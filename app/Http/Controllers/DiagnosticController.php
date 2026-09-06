<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\StudentGoal;
use App\Models\TutorProfile;
use App\Services\AiDiagnostic\AiDiagnosticEvaluator;
use App\Services\AiDiagnostic\RikzQuestionBank;
use App\Services\DiagnosticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticController extends Controller
{
    private const SESSION_KEY = 'diagnostic_progress';
    private const AI_RESULT_SESSION_KEY = 'ai_diagnostic_result';

    private const SUBJECTS = [
        'Математика' => 'Математика',
        'Русский язык' => 'Русский язык',
        'Белорусский язык' => 'Белорусский язык',
        'Физика' => 'Физика',
        'Английский язык' => 'Английский язык',
    ];

    private const EXAM_TYPES = ['ЦЭ 2026', 'ЦТ 2026', 'ЦЭ', 'ЦТ'];

    public function show(Request $request): View|JsonResponse
    {
        $step = (int) $request->session()->get(self::SESSION_KEY.'.step', 1);
        $data = $request->session()->get(self::SESSION_KEY, []);

        $subject = (string) $request->query('subject', $data['subject'] ?? 'Математика');
        $examType = (string) $request->query('exam_type', $request->query('examType', $data['exam_type'] ?? 'ЦТ 2026'));

        $questionBank = app(RikzQuestionBank::class);
        $questions = $questionBank->getQuestions($subject, $examType);

        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'subjects' => self::SUBJECTS,
                'exam_types' => self::EXAM_TYPES,
                'selected_subject' => $subject,
                'selected_exam_type' => $examType,
                'questions' => $questions,
            ]);
        }

        return view('diagnostic.show', [
            'step' => min(max($step, 1), 3),
            'subjects' => self::SUBJECTS,
            'examTypes' => self::EXAM_TYPES,
            'subject' => $subject,
            'examType' => $examType,
            'currentScore' => $data['current_score'] ?? 68,
            'targetScore' => $data['target_score'] ?? 85,
            'weakTopics' => $data['weak_topics'] ?? [],
            'notes' => $data['notes'] ?? '',
            'topicOptions' => $this->topicOptions($subject),
            'questions' => $questions,
            'isAuthenticated' => $request->user() !== null,
        ]);
    }

    public function questions(Request $request): JsonResponse
    {
        $subject = (string) $request->query('subject', 'Математика');
        $examType = (string) $request->query('exam_type', $request->query('examType', 'ЦТ 2026'));

        $questionBank = app(RikzQuestionBank::class);
        $questions = $questionBank->getQuestions($subject, $examType);

        return response()->json([
            'subject' => $subject,
            'exam_type' => $examType,
            'questions' => $questions,
        ]);
    }

    public function submitAnswers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:64'],
            'exam_type' => ['nullable', 'string', 'max:32'],
            'examType' => ['nullable', 'string', 'max:32'],
            'answers' => ['required', 'array'],
            'target_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'targetScore' => ['nullable', 'integer', 'min:0', 'max:100'],
            'current_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $subject = (string) $validated['subject'];
        $examType = (string) ($validated['exam_type'] ?? $validated['examType'] ?? 'ЦТ 2026');
        $targetScore = isset($validated['target_score'])
            ? (int) $validated['target_score']
            : (isset($validated['targetScore']) ? (int) $validated['targetScore'] : 85);
        $currentScore = isset($validated['current_score']) ? (int) $validated['current_score'] : null;

        $evaluator = app(AiDiagnosticEvaluator::class);
        $evalResult = $evaluator->evaluate(
            subject: $subject,
            examType: $examType,
            userAnswers: (array) $validated['answers'],
            targetScore: $targetScore,
            currentSelfScore: $currentScore,
        );

        if (!empty($validated['notes'])) {
            $evalResult['notes'] = $validated['notes'];
        }

        $session = $request->session();
        $session->put(self::AI_RESULT_SESSION_KEY, $evalResult);

        $weakTopics = array_values(array_unique(array_column($evalResult['skill_gaps'], 'topic')));
        $session->put(self::SESSION_KEY, [
            'step' => 3,
            'subject' => $subject,
            'exam_type' => $examType,
            'current_score' => $evalResult['scaled_score'],
            'target_score' => $targetScore,
            'weak_topics' => $weakTopics,
            'skill_gaps' => $evalResult['skill_gaps'],
            'notes' => $validated['notes'] ?? '',
            'answers' => $validated['answers'],
        ]);

        $user = $request->user();
        if ($user && $user->role === UserRole::Student) {
            $goal = StudentGoal::query()->firstOrCreate(
                [
                    'student_id' => $user->id,
                    'subject' => $subject,
                    'exam_type' => $examType,
                ],
                [
                    'tutor_id' => null,
                    'current_score' => $evalResult['scaled_score'],
                    'target_score' => $targetScore,
                    'status' => 'active',
                ]
            );

            $attempt = app(DiagnosticService::class)->recordAiDiagnostic(
                goal: $goal,
                studentId: $user->id,
                evalResult: $evalResult,
                answers: (array) $validated['answers'],
            );

            $evalResult['diagnostic_attempt_id'] = $attempt->id;
            $evalResult['is_saved'] = true;
        }

        return response()->json([
            'success' => true,
            'redirect' => route('diagnostic.finish'),
            'result' => $evalResult,
        ]);
    }

    public function submitStep(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'step' => ['required', 'integer', 'min:1', 'max:3'],
            'subject' => ['required_with:step', 'array'],
            'subject.*' => ['string', 'max:64'],
            'examType' => ['required_with:step', 'string', 'max:32'],
            'currentScore' => ['nullable', 'integer', 'min:0', 'max:100'],
            'targetScore' => ['nullable', 'integer', 'min:0', 'max:100'],
            'weakTopics' => ['nullable', 'array'],
            'weakTopics.*' => ['string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'skillGaps' => ['nullable', 'array'],
            'answers' => ['nullable', 'array'],
        ]);

        $step = (int) $validated['step'];
        $session = $request->session();
        $data = $session->get(self::SESSION_KEY, []);

        match ($step) {
            1 => $data = [
                'step' => 2,
                'subject' => $validated['subject'][0] ?? reset($validated['subject']),
                'exam_type' => $validated['examType'],
            ],
            2, 3 => $data = array_merge($data, [
                'step' => 3,
                'subject' => $validated['subject'][0] ?? ($data['subject'] ?? 'Математика'),
                'exam_type' => $validated['examType'] ?? ($data['exam_type'] ?? 'ЦТ 2026'),
                'current_score' => $validated['currentScore'] ?? ($data['current_score'] ?? 68),
                'target_score' => $validated['targetScore'] ?? ($data['target_score'] ?? 85),
                'weak_topics' => $validated['weakTopics'] ?? ($data['weak_topics'] ?? []),
                'skill_gaps' => $validated['skillGaps'] ?? ($data['skill_gaps'] ?? []),
                'notes' => $validated['notes'] ?? ($data['notes'] ?? ''),
                'answers' => $validated['answers'] ?? ($data['answers'] ?? []),
            ]),
            default => null,
        };

        if ($data !== null) {
            $session->put(self::SESSION_KEY, $data);
        }

        return response()->json(['success' => true, 'nextStep' => $data['step'] ?? $step]);
    }

    public function finish(Request $request): View|RedirectResponse
    {
        $aiResult = $request->session()->get(self::AI_RESULT_SESSION_KEY);
        $data = $request->session()->get(self::SESSION_KEY, []);

        $subject = $aiResult['subject'] ?? $request->query('subject', $data['subject'] ?? 'Математика');
        $examType = $aiResult['exam_type'] ?? $request->query('exam_type', $data['exam_type'] ?? 'ЦТ');
        $currentScore = (int) ($aiResult['scaled_score'] ?? $request->query('current_score', $data['current_score'] ?? 68));
        $targetScore = (int) ($aiResult['target_score'] ?? $request->query('target_score', $data['target_score'] ?? 85));

        $weakTopics = $aiResult !== null
            ? array_values(array_unique(array_column($aiResult['skill_gaps'] ?? [], 'topic')))
            : ($data['weak_topics'] ?? [
                'Логарифмические неравенства (ОДЗ)',
                'Стереометрия и сечения призмы',
                'Тригонометрический отбор корней',
            ]);

        $skillGaps = $aiResult['skill_gaps'] ?? ($data['skill_gaps'] ?? null);

        $user = $request->user();

        if ($aiResult === null && $user && $user->role === UserRole::Student && !empty($subject)) {
            $saveData = array_merge($data, [
                'subject' => $subject,
                'exam_type' => $examType,
                'current_score' => $currentScore,
                'target_score' => $targetScore,
                'weak_topics' => $weakTopics,
            ]);
            $this->saveForUser($user, $saveData);
        }

        $request->session()->forget(self::SESSION_KEY);
        $request->session()->forget(self::AI_RESULT_SESSION_KEY);

        $recommendedTutors = TutorProfile::query()
            ->with(['user', 'user.subscription'])
            ->where('is_verified', true)
            ->whereJsonContains('subjects', $subject)
            ->take(3)
            ->get();

        return view('diagnostic.result', [
            'subject' => $subject,
            'examType' => $examType,
            'currentScore' => $currentScore,
            'targetScore' => $targetScore,
            'weakTopics' => $weakTopics,
            'skillGaps' => $skillGaps,
            'aiResult' => $aiResult,
            'evalResult' => $aiResult,
            'recommendedTutors' => $recommendedTutors,
            'isSaved' => $user !== null,
        ]);
    }

    private function saveForUser($user, array $data): void
    {
        $goal = StudentGoal::query()->create([
            'student_id' => $user->id,
            'tutor_id' => null,
            'subject' => $data['subject'] ?? 'Математика',
            'exam_type' => $data['exam_type'] ?? 'ЦТ',
            'current_score' => $data['current_score'] ?? null,
            'target_score' => $data['target_score'] ?? null,
            'status' => 'active',
        ]);

        app(DiagnosticService::class)->recordBaseline(
            goal: $goal,
            studentId: $user->id,
            currentScore: $data['current_score'] ?? null,
            targetScore: $data['target_score'] ?? null,
            examDate: null,
            weakTopics: $data['weak_topics'] ?? [],
            notes: $data['notes'] ?? null,
        );
    }

    private function topicOptions(?string $subject): array
    {
        return match ($subject) {
            'Белорусский язык' => [
                'Орфография (Аканье, яканье)',
                'Правапіс спалучэнняў зычных',
                'Марфалогія і канчаткі',
                'Сінтаксіс і пунктуацыя',
                'Тэставыя пасткі РІКВ',
            ],
            'Русский язык' => [
                'Орфография (НЕ с частями речи)',
                'Правописание Н и НН',
                'Пунктуация в СПП и ССП',
                'Обособленные члены предложения',
                'Морфологические нормы РИКЗ',
            ],
            'Математика' => [
                'Свойства степеней и корней',
                'Логарифмы и ловушки ОДЗ',
                'Планиметрия и прямоугольный треугольник',
                'Тригонометрический отбор корней',
                'Показательные неравенства',
                'Стереометрия и сечения',
            ],
            'Физика' => [
                'Кинематика и законы Ньютона',
                'Законы сохранения импульса и энергии',
                'Изопроцессы идеального газа',
                'Закон Ома для полной цепи',
                'Магнитная индукция и правило Ленца',
                'Фотоэффект Эйнштейна',
            ],
            'Английский язык' => [
                'Past Perfect vs Past Simple',
                'Passive Voice & Modal Verbs',
                'Dependent Prepositions',
                'Word Formation (Prefixes & Suffixes)',
                'Sentence Restructuring',
                'Reading Comprehension Synthesis',
            ],
            default => [],
        };
    }
}
