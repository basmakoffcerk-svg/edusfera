<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutorAiAssistantPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_ai_assistant_page(): void
    {
        $response = $this->get('/admin/tutor-ai-assistant');
        $response->assertRedirect('/admin/login');
    }

    public function test_student_cannot_access_ai_assistant_page(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
        ]);

        $response = $this->actingAs($student)->get('/admin/tutor-ai-assistant');
        $response->assertForbidden();
    }

    public function test_tutor_with_active_subscription_can_access_ai_assistant_page(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        Subscription::create([
            'tutor_id' => $tutor->id,
            'status' => SubscriptionStatus::ACTIVE,
            'plan' => SubscriptionPlan::PRO,
            'is_onboarded' => true,
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($tutor)->get('/admin/tutor-ai-assistant');
        $response->assertOk();
        $response->assertSee('tutor-ai-assistant-root');
        $response->assertSee('window.EDUSFERA_AI_CONFIG', false);
    }

    public function test_admin_can_access_ai_assistant_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->get('/admin/tutor-ai-assistant');
        $response->assertOk();
        $response->assertSee('tutor-ai-assistant-root');
    }
}
