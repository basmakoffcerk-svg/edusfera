<x-filament-widgets::widget>
    @php
        $firstName = explode(' ', trim((string) $user->name))[0] ?? 'пользователь';
    @endphp

    <div class="space-y-6">
        {{-- Hero Header Section --}}
        <x-filament::section icon="heroicon-o-user">
            <x-slot name="heading">
                Рады видеть вас, {{ $firstName }} 👋
            </x-slot>

            <x-slot name="description">
                Контролируйте баланс, траектории подготовки к ЦТ/ЦЭ и расписание уроков в одном месте.
            </x-slot>

            <x-slot name="headerEnd">
                <div class="flex items-center gap-2 flex-wrap">
                    <x-filament::button href="/admin/diagnostic" tag="a" color="primary" size="sm" icon="heroicon-o-sparkles">
                        @if($diagnosticPendingCount > 0)
                            Пройти диагностику
                        @else
                            Обновить baseline
                        @endif
                    </x-filament::button>

                    <x-filament::button href="/admin/homework" tag="a" color="gray" size="sm" icon="heroicon-o-document-check">
                        Домашка
                        @if($activeHomeworkCount > 0)
                            <x-filament::badge color="primary" class="ml-1">
                                {{ $activeHomeworkCount }}
                            </x-filament::badge>
                        @endif
                    </x-filament::button>

                    <x-filament::button href="/admin/wallet" tag="a" color="success" size="sm" icon="heroicon-o-credit-card">
                        Пополнить баланс
                    </x-filament::button>

                    <x-filament::button href="/tutors" tag="a" color="gray" size="sm" icon="heroicon-o-magnifying-glass">
                        Поиск репетитора
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::section>

        {{-- 5 Key Metrics Grid --}}
        <x-filament::grid default="1" sm="2" lg="5" class="gap-4">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Траектории</span>
                <p class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">{{ $activeGoalsCount }}</p>
                <p class="text-[11px] text-gray-500">
                    @if($diagnosticPendingCount > 0)
                        {{ $diagnosticPendingCount }} ждут стартовой диагностики
                    @else
                        baseline заполнен
                    @endif
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Доступно</span>
                <p class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">{!! \App\Support\BynMoneyFormatter::format($availableBalance) !!}</p>
                <p class="text-[11px] text-gray-500">На кошельке</p>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-sky-600">Резерв</span>
                <p class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">{!! \App\Support\BynMoneyFormatter::format($heldForBookedLessons) !!}</p>
                <p class="text-[11px] text-gray-500">В безопасной сделке</p>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-600">В плане</span>
                <p class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">{{ $scheduledCount }}</p>
                <p class="text-[11px] text-gray-500">уроков забронировано</p>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-1">
                <span class="text-xs font-bold uppercase tracking-wider text-violet-600">Пройдено</span>
                <p class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">{{ $completedCount }}</p>
                <p class="text-[11px] text-gray-500">уроков проведено</p>
            </div>
        </x-filament::grid>

        {{-- Progress Section --}}
        <x-filament::section icon="heroicon-o-academic-cap">
            <x-slot name="heading">
                Учебный прогресс
            </x-slot>

            <x-slot name="description">
                @if($primaryGoal)
                    {{ $primaryGoal->subject }} · {{ $primaryGoal->exam_type }}
                @else
                    Траектория подготовки ещё не создана
                @endif
            </x-slot>

            @if($primaryGoal)
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <span>Балл: {{ $primaryGoal->baseline_score ?? 0 }} ➔ Цель: {{ $primaryGoal->target_score ?? 100 }}</span>
                        @if($latestSnapshot)
                            <span>Прогноз балла: {{ $latestSnapshot->predicted_score }} ({{ $progressPercent }}% готовности)</span>
                        @else
                            <span>Прогноз: {{ $progressPercent }}% готовности</span>
                        @endif
                    </div>

                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <div class="h-full rounded-full bg-primary-600 transition-all duration-500" style="width: {{ $progressPercent }}%;"></div>
                    </div>

                    @if($activeSkillGapsCount > 0)
                        <div class="text-xs text-amber-600 font-semibold pt-1">
                            Слабые темы в фокусе: {{ $activeSkillGapsCount }}
                        </div>
                    @endif
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 p-6 text-center space-y-3">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Траектория пока не создана</p>
                    <p class="text-xs text-gray-500 max-w-md mx-auto">
                        После первой оплаты платформа сформирует учебную цель, слабые темы и индивидуальный прогноз по баллам.
                    </p>
                    <x-filament::button href="/tutors" tag="a" color="primary" size="sm" icon="heroicon-o-magnifying-glass">
                        Выбрать репетитора
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
