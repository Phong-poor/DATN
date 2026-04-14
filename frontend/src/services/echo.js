import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

import { getToken } from './auth';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // Base URL for broadcasting auth (private channels)
    authEndpoint: (import.meta.env.VITE_APP_URL || 'http://localhost:8000') + '/api/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${getToken()}`,
            Accept: 'application/json',
        },
    },
});

echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ Socket connected to Reverb!');
});

echo.connector.pusher.connection.bind('error', (err) => {
    console.error('❌ Socket connection error:', err);
});

export default echo;
