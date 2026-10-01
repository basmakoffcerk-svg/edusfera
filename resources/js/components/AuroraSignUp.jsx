import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Eye, EyeOff, Loader2, CheckCircle2, ShieldCheck, 
  ArrowRight, ArrowLeft, Lock, Sparkles, Check, 
  GraduationCap, Briefcase, AlertCircle, 
  BookOpen, Bot, Video, FileText, CheckCircle,
  KeyRound, Mail
} from 'lucide-react';
import axios from 'axios';

// Configure Axios defaults for CSRF protection
if (typeof window !== 'undefined') {
  const token = window.EDUSFERA_CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
  }
  axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
}

const GoogleIcon = () => (
  <svg width="18" height="18" viewBox="0 0 24 24" className="shrink-0">
    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
  </svg>
);


export default function AuroraSignUp() {
  const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
  const initialRole = urlParams?.get('role') || 'student';
  const initialPlan = urlParams?.get('plan') || 'pro';
  const queryMode = urlParams?.get('mode') || urlParams?.get('tab');
  const queryRedirect = urlParams?.get('redirect') || urlParams?.get('redirect_to');

  // Check path for default mode
  const isDirectLoginPath = typeof window !== 'undefined' && 
    (window.location.pathname.includes('/login') || queryMode === 'login');

  // Tab Mode: 'login' | 'register'
  const [activeTab, setActiveTab] = useState(isDirectLoginPath ? 'login' : 'register');

  // Register Steps: 1 = Role selection, 2 = Profile details, 3 = Plan selection (tutors only)
  const [registerStep, setRegisterStep] = useState(1);

  // Form State
  const [formData, setFormData] = useState({
    loginIdentifier: '',
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    password: '',
    role: initialRole === 'tutor' ? 'tutor' : 'student',
    plan: ['start', 'premium'].includes(initialPlan) ? initialPlan : 'pro',
  });

  const [isYearly, setIsYearly] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');

  // Forgot Password State
  const [forgotStep, setForgotStep] = useState(1); // 1 = email, 2 = 6-digit code, 3 = new password
  const [resetEmail, setResetEmail] = useState('');
  const [resetCode, setResetCode] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [resendCooldown, setResendCooldown] = useState(0);
  const [resetSuccessMessage, setResetSuccessMessage] = useState('');

  // Resend code cooldown countdown
  useEffect(() => {
    if (resendCooldown > 0) {
      const timer = setTimeout(() => setResendCooldown(resendCooldown - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [resendCooldown]);

  // Auto-sync CSRF token on mount
  useEffect(() => {
    const token = window.EDUSFERA_CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
      axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
  }, []);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
    if (errorMessage) setErrorMessage('');
  };

  const handleTabSwitch = (tab) => {
    setActiveTab(tab);
    setForgotStep(1);
    setErrorMessage('');
    setIsSuccess(false);
  };

  // ─── FORGOT PASSWORD HANDLERS ───
  const handleSendResetCode = async (e) => {
    if (e) e.preventDefault();
    if (!resetEmail) {
      setErrorMessage('Пожалуйста, введите ваш email.');
      return;
    }

    setIsSubmitting(true);
    setErrorMessage('');

    try {
      await axios.post('/api/auth/forgot-password', {
        email: resetEmail.trim(),
      });
      setForgotStep(2);
      setResendCooldown(60);
    } catch (err) {
      if (err.response?.status === 429 || err.response?.data?.message === 'Too Many Attempts.') {
        setErrorMessage('Слишком много попыток. Пожалуйста, подождите одну минуту и попробуйте снова.');
        return;
      }
      const msg = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Ошибка при отправке кода.';
      setErrorMessage(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleVerifyResetCode = async (e) => {
    if (e) e.preventDefault();
    if (!resetCode || resetCode.length !== 6) {
      setErrorMessage('Введите 6-значный цифровой код из письма.');
      return;
    }

    setIsSubmitting(true);
    setErrorMessage('');

    try {
      await axios.post('/api/auth/verify-reset-code', {
        email: resetEmail.trim(),
        code: resetCode.trim(),
      });
      setForgotStep(3);
    } catch (err) {
      if (err.response?.status === 429 || err.response?.data?.message === 'Too Many Attempts.') {
        setErrorMessage('Слишком много попыток. Пожалуйста, подождите одну минуту и попробуйте снова.');
        return;
      }
      const msg = err.response?.data?.message || 'Неверный или просроченный код подтверждения.';
      setErrorMessage(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleResetPasswordSubmit = async (e) => {
    if (e) e.preventDefault();
    if (newPassword.length < 8) {
      setErrorMessage('Пароль должен содержать не менее 8 символов.');
      return;
    }
    if (newPassword !== confirmPassword) {
      setErrorMessage('Пароли не совпадают.');
      return;
    }

    setIsSubmitting(true);
    setErrorMessage('');

    try {
      const response = await axios.post('/api/auth/reset-password', {
        email: resetEmail.trim(),
        code: resetCode.trim(),
        password: newPassword,
        password_confirmation: confirmPassword,
      });
      setResetSuccessMessage(response.data?.message || 'Пароль успешно изменён! Выполняем вход...');
      handleSuccessfulAuth(response.data?.redirect || '/admin');
    } catch (err) {
      if (err.response?.status === 429 || err.response?.data?.message === 'Too Many Attempts.') {
        setErrorMessage('Слишком много попыток. Пожалуйста, подождите одну минуту и попробуйте снова.');
        return;
      }
      let msg = err.response?.data?.message || err.response?.data?.errors?.password?.[0] || 'Ошибка при сбросе пароля.';
      if (msg === 'Too Many Attempts.') {
        msg = 'Слишком много попыток. Пожалуйста, подождите одну минуту и попробуйте снова.';
      }
      setErrorMessage(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  // Helper redirect
  const handleSuccessfulAuth = (redirectUrl) => {
    setIsSuccess(true);
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    const fallbackTarget = currentPath.includes('site-admin') ? '/site-admin' : '/admin';
    const target = queryRedirect || redirectUrl || fallbackTarget;
    setTimeout(() => {
      window.location.href = target;
    }, 500);
  };

  // ─── LOGIN SUBMIT ───
  const handleLoginSubmit = async (e) => {
    e.preventDefault();
    if (!formData.loginIdentifier || !formData.password) {
      setErrorMessage('Пожалуйста, заполните e-mail / логин / телефон и пароль.');
      return;
    }

    setIsSubmitting(true);
    setErrorMessage('');

    try {
      const isSiteAdmin = typeof window !== 'undefined' && window.location.pathname.includes('site-admin');
      const response = await axios.post('/api/auth/login', {
        email: formData.loginIdentifier.trim(),
        password: formData.password,
        redirect: queryRedirect || (isSiteAdmin ? '/site-admin' : null),
      });

      if (response.data?.success) {
        handleSuccessfulAuth(response.data.redirect);
      }
    } catch (err) {
      setIsSubmitting(false);
      const msg = err.response?.data?.message ||
        (err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(', ')
          : 'Неверный e-mail/телефон или пароль.');
      setErrorMessage(msg);
    }
  };

  // ─── STEP 2 (PROFILE) SUBMIT OR ADVANCE TO PLAN ───
  const handleProfileStepSubmit = (e) => {
    e.preventDefault();
    setErrorMessage('');

    if (!formData.firstName.trim()) {
      setErrorMessage('Пожалуйста, укажите ваше имя.');
      return;
    }
    if (!formData.email.trim()) {
      setErrorMessage('Пожалуйста, укажите контактный e-mail.');
      return;
    }
    if (!formData.password || formData.password.length < 8) {
      setErrorMessage('Пароль должен содержать не менее 8 символов (буквы и цифры).');
      return;
    }

    // If Tutor, go to Plan Selection (Step 3)
    if (formData.role === 'tutor') {
      setRegisterStep(3);
    } else {
      // If Student/Parent, directly create account
      submitRegistration();
    }
  };

  // ─── FINAL REGISTRATION DISPATCH ───
  const submitRegistration = async () => {
    setIsSubmitting(true);
    setErrorMessage('');

    try {
      const payload = {
        firstName: formData.firstName.trim(),
        lastName: formData.lastName.trim(),
        email: formData.email.trim().toLowerCase(),
        phone: formData.phone.trim(),
        password: formData.password,
        role: formData.role,
        plan: formData.plan,
      };

      const response = await axios.post('/api/auth/register', payload);

      if (response.data?.success) {
        handleSuccessfulAuth(response.data.redirect);
      }
    } catch (err) {
      setIsSubmitting(false);
      let msg = err.response?.data?.message ||
        (err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(', ')
          : 'Ошибка при регистрации. Проверьте правильность введённых данных.');

      if (typeof msg === 'string') {
        if (msg.includes('validation.unique') || msg.includes('already been taken')) {
          msg = 'Пользователь с таким e-mail уже зарегистрирован. Пожалуйста, войдите в аккаунт или укажите другой e-mail.';
        } else if (msg.includes('validation.required')) {
          msg = 'Пожалуйста, заполните все обязательные поля.';
        } else if (msg.includes('validation.email')) {
          msg = 'Пожалуйста, укажите корректный адрес электронной почты.';
        }
      }
      setErrorMessage(msg);
    }
  };

  return (
    <main className="flex min-h-screen w-full bg-black selection:bg-[#C6FF33] selection:text-black p-2 sm:p-4 lg:h-screen lg:overflow-hidden font-sans text-white antialiased">
      
      {/* ─── LEFT COLUMN: HERO & BRANDING ─── */}
      <div className="relative hidden lg:flex w-[46%] xl:w-[44%] flex-col items-center justify-between p-10 xl:p-12 rounded-3xl overflow-hidden shadow-2xl h-full border border-white/10 bg-[#09090B]">
        {/* Background video with fallback gradient */}
        <video
          autoPlay
          muted
          loop
          playsInline
          className="absolute inset-0 w-full h-full object-cover opacity-40 mix-blend-screen"
        >
          <source src="/videos/aurora-bg.mp4" type="video/mp4" />
        </video>

        {/* Ambient gradients */}
        <div className="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent pointer-events-none" />
        <div className="absolute -top-32 -left-32 w-80 h-80 bg-[#7D39EB]/20 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute -bottom-32 -right-32 w-80 h-80 bg-[#C6FF33]/15 rounded-full blur-3xl pointer-events-none" />

        {/* Brand Logo Header */}
        <div className="z-10 w-full flex items-center justify-between">
          <a
            href="/"
            className="flex items-center gap-3 cursor-pointer group transition-transform hover:scale-[1.02]"
          >
            <div className="w-10 h-10 rounded-xl bg-[#7D39EB] flex items-center justify-center shadow-lg shadow-[#7D39EB]/30">
              <svg width="22" height="22" viewBox="0 0 64 64" fill="none">
                <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
              </svg>
            </div>
            <div className="flex flex-col">
              <span className="text-xl font-black tracking-wider text-white font-rimma uppercase">EDUSFERA</span>
              <span className="text-[10px] text-[#C6FF33] font-mono font-bold tracking-widest uppercase">Экосистема знаний</span>
            </div>
          </a>

          <span className="px-2.5 py-1 rounded-full text-[11px] font-bold bg-white/5 border border-white/10 text-white/70 backdrop-blur-md">
            Беларусь 🇧🇾
          </span>
        </div>

        {/* Hero Central Content */}
        <div className="z-10 w-full max-w-md space-y-6">
          <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 backdrop-blur-md">
            <Sparkles className="w-3.5 h-3.5 text-[#C6FF33]" />
            <span className="text-xs font-semibold text-white/90">
              {activeTab === 'register'
                ? 'Бесплатный старт за 1 минуту'
                : activeTab === 'forgot-password'
                ? 'Безопасность аккаунта'
                : 'Личный кабинет платформы'}
            </span>
          </div>

          <h1 className="text-3xl xl:text-4xl font-black tracking-tight leading-tight">
            {activeTab === 'register' ? (
              <>
                Образование нового <br />
                <span className="bg-clip-text text-transparent bg-gradient-to-r from-[#C6FF33] via-white to-emerald-400">
                  цифрового уровня.
                </span>
              </>
            ) : activeTab === 'forgot-password' ? (
              <>
                Быстрое и безопасное <br />
                <span className="bg-clip-text text-transparent bg-gradient-to-r from-[#C6FF33] via-white to-[#7D39EB]">
                  восстановление доступа.
                </span>
              </>
            ) : (
              <>
                Всё для уроков и роста <br />
                <span className="bg-clip-text text-transparent bg-gradient-to-r from-[#C6FF33] via-white to-[#7D39EB]">
                  в едином пространстве.
                </span>
              </>
            )}
          </h1>

          {/* Feature List */}
          <div className="space-y-3 pt-2">
            {[
              { icon: <Video className="w-4 h-4 text-[#C6FF33]" />, text: 'HD-видеокласс и интерактивная доска' },
              { icon: <Bot className="w-4 h-4 text-[#7D39EB]" />, text: 'ИИ-диагностика знаний по тестам РИКЗ' },
              { icon: <FileText className="w-4 h-4 text-emerald-400" />, text: 'Автоматические чеки НПД и расписание' },
            ].map((item, idx) => (
              <div key={idx} className="flex items-center gap-3 text-xs xl:text-sm text-white/80 font-medium">
                <div className="w-7 h-7 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center shrink-0">
                  {item.icon}
                </div>
                <span>{item.text}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Trust Footer */}
        <div className="z-10 w-full flex items-center justify-between text-[11px] text-white/40 pt-4 border-t border-white/10">
          <div className="flex items-center gap-1.5">
            <Lock className="w-3.5 h-3.5 text-emerald-400" />
            <span>256-битное шифрование</span>
          </div>
          <span>© {new Date().getFullYear()} Edusfera.by</span>
        </div>
      </div>

      {/* ─── RIGHT COLUMN: INTERACTIVE AUTH INTERFACE ─── */}
      <div className="flex-1 flex flex-col items-center justify-center py-6 px-3 sm:px-8 lg:px-10 xl:px-12 overflow-y-auto">
        <div className="w-full max-w-lg space-y-6">

          {/* Mobile Logo Header */}
          <div className="flex lg:hidden items-center justify-between pb-2">
            <a href="/" className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-lg bg-[#7D39EB] flex items-center justify-center">
                <svg width="18" height="18" viewBox="0 0 64 64" fill="none">
                  <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                  <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                </svg>
              </div>
              <span className="text-lg font-black tracking-wider text-white font-rimma uppercase">EDUSFERA</span>
            </a>
            <span className="text-xs text-white/50 font-mono">Беларусь 🇧🇾</span>
          </div>

          {/* ─── LUXURY TABS: [ ВХОД ] / [ РЕГИСТРАЦИЯ ] / [ ВОССТАНОВЛЕНИЕ ] ─── */}
          {activeTab !== 'forgot-password' ? (
            <div className="p-1 bg-[#121214] border border-white/10 rounded-2xl flex items-center relative shadow-inner">
              <button
                type="button"
                onClick={() => handleTabSwitch('login')}
                className={`relative flex-1 py-3 text-xs sm:text-sm font-bold rounded-xl transition-all duration-300 cursor-pointer z-10 flex items-center justify-center gap-2 ${
                  activeTab === 'login' ? 'text-black' : 'text-white/60 hover:text-white'
                }`}
              >
                {activeTab === 'login' && (
                  <motion.div
                    layoutId="auth-tab-pill"
                    className="absolute inset-0 bg-white rounded-xl shadow-md"
                    transition={{ type: 'spring', stiffness: 450, damping: 35 }}
                  />
                )}
                <span className="relative z-10">Вход</span>
              </button>

              <button
                type="button"
                onClick={() => handleTabSwitch('register')}
                className={`relative flex-1 py-3 text-xs sm:text-sm font-bold rounded-xl transition-all duration-300 cursor-pointer z-10 flex items-center justify-center gap-2 ${
                  activeTab === 'register' ? 'text-black' : 'text-white/60 hover:text-white'
                }`}
              >
                {activeTab === 'register' && (
                  <motion.div
                    layoutId="auth-tab-pill"
                    className="absolute inset-0 bg-[#C6FF33] rounded-xl shadow-md"
                    transition={{ type: 'spring', stiffness: 450, damping: 35 }}
                  />
                )}
                <span className="relative z-10">Регистрация</span>
              </button>
            </div>
          ) : (
            <div className="flex items-center justify-between px-1 py-1">
              <button
                type="button"
                onClick={() => handleTabSwitch('login')}
                className="inline-flex items-center gap-2 text-xs font-semibold text-white/50 hover:text-white transition-colors cursor-pointer"
              >
                <ArrowLeft className="w-4 h-4" />
                <span>Назад ко входу</span>
              </button>
              <div className="flex items-center gap-1.5">
                {[1, 2, 3].map((step) => (
                  <div
                    key={step}
                    className={`h-1.5 rounded-full transition-all duration-300 ${
                      forgotStep === step
                        ? 'w-6 bg-[#C6FF33]'
                        : forgotStep > step
                        ? 'w-1.5 bg-[#C6FF33]/60'
                        : 'w-1.5 bg-white/20'
                    }`}
                  />
                ))}
              </div>
            </div>
          )}

          {/* Error Alert Banner */}
          <AnimatePresence>
            {errorMessage && (
              <motion.div
                initial={{ opacity: 0, y: -8, scale: 0.98 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: -8, scale: 0.98 }}
                className="p-3.5 bg-red-500/10 border border-red-500/30 rounded-xl flex items-start gap-3 text-red-400 text-xs sm:text-sm leading-relaxed"
              >
                <AlertCircle className="w-4 h-4 shrink-0 mt-0.5 text-red-400" />
                <div className="flex-1 font-medium">
                  {errorMessage}
                  {errorMessage.includes('уже зарегистрирован') && (
                    <button
                      type="button"
                      onClick={() => {
                        setAuthMode('login');
                        setActiveTab('login');
                        setErrorMessage('');
                      }}
                      className="block mt-1.5 text-xs text-[#C6FF33] hover:underline font-semibold"
                    >
                      Перейти ко входу в аккаунт →
                    </button>
                  )}
                </div>
              </motion.div>
            )}
          </AnimatePresence>

          {/* ─── DYNAMIC TAB CONTENT ─── */}
          <AnimatePresence mode="wait">
            
            {/* ═══════════════════════════════════════════════════ */}
            {/* MODE 1: LOGIN MODE                                  */}
            {/* ═══════════════════════════════════════════════════ */}
            {activeTab === 'login' && (
              <motion.div
                key="login-view"
                initial={{ opacity: 0, x: -16 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: 16 }}
                transition={{ duration: 0.25 }}
                className="space-y-5"
              >
                <div className="space-y-1">
                  <h2 className="text-2xl font-bold tracking-tight text-white">Вход в личный кабинет</h2>
                  <p className="text-xs sm:text-sm text-white/50">
                    Введите свои данные или войдите через социальные сети
                  </p>
                </div>

                {/* Social Auth Buttons */}
                <div>
                  <a
                    href="/auth/google/redirect"
                    className="flex items-center justify-center gap-2 h-11 w-full bg-[#141416] hover:bg-[#1c1c20] border border-white/10 hover:border-white/20 rounded-xl transition-all text-xs font-semibold text-white shadow-sm cursor-pointer active:scale-98"
                  >
                    <GoogleIcon />
                    <span>Продолжить с Google</span>
                  </a>
                </div>

                {/* Divider */}
                <div className="relative flex items-center justify-center my-2">
                  <div className="absolute inset-0 flex items-center">
                    <div className="w-full border-t border-white/10" />
                  </div>
                  <span className="relative bg-black px-3 text-[11px] font-semibold text-white/40 uppercase tracking-wider">
                    или через E-mail / Телефон
                  </span>
                </div>

                {/* Login Form */}
                <form onSubmit={handleLoginSubmit} className="space-y-4">
                  <div className="space-y-1.5">
                    <label className="block text-xs font-semibold text-white/80">
                      Email или Телефон
                    </label>
                    <input
                      type="text"
                      name="loginIdentifier"
                      value={formData.loginIdentifier}
                      onChange={handleChange}
                      placeholder="tutor@edusfera.by или +375 (29) 123-45-67"
                      required
                      autoComplete="username"
                      className="w-full h-11 px-3.5 bg-[#141416] border border-white/10 rounded-xl text-xs sm:text-sm text-white placeholder:text-white/25 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                    />
                  </div>

                  <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                      <label className="block text-xs font-semibold text-white/80">
                        Пароль
                      </label>
                      <button
                        type="button"
                        onClick={() => {
                          setActiveTab('forgot-password');
                          setForgotStep(1);
                          setErrorMessage('');
                          if (formData.loginIdentifier && formData.loginIdentifier.includes('@')) {
                            setResetEmail(formData.loginIdentifier);
                          }
                        }}
                        className="text-[11px] font-medium text-white/40 hover:text-[#C6FF33] transition-colors cursor-pointer"
                      >
                        Забыли пароль?
                      </button>
                    </div>
                    <div className="relative">
                      <input
                        type={showPassword ? 'text' : 'password'}
                        name="password"
                        value={formData.password}
                        onChange={handleChange}
                        placeholder="••••••••"
                        required
                        autoComplete="current-password"
                        className="w-full h-11 pl-3.5 pr-10 bg-[#141416] border border-white/10 rounded-xl text-xs sm:text-sm text-white placeholder:text-white/25 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword(!showPassword)}
                        className="absolute right-3.5 top-3 text-white/40 hover:text-white transition-colors cursor-pointer"
                        tabIndex={-1}
                      >
                        {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                      </button>
                    </div>
                  </div>

                  <button
                    type="submit"
                    disabled={isSubmitting || isSuccess}
                    className={`w-full h-12 font-extrabold text-xs sm:text-sm rounded-xl transition-all duration-200 cursor-pointer flex items-center justify-center gap-2 mt-2 shadow-lg ${
                      isSuccess
                        ? 'bg-emerald-500 text-white'
                        : 'bg-white text-black hover:bg-white/90 active:scale-[0.99] disabled:opacity-50'
                    }`}
                  >
                    {isSuccess ? (
                      <>
                        <CheckCircle2 className="w-4 h-4" />
                        <span>Вход выполнен! Перенаправляем...</span>
                      </>
                    ) : isSubmitting ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin text-black" />
                        <span>Входим...</span>
                      </>
                    ) : (
                      <span>Войти в личный кабинет</span>
                    )}
                  </button>
                </form>

                <div className="flex flex-col items-center gap-2 pt-2">
                  <button
                    type="button"
                    onClick={() => {
                      setActiveTab('forgot-password');
                      setForgotStep(1);
                      setErrorMessage('');
                      if (formData.loginIdentifier && formData.loginIdentifier.includes('@')) {
                        setResetEmail(formData.loginIdentifier);
                      }
                    }}
                    className="text-[11px] text-white/40 hover:text-white transition-colors cursor-pointer"
                  >
                    Забыли пароль?
                  </button>

                  <button
                    type="button"
                    onClick={() => handleTabSwitch('register')}
                    className="text-xs text-white/60 hover:text-[#C6FF33] transition-colors cursor-pointer font-medium"
                  >
                    Ещё нет аккаунта? <span className="underline underline-offset-4 font-bold text-white">Зарегистрироваться</span>
                  </button>
                </div>
              </motion.div>
            )}

            {/* ═══════════════════════════════════════════════════ */}
            {/* MODE 2: REGISTER MODE (MULTI-STEP FLOW)             */}
            {/* ═══════════════════════════════════════════════════ */}
            {activeTab === 'register' && (
              <motion.div
                key="register-view"
                initial={{ opacity: 0, x: 16 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -16 }}
                transition={{ duration: 0.25 }}
                className="space-y-5"
              >
                {/* Steps Indicator */}
                <div className="flex items-center justify-between px-1">
                  <div className="flex items-center gap-2">
                    <span className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold ${
                      registerStep === 1 ? 'bg-[#C6FF33] text-black' : 'bg-white/10 text-white/70'
                    }`}>
                      1
                    </span>
                    <span className={`text-xs font-semibold ${registerStep === 1 ? 'text-white' : 'text-white/40'}`}>
                      Роль
                    </span>
                  </div>

                  <div className="w-6 h-[1px] bg-white/10" />

                  <div className="flex items-center gap-2">
                    <span className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold ${
                      registerStep === 2 ? 'bg-[#C6FF33] text-black' : 'bg-white/10 text-white/70'
                    }`}>
                      2
                    </span>
                    <span className={`text-xs font-semibold ${registerStep === 2 ? 'text-white' : 'text-white/40'}`}>
                      Данные
                    </span>
                  </div>

                  {formData.role === 'tutor' && (
                    <>
                      <div className="w-6 h-[1px] bg-white/10" />
                      <div className="flex items-center gap-2">
                        <span className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold ${
                          registerStep === 3 ? 'bg-[#C6FF33] text-black' : 'bg-white/10 text-white/70'
                        }`}>
                          3
                        </span>
                        <span className={`text-xs font-semibold ${registerStep === 3 ? 'text-white' : 'text-white/40'}`}>
                          Тариф
                        </span>
                      </div>
                    </>
                  )}
                </div>

                {/* ─── REGISTER STEP 1: ROLE SELECTION ─── */}
                {registerStep === 1 && (
                  <motion.div
                    key="step-role"
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -10 }}
                    className="space-y-4"
                  >
                    <div className="space-y-1">
                      <h2 className="text-2xl font-bold tracking-tight text-white">Выберите вашу роль</h2>
                      <p className="text-xs sm:text-sm text-white/50">
                        Это поможет настроить кабинет и рабочие инструменты под вас
                      </p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                      {/* Card 1: Student / Parent */}
                      <div
                        onClick={() => {
                          setFormData({ ...formData, role: 'student' });
                        }}
                        className={`p-4 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between space-y-3 relative group ${
                          formData.role === 'student'
                            ? 'bg-[#18181B] border-[#C6FF33] ring-1 ring-[#C6FF33]'
                            : 'bg-[#121214] border-white/10 hover:border-white/20 hover:bg-[#161618]'
                        }`}
                      >
                        <div className="space-y-2">
                          <div className="flex items-center justify-between">
                            <div className="w-9 h-9 rounded-xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400">
                              <GraduationCap className="w-5 h-5" />
                            </div>
                            <span className="text-[10px] font-bold uppercase px-2 py-0.5 rounded-md bg-white/5 text-white/60">
                              Для занятий
                            </span>
                          </div>
                          <div>
                            <h3 className="text-sm sm:text-base font-bold text-white">Ученик / Родитель</h3>
                            <p className="text-xs text-white/50 leading-relaxed mt-1">
                              Для подготовки к ЦТ/ЦЭ и занятий с репетитором
                            </p>
                          </div>
                        </div>

                        <div className="flex items-center justify-between pt-2 border-t border-white/5 text-[11px] text-white/40">
                          <span>Каталог и видеокласс</span>
                          <div className={`w-4 h-4 rounded-full border flex items-center justify-center ${
                            formData.role === 'student' ? 'border-[#C6FF33] bg-[#C6FF33] text-black' : 'border-white/20'
                          }`}>
                            {formData.role === 'student' && <Check className="w-3 h-3" />}
                          </div>
                        </div>
                      </div>

                      {/* Card 2: Tutor */}
                      <div
                        onClick={() => {
                          setFormData({ ...formData, role: 'tutor' });
                        }}
                        className={`p-4 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between space-y-3 relative group ${
                          formData.role === 'tutor'
                            ? 'bg-[#18181B] border-[#C6FF33] ring-1 ring-[#C6FF33]'
                            : 'bg-[#121214] border-white/10 hover:border-white/20 hover:bg-[#161618]'
                        }`}
                      >
                        <div className="space-y-2">
                          <div className="flex items-center justify-between">
                            <div className="w-9 h-9 rounded-xl bg-[#C6FF33]/10 border border-[#C6FF33]/20 flex items-center justify-center text-[#C6FF33]">
                              <Briefcase className="w-5 h-5" />
                            </div>
                            <span className="text-[10px] font-bold uppercase px-2 py-0.5 rounded-md bg-[#C6FF33]/15 text-[#C6FF33]">
                              Для работы
                            </span>
                          </div>
                          <div>
                            <h3 className="text-sm sm:text-base font-bold text-white">Репетитор</h3>
                            <p className="text-xs text-white/50 leading-relaxed mt-1">
                              Для ведения учеников, виртуального класса и ИИ-инструментов
                            </p>
                          </div>
                        </div>

                        <div className="flex items-center justify-between pt-2 border-t border-white/5 text-[11px] text-white/40">
                          <span>CRM, доска, авто-НПД</span>
                          <div className={`w-4 h-4 rounded-full border flex items-center justify-center ${
                            formData.role === 'tutor' ? 'border-[#C6FF33] bg-[#C6FF33] text-black' : 'border-white/20'
                          }`}>
                            {formData.role === 'tutor' && <Check className="w-3 h-3" />}
                          </div>
                        </div>
                      </div>
                    </div>

                    <button
                      type="button"
                      onClick={() => setRegisterStep(2)}
                      className="w-full h-12 bg-[#C6FF33] text-black hover:bg-[#d4ff59] font-extrabold text-xs sm:text-sm rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer shadow-[0_0_20px_rgba(198,255,51,0.2)] mt-3"
                    >
                      <span>Продолжить</span>
                      <ArrowRight className="w-4 h-4" />
                    </button>

                    {/* Social OAuth quick-entry */}
                    <div className="pt-2">
                      <div className="relative flex items-center justify-center mb-3">
                        <div className="absolute inset-0 flex items-center">
                          <div className="w-full border-t border-white/10" />
                        </div>
                        <span className="relative bg-black px-3 text-[10px] font-medium text-white/40 uppercase tracking-widest">
                          или быстрая регистрация
                        </span>
                      </div>
                      <div>
                        <a
                          href={`/auth/google/redirect?role=${formData.role}&plan=${formData.plan}`}
                          className="flex items-center justify-center gap-2 h-10 w-full bg-[#141416] hover:bg-[#1a1a1c] border border-white/10 rounded-xl text-xs font-semibold text-white/80"
                        >
                          <GoogleIcon />
                          <span>Продолжить с Google</span>
                        </a>
                      </div>
                    </div>
                  </motion.div>
                )}

                {/* ─── REGISTER STEP 2: PROFILE INPUTS ─── */}
                {registerStep === 2 && (
                  <motion.div
                    key="step-profile"
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -10 }}
                    className="space-y-4"
                  >
                    <div className="space-y-1">
                      <h2 className="text-2xl font-bold tracking-tight text-white">
                        {formData.role === 'tutor' ? 'Профиль репетитора' : 'Создание аккаунта'}
                      </h2>
                      <p className="text-xs sm:text-sm text-white/50">
                        Укажите контактные данные для доступа к платформе
                      </p>
                    </div>

                    <form onSubmit={handleProfileStepSubmit} className="space-y-3.5">
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div className="space-y-1.5">
                          <label className="block text-xs font-semibold text-white/80">
                            Имя <span className="text-[#C6FF33]">*</span>
                          </label>
                          <input
                            type="text"
                            name="firstName"
                            value={formData.firstName}
                            onChange={handleChange}
                            placeholder="Александр"
                            required
                            className="w-full h-11 px-3.5 bg-[#141416] border border-white/10 rounded-xl text-xs sm:text-sm text-white placeholder:text-white/25 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                          />
                        </div>

                        <div className="space-y-1.5">
                          <label className="block text-xs font-semibold text-white/80">
                            Фамилия <span className="text-white/40 text-[10px]">(опционально)</span>
                          </label>
                          <input
                            type="text"
                            name="lastName"
                            value={formData.lastName}
                            onChange={handleChange}
                            placeholder="Иванов"
                            className="w-full h-11 px-3.5 bg-[#141416] border border-white/10 rounded-xl text-xs sm:text-sm text-white placeholder:text-white/25 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                          />
                        </div>
                      </div>

                      <div className="space-y-1.5">
                        <label className="block text-xs font-semibold text-white/80">
                          E-mail <span className="text-[#C6FF33]">*</span>
                        </label>
                        <input
                          type="email"
                          name="email"
                          value={formData.email}
                          onChange={handleChange}
                          placeholder="alex@example.com"
                          required
                          autoComplete="email"
                          className="w-full h-11 px-3.5 bg-[#141416] border border-white/10 rounded-xl text-xs sm:text-sm text-white placeholder:text-white/25 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                        />
                      </div>

                      <div className="space-y-1.5">
                        <label className="block text-xs font-semibold text-white/80">
                          Телефон <span className="text-white/40 text-[10px]">(для уведомлений)</span>
                        </label>
                        <input
                          type="tel"
                          name="phone"
                          value={formData.phone}
                          onChange={handleChange}
                          placeholder="+375 (29) 123-45-67"
                          className="w-full h-11 px-3.5 bg-[#141416] border border-white/10 rounded-xl text-xs sm:text-sm text-white placeholder:text-white/25 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                        />
                      </div>

                      <div className="space-y-1.5">
                        <label className="block text-xs font-semibold text-white/80">
                          Пароль <span className="text-[#C6FF33]">*</span>
                        </label>
                        <div className="relative">
                          <input
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={formData.password}
                            onChange={handleChange}
                            placeholder="Минимум 8 символов"
                            required
                            className="w-full h-11 pl-3.5 pr-10 bg-[#141416] border border-white/10 rounded-xl text-xs sm:text-sm text-white placeholder:text-white/25 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                          />
                          <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute right-3.5 top-3 text-white/40 hover:text-white transition-colors cursor-pointer"
                            tabIndex={-1}
                          >
                            {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                          </button>
                        </div>
                        <p className="text-[11px] text-white/40">
                          Минимум 8 символов: строчные, заглавные буквы и цифры.
                        </p>
                      </div>

                      <div className="flex items-center gap-2 pt-2">
                        <button
                          type="button"
                          onClick={() => setRegisterStep(1)}
                          className="h-12 px-4 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white border border-white/10 rounded-xl transition-all flex items-center justify-center gap-1.5 text-xs font-bold cursor-pointer"
                        >
                          <ArrowLeft className="w-4 h-4" />
                          <span>Назад</span>
                        </button>

                        <button
                          type="submit"
                          disabled={isSubmitting || isSuccess}
                          className={`flex-1 h-12 font-extrabold text-xs sm:text-sm rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer shadow-lg ${
                            isSuccess
                              ? 'bg-emerald-500 text-white'
                              : formData.role === 'tutor'
                                ? 'bg-[#C6FF33] text-black hover:bg-[#d4ff59]'
                                : 'bg-white text-black hover:bg-white/90'
                          }`}
                        >
                          {isSuccess ? (
                            <>
                              <CheckCircle2 className="w-4 h-4" />
                              <span>Готово! Входим в кабинет...</span>
                            </>
                          ) : isSubmitting ? (
                            <>
                              <Loader2 className="w-4 h-4 animate-spin text-black" />
                              <span>Создание аккаунта...</span>
                            </>
                          ) : formData.role === 'tutor' ? (
                            <>
                              <span>Выбрать тариф подписки</span>
                              <ArrowRight className="w-4 h-4" />
                            </>
                          ) : (
                            <span>Создать аккаунт</span>
                          )}
                        </button>
                      </div>
                    </form>
                  </motion.div>
                )}

                {/* ─── REGISTER STEP 3: TARIFF SELECTION (FOR TUTORS) ─── */}
                {registerStep === 3 && (
                  <motion.div
                    key="step-tariff"
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -10 }}
                    className="space-y-4"
                  >
                    <div className="space-y-1">
                      <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#C6FF33]/15 border border-[#C6FF33]/30 text-[#C6FF33] font-bold text-[11px]">
                        <Sparkles className="w-3.5 h-3.5" />
                        <span>14 дней бесплатно, карта не требуется</span>
                      </div>
                      <h2 className="text-2xl font-bold tracking-tight text-white">Выбор тарифа подписки</h2>
                      <p className="text-xs sm:text-sm text-white/50">
                        Попробуйте все возможности платформы бесплатно. Сменить тариф можно в любой момент.
                      </p>
                    </div>

                    {/* Period Switcher */}
                    <div className="flex items-center justify-between p-1.5 bg-[#121214] rounded-xl border border-white/10">
                      <span className="text-xs font-semibold text-white/70 px-2">Период:</span>
                      <div className="flex gap-1">
                        <button
                          type="button"
                          onClick={() => setIsYearly(false)}
                          className={`px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer ${
                            !isYearly ? 'bg-white text-black shadow-sm' : 'text-white/60 hover:text-white'
                          }`}
                        >
                          Ежемесячно
                        </button>
                        <button
                          type="button"
                          onClick={() => setIsYearly(true)}
                          className={`px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5 cursor-pointer ${
                            isYearly ? 'bg-[#C6FF33] text-black shadow-sm' : 'text-white/60 hover:text-white'
                          }`}
                        >
                          <span>1 год</span>
                          <span className="text-[9px] bg-black text-[#C6FF33] px-1 py-0.2 rounded font-black">
                            -20%
                          </span>
                        </button>
                      </div>
                    </div>

                    {/* Tariff Cards: Стандарт, Про, Премиум */}
                    <div className="grid grid-cols-1 gap-2.5">
                      
                      {/* Plan 1: Стандарт (20 BYN) */}
                      <div
                        onClick={() => setFormData({ ...formData, plan: 'start' })}
                        className={`p-3.5 rounded-2xl border transition-all cursor-pointer space-y-2 relative ${
                          formData.plan === 'start'
                            ? 'bg-[#18181B] border-white ring-1 ring-white'
                            : 'bg-[#121214] border-white/10 hover:border-white/20'
                        }`}
                      >
                        <div className="flex items-center justify-between">
                          <div>
                            <span className="font-extrabold text-sm sm:text-base text-white">«Стандарт»</span>
                            <p className="text-xs text-white/50">Класс до 60 мин, CRM, расписание, свои ученики</p>
                          </div>
                          <div className="text-right">
                            <span className="text-sm sm:text-base font-black text-white font-mono">
                              {isYearly ? '16 BYN' : '20 BYN'}
                            </span>
                            <span className="text-[10px] text-white/40 block">/месяц</span>
                          </div>
                        </div>

                        <div className="pt-2 border-t border-white/5 grid grid-cols-2 gap-1 text-[11px] text-white/70">
                          <span className="flex items-center gap-1.5">
                            <Check className="w-3.5 h-3.5 text-emerald-400 shrink-0" /> HD класс (до 60 мин)
                          </span>
                          <span className="flex items-center gap-1.5">
                            <Check className="w-3.5 h-3.5 text-emerald-400 shrink-0" /> Интерактивная доска
                          </span>
                          <span className="flex items-center gap-1.5">
                            <Check className="w-3.5 h-3.5 text-emerald-400 shrink-0" /> CRM и расписание
                          </span>
                          <span className="flex items-center gap-1.5">
                            <Check className="w-3.5 h-3.5 text-emerald-400 shrink-0" /> Безлимит учеников
                          </span>
                        </div>
                      </div>

                      {/* Plan 2: Про (40 BYN) - Featured */}
                      <div
                        onClick={() => setFormData({ ...formData, plan: 'pro' })}
                        className={`p-3.5 rounded-2xl border transition-all cursor-pointer space-y-2 relative ${
                          formData.plan === 'pro'
                            ? 'bg-gradient-to-br from-[#18181B] via-[#18181B] to-[#7D39EB]/20 border-[#C6FF33] ring-1 ring-[#C6FF33]'
                            : 'bg-[#121214] border-white/10 hover:border-[#C6FF33]/40'
                        }`}
                      >
                        <div className="absolute -top-2.5 right-4 bg-[#C6FF33] text-black font-black text-[9px] uppercase px-2 py-0.5 rounded-full shadow-sm">
                          Хит продаж
                        </div>

                        <div className="flex items-center justify-between">
                          <div>
                            <span className="font-extrabold text-sm sm:text-base text-white">«Про»</span>
                            <p className="text-xs text-white/60">Поток учеников + ИИ-диагностика + авто-НПД</p>
                          </div>
                          <div className="text-right">
                            <span className="text-sm sm:text-base font-black text-[#C6FF33] font-mono">
                              {isYearly ? '32 BYN' : '40 BYN'}
                            </span>
                            <span className="text-[10px] text-white/40 block">/месяц</span>
                          </div>
                        </div>

                        <div className="pt-2 border-t border-white/5 grid grid-cols-2 gap-1 text-[11px] text-white/80">
                          <span className="flex items-center gap-1.5 font-medium">
                            <Check className="w-3.5 h-3.5 text-[#C6FF33] shrink-0" /> Всё из «Стандарта»
                          </span>
                          <span className="flex items-center gap-1.5 font-medium">
                            <Check className="w-3.5 h-3.5 text-[#C6FF33] shrink-0" /> ИИ-диагностика (РИКЗ)
                          </span>
                          <span className="flex items-center gap-1.5 font-medium">
                            <Check className="w-3.5 h-3.5 text-[#C6FF33] shrink-0" /> Авто-чеки НПД (МНС)
                          </span>
                          <span className="flex items-center gap-1.5 font-medium">
                            <Check className="w-3.5 h-3.5 text-[#C6FF33] shrink-0" /> Приоритет в каталоге
                          </span>
                        </div>
                      </div>

                      {/* Plan 3: Премиум (60 BYN) - Top Expert */}
                      <div
                        onClick={() => setFormData({ ...formData, plan: 'premium' })}
                        className={`p-3.5 rounded-2xl border transition-all cursor-pointer space-y-2 relative ${
                          formData.plan === 'premium'
                            ? 'bg-gradient-to-br from-[#18181B] via-[#18181B] to-[#F59E0B]/20 border-amber-400 ring-1 ring-amber-400'
                            : 'bg-[#121214] border-white/10 hover:border-amber-400/40'
                        }`}
                      >
                        <div className="absolute -top-2.5 right-4 bg-gradient-to-r from-amber-400 to-amber-500 text-black font-black text-[9px] uppercase px-2 py-0.5 rounded-full shadow-sm">
                          👑 Топ-эксперт
                        </div>

                        <div className="flex items-center justify-between">
                          <div>
                            <span className="font-extrabold text-sm sm:text-base text-white">«Премиум»</span>
                            <p className="text-xs text-white/60">ТОП-1 в поиске, видео-визитка и свой бренд</p>
                          </div>
                          <div className="text-right">
                            <span className="text-sm sm:text-base font-black text-amber-400 font-mono">
                              {isYearly ? '48 BYN' : '60 BYN'}
                            </span>
                            <span className="text-[10px] text-white/40 block">/месяц</span>
                          </div>
                        </div>

                        <div className="pt-2 border-t border-white/5 grid grid-cols-2 gap-1 text-[11px] text-white/80">
                          <span className="flex items-center gap-1.5 font-medium">
                            <Check className="w-3.5 h-3.5 text-amber-400 shrink-0" /> Всё из тарифа «Про»
                          </span>
                          <span className="flex items-center gap-1.5 font-medium">
                            <Check className="w-3.5 h-3.5 text-amber-400 shrink-0" /> 👑 ТОП-1 в каталоге
                          </span>
                          <span className="flex items-center gap-1.5 font-medium">
                            <Check className="w-3.5 h-3.5 text-amber-400 shrink-0" /> Видео-визитка в анкете
                          </span>
                          <span className="flex items-center gap-1.5 font-medium">
                            <Check className="w-3.5 h-3.5 text-amber-400 shrink-0" /> Свой брендинг комнат
                          </span>
                        </div>
                      </div>

                    </div>

                    <div className="p-3 bg-white/5 border border-white/10 rounded-xl text-xs text-white/70 flex items-center gap-2.5">
                      <ShieldCheck className="w-4 h-4 text-[#C6FF33] shrink-0" />
                      <span><strong>14 дней бесплатно</strong> на любом тарифе. Карта не требуется, никаких автоматических списаний.</span>
                    </div>

                    <div className="flex items-center gap-2 pt-1">
                      <button
                        type="button"
                        onClick={() => setRegisterStep(2)}
                        className="h-12 px-4 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white border border-white/10 rounded-xl transition-all flex items-center justify-center gap-1.5 text-xs font-bold cursor-pointer"
                      >
                        <ArrowLeft className="w-4 h-4" />
                        <span>Назад</span>
                      </button>

                      <button
                        type="button"
                        onClick={submitRegistration}
                        disabled={isSubmitting || isSuccess}
                        className={`flex-1 h-12 font-extrabold text-xs sm:text-sm rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer shadow-[0_0_25px_rgba(198,255,51,0.25)] ${
                          isSuccess
                            ? 'bg-emerald-500 text-white'
                            : 'bg-[#C6FF33] text-black hover:bg-[#d4ff59]'
                        }`}
                      >
                        {isSuccess ? (
                          <>
                            <CheckCircle2 className="w-4 h-4" />
                            <span>Аккаунт создан! Входим в кабинет...</span>
                          </>
                        ) : isSubmitting ? (
                          <>
                            <Loader2 className="w-4 h-4 animate-spin text-black" />
                            <span>Создание аккаунта...</span>
                          </>
                        ) : (
                          <span>Создать аккаунт и начать 14 дней триала</span>
                        )}
                      </button>
                    </div>
                  </motion.div>
                )}

              </motion.div>
            )}

            {/* ═══════════════════════════════════════════════════ */}
            {/* MODE 3: FORGOT PASSWORD                              */}
            {/* ═══════════════════════════════════════════════════ */}
            {activeTab === 'forgot-password' && (
              <motion.div
                key={`forgot-step-${forgotStep}`}
                initial={{ opacity: 0, x: 16 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -16 }}
                transition={{ duration: 0.25 }}
                className="space-y-5"
              >
                {/* ─── STEP 1: ENTER EMAIL ─── */}
                {forgotStep === 1 && (
                  <div className="space-y-5">
                    <div className="space-y-1">
                      <div className="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center mb-3 text-[#C6FF33]">
                        <KeyRound className="w-5 h-5" />
                      </div>
                      <h2 className="text-2xl font-bold tracking-tight text-white">Восстановление пароля</h2>
                      <p className="text-xs sm:text-sm text-white/50">
                        Введите адрес электронной почты, к которому привязан ваш аккаунт. Мы вышлем 6-значный цифровой код.
                      </p>
                    </div>

                    <form onSubmit={handleSendResetCode} className="space-y-4 pt-1">
                      <div className="space-y-1.5">
                        <label className="block text-xs font-semibold text-white/80">
                          Электронная почта
                        </label>
                        <div className="relative">
                          <input
                            type="email"
                            value={resetEmail}
                            onChange={(e) => { setResetEmail(e.target.value); if (errorMessage) setErrorMessage(''); }}
                            placeholder="name@edusfera.by"
                            required
                            autoFocus
                            autoComplete="email"
                            className="w-full h-11 pl-10 pr-3.5 bg-[#141416] border border-white/10 rounded-xl text-xs sm:text-sm text-white placeholder:text-white/25 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                          />
                          <Mail className="w-4 h-4 text-white/40 absolute left-3.5 top-3.5" />
                        </div>
                      </div>

                      <button
                        type="submit"
                        disabled={isSubmitting || !resetEmail}
                        className="w-full h-12 font-extrabold text-xs sm:text-sm rounded-xl transition-all duration-200 cursor-pointer flex items-center justify-center gap-2 mt-2 shadow-lg bg-[#C6FF33] text-black hover:bg-[#b8f526] active:scale-[0.99] disabled:opacity-50"
                      >
                        {isSubmitting ? (
                          <>
                            <Loader2 className="w-4 h-4 animate-spin text-black" />
                            <span>Отправляем код...</span>
                          </>
                        ) : (
                          <>
                            <span>Получить код</span>
                            <ArrowRight className="w-4 h-4 text-black" />
                          </>
                        )}
                      </button>

                      <div className="text-center pt-2">
                        <button
                          type="button"
                          onClick={() => handleTabSwitch('login')}
                          className="text-xs font-semibold text-white/40 hover:text-white transition-colors cursor-pointer"
                        >
                          Я вспомнил пароль — вернуться ко входу
                        </button>
                      </div>
                    </form>
                  </div>
                )}

                {/* ─── STEP 2: ENTER 6-DIGIT CODE ─── */}
                {forgotStep === 2 && (
                  <div className="space-y-5">
                    <div className="space-y-1">
                      <div className="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center mb-3 text-[#C6FF33]">
                        <Mail className="w-5 h-5" />
                      </div>
                      <h2 className="text-2xl font-bold tracking-tight text-white">Введите код из письма</h2>
                      <p className="text-xs sm:text-sm text-white/50">
                        Мы отправили 6-значный цифровой код на <span className="text-white font-medium">{resetEmail}</span>.
                      </p>
                    </div>

                    <form onSubmit={handleVerifyResetCode} className="space-y-4 pt-1">
                      <div className="space-y-1.5">
                        <label className="block text-xs font-semibold text-white/80">
                          Одноразовый код (6 цифр)
                        </label>
                        <input
                          type="text"
                          inputMode="numeric"
                          pattern="[0-9]*"
                          maxLength={6}
                          value={resetCode}
                          onChange={(e) => {
                            const val = e.target.value.replace(/\D/g, '').slice(0, 6);
                            setResetCode(val);
                            if (errorMessage) setErrorMessage('');
                          }}
                          placeholder="••••••"
                          required
                          autoFocus
                          className="w-full h-14 px-3.5 bg-[#141416] border border-white/10 rounded-xl text-center text-2xl tracking-[0.4em] font-mono text-[#C6FF33] placeholder:text-white/20 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                        />
                      </div>

                      <button
                        type="submit"
                        disabled={isSubmitting || resetCode.length !== 6}
                        className="w-full h-12 font-extrabold text-xs sm:text-sm rounded-xl transition-all duration-200 cursor-pointer flex items-center justify-center gap-2 mt-2 shadow-lg bg-[#C6FF33] text-black hover:bg-[#b8f526] active:scale-[0.99] disabled:opacity-50"
                      >
                        {isSubmitting ? (
                          <>
                            <Loader2 className="w-4 h-4 animate-spin text-black" />
                            <span>Проверяем...</span>
                          </>
                        ) : (
                          <>
                            <span>Подтвердить код</span>
                            <ArrowRight className="w-4 h-4 text-black" />
                          </>
                        )}
                      </button>

                      <div className="flex items-center justify-between pt-2 text-xs">
                        <button
                          type="button"
                          onClick={() => { setForgotStep(1); setErrorMessage(''); }}
                          className="text-white/40 hover:text-white transition-colors cursor-pointer"
                        >
                          Изменить email
                        </button>
                        <button
                          type="button"
                          disabled={resendCooldown > 0 || isSubmitting}
                          onClick={handleSendResetCode}
                          className={`font-semibold transition-colors cursor-pointer ${
                            resendCooldown > 0 ? 'text-white/30 cursor-not-allowed' : 'text-[#C6FF33] hover:underline'
                          }`}
                        >
                          {resendCooldown > 0 ? `Отправить повторно (${resendCooldown}с)` : 'Отправить код ещё раз'}
                        </button>
                      </div>
                    </form>
                  </div>
                )}

                {/* ─── STEP 3: ENTER NEW PASSWORD ─── */}
                {forgotStep === 3 && (
                  <div className="space-y-5">
                    <div className="space-y-1">
                      <div className="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center mb-3 text-[#C6FF33]">
                        <Lock className="w-5 h-5" />
                      </div>
                      <h2 className="text-2xl font-bold tracking-tight text-white">Новый пароль</h2>
                      <p className="text-xs sm:text-sm text-white/50">
                        Придумайте новый надежный пароль (минимум 8 символов, буквы и цифры).
                      </p>
                    </div>

                    <form onSubmit={handleResetPasswordSubmit} className="space-y-4 pt-1">
                      <div className="space-y-1.5">
                        <label className="block text-xs font-semibold text-white/80">
                          Новый пароль
                        </label>
                        <div className="relative">
                          <input
                            type={showNewPassword ? 'text' : 'password'}
                            value={newPassword}
                            onChange={(e) => { setNewPassword(e.target.value); if (errorMessage) setErrorMessage(''); }}
                            placeholder="Минимум 8 символов"
                            required
                            autoFocus
                            autoComplete="new-password"
                            className="w-full h-11 pl-3.5 pr-10 bg-[#141416] border border-white/10 rounded-xl text-xs sm:text-sm text-white placeholder:text-white/25 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                          />
                          <button
                            type="button"
                            onClick={() => setShowNewPassword(!showNewPassword)}
                            className="absolute right-3.5 top-3 text-white/40 hover:text-white transition-colors cursor-pointer"
                            tabIndex={-1}
                          >
                            {showNewPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                          </button>
                        </div>
                      </div>

                      <div className="space-y-1.5">
                        <label className="block text-xs font-semibold text-white/80">
                          Повторите новый пароль
                        </label>
                        <div className="relative">
                          <input
                            type={showNewPassword ? 'text' : 'password'}
                            value={confirmPassword}
                            onChange={(e) => { setConfirmPassword(e.target.value); if (errorMessage) setErrorMessage(''); }}
                            placeholder="Повторите пароль"
                            required
                            autoComplete="new-password"
                            className="w-full h-11 pl-3.5 pr-10 bg-[#141416] border border-white/10 rounded-xl text-xs sm:text-sm text-white placeholder:text-white/25 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none transition-all"
                          />
                        </div>
                      </div>

                      <button
                        type="submit"
                        disabled={isSubmitting || !newPassword || !confirmPassword}
                        className={`w-full h-12 font-extrabold text-xs sm:text-sm rounded-xl transition-all duration-200 cursor-pointer flex items-center justify-center gap-2 mt-2 shadow-lg ${
                          isSuccess
                            ? 'bg-emerald-500 text-white'
                            : 'bg-[#C6FF33] text-black hover:bg-[#b8f526] active:scale-[0.99] disabled:opacity-50'
                        }`}
                      >
                        {isSuccess ? (
                          <>
                            <CheckCircle2 className="w-4 h-4 text-white" />
                            <span>{resetSuccessMessage || 'Пароль обновлён! Входим...'}</span>
                          </>
                        ) : isSubmitting ? (
                          <>
                            <Loader2 className="w-4 h-4 animate-spin text-black" />
                            <span>Сохраняем...</span>
                          </>
                        ) : (
                          <>
                            <span>Сохранить пароль и войти</span>
                            <Check className="w-4 h-4 text-black" />
                          </>
                        )}
                      </button>
                    </form>
                  </div>
                )}
              </motion.div>
            )}

          </AnimatePresence>

        </div>
      </div>
    </main>
  );
}
