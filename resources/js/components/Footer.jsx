import React from 'react';
import { motion } from 'motion/react';
import { 
  ShieldCheck, 
  Sparkles, 
  ArrowUpRight, 
  Mail, 
  Phone, 
  MapPin, 
  Clock, 
  FileText,
  CheckCircle2
} from 'lucide-react';

/* ─── 1. LogoIcon Component ─── */
function LogoIcon() {
  return (
    <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-[#7D39EB] to-[#5B21B6] border border-violet-400/40 flex items-center justify-center shadow-lg shadow-violet-900/30 shrink-0">
      <svg width="22" height="22" viewBox="0 0 64 64" className="w-5 h-5">
        <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
        <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
      </svg>
    </div>
  );
}

/* ─── 2. FooterCard Component (Layered Card Aesthetic) ─── */
function FooterCard() {
  const platformLinks = [
    { label: 'Каталог репетиторов', href: '/tutors' },
    { label: 'Преподавателям', href: '/for-tutors' },
    { label: 'ИИ-Диагностика РИКЗ', href: '/diagnostic', badge: 'ИИ 2026' },
    { label: 'Новости и статьи', href: '/news' },
  ];

  const legalLinks = [
    { label: 'Публичная оферта и тарифы', href: '/offer' },
    { label: 'Конфиденциальность (Закон 99-З)', href: '/privacy-policy' },
    { label: 'Правила оплаты и безопасность', href: '/payment-security' },
    { label: 'Правила возврата и отмены', href: '/refund-policy' },
  ];

  const socialLinks = [
    { 
      name: 'Telegram', 
      href: 'https://t.me/edusfera', 
      svg: (
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <line x1="22" y1="2" x2="11" y2="13"></line>
          <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
        </svg>
      )
    },
    { 
      name: 'Instagram', 
      href: 'https://instagram.com', 
      svg: (
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
          <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
          <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
        </svg>
      )
    },
    { 
      name: 'YouTube', 
      href: 'https://youtube.com', 
      svg: (
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
          <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
        </svg>
      )
    },
  ];

  return (
    <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      {/* Outer Layered Container */}
      <div className="bg-[#0B0F19]/90 rounded-[40px] sm:rounded-[48px] border border-slate-800/80 shadow-2xl overflow-hidden backdrop-blur-2xl">
        
        {/* Inner Box */}
        <div className="bg-[#101726]/85 rounded-[32px] sm:rounded-[40px] m-2 sm:m-3 border border-slate-700/40 p-6 sm:p-10 lg:p-12 shadow-inner">
          
          {/* Main Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-10">
            
            {/* Brand Column (lg:col-span-4) */}
            <div className="lg:col-span-4 space-y-6">
              <div className="flex items-center gap-3">
                <LogoIcon />
                <a href="/" className="text-2xl font-black tracking-tight text-white uppercase font-rimma">
                  EDUSFERA
                </a>
              </div>

              <p className="text-slate-400 text-sm leading-relaxed max-w-sm font-normal">
                Первая белорусская платформа умной подготовки к ЦТ и ЦЭ с ИИ-агентами, подбором проверенных репетиторов из реестра и безопасными платежами.
              </p>

              <div className="flex items-center gap-2 flex-wrap pt-1">
                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-900/90 border border-slate-700/60 text-xs font-semibold text-[#C6FF33]">
                  <span className="w-2 h-2 rounded-full bg-[#C6FF33] animate-pulse"></span>
                  Платформа 2026 · Реестр РБ
                </span>
              </div>

              {/* Socials Group */}
              <div className="pt-2">
                <p className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                  Мы в соцсетях
                </p>
                <div className="flex items-center gap-2.5">
                  {socialLinks.map((item) => (
                    <a
                      key={item.name}
                      href={item.href}
                      target="_blank"
                      rel="noreferrer"
                      className="w-10 h-10 flex items-center justify-center rounded-xl border border-slate-700/60 bg-slate-800/60 hover:bg-slate-700 hover:border-[#C6FF33]/50 transition-all hover:scale-105 active:scale-95 group text-slate-300 hover:text-[#C6FF33] shadow-sm"
                      aria-label={item.name}
                    >
                      <span className="transition-transform group-hover:scale-110">
                        {item.svg}
                      </span>
                    </a>
                  ))}
                </div>
              </div>
            </div>

            {/* Column 2: Платформа (lg:col-span-2) */}
            <div className="lg:col-span-2 space-y-4">
              <h4 className="text-xs font-bold text-slate-300 uppercase tracking-widest">
                Платформа
              </h4>
              <ul className="space-y-3 text-sm">
                {platformLinks.map((item) => (
                  <li key={item.label}>
                    <a
                      href={item.href}
                      className="text-slate-400 hover:text-[#C6FF33] transition-colors flex items-center justify-between group"
                    >
                      <span>{item.label}</span>
                      {item.badge ? (
                        <span className="text-[10px] px-1.5 py-0.5 rounded-md bg-violet-500/20 text-violet-300 border border-violet-500/30 font-bold">
                          {item.badge}
                        </span>
                      ) : (
                        <ArrowUpRight className="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity text-[#C6FF33]" />
                      )}
                    </a>
                  </li>
                ))}
              </ul>
            </div>

            {/* Column 3: Правовые документы (lg:col-span-3) */}
            <div className="lg:col-span-3 space-y-4">
              <h4 className="text-xs font-bold text-slate-300 uppercase tracking-widest">
                Документы
              </h4>
              <ul className="space-y-3 text-sm">
                {legalLinks.map((item) => (
                  <li key={item.label}>
                    <a
                      href={item.href}
                      className="text-slate-400 hover:text-white transition-colors flex items-center justify-between group"
                    >
                      <span>{item.label}</span>
                      <ArrowUpRight className="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity text-slate-400" />
                    </a>
                  </li>
                ))}
              </ul>
            </div>

            {/* Column 4: Контакты и режим работы (lg:col-span-3) */}
            <div className="lg:col-span-3 space-y-4">
              <h4 className="text-xs font-bold text-slate-300 uppercase tracking-widest">
                Контакты и офис
              </h4>
              <ul className="space-y-3 text-sm">
                <li>
                  <a href="/contacts" className="text-slate-300 font-semibold hover:text-[#C6FF33] transition-colors flex items-center gap-2">
                    <MapPin className="w-4 h-4 text-slate-400 shrink-0" />
                    <span>Контакты ООО «Эдусфера»</span>
                  </a>
                </li>
                <li>
                  <a href="mailto:edusferaby@gmail.com" className="text-slate-400 hover:text-white transition-colors flex items-center gap-2">
                    <Mail className="w-4 h-4 text-slate-400 shrink-0" />
                    <span>edusferaby@gmail.com</span>
                  </a>
                </li>
                <li>
                  <a href="tel:+375295190821" className="text-slate-300 font-bold hover:text-[#C6FF33] transition-colors flex items-center gap-2">
                    <Phone className="w-4 h-4 text-[#C6FF33] shrink-0" />
                    <span>+375 (29) 519-08-21</span>
                  </a>
                </li>
              </ul>
              <div className="flex items-center gap-2 text-xs text-slate-400 pt-1">
                <Clock className="w-3.5 h-3.5 text-slate-500 shrink-0" />
                <span>Пн-Пт 09:00 – 18:00 (Сб-Вс: выходной)</span>
              </div>
            </div>

          </div>

          {/* Payment Systems & Acquiring Logos Strip */}
          <div className="mt-8 pt-8 border-t border-slate-800/80">
            <p className="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-4 text-center sm:text-left">
              Принимаем к онлайн-оплате через интернет-эквайринг ЗАО «Альфа-Банк»
            </p>
            <div className="flex flex-wrap items-center justify-center sm:justify-start gap-3">
              <div className="bg-white rounded-xl px-3 py-1.5 flex items-center justify-center shadow-sm h-10 border border-slate-700/30">
                <img src="/logo/alfa-bank.svg" alt="Альфа-Банк" className="h-6 max-w-[110px] object-contain" />
              </div>
              <div className="bg-white rounded-xl px-3 py-1.5 flex items-center justify-center shadow-sm h-10 border border-slate-700/30">
                <img src="/logo/belkart.svg" alt="БЕЛКАРТ" className="h-6 max-w-[90px] object-contain" />
              </div>
              <div className="bg-white rounded-xl px-3 py-1.5 flex items-center justify-center shadow-sm h-10 border border-slate-700/30">
                <img src="/logo/belkart-internetparol.svg" alt="Белкарт ИнтернетПароль" className="h-6 max-w-[90px] object-contain" />
              </div>
              <div className="bg-white rounded-xl px-3 py-1.5 flex items-center justify-center shadow-sm h-10 border border-slate-700/30">
                <img src="/logo/visa.svg" alt="VISA" className="h-5 max-w-[70px] object-contain" />
              </div>
              <div className="bg-white rounded-xl px-3 py-1.5 flex items-center justify-center shadow-sm h-10 border border-slate-700/30">
                <img src="/logo/visa-secure.svg" alt="Visa Secure" className="h-6 max-w-[85px] object-contain" />
              </div>
              <div className="bg-white rounded-xl px-3 py-1.5 flex items-center justify-center shadow-sm h-10 border border-slate-700/30">
                <img src="/logo/mastercard.svg" alt="MasterCard" className="h-6 max-w-[65px] object-contain" />
              </div>
              <div className="bg-white rounded-xl px-3 py-1.5 flex items-center justify-center shadow-sm h-10 border border-slate-700/30">
                <img src="/logo/mastercard-id-check.svg" alt="Mastercard Identity Check" className="h-6 max-w-[85px] object-contain" />
              </div>
              <div className="bg-white rounded-xl px-3 py-1.5 flex items-center justify-center shadow-sm h-10 border border-slate-700/30">
                <img src="/logo/apple-pay.svg" alt="Apple Pay" className="h-5 max-w-[60px] object-contain" />
              </div>
              <div className="bg-white rounded-xl px-3 py-1.5 flex items-center justify-center shadow-sm h-10 border border-slate-700/30">
                <img src="/logo/samsung-pay.svg" alt="Samsung Pay" className="h-5 max-w-[80px] object-contain" />
              </div>
            </div>
          </div>

          {/* Legal Information & Payment Systems Sub-Grid */}
          <div className="mt-8 pt-6 border-t border-slate-800/80 grid grid-cols-1 lg:grid-cols-2 gap-6 text-xs text-slate-400 leading-relaxed">
            <div className="space-y-1.5 bg-slate-900/40 p-4 rounded-2xl border border-slate-800/60">
              <h5 className="font-bold text-white text-xs uppercase tracking-wide flex items-center gap-1.5">
                <FileText className="w-3.5 h-3.5 text-[#C6FF33]" />
                Юридическая информация
              </h5>
              <p><strong>ООО «Эдусфера»</strong> · УНП 192854899 · Зарегистрировано Минским горисполкомом 04.05.2026 г.</p>
              <p><strong>Юридический адрес:</strong> 220100, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29 (Страна нахождения: Республика Беларусь).</p>
              <p><strong>Тел:</strong> +375 (29) 519-08-21 · <strong>Email:</strong> edusferaby@gmail.com · <strong>Режим работы:</strong> Пн-Пт 09:00 – 18:00</p>
              <p className="text-slate-500 text-[11px]">Деятельность не подлежит лицензированию в соответствии с законодательством Республики Беларусь.</p>
            </div>

            <div className="space-y-1.5 bg-slate-900/40 p-4 rounded-2xl border border-slate-800/60">
              <h5 className="font-bold text-white text-xs uppercase tracking-wide flex items-center gap-1.5">
                <ShieldCheck className="w-3.5 h-3.5 text-[#C6FF33]" />
                Платёжный эквайринг и безопасность
              </h5>
              <p><strong>Интернет-эквайринг:</strong> ЗАО «Альфа-Банк» · Защита платежей по стандарту PCI DSS v4.0</p>
              <p><strong>Поддерживаемые карты:</strong> VISA, Visa Secure, MasterCard, MasterCard ID Check, БЕЛКАРТ, Белкарт ИнтернетПароль, Apple Pay, Samsung Pay.</p>
              <p className="text-slate-500 text-[11px]">Безопасность передачи данных обеспечивается шифрованием TLS/SSL (256-bit) и технологиями 3D-Secure.</p>
            </div>
          </div>

        </div>

        {/* Bottom Legal Bar (Outside Inner Box, Inside Outer Wrap) */}
        <div className="px-6 sm:px-12 py-5 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-400">
          <p className="text-slate-400 font-medium text-center md:text-left">
            © 2026 ООО «Эдусфера». Все права защищены.
          </p>

          <div className="flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-slate-400 font-medium">
            <span>Минск, Беларусь</span>
            <div className="w-[1px] h-3.5 bg-slate-700 hidden sm:block" />
            <a href="/offer" className="hover:text-white transition-colors">
              Пользовательское соглашение
            </a>
            <div className="w-[1px] h-3.5 bg-slate-700 hidden sm:block" />
            <span className="inline-flex items-center gap-1 text-[#C6FF33]">
              ⚡ Платформа v2.0
            </span>
          </div>
        </div>

      </div>
    </div>
  );
}

/* ─── 3. GlassText Component (Massive Handcrafted SVG Filter & Motion) ─── */
function GlassText() {
  return (
    <div className="relative w-full flex items-center justify-center select-none pt-4 pb-2 overflow-hidden pointer-events-none">
      {/* Invisible SVG defining the filter */}
      <svg className="absolute w-0 h-0 pointer-events-none" aria-hidden="true" focusable="false">
        <defs>
          <filter id="glass-effect" x="-50%" y="-50%" width="200%" height="200%">
            <feDropShadow dx="0" dy="4" stdDeviation="6" floodColor="#000000" floodOpacity="0.35" result="outer-shadow" />
            <feComponentTransfer in="SourceAlpha" result="alpha">
              <feFuncA type="linear" slope="1" />
            </feComponentTransfer>
            <feOffset in="alpha" dx="0" dy="4" result="offset-white" />
            <feGaussianBlur in="offset-white" stdDeviation="4" result="blur-white" />
            <feComposite in="alpha" in2="blur-white" operator="out" result="inner-white-mask" />
            <feFlood floodColor="#ffffff" floodOpacity="0.3" result="white-fill" />
            <feComposite in="white-fill" in2="inner-white-mask" operator="in" result="inner-white-final" />
            <feGaussianBlur in="alpha" stdDeviation="6" result="blur-black" />
            <feComposite in="alpha" in2="blur-black" operator="out" result="inner-black-mask" />
            <feFlood floodColor="#000000" floodOpacity="0.3" result="black-fill" />
            <feComposite in="black-fill" in2="inner-black-mask" operator="in" result="inner-black-final" />
            <feMerge>
              <feMergeNode in="outer-shadow" />
              <feMergeNode in="SourceGraphic" />
              <feMergeNode in="inner-white-final" />
              <feMergeNode in="inner-black-final" />
            </feMerge>
          </filter>
        </defs>
      </svg>

      {/* Motion-animated Giant Brand Typography */}
      <motion.div
        initial={{ opacity: 0, scale: 0.98 }}
        whileInView={{ opacity: 1, scale: 1 }}
        transition={{ duration: 1.8, ease: [0.16, 1, 0.3, 1] }}
        className="relative"
      >
        <h1
          className="text-[min(24vw,340px)] font-black tracking-tighter leading-none select-none text-white/80 font-rimma uppercase text-center px-4"
          style={{ filter: 'url(#glass-effect)' }}
        >
          edusfera
        </h1>
      </motion.div>
    </div>
  );
}

/* ─── 4. Main Exported Footer Component ─── */
export default function Footer() {
  return (
    <footer className="w-full flex flex-col items-center gap-0 pt-16 pb-4 bg-[#010101] text-white">
      <FooterCard />
      <GlassText />
    </footer>
  );
}
