<?php

declare(strict_types=1);

namespace Tests\Property\Classroom;

use App\Contracts\Classroom\ClassroomTokenIssuer;
use App\Models\Lesson;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Property-based тест для correctness property P3 (design.md, раздел 6):
 * classroom-JWT действителен только до конца урока + grace.
 *
 * Feature: microservices-foundation, Property P3: classroom JWT exp ≤ lesson end + grace
 *
 * Свойство формулируется так (квантор по всем выпущенным токенам):
 *
 *   ∀ token T выпущенный ClassroomTokenIssuer для lesson L:
 *       decoded(T).exp ≤ L.end_time + GRACE_PERIOD            (требование 11.3)
 *       decoded(T).exp ≤ now() + max_ttl                      (требование 11.3)
 *       decoded(T).exp = min(L.end_time + grace, now + max_ttl)
 *
 * Дополнительно для каждого токена проверяется наличие всех обязательных
 * claims (sub/room/role/capabilities/lesson_id/iss/aud/exp/iat, требование
 * 11.4) и корректный заголовок RS256 + kid `classroom-*` (требование 11.2).
 *
 * Генерация детерминирована через mt_srand с фиксированным сидом, ≥100
 * итераций. На каждой итерации:
 *   - end_time = now() + случайное смещение ∈ [-5 минут, +30 дней];
 *   - случайный субъект (тьютор/студент);
 *   - случайный status среди существующих констант Lesson (issuer от статуса
 *     не зависит — это лишь покрытие входного пространства).
 *
 * Декод выполняется тестовым публичным ключом из
 * config('classroom.jwt_public_key_path'), то есть подпись валидируется
 * реальным RS256-ключом.
 *
 * **Validates: Requirements 11.2, 11.3, 11.4**
 */
class ClassroomTokenExpiryTest extends TestCase
{
    use RefreshDatabase;

    /** Число итераций property-теста (требование задачи: минимум 100). */
    private const ITERATIONS = 120;

    /** Фиксированный сид для воспроизводимости контрпримеров. */
    private const SEED = 110311;

    /** Нижняя граница смещения end_time: 5 минут в прошлом. */
    private const MIN_OFFSET_SECONDS = -300;

    /** Верхняя граница смещения end_time: 30 дней в будущем. */
    private const MAX_OFFSET_SECONDS = 30 * 24 * 60 * 60;

    /** Допуск на вычисления времени (issuer берёт собственный time()). */
    private const DELTA_SECONDS = 2;

    private User $tutor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);
    }

    /**
     * Property P3: для всех сгенерированных уроков exp выпущенного токена не
     * превышает ни end_time + grace, ни now + max_ttl, и равен минимуму из
     * этих двух границ. Параллельно проверяются claims и заголовок.
     */
    public function test_classroom_token_exp_is_bounded_by_lesson_end_plus_grace(): void
    {
        mt_srand(self::SEED);

        $issuer = app(ClassroomTokenIssuer::class);
        $grace = (int) config('classroom.jwt_grace');
        $maxTtl = (int) config('classroom.jwt_max_ttl');
        $publicKey = $this->publicKey();

        $statuses = [
            Lesson::STATUS_CONFIRMED,
            Lesson::STATUS_PENDING,
            Lesson::STATUS_COMPLETED,
        ];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $offset = mt_rand(self::MIN_OFFSET_SECONDS, self::MAX_OFFSET_SECONDS);
            $endTime = Carbon::now()->copy()->addSeconds($offset);
            $status = $statuses[mt_rand(0, count($statuses) - 1)];
            $issuedForTutor = mt_rand(0, 1) === 1;
            $user = $issuedForTutor ? $this->tutor : $this->student;

            $lesson = $this->makeLesson($endTime, $status);

            // Момент выпуска фиксируем непосредственно вокруг issue(), чтобы
            // сравнивать с now + max_ttl корректно (issuer берёт свой time()).
            $beforeIssue = time();
            $token = $issuer->issue($lesson, $user);
            $afterIssue = time();

            $context = sprintf(
                'i=%d offset=%ds status=%s role=%s end_ts=%d',
                $i,
                $offset,
                $status,
                $issuedForTutor ? 'tutor' : 'student',
                $endTime->getTimestamp(),
            );

            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            $endPlusGrace = $endTime->getTimestamp() + $grace;
            $maxTtlBound = $afterIssue + $maxTtl;

            // Инвариант 1 (11.3): exp ≤ end_time + grace.
            $this->assertLessThanOrEqual(
                $endPlusGrace + self::DELTA_SECONDS,
                $decoded->exp,
                'exp превышает end_time + grace: '.$context,
            );

            // Инвариант 2 (11.3): exp ≤ now + max_ttl (+допуск).
            $this->assertLessThanOrEqual(
                $maxTtlBound + self::DELTA_SECONDS,
                $decoded->exp,
                'exp превышает now + max_ttl: '.$context,
            );

            // Инвариант 3 (11.3): exp = min(end+grace, now+max_ttl) ±допуск.
            $expectedExp = min($endPlusGrace, $beforeIssue + $maxTtl);
            $this->assertEqualsWithDelta(
                $expectedExp,
                $decoded->exp,
                self::DELTA_SECONDS,
                'exp не равен min(end+grace, now+max_ttl): '.$context,
            );

            // exp должен быть в будущем относительно iat.
            $this->assertGreaterThanOrEqual(
                $decoded->iat,
                $decoded->exp,
                'exp раньше iat: '.$context,
            );

            // Инвариант 4 (11.4): полный набор обязательных claims.
            $this->assertSame($user->id, $decoded->sub, 'sub != user.id: '.$context);
            $this->assertSame('lesson-'.$lesson->id, $decoded->room, 'room mismatch: '.$context);

            $expectedRole = $issuedForTutor ? 'tutor' : 'student';
            $this->assertSame($expectedRole, $decoded->role, 'role mismatch: '.$context);
            $this->assertContains($decoded->role, ['tutor', 'student'], 'role вне множества: '.$context);

            $this->assertIsArray($decoded->capabilities, 'capabilities не массив: '.$context);
            $this->assertContains('whiteboard', $decoded->capabilities, 'нет whiteboard: '.$context);

            $this->assertSame($lesson->id, $decoded->lesson_id, 'lesson_id mismatch: '.$context);
            $this->assertSame((string) config('classroom.jwt_issuer'), $decoded->iss, 'iss mismatch: '.$context);
            $this->assertSame((string) config('classroom.jwt_audience'), $decoded->aud, 'aud mismatch: '.$context);
            $this->assertTrue(isset($decoded->iat), 'iat отсутствует: '.$context);
            $this->assertTrue(isset($decoded->exp), 'exp отсутствует: '.$context);

            // Инвариант 5 (11.2): заголовок alg=RS256, kid начинается с classroom-.
            $header = $this->decodeHeader($token);
            $this->assertSame('RS256', $header['alg'], 'alg != RS256: '.$context);
            $this->assertArrayHasKey('kid', $header, 'нет kid: '.$context);
            $this->assertStringStartsWith('classroom-', (string) $header['kid'], 'kid не classroom-: '.$context);
        }
    }

    /**
     * Создаёт урок с заданным временем окончания и статусом. Прочие денежные
     * поля заполнены валидными значениями, т.к. issuer от них не зависит.
     */
    private function makeLesson(Carbon $endTime, string $status): Lesson
    {
        return Lesson::forceCreate([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'status' => $status,
            'payment_status' => Lesson::PAYMENT_PAID,
            'start_time' => $endTime->copy()->subMinutes(60),
            'end_time' => $endTime,
            'duration_minutes' => 60,
            'price' => 1000,
            'platform_commission' => 200,
            'net_amount' => 800,
        ]);
    }

    private function publicKey(): string
    {
        return (string) file_get_contents((string) config('classroom.jwt_public_key_path'));
    }

    /**
     * Декодирует JWT-заголовок (первый сегмент) в ассоциативный массив.
     *
     * @return array<string, mixed>
     */
    private function decodeHeader(string $token): array
    {
        $segment = explode('.', $token)[0];

        return (array) json_decode(
            base64_decode(strtr($segment, '-_', '+/')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
