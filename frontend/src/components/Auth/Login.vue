<script setup>
import { ref, onMounted } from 'vue'
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
  const refCode = localStorage.getItem('affiliate_ref') || ''
  const endpoint = refCode ? `/auth/google?ref=${encodeURIComponent(refCode)}` : '/auth/google'
  window.location.href = `${api.defaults.baseURL}${endpoint}`
}

const loginFacebook = () => {
  const refCode = localStorage.getItem('affiliate_ref') || ''
  const endpoint = refCode ? `/auth/facebook?ref=${encodeURIComponent(refCode)}` : '/auth/facebook'
  window.location.href = `${api.defaults.baseURL}${endpoint}`
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
  if (!path.startsWith('/') || path.startsWith('/login')) return ''
  return path
}

const redirectAfterLogin = async (user, token) => {
  const redirectPath = safeRedirectPath(
    route.query.redirect || sessionStorage.getItem('redirect_after_auth')
  )
  sessionStorage.removeItem('redirect_after_auth')

  if (user?.role === 'admin') {
    await router.replace('/admin')
    return
  }

  if (redirectPath) {
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
      await router.replace('/cart')
      return
    } catch (err) {
      console.error('Lỗi thêm pending item:', err)
    }
  }

  await router.replace('/')
}


onMounted(() => {
  if (route.query.social_error) {
    const messageByCode = {
      google_callback_failed: 'Không thể xác thực Google. Vui lòng thử lại.',
      google_create_failed: 'Đăng nhập Google thất bại do lỗi tạo tài khoản.',
      google_user_not_found: 'Không tạo được tài khoản từ Google.',
      facebook_callback_failed: 'Không thể xác thực Facebook. Vui lòng thử lại.',
      facebook_create_failed: 'Đăng nhập Facebook thất bại do lỗi tạo tài khoản.',
      facebook_user_not_found: 'Không tạo được tài khoản từ Facebook.',
    }
    const provider = String(route.query.social_error).startsWith('google') ? 'Google' : 'Facebook'
    showModal('error', `Lỗi đăng nhập ${provider}`, messageByCode[route.query.social_error] || 'Đăng nhập mạng xã hội thất bại.')
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
    if (user.role === 'admin') {
      router.push('/admin')
    } else {
      router.push('/')
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
  const emailError = validateEmail(email.value)
  if (emailError) {
    showModal('error', 'Email không hợp lệ', emailError)
    return
  }

  if (!password.value) {
    showModal('error', 'Thiếu thông tin', 'Vui lòng nhập mật khẩu.')
    return
  }

  if (loading.value) return

  loading.value = true

  try {
    const res = await api.post('/login', {
      email: normalizeEmail(email.value),
      password: password.value,
      remember: remember.value
    })

    const user = res.data.user
    const token = res.data.token

    if (!token) {
      showModal('error', 'Lỗi', 'Máy chủ không trả về token đăng nhập.')
      return
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

    if (err.response?.data?.message) {
      showModal('error', 'Lỗi', formatAuthMessage(err.response.data.message, 'Email hoặc mật khẩu không đúng.'))
    } else {
      showModal('error', 'Lỗi', 'Email hoặc mật khẩu không đúng.')
    }
  } finally {
    loading.value = false
  }
}
</script>
<template>
  <div class="page">

    <div class="login-box">

      <!-- LEFT -->
      <div class="left">
        <h1>
          NextGen <br />
          <span>Chinh Phục</span> <br />
          Tầm Cao Mới.
        </h1>
        <p>
          Trở lại tài khoản để quản lý đơn hàng, lưu cấu hình laptop và nhận ưu đãi dành riêng cho bạn.
        </p>
        <div class="left-highlights">
          <span>✓ Đăng nhập nhanh</span>
          <span>✓ Đồng bộ giỏ hàng</span>
          <span>✓ Ưu đãi cá nhân</span>
        </div>
        <img class="left-laptop" src="/hero_3d_laptop.png" alt="NextGen laptop" />
      </div>

      <!-- RIGHT -->
      <div class="right">

        <div class="tabs">
          <span class="active" @click="router.push('/login')">Đăng nhập</span>
          <span @click="router.push('/register')">Đăng ký</span>
        </div>

        <h2>Chào mừng trở lại</h2>
        <p class="sub">Nhập thông tin tài khoản của bạn.</p>

        <!-- EMAIL -->
        <div class="input-box">
          <span>@</span>
          <input v-model="email" type="email" name="username" autocomplete="username"
            placeholder="example@vinatech.vn" />
        </div>

        <!-- PASSWORD -->
        <div class="input-box">
          <span>🔒</span>
          <input :type="showPassword ? 'text' : 'password'" v-model="password" name="password"
            autocomplete="current-password" placeholder="••••••••" />
          <button class="eye-btn" @click="showPassword = !showPassword" type="button">
            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
              <circle cx="12" cy="12" r="3" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
              <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
              <line x1="1" y1="1" x2="23" y2="23" />
            </svg>
          </button>
        </div>

        <!-- OPTIONS -->
        <div class="options">
          <label>
            <input type="checkbox" v-model="remember" />
            Ghi nhớ đăng nhập
          </label>
          <a @click="router.push('/forgot-password')">Quên mật khẩu?</a>
        </div>

        <!-- BUTTON -->
        <button class="btn" @click="handleLogin" :disabled="loading">
          {{ loading ? 'Đang đăng nhập...' : 'Đăng Nhập Ngay' }}
        </button>

        <div class="divider">HOẶC TIẾP TỤC VỚI</div>

        <!-- SOCIAL -->
        <div class="social">
          <button @click="loginGoogle" class="btn-google">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
              <path fill="#fff"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
              <path fill="#fff"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
              <path fill="#fff"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" />
              <path fill="#fff"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
            </svg>
            Google
          </button>

          <button @click="loginFacebook" class="btn-facebook">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#fff">
              <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
            </svg>
            Facebook
          </button>
        </div>

        <p class="register">
          Bạn chưa có tài khoản?
          <a @click="router.push('/register')">Tạo tài khoản mới</a>
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
  background: #f4f7fc;
  font-family: 'Inter', sans-serif;
}

.login-box {
  width: 950px;
  height: 520px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: white;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.08);
}

.left {
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  color: white;
  padding: 38px 44px 30px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
}

.left h1 {
  margin: 0;
  color: #ffffff;
  font-family: 'Be Vietnam Pro', 'Inter', system-ui, sans-serif;
  font-size: 33px;
  line-height: 1.18;
  font-weight: 900;
  letter-spacing: 0;
  text-wrap: balance;
  text-shadow: 0 12px 28px rgba(15, 23, 42, 0.24);
}

.left span {
  color: #e4dcff;
}

.left p {
  max-width: 410px;
  margin: 20px 0 0;
  color: rgba(255, 255, 255, 0.86);
  font-family: 'Be Vietnam Pro', 'Inter', system-ui, sans-serif;
  font-size: 13.5px;
  line-height: 1.7;
  font-weight: 500;
}

.left-highlights {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 16px 0 20px;
}

.left-highlights span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  color: #ffffff;
  font-size: 11.5px;
  font-weight: 700;
  border: 1px solid rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
}

.left-laptop {
  width: min(310px, 100%);
  align-self: center;
  margin-top: 0;
  transform: translateX(8px) rotate(-4deg);
  filter: drop-shadow(0 28px 36px rgba(15, 23, 42, 0.34));
  user-select: none;
  pointer-events: none;
}

.right {
  padding: 40px;
}

.tabs {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  font-weight: 600;
}

.tabs span {
  cursor: pointer;
  color: #94a3b8;
  transition: color 0.2s;
}

.tabs span:hover {
  color: #475569;
}

.tabs .active {
  color: #2563eb;
  border-bottom: 2px solid #2563eb;
}

h2 {
  margin-bottom: 4px;
}

.sub {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 20px;
}

.input-box {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f1f5f9;
  border-radius: 10px;
  padding: 10px;
  margin-bottom: 14px;
}

.input-box span {
  color: #64748b;
}

.input-box input {
  border: none;
  background: transparent;
  outline: none;
  flex: 1;
}

.eye-btn {
  background: none;
  border: none;
  padding: 0 2px;
  cursor: pointer;
  color: #94a3b8;
  display: flex;
  align-items: center;
  line-height: 1;
  transition: color 0.2s;
}

.eye-btn:hover {
  color: #475569;
}

.options {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  margin-bottom: 20px;
}

.options a {
  color: #2563eb;
  text-decoration: none;
  cursor: pointer;
}

.options a:hover {
  text-decoration: underline;
}

.btn {
  width: 100%;
  padding: 12px;
  border-radius: 25px;
  border: none;
  background: linear-gradient(90deg, #2563eb, #7c3aed);
  color: white;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 20px;
  transition: opacity 0.2s;
}

.btn:hover {
  opacity: 0.9;
}

.divider {
  text-align: center;
  font-size: 12px;
  color: #94a3b8;
  margin-bottom: 15px;
}

.social {
  display: flex;
  gap: 10px;
}

.btn-google,
.btn-facebook {
  flex: 1;
  padding: 10px;
  border-radius: 20px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: opacity 0.2s;
}

.btn-google {
  background: #db4437;
  color: white;
}

.btn-facebook {
  background: #1877f2;
  color: white;
}

.btn-google:hover,
.btn-facebook:hover {
  opacity: 0.88;
}

.register {
  text-align: center;
  font-size: 12px;
  margin-top: 15px;
}

.register a {
  color: #2563eb;
  text-decoration: none;
  cursor: pointer;
  font-weight: 600;
}

.register a:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .login-box {
    grid-template-columns: 1fr;
    width: 95%;
    height: auto;
  }

  .left {
    display: none;
  }
}

@media (max-width: 480px) {
  .right {
    padding: 24px 20px;
  }
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

.modal-card.error .modal-icon {
  background: #fee2e2;
  color: #ef4444;
}

.modal-card.success .modal-icon {
  background: #dcfce7;
  color: #22c55e;
}

.modal-title {
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 8px;
  color: #0f172a;
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

.modal-btn.error {
  background: linear-gradient(90deg, #ef4444, #dc2626);
  color: white;
}

.modal-btn.success {
  background: linear-gradient(90deg, #2563eb, #7c3aed);
  color: white;
}

.modal-btn:hover {
  opacity: 0.88;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease;
}

.modal-enter-active .modal-card,
.modal-leave-active .modal-card {
  transition: transform 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-card,
.modal-leave-to .modal-card {
  transform: scale(0.92) translateY(10px);
}
</style>
