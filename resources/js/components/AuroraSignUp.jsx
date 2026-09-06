import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Eye, EyeOff, Loader2, CheckCircle2, ShieldCheck, 
  CreditCard, ArrowRight, ArrowLeft, Lock, Sparkles, Check, Building2
} from 'lucide-react';
import axios from 'axios';
import AlfaBankWebSdkPayment from './AlfaBankWebSdkPayment';

const GoogleIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24">
    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
  </svg>
);

export default function App() {
  const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
  const initialRole = urlParams?.get('role') || 'student';
  const initialPlan = urlParams?.get('plan') || 'pro';
  const isDirectLogin = typeof window !== 'undefined' && window.location.pathname.includes('login');

  // Steps: 1 = Register/Login, 2 = Choose Plan (for tutors), 3 = Alfa-Bank Acquiring (for tutors)
  const [currentStep, setCurrentStep] = useState(1);
  const [showPassword, setShowPassword] = useState(false);
  const [isLoginMode, setIsLoginMode] = useState(isDirectLogin);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  
  // Tutor Plan & Payment State
  const [isYearly, setIsYearly] = useState(false);
  const [selectedPlan, setSelectedPlan] = useState(initialPlan);
  const [paymentMethod, setPaymentMethod] = useState('alfa'); // 'alfa' or 'erip'
  const [alfaSdkData, setAlfaSdkData] = useState(null);
  const [alfaSdkLoading, setAlfaSdkLoading] = useState(false);
  
  // Card input states
  const [cardNumber, setCardNumber] = useState('');
  const [cardExpiry, setCardExpiry] = useState('');
  const [cardCvc, setCardCvc] = useState('');
  const [cardHolder, setCardHolder] = useState('');

  useEffect(() => {
    if (currentStep === 3 && paymentMethod === 'alfa') {
      setAlfaSdkLoading(true);
      axios.post('/api/subscription/init-alfa-sdk', {
        plan: selectedPlan,
        isYearly: isYearly,
      })
      .then((res) => {
        if (res.data?.success && res.data?.mdOrder) {
          setAlfaSdkData(res.data);
        }
      })
      .catch((err) => {
        console.warn('[AuroraSignUp] Alfa SDK init fallback:', err);
      })
      .finally(() => {
        setAlfaSdkLoading(false);
      });
    }
  }, [currentStep, paymentMethod, selectedPlan, isYearly]);

  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    email: '',
    password: '',
    role: initialRole,
    plan: initialPlan,
  });

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
    if (errorMessage) setErrorMessage('');
  };

  const formatCardNumber = (value) => {
    const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    const matches = v.match(/\d{4,16}/g);
    const match = (matches && matches[0]) || '';
    const parts = [];
    for (let i = 0, len = match.length; i < len; i += 4) {
      parts.push(match.substring(i, i + 4));
    }
    if (parts.length) {
      return parts.join(' ');
    } else {
      return value;
    }
  };

  const formatExpiry = (value) => {
    const clean = value.replace(/[^0-9]/g, '');
    if (clean.length >= 2) {
      return `${clean.substring(0, 2)}/${clean.substring(2, 4)}`;
    }
    return clean;
  };

  // Step 1: Register Account
  const handleRegisterSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setErrorMessage('');

    try {
      const endpoint = isLoginMode ? '/api/auth/login' : '/api/auth/register';
      const response = await axios.post(endpoint, formData);

      if (response.data.success) {
        // If it's a tutor registering, advance to Step 2 (Select Plan)
        if (!isLoginMode && formData.role === 'tutor') {
          setIsSubmitting(false);
          setCurrentStep(2);
        } else {
          setIsSuccess(true);
          setTimeout(() => {
            window.location.href = response.data.redirect || '/admin';
          }, 400);
        }
      }
    } catch (err) {
      const msg =
        err.response?.data?.message ||
        (err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(', ')
          : 'Произошла ошибка авторизации.');
      setErrorMessage(msg);
      setIsSubmitting(false);
    }
  };

  // Step 2: Confirm Plan Selection and go to Acquiring
  const handlePlanSelectContinue = () => {
    setCurrentStep(3);
  };

  // Step 3: Submit Alfa-Bank Acquiring / Card Binding
  const handleAcquiringSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setErrorMessage('');

    try {
      const response = await axios.post('/api/subscription/confirm-plan', {
        plan: selectedPlan,
        isYearly: isYearly,
        paymentMethod: paymentMethod,
      });

      if (response.data.success) {
        setIsSuccess(true);
        setTimeout(() => {
          window.location.href = response.data.redirect || '/admin';
        }, 600);
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Ошибка обработки платежа в эквайринге Альфа-Банка.';
      setErrorMessage(msg);
      setIsSubmitting(false);
    }
  };

  const getPlanDetails = () => {
    if (selectedPlan === 'basic') return { title: 'Basic', price: isYearly ? '192 BYN/год' : '20 BYN/мес', monthlyPrice: '20.00 BYN' };
    if (selectedPlan === 'premium') return { title: 'Premium', price: isYearly ? '576 BYN/год' : '60 BYN/мес', monthlyPrice: '60.00 BYN' };
    return { title: 'Pro', price: isYearly ? '384 BYN/год' : '40 BYN/мес', monthlyPrice: '40.00 BYN' };
  };

  return (
    <main className="flex min-h-screen w-full bg-black selection:bg-[#C6FF33] selection:text-black p-2 transition-all duration-500 lg:h-screen lg:overflow-hidden lg:p-4 font-sans text-white antialiased">
      {/* Left Column (Hero & Background Video) */}
      <div className="relative hidden lg:flex w-[48%] flex-col items-center justify-end pb-24 px-12 rounded-3xl overflow-hidden shadow-2xl h-full border border-white/10">
        <video
          autoPlay
          muted
          loop
          playsInline
          className="absolute inset-0 w-full h-full object-cover"
        >
          <source src="/videos/aurora-bg.mp4" type="video/mp4" />
        </video>

        <div className="absolute inset-0 bg-black/40 backdrop-blur-[2px] pointer-events-none" />

        <motion.div
          className="z-10 w-full max-w-sm space-y-8 text-center"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ staggerChildren: 0.15, delayChildren: 0.2 }}
        >
          {/* Brand/Logo */}
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            <a
              href="/"
              className="flex items-center justify-center gap-2.5 cursor-pointer hover:opacity-80 transition-opacity"
            >
              <svg
                width="28"
                height="28"
                viewBox="0 0 64 64"
                className="w-7 h-7 rounded-lg shadow-sm"
              >
                <rect width="64" height="64" rx="14" fill="#7D39EB" />
                <path
                  d="M32 10L54 32L32 54L10 32L32 10Z"
                  fill="none"
                  stroke="#C6FF33"
                  strokeWidth="6"
                  strokeLinejoin="round"
                />
                <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
              </svg>
              <span className="text-xl font-bold tracking-tight text-white font-rimma uppercase">edusfera</span>
            </a>
          </motion.div>

          {/* Heading Block */}
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
            className="space-y-2"
          >
            <h1 className="text-3xl sm:text-4xl font-extrabold tracking-tight">
              {currentStep === 1 
                ? (isLoginMode ? 'Вход в Edusfera' : 'Регистрация')
                : currentStep === 2
                  ? 'Выбор тарифа'
                  : 'Эквайринг Альфа-Банка'}
            </h1>
            <p className="text-white/60 text-xs sm:text-sm leading-relaxed px-4">
              {currentStep === 1 && 'Пройдите быструю регистрацию для прямого доступа к личному кабинету.'}
              {currentStep === 2 && 'Первый месяц бесплатно на любом тарифе. Выберите возможности для работы.'}
              {currentStep === 3 && 'Безопасная привязка карты через защищенный шлюз ЗАО «Альфа-Банк» (Беларусь).'}
            </p>
          </motion.div>

          {/* Steps Progress */}
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
            className="space-y-2.5 text-left"
          >
            <StepItem 
              number={1} 
              text="Регистрация аккаунта" 
              active={currentStep === 1} 
              completed={currentStep > 1} 
            />
            <StepItem 
              number={2} 
              text="Выбор тарифа подписки" 
              active={currentStep === 2} 
              completed={currentStep > 2} 
            />
            <StepItem 
              number={3} 
              text="Оплата и эквайринг" 
              active={currentStep === 3} 
              completed={isSuccess} 
            />
          </motion.div>
        </motion.div>
      </div>

      {/* Right Column (Dynamic Screens) */}
      <div className="flex-1 flex flex-col items-center justify-center py-8 lg:py-6 px-4 sm:px-10 lg:px-12 xl:px-16 overflow-y-auto">
        <div className="w-full max-w-xl">
          
          <AnimatePresence mode="wait">
            {/* ─── SCREEN 1: REGISTRATION / LOGIN (CLEAN FORM) ─── */}
            {currentStep === 1 && (
              <motion.div
                key="step1"
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                transition={{ duration: 0.3 }}
                className="space-y-6"
              >
                <div className="space-y-1.5">
                  <h2 className="text-2xl sm:text-3xl font-bold tracking-tight">
                    {isLoginMode ? 'Вход в кабинет' : 'Создать профиль'}
                  </h2>
                  <p className="text-white/40 text-xs sm:text-sm">
                    Введите свои данные для прямого доступа к личному кабинету Edusfera.
                  </p>
                </div>

                {/* Social Buttons */}
                <div className="grid grid-cols-1 gap-3">
                  <SocialButton 
                    provider="google" 
                    icon={<GoogleIcon />} 
                    label="Войти через Google" 
                    role={formData.role} 
                    plan={formData.plan} 
                  />
                </div>

                {/* Divider */}
                <div className="relative flex items-center justify-center">
                  <div className="absolute inset-0 flex items-center">
                    <div className="w-full border-t border-white/10" />
                  </div>
                  <span className="relative bg-black px-4 text-[11px] font-medium text-white/40 uppercase tracking-widest">
                    Или E-mail
                  </span>
                </div>

                {/* Form */}
                <form onSubmit={handleRegisterSubmit} className="space-y-4">
                  {!isLoginMode && (
                    <>
                      {/* Role Selector */}
                      <div className="space-y-1.5">
                        <label className="block text-xs font-semibold text-white/80 uppercase tracking-wider">
                          Роль на платформе
                        </label>
                        <div className="grid grid-cols-3 gap-1.5 p-1 bg-[#141416] rounded-xl border border-white/10">
                          {[
                            { id: 'student', label: 'Ученик' },
                            { id: 'tutor', label: 'Репетитор' },
                            { id: 'parent', label: 'Родитель' },
                          ].map((r) => (
                            <button
                              key={r.id}
                              type="button"
                              onClick={() => setFormData({ ...formData, role: r.id })}
                              className={`py-2 text-xs font-bold rounded-lg transition-all cursor-pointer ${
                                formData.role === r.id
                                  ? 'bg-white text-black shadow-sm'
                                  : 'text-white/60 hover:text-white hover:bg-white/5'
                              }`}
                            >
                              {r.label}
                            </button>
                          ))}
                        </div>
                      </div>

                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <InputGroup
                          label="Имя"
                          name="firstName"
                          value={formData.firstName}
                          onChange={handleChange}
                          placeholder="Александр"
                          type="text"
                        />
                        <InputGroup
                          label="Фамилия"
                          name="lastName"
                          value={formData.lastName}
                          onChange={handleChange}
                          placeholder="Иванов"
                          type="text"
                        />
                      </div>
                    </>
                  )}

                  <InputGroup
                    label="E-mail"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    placeholder="tutor@edusfera.by"
                    type="email"
                  />

                  <div className="space-y-1.5 relative">
                    <InputGroup
                      label="Пароль"
                      name="password"
                      value={formData.password}
                      onChange={handleChange}
                      placeholder="••••••••"
                      type={showPassword ? 'text' : 'password'}
                      rightElement={
                        <button
                          type="button"
                          onClick={() => setShowPassword(!showPassword)}
                          className="absolute right-4 top-[36px] text-white/40 hover:text-white transition-colors cursor-pointer"
                        >
                          {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                      }
                    />
                    <p className="text-[11px] text-white/40 pt-0.5">
                      Минимум 8 символов (буквы и цифры).
                    </p>
                  </div>

                  {errorMessage && (
                    <div className="p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-xs font-medium">
                      {errorMessage}
                    </div>
                  )}

                  <button
                    type="submit"
                    disabled={isSubmitting || isSuccess}
                    className={`w-full h-12 font-bold text-sm rounded-xl transition-all mt-2 cursor-pointer flex items-center justify-center gap-2 ${
                      isSuccess
                        ? 'bg-emerald-500 text-white'
                        : 'bg-white text-black hover:bg-white/90 active:scale-[0.98] disabled:opacity-50'
                    }`}
                  >
                    {isSuccess ? (
                      <>
                        <CheckCircle2 className="w-4 h-4 text-white" />
                        <span>Успешно! Входим в кабинет...</span>
                      </>
                    ) : isSubmitting ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin" />
                        <span>Создание аккаунта...</span>
                      </>
                    ) : isLoginMode ? (
                      'Войти в личный кабинет'
                    ) : (
                      'Создать аккаунт и войти'
                    )}
                  </button>
                </form>

                <div className="text-center pt-2">
                  <button
                    type="button"
                    onClick={() => {
                      setIsLoginMode(!isLoginMode);
                      setErrorMessage('');
                    }}
                    className="text-xs text-white/60 hover:text-white transition-colors cursor-pointer"
                  >
                    {isLoginMode
                      ? 'Ещё нет аккаунта? Зарегистрироваться'
                      : 'Уже есть аккаунт? Войти в кабинет'}
                  </button>
                </div>
              </motion.div>
            )}

            {/* ─── SCREEN 2: TARIFF SELECTION SCREEN (AFTER REGISTRATION) ─── */}
            {currentStep === 2 && (
              <motion.div
                key="step2"
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                transition={{ duration: 0.3 }}
                className="space-y-6"
              >
                <div className="space-y-1.5">
                  <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#C6FF33]/10 border border-[#C6FF33]/30 text-[#C6FF33] font-bold text-xs">
                    <Sparkles className="w-3.5 h-3.5" />
                    <span>1 месяц бесплатно на любом тарифе</span>
                  </div>
                  <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    Выберите тарифный план
                  </h2>
                  <p className="text-white/60 text-xs sm:text-sm">
                    Первые 30 дней без списаний (0 BYN). Сменить или отменить тариф можно в любой момент в ЛК.
                  </p>
                </div>

                {/* Billing Period Toggle */}
                <div className="flex items-center justify-between p-1.5 bg-[#141416] rounded-xl border border-white/10">
                  <span className="text-xs font-semibold text-white/80 px-2">Период оплаты:</span>
                  <div className="flex gap-1">
                    <button
                      type="button"
                      onClick={() => setIsYearly(false)}
                      className={`px-3 py-1.5 text-xs font-bold rounded-lg transition-all ${
                        !isYearly ? 'bg-white text-black' : 'text-white/60 hover:text-white'
                      }`}
                    >
                      Ежемесячно
                    </button>
                    <button
                      type="button"
                      onClick={() => setIsYearly(true)}
                      className={`px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5 ${
                        isYearly ? 'bg-[#C6FF33] text-black' : 'text-white/60 hover:text-white'
                      }`}
                    >
                      <span>1 год</span>
                      <span className="text-[10px] bg-black text-[#C6FF33] px-1 py-0.2 rounded font-black uppercase">
                        -20%
                      </span>
                    </button>
                  </div>
                </div>

                {/* 3 Tariff Cards */}
                <div className="grid grid-cols-1 gap-3">
                  {/* Basic */}
                  <div
                    onClick={() => setSelectedPlan('basic')}
                    className={`p-4 rounded-2xl border transition-all cursor-pointer flex items-center justify-between ${
                      selectedPlan === 'basic'
                        ? 'bg-slate-900 border-white ring-1 ring-white'
                        : 'bg-[#121214]/80 border-white/10 hover:border-white/20'
                    }`}
                  >
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-sm text-white">Basic</span>
                        <span className="text-xs font-mono font-bold text-white/60">
                          {isYearly ? '16 BYN/мес' : '20 BYN/мес'}
                        </span>
                      </div>
                      <p className="text-xs text-white/50">Базовый профиль, просмотр заявок, базовый чат</p>
                    </div>
                    <div className={`w-5 h-5 rounded-full border flex items-center justify-center ${
                      selectedPlan === 'basic' ? 'border-white bg-white text-black' : 'border-white/30'
                    }`}>
                      {selectedPlan === 'basic' && <Check className="w-3.5 h-3.5" />}
                    </div>
                  </div>

                  {/* Pro (Featured) */}
                  <div
                    onClick={() => setSelectedPlan('pro')}
                    className={`p-4 rounded-2xl border transition-all cursor-pointer flex items-center justify-between relative ${
                      selectedPlan === 'pro'
                        ? 'bg-gradient-to-r from-slate-900 via-slate-900 to-[#C6FF33]/10 border-[#C6FF33] ring-1 ring-[#C6FF33]'
                        : 'bg-[#121214]/80 border-white/10 hover:border-[#C6FF33]/40'
                    }`}
                  >
                    <div className="absolute -top-2.5 right-4 bg-[#C6FF33] text-black font-black text-[9px] uppercase px-2 py-0.5 rounded-full">
                      Выбор большинства
                    </div>
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-sm text-white">Pro</span>
                        <span className="text-xs font-mono font-black text-[#C6FF33]">
                          {isYearly ? '32 BYN/мес' : '40 BYN/мес'}
                        </span>
                      </div>
                      <p className="text-xs text-white/70">
                        До 10 откликов на заявки, онлайн-расписание, автонапоминания и отчёты для НПД
                      </p>
                    </div>
                    <div className={`w-5 h-5 rounded-full border flex items-center justify-center shrink-0 ml-3 ${
                      selectedPlan === 'pro' ? 'border-[#C6FF33] bg-[#C6FF33] text-black' : 'border-white/30'
                    }`}>
                      {selectedPlan === 'pro' && <Check className="w-3.5 h-3.5" />}
                    </div>
                  </div>

                  {/* Premium */}
                  <div
                    onClick={() => setSelectedPlan('premium')}
                    className={`p-4 rounded-2xl border transition-all cursor-pointer flex items-center justify-between ${
                      selectedPlan === 'premium'
                        ? 'bg-slate-900 border-violet-400 ring-1 ring-violet-400'
                        : 'bg-[#121214]/80 border-white/10 hover:border-white/20'
                    }`}
                  >
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <span className="font-bold text-sm text-white">Premium</span>
                        <span className="text-xs font-mono font-bold text-violet-300">
                          {isYearly ? '48 BYN/мес' : '60 BYN/мес'}
                        </span>
                      </div>
                      <p className="text-xs text-white/50">Топ-5 в каталоге, безлимит откликов, видеокомната 45 мин</p>
                    </div>
                    <div className={`w-5 h-5 rounded-full border flex items-center justify-center ${
                      selectedPlan === 'premium' ? 'border-violet-400 bg-violet-400 text-black' : 'border-white/30'
                    }`}>
                      {selectedPlan === 'premium' && <Check className="w-3.5 h-3.5" />}
                    </div>
                  </div>
                </div>

                <div className="p-3 bg-white/5 border border-white/10 rounded-xl text-xs text-white/70 flex items-center gap-2">
                  <ShieldCheck className="w-4 h-4 text-[#C6FF33] shrink-0" />
                  <span>Сегодня к списанию <strong>0.00 BYN</strong>. Первый платёж спишется только через 30 дней.</span>
                </div>

                {/* Continue to Acquiring */}
                <button
                  type="button"
                  onClick={handlePlanSelectContinue}
                  className="w-full h-12 bg-[#C6FF33] text-black font-extrabold text-sm rounded-xl hover:bg-[#d4ff59] transition-all flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(198,255,51,0.25)] cursor-pointer"
                >
                  <span>Продолжить к оплате (0 BYN сегодня)</span>
                  <ArrowRight className="w-4 h-4" />
                </button>
              </motion.div>
            )}

            {/* ─── SCREEN 3: ALFA-BANK ACQUIRING & CHECKOUT ─── */}
            {currentStep === 3 && (
              <motion.div
                key="step3"
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                transition={{ duration: 0.3 }}
                className="space-y-5"
              >
                {/* Alfa-Bank Header */}
                <div className="p-4 bg-gradient-to-r from-red-950/40 via-[#141416] to-[#141416] border border-red-500/30 rounded-2xl flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-xl bg-[#EF3124] flex items-center justify-center text-white font-black text-lg">
                      А
                    </div>
                    <div>
                      <h3 className="text-sm font-bold text-white">ЗАО «Альфа-Банк» (Беларусь)</h3>
                      <p className="text-[11px] text-white/60">Интернет-эквайринг и безопасные платежи</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-1.5 text-[10px] font-bold text-white/50 bg-black/40 px-2 py-1 rounded-md border border-white/10">
                    <Lock className="w-3 h-3 text-emerald-400" />
                    <span>3-D Secure 2.0</span>
                  </div>
                </div>

                {/* Order Summary Box */}
                <div className="p-4 bg-[#141416] border border-white/10 rounded-2xl space-y-2">
                  <div className="flex justify-between items-center text-xs">
                    <span className="text-white/60">Выбранный тариф:</span>
                    <span className="font-bold text-white uppercase tracking-wider">
                      Тариф {getPlanDetails().title} ({isYearly ? '1 год' : '1 месяц'})
                    </span>
                  </div>
                  <div className="flex justify-between items-center text-xs">
                    <span className="text-white/60">Пробный период:</span>
                    <span className="font-bold text-[#C6FF33]">30 дней бесплатно</span>
                  </div>
                  <div className="pt-2 border-t border-white/10 flex justify-between items-baseline">
                    <span className="text-xs font-semibold text-white">К оплате сегодня:</span>
                    <span className="text-xl font-black text-[#C6FF33] font-mono">0.00 BYN</span>
                  </div>
                  <p className="text-[10px] text-white/40">
                    Первое продление {getPlanDetails().price} произойдёт через 30 дней. Отмена в 1 клик.
                  </p>
                </div>

                {/* Payment Method Switcher (Card vs ERIP) */}
                <div className="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => setPaymentMethod('alfa')}
                    className={`py-2 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 border transition-all cursor-pointer ${
                      paymentMethod === 'alfa'
                        ? 'bg-white text-black border-white'
                        : 'bg-[#141416] text-white/60 border-white/10 hover:text-white'
                    }`}
                  >
                    <CreditCard className="w-3.5 h-3.5" />
                    <span>Банковская карта</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => setPaymentMethod('erip')}
                    className={`py-2 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 border transition-all cursor-pointer ${
                      paymentMethod === 'erip'
                        ? 'bg-white text-black border-white'
                        : 'bg-[#141416] text-white/60 border-white/10 hover:text-white'
                    }`}
                  >
                    <Building2 className="w-3.5 h-3.5" />
                    <span>ЕРИП («Расчёт»)</span>
                  </button>
                </div>

                {/* Form based on selected payment method */}
                {paymentMethod === 'alfa' ? (
                  alfaSdkData?.mdOrder ? (
                    <AlfaBankWebSdkPayment
                      mdOrder={alfaSdkData.mdOrder}
                      sdkScriptUrl={alfaSdkData.web_sdk_url}
                      apiContext={alfaSdkData.api_context}
                      amount="0.00"
                      currency="BYN"
                      theme="dark"
                      buttonLabel="Привязать карту и активировать (0 BYN)"
                      customerDetails={{
                        email: formData.email,
                        cardholderName: `${formData.firstName} ${formData.lastName}`.trim().toUpperCase(),
                      }}
                      onSuccess={() => {
                        setIsSuccess(true);
                        setTimeout(() => {
                          window.location.href = alfaSdkData.redirect_url || '/admin';
                        }, 1200);
                      }}
                      onError={(err) => {
                        setErrorMessage(err?.message || 'Ошибка обработки платежа в Альфа-Банке');
                      }}
                    />
                  ) : (
                    <form onSubmit={handleAcquiringSubmit} className="space-y-3">
                      <div className="space-y-1">
                        <label className="block text-[11px] font-semibold text-white/70 uppercase">Номер карты</label>
                        <div className="relative">
                          <input
                            type="text"
                            required
                            maxLength={19}
                            value={cardNumber}
                            onChange={(e) => setCardNumber(formatCardNumber(e.target.value))}
                            placeholder="4258 0000 0000 0000"
                            className="bg-[#18181B] border border-white/10 rounded-xl h-11 px-4 text-sm font-mono text-white placeholder:text-white/20 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none w-full"
                          />
                          <div className="absolute right-3 top-3 flex items-center gap-1">
                            <span className="text-[9px] font-bold text-white/50 uppercase">БЕЛКАРТ / МИР / VISA</span>
                          </div>
                        </div>
                      </div>

                      <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                          <label className="block text-[11px] font-semibold text-white/70 uppercase">Срок действия</label>
                          <input
                            type="text"
                            required
                            maxLength={5}
                            value={cardExpiry}
                            onChange={(e) => setCardExpiry(formatExpiry(e.target.value))}
                            placeholder="ММ / ГГ"
                            className="bg-[#18181B] border border-white/10 rounded-xl h-11 px-4 text-sm font-mono text-white placeholder:text-white/20 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none w-full"
                          />
                        </div>
                        <div className="space-y-1">
                          <label className="block text-[11px] font-semibold text-white/70 uppercase">CVC / CVV</label>
                          <input
                            type="password"
                            required
                            maxLength={4}
                            value={cardCvc}
                            onChange={(e) => setCardCvc(e.target.value.replace(/[^0-9]/g, ''))}
                            placeholder="•••"
                            className="bg-[#18181B] border border-white/10 rounded-xl h-11 px-4 text-sm font-mono text-white placeholder:text-white/20 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none w-full"
                          />
                        </div>
                      </div>

                      <div className="space-y-1">
                        <label className="block text-[11px] font-semibold text-white/70 uppercase">Имя на карте (латиницей)</label>
                        <input
                          type="text"
                          required
                          value={cardHolder}
                          onChange={(e) => setCardHolder(e.target.value.toUpperCase())}
                          placeholder="IVAN IVANOV"
                          className="bg-[#18181B] border border-white/10 rounded-xl h-11 px-4 text-sm font-mono text-white placeholder:text-white/20 focus:border-[#C6FF33] focus:ring-1 focus:ring-[#C6FF33] outline-none w-full"
                        />
                      </div>

                      {errorMessage && (
                        <div className="p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-xs font-medium">
                          {errorMessage}
                        </div>
                      )}

                      <button
                        type="submit"
                        disabled={isSubmitting || isSuccess || alfaSdkLoading}
                        className="w-full h-12 bg-white text-black hover:bg-white/90 font-extrabold text-sm rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer shadow-lg mt-2"
                      >
                        {isSuccess ? (
                          <>
                            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                            <span>Карта привязана! Входим в кабинет...</span>
                          </>
                        ) : isSubmitting || alfaSdkLoading ? (
                          <>
                            <Loader2 className="w-4 h-4 animate-spin text-black" />
                            <span>Подключение к Альфа-Банку...</span>
                          </>
                        ) : (
                          'Привязать карту и активировать подписку (0 BYN)'
                        )}
                      </button>
                    </form>
                  )
                ) : (
                  /* ERIP Instructions Option */
                  <div className="space-y-4 pt-1">
                    <div className="p-4 bg-[#18181B] border border-white/10 rounded-2xl space-y-2 text-xs">
                      <div className="text-white/70">
                        Дерево ЕРИП: <strong>Образование и развитие → Информационные услуги → Эдусфера</strong>
                      </div>
                      <div className="text-white/70">
                        Лицевой счёт будет сгенерирован автоматически в вашем кабинете.
                      </div>
                    </div>

                    <button
                      type="button"
                      onClick={handleAcquiringSubmit}
                      disabled={isSubmitting || isSuccess}
                      className="w-full h-12 bg-white text-black hover:bg-white/90 font-extrabold text-sm rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer"
                    >
                      {isSubmitting ? (
                        <Loader2 className="w-4 h-4 animate-spin" />
                      ) : (
                        'Сформировать счёт в ЕРИП и войти в кабинет'
                      )}
                    </button>
                  </div>
                )}

                <div className="flex items-center justify-between pt-1">
                  <button
                    type="button"
                    onClick={() => setCurrentStep(2)}
                    className="text-xs text-white/50 hover:text-white flex items-center gap-1 transition-colors cursor-pointer"
                  >
                    <ArrowLeft className="w-3.5 h-3.5" />
                    <span>Назад к выбору тарифа</span>
                  </button>
                  <span className="text-[10px] text-white/40 flex items-center gap-1">
                    <ShieldCheck className="w-3 h-3 text-emerald-400" />
                    Безопасное 256-битное соединение
                  </span>
                </div>
              </motion.div>
            )}
          </AnimatePresence>

        </div>
      </div>
    </main>
  );
}

// ─── REUSABLE COMPONENTS ───

function StepItem({ number, text, active = false, completed = false }) {
  return (
    <div
      className={`flex items-center gap-3 p-3 rounded-2xl transition-all ${
        active
          ? 'bg-white text-black border border-white font-bold shadow-md'
          : completed
            ? 'bg-white/10 text-[#C6FF33] border border-[#C6FF33]/30'
            : 'bg-[#141416] text-white/40 border border-white/5'
      }`}
    >
      <div
        className={`w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 ${
          active
            ? 'bg-black text-white'
            : completed
              ? 'bg-[#C6FF33] text-black font-black'
              : 'bg-white/10 text-white/40'
        }`}
      >
        {completed ? '✓' : number}
      </div>
      <span className="text-xs sm:text-sm font-medium">{text}</span>
    </div>
  );
}

function SocialButton({ provider, icon, label, role = 'student', plan = 'pro' }) {
  const handleOAuthClick = () => {
    window.location.href = `/auth/${provider}/redirect?role=${encodeURIComponent(role)}&plan=${encodeURIComponent(plan)}`;
  };

  return (
    <button
      type="button"
      onClick={handleOAuthClick}
      className="flex items-center justify-center gap-2.5 h-11 bg-white/5 border border-white/10 hover:border-white/20 hover:bg-white/10 rounded-xl transition-all text-xs font-semibold text-white cursor-pointer active:scale-98 shadow-sm backdrop-blur-md"
    >
      {icon}
      <span>{label}</span>
    </button>
  );
}

function InputGroup({ label, name, value, onChange, placeholder, type, rightElement }) {
  return (
    <div className="space-y-1 relative w-full">
      <label className="block text-xs font-semibold text-white/80">{label}</label>
      <input
        type={type}
        name={name}
        value={value}
        onChange={onChange}
        required
        placeholder={placeholder}
        className="bg-[#141416] border border-white/10 rounded-xl h-11 px-4 text-xs sm:text-sm text-white placeholder:text-white/20 focus:border-white focus:ring-1 focus:ring-white outline-none w-full transition-all"
      />
      {rightElement}
    </div>
  );
}
