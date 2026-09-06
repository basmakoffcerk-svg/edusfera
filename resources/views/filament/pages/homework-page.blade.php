<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section icon="heroicon-o-document-check">
            <x-slot name="heading">
                Домашние задания между уроками
            </x-slot>

            <x-slot name="description">
                Задания от преподавателей. Выполняйте их для фиксации прогресса
            </x-slot>

            <x-slot name="headerEnd">
                <div class="flex items-center gap-2">
                    <x-filament::badge color="warning">
                        Активно: {{ $assignedCount }}
                    </x-filament::badge>
                    <x-filament::badge color="success">
                        Выполнено: {{ $completedCount }}
                    </x-filament::badge>
                </div>
            </x-slot>
        </x-filament::section>

        @if ($assignments->isEmpty())
            <x-filament::section icon="heroicon-o-document-magnifying-glass">
                <x-slot name="heading">
                    Пока нет активной домашки
                </x-slot>

                <x-slot name="description">
                    Домашние задания появляются после отчёта преподавателя по уроку. Когда преподаватель зафиксирует следующий шаг, вы увидите задание здесь.
                </x-slot>

                <div class="mt-4">
                    <x-filament::button href="/admin/lessons" tag="a" color="primary" size="md" icon="heroicon-o-academic-cap">
                        Открыть мои уроки
                    </x-filament::button>
                </div>
            </x-filament::section>
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
