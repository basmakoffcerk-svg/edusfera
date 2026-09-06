<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Services\Payment\AlfaBankPaymentGateway;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TutorSubscriptionPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.pages.tutor-subscription-page';

    protected static ?string $navigationLabel = 'Моя подписка';

    protected static ?string $title = 'Тарифный план и подписка';

    protected static ?int $navigationSort = 15;

    public ?Subscription $subscription = null;

    /** @var \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|array */
    public $invoices = [];

    public bool $isYearly = false;

    public bool $showCancelModal = false;

    public bool $showCardModal = false;

    public ?string $selectedPlanCode = null;

    public ?string $alfaRedirectUrl = null;

    public ?string $alfaOrderId = null;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user !== null && ($user->isTutor() || $user->isAdmin());
    }

    public function mount(SubscriptionService $service): void
    {
        $this->refreshData($service);
    }

    public function refreshData(SubscriptionService $service): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->subscription = Subscription::where('tutor_id', $user->id)->first();

        if (! $this->subscription) {
            $this->subscription = $service->startTrial($user, SubscriptionPlan::PRO);
        }

        $this->invoices = SubscriptionInvoice::where('subscription_id', $this->subscription->id)
            ->latest()
            ->limit(15)
            ->get();
    }

    public function toggleYearly(): void
    {
        $this->isYearly = ! $this->isYearly;
    }

    /**
     * Change plan directly.
     */
    public function changePlan(string $planName, SubscriptionService $service): void
    {
        $plan = SubscriptionPlan::tryFrom($planName);
        if (! $plan || ! $this->subscription) {
            return;
        }

        $this->subscription->update([
            'plan' => $plan,
        ]);

        $service->createInvoice($this->subscription, $plan, $this->isYearly ? 12 : 1);

        Notification::make()
            ->title('Тариф изменён на «' . $plan->title() . '»')
            ->body('Сформирован счёт на оплату.')
            ->success()
            ->send();

        $this->refreshData($service);
    }

    /**
     * Start card binding & subscription payment via Alfa-Bank.
     */
    public function subscribeWithCard(string $planName, AlfaBankPaymentGateway $gateway, SubscriptionService $service): void
    {
        $plan = SubscriptionPlan::tryFrom($planName);
        if (! $plan || ! $this->subscription) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->selectedPlanCode = $planName;
        $amountByn = $this->isYearly ? $plan->yearlyPriceByn() : $plan->monthlyPriceByn();

        try {
            $result = $gateway->createPayment([
                'amount' => $amountByn,
                'subscription' => true,
                'user_id' => $user->id,
                'plan' => $plan->value,
                'description' => sprintf(
                    'Подписка Edusfera: «%s» (%s)',
                    $plan->title(),
                    $this->isYearly ? 'на 1 год' : 'на 1 месяц'
                ),
            ]);

            $this->alfaOrderId = $result['mdOrder'] ?? $result['gateway_transaction_id'] ?? null;
            $this->alfaRedirectUrl = $result['redirect_url'] ?? null;

            // If a real external Alfa-Bank payment form URL is present, redirect to it
            if (! empty($this->alfaRedirectUrl) && ! str_contains($this->alfaRedirectUrl, 'tutor-subscription-page')) {
                $this->redirect($this->alfaRedirectUrl);
                return;
            }

            // In sandbox/test mode: open card confirmation modal
            $this->showCardModal = true;
        } catch (\Throwable $e) {
            Log::error('AlfaBank subscription card binding failed: ' . $e->getMessage());

            Notification::make()
                ->title('Ошибка связи с банком')
                ->body('Не удалось инициировать привязку карты. Попробуйте снова или воспользуйтесь ЕРИП.')
                ->danger()
                ->send();
        }
    }

    /**
     * Confirm card payment in modal / sandbox.
     */
    public function confirmCardPayment(SubscriptionService $service): void
    {
        if (! $this->subscription || ! $this->selectedPlanCode) {
            $this->showCardModal = false;
            return;
        }

        $plan = SubscriptionPlan::tryFrom($this->selectedPlanCode) ?? SubscriptionPlan::PRO;
        $periodMonths = $this->isYearly ? 12 : 1;

        $invoice = $service->createInvoice($this->subscription, $plan, $periodMonths);
        $service->recordPayment($invoice, 'card', [
            'card_mask' => '•••• 4242',
            'bank' => 'Альфа-Банк (Alfa-Bank)',
            'alfa_order_id' => $this->alfaOrderId,
        ]);

        $this->showCardModal = false;

        Notification::make()
            ->title('Карта успешно привязана!')
            ->body('Тариф «' . $plan->title() . '» успешно активирован.')
            ->success()
            ->send();

        $this->refreshData($service);
    }

    public function closeCardModal(): void
    {
        $this->showCardModal = false;
    }

    public function openCancelModal(): void
    {
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    /**
     * Cancel tutor subscription with confirmation.
     */
    public function cancelSubscription(SubscriptionService $service): void
    {
        if (! $this->subscription) {
            return;
        }

        $this->subscription->update([
            'status' => SubscriptionStatus::CANCELED,
            'canceled_at' => now(),
        ]);

        $this->showCancelModal = false;

        Notification::make()
            ->title('Подписка отменена')
            ->body('Все функции сохраняются до ' . ($this->subscription->current_period_ends_at?->format('d.m.Y') ?? 'конца оплаченного периода') . '.')
            ->warning()
            ->send();

        $this->refreshData($service);
    }

    /**
     * Resume a canceled subscription.
     */
    public function resumeSubscription(SubscriptionService $service): void
    {
        if (! $this->subscription) {
            return;
        }

        $this->subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'canceled_at' => null,
        ]);

        Notification::make()
            ->title('Подписка возобновлена')
            ->body('Автопродление тарифа «' . $this->subscription->plan->title() . '» активно.')
            ->success()
            ->send();

        $this->refreshData($service);
    }

    /**
     * Generate an invoice for payment via ERIP.
     */
    public function createNewInvoice(SubscriptionService $service): void
    {
        if (! $this->subscription) {
            return;
        }

        $service->createInvoice($this->subscription, $this->subscription->plan, $this->isYearly ? 12 : 1);

        Notification::make()
            ->title('Счёт в ЕРИП сформирован')
            ->body('Номер абонента: EDU' . sprintf('%05d', Auth::id()))
            ->success()
            ->send();

        $this->refreshData($service);
    }
}
