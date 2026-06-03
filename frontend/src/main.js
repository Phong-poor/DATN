import { createApp } from 'vue'
import './style.css'
import './assets/styles/swal-theme.css'
import App from './App.vue'
import router from './router/index.js'
import { initGoogleAnalytics } from './services/analytics'
import { installPerformanceWarmup } from './services/performanceWarmup'

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
    sessionStorage.removeItem(ADMIN_RECOVERY_KEY)
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

installPerformanceWarmup()
