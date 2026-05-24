<x-filament-widgets::widget>
    <section class="rounded-[28px] border border-stone-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-stone-500">Панель модератора</p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-stone-950">Рабочий центр Edusfera</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                    Здесь собраны только действия, которые влияют на качество каталога, спорные кейсы и деньги платформы.
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 xl:grid-cols-4 md:grid-cols-2">
            @foreach ($actions as $action)
                @php
                    $toneClasses = match ($action['tone']) {
                        'dark' => 'border-stone-950 bg-stone-950 text-white',
                        'lime' => 'border-lime-300 bg-lime-100/80 text-stone-950',
                        default => 'border-stone-200 bg-stone-50 text-stone-950',
                    };
                @endphp

                <a href="{{ $action['url'] }}"
                   class="{{ $toneClasses }} group flex min-h-36 flex-col justify-between rounded-[24px] border p-5 transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div>
                        <p class="text-lg font-black tracking-[-0.04em]">{{ $action['label'] }}</p>
                        <p class="mt-2 text-sm leading-6 {{ $action['tone'] === 'dark' ? 'text-white/75' : 'text-stone-600' }}">
                            {{ $action['description'] }}
                        </p>
                    </div>

                    <span class="mt-4 inline-flex items-center gap-2 text-sm font-black">
                        Открыть
                        <span class="transition group-hover:translate-x-1">-></span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
</x-filament-widgets::widget>

