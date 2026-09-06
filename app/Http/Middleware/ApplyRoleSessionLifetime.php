<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyRoleSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $lifetime = ($user->isTutor() || $user->isAdmin())
                ? 60 * 24 // 24 часа для администраторов и репетиторов
                : 60 * 24 * 7; // 7 дней для студентов

            config(['session.lifetime' => $lifetime]);
        }

        return $next($request);
    }
}
