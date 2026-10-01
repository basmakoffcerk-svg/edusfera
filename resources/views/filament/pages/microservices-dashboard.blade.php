<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Services Health Cards Grid --}}
        <x-filament::section>
            <x-slot name="heading">
                Состояние микросервисов платформы
            </x-slot>
            
            <x-slot name="description">
                Задержка отклика (Latency) и работоспособность ключевых узлов Edusfera
            </x-slot>

            <x-slot name="headerEnd">
                <div class="flex items-center gap-2">
                    <x-filament::button wire:click="purgeAllClassroomSignals" icon="heroicon-o-trash" color="warning" size="sm" outlined>
                        Очистить сигнальный кэш
                    </x-filament::button>
                    <x-filament::button wire:click="refreshStatus" icon="heroicon-o-arrow-path" color="primary" size="sm">
                        Обновить статус
                    </x-filament::button>
                </div>
            </x-slot>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $key => $service)
                    @php
                        $isOnline = $service['status'] === 'online';
                    @endphp
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900 shadow-2xs">
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $isOnline ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400' }}">
                                <span class="h-2 w-2 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $isOnline ? 'Online' : 'Offline' }}
                            </span>

                            <span class="text-xs font-mono font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                                {{ $service['latency'] }}
                            </span>
                        </div>

                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $service['type'] ?? 'Node' }}</span>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $service['name'] }}</h4>
                            <p class="text-xs font-mono text-gray-400 truncate">{{ $service['url'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- WebRTC Signaling & Relay Infrastructure --}}
        <x-filament::section>
            <x-slot name="heading">
                Инфраструктура WebRTC и шлюзов связи
            </x-slot>
            <x-slot name="description">
                Состояние серверов согласования сигналов и обхода NAT/Firewall
            </x-slot>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-gray-50 dark:bg-gray-900/60">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Активные видеоклассы</span>
                    <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $webrtcSignalingStatus['active_rooms'] ?? 0 }}</p>
                    <span class="text-[11px] text-gray-500">сессий прямо сейчас</span>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-gray-50 dark:bg-gray-900/60">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Подключенные пиры</span>
                    <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $webrtcSignalingStatus['connected_peers'] ?? 0 }}</p>
                    <span class="text-[11px] text-gray-500">участников онлайн</span>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-gray-50 dark:bg-gray-900/60">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Задержка сигналов</span>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $webrtcSignalingStatus['latency'] ?? '< 1 ms' }}</p>
                    <span class="text-[11px] text-gray-500">драйвер: {{ $webrtcSignalingStatus['cache_store'] ?? 'file' }}</span>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 bg-gray-50 dark:bg-gray-900/60">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Всего уроков сегодня</span>
                    <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $webrtcSignalingStatus['total_today'] ?? 0 }}</p>
                    <span class="text-[11px] text-gray-500">запусков сессий</span>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Состояние STUN / TURN Релеев (Обход NAT и мобильных сетей)</h4>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($iceRelayStatus as $relay)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-gray-900/80 border border-gray-200 dark:border-gray-800 p-3 text-xs">
                            <div>
                                <span class="font-bold text-gray-900 dark:text-gray-200 block truncate max-w-[160px]">{{ $relay['name'] }}</span>
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">● Доступен</span>
                            </div>
                            <span class="font-mono text-gray-600 dark:text-gray-300 font-bold bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                                {{ $relay['latency'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-filament::section>

        {{-- Active Classroom Live Sessions Table --}}
        <x-filament::section>
            <x-slot name="heading">
                Активные сессии виртуальных классов (Live Rooms)
            </x-slot>
            <x-slot name="description">
                Прямой контроль звонков в реальном времени с возможностью сброса и рестарта WebRTC
            </x-slot>

            @if(count($activeClassrooms) > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-800/80 text-[11px] uppercase tracking-wider text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4">Урок</th>
                                <th class="py-3 px-4">Предмет</th>
                                <th class="py-3 px-4">Преподаватель</th>
                                <th class="py-3 px-4">Ученик</th>
                                <th class="py-3 px-4">Статус связи</th>
                                <th class="py-3 px-4">Маршрут</th>
                                <th class="py-3 px-4">Старт</th>
                                <th class="py-3 px-4 text-right">Действие</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($activeClassrooms as $room)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                    <td class="py-3 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                        #{{ $room['lesson_id'] }}
                                    </td>
                                    <td class="py-3 px-4 font-semibold">
                                        {{ $room['subject'] }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full {{ $room['tutor_online'] ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
                                            <span>{{ $room['tutor_name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full {{ $room['student_online'] ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                            <span>{{ $room['student_name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($room['is_connected'])
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                ✔ Связь активна
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                                ⏳ Согласование
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-mono text-[11px]">
                                        {{ $room['route_type'] }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-400 font-mono">
                                        {{ $room['started_at'] }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <x-filament::button
                                            wire:click="resetLessonSession({{ $room['lesson_id'] }})"
                                            icon="heroicon-o-arrow-path"
                                            color="danger"
                                            size="xs"
                                            outlined
                                            tooltip="Принудительно отправить сигнал рестарта и сбросить кэш">
                                            Сбросить WebRTC
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-800 p-8 text-center bg-gray-50/50 dark:bg-gray-900/40">
                    <span class="text-3xl block mb-2">📡</span>
                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300">В данный момент нет активных уроков</h4>
                    <p class="text-xs text-gray-400 mt-1">При запуске урока репетитором комната появится здесь в реальном времени с параметрами задержки и маршрута связи.</p>
                </div>
            @endif
        </x-filament::section>

    </div>
</x-filament-panels::page>
