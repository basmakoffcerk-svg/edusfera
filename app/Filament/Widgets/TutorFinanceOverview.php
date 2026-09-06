<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Lesson;
use Filament\Widgets\Widget;

/**
 * Финансовая сводка кабинета репетитора: заработано за месяц (прямая оплата),
 * проведено занятий, ожидаемый доход по расписанию.
 * Деньги репетиторов не аккумулируются на платформе — ученики платят напрямую на карту.
 */
class TutorFinanceOverview extends Widget
{
    protected static string $view = "filament.widgets.tutor-finance-overview";

    protected int|string|array $columnSpan = "full";

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->isTutor() ?? false;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        if (! $user) {
            return ["stats" => []];
        }

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth()->utc();
        $endOfMonth = $now->copy()->endOfMonth()->utc();

        $earnedThisMonth = Lesson::query()
            ->where("tutor_id", $user->id)
            ->where("status", Lesson::STATUS_COMPLETED)
            ->where("start_time", ">=", $startOfMonth)
            ->where("start_time", "<=", $endOfMonth)
            ->sum("amount");

        $completedLessonsCount = Lesson::query()
            ->where("tutor_id", $user->id)
            ->where("status", Lesson::STATUS_COMPLETED)
            ->where("start_time", ">=", $startOfMonth)
            ->where("start_time", "<=", $endOfMonth)
            ->count();

        $monthlyForecast = Lesson::query()
            ->where("tutor_id", $user->id)
            ->whereIn("status", [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])
            ->where("start_time", ">=", now()->utc())
            ->where("start_time", "<=", $endOfMonth)
            ->sum("amount");

        return [
            "stats" => [
                [
                    "label" => "Заработано за " . now()->translatedFormat("F"),
                    "value" => $this->shortMoney((string) $earnedThisMonth) . " BYN",
                    "hint" => "Прямая оплата на вашу карту",
                    "href" => "/admin/lessons",
                    "accent" => true,
                ],
                [
                    "label" => "Проведено занятий",
                    "value" => (string) $completedLessonsCount,
                    "hint" => "Во встроенном классе",
                    "href" => "/admin/lessons",
                    "accent" => false,
                ],
                [
                    "label" => "Запланировано к получению",
                    "value" => $this->shortMoney((string) $monthlyForecast) . " BYN",
                    "hint" => "Ожидаемый доход от учеников",
                    "href" => "/admin/lessons",
                    "accent" => false,
                ],
            ],
        ];
    }

    /**
     * Короткий формат денег: копейки отбрасываются, когда они нулевые.
     */
    private function shortMoney(string $amount): string
    {
        $value = (float) $amount;

        if (fmod($value, 1.0) < 0.005) {
            return number_format($value, 0, ".", " ");
        }

        return number_format($value, 2, ".", " ");
    }
}
