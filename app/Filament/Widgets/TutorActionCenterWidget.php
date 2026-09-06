<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lesson;
use App\Services\ChatUnreadCounter;
use Filament\Widgets\Widget;

/**
 * Центр действий репетитора: последняя заявка с большими кнопками ответа
 * и быстрые плитки-переходы (на смартфоне боковое меню скрыто — плитки
 * заменяют его на главном экране).
 */
class TutorActionCenterWidget extends Widget
{
    protected static string $view = 'filament.widgets.tutor-action-center-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->isTutor() ?? false;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        $newRequestsCount = Lesson::query()
            ->where('tutor_id', $user->id)
            ->where('status', Lesson::STATUS_PENDING)
            ->count();

        $latestRequest = Lesson::query()
            ->with(['student', 'parent', 'conversation'])
            ->where('tutor_id', $user->id)
            ->where('status', Lesson::STATUS_PENDING)
            ->orderBy('created_at')
            ->first();

        $chatUrl = '/admin/messages';
        if ($latestRequest?->conversation_id) {
            $chatUrl = '/admin/messages?conversation='.$latestRequest->conversation_id;
        }

        return [
            'newRequestsCount' => $newRequestsCount,
            'latestRequest' => $latestRequest,
            'chatUrl' => $chatUrl,
            'unreadMessages' => app(ChatUnreadCounter::class)->countForUser($user),
        ];
    }
}
