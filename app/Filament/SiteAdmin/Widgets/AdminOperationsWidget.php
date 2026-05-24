<?php

declare(strict_types=1);

namespace App\Filament\SiteAdmin\Widgets;

use App\Filament\Resources\LessonRequestResource;
use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\TutorProfileResource;
use App\Models\Lesson;
use App\Models\Transaction;
use App\Models\TutorProfile;
use Filament\Widgets\Widget;

class AdminOperationsWidget extends Widget
{
    protected static string $view = 'filament.site-admin.widgets.admin-operations-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected function getViewData(): array
    {
        $pendingProfiles = TutorProfile::query()
            ->with('user')
            ->where('verification_status', 'pending')
            ->latest('verification_submitted_at')
            ->limit(5)
            ->get();

        $riskProfiles = TutorProfile::query()
            ->with('user')
            ->where(function ($query): void {
                $query
                    ->where('contact_bypass_attempts', '>', 0)
                    ->orWhere('search_penalized_until', '>', now());
            })
            ->orderByDesc('contact_bypass_attempts')
            ->latest('search_penalized_until')
            ->limit(5)
            ->get();

        $latestTransactions = Transaction::query()
            ->with(['lesson.student', 'lesson.tutor'])
            ->latest('paid_at')
            ->limit(5)
            ->get();

        $upcomingLessons = Lesson::query()
            ->with(['student', 'tutor'])
            ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        return [
            'pendingProfiles' => $pendingProfiles,
            'riskProfiles' => $riskProfiles,
            'latestTransactions' => $latestTransactions,
            'upcomingLessons' => $upcomingLessons,
            'tutorProfilesUrl' => TutorProfileResource::getUrl(panel: 'site-admin'),
            'lessonRequestsUrl' => LessonRequestResource::getUrl(panel: 'site-admin'),
            'transactionsUrl' => TransactionResource::getUrl(panel: 'site-admin'),
        ];
    }
}
