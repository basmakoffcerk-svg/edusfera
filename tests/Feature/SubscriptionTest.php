<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\SubscriptionGracePeriodNotification;
use App\Services\Payment\AlfaBankPaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_plans_pricing_and_features(): void
    {
        // START Plan
        $this->assertEquals(2900, SubscriptionPlan::START->monthlyPriceKopecks());
        $this->assertEquals(29, SubscriptionPlan::START->monthlyPriceByn());
        $this->assertEquals(29000, SubscriptionPlan::START->yearlyPriceKopecks());
        $this->assertEquals(290, SubscriptionPlan::START->yearlyPriceByn());
        $this->assertNull(SubscriptionPlan::START->maxResponsesPerMonth());

        $startFeatures = SubscriptionPlan::START->features();
        $this->assertCount(5, $startFeatures);
        $this->assertContains('Виртуальный класс (SFU)', $startFeatures);
        $this->assertContains('Интерактивная доска (Workspace)', $startFeatures);
        $this->assertContains('CRM и расписание уроков', $startFeatures);
        $this->assertContains('Неограниченно учеников', $startFeatures);
        $this->assertContains('Персональная ссылка для записи', $startFeatures);

        // PRO Plan
        $this->assertEquals(5900, SubscriptionPlan::PRO->monthlyPriceKopecks());
        $this->assertEquals(59, SubscriptionPlan::PRO->monthlyPriceByn());
        $this->assertEquals(59000, SubscriptionPlan::PRO->yearlyPriceKopecks());
        $this->assertEquals(590, SubscriptionPlan::PRO->yearlyPriceByn());
        $this->assertNull(SubscriptionPlan::PRO->maxResponsesPerMonth());

        $proFeatures = SubscriptionPlan::PRO->features();
        $this->assertCount(5, $proFeatures);
        $this->assertContains('Все возможности тарифа «Старт»', $proFeatures);
        $this->assertContains('ИИ-диагностика знаний (тесты РИКЗ)', $proFeatures);
        $this->assertContains('ИИ-помощник (конспекты, ДЗ, тесты)', $proFeatures);
        $this->assertContains('Авто-НПД (чеки МНС РБ)', $proFeatures);
        $this->assertContains('Персональный брендинг комнат', $proFeatures);

        // BASIC Plan is alias to START
        $this->assertEquals(SubscriptionPlan::START->monthlyPriceKopecks(), SubscriptionPlan::BASIC->monthlyPriceKopecks());
        $this->assertEquals(SubscriptionPlan::START->yearlyPriceKopecks(), SubscriptionPlan::BASIC->yearlyPriceKopecks());
        $this->assertEquals(SubscriptionPlan::START->features(), SubscriptionPlan::BASIC->features());
        $this->assertNull(SubscriptionPlan::BASIC->maxResponsesPerMonth());
    }

    public function test_subscription_status_is_operational(): void
    {
        $this->assertTrue(SubscriptionStatus::TRIAL->isOperational());
        $this->assertTrue(SubscriptionStatus::ACTIVE->isOperational());
        $this->assertTrue(SubscriptionStatus::PAST_DUE->isOperational(now()->addDays(2)));
        $this->assertFalse(SubscriptionStatus::PAST_DUE->isOperational(now()->subDay()));
        $this->assertFalse(SubscriptionStatus::CANCELED->isOperational());
        $this->assertFalse(SubscriptionStatus::EXPIRED->isOperational());
    }

    public function test_subscription_model_helpers_and_casts(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => SubscriptionStatus::TRIAL,
            'trial_ends_at' => now()->addDays(14),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addDays(14),
            'grace_period_ends_at' => null,
            'payment_token' => 'tok_card_123',
        ]);

        $this->assertInstanceOf(SubscriptionPlan::class, $sub->plan);
        $this->assertInstanceOf(SubscriptionStatus::class, $sub->status);
        $this->assertTrue($sub->isPro());
        $this->assertTrue($sub->isInTrial());
        $this->assertFalse($sub->isInGracePeriod());
        $this->assertTrue($sub->isActive());
        $this->assertGreaterThanOrEqual(13, $sub->daysRemaining());
        $this->assertEquals('tok_card_123', $sub->payment_token);

        // Switch to PAST_DUE within grace
        $sub->update([
            'status' => SubscriptionStatus::PAST_DUE,
            'grace_period_ends_at' => now()->addDays(3),
        ]);
        $this->assertTrue($sub->isInGracePeriod());
        $this->assertTrue($sub->isActive());

        // Grace expired
        $sub->update([
            'grace_period_ends_at' => now()->subDay(),
        ]);
        $this->assertFalse($sub->isInGracePeriod());
        $this->assertFalse($sub->isActive());
    }

    public function test_subscription_feature_gate_rules(): void
    {
        $gate = app(SubscriptionFeatureGate::class);
        $service = app(SubscriptionService::class);

        // 1. Trial tutor (full PRO access)
        $trialTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->ensureTrialStarted($trialTutor);

        $this->assertTrue($gate->canAccessClassroom($trialTutor));
        $this->assertTrue($gate->canUseAiTools($trialTutor));
        $this->assertTrue($gate->canUseNpd($trialTutor));
        $this->assertTrue($gate->canCustomizeBranding($trialTutor));

        // 2. Active START tutor
        $startTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($startTutor, SubscriptionPlan::START, 1);

        $this->assertTrue($gate->canAccessClassroom($startTutor));
        $this->assertFalse($gate->canUseAiTools($startTutor));
        $this->assertFalse($gate->canUseNpd($startTutor));
        $this->assertFalse($gate->canCustomizeBranding($startTutor));

        // 3. Active PRO tutor
        $proTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->subscribe($proTutor, SubscriptionPlan::PRO, 1);

        $this->assertTrue($gate->canAccessClassroom($proTutor));
        $this->assertTrue($gate->canUseAiTools($proTutor));
        $this->assertTrue($gate->canUseNpd($proTutor));
        $this->assertTrue($gate->canCustomizeBranding($proTutor));

        // 4. Expired subscription
        $sub = $service->getSubscription($proTutor);
        $sub->update([
            'status' => SubscriptionStatus::EXPIRED,
            'current_period_ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($gate->canAccessClassroom($proTutor));
        $this->assertFalse($gate->canUseAiTools($proTutor));
        $this->assertFalse($gate->canUseNpd($proTutor));
        $this->assertFalse($gate->canCustomizeBranding($proTutor));
    }

    public function test_subscription_service_methods(): void
    {
        $service = app(SubscriptionService::class);
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        // ensureTrialStarted
        $trial = $service->ensureTrialStarted($tutor);
        $this->assertEquals(SubscriptionPlan::PRO, $trial->plan);
        $this->assertEquals(SubscriptionStatus::TRIAL, $trial->status);
        $this->assertGreaterThanOrEqual(13, $trial->daysRemaining());

        // Calling again returns existing trial without changes
        $trialAgain = $service->ensureTrialStarted($tutor);
        $this->assertEquals($trial->id, $trialAgain->id);

        // subscribe
        $active = $service->subscribe($tutor, SubscriptionPlan::PRO, 1, 'card_token_abc');
        $this->assertEquals(SubscriptionStatus::ACTIVE, $active->status);
        $this->assertEquals('card_token_abc', $active->payment_token);
        $this->assertDatabaseHas('subscription_invoices', [
            'subscription_id' => $active->id,
            'amount_kopecks' => 5900,
            'status' => InvoiceStatus::PAID->value,
            'payment_method' => 'card',
        ]);

        // cancel renewal at end of current period
        $canceled = $service->cancel($tutor);
        $this->assertNotNull($canceled->canceled_at);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $canceled->status);
        $this->assertTrue($canceled->isActive());

        // handlePaymentFailure
        $pastDue = $service->handlePaymentFailure($active);
        $this->assertEquals(SubscriptionStatus::PAST_DUE, $pastDue->status);
        $this->assertNotNull($pastDue->grace_period_ends_at);
        $this->assertTrue($pastDue->grace_period_ends_at->isFuture());

        // expireGracePeriod when not elapsed -> does not change
        $service->expireGracePeriod($pastDue);
        $this->assertEquals(SubscriptionStatus::PAST_DUE, $pastDue->fresh()->status);

        // expireGracePeriod when elapsed
        $pastDue->update(['grace_period_ends_at' => now()->subMinute()]);
        $expired = $service->expireGracePeriod($pastDue);
        $this->assertEquals(SubscriptionStatus::EXPIRED, $expired->status);
    }

    public function test_billing_cron_auto_charge_with_payment_token(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);

        $sub = $service->subscribe($tutor, SubscriptionPlan::PRO, 1, 'tok_recurrent_valid');
        // Simulate subscription expiring
        $sub->update([
            'current_period_ends_at' => now()->subMinute(),
        ]);

        $this->artisan('edusfera:subscriptions-process-billing')
            ->assertSuccessful();

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);
        $this->assertTrue($sub->current_period_ends_at->isFuture());
    }

    public function test_billing_cron_moves_to_grace_and_notifies_when_charge_fails(): void
    {
        Notification::fake();

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);

        $sub = $service->subscribe($tutor, SubscriptionPlan::PRO, 1, 'fail'); // 'fail' triggers sandbox decline
        $sub->update([
            'current_period_ends_at' => now()->subMinute(),
        ]);

        $this->artisan('edusfera:subscriptions-process-billing')
            ->assertSuccessful();

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::PAST_DUE, $sub->status);
        $this->assertNotNull($sub->grace_period_ends_at);
        $this->assertTrue($sub->grace_period_ends_at->isFuture());

        Notification::assertSentTo($tutor, SubscriptionGracePeriodNotification::class);
    }

    public function test_billing_cron_expires_grace_period(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);

        $sub = $service->ensureTrialStarted($tutor);
        $sub->update([
            'status' => SubscriptionStatus::PAST_DUE,
            'grace_period_ends_at' => now()->subDay(),
        ]);

        $this->artisan('edusfera:subscriptions-process-billing')
            ->assertSuccessful();

        $sub->refresh();
        $this->assertEquals(SubscriptionStatus::EXPIRED, $sub->status);
    }

    public function test_tutor_registration_starts_trial_with_founder_badge(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'firstName' => 'Иван',
            'lastName' => 'Репетиторов',
            'email' => 'tutor.new@edusfera.by',
            'password' => 'Password123!',
            'role' => 'tutor',
            'plan' => 'pro',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $user = User::where('email', 'tutor.new@edusfera.by')->firstOrFail();
        $this->assertEquals(UserRole::Tutor, $user->role);

        $subscription = Subscription::where('tutor_id', $user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals(SubscriptionPlan::PRO, $subscription->plan);
        $this->assertEquals(SubscriptionStatus::TRIAL, $subscription->status);
        $this->assertTrue($subscription->is_founder);
        $this->assertTrue($subscription->isActive());
        $this->assertGreaterThanOrEqual(13, $subscription->daysRemaining());
    }
}
