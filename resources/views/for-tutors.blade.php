<!DOCTYPE html>
<html lang="ru" class="scroll-smooth dark">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edusfera для преподавателей — Платформа для лучших репетиторов</title>
    <meta name="description" content="Преподавайте легально и элегантно. Ученики, расписание, встроенный класс и безопасная оплата в одном премиальном кабинете.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        :root {
            --ed-accent: #C6FF33;
            --dark-bg: #09090b;
            --dark-card: rgba(255, 255, 255, 0.03);
            --dark-border: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--dark-bg);
            color: #e5e7eb;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(198, 255, 51, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(198, 255, 51, 0.03) 0%, transparent 40%);
            background-attachment: fixed;
        }

        .font-rimma {
            font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif;
        }

        /* --- Dark Glass Components & Discipline Grid --- */
        .glass-panel-dark {
            background: var(--dark-card);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid var(--dark-border);
            border-radius: 1.5rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .glass-panel-dark:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.16);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.7);
        }

        .glass-nav-dark {
            background: rgba(9, 9, 11, 0.88);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--dark-border);
        }

        /* --- Visual Response & Action Feedback --- */
        .btn-core {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 2rem;
            border-radius: 9999px;
            font-weight: 800;
            font-size: 0.9375rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            z-index: 1;
            cursor: pointer;
            user-select: none;
        }

        .btn-core:active {
            transform: scale(0.97) !important;
        }

        .btn-lime {
            background: var(--ed-accent);
            color: #000000;
            box-shadow: 0 8px 25px rgba(198, 255, 51, 0.25);
        }
        
        .btn-lime:hover {
            background: #d4ff59;
            transform: translateY(-2px);
            box-shadow: 0 14px 35px rgba(198, 255, 51, 0.4);
        }

        .btn-ghost-dark {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .btn-ghost-dark:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .anim-spin-slow { animation: spin 50s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        
        .anim-float { animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        
        .bg-grid-dark {
            background-size: 32px 32px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.025) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.025) 1px, transparent 1px);
        }
        [x-cloak] { display: none !important; }

        /* Range slider styling with active visual feedback */
        input[type=range] {
            -webkit-appearance: none;
            width: 100%;
            background: transparent;
        }
        input[type=range]:focus {
            outline: none;
        }
        input[type=range]::-webkit-slider-runnable-track {
            width: 100%;
            height: 8px;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        input[type=range]::-webkit-slider-thumb {
            height: 24px;
            width: 24px;
            border-radius: 50%;
            background: var(--ed-accent);
            cursor: pointer;
            -webkit-appearance: none;
            margin-top: -8px;
            box-shadow: 0 0 15px rgba(198, 255, 51, 0.6);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        input[type=range]::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            box-shadow: 0 0 22px rgba(198, 255, 51, 0.9);
        }
        input[type=range]::-webkit-slider-thumb:active {
            transform: scale(1.3);
            background: #ffffff;
        }
    </style>
</head>
<body x-data="{ scrolled: false, mobileOpen: false }" @scroll.window="scrolled = (window.pageYOffset > 20)" class="text-gray-300">

    <!-- NAVBAR WITH AUTH LOGIC -->
    <header class="fixed top-0 w-full z-50 transition-all duration-300" :class="scrolled || mobileOpen ? 'glass-nav-dark py-4' : 'py-6'">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 flex items-center justify-between">
            <a href="{{ route('home') }}" class="font-rimma font-bold text-2xl tracking-tighter flex items-center gap-2 group text-white active:scale-95 transition-transform">
                EDUSFERA
                <span class="text-[10px] uppercase font-sans font-extrabold text-black bg-lime-400 px-2.5 py-0.5 rounded-full ml-1 hidden xs:inline sm:inline">Репетиторам</span>
            </a>

            <nav class="hidden md:flex items-center gap-8 font-bold text-xs uppercase tracking-widest text-gray-400">
                <a href="#calculator" class="hover:text-lime-400 transition-colors py-1">Калькулятор</a>
                <a href="#features" class="hover:text-lime-400 transition-colors py-1">Модули</a>
                <a href="#roadmap" class="hover:text-lime-400 transition-colors py-1">Как начать</a>
                <a href="#faq" class="hover:text-lime-400 transition-colors py-1">Вопросы</a>
            </nav>

            <div class="flex items-center gap-4">
                <button @click="mobileOpen = !mobileOpen" class="md:hidden w-10 h-10 rounded-xl flex items-center justify-center text-gray-300 hover:bg-white/10 active:scale-95 transition-all" aria-label="Меню">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                @auth
                    @php
                        $user = auth()->user();
                        $unreadMessagesCount = app(\App\Services\ChatUnreadCounter::class)->countForUser($user);
                        $roleLabel = \App\Services\MultiAccountService::roleLabel($user->role);
                    @endphp
                    
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-3 bg-white/10 backdrop-blur-md border border-white/10 pl-2 pr-4 py-1.5 rounded-full hover:bg-white/20 active:scale-95 transition-all shadow-sm text-white">
                            <div class="w-8 h-8 rounded-full bg-lime-400 text-black flex items-center justify-center font-bold text-sm">
                                {{ mb_substr((string)$user->name, 0, 1) }}
                            </div>
                            <div class="text-left hidden sm:block">
                                <div class="text-sm font-bold leading-tight">{{ $user->name }}</div>
                                <div class="text-[10px] uppercase font-bold text-lime-400">{{ $roleLabel }}</div>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 ml-1 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <div x-show="open" x-transition.opacity.scale.95 style="display: none;" class="absolute right-0 mt-3 w-64 bg-[#141417] rounded-2xl border border-white/10 shadow-2xl p-2 z-50">
                            <a href="/admin" class="block px-4 py-2.5 text-sm font-bold text-gray-200 hover:text-white hover:bg-white/10 rounded-xl transition-colors">Личный кабинет</a>
                            <a href="/admin/transactions" class="block px-4 py-2.5 text-sm font-bold text-gray-200 hover:text-white hover:bg-white/10 rounded-xl transition-colors">Мои финансы</a>
                            <a href="/admin/messages" class="flex items-center justify-between px-4 py-2.5 text-sm font-bold text-gray-200 hover:text-white hover:bg-white/10 rounded-xl transition-colors">
                                Сообщения
                                @if($unreadMessagesCount > 0)
                                    <span class="bg-lime-400/20 text-lime-400 px-2 py-0.5 rounded-full text-xs font-bold">{{ $unreadMessagesCount }}</span>
                                @endif
                            </a>

                            <div class="h-px bg-white/10 my-2 mx-2"></div>
                            
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2.5 text-sm font-bold text-red-400 hover:bg-red-500/10 rounded-xl transition-colors">
                                    Выйти
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="/admin/login" class="hidden sm:block text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-white transition-colors">Войти</a>
                    <a href="/admin/register" class="btn-core btn-lime py-2.5 px-6 text-xs">Стать преподавателем</a>
                @endauth
            </div>
        </div>

        {{-- Mobile navigation menu --}}
        <div x-show="mobileOpen" x-cloak class="md:hidden border-t border-white/10 bg-[#09090b]/95 backdrop-blur-xl">
            <nav class="max-w-7xl mx-auto px-6 py-4 flex flex-col gap-2">
                <a href="#calculator" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                    🧮 Калькулятор дохода
                </a>
                <a href="#features" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                    🏛️ Архитектура модулей
                </a>
                <a href="#roadmap" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                    🚀 Как начать за 15 мин
                </a>
                <a href="#faq" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                    ❓ Вопросы и налоги
                </a>
                @guest
                <div class="h-px bg-white/10 my-2"></div>
                <a href="/admin/login" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                    Войти в кабинет
                </a>
                @endguest
            </nav>
        </div>
    </header>

    <!-- HERO SECTION (One Left Axis alignment, Single Lime Accent, 8pt Grid) -->
    <section class="relative min-h-screen pt-36 pb-24 flex items-center overflow-hidden bg-grid-dark">
        <!-- Ambient light (Strictly 1 accent color tone) -->
        <div class="absolute inset-0 z-0 pointer-events-none flex items-center justify-center overflow-hidden">
            <div class="absolute top-[20%] right-[15%] w-[550px] h-[550px] bg-lime-400/5 rounded-full mix-blend-screen filter blur-[160px]"></div>
            
            <svg class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] opacity-[0.03] anim-spin-slow text-white" viewBox="0 0 800 800" fill="none">
                <circle cx="400" cy="400" r="350" stroke="currentColor" stroke-width="1" stroke-dasharray="8 24"/>
                <circle cx="400" cy="400" r="240" stroke="currentColor" stroke-width="1" stroke-dasharray="4 12"/>
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-6 sm:px-8 w-full relative z-10">
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-16 items-center">
                
                <!-- Left Content: Single Left Axis Alignment -->
                <div class="lg:col-span-7 flex flex-col items-start text-left">
                    
                    <!-- Official Status Badge -->
                    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/5 border border-white/10 mb-8 backdrop-blur-md">
                        <span class="w-2.5 h-2.5 rounded-full bg-lime-400 animate-pulse shadow-[0_0_10px_rgba(198,255,51,0.8)]"></span>
                        <span class="text-xs font-extrabold tracking-wider uppercase text-gray-200">ГОСРЕЕСТР РБ 2026 · ОФИЦИАЛЬНО И ЛЕГАЛЬНО</span>
                    </div>

                    <!-- Hero Headline: Scale Contrast & One Hero -->
                    <h1 class="text-[clamp(2.2rem,4.2vw,4.2rem)] font-rimma font-black uppercase tracking-tighter mb-8 text-white leading-[0.95] max-w-full">
                        Преподавайте <br>
                        <span class="text-lime-400">с достоинством</span>
                    </h1>

                    <!-- Interactive Pictograms & Process Scheme instead of dense paragraph -->
                    <div class="w-full max-w-xl mb-10">
                        <div class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Единая экосистема автоматизации:</div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="glass-panel-dark p-3.5 flex flex-col items-start gap-2 hover:border-lime-400/40 transition-colors group cursor-default">
                                <span class="text-xl group-hover:scale-110 transition-transform">🔍</span>
                                <span class="text-xs font-bold text-white leading-snug">Поиск учеников</span>
                                <span class="text-[10px] text-gray-400 font-medium">Каталог РБ</span>
                            </div>
                            <div class="glass-panel-dark p-3.5 flex flex-col items-start gap-2 hover:border-lime-400/40 transition-colors group cursor-default">
                                <span class="text-xl group-hover:scale-110 transition-transform">💳</span>
                                <span class="text-xs font-bold text-white leading-snug">ЕРИП / bePaid</span>
                                <span class="text-[10px] text-gray-400 font-medium">Белая оплата</span>
                            </div>
                            <div class="glass-panel-dark p-3.5 flex flex-col items-start gap-2 hover:border-lime-400/40 transition-colors group cursor-default">
                                <span class="text-xl group-hover:scale-110 transition-transform">📅</span>
                                <span class="text-xs font-bold text-white leading-snug">Авто-слоты</span>
                                <span class="text-[10px] text-gray-400 font-medium">Без накладок</span>
                            </div>
                            <div class="glass-panel-dark p-3.5 flex flex-col items-start gap-2 hover:border-lime-400/40 transition-colors group cursor-default">
                                <span class="text-xl group-hover:scale-110 transition-transform">🎓</span>
                                <span class="text-xs font-bold text-white leading-snug">Онлайн-класс</span>
                                <span class="text-[10px] text-gray-400 font-medium">Видео и доска</span>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Actions -->
                    <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 mb-12 w-full sm:w-auto">
                        <a href="/admin/register" class="btn-core btn-lime text-base px-8 py-4">
                            Создать профиль →
                        </a>
                        <a href="#calculator" class="btn-core btn-ghost-dark text-base px-8 py-4">
                            Рассчитать доход ↓
                        </a>
                    </div>

                    <!-- Trust Pills Scheme (Monochrome with Lime Checkmark Accent) -->
                    <div class="grid grid-cols-3 gap-6 pt-6 border-t border-white/10 w-full max-w-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-lime-400/10 border border-lime-400/20 text-lime-400 flex items-center justify-center font-bold text-xs flex-shrink-0">✓</div>
                            <div>
                                <div class="text-xs font-bold text-white leading-tight">100% Легально</div>
                                <div class="text-[10px] text-gray-400">Авто-чеки НПД</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-lime-400/10 border border-lime-400/20 text-lime-400 flex items-center justify-center font-bold text-xs flex-shrink-0">✓</div>
                            <div>
                                <div class="text-xs font-bold text-white leading-tight">Защита Эскроу</div>
                                <div class="text-[10px] text-gray-400">Гарантия оплаты</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-lime-400/10 border border-lime-400/20 text-lime-400 flex items-center justify-center font-bold text-xs flex-shrink-0">✓</div>
                            <div>
                                <div class="text-xs font-bold text-white leading-tight">0 BYN</div>
                                <div class="text-[10px] text-gray-400">Взнос за анкету</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Content: Live Dashboard Mockup (Monochrome + 1 Lime Accent) -->
                <div class="lg:col-span-5 anim-float">
                    <div class="glass-panel-dark p-7 sm:p-8 w-full relative overflow-hidden border-white/15 shadow-2xl">
                        <div class="flex justify-between items-center mb-6 border-b border-white/10 pb-4">
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-lime-400 animate-ping"></span>
                                <h3 class="font-bold text-white tracking-wide text-sm sm:text-base">Кабинет Преподавателя</h3>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-black bg-lime-400 px-3 py-1 rounded-full">ЕРИП Активен</span>
                        </div>
                        
                        <div class="space-y-4 mb-6">
                            <!-- Lesson 1 (Paid) -->
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-4 flex items-center justify-between hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-full bg-white/10 border border-white/20 flex items-center justify-center font-bold text-white text-sm">Е</div>
                                    <div>
                                        <div class="font-bold text-white text-sm">Егор В. (11 класс)</div>
                                        <div class="text-[11px] text-gray-400 font-medium tracking-wider mt-0.5">Сегодня, 16:00 • Подготовка к ЦЭ</div>
                                    </div>
                                </div>
                                <div class="text-[11px] font-bold text-lime-400 border border-lime-400/30 bg-lime-400/10 px-2.5 py-1 rounded-lg flex-shrink-0">Оплачено</div>
                            </div>
                            
                            <!-- Lesson 2 Active Class -->
                            <div class="bg-white/10 border border-lime-400/40 rounded-2xl p-4 flex items-center justify-between text-white shadow-lg">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-full bg-lime-400 text-black flex items-center justify-center font-black text-sm">А</div>
                                    <div>
                                        <div class="font-bold text-white text-sm">Анна С. (Математика)</div>
                                        <div class="text-[11px] text-lime-400 font-bold tracking-wider mt-0.5 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-lime-400 animate-pulse"></span> Идет прямо сейчас
                                        </div>
                                    </div>
                                </div>
                                <a href="#" class="w-9 h-9 rounded-full bg-lime-400 flex items-center justify-center text-black shadow-md hover:scale-105 active:scale-95 transition-all flex-shrink-0" title="Войти в класс">
                                    <svg class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </a>
                            </div>
                        </div>

                        <!-- Income Metric Block -->
                        <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Баланс к выплате</div>
                                <div class="text-2xl font-rimma font-black text-white">2 850 <span class="text-base text-lime-400 font-sans">BYN</span></div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Авто-вывод</div>
                                <div class="text-[11px] font-bold text-lime-400 bg-lime-400/10 px-3 py-1 rounded-full border border-lime-400/20">На карту банка</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- INTERACTIVE INCOME CALCULATOR (Visual Feedback & 8pt Grid) -->
    <section id="calculator" class="py-24 relative z-10 border-t border-white/10 bg-[#0c0c0e]" x-data="{ rate: 45, hours: 16 }">
        <div class="max-w-7xl mx-auto px-6 sm:px-8">
            <div class="text-center mb-16">
                <span class="text-xs font-extrabold text-lime-400 uppercase tracking-widest bg-lime-400/10 border border-lime-400/20 px-4 py-1.5 rounded-full inline-block mb-4">ИНТЕРАКТИВНЫЙ РАСЧЕТ</span>
                <h2 class="text-[clamp(2rem,4vw,3.6rem)] font-rimma font-black uppercase tracking-tight mb-4 text-white">Сколько вы будете <span class="text-lime-400">зарабатывать</span></h2>
                <p class="text-base sm:text-lg text-gray-400 font-medium max-w-2xl mx-auto">Укажите желаемую ставку за 1 час урока и количество часов в неделю, чтобы узнать ваш чистый доход с учетом НПД (10%).</p>
            </div>

            <div class="grid lg:grid-cols-12 gap-8 items-center max-w-5xl mx-auto">
                <!-- Sliders Controls -->
                <div class="lg:col-span-7 glass-panel-dark p-8 space-y-8">
                    <!-- Slider 1: Hourly Rate -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <label class="font-bold text-white text-base">Ставка за 1 час (60 мин):</label>
                            <span class="text-2xl font-rimma font-black text-lime-400" x-text="rate + ' BYN'">45 BYN</span>
                        </div>
                        <input type="range" min="20" max="120" step="5" x-model="rate">
                        <div class="flex justify-between text-xs text-gray-500 font-medium mt-2">
                            <span>20 BYN</span>
                            <span>60 BYN</span>
                            <span>120 BYN</span>
                        </div>
                    </div>

                    <!-- Slider 2: Weekly Hours -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <label class="font-bold text-white text-base">Уроков в неделю:</label>
                            <span class="text-2xl font-rimma font-black text-lime-400" x-text="hours + ' ч/нед'">16 ч/нед</span>
                        </div>
                        <input type="range" min="4" max="40" step="2" x-model="hours">
                        <div class="flex justify-between text-xs text-gray-500 font-medium mt-2">
                            <span>4 ч (подработка)</span>
                            <span>20 ч (стандарт)</span>
                            <span>40 ч (максимум)</span>
                        </div>
                    </div>

                    <div class="bg-white/5 border border-white/10 rounded-xl p-4 flex items-center gap-3">
                        <div class="w-2.5 h-2.5 rounded-full bg-lime-400 flex-shrink-0"></div>
                        <div class="text-xs text-gray-300 leading-relaxed">
                            Расчет выполнен исходя из 4.33 недель в месяце с автоматическим удержанием 10% НПД в приложении МНС РБ.
                        </div>
                    </div>
                </div>

                <!-- Calculation Result Card (One Focus Point) -->
                <div class="lg:col-span-5 glass-panel-dark p-8 md:p-10 border-lime-400/30 text-center relative overflow-hidden bg-gradient-to-b from-white/5 to-white/0">
                    <div class="text-xs font-extrabold uppercase tracking-widest text-gray-400 mb-2">Чистый доход на карту в месяц</div>
                    <div class="text-4xl sm:text-5xl font-rimma font-black text-lime-400 mb-6" x-text="Math.round(rate * hours * 4.33 * 0.90).toLocaleString('ru-RU') + ' BYN'">
                        2 806 BYN
                    </div>

                    <div class="space-y-3 pt-6 border-t border-white/10 text-left text-sm mb-8">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Общий валовый доход:</span>
                            <span class="font-bold text-white" x-text="Math.round(rate * hours * 4.33).toLocaleString('ru-RU') + ' BYN'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Налог НПД (10%):</span>
                            <span class="font-bold text-gray-300" x-text="'-' + Math.round(rate * hours * 4.33 * 0.10).toLocaleString('ru-RU') + ' BYN'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Абонентская плата:</span>
                            <span class="font-bold text-lime-400">0 BYN</span>
                        </div>
                    </div>

                    <a href="/admin/register" class="btn-core btn-lime w-full text-base py-4">
                        Зарабатывать сейчас →
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- PROCESS COMPARISON SCHEME ("Было" vs "Edusfera") -->
    <section class="py-24 relative z-10 bg-grid-dark">
        <div class="max-w-7xl mx-auto px-6 sm:px-8">
            <div class="text-center mb-16">
                <span class="text-xs font-extrabold text-lime-400 uppercase tracking-widest bg-lime-400/10 border border-lime-400/20 px-4 py-1.5 rounded-full inline-block mb-4">СРАВНЕНИЕ ПРОЦЕССА</span>
                <h2 class="text-[clamp(2rem,4vw,3.6rem)] font-rimma font-black uppercase tracking-tight mb-4 text-white">Почему репетиторы выбирают <span class="text-lime-400">Edusfera</span></h2>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                <!-- Old Way (Monochrome Dark) -->
                <div class="glass-panel-dark p-8 border-white/10">
                    <div class="flex items-center gap-3 mb-6 border-b border-white/10 pb-4">
                        <span class="w-8 h-8 rounded-full bg-white/10 text-gray-400 flex items-center justify-center font-bold text-sm">✕</span>
                        <h3 class="text-xl font-bold text-white">Как было раньше</h3>
                    </div>
                    <ul class="space-y-4 text-sm font-medium text-gray-400">
                        <li class="flex items-start gap-3">
                            <span class="text-gray-500 font-bold">✕</span>
                            <span>Ручные переводы на карту и постоянные напоминания об оплате</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-gray-500 font-bold">✕</span>
                            <span>Отмена урока за 10 минут до начала без компенсации времени</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-gray-500 font-bold">✕</span>
                            <span>Хаос в расписании, накладки часов и Excel-таблицы</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-gray-500 font-bold">✕</span>
                            <span>Необходимость созваниваться в сторонних сервисах и отправлять ссылки</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-gray-500 font-bold">✕</span>
                            <span>Риски проверок при постоянных личных переводах от незнакомых людей</span>
                        </li>
                    </ul>
                </div>

                <!-- Edusfera Way (Lime Accent) -->
                <div class="glass-panel-dark p-8 border-lime-400/30 bg-lime-400/[0.02]">
                    <div class="flex items-center gap-3 mb-6 border-b border-lime-400/20 pb-4">
                        <span class="w-8 h-8 rounded-full bg-lime-400/20 text-lime-400 flex items-center justify-center font-bold text-sm">✓</span>
                        <h3 class="text-xl font-bold text-white">На платформе Edusfera</h3>
                    </div>
                    <ul class="space-y-4 text-sm font-medium text-gray-200">
                        <li class="flex items-start gap-3">
                            <span class="text-lime-400 font-bold">✓</span>
                            <span>Автоматический эквайринг и ЕРИП: оплата списывается за бронь</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-lime-400 font-bold">✓</span>
                            <span>Защита Эскроу: при поздней отмене средства выплачиваются вам</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-lime-400 font-bold">✓</span>
                            <span>Авто-календарь: ученики сами выбирают свободные слоты</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-lime-400 font-bold">✓</span>
                            <span>Встроенный класс с доской и видеосвязью прямо в браузере</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-lime-400 font-bold">✓</span>
                            <span>100% Легальность: автоматическая передача чеков НПД в МНС РБ</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- BENTO GRID (Why Edusfera) -->
    <section id="features" class="py-24 relative z-10 border-t border-white/5 bg-[#09090b]">
        <div class="max-w-7xl mx-auto px-6 sm:px-8">
            <div class="text-center mb-16">
                <h2 class="text-[clamp(2rem,4vw,3.5rem)] font-rimma font-black uppercase tracking-tight mb-4 text-white">Архитектура <span class="text-lime-400">модулей</span></h2>
                <p class="text-base sm:text-lg text-gray-400 font-medium max-w-2xl mx-auto">Каждая деталь платформы спроектирована для максимального удобства преподавателя и защиты от рутины.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="glass-panel-dark p-8 flex flex-col h-full group hover:border-lime-400/40">
                    <div class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 text-white flex items-center justify-center mb-6 group-hover:bg-lime-400 group-hover:text-black transition-all duration-300 text-lg font-bold">
                        💳
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Белый доход и ЕРИП</h3>
                    <p class="text-gray-400 leading-relaxed mb-6 font-medium text-sm">Родители оплачивают уроки официально картой любой системы. Мы сами генерируем чеки и отправляем данные в приложение МНС РБ.</p>
                    <div class="mt-auto">
                        <span class="inline-block bg-white/5 border border-white/10 rounded-lg px-3 py-1 text-xs font-bold text-lime-400 uppercase tracking-wider">Без открытия ИП</span>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="glass-panel-dark p-8 flex flex-col h-full group hover:border-lime-400/40">
                    <div class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 text-white flex items-center justify-center mb-6 group-hover:bg-lime-400 group-hover:text-black transition-all duration-300 text-lg font-bold">
                        📅
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Умный Календарь</h3>
                    <p class="text-gray-400 leading-relaxed mb-6 font-medium text-sm">Настройте свои свободные окна один раз. Система исключает накладки и высылает напоминания ученикам за 24 часа и 1 час до урока.</p>
                    <div class="mt-auto">
                        <span class="inline-block bg-white/5 border border-white/10 rounded-lg px-3 py-1 text-xs font-bold text-lime-400 uppercase tracking-wider">СМС и Telegram</span>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="glass-panel-dark p-8 flex flex-col h-full group hover:border-lime-400/40">
                    <div class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 text-white flex items-center justify-center mb-6 group-hover:bg-lime-400 group-hover:text-black transition-all duration-300 text-lg font-bold">
                        💻
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Интерактивный класс</h3>
                    <p class="text-gray-400 leading-relaxed mb-6 font-medium text-sm">Забудьте про сторонние сервисы. Проводите занятия во встроенном классе с видеосвязью, общей доской и быстрой загрузкой ДЗ.</p>
                    <div class="mt-auto">
                        <span class="inline-block bg-white/5 border border-white/10 rounded-lg px-3 py-1 text-xs font-bold text-lime-400 uppercase tracking-wider">Всё в одном месте</span>
                    </div>
                </div>

                <!-- Card 4 (Wide Premium) -->
                <div class="lg:col-span-3 glass-panel-dark p-8 md:p-12 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-8 border-lime-400/30">
                    <div class="relative z-10 max-w-2xl text-left">
                        <div class="inline-flex items-center gap-2 mb-4">
                            <span class="w-2 h-2 rounded-full bg-lime-400"></span>
                            <span class="text-xs font-extrabold tracking-widest uppercase text-lime-400">ПРОЗРАЧНЫЕ УСЛОВИЯ</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-rimma font-black mb-4 text-white leading-tight">Никаких скрытых плат и подписок</h3>
                        <p class="text-gray-300 text-sm sm:text-base leading-relaxed">Вы не платите за размещение анкеты или отклики. Понятная сервисная комиссия берется только с проведенных уроков. Нет проведенного урока — ноль расходов.</p>
                    </div>

                    <div class="relative z-10 flex-shrink-0">
                        <div class="w-32 h-32 sm:w-40 sm:h-40 rounded-full border-2 border-lime-400/30 bg-lime-400/5 flex flex-col items-center justify-center backdrop-blur-sm">
                            <span class="text-3xl sm:text-4xl font-rimma font-black text-lime-400 mb-1">0 <span class="text-base text-lime-400/70 font-sans">BYN</span></span>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">За публикацию</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ROADMAP STEPS (How to start) -->
    <section id="roadmap" class="py-24 relative z-10">
        <div class="max-w-7xl mx-auto px-6 sm:px-8">
            <div class="text-center mb-16">
                <span class="text-xs font-extrabold text-lime-400 uppercase tracking-widest bg-lime-400/10 border border-lime-400/20 px-4 py-1.5 rounded-full inline-block mb-4">БЫСТРЫЙ СТАРТ</span>
                <h2 class="text-[clamp(2rem,4vw,3.5rem)] font-rimma font-black uppercase tracking-tight mb-4 text-white">4 шага к первому <span class="text-lime-400">ученику</span></h2>
            </div>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="glass-panel-dark p-7 text-left relative z-10 group hover:-translate-y-1 hover:border-lime-400/40">
                    <div class="w-12 h-12 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center mb-6 group-hover:border-lime-400/50 transition-colors">
                        <span class="text-xl font-rimma font-black text-white">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Регистрация</h3>
                    <p class="text-gray-400 text-xs font-medium leading-relaxed">Заполните данные о предметах, опыте и желаемой стоимости урока (2 минуты).</p>
                </div>

                <div class="glass-panel-dark p-7 text-left relative z-10 group hover:-translate-y-1 hover:border-lime-400/40">
                    <div class="w-12 h-12 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center mb-6 group-hover:border-lime-400/50 transition-colors">
                        <span class="text-xl font-rimma font-black text-white">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Верификация</h3>
                    <p class="text-gray-400 text-xs font-medium leading-relaxed">Загрузите документ об образовании и паспорт для внесения в реестр РБ.</p>
                </div>

                <div class="glass-panel-dark p-7 text-left relative z-10 group hover:-translate-y-1 hover:border-lime-400/40">
                    <div class="w-12 h-12 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center mb-6 group-hover:border-lime-400/50 transition-colors">
                        <span class="text-xl font-rimma font-black text-white">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Календарь</h3>
                    <p class="text-gray-400 text-xs font-medium leading-relaxed">Отметьте удобные дни и часы для проведения индивидуальных или групповых уроков.</p>
                </div>

                <div class="glass-panel-dark p-7 text-left relative z-10 group hover:-translate-y-1 border-lime-400/30">
                    <div class="w-12 h-12 bg-white/10 border border-lime-400/40 rounded-xl flex items-center justify-center mb-6 group-hover:border-lime-400 transition-colors">
                        <span class="text-xl font-rimma font-black text-lime-400">4</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Уроки и выплата</h3>
                    <p class="text-gray-400 text-xs font-medium leading-relaxed">Проводите занятия и автоматически получайте оплату на карту любого банка РБ.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ SECTION -->
    <section id="faq" class="py-24 relative z-10 bg-[#0c0c0e]">
        <div class="max-w-4xl mx-auto px-6 sm:px-8">
            <div class="text-center mb-16">
                <span class="text-xs font-extrabold text-lime-400 uppercase tracking-widest bg-lime-400/10 border border-lime-400/20 px-4 py-1.5 rounded-full inline-block mb-4">ОТВЕТЫ НА ВОПРОСЫ</span>
                <h2 class="text-[clamp(2rem,4vw,3.2rem)] font-rimma font-black uppercase tracking-tight mb-4 text-white">Всё о налогах и <span class="text-lime-400">работе</span></h2>
            </div>

            <div class="space-y-4" x-data="{ openFaq: 1 }">
                <!-- FAQ 1 -->
                <div class="glass-panel-dark border-white/10 overflow-hidden">
                    <button @click="openFaq = (openFaq === 1 ? 0 : 1)" class="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base active:scale-[0.99] transition-transform">
                        <span>Нужно ли регистрировать ИП для преподавания на Edusfera?</span>
                        <span class="text-lime-400 text-xl font-black ml-4" x-text="openFaq === 1 ? '−' : '+'">+</span>
                    </button>
                    <div x-show="openFaq === 1" x-collapse class="px-6 pb-6 text-sm text-gray-300 leading-relaxed border-t border-white/5 pt-4">
                        Нет, открывать ИП не требуется. Согласно законодательству РБ, репетиторские услуги могут оказываться физическими лицами с уплатой Налога на профессиональный доход (НПД 10%). Платформа автоматически помогает передавать сведения в приложение МНС РБ.
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="glass-panel-dark border-white/10 overflow-hidden">
                    <button @click="openFaq = (openFaq === 2 ? 0 : 2)" class="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base active:scale-[0.99] transition-transform">
                        <span>На карты каких банков выплачиваются средства?</span>
                        <span class="text-lime-400 text-xl font-black ml-4" x-text="openFaq === 2 ? '−' : '+'">+</span>
                    </button>
                    <div x-show="openFaq === 2" x-collapse class="px-6 pb-6 text-sm text-gray-300 leading-relaxed border-t border-white/5 pt-4">
                        Вывод средств доступен на карты любых банков Республики Беларусь (Беларусбанк, Приорбанк, Альфа-Банк, МТБанк, БСБ Банк и др.) через безопасный шлюз bePaid без дополнительных задержек.
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="glass-panel-dark border-white/10 overflow-hidden">
                    <button @click="openFaq = (openFaq === 3 ? 0 : 3)" class="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base active:scale-[0.99] transition-transform">
                        <span>Что происходит, если ученик отменяет урок в последний момент?</span>
                        <span class="text-lime-400 text-xl font-black ml-4" x-text="openFaq === 3 ? '−' : '+'">+</span>
                    </button>
                    <div x-show="openFaq === 3" x-collapse class="px-6 pb-6 text-sm text-gray-300 leading-relaxed border-t border-white/5 pt-4">
                        Платформа использует систему заморозки Эскроу. Если отмена происходит менее чем за 4 часа до начала занятия без уважительной причины, зарезервированные средства компенсируются преподавателю в соответствии с правилами оферты.
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="glass-panel-dark border-white/10 overflow-hidden">
                    <button @click="openFaq = (openFaq === 4 ? 0 : 4)" class="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base active:scale-[0.99] transition-transform">
                        <span>Как быстро анкета появится в каталоге?</span>
                        <span class="text-lime-400 text-xl font-black ml-4" x-text="openFaq === 4 ? '−' : '+'">+</span>
                    </button>
                    <div x-show="openFaq === 4" x-collapse class="px-6 pb-6 text-sm text-gray-300 leading-relaxed border-t border-white/5 pt-4">
                        После заполнения профиля и загрузки документов проверка занимает до 15–30 минут в рабочее время. После верификации ваша анкета моментально начинает отображаться в интеллектуальном поиске учеников.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FINAL CTA SECTION -->
    <section class="py-24 md:py-32 px-6 sm:px-8">
        <div class="max-w-5xl mx-auto bg-[#101014] rounded-3xl p-8 sm:p-12 md:p-16 text-center relative overflow-hidden border border-white/10 shadow-2xl">
            <div class="relative z-10">
                <h2 class="text-3xl sm:text-5xl font-rimma font-black uppercase tracking-tighter mb-6 text-white leading-none">
                    Войдите в <span class="text-lime-400">Элиту</span>
                </h2>
                <p class="text-base sm:text-lg text-gray-300 font-medium mb-10 max-w-xl mx-auto leading-relaxed">
                    Регистрация занимает 15 минут. Никаких вступительных взносов. Начните монетизировать знания в лучшем интерфейсе Беларуси.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="/admin/register" class="btn-core btn-lime text-base px-8 py-4 w-full sm:w-auto">
                        Стать преподавателем →
                    </a>
                    <a href="/admin/login" class="btn-core btn-ghost-dark text-base px-8 py-4 w-full sm:w-auto">
                        Уже есть аккаунт
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('partials.site-footer', ['variant' => 'dark'])

</body>
</html>