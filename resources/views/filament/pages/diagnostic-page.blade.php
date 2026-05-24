<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl space-y-3">
                    <span class="inline-flex min-h-9 items-center rounded-full bg-violet-100 px-4 text-xs font-black uppercase tracking-[0.28em] text-violet-800">
                        Baseline
                    </span>
                    <div class="space-y-2">
                        <h2 class="text-3xl font-black tracking-[-0.05em] text-stone-950">Зафиксируйте стартовый уровень перед подготовкой</h2>
                        <p class="max-w-2xl text-sm leading-7 text-stone-600">
                            Эта форма создаёт первую диагностику, обновляет учебную цель и формирует стартовый progress snapshot.
                            Дальше по этим данным можно будет строить траекторию, домашние задания и отчёты по прогрессу.
                        </p>
                    </div>
                </div>

                <a href="/admin" class="inline-flex min-h-11 items-center rounded-2xl border border-stone-200 px-4 text-sm font-bold text-stone-800 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                    Вернуться в кабинет
                </a>
            </div>
        </section>

        @if ($goals->isEmpty())
            <section class="rounded-[2rem] border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <h3 class="text-xl font-black tracking-[-0.04em] text-stone-950">Пока нет активной учебной цели</h3>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-stone-700">
                    Диагностика становится доступной после первой оплаченной записи к репетитору. Оплатите занятие, и платформа автоматически создаст цель подготовки и стартовый exam track.
                </p>
                <a href="/tutors" class="mt-4 inline-flex min-h-11 items-center rounded-2xl bg-violet-600 px-5 text-sm font-black text-white transition hover:bg-violet-700">
                    Подобрать репетитора
                </a>
            </section>
        @else
            <form wire:submit="save" class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_22rem]">
                <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="space-y-2 md:col-span-2">
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-stone-500">Цель подготовки</span>
                            <select wire:model.live="selectedGoalId" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100">
                                @foreach ($goals as $goal)
                                    <option value="{{ $goal->id }}">{{ $goal->subject }} · {{ $goal->exam_type }} · преподаватель #{{ $goal->tutor_id }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-stone-500">Тип экзамена</span>
                            <select wire:model="examType" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100">
                                <option value="ЦЭ">ЦЭ</option>
                                <option value="ЦТ">ЦТ</option>
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-stone-500">Дата экзамена</span>
                            <input type="date" wire:model="examDate" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100">
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-stone-500">Текущий ориентир</span>
                            <input type="number" min="0" max="100" wire:model="currentScore" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100" placeholder="Например, 42">
                            @error('currentScore')<span class="text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-stone-500">Целевой балл</span>
                            <input type="number" min="0" max="100" wire:model="targetScore" class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100" placeholder="Например, 75">
                            @error('targetScore')<span class="text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <div class="mt-6">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-stone-500">Слабые зоны</p>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            @foreach ($topicOptions as $topic)
                                <label class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-stone-50/60 px-4 py-3 text-sm font-semibold text-stone-800 transition hover:border-violet-300 hover:bg-violet-50">
                                    <input type="checkbox" value="{{ $topic }}" wire:model="weakTopics" class="mt-0.5 h-4 w-4 rounded border-stone-300 text-violet-600 focus:ring-violet-500">
                                    <span>{{ $topic }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('weakTopics')<span class="mt-2 block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                    </div>

                    <label class="mt-6 block space-y-2">
                        <span class="text-xs font-black uppercase tracking-[0.2em] text-stone-500">Комментарий</span>
                        <textarea wire:model="notes" rows="5" class="w-full rounded-[1.4rem] border border-stone-200 bg-white px-4 py-3 text-sm font-medium text-stone-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100" placeholder="Например: сложно держать темп, путаюсь в синтаксисе, не хватает уверенности в тестовых формулировках."></textarea>
                    </label>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <x-filament::button type="submit" size="lg">
                            Сохранить диагностику
                        </x-filament::button>
                        <a href="/admin/messages" class="inline-flex min-h-12 items-center rounded-2xl border border-stone-200 px-5 text-sm font-bold text-stone-700 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                            Обсудить с преподавателем
                        </a>
                    </div>
                </section>

                <aside class="space-y-6">
                    <section class="rounded-[2rem] border border-stone-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-stone-500">Выбранная цель</p>
                        @if ($selectedGoal)
                            <div class="mt-3 rounded-[1.4rem] border border-violet-200 bg-violet-50 p-4">
                                <p class="text-lg font-black tracking-[-0.04em] text-stone-950">{{ $selectedGoal->subject }}</p>
                                <p class="mt-1 text-sm font-semibold text-violet-700">{{ $selectedGoal->exam_type }} · статус {{ $selectedGoal->status }}</p>
                                <p class="mt-3 text-sm leading-7 text-stone-700">
                                    @if ($selectedGoal->latest_diagnostic_at)
                                        Последняя диагностика: {{ $selectedGoal->latest_diagnostic_at->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i') }}
                                    @else
                                        Диагностика ещё не заполнена. Это ваш baseline для дальнейшего сравнения.
                                    @endif
                                </p>
                            </div>
                        @endif
                    </section>

                    <section class="rounded-[2rem] border border-stone-200 bg-stone-950 p-5 text-white shadow-sm">
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-lime-300">Что будет после сохранения</p>
                        <div class="mt-3 space-y-3 text-sm leading-7 text-white/78">
                            <p>1. Текущий и целевой балл сохранятся в учебной цели.</p>
                            <p>2. Появится первая запись в `diagnostic_attempts`.</p>
                            <p>3. Выбранные слабые темы запишутся в `skill_gaps`.</p>
                            <p>4. Платформа сформирует стартовый progress snapshot.</p>
                        </div>
                    </section>
                </aside>
            </form>
        @endif
    </div>
</x-filament-panels::page>
