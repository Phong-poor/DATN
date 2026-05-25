<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { formatAuthMessage } from '@/services/authMessages'

const email = ref('')
const loading = ref(false)
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

const handleSubmit = async () => {
  if (!email.value.trim()) {
    showModal('error', 'Thiếu thông tin', 'Vui lòng nhập địa chỉ email của bạn.')
    return
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email.value)) {
    showModal('error', 'Email không hợp lệ', 'Vui lòng nhập đúng định dạng email.')
    return
  }

  if (loading.value) return
  loading.value = true

  try {
    const res = await api.post('/forgot-password/send-otp', {
      email: email.value.trim()
    })

    showModal(
      'success',
      'Gửi OTP thành công!',
      formatAuthMessage(res.data.message, `Mã OTP đã được gửi đến ${email.value}. Vui lòng kiểm tra email.`),
      () => {
        router.push({
          name: 'otp-verify',
          query: { email: email.value.trim() }
        })
      }
    )
  } catch (err) {
    console.log(err)

    const errorMsg =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      'Không gửi được OTP'

    showModal('error', 'Lỗi', formatAuthMessage(errorMsg, 'Không gửi được OTP. Vui lòng thử lại.'))
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
  color: #1e293b;
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
  color: #1e293b;
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
