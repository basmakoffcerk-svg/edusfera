<?php

declare(strict_types=1);

namespace App\Filament\SiteAdmin\Widgets;

use App\Models\DiagnosticAttempt;
use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\ProgressSnapshot;
use App\Models\StudentGoal;
use App\Models\Transaction;
use App\Models\TutorProfile;
use App\Support\BynMoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverviewStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected function getStats(): array
    {
        $pendingProfiles = TutorProfile::query()
            ->where('verification_status', 'pending')
            ->count();

        $activeRisks = TutorProfile::query()
            ->where(function ($query): void {
                $query
                    ->where('contact_bypass_attempts', '>=', 3)
                    ->orWhere('search_penalized_until', '>', now());
            })
            ->count();

        $pendingLessons = Lesson::query()
            ->where('status', Lesson::STATUS_PENDING)
            ->count();

        $monthlyGmv = (float) Transaction::query()
            ->where('status', Transaction::STATUS_SUCCESS)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $activeGoals = StudentGoal::query()
            ->where('status', 'active')
            ->count();

        $diagnosticsDone = DiagnosticAttempt::query()->count();

        $homeworkCompletionRate = HomeworkAssignment::query()->count() > 0
            ? (int) round(
                (HomeworkAssignment::query()->where('status', 'completed')->count()
                    / max(HomeworkAssignment::query()->count(), 1)) * 100
            )
            : 0;

        $trackedProgress = ProgressSnapshot::query()
            ->whereNotNull('current_score')
            ->count();

        return [
            Stat::make('Анкеты на модерации', (string) $pendingProfiles)
                ->description('Проверьте новые профили и документы')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($pendingProfiles > 0 ? 'warning' : 'success'),
            Stat::make('Риски обхода', (string) $activeRisks)
                ->description('Профили с попытками увести сделку')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($activeRisks > 0 ? 'danger' : 'success'),
            Stat::make('Новые заявки', (string) $pendingLessons)
                ->description('Ожидают реакции или контроля')
                ->descriptionIcon('heroicon-m-inbox-stack')
                ->color($pendingLessons > 0 ? 'primary' : 'gray'),
            Stat::make('Оборот за месяц', BynMoneyFormatter::format($monthlyGmv))
                ->description('Успешные оплаты внутри платформы')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Активные траектории', (string) $activeGoals)
                ->description('Учебные цели со статусом active')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($activeGoals > 0 ? 'primary' : 'gray'),
            Stat::make('Диагностики', (string) $diagnosticsDone)
                ->description('Сохранённые baseline и повторные срезы')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($diagnosticsDone > 0 ? 'success' : 'gray'),
            Stat::make('Домашка выполнена', $homeworkCompletionRate.'%')
                ->description('Доля completed по homework_assignments')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($homeworkCompletionRate >= 60 ? 'success' : 'warning'),
            Stat::make('Срезы прогресса', (string) $trackedProgress)
                ->description('Snapshots с измеримым current score')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color($trackedProgress > 0 ? 'primary' : 'gray'),
        ];
    }
}
