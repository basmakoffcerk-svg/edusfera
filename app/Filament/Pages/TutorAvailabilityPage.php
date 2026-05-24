<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\TutorAvailability;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TutorAvailabilityPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.pages.tutor-availability-page';

    protected static ?string $navigationLabel = 'Доступность';

    protected static ?int $navigationSort = 20;

    public array $availability = [];

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'tutor'], true), 403);

        $existing = TutorAvailability::query()
            ->where('user_id', auth()->id())
            ->get()
            ->keyBy('day_of_week');

        $this->availability = collect($this->weekDays())
            ->map(function (string $label, int $day) use ($existing): array {
                $row = $existing->get($day);

                return [
                    'day_of_week' => $day,
                    'day_label' => $label,
                    'is_active' => (bool) ($row?->is_active ?? false),
                    'start_time' => substr((string) ($row?->start_time ?? '10:00'), 0, 5),
                    'end_time' => substr((string) ($row?->end_time ?? '18:00'), 0, 5),
                ];
            })
            ->values()
            ->all();
    }

    public function save(): void
    {
        validator(
            ['availability' => $this->availability],
            [
                'availability' => ['required', 'array', 'size:7'],
                'availability.*.day_of_week' => ['required', 'integer'],
                'availability.*.is_active' => ['required', 'boolean'],
                'availability.*.start_time' => ['required', 'date_format:H:i'],
                'availability.*.end_time' => ['required', 'date_format:H:i'],
            ],
            [
                'availability.*.start_time.date_format' => 'Укажите время начала в формате ЧЧ:ММ.',
                'availability.*.end_time.date_format' => 'Укажите время окончания в формате ЧЧ:ММ.',
            ],
        )->after(function ($validator): void {
            foreach ($this->availability as $row) {
                if (! ($row['is_active'] ?? false)) {
                    continue;
                }

                if (($row['start_time'] ?? '') === ($row['end_time'] ?? '')) {
                    $validator->errors()->add(
                        'availability',
                        'В активных днях время начала и окончания не должны совпадать.'
                    );
                }
            }
        })->validate();

        TutorAvailability::query()->where('user_id', auth()->id())->delete();

        collect($this->availability)->each(function (array $row): void {
            TutorAvailability::query()->create([
                'user_id' => auth()->id(),
                'day_of_week' => (int) $row['day_of_week'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'is_active' => (bool) $row['is_active'],
            ]);
        });

        Notification::make()
            ->title('Календарь доступности сохранен')
            ->body('Ученики уже видят новые временные окна в каталоге и профиле.')
            ->success()
            ->send();
    }

    public function applyPreset(string $preset): void
    {
        $templates = match ($preset) {
            'weekdays' => [
                1 => [true, '10:00', '18:00'],
                2 => [true, '10:00', '18:00'],
                3 => [true, '10:00', '18:00'],
                4 => [true, '10:00', '18:00'],
                5 => [true, '10:00', '18:00'],
                6 => [false, '10:00', '18:00'],
                0 => [false, '10:00', '18:00'],
            ],
            'evenings' => [
                1 => [true, '16:00', '21:00'],
                2 => [true, '16:00', '21:00'],
                3 => [true, '16:00', '21:00'],
                4 => [true, '16:00', '21:00'],
                5 => [true, '16:00', '21:00'],
                6 => [true, '10:00', '15:00'],
                0 => [false, '10:00', '18:00'],
            ],
            'everyday' => [
                1 => [true, '10:00', '18:00'],
                2 => [true, '10:00', '18:00'],
                3 => [true, '10:00', '18:00'],
                4 => [true, '10:00', '18:00'],
                5 => [true, '10:00', '18:00'],
                6 => [true, '10:00', '16:00'],
                0 => [true, '10:00', '16:00'],
            ],
            default => null,
        };

        if ($templates === null) {
            return;
        }

        $this->availability = collect($this->availability)
            ->map(function (array $row) use ($templates): array {
                [$isActive, $startTime, $endTime] = $templates[(int) $row['day_of_week']];

                $row['is_active'] = $isActive;
                $row['start_time'] = $startTime;
                $row['end_time'] = $endTime;

                return $row;
            })
            ->all();
    }

    public function clearCalendar(): void
    {
        $this->availability = collect($this->availability)
            ->map(function (array $row): array {
                $row['is_active'] = false;
                $row['start_time'] = '10:00';
                $row['end_time'] = '18:00';

                return $row;
            })
            ->all();
    }

    public function getUpcomingCalendarProperty(): array
    {
        $today = CarbonImmutable::now(config('booking.display_timezone'))->startOfDay();

        return collect(range(0, 6))
            ->map(function (int $offset) use ($today): array {
                $date = $today->addDays($offset);
                $weekday = (int) $date->dayOfWeek;
                $row = collect($this->availability)->firstWhere('day_of_week', $weekday);

                $slots = collect();

                if (($row['is_active'] ?? false) === true) {
                    $cursor = CarbonImmutable::parse($date->format('Y-m-d').' '.$row['start_time'], config('booking.display_timezone'));
                    $end = CarbonImmutable::parse($date->format('Y-m-d').' '.$row['end_time'], config('booking.display_timezone'));

                    if ($end->lessThanOrEqualTo($cursor)) {
                        $end = $end->addDay();
                    }

                    while ($cursor->lt($end)) {
                        $slots->push($cursor->format('H:i'));
                        $cursor = $cursor->addHour();
                    }
                }

                return [
                    'label' => $date->translatedFormat('D, d M'),
                    'full_label' => $date->translatedFormat('l, d F'),
                    'is_active' => (bool) ($row['is_active'] ?? false),
                    'slots' => $slots->take(6)->all(),
                    'extra_slots' => max($slots->count() - 6, 0),
                ];
            })
            ->all();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin'
            && auth()->user()?->role === 'tutor';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Организация';
    }

    private function weekDays(): array
    {
        return [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            0 => 'Воскресенье',
        ];
    }
}
