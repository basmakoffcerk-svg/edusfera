<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutUnauthorizedSiteAdminUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Filament::auth();
        $user = $guard->user();

        if ($user) {
            $panel = Filament::getPanel('site-admin');

            if ($panel && ! $user->canAccessPanel($panel)) {
                $guard->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return $next($request);
    }
}
