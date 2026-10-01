import React, { useState, useEffect } from 'react';
import { 
    CheckSquare, 
    Sparkles, 
    Layers, 
    AlertTriangle, 
    Target, 
    History, 
    Trash2, 
    AlertCircle,
    Check
} from 'lucide-react';
import AiFormattedOutput from './AiFormattedOutput';

const SUBJECTS = [
    { id: 'Математика', label: 'Математика', icon: '📐' },
    { id: 'Физика', label: 'Физика', icon: '⚡' },
    { id: 'Русский язык', label: 'Русский язык', icon: '📝' },
    { id: 'Английский язык', label: 'Английский язык', icon: '🇬🇧' },
    { id: 'Химия', label: 'Химия', icon: '🧪' },
    { id: 'Биология', label: 'Биология', icon: '🧬' },
];

const QUESTION_COUNTS = [
    { value: 3, label: '3 зад.', sub: 'Экспресс' },
    { value: 5, label: '5 зад.', sub: 'Стандарт' },
    { value: 8, label: '8 зад.', sub: 'Проверочная' },
    { value: 10, label: '10 зад.', sub: 'Мини-ЦТ' },
];

const DIFFICULTIES = [
    { 
        id: 'Базовый (Часть А ЦТ 2026)', 
        label: 'Часть А', 
        tag: 'Базовый', 
        desc: 'Выбор ответа (4-5 вариантов), дистракторы РИКЗ',
        icon: '📝' 
    },
    { 
        id: 'Средний (ЦТ 2026, часть А и Б)', 
        label: 'ЦТ 2026', 
        tag: 'А + Б', 
        desc: 'Сбалансированный тест полного формата экзамена',
        icon: '🎯' 
    },
    { 
        id: 'Сложный (Часть Б, высокий балл)', 
        label: 'Часть Б', 
        tag: '80+ баллов', 
        desc: 'Краткий числовой ответ, повышенная сложность',
        icon: '⚡' 
    },
    { 
        id: 'Олимпиадный уровень со звездочкой', 
        label: 'Олимпиадный', 
        tag: 'Хард (*)', 
        desc: 'Нестандартное мышление, задачи со звездочкой',
        icon: '🏆' 
    },
];

const FORMATS = [
    { id: 'Смешанный (Часть А с выбором и Часть Б с кратким числовым ответом)', label: 'Смешанный (А + Б)' },
    { id: 'Только Часть Б (краткий числовой ответ РИКЗ)', label: 'Только Часть Б' },
    { id: 'Только Часть А (4-5 вариантов с дистракторами)', label: 'Только Часть А' },
];

const QUICK_QUIZ_TOPICS = [
    { label: 'Стереометрия: расстояния и углы в пирамидах (Часть Б)', subject: 'Математика' },
    { label: 'Тригонометрические уравнения с отбором корней', subject: 'Математика' },
    { label: 'Законы постоянного тока и расчет смешанных цепей', subject: 'Физика' },
    { label: 'Пунктуация в бессоюзных и сложносочиненных предложениях', subject: 'Русский язык' },
    { label: 'Phrasal Verbs & Prepositions (CT 2026)', subject: 'Английский язык' },
];

export default function QuizGeneratorTab({ csrfToken, endpoints, initialSubject = 'Математика', onOpenLibrary }) {
    const [subject, setSubject] = useState(initialSubject || 'Математика');
    const [topic, setTopic] = useState('');
    const [count, setCount] = useState(5);
    const [difficulty, setDifficulty] = useState('Средний (ЦТ 2026, часть А и Б)');
    const [format, setFormat] = useState('Смешанный (Часть А с выбором и Часть Б с кратким числовым ответом)');

    const [isLoading, setIsLoading] = useState(false);
    const [result, setResult] = useState('');
    const [error, setError] = useState(null);
    const [history, setHistory] = useState([]);
    const [showHistory, setShowHistory] = useState(false);

    useEffect(() => {
        try {
            const saved = localStorage.getItem('edusfera_ai_quiz_history');
            if (saved) {
                setHistory(JSON.parse(saved));
            }
        } catch (e) {
            console.warn('Failed to load quiz history:', e);
        }
    }, []);

    const saveToHistory = (newQuizText, currentTopic) => {
        try {
            const newItem = {
                id: Date.now(),
                topic: currentTopic,
                subject,
                count,
                date: new Date().toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }),
                content: newQuizText,
            };
            const updated = [newItem, ...history.slice(0, 7)];
            setHistory(updated);
            localStorage.setItem('edusfera_ai_quiz_history', JSON.stringify(updated));
        } catch (e) {
            console.warn('Failed to save quiz history:', e);
        }
    };

    const clearHistory = () => {
        setHistory([]);
        localStorage.removeItem('edusfera_ai_quiz_history');
    };

    const handleGenerate = async (e) => {
        if (e) e.preventDefault();
        if (!topic.trim()) {
            setError('Пожалуйста, укажите тему для заданий.');
            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            const endpoint = endpoints?.quiz || '/admin/ai-copilot/quiz';
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''),
                },
                body: JSON.stringify({
                    subject,
                    topic: topic.trim(),
                    count: parseInt(count, 10),
                    difficulty,
                    format,
                }),
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Не удалось сгенерировать тест.');
            }

            setResult(data.result);
            saveToHistory(data.result, topic.trim());
        } catch (err) {
            console.error('Error generating quiz:', err);
            setError(err.message || 'Произошла непредвиденная ошибка при запросе к ИИ.');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="ed-ai-grid">
            {/* ═══ ЛЕВАЯ КОЛОНКА: БЛОКИ ПАРАМЕТРОВ ═══ */}
            <div className="ed-ai-col-form space-y-4">
                {/* БЛОК 1: Выбор предмета */}
                <div className="ed-ai-box">
                    <div className="flex items-center justify-between pb-3 mb-3 border-b border-[#ECEEF1] dark:border-slate-800">
                        <div className="flex items-center gap-2">
                            <span className="text-base">📝</span>
                            <span className="font-extrabold text-xs text-[#0C0A14] dark:text-white uppercase tracking-wider">
                                1. Предмет заданий
                            </span>
                        </div>
                        <span className="text-[11px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/40 px-2 py-0.5 rounded-md">
                            {subject}
                        </span>
                    </div>

                    <div className="ed-subject-grid">
                        {SUBJECTS.map((s) => {
                            const isSelected = subject.toLowerCase().includes(s.id.toLowerCase());
                            return (
                                <button
                                    key={s.id}
                                    type="button"
                                    onClick={() => setSubject(s.id)}
                                    className={`ed-subject-btn ${isSelected ? 'active' : ''}`}
                                >
                                    <span className="text-lg mb-1">{s.icon}</span>
                                    <span className={`text-xs font-bold leading-tight ${isSelected ? 'text-[#7D39EB] dark:text-violet-400' : 'text-slate-800 dark:text-slate-200'}`}>
                                        {s.label}
                                    </span>
                                    {isSelected && (
                                        <div className="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-[#C6FF33] text-black flex items-center justify-center text-[10px] font-black shadow-2xs">
                                            ✓
                                        </div>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* БЛОК 2: Тема заданий */}
                <div className="ed-ai-box space-y-3">
                    <div className="flex items-center justify-between pb-2 border-b border-[#ECEEF1] dark:border-slate-800">
                        <div className="flex items-center gap-2">
                            <span className="text-base">🎯</span>
                            <span className="font-extrabold text-xs text-[#0C0A14] dark:text-white uppercase tracking-wider">
                                2. Тема заданий *
                            </span>
                        </div>
                        <button
                            type="button"
                            onClick={onOpenLibrary}
                            className="text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:text-violet-700 flex items-center gap-1 cursor-pointer"
                        >
                            <Sparkles className="w-3 h-3" />
                            <span>Промпты ЦТ</span>
                        </button>
                    </div>

                    <div className="relative">
                        <input
                            type="text"
                            value={topic}
                            onChange={(e) => setTopic(e.target.value)}
                            placeholder="Напр.: Стереометрия, углы между скрещивающимися прямыми"
                            className="w-full rounded-xl border-2 border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3.5 py-3 font-semibold text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-[#7D39EB] focus:ring-4 focus:ring-violet-500/10 transition-all"
                            required
                        />
                        {topic && (
                            <button
                                type="button"
                                onClick={() => setTopic('')}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs p-1"
                            >
                                ✕
                            </button>
                        )}
                    </div>

                    <div>
                        <div className="text-[10px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            Популярные темы ЦТ 2026:
                        </div>
                        <div className="flex flex-wrap gap-1.5">
                            {QUICK_QUIZ_TOPICS.map((item, idx) => (
                                <button
                                    key={idx}
                                    type="button"
                                    onClick={() => {
                                        setTopic(item.label);
                                        setSubject(item.subject);
                                    }}
                                    className="text-[11px] px-2.5 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-[#FAF8FF] dark:hover:bg-violet-950/40 text-slate-700 dark:text-slate-300 hover:text-[#7D39EB] dark:hover:text-violet-300 transition-all border border-slate-200/80 dark:border-slate-700 text-left font-medium cursor-pointer"
                                >
                                    {item.label}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* БЛОК 3: Количество, сложность и формат */}
                <div className="ed-ai-box space-y-4">
                    <div className="flex items-center justify-between pb-3 border-b border-[#ECEEF1] dark:border-slate-800">
                        <div className="flex items-center gap-2.5">
                            <div className="w-7 h-7 rounded-xl bg-[#FAF8FF] dark:bg-violet-950/60 text-[#7D39EB] dark:text-violet-400 flex items-center justify-center border border-[#ECE5FB] dark:border-violet-900/50">
                                <Target className="w-4 h-4 text-[#7D39EB]" />
                            </div>
                            <div>
                                <span className="font-extrabold text-xs text-[#0C0A14] dark:text-white uppercase tracking-wider block">
                                    3. Спецификация РИКЗ
                                </span>
                                <span className="text-[10px] text-slate-400 font-medium block">
                                    Количество заданий и формат сложности
                                </span>
                            </div>
                        </div>
                        <span className="text-[10px] px-2.5 py-1 rounded-full font-extrabold bg-[#FAF8FF] dark:bg-slate-800 text-[#7D39EB] border border-[#ECE5FB]">
                            {count} заданий
                        </span>
                    </div>

                    {/* Count Buttons */}
                    <div className="space-y-2">
                        <label className="block text-[11px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                            Количество заданий:
                        </label>
                        <div className="grid grid-cols-4 gap-2">
                            {QUESTION_COUNTS.map((qc) => {
                                const isSelected = count === qc.value;
                                return (
                                    <button
                                        key={qc.value}
                                        type="button"
                                        onClick={() => setCount(qc.value)}
                                        className={`ed-timing-btn ${isSelected ? 'active' : ''}`}
                                    >
                                        {isSelected && (
                                            <span className="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-[#C6FF33] border-2 border-white dark:border-slate-900 shadow-2xs"></span>
                                        )}
                                        <span className="ed-timing-val">{qc.label}</span>
                                        <span className="ed-timing-sub">{qc.sub}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Difficulty Grid */}
                    <div className="space-y-2 pt-1">
                        <label className="block text-[11px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                            Формат сложности:
                        </label>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            {DIFFICULTIES.map((df) => {
                                const isSelected = difficulty === df.id;
                                return (
                                    <button
                                        key={df.id}
                                        type="button"
                                        onClick={() => setDifficulty(df.id)}
                                        className={`ed-level-card ${isSelected ? 'active' : ''}`}
                                    >
                                        <div className="flex items-center gap-2.5 min-w-0">
                                            <div className={`w-8 h-8 rounded-xl flex items-center justify-center text-sm shrink-0 transition-colors ${
                                                isSelected 
                                                    ? 'bg-[#7D39EB] text-white shadow-xs' 
                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200'
                                            }`}>
                                                {df.icon}
                                            </div>
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-1.5">
                                                    <span className="font-extrabold text-xs text-[#0C0A14] dark:text-white truncate">
                                                        {df.label}
                                                    </span>
                                                    <span className={`text-[9.5px] px-1.5 py-0.2 rounded-md font-bold tracking-tight ${
                                                        isSelected
                                                            ? 'bg-[#7D39EB]/15 text-[#7D39EB] dark:bg-violet-900/60 dark:text-violet-300'
                                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'
                                                    }`}>
                                                        {df.tag}
                                                    </span>
                                                </div>
                                                <div className="text-[10px] text-slate-500 dark:text-slate-400 truncate mt-0.5 font-medium">
                                                    {df.desc}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Custom Radio / Check Indicator */}
                                        <div className="ed-radio-indicator">
                                            {isSelected && (
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
                                                    <polyline points="20 6 9 17 4 12" />
                                                </svg>
                                            )}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Format Selector Pills */}
                    <div className="space-y-2 pt-1">
                        <label className="block text-[11px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                            Тип ответов:
                        </label>
                        <div className="flex flex-wrap gap-2">
                            {FORMATS.map((fmt) => {
                                const isSelected = format === fmt.id;
                                return (
                                    <button
                                        key={fmt.id}
                                        type="button"
                                        onClick={() => setFormat(fmt.id)}
                                        className={`ed-format-pill ${isSelected ? 'active' : ''}`}
                                    >
                                        {fmt.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {error && (
                    <div className="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
                        <AlertCircle className="w-4 h-4 shrink-0" />
                        <span>{error}</span>
                    </div>
                )}

                {/* Submit button */}
                <button
                    type="button"
                    onClick={handleGenerate}
                    disabled={isLoading}
                    className="w-full relative overflow-hidden py-4 px-6 rounded-2xl font-black text-sm text-white shadow-lg shadow-violet-600/30 transition-all duration-200 hover:shadow-xl hover:shadow-violet-600/40 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-2.5 cursor-pointer uppercase tracking-wider font-rimma"
                    style={{ background: 'linear-gradient(135deg, #7D39EB 0%, #6827D6 100%)' }}
                >
                    <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/15 to-transparent -translate-x-full animate-[shimmer_2s_infinite]"></div>

                    {isLoading ? (
                        <>
                            <Sparkles className="w-5 h-5 animate-spin" />
                            <span>Генерация банка заданий в Gemini...</span>
                        </>
                    ) : (
                        <>
                            <Sparkles className="w-5 h-5 text-[#C6FF33]" />
                            <span>Сгенерировать задания РИКЗ</span>
                        </>
                    )}
                </button>
            </div>

            {/* ═══ ПРАВАЯ КОЛОНКА: ИНТЕРАКТИВНОЕ ПОЛОТНО ЗАДАНИЙ (STICKY) ═══ */}
            <div className="ed-ai-col-canvas">
                {isLoading ? (
                    <AiFormattedOutput isLoading={true} />
                ) : result ? (
                    <AiFormattedOutput
                        content={result}
                        title={`Банк заданий: ${topic}`}
                        subtitle={`${subject} • ${count} заданий • ${difficulty}`}
                        onRegenerate={() => handleGenerate()}
                    />
                ) : (
                    /* Стильный пустой стейт */
                    <div className="ed-ai-box p-8 sm:p-12 text-center flex flex-col items-center justify-center min-h-[540px] space-y-5">
                        <div className="relative w-32 h-32 flex items-center justify-center">
                            <svg className="absolute inset-0 w-full h-full ed-animate-spin-slow" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#7D39EB" strokeWidth="2" strokeDasharray="8 12" strokeOpacity="0.3" />
                                <circle cx="114" cy="60" r="5" fill="#C6FF33" />
                                <circle cx="6" cy="60" r="4" fill="#7D39EB" />
                            </svg>

                            <div className="ed-animate-float w-20 h-20 rounded-3xl bg-gradient-to-tr from-[#FAF8FF] to-white dark:from-slate-800 dark:to-slate-700 border-2 border-violet-300 dark:border-violet-700 flex items-center justify-center shadow-xl shadow-violet-500/10">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#7D39EB" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="m9 12 2 2 4-4"/>
                                </svg>
                            </div>
                        </div>

                        <div className="max-w-md space-y-2">
                            <h3 className="text-lg font-black text-[#0C0A14] dark:text-white font-rimma uppercase tracking-tight">
                                Банк тестовых заданий РИКЗ
                            </h3>
                            <p className="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
                                Укажите предмет и тему раздела слева. Gemini составит авторские задачи с готовыми числовыми ключами, логикой решения и анализом ловушек.
                            </p>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full max-w-lg pt-4 text-left">
                            <div className="p-3.5 rounded-2xl bg-[#FAF8FF] dark:bg-slate-800/80 border border-[#ECE5FB] dark:border-slate-700 space-y-1 hover:border-violet-300 transition-colors">
                                <span className="text-base">⚠️</span>
                                <div className="text-xs font-bold text-slate-900 dark:text-white">Ловушки РИКЗ</div>
                                <div className="text-[10px] text-slate-500">Где ошибаются 80%</div>
                            </div>
                            <div className="p-3.5 rounded-2xl bg-[#FAF8FF] dark:bg-slate-800/80 border border-[#ECE5FB] dark:border-slate-700 space-y-1 hover:border-violet-300 transition-colors">
                                <span className="text-base">🔑</span>
                                <div className="text-xs font-bold text-slate-900 dark:text-white">Сводка ответов</div>
                                <div className="text-[10px] text-slate-500">Для экспресс-проверки</div>
                            </div>
                            <div className="p-3.5 rounded-2xl bg-[#FAF8FF] dark:bg-slate-800/80 border border-[#ECE5FB] dark:border-slate-700 space-y-1 hover:border-violet-300 transition-colors">
                                <span className="text-base">📋</span>
                                <div className="text-xs font-bold text-slate-900 dark:text-white">Быстрое ДЗ</div>
                                <div className="text-[10px] text-slate-500">Копирование в 1 клик</div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
