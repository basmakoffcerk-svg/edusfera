<x-filament-widgets::widget>
    @php
        $firstName = explode(' ', trim((string) $user->name))[0] ?? 'пользователь';
    @endphp

    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-3xl border border-white/70 bg-gradient-to-br from-white via-violet-50/50 to-lime-50/50 p-6 shadow-[0_20px_70px_-45px_rgba(15,23,42,0.45)] sm:p-8">
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-violet-300/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-20 -bottom-20 h-56 w-56 rounded-full bg-lime-300/20 blur-3xl"></div>

            <div class="relative grid gap-5 xl:grid-cols-[minmax(0,1fr)_19rem] xl:items-center">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.24em] text-violet-600">Личный кабинет</p>
                    <h2 class="mt-2 text-3xl font-black tracking-[-0.03em] text-gray-950 sm:text-4xl">
                        Рады видеть вас, <span class="text-violet-700">{{ $firstName }}</span>
                    </h2>
                    <p class="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-gray-600 sm:text-base">
                        Контролируйте баланс, уроки и быстрые действия в одном месте без лишних переходов.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row xl:flex-col">
                    <a href="/admin/diagnostic" class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-violet-600 to-violet-700 px-5 py-3 text-sm font-black text-white shadow-[0_10px_30px_-20px_rgba(109,40,217,1)] transition hover:from-violet-500 hover:to-violet-600">
                        @if($diagnosticPendingCount > 0)
                            Пройти диагностику
                        @else
                            Обновить baseline
                        @endif
                    </a>
                    <a href="/admin/homework" class="inline-flex items-center justify-center rounded-2xl border border-violet-200 bg-white px-5 py-3 text-sm font-black text-violet-700 transition hover:border-violet-300 hover:bg-violet-50">
                        Домашка
                        @if($activeHomeworkCount > 0)
                            <span class="ml-2 inline-flex min-w-6 items-center justify-center rounded-full bg-violet-600 px-2 py-0.5 text-[11px] font-black text-white">{{ $activeHomeworkCount }}</span>
                        @endif
                    </a>
                    <a href="/admin/wallet" class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-lime-300 to-lime-400 px-5 py-3 text-sm font-black text-lime-950 shadow-[0_10px_30px_-20px_rgba(132,204,22,1)] transition hover:from-lime-200 hover:to-lime-300">
                        Пополнить баланс
                    </a>
                    <a href="/tutors" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-900 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                        Поиск репетитора
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.3" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <article class="rounded-2xl border border-fuchsia-100 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-fuchsia-600">Траектории</p>
                <p class="mt-3 text-4xl font-black tracking-[-0.03em] text-gray-950">{{ $activeGoalsCount }}</p>
                <p class="mt-1 text-xs font-semibold text-gray-500">
                    @if($diagnosticPendingCount > 0)
                        {{ $diagnosticPendingCount }} ждут стартовой диагностики
                    @else
                        baseline уже заполнен
                    @endif
                </p>
            </article>

            <article class="rounded-2xl border border-violet-100 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-violet-600">Доступно</p>
                <p class="mt-3 text-4xl font-black tracking-[-0.03em] text-gray-950">{!! \App\Support\BynMoneyFormatter::format($availableBalance) !!}</p>
            </article>

            <article class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-sky-600">Резерв</p>
                <p class="mt-3 text-4xl font-black tracking-[-0.03em] text-gray-950">{!! \App\Support\BynMoneyFormatter::format($heldForBookedLessons) !!}</p>
            </article>

            <article class="rounded-2xl border border-amber-100 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-amber-600">В плане</p>
                <p class="mt-3 text-4xl font-black tracking-[-0.03em] text-gray-950">{{ $scheduledCount }}</p>
                <p class="mt-1 text-xs font-semibold text-gray-500">уроков</p>
            </article>

            <article class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-600">Пройдено</p>
                <p class="mt-3 text-4xl font-black tracking-[-0.03em] text-gray-950">{{ $completedCount }}</p>
                <p class="mt-1 text-xs font-semibold text-gray-500">уроков</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_20rem]">
            <article class="rounded-3xl border border-fuchsia-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-fuchsia-600">Учебный прогресс</p>
                        @if($primaryGoal)
                            <h3 class="mt-2 text-2xl font-black tracking-[-0.03em] text-gray-950">
                                {{ $primaryGoal->subject }} · {{ $primaryGoal->exam_type }}
                            </h3>
                            <p class="mt-2 text-sm font-medium leading-relaxed text-gray-600">
                                @if($primaryGoal->exam_date)
                                    Экзамен: {{ $primaryGoal->exam_date->format('d.m.Y') }}.
                                @else
                                    Дату экзамена можно зафиксировать в диагностике.
                                @endif
                                @if($primaryGoal->target_score)
                                    Цель: <span class="font-black text-fuchsia-700">{{ $primaryGoal->target_score }} баллов</span>.
                                @endif
                            </p>
                        @else
                            <h3 class="mt-2 text-2xl font-black tracking-[-0.03em] text-gray-950">Траектория пока не создана</h3>
                            <p class="mt-2 text-sm font-medium leading-relaxed text-gray-600">
                                После первой оплаты платформа создаст учебную цель, а здесь появятся baseline, слабые темы и прогноз по прогрессу.
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="/admin/diagnostic" class="inline-flex items-center rounded-2xl bg-fuchsia-600 px-4 py-2.5 text-sm font-black text-white transition hover:bg-fuchsia-700">
                            @if($diagnosticPendingCount > 0) Заполнить baseline @else Обновить диагностику @endif
                        </a>
                    </div>
                </div>

                @if($primaryGoal)
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl border border-fuchsia-100 bg-fuchsia-50/70 p-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-fuchsia-700">Текущий ориентир</p>
                            <p class="mt-2 text-3xl font-black tracking-[-0.03em] text-gray-950">
                                {{ $latestSnapshot?->current_score ?? $primaryGoal->current_score ?? '—' }}
                            </p>
                            <p class="mt-1 text-xs font-semibold text-gray-500">баллов сейчас</p>
                        </div>
                        <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-sky-700">Прогноз</p>
                            <p class="mt-2 text-3xl font-black tracking-[-0.03em] text-gray-950">
                                {{ $latestSnapshot?->predicted_score ?? $latestSnapshot?->current_score ?? $primaryGoal->current_score ?? '—' }}
                            </p>
                            <p class="mt-1 text-xs font-semibold text-gray-500">если держать текущий темп</p>
                        </div>
                        <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-amber-700">Слабые темы</p>
                            <p class="mt-2 text-3xl font-black tracking-[-0.03em] text-gray-950">{{ $activeSkillGapsCount }}</p>
                            <p class="mt-1 text-xs font-semibold text-gray-500">в активном фокусе</p>
                        </div>
                        <div class="rounded-2xl border border-lime-100 bg-lime-50/70 p-4 md:col-span-3">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-lime-700">Домашняя работа</p>
                                    <p class="mt-2 text-sm font-semibold text-gray-700">
                                        Активно: <span class="font-black text-lime-700">{{ $activeHomeworkCount }}</span>.
                                        Выполнено: <span class="font-black text-gray-950">{{ $completedHomeworkCount }}</span>.
                                    </p>
                                </div>
                                <a href="/admin/homework" class="inline-flex min-h-10 items-center rounded-xl bg-lime-400 px-4 text-sm font-black text-stone-950 transition hover:bg-lime-300">
                                    Открыть домашку
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-gray-100 bg-gray-50/70 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-black text-gray-950">Движение к цели</p>
                            <p class="text-sm font-black text-fuchsia-700">
                                @if($progressPercent !== null) {{ $progressPercent }}% @else Нет baseline @endif
                            </p>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full bg-white shadow-inner">
                            <div class="h-full rounded-full bg-gradient-to-r from-fuchsia-500 via-violet-500 to-sky-500" style="width: {{ $progressPercent ?? 8 }}%"></div>
                        </div>
                        <p class="mt-3 text-sm font-medium leading-relaxed text-gray-600">{{ $nextStep }}</p>
                    </div>
                @endif
            </article>

            <aside class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-500">Следующее действие</p>
                <div class="mt-3 space-y-3">
                    <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4">
                        <p class="text-sm font-black text-gray-950">
                            @if($diagnosticPendingCount > 0)
                                Сначала закройте стартовую диагностику
                            @elseif($activeSkillGapsCount > 0)
                                Согласуйте план по слабым темам
                            @else
                                Продолжайте по текущей траектории
                            @endif
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $nextStep }}</p>
                    </div>

                    <div class="rounded-2xl border border-lime-100 bg-lime-50/70 p-4">
                        <p class="text-sm font-black text-lime-900">Что даст следующий шаг</p>
                        <p class="mt-2 text-sm leading-relaxed text-lime-900/80">
                            После обновления baseline и слабых тем платформа сможет точнее показывать прогресс, формировать домашку и подсказывать, на чём фокусироваться между уроками.
                        </p>
                    </div>
                </div>
            </aside>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <article class="rounded-3xl border border-sky-100 bg-white p-6 shadow-sm">
                <h3 class="text-base font-black text-gray-950">Уже оплачено и защищено</h3>
                <p class="mt-2 text-sm font-medium text-gray-600">
                    <span class="font-black text-sky-700">{!! \App\Support\BynMoneyFormatter::format($heldForBookedLessons) !!}</span>
                    в удержании до завершения {{ $upcomingPaidCount }} занятий.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse($upcomingPaidLessons as $lesson)
                        <a href="/admin/lessons/{{ $lesson->id }}" class="inline-flex items-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-800 transition hover:border-sky-300 hover:bg-sky-100">
                            Оплачен {{ $lesson->start_time->setTimezone(config('booking.display_timezone'))->format('d.m H:i') }}
                        </a>
                    @empty
                        <span class="text-xs font-medium text-gray-400">Нет активных оплаченных уроков.</span>
                    @endforelse
                </div>
            </article>

            <article class="rounded-3xl border border-violet-100 bg-white p-6 shadow-sm">
                <h3 class="text-base font-black text-gray-950">Ближайшие оплаты</h3>
                <p class="mt-2 text-sm font-medium text-gray-600">
                    Нужно <span class="font-black text-violet-700">{!! \App\Support\BynMoneyFormatter::format($reservedForUpcoming) !!}</span>
                    на {{ $upcomingUnpaidCount }} ближайших уроков. Свободно: {!! \App\Support\BynMoneyFormatter::format($freeBalance) !!}.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse($upcomingUnpaidLessons as $lesson)
                        <a href="{{ route('checkout.show', $lesson) }}" class="inline-flex items-center rounded-xl bg-violet-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-violet-700">
                            Оплатить {{ $lesson->start_time->setTimezone(config('booking.display_timezone'))->format('d.m H:i') }}
                        </a>
                    @empty
                        <span class="text-xs font-medium text-gray-400">Все ближайшие уроки уже оплачены.</span>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-filament-widgets::widget>
