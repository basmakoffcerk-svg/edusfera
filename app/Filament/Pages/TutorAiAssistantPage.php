<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class TutorAiAssistantPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $slug = 'tutor-ai-assistant';

    protected static string $view = 'filament.pages.tutor-ai-assistant-page';

    protected static ?string $navigationLabel = 'ИИ-Помощник';

    protected static ?string $title = 'ИИ-Помощник репетитора (Google Gemini)';

    protected static ?int $navigationSort = 5;

    public function mount(): void
    {
        $role = auth()->user()?->role;
        $roleVal = is_object($role) ? $role->value : (string) $role;
        abort_unless(in_array($roleVal, ['admin', 'tutor'], true), 403);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return Filament::getCurrentPanel()?->getId() === 'admin'
            && ($user?->isTutor() || $user?->isAdmin());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Преподавание';
    }

    public function canUseAi(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return app(SubscriptionFeatureGate::class)->canUseAiTools($user);
    }

    public function getViewData(): array
    {
        $user = auth()->user();

        return [
            'canUseAi' => $this->canUseAi(),
            'user' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'role' => is_object($user?->role) ? $user?->role->value : (string) $user?->role,
                'subject' => $user?->tutorProfile?->subject ?? 'Математика',
            ],
            'csrfToken' => csrf_token(),
            'routes' => [
                'lessonPlan' => route('admin.ai-copilot.lesson-plan'),
                'quiz' => route('admin.ai-copilot.quiz'),
                'chat' => route('admin.ai-copilot.chat'),
                'subscription' => route('filament.admin.pages.tutor-subscription-page'),
            ],
        ];
    }
}
