import api from '@/services/api'
import { getToken } from '@/services/auth'

const HEARTBEAT_INTERVAL_MS = 25000
const MIN_HEARTBEAT_GAP_MS = 8000

let heartbeatTimer = null
let lastHeartbeatAt = 0
let installed = false

const hasToken = () => Boolean(getToken())

const sendHeartbeat = async ({ force = false, keepalive = false } = {}) => {
  if (!hasToken()) return

  const now = Date.now()
  if (!force && now - lastHeartbeatAt < MIN_HEARTBEAT_GAP_MS) return
  lastHeartbeatAt = now

  if (keepalive && typeof fetch === 'function') {
    try {
      await fetch(`${api.defaults.baseURL}/user/heartbeat`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          Authorization: `Bearer ${getToken()}`,
        },
        body: '{}',
        keepalive: true,
      })
      return
    } catch {
      // Fall back to axios below when the browser rejects keepalive.
    }
  }

  try {
    await api.post('/user/heartbeat', {}, {
      timeout: 6000,
      showGlobalLoader: false,
      invalidateCache: false,
    })
  } catch {
    // Heartbeat is best-effort; auth interceptors handle locked/expired accounts.
  }
}

const startHeartbeat = () => {
  if (heartbeatTimer) return
  sendHeartbeat({ force: true })
  heartbeatTimer = window.setInterval(() => {
    if (document.visibilityState === 'visible') {
      sendHeartbeat()
    }
  }, HEARTBEAT_INTERVAL_MS)
}

const stopHeartbeat = () => {
  if (!heartbeatTimer) return
  window.clearInterval(heartbeatTimer)
  heartbeatTimer = null
}

const syncHeartbeatState = () => {
  if (hasToken()) {
    startHeartbeat()
  } else {
    stopHeartbeat()
  }
}

export const installOnlinePresence = () => {
  if (typeof window === 'undefined' || installed) return
  installed = true

  syncHeartbeatState()

  window.addEventListener('user-updated', syncHeartbeatState)
  window.addEventListener('focus', () => sendHeartbeat({ force: true }))
  window.addEventListener('pageshow', () => {
    syncHeartbeatState()
    sendHeartbeat({ force: true })
  })
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      syncHeartbeatState()
      sendHeartbeat({ force: true })
    } else {
      sendHeartbeat({ keepalive: true })
    }
  })
  window.addEventListener('pagehide', () => sendHeartbeat({ keepalive: true }))
}
