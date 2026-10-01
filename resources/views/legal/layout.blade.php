<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Документы') — Edusfera</title>
    <meta name="description" content="@yield('meta_description', 'Edusfera — образовательная онлайн-платформа для подготовки к ЦТ и ЦЭ с проверенными репетиторами в Беларуси.')">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="{{ request()->url() }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_BY">
    <meta property="og:site_name" content="Edusfera">
    <meta property="og:title" content="@yield('title', 'Документы') — Edusfera">
    <meta property="og:description" content="@yield('meta_description', 'Edusfera — образовательная онлайн-платформа для подготовки к ЦТ и ЦЭ с проверенными репетиторами в Беларуси.')">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:image" content="{{ asset('og-image.png') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('title', 'Документы') — Edusfera">
    <meta name="twitter:description" content="@yield('meta_description', 'Edusfera — образовательная онлайн-платформа в Беларуси.')">
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
              "name": "@yield('title', 'Правовая информация')",
              "item": "{{ request()->url() }}"
            }
          ]
        }
      ]
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'SF Pro Text', system-ui, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .font-rimma {
            font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif;
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }

        [x-cloak] { display: none !important; }

        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        .prose-edusfera h2 {
            font-weight: 700;
            font-size: 1.2rem;
            color: #0f172a;
            margin-top: 2.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f1f5f9;
            letter-spacing: -0.01em;
        }
        .prose-edusfera h3 {
            font-weight: 600;
            font-size: 1.05rem;
            color: #1e293b;
            margin-top: 1.75rem;
            margin-bottom: 0.75rem;
        }
        .prose-edusfera p {
            color: #334155;
            line-height: 1.75;
            font-size: 0.9375rem;
            margin-bottom: 1.125rem;
        }
        .prose-edusfera a {
            color: #0f172a;
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 3px;
            transition: color 0.15s ease;
        }
        .prose-edusfera a:hover {
            color: #7D39EB;
        }
        .prose-edusfera ul {
            list-style: none;
            padding: 0;
            margin-bottom: 1.25rem;
        }
        .prose-edusfera ul li {
            position: relative;
            padding-left: 1.5rem;
            color: #334155;
            line-height: 1.7;
            font-size: 0.9375rem;
            margin-bottom: 0.5rem;
        }
        .prose-edusfera ul li::before {
            content: '';
            position: absolute;
            left: 0.25rem;
            top: 0.65rem;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #94a3b8;
        }
        .prose-edusfera strong {
            color: #0f172a;
            font-weight: 600;
        }
    </style>
</head>
<body x-data="{ scrolled: false, mobileOpen: false }" @scroll.window="scrolled = (window.pageYOffset > 20)">

    <!-- NAVBAR -->
    <header class="fixed top-0 w-full z-50 transition-all duration-300" :class="scrolled || mobileOpen ? 'glass-nav py-4' : 'py-8'">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-slate-900 group">
                <svg width="28" height="28" viewBox="0 0 64 64" class="w-7 h-7 rounded-lg shadow-sm group-hover:scale-105 transition-transform flex-shrink-0">
                    <rect width="64" height="64" rx="14" fill="#7D39EB" />
                    <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                    <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                </svg>
                <span class="text-xl font-bold tracking-tight text-slate-900 uppercase font-rimma">edusfera</span>
            </a>

            <nav class="hidden md:flex items-center rounded-full bg-slate-900/[0.04] p-1 border border-slate-900/[0.06] text-xs font-semibold text-slate-600">
                <a href="{{ route('tutors.index') }}" class="px-4 py-1.5 rounded-full hover:text-slate-900 hover:bg-white transition-all">Каталог</a>
                <a href="{{ route('for-tutors') }}" class="px-4 py-1.5 rounded-full hover:text-slate-900 hover:bg-white transition-all">Преподавателям</a>
            </nav>

            <div class="flex items-center gap-3">
                {{-- Mobile burger --}}
                <button @click="mobileOpen = !mobileOpen" class="md:hidden w-10 h-10 rounded-xl flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors" aria-label="Меню">
                    <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                @auth
                    @php
                        $user = auth()->user();
                        $unreadMessagesCount = app(\App\Services\ChatUnreadCounter::class)->countForUser($user);
                        $roleLabel = \App\Services\MultiAccountService::roleLabel($user->role);
                    @endphp

                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2.5 bg-white border border-slate-200/90 pl-1.5 pr-3.5 py-1 rounded-full hover:border-slate-300 transition-all shadow-xs">
                            <div class="w-7 h-7 rounded-full bg-[#C6FF33] text-black flex items-center justify-center font-bold text-xs">
                                {{ mb_substr((string)$user->name, 0, 1) }}
                            </div>
                            <div class="text-left hidden sm:block">
                                <div class="text-xs font-semibold leading-tight text-slate-900 max-w-28 truncate">{{ $user->name }}</div>
                            </div>
                            @if($unreadMessagesCount > 0)
                                <div class="w-4 h-4 bg-red-500 rounded-full flex items-center justify-center text-[9px] text-white font-bold">
                                    {{ $unreadMessagesCount > 9 ? '9+' : $unreadMessagesCount }}
                                </div>
                            @else
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            @endif
                        </button>

                        <div x-show="open" x-transition.opacity.scale.95 style="display: none;" class="absolute right-0 mt-3 w-64 bg-white rounded-2xl border border-slate-200 shadow-xl p-2 z-50">
                            <a href="/admin" class="block px-3.5 py-2 text-xs font-semibold text-slate-900 hover:bg-slate-100 rounded-xl transition-colors">Личный кабинет</a>
                            <a href="{{ $user->role === 'tutor' ? '/admin/transactions' : '/admin/lessons' }}" class="block px-3.5 py-2 text-xs font-semibold text-slate-900 hover:bg-slate-100 rounded-xl transition-colors">
                                {{ $user->role === 'tutor' ? 'Мои финансы' : 'Мои занятия' }}
                            </a>
                            <a href="/admin/messages" class="flex items-center justify-between px-3.5 py-2 text-xs font-semibold text-slate-900 hover:bg-slate-100 rounded-xl transition-colors">
                                Сообщения
                                @if($unreadMessagesCount > 0)
                                    <span class="bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-xs font-bold">{{ $unreadMessagesCount }}</span>
                                @endif
                            </a>
                            <div class="h-px bg-slate-100 my-1.5 mx-2"></div>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="w-full text-left px-3.5 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 rounded-xl transition-colors">
                                    Выйти
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="/admin/login" class="hidden sm:block text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors px-3 py-1.5">Войти</a>
                    <a href="/admin/register" class="inline-flex items-center justify-center py-2 px-4 rounded-full font-semibold text-xs text-white bg-slate-900 hover:bg-slate-800 transition-all shadow-xs">Начать</a>
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
             class="md:hidden border-t border-slate-200/60 bg-white/95 backdrop-blur-xl">
            <nav class="max-w-7xl mx-auto px-6 py-4 flex flex-col gap-1">
                <a href="{{ route('tutors.index') }}" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                    Каталог репетиторов
                </a>
                <a href="{{ route('for-tutors') }}" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                    Преподавателям
                </a>
                <div class="h-px bg-slate-200/60 my-2 mx-2"></div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-4 py-1">Документы и компания</div>
                <a href="{{ route('about') }}" @click="mobileOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-violet-700 bg-violet-50/60 hover:bg-violet-100">О компании</a>
                <a href="{{ route('legal.offer') }}" @click="mobileOpen = false" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100">Публичная оферта</a>
                <a href="{{ route('legal.privacy') }}" @click="mobileOpen = false" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100">Политика конфиденциальности</a>
                <a href="{{ route('legal.payment-security') }}" @click="mobileOpen = false" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100">Правила оплаты и безопасность</a>
                <a href="{{ route('legal.refund') }}" @click="mobileOpen = false" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100">Правила возврата</a>
                <a href="{{ route('contacts') }}" @click="mobileOpen = false" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100">Контакты</a>
                @guest
                <div class="h-px bg-slate-200/60 my-2 mx-2"></div>
                <a href="/admin/login" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                    Войти
                </a>
                @endguest
            </nav>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <main class="pt-28 sm:pt-32 pb-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            
            {{-- Document Tabs Switcher (Apple Legal Style) --}}
            <div class="mb-8 overflow-x-auto no-scrollbar py-1">
                <div class="inline-flex items-center p-1 rounded-2xl bg-slate-200/60 border border-slate-200 backdrop-blur-md gap-1 min-w-max">
                    <a href="{{ route('about') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('about') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        О компании
                    </a>
                    <a href="{{ route('legal.offer') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('legal.offer') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Оферта
                    </a>
                    <a href="{{ route('legal.privacy') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('legal.privacy') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Конфиденциальность
                    </a>
                    <a href="{{ route('legal.payment-security') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('legal.payment-security') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Оплата и безопасность
                    </a>
                    <a href="{{ route('legal.refund') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('legal.refund') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Правила возврата
                    </a>
                    <a href="{{ route('contacts') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('contacts') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Контакты
                    </a>
                </div>
            </div>

            {{-- Document Header --}}
            <div class="mb-8">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 border border-slate-200/80 text-[11px] font-semibold text-slate-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Официальный документ
                    </div>
                    <div class="text-xs text-slate-500 font-medium">
                        Актуальная редакция: <span class="text-slate-700 font-semibold">@yield('updated_at')</span>
                    </div>
                </div>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight text-slate-900 font-sans">@yield('heading')</h1>
                @hasSection('subtitle')
                    <p class="mt-2 text-sm sm:text-base text-slate-500 leading-relaxed max-w-3xl">@yield('subtitle')</p>
                @endif
            </div>

            {{-- Content Paper Card --}}
            <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-[0_1px_3px_rgba(0,0,0,0.04)] p-6 sm:p-10 md:p-12">
                <div class="prose-edusfera">
                    @yield('content')
                </div>
            </div>

            {{-- Help block --}}
            <div class="mt-8 bg-white/70 backdrop-blur-md rounded-2xl border border-slate-200/80 p-6 sm:p-7 flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 shadow-xs">
                <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center flex-shrink-0 border border-slate-200/60">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-sm font-bold text-slate-900 mb-0.5">Нужна консультация по юридическим вопросам?</h2>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Напишите нам на
                        <a class="font-semibold text-slate-800 hover:text-black underline underline-offset-2 transition-colors" href="mailto:{{ config('mail.from.address', 'edusferaby@gmail.com') }}">
                            {{ config('mail.from.address', 'edusferaby@gmail.com') }}
                        </a>
                        или обратитесь в службу поддержки через страницу
                        <a class="font-semibold text-slate-800 hover:text-black underline underline-offset-2 transition-colors" href="{{ route('contacts') }}">контактов</a>.
                    </p>
                </div>
            </div>
        </div>
    </main>

    @include('partials.site-footer')
    @include('partials.pwa-prompt')
</body>
</html>
