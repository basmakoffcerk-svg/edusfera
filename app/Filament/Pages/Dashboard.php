<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\MaxWidth;

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

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        // У репетитора заголовок страницы («Главная») дублирует приветствие
        // hero-виджета — прячем штатный заголовок (пустая строка = falsy,
        // Filament пропускает блок заголовка), остаётся один герой.
        if (auth()->user()?->isTutor()) {
            return '';
        }

        return parent::getHeading();
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        if (auth()->user()?->isTutor()) {
            return null;
        }

        return parent::getSubheading();
    }
}
