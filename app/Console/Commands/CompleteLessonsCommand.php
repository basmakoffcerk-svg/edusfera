<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CompleteLessonsCommand extends Command
{
    protected $signature = 'lessons:complete';

    protected $description = 'Автоматически переводит завершившиеся уроки в статус completed';

    public function __construct(private readonly PaymentService $paymentService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! Schema::hasTable('lessons')) {
            $this->warn('Таблица lessons еще не создана.');

            return self::SUCCESS;
        }

        $lessons = Lesson::query()
            ->where('status', Lesson::STATUS_CONFIRMED)
            ->where('end_time', '<=', now('UTC'))
            ->get();

        $updated = 0;

        foreach ($lessons as $lesson) {
            $lesson->update(['status' => Lesson::STATUS_COMPLETED]);
            $this->paymentService->settleCompletedLesson($lesson);
            $updated++;
        }

        $this->info("Завершено уроков: {$updated}");

        return self::SUCCESS;
    }
}
