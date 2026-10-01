<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Filament\Pages\TutorAiAssistantPage;
use App\Models\User;
use App\Services\Ai\GeminiService;
use App\Services\Classroom\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_gemini_service_is_configured(): void
    {
        $gemini = app(GeminiService::class);
        $this->assertTrue($gemini->isConfigured());
        $this->assertNotEmpty($gemini->getApiKey());
        $this->assertSame('gemini-3.5-flash-lite', $gemini->getDefaultModel());
    }

    public function test_gemini_generate_text_with_http_fake(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'ИИ работает отлично.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $gemini = app(GeminiService::class);
        $text = $gemini->generateText('Проверка связи');

        $this->assertSame('ИИ работает отлично.', $text);
    }

    public function test_gemini_generate_json_with_http_fake(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"reply": "Здравствуйте!", "actions": [{"action": "column.add", "payload": {"title": "Новая"}}]}'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $gemini = app(GeminiService::class);
        $json = $gemini->generateJson('Тестовый JSON');

        $this->assertIsArray($json);
        $this->assertSame('Здравствуйте!', $json['reply']);
        $this->assertCount(1, $json['actions']);
        $this->assertSame('column.add', $json['actions'][0]['action']);
    }

    public function test_classroom_ai_service_integrates_gemini(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"reply": "Добавил колонку Задачи", "actions": [{"action": "column.add", "payload": {"title": "Задачи"}}]}'],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'http://localhost:8083/*' => Http::response(['success' => true], 200),
        ]);

        $aiService = app(AiService::class);
        $reply = $aiService->chat('Создай колонку Задачи', 'test-room-42');

        $this->assertSame('Добавил колонку Задачи', $reply['reply'] ?? $reply);
    }

    public function test_tutor_with_pro_plan_can_use_ai_tools(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::PRO,
            'status' => 'active',
            'starts_at' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($tutor);

        $component = Livewire::test(TutorAiAssistantPage::class);
        $this->assertTrue($component->instance()->canUseAi());
    }

    public function test_tutor_with_start_plan_cannot_use_ai_tools_without_upgrade(): void
    {
        $tutor = User::factory()->create([
            'role' => UserRole::Tutor,
        ]);

        Subscription::create([
            'tutor_id' => $tutor->id,
            'plan' => SubscriptionPlan::START,
            'status' => 'active',
            'starts_at' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($tutor);

        $component = Livewire::test(TutorAiAssistantPage::class);
        $this->assertFalse($component->instance()->canUseAi());
    }

    public function test_gemini_xbox_dns_fallback_and_configuration(): void
    {
        $gemini = new GeminiService(apiKey: 'test-key', useXboxDns: true);
        $ips = $gemini->getXboxDnsIps();

        $this->assertIsArray($ips);
        $this->assertNotEmpty($ips);
        foreach ($ips as $ip) {
            $this->assertNotFalse(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
        }
    }
}
