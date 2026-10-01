<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use App\Models\Lesson;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TutorNpdPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $slug = 'tutor-npd';

    protected static string $view = 'filament.pages.tutor-npd-page';

    protected static ?string $navigationLabel = 'Налог на профдоход (НПД)';

    protected static ?string $title = 'Кабинет плательщика НПД (МНС РБ)';

    protected static ?int $navigationSort = 1;

    public string $activeTab = 'portal';

    public bool $applyFirstTimeDeduction = true;

    public ?int $selectedLessonId = null;

    public ?string $receiptNumberInput = null;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isTutor() ?? false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isTutor() || $user->isAdmin());
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Преподавание';
    }

    public function mount(): void
    {
        $user = auth()->user();
        $this->applyFirstTimeDeduction = (bool) session()->get('npd_first_time_deduction_'.($user?->id ?? 0), true);
    }

    public function canUseNpd(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return app(SubscriptionFeatureGate::class)->canUseNpd($user);
    }

    private function notifyUpgradeRequired(): void
    {
        Notification::make()
            ->title('Доступно на тарифе «Про»')
            ->body('Автоматическое формирование и учет чеков НПД МНС РБ доступны на тарифе «Про», «Премиум» или в течение бесплатного пробного периода.')
            ->warning()
            ->actions([
                Action::make('upgrade')
                    ->button()
                    ->label('Подключить «Про»')
                    ->url(route('filament.admin.pages.tutor-subscription-page')),
            ])
            ->send();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function toggleDeduction(): void
    {
        if (! $this->canUseNpd()) {
            $this->notifyUpgradeRequired();

            return;
        }

        $this->applyFirstTimeDeduction = ! $this->applyFirstTimeDeduction;
        session()->put('npd_first_time_deduction_'.(auth()->id() ?? 0), $this->applyFirstTimeDeduction);

        Notification::make()
            ->title($this->applyFirstTimeDeduction ? 'Льготный вычет 2 000 руб. включён' : 'Льготный вычет отключён')
            ->body($this->applyFirstTimeDeduction ? 'Расчёт учитывает налоговую льготу для впервые зарегистрированных плательщиков НПД.' : 'Расчёт производится по базовой ставке 10%.')
            ->info()
            ->send();
    }

    public function openReceiptModal(int $lessonId): void
    {
        if (! $this->canUseNpd()) {
            $this->notifyUpgradeRequired();

            return;
        }

        $this->selectedLessonId = $lessonId;
        $lesson = Lesson::query()->where('tutor_id', auth()->id())->find($lessonId);
        $this->receiptNumberInput = $lesson?->npd_receipt_number;
    }

    public function closeReceiptModal(): void
    {
        $this->selectedLessonId = null;
        $this->receiptNumberInput = null;
    }

    public function markReceiptIssued(int $lessonId): void
    {
        if (! $this->canUseNpd()) {
            $this->notifyUpgradeRequired();

            return;
        }

        $lesson = Lesson::query()->where('tutor_id', auth()->id())->findOrFail($lessonId);

        $number = trim((string) $this->receiptNumberInput);
        $lesson->update([
            'npd_receipt_issued_at' => now(),
            'npd_receipt_number' => $number !== '' ? $number : ('МНС-'.now()->format('Ymd').'-'.$lesson->id),
        ]);

        $this->closeReceiptModal();

        Notification::make()
            ->title('Чек отмечен как выбитый в МНС')
            ->success()
            ->send();
    }

    public function unmarkReceiptIssued(int $lessonId): void
    {
        if (! $this->canUseNpd()) {
            $this->notifyUpgradeRequired();

            return;
        }

        $lesson = Lesson::query()->where('tutor_id', auth()->id())->findOrFail($lessonId);

        $lesson->update([
            'npd_receipt_issued_at' => null,
            'npd_receipt_number' => null,
        ]);

        Notification::make()
            ->title('Статус чека сброшен')
            ->info()
            ->send();
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth()->utc();
        $endOfMonth = $now->copy()->endOfMonth()->utc();
        $startOfYear = $now->copy()->startOfYear()->utc();

        // Уроки репетитора: завершенные или оплаченные
        $lessonsQuery = Lesson::query()
            ->with(['student', 'parent'])
            ->where('tutor_id', $user->id)
            ->where(function ($query) {
                $query->where('status', Lesson::STATUS_COMPLETED)
                    ->orWhere('payment_status', Lesson::PAYMENT_PAID);
            });

        $monthGross = (float) (clone $lessonsQuery)
            ->where('start_time', '>=', $startOfMonth)
            ->where('start_time', '<=', $endOfMonth)
            ->sum('price');

        $yearGross = (float) (clone $lessonsQuery)
            ->where('start_time', '>=', $startOfYear)
            ->where('start_time', '<=', $endOfMonth)
            ->sum('price');

        $lessons = (clone $lessonsQuery)
            ->latest('start_time')
            ->limit(50)
            ->get();

        $receiptsIssuedCount = $lessons->whereNotNull('npd_receipt_issued_at')->count();
        $receiptsPendingCount = $lessons->whereNull('npd_receipt_issued_at')->count();

        // 2000 BYN deduction logic for Belarus NPD
        $deductionTotal = 2000.00;
        $deductionRemaining = max(0.00, $deductionTotal - $yearGross);
        $deductionUsed = min($deductionTotal, $yearGross);

        if ($this->applyFirstTimeDeduction) {
            // Если вычет применяется: облагается только превышение 2000 руб.
            $taxableIncomeMonth = max(0.00, $monthGross - $deductionRemaining);
            $taxDueMonth = round($taxableIncomeMonth * 0.10, 2);
            $taxSaved = round(min($monthGross, $deductionRemaining) * 0.10, 2);
        } else {
            $taxableIncomeMonth = $monthGross;
            $taxDueMonth = round($monthGross * 0.10, 2);
            $taxSaved = 0.00;
        }

        // Tax payment deadline: not later than the 22nd day of next month
        $nextMonth = $now->copy()->addMonth();
        $paymentDeadline = '22 '.$nextMonth->translatedFormat('F Y');
        $notificationDeadline = '10 '.$nextMonth->translatedFormat('F Y');

        $selectedLesson = $this->selectedLessonId
            ? $lessons->firstWhere('id', $this->selectedLessonId)
            : null;

        return [
            'user' => $user,
            'monthGross' => $monthGross,
            'yearGross' => $yearGross,
            'taxDueMonth' => $taxDueMonth,
            'taxSaved' => $taxSaved,
            'deductionRemaining' => $deductionRemaining,
            'deductionUsed' => $deductionUsed,
            'deductionTotal' => $deductionTotal,
            'paymentDeadline' => $paymentDeadline,
            'notificationDeadline' => $notificationDeadline,
            'lessons' => $lessons,
            'receiptsIssuedCount' => $receiptsIssuedCount,
            'receiptsPendingCount' => $receiptsPendingCount,
            'selectedLesson' => $selectedLesson,
            'canUseNpd' => $this->canUseNpd(),
        ];
    }
}
