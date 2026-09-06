<footer class="w-full bg-[#010101] text-white pt-12 pb-16 font-sans mt-16">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Outer Layered Card --}}
        <div class="bg-[#0B0F19]/90 rounded-[40px] sm:rounded-[48px] border border-slate-800/80 shadow-2xl overflow-hidden backdrop-blur-2xl">
            
            {{-- Inner Box --}}
            <div class="bg-[#101726]/85 rounded-[32px] sm:rounded-[40px] m-2 sm:m-3 border border-slate-700/40 p-6 sm:p-10 lg:p-12 shadow-inner">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-10">
                    
                    {{-- Brand Column (lg:col-span-4) --}}
                    <div class="lg:col-span-4 space-y-6">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#7D39EB] to-[#5B21B6] border border-violet-400/40 flex items-center justify-center shadow-lg shadow-violet-900/30 shrink-0">
                                <svg width="22" height="22" viewBox="0 0 64 64" class="w-5 h-5">
                                    <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                                    <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                                </svg>
                            </div>
                            <a href="{{ route('home') }}" class="text-2xl font-black tracking-tight text-white uppercase font-rimma">
                                EDUSFERA
                            </a>
                        </div>
                        
                        <p class="text-slate-400 text-sm leading-relaxed max-w-sm font-normal">
                            Первая белорусская платформа умной подготовки к ЦТ и ЦЭ с ИИ-агентами, подбором проверенных репетиторов из реестра и безопасными платежами.
                        </p>

                        <div class="pt-1">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-900/90 border border-slate-700/60 text-xs font-semibold text-[#C6FF33]">
                                <span class="w-2 h-2 rounded-full bg-[#C6FF33] animate-pulse"></span>
                                Платформа 2026 · Реестр РБ
                            </span>
                        </div>
                    </div>

                    {{-- Col 2: ПЛАТФОРМА --}}
                    <div class="lg:col-span-2 space-y-4">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-widest">
                            Платформа
                        </h4>
                        <ul class="space-y-3 text-sm">
                            <li>
                                <a href="{{ route('tutors.index') }}" class="text-slate-400 hover:text-[#C6FF33] transition-colors">
                                    Каталог репетиторов
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('for-tutors') }}" class="text-slate-400 hover:text-[#C6FF33] transition-colors">
                                    Преподавателям
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('diagnostic.show') }}" class="text-slate-400 hover:text-[#C6FF33] transition-colors flex items-center justify-between">
                                    <span>ИИ-Диагностика</span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-violet-500/20 text-violet-300 border border-violet-500/30 font-bold">2026</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('news.index') }}" class="text-slate-400 hover:text-[#C6FF33] transition-colors">
                                    Новости и статьи
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- Col 3: ПРАВОВЫЕ ДОКУМЕНТЫ --}}
                    <div class="lg:col-span-3 space-y-4">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-widest">
                            Документы
                        </h4>
                        <ul class="space-y-3 text-sm">
                            <li>
                                <a href="{{ route('legal.offer') }}" class="text-slate-400 hover:text-white transition-colors">
                                    Публичная оферта и тарифы
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('legal.privacy') }}" class="text-slate-400 hover:text-white transition-colors">
                                    Конфиденциальность (99-З)
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('legal.payment-security') }}" class="text-slate-400 hover:text-white transition-colors">
                                    Безопасность платежей
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('legal.refund') }}" class="text-slate-400 hover:text-white transition-colors">
                                    Правила возврата и отмены
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- Col 4: КОНТАКТЫ И РЕЖИМ РАБОТЫ --}}
                    <div class="lg:col-span-3 space-y-4">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-widest">
                            Контакты и офис
                        </h4>
                        <ul class="space-y-3 text-sm">
                            <li>
                                <a href="{{ route('contacts') }}" class="text-slate-300 font-semibold hover:text-[#C6FF33] transition-colors">
                                    Контакты ООО «Эдусфера»
                                </a>
                            </li>
                            <li>
                                <a href="mailto:edusferaby@gmail.com" class="text-slate-400 hover:text-white transition-colors">
                                    edusferaby@gmail.com
                                </a>
                            </li>
                            <li>
                                <a href="tel:+375295190821" class="text-slate-300 font-bold hover:text-[#C6FF33] transition-colors">
                                    +375 (29) 519-08-21
                                </a>
                            </li>
                        </ul>
                        <p class="text-xs text-slate-500 pt-1 leading-relaxed">
                            Режим работы: Пн-Пт 09:00 – 18:00 (Минск)
                        </p>
                    </div>

                </div>

                {{-- Payment Systems & Acquiring Logos Strip --}}
                <div class="mt-8 pt-8 border-t border-slate-800/80">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-4 text-center sm:text-left">
                        Принимаем к онлайн-оплате через интернет-эквайринг ЗАО «Альфа-Банк»
                    </p>
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                        <div class="bg-white rounded-xl px-3.5 py-1.5 flex items-center justify-center shadow-sm h-11 border border-slate-700/30">
                            <img src="/logo/alfa-bank.svg" alt="ЗАО «Альфа-Банк»" class="h-6 max-w-[115px] object-contain">
                        </div>
                        <div class="bg-white rounded-xl px-3.5 py-1.5 flex items-center justify-center shadow-sm h-11 border border-slate-700/30">
                            <img src="/logo/belkart.png" alt="БЕЛКАРТ" class="h-7 max-w-[90px] object-contain">
                        </div>
                        <div class="bg-white rounded-xl px-3.5 py-1.5 flex items-center justify-center shadow-sm h-11 border border-slate-700/30">
                            <img src="/logo/belkart-internetparol.svg" alt="Белкарт ИнтернетПароль" class="h-6 max-w-[110px] object-contain">
                        </div>
                        <div class="bg-white rounded-xl px-3.5 py-1.5 flex items-center justify-center shadow-sm h-11 border border-slate-700/30">
                            <img src="/logo/visa.svg" alt="VISA" class="h-5 max-w-[70px] object-contain">
                        </div>
                        <div class="bg-white rounded-xl px-3.5 py-1.5 flex items-center justify-center shadow-sm h-11 border border-slate-700/30">
                            <img src="/logo/visa-secure.svg" alt="Visa Secure" class="h-7 max-w-[65px] object-contain">
                        </div>
                        <div class="bg-white rounded-xl px-3.5 py-1.5 flex items-center justify-center shadow-sm h-11 border border-slate-700/30">
                            <img src="/logo/mastercard.svg" alt="MasterCard" class="h-6 max-w-[75px] object-contain">
                        </div>
                        <div class="bg-white rounded-xl px-3.5 py-1.5 flex items-center justify-center shadow-sm h-11 border border-slate-700/30">
                            <img src="/logo/mastercard-id-check.svg" alt="Mastercard Identity Check" class="h-7 max-w-[100px] object-contain">
                        </div>
                        <div class="bg-white rounded-xl px-3.5 py-1.5 flex items-center justify-center shadow-sm h-11 border border-slate-700/30">
                            <img src="/logo/apple-pay.svg" alt="Apple Pay" class="h-5 max-w-[60px] object-contain">
                        </div>
                        <div class="bg-white rounded-xl px-3.5 py-1.5 flex items-center justify-center shadow-sm h-11 border border-slate-700/30">
                            <img src="/logo/samsung-pay.svg" alt="Samsung Pay" class="h-5 max-w-[95px] object-contain">
                        </div>
                    </div>
                </div>

                {{-- Legal Information & Bank Details Grid --}}
                <div class="mt-8 pt-6 border-t border-slate-800/80 grid grid-cols-1 lg:grid-cols-2 gap-6 text-xs text-slate-400 leading-relaxed">
                    <div class="space-y-1.5 bg-slate-900/40 p-4 rounded-2xl border border-slate-800/60">
                        <h5 class="font-bold text-white text-xs uppercase tracking-wide">Юридическая информация</h5>
                        <p><strong>ООО «Эдусфера»</strong> · УНП 192854899 · Зарегистрировано Минским горисполкомом 04.05.2026 г.</p>
                        <p><strong>Юридический адрес:</strong> 220100, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29 (Страна нахождения: Республика Беларусь).</p>
                        <p><strong>Тел:</strong> +375 (29) 519-08-21 · <strong>Email:</strong> edusferaby@gmail.com · <strong>Режим работы:</strong> Пн-Пт 09:00 – 18:00</p>
                        <p class="text-slate-500 text-[11px]">Деятельность не подлежит лицензированию в соответствии с законодательством Республики Беларусь.</p>
                    </div>

                    <div class="space-y-1.5 bg-slate-900/40 p-4 rounded-2xl border border-slate-800/60">
                        <h5 class="font-bold text-white text-xs uppercase tracking-wide">Платёжный эквайринг и безопасность</h5>
                        <p><strong>Интернет-эквайринг:</strong> ЗАО «Альфа-Банк» · Защита платежей по стандарту PCI DSS v4.0</p>
                        <p><strong>Поддерживаемые карты:</strong> VISA, Visa Secure, MasterCard, MasterCard ID Check, БЕЛКАРТ, Белкарт ИнтернетПароль, Apple Pay, Samsung Pay.</p>
                        <p class="text-slate-500 text-[11px]">Безопасность передачи данных обеспечивается шифрованием TLS/SSL (256-bit) и технологиями 3D-Secure 2.0.</p>
                    </div>
                </div>

            </div>

            {{-- Bottom Bar: Copyright & Platform Info --}}
            <div class="px-6 sm:px-12 py-5 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-400">
                <p class="text-slate-400 font-medium text-center md:text-left">
                    © {{ date('Y') }} ООО «Эдусфера». Все права защищены.
                </p>

                <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-slate-400 font-medium">
                    <span>Минск, Беларусь</span>
                    <div class="w-[1px] h-3.5 bg-slate-700 hidden sm:block"></div>
                    <a href="{{ route('legal.offer') }}" class="hover:text-white transition-colors">
                        Пользовательское соглашение
                    </a>
                    <div class="w-[1px] h-3.5 bg-slate-700 hidden sm:block"></div>
                    <span class="inline-flex items-center gap-1 text-[#C6FF33]">
                        ⚡ Платформа v2.0
                    </span>
                </div>
            </div>

        </div>
    </div>
</footer>
