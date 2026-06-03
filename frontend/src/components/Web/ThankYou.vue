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
          <button @click="router.push('/orderspage')" class="btn secondary">Xem đơn hàng</button>
          <button @click="router.push('/')" class="btn primary">Tiếp tục mua sắm</button>
        </div>
      </div>
    </div>
  </div>

</template>

<style scoped>
.thank-you-page {
  background: #0d1b2e;
  min-height: 80vh;
  display: flex;
  align-items: center;
  padding: 40px 20px;
}

.container {
  max-width: 600px;
  margin: auto;
  width: 100%;
}

.card {
  background: #111f35;
  padding: 60px 40px;
  border-radius: 24px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.05);
  text-align: center;
}

.icon-box {
  width: 80px;
  height: 80px;
  margin: 0 auto 24px;
}

.icon {
  width: 100%;
  height: 100%;
}

.icon.success {
  color: #10b981;
}

.icon.error {
  color: #ef4444;
}

h1 {
  font-size: 28px;
  font-weight: 700;
  color: #e2e8f0;
  margin-bottom: 16px;
}

.message {
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 40px;
}

.actions {
  display: flex;
  gap: 16px;
  justify-content: center;
}

.btn {
  padding: 14px 28px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.3s;
  border: none;
}

.btn.primary {
  background: #2563eb;
  color: white;
}

.btn.primary:hover {
  background: #1d4ed8;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.btn.secondary {
  background: #111f35;
  color: #e2e8f0;
}

.btn.secondary:hover {
  background: #e2e8f0;
}

@media (max-width: 480px) {
  .actions {
    flex-direction: column;
  }
}
</style>
