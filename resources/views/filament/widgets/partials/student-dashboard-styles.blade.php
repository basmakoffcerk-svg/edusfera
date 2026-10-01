{{--
    Дизайн-система кабинета ученика Edusfera (UI_RULES.md / .agents/AGENTS.md):
    - Единый акцент #7D39EB (фиолетовый), неоновый статус #C6FF33 / #10B981 для онлайн-класса;
    - Glassmorphism, мягкие тени, 8pt сетка, 20px скругления;
    - Адаптивные тач-таргеты >= 44px на смартфонах;
    - Полная поддержка светлой и темной (.dark) темы;
    - Автономный CSS, не зависящий от сборки Tailwind админки.
--}}

<style>
    /* ═══ Базовые переменные и сброс для кабинета ученика ══════════ */
    :root {
        --stu-accent: #7D39EB;
        --stu-accent-hover: #6C2BD9;
        --stu-accent-light: rgba(125, 57, 235, 0.08);
        --stu-accent-glow: rgba(125, 57, 235, 0.22);
        --stu-live-green: #10B981;
        --stu-live-lime: #C6FF33;
        --stu-card-bg: #FFFFFF;
        --stu-card-border: #ECEEF1;
        --stu-card-shadow: 0 4px 20px -2px rgba(16, 10, 31, 0.05), 0 2px 6px -1px rgba(16, 10, 31, 0.02);
        --stu-text-main: #0F172A;
        --stu-text-muted: #64748B;
        --stu-surface-subtle: #F8FAFC;
    }

    .dark {
        --stu-card-bg: #111827;
        --stu-card-border: #1F2937;
        --stu-card-shadow: 0 4px 24px -2px rgba(0, 0, 0, 0.35);
        --stu-text-main: #F8FAFC;
        --stu-text-muted: #94A3B8;
        --stu-surface-subtle: rgba(30, 41, 59, 0.45);
        --stu-accent-light: rgba(125, 57, 235, 0.16);
    }

    .student-dashboard-root [x-cloak] { display: none !important; }

    /* ═══ Общий контейнер и карточки ═══════════════════════════════ */
    .student-card {
        background: var(--stu-card-bg);
        border: 1px solid var(--stu-card-border);
        border-radius: 20px;
        padding: 24px;
        box-shadow: var(--stu-card-shadow);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    @media (max-width: 640px) {
        .student-card { padding: 18px; border-radius: 16px; }
    }

    /* ═══ 1. Hero-шапка ученика ════════════════════════════════════ */
    .student-hero-header {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 24px;
    }

    .student-hero-top {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px 24px;
    }

    .student-hero-title-box h1 {
        margin: 0;
        font-size: clamp(1.6rem, 1.2rem + 1.6vw, 2.35rem);
        font-weight: 900;
        letter-spacing: -0.03em;
        line-height: 1.15;
        color: var(--stu-text-main);
    }

    .student-hero-subtitle {
        margin: 6px 0 0;
        font-size: 0.95rem;
        color: var(--stu-text-muted);
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .student-role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 9999px;
        background: var(--stu-accent-light);
        color: var(--stu-accent);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.02em;
        border: 1px solid rgba(125, 57, 235, 0.2);
    }

    .student-hero-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .student-chip-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
        border: 1px solid var(--stu-card-border);
        background: var(--stu-card-bg);
        color: var(--stu-text-main);
        cursor: pointer;
    }

    .student-chip-btn:hover {
        border-color: var(--stu-accent);
        color: var(--stu-accent);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--stu-accent-glow);
    }

    .student-chip-btn--primary {
        background: linear-gradient(135deg, #7D39EB, #6320D6);
        color: #FFFFFF !important;
        border-color: transparent;
        box-shadow: 0 4px 14px var(--stu-accent-glow);
    }

    .student-chip-btn--primary:hover {
        background: linear-gradient(135deg, #6C2BD9, #5214C4);
        transform: translateY(-2px);
    }

    .student-chip-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 9999px;
        background: #EF4444;
        color: #FFFFFF;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
    }

    /* ═══ 2. Spotlight-карточка урока (Живой урок / Ближайший) ═══ */
    .student-spotlight-card {
        border-radius: 20px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        margin-bottom: 24px;
        transition: all 0.25s ease;
    }

    /* Состояние: Урок прямо сейчас (LIVE) */
    .student-spotlight-card--live {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(125, 57, 235, 0.07) 100%), var(--stu-card-bg);
        border: 2px solid #10B981;
        box-shadow: 0 8px 30px rgba(16, 185, 129, 0.18);
    }

    .dark .student-spotlight-card--live {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(125, 57, 235, 0.12) 100%), var(--stu-card-bg);
        border-color: rgba(16, 185, 129, 0.85);
    }

    /* Состояние: Урок скоро (запланирован) */
    .student-spotlight-card--upcoming {
        background: linear-gradient(135deg, rgba(125, 57, 235, 0.04) 0%, var(--stu-surface-subtle) 100%), var(--stu-card-bg);
        border: 1px solid rgba(125, 57, 235, 0.2);
    }

    /* Состояние: Спокойно / Нет уроков */
    .student-spotlight-card--empty {
        background: var(--stu-surface-subtle);
        border: 1px dashed var(--stu-card-border);
        text-align: center;
        padding: 32px 24px;
    }

    .student-spotlight-inner {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .student-spotlight-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .student-pulse-dot {
        width: 10px;
        height: 10px;
        border-radius: 9999px;
        background: #10B981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: stuPulse 1.6s infinite;
    }

    @keyframes stuPulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .student-spotlight-details {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 10px;
    }

    .student-tutor-avatar-lg {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        background: linear-gradient(135deg, #7D39EB, #A855F7);
        color: #FFFFFF;
        font-size: 20px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 12px var(--stu-accent-glow);
    }

    .student-spotlight-info h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--stu-text-main);
        line-height: 1.2;
    }

    .student-spotlight-meta {
        margin-top: 4px;
        font-size: 0.9rem;
        color: var(--stu-text-muted);
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .student-spotlight-cta {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
    }

    .student-spotlight-cta--live {
        background: linear-gradient(135deg, #10B981, #059669);
        color: #FFFFFF !important;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
        animation: liveBounce 2.5s infinite;
    }

    @keyframes liveBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }

    .student-spotlight-cta--live:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.45);
    }

    .student-spotlight-cta--upcoming {
        background: var(--stu-accent);
        color: #FFFFFF !important;
        box-shadow: 0 4px 15px var(--stu-accent-glow);
    }

    .student-spotlight-cta--upcoming:hover {
        background: var(--stu-accent-hover);
        transform: translateY(-2px);
    }

    /* ═══ 3. Сетка интерактивных смарт-метрик ══════════════════════ */
    .student-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    @media (max-width: 1024px) {
        .student-metrics-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .student-metrics-grid { grid-template-columns: 1fr; gap: 12px; }
    }

    .student-metric-card {
        background: var(--stu-card-bg);
        border: 1px solid var(--stu-card-border);
        border-radius: 18px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 12px;
        transition: all 0.2s ease;
        position: relative;
    }

    .student-metric-card:hover {
        border-color: rgba(125, 57, 235, 0.4);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px -4px rgba(125, 57, 235, 0.12);
    }

    .student-metric-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .student-metric-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--stu-text-muted);
    }

    .student-metric-icon-wrap {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
    }

    .student-metric-value {
        font-size: 1.75rem;
        font-weight: 900;
        letter-spacing: -0.02em;
        line-height: 1.1;
        color: var(--stu-text-main);
    }

    .student-metric-subtext {
        font-size: 12px;
        color: var(--stu-text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .student-metric-action {
        font-size: 12px;
        font-weight: 700;
        color: var(--stu-accent);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 4px;
    }

    .student-metric-action:hover {
        text-decoration: underline;
    }

    /* ═══ 4. Траектория подготовки и слабые темы ═══════════════════ */
    .student-progress-card {
        background: var(--stu-card-bg);
        border: 1px solid var(--stu-card-border);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .student-progress-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .student-progress-title-wrap h2 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--stu-text-main);
    }

    .student-progress-bar-outer {
        height: 12px;
        width: 100%;
        background: var(--stu-surface-subtle);
        border: 1px solid var(--stu-card-border);
        border-radius: 9999px;
        overflow: hidden;
        position: relative;
        margin-top: 8px;
    }

    .student-progress-bar-fill {
        height: 100%;
        border-radius: 9999px;
        background: linear-gradient(90deg, #7D39EB 0%, #10B981 100%);
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 0 12px rgba(125, 57, 235, 0.4);
    }

    .student-gaps-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .student-gap-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(245, 158, 11, 0.1);
        color: #D97706;
        border: 1px solid rgba(245, 158, 11, 0.25);
    }

    .dark .student-gap-pill {
        background: rgba(245, 158, 11, 0.15);
        color: #FBBF24;
    }

    /* ═══ 5. Карточки уроков и репетиторов (Сетка 2 колонки) ══════ */
    .student-two-col-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    @media (max-width: 900px) {
        .student-two-col-grid { grid-template-columns: 1fr; gap: 16px; }
    }

    .student-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 14px;
        border: 1px solid var(--stu-card-border);
        background: var(--stu-card-bg);
        transition: all 0.15s ease;
    }

    .student-list-item:hover {
        border-color: rgba(125, 57, 235, 0.35);
        background: var(--stu-surface-subtle);
        transform: translateY(-1px);
    }

    .student-status-badge {
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .student-status-badge--paid {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
    }
    .dark .student-status-badge--paid {
        background: rgba(16, 185, 129, 0.2);
        color: #34D399;
    }

    .student-status-badge--unpaid {
        background: rgba(245, 158, 11, 0.12);
        color: #D97706;
    }
    .dark .student-status-badge--unpaid {
        background: rgba(245, 158, 11, 0.2);
        color: #FBBF24;
    }

    /* ═══ Адаптивность и мобильный тюнинг ═════════════════════════ */
    @media (max-width: 640px) {
        .student-spotlight-inner {
            flex-direction: column;
            align-items: stretch;
        }
        .student-spotlight-cta {
            width: 100%;
            justify-content: center;
        }
        .student-hero-actions {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .student-chip-btn {
            flex-shrink: 0;
        }
    }
</style>
