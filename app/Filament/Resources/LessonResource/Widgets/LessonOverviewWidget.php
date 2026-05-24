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

        $activeQuery = (clone $query)
            ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])
            ->where('end_time', '>=', $now);

        $nextLesson = (clone $activeQuery)
            ->with(['tutor', 'student'])
            ->orderBy('start_time')
            ->first();

        $unpaidCount = (clone $query)
            ->where('payment_status', Lesson::PAYMENT_UNPAID)
            ->where('status', '!=', Lesson::STATUS_CANCELLED)
            ->count();

        $pendingConfirmations = (clone $query)
            ->where('status', Lesson::STATUS_PENDING)
            ->where('payment_status', Lesson::PAYMENT_PAID)
            ->count();

        $completedThisMonth = (clone $query)
            ->where('status', Lesson::STATUS_COMPLETED)
            ->whereBetween('start_time', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();

        $paidPlannedTotal = (clone $query)
            ->where('payment_status', Lesson::PAYMENT_PAID)
            ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])
            ->sum(auth()->user()?->role === 'tutor' ? 'net_amount' : 'price');

        return [
            Stat::make('Ближайший урок', $nextLesson ? $nextLesson->start_time->setTimezone(config('booking.display_timezone'))->format('d.m, H:i') : 'Не запланирован')
                ->description($this->nextLessonDescription($nextLesson))
                ->descriptionIcon($nextLesson ? 'heroicon-m-clock' : 'heroicon-m-calendar')
                ->color($nextLesson ? 'primary' : 'gray'),
            Stat::make('Требуют действия', (string) ($unpaidCount + $pendingConfirmations))
                ->description($this->actionDescription($unpaidCount, $pendingConfirmations))
                ->descriptionIcon(($unpaidCount + $pendingConfirmations) > 0 ? 'heroicon-m-bolt' : 'heroicon-m-check-circle')
                ->color(($unpaidCount + $pendingConfirmations) > 0 ? 'warning' : 'success'),
            Stat::make('Завершено в месяце', (string) $completedThisMonth)
                ->description('Уроки со статусом «Завершён»')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
            Stat::make(auth()->user()?->role === 'tutor' ? 'К выплате в плане' : 'Оплачено в плане', $this->money((float) $paidPlannedTotal))
                ->description('Оплаченные будущие уроки')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }

    private function nextLessonDescription(?Lesson $lesson): string
    {
        if (! $lesson) {
            return auth()->user()?->role === 'tutor'
                ? 'Откройте расписание, чтобы принимать новые записи'
                : 'Выберите преподавателя и забронируйте время';
        }

        $participant = auth()->user()?->role === 'tutor'
            ? ($lesson->student?->name ?? 'Ученик')
            : ($lesson->tutor?->name ?? 'Репетитор');

        return $participant . ' · ' . LessonResource::statusLabel($lesson->status) . ' · ' . LessonResource::paymentLabel($lesson->payment_status);
    }

    private function actionDescription(int $unpaidCount, int $pendingConfirmations): string
    {
        if ($unpaidCount === 0 && $pendingConfirmations === 0) {
            return 'На сейчас всё в порядке';
        }

        $parts = [];

        if ($unpaidCount > 0) {
            $parts[] = $unpaidCount . ' к оплате';
        }

        if ($pendingConfirmations > 0) {
            $parts[] = $pendingConfirmations . ' к подтверждению';
        }

        return implode(' · ', $parts);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', ' ') . ' BYN';
    }
}
