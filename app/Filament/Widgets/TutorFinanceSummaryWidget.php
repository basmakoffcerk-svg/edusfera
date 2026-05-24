<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lesson;
use App\Models\TutorBalance;
use App\Support\BynMoneyFormatter;
use Filament\Widgets\Widget;

class TutorFinanceSummaryWidget extends Widget
{
    protected static string $view = 'filament.widgets.tutor-finance-summary-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'tutor';
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        $balance = TutorBalance::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'available_amount' => '0.00',
                'pending_amount' => '0.00',
                'total_earned' => '0.00',
                'total_withdrawn' => '0.00',
            ],
        );

        $monthlyForecast = Lesson::query()
            ->where('tutor_id', $user->id)
            ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])
            ->where('start_time', '>=', now()->utc())
            ->where('start_time', '<=', now()->endOfMonth()->utc())
            ->sum('net_amount');

        return [
            'cards' => [
                [
                    'eyebrow' => 'Доступно к выводу',
                    'value' => BynMoneyFormatter::format((string) $balance->available_amount),
                    'copy' => 'Готово к выплате по графику платформы.',
                    'accent' => 'lime',
                    'url' => '/admin/transactions',
                    'action' => 'Открыть выплаты',
                ],
                [
                    'eyebrow' => 'Заморожено',
                    'value' => BynMoneyFormatter::format((string) $balance->pending_amount),
                    'copy' => 'Уже оплачено учениками и ждет проведения уроков.',
                    'accent' => 'amber',
                    'url' => '/admin/lesson-requests',
                    'action' => 'Смотреть заявки',
                ],
                [
                    'eyebrow' => 'Прогноз до конца месяца',
                    'value' => BynMoneyFormatter::format($monthlyForecast),
                    'copy' => 'Сумма по текущему календарю и подтвержденным слотам.',
                    'accent' => 'slate',
                    'url' => '/admin/lessons',
                    'action' => 'Открыть расписание',
                ],
            ],
        ];
    }
}
