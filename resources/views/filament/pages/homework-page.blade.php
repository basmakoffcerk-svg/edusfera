<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl space-y-3">
                    <span class="inline-flex min-h-9 items-center rounded-full bg-lime-100 px-4 text-xs font-black uppercase tracking-[0.28em] text-lime-800">
                        Practice loop
                    </span>
                    <div class="space-y-2">
                        <h2 class="text-3xl font-black tracking-[-0.05em] text-stone-950">Домашние задания между уроками</h2>
                        <p class="max-w-2xl text-sm leading-7 text-stone-600">
                            Здесь собраны задания, которые появились после отчётов преподавателя. Выполняйте их между уроками, чтобы платформа видела реальный темп подготовки, а преподаватель понимал, что уже закрыто.
                        </p>
                    </div>
                </div>

                <div class="grid min-w-[16rem] gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-2xl border border-lime-200 bg-lime-50 p-4">
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-lime-700">Активно</p>
                        <p class="mt-2 text-3xl font-black tracking-[-0.04em] text-stone-950">{{ $assignedCount }}</p>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-sky-700">Выполнено</p>
                        <p class="mt-2 text-3xl font-black tracking-[-0.04em] text-stone-950">{{ $completedCount }}</p>
                    </div>
                </div>
            </div>
        </section>

        @if ($assignments->isEmpty())
            <section class="rounded-[2rem] border border-stone-200 bg-white p-8 text-center shadow-sm">
                <h3 class="text-2xl font-black tracking-[-0.04em] text-stone-950">Пока нет активной домашки</h3>
                <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-stone-600">
                    Домашние задания появляются после отчёта преподавателя по уроку. Когда преподаватель зафиксирует следующий шаг, вы увидите задание здесь и сможете отметить его выполнение.
                </p>
                <a href="/admin/lessons" class="mt-5 inline-flex min-h-11 items-center rounded-2xl bg-violet-600 px-5 text-sm font-black text-white transition hover:bg-violet-700">
                    Открыть мои уроки
                </a>
            </section>
        @else
            <div class="grid gap-6 xl:grid-cols-[20rem_minmax(0,1fr)]">
                <aside class="space-y-3">
                    @foreach ($assignments as $assignment)
                        <button
                            type="button"
                            wire:click="selectAssignment({{ $assignment->id }})"
                            class="w-full rounded-[1.6rem] border p-4 text-left transition {{ $selectedAssignment && $selectedAssignment->id === $assignment->id ? 'border-violet-300 bg-violet-50 shadow-sm' : 'border-stone-200 bg-white hover:border-violet-200 hover:bg-violet-50/40' }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-stone-950">{{ $assignment->title }}</p>
                                    <p class="mt-1 text-xs font-semibold text-stone-500">
                                        {{ $assignment->studentGoal?->subject ?? 'Подготовка' }}
                                    </p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] {{ $assignment->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $assignment->status === 'completed' ? 'Готово' : 'Активно' }}
                                </span>
                            </div>

                            <div class="mt-3 space-y-1 text-xs text-stone-500">
                                @if ($assignment->assigned_at)
                                    <p>Выдано: {{ $assignment->assigned_at->setTimezone(config('booking.display_timezone'))->format('d.m H:i') }}</p>
                                @endif
                                @if ($assignment->due_at)
                                    <p>Сдать до: {{ $assignment->due_at->setTimezone(config('booking.display_timezone'))->format('d.m H:i') }}</p>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </aside>

                <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    @if ($selectedAssignment)
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-3xl space-y-2">
                                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-stone-500">Задание</p>
                                <h3 class="text-2xl font-black tracking-[-0.04em] text-stone-950">{{ $selectedAssignment->title }}</h3>
                                <p class="text-sm font-semibold text-violet-700">
                                    {{ $selectedAssignment->studentGoal?->subject ?? 'Подготовка' }}
                                    @if($selectedAssignment->lesson?->tutor?->name)
                                        · преподаватель {{ $selectedAssignment->lesson->tutor->name }}
                                    @endif
                                </p>
                            </div>

                            @if ($selectedAssignment->status !== 'completed')
                                <x-filament::button
                                    wire:click="completeAssignment({{ $selectedAssignment->id }})"
                                    color="success"
                                >
                                    Отметить выполненным
                                </x-filament::button>
                            @endif
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-3">
                            <div class="rounded-2xl border border-stone-200 bg-stone-50/70 p-4">
                                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-stone-500">Статус</p>
                                <p class="mt-2 text-base font-black text-stone-950">
                                    {{ $selectedAssignment->status === 'completed' ? 'Выполнено' : 'Ожидает выполнения' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-stone-50/70 p-4">
                                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-stone-500">Выдано</p>
                                <p class="mt-2 text-base font-black text-stone-950">
                                    {{ $selectedAssignment->assigned_at?->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i') ?? '—' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-stone-50/70 p-4">
                                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-stone-500">Дедлайн</p>
                                <p class="mt-2 text-base font-black text-stone-950">
                                    {{ $selectedAssignment->due_at?->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i') ?? 'Без дедлайна' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 rounded-[1.6rem] border border-violet-100 bg-violet-50/50 p-5">
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-violet-700">Инструкция</p>
                            <div class="mt-3 whitespace-pre-line text-sm leading-7 text-stone-700">
                                {{ $selectedAssignment->instructions ?: 'Инструкция не заполнена.' }}
                            </div>
                        </div>

                        @if (($selectedAssignment->payload['focus'] ?? null) || ($selectedAssignment->payload['next_step'] ?? null))
                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div class="rounded-[1.4rem] border border-amber-100 bg-amber-50/70 p-4">
                                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-amber-700">Фокус урока</p>
                                    <p class="mt-2 text-sm leading-7 text-stone-700">{{ $selectedAssignment->payload['focus'] ?? '—' }}</p>
                                </div>
                                <div class="rounded-[1.4rem] border border-sky-100 bg-sky-50/70 p-4">
                                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-sky-700">Следующий шаг</p>
                                    <p class="mt-2 text-sm leading-7 text-stone-700">{{ $selectedAssignment->payload['next_step'] ?? '—' }}</p>
                                </div>
                            </div>
                        @endif
                    @endif
                </section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
