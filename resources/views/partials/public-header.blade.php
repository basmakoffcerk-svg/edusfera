@php
    $dark ??= false;
    $user = auth()->user();
    $notificationsCount = $user ? app(\App\Services\ChatUnreadCounter::class)->countForUser($user) : 0;
    $dashboardUrl = '/admin';
    $secondaryDashboardUrl = $user?->role === 'tutor' ? '/admin/transactions' : '/admin/lessons';
    $baseClass = $dark
        ? 'border-white/15 bg-black text-white'
        : 'border-gray-200 bg-white text-gray-900';
    $ghostClass = $dark
        ? 'border-white/25 text-white hover:border-[#96F22B] hover:text-[#96F22B]'
        : 'border-gray-300 text-gray-900 hover:border-[#96F22B] hover:text-[#111827]';
@endphp

<header class="rounded-2xl border px-4 py-3 {{ $baseClass }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('home') }}" class="ed-brand text-2xl {{ $dark ? 'text-white' : 'text-black' }}">Edusfera</a>

        <nav class="flex flex-wrap items-center gap-2">
            <a href="{{ route('home') }}" class="inline-flex min-h-10 items-center rounded-xl border px-4 text-sm font-semibold {{ $ghostClass }}">
                Главная
            </a>
            <a href="{{ route('tutors.index') }}" class="inline-flex min-h-10 items-center rounded-xl border px-4 text-sm font-semibold {{ $ghostClass }}">
                Каталог
            </a>
            <a href="{{ route('for-tutors') }}" class="inline-flex min-h-10 items-center rounded-xl border px-4 text-sm font-semibold {{ $ghostClass }}">
                Я — репетитор
            </a>

            @auth
                <a href="/admin/messages" class="relative inline-flex min-h-10 items-center justify-center rounded-xl border px-4 text-sm font-semibold {{ $ghostClass }}">
                    Уведомления
                    @if ($notificationsCount > 0)
                        <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[11px] font-bold text-white">
                            {{ $notificationsCount > 9 ? '9+' : $notificationsCount }}
                        </span>
                    @endif
                </a>

                <details class="group relative">
                    <summary class="inline-flex min-h-10 cursor-pointer list-none items-center gap-3 rounded-xl border px-4 text-sm font-semibold {{ $ghostClass }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#96F22B] text-xs font-black text-black">
                            {{ mb_substr((string) $user->name, 0, 1) }}
                        </span>
                        <span class="max-w-28 truncate">{{ $user->name }}</span>
                    </summary>

                    <div class="absolute right-0 z-20 mt-2 w-56 overflow-hidden rounded-2xl border border-gray-200 bg-white p-2 text-gray-900 shadow-2xl">
                        <a href="{{ $dashboardUrl }}" class="flex min-h-10 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                            Личный кабинет
                        </a>
                        <a href="{{ $secondaryDashboardUrl }}" class="flex min-h-10 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                            {{ $user->role === 'tutor' ? 'Мои финансы' : 'Мои занятия' }}
                        </a>
                        <a href="/admin/messages" class="flex min-h-10 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                            Сообщения
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex min-h-10 w-full items-center rounded-xl px-3 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                Выйти
                            </button>
                        </form>
                    </div>
                </details>
            @else
                <a href="/admin/login?redirect_to={{ urlencode(url()->full()) }}" class="inline-flex min-h-10 items-center rounded-xl bg-[#7D39EB] px-4 text-sm font-semibold text-white transition hover:bg-[#6A2ED1]">
                    Войти в профиль
                </a>
            @endauth
        </nav>
    </div>
</header>
