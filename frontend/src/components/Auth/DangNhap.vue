<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import api from '@/services/api'
import { getUser, saveAuth } from '@/services/auth'
import { formatAuthMessage } from '@/services/authMessages'
import { validateEmail, normalizeEmail } from '@/services/authValidation'

const email = ref('')
const password = ref('')
const remember = ref(false)
const showPassword = ref(false)
const loading = ref(false)
const adminOpening = ref(false)
const webOpening = ref(false)
const socialOpening = ref(false)

const failedAttempts = ref(Number(localStorage.getItem('login_failed_attempts') || 0))
const lockUntil = ref(Number(localStorage.getItem('login_lock_until') || 0))
const lockCount = ref(Number(localStorage.getItem('login_lock_count') || 0))
const secondsRemaining = ref(0)
let lockInterval = null

const updateLockCountdown = () => {
  const now = Date.now()
  if (lockUntil.value && now < lockUntil.value) {
    secondsRemaining.value = Math.ceil((lockUntil.value - now) / 1000)
  } else {
    secondsRemaining.value = 0
    if (lockInterval) {
      clearInterval(lockInterval)
      lockInterval = null
    }
  }
}

const startLockCountdown = () => {
  if (lockInterval) clearInterval(lockInterval)
  updateLockCountdown()
  if (secondsRemaining.value > 0) {
    lockInterval = setInterval(() => {
      updateLockCountdown()
    }, 1000)
  }
}

onUnmounted(() => {
  if (lockInterval) clearInterval(lockInterval)
})

const passwordError = computed(() => {
  if (secondsRemaining.value > 0) {
    return `Bạn đã nhập sai mật khẩu quá 5 lần. Vui lòng thử lại sau ${secondsRemaining.value} giây.`
  }
  if (failedAttempts.value > 0 && failedAttempts.value < 5) {
    const remaining = 5 - failedAttempts.value
    return `Bạn đã nhập sai mật khẩu. Còn lại ${remaining} lần thử.`
  }
  return ''
})

const modal = ref({
  show: false,
  type: 'error',
  title: '',
  message: '',
  onConfirm: null
})

let autoCloseTimer = null

const showModal = (type, title, message, onConfirm = null) => {
  modal.value = {
    show: true,
    type,
    title,
    message,
    onConfirm
  }

  if (type === 'success') {
    if (autoCloseTimer) clearTimeout(autoCloseTimer)

    autoCloseTimer = setTimeout(() => {
      closeModal()
    }, 2000)
  }
}

const loginGoogle = () => {
  if (loading.value || adminOpening.value || webOpening.value || socialOpening.value) return
  socialOpening.value = true
  if (route.query.redirect) {
    sessionStorage.setItem('redirect_after_auth', route.query.redirect)
  }
  const refCode = localStorage.getItem('affiliate_ref') || ''
  const params = new URLSearchParams({ frontend_url: window.location.origin })
  if (refCode) params.set('ref', refCode)
  const endpoint = `/auth/google?${params.toString()}`
  setTimeout(() => {
    window.location.href = `${api.defaults.baseURL}${endpoint}`
  }, 620)
}

const closeModal = () => {
  if (autoCloseTimer) {
    clearTimeout(autoCloseTimer)
    autoCloseTimer = null
  }

  const cb = modal.value.onConfirm

  modal.value.show = false

  if (cb) cb()
}

const router = useRouter()
const route = useRoute()

const safeRedirectPath = (path) => {
  if (!path || typeof path !== 'string') return ''
  if (!path.startsWith('/') || path.startsWith('/dang-nhap')) return ''
  return path
}

const isAdminUser = (user) => {
  const role = String(user?.vaitro || user?.role || '').toLowerCase()
  return role !== '' && role !== 'user'
}

const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms))

let adminPreloadPromise = null
let adminDashboardPreloadPromise = null

const DASHBOARD_CACHE_PREFIX = 'nextgen_admin_dashboard_'

const writeDashboardCache = (selectedPeriod, payload) => {
  try {
    localStorage.setItem(`${DASHBOARD_CACHE_PREFIX}${selectedPeriod}`, JSON.stringify({
      cachedAt: Date.now(),
      data: payload,
    }))
  } catch (_) {
    // Cache is only used to make the admin dashboard appear instantly.
  }
}

const preloadAdminRoute = () => {
  if (!adminPreloadPromise) {
    adminPreloadPromise = Promise.allSettled([
      import('../Admin/Layout/BoCucAdmin.vue'),
      import('../Admin/BangDieuKhien.vue')
    ])
  }

  return adminPreloadPromise
}

const preloadAdminDashboardData = () => {
  if (!adminDashboardPreloadPromise) {
    adminDashboardPreloadPromise = api.get('/admin/dashboard', {
      params: { period: 'all' },
      cache: false
    })
      .then((res) => {
        if (res.data?.data) {
          writeDashboardCache('all', res.data.data)
        }

        return res
      })
      .catch(() => null)
  }

  return adminDashboardPreloadPromise
}

const playAdminOpening = async () => {
  adminOpening.value = true

  const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  await wait(prefersReducedMotion ? 120 : 1160)
}

const playWebOpening = async () => {
  webOpening.value = true

  const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  await wait(prefersReducedMotion ? 120 : 1040)
}

const redirectAfterLogin = async (user, token) => {
  const redirectPath = safeRedirectPath(
    route.query.redirect || sessionStorage.getItem('redirect_after_auth')
  )
  sessionStorage.removeItem('redirect_after_auth')

  if (isAdminUser(user)) {
    const adminReady = preloadAdminRoute()
    const dashboardReady = preloadAdminDashboardData()
    await Promise.all([
      playAdminOpening(),
      adminReady,
      Promise.race([dashboardReady, wait(1250)])
    ])
    sessionStorage.setItem('skip_next_route_loader', '1')
    sessionStorage.setItem('admin_intro_animation', '1')
    await router.replace('/admin')
    return
  }

  if (redirectPath) {
    await playWebOpening()
    sessionStorage.setItem('web_intro_animation', '1')
    await router.replace(redirectPath)
    return
  }

  const pendingItemStr = localStorage.getItem('pendingCartItem')
  if (pendingItemStr) {
    try {
      const pendingItem = JSON.parse(pendingItemStr)
      await api.post('/gio-hang/them', pendingItem, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })

      localStorage.removeItem('pendingCartItem')
      window.dispatchEvent(new Event('cart-updated'))
      await playWebOpening()
      sessionStorage.setItem('web_intro_animation', '1')
      await router.replace('/gio-hang')
      return
    } catch (err) {
      console.error('Lỗi thêm pending item:', err)
    }
  }

  await playWebOpening()
  sessionStorage.setItem('web_intro_animation', '1')
  await router.replace('/')
}


onMounted(() => {
  startLockCountdown()
  const preloadWhenIdle = window.requestIdleCallback || ((cb) => setTimeout(cb, 250))
  preloadWhenIdle(() => preloadAdminRoute())

  if (route.query.account_locked || localStorage.getItem('account_locked_message')) {
    const message = localStorage.getItem('account_locked_message') || 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để được hỗ trợ.'
    localStorage.removeItem('account_locked_message')
    showModal('error', 'Tài khoản bị khóa', message)
  }

  if (route.query.social_error) {
    const messageByCode = {
      google_callback_failed: 'Không thể xác thực Google. Vui lòng thử lại.',
      google_create_failed: 'Đăng nhập Google thất bại do lỗi tạo tài khoản.',
      google_user_not_found: 'Không tạo được tài khoản từ Google.',
      missing_token: 'Máy chủ không trả về token đăng nhập. Vui lòng thử lại.',
      profile_fetch_failed: 'Đăng nhập thành công nhưng không thể lấy thông tin tài khoản. Vui lòng thử lại.',
    }
    const errorCode = String(route.query.social_error)
    const provider = errorCode.startsWith('google') ? 'Google' : 'mạng xã hội'
    showModal('error', `Lỗi đăng nhập ${provider}`, messageByCode[errorCode] || 'Đăng nhập mạng xã hội thất bại.')
  }

  const user = getUser()
  const token =
    localStorage.getItem('token') ||
    sessionStorage.getItem('token')

  if (token) {
    axios.defaults.headers.common[
      'Authorization'
    ] = `Bearer ${token}`
  }

  if (user && token) {
    if (isAdminUser(user)) {
      router.replace('/admin')
    } else {
      router.replace('/')
    }

    return
  }

  const savedEmail = localStorage.getItem('remember_email')

  if (savedEmail) {
    email.value = savedEmail
    remember.value = true
  }
})

const handleLogin = async () => {
  if (secondsRemaining.value > 0) {
    return
  }

  const emailError = validateEmail(email.value)
  if (emailError) {
    showModal('error', 'Email không hợp lệ', emailError)
    return
  }

  if (!password.value) {
    showModal('error', 'Thiếu thông tin', 'Vui lòng nhập mật khẩu.')
    return
  }

  if (loading.value || adminOpening.value || webOpening.value || socialOpening.value) return

  loading.value = true
  preloadAdminRoute()

  try {
    const res = await api.post('/login', {
      email: normalizeEmail(email.value),
      matkhau: password.value,
      password: password.value,
      remember: remember.value
    })

    const user = res.data.user
    const token = res.data.token

    if (!token) {
      showModal('error', 'Lỗi', 'Máy chủ không trả về token đăng nhập.')
      return
    }

    // Reset login failures on success
    localStorage.removeItem('login_failed_attempts')
    localStorage.removeItem('login_lock_until')
    localStorage.removeItem('login_lock_count')
    failedAttempts.value = 0
    lockUntil.value = 0
    lockCount.value = 0
    secondsRemaining.value = 0
    if (lockInterval) {
      clearInterval(lockInterval)
      lockInterval = null
    }

    saveAuth(token, user, remember.value)

    axios.defaults.headers.common[
      'Authorization'
    ] = `Bearer ${token}`

    if (remember.value) {
      localStorage.setItem(
        'remember_email',
        email.value
      )
    } else {
      localStorage.removeItem('remember_email')
    }

    await redirectAfterLogin(user, token)
    return
  } catch (err) {
    console.log(err)

    if (err.response?.status === 423 || err.response?.data?.code === 'ACCOUNT_LOCKED') {
      const message = err.response?.data?.message || 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để được hỗ trợ.'
      localStorage.removeItem('account_locked_message')
      localStorage.removeItem('login_failed_attempts')
      localStorage.removeItem('login_lock_until')
      localStorage.removeItem('login_lock_count')
      failedAttempts.value = 0
      lockUntil.value = 0
      lockCount.value = 0
      secondsRemaining.value = 0
      showModal('error', 'Tài khoản bị khóa', message)
      return
    }

    // Increment failed attempts
    failedAttempts.value += 1
    localStorage.setItem('login_failed_attempts', failedAttempts.value)

    if (failedAttempts.value >= 5) {
      // Calculate lock duration: 30s * 2^lockCount
      const duration = 30 * Math.pow(2, lockCount.value)
      lockUntil.value = Date.now() + duration * 1000
      localStorage.setItem('login_lock_until', lockUntil.value)
      
      // Increment lock count for next exponential lock
      lockCount.value += 1
      localStorage.setItem('login_lock_count', lockCount.value)

      startLockCountdown()
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page">

    <div class="login-box" :class="{ 'admin-opening': adminOpening, 'web-opening': webOpening, 'social-opening': socialOpening }">

      <!-- LEFT (Dark Column) -->
      <div class="left-col">
        <div class="left-content">
          <div class="brand-header">
            <span class="brand-title">NextGen</span>
            <span class="brand-slogan">Chinh Phục Tầm Cao Mới.</span>
          </div>
          <p class="brand-description">
            Trở lại tài khoản để quản lý đơn hàng, lưu cấu hình laptop và nhận ưu đãi dành riêng cho bạn.
          </p>
          <div class="highlight-pills">
            <span class="pill">Đăng nhập nhanh</span>
            <span class="pill">Đồng bộ giỏ hàng</span>
            <span class="pill">Ưu đãi thành viên</span>
          </div>
        </div>
      </div>

      <!-- RIGHT (White Column) -->
      <div class="right-col">
        <div class="tab-header">
          <span class="tab-btn active" @click="!adminOpening && !webOpening && !socialOpening && router.push('/dang-nhap')">Đăng nhập</span>
          <span class="tab-btn" @click="!adminOpening && !webOpening && !socialOpening && router.push('/dang-ky')">Đăng ký</span>
        </div>

        <div class="welcome-box">
          <h2 class="welcome-title">Chào mừng trở lại</h2>
          <p class="welcome-desc">Vui lòng nhập thông tin tài khoản của bạn để tiếp tục.</p>
        </div>

        <!-- FORM FIELDS -->
        <form class="form-container" @submit.prevent="handleLogin">
          <!-- EMAIL -->
          <div class="form-group">
            <label class="input-label">Địa chỉ Email</label>
            <div class="input-wrapper">
               <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="3"/>
                  <path d="M2 7l10 7 10-7"/>
                </svg>
              </span>
              <input v-model="email" type="email" name="username" autocomplete="username" placeholder="Example@vinatech.vn" />
            </div>
          </div>

          <!-- PASSWORD -->
          <div class="form-group">
            <label class="input-label">Mật khẩu</label>
            <div class="input-wrapper" :class="{ 'error': passwordError }">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </span>
              <input :type="showPassword ? 'text' : 'password'" v-model="password" name="password" autocomplete="current-password" placeholder="••••••••" />
              <button class="eye-toggle-btn" @click="showPassword = !showPassword" type="button">
                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                  <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
              </button>
            </div>
            <p v-if="passwordError" class="field-hint error">{{ passwordError }}</p>
          </div>

          <!-- REMEMBER & FORGOT -->
          <div class="options-row">
            <label class="remember-checkbox-label">
              <input type="checkbox" v-model="remember" />
              <span>Ghi nhớ đăng nhập</span>
            </label>
            <a class="forgot-link" @click="!adminOpening && !webOpening && !socialOpening && router.push('/quen-mat-khau')">Quên mật khẩu?</a>
          </div>

          <!-- SUBMIT BUTTON -->
          <button type="submit" class="submit-btn" :disabled="loading || adminOpening || webOpening || socialOpening || secondsRemaining > 0">
            <span class="btn-text">
              {{ secondsRemaining > 0 ? `Thử lại sau ${secondsRemaining}s` : (adminOpening ? 'Đang mở trang quản trị...' : (webOpening ? 'Đang mở trang chủ...' : (socialOpening ? 'Đang kết nối...' : (loading ? 'Đang đăng nhập...' : 'Đăng nhập ngay')))) }}
            </span>
            <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>
        </form>

        <!-- OR DIVIDER -->
        <div class="or-divider">
          <span class="divider-line"></span>
          <span class="divider-text">Hoặc</span>
          <span class="divider-line"></span>
        </div>

        <!-- SOCIAL BUTTONS -->
        <div class="social-row">
          <button @click="loginGoogle" class="social-btn-google" :disabled="adminOpening || webOpening || socialOpening">
            <svg class="google-logo" viewBox="0 0 18 18" aria-hidden="true">
              <path fill="#4285F4" d="M17.64 9.205c0-.638-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.717v2.259h2.908c1.702-1.567 2.684-3.874 2.684-6.616z"/>
              <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.179l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.583-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z"/>
              <path fill="#FBBC05" d="M3.964 10.711A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.711V4.957H.957A8.997 8.997 0 0 0 0 9c0 1.452.348 2.827.957 4.043l3.007-2.332z"/>
              <path fill="#EA4335" d="M9 3.578c1.322 0 2.508.454 3.44 1.346l2.581-2.581C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.957l3.007 2.332C4.672 5.161 6.656 3.578 9 3.578z"/>
            </svg>
            Đăng nhập bằng Google
          </button>
        </div>

        <!-- FOOTER REGISTER LINK -->
        <p class="footer-register">
          Bạn chưa có tài khoản? <span class="register-link" @click="!adminOpening && !webOpening && !socialOpening && router.push('/dang-ky')">Tạo tài khoản mới</span>
        </p>

      </div>
    </div>

    <!-- MODAL -->
    <Transition name="modal">
      <div v-if="modal.show" class="modal-overlay" @click.self="closeModal">
        <div class="modal-card" :class="modal.type">
          <div class="modal-icon">
            <svg v-if="modal.type === 'error'" xmlns="http://www.w3.org/2000/svg" width="32" height="32"
              viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
              <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
          </div>
          <h3 class="modal-title">{{ modal.title }}</h3>
          <p class="modal-message">{{ modal.message }}</p>
          <button v-if="modal.type !== 'success'" class="modal-btn" :class="modal.type" @click="closeModal">
            {{ modal.type === 'success' ? 'Tiếp tục' : 'Đã hiểu' }}
          </button>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f4f6f9;
  font-family: 'Inter', sans-serif;
  overflow: hidden;
  position: relative;
}

.page::before {
  content: "";
  position: fixed;
  inset: 0;
  z-index: 1;
  background:
    radial-gradient(circle at 50% 42%, rgba(37, 99, 235, 0.12), transparent 34%),
    linear-gradient(135deg, #f8fbff, #eef4ff);
  opacity: 0;
  transform: scale(1.02);
  transition: opacity 0.85s ease, transform 1s ease;
  pointer-events: none;
}

.page:has(.login-box.admin-opening)::before,
.page:has(.login-box.web-opening)::before,
.page:has(.login-box.social-opening)::before {
  opacity: 1;
  transform: scale(1);
}

.login-box {
  width: 960px;
  height: 580px;
  display: grid;
  grid-template-columns: 42% 58%;
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
  position: relative;
  z-index: 5;
  transform-origin: center;
  transition: box-shadow 0.35s ease, filter 0.35s ease;
  will-change: transform, opacity;
  backface-visibility: hidden;
}

.login-box.admin-opening,
.login-box.web-opening,
.login-box.social-opening {
  box-shadow: 0 30px 90px rgba(37, 99, 235, 0.22);
  pointer-events: none;
  animation: loginShellFade 1.08s cubic-bezier(0.72, 0, 0.16, 1) forwards;
}

.left-col {
  background: #0d1b2e;
  color: #ffffff;
  padding: 40px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
  isolation: isolate;
  will-change: transform, opacity;
  backface-visibility: hidden;
  transition:
    transform 1.02s cubic-bezier(0.72, 0, 0.16, 1),
    opacity 0.82s ease;
}

.left-col::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 0;
  background: url('/anhlogin.jpg') center / cover no-repeat;
  opacity: 0.78;
  transform: scale(1.02);
}

.left-col::after {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  background:
    linear-gradient(180deg, rgba(13, 27, 46, 0.58), rgba(13, 27, 46, 0.36)),
    linear-gradient(90deg, rgba(15, 23, 42, 0.66), rgba(15, 23, 42, 0.12));
  pointer-events: none;
}

.left-content {
  position: relative;
  z-index: 2;
  max-width: 360px;
}


.brand-header {
  display: flex;
  flex-direction: column;
  gap: 6px;
  position: relative;
  z-index: 2;
}

.brand-title {
  font-size: 14px;
  font-weight: 600;
  color: #60a5fa;
  text-transform: capitalize;
  letter-spacing: 0.08em;
}

.brand-slogan {
  font-size: 24px;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.2;
}

.brand-description {
  font-size: 13px;
  color: #94a3b8;
  line-height: 1.5;
  margin-top: 12px;
  margin-bottom: 20px;
  position: relative;
  z-index: 2;
}

.highlight-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  position: relative;
  z-index: 2;
}

.pill {
  background: rgba(59, 130, 246, 0.12);
  color: #93c5fd;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 500;
  border: 1px solid rgba(59, 130, 246, 0.25);
  backdrop-filter: blur(4px);
}

.right-col {
  padding: 36px 44px;
  background: #ffffff;
  display: flex;
  flex-direction: column;
  justify-content: center;
  will-change: transform, opacity;
  backface-visibility: hidden;
  transition:
    transform 1.02s cubic-bezier(0.72, 0, 0.16, 1),
    opacity 0.82s ease;
}

.login-box.admin-opening .left-col,
.login-box.web-opening .left-col,
.login-box.social-opening .left-col {
  transform: translate3d(-94%, 0, 0) scale(0.985);
  opacity: 0;
}

.login-box.admin-opening .right-col,
.login-box.web-opening .right-col,
.login-box.social-opening .right-col {
  transform: translate3d(94%, 0, 0) scale(0.985);
  opacity: 0;
}

.tab-header {
  display: flex;
  gap: 24px;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 16px;
}

.tab-btn {
  font-size: 14.5px;
  font-weight: 600;
  color: #9ca3af;
  padding-bottom: 8px;
  cursor: pointer;
  position: relative;
  transition: color 0.2s;
}

.tab-btn:hover {
  color: #1e40af;
}

.tab-btn.active {
  color: #1e40af;
}

.tab-btn.active::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  width: 100%;
  height: 2px;
  background: linear-gradient(90deg, #2563eb, #1d4ed8);
  border-radius: 2px;
}

.welcome-box {
  margin-bottom: 16px;
}

.welcome-title {
  font-size: 20px;
  font-weight: 700;
  color: #0d1b2e;
  margin: 0;
}

.welcome-desc {
  font-size: 13px;
  color: #6b7280;
  margin: 3px 0 0 0;
}

.form-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.input-label {
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
}

.input-wrapper {
  display: flex;
  align-items: center;
  background-color: #f8faff;
  border: 1px solid #dbeafe;
  border-radius: 6px;
  padding: 0 12px;
  height: 40px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.input-wrapper:focus-within {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
}

.input-wrapper.error {
  border-color: #ef4444;
  box-shadow: 0 0 0 1px #ef4444;
}

.field-hint {
  font-size: 10.5px;
  margin: 1px 0 0 2px;
  font-weight: 600;
}

.field-hint.error {
  color: #ef4444;
}

.input-icon {
  display: flex;
  align-items: center;
  color: #93c5fd;
  margin-right: 8px;
}

.input-icon svg {
  width: 16px;
  height: 16px;
}

.input-wrapper input {
  border: none;
  background: transparent;
  outline: none;
  flex: 1;
  font-size: 13.5px;
  color: #0d1b2e;
  width: 100%;
}

.input-wrapper input::placeholder {
  color: #9ca3af;
}

.eye-toggle-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #93c5fd;
  padding: 0;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}

.eye-toggle-btn:hover {
  color: #2563eb;
}

.eye-toggle-btn svg {
  width: 16px;
  height: 16px;
}

.options-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12.5px;
  margin-top: 2px;
}

.remember-checkbox-label {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #374151;
  cursor: pointer;
}

.remember-checkbox-label input {
  accent-color: #2563eb;
  width: 14px;
  height: 14px;
  border-radius: 3px;
  cursor: pointer;
}

.forgot-link {
  color: #2563eb;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
}

.forgot-link:hover {
  text-decoration: underline;
  color: #1d4ed8;
}

.submit-btn {
  background-color: #1e40af;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  height: 40px;
  font-weight: 600;
  font-size: 13.5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 6px;
  transition: background-color 0.2s;
  text-transform: none !important;
}

.btn-text {
  text-transform: none !important;
}

.submit-btn:hover {
  background-color: #1d4ed8;
}

.submit-btn:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.btn-arrow {
  width: 16px;
  height: 16px;
  transition: transform 0.2s;
}

.submit-btn:hover .btn-arrow {
  transform: translateX(3px);
}

.or-divider {
  display: flex;
  align-items: center;
  margin: 14px 0;
}

.divider-line {
  flex: 1;
  height: 1px;
  background-color: #e5e7eb;
}

.divider-text {
  font-size: 11.5px;
  font-weight: 600;
  color: #9ca3af;
  padding: 0 10px;
  text-transform: none !important;
}

.social-row {
  display: flex;
  gap: 8px;
}

.social-btn-google {
  flex: 1;
  height: 38px;
  background-color: #ffffff;
  border: 1px solid #dbeafe;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background-color 0.2s, border-color 0.2s;
  text-transform: none !important;
}

.google-logo {
  width: 18px;
  height: 18px;
  flex: 0 0 18px;
  display: block;
}

.social-btn-google:hover {
  background-color: #f0f7ff;
  border-color: #93c5fd;
}

.social-btn-google:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.footer-register {
  text-align: center;
  font-size: 12.5px;
  color: #4b5563;
  margin-top: 14px;
  margin-bottom: 0;
}

.register-link {
  color: #2563eb;
  font-weight: 700;
  cursor: pointer;
}

.register-link:hover {
  text-decoration: underline;
  color: #1d4ed8;
}

@keyframes loginShellFade {
  0% { transform: scale(1); opacity: 1; }
  48% { transform: scale(1.01); opacity: 1; }
  78% { transform: scale(1.025); opacity: 0.42; }
  100% { transform: scale(1.035); opacity: 0; }
}

@media (max-width: 768px) {
  .login-box {
    grid-template-columns: 1fr;
    width: 95%;
    height: auto;
  }
  .left-col { display: none; }

  .login-box.admin-opening .right-col,
  .login-box.web-opening .right-col,
  .login-box.social-opening .right-col {
    transform: translate3d(0, -56px, 0) scale(0.965);
  }
}

@media (prefers-reduced-motion: reduce) {
  .login-box,
  .left-col,
  .right-col {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}

@media (max-width: 480px) {
  .right-col { padding: 24px 20px; }
}

/* MODAL */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.modal-card {
  background: white;
  border-radius: 20px;
  padding: 36px 32px;
  width: 360px;
  text-align: center;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.15);
}

.modal-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.modal-card.error .modal-icon { background: #fee2e2; color: #ef4444; }
.modal-card.success .modal-icon { background: #dbeafe; color: #2563eb; }

.modal-title {
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 8px;
  color: #0d1b2e;
}

.modal-message {
  font-size: 14px;
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 24px;
}

.modal-btn {
  width: 100%;
  padding: 12px;
  border-radius: 25px;
  border: none;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: opacity 0.2s;
}

.modal-btn.error { background-color: #dc2626; color: white; }
.modal-btn.success { background-color: #1e40af; color: white; }
.modal-btn:hover { opacity: 0.88; }

.modal-enter-active,
.modal-leave-active { transition: opacity 0.25s ease; }

.modal-enter-active .modal-card,
.modal-leave-active .modal-card { transition: transform 0.25s ease; }

.modal-enter-from,
.modal-leave-to { opacity: 0; }

.modal-enter-from .modal-card,
.modal-leave-to .modal-card { transform: scale(0.92) translateY(10px); }
</style>
