import { createApp } from 'vue'
import './style.css'
import './assets/styles/design-system.css'
import './assets/styles/customer-pages.css'
import './assets/styles/account-pages.css'
import './assets/styles/content-pages.css'
import './assets/styles/admin-pages.css'
import './assets/styles/widgets-layout.css'
import './assets/styles/status-pages.css'
import './assets/styles/text-contrast-fixes.css'
import './assets/styles/swal-theme.css'
import './assets/styles/close-button-hover.css'
import './assets/styles/cancel-button-hover.css'
import App from './App.vue'
import router from './router/index.js'
import { initGoogleAnalytics } from './services/analytics'
import { installPerformanceWarmup } from './services/performanceWarmup'
import { installOnlinePresence } from './services/onlinePresence'
import { installScrollEffects } from './services/scrollEffects'
import { installI18n } from './services/i18n'
import { initUnsavedChangesGuard } from './services/unsavedChanges'

initGoogleAnalytics()

const ADMIN_RECOVERY_KEY = 'admin-runtime-recover-once'
const FALLBACK_IMAGE =
  'data:image/svg+xml;charset=UTF-8,' +
  encodeURIComponent(`
    <svg xmlns="http://www.w3.org/2000/svg" width="640" height="420" viewBox="0 0 640 420">
      <rect width="640" height="420" fill="#f1f5f9"/>
      <rect x="196" y="118" width="248" height="184" rx="22" fill="#e2e8f0"/>
      <path d="M245 258l58-62 43 46 28-30 55 46" fill="none" stroke="#94a3b8" stroke-width="16" stroke-linecap="round" stroke-linejoin="round"/>
      <circle cx="392" cy="166" r="22" fill="#94a3b8"/>
      <text x="320" y="344" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" font-weight="700" fill="#64748b">Không tải được ảnh</text>
    </svg>
  `)

const tryRecoverAdminRuntime = (event) => {
  if (!window.location.pathname.startsWith('/admin')) return
  if (event?.target && event.target !== window) return

  const msg = String(event?.reason?.message || event?.message || event?.reason || '')
  if (!msg.includes('Loading chunk') && !msg.includes('ChunkLoadError') && !msg.includes('Failed to fetch dynamically imported module')) {
    return
  }

  const recovered = sessionStorage.getItem(ADMIN_RECOVERY_KEY)
  if (recovered === '1') return
  sessionStorage.setItem(ADMIN_RECOVERY_KEY, '1')
  window.location.reload()
}

window.addEventListener('error', (event) => {
  const target = event.target
  if (!(target instanceof HTMLImageElement)) return
  if (target.dataset.fallbackApplied === '1') return

  target.dataset.fallbackApplied = '1'
  target.src = FALLBACK_IMAGE
}, true)

window.addEventListener('error', tryRecoverAdminRuntime)
window.addEventListener('unhandledrejection', tryRecoverAdminRuntime)
window.addEventListener('pageshow', () => {
  if (window.location.pathname.startsWith('/admin')) {
    setTimeout(() => {
      sessionStorage.removeItem(ADMIN_RECOVERY_KEY)
    }, 5000)
  }
})
window.addEventListener('pageshow', (event) => {
  if (!window.location.pathname.startsWith('/admin')) return
  const appRoot = document.getElementById('app')
  const isEmpty = !appRoot || appRoot.childElementCount === 0
  if (event.persisted || isEmpty) {
    const key = 'admin-bfcache-recover-once'
    if (sessionStorage.getItem(key) === '1') {
      sessionStorage.removeItem(key)
      return
    }
    sessionStorage.setItem(key, '1')
    window.location.reload()
  } else {
    sessionStorage.removeItem('admin-bfcache-recover-once')
  }
})

createApp(App)
  .use(router)
  .mount('#app')

initUnsavedChangesGuard(router)

installPerformanceWarmup()
installOnlinePresence()
installScrollEffects(router)
installI18n(router)

// Đồng bộ đăng nhập/đăng xuất giữa các tab
window.addEventListener('storage', (event) => {
  if (event.key === 'logout-event') {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    sessionStorage.removeItem('token')
    sessionStorage.removeItem('user')
    
    window.dispatchEvent(new Event('user-updated'))
    
    const isAdmin = window.location.pathname.startsWith('/admin')
    const isProtectedUserPage = ['/profile', '/trang-ca-nhan', '/checkout', '/thanh-toan', '/orderspage', '/don-hang', '/wishlistpage', '/danh-sach-yeu-thich', '/yeu-thich'].includes(window.location.pathname)
    
    if (isAdmin) {
      window.location.href = '/dang-nhap'
    } else if (isProtectedUserPage) {
      window.location.href = '/'
    }
  } else if (event.key === 'login-event' && event.newValue) {
    try {
      const { token, user, remember } = JSON.parse(event.newValue)
      if (remember) {
        localStorage.setItem('token', token)
        localStorage.setItem('user', user)
        sessionStorage.removeItem('token')
        sessionStorage.removeItem('user')
      } else {
        sessionStorage.setItem('token', token)
        sessionStorage.setItem('user', user)
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      }
      
      window.dispatchEvent(new Event('user-updated'))
      
      // Nếu đang ở trang login, tự động chuyển hướng theo vai trò người dùng
      if (window.location.pathname === '/login' || window.location.pathname === '/dang-nhap') {
        try {
          const decoded = JSON.parse(decodeURIComponent(escape(atob(user))))
          const role = String(decoded.vaitro || decoded.role || '').toLowerCase()
          if (role !== 'user') {
            window.location.href = '/admin'
          } else {
            window.location.href = '/'
          }
        } catch (_) {
          window.location.href = '/'
        }
      }
    } catch (e) {
      console.error('Lỗi đồng bộ đăng nhập đa tab:', e)
    }
  }
})
