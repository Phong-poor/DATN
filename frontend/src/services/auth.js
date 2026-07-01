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

export function saveAuth(token, user, remember = false) {
  const normalizedUser = normalizeAuthUser(user)
  const encodedUser = btoa(unescape(encodeURIComponent(JSON.stringify(normalizedUser))))

  if (remember) {
    localStorage.setItem('token', token)
    localStorage.setItem('user', encodedUser)
    sessionStorage.removeItem('token')
    sessionStorage.removeItem('user')
  } else {
    sessionStorage.setItem('token', token)
    sessionStorage.setItem('user', encodedUser)
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  const loginData = { token, user: encodedUser, remember }
  localStorage.setItem('login-event', JSON.stringify(loginData))
  localStorage.removeItem('login-event')

  window.dispatchEvent(new Event('user-updated'))
}

export function clearAuth() {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  sessionStorage.removeItem('token')
  sessionStorage.removeItem('user')

  localStorage.setItem('logout-event', Date.now().toString())
  localStorage.removeItem('logout-event')

  window.dispatchEvent(new Event('user-updated'))
}

export function getToken() {
  return (
    localStorage.getItem('token') ||
    sessionStorage.getItem('token')
  )
}

export function getUser() {
  const raw =
    localStorage.getItem('user') ||
    sessionStorage.getItem('user')

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

  if (localStorage.getItem('user')) {
    localStorage.setItem('user', encodedUser)
  } else {
    sessionStorage.setItem('user', encodedUser)
  }

  window.dispatchEvent(new Event('user-updated'))
}

export function isLoggedIn() {
  return Boolean(getToken())
}
