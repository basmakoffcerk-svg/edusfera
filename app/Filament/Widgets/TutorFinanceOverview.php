<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lesson;
use App\Models\TutorBalance;
use App\Support\BynMoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TutorFinanceOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! in_array($user?->role, ['admin', 'tutor'], true)) {
            return [];
        }

        $balance = TutorBalance::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'available_amount' => '0.00',
                'pending_amount' => '0.00',
                'total_earned' => '0.00',
                'total_withdrawn' => '0.00',
            ],
        );

        $weeklyRevenue = Lesson::query()
            ->where('tutor_id', $user->id)
            ->where('payment_status', Lesson::PAYMENT_PAID)
            ->whereBetween('start_time', [now()->subWeeks(5)->startOfWeek()->utc(), now()->endOfWeek()->utc()])
            ->get()
            ->groupBy(fn (Lesson $lesson) => $lesson->start_time->copy()->timezone('Europe/Minsk')->startOfWeek()->format('d.m'))
            ->map(fn ($lessons) => (float) $lessons->sum('net_amount'))
            ->values()
            ->pad(6, 0)
            ->take(-6)
            ->map(fn (float $value) => (int) round($value))
            ->all();

        $monthlyForecast = Lesson::query()
            ->where('tutor_id', $user->id)
            ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])
            ->where('start_time', '>=', now()->utc())
            ->where('start_time', '<=', now()->endOfMonth()->utc())
            ->sum('net_amount');

        return [
            Stat::make('Доступно к выводу', BynMoneyFormatter::format((string) $balance->available_amount))
                ->description('Можно выводить на карту по графику выплат')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($weeklyRevenue)
                ->color('success')
                ->url('/admin/transactions'),
            Stat::make('Заморожено в безопасной сделке', BynMoneyFormatter::format((string) $balance->pending_amount))
                ->description('Эти деньги уже оплачены учениками и ждут проведения уроков')
                ->descriptionIcon('heroicon-m-clock')
                ->chart($weeklyRevenue)
                ->color('warning')
                ->url('/admin/lesson-requests'),
            Stat::make('Прогноз дохода до конца месяца', BynMoneyFormatter::format($monthlyForecast))
                ->description('Сумма по запланированным урокам в календаре')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($weeklyRevenue)
                ->color('gray')
                ->url('/admin/lessons'),
        ];
    }

    public static function canView(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'tutor'], true);
    }
}
