<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrepareClassroomTestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_classroom_command_creates_student_and_lesson(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'email' => 'tutor@test.by',
        ]);

        TutorProfile::create([
            'user_id' => $tutor->id,
            'headline' => 'Test Tutor',
            'hourly_rate' => 30.00,
            'is_approved' => true,
            'is_active' => true,
        ]);

        $this->artisan('classroom:prepare-test')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'student-test@edusfera.by',
            'role' => 'student',
        ]);

        $this->assertDatabaseHas('lessons', [
            'tutor_id' => $tutor->id,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('classroom_sessions', [
            'status' => 'active',
        ]);
    }

    public function test_handles_soft_deleted_student_gracefully(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'email' => 'tutor2@test.by',
        ]);

        TutorProfile::create([
            'user_id' => $tutor->id,
            'headline' => 'Test Tutor 2',
            'hourly_rate' => 30.00,
            'is_approved' => true,
            'is_active' => true,
        ]);

        // Create student and soft-delete them
        $student = User::factory()->create([
            'email' => 'student-test@edusfera.by',
            'role' => 'student',
        ]);
        $student->delete();
        $this->assertSoftDeleted('users', ['id' => $student->id]);

        // Running command must restore student and not throw 1062 duplicate key
        $this->artisan('classroom:prepare-test')
            ->assertExitCode(0);

        $this->assertNotSoftDeleted('users', ['email' => 'student-test@edusfera.by']);
    }
}
