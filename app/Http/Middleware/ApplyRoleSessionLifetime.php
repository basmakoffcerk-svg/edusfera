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
            $lifetime = in_array($user->role, ['tutor', 'admin'], true)
                ? 60 * 24 * 7
                : 60 * 24 * 30;

            config(['session.lifetime' => $lifetime]);
        }

        return $next($request);
    }
}
