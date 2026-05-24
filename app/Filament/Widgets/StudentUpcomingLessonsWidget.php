<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lesson;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class StudentUpcomingLessonsWidget extends Widget
{
    protected static string $view = 'filament.widgets.student-upcoming-lessons-widget';

    protected int|string|array $columnSpan = 'half';

    protected static ?int $sort = 2; // Right after Welcome widget

    public static function canView(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user && in_array($user->role, ['student', 'parent'], true);
    }

    protected function getViewData(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Get up to 3 upcoming or currently running lessons
        $lessonsRelation = $user->role === 'parent' ? $user->parentLessons() : $user->studentLessons();

        $upcomingLessons = $lessonsRelation
            ->with(['tutor.tutorProfile'])
            ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_PENDING])
            ->where('end_time', '>', now())
            ->orderBy('start_time', 'asc')
            ->limit(3)
            ->get();

        return [
            'upcomingLessons' => $upcomingLessons,
        ];
    }
}
