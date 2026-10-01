import React from 'react';
import { 
  ArrowUpRight, 
  Mail, 
  Phone, 
  MapPin, 
  Clock
} from 'lucide-react';

/* ─── Apple Premium Minimalist Footer ─── */
export default function Footer() {
  const platformLinks = [
    { label: 'О компании', href: '/about' },
    { label: 'Каталог репетиторов', href: '/tutors' },
    { label: 'Преподавателям', href: '/for-tutors' },
    { label: 'ИИ-Диагностика РИКЗ', href: '/diagnostic', badge: 'ИИ 2026' },
    { label: 'Новости и статьи', href: '/news' },
  ];

  const legalLinks = [
    { label: 'Публичная оферта и тарифы', href: '/offer' },
    { label: 'Конфиденциальность (Закон 99-З)', href: '/privacy-policy' },
    { label: 'Безопасность платежей', href: '/payment-security' },
    { label: 'Правила возврата и отмены', href: '/refund-policy' },
  ];

  const socialLinks = [
    { 
      name: 'Telegram', 
      href: 'https://t.me/edusfera', 
      svg: (
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <line x1="22" y1="2" x2="11" y2="13"></line>
          <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
        </svg>
      )
    },
    { 
      name: 'Instagram', 
      href: 'https://instagram.com', 
      svg: (
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
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
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
          <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
        </svg>
      )
    },
  ];

  return (
    <footer 
      className="w-full bg-[#000000] border-t border-white/[0.08] text-[#86868b] font-sans antialiased"
      style={{ contentVisibility: 'auto', containIntrinsicSize: '1px 500px' }}
    >
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-12">
        
        {/* ─── Top 4-Column Directory Grid ─── */}
        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-12 gap-8 lg:gap-10 pb-12 border-b border-white/[0.08]">
          
          {/* Column 1: Brand & Identity (lg:col-span-4) */}
          <div className="col-span-2 md:col-span-4 lg:col-span-4 space-y-4">
            <a href="/" className="inline-flex items-center gap-2.5 text-white transition-opacity hover:opacity-90 group">
              <div className="w-7 h-7 rounded-lg bg-[#7D39EB] flex items-center justify-center shadow-sm shrink-0">
                <svg width="18" height="18" viewBox="0 0 64 64">
                  <path d="M32 10L54 32L32 54L10 32L32 10Z" fill="none" stroke="#C6FF33" strokeWidth="6" strokeLinejoin="round" />
                  <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                </svg>
              </div>
              <span className="text-lg font-bold tracking-tight text-white uppercase font-rimma">
                EDUSFERA
              </span>
            </a>

            <p className="text-xs text-[#86868b] leading-relaxed max-w-sm">
              Белорусская образовательная платформа для репетиторов и подготовки к ЦТ/ЦЭ. Интерактивный класс, автоматические чеки для самозанятых и безопасная оплата с 0% комиссии на уроки.
            </p>

            {/* Social Icons */}
            <div className="pt-1 flex items-center gap-2">
              {socialLinks.map((item) => (
                <a
                  key={item.name}
                  href={item.href}
                  target="_blank"
                  rel="noreferrer"
                  className="w-8 h-8 flex items-center justify-center rounded-lg bg-white/[0.04] hover:bg-white/[0.1] border border-white/[0.08] hover:border-white/20 text-[#86868b] hover:text-white transition-all"
                  aria-label={item.name}
                >
                  {item.svg}
                </a>
              ))}
            </div>
          </div>

          {/* Column 2: Платформа (lg:col-span-2 md:col-span-2) */}
          <div className="col-span-1 md:col-span-2 lg:col-span-2 space-y-3">
            <h4 className="text-xs font-semibold text-[#f5f5f7] tracking-tight uppercase">
              Платформа
            </h4>
            <ul className="space-y-2.5 text-xs">
              {platformLinks.map((item) => (
                <li key={item.label}>
                  <a
                    href={item.href}
                    className="text-[#86868b] hover:text-white transition-colors inline-flex items-center gap-1.5"
                  >
                    <span>{item.label}</span>
                    {item.badge && (
                      <span className="text-[9px] font-bold px-1.5 py-0.5 rounded bg-white/10 text-[#C6FF33] border border-white/10">
                        {item.badge}
                      </span>
                    )}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Column 3: Документы (lg:col-span-3 md:col-span-2) */}
          <div className="col-span-1 md:col-span-2 lg:col-span-3 space-y-3">
            <h4 className="text-xs font-semibold text-[#f5f5f7] tracking-tight uppercase">
              Документы
            </h4>
            <ul className="space-y-2.5 text-xs">
              {legalLinks.map((item) => (
                <li key={item.label}>
                  <a
                    href={item.href}
                    className="text-[#86868b] hover:text-white transition-colors"
                  >
                    {item.label}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          {/* Column 4: Связь и поддержка (lg:col-span-3 md:col-span-4) */}
          <div className="col-span-2 md:col-span-4 lg:col-span-3 space-y-3">
            <h4 className="text-xs font-semibold text-[#f5f5f7] tracking-tight uppercase">
              Связь и поддержка
            </h4>
            <ul className="space-y-2.5 text-xs">
              <li>
                <a 
                  href="tel:+375295190821" 
                  className="text-white font-semibold hover:text-[#C6FF33] transition-colors inline-flex items-center gap-2"
                >
                  <Phone className="w-3.5 h-3.5 text-[#C6FF33] shrink-0" />
                  <span>+375 (29) 519-08-21</span>
                </a>
              </li>
              <li>
                <a 
                  href="mailto:edusferaby@gmail.com" 
                  className="text-[#86868b] hover:text-white transition-colors inline-flex items-center gap-2"
                >
                  <Mail className="w-3.5 h-3.5 text-[#86868b] shrink-0" />
                  <span>edusferaby@gmail.com</span>
                </a>
              </li>
              <li className="text-[#6e6e73] inline-flex items-center gap-2">
                <Clock className="w-3.5 h-3.5 text-[#6e6e73] shrink-0" />
                <span>Пн–Пт 09:00 – 18:00 (Минск)</span>
              </li>
              <li className="pt-1">
                <a 
                  href="/contacts" 
                  className="text-xs font-medium text-white/90 hover:text-[#C6FF33] transition-colors inline-flex items-center gap-1 group"
                >
                  <span>Контакты и реквизиты</span>
                  <span className="transition-transform group-hover:translate-x-0.5">→</span>
                </a>
              </li>
            </ul>
          </div>

        </div>

        {/* ─── Payments Row: Apple Minimalist Presentation ─── */}
        <div className="py-8 border-b border-white/[0.08]">
          <p className="text-[11px] font-medium text-[#86868b] mb-4">
            Принимаем к онлайн-оплате через интернет-эквайринг ЗАО «Альфа-Банк»:
          </p>
          <div className="flex flex-wrap items-center gap-3 sm:gap-3.5">
            <div className="bg-white rounded-xl px-4 py-2 flex items-center justify-center h-12 border border-white/20 shadow-md hover:scale-[1.02] transition-transform">
              <img src="/logo/alfa-bank.svg" alt="ЗАО «Альфа-Банк»" width="125" height="28" loading="lazy" decoding="async" className="h-7 max-w-[125px] object-contain" />
            </div>
            <div className="bg-white rounded-xl px-4 py-2 flex items-center justify-center h-12 border border-white/20 shadow-md hover:scale-[1.02] transition-transform">
              <img src="/logo/belkart.png" alt="БЕЛКАРТ" width="100" height="28" loading="lazy" decoding="async" className="h-7 max-w-[100px] object-contain" />
            </div>
            <div className="bg-white rounded-xl px-4 py-2 flex items-center justify-center h-12 border border-white/20 shadow-md hover:scale-[1.02] transition-transform">
              <img src="/logo/belkart-internetparol.svg" alt="Белкарт ИнтернетПароль" width="110" height="28" loading="lazy" decoding="async" className="h-7 max-w-[100px] object-contain" />
            </div>
            <div className="bg-white rounded-xl px-4 py-2 flex items-center justify-center h-12 border border-white/20 shadow-md hover:scale-[1.02] transition-transform">
              <img src="/logo/visa.svg" alt="VISA" width="75" height="24" loading="lazy" decoding="async" className="h-6 max-w-[75px] object-contain" />
            </div>
            <div className="bg-white rounded-xl px-4 py-2 flex items-center justify-center h-12 border border-white/20 shadow-md hover:scale-[1.02] transition-transform">
              <img src="/logo/visa-secure.svg" alt="Visa Secure" width="90" height="26" loading="lazy" decoding="async" className="h-6 max-w-[90px] object-contain" />
            </div>
            <div className="bg-white rounded-xl px-4 py-2 flex items-center justify-center h-12 border border-white/20 shadow-md hover:scale-[1.02] transition-transform">
              <img src="/logo/mastercard.svg" alt="MasterCard" width="65" height="28" loading="lazy" decoding="async" className="h-7 max-w-[65px] object-contain" />
            </div>
            <div className="bg-white rounded-xl px-4 py-2 flex items-center justify-center h-12 border border-white/20 shadow-md hover:scale-[1.02] transition-transform">
              <img src="/logo/mastercard-id-check.svg" alt="Mastercard Identity Check" width="90" height="28" loading="lazy" decoding="async" className="h-7 max-w-[90px] object-contain" />
            </div>
            <div className="bg-white rounded-xl px-4 py-2 flex items-center justify-center h-12 border border-white/20 shadow-md hover:scale-[1.02] transition-transform">
              <img src="/logo/apple-pay.svg" alt="Apple Pay" width="65" height="24" loading="lazy" decoding="async" className="h-6 max-w-[65px] object-contain" />
            </div>
            <div className="bg-white rounded-xl px-4 py-2 flex items-center justify-center h-12 border border-white/20 shadow-md hover:scale-[1.02] transition-transform">
              <img src="/logo/samsung-pay.svg" alt="Samsung Pay" width="85" height="24" loading="lazy" decoding="async" className="h-6 max-w-[85px] object-contain" />
            </div>
          </div>
        </div>

        {/* ─── Apple Footnotes: Legal & Compliance ─── */}
        <div className="py-6 border-b border-white/[0.08] text-[11px] leading-relaxed text-[#6e6e73] space-y-2">
          <p>
            <strong>ООО «Эдусфера»</strong> · УНП 192854899 · Зарегистрировано Минским горисполкомом 04.05.2026 г. Юридический адрес: 220100, Республика Беларусь, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29. Телефон: <a href="tel:+375295190821" className="text-[#86868b] hover:text-white transition-colors">+375 (29) 519-08-21</a> · Email: <a href="mailto:edusferaby@gmail.com" className="text-[#86868b] hover:text-white transition-colors">edusferaby@gmail.com</a>. Режим работы: Пн–Пт 09:00 – 18:00.
          </p>
          <p>
            Безопасность передачи данных обеспечивается 256-битным шифрованием TLS/SSL и технологиями 3D-Secure 2.0 по международному стандарту PCI DSS v4.0 через эквайринг ЗАО «Альфа-Банк». Деятельность не подлежит лицензированию в соответствии с законодательством Республики Беларусь.
          </p>
        </div>

        {/* ─── Bottom Bar: Copyright, Links, Country ─── */}
        <div className="pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-[#6e6e73]">
          <div className="flex flex-wrap items-center justify-center sm:justify-start gap-x-4 gap-y-1.5 text-center sm:text-left">
            <span>Copyright © 2026 ООО «Эдусфера». Все права защищены.</span>
            <span className="hidden sm:inline text-white/10">|</span>
            <a href="/offer" className="hover:text-[#f5f5f7] transition-colors">Пользовательское соглашение</a>
            <span className="hidden sm:inline text-white/10">|</span>
            <a href="/privacy-policy" className="hover:text-[#f5f5f7] transition-colors">Конфиденциальность</a>
            <span className="hidden sm:inline text-white/10">|</span>
            <a href="/payment-security" className="hover:text-[#f5f5f7] transition-colors">Безопасность платежей</a>
            <span className="hidden sm:inline text-white/10">|</span>
            <a href="/refund-policy" className="hover:text-[#f5f5f7] transition-colors">Правила возврата</a>
          </div>

          <div className="flex items-center gap-1.5 text-[#86868b] shrink-0 font-medium">
            <span>Беларусь</span>
          </div>
        </div>

      </div>
    </footer>
  );
}
