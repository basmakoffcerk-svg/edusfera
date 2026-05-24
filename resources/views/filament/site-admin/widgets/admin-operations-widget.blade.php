<x-filament-widgets::widget>
    <section class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
        <div class="rounded-[28px] border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-stone-500">Модерация и риски</p>
                    <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-stone-950">Что требует решения сейчас</h3>
                </div>
                <a href="{{ $tutorProfilesUrl }}" class="inline-flex min-h-11 items-center rounded-xl border border-stone-200 px-4 text-sm font-bold text-stone-900 transition hover:border-lime-400 hover:bg-lime-50">
                    Все анкеты
                </a>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div class="rounded-[24px] border border-stone-200 bg-stone-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-black text-stone-950">Анкеты на проверке</p>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">{{ $pendingProfiles->count() }}</span>
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse ($pendingProfiles as $profile)
                            <div class="rounded-2xl border border-stone-200 bg-white p-3">
                                <p class="text-sm font-black text-stone-950">{{ $profile->user?->name ?? 'Без имени' }}</p>
                                <p class="mt-1 text-xs text-stone-500">
                                    {{ collect($profile->subjects ?? [])->take(2)->implode(', ') ?: 'Предметы не указаны' }}
                                </p>
                                <p class="mt-2 text-xs font-medium text-stone-600">
                                    Отправлено {{ optional($profile->verification_submitted_at)->timezone(config('booking.display_timezone'))->format('d.m H:i') ?? 'только что' }}
                                </p>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-4 text-sm leading-6 text-stone-500">
                                Новых анкет на проверке нет.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[24px] border border-stone-200 bg-stone-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-black text-stone-950">Риски обхода</p>
                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700">{{ $riskProfiles->count() }}</span>
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse ($riskProfiles as $profile)
                            <div class="rounded-2xl border border-stone-200 bg-white p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-stone-950">{{ $profile->user?->name ?? 'Без имени' }}</p>
                                        <p class="mt-1 text-xs text-stone-500">Попыток обхода: {{ $profile->contact_bypass_attempts }}</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $profile->search_penalized_until && $profile->search_penalized_until->isFuture() ? 'bg-red-100 text-red-700' : 'bg-stone-200 text-stone-700' }}">
                                        {{ $profile->search_penalized_until && $profile->search_penalized_until->isFuture() ? 'Пессимизация активна' : 'Под наблюдением' }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-4 text-sm leading-6 text-stone-500">
                                Активных сигналов обхода сейчас нет.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-[28px] border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-stone-500">Операционный контроль</p>
                    <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-stone-950">Платежи и ближайшие уроки</h3>
                </div>
            </div>

            <div class="mt-6 rounded-[24px] border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-black text-stone-950">Последние оплаты</p>
                    <a href="{{ $transactionsUrl }}" class="text-sm font-black text-stone-500 transition hover:text-stone-950">Все транзакции</a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($latestTransactions as $transaction)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-3">
                            <div>
                                <p class="text-sm font-black text-stone-950">
                                    {{ $transaction->lesson?->student?->name ?? 'Ученик' }} -> {{ $transaction->lesson?->tutor?->name ?? 'Репетитор' }}
                                </p>
                                <p class="mt-1 text-xs text-stone-500">
                                    {{ optional($transaction->paid_at)->timezone(config('booking.display_timezone'))->format('d.m H:i') ?? 'Без даты' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-stone-950">{{ number_format((float) $transaction->amount, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/></p>
                                <p class="mt-1 text-xs font-medium {{ $transaction->status === \App\Models\Transaction::STATUS_SUCCESS ? 'text-emerald-600' : 'text-stone-500' }}">
                                    {{ $transaction->status === \App\Models\Transaction::STATUS_SUCCESS ? 'Успешно' : $transaction->status }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-4 text-sm leading-6 text-stone-500">
                            Пока нет транзакций для отображения.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-4 rounded-[24px] border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-black text-stone-950">Ближайшие уроки и заявки</p>
                    <a href="{{ $lessonRequestsUrl }}" class="text-sm font-black text-stone-500 transition hover:text-stone-950">Открыть очередь</a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($upcomingLessons as $lesson)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-3">
                            <div>
                                <p class="text-sm font-black text-stone-950">{{ $lesson->tutor?->name ?? 'Репетитор' }} / {{ $lesson->student?->name ?? 'Ученик' }}</p>
                                <p class="mt-1 text-xs text-stone-500">
                                    {{ $lesson->start_time->timezone(config('booking.display_timezone'))->format('d.m.Y H:i') }}
                                </p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $lesson->status === \App\Models\Lesson::STATUS_PENDING ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $lesson->status === \App\Models\Lesson::STATUS_PENDING ? 'Новая заявка' : 'Подтверждено' }}
                            </span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-4 text-sm leading-6 text-stone-500">
                            Нет ближайших уроков или ожидающих заявок.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>

