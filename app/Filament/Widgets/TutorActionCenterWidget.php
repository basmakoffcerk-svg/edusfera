<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lesson;
use Filament\Widgets\Widget;

class TutorActionCenterWidget extends Widget
{
    protected static string $view = 'filament.widgets.tutor-action-center-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'tutor';
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        $newRequestsCount = Lesson::query()
            ->where('tutor_id', $user->id)
            ->where('status', Lesson::STATUS_PENDING)
            ->count();

        $latestRequest = Lesson::query()
            ->with(['student', 'parent'])
            ->where('tutor_id', $user->id)
            ->where('status', Lesson::STATUS_PENDING)
            ->orderBy('created_at')
            ->first();

        $upcomingLesson = Lesson::query()
            ->with('student')
            ->where('tutor_id', $user->id)
            ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_PENDING])
            ->where('start_time', '>', now()->utc())
            ->orderBy('start_time')
            ->first();

        $meetingJoinAvailable = $upcomingLesson !== null
            && $upcomingLesson->meeting_link !== null
            && now('UTC')->greaterThanOrEqualTo($upcomingLesson->start_time->copy()->subMinutes(10));

        return [
            'newRequestsCount' => $newRequestsCount,
            'latestRequest' => $latestRequest,
            'upcomingLesson' => $upcomingLesson,
            'meetingJoinAvailable' => $meetingJoinAvailable,
        ];
    }
}
