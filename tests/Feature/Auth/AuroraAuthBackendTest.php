<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\MultiAccountService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuroraAuthBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_auth_page_renders_for_guest(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    public function test_show_auth_page_redirects_authenticated_user_to_admin(): void
    {
        $user = User::factory()->create(['role' => UserRole::Student]);
        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/admin');
    }

    public function test_login_with_email_success(): void
    {
        $user = User::factory()->create([
            'email' => 'tutor.login@edusfera.by',
            'password' => Hash::make('Secret123!'),
            'role' => UserRole::Tutor,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'TUTOR.LOGIN@EDUSFERA.BY',
            'password' => 'Secret123!',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'redirect' => '/admin',
                'user' => [
                    'id' => $user->id,
                    'email' => 'tutor.login@edusfera.by',
                    'role' => 'tutor',
                ],
            ]);

        $this->assertAuthenticatedAs($user);
        $this->assertEquals($user->id, Filament::auth()->id());
    }

    public function test_login_with_phone_number_formats(): void
    {
        $user = User::factory()->create([
            'phone' => '+375291112233',
            'password' => Hash::make('Secret123!'),
            'role' => UserRole::Student,
        ]);

        // Login using without +
        $res1 = $this->postJson('/api/auth/login', [
            'email' => '375291112233',
            'password' => 'Secret123!',
        ]);
        $res1->assertOk()->assertJson(['success' => true]);

        Auth::logout();

        // Login using 80 format
        $res2 = $this->postJson('/api/auth/login', [
            'email' => '80291112233',
            'password' => 'Secret123!',
        ]);
        $res2->assertOk()->assertJson(['success' => true]);

        Auth::logout();

        // Login using formatted string
        $res3 = $this->postJson('/api/auth/login', [
            'email' => '+375 (29) 111-22-33',
            'password' => 'Secret123!',
        ]);
        $res3->assertOk()->assertJson(['success' => true]);
    }

    public function test_login_invalid_password_returns_422(): void
    {
        $user = User::factory()->create([
            'email' => 'test@edusfera.by',
            'password' => Hash::make('CorrectPassword1'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@edusfera.by',
            'password' => 'WrongPassword1',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
        $this->assertGuest();
    }

    public function test_login_non_existent_user_returns_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@edusfera.by',
            'password' => 'AnyPassword1',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
        $this->assertGuest();
    }

    public function test_register_student(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'firstName' => 'Мария',
            'lastName' => 'Петрова',
            'email' => 'maria.student@edusfera.by',
            'phone' => '+375 (33) 123-45-67',
            'password' => 'Password123!',
            'role' => 'student',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'role' => 'student',
                'isTutor' => false,
                'redirect' => '/admin',
            ]);

        $user = User::where('email', 'maria.student@edusfera.by')->firstOrFail();
        $this->assertEquals('Мария Петрова', $user->name);
        $this->assertEquals(UserRole::Student, $user->role);
        $this->assertEquals('+375331234567', $user->phone);
        $this->assertNotNull($user->offer_accepted_at);

        $this->assertAuthenticatedAs($user);
        $this->assertEquals($user->id, Filament::auth()->id());
    }

    public function test_register_tutor_creates_profile_and_trial(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'firstName' => 'Сергей',
            'lastName' => 'Преподаватель',
            'email' => 'sergey.tutor@edusfera.by',
            'phone' => '80299876543',
            'password' => 'Password123!',
            'role' => 'tutor',
            'plan' => 'start',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'role' => 'tutor',
                'isTutor' => true,
                'redirect' => '/admin',
            ]);

        $user = User::where('email', 'sergey.tutor@edusfera.by')->firstOrFail();
        $this->assertEquals(UserRole::Tutor, $user->role);
        $this->assertEquals('+375299876543', $user->phone);

        $profile = TutorProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);

        $sub = Subscription::where('tutor_id', $user->id)->first();
        $this->assertNotNull($sub);
        $this->assertEquals(SubscriptionPlan::START, $sub->plan);
        $this->assertEquals(SubscriptionStatus::TRIAL, $sub->status);
        $this->assertTrue($sub->isActive());

        $this->assertAuthenticatedAs($user);
        $this->assertEquals($user->id, Filament::auth()->id());
    }

    public function test_multi_account_role_labels(): void
    {
        $this->assertEquals('Администратор', MultiAccountService::roleLabel(UserRole::Admin));
        $this->assertEquals('Администратор', MultiAccountService::roleLabel('admin'));
        $this->assertEquals('Репетитор', MultiAccountService::roleLabel(UserRole::Tutor));
        $this->assertEquals('Репетитор', MultiAccountService::roleLabel('tutor'));
        $this->assertEquals('Ученик', MultiAccountService::roleLabel(UserRole::Student));
        $this->assertEquals('Ученик', MultiAccountService::roleLabel('student'));
        $this->assertEquals('Родитель', MultiAccountService::roleLabel(UserRole::Parent));
        $this->assertEquals('Родитель', MultiAccountService::roleLabel('parent'));
    }
}
