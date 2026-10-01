<x-filament-widgets::widget>
    <div class="student-card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--stu-text-main); display: flex; align-items: center; gap: 8px;">
                    <span>👨‍🏫</span>
                    <span>Мои преподаватели</span>
                </h3>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: var(--stu-text-muted);">
                    Репетиторы, с которыми вы занимаетесь на платформе
                </p>
            </div>

            <a href="/tutors" class="student-chip-btn" style="font-size: 12px; padding: 4px 10px;">
                <span>+ Добавить</span>
            </a>
        </div>

        @if ($tutors->isEmpty())
            <div style="text-align: center; padding: 32px 16px; border: 1px dashed var(--stu-card-border); border-radius: 14px; background: var(--stu-surface-subtle);">
                <div style="font-size: 32px; margin-bottom: 8px;">🎓</div>
                <h4 style="margin: 0 0 6px; font-size: 1rem; font-weight: 800; color: var(--stu-text-main);">
                    У вас пока нет репетиторов
                </h4>
                <p style="margin: 0 auto 16px; font-size: 0.85rem; color: var(--stu-text-muted); max-width: 320px;">
                    Перейдите в каталог, чтобы подобрать проверенного преподавателя под вашу цель.
                </p>
                <a href="/tutors" class="student-chip-btn student-chip-btn--primary">
                    <span>🔍</span>
                    <span>Перейти в каталог репетиторов</span>
                </a>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($tutors as $tutor)
                    @php
                        $profile = $tutor->tutorProfile;
                        $tutorName = $tutor->name;
                        $tutorInitial = mb_strtoupper(mb_substr($tutorName, 0, 1));
                        $subjects = !empty($profile?->subjects) ? (is_array($profile->subjects) ? implode(', ', $profile->subjects) : (string)$profile->subjects) : 'Репетитор';
                    @endphp

                    <div class="student-list-item">
                        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                            <div class="student-tutor-avatar-lg" style="width: 42px; height: 42px; font-size: 16px; border-radius: 12px; background: linear-gradient(135deg, #10B981, #059669);">
                                {{ $tutorInitial }}
                            </div>
                            <div style="min-width: 0;">
                                <div style="font-size: 0.95rem; font-weight: 800; color: var(--stu-text-main); line-height: 1.2;">
                                    {{ $tutorName }}
                                </div>
                                <div style="font-size: 0.82rem; color: var(--stu-text-muted); margin-top: 2px;">
                                    {{ $subjects }}
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="/admin/messages" class="student-chip-btn" style="padding: 6px 10px; font-size: 12px;" title="Написать сообщение">
                                <span>💬 Чат</span>
                            </a>
                            <a href="/admin/lessons" class="student-chip-btn" style="padding: 6px 10px; font-size: 12px;" title="История и расписание">
                                <span>Занятия</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
