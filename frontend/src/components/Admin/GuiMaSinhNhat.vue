<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'
import {
  Gift,
  Mail,
  CheckCircle,
  AlertTriangle,
  Clock,
  Search,
  RefreshCw,
  Send,
  Sliders,
  Settings,
  List,
  AlertCircle,
  Save,
  CheckSquare,
  Square,
  Play
} from 'lucide-vue-next'

// --- TABS CONFIG ---
const activeTab = ref('birthdays') // 'birthdays' | 'history' | 'config'
const tabs = [
  { id: 'birthdays', label: 'Sinh nhật hôm nay', icon: Gift },
  { id: 'history', label: 'Lịch sử gửi mã', icon: List },
  { id: 'config', label: 'Cấu hình tự động', icon: Settings }
]

// --- STATE ---
const loading = ref(false)
const searchBirthdayQuery = ref('')
const searchHistoryQuery = ref('')
const selectedStatusFilter = ref('Tất cả')
const selectedHistoryStatusFilter = ref('Tất cả')
const dateFilter = ref(new Date().toISOString().split('T')[0]) // default today
const isScanning = ref(false)
const isSendingBulk = ref(false)
const isRunningAutoNow = ref(false)

// stats values
const statsTotalToday = ref(0)
const statsCountSent = ref(0)
const statsCountUnsent = ref(0)
const statsCountError = ref(0)

// list states
const birthdayCustomers = ref([])
const sendHistory = ref([])

// --- SELECTIONS ---
const selectedIds = ref([])
const activeRowsLoading = ref({})

// --- AUTO CONFIGURATION SETUP ---
const autoConfig = ref({
  enabled: true,
  scanTime: '08:30',
  templateId: 'tpl-bday-default',
  promoCode: '',
  promotionId: null,
  limitOncePerYear: true,
  autoRetry: true,
  notifyAdmin: true
})

const emailTemplates = [
  { id: 'tpl-bday-default', name: '[Mẫu Mặc Định] Chúc mừng sinh nhật VinaTech Premium' },
  { id: 'tpl-bday-luxury', name: '[Mẫu Đặc Biệt] Tri ân khách hàng VIP sinh nhật vàng' },
  { id: 'tpl-bday-simple', name: '[Mẫu Rút Gọn] Quà tặng sinh nhật thành viên mới' }
]

const availablePromotions = ref([])

// --- STATS COMPUTED ---
const totalToday = computed(() => statsTotalToday.value)
const countSent = computed(() => statsCountSent.value)
const countUnsent = computed(() => statsCountUnsent.value)
const countError = computed(() => statsCountError.value)

// --- FILTERED COMPUTED ---
const filteredBirthdays = computed(() => {
  const q = searchBirthdayQuery.value.toLowerCase().trim()
  return birthdayCustomers.value.filter(c => {
    const matchesSearch = c.name.toLowerCase().includes(q) || c.email.toLowerCase().includes(q)
    const matchesStatus = selectedStatusFilter.value === 'Tất cả' || c.status === selectedStatusFilter.value
    return matchesSearch && matchesStatus
  })
})

const filteredHistory = computed(() => {
  const q = searchHistoryQuery.value.toLowerCase().trim()
  return sendHistory.value.filter(h => {
    const matchesSearch = h.name.toLowerCase().includes(q) || h.email.toLowerCase().includes(q) || h.code.toLowerCase().includes(q)
    const matchesStatus = selectedHistoryStatusFilter.value === 'Tất cả' || h.status === selectedHistoryStatusFilter.value
    return matchesSearch && matchesStatus
  })
})

// --- SELECTION HELPERS ---
const isAllSelected = computed(() => {
  return filteredBirthdays.value.length > 0 && selectedIds.value.length === filteredBirthdays.value.length
})

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = filteredBirthdays.value.map(c => c.id)
  }
}

const toggleSelectCustomer = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx > -1) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
}

// --- API FETCH METHODS ---
const fetchBirthdays = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/birthday-codes', {
      params: {
        date: dateFilter.value,
        keyword: searchBirthdayQuery.value,
        status: selectedStatusFilter.value
      }
    })
    if (res.data?.success) {
      birthdayCustomers.value = (res.data.data || []).map(c => ({
        ...c,
        promotionId: c.promotion_id
      }))
      statsTotalToday.value = res.data.stats?.total || 0
      statsCountSent.value = res.data.stats?.sent || 0
      statsCountUnsent.value = res.data.stats?.unsent || 0
      statsCountError.value = res.data.stats?.failed || 0
    }
  } catch (err) {
    console.error(err)
    swal.toast('Lỗi tải danh sách khách hàng sinh nhật!', 'error')
  } finally {
    loading.value = false
  }
}

const fetchHistory = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/birthday-codes/logs', {
      params: {
        keyword: searchHistoryQuery.value,
        status: selectedHistoryStatusFilter.value
      }
    })
    if (res.data?.success) {
      sendHistory.value = res.data.data || []
    }
  } catch (err) {
    console.error(err)
    swal.toast('Lỗi tải lịch sử gửi mã!', 'error')
  } finally {
    loading.value = false
  }
}

const fetchSettings = async () => {
  try {
    const res = await api.get('/admin/birthday-codes/settings')
    if (res.data?.success && res.data.data) {
      const d = res.data.data
      autoConfig.value = {
        enabled: !!d.enabled,
        scanTime: d.run_time || '08:30',
        templateId: d.email_template_id || 'tpl-bday-default',
        promoCode: d.promotion_code || '',
        promotionId: d.promotion_id || null,
        limitOncePerYear: !!d.send_once_per_year,
        autoRetry: !!d.retry_if_failed,
        notifyAdmin: !!d.notify_admin
      }

      if (res.data.promotions && Array.isArray(res.data.promotions)) {
        availablePromotions.value = res.data.promotions.map(p => ({
          id: p.id,
          code: p.code,
          name: `${p.code} - ${p.name} - Giảm ${p.type === 'percent' ? p.value + '%' : new Intl.NumberFormat('vi-VN').format(p.value) + 'đ'}`
        }))
      }
    }
  } catch (err) {
    console.error(err)
  }
}

const loadAvailablePromotions = async () => {
  try {
    const res = await api.get('/admin/birthday-codes/settings')
    if (res.data?.success && res.data.promotions) {
      availablePromotions.value = res.data.promotions.map(p => ({
        id: p.id,
        code: p.code,
        name: `${p.code} - ${p.name} - Giảm ${p.type === 'percent' ? p.value + '%' : new Intl.NumberFormat('vi-VN').format(p.value) + 'đ'}`
      }))
    }
  } catch (e) {
    console.error('Failed to load promotions:', e)
  }
}

// --- OPERATIONS ---
const refreshData = async () => {
  if (activeTab.value === 'birthdays') {
    await fetchBirthdays()
  } else if (activeTab.value === 'history') {
    await fetchHistory()
  } else {
    await fetchSettings()
    await loadAvailablePromotions()
  }
  swal.toast('Tải lại dữ liệu thành công', 'success')
}

const scanBirthdays = async () => {
  isScanning.value = true
  try {
    const res = await api.post('/admin/birthday-codes/scan', {
      date: dateFilter.value
    })
    if (res.data?.success) {
      swal.success('Quét dữ liệu hoàn tất', `Hệ thống phát hiện ${res.data.count} khách hàng có sinh nhật vào ngày ${dateFilter.value}.`)
      await fetchBirthdays()
    }
  } catch (err) {
    console.error(err)
    swal.toast('Lỗi quét dữ liệu sinh nhật!', 'error')
  } finally {
    isScanning.value = false
  }
}

// Send Single Email (Initial send or resend)
const sendSingleEmail = async (customer) => {
  if (!autoConfig.value.promotionId) {
    swal.toast('Vui lòng chọn mã khuyến mãi sinh nhật trong phần cấu hình tự động trước khi gửi.', 'warning')
    return
  }
  const isResend = customer.status === 'Đã gửi'
  const actionText = isResend ? 'Gửi lại' : 'Gửi ngay'
  
  const selectedPromo = availablePromotions.value.find(p => p.id === customer.promotionId)
  const promoCode = selectedPromo ? selectedPromo.code : customer.code
  
  const isConfirmed = await swal.confirm(
    'Xác nhận gửi',
    `Bạn muốn ${actionText.toLowerCase()} email chứa mã giảm giá "${promoCode}" cho khách hàng ${customer.name}?`
  )
  if (!isConfirmed) return

  activeRowsLoading.value[customer.id] = true
  
  try {
    const endpoint = isResend ? '/admin/gui-ma-sinh-nhat/resend' : '/admin/gui-ma-sinh-nhat/send'
    const payload = { 
      user_id: customer.id,
      promotion_id: customer.promotionId
    }

    const res = await api.post(endpoint, payload)
    
    if (res.data?.success) {
      swal.toast(`Gửi email thành công cho ${customer.name}!`, 'success')
    } else {
      swal.toast(res.data?.message || 'Gửi email không thành công.', 'error')
    }
    await fetchBirthdays()
  } catch (err) {
    console.error(err)
    const errorMsg = err.response?.data?.message || err.message || 'Lỗi hệ thống.'
    swal.error('Lỗi', `Không thể gửi email cho ${customer.name}. Chi tiết: ${errorMsg}`)
    await fetchBirthdays()
  } finally {
    activeRowsLoading.value[customer.id] = false
  }
}

// Send Bulk Emails
const sendBulk = async () => {
  if (!autoConfig.value.promotionId) {
    swal.toast('Vui lòng chọn mã khuyến mãi sinh nhật trong phần cấu hình tự động trước khi gửi.', 'warning')
    return
  }
  if (selectedIds.value.length === 0) {
    swal.error('Lỗi', 'Vui lòng chọn ít nhất một khách hàng để gửi mã!')
    return
  }

  const itemsToSend = birthdayCustomers.value.filter(c => selectedIds.value.includes(c.id) && c.status !== 'Đã gửi')
  if (itemsToSend.length === 0) {
    swal.error('Lỗi', 'Các khách hàng được chọn đều đã nhận được mã sinh nhật hôm nay!')
    return
  }

  const isConfirmed = await swal.confirm(
    'Gửi hàng loạt',
    `Bạn có chắc chắn muốn gửi email sinh nhật cho ${itemsToSend.length} khách hàng được chọn?`
  )
  if (!isConfirmed) return

  isSendingBulk.value = true
  
  try {
    const userPromotions = itemsToSend.map(item => ({
      user_id: item.id,
      promotion_id: item.promotionId || autoConfig.value.promotionId
    }))
    const res = await api.post('/admin/birthday-codes/send-bulk', {
      user_promotions: userPromotions
    })
    if (res.data?.success) {
      swal.success('Thành công', res.data.message || 'Đã hoàn tất gửi mã sinh nhật cho các khách hàng được chọn!')
      selectedIds.value = []
      await fetchBirthdays()
    }
  } catch (err) {
    console.error(err)
    swal.error('Lỗi', 'Có lỗi xảy ra khi gửi hàng loạt!')
    await fetchBirthdays()
  } finally {
    isSendingBulk.value = false
  }
}

// Save Automatic Configuration
const saveAutoConfig = async () => {
  try {
    const res = await api.post('/admin/birthday-codes/settings', {
      enabled: autoConfig.value.enabled,
      run_time: autoConfig.value.scanTime,
      promotion_id: autoConfig.value.promotionId,
      email_template_id: autoConfig.value.templateId,
      send_once_per_year: autoConfig.value.limitOncePerYear,
      retry_if_failed: autoConfig.value.autoRetry,
      notify_admin: autoConfig.value.notifyAdmin
    })
    if (res.data?.success) {
      swal.success('Lưu cấu hình', 'Đã lưu thiết lập tự động quét và gửi mã sinh nhật thành công!')
    }
  } catch (err) {
    console.error(err)
    swal.error('Lỗi', 'Lỗi không thể lưu cấu hình!')
  }
}

// Run Automatic Flow Instantly (For testing)
const runAutoNow = async () => {
  if (!autoConfig.value.promotionId) {
    swal.toast('Vui lòng chọn mã khuyến mãi sinh nhật trước khi chạy tự động.', 'warning')
    return
  }

  const isConfirmed = await swal.confirm(
    'Xác nhận chạy quét',
    'Bạn có chắc muốn chạy quét và gửi mã sinh nhật tự động ngay bây giờ không?'
  )
  if (!isConfirmed) return

  isRunningAutoNow.value = true
  try {
    const res = await api.post('/admin/birthday-codes/run-auto-now', {
      date: dateFilter.value,
      force: true
    })
    if (res.data?.success) {
      const result = res.data.data
      if (res.data.message === 'Không có khách hàng sinh nhật trong ngày được chọn.') {
        swal.toast(res.data.message, 'info')
      } else {
        swal.success(
          'Thành công',
          `Đã quét xong: tìm thấy ${result.total_birthdays} khách sinh nhật, gửi thành công ${result.sent}, lỗi ${result.failed}, bỏ qua ${result.skipped} khách đã nhận mã.`
        )
      }
      await fetchBirthdays()
      await fetchHistory()
    } else {
      swal.error('Lỗi', res.data?.message || 'Không thể chạy tự động.')
    }
  } catch (err) {
    console.error(err)
    const errorMsg = err.response?.data?.message || err.message || 'Lỗi hệ thống.'
    swal.error('Lỗi', `Lỗi khi chạy quét tự động: ${errorMsg}`)
  } finally {
    isRunningAutoNow.value = false
  }
}

// Show Error Log Details
const showErrorDetails = (log) => {
  swal.error('Nhật ký lỗi email', log || 'Không có chi tiết lỗi.')
}

// Avatar Initials Helper
const getInitials = (name) => {
  return name ? name.split(' ').slice(-2).map(n => n[0]).join('').toUpperCase() : 'KH'
}

const getAvatarColorClass = (id) => {
  const colors = ['bg-indigo', 'bg-teal', 'bg-purple', 'bg-blue', 'bg-pink']
  return colors[id % colors.length]
}

// Watches
watch([dateFilter, selectedStatusFilter], () => {
  if (activeTab.value === 'birthdays') {
    fetchBirthdays()
  }
})

watch(activeTab, (newTab) => {
  if (newTab === 'birthdays') {
    fetchBirthdays()
  } else if (newTab === 'history') {
    fetchHistory()
  } else if (newTab === 'config') {
    fetchSettings()
    loadAvailablePromotions()
  }
})

onMounted(async () => {
  await loadAvailablePromotions()
  await fetchBirthdays()
})
</script>

<template>
  <div class="page">
    <!-- STATS VIEW -->
    <div class="stats-grid">
      <!-- Card 1 -->
      <div class="stat-card stat-blue">
        <div class="stat-icon-wrapper">
          <Gift class="stat-icon" />
        </div>
        <div class="stat-data">
          <p class="stat-label">SINH NHẬT HÔM NAY</p>
          <div class="stat-number-row">
            <h2 class="stat-number">{{ totalToday }}</h2>
            <span class="stat-trend trend-neutral">Quét lúc {{ autoConfig.scanTime }}</span>
          </div>
        </div>
      </div>

      <!-- Card 2 -->
      <div class="stat-card stat-green">
        <div class="stat-icon-wrapper">
          <CheckCircle class="stat-icon" />
        </div>
        <div class="stat-data">
          <p class="stat-label">ĐÃ GỬI THÀNH CÔNG</p>
          <div class="stat-number-row">
            <h2 class="stat-number">{{ countSent }}</h2>
            <span class="stat-trend trend-up" v-if="totalToday > 0">
              {{ Math.round((countSent / totalToday) * 100) }}% Hoàn tất
            </span>
            <span class="stat-trend trend-neutral" v-else>0%</span>
          </div>
        </div>
      </div>

      <!-- Card 3 -->
      <div class="stat-card stat-yellow">
        <div class="stat-icon-wrapper">
          <Clock class="stat-icon" />
        </div>
        <div class="stat-data">
          <p class="stat-label">CHƯA GỬI MÃ</p>
          <div class="stat-number-row">
            <h2 class="stat-number">{{ countUnsent }}</h2>
            <span class="stat-trend trend-down" v-if="totalToday > 0">
              {{ Math.round((countUnsent / totalToday) * 100) }}% Còn lại
            </span>
            <span class="stat-trend trend-neutral" v-else>0%</span>
          </div>
        </div>
      </div>

      <!-- Card 4 -->
      <div class="stat-card stat-red">
        <div class="stat-icon-wrapper">
          <AlertTriangle class="stat-icon" />
        </div>
        <div class="stat-data">
          <p class="stat-label">GỬI EMAIL LỖI</p>
          <div class="stat-number-row">
            <h2 class="stat-number">{{ countError }}</h2>
            <span class="stat-trend trend-danger" v-if="countError > 0">Cần kiểm tra SMTP</span>
            <span class="stat-trend trend-safe" v-else>Hệ thống an toàn</span>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN INTERACTIVE SECTION -->
    <div class="dashboard-container">
      <!-- Custom Navigation Tabs -->
      <div class="tabs-header">
        <div class="tabs-buttons">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            :class="['tab-button', activeTab === tab.id && 'active']"
            @click="activeTab = tab.id"
          >
            <component :is="tab.icon" class="tab-icon" />
            <span>{{ tab.label }}</span>
          </button>
        </div>
      </div>

      <!-- TABS CONTAINER -->
      <div class="tab-content-wrapper">
        
        <!-- ================= TAB 1: TODAY BIRTHDAYS ================= -->
        <div v-if="activeTab === 'birthdays'" class="tab-panel animate-fade-in">
          
          <!-- Quick Action Bar -->
          <div class="action-bar-row">
            <div class="search-and-filters">
              <div class="search-input-box">
                <Search class="search-icon" />
                <input
                  v-model="searchBirthdayQuery"
                  type="text"
                  placeholder="Tìm kiếm khách hàng, email..."
                />
              </div>

              <div class="filter-dropdown-select">
                <span class="filter-label">Trạng thái:</span>
                <select v-model="selectedStatusFilter" class="styled-select">
                  <option value="Tất cả">Tất cả trạng thái</option>
                  <option value="Chưa gửi">Chưa gửi</option>
                  <option value="Đã gửi">Đã gửi</option>
                  <option value="Gửi lỗi">Gửi lỗi</option>
                </select>
              </div>

              <div class="date-picker-box">
                <span class="filter-label">Ngày sinh nhật:</span>
                <input v-model="dateFilter" type="date" class="styled-date-input" />
              </div>
            </div>

            <div class="action-buttons-group">
              <button class="btn-action btn-refresh" @click="refreshData">
                <RefreshCw class="btn-icon" />
                <span>Làm mới</span>
              </button>
              <button class="btn-action btn-secondary" @click="scanBirthdays" :disabled="isScanning">
                <RefreshCw :class="['btn-icon', isScanning && 'spin-icon']" />
                <span>{{ isScanning ? 'Đang quét...' : 'Quét sinh nhật' }}</span>
              </button>
              <button class="btn-action btn-secondary" @click="runAutoNow" :disabled="isRunningAutoNow">
                <Play :class="['btn-icon', isRunningAutoNow && 'spin-icon']" />
                <span>{{ isRunningAutoNow ? 'Đang chạy...' : 'Chạy tự động gửi mã' }}</span>
              </button>
              <button class="btn-action btn-primary" @click="sendBulk" :disabled="isSendingBulk || selectedIds.length === 0">
                <Send class="btn-icon" />
                <span>{{ isSendingBulk ? 'Đang gửi...' : 'Gửi tất cả đã chọn' }}</span>
              </button>
            </div>
          </div>

          <!-- Selection Info Notification -->
          <div class="selection-notice-bar" v-if="filteredBirthdays.length > 0">
            <div class="notice-info">
              <AlertCircle class="notice-icon" />
              <span>
                Tìm thấy <strong>{{ filteredBirthdays.length }}</strong> khách hàng. Đang chọn
                <strong>{{ selectedIds.length }}</strong> dòng.
              </span>
            </div>
            <div class="notice-guide">
              <span>Mẹo: Tích chọn checkbox ở các hàng để gửi nhanh cho nhiều người.</span>
            </div>
          </div>

          <!-- TABLE CONTAINER -->
          <div class="table-wrap">
            <div v-if="loading" class="table-state-msg">
              <div class="spinner"></div>
              <span>Đang tải danh sách khách hàng...</span>
            </div>
            <div v-else-if="filteredBirthdays.length === 0" class="table-state-msg">
              <AlertCircle class="empty-icon" />
              <span>Không tìm thấy khách hàng sinh nhật trùng khớp.</span>
            </div>
            <table v-else class="admin-data-table">
              <thead>
                <tr>
                  <th class="col-checkbox">
                    <button class="checkbox-btn" @click="toggleSelectAll">
                      <CheckSquare v-if="isAllSelected" class="checkbox-icon checked" />
                      <Square v-else class="checkbox-icon" />
                    </button>
                  </th>
                  <th>KHÁCH HÀNG</th>
                  <th>EMAIL</th>
                  <th class="text-center">NGÀY SINH</th>
                  <th class="text-center">MÃ VOUCHER</th>
                  <th class="text-center">TRẠNG THÁI</th>
                  <th class="text-center">THỜI GIAN GỬI</th>
                  <th class="text-right">THAO TÁC</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="c in filteredBirthdays"
                  :key="c.id"
                  :class="[selectedIds.includes(c.id) && 'row-selected']"
                >
                  <td class="col-checkbox">
                    <button class="checkbox-btn" @click="toggleSelectCustomer(c.id)">
                      <CheckSquare v-if="selectedIds.includes(c.id)" class="checkbox-icon checked" />
                      <Square v-else class="checkbox-icon" />
                    </button>
                  </td>
                  <td>
                    <div class="user-profile-cell">
                      <div :class="['avatar-initials', getAvatarColorClass(c.id)]">
                        {{ getInitials(c.name) }}
                      </div>
                      <div class="user-meta">
                        <span class="user-fullname">{{ c.name }}</span>
                        <span class="user-subtext">Khách hàng thành viên</span>
                      </div>
                    </div>
                  </td>
                  <td class="email-text">{{ c.email }}</td>
                  <td class="text-center bold-text">{{ c.dob }}</td>
                  <td class="text-center">
                    <span v-if="c.status === 'Đã gửi'" class="coupon-badge">{{ c.code }}</span>
                    <select
                      v-else
                      v-model="c.promotionId"
                      class="styled-select"
                      style="padding: 5px 10px; font-size: 12.5px; width: 140px; border-radius: 8px; cursor: pointer; border: 1px solid #cbd5e1; outline: none; background: #ffffff; color: #1e293b;"
                    >
                      <option v-for="promo in availablePromotions" :key="promo.id" :value="promo.id">
                        {{ promo.code }}
                      </option>
                    </select>
                  </td>
                  <td class="text-center">
                    <span
                      :class="[
                        'badge-status',
                        c.status === 'Đã gửi' && 'status-success',
                        c.status === 'Chưa gửi' && 'status-warning',
                        c.status === 'Gửi lỗi' && 'status-danger'
                      ]"
                    >
                      <span class="dot-indicator"></span>
                      {{ c.status }}
                    </span>
                  </td>
                  <td class="text-center sent-time-cell">{{ c.sentTime }}</td>
                  <td class="text-right col-actions">
                    <div class="action-buttons-cell">
                      <button
                        v-if="c.status === 'Gửi lỗi'"
                        class="btn-icon-action btn-danger-action"
                        title="Xem chi tiết lỗi"
                        @click="showErrorDetails(c.errorLog)"
                      >
                        <AlertTriangle class="icon-svg" />
                      </button>
                      <button
                        class="btn-table-action"
                        :disabled="activeRowsLoading[c.id] || isSendingBulk"
                        @click="sendSingleEmail(c)"
                      >
                        <span v-if="activeRowsLoading[c.id]" class="spinner-sm"></span>
                        <template v-else>
                          <Send class="icon-svg" />
                          <span>{{ c.status === 'Đã gửi' ? 'Gửi lại' : 'Gửi ngay' }}</span>
                        </template>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Table Footer Pagination Mockup -->
          <div class="table-pagination" v-if="filteredBirthdays.length > 0">
            <span class="showing-entries">
              Hiển thị 1 - {{ filteredBirthdays.length }} của <strong>{{ filteredBirthdays.length }}</strong> dòng
            </span>
            <div class="pages-group">
              <button class="btn-page" disabled>‹</button>
              <button class="btn-page active">1</button>
              <button class="btn-page" disabled>›</button>
            </div>
          </div>
        </div>

        <!-- ================= TAB 2: SENDING HISTORY LOGS ================= -->
        <div v-if="activeTab === 'history'" class="tab-panel animate-fade-in">
          <!-- Quick Action Bar for History -->
          <div class="action-bar-row">
            <div class="search-and-filters">
              <div class="search-input-box">
                <Search class="search-icon" />
                <input
                  v-model="searchHistoryQuery"
                  type="text"
                  placeholder="Tìm theo tên, email, mã..."
                />
              </div>

              <div class="filter-dropdown-select">
                <span class="filter-label">Trạng thái:</span>
                <select v-model="selectedHistoryStatusFilter" class="styled-select">
                  <option value="Tất cả">Tất cả trạng thái</option>
                  <option value="Đã gửi">Gửi thành công</option>
                  <option value="Gửi lỗi">Gửi thất bại</option>
                </select>
              </div>
            </div>

            <div class="action-buttons-group">
              <button class="btn-action btn-refresh" @click="refreshData">
                <RefreshCw class="btn-icon" />
                <span>Tải lại</span>
              </button>
            </div>
          </div>

          <!-- HISTORY TABLE -->
          <div class="table-wrap">
            <div v-if="filteredHistory.length === 0" class="table-state-msg">
              <AlertCircle class="empty-icon" />
              <span>Không có dữ liệu lịch sử gửi thư sinh nhật.</span>
            </div>
            <table v-else class="admin-data-table">
              <thead>
                <tr>
                  <th>TÊN KHÁCH HÀNG</th>
                  <th>EMAIL</th>
                  <th class="text-center">MÃ VOUCHER</th>
                  <th class="text-center">THỜI GIAN GỬI</th>
                  <th class="text-center">TRẠNG THÁI</th>
                  <th>CHI TIẾT / GHI CHÚ LỖI</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in filteredHistory" :key="log.id">
                  <td class="bold-text">{{ log.name }}</td>
                  <td class="email-text">{{ log.email }}</td>
                  <td class="text-center">
                    <span class="coupon-badge">{{ log.code }}</span>
                  </td>
                  <td class="text-center date-cell">{{ log.sentTime }}</td>
                  <td class="text-center">
                    <span
                      :class="[
                        'badge-status',
                        log.status === 'Đã gửi' ? 'status-success' : 'status-danger'
                      ]"
                    >
                      <span class="dot-indicator"></span>
                      {{ log.status === 'Đã gửi' ? 'Thành công' : 'Thất bại' }}
                    </span>
                  </td>
                  <td class="error-log-cell">
                    <span v-if="log.status === 'Gửi lỗi'" class="error-text">
                      <AlertTriangle class="inline-icon" />
                      {{ log.errorLog }}
                    </span>
                    <span v-else class="success-text">Đã nhận email quà tặng sinh nhật</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- History Pagination -->
          <div class="table-pagination" v-if="filteredHistory.length > 0">
            <span class="showing-entries">
              Hiển thị 1 - {{ filteredHistory.length }} của <strong>{{ filteredHistory.length }}</strong> lịch sử gửi
            </span>
            <div class="pages-group">
              <button class="btn-page" disabled>‹</button>
              <button class="btn-page active">1</button>
              <button class="btn-page" disabled>›</button>
            </div>
          </div>
        </div>

        <!-- ================= TAB 3: AUTO SCAN SCHEDULER CONFIG ================= -->
        <div v-if="activeTab === 'config'" class="tab-panel animate-fade-in">
          <div class="config-grid">
            <!-- Left Side: Basic settings form -->
            <div class="config-card">
              <div class="card-header-icon-row">
                <Settings class="header-icon" />
                <h3>Cấu hình hệ thống quét tự động</h3>
              </div>
              <p class="card-hint-text">
                Thiết lập hệ thống tự động quét dữ liệu sinh nhật khách hàng vào khung giờ cố định mỗi ngày và gửi mã giảm giá được chọn qua email.
              </p>

              <hr class="card-divider" />

              <div class="form-layout">
                <!-- Toggle enable -->
                <div class="form-group-flex">
                  <div class="meta-label">
                    <label class="bold-label">Bật chiến dịch sinh nhật tự động</label>
                    <span class="label-subtext">Hệ thống sẽ chạy cronjob tự động quét và gửi mail hằng ngày</span>
                  </div>
                  <div class="toggle-switch">
                    <input
                      type="checkbox"
                      id="auto-toggle"
                      v-model="autoConfig.enabled"
                      class="toggle-checkbox"
                    />
                    <label for="auto-toggle" class="toggle-label"></label>
                  </div>
                </div>

                <!-- Select Time -->
                <div class="form-group">
                  <label class="bold-label">Khung giờ quét tự động</label>
                  <input
                    type="time"
                    v-model="autoConfig.scanTime"
                    class="styled-time-input"
                    :disabled="!autoConfig.enabled"
                  />
                  <p class="form-help-text">Khuyên dùng: Các giờ sáng sớm để khách hàng nhận mã ngay đầu ngày sinh nhật.</p>
                </div>

                <!-- Select Template -->
                <div class="form-group">
                  <label class="bold-label">Mẫu Email chúc mừng áp dụng</label>
                  <select
                    v-model="autoConfig.templateId"
                    class="styled-select-full"
                    :disabled="!autoConfig.enabled"
                  >
                    <option v-for="tpl in emailTemplates" :key="tpl.id" :value="tpl.id">
                      {{ tpl.name }}
                    </option>
                  </select>
                </div>

                <!-- Select Active Birthday Promo Code -->
                <div class="form-group">
                  <label class="bold-label">Mã khuyến mãi sinh nhật liên kết</label>
                  <select
                    v-model="autoConfig.promotionId"
                    class="styled-select-full"
                    :disabled="!autoConfig.enabled"
                  >
                    <option v-for="promo in availablePromotions" :key="promo.id" :value="promo.id">
                      {{ promo.name }}
                    </option>
                  </select>
                  <p class="form-help-text">Bạn có thể cấu hình thêm các mã giảm giá sinh nhật mới tại mục <router-link to="/admin/quan-ly-khuyen-mai" class="inline-link">Khuyến mãi</router-link>.</p>
                </div>
              </div>
            </div>

            <!-- Right Side: Specific parameters and checks -->
            <div class="config-card flex-between">
              <div>
                <div class="card-header-icon-row">
                  <Sliders class="header-icon" />
                  <h3>Điều kiện & Ràng buộc bảo mật</h3>
                </div>
                <p class="card-hint-text">Cấu hình các điều kiện tối ưu để tránh thất thoát mã và spam email.</p>

                <hr class="card-divider" />

                <div class="checkbox-options-list">
                  <!-- Checkbox 1 -->
                  <label class="checkbox-option-item" :class="[!autoConfig.enabled && 'disabled']">
                    <input
                      type="checkbox"
                      v-model="autoConfig.limitOncePerYear"
                      :disabled="!autoConfig.enabled"
                    />
                    <div class="option-details">
                      <span class="option-title">Giới hạn chỉ gửi 1 lần / năm / khách hàng</span>
                      <span class="option-desc">Tránh trường hợp khách hàng thay đổi ngày sinh nhiều lần để trục lợi mã giảm giá.</span>
                    </div>
                  </label>

                  <!-- Checkbox 2 -->
                  <label class="checkbox-option-item" :class="[!autoConfig.enabled && 'disabled']">
                    <input
                      type="checkbox"
                      v-model="autoConfig.autoRetry"
                      :disabled="!autoConfig.enabled"
                    />
                    <div class="option-details">
                      <span class="option-title">Tự động gửi lại nếu xảy ra lỗi kết nối</span>
                      <span class="option-desc">Thử lại tối đa 3 lần sau mỗi 30 phút đối với email bị lỗi hàng chờ gửi.</span>
                    </div>
                  </label>

                  <!-- Checkbox 3 -->
                  <label class="checkbox-option-item" :class="[!autoConfig.enabled && 'disabled']">
                    <input
                      type="checkbox"
                      v-model="autoConfig.notifyAdmin"
                      :disabled="!autoConfig.enabled"
                    />
                    <div class="option-details">
                      <span class="option-title">Gửi báo cáo tổng hợp cho Admin hằng ngày</span>
                      <span class="option-desc">Gửi email thông báo danh sách khách hàng đã nhận mã khuyến mãi sau khi hoàn thành.</span>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Save button footer inside the card -->
              <div class="config-save-footer" style="display: flex; gap: 12px; width: 100%;">
                <button 
                  class="btn-action btn-secondary" 
                  style="flex: 1; justify-content: center;"
                  :disabled="isRunningAutoNow" 
                  @click="runAutoNow"
                >
                  <Play class="btn-icon" />
                  <span>{{ isRunningAutoNow ? 'Đang chạy...' : 'Chạy quét tự động ngay' }}</span>
                </button>
                <button 
                  class="btn-action btn-primary" 
                  style="flex: 1; justify-content: center;"
                  @click="saveAutoConfig"
                >
                  <Save class="btn-icon" />
                  <span>Lưu cấu hình tự động</span>
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* Common Page Container */
.page {
  padding: 24px;
  background: #f8fafc;
  min-height: 100vh;
  font-family: 'Be Vietnam Pro', 'Inter', sans-serif;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* 4 STATS ROW GRID */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
}

.stat-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 24px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.02);
  display: flex;
  align-items: center;
  gap: 20px;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
}

.stat-icon-wrapper {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon {
  width: 24px;
  height: 24px;
}

/* Stat color theme overrides */
.stat-blue .stat-icon-wrapper {
  background: rgba(37, 99, 235, 0.1);
  color: #2563eb;
}
.stat-blue::after {
  content: '';
  position: absolute;
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: rgba(37, 99, 235, 0.03);
  top: -20px;
  right: -20px;
}

.stat-green .stat-icon-wrapper {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}
.stat-green::after {
  content: '';
  position: absolute;
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.03);
  top: -20px;
  right: -20px;
}

.stat-yellow .stat-icon-wrapper {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}
.stat-yellow::after {
  content: '';
  position: absolute;
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: rgba(245, 158, 11, 0.03);
  top: -20px;
  right: -20px;
}

.stat-red .stat-icon-wrapper {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}
.stat-red::after {
  content: '';
  position: absolute;
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.03);
  top: -20px;
  right: -20px;
}

/* Stat Text Styling */
.stat-data {
  flex: 1;
  min-width: 0;
}

.stat-label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  letter-spacing: 0.1em;
  margin: 0 0 4px 0;
  text-transform: uppercase;
}

.stat-number-row {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
}

.stat-number {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
  margin: 0;
}

.stat-trend {
  font-size: 12px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
}

.trend-up {
  background: #dcfce7;
  color: #15803d;
}

.trend-down {
  background: #fef3c7;
  color: #b45309;
}

.trend-neutral {
  background: #f1f5f9;
  color: #475569;
}

.trend-danger {
  background: #fee2e2;
  color: #b91c1c;
  font-weight: 700;
}

.trend-safe {
  background: #ecfdf5;
  color: #047857;
}

/* CONTAINER BLOCK */
.dashboard-container {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.03);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* TABS HEADER NAV */
.tabs-header {
  padding: 18px 24px 0;
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
}

.tabs-buttons {
  display: flex;
  gap: 8px;
}

.tab-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 20px;
  font-size: 13.5px;
  font-weight: 600;
  color: #64748b;
  border: none;
  background: transparent;
  cursor: pointer;
  position: relative;
  transition: color 0.2s ease;
  outline: none;
}

.tab-button:hover {
  color: #0f172a;
}

.tab-button.active {
  color: #2563eb;
  font-weight: 700;
}

.tab-button.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: #2563eb;
  border-top-left-radius: 3px;
  border-top-right-radius: 3px;
}

.tab-icon {
  width: 16px;
  height: 16px;
}

/* PANEL INNER CONTENT */
.tab-content-wrapper {
  padding: 24px;
}

.tab-panel {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.animate-fade-in {
  animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ACTION BAR */
.action-bar-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.search-and-filters {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.search-input-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 8px 14px;
  width: 280px;
  box-shadow: inset 0 2px 4px rgba(15, 23, 42, 0.01);
  transition: border-color 0.2s ease;
}

.search-input-box:focus-within {
  border-color: #2563eb;
}

.search-icon {
  width: 15px;
  height: 15px;
  color: #64748b;
  stroke-width: 2.2;
}

.search-input-box input {
  border: none;
  outline: none;
  font-size: 13px;
  color: #1e293b;
  width: 100%;
}

.search-input-box input::placeholder {
  color: #94a3b8;
}

.filter-dropdown-select,
.date-picker-box {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-label {
  font-size: 12.5px;
  font-weight: 600;
  color: #64748b;
}

.styled-select {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 8px 14px;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  cursor: pointer;
  transition: border-color 0.2s ease;
}

.styled-select:focus {
  border-color: #2563eb;
}

.styled-date-input {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 7px 14px;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  cursor: pointer;
  transition: border-color 0.2s ease;
}

.styled-date-input:focus {
  border-color: #2563eb;
}

.action-buttons-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* BUTTONS DESIGN */
.btn-action {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 16px;
  font-size: 13px;
  font-weight: 600;
  border-radius: 10px;
  cursor: pointer;
  border: none;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-icon {
  width: 14px;
  height: 14px;
  stroke-width: 2.2;
}

.spin-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-refresh {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #475569;
}

.btn-refresh:hover {
  background: #f1f5f9;
  color: #1e293b;
  border-color: #cbd5e1;
}

.btn-secondary {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid rgba(37, 99, 235, 0.15);
}

.btn-secondary:hover:not(:disabled) {
  background: #dbeafe;
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.w-full {
  width: 100%;
  justify-content: center;
}

/* SELECTION NOTICE BAR */
.selection-notice-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  background: #eff6ff;
  border: 1px solid rgba(37, 99, 235, 0.15);
  border-radius: 12px;
  padding: 12px 18px;
}

.notice-info {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #1e40af;
}

.notice-icon {
  width: 16px;
  height: 16px;
  stroke-width: 2.2;
}

.notice-guide {
  font-size: 12px;
  color: #60a5fa;
  font-weight: 500;
}

/* TABLE CARD & LAYOUT */
.table-wrap {
  border: 1px solid #e8edf5;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.01);
  background: #ffffff;
}

.table-state-msg {
  padding: 48px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #94a3b8;
  font-size: 13.5px;
  font-weight: 500;
}

.table-state-msg .spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #f1f5f9;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.table-state-msg .empty-icon {
  width: 32px;
  height: 32px;
  color: #cbd5e1;
}

.admin-data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.admin-data-table thead tr {
  background: #f8faff;
  border-bottom: 1px solid #e8edf5;
}

.admin-data-table th {
  padding: 12px 18px;
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.admin-data-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s ease;
}

.admin-data-table tbody tr:last-child {
  border-bottom: none;
}

.admin-data-table tbody tr:hover {
  background: #fafbff;
}

.admin-data-table tbody tr.row-selected {
  background: #f0f7ff;
}

.admin-data-table td {
  padding: 14px 18px;
  font-size: 13px;
  color: #334155;
  vertical-align: middle;
}

/* CHECKBOX BUTTON CELL */
.col-checkbox {
  width: 44px;
  text-align: center;
  padding-right: 0 !important;
}

.checkbox-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  outline: none;
}

.checkbox-icon {
  width: 17px;
  height: 17px;
  color: #cbd5e1;
  transition: color 0.15s ease;
}

.checkbox-icon.checked {
  color: #2563eb;
  fill: #eff6ff;
}

/* USER IDENTITY CELL */
.user-profile-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar-initials {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Avatar backgrounds */
.bg-indigo { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
.bg-teal { background: linear-gradient(135deg, #14b8a6 0%, #0f766e 100%); }
.bg-purple { background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%); }
.bg-blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
.bg-pink { background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); }

.user-fullname {
  font-size: 13.5px;
  font-weight: 700;
  color: #0f172a;
  display: block;
}

.user-subtext {
  font-size: 11px;
  color: #94a3b8;
  display: block;
  margin-top: 1px;
}

.email-text {
  color: #475569;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.bold-text {
  font-weight: 600;
  color: #1e293b;
}

/* COUPON TAG BADGE */
.coupon-badge {
  display: inline-block;
  font-family: monospace;
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
  background: #eff6ff;
  border: 1px dashed rgba(37, 99, 235, 0.25);
  padding: 3px 8px;
  border-radius: 6px;
  letter-spacing: 0.02em;
}

/* STATE BADGES */
.badge-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
}

.dot-indicator {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background-color: currentColor;
}

.status-success {
  background-color: #dcfce7;
  color: #15803d;
}

.status-warning {
  background-color: #fef3c7;
  color: #b45309;
}

.status-danger {
  background-color: #fee2e2;
  color: #b91c1c;
}

.sent-time-cell {
  color: #64748b;
  font-size: 12.5px;
}

/* TABLE ACTION BUTTONS */
.action-buttons-cell {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
}

.btn-icon-action {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  outline: none;
}

.btn-icon-action .icon-svg {
  width: 14px;
  height: 14px;
}

.btn-danger-action {
  background: #fef2f2;
  border-color: #fee2e2;
  color: #dc2626;
}

.btn-danger-action:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}

.btn-table-action {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  font-size: 11.5px;
  font-weight: 700;
  color: #2563eb;
  background: #eff6ff;
  border: 1px solid rgba(37, 99, 235, 0.1);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-table-action:hover:not(:disabled) {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
}

.btn-table-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-table-action .icon-svg {
  width: 11px;
  height: 11px;
  stroke-width: 2.5;
}

.spinner-sm {
  width: 12px;
  height: 12px;
  border: 2px solid rgba(37, 99, 235, 0.15);
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.btn-table-action:hover .spinner-sm {
  border-color: rgba(255, 255, 255, 0.2);
  border-top-color: #ffffff;
}

/* PAGINATION ROW */
.table-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  border-top: 1px solid #f1f5f9;
}

.showing-entries {
  font-size: 12.5px;
  color: #64748b;
}

.pages-group {
  display: flex;
  gap: 4px;
}

.btn-page {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  font-size: 12.5px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  outline: none;
  transition: all 0.15s ease;
}

.btn-page.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
  font-weight: 700;
}

.btn-page:hover:not(.active):not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.btn-page:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* HISTORY ERRORS LOG DETAILS */
.error-log-cell {
  font-size: 12.5px;
}

.error-text {
  color: #dc2626;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
}

.success-text {
  color: #16a34a;
  font-weight: 500;
}

.inline-icon {
  width: 12px;
  height: 12px;
  flex-shrink: 0;
}

/* CONFIGURATION TAB LAYOUT */
.config-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 20px;
  align-items: stretch;
}

.config-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.01);
  display: flex;
  flex-direction: column;
}

.flex-between {
  justify-content: space-between;
}

.card-header-icon-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.header-icon {
  width: 20px;
  height: 20px;
  color: #2563eb;
}

.card-header-icon-row h3 {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.card-hint-text {
  font-size: 12.5px;
  color: #64748b;
  line-height: 1.5;
  margin: 0 0 16px 0;
}

.card-divider {
  border: none;
  border-top: 1px solid #f1f5f9;
  margin: 0 0 20px 0;
}

/* FORM ELEMENTS */
.form-layout {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.bold-label {
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}

.label-subtext {
  font-size: 11.5px;
  color: #64748b;
}

.form-help-text {
  font-size: 11.5px;
  color: #94a3b8;
  margin: 2px 0 0 0;
}

.styled-time-input {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 8px 14px;
  font-size: 14px;
  color: #1e293b;
  outline: none;
  width: 100px;
  transition: border-color 0.2s ease;
}

.styled-time-input:focus {
  border-color: #2563eb;
}

.styled-select-full {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 9px 14px;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  width: 100%;
  cursor: pointer;
}

.styled-select-full:focus {
  border-color: #2563eb;
}

.inline-link {
  color: #2563eb;
  text-decoration: underline;
  font-weight: 600;
}

.form-group-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.meta-label {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

/* TOGGLE SLIDER SWITCH */
.toggle-switch {
  position: relative;
  width: 48px;
  height: 24px;
  flex-shrink: 0;
}

.toggle-checkbox {
  opacity: 0;
  width: 0;
  height: 0;
  position: absolute;
}

.toggle-label {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  border-radius: 24px;
  cursor: pointer;
  transition: background-color 0.25s ease;
}

.toggle-label::after {
  content: '';
  position: absolute;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background-color: #ffffff;
  top: 3px;
  left: 3px;
  transition: transform 0.25s ease;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.15);
}

.toggle-checkbox:checked + .toggle-label {
  background-color: #10b981;
}

.toggle-checkbox:checked + .toggle-label::after {
  transform: translateX(24px);
}

/* CHECKBOX CRITERIA OPTIONS */
.checkbox-options-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.checkbox-option-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
  padding: 10px;
  border-radius: 8px;
  transition: background 0.15s ease;
}

.checkbox-option-item:hover:not(.disabled) {
  background: #f8fafc;
}

.checkbox-option-item.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.checkbox-option-item input[type='checkbox'] {
  width: 18px;
  height: 18px;
  accent-color: #2563eb;
  cursor: pointer;
  margin-top: 2px;
  flex-shrink: 0;
}

.option-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.option-title {
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}

.option-desc {
  font-size: 11.5px;
  color: #64748b;
  line-height: 1.4;
}

/* SAVE CONFIG FOOTER */
.config-save-footer {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}
</style>
