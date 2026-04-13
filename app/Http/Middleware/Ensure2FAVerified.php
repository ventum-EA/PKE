<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user has completed 2FA verification if enabled.
 *
 * Excludes the 2FA verification and logout endpoints themselves so the user
 * can actually submit their OTP code and log out if needed.
 */
class Ensure2FAVerified
{
    /**
     * Routes that bypass the 2FA check (the user needs these to complete
     * or abort the 2FA flow).
     */
    protected array $except = [
        'api/2fa/verify',
        'api/logout',
        'api/user',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // No user, or 2FA not enabled → pass through
        if (!$user || !$user->two_factor_enabled) {
            return $next($request);
        }

        // Already verified in this session
        if ($request->session()->get('2fa_verified')) {
            return $next($request);
        }

        // Allow exempt paths so the user can verify or log out
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        return response()->json([
            'message'      => '2FA verifikācija nepieciešama.',
            '2fa_required' => true,
        ], Response::HTTP_FORBIDDEN);
    }
}
