<script setup>
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../../services/api'

const router = useRouter()
const route = useRoute()

// Lấy trạng thái từ URL
const status = route.query.status
const orderId = route.query.order_id

onMounted(async () => {
    // Nếu thành công thì gửi mail
    if (orderId && status === 'success') {
        try {
            await api.post(`/orders/send-email/${orderId}`)
        } catch (error) {
            // Error handling if needed
        }
    }
})
</script>

<template>
  
  <div class="thank-you-page">
    <div class="container">
      <div class="card">
        <div class="icon-box">
          <svg v-if="status !== 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon success">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon error">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
        </div>

        <h1 v-if="status === 'error'">Thanh toán không thành công!</h1>
        <h1 v-else>Cảm ơn bạn đã đặt hàng!</h1>

        <p v-if="status === 'error'" class="message">
          Đã có lỗi xảy ra trong quá trình thanh toán. Đơn hàng của bạn đã được ghi nhận ở trạng thái chờ. 
          Vui lòng kiểm tra lại trong mục Đơn hàng của tôi.
        </p>
        <p v-else class="message">
          Đơn hàng của bạn đã được xác nhận thành công. Chúng tôi sẽ sớm liên hệ để giao hàng cho bạn. 
          Mọi thông tin chi tiết đã được gửi vào email của bạn.
        </p>

        <div class="actions">
          <button @click="router.push({ path: '/trang-ca-nhan', query: { tab: 'orders' } })" class="btn secondary">Xem đơn hàng</button>
          <button @click="router.push('/')" class="btn primary">Tiếp tục mua sắm</button>
        </div>
      </div>
    </div>
  </div>

</template>

<style scoped>

.thank-you-page {
  background:
    radial-gradient(circle at 18% 18%, rgba(37, 99, 235, 0.08), transparent 30%),
    radial-gradient(circle at 82% 18%, rgba(14, 165, 233, 0.08), transparent 32%),
    linear-gradient(180deg, #f8fafc 0%, #eef5ff 100%);
  min-height: calc(100vh - 210px);
  display: flex;
  align-items: center;
  padding: 72px 20px 88px;
}

.container {
  max-width: 720px;
  margin: auto;
  width: 100%;
}

.card {
  background: rgba(255, 255, 255, 0.96);
  padding: 56px 48px 48px;
  border-radius: 18px;
  border: 1px solid rgba(191, 219, 254, 0.78);
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.10);
  text-align: center;
  position: relative;
  overflow: hidden;
}

.card::before {
  content: '';
  position: absolute;
  inset: 0 0 auto;
  height: 5px;
  background: linear-gradient(90deg, #0ea5e9, #2563eb, #22c55e);
}

.icon-box {
  width: 88px;
  height: 88px;
  margin: 0 auto 24px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  box-shadow: 0 18px 36px rgba(37, 99, 235, 0.14);
}

.icon {
  width: 56px;
  height: 56px;
}

.icon.success {
  color: #2563eb;
}

.icon.error {
  color: #ef4444;
}

h1 {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 16px;
  letter-spacing: 0;
}

.message {
  color: #475569;
  line-height: 1.75;
  margin: 0 auto 38px;
  max-width: 560px;
  font-size: 16px;
}

.actions {
  display: flex;
  gap: 16px;
  justify-content: center;
}

.btn {
  min-width: 170px;
  padding: 14px 24px;
  border-radius: 12px;
  font-weight: 800;
  font-size: 15px;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease;
  border: 1px solid transparent;
}

.btn.primary {
  background: linear-gradient(135deg, #2563eb, #0ea5e9);
  color: #ffffff;
  box-shadow: 0 14px 28px rgba(37, 99, 235, 0.24);
}

.btn.primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 18px 34px rgba(37, 99, 235, 0.30);
}

.btn.secondary {
  background: #ffffff;
  color: #1d4ed8;
  border-color: #bfdbfe;
}

.btn.secondary:hover {
  background: #eff6ff;
  border-color: #93c5fd;
  transform: translateY(-2px);
}

@media (max-width: 480px) {
  .thank-you-page {
    padding: 44px 16px 64px;
  }

  .card {
    padding: 44px 22px 28px;
    border-radius: 16px;
  }

  h1 {
    font-size: 26px;
  }

  .actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>
