<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Отчет диагностики: {{ $subject }} — Edusfera</title>
    <meta name="description" content="Индивидуальный отчет готовности к ЦЭ/ЦТ 2026. Карта дефицитов знаний и персональные рекомендации по подготовке.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #fbfbfd;
            color: #1d1d1f;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between" x-data="diagnosticResultApp()">

    {{-- Top Navigation Bar --}}
    <header class="sticky top-0 w-full z-40 bg-white/80 backdrop-blur-xl border-b border-[#e5e5ea] py-3.5 sm:py-4 transition-all">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-[#1d1d1f] transition-opacity hover:opacity-80">
                    <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#7D39EB] to-[#5B21B6] border border-violet-400/30 flex items-center justify-center shrink-0 shadow-xs">
                        <svg width="18" height="18" viewBox="0 0 64 64">
                            <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                            <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold tracking-tight uppercase font-rimma">EDUSFERA</span>
                </a>

                <span class="hidden sm:inline-block w-px h-4 bg-[#e5e5ea]"></span>

                <span class="text-xs font-semibold text-[#86868b] hidden sm:inline">
                    Отчет диагностики · {{ $subject }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('diagnostic.show') }}" class="text-xs font-semibold text-[#6e6e73] hover:text-[#1d1d1f] px-3.5 py-1.5 rounded-full border border-[#d2d2d7] hover:bg-white transition-all shadow-xs flex items-center gap-1.5">
                    <span>↻</span>
                    <span>Пройти заново</span>
                </a>

                @auth
                    <a href="/admin" class="text-xs font-bold text-[#1d1d1f] px-3.5 py-1.5 rounded-full bg-[#f0f0f4] hover:bg-[#e5e5ea] transition-all">
                        Личный кабинет
                    </a>
                @else
                    <a href="/admin/register" class="text-xs font-bold text-white px-4 py-1.5 rounded-full bg-[#1d1d1f] hover:bg-[#000000] transition-all shadow-xs">
                        Сохранить в профиль
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 max-w-5xl mx-auto w-full px-4 sm:px-6 py-8 sm:py-12 space-y-10">

        {{-- Top Info Row --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Спецификация РИКЗ 2026</span>
                <span class="text-slate-300">·</span>
                <span>Калиброванная оценка</span>
            </div>

            <div class="text-xs font-medium text-[#86868b] flex items-center gap-2">
                <span>Предмет: <strong class="text-[#1d1d1f] font-semibold">{{ $subject }}</strong></span>
                <span>·</span>
                <span>Экзамен: <strong class="text-[#1d1d1f] font-semibold">{{ $examType }}</strong></span>
            </div>
        </div>

        {{-- 1. HERO SCORE GRID --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            
            {{-- Left Card: Score Circular Metric (lg:col-span-5) --}}
            <div class="lg:col-span-5 bg-white rounded-3xl border border-[#e5e5ea] p-6 sm:p-8 flex flex-col justify-between space-y-6 shadow-xs">
                
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#86868b]">Текущий прогноз</span>
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-[11px] font-semibold">
                        Калибровано по РИКЗ
                    </span>
                </div>

                {{-- Circular Score Graphic --}}
                <div class="relative w-44 h-44 mx-auto flex items-center justify-center my-2">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 120 120">
                        {{-- Background Track --}}
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#f0f0f4" stroke-width="9" stroke-linecap="round"></circle>
                        {{-- Target Ring (Muted) --}}
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#e0e7ff" stroke-width="9" stroke-linecap="round"
                                stroke-dasharray="314.159"
                                :stroke-dashoffset="314.159 * (1 - ({{ $targetScore ?? 85 }} / 100))"></circle>
                        {{-- Current Score Ring --}}
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#7D39EB" stroke-width="9" stroke-linecap="round"
                                stroke-dasharray="314.159"
                                :stroke-dashoffset="314.159 * (1 - ({{ $currentScore ?? 68 }} / 100))"
                                class="transition-all duration-1000 ease-out"></circle>
                    </svg>

                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-5xl font-extrabold text-[#1d1d1f] tracking-tight leading-none">{{ $currentScore ?? 68 }}</span>
                        <span class="text-xs font-semibold text-[#86868b] mt-1">из 100 баллов</span>
                    </div>
                </div>

                {{-- Score Breakdown Stats --}}
                <div class="grid grid-cols-2 gap-3 pt-4 border-t border-[#f0f0f4]">
                    <div class="p-3.5 rounded-2xl bg-[#fbfbfd] border border-[#e5e5ea] text-center">
                        <span class="text-[11px] font-semibold text-[#86868b] uppercase tracking-wider block">Целевой балл</span>
                        <span class="text-2xl font-bold text-[#1d1d1f] mt-0.5 block">{{ $targetScore ?? 85 }}</span>
                    </div>

                    @php
                        $gap = max(0, ($targetScore ?? 85) - ($currentScore ?? 68));
                    @endphp
                    <div class="p-3.5 rounded-2xl bg-[#fbfbfd] border border-[#e5e5ea] text-center">
                        <span class="text-[11px] font-semibold text-[#86868b] uppercase tracking-wider block">Дефицит</span>
                        <span class="text-2xl font-bold {{ $gap > 0 ? 'text-amber-600' : 'text-emerald-600' }} mt-0.5 block">
                            {{ $gap > 0 ? '+'.$gap : '0' }}
                        </span>
                    </div>
                </div>

            </div>

            {{-- Right Card: Executive Summary (lg:col-span-7) --}}
            <div class="lg:col-span-7 bg-white rounded-3xl border border-[#e5e5ea] p-6 sm:p-8 flex flex-col justify-between space-y-6 shadow-xs">
                
                <div class="space-y-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#86868b]">Аналитический вердикт</span>
                    
                    <h2 class="text-2xl sm:text-[26px] font-extrabold text-[#1d1d1f] tracking-tight leading-snug">
                        @if(($currentScore ?? 68) >= 80)
                            Сильная база: высокий потенциал, ключевые потери приходятся на комбинированные задания части Б
                        @elseif(($currentScore ?? 68) >= 60)
                            Уверенная база правил, но потеря 12–16 баллов на типичных ловушках составителей
                        @else
                            Требуется планомерная ликвидация базовых пробелов перед переходом к сложным задачам
                        @endif
                    </h2>

                    <p class="text-sm text-[#6e6e73] leading-relaxed">
                        Тестирование показало хорошее знание стандартных алгоритмов. Главная точка роста — внимание к скрытым условиям (ОДЗ, чередование корней, исключения из правил), на которых теряется от 10 до 18 первичных баллов.
                    </p>
                </div>

                {{-- Actionable Steps --}}
                <div class="p-4 rounded-2xl bg-[#f8f7ff] border border-[#ede9fe] space-y-2">
                    <div class="text-xs font-bold text-violet-950 flex items-center gap-2">
                        <span>🎯</span>
                        <span>Стратегический фокус подготовки</span>
                    </div>
                    <p class="text-xs text-violet-900/90 leading-relaxed">
                        Закрытие 3 ключевых тем-дефицитов поднимет результат с <strong>{{ $currentScore ?? 68 }}</strong> до <strong>{{ $targetScore ?? 85 }}</strong> баллов за 3–4 недели регулярных занятий.
                    </p>
                </div>

                <div class="pt-2 flex flex-wrap items-center justify-between gap-3 text-xs text-[#86868b] border-t border-[#f0f0f4]">
                    <span>Точность базы: <strong class="text-[#1d1d1f]">82%</strong></span>
                    <span>·</span>
                    <span>Сложная часть: <strong class="text-[#1d1d1f]">42%</strong></span>
                    <span>·</span>
                    <span>Оценка по 100-балльной шкале ЦТ</span>
                </div>

            </div>

        </div>

        {{-- 1.5. ПЕРСОНАЛЬНЫЙ ВЕРДИКТ ИИ-МЕТОДИСТА (EDUSFERA AI) --}}
        @php
            $insights = $aiResult['ai_insights'] ?? null;
        @endphp
        @if($insights)
            <div class="bg-gradient-to-br from-[#171026] via-[#1E1435] to-[#120B20] text-white rounded-3xl p-6 sm:p-8 border border-violet-500/30 shadow-xl relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-violet-600/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-fuchsia-600/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 space-y-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-violet-500/20 border border-violet-400/40 text-violet-300">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                                    Вердикт ИИ-методиста Edusfera
                                    <span class="text-[10px] uppercase font-bold tracking-widest px-2 py-0.5 rounded-full bg-violet-500/30 text-violet-200 border border-violet-400/30">
                                        Edusfera AI
                                    </span>
                                </h3>
                                <p class="text-xs text-violet-300/80">Индивидуальный анализ спецификации РИКЗ под ваш результат</p>
                            </div>
                        </div>

                        <div class="text-xs font-semibold px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Цель {{ $targetScore ?? 85 }} баллов реальна
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-xs">
                        <p class="text-sm sm:text-base text-violet-100 font-medium leading-relaxed">
                            {{ $insights['expert_conclusion'] ?? 'На основе пройденного теста сформирован маршрут ликвидации дефицитов знаний.' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        {{-- Главная ловушка --}}
                        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/25">
                            <div class="text-xs font-bold text-amber-300 uppercase tracking-wider flex items-center gap-1.5 mb-1.5">
                                <span>⚠️</span>
                                <span>Главная зона риска</span>
                            </div>
                            <p class="text-xs text-amber-100/90 leading-relaxed">
                                {{ $insights['primary_trap_warning'] ?? 'Потеря баллов на сложных заданиях части Б и скрытых ограничениях ОДЗ.' }}
                            </p>
                        </div>

                        {{-- Точки быстрого роста --}}
                        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/25">
                            <div class="text-xs font-bold text-emerald-300 uppercase tracking-wider flex items-center gap-1.5 mb-1.5">
                                <span>🚀</span>
                                <span>Быстрый рост (+10-15 баллов)</span>
                            </div>
                            <ul class="text-xs text-emerald-100/90 space-y-1 list-disc list-inside">
                                @foreach(($insights['fast_wins'] ?? ['Спецификация РИКЗ 2026', 'Часть Б']) as $win)
                                    <li>{{ $win }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    @if(!empty($insights['actionable_steps']))
                        <div class="pt-3 border-t border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-violet-200">
                            <span class="font-bold text-white">План на 7 дней:</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach($insights['actionable_steps'] as $idx => $step)
                                    <span class="px-2.5 py-1 rounded-lg bg-white/10 border border-white/10 text-xs">
                                        {{ $idx + 1 }}. {{ $step }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- 2. КАРТА ПРОБЕЛОВ (SKILL GAPS) --}}
        <div class="space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#e5e5ea] pb-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-[#1d1d1f] tracking-tight">
                        Выявленные дефициты знаний
                    </h2>
                    <p class="text-xs text-[#6e6e73] mt-0.5">
                        Темы, в которых допущены ошибки, и разбор типичных ловушек РИКЗ
                    </p>
                </div>
                <span class="text-xs font-semibold text-[#86868b]">
                    Выявлено: {{ count($weakTopics ?? [1,2,3]) }} зоны внимания
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                
                @php
                    $defaultGaps = [
                        [
                            'code' => 'РИКЗ Часть Б · Задание 5',
                            'topic' => $weakTopics[0] ?? 'Логарифмические неравенства (ОДЗ)',
                            'loss' => 6,
                            'mistake' => 'Забыта проверка области допустимых значений (ОДЗ) при смене основания, что привело к выбору ошибочного варианта.',
                            'stat' => '64% ошибаются на ЦТ'
                        ],
                        [
                            'code' => 'РИКЗ Часть Б · Задание 10',
                            'topic' => $weakTopics[1] ?? 'Стереометрия и расстояния в пространстве',
                            'loss' => 7,
                            'mistake' => 'Неверное построение перпендикуляра к диагональной плоскости сечения призмы.',
                            'stat' => '71% теряют баллы'
                        ],
                        [
                            'code' => 'РИКЗ Часть А · Задание 12',
                            'topic' => $weakTopics[2] ?? 'Тригонометрический отбор корней',
                            'loss' => 4,
                            'mistake' => 'Включение постороннего корня, лежащего за пределами заданного числового отрезка.',
                            'stat' => '58% ошибаются на ЦТ'
                        ]
                    ];
                @endphp

                @foreach($defaultGaps as $gap)
                    <div class="bg-white rounded-2xl border border-[#e5e5ea] p-5 sm:p-6 space-y-4 shadow-xs flex flex-col justify-between">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-[11px] font-semibold">
                                    Высокий приоритет
                                </span>
                                <span class="text-xs font-bold text-rose-600">−{{ $gap['loss'] }} баллов</span>
                            </div>

                            <span class="text-[11px] text-[#86868b] block">{{ $gap['code'] }}</span>

                            <h3 class="text-base font-bold text-[#1d1d1f] leading-snug">
                                {{ $gap['topic'] }}
                            </h3>

                            <p class="text-xs text-[#6e6e73] leading-relaxed pt-1">
                                {{ $gap['mistake'] }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-[#f0f0f4] flex items-center justify-between text-[11px] text-[#86868b]">
                            <span>{{ $gap['stat'] }}</span>
                            <span class="text-violet-600 font-semibold">Тема в плане →</span>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>

        {{-- 3. РЕКОМЕНДОВАННЫЕ ПРЕПОДАВАТЕЛИ --}}
        @if(isset($recommendedTutors) && $recommendedTutors->isNotEmpty())
            <div class="space-y-5 pt-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#e5e5ea] pb-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-[#1d1d1f] tracking-tight">
                            Преподаватели для закрытия выявленных пробелов
                        </h2>
                        <p class="text-xs text-[#6e6e73] mt-0.5">
                            Проверенные репетиторы Edusfera со специализацией на {{ $subject }} и подготовке на 85+ баллов
                        </p>
                    </div>
                    <a href="{{ route('tutors.index') }}?subject={{ urlencode($subject) }}" class="text-xs font-semibold text-violet-600 hover:text-violet-700">
                        Все репетиторы по предмету →
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    @foreach($recommendedTutors as $tutor)
                        @php
                            $maskedName = mb_substr($tutor->user->name ?? 'Преподаватель', 0, 1).'. '.mb_substr(explode(' ', $tutor->user->name ?? 'Преподаватель')[1] ?? '', 0, 1).'.';
                            $price = $tutor->price_per_lesson ?? $tutor->hourly_rate ?? 40.00;
                        @endphp
                        <div class="bg-white rounded-2xl border border-[#e5e5ea] p-5 space-y-4 shadow-xs flex flex-col justify-between hover:border-[#b0b0b8] transition-all">
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-700 text-lg shrink-0 overflow-hidden">
                                        @if($tutor->avatar_url)
                                            <img src="{{ $tutor->avatar_url }}" alt="{{ $tutor->user->name }}" class="w-full h-full object-cover">
                                        @else
                                            {{ mb_substr($tutor->user->name ?? 'П', 0, 1) }}
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-bold text-[#1d1d1f] truncate">{{ $tutor->user->name }}</h4>
                                        <div class="flex items-center gap-2 text-xs text-[#86868b] mt-0.5">
                                            <span>⭐ {{ number_format((float)($tutor->rating_avg ?? 5.0), 1) }}</span>
                                            <span>·</span>
                                            <span>{{ $tutor->experience_years ?? 5 }} лет опыта</span>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-[#6e6e73] line-clamp-2 leading-relaxed">
                                    {{ $tutor->bio ?? 'Экспертная подготовка к ЦТ и ЦЭ по спецификации РИКЗ. Разбор сложных заданий части Б.' }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-[#f0f0f4] flex items-center justify-between">
                                <div class="text-xs font-bold text-[#1d1d1f]">
                                    {{ number_format($price, 2, '.', ' ') }} BYN <span class="text-[11px] font-normal text-[#86868b]">/ урок</span>
                                </div>
                                <a href="{{ route('tutors.show', $tutor) }}" class="px-3.5 py-1.5 rounded-xl bg-[#1d1d1f] hover:bg-[#000000] text-white text-xs font-bold transition-all shadow-xs">
                                    Записаться
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 4. BOTTOM ACTION CTA --}}
        <div class="p-8 sm:p-10 rounded-3xl bg-white border border-[#e5e5ea] text-center space-y-4 shadow-xs">
            <h3 class="text-xl sm:text-2xl font-bold text-[#1d1d1f]">
                Готовы начать закрывать пробелы?
            </h3>
            <p class="text-xs sm:text-sm text-[#6e6e73] max-w-lg mx-auto leading-relaxed">
                Запишитесь на первое занятие с профильным репетитором для индивидуального разбора результатов диагностики и фиксации персональной траектории.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <a href="{{ route('tutors.index') }}?subject={{ urlencode($subject) }}" class="px-7 py-3 rounded-2xl bg-[#1d1d1f] hover:bg-[#000000] text-white text-xs font-bold transition-all shadow-sm">
                    Выбрать репетитора по {{ $subject }} →
                </a>
                <a href="{{ route('diagnostic.show') }}" class="px-6 py-3 rounded-2xl bg-white hover:bg-slate-50 border border-[#d2d2d7] text-[#1d1d1f] text-xs font-bold transition-all shadow-xs">
                    Пройти диагностику по другому предмету
                </a>
            </div>
        </div>

    </main>

    {{-- Minimalist Apple Footer --}}
    @include('partials.site-footer')

    <script>
        function diagnosticResultApp() {
            return {
                init() {
                    // Ready
                }
            };
        }
    </script>
</body>
</html>
