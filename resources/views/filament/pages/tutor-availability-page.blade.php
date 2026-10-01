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

        <form wire:submit="save" class="grid gap-8 lg:grid-cols-12 items-start">
            <section class="lg:col-span-7 xl:col-span-7 2xl:col-span-8 rounded-3xl border border-gray-200/80 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
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

            <aside class="lg:col-span-5 xl:col-span-5 2xl:col-span-4 space-y-6">
                {{-- Apple-Style Live Showcase Card --}}
                <section class="rounded-3xl border border-gray-200/80 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 p-6 shadow-sm backdrop-blur-md transition-all">
                    {{-- Header with Status Pill --}}
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-800/80 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                    Витрина в каталоге
                                </p>
                            </div>
                            <h3 class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-white mt-1">
                                Слоты для записи
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Ближайшие 7 дней в режиме реального времени
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-50/70 dark:bg-emerald-950/40 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                            Онлайн
                        </span>
                    </div>

                    {{-- 7 Days List --}}
                    <div class="mt-4 space-y-3">
                        @foreach ($this->upcomingCalendar as $previewIndex => $preview)
                            <div 
                                wire:key="preview-card-{{ $previewIndex }}-{{ $preview['label'] }}"
                                x-data="{ showAll: false }" 
                                class="group rounded-2xl border transition-all duration-200 p-3.5 {{ $preview['is_active'] ? 'border-gray-200/90 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 hover:border-primary-500/40 hover:bg-white dark:hover:bg-gray-800/70' : 'border-dashed border-gray-200 dark:border-gray-800/60 bg-transparent opacity-60' }}"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-black text-gray-900 dark:text-white">
                                                    {{ $preview['label'] }}
                                                </span>
                                                @if($preview['is_today'])
                                                    <span class="rounded-md bg-primary-500/10 px-1.5 py-0.5 text-[10px] font-extrabold text-primary-600 dark:text-primary-400 uppercase tracking-wider">
                                                        Сегодня
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-[11px] font-medium text-gray-400 dark:text-gray-500 capitalize">
                                                {{ $preview['day_name'] }}
                                            </span>
                                        </div>
                                    </div>

                                    @if ($preview['is_active'] && $preview['slots_count'] > 0)
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-500/10 px-2 py-1 text-[11px] font-extrabold text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                            {{ $preview['slots_count'] }} {{ trans_choice('слот|слота|слотов', $preview['slots_count']) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-lg bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                            Выходной
                                        </span>
                                    @endif
                                </div>

                                @if ($preview['is_active'] && $preview['slots_count'] > 0)
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach (array_slice($preview['slots'], 0, 6) as $slot)
                                            <span class="inline-flex items-center rounded-lg border border-gray-200/80 dark:border-gray-700 bg-white dark:bg-gray-800 px-2.5 py-1 text-xs font-bold text-gray-800 dark:text-gray-200 shadow-2xs font-mono transition-transform hover:scale-105">
                                                {{ $slot }}
                                            </span>
                                        @endforeach

                                        @if ($preview['slots_count'] > 6)
                                            <template x-if="showAll">
                                                <div class="contents">
                                                    @foreach (array_slice($preview['slots'], 6) as $slot)
                                                        <span class="inline-flex items-center rounded-lg border border-gray-200/80 dark:border-gray-700 bg-white dark:bg-gray-800 px-2.5 py-1 text-xs font-bold text-gray-800 dark:text-gray-200 shadow-2xs font-mono transition-transform hover:scale-105">
                                                            {{ $slot }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </template>

                                            <button 
                                                type="button" 
                                                @click="showAll = !showAll" 
                                                class="inline-flex items-center gap-1 rounded-lg border border-primary-500/30 bg-primary-50/50 dark:bg-primary-950/40 px-2.5 py-1 text-xs font-extrabold text-primary-600 dark:text-primary-400 transition hover:bg-primary-100 dark:hover:bg-primary-900/60 cursor-pointer"
                                            >
                                                <span x-text="showAll ? 'Свернуть' : '+{{ $preview['slots_count'] - 6 }} ещё'"></span>
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <p class="mt-2.5 text-[11px] font-medium text-gray-400 dark:text-gray-500 italic">
                                        Слоты скрыты. Включите день слева для открытия записи.
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Clean High-Contrast Instruction Box --}}
                <section class="rounded-3xl p-6 shadow-xl relative overflow-hidden" style="background: #0B0F19; border: 1px solid rgba(255, 255, 255, 0.12); color: #FFFFFF;">
                    <div class="absolute -right-10 -bottom-10 w-32 h-32 rounded-full blur-2xl pointer-events-none" style="background: rgba(16, 185, 129, 0.15);"></div>

                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-400" style="color: #34D399;">
                            Как работают слоты
                        </p>
                    </div>

                    <div class="mt-3.5 space-y-3 text-xs leading-relaxed" style="color: #CBD5E1;">
                        <div class="flex items-start gap-2.5">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold" style="background: rgba(255, 255, 255, 0.12); color: #FFFFFF;">1</span>
                            <p style="margin: 0;">Ученик выбирает любой свободный час прямо на вашей странице в каталоге.</p>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold" style="background: rgba(255, 255, 255, 0.12); color: #FFFFFF;">2</span>
                            <p style="margin: 0;">При бронировании слот <strong style="color: #FFFFFF;">мгновенно закрывается</strong> во избежание наложений.</p>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold" style="background: rgba(255, 255, 255, 0.12); color: #FFFFFF;">3</span>
                            <p style="margin: 0;">Анкета с активными слотами поднимается <strong style="color: #34D399;">на первые позиции</strong> в каталоге репетиторов.</p>
                        </div>
                    </div>
                </section>
            </aside>
        </form>
    </div>
</x-filament-panels::page>
