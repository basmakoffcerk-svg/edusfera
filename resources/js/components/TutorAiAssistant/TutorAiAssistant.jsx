import React, { useState, useEffect } from 'react';
import { 
    Sparkles, 
    BookOpen, 
    CheckSquare, 
    MessageSquare, 
    Crown, 
    ArrowRight, 
    ShieldCheck, 
    Zap 
} from 'lucide-react';
import LessonPlanTab from './LessonPlanTab';
import QuizGeneratorTab from './QuizGeneratorTab';
import MethodistChatTab from './MethodistChatTab';
import PromptLibraryModal from './PromptLibraryModal';

export default function TutorAiAssistant() {
    // Read global config from blade injection
    const config = window.EDUSFERA_AI_CONFIG || {};
    const {
        canUseAi = true,
        user = {},
        csrfToken = '',
        routes = {},
    } = config;

    const [activeTab, setActiveTab] = useState('lesson_plan'); // 'lesson_plan' | 'quiz_gen' | 'methodist'
    const [isPromptLibraryOpen, setIsPromptLibraryOpen] = useState(false);
    const [appliedPrompt, setAppliedPrompt] = useState(null);

    const handleSelectPrompt = (promptItem) => {
        if (promptItem.targetTab) {
            setActiveTab(promptItem.targetTab);
        }
        setAppliedPrompt(promptItem);
    };

    return (
        <div className="space-y-6 max-w-7xl mx-auto pb-12 font-sans">
            {/* ═══ 1. HERO BANNER (2026 Glass & Ambient Glow) ═══ */}
            <div
                className="relative overflow-hidden rounded-3xl p-6 sm:p-8 shadow-xl border border-violet-500/20"
                style={{
                    background: 'linear-gradient(135deg, #090D16 0%, #15102A 50%, #111827 100%)',
                    color: '#ffffff',
                }}
            >
                {/* Ambient glow orbs */}
                <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-violet-600/25 blur-3xl pointer-events-none"></div>
                <div className="absolute -left-20 -bottom-20 h-64 w-64 rounded-full bg-fuchsia-600/20 blur-3xl pointer-events-none"></div>
                <div className="absolute right-1/3 bottom-0 h-40 w-40 rounded-full bg-emerald-500/10 blur-2xl pointer-events-none"></div>

                <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div className="space-y-2.5 max-w-2xl">
                        <div
                            className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-md"
                            style={{
                                background: 'rgba(125, 57, 235, 0.25)',
                                border: '1px solid rgba(167, 139, 250, 0.35)',
                                color: '#ddd6fe',
                            }}
                        >
                            <span className="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Google Gemini AI Engine (gemini-3.5-flash-lite)</span>
                        </div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-2.5">
                            <span>ИИ-Помощник репетитора Edusfera</span>
                            <span className="text-xs px-2.5 py-1 rounded-xl bg-violet-500/20 text-violet-300 border border-violet-400/30 font-semibold tracking-normal hidden sm:inline-block">
                                2026 Edition
                            </span>
                        </h1>
                        <p className="text-xs sm:text-sm leading-relaxed text-slate-300">
                            Профессиональный инструмент генерации технологических карт, планов уроков и банка заданий по спецификациям РИКЗ ЦТ/ЦЭ 2026. Экономия до 5 часов подготовки каждую неделю.
                        </p>
                    </div>

                    {/* Pro Badge or Upgrade CTA */}
                    {canUseAi ? (
                        <div className="shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-white/10 border border-white/15 backdrop-blur-md text-xs font-medium text-emerald-300 shadow-sm">
                            <ShieldCheck className="w-5 h-5 text-emerald-400" />
                            <div>
                                <div className="font-bold text-white text-[11px]">Тариф Pro активен</div>
                                <div className="text-[10px] text-emerald-400/90">Безлимитный доступ к Gemini</div>
                            </div>
                        </div>
                    ) : (
                        <div className="shrink-0">
                            <a
                                href={routes.subscription || '/admin/tutor-subscription'}
                                className="inline-flex items-center gap-2 px-5 py-3 rounded-2xl text-white font-bold text-xs shadow-lg shadow-violet-600/30 transition-all hover:scale-[1.02] active:scale-100"
                                style={{ background: 'linear-gradient(135deg, #7D39EB 0%, #4F46E5 100%)' }}
                            >
                                <Crown className="w-4 h-4 text-amber-300" />
                                <span>Активировать тариф Pro</span>
                                <ArrowRight className="w-4 h-4" />
                            </a>
                        </div>
                    )}
                </div>
            </div>

            {/* ═══ 2. LOCK SCREEN IF NOT PRO ═══ */}
            {!canUseAi ? (
                <div className="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 sm:p-12 text-center space-y-6 shadow-sm">
                    <div className="mx-auto w-16 h-16 rounded-3xl bg-violet-100 dark:bg-violet-950/50 flex items-center justify-center text-3xl shadow-inner">
                        🔒
                    </div>
                    <div className="max-w-md mx-auto space-y-2">
                        <h3 className="text-xl font-bold text-slate-900 dark:text-white">
                            ИИ-генераторы доступны на тарифе Pro
                        </h3>
                        <p className="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Подключите подписку «Pro» или воспользуйтесь 14-дневным бесплатным пробным периодом, чтобы создавать планы уроков и тесты РИКЗ в 1 клик.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl mx-auto text-left">
                        <div className="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 space-y-1.5">
                            <span className="text-xl">📚</span>
                            <div className="font-semibold text-xs text-slate-900 dark:text-white">Конспекты и тайминги</div>
                            <div className="text-[11px] text-slate-500">Готовые структуры уроков под ЦТ и экзамены.</div>
                        </div>
                        <div className="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 space-y-1.5">
                            <span className="text-xl">📝</span>
                            <div className="font-semibold text-xs text-slate-900 dark:text-white">Тесты РИКЗ 2026</div>
                            <div className="text-[11px] text-slate-500">Генерация заданий с ловушками и разбором.</div>
                        </div>
                        <div className="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 space-y-1.5">
                            <span className="text-xl">💬</span>
                            <div className="font-semibold text-xs text-slate-900 dark:text-white">ИИ-Методист</div>
                            <div className="text-[11px] text-slate-500">Помощь с олимпиадными темами 24/7.</div>
                        </div>
                    </div>

                    <div className="pt-2">
                        <a
                            href={routes.subscription || '/admin/tutor-subscription'}
                            className="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl text-white font-bold text-xs shadow-md shadow-violet-600/25 transition-all hover:scale-[1.02]"
                            style={{ background: 'linear-gradient(135deg, #7D39EB 0%, #4F46E5 100%)' }}
                        >
                            <span>Перейти к тарифам (от 40 BYN / мес)</span>
                            <ArrowRight className="w-4 h-4" />
                        </a>
                    </div>
                </div>
            ) : (
                <>
                    {/* ═══ 3. TABS BAR & TOOLS ═══ */}
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        {/* Segmented Tab Controls */}
                        <div className="flex items-center p-1.5 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 w-fit">
                            <button
                                type="button"
                                onClick={() => setActiveTab('lesson_plan')}
                                className={`inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                                    activeTab === 'lesson_plan'
                                        ? 'bg-white dark:bg-slate-900 text-violet-600 dark:text-violet-400 shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                }`}
                            >
                                <BookOpen className="w-3.5 h-3.5" />
                                <span>📚 Конспект и план урока</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => setActiveTab('quiz_gen')}
                                className={`inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                                    activeTab === 'quiz_gen'
                                        ? 'bg-white dark:bg-slate-900 text-violet-600 dark:text-violet-400 shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                }`}
                            >
                                <CheckSquare className="w-3.5 h-3.5" />
                                <span>📝 Генератор тестов и ДЗ (РИКЗ)</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => setActiveTab('methodist')}
                                className={`inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                                    activeTab === 'methodist'
                                        ? 'bg-white dark:bg-slate-900 text-violet-600 dark:text-violet-400 shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                }`}
                            >
                                <MessageSquare className="w-3.5 h-3.5" />
                                <span>💬 ИИ-Методист (Чат)</span>
                            </button>
                        </div>

                        {/* Prompt Library Button */}
                        <button
                            type="button"
                            onClick={() => setIsPromptLibraryOpen(true)}
                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-violet-200 dark:border-violet-800/60 bg-violet-50/70 dark:bg-violet-950/30 hover:bg-violet-100 text-xs font-bold text-violet-700 dark:text-violet-300 transition-all shadow-2xs hover:scale-[1.02]"
                        >
                            <Sparkles className="w-4 h-4 text-violet-600 dark:text-violet-400" />
                            <span>Каталог промптов ЦТ/ЦЭ</span>
                        </button>
                    </div>

                    {/* ═══ 4. TAB CONTENTS ═══ */}
                    {activeTab === 'lesson_plan' && (
                        <LessonPlanTab
                            csrfToken={csrfToken}
                            endpoints={routes}
                            initialSubject={user.subject || 'Математика'}
                            onOpenLibrary={() => setIsPromptLibraryOpen(true)}
                        />
                    )}

                    {activeTab === 'quiz_gen' && (
                        <QuizGeneratorTab
                            csrfToken={csrfToken}
                            endpoints={routes}
                            initialSubject={user.subject || 'Математика'}
                            onOpenLibrary={() => setIsPromptLibraryOpen(true)}
                        />
                    )}

                    {activeTab === 'methodist' && (
                        <MethodistChatTab
                            csrfToken={csrfToken}
                            endpoints={routes}
                            user={user}
                            onOpenLibrary={() => setIsPromptLibraryOpen(true)}
                        />
                    )}
                </>
            )}

            {/* ═══ 5. PROMPT LIBRARY MODAL ═══ */}
            <PromptLibraryModal
                isOpen={isPromptLibraryOpen}
                onClose={() => setIsPromptLibraryOpen(false)}
                onSelectPrompt={handleSelectPrompt}
            />
        </div>
    );
}
