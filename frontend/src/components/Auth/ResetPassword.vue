<script setup>
import { ref, computed } from 'vue'
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import swal from '@/services/swal'

const route = useRoute()
const router = useRouter()

const email = route.query.email
const otp = route.query.otp

onMounted(() => {
  if (!email || !otp) {
    swal.error('Lỗi xác thực', 'Thiếu thông tin xác thực. Vui lòng thực hiện lại.')
    router.push('/forgot-password')
  }
})

const showPassword = ref(false)
const showConfirm = ref(false)
const isLoading = ref(false)

const form = ref({ password: '', confirm: '' })
const errors = ref({ password: '', confirm: '' })

const checks = computed(() => ({
  length: form.value.password.length >= 8,
  upper: /[A-Z]/.test(form.value.password),
  special: /[0-9!@#$%^&*]/.test(form.value.password),
}))

const strengthScore = computed(() => Object.values(checks.value).filter(Boolean).length)

const strengthWidth = computed(() => ['0%', '33%', '66%', '100%'][strengthScore.value])
const strengthColor = computed(() => ['#e2e8f0', '#ef4444', '#f59e0b', '#22c55e'][strengthScore.value])
const strengthText = computed(() => ['', 'Yếu', 'Trung bình', 'Mạnh'][strengthScore.value])

function validatePassword() {
  errors.value.password = ''
  if (form.value.confirm && form.value.password === form.value.confirm) {
    errors.value.confirm = ''
  }
}

function validate() {
  errors.value = { password: '', confirm: '' }
  if (!form.value.password) { errors.value.password = 'Vui lòng nhập mật khẩu mới.'; return false }
  if (strengthScore.value < 2) { errors.value.password = 'Mật khẩu chưa đủ mạnh.'; return false }
  if (!form.value.confirm) { errors.value.confirm = 'Vui lòng xác nhận mật khẩu.'; return false }
  if (form.value.password !== form.value.confirm) { errors.value.confirm = 'Mật khẩu xác nhận không khớp.'; return false }
  return true
}

async function submit() {
  if (!validate()) return
  if (isLoading.value) return

  isLoading.value = true

  try {
    const res = await axios.post('http://127.0.0.1:8000/api/forgot-password/reset-password', {
      email: email,
      otp: otp,
      password: form.value.password,
      password_confirmation: form.value.confirm
    })

    swal.success('Thành công', res.data.message || 'Đổi mật khẩu thành công!')

    router.push('/login')

  } catch (err) {
    console.log(err)

    if (err.response?.data?.errors) {
      const firstError = Object.values(err.response.data.errors)[0][0]
      swal.error('Lỗi', firstError)
    } else {
      swal.error('Lỗi', err.response?.data?.message || 'Lỗi đổi mật khẩu')
    }

  } finally {
    isLoading.value = false
  }
}
</script>



<template>
  <div class="layout">

    <!-- LEFT PANEL -->
    <div class="left-panel">
      <div class="circuit-bg">
        <svg class="circuit-svg" viewBox="0 0 400 500" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="80" cy="120" r="4" fill="rgba(255,255,255,0.2)" />
          <circle cx="200" cy="80" r="3" fill="rgba(255,255,255,0.15)" />
          <circle cx="320" cy="160" r="4" fill="rgba(255,255,255,0.2)" />
          <circle cx="140" cy="260" r="3" fill="rgba(255,255,255,0.12)" />
          <circle cx="300" cy="300" r="5" fill="rgba(255,255,255,0.18)" />
          <circle cx="60" cy="380" r="3" fill="rgba(255,255,255,0.15)" />
          <circle cx="240" cy="420" r="4" fill="rgba(255,255,255,0.2)" />
          <line x1="80" y1="120" x2="200" y2="80" stroke="rgba(255,255,255,0.1)" stroke-width="1" />
          <line x1="200" y1="80" x2="320" y2="160" stroke="rgba(255,255,255,0.1)" stroke-width="1" />
          <line x1="80" y1="120" x2="140" y2="260" stroke="rgba(255,255,255,0.08)" stroke-width="1" />
          <line x1="320" y1="160" x2="300" y2="300" stroke="rgba(255,255,255,0.1)" stroke-width="1" />
          <line x1="140" y1="260" x2="300" y2="300" stroke="rgba(255,255,255,0.08)" stroke-width="1" />
          <line x1="60" y1="380" x2="240" y2="420" stroke="rgba(255,255,255,0.1)" stroke-width="1" />
          <line x1="140" y1="260" x2="60" y2="380" stroke="rgba(255,255,255,0.07)" stroke-width="1" />
          <rect x="60" y="100" width="40" height="40" rx="4" stroke="rgba(255,255,255,0.06)" stroke-width="1"
            fill="none" />
          <rect x="180" y="60" width="40" height="40" rx="4" stroke="rgba(255,255,255,0.06)" stroke-width="1"
            fill="none" />
          <rect x="290" y="280" width="40" height="40" rx="4" stroke="rgba(255,255,255,0.06)" stroke-width="1"
            fill="none" />
          <!-- Laptop silhouette -->
          <rect x="90" y="310" width="220" height="140" rx="8" fill="rgba(255,255,255,0.04)"
            stroke="rgba(255,255,255,0.1)" stroke-width="1" />
          <rect x="100" y="320" width="200" height="120" rx="4" fill="rgba(255,255,255,0.05)" />
          <line x1="60" y1="450" x2="340" y2="450" stroke="rgba(255,255,255,0.08)" stroke-width="6"
            stroke-linecap="round" />
          <!-- Lock icon large -->
          <rect x="175" y="355" width="50" height="40" rx="4" fill="rgba(99,130,255,0.3)" stroke="rgba(255,255,255,0.2)"
            stroke-width="1" />
          <path d="M185 355 V345 Q200 330 215 345 V355" stroke="rgba(255,255,255,0.4)" stroke-width="2" fill="none"
            stroke-linecap="round" />
          <circle cx="200" cy="375" r="5" fill="rgba(255,255,255,0.5)" />
        </svg>
      </div>

      <!-- Badge -->
      <div class="badge">VINATECH PREMIUM SECURITY</div>

      <!-- Headline -->
      <div class="headline">
        <h1>Nâng tầm <span class="accent">Bảo<br />mật</span> Kỹ thuật số.</h1>
      </div>

      <!-- Desc -->
      <p class="left-desc">Trải nghiệm hệ sinh thái công nghệ hàng đầu với chuẩn bảo mật khắt khe nhất, bảo vệ dữ liệu
        của bạn trong mọi giao dịch.</p>

      <!-- Features -->
      <div class="features">
        <div class="feature-chip">
          <div class="chip-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              <circle cx="12" cy="16" r="1" fill="currentColor" />
            </svg>
          </div>
          <div>
            <p class="chip-title">Mã hóa 256-bit</p>
            <p class="chip-sub">Tiêu chuẩn quân đội</p>
          </div>
        </div>
        <div class="feature-chip">
          <div class="chip-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
              <polyline points="9 12 11 14 15 10" />
            </svg>
          </div>
          <div>
            <p class="chip-title">Xác thực đa lớp</p>
            <p class="chip-sub">Bảo mật tuyệt đối</p>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
      <div class="form-card">

        <h1 class="form-title">Đặt lại mật khẩu</h1>
        <p class="form-desc">
          Vui lòng thiết lập mật khẩu mới cho tài khoản<br />
          <strong>{{ email }}</strong>
        </p>

        <!-- New password -->
        <div class="field-group">
          <label class="field-label">Mật khẩu mới</label>
          <div class="input-wrap" :class="{ 'input-error': errors.password }">
            <input :type="showPassword ? 'text' : 'password'" v-model="form.password" placeholder="••••••••"
              @input="validatePassword" class="field-input" />
            <button class="eye-btn" type="button" @click="showPassword = !showPassword">
              <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path
                  d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                <line x1="1" y1="1" x2="23" y2="23" />
              </svg>
            </button>
          </div>
          <p class="err-msg" v-if="errors.password">{{ errors.password }}</p>
        </div>

        <!-- Confirm password -->
        <div class="field-group">
          <label class="field-label">Xác nhận mật khẩu mới</label>
          <div class="input-wrap" :class="{ 'input-error': errors.confirm }">
            <input :type="showConfirm ? 'text' : 'password'" v-model="form.confirm" placeholder="••••••••"
              class="field-input" />
            <button class="eye-btn" type="button" @click="showConfirm = !showConfirm">
              <svg v-if="!showConfirm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path
                  d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                <line x1="1" y1="1" x2="23" y2="23" />
              </svg>
            </button>
          </div>
          <p class="err-msg" v-if="errors.confirm">{{ errors.confirm }}</p>
        </div>

        <!-- Strength checker -->
        <div class="strength-section">
          <p class="strength-title">YÊU CẦU BẢO MẬT</p>
          <div class="strength-bar-row">
            <div class="strength-bar">
              <div class="strength-fill" :style="{ width: strengthWidth, background: strengthColor }"></div>
            </div>
            <span class="strength-label" :style="{ color: strengthColor }">{{ strengthText }}</span>
          </div>
          <div class="req-list">
            <div class="req-item" :class="{ 'req-ok': checks.length }">
              <span class="req-dot"></span>
              Ít nhất 8 ký tự
            </div>
            <div class="req-item" :class="{ 'req-ok': checks.upper }">
              <span class="req-dot"></span>
              Ít nhất 1 chữ hoa
            </div>
            <div class="req-item" :class="{ 'req-ok': checks.special }">
              <span class="req-dot"></span>
              Chứa ít nhất 1 số hoặc ký tự đặc biệt
            </div>
          </div>
        </div>

        <!-- Submit -->
        <button class="btn-submit" :class="{ loading: isLoading }" @click="submit" :disabled="isLoading">
          <span v-if="!isLoading">Cập nhật mật khẩu</span>
          <span v-else class="spin-wrap">
            <span class="spin"></span>
            Đang cập nhật...
          </span>
        </button>

        <!-- Back -->
        <p class="back-link">
          Quay lại trang
          <a href="" @click.prevent="router.push('/login')">Đăng nhập</a>
        </p>

      </div>
    </div>

  </div>
</template>



<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800;900&display=swap');

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Be Vietnam Pro', sans-serif;
}

/* ── LEFT PANEL ── */
.left-panel {
  width: 44%;
  background: linear-gradient(160deg, #0b1640 0%, #0d2070 35%, #1535b0 70%, #1a3ecf 100%);
  padding: 48px 44px;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}

.circuit-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.circuit-svg {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Badge */
.badge {
  position: relative;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: rgba(255, 255, 255, 0.8);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.2px;
  padding: 7px 16px;
  border-radius: 20px;
  margin-bottom: 40px;
  width: fit-content;
}

/* Headline */
.headline {
  position: relative;
  z-index: 2;
  margin-bottom: 20px;
}

.headline h1 {
  font-size: 42px;
  font-weight: 900;
  color: #fff;
  line-height: 1.15;
  letter-spacing: -0.5px;
}

.accent {
  color: #818cf8;
}

.left-desc {
  position: relative;
  z-index: 2;
  font-size: 13.5px;
  color: rgba(255, 255, 255, 0.6);
  line-height: 1.75;
  max-width: 310px;
  margin-bottom: 36px;
}

/* Features */
.features {
  position: relative;
  z-index: 2;
  display: flex;
  gap: 12px;
  margin-top: auto;
}

.feature-chip {
  flex: 1;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 14px;
  padding: 14px;
}

.chip-icon {
  width: 34px;
  height: 34px;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.chip-icon svg {
  width: 16px;
  height: 16px;
  stroke: #a5b4fc;
}

.chip-title {
  font-size: 12.5px;
  font-weight: 700;
  color: #fff;
  margin-bottom: 2px;
}

.chip-sub {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.5);
}

/* ── RIGHT PANEL ── */
.right-panel {
  flex: 1;
  background: #f8faff;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 32px;
}

.form-card {
  width: 100%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.form-title {
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 8px;
  letter-spacing: -0.3px;
}

.form-desc {
  font-size: 13.5px;
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 28px;
}

/* Fields */
.field-group {
  margin-bottom: 16px;
}

.field-label {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 7px;
}

.input-wrap {
  display: flex;
  align-items: center;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  transition: border-color .2s, box-shadow .2s;
  overflow: hidden;
}

.input-wrap:focus-within {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.input-wrap.input-error {
  border-color: #ef4444;
}

.input-wrap.input-error:focus-within {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.field-input {
  flex: 1;
  padding: 12px 14px;
  border: none;
  outline: none;
  font-size: 14px;
  color: #0f172a;
  background: transparent;
  font-family: inherit;
  letter-spacing: 1px;
}

.field-input::placeholder {
  letter-spacing: 2px;
  color: #94a3b8;
}

.eye-btn {
  width: 42px;
  background: none;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  transition: color .15s;
  padding: 0 12px 0 0;
  flex-shrink: 0;
}

.eye-btn svg {
  width: 17px;
  height: 17px;
}

.eye-btn:hover {
  color: #6366f1;
}

.err-msg {
  font-size: 11.5px;
  color: #ef4444;
  margin-top: 5px;
  font-weight: 500;
}

/* Strength */
.strength-section {
  background: #f8faff;
  border: 1px solid #e8edf5;
  border-radius: 14px;
  padding: 16px;
  margin-bottom: 24px;
}

.strength-title {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  color: #94a3b8;
  margin-bottom: 10px;
}

.strength-bar-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.strength-bar {
  flex: 1;
  height: 5px;
  background: #e2e8f0;
  border-radius: 99px;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  border-radius: 99px;
  transition: width .4s ease, background .4s ease;
}

.strength-label {
  font-size: 11.5px;
  font-weight: 700;
  width: 70px;
  text-align: right;
  transition: color .4s;
}

.req-list {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.req-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12.5px;
  color: #94a3b8;
  transition: color .2s;
}

.req-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #e2e8f0;
  flex-shrink: 0;
  transition: background .2s;
}

.req-ok {
  color: #16a34a;
}

.req-ok .req-dot {
  background: #22c55e;
}

/* Submit */
.btn-submit {
  width: 100%;
  padding: 14px;
  border-radius: 14px;
  border: none;
  background: linear-gradient(135deg, #6366f1, #818cf8);
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: all .2s;
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35);
  margin-bottom: 20px;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 26px rgba(99, 102, 241, 0.45);
}

.btn-submit:disabled {
  opacity: .6;
  cursor: not-allowed;
  transform: none;
}

.btn-submit.loading {
  opacity: .85;
  cursor: wait;
}

.spin-wrap {
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
  animation: spin .7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Back link */
.back-link {
  font-size: 13px;
  color: #94a3b8;
  text-align: center;
}

.back-link a {
  color: #6366f1;
  font-weight: 700;
  text-decoration: none;
}

.back-link a:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .layout {
    flex-direction: column;
  }

  .left-panel {
    width: 100%;
    min-height: 260px;
    padding: 32px 24px;
  }

  .headline h1 {
    font-size: 30px;
  }

  .right-panel {
    padding: 32px 20px;
  }
}
</style>