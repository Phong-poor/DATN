export function saveAuth(token, user, remember = false) {
  const encodedUser = btoa(JSON.stringify(user))

  // Luôn lưu vào localStorage để đảm bảo token được lưu trữ ổn định
  localStorage.setItem('token', token)
  localStorage.setItem('user', encodedUser)

  // Lưu thêm vào sessionStorage để đồng bộ
  sessionStorage.setItem('token', token)
  sessionStorage.setItem('user', encodedUser)
}

export function clearAuth() {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  sessionStorage.removeItem('token')
  sessionStorage.removeItem('user')
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
    return JSON.parse(atob(raw))
  } catch (e) {
    console.error('Failed to parse user data', e)
    return null
  }
}

export function updateUser(user) {
  const encodedUser = btoa(JSON.stringify(user))

  if (localStorage.getItem('user')) {
    localStorage.setItem('user', encodedUser)
  } else {
    sessionStorage.setItem('user', encodedUser)
  }
}

export function isLoggedIn() {
  return Boolean(getToken())
}