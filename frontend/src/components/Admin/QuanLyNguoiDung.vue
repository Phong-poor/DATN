<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { getUser } from '../../services/auth.js'
import * as XLSX from 'xlsx'
import BulkDeleteToolbar from './ThanhXoaHangLoat.vue'
import { useAdminBulkDelete } from '@/services/adminBulkDelete'

// ─── API ─────────────────────────────
// ─── STATE ───────────────────────────
const searchQuery = ref('')
const activeTab = ref('Tất cả')
const selectedStatus = ref('Tất cả')
const isOpenStatusDropdown = ref(false)

const currentUser = ref(null)

const showModal = ref(false)
const showEditModal = ref(false)

const formError = ref('')
const editError = ref('')

const users = ref([])
const loading = ref(false)
const router = useRouter()
const pendingAffiliateRequests = ref(0)
const hideAffiliateCard = ref(false)

// ─── PAGINATION ──────────────────────
const currentPage = ref(1)
const pageSize = 7

const tabs = ['Tất cả', 'Admin', 'Khách hàng']
const statusOptions = ['Tất cả', 'Hoạt động', 'Bị khóa']

const roleStyle = ref({
    'ADMIN': { bg: '#fee2e2', color: '#b91c1c' },
    'KHÁCH HÀNG': { bg: '#dcfce7', color: '#15803d' },
    'THỦ KHO': { bg: '#ffedd5', color: '#ea580c' },
    'XỬ LÝ ĐƠN HÀNG': { bg: '#e0f2fe', color: '#0369a1' },
    'MARKETING': { bg: '#fce7f3', color: '#db2777' },
    'QUẢN LÝ AFFILIATE': { bg: '#f3e8ff', color: '#1d4ed8' },
    'BIÊN TẬP VIÊN': { bg: '#e0e7ff', color: '#1d4ed8' },
    'TƯ VẤN VIÊN': { bg: '#ccfbf1', color: '#1d4ed8' },
    'KẾ TOÁN': { bg: '#fae8ff', color: '#a21caf' }
})

const statusStyle = {
    'Hoạt động': { color: '#2563eb' },
    'Bị khóa': { color: '#dc2626' }
}

const roleLabelMapFixed = ref({
    admin: 'ADMIN',
    user: 'KHÁCH HÀNG',
    inventory: 'THỦ KHO',
    order_manager: 'XỬ LÝ ĐƠN HÀNG',
    marketing: 'MARKETING',
    affiliate_manager: 'QUẢN LÝ AFFILIATE',
    editor: 'BIÊN TẬP VIÊN',
    support: 'TƯ VẤN VIÊN',
    accountant: 'KẾ TOÁN'
})

const roleValueMapFixed = computed(() => {
    return Object.fromEntries(Object.entries(roleLabelMapFixed.value).map(([value, label]) => [label, value]))
})

const staffRoles = computed(() => {
    return Object.values(roleLabelMapFixed.value).filter(lbl => lbl !== 'KHÁCH HÀNG')
})

const mapRoleLabel = (role) => roleLabelMapFixed.value[String(role || '').toLowerCase()] || 'KHÁCH HÀNG'
const mapRoleValue = (label) => roleValueMapFixed.value[label] || 'user'
const mapStatusLabel = (status) => String(status || '').toLowerCase() === 'locked' ? 'Bị khóa' : 'Hoạt động'
const mapStatusValue = (label) => String(label || '').toLowerCase().includes('khóa') ? 'locked' : 'active'

const protectedDeleteEmails = ['nextgenshop@gmail.com']
const isProtectedDeleteUser = (user) => protectedDeleteEmails.includes(String(user?.email || '').toLowerCase())

const normalizeUser = (u) => ({
    id: u.id,
    name: u.ten || u.name || '',
    email: u.email || '',
    phone: u.sodienthoai || u.phone || '',
    role: mapRoleLabel(u.vaitro || u.role),
    joined: u.created_at
        ? new Date(u.created_at).toLocaleDateString('vi-VN')
        : '',
    status: mapStatusLabel(u.trangthai || u.status)
})

// ─── FETCH ───────────────────────────
const fetchUsers = async () => {
    loading.value = true
    try {
        const { data } = await api.get('/admin/users')
        users.value = Array.isArray(data) ? data.map(normalizeUser) : []
    } catch (err) {
        console.error('Load users failed:', err.response?.data || err.message)
    } finally {
        loading.value = false
    }
}

const fetchPendingAffiliateRequests = async () => {
    try {
        const { data } = await api.get('/admin/affiliates')
        const profiles = Array.isArray(data?.profiles) ? data.profiles : []
        pendingAffiliateRequests.value = profiles.filter(p => String(p?.status || '').toLowerCase() === 'pending').length
    } catch {
        pendingAffiliateRequests.value = 0
    }
}

const closeStatusDropdown = (e) => {
    if (!e.target.closest('.status-filter-dropdown')) {
        isOpenStatusDropdown.value = false
    }
}

const fetchRoles = async () => {
    try {
        const { data } = await api.get('/admin/vaitro')
        if (data?.success && Array.isArray(data?.data)) {
            data.data.forEach(role => {
                const ma = String(role.ma_vaitro).toLowerCase()
                const ten = String(role.ten_vaitro).toUpperCase()
                roleLabelMapFixed.value[ma] = ten
                if (!roleStyle.value[ten]) {
                    roleStyle.value[ten] = { bg: '#f1f5f9', color: '#475569' }
                }
            })
        }
    } catch (err) {
        console.error('Fetch roles failed in user management:', err)
    }
}

onMounted(async () => {
    currentUser.value = getUser()
    await fetchRoles()
    fetchUsers()
    fetchPendingAffiliateRequests()
    document.addEventListener('click', closeStatusDropdown)
})

onUnmounted(() => {
    document.removeEventListener('click', closeStatusDropdown)
})

const openAffiliateList = () => {
    router.push('/admin/quan-ly-tiep-thi')
}

const dismissAffiliateCard = () => {
    hideAffiliateCard.value = true
}

// ─── FILTER (reset page khi search/tab thay đổi) ──
const filtered = computed(() => {
    const q = searchQuery.value.toLowerCase()
    const tab = String(activeTab.value || '').toLowerCase()
    const statusFilter = String(selectedStatus.value || '').toLowerCase()
    const map = {
        'Admin': 'ADMIN',
        'Khách hàng': 'KHÁCH HÀNG'
    }
    return users.value.filter(u => {
        const matchSearch =
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q)
        const isCustomer = u.role === 'KHÁCH HÀNG'
        const matchTab =
            activeTab.value === 'Tất cả' ||
            tab.includes('admin') && !isCustomer ||
            tab.includes('khách') && isCustomer
        const matchStatus =
            selectedStatus.value === 'Tất cả' ||
            statusFilter.includes('tất') ||
            u.status === selectedStatus.value
        return matchSearch && matchTab && matchStatus
    })
})

// Reset về trang 1 khi search/tab thay đổi
const onSearch = () => { currentPage.value = 1 }
const onTabChange = (t) => { activeTab.value = t; currentPage.value = 1 }
const onStatusChange = () => { currentPage.value = 1 }

const resetAdvancedFilters = () => {
    searchQuery.value = ''
    activeTab.value = 'Tất cả'
    selectedStatus.value = 'Tất cả'
    currentPage.value = 1
}

const exportUsersReport = () => {
    const rows = filtered.value.map((u, idx) => ({
        STT: idx + 1,
        'Họ tên': u.name,
        Email: u.email,
        'Số điện thoại': u.phone || '',
        'Vai trò': u.role,
        'Ngày tham gia': u.joined,
        'Trạng thái': u.status,
    }))

    const ws = XLSX.utils.json_to_sheet(rows)
    ws['!cols'] = [
        { wch: 8 },
        { wch: 24 },
        { wch: 30 },
        { wch: 16 },
        { wch: 14 },
        { wch: 14 },
        { wch: 14 },
    ]
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'NguoiDung')
    XLSX.writeFile(wb, `bao-cao-nguoi-dung-${Date.now()}.xlsx`)
}

// ─── PAGINATION COMPUTED ─────────────
const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize)))

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * pageSize
    return filtered.value.slice(start, start + pageSize)
})

const {
    selectedIds,
    isBulkDeleting,
    allCurrentPageSelected,
    toggleItemSelection,
    toggleCurrentPageSelection,
    clearSelection,
    removeSelected,
    removeAllFiltered,
} = useAdminBulkDelete({
    items: users,
    filteredItems: filtered,
    pageItems: paginatedUsers,
    getId: item => item.id,
    endpoint: id => `/admin/users/${id}`,
    entityLabel: 'người dùng',
    fetchItems: fetchUsers,
    canDelete: item => item.id !== currentUser.value?.id && !isProtectedDeleteUser(item),
    cannotDeleteMessage: 'Một số tài khoản không thể xóa, ví dụ tài khoản đang đăng nhập.',
})

// Dãy số trang hiển thị (tối đa 5 nút)
const pageNumbers = computed(() => {
    return []
})

const goToPage = (p) => {
    if (p >= 1 && p <= totalPages.value) currentPage.value = p
}

// ─── AVATAR ──────────────────────────
const avatarColors = ['#dbeafe', '#dcfce7', '#ede9fe', '#fef9c3', '#fee2e2', '#ffedd5']
const avatarText = ['#1d4ed8', '#1d4ed8', '#1d4ed8', '#a16207', '#b91c1c', '#c2410c']

const getAvatarStyle = (name) => {
    const i = (name || 'A').charCodeAt(0) % avatarColors.length
    return { background: avatarColors[i], color: avatarText[i] }
}

const initials = (name) =>
    name?.trim().split(' ').map(w => w[0]).slice(-2).join('').toUpperCase()

// ─── VALIDATE ────────────────────────
const validateUser = (f, isEdit = false) => {
    if (!f.name?.trim()) return 'Nhập họ tên'
    if (!f.email?.trim()) return 'Nhập email'
    if (!isEdit && !f.password) return 'Nhập mật khẩu'
    if (f.password && f.password.length < 8) return 'Mật khẩu >= 8 ký tự'
    return null
}

// ─── CUSTOM CONFIRM MODAL ────────────
const confirmModal = ref({
    show: false,
    title: '',
    message: '',
    subMessage: '',
    confirmText: '',
    confirmColor: 'blue', // 'blue' | 'red' | 'orange'
    icon: 'lock',         // 'lock' | 'unlock' | 'trash'
    onConfirm: null
})

const showConfirm = ({ title, message, subMessage = '', confirmText, confirmColor, icon, onConfirm }) => {
    confirmModal.value = { show: true, title, message, subMessage, confirmText, confirmColor, icon, onConfirm }
}

const closeConfirm = () => {
    confirmModal.value.show = false
}

const handleConfirm = async () => {
    if (confirmModal.value.onConfirm) await confirmModal.value.onConfirm()
    closeConfirm()
}

// ─── TOGGLE STATUS (dùng custom modal) ──
const toggleStatus = (u) => {
    const isLocking = u.status === 'Hoạt động'
    showConfirm({
        title: isLocking ? 'Khóa tài khoản' : 'Mở khóa tài khoản',
        message: isLocking
            ? `Bạn có chắc chắn muốn khóa tài khoản này không?`
            : `Bạn có chắc chắn muốn mở khóa tài khoản này không?`,
        subMessage: `Người dùng: ${u.name}`,
        confirmText: isLocking ? 'Khóa tài khoản' : 'Mở khóa',
        confirmColor: isLocking ? 'orange' : 'blue',
        icon: isLocking ? 'lock' : 'unlock',
        onConfirm: async () => {
            const next = isLocking ? 'Bị khóa' : 'Hoạt động'
            try {
                await api.put(`/admin/users/${u.id}`, { trangthai: mapStatusValue(next) })
                u.status = next
            } catch (err) {
                console.error('Toggle user status failed:', err)
            }
        }
    })
}

// ─── DELETE (dùng custom modal) ──────
const removeUser = (id) => {
    const user = users.value.find(u => u.id === id)
    showConfirm({
        title: 'Xóa người dùng',
        message: 'Hành động này không thể hoàn tác. Tài khoản sẽ bị xóa vĩnh viễn khỏi hệ thống.',
        subMessage: `Người dùng: ${user?.name || ''}`,
        confirmText: 'Xóa vĩnh viễn',
        confirmColor: 'red',
        icon: 'trash',
        onConfirm: async () => {
            try {
                await api.delete(`/admin/users/${id}`)
                users.value = users.value.filter(u => u.id !== id)
                if (paginatedUsers.value.length === 0 && currentPage.value > 1) {
                    currentPage.value--
                }
            } catch (err) {
                console.error('Delete user failed:', err)
            }
        }
    })
}

// ─── CREATE ──────────────────────────
const defaultForm = () => ({
    name: '', email: '', phone: '', role: 'KHÁCH HÀNG',
    status: 'Hoạt động', password: ''
})

const form = ref(defaultForm())

const openModal = () => {
    form.value = defaultForm()
    formError.value = ''
    showModal.value = true
}

const closeModal = () => showModal.value = false

const submitForm = async () => {
    const err = validateUser(form.value)
    if (err) return formError.value = err

    try {
        const { data } = await api.post('/admin/users', {
            ten: form.value.name,
            email: form.value.email,
            sodienthoai: form.value.phone,
            vaitro: mapRoleValue(form.value.role),
            trangthai: mapStatusValue(form.value.status),
            matkhau: form.value.password,
            matkhau_confirmation: form.value.password
        })
        users.value.unshift(normalizeUser(data.user))
        currentPage.value = 1
        closeModal()
    } catch (err) {
        formError.value = err.response?.data?.message || 'Lỗi tạo user'
    }
}

// ─── EDIT ────────────────────────────
const editingUser = ref(null)
const editForm = ref({})

const openEditModal = (u) => {
    editingUser.value = u
    editForm.value = { ...u, password: '' }
    editError.value = ''
    showEditModal.value = true
}

const closeEditModal = () => {
    showEditModal.value = false
    editingUser.value = null
}

const submitEdit = async () => {
    const err = validateUser(editForm.value, true)
    if (err) return editError.value = err

    const payload = {
        ten: editForm.value.name,
        email: editForm.value.email,
        sodienthoai: editForm.value.phone,
        vaitro: mapRoleValue(editForm.value.role),
        trangthai: mapStatusValue(editForm.value.status),
        matkhau: editForm.value.password || undefined,
        matkhau_confirmation: editForm.value.password || undefined,
    }
    if (!payload.matkhau) { delete payload.matkhau; delete payload.matkhau_confirmation }

    try {
        const { data } = await api.put(`/admin/users/${editingUser.value.id}`, payload)
        const i = users.value.findIndex(u => u.id === editingUser.value.id)
        if (i !== -1) users.value[i] = normalizeUser(data.user)
        closeEditModal()
    } catch (err) {
        editError.value = err.response?.data?.message || 'Update lỗi'
    }
}
</script>

<template>
    <div class="page">

        <!-- STATS -->
        <div class="stats">
            <div class="stat-card stat-blue">
                <div class="stat-info">
                    <p>TỔNG NGƯỜI DÙNG</p>
                    <div class="stat-val-row">
                        <b>{{ users.length }}</b>
                        <span class="badge-up">+12%</span>
                    </div>
                </div>
            </div>
            <div class="stat-card stat-teal">
                <div class="stat-info">
                    <p>ĐANG HOẠT ĐỘNG</p>
                    <div class="stat-val-row">
                        <b>{{users.filter(u => u.status === 'Hoạt động').length}}</b>
                        <span class="badge-neutral">Ổn định</span>
                    </div>
                </div>
            </div>
            <div class="stat-card stat-orange">
                <div class="stat-info">
                    <p>BỊ KHÓA</p>
                    <div class="stat-val-row">
                        <b>{{users.filter(u => u.status === 'Bị khóa').length}}</b>
                        <span class="badge-down">-5%</span>
                    </div>
                </div>
            </div>
            <div class="stat-card dark-stat">
                <div class="stat-info">
                    <p>TĂNG TRƯỞNG THÁNG</p>
                    <b class="big-growth">2.4k</b>
                </div>
                <svg class="trend-svg" viewBox="0 0 80 40" fill="none">
                    <polyline points="0,35 15,28 30,30 45,18 60,22 75,8" stroke="#60a5fa" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round" fill="none" />
                </svg>
            </div>
        </div>

        <!-- FILTER ROW -->
        <div class="filter-row">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.35-4.35" />
                </svg>
                <input v-model="searchQuery" @input="onSearch"
                    placeholder="Tìm kiếm người dùng, email..." />
            </div>
            <div class="tabs-group">
                <button v-for="t in tabs" :key="t" class="tab" :class="{ active: activeTab === t }"
                    @click="onTabChange(t)">{{ t }}</button>
                <div class="tab-divider"></div>
                <div class="tab status-tab">
                    <span>Trạng thái:</span>
                    <div class="custom-dropdown status-filter-dropdown">
                        <div class="dropdown-trigger" @click.stop="isOpenStatusDropdown = !isOpenStatusDropdown">
                            <span>{{ selectedStatus }}</span>
                            <svg class="chevron" :class="{ open: isOpenStatusDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <transition name="fade-slide">
                            <ul v-if="isOpenStatusDropdown" class="dropdown-menu">
                                <li v-for="s in statusOptions" :key="s"
                                    :class="{ active: selectedStatus === s }"
                                    @click="selectedStatus = s; onStatusChange(); isOpenStatusDropdown = false">
                                    {{ s }}
                                </li>
                            </ul>
                        </transition>
                    </div>
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn-filter" @click="resetAdvancedFilters">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        style="width:14px;height:14px">
                        <line x1="4" y1="6" x2="20" y2="6" />
                        <line x1="8" y1="12" x2="16" y2="12" />
                        <line x1="11" y1="18" x2="13" y2="18" />
                    </svg>
                    Đặt lại bộ lọc
                </button>
                <button class="btn-export" @click="exportUsersReport" :disabled="filtered.length === 0">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        style="width:14px;height:14px">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                    Xuất báo cáo
                </button>
                <button class="btn-new-user" @click="openModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                        style="width:14px;height:14px">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    Thêm người dùng
                </button>
            </div>
        </div>

        <BulkDeleteToolbar
            class="users-bulk-toolbar"
            :selected-count="selectedIds.length"
            :total-count="filtered.length"
            label="người dùng"
            :loading="isBulkDeleting"
            @clear="clearSelection"
            @delete-selected="removeSelected"
            @delete-all="removeAllFiltered"
        />

        <!-- TABLE -->
        <div class="table-wrap">
            <div v-if="loading" class="loading-row">Đang tải dữ liệu...</div>
            <table v-else>
                <thead>
                    <tr>
                        <th class="select-col">
                            <input type="checkbox" :checked="allCurrentPageSelected" :disabled="!paginatedUsers.length" @change="toggleCurrentPageSelection" />
                        </th>
                        <th>NGƯỜI DÙNG</th>
                        <th>VAI TRÒ</th>
                        <th>NGÀY THAM GIA</th>
                        <th>TRẠNG THÁI</th>
                        <th>THAO TÁC</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="paginatedUsers.length === 0">
                        <td colspan="6" class="empty">Không tìm thấy người dùng nào.</td>
                    </tr>
                    <tr v-for="u in paginatedUsers" :key="u.id" :class="{ 'row-selected': selectedIds.includes(u.id) }">
                        <td class="select-col">
                            <input type="checkbox" :checked="selectedIds.includes(u.id)" :disabled="u.id === currentUser?.id || isProtectedDeleteUser(u)" @change="toggleItemSelection(u.id)" />
                        </td>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar" :style="getAvatarStyle(u.name)">{{ initials(u.name) }}</div>
                                <div>
                                    <b>{{ u.name }}</b>
                                    <span>{{ u.email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge"
                                :style="{ background: roleStyle[u.role]?.bg || '#f1f5f9', color: roleStyle[u.role]?.color || '#475569' }">
                                {{ u.role }}
                            </span>
                        </td>
                        <td class="date-cell">{{ u.joined }}</td>
                        <td>
                            <span class="status-dot" :style="{ color: statusStyle[u.status]?.color }">
                                ● {{ u.status }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <!-- Sửa -->
                                <button class="act-btn" title="Chỉnh sửa" @click="openEditModal(u)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <!-- Khóa / Mở khóa (có confirm) -->
                                <button class="act-btn" :class="{ 'lock-active': u.status === 'Bị khóa' }"
                                    :title="u.id === currentUser?.id ? 'Không thể tự khóa tài khoản' : (u.status === 'Hoạt động' ? 'Khóa tài khoản' : 'Mở khóa tài khoản')"
                                    :disabled="u.id === currentUser?.id"
                                    :style="u.id === currentUser?.id ? 'opacity: 0.4; cursor: not-allowed' : ''"
                                    @click="toggleStatus(u)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                        <path v-if="u.status === 'Hoạt động'" d="M7 11V7a5 5 0 0 1 10 0v4" />
                                        <path v-else d="M7 11V7a5 5 0 0 1 9.9-1" />
                                    </svg>
                                </button>
                                <!-- Xóa -->
                                <button v-if="!isProtectedDeleteUser(u)" class="act-btn danger"
                                    :title="u.id === currentUser?.id ? 'Không thể tự xóa tài khoản' : 'Xóa'"
                                    :disabled="u.id === currentUser?.id"
                                    :style="u.id === currentUser?.id ? 'opacity: 0.4; cursor: not-allowed' : ''"
                                    @click="removeUser(u.id)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                        <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div class="table-footer">
            <span class="showing">
                Hiển thị {{ filtered.length === 0 ? 0 : (currentPage - 1) * pageSize + 1 }} –
                {{ Math.min(currentPage * pageSize, filtered.length) }}
                của {{ filtered.length }} người dùng
            </span>
            <div class="pagination">
                <button :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">‹</button>

                <!-- Nút trang đầu nếu không hiển thị -->
                <span class="active page-indicator">{{ currentPage }}/{{ totalPages }}</span>
                <template v-if="pageNumbers[0] > 1">
                    <button @click="goToPage(1)">1</button>
                    <button class="dots" v-if="pageNumbers[0] > 2" disabled>...</button>
                </template>

                <button v-for="p in pageNumbers" :key="p" :class="{ active: currentPage === p }" @click="goToPage(p)">{{
                    p }}</button>

                <!-- Nút trang cuối nếu không hiển thị -->
                <template v-if="pageNumbers[pageNumbers.length - 1] < totalPages">
                    <button class="dots" v-if="pageNumbers[pageNumbers.length - 1] < totalPages - 1"
                        disabled>...</button>
                    <button @click="goToPage(totalPages)">{{ totalPages }}</button>
                </template>

                <button :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">›</button>
            </div>
        </div>

        <!-- BOTTOM CARDS -->
        <div class="bottom-grid">
            <div class="bottom-card" v-if="!hideAffiliateCard">
                <div class="bottom-icon orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </div>
                <div class="bottom-text">
                    <h4>Yêu cầu đăng ký mới</h4>
                    <p>Hiện có {{ pendingAffiliateRequests }} yêu cầu đăng ký đang chờ phê duyệt từ hệ thống VinaTech Partner.</p>
                    <div class="bottom-actions">
                        <button class="btn-primary-sm" @click="openAffiliateList">Xem danh sách</button>
                        <button class="btn-ghost-sm" @click="dismissAffiliateCard">Để sau</button>
                    </div>
                </div>
            </div>
            <div class="bottom-card security-card">
                <div class="security-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                </div>
                <div class="bottom-text">
                    <h4>Bảo mật hệ thống</h4>
                    <p>Mọi tài khoản người dùng đều được ghi lại trong nhật ký hệ thống để đảm bảo an toàn.</p>
                </div>
            </div>
        </div>

        <!-- ─── MODAL TẠO MỚI ─── -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay">
                <div class="modal">
                    <div class="modal-header">
                        <h3>Thêm người dùng mới</h3>
                        <button class="modal-close" @click="closeModal">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>HỌ TÊN <span class="req">*</span></label>
                                <input v-model="form.name" placeholder="VD: Nguyễn Văn A" />
                            </div>
                            <div class="form-group">
                                <label>EMAIL <span class="req">*</span></label>
                                <input v-model="form.email" type="email" placeholder="VD: user@vinatech.com" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>SỐ ĐIỆN THOẠI</label>
                                <input v-model="form.phone" placeholder="VD: 0901234567" />
                            </div>
                            <div class="form-group">
                                <label>MẬT KHẨU <span class="req">*</span></label>
                                <input v-model="form.password" type="password" placeholder="Tối thiểu 8 ký tự" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>VAI TRÒ</label>
                                <select v-model="form.role">
                                    <option>KHÁCH HÀNG</option>
                                    <option v-for="r in staffRoles" :key="r">{{ r }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>TRẠNG THÁI</label>
                                <select v-model="form.status">
                                    <option>Hoạt động</option>
                                    <option>Bị khóa</option>
                                </select>
                            </div>
                        </div>
                        <p v-if="formError" class="form-error">⚠ {{ formError }}</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" @click="closeModal">Hủy</button>
                        <button class="btn-submit" @click="submitForm">Tạo người dùng</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── CUSTOM CONFIRM MODAL ─── -->
        <Teleport to="body">
            <Transition name="confirm-fade">
                <div v-if="confirmModal.show" class="confirm-overlay" @click.self="closeConfirm">
                    <div class="confirm-box" :class="`confirm-box--${confirmModal.confirmColor}`">
                        <!-- Icon vùng -->
                        <div class="confirm-icon-wrap" :class="`confirm-icon--${confirmModal.confirmColor}`">
                            <!-- Lock icon -->
                            <svg v-if="confirmModal.icon === 'lock'" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            <!-- Unlock icon -->
                            <svg v-else-if="confirmModal.icon === 'unlock'" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 9.9-1" />
                            </svg>
                            <!-- Trash icon -->
                            <svg v-else-if="confirmModal.icon === 'trash'" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                            </svg>
                        </div>

                        <div class="confirm-content">
                            <h4>{{ confirmModal.title }}</h4>
                            <p>{{ confirmModal.message }}</p>
                            <div v-if="confirmModal.subMessage" class="confirm-sub">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                {{ confirmModal.subMessage }}
                            </div>
                        </div>

                        <div class="confirm-actions">
                            <button class="confirm-cancel" @click="closeConfirm">Hủy bỏ</button>
                            <button class="confirm-ok" :class="`confirm-ok--${confirmModal.confirmColor}`"
                                @click="handleConfirm">
                                {{ confirmModal.confirmText }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ─── MODAL CHỈNH SỬA ─── -->
        <Teleport to="body">
            <div v-if="showEditModal" class="modal-overlay">
                <div class="modal">
                    <div class="modal-header">
                        <div class="modal-title-wrap">
                            <h3>Chỉnh sửa người dùng</h3>
                            <span class="modal-sub">ID: #{{ editingUser?.id }}</span>
                        </div>
                        <button class="modal-close" @click="closeEditModal">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>VAI TRÒ / PHÂN QUYỀN</label>
                                <select v-model="editForm.role" :disabled="editingUser?.id === currentUser?.id"
                                    :title="editingUser?.id === currentUser?.id ? 'Không thể tự thay đổi quyền của chính mình' : ''">
                                    <option>KHÁCH HÀNG</option>
                                    <option v-for="r in staffRoles" :key="r">{{ r }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>TRẠNG THÁI</label>
                                <select v-model="editForm.status" :disabled="editingUser?.id === currentUser?.id"
                                    :title="editingUser?.id === currentUser?.id ? 'Không thể tự khóa tài khoản của chính mình' : ''">
                                    <option>Hoạt động</option>
                                    <option>Bị khóa</option>
                                </select>
                            </div>
                        </div>

                        <!-- Preview role badge -->
                        <div class="role-preview">
                            <span class="preview-label">Xem trước quyền hạn:</span>
                            <span class="role-badge"
                                :style="{ background: roleStyle[editForm.role]?.bg, color: roleStyle[editForm.role]?.color }">
                                {{ editForm.role }}
                            </span>
                            <span class="status-dot" :style="{ color: statusStyle[editForm.status]?.color }">
                                ● {{ editForm.status }}
                            </span>
                        </div>

                        <p v-if="editError" class="form-error">⚠ {{ editError }}</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" @click="closeEditModal">Hủy</button>
                        <button class="btn-submit" @click="submitEdit">Lưu thay đổi</button>
                    </div>
                </div>
            </div>
        </Teleport>

    </div>
</template>

<style scoped>
* {
    box-sizing: border-box;
}

.page {
    background: #f5f7fb;
    min-height: 100vh;
    font-family: sans-serif;
    padding-top: 24px;
    padding-bottom: 40px;
}

.search-box {
    position: relative;
    width: 280px;
}

.search-box svg {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 14px;
    color: #94a3b8;
    pointer-events: none;
}

.search-box input {
    width: 100%;
    padding: 8px 12px 8px 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size: 13px;
    color: #0f172a;
    outline: none;
    background: #f8fafc;
}

.search-box input:focus {
    border-color: #2563eb;
    background: white;
}

.btn-new-user {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    padding: 9px 16px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #2563eb, #1D4ED8);
    color: white;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.2s;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
}

.btn-new-user:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.btn-new-user svg {
    width: 14px;
    height: 14px;
}

.users-bulk-toolbar {
    margin: 0 32px 14px;
}

/* STATS */
.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(220px, 1fr));
    gap: 20px;
    padding: 0 32px 20px;
}

.stat-card {
    background: white;
    min-height: 136px;
    border-radius: 16px;
    border: 1px solid transparent;
    padding: 26px 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
    display: flex;
    align-items: center;
}

.stat-card::after {
    content: '';
    position: absolute;
    width: 150px;
    height: 150px;
    border-radius: 999px;
    right: -28px;
    top: -54px;
    background: rgba(255, 255, 255, 0.13);
    pointer-events: none;
}

.stat-card.stat-blue {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    color: #fff;
}

.stat-card.stat-teal {
    background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
    color: #fff;
}

.stat-card.stat-orange {
    background: linear-gradient(135deg, #c2410c 0%, #f97316 100%);
    color: #fff;
}

.stat-info p {
    font-size: 12px;
    line-height: 1.2;
    font-weight: 800;
    color: rgba(255, 255, 255, 0.88);
    letter-spacing: 0.03em;
    margin: 0 0 20px;
    text-transform: uppercase;
}

.stat-info b {
    font-size: 34px;
    line-height: 1;
    font-weight: 800;
    color: #fff;
}

.stat-val-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.badge-up {
    font-size: 14px;
    font-weight: 800;
    color: #2563eb;
    background: #dcfce7;
    padding: 6px 10px;
    border-radius: 20px;
}

.badge-down {
    font-size: 14px;
    font-weight: 800;
    color: #dc2626;
    background: #fee2e2;
    padding: 6px 10px;
    border-radius: 20px;
}

.badge-neutral {
    font-size: 14px;
    font-weight: 800;
    color: #2563eb;
    background: #dbeafe;
    padding: 6px 10px;
    border-radius: 20px;
}

.dark-stat {
    background: linear-gradient(135deg, #1e3a5f, #0f172a);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    position: relative;
}

.dark-stat .stat-info p {
    color: rgba(255, 255, 255, 0.5);
}

.big-growth {
    font-size: 34px;
    line-height: 1;
    font-weight: 800;
    color: white !important;
}

.trend-svg {
    width: 96px;
    height: 48px;
    opacity: 0.8;
}

/* FILTER ROW */
.filter-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 32px 14px;
    flex-wrap: wrap;
    gap: 10px;
}

.tabs-group {
    display: flex;
    align-items: center;
    gap: 4px;
    background: white;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 4px 6px;
}

.tab {
    padding: 7px 14px;
    border-radius: 7px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.tab:hover {
    background: #f8fafc;
    color: #334155;
}

.tab.active {
    background: #2563eb;
    color: white;
}

.tab-divider {
    width: 1px;
    height: 20px;
    background: #e2e8f0;
    margin: 0 4px;
}

.status-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 2px 8px 2px 14px !important;
}

.status-tab:hover {
    background: transparent !important; /* Keep transparent hover since child dropdown handles interaction */
}

/* Custom Premium Dropdown specifically for status filter */
.status-filter-dropdown {
    position: relative;
    display: inline-block;
    min-width: 120px;
    user-select: none;
}

.status-filter-dropdown .dropdown-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1.5px solid #cbd5e1;
    background: white;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    transition: all .2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.status-filter-dropdown .dropdown-trigger:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(37,99,235,0.06);
}

.status-filter-dropdown .dropdown-trigger .chevron {
    width: 14px;
    height: 14px;
    color: #64748b;
    transition: transform .2s ease;
}

.status-filter-dropdown .dropdown-trigger .chevron.open {
    transform: rotate(180deg);
}

.status-filter-dropdown .dropdown-menu {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    z-index: 1000;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 6px;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 2px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    max-height: 240px;
    overflow-y: auto;
}

/* Custom Scrollbar for Dropdown Menu */
.status-filter-dropdown .dropdown-menu::-webkit-scrollbar {
    width: 6px;
}

.status-filter-dropdown .dropdown-menu::-webkit-scrollbar-track {
    background: transparent;
}

.status-filter-dropdown .dropdown-menu::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.status-filter-dropdown .dropdown-menu li {
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.12s ease;
    text-align: left;
}

.status-filter-dropdown .dropdown-menu li:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.status-filter-dropdown .dropdown-menu li.active {
    background: #475569;
    color: white;
    font-weight: 600;
}

/* Dropdown Transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all .2s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

.filter-actions {
    display: flex;
    gap: 8px;
}

.btn-filter,
.btn-export {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    font-size: 13px;
    color: #334155;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-filter:hover,
.btn-export:hover {
    border-color: #2563eb;
    color: #2563eb;
}

.btn-export:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* TABLE */
.table-wrap {
    margin: 0 32px;
    background: white;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
    overflow: hidden;
}

.loading-row {
    text-align: center;
    padding: 48px;
    color: #94a3b8;
    font-size: 14px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead tr {
    background: #f8fafc;
}

thead th {
    padding: 12px 18px;
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-align: left;
    letter-spacing: 0.07em;
    border-bottom: 1px solid #f1f5f9;
}

tbody tr {
    border-bottom: 1px solid #f8fafc;
    transition: background 0.15s;
}

tbody tr:last-child {
    border-bottom: none;
}

tbody tr:hover {
    background: #fafbff;
}

tbody tr.row-selected {
    background: #eff6ff;
}

tbody td {
    padding: 16px 18px;
    font-size: 13px;
    vertical-align: middle;
}

.select-col {
    width: 44px;
    text-align: center;
}

.select-col input {
    width: 16px;
    height: 16px;
    accent-color: #2563eb;
    cursor: pointer;
}

.select-col input:disabled {
    cursor: not-allowed;
    opacity: .45;
}

.empty {
    text-align: center;
    color: #94a3b8;
    padding: 50px !important;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.user-cell b {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 2px;
}

.user-cell span {
    font-size: 12px;
    color: #94a3b8;
}

.role-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    letter-spacing: 0.04em;
}

.date-cell {
    color: #64748b;
    font-size: 13px;
}

.status-dot {
    font-size: 13px;
    font-weight: 600;
}

.actions {
    display: flex;
    gap: 5px;
}

.act-btn {
    width: 30px;
    height: 30px;
    border-radius: 7px;
    border: 1px solid #e2e8f0;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    transition: all 0.2s;
}

.act-btn svg {
    width: 13px;
    height: 13px;
}

.act-btn:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #2563eb;
}

/* Trạng thái đang bị khóa - icon ổ khóa nổi bật hơn */
.act-btn.lock-active {
    background: #fef9c3;
    border-color: #fde68a;
    color: #b45309;
}

.act-btn.lock-active:hover {
    background: #fef08a;
    color: #92400e;
}

.act-btn.danger:hover {
    background: #fee2e2;
    border-color: #fecaca;
    color: #ef4444;
}

/* FOOTER */
.table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 32px;
}

.showing {
    font-size: 13px;
    color: #64748b;
}

.pagination {
    display: flex;
    gap: 5px;
}

.pagination button {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    font-size: 13px;
    cursor: pointer;
    color: #334155;
    transition: all 0.2s;
}

.pagination button:hover:not(:disabled) {
    border-color: #2563eb;
    color: #2563eb;
}

.pagination button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pagination .active {
    background: #2563eb;
    border-color: #2563eb;
    color: white;
}

.pagination .dots {
    border: none;
    background: transparent;
    cursor: default;
    pointer-events: none;
}

/* BOTTOM GRID */
.bottom-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 20px 32px 0;
}

.bottom-card {
    background: white;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
    padding: 22px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.bottom-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.bottom-icon svg {
    width: 22px;
    height: 22px;
}

.bottom-icon.orange {
    background: #fff7ed;
    color: #ea580c;
}

.bottom-text h4 {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px;
}

.bottom-text p {
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
    margin: 0 0 14px;
}

.bottom-actions {
    display: flex;
    gap: 8px;
}

.btn-primary-sm {
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    background: #0f172a;
    color: white;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.btn-primary-sm:hover {
    opacity: 0.85;
}

.btn-ghost-sm {
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.btn-ghost-sm:hover {
    border-color: #94a3b8;
}

.security-card {
    background: #f8fafc;
}

.security-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #2563eb, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: white;
}

.security-icon svg {
    width: 24px;
    height: 24px;
}

.security-card .bottom-text p {
    margin: 0;
}

/* MODAL CHUNG */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 560px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
    animation: modalIn 0.22s cubic-bezier(.22, 1, .36, 1);
}

@keyframes modalIn {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.97);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px 16px;
    border-bottom: 1px solid #f1f5f9;
}

.modal-title-wrap {
    display: flex;
    align-items: baseline;
    gap: 10px;
}

.modal-header h3 {
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.modal-sub {
    font-size: 12px;
    color: #94a3b8;
}

.modal-close {
    background: none;
    border: none;
    font-size: 22px;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    transition: color 0.2s;
}

.modal-close:hover {
    color: #0f172a;
}

.modal-body {
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    letter-spacing: 0.08em;
}

.req {
    color: #ef4444;
}

.form-group input,
.form-group select {
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size: 13px;
    color: #0f172a;
    outline: none;
    transition: border-color 0.2s;
    background: #f8fafc;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #2563eb;
    background: white;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}

.form-error {
    font-size: 12px;
    color: #ef4444;
    background: #fef2f2;
    border: 1px solid #fecaca;
    padding: 9px 12px;
    border-radius: 8px;
    margin: 0;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 24px 20px;
    border-top: 1px solid #f1f5f9;
}

.btn-cancel {
    padding: 10px 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-cancel:hover {
    background: #f8fafc;
}

.btn-submit {
    padding: 10px 22px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #2563eb, #2563eb);
    color: white;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.2s;
}

.btn-submit:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* ROLE PREVIEW (edit modal) */
.role-preview {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}

.preview-label {
    font-size: 12px;
    color: #94a3b8;
    font-weight: 600;
}

/* ─── CUSTOM CONFIRM MODAL ─── */
.confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 20px;
}

.confirm-box {
    background: white;
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    padding: 32px 28px 24px;
    box-shadow: 0 32px 80px rgba(0, 0, 0, 0.22), 0 0 0 1px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0;
}

/* Top accent border */
.confirm-box--orange {
    border-top: 4px solid #f97316;
}

.confirm-box--red {
    border-top: 4px solid #ef4444;
}

.confirm-box--blue {
    border-top: 4px solid #2563eb;
}

/* Icon */
.confirm-icon-wrap {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    flex-shrink: 0;
}

.confirm-icon-wrap svg {
    width: 28px;
    height: 28px;
}

.confirm-icon--orange {
    background: #fff7ed;
    color: #ea580c;
}

.confirm-icon--red {
    background: #fef2f2;
    color: #dc2626;
}

.confirm-icon--blue {
    background: #eff6ff;
    color: #2563eb;
}

/* Content */
.confirm-content {
    width: 100%;
    margin-bottom: 24px;
}

.confirm-content h4 {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 8px;
    letter-spacing: -0.01em;
}

.confirm-content p {
    font-size: 13.5px;
    color: #64748b;
    line-height: 1.6;
    margin: 0 0 12px;
}

.confirm-sub {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 7px 12px;
}

.confirm-sub svg {
    width: 13px;
    height: 13px;
    flex-shrink: 0;
    color: #94a3b8;
}

/* Actions */
.confirm-actions {
    display: flex;
    gap: 10px;
    width: 100%;
}

.confirm-cancel {
    flex: 1;
    padding: 11px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: white;
    font-size: 13.5px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}

.confirm-cancel:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #334155;
}

.confirm-ok {
    flex: 1.4;
    padding: 11px;
    border-radius: 10px;
    border: none;
    font-size: 13.5px;
    font-weight: 700;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
    letter-spacing: 0.01em;
}

.confirm-ok--orange {
    background: linear-gradient(135deg, #f97316, #ea580c);
}

.confirm-ok--red {
    background: linear-gradient(135deg, #f87171, #dc2626);
}

.confirm-ok--blue {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.confirm-ok:hover {
    opacity: 0.88;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
}

/* Transition */
.confirm-fade-enter-active {
    animation: confirmIn 0.25s cubic-bezier(.22, 1, .36, 1);
}

.confirm-fade-leave-active {
    animation: confirmOut 0.18s ease-in forwards;
}

@keyframes confirmIn {
    from {
        opacity: 0;
        transform: scale(0.88) translateY(20px);
    }

    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes confirmOut {
    from {
        opacity: 1;
        transform: scale(1);
    }

    to {
        opacity: 0;
        transform: scale(0.93);
    }
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .stats {
        grid-template-columns: 1fr 1fr;
    }

    .bottom-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .stats {
        padding: 0 16px 16px;
        grid-template-columns: 1fr 1fr;
    }

    .filter-row {
        padding: 0 16px 12px;
    }

    .users-bulk-toolbar {
        margin: 0 16px 14px;
    }

    .table-wrap {
        margin: 0 16px;
        overflow-x: auto;
    }

    table {
        min-width: 640px;
    }

    .table-footer {
        padding: 12px 16px;
        flex-direction: column;
        gap: 10px;
    }

    .bottom-grid {
        padding: 16px 16px 0;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
