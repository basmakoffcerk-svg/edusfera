<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AiService
{
    private array $settings = [];

    public function __construct()
    {
        $this->loadSettings();
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
     * Process prompt and return the text reply.
     * If workspace commands are parsed, sends them to edusfera-workspace.
     */
    public function chat(string $message, string $roomId): string
    {
        $provider = $this->settings['provider'] ?? config('services.ai.default_provider', 'openai');
        $openaiKey = $this->settings['openai_key'] ?? config('services.ai.openai_key', '');
        $anthropicKey = $this->settings['anthropic_key'] ?? config('services.ai.anthropic_key', '');
        $model = $this->settings['premium_model'] ?? config('services.ai.premium_model', 'gpt-4o-mini');

        $activeKey = $provider === 'openai' ? $openaiKey : $anthropicKey;
        $isMock = empty($activeKey) || str_contains($activeKey, '•••');

        if ($isMock) {
            return $this->processMockChat($message, $roomId);
        }

        try {
            $systemPrompt = $this->getSystemPrompt();

            if ($provider === 'openai') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $openaiKey,
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.2,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['choices'][0]['message']['content'] ?? '{}';
                    return $this->handleAiResult($content, $roomId);
                }
            } elseif ($provider === 'anthropic') {
                $response = Http::withHeaders([
                    'x-api-key' => $anthropicKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model' => $model,
                    'max_tokens' => 1500,
                    'system' => $systemPrompt,
                    'messages' => [
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.2,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['content'][0]['text'] ?? '{}';
                    return $this->handleAiResult($content, $roomId);
                }
            }
        } catch (\Throwable $e) {
            Log::error('AI Service API error, falling back to mock: ' . $e->getMessage());
        }

        return $this->processMockChat($message, $roomId);
    }

    private function handleAiResult(string $jsonString, string $roomId): string
    {
        try {
            $parsed = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return "Извините, не удалось распознать формат ответа ИИ.";
            }

            $actions = $parsed['actions'] ?? [];
            $reply = $parsed['reply'] ?? 'Команда обработана.';

            if (!empty($actions)) {
                $this->applyPatchToWorkspace($roomId, $actions);
            }

            return $reply;
        } catch (\Throwable $e) {
            Log::error('Failed to handle AI actions: ' . $e->getMessage());
            return "Произошла ошибка при обработке команд ИИ.";
        }
    }

    private function applyPatchToWorkspace(string $roomId, array $actions): bool
    {
        $url = config('classroom.workspace_internal_url', 'http://localhost:8083');
        $secret = config('classroom.workspace_internal_secret');

        $allowedTypes = ['draw_shape', 'add_text', 'clear_canvas', 'add_note', 'highlight_area'];
        $sanitizedActions = array_values(array_filter($actions, function ($action) use ($allowedTypes) {
            return is_array($action) && isset($action['type']) && in_array((string) $action['type'], $allowedTypes, true);
        }));

        if (empty($sanitizedActions)) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secret,
                'Content-Type' => 'application/json',
            ])->post("{$url}/api/v1/workspace/{$roomId}/apply-ai-patch", [
                'actions' => $sanitizedActions,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Failed to connect to workspace microservice at {$url}: " . $e->getMessage());
            return false;
        }
    }

    private function getSystemPrompt(): string
    {
        return "Вы — ИИ-ассистент в интерактивном виртуальном классе Edusfera. " .
            "Вы можете общаться с пользователем и помогать ему изменять состояние интерактивной Kanban-доски (Workspace). " .
            "Ваш ответ ОБЯЗАТЕЛЬНО должен быть валидным JSON-объектом со следующими ключами:\n" .
            "- \"reply\": (string) текстовый ответ пользователю на русском языке.\n" .
            "- \"actions\": (array) список команд изменений доски. Каждая команда содержит \"action\" и \"payload\".\n\n" .
            "Доступные команды в \"actions\":\n" .
            "1. Добавление колонки:\n" .
            "   - \"action\": \"column.add\"\n" .
            "   - \"payload\": { \"columnId\": \"col-<random-uuid>\", \"title\": \"Название\" }\n" .
            "2. Добавление карточки в колонку:\n" .
            "   - \"action\": \"card.add\"\n" .
            "   - \"payload\": { \"columnId\": \"colId\", \"card\": { \"id\": \"card-<random-uuid>\", \"type\": \"note|checklist|code|timer\", \"title\": \"Название\", \"content\": \"Текст для note или code\", \"items\": [] } }\n" .
            "   - Примечание: Для type='timer' в payload.card.meta запишите { \"seconds\": 300, \"running\": false }\n" .
            "3. Удаление карточки:\n" .
            "   - \"action\": \"card.delete\"\n" .
            "   - \"payload\": { \"cardId\": \"cardId\", \"columnId\": \"colId\" }\n" .
            "4. Удаление колонки:\n" .
            "   - \"action\": \"column.delete\"\n" .
            "   - \"payload\": { \"columnId\": \"colId\" }\n\n" .
            "Пример ответа при запросе 'добавь колонку Физика с карточкой Закон Ньютона':\n" .
            "{\n" .
            "  \"reply\": \"Я добавил колонку Физика и карточку с законом Ньютона.\",\n" .
            "  \"actions\": [\n" .
            "    { \"action\": \"column.add\", \"payload\": { \"columnId\": \"col-123\", \"title\": \"Физика\" } },\n" .
            "    { \"action\": \"card.add\", \"payload\": { \"columnId\": \"col-123\", \"card\": { \"id\": \"card-456\", \"type\": \"note\", \"title\": \"Закон Ньютона\", \"content\": \"Действие равно противодействию\" } } }\n" .
            "  ]\n" .
            "}";
    }

    /**
     * Fallback mock chatbot to parse simple command phrases offline.
     */
    private function processMockChat(string $message, string $roomId): string
    {
        $lower = mb_strtolower(trim($message));
        $actions = [];
        $reply = "Я готов помочь! Спросите меня о теме урока или дайте команду (например: 'создай колонку План урока').";

        // Simple patterns parsing
        if (str_contains($lower, 'создай колонку') || str_contains($lower, 'добавь колонку')) {
            // Extract column name
            $title = preg_replace('/(создай|добавь|добавить|создать)\s+колонку\s+/ui', '', $message);
            $title = trim($title, " \t\n\r\0\x0B.?!\"'");
            if (empty($title)) {
                $title = "Новая колонка";
            }

            $colId = 'col-' . (string) Str::uuid();
            $actions[] = [
                'action' => 'column.add',
                'payload' => [
                    'columnId' => $colId,
                    'title' => $title,
                ],
            ];
            $reply = "Колонка «{$title}» успешно создана на вашей интерактивной доске.";
        } elseif (str_contains($lower, 'добавь карточку') || str_contains($lower, 'создай карточку')) {
            $title = preg_replace('/(создай|добавь|добавить|создать)\s+карточку\s+/ui', '', $message);
            $title = trim($title, " \t\n\r\0\x0B.?!\"'");
            if (empty($title)) {
                $title = "Новое задание";
            }

            // Fallback column: if no columns, we create one, but for mock we assume first column or create/use 'Задачи'
            $colId = 'col-mock-tasks';
            // We first add a column just in case
            $actions[] = [
                'action' => 'column.add',
                'payload' => [
                    'columnId' => $colId,
                    'title' => 'Задачи от ИИ',
                ],
            ];

            $cardId = 'card-' . (string) Str::uuid();
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
        } elseif (str_contains($lower, 'добавь список') || str_contains($lower, 'создай список') || str_contains($lower, 'чек-лист')) {
            $title = preg_replace('/(создай|добавь|добавить|создать)\s+(список|чек-лист)\s+/ui', '', $message);
            $title = trim($title, " \t\n\r\0\x0B.?!\"'");
            if (empty($title)) {
                $title = "План подготовки";
            }

            $colId = 'col-mock-tasks';
            $actions[] = [
                'action' => 'column.add',
                'payload' => [
                    'columnId' => $colId,
                    'title' => 'Задачи от ИИ',
                ],
            ];

            $cardId = 'card-' . (string) Str::uuid();
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
        } elseif (str_contains($lower, 'таймер')) {
            $colId = 'col-mock-tasks';
            $actions[] = [
                'action' => 'column.add',
                'payload' => [
                    'columnId' => $colId,
                    'title' => 'Задачи от ИИ',
                ],
            ];

            $cardId = 'card-' . (string) Str::uuid();
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
            $reply = "Я добавил карточку с таймером обратного отсчета (на 5 минут) в колонку «Задачи от ИИ».";
        } elseif (str_contains($lower, 'привет') || str_contains($lower, 'здравствуй')) {
            $reply = "Здравствуйте! Я ваш ИИ-ассистент в виртуальном классе. Я могу отвечать на ваши вопросы по теме урока, а также помогать управлять Kanban-доской. Например, скажите мне: 'добавь список Подготовка к ЦТ'.";
        } elseif (str_contains($lower, 'спасибо') || str_contains($lower, 'благодарю')) {
            $reply = "Рад помочь! Если возникнут новые вопросы или понадобятся карточки на доске, обращайтесь.";
        } else {
            // General tutoring help responses
            $reply = "Отличный вопрос! В рамках нашей темы важно помнить ключевые определения и формулы. Если вы хотите зафиксировать эту тему на доске, я могу добавить карточку. Просто скажите: 'добавь карточку [Название]'.";
        }

        if (!empty($actions)) {
            $this->applyPatchToWorkspace($roomId, $actions);
        }

        return $reply;
    }
}
