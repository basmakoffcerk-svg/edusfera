<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUtmParameters
{
    private const COOKIE_NAME = 'edusfera_utm';

    private const COOKIE_LIFETIME_MINUTES = 60 * 24 * 30; // 30 days

    public function handle(Request $request, Closure $next): Response
    {
        $hasUtmInQuery = $request->hasAny([
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
        ]);

        $utmToStore = null;

        if ($hasUtmInQuery) {
            $utmToStore = [
                'source' => (string) $request->query('utm_source', ''),
                'medium' => (string) $request->query('utm_medium', ''),
                'campaign' => (string) $request->query('utm_campaign', ''),
                'content' => (string) $request->query('utm_content', ''),
                'term' => (string) $request->query('utm_term', ''),
                'referrer' => (string) $request->headers->get('referer', ''),
                'landing_page' => $request->path(),
                'captured_at' => now()->toIso8601String(),
            ];

            // Save in session
            session(['utm' => $utmToStore]);
        } elseif (! session()->has('utm') && $request->hasCookie(self::COOKIE_NAME)) {
            // Restore from cookie if session is fresh
            $cookieData = json_decode((string) $request->cookie(self::COOKIE_NAME), true);
            if (is_array($cookieData)) {
                session(['utm' => $cookieData]);
            }
        }

        /** @var Response $response */
        $response = $next($request);

        // Attach cookie if new UTM parameters were captured
        if ($utmToStore !== null) {
            $response->headers->setCookie(cookie(
                name: self::COOKIE_NAME,
                value: (string) json_encode($utmToStore),
                minutes: self::COOKIE_LIFETIME_MINUTES,
                path: '/',
                domain: null,
                secure: $request->isSecure(),
                httpOnly: false, // Accessible to JS if needed
                sameSite: 'lax'
            ));
        }

        return $response;
    }
}
