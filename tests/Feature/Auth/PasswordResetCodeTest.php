<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_code(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'tutor@edusfera.by',
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'tutor@edusfera.by',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        Notification::assertSentTo($user, ResetPasswordCodeNotification::class, function ($notification) {
            return strlen($notification->code) === 6 && ctype_digit($notification->code);
        });

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'tutor@edusfera.by',
        ]);
    }

    public function test_forgot_password_for_non_existent_email_returns_error(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'unknown@edusfera.by',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);

        Notification::assertNothingSent();
    }

    public function test_user_can_verify_valid_reset_code(): void
    {
        $code = '123456';
        DB::table('password_reset_tokens')->insert([
            'email' => 'student@edusfera.by',
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'student@edusfera.by',
            'code' => $code,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_verifying_invalid_code_fails(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => 'student@edusfera.by',
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'student@edusfera.by',
            'code' => '999999',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_verifying_expired_code_fails(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => 'student@edusfera.by',
            'token' => Hash::make('123456'),
            'created_at' => Carbon::now()->subMinutes(20),
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'student@edusfera.by',
            'code' => '123456',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'student@edusfera.by',
        ]);
    }

    public function test_user_can_reset_password_and_is_authenticated(): void
    {
        $user = User::factory()->create([
            'email' => 'reset.user@edusfera.by',
            'password' => Hash::make('OldPassword123'),
        ]);

        $code = '654321';
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123', $user->password));

        // Token record is cleared
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_cannot_reset_password_to_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'same.password@edusfera.by',
            'password' => Hash::make('CurrentPassword123'),
        ]);

        $code = '112233';
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'CurrentPassword123',
            'password_confirmation' => 'CurrentPassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Новый пароль не должен совпадать с текущим паролем. Придумайте другой пароль.',
        ]);
    }
}
