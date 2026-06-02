import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Laravel Echo — WebSocket client using Laravel Reverb (Pusher protocol).
 * Only load Pusher and connect if VITE_REVERB_APP_KEY AND VITE_REVERB_HOST
 * are both set in the build environment.
 */

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST;

if (reverbKey && reverbHost) {
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
                wsHost: reverbHost,
                wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
                wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
                forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
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
 * Register the service worker for offline support (PWA).
 * Only in production builds.
 */
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .then((registration) => {
                setInterval(() => registration.update(), 60 * 60 * 1000);
            })
            .catch((err) => {
                console.warn('Service worker registration failed:', err);
            });
    });
}
