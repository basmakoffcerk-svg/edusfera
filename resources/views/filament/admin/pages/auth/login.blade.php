<div class="w-full">
    @php
        $cardTitle    = $this->getHeading() ?: 'Вход в систему';
        $cardSubtitle = $this->getSubheading() ?: 'Введите ваш e-mail и пароль для входа в кабинет';
    @endphp

    @php
        ob_start();
    @endphp

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    @php
        $formSlot = ob_get_clean();

        $footerSlot = '';
        if (filament()->hasRegistration()) {
            $footerSlot = 'Ещё нет аккаунта? <a href="' . e(filament()->getRegistrationUrl()) . '">Зарегистрироваться</a>';
        }
    @endphp

    @include('filament.admin.pages.auth._auth-design', [
        'cardTitle' => $cardTitle,
        'cardSubtitle' => $cardSubtitle,
        'formSlot' => $formSlot,
        'footerSlot' => $footerSlot,
        'maxWidth' => 'max-w-md',
    ])
</div>
