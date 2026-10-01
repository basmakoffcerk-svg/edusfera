<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Filament\Pages\TutorNpdPage;
use App\Filament\Resources\TransactionResource;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TutorNpdPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_can_access_npd_page_and_see_tax_kpis(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298300001',
        ]);

        TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'unp' => 'KB1234567',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298300002',
        ]);

        Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now('UTC'),
            'end_time' => now('UTC')->addHour(),
            'duration_minutes' => 60,
            'price' => '60.00',
            'platform_commission' => '0.00',
            'net_amount' => '60.00',
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        Livewire::actingAs($tutor)
            ->test(TutorNpdPage::class)
            ->assertSuccessful()
            ->assertSee('Кабинет плательщика НПД')
            ->assertSee('60.00')
            ->assertSee('npd.nalog.gov.by')
            ->assertSee('Вход через МСИ');
    }

    public function test_tutor_can_toggle_deduction(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298300003',
        ]);

        app(SubscriptionService::class)->ensureTrialStarted($tutor);

        TutorProfile::query()->create([
            'user_id' => $tutor->id,
        ]);

        Livewire::actingAs($tutor)
            ->test(TutorNpdPage::class)
            ->call('toggleDeduction')
            ->assertSet('applyFirstTimeDeduction', false);
    }

    public function test_tutor_can_mark_and_unmark_npd_receipt(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298300004',
        ]);

        app(SubscriptionService::class)->ensureTrialStarted($tutor);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298300005',
        ]);

        $lesson = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now('UTC'),
            'end_time' => now('UTC')->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '0.00',
            'net_amount' => '80.00',
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        Livewire::actingAs($tutor)
            ->test(TutorNpdPage::class)
            ->call('openReceiptModal', $lesson->id)
            ->assertSet('selectedLessonId', $lesson->id)
            ->set('receiptNumberInput', 'ЧЕК-998877')
            ->call('markReceiptIssued', $lesson->id);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'npd_receipt_number' => 'ЧЕК-998877',
        ]);

        Livewire::actingAs($tutor)
            ->test(TutorNpdPage::class)
            ->call('unmarkReceiptIssued', $lesson->id);

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'npd_receipt_number' => null,
            'npd_receipt_issued_at' => null,
        ]);
    }

    public function test_start_plan_tutor_is_blocked_from_npd_actions(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298300006',
        ]);

        app(SubscriptionService::class)->subscribe(
            $tutor,
            SubscriptionPlan::START,
            1
        );

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298300007',
        ]);

        $lesson = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now('UTC'),
            'end_time' => now('UTC')->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '0.00',
            'net_amount' => '80.00',
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        Livewire::actingAs($tutor)
            ->test(TutorNpdPage::class)
            ->assertSee('Автоматизация чеков НПД доступна на тарифе «Про»')
            ->call('openReceiptModal', $lesson->id)
            ->assertSet('selectedLessonId', null)
            ->call('toggleDeduction')
            ->assertSet('applyFirstTimeDeduction', true);
    }

    public function test_student_cannot_access_npd_page(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298300006',
        ]);

        $this->actingAs($student)
            ->get('/admin/tutor-npd')
            ->assertForbidden();
    }

    public function test_tutor_cannot_see_transactions_resource_navigation(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298300007',
        ]);

        $this->actingAs($tutor);
        $this->assertFalse(TransactionResource::shouldRegisterNavigation());
        $this->assertFalse(TransactionResource::canViewAny());
    }
}
