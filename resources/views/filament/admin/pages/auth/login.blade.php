<x-filament-panels::page.simple>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    <style>
        /* Сброс стилей Filament */
        .fi-simple-header {
            display: none !important;
        }

        .fi-simple-page {
            width: 100%;
            max-width: none;
        }

        .fi-simple-layout,
        .fi-simple-main-ctn {
            min-height: 100dvh !important;
            background: #f6f6f9 !important;
        }

        .fi-simple-main {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
            border-radius: 0 !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            --tw-ring-shadow: 0 0 #0000 !important;
        }

        /* Фирменный фон */
        .ed-auth-container {
            min-height: 100dvh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 4vh, 3rem);
            font-family: 'Inter', system-ui, sans-serif;
            background-color: #f6f6f9;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(125, 57, 235, 0.06) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(198, 255, 51, 0.08) 0%, transparent 40%);
            background-attachment: fixed;
            position: relative;
            overflow: hidden;
        }

        /* Декоративные размытые круги */
        .ed-auth-glow-1 {
            position: absolute;
            top: -10%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(198, 255, 51, 0.2);
            border-radius: 50%;
            filter: blur(120px);
            pointer-events: none;
        }

        .ed-auth-glow-2 {
            position: absolute;
            bottom: -10%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: rgba(125, 57, 235, 0.15);
            border-radius: 50%;
            filter: blur(120px);
            pointer-events: none;
        }

        /* Стеклянная карточка */
        .ed-auth-card {
            width: 100%;
            max-width: 28rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 
                0 4px 24px rgba(0, 0, 0, 0.02),
                0 20px 50px rgba(125, 57, 235, 0.05);
            border-radius: 2.5rem;
            padding: clamp(1.5rem, 5vh, 3rem);
            z-index: 10;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .ed-auth-card:hover {
            box-shadow: 
                0 4px 30px rgba(0, 0, 0, 0.03),
                0 25px 60px rgba(125, 57, 235, 0.08);
        }

        /* Логотип */
        .font-rimma {
            font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif;
        }

        .ed-brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 900;
            font-size: 1.85rem;
            letter-spacing: -0.05em;
            color: #0f1115;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .ed-brand-logo:hover {
            transform: scale(1.02);
        }

        /* Стилизация выпадающего списка select */
        .ed-auth-form .fi-input-wrp select {
            padding-right: 2.5rem !important;
        }


        /* Заголовки */
        .ed-auth-title {
            margin-top: 1.5rem;
            font-size: clamp(1.8rem, 4vw, 2.3rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #0f1115;
            line-height: 1.1;
        }

        .ed-auth-subtitle {
            margin-top: 0.5rem;
            font-size: 0.95rem;
            color: #6b7280;
            font-weight: 500;
            line-height: 1.5;
        }

        /* Формы и поля ввода */
        .ed-auth-form {
            margin-top: 2rem;
        }

        .ed-auth-form .fi-fo-field-wrp {
            margin-bottom: 1.25rem;
        }

        .ed-auth-form .fi-fo-field-wrp-label label {
            color: #0f1115 !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }

        .ed-auth-form .fi-input-wrp {
            border: 1px solid rgba(0, 0, 0, 0.06) !important;
            border-radius: 9999px !important;
            background: rgba(255, 255, 255, 0.9) !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.01) !important;
            transition: all 0.3s ease !important;
            overflow: hidden;
        }

        .ed-auth-form .fi-input-wrp:focus-within {
            border-color: #7D39EB !important;
            box-shadow: 0 0 0 4px rgba(125, 57, 235, 0.12) !important;
            background: #fff !important;
        }

        .ed-auth-form .fi-input-wrp input {
            min-height: 3.5rem;
            padding: 0 1.5rem !important;
            font-size: 0.95rem !important;
            font-weight: 500 !important;
            color: #0f1115 !important;
            border: 0 !important;
            background: transparent !important;
        }

        /* Чекбокс */
        .ed-auth-form .fi-fo-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ed-auth-form .fi-fo-checkbox label {
            color: #4b5563 !important;
            font-weight: 500 !important;
            font-size: 0.9rem !important;
        }

        /* Ссылка восстановления */
        .ed-auth-form .fi-input-wrp-hint {
            color: #7D39EB !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            text-decoration: none !important;
            margin-right: 1rem;
        }

        .ed-auth-form .fi-input-wrp-hint:hover {
            color: #6827d6 !important;
        }

        /* Кнопка отправки */
        .ed-auth-form .fi-btn {
            min-height: 3.5rem;
            border-radius: 9999px !important;
            background: #7D39EB !important;
            color: #fff !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            box-shadow: 0 10px 25px rgba(125, 57, 235, 0.25) !important;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }

        .ed-auth-form .fi-btn:hover {
            background: #6827d6 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 12px 30px rgba(125, 57, 235, 0.35) !important;
        }

        /* Футер формы */
        .ed-auth-footer {
            margin-top: 1.5rem;
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .ed-auth-link {
            color: #7D39EB;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s ease;
            margin-left: 0.25rem;
        }

        .ed-auth-link:hover {
            color: #6827d6;
        }

        /* Адаптивность под мобильные устройства */
        @media (max-width: 640px) {
            .ed-auth-container {
                padding: 0.75rem;
            }

            .ed-auth-card {
                border-radius: 2rem;
                padding: 1.5rem;
            }

            .ed-auth-form .fi-input-wrp input {
                min-height: 3.25rem;
                padding: 0 1.25rem !important;
            }

            .ed-auth-form .fi-btn {
                min-height: 3.25rem;
            }
        }
    </style>

    <div class="ed-auth-container">
        <div class="ed-auth-glow-1"></div>
        <div class="ed-auth-glow-2"></div>

        <main class="ed-auth-card">
            <!-- Верхняя строка с логотипом -->
            <div class="flex justify-center mb-6">
                <a href="{{ route('home') }}" class="font-rimma ed-brand-logo group">
                    EDUSFERA
                    <svg class="w-6 h-6 text-lime-400 group-hover:rotate-180 transition-transform duration-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <path d="M12 2L22 12L12 22L2 12L12 2Z" />
                    </svg>
                </a>
            </div>

            <h1 class="ed-auth-title text-center">
                {{ $this->getHeading() }}
            </h1>

            @if (method_exists($this, 'getSubHeading') && $this->getSubHeading())
                <p class="ed-auth-subtitle text-center">
                    {{ $this->getSubHeading() }}
                </p>
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

            <div class="ed-auth-form">
                <x-filament-panels::form id="form" wire:submit="authenticate">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </x-filament-panels::form>
            </div>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

            <p class="ed-auth-footer">
                Нет аккаунта?
                @if (filament()->hasRegistration())
                    <a href="{{ filament()->getRegistrationUrl() }}" class="ed-auth-link">Регистрация</a>
                @endif
            </p>
        </main>
    </div>
</x-filament-panels::page.simple>
