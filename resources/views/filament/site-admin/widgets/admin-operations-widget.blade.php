<x-filament-widgets::widget>
    <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">

        {{-- Left Column: Moderation & Risk Radar --}}
        <div class="rounded-[2.5rem] border border-slate-200/90 bg-white p-7 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-3 mb-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-200/80 text-amber-700 font-extrabold text-[10px] uppercase tracking-[0.2em]">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            Модераторский контроль
                        </div>
                        <h3 class="mt-2 text-2xl font-black tracking-tight text-slate-900">Анкеты и Риски обхода</h3>
                    </div>
                    <a href="{{ $tutorProfilesUrl }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-extrabold text-slate-700 transition-all hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                         Все анкеты
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    {{-- Subcard: Pending Profiles --}}
                    <div class="rounded-[2rem] border border-slate-200/80 bg-slate-50/70 p-5">
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                На модерации
                            </span>
                            <span class="rounded-full bg-amber-100 border border-amber-200/60 px-3 py-0.5 text-xs font-black text-amber-800">
                                {{ $pendingProfiles->count() }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($pendingProfiles as $profile)
                                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs hover:border-amber-300 transition-all">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-extrabold text-slate-900">{{ $profile->user?->name ?? 'Без имени' }}</p>
                                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500 font-medium">
                                        {{ collect($profile->subjects ?? [])->take(2)->implode(', ') ?: 'Предметы не указаны' }}
                                    </p>
                                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-semibold">
                                        <span>Отправлено:</span>
                                        <span>{{ optional($profile->verification_submitted_at)->timezone(config('booking.display_timezone'))->format('d.m H:i') ?? 'только что' }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-5 text-center text-xs font-semibold text-slate-400">
                                    Очередь модерации пуста
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Subcard: Risk Alerts --}}
                    <div class="rounded-[2rem] border border-slate-200/80 bg-slate-50/70 p-5">
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                Сигналы рисков
                            </span>
                            <span class="rounded-full bg-rose-100 border border-rose-200/60 px-3 py-0.5 text-xs font-black text-rose-800">
                                {{ $riskProfiles->count() }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            @forelse ($riskProfiles as $profile)
                                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs hover:border-rose-300 transition-all">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-extrabold text-slate-900">{{ $profile->user?->name ?? 'Без имени' }}</p>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $profile->search_penalized_until && $profile->search_penalized_until->isFuture() ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $profile->search_penalized_until && $profile->search_penalized_until->isFuture() ? 'Пессимизация' : 'Наблюдение' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-rose-600 font-bold">
                                        Попыток обхода контактов: {{ $profile->contact_bypass_attempts }}
                                    </p>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-5 text-center text-xs font-semibold text-slate-400">
                                    Сигналов обхода не обнаружено
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Finance Feed & Upcoming Lessons --}}
        <div class="rounded-[2.5rem] border border-slate-200/90 bg-white p-7 shadow-sm flex flex-col justify-between space-y-6">
            
            {{-- Transactions Block --}}
            <div>
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-emerald-700 font-extrabold text-[10px] uppercase tracking-[0.2em]">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Финансовый поток
                        </div>
                        <h3 class="mt-1.5 text-xl font-black tracking-tight text-slate-900">Последние оплаты</h3>
                    </div>
                    <a href="{{ $transactionsUrl }}" class="text-xs font-extrabold text-violet-600 hover:text-violet-800 transition-colors">
                        Все транзакции →
                    </a>
                </div>

                <div class="space-y-2.5">
                    @forelse ($latestTransactions as $transaction)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3.5 hover:bg-white transition-all">
                            <div class="space-y-0.5">
                                <p class="text-xs font-extrabold text-slate-900">
                                    {{ $transaction->lesson?->student?->name ?? 'Ученик' }} → {{ $transaction->lesson?->tutor?->name ?? 'Репетитор' }}
                                </p>
                                <p class="text-[11px] font-medium text-slate-400">
                                    {{ optional($transaction->paid_at)->timezone(config('booking.display_timezone'))->format('d.m.Y H:i') ?? 'Без даты' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-black text-slate-900">{{ number_format((float) $transaction->amount, 2, '.', ' ') }} BYN</p>
                                <span class="inline-block mt-0.5 px-2 py-0.2 rounded text-[10px] font-extrabold {{ $transaction->status === \App\Models\Transaction::STATUS_SUCCESS ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $transaction->status === \App\Models\Transaction::STATUS_SUCCESS ? 'Успешно' : $transaction->status }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-4 text-center text-xs font-semibold text-slate-400">
                            Транзакции отсутствуют
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Upcoming Lessons Block --}}
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h4 class="text-sm font-extrabold text-slate-900">Ближайшие занятия и заявки</h4>
                    <a href="{{ $lessonRequestsUrl }}" class="text-xs font-extrabold text-slate-500 hover:text-slate-900 transition-colors">
                        Очередь →
                    </a>
                </div>

                <div class="space-y-2">
                    @forelse ($upcomingLessons as $lesson)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-white p-3 hover:border-violet-300 transition-all">
                            <div>
                                <p class="text-xs font-extrabold text-slate-900">{{ $lesson->tutor?->name ?? 'Репетитор' }} / {{ $lesson->student?->name ?? 'Ученик' }}</p>
                                <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                                    {{ $lesson->start_time->timezone(config('booking.display_timezone'))->format('d.m.Y H:i') }}
                                </p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $lesson->status === \App\Models\Lesson::STATUS_PENDING ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $lesson->status === \App\Models\Lesson::STATUS_PENDING ? 'Заявка' : 'Подтвержден' }}
                            </span>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-3 text-center text-xs text-slate-400">
                            Нет предстоящих занятий
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </section>
</x-filament-widgets::widget>
