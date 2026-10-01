<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\TutorApplicationReceivedNotification;
use App\Notifications\TutorVerificationApprovedNotification;
use App\Notifications\TutorVerificationRejectedNotification;
use App\Notifications\TutorVerificationSubmittedNotification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Log;

class TutorVerificationService
{
    /**
     * Одобрить анкету репетитора.
     */
    public function approve(TutorProfile $profile, ?User $admin = null): void
    {
        $profile->updateQuietly([
            'verification_status' => 'approved',
            'is_verified' => true,
        ]);

        $tutorUser = $profile->user;
        if ($tutorUser) {
            $tutorUser->updateQuietly([
                'is_verified' => true,
            ]);

            // 1. Мгновенная гарантированная доставка в колокольчик Filament
            try {
                $catalogUrl = route('tutors.show', $profile->id);
                FilamentNotification::make()
                    ->title('🎉 Ваша анкета одобрена!')
                    ->body('Поздравляем! Ваша анкета проверена, получен статус «✓ Диплом проверен», и ваш профиль опубликован в каталоге.')
                    ->icon('heroicon-o-check-badge')
                    ->iconColor('success')
                    ->actions([
                        Action::make('view_catalog')
                            ->label('Посмотреть в каталоге')
                            ->url($catalogUrl)
                            ->openUrlInNewTab()
                            ->button(),
                        Action::make('availability')
                            ->label('Календарь занятий')
                            ->url('/admin/tutor-availability-page')
                            ->color('gray'),
                    ])
                    ->sendToDatabase($tutorUser);
            } catch (\Throwable $e) {
                Log::error('Failed to send database notification to tutor: '.$e->getMessage());
            }

            // 2. Отправка Email-уведомления репетитору (с защитой от сбоев SMTP)
            try {
                $tutorUser->notify(new TutorVerificationApprovedNotification($profile));
            } catch (\Throwable $e) {
                Log::warning('Email notification to tutor skipped or failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Отклонить анкету репетитора с указанием причины.
     */
    public function reject(TutorProfile $profile, ?User $admin = null, ?string $reason = null): void
    {
        $profile->updateQuietly([
            'verification_status' => 'rejected',
            'is_verified' => false,
        ]);

        $tutorUser = $profile->user;
        if ($tutorUser) {
            $tutorUser->updateQuietly([
                'is_verified' => false,
            ]);

            // 1. Мгновенная доставка в колокольчик Filament
            try {
                $reasonText = $reason ? " Причина: {$reason}" : ' Пожалуйста, проверьте качество фото, скан диплома и полноту описания опыта.';
                FilamentNotification::make()
                    ->title('Анкета требует доработки')
                    ->body('Модератор проверил вашу анкету, но для публикации требуются уточнения.'.$reasonText)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('danger')
                    ->actions([
                        Action::make('edit')
                            ->label('Редактировать анкету')
                            ->url('/admin/tutor-profiles/'.$profile->id.'/edit')
                            ->button(),
                    ])
                    ->sendToDatabase($tutorUser);
            } catch (\Throwable $e) {
                Log::error('Failed to send database reject notification: '.$e->getMessage());
            }

            // 2. Email-уведомление (с защитой от сбоев SMTP)
            try {
                $tutorUser->notify(new TutorVerificationRejectedNotification($profile, $reason));
            } catch (\Throwable $e) {
                Log::warning('Email reject notification skipped or failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Подача анкеты репетитором на модерацию.
     */
    public function submitForReview(TutorProfile $profile): void
    {
        $profile->updateQuietly([
            'verification_status' => 'pending',
            'is_verified' => false,
            'verification_submitted_at' => now(),
            'onboarding_completed_at' => now(),
        ]);

        $tutorUser = $profile->user;
        if ($tutorUser) {
            // 1. Уведомление репетитору
            try {
                FilamentNotification::make()
                    ->title('Анкета отправлена на проверку')
                    ->body('Мы получили вашу анкету и документы. Технический администратор проверит данные (обычно в течение нескольких часов), после чего профиль появится в каталоге.')
                    ->icon('heroicon-o-clock')
                    ->iconColor('info')
                    ->sendToDatabase($tutorUser);
            } catch (\Throwable $e) {
                Log::error('Failed sending submit database notification: '.$e->getMessage());
            }

            try {
                $tutorUser->notify(new TutorVerificationSubmittedNotification($profile));
            } catch (\Throwable $e) {
                Log::warning('Email submit notification skipped: '.$e->getMessage());
            }
        }

        // 2. Оповестить администраторов
        $this->notifyAdminsAboutNewApplication($profile);
    }

    /**
     * Оповестить администраторов о новой анкете.
     */
    public function notifyAdminsAboutNewApplication(TutorProfile $profile): void
    {
        try {
            $admins = User::query()
                ->where(function ($query) {
                    $query->where('role', UserRole::Admin)
                        ->orWhere('role', 'admin');
                })
                ->get();

            $tutorName = $profile->user?->name ?? 'Новый репетитор';
            $editUrl = '/site-admin/tutor-profiles/'.$profile->id.'/edit';
            $subjects = is_array($profile->subjects)
                ? implode(', ', $profile->subjects)
                : ($profile->subjects ?? '');

            $body = "Репетитор {$tutorName} отправил(а) анкету на модерацию.";
            if ($subjects) {
                $body .= " Предметы: {$subjects}.";
            }

            foreach ($admins as $admin) {
                try {
                    FilamentNotification::make()
                        ->title('Новая анкета репетитора')
                        ->body($body)
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->iconColor('warning')
                        ->actions([
                            Action::make('review')
                                ->label('Проверить анкету')
                                ->url($editUrl)
                                ->button(),
                        ])
                        ->sendToDatabase($admin);
                } catch (\Throwable $e) {
                    Log::error('Admin database notification failed: '.$e->getMessage());
                }

                try {
                    $admin->notify(new TutorApplicationReceivedNotification($profile));
                } catch (\Throwable $e) {
                    // SMTP safe
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed notifying admins about tutor application: '.$e->getMessage());
        }
    }
}
