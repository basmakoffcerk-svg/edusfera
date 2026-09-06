{{--
    Дизайн-система кабинета репетитора (UI_RULES.md / .agents/AGENTS.md):
    - один акцент #7D39EB; лайм #C6FF33 — только для живой кнопки «Войти в класс»;
    - вся раскладка на собственном CSS: сборка Tailwind панели не содержит
      произвольных утилит (grid-cols-3 и т.п.), полагаться на них нельзя;
    - сетка 8pt, тач-таргеты >= 44px на смартфоне, 40px от 640px;
    - dark mode через селектор .dark (переключатель Filament).
    Подключается один раз (@once) из верхнего виджета кабинета.
--}}

<style>
    /* ═══ Базовые примитивы ═══════════════════════════════════════ */

    .tutor-hero [x-cloak] { display: none !important; }

    .tutor-card {
        background: #fff;
        border: 1px solid #ECEEF1;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 1px 2px rgba(16, 10, 31, .04);
    }
    .dark .tutor-card { background: #111827; border-color: #1F2937; }

    .tutor-card-label {
        font-size: 11px; font-weight: 800; letter-spacing: .09em;
        text-transform: uppercase; color: #9CA3AF;
    }

    /* ═══ Hero-блок ═══════════════════════════════════════════════ */

    .tutor-hero { display: flex; flex-direction: column; gap: 16px; }

    .tutor-hero-top {
        display: flex; flex-wrap: wrap; align-items: baseline;
        justify-content: space-between; gap: 4px 24px;
    }

    .tutor-hero-title {
        margin: 0;
        font-size: clamp(1.5rem, 1.1rem + 1.8vw, 2.25rem);
        font-weight: 900; letter-spacing: -.02em; line-height: 1.1;
        color: #0C0A14;
    }
    .dark .tutor-hero-title { color: #fff; }

    .tutor-hero-date {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .14em; color: #9CA3AF;
    }

    .tutor-hero-card {
        margin-top: 4px; padding: 20px;
        border-radius: 20px;
        background: #FAF8FF;
        border: 1px solid #ECE5FB;
        box-shadow: 0 1px 2px rgba(16, 10, 31, .04);
    }
    .dark .tutor-hero-card { background: rgba(125, 57, 235, .07); border-color: rgba(125, 57, 235, .24); }

    .tutor-hero-card--live { border: 2px solid #C6FF33; padding: 19px; }
    .dark .tutor-hero-card--live { border-color: rgba(198, 255, 51, .8); }

    .tutor-hero-card--calm {
        background: #F9FAFB; border: 1px dashed #E5E7EB; box-shadow: none;
    }
    .dark .tutor-hero-card--calm { background: rgba(31, 41, 55, .35); border-color: #374151; }

    .tutor-hero-status {
        display: flex; align-items: center; gap: 8px;
        font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .1em; color: #6B7280;
    }
    .dark .tutor-hero-status { color: #9CA3AF; }

    .tutor-hero-dot {
        width: 9px; height: 9px; border-radius: 9999px; flex: none;
        background: #65C400;
        animation: tutor-dot-pulse 1.6s ease-in-out infinite;
    }
    @keyframes tutor-dot-pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .45; transform: scale(.7); }
    }

    .tutor-hero-info {
        margin-top: 12px;
        display: flex; flex-wrap: wrap; align-items: baseline;
        justify-content: space-between; gap: 4px 24px;
    }

    .tutor-hero-name {
        margin: 0; font-size: 1.125rem; font-weight: 800; line-height: 1.2;
        color: #0C0A14;
    }
    @media (min-width: 640px) { .tutor-hero-name { font-size: 1.25rem; } }
    .dark .tutor-hero-name { color: #fff; }

    .tutor-hero-sub { margin: 4px 0 0; font-size: .875rem; color: #6B7280; }
    .dark .tutor-hero-sub { color: #9CA3AF; }

    .tutor-hero-time {
        margin: 0; font-size: 1rem; font-weight: 700;
        font-variant-numeric: tabular-nums; color: #1F2937;
    }
    @media (min-width: 640px) { .tutor-hero-time { font-size: 1.125rem; } }
    .dark .tutor-hero-time { color: #E5E7EB; }

    .tutor-hero-text { margin: 8px 0 0; font-size: .875rem; line-height: 1.5; color: #6B7280; }
    .dark .tutor-hero-text { color: #9CA3AF; }

    /* ═══ Кнопки действий ═════════════════════════════════════════ */

    .tutor-cta-row {
        margin-top: 20px; display: flex; flex-direction: column; gap: 12px;
    }
    @media (min-width: 640px) { .tutor-cta-row { flex-direction: row; flex-wrap: wrap; } }

    .tutor-cta {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-height: 48px; padding: 0 20px;
        border-radius: 14px;
        font-size: .9375rem; font-weight: 700; line-height: 1;
        text-decoration: none; white-space: nowrap;
        transition: transform .12s ease, background-color .2s ease, border-color .2s ease, color .2s ease;
        -webkit-tap-highlight-color: transparent;
    }
    @media (min-width: 640px) { .tutor-cta { min-height: 40px; } }
    .tutor-cta:active { transform: scale(.97); }
    .tutor-cta svg { width: 20px; height: 20px; flex: none; }

    .tutor-cta--lime {
        background: #C6FF33; color: #0A0A0A;
        animation: tutor-pulse-glow 2.2s ease-out infinite;
    }
    .tutor-cta--lime:hover { background: #d4ff5e; }
    @keyframes tutor-pulse-glow {
        0%   { box-shadow: 0 0 0 0 rgba(198, 255, 51, .5); }
        70%  { box-shadow: 0 0 0 14px rgba(198, 255, 51, 0); }
        100% { box-shadow: 0 0 0 0 rgba(198, 255, 51, 0); }
    }

    .tutor-cta--primary { background: #7D39EB; color: #fff; }
    .tutor-cta--primary:hover { background: #6B2FD0; }

    .tutor-cta--ghost {
        background: transparent; color: #4B5563;
        border: 1.5px solid #E5E7EB;
    }
    .dark .tutor-cta--ghost { color: #D1D5DB; border-color: #374151; }
    .tutor-cta--ghost:hover { border-color: #7D39EB; color: #7D39EB; }
    .dark .tutor-cta--ghost:hover { color: #A78BFA; border-color: #A78BFA; }

    /* ═══ Строка «новые заявки» ═══════════════════════════════════ */

    .tutor-hero-alert {
        display: flex; align-items: center; gap: 10px;
        min-height: 52px; padding: 12px 16px;
        border-radius: 16px;
        background: rgba(125, 57, 235, .08);
        color: #4B5563; font-size: .9375rem; text-decoration: none;
        transition: background-color .2s ease;
        -webkit-tap-highlight-color: transparent;
    }
    .tutor-hero-alert:hover { background: rgba(125, 57, 235, .15); }
    .tutor-hero-alert svg { width: 20px; height: 20px; flex: none; color: #7D39EB; }
    .tutor-hero-alert .tutor-alert-note { color: #6B7280; }
    .dark .tutor-hero-alert { background: rgba(125, 57, 235, .16); color: #E5E7EB; }
    .dark .tutor-hero-alert .tutor-alert-note { color: #9CA3AF; }
    .tutor-hero-alert .tutor-alert-chevron { margin-left: auto; width: 16px; height: 16px; color: #9CA3AF; }

    /* ═══ Финансовая лента ════════════════════════════════════════ */

    .tutor-finance { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    @media (min-width: 640px) { .tutor-finance { gap: 16px; } }

    .tutor-stat {
        display: flex; flex-direction: column; justify-content: space-between; gap: 8px;
        min-height: 96px; padding: 12px;
        background: #fff; border: 1px solid #ECEEF1; border-radius: 14px;
        text-decoration: none;
        transition: border-color .2s ease, transform .12s ease;
        -webkit-tap-highlight-color: transparent;
    }
    @media (min-width: 640px) { .tutor-stat { padding: 16px; border-radius: 18px; } }
    .dark .tutor-stat { background: #111827; border-color: #1F2937; }
    .tutor-stat:hover { border-color: #7D39EB; }
    .tutor-stat:active { transform: scale(.98); }
    .tutor-stat--accent { box-shadow: inset 0 0 0 1px rgba(125, 57, 235, .35); }

    .tutor-stat-label {
        font-size: 10px; font-weight: 800; letter-spacing: .07em;
        text-transform: uppercase; color: #9CA3AF; line-height: 1.25;
    }

    .tutor-stat-value {
        display: block;
        font-size: 1.125rem; font-weight: 900; letter-spacing: -.01em;
        font-variant-numeric: tabular-nums; line-height: 1.1;
        color: #0C0A14;
    }
    @media (min-width: 640px) { .tutor-stat-value { font-size: 1.5rem; } }
    .dark .tutor-stat-value { color: #fff; }
    .tutor-stat--accent .tutor-stat-value { color: #7D39EB; }
    .dark .tutor-stat--accent .tutor-stat-value { color: #A78BFA; }

    .tutor-stat-hint { display: block; margin-top: 2px; font-size: 10px; color: #9CA3AF; }
    @media (min-width: 640px) { .tutor-stat-hint { font-size: 12px; } }

    /* ═══ Центр действий ══════════════════════════════════════════ */

    .tutor-actions { display: grid; grid-template-columns: 1fr; gap: 16px; }
    @media (min-width: 1024px) { .tutor-actions { grid-template-columns: 3fr 2fr; gap: 24px; } }

    .tutor-card-head {
        display: flex; flex-wrap: wrap; align-items: center;
        justify-content: space-between; gap: 8px 12px;
    }

    .tutor-request { margin-top: 16px; display: flex; flex-direction: column; gap: 16px; flex: 1; }
    .tutor-request-row {
        display: flex; flex-wrap: wrap; align-items: baseline;
        justify-content: space-between; gap: 2px 16px;
    }
    .tutor-request-name {
        margin: 0; font-size: 1rem; font-weight: 800; line-height: 1.25; color: #0C0A14;
    }
    @media (min-width: 640px) { .tutor-request-name { font-size: 1.125rem; } }
    .dark .tutor-request-name { color: #fff; }
    .tutor-request-time {
        margin: 0; font-size: .875rem; font-weight: 600;
        font-variant-numeric: tabular-nums; color: #6B7280;
    }
    .dark .tutor-request-time { color: #9CA3AF; }
    .tutor-request-note { margin: 0; font-size: .875rem; color: #6B7280; }
    .dark .tutor-request-note { color: #9CA3AF; }

    .tutor-empty {
        margin-top: 16px; flex: 1;
        display: flex; flex-direction: column; align-items: flex-start;
        justify-content: center; gap: 8px;
        border: 1px dashed #E5E7EB; border-radius: 16px; padding: 20px;
    }
    .dark .tutor-empty { border-color: #374151; }
    .tutor-empty-title { margin: 0; font-size: 1rem; font-weight: 800; color: #0C0A14; }
    .dark .tutor-empty-title { color: #fff; }
    .tutor-empty-text { margin: 0; font-size: .875rem; line-height: 1.5; color: #6B7280; }
    .dark .tutor-empty-text { color: #9CA3AF; }

    /* ═══ Плитки быстрых действий ═════════════════════════════════ */

    .tutor-tiles { margin-top: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    @media (min-width: 640px) { .tutor-tiles { gap: 12px; } }

    .tutor-tile {
        position: relative;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
        min-height: 84px; padding: 12px 8px;
        border-radius: 16px;
        background: #F9FAFB; border: 1px solid #ECEEF1;
        text-decoration: none;
        transition: border-color .2s ease, transform .12s ease;
        -webkit-tap-highlight-color: transparent;
    }
    .dark .tutor-tile { background: #111827; border-color: #1F2937; }
    .tutor-tile:hover { border-color: #7D39EB; }
    .tutor-tile:active { transform: scale(.97); }
    .tutor-tile svg { width: 24px; height: 24px; color: #7D39EB; }
    .tutor-tile span { font-size: .8125rem; font-weight: 700; color: #374151; }
    .dark .tutor-tile span { color: #D1D5DB; }

    .tutor-tile-badge {
        position: absolute; top: 8px; right: 8px;
        min-width: 20px; height: 20px; padding: 0 5px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 9999px;
        background: #7D39EB; color: #fff;
        font-size: 11px; font-weight: 800; line-height: 1;
    }

    /* ═══ Прогресс-бары ═══════════════════════════════════════════ */

    .tutor-progress {
        height: 8px; border-radius: 9999px; overflow: hidden;
        background: #F3F4F6;
    }
    .dark .tutor-progress { background: #1F2937; }
    .tutor-progress > div {
        height: 100%; border-radius: 9999px;
        background: #7D39EB;
        transition: width .5s ease;
    }

    /* ═══ SaaS подписка ═══════════════════════════════════════ */

    .tutor-comm-top {
        display: flex; flex-wrap: wrap; align-items: baseline;
        justify-content: space-between; gap: 8px 32px;
    }
    .tutor-comm-rate {
        display: flex; align-items: baseline; gap: 12px;
    }
    .tutor-comm-rate-value {
        font-size: clamp(1.875rem, 1.4rem + 2vw, 2.5rem);
        font-weight: 900; letter-spacing: -.02em; line-height: 1;
        font-variant-numeric: tabular-nums; color: #0C0A14;
    }
    .dark .tutor-comm-rate-value { color: #fff; }
    .tutor-comm-rate-unit { font-size: 1.125rem; font-weight: 700; color: #9CA3AF; }
    .tutor-comm-revenue { text-align: right; }
    .tutor-comm-revenue-value {
        margin: 2px 0 0; font-size: 1.375rem; font-weight: 900; letter-spacing: -.01em;
        font-variant-numeric: tabular-nums; color: #7D39EB; line-height: 1.1;
    }
    @media (min-width: 640px) { .tutor-comm-revenue-value { font-size: 1.5rem; } }
    .dark .tutor-comm-revenue-value { color: #A78BFA; }
    .tutor-comm-revenue-unit { font-size: 12px; font-weight: 700; color: #9CA3AF; }

    .tutor-comm-progress { margin-top: 20px; }
    .tutor-comm-progress-head {
        display: flex; flex-wrap: wrap; align-items: baseline;
        justify-content: space-between; gap: 4px 16px;
        font-size: 12px; font-weight: 700; color: #6B7280;
    }
    .dark .tutor-comm-progress-head { color: #9CA3AF; }
    .tutor-comm-progress-head strong { color: #0C0A14; font-weight: 800; }
    .dark .tutor-comm-progress-head strong { color: #fff; }
    .tutor-comm-progress-head .tutor-comm-next { color: #7D39EB; font-weight: 800; }
    .dark .tutor-comm-progress-head .tutor-comm-next { color: #A78BFA; }
    .tutor-comm-progress .tutor-progress { margin-top: 8px; }

    .tutor-chips { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 8px; }

    .tutor-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 12px; border-radius: 9999px;
        font-size: 12px; font-weight: 700; line-height: 1;
        border: 1px solid #E5E7EB; color: #6B7280;
        background: #fff; white-space: nowrap;
    }
    .dark .tutor-chip { background: #111827; border-color: #374151; color: #9CA3AF; }
    .tutor-chip svg { width: 14px; height: 14px; }
    .tutor-chip--active {
        border-color: #7D39EB; color: #7D39EB;
        background: rgba(125, 57, 235, .07);
    }
    .dark .tutor-chip--active { color: #A78BFA; border-color: #A78BFA; background: rgba(125, 57, 235, .15); }
    .tutor-chip--done { color: #059669; border-color: #A7F3D0; }
    .dark .tutor-chip--done { color: #34D399; border-color: rgba(52, 211, 153, .3); }
    .tutor-chip-dot { width: 6px; height: 6px; border-radius: 9999px; background: #7D39EB; }

    .tutor-link {
        display: inline-flex; align-items: center; gap: 6px;
        margin-top: 20px;
        font-size: .875rem; font-weight: 700; text-decoration: none;
        color: #7D39EB;
    }
    .tutor-link:hover { text-decoration: underline; }
    .dark .tutor-link { color: #A78BFA; }
    .tutor-link svg { width: 16px; height: 16px; }

    /* ═══ Онбординг-чеклист ═══════════════════════════════════════ */

    .tutor-onb-head {
        display: flex; flex-wrap: wrap; align-items: center;
        justify-content: space-between; gap: 12px 24px;
    }
    .tutor-onb-title { margin: 4px 0 0; font-size: 1.125rem; font-weight: 800; line-height: 1.25; color: #0C0A14; }
    .dark .tutor-onb-title { color: #fff; }
    .tutor-onb-rank { margin: 2px 0 0; font-size: 12px; color: #9CA3AF; }
    .tutor-onb-head .tutor-progress { margin-top: 16px; width: 100%; order: 3; }

    .tutor-check-list { margin-top: 16px; display: grid; grid-template-columns: 1fr; gap: 10px; }
    @media (min-width: 640px) { .tutor-check-list { grid-template-columns: 1fr 1fr; gap: 10px; } }

    .tutor-check-row {
        display: flex; align-items: center; gap: 12px;
        min-height: 52px; padding: 10px 14px;
        border-radius: 16px;
        border: 1px solid #ECEEF1; background: #fff;
        text-decoration: none;
        transition: border-color .2s ease;
    }
    .dark .tutor-check-row { background: #111827; border-color: #1F2937; }
    .tutor-check-row:hover { border-color: #7D39EB; }
    .tutor-check-row--done { opacity: .62; }
    .tutor-check-row--done:hover { border-color: #ECEEF1; }

    .tutor-check-icon {
        width: 24px; height: 24px; flex: none;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 9999px;
    }
    .tutor-check-icon svg { width: 14px; height: 14px; }
    .tutor-check-icon--done { background: rgba(16, 185, 129, .14); color: #059669; }
    .tutor-check-icon--todo { background: rgba(245, 158, 11, .14); color: #D97706; }
    .dark .tutor-check-icon--todo { color: #FBBF24; }

    .tutor-check-label { font-size: .875rem; font-weight: 700; color: #1F2937; line-height: 1.3; }
    .dark .tutor-check-label { color: #E5E7EB; }
    .tutor-check-note { display: block; margin-top: 2px; font-size: 12px; font-weight: 500; color: #9CA3AF; }
    .tutor-check-chevron { margin-left: auto; width: 16px; height: 16px; flex: none; color: #D1D5DB; }

    .tutor-onb-welcome .tutor-onb-lead {
        margin: 8px 0 0; max-width: 560px;
        font-size: .875rem; line-height: 1.55; color: #6B7280;
    }
    .dark .tutor-onb-welcome .tutor-onb-lead { color: #9CA3AF; }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tutorLessonCountdown', ({ startsAt, endsAt, joinFrom, joinUntil, joinAvailable }) => ({
            now: Math.floor(Date.now() / 1000),
            hydrated: false,
            started: false,
            finished: false,
            joinAvailable: joinAvailable,

            init() {
                this.tick();
                setInterval(() => { this.now = Math.floor(Date.now() / 1000); this.tick(); }, 30000);
                this.hydrated = true;
            },

            tick() {
                this.started = this.now >= startsAt;
                this.finished = this.now > endsAt;
                this.joinAvailable = this.now >= joinFrom && this.now <= joinUntil;
            },

            get countdown() {
                const diff = startsAt - this.now;
                if (diff <= 0) return '';
                const days = Math.floor(diff / 86400);
                const hours = Math.floor((diff % 86400) / 3600);
                const minutes = Math.ceil((diff % 3600) / 60);
                if (days >= 1) return `через ${days} ${this.plural(days, 'день', 'дня', 'дней')} ${hours} ${this.plural(hours, 'час', 'часа', 'часов')}`;
                if (hours >= 1) return `через ${hours} ${this.plural(hours, 'час', 'часа', 'часов')} ${minutes} ${this.plural(minutes, 'мин', 'мин', 'мин')}`;
                if (minutes > 1) return `через ${minutes} ${this.plural(minutes, 'минуту', 'минуты', 'минут')}`;
                return 'начнётся с минуты на минуту';
            },

            plural(n, one, few, many) {
                const mod10 = n % 10, mod100 = n % 100;
                if (mod10 === 1 && mod100 !== 11) return one;
                if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return few;
                return many;
            },
        }));
    });
</script>
