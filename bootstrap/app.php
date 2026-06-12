<?php

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['middleware' => ['api']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (Docker, Cloudflare Tunnel, ngrok, etc.)
        // so cookies get the right domain and secure flag.
        $middleware->trustProxies(at: '*');

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Localize server-generated messages (validation/auth errors) to the
        // user's UI language instead of always falling back to English.
        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Rate limiting: return a clean, localized JSON message instead of
        // the framework's hardcoded English "Too Many Attempts." (which,
        // with debug on, also leaked a full stack trace).
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => __('errors.too_many_attempts'),
                ], 429, array_filter([
                    'Retry-After' => $e->getHeaders()['Retry-After'] ?? null,
                ]));
            }
        });
    })
    ->create();
