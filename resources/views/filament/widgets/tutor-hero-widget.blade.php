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

    {{-- Приветствие: один герой-заголовок экрана --}}
    <div class="tutor-hero-top">
        <h2 class="tutor-hero-title">{{ $greeting }}, {{ $firstName }} 👋</h2>
        <p class="tutor-hero-date">{{ $dateLine }}</p>
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
