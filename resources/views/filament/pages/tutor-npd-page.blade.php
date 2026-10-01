<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $monthGross = $data['monthGross'] ?? 0;
        $taxDueMonth = $data['taxDueMonth'] ?? 0;
        $deductionRemaining = $data['deductionRemaining'] ?? 2000;
        $deductionUsed = $data['deductionUsed'] ?? 0;
        $paymentDeadline = $data['paymentDeadline'] ?? '';
        $notificationDeadline = $data['notificationDeadline'] ?? '';
        $lessons = $data['lessons'] ?? collect();
        $receiptsIssuedCount = $data['receiptsIssuedCount'] ?? 0;
        $receiptsPendingCount = $data['receiptsPendingCount'] ?? 0;
        $selectedLesson = $data['selectedLesson'] ?? null;
        $canUseNpd = $data['canUseNpd'] ?? false;
    @endphp

    <div class="space-y-8">
        @if (! $canUseNpd)
            <div class="relative overflow-hidden rounded-2xl p-6 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border-2 border-amber-500/30">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/20 text-amber-500 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>Автоматизация чеков НПД доступна на тарифе «Про»</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-400 font-semibold">Тариф «Про» / «Премиум»</span>
                            </h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300 max-w-2xl">
                                На текущем тарифе «Стандарт» доступен только ручной учет. Подключите тариф «Про», чтобы автоматически выгружать чеки в приложение МНС РБ «Профдоход», вести книгу учета и экономить до 4 часов каждый месяц.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('filament.admin.pages.tutor-subscription-page') }}"
                       class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold text-sm shadow-lg shadow-amber-500/20 transition-all shrink-0">
                        <span>Подключить «Про»</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        @endif
        {{-- Header Status Banner with explicit Apple Dark styling --}}
        <div style="background: linear-gradient(135deg, #0B0F19 0%, #172033 50%, #0B0F19 100%) !important; color: #FFFFFF !important;"
             class="relative overflow-hidden rounded-2xl p-6 sm:p-8 shadow-xl border border-slate-800">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold tracking-wide text-emerald-300 border border-emerald-500/30">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Налог на профдоход (НПД)
                        </span>
                        <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 border border-white/10">
                            Ставка 10% • Физлица
                        </span>
                        <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-200 border border-white/10">
                            МНС РБ (ст. 381 НК РБ)
                        </span>
                    </div>

                    <h1 class="mt-4 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                        Кабинет плательщика НПД
                    </h1>
                    <p class="mt-2 text-sm sm:text-base text-slate-300 max-w-2xl leading-relaxed">
                        Платформа Edusfera не удерживает комиссию с уроков. Ученики платят вам напрямую, а вы формируете чеки в сервисе МНС «Профдоход» по ставке 10%.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                    <a href="https://npd.nalog.gov.by/npdweb/" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 px-5 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-emerald-500/25 transition-all hover:scale-[1.02] active:scale-[0.98]">
                        <span>Открыть npd.nalog.gov.by</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>

                    <a href="https://play.google.com/store/apps/details?id=by.nalog.npd" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 border border-white/20 px-4 py-3 text-sm font-medium text-white transition-all">
                        <span>Приложение «Профдоход»</span>
                    </a>
                </div>
            </div>

            {{-- Deduction Toggle Bar --}}
            <div class="mt-6 pt-5 border-t border-white/15 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-300">
                <div class="flex items-start gap-2">
                    <label class="flex items-start gap-2.5 cursor-pointer select-none text-slate-200">
                        <input type="checkbox"
                               wire:click="toggleDeduction"
                               @checked($this->applyFirstTimeDeduction)
                               class="mt-0.5 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500/20 shrink-0">
                        <span>Применять льготный вычет 2 000 руб. (впервые зарегистрированным плательщикам НПД)</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- 4 Metric KPI Cards in Apple Style --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {{-- Card 1: Доход за месяц --}}
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Доход за {{ now()->translatedFormat('F') }}</span>
                    <span class="rounded-lg bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        {{ number_format($monthGross, 2, '.', ' ') }} <span class="text-base font-normal text-slate-500">BYN</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Прямая оплата учеников за проведённые уроки
                    </div>
                </div>
            </div>

            {{-- Card 2: Расчётный налог к уплате --}}
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Налог к уплате (10%)</span>
                    <span class="rounded-lg bg-indigo-50 p-2 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        {{ number_format($taxDueMonth, 2, '.', ' ') }} <span class="text-base font-normal text-slate-500">BYN</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        @if ($this->applyFirstTimeDeduction && $monthGross <= $deductionRemaining)
                            <span class="inline-flex items-center font-semibold text-emerald-600 dark:text-emerald-400">
                                ✓ 0.00 BYN (покрыто льготным вычетом)
                            </span>
                        @else
                            <span>Рассчитано по ставке 10% от базы</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card 3: Льготный вычет 2000 BYN --}}
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Остаток вычета 2 000 руб.</span>
                    <span class="rounded-lg bg-amber-50 p-2 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        {{ number_format($deductionRemaining, 2, '.', ' ') }} <span class="text-base font-normal text-slate-500">BYN</span>
                    </div>
                    <div class="mt-2.5">
                        <div class="w-full bg-slate-100 rounded-full h-1.5 dark:bg-slate-800">
                            @php
                                $percentUsed = min(100, max(0, ($deductionUsed / 2000) * 100));
                            @endphp
                            <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ 100 - $percentUsed }}%"></div>
                        </div>
                        <div class="mt-1.5 text-[11px] text-slate-500 dark:text-slate-400 flex justify-between">
                            <span>Использовано: {{ number_format($deductionUsed, 0, '.', ' ') }} BYN</span>
                            <span>Лимит: 2 000 BYN</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 4: Срок уплаты --}}
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Срок уплаты налога</span>
                    <span class="rounded-lg bg-purple-50 p-2 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <div class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ $paymentDeadline }}
                    </div>
                    <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Извещение от МНС поступит в кабинет до {{ $notificationDeadline }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Interactive Tab Navigation --}}
        <div class="border-b border-slate-200 dark:border-slate-800 -mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto scrollbar-none" style="-webkit-overflow-scrolling: touch;">
            <nav class="-mb-px flex space-x-3 sm:space-x-8 min-w-max pb-px" aria-label="Tabs">
                <button type="button"
                        wire:click="setTab('portal')"
                        class="shrink-0 whitespace-nowrap group inline-flex items-center gap-2 py-3.5 sm:py-4 px-2 sm:px-1 border-b-2 font-medium text-xs sm:text-sm transition-all {{ $this->activeTab === 'portal' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span><span class="hidden sm:inline">Официальный портал </span>npd.nalog.gov.by</span>
                </button>

                <button type="button"
                        wire:click="setTab('receipts')"
                        class="shrink-0 whitespace-nowrap group inline-flex items-center gap-2 py-3.5 sm:py-4 px-2 sm:px-1 border-b-2 font-medium text-xs sm:text-sm transition-all {{ $this->activeTab === 'receipts' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Генератор чеков<span class="hidden sm:inline"> по урокам</span></span>
                    @if ($receiptsPendingCount > 0)
                        <span class="ml-1 rounded-full bg-amber-100 dark:bg-amber-950/80 px-2 py-0.5 text-xs font-bold text-amber-700 dark:text-amber-400">
                            {{ $receiptsPendingCount }}
                        </span>
                    @endif
                </button>

                <button type="button"
                        wire:click="setTab('guide')"
                        class="shrink-0 whitespace-nowrap group inline-flex items-center gap-2 py-3.5 sm:py-4 px-2 sm:px-1 border-b-2 font-medium text-xs sm:text-sm transition-all {{ $this->activeTab === 'guide' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>Памятка<span class="hidden sm:inline"> репетитора</span> по НПД</span>
                </button>
            </nav>
        </div>

        {{-- TAB 1: Official Portal View --}}
        @if ($this->activeTab === 'portal')
            <div class="space-y-6">
                {{-- Browser Toolbar & Embedded Portal Card --}}
                <div class="rounded-2xl border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
                    {{-- Chrome-style Topbar --}}
                    <div class="flex items-center justify-between px-3 sm:px-4 py-2 sm:py-3 bg-slate-100 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 gap-2">
                        <div class="hidden sm:flex items-center gap-1.5 shrink-0">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        </div>

                        <div class="flex items-center gap-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1 text-xs font-mono text-slate-600 dark:text-slate-300 flex-1 min-w-0 max-w-md sm:mx-4">
                            <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="truncate">https://npd.nalog.gov.by/npdweb/</span>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <a href="https://npd.nalog.gov.by/npdweb/" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:text-emerald-600 dark:text-slate-300 dark:hover:text-emerald-400 transition">
                                <span class="hidden sm:inline">Открыть на весь экран</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    {{-- Fast Auth Helper Banner --}}
                    <div class="p-4 bg-emerald-50/70 dark:bg-emerald-950/30 border-b border-emerald-100 dark:border-emerald-900/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white font-bold shrink-0">МСИ</span>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-slate-100">Вход без визита в налоговую через МСИ</p>
                                <p class="text-slate-600 dark:text-slate-400">Используйте номер телефона или идентификационный номер паспорта любого белорусского банка.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="https://npd.nalog.gov.by/npdweb/" target="_blank" rel="noopener noreferrer"
                               class="rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 font-medium transition">
                                Войти в МНС РБ →
                            </a>
                        </div>
                    </div>

                    {{-- Frame View with Fallback Notice --}}
                    <div class="relative w-full h-[640px] bg-slate-50 dark:bg-slate-950 flex flex-col items-center justify-center p-6 text-center">
                        <iframe src="https://npd.nalog.gov.by/npdweb/"
                                class="w-full h-full border-0 absolute inset-0 z-10"
                                title="Портал МНС РБ"></iframe>

                        <div class="relative z-0 max-w-md p-6 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400 mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Безопасный портал МНС РБ</h3>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                Государственный портал npd.nalog.gov.by защищён протоколом безопасности. Если страница не загрузилась автоматически — перейдите в 1 клик:
                            </p>
                            <div class="mt-5 flex flex-col gap-2.5">
                                <a href="https://npd.nalog.gov.by/npdweb/" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-2.5 px-4 text-xs transition">
                                    <span>Открыть личный кабинет npd.nalog.gov.by</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                                <button type="button" wire:click="setTab('receipts')"
                                        class="text-xs text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white font-medium underline">
                                    Или сформировать данные для чеков здесь
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3 Quick Auth cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="rounded-xl border border-slate-200/80 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Способ №1 (Рекомендуемый)</div>
                        <h4 class="mt-1.5 font-bold text-slate-900 dark:text-white text-sm">Вход через МСИ</h4>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Через межбанковскую систему идентификации. Нужен только номер телефона или паспорт любого банка РБ. Без визита в инспекцию.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wide">Способ №2</div>
                        <h4 class="mt-1.5 font-bold text-slate-900 dark:text-white text-sm">Логин и пароль МНС</h4>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Если вы ранее получали регистрационную карточку с паролем в любой налоговой инспекции РБ при постановке на учёт.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <div class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wide">Способ №3</div>
                        <h4 class="mt-1.5 font-bold text-slate-900 dark:text-white text-sm">Мобильная ЭЦП</h4>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Вход через специальную SIM-карту операторов А1 или МТС с активированной электронной цифровой подписью.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 2: Lesson Receipts Generator --}}
        @if ($this->activeTab === 'receipts')
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            Чеки по проведённым урокам
                        </h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            По каждому полученному платежу от ученика необходимо выбивать чек в приложении «Профдоход». Скопируйте готовые данные в 1 клик.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 px-3 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                            Выбито чеков: {{ $receiptsIssuedCount }}
                        </span>
                        @if ($receiptsPendingCount > 0)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-950/60 px-3 py-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                                Требуют чека: {{ $receiptsPendingCount }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 overflow-hidden shadow-sm">
                    @if ($lessons->isEmpty())
                        <div class="p-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Нет уроков к формированию чеков</h4>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Как только вы проведёте первое оплаченное занятие, данные для формирования чека МНС появятся здесь автоматически.
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 text-slate-500 uppercase tracking-wider font-semibold">
                                    <tr>
                                        <th class="px-6 py-3.5">Дата и время</th>
                                        <th class="px-6 py-3.5">Ученик (Заказчик)</th>
                                        <th class="px-6 py-3.5">Сумма к чеку</th>
                                        <th class="px-6 py-3.5">Налог (10%)</th>
                                        <th class="px-6 py-3.5">Статус чека в МНС</th>
                                        <th class="px-6 py-3.5 text-right">Действие</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                                    @foreach ($lessons as $lesson)
                                        @php
                                            $isIssued = ! empty($lesson->npd_receipt_issued_at);
                                            $lessonTax = round((float) $lesson->price * 0.10, 2);
                                            $studentName = $lesson->student?->name ?? 'Ученик';
                                            $lessonDate = $lesson->start_time?->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i') ?? '—';
                                            $receiptText = "Услуга: Репетиторские услуги\nСумма: {$lesson->price} BYN\nЗаказчик: {$studentName}\nДата: {$lessonDate}\nВид расчета: Безналичный";
                                        @endphp
                                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-slate-900 dark:text-white font-mono">
                                                {{ $lessonDate }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-slate-900 dark:text-white">
                                                {{ $studentName }}
                                                @if ($lesson->parent?->name)
                                                    <span class="block text-[11px] text-slate-400 font-normal">Родитель: {{ $lesson->parent->name }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap font-bold text-slate-900 dark:text-white">
                                                {{ number_format((float) $lesson->price, 2, '.', ' ') }} BYN
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-indigo-600 dark:text-indigo-400">
                                                {{ number_format($lessonTax, 2, '.', ' ') }} BYN
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($isIssued)
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">
                                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span>Выбит</span>
                                                        @if ($lesson->npd_receipt_number)
                                                            <span class="font-mono text-[10px] text-slate-400">({{ $lesson->npd_receipt_number }})</span>
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-950/60 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:text-amber-300">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                        Не выбит
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                                <button type="button"
                                                        wire:click="openReceiptModal({{ $lesson->id }})"
                                                        class="rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-800 dark:text-slate-200 transition">
                                                    Чек для МНС
                                                </button>
                                                @if ($isIssued)
                                                    <button type="button"
                                                            wire:click="unmarkReceiptIssued({{ $lesson->id }})"
                                                            title="Сбросить статус чека"
                                                            class="text-xs text-slate-400 hover:text-rose-500 transition">
                                                        ✕
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- TAB 3: Complete NPD Guide --}}
        @if ($this->activeTab === 'guide')
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/80 dark:text-emerald-400 font-bold">1</span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-base">Когда выбивать чек?</h4>
                        </div>
                        <p class="mt-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            Чек в приложении «Профдоход» формируется <strong>в момент получения оплаты</strong> от ученика или не позднее 7-го числа месяца, следующего за месяцем расчетов (при безналичных зачислениях на карту).
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950/80 dark:text-indigo-400 font-bold">2</span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-base">Как рассчитывается налог 10%?</h4>
                        </div>
                        <p class="mt-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            Налог рассчитывается <strong>автоматически самим приложением МНС</strong> на основе выбитых вами чеков. Вам не нужно заполнять декларации и сдавать отчёты.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/80 dark:text-amber-400 font-bold">3</span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-base">Льготный вычет 2 000 руб.</h4>
                        </div>
                        <p class="mt-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            Если вы впервые зарегистрировались как плательщик НПД, вам предоставляется налоговый вычет в размере <strong>2 000 белорусских рублей</strong>. До исчерпания этой суммы налог составляет 0 рублей.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-950/80 dark:text-purple-400 font-bold">4</span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-base">Сроки уплаты налога</h4>
                        </div>
                        <p class="mt-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            МНС уведомляет о сумме налога через приложение до <strong>10-го числа</strong> следующего месяца. Уплатить налог через ЕРИП необходимо не позднее <strong>22-го числа</strong>.
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-900 p-6 text-white text-xs sm:text-sm">
                    <h4 class="font-bold text-emerald-400 text-base mb-2">Путь оплаты через ЕРИП:</h4>
                    <p class="font-mono text-slate-300 leading-relaxed">
                        ЕРИП → Налоги → [Ваш город/район] → ИМНС по месту регистрации → Налог на профессиональный доход → Ввести свой УНП
                    </p>
                </div>
            </div>
        @endif

        {{-- Modal: Lesson Receipt Quick Flyout --}}
        @if ($selectedLesson)
            @php
                $modalTax = round((float) $selectedLesson->price * 0.10, 2);
                $modalStudent = $selectedLesson->student?->name ?? 'Ученик';
                $modalDate = $selectedLesson->start_time?->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i');
                $rawCopyText = "Наименование услуги: Репетиторские услуги\nСумма: {$selectedLesson->price} BYN\nЗаказчик: {$modalStudent}\nДата расчета: {$modalDate}\nВид расчета: Безналичный";
            @endphp

            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
                 x-data="{ copied: false }">
                <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-2xl space-y-5">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                        <div>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                Данные чека для приложения «Профдоход»
                            </span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                Занятие #{{ $selectedLesson->id }}
                            </h3>
                        </div>
                        <button type="button" wire:click="closeReceiptModal" class="rounded-lg p-1 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-3 font-mono text-xs bg-slate-50 dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">Услуга:</span>
                            <span class="text-slate-900 dark:text-white font-bold">Репетиторские услуги</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">Сумма расчёта:</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold text-sm">{{ $selectedLesson->price }} BYN</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">Налог 10%:</span>
                            <span class="text-slate-700 dark:text-slate-300">{{ $modalTax }} BYN</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">Покупатель (ФИО):</span>
                            <span class="text-slate-900 dark:text-white">{{ $modalStudent }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">Дата расчёта:</span>
                            <span class="text-slate-900 dark:text-white">{{ $modalDate }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-sans">Вид расчёта:</span>
                            <span class="text-slate-900 dark:text-white">Безналичный расчёт</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Номер чека из приложения «Профдоход» (необязательно):
                        </label>
                        <input type="text"
                               wire:model="receiptNumberInput"
                               placeholder="Например: 1002345"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button type="button"
                                @click="navigator.clipboard.writeText(`{{ addslashes($rawCopyText) }}`); copied = true; setTimeout(() => copied = false, 2500)"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 py-2.5 px-4 text-xs font-semibold text-slate-800 dark:text-slate-200 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span x-text="copied ? 'Скопировано в буфер!' : 'Скопировать для вставки'"></span>
                        </button>

                        <button type="button"
                                wire:click="markReceiptIssued({{ $selectedLesson->id }})"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-2.5 px-4 text-xs shadow-md transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Отметить как выбитый</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
