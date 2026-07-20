<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { AlertTriangle, Banknote, CheckCircle, Clock, MessageSquare, ShieldCheck, Wallet } from 'lucide-vue-next'
import api from '@/services/api'
import swal from '@/services/swal'

const banks = ['Vietcombank', 'BIDV', 'VietinBank', 'Agribank', 'Techcombank', 'MB Bank', 'ACB', 'Sacombank', 'VPBank', 'TPBank']
const loading = ref(true)
const submitting = ref(false)
const wallet = ref({ balance: 0, pending_balance: 0, total_withdrawn: 0, formatted: {} })
const withdrawals = ref([])
const errors = ref({})

const form = reactive({
  bank_name: '',
  phone_account: '',
  account_name: '',
  amount: '',
  confirm_demo: false,
  idempotency_key: '',
})

const stats = computed(() => [
  { label: 'So du kha dung', value: wallet.value.balance, icon: Wallet },
  { label: 'Dang cho', value: wallet.value.pending_balance, icon: Clock },
  { label: 'Tong da rut', value: wallet.value.total_withdrawn, icon: Banknote },
])

const canSubmit = computed(() => {
  return !submitting.value
    && form.confirm_demo
    && form.bank_name
    && form.phone_account
    && form.account_name
    && Number(form.amount) >= 100000
    && Number(form.amount) <= Number(wallet.value.balance || 0)
})

const formatCurrency = (value) => Number(value || 0).toLocaleString('vi-VN') + 'd'

const maskPhone = (phone = '') => {
  const value = String(phone || '').trim()
  if (value.length <= 6) return value
  return `${value.slice(0, 3)}${'*'.repeat(Math.max(3, value.length - 6))}${value.slice(-3)}`
}

const createIdempotencyKey = () => {
  if (crypto?.randomUUID) return crypto.randomUUID()
  return `wd-${Date.now()}-${Math.random().toString(16).slice(2)}`
}

const ensureIdempotencyKey = () => {
  if (!form.idempotency_key) form.idempotency_key = createIdempotencyKey()
}

const normalizeWallet = (payload) => payload?.data || payload || {}
const normalizeWithdrawal = (payload) => payload?.data || payload || {}

const fetchWallet = async () => {
  const { data } = await api.get('/affiliate/wallet', { cache: false })
  wallet.value = normalizeWallet(data)
}

const fetchWithdrawals = async () => {
  const { data } = await api.get('/affiliate/withdrawals', { cache: false, params: { per_page: 20 } })
  withdrawals.value = Array.isArray(data?.data) ? data.data : []
}

const loadAll = async () => {
  loading.value = true
  try {
    await Promise.all([fetchWallet(), fetchWithdrawals()])
    ensureIdempotencyKey()
  } finally {
    loading.value = false
  }
}

const withdrawAll = () => {
  form.amount = Math.floor(Number(wallet.value.balance || 0))
}

const resetForm = () => {
  form.bank_name = ''
  form.phone_account = ''
  form.account_name = ''
  form.amount = ''
  form.confirm_demo = false
  form.idempotency_key = createIdempotencyKey()
  errors.value = {}
}

const realtimeErrors = computed(() => {
  const next = {}
  const amount = Number(form.amount || 0)
  if (form.phone_account && !/^(\+84|0)(3|5|7|8|9)[0-9]{8}$/.test(form.phone_account.replace(/\s+|-|\./g, ''))) {
    next.phone_account = 'So dien thoai Viet Nam khong hop le.'
  }
  if (form.amount && (!Number.isInteger(amount) || amount < 100000)) {
    next.amount = 'So tien rut toi thieu 100.000d va phai la so nguyen.'
  } else if (amount > Number(wallet.value.balance || 0)) {
    next.amount = 'So tien rut vuot qua so du kha dung.'
  }
  return next
})

const submitWithdrawal = async () => {
  ensureIdempotencyKey()
  errors.value = {}

  if (!canSubmit.value) {
    swal.warning('Thong tin chua hop le', 'Vui long kiem tra form va xac nhan day la giao dich demo.')
    return
  }

  const amount = Number(form.amount)
  const remaining = Number(wallet.value.balance || 0) - amount
  const ok = await swal.confirm(
    'Xac nhan rut tien demo',
    `So tien: ${formatCurrency(amount)}. Ngan hang: ${form.bank_name}. Tai khoan: ${maskPhone(form.phone_account)}. So du con lai: ${formatCurrency(remaining)}. Khong phat sinh chuyen khoan ngan hang that.`
  )
  if (!ok) return

  submitting.value = true
  try {
    const { data } = await api.post('/affiliate/withdrawals', {
      bank_name: form.bank_name,
      phone_account: form.phone_account,
      account_name: form.account_name,
      amount,
      idempotency_key: form.idempotency_key,
    })

    await Promise.all([fetchWallet(), fetchWithdrawals()])
    const withdrawal = normalizeWithdrawal(data.withdrawal)
    const smsLabel = withdrawal.sms_status === 'sent' ? 'Da gui SMS' : (withdrawal.sms_status === 'failed' ? 'Gui SMS that bai' : 'Dang cho SMS')

    await swal.success(
      'Rut tien demo thanh cong',
      `Da rut: ${formatCurrency(amount)}. So du con lai: ${formatCurrency(data.remaining_balance)}. Ma GD: ${withdrawal.transaction_code || '-'}. SMS: ${smsLabel}. Khong phat sinh chuyen khoan ngan hang that.`
    )
    resetForm()
  } catch (e) {
    const payload = e?.response?.data
    errors.value = payload?.errors || {}
    swal.error('Khong rut duoc tien demo', payload?.message || 'Vui long thu lai.')
  } finally {
    submitting.value = false
  }
}

const statusLabel = (status) => ({
  success: 'Thanh cong',
  pending: 'Dang cho',
  processing: 'Dang xu ly',
  failed: 'That bai',
}[status] || status)

const smsLabel = (status) => ({
  sent: 'Da gui SMS',
  failed: 'Gui SMS that bai',
  pending: 'Dang cho SMS',
}[status] || status)

onMounted(loadAll)
</script>

<template>
  <main class="affiliate-wallet-page">
    <section class="wallet-hero">
      <div>
        <span class="eyebrow"><ShieldCheck :size="16" /> Demo Affiliate</span>
        <h1>Vi Affiliate</h1>
        <p>Quan ly hoa hong, rut tien demo va gui SMS xac nhan thao tac cho nguoi dung.</p>
      </div>
    </section>

    <div v-if="loading" class="wallet-skeleton">
      <span></span><span></span><span></span>
    </div>

    <template v-else>
      <section class="wallet-stats">
        <article v-for="item in stats" :key="item.label" class="stat-card">
          <component :is="item.icon" />
          <span>{{ item.label }}</span>
          <strong>{{ formatCurrency(item.value) }}</strong>
        </article>
      </section>

      <section class="demo-warning">
        <AlertTriangle :size="20" />
        <span>Day la chuc nang demo. He thong chi tru so du noi bo va gui SMS xac nhan, khong chuyen tien that vao ngan hang.</span>
      </section>

      <section class="wallet-grid">
        <form class="withdraw-form" @submit.prevent="submitWithdrawal">
          <h2>Xac nhan rut tien demo</h2>
          <label>
            <span>Ngan hang</span>
            <select v-model="form.bank_name">
              <option value="">Chon ngan hang</option>
              <option v-for="bank in banks" :key="bank" :value="bank">{{ bank }}</option>
            </select>
            <small v-if="errors.bank_name">{{ errors.bank_name[0] }}</small>
          </label>
          <label>
            <span>So dien thoai / So tai khoan demo</span>
            <input v-model.trim="form.phone_account" placeholder="0987654321" inputmode="tel" />
            <small v-if="realtimeErrors.phone_account || errors.phone_account">{{ realtimeErrors.phone_account || errors.phone_account?.[0] }}</small>
          </label>
          <label>
            <span>Ten chu tai khoan</span>
            <input v-model.trim="form.account_name" placeholder="LE NGOC TAI" />
            <small v-if="errors.account_name">{{ errors.account_name[0] }}</small>
          </label>
          <label>
            <span>So tien rut</span>
            <div class="amount-row">
              <input v-model.number="form.amount" type="number" min="100000" step="1" placeholder="Toi thieu 100.000d" />
              <button type="button" @click="withdrawAll">Rut toan bo</button>
            </div>
            <small v-if="realtimeErrors.amount || errors.amount">{{ realtimeErrors.amount || errors.amount?.[0] }}</small>
          </label>
          <div class="form-note">
            <span>So du hien tai: {{ formatCurrency(wallet.balance) }}</span>
            <span>Rut toi thieu: 100.000d</span>
          </div>
          <label class="confirm-row">
            <input v-model="form.confirm_demo" type="checkbox" />
            <span>Toi xac nhan day la giao dich demo</span>
          </label>
          <button class="submit-btn" type="submit" :disabled="!canSubmit">
            <CheckCircle v-if="!submitting" :size="18" />
            <span>{{ submitting ? 'Dang xu ly...' : 'Xac nhan rut tien demo' }}</span>
          </button>
        </form>

        <section class="history-card">
          <div class="history-head">
            <h2>Lich su rut tien</h2>
            <span>{{ withdrawals.length }} giao dich</span>
          </div>
          <div class="history-table">
            <table>
              <thead>
                <tr>
                  <th>Ma GD</th>
                  <th>Ngan hang</th>
                  <th>Tai khoan</th>
                  <th>So tien</th>
                  <th>Truoc</th>
                  <th>Sau</th>
                  <th>Trang thai</th>
                  <th>SMS</th>
                  <th>Thoi gian</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in withdrawals" :key="row.id">
                  <td class="code">{{ row.transaction_code }}</td>
                  <td>{{ row.bank_name }}</td>
                  <td>{{ row.phone_account_masked }}</td>
                  <td class="money">{{ formatCurrency(row.amount) }}</td>
                  <td>{{ formatCurrency(row.balance_before) }}</td>
                  <td>{{ formatCurrency(row.balance_after) }}</td>
                  <td><span :class="['pill', row.status]">{{ statusLabel(row.status) }}</span></td>
                  <td><span :class="['pill', 'sms', row.sms_status]"><MessageSquare :size="12" />{{ smsLabel(row.sms_status) }}</span></td>
                  <td>{{ row.created_at_formatted || row.created_at }}</td>
                </tr>
                <tr v-if="withdrawals.length === 0">
                  <td colspan="9" class="empty">Chua co giao dich rut tien demo.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </section>
    </template>
  </main>
</template>

<style scoped>
.affiliate-wallet-page { max-width: 1180px; margin: 0 auto; padding: 28px 18px 42px; color: #0f172a; }
.wallet-hero { padding: 28px; border-radius: 18px; background: linear-gradient(135deg, #eff6ff, #ffffff); border: 1px solid #dbeafe; }
.eyebrow { display: inline-flex; align-items: center; gap: 8px; color: #2563eb; font-size: 12px; font-weight: 800; text-transform: uppercase; }
.wallet-hero h1 { margin: 10px 0 6px; font-size: 34px; }
.wallet-hero p { margin: 0; color: #64748b; }
.wallet-skeleton, .wallet-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin: 18px 0; }
.wallet-skeleton span { height: 112px; border-radius: 16px; background: linear-gradient(90deg, #eef2ff, #f8fafc, #eef2ff); }
.stat-card { display: grid; gap: 8px; padding: 18px; border: 1px solid #dbeafe; border-radius: 16px; background: #fff; box-shadow: 0 12px 30px rgba(15, 23, 42, .06); }
.stat-card svg { color: #2563eb; }
.stat-card span { color: #64748b; font-size: 13px; font-weight: 700; }
.stat-card strong { font-size: 24px; color: #1d4ed8; }
.demo-warning { display: flex; gap: 10px; align-items: center; margin: 18px 0; padding: 14px 16px; border-radius: 14px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; font-weight: 700; }
.wallet-grid { display: grid; grid-template-columns: 380px minmax(0, 1fr); gap: 18px; align-items: start; }
.withdraw-form, .history-card { border: 1px solid #dbeafe; border-radius: 16px; background: #fff; padding: 18px; box-shadow: 0 12px 30px rgba(15, 23, 42, .06); }
.withdraw-form { display: grid; gap: 14px; }
.withdraw-form h2, .history-head h2 { margin: 0; font-size: 20px; }
label { display: grid; gap: 7px; color: #475569; font-size: 13px; font-weight: 800; }
input, select { height: 44px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 14px; outline: none; }
input:focus, select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
small { color: #dc2626; font-weight: 700; }
.amount-row { display: grid; grid-template-columns: minmax(0, 1fr) 108px; gap: 8px; }
.amount-row button { border: 1px solid #bfdbfe; border-radius: 10px; background: #eff6ff; color: #1d4ed8; font-weight: 800; cursor: pointer; }
.form-note { display: flex; justify-content: space-between; gap: 10px; color: #64748b; font-size: 12px; font-weight: 700; }
.confirm-row { display: flex; align-items: center; gap: 8px; }
.confirm-row input { width: 18px; height: 18px; }
.submit-btn { height: 48px; border: 0; border-radius: 12px; background: #2563eb; color: #fff; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 900; cursor: pointer; }
.submit-btn:disabled { opacity: .55; cursor: not-allowed; }
.history-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; }
.history-head span { color: #64748b; font-size: 13px; font-weight: 800; }
.history-table { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { color: #64748b; background: #f8fbff; text-align: left; padding: 12px; white-space: nowrap; }
td { border-top: 1px solid #e2e8f0; padding: 12px; white-space: nowrap; }
.code { font-family: monospace; color: #2563eb; font-weight: 900; }
.money { color: #1d4ed8; font-weight: 900; }
.pill { display: inline-flex; align-items: center; gap: 4px; padding: 5px 9px; border-radius: 999px; font-size: 11px; font-weight: 900; }
.pill.success, .pill.sent { color: #166534; background: #dcfce7; }
.pill.pending, .pill.processing { color: #92400e; background: #fef3c7; }
.pill.failed { color: #991b1b; background: #fee2e2; }
.empty { text-align: center; color: #64748b; padding: 34px; }
@media (max-width: 920px) {
  .wallet-grid, .wallet-stats, .wallet-skeleton { grid-template-columns: 1fr; }
}
</style>
