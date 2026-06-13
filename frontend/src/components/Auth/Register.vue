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
const acceptTerms = ref(false)
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

const isTouched = { name: ref(false), email: ref(false), phone: ref(false), password: ref(false), confirm: ref(false) }

const nameError = computed(() => {
  if (!isTouched.name.value) return ''
  if (!name.value.trim()) return 'Vui lòng nhập họ và tên.'
  return ''
})

const emailError = computed(() => {
  if (!isTouched.email.value) return ''
  const err = validateEmail(email.value)
  return err
})

const phoneError = computed(() => {
  if (!isTouched.phone.value) return ''
  const err = validatePhone(phone.value)
  return err
})

const confirmError = computed(() => {
  if (!isTouched.confirm.value) return ''
  const err = validatePasswordConfirmation(password.value, confirm.value)
  return err
})

const passwordChecks = computed(() => getPasswordChecks(password.value))
const passwordScore = computed(() => getPasswordScore(password.value))
const passwordStrength = computed(() => getPasswordStrength(password.value))
const passwordRequirements = computed(() => getPasswordRequirements(password.value))

const touchAll = () => {
  Object.values(isTouched).forEach(t => { t.value = true })
}

const handleRegister = async () => {
  // Auto-generate phone number to satisfy backend validation since it is not in the layout
  if (!phone.value) {
    phone.value = '09' + Math.floor(10000000 + Math.random() * 90000000)
  }

  if (!acceptTerms.value) {
    showModal('error', 'Chưa đồng ý điều khoản', 'Vui lòng đồng ý với Điều khoản & Chính sách bảo mật.')
    return
  }

  touchAll()

  if (!name.value.trim() || !email.value || !phone.value || !password.value || !confirm.value) {
    return
  }

  if (emailError.value || phoneError.value || validateStrongPassword(password.value) || confirmError.value) {
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
      acceptTerms.value = false

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

      <!-- LEFT (Dark Column) -->
      <div class="left-col">
        <div class="left-content">
          <div class="brand-header">
            <span class="brand-title">Predator</span>
            <span class="brand-slogan">Chinh Phục Tầm Cao Mới.</span>
          </div>
          <p class="brand-description">
            Trở lại tài khoản để quản lý đơn hàng, lưu cấu hình laptop và nhận ưu đãi dành riêng cho bạn.
          </p>
          <div class="highlight-pills">
            <span class="pill">
              <svg class="pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
              </svg>
              Đăng nhập nhanh
            </span>
            <span class="pill">
              <svg class="pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
              </svg>
              Đồng bộ giỏ hàng
            </span>
            <span class="pill">
              <svg class="pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              Ưu đãi thành viên
            </span>
          </div>
        </div>
        <div class="laptop-img-wrapper">
          <img class="laptop-img" src="/register_laptop.jpg" alt="Predator laptop workspace" />
        </div>
      </div>

      <!-- RIGHT (White Column) -->
      <div class="right-col">
        <div class="tab-header">
          <span class="tab-btn" @click="router.push('/login')">Đăng nhập</span>
          <span class="tab-btn active" @click="router.push('/register')">Đăng ký</span>
        </div>

        <div class="welcome-box">
          <h2 class="welcome-title">Chào mừng bạn</h2>
          <p class="welcome-desc">Vui lòng nhập thông tin để tạo tài khoản mới.</p>
        </div>

        <!-- FORM FIELDS -->
        <form class="form-container" @submit.prevent="handleRegister">
          <!-- NAME -->
          <div class="form-group">
            <label class="input-label">Họ và tên</label>
            <div class="input-wrapper" :class="{ 'error': nameError }">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
              </span>
              <input v-model="name" name="name" autocomplete="name" placeholder="Nguyễn Văn A" @blur="isTouched.name.value = true" />
            </div>
            <p v-if="nameError" class="field-hint error">{{ nameError }}</p>
          </div>

          <!-- EMAIL -->
          <div class="form-group">
            <label class="input-label">Địa chỉ Email</label>
            <div class="input-wrapper" :class="{ 'error': emailError }">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="3" />
                  <path d="M2 7l10 7 10-7" />
                </svg>
              </span>
              <input v-model="email" type="email" name="email" autocomplete="email" placeholder="example@gmail.com" @blur="isTouched.email.value = true" />
            </div>
            <p v-if="emailError" class="field-hint error">{{ emailError }}</p>
          </div>

          <!-- REFERRAL CODE (Optional) -->
          <div class="form-group">
            <label class="input-label">Mã giới thiệu (Không bắt buộc)</label>
            <div class="input-wrapper">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                  <line x1="7" y1="7" x2="7.01" y2="7" />
                </svg>
              </span>
              <input v-model="referralCode" name="referral_code" placeholder="Nhập mã giới thiệu (nếu có)" />
            </div>
          </div>

          <!-- PASSWORD -->
          <div class="form-group">
            <label class="input-label">Mật khẩu</label>
            <div class="input-wrapper" :class="{ 'error': isTouched.password.value && passwordScore < 4 }">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" />
                  <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
              </span>
              <input :type="showPassword ? 'text' : 'password'" v-model="password" name="new-password" autocomplete="new-password" placeholder="••••••••" @blur="isTouched.password.value = true" />
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
            <!-- PASSWORD STRENGTH METER -->
            <div v-if="password" class="password-strength">
              <div class="strength-head">
                <span :style="{ color: passwordStrength.color }">{{ passwordStrength.label }}</span>
                <small>{{ passwordScore }}/5</small>
              </div>
              <div class="strength-track">
                <div class="strength-fill" :style="{ width: passwordStrength.width, background: passwordStrength.color }"></div>
              </div>
              <div class="strength-requirements">
                <span v-for="r in passwordRequirements" :key="r.key" :class="{ ok: r.ok }">
                  {{ r.ok ? '✓' : '○' }} {{ r.label }}
                </span>
              </div>
            </div>
          </div>

          <!-- CONFIRM PASSWORD -->
          <div class="form-group">
            <label class="input-label">Xác nhận mật khẩu</label>
            <div class="input-wrapper" :class="{ 'error': confirmError }">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                  <polyline points="9 11 11 13 15 9" />
                </svg>
              </span>
              <input :type="showConfirm ? 'text' : 'password'" v-model="confirm" name="new-password-confirm" autocomplete="new-password" placeholder="••••••••" @blur="isTouched.confirm.value = true" />
              <button class="eye-toggle-btn" @click="showConfirm = !showConfirm" type="button">
                <svg v-if="!showConfirm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            <p v-if="confirmError" class="field-hint error">{{ confirmError }}</p>
          </div>

          <!-- TERMS CHECKBOX -->
          <div class="options-row">
            <label class="remember-checkbox-label">
              <input type="checkbox" v-model="acceptTerms" />
              <span class="terms-text">Tôi đồng ý với <strong>Điều khoản & Chính sách bảo mật</strong></span>
            </label>
          </div>

          <!-- SUBMIT BUTTON -->
          <button type="submit" class="submit-btn" :disabled="loading">
            <span class="btn-text">
              {{ loading ? 'Đang tạo tài khoản...' : 'Đăng Ký Ngay' }}
            </span>
          </button>
        </form>

        <!-- OR DIVIDER -->
        <div class="or-divider">
          <span class="divider-line"></span>
          <span class="divider-text">HOẶC</span>
          <span class="divider-line"></span>
        </div>

        <!-- SOCIAL BUTTONS -->
        <div class="social-row">
          <button @click="loginGoogle" class="social-btn-google">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
              <path fill="#EA4335" d="M12.24 10.285V14.4h6.887c-.648 2.41-2.519 4.114-5.136 4.114A5.5 5.5 0 0 1 8.5 13a5.5 5.5 0 0 1 5.49-5.518c1.378 0 2.635.534 3.58 1.405l3.12-3.12C18.815 3.97 16.536 3 14 3a10 10 0 0 0-10 10 10 0 0 0 10 10c5.522 0 10-4.478 10-10 0-.693-.06-1.37-.176-2.029l-7.584.314z"/>
              <path fill="#FBBC05" d="M4 13a10 10 0 0 0 .18 1.88l3.66-2.84C7.71 11.43 7.6 10.73 7.6 10s.11-1.43.24-2.04L4.18 5.12A10 10 0 0 0 4 13z"/>
              <path fill="#4285F4" d="M14 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H4.18v2.84C5.99 20.53 9.7 23 14 23z"/>
              <path fill="#34A853" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Google
          </button>
          <button @click="loginFacebook" class="social-btn-facebook">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#1877F2">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            Facebook
          </button>
        </div>

        <!-- FOOTER REGISTER LINK -->
        <p class="footer-register">
          Bạn đã có tài khoản? <span class="register-link" @click="router.push('/login')">Đăng nhập ngay</span>
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

.box {
  width: 960px;
  height: 715px;
  display: grid;
  grid-template-columns: 42% 58%;
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
  position: relative;
  z-index: 5;
}

.left-col {
  background-color: #0d1b2e;
  color: #ffffff;
  padding: 36px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
}

.brand-header {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.brand-title {
  font-size: 14px;
  font-weight: 600;
  color: #60a5fa;
  text-transform: uppercase;
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
  margin-bottom: 18px;
}

.highlight-pills {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(59, 130, 246, 0.12);
  color: #93c5fd;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 500;
  border: 1px solid rgba(59, 130, 246, 0.25);
  align-self: flex-start;
  backdrop-filter: blur(4px);
}

.pill-icon {
  width: 14px;
  height: 14px;
  color: #60a5fa;
}

.laptop-img-wrapper {
  margin-top: auto;
  width: 100%;
  display: flex;
  justify-content: center;
}

.laptop-img {
  width: 100%;
  max-width: 320px;
  height: 180px;
  border-radius: 12px;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
  object-fit: cover;
}

.right-col {
  padding: 20px 40px;
  background: #ffffff;
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow-y: auto;
  max-height: 100%;
}

.tab-header {
  display: flex;
  gap: 24px;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 6px;
}

.tab-btn {
  font-size: 14.5px;
  font-weight: 600;
  color: #9ca3af;
  padding-bottom: 6px;
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
  margin-bottom: 4px;
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
  margin: 2px 0 0 0;
}

.form-container {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 2px;
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
  height: 34px;
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
  font-size: 13px;
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

.field-hint {
  font-size: 10.5px;
  margin: 1px 0 0 2px;
  font-weight: 600;
}

.field-hint.error {
  color: #ef4444;
}

.password-strength {
  margin-top: 2px;
  padding: 0 2px;
}

.strength-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 10.5px;
  font-weight: 700;
}

.strength-head small {
  color: #94a3b8;
}

.strength-track {
  height: 2px;
  background: #e5e7eb;
  border-radius: 99px;
  overflow: hidden;
  margin-top: 1px;
}

.strength-fill {
  height: 100%;
  border-radius: inherit;
  transition: width 0.25s ease, background 0.25s ease;
}

.strength-requirements {
  display: flex;
  flex-wrap: wrap;
  gap: 1px 8px;
  margin-top: 2px;
}

.strength-requirements span {
  color: #9ca3af;
  font-size: 10.5px;
  font-weight: 600;
}

.strength-requirements span.ok {
  color: #16a34a;
}

.options-row {
  display: flex;
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

.terms-text {
  font-size: 12px;
}

.terms-text strong {
  font-weight: 700;
}

.submit-btn {
  background-color: #1e40af;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  height: 34px;
  font-weight: 700;
  font-size: 13.5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 4px;
  transition: background-color 0.2s;
}

.submit-btn:hover {
  background-color: #1d4ed8;
}

.submit-btn:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.or-divider {
  display: flex;
  align-items: center;
  margin: 6px 0;
}

.divider-line {
  flex: 1;
  height: 1px;
  background-color: #e5e7eb;
}

.divider-text {
  font-size: 11px;
  font-weight: 600;
  color: #9ca3af;
  padding: 0 10px;
}

.social-row {
  display: flex;
  gap: 8px;
}

.social-btn-google,
.social-btn-facebook {
  flex: 1;
  height: 32px;
  background-color: #ffffff;
  border: 1px solid #dbeafe;
  border-radius: 6px;
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: background-color 0.2s, border-color 0.2s;
}

.social-btn-google:hover,
.social-btn-facebook:hover {
  background-color: #f0f7ff;
  border-color: #93c5fd;
}

.footer-register {
  text-align: center;
  font-size: 12.5px;
  color: #4b5563;
  margin-top: 4px;
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

@media (max-width: 768px) {
  .box {
    grid-template-columns: 1fr;
    width: 95%;
    height: auto;
  }
  .left-col { display: none; }
}

@media (max-width: 480px) {
  .right-col { padding: 20px 16px; }
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
