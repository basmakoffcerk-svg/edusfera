<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>О компании ООО «Эдусфера» — Разработчик профессионального образовательного ПО</title>
    <meta name="description" content="ООО «Эдусфера» — белорусская IT-компания, разработчик профессионального программного обеспечения для образования: ИИ-диагностика знаний, интерактивные виртуальные классы, биллинг для репетиторов и образовательных центров.">
    <meta name="keywords" content="о компании эдусфера, разработка образовательного по беларусь, edtech беларусь, ит компания минск, платформа для репетиторов, ооо эдусфера">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="https://edusfera.by/about">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_BY">
    <meta property="og:site_name" content="Edusfera">
    <meta property="og:title" content="О компании ООО «Эдусфера» — Создаём цифровое будущее образования">
    <meta property="og:description" content="Белорусский разработчик высоконагруженных платформ для репетиторов, ИИ-тестирования ЦТ/ЦЭ и онлайн-образования.">
    <meta property="og:url" content="https://edusfera.by/about">
    <meta property="og:image" content="{{ asset('og-image.png') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="О компании ООО «Эдусфера»">
    <meta name="twitter:description" content="Разработка профессионального программного обеспечения для образования в Республике Беларусь.">
    <meta name="twitter:image" content="{{ asset('og-image.png') }}">

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
              "name": "О компании",
              "item": "https://edusfera.by/about"
            }
          ]
        },
        {
          "@type": "Organization",
          "name": "ООО «Эдусфера»",
          "alternateName": "Edusfera LLC",
          "url": "https://edusfera.by/",
          "logo": "https://edusfera.by/favicon.svg",
          "foundingDate": "2026-05-04",
          "taxID": "192854899",
          "description": "Белорусская продуктовая IT-компания, разработчик профессионального программного обеспечения и цифровых сервисов для онлайн-образования, репетиторов и подготовки к ЦТ/ЦЭ.",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "ул. Веры Хоружей, д. 6А, пом. 29",
            "addressLocality": "Минск",
            "postalCode": "220100",
            "addressCountry": "BY"
          },
          "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+375-29-519-08-21",
            "contactType": "customer service",
            "email": "edusferaby@gmail.com",
            "availableLanguage": ["Russian", "Belarusian"]
          }
        }
      ]
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        /* Force Deep Obsidian Background across entire page */
        html, body.nexum-body {
            background-color: #050508 !important;
            color: #ffffff !important;
            font-family: 'Geist', 'Inter', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .font-rimma {
            font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif;
        }

        .bento-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0.01) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .bento-card:hover {
            border-color: rgba(198, 255, 51, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.8), 0 0 30px -10px rgba(125, 57, 235, 0.2);
        }

        .bento-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            pointer-events-none;
        }

        .grid-pattern {
            background-size: 32px 32px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        }

        .code-syntax-keyword { color: #c084fc; font-weight: 600; }
        .code-syntax-string { color: #C6FF33; }
        .code-syntax-func { color: #38bdf8; }
        .code-syntax-number { color: #fbbf24; }
        .code-syntax-comment { color: #64748b; font-style: italic; }
    </style>
</head>
<body class="nexum-body min-h-screen bg-[#050508] text-white selection:bg-[#C6FF33] selection:text-black antialiased flex flex-col justify-between" x-data="{ mobileMenu: false, activeTab: 'ai' }">

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 1. NAVIGATION BAR (FLOATING GLASS)                 -->
    <!-- ═══════════════════════════════════════════════════ -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-[#050508]/85 backdrop-blur-2xl border-b border-white/[0.08]">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 h-20 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-[#7D39EB] flex items-center justify-center shadow-lg shadow-[#7D39EB]/30 transition-transform group-hover:scale-105 border border-white/10">
                    <svg width="22" height="22" viewBox="0 0 64 64">
                        <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                        <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-bold tracking-tight text-white font-rimma uppercase">EDUSFERA</span>
                    <span class="text-[9px] font-extrabold text-[#C6FF33] tracking-widest uppercase -mt-0.5">Software & AI</span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium">
                <a href="{{ route('home') }}" class="text-neutral-400 hover:text-white transition-colors">Главная</a>
                <a href="{{ route('tutors.index') }}" class="text-neutral-400 hover:text-white transition-colors">Каталог</a>
                <a href="{{ route('for-tutors') }}" class="text-neutral-400 hover:text-white transition-colors">Преподавателям</a>
                <a href="{{ route('diagnostic.show') }}" class="text-neutral-400 hover:text-white transition-colors">ИИ-Диагностика</a>
                <a href="{{ route('about') }}" class="text-white font-bold flex items-center gap-1.5">
                    <span>О компании</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#C6FF33] shadow-[0_0_8px_#C6FF33]"></span>
                </a>
                <a href="{{ route('contacts') }}" class="text-neutral-400 hover:text-white transition-colors">Контакты</a>
            </nav>

            <!-- Desktop Actions -->
            <div class="hidden md:flex items-center gap-4">
                @auth
                    <a href="/admin" class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-sm border border-white/15 transition-all">
                        🚀 Кабинет
                    </a>
                @else
                    <a href="/login" class="text-sm font-semibold text-neutral-300 hover:text-white transition-colors">Войти</a>
                    <a href="/register?role=tutor" class="px-5 py-2.5 rounded-xl bg-[#C6FF33] text-black font-extrabold text-sm hover:bg-[#d5ff5e] transition-all shadow-lg shadow-[#C6FF33]/20">
                        Подключиться
                    </a>
                @endauth
            </div>

            <!-- Mobile Hamburger Toggle -->
            <button @click="mobileMenu = !mobileMenu" aria-label="Меню" class="md:hidden p-2.5 rounded-xl bg-white/5 border border-white/10 text-white focus:outline-none">
                <svg x-show="!mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                <svg x-show="mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Mobile Drawer Menu -->
        <div x-show="mobileMenu" x-cloak class="md:hidden bg-[#050508] border-b border-white/10 px-6 py-6 space-y-4">
            <a href="{{ route('home') }}" class="block text-base font-medium text-neutral-300">Главная</a>
            <a href="{{ route('tutors.index') }}" class="block text-base font-medium text-neutral-300">Каталог</a>
            <a href="{{ route('for-tutors') }}" class="block text-base font-medium text-neutral-300">Преподавателям</a>
            <a href="{{ route('diagnostic.show') }}" class="block text-base font-medium text-neutral-300">ИИ-Диагностика</a>
            <a href="{{ route('about') }}" class="block text-base font-bold text-[#C6FF33]">О компании</a>
            <a href="{{ route('contacts') }}" class="block text-base font-medium text-neutral-300">Контакты</a>
            <div class="pt-4 border-t border-white/10 flex flex-col gap-3">
                <a href="/login" class="text-center py-3 rounded-xl bg-white/5 text-white font-semibold text-sm">Войти</a>
                <a href="/register?role=tutor" class="text-center py-3 rounded-xl bg-[#C6FF33] text-black font-extrabold text-sm">Подключиться</a>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 2. HERO: STRONG PRODUCT & TECH POSITIONING         -->
    <!-- ═══════════════════════════════════════════════════ -->
    <main class="flex-grow pt-24 sm:pt-32">
        <section class="relative px-5 sm:px-8 pt-12 pb-20 sm:pb-28 max-w-7xl mx-auto overflow-hidden">
            <!-- Grid Background Overlay -->
            <div class="absolute inset-0 grid-pattern opacity-40 -z-20 pointer-events-none"></div>

            <!-- Ambient Atmospheric Lighting -->
            <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[850px] h-[450px] bg-gradient-to-tr from-[#7D39EB]/25 via-[#C6FF33]/15 to-blue-500/10 rounded-full blur-[130px] pointer-events-none -z-10"></div>

            <div class="max-w-5xl mx-auto text-center space-y-7">
                <!-- Trust Badge -->
                <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-white/[0.04] border border-white/[0.1] backdrop-blur-xl">
                    <span class="w-2 h-2 rounded-full bg-[#C6FF33] shadow-[0_0_8px_#C6FF33]"></span>
                    <span class="text-xs sm:text-sm font-semibold tracking-wide text-neutral-200">
                        ООО «Эдусфера» · ИТ-разработчик образовательного ПО (УНП 192854899)
                    </span>
                </div>

                <!-- Main Hero Headline with High Contrast -->
                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight text-white leading-[1.08]">
                    Разрабатываем профессиональное <br class="hidden sm:inline" />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#C6FF33] via-[#d4ff68] to-emerald-400">программное обеспечение</span> <br class="hidden sm:inline" />
                    для современного образования
                </h1>

                <!-- Crisp Editorial Subtitle -->
                <p class="text-base sm:text-xl text-neutral-300 leading-relaxed max-w-3xl mx-auto font-normal">
                    Мы создаём экосистему цифровых продуктов нового поколения: адаптивные ИИ-движки диагностики знаний по спецификациям РИКЗ, защищённые виртуальные классы реального времени и финансово-технологическую инфраструктуру для репетиторов и образовательных центров в Беларуси.
                </p>

                <!-- Actions -->
                <div class="pt-4 flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ route('for-tutors') }}" class="px-8 py-4 rounded-2xl bg-[#C6FF33] text-black font-black text-base hover:bg-[#d4ff5e] transition-all shadow-xl shadow-[#C6FF33]/25 flex items-center gap-2 hover:scale-[1.02]">
                        <span>Оценить платформу</span>
                        <span>→</span>
                    </a>
                    <a href="#products" class="px-8 py-4 rounded-2xl bg-white/[0.06] hover:bg-white/[0.12] text-white font-semibold text-base border border-white/15 transition-all">
                        Программные решения ↓
                    </a>
                </div>

                <!-- Metric Badges Row -->
                <div class="pt-12 grid grid-cols-2 md:grid-cols-4 gap-4 text-left border-t border-white/[0.08]">
                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/[0.06]">
                        <div class="text-3xl font-black text-[#C6FF33] font-mono">15,400+</div>
                        <div class="text-xs text-neutral-400 mt-1.5 leading-snug">Проведённых онлайн-уроков на платформе</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/[0.06]">
                        <div class="text-3xl font-black text-white font-mono">99.9%</div>
                        <div class="text-xs text-neutral-400 mt-1.5 leading-snug">Uptime инфраструктуры (сервера в Минске)</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/[0.06]">
                        <div class="text-3xl font-black text-[#7D39EB] font-mono">&lt; 80 мс</div>
                        <div class="text-xs text-neutral-400 mt-1.5 leading-snug">Медиа-задержка WebRTC в виртуальном классе</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/[0.06]">
                        <div class="text-3xl font-black text-emerald-400 font-mono">0 BYN</div>
                        <div class="text-xs text-neutral-400 mt-1.5 leading-snug">Комиссий за уроки (чистая модель SaaS)</div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- INTERACTIVE ARCHITECTURE TERMINAL MOCKUP       -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="mt-14 max-w-4xl mx-auto rounded-3xl bg-[#09090f] border border-white/15 shadow-2xl overflow-hidden text-left">
                <!-- Terminal Header Bar -->
                <div class="px-5 py-4 bg-white/[0.03] border-b border-white/10 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                        <span class="ml-2 text-xs font-mono text-neutral-400 font-semibold">edusfera-core-v2.6 // telemetry & stack</span>
                    </div>
                    <div class="inline-flex rounded-xl bg-black/60 p-1 border border-white/10 text-xs font-semibold">
                        <button @click="activeTab = 'ai'" :class="activeTab === 'ai' ? 'bg-[#7D39EB] text-white shadow' : 'text-neutral-400 hover:text-white'" class="px-3 py-1 rounded-lg transition-all">ИИ-Диагностика</button>
                        <button @click="activeTab = 'webrtc'" :class="activeTab === 'webrtc' ? 'bg-[#7D39EB] text-white shadow' : 'text-neutral-400 hover:text-white'" class="px-3 py-1 rounded-lg transition-all">WebRTC Класс</button>
                        <button @click="activeTab = 'fintech'" :class="activeTab === 'fintech' ? 'bg-[#7D39EB] text-white shadow' : 'text-neutral-400 hover:text-white'" class="px-3 py-1 rounded-lg transition-all">Биллинг Альфа-Банк</button>
                    </div>
                </div>

                <!-- Terminal Content Panes -->
                <div class="p-6 font-mono text-xs sm:text-sm text-neutral-300 leading-relaxed overflow-x-auto min-h-[190px]">
                    <!-- Tab 1: AI Diagnostics -->
                    <div x-show="activeTab === 'ai'" x-cloak class="space-y-1.5">
                        <p><span class="code-syntax-comment">// Запуск калибровочного анализа РИКЗ (ЦТ и ЦЭ 2026)</span></p>
                        <p><span class="code-syntax-keyword">const</span> analysis = <span class="code-syntax-keyword">await</span> EdusferaAI.<span class="code-syntax-func">diagnoseStudentGaps</span>({</p>
                        <p class="pl-4">subject: <span class="code-syntax-string">'Математика'</span>, exam: <span class="code-syntax-string">'ЦТ/ЦЭ_2026'</span>, codifierVersion: <span class="code-syntax-string">'RIKZ_SPEC_v2'</span></p>
                        <p>});</p>
                        <p class="text-neutral-400">↳ Выявлено критических пробелов: <span class="text-[#C6FF33] font-bold">4 темы</span> (Тригонометрия B12, Стереометрия B14)</p>
                        <p class="text-neutral-400">↳ Прогноз балла до обучения: <span class="text-amber-400 font-bold">54</span> → Персональная траектория: <span class="text-[#C6FF33] font-bold">88–94 балла</span></p>
                    </div>

                    <!-- Tab 2: WebRTC Classroom -->
                    <div x-show="activeTab === 'webrtc'" x-cloak class="space-y-1.5">
                        <p><span class="code-syntax-comment">// Статус виртуального класса (WebRTC SFU + Collaborative Canvas)</span></p>
                        <p>ROOM_ID: <span class="code-syntax-string">'edusfera-lesson-live-8921'</span> · ENCRYPTION: <span class="code-syntax-keyword">AES-GCM-256</span></p>
                        <p>MEDIA_STREAM: <span class="text-emerald-400 font-bold">ACTIVE (1080p @ 60fps)</span> · AUDIO_CODEC: <span class="code-syntax-string">'Opus 48kHz HD'</span></p>
                        <p>LATENCY_TEST: <span class="text-[#C6FF33] font-bold">54ms round-trip</span> · PACKET_LOSS: <span class="text-[#C6FF33] font-bold">0.00%</span></p>
                        <p>WHITEBOARD: <span class="text-sky-400 font-bold">Векторный холст синхронизирован (двусторонний протокол &lt;15ms)</span></p>
                    </div>

                    <!-- Tab 3: FinTech & Taxes -->
                    <div x-show="activeTab === 'fintech'" x-cloak class="space-y-1.5">
                        <p><span class="code-syntax-comment">// Финансово-технологический шлюз ЗАО «Альфа-Банк» (Беларусь)</span></p>
                        <p>TRANSACTION: <span class="code-syntax-string">'HOLD_ESCROW_TX_8921'</span> · CURRENCY: <span class="code-syntax-keyword">BYN</span></p>
                        <p>SECURITY: <span class="text-emerald-400 font-bold">3D-Secure 2.0 / Белкарт ИнтернетПароль / PCI DSS Compliant</span></p>
                        <p>STATUS: <span class="text-[#C6FF33] font-bold">Урок успешно проведён → Выплата 100% репетитору</span></p>
                        <p>TAX_LEDGER: <span class="text-sky-400 font-bold">Автоматический фискальный чек НПД (налог на профдоход) сформирован</span></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- 3. BENTO GRID: OUR PRODUCTS & ECOSYSTEM             -->
        <!-- ═══════════════════════════════════════════════════ -->
        <section id="products" class="py-20 sm:py-28 max-w-7xl mx-auto px-5 sm:px-8">
            <div class="max-w-3xl mb-16 space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-[#C6FF33]/15 border border-[#C6FF33]/30 text-xs font-bold text-[#C6FF33] uppercase tracking-wider">
                    Собственная программная разработка
                </div>
                <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight">
                    Продуктовая экосистема Эдусферы
                </h2>
                <p class="text-neutral-400 text-base sm:text-lg">
                    Мы создали полную технологическую цепочку для современного репетитора и онлайн-образования — от умного привлечения учеников до встроенного класса и фискализации.
                </p>
            </div>

            <!-- Bento Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- 1. AI Diagnostic Core (Large Featured) -->
                <div class="bento-card p-8 sm:p-10 md:col-span-2 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#7D39EB]/20 border border-[#7D39EB]/40 flex items-center justify-center text-2xl">
                            🧠
                        </div>
                        <div class="inline-block text-xs font-bold uppercase tracking-wider text-[#c084fc]">
                            Machine Learning & Тестология РИКЗ
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-extrabold text-white">
                            Edusfera AI Diagnostic Core
                        </h3>
                        <p class="text-neutral-300 text-sm sm:text-base leading-relaxed max-w-2xl">
                            Собственная интеллектуальная система оценки знаний. Модуль анализирует ответы абитуриента по структуре кодификаторов Республиканского института контроля знаний (ЦТ и ЦЭ), с математической точностью находит корневые пробелы программы и выстраивает индивидуальную дорожную карту для набора 85+ баллов.
                        </p>
                    </div>

                    <div class="pt-8 grid grid-cols-2 sm:grid-cols-3 gap-4 border-t border-white/[0.08] mt-8 text-xs text-neutral-400">
                        <div>
                            <span class="block text-white font-bold text-sm">96.4%</span>
                            Точность прогноза балла
                        </div>
                        <div>
                            <span class="block text-white font-bold text-sm">6 предметов</span>
                            Полная база тестов РИКЗ 2026
                        </div>
                        <div>
                            <span class="block text-white font-bold text-sm">0 минут</span>
                            Экономия времени на проверке
                        </div>
                    </div>
                </div>

                <!-- 2. Real-Time Classroom -->
                <div class="bento-card p-8 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#C6FF33]/20 border border-[#C6FF33]/40 flex items-center justify-center text-2xl">
                            📹
                        </div>
                        <div class="inline-block text-xs font-bold uppercase tracking-wider text-[#C6FF33]">
                            Медиа-сервер реального времени
                        </div>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-white">
                            Edusfera Virtual Classroom
                        </h3>
                        <p class="text-neutral-300 text-sm leading-relaxed">
                            Интерактивный класс на базе WebRTC: HD-видео высокой чёткости, шумоподавление голоса, совместная бесконечная векторная доска, встроенные формулы и чат без сторонних программ.
                        </p>
                    </div>

                    <div class="pt-6 border-t border-white/[0.08] mt-6 flex items-center justify-between text-xs text-neutral-400">
                        <span>WebRTC / Canvas</span>
                        <span class="text-[#C6FF33] font-bold font-mono">&lt; 80ms пинг</span>
                    </div>
                </div>

                <!-- 3. FinTech & Payments -->
                <div class="bento-card p-8 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-500/20 border border-blue-500/40 flex items-center justify-center text-2xl">
                            💳
                        </div>
                        <div class="inline-block text-xs font-bold uppercase tracking-wider text-blue-400">
                            Финтех и безопасность
                        </div>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-white">
                            Edusfera Pay & Tax Ledger
                        </h3>
                        <p class="text-neutral-300 text-sm leading-relaxed">
                            Прямая интеграция с интернет-эквайрингом ЗАО «Альфа-Банк». Защита от срывов занятий, автохолдирование средств и автоматическое формирование чеков налога на профдоход (НПД) для репетиторов.
                        </p>
                    </div>

                    <div class="pt-6 border-t border-white/[0.08] mt-6 flex items-center justify-between text-xs text-neutral-400">
                        <span>Альфа-Банк / Белкарт / Visa</span>
                        <span class="text-[#C6FF33] font-bold font-mono">0% за урок</span>
                    </div>
                </div>

                <!-- 4. Smart CRM & Scheduling -->
                <div class="bento-card p-8 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-2xl">
                            📅
                        </div>
                        <div class="inline-block text-xs font-bold uppercase tracking-wider text-amber-400">
                            Автоматизация расписания
                        </div>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-white">
                            Smart Schedule Engine
                        </h3>
                        <p class="text-neutral-300 text-sm leading-relaxed">
                            Умный планировщик занятий: синхронизация свободных окон репетитора, мгновенное онлайн-бронирование, автоматические напоминания ученикам через PWA/Push и защита от неявок.
                        </p>
                    </div>

                    <div class="pt-6 border-t border-white/[0.08] mt-6 flex items-center justify-between text-xs text-neutral-400">
                        <span>Push / SMS / Telegram</span>
                        <span class="text-[#C6FF33] font-bold font-mono">-90% пропусков</span>
                    </div>
                </div>

                <!-- 5. Progressive Web App (PWA) -->
                <div class="bento-card p-8 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-2xl">
                            📲
                        </div>
                        <div class="inline-block text-xs font-bold uppercase tracking-wider text-emerald-400">
                            Кросс-платформенность
                        </div>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-white">
                            Progressive Web App (PWA)
                        </h3>
                        <p class="text-neutral-300 text-sm leading-relaxed">
                            Полноценное мобильное приложение, работающее на iOS, Android и десктопах: поддержка офлайн-режима, системных пуш-уведомлений и моментальная загрузка интерфейса.
                        </p>
                    </div>

                    <div class="pt-6 border-t border-white/[0.08] mt-6 flex items-center justify-between text-xs text-neutral-400">
                        <span>Service Worker / Cache API</span>
                        <span class="text-[#C6FF33] font-bold font-mono">0.8 сек старт</span>
                    </div>
                </div>

            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- 4. ENGINEERING STANDARDS & SECURITY                -->
        <!-- ═══════════════════════════════════════════════════ -->
        <section class="py-20 sm:py-24 bg-white/[0.015] border-y border-white/[0.06]">
            <div class="max-w-7xl mx-auto px-5 sm:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-5 space-y-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-[#7D39EB]/20 border border-[#7D39EB]/30 text-xs font-bold text-[#c49aff] uppercase tracking-wider">
                            Инженерные стандарты
                        </div>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight">
                            Безопасность национального уровня и современный стек
                        </h2>
                        <p class="text-neutral-300 text-base leading-relaxed">
                            Мы строим платформу с прицелом на максимальную надёжность. Все персональные данные пользователей хранятся на территории Республики Беларусь, а платёжные данные обрабатываются по строгим протоколам PCI DSS.
                        </p>
                    </div>

                    <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="p-6 rounded-2xl bg-[#09090f] border border-white/10 space-y-3">
                            <div class="text-[#C6FF33] text-xl font-bold">🛡️ Закон № 99-З РБ</div>
                            <h4 class="text-white font-bold text-base">Защита персональных данных</h4>
                            <p class="text-neutral-400 text-xs leading-relaxed">
                                Полное соответствие требованиям Национального центра защиты персональных данных Республики Беларусь. Данные изолированы и зашифрованы.
                            </p>
                        </div>

                        <div class="p-6 rounded-2xl bg-[#09090f] border border-white/10 space-y-3">
                            <div class="text-[#7D39EB] text-xl font-bold">🏛️ ЦОД в Минске</div>
                            <h4 class="text-white font-bold text-base">Прямой пиринг BelCloud</h4>
                            <p class="text-neutral-400 text-xs leading-relaxed">
                                Отказоустойчивая серверная инфраструктура в белорусских дата-центрах гарантирует минимальный пинг и бесперебойный доступ 24/7.
                            </p>
                        </div>

                        <div class="p-6 rounded-2xl bg-[#09090f] border border-white/10 space-y-3">
                            <div class="text-blue-400 text-xl font-bold">🔒 3D-Secure 2.0</div>
                            <h4 class="text-white font-bold text-base">Банковская безопасность</h4>
                            <p class="text-neutral-400 text-xs leading-relaxed">
                                Эквайринг ЗАО «Альфа-Банк». Поддержка Белкарт ИнтернетПароль, Visa Secure и MasterCard Identity Check с двухфакторной защитой.
                            </p>
                        </div>

                        <div class="p-6 rounded-2xl bg-[#09090f] border border-white/10 space-y-3">
                            <div class="text-emerald-400 text-xl font-bold">⚡ Высокая масштабируемость</div>
                            <h4 class="text-white font-bold text-base">Enterprise стек</h4>
                            <p class="text-neutral-400 text-xs leading-relaxed">
                                Архитектура на PHP 8.4, Laravel, React, Redis Cluster и WebRTC SFU обеспечивает стабильную работу даже во время пиковых нагрузок экзаменов.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- 5. COMPANY DETAILS & OFFICIAL REQUISITES           -->
        <!-- ═══════════════════════════════════════════════════ -->
        <section class="py-20 sm:py-28 max-w-7xl mx-auto px-5 sm:px-8">
            <div class="max-w-3xl mb-14 space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-white/5 border border-white/10 text-xs font-bold text-neutral-300 uppercase tracking-wider">
                    Юридическая прозрачность
                </div>
                <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight">
                    Официальная информация о компании
                </h2>
                <p class="text-neutral-400 text-base">
                    ООО «Эдусфера» ведёт открытую деятельность в строгом соответствии с законодательством Республики Беларусь.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Box 1 -->
                <div class="p-8 rounded-3xl bg-[#09090f] border border-white/10 space-y-4">
                    <div class="text-[#C6FF33] font-bold text-base flex items-center gap-2">
                        <span>🏢</span>
                        <span>Юридическое лицо</span>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-neutral-400 text-xs">Полное наименование:</dt>
                            <dd class="text-white font-bold">Общество с ограниченной ответственностью «Эдусфера»</dd>
                        </div>
                        <div>
                            <dt class="text-neutral-400 text-xs">Сокращённое наименование:</dt>
                            <dd class="text-white font-medium">ООО «Эдусфера»</dd>
                        </div>
                        <div>
                            <dt class="text-neutral-400 text-xs">УНП:</dt>
                            <dd class="text-[#C6FF33] font-bold font-mono text-base">192854899</dd>
                        </div>
                        <div>
                            <dt class="text-neutral-400 text-xs">Регистрация:</dt>
                            <dd class="text-neutral-300">Минский горисполком от 04.05.2026 г.</dd>
                        </div>
                    </dl>
                </div>

                <!-- Box 2 -->
                <div class="p-8 rounded-3xl bg-[#09090f] border border-white/10 space-y-4">
                    <div class="text-[#7D39EB] font-bold text-base flex items-center gap-2">
                        <span>📍</span>
                        <span>Контакты и адрес</span>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-neutral-400 text-xs">Юридический адрес:</dt>
                            <dd class="text-white font-medium">Республика Беларусь, 220100, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29</dd>
                        </div>
                        <div>
                            <dt class="text-neutral-400 text-xs">Телефон:</dt>
                            <dd class="text-white font-bold">
                                <a href="tel:+375295190821" class="hover:text-[#C6FF33] transition-colors">+375 (29) 519-08-21</a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-neutral-400 text-xs">Email:</dt>
                            <dd class="text-white font-bold">
                                <a href="mailto:edusferaby@gmail.com" class="hover:text-[#C6FF33] transition-colors">edusferaby@gmail.com</a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-neutral-400 text-xs">Режим работы поддержки:</dt>
                            <dd class="text-neutral-300">Пн – Пт: 09:00 – 18:00 (Минск)</dd>
                        </div>
                    </dl>
                </div>

                <!-- Box 3 -->
                <div class="p-8 rounded-3xl bg-[#09090f] border border-white/10 space-y-4">
                    <div class="text-blue-400 font-bold text-base flex items-center gap-2">
                        <span>🏦</span>
                        <span>Банковский эквайринг</span>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-neutral-400 text-xs">Банк-партнёр:</dt>
                            <dd class="text-white font-bold">ЗАО «Альфа-Банк» (Беларусь)</dd>
                        </div>
                        <div>
                            <dt class="text-neutral-400 text-xs">Платёжные системы:</dt>
                            <dd class="text-neutral-300">БЕЛКАРТ, Белкарт ИнтернетПароль, VISA, Visa Secure, MasterCard, Apple Pay</dd>
                        </div>
                        <div>
                            <dt class="text-neutral-400 text-xs">Валюта расчетов:</dt>
                            <dd class="text-[#C6FF33] font-bold">Белорусский рубль (BYN)</dd>
                        </div>
                        <div class="pt-2 flex flex-wrap gap-2 text-xs">
                            <a href="{{ route('legal.offer') }}" class="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-neutral-300 border border-white/10">Оферта</a>
                            <a href="{{ route('legal.privacy') }}" class="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-neutral-300 border border-white/10">Политика 99-З</a>
                            <a href="{{ route('legal.payment-security') }}" class="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-neutral-300 border border-white/10">Безопасность</a>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- 6. CONVERTING CALL-TO-ACTION                       -->
        <!-- ═══════════════════════════════════════════════════ -->
        <section class="py-20 sm:py-28 max-w-7xl mx-auto px-5 sm:px-8">
            <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#170a36] via-[#0f0b24] to-[#0a1810] border border-white/15 p-8 sm:p-14 lg:p-16 text-center space-y-6 shadow-2xl">
                <div class="absolute -top-32 -left-32 w-80 h-80 bg-[#7D39EB]/30 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-[#C6FF33]/20 rounded-full blur-[100px] pointer-events-none"></div>

                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 text-[#C6FF33] font-bold text-xs uppercase tracking-wider backdrop-blur-md">
                    ⚡ Станьте частью новой эры образования
                </div>

                <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight max-w-3xl mx-auto leading-tight">
                    Готовы преподавать и учиться на софте профессионального уровня?
                </h2>

                <p class="text-base sm:text-lg text-neutral-300 max-w-2xl mx-auto leading-relaxed">
                    Подключитесь к платформе уже сегодня. Первый месяц для преподавателей — бесплатный ознакомительный период. Оцените мощь автоматизации и проверенных технологий.
                </p>

                <div class="pt-4 flex flex-wrap items-center justify-center gap-4">
                    <a href="/register?role=tutor" class="px-8 py-4 rounded-2xl bg-[#C6FF33] text-black font-extrabold text-base hover:bg-[#d8ff5e] transition-all shadow-xl shadow-[#C6FF33]/30 hover:scale-[1.02]">
                        Начать 30 дней бесплатно (Репетиторам)
                    </a>
                    <a href="{{ route('diagnostic.show') }}" class="px-8 py-4 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-bold text-base border border-white/20 transition-all hover:scale-[1.02]">
                        Пройти ИИ-диагностику (Ученикам)
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- 7. FOOTER                                          -->
    <!-- ═══════════════════════════════════════════════════ -->
    @include('partials.site-footer', ['theme' => 'dark'])
    @include('partials.pwa-prompt')

</body>
</html>
