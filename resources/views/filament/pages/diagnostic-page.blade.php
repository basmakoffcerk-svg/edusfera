<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section icon="heroicon-o-sparkles">
            <x-slot name="heading">
                Зафиксируйте стартовый уровень перед подготовкой
            </x-slot>

            <x-slot name="description">
                Эта форма создаёт первую диагностику, обновляет учебную цель и формирует стартовый progress snapshot.
            </x-slot>

            <x-slot name="headerEnd">
                <x-filament::button href="/admin" tag="a" color="gray" size="sm" icon="heroicon-o-arrow-left">
                    Вернуться в кабинет
                </x-filament::button>
            </x-slot>
        </x-filament::section>

        @if ($goals->isEmpty())
            <x-filament::section icon="heroicon-o-exclamation-triangle">
                <x-slot name="heading">
                    Пока нет активной учебной цели
                </x-slot>

                <x-slot name="description">
                    Диагностика становится доступной после первой оплаченной записи к репетитору. Оплатите занятие, и платформа автоматически создаст цель подготовки и стартовый exam track.
                </x-slot>

                <div class="mt-4">
                    <x-filament::button href="/tutors" tag="a" color="primary" size="md" icon="heroicon-o-magnifying-glass">
                        Подобрать репетитора
                    </x-filament::button>
                </div>
            </x-filament::section>
        @else
            <form wire:submit="save" class="space-y-6">
                <x-filament::section icon="heroicon-o-adjustments-horizontal">
                    <x-slot name="heading">
                        Параметры диагностики
                    </x-slot>

                    <x-slot name="description">
                        Укажите ориентировочный текущий результат и целевой балл
                    </x-slot>

                    <div class="grid gap-6 md:grid-cols-2">
                        <label class="space-y-2 md:col-span-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Цель подготовки</span>
                            <select wire:model.live="selectedGoalId" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-900 dark:text-white">
                                @foreach ($goals as $goal)
                                    <option value="{{ $goal->id }}">{{ $goal->subject }} · {{ $goal->exam_type }} · преподаватель #{{ $goal->tutor_id }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Тип экзамена</span>
                            <select wire:model="examType" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-900 dark:text-white">
                                <option value="ЦЭ">ЦЭ</option>
                                <option value="ЦТ">ЦТ</option>
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Дата экзамена</span>
                            <input type="date" wire:model="examDate" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-900 dark:text-white">
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Текущий ориентир (0-100)</span>
                            <input type="number" min="0" max="100" wire:model="currentScore" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-900 dark:text-white" placeholder="Например, 42">
                            @error('currentScore')<span class="text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Целевой балл (0-100)</span>
                            <input type="number" min="0" max="100" wire:model="targetScore" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-900 dark:text-white" placeholder="Например, 75">
                            @error('targetScore')<span class="text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-800 space-y-3">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Слабые темы в фокусе</span>
                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach ($topicOptions as $topic)
                                <label class="flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 p-3 text-xs font-semibold text-gray-900 dark:text-white cursor-pointer hover:bg-gray-100 transition">
                                    <input type="checkbox" value="{{ $topic }}" wire:model="weakTopics" class="h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                                    <span>{{ $topic }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-800 flex justify-end">
                        <x-filament::button type="submit" color="primary" icon="heroicon-o-check-circle">
                            Сохранить результаты диагностики
                        </x-filament::button>
                    </div>
                </x-filament::section>
            </form>
        @endif
    </div>
</x-filament-panels::page>
