@php
    $dark ??= false;
    $unreadMessagesCount = auth()->check() ? app(\App\Services\ChatUnreadCounter::class)->countForUser(auth()->user()) : 0;
@endphp
<nav class="ed-nav {{ $dark ? 'ed-nav--dark' : '' }}">
    <a href="{{ route('home') }}" class="ed-nav__brand">
        <span class="ed-brand" style="font-size:1.8rem;">Edusfera</span>
        <span class="ed-nav__dot"></span>
    </a>
    <div class="ed-nav__links">
        <a href="{{ route('tutors.index') }}" class="ed-nav__link">Каталог</a>
        <a href="{{ route('for-tutors') }}" class="ed-nav__link">Преподавателям</a>
        <a href="{{ route('news.index') }}" class="ed-nav__link">Новости</a>
        @auth
            @php
                $user = auth()->user();
                $isTutor = $user->isTutor();
                $isAdmin = $user->isAdmin();
                $isStudentOrParent = $user->isStudent() || $user->isParent();
                $roleLabel = \App\Services\MultiAccountService::roleLabel($user->role);
            @endphp
            <a href="/admin/messages" class="ed-nav__link">
                Сообщения
                @if($unreadMessagesCount > 0)
                    <span class="ed-nav__badge">{{ $unreadMessagesCount > 9 ? '9+' : $unreadMessagesCount }}</span>
                @endif
            </a>
            <details class="ed-nav__dropdown">
                <summary class="ed-nav__link ed-nav__user" style="cursor:pointer; display:inline-flex; align-items:center; gap:0.5rem;">
                    <span class="ed-nav__avatar" style="width:30px; height:30px; border-radius:999px; background:#C6FF33; color:#111; font-weight:800; display:inline-flex; align-items:center; justify-content:center; font-size:0.75rem;">
                        {{ mb_substr((string)$user->name, 0, 1) }}
                    </span>
                    <span style="font-weight:600;">{{ $user->name }}</span>
                    <span style="font-size:0.7rem; font-weight:700; padding:2px 7px; border-radius:999px; background:rgba(125,57,235,0.12); color:#7D39EB;">
                        {{ $roleLabel }}
                    </span>
                </summary>
                <div class="ed-nav__menu" style="min-width:15rem;">
                    <div class="ed-nav__menu-meta">{{ $roleLabel }} · {{ $user->email }}</div>
                    <a href="/admin" style="font-weight:700; color:#7D39EB;">🚀 Личный кабинет</a>

                    @if($isTutor)
                        <a href="/admin/tutor-subscription-page">Управление тарифом</a>
                        <a href="/admin/tutor-availability-page">Расписание</a>
                        <a href="/admin/lessons">Мои занятия</a>
                        <a href="/admin/transactions">Мои финансы</a>
                    @elseif($isStudentOrParent)
                        <a href="/admin/lessons">Мои занятия</a>
                        <a href="/admin/diagnostic">ИИ-Диагностика</a>
                        <a href="/admin/homework">Домашние задания</a>
                        <a href="/admin/wallet">Учёт денег</a>
                    @elseif($isAdmin)
                        <a href="/admin/users">Пользователи</a>
                        <a href="/admin/saa-s-management">Управление SaaS</a>
                    @endif

                    <a href="/admin/messages">Сообщения</a>

                    @php $linked = app(\App\Services\MultiAccountService::class)->getLinkedAccounts(); @endphp
                    @if(count($linked) > 0)
                        <div class="ed-nav__menu-separator"></div>
                        <div class="ed-nav__menu-meta">Другие аккаунты</div>
                        @foreach($linked as $account)
                            <form method="POST" action="{{ route('account.switch', $account['id']) }}" style="margin:0;">
                                @csrf
                                <button type="submit" class="ed-nav__linked-account" style="color:var(--text, #111); width:100%; display:flex; align-items:center; gap:0.5rem; background:none; border:none; padding:0.5rem 0.75rem; text-align:left; cursor:pointer;">
                                    <span class="ed-nav__linked-avatar">{{ mb_substr($account['name'], 0, 1) }}</span>
                                    <span class="ed-nav__linked-content">
                                        <span class="ed-nav__linked-name">{{ $account['name'] }}</span>
                                        <span class="ed-nav__linked-role">{{ \App\Services\MultiAccountService::roleLabel($account['role']) }}</span>
                                    </span>
                                </button>
                            </form>
                        @endforeach
                    @endif

                    <div class="ed-nav__menu-separator"></div>
                    <a href="{{ route('account.add') }}" class="ed-nav__add-account" style="display:block; padding:0.5rem 0.75rem;">
                        <span>+</span> Добавить аккаунт
                    </a>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" style="width:100%; text-align:left; padding:0.5rem 0.75rem; background:none; border:none; color:#ef4444; font-weight:600; cursor:pointer;">
                            Выйти
                        </button>
                    </form>
                </div>
            </details>
        @else
            <a href="/login" class="ed-nav__link">Войти</a>
            <a href="/register" class="ed-nav__btn" style="background:#7D39EB; color:#fff; padding:0.5rem 1rem; border-radius:0.5rem; text-decoration:none; font-weight:700;">Регистрация</a>
        @endauth
    </div>
</nav>
