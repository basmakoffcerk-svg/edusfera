<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonResource\Widgets;

use App\Filament\Resources\LessonResource;
use App\Models\Lesson;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LessonOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $query = LessonResource::getEloquentQuery();
        $now = now('UTC');
        $user = auth()->user();

        $activeQuery = (clone $query)
            ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])
            ->where('end_time', '>=', $now);

        $nextLesson = (clone $activeQuery)
            ->with(['tutor', 'student'])
            ->orderBy('start_time')
            ->first();

        $upcomingCount = (clone $activeQuery)->count();

        $completedThisMonth = (clone $query)
            ->where('status', Lesson::STATUS_COMPLETED)
            ->whereBetween('start_time', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();

        $totalCompleted = (clone $query)
            ->where('status', Lesson::STATUS_COMPLETED)
            ->count();

        $monthlyNpdIncome = (clone $query)
            ->where('payment_status', Lesson::PAYMENT_PAID)
            ->whereMonth('start_time', $now->month)
            ->whereYear('start_time', $now->year)
            ->sum('price');

        $isTutor = $user?->isTutor() ?? false;

        return [
            Stat::make('Ближайший урок', $nextLesson ? $nextLesson->start_time->setTimezone(config('booking.display_timezone'))->format('d.m, H:i') : 'Не запланирован')
                ->description($this->nextLessonDescription($nextLesson))
                ->descriptionIcon($nextLesson ? 'heroicon-m-clock' : 'heroicon-m-calendar')
                ->color($nextLesson ? 'primary' : 'gray'),
            Stat::make('Предстоящие уроки', (string) $upcomingCount)
                ->description($upcomingCount > 0 ? 'Запланировано в расписании' : 'Нет активных уроков')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($upcomingCount > 0 ? 'primary' : 'gray'),
            Stat::make('Завершено в месяце', (string) $completedThisMonth)
                ->description('Уроки со статусом «Завершён»')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            $isTutor
                ? Stat::make('Прямой доход (для НПД)', $this->money((float) $monthlyNpdIncome))
                    ->description('Оплаты от учеников за месяц')
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color('primary')
                : Stat::make('Пройдено уроков', (string) $totalCompleted)
                    ->description('Всего проведенных занятий')
                    ->descriptionIcon('heroicon-m-academic-cap')
                    ->color('primary'),
        ];
    }

    private function nextLessonDescription(?Lesson $lesson): string
    {
        if (! $lesson) {
            return auth()->user()?->isTutor()
                ? 'Откройте расписание, чтобы принимать новые записи'
                : 'Выберите преподавателя и забронируйте время';
        }

        $participant = auth()->user()?->isTutor()
            ? ($lesson->student?->name ?? 'Ученик')
            : ($lesson->tutor?->name ?? 'Репетитор');

        return $participant.' · '.LessonResource::statusLabel($lesson->status);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', ' ').' BYN';
    }
}
