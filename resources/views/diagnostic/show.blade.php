<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ИИ-диагностика готовности к ЦТ и ЦЭ 2026 — Edusfera</title>
    <meta name="description" content="Индивидуальная экспресс-диагностика готовности к ЦЭ и ЦТ. Задания по спецификации РИКЗ, расчет прогнозного балла и карта пробелов за 4 минуты.">
    <meta name="keywords" content="тест цт онлайн, диагностика знаний цэ, проверить уровень цт, рикз тесты, подготовка к цт бесплатно">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="https://edusfera.by/diagnostic">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_BY">
    <meta property="og:site_name" content="Edusfera">
    <meta property="og:title" content="ИИ-диагностика уровня ЦТ/ЦЭ за 4 минуты — Edusfera">
    <meta property="og:description" content="Пройдите интерактивную диагностику по стандартам РИКЗ: узнайте свой прогнозный балл и слабые темы прямо сейчас.">
    <meta property="og:url" content="https://edusfera.by/diagnostic">
    <meta property="og:image" content="https://edusfera.by/og-image.png">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="ИИ-диагностика уровня ЦТ и ЦЭ — Edusfera">
    <meta name="twitter:description" content="Калиброванные задания по спецификации РИКЗ. Мгновенная карта пробелов.">
    <meta name="twitter:image" content="https://edusfera.by/og-image.png">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@graph": [
        {
          "@type": "BreadcrumbList",
          "itemListElement": [
            {
              "@type": "ListItem",
              "position": 1,
              "name": "Главная",
              "item": "https://edusfera.by/"
            },
            {
              "@type": "ListItem",
              "position": 2,
              "name": "ИИ-диагностика",
              "item": "https://edusfera.by/diagnostic"
            }
          ]
        },
        {
          "@type": "Quiz",
          "name": "ИИ-диагностика готовности к ЦТ и ЦЭ",
          "description": "Индивидуальный экспресс-тест для определения текущего уровня подготовки к ЦТ/ЦЭ по спецификациям РИКЗ.",
          "educationalLevel": "Среднее образование, Абитуриенты",
          "provider": {
            "@type": "EducationalOrganization",
            "name": "Edusfera",
            "url": "https://edusfera.by/"
          },
          "isAccessibleForFree": true
        }
      ]
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">

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

        /* Apple Range Slider */
        input[type=range].apple-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 999px;
            background: #e5e5ea;
            outline: none;
            cursor: pointer;
        }
        input[type=range].apple-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.06);
            cursor: grab;
            transition: transform 0.15s ease;
        }
        input[type=range].apple-slider::-webkit-slider-thumb:hover {
            transform: scale(1.1);
        }
        input[type=range].apple-slider::-moz-range-thumb {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            cursor: grab;
        }

        /* Apple-style smooth spinner */
        @keyframes apple-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .apple-spinner {
            animation: apple-spin 1s linear infinite;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between" x-data="diagnosticApp()" x-init="init()" @keydown.window="handleKey($event)">

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
                    Диагностика РИКЗ 2026
                </span>
            </div>

            <div class="flex items-center gap-3">
                <template x-if="phase === 'testing'">
                    <button @click="confirmExit()" class="text-xs font-semibold text-[#6e6e73] hover:text-[#1d1d1f] px-3 py-1.5 rounded-full hover:bg-slate-100 transition-colors">
                        Прервать тест
                    </button>
                </template>

                <template x-if="phase === 'setup'">
                    <a href="{{ route('tutors.index') }}" class="text-xs font-semibold text-[#6e6e73] hover:text-[#1d1d1f] px-3.5 py-1.5 rounded-full border border-[#d2d2d7] hover:bg-white transition-all shadow-xs">
                        Каталог репетиторов
                    </a>
                </template>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="flex-1 max-w-4xl mx-auto w-full px-4 sm:px-6 py-8 sm:py-14">

        {{-- ========================================================
             PHASE 1: SETUP & CONFIGURATION
             ======================================================== --}}
        <div x-show="phase === 'setup'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-10">
            
            {{-- Hero Title --}}
            <div class="text-center max-w-2xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Спецификация РИКЗ 2026</span>
                    <span class="text-slate-400">·</span>
                    <span>Бесплатно</span>
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-[42px] font-extrabold tracking-tight text-[#1d1d1f] leading-tight">
                    Оцените готовность к экзамену
                </h1>
                <p class="text-base sm:text-lg text-[#6e6e73] leading-relaxed font-normal">
                    6 калиброванных заданий с ключевыми ловушками составителей. Точный расчет текущего балла и персональная карта пробелов за 4 минуты.
                </p>
            </div>

            {{-- Main Config Card --}}
            <div class="bg-white rounded-3xl border border-[#e5e5ea] shadow-xs p-6 sm:p-10 space-y-8">
                
                {{-- 1. Subject Selector --}}
                <div class="space-y-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#86868b]">
                        1. Выберите предмет для диагностики
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 sm:gap-3">
                        <template x-for="sub in subjectsList" :key="sub.name">
                            <button type="button" @click="selectSubject(sub.name)"
                                    :class="selectedSubject === sub.name 
                                        ? 'border-[#7D39EB] bg-[#f8f7ff] text-[#1d1d1f] shadow-xs ring-1 ring-[#7D39EB]' 
                                        : 'border-[#e5e5ea] bg-white text-[#6e6e73] hover:border-[#b0b0b8] hover:text-[#1d1d1f]'"
                                    class="p-3.5 sm:p-4 rounded-2xl border text-left flex flex-col justify-between transition-all cursor-pointer min-h-[90px]">
                                <span class="text-2xl mb-2" x-text="sub.icon"></span>
                                <div>
                                    <span class="block text-xs sm:text-[13px] font-bold leading-tight" x-text="sub.name"></span>
                                    <span class="block text-[11px] text-[#86868b] mt-0.5" x-text="sub.tasksCount + ' заданий'"></span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- 2. Exam Type & Benchmark --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-6 border-t border-[#f0f0f4]">
                    
                    {{-- Exam Type Segmented Control --}}
                    <div class="space-y-3">
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#86868b]">
                            2. Формат экзамена
                        </label>
                        <div class="bg-[#f0f0f4] p-1 rounded-2xl flex items-center gap-1">
                            <button type="button" @click="selectedExam = 'ЦТ 2026'"
                                    :class="selectedExam === 'ЦТ 2026' ? 'bg-white text-[#1d1d1f] shadow-xs font-bold' : 'text-[#6e6e73] font-semibold hover:text-[#1d1d1f]'"
                                    class="flex-1 py-2.5 rounded-xl text-xs sm:text-sm text-center transition-all cursor-pointer">
                                ЦТ 2026
                            </button>
                            <button type="button" @click="selectedExam = 'ЦЭ 2026'"
                                    :class="selectedExam === 'ЦЭ 2026' ? 'bg-white text-[#1d1d1f] shadow-xs font-bold' : 'text-[#6e6e73] font-semibold hover:text-[#1d1d1f]'"
                                    class="flex-1 py-2.5 rounded-xl text-xs sm:text-sm text-center transition-all cursor-pointer">
                                ЦЭ 2026
                            </button>
                        </div>
                        <p class="text-xs text-[#86868b]">
                            Шкала первичных и тестовых баллов полностью идентична для ЦТ и ЦЭ.
                        </p>
                    </div>

                    {{-- Target Score Slider --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#86868b]">
                                3. Желаемый результат
                            </label>
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-extrabold text-[#1d1d1f]" x-text="targetScore"></span>
                                <span class="text-xs font-bold text-[#86868b]">баллов</span>
                            </div>
                        </div>

                        <input type="range" min="60" max="100" step="1" x-model.number="targetScore" class="apple-slider">

                        <div class="flex items-center justify-between text-[11px] text-[#86868b]">
                            <span>60</span>
                            <span :class="targetScoreBenchmark.color" class="font-semibold px-2 py-0.5 rounded-full bg-slate-100" x-text="targetScoreBenchmark.label"></span>
                            <span>100</span>
                        </div>
                    </div>

                </div>

                {{-- Feature Summary Cards --}}
                <div class="pt-6 border-t border-[#f0f0f4] grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 rounded-2xl bg-[#fbfbfd] border border-[#e5e5ea] space-y-1">
                        <div class="text-xs font-bold text-[#1d1d1f]">Спецификация 2026</div>
                        <div class="text-[12px] text-[#6e6e73] leading-relaxed">Задания части А и Б, составленные по структуре актуального РТ и РИКЗ.</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#fbfbfd] border border-[#e5e5ea] space-y-1">
                        <div class="text-xs font-bold text-[#1d1d1f]">Анализ ловушек</div>
                        <div class="text-[12px] text-[#6e6e73] leading-relaxed">Разбор типовых ошибок, на которых срезаются до 70% абитуриентов.</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#fbfbfd] border border-[#e5e5ea] space-y-1">
                        <div class="text-xs font-bold text-[#1d1d1f]">Мгновенный план</div>
                        <div class="text-[12px] text-[#6e6e73] leading-relaxed">Список тем первой необходимости для гарантированного роста балла.</div>
                    </div>
                </div>

                {{-- Action Button --}}
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs text-[#86868b]">
                        Время прохождения: ~4 минуты · Регистрация не требуется
                    </div>
                    <button type="button" @click="startDiagnostic()"
                            class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-[#1d1d1f] hover:bg-[#000000] text-white font-bold text-sm tracking-tight transition-all shadow-sm hover:scale-[1.02] cursor-pointer flex items-center justify-center gap-2">
                        <span>Начать диагностику</span>
                        <span>→</span>
                    </button>
                </div>

            </div>

        </div>

        {{-- ========================================================
             PHASE 2: INTERACTIVE ASSESSMENT (Testing)
             ======================================================== --}}
        <div x-show="phase === 'testing'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
            
            {{-- Test Header & Progress Bar --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between text-xs font-semibold text-[#86868b]">
                    <div class="flex items-center gap-2">
                        <span class="text-[#1d1d1f] font-bold" x-text="selectedSubject"></span>
                        <span>·</span>
                        <span x-text="selectedExam"></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>Задание</span>
                        <span class="text-[#1d1d1f] font-bold text-sm" x-text="(currentIndex + 1)"></span>
                        <span>из</span>
                        <span x-text="currentQuestions.length"></span>
                    </div>
                </div>

                {{-- Slim Progress Bar --}}
                <div class="w-full h-1.5 bg-[#e5e5ea] rounded-full overflow-hidden">
                    <div class="h-full bg-[#7D39EB] transition-all duration-300 ease-out rounded-full"
                         :style="'width: ' + (((currentIndex + 1) / currentQuestions.length) * 100) + '%'"></div>
                </div>
            </div>

            {{-- Question Card --}}
            <div class="bg-white rounded-3xl border border-[#e5e5ea] shadow-xs p-6 sm:p-10 space-y-6">
                
                {{-- Badges --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-xs font-semibold"
                          x-text="currentQuestion.code"></span>
                    <span class="px-2.5 py-1 rounded-full bg-violet-50 text-violet-700 border border-violet-200/60 text-xs font-semibold"
                          x-text="currentQuestion.theme"></span>
                </div>

                {{-- Question Text --}}
                <div class="space-y-4">
                    <h2 class="text-lg sm:text-xl font-bold text-[#1d1d1f] leading-snug" x-text="currentQuestion.text"></h2>

                    {{-- Formula / Math Block (Clean Apple styling, no dark terminal) --}}
                    <template x-if="currentQuestion.codeBlock">
                        <div class="p-4 sm:p-5 rounded-2xl bg-[#f8f9fc] border border-[#e5e5ea] font-mono text-sm sm:text-base text-[#1d1d1f] leading-relaxed">
                            <span x-text="currentQuestion.codeBlock"></span>
                        </div>
                    </template>
                </div>

                {{-- Options Stack (Part A) --}}
                <template x-if="currentQuestion.options && currentQuestion.options.length > 0">
                    <div class="space-y-2.5 pt-2">
                        <template x-for="(opt, oIdx) in currentQuestion.options" :key="oIdx">
                            <div @click="selectOption(oIdx)"
                                 :class="answers[currentIndex] === oIdx 
                                     ? 'border-[#7D39EB] bg-[#f8f7ff] text-[#1d1d1f] shadow-xs ring-1 ring-[#7D39EB]' 
                                     : 'border-[#e5e5ea] bg-white text-[#424245] hover:border-[#b0b0b8] hover:bg-[#fafafc]'"
                                 class="p-4 rounded-2xl border flex items-center justify-between transition-all cursor-pointer group">
                                
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div :class="answers[currentIndex] === oIdx 
                                            ? 'bg-[#7D39EB] text-white' 
                                            : 'bg-[#f0f0f4] text-[#6e6e73] group-hover:bg-[#e5e5ea]'"
                                         class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-colors"
                                         x-text="['A', 'B', 'C', 'D', 'E'][oIdx] || (oIdx + 1)"></div>
                                    <span class="text-sm sm:text-base font-medium leading-normal" x-text="opt"></span>
                                </div>

                                <div class="shrink-0 pl-3">
                                    <div :class="answers[currentIndex] === oIdx ? 'border-[#7D39EB] bg-[#7D39EB]' : 'border-[#d2d2d7] bg-white'"
                                         class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors">
                                        <template x-if="answers[currentIndex] === oIdx">
                                            <svg class="w-3 h-3 text-white" viewBox="0 0 12 12" fill="none">
                                                <path d="M2.5 6L5 8.5L9.5 3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Navigation Actions --}}
                <div class="pt-6 border-t border-[#f0f0f4] flex items-center justify-between gap-4">
                    <button type="button" @click="prevQuestion()" :disabled="currentIndex === 0"
                            :class="currentIndex === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-slate-100 cursor-pointer'"
                            class="px-5 py-2.5 rounded-xl border border-[#d2d2d7] text-xs font-bold text-[#6e6e73] hover:text-[#1d1d1f] transition-all">
                        ← Назад
                    </button>

                    <div class="flex items-center gap-3">
                        <span class="hidden sm:inline text-[11px] text-[#86868b]">Клавиши 1–4 и Enter</span>

                        <template x-if="currentIndex < currentQuestions.length - 1">
                            <button type="button" @click="nextQuestion()"
                                    class="px-6 py-2.5 rounded-xl bg-[#1d1d1f] hover:bg-[#000000] text-white text-xs font-bold transition-all shadow-xs cursor-pointer">
                                Следующее задание →
                            </button>
                        </template>

                        <template x-if="currentIndex === currentQuestions.length - 1">
                            <button type="button" @click="finishAndAnalyze()"
                                    class="px-6 py-2.5 rounded-xl bg-[#7D39EB] hover:bg-[#6827d6] text-white text-xs font-bold transition-all shadow-xs cursor-pointer">
                                Рассчитать результат →
                            </button>
                        </template>
                    </div>
                </div>

            </div>

        </div>

        {{-- ========================================================
             PHASE 3: CALM EVALUATION (Analyzing)
             ======================================================== --}}
        <div x-show="phase === 'analyzing'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-md mx-auto py-16 text-center space-y-6">
            
            <div class="bg-white rounded-3xl border border-[#e5e5ea] shadow-xs p-8 sm:p-10 space-y-6">
                
                {{-- Clean Circular Progress Indicator --}}
                <div class="relative w-24 h-24 mx-auto flex items-center justify-center">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="42" stroke="#f0f0f4" stroke-width="7" fill="none"/>
                        <circle cx="50" cy="50" r="42" stroke="#7D39EB" stroke-width="7" fill="none"
                                stroke-dasharray="263.89"
                                :stroke-dashoffset="263.89 * (1 - (analysisPercent / 100))"
                                stroke-linecap="round"
                                class="transition-all duration-100 ease-out"/>
                    </svg>
                    <span class="absolute text-xl font-extrabold text-[#1d1d1f]" x-text="analysisPercent + '%'"></span>
                </div>

                <div class="space-y-2">
                    <h3 class="text-base font-bold text-[#1d1d1f]">Обработка результатов</h3>
                    <p class="text-xs text-[#6e6e73] leading-relaxed min-h-[36px]" x-text="currentStatusText"></p>
                </div>

                <div class="w-full h-1 bg-[#f0f0f4] rounded-full overflow-hidden">
                    <div class="h-full bg-[#7D39EB] transition-all duration-100 ease-out rounded-full"
                         :style="'width: ' + analysisPercent + '%'"></div>
                </div>

            </div>

        </div>

    </main>

    {{-- Universal Minimalist Apple Footer --}}
    @include('partials.site-footer')

    <script>
        function diagnosticApp() {
            return {
                phase: 'setup', // 'setup' | 'testing' | 'analyzing'
                selectedSubject: @json($subject ?? 'Математика'),
                selectedExam: @json($examType ?? 'ЦТ 2026'),
                targetScore: @json($targetScore ?? 85),
                currentIndex: 0,
                answers: [],
                analysisPercent: 0,
                currentStatusText: 'Проверка ответов по ключам спецификации РИКЗ 2026...',

                subjectsList: [
                    { name: 'Математика', icon: '📐', tasksCount: 6 },
                    { name: 'Русский язык', icon: '✍️', tasksCount: 6 },
                    { name: 'Физика', icon: '⚡', tasksCount: 6 },
                    { name: 'Английский язык', icon: '🇬🇧', tasksCount: 6 },
                    { name: 'Белорусский язык', icon: '🇧🇾', tasksCount: 6 },
                ],

                // Curated RIKZ 2026 specification questions bank
                questionsDb: {
                    'Математика': [
                        {
                            code: 'РИКЗ А3 · Алгебра',
                            theme: 'Свойства корней и степеней',
                            text: 'Вычислите точное значение числового выражения:',
                            codeBlock: '√[3](54) · √[3](4)  −  √50 / √2',
                            options: ['1', '6', '4', '2'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Ошибка в свойствах корней одинаковой степени или извлечении корня из частного.'
                        },
                        {
                            code: 'РИКЗ А7 · Алгебра',
                            theme: 'Логарифмические уравнения (ловушка ОДЗ)',
                            text: 'Укажите сумму всех действительных корней уравнения:',
                            codeBlock: 'log₂(x² − 3x) = 2',
                            options: ['3', '4', '−1', '5'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Потеря отрицательного корня (−1) из-за ложной уверенности, что аргумент логарифма не может содержать отрицательный x.'
                        },
                        {
                            code: 'РИКЗ А12 · Планиметрия',
                            theme: 'Прямоугольный треугольник и тригонометрия',
                            text: 'В прямоугольном треугольнике гипотенуза равна 20, а sin α = 0.6. Найдите длину катета, прилежащего к углу α.',
                            codeBlock: 'c = 20,  sin α = 0.6  →  Найти: прилежащий катет b',
                            options: ['12', '16', '14', '8'],
                            correct: 1,
                            gapLoss: 4,
                            mistake: 'Путаница между синусом (противолежащий катет) и косинусом (прилежащий катет = 20 · 0.8 = 16).'
                        },
                        {
                            code: 'РИКЗ Б2 · Тригонометрия',
                            theme: 'Тригонометрические уравнения (отбор корней)',
                            text: 'Сколько корней уравнения cos(2x) − sin(x) = 0 принадлежит отрезку [0; π]?',
                            codeBlock: 'cos(2x) − sin(x) = 0,  x ∈ [0; π]',
                            options: ['1 корень', '2 корня', '3 корня', '4 корня'],
                            correct: 1,
                            gapLoss: 6,
                            mistake: 'Неверное разложение cos(2x) = 1 − 2sin²(x) или включение постороннего корня 3π/2.'
                        },
                        {
                            code: 'РИКЗ Б5 · Неравенства',
                            theme: 'Показательные и логарифмические неравенства',
                            text: 'Решите неравенство со сменой знака основания:',
                            codeBlock: 'log₀.₅(2x − 6) ≥ −2',
                            options: ['(3; 5]', '[3; 5]', '(−∞; 5]', '(3; +∞)'],
                            correct: 0,
                            gapLoss: 7,
                            mistake: 'Забыта проверка ОДЗ (2x − 6 > 0 => x > 3), что приводит к грубейшей потере баллов на ЦТ/ЦЭ.'
                        },
                        {
                            code: 'РИКЗ Б10 · Стереометрия',
                            theme: 'Расстояния и сечения в пространстве',
                            text: 'В правильной четырехугольной призме со стороной основания 4 и высотой 6 найдите расстояние от вершины основания до плоскости диагонального сечения.',
                            codeBlock: 'a = 4,  h = 6  →  Найти: d(A, BDD₁B₁)',
                            options: ['2√2', '4√2', '4', '2√3'],
                            correct: 0,
                            gapLoss: 7,
                            mistake: 'Неверное построение перпендикуляра из вершины квадрата к его диагонали (половина диагонали: 4√2 / 2 = 2√2).'
                        }
                    ],
                    'Русский язык': [
                        {
                            code: 'РИКЗ А2 · Орфография',
                            theme: 'Слитное и раздельное написание НЕ',
                            text: 'В каком варианте НЕ пишется раздельно со словом?',
                            codeBlock: '1) (не)прочитанная вовремя книга\n2) (не)годующий взгляд\n3) (не)высокий, но крутой холм\n4) крайне (не)осмотрительно',
                            options: ['(не)прочитанная вовремя книга', '(не)годующий взгляд', '(не)высокий, но крутой холм', 'крайне (не)осмотрительно'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Невнимательность к зависимому слову «вовремя» при полном причастии, требующему раздельного написания.'
                        },
                        {
                            code: 'РИКЗ А5 · Пунктуация',
                            theme: 'Сложносочиненное предложение с общим членом',
                            text: 'Укажите предложение, в котором запятая перед союзом И НЕ ставится:',
                            codeBlock: '1) В саду пахло яблоками и тихо шумел ветер.\n2) Пошел дождь и мы побежали домой.\n3) Солнце село и на небе зажглись звезды.\n4) Урок окончился и дети выбежали в коридор.',
                            options: ['В саду пахло яблоками и тихо шумел ветер.', 'Пошел дождь и мы побежали домой.', 'Солнце село и на небе зажглись звезды.', 'Урок окончился и дети выбежали в коридор.'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Пропуск общего второстепенного члена («В саду»), отменяющего запятую перед И в ССП.'
                        },
                        {
                            code: 'РИКЗ А10 · Орфография',
                            theme: 'Правописание корней с чередованием',
                            text: 'В каком слове на месте пропуска пишется буква А?',
                            codeBlock: '1) прик..саться\n2) зам..реть\n3) непром..каемый\n4) расст..лать',
                            options: ['прик..саться', 'зам..реть', 'непром..каемый', 'расст..лать'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Путаница между правилом суффикса -А- (кас/кос) и смысловыми корнями (мак/мок).'
                        },
                        {
                            code: 'РИКЗ Б1 · Нормы языка',
                            theme: 'Орфоэпические нормы (ударение)',
                            text: 'Укажите слово с верным ударением по нормам РИКЗ 2026:',
                            codeBlock: '1) жалюзИ\n2) блеклО\n3) включИт\n4) слИвовый',
                            options: ['жалюзИ', 'блеклО', 'включИт', 'слИвовый'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Французское происхождение слова жалюзи фиксирует ударение исключительно на последний слог.'
                        },
                        {
                            code: 'РИКЗ Б4 · Синтаксис',
                            theme: 'Синтаксические нормы (деепричастный оборот)',
                            text: 'Укажите грамматически правильное продолжение предложения:',
                            codeBlock: 'Возвращаясь вечером домой, ...',
                            options: ['я встретил старого школьного друга.', 'пошел сильный проливной дождь.', 'мне стало очень грустно.', 'ветер срывал последние листья.'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Субъект действия деепричастия обязан совпадать с подлежащим предложения.'
                        },
                        {
                            code: 'РИКЗ Б8 · Орфография',
                            theme: 'Сложные случаи Н и НН в суффиксах',
                            text: 'В каком слове пишется удвоенная НН?',
                            codeBlock: '1) ране..ый в плечо боец\n2) плете..ая корзина\n3) кова..ый сундук\n4) сви..ой окорок',
                            options: ['ране..ый в плечо боец', 'плете..ая корзина', 'кова..ый сундук', 'сви..ой окорок'],
                            correct: 0,
                            gapLoss: 7,
                            mistake: 'Наличие зависимого слова («в плечо») превращает отглагольное прилагательное в причастие с НН.'
                        }
                    ],
                    'Физика': [
                        {
                            code: 'РИКЗ А2 · Кинематика',
                            theme: 'Равноускоренное движение и графики',
                            text: 'По графику зависимости скорости v(t) определите путь, пройденный телом за первые 4 секунды:',
                            codeBlock: 'v₀ = 0,  v(4) = 12 м/с (линейный рост)',
                            options: ['24 м', '48 м', '12 м', '36 м'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Путь при равноускоренном движении численно равен площади треугольника под графиком: (12 · 4) / 2 = 24 м.'
                        },
                        {
                            code: 'РИКЗ А6 · Динамика',
                            theme: 'Закон сохранения импульса',
                            text: 'Тележка массой 2 кг, движущаяся со скоростью 3 м/с, сцепляется с неподвижной тележкой массой 4 кг. Какова скорость после сцепки?',
                            codeBlock: 'm₁ = 2 кг,  v₁ = 3 м/с,  m₂ = 4 кг,  v₂ = 0',
                            options: ['1 м/с', '1.5 м/с', '2 м/с', '0.5 м/с'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Абсолютно неупругий удар: v = (m₁v₁) / (m₁ + m₂) = 6 / 6 = 1 м/с.'
                        },
                        {
                            code: 'РИКЗ А11 · МКТ',
                            theme: 'Изопроцессы идеального газа',
                            text: 'При изохорном нагревании температура газа увеличилась в 1.5 раза. Начальное давление было 120 кПа. Каково конечное давление?',
                            codeBlock: 'V = const,  T₂ = 1.5 T₁,  p₁ = 120 кПа',
                            options: ['180 кПа', '160 кПа', '240 кПа', '80 кПа'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'При изохорном процессе p/T = const, следовательно p₂ = 1.5 · 120 = 180 кПа.'
                        },
                        {
                            code: 'РИКЗ Б1 · Электродинамика',
                            theme: 'Закон Ома для полной цепи',
                            text: 'Источник тока с ЭДС 12 В и внутренним сопротивлением 1 Ом подключен к резистору 5 Ом. Какова сила тока в цепи?',
                            codeBlock: 'E = 12 В,  r = 1 Ом,  R = 5 Ом',
                            options: ['2 А', '2.4 А', '1.2 А', '3 А'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Закон Ома для полной цепи: I = E / (R + r) = 12 / (5 + 1) = 2 А.'
                        },
                        {
                            code: 'РИКЗ Б3 · Оптика',
                            theme: 'Закон преломления света и полное отражение',
                            text: 'Предельный угол полного внутреннего отражения на границе стекло-воздух равен 30°. Чему равен показатель преломления стекла?',
                            codeBlock: 'sin α_пред = 1 / n  (α_пред = 30°)',
                            options: ['2', '1.5', '1.73', '1.33'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Показатель преломления n = 1 / sin(30°) = 1 / 0.5 = 2.'
                        },
                        {
                            code: 'РИКЗ Б6 · Колебания',
                            theme: 'Период колебаний пружинного и математического маятника',
                            text: 'Во сколько раз изменится период колебаний математического маятника при увеличении длины нити в 4 раза?',
                            codeBlock: 'T = 2π √(L / g),  L₂ = 4 L₁',
                            options: ['увеличится в 2 раза', 'увеличится в 4 раза', 'уменьшится в 2 раза', 'не изменится'],
                            correct: 0,
                            gapLoss: 7,
                            mistake: 'Период пропорционален корню из длины: √4 = 2.'
                        }
                    ],
                    'Английский язык': [
                        {
                            code: 'РИКЗ А3 · Grammar',
                            theme: 'Conditionals (Mixed Conditionals)',
                            text: 'Choose the correct form to complete the sentence:',
                            codeBlock: 'If he _____ the train yesterday, he would be in Minsk right now.',
                            options: ['had not missed', 'did not miss', 'would not miss', 'has not missed'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Смешанный тип условных предложений: условие в прошлом (Past Perfect) и следствие в настоящем.'
                        },
                        {
                            code: 'РИКЗ А8 · Vocabulary',
                            theme: 'Dependent Prepositions',
                            text: 'Fill in the correct preposition according to formal academic norms:',
                            codeBlock: "The entire staff is highly dedicated _____ improving students' test results.",
                            options: ['to', 'with', 'for', 'at'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Прилагательное dedicated всегда требует предлога to.'
                        },
                        {
                            code: 'РИКЗ Б1 · Word Formation',
                            theme: 'Prefixes and Suffixes',
                            text: 'Form the correct antonym of the word in capitals:',
                            codeBlock: 'The unexpected results proved to be completely _____ (EXPECTED).',
                            options: ['unexpected', 'unexpecting', 'non-expected', 'inexpected'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Ошибочный выбор префикса (non-/in-) вместо нормативного un- для expected.'
                        },
                        {
                            code: 'РИКЗ Б4 · Agreement',
                            theme: 'Subject-Verb Agreement (Proximity Rule)',
                            text: 'Identify the grammatically correct sentence according to RIKZ specification:',
                            codeBlock: 'Neither the head tutor nor the high school students _____ present at the conference.',
                            options: ['were', 'was', 'is', 'has been'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Правило близости при neither... nor: глагол согласуется с ближайшим подлежащим (students were).'
                        },
                        {
                            code: 'РИКЗ Б7 · Passive Voice',
                            theme: 'Passive Infinitive and Reporting Verbs',
                            text: 'Choose the correct form to complete the sentence:',
                            codeBlock: 'The new scientific library is reported _____ opened next September.',
                            options: ['to be', 'being', 'having been', 'to have'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Конструкция Complex Subject требует инфинитива: reported to be opened.'
                        },
                        {
                            code: 'РИКЗ Б9 · Articles',
                            theme: 'Definite and Zero Articles with Geographic Names',
                            text: 'Choose the correct articles in order of appearance:',
                            codeBlock: '_____ lake Baikal is deeper than _____ Baltic Sea.',
                            options: ['— / the', 'the / the', 'the / —', '— / —'],
                            correct: 0,
                            gapLoss: 7,
                            mistake: 'Со словом Lake артикль отсутствует (Lake Baikal), а с названиями морей обязателен the (the Baltic Sea).'
                        }
                    ],
                    'Белорусский язык': [
                        {
                            code: 'РИКЗ А1 · Арфаграфія',
                            theme: 'Правапіс галосных О, Э, А (аканне)',
                            text: 'Адзначце слова, у якім на месцы пропуску пішацца літара А:',
                            codeBlock: '1) кр..вавы\n2) ш..калад\n3) р..монт\n4) б..тон',
                            options: ['кр..вавы', 'ш..калад', 'р..монт', 'б..тон'],
                            correct: 0,
                            gapLoss: 4,
                            mistake: 'Памылка ў правіле акання: пад уплывам націску ў корані (кроў -> крывавы/крававы).'
                        },
                        {
                            code: 'РИКЗ А4 · Арфаграфія',
                            theme: 'Правапіс падоўжаных зычных',
                            text: 'У якім слове пішацца падаўжэнне зычных?',
                            codeBlock: '1) насен..е\n2) мыш..у\n3) ліс..е\n4) суц..е',
                            options: ['насен..е', 'мыш..у', 'ліс..е', 'суц..е'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Падаўжэнне зычных паміж двума голосными ў назоўніках ніякага роду: насенне.'
                        },
                        {
                            code: 'РИКЗ А8 · Фанетыка',
                            theme: 'Правапіс у нескладовага (Ў)',
                            text: 'У якім выпадку пішацца Ў (у нескладовае)?',
                            codeBlock: '1) жанчына-..рач\n2) ва ..ніверсітэце\n3) ток-..оў\n4) ва ..се часы',
                            options: ['жанчына-..рач', 'ва ..ніверсітэце', 'ток-..оў', 'ва ..се часы'],
                            correct: 0,
                            gapLoss: 5,
                            mistake: 'Пасля злучка пішацца ў нескладовае, калі папярэдняе слова заканчваецца на галосны.'
                        },
                        {
                            code: 'РИКЗ Б2 · Лексікалогія',
                            theme: 'Фразеалагізмы і іх значэнне',
                            text: 'Адзначце значэнне фразеалагізма «біць лынды»:',
                            codeBlock: 'Фразеалагізм: біць лынды',
                            options: ['гультаяваць, марнаваць час', 'вельмі хутка бегчы', 'шчыра радавацца', 'цяжка працаваць'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: '«Біць лынды» азначае гультаяваць (дармаеднічаць).'
                        },
                        {
                            code: 'РИКЗ Б5 · Марфалогія',
                            theme: 'Клічная форма назоўнікаў і канчаткі роднага склону',
                            text: 'Адзначце словазлучэнне з правільным канчаткам роднага склону:',
                            codeBlock: '1) кілаграм цукру\n2) кілаграм цукра\n3) шклянка чая\n4) кавалак пірога',
                            options: ['кілаграм цукру', 'кілаграм цукра', 'шклянка чая', 'кавалак пірога'],
                            correct: 0,
                            gapLoss: 6,
                            mistake: 'Рэчыўныя назоўнікі ў родным склоне адзіночнага ліку маюць канчатак -у/-ю (цукру, чаю).'
                        },
                        {
                            code: 'РИКЗ Б7 · Сінтаксіс',
                            theme: 'Знакі прыпынку ў сказах з пабочнымі канструкцыямі',
                            text: 'У якім сказе выдзеленае слова З’ЯЎЛЯЕЦЦА пабочным і выдзяляецца коскамі?',
                            codeBlock: '1) На шчасце, цягнік прыбыў своечасова.\n2) Ён спадзяваўся на шчасце.\n3) Усё гэта здавалася праўдай.\n4) Пагода была на рэдкасць добрай.',
                            options: ['На шчасце, цягнік прыбыў своечасова.', 'Ён спадзяваўся на шчасце.', 'Усё гэта здавалася праўдай.', 'Пагода была на рэдкасць добрай.'],
                            correct: 0,
                            gapLoss: 7,
                            mistake: '«На шчасце» выражае эмацыйную ацэнку аўтара і выдзяляецца коскамі як пабочнае слова.'
                        }
                    ]
                },

                init() {
                    this.resetTest();
                },

                get currentQuestions() {
                    return this.questionsDb[this.selectedSubject] || this.questionsDb['Математика'];
                },

                get currentQuestion() {
                    return this.currentQuestions[this.currentIndex] || this.currentQuestions[0];
                },

                get targetScoreBenchmark() {
                    const s = this.targetScore;
                    if (s >= 95) return { label: 'Топ-1% абитуриентов', color: 'text-emerald-700' };
                    if (s >= 85) return { label: 'Бюджет ведущих вузов', color: 'text-violet-700' };
                    if (s >= 75) return { label: 'Популярные специальности', color: 'text-blue-700' };
                    return { label: 'Базовый порог', color: 'text-slate-600' };
                },

                selectSubject(sub) {
                    this.selectedSubject = sub;
                    this.resetTest();
                },

                resetTest() {
                    this.currentIndex = 0;
                    this.answers = new Array(this.currentQuestions.length).fill(undefined);
                },

                startDiagnostic() {
                    this.resetTest();
                    this.phase = 'testing';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                confirmExit() {
                    if (confirm('Сбросить текущее тестирование и вернуться к выбору параметров?')) {
                        this.phase = 'setup';
                    }
                },

                selectOption(optIndex) {
                    this.answers[this.currentIndex] = optIndex;
                },

                prevQuestion() {
                    if (this.currentIndex > 0) {
                        this.currentIndex--;
                    }
                },

                nextQuestion() {
                    if (this.currentIndex < this.currentQuestions.length - 1) {
                        this.currentIndex++;
                    }
                },

                handleKey(e) {
                    if (this.phase !== 'testing') return;
                    if (['1', '2', '3', '4', '5'].includes(e.key)) {
                        const optIndex = parseInt(e.key, 10) - 1;
                        if (optIndex < this.currentQuestion.options.length) {
                            this.selectOption(optIndex);
                        }
                    } else if (e.key === 'Enter') {
                        if (this.answers[this.currentIndex] !== undefined) {
                            if (this.currentIndex === this.currentQuestions.length - 1) {
                                this.finishAndAnalyze();
                            } else {
                                this.nextQuestion();
                            }
                        }
                    }
                },

                finishAndAnalyze() {
                    this.phase = 'analyzing';
                    this.analysisPercent = 0;
                    window.scrollTo({ top: 0, behavior: 'smooth' });

                    const statuses = [
                        { at: 10, text: 'Сверка ответов со спецификацией РИКЗ 2026...' },
                        { at: 40, text: 'Выявление расчетных и смысловых ловушек...' },
                        { at: 70, text: 'Калибровка первичного балла по шкале ЦТ/ЦЭ...' },
                        { at: 90, text: 'Формирование персональной карты тем...' }
                    ];

                    const duration = 2200;
                    const interval = 40;
                    const step = 100 / (duration / interval);

                    const timer = setInterval(() => {
                        this.analysisPercent = Math.min(100, Math.round(this.analysisPercent + step));

                        for (const s of statuses) {
                            if (this.analysisPercent >= s.at) {
                                this.currentStatusText = s.text;
                            }
                        }

                        if (this.analysisPercent >= 100) {
                            clearInterval(timer);
                            this.compileAndSubmit();
                        }
                    }, interval);
                },

                compileAndSubmit() {
                    let correctCount = 0;
                    let totalGapLoss = 0;
                    const weakTopics = [];
                    const skillGaps = [];

                    this.currentQuestions.forEach((q, idx) => {
                        const userAns = this.answers[idx];
                        if (userAns === q.correct) {
                            correctCount++;
                        } else {
                            totalGapLoss += q.gapLoss;
                            weakTopics.push(q.theme);
                            skillGaps.push({
                                code: q.code,
                                topic: q.theme,
                                loss: q.gapLoss,
                                mistake: q.mistake,
                                criticality: q.gapLoss >= 6 ? 'Высокий приоритет' : 'Умеренный приоритет'
                            });
                        }
                    });

                    let predictedScore = Math.max(48, Math.min(98, 100 - Math.round(totalGapLoss * 1.5)));
                    if (correctCount === this.currentQuestions.length) {
                        predictedScore = 96;
                    }

                    const payload = {
                        step: 3,
                        subject: [this.selectedSubject],
                        examType: this.selectedExam,
                        currentScore: predictedScore,
                        targetScore: this.targetScore,
                        weakTopics: weakTopics.slice(0, 4),
                        skillGaps: skillGaps,
                        answers: this.answers
                    };

                    fetch('{{ route("diagnostic.submit") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(() => {
                        window.location.href = '{{ route("diagnostic.finish") }}?subject=' + encodeURIComponent(this.selectedSubject) + 
                                               '&exam_type=' + encodeURIComponent(this.selectedExam) + 
                                               '&current_score=' + predictedScore + 
                                               '&target_score=' + this.targetScore;
                    })
                    .catch(() => {
                        window.location.href = '{{ route("diagnostic.finish") }}?subject=' + encodeURIComponent(this.selectedSubject) + 
                                               '&exam_type=' + encodeURIComponent(this.selectedExam) + 
                                               '&current_score=' + predictedScore + 
                                               '&target_score=' + this.targetScore;
                    });
                }
            };
        }
    </script>
</body>
</html>
