<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section icon="heroicon-o-calendar">
            <x-slot name="heading">
                Откройте реальные окна для бронирования
            </x-slot>

            <x-slot name="description">
                Ученики видят только открытые слоты. Задайте рабочие часы на неделю и нажмите «Сохранить».
            </x-slot>

            <x-slot name="headerEnd">
                <div class="flex items-center gap-2 flex-wrap">
                    <x-filament::button type="button" wire:click="applyPreset('weekdays')" color="gray" size="xs">
                        Пн-Пт 10:00-18:00
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="applyPreset('evenings')" color="gray" size="xs">
                        Вечерние окна
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="applyPreset('everyday')" color="gray" size="xs">
                        Каждый день
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="resetAvailability" color="danger" size="xs">
                        Очистить всё
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::section>

        @error('availability')
            <div class="rounded-xl border border-danger-200 dark:border-danger-900 bg-danger-50 dark:bg-danger-950/40 p-4 text-sm font-semibold text-danger-700 dark:text-danger-400">
                {{ $message }}
            </div>
        @enderror

        <form wire:submit="save" class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_22rem]">
            <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 sm:p-6 shadow-xs">
                <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($availability as $index => $day)
                        @php $isActive = (bool) $day['is_active']; @endphp
                        <article class="rounded-xl border p-4 transition-all duration-200 {{ $isActive ? 'border-primary-500/80 bg-primary-50/40 dark:bg-primary-950/20 ring-1 ring-primary-500/20' : 'border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-900/60' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $day['day_label'] }}</h3>
                                    <p class="text-xs font-semibold uppercase tracking-wider {{ $isActive ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500' }}">
                                        {{ $isActive ? 'Открыт для записи' : 'Выходной' }}
                                    </p>
                                </div>

                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-300">
                                    <input
                                        type="checkbox"
                                        wire:model.live="availability.{{ $index }}.is_active"
                                        class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                    >
                                    Активен
                                </label>
                            </div>

                            <input type="hidden" wire:model="availability.{{ $index }}.day_of_week">
                            <input type="hidden" wire:model="availability.{{ $index }}.day_label">

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <label class="space-y-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Начало</span>
                                    <input
                                        type="time"
                                        wire:model.live="availability.{{ $index }}.start_time"
                                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                                    >
                                </label>
                                <label class="space-y-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Конец</span>
                                    <input
                                        type="time"
                                        wire:model.live="availability.{{ $index }}.end_time"
                                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                                    >
                                </label>
                            </div>

                            <div class="mt-4 rounded-lg border border-gray-200/80 dark:border-gray-800 bg-white/80 dark:bg-gray-800/80 px-3 py-2">
                                @if ($isActive)
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                        Слоты для записи с <strong class="text-primary-600 dark:text-primary-400">{{ $day['start_time'] }}</strong> до <strong class="text-primary-600 dark:text-primary-400">{{ $day['end_time'] }}</strong>.
                                    </p>
                                @else
                                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500">День скрыт из каталога репетиторов.</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <x-filament::button type="submit" size="lg" color="primary" icon="heroicon-m-check">
                        Сохранить календарь
                    </x-filament::button>
                    <x-filament::button href="/admin/lessons" tag="a" color="gray" size="lg">
                        Перейти в моё расписание
                    </x-filament::button>
                    <span wire:loading wire:target="save,applyPreset,resetAvailability" class="text-xs text-primary-600 dark:text-primary-400 font-medium">
                        Обновление слотов...
                    </span>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-xs">
                    <div class="space-y-1">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Расписание на 7 дней</p>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Слоты в каталоге</h3>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach ($this->upcomingCalendar as $preview)
                            <div class="rounded-xl border border-gray-200/80 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-900/60 p-3.5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $preview['label'] }}</p>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $preview['full_label'] }}</p>
                                    </div>
                                    <span class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $preview['is_active'] ? 'bg-primary-50 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-400' }}">
                                        {{ $preview['is_active'] ? 'Открыт' : 'Закрыт' }}
                                    </span>
                                </div>

                                @if ($preview['is_active'] && count($preview['slots']) > 0)
                                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                                        @foreach ($preview['slots'] as $slot)
                                            <span class="inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-2 py-1 text-xs font-bold text-gray-800 dark:text-gray-200 shadow-2xs">
                                                {{ $slot }}
                                            </span>
                                        @endforeach
                                        @if ($preview['extra_slots'] > 0)
                                            <span class="inline-flex items-center rounded-md border border-dashed border-gray-200 dark:border-gray-700 bg-transparent px-2 py-1 text-xs font-medium text-gray-400">
                                                +{{ $preview['extra_slots'] }} ещё
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <p class="mt-2 text-xs font-medium text-gray-400 dark:text-gray-500">Свободные слоты не показываются.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-900 dark:bg-gray-950 p-5 text-white shadow-xs">
                    <p class="text-xs font-bold uppercase tracking-wider text-primary-400">Как это работает</p>
                    <div class="mt-3 space-y-2 text-xs leading-relaxed text-gray-300">
                        <p>1. Откройте нужные дни и укажите удобный интервал времени.</p>
                        <p>2. Слоты формируются с интервалом в 60 минут.</p>
                        <p>3. При бронировании учеником слот автоматически закрепляется за ним.</p>
                    </div>
                </section>
            </aside>
        </form>
    </div>
</x-filament-panels::page>
