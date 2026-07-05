<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\StudentBalance;
use App\Models\StudentBalanceLedgerEntry;
use App\Services\Finance\StudentBalanceService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Support\BynMoneyFormatter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WalletPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $slug = 'wallet';

    protected static string $view = 'filament.pages.wallet-page';

    protected static ?string $navigationLabel = 'Мой баланс';

    protected static ?string $title = 'Мой баланс';

    protected static ?int $navigationSort = 41;

    public const PRESET_AMOUNTS = [40, 152, 288];

    public float|int|null $selectedTopUpAmount = 152;

    public ?string $customTopUpAmount = null;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, [\App\Enums\UserRole::Student, \App\Enums\UserRole::Parent], true);
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [\App\Enums\UserRole::Student, \App\Enums\UserRole::Parent], true);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Финансы';
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, [\App\Enums\UserRole::Student, \App\Enums\UserRole::Parent], true)) {
            return null;
        }

        $balance = StudentBalance::query()->firstWhere('user_id', $user->id);
        $amount = (float) ($balance?->available_amount ?? 0);

        return number_format($amount, 2, '.', ' ');
    }

    public function choosePresetAmount(float|int $amount): void
    {
        if (! in_array((int) $amount, self::PRESET_AMOUNTS, true)) {
            return;
        }

        $this->selectedTopUpAmount = $amount;
        $this->customTopUpAmount = null;
    }

    public function getTopUpAmountProperty(): float
    {
        return $this->resolveTopUpAmount();
    }

    public function getTopUpCtaLabelProperty(): string
    {
        return 'Оплатить '.number_format((float) $this->topUpAmount, 2, '.', ' ').' BYN';
    }

    public function topUp(): void
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, [\App\Enums\UserRole::Student, \App\Enums\UserRole::Parent], true)) {
            abort(403);
        }

        $amount = $this->resolveTopUpAmount();

        if ($amount < 10.0 || $amount > 5000.0) {
            Notification::make()
                ->title('Сумма пополнения должна быть от 10 до 5000 BYN')
                ->danger()
                ->send();

            return;
        }

        $gateway = app(PaymentGatewayInterface::class);
        $response = $gateway->createPayment([
            'wallet_topup' => true,
            'user_id' => $user->id,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'BYN',
        ]);

        if (! ($response['success'] ?? false)) {
            Notification::make()
                ->title('Пополнение не прошло')
                ->danger()
                ->send();

            return;
        }

        if (($response['status'] ?? '') === 'pending' && isset($response['redirect_url'])) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($user, $amount, $response) {
                \App\Models\WalletTopup::query()->create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => 'BYN',
                    'status' => 'pending',
                    'gateway_transaction_id' => $response['gateway_transaction_id'] ?? null,
                    'gateway_response' => $response,
                ]);
            });

            $this->redirect($response['redirect_url']);
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $amount, $response) {
            \App\Models\WalletTopup::query()->create([
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => 'BYN',
                'status' => 'success',
                'gateway_transaction_id' => $response['gateway_transaction_id'] ?? null,
                'gateway_response' => $response,
            ]);

            app(StudentBalanceService::class)->credit(
                balance: app(StudentBalanceService::class)->getOrCreate($user->id),
                amount: number_format($amount, 2, '.', ''),
                currency: 'BYN',
                type: StudentBalanceLedgerEntry::TYPE_TOPUP,
                meta: [
                    'source' => 'wallet_page',
                    'gateway_transaction_id' => $response['gateway_transaction_id'] ?? null,
                ],
            );
        });

        $this->customTopUpAmount = null;

        Notification::make()
            ->title('Баланс пополнен')
            ->body('Средства уже доступны для оплаты уроков в один клик.')
            ->success()
            ->send();
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        abort_unless($user && in_array($user->role, [\App\Enums\UserRole::Student, \App\Enums\UserRole::Parent], true), 403);

        $balance = app(StudentBalanceService::class)->getOrCreate($user->id);

        $entries = StudentBalanceLedgerEntry::query()
            ->with(['lesson.tutor'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(25)
            ->get();

        return [
            'availableHtml' => BynMoneyFormatter::format((string) $balance->available_amount)->toHtml(),
            'lockedHtml' => BynMoneyFormatter::format((string) $balance->locked_amount)->toHtml(),
            'entries' => $entries,
            'presetAmounts' => self::PRESET_AMOUNTS,
            'selectedTopUpAmount' => $this->selectedTopUpAmount,
        ];
    }

    private function resolveTopUpAmount(): float
    {
        $custom = trim((string) $this->customTopUpAmount);

        if ($custom !== '' && is_numeric($custom)) {
            return round((float) $custom, 2);
        }

        return (float) ($this->selectedTopUpAmount ?? 0.0);
    }
}
