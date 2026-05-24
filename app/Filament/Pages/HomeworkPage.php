<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\HomeworkAssignment;
use App\Services\HomeworkService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class HomeworkPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $slug = 'homework';

    protected static string $view = 'filament.pages.homework-page';

    protected static ?string $navigationLabel = 'Домашка';

    protected static ?string $title = 'Домашние задания';

    protected static ?int $navigationSort = 16;

    public ?int $selectedAssignmentId = null;

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

        $count = HomeworkAssignment::query()
            ->where('student_id', $user->id)
            ->where('status', 'assigned')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public function mount(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['student', 'parent'], true), 403);

        $this->selectedAssignmentId = $this->assignments()->first()?->id;
    }

    public function selectAssignment(int $assignmentId): void
    {
        if (! $this->assignments()->whereKey($assignmentId)->exists()) {
            return;
        }

        $this->selectedAssignmentId = $assignmentId;
    }

    public function completeAssignment(int $assignmentId): void
    {
        $assignment = $this->assignments()->whereKey($assignmentId)->first();

        if (! $assignment) {
            return;
        }

        app(HomeworkService::class)->markCompleted($assignment, (int) auth()->id());

        Notification::make()
            ->title('Домашка отмечена выполненной')
            ->body('Преподаватель и платформа увидят, что вы закрыли это задание.')
            ->success()
            ->send();
    }

    public function getViewData(): array
    {
        $assignments = $this->assignments()->get();
        $selectedAssignment = $this->selectedAssignmentId
            ? $assignments->firstWhere('id', $this->selectedAssignmentId)
            : $assignments->first();

        return [
            'assignments' => $assignments,
            'selectedAssignment' => $selectedAssignment,
            'assignedCount' => $assignments->where('status', 'assigned')->count(),
            'completedCount' => $assignments->where('status', 'completed')->count(),
        ];
    }

    private function assignments()
    {
        return HomeworkAssignment::query()
            ->with(['lesson.tutor', 'studentGoal'])
            ->where('student_id', auth()->id())
            ->latest('assigned_at')
            ->latest('id');
    }
}
