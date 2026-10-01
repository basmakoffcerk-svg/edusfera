<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Enums\UserRole;
use App\Filament\Pages\TutorSubscriptionPage;
use App\Filament\Widgets\CommissionLadderWidget;
use App\Filament\Widgets\TutorHeroWidget;
use App\Models\PromoCode;
use App\Models\TutorProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TutorSubscriptionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_can_access_subscription_page_and_see_plans(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
            'name' => 'Анна Репетитор',
        ]);

        $this->actingAs($tutor);

        Livewire::test(TutorSubscriptionPage::class)
            ->assertSuccessful()
            ->assertSee('«Стандарт»')
            ->assertSee('«Про»')
            ->assertSee('Неограниченно учеников')
            ->assertSee('Виртуальный класс (SFU)')
            ->assertSee('Безопасная оплата через Альфа-Банк')
            ->assertDontSee('Сформировать счёт в ЕРИП')
            ->assertDontSee('Оплата подписки через ЕРИП');
    }

    public function test_tutor_can_toggle_yearly_billing_and_change_plan(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $this->actingAs($tutor);

        $component = Livewire::test(TutorSubscriptionPage::class)
            ->assertSet('isYearly', false)
            ->call('toggleYearly')
            ->assertSet('isYearly', true)
            ->call('changePlan', 'start');

        $subscription = Subscription::where('tutor_id', $tutor->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals(SubscriptionPlan::START, $subscription->plan);

        // Check invoices created
        $this->assertDatabaseHas('subscription_invoices', [
            'subscription_id' => $subscription->id,
            'plan' => 'start',
            'period_months' => 12,
            'amount_kopecks' => 19200,
        ]);
    }

    public function test_tutor_can_bind_card_and_activate_plan(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $this->actingAs($tutor);

        Livewire::test(TutorSubscriptionPage::class)
            ->call('subscribeWithCard', 'pro')
            ->assertRedirect()
            ->assertSet('selectedPlanCode', 'pro')
            ->call('confirmCardPayment');

        $subscription = Subscription::where('tutor_id', $tutor->id)->first();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals(SubscriptionPlan::PRO, $subscription->plan);

        $this->assertDatabaseHas('subscription_invoices', [
            'subscription_id' => $subscription->id,
            'status' => 'paid',
            'payment_method' => 'card',
        ]);
    }

    public function test_tutor_can_cancel_and_resume_subscription(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);
        $sub = $service->startTrial($tutor, SubscriptionPlan::PRO);
        $sub->update(['status' => SubscriptionStatus::ACTIVE]);

        $this->actingAs($tutor);

        Livewire::test(TutorSubscriptionPage::class)
            ->call('openCancelModal')
            ->assertSet('showCancelModal', true)
            ->call('cancelSubscription')
            ->assertSet('showCancelModal', false);

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::CANCELED, $sub->status);
        $this->assertNotNull($sub->canceled_at);

        Livewire::test(TutorSubscriptionPage::class)
            ->call('resumeSubscription');

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertNull($sub->canceled_at);
    }

    public function test_tutor_hero_widget_shows_booking_link_and_subscription_pill(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
            'name' => 'Елена Преподаватель',
        ]);

        $profile = TutorProfile::create([
            'user_id' => $tutor->id,
            'is_verified' => true,
        ]);

        $this->actingAs($tutor);

        Livewire::test(TutorHeroWidget::class)
            ->assertSuccessful()
            ->assertSee('Ссылка для записи:')
            ->assertSee('Скопировать')
            ->assertSee('Пробный период')
            ->assertSee('/tutors/'.$profile->id);
    }

    public function test_tutor_hero_widget_shows_grace_period_alert_when_past_due(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);
        $sub = $service->startTrial($tutor, SubscriptionPlan::PRO);
        $sub->update([
            'status' => SubscriptionStatus::PAST_DUE,
            'grace_period_ends_at' => now()->addDays(2),
        ]);

        $this->actingAs($tutor);

        Livewire::test(TutorHeroWidget::class)
            ->assertSuccessful()
            ->assertSee('Платёж за подписку не прошёл')
            ->assertSee('Обновить карту')
            ->assertSee('/admin/tutor-subscription-page');
    }

    public function test_tutor_can_initiate_card_payment_for_all_three_packages(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $this->actingAs($tutor);

        // 1. Basic Plan
        Livewire::test(TutorSubscriptionPage::class)
            ->call('subscribeWithCard', 'basic')
            ->assertRedirect();

        $this->assertDatabaseHas('subscription_invoices', [
            'plan' => 'basic',
            'amount_kopecks' => 2000,
            'period_months' => 1,
            'status' => 'pending',
        ]);

        // 2. Pro Plan
        Livewire::test(TutorSubscriptionPage::class)
            ->call('subscribeWithCard', 'pro')
            ->assertRedirect();

        $this->assertDatabaseHas('subscription_invoices', [
            'plan' => 'pro',
            'amount_kopecks' => 4000,
            'period_months' => 1,
            'status' => 'pending',
        ]);

        // 3. Premium Plan with yearly toggle
        Livewire::test(TutorSubscriptionPage::class)
            ->call('toggleYearly')
            ->call('subscribeWithCard', 'premium')
            ->assertRedirect();

        $this->assertDatabaseHas('subscription_invoices', [
            'plan' => 'premium',
            'amount_kopecks' => 57600,
            'period_months' => 12,
            'status' => 'pending',
        ]);
    }

    public function test_returning_from_alfabank_activates_subscription_via_query_param(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);
        $sub = $service->startTrial($tutor, SubscriptionPlan::PRO);
        $invoice = $service->createInvoice($sub, SubscriptionPlan::PREMIUM, 12);

        $this->actingAs($tutor);

        // Simulate returning to the page with Alfa-Bank's orderId
        Livewire::test(TutorSubscriptionPage::class, ['orderId' => 'alfa_sb_verified_test_123'])
            ->assertSuccessful();

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertEquals(SubscriptionPlan::PREMIUM, $sub->plan);

        $this->assertDatabaseHas('subscription_invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
            'payment_method' => 'card',
        ]);
    }

    public function test_returning_from_alfabank_with_payment_failed_shows_error_notification_and_banner(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);
        $sub = $service->startTrial($tutor, SubscriptionPlan::PRO);
        $invoice = $service->createInvoice($sub, SubscriptionPlan::PRO, 1);

        $this->actingAs($tutor);

        // Simulate user cancelling or bank redirecting to failUrl (?payment=failed)
        Livewire::test(TutorSubscriptionPage::class, ['payment' => 'failed'])
            ->assertSuccessful()
            ->assertSet('paymentErrorMessage', 'Операция оплаты была отменена или прервана пользователем.')
            ->assertSee('Платёж не прошёл')
            ->assertSee('Повторить оплату');

        $invoice->refresh();
        $this->assertEquals('canceled', $invoice->status->value);
    }

    public function test_returning_from_alfabank_with_declined_order_status_shows_detailed_reason(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);
        $sub = $service->startTrial($tutor, SubscriptionPlan::PRO);
        $invoice = $service->createInvoice($sub, SubscriptionPlan::PRO, 1);

        $this->actingAs($tutor);

        Http::fake([
            '*/getOrderStatusExtended.do' => Http::response([
                'errorCode' => '0',
                'errorMessage' => 'Успешно',
                'orderStatus' => 6,
                'actionCode' => 116,
                'actionCodeDescription' => 'Недостаточно средств на карте',
            ], 200),
        ]);

        Livewire::test(TutorSubscriptionPage::class, ['orderId' => 'order_declined_116'])
            ->assertSuccessful()
            ->assertSee('Платёж не прошёл')
            ->assertSee('Недостаточно средств на карте')
            ->assertSee('Повторить оплату');

        $invoice->refresh();
        $this->assertEquals('canceled', $invoice->status->value);
    }

    public function test_tutor_can_apply_and_use_promo_code_for_subscription(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $this->actingAs($tutor);

        PromoCode::create([
            'code' => 'TUTOR25',
            'discount_type' => 'percent',
            'discount_value' => 25.0,
            'scope' => 'subscriptions',
            'is_active' => true,
        ]);

        Livewire::test(TutorSubscriptionPage::class)
            ->assertSuccessful()
            ->call('openPaymentModal', 'pro')
            ->assertSet('showPaymentModal', true)
            ->set('promoCode', 'tutor25')
            ->call('applyPromoCode')
            ->assertSet('appliedPromoCode', 'TUTOR25')
            ->assertSet('promoDiscountAmount', 10.0) // 25% of 40 BYN Pro plan
            ->assertSee('TUTOR25')
            ->call('removePromoCode')
            ->assertSet('appliedPromoCode', null)
            ->assertSet('promoDiscountAmount', 0.0);
    }

    public function test_tutor_can_activate_free_subscription_period_via_promo_code(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $this->actingAs($tutor);

        $promo = PromoCode::create([
            'code' => 'FREE3MONTHS',
            'discount_type' => 'free_period',
            'subscription_period' => '3_months',
            'subscription_plan' => 'pro',
            'scope' => 'subscriptions',
            'is_active' => true,
        ]);

        Livewire::test(TutorSubscriptionPage::class)
            ->call('openPaymentModal', 'pro')
            ->assertSet('showPaymentModal', true)
            ->set('promoCode', 'FREE3MONTHS')
            ->call('applyPromoCode')
            ->assertSet('appliedPromoCode', 'FREE3MONTHS')
            ->assertSet('isFreeActivation', true)
            ->assertSee('FREE3MONTHS')
            ->assertSee('Бесплатный период (3 месяца)')
            ->call('activateFreePromo')
            ->assertSet('showPaymentModal', false);

        $sub = Subscription::where('tutor_id', $tutor->id)->first();
        $this->assertNotNull($sub);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertEquals(SubscriptionPlan::PRO, $sub->plan);
        $this->assertTrue($sub->current_period_ends_at->isAfter(now()->addMonths(2)));

        $this->assertDatabaseHas('promo_code_usages', [
            'promo_code_id' => $promo->id,
            'user_id' => $tutor->id,
            'order_type' => 'subscription',
        ]);
    }

    public function test_tutor_can_activate_lifetime_subscription_via_promo_code(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $this->actingAs($tutor);

        $promo = PromoCode::create([
            'code' => 'LIFETIMEPRO',
            'discount_type' => 'lifetime',
            'subscription_plan' => 'pro',
            'scope' => 'subscriptions',
            'is_active' => true,
        ]);

        Livewire::test(TutorSubscriptionPage::class)
            ->call('openPaymentModal', 'pro')
            ->set('promoCode', 'LIFETIMEPRO')
            ->call('applyPromoCode')
            ->assertSet('isFreeActivation', true)
            ->assertSee('Бессрочный бесплатный доступ')
            ->call('activateFreePromo');

        $sub = Subscription::where('tutor_id', $tutor->id)->first();
        $this->assertNotNull($sub);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertTrue($sub->current_period_ends_at->isAfter(now()->addYears(10)));
    }

    public function test_commission_ladder_widget_displays_lifetime_and_active_badge_cleanly(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $this->actingAs($tutor);

        Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::ACTIVE,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => CarbonImmutable::create(2037, 12, 31, 23, 59, 59),
            'is_founder' => true,
        ]);

        Livewire::test(CommissionLadderWidget::class)
            ->assertSuccessful()
            ->assertSee('Pro')
            ->assertSee('(40 BYN/мес)')
            ->assertSee('Бессрочный доступ')
            ->assertSee('★ Статус Основателя (Founder)');
    }

    public function test_initiating_payment_and_cancelling_or_clicking_back_does_not_activate_subscription(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);

        // Tutor with expired subscription
        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::EXPIRED,
            'trial_ends_at' => now()->subDays(10),
            'current_period_ends_at' => now()->subDays(10),
            'is_onboarded' => false,
        ]);

        $this->actingAs($tutor);

        // Tutor initiates payment for basic (Стандарт)
        Livewire::test(TutorSubscriptionPage::class)
            ->call('subscribeWithCard', 'basic')
            ->assertRedirect();

        // The subscription should NOT be switched to basic or activated before payment!
        $sub->refresh();
        $this->assertEquals(SubscriptionPlan::PRO, $sub->plan);
        $this->assertEquals(SubscriptionStatus::EXPIRED, $sub->status);
        $this->assertFalse($sub->isActive());

        // The invoice is created in pending status
        $this->assertDatabaseHas('subscription_invoices', [
            'subscription_id' => $sub->id,
            'plan' => 'basic',
            'status' => 'pending',
        ]);

        // User returns by clicking back in browser (without orderId query param)
        Livewire::test(TutorSubscriptionPage::class)
            ->assertSuccessful();

        $sub->refresh();
        $this->assertEquals(SubscriptionPlan::PRO, $sub->plan);
        $this->assertEquals(SubscriptionStatus::EXPIRED, $sub->status);
        $this->assertFalse($sub->isActive());
    }

    public function test_first_payment_grants_plan_specific_trial_days_bonus(): void
    {
        $service = app(SubscriptionService::class);

        // 1. Standard (START): 5 days trial + 1 month
        $tutorStandard = User::factory()->create(['role' => UserRole::Tutor]);
        $sub1 = $service->startTrial($tutorStandard, SubscriptionPlan::START);
        $inv1 = $service->createInvoice($sub1, SubscriptionPlan::START, 1);

        $now = CarbonImmutable::now();
        CarbonImmutable::setTestNow($now);

        $service->recordPayment($inv1, 'card');
        $sub1->refresh();

        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub1->status);
        $this->assertEquals(SubscriptionPlan::START, $sub1->plan);
        // Should be now + 5 trial days + 1 month
        $expectedEndStandard = $now->addDays(5)->addMonth();
        $this->assertEquals($expectedEndStandard->toDateTimeString(), $sub1->current_period_ends_at->toDateTimeString());

        // 2. Pro: 14 days trial + 1 month
        $tutorPro = User::factory()->create(['role' => UserRole::Tutor]);
        $sub2 = $service->startTrial($tutorPro, SubscriptionPlan::PRO);
        $inv2 = $service->createInvoice($sub2, SubscriptionPlan::PRO, 1);

        $service->recordPayment($inv2, 'card');
        $sub2->refresh();

        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub2->status);
        $this->assertEquals(SubscriptionPlan::PRO, $sub2->plan);
        // Should be now + 14 trial days + 1 month
        $expectedEndPro = $now->addDays(14)->addMonth();
        $this->assertEquals($expectedEndPro->toDateTimeString(), $sub2->current_period_ends_at->toDateTimeString());

        // 3. Premium: 28 days trial + 1 month
        $tutorPremium = User::factory()->create(['role' => UserRole::Tutor]);
        $sub3 = $service->startTrial($tutorPremium, SubscriptionPlan::PREMIUM);
        $inv3 = $service->createInvoice($sub3, SubscriptionPlan::PREMIUM, 1);

        $service->recordPayment($inv3, 'card');
        $sub3->refresh();

        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub3->status);
        $this->assertEquals(SubscriptionPlan::PREMIUM, $sub3->plan);
        // Should be now + 28 trial days + 1 month
        $expectedEndPremium = $now->addDays(28)->addMonth();
        $this->assertEquals($expectedEndPremium->toDateTimeString(), $sub3->current_period_ends_at->toDateTimeString());

        // 4. Renewal payment for tutorPremium: subsequent payment should NOT add trial bonus again
        $inv4 = $service->createInvoice($sub3, SubscriptionPlan::PREMIUM, 1);
        $service->recordPayment($inv4, 'card');
        $sub3->refresh();

        // Should extend by exactly 1 month from previous end date without another 28 days bonus
        $expectedEndRenewal = $expectedEndPremium->addMonth();
        $this->assertEquals($expectedEndRenewal->toDateTimeString(), $sub3->current_period_ends_at->toDateTimeString());

        CarbonImmutable::setTestNow(null);
    }

    public function test_free_trial_starts_with_plan_specific_days(): void
    {
        $service = app(SubscriptionService::class);
        $now = CarbonImmutable::now();
        CarbonImmutable::setTestNow($now);

        // Standard: 5 days
        $tutor1 = User::factory()->create(['role' => UserRole::Tutor]);
        $sub1 = $service->startTrial($tutor1, SubscriptionPlan::START);
        $this->assertEquals(5, (int) round($now->diffInDays($sub1->trial_ends_at)));

        // Pro: 14 days
        $tutor2 = User::factory()->create(['role' => UserRole::Tutor]);
        $sub2 = $service->startTrial($tutor2, SubscriptionPlan::PRO);
        $this->assertEquals(14, (int) round($now->diffInDays($sub2->trial_ends_at)));

        // Premium: 28 days
        $tutor3 = User::factory()->create(['role' => UserRole::Tutor]);
        $sub3 = $service->startTrial($tutor3, SubscriptionPlan::PREMIUM);
        $this->assertEquals(28, (int) round($now->diffInDays($sub3->trial_ends_at)));

        CarbonImmutable::setTestNow(null);
    }
}
