@extends('legal.layout')

@section('title', 'Правила оплаты и безопасность платежей — Edusfera')

@section('heading', 'Правила оплаты и безопасность')

@section('updated_at', '04 сентября 2026 г.')

@section('subtitle', 'Регламент приёма онлайн-платежей через интернет-эквайринг ЗАО «Альфа-Банк», стандарты безопасности PCI DSS Level 1 и порядок прямых расчётов')

@section('content')
<style>
    .payment-badge-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 6px 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .payment-badge-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }
    .security-feature-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px 22px;
        transition: transform 0.2s, border-color 0.2s;
    }
    .security-feature-card:hover {
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    .step-card {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 16px 20px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        margin-bottom: 12px;
    }
    .step-num {
        flex-shrink: 0;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #0f172a;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.75rem;
    }
    .digital-receipt-card {
        background: #fbfbfd;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 28px 24px;
        margin: 24px auto;
        max-width: 560px;
        box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.05);
        font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .receipt-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        font-size: 0.8125rem;
        border-bottom: 1px dashed #e2e8f0;
    }
    .receipt-row:last-child {
        border-bottom: none;
    }
    .tariff-table-wrap {
        overflow-x: auto;
        margin: 20px 0;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
    }
    .tariff-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.875rem;
    }
    .tariff-table th {
        background-color: #f8fafc;
        color: #0f172a;
        font-weight: 700;
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .tariff-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .tariff-table tr:last-child td {
        border-bottom: none;
    }
    .tariff-table tr:hover td {
        background-color: #fafbfd;
    }
    .direct-pay-banner {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-left: 4px solid #10b981;
        border-radius: 14px;
        padding: 20px 24px;
        margin: 24px 0;
    }

    @media print {
        header, footer, .no-print { display: none !important; }
        body { background: white !important; }
        .prose-edusfera { max-width: 100% !important; font-size: 11pt !important; }
    }
</style>

{{-- Action Toolbar (Apple Document Bar) --}}
<div class="no-print flex flex-wrap items-center justify-between gap-4 pb-6 mb-8 border-b border-slate-100 not-prose">
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200/70 text-emerald-800 text-xs font-semibold">
        <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-600"></span>
        </span>
        Действующий регламент · Республика Беларусь
    </div>

    <div class="flex items-center gap-2">
        <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium text-xs shadow-xs transition-all cursor-pointer">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            <span>Печать</span>
        </button>
        <a href="{{ route('legal.offer') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium text-xs shadow-xs transition-all">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>Публичная оферта</span>
        </a>
    </div>
</div>

<p class="text-[15px] sm:text-base leading-relaxed text-slate-600 mb-6">
    Настоящий Регламент определяет правила приёма платежей, порядок авторизации банковских карт, архитектуру безопасности и гарантии конфиденциальности данных для пользователей платформы <strong>Edusfera</strong> (Общество с ограниченной ответственностью «Эдусфера»).
</p>

{{-- Payment Systems & Acquiring Banner (Cultured Apple Light Style) --}}
<div class="my-8 p-6 sm:p-8 bg-slate-50/80 rounded-2xl border border-slate-200/80 not-prose">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                Интернет-эквайринг ЗАО «Альфа-Банк»
            </div>
            <div class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                Поддерживаемые платёжные системы и сервисы
            </div>
        </div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-slate-200 text-[11px] font-semibold text-slate-700 self-start sm:self-center shadow-xs">
            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            PCI DSS Level 1 · 3D-Secure 2.0
        </div>
    </div>
    
    <p class="text-xs sm:text-sm text-slate-500 mb-6 leading-relaxed max-w-2xl">
        К оплате принимаются банковские карты международных платёжных систем <strong>VISA, MasterCard</strong>, национальной платёжной системы <strong>БЕЛКАРТ</strong>, а также бесконтактные цифровые кошельки <strong>Apple Pay</strong> и <strong>Samsung Pay</strong> в белорусских рублях (BYN).
    </p>

    <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
        <div class="payment-badge-card" title="ЗАО «Альфа-Банк»">
            <img src="/logo/alfa-bank.svg" alt="Альфа-Банк" class="h-6 max-w-[110px] object-contain">
        </div>
        <div class="payment-badge-card" title="Платёжная система БЕЛКАРТ">
            <img src="/logo/belkart.svg" alt="БЕЛКАРТ" class="h-6 max-w-[90px] object-contain">
        </div>
        <div class="payment-badge-card" title="Белкарт ИнтернетПароль (3D-Secure)">
            <img src="/logo/belkart-internetparol.svg" alt="Белкарт ИнтернетПароль" class="h-6 max-w-[90px] object-contain">
        </div>
        <div class="payment-badge-card" title="Платёжная система VISA">
            <img src="/logo/visa.svg" alt="VISA" class="h-5 max-w-[70px] object-contain">
        </div>
        <div class="payment-badge-card" title="Visa Secure">
            <img src="/logo/visa-secure.svg" alt="Visa Secure" class="h-6 max-w-[85px] object-contain">
        </div>
        <div class="payment-badge-card" title="Платёжная система MasterCard">
            <img src="/logo/mastercard.svg" alt="MasterCard" class="h-6 max-w-[65px] object-contain">
        </div>
        <div class="payment-badge-card" title="Mastercard Identity Check">
            <img src="/logo/mastercard-id-check.svg" alt="Mastercard Identity Check" class="h-6 max-w-[85px] object-contain">
        </div>
        <div class="payment-badge-card" title="Apple Pay">
            <img src="/logo/apple-pay.svg" alt="Apple Pay" class="h-5 max-w-[60px] object-contain">
        </div>
        <div class="payment-badge-card" title="Samsung Pay">
            <img src="/logo/samsung-pay.svg" alt="Samsung Pay" class="h-5 max-w-[75px] object-contain">
        </div>
    </div>
</div>

<h2>1. Валюта и способы расчётов</h2>
<p>Все расчёты на платформе Edusfera осуществляются в <strong>белорусских рублях (BYN)</strong> в точном соответствии с законодательством Республики Беларусь и требованиями Национального банка Республики Беларусь.</p>
<p>Оплата программной подписки (SaaS) для преподавателей производится безналичным способом:</p>
<ul>
    <li>Банковскими картами <strong>VISA, MasterCard, БЕЛКАРТ</strong> любых банков Республики Беларусь и зарубежных банков-эмитентов;</li>
    <li>Платёжными протоколами подтверждения подлинности <strong>Visa Secure, Mastercard Identity Check, Белкарт ИнтернетПароль</strong>;</li>
    <li>Цифровыми кошельками <strong>Apple Pay</strong> и <strong>Samsung Pay</strong> с привязанных банковских карт.</li>
</ul>

<h2>2. Предмет онлайн-оплаты через сайт Edusfera.by</h2>
<p>На сайте Edusfera.by осуществляется приём оплаты <strong>исключительно за программные услуги облачной SaaS-подписки</strong> для независимых преподавателей и репетиторов.</p>
<p>В состав оплачиваемого доступа входят: размещение в публичном каталоге преподавателей, интерактивный виртуальный класс (WebRTC, онлайн-доска, видеосвязь), интеллектуальное расписание занятий, модуль автоматического формирования чеков для плательщиков налога на профессиональный доход (НПД) и образовательные ИИ-ассистенты.</p>

<div class="tariff-table-wrap not-prose">
    <table class="tariff-table">
        <thead>
            <tr>
                <th>Тарифный план</th>
                <th>Продолжительность</th>
                <th>Стоимость (BYN)</th>
                <th>Особенности</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold text-slate-900">Пробный период (Trial)</td>
                <td>30 календарных дней</td>
                <td class="font-bold text-emerald-600">0.00 BYN</td>
                <td>Полный доступ для всех новых преподавателей Беларуси</td>
            </tr>
            <tr>
                <td class="font-bold text-slate-900">Тариф «Basic»</td>
                <td>1 месяц / 1 год</td>
                <td class="font-semibold text-slate-900">20.00 BYN / мес</td>
                <td>Базовый каталог, расписание, до 10 активных учеников</td>
            </tr>
            <tr>
                <td class="font-bold text-slate-900">Тариф «Pro»</td>
                <td>1 месяц / 1 год</td>
                <td class="font-semibold text-slate-900">40.00 BYN / мес</td>
                <td>Приоритет в каталоге, Встроенный класс, чеки НПД, безлимит учеников</td>
            </tr>
            <tr>
                <td class="font-bold text-slate-900">Тариф «Premium»</td>
                <td>1 месяц / 1 год</td>
                <td class="font-semibold text-slate-900">60.00 BYN / мес</td>
                <td>Топ каталога, персональный менеджер, ИИ-диагностика и брендинг</td>
            </tr>
            <tr>
                <td class="font-bold text-slate-900">Статус «Основатель»</td>
                <td>Бессрочно</td>
                <td class="font-semibold text-violet-700">Фиксированные условия</td>
                <td>Закрепление тарифа навсегда для первых 50 преподавателей</td>
            </tr>
        </tbody>
    </table>
</div>

<p class="text-xs text-slate-500">
    * При единовременной оплате любого тарифа за 1 год действует скидка 20% (Basic — 192.00 BYN/год, Pro — 384.00 BYN/год, Premium — 576.00 BYN/год).
</p>

<h2>3. Пошаговый порядок онлайн-оплаты</h2>
<p>Процедура оплаты подписки полностью автоматизирована и занимает не более 2 минут:</p>

<div class="my-4 not-prose">
    <div class="step-card">
        <div class="step-num">1</div>
        <div>
            <div class="text-sm font-bold text-slate-900 mb-0.5">Выбор тарифа в Личном кабинете</div>
            <div class="text-xs text-slate-500 leading-relaxed">Преподаватель переходит в раздел «Управление тарифом», выбирает подходящий план («Basic», «Pro» или «Premium») и расчетный период (ежемесячно или ежегодно).</div>
        </div>
    </div>
    <div class="step-card">
        <div class="step-num">2</div>
        <div>
            <div class="text-sm font-bold text-slate-900 mb-0.5">Переход на защищенный платёжный шлюз</div>
            <div class="text-xs text-slate-500 leading-relaxed">Система направляет запрос на сервер банка-эквайера ЗАО «Альфа-Банк». Открывается защищённая страница с сертификатом безопасности TLS/SSL 256 бит.</div>
        </div>
    </div>
    <div class="step-card">
        <div class="step-num">3</div>
        <div>
            <div class="text-sm font-bold text-slate-900 mb-0.5">Ввод реквизитов карты</div>
            <div class="text-xs text-slate-500 leading-relaxed">Держатель карты вводит 16-значный номер карты, срок её действия (месяц/год), имя и фамилию держателя латиницей, а также 3-значный код безопасности CVC2/CVV2.</div>
        </div>
    </div>
    <div class="step-card">
        <div class="step-num">4</div>
        <div>
            <div class="text-sm font-bold text-slate-900 mb-0.5">Подтверждение 3D-Secure</div>
            <div class="text-xs text-slate-500 leading-relaxed">Банк-эмитент, выпустивший карту, запрашивает подтверждение транзакции через одноразовый код из SMS, Push-уведомление или биометрию (Touch ID / Face ID).</div>
        </div>
    </div>
    <div class="step-card">
        <div class="step-num">5</div>
        <div>
            <div class="text-sm font-bold text-slate-900 mb-0.5">Моментальная активация и электронный чек</div>
            <div class="text-xs text-slate-500 leading-relaxed">После успешного списания средств доступ к сервисам продлевается мгновенно, а на электронную почту плательщика направляется фискальная квитанция об оплате.</div>
        </div>
    </div>
</div>

<h2>4. Стандарты безопасности и конфиденциальность платёжных данных</h2>
<p>Безопасность платёжных операций обеспечивается банковским интернет-эквайрингом <strong>ЗАО «Альфа-Банк»</strong> с применением современных международных протоколов шифрования и авторизации:</p>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 my-6 not-prose">
    <div class="security-feature-card">
        <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center mb-3 text-xs font-bold">
            01
        </div>
        <div class="text-sm font-bold text-slate-900 mb-1">Сертификация PCI DSS Level 1</div>
        <div class="text-xs text-slate-500 leading-relaxed">
            Процессинговый центр ЗАО «Альфа-Банк» соответствует высшему уровню международного стандарта безопасности индустрии платёжных карт Payment Card Industry Data Security Standard.
        </div>
    </div>

    <div class="security-feature-card">
        <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center mb-3 text-xs font-bold">
            02
        </div>
        <div class="text-sm font-bold text-slate-900 mb-1">Сквозное шифрование TLS / SSL</div>
        <div class="text-xs text-slate-500 leading-relaxed">
            Передача платёжных данных осуществляется по защищённым протоколам криптографического шифрования TLS 1.3 с длиной ключа 256 бит, исключающим перехват пакетов в сети.
        </div>
    </div>

    <div class="security-feature-card">
        <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center mb-3 text-xs font-bold">
            03
        </div>
        <div class="text-sm font-bold text-slate-900 mb-1">Аутентификация 3D-Secure 2.0</div>
        <div class="text-xs text-slate-500 leading-relaxed">
            Каждая транзакция проверяется банком-эмитентом карты по протоколам Visa Secure, Mastercard Identity Check и Белкарт ИнтернетПароль с подтверждением кодом из SMS или биометрией.
        </div>
    </div>

    <div class="security-feature-card">
        <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center mb-3 text-xs font-bold">
            04
        </div>
        <div class="text-sm font-bold text-slate-900 mb-1">Zero Card Data Storage</div>
        <div class="text-xs text-slate-500 leading-relaxed">
            ООО «Эдусфера» <strong>ни при каких обстоятельствах не собирает, не видит и не хранит</strong> полные номера банковских карт и CVC/CVV коды на своих серверах. Обработка выполняется банком.
        </div>
    </div>
</div>

<h2>5. Прямые расчёты за уроки между репетитором и учеником</h2>
<div class="direct-pay-banner not-prose">
    <div class="text-sm font-bold text-slate-900 mb-1 flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        0% комиссии платформы за проведённые уроки
    </div>
    <div class="text-xs text-slate-600 leading-relaxed">
        <strong>Денег преподавателей за уроки внутри Edusfera нет:</strong> платформа является каталогом и удобным цифровым сервисом для организации занятий. Оплата за репетиторские уроки перечисляется Учеником напрямую Репетитору (на банковскую карту преподавателя, расчетный счет или через ЕРИП). Edusfera не взимает процент с уроков, не замораживает и не депонирует средства преподавателей.
    </div>
</div>

<p>
    Все платежи через интернет-эквайринг ЗАО «Альфа-Банк» на сайте Edusfera.by принимаются исключительно за фиксированную SaaS-подписку Репетиторов в пользу ООО «Эдусфера».
</p>

<h2>6. Сроки и порядок предоставления электронных услуг</h2>
<p>Все сервисы платформы предоставляются исключительно в электронном виде через веб-интерфейс:</p>
<ul>
    <li><strong>Моментальный доступ:</strong> Активация или продление тарифного плана подписки происходит <strong>автоматически в течение 1–3 минут</strong> с момента получения банком подтверждения об успешной авторизации платежа;</li>
    <li><strong>Подтверждение транзакции:</strong> На зарегистрированный email-адрес пользователя и в Личный кабинет мгновенно направляется электронное уведомление с параметрами операции и фискальным чеком.</li>
</ul>

<h2>7. Правила отмены подписки, возврата средств и гарантии</h2>
<p>Регламент управления подпиской и возврата средств соответствует требованиям статьи 399-1 Гражданского кодекса Республики Беларусь и Закона Республики Беларусь «О защите прав потребителей»:</p>
<ul>
    <li><strong>Отмена автопродления в 1 клик:</strong> Преподаватель вправе в любой момент без комиссий, штрафов и предварительных уведомлений отключить продление тарифа в Личном кабинете. Доступ к оплаченным функциям в полном объеме сохраняется до конца текущего 30-дневного расчетного периода;</li>
    <li><strong>Канал возврата средств:</strong> При возникновении установленных законодательством оснований для возврата средств, выплата производится <strong>исключительно на ту же банковскую карту</strong>, с которой был совершен платёж через интернет-эквайринг ЗАО «Альфа-Банк»;</li>
    <li><strong>Сроки зачисления:</strong> Срок поступления средств на счет карты составляет <strong>от 1 до 30 календарных дней</strong> в зависимости от внутренних правил банка, выпустившего карту плательщика;</li>
    <li><strong>Гарантия активности («30 дней без заявок — продление 0 BYN»):</strong> Если преподаватель с активным, заполненным и прошедшим модерацию профилем не получил ни одной целевой заявки от новых учеников за 30 дней оплаченного периода, следующий месяц подписки предоставляется бесплатно (за 0.00 BYN).</li>
</ul>

<h2>8. Образец электронного чека (подтверждения оплаты)</h2>
<p>После проведения онлайн-транзакции клиенту формируется электронная квитанция установленного образца:</p>

<div class="digital-receipt-card not-prose">
    <div class="text-center pb-4 mb-4 border-b border-slate-200">
        <div class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Электронная квитанция об оплате</div>
        <div class="text-sm font-bold text-slate-900 mt-1">ООО «Эдусфера» · Edusfera.by</div>
        <div class="text-[11px] text-slate-500">УНП 192854899 · Минск, ул. В. Хоружей, д. 6А, пом. 29</div>
    </div>

    <div class="space-y-1 text-slate-700">
        <div class="receipt-row">
            <span class="text-slate-500">Номер заказа:</span>
            <span class="font-bold text-slate-900">EDUSFERA-2026-98412</span>
        </div>
        <div class="receipt-row">
            <span class="text-slate-500">Дата и время:</span>
            <span>{{ date('d.m.Y H:i:s') }}</span>
        </div>
        <div class="receipt-row">
            <span class="text-slate-500">Назначение платежа:</span>
            <span class="text-right font-medium">SaaS-подписка Edusfera (Тариф Pro)</span>
        </div>
        <div class="receipt-row">
            <span class="text-slate-500">Сумма операции:</span>
            <span class="font-bold text-slate-900 text-sm">40.00 BYN</span>
        </div>
        <div class="receipt-row">
            <span class="text-slate-500">Статус авторизации:</span>
            <span class="font-bold text-emerald-600">УСПЕШНО (100%)</span>
        </div>
        <div class="receipt-row">
            <span class="text-slate-500">Банк-эквайер:</span>
            <span>ЗАО «Альфа-Банк»</span>
        </div>
        <div class="receipt-row">
            <span class="text-slate-500">Платёжная карта:</span>
            <span>•••• •••• •••• 5412</span>
        </div>
        <div class="receipt-row">
            <span class="text-slate-500">Код авторизации:</span>
            <span class="font-mono font-bold text-slate-800">AUTH-789312</span>
        </div>
        <div class="receipt-row">
            <span class="text-slate-500">Референс транзакции (RRN):</span>
            <span class="font-mono text-slate-600">624918274019</span>
        </div>
    </div>

    <div class="mt-4 pt-3 border-t border-dashed border-slate-200 text-center text-[10px] text-slate-400">
        ✓ Документ сформирован в электронном виде и имеет юридическую силу подтверждения оплаты.
    </div>
</div>

<h2>9. Реквизиты юридического лица и контакты службы поддержки</h2>
<div class="mt-4 p-6 bg-slate-50/80 rounded-2xl border border-slate-200/80 not-prose text-xs text-slate-600 leading-relaxed">
    <div class="text-sm font-bold text-slate-900 mb-3">Общество с ограниченной ответственностью «Эдусфера» (ООО «Эдусфера»)</div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-4">
        <div><strong>УНП:</strong> 192854899</div>
        <div><strong>Гос. регистрация:</strong> Минский горисполком от 04.05.2026 г.</div>
        <div class="sm:col-span-2"><strong>Юридический адрес:</strong> 220100, Республика Беларусь, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29</div>
        <div><strong>Телефон поддержки:</strong> <a href="tel:+375295190821" class="text-slate-900 font-semibold hover:underline">+375 (29) 519-08-21</a></div>
        <div><strong>Электронная почта:</strong> <a href="mailto:edusferaby@gmail.com" class="text-slate-900 font-semibold hover:underline">edusferaby@gmail.com</a></div>
        <div><strong>Режим работы:</strong> Пн–Пт: 09:00 – 18:00 (Минск)</div>
        <div><strong>Банк-эквайер:</strong> Закрытое акционерное общество «Альфа-Банк»</div>
    </div>
    <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-400">
        Деятельность по разработке программного обеспечения, предоставлению облачных сервисов по модели SaaS и информационно-консультационным услугам не подлежит лицензированию в соответствии с законодательством Республики Беларусь.
    </div>
</div>
@endsection
