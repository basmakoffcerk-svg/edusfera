<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\StudentBalance;
use App\Models\User;
use App\Services\Finance\StudentBalanceService;
use App\Support\SecuritySanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityInfographicsExtendedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Topic 07: Fake Webhook Protection
     * Verifies that Webhooks without valid signature or from unauthorized IP are rejected.
     */
    public function test_fake_webhook_rejected_when_signature_invalid(): void
    {
        $payload = [
            'event' => 'completion',
            'transaction_id' => 'tx_fake_123',
            'status' => 'success',
            'ws_signature' => 'invalid_signature_hash',
        ];

        config(['payments.webhook_require_signature' => true]);

        $response = $this->postJson(route('payments.webpay.webhook'), $payload, [
            'X-WebPay-Signature' => 'invalid_signature_hash',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Topic 15: Log Masking
     * Verifies that sensitive keys (passwords, tokens, CVV, secrets) are masked before logging.
     */
    public function test_sensitive_data_is_masked(): void
    {
        $input = [
            'user' => 'john',
            'password' => 'SuperSecret123!',
            'card_number' => '4111111111111111',
            'nested' => [
                'api_key' => 'token_xyz',
            ],
        ];

        $masked = SecuritySanitizer::maskSensitiveData($input);

        $this->assertEquals('***MASKED***', $masked['password']);
        $this->assertEquals('***MASKED***', $masked['card_number']);
        $this->assertEquals('***MASKED***', $masked['nested']['api_key']);
        $this->assertEquals('john', $masked['user']);
    }

    /**
     * Topic 11: SSRF Protection
     * Verifies that internal IP addresses, loopback, and unsafe protocols are rejected.
     */
    public function test_ssrf_blocks_internal_ips_and_localhost(): void
    {
        $this->assertFalse(SecuritySanitizer::isSafeUrl('http://127.0.0.1/admin'));
        $this->assertFalse(SecuritySanitizer::isSafeUrl('http://localhost:8088/secret'));
        $this->assertFalse(SecuritySanitizer::isSafeUrl('file:///etc/passwd'));
        $this->assertFalse(SecuritySanitizer::isSafeUrl('http://169.254.169.254/latest/meta-data'));

        $this->assertTrue(SecuritySanitizer::isSafeUrl('https://edusfera.by/images/logo.png'));
    }

    /**
     * Topic 10: Session Security Options
     * Verifies that session HttpOnly flag is set to true.
     */
    public function test_session_cookie_is_httponly(): void
    {
        $this->assertTrue(config('session.http_only'));
    }

    /**
     * Topic 14: Balance Debit & Race Condition Protection
     * Verifies that balance debit throws error when funds are insufficient.
     */
    public function test_balance_debit_prevents_negative_balance(): void
    {
        $user = User::factory()->create();
        $service = app(StudentBalanceService::class);
        $balance = $service->getOrCreate($user->id);

        $balance->update(['available_amount' => '10.00']);

        $lesson = new \App\Models\Lesson([
            'student_id' => $user->id,
            'tutor_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);
        $lesson->price = 50.00;
        $lesson->platform_commission = 5.00;
        $lesson->net_amount = 45.00;
        $lesson->save();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $service->debitForLesson($balance, '50.00', 'BYN', $lesson);
    }
}
