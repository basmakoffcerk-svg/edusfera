<x-filament-widgets::widget>
    <div class="grid gap-4 xl:grid-cols-2">
        <section class="rounded-[24px] border border-stone-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-stone-500">Требует внимания</p>
                    <h3 class="mt-2 text-2xl font-black tracking-[-0.03em] text-stone-950">
                        @if ($newRequestsCount > 0)
                            {{ $newRequestsCount }} новых {{ trans_choice('заявка|заявки|заявок', $newRequestsCount) }}
                        @else
                            Новых заявок пока нет
                        @endif
                    </h3>
                </div>
                <a href="/admin/lesson-requests" class="inline-flex min-h-10 items-center rounded-xl border border-stone-200 px-4 text-sm font-bold text-stone-900 transition hover:border-lime-400 hover:bg-lime-50">
                    Все заявки
                </a>
            </div>

            @if ($latestRequest)
                <div class="mt-4 rounded-xl border border-lime-200 bg-lime-50 p-4">
                    <p class="text-sm font-semibold text-stone-900">
                        Новая заявка от {{ $latestRequest->student?->name ?? $latestRequest->parent?->name ?? 'ученика' }}
                    </p>
                    <p class="mt-1 text-sm leading-6 text-stone-600">Подтвердите слот быстро, чтобы не просесть в поисковой выдаче.</p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="/admin/lesson-requests" class="inline-flex min-h-10 items-center rounded-xl bg-stone-950 px-4 text-sm font-black text-white transition hover:bg-stone-800">
                            Подтвердить занятие
                        </a>
                        <a href="/admin/messages" class="inline-flex min-h-10 items-center rounded-xl border border-stone-200 bg-white px-4 text-sm font-bold text-stone-900 transition hover:border-lime-400">
                            Ответить в чат
                        </a>
                    </div>
                </div>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-stone-300 bg-stone-50 p-4">
                    <p class="text-sm font-semibold text-stone-700">Новых заявок пока нет.</p>
                    <p class="mt-1 text-sm leading-6 text-stone-600">Как только придет новый запрос, здесь появится короткое действие без лишней навигации.</p>
                </div>
            @endif
        </section>

        <section class="rounded-[24px] border border-stone-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-stone-500">Ближайший урок</p>
                    <h3 class="mt-2 text-2xl font-black tracking-[-0.03em] text-stone-950">
                        @if ($upcomingLesson)
                            {{ $upcomingLesson->start_time->timezone(config('booking.display_timezone'))->format('d.m, H:i') }}
                        @else
                            Пока пусто
                        @endif
                    </h3>
                </div>
            </div>

            @if ($upcomingLesson)
                <div class="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-4">
                    <p class="text-sm font-semibold text-stone-900">{{ $upcomingLesson->student?->name ?? 'Ученик' }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex min-h-9 items-center rounded-full border border-stone-200 bg-white px-3 text-xs font-bold text-stone-700">
                            {{ $upcomingLesson->status === \App\Models\Lesson::STATUS_CONFIRMED ? 'Подтвержден' : 'Ожидает подтверждения' }}
                        </span>
                        <span class="inline-flex min-h-9 items-center rounded-full border border-stone-200 bg-white px-3 text-xs font-bold text-stone-700">
                            {{ $upcomingLesson->payment_status === \App\Models\Lesson::PAYMENT_PAID ? 'Средства зафиксированы' : 'Ожидает оплаты' }}
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if ($meetingJoinAvailable)
                            <a href="{{ $upcomingLesson->meeting_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center rounded-xl bg-lime-400 px-4 text-sm font-black text-stone-950 transition hover:bg-lime-300">
                                Войти в звонок
                            </a>
                        @else
                            <a href="/admin/lessons" class="inline-flex min-h-10 items-center rounded-xl bg-stone-950 px-4 text-sm font-black text-white transition hover:bg-stone-800">
                                Открыть урок
                            </a>
                        @endif

                        <a href="/admin/messages" class="inline-flex min-h-10 items-center rounded-xl border border-stone-200 bg-white px-4 text-sm font-bold text-stone-900 transition hover:border-lime-400">
                            Написать ученику
                        </a>
                    </div>
                </div>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-stone-300 bg-stone-50 p-4">
                    <p class="text-sm font-semibold text-stone-700">Ближайший урок еще не запланирован.</p>
                    <p class="mt-1 text-sm leading-6 text-stone-600">Когда появится подтвержденный слот, здесь будет короткий доступ к уроку и чату с учеником.</p>
                </div>
            @endif
        </section>
    </div>
</x-filament-widgets::widget>
