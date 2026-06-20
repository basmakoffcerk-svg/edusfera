<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Edusfera')</title>
    <meta name="description" content="@yield('meta_description', 'Edusfera — платформа для подготовки к ЦТ и ЦЭ с лучшими репетиторами.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: #f6f6f9;
            color: #0f1115;
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

        .glass-nav {
            background: rgba(246, 246, 249, 0.7);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        [x-cloak] { display: none !important; }

        .prose-edusfera h2 {
            font-weight: 800;
            font-size: 1.25rem;
            color: #0f1115;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }
        .prose-edusfera p {
            color: #4b5563;
            line-height: 1.75;
            margin-bottom: 1rem;
        }
        .prose-edusfera a {
            color: #7D39EB;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .prose-edusfera a:hover {
            color: #5b21b6;
        }
        .prose-edusfera ul {
            list-style: none;
            padding: 0;
            margin-bottom: 1rem;
        }
        .prose-edusfera ul li {
            position: relative;
            padding-left: 1.5rem;
            color: #4b5563;
            line-height: 1.75;
            margin-bottom: 0.5rem;
        }
        .prose-edusfera ul li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.65rem;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #7D39EB;
        }
        .prose-edusfera strong {
            color: #0f1115;
            font-weight: 700;
        }
    </style>
</head>
<body x-data="{ scrolled: false, mobileOpen: false }" @scroll.window="scrolled = (window.pageYOffset > 20)">

    <!-- NAVBAR -->
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
                {{-- Mobile burger --}}
                <button @click="mobileOpen = !mobileOpen" class="md:hidden w-10 h-10 rounded-xl flex items-center justify-center text-gray-600 hover:bg-black/5 transition-colors" aria-label="Меню">
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
                        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-3 bg-white/50 backdrop-blur-md border border-white/40 pl-2 pr-4 py-1.5 rounded-full hover:bg-white/80 transition-all shadow-sm">
                            <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center font-bold text-sm">
                                {{ mb_substr((string)$user->name, 0, 1) }}
                            </div>
                            <div class="text-left hidden sm:block">
                                <div class="text-sm font-bold leading-tight text-gray-900 max-w-28 truncate">{{ $user->name }}</div>
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
                            <div class="h-px bg-gray-100 my-2 mx-2"></div>
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
                    <a href="/admin/register" class="inline-flex items-center justify-center py-3 px-6 rounded-full font-bold text-xs uppercase tracking-wider text-white bg-violet-600 hover:bg-violet-700 transition-colors shadow-lg shadow-violet-500/30">Начать</a>
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

    <!-- PAGE CONTENT -->
    <main class="pt-36 pb-24">
        <div class="max-w-4xl mx-auto px-6">
            {{-- Page header --}}
            <div class="mb-10">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-violet-50 border border-violet-100 mb-6">
                    <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                    <span class="text-xs font-bold tracking-widest uppercase text-violet-600">Edusfera</span>
                </div>
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-rimma font-black tracking-tight text-gray-900 mb-3">@yield('heading')</h1>
                <p class="text-sm text-gray-400 font-medium">Актуальная редакция: @yield('updated_at')</p>
            </div>

            {{-- Content --}}
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 sm:p-10 md:p-12">
                <div class="prose-edusfera">
                    @yield('content')
                </div>
            </div>

            {{-- Help block --}}
            <div class="mt-8 bg-gray-50 rounded-2xl border border-gray-100 p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6">
                <div class="w-12 h-12 rounded-2xl bg-violet-100 text-violet-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900 mb-1">Нужна помощь?</h2>
                    <p class="text-sm text-gray-500">
                        Напишите на
                        <a class="font-bold text-violet-600 hover:text-violet-800 transition-colors" href="mailto:{{ config('mail.from.address', 'support@edusfera.by') }}">
                            {{ config('mail.from.address', 'support@edusfera.by') }}
                        </a>
                        или перейдите на страницу
                        <a class="font-bold text-violet-600 hover:text-violet-800 transition-colors" href="{{ route('contacts') }}">контактов</a>.
                    </p>
                </div>
            </div>
        </div>
    </main>

    @include('partials.site-footer')

</body>
</html>
