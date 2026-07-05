<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Lesson;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $tutor;
    private Lesson $lesson;
    private Transaction $transaction;
    private string $webhookSecret = 'test_webhook_secret';
    private string $bepaidSecret = 'test_bepaid_secret';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->create(['role' => 'tutor', 'phone' => '+375291111111']);
        $this->tutor->tutorProfile()->create([
            'subjects' => ['Белорусский язык'],
            'audiences' => ['Подготовка к ЦЭ'],
            'price_per_hour' => '40.00',
            'experience_years' => 5,
            'legal_status' => 'self_employed',
            'bio' => 'Подготовка к экзаменам.',
            'is_verified' => true,
            'verification_status' => 'approved',
            'lesson_formats' => ['individual_online'],
        ]);

        $this->student = User::factory()->create(['role' => 'student', 'phone' => '+375292222222']);

        $this->lesson = Lesson::query()->forceCreate([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'start_time' => CarbonImmutable::now('UTC')->addDay(),
            'end_time' => CarbonImmutable::now('UTC')->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'platform_commission' => '4.00',
            'net_amount' => '34.82',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => CarbonImmutable::now('UTC')->addMinutes(15),
        ]);

        // Создаем pending транзакцию
        $this->transaction = Transaction::query()->create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'amount' => '40.00',
            'platform_commission' => '4.00',
            'acquiring_fee' => '1.18',
            'net_amount' => '34.82',
            'currency' => 'BYN',
            'status' => Transaction::STATUS_PENDING,
            'payment_method' => 'card',
            'gateway_transaction_id' => 'checkout-token-12345',
            'gateway_response' => ['charged_amount' => '40.00'],
        ]);

        Config::set('payments.webhook_secret', $this->webhookSecret);
        Config::set('payments.bepaid.secret_key', $this->bepaidSecret);
        Config::set('payments.webhook_require_signature', true);
        Config::set('payments.webhook_require_ip_allowlist', false);
    }

    public function test_it_successfully_processes_bepaid_success_webhook_with_valid_signature(): void
    {
        Config::set('payments.gateway', 'bepaid');

        $payload = [
            'transaction' => [
                'checkout_token' => 'checkout-token-12345',
                'status' => 'successful',
                'amount' => 4000,
                'currency' => 'BYN',
                'uid' => 'bepaid-tr-999',
            ]
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $this->bepaidSecret);

        $response = $this->postJson('/payments/webhook', $payload, [
            'Content-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->transaction->refresh();
        $this->lesson->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $this->transaction->status);
        $this->assertSame(Lesson::PAYMENT_PAID, $this->lesson->payment_status);
        $this->assertSame(Lesson::STATUS_CONFIRMED, $this->lesson->status);
    }

    public function test_it_rejects_bepaid_webhook_with_invalid_signature(): void
    {
        Config::set('payments.gateway', 'bepaid');

        $payload = [
            'transaction' => [
                'checkout_token' => 'checkout-token-12345',
                'status' => 'successful',
            ]
        ];

        $response = $this->postJson('/payments/webhook', $payload, [
            'Content-Signature' => 'invalid_signature_value',
        ]);

        $response->assertStatus(403);
        $this->transaction->refresh();
        $this->assertSame(Transaction::STATUS_PENDING, $this->transaction->status);
    }

    public function test_it_successfully_processes_legacy_webhook_with_valid_signature(): void
    {
        Config::set('payments.gateway', 'mock');

        $payload = [
            'gateway_transaction_id' => 'checkout-token-12345',
            'event' => 'payment.success',
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $this->webhookSecret);

        $response = $this->postJson('/payments/webhook', $payload, [
            'X-Webhook-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->transaction->refresh();
        $this->transaction->lesson->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $this->transaction->status);
    }

    public function test_it_marks_transaction_failed_on_bepaid_failed_webhook(): void
    {
        Config::set('payments.gateway', 'bepaid');

        $payload = [
            'transaction' => [
                'checkout_token' => 'checkout-token-12345',
                'status' => 'failed',
            ]
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $this->bepaidSecret);

        $response = $this->postJson('/payments/webhook', $payload, [
            'Content-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        $this->transaction->refresh();
        $this->lesson->refresh();

        $this->assertSame(Transaction::STATUS_FAILED, $this->transaction->status);
        $this->assertSame(Lesson::PAYMENT_UNPAID, $this->lesson->payment_status);
    }

    public function test_checkout_success_page_verifies_payment_synchronously_to_resolve_race_condition(): void
    {
        $mockGateway = $this->createMock(\App\Services\Payment\PaymentGatewayInterface::class);
        $mockGateway->method('verifyPayment')
            ->with('checkout-token-12345')
            ->willReturn(true);
        
        $this->app->instance(\App\Services\Payment\PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($this->student)
            ->get("/checkout/{$this->lesson->id}/success");

        $response->assertStatus(200);

        $this->transaction->refresh();
        $this->lesson->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $this->transaction->status);
        $this->assertSame(Lesson::PAYMENT_PAID, $this->lesson->payment_status);
        $this->assertSame(Lesson::STATUS_CONFIRMED, $this->lesson->status);
    }
}
