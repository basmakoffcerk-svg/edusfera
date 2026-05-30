<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edusfera — Подготовка к ЦТ/ЦЭ с топовыми репетиторами</title>
    <meta name="description" content="Индивидуальная подготовка к ЦТ и ЦЭ. Точечная диагностика знаний, математически выверенный план и лучшие преподаватели страны.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        :root {
            --bg-color: #f6f6f9;
            --text-main: #0f1115;
            --text-muted: #6b7280;
            --ed-violet: #7D39EB;
            --ed-lime: #C6FF33;
            --glass-bg: rgba(255, 255, 255, 0.6);
            --glass-border: rgba(255, 255, 255, 0.4);
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(125, 57, 235, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(198, 255, 51, 0.05) 0%, transparent 50%);
            background-attachment: fixed;
        }

        .font-rimma {
            font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif;
        }

        /* --- Typography Art --- */
        .text-outline-huge {
            font-size: clamp(6rem, 15vw, 15rem);
            font-weight: 900;
            line-height: 0.8;
            color: transparent;
            -webkit-text-stroke: 1px rgba(0, 0, 0, 0.03);
            text-transform: uppercase;
            position: absolute;
            z-index: -1;
            white-space: nowrap;
            user-select: none;
            pointer-events: none;
        }

        /* --- Glass & Components --- */
        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid var(--glass-border);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.02);
            border-radius: 2rem;
        }

        .glass-nav {
            background: rgba(246, 246, 249, 0.7);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .btn-core {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1.1rem 2.5rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-violet {
            background: var(--ed-violet);
            color: white;
            box-shadow: 0 10px 30px rgba(125, 57, 235, 0.3);
        }

        .btn-violet::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
            z-index: -1;
        }

        .btn-violet:hover::before {
            transform: translateX(100%);
        }
        
        .btn-violet:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(125, 57, 235, 0.4);
        }

        /* --- SVG Animations --- */
        .svg-draw {
            stroke-dasharray: 2000;
            stroke-dashoffset: 2000;
            animation: drawPath 4s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }

        @keyframes drawPath {
            to { stroke-dashoffset: 0; }
        }

        .anim-spin-slow {
            animation: spin 40s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .anim-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
            100% { transform: translateY(0); }
        }

        /* Interactive Tabs */
        .tab-btn {
            opacity: 0.4;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            border-left: 2px solid transparent;
            padding-left: 1.5rem;
        }
        .tab-btn.active {
            opacity: 1;
            border-left-color: var(--ed-violet);
            transform: translateX(10px);
        }
        .tab-btn:hover {
            opacity: 0.8;
        }
        
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ scrolled: false, mobileOpen: false }" @scroll.window="scrolled = (window.pageYOffset > 20)">

    <!-- NAVBAR WITH AUTH LOGIC -->
    <header class="fixed top-0 w-full z-50 transition-all duration-300" :class="scrolled || mobileOpen ? 'glass-nav py-4' : 'py-8'">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <a href="{{ route('home') }}" class="font-rimma font-bold text-2xl tracking-tighter flex items-center gap-2 group">
                EDUSFERA
                <svg class="w-6 h-6 text-lime-400 group-hover:rotate-180 transition-transform duration-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <path d="M12 2L22 12L12 22L2 12L12 2Z" />
                </svg>
            </a>

            <nav class="hidden md:flex items-center gap-12 font-bold text-xs uppercase tracking-widest text-gray-500">
                <a href="{{ route('tutors.index') }}" class="hover:text-black transition-colors">Каталог</a>
                <a href="{{ route('for-tutors') }}" class="hover:text-black transition-colors">Преподавателям</a>
            </nav>

            <div class="flex items-center gap-3">
                {{-- Mobile burger menu --}}
                <button @click="mobileOpen = !mobileOpen" class="md:hidden w-10 h-10 rounded-xl flex items-center justify-center text-gray-600 hover:bg-black/5 transition-colors" aria-label="Меню">
                    <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                @auth
                    @php
                        $user = auth()->user();
                        $unreadMessagesCount = app(\App\Services\ChatUnreadCounter::class)->countForUser($user);
                        $linked = app(\App\Services\MultiAccountService::class)->getLinkedAccounts();
                        $roleLabel = \App\Services\MultiAccountService::roleLabel($user->role);
                    @endphp
                    
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-3 bg-white/50 backdrop-blur-md border border-white/40 pl-2 pr-4 py-1.5 rounded-full hover:bg-white/80 transition-all shadow-sm">
                            <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center font-bold text-sm">
                                {{ mb_substr((string)$user->name, 0, 1) }}
                            </div>
                            <div class="text-left hidden sm:block">
                                <div class="text-sm font-bold leading-tight text-gray-900">{{ $user->name }}</div>
                                <div class="text-[10px] uppercase font-bold text-gray-400">{{ $roleLabel }}</div>
                            </div>
                            @if($unreadMessagesCount > 0)
                                <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full border-2 border-white flex items-center justify-center text-[8px] text-white font-bold">
                                    {{ $unreadMessagesCount > 9 ? '9+' : $unreadMessagesCount }}
                                </div>
                            @else
                                <svg class="w-4 h-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            @endif
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" x-transition.opacity.scale.95 style="display: none;" class="absolute right-0 mt-3 w-64 bg-white rounded-2xl border border-gray-200 shadow-xl p-2 z-50">
                            <a href="/admin" class="block px-4 py-2 text-sm font-bold text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">Личный кабинет</a>
                            
                            <a href="{{ $user->role === 'tutor' ? '/admin/transactions' : '/admin/lessons' }}" class="block px-4 py-2 text-sm font-bold text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                                {{ $user->role === 'tutor' ? 'Мои финансы' : 'Мои занятия' }}
                            </a>
                            
                            <a href="/admin/messages" class="flex items-center justify-between px-4 py-2 text-sm font-bold text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                                Сообщения
                                @if($unreadMessagesCount > 0)
                                    <span class="bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-xs">{{ $unreadMessagesCount }}</span>
                                @endif
                            </a>

                            @if(count($linked) > 0)
                                <div class="h-px bg-gray-100 my-2 mx-2"></div>
                                <div class="px-4 py-1 text-[10px] uppercase font-bold text-gray-400 tracking-wider">Связанные аккаунты</div>
                                @foreach($linked as $account)
                                    <a href="{{ route('account.switch', $account['id']) }}" class="flex items-center gap-3 px-4 py-2 hover:bg-violet-50 rounded-lg transition-colors group">
                                        <div class="w-6 h-6 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center text-xs font-bold group-hover:bg-violet-200">{{ mb_substr($account['name'], 0, 1) }}</div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900">{{ $account['name'] }}</div>
                                            <div class="text-[10px] font-bold text-gray-400 uppercase">{{ \App\Services\MultiAccountService::roleLabel($account['role']) }}</div>
                                        </div>
                                    </a>
                                @endforeach
                            @endif

                            <div class="h-px bg-gray-100 my-2 mx-2"></div>
                            
                            <a href="{{ route('account.add') }}" class="block px-4 py-2 text-sm font-bold text-lime-700 hover:bg-lime-50 rounded-lg transition-colors">
                                + Добавить аккаунт
                            </a>
                            
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    Выйти
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="/admin/login" class="hidden sm:block text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-black transition-colors">Войти</a>
                    <a href="/admin/register" class="btn-core btn-violet py-3 px-6 text-xs">Начать</a>
                @endauth
            </div>
        </div>

        {{-- Mobile navigation menu --}}
        <div x-show="mobileOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="md:hidden border-t border-gray-200/30">
            <nav class="max-w-7xl mx-auto px-6 py-4 flex flex-col gap-1">
                <a href="{{ route('tutors.index') }}" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-gray-700 hover:bg-black/5 transition-colors">
                    <svg class="w-5 h-5 text-violet-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Каталог репетиторов
                </a>
                <a href="{{ route('for-tutors') }}" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-gray-700 hover:bg-black/5 transition-colors">
                    <svg class="w-5 h-5 text-lime-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Преподавателям
                </a>
                @guest
                <div class="h-px bg-gray-200/50 my-2 mx-4"></div>
                <a href="/admin/login" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-gray-700 hover:bg-black/5 transition-colors">
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    Войти
                </a>
                @endguest
            </nav>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="relative min-h-screen pt-40 pb-24 flex items-center overflow-hidden">
        <!-- Subtle Background -->
        <div class="absolute inset-0 z-0 pointer-events-none flex items-center justify-center overflow-hidden">
             <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-lime-300 rounded-full mix-blend-multiply filter blur-[100px] opacity-20"></div>
             <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-violet-400 rounded-full mix-blend-multiply filter blur-[100px] opacity-20"></div>
        </div>

        <div class="max-w-7xl mx-auto px-6 w-full relative z-10 text-center">
            <h1 class="text-[clamp(3rem,7vw,6rem)] font-rimma font-black uppercase tracking-tighter mb-6 text-gray-900 leading-[0.9]">
                Сдайте ЦТ и ЦЭ на <br class="hidden sm:block"><span class="text-violet-600">90+ баллов</span>
            </h1>

            <p class="text-xl md:text-2xl text-gray-500 font-medium mb-12 max-w-3xl mx-auto leading-relaxed">
                Занимайтесь с проверенными репетиторами. Пройдите бесплатную ИИ-диагностику и получите индивидуальный план подготовки на основе тестов РИКЗ.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center max-w-2xl mx-auto">
                <a href="/tutors" class="btn-core btn-violet text-lg px-10 py-5 w-full sm:w-auto shadow-xl shadow-violet-500/30">
                    Найти репетитора
                </a>
                <a href="/admin/register" class="btn-core bg-white text-gray-900 border border-gray-200 hover:border-gray-900 hover:bg-gray-50 w-full sm:w-auto text-lg px-10 py-5">
                    Пройти диагностику
                </a>
            </div>
            
            <!-- TRUST BAR (Social Proof) -->
            <div class="mt-20 pt-10 border-t border-gray-200/60 grid grid-cols-2 md:grid-cols-4 gap-6 text-center max-w-4xl mx-auto">
                <div>
                    <div class="text-3xl font-rimma font-black text-gray-900">86+</div>
                    <div class="text-sm font-bold text-gray-500 uppercase tracking-widest mt-1">Средний балл</div>
                </div>
                <div>
                    <div class="text-3xl font-rimma font-black text-gray-900">100%</div>
                    <div class="text-sm font-bold text-gray-500 uppercase tracking-widest mt-1">Безопасность</div>
                </div>
                <div>
                    <div class="text-3xl font-rimma font-black text-gray-900">ТОП</div>
                    <div class="text-sm font-bold text-gray-500 uppercase tracking-widest mt-1">Преподаватели</div>
                </div>
                <div>
                    <div class="text-3xl font-rimma font-black text-gray-900">Всё</div>
                    <div class="text-sm font-bold text-gray-500 uppercase tracking-widest mt-1">В браузере</div>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW WE WORK (Simplified) -->
    <section class="py-24 bg-white overflow-hidden border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-[clamp(2rem,4vw,3.5rem)] font-rimma font-black uppercase tracking-tight mb-4">Стандарт Edusfera</h2>
                <p class="text-xl text-gray-500 font-medium max-w-2xl mx-auto">Мы убрали всё лишнее, чтобы вы сфокусировались только на результате.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gray-50 rounded-[2.5rem] p-10 border border-gray-100 hover:border-violet-200 transition-colors">
                    <div class="w-14 h-14 rounded-2xl bg-violet-100 text-violet-600 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Точечная диагностика</h3>
                    <p class="text-gray-500 leading-relaxed">ИИ определяет ваши пробелы до начала занятий, экономя время и деньги на повторении известного.</p>
                </div>

                <div class="bg-gray-50 rounded-[2.5rem] p-10 border border-gray-100 hover:border-lime-200 transition-colors">
                    <div class="w-14 h-14 rounded-2xl bg-lime-100 text-lime-700 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Безопасная оплата</h3>
                    <p class="text-gray-500 leading-relaxed">Деньги списываются только после того, как урок состоялся. Полная защита от мошенников.</p>
                </div>

                <div class="bg-gray-50 rounded-[2.5rem] p-10 border border-gray-100 hover:border-blue-200 transition-colors">
                    <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Всё в одном окне</h3>
                    <p class="text-gray-500 leading-relaxed">Интерактивная доска, видеосвязь и архив материалов — прямо в вашем браузере.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- RIKZ BLOCK (Premium Accent) -->
    <section class="py-24 md:py-32 bg-gray-900 text-white relative overflow-hidden">
        <!-- Abstract Neumorphic Glows -->
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-violet-600 rounded-full blur-[120px] opacity-40 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-[600px] h-[600px] bg-lime-500 rounded-full blur-[120px] opacity-20 pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/20 mb-8">
                        <span class="w-2 h-2 rounded-full bg-lime-400 animate-pulse"></span>
                        <span class="text-sm font-bold tracking-widest uppercase text-lime-400">Эксклюзивная технология</span>
                    </div>
                    <h2 class="text-[clamp(2.5rem,5vw,4rem)] font-rimma font-black uppercase tracking-tight mb-6 leading-none">
                        Алгоритмы на базе <span class="text-transparent bg-clip-text bg-gradient-to-r from-lime-300 to-violet-400">РИКЗ 2026</span>
                    </h2>
                    <p class="text-xl text-gray-300 font-medium mb-10 leading-relaxed">
                        Мы обучаем нашу нейросеть на тысячах реальных тестов. Система точно предсказывает ваш балл и выстраивает маршрут подготовки так, чтобы максимизировать результат на экзамене.
                    </p>
                    <a href="/admin/register" class="btn-core bg-lime-400 hover:bg-lime-300 text-gray-900 text-lg px-8 py-4 shadow-[0_0_30px_rgba(198,255,51,0.3)]">
                        Узнать свой прогноз
                    </a>
                </div>

                <!-- Gaussian Curve Visualization -->
                <div class="glass-panel bg-white/5 border-white/10 p-8 md:p-12 rounded-[3rem] relative">
                    <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-4">
                        <div class="text-sm font-bold text-gray-400 uppercase tracking-widest">Прогноз результата</div>
                        <div class="text-5xl font-rimma font-black text-white">96<span class="text-xl text-gray-500">/100</span></div>
                    </div>
                    
                    <div class="h-64 w-full relative">
                        <svg viewBox="0 0 400 200" class="w-full h-full overflow-visible">
                            <!-- Axis -->
                            <line x1="0" y1="160" x2="400" y2="160" stroke="rgba(255,255,255,0.2)" stroke-width="2"/>
                            
                            <!-- Area Gradient -->
                            <defs>
                                <linearGradient id="gaussGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="var(--ed-violet)" stop-opacity="0.8"/>
                                    <stop offset="100%" stop-color="var(--ed-violet)" stop-opacity="0.0"/>
                                </linearGradient>
                            </defs>

                            <!-- Gaussian Path Area -->
                            <path d="M 0 160 Q 100 160 160 100 T 200 20 T 240 100 T 300 160 T 400 160 L 400 160 L 0 160 Z" fill="url(#gaussGradient)"/>
                            <!-- Gaussian Line -->
                            <path d="M 0 160 Q 100 160 160 100 T 200 20 T 240 100 T 300 160 T 400 160" fill="none" stroke="var(--ed-lime)" stroke-width="4" class="svg-draw"/>
                            
                            <!-- Target Indicator -->
                            <line x1="260" y1="20" x2="260" y2="160" stroke="white" stroke-width="2" stroke-dasharray="4 4"/>
                            <circle cx="260" cy="120" r="6" fill="var(--ed-lime)" class="animate-pulse"/>
                            <rect x="235" y="-10" width="50" height="26" rx="6" fill="white"/>
                            <text x="260" y="8" fill="black" font-size="14" font-weight="900" text-anchor="middle">96</text>
                            
                            <!-- Current Indicator -->
                            <line x1="140" y1="80" x2="140" y2="160" stroke="rgba(255,255,255,0.5)" stroke-width="2" stroke-dasharray="4 4"/>
                            <circle cx="140" cy="120" r="5" fill="var(--text-muted)"/>
                            <rect x="120" y="60" width="40" height="24" rx="6" fill="rgba(255,255,255,0.1)" backdrop-filter="blur(10px)"/>
                            <text x="140" y="76" fill="white" font-size="12" font-weight="700" text-anchor="middle">45</text>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TOP TUTORS -->
    <section class="py-24 bg-gray-50/50 border-y border-gray-100">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
                <div>
                    <h2 class="text-[clamp(2rem,4vw,3.5rem)] font-rimma font-black uppercase tracking-tight mb-4">Архитекторы баллов</h2>
                    <p class="text-xl text-gray-500 font-medium max-w-xl">У нас преподают только те, кто доказал свою компетентность реальными результатами учеников.</p>
                </div>
                <a href="/tutors" class="btn-core bg-white hover:bg-gray-50 text-gray-900 border border-gray-200">Смотреть всех</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Tutor Card 1 -->
                <a href="/tutors" class="glass-panel bg-white border border-gray-200 p-6 rounded-[2rem] hover:shadow-2xl hover:border-violet-300 transition-all duration-300 group block relative">
                    <div class="relative w-full h-48 rounded-[1.5rem] bg-gray-200 mb-6 overflow-hidden">
                        <img src="https://i.pravatar.cc/300?img=47" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Tutor">
                        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                            <span class="text-lime-600">★</span> 5.0
                        </div>
                    </div>
                    <h4 class="font-bold text-xl text-gray-900 mb-1">Елена М.</h4>
                    <p class="text-sm font-bold text-violet-600 uppercase tracking-widest mb-4">Математика</p>
                    <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-4 mb-4">
                        <span class="text-gray-500">Ср. балл учеников</span>
                        <span class="font-black text-gray-900">88.5</span>
                    </div>
                    <div class="w-full text-center py-2 rounded-xl bg-violet-50 text-violet-700 font-bold text-sm group-hover:bg-violet-600 group-hover:text-white transition-colors">
                        Записаться
                    </div>
                </a>

                <!-- Tutor Card 2 -->
                <a href="/tutors" class="glass-panel bg-white border border-gray-200 p-6 rounded-[2rem] hover:shadow-2xl hover:border-violet-300 transition-all duration-300 group block relative">
                    <div class="relative w-full h-48 rounded-[1.5rem] bg-gray-200 mb-6 overflow-hidden">
                        <img src="https://i.pravatar.cc/300?img=12" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Tutor">
                        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                            <span class="text-lime-600">★</span> 4.9
                        </div>
                    </div>
                    <h4 class="font-bold text-xl text-gray-900 mb-1">Алексей В.</h4>
                    <p class="text-sm font-bold text-violet-600 uppercase tracking-widest mb-4">Физика</p>
                    <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-4 mb-4">
                        <span class="text-gray-500">Ср. балл учеников</span>
                        <span class="font-black text-gray-900">91.0</span>
                    </div>
                    <div class="w-full text-center py-2 rounded-xl bg-violet-50 text-violet-700 font-bold text-sm group-hover:bg-violet-600 group-hover:text-white transition-colors">
                        Записаться
                    </div>
                </a>

                <!-- Tutor Card 3 -->
                <a href="/tutors" class="glass-panel bg-white border border-gray-200 p-6 rounded-[2rem] hover:shadow-2xl hover:border-violet-300 transition-all duration-300 group block relative">
                    <div class="relative w-full h-48 rounded-[1.5rem] bg-gray-200 mb-6 overflow-hidden">
                        <img src="https://i.pravatar.cc/300?img=5" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Tutor">
                        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                            <span class="text-lime-600">★</span> 5.0
                        </div>
                    </div>
                    <h4 class="font-bold text-xl text-gray-900 mb-1">Ирина К.</h4>
                    <p class="text-sm font-bold text-violet-600 uppercase tracking-widest mb-4">Русский язык</p>
                    <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-4 mb-4">
                        <span class="text-gray-500">Ср. балл учеников</span>
                        <span class="font-black text-gray-900">94.2</span>
                    </div>
                    <div class="w-full text-center py-2 rounded-xl bg-violet-50 text-violet-700 font-bold text-sm group-hover:bg-violet-600 group-hover:text-white transition-colors">
                        Записаться
                    </div>
                </a>

                <!-- Tutor Card 4 -->
                <a href="/tutors" class="glass-panel bg-white border border-gray-200 p-6 rounded-[2rem] hover:shadow-2xl hover:border-violet-300 transition-all duration-300 group block relative">
                    <div class="relative w-full h-48 rounded-[1.5rem] bg-gray-200 mb-6 overflow-hidden">
                        <img src="https://i.pravatar.cc/300?img=33" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Tutor">
                        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                            <span class="text-lime-600">★</span> 4.8
                        </div>
                    </div>
                    <h4 class="font-bold text-xl text-gray-900 mb-1">Сергей Н.</h4>
                    <p class="text-sm font-bold text-violet-600 uppercase tracking-widest mb-4">Биология</p>
                    <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-4 mb-4">
                        <span class="text-gray-500">Ср. балл учеников</span>
                        <span class="font-black text-gray-900">86.0</span>
                    </div>
                    <div class="w-full text-center py-2 rounded-xl bg-violet-50 text-violet-700 font-bold text-sm group-hover:bg-violet-600 group-hover:text-white transition-colors">
                        Записаться
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- REVIEWS -->
    <section class="py-24 bg-white overflow-hidden" x-data="{
        next() {
            this.$refs.slider.scrollBy({ left: this.$refs.slider.offsetWidth, behavior: 'smooth' });
        },
        prev() {
            this.$refs.slider.scrollBy({ left: -this.$refs.slider.offsetWidth, behavior: 'smooth' });
        }
    }">
        <div class="max-w-7xl mx-auto px-6 mb-12 flex justify-between items-end">
            <h2 class="text-[clamp(2rem,4vw,3.5rem)] font-rimma font-black uppercase tracking-tight leading-none">Они уже <span class="text-violet-600">поступили</span></h2>
            
            <div class="hidden md:flex gap-4">
                <button @click="prev()" class="w-12 h-12 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button @click="next()" class="w-12 h-12 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>

        <div class="max-w-7xl mx-auto pl-6 pr-6 md:pr-0 relative">
            <div x-ref="slider" class="flex overflow-x-auto gap-6 snap-x snap-mandatory scrollbar-hide pb-8">
                
                <!-- Review 1 -->
                <div class="snap-start shrink-0 w-[85vw] md:w-[400px] bg-gray-50 rounded-[2.5rem] p-8 border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex gap-1 text-lime-500 mb-4 text-xl">★★★★★</div>
                        <p class="text-gray-700 leading-relaxed mb-6 font-medium">«Платформа невероятно удобная. Никаких сторонних ссылок. Мой репетитор по математике сразу выявил пробелы в тригонометрии с помощью теста. Итог: с 45 баллов на первом РТ до 92 на самом ЦТ.»</p>
                    </div>
                    <div class="flex items-center gap-4 mt-auto">
                        <div class="w-12 h-12 rounded-full bg-violet-200 text-violet-700 flex items-center justify-center font-bold">М</div>
                        <div>
                            <div class="font-bold text-gray-900">Максим Д.</div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold tracking-widest mt-0.5">Поступил в БГУИР</div>
                        </div>
                    </div>
                </div>

                <!-- Review 2 -->
                <div class="snap-start shrink-0 w-[85vw] md:w-[400px] bg-gray-50 rounded-[2.5rem] p-8 border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex gap-1 text-lime-500 mb-4 text-xl">★★★★★</div>
                        <p class="text-gray-700 leading-relaxed mb-6 font-medium">«Больше всего понравилась система безопасной оплаты. Родители были спокойны. Училась у Елены по физике — это просто восторг, материал объясняется на пальцах!»</p>
                    </div>
                    <div class="flex items-center gap-4 mt-auto">
                        <div class="w-12 h-12 rounded-full bg-lime-200 text-lime-700 flex items-center justify-center font-bold">А</div>
                        <div>
                            <div class="font-bold text-gray-900">Анна С.</div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold tracking-widest mt-0.5">Поступила в БНТУ</div>
                        </div>
                    </div>
                </div>

                <!-- Review 3 -->
                <div class="snap-start shrink-0 w-[85vw] md:w-[400px] bg-gray-50 rounded-[2.5rem] p-8 border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex gap-1 text-lime-500 mb-4 text-xl">★★★★★</div>
                        <p class="text-gray-700 leading-relaxed mb-6 font-medium">«График прогресса мотивирует лучше любых слов. Ты видишь, как линия ползет вверх каждую неделю. Сдал английский на 98 баллов, хотя в начале года еле дотягивал до 60.»</p>
                    </div>
                    <div class="flex items-center gap-4 mt-auto">
                        <div class="w-12 h-12 rounded-full bg-blue-200 text-blue-700 flex items-center justify-center font-bold">Е</div>
                        <div>
                            <div class="font-bold text-gray-900">Егор В.</div>
                            <div class="text-[10px] text-gray-500 uppercase font-bold tracking-widest mt-0.5">Поступил в БГУ</div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Fade hint for horizontal scroll --}}
            <div class="hidden md:block absolute right-0 top-0 bottom-8 w-24 bg-gradient-to-l from-white to-transparent pointer-events-none z-10"></div>
        </div>
    </section>

    <!-- FAQ & CTA -->
    <section class="py-24 bg-gray-50/50 border-t border-gray-100">
        <div class="max-w-4xl mx-auto px-6 mb-24">
            <h2 class="text-[clamp(2rem,4vw,3.5rem)] font-rimma font-black uppercase tracking-tight mb-10 text-center">Остались вопросы?</h2>
            
            <div class="space-y-4" x-data="{ activeAccordion: null }">
                <div class="border border-gray-200 rounded-[2rem] bg-white overflow-hidden">
                    <button @click="activeAccordion = activeAccordion === 1 ? null : 1" class="w-full flex items-center justify-between p-6 md:p-8 text-left focus:outline-none hover:bg-gray-50 transition-colors">
                        <span class="font-bold text-lg text-gray-900">Как работает безопасная сделка?</span>
                        <svg class="w-6 h-6 transform transition-transform duration-300 text-violet-600 flex-shrink-0" :class="activeAccordion === 1 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="activeAccordion === 1" x-collapse>
                        <div class="px-6 md:px-8 pb-8 text-gray-600 leading-relaxed">
                            Вы оплачиваете занятие картой на платформе, но деньги не уходят репетитору сразу. Они холдируются. Репетитор получит оплату только после того, как урок фактически состоится в нашем Встроенном классе.
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-[2rem] bg-white overflow-hidden">
                    <button @click="activeAccordion = activeAccordion === 2 ? null : 2" class="w-full flex items-center justify-between p-6 md:p-8 text-left focus:outline-none hover:bg-gray-50 transition-colors">
                        <span class="font-bold text-lg text-gray-900">Что делать, если репетитор не подошел?</span>
                        <svg class="w-6 h-6 transform transition-transform duration-300 text-violet-600 flex-shrink-0" :class="activeAccordion === 2 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="activeAccordion === 2" x-collapse>
                        <div class="px-6 md:px-8 pb-8 text-gray-600 leading-relaxed">
                            Вы можете отменить или заменить репетитора в любой момент через личный кабинет. Если вы оплатили урок, но отменили его заранее — деньги вернутся на баланс.
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-[2rem] bg-white overflow-hidden">
                    <button @click="activeAccordion = activeAccordion === 3 ? null : 3" class="w-full flex items-center justify-between p-6 md:p-8 text-left focus:outline-none hover:bg-gray-50 transition-colors">
                        <span class="font-bold text-lg text-gray-900">Нужно ли устанавливать Zoom или Skype?</span>
                        <svg class="w-6 h-6 transform transition-transform duration-300 text-violet-600 flex-shrink-0" :class="activeAccordion === 3 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="activeAccordion === 3" x-collapse>
                        <div class="px-6 md:px-8 pb-8 text-gray-600 leading-relaxed">
                            Нет. В Edusfera встроен собственный интерактивный класс. Занятия проходят прямо в браузере. Вы и преподаватель видите друг друга по видео, вместе рисуете на цифровой доске и решаете тесты в одном окне.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FINAL CTA -->
        <div class="max-w-6xl mx-auto px-6">
            <div class="bg-gray-900 rounded-[3rem] p-12 md:p-20 text-center relative overflow-hidden shadow-2xl">
                <div class="absolute inset-0 z-0">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-violet-600 rounded-full blur-[150px] opacity-20"></div>
                </div>
                <div class="relative z-10">
                    <h2 class="text-4xl md:text-6xl font-rimma font-black uppercase tracking-tighter mb-6 text-white leading-none">
                        Готовы к высоким <span class="text-lime-400">баллам?</span>
                    </h2>
                    <p class="text-xl text-gray-400 font-medium mb-12 max-w-2xl mx-auto">
                        Сделайте первый шаг прямо сейчас. Пройдите регистрацию и получите бесплатную диагностику знаний.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="/admin/register" class="btn-core btn-violet text-lg px-12 py-5 w-full sm:w-auto">Создать аккаунт</a>
                        <a href="/tutors" class="btn-core bg-white/10 hover:bg-white/20 text-white border border-white/20 text-lg px-12 py-5 w-full sm:w-auto transition-colors">Поиск репетитора</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.site-footer')

</body>
</html>