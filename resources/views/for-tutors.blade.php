<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edusfera для репетиторов — Больше не ищите учеников. Преподавайте.</title>
    <meta name="description" content="Edusfera приводит заявки от родителей, ведёт расписание, сама напоминает ученикам о занятиях и считает ваш доход. Первый месяц бесплатно.">

    <!-- Font: Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

    @php
        $authUser = auth()->user() ? [
            'id' => auth()->user()->id,
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'role' => is_object(auth()->user()->role) ? auth()->user()->role->value : auth()->user()->role,
            'role_label' => \App\Services\MultiAccountService::roleLabel(auth()->user()->role),
        ] : null;
        $linkedAccounts = $authUser ? app(\App\Services\MultiAccountService::class)->getLinkedAccounts() : [];
    @endphp
    <script>
        window.EDUSFERA_USER = {!! json_encode($authUser) !!};
        window.EDUSFERA_LINKED_ACCOUNTS = {!! json_encode($linkedAccounts) !!};
        window.EDUSFERA_CSRF_TOKEN = "{{ csrf_token() }}";
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        :root {
            --ed-lime: #C6FF33;
            --ed-lime-hover: #d4ff59;
            --ed-lime-glow: rgba(198, 255, 51, 0.25);
            --ed-violet: #7D39EB;
            --ed-violet-glow: rgba(125, 57, 235, 0.25);
            --ed-dark-bg: #010101;
            --ed-surface-card: rgba(15, 23, 42, 0.65);
            --ed-surface-card-hover: rgba(30, 41, 59, 0.75);
            --ed-border: rgba(51, 65, 85, 0.7);
            --ed-border-light: rgba(255, 255, 255, 0.1);
        }

        body, button, input, select, textarea, p, span, a, h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', system-ui, -apple-system, sans-serif;
        }

        .font-rimma {
            font-family: 'Rimma Sans', 'Montserrat', system-ui, sans-serif !important;
        }

        /* Subtle Dark Grid Texture */
        .bg-dark-mesh {
            background-image: 
                radial-gradient(circle at 50% -10%, rgba(125, 57, 235, 0.18) 0%, transparent 60%),
                radial-gradient(circle at 90% 20%, rgba(198, 255, 51, 0.08) 0%, transparent 50%),
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 48px 48px, 48px 48px;
        }

        .bg-card-grid {
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        /* Tech Glass Card Styles */
        .wb-card {
            background-color: var(--ed-surface-card);
            border: 1px solid var(--ed-border);
            border-radius: 1.25rem;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: border-color 0.25s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease;
        }

        .wb-card:hover {
            border-color: rgba(198, 255, 51, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 12px 36px -8px rgba(0, 0, 0, 0.6), 0 0 20px -4px var(--ed-lime-glow);
        }

        .wb-card-dark {
            background-color: #07090E;
            border: 1px solid rgba(51, 65, 85, 0.8);
            border-radius: 1.25rem;
        }

        /* High-Impact CTA Buttons */
        .wb-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.875rem;
            letter-spacing: -0.01em;
            padding: 0.875rem 1.75rem;
            border-radius: 0.75rem;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
            cursor: pointer;
            text-align: center;
        }

        .wb-btn:active {
            transform: translateY(1px) scale(0.98);
        }

        .wb-btn-primary {
            background-color: var(--ed-lime);
            color: #000000;
            border: 1px solid var(--ed-lime);
            box-shadow: 0 0 24px rgba(198, 255, 51, 0.25);
        }

        .wb-btn-primary:hover {
            background-color: var(--ed-lime-hover);
            border-color: var(--ed-lime-hover);
            box-shadow: 0 0 32px rgba(198, 255, 51, 0.4);
            color: #000000;
        }

        .wb-btn-secondary {
            background-color: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
        }

        .wb-btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            color: #ffffff;
        }

        .wb-btn-dark-outline {
            background-color: rgba(0, 0, 0, 0.4);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(12px);
        }

        .wb-btn-dark-outline:hover {
            background-color: rgba(255, 255, 255, 0.08);
            border-color: #ffffff;
        }

        /* Electric Lime Range Slider */
        input[type=range].wb-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 8px;
            background: #1E293B;
            border-radius: 999px;
            outline: none;
            border: 1px solid #334155;
            touch-action: pan-y;
            cursor: pointer;
        }

        input[type=range].wb-slider:focus-visible {
            outline: 2px solid var(--ed-lime);
            outline-offset: 4px;
        }

        /* WebKit Track & Thumb */
        input[type=range].wb-slider::-webkit-slider-runnable-track {
            width: 100%;
            height: 8px;
            background: #1E293B;
            border-radius: 999px;
            border: 1px solid #334155;
        }

        input[type=range].wb-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 28px;
            height: 28px;
            margin-top: -10px;
            border-radius: 50%;
            background: var(--ed-lime);
            border: 3px solid #000000;
            cursor: grab;
            box-shadow: 0 0 16px rgba(198, 255, 51, 0.6);
            transition: transform 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        }

        input[type=range].wb-slider::-webkit-slider-thumb:hover {
            transform: scale(1.15);
            background-color: var(--ed-lime-hover);
            box-shadow: 0 0 24px rgba(198, 255, 51, 0.8);
        }

        input[type=range].wb-slider::-webkit-slider-thumb:active {
            transform: scale(1.25);
            cursor: grabbing;
        }

        /* Firefox Track & Thumb */
        input[type=range].wb-slider::-moz-range-track {
            width: 100%;
            height: 8px;
            background: #1E293B;
            border-radius: 999px;
            border: 1px solid #334155;
        }

        input[type=range].wb-slider::-moz-range-thumb {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--ed-lime);
            border: 3px solid #000000;
            cursor: grab;
            box-shadow: 0 0 16px rgba(198, 255, 51, 0.6);
            transition: transform 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        }

        input[type=range].wb-slider::-moz-range-thumb:hover {
            transform: scale(1.15);
            background-color: var(--ed-lime-hover);
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ scrolled: false, mobileOpen: false }" @scroll.window="scrolled = (window.pageYOffset > 20)" class="nexum-body min-h-screen bg-[#010101] text-white selection:bg-[#C6FF33] selection:text-black overflow-x-hidden antialiased">
    <div id="for-tutors-app" class="w-full">

    <!-- ─── HEADER / NAVIGATION (Liquid Glass Island: Canonical Logo + Tutor Badge + Glass 3 Tabs + Glass Login) ─── -->
    <header class="sticky top-0 w-full z-40 transition-all duration-300 bg-gradient-to-b from-[#010101]/85 via-[#010101]/65 to-[#010101]/25 backdrop-blur-2xl border-b border-white/[0.1] py-3.5 sm:py-4 shadow-[0_10px_35px_-10px_rgba(0,0,0,0.8)]">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 lg:px-12 flex items-center justify-between">
            
            <!-- Brand Mark (Canonical Design System Logo + Tutor Tag) -->
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-white transition-colors group">
                    <svg width="28" height="28" viewBox="0 0 64 64" class="w-7 h-7 rounded-lg shadow-sm group-hover:scale-105 transition-transform">
                        <rect width="64" height="64" rx="14" fill="#7D39EB" />
                        <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                        <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                    </svg>
                    <span class="text-xl font-bold tracking-tight text-white uppercase font-rimma">edusfera</span>
                </a>

                <!-- Liquid Glass Badge: Репетиторам -->
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gradient-to-r from-violet-500/20 via-white/[0.08] to-[#C6FF33]/15 border border-white/20 text-[#C6FF33] font-bold text-[11px] uppercase tracking-wider backdrop-blur-xl shadow-inner">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#C6FF33] animate-pulse"></span>
                    Репетиторам
                </span>
            </div>

            <!-- Center: Floating Liquid Glass Pill Nav (3 Main Tabs Only) -->
            <nav class="hidden md:flex items-center">
                <div class="rounded-full bg-gradient-to-b from-white/[0.14] to-white/[0.04] backdrop-blur-2xl px-2 py-1.5 flex items-center gap-1 border border-white/[0.18] shadow-[0_8px_32px_rgba(0,0,0,0.5),inset_0_1px_1px_rgba(255,255,255,0.25)] ring-1 ring-white/10">
                    <a href="#pains" class="rounded-full px-5 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/15 transition-all">Преимущества</a>
                    <a href="#calculator" class="rounded-full px-5 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/15 transition-all">Калькулятор</a>
                    <a href="#pricing" class="rounded-full px-5 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/15 transition-all">Тарифы</a>
                </div>
            </nav>

            <!-- Right: Liquid Glass Action / Login Button -->
            <div class="hidden sm:flex items-center gap-3">
                @auth
                    @php
                        $user = auth()->user();
                        $unreadMessagesCount = app(\App\Services\ChatUnreadCounter::class)->countForUser($user);
                        $roleLabel = \App\Services\MultiAccountService::roleLabel($user->role);
                    @endphp
                    
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false" 
                                aria-haspopup="true" 
                                :aria-expanded="open ? 'true' : 'false'" 
                                aria-label="Меню профиля пользователя"
                                class="flex items-center gap-3 bg-gradient-to-b from-white/[0.15] to-white/[0.05] border border-white/20 pl-2.5 pr-4 py-1.5 rounded-full hover:border-white/30 active:scale-95 transition-all text-white backdrop-blur-xl shadow-lg">
                            <div class="w-7 h-7 rounded-full bg-[#C6FF33] text-black flex items-center justify-center font-bold text-xs">
                                {{ mb_substr((string)$user->name, 0, 1) }}
                            </div>
                            <div class="text-left hidden sm:block">
                                <div class="text-xs font-bold leading-tight text-white">{{ $user->name }}</div>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <div x-show="open" x-transition.opacity.scale.95 style="display: none;" class="absolute right-0 mt-3 w-64 bg-[#0B0F19] rounded-2xl border border-slate-700/80 shadow-2xl p-2.5 z-50 backdrop-blur-2xl">
                            <a href="/admin" class="block px-4 py-3 text-xs font-bold text-white hover:bg-white/10 rounded-xl transition-colors">Личный кабинет</a>
                            <a href="/admin/transactions" class="block px-4 py-3 text-xs font-bold text-white hover:bg-white/10 rounded-xl transition-colors">Мои финансы</a>
                            <a href="/admin/messages" class="flex items-center justify-between px-4 py-3 text-xs font-bold text-white hover:bg-white/10 rounded-xl transition-colors">
                                <span>Сообщения</span>
                                @if($unreadMessagesCount > 0)
                                    <span class="bg-[#C6FF33] text-black px-2 py-0.5 rounded-full text-[10px] font-black">{{ $unreadMessagesCount }}</span>
                                @endif
                            </a>

                            <div class="h-px bg-slate-800 my-2 mx-2"></div>
                            
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-xs font-bold text-rose-400 hover:bg-rose-500/10 rounded-xl transition-colors">
                                    Выйти
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="/login" class="rounded-full bg-gradient-to-b from-white/[0.15] to-white/[0.05] hover:from-white/[0.22] hover:to-white/[0.1] text-white font-medium text-sm px-5 py-2 border border-white/20 backdrop-blur-xl transition-all flex items-center gap-2 shadow-[0_4px_20px_rgba(0,0,0,0.4),inset_0_1px_0_rgba(255,255,255,0.3)]">
                        <span>Войти</span>
                    </a>
                @endauth
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="md:hidden">
                <button @click="mobileOpen = !mobileOpen" 
                        :aria-expanded="mobileOpen ? 'true' : 'false'" 
                        aria-controls="mobile-drawer" 
                        aria-label="Меню навигации"
                        class="w-10 h-10 rounded-xl border border-white/20 bg-white/10 flex items-center justify-center text-white hover:bg-white/15 active:scale-95 transition-all backdrop-blur-xl">
                    <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

        </div>

        {{-- Mobile Glass Drawer Menu (3 Tabs Only) --}}
        <div id="mobile-drawer" x-show="mobileOpen" x-cloak class="md:hidden border-t border-white/10 bg-[#010101]/95 backdrop-blur-2xl">
            <nav class="max-w-7xl mx-auto px-6 py-6 flex flex-col gap-3 text-base font-semibold">
                <a href="#pains" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-slate-200 hover:text-[#C6FF33] hover:bg-white/10 transition-colors">
                    Преимущества
                </a>
                <a href="#calculator" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-slate-200 hover:text-[#C6FF33] hover:bg-white/10 transition-colors">
                    Калькулятор
                </a>
                <a href="#pricing" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-slate-200 hover:text-[#C6FF33] hover:bg-white/10 transition-colors">
                    Тарифы
                </a>
                <div class="h-px bg-white/10 my-2"></div>
                <a href="/login" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-[#C6FF33] hover:bg-white/10 transition-colors">
                    Войти в личный кабинет →
                </a>
            </nav>
        </div>
    </header>

    <!-- ─── BLOCK 01: HERO SECTION ─── -->
    <section class="relative pt-14 pb-20 md:pt-24 md:pb-32 border-b border-white/10 bg-dark-mesh overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-12 gap-10 lg:gap-14 items-center">
                
                <!-- Left Narrative Column (7 cols) -->
                <div class="lg:col-span-7 flex flex-col items-start text-left">
                    
                    <!-- Section Index & Scarcity Tag -->
                    <div class="flex flex-wrap items-center gap-2 mb-6">
                        <span class="font-mono-tech text-xs font-bold uppercase tracking-wider text-[#C6FF33] bg-[#C6FF33]/10 border border-[#C6FF33]/30 px-3.5 py-1.5 rounded-full backdrop-blur-xl shadow-lg">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#C6FF33] animate-pulse inline-block mr-1.5"></span>
                            ПЛАТФОРМА ДЛЯ НАСТОЯЩИХ ПРОФИ · БЕЛАРУСЬ 2026
                        </span>
                    </div>

                    <!-- Hero Main Headline -->
                    <h1 class="text-[clamp(2.5rem,5.2vw,4.5rem)] font-extrabold tracking-tight text-white leading-[1.02] mb-6">
                        Платформа для настоящих профи. <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#C6FF33] via-lime-200 to-white">Больше не ищите учеников. Преподавайте.</span>
                    </h1>

                    <!-- Hero Lead -->
                    <p class="text-base sm:text-lg text-slate-300 font-normal leading-relaxed mb-8 max-w-2xl">
                        Edusfera — платформа для настоящих профи. Мы приводим заявки от родителей в вашем районе, ведём умное расписание, автоматически напоминаем ученикам о занятиях и формируем отчёты для НПД. Вы тратите время только на оплачиваемые уроки.
                    </p>

                    <!-- Economic Cost Reframe Box -->
                    <div class="w-full bg-slate-900/70 border-l-4 border-l-[#C6FF33] border-y border-r border-slate-800 rounded-r-2xl p-5 sm:p-6 mb-8 max-w-2xl shadow-xl backdrop-blur-xl">
                        <div class="flex items-center gap-2 font-mono-tech text-xs font-bold uppercase tracking-widest text-[#C6FF33] mb-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#C6FF33] animate-ping"></span> ЭКОНОМИЧЕСКИЙ РЕФРЕЙМ
                        </div>
                        <div class="text-sm sm:text-base font-medium text-slate-200 leading-snug">
                            Подписка стоит как <strong>один ваш урок</strong> (40 BYN). Всего один найденный ученик окупает <strong>целый год подписки</strong>.
                        </div>
                    </div>

                    <!-- CTA Actions -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5 mb-10 w-full sm:w-auto">
                        <a href="/register?role=tutor&plan=pro" class="wb-btn wb-btn-primary text-sm sm:text-base py-4 px-8 shadow-[0_0_30px_rgba(198,255,51,0.25)]">
                            Занять место в своей нише →
                        </a>
                        <a href="#calculator" class="wb-btn wb-btn-secondary text-sm sm:text-base py-4 px-7">
                            Рассчитать потери ↓
                        </a>
                    </div>

                    <!-- 3 Trust Metrics -->
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 pt-6 border-t border-slate-800 w-full max-w-2xl text-left">
                        <div>
                            <div class="font-mono-tech text-[10px] sm:text-xs text-slate-500 uppercase mb-0.5">Условия</div>
                            <div class="text-[11px] sm:text-sm font-bold text-white">1 мес. бесплатно</div>
                            <div class="text-[10px] sm:text-[11px] text-slate-400 leading-tight mt-0.5">На любом тарифе</div>
                        </div>
                        <div class="border-l border-slate-800 pl-2.5 sm:pl-4">
                            <div class="font-mono-tech text-[10px] sm:text-xs text-slate-500 uppercase mb-0.5">Привилегия</div>
                            <div class="text-[11px] sm:text-sm font-bold text-white">Статус Основателя</div>
                            <div class="text-[10px] sm:text-[11px] text-slate-400 leading-tight mt-0.5">Фиксация цены</div>
                        </div>
                        <div class="border-l border-slate-800 pl-2.5 sm:pl-4">
                            <div class="font-mono-tech text-[10px] sm:text-xs text-slate-500 uppercase mb-0.5">Гарантия</div>
                            <div class="text-[11px] sm:text-sm font-bold text-white">Заявки родителей</div>
                            <div class="text-[10px] sm:text-[11px] text-slate-400 leading-tight mt-0.5">Или продление 0 BYN</div>
                        </div>
                    </div>

                </div>

                <!-- Right Visual Column: Dark Tech Console Mockup (5 cols) -->
                <div class="lg:col-span-5 w-full">
                    <div class="wb-card bg-[#0B0F19]/90 p-6 sm:p-8 border-slate-800 shadow-[0_20px_50px_rgba(0,0,0,0.8)] relative overflow-hidden backdrop-blur-2xl">
                        
                        <!-- Ambient backlight -->
                        <div class="absolute -top-24 -right-24 w-48 h-48 bg-[#7D39EB]/20 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-[#C6FF33]/10 rounded-full blur-3xl pointer-events-none"></div>

                        <!-- Console Header -->
                        <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-800 relative z-10">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#C6FF33]"></span>
                                <span class="font-mono-tech text-xs font-bold uppercase tracking-wider text-slate-200">Кабинет преподавателя</span>
                            </div>
                            <span class="font-mono-tech text-[10px] font-bold uppercase tracking-wider text-black bg-[#C6FF33] px-2.5 py-1 rounded-full shadow-sm">
                                FOUNDER #38
                            </span>
                        </div>

                        <!-- Live Lead Simulated Card -->
                        <div class="space-y-4 mb-6 relative z-10">
                            
                            <!-- Application Card -->
                            <div class="bg-slate-900/80 border border-slate-700/80 rounded-2xl p-4 hover:border-[#C6FF33]/50 transition-colors shadow-lg">
                                <div class="flex items-start justify-between gap-3 mb-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#7D39EB]/30 to-[#C6FF33]/20 border border-slate-700 flex items-center justify-center font-bold text-sm shrink-0">
                                            🎯
                                        </div>
                                        <div>
                                            <div class="font-bold text-xs text-white">Новая заявка: ЦТ Математика</div>
                                            <div class="font-mono-tech text-[11px] text-slate-400">Минск, Первомайский р-н</div>
                                        </div>
                                    </div>
                                    <span class="font-mono-tech text-[10px] font-bold text-[#C6FF33] bg-[#C6FF33]/10 border border-[#C6FF33]/30 px-2 py-0.5 rounded-full shrink-0">
                                        40 BYN/час
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-800">
                                    <span>Цель: 85+ баллов</span>
                                    <span class="font-semibold text-white">Автоподбор 98%</span>
                                </div>
                            </div>

                            <!-- Automatic Notification Simulator -->
                            <div class="bg-black/70 text-white rounded-2xl p-4 border border-slate-800 shadow-inner">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-[#C6FF33] animate-ping"></span>
                                        <span class="font-mono-tech text-[11px] font-bold text-white uppercase tracking-wide">Автонапоминание</span>
                                    </div>
                                    <span class="font-mono-tech text-[10px] text-slate-400">14:00 (за 2ч)</span>
                                </div>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    SMS и Push ученику: «Напоминаем об уроке физики в 16:00». Срывы занятий сведены к нулю.
                                </p>
                                <div class="mt-3 flex items-center justify-between pt-2 border-t border-slate-800/80 text-[10px] font-mono-tech text-slate-400">
                                    <span>Статус: Доставлено</span>
                                    <span class="text-[#C6FF33] font-bold">Подтверждено учеником ✓</span>
                                </div>
                            </div>

                        </div>

                        <!-- Console Footer Stats -->
                        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-800 relative z-10">
                            <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-800">
                                <div class="font-mono-tech text-[10px] uppercase text-slate-400">Сэкономлено времени</div>
                                <div class="font-bold text-xl text-white mt-0.5">24 <span class="text-xs font-normal text-slate-400">часа/мес</span></div>
                            </div>
                            <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-800">
                                <div class="font-mono-tech text-[10px] uppercase text-slate-400">Отчёт для НПД</div>
                                <div class="font-bold text-xl text-[#C6FF33] mt-0.5">5 <span class="text-xs font-normal text-slate-400">минут</span></div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ─── BLOCK 02: PAIN POINTS ─── -->
    <section id="pains" class="py-20 md:py-28 border-b border-white/10 bg-[#010101] relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="max-w-3xl mb-16 text-left">
                <span class="font-mono-tech text-xs font-bold uppercase tracking-wider text-[#C6FF33] bg-[#C6FF33]/10 border border-[#C6FF33]/30 px-3 py-1 rounded-full inline-block mb-3">
                    02 // АНАЛИЗ ПОТЕРЬ И РУТИНЫ
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight mb-4">
                    Знакомая <span class="text-[#C6FF33]">рутина?</span>
                </h2>
                <p class="text-base sm:text-lg text-slate-300 font-normal leading-relaxed">
                    Большинство репетиторов тратят до трети рабочего времени на операционный хаос вместо оплачиваемых академических часов.
                </p>
            </div>

            <!-- 3 Columns Grid -->
            <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
                
                <!-- Pain 1 -->
                <div class="wb-card p-7 sm:p-8 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
                            <span class="font-mono-tech text-xs font-bold text-slate-400">01 / 03</span>
                            <span class="w-9 h-9 rounded-xl bg-slate-800/80 text-rose-400 border border-slate-700 flex items-center justify-center font-bold text-sm">🕳️</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Пустые окна и сорванные уроки</h3>
                        <p class="text-sm text-slate-300 leading-relaxed mb-6">
                            Ученик забыл, отменил за полчаса или пропал после пары уроков. Это 2–4 сорванных занятия в месяц — <strong>минус 60–160 BYN</strong>, которые вам никто не компенсирует.
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-800">
                        <div class="font-mono-tech text-[11px] font-bold uppercase tracking-wider text-rose-400">
                            Потеря: до 1 500 BYN в год
                        </div>
                    </div>
                </div>

                <!-- Pain 2 -->
                <div class="wb-card p-7 sm:p-8 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
                            <span class="font-mono-tech text-xs font-bold text-slate-400">02 / 03</span>
                            <span class="w-9 h-9 rounded-xl bg-slate-800/80 text-amber-400 border border-slate-700 flex items-center justify-center font-bold text-sm">⏳</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Рутина съедает вечера</h3>
                        <p class="text-sm text-slate-300 leading-relaxed mb-6">
                            Переписки в 5 разных мессенджерах (Telegram, Viber, WhatsApp), бесконечные согласования времени, ручные напоминания об оплатах — <strong>5–7 часов в неделю</strong> впустую.
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-800">
                        <div class="font-mono-tech text-[11px] font-bold uppercase tracking-wider text-amber-400">
                            Потеря: 25+ часов рутины в месяц
                        </div>
                    </div>
                </div>

                <!-- Pain 3 -->
                <div class="wb-card p-7 sm:p-8 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
                            <span class="font-mono-tech text-xs font-bold text-slate-400">03 / 03</span>
                            <span class="w-9 h-9 rounded-xl bg-slate-800/80 text-[#C6FF33] border border-slate-700 flex items-center justify-center font-bold text-sm">👥</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Вы — один из сотни на досках</h3>
                        <p class="text-sm text-slate-300 leading-relaxed mb-6">
                            На бесплатных досках объявлений ваша анкета тонет среди сотен одинаковых профилей. Родители выбирают тех, кто просто выше в списке или демпингует цену.
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-800">
                        <div class="font-mono-tech text-[11px] font-bold uppercase tracking-wider text-slate-300">
                            Итог: размытие спроса и демпинг
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ─── BLOCK 03: SOLUTION / DIGITAL WORK ENVIRONMENT ─── -->
    <section id="features" class="py-20 md:py-28 border-b border-white/10 bg-[#0B0F19]/40 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="max-w-3xl mb-16 text-left">
                <span class="font-mono-tech text-xs font-bold uppercase tracking-wider text-[#C6FF33] bg-[#C6FF33]/10 border border-[#C6FF33]/30 px-3 py-1 rounded-full inline-block mb-3">
                    03 // АРХИТЕКТУРА РЕШЕНИЯ
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight mb-4">
                    Не доска объявлений. <br>
                    <span class="text-[#C6FF33]">Ваша рабочая среда.</span>
                </h2>
                <p class="text-base sm:text-lg text-slate-300 font-normal leading-relaxed">
                    Edusfera объединяет стабильный поток заявок и полный инструментарий управления обучением в единой экосистеме.
                </p>
            </div>

            <!-- 6 Grid Modules -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div class="wb-card p-7 flex flex-col justify-between">
                    <div>
                        <div class="font-mono-tech text-xs font-bold text-slate-400 mb-4">МОДУЛЬ 01</div>
                        <div class="w-11 h-11 rounded-xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-xl mb-4">🎯</div>
                        <h3 class="text-lg font-bold text-white mb-2">Заявки от родителей в нише</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            По вашему предмету и району города. В каждой связке удерживается ограниченное число анкет, чтобы заявки не размывались на сотни конкурентов.
                        </p>
                    </div>
                </div>

                <div class="wb-card p-7 flex flex-col justify-between">
                    <div>
                        <div class="font-mono-tech text-xs font-bold text-slate-400 mb-4">МОДУЛЬ 02</div>
                        <div class="w-11 h-11 rounded-xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-xl mb-4">📅</div>
                        <h3 class="text-lg font-bold text-white mb-2">Расписание и автонапоминания</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Ученики видят только свободные слоты. Система сама отправляет напоминания за сутки и за 2 часа, сокращая забывания и срывы на 80%.
                        </p>
                    </div>
                </div>

                <div class="wb-card p-7 flex flex-col justify-between">
                    <div>
                        <div class="font-mono-tech text-xs font-bold text-slate-400 mb-4">МОДУЛЬ 03</div>
                        <div class="w-11 h-11 rounded-xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-xl mb-4">💬</div>
                        <h3 class="text-lg font-bold text-white mb-2">Чат с файлами и голосовыми</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Вся учебная переписка, домашние задания, скриншоты конспектов и голосовые комментарии хранятся в защищённом профиле занятия.
                        </p>
                    </div>
                </div>

                <div class="wb-card p-7 flex flex-col justify-between">
                    <div>
                        <div class="font-mono-tech text-xs font-bold text-slate-400 mb-4">МОДУЛЬ 04</div>
                        <div class="w-11 h-11 rounded-xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-xl mb-4">📊</div>
                        <h3 class="text-lg font-bold text-white mb-2">Журнал доходов и отчёты для НПД</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Автоматический расчёт налогооблагаемой базы и выгрузка статистики. Закрытие месяца и расчет налога на профдоход занимают ровно 5 минут.
                        </p>
                    </div>
                </div>

                <div class="wb-card p-7 flex flex-col justify-between">
                    <div>
                        <div class="font-mono-tech text-xs font-bold text-slate-400 mb-4">МОДУЛЬ 05</div>
                        <div class="w-11 h-11 rounded-xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-xl mb-4">⭐</div>
                        <h3 class="text-lg font-bold text-white mb-2">Профиль, который продаёт</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Подтверждённые отзывы реальных учеников, дипломы и сертификаты, видео-визитка и динамика среднего балла ваших выпускников на ЦТ/ЦЭ.
                        </p>
                    </div>
                </div>

                <div class="wb-card border-[#C6FF33]/50 p-7 flex flex-col justify-between relative shadow-lg shadow-[#C6FF33]/5">
                    <div class="absolute -top-3 right-4 font-mono-tech text-[9px] font-bold uppercase tracking-widest bg-[#C6FF33] text-black px-2.5 py-0.5 rounded-full">
                        PRO / PREMIUM
                    </div>
                    <div>
                        <div class="font-mono-tech text-xs font-bold text-[#C6FF33] mb-4">МОДУЛЬ 06</div>
                        <div class="w-11 h-11 rounded-xl bg-slate-800/80 text-[#C6FF33] border border-slate-700 flex items-center justify-center text-xl mb-4">💎</div>
                        <h3 class="text-lg font-bold text-white mb-2">Google Calendar и видео-класс</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Автоматическая синхронизация с личным Google Календарем и встроенный интерактивный видеокласс без необходимости оплачивать сторонний софт.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ─── BLOCK 04: LOSS CALCULATOR ─── -->
    <section id="calculator" class="py-20 md:py-28 border-b border-white/10 bg-[#010101] bg-card-grid" 
             x-data="{ 
                 rate: 35, 
                 canceledPerMonth: 3, 
                 planPriceYear: 480,
                 get annualLoss() { return this.rate * this.canceledPerMonth * 12; },
                 get netSaved() { return Math.max(0, this.annualLoss - this.planPriceYear); }
             }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="max-w-3xl mb-16 text-left">
                <span class="font-mono-tech text-xs font-bold uppercase tracking-wider text-[#C6FF33] bg-[#C6FF33]/10 border border-[#C6FF33]/30 px-3 py-1 rounded-full inline-block mb-3">
                    04 // ИЗМЕРИТЕЛЬНЫЙ ПРИБОР
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight mb-4">
                    Калькулятор <span class="text-[#C6FF33]">потерь</span>
                </h2>
                <p class="text-base sm:text-lg text-slate-300 font-normal leading-relaxed">
                    Двигайте ползунки, чтобы рассчитать сумму упущенной выгоды на отменах в сравнении со стоимостью подписки Pro.
                </p>
            </div>

            <!-- Precision Instrument Console (12-Col Grid) -->
            <div class="grid lg:grid-cols-12 gap-8 items-stretch max-w-6xl">
                
                <!-- Left Sliders Console (7 cols) -->
                <div class="lg:col-span-7 wb-card p-6 sm:p-10 flex flex-col justify-between space-y-8 bg-slate-900/70 border-slate-800">
                    
                    <!-- Slider 1: Hourly Rate -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label for="tutor-rate-slider" class="font-bold text-sm sm:text-base text-white cursor-pointer">Ваша ставка за 1 урок (60 мин):</label>
                            <span class="font-mono-tech text-xl sm:text-2xl font-black text-[#C6FF33] bg-black/60 border border-slate-800 px-3 py-1 rounded-xl shadow-inner" x-text="rate + ' BYN'">
                                35 BYN
                            </span>
                        </div>
                        <input id="tutor-rate-slider" 
                               type="range" 
                               min="15" 
                               max="100" 
                               step="5" 
                               x-model.number="rate" 
                               aria-label="Ваша ставка за 1 урок в белорусских рублях"
                               aria-valuemin="15"
                               aria-valuemax="100"
                               :aria-valuenow="rate"
                               :aria-valuetext="rate + ' BYN'"
                               aria-valuenow="35"
                               class="wb-slider mt-2">
                        <div class="flex justify-between font-mono-tech text-[11px] text-slate-400 mt-2">
                            <span>15 BYN</span>
                            <span>35 BYN (базовая)</span>
                            <span>60 BYN</span>
                            <span>100 BYN</span>
                        </div>
                    </div>

                    <!-- Slider 2: Canceled lessons -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label for="tutor-canceled-slider" class="font-bold text-sm sm:text-base text-white cursor-pointer">Сорванных / отмененных уроков в месяц:</label>
                            <span class="font-mono-tech text-xl sm:text-2xl font-black text-rose-400 bg-black/60 border border-slate-800 px-3 py-1 rounded-xl shadow-inner" x-text="canceledPerMonth + ' ур./мес'">
                                3 ур./мес
                            </span>
                        </div>
                        <input id="tutor-canceled-slider" 
                               type="range" 
                               min="1" 
                               max="10" 
                               step="1" 
                               x-model.number="canceledPerMonth" 
                               aria-label="Количество отмененных или сорванных уроков в месяц"
                               aria-valuemin="1"
                               aria-valuemax="10"
                               :aria-valuenow="canceledPerMonth"
                               :aria-valuetext="canceledPerMonth + ' ур./мес'"
                               aria-valuenow="3"
                               class="wb-slider mt-2">
                        <div class="flex justify-between font-mono-tech text-[11px] text-slate-400 mt-2">
                            <span>1 урок</span>
                            <span>3 урока (среднее)</span>
                            <span>6 уроков</span>
                            <span>10 уроков</span>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 flex items-start gap-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#C6FF33] mt-1 shrink-0"></span>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Автонапоминания (SMS + Push) и строгое расписание Edusfera сокращают отмены и забывания на <strong>70–80%</strong> уже в первый месяц.
                        </p>
                    </div>

                </div>

                <!-- Right Calculation Slab (5 cols) -->
                <div class="lg:col-span-5 wb-card-dark p-7 sm:p-10 flex flex-col justify-between text-left relative overflow-hidden shadow-2xl bg-gradient-to-b from-[#0B0F19] to-black border-slate-800">
                    
                    <!-- Ambient Glow -->
                    <div class="absolute -top-16 -right-16 w-36 h-36 bg-[#7D39EB]/30 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative z-10">
                        <div class="font-mono-tech text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            ГОДОВОЙ УЩЕРБ БЕЗ ПЛАТФОРМЫ
                        </div>
                        <div class="font-extrabold text-4xl sm:text-5xl text-rose-400 tracking-tight mb-6" x-text="annualLoss.toLocaleString('ru-RU') + ' BYN'">
                            1 260 BYN
                        </div>

                        <div class="space-y-3.5 pt-6 border-t border-slate-800 text-xs">
                            <div class="flex justify-between items-center text-slate-300">
                                <span>Стоимость Edusfera Pro за год:</span>
                                <span class="font-mono-tech font-bold text-white">480 BYN</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-300">
                                <span>1-й месяц в подарок (Trial):</span>
                                <span class="font-mono-tech font-bold text-[#C6FF33]">0 BYN</span>
                            </div>
                            <div class="pt-3 border-t border-slate-800 flex justify-between items-baseline">
                                <span class="font-bold text-sm text-white">Чистая сохранённая выгода:</span>
                                <span class="font-mono-tech font-black text-2xl text-[#C6FF33]" x-text="'+ ' + netSaved.toLocaleString('ru-RU') + ' BYN'">
                                    + 780 BYN
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-800 relative z-10">
                        <a href="/register?role=tutor&plan=pro" class="wb-btn wb-btn-primary w-full text-sm py-4 shadow-[0_0_24px_rgba(198,255,51,0.3)]">
                            Сохранить доход с Edusfera Pro →
                        </a>
                        <div class="text-center font-mono-tech text-[10px] text-slate-400 mt-2.5">
                            Активация без привязки банковской карты
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    <!-- ─── BLOCK 05: VALUE STACK ─── -->
    <section class="py-20 md:py-28 border-b border-white/10 bg-[#0B0F19]/40 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="max-w-3xl mb-16 text-left">
                <span class="font-mono-tech text-xs font-bold uppercase tracking-wider text-[#C6FF33] bg-[#C6FF33]/10 border border-[#C6FF33]/30 px-3 py-1 rounded-full inline-block mb-3">
                    05 // СРАВНЕНИЕ РЫНОЧНОЙ СТОИМОСТИ
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight mb-4">
                    Что вы получаете в тарифе <span class="text-[#C6FF33]">Pro</span>
                </h2>
                <p class="text-base sm:text-lg text-slate-300 font-normal leading-relaxed">
                    Сравнение реальной рыночной стоимости разрозненных сервисов против единой подписки Edusfera.
                </p>
            </div>

            <!-- Value Stack Container -->
            <div class="max-w-4xl wb-card p-7 sm:p-10 border-slate-800 shadow-2xl bg-slate-900/60">
                <div class="divide-y divide-slate-800">
                    
                    <div class="py-4 sm:py-5 flex flex-col sm:flex-row items-start sm:items-start justify-between gap-2 sm:gap-4 first:pt-0">
                        <div>
                            <div class="font-bold text-sm sm:text-base text-white">Поток целевых заявок от родителей в вашем районе</div>
                            <div class="text-xs text-slate-400 mt-1">Привлечение одного ученика через таргет в соцсетях стоит 25–50 BYN. В Edusfera заявки приходят сами.</div>
                        </div>
                        <div class="font-mono-tech text-xs text-slate-400 line-through shrink-0 whitespace-nowrap bg-slate-800/80 px-2.5 py-1 rounded-lg border border-slate-700">150 BYN / мес</div>
                    </div>

                    <div class="py-4 sm:py-5 flex flex-col sm:flex-row items-start sm:items-start justify-between gap-2 sm:gap-4">
                        <div>
                            <div class="font-bold text-sm sm:text-base text-white">5–7 часов свободного времени каждую неделю</div>
                            <div class="text-xs text-slate-400 mt-1">Расписание, бронирование слотов и напоминания ученикам ведутся автоматически без рутины.</div>
                        </div>
                        <div class="font-mono-tech text-xs text-slate-400 line-through shrink-0 whitespace-nowrap bg-slate-800/80 px-2.5 py-1 rounded-lg border border-slate-700">80 BYN / мес</div>
                    </div>

                    <div class="py-4 sm:py-5 flex flex-col sm:flex-row items-start sm:items-start justify-between gap-2 sm:gap-4">
                        <div>
                            <div class="font-bold text-sm sm:text-base text-white">Снижение пропусков и срывов уроков на 80%</div>
                            <div class="text-xs text-slate-400 mt-1">Автоматический SMS и Push шлюз за сутки и за 2 часа до урока с подтверждением от ученика.</div>
                        </div>
                        <div class="font-mono-tech text-xs text-slate-400 line-through shrink-0 whitespace-nowrap bg-slate-800/80 px-2.5 py-1 rounded-lg border border-slate-700">100 BYN / мес</div>
                    </div>

                    <div class="py-4 sm:py-5 flex flex-col sm:flex-row items-start sm:items-start justify-between gap-2 sm:gap-4 last:pb-0">
                        <div>
                            <div class="font-bold text-sm sm:text-base text-white">Автоматические отчёты для налога НПД за 5 минут</div>
                            <div class="text-xs text-slate-400 mt-1">Готовая сводка доходов, экономия на консультациях бухгалтера и спокойное закрытие месяца.</div>
                        </div>
                        <div class="font-mono-tech text-xs text-slate-400 line-through shrink-0 whitespace-nowrap bg-slate-800/80 px-2.5 py-1 rounded-lg border border-slate-700">50 BYN / мес</div>
                    </div>

                </div>

                <!-- Summary Bar -->
                <div class="mt-8 pt-6 border-t border-slate-700 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 bg-slate-900/90 p-6 rounded-2xl border border-slate-800">
                    <div>
                        <div class="font-mono-tech text-xs text-slate-400 uppercase">
                            Рыночная стоимость инструментов: <span class="line-through text-slate-400 font-bold">380+ BYN / мес</span>
                        </div>
                        <div class="text-2xl font-extrabold text-white mt-1">
                            Цена Edusfera Pro: <span class="text-[#C6FF33]">40 BYN / месяц</span>
                        </div>
                    </div>
                    <a href="/register?role=tutor&plan=pro" class="wb-btn wb-btn-primary text-xs sm:text-sm py-3 px-6 shrink-0 w-full sm:w-auto shadow-[0_0_20px_rgba(198,255,51,0.25)]">
                        Начать бесплатно (1 мес) →
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- ─── BLOCK 06: PRICING GRID ─── -->
    <section id="pricing" class="py-20 md:py-28 border-b border-white/10 bg-[#010101]" x-data="{ yearly: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header & Switcher -->
            <div class="max-w-3xl mb-12 text-left">
                <span class="font-mono-tech text-xs font-bold uppercase tracking-wider text-[#C6FF33] bg-[#C6FF33]/10 border border-[#C6FF33]/30 px-3 py-1 rounded-full inline-block mb-3">
                    06 // ТАРИФНЫЕ ПЛАНЫ
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight mb-4">
                    Честные <span class="text-[#C6FF33]">тарифы</span>
                </h2>
                <p class="text-base sm:text-lg text-slate-300 font-normal leading-relaxed mb-8">
                    Первый месяц — бесплатно на любом тарифе. Переключайтесь или отменяйте в любой момент без скрытых платежей.
                </p>

                <!-- Yearly Toggle Switch -->
                <div class="inline-flex items-center gap-2 bg-slate-900/90 border border-slate-800 p-1.5 rounded-2xl" role="group" aria-label="Выбор периода оплаты">
                    <button @click="yearly = false" type="button" 
                            :aria-pressed="(!yearly).toString()"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all"
                            :class="!yearly ? 'bg-white text-black shadow-md' : 'text-slate-400 hover:text-white'">
                        Помесячно
                    </button>
                    <button @click="yearly = true" type="button" 
                            :aria-pressed="yearly.toString()"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
                            :class="yearly ? 'bg-[#C6FF33] text-black shadow-[0_0_20px_rgba(198,255,51,0.3)]' : 'text-slate-400 hover:text-white'">
                        <span>Оплата за год</span>
                        <span class="font-mono-tech text-[10px] bg-black text-[#C6FF33] px-2 py-0.5 rounded-md font-extrabold">−20%</span>
                    </button>
                </div>
            </div>

            <!-- 3 Pricing Cards Grid -->
            <div class="grid lg:grid-cols-3 gap-6 lg:gap-8 items-stretch max-w-6xl">
                
                <!-- Plan 1: Basic -->
                <div class="wb-card p-7 sm:p-8 flex flex-col justify-between bg-slate-900/60">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-800">
                            <h3 class="text-xl font-bold text-white">Basic</h3>
                            <span class="font-mono-tech text-xs text-slate-400 uppercase">Для старта</span>
                        </div>
                        
                        <div class="mb-6">
                            <div class="flex items-baseline gap-1.5">
                                <span class="font-extrabold text-4xl text-white" x-text="yearly ? '16' : '20'">20</span>
                                <span class="font-mono-tech text-xs text-slate-400">BYN / месяц</span>
                            </div>
                            <div class="font-mono-tech text-xs font-bold text-[#C6FF33] mt-1">1-й месяц бесплатно (0 BYN)</div>
                            <div x-show="yearly" x-cloak class="font-mono-tech text-[11px] text-slate-400 mt-1">192 BYN / год при оплате за год</div>
                        </div>

                        <ul class="space-y-3 text-xs text-slate-300 mb-8">
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> Базовый профиль (фото, предмет, цены)
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> Просмотр входящих заявок
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> Базовый чат с учениками
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> Email-уведомления
                            </li>
                            <li class="flex items-start gap-2.5 text-slate-600 line-through">
                                <span>✕</span> Онлайн-календарь и расписание
                            </li>
                            <li class="flex items-start gap-2.5 text-slate-600 line-through">
                                <span>✕</span> Отчёты для НПД и статистика
                            </li>
                        </ul>
                    </div>

                    <a href="/register?role=tutor&plan=basic" class="wb-btn wb-btn-secondary w-full text-xs py-3.5">
                        Выбрать Basic (Trial) →
                    </a>
                </div>

                <!-- Plan 2: Pro (Featured) -->
                <div class="wb-card bg-[#0B0F19] border-2 border-[#C6FF33] p-7 sm:p-8 flex flex-col justify-between relative shadow-[0_0_40px_rgba(198,255,51,0.15)] lg:-translate-y-2">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-[#C6FF33] text-black font-mono-tech font-black uppercase text-[10px] tracking-wider px-3.5 py-1 rounded-full shadow-lg">
                        ВЫБОР РЕДАКЦИИ · ДЛЯ 5+ УЧЕНИКОВ
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-800 mt-2">
                            <h3 class="text-2xl font-bold text-white">Pro</h3>
                            <span class="font-mono-tech text-xs font-bold text-[#C6FF33] uppercase">Популярный</span>
                        </div>
                        
                        <div class="mb-6">
                            <div class="flex items-baseline gap-1.5">
                                <span class="font-extrabold text-5xl text-[#C6FF33]" x-text="yearly ? '32' : '40'">40</span>
                                <span class="font-mono-tech text-xs text-slate-300">BYN / месяц</span>
                            </div>
                            <div class="font-mono-tech text-xs font-bold text-[#C6FF33] mt-1">1-й месяц бесплатно (0 BYN)</div>
                            <div x-show="yearly" x-cloak class="font-mono-tech text-[11px] text-[#C6FF33] font-medium mt-1">384 BYN / год при оплате за год</div>
                        </div>

                        <ul class="space-y-3 text-xs text-slate-200 font-medium mb-8">
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> <strong>Расширенный профиль</strong> (+ отзывы, дипломы)
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> <strong>Отклик на заявки</strong> (до 10 в месяц)
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> <strong>Календарь + автонапоминания</strong>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> Чат с файлами и голосовыми сообщениями
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> <strong>Журнал доходов + отчёты для НПД</strong>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> Push + Email уведомления ученикам
                            </li>
                        </ul>
                    </div>

                    <a href="/register?role=tutor&plan=pro" class="wb-btn wb-btn-primary w-full text-xs py-4 shadow-[0_0_24px_rgba(198,255,51,0.35)]">
                        Выбрать Pro (1 мес бесплатно) →
                    </a>
                </div>

                <!-- Plan 3: Premium -->
                <div class="wb-card p-7 sm:p-8 flex flex-col justify-between bg-slate-900/60">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-800">
                            <h3 class="text-xl font-bold text-white">Premium</h3>
                            <span class="font-mono-tech text-xs text-slate-400 uppercase">Топ-эксперт</span>
                        </div>
                        
                        <div class="mb-6">
                            <div class="flex items-baseline gap-1.5">
                                <span class="font-extrabold text-4xl text-white" x-text="yearly ? '48' : '60'">60</span>
                                <span class="font-mono-tech text-xs text-slate-400">BYN / месяц</span>
                            </div>
                            <div class="font-mono-tech text-xs font-bold text-[#C6FF33] mt-1">1-й месяц бесплатно (0 BYN)</div>
                            <div x-show="yearly" x-cloak class="font-mono-tech text-[11px] text-slate-400 mt-1">576 BYN / год при оплате за год</div>
                        </div>

                        <ul class="space-y-3 text-xs text-slate-300 mb-8">
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> <strong>Топ-5 в поиске</strong> + видео-визитка
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> <strong>Безлимитные отклики</strong> + автоподбор
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> <strong>Синхронизация с Google Calendar</strong>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> <strong>Встроенный видеокласс (до 45 мин)</strong>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> Расширенная аналитика + экспорт
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="text-[#C6FF33] font-bold">✓</span> SMS-уведомления + приоритет поддержки
                            </li>
                        </ul>
                    </div>

                    <a href="/register?role=tutor&plan=premium" class="wb-btn wb-btn-secondary w-full text-xs py-3.5">
                        Выбрать Premium (Trial) →
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- ─── BLOCK 07 & 08: GUARANTEE & FOUNDER STATUS ─── -->
    <section class="py-20 md:py-28 border-b border-white/10 bg-[#0B0F19]/40 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-8 max-w-6xl">
                
                <!-- Block 07: Risk Reversal Guarantee -->
                <div class="wb-card p-8 sm:p-10 flex flex-col justify-between border-slate-800 bg-slate-900/60">
                    <div>
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
                            <span class="font-mono-tech text-xs font-bold text-[#C6FF33]">07 // ГАРАНТИЯ РЕЗУЛЬТАТА</span>
                            <span class="w-9 h-9 rounded-xl bg-slate-800/80 text-[#C6FF33] border border-slate-700 flex items-center justify-center font-bold text-sm">🛡️</span>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3 leading-snug">30 дней без заявок — продление 0 BYN</h3>
                        <p class="text-sm text-slate-300 leading-relaxed mb-6">
                            Если за первый оплаченный месяц на тарифе Pro или Premium вы не получили ни одной заявки от родителей — мы продлеваем подписку бесплатно, пока заявка не придёт. Вы ничем не рискуете.
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-800 flex items-center gap-2 font-mono-tech text-xs font-bold text-[#C6FF33]">
                        <span>✓</span> Честное взаимное партнёрство без скрытых условий
                    </div>
                </div>

                <!-- Block 08: Scarcity & Founder Badge -->
                <div class="wb-card p-8 sm:p-10 flex flex-col justify-between border-slate-800 bg-slate-900/60">
                    <div>
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
                            <span class="font-mono-tech text-xs font-bold text-white">08 // СТАТУС ОСНОВАТЕЛЯ</span>
                            <span class="w-9 h-9 rounded-xl bg-slate-800/80 text-amber-400 border border-slate-700 flex items-center justify-center font-bold text-sm">👑</span>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3 leading-snug">Ограниченные ниши в городах РБ</h3>
                        <p class="text-sm text-slate-300 leading-relaxed mb-6">
                            В каждой связке «предмет + район» мы удерживаем ограниченную квоту репетиторов, чтобы исключить демпинг. Первым 50 анкетам присваивается бейдж «Основатель платформы», а <strong>цена тарифа фиксируется навсегда</strong>.
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-800">
                        <div class="flex items-center justify-between font-mono-tech text-xs mb-2.5">
                            <span class="text-slate-400">Квота основателей:</span>
                            <span class="font-bold text-[#C6FF33]">Осталось 38 из 50 мест</span>
                        </div>
                        <div class="w-full h-2.5 bg-slate-800 rounded-full overflow-hidden p-0.5 border border-slate-700">
                            <div class="h-full bg-gradient-to-r from-[#7D39EB] to-[#C6FF33] rounded-full shadow-[0_0_12px_rgba(198,255,51,0.5)]" style="width: 76%;"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ─── BLOCK 09: FAQ ─── -->
    <section id="faq" class="py-20 md:py-28 border-b border-white/10 bg-[#010101]" x-data="{ openFaq: 1 }">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-16 text-left">
                <span class="font-mono-tech text-xs font-bold uppercase tracking-wider text-[#C6FF33] bg-[#C6FF33]/10 border border-[#C6FF33]/30 px-3 py-1 rounded-full inline-block mb-3">
                    09 // ПРЯМЫЕ ОТВЕТЫ
                </span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight mb-4">
                    Честные ответы на <span class="text-[#C6FF33]">главные вопросы</span>
                </h2>
                <p class="text-base sm:text-lg text-slate-300 font-normal leading-relaxed">
                    Разбираем ключевые сомнения перед стартом работы на платформе.
                </p>
            </div>

            <div class="space-y-4">
                
                <!-- Objection 1 -->
                <div class="wb-card bg-slate-900/60 overflow-hidden transition-all border-slate-800">
                    <button id="faq-btn-1"
                            @click="openFaq = (openFaq === 1 ? 0 : 1)" 
                            :aria-expanded="openFaq === 1 ? 'true' : 'false'"
                            aria-controls="faq-answer-1"
                            class="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base active:scale-[0.99] transition-transform">
                        <span>01. Зачем платить подписку, если есть бесплатные доски объявлений?</span>
                        <span class="font-mono-tech text-lg font-black ml-4 shrink-0 text-[#C6FF33]" x-text="openFaq === 1 ? '−' : '+'" aria-hidden="true">+</span>
                    </button>
                    <div id="faq-answer-1" 
                         role="region" 
                         aria-labelledby="faq-btn-1" 
                         x-show="openFaq === 1" 
                         x-cloak 
                         class="px-6 pb-6 text-sm text-slate-300 leading-relaxed border-t border-slate-800 pt-4">
                        На бесплатных досках вы платите не деньгами, а своим временем и жёсткой конкуренцией: сотни анкет, ноль инструментов, демпинг цен. Edusfera — это не доска, а специализированная рабочая среда: число мест в нише ограничено, а расписание, напоминания ученикам и готовые отчёты для налога НПД ведутся платформой автоматически.
                    </div>
                </div>

                <!-- Objection 2 -->
                <div class="wb-card bg-slate-900/60 overflow-hidden transition-all border-slate-800">
                    <button id="faq-btn-2"
                            @click="openFaq = (openFaq === 2 ? 0 : 2)" 
                            :aria-expanded="openFaq === 2 ? 'true' : 'false'"
                            aria-controls="faq-answer-2"
                            class="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base active:scale-[0.99] transition-transform">
                        <span>02. У меня уже есть ученики по сарафанному радио. Зачем мне Edusfera?</span>
                        <span class="font-mono-tech text-lg font-black ml-4 shrink-0 text-[#C6FF33]" x-text="openFaq === 2 ? '−' : '+'" aria-hidden="true">+</span>
                    </button>
                    <div id="faq-answer-2" 
                         role="region" 
                         aria-labelledby="faq-btn-2" 
                         x-show="openFaq === 2" 
                         x-cloak 
                         class="px-6 pb-6 text-sm text-slate-300 leading-relaxed border-t border-slate-800 pt-4">
                        Сарафанное радио — отличный, но нестабильный канал с сезонными просадками (осень/зима). Платформа даёт второй управляемый поток заявок, быстро закрывает внезапные «окна» в расписании и снимает рутину: напоминания ученикам, расчёт дохода и систематизацию учебных материалов.
                    </div>
                </div>

                <!-- Objection 3 -->
                <div class="wb-card bg-slate-900/60 overflow-hidden transition-all border-slate-800">
                    <button id="faq-btn-3"
                            @click="openFaq = (openFaq === 3 ? 0 : 3)" 
                            :aria-expanded="openFaq === 3 ? 'true' : 'false'"
                            aria-controls="faq-answer-3"
                            class="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base active:scale-[0.99] transition-transform">
                        <span>03. Не дорого ли платить каждый месяц?</span>
                        <span class="font-mono-tech text-lg font-black ml-4 shrink-0 text-[#C6FF33]" x-text="openFaq === 3 ? '−' : '+'" aria-hidden="true">+</span>
                    </button>
                    <div id="faq-answer-3" 
                         role="region" 
                         aria-labelledby="faq-btn-3" 
                         x-show="openFaq === 3" 
                         x-cloak 
                         class="px-6 pb-6 text-sm text-slate-300 leading-relaxed border-t border-slate-800 pt-4">
                        Месячная подписка Pro (40 BYN) равна стоимости ровно <strong>одного вашего урока</strong>. При этом один найденный через платформу ученик, занимающийся 2 раза в неделю, приносит вам 300–400 BYN в месяц, окупая подписку на год вперёд. Кроме того, первый месяц вы тестируете систему абсолютно бесплатно (0 BYN).
                    </div>
                </div>

                <!-- Objection 4 -->
                <div class="wb-card bg-slate-900/60 overflow-hidden transition-all border-slate-800">
                    <button id="faq-btn-4"
                            @click="openFaq = (openFaq === 4 ? 0 : 4)" 
                            :aria-expanded="openFaq === 4 ? 'true' : 'false'"
                            aria-controls="faq-answer-4"
                            class="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base active:scale-[0.99] transition-transform">
                        <span>04. Что делать, если за месяц не поступит ни одной заявки?</span>
                        <span class="font-mono-tech text-lg font-black ml-4 shrink-0 text-[#C6FF33]" x-text="openFaq === 4 ? '−' : '+'" aria-hidden="true">+</span>
                    </button>
                    <div id="faq-answer-4" 
                         role="region" 
                         aria-labelledby="faq-btn-4" 
                         x-show="openFaq === 4" 
                         x-cloak 
                         class="px-6 pb-6 text-sm text-slate-300 leading-relaxed border-t border-slate-800 pt-4">
                        Для тарифов Pro и Premium действует безусловная гарантия: если за оплаченный период вы не получили заявок, доступ автоматически продлевается бесплатно на следующий месяц, пока вы не получите целевую заявку.
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ─── FINAL CTA SLAB ─── -->
    <section class="py-20 md:py-28 px-4 sm:px-6 lg:px-8 bg-[#010101] relative overflow-hidden">
        
        <!-- Ambient radial glow -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(125,57,235,0.15),transparent_70%)] pointer-events-none"></div>

        <div class="max-w-5xl mx-auto wb-card-dark p-8 sm:p-14 md:p-16 text-center relative overflow-hidden shadow-2xl border-slate-800 bg-gradient-to-b from-[#0B0F19] to-black">
            
            <div class="relative z-10 max-w-2xl mx-auto">
                <span class="font-mono-tech text-xs font-bold uppercase tracking-wider text-[#C6FF33] bg-[#C6FF33]/10 border border-[#C6FF33]/30 px-3.5 py-1 rounded-full inline-block mb-4">
                    СТАРТ БЕЗ РИСКОВ
                </span>
                <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight mb-6">
                    Займите место в своей <span class="text-[#C6FF33]">нише</span>
                </h2>
                <p class="text-base sm:text-lg text-slate-300 font-normal mb-10 leading-relaxed">
                    Первый месяц бесплатно на тарифе Basic, Pro или Premium. Фиксация пожизненной цены основателя для первых 50 репетиторов Беларуси.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="/register?role=tutor&plan=pro" class="wb-btn wb-btn-primary text-base px-8 py-4 w-full sm:w-auto shadow-[0_0_30px_rgba(198,255,51,0.3)]">
                        Занять место в своей нише →
                    </a>
                    <a href="/login" class="wb-btn wb-btn-dark-outline text-base px-8 py-4 w-full sm:w-auto">
                        Войти в личный кабинет
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── SITE FOOTER ─── -->
    @include('partials.site-footer')

    </div>
</body>
</html>
