<x-filament-panels::page.simple>
    <style>
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

        .ed-auth-layout {
            min-height: 100dvh;
            padding: clamp(0.5rem, 1.8vh, 1rem);
        }

        .ed-auth-shell {
            max-width: 1180px;
            margin: 0 auto;
            min-height: calc(100dvh - clamp(1rem, 3.6vh, 2rem));
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
            border-radius: 2rem;
            overflow: hidden;
            border: 1px solid rgba(125, 57, 235, 0.08);
            background: #ffffff;
            box-shadow: 0 30px 80px rgba(17, 17, 17, 0.08);
        }

        .ed-auth-aside {
            position: relative;
            padding: clamp(1.25rem, 2.8vh, 2.25rem);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(circle at 18% 18%, rgba(198, 255, 51, 0.18), transparent 18%),
                radial-gradient(circle at 82% 28%, rgba(255, 255, 255, 0.08), transparent 16%),
                linear-gradient(145deg, #050505 0%, #131313 45%, #7d39eb 100%);
        }

        .ed-auth-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            font-size: 1.75rem;
            color: #fff;
        }

        .ed-auth-brand::after {
            content: "";
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 999px;
            background: #c6ff33;
            box-shadow: 0 0 18px rgba(198, 255, 51, 0.8);
        }

        .ed-auth-kicker {
            display: inline-flex;
            margin-top: 2rem;
            padding: 0.5rem 0.85rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.09);
            color: #c6ff33;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .ed-auth-heading {
            margin: 1.25rem 0 0;
            max-width: 10ch;
            font-size: clamp(3rem, 5vw, 4.75rem);
            line-height: 0.94;
            letter-spacing: -0.06em;
            font-weight: 700;
        }

        .ed-auth-copy {
            margin-top: 1.25rem;
            max-width: 31rem;
            color: rgba(255, 255, 255, 0.76);
            font-size: 1.04rem;
            line-height: 1.8;
        }

        .ed-auth-main {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 2.2vh, 2rem);
            background:
                radial-gradient(circle at top right, rgba(125, 57, 235, 0.08), transparent 22%),
                radial-gradient(circle at bottom left, rgba(198, 255, 51, 0.14), transparent 18%),
                #f8f8fb;
        }

        .ed-auth-card {
            width: 100%;
            max-width: 30rem;
            padding: 2rem;
            border-radius: 1.75rem;
            border: 1px solid rgba(0, 0, 0, 0.06);
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 18px 48px rgba(17, 17, 17, 0.08);
            backdrop-filter: blur(20px);
        }

        .ed-auth-topline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .ed-auth-link {
            color: #7d39eb;
            text-decoration: none;
            font-weight: 600;
        }

        .ed-auth-link:hover {
            color: #0a0a0a;
        }

        .ed-auth-title {
            margin: 1.1rem 0 0;
            color: #0a0a0a;
            font-size: clamp(2rem, 4vw, 2.8rem);
            line-height: 1;
            letter-spacing: -0.05em;
            font-weight: 700;
        }

        .ed-auth-subtitle {
            margin: 0.8rem 0 0;
            color: #60646f;
            font-size: 1rem;
            line-height: 1.75;
        }

        .ed-auth-form {
            margin-top: 1.5rem;
        }

        .ed-auth-form .fi-fo-field-wrp {
            margin-bottom: 1rem;
        }

        .ed-auth-form .fi-fo-field-wrp-label {
            margin-bottom: 0.45rem;
        }

        .ed-auth-form .fi-fo-field-wrp-label label,
        .ed-auth-form .fi-fo-checkbox label {
            color: #0a0a0a;
            font-weight: 600;
        }

        .ed-auth-form .fi-input-wrp {
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            border-radius: 1.1rem !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        .ed-auth-form .fi-input-wrp:focus-within {
            border-color: #7d39eb !important;
            box-shadow: 0 0 0 4px rgba(125, 57, 235, 0.12) !important;
        }

        .ed-auth-form .fi-input-wrp input[type='text'],
        .ed-auth-form .fi-input-wrp input[type='email'],
        .ed-auth-form .fi-input-wrp input[type='password'],
        .ed-auth-form .fi-input-wrp input[type='tel'],
        .ed-auth-form .fi-input-wrp select {
            min-height: 3.65rem;
            border-radius: 1.1rem !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .ed-auth-form .fi-input-wrp input:focus,
        .ed-auth-form .fi-input-wrp select:focus {
            border: 0 !important;
            box-shadow: none !important;
        }

        .ed-auth-form .fi-input-wrp-hint {
            color: #7d39eb;
            font-weight: 600;
        }

        .ed-auth-form .fi-btn {
            min-height: 3.75rem;
            border-radius: 1.1rem !important;
            background: #0a0a0a !important;
            color: #fff !important;
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.12);
        }

        .ed-auth-form .fi-btn:hover {
            background: #7d39eb !important;
        }

        .ed-auth-footer {
            margin-top: 1rem;
            color: #747884;
            font-size: 0.9rem;
            line-height: 1.65;
        }

        @media (max-width: 1024px) {
            .ed-auth-shell {
                min-height: calc(100dvh - 1rem);
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .ed-auth-layout {
                padding: 0.5rem;
            }

            .ed-auth-shell {
                border-radius: 1.25rem;
            }

            .ed-auth-aside {
                padding: 1.25rem;
            }

            .ed-auth-main {
                padding: 0.85rem;
            }

            .ed-auth-card {
                padding: 1.1rem;
                border-radius: 1.25rem;
            }

            .ed-auth-heading {
                max-width: none;
                font-size: clamp(2.2rem, 13vw, 3.2rem);
            }

            .ed-auth-copy {
                font-size: 0.95rem;
                line-height: 1.65;
            }

            .ed-auth-topline {
                align-items: flex-start;
                flex-direction: column;
                gap: 0.4rem;
            }

            .ed-auth-title {
                font-size: 2rem;
            }
        }

        @media (max-height: 820px) {
            .ed-auth-aside,
            .ed-auth-main {
                padding: 1.15rem;
            }

            .ed-auth-card {
                padding: 1.25rem;
            }

            .ed-auth-heading {
                font-size: clamp(2.35rem, 8.2vh, 3.6rem);
            }

            .ed-auth-copy {
                line-height: 1.6;
            }

        }
    </style>

    <div class="ed-auth-layout">
        <div class="ed-auth-shell">
            <aside class="ed-auth-aside">
                <a href="{{ route('home') }}" class="ed-brand ed-auth-brand">Edusfera</a>
                <span class="ed-auth-kicker">Вход на платформу</span>
                <h1 class="ed-auth-heading">Учёба, чаты и записи в одном кабинете</h1>
                <p class="ed-auth-copy">
                    Вход по email или телефону.
                </p>
            </aside>

            <main class="ed-auth-main">
                <section class="ed-auth-card">
                    <div class="ed-auth-topline">
                        <a href="{{ route('home') }}" class="ed-brand text-2xl text-black">Edusfera</a>

                        @if (filament()->hasRegistration())
                            <a href="{{ filament()->getRegistrationUrl() }}" class="ed-auth-link">Регистрация</a>
                        @endif
                    </div>

                    <h2 class="ed-auth-title">Вход</h2>
                    <p class="ed-auth-subtitle">Введите данные аккаунта.</p>

                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

                    <x-filament-panels::form id="form" wire:submit="authenticate" class="ed-auth-form">
                        {{ $this->form }}

                        <x-filament-panels::form.actions
                            :actions="$this->getCachedFormActions()"
                            :full-width="$this->hasFullWidthFormActions()"
                        />
                    </x-filament-panels::form>

                    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

                    <p class="ed-auth-footer">
                        Нет аккаунта?
                        @if (filament()->hasRegistration())
                            <a href="{{ filament()->getRegistrationUrl() }}" class="ed-auth-link">Регистрация</a>
                        @endif
                    </p>
                </section>
            </main>
        </div>
    </div>
</x-filament-panels::page.simple>
