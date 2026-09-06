<x-filament-widgets::widget>
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-violet-50 text-violet-700 font-bold text-xs dark:bg-violet-950/50 dark:text-violet-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>Edusfera Core · SaaS Engine</span>
                </div>
                
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Центр управления, {{ $adminName }}
                </h1>
                
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Сводка экосистемы на {{ now()->translatedFormat('j F Y, H:i') }}. SaaS-модель активна: фиксированные тарифные планы.
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <a href="/admin/microservices-dashboard" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-gray-100 hover:bg-violet-50 text-gray-700 hover:text-violet-700 transition text-xs font-semibold dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>Состояние системы (4/4)</span>
                </a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- SaaS MRR -->
            <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">MRR Подписок</span>
                    <span class="inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-xs font-bold text-violet-700 dark:bg-violet-900/60 dark:text-violet-300">
                        SaaS
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">
                    {{ $mrr }} <span class="text-xs font-normal text-gray-500">BYN/мес</span>
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Собрано в месяце: <strong class="text-gray-700 dark:text-gray-200">{{ $subscriptionRevenueThisMonth }} BYN</strong>
                </div>
            </div>

            <!-- Active Subscriptions -->
            <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Подписчики</span>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">
                        {{ $totalSubscribers }} всего
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">
                    {{ $activeSubscriptions }} <span class="text-xs font-normal text-emerald-600 font-semibold">+{{ $trialSubscriptions }} trial</span>
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Основателей (Founders): <strong class="text-amber-600 dark:text-amber-400">{{ $founderSubscriptions }}/50</strong>
                </div>
            </div>

            <!-- Total GMV -->
            <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">GMV Уроков</span>
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-900/60 dark:text-blue-300">
                        100% репетиторам
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">
                    {{ $gmvTotal }} <span class="text-xs font-normal text-gray-500">BYN</span>
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    В текущем месяце: <strong class="text-gray-700 dark:text-gray-200">{{ $gmvThisMonth }} BYN</strong>
                </div>
            </div>

            <!-- Lessons & Users -->
            <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Уроки / Конверсия</span>
                    <span class="inline-flex items-center rounded-full bg-gray-200 px-2 py-0.5 text-xs font-bold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ $completionRate }}%
                    </span>
                </div>
                <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">
                    {{ $completedLessons }} <span class="text-xs font-normal text-gray-500">из {{ $totalLessons }} уроков</span>
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Пользователей: <strong class="text-gray-700 dark:text-gray-200">{{ $totalUsers }}</strong> ({{ $totalTutors }} реп. / {{ $totalStudents }} уч.)
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
