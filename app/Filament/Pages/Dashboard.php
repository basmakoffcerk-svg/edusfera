<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }

    public static function getNavigationLabel(): string
    {
        return 'Главная';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-home';
    }

    public function getHeading(): string|Htmlable
    {
        // У репетитора и ученика заголовок страницы («Главная» / «Инфопанель»)
        // дублирует приветствие hero-виджета — прячем штатный заголовок,
        // оставляя один сильный визуальный герой экрана.
        $user = auth()->user();
        if ($user && ($user->isTutor() || $user->isStudent() || $user->isParent())) {
            return '';
        }

        return parent::getHeading();
    }

    public function getSubheading(): string|Htmlable|null
    {
        $user = auth()->user();
        if ($user && ($user->isTutor() || $user->isStudent() || $user->isParent())) {
            return null;
        }

        return parent::getSubheading();
    }
}
