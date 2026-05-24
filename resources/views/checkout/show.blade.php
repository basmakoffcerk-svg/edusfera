<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Оплата урока — Edusfera</title>
    <meta name="description" content="Безопасная оплата урока через платформу Edusfera. Гарантия возврата и защита сделки.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --lime:#C6FF33;--lime-hover:#d8ff66;--lime-glow:rgba(198,255,51,0.35);
            --violet:#7D39EB;--violet-light:rgba(125,57,235,0.08);
            --dark:#0a0a0a;--text:#1a1a2e;--text-sec:#555770;
            --bg:#f8f9fc;--card:#fff;--border:#eaedf3;
            --radius:1rem;--radius-lg:1.5rem;
            --shadow-sm:0 1px 3px rgba(0,0,0,0.04);--shadow-md:0 4px 20px rgba(0,0,0,0.06);
            --font-display:'Space Grotesk',system-ui,sans-serif;
            --font-body:'Inter',system-ui,-apple-system,sans-serif;
        }
        html{scroll-behavior:smooth}
        body{font-family:var(--font-body);background:var(--bg);color:var(--text);line-height:1.6;-webkit-font-smoothing:antialiased}
        .container{max-width:1180px;margin:0 auto;padding:1.25rem}

        /* TOP BAR */
        .co-topbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 1rem;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);box-shadow:var(--shadow-sm)}
        .co-topbar .brand{text-decoration:none;color:var(--text)}
        .co-back{display:inline-flex;align-items:center;min-height:2.5rem;padding:0 1rem;border-radius:.6rem;border:1px solid var(--border);background:var(--card);color:var(--text);text-decoration:none;font-weight:600;font-size:.88rem;transition:border-color .2s}
        .co-back:hover{border-color:var(--violet)}

        /* LAYOUT */
        .co-layout{display:grid;grid-template-columns:1fr 380px;gap:1.25rem;margin-top:1.25rem}

        /* CARDS */
        .co-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.5rem;box-shadow:var(--shadow-sm)}
        .co-card h1,.co-card h2{font-family:var(--font-display);font-weight:800;letter-spacing:-.03em;margin:0}
        .co-card h1{font-size:clamp(1.6rem,3vw,2.2rem);line-height:1.1;margin-top:.75rem}
        .co-card h2{font-size:1.25rem}
        .co-copy{color:var(--text-sec);line-height:1.7;margin-top:.6rem;font-size:.95rem}
        .co-label{display:inline-flex;align-items:center;min-height:1.7rem;padding:0 .65rem;border-radius:999px;background:rgba(198,255,51,.15);color:#365314;font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
        .co-lock{display:inline-flex;align-items:center;gap:.35rem;min-height:1.85rem;padding:0 .7rem;border-radius:999px;background:var(--dark);color:#fff;font-size:.75rem;font-weight:800}
        .co-kicker{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}

        /* LESSON */
        .co-lesson{display:grid;grid-template-columns:5rem 1fr;gap:1rem;margin-top:1rem;padding:1rem;border-radius:var(--radius);background:#f8f9fc}
        .co-avatar{width:5rem;height:5rem;border-radius:var(--radius);overflow:hidden;background:linear-gradient(135deg,#f4f5f9,#eef3ff);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:800}
        .co-avatar img{width:100%;height:100%;object-fit:cover}
        .co-link{color:var(--violet);text-decoration:none;font-weight:700;font-size:.9rem}
        .co-link:hover{color:var(--text)}

        /* TIME ROW */
        .co-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:1rem;padding:.85rem 1rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--card)}
        .co-row-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-sec)}
        .co-row strong{font-size:.95rem;font-weight:800}
        .co-mini-chips{display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.4rem}
        .co-mini-chip{display:inline-flex;align-items:center;min-height:1.5rem;padding:0 .5rem;border-radius:999px;background:#f3f4f6;font-size:.72rem;font-weight:700;color:#374151}

        /* PACKAGES */
        .co-packages{display:grid;gap:.75rem;margin-top:1rem}
        .co-pkg-option{display:block;cursor:pointer}
        .co-pkg-option input{position:absolute;opacity:0;pointer-events:none}
        .co-pkg{display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:center;padding:1rem;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);transition:border-color .25s,box-shadow .25s,transform .15s}
        .co-pkg-option input:checked+.co-pkg{border-color:var(--lime);box-shadow:0 0 20px rgba(198,255,51,.15);transform:translateY(-1px)}
        .co-pkg-option.recommended .co-pkg{background:linear-gradient(180deg,#fff,#fcfff0)}
        .co-pkg-title{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;font-weight:800;font-size:.98rem}
        .co-pkg-badge{display:inline-flex;align-items:center;min-height:1.5rem;padding:0 .5rem;border-radius:999px;background:rgba(198,255,51,.15);color:#365314;font-size:.7rem;font-weight:700}
        .co-pkg-copy{margin-top:.25rem;color:var(--text-sec);font-size:.88rem;line-height:1.5}
        .co-pkg-price{text-align:right}
        .co-pkg-price strong{font-family:var(--font-display);font-size:1.35rem;font-weight:900}
        .co-pkg-price span{display:block;margin-top:.2rem;color:var(--text-sec);font-size:.8rem}

        /* FORM */
        .co-form{display:grid;gap:1rem}
        .co-summ-wrap{position:sticky;top:1rem;display:grid;gap:1rem}

        /* SUMMARY */
        .co-summary{padding:1.25rem;border-radius:var(--radius-lg);background:linear-gradient(180deg,#0c0c11,#16161f);color:#fff}
        .co-summary-top{display:flex;align-items:center;justify-content:space-between;gap:1rem}
        .co-summary small{display:block;color:rgba(255,255,255,.6);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .co-summary strong{font-family:var(--font-display);font-size:clamp(1.6rem,2.5vw,2rem);font-weight:900}
        .co-timer{min-width:6rem;text-align:right}
        .co-timer-value{font-family:var(--font-display);font-size:clamp(1.3rem,2vw,1.7rem);font-weight:900;font-variant-numeric:tabular-nums;letter-spacing:.02em}
        .co-timer.is-warning .co-timer-value,.co-timer.is-warning small{color:#facc15}
        .co-timer.is-danger .co-timer-value,.co-timer.is-danger small{color:#f87171}
        .co-timer.is-danger .co-timer-value{animation:co-pulse 1s ease-in-out infinite}
        @keyframes co-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.72;transform:scale(1.02)}}

        /* SAFETY */
        .co-safety{display:grid;gap:.6rem}
        .co-safety-item{display:flex;gap:.6rem;align-items:flex-start;padding:.8rem .85rem;border-radius:var(--radius);border:1px solid var(--border);background:#f8f9fc}
        .co-safety-item strong{font-size:.92rem;font-weight:800}
        .co-safety-item span{display:block;margin-top:.2rem;color:var(--text-sec);font-size:.85rem;line-height:1.5}

        /* METHODS */
        .co-methods{display:grid;gap:.6rem;margin-top:1rem}
        .co-method{display:flex;gap:.6rem;align-items:center;padding:.8rem .85rem;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);font-weight:700;font-size:.92rem}
        .co-method input{accent-color:var(--violet)}
        .co-method-copy{display:block;margin-top:.1rem;color:var(--text-sec);font-size:.78rem;font-weight:500}

        .co-checkbox{display:flex;gap:.6rem;align-items:flex-start;padding:.8rem .85rem;border:1px solid var(--border);border-radius:var(--radius);background:#f8f9fc;font-size:.88rem;line-height:1.5;color:var(--text-sec)}
        .co-checkbox input{margin-top:.15rem;accent-color:var(--violet)}

        .btn{display:inline-flex;align-items:center;justify-content:center;font-family:var(--font-body);font-weight:700;border-radius:.75rem;text-decoration:none;padding:.85rem 1.5rem;font-size:.95rem;transition:all .25s;cursor:pointer;border:none;width:100%}
        .btn-primary{background:var(--lime);color:#111;box-shadow:0 0 16px var(--lime-glow)}
        .btn-primary:hover{background:var(--lime-hover);transform:translateY(-2px);box-shadow:0 0 24px var(--lime-glow)}
        .btn-primary:focus-visible{outline:none;box-shadow:0 0 0 3px #fff,0 0 0 5px #111}

        .co-fineprint{color:var(--text-sec);font-size:.8rem;line-height:1.5;text-align:center;margin-top:.5rem}
        .co-inline-link{display:inline-flex;align-items:center;justify-content:center;margin-top:.25rem;color:var(--violet);background:transparent;border:0;padding:0;font-size:.82rem;font-weight:700;cursor:pointer}
        .co-error{padding:.75rem .85rem;border-radius:.75rem;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-weight:700;font-size:.9rem}

        /* POPUP */
        .co-popup{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(9,9,11,.55);z-index:60}
        .co-popup.is-open{display:flex}
        .co-popup-card{max-width:30rem;padding:1.25rem;border-radius:var(--radius-lg);background:#fff;box-shadow:0 30px 80px rgba(15,23,42,.2)}
        .co-popup-card h3{font-family:var(--font-display);font-size:1.3rem;font-weight:800;margin:0}
        .co-popup-card p{margin:.6rem 0 0;color:var(--text-sec);line-height:1.6;font-size:.92rem}
        .co-popup-card ul{margin:.7rem 0 0;padding-left:1rem;color:var(--text-sec);line-height:1.6;font-size:.9rem}
        .co-popup-actions{display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem}
        .co-popup-actions .btn{width:auto}

        @media(max-width:960px){
            .co-layout{grid-template-columns:1fr}
            .co-summ-wrap{position:static}
        }
        @media(max-width:640px){
            .container{padding:.85rem}
            .co-lesson,.co-pkg{grid-template-columns:1fr}
            .co-pkg-price{text-align:left}
            .co-summary-top{flex-direction:column;align-items:flex-start}
            .co-kicker,.co-row{flex-direction:column;align-items:flex-start}
            .co-timer{text-align:left}
        }
    </style>
</head>
<body>
    @php
        $tutorProfile = $lesson->tutor?->tutorProfile;
        $avatarPath = $tutorProfile?->avatar_path ? asset('storage/' . $tutorProfile->avatar_path) : null;
        $displayName = $lesson->tutor?->name ?? 'Репетитор';
        $packageCode = old('package_code', $lesson->package_code ?: 'single');
        $selectedDate = $lesson->start_time->setTimezone(config('booking.display_timezone'));
        $singlePrice = (float) $lesson->price;
        $pack4 = round($singlePrice * 4 * 0.95, 2);
        $pack8 = round($singlePrice * 8 * 0.90, 2);
        $selectedAmount = match ($packageCode) {
            'pack_4' => $pack4,
            'pack_8' => $pack8,
            default => $singlePrice,
        };
        $bynIconSvg = <<<'SVG'
<svg viewBox="0 0 360.67 446.4" width="0.81em" height="1em" style="display:inline-block;vertical-align:-0.12em" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M475.61,528.84c0-72.5-62.75-131.27-140.16-131.27H227.58V263.37H426v-49.6H178v290h-63.1v49.7H178V660.17h49.54l107.92-.07c77.36,0,140.11-58.77,140.11-131.26Zm-248-25.1V447.1c35.89,0,72.35.07,107.87.07,50,0,90.56,36.57,90.56,81.67s-40.54,81.67-90.56,81.7l-107.87,0V553.44h112.7v-49.7Z" transform="translate(-114.94 -213.77)"/></svg>
SVG;
        $singleButtonLabel = number_format($singlePrice, 2, '.', ' ') . '&nbsp;' . $bynIconSvg;
        $pack4ButtonLabel = number_format($pack4, 2, '.', ' ') . '&nbsp;' . $bynIconSvg;
        $pack8ButtonLabel = number_format($pack8, 2, '.', ' ') . '&nbsp;' . $bynIconSvg;
        $selectedButtonLabel = match ($packageCode) {
            'pack_4' => $pack4ButtonLabel,
            'pack_8' => $pack8ButtonLabel,
            default => $singleButtonLabel,
        };
        $subjectLabel = $tutorProfile?->subjects ? implode(' • ', array_slice($tutorProfile->subjects, 0, 2)) : 'Индивидуальное занятие';
        $walletBalance = (float) ($walletBalance ?? 0);
        $walletCoversSelectedAmount = $walletBalance >= $selectedAmount;
        $defaultPaymentMethod = $walletCoversSelectedAmount ? 'wallet' : 'card';
        $selectedPaymentMethod = old('payment_method', $defaultPaymentMethod);
        $useWalletBalance = old('use_wallet_balance', $walletBalance > 0 && $walletBalance < $singlePrice ? '1' : '0') === '1';
        $walletBalanceLabel = number_format($walletBalance, 2, '.', ' ') . '&nbsp;' . $bynIconSvg;
    @endphp

    <main class="container">
        <div class="co-topbar">
            <a href="{{ route('home') }}" class="brand ed-brand" style="font-size:1.6rem;">Edusfera</a>
            <a href="{{ route('tutors.show', ['tutor' => $tutorProfile, 'date' => $selectedDate->format('Y-m-d')]) }}#booking" class="co-back">← Назад к выбору времени</a>
        </div>

        <section class="co-layout">
            <section class="co-card">
                <div class="co-kicker">
                    <span class="co-label">Что покупаем</span>
                    <span class="co-lock">⏱ Время держим 15 минут</span>
                </div>
                <h1>Безопасная сделка внутри платформы</h1>
                <p class="co-copy">Вы оплачиваете занятие через платформу. Это сохраняет гарантию возврата и защищает слот.</p>

                @if(session('booking_success'))
                    <p class="co-copy" style="color:#365314;font-weight:700;">{{ session('booking_success') }}</p>
                @endif

                <div class="co-lesson">
                    <div class="co-avatar" aria-hidden="true">
                        @if($avatarPath)
                            <img src="{{ $avatarPath }}" alt="{{ $displayName }}">
                        @else
                            {{ mb_substr($displayName, 0, 1) }}
                        @endif
                    </div>
                    <div>
                        <span class="co-label">Репетитор</span>
                        <h2 style="margin-top:.4rem;">{{ $displayName }}</h2>
                        <p class="co-copy" style="margin-top:.25rem;">{{ $subjectLabel }}</p>
                    </div>
                </div>

                <div class="co-row">
                    <div>
                        <div class="co-row-label">Выбранное время</div>
                        <strong>{{ $selectedDate->translatedFormat('l, d.m.Y \\в H:i') }} (Минск)</strong>
                        <div class="co-mini-chips">
                            <span class="co-mini-chip">Оплата внутри платформы</span>
                            <span class="co-mini-chip">Контакты после оплаты</span>
                        </div>
                    </div>
                    <a href="{{ route('tutors.show', ['tutor' => $tutorProfile, 'date' => $selectedDate->format('Y-m-d')]) }}#booking" class="co-link">Изменить время</a>
                </div>

                <div class="co-packages">
                    <label class="co-pkg-option">
                        <input type="radio" name="package_code" value="single" form="checkout-form"
                               data-total-amount="{{ number_format($singlePrice, 2, '.', '') }}"
                               data-button-label="{{ $singleButtonLabel }}"
                               @checked($packageCode === 'single')>
                        <div class="co-pkg">
                            <div>
                                <div class="co-pkg-title">Стартовая сессия</div>
                                <div class="co-pkg-copy">Первый шаг: знакомство, диагностика и фиксация цели.</div>
                            </div>
                            <div class="co-pkg-price">
                                <strong>{!! $singleButtonLabel !!}</strong>
                                <span>Без скидки</span>
                            </div>
                        </div>
                    </label>
                    <label class="co-pkg-option recommended">
                        <input type="radio" name="package_code" value="pack_4" form="checkout-form"
                               data-total-amount="{{ number_format($pack4, 2, '.', '') }}"
                               data-button-label="{{ $pack4ButtonLabel }}"
                               @checked($packageCode === 'pack_4')>
                        <div class="co-pkg">
                            <div>
                                <div class="co-pkg-title">Траектория 4 занятия <span class="co-pkg-badge">🔥 Хит продаж</span></div>
                                <div class="co-pkg-copy">Диагностика, первые слабые темы и быстрый рост без длинного цикла.</div>
                            </div>
                            <div class="co-pkg-price">
                                <strong>{!! $pack4ButtonLabel !!}</strong>
                                <span>Скидка 5% · -{!! number_format(($singlePrice * 4) - $pack4, 2, '.', ' ') . '&nbsp;' . $bynIconSvg !!}</span>
                            </div>
                        </div>
                    </label>
                    <label class="co-pkg-option">
                        <input type="radio" name="package_code" value="pack_8" form="checkout-form"
                               data-total-amount="{{ number_format($pack8, 2, '.', '') }}"
                               data-button-label="{{ $pack8ButtonLabel }}"
                               @checked($packageCode === 'pack_8')>
                        <div class="co-pkg">
                            <div>
                                <div class="co-pkg-title">Траектория 8 занятий</div>
                                <div class="co-pkg-copy">Для серьёзной подготовки к ЦЭ/ЦТ с домашкой, прогрессом и контролем результата.</div>
                            </div>
                            <div class="co-pkg-price">
                                <strong>{!! $pack8ButtonLabel !!}</strong>
                                <span>Скидка 10% · -{!! number_format(($singlePrice * 8) - $pack8, 2, '.', ' ') . '&nbsp;' . $bynIconSvg !!}</span>
                            </div>
                        </div>
                    </label>
                </div>
                @error('package_code')
                    <div class="co-error" style="margin-top:.75rem;">{{ $message }}</div>
                @enderror
            </section>

            <section class="co-card">
                <form method="POST" action="{{ route('checkout.pay', $lesson) }}" class="co-form" id="checkout-form">
                    @csrf
                    <div class="co-summ-wrap">
                        <div class="co-summary">
                            <div class="co-summary-top">
                                <div>
                                    <small>Итого к оплате</small>
                                    <strong id="checkout-total">{!! $selectedButtonLabel !!}</strong>
                                </div>
                                <div class="co-timer" id="checkout-timer-wrap">
                                    <small>Резерв за вами</small>
                                    <strong id="checkout-timer" class="co-timer-value">{{ gmdate('i:s', (int)$expiresInSeconds) }}</strong>
                                </div>
                            </div>
                        </div>

                        @error('payment')
                            <div class="co-error">{{ $message }}</div>
                        @enderror

                        <div class="co-safety">
                            <div class="co-safety-item">
                                <div>🛡️</div>
                                <div>
                                    <strong>Деньги замораживаются на платформе</strong>
                                    <span>Перевод преподавателю — только после подтверждения занятия.</span>
                                </div>
                            </div>
                            <div class="co-safety-item">
                                <div>↩</div>
                                <div>
                                    <strong>Гарантия возврата 100%</strong>
                                    <span>Если занятие не состоялось — возврат без споров в переписке.</span>
                                </div>
                            </div>
                        </div>

                        <div id="wallet-one-click-box" class="co-safety-item" style="display:none;">
                            <div>⚡</div>
                            <div>
                                <strong>Оплата в 1 клик с баланса</strong>
                                <span id="wallet-one-click-copy">К оплате: {!! $selectedButtonLabel !!}. На вашем балансе: {!! $walletBalanceLabel !!}.</span>
                            </div>
                        </div>

                        <div id="wallet-partial-box" class="co-safety-item" style="display:none;">
                            <div>💳</div>
                            <div>
                                <label class="co-method" style="padding:0;border:0;background:transparent;">
                                    <input type="checkbox" name="use_wallet_balance" id="use-wallet-balance" value="1" @checked($useWalletBalance)>
                                    <span>
                                        <strong id="wallet-partial-copy">Использовать часть средств с баланса</strong>
                                        <span class="co-method-copy">Доступно: {!! $walletBalanceLabel !!}. К доплате картой/ЕРИП только остаток.</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div id="payment-methods-box">
                            <h2>Способ оплаты</h2>
                            <div class="co-methods">
                                @foreach($paymentMethods as $method => $label)
                                    <label class="co-method">
                                        <input type="radio" name="payment_method" value="{{ $method }}" @checked($selectedPaymentMethod === $method)>
                                        <span>
                                            {{ $label }}
                                            <span class="co-method-copy">
                                                @if($method === 'wallet')
                                                    Доступно: {!! number_format($walletBalance, 2, '.', ' ') . '&nbsp;' . $bynIconSvg !!}. Списываем за 1 урок, остаток сохранится.
                                                @elseif($method === 'erip') Через интернет-банк, удобно для всей Беларуси.
                                                @elseif($method === 'card') Быстрая онлайн-оплата картой.
                                                @else Мгновенный способ оплаты. @endif
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('payment_method')
                                <div class="co-error" style="margin-top:.5rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <label class="co-checkbox" id="remember-card-box">
                            <input type="checkbox" name="remember_card" value="1" @checked(old('remember_card','1') === '1')>
                            <span>Сохранить карту для быстрой оплаты следующих занятий.</span>
                        </label>

                        <button type="submit" class="btn btn-primary" id="checkout-submit">Оплатить {!! $selectedButtonLabel !!}</button>
                        <div class="co-fineprint">
                            Нажимая кнопку, вы соглашаетесь с
                            <a href="{{ route('legal.offer') }}">условиями оферты</a>
                            и
                            <a href="{{ route('legal.refund') }}">правилами возврата</a>.
                        </div>
                        <button type="button" class="co-inline-link" onclick="window.__openCheckoutPopup('safe')">Как работает безопасная сделка?</button>
                    </div>
                </form>
            </section>
        </section>
    </main>

    <div class="co-popup" id="checkout-popup">
        <div class="co-popup-card">
            <h3 id="checkout-popup-title">Оплачивая напрямую, вы теряете гарантию платформы</h3>
            <p id="checkout-popup-copy">Вне Edusfera не работают возврат, история оплат и защита сделки.</p>
            <ul id="checkout-popup-list">
                <li>Платформа фиксирует оплату и хранит историю сделки.</li>
                <li>Возврат оформляется внутри Edusfera, если урок не состоялся.</li>
                <li>Контакты открываются только после успешной оплаты.</li>
            </ul>
            <div class="co-popup-actions">
                <a href="{{ auth()->check() ? '/admin/messages' : route('contacts') }}" class="co-back">Написать в поддержку</a>
                <button type="button" class="btn btn-primary" style="width:auto;min-height:2.75rem;" onclick="window.__closeCheckoutPopup()">Продолжить оплату</button>
            </div>
        </div>
    </div>

    @php
        $walletBalanceLabel = number_format($walletBalance, 2, '.', ' ') . ' BYN';
        $expiredRedirectUrl = route('tutors.show', ['tutor' => $tutorProfile, 'date' => $selectedDate->format('Y-m-d')]) . '#booking';
    @endphp

    <script>
        (() => {
            const popup = document.getElementById('checkout-popup');
            const popupTitle = document.getElementById('checkout-popup-title');
            const popupCopy = document.getElementById('checkout-popup-copy');
            const timerWrap = document.getElementById('checkout-timer-wrap');
            const timerNode = document.getElementById('checkout-timer');
            const totalNode = document.getElementById('checkout-total');
            const submitNode = document.getElementById('checkout-submit');
            const oneClickBox = document.getElementById('wallet-one-click-box');
            const oneClickCopyNode = document.getElementById('wallet-one-click-copy');
            const partialBox = document.getElementById('wallet-partial-box');
            const partialCopyNode = document.getElementById('wallet-partial-copy');
            const useWalletCheckbox = document.getElementById('use-wallet-balance');
            const paymentMethodsBox = document.getElementById('payment-methods-box');
            const rememberCardBox = document.getElementById('remember-card-box');
            const packageNodes = document.querySelectorAll('input[name="package_code"]');
            const paymentMethodNodes = document.querySelectorAll('input[name="payment_method"]');
            const walletRadio = document.querySelector('input[name="payment_method"][value="wallet"]');
            const cardRadio = document.querySelector('input[name="payment_method"][value="card"]');
            const walletBalance = Number.parseFloat('{{ number_format($walletBalance, 2, '.', '') }}');
            const bynIconSvg = @json($bynIconSvg);
            const defaultButtonLabel = @json($selectedButtonLabel);
            const walletBalanceLabel = @json($walletBalanceLabel);
            const expiredRedirectUrl = @json($expiredRedirectUrl);
            let secondsLeft = Number.parseInt(@json((int) $expiresInSeconds), 10);
            let popupOpened = false;

            window.__closeCheckoutPopup = () => popup.classList.remove('is-open');
            window.__openCheckoutPopup = (mode = 'exit') => {
                if (mode === 'safe') {
                    popupTitle.textContent = 'Как работает безопасная сделка';
                    popupCopy.textContent = 'Edusfera держит оплату внутри платформы до подтверждения урока. Это защищает вас от перевода денег напрямую незнакомому преподавателю.';
                } else {
                    popupTitle.textContent = 'Оплачивая напрямую, вы теряете гарантию платформы';
                    popupCopy.textContent = 'Вне Edusfera не работают возврат, история оплат и защита сделки. Если остались вопросы по оплате, откройте поддержку внутри кабинета.';
                }
                popup.classList.add('is-open');
            };

            const renderTimer = () => {
                const totalSeconds = Math.max(Math.floor(secondsLeft), 0);
                const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
                const seconds = String(totalSeconds % 60).padStart(2, '0');
                timerNode.textContent = `${minutes}:${seconds}`;
                timerWrap.classList.remove('is-warning', 'is-danger');
                if (totalSeconds <= 120 && totalSeconds > 30) timerWrap.classList.add('is-warning');
                if (totalSeconds <= 30) timerWrap.classList.add('is-danger');
            };

            renderTimer();

            const formatMoneyHtml = (amount) => {
                const value = Number.parseFloat(amount || 0);
                const normalized = value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

                return `${normalized}&nbsp;${bynIconSvg}`;
            };

            const disableWalletForPackage = (selectedPackageCode) => {
                if (!walletRadio) return;

                const walletLabel = walletRadio.closest('.co-method');
                const walletAllowed = selectedPackageCode === 'single';
                walletRadio.disabled = !walletAllowed;

                if (walletLabel) {
                    walletLabel.style.opacity = walletAllowed ? '1' : '0.45';
                    walletLabel.style.pointerEvents = walletAllowed ? 'auto' : 'none';
                }

                if (!walletAllowed && walletRadio.checked && cardRadio) {
                    cardRadio.checked = true;
                }
            };

            const updateRememberCardVisibility = () => {
                const selectedMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
                rememberCardBox.style.display = selectedMethod === 'wallet' ? 'none' : 'flex';
            };

            const syncPackageSummary = () => {
                const selectedNode = document.querySelector('input[name="package_code"]:checked');
                if (!selectedNode) return;

                const selectedPackageCode = selectedNode.value;
                const selectedAmount = Number.parseFloat(selectedNode.dataset.totalAmount || '0');
                const buttonLabel = selectedNode.dataset.buttonLabel ?? defaultButtonLabel;
                const fullyCoveredByWallet = selectedPackageCode === 'single' && walletBalance >= selectedAmount && Boolean(walletRadio);
                const canUsePartialWallet = selectedPackageCode === 'single' && walletBalance > 0 && walletBalance < selectedAmount;
                const usePartialWallet = canUsePartialWallet && Boolean(useWalletCheckbox?.checked);
                const topUpAmount = Math.max(selectedAmount - walletBalance, 0);

                disableWalletForPackage(selectedPackageCode);
                totalNode.innerHTML = buttonLabel;

                if (fullyCoveredByWallet) {
                    if (walletRadio) {
                        walletRadio.checked = true;
                    }
                    if (oneClickCopyNode) {
                        oneClickCopyNode.innerHTML = `К оплате: ${buttonLabel}. На вашем балансе: ${walletBalanceLabel}.`;
                    }
                    oneClickBox.style.display = 'flex';
                    partialBox.style.display = 'none';
                    paymentMethodsBox.style.display = 'none';
                    rememberCardBox.style.display = 'none';
                    submitNode.innerHTML = `Подтвердить запись за ${buttonLabel}`;

                    return;
                }

                oneClickBox.style.display = 'none';
                paymentMethodsBox.style.display = 'block';

                if (canUsePartialWallet) {
                    partialBox.style.display = 'flex';
                    if (partialCopyNode) {
                        partialCopyNode.innerHTML = `Использовать ${formatMoneyHtml(walletBalance)} с баланса. К доплате: ${formatMoneyHtml(topUpAmount)}.`;
                    }
                } else {
                    partialBox.style.display = 'none';
                    if (useWalletCheckbox) {
                        useWalletCheckbox.checked = false;
                    }
                }

                if (usePartialWallet) {
                    if (walletRadio?.checked && cardRadio) {
                        cardRadio.checked = true;
                    }
                    submitNode.innerHTML = `Доплатить ${formatMoneyHtml(topUpAmount)}`;
                } else {
                    submitNode.innerHTML = `Оплатить ${buttonLabel}`;
                }

                updateRememberCardVisibility();
            };

            packageNodes.forEach((node) => node.addEventListener('change', syncPackageSummary));
            paymentMethodNodes.forEach((node) => node.addEventListener('change', syncPackageSummary));
            if (useWalletCheckbox) {
                useWalletCheckbox.addEventListener('change', syncPackageSummary);
            }
            syncPackageSummary();

            const interval = window.setInterval(() => {
                secondsLeft -= 1;
                renderTimer();
                if (secondsLeft <= 0) {
                    window.clearInterval(interval);
                    alert('Время вышло, слот освобожден. Выберите слот повторно.');
                    window.location.href = expiredRedirectUrl;
                }
            }, 1000);

            document.addEventListener('mouseleave', (event) => {
                if (popupOpened || secondsLeft <= 0) return;
                if (event.clientY <= 0) {
                    window.__openCheckoutPopup('exit');
                    popupOpened = true;
                }
            });
        })();
    </script>
</body>
</html>
