<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Detect HTTPS behind reverse proxies (Cloudflare Tunnel, nginx, etc.)
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $isHttps = $forwardedProto === 'https'
            || ($forwardedProto && str_contains($forwardedProto, 'https'))
            || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        if ($isHttps) {
            URL::forceScheme('https');
            $_SERVER['HTTPS'] = 'on';
        }

        // Force session/cookie domain to null at runtime so cookies
        // are scoped to the current host — works with any tunnel domain.
        // This overrides any cached config value.
        config([
            'session.domain' => null,
            'session.secure' => $isHttps,
        ]);

        // Dynamically add the current host to Sanctum's stateful domains
        // so tunnels (cloudflared, ngrok) work without manual .env changes.
        $host = $_SERVER['HTTP_HOST'] ?? null;
        if ($host) {
            $current = config('sanctum.stateful', []);
            if (is_array($current) && !in_array($host, $current)) {
                $current[] = $host;
                config(['sanctum.stateful' => $current]);
            }
        }
    }
}
