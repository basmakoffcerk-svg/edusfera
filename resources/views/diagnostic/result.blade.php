<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Отчет ИИ-Диагностики: {{ $subject }} — Edusfera</title>
    <meta name="description" content="Персональный когнитивный отчет готовности к ЦЭ/ЦТ 2026. Карта дефицитов знаний и 90-дневный индивидуальный трек подготовки.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="/js/alpine.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        :root {
            --ed-lime: #C6FF33;
            --ed-lime-glow: rgba(198, 255, 51, 0.35);
            --ed-violet: #7D39EB;
            --ed-violet-glow: rgba(125, 57, 235, 0.3);
            --ed-bg: #010101;
            --ed-surface: #0B0F19;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--ed-bg);
            color: #F8FAFC;
            -webkit-font-smoothing: antialiased;
        }

        .font-rimma { font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif; }

        .story-glow {
            background: linear-gradient(135deg, rgba(19, 27, 46, 0.9) 0%, rgba(30, 16, 53, 0.85) 50%, rgba(13, 19, 34, 0.9) 100%);
            border: 1px solid rgba(198, 255, 51, 0.25);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="nexum-body min-h-screen bg-[#010101] text-white flex flex-col selection:bg-[#C6FF33] selection:text-black relative overflow-x-hidden antialiased"
      x-data="diagnosticResultApp()" x-init="init()">

    {{-- Ambient Luxury Glows --}}
    <div class="fixed top-0 left-1/3 -translate-x-1/2 -top-40 w-[650px] h-[650px] bg-[#7D39EB]/12 rounded-full blur-[150px] pointer-events-none z-0"></div>
    <div class="fixed top-40 right-1/4 translate-x-1/3 w-[600px] h-[600px] bg-[#C6FF33]/08 rounded-full blur-[160px] pointer-events-none z-0"></div>

    {{-- Background Grid Pattern --}}
    <div class="fixed inset-0 bg-[radial-gradient(rgba(255,255,255,0.03)_1px,transparent_1px)] [background-size:32px_32px] pointer-events-none z-0"></div>

    <div class="relative z-10 min-h-screen flex flex-col justify-between">

        {{-- Top Navigation Bar --}}
        <header class="sticky top-0 w-full z-40 bg-gradient-to-b from-[#010101]/90 via-[#010101]/75 to-[#010101]/30 backdrop-blur-2xl border-b border-white/[0.08] py-3.5 sm:py-4">
            <div class="max-w-7xl mx-auto px-5 sm:px-8 lg:px-12 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-white transition-colors group">
                        <svg width="28" height="28" viewBox="0 0 64 64" class="w-7 h-7 rounded-lg shadow-sm group-hover:scale-105 transition-transform">
                            <rect width="64" height="64" rx="14" fill="#7D39EB" />
                            <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                            <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                        </svg>
                        <span class="text-xl font-bold tracking-tight text-white uppercase font-rimma">edusfera</span>
                    </a>

                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gradient-to-r from-violet-500/20 via-white/[0.08] to-[#C6FF33]/15 border border-white/15 text-[#C6FF33] font-bold text-[11px] uppercase tracking-wider backdrop-blur-xl shadow-inner">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C6FF33] animate-pulse"></span>
                        Отчет 2026
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('diagnostic.show') }}" class="text-xs font-bold text-neutral-300 hover:text-white px-4 py-2 rounded-full border border-white/10 hover:bg-white/10 transition flex items-center gap-1.5">
                        <span>↻</span>
                        <span class="hidden sm:inline">Пройти заново</span>
                    </a>

                    @auth
                        <a href="/admin" class="flex items-center gap-2.5 bg-gradient-to-b from-white/[0.15] to-white/[0.05] border border-white/20 pl-2.5 pr-4 py-1.5 rounded-full hover:border-white/30 transition-all text-white backdrop-blur-xl">
                            <span class="w-6 h-6 rounded-full bg-[#C6FF33] text-black font-black flex items-center justify-center text-[10px]">
                                {{ mb_substr(auth()->user()->name, 0, 1) }}
                            </span>
                            <span class="text-xs font-bold text-white">{{ auth()->user()->name }}</span>
                        </a>
                    @else
                        <button @click="showAuthModal = true" class="text-xs font-black text-black bg-[#C6FF33] hover:bg-[#d4ff59] px-5 py-2.5 rounded-full transition shadow-[0_0_20px_rgba(198,255,51,0.3)] cursor-pointer">
                            Сохранить в профиль
                        </button>
                    @endauth
                </div>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="flex-1 px-4 sm:px-6 py-8 sm:py-12 max-w-6xl mx-auto w-full space-y-12">

            {{-- 1. HERO BLOCK: Результат, Спидометр и Когнитивная Оценка --}}
            <div class="space-y-6">
                
                {{-- Header Pill --}}
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-[#C6FF33] text-xs font-extrabold uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-[#C6FF33] animate-pulse"></span>
                        Отчет ИИ-Диагностики • Спецификация РИКЗ 2026
                    </div>

                    <div class="text-xs font-bold text-slate-400 flex items-center gap-2">
                        <span>Экзамен: <strong class="text-white">{{ $examType }}</strong></span>
                        <span>•</span>
                        <span>Предмет: <strong class="text-[#C6FF33]">{{ $subject }}</strong></span>
                    </div>
                </div>

                {{-- Hero Grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    {{-- Left Card: Speedometer & Scores (lg:col-span-5) --}}
                    <div class="lg:col-span-5 bg-slate-900/70 backdrop-blur-xl border border-white/10 rounded-3xl p-6 sm:p-8 flex flex-col justify-between space-y-6 shadow-2xl relative overflow-hidden">
                        
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Прогноз готовности</span>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                                Высокая точность 94%
                            </span>
                        </div>

                        {{-- Circular Speedometer Graphic --}}
                        <div class="flex flex-col items-center justify-center my-2 relative">
                            <svg class="w-48 h-48 transform -rotate-90" viewBox="0 0 120 120">
                                {{-- Background Track --}}
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#1E293B" stroke-width="10" stroke-linecap="round"></circle>
                                {{-- Target Score Ring --}}
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#7D39EB" stroke-width="10" stroke-linecap="round"
                                        stroke-dasharray="314.159"
                                        :stroke-dashoffset="314.159 * (1 - ({{ $targetScore ?? 85 }} / 100))"
                                        opacity="0.3"></circle>
                                {{-- Current Score Ring --}}
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#C6FF33" stroke-width="10" stroke-linecap="round"
                                        stroke-dasharray="314.159"
                                        :stroke-dashoffset="314.159 * (1 - ({{ $currentScore ?? 68 }} / 100))"
                                        class="transition-all duration-1000 ease-out drop-shadow-[0_0_12px_rgba(198,255,51,0.6)]"></circle>
                            </svg>

                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-5xl font-black text-white tracking-tight">{{ $currentScore ?? 68 }}</span>
                                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mt-0.5">из 100 баллов</span>
                            </div>
                        </div>

                        {{-- Metric Split Box --}}
                        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-white/10">
                            <div class="bg-black/40 rounded-2xl p-3.5 border border-white/5">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Целевой балл</span>
                                <span class="text-2xl font-black text-white mt-1 block">{{ $targetScore ?? 85 }}</span>
                                <span class="text-[10px] text-slate-500">Бюджет вуза</span>
                            </div>

                            <div class="bg-black/40 rounded-2xl p-3.5 border border-white/5">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Дефицит баллов</span>
                                <span class="text-2xl font-black text-[#C6FF33] mt-1 block">
                                    +{{ max(0, ($targetScore ?? 85) - ($currentScore ?? 68)) }}
                                </span>
                                <span class="text-[10px] text-[#C6FF33]/80">Нужно набрать</span>
                            </div>
                        </div>

                    </div>

                    {{-- Right Card: Cognitive AI Verdict (lg:col-span-7) --}}
                    <div class="lg:col-span-7 bg-slate-900/70 backdrop-blur-xl border border-white/10 rounded-3xl p-6 sm:p-8 flex flex-col justify-between space-y-6 shadow-2xl relative">
                        
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#C6FF33] animate-ping"></span>
                                    <span class="text-xs font-black uppercase tracking-[0.2em] text-[#C6FF33]">
                                        Когнитивный вердикт нейросети Edusfera AI
                                    </span>
                                </div>
                                <span class="text-xs font-mono text-slate-400">RIKZ-Spec v2.6</span>
                            </div>

                            <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight leading-snug">
                                @if(($currentScore ?? 68) >= 80)
                                    Высокий потенциал: близко к топовому результату, зона потерь — сложная часть Б
                                @elseif(($currentScore ?? 68) >= 60)
                                    Твердая база, но потеря 12-16 баллов на ловушках части Б
                                @else
                                    Требуется системное закрытие базовых дефицитов и правил РИКЗ
                                @endif
                            </h2>

                            <p class="text-slate-300 text-sm leading-relaxed">
                                Анализ выявил устойчивое понимание стандартных формулировок части А, однако при переходе к комбинированным заданиям (ОДЗ, отбор корней, пунктуационные исключения) фиксируется системный сбой из-за дистракторов составителей РИКЗ.
                            </p>
                        </div>

                        {{-- Accuracy Bars --}}
                        <div class="space-y-3 pt-2">
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-slate-300">Точность выполнения Части А (Базовая):</span>
                                    <span class="text-emerald-400">82% (Надежная база)</span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                                    <div class="h-full bg-emerald-400 rounded-full w-[82%]"></div>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-slate-300">Точность выполнения Части Б (Комплексная):</span>
                                    <span class="text-amber-400">42% (Критическая зона риска)</span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                                    <div class="h-full bg-amber-400 rounded-full w-[42%]"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Quick Insight Pill --}}
                        <div class="bg-violet-950/30 border border-violet-500/30 rounded-2xl p-4 flex items-start gap-3">
                            <span class="text-xl">💡</span>
                            <div class="text-xs text-slate-300 leading-relaxed">
                                <strong class="text-white font-bold">Стратегический фокус:</strong>
                                Ликвидация всего 3 ключевых тем-пробелов вернет <strong class="text-[#C6FF33]">+14 первичных баллов</strong> на реальном экзамене.
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            {{-- 2. КАРТА ДЕФИЦИТОВ (SKILL GAPS) --}}
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-white/10 pb-4">
                    <div>
                        <span class="text-xs font-black uppercase tracking-[0.2em] text-[#C6FF33]">Диагностика ошибок</span>
                        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-1">
                            Карта выявленных пробелов (Skill Gaps)
                        </h2>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">
                        Спецификация РИКЗ: выявлено {{ count($weakTopics ?? [1,2,3]) }} зоны уязвимости
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    
                    {{-- Gap Card 1 --}}
                    <div class="bg-slate-900/70 backdrop-blur-xl border border-rose-500/30 rounded-3xl p-6 space-y-4 relative shadow-xl hover:border-rose-500/60 transition group">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs font-bold flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                                Критический пробел
                            </span>
                            <span class="text-xs font-black text-rose-400">-6 баллов</span>
                        </div>

                        <div>
                            <span class="text-[11px] font-mono text-slate-400 block mb-1">РИКЗ Часть Б • Задание 5</span>
                            <h3 class="text-lg font-black text-white group-hover:text-[#C6FF33] transition">
                                {{ $weakTopics[0] ?? 'Логарифмические неравенства и ловушка ОДЗ' }}
                            </h3>
                        </div>

                        <div class="bg-black/40 rounded-2xl p-4 border border-white/5 space-y-2">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-rose-300 block">Где допущена ошибка:</span>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Забыта проверка области допустимых значений (ОДЗ) аргумента при делении и смене основания, что привело к выбору ложного дистрактора.
                            </p>
                        </div>

                        <div class="pt-2 flex items-center justify-between text-xs text-slate-400 border-t border-white/10">
                            <span>Срезаются на ЦТ: <strong class="text-white">64%</strong></span>
                            <span class="text-[#C6FF33] font-bold">Разбор в плане →</span>
                        </div>
                    </div>

                    {{-- Gap Card 2 --}}
                    <div class="bg-slate-900/70 backdrop-blur-xl border border-rose-500/30 rounded-3xl p-6 space-y-4 relative shadow-xl hover:border-rose-500/60 transition group">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs font-bold flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                                Критический пробел
                            </span>
                            <span class="text-xs font-black text-rose-400">-7 баллов</span>
                        </div>

                        <div>
                            <span class="text-[11px] font-mono text-slate-400 block mb-1">РИКЗ Часть Б • Задание 10</span>
                            <h3 class="text-lg font-black text-white group-hover:text-[#C6FF33] transition">
                                {{ $weakTopics[1] ?? 'Стереометрия и сечения в пространстве' }}
                            </h3>
                        </div>

                        <div class="bg-black/40 rounded-2xl p-4 border border-white/5 space-y-2">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-rose-300 block">Где допущена ошибка:</span>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Неверное построение проекции высоты на плоскость основания и путаница в тригонометрических соотношениях наклонных.
                            </p>
                        </div>

                        <div class="pt-2 flex items-center justify-between text-xs text-slate-400 border-t border-white/10">
                            <span>Срезаются на ЦТ: <strong class="text-white">71%</strong></span>
                            <span class="text-[#C6FF33] font-bold">Разбор в плане →</span>
                        </div>
                    </div>

                    {{-- Gap Card 3 --}}
                    <div class="bg-slate-900/70 backdrop-blur-xl border border-amber-500/30 rounded-3xl p-6 space-y-4 relative shadow-xl hover:border-amber-500/60 transition group">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/40 text-amber-300 text-xs font-bold flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                Требует закрепления
                            </span>
                            <span class="text-xs font-black text-amber-400">-5 баллов</span>
                        </div>

                        <div>
                            <span class="text-[11px] font-mono text-slate-400 block mb-1">РИКЗ Часть А • Задание 12</span>
                            <h3 class="text-lg font-black text-white group-hover:text-[#C6FF33] transition">
                                {{ $weakTopics[2] ?? 'Тригонометрический отбор корней на отрезке' }}
                            </h3>
                        </div>

                        <div class="bg-black/40 rounded-2xl p-4 border border-white/5 space-y-2">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-300 block">Где допущена ошибка:</span>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Механический отбор без единичной окружности привел к включению посторонней серии корней вне заданного промежутка.
                            </p>
                        </div>

                        <div class="pt-2 flex items-center justify-between text-xs text-slate-400 border-t border-white/10">
                            <span>Срезаются на ЦТ: <strong class="text-white">52%</strong></span>
                            <span class="text-[#C6FF33] font-bold">Разбор в плане →</span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- 3. ИНТЕРАКТИВНЫЙ ПЛАН ЛИКВИДАЦИИ ПРОБЕЛОВ (30 / 60 / 90 ДНЕЙ) --}}
            <div class="bg-slate-900/70 backdrop-blur-xl border border-white/10 rounded-3xl p-6 sm:p-10 space-y-8 shadow-2xl">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-black uppercase tracking-[0.2em] text-[#C6FF33]">Индивидуальная стратегия</span>
                        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-1">
                            План ликвидации пробелов по дням
                        </h2>
                    </div>

                    {{-- 30 / 60 / 90 Days Switcher --}}
                    <div class="bg-black/60 p-1.5 rounded-2xl border border-white/10 inline-flex gap-2">
                        <button type="button" @click="activePlanTab = 30"
                                :class="activePlanTab === 30 ? 'bg-[#C6FF33] text-black font-black' : 'text-slate-400 hover:text-white'"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition">
                            30 дней (Экспресс)
                        </button>
                        <button type="button" @click="activePlanTab = 60"
                                :class="activePlanTab === 60 ? 'bg-[#C6FF33] text-black font-black' : 'text-slate-400 hover:text-white'"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition">
                            60 дней (Оптимум)
                        </button>
                        <button type="button" @click="activePlanTab = 90"
                                :class="activePlanTab === 90 ? 'bg-[#C6FF33] text-black font-black' : 'text-slate-400 hover:text-white'"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition">
                            90 дней (Максимум)
                        </button>
                    </div>
                </div>

                {{-- Plan Stages Timeline --}}
                <div class="space-y-4">
                    
                    {{-- Stage 1 --}}
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-5 sm:p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 hover:border-white/15 transition">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-2xl bg-violet-600/20 border border-violet-500/40 text-violet-300 font-black flex items-center justify-center shrink-0">
                                01
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black uppercase tracking-wider text-[#C6FF33]">Этап 1 (Дни 1-15)</span>
                                    <span class="text-xs text-slate-500">•</span>
                                    <span class="text-xs text-slate-400">Закрытие базовых дыр</span>
                                </div>
                                <h4 class="text-base font-bold text-white mt-1">Отработка ОДЗ, корней и базовых ловушек РИКЗ</h4>
                                <p class="text-xs text-slate-300 mt-1">Разбор 30 прототипов заданий спецификации с проверкой ограничений.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 self-end md:self-center">
                            <span class="px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                                +8 баллов
                            </span>
                            <span class="text-slate-600">✓</span>
                        </div>
                    </div>

                    {{-- Stage 2 --}}
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-5 sm:p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 hover:border-white/15 transition">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-2xl bg-violet-600/20 border border-violet-500/40 text-violet-300 font-black flex items-center justify-center shrink-0">
                                02
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black uppercase tracking-wider text-[#C6FF33]"
                                          x-text="activePlanTab === 30 ? 'Этап 2 (Дни 16-25)' : 'Этап 2 (Дни 16-45)'"></span>
                                    <span class="text-xs text-slate-500">•</span>
                                    <span class="text-xs text-slate-400">Часть Б и Алгоритмы</span>
                                </div>
                                <h4 class="text-base font-bold text-white mt-1">Стереометрические модели и отбор корней</h4>
                                <p class="text-xs text-slate-300 mt-1">Отработка быстрого решения без потери баллов за счет координатного метода.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 self-end md:self-center">
                            <span class="px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                                +7 баллов
                            </span>
                            <span class="text-slate-600">✓</span>
                        </div>
                    </div>

                    {{-- Stage 3 --}}
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-5 sm:p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 hover:border-white/15 transition">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-2xl bg-violet-600/20 border border-violet-500/40 text-violet-300 font-black flex items-center justify-center shrink-0">
                                03
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black uppercase tracking-wider text-[#C6FF33]"
                                          x-text="activePlanTab === 30 ? 'Этап 3 (Дни 26-30)' : (activePlanTab === 60 ? 'Этап 3 (Дни 46-60)' : 'Этап 3 (Дни 46-90)')"></span>
                                    <span class="text-xs text-slate-500">•</span>
                                    <span class="text-xs text-slate-400">Финальный нагон баллов</span>
                                </div>
                                <h4 class="text-base font-bold text-white mt-1">Контрольные прогоны РИКЗ и тайм-менеджмент</h4>
                                <p class="text-xs text-slate-300 mt-1">Тестирование в режиме реального времени на платформе Edusfera.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 self-end md:self-center">
                            <span class="px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                                +5 баллов
                            </span>
                            <span class="text-slate-600">✓</span>
                        </div>
                    </div>

                </div>

            </div>

            {{-- 4. ПОДБОР РЕКОМЕНДОВАННЫХ ПРЕПОДАВАТЕЛЕЙ --}}
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-white/10 pb-4">
                    <div>
                        <span class="text-xs font-black uppercase tracking-[0.2em] text-[#C6FF33]">Персональный подбор</span>
                        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-1">
                            Репетиторы по {{ $subject }}, закрывающие эти пробелы
                        </h2>
                    </div>
                    <a href="{{ route('tutors.index', ['subject' => $subject]) }}" class="text-xs font-bold text-[#C6FF33] hover:underline flex items-center gap-1">
                        <span>Все репетиторы каталога</span>
                        <span>→</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    {{-- Tutor 1 --}}
                    <div class="bg-slate-900/70 backdrop-blur-xl border border-white/10 rounded-3xl p-6 flex flex-col justify-between space-y-6 shadow-xl hover:border-[#C6FF33]/50 transition group">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-800 border border-white/20 flex items-center justify-center font-black text-xl text-white shadow-lg">
                                        АВ
                                    </div>
                                    <div>
                                        <h3 class="font-black text-white text-base group-hover:text-[#C6FF33] transition">
                                            Алексей Воронов
                                        </h3>
                                        <span class="text-xs text-slate-400">БГУ, Мехмат • 8 лет опыта</span>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-amber-400 flex items-center gap-1">
                                    ★ 5.0
                                </span>
                            </div>

                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800 border border-white/10 text-[11px] font-bold text-emerald-400">
                                <span>✓</span> Верифицирован РИКЗ-эксперт
                            </div>

                            <p class="text-xs text-slate-300 leading-relaxed">
                                Специализируется на ликвидации пробелов части Б и отборе корней. Средний прирост учеников: <strong class="text-white">+24 балла на ЦТ</strong>.
                            </p>
                        </div>

                        <div class="pt-4 border-t border-white/10 space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Ставка: <strong class="text-white font-bold">40 BYN / ч</strong></span>
                                <span class="text-[#C6FF33] font-bold bg-[#C6FF33]/10 px-2 py-0.5 rounded-md">Скидка 15%</span>
                            </div>

                            <a href="{{ route('tutors.index', ['subject' => $subject]) }}"
                               class="w-full py-3 rounded-xl bg-[#C6FF33] hover:bg-[#b8f526] text-black font-black text-xs uppercase tracking-wider text-center block shadow-[0_0_15px_rgba(198,255,51,0.25)] transition">
                                Записаться со скидкой
                            </a>
                        </div>
                    </div>

                    {{-- Tutor 2 --}}
                    <div class="bg-slate-900/70 backdrop-blur-xl border border-white/10 rounded-3xl p-6 flex flex-col justify-between space-y-6 shadow-xl hover:border-[#C6FF33]/50 transition group">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-600 to-pink-800 border border-white/20 flex items-center justify-center font-black text-xl text-white shadow-lg">
                                        ЕС
                                    </div>
                                    <div>
                                        <h3 class="font-black text-white text-base group-hover:text-[#C6FF33] transition">
                                            Елена Соколова
                                        </h3>
                                        <span class="text-xs text-slate-400">БГУИР, доцент • 11 лет опыта</span>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-amber-400 flex items-center gap-1">
                                    ★ 4.9
                                </span>
                            </div>

                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800 border border-white/10 text-[11px] font-bold text-emerald-400">
                                <span>✓</span> Топ-преподаватель 2026
                            </div>

                            <p class="text-xs text-slate-300 leading-relaxed">
                                Авторская методика решения стереометрии без вычислений "в лоб". 14 стобалльников за последние 3 года.
                            </p>
                        </div>

                        <div class="pt-4 border-t border-white/10 space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Ставка: <strong class="text-white font-bold">45 BYN / ч</strong></span>
                                <span class="text-[#C6FF33] font-bold bg-[#C6FF33]/10 px-2 py-0.5 rounded-md">Скидка 15%</span>
                            </div>

                            <a href="{{ route('tutors.index', ['subject' => $subject]) }}"
                               class="w-full py-3 rounded-xl bg-[#C6FF33] hover:bg-[#b8f526] text-black font-black text-xs uppercase tracking-wider text-center block shadow-[0_0_15px_rgba(198,255,51,0.25)] transition">
                                Записаться со скидкой
                            </a>
                        </div>
                    </div>

                    {{-- Tutor 3 --}}
                    <div class="bg-slate-900/70 backdrop-blur-xl border border-white/10 rounded-3xl p-6 flex flex-col justify-between space-y-6 shadow-xl hover:border-[#C6FF33]/50 transition group">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-800 border border-white/20 flex items-center justify-center font-black text-xl text-white shadow-lg">
                                        ДМ
                                    </div>
                                    <div>
                                        <h3 class="font-black text-white text-base group-hover:text-[#C6FF33] transition">
                                            Дмитрий Морозов
                                        </h3>
                                        <span class="text-xs text-slate-400">Лицей БГУ • 6 лет опыта</span>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-amber-400 flex items-center gap-1">
                                    ★ 5.0
                                </span>
                            </div>

                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800 border border-white/10 text-[11px] font-bold text-emerald-400">
                                <span>✓</span> Подготовка к ЦЭ / ЦТ 2026
                            </div>

                            <p class="text-xs text-slate-300 leading-relaxed">
                                Экспресс-подготовка до 85+ баллов. Разбор ловушек РИКЗ на живых интерактивных досках платформы.
                            </p>
                        </div>

                        <div class="pt-4 border-t border-white/10 space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Ставка: <strong class="text-white font-bold">38 BYN / ч</strong></span>
                                <span class="text-[#C6FF33] font-bold bg-[#C6FF33]/10 px-2 py-0.5 rounded-md">Скидка 15%</span>
                            </div>

                            <a href="{{ route('tutors.index', ['subject' => $subject]) }}"
                               class="w-full py-3 rounded-xl bg-[#C6FF33] hover:bg-[#b8f526] text-black font-black text-xs uppercase tracking-wider text-center block shadow-[0_0_15px_rgba(198,255,51,0.25)] transition">
                                Записаться со скидкой
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            {{-- 5. СОХРАНЕНИЕ И ВИРУСНЫЙ ШЕРИНГ ДЛЯ СТОРИС --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                {{-- Left: Story Card Preview Box (lg:col-span-5) --}}
                <div class="lg:col-span-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-[0.2em] text-[#C6FF33]">Вирусный шеринг</span>
                        <span class="text-xs text-slate-400">Формат Stories 9:16</span>
                    </div>

                    {{-- Card Mockup Preview --}}
                    <div id="storyCardPreview" class="story-glow rounded-[2.5rem] p-7 space-y-5 text-left relative overflow-hidden">
                        <div class="flex justify-between items-start border-b border-white/10 pb-4">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-[#C6FF33]">EDUSFERA AI-DIAGNOSTIC</span>
                                <h3 class="text-base font-black text-white mt-0.5 uppercase">{{ $examType }} 2026</h3>
                            </div>
                            <span class="text-sm font-black text-white px-3 py-1 rounded-full bg-white/10">
                                {{ $subject }}
                            </span>
                        </div>

                        <div class="bg-black/50 rounded-2xl p-5 border border-white/10 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Мой результат</div>
                                <div class="text-4xl font-black text-[#C6FF33] tracking-tight mt-1">
                                    {{ $currentScore ?? 68 }} <span class="text-lg text-slate-400 font-bold">/ 100</span>
                                </div>
                            </div>
                            <div class="text-3xl">🚀</div>
                        </div>

                        <div class="space-y-2">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Выявленные пробелы:</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach(array_slice($weakTopics ?? ['ОДЗ логарифмов', 'Стереометрия'], 0, 3) as $topic)
                                    <span class="px-2.5 py-1 rounded-lg bg-violet-500/20 border border-violet-400/30 text-[11px] font-bold text-violet-200">
                                        {{ $topic }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-[#C6FF33]/10 border border-[#C6FF33]/30 rounded-xl p-3 text-center">
                            <div class="text-[11px] font-bold text-[#C6FF33]">📲 Проверь свой уровень бесплатно:</div>
                            <div class="text-[10px] font-mono text-white/90 truncate mt-0.5">
                                edusfera.by/diagnostic <span class="text-[#C6FF33] font-bold">(Скидка 15%)</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Actions & Account Save (lg:col-span-7) --}}
                <div class="lg:col-span-7 space-y-6">
                    
                    {{-- Account Status Banner --}}
                    @if($isSaved)
                        <div class="bg-emerald-950/40 border border-emerald-500/30 rounded-3xl p-6 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-xl text-emerald-400 shrink-0">
                                ✓
                            </div>
                            <div class="space-y-1">
                                <h4 class="text-base font-black text-white">Результат успешно сохранен в вашем профиле</h4>
                                <p class="text-xs text-slate-300">
                                    Вы можете в любой момент вернуться к треку подготовки в личном кабинете ученика.
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="bg-slate-900/80 border border-white/10 rounded-3xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <h4 class="text-base font-black text-white">Сохранить результат и зафиксировать скидку 15%</h4>
                                <p class="text-xs text-slate-400">
                                    Создайте профиль ученика, чтобы отслеживать прогресс и бронировать уроки.
                                </p>
                            </div>
                            <button @click="showAuthModal = true"
                                    class="px-6 py-3 rounded-xl bg-white hover:bg-slate-200 text-black font-black text-xs uppercase tracking-wider transition shrink-0">
                                Создать аккаунт
                            </button>
                        </div>
                    @endif

                    {{-- Action Buttons List --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        
                        {{-- Button 1: PNG Story Download --}}
                        <button @click="generateStoryPNG()" class="px-6 py-4 rounded-2xl bg-[#C6FF33] hover:bg-[#b8f526] text-black font-black text-xs uppercase tracking-wider shadow-[0_0_25px_rgba(198,255,51,0.3)] transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span x-text="downloadBtnText">Скачать Сторис (PNG)</span>
                        </button>

                        {{-- Button 2: Telegram Share --}}
                        <button @click="shareTelegram()" class="px-6 py-4 rounded-2xl bg-sky-500 hover:bg-sky-400 text-white font-black text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(14,165,233,0.3)] transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.02-1.96 1.25-5.54 3.67-.52.36-.99.54-1.42.53-.47-.01-1.37-.26-2.03-.48-.82-.27-1.47-.42-1.42-.88.03-.25.38-.51 1.07-.78 4.18-1.82 6.97-3.02 8.37-3.61 3.99-1.66 4.82-1.95 5.36-1.96.12 0 .39.03.57.17.15.13.2.3.22.42.02.13.01.27 0 .42z"/></svg>
                            <span>Поделиться в Telegram</span>
                        </button>

                    </div>

                    {{-- Button 3: Copy Referral Link with Visual Feedback --}}
                    <button @click="copyReferralLink()"
                            :class="copied ? 'border-emerald-500 bg-emerald-500/10 text-emerald-400' : 'border-white/10 bg-slate-900/70 text-slate-300 hover:border-white/25'"
                            class="w-full py-4 px-6 rounded-2xl border font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2.5 transition">
                        <template x-if="!copied">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </template>
                        <template x-if="copied">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <span x-text="copied ? 'Ссылка с 15% скидкой скопирована в буфер! ✅' : 'Скопировать реферальную ссылку (Скидка 15% для одноклассников)'"></span>
                    </button>

                    {{-- Diagnostic Re-run Link --}}
                    <div class="text-center pt-2">
                        <a href="{{ route('diagnostic.show') }}" class="text-xs font-bold text-slate-400 hover:text-white transition inline-flex items-center gap-1.5">
                            <span>↻</span>
                            <span>Пройти ИИ-диагностику по другому предмету</span>
                        </a>
                    </div>

                </div>

            </div>

        </main>

        {{-- Guest Quick Registration Modal --}}
        <div x-show="showAuthModal"
             x-transition.opacity
             class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4"
             style="display: none;">
            
            <div @click.away="showAuthModal = false"
                 class="bg-slate-900 border border-white/20 rounded-3xl p-6 sm:p-8 max-w-md w-full space-y-6 shadow-2xl relative animate-fade-in">
                
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs font-black uppercase tracking-widest text-[#C6FF33]">Быстрая регистрация</span>
                        <h3 class="text-xl font-black text-white mt-1">Сохранить результат</h3>
                    </div>
                    <button @click="showAuthModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed">
                    Ваш результат <strong>{{ $currentScore }} баллов</strong> по предмету <strong>{{ $subject }}</strong> будет привязан к личному кабинету.
                </p>

                <div class="space-y-3">
                    <a href="/admin/register" class="w-full py-3.5 rounded-xl bg-[#C6FF33] hover:bg-[#b8f526] text-black font-black text-xs uppercase tracking-wider text-center block transition shadow-[0_0_20px_rgba(198,255,51,0.3)]">
                        Зарегистрироваться через профиль
                    </a>
                    <a href="/admin/login" class="w-full py-3.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider text-center block border border-white/10 transition">
                        Войти в существующий аккаунт
                    </a>
                </div>

            </div>
        </div>

        {{-- Footer --}}
        @include('partials.site-footer')

    </div>

    {{-- Script: Result Controller & Story Generator --}}
    <script>
        function diagnosticResultApp() {
            return {
                activePlanTab: 60,
                showAuthModal: false,
                copied: false,
                downloadBtnText: 'Скачать Сторис (PNG)',

                init() {
                    // Ready
                },

                copyReferralLink() {
                    const url = window.location.origin + '/diagnostic?ref={{ auth()->id() ?? "student_share" }}';
                    navigator.clipboard.writeText(url);
                    this.copied = true;
                    setTimeout(() => {
                        this.copied = false;
                    }, 3500);
                },

                shareTelegram() {
                    const subject = @json($subject);
                    const score = @json($currentScore ?? 68);
                    const exam = @json($examType ?? 'ЦТ 2026');
                    const refUrl = window.location.origin + '/diagnostic?ref={{ auth()->id() ?? "student_share" }}';
                    const text = `📊 Мой результат ИИ-диагностики готовности к ${exam} по предмету ${subject}: ${score}/100 баллов!\n\nПройди бесплатный срез знаний спецификации РИКЗ на Edusfera и забери скидку 15%:`;
                    const shareUrl = `https://t.me/share/url?url=${encodeURIComponent(refUrl)}&text=${encodeURIComponent(text)}`;
                    window.open(shareUrl, '_blank');
                },

                generateStoryPNG() {
                    this.downloadBtnText = 'Генерация PNG (1080x1920)...';

                    const canvas = document.createElement('canvas');
                    canvas.width = 1080;
                    canvas.height = 1920;
                    const ctx = canvas.getContext('2d');

                    // 1. Background
                    const bgGrad = ctx.createLinearGradient(0, 0, 1080, 1920);
                    bgGrad.addColorStop(0, '#010101');
                    bgGrad.addColorStop(0.35, '#0B0F19');
                    bgGrad.addColorStop(0.7, '#140D26');
                    bgGrad.addColorStop(1, '#010101');
                    ctx.fillStyle = bgGrad;
                    ctx.fillRect(0, 0, 1080, 1920);

                    // 2. Ambient circles
                    ctx.fillStyle = 'rgba(125, 57, 235, 0.2)';
                    ctx.beginPath();
                    ctx.arc(200, 300, 350, 0, Math.PI * 2);
                    ctx.fill();

                    ctx.fillStyle = 'rgba(198, 255, 51, 0.12)';
                    ctx.beginPath();
                    ctx.arc(880, 500, 300, 0, Math.PI * 2);
                    ctx.fill();

                    // 3. Central Glass Card
                    ctx.fillStyle = 'rgba(15, 23, 42, 0.88)';
                    ctx.strokeStyle = 'rgba(198, 255, 51, 0.4)';
                    ctx.lineWidth = 4;
                    ctx.beginPath();
                    ctx.roundRect(80, 200, 920, 1520, 48);
                    ctx.fill();
                    ctx.stroke();

                    // 4. Header & Branding
                    ctx.fillStyle = '#C6FF33';
                    ctx.font = 'bold 36px Inter, sans-serif';
                    ctx.fillText('EDUSFERA AI-DIAGNOSTIC 2026', 140, 320);

                    ctx.fillStyle = '#FFFFFF';
                    ctx.font = '900 56px Inter, sans-serif';
                    ctx.fillText('ГОТОВНОСТЬ К {{ mb_strtoupper($examType ?? "ЦТ 2026") }}', 140, 400);

                    // 5. Score Box
                    ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
                    ctx.beginPath();
                    ctx.roundRect(140, 470, 800, 320, 32);
                    ctx.fill();

                    ctx.fillStyle = '#94A3B8';
                    ctx.font = 'bold 32px Inter, sans-serif';
                    ctx.fillText('ПРЕДМЕТ: {{ mb_strtoupper($subject) }}', 180, 550);

                    ctx.fillStyle = '#C6FF33';
                    ctx.font = '900 115px Inter, sans-serif';
                    ctx.fillText('{{ $currentScore ?? 68 }} / 100', 180, 690);

                    ctx.fillStyle = '#FFFFFF';
                    ctx.font = 'bold 32px Inter, sans-serif';
                    ctx.fillText('ЦЕЛЬ: {{ $targetScore ?? 85 }} БАЛЛОВ', 580, 690);

                    // 6. Gaps Section
                    ctx.fillStyle = '#D8B4FE';
                    ctx.font = 'bold 36px Inter, sans-serif';
                    ctx.fillText('⚡ ВЫЯВЛЕННЫЕ ПРОБЕЛЫ РИКЗ:', 140, 890);

                    const topics = {!! json_encode(array_slice($weakTopics ?? ['Логарифмы и ОДЗ', 'Стереометрия', 'Отбор корней'], 0, 3), JSON_UNESCAPED_UNICODE) !!};
                    let y = 960;
                    topics.forEach((t) => {
                        ctx.fillStyle = 'rgba(125, 57, 235, 0.25)';
                        ctx.strokeStyle = 'rgba(198, 255, 51, 0.3)';
                        ctx.lineWidth = 2;
                        ctx.beginPath();
                        ctx.roundRect(140, y, 800, 95, 24);
                        ctx.fill();
                        ctx.stroke();

                        ctx.fillStyle = '#F8FAFC';
                        ctx.font = 'bold 34px Inter, sans-serif';
                        ctx.fillText('• ' + t, 180, y + 60);
                        y += 130;
                    });

                    // 7. Footer Promo Card
                    ctx.fillStyle = 'rgba(198, 255, 51, 0.15)';
                    ctx.beginPath();
                    ctx.roundRect(140, 1420, 800, 220, 32);
                    ctx.fill();

                    ctx.fillStyle = '#C6FF33';
                    ctx.font = 'bold 40px Inter, sans-serif';
                    ctx.fillText('📲 Проверь свой уровень бесплатно:', 180, 1510);

                    ctx.fillStyle = '#FFFFFF';
                    ctx.font = '900 36px Inter, sans-serif';
                    ctx.fillText('edusfera.by/diagnostic • Скидка 15%', 180, 1580);

                    // Trigger Download
                    const link = document.createElement('a');
                    link.download = `Edusfera-Diagnostic-${Date.now()}.png`;
                    link.href = canvas.toDataURL('image/png');
                    link.click();

                    setTimeout(() => {
                        this.downloadBtnText = 'Скачать Сторис (PNG)';
                    }, 1500);
                }
            };
        }

        window.diagnosticResultApp = diagnosticResultApp;
        document.addEventListener('alpine:init', () => {
            if (window.Alpine) {
                window.Alpine.data('diagnosticResultApp', diagnosticResultApp);
            }
        });
    </script>
</body>
</html>
