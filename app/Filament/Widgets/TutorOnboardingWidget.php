<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lesson;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use Filament\Widgets\Widget;

class TutorOnboardingWidget extends Widget
{
    protected static string $view = 'filament.widgets.tutor-onboarding-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        $user = auth()->user();

        if ($user?->role !== 'tutor') {
            return false;
        }

        $profile = TutorProfile::query()->where('user_id', $user->id)->first();

        if (! $profile) {
            return true;
        }

        $hasSubjectAndPrice = ! empty($profile->subjects) && (float) $profile->price_per_hour > 0;
        $hasPhotoAndDiploma = ! empty($profile->avatar_path) && ! empty($profile->diploma_path);
        $hasExtendedProfile = ! empty($profile->education_summary)
            && ! empty($profile->teaching_methodology)
            && ! empty($profile->achievements);
        $hasAvailability = TutorAvailability::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
        $moderationDone = $profile->verification_status === 'approved';

        $isCompleted = $hasSubjectAndPrice && $hasPhotoAndDiploma && $hasExtendedProfile && $hasAvailability && $moderationDone;

        if (! $isCompleted) {
            return true;
        }

        $hasLeads = Lesson::query()
            ->where('tutor_id', $user->id)
            ->exists();

        return $profile->created_at->lt(now()->subDays(7)) && ! $hasLeads;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        $profile = TutorProfile::query()->where('user_id', $user->id)->first();

        if (! $profile) {
            return [
                'hasProfile' => false,
            ];
        }

        $hasSubjectAndPrice = ! empty($profile->subjects) && (float) $profile->price_per_hour > 0;
        $hasPhotoAndDiploma = ! empty($profile->avatar_path) && ! empty($profile->diploma_path);
        $hasExtendedProfile = ! empty($profile->education_summary)
            && ! empty($profile->teaching_methodology)
            && ! empty($profile->achievements);
        $hasAvailability = TutorAvailability::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
        $moderationDone = $profile->verification_status === 'approved';

        $stepsCompleted = collect([$hasSubjectAndPrice, $hasPhotoAndDiploma, $hasExtendedProfile, $hasAvailability, $moderationDone])
            ->filter(fn (bool $done): bool => $done)
            ->count();
        $progress = (int) round(($stepsCompleted / 5) * 100);

        $hasLeads = Lesson::query()
            ->where('tutor_id', $user->id)
            ->exists();

        $showAntiChurn = $profile->created_at->lt(now()->subDays(7)) && ! $hasLeads;

        $verifiedProfiles = TutorProfile::query()
            ->where('is_verified', true)
            ->orderByDesc('rating_avg')
            ->orderBy('price_per_hour')
            ->pluck('user_id')
            ->values();

        $rank = $verifiedProfiles->search($user->id);
        $rank = $rank === false ? null : $rank + 1;

        return [
            'hasProfile' => true,
            'profileId' => $profile->id,
            'progress' => $progress,
            'rank' => $rank,
            'steps' => [
                [
                    'done' => $hasSubjectAndPrice,
                    'label' => 'Указать предмет и цену',
                    'action' => route('filament.admin.resources.tutor-profiles.edit', ['record' => $profile]),
                    'action_label' => 'Изменить',
                ],
                [
                    'done' => $hasPhotoAndDiploma,
                    'label' => 'Загрузить фото и диплом',
                    'action' => route('filament.admin.resources.tutor-profiles.edit', ['record' => $profile]),
                    'action_label' => 'Добавить',
                ],
                [
                    'done' => $hasAvailability,
                    'label' => 'Добавить расписание',
                    'action' => route('filament.admin.pages.tutor-availability-page'),
                    'action_label' => 'Открыть календарь',
                ],
                [
                    'done' => $hasExtendedProfile,
                    'label' => 'Заполнить расширенный профиль',
                    'action' => route('filament.admin.resources.tutor-profiles.edit', ['record' => $profile]),
                    'action_label' => 'Заполнить',
                ],
                [
                    'done' => $moderationDone,
                    'label' => 'Пройти модерацию',
                    'action' => null,
                    'action_label' => $profile->verification_status === 'rejected'
                        ? 'Требуются правки профиля'
                        : 'Проверяем данные, обычно до 2 часов',
                ],
            ],
            'showAntiChurn' => $showAntiChurn,
            'antiChurn' => [
                'editUrl' => route('filament.admin.resources.tutor-profiles.edit', ['record' => $profile]),
            ],
        ];
    }
}
