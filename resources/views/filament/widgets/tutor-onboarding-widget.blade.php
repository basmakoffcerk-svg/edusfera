<x-filament-widgets::widget>
    @if (! $hasProfile)
        <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <h3 class="text-2xl font-black tracking-tight text-stone-950">Добро пожаловать в Edusfera! До первых учеников осталось 3 шага.</h3>
            <p class="mt-3 text-sm leading-7 text-stone-600">Мы берем на себя поиск клиентов, платежи и чеки, чтобы вы могли просто преподавать. Заполнение займет не более 15 минут.</p>
            <div class="mt-5">
                <a href="/admin/tutor-profiles/create" class="inline-flex min-h-11 items-center rounded-xl bg-lime-400 px-5 text-sm font-bold text-stone-900 hover:bg-lime-300">
                    Начать настройку профиля
                </a>
            </div>
        </section>
    @else
        <section class="rounded-[24px] border border-stone-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-stone-500">Здоровье профиля</p>
                    <h3 class="mt-2 text-2xl font-black tracking-tight text-stone-950">Профиль заполнен на {{ $progress }}%</h3>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-600">
                        @if ($rank)
                            Сейчас анкета находится на {{ $rank }}-м месте в поиске. Чем выше заполнение и больше оплаченных отзывов, тем выше позиция.
                        @else
                            После модерации и первых оплаченных уроков анкета начнет подниматься в поисковой выдаче.
                        @endif
                    </p>
                </div>
                <a href="{{ route('filament.admin.resources.tutor-profiles.edit', ['record' => $profileId]) }}" class="inline-flex min-h-10 items-center rounded-xl border border-stone-300 px-4 text-sm font-bold text-stone-900 hover:border-lime-400">
                    Доработать профиль
                </a>
            </div>

            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-stone-100">
                <div class="h-full rounded-full bg-lime-400 transition-all duration-300" style="width: {{ $progress }}%;"></div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <article class="rounded-xl border border-stone-200 bg-stone-50 px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Позиция в каталоге</p>
                    <p class="mt-2 text-xl font-black tracking-tight text-stone-950">
                        @if ($rank)
                            {{ $rank }} место
                        @else
                            Ждет публикации
                        @endif
                    </p>
                </article>
                <article class="rounded-xl border border-stone-200 bg-stone-50 px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Отзывы</p>
                    <p class="mt-2 text-xl font-black tracking-tight text-stone-950">Только после оплаты</p>
                </article>
                <article class="rounded-xl border border-stone-200 bg-stone-50 px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-500">Рычаг роста</p>
                    <p class="mt-2 text-xl font-black tracking-tight text-stone-950">Фото + расписание + цена</p>
                </article>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach ($steps as $step)
                    <article class="rounded-xl border border-stone-200 bg-stone-50 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full {{ $step['done'] ? 'bg-lime-400 text-stone-900' : 'bg-stone-200 text-stone-500' }}">
                                    {{ $step['done'] ? 'x' : '!' }}
                                </span>
                                <span class="text-sm font-semibold text-stone-800">{{ $step['label'] }}</span>
                            </div>
                            @if (! $step['done'] && $step['action'])
                                <a href="{{ $step['action'] }}" class="text-xs font-bold text-violet-700 hover:text-stone-900">{{ $step['action_label'] }}</a>
                            @elseif (! $step['done'])
                                <span class="text-xs font-semibold text-amber-700">{{ $step['action_label'] }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        @if ($showAntiChurn)
            <section class="mt-4 rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700">Анти-отток</p>
                <h3 class="mt-2 text-xl font-black text-stone-950">Пока нет заявок?</h3>
                <p class="mt-2 text-sm leading-7 text-stone-700">По статистике Edusfera, снижение цены на 5&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/> или добавление видео-визитки приносит первую заявку в течение 24 часов.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ $antiChurn['editUrl'] }}" class="inline-flex min-h-10 items-center rounded-xl bg-stone-900 px-4 text-sm font-bold text-white hover:bg-violet-700">
                        Снизить цену на первую неделю
                    </a>
                    <a href="{{ $antiChurn['editUrl'] }}" class="inline-flex min-h-10 items-center rounded-xl border border-stone-300 bg-white px-4 text-sm font-bold text-stone-900 hover:border-lime-400">
                        Загрузить видео (скоро)
                    </a>
                </div>
            </section>
        @endif
    @endif
</x-filament-widgets::widget>
