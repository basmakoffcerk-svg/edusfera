@php
    /**
     * Общая дизайн-система auth-страниц Edusfera (2026).
     * Включается в login/register blade через @include.
     *
     * Переменные (необязательные, переопределяются вызывающим):
     *  $asideTheme   = 'dark' | 'light'   — тема левой панели (по умолчанию 'dark')
     *  $asideBadge   = string              — текст кикера (над заголовком)
     *  $asideTitle   = string              — крупный заголовок панели
     *  $asideCopy    = string              — абзац под заголовком
     *  $cardBadge    = string|null         — бейдж над формой (для site-admin)
     *  $cardTitle    = string              — заголовок формы
     *  $cardSubtitle = string              — подзаголовок формы
     *  $switchHref   = string              — ссылка на парную страницу (login↔register)
     *  $switchLabel  = string              — текст ссылки-переключателя
     *  $formSlot     = string (HTML)       — сама форма (передаётся как {form})
     *  $footerSlot   = string (HTML)       — футер под формой
     *  $bullet       = array|null          — список буллетов для панели [[icon,text],...]
     @endphp
@php
    $asideTheme = $asideTheme ?? 'dark';
    $asideBadge = $asideBadge ?? 'Edusfera';
    $asideTitle = $asideTitle ?? '';
    $asideCopy  = $asideCopy  ?? '';
    $cardTitle    = $cardTitle    ?? '';
    $cardSubtitle = $cardSubtitle ?? '';
    $switchHref   = $switchHref   ?? '#';
    $switchLabel  = $switchLabel  ?? '';
    $formSlot     = $formSlot     ?? '';
    $footerSlot   = $footerSlot   ?? '';
    $bullet       = $bullet       ?? null;
    $isDark = $asideTheme === 'dark';
@endphp
<style>
    /* ── Сброс обёртки Filament simple-page под наш fullbleed лейаут ── */
    .fi-simple-header{display:none!important}
    .fi-simple-page{width:100%;max-width:none}
    .fi-simple-layout,.fi-simple-main-ctn{min-height:100dvh!important}
    .fi-simple-main{margin:0!important;padding:0!important;max-width:100%!important;border-radius:0!important;border:0!important;background:transparent!important;box-shadow:none!important;--tw-ring-shadow:0 0 #0000!important}

    /* ═══ EDUSFERA AUTH 2026 — дизайн-система ═══ */
    .ed-auth-layout{min-height:100dvh;padding:clamp(.5rem,1.8vh,1rem)}
    .ed-auth-shell{
        max-width:1180px;margin:0 auto;
        min-height:calc(100dvh - clamp(1rem,3.6vh,2rem));
        display:grid;grid-template-columns:minmax(0,1.05fr) minmax(320px,.95fr);
        border-radius:2rem;overflow:hidden;
        border:1px solid rgba(125,57,235,.1);
        background:#fff;
        box-shadow:0 30px 80px rgba(17,17,17,.1);
    }

    /* ── Левая панель: тёмная (default) или светлая ── */
    .ed-auth-aside{
        position:relative;padding:clamp(1.5rem,3vh,2.5rem);
        display:flex;flex-direction:column;overflow:hidden;
        color:#fff;
        background:
            radial-gradient(circle at 18% 18%,rgba(198,255,51,.18),transparent 22%),
            radial-gradient(circle at 82% 28%,rgba(255,255,255,.08),transparent 18%),
            linear-gradient(150deg,#09090b 0%,#1a0b2e 50%,#7d39eb 115%);
    }
    .ed-auth-aside.is-light{
        color:#0f1115;
        background:
            radial-gradient(circle at 22% 20%,rgba(125,57,235,.14),transparent 22%),
            radial-gradient(circle at 78% 78%,rgba(198,255,51,.2),transparent 22%),
            linear-gradient(180deg,#fbfbfe 0%,#f1ecff 100%);
    }
    /* Aurora-пятна на тёмной панели */
    .ed-auth-aside.is-dark::before,
    .ed-auth-aside.is-dark::after{
        content:'';position:absolute;border-radius:50%;filter:blur(60px);pointer-events:none;z-index:0;
    }
    .ed-auth-aside.is-dark::before{width:320px;height:320px;background:rgba(125,57,235,.4);top:-80px;right:-60px;animation:edAuthFloat1 18s ease-in-out infinite}
    .ed-auth-aside.is-dark::after{width:260px;height:260px;background:rgba(198,255,51,.16);bottom:-70px;left:-40px;animation:edAuthFloat2 22s ease-in-out infinite}
    @keyframes edAuthFloat1{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(-40px,50px) scale(1.12)}}
    @keyframes edAuthFloat2{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(30px,-40px) scale(.92)}}
    .ed-auth-aside>*{position:relative;z-index:1}

    /* Бренд */
    .ed-auth-brand{display:inline-flex;align-items:center;gap:.6rem;font-family:'Rimma Sans','Inter',sans-serif;font-size:1.6rem;font-weight:800;letter-spacing:-.04em;text-transform:uppercase}
    .ed-auth-brand .ed-diamond{width:18px;height:18px;display:inline-block;flex-shrink:0}
    .ed-auth-brand .ed-diamond path{transition:transform .7s ease}
    .ed-auth-brand:hover .ed-diamond path{transform:rotate(90deg);transform-origin:center}

    /* Кикер-бейдж */
    .ed-auth-kicker{
        display:inline-flex;align-items:center;gap:.4rem;margin-top:auto;margin-bottom:1.5rem;
        padding:.45rem .9rem;border-radius:999px;align-self:flex-start;
        font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;
    }
    .ed-auth-aside.is-dark .ed-auth-kicker{background:rgba(255,255,255,.1);color:var(--ed-lime,#C6FF33)}
    .ed-auth-aside.is-light .ed-auth-kicker{background:#efffc8;color:#101010}
    .ed-auth-kicker .ed-kd{width:6px;height:6px;border-radius:50%;background:currentColor;animation:edPulse 2s infinite}
    @keyframes edPulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}

    /* Заголовок панели */
    .ed-auth-heading{
        margin:0;max-width:11ch;
        font-family:'Rimma Sans','Inter',sans-serif;
        font-size:clamp(2.4rem,4.4vw,4rem);
        line-height:.96;letter-spacing:-.05em;font-weight:800;text-transform:uppercase;
    }
    .ed-auth-aside.is-dark .ed-auth-heading .ed-gt{
        background:linear-gradient(90deg,#b366ff 0%,#C6FF33 50%,#b366ff 100%);
        background-size:200% 100%;-webkit-background-clip:text;background-clip:text;
        -webkit-text-fill-color:transparent;color:transparent;animation:edGradShift 6s ease infinite;
    }
    .ed-auth-aside.is-light .ed-auth-heading .ed-gt{color:#7d39eb}
    @keyframes edGradShift{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}

    .ed-auth-copy{margin-top:1.1rem;max-width:30rem;font-size:1.02rem;line-height:1.7}
    .ed-auth-aside.is-dark .ed-auth-copy{color:rgba(255,255,255,.74)}
    .ed-auth-aside.is-light .ed-auth-copy{color:#5f6470}

    /* Буллеты на панели */
    .ed-auth-bullets{list-style:none;display:grid;gap:.7rem;margin-top:1.4rem;padding:0}
    .ed-auth-bullet{display:flex;align-items:center;gap:.6rem;font-size:.9rem;font-weight:500}
    .ed-auth-aside.is-dark .ed-auth-bullet{color:rgba(255,255,255,.82)}
    .ed-auth-aside.is-light .ed-auth-bullet{color:#3f4350}
    .ed-auth-bullet .ed-bi{width:24px;height:24px;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px}
    .ed-auth-aside.is-dark .ed-auth-bullet .ed-bi{background:rgba(198,255,51,.14);color:var(--ed-lime,#C6FF33)}
    .ed-auth-aside.is-light .ed-auth-bullet .ed-bi{background:rgba(125,57,235,.1);color:#7d39eb}

    /* Статус-строка внизу панели */
    .ed-auth-status{margin-top:auto;display:inline-flex;align-items:center;gap:.5rem;font-size:.78rem;font-weight:600;padding-top:1.5rem}
    .ed-auth-aside.is-dark .ed-auth-status{color:rgba(255,255,255,.5)}
    .ed-auth-aside.is-light .ed-auth-status{color:#9ca3af}
    .ed-auth-status .ed-sd{width:7px;height:7px;border-radius:50%;background:var(--ed-lime,#C6FF33);box-shadow:0 0 0 4px rgba(198,255,51,.22);animation:edPulse2 2.4s infinite}
    @keyframes edPulse2{0%,100%{box-shadow:0 0 0 4px rgba(198,255,51,.22)}50%{box-shadow:0 0 0 8px rgba(198,255,51,.06)}}

    /* ── Правая часть: стеклянная карточка с формой ── */
    .ed-auth-main{
        display:flex;align-items:center;justify-content:center;padding:clamp(1.25rem,3vh,2.5rem);
        background:
            radial-gradient(circle at top right,rgba(125,57,235,.08),transparent 24%),
            radial-gradient(circle at bottom left,rgba(198,255,51,.12),transparent 20%),
            #f7f7fb;
    }
    .ed-auth-card{
        width:100%;max-width:30rem;padding:clamp(1.5rem,3vh,2.25rem);
        border-radius:1.75rem;
        border:1px solid rgba(255,255,255,.6);
        background:rgba(255,255,255,.82);
        backdrop-filter:blur(24px) saturate(180%);-webkit-backdrop-filter:blur(24px) saturate(180%);
        box-shadow:0 18px 48px rgba(17,17,17,.08);
    }
    .ed-auth-topline{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.5rem}
    .ed-auth-topbrand{font-family:'Rimma Sans','Inter',sans-serif;font-weight:800;font-size:1.2rem;letter-spacing:-.04em;text-transform:uppercase;color:#0f1115;display:inline-flex;align-items:center;gap:.45rem}
    .ed-auth-topbrand .ed-diamond{width:15px;height:15px}
    .ed-auth-switch{
        font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
        color:#7d39eb;text-decoration:none;padding:.5rem 1rem;border-radius:999px;
        border:1px solid rgba(125,57,235,.2);transition:all .25s ease;
    }
    .ed-auth-switch:hover{background:#7d39eb;color:#fff;border-color:#7d39eb}
    .ed-auth-cardbadge{
        display:inline-flex;align-self:flex-start;align-items:center;gap:.4rem;
        padding:.4rem .85rem;border-radius:999px;margin-bottom:.75rem;
        background:rgba(125,57,235,.08);color:#7d39eb;
        font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
    }
    .ed-auth-title{
        margin:.2rem 0 0;color:#0f1115;
        font-family:'Rimma Sans','Inter',sans-serif;
        font-size:clamp(1.8rem,3.6vw,2.6rem);line-height:1;letter-spacing:-.04em;font-weight:800;text-transform:uppercase;
    }
    .ed-auth-subtitle{margin:.7rem 0 0;color:#60646f;font-size:.98rem;line-height:1.6}
    .ed-auth-form{margin-top:1.6rem}

    /* ── Стилизация полей Filament под дизайн-систему ── */
    .ed-auth-form .fi-fo-field-wrp{margin-bottom:1rem}
    .ed-auth-form .fi-fo-field-wrp-label{margin-bottom:.45rem}
    .ed-auth-form .fi-fo-field-wrp-label label,.ed-auth-form .fi-fo-checkbox label{color:#0f1115;font-weight:600;font-size:.9rem}
    .ed-auth-form .fi-input-wrp{border:1.5px solid rgba(15,17,21,.1)!important;border-radius:1.1rem!important;background:#fff!important;box-shadow:none!important;transition:border-color .2s,box-shadow .2s}
    .ed-auth-form .fi-input-wrp:focus-within{border-color:#7d39eb!important;box-shadow:0 0 0 4px rgba(125,57,235,.12)!important}
    .ed-auth-form .fi-input-wrp input[type='text'],.ed-auth-form .fi-input-wrp input[type='email'],.ed-auth-form .fi-input-wrp input[type='password'],.ed-auth-form .fi-input-wrp input[type='tel'],.ed-auth-form .fi-input-wrp select{min-height:3.5rem;border-radius:1.1rem!important;border:0!important;background:transparent!important;box-shadow:none!important}
    .ed-auth-form .fi-input-wrp input:focus,.ed-auth-form .fi-input-wrp select:focus{border:0!important;box-shadow:none!important}
    .ed-auth-form .fi-fo-field-wrp-hint{color:#7d39eb;font-weight:600}
    .ed-auth-form .fi-checkbox-wrp,[class*='checkbox']{accent-color:#7d39eb}

    /* Главная кнопка — с shimmer */
    .ed-auth-form .fi-btn{
        min-height:3.65rem;border-radius:1.1rem!important;font-weight:700!important;font-size:1rem!important;
        background:#7d39eb!important;color:#fff!important;
        box-shadow:0 14px 30px rgba(125,57,235,.28)!important;
        position:relative;overflow:hidden;transition:transform .25s cubic-bezier(.16,1,.3,1),box-shadow .25s;
    }
    .ed-auth-form .fi-btn::after{
        content:'';position:absolute;inset:0;border-radius:inherit;pointer-events:none;
        background:linear-gradient(110deg,transparent 35%,rgba(255,255,255,.4) 50%,transparent 65%);
        background-size:250% 100%;background-position:200% 0;transition:background-position .7s ease;
    }
    .ed-auth-form .fi-btn:hover{transform:translateY(-2px);background:#6827d6!important;box-shadow:0 18px 40px rgba(125,57,235,.4)!important}
    .ed-auth-form .fi-btn:hover::after{background-position:-100% 0}

    .ed-auth-footer{margin-top:1.25rem;color:#747884;font-size:.9rem;line-height:1.6}
    .ed-auth-footer a{color:#7d39eb;font-weight:600}
    .ed-auth-footer a:hover{color:#0f1115}

    /* ── Адаптив ── */
    @media(max-width:1024px){
        .ed-auth-shell{min-height:calc(100dvh - 1rem);grid-template-columns:1fr}
        .ed-auth-aside{min-height:auto;padding:2rem clamp(1.25rem,5vw,2.5rem)}
        .ed-auth-kicker{margin-top:1.25rem}
        .ed-auth-status{display:none}
    }
    @media(max-width:640px){
        .ed-auth-layout{padding:.5rem}
        .ed-auth-shell{border-radius:1.25rem}
        .ed-auth-aside{padding:1.5rem 1.1rem}
        .ed-auth-main{padding:1.1rem}
        .ed-auth-card{padding:1.25rem;border-radius:1.25rem}
        .ed-auth-heading{max-width:none;font-size:clamp(2rem,11vw,3rem)}
        .ed-auth-copy{font-size:.95rem}
        .ed-auth-topline{align-items:flex-start;flex-direction:column;gap:.5rem}
        .ed-auth-title{font-size:1.7rem}
        .ed-auth-bullets{gap:.55rem}
    }
    @media(max-height:820px) and (min-width:1025px){
        .ed-auth-aside,.ed-auth-main{padding:1.4rem}
        .ed-auth-card{padding:1.5rem}
        .ed-auth-heading{font-size:clamp(2.2rem,7.5vh,3.4rem)}
        .ed-auth-copy{line-height:1.55;margin-top:.85rem}
        .ed-auth-bullets{margin-top:1rem;gap:.55rem}
    }
    @media(prefers-reduced-motion:reduce){
        .ed-auth-aside.is-dark::before,.ed-auth-aside.is-dark::after,.ed-auth-kicker .ed-kd,.ed-auth-status .ed-sd{animation:none!important}
        .ed-auth-heading .ed-gt{-webkit-text-fill-color:initial;background:none;animation:none}
    }
</style>

<div class="ed-auth-layout">
    <div class="ed-auth-shell">
        <aside class="ed-auth-aside {{ $isDark ? 'is-dark' : 'is-light' }}">
            <a href="{{ route('home') }}" class="ed-auth-brand" aria-label="Edusfera — на главную">
                Edusfera
                <svg class="ed-diamond" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M12 2L22 12L12 22L2 12L12 2Z"/></svg>
            </a>

            <span class="ed-auth-kicker"><span class="ed-kd"></span>{{ $asideBadge }}</span>

            <h1 class="ed-auth-heading">{!! $asideTitle !!}</h1>

            @if($asideCopy)
            <p class="ed-auth-copy">{{ $asideCopy }}</p>
            @endif

            @if(!empty($bullet))
            <ul class="ed-auth-bullets">
                @foreach($bullet as $b)
                    <li class="ed-auth-bullet"><span class="ed-bi">{{ $b[0] }}</span>{{ $b[1] }}</li>
                @endforeach
            </ul>
            @endif

            <div class="ed-auth-status"><span class="ed-sd"></span> Система работает стабильно</div>
        </aside>

        <main class="ed-auth-main">
            <section class="ed-auth-card">
                <div class="ed-auth-topline">
                    <span class="ed-auth-topbrand">
                        EDUSFERA
                        <svg class="ed-diamond" viewBox="0 0 24 24" fill="none" stroke="#C6FF33" stroke-width="3" aria-hidden="true"><path d="M12 2L22 12L12 22L2 12L12 2Z"/></svg>
                    </span>
                    @if($switchLabel)
                    <a href="{{ $switchHref }}" class="ed-auth-switch">{{ $switchLabel }}</a>
                    @endif
                </div>

                <h2 class="ed-auth-title">{{ $cardTitle }}</h2>
                @if($cardSubtitle)
                <p class="ed-auth-subtitle">{{ $cardSubtitle }}</p>
                @endif

                {!! $formSlot !!}

                @if($footerSlot)
                <p class="ed-auth-footer">{!! $footerSlot !!}</p>
                @endif
            </section>
        </main>
    </div>
</div>
