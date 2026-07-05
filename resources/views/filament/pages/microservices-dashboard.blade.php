<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Health status section -->
        <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-black tracking-[-0.04em] text-stone-950">Статус микросервисов (Health Check)</h3>
                    <p class="text-sm text-stone-500">Автоматический опрос состояния распределенной архитектуры в реальном времени</p>
                </div>
                <x-filament::button wire:click="refreshStatus" icon="heroicon-o-arrow-path">
                    Обновить
                </x-filament::button>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($services as $key => $service)
                    <div class="rounded-2xl border border-stone-100 p-5 flex flex-col justify-between {{ $service['status'] === 'online' ? 'bg-emerald-50/30' : 'bg-rose-50/30' }}">
                        <div class="flex items-center justify-between mb-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $service['status'] === 'online' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $service['status'] === 'online' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $service['status'] === 'online' ? 'Online' : 'Offline' }}
                            </span>
                            <span class="text-xs font-bold text-stone-400">Latency: {{ $service['latency'] }}</span>
                        </div>
                        <div>
                            <h4 class="text-md font-bold text-stone-900">{{ $service['name'] }}</h4>
                            <p class="text-xs text-stone-400 mt-1">{{ $service['url'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- WebRTC Dashboard section -->
        <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <h3 class="text-xl font-black tracking-[-0.04em] text-stone-950 mb-4">Нагрузка на видеосервер (WebRTC & SFU)</h3>
            
            <div class="grid gap-4 md:grid-cols-4 mb-6">
                <div class="rounded-2xl border border-stone-100 p-5 bg-stone-50">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Активные звонки</span>
                    <p class="text-3xl font-black text-stone-900 mt-2">{{ $webrtcMetrics['active_sessions'] }}</p>
                </div>
                <div class="rounded-2xl border border-stone-100 p-5 bg-stone-50">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Суммарный битрейт</span>
                    <p class="text-3xl font-black text-stone-900 mt-2">{{ $webrtcMetrics['total_bandwidth'] }}</p>
                </div>
                <div class="rounded-2xl border border-stone-100 p-5 bg-stone-50">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Средняя задержка (RTT)</span>
                    <p class="text-3xl font-black text-stone-900 mt-2">{{ $webrtcMetrics['average_latency'] }}</p>
                </div>
                <div class="rounded-2xl border border-stone-100 p-5 bg-stone-50">
                    <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">В режиме оптимизации SD</span>
                    <p class="text-3xl font-black text-amber-600 mt-2">{{ $webrtcMetrics['sd_fallback_count'] }}</p>
                </div>
            </div>

            <!-- Warnings/Logs -->
            <div>
                <h4 class="text-md font-bold text-stone-900 mb-3">Технические предупреждения качества связи</h4>
                <div class="space-y-2">
                    @foreach($webrtcMetrics['call_quality_warnings'] as $warning)
                        <div class="flex items-center justify-between rounded-xl bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-stone-800">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span><strong>Преподаватель: {{ $warning['tutor'] }}</strong> — {{ $warning['reason'] }}</span>
                            </div>
                            <span class="text-xs text-stone-400 font-semibold">{{ $warning['time'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
