@php
    $dark = ($variant ?? '') === 'dark';
@endphp

<footer class="{{ $dark ? 'bg-[#09090b] border-white/10' : 'bg-[#0f1115] border-gray-800' }} text-gray-400 border-t relative overflow-hidden font-sans">
    {{-- Decorative background glow --}}
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[radial-gradient(circle,rgba(125,57,235,0.06)_0%,transparent_70%)] pointer-events-none translate-x-1/3 -translate-y-1/2"></div>
    <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[radial-gradient(circle,rgba(198,255,51,0.04)_0%,transparent_70%)] pointer-events-none -translate-x-1/3 translate-y-1/2"></div>

    <div class="max-w-7xl mx-auto px-6 py-12 md:py-16 relative z-10">

        {{-- Top Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-10 mb-12">
            
            {{-- Brand Column (lg:col-span-4) --}}
            <div class="lg:col-span-4">
                <a href="{{ route('home') }}" class="font-rimma font-black text-2xl tracking-tighter text-white mb-4 flex items-center gap-2 group">
                    EDUSFERA.
                    <span class="inline-block w-2 h-2 rounded-full bg-lime-400 group-hover:scale-125 transition-transform"></span>
                </a>
                <p class="text-sm text-gray-400 leading-relaxed mb-6 max-w-sm">
                    Первая белорусская платформа для легального преподавания, подбора проверенных репетиторов из госреестра и безопасной оплаты через ЕРИП и безналичные платежи.
                </p>
                <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs font-bold text-lime-400">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-lime-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-lime-400"></span>
                    </span>
                    Платформа на стадии активного роста
                </div>
            </div>

            {{-- Navigation Column (lg:col-span-2) --}}
            <div class="lg:col-span-2">
                <h4 class="font-bold text-white text-xs uppercase tracking-widest mb-4">Платформа</h4>
                <ul class="space-y-2.5 text-sm font-medium">
                    <li><a href="{{ route('tutors.index') }}" class="text-gray-400 hover:text-lime-400 transition-colors">Каталог репетиторов</a></li>
                    <li><a href="{{ route('for-tutors') }}" class="text-gray-400 hover:text-lime-400 transition-colors">Преподавателям</a></li>
                    <li><a href="{{ route('diagnostic.show') }}" class="text-gray-400 hover:text-lime-400 transition-colors">ИИ-Диагностика РИКЗ</a></li>
                    <li><a href="{{ route('news.index') }}" class="text-gray-400 hover:text-lime-400 transition-colors">Новости и статьи</a></li>
                </ul>
            </div>

            {{-- Legal Documents Column (lg:col-span-3) --}}
            <div class="lg:col-span-3">
                <h4 class="font-bold text-white text-xs uppercase tracking-widest mb-4">Документы (bePaid & БСБ)</h4>
                <ul class="space-y-2.5 text-sm font-medium">
                    <li><a href="{{ route('legal.offer') }}" class="text-gray-400 hover:text-lime-400 transition-colors">Публичная оферта и тарифы</a></li>
                    <li><a href="{{ route('legal.privacy') }}" class="text-gray-400 hover:text-lime-400 transition-colors">Конфиденциальность (Закон 99-З)</a></li>
                    <li><a href="{{ route('legal.payment-security') }}" class="text-gray-400 hover:text-lime-400 transition-colors">Правила оплаты и безопасность</a></li>
                    <li><a href="{{ route('legal.refund') }}" class="text-gray-400 hover:text-lime-400 transition-colors">Правила возврата и отмены</a></li>
                </ul>
            </div>

            {{-- Contacts Column (lg:col-span-3) --}}
            <div class="lg:col-span-3">
                <h4 class="font-bold text-white text-xs uppercase tracking-widest mb-4">Контакты и режим работы</h4>
                <ul class="space-y-2.5 text-sm font-medium mb-3">
                    <li><a href="{{ route('contacts') }}" class="text-gray-400 hover:text-lime-400 transition-colors">Контакты ООО «Эдусфера»</a></li>
                    <li><a href="mailto:edusferaby@gmail.com" class="text-gray-400 hover:text-lime-400 transition-colors font-semibold">edusferaby@gmail.com</a></li>
                    <li><a href="tel:+375295190821" class="text-gray-400 hover:text-lime-400 transition-colors font-semibold">+375 (29) 519-08-21</a></li>
                </ul>
                <div class="text-xs text-gray-500 leading-normal pt-1">
                    Режим работы: Пн-Пт 09:00 – 18:00<br>(Сб-Вс: выходной)
                </div>
            </div>

        </div>

        {{-- Payment Logos Banner --}}
        <div class="border-t border-white/10 pt-6 pb-6 my-6">
            <img src="{{ asset('promo/payment-logos-full.svg') }}" 
                 alt="bePaid, БЕЛКАРТ, Visa, Mastercard, Samsung Pay, Google Pay" 
                 class="w-full max-w-full h-auto object-contain block opacity-85 hover:opacity-100 transition-opacity" 
                 loading="lazy">
        </div>

        {{-- Legal Info & Bank Details Grid --}}
        <div class="border-t border-white/10 pt-6 my-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-gray-400 leading-relaxed">
            <div>
                <strong class="text-white block mb-1 font-bold">Юридическая информация</strong>
                Наименование: Общество с ограниченной ответственностью «Эдусфера»<br>
                Гос. регистрация: Зарегистрировано Минским горисполкомом 25.10.2023 г., УНП 192854899<br>
                Юридический адрес: Республика Беларусь, 220100, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29
            </div>
            <div>
                <strong class="text-white block mb-1 font-bold">Банковские реквизиты</strong>
                Расчетный счет (IBAN): BY64UNBS30121685100000001933<br>
                Банк: ЗАО «БСБ Банк»<br>
                БИК банка: UNBSBY2X
            </div>
        </div>

        {{-- Bottom Copyright --}}
        <div class="border-t border-white/10 pt-6 mt-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-gray-400">
            <div>© <span id="site-footer-year">{{ date('Y') }}</span> ООО «Эдусфера». Все права защищены.</div>
            <div class="flex items-center gap-4 text-gray-400">
                <span>Минск, Беларусь</span>
                <span>·</span>
                <span>Сделано для преподавателей и абитуриентов</span>
            </div>
        </div>

    </div>
</footer>
