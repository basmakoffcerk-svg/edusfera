<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edusfera — подготовка к ЦЭ и ЦТ по системе</title>
    <meta name="description" content="Edusfera помогает выбрать преподавателя, зафиксировать стартовый уровень и двигаться к целевому баллу по понятной траектории.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
            background: #ffffff;
            color: #050505;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        .page {
            min-height: 100vh;
            overflow: hidden;
            background:
                linear-gradient(110deg, rgba(125, 57, 235, .14) 0%, rgba(250, 250, 250, .92) 47%, rgba(198, 255, 51, .22) 100%);
        }
        .wrap { width: min(1500px, calc(100% - 48px)); margin: 0 auto; }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: "Rimma Sans", Impact, sans-serif;
            font-size: clamp(24px, 2vw, 34px);
            line-height: 1;
            letter-spacing: .01em;
            text-transform: uppercase;
        }
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
            min-height: 76px;
            margin-top: 30px;
            padding: 0 28px;
            border: 1px solid rgba(5, 5, 5, .11);
            border-radius: 28px;
            background: rgba(255, 255, 255, .82);
            box-shadow: 0 20px 55px rgba(18, 18, 30, .09);
            backdrop-filter: blur(22px);
        }
        .nav-links { display: flex; align-items: center; justify-content: center; gap: 38px; font-size: 18px; font-weight: 750; }
        .nav-links a { color: rgba(5, 5, 5, .76); }
        .nav-links a:hover { color: #050505; }
        .login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
            min-height: 54px;
            padding: 0 26px;
            border-radius: 22px;
            background: #7d39eb;
            color: white;
            font-size: 18px;
            font-weight: 850;
            box-shadow: 0 18px 38px rgba(125, 57, 235, .24);
        }
        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(360px, .9fr);
            align-items: center;
            gap: 58px;
            min-height: calc(100vh - 106px);
            padding: clamp(74px, 9vh, 138px) 0 clamp(72px, 8vh, 110px);
        }
        .kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            padding: 10px 18px;
            border: 1px solid rgba(5, 5, 5, .1);
            border-radius: 999px;
            background: rgba(255, 255, 255, .62);
            color: rgba(5, 5, 5, .68);
            font-size: 13px;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        .kicker::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #c6ff33;
        }
        h1, h2, h3 { margin: 0; }
        .hero-title {
            max-width: 930px;
            font-family: "Rimma Sans", Impact, sans-serif;
            font-size: clamp(58px, 6.3vw, 126px);
            line-height: .94;
            letter-spacing: 0;
            text-transform: uppercase;
            word-break: break-word;
        }
        .hero-copy {
            max-width: 760px;
            margin: 34px 0 44px;
            color: rgba(35, 35, 48, .68);
            font-size: clamp(22px, 1.8vw, 32px);
            line-height: 1.62;
            font-weight: 520;
        }
        .cta-row { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; }
        .btn-lime, .btn-purple, .btn-line {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 70px;
            padding: 0 38px;
            border-radius: 26px;
            font-size: 19px;
            font-weight: 900;
            border: 0;
            text-align: center;
        }
        .btn-lime { background: #c6ff33; color: #050505; box-shadow: 0 24px 44px rgba(198, 255, 51, .28); }
        .btn-purple { background: #7d39eb; color: white; box-shadow: 0 20px 38px rgba(125, 57, 235, .24); }
        .btn-line { border: 1px solid rgba(5, 5, 5, .12); background: rgba(255, 255, 255, .58); color: #050505; }
        .hero-orbit {
            position: relative;
            min-height: 620px;
            display: grid;
            place-items: center;
        }
        .hero-orbit::before {
            content: "";
            position: absolute;
            width: min(78vw, 520px);
            aspect-ratio: 1;
            border-radius: 999px;
            background: rgba(255, 255, 255, .9);
            box-shadow: 0 28px 55px rgba(18, 18, 30, .12), inset 0 0 0 1px rgba(5, 5, 5, .06);
        }
        .institution {
            position: relative;
            display: grid;
            place-items: center;
            width: 190px;
            height: 190px;
            color: #7d39eb;
        }
        .institution svg { width: 150px; height: 150px; stroke-width: 7; }
        .float-card {
            position: absolute;
            display: grid;
            gap: 4px;
            width: 220px;
            padding: 18px;
            border: 1px solid rgba(5, 5, 5, .08);
            border-radius: 20px;
            background: rgba(255, 255, 255, .86);
            box-shadow: 0 18px 35px rgba(18, 18, 30, .11);
        }
        .float-card b { font-size: 16px; }
        .float-card span { color: #747482; font-size: 14px; line-height: 1.35; }
        .float-a { top: 92px; right: 0; }
        .float-b { bottom: 86px; left: 0; }
        .section {
            padding: 108px 0;
            background: #fff;
        }
        .section-title {
            max-width: 1000px;
            margin: 0 auto 18px;
            font-family: "Rimma Sans", Impact, sans-serif;
            font-size: clamp(44px, 4.2vw, 82px);
            line-height: .96;
            text-align: center;
            text-transform: uppercase;
            word-break: break-word;
        }
        .section-lead {
            max-width: 720px;
            margin: 0 auto 70px;
            color: #6a6b78;
            font-size: 22px;
            line-height: 1.45;
            text-align: center;
        }
        .system-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
        }
        .panel {
            min-height: 410px;
            border: 1px solid #dedfe7;
            border-radius: 28px;
            background: #f7f7fa;
            padding: 34px;
            overflow: hidden;
            box-shadow: 0 14px 34px rgba(18, 18, 30, .05);
        }
        .panel h3 {
            color: #7d39eb;
            font-size: 18px;
            font-weight: 950;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .diagnostic-card {
            margin: 64px auto 0;
            width: 100%;
            max-width: 330px;
            padding: 22px;
            border-radius: 18px;
            background: white;
            box-shadow: 0 14px 24px rgba(5, 5, 5, .18);
        }
        .mini-avatar { width: 48px; height: 48px; border-radius: 999px; background: linear-gradient(135deg, #e8e8ee, #c8cbd7); }
        .pill { display: inline-flex; padding: 7px 13px; border-radius: 999px; background: #c6ff33; font-size: 13px; font-weight: 900; }
        .diagnostic-top { display: flex; justify-content: space-between; gap: 16px; align-items: start; margin-bottom: 18px; }
        .diagnostic-card strong { display: block; font-size: 18px; margin-bottom: 3px; }
        .diagnostic-card p { margin: 0; color: #747482; line-height: 1.35; }
        .track-panel {
            background: #050505;
            color: white;
            box-shadow: 0 24px 48px rgba(125, 57, 235, .16);
        }
        .track-panel h3 { color: #c6ff33; }
        .track-list { display: grid; gap: 18px; margin-top: 44px; }
        .track-item {
            padding: 18px 20px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 15px;
            background: #11151f;
            color: rgba(255, 255, 255, .68);
            font: 18px/1.45 ui-monospace, SFMono-Regular, Menlo, monospace;
            word-break: break-word;
        }
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 36px;
        }
        .day { color: #a6a8b4; text-align: center; font-size: 14px; font-weight: 800; }
        .slot { aspect-ratio: 1.1; border-radius: 10px; background: #f3f4f7; border: 1px solid #eceef4; }
        .slot.active { background: #c6ff33; box-shadow: 0 10px 20px rgba(198, 255, 51, .25); }
        .prices {
            display: grid;
            grid-template-columns: 1fr 1.05fr 1fr;
            align-items: center;
            gap: 34px;
            margin-top: 72px;
        }
        .price-card {
            min-height: 430px;
            padding: 38px;
            border: 1px solid #dedfe7;
            border-radius: 28px;
            background: #f7f7fa;
        }
        .price-card.featured {
            min-height: 540px;
            background: #c6ff33;
            border-color: #c6ff33;
            box-shadow: 0 34px 60px rgba(198, 255, 51, .26);
        }
        .price-card h3 { font-size: 27px; margin-bottom: 12px; }
        .price-card p { margin: 0 0 34px; color: #747482; font-size: 18px; line-height: 1.35; }
        .price-card.featured p { color: rgba(5, 5, 5, .76); }
        .lessons {
            margin: 0 0 34px;
            font-family: "Rimma Sans", Impact, sans-serif;
            font-size: clamp(38px, 4vw, 64px);
            line-height: 1;
            text-transform: uppercase;
        }
        .price-card ul { display: grid; gap: 16px; margin: 0 0 34px; padding: 0; list-style: none; font-size: 18px; }
        .price-card li::before { content: "✓"; margin-right: 12px; color: #6a6b78; font-weight: 900; }
        .footer {
            padding: 48px 0 60px;
            border-top: 1px solid #ececf2;
            background: white;
            color: #747482;
        }
        .footer-inner { display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        .footer a { color: inherit; font-weight: 700; }
        .footer-links { display: flex; gap: 22px; flex-wrap: wrap; }
        @media (max-width: 1050px) {
            .hero { grid-template-columns: 1fr; }
            .hero-orbit { min-height: 430px; }
            .system-grid, .prices { grid-template-columns: 1fr; }
            .price-card, .price-card.featured { min-height: auto; }
        }
        @media (max-width: 740px) {
            .wrap { width: min(100% - 28px, 1500px); }
            .nav-shell { margin-top: 14px; padding: 14px; border-radius: 22px; min-height: auto; flex-wrap: wrap; }
            .nav-links { order: 3; width: 100%; justify-content: center; gap: 15px; font-size: 14px; margin-top: 10px; }
            .login { min-width: auto; min-height: 44px; padding: 0 18px; font-size: 15px; border-radius: 16px; }
            .hero { padding-top: 40px; padding-bottom: 40px; gap: 32px; }
            .hero-title { font-size: clamp(38px, 11vw, 76px); }
            .hero-copy { font-size: 17px; margin: 24px 0 32px; }
            .btn-lime, .btn-purple, .btn-line { width: 100%; min-height: 54px; border-radius: 16px; font-size: 17px; }
            .float-card { display: none; }
            .section { padding: 50px 0; }
            .section-title { font-size: clamp(32px, 9vw, 44px); }
            .section-lead { font-size: 18px; margin-bottom: 40px; }
            .panel { min-height: auto; padding: 24px; }
            .track-item { font-size: 15px; padding: 14px; }
            .calendar { gap: 6px; margin-top: 24px; }
            .price-card { padding: 24px; }
            .price-card h3 { font-size: 24px; }
            .lessons { font-size: clamp(32px, 9vw, 38px); margin: 0 0 24px; }
            .price-card p, .price-card ul { font-size: 16px; }
            .footer { padding: 34px 0; }
            .footer-inner { flex-direction: column; align-items: flex-start; gap: 16px; }
            .footer-links { gap: 14px; flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="wrap nav-shell">
            <a class="brand" href="{{ route('home') }}">Edusfera <span class="brand-dot"></span></a>
            <nav class="nav-links" aria-label="Основная навигация">
                <a href="{{ route('tutors.index') }}">Каталог</a>
                <a href="{{ route('for-tutors') }}">Преподавателям</a>
            </nav>
            @auth
                <a class="login" href="/admin">Кабинет</a>
            @else
                <a class="login" href="/admin/login?redirect_to={{ urlencode(url()->full()) }}">Войти</a>
            @endauth
        </header>

        <section class="wrap hero">
            <div>
                <div class="kicker">Подготовка по системе</div>
                <h1 class="hero-title">Готовьтесь к ЦЭ и ЦТ по системе.</h1>
                <p class="hero-copy">Edusfera — это не просто подбор преподавателя. Сначала фиксируем стартовый уровень, затем строим траекторию и ведём к целевому баллу шаг за шагом.</p>
                <div class="cta-row">
                    <a class="btn-lime" href="{{ route('tutors.index') }}">Подобрать преподавателя</a>
                    <a class="btn-line" href="#system">Как это работает</a>
                </div>
            </div>

            <div class="hero-orbit" aria-hidden="true">
                <div class="float-card float-a">
                    <b>Цель зафиксирована</b>
                    <span>ЦЭ по физике · 82 балла</span>
                </div>
                <div class="institution">
                    <svg viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 48L60 22L102 48"></path>
                        <path d="M28 53H92"></path>
                        <path d="M35 53V91"></path>
                        <path d="M53 53V91"></path>
                        <path d="M71 53V91"></path>
                        <path d="M89 53V91"></path>
                        <path d="M24 92H96"></path>
                        <path d="M60 39H60.1"></path>
                    </svg>
                </div>
                <div class="float-card float-b">
                    <b>Следующий шаг</b>
                    <span>Разбор тестовых ловушек</span>
                </div>
            </div>
        </section>
    </div>

    <main>
        <section id="system" class="section">
            <div class="wrap">
                <h2 class="section-title">Подготовка как система. Не как хаос.</h2>
                <p class="section-lead">Диагностика, выбор преподавателя под цель и прозрачный прогресс по темам в одном рабочем контуре.</p>

                <div class="system-grid">
                    <article class="panel">
                        <h3>Карта стартовой диагностики</h3>
                        <div class="diagnostic-card">
                            <div class="diagnostic-top">
                                <div class="mini-avatar"></div>
                                <span class="pill">Стабильно</span>
                            </div>
                            <strong>Физика</strong>
                            <p>Механика и задачи · Текущий уровень 71%</p>
                        </div>
                    </article>

                    <article class="panel track-panel">
                        <h3>Траектория подготовки</h3>
                        <div class="track-list">
                            <div class="track-item">ЦЭ и темп ученика...</div>
                            <div class="track-item">Урок проведён: закрыт блок по пунктуации...</div>
                            <div class="track-item">Добавлен следующий шаг: разбор тестовых ловушек...</div>
                        </div>
                    </article>

                    <article class="panel">
                        <h3>Следующий шаг ученика</h3>
                        <div class="calendar">
                            @foreach (['M','T','W','T','F','S','S'] as $day)
                                <div class="day">{{ $day }}</div>
                            @endforeach
                            @for ($i = 1; $i <= 28; $i++)
                                <div class="slot {{ $i === 18 ? 'active' : '' }}"></div>
                            @endfor
                        </div>
                        <a class="btn-purple" href="{{ route('tutors.index') }}" style="width:100%;margin-top:28px;">Зафиксировать шаг</a>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" style="background:#fbfbfd;">
            <div class="wrap">
                <h2 class="section-title">Выберите темп подготовки</h2>
                <p class="section-lead">Платформа бесплатна для учеников. Оплата списывается только за проведённые уроки.</p>

                <div class="prices">
                    <article class="price-card">
                        <h3>Знакомство</h3>
                        <p>Быстрый старт и первичная диагностика</p>
                        <div class="lessons">4 урока</div>
                        <ul>
                            <li>Полный доступ</li>
                            <li>Базовый саппорт</li>
                        </ul>
                        <a class="btn-line" href="{{ route('tutors.index') }}" style="width:100%;">Выбрать</a>
                    </article>

                    <article class="price-card featured">
                        <span class="pill" style="float:right;background:#050505;color:white;">Интенсив</span>
                        <h3>Уверенный рост</h3>
                        <p>Системная подготовка к целевому баллу</p>
                        <div class="lessons">8 уроков</div>
                        <ul>
                            <li>Персональный трек</li>
                            <li>Приоритетная поддержка</li>
                            <li>Заморозка баланса</li>
                        </ul>
                        <a class="btn-purple" href="{{ route('tutors.index') }}" style="width:100%;">Выбрать пакет</a>
                    </article>

                    <article class="price-card">
                        <h3>Максимум</h3>
                        <p>Длинный маршрут для максимального результата</p>
                        <div class="lessons">16 уроков</div>
                        <ul>
                            <li>Все фичи Интенсива</li>
                            <li>Премиум аналитика</li>
                        </ul>
                        <a class="btn-line" href="{{ route('tutors.index') }}" style="width:100%;">Выбрать</a>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="wrap footer-inner">
            <span class="brand" style="font-size:24px;">Edusfera <span class="brand-dot"></span></span>
            <div class="footer-links">
                <a href="{{ route('legal.offer') }}">Оферта</a>
                <a href="{{ route('legal.privacy') }}">Политика</a>
                <a href="{{ route('contacts') }}">Контакты</a>
            </div>
        </div>
    </footer>
</body>
</html>
