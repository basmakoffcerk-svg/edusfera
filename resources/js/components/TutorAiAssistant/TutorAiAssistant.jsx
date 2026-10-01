import React, { useState } from 'react';
import { 
    Sparkles, 
    BookOpen, 
    CheckSquare, 
    MessageSquare, 
    Crown, 
    ArrowRight, 
    ShieldCheck, 
    Zap,
    Flame
} from 'lucide-react';
import LessonPlanTab from './LessonPlanTab';
import QuizGeneratorTab from './QuizGeneratorTab';
import MethodistChatTab from './MethodistChatTab';
import PromptLibraryModal from './PromptLibraryModal';

export default function TutorAiAssistant() {
    const config = window.EDUSFERA_AI_CONFIG || {};
    const {
        canUseAi = true,
        user = {},
        csrfToken = '',
        routes = {},
    } = config;

    const [activeTab, setActiveTab] = useState('lesson_plan');
    const [isPromptLibraryOpen, setIsPromptLibraryOpen] = useState(false);
    const [appliedPrompt, setAppliedPrompt] = useState(null);

    const handleSelectPrompt = (promptItem) => {
        if (promptItem.targetTab) {
            setActiveTab(promptItem.targetTab);
        }
        setAppliedPrompt(promptItem);
    };

    return (
        <div className="ed-ai-wrap max-w-7xl mx-auto pb-12 font-sans">
            {/* ═══ 1. HERO BANNER В ДИЗАЙН-СИСТЕМЕ EDUSFERA ═══ */}
            <div className="ed-ai-hero-card">
                {/* Background decorative SVG glow */}
                <div className="absolute right-0 top-0 -mr-16 -mt-16 w-80 h-80 rounded-full bg-gradient-to-br from-violet-500/10 via-fuchsia-500/5 to-transparent blur-3xl pointer-events-none"></div>
                <div className="absolute left-1/3 bottom-0 -mb-10 w-64 h-64 rounded-full bg-[#C6FF33]/10 blur-2xl pointer-events-none"></div>

                <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div className="space-y-3 max-w-2xl">
                        {/* Live AI Status Pill */}
                        <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-[#FAF8FF] dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 border border-violet-200/80 dark:border-violet-800/60 shadow-2xs">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span>Google Gemini AI Engine • gemini-3.5-flash</span>
                            <span className="text-[10px] px-1.5 py-0.2 rounded-md bg-[#C6FF33] text-black font-extrabold uppercase">
                                2026
                            </span>
                        </div>

                        {/* Title with Platform Typography */}
                        <h1 className="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-[#0C0A14] dark:text-white font-rimma uppercase">
                            ИИ-Помощник <span className="text-[#7D39EB]">репетитора</span>
                        </h1>

                        <p className="text-xs sm:text-sm leading-relaxed text-slate-600 dark:text-slate-300 font-medium">
                            Методические технологические карты, генерация банка заданий ЦТ/ЦЭ 2026 с ловушками РИКЗ и педагогический копайлот. Экономьте до 5 часов подготовки каждую неделю.
                        </p>
                    </div>

                    {/* Right side: Animated SVG Mascot & Pro Badge */}
                    <div className="flex items-center gap-4 shrink-0">
                        {/* Interactive Animated SVG Diamond Logo */}
                        <div className="hidden sm:flex relative w-20 h-20 items-center justify-center">
                            {/* Rotating Orbit SVG */}
                            <svg className="absolute inset-0 w-full h-full ed-animate-spin-slow" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="42" fill="none" stroke="#7D39EB" strokeWidth="1.5" strokeDasharray="6 8" strokeOpacity="0.4" />
                                <circle cx="92" cy="50" r="4" fill="#C6FF33" />
                            </svg>

                            {/* Floating Edusfera Logo Icon */}
                            <div className="ed-animate-float w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#7D39EB] to-[#632cd6] text-white flex items-center justify-center shadow-lg shadow-violet-500/25">
                                <svg width="32" height="32" viewBox="0 0 64 64" fill="none">
                                    <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                                    <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                                </svg>
                            </div>
                        </div>

                        {/* Status Card */}
                        {canUseAi ? (
                            <div className="p-3.5 rounded-2xl bg-[#FAF8FF] dark:bg-slate-800/80 border border-[#ECE5FB] dark:border-violet-900/50 space-y-1 text-left min-w-[170px]">
                                <div className="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                                    <ShieldCheck className="w-4 h-4 text-emerald-500" />
                                    <span>Тариф Pro</span>
                                    <span className="text-[10px] text-emerald-600 dark:text-emerald-400 font-extrabold">Active</span>
                                </div>
                                <div className="text-[11px] text-slate-500 dark:text-slate-400">
                                    Безлимитные запросы
                                </div>
                            </div>
                        ) : (
                            <a
                                href={routes.subscription || '/admin/tutor-subscription'}
                                className="inline-flex items-center gap-2 px-5 py-3 rounded-2xl text-black font-extrabold text-xs shadow-md transition-all hover:scale-[1.02] active:scale-100"
                                style={{ background: '#C6FF33' }}
                            >
                                <Crown className="w-4 h-4 text-black" />
                                <span>Активировать Pro</span>
                                <ArrowRight className="w-4 h-4" />
                            </a>
                        )}
                    </div>
                </div>
            </div>

            {/* ═══ 2. TABS NAVIGATION & PROMPTS CATALOG ═══ */}
            <div className="flex flex-wrap items-center justify-between gap-3 pt-1">
                {/* Segmented Pill Tabs in Platform Style */}
                <div className="flex items-center p-1.5 rounded-2xl bg-white dark:bg-slate-800 border border-[#ECEEF1] dark:border-slate-700 shadow-2xs">
                    <button
                        type="button"
                        onClick={() => setActiveTab('lesson_plan')}
                        className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                            activeTab === 'lesson_plan'
                                ? 'bg-[#7D39EB] text-white shadow-sm shadow-violet-500/25'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50'
                        }`}
                    >
                        <BookOpen className="w-4 h-4" />
                        <span>Конспект и план урока</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTab('quiz_gen')}
                        className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                            activeTab === 'quiz_gen'
                                ? 'bg-[#7D39EB] text-white shadow-sm shadow-violet-500/25'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50'
                        }`}
                    >
                        <CheckSquare className="w-4 h-4" />
                        <span>Тесты и ДЗ (РИКЗ 2026)</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTab('methodist')}
                        className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                            activeTab === 'methodist'
                                ? 'bg-[#7D39EB] text-white shadow-sm shadow-violet-500/25'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50'
                        }`}
                    >
                        <MessageSquare className="w-4 h-4" />
                        <span>ИИ-Методист (Чат)</span>
                    </button>
                </div>

                {/* Catalog Button */}
                <button
                    type="button"
                    onClick={() => setIsPromptLibraryOpen(true)}
                    className="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-800 border border-violet-200 dark:border-violet-800 text-xs font-bold text-violet-700 dark:text-violet-300 hover:bg-[#FAF8FF] dark:hover:bg-violet-950/40 transition-all shadow-2xs hover:scale-[1.02] cursor-pointer"
                >
                    <Sparkles className="w-4 h-4 text-violet-600 dark:text-violet-400" />
                    <span>Каталог готовых промптов ЦТ/ЦЭ</span>
                </button>
            </div>

            {/* ═══ 3. TAB CONTENT VIEWS ═══ */}
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

            {/* ═══ 4. PROMPT LIBRARY MODAL ═══ */}
            <PromptLibraryModal
                isOpen={isPromptLibraryOpen}
                onClose={() => setIsPromptLibraryOpen(false)}
                onSelectPrompt={handleSelectPrompt}
            />
        </div>
    );
}
