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
                    <div class="ed-copilot-empty-icon">💡</div>
                    <div class="ed-copilot-empty-title">Чем помочь сегодня?</div>
                    <p class="ed-copilot-empty-desc">
                        @if($isTutor)
                            Спросите методический совет, попросите составить конспект, тест РИКЗ или расписать план занятия.
                        @else
                            Задайте любой вопрос по школьной программе, попросите разобрать задачу или объяснить формулу.
                        @endif
                    </p>
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
    --copilot-bg: rgba(255, 255, 255, 0.95);
    --copilot-card-bg: #FFFFFF;
    --copilot-border: rgba(226, 232, 240, 0.9);
    --copilot-text: #0F172A;
    --copilot-muted: #64748B;
    --copilot-shadow: 0 20px 48px -8px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(125, 57, 235, 0.08);
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    font-family: inherit;
}

.dark #ed-ai-copilot-root {
    --copilot-bg: rgba(15, 23, 42, 0.94);
    --copilot-card-bg: #1E293B;
    --copilot-border: rgba(51, 65, 85, 0.8);
    --copilot-text: #F8FAFC;
    --copilot-muted: #94A3B8;
    --copilot-shadow: 0 24px 54px -10px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(125, 57, 235, 0.25);
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
    border: none;
    cursor: pointer;
    box-shadow: 0 10px 25px -4px rgba(125, 57, 235, 0.45);
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
    bottom: 60px;
    right: 0;
    width: 420px;
    max-width: calc(100vw - 32px);
    height: 600px;
    max-height: calc(100vh - 100px);
    border-radius: 24px;
    background: var(--copilot-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--copilot-border);
    box-shadow: var(--copilot-shadow);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform-origin: bottom right;
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
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
    padding: 16px 18px;
    border-bottom: 1px solid var(--copilot-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(125, 57, 235, 0.04);
}

.ed-copilot-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ed-copilot-avatar {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: linear-gradient(135deg, #7D39EB, #4F46E5);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 4px 12px rgba(125, 57, 235, 0.3);
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
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 2px 7px;
    border-radius: 6px;
    background: var(--copilot-accent-light);
    color: var(--copilot-accent);
}

.ed-copilot-subtitle {
    margin: 2px 0 0;
    font-size: 11px;
    color: var(--copilot-muted);
    display: flex;
    align-items: center;
    gap: 6px;
}

.ed-copilot-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10B981;
}

.ed-copilot-header-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ed-copilot-tool-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: var(--copilot-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.ed-copilot-tool-btn:hover {
    background: rgba(0, 0, 0, 0.05);
    color: var(--copilot-text);
}

.dark .ed-copilot-tool-btn:hover {
    background: rgba(255, 255, 255, 0.08);
}

.ed-copilot-tool-btn svg {
    width: 16px;
    height: 16px;
}

/* Chips */
.ed-copilot-chips-wrap {
    padding: 10px 14px 6px;
    border-bottom: 1px solid var(--copilot-border);
    background: rgba(0, 0, 0, 0.01);
}

.ed-copilot-chips {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: none;
}

.ed-copilot-chips::-webkit-scrollbar {
    display: none;
}

.ed-copilot-chip {
    white-space: nowrap;
    font-size: 11px;
    font-weight: 600;
    padding: 5px 10px;
    border-radius: 9999px;
    background: var(--copilot-card-bg);
    color: var(--copilot-text);
    border: 1px solid var(--copilot-border);
    cursor: pointer;
    transition: all 0.15s ease;
}

.ed-copilot-chip:hover {
    border-color: var(--copilot-accent);
    color: var(--copilot-accent);
    background: var(--copilot-accent-light);
}

/* Messages */
.ed-copilot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.ed-copilot-empty {
    margin: auto;
    text-align: center;
    max-width: 280px;
    padding: 24px 0;
}

.ed-copilot-empty-icon {
    font-size: 32px;
    margin-bottom: 8px;
}

.ed-copilot-empty-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--copilot-text);
    margin-bottom: 6px;
}

.ed-copilot-empty-desc {
    font-size: 12px;
    color: var(--copilot-muted);
    line-height: 1.45;
    margin: 0;
}

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
    border-radius: 16px;
    padding: 12px 14px;
    font-size: 13px;
    line-height: 1.5;
}

.ed-copilot-msg--user .ed-copilot-msg-bubble {
    background: linear-gradient(135deg, #7D39EB, #6929D5);
    color: #FFFFFF;
    border-bottom-right-radius: 4px;
}

.ed-copilot-msg--assistant .ed-copilot-msg-bubble {
    background: var(--copilot-card-bg);
    color: var(--copilot-text);
    border: 1px solid var(--copilot-border);
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
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
    gap: 5px;
    padding: 8px 12px;
    border-radius: 12px;
    background: var(--copilot-card-bg);
    border: 1px solid var(--copilot-border);
    width: fit-content;
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

/* Footer & Form */
.ed-copilot-footer {
    padding: 12px 14px;
    border-top: 1px solid var(--copilot-border);
    background: var(--copilot-card-bg);
}

.ed-copilot-form {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--copilot-bg);
    border: 1px solid var(--copilot-border);
    border-radius: 16px;
    padding: 4px 6px 4px 12px;
    transition: all 0.2s ease;
}

.ed-copilot-form:focus-within {
    border-color: var(--copilot-accent);
    box-shadow: 0 0 0 3px var(--copilot-accent-light);
}

.ed-copilot-textarea {
    flex: 1;
    border: none;
    background: transparent;
    padding: 6px 0;
    font-size: 13px;
    color: var(--copilot-text);
    resize: none;
    outline: none;
    max-height: 100px;
    line-height: 1.4;
}

.ed-copilot-textarea::placeholder {
    color: var(--copilot-muted);
}

.ed-copilot-send-btn {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: none;
    background: var(--copilot-accent);
    color: #FFFFFF;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.ed-copilot-send-btn:hover:not(:disabled) {
    background: var(--copilot-accent-hover);
    transform: scale(1.05);
}

.ed-copilot-send-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.ed-copilot-send-btn svg {
    width: 16px;
    height: 16px;
}

.ed-copilot-disclaimer {
    margin-top: 6px;
    text-align: center;
    font-size: 10px;
    color: var(--copilot-muted);
    opacity: 0.8;
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

/* ═══ VERTICAL MOBILE ADAPTATION (iPhone, Android, Tablets <= 768px) ═══ */
@media (max-width: 768px) {
    .ed-copilot-backdrop {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.72);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        z-index: 99998;
    }

    #ed-ai-copilot-root {
        bottom: max(16px, env(safe-area-inset-bottom, 16px));
        right: 16px;
    }

    .ed-copilot-fab {
        height: 48px;
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
        border-radius: 28px 28px 0 0 !important;
        border-bottom: none !important;
        border-left: none !important;
        border-right: none !important;
        box-shadow: 0 -12px 48px -4px rgba(0, 0, 0, 0.55), 0 0 0 1px rgba(125, 57, 235, 0.2) !important;
        z-index: 99999 !important;
        transform-origin: bottom center !important;
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
        padding: 10px 0 4px 0;
        width: 100%;
        cursor: pointer;
        touch-action: none;
    }

    .ed-copilot-drag-handle {
        width: 44px;
        height: 5px;
        border-radius: 9999px;
        background: rgba(148, 163, 184, 0.45);
        transition: background 0.2s ease;
    }

    .ed-copilot-drag-handle-wrap:active .ed-copilot-drag-handle {
        background: var(--copilot-accent);
    }

    .ed-copilot-header {
        padding: 8px 16px 12px 16px;
    }

    .ed-copilot-avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        font-size: 16px;
    }

    .ed-copilot-title {
        font-size: 15px;
    }

    .ed-copilot-badge {
        font-size: 9.5px;
        padding: 2px 6px;
    }

    .ed-copilot-header-actions .ed-copilot-tool-btn {
        width: 38px;
        height: 38px;
        border-radius: 10px;
    }

    .ed-copilot-chips-wrap {
        padding: 8px 14px 6px 14px;
        background: rgba(125, 57, 235, 0.03);
    }

    .ed-copilot-chips {
        gap: 6px;
        padding-bottom: 4px;
        -webkit-overflow-scrolling: touch;
        scroll-snap-type: x mandatory;
    }

    .ed-copilot-chip {
        scroll-snap-align: start;
        padding: 8px 14px;
        font-size: 12.5px;
        min-height: 36px;
        border-radius: 12px;
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
        padding: 12px 14px;
        border-radius: 18px;
        font-size: 14px;
        line-height: 1.55;
    }

    .ed-copilot-footer {
        padding: 8px 12px max(12px, env(safe-area-inset-bottom, 12px)) 12px;
        background: var(--copilot-card-bg);
    }

    .ed-copilot-form {
        padding: 4px 6px 4px 12px;
        border-radius: 20px;
    }

    .ed-copilot-textarea {
        font-size: 16px !important; /* Critical to prevent iOS Safari auto-zoom */
        padding: 7px 0;
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
