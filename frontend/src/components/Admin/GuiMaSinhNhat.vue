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
  CheckSquare,
  Square,
  Power
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
  validDays: 30,
  templateId: 'tpl-bday-default',
  promoCode: '',
  promotionId: null,
  limitOncePerYear: true,
  autoRetry: true,
  notifyAdmin: true
})
const savedAutoStatus = ref({ enabled: false, scanTime: '08:30' })

const emailTemplates = [
  { id: 'tpl-bday-default', name: '[Mẫu mặc định] Chúc mừng sinh nhật NextGen' }
]

const availablePromotions = ref([])

const formatPromotionOption = (p) => {
  const valueText = p.loai === 'percent'
    ? `${p.giatri}%`
    : `${new Intl.NumberFormat('vi-VN').format(p.giatri)}đ`

  return {
    id: p.id,
    code: p.code,
    name: `${p.code} - ${p.ten} - Giảm ${valueText}`,
    valueText,
  }
}

const selectedPromotion = computed(() => {
  return availablePromotions.value.find(p => Number(p.id) === Number(autoConfig.value.promotionId)) || null
})

const syncUnsentRowsPromotion = () => {
  if (!autoConfig.value.promotionId) return
  birthdayCustomers.value = birthdayCustomers.value.map(c => {
    if (c.status === 'Đã gửi') return c
    return {
      ...c,
      promotionId: c.promotionId || autoConfig.value.promotionId
    }
  })
}

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
        promotionId: c.promotion_id || autoConfig.value.promotionId
      }))
      syncUnsentRowsPromotion()
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
        validDays: Number(d.valid_days || 30),
        templateId: d.email_template_id || 'tpl-bday-default',
        promoCode: d.promotion_code || '',
        promotionId: d.promotion_id || null,
        limitOncePerYear: !!d.send_once_per_year,
        autoRetry: !!d.retry_if_failed,
        notifyAdmin: !!d.notify_admin
      }
      savedAutoStatus.value = {
        enabled: !!d.enabled,
        scanTime: d.run_time || '08:30'
      }

      if (res.data.promotions && Array.isArray(res.data.promotions)) {
        availablePromotions.value = res.data.promotions.map(formatPromotionOption)
        if (!autoConfig.value.promotionId && availablePromotions.value.length > 0) {
          autoConfig.value.promotionId = availablePromotions.value[0].id
          autoConfig.value.promoCode = availablePromotions.value[0].code
        }
        syncUnsentRowsPromotion()
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
      availablePromotions.value = res.data.promotions.map(formatPromotionOption)
      if (!autoConfig.value.promotionId && availablePromotions.value.length > 0) {
        autoConfig.value.promotionId = availablePromotions.value[0].id
        autoConfig.value.promoCode = availablePromotions.value[0].code
      }
      syncUnsentRowsPromotion()
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
  const promotionId = customer.promotionId || autoConfig.value.promotionId
  if (!promotionId) {
    swal.toast('Vui lòng chọn mã khuyến mãi sinh nhật trước khi gửi.', 'warning')
    return
  }
  const isResend = customer.status === 'Đã gửi'
  const actionText = isResend ? 'Gửi lại' : 'Gửi ngay'
  
  const selectedPromo = availablePromotions.value.find(p => Number(p.id) === Number(promotionId))
  const promoCode = selectedPromo ? selectedPromo.code : customer.code
  
  const isConfirmed = await swal.confirm(
    'Xác nhận gửi',
    `Bạn muốn ${actionText.toLowerCase()} email chứa mã giảm giá "${promoCode}" cho khách hàng ${customer.name}?`
  )
  if (!isConfirmed) return

  activeRowsLoading.value[customer.id] = true
  
  try {
    const endpoint = isResend ? '/admin/birthday-codes/resend' : '/admin/birthday-codes/send'
    const payload = { 
      user_id: customer.id,
      promotion_id: promotionId
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
    swal.toast('Vui lòng chọn mã khuyến mãi sinh nhật trước khi gửi.', 'warning')
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
    `Bạn có chắc chắn muốn gửi email sinh nhật cho ${itemsToSend.length} khách hàng được chọn bằng mã "${selectedPromotion.value?.code || ''}"?`
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
const saveAutoConfig = async (silent = false) => {
  if (!Number.isInteger(autoConfig.value.validDays) || autoConfig.value.validDays < 1 || autoConfig.value.validDays > 365) {
    swal.error('Thời hạn không hợp lệ', 'Thời hạn mã sinh nhật phải từ 1 đến 365 ngày.')
    return
  }
  try {
    const res = await api.post('/admin/birthday-codes/settings', {
      enabled: autoConfig.value.enabled,
      run_time: autoConfig.value.scanTime,
      valid_days: autoConfig.value.validDays,
      promotion_id: autoConfig.value.promotionId,
      email_template_id: autoConfig.value.templateId,
      send_once_per_year: autoConfig.value.limitOncePerYear,
      retry_if_failed: autoConfig.value.autoRetry,
      notify_admin: autoConfig.value.notifyAdmin
    })
    if (res.data?.success) {
      savedAutoStatus.value = {
        enabled: autoConfig.value.enabled,
        scanTime: autoConfig.value.scanTime
      }
      if (!silent) {
        swal.success('Lưu cấu hình', 'Đã lưu thiết lập tự động quét và gửi mã sinh nhật thành công!')
      }
    }
  } catch (err) {
    console.error(err)
    swal.error('Lỗi', 'Lỗi không thể lưu cấu hình!')
  }
}

const toggleAutoConfig = async () => {
  autoConfig.value.enabled = !autoConfig.value.enabled
  await saveAutoConfig(true)
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

watch(() => autoConfig.value.promotionId, (promotionId) => {
  const promo = availablePromotions.value.find(p => Number(p.id) === Number(promotionId))
  autoConfig.value.promoCode = promo?.code || ''
  syncUnsentRowsPromotion()
})

onMounted(async () => {
  await fetchSettings()
  await fetchBirthdays()
})
</script>

<template>
  <div class="page">
    <!-- STATS VIEW -->
    <div class="stats-grid">
      <!-- Card 1 -->
      <div class="gmsn-stat-card">
        <span class="gmsn-label">SINH NHẬT HÔM NAY</span>
        <div class="gmsn-card-body">
          <div class="gmsn-left-group">
            <div class="gmsn-icon-box blue">
              <Gift class="gmsn-icon" />
            </div>
            <h2 class="gmsn-number">{{ totalToday }}</h2>
          </div>
          <span class="gmsn-badge neutral">Quét lúc {{ autoConfig.scanTime }}</span>
        </div>
      </div>

      <!-- Card 2 -->
      <div class="gmsn-stat-card">
        <span class="gmsn-label">ĐÃ GỬI THÀNH CÔNG</span>
        <div class="gmsn-card-body">
          <div class="gmsn-left-group">
            <div class="gmsn-icon-box green">
              <CheckCircle class="gmsn-icon" />
            </div>
            <h2 class="gmsn-number">{{ countSent }}</h2>
          </div>
          <span class="gmsn-badge success" v-if="totalToday > 0">
            {{ Math.round((countSent / totalToday) * 100) }}% Hoàn tất
          </span>
          <span class="gmsn-badge neutral" v-else>0% Hoàn tất</span>
        </div>
      </div>

      <!-- Card 3 -->
      <div class="gmsn-stat-card">
        <span class="gmsn-label">CHƯA GỬI MÃ</span>
        <div class="gmsn-card-body">
          <div class="gmsn-left-group">
            <div class="gmsn-icon-box yellow">
              <Clock class="gmsn-icon" />
            </div>
            <h2 class="gmsn-number">{{ countUnsent }}</h2>
          </div>
          <span class="gmsn-badge warning" v-if="totalToday > 0">
            {{ Math.round((countUnsent / totalToday) * 100) }}% Còn lại
          </span>
          <span class="gmsn-badge neutral" v-else>0% Còn lại</span>
        </div>
      </div>

      <!-- Card 4 -->
      <div class="gmsn-stat-card">
        <span class="gmsn-label">GỬI EMAIL LỖI</span>
        <div class="gmsn-card-body">
          <div class="gmsn-left-group">
            <div class="gmsn-icon-box red">
              <AlertTriangle class="gmsn-icon" />
            </div>
            <h2 class="gmsn-number">{{ countError }}</h2>
          </div>
          <span class="gmsn-badge danger" v-if="countError > 0">Cần kiểm tra SMTP</span>
          <span class="gmsn-badge success" v-else>Hệ thống an toàn</span>
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

              <div class="birthday-promo-select">
                <span class="filter-label">Chương trình sinh nhật:</span>
                <select v-model="autoConfig.promotionId" class="styled-select promo-select">
                  <option :value="null" disabled>Chọn chương trình sinh nhật</option>
                  <option v-for="promo in availablePromotions" :key="promo.id" :value="promo.id">
                    {{ promo.name }}
                  </option>
                </select>
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
              <button
                class="btn-action auto-status-button"
                :class="savedAutoStatus.enabled ? 'is-enabled' : 'is-disabled'"
                @click="activeTab = 'config'"
                title="Mở cấu hình gửi mã tự động"
              >
                <Settings class="btn-icon" />
                <span>{{ savedAutoStatus.enabled ? `Tự động bật · ${savedAutoStatus.scanTime}` : 'Tự động đang tắt' }}</span>
              </button>
              <button class="btn-action btn-primary" @click="sendBulk" :disabled="isSendingBulk || selectedIds.length === 0">
                <Send class="btn-icon" />
                <span>{{ isSendingBulk ? 'Đang gửi...' : 'Gửi tất cả đã chọn' }}</span>
              </button>
            </div>
          </div>

          <div class="promotion-empty-warning" v-if="availablePromotions.length === 0">
            <AlertTriangle class="notice-icon" />
            <span>
              Chưa có mã khuyến mãi sinh nhật đang hoạt động. Tạo mã tại
              <router-link to="/admin/quan-ly-khuyen-mai" class="inline-link">Khuyến mãi</router-link>
              với loại sinh nhật trước khi gửi.
            </span>
          </div>

          <!-- Selection Info Notification -->
          <div class="selection-notice-bar" v-if="filteredBirthdays.length > 0">
            <div class="notice-info">
              <AlertCircle class="notice-icon" />
              <span>
                Tìm thấy <strong>{{ filteredBirthdays.length }}</strong> khách hàng. Đang chọn
                <strong>{{ selectedIds.length }}</strong> dòng.
                Mã gửi: <strong>{{ selectedPromotion?.code || 'Chưa chọn' }}</strong>.
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
                      <option :value="null" disabled>Chọn mã</option>
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

              <div class="action-buttons-group history-actions">
                <button class="btn-action btn-refresh" @click="refreshData">
                  <RefreshCw class="btn-icon" />
                  <span>Tải lại</span>
                </button>
              </div>
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
                      @change="saveAutoConfig(true)"
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
                    @change="saveAutoConfig(true)"
                  />
                  <p class="form-help-text">Khuyên dùng: Các giờ sáng sớm để khách hàng nhận mã ngay đầu ngày sinh nhật.</p>
                </div>

                <div class="form-group">
                  <label class="bold-label">Thời hạn sử dụng mã sinh nhật</label>
                  <div class="validity-input-wrap">
                    <input
                      v-model.number="autoConfig.validDays"
                      type="number"
                      min="1"
                      max="365"
                      class="styled-time-input"
                      :disabled="!autoConfig.enabled"
                      @change="saveAutoConfig(true)"
                    />
                    <strong>ngày kể từ lúc gửi</strong>
                  </div>
                  <p class="form-help-text">Mỗi mã được cấp riêng cho một khách hàng, hết hạn sau thời gian trên và chỉ sử dụng được 1 lần.</p>
                </div>

                <!-- Select Template -->
                <div class="form-group">
                  <label class="bold-label">Mẫu Email chúc mừng áp dụng</label>
                  <select
                    v-model="autoConfig.templateId"
                    class="styled-select-full"
                    :disabled="!autoConfig.enabled"
                    @change="saveAutoConfig(true)"
                  >
                    <option v-for="tpl in emailTemplates" :key="tpl.id" :value="tpl.id">
                      {{ tpl.name }}
                    </option>
                  </select>
                </div>

                <!-- Select Active Birthday Promo Code -->
                <div class="form-group">
                  <label class="bold-label">Chương trình khuyến mãi sinh nhật liên kết</label>
                  <select
                    v-model="autoConfig.promotionId"
                    class="styled-select-full"
                    :disabled="!autoConfig.enabled"
                    @change="saveAutoConfig(true)"
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
                      @change="saveAutoConfig(true)"
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
                      @change="saveAutoConfig(true)"
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
                      @change="saveAutoConfig(true)"
                    />
                    <div class="option-details">
                      <span class="option-title">Gửi báo cáo tổng hợp cho Admin hằng ngày</span>
                      <span class="option-desc">Gửi email thông báo danh sách khách hàng đã nhận mã khuyến mãi sau khi hoàn thành.</span>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Automatic controls -->
              <div class="config-save-footer" style="display: flex; gap: 12px; width: 100%;">
                <button
                  type="button"
                  class="btn-action auto-config-toggle-button"
                  :class="autoConfig.enabled ? 'is-enabled' : 'is-disabled'"
                  :aria-pressed="autoConfig.enabled"
                  @click="toggleAutoConfig"
                >
                  <Power class="btn-icon" />
                  <span>{{ autoConfig.enabled ? 'Quét tự động: Đang bật' : 'Quét tự động: Đang tắt' }}</span>
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
  grid-template-columns: repeat(4, minmax(200px, 1fr));
  gap: 16px;
}

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.gmsn-stat-card {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%) !important;
  border-radius: 16px;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.14);
  position: relative;
  overflow: hidden;
  border: none !important;
  transition: all 0.2s ease;
}

.gmsn-stat-card::after {
  content: '';
  position: absolute;
  width: 140px;
  height: 140px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  top: -45px;
  right: -25px;
  pointer-events: none;
}

.gmsn-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 32px rgba(15, 23, 42, 0.2);
}

.gmsn-label {
  font-size: 11.5px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.9);
  letter-spacing: 0.5px;
  text-transform: uppercase;
  position: relative;
  z-index: 1;
}

.gmsn-card-body {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  position: relative;
  z-index: 1;
}

.gmsn-left-group {
  display: flex;
  align-items: center;
  gap: 12px;
}

.gmsn-icon-box {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.18) !important;
  color: #ffffff !important;
}

.gmsn-icon {
  width: 22px;
  height: 22px;
  stroke-width: 2.2;
}

.gmsn-number {
  font-size: 30px;
  font-weight: 800;
  color: #ffffff !important;
  line-height: 1;
  margin: 0;
}

.gmsn-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 5px 12px;
  border-radius: 999px;
  white-space: nowrap;
  background: rgba(255, 255, 255, 0.92) !important;
  color: #1d4ed8 !important;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.gmsn-badge.neutral { color: #1d4ed8 !important; }
.gmsn-badge.success { color: #059669 !important; }
.gmsn-badge.warning { color: #d97706 !important; }
.gmsn-badge.danger { color: #dc2626 !important; }

.stat-trend {
  font-size: 12px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
}

.trend-up {
  background: #dcfce7;
  color: #1d4ed8;
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
  color: #1E40AF;
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
  flex-direction: column;
  align-items: stretch;
  gap: 14px;
}

.search-and-filters {
  display: grid;
  grid-template-columns: minmax(190px, .8fr) minmax(250px, 1fr) minmax(320px, 1.5fr);
  align-items: center;
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
  width: 100%;
  box-sizing: border-box;
  grid-column: 1 / -1;
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
.date-picker-box,
.birthday-promo-select {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.birthday-promo-select {
  min-width: 0;
}

.promo-select {
  width: 100%;
  min-width: 0;
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

.filter-dropdown-select .styled-select,
.date-picker-box .styled-date-input {
  flex: 1;
  min-width: 0;
}

.action-buttons-group {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.history-actions {
  justify-content: flex-start;
}

@media (max-width: 1050px) {
  .search-and-filters {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .birthday-promo-select {
    grid-column: 1 / -1;
  }
}

@media (max-width: 700px) {
  .search-and-filters {
    grid-template-columns: 1fr;
  }

  .search-input-box,
  .birthday-promo-select {
    grid-column: auto;
  }

  .filter-dropdown-select,
  .date-picker-box,
  .birthday-promo-select {
    align-items: stretch;
    flex-direction: column;
  }
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

.validity-input-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}
.validity-input-wrap input { width: 120px; }
.validity-input-wrap strong { color: #334155; font-size: 12px; }

.auto-status-button {
  min-width: 166px;
  justify-content: center;
  border: 1px solid transparent;
}

.auto-status-button.is-enabled {
  background: linear-gradient(135deg, #059669, #047857);
  border-color: #047857;
  color: #ffffff;
  box-shadow: 0 6px 16px rgba(5, 150, 105, .32);
}

.auto-status-button.is-enabled:hover:not(:disabled) {
  background: linear-gradient(135deg, #047857, #065f46);
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(5, 150, 105, .4);
}

.auto-status-button.is-disabled {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  border-color: #b91c1c;
  color: #ffffff;
  box-shadow: 0 6px 16px rgba(220, 38, 38, .28);
}

.auto-status-button.is-disabled:hover:not(:disabled) {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  border-color: #991b1b;
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(220, 38, 38, .38);
}

.auto-status-button .btn-icon { flex: 0 0 auto; }

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

.promotion-empty-warning {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 12px;
  color: #92400e;
  font-size: 13px;
  font-weight: 600;
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
  text-transform: capitalize;
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
.bg-indigo { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.bg-teal { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
.bg-purple { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
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
  color: #1d4ed8;
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
  color: #2563eb;
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
  background-color: #2563eb;
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

.auto-config-toggle-button {
  flex: 1;
  justify-content: center;
  color: #ffffff;
  border: 1px solid transparent;
  white-space: nowrap;
  box-shadow: 0 5px 14px rgba(15, 23, 42, 0.18);
}

.auto-config-toggle-button.is-enabled {
  background: linear-gradient(135deg, #059669, #047857);
  border-color: #047857;
}

.auto-config-toggle-button.is-enabled:hover {
  background: linear-gradient(135deg, #047857, #065f46);
}

.auto-config-toggle-button.is-disabled {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  border-color: #b91c1c;
}

.auto-config-toggle-button.is-disabled:hover {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
}

@media (max-width: 1100px) {
  .config-save-footer {
    flex-wrap: wrap;
  }

  .config-save-footer > .btn-action {
    min-width: 220px;
  }
}
</style>
