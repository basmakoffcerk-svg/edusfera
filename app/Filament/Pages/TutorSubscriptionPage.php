<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use App\Services\Payment\AlfaBankPaymentGateway;
use App\Services\PromoCodeService;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;

class TutorSubscriptionPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.pages.tutor-subscription-page';

    protected static ?string $navigationLabel = 'Моя подписка';

    protected static ?string $title = 'Тарифный план и подписка';

    protected static ?int $navigationSort = 15;

    public ?Subscription $subscription = null;

    /** @var \Illuminate\Database\Eloquent\Collection|Collection|array */
    public $invoices = [];

    public bool $isYearly = false;

    public bool $showCancelModal = false;

    public bool $showCardModal = false;

    public bool $showPaymentModal = false;

    public ?string $checkoutPlanCode = 'pro';

    public ?string $selectedPlanCode = null;

    public ?string $alfaRedirectUrl = null;

    public ?string $alfaOrderId = null;

    #[Url]
    public ?string $orderId = null;

    #[Url]
    public ?string $payment = null;

    public ?string $paymentErrorMessage = null;

    public ?string $paymentSuccessMessage = null;

    public bool $isOnboarding = false;

    public ?string $promoCode = null;

    public ?string $appliedPromoCode = null;

    public float $promoDiscountAmount = 0.0;

    public ?string $promoDiscountType = null;

    public ?string $promoSubscriptionPeriod = null;

    public bool $isFreeActivation = false;

    public ?string $promoErrorMessage = null;

    public ?string $promoSuccessMessage = null;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user !== null && ($user->isTutor() || $user->isAdmin());
    }

    public function mount(SubscriptionService $service, ?string $orderId = null): void
    {
        if (! empty($orderId)) {
            $this->orderId = $orderId;
        }

        $this->refreshData($service);

        if (request()->query('onboarding') || ($this->subscription && ! $this->subscription->is_onboarded)) {
            $this->isOnboarding = true;
        }
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

        $checkOrderId = $this->orderId ?: request()->query('orderId');
        $paymentStatus = $this->payment ?: request()->query('payment');

        if (! empty($checkOrderId) || $paymentStatus === 'failed') {
            $gateway = app(AlfaBankPaymentGateway::class);
            $statusDetails = ! empty($checkOrderId)
                ? $gateway->getPaymentStatusDetails($checkOrderId)
                : [
                    'success' => false,
                    'message' => 'Операция оплаты была отменена или прервана пользователем.',
                ];

            if ($statusDetails['success']) {
                $invoice = SubscriptionInvoice::where('subscription_id', $this->subscription->id)
                    ->whereIn('status', [InvoiceStatus::PENDING, 'pending'])
                    ->latest()
                    ->first();

                if (! $invoice) {
                    $invoice = $service->createInvoice($this->subscription, $this->subscription->plan, 1);
                }

                if ($invoice) {
                    $service->recordPayment($invoice, 'card', [
                        'alfa_order_id' => $checkOrderId,
                        'bank' => 'Альфа-Банк (Alfa-Bank)',
                        'verified_at' => now()->toIso8601String(),
                    ]);

                    if ($invoice->promo_code_id) {
                        $promo = PromoCode::find($invoice->promo_code_id);
                        if ($promo) {
                            $alreadyUsed = PromoCodeUsage::where('order_type', 'subscription')
                                ->where('user_id', $user->id)
                                ->where('created_at', '>=', now()->subMinutes(60))
                                ->exists();
                            if (! $alreadyUsed) {
                                $discByn = $invoice->discount_kopecks / 100;
                                $origByn = ($invoice->amount_kopecks + $invoice->discount_kopecks) / 100;
                                app(PromoCodeService::class)->applyToSubscription(
                                    $promo,
                                    $user,
                                    $invoice,
                                    $discByn,
                                    $origByn
                                );
                            }
                        }
                    }

                    $this->subscription->refresh();
                    $this->subscription->update(['is_onboarded' => true]);
                }

                $this->paymentSuccessMessage = 'Тариф «'.$this->subscription->plan->title().'» успешно оплачен и активирован!';
                $this->paymentErrorMessage = null;

                Notification::make()
                    ->title('Подписка успешно оплачена!')
                    ->body($this->paymentSuccessMessage)
                    ->success()
                    ->send();

                $this->orderId = null;
                $this->payment = null;
                $this->subscription->refresh();
                $this->invoices = SubscriptionInvoice::where('subscription_id', $this->subscription->id)
                    ->latest()
                    ->limit(15)
                    ->get();
            } else {
                $this->paymentErrorMessage = $statusDetails['message'];
                $this->paymentSuccessMessage = null;

                $pendingInvoice = SubscriptionInvoice::where('subscription_id', $this->subscription->id)
                    ->whereIn('status', [InvoiceStatus::PENDING, 'pending'])
                    ->latest()
                    ->first();

                if ($pendingInvoice) {
                    $pendingInvoice->update([
                        'status' => InvoiceStatus::CANCELED,
                        'notes' => 'Отклонено Альфа-Банком: '.$statusDetails['message'],
                    ]);
                }

                Notification::make()
                    ->title('Платёж не прошёл')
                    ->body($this->paymentErrorMessage.' Пожалуйста, повторите попытку.')
                    ->danger()
                    ->persistent()
                    ->send();

                $this->orderId = null;
                $this->payment = null;
            }
        }
    }

    public function toggleYearly(): void
    {
        $this->isYearly = ! $this->isYearly;
    }

    public function setYearly(bool $yearly): void
    {
        $this->isYearly = $yearly;
    }

    /**
     * Change plan request: updates plan during trial/free or generates an invoice.
     */
    public function changePlan(string $planName, SubscriptionService $service): void
    {
        $plan = SubscriptionPlan::tryFrom($planName);
        if (! $plan || ! $this->subscription) {
            return;
        }

        // During trial or if already paid, allow selecting/switching the target plan tier
        $this->subscription->update([
            'plan' => $plan,
            'is_onboarded' => true,
        ]);

        $service->createInvoice($this->subscription, $plan, $this->isYearly ? 12 : 1);

        Notification::make()
            ->title('Тариф изменён на «'.$plan->title().'»')
            ->body('Сформирован счёт на оплату.')
            ->success()
            ->send();

        $this->refreshData($service);
    }

    public function getCheckoutPlanProperty(): SubscriptionPlan
    {
        $code = $this->checkoutPlanCode ?: $this->selectedPlanCode ?: ($this->subscription?->plan?->value ?? 'pro');

        return SubscriptionPlan::tryFrom($code) ?? SubscriptionPlan::PRO;
    }

    public function getCheckoutBaseAmountProperty(): float
    {
        $plan = $this->checkoutPlan;

        return (float) ($this->isYearly ? $plan->yearlyPriceByn() : $plan->monthlyPriceByn());
    }

    public function getCheckoutFinalAmountProperty(): float
    {
        if ($this->isFreeActivation) {
            return 0.0;
        }

        return max(0.0, round($this->checkoutBaseAmount - $this->promoDiscountAmount, 2));
    }

    public function getHasFirstPaymentTrialBonusProperty(): bool
    {
        if (! $this->subscription) {
            return true;
        }

        return SubscriptionInvoice::where('tutor_id', $this->subscription->tutor_id)
            ->where('status', InvoiceStatus::PAID)
            ->doesntExist();
    }

    public function openPaymentModal(string $planCode): void
    {
        $this->checkoutPlanCode = $planCode;
        $this->selectedPlanCode = $planCode;
        $this->paymentErrorMessage = null;
        $this->showPaymentModal = true;

        if (! empty($this->appliedPromoCode)) {
            $this->applyPromoCode();
        }
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->promoErrorMessage = null;
    }

    public function applyPromoCode(): void
    {
        $this->promoErrorMessage = null;
        $this->promoSuccessMessage = null;

        try {
            $code = trim((string) $this->promoCode);
            if (empty($code)) {
                $this->promoErrorMessage = 'Введите промокод.';

                return;
            }

            $user = Auth::user();
            if (! $user) {
                return;
            }

            $plan = $this->checkoutPlan;
            $baseAmount = $this->checkoutBaseAmount;

            $result = app(PromoCodeService::class)->validate(
                $code,
                $baseAmount,
                $user,
                [
                    'order_type' => 'subscription',
                    'plan' => $plan->value,
                ]
            );

            if (! $result['valid']) {
                $this->promoErrorMessage = $result['error'] ?? 'Недействительный промокод.';
                $this->appliedPromoCode = null;
                $this->promoDiscountAmount = 0.0;
                $this->promoDiscountType = null;
                $this->promoSubscriptionPeriod = null;
                $this->isFreeActivation = false;

                return;
            }

            /** @var PromoCode $promo */
            $promo = $result['promo_code'];
            $this->appliedPromoCode = $promo->code;
            $this->promoDiscountType = $promo->discount_type;
            $this->promoSubscriptionPeriod = $promo->subscription_period;
            $this->isFreeActivation = $promo->isFreeSubscription();

            if ($this->isFreeActivation) {
                $this->promoDiscountAmount = $baseAmount;
                if ($promo->discount_type === 'lifetime') {
                    $this->promoSuccessMessage = sprintf('Промокод «%s» применён! Вам предоставлен бессрочный бесплатный доступ.', $this->appliedPromoCode);
                } else {
                    $periodLabels = [
                        '1_month' => '1 месяц',
                        '3_months' => '3 месяца',
                        '6_months' => '6 месяцев',
                        '12_months' => '1 год',
                    ];
                    $lbl = $periodLabels[$promo->subscription_period] ?? 'бесплатный доступ';
                    $this->promoSuccessMessage = sprintf('Промокод «%s» применён! Вам предоставлено %s бесплатной подписки.', $this->appliedPromoCode, $lbl);
                }
            } else {
                $this->promoDiscountAmount = (float) $result['discount_amount'];
                $this->promoSuccessMessage = sprintf('Промокод «%s» применён! Скидка: %.2f BYN', $this->appliedPromoCode, $this->promoDiscountAmount);
            }

            Notification::make()
                ->title('Промокод применён')
                ->body($this->promoSuccessMessage)
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Promo code apply failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->promoErrorMessage = 'Ошибка при проверке промокода: '.$e->getMessage();
        }
    }

    public function removePromoCode(): void
    {
        $this->promoCode = null;
        $this->appliedPromoCode = null;
        $this->promoDiscountAmount = 0.0;
        $this->promoDiscountType = null;
        $this->promoSubscriptionPeriod = null;
        $this->isFreeActivation = false;
        $this->promoErrorMessage = null;
        $this->promoSuccessMessage = null;
    }

    /**
     * Активировать бесплатную подписку репетитору по промокоду (1/3/6/12 месяцев или пожизненно).
     */
    public function activateFreePromo(?PromoCodeService $promoService = null, ?SubscriptionService $subscriptionService = null): void
    {
        $promoService = $promoService ?? app(PromoCodeService::class);
        $subscriptionService = $subscriptionService ?? app(SubscriptionService::class);

        $user = Auth::user();
        if (! $user) {
            return;
        }

        if (empty($this->appliedPromoCode)) {
            $this->promoErrorMessage = 'Промокод не указан.';

            return;
        }

        try {
            $promo = $promoService->findByCode($this->appliedPromoCode);
            if (! $promo || ! $promo->isFreeSubscription()) {
                $this->promoErrorMessage = 'Этот промокод не предоставляет бесплатную подписку.';

                return;
            }

            $targetPlanCode = $this->checkoutPlanCode ?: $this->selectedPlanCode ?: ($this->subscription?->plan?->value ?? 'pro');

            $result = $promoService->activateFreeSubscriptionViaPromo($promo, $user, $targetPlanCode);

            $this->showPaymentModal = false;
            $this->paymentErrorMessage = null;

            $label = $result['is_lifetime']
                ? 'Бессрочный доступ к тарифу «'.$result['plan']->title().'» успешно активирован!'
                : sprintf('Тариф «%s» на %d мес. успешно активирован бесплатно!', $result['plan']->title(), $result['months']);

            $this->paymentSuccessMessage = $label;

            Notification::make()
                ->title('Подписка успешно активирована!')
                ->body($label)
                ->success()
                ->persistent()
                ->send();

            $this->removePromoCode();
            $this->refreshData($subscriptionService);
        } catch (\Throwable $e) {
            Log::error('Free promo subscription activation failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->promoErrorMessage = 'Не удалось активировать подписку по промокоду: '.$e->getMessage();
            Notification::make()
                ->title('Ошибка активации')
                ->body($this->promoErrorMessage)
                ->danger()
                ->send();
        }
    }

    /**
     * Переход из модального окна оплаты в Альфа-Банк или бесплатная активация.
     */
    public function proceedToAcquiring(AlfaBankPaymentGateway $gateway, SubscriptionService $service): void
    {
        if ($this->isFreeActivation) {
            $this->activateFreePromo(app(PromoCodeService::class), $service);

            return;
        }

        $targetPlanCode = $this->checkoutPlanCode ?: $this->selectedPlanCode ?: ($this->subscription?->plan?->value ?? 'pro');
        $this->showPaymentModal = false;
        $this->subscribeWithCard($targetPlanCode, $gateway, $service);
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

        $this->paymentErrorMessage = null;
        $this->paymentSuccessMessage = null;
        $this->selectedPlanCode = $planName;
        $baseAmountByn = $this->isYearly ? $plan->yearlyPriceByn() : $plan->monthlyPriceByn();
        $amountByn = $baseAmountByn;
        $periodMonths = $this->isYearly ? 12 : 1;

        $promoModel = null;
        $discountAmount = 0.0;
        if (! empty($this->appliedPromoCode)) {
            $promoResult = app(PromoCodeService::class)->validate(
                $this->appliedPromoCode,
                $baseAmountByn,
                $user,
                ['order_type' => 'subscription']
            );

            if ($promoResult['valid']) {
                $promoModel = $promoResult['promo_code'];
                $discountAmount = $promoResult['discount_amount'];
                $amountByn = max(0.01, round($baseAmountByn - $discountAmount, 2));
            }
        }

        // Создаем счет на оплату выбранного тарифа
        // План и статус подписки активируются строго после успешной оплаты через шлюз или вебхук
        $invoice = $service->createInvoice($this->subscription, $plan, $periodMonths);
        if ($promoModel) {
            $invoice->update([
                'promo_code_id' => $promoModel->id,
                'discount_kopecks' => (int) round($discountAmount * 100),
                'amount_kopecks' => (int) round($amountByn * 100),
            ]);
        }

        try {
            $result = $gateway->createPayment([
                'amount' => $amountByn,
                'subscription' => true,
                'user_id' => $user->id,
                'plan' => $plan->value,
                'invoice_id' => $invoice->id,
                'description' => sprintf(
                    'Подписка Edusfera: «%s» (%s)%s',
                    $plan->title(),
                    $this->isYearly ? 'на 1 год' : 'на 1 месяц',
                    $promoModel ? " [промокод {$promoModel->code}]" : ''
                ),
            ]);

            if (! ($result['success'] ?? false)) {
                $this->paymentErrorMessage = $result['message'] ?? 'Не удалось связаться с платёжным шлюзом банка. Попробуйте ещё раз.';
                Notification::make()
                    ->title('Ошибка связи с банком')
                    ->body($this->paymentErrorMessage)
                    ->danger()
                    ->persistent()
                    ->send();

                return;
            }

            $this->alfaOrderId = $result['mdOrder'] ?? $result['gateway_transaction_id'] ?? null;
            $this->alfaRedirectUrl = $result['redirect_url'] ?? null;

            // Перенаправляем на защищенную страницу оплаты Альфа-Банка (formUrl или hosted-test)
            $redirectPath = parse_url((string) $this->alfaRedirectUrl, PHP_URL_PATH) ?? '';
            $currentPath = parse_url(route('filament.admin.pages.tutor-subscription-page'), PHP_URL_PATH) ?? '';

            if (! empty($this->alfaRedirectUrl) && $redirectPath !== $currentPath) {
                $this->redirect($this->alfaRedirectUrl);

                return;
            }

            // Fallback для тестов/песочницы
            $this->showCardModal = true;
        } catch (\Throwable $e) {
            Log::error('AlfaBank subscription card binding failed: '.$e->getMessage());

            $this->paymentErrorMessage = 'Не удалось инициировать платёж. Проверьте соединение или повторите попытку позже.';

            Notification::make()
                ->title('Ошибка связи с банком')
                ->body($this->paymentErrorMessage)
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Confirm card payment in modal / sandbox.
     */
    public function confirmCardPayment(SubscriptionService $service, AlfaBankPaymentGateway $gateway): void
    {
        if (! $this->subscription || ! $this->selectedPlanCode) {
            $this->showCardModal = false;

            return;
        }

        if (! empty($this->alfaOrderId)) {
            $status = $gateway->getPaymentStatusDetails($this->alfaOrderId);
            if (! $status['success']) {
                $this->paymentErrorMessage = $status['message'];
                Notification::make()
                    ->title('Оплата не подтверждена банком')
                    ->body($this->paymentErrorMessage)
                    ->danger()
                    ->persistent()
                    ->send();

                return;
            }
        }

        $plan = SubscriptionPlan::tryFrom($this->selectedPlanCode) ?? SubscriptionPlan::PRO;
        $periodMonths = $this->isYearly ? 12 : 1;

        $invoice = $service->createInvoice($this->subscription, $plan, $periodMonths);
        $service->recordPayment($invoice, 'card', [
            'card_mask' => '•••• 4242',
            'bank' => 'Альфа-Банк (Alfa-Bank)',
            'alfa_order_id' => $this->alfaOrderId,
        ]);

        $this->subscription->update(['is_onboarded' => true]);
        $this->showCardModal = false;

        Notification::make()
            ->title('Карта успешно привязана!')
            ->body('Тариф «'.$plan->title().'» успешно активирован.')
            ->success()
            ->send();

        $this->refreshData($service);
    }

    /**
     * Confirm trial plan during onboarding and proceed to the admin dashboard.
     */
    public function activateTrialAndStart(?string $planName = null, ?SubscriptionService $service = null): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $service = $service ?? app(SubscriptionService::class);
        $plan = ! empty($planName) ? (SubscriptionPlan::tryFrom($planName) ?? SubscriptionPlan::PRO) : SubscriptionPlan::PRO;
        $trialDays = $plan->trialDays();
        $now = CarbonImmutable::now();

        if (! $this->subscription) {
            $this->subscription = $service->startTrial($user, $plan);
        } else {
            $this->subscription->update([
                'plan' => $plan,
                'status' => SubscriptionStatus::TRIAL,
                'trial_ends_at' => $now->addDays($trialDays),
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $now->addDays($trialDays),
            ]);
        }

        $this->subscription->is_onboarded = true;
        $this->subscription->save();

        session()->forget('subscription_required');

        Notification::make()
            ->title('Пробный период активирован!')
            ->body('Тариф «'.$this->subscription->plan->title().'» ('.$trialDays.' '.trans_choice('день|дня|дней', $trialDays).' бесплатно) активирован. Добро пожаловать!')
            ->success()
            ->send();

        $this->redirect('/admin');
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
            ->body('Все функции сохраняются до '.($this->subscription->current_period_ends_at?->format('d.m.Y') ?? 'конца оплаченного периода').'.')
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
            ->body('Автопродление тарифа «'.$this->subscription->plan->title().'» активно.')
            ->success()
            ->send();

        $this->refreshData($service);
    }

    /**
     * Generate an invoice for payment.
     */
    public function createNewInvoice(SubscriptionService $service): void
    {
        if (! $this->subscription) {
            return;
        }

        $service->createInvoice($this->subscription, $this->subscription->plan, $this->isYearly ? 12 : 1);

        Notification::make()
            ->title('Счёт на оплату сформирован')
            ->body('Счёт добавлен в историю начислений.')
            ->success()
            ->send();

        $this->refreshData($service);
    }
}
