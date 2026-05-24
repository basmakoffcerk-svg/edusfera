<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class TutorWelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.tutor-welcome-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'tutor';
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'name' => $user?->name ?? 'Репетитор',
        ];
    }
}
