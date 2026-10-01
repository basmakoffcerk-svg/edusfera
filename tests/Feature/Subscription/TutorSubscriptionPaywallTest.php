<?php

declare(strict_types=1);

namespace Tests\Feature\Subscription;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Filament\Pages\TutorSubscriptionPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TutorSubscriptionPaywallTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_without_subscription_cannot_access_admin_and_is_redirected(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        $response = $this->actingAs($tutor)->get('/admin');

        $response->assertRedirect(route('filament.admin.pages.tutor-subscription-page'));
        $response->assertSessionHas('subscription_required', true);
    }

    public function test_tutor_not_onboarded_cannot_access_admin_dashboard_or_lessons(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => SubscriptionStatus::TRIAL,
            'plan' => SubscriptionPlan::PRO,
            'trial_ends_at' => now()->addDays(14),
            'is_onboarded' => false,
        ]);

        $response = $this->actingAs($tutor)->get('/admin');
        $response->assertRedirect(route('filament.admin.pages.tutor-subscription-page'));

        $responseLessons = $this->actingAs($tutor)->get('/admin/lessons');
        $responseLessons->assertRedirect(route('filament.admin.pages.tutor-subscription-page'));
    }

    public function test_tutor_with_active_onboarded_subscription_can_access_admin(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => SubscriptionStatus::ACTIVE,
            'plan' => SubscriptionPlan::PRO,
            'current_period_ends_at' => now()->addMonth(),
            'is_onboarded' => true,
        ]);

        $response = $this->actingAs($tutor)->get('/admin');
        $response->assertSuccessful();
    }

    public function test_tutor_with_expired_subscription_is_blocked_by_paywall(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => SubscriptionStatus::EXPIRED,
            'plan' => SubscriptionPlan::PRO,
            'trial_ends_at' => now()->subDays(5),
            'current_period_ends_at' => now()->subDays(5),
            'is_onboarded' => true,
        ]);

        $response = $this->actingAs($tutor)->get('/admin');
        $response->assertRedirect(route('filament.admin.pages.tutor-subscription-page'));
    }

    public function test_student_and_admin_are_not_blocked_by_tutor_paywall(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
        ]);

        $responseStudent = $this->actingAs($student)->get('/admin');
        $responseStudent->assertSuccessful();

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $responseAdmin = $this->actingAs($admin)->get('/admin');
        $responseAdmin->assertSuccessful();
    }

    public function test_json_request_receives_403_with_redirect_url(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        $response = $this->actingAs($tutor)->getJson('/admin');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'subscription_required',
                'redirect' => route('filament.admin.pages.tutor-subscription-page'),
            ]);
    }

    public function test_tutor_can_activate_trial_and_complete_onboarding(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        $sub = Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => SubscriptionStatus::TRIAL,
            'plan' => SubscriptionPlan::PRO,
            'trial_ends_at' => now()->addDays(14),
            'is_onboarded' => false,
        ]);

        $this->actingAs($tutor);

        Livewire::test(TutorSubscriptionPage::class)
            ->assertSet('isOnboarding', true)
            ->call('activateTrialAndStart', 'pro')
            ->assertRedirect('/admin');

        $sub->refresh();
        $this->assertTrue($sub->is_onboarded);
    }
}
