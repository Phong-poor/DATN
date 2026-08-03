<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'

const route = useRoute()
const router = useRouter()
const orderId = computed(() => String(route.params.id || ''))
const payment = ref(null)
const status = ref('pending')
const error = ref('')
let timer = null

const formatMoney = value => Number(value || 0).toLocaleString('vi-VN') + 'đ'
const copy = async value => navigator.clipboard?.writeText(String(value || ''))

const checkStatus = async () => {
  try {
    const response = await api.get(`/orders/${orderId.value}/sepay-status`)
    status.value = response.data.payment_status
    if (status.value === 'paid') {
      clearInterval(timer)
      sessionStorage.removeItem(`sepay_payment_${orderId.value}`)
      router.replace({ name: 'thank-you', query: { status: 'success', order_id: orderId.value } })
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Không kiểm tra được trạng thái thanh toán.'
  }
}

onMounted(() => {
  try { payment.value = JSON.parse(sessionStorage.getItem(`sepay_payment_${orderId.value}`)) } catch (_) {}
  if (!payment.value) error.value = 'Không tìm thấy thông tin QR. Vui lòng mở lại đơn hàng.'
  checkStatus()
  timer = setInterval(checkStatus, 3000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <main class="sepay-page">
    <section class="payment-card">
      <header>
        <span class="brand">SePay</span>
        <div><h1>Quét mã để thanh toán</h1><p>Hệ thống tự xác nhận sau khi ngân hàng ghi nhận tiền vào.</p></div>
      </header>

      <div v-if="payment" class="payment-grid">
        <div class="qr-wrap"><img :src="payment.qr_url" alt="Mã VietQR thanh toán SePay" /></div>
        <div class="details">
          <div><span>Số tiền</span><strong class="amount">{{ formatMoney(payment.amount) }}</strong></div>
          <div><span>Ngân hàng</span><strong>{{ payment.bank }}</strong></div>
          <div><span>Số tài khoản</span><strong>{{ payment.account_number }}</strong><button @click="copy(payment.account_number)">Sao chép</button></div>
          <div v-if="payment.account_name"><span>Chủ tài khoản</span><strong>{{ payment.account_name }}</strong></div>
          <div><span>Nội dung chuyển khoản</span><strong class="code">{{ payment.payment_code }}</strong><button @click="copy(payment.payment_code)">Sao chép</button></div>
          <p class="warning">Giữ nguyên số tiền và nội dung chuyển khoản để đơn được xác nhận tự động.</p>
        </div>
      </div>

      <div class="waiting" v-if="status !== 'paid'"><i></i> Đang chờ thanh toán đơn #{{ orderId }}</div>
      <p class="error" v-if="error">{{ error }}</p>
      <button class="back" @click="router.push({ path: '/trang-ca-nhan', query: { tab: 'orders' } })">Xem đơn hàng</button>
    </section>
  </main>
</template>

<style scoped>
.sepay-page{min-height:calc(100vh - 160px);padding:48px 16px;background:#f3f7fb}.payment-card{max-width:900px;margin:auto;background:#fff;border:1px solid #dbe5ef;border-radius:20px;padding:32px;box-shadow:0 18px 50px #0f172a12}header{display:flex;gap:18px;align-items:center;border-bottom:1px solid #e5e7eb;padding-bottom:22px}.brand{background:#16a34a;color:white;font-size:22px;font-weight:800;padding:13px 16px;border-radius:12px}h1{font-size:25px;margin:0 0 5px}header p{margin:0;color:#64748b}.payment-grid{display:grid;grid-template-columns:390px 1fr;gap:32px;padding:28px 0}.qr-wrap img{width:100%;border-radius:14px;border:1px solid #e2e8f0}.details>div{display:grid;grid-template-columns:145px 1fr auto;align-items:center;padding:12px 0;border-bottom:1px solid #eef2f7}.details span{color:#64748b}.details button{border:0;background:#e8f5ed;color:#15803d;padding:7px 10px;border-radius:7px;cursor:pointer}.amount,.code{color:#dc2626;font-size:21px}.warning{background:#fff7ed;color:#9a3412;padding:12px;border-radius:9px;line-height:1.5}.waiting{text-align:center;background:#eff6ff;color:#1d4ed8;padding:14px;border-radius:10px;font-weight:650}.waiting i{display:inline-block;width:9px;height:9px;background:#2563eb;border-radius:50%;margin-right:8px;animation:pulse 1s infinite}.error{text-align:center;color:#dc2626}.back{display:block;margin:18px auto 0;border:1px solid #cbd5e1;background:white;padding:10px 18px;border-radius:9px;cursor:pointer}@keyframes pulse{50%{opacity:.25}}@media(max-width:760px){.payment-card{padding:20px}.payment-grid{grid-template-columns:1fr}.qr-wrap{max-width:390px;margin:auto}.details>div{grid-template-columns:120px 1fr auto}header{align-items:flex-start}h1{font-size:20px}}
</style>
