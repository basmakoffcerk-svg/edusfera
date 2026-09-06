<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Filament\Pages\WalletPage;
use App\Models\StudentBalance;
use App\Models\StudentBalanceLedgerEntry;
use App\Models\User;
use App\Models\WalletTopup;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class WalletTopupTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private string $webpaySecret = 'test_webpay_secret';

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create(['role' => 'student', 'phone' => '+375292222222']);

        Config::set('payments.webpay.secret_key', $this->webpaySecret);
        Config::set('payments.webhook_require_signature', false);
        Config::set('payments.webhook_require_ip_allowlist', false);
    }

    public function test_it_credits_balance_immediately_with_mock_gateway(): void
    {
        Config::set('payments.gateway', 'mock');

        $this->actingAs($this->student);

        // По умолчанию selectedTopUpAmount = 152
        Livewire::test(WalletPage::class)
            ->call('topUp')
            ->assertHasNoErrors();

        // Проверяем начисление баланса
        $balance = StudentBalance::query()->where('user_id', $this->student->id)->first();
        $this->assertNotNull($balance);
        $this->assertEquals('152.00', $balance->available_amount);

        // Проверяем запись пополнения
        $topup = WalletTopup::query()->where('user_id', $this->student->id)->first();
        $this->assertNotNull($topup);
        $this->assertEquals('152.00', $topup->amount);
        $this->assertEquals('success', $topup->status);
    }

    public function test_it_does_not_credit_balance_immediately_with_webpay_gateway(): void
    {
        Config::set('payments.gateway', 'webpay');

        $mockGateway = $this->createMock(PaymentGatewayInterface::class);
        $mockGateway->method('createPayment')
            ->willReturn([
                'success' => true,
                'gateway_transaction_id' => 'checkout-token-topup-123',
                'status' => 'pending',
                'redirect_url' => 'https://apisandbox.webpay.by/checkout?token=checkout-token-topup-123',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $this->actingAs($this->student);

        Livewire::test(WalletPage::class)
            ->set('customTopUpAmount', '100')
            ->call('topUp')
            ->assertRedirect('https://apisandbox.webpay.by/checkout?token=checkout-token-topup-123');

        // Баланс не должен измениться (не создан или равен 0)
        $balance = StudentBalance::query()->where('user_id', $this->student->id)->first();
        if ($balance) {
            $this->assertEquals('0.00', $balance->available_amount);
        }

        // Запись пополнения должна быть pending
        $topup = WalletTopup::query()->where('user_id', $this->student->id)->first();
        $this->assertNotNull($topup);
        $this->assertEquals('100.00', $topup->amount);
        $this->assertEquals('pending', $topup->status);
        $this->assertEquals('checkout-token-topup-123', $topup->gateway_transaction_id);
    }

    public function test_webhook_credits_pending_topup_balance(): void
    {
        Config::set('payments.gateway', 'webpay');

        // Создаем ожидающий top-up
        $topup = WalletTopup::query()->create([
            'user_id' => $this->student->id,
            'amount' => '250.00',
            'currency' => 'BYN',
            'status' => 'pending',
            'gateway_transaction_id' => 'checkout-token-topup-999',
        ]);

        $payload = [
            'transaction_id' => 'checkout-token-topup-999',
            'payment_type' => 'completion',
            'status' => 'completed',
            'amount' => '250.00',
            'currency' => 'BYN',
        ];

        $response = $this->postJson('/webhooks/webpay', $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Статус пополнения должен измениться на success
        $topup->refresh();
        $this->assertEquals('success', $topup->status);

        // Баланс должен быть пополнен
        $balance = StudentBalance::query()->where('user_id', $this->student->id)->first();
        $this->assertNotNull($balance);
        $this->assertEquals('250.00', $balance->available_amount);

        // Проверяем запись в реестре Ledger
        $ledgerEntry = StudentBalanceLedgerEntry::query()
            ->where('student_balance_id', $balance->id)
            ->where('type', StudentBalanceLedgerEntry::TYPE_TOPUP)
            ->first();
        $this->assertNotNull($ledgerEntry);
        $this->assertEquals('250.00', $ledgerEntry->amount);
    }

    public function test_webhook_updates_status_on_failed_topup(): void
    {
        Config::set('payments.gateway', 'webpay');

        $topup = WalletTopup::query()->create([
            'user_id' => $this->student->id,
            'amount' => '250.00',
            'currency' => 'BYN',
            'status' => 'pending',
            'gateway_transaction_id' => 'checkout-token-topup-999',
        ]);

        $payload = [
            'transaction_id' => 'checkout-token-topup-999',
            'payment_type' => 'failed',
            'status' => 'failed',
        ];

        $response = $this->postJson('/webhooks/webpay', $payload);

        $response->assertStatus(200);

        $topup->refresh();
        $this->assertEquals('failed', $topup->status);

        // Баланс не должен быть начислен
        $balance = StudentBalance::query()->where('user_id', $this->student->id)->first();
        if ($balance) {
            $this->assertEquals('0.00', $balance->available_amount);
        }
    }
}
