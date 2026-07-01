<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import swal from '@/services/swal'
import { formatAuthMessage } from '@/services/authMessages'

const route = useRoute()
const router = useRouter()

const email = ref(route.query.email || '')
const displayEmail = computed(() => {
  return email.value.replace('@example.com', '@gmail.com')
})
const otp = ref(['', '', '', '', '', ''])
const inputs = ref([])
const hasError = ref(false)
const errorMessage = ref('')
const isLoading = ref(false)
const isResending = ref(false)
const captchaLoading = ref(false)
const captcha = ref({ token: '', label: 'Xác minh bạn là con người', verified: false })
const countdown = ref(60)
let timer = null

const formatCountdown = computed(() => {
  const m = Math.floor(countdown.value / 60).toString().padStart(2, '0')
  const s = (countdown.value % 60).toString().padStart(2, '0')
  return `${m}:${s}`
})

function startTimer() {
  countdown.value = 60
  clearInterval(timer)
  timer = setInterval(() => {
    if (countdown.value > 0) countdown.value--
    else clearInterval(timer)
  }, 1000)
}

function clearOtp() {
  otp.value = ['', '', '', '', '', '']
}

function focusInput(index = 0) {
  setTimeout(() => {
    inputs.value[index]?.focus()
  }, 50)
}

async function loadCaptcha() {
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

async function verifyCaptcha() {
  if (captchaLoading.value) return
  if (!captcha.value.token) {
    await loadCaptcha()
    return
  }
  captcha.value.verified = !captcha.value.verified
}

onMounted(() => {
  if (!email.value) {
    swal.error('Lỗi', 'Thiếu email, vui lòng quay lại trang quên mật khẩu.')
    router.push('/quen-mat-khau')
    return
  }

  startTimer()
  loadCaptcha()
  focusInput(0)
})

onUnmounted(() => {
  clearInterval(timer)
})

function handleInput(e, i) {
  const val = e.target.value.replace(/\D/g, '').slice(-1)
  otp.value[i] = val
  e.target.value = val
  hasError.value = false
  errorMessage.value = ''

  if (val && i < 5) {
    inputs.value[i + 1]?.focus()
  }
}

function handleKeydown(e, i) {
  if (e.key === 'Backspace') {
    hasError.value = false
    errorMessage.value = ''

    if (otp.value[i]) {
      otp.value[i] = ''
      return
    }

    if (i > 0) {
      otp.value[i - 1] = ''
      inputs.value[i - 1]?.focus()
    }
  }

  if (e.key === 'ArrowLeft' && i > 0) {
    inputs.value[i - 1]?.focus()
  }

  if (e.key === 'ArrowRight' && i < 5) {
    inputs.value[i + 1]?.focus()
  }

  if (e.key === 'Enter') {
    if (otp.value.join('').length === 6) {
      verify()
    }
  }
}

function handlePaste(e) {
  e.preventDefault()
  const text = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
  clearOtp()
  text.split('').forEach((c, i) => {
    otp.value[i] = c
  })
  hasError.value = false
  errorMessage.value = ''

  const nextIndex = text.length >= 6 ? 5 : Math.max(text.length - 1, 0)
  inputs.value[nextIndex]?.focus()
}

async function verify() {
  const otpCode = otp.value.join('')

  if (otpCode.length < 6) return
  if (isLoading.value) return

  isLoading.value = true
  hasError.value = false
  errorMessage.value = ''

  try {
    await api.post('/forgot-password/verify-otp', {
      email: email.value,
      otp: otpCode
    })

    router.push({
      path: '/dat-lai-mat-khau',
      query: {
        email: email.value,
        otp: otpCode
      }
    })
  } catch (err) {
    console.log(err)
    hasError.value = true
    errorMessage.value =
      formatAuthMessage(
        err.response?.data?.errors?.otp?.[0] ||
        err.response?.data?.message ||
        'Mã OTP không đúng hoặc đã hết hạn.',
        'Mã OTP không đúng hoặc đã hết hạn.'
      )

    clearOtp()
    focusInput(0)
  } finally {
    isLoading.value = false
  }
}

async function resend() {
  if (countdown.value > 0 || isResending.value) return

  if (!captcha.value.token || !captcha.value.verified) {
    swal.error('Thiếu xác minh', 'Vui lòng xác minh bạn là con người trước khi gửi lại mã OTP.')
    return
  }

  isResending.value = true
  hasError.value = false
  errorMessage.value = ''

  try {
    await api.post('/forgot-password/send-otp', {
      email: email.value,
      captcha_token: captcha.value.token,
      captcha_verified: captcha.value.verified
    })

    clearOtp()
    focusInput(0)
    startTimer()
    await loadCaptcha()
    swal.toast('Đã gửi lại mã OTP.')
  } catch (err) {
    console.log(err)
    swal.error('Lỗi',
      formatAuthMessage(
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.errors?.captcha_verified?.[0] ||
        err.response?.data?.message ||
        'Không thể gửi lại OTP.',
        'Không thể gửi lại OTP.'
      )
    )
    await loadCaptcha()
  } finally {
    isResending.value = false
  }
}
</script>

<template>
  <div class="page">
    
    <!-- LEFT COLUMN (OTP Entry) -->
    <div class="left-col">
      <div class="left-content-wrapper">
        <h1 class="card-title">Xác thực mã OTP</h1>
        <p class="card-desc">
          Vui lòng nhập mã OTP đã được gửi đến email của bạn.
          <br />
          <span class="email-text">{{ displayEmail }}</span>
        </p>

        <!-- OTP inputs -->
        <div class="otp-row">
          <input v-for="(_, i) in 6" :key="i" :ref="el => { if (el) inputs[i] = el }" class="otp-input"
            :class="{ filled: otp[i], error: hasError }" type="text" inputmode="numeric" maxlength="1" :value="otp[i]"
            @input="handleInput($event, i)" @keydown="handleKeydown($event, i)" @paste="handlePaste($event)"
            @focus="$event.target.select()" />
        </div>

        <p class="error-msg" v-if="hasError">{{ errorMessage }}</p>

        <button class="btn-verify" :class="{ loading: isLoading }" @click="verify"
          :disabled="isLoading || otp.join('').length < 6">
          <span v-if="!isLoading">Xác nhận</span>
          <span v-else class="spinner-wrap">
            <span class="spin"></span>
            Đang xác thực...
          </span>
        </button>

        <!-- Resend -->
        <div class="resend-row">
          <span class="resend-label">Không nhận được mã?</span>
          
          <div v-if="countdown <= 0" class="captcha-resend-box">
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

          <button class="resend-btn" @click="resend" :disabled="countdown > 0 || isResending">
            {{ isResending ? 'Đang gửi...' : 'Gửi lại mã' }}
            <span v-if="countdown > 0" class="countdown">({{ formatCountdown }})</span>
          </button>
        </div>

        <!-- Back to Login -->
        <a class="back-to-login" @click="router.push('/dang-nhap')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12" />
            <polyline points="12 19 5 12 12 5" />
          </svg>
          Quay lại trang Đăng nhập
        </a>
      </div>
    </div>

    <!-- RIGHT COLUMN (Mockup Image & Slogans) -->
    <div class="right-col">
      <div class="right-content-wrapper">
        <h2 class="mockup-title">Chinh Phục Tầm Cao Mới</h2>
        <p class="mockup-desc">Tham gia vào thế giới hiệu năng vượt trội cùng hệ sinh thái công nghệ tiên tiến của NextGen.</p>
        <div class="laptop-img-wrapper">
          <img class="laptop-img" src="/login_laptop_mockup.png" alt="NextGen Laptop Workspace" />
        </div>
      </div>
    </div>

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
  grid-template-columns: 1fr 1fr;
  font-family: 'Inter', sans-serif;
  background: #ffffff;
  overflow: hidden;
}

/* LEFT COLUMN */
.left-col {
  background-color: #ffffff;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 40px;
}

.left-content-wrapper {
  width: 100%;
  max-width: 380px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.card-title {
  font-size: 28px;
  font-weight: 800;
  color: #0d1b2e;
  margin-bottom: 8px;
  letter-spacing: -0.5px;
  text-align: center;
}

.card-desc {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.5;
  margin-bottom: 28px;
  text-align: center;
}

.email-text {
  font-weight: 600;
  color: #374151;
  display: inline-block;
  margin-top: 4px;
}

.otp-row {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
  justify-content: center;
  width: 100%;
}

.otp-input {
  width: 52px;
  height: 52px;
  border-radius: 8px;
  border: 1px solid #dbeafe;
  background: #f8faff;
  font-size: 22px;
  font-weight: 700;
  color: #0d1b2e;
  text-align: center;
  outline: none;
  transition: all 0.2s ease;
  font-family: inherit;
}

.otp-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
}

.otp-input.filled {
  border-color: #2563eb;
  background: #eff6ff;
}

.otp-input.error {
  border-color: #ef4444;
  background: #fef2f2;
  color: #ef4444;
  animation: shake 0.4s ease;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20% { transform: translateX(-4px); }
  40% { transform: translateX(4px); }
  60% { transform: translateX(-3px); }
  80% { transform: translateX(3px); }
}

.error-msg {
  font-size: 12.5px;
  color: #ef4444;
  margin-bottom: 16px;
  font-weight: 500;
  text-align: center;
}

.btn-verify {
  width: 100%;
  height: 44px;
  border-radius: 8px;
  border: none;
  background-color: #1e40af;
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-verify:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.btn-verify:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.btn-verify.loading {
  opacity: 0.85;
  cursor: wait;
}

.spinner-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.spin {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* RESEND */
.resend-row {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  width: 100%;
  margin-bottom: 32px;
}

.resend-label {
  font-size: 13px;
  color: #6b7280;
}

.resend-btn {
  background: none;
  border: none;
  color: #2563eb;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  padding: 2px 6px;
  transition: color 0.15s;
  text-decoration: underline;
}

.resend-btn:hover:not(:disabled) {
  color: #1d4ed8;
}

.resend-btn:disabled {
  color: #9ca3af;
  cursor: not-allowed;
  text-decoration: none;
}

.captcha-resend-box {
  width: 100%;
  min-height: 52px;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 10px;
  background: #fafafa;
  border: 1px solid #d1d5db;
  border-radius: 3px;
  padding: 8px 12px;
  margin-top: 4px;
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

/* BACK TO LOGIN */
.back-to-login {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #4b5563;
  font-size: 13.5px;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  transition: color 0.15s ease;
}

.back-to-login:hover {
  color: #2563eb;
}

.back-to-login svg {
  width: 14px;
  height: 14px;
}

/* RIGHT COLUMN */
.right-col {
  background-color: #0d1b2e;
  color: #ffffff;
  padding: 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.right-content-wrapper {
  width: 100%;
  max-width: 520px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.mockup-title {
  font-size: 36px;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 16px;
  color: #ffffff;
  letter-spacing: -0.5px;
}

.mockup-desc {
  font-size: 14.5px;
  color: #9ca3af;
  line-height: 1.6;
  max-width: 420px;
  margin-bottom: 32px;
}

.laptop-img-wrapper {
  width: 100%;
  display: flex;
  justify-content: center;
}

.laptop-img {
  width: 100%;
  max-width: 420px;
  height: auto;
  border-radius: 12px;
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.1);
  object-fit: cover;
}

/* RESPONSIVE LAYOUT */
@media (max-width: 1024px) {
  .page {
    grid-template-columns: 1fr;
  }
  
  .right-col {
    display: none;
  }
  
  .left-col {
    padding: 30px 20px;
  }
}

@media (max-width: 480px) {
  .left-col {
    padding: 24px 16px;
  }
  
  .otp-input {
    width: 44px;
    height: 44px;
    font-size: 18px;
  }
  
  .otp-row {
    gap: 8px;
  }
}
</style>
