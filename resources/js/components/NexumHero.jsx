import React, { useState, useEffect, useRef } from 'react';
import { 
  Menu, X, LayoutDashboard, User, LogIn, Sparkles, 
  BrainCircuit, CheckCircle2, ShieldCheck, GraduationCap, 
  TrendingUp, Calendar, Award, ArrowRight, Lock, BookOpen, 
  Users, Check, FileCheck, DollarSign, ChevronDown, LogOut,
  UserPlus, MessageSquare
} from 'lucide-react';
import Footer from './Footer';

export default function NexumHero() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [registerModalOpen, setRegisterModalOpen] = useState(false);
  const [emailInput, setEmailInput] = useState('');
  const [user, setUser] = useState(typeof window !== 'undefined' ? window.EDUSFERA_USER : null);
  const [linkedAccounts, setLinkedAccounts] = useState(typeof window !== 'undefined' ? (window.EDUSFERA_LINKED_ACCOUNTS || []) : []);
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const userMenuRef = useRef(null);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      if (window.EDUSFERA_USER) {
        setUser(window.EDUSFERA_USER);
      }
      if (window.EDUSFERA_LINKED_ACCOUNTS) {
        setLinkedAccounts(window.EDUSFERA_LINKED_ACCOUNTS);
      }
    }
  }, []);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (userMenuRef.current && !userMenuRef.current.contains(event.target)) {
        setUserMenuOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  const handleLogout = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = (typeof window !== 'undefined' && window.EDUSFERA_CSRF_TOKEN) ? window.EDUSFERA_CSRF_TOKEN : '';
    
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
  };

  const handleSwitchAccount = (accId) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/account/switch/${accId}`;
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = (typeof window !== 'undefined' && window.EDUSFERA_CSRF_TOKEN) ? window.EDUSFERA_CSRF_TOKEN : '';
    
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
  };

  useEffect(() => {
    if (mobileMenuOpen || registerModalOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [mobileMenuOpen, registerModalOpen]);

  const handleNavClick = (target) => {
    setMobileMenuOpen(false);
    if (target.startsWith('#')) {
      const el = document.querySelector(target);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
        return;
      }
    }
    window.location.href = target;
  };

  const navItems = [
    { label: 'Каталог', href: '/tutors' },
    { label: 'ИИ-Диагностика', href: '/diagnostic' },
    { label: 'Как это работает', href: '#how-it-works' },
    { label: 'Предметы', href: '#subjects' },
    { label: 'Модель сервиса', href: '#business-model' },
    { label: 'Гарантии', href: '#guarantees' },
    { label: 'Репетиторам', href: '/for-tutors', isSpecial: true },
  ];

  const subjects = [
    { name: 'Математика', gain: '+38 баллов', color: 'from-violet-500/20 to-purple-500/20', border: 'border-violet-500/30', text: 'text-violet-400', href: '/tutors?subject=Математика' },
    { name: 'Физика', gain: '+34 балла', color: 'from-blue-500/20 to-cyan-500/20', border: 'border-blue-500/30', text: 'text-blue-400', href: '/tutors?subject=Физика' },
    { name: 'Русский язык', gain: '+29 баллов', color: 'from-rose-500/20 to-pink-500/20', border: 'border-rose-500/30', text: 'text-rose-400', href: '/tutors?subject=Русский+язык' },
    { name: 'Белорусский язык', gain: '+31 балл', color: 'from-emerald-500/20 to-green-500/20', border: 'border-emerald-500/30', text: 'text-emerald-400', href: '/tutors?subject=Белорусский+язык' },
    { name: 'Английский язык', gain: '+35 баллов', color: 'from-amber-500/20 to-yellow-500/20', border: 'border-amber-500/30', text: 'text-amber-400', href: '/tutors?subject=Английский+язык' },
    { name: 'Обществоведение', gain: '+32 балла', color: 'from-indigo-500/20 to-sky-500/20', border: 'border-indigo-500/30', text: 'text-indigo-400', href: '/tutors?subject=Обществоведение' },
  ];

  return (
    <div className="w-full bg-[#010101] text-white font-sans selection:bg-[#C6FF33] selection:text-black">
      

      {/* ─── 1. HERO SECTION (WITH PHOTO/VIDEO BACKGROUND) ─── */}
      <section className="relative min-h-screen w-full overflow-hidden select-none font-geist flex flex-col justify-between bg-gradient-to-br from-neutral-950 via-slate-900 to-black">
        <video
          src="/videos/hero-bg.mp4"
          autoPlay
          loop
          muted
          playsInline
          className="absolute inset-0 h-full w-full object-cover z-0"
        />

        <div className="absolute inset-0 bg-black/45 z-0 pointer-events-none" />

        <div className="relative z-10 flex flex-col min-h-screen justify-between">
          {/* Header Bar */}
          <nav className="flex items-center justify-between px-5 py-5 sm:px-8 sm:py-6 lg:px-12">
            <a href="/" className="flex items-center gap-2.5 text-white transition-colors">
              <svg width="28" height="28" viewBox="0 0 64 64" className="w-7 h-7 rounded-lg shadow-sm">
                <rect width="64" height="64" rx="14" fill="#7D39EB" />
                <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
              </svg>
              <span className="text-xl font-bold tracking-tight text-white font-rimma uppercase">edusfera</span>
            </a>

            {/* Desktop Nav */}
            <div className="hidden md:flex items-center gap-3">
              <div className="rounded-full bg-white/10 px-1.5 py-1.5 backdrop-blur-lg flex items-center gap-1 border border-white/10">
                {navItems.map((item) => (
                  <button
                    key={item.label}
                    onClick={() => handleNavClick(item.href)}
                    className={`rounded-full px-4 py-1.5 text-sm font-medium transition-all flex items-center gap-1.5 cursor-pointer ${
                      item.isSpecial 
                        ? 'bg-[#C6FF33] text-black font-extrabold shadow-sm hover:bg-[#d4ff59]' 
                        : 'text-white/80 hover:bg-white/10 hover:text-white'
                    }`}
                  >
                    <span>{item.label}</span>
                    {item.isSpecial && (
                      <span className="text-[9px] bg-black text-[#C6FF33] px-1.5 py-0.2 rounded-full uppercase font-black">
                        0 BYN
                      </span>
                    )}
                  </button>
                ))}
              </div>

              {user ? (
                <div className="relative" ref={userMenuRef}>
                  <button
                    type="button"
                    onClick={() => setUserMenuOpen(!userMenuOpen)}
                    className="rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-4 py-2 text-sm font-semibold hover:bg-emerald-500/30 transition-all flex items-center gap-2.5 cursor-pointer backdrop-blur-md shadow-sm focus:outline-none"
                    aria-expanded={userMenuOpen}
                  >
                    <span className="relative flex h-2.5 w-2.5">
                      <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span className="truncate max-w-[120px]">{user.name || 'Кабинет'}</span>
                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-white/10 text-emerald-200 font-bold border border-white/10">
                      {user.role_label || (user.role === 'tutor' ? 'Репетитор' : user.role === 'admin' ? 'Администратор' : user.role === 'parent' ? 'Родитель' : 'Ученик')}
                    </span>
                    <ChevronDown className={`w-3.5 h-3.5 text-emerald-400 transition-transform ${userMenuOpen ? 'rotate-180' : ''}`} />
                  </button>

                  {/* Dropdown Menu */}
                  {userMenuOpen && (
                    <div className="absolute right-0 top-full mt-2 w-80 rounded-2xl bg-[#121214]/95 border border-white/10 p-2 text-white shadow-2xl backdrop-blur-xl z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                      {/* Current User Card */}
                      <div className="flex items-center gap-3 p-2.5 rounded-xl bg-white/5 border border-white/5 mb-1.5">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 font-bold text-sm">
                          {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                        </div>
                        <div className="min-w-0 flex-1">
                          <p className="text-sm font-bold text-white truncate">{user.name}</p>
                          <div className="flex items-center gap-1.5 mt-0.5">
                            <span className="inline-block text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                              {user.role_label || (user.role === 'tutor' ? 'Репетитор' : user.role === 'admin' ? 'Администратор' : user.role === 'parent' ? 'Родитель' : 'Ученик')}
                            </span>
                            <span className="text-xs text-neutral-400 truncate">{user.email}</span>
                          </div>
                        </div>
                      </div>

                      {/* Unified Dashboard Link */}
                      <div className="space-y-0.5">
                        <a
                          href="/admin"
                          className="flex items-center gap-2.5 px-3 py-2 text-sm font-semibold text-white rounded-xl bg-gradient-to-r from-[#7D39EB]/30 to-[#C6FF33]/20 border border-white/10 hover:from-[#7D39EB]/50 hover:to-[#C6FF33]/30 transition-all"
                        >
                          <span className="text-base">🚀</span>
                          <span>Личный кабинет</span>
                        </a>

                        {/* Role-Specific Quick Actions */}
                        {user.role === 'tutor' && (
                          <>
                            <a
                              href="/admin/tutor-subscription-page"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <Sparkles className="w-4 h-4 text-amber-400" />
                              <span>Управление тарифом</span>
                            </a>
                            <a
                              href="/admin/tutor-availability-page"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <Calendar className="w-4 h-4 text-emerald-400" />
                              <span>Расписание</span>
                            </a>
                            <a
                              href="/admin/lessons"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <BookOpen className="w-4 h-4 text-blue-400" />
                              <span>Мои занятия</span>
                            </a>
                            <a
                              href="/admin/transactions"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <DollarSign className="w-4 h-4 text-[#C6FF33]" />
                              <span>Мои финансы</span>
                            </a>
                          </>
                        )}

                        {(user.role === 'student' || user.role === 'parent') && (
                          <>
                            <a
                              href="/admin/lessons"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <BookOpen className="w-4 h-4 text-blue-400" />
                              <span>Мои занятия</span>
                            </a>
                            <a
                              href="/admin/diagnostic"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <GraduationCap className="w-4 h-4 text-emerald-400" />
                              <span>ИИ-Диагностика</span>
                            </a>
                            <a
                              href="/admin/homework"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <FileCheck className="w-4 h-4 text-amber-400" />
                              <span>Домашние задания</span>
                            </a>
                            <a
                              href="/admin/wallet"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <DollarSign className="w-4 h-4 text-[#C6FF33]" />
                              <span>Учёт денег</span>
                            </a>
                          </>
                        )}

                        {user.role === 'admin' && (
                          <>
                            <a
                              href="/admin/users"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <Users className="w-4 h-4 text-blue-400" />
                              <span>Пользователи</span>
                            </a>
                            <a
                              href="/admin/saa-s-management"
                              className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                            >
                              <Sparkles className="w-4 h-4 text-violet-400" />
                              <span>Управление SaaS</span>
                            </a>
                          </>
                        )}

                        <a
                          href="/admin/messages"
                          className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                        >
                          <MessageSquare className="w-4 h-4 text-violet-400" />
                          <span>Сообщения</span>
                        </a>
                      </div>

                      {/* Linked Accounts (Switching) */}
                      {linkedAccounts && linkedAccounts.length > 0 && (
                        <div className="mt-2 pt-2 border-t border-white/10">
                          <p className="px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-neutral-400">
                            Переключить аккаунт
                          </p>
                          <div className="space-y-0.5 mt-1">
                            {linkedAccounts.map((acc) => (
                              <button
                                type="button"
                                key={acc.id}
                                onClick={() => handleSwitchAccount(acc.id)}
                                className="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors cursor-pointer text-left"
                              >
                                <div className="flex items-center gap-2.5 min-w-0">
                                  <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-bold text-neutral-300">
                                    {acc.name ? acc.name.charAt(0).toUpperCase() : 'U'}
                                  </div>
                                  <span className="truncate text-xs font-semibold">{acc.name}</span>
                                </div>
                                <span className="text-[10px] px-2 py-0.5 rounded-md bg-white/10 text-neutral-400 font-medium shrink-0">
                                  {acc.role === 'tutor' ? 'Репетитор' : acc.role === 'admin' ? 'Администратор' : acc.role === 'parent' ? 'Родитель' : 'Ученик'}
                                </span>
                              </button>
                            ))}
                          </div>
                        </div>
                      )}

                      {/* Add Account & Logout */}
                      <div className="mt-2 pt-2 border-t border-white/10 space-y-0.5">
                        <a
                          href="/account/add"
                          className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-neutral-200 hover:text-white rounded-xl hover:bg-white/10 transition-colors"
                        >
                          <UserPlus className="w-4 h-4 text-blue-400" />
                          <span>+ Добавить аккаунт</span>
                        </a>

                        <button
                          type="button"
                          onClick={handleLogout}
                          className="w-full flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-red-400 hover:text-red-300 rounded-xl hover:bg-red-500/10 transition-colors cursor-pointer text-left"
                        >
                          <LogOut className="w-4 h-4 text-red-400" />
                          <span>Выйти</span>
                        </button>
                      </div>
                    </div>
                  )}
                </div>
              ) : (
                <div className="flex items-center gap-2">
                  <a
                    href="/login"
                    className="rounded-full px-4 py-2 text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all"
                  >
                    Войти
                  </a>
                  <a
                    href="/register"
                    className="rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-bold px-4 py-2 text-sm hover:from-violet-500 hover:to-indigo-500 transition-all shadow-md"
                  >
                    Регистрация
                  </a>
                  <a
                    href="/diagnostic"
                    className="rounded-full bg-[#C6FF33] text-black font-extrabold px-4 py-2 text-sm hover:bg-[#d4ff59] transition-all shadow-md"
                  >
                    ИИ-Диагностика
                  </a>
                </div>
              )}
            </div>

            {/* Mobile Hamburger */}
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              aria-label="Toggle Menu"
              className="md:hidden relative z-50 h-10 w-10 rounded-full bg-white/10 backdrop-blur-lg flex items-center justify-center text-white transition-colors focus:outline-none cursor-pointer border border-white/10"
            >
              {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
            </button>
          </nav>

          {/* Mobile Drawer */}
          {mobileMenuOpen && (
            <div className="fixed inset-0 z-40 bg-black/90 backdrop-blur-xl flex flex-col p-6 pt-24 space-y-4 md:hidden overflow-y-auto">
              {navItems.map((item) => (
                <button
                  key={item.label}
                  onClick={() => handleNavClick(item.href)}
                  className="text-left text-lg font-medium text-white/90 py-2 border-b border-white/10"
                >
                  {item.label}
                </button>
              ))}

              {user ? (
                <div className="mt-4 pt-4 border-t border-white/10 space-y-2.5">
                  <div className="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400 font-bold text-sm">
                      {user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-bold text-white truncate">{user.name}</p>
                      <span className="inline-block text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        {user.role_label || (user.role === 'tutor' ? 'Репетитор' : user.role === 'admin' ? 'Администратор' : user.role === 'parent' ? 'Родитель' : 'Ученик')}
                      </span>
                    </div>
                  </div>

                  <a
                    href="/admin"
                    className="flex items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-[#7D39EB]/40 to-[#C6FF33]/30 border border-white/20 px-4 py-3 text-sm font-semibold text-white"
                  >
                    <span>🚀 Личный кабинет</span>
                  </a>

                  {user.role === 'tutor' && (
                    <div className="space-y-1">
                      <a
                        href="/admin/tutor-subscription-page"
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <Sparkles className="w-4 h-4 text-amber-400" />
                        <span>Управление тарифом</span>
                      </a>
                      <a
                        href="/admin/tutor-availability-page"
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <Calendar className="w-4 h-4 text-emerald-400" />
                        <span>Расписание</span>
                      </a>
                      <a
                        href="/admin/lessons"
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <BookOpen className="w-4 h-4 text-blue-400" />
                        <span>Мои занятия</span>
                      </a>
                    </div>
                  )}

                  {(user.role === 'student' || user.role === 'parent') && (
                    <div className="space-y-1">
                      <a
                        href="/admin/lessons"
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <BookOpen className="w-4 h-4 text-blue-400" />
                        <span>Мои занятия</span>
                      </a>
                      <a
                        href="/admin/diagnostic"
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <GraduationCap className="w-4 h-4 text-emerald-400" />
                        <span>ИИ-Диагностика</span>
                      </a>
                      <a
                        href="/admin/homework"
                        className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-white"
                      >
                        <FileCheck className="w-4 h-4 text-amber-400" />
                        <span>Домашние задания</span>
                      </a>
                    </div>
                  )}

                  <a
                    href="/admin/messages"
                    className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-semibold text-white"
                  >
                    <MessageSquare className="w-4 h-4 text-violet-400" />
                    <span>Сообщения</span>
                  </a>

                  {linkedAccounts && linkedAccounts.length > 0 && (
                    <div className="space-y-1.5 pt-2">
                      <p className="text-[11px] font-bold uppercase tracking-wider text-neutral-400 px-1">
                        Сменить аккаунт:
                      </p>
                      {linkedAccounts.map((acc) => (
                        <button
                          type="button"
                          key={acc.id}
                          onClick={() => handleSwitchAccount(acc.id)}
                          className="w-full flex items-center justify-between rounded-xl bg-white/5 px-4 py-2 text-sm text-neutral-200 cursor-pointer text-left"
                        >
                          <span className="font-medium">{acc.name}</span>
                          <span className="text-xs text-neutral-400">
                            {acc.role === 'tutor' ? 'Репетитор' : acc.role === 'admin' ? 'Администратор' : acc.role === 'parent' ? 'Родитель' : 'Ученик'}
                          </span>
                        </button>
                      ))}
                    </div>
                  )}

                  <div className="pt-2 border-t border-white/10 space-y-1">
                    <a
                      href="/account/add"
                      className="flex items-center gap-2.5 rounded-xl bg-white/5 px-4 py-2.5 text-sm font-medium text-neutral-300"
                    >
                      <UserPlus className="w-4 h-4 text-blue-400" />
                      <span>+ Добавить аккаунт</span>
                    </a>
                    <button
                      type="button"
                      onClick={handleLogout}
                      className="w-full flex items-center gap-2.5 rounded-xl bg-red-500/10 px-4 py-2.5 text-sm font-medium text-red-400 cursor-pointer text-left"
                    >
                      <LogOut className="w-4 h-4 text-red-400" />
                      <span>Выйти</span>
                    </button>
                  </div>
                </div>
              ) : (
                <div className="mt-4 pt-4 border-t border-white/10 space-y-3">
                  <a
                    href="/login"
                    className="block text-center rounded-xl bg-white/10 py-3 text-sm font-semibold text-white"
                  >
                    Войти
                  </a>
                  <a
                    href="/register"
                    className="block text-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 py-3 text-sm font-bold text-white shadow-md"
                  >
                    Регистрация
                  </a>
                  <a
                    href="/diagnostic"
                    className="block text-center rounded-xl bg-[#C6FF33] py-3 text-sm font-extrabold text-black shadow-md"
                  >
                    ИИ-Диагностика
                  </a>
                </div>
              )}
            </div>
          )}

          {/* Main Hero Content */}
          <main className="mt-auto flex flex-col lg:flex-row lg:items-end lg:justify-between px-5 pb-8 sm:px-8 sm:pb-12 lg:px-12 lg:pb-16 gap-6 sm:gap-8">
            <div className="max-w-2xl">
              <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-violet-500/20 border border-violet-400/30 text-violet-300 font-bold text-xs mb-4 backdrop-blur-md">
                <Sparkles className="w-3.5 h-3.5 text-[#C6FF33]" />
                ИИ-Диагностика пробелов РИКЗ 2026 + Топ-репетиторы Беларуси
              </div>

              <h1 className="text-3xl sm:text-4xl lg:text-[3.3rem] font-semibold leading-[1.08] tracking-tight text-white drop-shadow-md">
                Готовься к ЦТ и ЦЭ с ИИ-агентами и лучшими преподавателями
              </h1>

              <p className="mt-4 text-slate-300 text-sm sm:text-base leading-relaxed max-w-xl">
                Узнайте свой реальный прогнозный балл за 15 минут и закрывайте пробелы с проверенными репетиторами из госреестра без переплат и посредников.
              </p>

              {user ? (
                <div className="mt-6 sm:mt-8">
                  <a
                    href="/admin"
                    className="inline-flex items-center justify-center gap-3 rounded-full bg-white text-black font-bold px-7 py-3.5 text-sm sm:text-base hover:bg-white/90 transition-all cursor-pointer shadow-lg hover:scale-[1.02]"
                  >
                    <LayoutDashboard className="w-5 h-5 text-violet-600" />
                    <span>Перейти в Личный Кабинет ({user.name || 'Профиль'})</span>
                  </a>
                </div>
              ) : (
                <div className="mt-6 sm:mt-8 flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5">
                  <a
                    href="/diagnostic"
                    className="inline-flex items-center justify-center gap-2.5 rounded-full bg-[#C6FF33] text-black font-extrabold px-7 py-3.5 text-sm sm:text-base hover:bg-[#d4ff59] transition-all shadow-[0_0_25px_rgba(198,255,51,0.3)] hover:scale-[1.02]"
                  >
                    <Sparkles className="w-4 h-4" />
                    <span>Пройти ИИ-диагностику (0 BYN)</span>
                  </a>
                  <a
                    href="/tutors"
                    className="inline-flex items-center justify-center gap-2 rounded-full bg-white/10 hover:bg-white/20 text-white font-semibold px-6 py-3.5 text-sm sm:text-base border border-white/15 backdrop-blur-md transition-all"
                  >
                    <span>Выбрать репетитора →</span>
                  </a>
                </div>
              )}
            </div>

            {/* Right Cards */}
            <div className="flex flex-col gap-4 sm:flex-row lg:w-auto lg:gap-5 w-full">
              <div className="rounded-2xl bg-white/10 backdrop-blur-lg p-5 sm:p-6 sm:w-64 flex flex-col justify-between border border-white/10 shadow-xl">
                <div>
                  <div className="text-3xl sm:text-4xl font-normal tracking-tight text-white font-mono">
                    15,400+
                  </div>
                  <p className="text-sm leading-relaxed mt-3 sm:mt-4 text-white/80">
                    Учеников сдали ЦТ и ЦЭ на 80+ баллов благодаря точечной ИИ-диагностике.
                  </p>
                </div>
              </div>

              <div className="rounded-2xl bg-white/10 backdrop-blur-lg p-5 sm:p-6 sm:w-80 border border-white/10 shadow-xl">
                <div className="flex items-center gap-2.5 text-xs text-white/90">
                  <div className="w-5 h-5 rounded bg-violet-600 flex items-center justify-center font-bold text-[10px]">
                    E
                  </div>
                  <span className="font-medium">Edusfera AI</span>
                </div>
                <p className="text-xs sm:text-sm leading-relaxed mt-3 text-white/90 font-light italic">
                  "С Edusfera я за 3 месяца поднял балл по математике с 45 до 92. ИИ-наставник сразу нашел мои слабые темы."
                </p>
                <div className="flex items-center gap-3 mt-4 pt-3 border-t border-white/10">
                  <div className="w-8 h-8 rounded-full bg-violet-500/30 flex items-center justify-center overflow-hidden border border-white/20">
                    <User className="w-4 h-4 text-white" />
                  </div>
                  <div>
                    <div className="text-xs font-semibold text-white">Максим Ковалев</div>
                    <div className="text-[11px] text-white/60">Студент БГУ, 96 баллов ЦТ</div>
                  </div>
                </div>
              </div>
            </div>
          </main>
        </div>
      </section>

      {/* ─── 2. HOW IT WORKS SECTION (ПОШАГОВАЯ СХЕМА) ─── */}
      <section id="how-it-works" className="py-20 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto border-t border-slate-800/60">
        <div className="text-center max-w-3xl mx-auto space-y-4 mb-16">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-xs font-bold text-[#C6FF33]">
            <BrainCircuit className="w-4 h-4 text-[#C6FF33]" />
            Умная технология подготовки
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
            Как Edusfera выводит на 80+ баллов ЦТ и ЦЭ
          </h2>
          <p className="text-slate-400 text-sm sm:text-base leading-relaxed">
            Мы объединили точную ИИ-диагностику пробелов знаний и лучших репетиторов Беларуси в один неразрывный учебный цикл.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
          {/* Step 1 */}
          <div className="rounded-3xl bg-slate-900/60 border border-slate-800 p-8 space-y-5 hover:border-violet-500/40 transition-all group">
            <div className="w-14 h-14 rounded-2xl bg-violet-600/20 border border-violet-500/30 flex items-center justify-center text-violet-400 font-extrabold text-xl group-hover:scale-110 transition-transform">
              01
            </div>
            <h3 className="text-xl font-bold text-white">ИИ-Диагностика пробелов</h3>
            <p className="text-slate-400 text-sm leading-relaxed">
              За 15 минут ИИ-агент сканирует ваши знания по спецификациям РИКЗ, формирует базовый балл и находит скрытые слабые темы.
            </p>
            <div className="pt-2 flex items-center gap-2 text-xs font-bold text-[#C6FF33]">
              <span>Точность прогноза 94%</span>
              <CheckCircle2 className="w-4 h-4" />
            </div>
          </div>

          {/* Step 2 */}
          <div className="rounded-3xl bg-slate-900/60 border border-slate-800 p-8 space-y-5 hover:border-lime-500/40 transition-all group">
            <div className="w-14 h-14 rounded-2xl bg-[#C6FF33]/20 border border-[#C6FF33]/30 flex items-center justify-center text-[#C6FF33] font-extrabold text-xl group-hover:scale-110 transition-transform">
              02
            </div>
            <h3 className="text-xl font-bold text-white">Подбор проверенного репетитора</h3>
            <p className="text-slate-400 text-sm leading-relaxed">
              Платформа предлагает преподавателей из госреестра с опытом подготовки от 5 лет. Выбирайте по рейтингу, отзывам и видео-визиткам.
            </p>
            <div className="pt-2 flex items-center gap-2 text-xs font-bold text-lime-400">
              <span>Только дипломированные эксперты</span>
              <ShieldCheck className="w-4 h-4" />
            </div>
          </div>

          {/* Step 3 */}
          <div className="rounded-3xl bg-slate-900/60 border border-slate-800 p-8 space-y-5 hover:border-sky-500/40 transition-all group">
            <div className="w-14 h-14 rounded-2xl bg-sky-600/20 border border-sky-500/30 flex items-center justify-center text-sky-400 font-extrabold text-xl group-hover:scale-110 transition-transform">
              03
            </div>
            <h3 className="text-xl font-bold text-white">Виртуальный класс & Отчёты</h3>
            <p className="text-slate-400 text-sm leading-relaxed">
              Уроки проходят на интерактивной доске с онлайн-связью. После каждого занятия вы и родители получаете отчёт о росте баллов.
            </p>
            <div className="pt-2 flex items-center gap-2 text-xs font-bold text-sky-400">
              <span>100% прозрачность для родителей</span>
              <TrendingUp className="w-4 h-4" />
            </div>
          </div>
        </div>
      </section>

      {/* ─── 3. INTERACTIVE DASHBOARD PREVIEW ─── */}
      <section className="py-16 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto">
        <div className="rounded-3xl bg-gradient-to-r from-violet-950/60 via-slate-900 to-indigo-950/60 border border-slate-800 p-8 sm:p-12 relative overflow-hidden">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            
            <div className="space-y-6">
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-500/20 border border-violet-400/30 text-violet-300 font-bold text-xs">
                <GraduationCap className="w-4 h-4 text-[#C6FF33]" />
                Личный Кабинет Обучения
              </div>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-white leading-tight">
                Вся динамика подготовки перед глазами
              </h2>
              <p className="text-slate-300 text-sm sm:text-base leading-relaxed">
                Забудьте о хаотичных тетрадях. В кабинете Edusfera фиксируются домашние задания, графики занятий, записи уроков и финансовый баланс безопасной сделки.
              </p>

              <div className="space-y-3 pt-2">
                <div className="flex items-center gap-3 text-sm text-slate-200">
                  <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">✓</div>
                  <span>Автоматический отсчёт дней до экзаменов ЦТ и ЦЭ</span>
                </div>
                <div className="flex items-center gap-3 text-sm text-slate-200">
                  <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">✓</div>
                  <span>Контроль выполнения домашки после каждого урока</span>
                </div>
                <div className="flex items-center gap-3 text-sm text-slate-200">
                  <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">✓</div>
                  <span>Оплата через WebPAY и ЕРИП с мгновенным чеком</span>
                </div>
              </div>

              <div className="pt-4">
                <a
                  href="/tutors"
                  className="inline-flex items-center gap-2 px-6 py-3.5 rounded-full bg-[#C6FF33] text-black font-extrabold text-sm hover:bg-[#b8f520] transition-colors cursor-pointer"
                >
                  <span>Выбрать репетитора в каталоге</span>
                  <ArrowRight className="w-4 h-4" />
                </a>
              </div>
            </div>

            {/* Visual Dashboard Card Mockup */}
            <div className="space-y-4 bg-slate-950/80 border border-slate-800 rounded-2xl p-6 shadow-2xl">
              <div className="flex items-center justify-between pb-4 border-b border-slate-800">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-xl bg-violet-600 flex items-center justify-center font-bold text-white">
                    МК
                  </div>
                  <div>
                    <h4 className="text-sm font-bold text-white">Максим Ковалев</h4>
                    <p className="text-xs text-slate-400">Цель: Математика ЦЭ (80+)</p>
                  </div>
                </div>
                <span className="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold border border-emerald-500/30">
                  Прогноз: 92 балла
                </span>
              </div>

              {/* Progress bar */}
              <div className="space-y-2 pt-2">
                <div className="flex justify-between text-xs text-slate-400 font-bold">
                  <span>Старт: 45 баллов</span>
                  <span className="text-white">Готовность 85%</span>
                </div>
                <div className="w-full h-3 rounded-full bg-slate-800 overflow-hidden">
                  <div className="h-full bg-gradient-to-r from-violet-600 to-[#C6FF33] w-[85%] rounded-full"></div>
                </div>
              </div>

              {/* Weak topics widget mockup */}
              <div className="pt-4 space-y-2">
                <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Слабые темы под контролем:</p>
                <div className="grid grid-cols-2 gap-2 text-xs">
                  <div className="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                    <span className="text-slate-300">Тригонометрия</span>
                    <span className="text-emerald-400 font-bold">Закрыто ✓</span>
                  </div>
                  <div className="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                    <span className="text-slate-300">Стереометрия</span>
                    <span className="text-amber-400 font-bold">В процессе</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* ─── 4. SUBJECTS GRID (ПРЕДМЕТЫ ПОДГОТОВКИ) ─── */}
      <section id="subjects" className="py-20 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto border-t border-slate-800/60">
        <div className="text-center max-w-3xl mx-auto space-y-4 mb-16">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-xs font-bold text-[#C6FF33]">
            <BookOpen className="w-4 h-4 text-[#C6FF33]" />
            Каталог предметов 2026
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
            Подготовка по всем ключевым предметам ЦТ и ЦЭ
          </h2>
          <p className="text-slate-400 text-sm sm:text-base leading-relaxed">
            Выбирайте предмет и находите репетитора под вашу целевую специальность в ВУЗах Беларуси.
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {subjects.map((sub, idx) => (
            <a
              key={idx}
              href={sub.href}
              className={`rounded-2xl bg-gradient-to-br ${sub.color} border ${sub.border} p-6 hover:scale-[1.02] transition-all cursor-pointer space-y-4 block`}
            >
              <div className="flex items-center justify-between">
                <h3 className="text-xl font-bold text-white">{sub.name}</h3>
                <span className={`text-xs font-black px-2.5 py-1 rounded-full bg-black/40 border border-white/10 ${sub.text}`}>
                  {sub.gain}
                </span>
              </div>
              <p className="text-slate-300 text-xs leading-relaxed">
                Полный курс по программе РИКЗ + решение открытых тестов прошлых лет.
              </p>
              <div className="flex items-center gap-1.5 text-xs font-bold text-white pt-2">
                <span>Смотреть преподавателей</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </div>
            </a>
          ))}
        </div>
      </section>

      {/* ─── 4.5 BUSINESS MODEL: 0 BYN ДЛЯ УЧЕНИКОВ И ЧЕСТНАЯ ПОДПИСКА ДЛЯ РЕПЕТИТОРОВ ─── */}
      <section id="business-model" className="py-20 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto border-t border-slate-800/60">
        <div className="text-center max-w-3xl mx-auto space-y-4 mb-16">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-xs font-bold text-[#C6FF33]">
            <Sparkles className="w-4 h-4 text-[#C6FF33]" />
            Бизнес-модель Edusfera 2026
          </div>
          <h2 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-white">
            Честная среда без посредников
          </h2>
          <p className="text-slate-400 text-sm sm:text-base leading-relaxed">
            Мы создали чистую SaaS-модель без поборов за уроки. В Edusfera прямые открытые отношения между семьями и преподавателями.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          
          {/* Card 1: Для учеников и родителей */}
          <div className="rounded-3xl bg-gradient-to-br from-slate-900/90 to-slate-950/90 border border-slate-800 p-8 sm:p-10 flex flex-col justify-between relative overflow-hidden">
            <div className="absolute top-0 right-0 w-48 h-48 bg-violet-600/10 rounded-full blur-3xl pointer-events-none"></div>
            <div>
              <div className="inline-block text-xs uppercase font-bold text-violet-400 mb-3 tracking-wider">
                Ученикам и родителям
              </div>
              <h3 className="text-2xl sm:text-3xl font-extrabold text-white mb-4">
                100% бесплатно и открыто
              </h3>
              <p className="text-slate-400 text-sm leading-relaxed mb-6">
                Вы платите строго за проведенные уроки по ставке репетитора. Сервис не берет никаких скрытых доплат.
              </p>

              <ul className="space-y-3.5 text-sm text-slate-200 mb-8">
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>0 BYN</strong> за входную ИИ-диагностику и прогноз баллов</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>0 BYN</strong> за поиск и подбор преподавателя в каталоге</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>0 BYN</strong> за онлайн-класс с интерактивной доской</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>Безопасная сделка</strong> (Эскроу через Альфа-Банк и ЕРИП)</span>
                </li>
              </ul>
            </div>

            <a
              href="/diagnostic"
              className="inline-flex items-center justify-center gap-2 w-full py-4 rounded-full bg-white text-black font-bold text-sm hover:bg-slate-100 transition-all shadow-lg"
            >
              <span>Пройти диагностику знаний (0 BYN) →</span>
            </a>
          </div>

          {/* Card 2: Для репетиторов */}
          <div className="rounded-3xl bg-gradient-to-br from-[#0B0F19] to-black border-2 border-[#C6FF33]/80 p-8 sm:p-10 flex flex-col justify-between relative overflow-hidden shadow-[0_0_50px_rgba(198,255,51,0.15)]">
            <div className="absolute top-0 right-0 w-48 h-48 bg-[#C6FF33]/10 rounded-full blur-3xl pointer-events-none"></div>
            <div>
              <div className="flex items-center justify-between gap-2 mb-3">
                <span className="text-xs uppercase font-bold text-[#C6FF33] tracking-wider">
                  Преподавателям Беларуси
                </span>
                <span className="text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-[#C6FF33] text-black">
                  1-й месяц 0 BYN
                </span>
              </div>
              <h3 className="text-2xl sm:text-3xl font-extrabold text-white mb-4">
                Подписка по цене 1 урока
              </h3>
              <p className="text-slate-400 text-sm leading-relaxed mb-6">
                Фиксированная подписка от 20 до 40 BYN/мес. Все 100% дохода с уроков остаются вам без процентов платформе.
              </p>

              <ul className="space-y-3.5 text-sm text-slate-200 mb-8">
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>100% дохода вам</strong> с ваших учеников и уроков</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>Постоянный поток заявок</strong> от мотивированных родителей</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>Авто-чеки для НПД</strong> в налоговые органы МНС РБ</span>
                </li>
                <li className="flex items-center gap-3">
                  <div className="w-5 h-5 rounded-full bg-[#C6FF33]/20 text-[#C6FF33] flex items-center justify-center font-bold text-xs">✓</div>
                  <span><strong>Гарантия:</strong> 30 дней без заявок — продление за 0 BYN</span>
                </li>
              </ul>
            </div>

            <a
              href="/for-tutors"
              className="inline-flex items-center justify-center gap-2 w-full py-4 rounded-full bg-[#C6FF33] hover:bg-[#d4ff59] text-black font-extrabold text-sm transition-all shadow-[0_0_30px_rgba(198,255,51,0.3)]"
            >
              <span>Узнать условия для репетиторов →</span>
            </a>
          </div>

        </div>
      </section>

      {/* ─── 5. TRUST & GUARANTEES (ГАРАНТИИ И ЛЕГАЛЬНОСТЬ) ─── */}
      <section id="guarantees" className="py-20 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto border-t border-slate-800/60">
        <div className="text-center max-w-3xl mx-auto space-y-4 mb-16">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-xs font-bold text-emerald-400">
            <ShieldCheck className="w-4 h-4 text-emerald-400" />
            Надёжность и прозрачность
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
            4 гарантии защиты учеников и родителей
          </h2>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div className="rounded-2xl bg-slate-900/80 border border-slate-800 p-6 space-y-3">
            <FileCheck className="w-8 h-8 text-violet-400 mb-2" />
            <h4 className="text-base font-bold text-white">Верификация в госреестре</h4>
            <p className="text-slate-400 text-xs leading-relaxed">
              Все репетиторы проходят проверку дипломов и зарегистрированы в налоговых органах РБ (НПД).
            </p>
          </div>

          <div className="rounded-2xl bg-slate-900/80 border border-slate-800 p-6 space-y-3">
            <Lock className="w-8 h-8 text-emerald-400 mb-2" />
            <h4 className="text-base font-bold text-white">0 BYN для учеников</h4>
            <p className="text-slate-400 text-xs leading-relaxed">
              Поиск репетиторов, онлайн-класс и базовая ИИ-диагностика на 100% бесплатны для семей.
            </p>
          </div>

          <div className="rounded-2xl bg-slate-900/80 border border-slate-800 p-6 space-y-3">
            <DollarSign className="w-8 h-8 text-[#C6FF33] mb-2" />
            <h4 className="text-base font-bold text-white">Прямой ЕРИП</h4>
            <p className="text-slate-400 text-xs leading-relaxed">
              Удобные платежи через систему «Расчёт» (ЕРИП) напрямую и без посредников.
            </p>
          </div>

          <div className="rounded-2xl bg-slate-900/80 border border-slate-800 p-6 space-y-3">
            <Award className="w-8 h-8 text-amber-400 mb-2" />
            <h4 className="text-base font-bold text-white">Отчёты о результатах</h4>
            <p className="text-slate-400 text-xs leading-relaxed">
              Постурочные отчёты и трекинг баллов ЦТ/ЦЭ в личном кабинете после каждого занятия.
            </p>
          </div>
        </div>
      </section>

      {/* ─── 6. FINAL CALL TO ACTION HERO CARD ─── */}
      <section className="py-16 px-5 sm:px-8 lg:px-12 max-w-7xl mx-auto">
        <div className="rounded-3xl bg-gradient-to-r from-violet-900 via-slate-900 to-indigo-900 border border-violet-500/30 p-10 sm:p-14 text-center space-y-6 relative overflow-hidden">
          <h2 className="text-3xl sm:text-5xl font-black text-white tracking-tight">
            Готовы узнать свой реальный балл ЦТ прямо сейчас?
          </h2>
          <p className="text-slate-300 text-sm sm:text-base max-w-2xl mx-auto leading-relaxed">
            Пройдите 15-минутную бесплатную ИИ-диагностику и получите индивидуальный план подготовки до целевого балла.
          </p>

          <div className="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
            <button
              onClick={() => setRegisterModalOpen(true)}
              className="w-full sm:w-auto px-8 py-4 rounded-full bg-[#C6FF33] text-black font-extrabold text-base hover:bg-[#b8f520] transition-transform hover:scale-105 cursor-pointer shadow-lg"
            >
              Начать бесплатный доступ
            </button>
            <a
              href="/tutors"
              className="w-full sm:w-auto px-8 py-4 rounded-full bg-white/10 text-white font-bold text-base hover:bg-white/20 transition-all cursor-pointer border border-white/20"
            >
              Подобрать репетитора
            </a>
          </div>
        </div>
      </section>

      {/* ─── 7. FOOTER SECTION ─── */}
      <Footer />

      {/* Registration Modal Overlay */}
      {registerModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="relative w-full max-w-md bg-neutral-900 border border-white/10 rounded-2xl p-6 sm:p-8 text-white shadow-2xl">
            <button
              onClick={() => setRegisterModalOpen(false)}
              className="absolute top-4 right-4 text-white/50 hover:text-white transition-colors cursor-pointer"
            >
              <X className="w-5 h-5" />
            </button>
            <h3 className="text-2xl font-bold text-white mb-2">Начать бесплатный доступ</h3>
            <p className="text-sm text-white/60 mb-6">
              Зарегистрируйтесь за 30 секунд для запуска ИИ-диагностики знаний.
            </p>
            <a
              href="/register"
              className="w-full inline-flex items-center justify-center gap-2 py-3.5 bg-white text-black font-semibold rounded-xl hover:bg-white/90 transition-all cursor-pointer text-sm"
            >
              <span>Перейти к регистрации</span>
            </a>
          </div>
        </div>
      )}

    </div>
  );
}
