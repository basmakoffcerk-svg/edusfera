<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <div class="space-y-4">
                {{ $this->form }}
            </div>

            <div class="mt-6">
                <x-filament::button type="submit" size="lg">
                    Сохранить настройки
                </x-filament::button>
            </div>
        </section>
    </form>
</x-filament-panels::page>
