import React, { useState } from 'react';
import { Sparkles, X, Search, BookOpen, Target, Brain, Award, Check } from 'lucide-react';

const PROMPT_CATEGORIES = [
    { id: 'all', label: 'Все сценарии' },
    { id: 'ct_ce', label: '🎯 ЦТ и ЦЭ 2026' },
    { id: 'lesson_plan', label: '📚 Планы занятий' },
    { id: 'quiz', label: '📝 Проверочные и ДЗ' },
    { id: 'pedagogy', label: '💡 Методика и мнемоника' },
    { id: 'olympiad', label: '🏆 Олимпиады' },
];

const PRESET_PROMPTS = [
    {
        id: 1,
        category: 'ct_ce',
        title: 'Разбор ловушек РИКЗ в логарифмических неравенствах',
        subject: 'Математика',
        desc: 'Помогает ученику не потерять ОДЗ и не ошибиться при переходе к переменному основанию.',
        prompt: 'Составь подборку из 4 сложных логарифмических неравенств формата ЦТ/ЦЭ 2026 (Часть Б) с переменным основанием. Для каждого покажи главную ловушку (потерю ОДЗ или смену знака) и пошаговый алгоритм решения.',
        targetTab: 'quiz_gen',
    },
    {
        id: 2,
        category: 'ct_ce',
        title: 'Топ-5 ловушек в орфографии (Н и НН в суффиксах)',
        subject: 'Русский язык',
        desc: 'Концентрированный разбор исключений и каверзных формулировок части А.',
        prompt: 'Сформируй экспресс-шпаргалку по теме «Правописание Н и НН в различных частях речи» для абитуриента ЦТ/ЦЭ 2026. Выдели 5 типичных ловушек составителей РИКЗ и дай мнемонические правила для запоминания.',
        targetTab: 'methodist',
    },
    {
        id: 3,
        category: 'lesson_plan',
        title: 'Урок-интенсив: Кинематика равноускоренного движения (60 мин)',
        subject: 'Физика',
        desc: 'Поминутный конспект с выводом уравнений проекций скорости и перемещения без времени.',
        prompt: 'Составь технологическую карту 60-минутного занятия по физике: «Равноускоренное прямолинейное движение. Формулы пути без времени». Включи разминку на 5 мин, вывод формул через LaTeX и 3 задачи уровня ЦТ.',
        targetTab: 'lesson_plan',
    },
    {
        id: 4,
        category: 'lesson_plan',
        title: 'Интерактивный урок: Conditionals 0, 1, 2, 3 и Mixed',
        subject: 'Английский язык',
        desc: 'Конспект с коммуникативными ситуациями и разбором распространенных ошибок.',
        prompt: 'Разработай конспект индивидуального занятия на 60 минут: «Условные предложения в английском языке (Conditionals 0, 1, 2, 3 и Mixed Conditionals)». Добавь наглядную сравнительную таблицу и упражнения на трансформацию предложений.',
        targetTab: 'lesson_plan',
    },
    {
        id: 5,
        category: 'quiz',
        title: 'Проверочный тест: Стереометрия, углы в пирамидах (Часть Б)',
        subject: 'Математика',
        desc: 'Банк заданий с готовыми ответами и числовыми критериями.',
        prompt: 'Сгенерируй проверочный тест из 5 задач части Б ЦТ 2026 по теме «Правильные пирамиды: угол между боковым ребром и плоскостью основания, сечения». Для каждой задачи укажи числовой ответ и подробный ход решения.',
        targetTab: 'quiz_gen',
    },
    {
        id: 6,
        category: 'pedagogy',
        title: 'Объяснение формулы Бернулли на жизненном примере',
        subject: 'Теория вероятностей',
        desc: 'Позволяет за 5 минут объяснить сложную формулу комбинаторики без зубрежки.',
        prompt: 'Как объяснить формулу Бернулли для схемы независимых испытаний ученику 10-11 класса на супер-наглядном жизненном примере (стрельба по мишени или броски монеты)? Дай пошаговую педагогическую метафору и наглядную схему.',
        targetTab: 'methodist',
    },
    {
        id: 7,
        category: 'olympiad',
        title: 'Олимпиадная планиметрия: Лемма о трезубце и вневписанные окружности',
        subject: 'Математика',
        desc: 'Разбор теоремы для подготовки к городским и республиканским олимпиадам.',
        prompt: 'Сформулируй лемму о трезубце (теорему Мансиона) в планиметрии. Приведи строгое доказательство через вписанные углы в LaTeX и разбери 1 красивую олимпиадную задачу на ее прямое применение.',
        targetTab: 'methodist',
    },
    {
        id: 8,
        category: 'quiz',
        title: 'Трехуровневое дифференцированное ДЗ: Электролиз растворов солей',
        subject: 'Химия',
        desc: 'Уровень А (база), уровень Б (стандарт ЦЭ), уровень С (высокий балл).',
        prompt: 'Составь домашнее задание по химии по теме «Электролиз водных растворов солей»: 2 задания уровня База (составить уравнения полуреакций), 2 задания уровня ЦТ/ЦЭ (расчет массы выделившегося вещества по закону Фарадея) и 1 задание со звездочкой.',
        targetTab: 'quiz_gen',
    },
];

export default function PromptLibraryModal({ isOpen, onClose, onSelectPrompt }) {
    const [activeCat, setActiveCat] = useState('all');
    const [search, setSearch] = useState('');
    const [copiedId, setCopiedId] = useState(null);

    if (!isOpen) return null;

    const filteredPrompts = PRESET_PROMPTS.filter((p) => {
        const matchesCat = activeCat === 'all' || p.category === activeCat;
        const matchesSearch =
            p.title.toLowerCase().includes(search.toLowerCase()) ||
            p.desc.toLowerCase().includes(search.toLowerCase()) ||
            p.subject.toLowerCase().includes(search.toLowerCase());
        return matchesCat && matchesSearch;
    });

    const handleApply = (item) => {
        onSelectPrompt(item);
        setCopiedId(item.id);
        setTimeout(() => {
            setCopiedId(null);
            onClose();
        }, 500);
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-fadeIn">
            <div className="relative w-full max-w-3xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
                {/* Header */}
                <div className="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/60 dark:bg-slate-800/40">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-2xl bg-gradient-to-tr from-violet-600 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-violet-500/20">
                            <Sparkles className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="font-bold text-base text-slate-900 dark:text-white">
                                Библиотека готовых промптов репетитора
                            </h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Отобранные методические сценарии под стандарты ЦТ и ЦЭ Беларуси 2026
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Search & Category Filter */}
                <div className="p-5 border-b border-slate-100 dark:border-slate-800 space-y-3 bg-white dark:bg-slate-900">
                    <div className="relative">
                        <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Поиск по теме, предмету или ключевым словам..."
                            className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800/80 text-xs font-medium text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500/40 focus:border-violet-500"
                        />
                    </div>

                    <div className="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                        {PROMPT_CATEGORIES.map((cat) => (
                            <button
                                key={cat.id}
                                type="button"
                                onClick={() => setActiveCat(cat.id)}
                                className={`px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${
                                    activeCat === cat.id
                                        ? 'bg-violet-600 text-white shadow-xs'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                                }`}
                            >
                                {cat.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Prompts Grid */}
                <div className="flex-1 overflow-y-auto p-5 space-y-3">
                    {filteredPrompts.length === 0 ? (
                        <div className="text-center py-12 text-slate-400 text-xs">
                            Промпты по вашему запросу не найдены.
                        </div>
                    ) : (
                        filteredPrompts.map((item) => (
                            <div
                                key={item.id}
                                className="group p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-violet-300 dark:hover:border-violet-700 bg-white dark:bg-slate-800/60 hover:bg-violet-50/20 dark:hover:bg-violet-950/20 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                            >
                                <div className="space-y-1 max-w-xl">
                                    <div className="flex items-center gap-2">
                                        <span className="text-[10px] font-bold px-2 py-0.5 rounded-md bg-violet-100 dark:bg-violet-950/80 text-violet-700 dark:text-violet-300">
                                            {item.subject}
                                        </span>
                                        <h4 className="font-bold text-xs text-slate-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                                            {item.title}
                                        </h4>
                                    </div>
                                    <p className="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                        {item.desc}
                                    </p>
                                    <div className="text-[10px] text-slate-400 line-clamp-1 italic">
                                        «{item.prompt}»
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    onClick={() => handleApply(item)}
                                    className={`shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                                        copiedId === item.id
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-violet-600 hover:bg-violet-500 text-white shadow-xs'
                                    }`}
                                >
                                    {copiedId === item.id ? (
                                        <>
                                            <Check className="w-3.5 h-3.5" />
                                            <span>Применено!</span>
                                        </>
                                    ) : (
                                        <>
                                            <Sparkles className="w-3.5 h-3.5" />
                                            <span>Использовать</span>
                                        </>
                                    )}
                                </button>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}
