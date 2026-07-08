<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import {
  BadgeCheck,
  Ban,
  Banknote,
  CheckCircle2,
  ChevronRight,
  ClipboardCopy,
  Clock3,
  Download,
  Filter,
  HandCoins,
  Link2,
  Loader2,
  MoreHorizontal,
  RefreshCcw,
  Search,
  ShieldCheck,
  SlidersHorizontal,
  Sparkles,
  TrendingUp,
  UserCheck,
  Users,
  WalletCards,
  XCircle,
} from 'lucide-vue-next'
import api from '@/services/api'
import swal from '@/services/swal'

const loading = ref(true)
const actionLoading = ref('')
const activeTab = ref('publishers')
const searchQuery = ref('')
const statusFilter = ref('all')
const selectedProfile = ref(null)
const currentPage = ref(1)
const itemsPerPage = ref(5)
const payload = ref({ profiles: [], commissions: [], withdraw_requests: [] })

const profileStatuses = [
  { value: 'all', label: 'Tất cả trạng thái' },
  { value: 'pending', label: 'Chờ duyệt' },
  { value: 'active', label: 'Đang hoạt động' },
  { value: 'suspended', label: 'Tạm khóa' },
  { value: 'rejected', label: 'Từ chối' },
]

const commissionStatuses = [
  { value: 'all', label: 'Tất cả trạng thái' },
  { value: 'pending', label: 'Chờ duyệt' },
  { value: 'approved', label: 'Đã duyệt' },
  { value: 'paid', label: 'Đã thanh toán' },
  { value: 'cancelled', label: 'Đã hủy' },
]

const withdrawStatuses = [
  { value: 'all', label: 'Tất cả trạng thái' },
  { value: 'pending', label: 'Chờ duyệt' },
  { value: 'approved', label: 'Đã duyệt' },
  { value: 'paid', label: 'Đã chi trả' },
  { value: 'rejected', label: 'Từ chối' },
]

const statusLabelMap = {
  pending: 'Chờ duyệt',
  active: 'Đang hoạt động',
  suspended: 'Tạm khóa',
  rejected: 'Từ chối',
  approved: 'Đã duyệt',
  paid: 'Đã thanh toán',
  cancelled: 'Đã hủy',
}

const tabItems = computed(() => [
  { key: 'publishers', label: 'Publisher', count: profiles.value.length, icon: Users },
  { key: 'commissions', label: 'Hoa hồng', count: commissions.value.length, icon: HandCoins },
  { key: 'withdraws', label: 'Rút tiền', count: withdraws.value.length, icon: WalletCards },
])

const profiles = computed(() => payload.value.profiles || [])
const commissions = computed(() => payload.value.commissions || [])
const withdraws = computed(() => payload.value.withdraw_requests || [])

const currentStatuses = computed(() => {
  if (activeTab.value === 'commissions') return commissionStatuses
  if (activeTab.value === 'withdraws') return withdrawStatuses
  return profileStatuses
})

const formatMoney = (value) => `${Number(value || 0).toLocaleString('vi-VN')}đ`

const formatDate = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const initials = (name = '') => {
  const words = String(name || 'N').trim().split(/\s+/).filter(Boolean)
  if (!words.length) return 'N'
  return words.length === 1
    ? words[0].slice(0, 2).toUpperCase()
    : `${words[0][0]}${words[words.length - 1][0]}`.toUpperCase()
}

const normalize = (value) => String(value || '').toLowerCase().trim()

const loadData = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/admin/affiliates', { cache: false })
    payload.value = {
      profiles: data.profiles || [],
      commissions: data.commissions || [],
      withdraw_requests: data.withdraw_requests || [],
    }

    if (!selectedProfile.value && payload.value.profiles.length) {
      selectedProfile.value = payload.value.profiles[0]
    } else if (selectedProfile.value) {
      selectedProfile.value = payload.value.profiles.find(p => p.id === selectedProfile.value.id) || payload.value.profiles[0] || null
    }
  } catch (err) {
    swal.error('Không tải được dữ liệu', err?.response?.data?.message || 'Vui lòng kiểm tra lại API affiliate admin.')
  } finally {
    loading.value = false
  }
}

const stats = computed(() => {
  const pendingProfiles = profiles.value.filter(p => p.status === 'pending').length
  const activeProfiles = profiles.value.filter(p => p.status === 'active').length
  const pendingCommissions = commissions.value.filter(c => c.status === 'pending')
  const approvedCommissions = commissions.value.filter(c => c.status === 'approved')
  const paidCommissions = commissions.value.filter(c => c.status === 'paid')
  const pendingWithdraws = withdraws.value.filter(w => w.status === 'pending')

  return {
    publishers: profiles.value.length,
    activeProfiles,
    pendingProfiles,
    pendingCommissionCount: pendingCommissions.length,
    pendingCommissionAmount: pendingCommissions.reduce((sum, row) => sum + Number(row.amount || 0), 0),
    approvedAmount: approvedCommissions.reduce((sum, row) => sum + Number(row.amount || 0), 0),
    paidAmount: paidCommissions.reduce((sum, row) => sum + Number(row.amount || 0), 0),
    pendingWithdrawCount: pendingWithdraws.length,
    pendingWithdrawAmount: pendingWithdraws.reduce((sum, row) => sum + Number(row.amount || 0), 0),
    conversionOrders: commissions.value.length,
  }
})

const filteredProfiles = computed(() => {
  const q = normalize(searchQuery.value)
  return profiles.value.filter((profile) => {
    const haystack = normalize([
      profile.user?.name,
      profile.user?.email,
      profile.affiliate_code,
      profile.status,
    ].join(' '))
    const matchSearch = !q || haystack.includes(q)
    const matchStatus = statusFilter.value === 'all' || profile.status === statusFilter.value
    return matchSearch && matchStatus
  })
})

const filteredCommissions = computed(() => {
  const q = normalize(searchQuery.value)
  return commissions.value.filter((row) => {
    const haystack = normalize([
      row.order_id,
      row.affiliate_user?.name,
      row.affiliate_user?.email,
      row.referred_user?.name,
      row.referred_user?.email,
      row.status,
    ].join(' '))
    const matchSearch = !q || haystack.includes(q)
    const matchStatus = statusFilter.value === 'all' || row.status === statusFilter.value
    return matchSearch && matchStatus
  })
})

const filteredWithdraws = computed(() => {
  const q = normalize(searchQuery.value)
  return withdraws.value.filter((row) => {
    const haystack = normalize([
      row.affiliate_user?.name,
      row.affiliate_user?.email,
      row.bank_name,
      row.bank_account_name,
      row.bank_account_number,
      row.status,
    ].join(' '))
    const matchSearch = !q || haystack.includes(q)
    const matchStatus = statusFilter.value === 'all' || row.status === statusFilter.value
    return matchSearch && matchStatus
  })
})

const activeRows = computed(() => {
  if (activeTab.value === 'commissions') return filteredCommissions.value
  if (activeTab.value === 'withdraws') return filteredWithdraws.value
  return filteredProfiles.value
})

const totalPages = computed(() => Math.max(1, Math.ceil(activeRows.value.length / Number(itemsPerPage.value || 5))))

const paginatedProfiles = computed(() => {
  const start = (currentPage.value - 1) * Number(itemsPerPage.value || 5)
  return filteredProfiles.value.slice(start, start + Number(itemsPerPage.value || 5))
})

const paginatedCommissions = computed(() => {
  const start = (currentPage.value - 1) * Number(itemsPerPage.value || 5)
  return filteredCommissions.value.slice(start, start + Number(itemsPerPage.value || 5))
})

const paginatedWithdraws = computed(() => {
  const start = (currentPage.value - 1) * Number(itemsPerPage.value || 5)
  return filteredWithdraws.value.slice(start, start + Number(itemsPerPage.value || 5))
})

const pageStart = computed(() => {
  if (!activeRows.value.length) return 0
  return (currentPage.value - 1) * Number(itemsPerPage.value || 5) + 1
})

const pageEnd = computed(() => Math.min(currentPage.value * Number(itemsPerPage.value || 5), activeRows.value.length))

const visiblePageNumbers = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, start + 4)
  const adjustedStart = Math.max(1, end - 4)

  for (let page = adjustedStart; page <= end; page += 1) {
    pages.push(page)
  }

  return pages
})

const goToPage = (page) => {
  currentPage.value = Math.min(Math.max(1, page), totalPages.value)
}

const setTab = (tab) => {
  activeTab.value = tab
  statusFilter.value = 'all'
  currentPage.value = 1
}

const updateProfile = async (profile, data, message) => {
  actionLoading.value = `profile-${profile.id}`
  try {
    await api.put(`/admin/affiliate-profiles/${profile.id}`, data)
    swal.success('Đã cập nhật', message)
    await loadData()
  } catch (err) {
    swal.error('Không cập nhật được publisher', err?.response?.data?.message || 'Vui lòng thử lại.')
  } finally {
    actionLoading.value = ''
  }
}

const changeProfileStatus = async (profile, status) => {
  await updateProfile(profile, { status }, `Publisher đã chuyển sang trạng thái ${statusLabelMap[status] || status}.`)
}

const updateRate = async (profile) => {
  const value = Number(profile.commission_rate)
  if (Number.isNaN(value) || value < 0 || value > 100) {
    swal.warning('Tỉ lệ không hợp lệ', 'Tỉ lệ hoa hồng phải nằm trong khoảng 0 - 100%.')
    return
  }
  await updateProfile(profile, { commission_rate: value }, 'Tỉ lệ hoa hồng đã được lưu.')
}

const updateCommissionStatus = async (row, status) => {
  actionLoading.value = `commission-${row.id}`
  try {
    await api.put(`/admin/affiliate-commissions/${row.id}/status`, { status })
    swal.success('Đã cập nhật hoa hồng', `Giao dịch #${row.order_id || row.id} đã chuyển sang ${statusLabelMap[status] || status}.`)
    await loadData()
  } catch (err) {
    swal.error('Không cập nhật được hoa hồng', err?.response?.data?.message || 'Vui lòng thử lại.')
  } finally {
    actionLoading.value = ''
  }
}

const updateWithdrawStatus = async (row, status) => {
  actionLoading.value = `withdraw-${row.id}`
  try {
    await api.put(`/admin/affiliate-withdraws/${row.id}/status`, { status })
    swal.success('Đã cập nhật rút tiền', `Yêu cầu rút tiền đã chuyển sang ${statusLabelMap[status] || status}.`)
    await loadData()
  } catch (err) {
    swal.error('Không cập nhật được rút tiền', err?.response?.data?.message || 'Vui lòng thử lại.')
  } finally {
    actionLoading.value = ''
  }
}

const copyText = async (text, label = 'Nội dung') => {
  if (!text) return
  try {
    await navigator.clipboard.writeText(text)
    swal.toast(`${label} đã được sao chép`)
  } catch {
    swal.warning('Không thể sao chép', 'Trình duyệt chưa cấp quyền clipboard.')
  }
}

const affiliateLink = (code) => `${window.location.origin}/dang-ky?ref=${code}`

const exportCsv = () => {
  const rows = [
    ['Ten publisher', 'Email', 'Ma affiliate', 'Trang thai', 'Ti le', 'Tong kiem duoc', 'Da thanh toan', 'So du kha dung'],
    ...filteredProfiles.value.map(p => [
      p.user?.name || '',
      p.user?.email || '',
      p.affiliate_code || '',
      statusLabelMap[p.status] || p.status || '',
      `${p.commission_rate || 0}%`,
      p.total_earned || 0,
      p.total_paid || 0,
      p.available_balance || 0,
    ]),
  ]
  const csv = rows.map(row => row.map(cell => `"${String(cell).replaceAll('"', '""')}"`).join(',')).join('\n')
  const blob = new Blob([`\ufeff${csv}`], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `affiliate-publishers-${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

watch([searchQuery, statusFilter, itemsPerPage], () => {
  currentPage.value = 1
})

watch(totalPages, (pages) => {
  if (currentPage.value > pages) {
    currentPage.value = pages
  }
})

onMounted(loadData)
</script>

<template>
  <div class="affiliate-admin">
    <section class="affiliate-hero">
      <div>
        <div class="eyebrow">
          <Sparkles :size="16" />
          Affiliate Commerce Ops
        </div>
        <h1>Affiliate Center Admin</h1>
        <p>Theo dõi publisher, duyệt hoa hồng, xử lý rút tiền và tối ưu hiệu suất tiếp thị liên kết trong một màn hình.</p>
      </div>
      <div class="hero-actions">
        <button class="btn ghost" type="button" @click="exportCsv">
          <Download :size="18" />
          Xuất CSV
        </button>
        <button class="btn primary" type="button" :disabled="loading" @click="loadData">
          <Loader2 v-if="loading" :size="18" class="spin" />
          <RefreshCcw v-else :size="18" />
          Tải lại
        </button>
      </div>
    </section>

    <section class="metrics-grid">
      <article class="metric-card blue">
        <div class="metric-icon"><Users :size="24" /></div>
        <div>
          <span>Publisher</span>
          <strong>{{ stats.publishers }}</strong>
          <small>{{ stats.activeProfiles }} đang hoạt động · {{ stats.pendingProfiles }} chờ duyệt</small>
        </div>
      </article>
      <article class="metric-card blue">
        <div class="metric-icon"><Clock3 :size="24" /></div>
        <div>
          <span>Hoa hồng chờ duyệt</span>
          <strong>{{ formatMoney(stats.pendingCommissionAmount) }}</strong>
          <small>{{ stats.pendingCommissionCount }} giao dịch cần xử lý</small>
        </div>
      </article>
      <article class="metric-card teal">
        <div class="metric-icon"><Banknote :size="24" /></div>
        <div>
          <span>Rút tiền chờ duyệt</span>
          <strong>{{ formatMoney(stats.pendingWithdrawAmount) }}</strong>
          <small>{{ stats.pendingWithdrawCount }} yêu cầu thanh toán</small>
        </div>
      </article>
      <article class="metric-card violet">
        <div class="metric-icon"><TrendingUp :size="24" /></div>
        <div>
          <span>Đã thanh toán</span>
          <strong>{{ formatMoney(stats.paidAmount) }}</strong>
          <small>{{ stats.conversionOrders }} đơn có ghi nhận hoa hồng</small>
        </div>
      </article>
    </section>

    <section class="control-panel">
      <div class="tabs">
        <button
          v-for="tab in tabItems"
          :key="tab.key"
          type="button"
          class="tab-btn"
          :class="{ active: activeTab === tab.key }"
          @click="setTab(tab.key)"
        >
          <component :is="tab.icon" :size="17" />
          {{ tab.label }}
          <span>{{ tab.count }}</span>
        </button>
      </div>

      <div class="filters">
        <label class="search-box">
          <Search :size="18" />
          <input v-model="searchQuery" type="search" placeholder="Tìm publisher, email, mã affiliate, đơn hàng..." />
        </label>
        <label class="select-box">
          <Filter :size="17" />
          <select v-model="statusFilter">
            <option v-for="item in currentStatuses" :key="item.value" :value="item.value">
              {{ item.label }}
            </option>
          </select>
        </label>
      </div>
    </section>

    <div v-if="loading" class="loading-card">
      <Loader2 :size="34" class="spin" />
      <span>Đang đồng bộ dữ liệu affiliate...</span>
    </div>

    <template v-else>
      <section v-if="activeTab === 'publishers'" class="content-grid">
        <article class="data-card wide">
          <div class="card-title-row">
            <div>
              <h2>Danh sách publisher</h2>
              <p>{{ filteredProfiles.length }} publisher phù hợp bộ lọc hiện tại</p>
            </div>
            <SlidersHorizontal :size="20" />
          </div>

          <div class="publisher-list">
            <button
              v-for="profile in paginatedProfiles"
              :key="profile.id"
              type="button"
              class="publisher-row"
              :class="{ selected: selectedProfile?.id === profile.id }"
              @click="selectedProfile = profile"
            >
              <div class="avatar">{{ initials(profile.user?.name) }}</div>
              <div class="publisher-main">
                <strong>{{ profile.user?.name || 'Chưa có tên' }}</strong>
                <span>{{ profile.user?.email || '-' }}</span>
              </div>
              <code>{{ profile.affiliate_code }}</code>
              <span class="rate">{{ profile.commission_rate || 0 }}%</span>
              <span class="money">{{ formatMoney(profile.total_earned) }}</span>
              <span class="status-pill" :class="profile.status">{{ statusLabelMap[profile.status] || profile.status }}</span>
              <ChevronRight :size="18" />
            </button>

            <div v-if="filteredProfiles.length === 0" class="empty-state">
              <Users :size="42" />
              <strong>Chưa có publisher phù hợp</strong>
              <span>Thay đổi từ khóa hoặc bộ lọc để xem thêm dữ liệu.</span>
            </div>
          </div>

          <div v-if="filteredProfiles.length > 0" class="pagination-bar">
            <div class="pagination-info">
              Hiển thị {{ pageStart }}-{{ pageEnd }} / {{ filteredProfiles.length }} publisher
            </div>
            <div class="pagination-actions">
              <label class="page-size">
                <span>Dòng/trang</span>
                <select v-model.number="itemsPerPage">
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                </select>
              </label>
              <button type="button" class="page-btn" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Trước</button>
              <span class="page-btn number active page-indicator">{{ currentPage }}/{{ totalPages }}</span>
              <button type="button" class="page-btn" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">Sau</button>
            </div>
          </div>
        </article>

        <aside class="data-card profile-panel">
          <template v-if="selectedProfile">
            <div class="profile-head">
              <div class="avatar large">{{ initials(selectedProfile.user?.name) }}</div>
              <div class="profile-title">
                <h3>{{ selectedProfile.user?.name || 'Publisher' }}</h3>
                <p>{{ selectedProfile.user?.email || '-' }}</p>
                <span class="status-pill profile-status" :class="selectedProfile.status">
                  {{ statusLabelMap[selectedProfile.status] || selectedProfile.status }}
                </span>
              </div>
            </div>

            <div class="profile-stats">
              <div>
                <span>Đã giới thiệu</span>
                <strong>{{ selectedProfile.referrals_count || 0 }}</strong>
              </div>
              <div>
                <span>Số dư khả dụng</span>
                <strong>{{ formatMoney(selectedProfile.available_balance) }}</strong>
              </div>
            </div>

            <label class="field">
              <span>Tỉ lệ hoa hồng</span>
              <div class="rate-editor">
                <input v-model="selectedProfile.commission_rate" type="number" min="0" max="100" step="0.1" />
                <span class="rate-suffix">%</span>
                <button type="button" @click="updateRate(selectedProfile)">Lưu</button>
              </div>
            </label>

            <div class="copy-card">
              <div>
                <span>Mã tiếp thị</span>
                <strong>{{ selectedProfile.affiliate_code }}</strong>
              </div>
              <button type="button" @click="copyText(selectedProfile.affiliate_code, 'Mã affiliate')">
                <ClipboardCopy :size="16" />
              </button>
            </div>

            <div class="copy-card">
              <div>
                <span>Link đăng ký</span>
                <strong>{{ affiliateLink(selectedProfile.affiliate_code) }}</strong>
              </div>
              <button type="button" @click="copyText(affiliateLink(selectedProfile.affiliate_code), 'Link affiliate')">
                <Link2 :size="16" />
              </button>
            </div>

            <div class="action-stack">
              <button class="action approve" type="button" :disabled="actionLoading === `profile-${selectedProfile.id}`" @click="changeProfileStatus(selectedProfile, 'active')">
                <UserCheck :size="18" />
                Duyệt / mở hoạt động
              </button>
              <button class="action warn" type="button" :disabled="actionLoading === `profile-${selectedProfile.id}`" @click="changeProfileStatus(selectedProfile, 'suspended')">
                <Ban :size="18" />
                Tạm khóa publisher
              </button>
              <button class="action danger" type="button" :disabled="actionLoading === `profile-${selectedProfile.id}`" @click="changeProfileStatus(selectedProfile, 'rejected')">
                <XCircle :size="18" />
                Từ chối hồ sơ
              </button>
            </div>
          </template>

          <div v-else class="empty-state compact">
            <MoreHorizontal :size="34" />
            <strong>Chọn một publisher</strong>
            <span>Chi tiết hồ sơ và thao tác nhanh sẽ hiển thị ở đây.</span>
          </div>
        </aside>
      </section>

      <section v-else-if="activeTab === 'commissions'" class="data-card">
        <div class="card-title-row">
          <div>
            <h2>Phê duyệt hoa hồng</h2>
            <p>{{ filteredCommissions.length }} giao dịch hoa hồng trong danh sách</p>
          </div>
          <BadgeCheck :size="20" />
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Đơn hàng</th>
                <th>Publisher</th>
                <th>Khách mua</th>
                <th>Giá trị đơn</th>
                <th>Hoa hồng</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in paginatedCommissions" :key="row.id">
                <td><strong>#{{ row.order_id }}</strong></td>
                <td>
                  <div class="person">
                    <span>{{ initials(row.affiliate_user?.name) }}</span>
                    <div>
                      <strong>{{ row.affiliate_user?.name || '-' }}</strong>
                      <small>{{ row.affiliate_user?.email || '-' }}</small>
                    </div>
                  </div>
                </td>
                <td>{{ row.referred_user?.name || '-' }}</td>
                <td>{{ formatMoney(row.order?.tongtien) }}</td>
                <td class="positive">{{ formatMoney(row.amount) }}</td>
                <td><span class="status-pill" :class="row.status">{{ statusLabelMap[row.status] || row.status }}</span></td>
                <td>
                  <div class="row-actions">
                    <button type="button" class="mini approve" :disabled="actionLoading === `commission-${row.id}`" @click="updateCommissionStatus(row, 'approved')">
                      <CheckCircle2 :size="16" />
                    </button>
                    <button type="button" class="mini paid" :disabled="actionLoading === `commission-${row.id}`" @click="updateCommissionStatus(row, 'paid')">
                      <ShieldCheck :size="16" />
                    </button>
                    <button type="button" class="mini danger" :disabled="actionLoading === `commission-${row.id}`" @click="updateCommissionStatus(row, 'cancelled')">
                      <XCircle :size="16" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredCommissions.length === 0">
                <td colspan="7">
                  <div class="empty-state table-empty">
                    <HandCoins :size="42" />
                    <strong>Chưa có hoa hồng phù hợp</strong>
                    <span>Các đơn phát sinh từ link affiliate sẽ xuất hiện tại đây.</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredCommissions.length > 0" class="pagination-bar">
          <div class="pagination-info">
            Hiển thị {{ pageStart }}-{{ pageEnd }} / {{ filteredCommissions.length }} giao dịch
          </div>
          <div class="pagination-actions">
            <label class="page-size">
              <span>Dòng/trang</span>
              <select v-model.number="itemsPerPage">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
              </select>
            </label>
            <button type="button" class="page-btn" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Trước</button>
            <span class="page-btn number active page-indicator">{{ currentPage }}/{{ totalPages }}</span>
            <button type="button" class="page-btn" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">Sau</button>
          </div>
        </div>
      </section>

      <section v-else class="data-card">
        <div class="card-title-row">
          <div>
            <h2>Yêu cầu rút tiền</h2>
            <p>{{ filteredWithdraws.length }} yêu cầu thanh toán từ publisher</p>
          </div>
          <WalletCards :size="20" />
        </div>

        <div class="withdraw-grid">
          <article v-for="row in paginatedWithdraws" :key="row.id" class="withdraw-card">
            <div class="withdraw-top">
              <div class="person">
                <span>{{ initials(row.affiliate_user?.name) }}</span>
                <div>
                  <strong>{{ row.affiliate_user?.name || '-' }}</strong>
                  <small>{{ row.affiliate_user?.email || '-' }}</small>
                </div>
              </div>
              <span class="status-pill" :class="row.status">{{ statusLabelMap[row.status] || row.status }}</span>
            </div>
            <div class="withdraw-amount">{{ formatMoney(row.amount) }}</div>
            <dl>
              <div>
                <dt>Ngân hàng</dt>
                <dd>{{ row.bank_name || '-' }}</dd>
              </div>
              <div>
                <dt>Chủ tài khoản</dt>
                <dd>{{ row.bank_account_name || '-' }}</dd>
              </div>
              <div>
                <dt>Số tài khoản</dt>
                <dd>
                  {{ row.bank_account_number || '-' }}
                  <button type="button" @click="copyText(row.bank_account_number, 'Số tài khoản')">
                    <ClipboardCopy :size="14" />
                  </button>
                </dd>
              </div>
              <div>
                <dt>Thời gian</dt>
                <dd>{{ formatDate(row.created_at) }}</dd>
              </div>
            </dl>
            <div class="withdraw-actions">
              <button type="button" class="action approve" :disabled="actionLoading === `withdraw-${row.id}`" @click="updateWithdrawStatus(row, 'approved')">
                <CheckCircle2 :size="17" />
                Duyệt
              </button>
              <button type="button" class="action paid" :disabled="actionLoading === `withdraw-${row.id}`" @click="updateWithdrawStatus(row, 'paid')">
                <Banknote :size="17" />
                Đã chuyển
              </button>
              <button type="button" class="action danger" :disabled="actionLoading === `withdraw-${row.id}`" @click="updateWithdrawStatus(row, 'rejected')">
                <XCircle :size="17" />
                Từ chối
              </button>
            </div>
          </article>

          <div v-if="filteredWithdraws.length === 0" class="empty-state">
            <WalletCards :size="42" />
            <strong>Chưa có yêu cầu rút tiền phù hợp</strong>
            <span>Publisher gửi yêu cầu rút tiền sẽ được đưa vào hàng chờ xử lý.</span>
          </div>
        </div>

        <div v-if="filteredWithdraws.length > 0" class="pagination-bar">
          <div class="pagination-info">
            Hiển thị {{ pageStart }}-{{ pageEnd }} / {{ filteredWithdraws.length }} yêu cầu
          </div>
          <div class="pagination-actions">
            <label class="page-size">
              <span>Dòng/trang</span>
              <select v-model.number="itemsPerPage">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
              </select>
            </label>
            <button type="button" class="page-btn" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Trước</button>
            <span class="page-btn number active page-indicator">{{ currentPage }}/{{ totalPages }}</span>
            <button type="button" class="page-btn" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">Sau</button>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.affiliate-admin {
  color: #0f172a;
  padding: 8px 0 28px;
}

.affiliate-hero {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 22px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #2563eb;
  font-size: 13px;
  font-weight: 800;
  margin-bottom: 10px;
  text-transform: uppercase;
}

.affiliate-hero h1 {
  margin: 0 0 8px;
  font-size: 30px;
  font-weight: 900;
  letter-spacing: 0;
}

.affiliate-hero p {
  margin: 0;
  max-width: 760px;
  color: #64748b;
  font-size: 15px;
  line-height: 1.6;
}

.hero-actions,
.filters,
.tabs,
.row-actions,
.withdraw-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn,
.tab-btn,
.action,
.mini,
.copy-card button,
.rate-editor button,
.withdraw-card dd button {
  border: 1px solid #dbe4f0;
  background: #fff;
  color: #1e293b;
  cursor: pointer;
  transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease, border-color .18s ease;
}

.btn:hover,
.tab-btn:hover,
.action:hover,
.mini:hover,
.copy-card button:hover,
.rate-editor button:hover,
.withdraw-card dd button:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(15, 23, 42, .10);
}

.btn:active,
.tab-btn:active,
.action:active,
.mini:active {
  transform: scale(.97);
}

.btn {
  height: 44px;
  border-radius: 999px;
  padding: 0 16px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 800;
}

.btn.primary {
  color: #fff;
  background: #2563eb;
  border-color: #2563eb;
}

.btn.ghost:hover {
  color: #2563eb;
  border-color: #bfdbfe;
  background: #eff6ff;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 18px;
}

.metric-card {
  min-height: 142px;
  border-radius: 8px;
  padding: 20px;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 16px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 18px 34px rgba(15, 23, 42, .10);
}

.metric-card::after {
  content: "";
  position: absolute;
  width: 140px;
  height: 140px;
  right: -42px;
  top: -42px;
  border-radius: 999px;
  background: rgba(255, 255, 255, .14);
}

.metric-card.blue { background: linear-gradient(135deg, #1d4ed8, #3b82f6); }
.metric-card.amber { background: linear-gradient(135deg, #c2410c, #f97316); }
.metric-card.teal { background: linear-gradient(135deg, #1d4ed8, #3b82f6); }
.metric-card.violet { background: linear-gradient(135deg, #1d4ed8, #2563eb); }

.metric-icon {
  width: 54px;
  height: 54px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  background: rgba(255, 255, 255, .18);
  flex: 0 0 auto;
}

.metric-card span,
.metric-card small {
  display: block;
  color: rgba(255, 255, 255, .86);
  font-weight: 700;
}

.metric-card span {
  font-size: 12px;
  text-transform: uppercase;
}

.metric-card strong {
  display: block;
  margin: 7px 0 4px;
  font-size: 28px;
  line-height: 1.1;
  font-weight: 900;
}

.metric-card small {
  font-size: 12px;
}

.control-panel,
.data-card,
.loading-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, .05);
}

.control-panel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px;
  margin-bottom: 18px;
}

.tab-btn {
  height: 42px;
  padding: 0 14px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 800;
}

.tab-btn span {
  min-width: 24px;
  height: 24px;
  padding: 0 7px;
  border-radius: 999px;
  display: inline-grid;
  place-items: center;
  background: #eef2ff;
  color: #2563eb;
  font-size: 12px;
}

.tab-btn.active {
  color: #fff;
  background: #2563eb;
  border-color: #2563eb;
}

.tab-btn.active span {
  color: #1d4ed8;
  background: #fff;
}

.search-box,
.select-box {
  height: 44px;
  border: 1px solid #dbe4f0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 12px;
  background: #fff;
}

.search-box {
  width: min(460px, 42vw);
}

.search-box input,
.select-box select,
.rate-editor input {
  border: 0;
  outline: none;
  background: transparent;
  color: #0f172a;
  font: inherit;
}

.search-box input {
  width: 100%;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(390px, 440px);
  gap: 18px;
  align-items: start;
}

.data-card {
  overflow: hidden;
}

.data-card.wide {
  min-width: 0;
}

.card-title-row {
  min-height: 76px;
  padding: 18px 22px;
  border-bottom: 1px solid #edf2f7;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.card-title-row h2 {
  margin: 0 0 4px;
  font-size: 18px;
  font-weight: 900;
}

.card-title-row p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
}

.publisher-list {
  padding: 8px;
  max-height: 640px;
  overflow-y: auto;
  overflow-x: hidden;
}

.publisher-row {
  width: 100%;
  min-height: 78px;
  display: grid;
  grid-template-columns: 48px minmax(160px, 1fr) minmax(84px, .48fr) 72px minmax(104px, .62fr) minmax(122px, .68fr);
  align-items: center;
  gap: 12px;
  border: 0;
  border-radius: 8px;
  background: #fff;
  padding: 10px 12px;
  text-align: left;
  color: #0f172a;
  cursor: pointer;
  transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
}

.publisher-row > svg {
  display: none;
}

.publisher-row:hover,
.publisher-row.selected {
  background: #eff6ff;
  box-shadow: inset 3px 0 0 #2563eb;
}

.avatar,
.person > span {
  width: 42px;
  height: 42px;
  border-radius: 999px;
  display: grid;
  place-items: center;
  color: #fff;
  background: linear-gradient(135deg, #2563eb, #2563eb);
  font-weight: 900;
}

.avatar.large {
  width: 62px;
  height: 62px;
  font-size: 20px;
}

.publisher-main strong,
.publisher-main span {
  display: block;
}

.publisher-main span,
.person small {
  color: #64748b;
  font-size: 13px;
}

.publisher-row code,
.copy-card strong {
  color: #1d4ed8;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-weight: 800;
}

.rate,
.money {
  font-weight: 900;
}

.money,
.positive {
  color: #1D4ED8;
  font-weight: 900;
}

.status-pill {
  min-height: 30px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 12px;
  font-size: 12px;
  font-weight: 900;
  white-space: nowrap;
}

.status-pill.pending { color: #b45309; background: #fef3c7; }
.status-pill.active,
.status-pill.approved { color: #1E40AF; background: #d1fae5; }
.status-pill.paid { color: #1d4ed8; background: #dbeafe; }
.status-pill.suspended { color: #92400e; background: #ffedd5; }
.status-pill.rejected,
.status-pill.cancelled { color: #b91c1c; background: #fee2e2; }

.profile-panel {
  padding: 0;
  align-self: start;
  position: sticky;
  top: 18px;
  background:
    linear-gradient(180deg, #f8fbff 0%, #ffffff 155px),
    #ffffff;
}

.profile-head {
  display: flex;
  gap: 14px;
  align-items: center;
  margin: 0;
  padding: 22px;
  border-bottom: 1px solid #edf2f7;
}

.profile-title {
  min-width: 0;
}

.profile-head h3 {
  margin: 0 0 5px;
  font-size: 18px;
  font-weight: 900;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-head p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-status {
  margin-top: 10px;
}

.profile-stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  padding: 16px 22px 0;
  margin-bottom: 0;
}

.profile-stats div {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 14px;
  background: #ffffff;
  box-shadow: 0 10px 22px rgba(15, 23, 42, .04);
}

.profile-stats span,
.field span,
.copy-card span {
  display: block;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
}

.profile-stats strong {
  display: block;
  margin-top: 5px;
  font-size: 18px;
}

.field {
  display: block;
  margin: 0;
  padding: 16px 22px 0;
}

.rate-editor {
  margin-top: 8px;
  height: 48px;
  border: 1px solid #dbe4f0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  overflow: hidden;
  background: #fff;
  box-shadow: inset 0 1px 0 rgba(15, 23, 42, .02);
}

.rate-editor input {
  width: 100%;
  padding: 0 12px 0 14px;
  font-weight: 900;
}

.rate-suffix {
  color: #64748b;
  font-weight: 900;
  padding-right: 12px;
}

.rate-editor button {
  height: 100%;
  border-width: 0 0 0 1px;
  padding: 0 18px;
  color: #2563eb;
  font-weight: 900;
  background: #f8fafc;
}

.copy-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 13px 12px;
  margin: 14px 22px 0;
  background: #f8fafc;
  min-width: 0;
  transition: border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.copy-card:hover {
  border-color: #bfdbfe;
  background: #f9fbff;
  box-shadow: 0 12px 24px rgba(37, 99, 235, .08);
}

.copy-card > div {
  min-width: 0;
}

.copy-card strong {
  display: block;
  margin-top: 5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.copy-card button {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
}

.copy-card button:hover {
  transform: translateY(-2px);
}

.action-stack {
  display: grid;
  gap: 10px;
  margin-top: 18px;
  padding: 0 22px 22px;
}

.action {
  min-height: 42px;
  border-radius: 8px;
  padding: 0 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-weight: 900;
}

.action.approve,
.mini.approve {
  color: #1E40AF;
  background: #ecfdf5;
  border-color: #a7f3d0;
}

.action.paid,
.mini.paid {
  color: #1d4ed8;
  background: #eff6ff;
  border-color: #bfdbfe;
}

.action.warn {
  color: #b45309;
  background: #fffbeb;
  border-color: #fde68a;
}

.action.danger,
.mini.danger {
  color: #b91c1c;
  background: #fef2f2;
  border-color: #fecaca;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background: #f8fafc;
  color: #475569;
  text-align: left;
  padding: 15px 18px;
  font-size: 12px;
  text-transform: uppercase;
}

td {
  border-top: 1px solid #edf2f7;
  padding: 15px 18px;
  color: #1e293b;
}

.person {
  display: flex;
  align-items: center;
  gap: 10px;
}

.person > span {
  width: 36px;
  height: 36px;
  font-size: 12px;
}

.person strong,
.person small {
  display: block;
}

.mini {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: grid;
  place-items: center;
}

.withdraw-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  padding: 18px;
}

.withdraw-card {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  background: #fff;
}

.withdraw-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.withdraw-amount {
  margin: 18px 0;
  font-size: 26px;
  font-weight: 900;
  color: #1d4ed8;
}

.withdraw-card dl {
  margin: 0 0 16px;
  display: grid;
  gap: 10px;
}

.withdraw-card dl div {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px dashed #e2e8f0;
  padding-bottom: 9px;
}

.withdraw-card dt {
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
}

.withdraw-card dd {
  margin: 0;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 800;
  text-align: right;
}

.withdraw-card dd button {
  width: 26px;
  height: 26px;
  border-radius: 7px;
  display: grid;
  place-items: center;
}

.pagination-bar {
  min-height: 70px;
  border-top: 1px solid #edf2f7;
  padding: 14px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  background: #fff;
}

.pagination-info {
  color: #64748b;
  font-size: 13px;
  font-weight: 800;
}

.pagination-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.page-size {
  height: 38px;
  border: 1px solid #dbe4f0;
  border-radius: 8px;
  padding: 0 10px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #f8fafc;
}

.page-size span {
  color: #64748b;
  font-size: 12px;
  font-weight: 900;
}

.page-size select {
  border: 0;
  outline: none;
  background: transparent;
  color: #0f172a;
  font-weight: 900;
}

.page-btn {
  min-width: 38px;
  height: 38px;
  border: 1px solid #dbe4f0;
  border-radius: 8px;
  padding: 0 12px;
  background: #fff;
  color: #334155;
  font-weight: 900;
  cursor: pointer;
  transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease, border-color .18s ease;
}

.page-btn:hover:not(:disabled) {
  color: #2563eb;
  border-color: #bfdbfe;
  background: #eff6ff;
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(37, 99, 235, .14);
}

.page-btn.active {
  color: #fff;
  background: #2563eb;
  border-color: #2563eb;
  box-shadow: 0 10px 20px rgba(37, 99, 235, .20);
}

.page-btn:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.loading-card,
.empty-state {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #94a3b8;
  text-align: center;
}

.empty-state strong {
  color: #334155;
}

.empty-state.compact {
  min-height: 460px;
}

.table-empty {
  min-height: 260px;
}

.spin {
  animation: spin 1s linear infinite;
}

button:disabled {
  opacity: .6;
  cursor: not-allowed;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 1280px) {
  .metrics-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .content-grid,
  .withdraw-grid {
    grid-template-columns: 1fr;
  }

  .profile-panel {
    order: -1;
  }
}

@media (max-width: 860px) {
  .affiliate-hero,
  .control-panel,
  .filters {
    align-items: stretch;
    flex-direction: column;
  }

  .tabs {
    overflow-x: auto;
  }

  .search-box {
    width: 100%;
  }

  .metrics-grid {
    grid-template-columns: 1fr;
  }

  .publisher-row {
    grid-template-columns: 42px minmax(0, 1fr);
  }

  .publisher-row code,
  .publisher-row .rate,
  .publisher-row .money,
  .publisher-row .status-pill,
  .publisher-row > svg {
    display: none;
  }

  .pagination-bar {
    align-items: stretch;
    flex-direction: column;
  }

  .pagination-actions {
    justify-content: flex-start;
  }
}
</style>
