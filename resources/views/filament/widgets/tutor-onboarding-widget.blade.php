@if (! $hasProfile)
    {{-- Анкеты ещё нет: приоритетный онбординг-блок с одним главным действием --}}
    <div class="tutor-card tutor-onb-welcome" style="background:#FAF8FF; border-color:#ECE5FB;">
        <span class="tutor-card-label">Последний шаг до учеников</span>
        <p class="tutor-onb-title" style="font-size:1.375rem">
            Создайте анкету — и мы начнём приводить учеников
        </p>
        <p class="tutor-onb-lead">
            Поиск клиентов, безопасные платежи и отчётность — на платформе. Вам нужно рассказать о себе и предметах.
        </p>
        <div class="tutor-cta-row">
            <a href="/admin/tutor-profiles/create" class="tutor-cta tutor-cta--primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Заполнить анкету
            </a>
        </div>
    </div>
@else
    @php
        $undoneSteps = collect($steps)->reject(fn ($step) => $step['done']);
        $doneSteps = collect($steps)->filter(fn ($step) => $step['done']);
    @endphp

    <div class="tutor-card">
        <div class="tutor-onb-head">
            <div>
                <span class="tutor-card-label">Анкета репетитора · {{ $progress }}%</span>
                <p class="tutor-onb-title">
                    @if ($progress === 100)
                        Анкета готова — вы в каталоге 🚀
                    @else
                        До первых учеников осталось {{ $undoneSteps->count() }}
                        {{ trans_choice('шаг|шага|шагов', $undoneSteps->count()) }}
                    @endif
                </p>
                @if ($rank)
                    <p class="tutor-onb-rank">{{ $rank }}-е место в каталоге · чем полнее анкета, тем больше обращений</p>
                @endif
            </div>
            <a href="{{ route('filament.admin.resources.tutor-profiles.edit', ['record' => $profileId]) }}" class="tutor-cta tutor-cta--ghost">
                Редактировать
            </a>
            <div class="tutor-progress">
                <div style="width: {{ $progress }}%;"></div>
            </div>
        </div>

        {{-- Шаги: сначала незавершённые, выполненные приглушены --}}
        <div class="tutor-check-list">
            @foreach ($undoneSteps->merge($doneSteps) as $step)
                @if (! $step['done'] && ! empty($step['action']))
                    <a href="{{ $step['action'] }}" class="tutor-check-row">
                        <span class="tutor-check-icon tutor-check-icon--todo">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                        </span>
                        <span class="tutor-check-label">{{ $step['label'] }}</span>
                        <svg class="tutor-check-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </a>
                @else
                    <div class="tutor-check-row tutor-check-row--done">
                        <span class="tutor-check-icon {{ $step['done'] ? 'tutor-check-icon--done' : 'tutor-check-icon--todo' }}">
                            @if ($step['done'])
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @endif
                        </span>
                        <span class="tutor-check-label" style="font-weight:600">
                            {{ $step['label'] }}
                            @if (! $step['done'])
                                <span class="tutor-check-note">{{ $step['action_label'] }}</span>
                            @endif
                        </span>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif
