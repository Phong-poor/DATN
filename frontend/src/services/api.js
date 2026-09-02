import axios from 'axios'
import { clearAuth, getDeviceFingerprint, getToken, updateUser } from './auth'
import { apiBaseUrl } from './urls'

const api = axios.create({
  baseURL: apiBaseUrl,
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Offline support is non-critical for first paint. Install it during idle time
// so it cannot compete with the initial route, CSS, fonts, or hero images.
const installOfflineSupport = () => {
  import('./offlineSync')
    .then(({ initOfflineInterceptor, registerSyncSuccessCallback }) => {
      initOfflineInterceptor(api)
      registerSyncSuccessCallback(clearApiGetCache)
    })
    .catch(() => {
      // Online requests keep working when the optional offline module cannot load.
    })
}

if (typeof window !== 'undefined' && 'requestIdleCallback' in window) {
  window.requestIdleCallback(installOfflineSupport, { timeout: 1800 })
} else if (typeof window !== 'undefined') {
  window.setTimeout(installOfflineSupport, 600)
}

const GET_CACHE_TTL_MS = 5 * 60 * 1000
const getCache = new Map()
const inFlightGetRequests = new Map()
const SESSION_CHECK_INTERVAL_MS = 10000
let sessionCheckTimer = null
let authRedirectInProgress = false
const NO_CACHE_GET_PREFIXES = [
  '/gio-hang',
  '/yeu-thich',
  '/orders',
  '/user/vouchers',
  '/affiliate',
  '/admin',
]

export const clearApiGetCache = () => {
  getCache.clear()
  inFlightGetRequests.clear()
}

const shouldShowGlobalLoader = (config = {}) => config.showGlobalLoader === true

const redirectAfterAuthFailure = (target) => {
  if (typeof window === 'undefined' || authRedirectInProgress) return

  authRedirectInProgress = true
  if (sessionCheckTimer) {
    window.clearInterval(sessionCheckTimer)
    sessionCheckTimer = null
  }

  // Một màn hình có thể gọi nhiều API đồng thời. Chỉ cho phép phản hồi lỗi đầu
  // tiên điều hướng để tránh nhiều lệnh location làm trang tải lại liên tục.
  window.location.replace(target)
}

const shouldCacheGet = (config = {}) => {
  if (config.method?.toLowerCase?.() !== 'get' || config.cache === false) return false
  const url = String(config.url || '')
  return !NO_CACHE_GET_PREFIXES.some((prefix) => url === prefix || url.startsWith(`${prefix}/`))
}

const stableStringify = (value) => {
  if (!value || typeof value !== 'object') return value ? String(value) : ''
  if (Array.isArray(value)) return `[${value.map(stableStringify).join(',')}]`
  return `{${Object.keys(value).sort().map((key) => `${key}:${stableStringify(value[key])}`).join(',')}}`
}

const getCacheKey = (url, config = {}) => {
  const params = config.params ? stableStringify(config.params) : ''
  return `${url || ''}?${params}`
}

api.interceptors.request.use((config) => {
  config.headers['X-Device-Fingerprint'] = getDeviceFingerprint()
  const method = config.method?.toLowerCase?.()
  if (method && method !== 'get' && config.invalidateCache !== false) {
    clearApiGetCache()
  }

  if (shouldShowGlobalLoader(config)) {
    window.dispatchEvent(
      config.immediateLoader
        ? new CustomEvent('global-loader-show', { detail: { immediate: true, minDuration: 180 } })
        : new Event('global-loader-show')
    )
  }

  const token = getToken()
  const hasAuthorizationHeader = Boolean(
    config.headers?.Authorization ||
    config.headers?.authorization ||
    (typeof config.headers?.get === 'function' && config.headers.get('Authorization'))
  )

  if (token && !hasAuthorizationHeader) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}, (error) => {
  if (shouldShowGlobalLoader(error.config)) {
    window.dispatchEvent(new Event('global-loader-hide'))
  }
  return Promise.reject(error)
})

api.interceptors.response.use(
  (response) => {
    if (shouldShowGlobalLoader(response.config)) {
      window.dispatchEvent(new Event('global-loader-hide'))
    }
    
    // Xóa bản nháp toàn cục khi lưu/thay đổi dữ liệu thành công
    const method = response.config?.method?.toLowerCase?.()
    if (method && method !== 'get') {
      localStorage.removeItem(`global_form_draft_${window.location.pathname}`)
    }
    
    return response
  },
  (error) => {
    if (shouldShowGlobalLoader(error.config)) {
      window.dispatchEvent(new Event('global-loader-hide'))
    }
    if (error.isOfflineQueue || error.message === 'OFFLINE_QUEUED') {
      window.__lastRequestWasOfflineQueued = true
      return Promise.resolve({
        status: 202,
        data: {
          success: true,
          message: error.response?.data?.message || 'Bạn đang ngoại tuyến. Dữ liệu đã được lưu tạm và sẽ tự động đồng bộ khi có mạng.'
        }
      })
    }
    if (error.response?.status === 423 || error.response?.data?.code === 'ACCOUNT_LOCKED') {
      const message = error.response?.data?.message || 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để được hỗ trợ.'
      localStorage.setItem('account_locked_message', message)
      clearAuth()
      if (window.location.pathname !== '/dang-nhap') {
        redirectAfterAuthFailure('/dang-nhap?account_locked=1')
      }
    } else if (error.response?.status === 403 && error.response?.data?.code === 'ADMIN_ACCESS_REVOKED') {
      clearAuth()
      if (window.location.pathname.startsWith('/admin')) {
        redirectAfterAuthFailure('/dang-nhap?admin_revoked=1')
      }
    } else if (error.response?.status === 401) {
      clearAuth()
      const path = window.location.pathname
      const isAdmin = path.startsWith('/admin')
      const isAuthPage = ['/dang-nhap', '/login', '/dang-ky', '/register', '/quen-mat-khau', '/forgot-password', '/xac-thuc-otp', '/otp-verify', '/dat-lai-mat-khau', '/reset-password'].some(p => path.startsWith(p))
      const isPublicPage = ['/', '/laptop', '/phu-kien', '/gaming', '/news', '/tin-tuc', '/contact', '/lien-he', '/cart', '/gio-hang', '/thank-you', '/cam-on', '/payment-failed', '/thanh-toan-that-bai', '/khuyen-mai'].includes(path) ||
                           path.startsWith('/products/') ||
                           path.startsWith('/san-pham/') ||
                           path.startsWith('/news/') ||
                           path.startsWith('/tin-tuc/')
                           
      if (isAdmin) {
        if (path !== '/dang-nhap') {
          redirectAfterAuthFailure('/dang-nhap')
        }
      } else if (!isPublicPage && !isAuthPage) {
        redirectAfterAuthFailure('/')
      }
    }
    return Promise.reject(error)
  }
)

const rawGet = api.get.bind(api)
api.get = (url, config = {}) => {
  const requestConfig = { ...config, method: 'get' }
  if (!shouldCacheGet(requestConfig)) return rawGet(url, config)

  const key = getCacheKey(url, requestConfig)
  const cached = getCache.get(key)
  if (cached && Date.now() - cached.cachedAt < GET_CACHE_TTL_MS) {
    return Promise.resolve(cached.response)
  }

  const inFlight = inFlightGetRequests.get(key)
  if (inFlight) return inFlight

  const request = rawGet(url, config)
    .then((response) => {
      getCache.set(key, { cachedAt: Date.now(), response })
      return response
    })
    .finally(() => {
      inFlightGetRequests.delete(key)
    })

  inFlightGetRequests.set(key, request)
  return request
}

const authPages = ['/dang-nhap', '/login', '/login-success', '/dang-nhap-thanh-cong']
const protectedPages = [
  '/admin',
  '/profile',
  '/trang-ca-nhan',
  '/checkout',
  '/thanh-toan',
  '/orderspage',
  '/don-hang',
  '/wishlistpage',
  '/danh-sach-yeu-thich',
  '/yeu-thich',
]

const isAuthPage = () => {
  if (typeof window === 'undefined') return true
  return authPages.some((path) => window.location.pathname.startsWith(path))
}

const isProtectedPage = () => {
  if (typeof window === 'undefined') return false
  const currentPath = window.location.pathname
  return protectedPages.some((path) => currentPath === path || currentPath.startsWith(path + '/'))
}

export const startSessionGuard = () => {
  if (typeof window === 'undefined' || sessionCheckTimer) return

  sessionCheckTimer = window.setInterval(() => {
    if (!getToken() || isAuthPage()) return

    rawGet('/auth/session', {
      cache: false,
      invalidateCache: false,
      showGlobalLoader: false,
    }).then((response) => {
      const user = response.data?.user
      if (!user) return

      updateUser(user)

      const role = String(user.vaitro || user.role || '').toLowerCase()
      if (window.location.pathname.startsWith('/admin') && (!role || role === 'user')) {
        clearAuth()
        redirectAfterAuthFailure('/dang-nhap?admin_revoked=1')
      }
    }).catch(() => {
      // 401/403/423 are handled by the response interceptor, so no extra UI is needed here.
    })
  }, SESSION_CHECK_INTERVAL_MS)
}

export const stopSessionGuard = () => {
  if (typeof window === 'undefined' || !sessionCheckTimer) return
  window.clearInterval(sessionCheckTimer)
  sessionCheckTimer = null
}

startSessionGuard()

export default api
