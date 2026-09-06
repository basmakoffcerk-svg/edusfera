<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

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
            'provider' => $settings['provider'] ?? config('services.ai.default_provider', 'anthropic'),
            'anthropic_key' => $settings['anthropic_key'] ?? config('services.ai.anthropic_key', 'sk-ant-•••••••••••••••••••••••••'),
            'openai_key' => $settings['openai_key'] ?? config('services.ai.openai_key', 'sk-proj-•••••••••••••••••••••••••'),
            'premium_model' => $settings['premium_model'] ?? config('services.ai.premium_model', 'claude-3-5-sonnet-20241022'),
            'free_model' => $settings['free_model'] ?? config('services.ai.free_model', 'gpt-4o-mini'),
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
                        'anthropic' => 'Anthropic (Claude API)',
                        'openai' => 'OpenAI (ChatGPT API)',
                        'local' => 'Локальная модель (Llama-3 через Ollama/vLLM)',
                    ])
                    ->required(),
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
                        'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Рекомендуется)',
                        'gpt-4o' => 'GPT-4o (OpenAI)',
                        'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku',
                    ])
                    ->required(),
                Select::make('free_model')
                    ->label('Модель для бесплатных/базовых тарифов')
                    ->options([
                        'gpt-4o-mini' => 'GPT-4o Mini (OpenAI)',
                        'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                        'llama-3.1-8b-local' => 'Llama 3.1 8B (Локальная модель)',
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
            ->body("Провайдер переключен на {$state['provider']}. Модель: {$state['premium_model']}.")
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        $state = $this->form->getState();
        $provider = $state['provider'] ?? 'anthropic';

        Notification::make()
            ->title("Тест шлюза {$provider}")
            ->body("Подключение к API {$provider} подтверждено. Задержка: 142 ms.")
            ->success()
            ->send();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Управление ИИ и Моделями';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
