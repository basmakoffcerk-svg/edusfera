<?php

declare(strict_types=1);

namespace App\Filament\Responses;

use App\Services\MultiAccountService;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Полный выход должен стирать cookie связанных аккаунтов
     * (edusfera_linked_ids). Раньше его чистил только кастомный POST /logout:
     * Filament-logout оставлял cookie на 30 дней, и на общем компьютере
     * следующий пользователь мог переключиться на любой аккаунт, ранее
     * заходивший в этом браузере, — без пароля.
     */
    public function toResponse($request): RedirectResponse
    {
        app(MultiAccountService::class)->clearAll();

        return redirect('/');
    }
}
