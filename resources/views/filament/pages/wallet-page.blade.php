<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-lime-200 bg-lime-50 p-5">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-lime-700">Доступно</p>
                    <p class="mt-2 text-3xl font-black tracking-[-0.04em] text-stone-950">{!! $availableHtml !!}</p>
                    <p class="mt-2 text-sm text-stone-600">Можно потратить прямо сейчас на бронирование урока.</p>
                </div>
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-5">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-sky-700">
                        Заморожено
                        <span class="ml-1 inline-flex h-4 w-4 items-center justify-center rounded-full bg-white text-[10px] font-black text-sky-700" title="Средства зарезервированы под запланированные уроки и спишутся только после проведения.">?</span>
                    </p>
                    <p class="mt-2 text-3xl font-black tracking-[-0.04em] text-stone-950">{!! $lockedHtml !!}</p>
                    <p class="mt-2 text-sm text-stone-600">Эти средства участвуют в безопасной сделке по будущим урокам.</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-black text-stone-950">Пополнить баланс</h3>
            <p class="mt-1 text-sm text-stone-600">Выберите готовый пакет или введите свою сумму.</p>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach($presetAmounts as $amount)
                    @php
                        $isActive = (int) $selectedTopUpAmount === (int) $amount;
                    @endphp
                    <button
                        type="button"
                        wire:click="choosePresetAmount({{ $amount }})"
                        class="cursor-pointer rounded-xl border p-4 text-left transition {{ $isActive ? 'border-violet-500 bg-violet-50 ring-2 ring-violet-200' : 'border-stone-200 bg-white hover:border-violet-300 hover:bg-violet-50/40' }}"
                    >
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-stone-500">
                            @if($amount === 152) 🔥 Популярно
                            @elseif($amount === 288) Выгодно
                            @else На 1 занятие
                            @endif
                        </p>
                        <div class="mt-2 flex items-center justify-between gap-3">
                            <p class="text-2xl font-black text-stone-950">{{ number_format($amount, 2, '.', ' ') }} <x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/></p>
                            @if($isActive)
                                <span class="inline-flex items-center rounded-full bg-violet-600 px-2 py-1 text-[10px] font-black uppercase tracking-[0.12em] text-white">Выбрано</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-stone-600">
                            @if($amount === 152) Пакет на 4 занятия, экономия 5%.
                            @elseif($amount === 288) Пакет на 8 занятий, экономия 10%.
                            @else Хватит на один стандартный урок.
                            @endif
                        </p>
                    </button>
                @endforeach
            </div>

            <div class="mt-4">
                <input
                    type="number"
                    min="10"
                    step="1"
                    wire:model.defer="customTopUpAmount"
                    class="rounded-xl border border-stone-200 px-4 py-3 text-sm font-medium text-stone-900"
                    placeholder="Другая сумма, BYN"
                >
            </div>

            <button
                type="button"
                wire:click="topUp"
                class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-violet-600 px-5 py-3.5 text-base font-black text-white transition hover:bg-violet-700"
            >
                {{ $this->topUpCtaLabel }}
            </button>
            <p class="mt-2 text-xs text-stone-500">
                После нажатия вы перейдете на платежный шлюз для пополнения баланса.
            </p>
        </section>

        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-black text-stone-950">История операций</h3>
            <div class="mt-4 overflow-hidden rounded-xl border border-stone-200">
                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-stone-50">
                        <tr class="text-left text-xs font-black uppercase tracking-[0.18em] text-stone-500">
                            <th class="px-4 py-3">Операция</th>
                            <th class="px-4 py-3">Детали</th>
                            <th class="px-4 py-3">Дата</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 bg-white">
                        @forelse($entries as $entry)
                            @php
                                $type = (string) $entry->type;
                                $amount = \App\Support\BynMoneyFormatter::format((string) $entry->amount)->toHtml();
                                $label = match ($type) {
                                    'topup' => '🟢 +' . $amount,
                                    'hold' => '🟡 В резерве: ' . $amount,
                                    'release' => '🟢 Возврат резерва: ' . $amount,
                                    'payment' => '🔴 -' . $amount,
                                    'refund' => '🟢 Возврат: ' . $amount,
                                    default => '• ' . $amount,
                                };
                                $details = match ($type) {
                                    'topup' => 'Пополнение баланса',
                                    'hold' => 'Бронь под урок',
                                    'release' => 'Разморозка после отмены',
                                    'payment' => 'Урок проведен',
                                    'refund' => 'Возврат на карту',
                                    default => 'Операция',
                                };
                                if ($entry->lesson?->tutor?->name) {
                                    $details .= ': ' . $entry->lesson->tutor->name;
                                }
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-semibold text-stone-900">{!! $label !!}</td>
                                <td class="px-4 py-3 text-stone-600">{{ $details }}</td>
                                <td class="px-4 py-3 text-stone-500">{{ $entry->created_at->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-sm text-stone-500">Операций пока нет</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-xs text-stone-500">
                <a href="/admin/messages" class="text-stone-500 underline underline-offset-4 hover:text-stone-700">Вернуть остаток на карту</a>
                через поддержку. При возврате пакетные скидки аннулируются.
            </div>
        </section>
    </div>
</x-filament-panels::page>
