<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\StudentBalance;
use App\Models\Transaction;
use App\Models\TutorBalance;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function bootstrapPaidPack4(User $tutor, User $student): array
    {
        Notification::fake();

        $now = CarbonImmutable::now('UTC');

        $parent = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->subHours(2),
            'end_time' => $now->subHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'package_code' => 'pack_4',
            'package_lessons' => 4,
            'package_lessons_remaining' => 4,
            'package_total' => '152.00',
            'package_discount' => '8.00',
            'platform_commission' => '0.00',
            'net_amount' => '148.36',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => $now->addMinutes(15),
        ]);

        $children = [];
        for ($i = 0; $i < 3; $i++) {
            $children[] = Lesson::query()->forceCreate([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'start_time' => $now->subHours(2),
                'end_time' => $now->subHour(),
                'duration_minutes' => 60,
                'price' => '40.00',
                'package_code' => 'pack_4',
                'package_lessons' => 1,
                'package_parent_lesson_id' => $parent->id,
                'package_discount' => '0.00',
                'platform_commission' => '0.00',
                'net_amount' => '0.00',
                'status' => Lesson::STATUS_PENDING,
                'payment_status' => Lesson::PAYMENT_UNPAID,
                'payment_lock_expires_at' => $now->addMinutes(15),
            ]);
        }

        $transaction = app(PaymentService::class)->processPayment(
            lessonId: $parent->id,
            userId: $student->id,
            paymentMethod: 'card',
            rememberPaymentMethod: false,
            useWalletBalance: false,
        );

        return [
            'parent' => $parent->refresh(),
            'children' => array_map(fn (Lesson $l): Lesson => $l->refresh(), $children),
            'transaction' => $transaction,
        ];
    }

    public function test_command_settles_all_package_lessons_after_completion(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'phone' => '+375295000001']);
        $tutor->tutorProfile()->create([
            'subjects' => ['Математика'],
            'audiences' => ['Подготовка к ЦЭ'],
            'price_per_hour' => '40.00',
            'experience_years' => 5,
            'legal_status' => 'self_employed',
            'bio' => 'Подготовка.',
            'is_verified' => true,
            'verification_status' => 'approved',
            'lesson_formats' => ['individual_online'],
        ]);
        $student = User::factory()->create(['role' => 'student', 'phone' => '+375295000002']);

        $ctx = $this->bootstrapPaidPack4($tutor, $student);

        // Run complete lessons command
        $this->artisan('lessons:complete')
            ->expectsOutputToContain('Завершено уроков: 4')
            ->assertSuccessful();

        $balance = TutorBalance::query()->where('user_id', $tutor->id)->firstOrFail();
        $this->assertSame('148.36', (string) $balance->available_amount);
        $this->assertSame('0.00', (string) $balance->pending_amount);
    }

    public function test_command_handles_mixed_single_and_package_lessons(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'phone' => '+375295000003']);
        $tutor->tutorProfile()->create([
            'subjects' => ['Математика'],
            'audiences' => ['Подготовка к ЦЭ'],
            'price_per_hour' => '40.00',
            'experience_years' => 5,
            'legal_status' => 'self_employed',
            'bio' => 'Подготовка.',
            'is_verified' => true,
            'verification_status' => 'approved',
            'lesson_formats' => ['individual_online'],
        ]);
        $student = User::factory()->create(['role' => 'student', 'phone' => '+375295000004']);

        $now = CarbonImmutable::now('UTC');

        // Create paid single lesson that ended in the past
        $single = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->subHours(2),
            'end_time' => $now->subHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'package_code' => 'single',
            'package_lessons' => 1,
            'platform_commission' => '0.00',
            'net_amount' => '38.82',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => $now->addMinutes(15),
        ]);

        app(PaymentService::class)->processPayment($single->id, $student->id);

        // Create paid package pack_4
        $this->bootstrapPaidPack4($tutor, $student);

        // Run complete command
        $this->artisan('lessons:complete')
            ->expectsOutputToContain('Завершено уроков: 5')
            ->assertSuccessful();

        $balance = TutorBalance::query()->where('user_id', $tutor->id)->firstOrFail();
        // single net: 40 - 0 commission - 1.18 acq = 38.82
        // package net: 148.36
        // total: 38.82 + 148.36 = 187.18
        $this->assertSame('187.18', (string) $balance->available_amount);
    }

    public function test_backfill_creates_one_record_per_legacy_settled_package(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'phone' => '+375295000005']);
        $student = User::factory()->create(['role' => 'student', 'phone' => '+375295000006']);

        $now = CarbonImmutable::now('UTC');

        $parent = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now,
            'end_time' => $now->addHour(),
            'price' => '40.00',
            'package_code' => 'pack_4',
            'package_lessons' => 4,
            'platform_commission' => '15.20',
            'net_amount' => '133.16',
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $transaction = Transaction::query()->create([
            'lesson_id' => $parent->id,
            'user_id' => $student->id,
            'amount' => '152.00',
            'platform_commission' => '15.20',
            'acquiring_fee' => '3.64',
            'net_amount' => '133.16',
            'currency' => 'BYN',
            'status' => Transaction::STATUS_SUCCESS,
            'payment_method' => 'card',
            'gateway_transaction_id' => 'legacy-tx-1',
            'gateway_response' => [
                'settled' => true,
                'settled_at' => $now->toIso8601String(),
                'package_code' => 'pack_4',
                'package_lessons' => 4,
            ],
            'paid_at' => $now,
        ]);

        // Clear Settlements
        DB::table('lesson_settlements')->truncate();

        // Run backfill manually using reflection
        $migration = require database_path('migrations/2026_06_01_000100_create_lesson_settlements_table.php');
        $ref = new \ReflectionMethod($migration, 'backfill');
        $ref->setAccessible(true);
        $ref->invoke($migration);

        $this->assertDatabaseHas('lesson_settlements', [
            'lesson_id' => $parent->id,
            'transaction_id' => $transaction->id,
            'net_share' => '133.16',
            'gross_share' => '152.00',
        ]);
    }

    public function test_backfill_creates_records_for_completed_singles(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'phone' => '+375295000007']);
        $student = User::factory()->create(['role' => 'student', 'phone' => '+375295000008']);

        $now = CarbonImmutable::now('UTC');

        $single = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now,
            'end_time' => $now->addHour(),
            'price' => '40.00',
            'package_code' => 'single',
            'package_lessons' => 1,
            'platform_commission' => '6.00',
            'net_amount' => '32.82',
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $transaction = Transaction::query()->create([
            'lesson_id' => $single->id,
            'user_id' => $student->id,
            'amount' => '40.00',
            'platform_commission' => '6.00',
            'acquiring_fee' => '1.18',
            'net_amount' => '32.82',
            'currency' => 'BYN',
            'status' => Transaction::STATUS_SUCCESS,
            'payment_method' => 'card',
            'gateway_transaction_id' => 'legacy-tx-2',
            'gateway_response' => [
                'package_code' => 'single',
                'package_lessons' => 1,
            ],
            'paid_at' => $now,
        ]);

        DB::table('lesson_settlements')->truncate();

        $migration = require database_path('migrations/2026_06_01_000100_create_lesson_settlements_table.php');
        $ref = new \ReflectionMethod($migration, 'backfill');
        $ref->setAccessible(true);
        $ref->invoke($migration);

        $this->assertDatabaseHas('lesson_settlements', [
            'lesson_id' => $single->id,
            'transaction_id' => $transaction->id,
            'net_share' => '32.82',
            'gross_share' => '40.00',
        ]);
    }

    public function test_full_package_lifecycle_settle_then_refund_remaining(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'phone' => '+375295000009']);
        $tutor->tutorProfile()->create([
            'subjects' => ['Математика'],
            'audiences' => ['Подготовка к ЦЭ'],
            'price_per_hour' => '40.00',
            'experience_years' => 5,
            'legal_status' => 'self_employed',
            'bio' => 'Подготовка.',
            'is_verified' => true,
            'verification_status' => 'approved',
            'lesson_formats' => ['individual_online'],
        ]);
        $student = User::factory()->create(['role' => 'student', 'phone' => '+375295000010']);

        // Fund student wallet so that the payment refund goes smoothly via wallet credit if needed
        StudentBalance::query()->create([
            'user_id' => $student->id,
            'available_amount' => '152.00',
            'locked_amount' => '0.00',
            'total_topped_up' => '152.00',
            'total_spent' => '0.00',
            'total_refunded' => '0.00',
        ]);

        // Bootstrap paid pack_4 using wallet balance so refund doesn't call card gateway
        $now = CarbonImmutable::now('UTC');
        $parent = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->subHours(2),
            'end_time' => $now->subHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'package_code' => 'pack_4',
            'package_lessons' => 4,
            'package_lessons_remaining' => 4,
            'package_total' => '152.00',
            'package_discount' => '8.00',
            'platform_commission' => '0.00',
            'net_amount' => '148.36',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => $now->addMinutes(15),
        ]);

        $children = [];
        for ($i = 0; $i < 3; $i++) {
            $children[] = Lesson::query()->forceCreate([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'start_time' => $now->subHours(2),
                'end_time' => $now->subHour(),
                'duration_minutes' => 60,
                'price' => '40.00',
                'package_code' => 'pack_4',
                'package_lessons' => 1,
                'package_parent_lesson_id' => $parent->id,
                'package_discount' => '0.00',
                'platform_commission' => '0.00',
                'net_amount' => '0.00',
                'status' => Lesson::STATUS_PENDING,
                'payment_status' => Lesson::PAYMENT_UNPAID,
                'payment_lock_expires_at' => $now->addMinutes(15),
            ]);
        }

        $transaction = app(PaymentService::class)->processPayment(
            lessonId: $parent->id,
            userId: $student->id,
            paymentMethod: 'card',
            rememberPaymentMethod: false,
            useWalletBalance: true, // Use wallet balance
        );

        $parent->refresh();
        $children = array_map(fn ($c) => $c->refresh(), $children);

        // Settle parent lesson (tutor gets 37.09)
        $parent->update(['status' => Lesson::STATUS_COMPLETED]);
        app(PaymentService::class)->settleCompletedLesson($parent->fresh());

        // Settle child 1 lesson (tutor gets 37.09, total 74.18)
        $children[0]->update(['status' => Lesson::STATUS_COMPLETED]);
        app(PaymentService::class)->settleCompletedLesson($children[0]->fresh());

        $balance = TutorBalance::query()->where('user_id', $tutor->id)->firstOrFail();
        $this->assertSame('74.18', (string) $balance->available_amount);

        // Refund child 2 lesson
        app(PaymentService::class)->refundLessonPayment($children[1]->fresh(), 'student_cancelled');

        // Refund child 3 lesson
        app(PaymentService::class)->refundLessonPayment($children[2]->fresh(), 'student_cancelled');

        $balance->refresh();
        $this->assertSame('74.18', (string) $balance->available_amount);
        $this->assertSame('0.00', (string) $balance->pending_amount);

        $transaction->refresh();
        $this->assertSame(Transaction::STATUS_PARTIALLY_REFUNDED, $transaction->status);
    }
}
