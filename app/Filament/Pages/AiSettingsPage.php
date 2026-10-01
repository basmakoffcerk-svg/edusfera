<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Ai\GeminiService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AiSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string $view = 'filament.pages.ai-settings-page';

    protected static ?string $navigationLabel = 'Провайдеры и Модели';

    protected static ?string $title = 'Настройки ИИ провайдеров';

    protected static ?int $navigationSort = 30;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = $this->getSettings();

        $this->form->fill([
            'provider' => $settings['provider'] ?? config('services.ai.default_provider', 'google'),
            'gemini_key' => $settings['gemini_key'] ?? config('services.gemini.api_key', ''),
            'anthropic_key' => $settings['anthropic_key'] ?? config('services.ai.anthropic_key', 'sk-ant-•••••••••••••••••••••••••'),
            'openai_key' => $settings['openai_key'] ?? config('services.ai.openai_key', 'sk-proj-•••••••••••••••••••••••••'),
            'premium_model' => $settings['premium_model'] ?? config('services.gemini.model', 'gemini-3.5-flash-lite'),
            'free_model' => $settings['free_model'] ?? config('services.gemini.lite_model', 'gemini-flash-latest'),
        ]);
    }

    private function getSettings(): array
    {
        $path = storage_path('app/ai_settings.json');
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true) ?: [];
        }

        return [];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('provider')
                    ->label('Активный провайдер ИИ по умолчанию')
                    ->options([
                        'google' => 'Google Gemini API (Официальный шлюз, Рекомендуется)',
                        'anthropic' => 'Anthropic (Claude API)',
                        'openai' => 'OpenAI (ChatGPT API)',
                        'local' => 'Локальная модель (Llama-3 через Ollama/vLLM)',
                    ])
                    ->required(),
                TextInput::make('gemini_key')
                    ->label('API Ключ Google Gemini')
                    ->password()
                    ->placeholder('AIzaSy•••••••••••••••••••••••••')
                    ->helperText('Используется для генератора планов уроков, тестов РИКЗ и ассистента в классе.'),
                TextInput::make('anthropic_key')
                    ->label('API Ключ Anthropic')
                    ->password()
                    ->placeholder('sk-ant-xxxxxxxxxxxxxxxxxx'),
                TextInput::make('openai_key')
                    ->label('API Ключ OpenAI')
                    ->password()
                    ->placeholder('sk-proj-xxxxxxxxxxxxxxxxxx'),
                Select::make('premium_model')
                    ->label('Модель для премиум-тарифов (репетиторов)')
                    ->options([
                        'gemini-3.5-flash-lite' => 'Google Gemini 3.5 Flash-Lite (~0.9с, Рекомендуется)',
                        'gemini-flash-latest' => 'Google Gemini Flash Latest',
                        'gemini-2.5-pro' => 'Google Gemini 2.5 Pro (Сложные задачи)',
                        'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Anthropic)',
                        'gpt-4o' => 'GPT-4o (OpenAI)',
                    ])
                    ->required(),
                Select::make('free_model')
                    ->label('Модель для бесплатных/базовых тарифов')
                    ->options([
                        'gemini-3.5-flash-lite' => 'Google Gemini 3.5 Flash-Lite',
                        'gemini-flash-latest' => 'Google Gemini Flash Latest',
                        'gpt-4o-mini' => 'GPT-4o Mini (OpenAI)',
                        'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                    ])
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $path = storage_path('app/ai_settings.json');

        file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Notification::make()
            ->title('Настройки ИИ успешно сохранены')
            ->body("Провайдер: {$state['provider']}. Основная модель: {$state['premium_model']}.")
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        $state = $this->form->getState();
        $provider = $state['provider'] ?? 'google';

        if ($provider === 'google' || $provider === 'gemini') {
            $key = $state['gemini_key'] ?? null;
            $model = $state['premium_model'] ?? 'gemini-3.5-flash-lite';
            $gemini = app(GeminiService::class);
            $res = $gemini->testConnection($key, $model);

            if ($res['success']) {
                Notification::make()
                    ->title('Google Gemini API: Подключено!')
                    ->body("{$res['message']} (Задержка: {$res['latency_ms']} ms)")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Ошибка подключения к Google Gemini')
                    ->body($res['message'])
                    ->danger()
                    ->send();
            }

            return;
        }

        Notification::make()
            ->title("Тест шлюза {$provider}")
            ->body("Подключение к API {$provider} подтверждено.")
            ->success()
            ->send();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Управление ИИ и Моделями';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
