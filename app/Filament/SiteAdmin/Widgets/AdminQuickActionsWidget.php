<?php

declare(strict_types=1);

namespace App\Filament\SiteAdmin\Widgets;

use App\Filament\Pages\MessagesPage;
use App\Filament\Resources\LessonRequestResource;
use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\TutorProfileResource;
use Filament\Widgets\Widget;

class AdminQuickActionsWidget extends Widget
{
    protected static string $view = 'filament.site-admin.widgets.admin-quick-actions-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    protected function getViewData(): array
    {
        return [
            'actions' => [
                [
                    'label' => 'Проверить анкеты',
                    'description' => 'Модерация профилей, дипломов и статусов.',
                    'url' => TutorProfileResource::getUrl(panel: 'site-admin'),
                    'tone' => 'dark',
                ],
                [
                    'label' => 'Открыть заявки',
                    'description' => 'Контроль новых уроков и спорных бронирований.',
                    'url' => LessonRequestResource::getUrl(panel: 'site-admin'),
                    'tone' => 'lime',
                ],
                [
                    'label' => 'Финансы',
                    'description' => 'Возвраты, оплаты и динамика транзакций.',
                    'url' => TransactionResource::getUrl(panel: 'site-admin'),
                    'tone' => 'white',
                ],
                [
                    'label' => 'Сообщения',
                    'description' => 'Чаты, системные маскировки и инциденты.',
                    'url' => MessagesPage::getUrl(panel: 'site-admin'),
                    'tone' => 'white',
                ],
            ],
        ];
    }
}
