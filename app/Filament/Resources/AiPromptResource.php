<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AiPromptResource\Pages;
use App\Models\AiPrompt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiPromptResource extends Resource
{
    protected static ?string $model = AiPrompt::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label('Ключ промпта')
                    ->required()
                    ->disabled(fn (?AiPrompt $record) => $record !== null)
                    ->unique(ignoreRecord: true)
                    ->placeholder('tutor_assistant'),
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->placeholder('Ассистент репетитора'),
                Forms\Components\Textarea::make('system_prompt')
                    ->label('Системный промпт (System Prompt)')
                    ->required()
                    ->rows(12)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('description')
                    ->label('Описание контекста использования')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('key')
                    ->label('Ключ')
                    ->copyable()
                    ->badge(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAiPrompts::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Промпт-инжиниринг';
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
