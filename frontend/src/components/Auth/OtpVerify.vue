<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const email = ref(route.query.email || '')
const otp = ref(['', '', '', '', '', ''])
const inputs = ref([])
const hasError = ref(false)
const errorMessage = ref('')
const isLoading = ref(false)
const isResending = ref(false)
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

onMounted(() => {
  if (!email.value) {
    alert('Thiếu email, vui lòng quay lại trang quên mật khẩu.')
    router.push('/forgot-password')
    return
  }

  startTimer()
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
    await axios.post('http://127.0.0.1:8000/api/forgot-password/verify-otp', {
      email: email.value,
      otp: otpCode
    })

    router.push({
      path: '/reset-password',
      query: {
        email: email.value,
        otp: otpCode
      }
    })
  } catch (err) {
    console.log(err)
    hasError.value = true
    errorMessage.value =
      err.response?.data?.errors?.otp?.[0] ||
      err.response?.data?.message ||
      'Mã OTP không đúng hoặc đã hết hạn.'

    clearOtp()
    focusInput(0)
  } finally {
    isLoading.value = false
  }
}

async function resend() {
  if (countdown.value > 0 || isResending.value) return

  isResending.value = true
  hasError.value = false
  errorMessage.value = ''

  try {
    await axios.post('http://127.0.0.1:8000/api/forgot-password/send-otp', {
      email: email.value
    })

    clearOtp()
    focusInput(0)
    startTimer()
    alert('Đã gửi lại mã OTP.')
  } catch (err) {
    console.log(err)
    alert(
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      'Không thể gửi lại OTP.'
    )
  } finally {
    isResending.value = false
  }
}
</script>

<template>
  <div class="page">
    <div class="otp-card">

      <!-- Icon -->
      <div class="card-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
          <polyline points="22,6 12,13 2,6" />
        </svg>
      </div>

      <h1 class="card-title">Xác thực tài khoản</h1>
      <p class="card-desc">
        Vui lòng nhập mã OTP 6 chữ số đã được gửi đến<br />
        <strong>{{ email }}</strong>
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
        <span v-if="!isLoading">Xác Nhận OTP</span>
        <span v-else class="spinner-wrap">
          <span class="spin"></span>
          Đang xác thực...
        </span>
      </button>

      <!-- Resend -->
      <div class="resend-row">
        <span class="resend-label">Không nhận được mã?</span>
        <button class="resend-btn" @click="resend" :disabled="countdown > 0 || isResending">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10" />
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
          </svg>
          {{ isResending ? 'Đang gửi...' : 'Gửi lại mã' }}
          <span v-if="countdown > 0" class="countdown">{{ formatCountdown }}</span>
        </button>
      </div>

    </div>
  </div>
</template>


<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap');

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.page {
  min-height: 100vh;
  background: #f8faff;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  font-family: 'Be Vietnam Pro', sans-serif;
}

.otp-card {
  background: #fff;
  border-radius: 24px;
  padding: 48px 44px 40px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 8px 40px rgba(30, 60, 180, 0.07), 0 2px 8px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.card-icon {
  width: 56px;
  height: 56px;
  background: #eef2ff;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 22px;
}

.card-icon svg {
  width: 26px;
  height: 26px;
  stroke: #4f46e5;
}

.card-title {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 10px;
  letter-spacing: -0.3px;
}

.card-desc {
  font-size: 13.5px;
  color: #64748b;
  line-height: 1.65;
  margin-bottom: 32px;
}

.otp-row {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
}

.otp-input {
  width: 52px;
  height: 58px;
  border-radius: 14px;
  border: 2px solid #e2e8f0;
  background: #f8faff;
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  text-align: center;
  outline: none;
  transition: all 0.2s;
  font-family: inherit;
  caret-color: #4f46e5;
}

.otp-input:focus {
  border-color: #4f46e5;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
  transform: translateY(-2px);
}

.otp-input.filled {
  border-color: #c7d2fe;
  background: #eef2ff;
  color: #4f46e5;
}

.otp-input.error {
  border-color: #ef4444;
  background: #fef2f2;
  color: #ef4444;
  animation: shake 0.4s ease;
}

@keyframes shake {

  0%,
  100% {
    transform: translateX(0);
  }

  20% {
    transform: translateX(-6px);
  }

  40% {
    transform: translateX(6px);
  }

  60% {
    transform: translateX(-4px);
  }

  80% {
    transform: translateX(4px);
  }
}

.error-msg {
  font-size: 12.5px;
  color: #ef4444;
  margin-bottom: 16px;
  font-weight: 500;
}

.btn-verify {
  width: 100%;
  padding: 14px;
  border-radius: 14px;
  border: none;
  background: linear-gradient(135deg, #4f46e5, #6366f1);
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  margin-bottom: 20px;
  box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
}

.btn-verify:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(79, 70, 229, 0.45);
}

.btn-verify:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
}

.btn-verify.loading {
  opacity: 0.85;
  cursor: wait;
}

.spinner-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.spin {
  width: 16px;
  height: 16px;
  border: 2.5px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.resend-row {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

.resend-label {
  font-size: 12.5px;
  color: #94a3b8;
}

.resend-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background: none;
  border: none;
  color: #4f46e5;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  padding: 4px 8px;
  border-radius: 8px;
  transition: background 0.15s;
}

.resend-btn svg {
  width: 13px;
  height: 13px;
}

.resend-btn:hover:not(:disabled) {
  background: #eef2ff;
}

.resend-btn:disabled {
  color: #94a3b8;
  cursor: default;
}

.countdown {
  background: #eef2ff;
  color: #4f46e5;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
  font-family: monospace;
}

@media (max-width: 480px) {
  .otp-card {
    padding: 36px 20px 32px;
  }

  .otp-input {
    width: 44px;
    height: 52px;
    font-size: 20px;
  }
}
</style>