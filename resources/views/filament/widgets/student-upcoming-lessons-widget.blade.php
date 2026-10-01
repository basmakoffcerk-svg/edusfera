<x-filament-widgets::widget>
    <div class="student-card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--stu-text-main); display: flex; align-items: center; gap: 8px;">
                    <span>📅</span>
                    <span>Ближайшие уроки</span>
                </h3>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: var(--stu-text-muted);">
                    Расписание занятий в интерактивном классе
                </p>
            </div>

            @if($upcomingLessons->isNotEmpty())
                <a href="/admin/lessons" class="student-chip-btn" style="font-size: 12px; padding: 4px 10px;">
                    Все уроки →
                </a>
            @endif
        </div>

        @if ($upcomingLessons->isEmpty())
            <div style="text-align: center; padding: 32px 16px; border: 1px dashed var(--stu-card-border); border-radius: 14px; background: var(--stu-surface-subtle);">
                <div style="font-size: 32px; margin-bottom: 8px;">🗓️</div>
                <h4 style="margin: 0 0 6px; font-size: 1rem; font-weight: 800; color: var(--stu-text-main);">
                    Пока нет запланированных уроков
                </h4>
                <p style="margin: 0 auto 16px; font-size: 0.85rem; color: var(--stu-text-muted); max-width: 320px;">
                    Выберите преподавателя в каталоге и забронируйте удобное время.
                </p>
                <a href="/tutors" class="student-chip-btn student-chip-btn--primary">
                    <span>🔍</span>
                    <span>Найти репетитора</span>
                </a>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($upcomingLessons as $lesson)
                    @php
                        $tutor = $lesson->tutor;
                        $profile = $tutor?->tutorProfile;
                        $tutorName = $tutor?->name ?? 'Преподаватель';
                        $tutorInitial = mb_strtoupper(mb_substr($tutorName, 0, 1));
                        $classroomUrl = route('classroom.show', $lesson);
                        $isLive = now()->between($lesson->start_time->clone()->subMinutes(15), $lesson->end_time);
                    @endphp

                    <div class="student-list-item">
                        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                            <div class="student-tutor-avatar-lg" style="width: 42px; height: 42px; font-size: 16px; border-radius: 12px;">
                                {{ $tutorInitial }}
                            </div>
                            <div style="min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span style="font-size: 0.95rem; font-weight: 800; color: var(--stu-text-main);">
                                        {{ $tutorName }}
                                    </span>
                                    @if($isLive)
                                        <span class="student-status-badge student-status-badge--paid" style="background:#10B981; color:#FFFFFF;">
                                            ● В эфире
                                        </span>
                                    @elseif($lesson->status === \App\Models\Lesson::STATUS_CONFIRMED)
                                        <span class="student-status-badge student-status-badge--paid">
                                            ✓ Подтверждён
                                        </span>
                                    @else
                                        <span class="student-status-badge" style="background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.25);">
                                            Ожидает подтверждения
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size: 0.82rem; color: var(--stu-text-muted); margin-top: 2px;">
                                    <span>{{ $lesson->start_time->translatedFormat('j M (D), H:i') }}</span>
                                    <span>•</span>
                                    <span>{{ $lesson->subject ?? 'Урок' }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            @if($lesson->status === \App\Models\Lesson::STATUS_CONFIRMED)
                                @if($isLive)
                                    <a href="{{ $classroomUrl }}" target="_blank" class="student-chip-btn student-chip-btn--primary" style="padding: 8px 14px; font-weight: 800;">
                                        <span>🚀 Войти</span>
                                    </a>
                                @else
                                    <a href="{{ $classroomUrl }}" class="student-chip-btn" style="padding: 6px 12px;">
                                        <span>Класс</span>
                                    </a>
                                @endif
                            @else
                                <span class="student-chip-btn" style="padding: 6px 10px; opacity: 0.75; cursor: default; background: rgba(245, 158, 11, 0.08); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.2);">
                                    <span>⏳ Ожидает</span>
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
