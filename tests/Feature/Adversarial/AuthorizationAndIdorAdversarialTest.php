<?php

declare(strict_types=1);

namespace Tests\Feature\Adversarial;

use App\Enums\UserRole;
use App\Filament\Resources\AiPromptLogResource;
use App\Filament\Resources\AiPromptResource;
use App\Filament\Resources\DisputeResource;
use App\Filament\Resources\TutorProfileResource;
use App\Models\AiPrompt;
use App\Models\AiPromptLog;
use App\Models\Dispute;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\MultiAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AuthorizationAndIdorAdversarialTest extends TestCase
{
    use RefreshDatabase;

    /**
     * DisputeResource authorization enforcement: only admin users can access dispute management.
     */
    public function test_unauthorized_student_or_tutor_is_denied_access_to_dispute_resource(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $this->actingAs($student);

        $this->assertFalse(DisputeResource::canAccess(), 'DisputeResource::canAccess() must deny student.');
        $this->assertFalse(DisputeResource::canViewAny(), 'DisputeResource::canViewAny() must deny student.');
        $this->assertFalse(DisputeResource::canCreate(), 'DisputeResource::canCreate() must deny student.');
        $this->assertFalse(DisputeResource::shouldRegisterNavigation(), 'DisputeResource navigation must be hidden for student.');

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $this->actingAs($tutor);

        $this->assertFalse(DisputeResource::canAccess(), 'DisputeResource::canAccess() must deny tutor.');
        $this->assertFalse(DisputeResource::canViewAny(), 'DisputeResource::canViewAny() must deny tutor.');
        $this->assertFalse(DisputeResource::canCreate(), 'DisputeResource::canCreate() must deny tutor.');
        $this->assertFalse(DisputeResource::shouldRegisterNavigation(), 'DisputeResource navigation must be hidden for tutor.');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $this->assertTrue(DisputeResource::canAccess(), 'DisputeResource::canAccess() must permit admin.');
        $this->assertTrue(DisputeResource::canViewAny(), 'DisputeResource::canViewAny() must permit admin.');
        $this->assertTrue(DisputeResource::canCreate(), 'DisputeResource::canCreate() must permit admin.');
        $this->assertTrue(DisputeResource::shouldRegisterNavigation(), 'DisputeResource navigation must be visible for admin.');
    }

    /**
     * AiPromptResource and AiPromptLogResource access control: only admins can access system prompts and AI logs.
     */
    public function test_non_admin_user_cannot_access_ai_prompt_resources_and_logs(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $this->actingAs($student);

        // Non-admin student cannot access
        $this->assertFalse(AiPromptResource::canAccess(), 'AiPromptResource::canAccess() must deny student.');
        $this->assertFalse(AiPromptResource::canViewAny(), 'AiPromptResource::canViewAny() must deny student.');
        $this->assertFalse(AiPromptResource::canCreate(), 'AiPromptResource::canCreate() must deny student.');
        $this->assertFalse(AiPromptResource::shouldRegisterNavigation(), 'AiPromptResource navigation must be hidden for student.');

        $this->assertFalse(AiPromptLogResource::canAccess(), 'AiPromptLogResource::canAccess() must deny student.');
        $this->assertFalse(AiPromptLogResource::canViewAny(), 'AiPromptLogResource::canViewAny() must deny student.');
        $this->assertFalse(AiPromptLogResource::shouldRegisterNavigation(), 'AiPromptLogResource navigation must be hidden for student.');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        // Admin can access
        $this->assertTrue(AiPromptResource::canAccess(), 'AiPromptResource::canAccess() must permit admin.');
        $this->assertTrue(AiPromptResource::canViewAny(), 'AiPromptResource::canViewAny() must permit admin.');
        $this->assertTrue(AiPromptResource::canCreate(), 'AiPromptResource::canCreate() must permit admin.');
        $this->assertTrue(AiPromptResource::shouldRegisterNavigation(), 'AiPromptResource navigation must be visible for admin.');

        $this->assertTrue(AiPromptLogResource::canAccess(), 'AiPromptLogResource::canAccess() must permit admin.');
        $this->assertTrue(AiPromptLogResource::canViewAny(), 'AiPromptLogResource::canViewAny() must permit admin.');
        $this->assertTrue(AiPromptLogResource::shouldRegisterNavigation(), 'AiPromptLogResource navigation must be visible for admin.');
    }

    /**
     * TutorProfileResource Eloquent Query: Admin views all, Tutor views self, non-admin non-tutor gets empty query.
     */
    public function test_student_gets_empty_tutor_profiles_in_filament_query(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $tutor1 = User::factory()->create(['role' => UserRole::Tutor]);
        $tutor2 = User::factory()->create(['role' => UserRole::Tutor]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        TutorProfile::query()->create(['user_id' => $tutor1->id, 'price_per_hour' => '30.00', 'is_verified' => true]);
        TutorProfile::query()->create(['user_id' => $tutor2->id, 'price_per_hour' => '45.00', 'is_verified' => true]);

        // Student gets 0 results
        $this->actingAs($student);
        $studentResults = TutorProfileResource::getEloquentQuery()->get();
        $this->assertCount(0, $studentResults, 'Student must not see any tutor profiles in Filament query.');

        // Tutor gets only their own profile
        $this->actingAs($tutor1);
        $tutorResults = TutorProfileResource::getEloquentQuery()->get();
        $this->assertCount(1, $tutorResults);
        $this->assertEquals($tutor1->id, $tutorResults->first()->user_id);

        // Admin gets all profiles
        $this->actingAs($admin);
        $adminResults = TutorProfileResource::getEloquentQuery()->get();
        $this->assertCount(2, $adminResults);
    }

    /**
     * Account Switching: Insecure GET requests are rejected (405 Method Not Allowed), only POST is accepted.
     */
    public function test_account_switcher_rejects_insecure_get_requests_and_requires_post(): void
    {
        $user1 = User::factory()->create(['role' => UserRole::Student]);
        $user2 = User::factory()->create(['role' => UserRole::Tutor]);

        $this->actingAs($user1);

        $cookieValue = Crypt::encrypt("{$user1->id},{$user2->id}", false);

        // Send a GET request -> Must be rejected with 405 Method Not Allowed
        $getResponse = $this->withCookie('edusfera_linked_ids', $cookieValue)
            ->get("/account/switch/{$user2->id}");

        $getResponse->assertStatus(405);

        // Active user is still user1
        $this->assertEquals($user1->id, auth()->id());

        // Send a POST request -> Successfully switches
        $postResponse = $this->withCookie('edusfera_linked_ids', $cookieValue)
            ->post("/account/switch/{$user2->id}");

        $postResponse->assertRedirect('/admin');
        $this->assertEquals($user2->id, auth()->id());
    }
}
