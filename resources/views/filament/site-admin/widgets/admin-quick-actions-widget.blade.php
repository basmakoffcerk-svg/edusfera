<x-filament-widgets::widget>
    <section class="rounded-[2.5rem] border border-slate-200/90 bg-white p-7 md:p-8 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-50 border border-violet-200/80 text-violet-700 font-extrabold text-[10px] uppercase tracking-[0.2em] mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-violet-600"></span>
                    Быстрые действия
                </div>
                <h2 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900">Оперативный центр модерации</h2>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">
                    Инструменты контроля качества каталога репетиторов, разбора инцидентов и движения финансовых средств.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="/admin/microservices-dashboard" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 font-extrabold text-xs hover:bg-violet-50 hover:border-violet-200 hover:text-violet-700 transition-all">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                    Мониторинг узлов
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 xl:grid-cols-4 md:grid-cols-2">
            @foreach ($actions as $action)
                @php
                    $isDark = $action['tone'] === 'dark';
                    $isLime = $action['tone'] === 'lime';
                @endphp

                <a href="{{ $action['url'] }}"
                   class="group relative flex min-h-[140px] flex-col justify-between rounded-[2rem] p-6 transition-all duration-200 border overflow-hidden hover:-translate-y-1 hover:shadow-xl {{ $isDark ? 'border-slate-900 bg-slate-950 text-white hover:border-violet-500' : ($isLime ? 'border-violet-200 bg-gradient-to-br from-violet-50 to-indigo-50/50 text-slate-900 hover:border-violet-400' : 'border-slate-200/90 bg-white text-slate-900 hover:border-slate-400') }}">
                    
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-black tracking-tight {{ $isDark ? 'text-white' : 'text-slate-900' }}">
                                {{ $action['label'] }}
                            </h3>
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110 {{ $isDark ? 'bg-white/10 text-violet-400' : 'bg-violet-50 text-violet-600' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </div>
                        </div>
                        <p class="text-xs leading-relaxed {{ $isDark ? 'text-slate-400' : 'text-slate-500' }}">
                            {{ $action['description'] }}
                        </p>
                    </div>

                    <div class="mt-4 pt-3 border-t {{ $isDark ? 'border-white/10' : 'border-slate-100' }} flex items-center justify-between text-xs font-extrabold {{ $isDark ? 'text-violet-400' : 'text-violet-600' }}">
                        <span>Перейти</span>
                        <span class="transition-transform group-hover:translate-x-1">→</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</x-filament-widgets::widget>
