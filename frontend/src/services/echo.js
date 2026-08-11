import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

import { getToken } from './auth'
import { backendBaseUrl } from './urls'

window.Pusher = Pusher

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY || '329e9fe1cfb4e86150ce'
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap1'

let echo

if (reverbKey) {
    const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname
    const reverbPort = import.meta.env.VITE_REVERB_PORT || 8080
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'http'

    echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: Number(reverbPort),
        wssPort: Number(reverbPort),
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `${backendBaseUrl}/api/broadcasting/auth`,
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                const token = getToken()
                if (!token) {
                    callback(true, new Error('Socket auth skipped: missing token'))
                    return
                }
                fetch(`${backendBaseUrl}/api/broadcasting/auth`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        Authorization: `Bearer ${token}`,
                    },
                    body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
                })
                    .then((res) => {
                        if (!res.ok) throw new Error(`Auth failed: ${res.status}`)
                        return res.json()
                    })
                    .then((data) => callback(false, data))
                    .catch((err) => callback(true, err))
            },
        }),
    })
} else {
    echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
        authEndpoint: `${backendBaseUrl}/api/broadcasting/auth`,
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                const token = getToken()
                if (!token) {
                    callback(true, new Error('Socket auth skipped: missing token'))
                    return
                }
                fetch(`${backendBaseUrl}/api/broadcasting/auth`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        Authorization: `Bearer ${token}`,
                    },
                    body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
                })
                    .then((res) => {
                        if (!res.ok) throw new Error(`Auth failed: ${res.status}`)
                        return res.json()
                    })
                    .then((data) => callback(false, data))
                    .catch((err) => callback(true, err))
            },
        }),
    })
}

echo.connector?.pusher?.connection?.bind('connected', () => {
    console.log('Socket connected successfully!')
})

echo.connector?.pusher?.connection?.bind('error', (err) => {
    console.warn('Socket connection error:', err)
})

export default echo
