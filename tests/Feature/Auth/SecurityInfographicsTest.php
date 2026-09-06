<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityInfographicsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Topic 03: IDOR Defense
     * Verifies that User A cannot access checkout/lesson belonging to User B.
     */
    public function test_idor_user_cannot_access_another_users_checkout(): void
    {
        $studentA = User::factory()->create(['role' => UserRole::Student]);
        $studentB = User::factory()->create(['role' => UserRole::Student]);
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $lesson = new Lesson([
            'student_id' => $studentA->id,
            'tutor_id' => $tutor->id,
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'start_time' => now('UTC')->addDay(),
            'end_time' => now('UTC')->addDay()->addHour(),
            'payment_lock_expires_at' => now('UTC')->addMinutes(15),
        ]);
        $lesson->price = 50.00;
        $lesson->platform_commission = 5.00;
        $lesson->net_amount = 45.00;
        $lesson->save();

        // Student B attempts to access Student A's checkout page
        $this->actingAs($studentB)
            ->get(route('checkout.show', $lesson))
            ->assertStatus(403);
    }

    /**
     * Topic 05: Broken Access Control Defense
     * Verifies that server-side access control blocks non-admin users from site-admin panel regardless of UI state.
     */
    public function test_broken_access_control_server_side_role_check(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        // Attempt direct access to site-admin page
        $this->actingAs($student)
            ->get('/site-admin')
            ->assertStatus(403);
    }

    /**
     * Topic 06: SQL Injection Defense
     * Verifies that user search input containing SQL injection payloads is safely sanitized & parameterized.
     */
    public function test_sql_injection_search_payload_handled_safely(): void
    {
        $user = User::factory()->create(['role' => UserRole::Student]);
        $payload = "' OR '1'='1'; DROP TABLE users; --";

        $response = $this->get(route('tutors.index', ['q' => $payload]));

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /**
     * Topic 03: Secret Keys in Environment
     * Verifies that sensitive application keys and configurations are set from server environment.
     */
    public function test_environment_secrets_are_configured(): void
    {
        $this->assertNotEmpty(config('app.key'));
    }
}
