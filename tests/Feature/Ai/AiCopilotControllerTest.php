<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Enums\UserRole;
use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiCopilotControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_ai_copilot_chat(): void
    {
        $response = $this->postJson('/admin/ai-copilot/chat', [
            'prompt' => 'Привет',
        ]);

        $response->assertStatus(401);
    }

    public function test_tutor_can_chat_with_copilot_and_receives_answer(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Привет! Я готов помочь подготовить занятие.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
            'name' => 'Анна Репетитор',
        ]);

        TutorProfile::create([
            'user_id' => $tutor->id,
            'subject' => 'Математика',
            'hourly_rate' => 45.00,
            'is_published' => true,
        ]);

        $this->actingAs($tutor);

        $response = $this->postJson('/admin/ai-copilot/chat', [
            'prompt' => 'Составь разминку для урока по тригонометрии',
            'persona' => 'methodologist',
            'tone' => 'academic',
            'length' => 'short',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'reply' => 'Привет! Я готов помочь подготовить занятие.',
        ]);
    }

    public function test_copilot_injects_platform_context_for_student(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function ($request) {
                $body = $request->data();
                $systemInstruction = $body['systemInstruction']['parts'][0]['text'] ?? '';

                // Verify that student context is present in system instruction
                if (
                    str_contains($systemInstruction, 'Ученик') &&
                    str_contains($systemInstruction, 'Геометрия: теорема косинусов')
                ) {
                    return Http::response([
                        'candidates' => [
                            [
                                'content' => [
                                    'parts' => [
                                        ['text' => 'Твой следующий урок с репетитором по математике!'],
                                    ],
                                ],
                            ],
                        ],
                    ], 200);
                }

                return Http::response([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => 'Ответ без контекста'],
                                ],
                            ],
                        ],
                    ],
                ], 200);
            },
        ]);

        $student = User::factory()->create([
            'role' => UserRole::Student,
            'name' => 'Михаил Ученик',
        ]);

        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
            'name' => 'Дмитрий Учитель',
        ]);

        TutorProfile::create([
            'user_id' => $tutor->id,
            'subject' => 'Математика',
            'hourly_rate' => 40.00,
        ]);

        Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => 40.00,
            'platform_commission' => 4.80,
            'net_amount' => 35.20,
            'status' => Lesson::STATUS_CONFIRMED,
            'notes' => 'Геометрия: теорема косинусов',
        ]);

        HomeworkAssignment::create([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'title' => 'Задачи 10-15 по векторам',
            'status' => 'pending',
            'due_at' => now()->addDays(2),
        ]);

        $this->actingAs($student);

        $response = $this->postJson('/admin/ai-copilot/chat', [
            'prompt' => 'Когда мой следующий урок?',
            'persona' => 'default',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'reply' => 'Твой следующий урок с репетитором по математике!',
        ]);
    }

    public function test_copilot_validates_prompt(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/admin/ai-copilot/chat', [
            'prompt' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    public function test_copilot_handles_persona_rikz_expert(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function ($request) {
                $body = $request->data();
                $systemInstruction = $body['systemInstruction']['parts'][0]['text'] ?? '';

                $this->assertStringContainsString('РИКЗ', $systemInstruction);

                return Http::response([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => 'Ловушка в части А заключается в знаке корня.'],
                                ],
                            ],
                        ],
                    ],
                ], 200);
            },
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/admin/ai-copilot/chat', [
            'prompt' => 'Разбери ловушку в задании А12 по физике',
            'persona' => 'rikz_expert',
            'tone' => 'step_by_step',
            'length' => 'detailed',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'reply' => 'Ловушка в части А заключается в знаке корня.',
        ]);
    }

    public function test_copilot_handles_persona_free_chat(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function ($request) {
                $body = $request->data();
                $systemInstruction = $body['systemInstruction']['parts'][0]['text'] ?? '';

                $this->assertStringContainsString('живой образовательный брейншторм', $systemInstruction);

                return Http::response([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => 'Отличная идея для открытого обсуждения!'],
                                ],
                            ],
                        ],
                    ],
                ], 200);
            },
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/admin/ai-copilot/chat', [
            'prompt' => 'Как повысить вовлеченность старшеклассников?',
            'persona' => 'free_chat',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'reply' => 'Отличная идея для открытого обсуждения!',
        ]);
    }

    public function test_copilot_injects_platform_context_for_parent(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function ($request) {
                $body = $request->data();
                $systemInstruction = $body['systemInstruction']['parts'][0]['text'] ?? '';

                $this->assertStringContainsString('Родитель', $systemInstruction);
                $this->assertStringContainsString('Степан Ученик', $systemInstruction);

                return Http::response([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => 'У вашего ребенка запланирован урок завтра.'],
                                ],
                            ],
                        ],
                    ],
                ], 200);
            },
        ]);

        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'name' => 'Ольга Мама',
        ]);

        $student = User::factory()->create([
            'role' => UserRole::Student,
            'name' => 'Степан Ученик',
        ]);

        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
            'name' => 'Елена Учитель',
        ]);

        TutorProfile::create([
            'user_id' => $tutor->id,
            'subject' => 'Физика',
            'hourly_rate' => 45.00,
        ]);

        Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => 45.00,
            'platform_commission' => 5.00,
            'net_amount' => 40.00,
            'status' => Lesson::STATUS_CONFIRMED,
            'notes' => 'Законы Ньютона',
        ]);

        $this->actingAs($parent);

        $response = $this->postJson('/admin/ai-copilot/chat', [
            'prompt' => 'Когда следующий урок у сына?',
            'persona' => 'default',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'reply' => 'У вашего ребенка запланирован урок завтра.',
        ]);
    }

    public function test_copilot_solve_task_endpoint(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "### Пошаговое решение:\n1. ОДЗ: x > 0\n2. Ответ: 4"],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/admin/ai-copilot/solve-task', [
            'task' => 'Решите уравнение: log2(x) = 2',
            'subject' => 'Математика',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertStringContainsString('Пошаговое решение', (string) $response->json('result'));
        $this->assertStringContainsString('Ответ: 4', (string) $response->json('result'));
    }
}
