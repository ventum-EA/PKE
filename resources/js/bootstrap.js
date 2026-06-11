import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Laravel Echo — WebSocket client using Laravel Reverb (Pusher protocol).
 *
 * Only requires VITE_REVERB_APP_KEY at build time. Host, port, and scheme
 * are auto-detected from the current page URL so the same build works on
 * any deployment (localhost, Railway, custom domain) without rebuild.
 *
 * Expects the server to proxy WebSocket requests (/app/*) to Reverb.
 */

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    // Auto-detect from current page — works for any deployment
    const isSecure = window.location.protocol === 'https:';
    const wsHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
    const wsPort = import.meta.env.VITE_REVERB_PORT || (isSecure ? 443 : 80);

    // Dynamically import to avoid loading Pusher when not needed
    Promise.all([
        import('laravel-echo'),
        import('pusher-js'),
    ]).then(([EchoModule, PusherModule]) => {
        const Pusher = PusherModule.default;
        Pusher.logToConsole = false;
        window.Pusher = Pusher;

        try {
            window.Echo = new EchoModule.default({
                broadcaster: 'reverb',
                key: reverbKey,
                wsHost: wsHost,
                wsPort: isSecure ? 443 : wsPort,
                wssPort: isSecure ? wsPort : 443,
                forceTLS: isSecure,
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
            });
        } catch (err) {
            console.warn('Laravel Echo init failed:', err);
        }
    }).catch(() => {
        // Pusher/Echo not available — stub already in place
    });
}

// Provide a stub so components never throw, regardless of whether Echo loaded
if (!window.Echo) {
    const noop = () => ({
        listen: () => ({}),
        stopListening: () => ({}),
        here: function() { return this; },
        joining: function() { return this; },
        leaving: function() { return this; },
    });
    window.Echo = {
        private: noop,
        join: noop,
        leave: () => {},
    };
}

/**
 * Service worker for offline support (PWA) — currently disabled.
 *
 * The SW's navigate handler (network-first for HTML) interferes with
 * Sanctum SPA auth flow, causing Chrome tabs to freeze on login/register
 * form submission. Disabled until the interaction is resolved.
 *
 * To re-enable: uncomment the block below and investigate the
 * SW fetch handler's interaction with POST-then-navigate auth flows.
 */
// if ('serviceWorker' in navigator && import.meta.env.PROD) {
//     window.addEventListener('load', () => {
//         navigator.serviceWorker
//             .register('/sw.js', { scope: '/' })
//             .then((registration) => {
//                 setInterval(() => registration.update(), 60 * 60 * 1000);
//             })
//             .catch((err) => {
//                 console.warn('Service worker registration failed:', err);
//             });
//     });
// }

// Unregister any previously installed service worker to prevent stale
// fetch interception from causing auth freezes.
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then((registrations) => {
        registrations.forEach((r) => r.unregister());
    });
}
