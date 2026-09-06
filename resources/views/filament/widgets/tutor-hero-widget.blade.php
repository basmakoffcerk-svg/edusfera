@php
    $tz = config('booking.display_timezone', 'Europe/Minsk');
    $lesson = $upcomingLesson;
@endphp

<div class="tutor-hero" x-data="tutorLessonCountdown({
        startsAt: {{ (int) $lessonStartsAt }},
        endsAt: {{ (int) $lessonEndsAt }},
        joinFrom: {{ (int) $lessonJoinFrom }},
        joinUntil: {{ (int) $lessonJoinUntil }},
        joinAvailable: {{ $meetingJoinAvailable ? 'true' : 'false' }},
    })">

    {{-- Приветствие: один герой-заголовок экрана и бейдж подписки --}}
    <div class="tutor-hero-top">
        <div>
            <h2 class="tutor-hero-title">{{ $greeting }}, {{ $firstName }} 👋</h2>
            <p class="tutor-hero-date">{{ $dateLine }}</p>
        </div>
        <a href="/admin/tutor-subscription-page" class="tutor-hero-sub-pill {{ $isInGrace ? 'tutor-hero-sub-pill--grace' : ($isTrial ? 'tutor-hero-sub-pill--trial' : 'tutor-hero-sub-pill--active') }}" title="Управление подпиской">
            @if($isTrial)
                <span>🌱</span>
            @elseif($isInGrace)
                <span>⚠️</span>
            @else
                <span>⚡</span>
            @endif
            <span>{{ $subPillText }}</span>
        </a>
    </div>

    {{-- Ненавязчивое предупреждение об окончании / льготном периоде --}}
    @if ($isInGrace)
        <div class="tutor-hero-sub-alert tutor-hero-sub-alert--grace">
            <div class="tutor-hero-sub-alert-content">
                <svg class="tutor-hero-sub-alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <strong>Платёж за подписку не прошёл.</strong>
                    <span>Действует льготный период (осталось {{ $graceDaysRemaining }} {{ trans_choice('день|дня|дней', $graceDaysRemaining) }}). Обновите карту во избежание блокировки класса.</span>
                </div>
            </div>
            <a href="/admin/tutor-subscription-page" class="tutor-hero-sub-alert-btn tutor-hero-sub-alert-btn--danger">
                Обновить карту
            </a>
        </div>
    @elseif ($isExpiringSoon)
        <div class="tutor-hero-sub-alert tutor-hero-sub-alert--expiring">
            <div class="tutor-hero-sub-alert-content">
                <svg class="tutor-hero-sub-alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <div>
                    @if ($isTrial)
                        <strong>Пробный период завершается через {{ $daysRemaining }} {{ trans_choice('день|дня|дней', $daysRemaining) }}.</strong>
                        <span>Подключите тариф, чтобы продолжить преподавать.</span>
                    @else
                        <strong>Подписка истекает через {{ $daysRemaining }} {{ trans_choice('день|дня|дней', $daysRemaining) }}.</strong>
                        <span>Продлите тариф во избежание приостановки доступа.</span>
                    @endif
                </div>
            </div>
            <a href="/admin/tutor-subscription-page" class="tutor-hero-sub-alert-btn">
                Перейти к тарифу
            </a>
        </div>
    @endif

    {{-- Персональная ссылка для записи --}}
    <div class="tutor-hero-booking-bar" x-data="{ copied: false }">
        <div class="tutor-hero-booking-left">
            <svg class="tutor-hero-booking-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
            </svg>
            <span class="tutor-hero-booking-label">Ссылка для записи:</span>
            <a href="{{ $bookingUrl }}" target="_blank" class="tutor-hero-booking-url">{{ $bookingUrl }}</a>
        </div>
        <button 
            type="button" 
            class="tutor-hero-copy-btn" 
            :class="{ 'tutor-hero-copy-btn--copied': copied }"
            @click="navigator.clipboard.writeText('{{ $bookingUrl }}'); copied = true; setTimeout(() => copied = false, 2500)"
        >
            <template x-if="!copied">
                <span style="display:inline-flex; align-items:center; gap:6px;">
                    <svg style="width:15px; height:15px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9 9 9 0 0 0-9 9v.375c0 .621.504 1.125 1.125 1.125H6.75m9 6.75v-3.75A2.25 2.25 0 0 0 13.5 12h-3a2.25 2.25 0 0 0-2.25 2.25v3.75m9 0H7.5"/></svg>
                    <span>Скопировать</span>
                </span>
            </template>
            <template x-if="copied">
                <span style="display:inline-flex; align-items:center; gap:6px;">
                    <svg style="width:15px; height:15px;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    <span>Скопировано!</span>
                </span>
            </template>
        </button>
    </div>

    @if ($lesson)
        {{-- Карточка ближайшего урока: главный фокус экрана --}}
        <div class="tutor-hero-card {{ $meetingJoinAvailable ? 'tutor-hero-card--live' : '' }}">
            <div class="tutor-hero-status">
                <span class="tutor-hero-dot" x-show="joinAvailable" x-cloak></span>
                <span x-show="!hydrated">Ближайший урок · {{ $meetingJoinAvailable ? 'идёт сейчас' : 'скоро' }}</span>
                <span x-show="hydrated && !started" x-cloak>Ближайший урок · <span style="color:#7D39EB" x-text="countdown"></span></span>
                <span x-show="hydrated && started && !finished" x-cloak style="color:#059669">Урок идёт сейчас</span>
                <span x-show="hydrated && finished" x-cloak>Урок завершается</span>
            </div>

            <div class="tutor-hero-info">
                <div>
                    <p class="tutor-hero-name">{{ $lesson->student?->name ?? $lesson->parent?->name ?? 'Ученик' }}</p>
                    <p class="tutor-hero-sub">{{ $lesson->package_label }} · {{ $lesson->duration_minutes ?? 60 }} мин</p>
                </div>
                <p class="tutor-hero-time">
                    {{ $lesson->start_time->timezone($tz)->isSameDay(now($tz)) ? 'сегодня' : $lesson->start_time->timezone($tz)->translatedFormat('j F') }},
                    {{ $lesson->start_time->timezone($tz)->translatedFormat('H:i') }}–{{ $lesson->end_time->timezone($tz)->translatedFormat('H:i') }}
                </p>
            </div>

            <div class="tutor-cta-row">
                @if ($meetingJoinAvailable && $classroomUrl)
                    <a href="{{ $classroomUrl }}" target="_blank" class="tutor-cta tutor-cta--lime">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        Войти в класс
                    </a>
                @elseif ($classroomUrl)
                    <a href="{{ $classroomUrl }}" class="tutor-cta tutor-cta--primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                        Карточка урока
                    </a>
                @endif
                <a href="/admin/lessons" class="tutor-cta tutor-cta--ghost">Расписание</a>
            </div>
        </div>
    @else
        {{-- Свободный день --}}
        <div class="tutor-hero-card tutor-hero-card--calm">
            <p class="tutor-hero-name">Свободное окно ✨</p>
            <p class="tutor-hero-text">
                Предстоящих уроков нет. Откройте календарь и заполните слоты — так ученики находят вас чаще.
            </p>
            <div class="tutor-cta-row">
                <a href="/admin/tutor-availability" class="tutor-cta tutor-cta--primary">Настроить слоты</a>
                <a href="/admin/lessons" class="tutor-cta tutor-cta--ghost">Расписание</a>
            </div>
        </div>
    @endif

    @if ($newRequestsCount > 0)
        <a href="/admin/lesson-requests" class="tutor-hero-alert">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            <span><strong>{{ $newRequestsCount }} {{ trans_choice('новая заявка|новые заявки|новых заявок', $newRequestsCount) }}</strong></span>
            <span class="tutor-alert-note">— ждёт вашего ответа</span>
            <svg class="tutor-alert-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </a>
    @endif
</div>
{{-- Дизайн-система кабинета подключается через renderHook PAGE_START
     (см. AdminPanelProvider): @once внутри Livewire-виджетов ненадёжен. --}}
