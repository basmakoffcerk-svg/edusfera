<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Models\Lesson;
use Filament\Widgets\Widget;

class CommissionLadderWidget extends Widget
{
    protected static string $view = 'filament.widgets.commission-ladder-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->isTutor() ?? false;
    }

    protected function getViewData(): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $subscription = Subscription::where('tutor_id', $user->id)->first();

        if (! $subscription) {
            /** @var SubscriptionService $service */
            $service = app(SubscriptionService::class);
            $subscription = $service->startTrial($user, SubscriptionPlan::PRO);
        }

        $plan = $subscription->plan;
        $status = $subscription->status;
        $isFounder = (bool) $subscription->is_founder;
        $isTrial = $status === SubscriptionStatus::TRIAL;
        $daysRemaining = $subscription->daysRemaining();

        $responsesUsed = (int) $subscription->responses_used_this_month;
        $maxResponses = $plan->maxResponsesPerMonth();

        $progressPercent = 0;
        if ($maxResponses === null) {
            $progressPercent = 100;
        } elseif ($maxResponses > 0) {
            $progressPercent = min(100, (int) round(($responsesUsed / $maxResponses) * 100));
        }

        $monthlyRevenue = (float) Lesson::query()
            ->where('tutor_id', $user->id)
            ->where('payment_status', Lesson::PAYMENT_PAID)
            ->whereMonth('start_time', now()->month)
            ->whereYear('start_time', now()->year)
            ->sum('amount');

        $plansInfo = [
            [
                'plan' => SubscriptionPlan::BASIC,
                'name' => 'Basic',
                'price' => '20 BYN/мес',
                'responses' => '0 откликов',
                'isActive' => $plan === SubscriptionPlan::BASIC,
            ],
            [
                'plan' => SubscriptionPlan::PRO,
                'name' => 'Pro',
                'price' => '40 BYN/мес',
                'responses' => '10 откликов/мес',
                'isActive' => $plan === SubscriptionPlan::PRO,
            ],
            [
                'plan' => SubscriptionPlan::PREMIUM,
                'name' => 'Premium',
                'price' => '60 BYN/мес',
                'responses' => 'Безлимит откликов',
                'isActive' => $plan === SubscriptionPlan::PREMIUM,
            ],
        ];

        $statusBadge = match ($status) {
            SubscriptionStatus::TRIAL => [
                'label' => 'Пробный период (0 BYN)',
                'color' => 'trial',
                'hint' => $daysRemaining > 0 ? "Осталось {$daysRemaining} дн." : 'Истекает сегодня',
            ],
            SubscriptionStatus::ACTIVE => [
                'label' => 'Подписка активна',
                'color' => 'active',
                'hint' => $daysRemaining > 0 ? "Осталось {$daysRemaining} дн." : 'Активна',
            ],
            SubscriptionStatus::PAST_DUE => [
                'label' => 'Ожидает оплаты',
                'color' => 'warning',
                'hint' => 'Льготный период',
            ],
            SubscriptionStatus::CANCELED, SubscriptionStatus::EXPIRED => [
                'label' => 'Не активна',
                'color' => 'danger',
                'hint' => 'Требуется продление',
            ],
            default => [
                'label' => 'Не активна',
                'color' => 'gray',
                'hint' => '',
            ],
        };

        return [
            'userName' => $user->name,
            'subscription' => $subscription,
            'plan' => $plan,
            'planTitle' => $plan->title(),
            'monthlyPrice' => $plan->monthlyPriceByn(),
            'status' => $status,
            'statusBadge' => $statusBadge,
            'isFounder' => $isFounder,
            'isTrial' => $isTrial,
            'daysRemaining' => $daysRemaining,
            'responsesUsed' => $responsesUsed,
            'maxResponses' => $maxResponses,
            'progressPercent' => $progressPercent,
            'monthlyRevenue' => number_format($monthlyRevenue, 2, '.', ' '),
            'plansInfo' => $plansInfo,
        ];
    }
}
