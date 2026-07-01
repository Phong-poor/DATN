<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { formatAuthMessage } from '@/services/authMessages'
import { normalizeEmail, validateEmail } from '@/services/authValidation'

const email = ref('')
const loading = ref(false)
const captchaLoading = ref(false)
const captcha = ref({ token: '', label: 'Xác minh bạn là con người', verified: false })
const showCaptcha = ref(false)
const router = useRouter()

// Reset captcha if email changes
watch(email, () => {
  showCaptcha.value = false
  captcha.value.verified = false
})

const modal = ref({
  show: false,
  type: 'error',
  title: '',
  message: '',
  onConfirm: null
})

const showModal = (type, title, message, onConfirm = null) => {
  modal.value = { show: true, type, title, message, onConfirm }
}

const closeModal = () => {
  const cb = modal.value.onConfirm
  modal.value.show = false
  modal.value.onConfirm = null
  if (cb) cb()
}

const loadCaptcha = async () => {
  captchaLoading.value = true
  try {
    const res = await api.get('/forgot-password/captcha')
    captcha.value = {
      token: res.data.token || '',
      label: res.data.label || 'Xác minh bạn là con người',
      verified: false
    }
  } catch (err) {
    console.error('Không tải được captcha:', err)
    captcha.value = { token: '', label: 'Không tải được captcha', verified: false }
  } finally {
    captchaLoading.value = false
  }
}

onMounted(loadCaptcha)

const verifyCaptcha = async () => {
  if (captchaLoading.value || loading.value) return
  if (!captcha.value.token) {
    await loadCaptcha()
    return
  }
  captcha.value.verified = !captcha.value.verified
  if (captcha.value.verified) {
    // Automatically submit when checked
    await handleSubmit()
  }
}

const handleSubmit = async () => {
  const emailError = validateEmail(email.value)
  if (emailError) {
    showModal('error', 'Email không hợp lệ', emailError)
    return
  }

  // If captcha box is not shown yet, show it and return
  if (!showCaptcha.value) {
    showCaptcha.value = true
    if (!captcha.value.token) {
      await loadCaptcha()
    }
    return
  }

  if (!captcha.value.token) {
    showModal('error', 'Thiếu xác minh', 'Vui lòng tải lại mã xác minh.')
    await loadCaptcha()
    return
  }

  if (!captcha.value.verified) {
    showModal('error', 'Thiếu xác minh', 'Vui lòng xác minh bạn là con người.')
    return
  }

  if (loading.value) return
  loading.value = true

  try {
    const res = await api.post('/forgot-password/send-otp', {
      email: normalizeEmail(email.value),
      captcha_token: captcha.value.token,
      captcha_verified: captcha.value.verified
    })

    showModal(
      'success',
      'Gửi OTP thành công!',
      formatAuthMessage(res.data.message, `Mã OTP đã được gửi đến ${email.value}. Vui lòng kiểm tra email.`),
      () => {
        router.push({
          name: 'otp-verify',
          query: { email: normalizeEmail(email.value) }
        })
      }
    )
  } catch (err) {
    console.log(err)

    const errorMsg =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.errors?.captcha_verified?.[0] ||
      err.response?.data?.message ||
      'Không gửi được OTP'

    showModal('error', 'Lỗi', formatAuthMessage(errorMsg, 'Không gửi được OTP. Vui lòng thử lại.'))
    // Reset captcha on failure
    captcha.value.verified = false
    await loadCaptcha()
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page">

    <!-- LEFT COLUMN (Branding & Workspace Laptop Image) -->
    <div class="left-col">
      <div class="left-content">
        <div class="brand-header">
          <span class="brand-title">NEXTGEN</span>
          <span class="brand-slogan">Chinh Phục Tầm Cao Mới</span>
        </div>
        <p class="brand-description">
          Hành trình chinh phục hiệu năng đỉnh cao bắt đầu từ đây. Quản lý không gian làm việc, cấu hình thiết bị và nhận các đặc quyền dễ dàng.
        </p>
        <div class="laptop-img-wrapper">
          <img class="laptop-img" src="/login_laptop_mockup.png" alt="NextGen Laptop Workspace" />
        </div>
        <div class="highlight-pills">
          <span class="pill">Bảo mật tối đa</span>
          <span class="pill">Đồng bộ đám mây</span>
          <span class="pill">Đặc quyền VIP</span>
        </div>
      </div>
    </div>

    <!-- RIGHT COLUMN (Form fields) -->
    <div class="right-col">
      <div class="form-wrapper">
        
        <div class="header-box">
          <h2 class="form-title">Quên mật khẩu?</h2>
          <p class="form-desc">
            Đừng lo lắng, hãy nhập email của bạn để chúng tôi gửi hướng dẫn đặt lại mật khẩu.
          </p>
        </div>

        <!-- FORM FIELDS -->
        <form class="form-container" @submit.prevent="handleSubmit">
          <!-- EMAIL -->
          <div class="form-group">
            <label class="input-label">Địa chỉ Email</label>
            <div class="input-wrapper">
              <span class="input-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="3" />
                  <path d="M2 7l10 7 10-7" />
                </svg>
              </span>
              <input v-model="email" type="email" placeholder="example@gmail.com" />
            </div>
          </div>

          <!-- CAPTCHA WITH TRANSITION -->
          <Transition name="fade-slide">
            <div class="captcha-box" v-if="showCaptcha">
              <button
                type="button"
                class="captcha-check"
                :class="{ checked: captcha.verified }"
                @click="verifyCaptcha"
                :disabled="captchaLoading || loading"
                aria-label="Xác minh bạn là con người"
              >
                <span v-if="captcha.verified">✓</span>
              </button>
              <span class="captcha-title">{{ captcha.label }}</span>
              <div class="captcha-brand">
                <svg viewBox="0 0 64 40" aria-hidden="true">
                  <path fill="#f97316" d="M44 28H20a9 9 0 0 1 8.6-11.6A13 13 0 0 1 53.8 20H55a6 6 0 0 1 0 12h-9.8c1-1.2 1.5-2.6 1.5-4Z"/>
                </svg>
                <strong>CLOUDFLARE</strong>
                <small>Quyền riêng tư · Giúp đỡ</small>
                <button type="button" class="captcha-refresh" @click="loadCaptcha" :disabled="captchaLoading || loading">
                  ↻
                </button>
              </div>
            </div>
          </Transition>

          <!-- SUBMIT BUTTON -->
          <button type="submit" class="submit-btn" :disabled="loading">
            <span class="btn-text">
              {{ loading ? 'Đang gửi...' : 'Gửi yêu cầu khôi phục' }}
            </span>
            <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>
        </form>

        <!-- BACK TO LOGIN -->
        <a class="back-link" @click="router.push('/dang-nhap')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12" />
            <polyline points="12 19 5 12 12 5" />
          </svg>
          Quay lại trang Đăng nhập
        </a>

        <!-- OR DIVIDER -->
        <div class="or-divider">
          <span class="divider-line"></span>
          <span class="divider-text">HOẶC</span>
          <span class="divider-line"></span>
        </div>

        <!-- FOOTER REGISTER LINK -->
        <p class="footer-register">
          Bạn chưa có tài khoản? <span class="register-link" @click="router.push('/dang-ky')">Tạo tài khoản mới</span>
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
          <button class="modal-btn" :class="modal.type" @click="closeModal">
            {{ modal.type === 'success' ? 'Nhập mã OTP' : 'Đã hiểu' }}
          </button>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 42% 58%;
  font-family: 'Inter', sans-serif;
  background: #ffffff;
  overflow: hidden;
}

/* LEFT COLUMN */
.left-col {
  background-color: #0d1b2e;
  color: #ffffff;
  padding: 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
}

.left-content {
  width: 100%;
  max-width: 360px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  height: 100%;
  justify-content: space-between;
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
  margin-top: 16px;
  margin-bottom: 24px;
}

.laptop-img-wrapper {
  width: 100%;
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.laptop-img {
  width: 100%;
  max-width: 320px;
  height: auto;
  border-radius: 12px;
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.1);
  object-fit: cover;
}

.highlight-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: auto;
}

.pill {
  background: rgba(59, 130, 246, 0.12);
  color: #93c5fd;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 500;
  border: 1px solid rgba(59, 130, 246, 0.25);
  backdrop-filter: blur(4px);
}

/* RIGHT COLUMN */
.right-col {
  padding: 40px;
  background: #ffffff;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.form-wrapper {
  width: 100%;
  max-width: 380px;
  margin: 0 auto;
}

.header-box {
  margin-bottom: 24px;
}

.form-title {
  font-size: 24px;
  font-weight: 700;
  color: #0d1b2e;
  margin: 0 0 6px 0;
}

.form-desc {
  font-size: 13px;
  color: #6b7280;
  line-height: 1.5;
  margin: 0;
}

.form-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
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

/* SUBMIT BUTTON */
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

/* BACK LINK */
.back-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 13.5px;
  font-weight: 500;
  color: #4b5563;
  cursor: pointer;
  text-decoration: none;
  transition: color 0.15s ease;
  margin-top: 16px;
}

.back-link:hover {
  color: #2563eb;
}

.back-link svg {
  width: 14px;
  height: 14px;
}

/* DIVIDER */
.or-divider {
  display: flex;
  align-items: center;
  margin: 20px 0;
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
}

/* REGISTER FOOTER */
.footer-register {
  text-align: center;
  font-size: 12.5px;
  color: #4b5563;
  margin: 0;
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

/* CAPTCHA */
.captcha-box {
  min-height: 52px;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 10px;
  background: #fafafa;
  border: 1px solid #d1d5db;
  border-radius: 3px;
  padding: 8px 12px;
  text-align: left;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}

.captcha-check {
  width: 22px;
  height: 22px;
  border: 2px solid #4b5563;
  border-radius: 3px;
  background: #ffffff;
  color: #ffffff;
  display: grid;
  place-items: center;
  font-size: 13px;
  font-weight: 900;
  cursor: pointer;
  transition: background 0.18s ease, border-color 0.18s ease;
}

.captcha-check.checked {
  background: #2563eb;
  border-color: #2563eb;
}

.captcha-check:disabled {
  cursor: wait;
  opacity: 0.65;
}

.captcha-title {
  color: #111827;
  font-size: 13px;
  font-weight: 500;
}

.captcha-brand {
  width: 80px;
  display: grid;
  justify-items: center;
  gap: 1px;
  color: #111827;
}

.captcha-brand svg {
  width: 32px;
  height: 18px;
}

.captcha-brand strong {
  color: #111827;
  font-size: 8px;
  letter-spacing: 1.2px;
  line-height: 1;
}

.captcha-brand small {
  color: #374151;
  font-size: 7px;
  text-decoration: underline;
  white-space: nowrap;
}

.captcha-refresh {
  border: none;
  background: transparent;
  color: #64748b;
  padding: 1px 4px;
  font-size: 10px;
  cursor: pointer;
}

/* TRANSITIONS */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.25s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
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

/* RESPONSIVE LAYOUT */
@media (max-width: 768px) {
  .page {
    grid-template-columns: 1fr;
  }
  .left-col { display: none; }
}

@media (max-width: 480px) {
  .right-col { padding: 24px 20px; }
}
</style>
