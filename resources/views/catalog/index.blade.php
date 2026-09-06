<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Каталог репетиторов — Edusfera</title>
    <meta name="description" content="Подберите проверенного репетитора в Беларуси. Фильтр по предмету, цене и рейтингу. Безопасная оплата через платформу.">

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

        /* Reveal animation */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Custom select styling */
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
    </style>
</head>
<body x-data="{ scrolled: false, mobileOpen: false, filtersOpen: false }" @scroll.window="scrolled = (window.pageYOffset > 20)">

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
                <a href="{{ route('tutors.index') }}" class="text-black transition-colors">Каталог</a>
                <a href="{{ route('for-tutors') }}" class="hover:text-black transition-colors">Преподавателям</a>
                <a href="{{ route('news.index') }}" class="hover:text-black transition-colors">Новости</a>
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
                                    <form method="POST" action="{{ route('account.switch', $account['id']) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-violet-50 rounded-lg transition-colors group cursor-pointer border-0 bg-transparent">
                                            <div class="w-6 h-6 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center text-xs font-bold group-hover:bg-violet-200">{{ mb_substr($account['name'], 0, 1) }}</div>
                                            <div>
                                                <div class="text-sm font-bold text-gray-900">{{ $account['name'] }}</div>
                                                <div class="text-[10px] font-bold text-gray-400 uppercase">{{ \App\Services\MultiAccountService::roleLabel($account['role']) }}</div>
                                            </div>
                                        </button>
                                    </form>
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
                    <a href="/admin/register" class="inline-flex items-center justify-center py-3 px-6 rounded-full font-bold text-xs uppercase tracking-wider text-white bg-violet-600 hover:bg-violet-700 transition-colors shadow-lg shadow-violet-500/30">Начать</a>
                @endauth
            </div>
        </div>

        {{-- Mobile navigation --}}
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
                <a href="{{ route('news.index') }}" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-gray-700 hover:bg-black/5 transition-colors">
                    <svg class="w-5 h-5 text-violet-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 00-2-2v3m2-3V9a2 2 0 00-2-2v3m2-3V5a2 2 0 00-2-2v3m2-3v12a2 2 0 00-2-2H9"></path></svg>
                    Новости
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

    <!-- HERO + SEARCH -->
    <section class="pt-36 pb-6">
        <div class="max-w-7xl mx-auto px-6">
            {{-- Diagnostic context banner --}}
            @if($diagnosticContext['subject'] ?? null)
                <div class="mb-6 bg-gradient-to-r from-violet-50 to-amber-50 border border-violet-200 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">Подбор по результатам диагностики</p>
                            <p class="text-xs text-gray-500">
                                {{ $diagnosticContext['subject'] }}
                                @if($diagnosticContext['exam_type'] ?? null) · {{ $diagnosticContext['exam_type'] }}@endif
                                @if($diagnosticContext['current_score'] ?? null) · ваш уровень: {{ $diagnosticContext['current_score'] }} баллов@endif
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('diagnostic.show') }}" class="text-xs font-bold text-violet-600 hover:text-violet-800 whitespace-nowrap">Пройти заново →</a>
                </div>
            @endif

            <div class="mb-8">
                <h1 class="text-[clamp(2rem,4vw,3.5rem)] font-rimma font-black uppercase tracking-tight text-gray-900 mb-3 leading-tight">
                    Найдите <span class="text-violet-600">репетитора</span>
                </h1>
                <p class="text-lg text-gray-500 font-medium max-w-xl">Фильтр по предмету, цене и рейтингу. Все анкеты проверены, оплата через платформу.</p>
            </div>

            <!-- Search bar -->
            <form method="GET" action="{{ route('tutors.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Поиск по имени преподавателя"
                           class="w-full h-14 pl-12 pr-4 rounded-2xl border border-gray-200 bg-white text-base font-medium text-gray-900 placeholder-gray-400 shadow-sm outline-none transition-all focus:border-violet-400 focus:ring-4 focus:ring-violet-100"
                           aria-label="Поиск">
                </div>
                @if(request()->filled('subject'))<input type="hidden" name="subject" value="{{ request('subject') }}">@endif
                @if(request()->filled('price_max'))<input type="hidden" name="price_max" value="{{ request('price_max') }}">@endif
                @if(request()->boolean('exam_track'))<input type="hidden" name="exam_track" value="1">@endif
                @if(request()->boolean('diagnostic_supported'))<input type="hidden" name="diagnostic_supported" value="1">@endif
                @if(request()->boolean('official'))<input type="hidden" name="official" value="1">@endif
                @if(request()->filled('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                <button class="h-14 px-8 rounded-2xl bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm uppercase tracking-wider transition-all shadow-lg shadow-violet-500/20 hover:shadow-violet-500/30 hover:-translate-y-0.5" type="submit">Найти</button>
                @if(request()->hasAny(['q','subject','price_max','exam_track','diagnostic_supported','official']))
                    <a class="h-14 px-6 rounded-2xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-bold text-sm flex items-center justify-center transition-colors" href="{{ route('tutors.index') }}">Сбросить</a>
                @endif
            </form>

            <!-- Subject chips -->
            <div class="flex gap-2 flex-wrap mt-5" aria-label="Быстрый выбор предмета">
                @foreach(array_slice($allSubjects, 0, 8) as $subject)
                    <a href="{{ route('tutors.index', array_filter(['subject' => $subject, 'sort' => request('sort')])) }}"
                       class="inline-flex items-center h-9 px-4 rounded-full text-sm font-bold transition-all duration-200 border
                              {{ request('subject') === $subject
                                  ? 'bg-violet-600 text-white border-violet-600 shadow-md shadow-violet-500/20'
                                  : 'bg-white text-gray-600 border-gray-200 hover:border-violet-300 hover:text-violet-600 hover:-translate-y-0.5' }}">
                        {{ $subject }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- FILTERS -->
    <section class="pb-2">
        <div class="max-w-7xl mx-auto px-6">
            <button @click="filtersOpen = !filtersOpen" class="flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-gray-900 transition-colors mb-4 md:hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                <span x-text="filtersOpen ? 'Скрыть фильтры' : 'Показать фильтры'">Показать фильтры</span>
            </button>

            <form method="GET" action="{{ route('tutors.index') }}"
                  class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 transition-all"
                  :class="filtersOpen ? '' : 'hidden md:block'">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                    <!-- Subject -->
                    <div class="flex flex-col gap-1.5 lg:col-span-1">
                        <label for="subject" class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Предмет</label>
                        <select id="subject" name="subject" class="h-11 rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100 transition-all">
                            <option value="">Все предметы</option>
                            @foreach($allSubjects as $subject)
                                <option value="{{ $subject }}" @selected(request('subject') === $subject)>{{ $subject }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Max price -->
                    <div class="flex flex-col gap-1.5 lg:col-span-1">
                        <label for="price_max" class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Макс. цена</label>
                        <input id="price_max" type="number" min="0" step="1" name="price_max" value="{{ request('price_max') }}" placeholder="До ₽"
                               class="h-11 rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100 transition-all">
                    </div>

                    <!-- Sort -->
                    <div class="flex flex-col gap-1.5 lg:col-span-1">
                        <label for="sort" class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Сортировка</label>
                        <select id="sort" name="sort" class="h-11 rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100 transition-all">
                            <option value="rating" @selected(request('sort','rating')==='rating')>По рейтингу</option>
                            @if($diagnosticContext['subject'] ?? null)
                                <option value="match" @selected(request('sort')==='match')>По соответствию диагностики</option>
                            @endif
                            <option value="price_asc" @selected(request('sort')==='price_asc')>Сначала дешевле</option>
                            <option value="price_desc" @selected(request('sort')==='price_desc')>Сначала дороже</option>
                            <option value="experience" @selected(request('sort')==='experience')>По опыту</option>
                            <option value="outcomes" @selected(request('sort')==='outcomes')>По результатам</option>
                        </select>
                    </div>

                    <!-- Checkboxes -->
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:gap-4 lg:col-span-2">
                        <label class="flex items-center gap-2 cursor-pointer select-none group" for="exam_track">
                            <input id="exam_track" type="checkbox" name="exam_track" value="1" @checked(request()->boolean('exam_track'))
                                   class="w-4 h-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500 accent-violet-600">
                            <span class="text-sm font-semibold text-gray-600 group-hover:text-gray-900 transition-colors whitespace-nowrap">ЦЭ/ЦТ</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer select-none group" for="diagnostic_supported">
                            <input id="diagnostic_supported" type="checkbox" name="diagnostic_supported" value="1" @checked(request()->boolean('diagnostic_supported'))
                                   class="w-4 h-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500 accent-violet-600">
                            <span class="text-sm font-semibold text-gray-600 group-hover:text-gray-900 transition-colors whitespace-nowrap">Диагностика</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer select-none group" for="official">
                            <input id="official" type="checkbox" name="official" value="1" @checked(request()->boolean('official'))
                                   class="w-4 h-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500 accent-violet-600">
                            <span class="text-sm font-semibold text-gray-600 group-hover:text-gray-900 transition-colors whitespace-nowrap">Официальные</span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 lg:col-span-1">
                        @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                        <button class="flex-1 h-11 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm transition-colors shadow-sm" type="submit">Применить</button>
                        <a class="h-11 px-4 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-500 font-bold text-sm flex items-center justify-center transition-colors" href="{{ route('tutors.index') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- RESULTS -->
    <section class="py-6">
        <div class="max-w-7xl mx-auto px-6">
            <!-- Sort bar -->
            <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
                <p class="text-sm font-medium text-gray-400">
                    Найдено <span class="font-bold text-gray-900">{{ $tutors->total() }}</span> анкет
                </p>
                <div class="flex gap-2">
                    @auth
                        <a class="h-9 px-4 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-sm font-bold text-gray-600 flex items-center transition-colors" href="/admin">Кабинет</a>
                    @else
                        <a class="h-9 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold flex items-center transition-colors shadow-sm" href="/admin/register?redirect_to={{ urlencode(url()->full()) }}">Стать репетитором</a>
                    @endauth
                </div>
            </div>

            <!-- Cards grid -->
            @if($tutors->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5" aria-label="Список репетиторов">
                    @foreach($tutors as $tutor)
                        @php
                            $subjects = array_values(array_filter($tutor->subjects ?? []));
                            $initials = collect(explode(' ', (string)$tutor->user?->name))
                                ->filter()->map(fn(string $p)=>mb_substr($p,0,1))->take(2)->implode('');
                            $maskedName = collect(explode(' ', trim((string)$tutor->user?->name)))
                                ->filter()->values()
                                ->pipe(function($parts){
                                    if($parts->count()<=1) return (string)$parts->first();
                                    return $parts->first().' '.mb_substr((string)$parts->get(1),0,1).'.';
                                });
                            $examSpecializations = array_values(array_filter($tutor->exam_specializations ?? []));
                            $isOfficial = $tutor->legal_status !== 'none';
                            $isTop = (float)$tutor->rating_avg >= 4.8;
                        @endphp

                        <article class="reveal bg-white rounded-3xl border border-gray-100 shadow-sm p-6 hover:shadow-xl hover:border-violet-200 hover:-translate-y-1 transition-all duration-300 group">
                            <div class="flex gap-5">
                                <!-- Avatar -->
                                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-violet-50 to-gray-100 flex-shrink-0 flex items-center justify-center overflow-hidden text-lg font-bold text-gray-500">
                                    @if($tutor->avatar_path)
                                        <img src="{{ asset('storage/'.$tutor->avatar_path) }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        {{ $initials ?: 'ED' }}
                                    @endif
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <!-- Badges -->
                                    <div class="flex gap-1.5 flex-wrap mb-2">
                                        @if($isOfficial)<span class="inline-flex items-center h-6 px-2.5 rounded-full text-[11px] font-bold bg-lime-50 text-lime-700 border border-lime-200">Официальный</span>@endif
                                        @if($tutor->diagnostic_supported)<span class="inline-flex items-center h-6 px-2.5 rounded-full text-[11px] font-bold bg-violet-50 text-violet-600 border border-violet-200">Диагностика</span>@endif
                                        @if(in_array('ЦЭ', $examSpecializations, true) || in_array('ЦТ', $examSpecializations, true))
                                            <span class="inline-flex items-center h-6 px-2.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">ЦЭ/ЦТ</span>
                                        @endif
                                        @if((float)$tutor->rating_avg > 0)
                                            @if($isTop)<span class="inline-flex items-center h-6 px-2.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Топ</span>@endif
                                        @else
                                            <span class="inline-flex items-center h-6 px-2.5 rounded-full text-[11px] font-bold bg-violet-50 text-violet-600 border border-violet-200">Новый</span>
                                        @endif
                                    </div>

                                    <!-- Name -->
                                    <h2 class="text-lg font-bold text-gray-900 mb-1 truncate">{{ $maskedName }}</h2>

                                    <!-- Rating -->
                                    @if((float)$tutor->rating_avg > 0)
                                        <div class="flex items-center gap-1 text-sm text-gray-500 font-medium mb-2">
                                            <span class="text-amber-400">★</span>
                                            {{ number_format((float)$tutor->rating_avg, 1) }}
                                        </div>
                                    @endif

                                    <!-- Meta tags -->
                                    <div class="flex gap-1.5 flex-wrap mb-2">
                                        @foreach(array_slice($subjects,0,2) as $subject)
                                            <span class="inline-flex items-center h-6 px-2.5 rounded-lg bg-gray-50 text-xs font-semibold text-gray-600">{{ $subject }}</span>
                                        @endforeach
                                        <span class="inline-flex items-center h-6 px-2.5 rounded-lg bg-gray-50 text-xs font-semibold text-gray-600">{{ $tutor->experience_years }} лет опыта</span>
                                    </div>

                                    <!-- Stats -->
                                    @if((int) $tutor->students_prepared_count > 0 || (int) $tutor->average_score_growth > 0 || (int) $tutor->max_recent_score > 0)
                                        <div class="flex gap-1.5 flex-wrap mb-2">
                                            @if((int) $tutor->students_prepared_count > 0)
                                                <span class="inline-flex items-center h-6 px-2.5 rounded-lg bg-green-50 text-xs font-semibold text-green-700">{{ (int) $tutor->students_prepared_count }} учеников</span>
                                            @endif
                                            @if((int) $tutor->average_score_growth > 0)
                                                <span class="inline-flex items-center h-6 px-2.5 rounded-lg bg-green-50 text-xs font-semibold text-green-700">+{{ (int) $tutor->average_score_growth }} баллов</span>
                                            @endif
                                            @if((int) $tutor->max_recent_score > 0)
                                                <span class="inline-flex items-center h-6 px-2.5 rounded-lg bg-green-50 text-xs font-semibold text-green-700">до {{ (int) $tutor->max_recent_score }} б.</span>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Bio -->
                                    <p class="text-sm text-gray-400 leading-relaxed line-clamp-2">{{ \Illuminate\Support\Str::limit((string)$tutor->bio, 120, '...') }}</p>
                                </div>
                            </div>

                            <!-- Bottom: price + slot + actions -->
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mt-5 pt-5 border-t border-gray-100">
                                <div class="flex items-center gap-4">
                                    <div class="text-xl font-black text-gray-900">{{ number_format((float)$tutor->price_per_hour, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/>/час</div>
                                    <div class="text-xs font-semibold text-lime-700 bg-lime-50 border border-lime-200 rounded-lg px-2.5 py-1 leading-snug max-w-[14rem] truncate">
                                        {{ $availabilityHints[$tutor->user_id] ?? 'Ближайшее окно уточняется' }}
                                    </div>
                                </div>
                                <div class="flex gap-2 w-full sm:w-auto">
                                    <a class="flex-1 sm:flex-initial h-10 px-5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold flex items-center justify-center transition-all shadow-sm hover:shadow-md" href="{{ route('tutors.show', $tutor) }}">Записаться</a>
                                    <a class="flex-1 sm:flex-initial h-10 px-5 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-bold flex items-center justify-center transition-colors" href="{{ route('tutors.show', $tutor) }}">Подробнее</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">{{ $tutors->links() }}</div>
            @else
                <!-- Empty state -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-12 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-violet-50 text-violet-600 flex items-center justify-center mb-6">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <h2 class="text-2xl font-rimma font-black text-gray-900 mb-2">По этим фильтрам ничего не найдено</h2>
                    <p class="text-gray-500 font-medium mb-8 max-w-md mx-auto">Сбросьте фильтры, выберите другой предмет или расширьте диапазон цены.</p>
                    <div class="flex gap-3 justify-center flex-wrap">
                        <a class="h-12 px-6 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-bold text-sm flex items-center transition-colors" href="{{ route('tutors.index') }}">Сбросить фильтры</a>
                        <a class="h-12 px-6 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm flex items-center transition-colors shadow-lg shadow-violet-500/20" href="/admin/register?redirect_to={{ urlencode(url()->full()) }}">Стать первым репетитором</a>
                    </div>
                </div>
            @endif

            <!-- Bottom CTA -->
            <div class="mt-12 bg-gray-900 rounded-3xl p-8 sm:p-12 text-center relative overflow-hidden shadow-xl">
                <div class="absolute inset-0 z-0">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-violet-600 rounded-full blur-[120px] opacity-20"></div>
                </div>
                <div class="relative z-10">
                    <h2 class="text-2xl sm:text-3xl font-rimma font-black uppercase tracking-tight mb-3 text-white">Вы репетитор? Подключайтесь</h2>
                    <p class="text-gray-400 font-medium mb-8 max-w-lg mx-auto">Анкета, расписание, бронирование и оплата — в одном кабинете. Регистрация бесплатна.</p>
                    <div class="flex gap-3 justify-center flex-wrap">
                        <a class="h-12 px-8 rounded-xl bg-lime-400 hover:bg-lime-300 text-gray-900 font-bold text-sm flex items-center transition-all shadow-lg shadow-lime-400/20 hover:-translate-y-0.5" href="/admin/register?redirect_to={{ urlencode(url()->full()) }}">Зарегистрироваться бесплатно</a>
                        <a class="h-12 px-8 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold text-sm flex items-center transition-colors" href="/admin/login?redirect_to={{ urlencode(url()->full()) }}">Уже есть профиль</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.site-footer')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const els = document.querySelectorAll('.reveal');
            if (!els.length) return;
            const io = new IntersectionObserver(entries => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('is-visible');
                        io.unobserve(e.target);
                    }
                });
            }, { threshold: 0.05, rootMargin: '0px 0px -20px 0px' });
            els.forEach(el => io.observe(el));
        });
    </script>
</body>
</html>
