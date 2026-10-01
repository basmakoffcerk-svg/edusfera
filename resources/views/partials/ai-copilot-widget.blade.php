@php
    $currentUser = auth()->user();
    if (!$currentUser) return;
    $isTutor = $currentUser->isTutor();
    $roleTitle = $isTutor ? 'ИИ-Методист' : 'ИИ-Тьютор';
    $roleBadge = $isTutor ? 'Преподавателю' : 'Ученику';
@endphp

<div id="ed-ai-copilot-root" x-data="edusferaAiCopilot()" x-cloak>
    {{-- ═══ 0. МОБИЛЬНЫЙ BACKDROP OVERLAY ═══ --}}
    <div
        class="ed-copilot-backdrop"
        x-show="isOpen"
        x-transition:enter="ed-backdrop-fade-enter"
        x-transition:enter-start="ed-opacity-0"
        x-transition:enter-end="ed-opacity-100"
        x-transition:leave="ed-backdrop-fade-leave"
        x-transition:leave-start="ed-opacity-100"
        x-transition:leave-end="ed-opacity-0"
        @click="close()"
    ></div>

    {{-- ═══ 1. ПЛАВАЮЩАЯ КНОПКА (FAB) ═══ --}}
    <button
        type="button"
        id="ai-copilot-trigger-btn"
        class="ed-copilot-fab"
        :class="{ 'ed-copilot-fab--active': isOpen }"
        @click="toggle()"
        title="Открыть ИИ-Ассистент Edusfera AI"
        aria-label="Edusfera AI"
    >
        <span class="ed-copilot-fab-glow"></span>
        <div class="ed-copilot-fab-icon">
            <template x-if="!isOpen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                </svg>
            </template>
            <template x-if="isOpen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </template>
        </div>
        <div class="ed-copilot-fab-label">
            <span class="ed-copilot-fab-pulse"></span>
            <span class="ed-copilot-fab-text">Edusfera AI</span>
        </div>
    </button>

    {{-- ═══ 2. ПЛАВАЮЩАЯ ПАНЕЛЬ ДИАЛОГА (SLIDE-OVER / DRAWER) ═══ --}}
    <div
        class="ed-copilot-panel"
        :class="{ 'ed-copilot-panel--open': isOpen }"
        x-show="isOpen"
        x-transition:enter="ed-trans-enter"
        x-transition:enter-start="ed-trans-start"
        x-transition:enter-end="ed-trans-end"
        x-transition:leave="ed-trans-leave"
        x-transition:leave-start="ed-trans-end"
        x-transition:leave-end="ed-trans-start"
        @click.outside="if (isDesktop()) close()"
    >
        {{-- ДРАГ-ХЭНДЛ ДЛЯ ВЕРТИКАЛЬНЫХ МОБИЛЬНЫХ УСТРОЙСТВ --}}
        <div class="ed-copilot-drag-handle-wrap" @click="close()" title="Закрыть">
            <span class="ed-copilot-drag-handle"></span>
        </div>

        {{-- ШАПКА АССИСТЕНТА --}}
        <div class="ed-copilot-header">
            <div class="ed-copilot-header-info">
                <div class="ed-copilot-avatar">
                    <span>✨</span>
                </div>
                <div>
                    <div class="ed-copilot-title-row">
                        <h4 class="ed-copilot-title">Edusfera AI</h4>
                        <span class="ed-copilot-badge">{{ $roleTitle }}</span>
                    </div>
                    <p class="ed-copilot-subtitle">
                        <span class="ed-copilot-status-dot"></span>
                        Edusfera AI Engine
                    </p>
                </div>
            </div>

            <div class="ed-copilot-header-actions">
                <button
                    type="button"
                    class="ed-copilot-tool-btn"
                    @click="clearHistory()"
                    title="Очистить диалог"
                    x-show="messages.length > 0"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                    </svg>
                </button>
                <button
                    type="button"
                    class="ed-copilot-tool-btn"
                    @click="close()"
                    title="Свернуть"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        {{-- БЫСТРЫЕ ЧИПСЫ-ПОДСКАЗКИ --}}
        <div class="ed-copilot-chips-wrap">
            <div class="ed-copilot-chips">
                @if($isTutor)
                    <button type="button" class="ed-copilot-chip" @click="sendPrompt('Составь подробный план 60-минутного урока для подготовки к ЦТ по математике на тему: Производная')">
                        📚 План урока ЦТ
                    </button>
                    <button type="button" class="ed-copilot-chip" @click="sendPrompt('Придумай 3 тестовые задачи в формате ЦТ 2026 части Б с ловушками РИКЗ и подробным решением')">
                        📝 Задачи с ловушкой РИКЗ
                    </button>
                    <button type="button" class="ed-copilot-chip" @click="sendPrompt('Как наглядно и понятно объяснить формулу Бернулли для ученика 10 класса?')">
                        💡 Как объяснить тему
                    </button>
                    <button type="button" class="ed-copilot-chip" @click="sendPrompt('Составь критерии быстрой проверки домашнего задания по геометрии')">
                        ✅ Критерии проверки ДЗ
                    </button>
                @else
                    <button type="button" class="ed-copilot-chip" @click="sendPrompt('Объясни мне простыми словами закон всемирного тяготения и где он применяется')">
                        ❓ Объясни формулу
                    </button>
                    <button type="button" class="ed-copilot-chip" @click="sendPrompt('Помоги разобрать по шагам решение тригонометрического уравнения: sin(2x) = cos(x)')">
                        📐 Разбор задачи
                    </button>
                    <button type="button" class="ed-copilot-chip" @click="sendPrompt('Назови топ-5 типичных ошибок и ловушек в ЦТ/ЦЭ по русскому языку в блоке орфографии')">
                        🎯 Топ ошибок ЦТ
                    </button>
                    <button type="button" class="ed-copilot-chip" @click="sendPrompt('Дай совет, как распределить время на централизованном экзамене (100 минут)')">
                        ⏱️ Тайм-менеджмент на ЦЭ
                    </button>
                @endif
            </div>
        </div>

        {{-- ОКНО СООБЩЕНИЙ --}}
        <div class="ed-copilot-messages" x-ref="messagesContainer">
            <template x-if="messages.length === 0">
                <div class="ed-copilot-empty">
                    <div class="ed-copilot-empty-orb-wrap">
                        <div class="ed-copilot-empty-orb-halo"></div>
                        <div class="ed-copilot-empty-orb">
                            <svg class="ed-copilot-orb-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M12 2L14.4 9.6L22 12L14.4 14.4L12 22L9.6 14.4L2 12L9.6 9.6L12 2Z" fill="url(#edOrbGrad)" stroke="none"/>
                                <defs>
                                    <linearGradient id="edOrbGrad" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#C6FF33"/>
                                        <stop offset="0.5" stop-color="#7D39EB"/>
                                        <stop offset="1" stop-color="#38BDF8"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                    </div>
                    <div class="ed-copilot-empty-title">
                        Чем помочь сегодня?
                    </div>
                    <p class="ed-copilot-empty-desc">
                        @if($isTutor)
                            Интеллектуальный методист по спецификациям РИКЗ 2026. Выберите готовый сценарий или задайте вопрос:
                        @else
                            Ваш персональный наставник ЦТ и ЦЭ. Выберите сценарий или спросите формулу:
                        @endif
                    </p>

                    {{-- ИНТЕРАКТИВНЫЕ ГЛАСС-КАРТОЧКИ БЫСТРОГО СТАРТА --}}
                    <div class="ed-copilot-feature-grid">
                        @if($isTutor)
                            <div class="ed-copilot-feature-card" @click="sendPrompt('Составь подробный план 60-минутного урока для подготовки к ЦТ по математике на тему: Производная')">
                                <div class="ed-copilot-card-icon">📋</div>
                                <div class="ed-copilot-card-text">
                                    <div class="ed-copilot-card-title">План урока ЦТ</div>
                                    <div class="ed-copilot-card-sub">Техкарта на 60 мин</div>
                                </div>
                            </div>
                            <div class="ed-copilot-feature-card" @click="sendPrompt('Придумай 3 тестовые задачи в формате ЦТ 2026 части Б с ловушками РИКЗ и подробным решением')">
                                <div class="ed-copilot-card-icon">🎯</div>
                                <div class="ed-copilot-card-text">
                                    <div class="ed-copilot-card-title">Ловушки РИКЗ</div>
                                    <div class="ed-copilot-card-sub">Задания части Б с капканами</div>
                                </div>
                            </div>
                            <div class="ed-copilot-feature-card" @click="sendPrompt('Как наглядно и понятно объяснить формулу Бернулли для ученика 10 класса?')">
                                <div class="ed-copilot-card-icon">💡</div>
                                <div class="ed-copilot-card-text">
                                    <div class="ed-copilot-card-title">Аналогия темы</div>
                                    <div class="ed-copilot-card-sub">Объяснение «на пальцах»</div>
                                </div>
                            </div>
                            <div class="ed-copilot-feature-card" @click="sendPrompt('Составь критерии быстрой проверки домашнего задания по геометрии')">
                                <div class="ed-copilot-card-icon">✅</div>
                                <div class="ed-copilot-card-text">
                                    <div class="ed-copilot-card-title">Критерии ДЗ</div>
                                    <div class="ed-copilot-card-sub">Чек-лист проверки</div>
                                </div>
                            </div>
                        @else
                            <div class="ed-copilot-feature-card" @click="sendPrompt('Помоги разобрать по шагам решение тригонометрического уравнения: sin(2x) = cos(x)')">
                                <div class="ed-copilot-card-icon">📐</div>
                                <div class="ed-copilot-card-text">
                                    <div class="ed-copilot-card-title">Разбор задачи</div>
                                    <div class="ed-copilot-card-sub">Пошагово с формулами</div>
                                </div>
                            </div>
                            <div class="ed-copilot-feature-card" @click="sendPrompt('Назови топ-5 типичных ошибок и ловушек в ЦТ/ЦЭ по русскому языку в блоке орфографии')">
                                <div class="ed-copilot-card-icon">🎯</div>
                                <div class="ed-copilot-card-text">
                                    <div class="ed-copilot-card-title">Ловушки ЦТ</div>
                                    <div class="ed-copilot-card-sub">Где теряют баллы</div>
                                </div>
                            </div>
                            <div class="ed-copilot-feature-card" @click="sendPrompt('Объясни мне простыми словами закон всемирного тяготения и где он применяется')">
                                <div class="ed-copilot-card-icon">💡</div>
                                <div class="ed-copilot-card-text">
                                    <div class="ed-copilot-card-title">Объясни тему</div>
                                    <div class="ed-copilot-card-sub">Просто и понятно</div>
                                </div>
                            </div>
                            <div class="ed-copilot-feature-card" @click="sendPrompt('Дай совет, как распределить время на централизованном экзамене (100 минут)')">
                                <div class="ed-copilot-card-icon">⏱️</div>
                                <div class="ed-copilot-card-text">
                                    <div class="ed-copilot-card-title">Тайминг ЦЭ/ЦТ</div>
                                    <div class="ed-copilot-card-sub">Стратегия на 85+ баллов</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </template>

            <template x-for="(msg, index) in messages" :key="index">
                <div class="ed-copilot-msg" :class="msg.role === 'user' ? 'ed-copilot-msg--user' : 'ed-copilot-msg--assistant'">
                    <div class="ed-copilot-msg-bubble">
                        <div class="ed-copilot-msg-header">
                            <span class="ed-copilot-msg-sender" x-text="msg.role === 'user' ? 'Вы' : 'Edusfera AI'"></span>
                            <template x-if="msg.role === 'assistant'">
                                <button
                                    type="button"
                                    class="ed-copilot-copy-btn"
                                    @click="copyText(msg.content, $event)"
                                    title="Скопировать ответ"
                                >
                                    <span>Копировать</span>
                                </button>
                            </template>
                        </div>
                        <div class="ed-copilot-msg-content" x-html="renderMarkdown(msg.content)"></div>
                    </div>
                </div>
            </template>

            {{-- ИНДИКАТОР ПЕЧАТИ --}}
            <div class="ed-copilot-typing" x-show="isLoading">
                <span class="ed-copilot-typing-dot"></span>
                <span class="ed-copilot-typing-dot"></span>
                <span class="ed-copilot-typing-dot"></span>
                <span class="ed-copilot-typing-text">Edusfera AI думает...</span>
            </div>
        </div>

        {{-- ПОЛЕ ВВОДА --}}
        <div class="ed-copilot-footer">
            <form @submit.prevent="submitMessage()" class="ed-copilot-form">
                <textarea
                    x-model="inputPrompt"
                    x-ref="inputField"
                    @keydown.enter.prevent="if (!$event.shiftKey) submitMessage()"
                    placeholder="{{ $isTutor ? 'Задайте методический вопрос или тему урока...' : 'Спросите о любой теме или задаче...' }}"
                    rows="1"
                    class="ed-copilot-textarea"
                    :disabled="isLoading"
                ></textarea>
                <button
                    type="submit"
                    class="ed-copilot-send-btn"
                    :disabled="isLoading || !inputPrompt.trim()"
                    title="Отправить (Enter)"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
            <div class="ed-copilot-disclaimer">
                Edusfera AI • Персональный интеллектуальный ассистент
            </div>
        </div>
    </div>
</div>

<style>
/* ─── STYLES FOR EDUSFERA UNIVERSAL AI COPILOT ─── */
#ed-ai-copilot-root {
    --copilot-accent: #7D39EB;
    --copilot-accent-hover: #6929D5;
    --copilot-accent-light: rgba(125, 57, 235, 0.12);
    --copilot-lime: #C6FF33;
    --copilot-cyan: #38BDF8;
    --copilot-bg: rgba(255, 255, 255, 0.82);
    --copilot-card-bg: rgba(255, 255, 255, 0.75);
    --copilot-border: rgba(226, 232, 240, 0.85);
    --copilot-glass-border: rgba(255, 255, 255, 0.8);
    --copilot-text: #0F172A;
    --copilot-muted: #64748B;
    --copilot-shadow: 0 24px 64px -12px rgba(125, 57, 235, 0.22), 0 0 0 1px rgba(255, 255, 255, 0.8);
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    font-family: inherit;
}

.dark #ed-ai-copilot-root {
    --copilot-bg: rgba(15, 23, 42, 0.88);
    --copilot-card-bg: rgba(30, 41, 59, 0.75);
    --copilot-border: rgba(51, 65, 85, 0.8);
    --copilot-glass-border: rgba(255, 255, 255, 0.12);
    --copilot-text: #F8FAFC;
    --copilot-muted: #94A3B8;
    --copilot-shadow: 0 28px 64px -10px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(125, 57, 235, 0.35);
}

/* FAB Button */
.ed-copilot-fab {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    height: 48px;
    padding: 0 18px 0 14px;
    border-radius: 9999px;
    background: linear-gradient(135deg, #7D39EB 0%, #4F46E5 100%);
    color: #FFFFFF;
    border: 1px solid rgba(255, 255, 255, 0.25);
    cursor: pointer;
    box-shadow: 0 10px 25px -4px rgba(125, 57, 235, 0.45), inset 0 1px 1px rgba(255, 255, 255, 0.35);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    user-select: none;
}

.ed-copilot-fab:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 14px 30px -4px rgba(125, 57, 235, 0.55);
}

.ed-copilot-fab:active {
    transform: translateY(0) scale(0.98);
}

.ed-copilot-fab--active {
    background: #0F172A;
    box-shadow: 0 8px 20px -2px rgba(0, 0, 0, 0.3);
}

.dark .ed-copilot-fab--active {
    background: #334155;
}

.ed-copilot-fab-icon {
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ed-copilot-fab-icon svg {
    width: 20px;
    height: 20px;
}

.ed-copilot-fab-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: -0.01em;
}

.ed-copilot-fab-pulse {
    width: 7px;
    height: 7px;
    border-radius: 9999px;
    background: #10B981;
    box-shadow: 0 0 8px #10B981;
    animation: edPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes edPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.85); }
}

/* Chat Drawer Panel */
.ed-copilot-panel {
    position: absolute;
    bottom: 64px;
    right: 0;
    width: 430px;
    max-width: calc(100vw - 32px);
    height: 630px;
    max-height: calc(100vh - 100px);
    border-radius: 28px;
    background:
        radial-gradient(130% 90% at 85% 0%, rgba(125, 57, 235, 0.16) 0%, rgba(198, 255, 51, 0.08) 35%, transparent 70%),
        radial-gradient(100% 70% at 10% 100%, rgba(125, 57, 235, 0.08) 0%, transparent 50%),
        rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(28px) saturate(190%);
    -webkit-backdrop-filter: blur(28px) saturate(190%);
    border: 1px solid var(--copilot-glass-border);
    box-shadow: 0 28px 64px -12px rgba(125, 57, 235, 0.2), 0 0 0 1px rgba(125, 57, 235, 0.1), inset 0 1.5px 1px 0 rgba(255, 255, 255, 0.95);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform-origin: bottom right;
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

.dark .ed-copilot-panel {
    background:
        radial-gradient(130% 90% at 85% 0%, rgba(125, 57, 235, 0.32) 0%, rgba(198, 255, 51, 0.1) 35%, transparent 70%),
        radial-gradient(100% 70% at 10% 100%, rgba(125, 57, 235, 0.15) 0%, transparent 50%),
        rgba(15, 23, 42, 0.90);
    box-shadow: 0 32px 72px -12px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(125, 57, 235, 0.3), inset 0 1px 1px 0 rgba(255, 255, 255, 0.2);
}

.ed-trans-enter {
    opacity: 0;
    transform: scale(0.92) translateY(16px);
}
.ed-trans-end {
    opacity: 1;
    transform: scale(1) translateY(0);
}
.ed-trans-leave {
    opacity: 0;
    transform: scale(0.94) translateY(12px);
}

/* Header */
.ed-copilot-header {
    padding: 14px 18px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(255, 255, 255, 0.45);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.dark .ed-copilot-header {
    background: rgba(15, 23, 42, 0.5);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.ed-copilot-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ed-copilot-avatar {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    background: linear-gradient(135deg, #7D39EB 0%, #9F55FF 50%, #C6FF33 100%);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 4px 18px rgba(125, 57, 235, 0.4), inset 0 1px 1px rgba(255, 255, 255, 0.7);
    animation: edOrbGlow 4s ease-in-out infinite alternate;
}

@keyframes edOrbGlow {
    0% { box-shadow: 0 4px 16px rgba(125, 57, 235, 0.35); }
    100% { box-shadow: 0 6px 24px rgba(198, 255, 51, 0.45); }
}

.ed-copilot-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.ed-copilot-title {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: var(--copilot-text);
    letter-spacing: -0.01em;
}

.ed-copilot-badge {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 3px 8px;
    border-radius: 8px;
    background: linear-gradient(135deg, rgba(125, 57, 235, 0.12), rgba(198, 255, 51, 0.15));
    border: 1px solid rgba(125, 57, 235, 0.25);
    color: #7D39EB;
}

.dark .ed-copilot-badge {
    background: rgba(125, 57, 235, 0.25);
    border-color: rgba(125, 57, 235, 0.4);
    color: #C6FF33;
}

.ed-copilot-subtitle {
    margin: 2px 0 0;
    font-size: 11px;
    color: var(--copilot-muted);
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

.ed-copilot-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 8px #10B981;
}

.ed-copilot-header-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Chips */
.ed-copilot-chips-wrap {
    padding: 8px 16px 8px 16px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    mask-image: linear-gradient(to right, black 85%, transparent 100%);
    -webkit-mask-image: linear-gradient(to right, black 85%, transparent 100%);
}

.ed-copilot-chips-wrap::-webkit-scrollbar {
    display: none;
}

.ed-copilot-chips {
    display: inline-flex;
    gap: 8px;
    padding-bottom: 2px;
}

.ed-copilot-chip {
    white-space: nowrap;
    font-size: 12px;
    font-weight: 600;
    padding: 7px 14px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.75);
    color: #0F172A;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(125, 57, 235, 0.18);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04), inset 0 1px 1px rgba(255, 255, 255, 0.95);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.dark .ed-copilot-chip {
    background: rgba(30, 41, 59, 0.75);
    color: #F8FAFC;
    border-color: rgba(125, 57, 235, 0.3);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.ed-copilot-chip:hover, .ed-copilot-chip:active {
    background: linear-gradient(135deg, rgba(125, 57, 235, 0.12) 0%, rgba(198, 255, 51, 0.15) 100%);
    border-color: rgba(125, 57, 235, 0.4);
    color: var(--copilot-accent);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(125, 57, 235, 0.18), inset 0 1px 1px rgba(255, 255, 255, 1);
}

/* Messages */
.ed-copilot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* Empty State: Rich Glassmorphic Hero & Scenario Cards */
.ed-copilot-empty {
    margin: auto;
    text-align: center;
    max-width: 360px;
    padding: 14px 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.ed-copilot-empty-orb-wrap {
    position: relative;
    width: 68px;
    height: 68px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ed-copilot-empty-orb-halo {
    position: absolute;
    inset: -10px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(125, 57, 235, 0.35) 0%, rgba(198, 255, 51, 0.22) 50%, transparent 75%);
    filter: blur(14px);
    animation: edPulseHalo 3.5s ease-in-out infinite;
}

@keyframes edPulseHalo {
    0%, 100% { transform: scale(1); opacity: 0.6; }
    50% { transform: scale(1.18); opacity: 1; }
}

.ed-copilot-empty-orb {
    position: relative;
    width: 56px;
    height: 56px;
    border-radius: 18px;
    background: linear-gradient(135deg, #7D39EB 0%, #5B21B6 50%, #0F172A 100%);
    box-shadow: 0 10px 28px -4px rgba(125, 57, 235, 0.5), inset 0 1.5px 1.5px rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FFFFFF;
}

.ed-copilot-orb-svg {
    width: 28px;
    height: 28px;
    animation: edSpinSlow 24s linear infinite;
}

@keyframes edSpinSlow {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.ed-copilot-empty-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--copilot-text);
    letter-spacing: -0.02em;
    margin-bottom: 6px;
}

.ed-copilot-empty-desc {
    font-size: 12.5px;
    color: var(--copilot-muted);
    line-height: 1.5;
    margin: 0 0 16px 0;
    padding: 0 6px;
}

.ed-copilot-feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    width: 100%;
}

.ed-copilot-feature-card {
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 16px;
    padding: 11px 12px;
    display: flex;
    align-items: flex-start;
    gap: 9px;
    text-align: left;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.95);
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.dark .ed-copilot-feature-card {
    background: rgba(30, 41, 59, 0.72);
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.ed-copilot-feature-card:hover, .ed-copilot-feature-card:active {
    transform: translateY(-2px);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(245, 243, 255, 0.95) 100%);
    border-color: rgba(125, 57, 235, 0.35);
    box-shadow: 0 10px 24px -4px rgba(125, 57, 235, 0.18), inset 0 1px 0 rgba(255, 255, 255, 1);
}

.dark .ed-copilot-feature-card:hover, .dark .ed-copilot-feature-card:active {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(51, 65, 85, 0.95) 100%);
    border-color: rgba(125, 57, 235, 0.5);
    box-shadow: 0 10px 24px -4px rgba(125, 57, 235, 0.3);
}

.ed-copilot-card-icon {
    font-size: 18px;
    flex-shrink: 0;
    margin-top: 1px;
}

.ed-copilot-card-text {
    flex: 1;
    min-width: 0;
}

.ed-copilot-card-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--copilot-text);
    line-height: 1.3;
    margin-bottom: 2px;
}

.ed-copilot-card-sub {
    font-size: 10px;
    color: var(--copilot-muted);
    line-height: 1.3;
}

/* Chat Bubbles */
.ed-copilot-msg {
    display: flex;
    width: 100%;
}

.ed-copilot-msg--user {
    justify-content: flex-end;
}

.ed-copilot-msg--assistant {
    justify-content: flex-start;
}

.ed-copilot-msg-bubble {
    max-width: 88%;
    border-radius: 18px;
    padding: 12px 16px;
    font-size: 13.5px;
    line-height: 1.55;
}

.ed-copilot-msg--user .ed-copilot-msg-bubble {
    background: linear-gradient(135deg, #7D39EB 0%, #5B21B6 100%);
    color: #FFFFFF;
    border-bottom-right-radius: 4px;
    box-shadow: 0 4px 18px rgba(125, 57, 235, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.ed-copilot-msg--assistant .ed-copilot-msg-bubble {
    background: rgba(255, 255, 255, 0.82);
    color: var(--copilot-text);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-bottom-left-radius: 4px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.95);
}

.dark .ed-copilot-msg--assistant .ed-copilot-msg-bubble {
    background: rgba(30, 41, 59, 0.8);
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.ed-copilot-msg-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
    font-size: 11px;
    opacity: 0.8;
}

.ed-copilot-msg--user .ed-copilot-msg-header {
    color: rgba(255, 255, 255, 0.8);
}

.ed-copilot-msg--assistant .ed-copilot-msg-header {
    color: var(--copilot-muted);
}

.ed-copilot-copy-btn {
    background: none;
    border: none;
    padding: 0;
    font-size: 10px;
    font-weight: 700;
    color: var(--copilot-accent);
    cursor: pointer;
    text-transform: uppercase;
}

.ed-copilot-copy-btn:hover {
    text-decoration: underline;
}

.ed-copilot-msg-content {
    word-break: break-word;
}

.ed-copilot-msg-content p {
    margin: 0 0 6px;
}

.ed-copilot-msg-content p:last-child {
    margin-bottom: 0;
}

.ed-copilot-msg-content ul, .ed-copilot-msg-content ol {
    margin: 4px 0 8px 18px;
    padding: 0;
}

.ed-copilot-msg-content li {
    margin-bottom: 3px;
}

.ed-copilot-msg-content code {
    padding: 2px 5px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
    background: rgba(0, 0, 0, 0.06);
}

.dark .ed-copilot-msg-content code {
    background: rgba(255, 255, 255, 0.1);
}

.ed-copilot-msg--user .ed-copilot-msg-content code {
    background: rgba(255, 255, 255, 0.2);
    color: #FFFFFF;
}

/* Typing Indicator */
.ed-copilot-typing {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.95);
    width: fit-content;
}

.dark .ed-copilot-typing {
    background: rgba(30, 41, 59, 0.75);
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}

.ed-copilot-typing-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--copilot-accent);
    animation: edBounce 1.2s infinite ease-in-out;
}

.ed-copilot-typing-dot:nth-child(2) { animation-delay: 0.2s; }
.ed-copilot-typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes edBounce {
    0%, 80%, 100% { transform: translateY(0); opacity: 0.4; }
    40% { transform: translateY(-4px); opacity: 1; }
}

.ed-copilot-typing-text {
    font-size: 11px;
    color: var(--copilot-muted);
    margin-left: 4px;
    font-weight: 600;
}

/* Footer & Form (Floating Glass Capsule) */
.ed-copilot-footer {
    padding: 12px 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.7);
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.dark .ed-copilot-footer {
    background: rgba(15, 23, 42, 0.75);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.ed-copilot-form {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(125, 57, 235, 0.22);
    border-radius: 20px;
    padding: 5px 7px 5px 14px;
    box-shadow: 0 4px 18px rgba(125, 57, 235, 0.08), inset 0 1px 1px #FFFFFF;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.dark .ed-copilot-form {
    background: rgba(30, 41, 59, 0.85);
    border-color: rgba(125, 57, 235, 0.35);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.ed-copilot-form:focus-within {
    border-color: var(--copilot-accent);
    box-shadow: 0 0 0 3px rgba(125, 57, 235, 0.18), 0 8px 24px rgba(125, 57, 235, 0.16);
    background: #FFFFFF;
}

.dark .ed-copilot-form:focus-within {
    background: rgba(30, 41, 59, 0.98);
}

.ed-copilot-textarea {
    flex: 1;
    border: none;
    background: transparent;
    padding: 7px 0;
    font-size: 13.5px;
    color: var(--copilot-text);
    resize: none;
    outline: none;
    max-height: 100px;
    line-height: 1.45;
}

.ed-copilot-textarea::placeholder {
    color: var(--copilot-muted);
}

.ed-copilot-send-btn {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #7D39EB 0%, #4F46E5 100%);
    color: #FFFFFF;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(125, 57, 235, 0.45);
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.ed-copilot-send-btn:hover:not(:disabled) {
    transform: scale(1.06);
    box-shadow: 0 6px 20px rgba(125, 57, 235, 0.55);
}

.ed-copilot-send-btn:active:not(:disabled) {
    transform: scale(0.96);
}

.ed-copilot-send-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
    box-shadow: none;
}

.ed-copilot-send-btn svg {
    width: 16px;
    height: 16px;
}

.ed-copilot-disclaimer {
    margin-top: 6px;
    text-align: center;
    font-size: 10.5px;
    color: var(--copilot-muted);
    opacity: 0.85;
    font-weight: 500;
}

/* Drag Handle */
.ed-copilot-drag-handle-wrap {
    display: none;
}

/* Mobile backdrop */
.ed-copilot-backdrop {
    display: none;
}
.ed-backdrop-fade-enter {
    transition: opacity 0.25s ease-out;
}
.ed-backdrop-fade-leave {
    transition: opacity 0.2s ease-in;
}
.ed-opacity-0 {
    opacity: 0;
}
.ed-opacity-100 {
    opacity: 1;
}

/* ═══ VERTICAL MOBILE ADAPTATION (iPhone, Android <= 768px) ═══ */
@media (max-width: 768px) {
    .ed-copilot-backdrop {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        z-index: 99998;
    }

    #ed-ai-copilot-root {
        bottom: max(16px, env(safe-area-inset-bottom, 16px));
        right: 16px;
    }

    .ed-copilot-fab {
        height: 50px;
        padding: 0 16px 0 12px;
        box-shadow: 0 10px 28px -4px rgba(125, 57, 235, 0.5);
    }

    .ed-copilot-panel {
        position: fixed !important;
        inset: auto 0 0 0 !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
        height: min(92dvh, calc(100vh - 24px)) !important;
        max-height: 92dvh !important;
        border-radius: 32px 32px 0 0 !important;
        border-bottom: none !important;
        border-left: none !important;
        border-right: none !important;
        border-top: 1px solid rgba(255, 255, 255, 0.85) !important;
        background:
            radial-gradient(120% 80% at 85% 0%, rgba(125, 57, 235, 0.16) 0%, rgba(198, 255, 51, 0.08) 35%, transparent 70%),
            radial-gradient(100% 60% at 10% 90%, rgba(125, 57, 235, 0.07) 0%, transparent 50%),
            rgba(255, 255, 255, 0.88) !important;
        backdrop-filter: blur(32px) saturate(200%) !important;
        -webkit-backdrop-filter: blur(32px) saturate(200%) !important;
        box-shadow: 0 -16px 54px -6px rgba(15, 23, 42, 0.28), 0 0 0 1px rgba(125, 57, 235, 0.15), inset 0 1.5px 0 0 rgba(255, 255, 255, 0.95) !important;
        z-index: 99999 !important;
        transform-origin: bottom center !important;
    }

    .dark .ed-copilot-panel {
        background:
            radial-gradient(120% 80% at 85% 0%, rgba(125, 57, 235, 0.32) 0%, rgba(198, 255, 51, 0.1) 35%, transparent 70%),
            radial-gradient(100% 60% at 10% 90%, rgba(125, 57, 235, 0.15) 0%, transparent 50%),
            rgba(15, 23, 42, 0.92) !important;
        border-top: 1px solid rgba(255, 255, 255, 0.15) !important;
        box-shadow: 0 -16px 54px -6px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(125, 57, 235, 0.3), inset 0 1.5px 0 0 rgba(255, 255, 255, 0.2) !important;
    }

    .ed-trans-enter {
        opacity: 0;
        transform: translateY(100%);
    }
    .ed-trans-end {
        opacity: 1;
        transform: translateY(0);
    }
    .ed-trans-leave {
        opacity: 0;
        transform: translateY(100%);
    }

    .ed-copilot-drag-handle-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 12px 0 6px 0;
        width: 100%;
        cursor: pointer;
        touch-action: none;
    }

    .ed-copilot-drag-handle {
        width: 48px;
        height: 5px;
        border-radius: 9999px;
        background: rgba(125, 57, 235, 0.25);
        transition: all 0.2s ease;
    }

    .ed-copilot-drag-handle-wrap:active .ed-copilot-drag-handle {
        background: var(--copilot-accent);
        transform: scaleX(1.15);
    }

    .ed-copilot-header {
        padding: 8px 16px 12px 16px;
    }

    .ed-copilot-avatar {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        font-size: 16px;
    }

    .ed-copilot-title {
        font-size: 15px;
    }

    .ed-copilot-badge {
        font-size: 9.5px;
        padding: 2.5px 7px;
    }

    .ed-copilot-header-actions .ed-copilot-tool-btn {
        width: 38px;
        height: 38px;
        border-radius: 12px;
    }

    .ed-copilot-chips-wrap {
        padding: 6px 14px 6px 14px;
        background: rgba(125, 57, 235, 0.03);
    }

    .ed-copilot-chips {
        gap: 8px;
        padding-bottom: 4px;
        -webkit-overflow-scrolling: touch;
        scroll-snap-type: x mandatory;
    }

    .ed-copilot-chip {
        scroll-snap-align: start;
        padding: 8px 15px;
        font-size: 12.5px;
        min-height: 38px;
        border-radius: 14px;
        flex-shrink: 0;
    }

    .ed-copilot-chip:active {
        transform: scale(0.96);
    }

    .ed-copilot-messages {
        padding: 12px 14px;
        gap: 12px;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
    }

    .ed-copilot-msg-bubble {
        max-width: 90%;
        padding: 12px 15px;
        border-radius: 18px;
        font-size: 14px;
        line-height: 1.55;
    }

    .ed-copilot-footer {
        padding: 8px 12px max(12px, env(safe-area-inset-bottom, 12px)) 12px;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
    }

    .dark .ed-copilot-footer {
        background: rgba(15, 23, 42, 0.9);
    }

    .ed-copilot-form {
        padding: 4px 6px 4px 14px;
        border-radius: 20px;
    }

    .ed-copilot-textarea {
        font-size: 16px !important; /* Critical to prevent iOS Safari auto-zoom */
        padding: 8px 0;
        max-height: 90px;
        line-height: 1.35;
    }

    .ed-copilot-send-btn {
        width: 38px;
        height: 38px;
        border-radius: 14px;
    }
}
</style>

<script>
function edusferaAiCopilot() {
    return {
        isOpen: false,
        isLoading: false,
        inputPrompt: '',
        messages: [],

        init() {
            // Restore conversation from session storage if present
            try {
                const saved = sessionStorage.getItem('ed_copilot_messages');
                if (saved) {
                    this.messages = JSON.parse(saved);
                }
            } catch (e) {}
        },

        isDesktop() {
            return window.innerWidth > 640;
        },

        toggle() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.scrollToBottom();
                    if (this.$refs.inputField) this.$refs.inputField.focus();
                });
            }
        },

        close() {
            this.isOpen = false;
        },

        clearHistory() {
            this.messages = [];
            try { sessionStorage.removeItem('ed_copilot_messages'); } catch (e) {}
        },

        sendPrompt(text) {
            this.inputPrompt = text;
            this.submitMessage();
        },

        async submitMessage() {
            const prompt = this.inputPrompt.trim();
            if (!prompt || this.isLoading) return;

            this.inputPrompt = '';
            this.messages.push({ role: 'user', content: prompt });
            this.isLoading = true;
            this.$nextTick(() => this.scrollToBottom());

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || (window.filamentData && window.filamentData.csrfToken) || '';

                const res = await fetch('/admin/ai-copilot/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        prompt: prompt,
                        history: this.messages.slice(-8)
                    })
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.reply) {
                        this.messages.push({ role: 'assistant', content: data.reply });
                    } else {
                        this.messages.push({ role: 'assistant', content: 'Ответ получен, но он пустой. Попробуйте еще раз.' });
                    }
                } else {
                    const err = await res.json().catch(() => ({}));
                    this.messages.push({
                        role: 'assistant',
                        content: err.error || 'Произошла ошибка при обращении к Edusfera AI. Попробуйте еще раз.'
                    });
                }
            } catch (e) {
                this.messages.push({
                    role: 'assistant',
                    content: 'Не удалось связаться с сервером ИИ. Проверьте интернет-соединение.'
                });
            } finally {
                this.isLoading = false;
                try {
                    sessionStorage.setItem('ed_copilot_messages', JSON.stringify(this.messages.slice(-20)));
                } catch (e) {}
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        copyText(text, event) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    const btn = event.target;
                    const orig = btn.innerText;
                    btn.innerText = 'Скопировано! ✓';
                    setTimeout(() => { btn.innerText = orig; }, 2000);
                });
            }
        },

        renderMarkdown(text) {
            if (!text) return '';
            let html = String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Headers
            html = html.replace(/^### (.*$)/gim, '<div style="font-weight:800; font-size:13px; margin:6px 0 3px;">$1</div>');
            html = html.replace(/^## (.*$)/gim, '<div style="font-weight:800; font-size:14px; margin:8px 0 4px; color:var(--copilot-accent);">$1</div>');
            html = html.replace(/^# (.*$)/gim, '<div style="font-weight:900; font-size:15px; margin:10px 0 4px;">$1</div>');

            // Bold & Italic
            html = html.replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>');
            html = html.replace(/\*(.*?)\*/gim, '<em>$1</em>');

            // Code
            html = html.replace(/```([\s\S]*?)```/gim, '<pre style="background:rgba(0,0,0,0.06); padding:8px; border-radius:8px; font-size:11px; overflow-x:auto;"><code>$1</code></pre>');
            html = html.replace(/`([^`]+)`/gim, '<code>$1</code>');

            // Bullet lists
            html = html.replace(/^\s*-\s+(.*$)/gim, '• $1<br>');
            html = html.replace(/^\s*\*\s+(.*$)/gim, '• $1<br>');

            // Line breaks
            html = html.replace(/\n/g, '<br>');

            return html;
        }
    };
}
</script>
