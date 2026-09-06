<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Models\TutorSubscription;
use App\Enums\UserRole;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_unsupported_provider_returns_404(): void
    {
        $response = $this->get('/auth/unsupported/redirect');
        $response->assertStatus(404);

        $responseCallback = $this->get('/auth/unsupported/callback');
        $responseCallback->assertStatus(404);
    }

    public function test_google_redirect_saves_role_and_plan_in_session(): void
    {
        $response = $this->get('/auth/google/redirect?role=tutor&plan=premium');
        
        $response->assertRedirect();
        $this->assertEquals('tutor', session('oauth_role'));
        $this->assertEquals('premium', session('oauth_plan'));
    }

    public function test_google_callback_registers_new_student(): void
    {
        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-id-12345');
        $socialiteUser->shouldReceive('getEmail')->andReturn('student.test@gmail.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Анна Смирнова');
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar123');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->withSession(['oauth_role' => 'student'])
            ->get('/auth/google/callback');

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();

        $user = User::where('email', 'student.test@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Анна Смирнова', $user->name);
        $this->assertEquals('google-id-12345', $user->google_id);
        $this->assertEquals('https://lh3.googleusercontent.com/avatar123', $user->avatar);
        $this->assertEquals(UserRole::Student, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->offer_accepted_at);
    }

    public function test_yandex_is_disabled_and_returns_404(): void
    {
        $response = $this->get('/auth/yandex/redirect');
        $response->assertStatus(404);

        $responseCallback = $this->get('/auth/yandex/callback');
        $responseCallback->assertStatus(404);
    }

    public function test_google_callback_registers_new_tutor_with_trial(): void
    {
        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-id-99999');
        $socialiteUser->shouldReceive('getEmail')->andReturn('tutor.pro@gmail.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Дмитрий Репетитор');
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar456');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->withSession([
            'oauth_role' => 'tutor',
            'oauth_plan' => 'pro',
        ])->get('/auth/google/callback');

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();

        $user = User::where('email', 'tutor.pro@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Дмитрий Репетитор', $user->name);
        $this->assertEquals('google-id-99999', $user->google_id);
        $this->assertEquals(UserRole::Tutor, $user->role);

        // Verify Tutor Profile created
        $this->assertDatabaseHas('tutor_profiles', [
            'user_id' => $user->id,
        ]);

        // Verify Subscription Trial created
        $this->assertDatabaseHas('subscriptions', [
            'tutor_id' => $user->id,
            'plan' => 'pro',
            'status' => 'trial',
        ]);
    }

    public function test_google_callback_links_existing_user_by_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing.user@gmail.com',
            'google_id' => null,
            'role' => UserRole::Student,
        ]);

        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-id-55555');
        $socialiteUser->shouldReceive('getEmail')->andReturn('existing.user@gmail.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Existing User');
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://avatar.url');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($existingUser);

        $existingUser->refresh();
        $this->assertEquals('google-id-55555', $existingUser->google_id);
    }
}
