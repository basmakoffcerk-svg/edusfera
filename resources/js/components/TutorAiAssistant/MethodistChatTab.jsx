import React, { useState, useRef, useEffect } from 'react';
import { 
    MessageSquare, 
    Send, 
    Sparkles, 
    Trash2, 
    Bot, 
    User, 
    SlidersHorizontal, 
    ChevronDown, 
    CornerDownLeft, 
    Copy, 
    Check, 
    Lightbulb, 
    RotateCcw 
} from 'lucide-react';
import { formatAiMarkdown } from './AiFormattedOutput';

const PERSONAS = [
    { id: 'methodologist', label: '🎓 ИИ-Методист', desc: 'Технологические карты занятий и дидактика' },
    { id: 'rikz_expert', label: '🎯 Эксперт РИКЗ', desc: 'Спецификации, ловушки и критерии ЦТ/ЦЭ 2026' },
    { id: 'express_solver', label: '⚡ Экспресс-решатель', desc: 'Строгое пошаговое аналитическое решение задач' },
    { id: 'simple_analogy', label: '💡 Наглядные аналогии', desc: 'Объяснение сложных концепций «на пальцах»' },
    { id: 'homework_gen', label: '📋 Генератор ДЗ', desc: 'Дифференцированные задания с ключами' },
];

const TONES = [
    { id: 'friendly', label: 'Дружелюбный' },
    { id: 'academic', label: 'Академический' },
    { id: 'concise', label: 'Краткий' },
    { id: 'step_by_step', label: 'Пошаговый' },
];

const LENGTHS = [
    { id: 'balanced', label: 'Сбалансированный' },
    { id: 'short', label: 'Краткий' },
    { id: 'detailed', label: 'Развернутый' },
];

const STARTER_PROMPTS = [
    'Как наглядно объяснить формулу Бернулли для 10 класса без зубрежки?',
    'Дай 3 олимпиадные задачи по планиметрии на свойство биссектрисы с решениями.',
    'Какие 5 главных ловушек составителей РИКЗ в ЦТ по русскому языку в блоке орфографии?',
    'Помоги составить план дифференцированного ДЗ по стереометрии на 3 уровня сложности.',
];

export default function MethodistChatTab({ csrfToken, endpoints, user, onOpenLibrary }) {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);

    // Settings
    const [persona, setPersona] = useState('methodologist');
    const [tone, setTone] = useState('friendly');
    const [length, setLength] = useState('balanced');
    const [showSettings, setShowSettings] = useState(false);

    const [copiedIndex, setCopiedIndex] = useState(null);
    const messagesEndRef = useRef(null);
    const textareaRef = useRef(null);

    // Auto-scroll on new message
    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages, isLoading]);

    // Auto-resize textarea
    useEffect(() => {
        if (textareaRef.current) {
            textareaRef.current.style.height = 'auto';
            textareaRef.current.style.height = `${Math.min(textareaRef.current.scrollHeight, 180)}px`;
        }
    }, [input]);

    // Load chat history from localStorage
    useEffect(() => {
        try {
            const saved = localStorage.getItem('edusfera_ai_chat_history');
            if (saved) {
                setMessages(JSON.parse(saved));
            }
        } catch (e) {
            console.warn('Failed to load chat history:', e);
        }
    }, []);

    const saveMessages = (newMsgs) => {
        setMessages(newMsgs);
        try {
            localStorage.setItem('edusfera_ai_chat_history', JSON.stringify(newMsgs));
        } catch (e) {
            console.warn('Failed to save chat history:', e);
        }
    };

    const clearChat = () => {
        setMessages([]);
        localStorage.removeItem('edusfera_ai_chat_history');
    };

    const handleSendMessage = async (textToSend = null) => {
        const text = (textToSend || input).trim();
        if (!text || isLoading) return;

        const userMsg = {
            id: Date.now(),
            role: 'user',
            content: text,
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        };

        const updatedMessages = [...messages, userMsg];
        saveMessages(updatedMessages);
        setInput('');
        if (textareaRef.current) textareaRef.current.style.height = 'auto';
        setIsLoading(true);

        try {
            const endpoint = endpoints?.chat || '/admin/ai-copilot/chat';
            const historyPayload = messages.slice(-10).map((m) => ({
                role: m.role === 'assistant' ? 'assistant' : 'user',
                content: m.content,
            }));

            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''),
                },
                body: JSON.stringify({
                    prompt: text,
                    history: historyPayload,
                    persona,
                    tone,
                    length,
                }),
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Не удалось получить ответ ИИ.');
            }

            const aiMsg = {
                id: Date.now() + 1,
                role: 'assistant',
                content: data.reply,
                timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                persona,
            };

            saveMessages([...updatedMessages, aiMsg]);
        } catch (err) {
            console.error('Chat error:', err);
            const errorMsg = {
                id: Date.now() + 1,
                role: 'assistant',
                content: `⚠️ **Ошибка генерации:** ${err.message || 'Сервер временно недоступен. Попробуйте еще раз через несколько секунд.'}`,
                timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                isError: true,
            };
            saveMessages([...updatedMessages, errorMsg]);
        } finally {
            setIsLoading(false);
        }
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    };

    const handleCopy = async (content, idx) => {
        try {
            await navigator.clipboard.writeText(content);
            setCopiedIndex(idx);
            setTimeout(() => setCopiedIndex(null), 2000);
        } catch (err) {
            console.error('Failed to copy message:', err);
        }
    };

    return (
        <div className="ed-ai-card flex flex-col h-[740px] max-h-[82vh] overflow-hidden p-0">
            {/* Header */}
            <div className="px-6 py-4 border-b border-[#ECEEF1] dark:border-slate-800 bg-[#FAF8FF] dark:bg-slate-800/40 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-2xl bg-[#7D39EB] text-white flex items-center justify-center shadow-xs">
                        <MessageSquare className="w-4 h-4 text-[#C6FF33]" />
                    </div>
                    <div>
                        <h3 className="font-extrabold text-xs text-[#0C0A14] dark:text-white flex items-center gap-2 tracking-wide uppercase">
                            <span>Диалог с ИИ-Методистом Edusfera</span>
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                        </h3>
                        <p className="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                            Режим: <span className="font-bold text-[#7D39EB] dark:text-violet-400">{PERSONAS.find(p => p.id === persona)?.label}</span>
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-2 ml-auto">
                    {/* Settings Toggler */}
                    <button
                        type="button"
                        onClick={() => setShowSettings(!showSettings)}
                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold transition-all cursor-pointer ${
                            showSettings
                                ? 'bg-violet-100 dark:bg-violet-900/50 text-[#7D39EB] dark:text-violet-300 border-[#7D39EB]'
                                : 'border-[#ECEEF1] dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50'
                        }`}
                        title="Настройки персоны и тона"
                    >
                        <SlidersHorizontal className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Параметры</span>
                        <ChevronDown className={`w-3 h-3 transition-transform ${showSettings ? 'rotate-180' : ''}`} />
                    </button>

                    {/* Prompt Library */}
                    <button
                        type="button"
                        onClick={onOpenLibrary}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-violet-50 dark:bg-violet-950/40 text-[#7D39EB] dark:text-violet-400 hover:bg-violet-100 text-xs font-extrabold transition-colors cursor-pointer"
                    >
                        <Sparkles className="w-3.5 h-3.5 text-violet-600" />
                        <span className="hidden sm:inline">Каталог промптов</span>
                    </button>

                    {/* Clear Chat */}
                    {messages.length > 0 && (
                        <button
                            type="button"
                            onClick={clearChat}
                            className="p-1.5 rounded-xl border border-[#ECEEF1] dark:border-slate-700 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                            title="Очистить историю диалога"
                        >
                            <Trash2 className="w-3.5 h-3.5" />
                        </button>
                    )}
                </div>
            </div>

            {/* Expandable Settings Bar */}
            {showSettings && (
                <div className="p-4 border-b border-[#ECEEF1] dark:border-slate-800 bg-[#FAF8FF]/60 dark:bg-slate-800/80 space-y-3">
                    <div>
                        <label className="block text-[10.5px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Педагогическая роль (Персона)
                        </label>
                        <div className="flex flex-wrap gap-1.5">
                            {PERSONAS.map((p) => (
                                <button
                                    key={p.id}
                                    type="button"
                                    onClick={() => setPersona(p.id)}
                                    className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                                        persona === p.id
                                            ? 'bg-[#7D39EB] text-white shadow-xs'
                                            : 'bg-white dark:bg-slate-700/80 text-slate-700 dark:text-slate-300 border border-[#ECEEF1] dark:border-slate-600 hover:bg-slate-50'
                                    }`}
                                >
                                    {p.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label className="block text-[10.5px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Тональность ответа
                            </label>
                            <div className="flex gap-1">
                                {TONES.map((t) => (
                                    <button
                                        key={t.id}
                                        type="button"
                                        onClick={() => setTone(t.id)}
                                        className={`flex-1 py-1.5 px-2 rounded-xl text-[11px] font-bold transition-all cursor-pointer ${
                                            tone === t.id
                                                ? 'bg-[#0C0A14] text-white dark:bg-white dark:text-slate-900 shadow-2xs'
                                                : 'bg-white dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 border border-[#ECEEF1] dark:border-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        {t.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-[10.5px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                                Объем ответа
                            </label>
                            <div className="flex gap-1">
                                {LENGTHS.map((l) => (
                                    <button
                                        key={l.id}
                                        type="button"
                                        onClick={() => setLength(l.id)}
                                        className={`flex-1 py-1.5 px-2 rounded-xl text-[11px] font-bold transition-all cursor-pointer ${
                                            length === l.id
                                                ? 'bg-[#0C0A14] text-white dark:bg-white dark:text-slate-900 shadow-2xs'
                                                : 'bg-white dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 border border-[#ECEEF1] dark:border-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        {l.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Chat Messages Scroll Canvas */}
            <div className="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 bg-[#FAF8FF]/30 dark:bg-slate-900/50">
                {messages.length === 0 ? (
                    <div className="m-auto max-w-lg text-center py-10 space-y-5">
                        {/* Animated SVG Mascot Illustration */}
                        <div className="relative w-24 h-24 mx-auto flex items-center justify-center">
                            {/* Rotating Orbit */}
                            <svg className="absolute inset-0 w-full h-full ed-animate-spin-slow" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="44" fill="none" stroke="#7D39EB" strokeWidth="1.5" strokeDasharray="5 7" strokeOpacity="0.4" />
                                <circle cx="94" cy="50" r="4.5" fill="#C6FF33" />
                            </svg>
                            {/* Floating Mascot Card */}
                            <div className="ed-animate-float w-16 h-16 rounded-2xl bg-gradient-to-tr from-[#7D39EB] to-[#632cd6] text-white flex items-center justify-center shadow-lg shadow-violet-500/25">
                                <svg width="34" height="34" viewBox="0 0 64 64" fill="none">
                                    <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                                    <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                                </svg>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <h4 className="font-extrabold text-sm text-[#0C0A14] dark:text-white uppercase tracking-wide">
                                Задайте методический или практический вопрос
                            </h4>
                            <p className="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                ИИ-Методист поможет составить сценарий урока, разберет нестандартную задачу или проверит критерии РИКЗ.
                            </p>
                        </div>

                        {/* Starter Chips */}
                        <div className="pt-2 flex flex-col gap-2">
                            {STARTER_PROMPTS.map((promptText, i) => (
                                <button
                                    key={i}
                                    type="button"
                                    onClick={() => handleSendMessage(promptText)}
                                    className="p-3.5 rounded-2xl bg-white dark:bg-slate-800 hover:bg-[#FAF8FF] dark:hover:bg-violet-950/40 border border-[#ECEEF1] dark:border-slate-700/80 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 hover:text-[#7D39EB] dark:hover:text-violet-300 transition-all shadow-2xs flex items-center justify-between group cursor-pointer"
                                >
                                    <span>«{promptText}»</span>
                                    <CornerDownLeft className="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 text-[#7D39EB] transition-opacity shrink-0 ml-2" />
                                </button>
                            ))}
                        </div>
                    </div>
                ) : (
                    messages.map((msg, idx) => {
                        const isUser = msg.role === 'user';
                        return (
                            <div
                                key={msg.id || idx}
                                className={`flex gap-3 ${isUser ? 'justify-end' : 'justify-start'}`}
                            >
                                {!isUser && (
                                    <div className="w-8 h-8 rounded-xl bg-[#7D39EB] text-white flex items-center justify-center shrink-0 shadow-xs mt-1">
                                        <svg width="18" height="18" viewBox="0 0 64 64" fill="none">
                                            <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                                            <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                                        </svg>
                                    </div>
                                )}

                                <div className={`relative group max-w-[88%] sm:max-w-[80%] rounded-2xl p-4 text-xs leading-relaxed transition-all shadow-2xs ${
                                    isUser
                                        ? 'bg-[#7D39EB] text-white rounded-tr-xs'
                                        : 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 border border-[#ECEEF1] dark:border-slate-700/80 rounded-tl-xs'
                                }`}>
                                    {/* Author & Timestamp */}
                                    <div className="flex items-center justify-between gap-4 mb-2 pb-1.5 border-b border-black/5 dark:border-white/10">
                                        <span className={`font-bold text-[10.5px] ${isUser ? 'text-violet-100' : 'text-violet-600 dark:text-violet-400'}`}>
                                            {isUser ? (user?.name || 'Вы') : 'Edusfera AI'}
                                        </span>
                                        <span className={`text-[10px] ${isUser ? 'text-violet-200' : 'text-slate-400'}`}>
                                            {msg.timestamp}
                                        </span>
                                    </div>

                                    {/* Content */}
                                    {isUser ? (
                                        <div className="whitespace-pre-wrap font-sans text-[13px]">
                                            {msg.content}
                                        </div>
                                    ) : (
                                        <div
                                            className="ai-content-canvas text-[13.5px] leading-relaxed text-slate-800 dark:text-slate-100"
                                            dangerouslySetInnerHTML={{ __html: formatAiMarkdown(msg.content) }}
                                        />
                                    )}

                                    {/* Action bar on hover */}
                                    {!isUser && (
                                        <div className="pt-2 mt-2 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-[11px] text-slate-400">
                                            <span>Edusfera AI Engine</span>
                                            <button
                                                type="button"
                                                onClick={() => handleCopy(msg.content, idx)}
                                                className="inline-flex items-center gap-1 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                                                title="Скопировать ответ"
                                            >
                                                {copiedIndex === idx ? (
                                                    <>
                                                        <Check className="w-3 h-3 text-emerald-500" />
                                                        <span className="text-emerald-500 font-semibold">Скопировано!</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <Copy className="w-3 h-3" />
                                                        <span>Копировать</span>
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    )}
                                </div>

                                {isUser && (
                                    <div className="w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center shrink-0 text-xs font-bold mt-1">
                                        <User className="w-4 h-4" />
                                    </div>
                                )}
                            </div>
                        );
                    })
                )}

                {/* Loading indicator */}
                {isLoading && (
                    <div className="flex gap-3 justify-start">
                        <div className="w-8 h-8 rounded-xl bg-[#7D39EB] text-white flex items-center justify-center shrink-0 shadow-xs animate-pulse">
                            <svg width="18" height="18" viewBox="0 0 64 64" fill="none">
                                <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                                <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                            </svg>
                        </div>
                        <div className="rounded-2xl rounded-tl-xs p-3.5 bg-white dark:bg-slate-800 border border-[#ECEEF1] dark:border-slate-700 text-xs text-slate-700 dark:text-slate-300 flex items-center gap-2.5 shadow-2xs">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-[#7D39EB]"></span>
                            </span>
                            <span className="font-semibold">ИИ-Методист формулирует педагогический ответ...</span>
                        </div>
                    </div>
                )}

                <div ref={messagesEndRef} />
            </div>

            {/* Bottom Input Area */}
            <div className="p-3.5 sm:p-5 border-t border-[#ECEEF1] dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
                <div className="relative rounded-2xl border-2 border-[#ECEEF1] dark:border-slate-700 bg-[#FAF8FF]/40 dark:bg-slate-800/60 focus-within:border-[#7D39EB] focus-within:bg-white focus-within:ring-4 focus-within:ring-violet-500/10 transition-all p-2.5 flex flex-col">
                    <textarea
                        ref={textareaRef}
                        rows={1}
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder="Спросите у методиста или вставьте текст задачи... (Enter для отправки)"
                        className="w-full bg-transparent resize-none border-none outline-none text-xs sm:text-[13px] text-slate-900 dark:text-slate-100 placeholder-slate-400 p-1.5 max-h-44 min-h-[38px] leading-relaxed font-medium"
                        disabled={isLoading}
                    />

                    <div className="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-700/50 mt-1">
                        <div className="text-[10px] text-slate-400 px-1 hidden sm:block font-medium">
                            <span>Нажмите <kbd className="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono text-[9px] border border-slate-200 dark:border-slate-600">Enter</kbd> для отправки, <kbd className="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono text-[9px] border border-slate-200 dark:border-slate-600">Shift+Enter</kbd> для новой строки</span>
                        </div>

                        <button
                            type="button"
                            onClick={() => handleSendMessage()}
                            disabled={isLoading || !input.trim()}
                            className="ml-auto inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black text-white shadow-xs transition-all disabled:opacity-40 disabled:pointer-events-none cursor-pointer hover:shadow-md hover:shadow-violet-500/20 active:scale-98"
                            style={{ background: 'linear-gradient(135deg, #7D39EB 0%, #632cd6 100%)' }}
                        >
                            <span>Отправить</span>
                            <Send className="w-3.5 h-3.5 text-[#C6FF33]" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
