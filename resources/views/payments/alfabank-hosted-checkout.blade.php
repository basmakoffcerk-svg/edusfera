<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ЗАО «Альфа-Банк» — Безопасная оплата заказа {{ $orderNumber }}</title>
    <link rel="icon" type="image/svg+xml" href="/logo/alfa-bank.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #0F1117;
            color: #E2E8F0;
        }
        .font-mono-card {
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.1em;
        }
        .alfa-card-gradient {
            background: linear-gradient(135deg, #1E222D 0%, #12151D 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .alfa-btn {
            background: #EF3124;
            transition: all 0.2s ease;
        }
        .alfa-btn:hover {
            background: #D92015;
            box-shadow: 0 4px 20px rgba(239, 49, 36, 0.35);
        }
        .alfa-btn:active {
            transform: scale(0.98);
        }
        input:focus {
            outline: none;
            border-color: #EF3124;
            box-shadow: 0 0 0 3px rgba(239, 49, 36, 0.2);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between p-4 sm:p-6 lg:p-8">

    <!-- Top Bank Header -->
    <header class="w-full max-w-4xl mx-auto flex items-center justify-between pb-6 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#EF3124] flex items-center justify-center font-black text-white text-xl shadow-lg shadow-[#EF3124]/30">
                A
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-extrabold text-white text-lg tracking-tight">АЛЬФА-БАНК</span>
                    <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        UAT Sandbox
                    </span>
                </div>
                <div class="text-xs text-slate-400">Интернет-эквайринг ЗАО «Альфа-Банк» (Беларусь)</div>
            </div>
        </div>

        <div class="hidden sm:flex items-center gap-4 text-xs text-slate-400">
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>3-D Secure 2.0</span>
            </div>
            <div class="h-3 w-px bg-slate-800"></div>
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span>PCI DSS Level 1</span>
            </div>
        </div>
    </header>

    <!-- Main Payment Container -->
    <main class="w-full max-w-4xl mx-auto my-auto py-8">
        <div class="grid lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Order Summary -->
            <div class="lg:col-span-5 alfa-card-gradient rounded-2xl p-6 sm:p-7">
                <div class="flex items-center justify-between pb-5 border-b border-slate-800/80">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Продавец</div>
                        <div class="font-extrabold text-white text-base mt-0.5">Edusfera.by</div>
                        <div class="text-xs text-slate-400">ООО «Эдусфера»</div>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-800/80 flex items-center justify-center text-slate-300 font-black text-sm border border-slate-700">
                        EDU
                    </div>
                </div>

                <div class="py-5 space-y-3.5 text-sm border-b border-slate-800/80">
                    <div>
                        <div class="text-xs text-slate-400">Назначение платежа:</div>
                        <div class="font-semibold text-slate-200 mt-0.5">{{ $description }}</div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">Номер заказа:</span>
                        <span class="font-mono text-xs font-semibold text-slate-300">{{ $orderNumber }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-400">Сессия транзакции:</span>
                        <span class="font-mono text-[11px] text-slate-400 truncate max-w-[160px]">{{ $orderId }}</span>
                    </div>
                </div>

                <div class="pt-5">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">К оплате:</div>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-4xl font-black text-white font-mono-card">
                            {{ number_format($amount, 2, '.', ' ') }}
                        </span>
                        <span class="text-lg font-bold text-[#EF3124]">BYN</span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Белорусских рублей, НДС 0% (УСН)</div>
                </div>

                <!-- Payment Methods Supported -->
                <div class="mt-6 pt-5 border-t border-slate-800/80">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">Поддерживаемые карты</div>
                    <div class="flex items-center gap-3 text-slate-400 text-xs font-semibold">
                        <span class="px-2 py-1 rounded bg-slate-800/90 border border-slate-700">VISA</span>
                        <span class="px-2 py-1 rounded bg-slate-800/90 border border-slate-700">Mastercard</span>
                        <span class="px-2 py-1 rounded bg-slate-800/90 border border-slate-700 text-emerald-400">БЕЛКАРТ</span>
                        <span class="px-2 py-1 rounded bg-slate-800/90 border border-slate-700">МИР</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Card Entry Form -->
            <div class="lg:col-span-7 alfa-card-gradient rounded-2xl p-6 sm:p-8 relative overflow-hidden">

                <!-- Processing Overlay (Hidden initially) -->
                <div id="processing-overlay" class="absolute inset-0 bg-[#0F1117]/95 backdrop-blur-sm z-30 flex flex-col items-center justify-center p-6 text-center hidden">
                    <div class="w-16 h-16 relative mb-4">
                        <div class="w-16 h-16 rounded-full border-4 border-slate-800 border-t-[#EF3124] animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center font-black text-white text-lg">A</div>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-1">Обработка платежа в Альфа-Банке</h3>
                    <p class="text-xs text-slate-400 max-w-xs mb-4">Защищённое соединение с процессинговым центром. Проверка 3-D Secure...</p>
                    <div class="text-[11px] font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 rounded-full">
                        ✓ Авторизация успешна. Перенаправление...
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#EF3124]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        Данные банковской карты
                    </h2>
                    <span class="text-xs text-slate-400 font-medium">Безопасный шлюз</span>
                </div>

                <!-- Test Card Quick Helpers -->
                <div class="mb-6 p-3.5 rounded-xl bg-slate-900/90 border border-slate-800">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Быстрое заполнение для теста</span>
                        <span class="text-[10px] text-amber-400 font-medium">Тестовый стенд UAT</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="fillTestCard('visa')" class="text-xs px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition">
                            💳 Alfa Visa (Успех)
                        </button>
                        <button type="button" onclick="fillTestCard('belkart')" class="text-xs px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition">
                            🇧🇾 БЕЛКАРТ
                        </button>
                        <button type="button" onclick="fillTestCard('mastercard')" class="text-xs px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition">
                            💳 Mastercard
                        </button>
                    </div>
                </div>

                <form id="payment-form" onsubmit="handlePaymentSubmit(event)" class="space-y-5">
                    
                    <!-- Card Number -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Номер карты</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="cardNumber" 
                                required
                                maxlength="19" 
                                placeholder="4242 •••• •••• 4242" 
                                class="w-full bg-[#151922] border border-slate-700 rounded-xl px-4 py-3 text-white font-mono-card text-base placeholder:text-slate-600 focus:border-[#EF3124] transition pr-16"
                                oninput="formatCardNumber(this)"
                            >
                            <div id="cardBrandIcon" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 uppercase">
                                VISA
                            </div>
                        </div>
                    </div>

                    <!-- Expiry & CVC Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Срок действия</label>
                            <input 
                                type="text" 
                                id="cardExpiry" 
                                required
                                maxlength="5" 
                                placeholder="ММ / ГГ" 
                                class="w-full bg-[#151922] border border-slate-700 rounded-xl px-4 py-3 text-white font-mono-card text-base placeholder:text-slate-600 focus:border-[#EF3124] transition text-center"
                                oninput="formatExpiry(this)"
                            >
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-300">CVC / CVV</label>
                                <span class="text-[10px] text-slate-500">3 цифры</span>
                            </div>
                            <input 
                                type="password" 
                                id="cardCvc" 
                                required
                                maxlength="4" 
                                placeholder="•••" 
                                class="w-full bg-[#151922] border border-slate-700 rounded-xl px-4 py-3 text-white font-mono-card text-base placeholder:text-slate-600 focus:border-[#EF3124] transition text-center"
                                oninput="formatCvc(this)"
                            >
                        </div>
                    </div>

                    <!-- Cardholder Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Имя и фамилия владельца карты</label>
                        <input 
                            type="text" 
                            id="cardHolder" 
                            required
                            placeholder="IVAN IVANOV" 
                            class="w-full bg-[#151922] border border-slate-700 rounded-xl px-4 py-3 text-white text-sm uppercase placeholder:text-slate-600 focus:border-[#EF3124] transition"
                            oninput="this.value = this.value.toUpperCase()"
                        >
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-2 space-y-3">
                        <button 
                            type="submit" 
                            id="submitBtn"
                            class="alfa-btn w-full py-3.5 rounded-xl text-white font-bold text-sm tracking-wide flex items-center justify-center gap-2 shadow-lg cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Оплатить {{ number_format($amount, 2, '.', ' ') }} BYN
                        </button>

                        <a 
                            href="{{ $failUrl }}" 
                            class="block w-full py-2.5 text-center text-xs text-slate-400 hover:text-slate-200 transition"
                        >
                            Отменить и вернуться на Edusfera.by
                        </a>
                    </div>
                </form>

            </div>

        </div>
    </main>

    <!-- Bottom Footer -->
    <footer class="w-full max-w-4xl mx-auto pt-6 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
        <div>
            © 2026 ЗАО «Альфа-Банк». Лицензия НБ РБ № 13 от 10.05.2012.
        </div>
        <div class="flex items-center gap-4 text-[11px]">
            <span>Защита протоколом TLS 1.3</span>
            <span>·</span>
            <span>Шифрование AES-256</span>
            <span>·</span>
            <a href="https://www.alfabank.by" target="_blank" class="hover:underline text-slate-400">alfabank.by</a>
        </div>
    </footer>

    <script>
        function formatCardNumber(input) {
            let v = input.value.replace(/\D/g, '').substring(0, 16);
            let formatted = v.match(/.{1,4}/g)?.join(' ') || v;
            input.value = formatted;

            const brandIcon = document.getElementById('cardBrandIcon');
            if (v.startsWith('4')) {
                brandIcon.textContent = 'VISA';
                brandIcon.className = 'absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-blue-400 uppercase';
            } else if (v.startsWith('5') || v.startsWith('2')) {
                brandIcon.textContent = 'MASTERCARD';
                brandIcon.className = 'absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-amber-400 uppercase';
            } else if (v.startsWith('9')) {
                brandIcon.textContent = 'БЕЛКАРТ';
                brandIcon.className = 'absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-emerald-400 uppercase';
            } else {
                brandIcon.textContent = 'КАРТА';
                brandIcon.className = 'absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 uppercase';
            }
        }

        function formatExpiry(input) {
            let v = input.value.replace(/\D/g, '').substring(0, 4);
            if (v.length >= 2) {
                input.value = v.substring(0, 2) + ' / ' + v.substring(2);
            } else {
                input.value = v;
            }
        }

        function formatCvc(input) {
            input.value = input.value.replace(/\D/g, '').substring(0, 4);
        }

        function fillTestCard(type) {
            const num = document.getElementById('cardNumber');
            const exp = document.getElementById('cardExpiry');
            const cvc = document.getElementById('cardCvc');
            const holder = document.getElementById('cardHolder');

            if (type === 'visa') {
                num.value = '4242 4242 4242 4242';
                exp.value = '12 / 28';
                cvc.value = '123';
                holder.value = 'ALFA TESTER';
            } else if (type === 'belkart') {
                num.value = '9112 0000 0000 1234';
                exp.value = '08 / 27';
                cvc.value = '456';
                holder.value = 'TUTOR BELARUS';
            } else {
                num.value = '5555 5555 5555 4444';
                exp.value = '11 / 29';
                cvc.value = '789';
                holder.value = 'EDUSFERA PRO';
            }
            formatCardNumber(num);
        }

        function handlePaymentSubmit(e) {
            e.preventDefault();
            const overlay = document.getElementById('processing-overlay');
            overlay.classList.remove('hidden');

            const returnUrl = @json($returnUrl);
            const orderId = @json($orderId);
            const separator = returnUrl.includes('?') ? '&' : '?';
            const destination = returnUrl + separator + 'orderId=' + encodeURIComponent(orderId);

            // Realistic 3-D Secure redirect simulation
            setTimeout(() => {
                window.location.href = destination;
            }, 1200);
        }
    </script>
</body>
</html>
