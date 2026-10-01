<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClassroomSession;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ClassroomSignalConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private User $student;

    private Lesson $lesson;

    private ClassroomSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->tutor = User::factory()->create(['role' => 'tutor']);
        $this->student = User::factory()->create(['role' => 'student']);

        $this->lesson = Lesson::forceCreate([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'duration_minutes' => 60,
            'price' => 1000,
            'platform_commission' => 200,
            'net_amount' => 800,
        ]);

        $this->session = ClassroomSession::create([
            'lesson_id' => $this->lesson->id,
            'room_id' => 'concurrency-test-room-uuid',
            'status' => ClassroomSession::STATUS_ACTIVE,
            'started_at' => now(),
        ]);
    }

    public function test_batch_ice_candidates_exchange(): void
    {
        $candidatesList = [
            ['candidate' => 'candidate:1 1 UDP 2122260223 192.168.1.100 50000 typ host', 'sdpMid' => '0', 'sdpMLineIndex' => 0],
            ['candidate' => 'candidate:2 1 UDP 2122260224 192.168.1.100 50001 typ host', 'sdpMid' => '1', 'sdpMLineIndex' => 1],
            ['candidate' => 'candidate:3 1 UDP 1686052607 198.51.100.1 55000 typ srflx', 'sdpMid' => '0', 'sdpMLineIndex' => 0],
        ];

        // Tutor sends batch of candidates in 1 request
        $res = $this->actingAs($this->tutor)->postJson(route('classroom.signal.send', $this->lesson), [
            'type' => 'candidates',
            'payload' => ['candidates' => $candidatesList],
            'client_id' => 'client_tutor_1',
            'role' => 'tutor',
        ]);

        $res->assertOk();
        $res->assertJsonStructure(['status', 'signal_id', 'server_time']);

        // Student fetches signals
        $fetch = $this->actingAs($this->student)->getJson(route('classroom.signal.get', [
            'lesson' => $this->lesson->id,
            'client_id' => 'client_student_1',
        ]));

        $fetch->assertOk();
        $signals = $fetch->json('signals');
        $this->assertCount(1, $signals);
        $this->assertSame('candidates', $signals[0]['type']);
        $this->assertCount(3, $signals[0]['payload']['candidates']);
    }

    public function test_early_signals_retained_when_peer_joins_later(): void
    {
        // 1. Tutor enters and sends hello and offer
        $this->actingAs($this->tutor)->postJson(route('classroom.signal.send', $this->lesson), [
            'type' => 'hello',
            'payload' => ['role' => 'tutor'],
            'client_id' => 'client_tutor_early',
            'role' => 'tutor',
        ]);

        $this->actingAs($this->tutor)->postJson(route('classroom.signal.send', $this->lesson), [
            'type' => 'offer',
            'payload' => ['sdp' => 'v=0...tutor-offer-content'],
            'client_id' => 'client_tutor_early',
            'role' => 'tutor',
        ]);

        // 2. Simulate 25 seconds elapsed by modifying created_at_ms to be 25 seconds ago
        $cacheKey = 'classroom_signals_'.$this->lesson->id;
        $signals = Cache::get($cacheKey, []);
        $nowMs = (int) (microtime(true) * 1000);
        foreach ($signals as &$sig) {
            $sig['created_at_ms'] = $nowMs - 25000; // 25 seconds old
        }
        unset($sig);
        Cache::put($cacheKey, $signals, now()->addHours(2));

        // 3. Student joins for the first time (since = 0)
        $studentFetch = $this->actingAs($this->student)->getJson(route('classroom.signal.get', [
            'lesson' => $this->lesson->id,
            'since' => 0,
            'client_id' => 'client_student_late',
        ]));

        $studentFetch->assertOk();
        $received = $studentFetch->json('signals');

        // Student must NOT have missed the offer and hello (with old 4s threshold this would fail)
        $this->assertCount(2, $received);
        $types = array_column($received, 'type');
        $this->assertContains('hello', $types);
        $this->assertContains('offer', $types);
    }

    public function test_restart_signal_purges_stale_signals_for_clean_negotiation(): void
    {
        // Tutor sends stale offer
        $this->actingAs($this->tutor)->postJson(route('classroom.signal.send', $this->lesson), [
            'type' => 'offer',
            'payload' => ['sdp' => 'v=0...old-offer'],
        ]);

        // Student sends stale answer
        $this->actingAs($this->student)->postJson(route('classroom.signal.send', $this->lesson), [
            'type' => 'answer',
            'payload' => ['sdp' => 'v=0...old-answer'],
        ]);

        // Tutor triggers restart
        $restartRes = $this->actingAs($this->tutor)->postJson(route('classroom.signal.send', $this->lesson), [
            'type' => 'restart',
            'payload' => ['reason' => 'ice-failed'],
        ]);
        $restartRes->assertOk();

        // Student checks signals: only the restart signal should exist
        $fetch = $this->actingAs($this->student)->getJson(route('classroom.signal.get', [
            'lesson' => $this->lesson->id,
            'since' => 0,
        ]));

        $signals = $fetch->json('signals');
        $this->assertCount(1, $signals);
        $this->assertSame('restart', $signals[0]['type']);
    }

    public function test_rapid_signal_exchange_maintains_integrity(): void
    {
        // Fire 10 rapid candidate/signal bursts
        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($this->tutor)->postJson(route('classroom.signal.send', $this->lesson), [
                'type' => 'candidate',
                'payload' => ['candidate' => "candidate:tutor-{$i}"],
                'client_id' => 'client_tutor',
            ])->assertOk();

            $this->actingAs($this->student)->postJson(route('classroom.signal.send', $this->lesson), [
                'type' => 'candidate',
                'payload' => ['candidate' => "candidate:student-{$i}"],
                'client_id' => 'client_student',
            ])->assertOk();
        }

        // Student fetches tutor's signals
        $studentFetch = $this->actingAs($this->student)->getJson(route('classroom.signal.get', [
            'lesson' => $this->lesson->id,
            'since' => 0,
            'client_id' => 'client_student',
        ]));

        $studentFetch->assertOk();
        $this->assertCount(10, $studentFetch->json('signals'));

        // Tutor fetches student's signals
        $tutorFetch = $this->actingAs($this->tutor)->getJson(route('classroom.signal.get', [
            'lesson' => $this->lesson->id,
            'since' => 0,
            'client_id' => 'client_tutor',
        ]));

        $tutorFetch->assertOk();
        $this->assertCount(10, $tutorFetch->json('signals'));
    }
}
