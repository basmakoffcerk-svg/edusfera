<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lesson;
use Filament\Widgets\Widget;

/**
 * Верхний блок кабинета репетитора: приветствие, ближайший урок с живым
 * обратным отсчётчиком и единственная главная кнопка экрана (вход в класс).
 */
class TutorHeroWidget extends Widget
{
    protected static string $view = 'filament.widgets.tutor-hero-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    private const JOIN_LEAD_MINUTES = 15;

    public static function canView(): bool
    {
        return auth()->user()?->isTutor() ?? false;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $timezone = config('booking.display_timezone', 'Europe/Minsk');

        $hour = (int) now($timezone)->format('G');
        $greeting = match (true) {
            $hour < 6 => 'Доброй ночи',
            $hour < 12 => 'Доброе утро',
            $hour < 18 => 'Добрый день',
            default => 'Добрый вечер',
        };

        $now = now()->utc();

        // Ближайший урок: подтверждённый и ещё не закончившийся
        // (включая идущий прямо сейчас).
        $upcomingLesson = Lesson::query()
            ->with(['student', 'parent'])
            ->where('tutor_id', $user->id)
            ->where('status', Lesson::STATUS_CONFIRMED)
            ->where('end_time', '>', $now)
            ->orderBy('start_time')
            ->first();

        $meetingJoinAvailable = $upcomingLesson !== null
            && $now->greaterThanOrEqualTo($upcomingLesson->start_time->copy()->subMinutes(self::JOIN_LEAD_MINUTES));

        $classroomUrl = null;
        if ($upcomingLesson) {
            $classroomUrl = ! empty($upcomingLesson->meeting_link) && str_starts_with($upcomingLesson->meeting_link, 'http')
                ? $upcomingLesson->meeting_link
                : route('classroom.show', $upcomingLesson);
        }

        $newRequestsCount = Lesson::query()
            ->where('tutor_id', $user->id)
            ->where('status', Lesson::STATUS_PENDING)
            ->count();

        // Подписка репетитора
        $subscription = $user->subscription;
        if (! $subscription) {
            /** @var \App\Domain\Subscription\Services\SubscriptionService $subService */
            $subService = app(\App\Domain\Subscription\Services\SubscriptionService::class);
            $subscription = $subService->startTrial($user, \App\Domain\Subscription\Enums\SubscriptionPlan::PRO);
        }

        $isTrial = $subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::TRIAL;
        $isActive = $subscription->status === \App\Domain\Subscription\Enums\SubscriptionStatus::ACTIVE;
        $isInGrace = $subscription->isInGracePeriod();
        $daysRemaining = $subscription->daysRemaining();
        $graceDaysRemaining = $subscription->graceDaysRemaining();
        $isExpiringSoon = ($isTrial || $isActive) && $daysRemaining <= 3 && $daysRemaining > 0;

        if ($isTrial) {
            $subPillText = "Пробный период: {$daysRemaining} " . trans_choice('день|дня|дней', $daysRemaining) . " бесплатно";
        } elseif ($isActive) {
            $subPillText = "Тариф: " . $subscription->plan->title();
        } elseif ($isInGrace) {
            $subPillText = "Льготный период (" . $graceDaysRemaining . " " . trans_choice('день|дня|дней', $graceDaysRemaining) . ")";
        } else {
            $subPillText = "Тариф: " . $subscription->plan->title();
        }

        // Персональная ссылка для записи
        $tutorProfile = $user->tutorProfile;
        $bookingUrl = $tutorProfile
            ? route('tutors.show', $tutorProfile)
            : url('/tutors/' . $user->id);

        return [
            'greeting' => $greeting,
            'firstName' => explode(' ', trim((string) $user->name))[0] ?? $user->name,
            'dateLine' => now($timezone)->translatedFormat('l, j F'),
            'upcomingLesson' => $upcomingLesson,
            'lessonStartsAt' => $upcomingLesson?->start_time->getTimestamp(),
            'lessonEndsAt' => $upcomingLesson?->end_time->getTimestamp(),
            'lessonJoinFrom' => $upcomingLesson?->start_time->copy()->subMinutes(self::JOIN_LEAD_MINUTES)->getTimestamp(),
            'lessonJoinUntil' => $upcomingLesson?->end_time->getTimestamp(),
            'meetingJoinAvailable' => $meetingJoinAvailable,
            'classroomUrl' => $classroomUrl,
            'newRequestsCount' => $newRequestsCount,
            'subscription' => $subscription,
            'subPillText' => $subPillText,
            'isTrial' => $isTrial,
            'isActive' => $isActive,
            'isInGrace' => $isInGrace,
            'isExpiringSoon' => $isExpiringSoon,
            'daysRemaining' => $daysRemaining,
            'graceDaysRemaining' => $graceDaysRemaining,
            'bookingUrl' => $bookingUrl,
        ];
    }
}
