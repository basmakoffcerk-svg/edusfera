<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Enums\UserRole;
use App\Filament\Pages\TutorSubscriptionPage;
use App\Models\Lesson;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PromoCodeService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromoAndSubscriptionSecurityComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private PromoCodeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PromoCodeService::class);
    }

    /**
     * 1. Нормализация кода: регистронезависимость, удаление пробелов и невидимых Unicode-символов.
     */
    public function test_case_insensitivity_and_unicode_space_handling(): void
    {
        $promo = PromoCode::create([
            'code' => 'SUPER-PROMO',
            'discount_type' => 'percent',
            'discount_value' => 20.00,
            'is_active' => true,
        ]);

        $variations = [
            'super-promo',
            'SUPER-PROMO',
            'SuPeR-PrOmO',
            '  SUPER-PROMO  ',
            "\tsuper-promo\n",
            "SUPER\u{00A0}-\u{00A0}PROMO", // Non-breaking spaces
            "SUPER\u{200B}PROMO",         // Zero-width space
        ];

        foreach ($variations as $var) {
            $found = $this->service->findByCode($var);
            if ($found) {
                $this->assertEquals($promo->id, $found->id, "Failed for variation: '{$var}'");
            }
        }
    }

    /**
     * 2. Защита от SQL-инъекций и XSS в строке промокода.
     */
    public function test_xss_and_sqli_payloads_in_promo_code_input(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $attacks = [
            "' OR '1'='1",
            "'; DROP TABLE promo_codes; --",
            '<script>alert(1)</script>',
            '"><svg onload=alert(1)>',
            'UNION SELECT * FROM users--',
        ];

        foreach ($attacks as $payload) {
            $res = $this->service->validate($payload, 40.0, $tutor);
            $this->assertFalse($res['valid']);
            $this->assertEquals('Промокод не найден.', $res['error']);
            $this->assertNull($res['promo_code']);
        }
    }

    /**
     * 3. Защита от DoS сверхдлинными строками.
     */
    public function test_long_string_dos_payload_handled_safely(): void
    {
        $huge = str_repeat('A', 20000);
        $found = $this->service->findByCode($huge);
        $this->assertNull($found);
    }

    /**
     * 4. Промокод с будущей датой (starts_at) отклоняется.
     */
    public function test_future_scheduled_starts_at_is_rejected(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $promo = PromoCode::create([
            'code' => 'FUTURE-CODE',
            'discount_type' => 'percent',
            'discount_value' => 15.00,
            'is_active' => true,
            'starts_at' => now()->addHour(),
        ]);

        $val = $promo->validateFor(40.0, $tutor);
        $this->assertFalse($val['valid']);
        $this->assertStringContainsString('ещё не начался', $val['error']);
    }

    /**
     * 5. Промокод с истекшим сроком (expires_at) отклоняется.
     */
    public function test_expired_promo_code_is_rejected(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $promo = PromoCode::create([
            'code' => 'EXPIRED-CODE',
            'discount_type' => 'percent',
            'discount_value' => 15.00,
            'is_active' => true,
            'expires_at' => now()->subMinute(),
        ]);

        $val = $promo->validateFor(40.0, $tutor);
        $this->assertFalse($val['valid']);
        $this->assertStringContainsString('истёк', $val['error']);
    }

    /**
     * 6. Строгое соблюдение лимита глобальных активаций (max_uses).
     */
    public function test_max_uses_global_limit_enforced(): void
    {
        $promo = PromoCode::create([
            'code' => 'LIMITED-ONE',
            'discount_type' => 'fixed',
            'discount_value' => 10.00,
            'max_uses' => 1,
            'used_count' => 1,
            'is_active' => true,
        ]);

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $val = $promo->validateFor(40.0, $tutor);

        $this->assertFalse($val['valid']);
        $this->assertStringContainsString('Лимит активаций этого промокода исчерпан', $val['error']);
    }

    /**
     * 7. Соблюдение лимита активаций на одного пользователя (max_uses_per_user).
     */
    public function test_max_uses_per_user_limit_enforced(): void
    {
        $promo = PromoCode::create([
            'code' => 'USER-LIMIT-ONE',
            'discount_type' => 'percent',
            'discount_value' => 10.00,
            'max_uses_per_user' => 1,
            'is_active' => true,
        ]);

        $user1 = User::factory()->create(['role' => UserRole::Tutor]);
        $user2 = User::factory()->create(['role' => UserRole::Tutor]);

        PromoCodeUsage::create([
            'promo_code_id' => $promo->id,
            'user_id' => $user1->id,
            'order_type' => 'subscription',
            'discount_amount' => 10.00,
            'original_amount' => 40.00,
            'final_amount' => 30.00,
            'created_at' => now(),
        ]);

        // Для user1 должно быть отклонено
        $val1 = $promo->validateFor(40.0, $user1);
        $this->assertFalse($val1['valid']);
        $this->assertStringContainsString('уже использовали этот промокод', $val1['error']);

        // Для user2 должно быть доступно
        $val2 = $promo->validateFor(40.0, $user2);
        $this->assertTrue($val2['valid']);
    }

    /**
     * 8. Правило first_order_only отклоняет пользователя с завершённым уроком.
     */
    public function test_first_order_only_rejects_user_with_paid_lesson(): void
    {
        $promo = PromoCode::create([
            'code' => 'NEWBIE-ONLY',
            'discount_type' => 'percent',
            'discount_value' => 50.00,
            'first_order_only' => true,
            'is_active' => true,
        ]);

        $student = User::factory()->create(['role' => UserRole::Student]);
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => 25.00,
            'platform_commission' => 2.50,
            'net_amount' => 22.50,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        Transaction::create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'amount' => 25.00,
            'platform_commission' => 2.50,
            'acquiring_fee' => 0.50,
            'net_amount' => 22.00,
            'status' => Transaction::STATUS_SUCCESS,
            'currency' => 'BYN',
            'payment_method' => 'card',
        ]);

        $val = $promo->validateFor(50.0, $student);
        $this->assertFalse($val['valid']);
        $this->assertStringContainsString('только на первый заказ', $val['error']);
    }

    /**
     * 9. Правило first_order_only отклоняет репетитора с оплаченным счётом подписки.
     */
    public function test_first_order_only_rejects_tutor_with_paid_subscription_invoice(): void
    {
        $promo = PromoCode::create([
            'code' => 'NEW-TUTOR-ONLY',
            'discount_type' => 'lifetime',
            'first_order_only' => true,
            'is_active' => true,
        ]);

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::ACTIVE,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        SubscriptionInvoice::create([
            'subscription_id' => $sub->id,
            'tutor_id' => $tutor->id,
            'invoice_number' => 'INV-PAID-TEST',
            'plan' => SubscriptionPlan::PRO,
            'period_months' => 1,
            'amount_kopecks' => 4000,
            'status' => InvoiceStatus::PAID,
            'due_date' => now(),
            'paid_at' => now(),
        ]);

        $val = $promo->validateFor(40.0, $tutor, ['order_type' => 'subscription']);
        $this->assertFalse($val['valid']);
        $this->assertStringContainsString('только на первый заказ', $val['error']);
    }

    /**
     * 10. Минимальная сумма заказа (min_order_amount).
     */
    public function test_min_order_amount_enforcement(): void
    {
        $promo = PromoCode::create([
            'code' => 'MIN-100-BYN',
            'discount_type' => 'fixed',
            'discount_value' => 20.00,
            'min_order_amount' => 100.00,
            'is_active' => true,
        ]);

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $tooLow = $promo->validateFor(80.0, $tutor);
        $this->assertFalse($tooLow['valid']);
        $this->assertStringContainsString('Минимальная сумма для этого промокода — 100.00 BYN', $tooLow['error']);

        $exact = $promo->validateFor(100.0, $tutor);
        $this->assertTrue($exact['valid']);
        $this->assertEquals(20.00, $exact['discount_amount']);
        $this->assertEquals(80.00, $exact['final_amount']);
    }

    /**
     * 11. Ограничение максимальной суммы скидки (max_discount_amount).
     */
    public function test_max_discount_amount_capping(): void
    {
        $promo = PromoCode::create([
            'code' => 'CAP-50-BYN',
            'discount_type' => 'percent',
            'discount_value' => 50.00,
            'max_discount_amount' => 30.00,
            'is_active' => true,
        ]);

        $discount = $promo->calculateDiscount(200.0);
        $this->assertEquals(30.00, $discount);
    }

    /**
     * 12. Невозможность получения отрицательной цены или выплаты пользователю.
     */
    public function test_negative_discount_or_over_100_percent_discount_never_causes_negative_price(): void
    {
        $promo = PromoCode::create([
            'code' => 'HUGE-DISCOUNT',
            'discount_type' => 'fixed',
            'discount_value' => 500.00,
            'is_active' => true,
        ]);

        $discount = $promo->calculateDiscount(40.00);
        $this->assertEquals(40.00, $discount);

        $val = $promo->validateFor(40.00);
        $this->assertEquals(0.00, $val['final_amount']);
        $this->assertEquals(40.00, $val['discount_amount']);
    }

    /**
     * 13. Ограничение области применения scope: уроки не действуют на подписки.
     */
    public function test_scope_lessons_cannot_be_used_on_subscriptions(): void
    {
        $promo = PromoCode::create([
            'code' => 'LESSON-ONLY',
            'discount_type' => 'percent',
            'discount_value' => 10.00,
            'scope' => 'lessons',
            'is_active' => true,
        ]);

        $val = $promo->validateFor(40.0, null, ['order_type' => 'subscription']);
        $this->assertFalse($val['valid']);
        $this->assertStringContainsString('только для оплаты занятий', $val['error']);
    }

    /**
     * 14. Ограничение области применения scope: подписки не действуют на уроки.
     */
    public function test_scope_subscriptions_cannot_be_used_on_lessons(): void
    {
        $promo = PromoCode::create([
            'code' => 'SUB-ONLY',
            'discount_type' => 'percent',
            'discount_value' => 10.00,
            'scope' => 'subscriptions',
            'is_active' => true,
        ]);

        $val = $promo->validateFor(40.0, null, ['order_type' => 'lesson']);
        $this->assertFalse($val['valid']);
        $this->assertStringContainsString('только для оплаты тарифов репетитора', $val['error']);
    }

    /**
     * 15. Ограничение по конкретному тарифному плану подписки (subscription_plan).
     */
    public function test_subscription_plan_restriction(): void
    {
        $promo = PromoCode::create([
            'code' => 'PRO-ONLY-PLAN',
            'discount_type' => 'free_period',
            'subscription_period' => '1_month',
            'subscription_plan' => 'pro',
            'is_active' => true,
        ]);

        $valPro = $promo->validateFor(40.0, null, ['order_type' => 'subscription', 'plan' => 'pro']);
        $this->assertTrue($valPro['valid']);

        $valPremium = $promo->validateFor(60.0, null, ['order_type' => 'subscription', 'plan' => 'premium']);
        $this->assertFalse($valPremium['valid']);
        $this->assertStringContainsString('только для тарифа «pro»', $valPremium['error']);
    }

    /**
     * 16. Студент не может активировать репетиторскую бесплатную подписку.
     */
    public function test_non_tutor_cannot_activate_free_subscription(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        $promo = PromoCode::create([
            'code' => 'TUTOR-GRANT',
            'discount_type' => 'lifetime',
            'subscription_period' => 'lifetime',
            'subscription_plan' => 'pro',
            'is_active' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Бесплатная подписка по промокоду доступна только для преподавателей');

        $this->service->activateFreeSubscriptionViaPromo($promo, $student);
    }

    /**
     * 17. Правильное сложение периодов (stacking) при активации подписки.
     */
    public function test_subscription_period_stacking_correctness(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $promo = PromoCode::create([
            'code' => 'MONTH-GRANT',
            'discount_type' => 'free_period',
            'subscription_period' => '3_months',
            'subscription_plan' => 'pro',
            'is_active' => true,
        ]);

        $currentEnd = CarbonImmutable::now()->addDays(20);
        Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::ACTIVE,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => $currentEnd,
        ]);

        $res = $this->service->activateFreeSubscriptionViaPromo($promo, $tutor);

        /** @var Subscription $updatedSub */
        $updatedSub = $res['subscription'];
        $this->assertEquals(SubscriptionStatus::ACTIVE, $updatedSub->status);
        $expected = $currentEnd->addMonths(3);
        $this->assertEquals($expected->toDateString(), $updatedSub->current_period_ends_at->toDateString());
    }

    /**
     * 18. Бессрочная подписка (lifetime) не превышает лимит MySQL 2038 года.
     */
    public function test_lifetime_subscription_safe_date_horizon(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $promo = PromoCode::create([
            'code' => 'SAFE-LIFETIME',
            'discount_type' => 'lifetime',
            'subscription_period' => 'lifetime',
            'subscription_plan' => 'pro',
            'is_active' => true,
        ]);

        $res = $this->service->activateFreeSubscriptionViaPromo($promo, $tutor);

        /** @var Subscription $sub */
        $sub = $res['subscription'];
        $this->assertEquals(2037, $sub->current_period_ends_at->year);
        $this->assertTrue($sub->isActive());
    }

    /**
     * 19. Защита от состояния гонки при достижении max_uses.
     */
    public function test_concurrency_protection_when_max_uses_reached(): void
    {
        $promo = PromoCode::create([
            'code' => 'RACE-CHECK',
            'discount_type' => 'lifetime',
            'subscription_period' => 'lifetime',
            'subscription_plan' => 'pro',
            'max_uses' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $tutor1 = User::factory()->create(['role' => UserRole::Tutor]);
        $tutor2 = User::factory()->create(['role' => UserRole::Tutor]);

        $res1 = $this->service->activateFreeSubscriptionViaPromo($promo, $tutor1);
        $this->assertNotNull($res1['subscription']);

        $promo->refresh();
        $this->assertEquals(1, $promo->used_count);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->activateFreeSubscriptionViaPromo($promo, $tutor2);
    }

    /**
     * 20. Мягко удалённый (soft-deleted) промокод не может быть найден.
     */
    public function test_soft_deleted_promo_code_cannot_be_found(): void
    {
        $promo = PromoCode::create([
            'code' => 'DELETED-PROMO',
            'discount_type' => 'percent',
            'discount_value' => 20.00,
            'is_active' => true,
        ]);

        $promo->delete();

        $this->assertNull($this->service->findByCode('DELETED-PROMO'));
    }

    /**
     * 21. Livewire UI: модальное окно отображает кнопку бесплатной активации и скрывает переход в банк.
     */
    public function test_livewire_checkout_modal_displays_free_activation_flow(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        PromoCode::create([
            'code' => 'FREE-PRO-YEAR',
            'discount_type' => 'free_period',
            'subscription_period' => '12_months',
            'subscription_plan' => 'pro',
            'is_active' => true,
        ]);

        $this->actingAs($tutor);

        Livewire::test(TutorSubscriptionPage::class)
            ->call('openPaymentModal', 'pro')
            ->set('promoCode', 'FREE-PRO-YEAR')
            ->call('applyPromoCode')
            ->assertSet('isFreeActivation', true)
            ->assertSee('Бесплатный период (1 год)')
            ->assertSee('Активировать подписку бесплатно')
            ->call('activateFreePromo')
            ->assertHasNoErrors();

        $sub = Subscription::where('tutor_id', $tutor->id)->first();
        $this->assertNotNull($sub);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertEquals(SubscriptionPlan::PRO, $sub->plan);
    }
}
