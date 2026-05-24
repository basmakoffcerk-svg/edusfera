<x-filament-widgets::widget>
    <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 xl:grid-cols-[1.25fr_0.75fr]">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-stone-500">Лестница комиссий</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h3 class="text-3xl font-black tracking-[-0.05em] text-stone-950">Текущая комиссия: {{ $currentRate }}%</h3>
                    <span class="inline-flex min-h-9 items-center rounded-full bg-stone-100 px-3 text-xs font-bold text-stone-700">
                        {{ $monthlyPaidLessons }} оплаченных уроков в этом месяце
                    </span>
                </div>

                <p class="mt-3 text-sm leading-7 text-stone-600">
                    @if ($nextTier)
                        Проведите еще {{ $lessonsToNextTier }}
                        {{ trans_choice('урок|урока|уроков', $lessonsToNextTier) }}
                        через платформу, чтобы снизить комиссию до {{ $nextTier['rate'] }}%.
                    @else
                        Вы уже на максимальной ступени комиссии. Сохраняйте уроки внутри платформы, чтобы не терять маржинальность.
                    @endif
                </p>

                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs font-black uppercase tracking-[0.18em] text-stone-500">
                        <span>{{ $currentTier['label'] }}</span>
                        <span>{{ $nextTier['label'] ?? 'Максимум' }}</span>
                    </div>
                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-stone-100">
                        <div class="h-full rounded-full bg-lime-500 transition-all duration-500" style="width: {{ $progress }}%;"></div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-stone-500">Доход за месяц</p>
                <p class="mt-2 text-3xl font-black tracking-[-0.05em] text-stone-950">{{ $monthlyRevenue }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/></p>
                <p class="mt-2 text-sm leading-7 text-stone-600">{{ $nextTierRevenueHint }}</p>
                <a
                    href="/admin/transactions"
                    class="mt-3 inline-flex min-h-10 items-center rounded-xl bg-lime-400 px-4 text-sm font-black text-stone-950 transition hover:bg-lime-300"
                >
                    История оплат
                </a>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
