<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;

class SaaSManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cloud';

    protected static string $view = 'filament.pages.saas-management';

    protected static ?string $navigationLabel = 'Хранилище и SaaS';

    protected static ?string $title = 'Лимиты хранилища и тарифы';

    protected static ?int $navigationSort = 60;

    public ?array $data = [];
    public array $usersQuotas = [];

    public function mount(): void
    {
        $this->form->fill([
            'beta_quota' => 2, // ГБ
            'free_ai_limit' => 15, // генераций в час
            'premium_ai_limit' => 200, // генераций в час
        ]);

        $this->loadQuotas();
    }

    public function loadQuotas(): void
    {
        // Выбираем репетиторов для расчета занятого места
        $tutors = User::query()->where('role', 'tutor')->limit(10)->get();
        
        $this->usersQuotas = [];
        $totalGb = 2.0; // Тарифный лимит по умолчанию 2 ГБ
        
        foreach ($tutors as $tutor) {
            $sizeInBytes = \App\Models\ClassroomFile::query()
                ->where('uploaded_by', $tutor->id)
                ->sum('size_bytes') ?: 0;
                
            $sizeInGb = $sizeInBytes / (1024 * 1024 * 1024);
            
            if ($sizeInBytes < 1024 * 1024) {
                $usedFormatted = round($sizeInBytes / 1024, 2) . ' КБ';
            } elseif ($sizeInBytes < 1024 * 1024 * 1024) {
                $usedFormatted = round($sizeInBytes / (1024 * 1024), 2) . ' МБ';
            } else {
                $usedFormatted = round($sizeInGb, 2) . ' ГБ';
            }
            
            $percentage = min(($sizeInGb / $totalGb) * 100, 100);
            
            $this->usersQuotas[] = [
                'name' => $tutor->name,
                'email' => $tutor->email,
                'used' => $usedFormatted,
                'total' => $totalGb . ' ГБ',
                'percentage' => $percentage,
            ];
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('beta_quota')
                            ->label('Квота диска для бета-тестеров (ГБ)')
                            ->numeric()
                            ->required(),
                        TextInput::make('free_ai_limit')
                            ->label('ИИ-генераций в час (Бесплатный)')
                            ->numeric()
                            ->required(),
                        TextInput::make('premium_ai_limit')
                            ->label('ИИ-генераций в час (Премиум)')
                            ->numeric()
                            ->required(),
                    ])
            ])
            ->statePath('data');
    }

    public function saveLimits(): void
    {
        $state = $this->form->getState();
        
        // Заглушка под сохранение лимитов в БД/конфиг
        Notification::make()
            ->title('Лимиты тарифов обновлены')
            ->body("Размер хранилища: {$state['beta_quota']} ГБ. Лимит генераций: {$state['free_ai_limit']}/час.")
            ->success()
            ->send();
    }

    public function runOptimization(): void
    {
        try {
            // Запускаем нашу команду оптимизации
            Artisan::call('app:optimize-storage');
            $output = Artisan::output();

            Notification::make()
                ->title('Оптимизация успешно завершена')
                ->body(nl2br(trim($output)))
                ->success()
                ->send();
                
            $this->loadQuotas();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Ошибка запуска команды')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Хранилище и SaaS-тарифы';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
