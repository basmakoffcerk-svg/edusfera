@php
    $isDark = ($theme ?? null) === 'dark';
@endphp

<footer class="site-footer {{ $isDark ? 'site-footer--dark' : 'site-footer--light' }}">
    <div class="site-footer__container">
        {{-- Main Navigation Grid --}}
        <div class="site-footer__grid">
            
            {{-- Column 1: Brand & Identity --}}
            <div class="site-footer__col-brand">
                <a href="{{ route('home') }}" class="site-footer__brand-link">
                    <div class="site-footer__brand-icon">
                        <svg width="18" height="18" viewBox="0 0 64 64">
                            <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                            <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                        </svg>
                    </div>
                    <span class="site-footer__brand-name">EDUSFERA</span>
                </a>
                
                <p class="site-footer__brand-desc">
                    Белорусская образовательная платформа для репетиторов и подготовки к ЦТ/ЦЭ. Интерактивный класс, автоматические чеки для самозанятых и безопасная оплата с 0% комиссии на уроки.
                </p>
            </div>

            {{-- Column 2: Платформа --}}
            <div class="site-footer__col">
                <h4 class="site-footer__col-title">Платформа</h4>
                <ul class="site-footer__links">
                    <li><a href="{{ route('about') }}" class="site-footer__about-link font-semibold">О компании</a></li>
                    <li><a href="{{ route('tutors.index') }}">Каталог репетиторов</a></li>
                    <li><a href="{{ route('for-tutors') }}">Преподавателям</a></li>
                    <li>
                        <a href="{{ route('diagnostic.show') }}" class="site-footer__badge-link">
                            <span>ИИ-Диагностика</span>
                            <span class="site-footer__tag">ЦЭ/ЦТ</span>
                        </a>
                    </li>
                    <li><a href="{{ route('news.index') }}">Новости и статьи</a></li>
                </ul>
            </div>

            {{-- Column 3: Документы --}}
            <div class="site-footer__col">
                <h4 class="site-footer__col-title">Документы</h4>
                <ul class="site-footer__links">
                    <li><a href="{{ route('legal.offer') }}">Публичная оферта и тарифы</a></li>
                    <li><a href="{{ route('legal.privacy') }}">Конфиденциальность (99-З)</a></li>
                    <li><a href="{{ route('legal.payment-security') }}">Безопасность платежей</a></li>
                    <li><a href="{{ route('legal.refund') }}">Правила возврата и отмены</a></li>
                </ul>
            </div>

            {{-- Column 4: Связь и поддержка --}}
            <div class="site-footer__col">
                <h4 class="site-footer__col-title">Связь и поддержка</h4>
                <ul class="site-footer__links">
                    <li>
                        <a href="tel:+375295190821" class="site-footer__phone">
                            +375 (29) 519-08-21
                        </a>
                    </li>
                    <li>
                        <a href="mailto:edusferaby@gmail.com">
                            edusferaby@gmail.com
                        </a>
                    </li>
                    <li class="site-footer__hours">
                        Пн–Пт 09:00 – 18:00 (Минск)
                    </li>
                    <li>
                        <a href="{{ route('contacts') }}" class="site-footer__requisites-link">
                            Контакты и реквизиты →
                        </a>
                    </li>
                </ul>
            </div>

        </div>

        {{-- Payments Row: Large prominent payment badges --}}
        <div class="site-footer__payments-section">
            <p class="site-footer__payments-title">
                Принимаем к онлайн-оплате через интернет-эквайринг ЗАО «Альфа-Банк»
            </p>
            <div class="site-footer__payments-grid">
                <div class="site-footer__pay-badge" title="ЗАО «Альфа-Банк»">
                    <img src="/logo/alfa-bank.svg" alt="ЗАО «Альфа-Банк»" class="site-footer__pay-img site-footer__pay-img--alfa">
                </div>
                <div class="site-footer__pay-badge" title="БЕЛКАРТ">
                    <img src="/logo/belkart.png" alt="БЕЛКАРТ" class="site-footer__pay-img site-footer__pay-img--belkart">
                </div>
                <div class="site-footer__pay-badge" title="Белкарт ИнтернетПароль">
                    <img src="/logo/belkart-internetparol.svg" alt="Белкарт ИнтернетПароль" class="site-footer__pay-img site-footer__pay-img--belkart-ip">
                </div>
                <div class="site-footer__pay-badge" title="VISA">
                    <img src="/logo/visa.svg" alt="VISA" class="site-footer__pay-img site-footer__pay-img--visa">
                </div>
                <div class="site-footer__pay-badge" title="Visa Secure">
                    <img src="/logo/visa-secure.svg" alt="Visa Secure" class="site-footer__pay-img site-footer__pay-img--visa-sec">
                </div>
                <div class="site-footer__pay-badge" title="MasterCard">
                    <img src="/logo/mastercard.svg" alt="MasterCard" class="site-footer__pay-img site-footer__pay-img--mc">
                </div>
                <div class="site-footer__pay-badge" title="Mastercard Identity Check">
                    <img src="/logo/mastercard-id-check.svg" alt="Mastercard Identity Check" class="site-footer__pay-img site-footer__pay-img--mc-id">
                </div>
                <div class="site-footer__pay-badge" title="Apple Pay">
                    <img src="/logo/apple-pay.svg" alt="Apple Pay" class="site-footer__pay-img site-footer__pay-img--apple">
                </div>
                <div class="site-footer__pay-badge" title="Samsung Pay">
                    <img src="/logo/samsung-pay.svg" alt="Samsung Pay" class="site-footer__pay-img site-footer__pay-img--samsung">
                </div>
            </div>
        </div>

        {{-- Legal & Compliance text --}}
        <div class="site-footer__legal-section">
            <p>
                <strong>ООО «Эдусфера»</strong> · УНП 192854899 · Зарегистрировано Минским горисполкомом 04.05.2026 г. Юридический адрес: 220100, Республика Беларусь, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29.
            </p>
            <p>
                Деятельность не подлежит лицензированию в соответствии с законодательством Республики Беларусь. Передача данных защищена 256-битным шифрованием TLS/SSL и технологиями 3D-Secure 2.0.
            </p>
        </div>

        {{-- Bottom Copyright Line --}}
        <div class="site-footer__bottom-bar">
            <p class="site-footer__copyright">
                © {{ date('Y') }} ООО «Эдусфера». Все права защищены.
            </p>
            <div class="site-footer__bottom-links">
                <span>Минск, Беларусь</span>
                <span class="site-footer__dot-sep">·</span>
                <a href="{{ route('legal.offer') }}">Пользовательское соглашение</a>
                <span class="site-footer__dot-sep">·</span>
                <a href="{{ route('legal.privacy') }}">Конфиденциальность</a>
                <span class="site-footer__dot-sep">·</span>
                <a href="{{ route('legal.payment-security') }}">Безопасность платежей</a>
            </div>
        </div>
    </div>
</footer>

<style>
/* ========================================================
   ENCAPSULATED SITE FOOTER (Apple Minimalist & Scoped)
   Guaranteed immunity against global CSS resets & resets
   ======================================================== */
.site-footer {
    width: 100%;
    font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    box-sizing: border-box;
}

/* Light Theme (Default) */
.site-footer--light {
    background-color: #f5f5f7;
    color: #6e6e73;
    border-top: 1px solid #d2d2d7;
}
.site-footer--light a {
    color: #6e6e73;
    text-decoration: none;
    transition: color 0.15s ease;
}
.site-footer--light a:hover {
    color: #1d1d1f;
}
.site-footer--light .site-footer__col-title {
    color: #1d1d1f;
}
.site-footer--light .site-footer__brand-name {
    color: #1d1d1f;
}
.site-footer--light .site-footer__phone {
    color: #1d1d1f;
}
.site-footer--light .site-footer__phone:hover {
    color: #7D39EB;
}
.site-footer--light .site-footer__requisites-link {
    color: #7D39EB;
}
.site-footer--light .site-footer__requisites-link:hover {
    color: #5B21B6;
}
.site-footer--light .site-footer__pay-badge {
    background: #ffffff;
    border: 1px solid #e5e5ea;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.site-footer--light .site-footer__pay-badge:hover {
    border-color: #c7c7cc;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}
.site-footer--light .site-footer__tag {
    background: #ede9fe;
    color: #6d28d9;
}
.site-footer--light .site-footer__payments-section,
.site-footer--light .site-footer__legal-section,
.site-footer--light .site-footer__bottom-bar {
    border-top: 1px solid #e5e5ea;
}
.site-footer--light .site-footer__dot-sep {
    color: #d2d2d7;
}

/* Dark Theme */
.site-footer--dark {
    background-color: #000000;
    color: #86868b;
    border-top: 1px solid rgba(255,255,255,0.08);
}
.site-footer--dark a {
    color: #9ca3af;
    text-decoration: none;
    transition: color 0.15s ease;
}
.site-footer--dark a:hover {
    color: #ffffff;
}
.site-footer--dark .site-footer__col-title {
    color: #ffffff;
}
.site-footer--dark .site-footer__brand-name {
    color: #ffffff;
}
.site-footer--dark .site-footer__brand-desc {
    color: #9ca3af;
}
.site-footer--dark .site-footer__phone {
    color: #ffffff;
}
.site-footer--dark .site-footer__phone:hover {
    color: #C6FF33;
}
.site-footer--dark .site-footer__requisites-link {
    color: #a78bfa;
}
.site-footer--dark .site-footer__requisites-link:hover {
    color: #c4b5fd;
}
.site-footer--dark .site-footer__pay-badge {
    background: #ffffff;
    border: 1px solid rgba(255,255,255,0.15);
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}
.site-footer--dark .site-footer__pay-badge:hover {
    border-color: #C6FF33;
    transform: translateY(-1px);
}
.site-footer--dark .site-footer__tag {
    background: rgba(125,57,235,0.25);
    color: #c4b5fd;
}
.site-footer--dark .site-footer__payments-section,
.site-footer--dark .site-footer__legal-section,
.site-footer--dark .site-footer__bottom-bar {
    border-top: 1px solid rgba(255,255,255,0.08);
}
.site-footer--dark .site-footer__dot-sep {
    color: rgba(255,255,255,0.2);
}

/* Layout Core */
.site-footer__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 3.5rem 1.5rem 3.5rem;
    box-sizing: border-box;
}

.site-footer__grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1.25fr 1.25fr;
    gap: 2.5rem;
    align-items: start;
    box-sizing: border-box;
}

.site-footer__col-brand {
    box-sizing: border-box;
}

.site-footer__brand-link {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    text-decoration: none;
    margin-bottom: 0.85rem;
}

.site-footer__brand-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: linear-gradient(135deg, #7D39EB, #5B21B6);
    border: 1px solid rgba(198,255,51,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.site-footer__brand-name {
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    text-transform: uppercase;
}

.site-footer__brand-desc {
    font-size: 13px;
    line-height: 1.65;
    max-width: 360px;
    margin: 0 0 1rem 0;
}

.site-footer__col {
    box-sizing: border-box;
}

.site-footer__col-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0 0 1rem 0;
}

.site-footer__links {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    font-size: 13px;
    line-height: 1.4;
}

.site-footer__badge-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.site-footer__tag {
    font-size: 9px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    letter-spacing: 0.03em;
}

.site-footer__phone {
    font-weight: 700;
    font-size: 13.5px;
}

.site-footer__hours {
    font-size: 12px;
    color: #86868b;
    padding-top: 2px;
}

.site-footer__requisites-link {
    font-size: 12px;
    font-weight: 600;
}

/* Payments Section */
.site-footer__payments-section {
    margin-top: 2.75rem;
    padding-top: 2rem;
    box-sizing: border-box;
}

.site-footer__payments-title {
    font-size: 11.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin: 0 0 1.15rem 0;
}

.site-footer__payments-grid {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    box-sizing: border-box;
}

.site-footer__pay-badge {
    height: 48px;
    padding: 0 16px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    transition: all 0.2s ease;
    cursor: default;
}

.site-footer__pay-img {
    display: block;
    object-fit: contain;
}
.site-footer__pay-img--alfa { height: 28px; max-width: 125px; }
.site-footer__pay-img--belkart { height: 28px; max-width: 100px; }
.site-footer__pay-img--belkart-ip { height: 28px; max-width: 110px; }
.site-footer__pay-img--visa { height: 22px; max-width: 75px; }
.site-footer__pay-img--visa-sec { height: 26px; max-width: 90px; }
.site-footer__pay-img--mc { height: 28px; max-width: 65px; }
.site-footer__pay-img--mc-id { height: 28px; max-width: 90px; }
.site-footer__pay-img--apple { height: 24px; max-width: 65px; }
.site-footer__pay-img--samsung { height: 24px; max-width: 85px; }

/* Legal & Compliance */
.site-footer__legal-section {
    margin-top: 2rem;
    padding-top: 1.5rem;
    font-size: 11.5px;
    line-height: 1.65;
    color: #86868b;
    box-sizing: border-box;
}
.site-footer__legal-section p {
    margin: 0 0 0.5rem 0;
}
.site-footer__legal-section p:last-child {
    margin-bottom: 0;
}

/* Bottom Bar */
.site-footer__bottom-bar {
    margin-top: 1.5rem;
    padding-top: 1.25rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    font-size: 12px;
    color: #86868b;
    box-sizing: border-box;
}

.site-footer__copyright {
    margin: 0;
}

.site-footer__bottom-links {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}

/* Responsive adjustments */
@media (max-width: 960px) {
    .site-footer__grid {
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    .site-footer__col-brand {
        grid-column: 1 / -1;
    }
}

@media (max-width: 640px) {
    .site-footer__container {
        padding: 2.5rem 1rem 6rem; /* Extra bottom padding for mobile sticky bar */
    }
    .site-footer__grid {
        grid-template-columns: 1fr;
        gap: 1.75rem;
    }
    .site-footer__bottom-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .site-footer__payments-grid {
        gap: 8px;
    }
    .site-footer__pay-badge {
        height: 42px;
        padding: 0 12px;
    }
}
</style>
