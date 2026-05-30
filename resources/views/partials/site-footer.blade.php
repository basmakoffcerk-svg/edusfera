@php
    $dark = ($variant ?? '') === 'dark';
@endphp

<footer class="{{ $dark ? 'bg-[#09090b] border-white/5' : 'bg-white border-gray-100' }} border-t relative overflow-hidden">
    {{-- Decorative background --}}
    @if($dark)
        <div class="absolute top-0 right-0 w-[400px] h-[400px] bg-[radial-gradient(circle,rgba(125,57,235,0.04)_0%,transparent_70%)] pointer-events-none translate-x-1/3 -translate-y-1/2"></div>
    @else
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[radial-gradient(circle,rgba(125,57,235,0.02)_0%,transparent_70%)] pointer-events-none translate-x-1/3 -translate-y-1/2"></div>
    @endif

    <div class="max-w-7xl mx-auto px-6 relative z-10">

        {{-- Links grid --}}
        <div class="py-10 md:py-12 grid grid-cols-2 sm:grid-cols-4 gap-6 lg:gap-10">
            {{-- Brand --}}
            <div>
                <a href="{{ route('home') }}" class="font-rimma font-black text-lg tracking-tighter {{ $dark ? 'text-white' : 'text-gray-900' }} mb-3 block">EDUSFERA.</a>
                <p class="text-xs {{ $dark ? 'text-gray-500' : 'text-gray-400' }} leading-relaxed max-w-xs">
                    Платформа для легального поиска репетиторов и безопасной оплаты занятий.
                </p>
            </div>

            <div>
                <h4 class="font-bold {{ $dark ? 'text-white' : 'text-gray-900' }} mb-3 text-xs uppercase tracking-wider">Обучение</h4>
                <ul class="space-y-2 text-sm font-medium">
                    <li><a href="{{ route('tutors.index') }}" class="{{ $dark ? 'text-gray-400 hover:text-lime-400' : 'text-gray-500 hover:text-violet-600' }} transition-colors">Каталог репетиторов</a></li>
                    <li><a href="{{ route('for-tutors') }}" class="{{ $dark ? 'text-gray-400 hover:text-lime-400' : 'text-gray-500 hover:text-violet-600' }} transition-colors">Преподавателям</a></li>
                    <li><a href="{{ route('home') }}#how-it-works" class="{{ $dark ? 'text-gray-400 hover:text-lime-400' : 'text-gray-500 hover:text-violet-600' }} transition-colors">Как это работает</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-bold {{ $dark ? 'text-white' : 'text-gray-900' }} mb-3 text-xs uppercase tracking-wider">Документы</h4>
                <ul class="space-y-2 text-sm font-medium">
                    <li><a href="{{ route('legal.offer') }}" class="{{ $dark ? 'text-gray-400 hover:text-lime-400' : 'text-gray-500 hover:text-violet-600' }} transition-colors">Публичная оферта</a></li>
                    <li><a href="{{ route('legal.refund') }}" class="{{ $dark ? 'text-gray-400 hover:text-lime-400' : 'text-gray-500 hover:text-violet-600' }} transition-colors">Правила возврата</a></li>
                    <li><a href="{{ route('legal.privacy') }}" class="{{ $dark ? 'text-gray-400 hover:text-lime-400' : 'text-gray-500 hover:text-violet-600' }} transition-colors">Конфиденциальность</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-bold {{ $dark ? 'text-white' : 'text-gray-900' }} mb-3 text-xs uppercase tracking-wider">Связь</h4>
                <ul class="space-y-2 text-sm font-medium">
                    <li><a href="{{ route('contacts') }}" class="{{ $dark ? 'text-gray-400 hover:text-lime-400' : 'text-gray-500 hover:text-violet-600' }} transition-colors">Контакты</a></li>
                    <li><a href="mailto:support@edusfera.by" class="{{ $dark ? 'text-gray-400 hover:text-lime-400' : 'text-gray-500 hover:text-violet-600' }} transition-colors">support@edusfera.by</a></li>
                </ul>
            </div>
        </div>

        {{-- Copyright --}}
        <div class="flex flex-col sm:flex-row justify-between items-center gap-2 py-5 border-t {{ $dark ? 'border-white/5' : 'border-gray-100' }} text-xs font-medium {{ $dark ? 'text-gray-500' : 'text-gray-400' }}">
            <p>© {{ date('Y') }} Edusfera.by. Все права защищены.</p>
            <div class="flex items-center gap-3 flex-wrap justify-center">
                <span>Минск, Беларусь</span>
                <span class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-lime-400 relative inline-block">
                        <span class="animate-ping absolute inset-0 rounded-full bg-lime-400 opacity-75"></span>
                    </span>
                    Система работает стабильно
                </span>
            </div>
        </div>
    </div>
</footer>
