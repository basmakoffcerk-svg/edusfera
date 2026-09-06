<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Services Health Cards Grid --}}
        <x-filament::section>
            <x-slot name="heading">
                Состояние микросервисов
            </x-slot>
            
            <x-slot name="description">
                Задержка отклика (Latency) и работоспособность узлов
            </x-slot>

            <x-slot name="headerEnd">
                <x-filament::button wire:click="refreshStatus" icon="heroicon-o-arrow-path" color="gray" size="sm">
                    Обновить статус
                </x-filament::button>
            </x-slot>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $key => $service)
                    @php
                        $isOnline = $service['status'] === 'online';
                    @endphp
                    <div class="rounded-xl border border-gray-200 p-4 bg-white shadow-2xs">
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $isOnline ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                <span class="h-2 w-2 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $isOnline ? 'Online' : 'Offline' }}
                            </span>

                            <span class="text-xs font-mono font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                                {{ $service['latency'] }}
                            </span>
                        </div>

                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $service['type'] ?? 'Node' }}</span>
                            <h4 class="text-sm font-bold text-gray-900">{{ $service['name'] }}</h4>
                            <p class="text-xs font-mono text-gray-400 truncate">{{ $service['url'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- WebRTC Load Center --}}
        <x-filament::section>
            <x-slot name="heading">
                Нагрузка видеосервера (WebRTC SFU)
            </x-slot>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Активные видеосессии</span>
                    <p class="text-2xl font-black text-gray-900 mt-1">{{ $webrtcMetrics['active_sessions'] }}</p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Суммарный битрейт</span>
                    <p class="text-2xl font-black text-gray-900 mt-1">{{ $webrtcMetrics['total_bandwidth'] }}</p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Средняя задержка</span>
                    <p class="text-2xl font-black text-gray-900 mt-1">{{ $webrtcMetrics['average_latency'] }}</p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Режим SD Fallback</span>
                    <p class="text-2xl font-black text-gray-900 mt-1">{{ $webrtcMetrics['sd_fallback_count'] }}</p>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-3">Журнал связи</h4>
                <div class="space-y-2">
                    @foreach($webrtcMetrics['call_quality_warnings'] as $warning)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 border border-gray-200 px-3.5 py-2.5 text-xs text-gray-700">
                            <span><strong class="font-bold text-gray-900">{{ $warning['tutor'] }}:</strong> {{ $warning['reason'] }}</span>
                            <span class="text-[11px] text-gray-400 font-mono">{{ $warning['time'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
