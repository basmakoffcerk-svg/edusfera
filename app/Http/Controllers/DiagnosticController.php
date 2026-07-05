<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\StudentGoal;
use App\Services\DiagnosticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticController extends Controller
{
    private const SESSION_KEY = 'diagnostic_progress';

    private const SUBJECTS = [
        'Белорусский язык' => 'Белорусский язык',
        'Русский язык' => 'Русский язык',
        'Математика' => 'Математика',
    ];

    private const EXAM_TYPES = ['ЦЭ', 'ЦТ'];

    public function show(Request $request): View
    {
        $step = (int) $request->session()->get(self::SESSION_KEY.'.step', 1);
        $data = $request->session()->get(self::SESSION_KEY, []);

        return view('diagnostic.show', [
            'step' => min(max($step, 1), 3),
            'subjects' => self::SUBJECTS,
            'examTypes' => self::EXAM_TYPES,
            'subject' => $data['subject'] ?? null,
            'examType' => $data['exam_type'] ?? null,
            'currentScore' => $data['current_score'] ?? null,
            'targetScore' => $data['target_score'] ?? null,
            'weakTopics' => $data['weak_topics'] ?? [],
            'notes' => $data['notes'] ?? '',
            'topicOptions' => $this->topicOptions($data['subject'] ?? null),
            'isAuthenticated' => $request->user() !== null,
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
            2 => $data = array_merge($data, [
                'step' => 3,
                'current_score' => $validated['currentScore'] ?? null,
                'target_score' => $validated['targetScore'] ?? null,
                'weak_topics' => $validated['weakTopics'] ?? [],
                'notes' => $validated['notes'] ?? '',
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
        $data = $request->session()->get(self::SESSION_KEY, []);

        if (empty($data['subject'])) {
            return redirect()->route('diagnostic.show');
        }

        $user = $request->user();

        if ($user && $user->role === UserRole::Student) {
            $this->saveForUser($user, $data);
        }

        $request->session()->forget(self::SESSION_KEY);

        return view('diagnostic.result', [
            'subject' => $data['subject'],
            'examType' => $data['exam_type'],
            'currentScore' => $data['current_score'] ?? null,
            'targetScore' => $data['target_score'] ?? null,
            'weakTopics' => $data['weak_topics'] ?? [],
            'isSaved' => $user !== null,
        ]);
    }

    private function saveForUser($user, array $data): void
    {
        $goal = StudentGoal::query()->create([
            'student_id' => $user->id,
            'tutor_id' => null,
            'subject' => $data['subject'],
            'exam_type' => $data['exam_type'],
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
                'Орфография',
                'Лексика и фразеология',
                'Морфология',
                'Синтаксис',
                'Пунктуация',
                'Тестовые ловушки ЦЭ/ЦТ',
            ],
            'Русский язык' => [
                'Орфография',
                'Пунктуация',
                'Синтаксис',
                'Сочинение и аргументация',
                'Тестовые формулировки',
            ],
            'Математика' => [
                'Алгебра',
                'Геометрия',
                'Уравнения и неравенства',
                'Текстовые задачи',
                'Тестовая стратегия',
            ],
            default => [],
        };
    }
}
