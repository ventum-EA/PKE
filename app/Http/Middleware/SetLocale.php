<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale so server-generated messages (validation
 * errors, auth errors) match the language the user sees in the UI.
 *
 * Priority: authenticated user's saved locale → X-Locale header →
 * Accept-Language → app default (lv).
 */
class SetLocale
{
    private const SUPPORTED = ['lv', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?? $request->header('X-Locale')
            ?? $request->getPreferredLanguage(self::SUPPORTED);

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale', 'lv');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
