<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { formatAuthMessage } from '@/services/authMessages'
import {
  getPasswordChecks,
  getPasswordRequirements,
  getPasswordScore,
  getPasswordStrength,
  normalizeEmail,
  normalizePhone,
  validateEmail,
  validatePasswordConfirmation,
  validatePhone,
  validateStrongPassword,
} from '@/services/authValidation'

const name = ref('')
const email = ref('')
const phone = ref('')
const password = ref('')
const confirm = ref('')
const showPassword = ref(false)
const showConfirm = ref(false)
const loading = ref(false)

const modal = ref({ show: false, type: 'error', title: '', message: '', onConfirm: null })

let autoCloseTimer = null

const showModal = (type, title, message, onConfirm = null) => {
  modal.value = { show: true, type, title, message, onConfirm }
  if (type === 'success') {
    if (autoCloseTimer) clearTimeout(autoCloseTimer)
    autoCloseTimer = setTimeout(() => {
      closeModal()
    }, 2000)
  }
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
const referralCode = ref((route.query.ref || localStorage.getItem('affiliate_ref') || '').toString().trim().toUpperCase())

const normalizedPhone = computed(() => normalizePhone(phone.value))

const isValidPhone = computed(() => !phone.value || !validatePhone(phone.value))

const passwordChecks = computed(() => getPasswordChecks(password.value))

const passwordScore = computed(() => getPasswordScore(password.value))

const passwordStrength = computed(() => getPasswordStrength(password.value))

const passwordRequirements = computed(() => getPasswordRequirements(password.value))

const handleRegister = async () => {
  if (!name.value.trim() || !email.value || !phone.value || !password.value || !confirm.value) {
    showModal('error', 'Thiếu thông tin', 'Vui lòng nhập đầy đủ thông tin.')
    return
  }

  const emailError = validateEmail(email.value)
  if (emailError) {
    showModal('error', 'Email không hợp lệ', emailError)
    return
  }

  const phoneError = validatePhone(phone.value)
  if (phoneError) {
    showModal('error', 'Số điện thoại không hợp lệ', phoneError)
    return
  }

  const passwordError = validateStrongPassword(password.value)
  if (passwordError) {
    showModal('error', 'Mật khẩu chưa đủ mạnh', passwordError)
    return
  }

  const confirmError = validatePasswordConfirmation(password.value, confirm.value)
  if (confirmError) {
    showModal('error', 'Mật khẩu không khớp', confirmError)
    return
  }

  if (loading.value) return
  loading.value = true

  try {
    const res = await api.post('/register', {
      name: name.value.trim(),
      email: normalizeEmail(email.value),
      phone: normalizedPhone.value,
      password: password.value,
      password_confirmation: confirm.value,
      referral_code: referralCode.value || null,
    })
    showModal('success', 'Đăng ký thành công!', formatAuthMessage(res.data.message, 'Đăng ký thành công!'), () => {
      name.value = ''
      email.value = ''
      phone.value = ''
      password.value = ''
      confirm.value = ''

      router.push('/login')
    })

  } catch (err) {
    console.log(err)

    if (err.response?.data?.errors) {
      const firstError = Object.values(err.response.data.errors)[0][0]
      showModal('error', 'Lỗi', formatAuthMessage(firstError))
    } else if (err.response?.data?.message) {
      showModal('error', 'Lỗi', formatAuthMessage(err.response.data.message))
    } else {
      showModal('error', 'Lỗi', 'Có lỗi xảy ra. Vui lòng thử lại.')
    }

  } finally {
    loading.value = false
  
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
</script>

<template>
  <div class="page">

    <div class="box">

      <!-- LEFT -->
      <div class="left">
        <h1>
          Predator <br />
          Thế hệ mới 2026 <br />
          <span>Chinh Phục Tầm Cao</span>
        </h1>
        <p>
          Tạo tài khoản để lưu cấu hình laptop yêu thích, nhận ưu đãi riêng và theo dõi đơn hàng nhanh hơn.
        </p>
        <div class="left-highlights">
          <span>Ưu đãi thành viên</span>
          <span>Theo dõi bảo hành</span>
          <span>Gợi ý phụ kiện phù hợp</span>
        </div>
        <img class="left-accessories" src="/elite_accessories.png" alt="Phụ kiện laptop Predator" />
      </div>

      <!-- RIGHT -->
      <div class="right">

        <div class="tabs">
          <span @click="router.push('/login')">Đăng nhập</span>
          <span class="active" @click="router.push('/register')">Đăng ký</span>
        </div>

        <h2>Tạo tài khoản mới</h2>
        <p class="sub">Bắt đầu hành trình cùng VinaTech</p>

        <!-- NAME -->
        <div class="input-box">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
              <circle cx="12" cy="7" r="4" />
            </svg>
          </span>
          <input v-model="name" name="name" autocomplete="name" placeholder="Nguyễn Văn A" />
        </div>

        <!-- EMAIL -->
        <div class="input-box">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="3" />
              <path d="M2 7l10 7 10-7" />
            </svg>
          </span>
          <input v-model="email" type="email" name="email" autocomplete="email" placeholder="example@vinatech.vn" />
        </div>

        <!-- PHONE -->
        <div class="input-box">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg>
          </span>
          <input v-model="phone" type="tel" name="phone" autocomplete="tel" inputmode="numeric" maxlength="12" placeholder="0123 456 789" />
        </div>
        <p v-if="phone && !isValidPhone" class="field-hint error">
          Số điện thoại phải có 10 chữ số và bắt đầu bằng 0.
        </p>

        <div class="input-box" v-if="referralCode">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
              <line x1="7" y1="7" x2="7.01" y2="7" />
            </svg>
          </span>
          <input :value="referralCode" name="referral_code" readonly />
        </div>

        <!-- PASSWORD -->
        <div class="input-box">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
          </span>
          <input :type="showPassword ? 'text' : 'password'" v-model="password" name="new-password" autocomplete="new-password" placeholder="••••••••" />
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
        <div v-if="password" class="password-strength">
          <div class="strength-head">
            <span :style="{ color: passwordStrength.color }">{{ passwordStrength.label }}</span>
            <small>{{ passwordScore }}/5</small>
          </div>
          <div class="strength-track">
            <div class="strength-fill" :style="{ width: passwordStrength.width, background: passwordStrength.color }"></div>
          </div>
        </div>

        <!-- CONFIRM -->
        <div class="input-box">
          <span class="input-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
          </span>
          <input :type="showConfirm ? 'text' : 'password'" v-model="confirm" name="new-password-confirm" autocomplete="new-password" placeholder="Xác nhận mật khẩu" />
          <button class="eye-btn" @click="showConfirm = !showConfirm" type="button">
            <svg v-if="!showConfirm" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
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

        <!-- BUTTON -->
        <button class="btn" @click="handleRegister">Đăng ký ngay</button>

        <div class="divider">HOẶC ĐĂNG KÝ BẰNG</div>

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

        <p class="login">
          Bạn đã có tài khoản?
          <a @click="router.push('/login')">Đăng nhập ngay</a>
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

.box {
  width: 1000px;
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
  padding: 38px 44px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.left h1 {
  margin: 0;
  color: #ffffff;
  font-family: 'Be Vietnam Pro', 'Inter', system-ui, sans-serif;
  font-size: 31px;
  line-height: 1.22;
  font-weight: 900;
  letter-spacing: 0;
  text-shadow: 0 12px 28px rgba(15, 23, 42, 0.24);
}

.left span {
  color: #e4dcff;
}

.left p {
  max-width: 410px;
  margin: 22px 0 0;
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
  margin: 18px 0 24px;
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

.left-accessories {
  width: 100%;
  height: 286px;
  object-fit: cover;
  object-position: center;
  border-radius: 14px;
  box-shadow: 0 22px 42px rgba(15, 23, 42, 0.28);
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
  margin-bottom: 12px;
}

.input-box input {
  border: none;
  background: transparent;
  outline: none;
  flex: 1;
  font-size: 14px;
  color: #1e293b;
}

.input-icon {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  color: #6366f1;
}
.input-icon svg {
  width: 16px;
  height: 16px;
}

.field-hint {
  margin: -6px 0 10px;
  font-size: 11.5px;
  font-weight: 600;
  line-height: 1.4;
}

.field-hint.error {
  color: #ef4444;
}

.password-strength {
  margin: -8px 0 12px;
  padding: 0 4px;
  background: transparent;
  border: none;
  border-radius: 0;
}

.strength-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 4px;
  font-size: 11px;
  font-weight: 700;
}

.strength-head small {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
}

.strength-track {
  height: 3px;
  background: #e2e8f0;
  border-radius: 99px;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  border-radius: inherit;
  transition: width 0.25s ease, background 0.25s ease;
}

.strength-requirements span {
  color: #94a3b8;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}

.strength-requirements span.ok {
  color: #16a34a;
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

.btn {
  width: 100%;
  padding: 12px;
  border-radius: 25px;
  border: none;
  background: linear-gradient(90deg, #2563eb, #7c3aed);
  color: white;
  font-weight: 600;
  cursor: pointer;
  margin-top: 10px;
  transition: opacity 0.2s;
}

.btn:hover {
  opacity: 0.9;
}

.divider {
  text-align: center;
  font-size: 12px;
  margin: 15px 0;
  color: #94a3b8;
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

.login {
  text-align: center;
  font-size: 12px;
  margin-top: 15px;
}

.login a {
  color: #2563eb;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
}

.login a:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .box {
    grid-template-columns: 1fr;
    width: 95%;
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
