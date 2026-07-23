@php
    /**
     * Общая дизайн-система auth-страниц Edusfera (2026).
     * Мобильная адаптивность UI/UX Pro Max.
     */
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
    $isMinimal = $asideTheme === 'minimal';
@endphp
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

    /* ── Сброс обёртки Filament simple-page ── */
    .fi-simple-header { display: none !important; }
    .fi-simple-page { width: 100%; max-width: none; }
    .fi-simple-layout, .fi-simple-main-ctn { min-height: 100dvh !important; background: #f8f9fb !important; padding: 0 !important; }
    .fi-simple-main { margin: 0 !important; padding: 0 !important; max-width: 100% !important; border-radius: 0 !important; border: 0 !important; background: transparent !important; box-shadow: none !important; --tw-ring-shadow: 0 0 #0000 !important; }

    /* ═══ EDUSFERA AUTH 2026 — ДИЗАЙН-СИСТЕМА ═══ */
    .ed-auth-layout {
        min-height: 100dvh;
        padding: clamp(1rem, 3vh, 2.5rem);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background-color: #f8f9fb;
        background-image: 
            radial-gradient(circle at 10% 10%, rgba(125, 57, 235, 0.05) 0%, transparent 45%),
            radial-gradient(circle at 90% 90%, rgba(125, 57, 235, 0.03) 0%, transparent 45%);
        background-attachment: fixed;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ── Ультраминималистичный вариант (Minimal Centered Card) ── */
    .ed-auth-minimal-shell {
        width: 100%;
        max-width: 540px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .ed-auth-minimal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 0.5rem;
    }

    .ed-auth-shell {
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        min-height: calc(100dvh - clamp(1rem, 4vh, 3rem));
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
        border-radius: 2.25rem;
        overflow: hidden;
        border: 1px solid rgba(125, 57, 235, 0.12);
        background: #ffffff;
        box-shadow: 0 30px 80px rgba(17, 17, 17, 0.08);
    }

    /* ── Мобильный верхний бар ── */
    .ed-mobile-bar {
        display: none;
        align-items: center;
        justify-content: space-between;
        padding: 0.85rem 1.25rem;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        width: 100%;
    }

    .ed-mobile-back {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.82rem;
        font-weight: 700;
        color: #6b7280;
        text-decoration: none;
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.04);
        transition: color 0.2s, background-color 0.2s;
    }
    .ed-mobile-back:hover { color: #0f1115; background: rgba(0, 0, 0, 0.08); }

    /* ── Левая панель ── */
    .ed-auth-aside {
        position: relative;
        padding: clamp(2rem, 4vh, 3.5rem);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        color: #ffffff;
        background:
            radial-gradient(circle at 18% 18%, rgba(198, 255, 51, 0.18), transparent 25%),
            radial-gradient(circle at 82% 28%, rgba(255, 255, 255, 0.08), transparent 20%),
            linear-gradient(150deg, #09090b 0%, #1a0b2e 50%, #7d39eb 115%);
    }

    /* Бренд */
    .ed-auth-brand { display: inline-flex; align-items: center; gap: 0.6rem; font-family: 'Rimma Sans', 'Inter', sans-serif; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.04em; text-transform: uppercase; color: #0f1115; text-decoration: none; }
    .ed-auth-brand .ed-diamond { width: 18px; height: 18px; display: inline-block; flex-shrink: 0; color: #7d39eb; }
    .ed-auth-aside .ed-auth-brand { color: #ffffff; }
    .ed-auth-aside .ed-diamond { color: #C6FF33; }

    /* Заголовок панели */
    .ed-auth-heading {
        margin: 0; max-width: 12ch;
        font-family: 'Rimma Sans', 'Inter', sans-serif;
        font-size: clamp(2.2rem, 4.2vw, 3.8rem);
        line-height: 0.98; letter-spacing: -0.05em; font-weight: 900; text-transform: uppercase;
    }
    .ed-auth-heading .ed-gt {
        background: linear-gradient(90deg, #b366ff 0%, #C6FF33 50%, #b366ff 100%);
        background-size: 200% 100%; -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent; color: transparent; animation: edGradShift 6s ease infinite;
    }

    .ed-auth-copy { margin-top: 1.1rem; max-width: 28rem; font-size: 1rem; line-height: 1.65; font-weight: 400; color: rgba(255, 255, 255, 0.78); }

    /* ── Карточка формы (Ультраминимализм) ── */
    .ed-auth-main {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: clamp(1.25rem, 4vh, 3rem);
        background: transparent;
    }

    .ed-auth-card {
        width: 100%;
        padding: clamp(1.75rem, 4vh, 2.75rem);
        border-radius: 2rem;
        border: 1px solid rgba(0, 0, 0, 0.07);
        background: #ffffff;
        box-shadow: 0 20px 60px -15px rgba(0, 0, 0, 0.05);
    }

    .ed-auth-topline { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.75rem; }
    .ed-auth-topbrand { font-family: 'Rimma Sans', 'Inter', sans-serif; font-weight: 800; font-size: 1.25rem; letter-spacing: -0.04em; text-transform: uppercase; color: #0f1115; display: inline-flex; align-items: center; gap: 0.45rem; text-decoration: none; }
    .ed-auth-topbrand .ed-diamond { width: 16px; height: 16px; color: #7D39EB; }
    
    .ed-auth-switch {
        font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
        color: #7d39eb; text-decoration: none; padding: 0.5rem 1.1rem; border-radius: 999px;
        border: 1px solid rgba(125, 57, 235, 0.2); transition: all 0.25s ease; background: rgba(125, 57, 235, 0.05);
        min-height: 38px; display: inline-flex; align-items: center; justify-content: center;
    }
    .ed-auth-switch:hover { background: #7d39eb; color: #ffffff; border-color: #7d39eb; }

    .ed-auth-title {
        margin: 0.2rem 0 0; color: #0f1115;
        font-family: 'Rimma Sans', 'Inter', sans-serif;
        font-size: clamp(1.6rem, 3.5vw, 2.2rem); line-height: 1.05; letter-spacing: -0.04em; font-weight: 800; text-transform: uppercase;
    }
    .ed-auth-subtitle { margin: 0.4rem 0 0; color: #6b7280; font-size: 0.92rem; line-height: 1.5; font-weight: 500; }
    .ed-auth-form { margin-top: 1.5rem; }

    /* ── Чистые минималистичные поля Filament ── */
    .ed-auth-form .fi-fo-field-wrp { margin-bottom: 1.25rem; }
    .ed-auth-form .fi-fo-field-wrp-label { margin-bottom: 0.4rem; }
    .ed-auth-form .fi-fo-field-wrp-label label, .ed-auth-form .fi-fo-checkbox label { color: #0f1115 !important; font-weight: 700 !important; font-size: 0.8rem !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; }
    
    .ed-auth-form .fi-input-wrp {
        border: 1px solid rgba(15, 17, 21, 0.12) !important;
        border-radius: 1rem !important;
        background: #ffffff !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02) !important;
        transition: border-color 0.2s, box-shadow 0.2s;
        overflow: hidden;
    }
    .ed-auth-form .fi-input-wrp:focus-within {
        border-color: #7d39eb !important;
        box-shadow: 0 0 0 4px rgba(125, 57, 235, 0.12) !important;
    }
    
    .ed-auth-form .fi-input-wrp input[type='text'],
    .ed-auth-form .fi-input-wrp input[type='email'],
    .ed-auth-form .fi-input-wrp input[type='password'],
    .ed-auth-form .fi-input-wrp input[type='tel'],
    .ed-auth-form .fi-input-wrp select {
        min-height: 3.25rem;
        border-radius: 1rem !important;
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        font-size: 16px !important;
        font-weight: 500 !important;
        color: #0f1115 !important;
        padding: 0 1.15rem !important;
    }

    /* ── Ультраминималистичный 3-шаговый Wizard (Без стрелок, только плашки) ── */
    .ed-auth-form .fi-fo-wizard { border: 0 !important; background: transparent !important; box-shadow: none !important; }
    .ed-auth-form .fi-fo-wizard-header {
        border: 0 !important;
        padding: 0 !important;
        margin-bottom: 1.5rem !important;
        background: transparent !important;
    }
    .ed-auth-form .fi-fo-wizard-header ol {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 0.6rem !important;
        border: 0 !important;
        background: transparent !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .ed-auth-form .fi-fo-wizard-header li {
        border: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        display: flex !important;
        align-items: center !important;
    }
    /* Полное скрытие разделительных стрелок и шевронов между шагами */
    .ed-auth-form .fi-fo-wizard-header svg:not(.fi-fo-wizard-header-step-icon),
    .ed-auth-form .fi-fo-wizard-header [class*="separator"],
    .ed-auth-form .fi-fo-wizard-header [class*="chevron"],
    .ed-auth-form .fi-fo-wizard-header li::after,
    .ed-auth-form .fi-fo-wizard-header li::before {
        display: none !important;
        content: none !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .ed-auth-form .fi-fo-wizard-header-step-button {
        padding: 0.6rem 1.1rem !important;
        border-radius: 999px !important;
        border: 1px solid rgba(15, 17, 21, 0.08) !important;
        background: #f4f5f8 !important;
        transition: all 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
    }
    .ed-auth-form .fi-fo-wizard-header-step-button[aria-current="step"] {
        background: #7d39eb !important;
        border-color: #7d39eb !important;
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(125, 57, 235, 0.25) !important;
    }
    .ed-auth-form .fi-fo-wizard-header-step-button[aria-current="step"] * {
        color: #ffffff !important;
    }

    .ed-auth-form .fi-fo-wizard-footer-actions {
        margin-top: 1.5rem !important;
        gap: 0.75rem !important;
    }

    /* Кнопки действий */
    .ed-auth-form .fi-btn {
        min-height: 3.25rem;
        border-radius: 1rem !important;
        font-weight: 800 !important;
        font-size: 0.9rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        background: #7d39eb !important;
        color: #ffffff !important;
        box-shadow: 0 8px 20px rgba(125, 57, 235, 0.25) !important;
        position: relative;
        transition: all 0.2s ease;
    }
    .ed-auth-form .fi-btn:active { transform: scale(0.98); }
    .ed-auth-form .fi-btn:hover { background: #6c2bd9 !important; box-shadow: 0 12px 28px rgba(125, 57, 235, 0.35) !important; }

    .ed-auth-footer { margin-top: 1.5rem; color: #6b7280; font-size: 0.88rem; font-weight: 500; text-align: center; }
    .ed-auth-footer a { color: #7d39eb; font-weight: 700; text-decoration: none; margin-left: 0.25rem; }
    .ed-auth-footer a:hover { text-decoration: underline; }
</style>

<div class="ed-auth-layout">
    @if($isMinimal)
        {{-- Ультраминималистичный центрированный макет для регистрации --}}
        <div class="ed-auth-minimal-shell">
            <header class="ed-auth-minimal-header">
                <a href="{{ route('home') }}" class="ed-auth-brand">
                    EDUSFERA
                    <svg class="ed-diamond" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M12 2L22 12L12 22L2 12L12 2Z"/></svg>
                </a>
                @if($switchLabel)
                <a href="{{ $switchHref }}" class="ed-auth-switch">{{ $switchLabel }}</a>
                @endif
            </header>

            <main class="ed-auth-card">
                <h2 class="ed-auth-title">{{ $cardTitle }}</h2>
                @if($cardSubtitle)
                <p class="ed-auth-subtitle">{{ $cardSubtitle }}</p>
                @endif

                {!! $formSlot !!}

                @if($footerSlot)
                <p class="ed-auth-footer">{!! $footerSlot !!}</p>
                @endif
            </main>
        </div>
    @else
        {{-- Двухколоночный макет (для входа и прочих страниц) --}}
        <div class="ed-auth-shell">
            <header class="ed-mobile-bar">
                <a href="{{ route('home') }}" class="ed-mobile-back">← На главную</a>
                @if($switchLabel)
                <a href="{{ $switchHref }}" class="ed-auth-switch">{{ $switchLabel }}</a>
                @endif
            </header>

            <aside class="ed-auth-aside is-dark">
                <a href="{{ route('home') }}" class="ed-auth-brand" aria-label="Edusfera — на главную">
                    Edusfera
                    <svg class="ed-diamond" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M12 2L22 12L12 22L2 12L12 2Z"/></svg>
                </a>
                <span class="ed-auth-kicker">{{ $asideBadge }}</span>
                <h1 class="ed-auth-heading">{!! $asideTitle !!}</h1>
                @if($asideCopy)
                <p class="ed-auth-copy">{{ $asideCopy }}</p>
                @endif
            </aside>

            <main class="ed-auth-main">
                <section class="ed-auth-card">
                    <div class="ed-auth-topline">
                        <a href="{{ route('home') }}" class="ed-auth-topbrand">
                            EDUSFERA
                            <svg class="ed-diamond" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M12 2L22 12L12 22L2 12L12 2Z"/></svg>
                        </a>
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
    @endif
</div>
