import React, { useState, useEffect } from 'react';
import { 
    CheckSquare, 
    Sparkles, 
    Layers, 
    AlertTriangle, 
    Target, 
    History, 
    Trash2, 
    AlertCircle 
} from 'lucide-react';
import AiFormattedOutput from './AiFormattedOutput';

const QUICK_QUIZ_TOPICS = [
    { label: '📐 Тригонометрические уравнения с отбором корней', subject: 'Математика' },
    { label: '📐 Стереометрия: расстояния и углы в пирамидах (Часть Б)', subject: 'Математика' },
    { label: '⚡ Законы постоянного тока и расчет смешанных цепей', subject: 'Физика' },
    { label: '📝 Пунктуация в сложносочиненных и бессоюзных предложениях', subject: 'Русский язык' },
    { label: '🇬🇧 Phrasal Verbs & Prepositions (CT 2026 Format)', subject: 'Английский язык' },
    { label: '🧪 Реакции окисления-восстановления методом электронного баланса', subject: 'Химия' },
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
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {/* Left: Settings */}
            <div className="lg:col-span-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-5">
                <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                            <CheckSquare className="w-4 h-4" />
                        </div>
                        <div>
                            <h3 className="font-bold text-xs text-slate-900 dark:text-white">
                                Параметры заданий РИКЗ
                            </h3>
                            <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                Спецификации ЦТ/ЦЭ 2026, дистракторы и ловушки
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-1">
                        {history.length > 0 && (
                            <button
                                type="button"
                                onClick={() => setShowHistory(!showHistory)}
                                className={`p-1.5 rounded-xl border text-xs transition-colors flex items-center gap-1 ${
                                    showHistory 
                                        ? 'bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 border-violet-200' 
                                        : 'border-slate-200 dark:border-slate-700 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                                }`}
                                title="История тестов"
                            >
                                <History className="w-3.5 h-3.5" />
                                <span className="text-[10px] font-bold">{history.length}</span>
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={onOpenLibrary}
                            className="px-2.5 py-1.5 rounded-xl bg-violet-50 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 hover:bg-violet-100 text-[11px] font-bold flex items-center gap-1 transition-colors"
                        >
                            <Sparkles className="w-3 h-3" />
                            <span>Промпты</span>
                        </button>
                    </div>
                </div>

                {/* History if open */}
                {showHistory && (
                    <div className="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2">
                        <div className="flex items-center justify-between text-[11px] font-bold text-slate-600 dark:text-slate-300">
                            <span>Недавние тесты ({history.length}):</span>
                            <button
                                type="button"
                                onClick={clearHistory}
                                className="text-rose-500 hover:text-rose-600 text-[10px] flex items-center gap-1"
                            >
                                <Trash2 className="w-3 h-3" /> Очистить
                            </button>
                        </div>
                        <div className="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                            {history.map((item) => (
                                <div
                                    key={item.id}
                                    onClick={() => {
                                        setResult(item.content);
                                        setTopic(item.topic);
                                        setShowHistory(false);
                                    }}
                                    className="p-2 rounded-xl bg-white dark:bg-slate-800 hover:bg-violet-50 dark:hover:bg-violet-950/40 border border-slate-200/60 dark:border-slate-700/60 cursor-pointer text-left transition-colors"
                                >
                                    <div className="font-semibold text-xs text-slate-900 dark:text-white line-clamp-1">
                                        {item.topic}
                                    </div>
                                    <div className="text-[10px] text-slate-400 flex items-center justify-between mt-0.5">
                                        <span>{item.subject} • {item.count} зад.</span>
                                        <span>{item.date}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                <form onSubmit={handleGenerate} className="space-y-3.5">
                    {/* Subject */}
                    <div>
                        <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Предмет
                        </label>
                        <input
                            type="text"
                            value={subject}
                            onChange={(e) => setSubject(e.target.value)}
                            placeholder="Математика, Физика, Русский язык..."
                            className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                        />
                    </div>

                    {/* Topic */}
                    <div>
                        <div className="flex items-center justify-between mb-1">
                            <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                Тема для заданий <span className="text-rose-500">*</span>
                            </label>
                            <span className="text-[10px] text-slate-400">Специфика раздела</span>
                        </div>
                        <input
                            type="text"
                            value={topic}
                            onChange={(e) => setTopic(e.target.value)}
                            placeholder="Напр.: Стереометрия, углы между скрещивающимися прямыми"
                            className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2.5 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                            required
                        />

                        {/* Quick Chips */}
                        <div className="pt-2 flex flex-wrap gap-1.5">
                            {QUICK_QUIZ_TOPICS.map((item, idx) => (
                                <button
                                    key={idx}
                                    type="button"
                                    onClick={() => {
                                        setTopic(item.label.replace(/^[^\s]+\s/, ''));
                                        setSubject(item.subject);
                                    }}
                                    className="text-[10.5px] px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-violet-50 dark:hover:bg-violet-950/40 text-slate-600 dark:text-slate-300 hover:text-violet-600 dark:hover:text-violet-300 transition-colors border border-slate-200/60 dark:border-slate-700/60"
                                >
                                    {item.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Count & Difficulty */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Количество заданий
                            </label>
                            <select
                                value={count}
                                onChange={(e) => setCount(Number(e.target.value))}
                                className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                            >
                                <option value={3}>3 задания (экспресс-срез)</option>
                                <option value={5}>5 заданий (стандартный тест)</option>
                                <option value={8}>8 заданий (проверочная работа)</option>
                                <option value={10}>10 заданий (полный блок)</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Уровень сложности
                            </label>
                            <select
                                value={difficulty}
                                onChange={(e) => setDifficulty(e.target.value)}
                                className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                            >
                                <option value="Базовый (Часть А ЦТ 2026)">Базовый (Часть А)</option>
                                <option value="Средний (ЦТ 2026, часть А и Б)">Средний (ЦТ 2026, А+Б)</option>
                                <option value="Сложный (Часть Б, высокий балл)">Сложный (Часть Б, 80+)</option>
                                <option value="Олимпиадный уровень со звездочкой">Олимпиадный (*)</option>
                            </select>
                        </div>
                    </div>

                    {/* Format */}
                    <div>
                        <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Формат заданий
                        </label>
                        <select
                            value={format}
                            onChange={(e) => setFormat(e.target.value)}
                            className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                        >
                            <option value="Смешанный (Часть А с выбором и Часть Б с кратким числовым ответом)">Смешанный (Часть А + Часть Б)</option>
                            <option value="Только Часть Б (краткий числовой ответ РИКЗ)">Только Часть Б (числовой ответ)</option>
                            <option value="Только Часть А (4-5 вариантов с дистракторами)">Только Часть А (тест с вариантами)</option>
                        </select>
                    </div>

                    {error && (
                        <div className="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
                            <AlertCircle className="w-4 h-4 shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}

                    {/* Button */}
                    <div className="pt-2">
                        <button
                            type="submit"
                            disabled={isLoading}
                            className="w-full relative overflow-hidden py-3 px-4 rounded-xl font-bold text-xs text-white shadow-md shadow-violet-600/25 transition-all duration-200 hover:shadow-lg hover:shadow-violet-600/35 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-2"
                            style={{ background: 'linear-gradient(135deg, #7D39EB 0%, #632cd6 100%)' }}
                        >
                            {isLoading ? (
                                <>
                                    <Sparkles className="w-4 h-4 animate-spin" />
                                    <span>Генерация заданий РИКЗ...</span>
                                </>
                            ) : (
                                <>
                                    <Sparkles className="w-4 h-4" />
                                    <span>✨ Сгенерировать банк заданий</span>
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>

            {/* Right: Output */}
            <div className="lg:col-span-7">
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
                    <div className="rounded-3xl border border-dashed border-slate-300 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 p-10 text-center flex flex-col items-center justify-center min-h-[460px] space-y-4">
                        <div className="w-16 h-16 rounded-3xl bg-violet-50 dark:bg-violet-950/40 border border-violet-200/50 dark:border-violet-800/50 flex items-center justify-center text-2xl text-violet-600 shadow-inner">
                            🎯
                        </div>
                        <div className="max-w-md space-y-2">
                            <h3 className="text-base font-bold text-slate-900 dark:text-white">
                                Банк заданий пока не сформирован
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                Задайте тему и формат слева. ИИ создаст подборку заданий с однозначными условиями, правильными числовыми ответами, пошаговыми выкладками и разбором ловушек.
                            </p>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-lg w-full pt-4 text-left">
                            <div className="p-3 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 space-y-1">
                                <span className="text-sm">⚠️</span>
                                <div className="text-[11px] font-bold text-slate-900 dark:text-white">Ловушки РИКЗ</div>
                                <div className="text-[10px] text-slate-500">Где ошибаются 80%</div>
                            </div>
                            <div className="p-3 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 space-y-1">
                                <span className="text-sm">🔑</span>
                                <div className="text-[11px] font-bold text-slate-900 dark:text-white">Сводка ответов</div>
                                <div className="text-[10px] text-slate-500">Для экспресс-проверки</div>
                            </div>
                            <div className="p-3 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 space-y-1">
                                <span className="text-sm">📋</span>
                                <div className="text-[11px] font-bold text-slate-900 dark:text-white">Быстрое ДЗ</div>
                                <div className="text-[10px] text-slate-500">Копирование в 1 клик</div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
