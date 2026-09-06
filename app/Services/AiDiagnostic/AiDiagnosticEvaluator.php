<?php

declare(strict_types=1);

namespace App\Services\AiDiagnostic;

class AiDiagnosticEvaluator
{
    public function __construct(
        private readonly RikzQuestionBank $questionBank
    ) {}

    /**
     * @param  array<string, mixed>  $userAnswers  [question_id => answer]
     * @return array<string, mixed>
     */
    public function evaluate(
        string $subject,
        string $examType,
        array $userAnswers,
        ?int $targetScore = null,
        ?int $currentSelfScore = null,
    ): array {
        $questions = $this->questionBank->getQuestions($subject, $examType);
        $targetScore = $targetScore !== null ? max(0, min(100, $targetScore)) : 80;

        $correctCount = 0;
        $incorrectCount = 0;
        $unansweredCount = 0;
        $earnedPrimary = 0;
        $maxPrimary = 0;

        $skillGaps = [];
        $biasOccurrences = [];
        $questionsSummary = [];

        foreach ($questions as $q) {
            $qId = (string) $q['id'];
            $userAns = $userAnswers[$qId] ?? null;
            $weight = (int) ($q['weight'] ?? 2);
            $maxPrimary += $weight;

            $isCorrect = false;
            $hasAnswered = $userAns !== null && trim((string) $userAns) !== '';

            if ($hasAnswered) {
                $isCorrect = $this->checkAnswer($q, (string) $userAns);
            }

            if ($isCorrect) {
                $correctCount++;
                $earnedPrimary += $weight;
            } else {
                if ($hasAnswered) {
                    $incorrectCount++;
                } else {
                    $unansweredCount++;
                }

                $severity = ($q['part'] === 'B' || $weight >= 4) ? 'high' : 'medium';
                $potentialLoss = $q['part'] === 'B' ? 12 : 6;

                $skillGaps[] = [
                    'question_id' => $qId,
                    'topic' => $q['topic'],
                    'section' => $q['section'],
                    'part' => $q['part'],
                    'difficulty' => $q['difficulty'],
                    'severity' => $severity,
                    'potential_score_loss' => $potentialLoss,
                    'user_answer' => $hasAnswered ? (string) $userAns : 'нет ответа',
                    'correct_answer' => (string) $q['correct_answer'],
                    'trap_analysis' => $q['trap_analysis'] ?? '',
                    'explanation' => $q['explanation'] ?? '',
                    'cognitive_bias' => $q['cognitive_bias'] ?? 'Неточность рассуждений',
                ];

                $bias = (string) ($q['cognitive_bias'] ?? 'Общие пробелы в правилах');
                $biasOccurrences[$bias] = ($biasOccurrences[$bias] ?? 0) + 1;
            }

            $questionsSummary[] = [
                'id' => $qId,
                'text' => $q['text'],
                'part' => $q['part'],
                'topic' => $q['topic'],
                'section' => $q['section'],
                'is_correct' => $isCorrect,
                'user_answer' => $hasAnswered ? (string) $userAns : null,
                'correct_answer' => (string) $q['correct_answer'],
                'explanation' => $q['explanation'] ?? '',
                'trap_analysis' => $q['trap_analysis'] ?? '',
            ];
        }

        $totalQuestions = count($questions);
        $accuracyPercent = $totalQuestions > 0
            ? round(($correctCount / $totalQuestions) * 100, 1)
            : 0.0;

        // Scaled score on RIKZ 100-point scale
        $scaledScore = $maxPrimary > 0
            ? (int) round(($earnedPrimary / $maxPrimary) * 100)
            : 0;

        $scoreGap = max(0, $targetScore - $scaledScore);

        // Probability of passing with target score
        if ($scaledScore >= $targetScore) {
            $passProbability = min(98, 85 + (int) round(($scaledScore - $targetScore) * 1.5));
        } else {
            $passProbability = max(12, min(80, (int) round(82 - ($scoreGap * 1.4))));
        }

        $cognitiveProfile = $this->buildCognitiveProfile($biasOccurrences, $totalQuestions);
        $studyPlan = $this->buildStudyPlan($subject, $skillGaps, $scaledScore, $targetScore);
        $tutorRecommendations = $this->buildTutorRecommendations($subject, $skillGaps, $scaledScore, $targetScore);

        return [
            'subject' => $subject,
            'exam_type' => $examType,
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'unanswered_count' => $unansweredCount,
            'earned_primary_score' => $earnedPrimary,
            'max_primary_score' => $maxPrimary,
            'accuracy_percent' => $accuracyPercent,
            'scaled_score' => $scaledScore,
            'target_score' => $targetScore,
            'current_self_score' => $currentSelfScore ?? $scaledScore,
            'score_gap' => $scoreGap,
            'pass_probability_percent' => $passProbability,
            'skill_gaps' => $skillGaps,
            'cognitive_profile' => $cognitiveProfile,
            'study_plan' => $studyPlan,
            'tutor_recommendations' => $tutorRecommendations,
            'questions_summary' => $questionsSummary,
        ];
    }

    private function checkAnswer(array $question, string $userAnswer): bool
    {
        $normalizedUser = mb_strtolower(trim($userAnswer));
        $normalizedCorrect = mb_strtolower(trim((string) $question['correct_answer']));

        if ($question['part'] === 'A') {
            return $normalizedUser === $normalizedCorrect;
        }

        // Part B: normalized matching (handling alternative numbers, words, spaces)
        if ($normalizedUser === $normalizedCorrect) {
            return true;
        }

        // Clean extra punctuation or whitespace
        $cleanUser = preg_replace('/[^\p{L}\p{N}]/u', '', $normalizedUser);
        $cleanCorrect = preg_replace('/[^\p{L}\p{N}]/u', '', $normalizedCorrect);

        if ($cleanUser === $cleanCorrect && $cleanCorrect !== '') {
            return true;
        }

        // Handle specific synonymous norms in Part B
        return match ($question['id'] ?? '') {
            'rus_b02' => in_array($cleanUser, ['красивее', 'болеекрасивый'], true),
            'bel_b01' => in_array($cleanUser, ['моцны', 'моцныболь'], true),
            'bel_b02' => in_array($cleanUser, ['бяздзейнічаць', 'гультаяваць'], true),
            default => false,
        };
    }

    /**
     * @param  array<string, int>  $biasOccurrences
     * @return array<int, array<string, mixed>>
     */
    private function buildCognitiveProfile(array $biasOccurrences, int $totalQuestions): array
    {
        if ($biasOccurrences === []) {
            return [
                [
                    'title' => 'Высокая концентрация и системность',
                    'occurrences' => 0,
                    'severity' => 'low',
                    'description' => 'Вы продемонстрировали эталонную внимательность: ни одной типичной ловушки РИКЗ не зафиксировано.',
                    'advice' => 'Поддерживайте этот темп и тренируйте скорость выполнения заданий части Б.',
                ],
            ];
        }

        arsort($biasOccurrences);
        $profile = [];

        foreach ($biasOccurrences as $bias => $count) {
            $percentage = round(($count / max(1, $totalQuestions)) * 100);

            $meta = match ($bias) {
                'Спешка при прочтении условия' => [
                    'severity' => 'high',
                    'description' => 'Склонность бегло просматривать условие задачи и упускать критические слова («НЕ», «все кроме», «за четвертую секунду», «первая труба»).',
                    'advice' => 'Применяйте метод карандашного подчеркивания ключевого вопроса перед выбором ответа в бланке.',
                ],
                'Пробел в исключениях из правил' => [
                    'severity' => 'high',
                    'description' => 'Уверенное знание общего правила, но систематический сбой на нюансах и исключениях (приставки НЕ-, инверсия, приставные звуки).',
                    'advice' => 'Сформируйте карточки-памятки со списками исключений РИКЗ за последние 5 лет.',
                ],
                'Ловушка смежных понятий' => [
                    'severity' => 'medium',
                    'description' => 'Смешение терминов, омонимичных корней или грамматических аналогий между языками (паронимы, межъязыковая интерференция).',
                    'advice' => 'Фиксируйте контрастные пары (эффектный/эффективный, гор/гар) в словаре ловушек.',
                ],
                'Незнание формул геометрии', 'Незнание формул физики' => [
                    'severity' => 'high',
                    'description' => 'Потеря баллов на базовых связях величин (радиус вписанной окружности, объем пирамиды, работа газа pΔV).',
                    'advice' => 'Проведите ревизию кодификатора формул РИКЗ и выведите их самостоятельно без шпаргалок.',
                ],
                'Игнорирование ОДЗ и ограничений' => [
                    'severity' => 'high',
                    'description' => 'Решение уравнения до конца без обязательной проверки допустимых значений (логарифмы, знаменатели).',
                    'advice' => 'Первым шагом любого уравнения всегда выписывайте ОДЗ ярким маркером.',
                ],
                'Ошибки в знаках и базовой арифметике' => [
                    'severity' => 'medium',
                    'description' => 'Обидные вычислительные погрешности в простых действиях (модули, корень из степени, сложение импульсов).',
                    'advice' => 'Вводите обязательную 30-секундную перепроверку расчетов методом обратного действия.',
                ],
                default => [
                    'severity' => 'medium',
                    'description' => 'Неточность формулировок и пробелы в тестовой стратегии РИКЗ.',
                    'advice' => 'Регулярно разбирайте спецификации ЦТ/ЦЭ и шкалы перевода баллов.',
                ],
            };

            $profile[] = [
                'title' => $bias,
                'occurrences' => $count,
                'error_rate_percent' => $percentage,
                'severity' => $meta['severity'],
                'description' => $meta['description'],
                'advice' => $meta['advice'],
            ];
        }

        return $profile;
    }

    /**
     * @param  array<int, array<string, mixed>>  $skillGaps
     * @return array<string, array<int, string>>
     */
    private function buildStudyPlan(
        string $subject,
        array $skillGaps,
        int $scaledScore,
        int $targetScore,
    ): array {
        $topics = array_unique(array_column($skillGaps, 'topic'));
        $firstTopics = array_slice($topics, 0, 2);
        $nextTopics = array_slice($topics, 2, 3);

        $topicsText1 = $firstTopics !== [] ? implode(', ', $firstTopics) : 'Повторение ключевых тем курса';
        $topicsText2 = $nextTopics !== [] ? implode(', ', $nextTopics) : 'Углубленные прототипы части Б';

        return [
            '30_days' => [
                "Ликвидация критических пробелов первой линии: {$topicsText1}.",
                'Отработка 15 типовых задач части А на каждый выявленный пробел.',
                'Внедрение чек-листа самопроверки бланков ответов для исключения спешки.',
                'Прохождение контрольного микро-среза знаний на отметку ' . min(100, $scaledScore + 15) . '+ баллов.',
            ],
            '60_days' => [
                "Системная прокачка повышенного уровня: {$topicsText2}.",
                'Разбор алгоритмов решения задач части Б по спецификации РИКЗ.',
                'Освоение тайм-менеджмента: не более 2 минут на задание части А.',
                'Решение двух полных тестов РТ предыдущих этапов с разбором ошибок.',
            ],
            '90_days' => [
                "Шлифовка знаний на целевой балл ({$targetScore} баллов).",
                'Стресс-тестирование на бланках РИКЗ в условиях строгого тайминга.',
                'Искоренение когнитивных ловушек (ОДЗ, исключения, ложные аналогии).',
                'Финальная репетиция ЦЭ/ЦТ с выходом на стабильный результат 85+ баллов.',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $skillGaps
     * @return array<string, mixed>
     */
    private function buildTutorRecommendations(
        string $subject,
        array $skillGaps,
        int $scaledScore,
        int $targetScore,
    ): array {
        $gap = max(0, $targetScore - $scaledScore);
        $topics = array_values(array_unique(array_column($skillGaps, 'topic')));

        $weeklyHours = match (true) {
            $gap >= 35 => 3,
            $gap >= 20 => 2,
            default => 1,
        };

        $specialization = match (true) {
            $scaledScore >= 75 => 'Эксперт по олимпиадным задачам и сложной части Б ЦЭ/ЦТ',
            $scaledScore >= 50 => 'Преподаватель высшей категории с авторской методикой обхода ловушек РИКЗ',
            default => 'Сильный методист по системному устранению базовых пробелов школьной программы',
        };

        $strategy = match (true) {
            $gap >= 30 => 'Интенсивный курс 2-3 раза в неделю с жестким контролем ДЗ и еженедельными мини-тестами.',
            $gap >= 15 => 'Регулярные занятия 2 раза в неделю с фокусом на часть Б и тестовые нюансы РИКЗ.',
            default => 'Поддерживающий формат 1 раз в неделю для шлифовки формулировок и разбора сложных заданий.',
        };

        return [
            'recommended_focus_topics' => $topics !== [] ? $topics : ['Спецификации РИКЗ 2026', 'Часть Б'],
            'weekly_hours' => $weeklyHours,
            'tutor_specialization' => $specialization,
            'lesson_strategy' => $strategy,
            'projected_score_boost' => min(35, max(15, (int) round($gap * 0.8))),
        ];
    }
}
