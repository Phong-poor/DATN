import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: (import.meta.env.VITE_APP_URL || 'http://localhost:8000') + '/api/broadcasting/auth',
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                const token = localStorage.getItem('token');
                fetch((import.meta.env.VITE_APP_URL || 'http://localhost:8000') + '/api/broadcasting/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        socket_id: socketId,
                        channel_name: channel.name
                    })
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Auth failed: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => callback(false, data))
                    .catch(error => {
                        console.error('Lỗi xác thực Socket:', error);
                        callback(true, error);
                    });
            }
        };
    },
});

echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ Socket connected to Reverb!');
});

echo.connector.pusher.connection.bind('error', (err) => {
    console.error('❌ Socket connection error:', err);
});

export default echo;
