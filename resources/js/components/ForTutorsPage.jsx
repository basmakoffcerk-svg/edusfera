import React, { useState, useMemo } from 'react';
import { 
  CheckCircle2, 
  Sparkles, 
  ShieldCheck, 
  Calendar, 
  Clock, 
  FileText
} from 'lucide-react';
import Footer from './Footer';

export default function ForTutorsPage() {
  // Navigation State
  const [mobileOpen, setMobileOpen] = useState(false);
  const [user] = useState(typeof window !== 'undefined' ? window.EDUSFERA_USER : null);
  
  // Interactive Hero Console Mockup Tab State
  const [heroTab, setHeroTab] = useState('requests'); // 'requests' | 'schedule' | 'npd'
  
  // Interactive Calculator State
  const [rate, setRate] = useState(35);
  const [canceledPerMonth, setCanceledPerMonth] = useState(3);
  
  // Tariffs State
  const [yearly, setYearly] = useState(false);
  
  // FAQ State
  const [openFaq, setOpenFaq] = useState(0);

  // Calculator computations (9 active school months)
  const calcResults = useMemo(() => {
    const months = 9;
    const lossYear = rate * canceledPerMonth * months;
    const proCostYear = 480;
    const netSaved = Math.max(0, lossYear - proCostYear);
    return { lossYear, proCostYear, netSaved };
  }, [rate, canceledPerMonth]);

  const scrollToSection = (id) => {
    const el = document.getElementById(id);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' });
    }
    setMobileOpen(false);
  };

  return (
    <div className="min-h-screen bg-[#010101] text-white selection:bg-[#C6FF33] selection:text-black font-['Montserrat',sans-serif] antialiased overflow-x-hidden">
      
      {/* ─── HEADER / NAVIGATION (Liquid Glass Island: Canonical Logo + Tutor Badge + Glass 3 Tabs + Glass Login) ─── */}
      <header className="sticky top-0 w-full z-50 bg-gradient-to-b from-[#010101]/85 via-[#010101]/65 to-[#010101]/25 backdrop-blur-2xl border-b border-white/[0.1] py-3.5 sm:py-4 transition-all duration-300 shadow-[0_10px_35px_-10px_rgba(0,0,0,0.8)]">
        <div className="max-w-7xl mx-auto px-5 sm:px-8 lg:px-12 flex items-center justify-between">
          
          {/* Brand Mark (Canonical Design System Logo + Tutor Tag) */}
          <div className="flex items-center gap-3">
            <a href="/" className="flex items-center gap-2.5 text-white transition-colors group">
              <svg width="28" height="28" viewBox="0 0 64 64" className="w-7 h-7 rounded-lg shadow-sm group-hover:scale-105 transition-transform">
                <rect width="64" height="64" rx="14" fill="#7D39EB" />
                <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
              </svg>
              <span className="text-xl font-bold tracking-tight text-white uppercase font-rimma">
                edusfera
              </span>
            </a>

            {/* Liquid Glass Badge: Репетиторам */}
            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gradient-to-r from-violet-500/20 via-white/[0.08] to-[#C6FF33]/15 border border-white/20 text-[#C6FF33] font-bold text-[11px] uppercase tracking-wider backdrop-blur-xl shadow-inner">
              <span className="w-1.5 h-1.5 rounded-full bg-[#C6FF33] animate-pulse"></span>
              Репетиторам
            </span>
          </div>

          {/* Center: Floating Liquid Glass Pill Nav */}
          <nav className="hidden md:flex items-center">
            <div className="rounded-full bg-gradient-to-b from-white/[0.14] to-white/[0.04] backdrop-blur-2xl px-2 py-1.5 flex items-center gap-1 border border-white/[0.18] shadow-[0_8px_32px_rgba(0,0,0,0.5),inset_0_1px_1px_rgba(255,255,255,0.25)] ring-1 ring-white/10">
              <button 
                onClick={() => scrollToSection('pains')} 
                className="rounded-full px-5 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/15 transition-all cursor-pointer"
              >
                Преимущества
              </button>
              <button 
                onClick={() => scrollToSection('calculator')} 
                className="rounded-full px-5 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/15 transition-all cursor-pointer"
              >
                Калькулятор
              </button>
              <button 
                onClick={() => scrollToSection('pricing')} 
                className="rounded-full px-5 py-2 text-sm font-medium text-white/90 hover:text-white hover:bg-white/15 transition-all cursor-pointer"
              >
                Тарифы
              </button>
            </div>
          </nav>

          {/* Right: Liquid Glass Action / Login Button */}
          <div className="hidden sm:flex items-center gap-3">
            {user ? (
              <a 
                href={user.role === 'admin' ? '/site-admin' : '/admin'} 
                className="rounded-full bg-gradient-to-b from-white/[0.15] to-white/[0.05] hover:from-white/[0.22] hover:to-white/[0.1] text-white font-medium text-sm px-5 py-2 border border-white/20 backdrop-blur-xl transition-all flex items-center gap-2 shadow-lg"
              >
                <span className="w-2 h-2 rounded-full bg-[#C6FF33] animate-pulse"></span>
                <span>Личный кабинет</span>
              </a>
            ) : (
              <a 
                href="/login" 
                className="rounded-full bg-gradient-to-b from-white/[0.15] to-white/[0.05] hover:from-white/[0.22] hover:to-white/[0.1] text-white font-medium text-sm px-5 py-2 border border-white/20 backdrop-blur-xl transition-all flex items-center gap-2 shadow-[0_4px_20px_rgba(0,0,0,0.4),inset_0_1px_0_rgba(255,255,255,0.3)]"
              >
                <span>Войти</span>
              </a>
            )}
          </div>

          {/* Mobile Hamburger Button */}
          <div className="md:hidden">
            <button 
              onClick={() => setMobileOpen(!mobileOpen)} 
              aria-label="Меню"
              className="w-10 h-10 rounded-xl border border-white/20 bg-white/10 flex items-center justify-center text-white hover:bg-white/15 active:scale-95 transition-all backdrop-blur-xl"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {mobileOpen ? (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                ) : (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                )}
              </svg>
            </button>
          </div>

        </div>

        {/* Mobile Glass Drawer Menu */}
        {mobileOpen && (
          <div className="md:hidden border-t border-white/10 bg-[#010101]/95 backdrop-blur-2xl px-6 py-6 flex flex-col gap-3 text-base font-semibold">
            <button 
              onClick={() => scrollToSection('pains')} 
              className="text-left px-4 py-3 rounded-xl text-slate-200 hover:text-[#C6FF33] hover:bg-white/10 transition-colors"
            >
              Преимущества
            </button>
            <button 
              onClick={() => scrollToSection('calculator')} 
              className="text-left px-4 py-3 rounded-xl text-slate-200 hover:text-[#C6FF33] hover:bg-white/10 transition-colors"
            >
              Калькулятор
            </button>
            <button 
              onClick={() => scrollToSection('pricing')} 
              className="text-left px-4 py-3 rounded-xl text-slate-200 hover:text-[#C6FF33] hover:bg-white/10 transition-colors"
            >
              Тарифы
            </button>
            <div className="h-px bg-white/10 my-2"></div>
            <a 
              href="/login"
              className="px-4 py-3 rounded-xl text-[#C6FF33] hover:bg-white/10 transition-colors"
            >
              Войти в личный кабинет →
            </a>
          </div>
        )}
      </header>

      {/* ─── BLOCK 01: HERO SECTION ─── */}
      <section className="relative pt-12 pb-20 md:pt-20 md:pb-32 border-b border-white/10 bg-[radial-gradient(circle_at_50%_-10%,rgba(125,57,235,0.22)_0%,transparent_60%)] overflow-hidden">
        <div className="max-w-7xl mx-auto px-6 sm:px-10 lg:px-12 relative z-10">
          <div className="grid lg:grid-cols-12 gap-12 lg:gap-16 items-center">
            
            {/* Left Narrative Column */}
            <div className="lg:col-span-7 flex flex-col items-start text-left">
              
              {/* Liquid Badge */}
              <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-white/[0.12] via-white/[0.06] to-transparent border border-white/20 text-[#C6FF33] font-bold text-xs uppercase tracking-wider mb-6 backdrop-blur-2xl shadow-lg">
                <span className="w-2 h-2 rounded-full bg-[#C6FF33] animate-pulse"></span>
                ПЛАТФОРМА ДЛЯ НАСТОЯЩИХ ПРОФИ · БЕЛАРУСЬ 2026
              </div>

              <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-[1.06] mb-6">
                Платформа для настоящих профи. <br />
                <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#C6FF33] via-lime-200 to-white">
                  Больше не ищите учеников. Преподавайте.
                </span>
              </h1>

              <p className="text-base sm:text-lg text-slate-300 font-normal leading-relaxed mb-8 max-w-2xl">
                Edusfera — платформа для настоящих профи. Мы приводим заявки от родителей в вашем районе, ведём умное расписание, автоматически напоминаем ученикам о занятиях и формируем отчёты для НПД. Вы тратите время только на оплачиваемые уроки.
              </p>

              {/* Economic Reframe Card */}
              <div className="w-full bg-slate-900/60 border-l-4 border-l-[#C6FF33] border-y border-r border-slate-800/80 rounded-r-2xl p-5 sm:p-6 mb-8 max-w-2xl shadow-xl backdrop-blur-xl">
                <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-[#C6FF33] mb-1.5">
                  <Sparkles className="w-3.5 h-3.5" /> ЭКОНОМИЧЕСКИЙ РЕФРЕЙМ
                </div>
                <div className="text-sm sm:text-base font-medium text-slate-200 leading-snug">
                  Подписка стоит как <strong>один ваш урок</strong> (40 BYN). Всего один найденный ученик окупает <strong>целый год подписки</strong>.
                </div>
              </div>

              {/* CTAs */}
              <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 mb-10 w-full sm:w-auto">
                <a 
                  href="/register?role=tutor&plan=pro" 
                  className="inline-flex items-center justify-center font-bold text-sm sm:text-base py-4 px-8 rounded-full bg-[#C6FF33] hover:bg-[#d4ff59] text-black shadow-[0_0_30px_rgba(198,255,51,0.3)] hover:scale-[1.02] active:scale-[0.98] transition-all"
                >
                  Занять место в своей нише →
                </a>
                <button 
                  onClick={() => scrollToSection('calculator')}
                  className="inline-flex items-center justify-center font-bold text-sm sm:text-base py-4 px-7 rounded-full bg-white/5 hover:bg-white/10 border border-white/15 text-white transition-all cursor-pointer"
                >
                  Рассчитать потери ↓
                </button>
              </div>

              {/* Trust Metrics */}
              <div className="grid grid-cols-3 gap-3 sm:gap-6 pt-6 border-t border-slate-800 w-full max-w-2xl text-left">
                <div>
                  <div className="text-xs text-slate-500 uppercase font-semibold mb-1">Условия</div>
                  <div className="text-xs sm:text-sm font-bold text-white">1 мес. бесплатно</div>
                  <div className="text-[11px] text-slate-400 mt-0.5">На любом тарифе</div>
                </div>
                <div className="border-l border-slate-800 pl-3 sm:pl-6">
                  <div className="text-xs text-slate-500 uppercase font-semibold mb-1">Привилегия</div>
                  <div className="text-xs sm:text-sm font-bold text-white">Статус Основателя</div>
                  <div className="text-[11px] text-slate-400 mt-0.5">Фиксация цены</div>
                </div>
                <div className="border-l border-slate-800 pl-3 sm:pl-6">
                  <div className="text-xs text-slate-500 uppercase font-semibold mb-1">Гарантия</div>
                  <div className="text-xs sm:text-sm font-bold text-white">Заявки родителей</div>
                  <div className="text-[11px] text-slate-400 mt-0.5">Или продление 0 BYN</div>
                </div>
              </div>

            </div>

            {/* Right Visual Column: Interactive Dark Tech Console Mockup */}
            <div className="lg:col-span-5 w-full">
              <div className="bg-[#0B0F19]/90 rounded-3xl p-6 sm:p-8 border border-slate-800 shadow-[0_25px_60px_rgba(0,0,0,0.8)] relative overflow-hidden backdrop-blur-2xl">
                
                {/* Backlight glow */}
                <div className="absolute -top-20 -right-20 w-40 h-40 bg-[#7D39EB]/25 rounded-full blur-3xl pointer-events-none"></div>
                <div className="absolute -bottom-20 -left-20 w-40 h-40 bg-[#C6FF33]/15 rounded-full blur-3xl pointer-events-none"></div>

                {/* Console Top Switcher */}
                <div className="flex items-center justify-between pb-4 mb-6 border-b border-slate-800 relative z-10">
                  <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full bg-rose-500/80"></div>
                    <div className="w-3 h-3 rounded-full bg-amber-500/80"></div>
                    <div className="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                    <span className="text-xs font-bold uppercase tracking-wider text-slate-300 ml-2">Кабинет Edusfera</span>
                  </div>
                  <span className="text-[11px] font-bold text-black bg-[#C6FF33] px-2.5 py-0.5 rounded-full">
                    LIVE
                  </span>
                </div>

                {/* Interactive Mockup Tabs */}
                <div className="grid grid-cols-3 gap-2 mb-6 relative z-10">
                  <button 
                    onClick={() => setHeroTab('requests')}
                    className={`py-2 px-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                      heroTab === 'requests' 
                        ? 'bg-[#C6FF33] text-black shadow-lg shadow-[#C6FF33]/20' 
                        : 'bg-slate-800/60 text-slate-400 hover:text-white hover:bg-slate-800'
                    }`}
                  >
                    Заявки (2)
                  </button>
                  <button 
                    onClick={() => setHeroTab('schedule')}
                    className={`py-2 px-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                      heroTab === 'schedule' 
                        ? 'bg-[#C6FF33] text-black shadow-lg shadow-[#C6FF33]/20' 
                        : 'bg-slate-800/60 text-slate-400 hover:text-white hover:bg-slate-800'
                    }`}
                  >
                    Расписание
                  </button>
                  <button 
                    onClick={() => setHeroTab('npd')}
                    className={`py-2 px-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                      heroTab === 'npd' 
                        ? 'bg-[#C6FF33] text-black shadow-lg shadow-[#C6FF33]/20' 
                        : 'bg-slate-800/60 text-slate-400 hover:text-white hover:bg-slate-800'
                    }`}
                  >
                    Чек НПД
                  </button>
                </div>

                {/* Dynamic Content Display */}
                <div className="space-y-3 relative z-10 min-h-[220px]">
                  {heroTab === 'requests' && (
                    <div className="space-y-3">
                      <div className="p-4 rounded-2xl bg-slate-900/80 border border-slate-700/60 flex items-start justify-between gap-3">
                        <div>
                          <div className="flex items-center gap-2 mb-1">
                            <span className="w-2 h-2 rounded-full bg-[#C6FF33]"></span>
                            <span className="text-xs font-bold text-white">Новая заявка · Математика ЦЭ/ЦТ</span>
                          </div>
                          <p className="text-xs text-slate-400">Родитель: Ольга В. · Минск, Первомайский р-н</p>
                        </div>
                        <span className="text-[11px] font-bold text-[#C6FF33] bg-[#C6FF33]/10 px-2 py-0.5 rounded-md">
                          +70 BYN/нед
                        </span>
                      </div>
                      <div className="p-4 rounded-2xl bg-slate-900/80 border border-slate-700/60 flex items-start justify-between gap-3">
                        <div>
                          <div className="flex items-center gap-2 mb-1">
                            <span className="w-2 h-2 rounded-full bg-violet-400"></span>
                            <span className="text-xs font-bold text-white">Новая заявка · Физика 11 класс</span>
                          </div>
                          <p className="text-xs text-slate-400">Абитуриент: Максим К. · Онлайн класс</p>
                        </div>
                        <span className="text-[11px] font-bold text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded-md">
                          +80 BYN/нед
                        </span>
                      </div>
                    </div>
                  )}

                  {heroTab === 'schedule' && (
                    <div className="space-y-3">
                      <div className="p-4 rounded-2xl bg-slate-900/80 border border-slate-700/60">
                        <div className="flex items-center justify-between text-xs font-bold text-white mb-2">
                          <span className="flex items-center gap-1.5"><Calendar className="w-4 h-4 text-[#C6FF33]" /> Сегодня, 16:00</span>
                          <span className="text-emerald-400">Подтверждено</span>
                        </div>
                        <p className="text-xs text-slate-300">Урок: Тригонометрия с Анной М. (Автонапоминание отправлено)</p>
                      </div>
                      <div className="p-4 rounded-2xl bg-slate-900/80 border border-slate-700/60">
                        <div className="flex items-center justify-between text-xs font-bold text-white mb-2">
                          <span className="flex items-center gap-1.5"><Clock className="w-4 h-4 text-[#C6FF33]" /> Сегодня, 18:00</span>
                          <span className="text-emerald-400">Оплачено (Эскроу)</span>
                        </div>
                        <p className="text-xs text-slate-300">Урок: Геометрия с Дмитрием В.</p>
                      </div>
                    </div>
                  )}

                  {heroTab === 'npd' && (
                    <div className="p-5 rounded-2xl bg-slate-900/90 border border-slate-700/60 space-y-3">
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-bold text-white flex items-center gap-2">
                          <FileText className="w-4 h-4 text-[#C6FF33]" /> Электронный чек № 2026-0841
                        </span>
                        <span className="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded">МНС РБ</span>
                      </div>
                      <div className="text-xs text-slate-300 space-y-1">
                        <div>Услуга: Репетиторские услуги (НПД 10%)</div>
                        <div>Сумма: <strong>40.00 BYN</strong> · Налог: <strong>4.00 BYN</strong></div>
                      </div>
                      <div className="text-[11px] text-slate-400 pt-2 border-t border-slate-800">
                        ✓ Чек автоматически передан в приложение «Профдоход»
                      </div>
                    </div>
                  )}
                </div>

                {/* Bottom live stats footer */}
                <div className="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400 relative z-10">
                  <span>Сэкономлено: <strong>12 часов/мес</strong></span>
                  <span className="text-[#C6FF33] font-bold">Выручка: +1 440 BYN/мес</span>
                </div>

              </div>
            </div>

          </div>
        </div>
      </section>

      {/* ─── BLOCK 02 & 03: PROBLEMS VS EDUSFERA SOLUTIONS ─── */}
      <section id="pains" className="py-20 md:py-28 px-6 sm:px-10 lg:px-12 bg-[#05070E] border-b border-white/10">
        <div className="max-w-7xl mx-auto">
          
          <div className="text-center max-w-3xl mx-auto mb-16">
            <span className="inline-block px-3.5 py-1 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-[#C6FF33] font-bold text-xs uppercase tracking-wider mb-4">
              ПРЕИМУЩЕСТВА ПЛАТФОРМЫ
            </span>
            <h2 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white mb-6">
              Почему успешные репетиторы <br />
              выбирают <span className="text-[#C6FF33]">Edusfera</span>
            </h2>
            <p className="text-base text-slate-400">
              Сравните привычный хаос бесплатных досок с системной средой для профессионального преподавания
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            
            {/* Card 1 */}
            <div className="bg-slate-900/50 rounded-3xl p-8 border border-slate-800 hover:border-[#C6FF33]/40 transition-all hover:-translate-y-1">
              <div className="text-xs font-bold uppercase tracking-wider text-rose-400 mb-2">Проблема 01</div>
              <h3 className="text-xl font-bold text-white mb-4">Пустые окна и сорванные уроки</h3>
              <p className="text-sm text-slate-400 leading-relaxed mb-6">
                Ученик забыл предупредить или отменил за полчаса. Вы потеряли 40–80 BYN и час времени, который уже ничем не заполнить.
              </p>
              <div className="p-4 rounded-2xl bg-[#0B0F19] border border-slate-800 text-xs text-slate-300">
                <strong className="text-[#C6FF33] block mb-1">Решение Edusfera:</strong>
                Автонапоминания в Telegram/SMS и бронирование слотов через платформу с гарантией сохранности оплаты.
              </div>
            </div>

            {/* Card 2 */}
            <div className="bg-slate-900/50 rounded-3xl p-8 border border-slate-800 hover:border-[#C6FF33]/40 transition-all hover:-translate-y-1">
              <div className="text-xs font-bold uppercase tracking-wider text-amber-400 mb-2">Проблема 02</div>
              <h3 className="text-xl font-bold text-white mb-4">Бесконечная рутина и чеки</h3>
              <p className="text-sm text-slate-400 leading-relaxed mb-6">
                Постоянные переписки в мессенджерах, сверка оплат, ручное формирование чеков в приложении НПД и путаница в расписании.
              </p>
              <div className="p-4 rounded-2xl bg-[#0B0F19] border border-slate-800 text-xs text-slate-300">
                <strong className="text-[#C6FF33] block mb-1">Решение Edusfera:</strong>
                Единый автопилот: умный календарь, авто-формирование чеков для налоговой и статистика дохода за 1 клик.
              </div>
            </div>

            {/* Card 3 */}
            <div className="bg-slate-900/50 rounded-3xl p-8 border border-slate-800 hover:border-[#C6FF33]/40 transition-all hover:-translate-y-1">
              <div className="text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">Проблема 03</div>
              <h3 className="text-xl font-bold text-white mb-4">Демпинг на досках объявлений</h3>
              <p className="text-sm text-slate-400 leading-relaxed mb-6">
                На сайтах объявлений ваш профиль теряется среди тысяч случайных людей, где клиенты смотрят только на самую низкую цену.
              </p>
              <div className="p-4 rounded-2xl bg-[#0B0F19] border border-slate-800 text-xs text-slate-300">
                <strong className="text-[#C6FF33] block mb-1">Решение Edusfera:</strong>
                Премиальный профиль с верификацией в госреестре РБ, бейджем проверенного преподавателя и прямым потоком платежеспособных родителей.
              </div>
            </div>

          </div>

        </div>
      </section>

      {/* ─── BLOCK 04: INTERACTIVE SWISS-ENGINE CALCULATOR ─── */}
      <section id="calculator" className="py-20 md:py-28 px-6 sm:px-10 lg:px-12 bg-[#010101] border-b border-white/10 relative">
        <div className="max-w-5xl mx-auto">
          
          <div className="text-center max-w-2xl mx-auto mb-14">
            <span className="inline-block px-3.5 py-1 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-[#C6FF33] font-bold text-xs uppercase tracking-wider mb-4">
              АНАЛОГОВЫЙ КАЛЬКУЛЯТОР ПОТЕРЬ
            </span>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-white mb-4">
              Сколько вы теряете <br />
              <span className="text-[#C6FF33]">без умной платформы?</span>
            </h2>
            <p className="text-sm sm:text-base text-slate-400">
              Подвигайте ползунки и посмотрите расчет упущенной выгоды за учебный год
            </p>
          </div>

          <div className="bg-[#0B0F19] rounded-3xl p-8 sm:p-12 border border-slate-800 shadow-2xl">
            <div className="grid md:grid-cols-2 gap-10 lg:gap-14 items-center">
              
              {/* Sliders Side */}
              <div className="space-y-8">
                
                {/* Slider 1 */}
                <div>
                  <div className="flex justify-between items-center mb-3">
                    <label className="text-sm font-bold text-slate-200">
                      Ваша ставка за 1 урок (60 мин)
                    </label>
                    <span className="text-lg font-black text-[#C6FF33] bg-black/60 border border-slate-800 px-3 py-1 rounded-xl">
                      {rate} BYN
                    </span>
                  </div>
                  <input 
                    type="range" 
                    min="20" 
                    max="100" 
                    step="5"
                    value={rate}
                    onChange={(e) => setRate(Number(e.target.value))}
                    className="w-full accent-[#C6FF33] h-2 bg-slate-800 rounded-lg cursor-pointer"
                  />
                  <div className="flex justify-between text-[11px] text-slate-500 mt-2 font-medium">
                    <span>20 BYN</span>
                    <span>50 BYN</span>
                    <span>100 BYN</span>
                  </div>
                </div>

                {/* Slider 2 */}
                <div>
                  <div className="flex justify-between items-center mb-3">
                    <label className="text-sm font-bold text-slate-200">
                      Отмененных / сорванных уроков в месяц
                    </label>
                    <span className="text-lg font-black text-rose-400 bg-black/60 border border-slate-800 px-3 py-1 rounded-xl">
                      {canceledPerMonth} ур./мес
                    </span>
                  </div>
                  <input 
                    type="range" 
                    min="1" 
                    max="10" 
                    step="1"
                    value={canceledPerMonth}
                    onChange={(e) => setCanceledPerMonth(Number(e.target.value))}
                    className="w-full accent-rose-500 h-2 bg-slate-800 rounded-lg cursor-pointer"
                  />
                  <div className="flex justify-between text-[11px] text-slate-500 mt-2 font-medium">
                    <span>1 урок</span>
                    <span>5 уроков</span>
                    <span>10 уроков</span>
                  </div>
                </div>

              </div>

              {/* Profit / Loss Output Side */}
              <div className="bg-slate-900/70 p-6 sm:p-8 rounded-2xl border border-slate-800 space-y-5 text-left">
                
                <div>
                  <div className="text-xs uppercase font-bold text-slate-400 mb-1">Потери за учебный год (9 мес):</div>
                  <div className="text-2xl sm:text-3xl font-black text-rose-400">
                    − {calcResults.lossYear.toLocaleString('ru-RU')} BYN
                  </div>
                </div>

                <div className="pt-4 border-t border-slate-800/80">
                  <div className="text-xs uppercase font-bold text-slate-400 mb-1">Стоимость подписки Pro:</div>
                  <div className="text-lg font-bold text-white">480 BYN / год (40 BYN/мес)</div>
                  <div className="text-xs text-[#C6FF33] font-semibold mt-0.5">1-й месяц: 0 BYN (Бесплатно)</div>
                </div>

                <div className="pt-4 border-t border-slate-800/80 bg-[#C6FF33]/5 -mx-6 sm:-mx-8 p-6 sm:p-8 -mb-6 sm:-mb-8 rounded-b-2xl border-t border-[#C6FF33]/20">
                  <div className="text-xs uppercase font-bold text-[#C6FF33] mb-1">Чистая сохранённая выгода:</div>
                  <div className="text-3xl sm:text-4xl font-black text-[#C6FF33]">
                    + {calcResults.netSaved.toLocaleString('ru-RU')} BYN
                  </div>
                  <p className="text-xs text-slate-400 mt-2">
                    Платформа окупает себя в первый же месяц за счёт снижения отмен и нового потока учеников.
                  </p>
                </div>

              </div>

            </div>
          </div>

        </div>
      </section>

      {/* ─── BLOCK 06: TARIFFS MATRIX ─── */}
      <section id="pricing" className="py-20 md:py-28 px-6 sm:px-10 lg:px-12 bg-[#05070E] border-b border-white/10">
        <div className="max-w-7xl mx-auto">
          
          <div className="text-center max-w-2xl mx-auto mb-14">
            <span className="inline-block px-3.5 py-1 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-[#C6FF33] font-bold text-xs uppercase tracking-wider mb-4">
              ТАРИФНАЯ СЕТКА 2026
            </span>
            <h2 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white mb-6">
              Прозрачные тарифы <br />
              <span className="text-[#C6FF33]">без скрытых платежей</span>
            </h2>
            <p className="text-sm sm:text-base text-slate-400 mb-8">
              Первый месяц бесплатно на любом тарифе. Отмена в 1 клик в любое время.
            </p>

            {/* Toggle Month / Year */}
            <div className="inline-flex items-center bg-slate-900 p-1.5 rounded-full border border-slate-800">
              <button 
                onClick={() => setYearly(false)}
                className={`px-5 py-2 rounded-full text-xs font-bold transition-all cursor-pointer ${
                  !yearly ? 'bg-[#C6FF33] text-black shadow-md' : 'text-slate-400 hover:text-white'
                }`}
              >
                Оплата помесячно
              </button>
              <button 
                onClick={() => setYearly(true)}
                className={`px-5 py-2 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer ${
                  yearly ? 'bg-[#C6FF33] text-black shadow-md' : 'text-slate-400 hover:text-white'
                }`}
              >
                <span>Оплата за год</span>
                <span className="bg-black text-[#C6FF33] px-2 py-0.5 rounded-md text-[10px] font-extrabold">−20%</span>
              </button>
            </div>

          </div>

          <div className="grid md:grid-cols-3 gap-8 items-stretch">
            
            {/* Basic */}
            <div className="bg-slate-900/50 rounded-3xl p-8 border border-slate-800 flex flex-col justify-between hover:border-slate-700 transition-all">
              <div>
                <div className="text-xs uppercase font-bold text-slate-400 mb-2">Для старта</div>
                <h3 className="text-2xl font-bold text-white mb-2">Basic</h3>
                <p className="text-xs text-slate-400 mb-6">Для начинающих репетиторов с небольшой базой</p>
                <div className="mb-8">
                  <span className="text-4xl font-black text-white">{yearly ? '16' : '20'} BYN</span>
                  <span className="text-xs text-slate-400 ml-2">/ месяц</span>
                  {yearly && <div className="text-[11px] text-slate-500 mt-1">192 BYN / год при оплате за год</div>}
                </div>
                <ul className="space-y-3 text-xs text-slate-300 mb-8 text-left">
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> До 5 активных учеников</li>
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> Умное расписание и календарь</li>
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> Авто-напоминания ученикам</li>
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> Формирование отчетов НПД</li>
                </ul>
              </div>
              <a 
                href="/register?role=tutor&plan=basic"
                className="w-full py-3.5 px-6 rounded-full bg-white/5 hover:bg-white/10 border border-white/15 text-white font-bold text-xs text-center transition-all"
              >
                Выбрать Basic (1 мес. 0 BYN)
              </a>
            </div>

            {/* Pro (Leader) */}
            <div className="bg-[#0B0F19] rounded-3xl p-8 border-2 border-[#C6FF33] flex flex-col justify-between relative shadow-[0_0_40px_rgba(198,255,51,0.15)] md:-translate-y-2">
              <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-[#C6FF33] text-black font-extrabold text-[11px] uppercase tracking-wider px-4 py-1 rounded-full shadow-md">
                Выбор большинства (82%)
              </div>
              <div>
                <div className="text-xs uppercase font-bold text-[#C6FF33] mb-2 mt-2">Оптимальный</div>
                <h3 className="text-2xl font-bold text-white mb-2">Pro</h3>
                <p className="text-xs text-slate-400 mb-6">Для практикующих репетиторов с полной загрузкой</p>
                <div className="mb-8">
                  <span className="text-4xl font-black text-[#C6FF33]">{yearly ? '32' : '40'} BYN</span>
                  <span className="text-xs text-slate-400 ml-2">/ месяц</span>
                  {yearly && <div className="text-[11px] text-slate-500 mt-1">384 BYN / год при оплате за год</div>}
                </div>
                <ul className="space-y-3 text-xs text-slate-200 mb-8 text-left font-medium">
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> <strong>Неограниченно</strong> учеников</li>
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> <strong>Приоритет в каталоге</strong> репетиторов</li>
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> <strong>Приём заявок от родителей</strong></li>
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> Эскроу-защита оплаты уроков</li>
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> Гарантия: продление 0 BYN без заявок</li>
                </ul>
              </div>
              <a 
                href="/register?role=tutor&plan=pro"
                className="w-full py-4 px-6 rounded-full bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-xs text-center shadow-[0_0_25px_rgba(198,255,51,0.3)] transition-all"
              >
                Выбрать Pro (1 мес. 0 BYN) →
              </a>
            </div>

            {/* Premium */}
            <div className="bg-slate-900/50 rounded-3xl p-8 border border-slate-800 flex flex-col justify-between hover:border-slate-700 transition-all">
              <div>
                <div className="text-xs uppercase font-bold text-violet-400 mb-2">Максимум</div>
                <h3 className="text-2xl font-bold text-white mb-2">Premium</h3>
                <p className="text-xs text-slate-400 mb-6">Для топ-репетиторов и авторских мини-групп</p>
                <div className="mb-8">
                  <span className="text-4xl font-black text-white">{yearly ? '48' : '60'} BYN</span>
                  <span className="text-xs text-slate-400 ml-2">/ месяц</span>
                  {yearly && <div className="text-[11px] text-slate-500 mt-1">576 BYN / год при оплате за год</div>}
                </div>
                <ul className="space-y-3 text-xs text-slate-300 mb-8 text-left">
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> Всё, что входит в тариф Pro</li>
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> <strong>ТОП-1 позиция</strong> в выдаче каталога</li>
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> Запуск и ведение <strong>мини-групп</strong></li>
                  <li className="flex items-center gap-2"><CheckCircle2 className="w-4 h-4 text-[#C6FF33] shrink-0" /> Персональный менеджер поддержки</li>
                </ul>
              </div>
              <a 
                href="/register?role=tutor&plan=premium"
                className="w-full py-3.5 px-6 rounded-full bg-white/5 hover:bg-white/10 border border-white/15 text-white font-bold text-xs text-center transition-all"
              >
                Выбрать Premium (1 мес. 0 BYN)
              </a>
            </div>

          </div>

          {/* Guarantee & Quota Bar */}
          <div className="mt-14 p-6 sm:p-8 rounded-2xl bg-[#0B0F19] border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6 text-left">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-2xl bg-[#C6FF33]/10 border border-[#C6FF33]/30 flex items-center justify-center text-[#C6FF33] shrink-0">
                <ShieldCheck className="w-6 h-6" />
              </div>
              <div>
                <h4 className="text-base font-bold text-white">30 дней без заявок — продление 0 BYN</h4>
                <p className="text-xs text-slate-400">Если на тарифе Pro вы не получите заявок за месяц, доступ продлится бесплатно.</p>
              </div>
            </div>
            <div className="shrink-0 text-center md:text-right">
              <span className="text-xs font-bold text-[#C6FF33] bg-[#C6FF33]/10 px-3.5 py-1.5 rounded-full border border-[#C6FF33]/30">
                Осталось 38 из 50 мест
              </span>
            </div>
          </div>

        </div>
      </section>

      {/* ─── BLOCK 09: FAQ ACCORDION ─── */}
      <section id="faq" className="py-20 md:py-28 px-6 sm:px-10 lg:px-12 bg-[#010101] border-b border-white/10">
        <div className="max-w-4xl mx-auto">
          
          <div className="text-center max-w-2xl mx-auto mb-14">
            <span className="inline-block px-3.5 py-1 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-[#C6FF33] font-bold text-xs uppercase tracking-wider mb-4">
              ЧАСТЫЕ ВОПРОСЫ
            </span>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-white mb-4">
              Ответы на главные вопросы
            </h2>
          </div>

          <div className="space-y-4">
            
            {/* FAQ 1 */}
            <div className="bg-slate-900/50 rounded-2xl border border-slate-800 overflow-hidden">
              <button 
                onClick={() => setOpenFaq(openFaq === 1 ? 0 : 1)}
                className="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base cursor-pointer"
              >
                <span>01. Зачем платить подписку, если есть бесплатные доски объявлений?</span>
                <span className="text-[#C6FF33] text-xl font-bold ml-4">{openFaq === 1 ? '−' : '+'}</span>
              </button>
              {openFaq === 1 && (
                <div className="px-6 pb-6 text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4">
                  Бесплатные доски объявлений создают иллюзию экономии. На них ваш профиль конкурирует по демпингу, а рутина с отменами уроков отнимает сотни рублей каждый месяц. Edusfera — это рабочий инструмент, который окупается с первого ученика и защищает ваш доход.
                </div>
              )}
            </div>

            {/* FAQ 2 */}
            <div className="bg-slate-900/50 rounded-2xl border border-slate-800 overflow-hidden">
              <button 
                onClick={() => setOpenFaq(openFaq === 2 ? 0 : 2)}
                className="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base cursor-pointer"
              >
                <span>02. У меня уже есть ученики по сарафанному радио. Зачем мне Edusfera?</span>
                <span className="text-[#C6FF33] text-xl font-bold ml-4">{openFaq === 2 ? '−' : '+'}</span>
              </button>
              {openFaq === 2 && (
                <div className="px-6 pb-6 text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4">
                  Сарафанное радио имеет сезонные провалы. Платформа даёт стабильный управляемый поток новых заявок, мгновенно закрывает освободившиеся окна и автоматизирует рутину с напоминаниями и чеками НПД.
                </div>
              )}
            </div>

            {/* FAQ 3 */}
            <div className="bg-slate-900/50 rounded-2xl border border-slate-800 overflow-hidden">
              <button 
                onClick={() => setOpenFaq(openFaq === 3 ? 0 : 3)}
                className="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base cursor-pointer"
              >
                <span>03. Не дорого ли платить каждый месяц?</span>
                <span className="text-[#C6FF33] text-xl font-bold ml-4">{openFaq === 3 ? '−' : '+'}</span>
              </button>
              {openFaq === 3 && (
                <div className="px-6 pb-6 text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4">
                  Стоимость тарифа Pro (40 BYN) равна ровно <strong>одному уроку</strong>. При этом один найденный ученик приносит 300–400 BYN в месяц, окупая подписку на год вперёд. Первый месяц — бесплатно.
                </div>
              )}
            </div>

            {/* FAQ 4 */}
            <div className="bg-slate-900/50 rounded-2xl border border-slate-800 overflow-hidden">
              <button 
                onClick={() => setOpenFaq(openFaq === 4 ? 0 : 4)}
                className="w-full p-6 text-left flex justify-between items-center font-bold text-white text-base cursor-pointer"
              >
                <span>04. Что делать, если за месяц не поступит ни одной заявки?</span>
                <span className="text-[#C6FF33] text-xl font-bold ml-4">{openFaq === 4 ? '−' : '+'}</span>
              </button>
              {openFaq === 4 && (
                <div className="px-6 pb-6 text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4">
                  Для тарифа Pro действует гарантия: если за оплаченный период вы не получили заявок, доступ автоматически продлевается бесплатно на следующий месяц.
                </div>
              )}
            </div>

          </div>

        </div>
      </section>

      {/* ─── FINAL CTA SLAB ─── */}
      <section className="py-20 md:py-28 px-6 sm:px-10 lg:px-12 bg-[#010101]">
        <div className="max-w-5xl mx-auto bg-gradient-to-b from-[#0B0F19] to-black p-10 sm:p-16 rounded-3xl border border-slate-800 text-center shadow-2xl relative overflow-hidden">
          <div className="relative z-10 max-w-2xl mx-auto">
            <span className="inline-block px-3.5 py-1 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-[#C6FF33] font-bold text-xs uppercase tracking-wider mb-4">
              СТАРТ БЕЗ РИСКОВ
            </span>
            <h2 className="text-3xl sm:text-5xl font-extrabold text-white mb-6">
              Займите место в своей <span className="text-[#C6FF33]">нише</span>
            </h2>
            <p className="text-base text-slate-300 mb-10 leading-relaxed">
              Первый месяц бесплатно на тарифе Basic, Pro или Premium. Фиксация пожизненной цены основателя для первых 50 репетиторов Беларуси.
            </p>
            <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
              <a 
                href="/register?role=tutor&plan=pro" 
                className="w-full sm:w-auto font-bold text-base py-4 px-8 rounded-full bg-[#C6FF33] hover:bg-[#d4ff59] text-black shadow-[0_0_30px_rgba(198,255,51,0.3)] transition-all"
              >
                Занять место в своей нише →
              </a>
              <a 
                href="/login" 
                className="w-full sm:w-auto font-bold text-base py-4 px-8 rounded-full bg-white/5 hover:bg-white/10 border border-white/20 text-white transition-all"
              >
                Войти в личный кабинет
              </a>
            </div>
          </div>
        </div>
      </section>

      {/* ─── FOOTER ─── */}
      <Footer />

    </div>
  );
}
