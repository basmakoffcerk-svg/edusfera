import React, { useEffect, useRef, useState } from 'react';
import { ShieldCheck, Lock, AlertCircle, Loader2 } from 'lucide-react';

/**
 * Load external script asynchronously with deduplication.
 */
function loadScript(src) {
  return new Promise((resolve, reject) => {
    if (window.PaymentForm) {
      return resolve();
    }
    const existing = document.querySelector(`script[src="${src}"]`);
    if (existing) {
      if (existing.getAttribute('data-loaded') === 'true' || window.PaymentForm) {
        return resolve();
      }
      existing.addEventListener('load', () => resolve());
      existing.addEventListener('error', (e) => reject(e));
      return;
    }

    const script = document.createElement('script');
    script.src = src;
    script.async = true;
    script.setAttribute('data-alfabank-sdk', 'true');
    script.addEventListener('load', () => {
      script.setAttribute('data-loaded', 'true');
      resolve();
    });
    script.addEventListener('error', (e) => reject(e));
    document.head.appendChild(script);
  });
}

/**
 * AlfaBankWebSdkPayment - Official Multiframe Web SDK Component for Alfa-Bank Belarus
 * 
 * Compliant with PCI DSS SAQ A, 3-D Secure 2.0 and official documentation:
 * https://sandbox.alfabank.by/sandbox/ru/integration/sdk/web_sdk_multiframe.html
 */
export default function AlfaBankWebSdkPayment({
  mdOrder,
  sdkScriptUrl = 'https://abby.rbsuat.com/payment/modules/multiframe/main.js',
  apiContext = '/payment',
  amount = '',
  currency = 'BYN',
  customerDetails = {},
  theme = 'dark',
  buttonLabel = 'Оплатить',
  onSuccess,
  onError,
  onFormValidate,
  fallbackRedirectUrl = null,
}) {
  const panRef = useRef(null);
  const expiryRef = useRef(null);
  const cvcRef = useRef(null);
  const selectBindingRef = useRef(null);
  const saveCardRef = useRef(null);

  const [isLoading, setIsLoading] = useState(true);
  const [isPaying, setIsPaying] = useState(false);
  const [isFormValid, setIsFormValid] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);
  const [hasBindings, setHasBindings] = useState(false);
  const [canSaveCard, setCanSaveCard] = useState(false);

  // Customer metadata fields
  const [cardholderName, setCardholderName] = useState(customerDetails.cardholderName || '');
  const [email, setEmail] = useState(customerDetails.email || '');
  const [phone, setPhone] = useState(customerDetails.phone || '');

  const webSdkPaymentFormRef = useRef(null);

  const isDark = theme === 'dark';

  useEffect(() => {
    let isCancelled = false;

    if (!mdOrder) {
      setIsLoading(false);
      return;
    }

    async function initSdk() {
      try {
        setIsLoading(true);
        setErrorMessage(null);

        // 1. Load official Alfa-Bank Web SDK script
        await loadScript(sdkScriptUrl);

        if (isCancelled) return;

        if (!window.PaymentForm) {
          throw new Error('Библиотека PaymentForm не найдена после загрузки скрипта.');
        }

        // Clean up any previously initialized instance
        if (webSdkPaymentFormRef.current) {
          try {
            webSdkPaymentFormRef.current.destroy();
          } catch (e) {
            console.warn('[AlfaBankWebSdk] Destroy error:', e);
          }
          webSdkPaymentFormRef.current = null;
        }

        // 2. Initialize PaymentForm with styling matching design system
        const form = new window.PaymentForm({
          mdOrder: mdOrder,
          apiContext: apiContext,
          containerClassName: isDark ? 'alfa-sdk-field-dark' : 'alfa-sdk-field-light',
          language: 'ru',
          autoFocus: true,
          showPanIcon: true,
          panIconStyle: {
            height: '18px',
            top: 'calc(50% - 9px)',
            right: '12px',
          },
          bindingPanFormat: 'dddd **** **** dddd',
          onFormValidate: (isValid) => {
            setIsFormValid(isValid);
            if (onFormValidate) {
              onFormValidate(isValid);
            }
          },
          fields: {
            pan: {
              container: panRef.current,
              placeholder: '0000 0000 0000 0000',
            },
            expiry: {
              container: expiryRef.current,
              placeholder: 'ММ / ГГ',
            },
            cvc: {
              container: cvcRef.current,
              placeholder: 'CVC / CVV',
            },
          },
          styles: {
            base: {
              color: isDark ? '#FFFFFF' : '#0F172A',
              padding: '0px 14px',
              fontSize: '15px',
              fontFamily: "'Space Grotesk', -apple-system, BlinkMacSystemFont, monospace",
              letterSpacing: '0.04em',
            },
            focus: {
              color: isDark ? '#C6FF33' : '#7D39EB',
            },
            valid: {
              color: isDark ? '#C6FF33' : '#10B981',
            },
            invalid: {
              color: '#EF4444',
            },
            disabled: {
              color: '#6B7280',
            },
            placeholder: {
              base: {
                color: isDark ? 'rgba(255, 255, 255, 0.25)' : 'rgba(100, 116, 139, 0.4)',
              },
              focus: {
                color: 'transparent',
              },
            },
          },
        });

        webSdkPaymentFormRef.current = form;

        // 3. Call init()
        const initResult = await form.init();
        if (isCancelled) return;

        const session = initResult?.orderSession;
        if (session) {
          if (session.bindings && session.bindings.length > 0) {
            setHasBindings(true);
            if (selectBindingRef.current) {
              selectBindingRef.current.innerHTML = '<option value="new_card">Оплатить новой картой</option>';
              session.bindings.forEach((b) => {
                const opt = new Option(b.pan || 'Сохраненная карта', b.id);
                selectBindingRef.current.options.add(opt);
              });
            }
          }

          if (session.bindingEnabled) {
            setCanSaveCard(true);
          }
        }

        setIsLoading(false);
      } catch (err) {
        if (isCancelled) return;
        console.error('[AlfaBankWebSdk] Initialization error:', err);
        setErrorMessage(err.message || 'Ошибка инициализации платежной формы Альфа-Банка');
        setIsLoading(false);
        if (onError) onError(err);
      }
    }

    initSdk();

    return () => {
      isCancelled = true;
      if (webSdkPaymentFormRef.current) {
        try {
          webSdkPaymentFormRef.current.destroy();
        } catch (e) {
          console.warn('[AlfaBankWebSdk] Unmount cleanup error:', e);
        }
        webSdkPaymentFormRef.current = null;
      }
    };
  }, [mdOrder, sdkScriptUrl, apiContext]);

  const handleSelectBinding = (e) => {
    const bindingId = e.target.value;
    if (!webSdkPaymentFormRef.current) return;

    if (bindingId !== 'new_card') {
      webSdkPaymentFormRef.current.selectBinding(bindingId);
      if (saveCardRef.current) {
        saveCardRef.current.style.display = 'none';
      }
    } else {
      webSdkPaymentFormRef.current.selectBinding(null);
      if (saveCardRef.current) {
        saveCardRef.current.style.display = 'flex';
      }
    }
  };

  const handlePay = async (e) => {
    if (e) e.preventDefault();
    if (!webSdkPaymentFormRef.current || isPaying) return;

    if (cardholderName && !/^[A-Za-z\s.'-]+$/.test(cardholderName.trim())) {
      setErrorMessage('Имя держателя карты должно содержать только латинские буквы');
      return;
    }

    setIsPaying(true);
    setErrorMessage(null);

    try {
      const payload = {
        cardholderName: cardholderName ? cardholderName.trim().toUpperCase() : undefined,
        email: email ? email.trim() : undefined,
        phone: phone ? phone.trim() : undefined,
        jsonParams: {
          platform: 'Edusfera',
          client_time: new Date().toISOString(),
        },
      };

      const result = await webSdkPaymentFormRef.current.doPayment(payload);

      if (result && result.redirectUrl) {
        window.location.href = result.redirectUrl;
        return;
      }

      if (onSuccess) {
        onSuccess(result);
      } else if (result?.finishedPaymentInfo?.backUrl || result?.finishedPaymentInfo?.successUrl) {
        window.location.href = result.finishedPaymentInfo.successUrl || result.finishedPaymentInfo.backUrl;
      }
    } catch (err) {
      console.error('[AlfaBankWebSdk] Payment execution error:', err);
      const msg = err?.message || 'Не удалось выполнить платеж. Проверьте реквизиты карты.';
      setErrorMessage(msg);
      if (onError) onError(err);
    } finally {
      setIsPaying(false);
    }
  };

  return (
    <div className={`rounded-2xl p-5 border transition-all ${
      isDark 
        ? 'bg-[#121214]/95 border-white/10 text-white' 
        : 'bg-white border-slate-200 text-slate-900 shadow-sm'
    }`}>
      {/* Header with Alfa-Bank brand badge & 3-D Secure guarantee */}
      <div className="flex items-center justify-between gap-3 mb-5 pb-4 border-b border-white/10">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-lg bg-[#EF3124] flex items-center justify-center text-white font-black text-sm shadow-sm">
            А
          </div>
          <div>
            <div className="text-xs font-bold tracking-tight">ЗАО «Альфа-Банк» (Беларусь)</div>
            <div className={`text-[11px] ${isDark ? 'text-white/60' : 'text-slate-500'}`}>
              Безопасный платёжный шлюз Web SDK
            </div>
          </div>
        </div>

        <div className="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
          <Lock className="w-3 h-3" />
          <span>3-D Secure 2.0</span>
        </div>
      </div>

      {/* Saved bindings selector if available */}
      {hasBindings && (
        <div className="mb-4">
          <label className={`block text-[11px] font-bold uppercase tracking-wider mb-1.5 ${
            isDark ? 'text-white/70' : 'text-slate-600'
          }`}>
            Способ оплаты
          </label>
          <select
            ref={selectBindingRef}
            onChange={handleSelectBinding}
            className={`w-full h-11 px-3.5 rounded-xl text-sm font-medium border outline-none ${
              isDark 
                ? 'bg-[#18181B] border-white/10 text-white focus:border-[#C6FF33]' 
                : 'bg-slate-50 border-slate-200 text-slate-900 focus:border-violet-600'
            }`}
          >
            <option value="new_card">Оплатить новой картой</option>
          </select>
        </div>
      )}

      {/* Card Details Form (Isolated Iframes) */}
      <div className="space-y-4">
        {/* PAN field container */}
        <div>
          <label className={`block text-[11px] font-bold uppercase tracking-wider mb-1.5 ${
            isDark ? 'text-white/70' : 'text-slate-600'
          }`}>
            Номер карты
          </label>
          <div
            id="pan"
            ref={panRef}
            className={`w-full h-12 rounded-xl border flex items-center transition-all ${
              isDark 
                ? 'bg-[#18181B] border-white/10 hover:border-white/20' 
                : 'bg-slate-50 border-slate-200 hover:border-slate-300'
            }`}
          />
        </div>

        {/* Expiry & CVC row */}
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className={`block text-[11px] font-bold uppercase tracking-wider mb-1.5 ${
              isDark ? 'text-white/70' : 'text-slate-600'
            }`}>
              Срок действия
            </label>
            <div
              id="expiry"
              ref={expiryRef}
              className={`w-full h-12 rounded-xl border flex items-center transition-all ${
                isDark 
                  ? 'bg-[#18181B] border-white/10 hover:border-white/20' 
                  : 'bg-slate-50 border-slate-200 hover:border-slate-300'
              }`}
            />
          </div>

          <div>
            <label className={`block text-[11px] font-bold uppercase tracking-wider mb-1.5 ${
              isDark ? 'text-white/70' : 'text-slate-600'
            }`}>
              CVC / CVV
            </label>
            <div
              id="cvc"
              ref={cvcRef}
              className={`w-full h-12 rounded-xl border flex items-center transition-all ${
                isDark 
                  ? 'bg-[#18181B] border-white/10 hover:border-white/20' 
                  : 'bg-slate-50 border-slate-200 hover:border-slate-300'
              }`}
            />
          </div>
        </div>

        {/* Optional Cardholder Name */}
        <div>
          <label className={`block text-[11px] font-bold uppercase tracking-wider mb-1.5 ${
            isDark ? 'text-white/70' : 'text-slate-600'
          }`}>
            Имя держателя (как на карте)
          </label>
          <input
            type="text"
            value={cardholderName}
            onChange={(e) => setCardholderName(e.target.value.toUpperCase())}
            placeholder="IVAN IVANOV"
            className={`w-full h-12 px-3.5 rounded-xl text-sm font-mono uppercase tracking-wider border outline-none transition-all ${
              isDark 
                ? 'bg-[#18181B] border-white/10 text-white placeholder:text-white/20 focus:border-[#C6FF33]' 
                : 'bg-slate-50 border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-violet-600'
            }`}
          />
        </div>

        {/* Save Card Checkbox */}
        {canSaveCard && (
          <div ref={saveCardRef} className="flex items-center gap-2.5 pt-1">
            <input
              type="checkbox"
              id="save-card"
              className="w-4 h-4 rounded text-violet-600 focus:ring-0 focus:ring-offset-0 cursor-pointer accent-[#C6FF33]"
            />
            <label htmlFor="save-card" className={`text-xs cursor-pointer ${
              isDark ? 'text-white/70' : 'text-slate-600'
            }`}>
              Сохранить карту для быстрых будущих оплат
            </label>
          </div>
        )}
      </div>

      {/* Error alert if any */}
      {errorMessage && (
        <div className="mt-4 p-3.5 bg-red-500/10 border border-red-500/30 rounded-xl flex items-start gap-2.5 text-xs text-red-400">
          <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
          <div className="flex-1">
            <p className="font-semibold">{errorMessage}</p>
            {fallbackRedirectUrl && (
              <a
                href={fallbackRedirectUrl}
                className="mt-2 inline-block font-bold text-red-300 underline hover:text-red-200"
              >
                Оплатить через внешнюю форму банка →
              </a>
            )}
          </div>
        </div>
      )}

      {/* Submit Button */}
      <button
        type="button"
        onClick={handlePay}
        disabled={isLoading || isPaying}
        className={`w-full mt-5 h-12 rounded-xl font-bold text-sm flex items-center justify-center gap-2 transition-all cursor-pointer shadow-lg active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed ${
          isDark
            ? 'bg-[#C6FF33] hover:bg-[#d8ff66] text-black shadow-[#C6FF33]/15'
            : 'bg-slate-950 hover:bg-slate-800 text-white'
        }`}
      >
        {isPaying ? (
          <>
            <Loader2 className="w-4 h-4 animate-spin" />
            <span>Обработка платежа...</span>
          </>
        ) : isLoading ? (
          <>
            <Loader2 className="w-4 h-4 animate-spin" />
            <span>Подключение к Альфа-Банку...</span>
          </>
        ) : (
          <>
            <ShieldCheck className="w-4 h-4" />
            <span>
              {buttonLabel} {amount ? `${amount} ${currency}` : ''}
            </span>
          </>
        )}
      </button>

      {/* PCI-DSS & Bank Compliance Notice */}
      <div className="mt-4 flex items-center justify-center gap-2 text-[11px] text-center text-white/50">
        <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
        <span>Платежные данные защищены сквозным шифрованием PCI DSS SAQ A</span>
      </div>
    </div>
  );
}
