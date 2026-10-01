<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;

    private string $defaultModel;

    private string $liteModel;

    private string $baseUrl;

    private ?string $proxy;

    private bool $useXboxDns;

    public function __construct(
        ?string $apiKey = null,
        ?string $defaultModel = null,
        ?string $liteModel = null,
        ?string $baseUrl = null,
        ?string $proxy = null,
        ?bool $useXboxDns = null,
    ) {
        $this->apiKey = (string) ($apiKey ?? config('services.gemini.api_key', ''));
        $this->defaultModel = (string) ($defaultModel ?? config('services.gemini.model', 'gemini-flash-latest'));
        $this->liteModel = (string) ($liteModel ?? config('services.gemini.lite_model', 'gemini-3.5-flash-lite'));
        $this->baseUrl = rtrim((string) ($baseUrl ?? config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta')), '/');
        $this->proxy = $proxy ?? (config('services.gemini.proxy') ?: null);
        $this->useXboxDns = $useXboxDns ?? (bool) config('services.gemini.use_xbox_dns', true);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! str_contains($this->apiKey, '•••');
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    /**
     * Generate text reply from prompt.
     */
    public function generateText(
        string $prompt,
        ?string $systemInstruction = null,
        ?string $model = null,
        float $temperature = 0.7,
        int $timeoutSeconds = 25
    ): string {
        $model = $model ?? $this->defaultModel;
        $body = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
            ],
        ];

        if (! empty($systemInstruction)) {
            $body['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $result = $this->callApi($model, $body, $timeoutSeconds);

        return $result['text'] ?? '';
    }

    /**
     * Generate structured JSON data from prompt.
     */
    public function generateJson(
        string $prompt,
        ?string $systemInstruction = null,
        ?string $model = null,
        float $temperature = 0.2,
        int $timeoutSeconds = 30
    ): array {
        $model = $model ?? $this->defaultModel;
        $body = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'responseMimeType' => 'application/json',
            ],
        ];

        if (! empty($systemInstruction)) {
            $body['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $result = $this->callApi($model, $body, $timeoutSeconds);
        $rawText = trim($result['text'] ?? '');

        if (empty($rawText)) {
            return [];
        }

        // Clean any possible markdown code fence (```json ... ```)
        if (str_starts_with($rawText, '```')) {
            $rawText = preg_replace('/^```(?:json)?\s*/i', '', $rawText);
            $rawText = preg_replace('/\s*```$/', '', $rawText);
            $rawText = trim((string) $rawText);
        }

        try {
            $decoded = json_decode($rawText, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('Gemini JSON decode failed: '.$e->getMessage(), ['raw' => $rawText]);
        }

        return [];
    }

    /**
     * Multi-turn chat conversation.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(
        array $messages,
        ?string $systemInstruction = null,
        ?string $model = null,
        float $temperature = 0.7,
        int $timeoutSeconds = 30
    ): string {
        $model = $model ?? $this->defaultModel;

        $contents = [];
        foreach ($messages as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => (string) ($msg['content'] ?? '')],
                ],
            ];
        }

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
            ],
        ];

        if (! empty($systemInstruction)) {
            $body['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $result = $this->callApi($model, $body, $timeoutSeconds);

        return $result['text'] ?? '';
    }

    /**
     * Multi-turn chat returning structured JSON data.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chatJson(
        array $messages,
        ?string $systemInstruction = null,
        ?string $model = null,
        float $temperature = 0.2,
        int $timeoutSeconds = 30
    ): array {
        $model = $model ?? $this->defaultModel;

        $contents = [];
        foreach ($messages as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => (string) ($msg['content'] ?? '')],
                ],
            ];
        }

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'responseMimeType' => 'application/json',
            ],
        ];

        if (! empty($systemInstruction)) {
            $body['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $result = $this->callApi($model, $body, $timeoutSeconds);
        $rawText = trim($result['text'] ?? '');

        if (empty($rawText)) {
            return [];
        }

        if (str_starts_with($rawText, '```')) {
            $rawText = preg_replace('/^```(?:json)?\s*/i', '', $rawText);
            $rawText = preg_replace('/\s*```$/', '', $rawText);
            $rawText = trim((string) $rawText);
        }

        try {
            $decoded = json_decode($rawText, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('Gemini chatJson decode failed: '.$e->getMessage(), ['raw' => $rawText]);
        }

        return [];
    }

    /**
     * Test connection to Gemini API.
     */
    public function testConnection(?string $apiKey = null, ?string $model = null): array
    {
        $key = $apiKey ?? $this->apiKey;
        $primaryModel = $model ?? $this->defaultModel;

        if (empty($key) || str_contains($key, '•••')) {
            return [
                'success' => false,
                'message' => 'API-ключ Google Gemini не задан или содержит маску.',
                'latency_ms' => 0,
            ];
        }

        $modelsToTry = array_values(array_unique(array_filter([
            $primaryModel,
            $this->liteModel,
            'gemini-3.5-flash-lite',
            'gemini-flash-latest',
        ])));

        $startTime = microtime(true);
        $lastError = '';

        foreach ($modelsToTry as $targetModel) {
            try {
                $url = "{$this->baseUrl}/models/{$targetModel}:generateContent";
                $request = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $key,
                ])->timeout(8);

                $request = $this->applyHttpOptions($request);

                $response = $request->post($url, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => 'Ответь одним словом: работает?'],
                            ],
                        ],
                    ],
                ]);

                $latency = (int) round((microtime(true) - $startTime) * 1000);

                if ($response->successful()) {
                    $data = $response->json();
                    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'OK';
                    $actualModel = $data['modelVersion'] ?? $targetModel;

                    return [
                        'success' => true,
                        'message' => "Соединение успешно! Модель: {$actualModel}. Ответ: ".trim($reply),
                        'latency_ms' => $latency,
                        'model' => $actualModel,
                    ];
                }

                $errorData = $response->json();
                $lastError = $errorData['error']['message'] ?? "HTTP {$response->status()}";
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        $latency = (int) round((microtime(true) - $startTime) * 1000);

        return [
            'success' => false,
            'message' => "Ошибка подключения к Gemini API: {$lastError}",
            'latency_ms' => $latency,
        ];
    }

    /**
     * Core HTTP request handler with automatic fallback to lite model if primary fails.
     */
    private function callApi(string $model, array $body, int $timeoutSeconds = 25): array
    {
        if (! $this->isConfigured()) {
            Log::warning('Gemini API key is not configured, skipping AI request.');

            return ['text' => '', 'model' => $model];
        }

        $modelsToTry = array_values(array_unique(array_filter([
            $model,
            $this->liteModel,
            'gemini-3.5-flash-lite',
            'gemini-flash-latest',
        ])));

        foreach ($modelsToTry as $currentModel) {
            try {
                $url = "{$this->baseUrl}/models/{$currentModel}:generateContent";
                $request = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $this->apiKey,
                ])
                    ->timeout($timeoutSeconds);

                $request = $this->applyHttpOptions($request);

                $response = $request->post($url, $body);

                if ($response->successful()) {
                    $data = $response->json();
                    $parts = $data['candidates'][0]['content']['parts'] ?? [];

                    $combinedText = '';
                    foreach ($parts as $part) {
                        if (! empty($part['text'])) {
                            $combinedText .= $part['text'];
                        }
                    }

                    return [
                        'text' => $combinedText,
                        'model' => $data['modelVersion'] ?? $currentModel,
                        'data' => $data,
                    ];
                }

                $errorBody = $response->json();
                $errMsg = $errorBody['error']['message'] ?? $response->body();
                Log::warning("Gemini API error on model {$currentModel}: {$errMsg}");
            } catch (\Throwable $e) {
                Log::error("Gemini API connection exception on model {$currentModel}: ".$e->getMessage());
            }
        }

        return ['text' => '', 'model' => $model];
    }

    /**
     * Apply proxy or xbox-dns routing to HTTP client.
     *
     * @param  PendingRequest  $request
     * @return PendingRequest
     */
    private function applyHttpOptions($request)
    {
        $options = [];

        if ($this->proxy) {
            $options['proxy'] = $this->proxy;
        }

        // When using xbox-dns and target host is Google's API, route via xbox-dns proxy IPs
        if ($this->useXboxDns && str_contains($this->baseUrl, 'generativelanguage.googleapis.com')) {
            $ips = $this->getXboxDnsIps();
            $resolveList = [];
            foreach ($ips as $ip) {
                $resolveList[] = "generativelanguage.googleapis.com:443:{$ip}";
            }
            $options['curl'] = [
                CURLOPT_RESOLVE => $resolveList,
                CURLOPT_CONNECTTIMEOUT => 4,
            ];
        }

        if (! empty($options)) {
            $request = $request->withOptions($options);
        }

        return $request;
    }

    /**
     * Get IP addresses for generativelanguage.googleapis.com via xbox-dns.ru.
     *
     * @return array<string>
     */
    public function getXboxDnsIps(): array
    {
        $fallback = ['188.68.214.130', '188.68.214.143'];

        try {
            return cache()->remember('gemini_xbox_dns_ips', 1800, function () use ($fallback): array {
                try {
                    $packet = $this->buildDnsQueryPacket('generativelanguage.googleapis.com');
                    $response = Http::timeout(2)
                        ->withHeaders(['Content-Type' => 'application/dns-message'])
                        ->withBody($packet, 'application/dns-message')
                        ->post('https://xbox-dns.ru/dns-query');

                    if ($response->successful()) {
                        $ips = $this->parseDnsResponseIps($response->body());
                        if (! empty($ips)) {
                            return $ips;
                        }
                    }
                } catch (\Throwable $e) {
                    // Fallback to static IPs
                }

                return $fallback;
            });
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function buildDnsQueryPacket(string $domain): string
    {
        $header = pack('n6', 0x1234, 0x0100, 1, 0, 0, 0);
        $qname = '';
        foreach (explode('.', $domain) as $part) {
            $qname .= chr(strlen($part)).$part;
        }
        $qname .= "\0";
        $qtypeClass = pack('n2', 1, 1);

        return $header.$qname.$qtypeClass;
    }

    private function parseDnsResponseIps(string $data): array
    {
        $ips = [];
        $len = strlen($data);
        for ($i = 0; $i < $len - 14; $i++) {
            if (substr($data, $i, 4) === "\x00\x01\x00\x01") {
                $rdataLen = unpack('n', substr($data, $i + 8, 2))[1] ?? 0;
                if ($rdataLen === 4) {
                    $ip = long2ip(unpack('N', substr($data, $i + 10, 4))[1] ?? 0);
                    if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }
}
