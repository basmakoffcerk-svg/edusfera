@extends('legal.layout')

@section('title', 'Правила оплаты и безопасность платежей | Edusfera')
@section('heading', 'Правила оплаты и безопасность')
@section('updated_at', '24 июня 2026')

@section('content')
    <p class="text-base leading-relaxed text-gray-700 mb-6">
        Регламент приёма онлайн-платежей, гарантии безопасности, эскроу-защита расчётов и шифрование данных для пользователей платформы Edusfera (ООО «Эдусфера»).
    </p>

    <h2>💳 1. Способы оплаты и валюта расчётов</h2>
    <p>Все расчёты на образовательной платформе Edusfera осуществляются в <strong>белорусских рублях (BYN)</strong> в соответствии с законодательством Республики Беларусь.</p>
    <p>Оплатить индивидуальное занятие, пакет уроков или подписку можно следующими платёжными инструментами:</p>
    <ul>
        <li><strong>Платёжная система bePaid:</strong> Безналичная оплата банковскими картами Visa, Mastercard, БЕЛКАРТ;</li>
        <li><strong>Система «Расчет» (ЕРИП):</strong> Оплата через интернет-банкинг, мобильное приложение или кассы банков;</li>
        <li><strong>Бесконтактные сервисы:</strong> Apple Pay, Google Pay, Samsung Pay (через bePaid gateway).</li>
    </ul>

    <div class="my-8 p-6 bg-gray-50 border border-gray-100 rounded-2xl">
        <img src="{{ asset('promo/payment-logos-full.svg') }}" 
             alt="bePaid, БЕЛКАРТ, Visa, Mastercard, Samsung Pay, Google Pay" 
             class="w-full max-w-full h-auto object-contain block opacity-90">
    </div>

    <h2>🔒 2. Безопасность платежей и протокол шифрования</h2>
    <p>Безопасность проводимых платежей обеспечивается провайдером электронных платежей <strong>bePaid (ООО «ИКомЧардж»)</strong>, работающим по международному стандарту безопасности <strong>PCI DSS Level 1</strong>.</p>
    <ul>
        <li><strong>Передача данных:</strong> Все персональные данные и реквизиты карт передаются по защищенному протоколу с использованием криптографического шифрования TLS / SSL (256-bit).</li>
        <li><strong>Конфиденциальность:</strong> Данные вашей банковской карты (номер, CVC/CVV код, срок действия) вводятся на защищённой странице авторизации bePaid и <strong>не хранятся</strong> на серверах ООО «Эдусфера».</li>
        <li><strong>Технология 3D-Secure:</strong> Для подтверждения транзакции может потребоваться ввод одноразового пароля (SMS или Push-уведомление), отправленного вашим банком-эмитентом.</li>
    </ul>

    <h2>🛡️ 3. Принцип эскроу (Безопасная сделка)</h2>
    <p>При оплате уроков Edusfera использует механизм временного холдирования средств:</p>
    <ul>
        <li>Средства удерживаются на специальном счете до фактического проведения урока во Встроенном классе.</li>
        <li>Репетитор получает оплату только после того, как занятие состоялось и подтверждено сторонами.</li>
        <li>В случае отмены занятия заранее или при возникновении обоснованных претензий средства возвращаются ученику в 100% объеме.</li>
    </ul>

    <h2>📄 4. Образец электронного чека (подтверждения оплаты)</h2>
    <p>После проведения транзакции вы мгновенно получаете электронный чек на уполномоченный email. Чек имеет полную юридическую силу подтверждения оплаты.</p>

    <div class="my-6 p-6 bg-gray-900 text-white rounded-2xl font-mono text-xs leading-relaxed shadow-lg border border-gray-800">
        <div class="text-center font-bold pb-3 mb-3 border-b border-gray-800 text-lime-400">
            ООО «ЭДУСФЕРА»<br>
            УНП 192854899 · г. Минск, ул. В. Хоружей, 6А-29<br>
            ЭЛЕКТРОННЫЙ ЧЕК КЛИЕНТА
        </div>
        <div class="flex justify-between py-1"><span>Заказ №:</span><span class="text-white font-bold">EDUSFERA-2026-8942</span></div>
        <div class="flex justify-between py-1"><span>Дата и время:</span><span class="text-gray-300">23.07.2026 14:30:15</span></div>
        <div class="flex justify-between py-1"><span>Услуга:</span><span class="text-gray-300">Доступ к платформе Edusfera (Занятие с репетитором)</span></div>
        <div class="flex justify-between py-1"><span>Сумма оплаты:</span><span class="text-lime-400 font-bold">120.00 BYN</span></div>
        <div class="flex justify-between py-1"><span>Статус платежа:</span><span class="text-lime-400 font-bold">ОПЛАЧЕНО 100% (bePaid / БСБ Банк)</span></div>
        <div class="flex justify-between py-1"><span>Платёжная система:</span><span class="text-gray-300">БЕЛКАРТ / VISA / ЕРИП</span></div>
        <div class="mt-4 pt-3 border-t border-dashed border-gray-800 text-center text-[10px] text-gray-500">
            Спасибо за оплату! Чек действителен в электронной форме.
        </div>
    </div>

    <h2>❓ 5. Проблемы с оплатой</h2>
    <p>Если транзакция не проходит, проверьте лимит по карте для интернет-оплат, баланс счета или обратитесь в службу поддержки Edusfera: <a href="mailto:edusferaby@gmail.com">edusferaby@gmail.com</a> или по телефону <a href="tel:+375295190821">+375 (29) 519-08-21</a>.</p>
@endsection
