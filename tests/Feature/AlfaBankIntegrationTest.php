<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletTopup;
use App\Services\Payment\AlfaBankPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AlfaBankIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('payments.gateway', 'alfa');
        Config::set('payments.alfabank.test_mode', true);
        Config::set('payments.alfabank.user_name', 'test_alfa_user');
        Config::set('payments.alfabank.password', 'test_alfa_password');
        Config::set('payments.alfabank.api_url', 'https://web.rbsuat.com/ab_by/rest');
    }

    public function test_gateway_binding_resolves_alfabank_gateway(): void
    {
        $gateway = app(PaymentGatewayInterface::class);
        $this->assertInstanceOf(AlfaBankPaymentGateway::class, $gateway);
    }

    public function test_alfabank_gateway_creates_preauth_payment_in_test_mode(): void
    {
        $gateway = app(AlfaBankPaymentGateway::class);

        $result = $gateway->createPayment([
            'amount' => 45.00,
            'lesson_id' => 123,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('authorized', $result['status']);
        $this->assertNotEmpty($result['gateway_transaction_id']);
        $this->assertStringStartsWith('alfa_sb_', $result['gateway_transaction_id']);
    }

    public function test_alfabank_gateway_calls_real_api_when_mocked(): void
    {
        Config::set('payments.alfabank.test_mode', false);

        Http::fake([
            'https://web.rbsuat.com/ab_by/rest/registerPreAuth.do' => Http::response([
                'errorCode' => 0,
                'orderId' => 'alfa_guid_12345678',
                'formUrl' => 'https://web.rbsuat.com/ab_by/payment.html?mdOrder=alfa_guid_12345678',
            ], 200),
        ]);

        $gateway = app(AlfaBankPaymentGateway::class);
        $result = $gateway->createPayment([
            'amount' => 40.00,
            'lesson_id' => 456,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('alfa_guid_12345678', $result['gateway_transaction_id']);
        $this->assertStringContainsString('mdOrder=alfa_guid_12345678', $result['redirect_url']);
    }

    public function test_alfabank_gateway_captures_and_refunds(): void
    {
        $gateway = app(AlfaBankPaymentGateway::class);

        // In test mode with sandbox id
        $txId = 'alfa_sb_test_tx_999';
        $this->assertTrue($gateway->verifyPayment($txId));
        $this->assertTrue($gateway->capturePayment($txId, 40.00));
        $this->assertTrue($gateway->voidPayment($txId));
        $this->assertTrue($gateway->refundPayment($txId, 40.00));
    }

    public function test_alfabank_webhook_captures_pending_transaction(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $tutor = User::factory()->create(['role' => 'tutor']);

        $lesson = Lesson::query()->forceCreate([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '50.00',
            'platform_commission' => '0.00',
            'net_amount' => '50.00',
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'status' => Lesson::STATUS_CONFIRMED,
        ]);

        $tx = Transaction::query()->create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'amount' => '50.00',
            'platform_commission' => '0.00',
            'acquiring_fee' => '0.00',
            'net_amount' => '50.00',
            'currency' => 'BYN',
            'payment_method' => 'card',
            'status' => Transaction::STATUS_PENDING,
            'gateway_transaction_id' => 'alfa_order_test_777',
        ]);

        $response = $this->postJson('/payments/alfabank/webhook', [
            'mdOrder' => 'alfa_order_test_777',
            'operation' => 'deposited',
            'status' => 2,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $tx->refresh();
        $this->assertSame(Transaction::STATUS_SUCCESS, $tx->status);
    }

    public function test_alfabank_webhook_credits_wallet_topup(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $topup = WalletTopup::create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'currency' => 'BYN',
            'status' => 'pending',
            'gateway' => 'alfa',
            'gateway_transaction_id' => 'alfa_wallet_tx_888',
        ]);

        $response = $this->postJson('/webhooks/alfabank', [
            'mdOrder' => 'alfa_wallet_tx_888',
            'operation' => 'deposited',
            'status' => 2,
        ]);

        $response->assertOk();
        $topup->refresh();
        $this->assertSame('success', $topup->status);
    }

    public function test_checkout_alfa_sdk_init_returns_md_order_and_sdk_url(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $tutor = User::factory()->create(['role' => 'tutor']);
        \App\Models\TutorProfile::create(['user_id' => $tutor->id]);

        $lesson = Lesson::forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'price' => '50.00',
            'platform_commission' => '0.00',
            'net_amount' => '50.00',
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_lock_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($student)
            ->postJson(route('checkout.alfa-sdk.init', $lesson), [
                'package_code' => 'single',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'mdOrder',
            'transaction_id',
            'status',
            'web_sdk_url',
            'api_context',
            'amount',
            'currency',
            'redirect_url',
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('mdOrder'));
        $this->assertStringContainsString('multiframe/main.js', $response->json('web_sdk_url'));
    }

    public function test_subscription_init_alfa_sdk_returns_md_order_for_tutor(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        \App\Models\TutorProfile::create(['user_id' => $tutor->id]);

        $response = $this->actingAs($tutor)
            ->postJson('/api/subscription/init-alfa-sdk', [
                'plan' => 'pro',
                'isYearly' => false,
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'mdOrder',
            'web_sdk_url',
            'api_context',
            'redirect_url',
            'amount',
            'currency',
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('mdOrder'));
    }

    public function test_checkout_card_payment_can_be_completed_after_sdk_init(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);

        $lesson = Lesson::forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '50.00',
            'platform_commission' => '0.00',
            'net_amount' => '50.00',
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'status' => Lesson::STATUS_PENDING,
            'payment_lock_expires_at' => now()->addMinutes(15),
        ]);

        // 1. SDK init (simulating page load)
        $initResponse = $this->actingAs($student)
            ->postJson(route('checkout.alfa-sdk.init', $lesson), [
                'package_code' => 'single',
            ]);
        $initResponse->assertOk();

        // 2. User confirms/submits card payment without deadlock
        $payResponse = $this->actingAs($student)
            ->post(route('checkout.pay', $lesson), [
                'package_code' => 'single',
                'payment_method' => 'card',
            ]);

        $payResponse->assertRedirect(route('checkout.success', $lesson));

        $lesson->refresh();
        $this->assertEquals(Lesson::PAYMENT_PAID, $lesson->payment_status);
        $this->assertEquals(Lesson::STATUS_CONFIRMED, $lesson->status);
    }

    public function test_checkout_page_reload_does_not_fail_with_already_initiated_error(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);

        $lesson = Lesson::forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '50.00',
            'platform_commission' => '0.00',
            'net_amount' => '50.00',
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'status' => Lesson::STATUS_PENDING,
            'payment_lock_expires_at' => now()->addMinutes(15),
        ]);

        // First init
        $first = $this->actingAs($student)
            ->postJson(route('checkout.alfa-sdk.init', $lesson), [
                'package_code' => 'single',
            ]);
        $first->assertOk();

        // Second init (page reload or package re-selection)
        $second = $this->actingAs($student)
            ->postJson(route('checkout.alfa-sdk.init', $lesson), [
                'package_code' => 'single',
            ]);
        $second->assertOk();
        $this->assertEquals($first->json('mdOrder'), $second->json('mdOrder'));
    }

    public function test_checkout_switching_payment_method_from_card_to_wallet_succeeds(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);

        // Fund student wallet
        $balance = app(\App\Services\Finance\StudentBalanceService::class)->getOrCreate($student->id);
        $balance->update(['available_amount' => '100.00']);

        $lesson = Lesson::forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '50.00',
            'platform_commission' => '0.00',
            'net_amount' => '50.00',
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'status' => Lesson::STATUS_PENDING,
            'payment_lock_expires_at' => now()->addMinutes(15),
        ]);

        // First card init runs
        $this->actingAs($student)
            ->postJson(route('checkout.alfa-sdk.init', $lesson), [
                'package_code' => 'single',
            ])->assertOk();

        // User switches to wallet and pays
        $payResponse = $this->actingAs($student)
            ->post(route('checkout.pay', $lesson), [
                'package_code' => 'single',
                'payment_method' => 'wallet',
            ]);

        $payResponse->assertRedirect(route('checkout.success', $lesson));

        $lesson->refresh();
        $this->assertEquals(Lesson::PAYMENT_PAID, $lesson->payment_status);
        $this->assertEquals(Lesson::STATUS_CONFIRMED, $lesson->status);
    }
}
