<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use App\Services\Ai\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiCopilotController extends Controller
{
    public function chat(Request $request, GeminiService $gemini): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Требуется авторизация'], 401);
        }

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:3000'],
            'history' => ['nullable', 'array', 'max:20'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant,model'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
            'persona' => ['nullable', 'string', 'in:default,methodologist,rikz_expert,express_solver,simple_analogy,homework_gen,free_chat'],
            'tone' => ['nullable', 'string', 'in:friendly,academic,concise,step_by_step'],
            'length' => ['nullable', 'string', 'in:balanced,short,detailed'],
            'custom_prompt' => ['nullable', 'string', 'max:500'],
        ]);

        $prompt = trim($validated['prompt']);
        $rawHistory = $validated['history'] ?? [];
        $persona = (string) ($validated['persona'] ?? 'default');
        $tone = (string) ($validated['tone'] ?? 'friendly');
        $length = (string) ($validated['length'] ?? 'balanced');
        $customPrompt = trim((string) ($validated['custom_prompt'] ?? ''));

        // Build role-adapted system instructions
        $isTutor = $user->isTutor();
        $isStudentOrParent = $user->isStudent() || $user->isParent();
        $userName = explode(' ', trim((string) $user->name))[0] ?? 'Пользователь';

        // Base persona definition
        if ($persona === 'rikz_expert') {
            $baseRole = "Ты — ведущий эксперт-методист по тестированию ЦТ и ЦЭ Беларуси 2026 (спецификации РИКЗ). Твоего собеседника зовут {$userName}. Твой фокус — тонкости формулировок, ловушки в части А и Б, разбор типичных ошибок абитуриентов и критерии перевода баллов.";
        } elseif ($persona === 'express_solver') {
            $baseRole = "Ты — мгновенный аналитик и решатель задач для {$userName}. Давай строго аналитическое, выверенное решение с формулами, промежуточными расчетами и итоговым ответом.";
        } elseif ($persona === 'simple_analogy') {
            $baseRole = "Ты — мастер ярких педагогических аналогий и метафор для {$userName}. Твоя суперсила — объяснить любую сложнейшую тему школьной программы на простых жизненных примерах 'на пальцах'.";
        } elseif ($persona === 'homework_gen') {
            $baseRole = "Ты — генератор авторских домашних заданий и проверочных работ для {$userName}. Формируй задания блоками по уровню сложности, а в конце обязательно давай блок ответов и критериев быстрой проверки.";
        } elseif ($persona === 'methodologist' || ($persona === 'default' && $isTutor)) {
            $baseRole = "Ты — персональный ИИ-методист образовательной платформы Edusfera.by. Твоего собеседника зовут {$userName} (преподаватель/репетитор). Ты помогаешь составлять технологические карты занятий, практические задания, интерактивные приемы и конспекты.";
        } elseif ($persona === 'default' && $isStudentOrParent) {
            $baseRole = "Ты — заботливый и умный ИИ-тьютор Edusfera.by для {$userName}. Помогаешь понять учебный материал, подготовиться к урокам и экзаменам, мотивируешь и поддерживаешь интерес к учебе.";
        } else {
            $baseRole = "Ты — официальный ИИ-ассистент платформы Edusfera.by для {$userName}. Отвечай структурированно, понятно и по делу.";
        }

        // Tone adjustments
        $toneGuide = match ($tone) {
            'academic' => 'Придерживайся строгого академического стиля, точной научной терминологии и доказательности.',
            'concise' => 'Отвечай предельно емко, тезисно, без лишних вводных слов и ритуалов вежливости.',
            'step_by_step' => 'Обязательно структурируй объяснение по пронумерованным этапам: Шаг 1, Шаг 2 и т.д.',
            default => 'Пиши живым, воодушевляющим и дружелюбным языком опытного наставника.',
        };

        // Length adjustments
        $lengthGuide = match ($length) {
            'short' => 'Формат ответа: краткий конспект (не более 2-3 компактных пунктов/абзацев).',
            'detailed' => 'Формат ответа: развернутый, исчерпывающий разбор с подробными примерами и контекстом.',
            default => 'Формат ответа: сбалансированный объем, идеальный для быстрого чтения.',
        };

        $formatting = 'Всегда используй красивый Markdown: структурируй подзаголовками (###), выделяй важное (**жирный текст**), оформляй формулы и код в моноширинные блоки (`код` или ```).';

        $systemInstruction = "{$baseRole}\n\nИнструкции по стилю:\n- {$toneGuide}\n- {$lengthGuide}\n- {$formatting}";

        // Inject real-time platform context (schedule, homework, subject)
        $platformContext = $this->buildUserPlatformContext($user);
        if (! empty($platformContext)) {
            $systemInstruction .= "\n\n{$platformContext}";
        }

        if (! empty($customPrompt)) {
            $systemInstruction .= "\n\nДополнительное указание от пользователя: {$customPrompt}";
        }

        try {
            $messages = [];
            foreach ($rawHistory as $msg) {
                $messages[] = [
                    'role' => $msg['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => (string) $msg['content'],
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $prompt];

            $reply = $gemini->chat($messages, $systemInstruction);

            if (empty(trim($reply))) {
                $reply = $gemini->generateText($prompt, $systemInstruction);
            }

            if (empty(trim($reply))) {
                $reply = "Извините, не удалось сформировать ответ. Попробуйте переформулировать запрос.";
            }

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI Copilot request error: ' . $e->getMessage(), ['user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'error' => 'Произошла ошибка при обращении к ИИ-ассистенту. Попробуйте еще раз через несколько секунд.',
            ], 500);
        }
    }

    /**
     * Build contextual knowledge about user's current platform data
     */
    protected function buildUserPlatformContext(\App\Models\User $user): string
    {
        $now = now()->setTimezone('Europe/Minsk');
        $lines = [
            "### Актуальный контекст платформы Edusfera.by:",
            "- Текущее время (Минск): " . $now->format('d.m.Y H:i') . " (" . $now->translatedFormat('l') . ")",
        ];

        try {
            if ($user->isTutor()) {
                $lines[] = "- Роль: Преподаватель (Репетитор)";
                if ($user->tutorProfile) {
                    $lines[] = "- Предмет преподавателя: " . ($user->tutorProfile->subject ?? 'Не указан');
                    if ($user->tutorProfile->hourly_rate) {
                        $lines[] = "- Ставка за урок: " . $user->tutorProfile->hourly_rate . " BYN";
                    }
                }

                $nextLessons = \App\Models\Lesson::where('tutor_id', $user->id)
                    ->where('start_time', '>=', $now)
                    ->whereIn('status', [\App\Models\Lesson::STATUS_CONFIRMED, \App\Models\Lesson::STATUS_PENDING])
                    ->orderBy('start_time')
                    ->with('student')
                    ->take(3)
                    ->get();

                if ($nextLessons->isNotEmpty()) {
                    $lessonDescriptions = [];
                    foreach ($nextLessons as $ls) {
                        $studentName = $ls->student?->name ?? 'Ученик';
                        $lessonDescriptions[] = $ls->start_time->setTimezone('Europe/Minsk')->format('d.m в H:i') . " с {$studentName} (" . ($ls->notes ?: 'без темы') . ")";
                    }
                    $lines[] = "- Ближайшие уроки репетитора: " . implode('; ', $lessonDescriptions);
                } else {
                    $lines[] = "- Ближайшие уроки: Нет запланированных уроков на ближайшее время.";
                }

                $weekCount = \App\Models\Lesson::where('tutor_id', $user->id)
                    ->whereBetween('start_time', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
                    ->whereIn('status', [\App\Models\Lesson::STATUS_CONFIRMED, \App\Models\Lesson::STATUS_PENDING])
                    ->count();
                $lines[] = "- Количество запланированных уроков на этой неделе: {$weekCount}";
            } elseif ($user->isStudent()) {
                $lines[] = "- Роль: Ученик";

                $nextLessons = \App\Models\Lesson::where('student_id', $user->id)
                    ->where('start_time', '>=', $now)
                    ->whereIn('status', [\App\Models\Lesson::STATUS_CONFIRMED, \App\Models\Lesson::STATUS_PENDING])
                    ->orderBy('start_time')
                    ->with('tutor.tutorProfile')
                    ->take(3)
                    ->get();

                if ($nextLessons->isNotEmpty()) {
                    $lessonDescriptions = [];
                    foreach ($nextLessons as $ls) {
                        $tutorName = $ls->tutor?->name ?? 'Преподаватель';
                        $subj = $ls->tutor?->tutorProfile?->subject ?? 'Занятие';
                        $noteStr = $ls->notes ? ", тема: {$ls->notes}" : '';
                        $lessonDescriptions[] = $ls->start_time->setTimezone('Europe/Minsk')->format('d.m в H:i') . " ({$subj}, репетитор {$tutorName}{$noteStr})";
                    }
                    $lines[] = "- Ближайшие запланированные занятия: " . implode('; ', $lessonDescriptions);
                } else {
                    $lines[] = "- Ближайшие занятия: Нет запланированных занятий.";
                }

                $pendingHw = \App\Models\HomeworkAssignment::where('student_id', $user->id)
                    ->where('status', '!=', 'completed')
                    ->orderBy('due_at')
                    ->take(3)
                    ->get();

                if ($pendingHw->isNotEmpty()) {
                    $hwList = [];
                    foreach ($pendingHw as $hw) {
                        $due = $hw->due_at ? "до " . $hw->due_at->format('d.m') : "без дедлайна";
                        $hwList[] = "«{$hw->title}» ({$due})";
                    }
                    $lines[] = "- Невыполненные домашние задания: " . implode(', ', $hwList);
                } else {
                    $lines[] = "- Домашние задания: Все текущие ДЗ выполнены или не назначены.";
                }

                $goals = \App\Models\StudentGoal::where('student_id', $user->id)
                    ->where('status', 'active')
                    ->get();

                if ($goals->isNotEmpty()) {
                    $goalsList = [];
                    foreach ($goals as $g) {
                        $goalsList[] = "{$g->subject} ({$g->exam_type}: цель {$g->target_score} б.)";
                    }
                    $lines[] = "- Активные цели ЦТ/ЦЭ: " . implode('; ', $goalsList);
                }
            }

            $lines[] = "Если пользователь спрашивает о своем расписании, следующем уроке или домашних заданиях, отвечай на основании этих фактов.";
        } catch (\Throwable $e) {
            // Silently fall back if DB is unavailable
        }

        return implode("\n", $lines);
    }

    /**
     * Generate structured lesson plan with pedagogical timing, theory, and exercises
     */
    public function generateLessonPlan(Request $request, GeminiService $gemini): JsonResponse
     {
         $user = $request->user();
         if (! $user) {
             return response()->json(['success' => false, 'error' => 'Требуется авторизация'], 401);
         }

         if (! $user->isAdmin() && ! app(SubscriptionFeatureGate::class)->canUseAiTools($user)) {
             return response()->json([
                 'success' => false,
                 'error' => 'ИИ-генератор конспектов доступен на тарифе Pro и в бесплатном 14-дневном периоде.',
                 'upgrade_required' => true,
             ], 403);
         }

         $validated = $request->validate([
             'subject' => ['nullable', 'string', 'max:100'],
             'grade' => ['nullable', 'string', 'max:150'],
             'topic' => ['required', 'string', 'max:300'],
             'duration' => ['nullable', 'string', 'max:50'],
             'goal' => ['nullable', 'string', 'max:300'],
             'focus' => ['nullable', 'string', 'max:150'],
         ]);

         $subject = trim($validated['subject'] ?? 'Математика');
         $grade = trim($validated['grade'] ?? '11 класс (подготовка к ЦТ/ЦЭ)');
         $topic = trim($validated['topic']);
         $duration = trim($validated['duration'] ?? '60 минут');
         $goal = trim($validated['goal'] ?? '');
         $focus = trim($validated['focus'] ?? 'Практика ЦТ/ЦЭ и разбор ловушек');

         $prompt = "Ты — опытнейший методист и преподаватель Edusfera.by.\n" .
             "Составь подробный, готовый к проведению технологический конспект занятия для репетитора.\n\n" .
             "ПАРАМЕТРЫ ЗАНЯТИЯ:\n" .
             "- Предмет: {$subject}\n" .
             "- Аудитория / Уровень: {$grade}\n" .
             "- Тема занятия: {$topic}\n" .
             "- Длительность: {$duration}\n" .
             "- Фокус урока: {$focus}\n" .
             ($goal ? "- Ключевая цель: {$goal}\n" : "") .
             "\nОБЯЗАТЕЛЬНАЯ СТРУКТУРА КОНСПЕКТА:\n" .
             "1. **Цели и планируемый результат урока** (образовательные, развивающие, критерии успеха ученика).\n" .
             "2. **Разминка и блиц-проверка (5-7 мин)**: 2-3 экспресс-вопроса на понимание базы.\n" .
             "3. **Теоретический блок и наглядные алгоритмы**: ключевые свойства, формулы, правила и мнемонические подсказки.\n" .
             "4. **Практический блок (с подробными решениями)**: 3-4 разноуровневые задачи (от базовой до уровня ЦТ/ЦЭ), обязательное указание *типичной ошибки / ловушки РИКЗ*.\n" .
             "5. **Рефлексия и фиксация выводов (5 мин)**: контрольные вопросы для самопроверки ученика.\n" .
             "6. **Домашнее задание**: 3 номера для закрепления с краткими ответами для проверки.\n\n" .
             "ПРАВИЛА ОФОРМЛЕНИЯ:\n" .
             "- Все математические и физические формулы оформляй строго в LaTeX: встроенные формулы внутри $ ... $, а отдельные крупные формулы внутри $$ ... $$.\n" .
             "- Используй заголовки (###), списки и жирные акценты. Разделяй ключевые блоки разделителями `---`.";

         $system = "Ты ведущий педагогический методист Edusfera.by. Твой стиль — структурированный, прикладной, живой и точный. Всегда используй правильный синтаксис LaTeX для формул ($...$ и $$...$$) и Markdown.";

         try {
             $result = $gemini->generateText($prompt, $system);
             if (empty(trim($result))) {
                 return response()->json(['success' => false, 'error' => 'Не удалось получить ответ модели'], 502);
             }

             return response()->json([
                 'success' => true,
                 'result' => $result,
             ]);
         } catch (\Throwable $e) {
             Log::error('AI lesson plan generation error: ' . $e->getMessage(), ['user_id' => $user->id]);
             return response()->json(['success' => false, 'error' => 'Ошибка генерации: ' . $e->getMessage()], 500);
         }
     }

    /**
     * Generate quiz/tasks in RIKZ Belarus CT/CE format
     */
     public function generateQuiz(Request $request, GeminiService $gemini): JsonResponse
     {
         $user = $request->user();
         if (! $user) {
             return response()->json(['success' => false, 'error' => 'Требуется авторизация'], 401);
         }

         if (! $user->isAdmin() && ! app(SubscriptionFeatureGate::class)->canUseAiTools($user)) {
             return response()->json([
                 'success' => false,
                 'error' => 'ИИ-генератор тестов доступен на тарифе Pro и в бесплатном 14-дневном периоде.',
                 'upgrade_required' => true,
             ], 403);
         }

         $validated = $request->validate([
             'subject' => ['nullable', 'string', 'max:100'],
             'topic' => ['required', 'string', 'max:300'],
             'count' => ['nullable', 'integer', 'min:1', 'max:15'],
             'difficulty' => ['nullable', 'string', 'max:150'],
             'format' => ['nullable', 'string', 'max:100'],
         ]);

         $subject = trim($validated['subject'] ?? 'Математика');
         $topic = trim($validated['topic']);
         $count = (int) ($validated['count'] ?? 5);
         $difficulty = trim($validated['difficulty'] ?? 'Средний (ЦТ/ЦЭ 2026, часть А и Б)');
         $format = trim($validated['format'] ?? 'Смешанный (Часть А с выбором и Часть Б с кратким числовым ответом)');

         $prompt = "Ты — составитель тестовых материалов РИКЗ Беларуси по предмету «{$subject}».\n" .
             "Составь качественный блок проверочных заданий ({$count} шт.) по теме: «{$topic}».\n" .
             "Сложность: {$difficulty}.\n" .
             "Формат заданий: {$format}.\n\n" .
             "ДЛЯ КАЖДОГО ЗАДАНИЯ СТРОГО СФОРМИРУЙ:\n" .
             "1. **Условие задачи** (четкая и однозначная формулировка в стиле ЦТ/ЦЭ 2026. Для части А — 4-5 вариантов ответа, для части Б — требование дать числовой ответ).\n" .
             "2. **Правильный ответ**.\n" .
             "3. **Пошаговое решение**.\n" .
             "4. **Ловушка РИКЗ** (на чем обычно ошибаются 80% учеников: не учли ОДЗ, потеряли корень, спутали знаки и т.д.).\n\n" .
             "ПРАВИЛА ОФОРМЛЕНИЯ:\n" .
             "- Все формулы строго в LaTeX ($...$ и $$...$$).\n" .
             "- Четкая нумерация задач: ### Задание 1, ### Задание 2 и т.д.\n" .
             "- В конце добавь краткую сводную таблицу ответов для быстрой проверки репетитором.";

         $system = "Ты эксперт РИКЗ по составлению тестовых материалов ЦТ и ЦЭ 2026. Оформляй в аккуратном структурированном Markdown с LaTeX формулами.";

         try {
             $result = $gemini->generateText($prompt, $system);
             if (empty(trim($result))) {
                 return response()->json(['success' => false, 'error' => 'Не удалось получить ответ модели'], 502);
             }

             return response()->json([
                 'success' => true,
                 'result' => $result,
             ]);
         } catch (\Throwable $e) {
             Log::error('AI quiz generation error: ' . $e->getMessage(), ['user_id' => $user->id]);
             return response()->json(['success' => false, 'error' => 'Ошибка генерации: ' . $e->getMessage()], 500);
         }
     }
}

