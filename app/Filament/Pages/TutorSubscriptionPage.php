<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Domain\Subscription\Services\SubscriptionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class TutorSubscriptionPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.pages.tutor-subscription-page';

    protected static ?string $navigationLabel = 'Моя подписка';

    protected static ?string $title = 'Тарифный план и подписка';

    protected static ?int $navigationSort = 15;

    public ?Subscription $subscription = null;
    public $invoices = [];
    public bool $isYearly = false;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user !== null && $user->isTutor();
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
            ->limit(10)
            ->get();
    }

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
            ->title('Тариф успешно изменён на ' . $plan->title())
            ->body('Счёт на оплату в ЕРИП обновлён.')
            ->success()
            ->send();

        $this->refreshData($service);
    }

    public function createNewInvoice(SubscriptionService $service): void
    {
        if (! $this->subscription) {
            return;
        }

        $service->createInvoice($this->subscription, $this->subscription->plan, $this->isYearly ? 12 : 1);

        Notification::make()
            ->title('Счёт сформирован')
            ->body('Оплатите счёт через ЕРИП по вашему номеру абонента.')
            ->success()
            ->send();

        $this->refreshData($service);
    }
}
