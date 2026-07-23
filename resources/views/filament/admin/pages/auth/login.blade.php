<x-filament-panels::page.simple>
    @php
        $asideTheme   = 'minimal';
        $cardTitle    = 'Вход в Edusfera';
        $cardSubtitle = 'Введите ваш e-mail и пароль для входа в кабинет';
        $switchLabel  = filament()->hasRegistration() ? 'Регистрация' : '';
        $switchHref   = filament()->hasRegistration() ? filament()->getRegistrationUrl() : '#';
    @endphp

    @php
        ob_start();
    @endphp

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

    @php
        $formSlot = ob_get_clean();

        $footerSlot = '';
        if (filament()->hasRegistration()) {
            $footerSlot = 'Ещё нет аккаунта? <a href="' . e(filament()->getRegistrationUrl()) . '">Зарегистрироваться</a>';
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
