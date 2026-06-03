<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { formatAuthMessage } from '@/services/authMessages'
import { normalizeEmail, validateEmail } from '@/services/authValidation'

const email = ref('')
const loading = ref(false)
const captchaLoading = ref(false)
const captcha = ref({ token: '', label: 'Xác minh bạn là con người', verified: false })
const router = useRouter()

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
  if (captchaLoading.value) return
  if (!captcha.value.token) {
    await loadCaptcha()
    return
  }
  captcha.value.verified = !captcha.value.verified
}

const handleSubmit = async () => {
  const emailError = validateEmail(email.value)
  if (emailError) {
    showModal('error', 'Email không hợp lệ', emailError)
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
    await loadCaptcha()
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page">

    <div class="card">

      <h3 class="brand">NextGen Laptop</h3>

      <div class="icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2563eb"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
          <path d="M7 11V7a5 5 0 0 1 10 0v4" />
        </svg>
      </div>

      <h2>Quên mật khẩu?</h2>
      <p class="desc">
        Nhập email của bạn để nhận hướng dẫn khôi phục mật khẩu
      </p>

      <!-- INPUT -->
      <div class="input-box">
        <span>@</span>
        <input v-model="email" placeholder="example@vinatech.com" @keyup.enter="handleSubmit" />
      </div>

      <div class="captcha-box">
        <button
          type="button"
          class="captcha-check"
          :class="{ checked: captcha.verified }"
          @click="verifyCaptcha"
          :disabled="captchaLoading"
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
          <button type="button" class="captcha-refresh" @click="loadCaptcha" :disabled="captchaLoading">
            ↻
          </button>
        </div>
      </div>

      <!-- BUTTON -->
      <button class="btn" @click="handleSubmit" :disabled="loading">
        {{ loading ? 'Đang gửi...' : 'Gửi yêu cầu →' }}
      </button>

      <div class="divider"></div>

      <!-- BACK -->
      <p class="back" @click="router.push('/login')">
        ← Quay lại đăng nhập
      </p>

      <p class="register-link">
        Chưa có tài khoản?
        <a @click="router.push('/register')">Đăng ký ngay</a>
      </p>

    </div>

    <p class="footer">
      © 2026 VinaTech Premium. Hệ thống bảo mật 2026.
    </p>

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
.page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #eef2ff, #f1f5f9);
  font-family: 'Inter', sans-serif;
}

.card {
  width: 380px;
  padding: 40px;
  border-radius: 24px;
  background: white;
  text-align: center;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.08);
}

.brand {
  color: #2563eb;
  margin-bottom: 10px;
}

.icon {
  width: 56px;
  height: 56px;
  margin: 10px auto 16px;
  background: #e0ecff;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

h2 {
  font-size: 22px;
  margin-bottom: 8px;
  color: #0f172a;
}

.desc {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 24px;
  line-height: 1.6;
}

.input-box {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f1f5f9;
  border-radius: 10px;
  padding: 10px;
  margin-bottom: 15px;
}

.input-box span {
  color: #64748b;
}

.input-box input {
  border: none;
  background: transparent;
  outline: none;
  flex: 1;
  font-size: 14px;
}

.captcha-box {
  min-height: 76px;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 12px;
  background: #fafafa;
  border: 1px solid #d1d5db;
  border-radius: 3px;
  padding: 12px;
  margin-bottom: 15px;
  text-align: left;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}

.captcha-check {
  width: 30px;
  height: 30px;
  border: 2px solid #4b5563;
  border-radius: 3px;
  background: #ffffff;
  color: #ffffff;
  display: grid;
  place-items: center;
  font-size: 19px;
  font-weight: 900;
  cursor: pointer;
  transition: background 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
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
  font-size: 15px;
  font-weight: 500;
}

.captcha-brand {
  width: 92px;
  display: grid;
  justify-items: center;
  gap: 1px;
  color: #111827;
}

.captcha-brand svg {
  width: 42px;
  height: 24px;
}

.captcha-brand strong {
  color: #111827;
  font-size: 10px;
  letter-spacing: 1.4px;
  line-height: 1;
}

.captcha-brand small {
  color: #374151;
  font-size: 8px;
  text-decoration: underline;
  white-space: nowrap;
}

.captcha-refresh {
  border: none;
  background: transparent;
  color: #64748b;
  padding: 1px 4px;
  font-size: 12px;
  cursor: pointer;
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
  font-size: 14px;
  transition: opacity 0.2s;
}

.btn:hover {
  opacity: 0.9;
}

.divider {
  height: 1px;
  background: #e5e7eb;
  margin: 20px 0;
}

.back {
  font-size: 13px;
  color: #2563eb;
  cursor: pointer;
  font-weight: 600;
  transition: opacity 0.2s;
}

.back:hover {
  opacity: 0.7;
}

.register-link {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 12px;
}

.register-link a {
  color: #2563eb;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
}

.register-link a:hover {
  text-decoration: underline;
}

.footer {
  margin-top: 20px;
  font-size: 11px;
  color: #94a3b8;
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

@media (max-width: 480px) {
  .card {
    width: 100%;
    max-width: 340px;
    padding: 24px 20px;
  }
  .modal-card {
    width: min(320px, 90%);
    padding: 24px 20px;
  }
}
</style>
