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
use App\Models\TutorProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_registration_starts_trial_with_selected_plan_and_founder_badge(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'firstName' => 'Иван',
            'lastName' => 'Репетиторов',
            'email' => 'tutor.test@edusfera.by',
            'password' => 'Password123!',
            'role' => 'tutor',
            'plan' => 'premium',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $user = User::where('email', 'tutor.test@edusfera.by')->firstOrFail();
        $this->assertEquals(UserRole::Tutor, $user->role);

        $subscription = Subscription::where('tutor_id', $user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals(SubscriptionPlan::PREMIUM, $subscription->plan);
        $this->assertEquals(SubscriptionStatus::TRIAL, $subscription->status);
        $this->assertTrue($subscription->is_founder);
        $this->assertTrue($subscription->isActive());
        $this->assertGreaterThanOrEqual(29, $subscription->daysRemaining());
    }

    public function test_invoice_creation_and_payment_recording(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);

        $subscription = $service->startTrial($tutor, SubscriptionPlan::PRO);
        $this->assertEquals(SubscriptionStatus::TRIAL, $subscription->status);

        $invoice = $service->createInvoice($subscription, SubscriptionPlan::PRO, 1);
        $this->assertEquals(4000, $invoice->amount_kopecks);
        $this->assertEquals(InvoiceStatus::PENDING, $invoice->status);
        $this->assertEquals("EDU" . sprintf('%05d', $tutor->id), $invoice->erip_account_number);

        $paidInvoice = $service->recordPayment($invoice, 'erip');
        $this->assertEquals(InvoiceStatus::PAID, $paidInvoice->status);

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertNotNull($subscription->current_period_ends_at);
        $this->assertTrue($subscription->current_period_ends_at->isFuture());
    }

    public function test_subscription_feature_gate_limits(): void
    {
        $gate = app(SubscriptionFeatureGate::class);
        $service = app(SubscriptionService::class);

        $basicTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->startTrial($basicTutor, SubscriptionPlan::BASIC);

        $this->assertFalse($gate->canRespondToRequests($basicTutor));
        $this->assertFalse($gate->canAccessAnalytics($basicTutor));
        $this->assertFalse($gate->canHostVideoCalls($basicTutor));

        $proTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->startTrial($proTutor, SubscriptionPlan::PRO);

        $this->assertTrue($gate->canRespondToRequests($proTutor));
        $this->assertTrue($gate->canAccessAnalytics($proTutor));
        $this->assertFalse($gate->canHostVideoCalls($proTutor));

        // Use up 10 responses on Pro
        for ($i = 0; $i < 10; $i++) {
            $gate->incrementResponseUsage($proTutor);
        }
        $this->assertFalse($gate->canRespondToRequests($proTutor));

        $premiumTutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service->startTrial($premiumTutor, SubscriptionPlan::PREMIUM);

        $this->assertTrue($gate->canRespondToRequests($premiumTutor));
        $this->assertTrue($gate->canAccessAnalytics($premiumTutor));
        $this->assertTrue($gate->canSyncCalendar($premiumTutor));
        $this->assertTrue($gate->canHostVideoCalls($premiumTutor));
    }

    public function test_billing_cycle_cron_command(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $service = app(SubscriptionService::class);

        $sub = $service->startTrial($tutor, SubscriptionPlan::PRO);
        // Simulate expiring in 2 days (within T-3)
        $sub->update([
            'current_period_ends_at' => now()->addDays(2),
        ]);

        $this->artisan('edusfera:subscriptions-process-billing')
            ->assertSuccessful();

        $this->assertDatabaseHas('subscription_invoices', [
            'subscription_id' => $sub->id,
            'status' => 'pending',
        ]);
    }
}
