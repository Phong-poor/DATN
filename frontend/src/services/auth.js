export function normalizeAuthUser(user) {
  if (!user || typeof user !== 'object') return user

  const role = String(user.vaitro || user.role || '').toLowerCase()
  const rawAvatar = user.avatar || user.anhdaidien || user.anh_dai_dien || ''
  // Older profile code assigned this stock portrait to every account without
  // an avatar. Never persist or display that unrelated person's photo.
  const avatar = /randomuser\.me\//i.test(String(rawAvatar)) ? '' : rawAvatar

  return {
    ...user,
    id: user.id ?? user.id_user,
    name: user.name || user.ten || user.email || 'User',
    role: role || user.role,
    vaitro: role || user.vaitro,
    avatar,
  }
}

function hashDeviceSeed(seed, offset) {
  let hash = (0x811c9dc5 ^ offset) >>> 0
  for (let index = 0; index < seed.length; index += 1) {
    hash ^= seed.charCodeAt(index)
    hash = Math.imul(hash, 0x01000193) >>> 0
  }
  return hash.toString(16).padStart(8, '0')
}

// Deliberately excludes the user-agent so Chrome, Cốc Cốc, Edge, etc. on the
// same computer resolve to one machine slot.
export function getDeviceFingerprint() {
  if (typeof window === 'undefined' || typeof navigator === 'undefined') {
    return '0000000000000000'
  }

  const screenInfo = window.screen || {}
  const seed = [
    navigator.platform || 'unknown',
    screenInfo.width || 0,
    screenInfo.height || 0,
    screenInfo.colorDepth || 0,
    Intl.DateTimeFormat().resolvedOptions().timeZone || 'unknown',
    navigator.hardwareConcurrency || 0,
    navigator.deviceMemory || 0,
    navigator.maxTouchPoints || 0,
  ].join('|').toLowerCase()

  return `${hashDeviceSeed(seed, 0)}${hashDeviceSeed(seed, 0x9e3779b9)}`
}

const disposableCachePrefixes = [
  'nextgen_product_detail_cache_',
  'nextgen_news_detail_cache_',
  'nextgen_admin_dashboard_',
  'predator_admin_dashboard_',
  'global_form_draft_',
]

const disposableCacheKeys = new Set([
  'nextgen_products_prefetch_cache',
  'nextgen_admin_products_cache',
  'nextgen_news_cache',
  'premium_home_cache',
  'nextgen_cart_cache',
])

function freeLocalStorageForAuth() {
  Object.keys(localStorage).forEach((key) => {
    if (disposableCacheKeys.has(key) || disposableCachePrefixes.some((prefix) => key.startsWith(prefix))) {
      localStorage.removeItem(key)
    }
  })
}

function writePersistentAuth(token, encodedUser) {
  try {
    localStorage.setItem('token', token)
    localStorage.setItem('user', encodedUser)
  } catch (error) {
    // Cache sản phẩm/tin tức có thể chiếm hết quota. Dọn cache tái tạo được rồi thử lại.
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    freeLocalStorageForAuth()
    localStorage.setItem('token', token)
    localStorage.setItem('user', encodedUser)
  }
}

function broadcastAuthEvent(key, value) {
  try {
    localStorage.setItem(key, value)
    localStorage.removeItem(key)
  } catch (_) {
    // Đồng bộ giữa các tab là phụ; không được làm đăng nhập thất bại khi storage đầy.
  }
}

export function clearLoginFailures() {
  try {
    localStorage.removeItem('login_failed_attempts')
    localStorage.removeItem('login_lock_until')
    localStorage.removeItem('login_lock_count')
  } catch (_) {}
}

export function saveAuth(token, user, remember = false) {
  clearLoginFailures()
  const normalizedUser = normalizeAuthUser(user)
  const encodedUser = btoa(unescape(encodeURIComponent(JSON.stringify(normalizedUser))))

  if (remember) {
    writePersistentAuth(token, encodedUser)
    sessionStorage.removeItem('token')
    sessionStorage.removeItem('user')
  } else {
    sessionStorage.setItem('token', token)
    sessionStorage.setItem('user', encodedUser)
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  const loginData = { token, user: encodedUser, remember }
  broadcastAuthEvent('login-event', JSON.stringify(loginData))

  window.dispatchEvent(new Event('user-updated'))
}

export function clearAuth() {
  clearLoginFailures()
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  sessionStorage.removeItem('token')
  sessionStorage.removeItem('user')

  broadcastAuthEvent('logout-event', Date.now().toString())

  window.dispatchEvent(new Event('user-updated'))
}

export function getToken() {
  return (
    localStorage.getItem('token') ||
    sessionStorage.getItem('token')
  )
}

export function getUser() {
  // Luôn đọc user cùng nơi với token của phiên hiện tại. Tránh lấy nhầm
  // hồ sơ cũ ở localStorage khi tài khoản mới đăng nhập theo session.
  const raw = sessionStorage.getItem('token')
    ? sessionStorage.getItem('user')
    : localStorage.getItem('user')

  if (!raw) return null

  try {
    const parsed = raw.startsWith('{')
      ? JSON.parse(raw)
      : JSON.parse(decodeURIComponent(escape(atob(raw))))

    return normalizeAuthUser(parsed)
  } catch (e) {
    console.error('Failed to parse user data', e)
    return null
  }
}

export function updateUser(user) {
  const normalizedUser = normalizeAuthUser(user)
  const encodedUser = btoa(unescape(encodeURIComponent(JSON.stringify(normalizedUser))))

  if (sessionStorage.getItem('token')) {
    sessionStorage.setItem('user', encodedUser)
    localStorage.removeItem('user')
  } else {
    try {
      localStorage.setItem('user', encodedUser)
    } catch (_) {
      freeLocalStorageForAuth()
      localStorage.setItem('user', encodedUser)
    }
    sessionStorage.removeItem('user')
  }

  window.dispatchEvent(new Event('user-updated'))
}

export function isLoggedIn() {
  return Boolean(getToken())
}
