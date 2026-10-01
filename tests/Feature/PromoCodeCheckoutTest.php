<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\PromoCodeResource\Pages\EditPromoCode;
use App\Models\Lesson;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromoCodeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function createLessonWithUsers(float $price = 50.0): array
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291112233',
        ]);
        $tutor->tutorProfile()->create([
            'subjects' => ['Математика'],
            'audiences' => ['10-11 классы'],
            'price_per_hour' => (string) $price,
            'experience_years' => 5,
            'legal_status' => 'self_employed',
            'bio' => 'Репетитор по математике',
            'is_verified' => true,
            'verification_status' => 'approved',
            'lesson_formats' => ['individual_online'],
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375294445566',
        ]);

        $lesson = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => CarbonImmutable::now('UTC')->addDay(),
            'end_time' => CarbonImmutable::now('UTC')->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => (string) $price,
            'platform_commission' => '0.00',
            'net_amount' => (string) ($price - 1.4),
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => CarbonImmutable::now('UTC')->addMinutes(30),
        ]);

        return [$tutor, $student, $lesson];
    }

    public function test_promo_code_calculates_percent_and_fixed_discounts_correctly(): void
    {
        $percentPromo = PromoCode::create([
            'code' => 'DISCOUNT20',
            'discount_type' => 'percent',
            'discount_value' => 20.0,
            'max_discount_amount' => 15.0,
            'is_active' => true,
        ]);

        // 20% of 100 is 20, but capped at max 15.0
        $this->assertEquals(15.0, $percentPromo->calculateDiscount(100.0));

        // 20% of 50 is 10, below cap of 15
        $this->assertEquals(10.0, $percentPromo->calculateDiscount(50.0));

        $fixedPromo = PromoCode::create([
            'code' => 'MINUS15',
            'discount_type' => 'fixed',
            'discount_value' => 15.0,
            'is_active' => true,
        ]);

        // 15 BYN off 50 BYN
        $this->assertEquals(15.0, $fixedPromo->calculateDiscount(50.0));
        // 15 BYN off 10 BYN cannot exceed the order amount
        $this->assertEquals(10.0, $fixedPromo->calculateDiscount(10.0));
    }

    public function test_promo_code_validation_respects_limits_and_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        // 1. Expired promo
        $expiredPromo = PromoCode::create([
            'code' => 'OLDYEAR',
            'discount_type' => 'fixed',
            'discount_value' => 10.0,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);
        $val1 = $expiredPromo->validateFor(50.0, $student);
        $this->assertFalse($val1['valid']);
        $this->assertStringContainsString('истёк', $val1['error']);

        // 2. Minimum order amount
        $minPromo = PromoCode::create([
            'code' => 'BIGBUY',
            'discount_type' => 'fixed',
            'discount_value' => 20.0,
            'min_order_amount' => 80.0,
            'is_active' => true,
        ]);
        $val2 = $minPromo->validateFor(50.0, $student);
        $this->assertFalse($val2['valid']);
        $this->assertStringContainsString('Минимальная сумма', $val2['error']);

        // 3. Max uses limit
        $limitPromo = PromoCode::create([
            'code' => 'LIMITED',
            'discount_type' => 'percent',
            'discount_value' => 10.0,
            'max_uses' => 1,
            'used_count' => 1,
            'is_active' => true,
        ]);
        $val3 = $limitPromo->validateFor(50.0, $student);
        $this->assertFalse($val3['valid']);
        $this->assertStringContainsString('исчерпан', $val3['error']);

        // 4. Per user limit
        $userLimitPromo = PromoCode::create([
            'code' => 'ONCEPERUSER',
            'discount_type' => 'fixed',
            'discount_value' => 5.0,
            'max_uses_per_user' => 1,
            'is_active' => true,
        ]);
        PromoCodeUsage::create([
            'promo_code_id' => $userLimitPromo->id,
            'user_id' => $student->id,
            'order_type' => 'lesson',
            'discount_amount' => 5.0,
            'original_amount' => 50.0,
            'final_amount' => 45.0,
            'created_at' => now(),
        ]);
        $val4 = $userLimitPromo->validateFor(50.0, $student);
        $this->assertFalse($val4['valid']);
        $this->assertStringContainsString('максимально допустимое', $val4['error']);
    }

    public function test_apply_promo_endpoint_via_checkout_controller(): void
    {
        [$tutor, $student, $lesson] = $this->createLessonWithUsers(60.0);

        PromoCode::create([
            'code' => 'WELCOME15',
            'discount_type' => 'percent',
            'discount_value' => 15.0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($student)->postJson(route('checkout.promo.apply', $lesson), [
            'promo_code' => 'welcome15', // test case-insensitivity
            'package_code' => 'single',
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => true,
            'code' => 'WELCOME15',
            'discount_amount' => 9.0, // 15% of 60
            'final_amount' => 51.0,
        ]);
    }

    public function test_process_payment_applies_discount_and_records_usage(): void
    {
        [$tutor, $student, $lesson] = $this->createLessonWithUsers(80.0);

        $promo = PromoCode::create([
            'code' => 'SAVE20BYN',
            'discount_type' => 'fixed',
            'discount_value' => 20.0,
            'is_active' => true,
        ]);

        $paymentService = app(PaymentService::class);
        $transaction = $paymentService->processPayment(
            $lesson->id,
            $student->id,
            'card',
            false,
            false,
            'SAVE20BYN'
        );

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertEquals(60.0, (float) $transaction->amount);
        $this->assertEquals(20.0, (float) $transaction->discount_amount);
        $this->assertSame($promo->id, $transaction->promo_code_id);

        // Verify usage was recorded
        $usage = PromoCodeUsage::where('promo_code_id', $promo->id)->first();
        $this->assertNotNull($usage);
        $this->assertSame($student->id, $usage->user_id);
        $this->assertSame($lesson->id, $usage->lesson_id);
        $this->assertEquals(20.0, $usage->discount_amount);
        $this->assertEquals(80.0, $usage->original_amount);
        $this->assertEquals(60.0, $usage->final_amount);

        // Verify promo code used_count incremented
        $promo->refresh();
        $this->assertSame(1, $promo->used_count);
    }

    public function test_admin_can_view_edit_promo_code_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $promo = PromoCode::create([
            'code' => 'TESTEDIT',
            'discount_type' => 'percent',
            'discount_value' => 10.0,
            'scope' => 'all',
            'package_codes' => ['single'],
            'subjects' => ['Математика'],
            'tutor_ids' => [1],
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        Livewire::test(EditPromoCode::class, [
            'record' => $promo->getRouteKey(),
        ])->assertSuccessful();
    }

    public function test_admin_can_save_lifetime_promo_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $promo = PromoCode::create([
            'code' => 'TESTLIFETIME',
            'discount_type' => 'percent',
            'discount_value' => 10.0,
            'scope' => 'all',
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        Livewire::test(EditPromoCode::class, [
            'record' => $promo->getRouteKey(),
        ])
            ->fillForm([
                'discount_type' => 'lifetime',
                'subscription_plan' => 'pro',
                'scope' => 'subscriptions',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $promo->refresh();
        $this->assertSame('lifetime', $promo->discount_type);
        $this->assertSame('pro', $promo->subscription_plan);
    }
}
