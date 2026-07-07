<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'
import {
  Users,
  DollarSign,
  Clock,
  CheckCircle,
  CreditCard,
  Copy,
  Check,
  History,
  AlertCircle,
  Info,
  TrendingUp,
  Award,
  Wallet
} from 'lucide-vue-next'

const loading = ref(true)
const activating = ref(false)
const activeTab = ref('overview')
const data = ref({
  active: false,
  profile: null,
  ref_link: '',
  stats: {
    total_referrals: 0,
    pending_commission: 0,
    approved_commission: 0,
    paid_commission: 0,
  },
})
const referrals = ref([])
const commissions = ref([])
const withdraws = ref([])
const withdrawForm = ref({
  amount: '',
  bank_name: '',
  bank_account_name: '',
  bank_account_number: '',
})
const withdrawLoading = ref(false)
const error = ref('')
const copied = ref(false)

// Product Affiliate Link Generator variables
const shopProducts = ref([])
const customLinkInput = ref('')
const selectedProductId = ref('')
const generatedLink = ref('')
const genCopied = ref(false)

const fetchShopProducts = async () => {
  try {
    const res = await api.get('/sanpham')
    const raw = Array.isArray(res.data) ? res.data : (res.data.data || [])
    shopProducts.value = raw
  } catch (err) {
    console.error('Không th? t?i danh sách s?n ph?m tiếp thị', err)
  }
}

const generateCustomLink = () => {
  if (!customLinkInput.value.trim()) {
    generatedLink.value = ''
    return
  }
  
  selectedProductId.value = '' // Clear select box
  const code = data.value.profile?.affiliate_code
  if (!code) return
  
  let val = customLinkInput.value.trim()
  
  try {
    if (val.startsWith('http://') || val.startsWith('https://')) {
      const url = new URL(val)
      url.searchParams.set('ref', code)
      generatedLink.value = url.toString()
    } else {
      let path = val
      if (path.includes('?')) {
        const parts = path.split('?')
        const basePart = parts[0]
        const queryPart = parts[1]
        const params = new URLSearchParams(queryPart)
        params.set('ref', code)
        generatedLink.value = `${window.location.origin}${basePart.startsWith('/') ? '' : '/'}${basePart}?${params.toString()}`
      } else {
        generatedLink.value = `${window.location.origin}${path.startsWith('/') ? '' : '/'}${path}?ref=${code}`
      }
    }
  } catch (e) {
    generatedLink.value = `${window.location.origin}${val.startsWith('/') ? '' : '/'}${val}?ref=${code}`
  }
}

const onProductSelectChange = () => {
  if (!selectedProductId.value) {
    generatedLink.value = ''
    return
  }
  
  customLinkInput.value = '' // Clear custom input
  const code = data.value.profile?.affiliate_code
  if (!code) return
  
  generatedLink.value = `${window.location.origin}/products/${selectedProductId.value}?ref=${code}`
}

const fallbackCopyTextToClipboard = (text) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  textArea.style.position = 'fixed'
  textArea.style.top = '0'
  textArea.style.left = '0'
  textArea.style.opacity = '0'
  textArea.style.pointerEvents = 'none'
  document.body.appendChild(textArea)
  textArea.focus()
  textArea.select()
  try {
    const successful = document.execCommand('copy')
    document.body.removeChild(textArea)
    return successful
  } catch (err) {
    console.error('Fallback copy failed:', err)
    document.body.removeChild(textArea)
    return false
  }
}

const copyGeneratedLink = async () => {
  if (!generatedLink.value) return
  try {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(generatedLink.value)
    } else {
      if (!fallbackCopyTextToClipboard(generatedLink.value)) {
        throw new Error('Fallback copy failed')
      }
    }
    genCopied.value = true
    swal.toast('Đã sao chép link tiếp thị!', 'success')
    setTimeout(() => {
      genCopied.value = false
    }, 2000)
  } catch (e) {
    console.warn('Không thể copy bằng navigator.clipboard, thử copy bằng fallback...', e)
    if (fallbackCopyTextToClipboard(generatedLink.value)) {
      genCopied.value = true
      swal.toast('Đã sao chép link tiếp thị!', 'success')
      setTimeout(() => {
        genCopied.value = false
      }, 2000)
    } else {
      swal.error('Lỗi sao chép', 'Không thể tự động copy vào bộ nhớ tạm.')
    }
  }
}

const formatMoney = (value) => Number(value || 0).toLocaleString('vi-VN') + 'd'

const summaryCards = computed(() => [
  { label: 'Người được giới thiệu', value: data.value.stats.total_referrals },
  { label: 'Hoa hồng chờ duyệt', value: formatMoney(data.value.stats.pending_commission) },
  { label: 'Hoa h?ng dã duy?t', value: formatMoney(data.value.stats.approved_commission) },
  { label: 'Đã thanh toán', value: formatMoney(data.value.stats.paid_commission) },
])

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    const [meRes, refRes, comRes, wdRes] = await Promise.all([
      api.get('/affiliate/me'),
      api.get('/affiliate/referrals'),
      api.get('/affiliate/commissions'),
      api.get('/affiliate/withdraws'),
    ])
    data.value = meRes.data
    referrals.value = refRes.data
    commissions.value = comRes.data
    withdraws.value = wdRes.data

    if (data.value.active && data.value.profile?.affiliate_code) {
      fetchShopProducts()
    }
  } catch (e) {
    error.value = e?.response?.data?.message || 'Không t?i được dữ liệu affiliate.'
  } finally {
    loading.value = false
  }
}

const submitWithdraw = async () => {
  if (
    !withdrawForm.value.amount ||
    !withdrawForm.value.bank_name ||
    !withdrawForm.value.bank_account_name ||
    !withdrawForm.value.bank_account_number
  ) {
    swal.error('Lỗi nhập liệu', 'Vui lòng điền đầy đủ tất cả thông tin yêu cầu rút tiền.')
    return
  }

  const amountNum = Number(withdrawForm.value.amount || 0)
  if (amountNum < 10000) {
    swal.error('Số tiền không hợp lệ', 'Số tiền rút tối thiểu phải từ 10.000đ trở lên.')
    return
  }

  const available = Number(data.value.stats?.available_balance || 0)
  if (amountNum > available) {
    swal.error('Số dư không đủ', `Số dư khả dụng của bạn (${formatMoney(available)}) không đủ để rút số tiền này.`)
    return
  }

  const isConfirmed = await swal.confirm(
    'Xác nhận rút tiền',
    `Bạn có chắc chắn muốn gửi yêu cầu rút ${formatMoney(amountNum)} về tài khoản ngân hàng ${withdrawForm.value.bank_name}?`
  )
  if (!isConfirmed) return

  withdrawLoading.value = true
  try {
    await api.post('/affiliate/withdraws', {
      amount: amountNum,
      bank_name: withdrawForm.value.bank_name,
      bank_account_name: withdrawForm.value.bank_account_name,
      bank_account_number: withdrawForm.value.bank_account_number,
    })
    withdrawForm.value = {
      amount: '',
      bank_name: '',
      bank_account_name: '',
      bank_account_number: '',
    }
    await loadAll()
    swal.success('Đã gửi yêu cầu!', 'Yêu cầu rút tiền của bạn đã được tiếp nhận và chờ phê duyệt.')
  } catch (e) {
    swal.error('Lỗi gửi yêu cầu', e?.response?.data?.message || 'Gửi yêu cầu rút tiền thất bại.')
  } finally {
    withdrawLoading.value = false
  }
}

const activate = async () => {
  const isConfirmed = await swal.confirm(
    'Kích hoạt Affiliate',
    'B?n mu?n tham gia chuong trình tiếp thị liên k?t d? b?t d?u gia tang thu nh?p th? d?ng cùng NextGen?'
  )
  if (!isConfirmed) return

  activating.value = true
  try {
    await api.post('/affiliate/activate')
    await loadAll()
    swal.success('Kích hoạt thành công', 'Chào mừng bạn đến với mạng lưới đối tác của NextGen!')
  } catch (e) {
    swal.error('Lỗi kích hoạt', e?.response?.data?.message || 'Kích hoạt tiếp thị liên kết thất bại.')
  } finally {
    activating.value = false
  }
}

const copyLink = async () => {
  if (!data.value.ref_link) return
  try {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(data.value.ref_link)
    } else {
      if (!fallbackCopyTextToClipboard(data.value.ref_link)) {
        throw new Error('Fallback copy failed')
      }
    }
    copied.value = true
    swal.toast('Đã sao chép link giới thiệu!', 'success')
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (e) {
    console.warn('Không thể copy bằng navigator.clipboard, thử copy bằng fallback...', e)
    if (fallbackCopyTextToClipboard(data.value.ref_link)) {
      copied.value = true
      swal.toast('Đã sao chép link giới thiệu!', 'success')
      setTimeout(() => {
        copied.value = false
      }, 2000)
    } else {
      swal.error('Lỗi sao chép', 'Không thể tự động copy vào bộ nhớ tạm.')
    }
  }
}

// Helpers for UI rendering
const getStatIcon = (index) => {
  const icons = [Users, Clock, CheckCircle, DollarSign]
  return icons[index] || Users
}

const getStatIconClass = (index) => {
  const classes = ['icon-blue', 'icon-amber', 'icon-emerald', 'icon-indigo']
  return classes[index] || 'icon-blue'
}

const getCommissionStatusClass = (status) => {
  if (status === 'pending') return 'status-warning'
  if (status === 'approved') return 'status-success'
  if (status === 'paid') return 'status-info'
  return ''
}

const getCommissionStatusLabel = (status) => {
  if (status === 'pending') return 'Chờ duyệt'
  if (status === 'approved') return 'Đã duyệt'
  if (status === 'paid') return 'Đã thanh toán'
  return status
}

const getWithdrawStatusClass = (status) => {
  if (status === 'pending') return 'status-warning'
  if (status === 'approved') return 'status-success'
  if (status === 'paid') return 'status-info'
  return ''
}

const getWithdrawStatusLabel = (status) => {
  if (status === 'pending') return 'Đang xử lý'
  if (status === 'approved') return 'Chấp nhận'
  if (status === 'paid') return 'Đã chuyển tiền'
  return status
}

onMounted(loadAll)
</script>

<template>
  <div class="affiliate-page">
    <!-- Header Banner -->
    <div class="heading-banner shadow-sm">
      <div class="heading-overlay"></div>
      <div class="heading-content">
        <span class="badge-tag">Chương Trình Đối Tác</span>
        <h1>Affiliate Center</h1>
        <p>Kiếm tiền thụ động không giới hạn bằng việc tiếp thị sản phẩm của NextGen tới cộng đồng của bạn.</p>
      </div>
    </div>

    <!-- Main Container -->
    <div class="container-body">
      <!-- Loading State -->
      <div v-if="loading" class="card card-loading">
        <div class="spinner-box">
          <div class="double-bounce1"></div>
          <div class="double-bounce2"></div>
        </div>
        <p>Đang tải dữ liệu, vui lòng d?i...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="card card-error-status">
        <AlertCircle class="error-status-icon" />
        <h3>Đã xảy ra sự cố</h3>
        <p>{{ error }}</p>
        <button class="btn btn-primary" @click="loadAll">T?i l?i trang</button>
      </div>

      <!-- Active Status Check -->
      <template v-else>
        <!-- Non-activated State -->
        <div class="card activation-card shadow-sm" v-if="!data.active">
          <div class="activation-icon-box">
            <Award class="award-icon" />
          </div>
          <h3>Đăng Ký Cộng Tác Viên Ti?p Th?</h3>
          <p class="activation-desc">
            Nhận mức chia sẻ hoa hồng ưu đãi trọn đời lên tới <strong>{{ data.profile?.commission_rate || 5 }}%</strong> cho mỗi đơn hàng phát sinh thành công từ mạng lưới tiếp thị của bạn.
          </p>
          <button class="btn btn-primary btn-lg" :disabled="activating" @click="activate">
            <Award class="icon-inline" v-if="!activating" />
            <span>{{ activating ? 'Đang kích hoạt...' : 'Kích hoạt tài khoản ngay' }}</span>
          </button>
        </div>

        <!-- Activated / Dashboard State -->
        <div class="dashboard-container" v-else>
          <!-- Navigation Tabs Bar -->
          <div class="dashboard-tabs">
            <button :class="['tab-btn', { active: activeTab === 'overview' }]" @click="activeTab = 'overview'">
              <TrendingUp class="tab-icon" />
              <span>T?ng quan</span>
            </button>
            <button :class="['tab-btn', { active: activeTab === 'referrals' }]" @click="activeTab = 'referrals'">
              <Users class="tab-icon" />
              <span>Thành viên ({{ referrals.length }})</span>
            </button>
            <button :class="['tab-btn', { active: activeTab === 'commissions' }]" @click="activeTab = 'commissions'">
              <DollarSign class="tab-icon" />
              <span>Hoa h?ng ({{ commissions.length }})</span>
            </button>
            <button :class="['tab-btn', { active: activeTab === 'withdraw' }]" @click="activeTab = 'withdraw'">
              <CreditCard class="tab-icon" />
              <span>Rút tiền</span>
            </button>
          </div>

          <!-- Tab Panels Container -->
          <div class="tab-content-panel shadow-sm">
            <!-- TAB 1: OVERVIEW -->
            <div v-if="activeTab === 'overview'" class="tab-pane fade-in">
              <div class="welcome-row">
                <div class="welcome-meta">
                  <h2>Chào m?ng tr? l?i, {{ data.profile?.name || 'C?ng tác viên' }}!</h2>
                  <p>Hãy theo dõi liên kết giới thiệu và trạng thái tài chính của bạn tại đây.</p>
                </div>
                <div class="code-badges">
                  <div class="info-badge">
                    <span class="info-badge-label">Mã CTV:</span>
                    <span class="info-badge-value">{{ data.profile?.affiliate_code }}</span>
                  </div>
                  <div class="info-badge highlight">
                    <span class="info-badge-label">Hoa h?ng:</span>
                    <span class="info-badge-value">{{ data.profile?.commission_rate }}%</span>
                  </div>
                </div>
              </div>

              <!-- Link Copy Block -->
              <div class="link-sharing-card">
                <div class="link-card-body">
                  <div class="link-info-text">
                    <h4>Mã liên kết giới thiệu của bạn</h4>
                    <p>Chia sẻ đường dẫn này cho mọi người. Khi họ đăng ký tài khoản qua liên kết này, bạn sẽ nhận được hoa hồng từ mỗi đơn hàng mua thành công.</p>
                  </div>
                  <div class="link-copy-box">
                    <input class="link-input" :value="data.ref_link" readonly />
                    <button :class="['btn btn-copy', { 'copied': copied }]" @click="copyLink">
                      <component :is="copied ? Check : Copy" class="btn-icon" />
                      <span>{{ copied ? 'Đã sao chép' : 'Sao chép link' }}</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Product Affiliate Link Generator -->
              <div class="link-sharing-card product-generator-card" style="margin-top: 20px;">
                <div class="link-card-body">
                  <div class="link-info-text">
                    <h4>Công cụ tạo Link Tiếp Thị Sản Phẩm</h4>
                    <p>Chia sẻ trực tiếp bất kỳ trang sản phẩm hoặc trang nội dung nào. Khi khách hàng click vào link tiếp thị này, họ sẽ được dẫn thẳng đến trang đó để xem hoặc mua hàng, đồng thời hệ thống vẫn tự động ghi nhận hoa hồng cho bạn.</p>
                  </div>
                  
                  <div class="generator-container">
                    <div class="generator-row">
                      <div class="generator-col">
                        <label class="generator-label">Cách 1: Ch?n s?n ph?m t? danh sách</label>
                        <select class="generator-select" v-model="selectedProductId" @change="onProductSelectChange">
                          <option value="">-- Ch?n s?n ph?m tiếp thị --</option>
                          <option v-for="prod in shopProducts" :key="prod.id_sanpham" :value="prod.id_sanpham">
                            {{ prod.tenSP }}
                          </option>
                        </select>
                      </div>
                      
                      <div class="generator-col">
                        <label class="generator-label">Cách 2: Dán du?ng d?n trang web b?t k?</label>
                        <input class="generator-input" v-model="customLinkInput" placeholder="Ví d?: /products/12 ho?c http://localhost:5173/products" @input="generateCustomLink" />
                      </div>
                    </div>

                    <!-- Generated Result -->
                    <div class="generated-box-wrapper" v-if="generatedLink">
                      <span class="result-badge">Đường dẫn tiếp thị của bạn (Đã tích hợp mã CTV):</span>
                      <div class="link-copy-box generated-copy-box">
                        <input class="link-input generated-input" :value="generatedLink" readonly />
                        <button :class="['btn btn-copy', { 'copied': genCopied }]" @click="copyGeneratedLink">
                          <component :is="genCopied ? Check : Copy" class="btn-icon" />
                          <span>{{ genCopied ? 'Đã sao chép' : 'Sao chép link' }}</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Stats Dashboard Grid -->
              <div class="stats-grid">
                <div class="stat-card shadow-xs" v-for="(item, index) in summaryCards" :key="index">
                  <div :class="['stat-icon-wrapper', getStatIconClass(index)]">
                    <component :is="getStatIcon(index)" class="stat-icon" />
                  </div>
                  <div class="stat-details">
                    <div class="stat-label">{{ item.label }}</div>
                    <div class="stat-value">{{ item.value }}</div>
                  </div>
                </div>
              </div>

              <!-- General Info notice card -->
              <div class="notice-info-card">
                <Info class="notice-icon" />
                <div class="notice-text">
                  <strong>Chu kỳ duyệt hoa hồng:</strong> Giao dịch hoa hồng phát sinh sẽ hiển thị ở trạng thái <strong>Chờ duyệt</strong>. Sau khi đơn hàng hoàn thành thành công và qua 7 ngày đổi trả, số dư sẽ chuyển thành <strong>Đã duyệt (Khả dụng)</strong> để bạn có thể rút về tài khoản ngân hàng.
                </div>
              </div>
            </div>

            <!-- TAB 2: REFERRALS LIST -->
            <div v-else-if="activeTab === 'referrals'" class="tab-pane fade-in">
              <div class="section-header">
                <h3>Thành viên được bạn giới thiệu</h3>
                <p>Tổng quan danh sách những khách hàng đăng ký tài khoản thành công qua liên kết giới thiệu của bạn.</p>
              </div>
              <div class="table-container">
                <table class="modern-table">
                  <thead>
                    <tr>
                      <th>Tên thành viên</th>
                      <th>Email</th>
                      <th>Ngày tham gia</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="r in referrals" :key="r.id">
                      <td class="font-semibold">{{ r.referred_user?.name || '-' }}</td>
                      <td class="text-muted">{{ r.referred_user?.email || '-' }}</td>
                      <td>{{ r.registered_at ? new Date(r.registered_at).toLocaleString('vi-VN') : '-' }}</td>
                    </tr>
                    <tr v-if="referrals.length === 0">
                      <td colspan="3" class="table-empty">
                        <Users class="empty-icon" />
                        <p>Chưa có thành viên nào đăng ký qua liên kết giới thiệu của bạn.</p>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- TAB 3: COMMISSION HISTORY -->
            <div v-else-if="activeTab === 'commissions'" class="tab-pane fade-in">
              <div class="section-header">
                <h3>Lịch sử hoa hồng</h3>
                <p>Chi tiết các khoản tiền thưởng tích lũy từ các hóa đơn thanh toán của người được giới thiệu.</p>
              </div>
              <div class="table-container">
                <table class="modern-table">
                  <thead>
                    <tr>
                      <th>Đơn hàng</th>
                      <th>Khách hàng</th>
                      <th>Số tiền hoa hồng</th>
                      <th>Tr?ng thái</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="c in commissions" :key="c.id">
                      <td class="order-code">#{{ c.order_id }}</td>
                      <td class="font-semibold">{{ c.referred_user?.name || '-' }}</td>
                      <td class="commission-amount">{{ formatMoney(c.amount) }}</td>
                      <td>
                        <span :class="['badge-status', getCommissionStatusClass(c.status)]">
                          {{ getCommissionStatusLabel(c.status) }}
                        </span>
                      </td>
                    </tr>
                    <tr v-if="commissions.length === 0">
                      <td colspan="4" class="table-empty">
                        <DollarSign class="empty-icon" />
                        <p>Hiện tại bạn chưa phát sinh bất kỳ khoản hoa hồng nào.</p>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- TAB 4: WITHDRAW & HISTORY -->
            <div v-else-if="activeTab === 'withdraw'" class="tab-pane fade-in">
              <div class="withdraw-dashboard">
                <!-- Request Form Card -->
                <div class="withdraw-form-card">
                  <div class="withdraw-balance-box">
                    <div class="balance-meta">
                      <span class="balance-label">S? d? kh? d?ng d? rút:</span>
                      <h2 class="balance-val">{{ formatMoney(data.stats?.available_balance || 0) }}</h2>
                    </div>
                    <Wallet class="balance-icon" />
                  </div>

                  <div class="withdraw-inputs">
                    <div class="input-group">
                      <label>Số tiền rút (VNĐ) <span class="required">*</span></label>
                      <div class="input-wrapper">
                        <DollarSign class="input-icon" />
                        <input v-model="withdrawForm.amount" type="number" min="10000" placeholder="Số tiền rút (t?i thi?u 10,000d)" />
                      </div>
                    </div>

                    <div class="input-group">
                      <label>Tên Ngân hàng <span class="required">*</span></label>
                      <input v-model="withdrawForm.bank_name" placeholder="Ví d?: Vietcombank, Techcombank..." />
                    </div>

                    <div class="input-group">
                      <label>Tên Ch? tài khoản <span class="required">*</span></label>
                      <input v-model="withdrawForm.bank_account_name" placeholder="Ví d?: NGUYEN VAN A" />
                    </div>

                    <div class="input-group">
                      <label>S? tài khoản <span class="required">*</span></label>
              <input v-model="withdrawForm.bank_account_number" placeholder="Nhập chính xác số tài khoản ngân hàng" />
                    </div>
                  </div>

                  <button class="btn btn-primary btn-block btn-lg" :disabled="withdrawLoading" @click="submitWithdraw">
                    <CreditCard class="icon-inline" v-if="!withdrawLoading" />
                    <span>{{ withdrawLoading ? 'Đang gửi yêu cầu...' : 'Gửi yêu cầu rút tiền' }}</span>
                  </button>
                </div>

                <!-- Withdraw requests History table -->
                <div class="withdraw-history-box">
                  <div class="section-header border-none">
                    <h3>Yêu cầu rút tiền của bạn</h3>
                    <p>Theo dõi quá trình phê duyệt và chi trả tiền hoa hồng tiếp thị liên kết.</p>
                  </div>
                  <div class="table-container">
                    <table class="modern-table">
                      <thead>
                        <tr>
                          <th>Số tiền</th>
                          <th>Tài kho?n th? hu?ng</th>
                          <th>Tr?ng thái</th>
                          <th>Ngày tạo</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="w in withdraws" :key="w.id">
                          <td class="font-bold text-dark">{{ formatMoney(w.amount) }}</td>
                          <td>
                            <div class="bank-meta-text">
                              <strong>{{ w.bank_name }}</strong>
                              <span>{{ w.bank_account_number }} - {{ w.bank_account_name }}</span>
                            </div>
                          </td>
                          <td>
                            <span :class="['badge-status', getWithdrawStatusClass(w.status)]">
                              {{ getWithdrawStatusLabel(w.status) }}
                            </span>
                          </td>
                          <td>{{ new Date(w.created_at).toLocaleString('vi-VN') }}</td>
                        </tr>
                        <tr v-if="withdraws.length === 0">
                          <td colspan="4" class="table-empty">
                            <History class="empty-icon" />
                            <p>Bạn chưa gửi yêu cầu rút tiền nào.</p>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.affiliate-page {
  font-family: 'Inter', sans-serif;
  max-width: 1200px;
  margin: 30px auto;
  padding: 0 20px;
  color: #e2e8f0;
}

/* Heading Banner */
.heading-banner {
  position: relative;
  background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
  border-radius: 20px;
  padding: 45px 40px;
  overflow: hidden;
  color: #ffffff;
  margin-bottom: 30px;
}
.heading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.15) 0%, transparent 60%);
  z-index: 1;
}
.heading-content {
  position: relative;
  z-index: 2;
  max-width: 700px;
}
.badge-tag {
  display: inline-block;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(4px);
  color: #f3f4f6;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  padding: 6px 16px;
  border-radius: 99px;
  margin-bottom: 16px;
}
.heading-content h1 {
  font-size: 36px;
  font-weight: 800;
  margin: 0 0 10px;
  letter-spacing: -0.5px;
}
.heading-content p {
  font-size: 16px;
  color: #d1d5db;
  margin: 0;
  line-height: 1.5;
}

/* General Layout Elements */
.container-body {
  min-height: 350px;
}

.card {
  background: #111f35;
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px;
  padding: 24px;
}

/* Loading status */
.card-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #64748b;
  gap: 16px;
}
.spinner-box {
  width: 40px;
  height: 40px;
  position: relative;
}
.double-bounce1, .double-bounce2 {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background-color: #3b82f6;
  opacity: 0.6;
  position: absolute;
  top: 0;
  left: 0;
  animation: sk-bounce 2.0s infinite ease-in-out;
}
.double-bounce2 {
  animation-delay: -1.0s;
}
@keyframes sk-bounce {
  0%, 100% { transform: scale(0.0) }
  50% { transform: scale(1.0) }
}

/* Error status */
.card-error-status {
  text-align: center;
  padding: 50px 30px;
}
.error-status-icon {
  width: 54px;
  height: 54px;
  color: #ef4444;
  margin-bottom: 16px;
}
.card-error-status h3 {
  font-size: 20px;
  margin: 0 0 8px;
}
.card-error-status p {
  color: #64748b;
  font-size: 14px;
  margin: 0 0 24px;
}

/* Activation Card */
.activation-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 55px 30px;
  max-width: 650px;
  margin: 20px auto;
}
.activation-icon-box {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #eff6ff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
}
.award-icon {
  width: 36px;
  height: 36px;
}
.activation-card h3 {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 12px;
}
.activation-desc {
  font-size: 15px;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 32px;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  padding: 10px 20px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}
.btn-primary:hover {
  opacity: 0.95;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}
.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
.btn-lg {
  padding: 14px 28px;
  font-size: 15px;
  border-radius: 12px;
}
.icon-inline {
  width: 18px;
  height: 18px;
}

/* Dashboard Container */
.dashboard-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Navigation Tabs */
.dashboard-tabs {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  background: #0d1b2e;
  padding: 6px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.07);
}
.tab-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: transparent;
  border: none;
  border-radius: 10px;
  padding: 12px 10px;
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}
.tab-btn:hover {
  color: #e2e8f0;
  background: rgba(255,255,255,0.7);
}
.tab-btn.active {
  background: #111f35;
  color: #2563eb;
  box-shadow: 0 4px 10px -2px rgba(15, 23, 42, 0.08);
}
.tab-icon {
  width: 18px;
  height: 18px;
  stroke-width: 2.2;
}

/* Tab Panel */
.tab-content-panel {
  background: #111f35;
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 16px;
  padding: 28px;
  min-height: 300px;
}

/* Tab 1: Overview Panel styling */
.welcome-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}
.welcome-meta h2 {
  font-size: 22px;
  font-weight: 700;
  margin: 0 0 6px;
}
.welcome-meta p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}
.code-badges {
  display: flex;
  gap: 10px;
}
.info-badge {
  background: #111f35;
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 10px;
  padding: 8px 14px;
  display: flex;
  flex-direction: column;
  min-width: 100px;
}
.info-badge.highlight {
  background: #eff6ff;
  border-color: #bfdbfe;
}
.info-badge-label {
  font-size: 10px;
  text-transform: uppercase;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 2px;
}
.info-badge.highlight .info-badge-label {
  color: #2563eb;
}
.info-badge-value {
  font-size: 15px;
  font-weight: 700;
  color: #f1f5f9;
}
.info-badge.highlight .info-badge-value {
  color: #1e3a8a;
}

/* Link Sharing Card */
.link-sharing-card {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 14px;
  padding: 20px;
  margin-bottom: 28px;
}
.link-info-text h4 {
  font-size: 15px;
  font-weight: 700;
  margin: 0 0 4px;
}
.link-info-text p {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 16px;
  line-height: 1.4;
}
.link-copy-box {
  display: flex;
  gap: 8px;
  background: #111f35;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 6px;
  overflow: hidden;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
.link-input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 8px 10px;
  font-size: 13.5px;
  color: #e2e8f0;
  outline: none;
  font-family: monospace;
}
.btn-copy {
  flex-shrink: 0;
  padding: 8px 16px;
  border-radius: 8px;
  background: #0f172a;
  color: #ffffff;
}
.btn-copy:hover {
  background: #1e293b;
}
.btn-copy.copied {
  background: #2563eb;
}
.btn-icon {
  width: 14px;
  height: 14px;
}

/* Stats dashboard cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
.stat-card {
  background: #111f35;
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 14px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01), 0 2px 4px -1px rgba(0,0,0,0.01);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04), 0 4px 6px -2px rgba(0,0,0,0.01);
}
.stat-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-icon {
  width: 20px;
  height: 20px;
}
.icon-blue { background: #eff6ff; color: #2563eb; }
.icon-amber { background: #fffbeb; color: #d97706; }
.icon-emerald { background: #ecfdf5; color: #1D4ED8; }
.icon-indigo { background: #eef2ff; color: #2563eb; }

.stat-details {
  min-width: 0;
}
.stat-label {
  font-size: 12px;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.stat-value {
  font-size: 16px;
  font-weight: 700;
  margin-top: 2px;
}

/* Notice Panel */
.notice-info-card {
  display: flex;
  gap: 12px;
  background: #0d1b2e;
  border-left: 4px solid #3b82f6;
  border-radius: 10px;
  padding: 16px;
  margin-top: 20px;
}
.notice-icon {
  width: 20px;
  height: 20px;
  color: #3b82f6;
  flex-shrink: 0;
}
.notice-text {
  font-size: 13px;
  color: #94a3b8;
  line-height: 1.5;
}

/* Section headers */
.section-header {
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.section-header h3 {
  font-size: 18px;
  font-weight: 700;
  margin: 0 0 4px;
}
.section-header p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}
.border-none {
  border-bottom: none;
  padding-bottom: 0;
}

/* Modern Tables styling */
.table-container {
  overflow-x: auto;
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 12px;
}
.modern-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13.5px;
}
.modern-table th {
  background: #0d1b2e;
  color: #94a3b8;
  font-weight: 600;
  padding: 14px 18px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 12.5px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.modern-table td {
  padding: 14px 18px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
  color: #cbd5e1;
  vertical-align: middle;
}
.modern-table tr:last-child td {
  border-bottom: none;
}
.modern-table tbody tr:hover td {
  background: #0d1b2e;
}
.font-semibold { font-weight: 600; }
.text-muted { color: #64748b; }
.order-code { font-family: monospace; font-weight: 700; color: #2563eb; }
.commission-amount { font-weight: 700; color: #2563eb; }

.table-empty {
  text-align: center;
  padding: 40px 20px !important;
  color: #94a3b8;
}
.empty-icon {
  width: 38px;
  height: 38px;
  margin-bottom: 12px;
  color: #cbd5e1;
}

/* Status Badges */
.badge-status {
  display: inline-flex;
  align-items: center;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 99px;
  line-height: 1;
}
.status-warning {
  background: #fef9c3;
  color: #a16207;
}
.status-success {
  background: #d1fae5;
  color: #065f46;
}
.status-info {
  background: #dbeafe;
  color: #1e40af;
}

/* Withdraw Layout Panels */
.withdraw-dashboard {
  display: grid;
  grid-template-columns: 1.1fr 1.9fr;
  gap: 24px;
  align-items: start;
}
.withdraw-form-card {
  background: #111f35;
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
}
.withdraw-balance-box {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  border-radius: 12px;
  padding: 16px 20px;
  color: #ffffff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.balance-label {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 500;
}
.balance-val {
  font-size: 20px;
  font-weight: 700;
  margin: 4px 0 0;
  color: #f8fafc;
}
.balance-icon {
  width: 26px;
  height: 26px;
  color: #3b82f6;
  opacity: 0.9;
}

.withdraw-inputs {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-bottom: 20px;
}
.input-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.input-group label {
  font-size: 12px;
  font-weight: 600;
  color: #94a3b8;
}
.required {
  color: #ef4444;
}
.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}
.input-icon {
  position: absolute;
  left: 12px;
  width: 16px;
  height: 16px;
  color: #94a3b8;
}
.input-group input, .input-wrapper input {
  width: 100% !important;
  height: 42px !important;
  max-height: 42px !important;
  box-sizing: border-box !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 8px !important;
  padding: 9px 12px !important;
  font-size: 13.5px !important;
  color: #e2e8f0 !important;
  outline: none !important;
  transition: all 0.2s ease !important;
}
.input-wrapper input {
  padding-left: 36px;
}
.input-group input:focus, .input-wrapper input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.btn-block {
  width: 100%;
}
.bank-meta-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.bank-meta-text span {
  font-size: 11px;
  color: #64748b;
}
.font-bold { font-weight: 700; }
.text-dark { color: #f1f5f9; }

/* Micro-animations */
.fade-in {
  animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Product Affiliate Link Generator styles */
.product-generator-card {
  border: 1px solid rgba(255,255,255,0.07);
  background: #0d1b2e !important;
}
.generator-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-top: 15px;
}
.generator-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}
.generator-col {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.generator-label {
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
}
.generator-select, .generator-input {
  width: 100% !important;
  height: 48px !important;
  max-height: 48px !important;
  padding: 12px 16px !important;
  box-sizing: border-box !important;
  border-radius: 12px !important;
  border: 1px solid #cbd5e1 !important;
  background: var(--tn-surface) !important;
  font-size: 14px !important;
  color: #e2e8f0 !important;
  outline: none !important;
  transition: all 0.2s ease !important;
  line-height: normal !important;
}
.generator-select:focus, .generator-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.generated-box-wrapper {
  background: #eff6ff;
  border: 1px dashed #3b82f6;
  padding: 20px;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  animation: fadeIn 0.25s ease-out;
}
.result-badge {
  font-size: 13px;
  font-weight: 700;
  color: #1d4ed8;
}
.generated-copy-box {
  background: var(--tn-surface) !important;
}
.generated-input {
  color: #1d4ed8 !important;
  font-weight: 600;
}

/* Responsive breakdowns */
@media (max-width: 991px) {
  .withdraw-dashboard {
    grid-template-columns: 1fr;
  }
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 767px) {
  .heading-banner {
    padding: 30px 20px;
  }
  .heading-content h1 {
    font-size: 28px;
  }
  .heading-content p {
    font-size: 14px;
  }
  .dashboard-tabs {
    grid-template-columns: 1fr;
    gap: 4px;
  }
  .tab-btn {
    padding: 10px;
  }
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .welcome-row {
    flex-direction: column;
    align-items: stretch;
  }
  .code-badges {
    justify-content: space-between;
  }
  .generator-row {
    grid-template-columns: 1fr;
    gap: 15px;
  }
}
</style>
