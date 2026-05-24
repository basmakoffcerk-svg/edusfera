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
use Illuminate\Support\Collection;

class WalletPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $slug = 'wallet';

    protected static string $view = 'filament.pages.wallet-page';

    protected static ?string $navigationLabel = 'Мой баланс';

    protected static ?string $title = 'Мой баланс';

    protected static ?int $navigationSort = 41;

    public ?int $selectedTopUpAmount = 152;

    public ?string $customTopUpAmount = null;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, ['student', 'parent'], true);
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['student', 'parent'], true);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Финансы';
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, ['student', 'parent'], true)) {
            return null;
        }

        $balance = StudentBalance::query()->firstWhere('user_id', $user->id);
        $amount = (float) ($balance?->available_amount ?? 0);

        return number_format($amount, 2, '.', ' ');
    }

    public function choosePresetAmount(int $amount): void
    {
        if (! in_array($amount, [40, 152, 288], true)) {
            return;
        }

        $this->selectedTopUpAmount = $amount;
    }

    public function getTopUpAmountProperty(): int
    {
        return $this->resolveTopUpAmount();
    }

    public function getTopUpCtaLabelProperty(): string
    {
        return 'Оплатить ' . number_format((float) $this->topUpAmount, 2, '.', ' ') . ' BYN';
    }

    public function topUp(): void
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, ['student', 'parent'], true)) {
            abort(403);
        }

        $amount = $this->resolveTopUpAmount();

        if ($amount < 10 || $amount > 5000) {
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
        abort_unless($user && in_array($user->role, ['student', 'parent'], true), 403);

        $balance = StudentBalance::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'available_amount' => '0.00',
                'locked_amount' => '0.00',
                'total_topped_up' => '0.00',
                'total_spent' => '0.00',
                'total_refunded' => '0.00',
            ],
        );

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
            'presetAmounts' => [40, 152, 288],
            'selectedTopUpAmount' => $this->selectedTopUpAmount,
        ];
    }

    private function resolveTopUpAmount(): int
    {
        $custom = trim((string) $this->customTopUpAmount);

        if ($custom !== '' && is_numeric($custom)) {
            return (int) round((float) $custom);
        }

        return (int) ($this->selectedTopUpAmount ?? 0);
    }
}
