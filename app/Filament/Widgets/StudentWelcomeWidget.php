<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\ProgressSnapshot;
use App\Models\StudentBalance;
use App\Models\StudentGoal;
use App\Models\Transaction;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class StudentWelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.student-welcome-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user && in_array($user->role, ['student', 'parent'], true);
    }

    protected function getViewData(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $lessonsRelation = $user->role === 'parent' ? $user->parentLessons() : $user->studentLessons();

        $completedTokens = (clone $lessonsRelation)
            ->where('status', Lesson::STATUS_COMPLETED)
            ->count();

        $scheduledTokens = (clone $lessonsRelation)
            ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_PENDING])
            ->where('start_time', '>=', now())
            ->count();

        $studentBalance = StudentBalance::query()->firstWhere('user_id', $user->id);
        $availableBalance = (float) ($studentBalance?->available_amount ?? 0);

        $upcomingUnpaidLessons = (clone $lessonsRelation)
            ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_PENDING])
            ->where('payment_status', Lesson::PAYMENT_UNPAID)
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->get();

        $upcomingPaidLessons = (clone $lessonsRelation)
            ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_PENDING])
            ->where('payment_status', Lesson::PAYMENT_PAID)
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->get();

        $reservedForUpcoming = (float) $upcomingUnpaidLessons
            ->sum(fn (Lesson $lesson): float => (float) $lesson->price);

        $heldFromDeals = (float) Transaction::query()
            ->where('user_id', $user->id)
            ->where('status', Transaction::STATUS_SUCCESS)
            ->whereHas('lesson', function ($query): void {
                $query
                    ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])
                    ->where('payment_status', Lesson::PAYMENT_PAID)
                    ->where('end_time', '>', now('UTC'));
            })
            ->get()
            ->filter(fn (Transaction $transaction): bool => ($transaction->gateway_response['settled'] ?? false) !== true)
            ->sum(fn (Transaction $transaction): float => (float) $transaction->amount);

        $heldForBookedLessons = max((float) ($studentBalance?->locked_amount ?? 0), $heldFromDeals);

        $freeBalance = max($availableBalance - $reservedForUpcoming, 0);
        $payableByBalanceCount = $upcomingUnpaidLessons
            ->reduce(function (array $state, Lesson $lesson): array {
                if ($state['left'] >= (float) $lesson->price) {
                    $state['left'] -= (float) $lesson->price;
                    $state['count']++;
                }

                return $state;
            }, ['left' => $availableBalance, 'count' => 0])['count'];

        $activeGoals = StudentGoal::query()
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->with(['progressSnapshots', 'skillGaps'])
            ->orderByDesc('latest_diagnostic_at')
            ->orderByDesc('id')
            ->get();

        $diagnosticPendingCount = $activeGoals
            ->filter(fn (StudentGoal $goal): bool => $goal->latest_diagnostic_at === null)
            ->count();

        $activeHomeworkCount = HomeworkAssignment::query()
            ->where('student_id', $user->id)
            ->where('status', 'assigned')
            ->count();

        $completedHomeworkCount = HomeworkAssignment::query()
            ->where('student_id', $user->id)
            ->where('status', 'completed')
            ->count();

        /** @var StudentGoal|null $primaryGoal */
        $primaryGoal = $activeGoals->first();
        /** @var ProgressSnapshot|null $latestSnapshot */
        $latestSnapshot = $primaryGoal?->progressSnapshots->sortByDesc('snapshot_date')->first();
        $activeSkillGapsCount = $primaryGoal
            ? $primaryGoal->skillGaps->where('status', 'open')->count()
            : 0;
        $progressPercent = $this->resolveProgressPercent(
            currentScore: $latestSnapshot?->current_score ?? $primaryGoal?->current_score,
            targetScore: $latestSnapshot?->target_score ?? $primaryGoal?->target_score,
        );
        $nextStep = $this->resolveNextStep($primaryGoal, $latestSnapshot, $activeSkillGapsCount);

        return [
            'user' => $user,
            'completedCount' => $completedTokens,
            'scheduledCount' => $scheduledTokens,
            'availableBalance' => $availableBalance,
            'heldForBookedLessons' => $heldForBookedLessons,
            'reservedForUpcoming' => $reservedForUpcoming,
            'freeBalance' => $freeBalance,
            'upcomingUnpaidLessons' => $upcomingUnpaidLessons->take(3),
            'upcomingPaidLessons' => $upcomingPaidLessons->take(3),
            'upcomingUnpaidCount' => $upcomingUnpaidLessons->count(),
            'upcomingPaidCount' => $upcomingPaidLessons->count(),
            'payableByBalanceCount' => $payableByBalanceCount,
            'activeGoalsCount' => $activeGoals->count(),
            'diagnosticPendingCount' => $diagnosticPendingCount,
            'activeHomeworkCount' => $activeHomeworkCount,
            'completedHomeworkCount' => $completedHomeworkCount,
            'primaryGoal' => $primaryGoal,
            'latestSnapshot' => $latestSnapshot,
            'activeSkillGapsCount' => $activeSkillGapsCount,
            'progressPercent' => $progressPercent,
            'nextStep' => $nextStep,
        ];
    }

    private function resolveProgressPercent(?int $currentScore, ?int $targetScore): ?int
    {
        if ($currentScore === null || $targetScore === null || $targetScore <= 0) {
            return null;
        }

        return max(0, min((int) round(($currentScore / $targetScore) * 100), 100));
    }

    private function resolveNextStep(?StudentGoal $goal, ?ProgressSnapshot $snapshot, int $activeSkillGapsCount): string
    {
        if ($goal === null) {
            return 'Оплатите первое занятие, чтобы открыть траекторию подготовки.';
        }

        if ($goal->latest_diagnostic_at === null) {
            return 'Заполните стартовую диагностику, чтобы платформа увидела baseline и слабые темы.';
        }

        if ($activeSkillGapsCount > 0) {
            return "В приоритете {$activeSkillGapsCount} слабых тем. Сфокусируйтесь на них в ближайшем цикле занятий.";
        }

        if ($snapshot?->summary) {
            return (string) $snapshot->summary;
        }

        return 'Продолжайте занятия и обновляйте диагностику, чтобы видеть рост результата по траектории.';
    }
}
