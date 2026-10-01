<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    @php
        $days = $this->calendarWeekDays;
        $currentTimePos = $this->currentTimePosition;
        $selectedLesson = $this->selectedLesson;
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $weekStartCarbon = \Carbon\Carbon::parse($this->currentWeekStart, $displayTz);
        $monthYearLabel = $weekStartCarbon->translatedFormat('F Y');
    @endphp

    <div class="space-y-6">
        {{-- Calendar Top Control Bar --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            {{-- Navigation and Date title --}}
            <div class="flex items-center gap-3">
                <button type="button"
                        wire:click="goToToday"
                        class="rounded-xl border border-slate-300 dark:border-slate-700 px-4 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition shadow-sm">
                    Сегодня
                </button>

                <div class="flex items-center rounded-xl border border-slate-300 dark:border-slate-700 overflow-hidden">
                    <button type="button"
                            wire:click="previousWeek"
                            title="Предыдущая неделя"
                            class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div class="h-4 w-px bg-slate-200 dark:bg-slate-800"></div>
                    <button type="button"
                            wire:click="nextWeek"
                            title="Следующая неделя"
                            class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <h2 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white capitalize ml-2">
                    {{ $monthYearLabel }}
                </h2>
            </div>

            {{-- Controls: View Mode & Google Calendar Sync --}}
            <div class="flex flex-wrap items-center gap-2.5">
                {{-- View Mode Switcher --}}
                <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-950 p-1 border border-slate-200 dark:border-slate-800">
                    <button type="button"
                            wire:click="setViewMode('calendar')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition {{ $this->viewMode === 'calendar' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Календарь</span>
                    </button>
                    <button type="button"
                            wire:click="setViewMode('table')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition {{ $this->viewMode === 'table' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        <span>Таблица</span>
                    </button>
                </div>

                {{-- Google Calendar Sync Button --}}
                <button type="button"
                        wire:click="exportIcs"
                        title="Экспортировать расписание для Google Calendar и Apple Calendar"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 px-3.5 py-2 text-xs font-semibold transition shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                    </svg>
                    <span>Синхронизация (.ics)</span>
                </button>
            </div>
        </div>

        {{-- MAIN CONTENT AREA --}}
        @if ($this->viewMode === 'calendar')
            {{-- Google Calendar 7-Day Week Grid View --}}
            <div class="rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden select-none">
                {{-- Calendar Column Headers --}}
                <div style="display: grid; grid-template-columns: 60px repeat(7, minmax(0, 1fr));" class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/60">
                    {{-- Timezone Corner Cell --}}
                    <div class="flex items-center justify-center border-r border-slate-200 dark:border-slate-800 p-2 text-[10px] font-mono font-medium text-slate-400">
                        GMT+3
                    </div>

                    {{-- 7 Day Headers (Mon - Sun) --}}
                    @foreach ($days as $day)
                        <div class="flex flex-col items-center justify-center py-3 border-r border-slate-200/70 dark:border-slate-800/80 last:border-r-0 {{ $day['is_today'] ? 'bg-sky-50/60 dark:bg-sky-950/30' : '' }}">
                            <span class="text-[11px] font-bold uppercase tracking-wider {{ $day['is_today'] ? 'text-sky-600 dark:text-sky-400' : 'text-slate-500 dark:text-slate-400' }}">
                                {{ $day['day_name'] }}
                            </span>
                            <div class="mt-1 flex items-center justify-center">
                                @if ($day['is_today'])
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-sky-600 text-white font-extrabold text-sm shadow-md">
                                        {{ $day['day_number'] }}
                                    </span>
                                @else
                                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $day['day_number'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Scrollable 16-Hour Grid Canvas (07:00 to 23:00 = 960px) --}}
                <div class="relative overflow-x-auto">
                    <div style="display: grid; grid-template-columns: 60px repeat(7, minmax(0, 1fr)); height: 960px; min-width: 760px; position: relative;">
                        {{-- Hours Axis (07:00 to 22:00) --}}
                        <div style="height: 960px; position: relative;" class="border-r border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-950/20">
                            @for ($hour = 7; $hour <= 22; $hour++)
                                <div class="absolute w-full text-right pr-2 -translate-y-2 text-[11px] font-mono text-slate-400 select-none"
                                     style="top: {{ ($hour - 7) * 60 }}px;">
                                    {{ sprintf('%02d:00', $hour) }}
                                </div>
                            @endfor
                        </div>

                        {{-- 7 Day Columns --}}
                        @foreach ($days as $dayIndex => $day)
                            <div style="height: 960px; position: relative;" class="border-r border-slate-200/70 dark:border-slate-800/80 last:border-r-0 {{ $day['is_today'] ? 'bg-sky-50/20 dark:bg-sky-950/10' : '' }}">
                                {{-- Horizontal Hour Guidelines --}}
                                @for ($hour = 7; $hour <= 22; $hour++)
                                    <div class="absolute left-0 right-0 border-t border-slate-100 dark:border-slate-800/60 pointer-events-none"
                                         style="top: {{ ($hour - 7) * 60 }}px;"></div>
                                @endfor

                                {{-- Red Current Time Line if Today --}}
                                @if ($day['is_today'] && $currentTimePos !== null)
                                    <div class="absolute left-0 right-0 z-20 pointer-events-none flex items-center"
                                         style="top: {{ $currentTimePos }}px;">
                                        <div class="h-2.5 w-2.5 -ml-1 rounded-full bg-rose-600 shadow-sm"></div>
                                        <div class="h-[2px] w-full bg-rose-600 shadow-sm"></div>
                                    </div>
                                @endif

                                {{-- Lesson Event Cards in this day --}}
                                @foreach ($day['lessons'] as $lesson)
                                    @php
                                        $colorClasses = match ($lesson['status']) {
                                            'completed' => 'bg-emerald-600 hover:bg-emerald-700 text-white border-l-4 border-emerald-800',
                                            'confirmed' => 'bg-sky-600 hover:bg-sky-700 text-white border-l-4 border-sky-800',
                                            'pending' => 'bg-amber-600 hover:bg-amber-700 text-white border-l-4 border-amber-800',
                                            default => 'bg-slate-600 hover:bg-slate-700 text-white border-l-4 border-slate-800',
                                        };
                                    @endphp
                                    <div wire:click="openCalendarLesson({{ $lesson['id'] }})"
                                         class="absolute left-1 right-1 z-10 rounded-lg p-2.5 shadow-md cursor-pointer transition-all duration-150 hover:scale-[1.02] hover:z-30 overflow-hidden {{ $colorClasses }}"
                                         style="top: {{ $lesson['top_px'] }}px; height: {{ $lesson['height_px'] }}px; min-height: 38px;"
                                         title="{{ $lesson['title'] }} ({{ $lesson['time_span'] }})">
                                        <div class="flex items-center justify-between gap-1 leading-none">
                                            <span class="text-[11px] font-bold tracking-tight truncate">
                                                {{ $lesson['title'] }}
                                            </span>
                                            <span class="text-[10px] font-mono opacity-90 shrink-0">
                                                {{ $lesson['start_formatted'] }}
                                            </span>
                                        </div>

                                        <div class="mt-1 flex items-center justify-between gap-1 text-[10px] opacity-90">
                                            <span class="font-medium truncate">{{ $lesson['time_span'] }}</span>
                                            @if ($lesson['payment_status'] === 'paid')
                                                <span class="rounded bg-white/20 px-1 py-0.5 text-[9px] font-bold uppercase">Оплачен</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Footer Legend --}}
                <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/30 text-xs">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-sky-600"></span>
                            <span class="text-slate-600 dark:text-slate-400">Подтверждён</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-emerald-600"></span>
                            <span class="text-slate-600 dark:text-slate-400">Завершён</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded-full bg-amber-600"></span>
                            <span class="text-slate-600 dark:text-slate-400">Ожидает</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-6 rounded-sm bg-rose-600"></span>
                            <span class="text-slate-600 dark:text-slate-400">Текущее время</span>
                        </div>
                    </div>

                    <span class="text-slate-400">Нажмите на любой урок для подробностей и входа в онлайн-класс</span>
                </div>
            </div>
        @else
            {{-- Standard Filament Table View with Tabs --}}
            <div class="flex flex-col gap-y-6">
                <x-filament-panels::resources.tabs />

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

                {{ $this->table }}

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
            </div>
        @endif

        {{-- Lesson Detail Modal / Flyout --}}
        @if ($selectedLesson)
            @php
                $modalStart = $selectedLesson->start_time?->setTimezone($displayTz)->format('d.m.Y H:i') ?? '—';
                $modalEnd = $selectedLesson->end_time?->setTimezone($displayTz)->format('H:i') ?? '—';
                $gcalLink = 'https://calendar.google.com/calendar/render?' . http_build_query([
                    'action' => 'TEMPLATE',
                    'text' => 'Урок: ' . ($selectedLesson->student?->name ?? 'Ученик'),
                    'dates' => $selectedLesson->start_time->setTimezone('UTC')->format('Ymd\THis\Z') . '/' . $selectedLesson->end_time->setTimezone('UTC')->format('Ymd\THis\Z'),
                    'details' => 'Edusfera онлайн-урок. Ссылка: ' . route('classroom.show', $selectedLesson),
                    'location' => route('classroom.show', $selectedLesson),
                ]);
            @endphp

            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-2xl space-y-5">
                    {{-- Modal Header --}}
                    <div class="flex items-start justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-md bg-sky-50 dark:bg-sky-950/80 px-2 py-0.5 text-xs font-bold text-sky-700 dark:text-sky-300">
                                    {{ $selectedLesson->package_label }}
                                </span>
                                <span class="text-xs text-slate-400 font-mono">#{{ $selectedLesson->id }}</span>
                            </div>
                            <h3 class="mt-2 text-xl font-bold text-slate-900 dark:text-white">
                                {{ $selectedLesson->student?->name ?? 'Ученик' }}
                            </h3>
                        </div>

                        <button type="button"
                                wire:click="closeCalendarLesson"
                                class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Details --}}
                    <div class="space-y-3 text-xs bg-slate-50 dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Время:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $modalStart }} – {{ $modalEnd }} ({{ $selectedLesson->duration_minutes }} мин)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Статус:</span>
                            <span class="font-semibold text-sky-600 dark:text-sky-400">{{ \App\Filament\Resources\LessonResource::statusLabel($selectedLesson->status) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Оплата:</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ \App\Filament\Resources\LessonResource::paymentLabel($selectedLesson->payment_status) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Стоимость:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ number_format((float) $selectedLesson->price, 2, '.', ' ') }} BYN</span>
                        </div>
                        @if ($selectedLesson->parent?->name)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Родитель:</span>
                                <span class="text-slate-900 dark:text-white">{{ $selectedLesson->parent->name }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        @if ($selectedLesson->payment_status !== 'paid' && (auth()->user()?->isTutor() ?? false))
                            <button type="button"
                                    wire:click="markLessonPaidDirectly({{ $selectedLesson->id }})"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-2.5 px-4 text-xs shadow-md transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Отметить оплату напрямую</span>
                            </button>
                        @endif

                        @if ($selectedLesson->status !== 'cancelled')
                            @php
                                $classUrl = (! config('classroom.enabled', false) && ! empty($selectedLesson->meeting_link) && str_starts_with($selectedLesson->meeting_link, 'http'))
                                    ? $selectedLesson->meeting_link
                                    : route('classroom.show', $selectedLesson);
                                $isExternal = (! config('classroom.enabled', false) && ! empty($selectedLesson->meeting_link) && str_starts_with($selectedLesson->meeting_link, 'http'));
                            @endphp
                            <a href="{{ $classUrl }}"
                               target="_blank" rel="noopener noreferrer"
                               class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-4 text-xs shadow-md transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>{{ $isExternal ? 'Подключиться к уроку' : 'Войти в класс' }}</span>
                            </a>
                        @endif

                        <a href="{{ $gcalLink }}" target="_blank" rel="noopener noreferrer"
                           class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 py-2.5 px-4 text-xs font-semibold text-slate-800 dark:text-slate-200 transition">
                            <svg class="w-4 h-4 text-sky-500" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                            </svg>
                            <span>В Google Calendar</span>
                        </a>

                        <a href="{{ route('filament.admin.resources.lessons.view', $selectedLesson) }}"
                           class="rounded-xl border border-slate-300 dark:border-slate-700 p-2.5 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition"
                           title="Все подробности урока">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
