<!DOCTYPE html>
<html lang="ru" class="scroll-smooth dark">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edusfera для преподавателей — Элитарная платформа для лучших</title>
    <meta name="description" content="Преподавайте легально и элегантно. Ученики, расписание, встроенный класс и безопасная оплата в одном премиальном кабинете.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        :root {
            --ed-violet: #7D39EB;
            --ed-lime: #C6FF33;
            --dark-bg: #09090b; /* gray-950 */
            --dark-card: rgba(255, 255, 255, 0.03);
            --dark-border: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--dark-bg);
            color: #d1d5db; /* gray-300 */
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(125, 57, 235, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 85% 30%, rgba(198, 255, 51, 0.05) 0%, transparent 40%);
            background-attachment: fixed;
        }

        .font-rimma {
            font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif;
        }

        /* --- Dark Glass & Components --- */
        .glass-panel-dark {
            background: var(--dark-card);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid var(--dark-border);
            border-radius: 2rem;
            transition: all 0.4s ease;
        }
        
        .glass-panel-dark:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.5);
        }

        .glass-nav-dark {
            background: rgba(9, 9, 11, 0.7);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--dark-border);
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

        .btn-lime {
            background: var(--ed-lime);
            color: #000;
            box-shadow: 0 10px 30px rgba(198, 255, 51, 0.2);
        }
        
        .btn-lime:hover {
            background: #d8ff66;
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(198, 255, 51, 0.3);
        }

        .btn-ghost-dark {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-ghost-dark:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .anim-spin-slow { animation: spin 40s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        
        .anim-float { animation: float 8s ease-in-out infinite; }
        @keyframes float { 0% { transform: translateY(0); } 50% { transform: translateY(-15px); } 100% { transform: translateY(0); } }
        
        /* Subtle grid background */
        .bg-grid-dark {
            background-size: 50px 50px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ scrolled: false, mobileOpen: false }" @scroll.window="scrolled = (window.pageYOffset > 20)" class="text-gray-300">

    <!-- NAVBAR WITH AUTH LOGIC -->
    <header class="fixed top-0 w-full z-50 transition-all duration-300" :class="scrolled || mobileOpen ? 'glass-nav-dark py-4' : 'py-8'">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <a href="{{ route('home') }}" class="font-rimma font-bold text-2xl tracking-tighter flex items-center gap-2 group text-white">
                EDUSFERA
                <span class="text-[10px] uppercase font-sans font-bold text-black bg-lime-400 px-2 py-0.5 rounded-full ml-1 hidden xs:inline sm:inline">Репетиторам</span>
            </a>

            <nav class="hidden md:flex items-center gap-12 font-bold text-xs uppercase tracking-widest text-gray-400">
                <a href="{{ route('tutors.index') }}" class="hover:text-white transition-colors">Каталог</a>
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Ученикам</a>
            </nav>

            <div class="flex items-center gap-3">
                {{-- Mobile burger menu --}}
                <button @click="mobileOpen = !mobileOpen" class="md:hidden w-10 h-10 rounded-xl flex items-center justify-center text-gray-300 hover:bg-white/10 transition-colors" aria-label="Меню">
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
                        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-3 bg-white/10 backdrop-blur-md border border-white/10 pl-2 pr-4 py-1.5 rounded-full hover:bg-white/20 transition-all shadow-sm text-white">
                            <div class="w-8 h-8 rounded-full bg-lime-400 text-black flex items-center justify-center font-bold text-sm">
                                {{ mb_substr((string)$user->name, 0, 1) }}
                            </div>
                            <div class="text-left hidden sm:block">
                                <div class="text-sm font-bold leading-tight">{{ $user->name }}</div>
                                <div class="text-[10px] uppercase font-bold text-lime-400">{{ $roleLabel }}</div>
                            </div>
                            @if($unreadMessagesCount > 0)
                                <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center text-[8px] text-white font-bold">
                                    {{ $unreadMessagesCount > 9 ? '9+' : $unreadMessagesCount }}
                                </div>
                            @else
                                <svg class="w-4 h-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            @endif
                        </button>

                        <div x-show="open" x-transition.opacity.scale.95 style="display: none;" class="absolute right-0 mt-3 w-64 bg-[#1a1a1f] rounded-2xl border border-white/10 shadow-xl p-2 z-50">
                            <a href="/admin" class="block px-4 py-2 text-sm font-bold text-gray-200 hover:text-white hover:bg-white/10 rounded-lg transition-colors">Личный кабинет</a>
                            <a href="/admin/transactions" class="block px-4 py-2 text-sm font-bold text-gray-200 hover:text-white hover:bg-white/10 rounded-lg transition-colors">Мои финансы</a>
                            <a href="/admin/messages" class="flex items-center justify-between px-4 py-2 text-sm font-bold text-gray-200 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
                                Сообщения
                                @if($unreadMessagesCount > 0)
                                    <span class="bg-red-500/20 text-red-400 px-2 py-0.5 rounded-full text-xs">{{ $unreadMessagesCount }}</span>
                                @endif
                            </a>

                            @if(count($linked) > 0)
                                <div class="h-px bg-white/10 my-2 mx-2"></div>
                                <div class="px-4 py-1 text-[10px] uppercase font-bold text-gray-500 tracking-wider">Связанные аккаунты</div>
                                @foreach($linked as $account)
                                    <a href="{{ route('account.switch', $account['id']) }}" class="flex items-center gap-3 px-4 py-2 hover:bg-white/10 rounded-lg transition-colors group">
                                        <div class="w-6 h-6 rounded-full bg-white/10 text-white flex items-center justify-center text-xs font-bold">{{ mb_substr($account['name'], 0, 1) }}</div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-200">{{ $account['name'] }}</div>
                                            <div class="text-[10px] font-bold text-gray-500 uppercase">{{ \App\Services\MultiAccountService::roleLabel($account['role']) }}</div>
                                        </div>
                                    </a>
                                @endforeach
                            @endif

                            <div class="h-px bg-white/10 my-2 mx-2"></div>
                            
                            <a href="{{ route('account.add') }}" class="block px-4 py-2 text-sm font-bold text-lime-400 hover:bg-lime-400/10 rounded-lg transition-colors">
                                + Добавить аккаунт
                            </a>
                            
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm font-bold text-red-400 hover:bg-red-400/10 rounded-lg transition-colors">
                                    Выйти
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="/admin/login" class="hidden sm:block text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-white transition-colors">Войти</a>
                    <a href="/admin/register" class="btn-core btn-lime py-2.5 px-6 text-xs">Присоединиться</a>
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
             class="md:hidden border-t border-white/10">
            <nav class="max-w-7xl mx-auto px-6 py-4 flex flex-col gap-1">
                <a href="{{ route('tutors.index') }}" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                    <svg class="w-5 h-5 text-violet-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Каталог репетиторов
                </a>
                <a href="{{ route('home') }}" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                    <svg class="w-5 h-5 text-lime-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Ученикам
                </a>
                @guest
                <div class="h-px bg-white/10 my-2 mx-4"></div>
                <a href="/admin/login" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                    <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    Войти
                </a>
                @endguest
            </nav>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="relative min-h-screen pt-40 pb-24 flex items-center overflow-hidden bg-grid-dark">
        <!-- Abstract Shapes -->
        <div class="absolute inset-0 z-0 pointer-events-none flex items-center justify-center overflow-hidden">
            <div class="absolute top-[20%] right-[10%] w-[500px] h-[500px] bg-violet-600 rounded-full mix-blend-screen filter blur-[150px] opacity-30"></div>
            <div class="absolute bottom-[10%] left-[10%] w-[600px] h-[600px] bg-lime-500 rounded-full mix-blend-screen filter blur-[150px] opacity-10"></div>
            
            <svg class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] opacity-[0.05] anim-spin-slow text-white" viewBox="0 0 800 800" fill="none">
                <circle cx="400" cy="400" r="350" stroke="currentColor" stroke-width="1" stroke-dasharray="10 30"/>
                <circle cx="400" cy="400" r="250" stroke="currentColor" stroke-width="1" stroke-dasharray="5 15"/>
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-6 w-full relative z-10">
            <div class="grid lg:grid-cols-12 gap-16 items-center">
                <!-- Left Content -->
                <div class="lg:col-span-7">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 shadow-sm mb-8 backdrop-blur-md">
                        <span class="w-2 h-2 rounded-full bg-lime-400 animate-pulse shadow-[0_0_10px_rgba(198,255,51,0.8)]"></span>
                        <span class="text-xs font-bold tracking-widest uppercase text-gray-300">Платформа нового уровня</span>
                    </div>

                    <h1 class="text-[clamp(2.2rem,6vw,5.5rem)] font-rimma font-black uppercase tracking-tighter mb-8 text-white leading-[0.9]">
                        Преподавайте <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-lime-300 to-lime-500">с достоинством</span>
                    </h1>

                    <p class="text-xl md:text-2xl text-gray-400 font-medium mb-12 max-w-xl leading-relaxed">
                        Мы автоматизировали рутину. Поиск учеников, онлайн-оплаты, расписание и интерактивный класс — в единой элитарной экосистеме. Без оформления ИП.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
                        <a href="/admin/register" class="btn-core btn-lime w-full sm:w-auto text-base sm:text-lg px-6 sm:px-10 py-4 sm:py-5">
                            Создать профиль
                        </a>
                        <a href="#features" class="btn-core btn-ghost-dark w-full sm:w-auto text-base sm:text-lg px-6 sm:px-10 py-4 sm:py-5">
                            Изучить систему
                        </a>
                    </div>
                </div>

                <!-- Right Content: Abstract UI Representation -->
                <div class="hidden lg:flex lg:col-span-5 justify-end anim-float">
                    <div class="glass-panel-dark p-8 w-full relative overflow-hidden">
                        
                        <!-- UI Elements -->
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-4">
                            <h3 class="font-bold text-white tracking-wide">Расписание</h3>
                            <span class="text-xs font-bold text-black bg-lime-400 px-3 py-1 rounded-full shadow-[0_0_15px_rgba(198,255,51,0.4)]">Онлайн</span>
                        </div>
                        
                        <div class="space-y-4 mb-8">
                            <!-- Lesson 1 -->
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-5 flex items-center justify-between hover:bg-white/10 transition-colors cursor-default">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-violet-500/20 border border-violet-500/30 flex items-center justify-center font-bold text-violet-300 text-lg">Е</div>
                                    <div>
                                        <div class="font-bold text-white text-base">Егор В.</div>
                                        <div class="text-xs text-gray-400 font-medium uppercase tracking-wider mt-1">15:00 • Математика</div>
                                    </div>
                                </div>
                                <div class="text-xs font-bold text-lime-400 border border-lime-400/30 bg-lime-400/10 px-3 py-1.5 rounded-lg">Оплачено</div>
                            </div>
                            
                            <!-- Lesson 2 Active -->
                            <div class="bg-gradient-to-r from-violet-600 to-violet-800 rounded-2xl p-5 flex items-center justify-between text-white shadow-[0_10px_30px_rgba(125,57,235,0.3)] transform scale-[1.03] border border-violet-400/50">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center font-bold text-lg">А</div>
                                    <div>
                                        <div class="font-bold text-white text-base">Анна С.</div>
                                        <div class="text-xs text-white/70 font-medium uppercase tracking-wider mt-1">Идет сейчас • Физика</div>
                                    </div>
                                </div>
                                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-violet-700 shadow-lg">
                                    <svg class="w-5 h-5 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Pseudo Balance -->
                        <div class="pt-2">
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Доход (BYN)</div>
                            <div class="text-4xl font-rimma font-black text-white">2 850<span class="text-xl text-gray-500">.00</span></div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- BENTO GRID (Why Edusfera) -->
    <section id="features" class="py-24 relative z-10 border-t border-white/5 bg-grid-dark">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-20">
                <h2 class="text-[clamp(2rem,4vw,3.5rem)] font-rimma font-black uppercase tracking-tight mb-4 text-white">Архитектура <span class="text-violet-500">процесса</span></h2>
                <p class="text-xl text-gray-400 font-medium max-w-2xl mx-auto">Мы убрали всё лишнее. Никаких мессенджеров, переводов на карту и Excel-таблиц. Только чистый фокус на обучении.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- Card 1 -->
                <div class="glass-panel-dark p-10 flex flex-col h-full group">
                    <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 text-lime-400 flex items-center justify-center mb-8 group-hover:bg-lime-400 group-hover:text-black transition-colors duration-300">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Белый доход</h3>
                    <p class="text-gray-400 leading-relaxed mb-8 font-medium">Родители оплачивают уроки официально картой через платформу. Мы сами оформляем чеки и платим налоги. Вам не нужно открывать ИП.</p>
                    <div class="mt-auto">
                        <span class="inline-block bg-white/5 border border-white/10 rounded-lg px-4 py-1.5 text-xs font-bold text-gray-300 uppercase tracking-wider">Легально</span>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="glass-panel-dark p-10 flex flex-col h-full group">
                    <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 text-violet-400 flex items-center justify-center mb-8 group-hover:bg-violet-500 group-hover:text-white transition-colors duration-300">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Авто-расписание</h3>
                    <p class="text-gray-400 leading-relaxed mb-8 font-medium">Настройте свои свободные часы один раз. Ученики сами бронируют слоты. Система автоматически пришлет напоминания всем участникам.</p>
                    <div class="mt-auto">
                        <span class="inline-block bg-white/5 border border-white/10 rounded-lg px-4 py-1.5 text-xs font-bold text-gray-300 uppercase tracking-wider">Умное</span>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="glass-panel-dark p-10 flex flex-col h-full group">
                    <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 text-blue-400 flex items-center justify-center mb-8 group-hover:bg-blue-500 group-hover:text-white transition-colors duration-300">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Свой видеокласс</h3>
                    <p class="text-gray-400 leading-relaxed mb-8 font-medium">Отправьте Zoom в прошлое. Проводите интерактивные уроки прямо в браузере. Видеосвязь, общая доска и тетради уже встроены.</p>
                    <div class="mt-auto">
                        <span class="inline-block bg-white/5 border border-white/10 rounded-lg px-4 py-1.5 text-xs font-bold text-gray-300 uppercase tracking-wider">Всё в одном</span>
                    </div>
                </div>

                <!-- Card 4 (Wide Premium) -->
                <div class="lg:col-span-3 glass-panel-dark p-12 md:p-20 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-12 border-lime-500/20">
                    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-lime-500/10 rounded-full blur-[120px] pointer-events-none"></div>
                    
                    <div class="relative z-10 max-w-3xl">
                        <div class="inline-flex items-center gap-2 mb-6">
                            <span class="w-2 h-2 rounded-full bg-lime-400"></span>
                            <span class="text-xs font-bold tracking-widest uppercase text-lime-400">Прозрачная экономика</span>
                        </div>
                        <h3 class="text-4xl font-rimma font-black mb-6 text-white leading-tight">Никаких <br>абонентских плат</h3>
                        <p class="text-gray-300 text-xl leading-relaxed">Вы не платите за размещение анкеты или отклики. Мы берем честную комиссию только за реально проведенные и оплаченные уроки. Нет урока — нет расходов.</p>
                    </div>

                    <div class="relative z-10 flex-shrink-0">
                        <div class="w-40 h-40 md:w-48 md:h-48 rounded-full border-[2px] border-lime-400/30 bg-lime-400/5 flex flex-col items-center justify-center shadow-[0_0_50px_rgba(198,255,51,0.1)] backdrop-blur-sm">
                            <span class="text-5xl md:text-6xl font-rimma font-black text-lime-400 mb-1">0<span class="text-2xl text-lime-400/50 ml-1">BYN</span></span>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Скрытых платежей</span>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- STEPS (How to start) -->
    <section class="py-24 md:py-32 relative z-10">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-[clamp(2rem,4vw,3.5rem)] font-rimma font-black uppercase tracking-tight mb-20 text-center text-white">Путь к <span class="text-lime-400">результату</span></h2>
            
            <div class="grid md:grid-cols-3 gap-8 relative">
                <!-- Connecting Line -->
                <div class="hidden md:block absolute top-1/2 left-10 right-10 h-0.5 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-y-1/2 z-0"></div>

                <div class="glass-panel-dark p-10 text-center relative z-10 group hover:-translate-y-2">
                    <div class="w-20 h-20 mx-auto bg-gray-900 border-2 border-white/10 rounded-2xl flex items-center justify-center mb-8 shadow-xl group-hover:border-lime-400/50 transition-colors">
                        <span class="text-3xl font-rimma font-black text-white">1</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Профиль</h3>
                    <p class="text-gray-400 leading-relaxed font-medium">Создайте эстетичную анкету: укажите образование, опыт, ставку и предметы. Это ваш личный мини-сайт.</p>
                </div>

                <div class="glass-panel-dark p-10 text-center relative z-10 group hover:-translate-y-2">
                    <div class="w-20 h-20 mx-auto bg-gray-900 border-2 border-white/10 rounded-2xl flex items-center justify-center mb-8 shadow-xl group-hover:border-violet-500/50 transition-colors">
                        <span class="text-3xl font-rimma font-black text-white">2</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Слоты</h3>
                    <p class="text-gray-400 leading-relaxed font-medium">Настройте календарь доступности. Анкета моментально появится в интеллектуальном поиске платформы.</p>
                </div>

                <div class="glass-panel-dark p-10 text-center relative z-10 group hover:-translate-y-2">
                    <div class="w-20 h-20 mx-auto bg-gray-900 border-2 border-lime-400/30 rounded-2xl flex items-center justify-center mb-8 shadow-[0_0_30px_rgba(198,255,51,0.15)] group-hover:border-lime-400 transition-colors">
                        <span class="text-3xl font-rimma font-black text-lime-400">3</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Доход</h3>
                    <p class="text-gray-400 leading-relaxed font-medium">Подтверждайте заявки, проводите уроки в премиальном видеоклассе и выводите честно заработанные средства.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FINAL CTA -->
    <section class="py-24 md:py-32 px-6 mb-12">
        <div class="max-w-6xl mx-auto bg-gray-900 rounded-3xl md:rounded-[4rem] p-8 sm:p-12 md:p-16 lg:p-32 text-center relative overflow-hidden border border-white/10 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.8)]">
            <!-- Background Decoration -->
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff05_1px,transparent_1px),linear-gradient(to_bottom,#ffffff05_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-[radial-gradient(circle,rgba(125,57,235,0.15)_0%,transparent_60%)] rounded-full pointer-events-none"></div>

            <div class="relative z-10">
                <h2 class="text-3xl sm:text-5xl md:text-7xl font-rimma font-black uppercase tracking-tighter mb-8 text-white leading-none">
                    Войдите в <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-lime-400 to-lime-200">Элиту</span>
                </h2>
                <p class="text-xl text-gray-400 font-medium mb-12 max-w-2xl mx-auto leading-relaxed">
                    Регистрация занимает 15 минут. Никаких взносов. Начните монетизировать свои знания легально и в лучшем интерфейсе на рынке.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                    <a href="/admin/register" class="btn-core btn-lime text-base sm:text-lg px-8 sm:px-14 py-4 sm:py-5 w-full sm:w-auto">Стать преподавателем</a>
                    <a href="/admin/login" class="text-sm font-bold uppercase tracking-widest text-gray-400 hover:text-white transition-colors w-full sm:w-auto text-center py-4">Уже в системе</a>
                </div>
            </div>
        </div>
    </section>

    @include('partials.site-footer', ['variant' => 'dark'])

</body>
</html>