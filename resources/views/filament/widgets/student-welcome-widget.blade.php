@php
    $firstName = explode(' ', trim((string) $user->name))[0] ?? 'Ученик';
@endphp

<div class="student-dashboard-root">
    {{-- ═══ 1. Hero-шапка ученика ═══ --}}
    <div class="student-hero-header">
        <div class="student-hero-top">
            <div class="student-hero-title-box">
                <h1>Привет, {{ $firstName }}! 👋</h1>
                <div class="student-hero-subtitle">
                    <span class="student-role-badge">
                        <span>⚡</span>
                        <span>{{ $user->isParent() ? 'Родитель Edusfera' : 'Ученик Edusfera' }}</span>
                    </span>
                    <span>•</span>
                    <span>{{ $dateLine }}</span>
                </div>
            </div>

            {{-- Интерактивные чипсы быстрых действий --}}
            <div class="student-hero-actions">
                @if($nextLesson && $meetingJoinAvailable)
                    <a href="{{ route('classroom.show', $nextLesson) }}" target="_blank" class="student-chip-btn student-chip-btn--primary">
                        <span class="student-pulse-dot" style="background:#FFFFFF; box-shadow:none;"></span>
                        <span>В класс</span>
                    </a>
                @endif

                <a href="/admin/homework" class="student-chip-btn" title="Домашние задания">
                    <span>📚</span>
                    <span>Домашка</span>
                    @if($activeHomeworkCount > 0)
                        <span class="student-chip-badge">{{ $activeHomeworkCount }}</span>
                    @endif
                </a>

                <a href="/admin/diagnostic" class="student-chip-btn" title="Траектория и срез знаний">
                    <span>🎯</span>
                    <span>Диагностика</span>
                    @if($diagnosticPendingCount > 0)
                        <span class="student-chip-badge" style="background:#D97706;">!</span>
                    @endif
                </a>

                <a href="/admin/messages" class="student-chip-btn" title="Чат с преподавателями">
                    <span>💬</span>
                    <span>Чат</span>
                    @if($unreadMessagesCount > 0)
                        <span class="student-chip-badge">{{ $unreadMessagesCount }}</span>
                    @endif
                </a>

                <a href="/admin/lessons" class="student-chip-btn" title="Мои занятия">
                    <span>📅</span>
                    <span>Уроки</span>
                    @if(($scheduledLessonsCount ?? 0) > 0)
                        <span class="student-chip-badge">{{ $scheduledLessonsCount }}</span>
                    @endif
                </a>

                <a href="/tutors" class="student-chip-btn" title="Каталог репетиторов">
                    <span>🔍</span>
                    <span>Каталог</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ═══ 2. Spotlight-карточка активного / ближайшего урока ═══ --}}
    @if($nextLesson)
        @php
            $tutor = $nextLesson->tutor;
            $tutorName = $tutor?->name ?? 'Преподаватель';
            $tutorInitial = mb_strtoupper(mb_substr($tutorName, 0, 1));
            $subject = $nextLesson->subject ?? 'Индивидуальное занятие';
            $classroomUrl = route('classroom.show', $nextLesson);
        @endphp

        <div class="student-spotlight-card {{ $isLiveNow ? 'student-spotlight-card--live' : 'student-spotlight-card--upcoming' }}">
            <div class="student-spotlight-inner">
                <div>
                    <div class="student-spotlight-status">
                        @if($isLiveNow)
                            <span class="student-pulse-dot"></span>
                            <span style="color:#10B981;">Урок идет прямо сейчас</span>
                        @else
                            <span style="color:var(--stu-accent);">⏱️ Ближайший урок</span>
                            <span style="color:var(--stu-text-muted);">
                                @if($startsInMinutes !== null && $startsInMinutes < 60)
                                    (через {{ $startsInMinutes }} мин)
                                @elseif($startsInMinutes !== null && $startsInMinutes < 1440)
                                    (через {{ intdiv($startsInMinutes, 60) }} ч {{ $startsInMinutes % 60 }} мин)
                                @else
                                    ({{ $nextLesson->start_time->translatedFormat('j F, H:i') }})
                                @endif
                            </span>
                        @endif
                    </div>

                    <div class="student-spotlight-details">
                        <div class="student-tutor-avatar-lg">
                            {{ $tutorInitial }}
                        </div>
                        <div class="student-spotlight-info">
                            <h3>{{ $subject }}</h3>
                            <div class="student-spotlight-meta">
                                <span>Преподаватель: <strong>{{ $tutorName }}</strong></span>
                                <span>•</span>
                                <span>🕒 {{ $nextLesson->start_time->translatedFormat('j F (D), H:i') }} – {{ $nextLesson->end_time->format('H:i') }}</span>
                                @if(($nextLesson->payment_status ?? '') === \App\Models\Lesson::PAYMENT_PAID)
                                    <span class="student-status-badge student-status-badge--paid">✓ Согласовано</span>
                                @else
                                    <span class="student-status-badge student-status-badge--unpaid">Запланирован</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    @if($isLiveNow || $meetingJoinAvailable)
                        <a href="{{ $classroomUrl }}" target="_blank" class="student-spotlight-cta student-spotlight-cta--live">
                            <span>🚀</span>
                            <span>Войти в онлайн-класс</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    @else
                        <a href="{{ $classroomUrl }}" class="student-spotlight-cta student-spotlight-cta--upcoming">
                            <span>Открыть класс урока</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="student-spotlight-card student-spotlight-card--empty">
            <div style="font-size: 36px; margin-bottom: 8px;">✨</div>
            <h3 style="margin: 0 0 6px; font-size: 1.2rem; font-weight: 800; color: var(--stu-text-main);">
                Готовы к новому уроку?
            </h3>
            <p style="margin: 0 auto 18px; font-size: 0.9rem; color: var(--stu-text-muted); max-width: 520px; line-height: 1.5;">
                Выберите проверенного репетитора для подготовки к ЦТ/ЦЭ или школьной программе, либо начните со стартовой диагностики знаний.
            </p>
            <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                <a href="/tutors" class="student-chip-btn student-chip-btn--primary" style="padding: 10px 20px; font-size: 14px;">
                    <span>🔍</span>
                    <span>Выбрать репетитора в каталоге</span>
                </a>
                <a href="/admin/diagnostic" class="student-chip-btn" style="padding: 10px 18px; font-size: 14px;">
                    <span>🎯</span>
                    <span>Пройти диагностику</span>
                </a>
            </div>
        </div>
    @endif

    {{-- ═══ 3. Интерактивные смарт-метрики (4 карточки) ═══ --}}
    <div class="student-metrics-grid">
        {{-- Карточка 1: Моё расписание --}}
        <div class="student-metric-card">
            <div>
                <div class="student-metric-header">
                    <span class="student-metric-label" style="color:var(--stu-accent);">Расписание</span>
                    <div class="student-metric-icon-wrap" style="background:var(--stu-accent-light); color:var(--stu-accent);">
                        📅
                    </div>
                </div>
                <div class="student-metric-value">
                    {{ $scheduledLessonsCount ?? 0 }}
                </div>
                <div class="student-metric-subtext">
                    @if(($scheduledLessonsCount ?? 0) > 0)
                        <span>запланировано уроков</span>
                    @else
                        <span>Нет предстоящих занятий</span>
                    @endif
                </div>
            </div>
            <div>
                <a href="/admin/lessons" class="student-metric-action">
                    <span>Мои уроки</span>
                    <span>→</span>
                </a>
            </div>
        </div>

        {{-- Карточка 2: Цель и Баллы --}}
        <div class="student-metric-card">
            <div>
                <div class="student-metric-header">
                    <span class="student-metric-label" style="color:var(--stu-accent);">Траектория</span>
                    <div class="student-metric-icon-wrap" style="background:var(--stu-accent-light); color:var(--stu-accent);">
                        🎯
                    </div>
                </div>
                <div class="student-metric-value">
                    @if($primaryGoal)
                        {{ $latestSnapshot?->current_score ?? $primaryGoal->baseline_score ?? 0 }} ➔ {{ $primaryGoal->target_score ?? 100 }} б.
                    @else
                        100 б.
                    @endif
                </div>
                <div class="student-metric-subtext">
                    @if($primaryGoal)
                        <span>{{ $progressPercent ?? 0 }}% готовности к экзамену</span>
                    @else
                        <span>Стартовая цель ЦТ / ЦЭ</span>
                    @endif
                </div>
            </div>
            <div>
                <a href="/admin/diagnostic" class="student-metric-action">
                    <span>{{ $primaryGoal ? 'Смотреть срез знаний' : 'Заполнить цель' }}</span>
                    <span>→</span>
                </a>
            </div>
        </div>

        {{-- Карточка 3: Домашние задания --}}
        <div class="student-metric-card">
            <div>
                <div class="student-metric-header">
                    <span class="student-metric-label" style="color:#D97706;">Домашка</span>
                    <div class="student-metric-icon-wrap" style="background:rgba(245, 158, 11, 0.12); color:#D97706;">
                        📚
                    </div>
                </div>
                <div class="student-metric-value">
                    {{ $activeHomeworkCount }}
                </div>
                <div class="student-metric-subtext">
                    @if($activeHomeworkCount > 0)
                        <span>требуют выполнения</span>
                    @else
                        <span>Все задания сданы вовремя 👍</span>
                    @endif
                </div>
            </div>
            <div>
                <a href="/admin/homework" class="student-metric-action">
                    <span>Перейти к заданиям</span>
                    <span>→</span>
                </a>
            </div>
        </div>

        {{-- Карточка 4: Пройдено уроков --}}
        <div class="student-metric-card">
            <div>
                <div class="student-metric-header">
                    <span class="student-metric-label" style="color:#7C3AED;">Опыт</span>
                    <div class="student-metric-icon-wrap" style="background:rgba(124, 58, 237, 0.12); color:#7C3AED;">
                        🏆
                    </div>
                </div>
                <div class="student-metric-value">
                    {{ $completedCount }}
                </div>
                <div class="student-metric-subtext">
                    <span>уроков проведено ({{ $completedHours }} ч)</span>
                </div>
            </div>
            <div>
                <a href="/admin/lessons" class="student-metric-action">
                    <span>Расписание и история</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ═══ 4. Траектория подготовки и слабые темы ═══ --}}
    <div class="student-progress-card">
        <div class="student-progress-header">
            <div class="student-progress-title-wrap">
                <h2>Учебная траектория и прогресс</h2>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: var(--stu-text-muted);">
                    @if($primaryGoal)
                        Предмет: <strong>{{ $primaryGoal->subject }}</strong> · Экзамен: <strong>{{ $primaryGoal->exam_type }}</strong>
                    @else
                        Индивидуальный мониторинг готовности к экзаменам
                    @endif
                </p>
            </div>

            @if($primaryGoal)
                <div style="font-size: 13px; font-weight: 800; color: var(--stu-accent);">
                    Готовность: {{ $progressPercent ?? 0 }}%
                </div>
            @endif
        </div>

        @if($primaryGoal)
            {{-- Градиентный анимированный прогресс-бар --}}
            <div class="student-progress-bar-outer">
                <div class="student-progress-bar-fill" style="width: {{ max(4, min(100, $progressPercent ?? 0)) }}%;"></div>
            </div>

            {{-- Слабые темы в фокусе (Skill Gaps) --}}
            @if($weakTopics->isNotEmpty())
                <div style="margin-top: 18px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--stu-text-muted);">
                        Слабые темы в фокусе (проработайте на ближайших уроках):
                    </span>
                    <div class="student-gaps-list">
                        @foreach($weakTopics as $gap)
                            <span class="student-gap-pill" title="Тема требует отработки">
                                <span>⚠️</span>
                                <span>{{ $gap->topic_name ?? $gap->topic }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Рекомендация от платформы / ИИ --}}
            <div style="margin-top: 16px; padding: 14px 16px; border-radius: 12px; background: var(--stu-surface-subtle); border: 1px solid var(--stu-card-border); font-size: 0.88rem; display: flex; align-items: flex-start; gap: 10px;">
                <span style="font-size: 18px; line-height: 1;">💡</span>
                <div style="color: var(--stu-text-main); line-height: 1.45;">
                    <strong>Рекомендация по траектории:</strong> {{ $nextStep }}
                </div>
            </div>
        @else
            <div style="text-align: center; padding: 20px 0;">
                <p style="margin: 0 0 12px; font-size: 0.9rem; color: var(--stu-text-muted);">
                    Траектория подготовки автоматически формируется после стартовой диагностики или записи на урок с репетитором.
                </p>
                <a href="/admin/diagnostic" class="student-chip-btn student-chip-btn--primary">
                    <span>🎯</span>
                    <span>Пройти стартовую диагностику (2 мин)</span>
                </a>
            </div>
        @endif
    </div>
</div>
