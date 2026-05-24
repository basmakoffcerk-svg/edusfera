<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lesson;
use Filament\Widgets\Widget;

class CommissionLadderWidget extends Widget
{
    protected static string $view = 'filament.widgets.commission-ladder-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'tutor';
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        $monthlyPaidLessons = Lesson::query()
            ->where('tutor_id', $user->id)
            ->where('payment_status', Lesson::PAYMENT_PAID)
            ->whereMonth('start_time', now()->month)
            ->whereYear('start_time', now()->year)
            ->count();

        $tiers = [
            ['rate' => 15, 'lessons' => 0, 'label' => 'Старт'],
            ['rate' => 13, 'lessons' => 5, 'label' => 'Рост'],
            ['rate' => 11, 'lessons' => 12, 'label' => 'Пик'],
        ];

        $currentTier = $tiers[0];
        $nextTier = null;

        foreach ($tiers as $index => $tier) {
            if ($monthlyPaidLessons >= $tier['lessons']) {
                $currentTier = $tier;
                $nextTier = $tiers[$index + 1] ?? null;
            }
        }

        $lessonsToNextTier = $nextTier ? max($nextTier['lessons'] - $monthlyPaidLessons, 0) : 0;
        $progressMax = $nextTier['lessons'] ?? max($monthlyPaidLessons, 1);
        $progress = min(100, (int) round(($monthlyPaidLessons / max($progressMax, 1)) * 100));

        $monthlyRevenue = Lesson::query()
            ->where('tutor_id', $user->id)
            ->where('payment_status', Lesson::PAYMENT_PAID)
            ->whereMonth('start_time', now()->month)
            ->whereYear('start_time', now()->year)
            ->sum('net_amount');

        return [
            'currentRate' => $currentTier['rate'],
            'currentTier' => $currentTier,
            'nextTier' => $nextTier,
            'monthlyPaidLessons' => $monthlyPaidLessons,
            'lessonsToNextTier' => $lessonsToNextTier,
            'progress' => $progress,
            'monthlyRevenue' => number_format((float) $monthlyRevenue, 2, '.', ' '),
            'nextTierRevenueHint' => $nextTier
                ? 'Каждый оплаченный урок приближает вас к ставке '.$nextTier['rate'].'%.'
                : 'Вы уже на максимальной выгодной ступени комиссии.',
        ];
    }
}
