<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Tariffs & Limits form -->
        <form wire:submit="saveLimits" class="space-y-6">
            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h3 class="text-xl font-black tracking-[-0.04em] text-stone-950 mb-4">Конструктор лимитов тарифов</h3>
                
                {{ $this->form }}

                <div class="mt-6">
                    <x-filament::button type="submit" size="lg">
                        Обновить лимиты
                    </x-filament::button>
                </div>
            </section>
        </form>

        <!-- Quotas and optimization grid -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Quotas list -->
            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h3 class="text-xl font-black tracking-[-0.04em] text-stone-950 mb-2">Контроль дисковых квот</h3>
                <p class="text-sm text-stone-500 mb-6">Список пользователей с наибольшим заполнением персонального хранилища</p>

                <div class="space-y-4">
                    @forelse($usersQuotas as $quota)
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-sm font-semibold">
                                <span class="text-stone-900">{{ $quota['name'] }} <span class="text-xs text-stone-400 font-medium">({{ $quota['email'] }})</span></span>
                                <span class="text-stone-600">{{ $quota['used'] }} / {{ $quota['total'] }}</span>
                            </div>
                            <!-- Progress Bar -->
                            <div class="w-full bg-stone-100 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $quota['percentage'] > 90 ? 'bg-rose-500' : ($quota['percentage'] > 75 ? 'bg-amber-500' : 'bg-violet-600') }}" style="width: {{ $quota['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-stone-400">Нет данных о квотах пользователей</p>
                    @endforelse
                </div>
            </section>

            <!-- Storage Optimization section -->
            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-xl font-black tracking-[-0.04em] text-stone-950 mb-2">Оптимизация S3 и диска</h3>
                    <p class="text-sm text-stone-500 mb-4">Сжатие старых медиафайлов и очистка кэша уроков неактивных пользователей для экономии дискового пространства.</p>
                    
                    <div class="rounded-xl bg-violet-50/50 border border-violet-100 p-4 mb-6">
                        <h4 class="text-sm font-bold text-violet-900 mb-1">Политика автоочистки</h4>
                        <p class="text-xs text-violet-800/80 leading-5">Каждые 30 дней система автоматически сжимает старые изображения (> 2 МБ) и удаляет кэш сессий досок, если урок завершен более 3 месяцев назад.</p>
                    </div>
                </div>

                <div>
                    <x-filament::button wire:click="runOptimization" icon="heroicon-o-sparkles" size="lg" color="warning" class="w-full">
                        Запустить принудительную очистку
                    </x-filament::button>
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
