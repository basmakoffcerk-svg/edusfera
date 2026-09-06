@php
    $cardTitle    = $cardTitle    ?? 'Вход в кабинет';
    $cardSubtitle = $cardSubtitle ?? 'Введите ваши данные для доступа к платформе Edusfera';
    $formSlot     = $formSlot     ?? '';
    $footerSlot   = $footerSlot   ?? '';
    $maxWidth     = $maxWidth     ?? 'max-w-md';
@endphp

<div class="min-h-screen min-h-[100dvh] w-full flex flex-col items-center justify-center p-4 sm:p-6 lg:p-8 bg-[#F8F9FC] font-sans antialiased text-slate-900 selection:bg-purple-500 selection:text-white" style="background-image: radial-gradient(circle at 50% 0%, rgba(124, 58, 237, 0.08) 0%, transparent 60%), radial-gradient(circle at 100% 100%, rgba(198, 255, 51, 0.07) 0%, transparent 60%);">

    <style>
        /* Сброс конфликтующих стилей внешнего контейнера Filament */
        .fi-simple-main, .fi-simple-layout {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
        }

        /* Принудительная вертикальная одноколоночная структура (отмена 2-колоночного сплита sm:grid-cols-3) */
        .fi-fo-component-ctn,
        .fi-form {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            gap: 1.25rem !important;
        }

        .fi-fo-field-wrp {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
        }

        .fi-fo-field-wrp > div {
            display: flex !important;
            flex-direction: column !important;
            grid-template-columns: none !important;
            gap: 0.375rem !important;
            width: 100% !important;
        }

        .fi-fo-field-wrp > div > div {
            grid-column: span 1 / span 1 !important;
            width: 100% !important;
        }

        .fi-fo-field-wrp-label {
            margin-bottom: 0.375rem !important;
            text-align: left !important;
            width: 100% !important;
        }
        
        .fi-fo-field-wrp-label label,
        .fi-fo-field-wrp-label span {
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            color: #334155 !important;
        }

        /* Полная ширина полей ввода (input wrapper) */
        .fi-input-wrp {
            width: 100% !important;
            border-radius: 0.625rem !important; /* 10px */
            border: 1px solid #CBD5E1 !important; /* slate-300 */
            background-color: #FFFFFF !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
            transition: all 0.2s ease !important;
            box-sizing: border-box !important;
        }

        .fi-input-wrp:focus-within {
            border-color: #7C3AED !important;
            box-shadow: 0 0 0 3.5px rgba(124, 58, 237, 0.16) !important;
        }

        .fi-input-wrp input {
            height: 2.75rem !important;
            font-size: 0.9375rem !important;
            padding-left: 0.875rem !important;
            padding-right: 0.875rem !important;
            width: 100% !important;
        }

        /* Кнопка отправки формы: full-width, violet-600 (#7C3AED), белая надпись, h-11, rounded-lg */
        .fi-btn-primary, 
        .fi-form-actions button,
        button[type="submit"],
        button[wire\:click="authenticate"] {
            background-color: #7C3AED !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 0.95rem !important;
            min-height: 2.75rem !important;
            height: 2.75rem !important;
            border-radius: 0.625rem !important;
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 4px 14px 0 rgba(124, 58, 237, 0.28) !important;
            transition: all 0.2s ease !important;
            border: none !important;
            cursor: pointer !important;
            margin-top: 0.5rem !important;
        }
        
        .fi-btn-primary:hover,
        .fi-form-actions button:hover,
        button[type="submit"]:hover {
            background-color: #6D28D9 !important;
            box-shadow: 0 6px 20px 0 rgba(124, 58, 237, 0.38) !important;
            transform: translateY(-1px);
        }

        .fi-btn-primary span,
        .fi-btn-primary label,
        .fi-form-actions button span,
        button[type="submit"] span {
            color: #ffffff !important;
            font-weight: 600 !important;
            opacity: 1 !important;
        }

        .fi-form-actions {
            margin-top: 0.5rem !important;
            width: 100% !important;
        }
    </style>

    <div class="w-full {{ $maxWidth }} mx-auto bg-white rounded-2xl p-5 sm:p-8 shadow-xl shadow-slate-200/70 border border-slate-200/80 transition-all duration-200">
        
        <!-- Логотип + названия бренда EDUSFERA -->
        <a href="/" class="flex flex-col items-center justify-center gap-3 mb-6 no-underline group">
            <div class="w-14 h-14 rounded-2xl bg-[#7D39EB] flex items-center justify-center shadow-md shadow-[#7D39EB]/25 transition-transform duration-300 group-hover:scale-105">
                <svg width="28" height="28" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32 10L54 32L32 54L10 32L32 10Z" stroke="#C6FF33" stroke-width="6" stroke-linejoin="round" />
                    <path d="M32 22L42 32L32 42L22 32L32 22Z" fill="#C6FF33" />
                </svg>
            </div>
            <span class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 font-rimma uppercase">EDUSFERA</span>
        </a>

        <!-- Заголовок и подзаголовок -->
        <div class="text-center mb-6">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $cardTitle }}</h1>
            @if($cardSubtitle)
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-1.5 leading-relaxed">{{ $cardSubtitle }}</p>
            @endif
        </div>

        <!-- Форма Filament -->
        <div class="w-full">
            {!! $formSlot !!}
        </div>

        <!-- Ссылка перехода внизу -->
        @if($footerSlot)
            <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs sm:text-sm text-slate-500 font-medium">
                {!! str_replace('<a ', '<a class="text-[#7C3AED] hover:text-[#6D28D9] font-semibold transition-colors underline decoration-2 underline-offset-4 decoration-[#7C3AED]/30 hover:decoration-[#7C3AED]" ', $footerSlot) !!}
            </div>
        @endif

        <!-- Бейджи безопасности -->
        <div class="mt-6 pt-2 flex items-center justify-center gap-3 text-[10px] sm:text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
            <span class="flex items-center gap-1.5">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Защита 256-bit SSL
            </span>
            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
            <span class="flex items-center gap-1.5">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Anti-Brute Force
            </span>
        </div>
    </div>
</div>
