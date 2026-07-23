<x-filament-panels::page.simple>
    @php
        $asideTheme   = 'minimal';
        $cardTitle    = 'Регистрация в Edusfera';
        $cardSubtitle = 'Заполните 3 простых шага для создания аккаунта';
        $switchLabel  = filament()->hasLogin() ? 'Войти' : '';
        $switchHref   = filament()->hasLogin() ? filament()->getLoginUrl() : '#';
    @endphp

    @php
        ob_start();
    @endphp

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <div class="ed-auth-form">
        <x-filament-panels::form id="form" wire:submit="register">
            {{ $this->form }}
        </x-filament-panels::form>
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_REGISTER_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    @php
        $formSlot = ob_get_clean();

        $footerSlot = '';
        if (filament()->hasLogin()) {
            $footerSlot = 'Уже есть аккаунт? <a href="' . e(filament()->getLoginUrl()) . '">Войти</a>';
        }
    @endphp

    @include('filament.admin.pages.auth._auth-design', [
        'asideTheme' => $asideTheme,
        'cardTitle' => $cardTitle,
        'cardSubtitle' => $cardSubtitle,
        'switchLabel' => $switchLabel,
        'switchHref' => $switchHref,
        'formSlot' => $formSlot,
        'footerSlot' => $footerSlot,
    ])
</x-filament-panels::page.simple>
