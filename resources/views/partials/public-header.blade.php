@php
    $dark ??= false;
    $user = auth()->user();
    $notificationsCount = $user ? app(\App\Services\ChatUnreadCounter::class)->countForUser($user) : 0;
    $isTutor = $user?->isTutor() ?? false;
    $isAdmin = $user?->isAdmin() ?? false;
    $isStudentOrParent = ($user?->isStudent() || $user?->isParent()) ?? false;
    $roleLabel = $user ? \App\Services\MultiAccountService::roleLabel($user->role) : '';
    $linkedAccounts = $user ? app(\App\Services\MultiAccountService::class)->getLinkedAccounts() : [];

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
            <a href="{{ route('news.index') }}" class="inline-flex min-h-10 items-center rounded-xl border px-4 text-sm font-semibold {{ $ghostClass }}">
                Новости
            </a>

            @auth
                <a href="/admin/messages" class="relative inline-flex min-h-10 items-center justify-center rounded-xl border px-4 text-sm font-semibold {{ $ghostClass }}">
                    Сообщения
                    @if ($notificationsCount > 0)
                        <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[11px] font-bold text-white">
                            {{ $notificationsCount > 9 ? '9+' : $notificationsCount }}
                        </span>
                    @endif
                </a>

                <details class="group relative">
                    <summary class="inline-flex min-h-10 cursor-pointer list-none items-center gap-2.5 rounded-xl border px-3 text-sm font-semibold {{ $ghostClass }}">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#C6FF33] text-xs font-black text-black">
                            {{ mb_substr((string) $user->name, 0, 1) }}
                        </span>
                        <span class="max-w-28 truncate">{{ $user->name }}</span>
                        <span class="rounded-md bg-purple-100 px-1.5 py-0.5 text-[10px] font-bold text-purple-700">
                            {{ $roleLabel }}
                        </span>
                    </summary>

                    <div class="absolute right-0 z-30 mt-2 w-64 overflow-hidden rounded-2xl border border-gray-200 bg-white p-2 text-gray-900 shadow-2xl">
                        <div class="px-3 py-1.5 text-xs text-gray-500 border-b border-gray-100 mb-1">
                            {{ $roleLabel }} · {{ $user->email }}
                        </div>
                        <a href="/admin" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-bold text-[#7D39EB] transition hover:bg-purple-50">
                            🚀 Личный кабинет
                        </a>

                        @if($isTutor)
                            <a href="/admin/tutor-subscription-page" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                                Управление тарифом
                            </a>
                            <a href="/admin/tutor-availability-page" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                                Расписание
                            </a>
                            <a href="/admin/lessons" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                                Мои занятия
                            </a>
                            <a href="/admin/transactions" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                                Мои финансы
                            </a>
                        @elseif($isStudentOrParent)
                            <a href="/admin/lessons" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                                Мои занятия
                            </a>
                            <a href="/admin/diagnostic" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                                ИИ-Диагностика
                            </a>
                            <a href="/admin/homework" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                                Домашние задания
                            </a>
                            <a href="/admin/wallet" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                                Учёт денег
                            </a>
                        @elseif($isAdmin)
                            <a href="/admin/users" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                                Пользователи
                            </a>
                            <a href="/admin/saa-s-management" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                                Управление SaaS
                            </a>
                        @endif

                        <a href="/admin/messages" class="flex min-h-9 items-center rounded-xl px-3 text-sm font-semibold transition hover:bg-gray-100">
                            Сообщения
                        </a>

                        @if(count($linkedAccounts) > 0)
                            <div class="my-1 border-t border-gray-100 pt-1">
                                <div class="px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-gray-400">Другие аккаунты</div>
                                @foreach($linkedAccounts as $account)
                                    <form method="POST" action="{{ route('account.switch', $account['id']) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center justify-between rounded-xl px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                                            <span class="truncate">{{ $account['name'] }}</span>
                                            <span class="text-[10px] text-gray-400">{{ \App\Services\MultiAccountService::roleLabel($account['role']) }}</span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif

                        <div class="my-1 border-t border-gray-100 pt-1">
                            <a href="{{ route('account.add') }}" class="flex min-h-9 items-center rounded-xl px-3 text-xs font-semibold text-gray-600 hover:bg-gray-100">
                                + Добавить аккаунт
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="flex min-h-9 w-full items-center rounded-xl px-3 text-left text-xs font-bold text-red-600 transition hover:bg-red-50">
                                    Выйти
                                </button>
                            </form>
                        </div>
                    </div>
                </details>
            @else
                <a href="/login" class="inline-flex min-h-10 items-center rounded-xl border border-gray-300 px-4 text-sm font-semibold {{ $ghostClass }}">
                    Войти
                </a>
                <a href="/register" class="inline-flex min-h-10 items-center rounded-xl bg-[#7D39EB] px-4 text-sm font-semibold text-white transition hover:bg-[#6A2ED1]">
                    Регистрация
                </a>
            @endauth
        </nav>
    </div>
</header>
