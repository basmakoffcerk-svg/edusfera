<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\StudentBalance;
use App\Models\StudentBalanceLedgerEntry;
use App\Models\Transaction;
use App\Models\WalletTopup;
use App\Services\Finance\StudentBalanceService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Support\BynMoneyFormatter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class WalletPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $slug = 'wallet';

    protected static string $view = 'filament.pages.wallet-page';

    protected static ?string $navigationLabel = 'Учёт денег';

    protected static ?string $title = 'Учёт денег и история оплат';

    protected static ?int $navigationSort = 41;

    public const PRESET_AMOUNTS = [40, 152, 288];

    public float|int|null $selectedTopUpAmount = 152;

    public ?string $customTopUpAmount = null;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user && ($user->isStudent() || $user->isParent());
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isStudent() || $user->isParent() || $user->isAdmin());
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Финансы';
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user || (! $user->isStudent() && ! $user->isParent())) {
            return null;
        }

        $balance = StudentBalance::query()->firstWhere('user_id', $user->id);
        $spent = (float) ($balance?->total_spent ?? 0);

        return number_format($spent, 2, '.', ' ').' BYN';
    }

    public function mount(): void
    {
        $this->checkPendingTopups();
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

        if (! $user || ! in_array($user->role, [UserRole::Student, UserRole::Parent], true)) {
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
                ->body($response['message'] ?? 'Неизвестная ошибка платежного шлюза')
                ->danger()
                ->send();

            return;
        }

        // WebPAY возвращает status=authorized (холд до ввода карты/3-D Secure
        // ещё не подтверждён) — деньги при этом НЕ получены. Кредитуем баланс
        // только после подтверждения (webhook / checkPendingTopups).
        // Мгновенное зачисление допустимо лишь для синхронных шлюзов (mock),
        // которые возвращают status=success.
        $responseStatus = (string) ($response['status'] ?? '');

        if ($responseStatus !== 'success') {
            DB::transaction(function () use ($user, $amount, $response) {
                WalletTopup::query()->create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => 'BYN',
                    'status' => 'pending',
                    'gateway_transaction_id' => $response['gateway_transaction_id'] ?? null,
                    'gateway_response' => $response,
                ]);
            });

            if (isset($response['redirect_url'])) {
                $this->redirect($response['redirect_url']);

                return;
            }

            Notification::make()
                ->title('Платёж инициирован')
                ->body('Баланс будет пополнен после подтверждения платежа системой.')
                ->info()
                ->send();

            return;
        }

        DB::transaction(function () use ($user, $amount, $response) {
            WalletTopup::query()->create([
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
            ->body('Средства уже доступны для оплаты уроков.')
            ->success()
            ->send();
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        abort_unless($user && in_array($user->role, [UserRole::Student, UserRole::Parent], true), 403);

        $balance = app(StudentBalanceService::class)->getOrCreate($user->id);

        $entries = StudentBalanceLedgerEntry::query()
            ->with(['lesson.tutor'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(50)
            ->get();

        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(50)
            ->get();

        $totalSpent = (float) $balance->total_spent;
        $totalRefunded = (float) $balance->total_refunded;

        return [
            'totalSpent' => $totalSpent,
            'totalRefunded' => $totalRefunded,
            'totalSpentHtml' => BynMoneyFormatter::format((string) $balance->total_spent)->toHtml(),
            'totalRefundedHtml' => BynMoneyFormatter::format((string) $balance->total_refunded)->toHtml(),
            'availableAmount' => (float) $balance->available_amount,
            'lockedAmount' => (float) $balance->locked_amount,
            'availableHtml' => BynMoneyFormatter::format((string) $balance->available_amount)->toHtml(),
            'lockedHtml' => BynMoneyFormatter::format((string) $balance->locked_amount)->toHtml(),
            'entries' => $entries,
            'transactions' => $transactions,
        ];
    }

    private function checkPendingTopups(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $pendingTopups = WalletTopup::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->get();

        if ($pendingTopups->isEmpty()) {
            return;
        }

        $gateway = app(PaymentGatewayInterface::class);

        foreach ($pendingTopups as $topup) {
            if ($topup->gateway_transaction_id && $gateway->verifyPayment($topup->gateway_transaction_id)) {
                DB::transaction(function () use ($topup, $user) {
                    // Атомарная смена статуса — защита от двойного зачисления
                    // при гонке с webhook (кто первый перевёл pending→success,
                    // тот и кредитует баланс).
                    $claimed = WalletTopup::query()
                        ->whereKey($topup->id)
                        ->where('status', 'pending')
                        ->update(['status' => 'success']);

                    if ($claimed === 0) {
                        return;
                    }

                    app(StudentBalanceService::class)->credit(
                        balance: app(StudentBalanceService::class)->getOrCreate($user->id),
                        amount: number_format((float) $topup->amount, 2, '.', ''),
                        currency: $topup->currency ?: 'BYN',
                        type: StudentBalanceLedgerEntry::TYPE_TOPUP,
                        meta: [
                            'source' => 'auto_verification',
                            'gateway_transaction_id' => $topup->gateway_transaction_id,
                            'wallet_topup_id' => $topup->id,
                        ],
                    );
                });

                Notification::make()
                    ->title('Платеж подтвержден!')
                    ->body('Баланс успешно пополнен на '.number_format((float) $topup->amount, 2, '.', ' ').' BYN')
                    ->success()
                    ->send();
            }
        }
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
