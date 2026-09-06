<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section icon="heroicon-o-cpu-chip">
            <x-slot name="heading">
                Шлюз ИИ-провайдеров и Маршрутизация Моделей
            </x-slot>

            <x-slot name="description">
                Управление API-ключами, лимитами генераций и резервными LLM-шлюзами
            </x-slot>

            <x-slot name="headerEnd">
                <x-filament::button wire:click="testConnection" icon="heroicon-o-bolt" size="sm" color="primary">
                    Проверить API соединение
                </x-filament::button>
            </x-slot>

            <x-filament::grid default="1" sm="3" class="gap-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 font-bold">
                            A
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">Anthropic Claude</p>
                            <p class="text-xs text-gray-500">Основной шлюз</p>
                        </div>
                    </div>
                    <x-filament::badge color="success">Активен</x-filament::badge>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-bold">
                            O
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">OpenAI ChatGPT</p>
                            <p class="text-xs text-gray-500">Резервный шлюз</p>
                        </div>
                    </div>
                    <x-filament::badge color="success">Активен</x-filament::badge>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-bold">
                            L
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">Local / DeepSeek</p>
                            <p class="text-xs text-gray-500">Локальный провайдер</p>
                        </div>
                    </div>
                    <x-filament::badge color="gray">Готов</x-filament::badge>
                </div>
            </x-filament::grid>
        </x-filament::section>

        {{-- Form Section --}}
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-check-circle">
                    Сохранить настройки
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
