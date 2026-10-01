import React, { useState, useEffect } from 'react';
import { 
    BookOpen, 
    Sparkles, 
    Clock, 
    Target, 
    Layers, 
    Flame, 
    CheckCircle2, 
    AlertCircle, 
    History, 
    Trash2 
} from 'lucide-react';
import AiFormattedOutput from './AiFormattedOutput';

const QUICK_TOPICS = [
    { label: '📐 Логарифмические неравенства с переменным основанием', subject: 'Математика' },
    { label: '⚡ Закон сохранения энергии в механике', subject: 'Физика' },
    { label: '📝 Правописание НЕ и НИ с разными частями речи', subject: 'Русский язык' },
    { label: '🇬🇧 Conditional Sentences (Type 1, 2, 3 and Mixed)', subject: 'Английский язык' },
    { label: '🧪 Электролитическая диссоциация и реакции ионного обмена', subject: 'Химия' },
    { label: '🧬 Синтез белка: транскрипция и трансляция', subject: 'Биология' },
];

export default function LessonPlanTab({ csrfToken, endpoints, initialSubject = 'Математика', onOpenLibrary }) {
    const [subject, setSubject] = useState(initialSubject || 'Математика');
    const [grade, setGrade] = useState('11 класс (подготовка к ЦТ/ЦЭ 2026)');
    const [topic, setTopic] = useState('');
    const [duration, setDuration] = useState('60 минут');
    const [goal, setGoal] = useState('');
    const [focus, setFocus] = useState('Практика ЦТ/ЦЭ и разбор ловушек РИКЗ');

    const [isLoading, setIsLoading] = useState(false);
    const [result, setResult] = useState('');
    const [error, setError] = useState(null);
    const [history, setHistory] = useState([]);
    const [showHistory, setShowHistory] = useState(false);

    // Load history from localStorage
    useEffect(() => {
        try {
            const saved = localStorage.getItem('edusfera_ai_lp_history');
            if (saved) {
                setHistory(JSON.parse(saved));
            }
        } catch (e) {
            console.warn('Failed to load history:', e);
        }
    }, []);

    const saveToHistory = (newPlanText, currentTopic) => {
        try {
            const newItem = {
                id: Date.now(),
                topic: currentTopic,
                subject,
                date: new Date().toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }),
                content: newPlanText,
            };
            const updated = [newItem, ...history.slice(0, 7)];
            setHistory(updated);
            localStorage.setItem('edusfera_ai_lp_history', JSON.stringify(updated));
        } catch (e) {
            console.warn('Failed to save history:', e);
        }
    };

    const clearHistory = () => {
        setHistory([]);
        localStorage.removeItem('edusfera_ai_lp_history');
    };

    const handleGenerate = async (e) => {
        if (e) e.preventDefault();
        if (!topic.trim()) {
            setError('Пожалуйста, введите тему занятия.');
            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            const endpoint = endpoints?.lessonPlan || '/admin/ai-copilot/lesson-plan';
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''),
                },
                body: JSON.stringify({
                    subject,
                    grade,
                    topic: topic.trim(),
                    duration,
                    goal: goal.trim(),
                    focus,
                }),
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.error || 'Не удалось сгенерировать конспект урока.');
            }

            setResult(data.result);
            saveToHistory(data.result, topic.trim());
        } catch (err) {
            console.error('Error generating lesson plan:', err);
            setError(err.message || 'Произошла непредвиденная ошибка при запросе к ИИ.');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {/* Left Column: Form & Settings */}
            <div className="lg:col-span-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-6 shadow-sm space-y-5">
                {/* Header */}
                <div className="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                            <BookOpen className="w-4 h-4" />
                        </div>
                        <div>
                            <h3 className="font-bold text-xs text-slate-900 dark:text-white">
                                Параметры конспекта
                            </h3>
                            <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                Тайминг, теория, задачи с решениями и ДЗ
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
                                title="История генераций"
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

                {/* History Drawer if toggled */}
                {showHistory && (
                    <div className="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2">
                        <div className="flex items-center justify-between text-[11px] font-bold text-slate-600 dark:text-slate-300">
                            <span>Недавние планы ({history.length}):</span>
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
                                        <span>{item.subject}</span>
                                        <span>{item.date}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                <form onSubmit={handleGenerate} className="space-y-3.5">
                    {/* Subject & Grade */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Предмет
                            </label>
                            <input
                                type="text"
                                value={subject}
                                onChange={(e) => setSubject(e.target.value)}
                                placeholder="Математика, Физика..."
                                className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                            />
                        </div>
                        <div>
                            <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Аудитория и уровень
                            </label>
                            <select
                                value={grade}
                                onChange={(e) => setGrade(e.target.value)}
                                className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                            >
                                <option value="11 класс (подготовка к ЦТ/ЦЭ 2026)">11 класс (ЦТ / ЦЭ 2026)</option>
                                <option value="10 класс (углубленный уровень)">10 класс (углубленный)</option>
                                <option value="9 класс (выпускные экзамены / ОГЭ)">9 класс (базовый/экзамен)</option>
                                <option value="5-8 классы (устранение пробелов)">5–8 классы (пробелы)</option>
                                <option value="Олимпиадная подготовка (район / город)">Олимпиада (район/город)</option>
                            </select>
                        </div>
                    </div>

                    {/* Topic Input with Fast Chips */}
                    <div>
                        <div className="flex items-center justify-between mb-1">
                            <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                Тема занятия <span className="text-rose-500">*</span>
                            </label>
                            <span className="text-[10px] text-slate-400">Точная формулировка</span>
                        </div>
                        <input
                            type="text"
                            value={topic}
                            onChange={(e) => setTopic(e.target.value)}
                            placeholder="Напр.: Логарифмические неравенства с переменным основанием"
                            className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2.5 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                            required
                        />

                        {/* Quick Topic Chips */}
                        <div className="pt-2 flex flex-wrap gap-1.5">
                            {QUICK_TOPICS.map((item, idx) => (
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

                    {/* Duration & Focus */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Длительность
                            </label>
                            <select
                                value={duration}
                                onChange={(e) => setDuration(e.target.value)}
                                className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                            >
                                <option value="45 минут">45 минут (экспресс)</option>
                                <option value="60 минут">60 минут (стандарт)</option>
                                <option value="90 минут">90 минут (интенсив)</option>
                                <option value="120 минут">120 минут (парное)</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Фокус урока
                            </label>
                            <select
                                value={focus}
                                onChange={(e) => setFocus(e.target.value)}
                                className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                            >
                                <option value="Практика ЦТ/ЦЭ и разбор ловушек РИКЗ">Практика ЦТ/ЦЭ + ловушки</option>
                                <option value="Изучение новой темы с нуля (наглядно)">Новая тема с нуля («на пальцах»)</option>
                                <option value="Олимпиадные задачи повышенной сложности">Олимпиадный уровень</option>
                                <option value="Экспресс-повторение перед экзаменом">Экспресс-повторение</option>
                            </select>
                        </div>
                    </div>

                    {/* Target Goal (Optional) */}
                    <div>
                        <label className="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Конкретная цель / результат (опционально)
                        </label>
                        <input
                            type="text"
                            value={goal}
                            onChange={(e) => setGoal(e.target.value)}
                            placeholder="Напр.: Научить решать задачи части Б №10 без ошибок в ОДЗ"
                            className="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/90 text-xs px-3 py-2 font-medium text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                        />
                    </div>

                    {/* Error Banner */}
                    {error && (
                        <div className="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
                            <AlertCircle className="w-4 h-4 shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}

                    {/* Submit Button */}
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
                                    <span>Генерация конспекта через Gemini...</span>
                                </>
                            ) : (
                                <>
                                    <Sparkles className="w-4 h-4" />
                                    <span>✨ Сгенерировать конспект урока</span>
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>

            {/* Right Column: Output Viewer or Empty State */}
            <div className="lg:col-span-7">
                {isLoading ? (
                    <AiFormattedOutput isLoading={true} />
                ) : result ? (
                    <AiFormattedOutput
                        content={result}
                        title={`План урока: ${topic}`}
                        subtitle={`${subject} • ${grade} • ${duration}`}
                        onRegenerate={() => handleGenerate()}
                    />
                ) : (
                    <div className="rounded-3xl border border-dashed border-slate-300 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 p-10 text-center flex flex-col items-center justify-center min-h-[460px] space-y-4">
                        <div className="w-16 h-16 rounded-3xl bg-violet-50 dark:bg-violet-950/40 border border-violet-200/50 dark:border-violet-800/50 flex items-center justify-center text-2xl text-violet-600 shadow-inner">
                            📚
                        </div>
                        <div className="max-w-md space-y-2">
                            <h3 className="text-base font-bold text-slate-900 dark:text-white">
                                Конспект занятия пока не составлен
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                Задайте предмет и тему слева, либо выберите один из быстрых чипсов. Gemini составит полноценную технологическую карту с поминутным таймингом, разбором задач и домашним заданием.
                            </p>
                        </div>

                        {/* Feature Badges */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-lg w-full pt-4 text-left">
                            <div className="p-3 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 space-y-1">
                                <span className="text-sm">⏱</span>
                                <div className="text-[11px] font-bold text-slate-900 dark:text-white">Тайминг этапов</div>
                                <div className="text-[10px] text-slate-500">От разминки до рефлексии</div>
                            </div>
                            <div className="p-3 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 space-y-1">
                                <span className="text-sm">📐</span>
                                <div className="text-[11px] font-bold text-slate-900 dark:text-white">LaTeX Формулы</div>
                                <div className="text-[10px] text-slate-500">KaTeX рендеринг уравнений</div>
                            </div>
                            <div className="p-3 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 space-y-1">
                                <span className="text-sm">🖨</span>
                                <div className="text-[11px] font-bold text-slate-900 dark:text-white">Экспорт в PDF</div>
                                <div className="text-[10px] text-slate-500">Печать чистого конспекта</div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
