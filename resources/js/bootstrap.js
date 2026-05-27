import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Laravel Echo — WebSocket client using Laravel Reverb (Pusher protocol).
 * Required for multiplayer real-time moves, match notifications, and
 * online presence. Falls back gracefully if the Reverb server is unreachable.
 */
window.Pusher = Pusher;

try {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY ?? 'local',
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
    });
} catch (err) {
    console.warn('Laravel Echo init failed — multiplayer features will be unavailable:', err);
    // Provide a stub so useWebSocket calls don't throw
    window.Echo = {
        private: () => ({ listen: () => ({}), stopListening: () => ({}) }),
        join: () => ({ here: () => ({}), joining: () => ({}), leaving: () => ({}), listen: () => ({}) }),
        leave: () => {},
    };
}

/**
 * Register the service worker for offline support (PWA).
 * Only in production builds — dev mode is served by Vite with HMR, which
 * a service worker would interfere with.
 */
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .then((registration) => {
                // Check for updates every 60 minutes while the app is open
                setInterval(() => registration.update(), 60 * 60 * 1000);
            })
            .catch((err) => {
                console.warn('Service worker registration failed:', err);
            });
    });
}
