<?php

declare(strict_types=1);

namespace Tests\Feature\Classroom;

use App\Contracts\Classroom\ClassroomTokenIssuer;
use App\Domain\Classroom\RsaClassroomTokenIssuer;
use App\Models\ClassroomSession;
use App\Models\Lesson;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Покрывает требования 11.1, 11.2, 11.3, 11.4, 11.8 для ClassroomTokenIssuer.
 */
class ClassroomTokenIssuerTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);
    }

    private function publicKey(): string
    {
        return (string) file_get_contents((string) config('classroom.jwt_public_key_path'));
    }

    private function makeLesson(\DateTimeInterface|string $endTime): Lesson
    {
        return Lesson::forceCreate([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'start_time' => now()->subMinutes(10),
            'end_time' => $endTime,
            'duration_minutes' => 60,
            'price' => 1000,
            'platform_commission' => 200,
            'net_amount' => 800,
        ]);
    }

    private function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->publicKey(), 'RS256'));
    }

    public function test_default_binding_resolves_rsa_issuer(): void
    {
        $issuer = app(ClassroomTokenIssuer::class);

        $this->assertInstanceOf(RsaClassroomTokenIssuer::class, $issuer);
    }

    public function test_issue_returns_valid_rs256_jwt_decodable_with_public_key(): void
    {
        $lesson = $this->makeLesson(now()->addMinutes(50));

        $token = app(ClassroomTokenIssuer::class)->issue($lesson, $this->student);

        // RS256 JWT состоит из трёх сегментов.
        $this->assertCount(3, explode('.', $token));

        // Декодируется публичным ключом → подпись валидна.
        $decoded = $this->decode($token);
        $this->assertNotNull($decoded);

        // Заголовок содержит alg=RS256 и kid.
        $header = json_decode(
            base64_decode(strtr(explode('.', $token)[0], '-_', '+/')),
            true
        );
        $this->assertSame('RS256', $header['alg']);
        $this->assertArrayHasKey('kid', $header);
        $this->assertStringStartsWith('classroom-', $header['kid']);
    }

    public function test_token_contains_all_required_claims(): void
    {
        $lesson = $this->makeLesson(now()->addMinutes(50));

        $token = app(ClassroomTokenIssuer::class)->issue($lesson, $this->student);
        $decoded = $this->decode($token);

        $this->assertSame($this->student->id, $decoded->sub);
        $this->assertSame('lesson-'.$lesson->id, $decoded->room);
        $this->assertSame('student', $decoded->role);
        $this->assertIsArray($decoded->capabilities);
        $this->assertContains('whiteboard', $decoded->capabilities);
        $this->assertSame($lesson->id, $decoded->lesson_id);
        $this->assertSame((string) config('classroom.jwt_issuer'), $decoded->iss);
        $this->assertSame((string) config('classroom.jwt_audience'), $decoded->aud);
        $this->assertTrue(isset($decoded->iat), 'iat claim must be present');
        $this->assertTrue(isset($decoded->exp), 'exp claim must be present');
    }

    public function test_role_is_tutor_for_tutor_and_student_for_student(): void
    {
        $lesson = $this->makeLesson(now()->addMinutes(50));

        $tutorToken = app(ClassroomTokenIssuer::class)->issue($lesson, $this->tutor);
        $studentToken = app(ClassroomTokenIssuer::class)->issue($lesson, $this->student);

        $this->assertSame('tutor', $this->decode($tutorToken)->role);
        $this->assertSame('student', $this->decode($studentToken)->role);

        // Тьютор получает расширенный набор capabilities (включая screen).
        $this->assertContains('screen', $this->decode($tutorToken)->capabilities);
        $this->assertNotContains('screen', $this->decode($studentToken)->capabilities);
    }

    public function test_exp_is_bounded_by_lesson_end_plus_grace_for_near_lesson(): void
    {
        $grace = (int) config('classroom.jwt_grace');
        $endTime = now()->addMinutes(30);

        $lesson = $this->makeLesson($endTime);

        $token = app(ClassroomTokenIssuer::class)->issue($lesson, $this->student);
        $decoded = $this->decode($token);

        // Урок близко → exp ограничен концом урока + grace.
        $expectedExp = $endTime->getTimestamp() + $grace;
        $this->assertEqualsWithDelta($expectedExp, $decoded->exp, 2);

        // И не превышает now + max_ttl.
        $maxTtl = (int) config('classroom.jwt_max_ttl');
        $this->assertLessThanOrEqual(time() + $maxTtl + 2, $decoded->exp);
    }

    public function test_exp_is_bounded_by_max_ttl_for_far_future_lesson(): void
    {
        $maxTtl = (int) config('classroom.jwt_max_ttl');
        $grace = (int) config('classroom.jwt_grace');

        // Урок далеко в будущем → end+grace огромен, должен сработать max_ttl.
        $endTime = now()->addDays(10);
        $lesson = $this->makeLesson($endTime);

        $issuedAt = time();
        $token = app(ClassroomTokenIssuer::class)->issue($lesson, $this->student);
        $decoded = $this->decode($token);

        // exp ограничен now + max_ttl, а не концом урока + grace.
        $this->assertEqualsWithDelta($issuedAt + $maxTtl, $decoded->exp, 2);
        $this->assertLessThan($endTime->getTimestamp() + $grace, $decoded->exp);
    }

    public function test_room_is_taken_from_active_classroom_session_when_present(): void
    {
        $lesson = $this->makeLesson(now()->addMinutes(50));

        ClassroomSession::create([
            'lesson_id' => $lesson->id,
            'room_id' => 'room-uuid-123',
            'status' => ClassroomSession::STATUS_ACTIVE,
            'started_at' => now(),
        ]);

        $token = app(ClassroomTokenIssuer::class)->issue($lesson->fresh(), $this->student);

        $this->assertSame('room-uuid-123', $this->decode($token)->room);
    }
}
