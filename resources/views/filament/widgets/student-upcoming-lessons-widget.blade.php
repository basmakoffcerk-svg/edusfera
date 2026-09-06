<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-calendar">
        <x-slot name="heading">
            Ближайшие уроки
        </x-slot>

        <x-slot name="description">
            Запланированные занятия в онлайн-классе Edusfera
        </x-slot>

        <x-slot name="headerEnd">
            @if($upcomingLessons->isNotEmpty())
                <x-filament::badge color="primary">
                    След. {{ $upcomingLessons->first()->start_time->format('d.m, H:i') }}
                </x-filament::badge>
            @endif
        </x-slot>

        @if ($upcomingLessons->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 p-6 text-center space-y-3">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Пока нет запланированных уроков</p>
                <p class="text-xs text-gray-500 max-w-sm mx-auto">Выберите репетитора и забронируйте удобное время для занятий.</p>
                <x-filament::button href="/tutors" tag="a" color="primary" size="sm" icon="heroicon-o-magnifying-glass">
                    Найти репетитора
                </x-filament::button>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($upcomingLessons as $lesson)
                    @php
                        $tutor = $lesson->tutor;
                        $profile = $tutor?->tutorProfile;
                    @endphp

                    <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-700 font-bold">
                                {{ mb_substr($tutor?->name ?? 'Р', 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $tutor?->name ?? 'Преподаватель' }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $lesson->start_time->format('d.m.Y, H:i') }} · {{ $lesson->subject ?? 'Занятие' }}
                                </p>
                            </div>
                        </div>

                        <x-filament::button href="/admin/lessons" tag="a" color="primary" size="xs">
                            Войти в класс
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
