<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DiagnosticAttempt;
use App\Models\ProgressSnapshot;
use App\Models\SkillGap;
use App\Models\StudentGoal;
use App\Models\User;
use App\Services\AiDiagnostic\AiDiagnosticEvaluator;
use App\Services\AiDiagnostic\RikzQuestionBank;
use App\Services\DiagnosticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_bank_supports_all_five_subjects(): void
    {
        $bank = new RikzQuestionBank();
        $expectedSubjects = [
            'Математика',
            'Русский язык',
            'Белорусский язык',
            'Физика',
            'Английский язык',
        ];

        $this->assertEquals($expectedSubjects, $bank->getSupportedSubjects());

        foreach ($expectedSubjects as $subject) {
            $questions = $bank->getQuestions($subject, 'ЦТ 2026');
            $this->assertNotEmpty($questions, "Questions for {$subject} should not be empty");
            $this->assertGreaterThanOrEqual(4, count($questions));

            $first = $questions[0];
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('text', $first);
            $this->assertArrayHasKey('topic', $first);
            $this->assertArrayHasKey('section', $first);
            $this->assertArrayHasKey('difficulty', $first);
            $this->assertArrayHasKey('correct_answer', $first);
            $this->assertArrayHasKey('explanation', $first);
            $this->assertArrayHasKey('trap_analysis', $first);
            $this->assertArrayHasKey('cognitive_bias', $first);

            $single = $bank->getQuestion($subject, $first['id']);
            $this->assertNotNull($single);
            $this->assertSame($first['id'], $single['id']);
        }
    }

    public function test_evaluator_calculates_scores_gaps_cognitive_profile_and_plans(): void
    {
        $bank = new RikzQuestionBank();
        $evaluator = new AiDiagnosticEvaluator($bank);

        // In Math: math_01 ans=2, math_02 ans=1, math_03 ans=1, math_04 ans=2, math_b01 ans=384, math_b02 ans=20
        $perfectAnswers = [
            'math_01' => '2',
            'math_02' => '1',
            'math_03' => '1',
            'math_04' => '2',
            'math_b01' => '384',
            'math_b02' => '20',
        ];

        $perfectResult = $evaluator->evaluate('Математика', 'ЦТ 2026', $perfectAnswers, 90);
        $this->assertSame(100, $perfectResult['scaled_score']);
        $this->assertSame(6, $perfectResult['correct_count']);
        $this->assertSame(0, $perfectResult['incorrect_count']);
        $this->assertEmpty($perfectResult['skill_gaps']);
        $this->assertGreaterThanOrEqual(90, $perfectResult['pass_probability_percent']);

        // Now test partial answers with errors
        $mixedAnswers = [
            'math_01' => '3', // wrong (chose distractor -2)
            'math_02' => '1', // correct
            'math_03' => '1', // correct
            'math_04' => '4', // wrong (chose distractor 1.5)
            'math_b01' => '1152', // wrong (forgot 1/3)
            'math_b02' => '20', // correct
        ];

        $mixedResult = $evaluator->evaluate('Математика', 'ЦТ 2026', $mixedAnswers, 85);
        $this->assertSame(3, $mixedResult['correct_count']);
        $this->assertSame(3, $mixedResult['incorrect_count']);
        $this->assertGreaterThan(0, $mixedResult['scaled_score']);
        $this->assertLessThan(100, $mixedResult['scaled_score']);
        $this->assertNotEmpty($mixedResult['skill_gaps']);

        // Check skill gap fields
        $gap = $mixedResult['skill_gaps'][0];
        $this->assertArrayHasKey('topic', $gap);
        $this->assertArrayHasKey('severity', $gap);
        $this->assertArrayHasKey('potential_score_loss', $gap);
        $this->assertArrayHasKey('trap_analysis', $gap);

        // Check cognitive profile
        $this->assertNotEmpty($mixedResult['cognitive_profile']);
        $this->assertArrayHasKey('title', $mixedResult['cognitive_profile'][0]);
        $this->assertArrayHasKey('advice', $mixedResult['cognitive_profile'][0]);

        // Check study plan
        $this->assertArrayHasKey('30_days', $mixedResult['study_plan']);
        $this->assertArrayHasKey('60_days', $mixedResult['study_plan']);
        $this->assertArrayHasKey('90_days', $mixedResult['study_plan']);

        // Check tutor recommendations
        $this->assertNotEmpty($mixedResult['tutor_recommendations']['recommended_focus_topics']);
        $this->assertGreaterThan(0, $mixedResult['tutor_recommendations']['weekly_hours']);
    }

    public function test_diagnostic_service_record_ai_diagnostic(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375297111001',
        ]);

        $goal = StudentGoal::query()->create([
            'student_id' => $student->id,
            'tutor_id' => null,
            'subject' => 'Русский язык',
            'exam_type' => 'ЦЭ 2026',
            'status' => 'active',
        ]);

        $bank = new RikzQuestionBank();
        $evaluator = new AiDiagnosticEvaluator($bank);
        $answers = [
            'rus_01' => '1',
            'rus_02' => '1', // wrong
            'rus_03' => '1',
            'rus_b01' => 'эффективное',
            'rus_b02' => 'красивее',
        ];

        $evalResult = $evaluator->evaluate('Русский язык', 'ЦЭ 2026', $answers, 80);

        $service = app(DiagnosticService::class);
        $attempt = $service->recordAiDiagnostic($goal, $student->id, $evalResult, $answers);

        $this->assertInstanceOf(DiagnosticAttempt::class, $attempt);
        $this->assertSame('ai_diagnostic', $attempt->source);
        $this->assertSame($evalResult['scaled_score'], $attempt->score);

        $this->assertDatabaseHas('diagnostic_attempts', [
            'id' => $attempt->id,
            'student_id' => $student->id,
            'source' => 'ai_diagnostic',
            'score' => $evalResult['scaled_score'],
        ]);

        // Check SkillGap creation
        $this->assertDatabaseHas('skill_gaps', [
            'diagnostic_attempt_id' => $attempt->id,
            'student_id' => $student->id,
            'topic' => 'Н и НН в суффиксах частей речи',
            'status' => 'open',
        ]);

        // Check ProgressSnapshot creation
        $this->assertDatabaseHas('progress_snapshots', [
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'current_score' => $evalResult['scaled_score'],
        ]);

        // Check Goal updated
        $goal->refresh();
        $this->assertSame($evalResult['scaled_score'], $goal->current_score);
    }

    public function test_controller_show_endpoint(): void
    {
        $response = $this->get('/diagnostic');
        $response->assertOk();
        $response->assertSee('Математика');
        $response->assertSee('Русский язык');
        $response->assertSee('Белорусский язык');
        $response->assertSee('Физика');
        $response->assertSee('Английский язык');

        // Test JSON request
        $jsonResponse = $this->getJson('/diagnostic?subject=' . urlencode('Физика') . '&exam_type=' . urlencode('ЦТ 2026'));
        $jsonResponse->assertOk();
        $jsonResponse->assertJsonStructure([
            'subjects',
            'exam_types',
            'selected_subject',
            'selected_exam_type',
            'questions',
        ]);
        $jsonResponse->assertJson([
            'selected_subject' => 'Физика',
        ]);
    }

    public function test_controller_questions_endpoint(): void
    {
        $response = $this->getJson('/diagnostic/questions?subject=' . urlencode('Белорусский язык') . '&exam_type=' . urlencode('ЦТ 2026'));
        $response->assertOk();
        $response->assertJsonStructure([
            'subject',
            'exam_type',
            'questions',
        ]);
        $this->assertSame('Белорусский язык', $response->json('subject'));
        $this->assertNotEmpty($response->json('questions'));
    }

    public function test_controller_submit_answers_and_finish_guest(): void
    {
        $payload = [
            'subject' => 'Английский язык',
            'exam_type' => 'ЦТ 2026',
            'target_score' => 85,
            'answers' => [
                'eng_01' => '2',
                'eng_02' => '2',
                'eng_03' => '4',
                'eng_b01' => 'reduction',
                'eng_b02' => 'off',
            ],
            'notes' => 'Хочу 85+ для поступления',
        ];

        $response = $this->postJson('/diagnostic/answers', $payload);
        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertSame(100, $response->json('result.scaled_score'));

        // Visit finish page
        $finishResponse = $this->get('/diagnostic/result');
        $finishResponse->assertOk();
        $finishResponse->assertSee('100');
        $finishResponse->assertSee('Английский язык');
    }

    public function test_controller_submit_answers_authenticated_student_persists_to_db(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375297222002',
        ]);

        $payload = [
            'subject' => 'Физика',
            'exam_type' => 'ЦТ 2026',
            'target_score' => 90,
            'answers' => [
                'phys_01' => '2',
                'phys_02' => '1',
                'phys_03' => '2',
                'phys_b01' => '831',
                'phys_b02' => '24', // wrong (distractor)
            ],
        ];

        $response = $this->actingAs($student)->postJson('/diagnostic/answers', $payload);
        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('student_goals', [
            'student_id' => $student->id,
            'subject' => 'Физика',
        ]);

        $this->assertDatabaseHas('diagnostic_attempts', [
            'student_id' => $student->id,
            'subject' => 'Физика',
            'source' => 'ai_diagnostic',
        ]);

        $this->assertDatabaseHas('skill_gaps', [
            'student_id' => $student->id,
            'topic' => 'Колебательный контур (формула Томсона)',
            'status' => 'open',
        ]);
    }
}
