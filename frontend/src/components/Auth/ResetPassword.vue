<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { formatAuthMessage } from '@/services/authMessages'
import {
  getPasswordChecks,
  getPasswordRequirements,
  getPasswordScore,
  getPasswordStrength,
  validatePasswordConfirmation,
  validateStrongPassword,
} from '@/services/authValidation'

const route = useRoute()
const router = useRouter()

const email = ref(route.query.email || '')
const otp = ref(route.query.otp || '')

const showPassword = ref(false)
const showConfirm = ref(false)
const isLoading = ref(false)

const form = ref({ password: '', confirm: '' })
const errors = ref({ password: '', confirm: '' })

const isTouched = {
  password: ref(false),
  confirm: ref(false),
}

onMounted(() => {
  if (!email.value || !otp.value) {
    showModal('error', 'Lỗi xác thực', 'Thiếu thông tin xác thực. Vui lòng thực hiện lại.', () => {
      router.push('/forgot-password')
    })
  }
})

const passwordChecks = computed(() => getPasswordChecks(form.value.password))
const passwordScore = computed(() => getPasswordScore(form.value.password))
const passwordStrength = computed(() => getPasswordStrength(form.value.password))
const passwordRequirements = computed(() => getPasswordRequirements(form.value.password))

const confirmError = computed(() => {
  if (!isTouched.confirm.value) return ''
  return validatePasswordConfirmation(form.value.password, form.value.confirm)
})

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

function validatePassword() {
  errors.value.password = ''
  if (form.value.confirm && form.value.password === form.value.confirm) {
    errors.value.confirm = ''
  }
}

function validate() {
  errors.value = { password: '', confirm: '' }
  const passwordError = validateStrongPassword(form.value.password).replace('mật khẩu.', 'mật khẩu mới.')
  if (passwordError) {
    errors.value.password = passwordError
    showModal('error', 'Mật khẩu yếu', passwordError)
    return false
  }
  const confirmErrorVal = validatePasswordConfirmation(form.value.password, form.value.confirm)
  if (confirmErrorVal) {
    errors.value.confirm = confirmErrorVal
    showModal('error', 'Lỗi xác nhận', confirmErrorVal)
    return false
  }
  return true
}

async function submit() {
  isTouched.password.value = true
  isTouched.confirm.value = true
  
  if (!validate()) return
  if (isLoading.value) return

  isLoading.value = true

  try {
    const res = await api.post('/forgot-password/reset-password', {
      email: email.value,
      otp: otp.value,
      matkhau: form.value.password,
      matkhau_confirmation: form.value.confirm,
    })

    showModal('success', 'Thành công', formatAuthMessage(res.data.message, 'Đổi mật khẩu thành công!'), () => {
      sessionStorage.removeItem('redirect_after_auth')
      router.push('/login')
    })

  } catch (err) {
    console.log(err)

    if (err.response?.data?.errors) {
      const firstError = Object.values(err.response.data.errors)[0][0]
      showModal('error', 'Lỗi', formatAuthMessage(firstError))
    } else {
      showModal('error', 'Lỗi', formatAuthMessage(err.response?.data?.message, 'Không thể đổi mật khẩu. Vui lòng thử lại.'))
    }

  } finally {
    isLoading.value = false
  }
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
            Thiết lập mật khẩu mới bảo mật để tiếp tục truy cập tài khoản Predator của bạn.
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
          <img class="laptop-img" src="/login_laptop_mockup.png" alt="Predator laptop workspace" />
        </div>
      </div>

      <!-- RIGHT (White Column) -->
      <div class="right-col">
        <div class="welcome-box">
          <h2 class="welcome-title">Đặt lại mật khẩu mới</h2>
          <p class="welcome-desc">Vui lòng nhập mật khẩu mới để bảo mật tài khoản của bạn.</p>
        </div>

        <!-- FORM FIELDS -->
        <form class="form-container" @submit.prevent="submit">
          <!-- NEW PASSWORD -->
          <div class="form-group">
            <label class="input-label">Mật khẩu mới</label>
            <div class="input-wrapper" :class="{ 'error': (isTouched.password.value && passwordScore < 4) || errors.password }">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" />
                  <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
              </span>
              <input :type="showPassword ? 'text' : 'password'" v-model="form.password" placeholder="••••••••" @blur="isTouched.password.value = true" @input="validatePassword" />
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
            <div v-if="form.password" class="password-strength">
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
            <p v-if="errors.password" class="field-hint error">{{ errors.password }}</p>
          </div>

          <!-- CONFIRM PASSWORD -->
          <div class="form-group">
            <label class="input-label">Xác nhận mật khẩu mới</label>
            <div class="input-wrapper" :class="{ 'error': confirmError || errors.confirm }">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                  <polyline points="9 11 11 13 15 9" />
                </svg>
              </span>
              <input :type="showConfirm ? 'text' : 'password'" v-model="form.confirm" placeholder="••••••••" @blur="isTouched.confirm.value = true" />
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
            <p v-if="confirmError || errors.confirm" class="field-hint error">{{ confirmError || errors.confirm }}</p>
          </div>

          <!-- SUBMIT BUTTON -->
          <button type="submit" class="submit-btn" :disabled="isLoading">
            <span class="btn-text">
              {{ isLoading ? 'Đang cập nhật mật khẩu...' : 'Cập nhật mật khẩu' }}
            </span>
          </button>
        </form>

        <!-- FOOTER BACK LINK -->
        <p class="footer-register">
          <span class="register-link back-to-login" @click="router.push('/login')">
            <svg class="back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="19" y1="12" x2="5" y2="12"></line>
              <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Quay lại trang đăng nhập
          </span>
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
  height: 590px;
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
  padding: 30px 44px;
  background: #ffffff;
  display: flex;
  flex-direction: column;
  justify-content: center;
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
  margin: 4px 0 0 0;
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
  height: 3px;
  background: #e5e7eb;
  border-radius: 99px;
  overflow: hidden;
  margin-top: 2px;
}

.strength-fill {
  height: 100%;
  border-radius: inherit;
  transition: width 0.25s ease, background 0.25s ease;
}

.strength-requirements {
  display: flex;
  flex-wrap: wrap;
  gap: 2px 8px;
  margin-top: 4px;
}

.strength-requirements span {
  color: #9ca3af;
  font-size: 10.5px;
  font-weight: 600;
}

.strength-requirements span.ok {
  color: #16a34a;
}

.submit-btn {
  background-color: #1e40af;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  height: 40px;
  font-weight: 700;
  font-size: 13.5px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 6px;
  transition: background-color 0.2s;
}

.submit-btn:hover {
  background-color: #1d4ed8;
}

.submit-btn:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.footer-register {
  text-align: center;
  font-size: 12.5px;
  color: #4b5563;
  margin-top: 18px;
  margin-bottom: 0;
}

.register-link {
  color: #2563eb;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.register-link:hover {
  text-decoration: underline;
  color: #1d4ed8;
}

.back-icon {
  width: 14px;
  height: 14px;
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
