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
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentServiceSettlementAndRefundTest extends TestCase
{
    use RefreshDatabase;

    private function bootstrapPaidPack4(): array
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375294000001',
        ]);

        $tutor->tutorProfile()->create([
            'subjects' => ['Математика'],
            'audiences' => ['Подготовка к ЦЭ'],
            'price_per_hour' => '40.00',
            'experience_years' => 5,
            'legal_status' => 'self_employed',
            'bio' => 'Подготовка к экзаменам.',
            'is_verified' => true,
            'verification_status' => 'approved',
            'lesson_formats' => ['individual_online'],
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375294000002',
        ]);

        $now = CarbonImmutable::now('UTC');

        $parent = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->addDays(2),
            'end_time' => $now->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'package_code' => 'pack_4',
            'package_lessons' => 4,
            'package_lessons_remaining' => 4,
            'package_total' => '152.00',
            'package_discount' => '8.00',
            'platform_commission' => '15.20',
            'net_amount' => '133.16',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => $now->addMinutes(15),
        ]);

        $children = [];
        for ($i = 0; $i < 3; $i++) {
            $children[] = Lesson::query()->forceCreate([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'start_time' => $now->addDays(3 + $i),
                'end_time' => $now->addDays(3 + $i)->addHour(),
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
            'tutor' => $tutor,
            'student' => $student,
            'parent' => $parent->refresh(),
            'children' => array_map(fn (Lesson $lesson): Lesson => $lesson->refresh(), $children),
            'transaction' => $transaction,
        ];
    }

    public function test_settle_package_parent_credits_one_share(): void
    {
        $ctx = $this->bootstrapPaidPack4();
        /** @var Lesson $parent */
        $parent = $ctx['parent'];
        $parent->update(['status' => Lesson::STATUS_COMPLETED]);

        app(PaymentService::class)->settleCompletedLesson($parent->fresh());

        $balance = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        // net_amount = 133.16. Share = floor(133.16 / 4, 2) = 33.29
        $this->assertSame('33.29', (string) $balance->available_amount);
        $this->assertSame('99.87', (string) $balance->pending_amount); // 133.16 - 33.29
        
        $settlement = $parent->refresh()->settlement;
        $this->assertNotNull($settlement);
        $this->assertSame('33.29', (string) $settlement->net_share);
        $this->assertSame('38.00', (string) $settlement->gross_share); // 152 / 4
    }

    public function test_settle_package_child_credits_one_share(): void
    {
        $ctx = $this->bootstrapPaidPack4();
        /** @var Lesson $child */
        $child = $ctx['children'][0];
        $child->update(['status' => Lesson::STATUS_COMPLETED]);

        app(PaymentService::class)->settleCompletedLesson($child->fresh());

        $balance = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        $this->assertSame('33.29', (string) $balance->available_amount);
        $this->assertSame('99.87', (string) $balance->pending_amount);
    }

    public function test_settle_last_package_lesson_uses_residual(): void
    {
        $ctx = $this->bootstrapPaidPack4();
        /** @var Lesson $parent */
        $parent = $ctx['parent'];
        /** @var array<int, Lesson> $children */
        $children = $ctx['children'];

        $parent->update(['status' => Lesson::STATUS_COMPLETED]);
        app(PaymentService::class)->settleCompletedLesson($parent->fresh());

        foreach ($children as $child) {
            $child->update(['status' => Lesson::STATUS_COMPLETED]);
            app(PaymentService::class)->settleCompletedLesson($child->fresh());
        }

        $balance = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        // 133.16 exactly
        $this->assertSame('133.16', (string) $balance->available_amount);
        $this->assertSame('0.00', (string) $balance->pending_amount);

        // Verification of residual share:
        // Shares 1, 2, 3: 33.29 each. Sum = 99.87.
        // Residual share on 4th lesson: 133.16 - 99.87 = 33.29. (here it is exactly equal, but let's check another math)
    }

    public function test_settle_package_idempotency(): void
    {
        $ctx = $this->bootstrapPaidPack4();
        /** @var Lesson $parent */
        $parent = $ctx['parent'];
        $parent->update(['status' => Lesson::STATUS_COMPLETED]);

        app(PaymentService::class)->settleCompletedLesson($parent->fresh());
        $balance1 = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail()->available_amount;

        // Second settle call should be a no-op
        app(PaymentService::class)->settleCompletedLesson($parent->fresh());
        $balance2 = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail()->available_amount;

        $this->assertSame((string) $balance1, (string) $balance2);
    }

    public function test_refund_unsettled_package_lesson_partial(): void
    {
        $ctx = $this->bootstrapPaidPack4();
        /** @var Lesson $child */
        $child = $ctx['children'][0];

        app(PaymentService::class)->refundLessonPayment($child->fresh(), 'student_cancelled');

        $balance = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        // pending: 133.16 - 33.29 = 99.87
        $this->assertSame('99.87', (string) $balance->pending_amount);
        $this->assertSame('0.00', (string) $balance->available_amount);

        $child->refresh();
        $this->assertSame(Lesson::STATUS_CANCELLED, $child->status);
        $this->assertSame(Lesson::PAYMENT_REFUNDED, $child->payment_status);
    }

    public function test_refund_settled_package_lesson_throws(): void
    {
        $ctx = $this->bootstrapPaidPack4();
        /** @var Lesson $parent */
        $parent = $ctx['parent'];
        $parent->update(['status' => Lesson::STATUS_COMPLETED]);

        app(PaymentService::class)->settleCompletedLesson($parent->fresh());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Урок уже проведён, возврат недоступен.');

        app(PaymentService::class)->refundLessonPayment($parent->fresh(), 'student_cancelled');
    }

    public function test_refund_package_does_not_affect_other_lessons(): void
    {
        $ctx = $this->bootstrapPaidPack4();
        /** @var Lesson $child */
        $child = $ctx['children'][0];
        /** @var Lesson $parent */
        $parent = $ctx['parent'];

        app(PaymentService::class)->refundLessonPayment($child->fresh(), 'student_cancelled');

        $parent->refresh();
        $this->assertSame(Lesson::STATUS_CONFIRMED, $parent->status);
        $this->assertSame(Lesson::PAYMENT_PAID, $parent->payment_status);
    }
}
