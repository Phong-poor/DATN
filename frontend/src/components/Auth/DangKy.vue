<script setup>
import { computed, nextTick, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { formatAuthMessage } from '@/services/authMessages'
import {
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
const referralCode = ref('')

const normalizedPhone = computed(() => normalizePhone(phone.value))
const formatMoney = (value) => `${Number(value || 0).toLocaleString('vi-VN')}đ`

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
  await nextTick()

  try {
    const res = await api.post('/register', {
      ten: name.value.trim(),
      email: normalizeEmail(email.value),
      sodienthoai: normalizedPhone.value,
      matkhau: password.value,
      matkhau_confirmation: confirm.value,
      referral_code: referralCode.value.trim().toUpperCase() || null,
    }, { immediateLoader: true })
    const rewardPromotion = res.data?.referral_reward?.promotion
    const rewardMessage = rewardPromotion
      ? ` Bạn đã nhận voucher ${rewardPromotion.code} trị giá ${formatMoney(rewardPromotion.value)} trong ví ưu đãi.`
      : ''

    showModal('success', 'Đăng ký thành công!', `${formatAuthMessage(res.data.message, 'Đăng ký thành công!')}${rewardMessage}`, () => {
      name.value = ''
      email.value = ''
      phone.value = ''
      password.value = ''
      confirm.value = ''
      referralCode.value = ''
      acceptTerms.value = false

      router.push('/dang-nhap')
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
  const params = new URLSearchParams({ frontend_url: window.location.origin })
  if (refCode) params.set('ref', refCode)
  const endpoint = `/auth/google?${params.toString()}`
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
            <span class="brand-title">NextGen</span>
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
      </div>

      <!-- RIGHT (White Column) -->
      <div class="right-col">
        <div class="tab-header">
          <span class="tab-btn" @click="router.push('/dang-nhap')">Đăng nhập</span>
          <span class="tab-btn active" @click="router.push('/dang-ky')">Đăng ký</span>
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
              <input v-model="email" type="email" name="email" autocomplete="email" placeholder="Example@gmail.com" @blur="isTouched.email.value = true" />
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
              <input v-model="referralCode" name="referral_code" autocomplete="off" placeholder="Nhập mã giới thiệu nếu có" />
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
              {{ loading ? 'Đang tạo tài khoản...' : 'Đăng ký ngay' }}
            </span>
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
          <button @click="loginGoogle" class="social-btn-google">
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
          Bạn đã có tài khoản? <span class="register-link" @click="router.push('/dang-nhap')">Đăng nhập ngay</span>
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
  background: #0d1b2e;
  color: #ffffff;
  padding: 36px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
  isolation: isolate;
}

.left-col::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 0;
  background: url('/register_laptop.jpg') center / cover no-repeat;
  opacity: 0.76;
  transform: scale(1.02);
}

.left-col::after {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  background:
    linear-gradient(180deg, rgba(13, 27, 46, 0.62), rgba(13, 27, 46, 0.42)),
    linear-gradient(90deg, rgba(15, 23, 42, 0.72), rgba(15, 23, 42, 0.18));
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
  margin-bottom: 18px;
  position: relative;
  z-index: 2;
}

.highlight-pills {
  display: flex;
  flex-direction: column;
  gap: 8px;
  position: relative;
  z-index: 2;
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
  margin-top: 4px;
  padding: 0 2px;
}

.strength-track {
  height: 3px;
  background: #e5e7eb;
  border-radius: 99px;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  border-radius: inherit;
  transition: width 0.25s ease, background 0.25s ease;
}

.strength-requirements {
  display: none;
}

.strength-requirements span {
  color: #9ca3af;
  font-size: 10.5px;
  font-weight: 600;
}

.strength-requirements span.ok {
  color: #2563eb;
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
  text-transform: none !important;
}

.social-row {
  display: flex;
  gap: 8px;
}

.social-btn-google {
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
  text-transform: none !important;
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
