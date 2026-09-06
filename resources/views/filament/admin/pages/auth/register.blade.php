<div class="w-full">
    @php
        $cardTitle    = 'Регистрация';
        $cardSubtitle = 'Заполните 3 простых шага для создания аккаунта';
    @endphp

    @php
        ob_start();
    @endphp

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="register">
        {{ $this->form }}
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_REGISTER_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    @php
        $formSlot = ob_get_clean();

        $footerSlot = '';
        if (filament()->hasLogin()) {
            $footerSlot = 'Уже есть аккаунт? <a href="' . e(filament()->getLoginUrl()) . '">Войти</a>';
        }
    @endphp

    @include('filament.admin.pages.auth._auth-design', [
        'cardTitle' => $cardTitle,
        'cardSubtitle' => $cardSubtitle,
        'formSlot' => $formSlot,
        'footerSlot' => $footerSlot,
        'maxWidth' => 'max-w-xl',
    ])
</div>
