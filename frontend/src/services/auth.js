export function saveAuth(token, user, remember = false) {
  const encodedUser = btoa(unescape(encodeURIComponent(JSON.stringify(user))))

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

  // Ghi nhận sự kiện đăng nhập để đồng bộ qua các tab khác
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
  
  // Ghi nhận sự kiện đăng xuất để đồng bộ qua các tab khác
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
    if (raw.startsWith('{')) {
      return JSON.parse(raw)
    }
    // Giải mã UTF-8 an toàn
    return JSON.parse(decodeURIComponent(escape(atob(raw))))
  } catch (e) {
    console.error('Failed to parse user data', e)
    return null
  }
}

export function updateUser(user) {
  const encodedUser = btoa(unescape(encodeURIComponent(JSON.stringify(user))))

  if (localStorage.getItem('user')) {
    localStorage.setItem('user', encodedUser)
  } else {
    sessionStorage.setItem('user', encodedUser)
  }
}

export function isLoggedIn() {
  return Boolean(getToken())
}