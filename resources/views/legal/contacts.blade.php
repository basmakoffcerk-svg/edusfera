@extends('legal.layout')

@section('title', 'Контакты и поддержка | Edusfera')
@section('heading', 'Контакты и реквизиты')
@section('updated_at', '24 июня 2026')

@section('content')
    <p class="text-base leading-relaxed text-gray-700 mb-8">
        Официальные контакты ООО «Эдусфера», служба поддержки пользователей, реквизиты компании и режим работы.
    </p>

    {{-- Contact Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-10 not-prose">
        <div class="p-6 bg-violet-50/60 border border-violet-100 rounded-2xl">
            <div class="w-10 h-10 rounded-xl bg-violet-600 text-white flex items-center justify-center font-bold mb-3 text-lg">
                ✉
            </div>
            <h3 class="font-bold text-gray-900 text-base mb-1">Служба поддержки</h3>
            <p class="text-xs text-gray-500 mb-3">По всем вопросам работы платформы и бронирований</p>
            <a href="mailto:edusferaby@gmail.com" class="text-violet-700 font-bold hover:underline text-sm">
                edusferaby@gmail.com
            </a>
        </div>

        <div class="p-6 bg-lime-50/60 border border-lime-200/80 rounded-2xl">
            <div class="w-10 h-10 rounded-xl bg-lime-500 text-gray-900 flex items-center justify-center font-bold mb-3 text-lg">
                📞
            </div>
            <h3 class="font-bold text-gray-900 text-base mb-1">Горячая линия</h3>
            <p class="text-xs text-gray-500 mb-3">Пн-Пт 09:00 – 18:00 (Сб-Вс: выходной)</p>
            <a href="tel:+375295190821" class="text-gray-900 font-bold hover:underline text-sm">
                +375 (29) 519-08-21
            </a>
        </div>
    </div>

    <h2>🏢 Юридическая информация</h2>
    <ul>
        <li><strong>Наименование:</strong> Общество с ограниченной ответственностью «Эдусфера» (ООО «Эдусфера»)</li>
        <li><strong>Государственная регистрация:</strong> Зарегистрировано Минским горисполкомом 25.10.2023 г.</li>
        <li><strong>УНП:</strong> 192854899</li>
        <li><strong>Юридический адрес:</strong> Республика Беларусь, 220100, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29</li>
    </ul>

    <h2>🏦 Банковские реквизиты</h2>
    <ul>
        <li><strong>Расчетный счет (IBAN):</strong> BY64UNBS30121685100000001933</li>
        <li><strong>Обслуживающий банк:</strong> ЗАО «БСБ Банк»</li>
        <li><strong>БИК банка:</strong> UNBSBY2X</li>
        <li><strong>Валюта расчетов:</strong> Белорусский рубль (BYN)</li>
    </ul>

    <h2>❓ По каким вопросам обращаться</h2>
    <ul>
        <li>Вопросы оплаты занятий, подтверждения чеков и работы ЕРИП;</li>
        <li>Запросы на возврат или перенос запланированных уроков;</li>
        <li>Вопросы по верификации репетиторов в государственном реестре;</li>
        <li>Технические сложности при работе во Встроенном классе.</li>
    </ul>

    <h2>📜 Правовые документы</h2>
    <div class="my-6 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm font-semibold not-prose">
        <a href="{{ route('legal.offer') }}" class="p-3 bg-gray-50 hover:bg-violet-50 border border-gray-200 hover:border-violet-200 rounded-xl text-violet-700 transition-colors flex items-center justify-between">
            <span>Публичная оферта и тарифы</span>
            <span>→</span>
        </a>
        <a href="{{ route('legal.privacy') }}" class="p-3 bg-gray-50 hover:bg-violet-50 border border-gray-200 hover:border-violet-200 rounded-xl text-violet-700 transition-colors flex items-center justify-between">
            <span>Политика конфиденциальности (99-З)</span>
            <span>→</span>
        </a>
        <a href="{{ route('legal.payment-security') }}" class="p-3 bg-gray-50 hover:bg-violet-50 border border-gray-200 hover:border-violet-200 rounded-xl text-violet-700 transition-colors flex items-center justify-between">
            <span>Правила оплаты и безопасность</span>
            <span>→</span>
        </a>
        <a href="{{ route('legal.refund') }}" class="p-3 bg-gray-50 hover:bg-violet-50 border border-gray-200 hover:border-violet-200 rounded-xl text-violet-700 transition-colors flex items-center justify-between">
            <span>Правила возврата и отмены</span>
            <span>→</span>
        </a>
    </div>
@endsection
