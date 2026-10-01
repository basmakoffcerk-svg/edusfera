<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClassroomSession;
use App\Models\Lesson;
use App\Models\User;
use App\Services\ClassroomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ClassroomSignalTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_and_student_can_exchange_webrtc_signals(): void
    {
        Cache::flush();

        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);

        $lesson = Lesson::forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'duration_minutes' => 60,
            'price' => 1000,
            'platform_commission' => 200,
            'net_amount' => 800,
        ]);

        $session = ClassroomSession::create([
            'lesson_id' => $lesson->id,
            'room_id' => 'signal-test-room-uuid',
            'status' => ClassroomSession::STATUS_ACTIVE,
            'started_at' => now(),
        ]);

        // 1. Tutor sends offer
        $response = $this->actingAs($tutor)->postJson(route('classroom.signal.send', $lesson), [
            'type' => 'offer',
            'payload' => ['sdp' => 'v=0\r\no=tutor 123...'],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['status', 'signal_id', 'server_time']);

        // 2. Student fetches signals
        $studentFetch = $this->actingAs($student)->getJson(route('classroom.signal.get', $lesson));
        $studentFetch->assertOk();
        $signals = $studentFetch->json('signals');
        $this->assertCount(1, $signals);
        $this->assertSame('offer', $signals[0]['type']);
        $this->assertSame('tutor', $signals[0]['sender_role']);
        $this->assertSame($tutor->id, $signals[0]['sender_id']);

        // 3. Tutor should NOT see their own signal
        $tutorFetch = $this->actingAs($tutor)->getJson(route('classroom.signal.get', $lesson));
        $tutorFetch->assertOk();
        $this->assertCount(0, $tutorFetch->json('signals'));

        // 4. Student sends answer
        $answerResponse = $this->actingAs($student)->postJson(route('classroom.signal.send', $lesson), [
            'type' => 'answer',
            'payload' => ['sdp' => 'v=0\r\no=student 456...'],
        ]);
        $answerResponse->assertOk();

        // 5. Tutor fetches student answer
        $tutorFetchAfter = $this->actingAs($tutor)->getJson(route('classroom.signal.get', ['lesson' => $lesson->id, 'since' => 0]));
        $tutorFetchAfter->assertOk();
        $tutorSignals = $tutorFetchAfter->json('signals');
        $this->assertCount(1, $tutorSignals);
        $this->assertSame('answer', $tutorSignals[0]['type']);
        $this->assertSame('student', $tutorSignals[0]['sender_role']);

        // 6. Whiteboard state save & get
        $saveWb = $this->actingAs($tutor)->postJson(route('classroom.whiteboard.save', $lesson), [
            'state' => [
                'strokes' => [
                    ['tool' => 'pen', 'color' => '#7D39EB', 'points' => [[10, 10], [20, 20]]],
                ],
            ],
        ]);
        $saveWb->assertOk();

        $getWb = $this->actingAs($student)->getJson(route('classroom.whiteboard.get', $lesson));
        $getWb->assertOk();
        $this->assertNotEmpty($getWb->json('state'));
    }

    public function test_ice_servers_generation_includes_turn_and_tcp_transport(): void
    {
        config([
            'classroom.turn.url' => 'turn:turn.edusfera.by:3478',
            'classroom.turn.username' => 'test-user',
            'classroom.turn.credential' => 'test-pass',
        ]);

        $service = app(ClassroomService::class);
        $servers = $service->getIceServers();

        $this->assertNotEmpty($servers);

        // Find TURN entry
        $turnEntry = collect($servers)->first(function ($s) {
            $urls = (array) ($s['urls'] ?? []);

            return collect($urls)->contains(fn ($u) => str_starts_with($u, 'turn:'));
        });

        $this->assertNotNull($turnEntry);
        $this->assertSame('test-user', $turnEntry['username']);
        $this->assertSame('test-pass', $turnEntry['credential']);

        // Must include both UDP and TCP fallback for mobile 4G/LTE traversal
        $urls = (array) $turnEntry['urls'];
        $this->assertTrue(collect($urls)->contains('turn:turn.edusfera.by:3478'));
        $this->assertTrue(collect($urls)->contains('turn:turn.edusfera.by:3478?transport=tcp'));
    }
}
