export function normalizeAuthUser(user) {
  if (!user || typeof user !== 'object') return user

  const role = String(user.vaitro || user.role || '').toLowerCase()

  return {
    ...user,
    id: user.id ?? user.id_user,
    name: user.name || user.ten || user.email || 'User',
    role: role || user.role,
    vaitro: role || user.vaitro,
    avatar: user.avatar || user.anhdaidien || user.anh_dai_dien,
  }
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

export function saveAuth(token, user, remember = false) {
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
