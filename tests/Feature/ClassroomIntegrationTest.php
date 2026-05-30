<?php

namespace Tests\Feature;

use App\Models\ClassroomChatMessage;
use App\Models\ClassroomFile;
use App\Models\ClassroomNote;
use App\Models\ClassroomSession;
use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;
    private User $student;
    private Lesson $lesson;
    private ClassroomSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);

        $this->lesson = Lesson::create([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'subject' => 'Math',
            'duration_minutes' => 60,
            'price' => 1000,
            'platform_commission' => 200,
            'net_amount' => 800,
            'tutor_earning' => 800,
        ]);

        $this->session = ClassroomSession::create([
            'lesson_id' => $this->lesson->id,
            'room_id' => 'test-room-uuid',
            'status' => ClassroomSession::STATUS_ACTIVE,
            'started_at' => now(),
        ]);
    }

    public function test_student_can_fetch_notes()
    {
        ClassroomNote::create([
            'classroom_session_id' => $this->session->id,
            'author_id' => $this->tutor->id,
            'content' => 'Test note content',
            'is_shared' => true,
        ]);

        $response = $this->actingAs($this->student)->getJson(route('classroom.notes.get', $this->lesson));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'text' => 'Test note content',
        ]);
    }

    public function test_student_can_fetch_files()
    {
        ClassroomFile::create([
            'classroom_session_id' => $this->session->id,
            'uploaded_by' => $this->tutor->id,
            'original_name' => 'test_document.pdf',
            'path' => 'classroom-files/1/test.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
        ]);

        $response = $this->actingAs($this->student)->getJson(route('classroom.files.get', $this->lesson));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'test_document.pdf',
            'size' => '1 КБ',
        ]);
    }

    public function test_student_can_fetch_chat()
    {
        ClassroomChatMessage::create([
            'classroom_session_id' => $this->session->id,
            'sender_id' => $this->tutor->id,
            'message' => 'Hello student!',
        ]);

        $response = $this->actingAs($this->student)->getJson(route('classroom.chat.get', $this->lesson));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'text' => 'Hello student!',
            'userId' => $this->tutor->id,
        ]);
    }

    public function test_student_can_fetch_homework()
    {
        HomeworkAssignment::create([
            'lesson_id' => $this->lesson->id,
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'title' => 'Math exercises',
            'status' => 'assigned',
            'source' => 'tutor',
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->getJson(route('classroom.homework.get', $this->lesson));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'title' => 'Math exercises',
            'status' => 'pending',
        ]);
    }

    public function test_ending_lesson_updates_status()
    {
        $response = $this->actingAs($this->tutor)->post(route('classroom.end', $this->lesson));

        $response->assertRedirect();
        
        $this->lesson->refresh();
        $this->session->refresh();

        $this->assertEquals(Lesson::STATUS_COMPLETED, $this->lesson->status);
        $this->assertEquals(ClassroomSession::STATUS_ENDED, $this->session->status);
    }
    
    public function test_unauthorized_user_cannot_access_classroom_api()
    {
        $stranger = User::factory()->create(['role' => 'student']);
        
        $response = $this->actingAs($stranger)->getJson(route('classroom.chat.get', $this->lesson));
        $response->assertStatus(403);
    }

    public function test_participant_can_store_chat_message()
    {
        $response = $this->actingAs($this->student)->postJson(route('classroom.chat.store', $this->lesson), [
            'message' => 'New test message',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'text' => 'New test message',
            'userId' => $this->student->id,
        ]);

        $this->assertDatabaseHas('classroom_chat_messages', [
            'classroom_session_id' => $this->session->id,
            'sender_id' => $this->student->id,
            'message' => 'New test message',
        ]);
    }

    public function test_internal_api_can_save_whiteboard_state()
    {
        $payload = [
            ['action' => 'stroke', 'data' => ['color' => '#000']],
            ['action' => 'clear', 'data' => []],
        ];

        $token = config('classroom.jwt_secret');

        $response = $this->postJson(
            route('internal.classroom.whiteboard', ['roomId' => $this->session->room_id]),
            $payload,
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertStatus(200);

        $this->session->refresh();
        $this->assertEquals($payload, $this->session->whiteboard_state);
    }
}
