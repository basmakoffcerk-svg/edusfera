<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\LessonResource;
use App\Filament\Resources\LessonResource\Widgets\LessonOverviewWidget;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;

    protected static string $view = 'filament.resources.lesson-resource.pages.list-lessons';

    public string $viewMode = 'calendar';

    public ?string $currentWeekStart = null;

    public ?int $selectedCalendarLessonId = null;

    public function mount(): void
    {
        parent::mount();

        $user = auth()->user();
        $this->viewMode = ($user?->isTutor() ?? false) ? 'calendar' : 'table';

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $this->currentWeekStart = Carbon::now($displayTz)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public function getTitle(): string
    {
        return LessonResource::getNavigationLabel();
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['calendar', 'table'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function previousWeek(): void
    {
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $this->currentWeekStart = Carbon::parse($this->currentWeekStart, $displayTz)
            ->subWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->format('Y-m-d');
    }

    public function nextWeek(): void
    {
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $this->currentWeekStart = Carbon::parse($this->currentWeekStart, $displayTz)
            ->addWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->format('Y-m-d');
    }

    public function goToToday(): void
    {
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $this->currentWeekStart = Carbon::now($displayTz)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public function openCalendarLesson(int $lessonId): void
    {
        $this->selectedCalendarLessonId = $lessonId;
    }

    public function closeCalendarLesson(): void
    {
        $this->selectedCalendarLessonId = null;
    }

    public function markLessonPaidDirectly(int $lessonId): void
    {
        $user = auth()->user();
        $lesson = Lesson::query()
            ->when($user?->isTutor(), fn ($q) => $q->where('tutor_id', $user->id))
            ->findOrFail($lessonId);

        $lesson->update([
            'payment_status' => Lesson::PAYMENT_PAID,
            'status' => $lesson->status === Lesson::STATUS_PENDING ? Lesson::STATUS_CONFIRMED : $lesson->status,
        ]);

        Notification::make()
            ->title('Оплата зафиксирована')
            ->body('Урок отмечен как оплаченный напрямую. Доступ в виртуальный класс открыт.')
            ->success()
            ->send();
    }

    public function exportIcs(): StreamedResponse
    {
        $user = auth()->user();
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');

        $lessons = Lesson::query()
            ->with(['tutor', 'student'])
            ->when($user?->isTutor(), fn (Builder $q) => $q->where('tutor_id', $user->id))
            ->when($user && ! $user->isTutor() && ! $user->isAdmin(), fn (Builder $q) => $q->where('student_id', $user->id))
            ->where('start_time', '>=', Carbon::now('UTC')->subMonth())
            ->where('status', '!=', Lesson::STATUS_CANCELLED)
            ->get();

        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//Edusfera//Calendar 1.0//RU\r\n";
        $icsContent .= "CALSCALE:GREGORIAN\r\n";
        $icsContent .= "METHOD:PUBLISH\r\n";
        $icsContent .= "X-WR-CALNAME:Edusfera Расписание\r\n";
        $icsContent .= "X-WR-TIMEZONE:{$displayTz}\r\n";

        foreach ($lessons as $lesson) {
            $dtStart = $lesson->start_time->setTimezone('UTC')->format('Ymd\THis\Z');
            $dtEnd = $lesson->end_time->setTimezone('UTC')->format('Ymd\THis\Z');
            $summary = 'Урок Edusfera: '.($user?->isTutor() ? ($lesson->student?->name ?? 'Ученик') : ($lesson->tutor?->name ?? 'Репетитор'));
            $description = 'Онлайн-урок на платформе Edusfera. Ссылка на класс: '.route('classroom.show', $lesson);

            $icsContent .= "BEGIN:VEVENT\r\n";
            $icsContent .= "UID:edusfera-lesson-{$lesson->id}@edusfera.by\r\n";
            $icsContent .= 'DTSTAMP:'.Carbon::now('UTC')->format('Ymd\THis\Z')."\r\n";
            $icsContent .= "DTSTART:{$dtStart}\r\n";
            $icsContent .= "DTEND:{$dtEnd}\r\n";
            $icsContent .= "SUMMARY:{$summary}\r\n";
            $icsContent .= "DESCRIPTION:{$description}\r\n";
            $icsContent .= 'URL:'.route('classroom.show', $lesson)."\r\n";
            $icsContent .= "STATUS:CONFIRMED\r\n";
            $icsContent .= "END:VEVENT\r\n";
        }

        $icsContent .= "END:VCALENDAR\r\n";

        return response()->streamDownload(
            function () use ($icsContent) {
                echo $icsContent;
            },
            'edusfera-schedule.ics',
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="edusfera-schedule.ics"',
            ]
        );
    }

    public function getCalendarWeekDaysProperty(): array
    {
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $startOfWeek = Carbon::parse($this->currentWeekStart, $displayTz)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);
        $today = Carbon::now($displayTz);

        $user = auth()->user();

        // Load all lessons for this week in UTC with safety margin for timezone offsets
        $startUtc = $startOfWeek->copy()->subDays(2)->startOfDay()->utc();
        $endUtc = $endOfWeek->copy()->addDays(2)->endOfDay()->utc();

        $lessons = Lesson::query()
            ->with(['tutor', 'student', 'parent'])
            ->when($user?->isTutor(), fn (Builder $q) => $q->where('tutor_id', $user->id))
            ->when($user && ! $user->isTutor() && ! $user->isAdmin(), fn (Builder $q) => $q->where('student_id', $user->id))
            ->where('start_time', '>=', $startUtc)
            ->where('start_time', '<=', $endUtc)
            ->get();

        $days = [];
        $russianDayNames = [
            1 => 'ПН',
            2 => 'ВТ',
            3 => 'СР',
            4 => 'ЧТ',
            5 => 'ПТ',
            6 => 'СБ',
            7 => 'ВС',
        ];

        for ($i = 0; $i < 7; $i++) {
            $dayDate = $startOfWeek->copy()->addDays($i);
            $dayKey = $dayDate->format('Y-m-d');
            $isToday = $dayDate->isSameDay($today);

            // Filter lessons for this day in local timezone
            $dayLessons = $lessons->filter(function (Lesson $lesson) use ($dayDate, $displayTz) {
                $lessonLocal = $lesson->start_time->setTimezone($displayTz);

                return $lessonLocal->isSameDay($dayDate);
            })->map(function (Lesson $lesson) use ($displayTz) {
                $startLocal = $lesson->start_time->setTimezone($displayTz);
                $endLocal = $lesson->end_time->setTimezone($displayTz);

                // Grid runs from 07:00 to 23:00 (16 hours = 960 mins)
                $gridStartHour = 7;
                $gridEndHour = 23;
                $totalMinutes = ($gridEndHour - $gridStartHour) * 60; // 960 min

                $startMinFromGrid = ($startLocal->hour - $gridStartHour) * 60 + $startLocal->minute;
                $durationMin = max(30, (int) $lesson->duration_minutes);

                $topPercent = max(0, min(100, ($startMinFromGrid / $totalMinutes) * 100));
                $heightPercent = max(3.5, min(100 - $topPercent, ($durationMin / $totalMinutes) * 100));

                $gcalUrl = 'https://calendar.google.com/calendar/render?'.http_build_query([
                    'action' => 'TEMPLATE',
                    'text' => 'Урок: '.($lesson->student?->name ?? 'Ученик'),
                    'dates' => $lesson->start_time->setTimezone('UTC')->format('Ymd\THis\Z').'/'.$lesson->end_time->setTimezone('UTC')->format('Ymd\THis\Z'),
                    'details' => 'Edusfera онлайн-урок. Ссылка: '.route('classroom.show', $lesson),
                    'location' => route('classroom.show', $lesson),
                ]);

                return [
                    'id' => $lesson->id,
                    'record' => $lesson,
                    'title' => auth()->user()?->isTutor() ? ($lesson->student?->name ?? 'Ученик') : ($lesson->tutor?->name ?? 'Репетитор'),
                    'student_name' => $lesson->student?->name ?? 'Ученик',
                    'tutor_name' => $lesson->tutor?->name ?? 'Репетитор',
                    'time_span' => $startLocal->format('H:i').' – '.$endLocal->format('H:i'),
                    'start_formatted' => $startLocal->format('H:i'),
                    'end_formatted' => $endLocal->format('H:i'),
                    'status' => $lesson->status,
                    'status_label' => LessonResource::statusLabel($lesson->status),
                    'payment_status' => $lesson->payment_status,
                    'price' => $lesson->price,
                    'meeting_link' => $lesson->meeting_link,
                    'top_percent' => round($topPercent, 2),
                    'height_percent' => round($heightPercent, 2),
                    'top_px' => max(4, min(900, $startMinFromGrid)),
                    'height_px' => max(36, min(180, $durationMin)),
                    'gcal_url' => $gcalUrl,
                    'classroom_url' => route('classroom.show', $lesson),
                ];
            })->values();

            $days[] = [
                'date' => $dayDate,
                'date_string' => $dayKey,
                'day_name' => $russianDayNames[$dayDate->dayOfWeekIso],
                'day_number' => $dayDate->format('j'),
                'is_today' => $isToday,
                'lessons' => $dayLessons,
            ];
        }

        return $days;
    }

    public function getCurrentTimePositionProperty(): ?int
    {
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $now = Carbon::now($displayTz);

        $startOfWeek = Carbon::parse($this->currentWeekStart, $displayTz)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        if (! $now->between($startOfWeek, $endOfWeek)) {
            return null;
        }

        $gridStartHour = 7;
        $gridEndHour = 23;
        $totalMinutes = ($gridEndHour - $gridStartHour) * 60;

        $currentMin = ($now->hour - $gridStartHour) * 60 + $now->minute;

        if ($currentMin < 0 || $currentMin > $totalMinutes) {
            return null;
        }

        return (int) $currentMin;
    }

    public function getSelectedLessonProperty(): ?Lesson
    {
        if (! $this->selectedCalendarLessonId) {
            return null;
        }

        return Lesson::query()
            ->with(['tutor', 'student', 'parent', 'review'])
            ->find($this->selectedCalendarLessonId);
    }

    public function getTabs(): array
    {
        return [
            'upcoming' => Tab::make('Предстоящие')
                ->icon('heroicon-m-calendar-days')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('end_time', '>=', now('UTC'))
                    ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])),
            'completed' => Tab::make('Завершённые')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', Lesson::STATUS_COMPLETED)),
            'all' => Tab::make('Все уроки')
                ->icon('heroicon-m-queue-list'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'upcoming';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LessonOverviewWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        return [
            Actions\Action::make('create_lesson')
                ->label('Запланировать урок')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->visible(fn (): bool => $user?->isTutor() ?? false)
                ->modalHeading('Запланировать новый урок')
                ->modalDescription('Добавьте урок в расписание для своего ученика. Оплата производится напрямую репетитору.')
                ->modalSubmitActionLabel('Создать урок')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\Select::make('student_mode')
                        ->label('Ученик')
                        ->options([
                            'existing' => 'Выбрать из базы учеников',
                            'new' => '+ Добавить нового ученика',
                        ])
                        ->default('existing')
                        ->live(),
                    Forms\Components\Select::make('student_id')
                        ->label('Выберите ученика')
                        ->options(function () {
                            return User::query()
                                ->where('role', UserRole::Student)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => ($get('student_mode') ?? 'existing') === 'existing')
                        ->required(fn (Forms\Get $get) => ($get('student_mode') ?? 'existing') === 'existing'),
                    Forms\Components\TextInput::make('new_student_name')
                        ->label('Имя ученика')
                        ->placeholder('Например, Максим')
                        ->visible(fn (Forms\Get $get) => $get('student_mode') === 'new')
                        ->required(fn (Forms\Get $get) => $get('student_mode') === 'new'),
                    Forms\Components\TextInput::make('new_student_phone')
                        ->label('Телефон ученика')
                        ->placeholder('+37529XXXXXXX')
                        ->tel()
                        ->visible(fn (Forms\Get $get) => $get('student_mode') === 'new'),
                    Forms\Components\TextInput::make('subject')
                        ->label('Предмет')
                        ->default(fn () => auth()->user()?->tutorProfile?->subjects[0] ?? 'Математика')
                        ->required(),
                    Forms\Components\DateTimePicker::make('start_time')
                        ->label('Дата и время начала')
                        ->default(now()->addHour()->startOfHour())
                        ->native(false)
                        ->required(),
                    Forms\Components\Select::make('duration_minutes')
                        ->label('Длительность')
                        ->options([
                            45 => '45 минут',
                            60 => '60 минут (1 час)',
                            90 => '90 минут (1.5 часа)',
                            120 => '120 минут (2 часа)',
                        ])
                        ->default(60)
                        ->required(),
                    Forms\Components\TextInput::make('price')
                        ->label('Стоимость урока (BYN)')
                        ->numeric()
                        ->default(fn () => (float) (auth()->user()?->tutorProfile?->price_per_hour ?? 35.00))
                        ->prefix('BYN')
                        ->required(),
                    Forms\Components\Select::make('payment_status')
                        ->label('Статус оплаты')
                        ->options([
                            Lesson::PAYMENT_UNPAID => 'Ожидает оплаты напрямую',
                            Lesson::PAYMENT_PAID => 'Оплачено напрямую репетитору',
                        ])
                        ->default(Lesson::PAYMENT_UNPAID)
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Заметки к уроку (тема, ссылка, ДЗ)')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $user = auth()->user();
                    $studentId = null;

                    if (($data['student_mode'] ?? 'existing') === 'new') {
                        $phone = trim($data['new_student_phone'] ?? '');
                        $name = trim($data['new_student_name'] ?? 'Ученик');
                        $email = 'student_'.time().'_'.rand(100, 999).'@edusfera.local';

                        $student = User::create([
                            'name' => $name,
                            'phone' => $phone ?: '+37529'.rand(1000000, 9999999),
                            'email' => $email,
                            'password' => bcrypt(str()->random(16)),
                            'role' => UserRole::Student,
                        ]);
                        $studentId = $student->id;
                    } else {
                        $studentId = (int) $data['student_id'];
                    }

                    $start = Carbon::parse($data['start_time']);
                    $duration = (int) $data['duration_minutes'];
                    $end = $start->copy()->addMinutes($duration);

                    $subject = trim((string) ($data['subject'] ?? ''));
                    $notes = trim((string) ($data['notes'] ?? ''));
                    $combinedNotes = $subject !== ''
                        ? ("Предмет: {$subject}".($notes !== '' ? "\n{$notes}" : ''))
                        : ($notes !== '' ? $notes : null);

                    Lesson::create([
                        'tutor_id' => $user->id,
                        'student_id' => $studentId,
                        'start_time' => $start->utc(),
                        'end_time' => $end->utc(),
                        'duration_minutes' => $duration,
                        'price' => $data['price'],
                        'platform_commission' => 0.00,
                        'net_amount' => $data['price'],
                        'status' => Lesson::STATUS_CONFIRMED,
                        'payment_status' => $data['payment_status'],
                        'package_code' => 'single',
                        'package_lessons' => 1,
                        'notes' => $combinedNotes,
                    ]);

                    Notification::make()
                        ->title('Урок запланирован!')
                        ->body("Урок добавлен на {$start->format('d.m.Y H:i')}. Ссылка на класс доступна в расписании.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('book_lesson')
                ->label('Найти репетитора')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->url('/tutors')
                ->visible(fn (): bool => $user !== null && ($user->isStudent() || $user->isParent())),
            Actions\Action::make('availability')
                ->label('Рабочие часы')
                ->icon('heroicon-o-clock')
                ->url(fn (): string => route('filament.admin.pages.tutor-availability-page'))
                ->visible(fn (): bool => $user?->isTutor() ?? false),
            Actions\Action::make('npd')
                ->label('Налог НПД (МНС)')
                ->icon('heroicon-o-document-currency-dollar')
                ->url('/admin/tutor-npd')
                ->visible(fn (): bool => $user?->isTutor() ?? false),
            Actions\Action::make('diagnostic')
                ->label('Диагностика')
                ->icon('heroicon-o-sparkles')
                ->url('/admin/diagnostic')
                ->visible(fn (): bool => $user !== null && ($user->isStudent() || $user->isParent())),
        ];
    }
}
