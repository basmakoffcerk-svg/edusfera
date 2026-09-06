<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Models\Lesson;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Покрывает эндпоинт `GET /api/v1/lessons/{id}/classroom-token`
 * спеки microservices-foundation:
 *  - 11.5: 200 `{token, room, urls}` при наличии прав; токен — валидный RS256
 *          classroom-JWT, проверяемый classroom-публичным ключом.
 *  - 11.6: 403 Forbidden без прав на урок (токен не выпускается).
 *  - 1.5/2.4: без Sanctum-токена защищённый роут возвращает 401.
 *  - несуществующий урок → 404 в формате конверта.
 *
 * Используются реальные personal access token'ы Sanctum (User::createToken),
 * чтобы проверять настоящий путь аутентификации без подмены мок-объектами.
 */
class ClassroomTokenEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private User $student;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);

        $this->subscription = Subscription::query()->create([
            'tutor_id' => $this->tutor->id,
            'plan' => SubscriptionPlan::PREMIUM,
            'status' => SubscriptionStatus::ACTIVE,
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ]);
    }

    private function makeLesson(): Lesson
    {
        return Lesson::forceCreate([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'duration_minutes' => 60,
            'price' => 1000,
            'platform_commission' => 200,
            'net_amount' => 800,
        ]);
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test', ['lessons:read'])->plainTextToken;
    }

    private function publicKey(): string
    {
        return (string) file_get_contents((string) config('classroom.jwt_public_key_path'));
    }

    public function test_returns_401_without_token(): void
    {
        // Требование 2.4: без валидного токена защищённый роут отвергает запрос.
        $lesson = $this->makeLesson();

        $response = $this->getJson("/api/v1/lessons/{$lesson->id}/classroom-token");

        $response->assertStatus(401);
    }

    public function test_returns_200_for_tutor_of_own_lesson(): void
    {
        // Требование 11.5: тьютор своего урока получает валидный classroom-JWT.
        $lesson = $this->makeLesson();

        $response = $this->withToken($this->tokenFor($this->tutor))
            ->getJson("/api/v1/lessons/{$lesson->id}/classroom-token");

        $response->assertOk();
        $response->assertJsonStructure([
            'token',
            'room',
            'urls' => ['media_server', 'ice_servers'],
        ]);
        $response->assertJsonPath('room', 'lesson-'.$lesson->id);

        // Токен декодируется classroom-публичным ключом (RS256) → подпись валидна.
        $decoded = JWT::decode($response->json('token'), new Key($this->publicKey(), 'RS256'));

        $this->assertSame($this->tutor->id, $decoded->sub);
        $this->assertSame('tutor', $decoded->role);
        $this->assertSame($lesson->id, $decoded->lesson_id);

        // urls присутствуют и содержат адрес media-сервера.
        $this->assertSame(config('classroom.media_server_url'), $response->json('urls.media_server'));
    }

    public function test_returns_200_for_student_of_own_lesson(): void
    {
        // Требование 11.5: студент своего урока тоже получает токен (role=student).
        $lesson = $this->makeLesson();

        $response = $this->withToken($this->tokenFor($this->student))
            ->getJson("/api/v1/lessons/{$lesson->id}/classroom-token");

        $response->assertOk();

        $decoded = JWT::decode($response->json('token'), new Key($this->publicKey(), 'RS256'));

        $this->assertSame($this->student->id, $decoded->sub);
        $this->assertSame('student', $decoded->role);
    }

    public function test_returns_403_for_unrelated_user(): void
    {
        // Требование 11.6: посторонний пользователь → 403, токен не выпускается.
        $lesson = $this->makeLesson();
        $outsider = User::factory()->create(['role' => 'student']);

        $response = $this->withToken($this->tokenFor($outsider))
            ->getJson("/api/v1/lessons/{$lesson->id}/classroom-token");

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'forbidden');
    }

    public function test_returns_404_for_missing_lesson(): void
    {
        // Требование 11.5: несуществующий урок → 404 в формате конверта.
        $response = $this->withToken($this->tokenFor($this->tutor))
            ->getJson('/api/v1/lessons/999999/classroom-token');

        $response->assertStatus(404);
        $response->assertJsonPath('error.code', 'not_found');
    }

    public function test_returns_422_when_tutor_subscription_is_expired(): void
    {
        $this->subscription->update([
            'status' => SubscriptionStatus::CANCELED,
            'current_period_ends_at' => now()->subDay(),
        ]);

        $lesson = $this->makeLesson();

        $response = $this->withToken($this->tokenFor($this->tutor))
            ->getJson("/api/v1/lessons/{$lesson->id}/classroom-token");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription']);
        $this->assertStringContainsString(
            'Для входа в виртуальный класс необходимо продлить подписку на платформу Edusfera.',
            $response->json('errors.subscription.0')
        );
    }

    public function test_returns_422_to_student_when_tutor_subscription_is_expired(): void
    {
        $this->subscription->update([
            'status' => SubscriptionStatus::CANCELED,
            'current_period_ends_at' => now()->subDay(),
        ]);

        $lesson = $this->makeLesson();

        $response = $this->withToken($this->tokenFor($this->student))
            ->getJson("/api/v1/lessons/{$lesson->id}/classroom-token");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription']);
        $this->assertStringContainsString(
            'Виртуальный класс преподавателя временно неактивен',
            $response->json('errors.subscription.0')
        );
    }
}
