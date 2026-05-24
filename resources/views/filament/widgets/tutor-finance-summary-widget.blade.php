<x-filament-widgets::widget>
    <div class="grid gap-4 xl:grid-cols-3">
        @foreach ($cards as $card)
            <article class="rounded-[24px] border border-stone-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-stone-500">{{ $card['eyebrow'] }}</p>
                        <p class="mt-3 text-4xl font-black tracking-[-0.06em] text-stone-950">{{ $card['value'] }}</p>
                    </div>
                    <span class="mt-1 h-3 w-3 rounded-full {{ $card['accent'] === 'lime' ? 'bg-lime-400' : ($card['accent'] === 'amber' ? 'bg-amber-400' : 'bg-stone-300') }}"></span>
                </div>

                <p class="mt-3 text-sm leading-7 text-stone-600">{{ $card['copy'] }}</p>

                <a
                    href="{{ $card['url'] }}"
                    class="mt-4 inline-flex min-h-10 items-center rounded-xl border border-stone-200 px-4 text-sm font-bold text-stone-900 transition hover:border-lime-400 hover:bg-lime-50"
                >
                    {{ $card['action'] }}
                </a>
            </article>
        @endforeach
    </div>
</x-filament-widgets::widget>
