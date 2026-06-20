<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lesson;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class StudentTutorsWidget extends Widget
{
    protected static string $view = 'filament.widgets.student-tutors-widget';

    protected int|string|array $columnSpan = 'half';

    protected static ?int $sort = 3;

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

        // Find unique tutors this student has booked or completed lessons with
        $studentConditionColumn = $user->role === 'parent' ? 'parent_id' : 'student_id';

        $tutors = User::query()
            ->where('role', 'tutor')
            ->whereHas('tutorLessons', function ($query) use ($studentConditionColumn, $user) {
                $query->where($studentConditionColumn, $user->id)
                    ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_COMPLETED]);
            })
            ->with(['tutorProfile'])
            ->limit(5)
            ->get();

        return [
            'tutors' => $tutors,
        ];
    }
}
