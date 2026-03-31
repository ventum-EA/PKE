import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

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
