<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edusfera Pro — фокус на преподавании</title>
    <meta name="description" content="Личный кабинет репетитора, легальные выплаты, заявки, расписание и уроки в одном тёмном рабочем пространстве.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; background: #030303; }
        body {
            margin: 0;
            min-height: 100vh;
            background: #030303;
            color: #f6f6f6;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        .pro-page {
            min-height: 100vh;
            overflow: hidden;
            background:
                radial-gradient(circle at 50% 42%, rgba(125, 57, 235, .18), transparent 30%),
                linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px),
                #030303;
            background-size: auto, 48px 48px, 48px 48px, auto;
        }
        .wrap { width: min(1500px, calc(100% - 48px)); margin: 0 auto; }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: "Rimma Sans", Impact, sans-serif;
            font-size: clamp(23px, 2vw, 34px);
            line-height: 1;
            letter-spacing: .01em;
            text-transform: uppercase;
        }
        .brand-pro { color: #7d39eb; }
        .brand-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #c6ff33;
            box-shadow: 0 0 18px rgba(198, 255, 51, .9);
        }
        .nav-shell {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            min-height: 78px;
            margin-top: 30px;
            padding: 0 28px;
            border: 1px solid rgba(255, 255, 255, .13);
            border-radius: 28px;
            background: rgba(18, 18, 20, .68);
            box-shadow: 0 22px 55px rgba(0, 0, 0, .44);
            backdrop-filter: blur(20px);
        }
        .nav-links { display: flex; align-items: center; justify-content: center; gap: 38px; color: rgba(255, 255, 255, .52); font-size: 18px; font-weight: 780; }
        .nav-links a:hover, .nav-links .active { color: #fff; }
        .login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
            min-height: 54px;
            padding: 0 26px;
            border: 1px solid rgba(255, 255, 255, .28);
            border-radius: 22px;
            background: rgba(255, 255, 255, .09);
            color: #fff;
            font-size: 18px;
            font-weight: 850;
        }
        h1, h2, h3, p { margin-top: 0; }
        .hero {
            min-height: calc(100vh - 108px);
            padding: clamp(72px, 8vh, 116px) 0 72px;
            text-align: center;
        }
        .kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 46px;
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, .13);
            border-radius: 999px;
            background: rgba(255, 255, 255, .07);
            color: rgba(255, 255, 255, .76);
            font-size: 14px;
            font-weight: 950;
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        .kicker::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #c6ff33;
            box-shadow: 0 0 16px rgba(198, 255, 51, .82);
        }
        .hero-title {
            max-width: 1180px;
            margin: 0 auto;
            font-family: "Rimma Sans", Impact, sans-serif;
            font-size: clamp(58px, 7.4vw, 145px);
            line-height: .95;
            letter-spacing: 0;
            text-transform: uppercase;
        }
        .gradient-word {
            display: inline-block;
            color: transparent;
            background: linear-gradient(90deg, #c6ff33 0%, #c8d7c0 46%, #7d39eb 100%);
            -webkit-background-clip: text;
            background-clip: text;
        }
        .hero-copy {
            max-width: 850px;
            margin: 42px auto 44px;
            color: rgba(255, 255, 255, .68);
            font-size: clamp(22px, 1.7vw, 31px);
            line-height: 1.52;
            font-weight: 520;
        }
        .cta-row { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 22px; }
        .btn-lime, .btn-dark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 72px;
            min-width: 300px;
            padding: 0 38px;
            border-radius: 22px;
            font-size: 21px;
            font-weight: 950;
            overflow: hidden;
            position: relative;
        }
        .btn-lime { background: #c6ff33; color: #050505; box-shadow: 0 22px 42px rgba(198, 255, 51, .18); }
        .btn-dark { border: 1px solid rgba(255, 255, 255, .14); background: rgba(255, 255, 255, .09); color: #fff; }
        .btn-dark::after {
            content: "";
            position: absolute;
            inset: 0 0 0 auto;
            width: 34%;
            background: linear-gradient(45deg, transparent 0 42%, rgba(255,255,255,.18) 42% 100%);
        }
        .dashboard-card {
            width: min(980px, 100%);
            margin: 86px auto 0;
            padding: 38px;
            border: 1px solid rgba(125, 57, 235, .32);
            border-radius: 28px;
            background:
                radial-gradient(circle at 90% 0%, rgba(125, 57, 235, .25), transparent 36%),
                linear-gradient(135deg, rgba(198, 255, 51, .08), rgba(18, 18, 24, .92) 32%, rgba(22, 12, 46, .9));
            box-shadow: 0 32px 80px rgba(0, 0, 0, .45);
            text-align: left;
        }
        .dash-head { display: flex; align-items: center; justify-content: space-between; gap: 22px; margin-bottom: 30px; }
        .avatar {
            display: inline-grid;
            place-items: center;
            width: 54px;
            height: 54px;
            border-radius: 999px;
            background: #303846;
            color: white;
            font-size: 24px;
            font-weight: 900;
            box-shadow: inset 0 0 0 2px rgba(255,255,255,.12);
        }
        .dash-title { display: flex; align-items: center; gap: 18px; }
        .dash-title h2 {
            margin: 0;
            font-family: "Rimma Sans", Impact, sans-serif;
            font-size: clamp(25px, 2.1vw, 38px);
            line-height: 1;
            text-transform: uppercase;
        }
        .status { color: #c6ff33; font-size: 13px; font-weight: 950; letter-spacing: .16em; text-transform: uppercase; }
        .bell {
            display: grid;
            place-items: center;
            width: 50px;
            height: 50px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 16px;
            background: rgba(255, 255, 255, .08);
            color: #c6ff33;
        }
        .dash-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 18px;
        }
        .metric {
            min-height: 118px;
            padding: 22px;
            border: 1px solid rgba(255, 255, 255, .11);
            border-radius: 18px;
            background: rgba(255, 255, 255, .06);
        }
        .metric-label {
            margin-bottom: 12px;
            color: #787d8e;
            font-size: 13px;
            font-weight: 950;
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        .money { color: rgba(255,255,255,.82); font: 34px/1 ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 900; }
        .request { border-color: rgba(125, 57, 235, .54); background: rgba(125, 57, 235, .12); }
        .request strong, .lesson strong { display: block; color: rgba(255,255,255,.82); font-size: 18px; line-height: 1.35; }
        .mini-btn { float: right; margin-top: -2px; padding: 9px 18px; border-radius: 14px; background: #7d39eb; color: white; font-weight: 900; }
        .lesson-line { display: flex; align-items: center; gap: 14px; }
        .lesson-dot { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 999px; background: rgba(255,255,255,.08); color: rgba(255,255,255,.7); font-weight: 950; }
        .section { padding: 116px 0; }
        .split {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(360px, .85fr);
            gap: 70px;
            align-items: start;
        }
        .section-title {
            max-width: 900px;
            margin: 0;
            font-family: "Rimma Sans", Impact, sans-serif;
            font-size: clamp(52px, 5.4vw, 104px);
            line-height: .98;
            text-transform: uppercase;
        }
        .section-copy {
            margin: 36px 0 0;
            color: rgba(255,255,255,.68);
            font-size: clamp(21px, 1.6vw, 28px);
            line-height: 1.5;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: 1.45fr .85fr;
            gap: 28px;
            margin-top: 70px;
        }
        .feature-card {
            min-height: 440px;
            padding: 44px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 28px;
            background: #121214;
            overflow: hidden;
        }
        .feature-card.large {
            background:
                radial-gradient(circle at 90% 20%, rgba(125,57,235,.18), transparent 32%),
                #111116;
        }
        .feature-icon {
            display: grid;
            place-items: center;
            width: 58px;
            height: 58px;
            margin-bottom: 46px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 16px;
            background: rgba(255,255,255,.06);
            color: #c6ff33;
            font-size: 30px;
        }
        .feature-card h3 { margin: 0 0 22px; font-size: clamp(26px, 2.2vw, 38px); line-height: 1.14; }
        .feature-card p { margin: 0; color: rgba(255,255,255,.68); font-size: 21px; line-height: 1.55; }
        .balance-box {
            margin-top: 44px;
            padding: 26px;
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 18px;
            background: rgba(0,0,0,.28);
        }
        .balance-box span { display: block; color: #787d8e; font-size: 13px; font-weight: 950; letter-spacing: .13em; text-transform: uppercase; }
        .balance-box b { display: block; margin-top: 8px; color: #c6ff33; font: 42px/1 ui-monospace, SFMono-Regular, Menlo, monospace; }
        .schedule { display: grid; gap: 12px; margin-top: 34px; }
        .time-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 58px;
            padding: 0 18px;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 13px;
            background: rgba(255,255,255,.06);
            font-weight: 900;
        }
        .time-row.free { border-color: rgba(198,255,51,.35); background: rgba(198,255,51,.11); color: #c6ff33; }
        .badge { padding: 8px 12px; border-radius: 10px; background: #7d39eb; color: white; font-size: 13px; text-transform: uppercase; }
        .time-row.free .badge { background: transparent; color: #c6ff33; }
        .footer {
            padding: 48px 0 60px;
            border-top: 1px solid rgba(255,255,255,.08);
            color: rgba(255,255,255,.48);
        }
        .footer-inner { display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        .footer-links { display: flex; gap: 22px; flex-wrap: wrap; }
        .footer a { color: inherit; font-weight: 800; }
        @media (max-width: 1080px) {
            .dash-grid, .split, .feature-grid { grid-template-columns: 1fr; }
            .dashboard-card { margin-top: 60px; }
        }
        @media (max-width: 740px) {
            .wrap { width: min(100% - 28px, 1500px); }
            .nav-shell { margin-top: 14px; padding: 14px; border-radius: 22px; min-height: auto; }
            .nav-links { order: 3; width: 100%; justify-content: space-between; gap: 10px; font-size: 14px; }
            .login { min-width: auto; min-height: 44px; padding: 0 18px; font-size: 15px; border-radius: 16px; }
            .hero { padding-top: 60px; }
            .hero-title { font-size: clamp(46px, 15vw, 78px); }
            .btn-lime, .btn-dark { width: 100%; min-width: 0; min-height: 60px; font-size: 18px; }
            .dashboard-card, .feature-card { padding: 24px; }
            .dash-head, .dash-title { align-items: flex-start; }
            .section { padding: 74px 0; }
        }
    </style>
</head>
<body>
    <div class="pro-page">
        <header class="wrap nav-shell">
            <a class="brand" href="{{ route('home') }}">Edusfera<span class="brand-pro">Pro</span><span class="brand-dot"></span></a>
            <nav class="nav-links" aria-label="Основная навигация">
                <a href="{{ route('home') }}">Главная</a>
                <a href="{{ route('tutors.index') }}">Каталог</a>
                <a class="active" href="{{ route('for-tutors') }}">Преподавателям</a>
            </nav>
            @auth
                <a class="login" href="/admin">Кабинет</a>
            @else
                <a class="login" href="/admin/login?redirect_to={{ urlencode(url()->full()) }}">Войти</a>
            @endauth
        </header>

        <section class="wrap hero">
            <div class="kicker">Инструмент для профи</div>
            <h1 class="hero-title">Фокус на <span class="gradient-word">преподавании</span> а не на чеках</h1>
            <p class="hero-copy">Единый дашборд. Легальный доход без открытия ИП. Мы берём на себя комиссии банков, налоги и организацию оплат.</p>
            <div class="cta-row">
                <a class="btn-lime" href="/admin/register?redirect_to={{ urlencode(url()->full()) }}">Создать анкету профи</a>
                <a class="btn-dark" href="#dashboard">Изучить дашборд</a>
            </div>

            <div id="dashboard" class="dashboard-card" aria-label="Пример кабинета репетитора">
                <div class="dash-head">
                    <div class="dash-title">
                        <span class="avatar">C</span>
                        <div>
                            <h2>Здравствуйте, Сергей!</h2>
                            <div class="status">Статус: активен</div>
                        </div>
                    </div>
                    <div class="bell">
                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            <circle cx="18" cy="5" r="3" fill="#c6ff33" stroke="#c6ff33"></circle>
                        </svg>
                    </div>
                </div>
                <div class="dash-grid">
                    <div class="metric">
                        <div class="metric-label">Баланс к выводу</div>
                        <div class="money">1 240.00 Б</div>
                    </div>
                    <div class="metric request">
                        <div class="metric-label" style="color:#9b67ff;">Новая заявка</div>
                        <a class="mini-btn" href="/admin/login?redirect_to={{ urlencode(url()->full()) }}">Принять</a>
                        <strong>Английский · 10 класс</strong>
                    </div>
                    <div class="metric lesson">
                        <div class="metric-label">Ближайший урок <span style="color:#c6ff33;">через 15 мин</span></div>
                        <div class="lesson-line"><span class="lesson-dot">И</span><strong>Илья В.</strong></div>
                    </div>
                </div>
            </div>
        </section>

        <main>
            <section class="wrap section">
                <div class="split">
                    <h2 class="section-title">Ваш личный кабинет. Ничего лишнего.</h2>
                    <p class="section-copy">Мы создали пространство, где автоматизирована вся рутина — от расписания до вывода средств на карту.</p>
                </div>

                <div class="feature-grid">
                    <article class="feature-card large">
                        <div class="feature-icon">Б</div>
                        <h3>Легальные выплаты мгновенно</h3>
                        <p>Родители оплачивают 100% стоимости урока картой на платформе. Доход зачисляется на ваш счёт в Edusfera. Налоги и ИП мы берём на себя. Вы получаете чистую прибыль на банковскую карту.</p>
                        <div class="balance-box">
                            <span>Доступно к выводу</span>
                            <b>1 240.00 Б</b>
                        </div>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <path d="M16 2v4"></path>
                                <path d="M8 2v4"></path>
                                <path d="M3 10h18"></path>
                            </svg>
                        </div>
                        <h3>Умное расписание</h3>
                        <p>Родители видят ваши слоты и бронируют сами. Никаких переписок.</p>
                        <div class="schedule">
                            <div class="time-row"><span>18:00</span><span class="badge">Занято</span></div>
                            <div class="time-row free"><span>19:30</span><span class="badge">Свободно</span></div>
                        </div>
                    </article>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="wrap footer-inner">
                <span class="brand" style="font-size:24px;">Edusfera<span class="brand-pro">Pro</span><span class="brand-dot"></span></span>
                <div class="footer-links">
                    <a href="{{ route('legal.offer') }}">Оферта</a>
                    <a href="{{ route('legal.privacy') }}">Политика</a>
                    <a href="{{ route('contacts') }}">Контакты</a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
