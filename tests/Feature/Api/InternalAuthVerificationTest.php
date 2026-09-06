<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Contracts\Classroom\ClassroomTokenIssuer;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Тестирует эндпоинт `GET /api/internal/v1/auth/verify` для Nginx auth_request.
 */
class InternalAuthVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;
    private User $student;
    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        // Run Laravel Passport migrations for SQLite in-memory testing
        $this->artisan('migrate', [
            '--path' => 'vendor/laravel/passport/database/migrations',
            '--realpath' => true,
        ]);

        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);

        $this->lesson = Lesson::forceCreate([
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

    public function test_returns_401_for_missing_authorization_header(): void
    {
        $response = $this->getJson('/api/internal/v1/auth/verify');

        $response->assertStatus(401);
        $response->assertJsonPath('error', 'Unauthorized');
    }

    public function test_returns_401_for_invalid_bearer_format(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'InvalidToken'
        ])->getJson('/api/internal/v1/auth/verify');

        $response->assertStatus(401);
    }

    public function test_successfully_verifies_classroom_jwt(): void
    {
        /** @var ClassroomTokenIssuer $issuer */
        $issuer = app(ClassroomTokenIssuer::class);
        $token = $issuer->issue($this->lesson, $this->tutor);

        $response = $this->withToken($token)
            ->getJson('/api/internal/v1/auth/verify');

        $response->assertOk();
        $response->assertHeader('X-User-Id', (string) $this->tutor->id);
        $response->assertHeader('X-User-Role', 'tutor');
        $response->assertHeader('X-Classroom-Room-Id', 'lesson-' . $this->lesson->id);
    }

    public function test_successfully_verifies_sanctum_pat(): void
    {
        $token = $this->student->createToken('test-sanctum')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/internal/v1/auth/verify');

        $response->assertOk();
        $response->assertHeader('X-User-Id', (string) $this->student->id);
        $response->assertHeader('X-User-Role', 'student');
        $response->assertHeaderMissing('X-Classroom-Room-Id');
    }

    public function test_successfully_verifies_passport_s2s_token(): void
    {
        // Создаем Passport клиента для client_credentials напрямую через репозиторий
        $clientRepository = app(\Laravel\Passport\ClientRepository::class);
        $client = $clientRepository->createClientCredentialsGrantClient('test-s2s-client');

        $this->assertNotNull($client);

        // Получаем токен от Passport через oauth/token
        $tokenResponse = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->plainSecret,
        ]);

        $tokenResponse->assertOk();
        $accessToken = $tokenResponse->json('access_token');
        $this->assertNotEmpty($accessToken);

        // Проверяем токен через наш верификатор
        $response = $this->withToken($accessToken)
            ->getJson('/api/internal/v1/auth/verify');

        $response->assertOk();
        $response->assertHeader('X-User-Id', 'client-' . $client->id);
        $response->assertHeader('X-User-Role', 'service');
    }
}
