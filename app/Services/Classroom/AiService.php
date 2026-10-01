<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use App\Models\ClassroomNote;
use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\SkillGap;
use App\Models\StudentGoal;
use App\Models\User;
use App\Services\Ai\GeminiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiService
{
    private array $settings = [];

    public function __construct(?array $settings = null)
    {
        if ($settings !== null) {
            $this->settings = $settings;
        } else {
            $this->loadSettings();
        }
    }

    private function loadSettings(): void
    {
        $path = storage_path('app/ai_settings.json');
        if (file_exists($path)) {
            try {
                $this->settings = json_decode(file_get_contents($path), true) ?: [];
            } catch (\Throwable $e) {
                $this->settings = [];
            }
        }
    }

    /**
     * Process prompt, execute platform entities and workspace actions,
     * and return structured result.
     *
     * @return array{reply: string, actions: array, created_entities: array}
     */
    public function chat(
        string $message,
        string $roomId,
        ?Lesson $lesson = null,
        ?User $user = null,
        array $history = []
    ): array {
        $provider = $this->settings['provider'] ?? config('services.ai.default_provider', 'google');
        $openaiKey = $this->settings['openai_key'] ?? config('services.ai.openai_key', '');
        $anthropicKey = $this->settings['anthropic_key'] ?? config('services.ai.anthropic_key', '');
        $geminiKey = $this->settings['gemini_key'] ?? config('services.gemini.api_key', '');
        $model = $this->settings['premium_model'] ?? config('services.gemini.model', 'gemini-flash-latest');

        $systemPrompt = $this->getSystemPrompt($lesson, $user);

        // 1. Google Gemini Provider (Primary)
        if ($provider === 'google' || $provider === 'gemini' || (! empty($geminiKey) && empty($openaiKey) && empty($anthropicKey))) {
            try {
                $gemini = app(GeminiService::class);
                if ($gemini->isConfigured()) {
                    $aiData = [];

                    // If conversation history is provided, perform multi-turn structured chat
                    if (! empty($history)) {
                        $messages = [];
                        foreach ($history as $h) {
                            $r = ($h['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
                            $c = trim((string) ($h['content'] ?? $h['text'] ?? ''));
                            if ($c !== '') {
                                $messages[] = ['role' => $r, 'content' => $c];
                            }
                        }
                        $messages[] = ['role' => 'user', 'content' => $message];

                        $aiData = $gemini->chatJson($messages, $systemPrompt, $model);
                    }

                    // Fallback to single turn JSON if no history or chatJson returned empty
                    if (empty($aiData)) {
                        $aiData = $gemini->generateJson($message, $systemPrompt, $model);
                    }

                    if (! empty($aiData) && (isset($aiData['reply']) || isset($aiData['actions']))) {
                        return $this->handleAiResultArray($aiData, $roomId, $lesson, $user);
                    }

                    $textReply = $gemini->generateText($message, $systemPrompt, $model);
                    if (! empty(trim($textReply))) {
                        return [
                            'reply' => trim($textReply),
                            'actions' => [],
                            'created_entities' => [],
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Gemini AI Classroom Service error: '.$e->getMessage());
            }
        }

        $activeKey = $provider === 'openai' ? $openaiKey : $anthropicKey;
        $isMock = empty($activeKey) || str_contains($activeKey, '•••');

        if ($isMock) {
            return $this->processMockChat($message, $roomId, $lesson, $user);
        }

        try {
            if ($provider === 'openai') {
                $messages = [['role' => 'system', 'content' => $systemPrompt]];
                foreach ($history as $h) {
                    $r = ($h['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
                    $c = trim((string) ($h['content'] ?? $h['text'] ?? ''));
                    if ($c !== '') {
                        $messages[] = ['role' => $r, 'content' => $c];
                    }
                }
                $messages[] = ['role' => 'user', 'content' => $message];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$openaiKey,
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.2,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['choices'][0]['message']['content'] ?? '{}';

                    return $this->handleAiResult($content, $roomId, $lesson, $user);
                }
            } elseif ($provider === 'anthropic') {
                $messages = [];
                foreach ($history as $h) {
                    $r = ($h['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
                    $c = trim((string) ($h['content'] ?? $h['text'] ?? ''));
                    if ($c !== '') {
                        $messages[] = ['role' => $r, 'content' => $c];
                    }
                }
                $messages[] = ['role' => 'user', 'content' => $message];

                $response = Http::withHeaders([
                    'x-api-key' => $anthropicKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model' => $model,
                    'max_tokens' => 1500,
                    'system' => $systemPrompt,
                    'messages' => $messages,
                    'temperature' => 0.2,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['content'][0]['text'] ?? '{}';

                    return $this->handleAiResult($content, $roomId, $lesson, $user);
                }
            }
        } catch (\Throwable $e) {
            Log::error('AI Service API error, falling back to mock: '.$e->getMessage());
        }

        return $this->processMockChat($message, $roomId, $lesson, $user);
    }

    private function handleAiResultArray(
        array $parsed,
        string $roomId,
        ?Lesson $lesson = null,
        ?User $user = null
    ): array {
        try {
            $actions = $parsed['actions'] ?? [];
            $reply = $parsed['reply'] ?? 'Команда обработана.';

            $execution = $this->handleActions($actions, $roomId, $lesson, $user);

            return [
                'reply' => $reply,
                'actions' => $execution['executed_actions'],
                'created_entities' => $execution['created_entities'],
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to handle AI actions: '.$e->getMessage());

            return [
                'reply' => 'Произошла ошибка при обработке команд ИИ.',
                'actions' => [],
                'created_entities' => [],
            ];
        }
    }

    private function handleAiResult(
        string $jsonString,
        string $roomId,
        ?Lesson $lesson = null,
        ?User $user = null
    ): array {
        try {
            $parsed = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($parsed)) {
                return [
                    'reply' => 'Извините, не удалось распознать формат ответа ИИ.',
                    'actions' => [],
                    'created_entities' => [],
                ];
            }

            return $this->handleAiResultArray($parsed, $roomId, $lesson, $user);
        } catch (\Throwable $e) {
            Log::error('Failed to parse AI JSON: '.$e->getMessage());

            return [
                'reply' => 'Произошла ошибка при разборе ответа ИИ.',
                'actions' => [],
                'created_entities' => [],
            ];
        }
    }

    /**
     * Separate platform entities (Homework, SkillGap, Note) and execute them against the DB,
     * while forwarding workspace actions (Kanban) to the workspace service.
     */
    private function handleActions(
        array $actions,
        string $roomId,
        ?Lesson $lesson = null,
        ?User $user = null
    ): array {
        $createdEntities = [
            'homework' => [],
            'gaps' => [],
            'resolved_gaps' => [],
            'notes' => [],
            'goals' => [],
            'reports' => [],
        ];
        $workspaceActions = [];
        $executedActions = [];

        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            $act = (string) ($action['action'] ?? $action['type'] ?? '');
            $payload = is_array($action['payload'] ?? null) ? $action['payload'] : [];

            // ── Platform Entity: Homework Assignment ──
            if ($act === 'homework.create') {
                if ($lesson && $lesson->student_id && $lesson->tutor_id) {
                    try {
                        $title = trim((string) ($payload['title'] ?? 'Домашнее задание по уроку'));
                        $instructions = trim((string) ($payload['instructions'] ?? ''));
                        $dueDays = max(1, (int) ($payload['due_days'] ?? 3));
                        $dueAt = Carbon::now('UTC')->addDays($dueDays)->setTime(23, 59, 0);

                        $studentGoalId = null;
                        $goal = StudentGoal::query()
                            ->where('student_id', $lesson->student_id)
                            ->where('status', 'active')
                            ->latest('id')
                            ->first();
                        $studentGoalId = $goal?->id;

                        $hw = HomeworkAssignment::query()->create([
                            'lesson_id' => $lesson->id,
                            'student_id' => $lesson->student_id,
                            'tutor_id' => $lesson->tutor_id,
                            'student_goal_id' => $studentGoalId,
                            'title' => mb_substr($title, 0, 160),
                            'instructions' => $instructions !== '' ? $instructions : null,
                            'source' => 'ai',
                            'status' => 'assigned',
                            'assigned_at' => Carbon::now('UTC'),
                            'due_at' => $dueAt,
                        ]);

                        $createdEntities['homework'][] = [
                            'id' => $hw->id,
                            'title' => $hw->title,
                            'due_at' => $hw->due_at?->toDateString(),
                        ];
                        $executedActions[] = [
                            'action' => 'homework.create',
                            'payload' => ['id' => $hw->id, 'title' => $hw->title],
                        ];

                        // Automatically add card to Kanban board
                        $workspaceActions[] = [
                            'action' => 'card.add',
                            'payload' => [
                                'columnId' => 'col-hw-ai',
                                'card' => [
                                    'id' => 'card-hw-'.$hw->id,
                                    'type' => 'checklist',
                                    'title' => 'ДЗ: '.$hw->title,
                                    'content' => $hw->instructions ?? 'Выполнить к '.$hw->due_at?->format('d.m.Y'),
                                    'items' => [
                                        ['id' => 'hw-1', 'text' => 'Изучить материалы задания', 'completed' => false],
                                        ['id' => 'hw-2', 'text' => 'Решить практическую часть', 'completed' => false],
                                    ],
                                ],
                            ],
                        ];
                    } catch (\Throwable $e) {
                        Log::error('Failed to create HomeworkAssignment via AI: '.$e->getMessage());
                    }
                }

                continue;
            }

            // ── Platform Entity: Skill Gap (Create) ──
            if ($act === 'skill_gap.create') {
                if ($lesson && $lesson->student_id) {
                    try {
                        $topic = trim((string) ($payload['topic'] ?? ''));
                        if ($topic !== '') {
                            $severity = in_array($payload['severity'] ?? '', ['low', 'medium', 'high'], true) ? $payload['severity'] : 'medium';
                            $subject = $lesson->tutor?->tutorProfile?->subject ?? ($lesson->notes ?: 'Школьная программа');

                            $studentGoalId = null;
                            $goal = StudentGoal::query()
                                ->where('student_id', $lesson->student_id)
                                ->where('status', 'active')
                                ->latest('id')
                                ->first();

                            if (! $goal && $lesson->student_id) {
                                $goal = StudentGoal::query()->create([
                                    'student_id' => $lesson->student_id,
                                    'tutor_id' => $lesson->tutor_id,
                                    'subject' => mb_substr($subject, 0, 100),
                                    'exam_type' => 'ЦТ/ЦЭ',
                                    'current_score' => 50,
                                    'target_score' => 85,
                                    'status' => 'active',
                                ]);
                            }
                            $studentGoalId = $goal?->id;

                            $gap = SkillGap::query()->create([
                                'student_id' => $lesson->student_id,
                                'student_goal_id' => $studentGoalId,
                                'subject' => mb_substr($subject, 0, 100),
                                'topic' => mb_substr($topic, 0, 190),
                                'severity' => $severity,
                                'status' => 'open',
                                'last_detected_at' => Carbon::now('UTC'),
                                'evidence' => [
                                    'source' => 'classroom_ai',
                                    'lesson_id' => $lesson->id,
                                    'created_at' => Carbon::now('UTC')->toIso8601String(),
                                ],
                            ]);

                            $createdEntities['gaps'][] = [
                                'id' => $gap->id,
                                'topic' => $gap->topic,
                                'severity' => $gap->severity,
                            ];
                            $executedActions[] = [
                                'action' => 'skill_gap.create',
                                'payload' => ['id' => $gap->id, 'topic' => $gap->topic],
                            ];
                        }
                    } catch (\Throwable $e) {
                        Log::error('Failed to create SkillGap via AI: '.$e->getMessage());
                    }
                }

                continue;
            }

            // ── Platform Entity: Skill Gap (Resolve) ──
            if ($act === 'skill_gap.resolve') {
                if ($lesson && $lesson->student_id) {
                    try {
                        $topic = trim((string) ($payload['topic'] ?? ''));
                        $gapId = $payload['id'] ?? $payload['gap_id'] ?? null;

                        $query = SkillGap::query()
                            ->where('student_id', $lesson->student_id)
                            ->where('status', 'open');

                        $gap = null;
                        if (! empty($gapId)) {
                            $gap = (clone $query)->where('id', $gapId)->first();
                        }

                        if (! $gap && $topic !== '') {
                            $cleanTopic = trim(preg_replace('/^(по теме|по|в|на тему)\s+/ui', '', $topic));
                            $gap = (clone $query)
                                ->where(function ($q) use ($topic, $cleanTopic) {
                                    $q->where('topic', 'like', '%'.$topic.'%')
                                        ->orWhere('topic', 'like', '%'.$cleanTopic.'%')
                                        ->orWhereRaw('LOWER(topic) = ?', [mb_strtolower($cleanTopic)]);
                                })
                                ->first();

                            if (! $gap) {
                                $openGaps = (clone $query)->get();
                                foreach ($openGaps as $candidate) {
                                    $candLower = mb_strtolower($candidate->topic);
                                    $topicLower = mb_strtolower($cleanTopic);
                                    if (str_contains($candLower, $topicLower) || str_contains($topicLower, $candLower) || (mb_strlen($candLower) >= 4 && mb_substr($topicLower, 0, 4) === mb_substr($candLower, 0, 4))) {
                                        $gap = $candidate;
                                        break;
                                    }
                                }
                            }
                        }

                        if (! $gap && empty($gapId)) {
                            $gap = (clone $query)->latest('id')->first();
                        }

                        if ($gap) {
                            $gap->update([
                                'status' => 'resolved',
                            ]);

                            $createdEntities['resolved_gaps'][] = [
                                'id' => $gap->id,
                                'topic' => $gap->topic,
                                'status' => 'resolved',
                            ];
                            $executedActions[] = [
                                'action' => 'skill_gap.resolve',
                                'payload' => ['id' => $gap->id, 'topic' => $gap->topic],
                            ];
                        }
                    } catch (\Throwable $e) {
                        Log::error('Failed to resolve SkillGap via AI: '.$e->getMessage());
                    }
                }

                continue;
            }

            // ── Platform Entity: Student Goal (Update / Set) ──
            if ($act === 'student_goal.update' || $act === 'student_goal.create') {
                if ($lesson && $lesson->student_id) {
                    try {
                        $targetScore = isset($payload['target_score']) ? (int) $payload['target_score'] : null;
                        $currentScore = isset($payload['current_score']) ? (int) $payload['current_score'] : null;
                        $subject = trim((string) ($payload['subject'] ?? ''));
                        if ($subject === '') {
                            $subject = $lesson->tutor?->tutorProfile?->subject ?? ($lesson->notes ?: 'Школьная программа');
                        }

                        $goal = StudentGoal::query()
                            ->where('student_id', $lesson->student_id)
                            ->where('status', 'active')
                            ->latest('id')
                            ->first();

                        if (! $goal) {
                            $goal = StudentGoal::query()->create([
                                'student_id' => $lesson->student_id,
                                'tutor_id' => $lesson->tutor_id,
                                'subject' => mb_substr($subject, 0, 100),
                                'exam_type' => $payload['exam_type'] ?? 'ЦТ/ЦЭ',
                                'current_score' => $currentScore ?? 50,
                                'target_score' => $targetScore ?? 85,
                                'status' => 'active',
                            ]);
                        } else {
                            $updates = [];
                            if ($targetScore !== null) {
                                $updates['target_score'] = max(0, min(100, $targetScore));
                            }
                            if ($currentScore !== null) {
                                $updates['current_score'] = max(0, min(100, $currentScore));
                            }
                            if (! empty($payload['exam_type'])) {
                                $updates['exam_type'] = (string) $payload['exam_type'];
                            }
                            if (! empty($updates)) {
                                $goal->update($updates);
                            }
                        }

                        $createdEntities['goals'][] = [
                            'id' => $goal->id,
                            'target_score' => $goal->target_score,
                            'current_score' => $goal->current_score,
                            'subject' => $goal->subject,
                        ];
                        $executedActions[] = [
                            'action' => 'student_goal.update',
                            'payload' => [
                                'id' => $goal->id,
                                'target_score' => $goal->target_score,
                                'current_score' => $goal->current_score,
                            ],
                        ];
                    } catch (\Throwable $e) {
                        Log::error('Failed to update StudentGoal via AI: '.$e->getMessage());
                    }
                }

                continue;
            }

            // ── Platform Entity: Lesson Report Draft ──
            if ($act === 'report.draft') {
                if ($lesson) {
                    try {
                        $summary = trim((string) ($payload['summary'] ?? $payload['focus'] ?? ''));
                        $achievements = trim((string) ($payload['achievements'] ?? ''));
                        $recommendations = trim((string) ($payload['recommendations'] ?? ''));

                        $fullReport = $summary;
                        if ($achievements !== '') {
                            $fullReport .= ($fullReport !== '' ? "\n\n" : '').'Успехи: '.$achievements;
                        }
                        if ($recommendations !== '') {
                            $fullReport .= ($fullReport !== '' ? "\n\n" : '').'Рекомендации: '.$recommendations;
                        }

                        $focusText = mb_substr($summary !== '' ? $summary : 'Урок завершен продуктивно.', 0, 255);
                        $lesson->update([
                            'tutor_report_focus' => $focusText,
                        ]);

                        $session = $lesson->activeClassroom;
                        if ($session && $fullReport !== '') {
                            ClassroomNote::query()->create([
                                'classroom_session_id' => $session->id,
                                'author_id' => $user?->id ?? $lesson->tutor_id,
                                'content' => "📋 Итоги урока:\n".$fullReport,
                                'is_shared' => true,
                            ]);
                        }

                        $createdEntities['reports'][] = [
                            'lesson_id' => $lesson->id,
                            'focus' => $focusText,
                        ];
                        $executedActions[] = [
                            'action' => 'report.draft',
                            'payload' => ['lesson_id' => $lesson->id, 'focus' => $focusText],
                        ];
                    } catch (\Throwable $e) {
                        Log::error('Failed to draft Lesson Report via AI: '.$e->getMessage());
                    }
                }

                continue;
            }

            // ── Interactive Workspace: Quiz / Checkpoint Card on Board ──
            if ($act === 'quiz.create') {
                $title = trim((string) ($payload['title'] ?? 'Экспресс-срез по теме'));
                $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : [];

                $items = [];
                if (! empty($questions)) {
                    foreach ($questions as $idx => $q) {
                        $qText = is_array($q) ? ($q['text'] ?? $q['question'] ?? 'Вопрос') : (string) $q;
                        $items[] = [
                            'id' => 'quiz-item-'.($idx + 1),
                            'text' => $qText,
                            'completed' => false,
                        ];
                    }
                } else {
                    $items = [
                        ['id' => 'quiz-1', 'text' => '1. Базовые определения и формулы', 'completed' => false],
                        ['id' => 'quiz-2', 'text' => '2. Применение в типовых задачах', 'completed' => false],
                        ['id' => 'quiz-3', 'text' => '3. Разбор типичной ловушки РИКЗ', 'completed' => false],
                    ];
                }

                $cardId = 'card-quiz-'.(string) Str::uuid();
                $workspaceActions[] = [
                    'action' => 'column.add',
                    'payload' => [
                        'columnId' => 'col-quiz-ai',
                        'title' => 'Проверочный срез',
                    ],
                ];
                $workspaceActions[] = [
                    'action' => 'card.add',
                    'payload' => [
                        'columnId' => 'col-quiz-ai',
                        'card' => [
                            'id' => $cardId,
                            'type' => 'checklist',
                            'title' => 'Квиз: '.$title,
                            'content' => 'Контрольные вопросы для быстрой проверки усвоения материала.',
                            'items' => $items,
                        ],
                    ],
                ];
                $executedActions[] = [
                    'action' => 'quiz.create',
                    'payload' => ['title' => $title, 'card_id' => $cardId, 'count' => count($items)],
                ];

                continue;
            }

            // ── Platform Entity: Classroom Note ──
            if ($act === 'note.create') {
                $session = $lesson?->activeClassroom;
                if ($session) {
                    try {
                        $content = trim((string) ($payload['content'] ?? ''));
                        if ($content !== '') {
                            $authorId = $user?->id ?? $lesson?->tutor_id;
                            $isShared = (bool) ($payload['is_shared'] ?? true);

                            $note = ClassroomNote::query()->create([
                                'classroom_session_id' => $session->id,
                                'author_id' => $authorId,
                                'content' => $content,
                                'is_shared' => $isShared,
                            ]);

                            $createdEntities['notes'][] = [
                                'id' => $note->id,
                                'content' => mb_substr($note->content, 0, 80),
                            ];
                            $executedActions[] = [
                                'action' => 'note.create',
                                'payload' => ['id' => $note->id],
                            ];
                        }
                    } catch (\Throwable $e) {
                        Log::error('Failed to create ClassroomNote via AI: '.$e->getMessage());
                    }
                }

                continue;
            }

            // ── Interactive Workspace actions ──
            $workspaceActions[] = [
                'action' => $act,
                'payload' => $payload,
            ];
            $executedActions[] = [
                'action' => $act,
                'payload' => $payload,
            ];
        }

        if (! empty($workspaceActions)) {
            $this->applyPatchToWorkspace($roomId, $workspaceActions);
        }

        return [
            'executed_actions' => $executedActions,
            'created_entities' => $createdEntities,
        ];
    }

    private function applyPatchToWorkspace(string $roomId, array $actions): bool
    {
        $url = config('classroom.workspace_internal_url', 'http://localhost:8083');
        $secret = config('classroom.workspace_internal_secret');

        $allowedActions = [
            'column.add',
            'column.move',
            'column.delete',
            'card.add',
            'card.update',
            'card.move',
            'card.delete',
            'timer.toggle',
            'timer.set',
            'checklist.toggle',
            'board.clear',
        ];

        $sanitizedActions = [];
        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }
            $act = (string) ($action['action'] ?? $action['type'] ?? '');
            if (in_array($act, $allowedActions, true)) {
                $sanitizedActions[] = [
                    'action' => $act,
                    'payload' => $action['payload'] ?? [],
                ];
            }
        }

        if (empty($sanitizedActions)) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$secret,
                'Content-Type' => 'application/json',
            ])->post("{$url}/api/v1/workspace/{$roomId}/apply-ai-patch", [
                'actions' => $sanitizedActions,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Failed to connect to workspace microservice at {$url}: ".$e->getMessage());

            return false;
        }
    }

    private function getSystemPrompt(?Lesson $lesson = null, ?User $user = null): string
    {
        $subject = 'Учебный предмет';
        $topic = 'Тема занятия';
        $studentName = 'Ученик';
        $tutorName = 'Преподаватель';
        $speakerName = $user?->name ?? 'Пользователь';
        $speakerRole = 'участник урока';

        if ($lesson) {
            $studentName = $lesson->student?->name ?? 'Ученик';
            $tutorName = $lesson->tutor?->name ?? 'Преподаватель';
            $subject = $lesson->tutor?->tutorProfile?->subject ?? ($lesson->notes ?: 'Школьная программа');
            $topic = $lesson->notes ?: ($lesson->tutor_report_focus ?: 'Индивидуальное занятие');

            if ($user) {
                if ($user->id === $lesson->tutor_id) {
                    $speakerRole = "преподаватель ({$tutorName})";
                } elseif ($user->id === $lesson->student_id) {
                    $speakerRole = "ученик ({$studentName})";
                }
            }
        }

        $goalsInfo = '';
        $gapsInfo = '';
        if ($lesson && $lesson->student_id) {
            $goal = StudentGoal::query()
                ->where('student_id', $lesson->student_id)
                ->where('status', 'active')
                ->latest('id')
                ->first();
            if ($goal) {
                $goalsInfo = "\n- Цель ученика: подготовка к {$goal->exam_type} по {$goal->subject}, текущий балл: {$goal->current_score}, целевой балл: {$goal->target_score}.";
            }

            $gaps = SkillGap::query()
                ->where('student_id', $lesson->student_id)
                ->where('status', 'open')
                ->limit(5)
                ->pluck('topic')
                ->toArray();
            if (! empty($gaps)) {
                $gapsInfo = "\n- Выявленные пробелы в знаниях ученика: ".implode('; ', $gaps).'.';
            }
        }

        return "Вы — умный ИИ-ассистент (Classroom Copilot) в интерактивном виртуальном классе Edusfera.by.\n".
            "Контекст текущего урока:\n".
            "- Предмет: {$subject}\n".
            "- Тема/фокус: {$topic}\n".
            "- Преподаватель: {$tutorName}\n".
            "- Ученик: {$studentName}\n".
            "- Сейчас к вам обращается: {$speakerName} ({$speakerRole}).".
            $goalsInfo.
            $gapsInfo."\n\n".
            "Ваша роль:\n".
            "1. Помогать с объяснением сложных тем, формул, разбором задач (включая формат ЦТ/ЦЭ 2026 и критерии РИКЗ Беларуси).\n".
            "2. Управлять интерактивной доской урока и выполнять действия платформы (создавать ДЗ, заметки, фиксировать пробелы).\n".
            "3. Отвечать структурированно, понятно, с формулами и красивым Markdown.\n\n".
            "ОБЯЗАТЕЛЬНЫЙ ФОРМАТ ОТВЕТА — валидный JSON:\n".
            "{\n".
            "  \"reply\": \"(string) Текстовый ответ пользователю на русском языке.\",\n".
            "  \"actions\": [\n".
            "    { \"action\": \"команда\", \"payload\": { ... } }\n".
            "  ]\n".
            "}\n\n".
            "Доступные команды в \"actions\":\n".
            "1. Создание домашнего задания в системе:\n".
            "   - \"action\": \"homework.create\", \"payload\": { \"title\": \"Название темы ДЗ\", \"instructions\": \"Подробный текст задания и номера задач\", \"due_days\": 3 }\n".
            "2. Фиксация нового пробела в знаниях ученика:\n".
            "   - \"action\": \"skill_gap.create\", \"payload\": { \"topic\": \"Тема пробела\", \"severity\": \"low|medium|high\" }\n".
            "3. Устранение/ликвидация ранее выявленного пробела:\n".
            "   - \"action\": \"skill_gap.resolve\", \"payload\": { \"topic\": \"Название темы освоенного пробела\", \"id\": 123 }\n".
            "4. Корректировка цели подготовки ученика (балл ЦТ/ЦЭ):\n".
            "   - \"action\": \"student_goal.update\", \"payload\": { \"target_score\": 90, \"current_score\": 75, \"exam_type\": \"ЦТ/ЦЭ\" }\n".
            "5. Составление черновика отчета по уроку (для репетитора и родителей):\n".
            "   - \"action\": \"report.draft\", \"payload\": { \"summary\": \"Итоги занятия\", \"achievements\": \"Что получилось\", \"recommendations\": \"На что обратить внимание\" }\n".
            "6. Создание экспресс-квиза / проверочного среза на доске:\n".
            "   - \"action\": \"quiz.create\", \"payload\": { \"title\": \"Тема среза\", \"questions\": [\"Вопрос 1\", \"Вопрос 2\"] }\n".
            "7. Добавление заметки в конспект урока:\n".
            "   - \"action\": \"note.create\", \"payload\": { \"content\": \"Текст заметки для конспекта\", \"is_shared\": true }\n".
            "8. Управление интерактивной Kanban-доской:\n".
            "   - \"action\": \"column.add\", \"payload\": { \"columnId\": \"col-<uuid>\", \"title\": \"Название\" }\n".
            "   - \"action\": \"card.add\", \"payload\": { \"columnId\": \"colId\", \"card\": { \"id\": \"card-<uuid>\", \"type\": \"note|checklist|code|timer\", \"title\": \"...\", \"content\": \"...\", \"items\": [] } }\n".
            "   - \"action\": \"card.delete\", \"payload\": { \"cardId\": \"cardId\", \"columnId\": \"colId\" }\n".
            "   - \"action\": \"column.delete\", \"payload\": { \"columnId\": \"colId\" }\n".
            "   - \"action\": \"timer.set\", \"payload\": { \"seconds\": 300, \"running\": false }\n".
            "   - \"action\": \"checklist.toggle\", \"payload\": { \"cardId\": \"cardId\", \"itemId\": \"item-1\", \"completed\": true }\n".
            "   - \"action\": \"board.clear\", \"payload\": {}\n\n".
            'Если никаких действий выполнять не нужно, передайте пустой массив "actions": [].';
    }

    /**
     * Fallback mock chatbot to parse simple command phrases offline.
     */
    private function processMockChat(
        string $message,
        string $roomId,
        ?Lesson $lesson = null,
        ?User $user = null
    ): array {
        $lower = mb_strtolower(trim($message));
        $actions = [];
        $reply = "Я готов помочь! Спросите меня о теме урока или дайте команду (например: 'создай колонку План урока' или 'задай дз по производным').";

        // Homework creation offline pattern
        if (str_contains($lower, 'дз') || str_contains($lower, 'домашк') || str_contains($lower, 'домашнее задание')) {
            $title = preg_replace('/(создай|добавь|задай|назначь)?\s*(домашнее задание|домашней работы|домашнюю работу|домашк[а-я]*|дз)\s*(по|на тему|:)?\s*/ui', '', $message);
            $title = trim($title, " \t\n\r\0\x0B.?!\"'");
            if (empty($title)) {
                $title = 'Домашнее задание по уроку';
            }

            $actions[] = [
                'action' => 'homework.create',
                'payload' => [
                    'title' => $title,
                    'instructions' => 'Выполнить задания по теме «'.$title.'» и повторить конспект.',
                    'due_days' => 3,
                ],
            ];
            $reply = "Домашнее задание «{$title}» успешно создано и назначено ученику на 3 дня.";
        }
        // Skill gap resolution offline pattern
        elseif (str_contains($lower, 'закрой пробел') || str_contains($lower, 'пробел устранен') || str_contains($lower, 'пробел ликвидирован') || str_contains($lower, 'освоили тему') || str_contains($lower, 'тема освоена') || str_contains($lower, 'устранили пробел')) {
            $topic = preg_replace('/(закрой|устранили|ликвидировали)?\s*пробел\s*(устранен|ликвидирован|в знаниях|по теме|:)?\s*/ui', '', $message);
            $topic = trim($topic, " \t\n\r\0\x0B.?!\"'");
            if (empty($topic)) {
                $topic = 'Текущая тема урока';
            }

            $actions[] = [
                'action' => 'skill_gap.resolve',
                'payload' => [
                    'topic' => $topic,
                ],
            ];
            $reply = "Отлично! Пробел по теме «{$topic}» успешно отмечен как ликвидированный в карточке ученика.";
        }
        // Skill gap creation offline pattern
        elseif (str_contains($lower, 'зафиксируй пробел') || str_contains($lower, 'отметь пробел') || str_contains($lower, 'добавь пробел')) {
            $topic = preg_replace('/(зафиксируй|отметь|добавь|запиши)\s+пробел\s*(в знаниях|по теме|:)?\s*/ui', '', $message);
            $topic = trim($topic, " \t\n\r\0\x0B.?!\"'");
            if (empty($topic)) {
                $topic = 'Сложная тема урока';
            }

            $actions[] = [
                'action' => 'skill_gap.create',
                'payload' => [
                    'topic' => $topic,
                    'severity' => 'medium',
                ],
            ];
            $reply = "Пробел по теме «{$topic}» успешно зафиксирован в карточке подготовки ученика.";
        }
        // Student goal update offline pattern
        elseif (str_contains($lower, 'обнови цель') || str_contains($lower, 'поставь цель') || str_contains($lower, 'целевой балл') || str_contains($lower, 'измени цель')) {
            preg_match('/(\d{1,3})\s*(баллов|балла|баллов|б\b)?/ui', $message, $scoreMatch);
            $targetScore = isset($scoreMatch[1]) ? (int) $scoreMatch[1] : 85;

            $actions[] = [
                'action' => 'student_goal.update',
                'payload' => [
                    'target_score' => $targetScore,
                ],
            ];
            $reply = "Целевой балл ученика успешно скорректирован до {$targetScore} баллов.";
        }
        // Lesson report draft offline pattern
        elseif (str_contains($lower, 'составь отчет') || str_contains($lower, 'итоги урока') || str_contains($lower, 'отчет по уроку') || str_contains($lower, 'сформируй отчет')) {
            $actions[] = [
                'action' => 'report.draft',
                'payload' => [
                    'summary' => 'Урок проведен продуктивно, отработаны ключевые правила и практические прототипы ЦТ/ЦЭ.',
                    'achievements' => 'Ученик уверенно разобрался с алгоритмом решения типовых заданий.',
                    'recommendations' => 'Закрепить алгоритм на домашнем задании.',
                ],
            ];
            $reply = 'Черновик отчета по уроку успешно составлен и зафиксирован в карточке занятия.';
        }
        // Quiz creation offline pattern
        elseif (str_contains($lower, 'создай тест') || str_contains($lower, 'создай квиз') || str_contains($lower, 'проверочная работа') || str_contains($lower, 'мини-тест')) {
            $title = preg_replace('/(создай|добавь)?\s*(тест|квиз|проверочную работу|мини-тест)\s*(по теме|:)?\s*/ui', '', $message);
            $title = trim($title, " \t\n\r\0\x0B.?!\"'");
            if (empty($title)) {
                $title = 'Проверочный срез';
            }

            $actions[] = [
                'action' => 'quiz.create',
                'payload' => [
                    'title' => $title,
                    'questions' => [
                        'Определение и геометрический смысл',
                        'Формула и область допустимых значений',
                        'Типовая задача уровня ЦТ/ЦЭ части А',
                        'Анализ ловушки РИКЗ',
                    ],
                ],
            ];
            $reply = "Интерактивный квиз «{$title}» из 4 вопросов добавлен на доску в колонку «Проверочный срез».";
        }
        // Classroom note offline pattern
        elseif (str_contains($lower, 'добавь в заметки') || str_contains($lower, 'создай заметку') || str_contains($lower, 'запиши в конспект')) {
            $content = preg_replace('/(добавь в заметки|создай заметку|запиши в конспект)\s*(:)?\s*/ui', '', $message);
            $content = trim($content, " \t\n\r\0\x0B.?!\"'");
            if (empty($content)) {
                $content = 'Ключевая мысль урока.';
            }

            $actions[] = [
                'action' => 'note.create',
                'payload' => [
                    'content' => $content,
                    'is_shared' => true,
                ],
            ];
            $reply = "Заметка «{$content}» успешно сохранена в конспекте текущего урока.";
        }
        // Board clear offline pattern
        elseif (str_contains($lower, 'очисти доску') || str_contains($lower, 'очистить доску') || str_contains($lower, 'удали все карточки')) {
            $actions[] = [
                'action' => 'board.clear',
                'payload' => [],
            ];
            $reply = 'Интерактивная доска урока очищена.';
        }
        // Column addition
        elseif (str_contains($lower, 'создай колонку') || str_contains($lower, 'добавь колонку')) {
            $title = preg_replace('/(создай|добавь|добавить|создать)\s+колонку\s+/ui', '', $message);
            $title = trim($title, " \t\n\r\0\x0B.?!\"'");
            if (empty($title)) {
                $title = 'Новая колонка';
            }

            $colId = 'col-'.(string) Str::uuid();
            $actions[] = [
                'action' => 'column.add',
                'payload' => [
                    'columnId' => $colId,
                    'title' => $title,
                ],
            ];
            $reply = "Колонка «{$title}» успешно создана на вашей интерактивной доске.";
        }
        // Card addition
        elseif (str_contains($lower, 'добавь карточку') || str_contains($lower, 'создай карточку')) {
            $title = preg_replace('/(создай|добавь|добавить|создать)\s+карточку\s+/ui', '', $message);
            $title = trim($title, " \t\n\r\0\x0B.?!\"'");
            if (empty($title)) {
                $title = 'Новое задание';
            }

            $colId = 'col-mock-tasks';
            $actions[] = [
                'action' => 'column.add',
                'payload' => [
                    'columnId' => $colId,
                    'title' => 'Задачи от ИИ',
                ],
            ];

            $cardId = 'card-'.(string) Str::uuid();
            $actions[] = [
                'action' => 'card.add',
                'payload' => [
                    'columnId' => $colId,
                    'card' => [
                        'id' => $cardId,
                        'type' => 'note',
                        'title' => $title,
                        'content' => 'Создано ИИ-ассистентом по вашему запросу.',
                    ],
                ],
            ];
            $reply = "Я добавил карточку «{$title}» в колонку «Задачи от ИИ».";
        }
        // Checklist
        elseif (str_contains($lower, 'добавь список') || str_contains($lower, 'создай список') || str_contains($lower, 'чек-лист')) {
            $title = preg_replace('/(создай|добавь|добавить|создать)\s+(список|чек-лист)\s+/ui', '', $message);
            $title = trim($title, " \t\n\r\0\x0B.?!\"'");
            if (empty($title)) {
                $title = 'План подготовки';
            }

            $colId = 'col-mock-tasks';
            $actions[] = [
                'action' => 'column.add',
                'payload' => [
                    'columnId' => $colId,
                    'title' => 'Задачи от ИИ',
                ],
            ];

            $cardId = 'card-'.(string) Str::uuid();
            $actions[] = [
                'action' => 'card.add',
                'payload' => [
                    'columnId' => $colId,
                    'card' => [
                        'id' => $cardId,
                        'type' => 'checklist',
                        'title' => $title,
                        'items' => [
                            ['id' => 'item-1', 'text' => 'Изучить теорию', 'completed' => false],
                            ['id' => 'item-2', 'text' => 'Решить практические задачи', 'completed' => false],
                            ['id' => 'item-3', 'text' => 'Пройти тест', 'completed' => false],
                        ],
                    ],
                ],
            ];
            $reply = "Чек-лист «{$title}» с базовыми шагами добавлен в колонку «Задачи от ИИ».";
        }
        // Timer
        elseif (str_contains($lower, 'таймер')) {
            $colId = 'col-mock-tasks';
            $actions[] = [
                'action' => 'column.add',
                'payload' => [
                    'columnId' => $colId,
                    'title' => 'Задачи от ИИ',
                ],
            ];

            $cardId = 'card-'.(string) Str::uuid();
            $actions[] = [
                'action' => 'card.add',
                'payload' => [
                    'columnId' => $colId,
                    'card' => [
                        'id' => $cardId,
                        'type' => 'timer',
                        'title' => 'Время на выполнение',
                        'meta' => [
                            'seconds' => 300,
                            'running' => false,
                        ],
                    ],
                ],
            ];
            $reply = 'Я добавил карточку с таймером обратного отсчета (на 5 минут) в колонку «Задачи от ИИ».';
        } elseif (str_contains($lower, 'привет') || str_contains($lower, 'здравствуй')) {
            $reply = "Здравствуйте! Я ваш умный ИИ-ассистент в виртуальном классе. Я помогаю разбирать формулы, управлять доской и фиксировать задачи урока (например: 'задай дз по геометрии' или 'зафиксируй пробел в тригонометрии').";
        } elseif (str_contains($lower, 'спасибо') || str_contains($lower, 'благодарю')) {
            $reply = 'Рад помочь! Если возникнут новые вопросы или понадобятся карточки на доске, обращайтесь.';
        } else {
            $reply = 'Отличный вопрос! В рамках нашей темы важно помнить ключевые определения и формулы. Если вы хотите зафиксировать эту задачу на доске или назначить ДЗ, просто скажите мне об этом.';
        }

        $execution = $this->handleActions($actions, $roomId, $lesson, $user);

        return [
            'reply' => $reply,
            'actions' => $execution['executed_actions'],
            'created_entities' => $execution['created_entities'],
        ];
    }
}
