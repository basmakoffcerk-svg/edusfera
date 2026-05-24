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
        @auth
            <a href="/admin" class="ed-nav__link">Кабинет</a>
            <a href="/admin/messages" class="ed-nav__link">
                Сообщения
                @if($unreadMessagesCount > 0)
                    <span class="ed-nav__badge">{{ $unreadMessagesCount > 9 ? '9+' : $unreadMessagesCount }}</span>
                @endif
            </a>
            <details class="ed-nav__dropdown">
                <summary class="ed-nav__link ed-nav__user">
                    <span class="ed-nav__avatar">{{ mb_substr((string)auth()->user()->name, 0, 1) }}</span>
                    <span>{{ auth()->user()->name }}</span>
                </summary>
                <div class="ed-nav__menu">
                    <div class="ed-nav__menu-meta">{{ \App\Services\MultiAccountService::roleLabel(auth()->user()->role) }}</div>
                    <a href="/admin">Личный кабинет</a>
                    <a href="{{ auth()->user()->role === 'tutor' ? '/admin/transactions' : '/admin/lessons' }}">
                        {{ auth()->user()->role === 'tutor' ? 'Мои финансы' : 'Мои занятия' }}
                    </a>
                    <a href="/admin/messages">Сообщения</a>

                    @php $linked = app(\App\Services\MultiAccountService::class)->getLinkedAccounts(); @endphp
                    @if(count($linked) > 0)
                        <div class="ed-nav__menu-separator"></div>
                        <div class="ed-nav__menu-meta">Другие аккаунты</div>
                        @foreach($linked as $account)
                            <a href="{{ route('account.switch', $account['id']) }}" class="ed-nav__linked-account">
                                <span class="ed-nav__linked-avatar">{{ mb_substr($account['name'], 0, 1) }}</span>
                                <span class="ed-nav__linked-content">
                                    <span class="ed-nav__linked-name">{{ $account['name'] }}</span>
                                    <span class="ed-nav__linked-role">{{ \App\Services\MultiAccountService::roleLabel($account['role']) }}</span>
                                </span>
                            </a>
                        @endforeach
                    @endif

                    <div class="ed-nav__menu-separator"></div>
                    <a href="{{ route('account.add') }}" class="ed-nav__add-account">
                        <span>+</span> Добавить аккаунт
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Выйти</button>
                    </form>
                </div>
            </details>
        @else
            <a href="/admin/login?redirect_to={{ urlencode(url()->full()) }}" class="ed-nav__btn">Войти</a>
        @endauth
    </div>
</nav>
