<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl space-y-3">
                    <span class="inline-flex min-h-9 items-center rounded-full bg-lime-100 px-4 text-xs font-black uppercase tracking-[0.28em] text-lime-800">
                        Календарь записи
                    </span>
                    <div class="space-y-2">
                        <h2 class="text-3xl font-black tracking-[-0.05em] text-stone-950">Откройте реальные окна для бронирования</h2>
                        <p class="max-w-2xl text-sm leading-7 text-stone-600">
                            Ученики видят только те часы, которые вы откроете здесь. Включите день, задайте время начала и окончания,
                            затем сохраните календарь. Слоты на ближайшую неделю появятся в каталоге и в карточке репетитора.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="applyPreset('weekdays')"
                        class="inline-flex min-h-11 items-center rounded-2xl border border-stone-200 px-4 text-sm font-bold text-stone-800 transition hover:border-lime-400 hover:bg-lime-50"
                    >
                        Пн-Пт 10:00-18:00
                    </button>
                    <button
                        type="button"
                        wire:click="applyPreset('evenings')"
                        class="inline-flex min-h-11 items-center rounded-2xl border border-stone-200 px-4 text-sm font-bold text-stone-800 transition hover:border-lime-400 hover:bg-lime-50"
                    >
                        Вечерние окна
                    </button>
                    <button
                        type="button"
                        wire:click="applyPreset('everyday')"
                        class="inline-flex min-h-11 items-center rounded-2xl border border-stone-200 px-4 text-sm font-bold text-stone-800 transition hover:border-lime-400 hover:bg-lime-50"
                    >
                        Каждый день
                    </button>
                    <button
                        type="button"
                        wire:click="clearCalendar"
                        class="inline-flex min-h-11 items-center rounded-2xl border border-stone-200 px-4 text-sm font-bold text-stone-600 transition hover:border-stone-400 hover:bg-stone-50"
                    >
                        Очистить
                    </button>
                </div>
            </div>
        </section>

        @error('availability')
            <section class="rounded-[1.6rem] border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-700 shadow-sm">
                {{ $message }}
            </section>
        @enderror

        <form wire:submit="save" class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_22rem]">
            <section class="rounded-[2rem] border border-stone-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($availability as $index => $day)
                        <article class="rounded-[1.6rem] border {{ $day['is_active'] ? 'border-lime-300 bg-lime-50/40' : 'border-stone-200 bg-stone-50/70' }} p-4 transition">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <h3 class="text-lg font-black tracking-[-0.04em] text-stone-950">{{ $day['day_label'] }}</h3>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] {{ $day['is_active'] ? 'text-lime-700' : 'text-stone-400' }}">
                                        {{ $day['is_active'] ? 'Открыт для записи' : 'Выходной / скрыт' }}
                                    </p>
                                </div>

                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-stone-200 bg-white px-3 py-2 text-xs font-bold text-stone-700">
                                    <input
                                        type="checkbox"
                                        wire:model.live="availability.{{ $index }}.is_active"
                                        class="h-4 w-4 rounded border-stone-300 text-lime-500 focus:ring-lime-500"
                                    >
                                    Активен
                                </label>
                            </div>

                            <input type="hidden" wire:model="availability.{{ $index }}.day_of_week">
                            <input type="hidden" wire:model="availability.{{ $index }}.day_label">

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <label class="space-y-2">
                                    <span class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Начало</span>
                                    <input
                                        type="time"
                                        wire:model.live="availability.{{ $index }}.start_time"
                                        class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-900 outline-none transition focus:border-lime-400 focus:ring-2 focus:ring-lime-100"
                                    >
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-black uppercase tracking-[0.18em] text-stone-500">Конец</span>
                                    <input
                                        type="time"
                                        wire:model.live="availability.{{ $index }}.end_time"
                                        class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-900 outline-none transition focus:border-lime-400 focus:ring-2 focus:ring-lime-100"
                                    >
                                </label>
                            </div>

                            <div class="mt-4 rounded-[1.2rem] border border-stone-200 bg-white/80 px-4 py-3">
                                @if ($day['is_active'])
                                    <p class="text-sm font-semibold text-stone-900">
                                        Ученики смогут бронировать с <span class="text-lime-700">{{ $day['start_time'] }}</span> до <span class="text-lime-700">{{ $day['end_time'] }}</span>.
                                    </p>
                                @else
                                    <p class="text-sm font-semibold text-stone-500">День скрыт из каталога и не показывает слоты.</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <x-filament::button type="submit" size="lg" color="success">
                        Сохранить календарь
                    </x-filament::button>
                    <a href="/admin/lessons" class="inline-flex min-h-12 items-center rounded-2xl border border-stone-200 px-5 text-sm font-bold text-stone-800 transition hover:border-lime-400 hover:bg-lime-50">
                        Перейти в моё расписание
                    </a>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-[2rem] border border-stone-200 bg-white p-5 shadow-sm">
                    <div class="space-y-2">
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-stone-500">Превью на 7 дней</p>
                        <h3 class="text-2xl font-black tracking-[-0.05em] text-stone-950">Как ученики увидят ваши окна</h3>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach ($this->upcomingCalendar as $preview)
                            <div class="rounded-[1.3rem] border border-stone-200 bg-stone-50/80 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-stone-950">{{ $preview['label'] }}</p>
                                        <p class="text-xs text-stone-500">{{ $preview['full_label'] }}</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-[0.18em] {{ $preview['is_active'] ? 'bg-lime-100 text-lime-800' : 'bg-stone-200 text-stone-500' }}">
                                        {{ $preview['is_active'] ? 'Открыт' : 'Закрыт' }}
                                    </span>
                                </div>

                                @if ($preview['is_active'] && count($preview['slots']) > 0)
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($preview['slots'] as $slot)
                                            <span class="inline-flex min-h-9 items-center rounded-full border border-lime-200 bg-white px-3 text-sm font-bold text-stone-900">
                                                {{ $slot }}
                                            </span>
                                        @endforeach
                                        @if ($preview['extra_slots'] > 0)
                                            <span class="inline-flex min-h-9 items-center rounded-full border border-stone-200 bg-white px-3 text-sm font-bold text-stone-500">
                                                +{{ $preview['extra_slots'] }} еще
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <p class="mt-3 text-sm font-semibold text-stone-500">Свободные слоты не показываются.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-[2rem] border border-stone-200 bg-stone-950 p-5 text-white shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-lime-300">Логика бронирования</p>
                    <div class="mt-3 space-y-3 text-sm leading-7 text-white/78">
                        <p>1. Откройте день и задайте рабочий диапазон.</p>
                        <p>2. Слоты появляются в каталоге автоматически с шагом в 60 минут.</p>
                        <p>3. После бронирования слот блокируется на оплату и пропадает из выдачи.</p>
                    </div>
                </section>
            </aside>
        </form>
    </div>
</x-filament-panels::page>
