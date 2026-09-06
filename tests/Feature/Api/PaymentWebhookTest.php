<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Lesson;
use App\Models\Transaction;
use App\Models\User;
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
    private string $webpaySecret = 'test_webpay_secret';

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
            'unp' => '123456789',
            'webpay_billing_id' => 'billing_123',
            'webpay_account_id' => 'account_123',
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
            'platform_commission' => '6.00',
            'net_amount' => '34.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => CarbonImmutable::now('UTC')->addMinutes(15),
        ]);

        $this->transaction = Transaction::query()->create([
            'lesson_id' => $this->lesson->id,
            'user_id' => $this->student->id,
            'amount' => '40.00',
            'platform_commission' => '6.00',
            'acquiring_fee' => '0.00',
            'net_amount' => '34.00',
            'currency' => 'BYN',
            'status' => Transaction::STATUS_PENDING,
            'payment_method' => 'card',
            'gateway_transaction_id' => 'checkout-token-12345',
            'gateway_response' => ['charged_amount' => '40.00'],
        ]);

        Config::set('payments.webpay.secret_key', $this->webpaySecret);
        Config::set('payments.webpay.allowed_ips', ['127.0.0.1', '178.163.225.84']);
        Config::set('payments.webhook_require_ip_allowlist', false);
    }

    public function test_it_successfully_processes_webpay_success_webhook(): void
    {
        Config::set('payments.gateway', 'webpay');

        $payload = [
            'transaction_id' => 'checkout-token-12345',
            'payment_type' => 'completion',
            'status' => 'completed',
            'amount' => '40.00',
            'currency' => 'BYN',
        ];
        $payload['ws_signature'] = $this->signPayload($payload);

        $response = $this->postJson('/webhooks/webpay', $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->transaction->refresh();
        $this->lesson->refresh();

        $this->assertSame(Transaction::STATUS_SUCCESS, $this->transaction->status);
        $this->assertSame(Lesson::PAYMENT_PAID, $this->lesson->payment_status);
        $this->assertSame(Lesson::STATUS_CONFIRMED, $this->lesson->status);
    }

    public function test_it_marks_transaction_failed_on_webpay_failed_webhook(): void
    {
        Config::set('payments.gateway', 'webpay');

        $payload = [
            'transaction_id' => 'checkout-token-12345',
            'payment_type' => 'failed',
            'status' => 'failed',
        ];
        $payload['ws_signature'] = $this->signPayload($payload);

        $response = $this->postJson('/webhooks/webpay', $payload);

        $response->assertStatus(200);

        $this->transaction->refresh();
        $this->lesson->refresh();

        $this->assertSame(Transaction::STATUS_FAILED, $this->transaction->status);
        $this->assertSame(Lesson::PAYMENT_UNPAID, $this->lesson->payment_status);
    }

    private function signPayload(array $payload): string
    {
        $batch = ($payload['batch_timestamp'] ?? '').
                 ($payload['currency_id'] ?? $payload['currency'] ?? '').
                 ($payload['amount'] ?? '').
                 ($payload['payment_method'] ?? '').
                 ($payload['order_id'] ?? '').
                 ($payload['site_order_id'] ?? '').
                 ($payload['transaction_id'] ?? '').
                 ($payload['payment_type'] ?? '').
                 ($payload['rrn'] ?? '').
                 $this->webpaySecret;

        return md5($batch);
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
