export function saveAuth(token, user, remember = false) {
  const encodedUser = btoa(JSON.stringify(user))

  if (remember) {
    // lưu lâu
    localStorage.setItem('token', token)
    localStorage.setItem('user', encodedUser)

    sessionStorage.removeItem('token')
    sessionStorage.removeItem('user')
  } else {
    // chỉ lưu phiên hiện tại
    sessionStorage.setItem('token', token)
    sessionStorage.setItem('user', encodedUser)

    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }
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