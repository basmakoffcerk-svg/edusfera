<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\StudentGoal;
use App\Services\DiagnosticService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class DiagnosticPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $slug = 'diagnostic';

    protected static string $view = 'filament.pages.diagnostic-page';

    protected static ?string $navigationLabel = 'Диагностика';

    protected static ?string $title = 'Стартовая диагностика';

    protected static ?int $navigationSort = 15;

    public ?int $selectedGoalId = null;

    public ?string $examType = null;

    public ?int $currentScore = null;

    public ?int $targetScore = null;

    public ?string $examDate = null;

    /**
     * @var array<int, string>
     */
    public array $weakTopics = [];

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['student', 'parent'], true), 403);

        $this->selectedGoalId = $this->goals()->first()?->id;

        if ($this->selectedGoalId !== null) {
            $this->hydrateFromGoal();
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, ['student', 'parent'], true);
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['student', 'parent'], true);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Подготовка';
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, ['student', 'parent'], true)) {
            return null;
        }

        $count = StudentGoal::query()
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->whereNull('latest_diagnostic_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public function updatedSelectedGoalId(): void
    {
        $this->hydrateFromGoal();
    }

    public function save(): void
    {
        $goal = $this->selectedGoal();

        if (! $goal) {
            return;
        }

        validator(
            [
                'selectedGoalId' => $this->selectedGoalId,
                'examType' => $this->examType,
                'currentScore' => $this->currentScore,
                'targetScore' => $this->targetScore,
                'examDate' => $this->examDate,
                'weakTopics' => $this->weakTopics,
                'notes' => $this->notes,
            ],
            [
                'selectedGoalId' => ['required', 'integer'],
                'examType' => ['required', 'string', 'max:32'],
                'currentScore' => ['nullable', 'integer', 'min:0', 'max:100'],
                'targetScore' => ['nullable', 'integer', 'min:0', 'max:100'],
                'examDate' => ['nullable', 'date'],
                'weakTopics' => ['required', 'array', 'min:1'],
                'weakTopics.*' => ['string', 'max:160'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            [
                'weakTopics.min' => 'Выберите хотя бы одну зону, которую нужно подтянуть.',
            ],
        )->after(function ($validator): void {
            if ($this->currentScore !== null && $this->targetScore !== null && $this->targetScore < $this->currentScore) {
                $validator->errors()->add('targetScore', 'Целевой балл должен быть не ниже текущего.');
            }
        })->validate();

        $goal->update([
            'exam_type' => (string) $this->examType,
        ]);

        app(DiagnosticService::class)->recordBaseline(
            goal: $goal->fresh(),
            studentId: (int) auth()->id(),
            currentScore: $this->currentScore,
            targetScore: $this->targetScore,
            examDate: $this->examDate,
            weakTopics: $this->weakTopics,
            notes: trim($this->notes) !== '' ? trim($this->notes) : null,
        );

        Notification::make()
            ->title('Диагностика сохранена')
            ->body('Базовый уровень, слабые темы и стартовый progress snapshot уже записаны.')
            ->success()
            ->send();
    }

    public function getViewData(): array
    {
        $goal = $this->selectedGoal();

        return [
            'goals' => $this->goals()->get(),
            'selectedGoal' => $goal,
            'topicOptions' => $this->topicOptions($goal?->subject),
        ];
    }

    private function goals()
    {
        return StudentGoal::query()
            ->where('student_id', auth()->id())
            ->where('status', 'active')
            ->orderByDesc('id');
    }

    private function selectedGoal(): ?StudentGoal
    {
        if ($this->selectedGoalId === null) {
            return null;
        }

        return $this->goals()->whereKey($this->selectedGoalId)->first();
    }

    private function hydrateFromGoal(): void
    {
        $goal = $this->selectedGoal();

        if (! $goal) {
            return;
        }

        $this->examType = $goal->exam_type;
        $this->currentScore = $goal->current_score;
        $this->targetScore = $goal->target_score;
        $this->examDate = $goal->exam_date?->format('Y-m-d');
        $this->weakTopics = $goal->skillGaps()
            ->where('status', 'open')
            ->pluck('topic')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function topicOptions(?string $subject): array
    {
        return match ($subject) {
            'Белорусский язык' => [
                'Орфография',
                'Лексика и фразеология',
                'Морфология',
                'Синтаксис',
                'Пунктуация',
                'Тестовые ловушки ЦЭ/ЦТ',
            ],
            'Русский язык' => [
                'Орфография',
                'Пунктуация',
                'Синтаксис',
                'Сочинение и аргументация',
                'Тестовые формулировки',
            ],
            'Математика' => [
                'Алгебра',
                'Геометрия',
                'Уравнения и неравенства',
                'Текстовые задачи',
                'Тестовая стратегия',
            ],
            default => [
                'Теория',
                'Практика заданий',
                'Темп решения',
                'Пробелы в базе',
                'Экзаменационная стратегия',
            ],
        };
    }
}
