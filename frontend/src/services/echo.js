import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

import { getToken } from './auth'
import { backendBaseUrl } from './urls'

const noopChannel = {
    listen: () => noopChannel,
    stopListening: () => noopChannel,
    notification: () => noopChannel,
    subscribed: () => noopChannel,
    error: () => noopChannel,
    here: () => noopChannel,
    joining: () => noopChannel,
    leaving: () => noopChannel,
    listenForWhisper: () => noopChannel,
    whisper: () => noopChannel,
}

const disabledEcho = {
    channel: () => noopChannel,
    private: () => noopChannel,
    encryptedPrivate: () => noopChannel,
    join: () => noopChannel,
    leave: () => {},
    leaveChannel: () => {},
    disconnect: () => {},
    connector: null,
    disabled: true,
}

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY
const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname
const reverbPort = import.meta.env.VITE_REVERB_PORT || 8080
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'http'

let echo = disabledEcho

if (reverbKey) {
    window.Pusher = Pusher

    echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
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
                    body: JSON.stringify({
                        socket_id: socketId,
                        channel_name: channel.name,
                    }),
                })
                    .then((response) => {
                        if (!response.ok) throw new Error(`Auth failed: ${response.status}`)
                        return response.json()
                    })
                    .then((data) => callback(false, data))
                    .catch((error) => {
                        console.warn('Không thể xác thực Socket:', error.message)
                        callback(true, error)
                    })
            },
        }),
    })

    echo.connector?.pusher?.connection?.bind('connected', () => {
        console.log('Socket connected to Reverb!')
    })

    echo.connector?.pusher?.connection?.bind('error', (err) => {
        console.warn('Socket connection error:', err)
    })
} else {
    console.warn('Reverb socket disabled: missing VITE_REVERB_APP_KEY. App will continue without realtime updates.')
}

export default echo
