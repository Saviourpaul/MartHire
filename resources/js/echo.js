import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;
const host = import.meta.env.VITE_REVERB_HOST;

if (key && host) {
    const port = Number(import.meta.env.VITE_REVERB_PORT || 0);
    const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port || 80,
        wssPort: port || 443,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
    });
} else {
    window.Echo = null;
}
