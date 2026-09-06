<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Новости Edusfera — Полезные материалы и объявления</title>
    <meta name="description" content="Будьте в курсе последних новостей платформы Edusfera. Полезные статьи, видеоуроки, лайфхаки по подготовке к ЦТ/ЦЭ.">

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

        .glass-panel {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.02);
            border-radius: 2rem;
        }

        [x-cloak] { display: none !important; }
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
                <a href="{{ route('news.index') }}" class="text-black font-extrabold border-b-2 border-violet-600 pb-1">Новости</a>
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
                <a href="{{ route('news.index') }}" @click="mobileOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-gray-700 hover:bg-black/5 transition-colors bg-violet-50 text-violet-700">
                    <svg class="w-5 h-5 text-violet-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 00-2-2v3m2-3V9a2 2 0 00-2-2v3m2-3V5a2 2 0 00-2-2v3m2-3v12a2 2 0 00-2-2H9"></path></svg>
                    Новости
                </a>
            </nav>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="pt-36 pb-24 min-h-[70vh]">
        <div class="max-w-7xl mx-auto px-6">
            <!-- Header Section -->
            <div class="mb-16 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-violet-50 border border-violet-100 mb-6">
                    <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                    <span class="text-xs font-bold tracking-widest uppercase text-violet-600">Новости и блог</span>
                </div>
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-rimma font-black uppercase tracking-tight text-gray-900 mb-4">Новости Edusfera</h1>
                <p class="text-lg text-gray-500 font-medium max-w-2xl mx-auto">Полезные советы по подготовке, лайфхаки для ЦТ/ЦЭ и важные обновления нашей платформы.</p>
            </div>

            @if($articles->isEmpty())
                <div class="text-center py-20 bg-white rounded-3xl border border-gray-100">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 00-2-2v3m2-3V9a2 2 0 00-2-2v3m2-3V5a2 2 0 00-2-2v3m2-3v12a2 2 0 00-2-2H9"></path></svg>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Здесь пока пусто</h3>
                    <p class="text-gray-400">Мы работаем над первыми материалами. Загляните позже!</p>
                </div>
            @else
                <!-- Grid of Articles -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($articles as $article)
                        <div class="glass-panel bg-white border border-gray-200 p-5 rounded-[2rem] hover:shadow-2xl hover:border-violet-300 transition-all duration-350 group flex flex-col justify-between">
                            <div>
                                <!-- Cover / Video Placeholder -->
                                <div class="relative w-full h-48 rounded-[1.5rem] bg-gray-100 mb-6 overflow-hidden">
                                    @if($article->featured_image)
                                        <img src="{{ asset('storage/' . $article->featured_image) }}" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-500" alt="{{ $article->title }}">
                                    @else
                                        <!-- Geometric Abstract Placeholder -->
                                        <div class="w-full h-full bg-gradient-to-tr from-violet-100 to-lime-50 flex items-center justify-center">
                                            <svg class="w-12 h-12 text-violet-300 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                    @endif

                                    <!-- Video Icon badge if video is present -->
                                    @if($article->video_url)
                                        <div class="absolute bottom-3 right-3 bg-violet-600/90 text-white w-9 h-9 rounded-full flex items-center justify-center shadow-lg backdrop-blur-sm">
                                            <svg class="w-4 h-4 fill-current ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    @endif
                                </div>

                                <!-- Date -->
                                <div class="text-[10px] uppercase font-bold tracking-wider text-violet-600 mb-2">
                                    {{ $article->published_at ? $article->published_at->format('d.m.Y') : $article->created_at->format('d.m.Y') }}
                                </div>

                                <!-- Title -->
                                <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-violet-600 transition-colors line-clamp-2">
                                    {{ $article->title }}
                                </h3>

                                <!-- Excerpt -->
                                <p class="text-gray-500 text-sm leading-relaxed mb-6 line-clamp-3">
                                    {{ Str::limit(preg_replace('/\s+/', ' ', strip_tags(str_replace(['</p>', '</div>', '<br>', '<br />', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>'], ' ', $article->content))), 130) }}
                                </p>
                            </div>

                            <a href="{{ route('news.show', $article->slug) }}" class="w-full text-center py-3 rounded-xl bg-violet-50 text-violet-700 font-bold text-sm group-hover:bg-violet-600 group-hover:text-white transition-all">
                                Читать статью
                            </a>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-16 flex justify-center">
                    {{ $articles->links() }}
                </div>
            @endif
        </div>
    </main>

    @include('partials.site-footer')

</body>
</html>
