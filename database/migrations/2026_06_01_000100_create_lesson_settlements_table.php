<?php

declare(strict_types=1);

use App\Models\Lesson;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the lesson_settlements table which records the fact of a
     * per-lesson settlement (or refund) on top of a single package
     * Transaction. This separates "fact of payment" (one Transaction per
     * package) from "fact of service delivery" (one LessonSettlement per
     * lesson) and is the source of truth for settlement idempotency.
     *
     * The same migration backfills audit records for legacy data so that the
     * new per-lesson settlement logic correctly sees already-settled packages.
     */
    public function up(): void
    {
        Schema::create('lesson_settlements', function (Blueprint $table): void {
            $table->id();

            // One settlement per lesson. UNIQUE guarantees idempotency and a
            // cascade delete keeps audit rows in sync with their lesson.
            $table->foreignId('lesson_id')
                ->unique()
                ->constrained('lessons')
                ->cascadeOnDelete();

            // The package/single Transaction this settlement belongs to.
            // RESTRICT: never silently drop the audit trail with a payment.
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->restrictOnDelete();

            $table->decimal('net_share', 10, 2)->default(0);
            $table->decimal('gross_share', 10, 2)->default(0);

            $table->timestamp('settled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // json works on sqlite, mysql and pgsql.
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['transaction_id', 'settled_at']);
            $table->index(['transaction_id', 'refunded_at']);
        });

        $this->backfill();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_settlements');
    }

    /**
     * Backfill audit records for existing settled packages and completed
     * single lessons. This only writes audit rows — it never touches
     * tutor_balance or student_balance.
     *
     * Transactions are loaded through Eloquent and filtered in PHP so the
     * backfill is portable across PostgreSQL (prod) and SQLite (tests)
     * without relying on database-specific JSON path operators.
     */
    private function backfill(): void
    {
        DB::transaction(function (): void {
            Transaction::query()
                ->whereIn('status', [
                    Transaction::STATUS_SUCCESS,
                    Transaction::STATUS_PARTIALLY_REFUNDED,
                ])
                ->orderBy('id')
                ->chunkById(200, function ($transactions): void {
                    foreach ($transactions as $transaction) {
                        $this->backfillTransaction($transaction);
                    }
                });
        });
    }

    private function backfillTransaction(Transaction $transaction): void
    {
        $gateway = $transaction->gateway_response ?? [];
        $packageLessons = (int) ($gateway['package_lessons'] ?? 0);
        $isSettled = ($gateway['settled'] ?? false) === true;

        // Legacy settled package: create exactly one LessonSettlement for the
        // parent lesson on the full transaction amount so that
        // Σ already_settled = transaction.net_amount and partial re-settle of
        // child lessons is blocked.
        if ($packageLessons > 1 && $isSettled) {
            $settledAt = isset($gateway['settled_at'])
                ? Carbon::parse($gateway['settled_at'])
                : Carbon::now('UTC');

            $this->insertSettlement(
                lessonId: (int) $transaction->lesson_id,
                transactionId: (int) $transaction->id,
                netShare: (string) $transaction->net_amount,
                grossShare: (string) $transaction->amount,
                settledAt: $settledAt,
                meta: [
                    'backfill' => true,
                    'reason' => 'legacy_settled_package',
                    'package_lessons' => $packageLessons,
                ],
            );

            return;
        }

        // Completed single lesson without a LessonSettlement: create one on the
        // full transaction amount for consistency.
        $lesson = $transaction->lesson;

        if ($lesson === null) {
            return;
        }

        $isSingle = $packageLessons <= 1
            && $lesson->package_parent_lesson_id === null;

        if ($isSingle && $lesson->status === Lesson::STATUS_COMPLETED) {
            $this->insertSettlement(
                lessonId: (int) $lesson->id,
                transactionId: (int) $transaction->id,
                netShare: (string) $transaction->net_amount,
                grossShare: (string) $transaction->amount,
                settledAt: Carbon::now('UTC'),
                meta: [
                    'backfill' => true,
                    'reason' => 'completed_single_lesson',
                ],
            );
        }
    }

    /**
     * Insert a settlement audit row, skipping if one already exists for the
     * lesson (idempotent across repeated migration runs).
     *
     * @param  array<string, mixed>  $meta
     */
    private function insertSettlement(
        int $lessonId,
        int $transactionId,
        string $netShare,
        string $grossShare,
        Carbon $settledAt,
        array $meta,
    ): void {
        $exists = DB::table('lesson_settlements')
            ->where('lesson_id', $lessonId)
            ->exists();

        if ($exists) {
            return;
        }

        $now = Carbon::now('UTC');

        DB::table('lesson_settlements')->insert([
            'lesson_id' => $lessonId,
            'transaction_id' => $transactionId,
            'net_share' => $netShare,
            'gross_share' => $grossShare,
            'settled_at' => $settledAt,
            'refunded_at' => null,
            'meta' => json_encode($meta),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
