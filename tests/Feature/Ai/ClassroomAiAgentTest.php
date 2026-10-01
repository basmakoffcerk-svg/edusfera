<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Models\ClassroomNote;
use App\Models\ClassroomSession;
use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\SkillGap;
use App\Models\StudentGoal;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\Classroom\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClassroomAiAgentTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;
    private User $student;
    private Lesson $lesson;
    private ClassroomSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Алексей Репетитор',
        ]);

        TutorProfile::create([
            'user_id' => $this->tutor->id,
            'subject' => 'Математика',
            'hourly_rate' => 50,
            'bio' => 'Опытный преподаватель ЦТ/ЦЭ',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Иван Ученик',
        ]);

        $this->lesson = Lesson::forceCreate([
            'tutor_id' => $this->tutor->id,
            'student_id' => $this->student->id,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'duration_minutes' => 60,
            'price' => 50,
            'platform_commission' => 10,
            'net_amount' => 40,
            'notes' => 'Подготовка к ЦТ: Производные функции',
        ]);

        $this->session = ClassroomSession::create([
            'lesson_id' => $this->lesson->id,
            'room_id' => 'room-test-ai-uuid',
            'status' => ClassroomSession::STATUS_ACTIVE,
            'started_at' => now(),
            'meta' => [],
        ]);
    }

    public function test_ai_agent_creates_homework_assignment_via_gemini_action(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'reply' => 'Домашнее задание по производным успешно создано!',
                                        'actions' => [
                                            [
                                                'action' => 'homework.create',
                                                'payload' => [
                                                    'title' => 'Производная сложной функции',
                                                    'instructions' => 'Решить задания №10-15 из сборника РИКЗ 2026.',
                                                    'due_days' => 4,
                                                ],
                                            ],
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'http://localhost:8083/*' => Http::response(['success' => true], 200),
        ]);

        $aiService = app(AiService::class);
        $result = $aiService->chat(
            'Задай ДЗ по производным',
            $this->session->room_id,
            $this->lesson,
            $this->tutor
        );

        $this->assertSame('Домашнее задание по производным успешно создано!', $result['reply']);
        $this->assertCount(1, $result['created_entities']['homework']);

        $this->assertDatabaseHas('homework_assignments', [
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student->id,
            'tutor_id' => $this->tutor->id,
            'title' => 'Производная сложной функции',
            'source' => 'ai',
        ]);
    }

    public function test_ai_agent_creates_skill_gap_via_gemini_action(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'reply' => 'Пробел в знаниях зафиксирован.',
                                        'actions' => [
                                            [
                                                'action' => 'skill_gap.create',
                                                'payload' => [
                                                    'topic' => 'Формула производной частного',
                                                    'severity' => 'high',
                                                ],
                                            ],
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $aiService = app(AiService::class);
        $result = $aiService->chat(
            'Зафиксируй пробел по производной частного',
            $this->session->room_id,
            $this->lesson,
            $this->tutor
        );

        $this->assertCount(1, $result['created_entities']['gaps']);
        $this->assertDatabaseHas('skill_gaps', [
            'student_id' => $this->student->id,
            'topic' => 'Формула производной частного',
            'severity' => 'high',
            'status' => 'open',
        ]);
    }

    public function test_ai_agent_creates_classroom_note_via_gemini_action(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'reply' => 'Заметка сохранена.',
                                        'actions' => [
                                            [
                                                'action' => 'note.create',
                                                'payload' => [
                                                    'content' => 'Повторить правило дифференцирования произведения: (uv)\'=u\'v+uv\'',
                                                    'is_shared' => true,
                                                ],
                                            ],
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $aiService = app(AiService::class);
        $result = $aiService->chat(
            'Запиши формулу в заметки',
            $this->session->room_id,
            $this->lesson,
            $this->tutor
        );

        $this->assertCount(1, $result['created_entities']['notes']);
        $this->assertDatabaseHas('classroom_notes', [
            'classroom_session_id' => $this->session->id,
            'author_id' => $this->tutor->id,
            'content' => 'Повторить правило дифференцирования произведения: (uv)\'=u\'v+uv\'',
            'is_shared' => true,
        ]);
    }

    public function test_offline_mock_chat_parses_commands_and_creates_entities(): void
    {
        // Mock chat triggers when provider has dummy/empty credentials
        $aiService = new AiService(['provider' => 'mock', 'gemini_key' => '']);

        // 1. Homework creation via mock
        $resHw = $aiService->chat(
            'Задай домашку по стереометрии',
            $this->session->room_id,
            $this->lesson,
            $this->tutor
        );
        $this->assertStringContainsString('успешно создано', $resHw['reply']);
        $this->assertCount(1, $resHw['created_entities']['homework']);
        $this->assertDatabaseHas('homework_assignments', [
            'lesson_id' => $this->lesson->id,
            'title' => 'стереометрии',
        ]);

        // 2. Skill gap via mock
        $resGap = $aiService->chat(
            'Зафиксируй пробел: тригонометрический круг',
            $this->session->room_id,
            $this->lesson,
            $this->tutor
        );
        $this->assertStringContainsString('успешно зафиксирован', $resGap['reply']);
        $this->assertCount(1, $resGap['created_entities']['gaps']);
        $this->assertDatabaseHas('skill_gaps', [
            'student_id' => $this->student->id,
            'topic' => 'тригонометрический круг',
        ]);

        // 3. Note creation via mock
        $resNote = $aiService->chat(
            'Добавь в заметки: Разобрать теорему трех перпендикуляров',
            $this->session->room_id,
            $this->lesson,
            $this->tutor
        );
        $this->assertStringContainsString('успешно сохранена', $resNote['reply']);
        $this->assertCount(1, $resNote['created_entities']['notes']);
        $this->assertDatabaseHas('classroom_notes', [
            'classroom_session_id' => $this->session->id,
            'content' => 'Разобрать теорему трех перпендикуляров',
        ]);
    }

    public function test_classroom_controller_chat_ai_endpoint_persists_history(): void
    {
        // Create student goal and skill gap to verify context load
        StudentGoal::create([
            'student_id' => $this->student->id,
            'tutor_id' => $this->tutor->id,
            'subject' => 'Математика',
            'exam_type' => 'ЦТ',
            'current_score' => 65,
            'target_score' => 90,
            'status' => 'active',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'reply' => 'Привет! Готов разобрать сложные задачи.',
                                        'actions' => [],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->tutor)->postJson(route('classroom.ai-chat', $this->lesson), [
            'message' => 'С чего начнем повторение темы?',
            'history' => [],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'reply',
                'actions',
                'created_entities',
                'history',
            ]);

        $this->session->refresh();
        $history = $this->session->meta['ai_chat_history'] ?? [];

        $this->assertCount(2, $history); // 1 user message, 1 assistant response
        $this->assertSame('user', $history[0]['role']);
        $this->assertSame('С чего начнем повторение темы?', $history[0]['text']);
        $this->assertSame('assistant', $history[1]['role']);
        $this->assertSame('Привет! Готов разобрать сложные задачи.', $history[1]['text']);
    }
}
