<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section icon="heroicon-o-server-stack">
            <x-slot name="heading">
                Управление тарифами и квотами хранилища (SaaS Hub)
            </x-slot>

            <x-slot name="description">
                Настройка лимитов использования диска, генераций ИИ по тарифам и регламент автоматической очистки S3
            </x-slot>

            <x-slot name="headerEnd">
                <x-filament::button wire:click="runOptimization" icon="heroicon-o-sparkles" size="sm" color="warning">
                    Запустить очистку S3
                </x-filament::button>
            </x-slot>
        </x-filament::section>

        {{-- Tariffs & Limits Form --}}
        <form wire:submit="saveLimits" class="space-y-6">
            <x-filament::section icon="heroicon-o-adjustments-horizontal">
                <x-slot name="heading">
                    Конструктор лимитов и тарифов
                </x-slot>

                <x-slot name="description">
                    Параметры квот для категорий пользователей платформы
                </x-slot>

                <div class="space-y-6">
                    {{ $this->form }}
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-800 flex justify-end">
                    <x-filament::button type="submit" color="primary" size="md">
                        Сохранить лимиты
                    </x-filament::button>
                </div>
            </x-filament::section>
        </form>
    </div>
</x-filament-panels::page>
