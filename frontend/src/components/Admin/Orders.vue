<script setup>
import { ref, computed, onMounted } from 'vue'
import * as XLSX from 'xlsx'

import api from '../../services/api'

const activeTab = ref('Tất cả')
const searchQuery = ref('')
const showModal = ref(false)
const formError = ref('')

const tabs = ['Tất cả', 'Chờ xác nhận', 'Đã xác nhận', 'Đang giao', 'Hoàn thành', 'Đã hủy']

const statusMap = {
    'pending':   { label: 'Chờ xác nhận', bg: '#fef9c3', color: '#ca8a04' },
    'confirmed': { label: 'Đã xác nhận', bg: '#e0f2fe', color: '#0369a1' },
    'shipping':  { label: 'Đang giao', bg: '#dbeafe', color: '#2563eb' },
    'done':      { label: 'Hoàn thành', bg: '#dcfce7', color: '#16a34a' },
    'cancelled': { label: 'Đã hủy', bg: '#fee2e2', color: '#dc2626' },
}

const getStatusLabel = (s) => statusMap[s]?.label || s
const getStatusStyle = (s) => ({ background: statusMap[s]?.bg, color: statusMap[s]?.color })

const orders = ref([])
const isLoading = ref(false)

const fetchOrders = async () => {
    try {
        isLoading.value = true
        const res = await api.get('/admin/orders')
        if (res.data.success) {
            orders.value = res.data.orders.map(o => ({
                id_backend: o.id_dathang,
                id: `#VT-2026-${String(o.id_dathang).padStart(3, '0')}`,
                name: o.user?.name || 'Ẩn danh',
                email: o.user?.email || '',
                avatar: (o.user?.name || 'NA').split(' ').map(w => w[0]).slice(-2).join('').toUpperCase(),
                date: new Date(o.created_at).toLocaleDateString('vi-VN'),
                total: new Intl.NumberFormat('vi-VN').format(o.tongtien) + 'đ',
                status: o.trangthai,
                phone: o.user?.phone || '',
                address: o.diachi || '',
                note: '', // Có thể thêm cột này sau
            }))
        }
    } catch (error) {
        console.error('Lỗi tải đơn hàng:', error)
    } finally {
        isLoading.value = false
    }
}

const updateOrderStatus = async (orderId, newStatus) => {
    try {
        const res = await api.put(`/admin/orders/${orderId}/status`, { trangthai: newStatus })
        if (res.data.success) {
            const idx = orders.value.findIndex(o => o.id_backend === orderId)
            if (idx !== -1) orders.value[idx].status = newStatus
            alert('Cập nhật trạng thái thành công!')
        }
    } catch (error) {
        alert('Lỗi: ' + (error.response?.data?.message || 'Không thể cập nhật'))
    }
}

onMounted(() => {
    fetchOrders()
})

const todayRevenue = computed(() => {
    const today = new Date().toLocaleDateString('vi-VN')
    return orders.value
        .filter(o => o.date === today && o.status === 'done')
        .reduce((sum, o) => {
            const price = parseInt(o.total.replace(/[^0-9]/g, ''))
            return sum + price
        }, 0)
})

const formatRevenue = (val) => {
    if (val >= 1000000000) return '+' + (val / 1000000000).toFixed(1) + 'B'
    if (val >= 1000000) return '+' + (val / 1000000).toFixed(1) + 'M'
    return '+' + val.toLocaleString('vi-VN')
}

const filteredOrders = computed(() => {
    return orders.value.filter(o => {
        const matchTab = activeTab.value === 'Tất cả' || o.status === activeTab.value
        const matchSearch = o.id.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            o.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        return matchTab && matchSearch
    })
})

// Generate next order ID
const nextId = computed(() => {
    const nums = orders.value.map(o => parseInt(o.id.split('-')[2]))
    const max = Math.max(...nums)
    return `#VT-2026-${String(max + 1).padStart(3, '0')}`
})

const defaultForm = () => ({
    name: '',
    email: '',
    phone: '',
    address: '',
    total: '',
    status: 'Chờ xác nhận',
    note: '',
    date: new Date().toLocaleDateString('vi-VN'),
})

const form = ref(defaultForm())

const openModal = () => {
    form.value = defaultForm()
    formError.value = ''
    showModal.value = true
}

const closeModal = () => { showModal.value = false }

const submitForm = () => {
    if (!form.value.name.trim())    { formError.value = 'Vui lòng nhập tên khách hàng.'; return }
    if (!form.value.phone.trim())   { formError.value = 'Vui lòng nhập số điện thoại.'; return }
    if (!form.value.address.trim()) { formError.value = 'Vui lòng nhập địa chỉ.'; return }
    if (!form.value.total.trim())   { formError.value = 'Vui lòng nhập tổng tiền.'; return }

    const initials = form.value.name.trim().split(' ').map(w => w[0]).slice(-2).join('').toUpperCase()
    orders.value.unshift({
        id: nextId.value,
        name: form.value.name.trim(),
        email: form.value.email.trim(),
        avatar: initials,
        date: form.value.date,
        total: form.value.total.trim(),
        status: form.value.status,
        phone: form.value.phone.trim(),
        address: form.value.address.trim(),
        note: form.value.note.trim(),
    })
    closeModal()
}

const removeOrder = (i) => orders.value.splice(i, 1)

const avatarColors = ['#dbeafe', '#dcfce7', '#fef9c3', '#ede9fe', '#fee2e2', '#ffedd5']
const avatarTextColors = ['#1d4ed8', '#15803d', '#a16207', '#6d28d9', '#b91c1c', '#c2410c']
const getAvatarStyle = (name) => {
    const idx = name.charCodeAt(0) % avatarColors.length
    return { background: avatarColors[idx], color: avatarTextColors[idx] }
}

// ── XUẤT EXCEL ──────────────────────────────────────────────
function exportExcel() {
    const today = new Date().toLocaleDateString('vi-VN')
    const tabLabel = activeTab.value

    const titleRow = [`BÁO CÁO ĐƠN HÀNG – ${tabLabel.toUpperCase()} (xuất ngày ${today})`]
    const blankRow = []
    const header = ['Mã đơn hàng', 'Khách hàng', 'Email', 'Số điện thoại', 'Địa chỉ', 'Ngày đặt hàng', 'Tổng tiền', 'Trạng thái', 'Ghi chú']

    const dataRows = filteredOrders.value.map(o => [
        o.id,
        o.name,
        o.email,
        o.phone,
        o.address,
        o.date,
        o.total,
        o.status,
        o.note,
    ])

    const ws = XLSX.utils.aoa_to_sheet([titleRow, blankRow, header, ...dataRows])
    ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 8 } }]
    ws['!cols'] = [
        { wch: 16 }, { wch: 22 }, { wch: 26 }, { wch: 14 },
        { wch: 32 }, { wch: 14 }, { wch: 16 }, { wch: 14 }, { wch: 28 },
    ]

    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Đơn hàng')

    const fileName = `don-hang-${tabLabel.toLowerCase().replace(/\s+/g, '-')}-${Date.now()}.xlsx`
    XLSX.writeFile(wb, fileName)
}
</script>

<template>
    <div class="page">

        <!-- BREADCRUMB -->
        <div class="breadcrumb">
            <span>Admin</span>
            <span class="sep">›</span>
            <span class="active-crumb">Orders Management</span>
        </div>

        <!-- TOP -->
        <div class="top">
            <h1>QUẢN LÝ ĐƠN HÀNG</h1>
            <div class="top-actions">
                <button class="btn-export" @click="exportExcel">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                    Xuất báo cáo
                </button>
                <button class="btn-create" @click="openModal">+ Tạo đơn mới</button>
            </div>
        </div>

        <!-- FILTER -->
        <div class="filter-wrap">
            <div class="search-row">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />
                    </svg>
                    <input v-model="searchQuery" placeholder="Tìm kiếm mã đơn hàng (#VT-2026..." />
                </div>

                <div class="tabs">
                    <button
                        v-for="tab in tabs" :key="tab"
                        class="tab" :class="{ active: activeTab === tab }"
                        @click="activeTab = tab"
                    >{{ tab }}</button>
                </div>
            </div>

            <div class="date-filter">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Tháng 10, 2026</span>
                <svg viewBox="0 0 20 20" fill="none" class="chevron">
                    <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2"/>
                </svg>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>MÃ ĐƠN HÀNG</th>
                        <th>KHÁCH HÀNG</th>
                        <th>NGÀY ĐẶT HÀNG</th>
                        <th>TỔNG TIỀN</th>
                        <th>TRẠNG THÁI</th>
                        <th>THAO TÁC</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="filteredOrders.length === 0">
                        <td colspan="6" class="empty">Không tìm thấy đơn hàng nào.</td>
                    </tr>
                    <tr v-for="(o, i) in filteredOrders" :key="o.id">

                        <td>
                            <span class="order-id">{{ o.id }}</span>
                        </td>

                        <td>
                            <div class="customer-cell">
                                <div class="avatar" :style="getAvatarStyle(o.name)">{{ o.avatar }}</div>
                                <div>
                                    <b>{{ o.name }}</b>
                                    <span>{{ o.email }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="date-cell">{{ o.date }}</td>

                        <td><b class="total">{{ o.total }}</b></td>

                        <td>
                            <select 
                                :value="o.status" 
                                @change="updateOrderStatus(o.id_backend, $event.target.value)"
                                class="status-select"
                                :style="getStatusStyle(o.status)"
                            >
                                <option v-for="(val, key) in statusMap" :key="key" :value="key">
                                    {{ val.label }}
                                </option>
                            </select>
                        </td>

                        <td>
                            <div class="actions">
                                <button class="act-btn" title="Xem">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                                <button class="act-btn" title="Sửa">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button class="act-btn danger" title="Xóa" @click="removeOrder(i)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                    </svg>
                                </button>
                            </div>
                        </td>

                    </tr>
                </tbody>
            </table>
        </div>

        <!-- FOOTER -->
        <div class="table-footer">
            <span class="showing">Hiển thị 1 – {{ filteredOrders.length }} của {{ orders.length }} đơn hàng</span>

            <div class="pagination">
                <button>‹</button>
                <button class="active">1</button>
                <button>2</button>
                <button>3</button>
                <button class="dots">...</button>
                <button>12</button>
                <button>›</button>
            </div>

            <div class="revenue-chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                    <polyline points="17 6 23 6 23 12"/>
                </svg>
                <div>
                    <span>DOANH THU HÔM NAY</span>
                    <b>{{ formatRevenue(todayRevenue) }}</b>
                </div>
            </div>
        </div>

        <!-- MODAL TẠO ĐƠN MỚI -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal">

                    <div class="modal-header">
                        <div>
                            <p class="modal-sub">Mã đơn: <b>{{ nextId }}</b></p>
                            <h3>Tạo đơn hàng mới</h3>
                        </div>
                        <button class="modal-close" @click="closeModal">×</button>
                    </div>

                    <div class="modal-body">

                        <div class="section-title">Thông tin khách hàng</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Họ tên khách hàng <span class="req">*</span></label>
                                <input v-model="form.name" placeholder="VD: Nguyễn Văn A" />
                            </div>
                            <div class="form-group">
                                <label>Số điện thoại <span class="req">*</span></label>
                                <input v-model="form.phone" placeholder="VD: 0901234567" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input v-model="form.email" placeholder="VD: khachhang@gmail.com" />
                        </div>

                        <div class="form-group">
                            <label>Địa chỉ giao hàng <span class="req">*</span></label>
                            <input v-model="form.address" placeholder="VD: 12 Nguyễn Huệ, Q.1, TP.HCM" />
                        </div>

                        <div class="section-title" style="margin-top: 8px;">Thông tin đơn hàng</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Tổng tiền <span class="req">*</span></label>
                                <input v-model="form.total" placeholder="VD: 45.990.000đ" />
                            </div>
                            <div class="form-group">
                                <label>Trạng thái</label>
                                <select v-model="form.status">
                                    <option>Chờ xác nhận</option>
                                    <option>Đang giao</option>
                                    <option>Hoàn thành</option>
                                    <option>Đã hủy</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Ngày đặt hàng</label>
                                <input v-model="form.date" placeholder="DD/MM/YYYY" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Ghi chú</label>
                            <textarea v-model="form.note" placeholder="Ghi chú thêm về đơn hàng..." rows="3"></textarea>
                        </div>

                        <p v-if="formError" class="form-error">⚠ {{ formError }}</p>

                    </div>

                    <div class="modal-footer">
                        <button class="btn-cancel" @click="closeModal">Hủy</button>
                        <button class="btn-submit" @click="submitForm">Tạo đơn hàng</button>
                    </div>

                </div>
            </div>
        </Teleport>

    </div>
</template>

<style scoped>
* { box-sizing: border-box; }

.page {
    padding: 28px 40px;
    background: #f5f7fb;
    min-height: 100vh;
    font-family: sans-serif;
}

/* BREADCRUMB */
.breadcrumb {
    font-size: 12px; color: #94a3b8; margin-bottom: 10px;
    display: flex; align-items: center; gap: 6px;
}
.sep { color: #cbd5e1; }
.active-crumb { color: #2563eb; font-weight: 500; }

/* TOP */
.top {
    display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;
}
.top h1 { font-size: 28px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.top-actions { display: flex; gap: 10px; }

.btn-export {
    display: flex; align-items: center; gap: 7px;
    padding: 10px 18px; border-radius: 10px; border: 1px solid #e2e8f0;
    background: white; font-size: 13px; font-weight: 600; color: #334155;
    cursor: pointer; transition: all 0.2s;
}
.btn-export svg { width: 15px; height: 15px; }
.btn-export:hover { border-color: #2563eb; color: #2563eb; }

.btn-create {
    padding: 10px 20px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: opacity 0.2s, transform 0.2s;
}
.btn-create:hover { opacity: 0.9; transform: translateY(-1px); }

/* FILTER */
.filter-wrap {
    background: white; border-radius: 14px; border: 1px solid #f1f5f9;
    padding: 16px 20px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 14px;
}
.search-row { display: flex; align-items: center; gap: 12px; }
.search-box {
    flex: 1; position: relative;
}
.search-box svg {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    width: 15px; height: 15px; color: #94a3b8; pointer-events: none;
}
.search-box input {
    width: 100%; padding: 9px 14px 9px 36px; border-radius: 8px;
    border: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;
    outline: none; transition: border-color 0.2s;
}
.search-box input:focus { border-color: #2563eb; }

.tabs { display: flex; gap: 6px; }
.tab {
    padding: 8px 14px; border-radius: 8px; border: none;
    background: transparent; font-size: 13px; font-weight: 500;
    color: #64748b; cursor: pointer; transition: all 0.2s; white-space: nowrap;
}
.tab:hover { background: #f1f5f9; color: #334155; }
.tab.active { background: #2563eb; color: white; }

.date-filter {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 14px; border-radius: 8px; border: 1px solid #e2e8f0;
    font-size: 13px; font-weight: 500; color: #334155; cursor: pointer;
    width: fit-content;
}
.date-filter svg { width: 14px; height: 14px; color: #64748b; }
.chevron { width: 12px; height: 12px; }

/* TABLE */
.table-wrap { background: white; border-radius: 14px; border: 1px solid #f1f5f9; overflow: hidden; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #f8fafc; }
thead th {
    padding: 13px 20px; font-size: 11px; font-weight: 700;
    color: #94a3b8; text-align: left; letter-spacing: 0.06em;
    border-bottom: 1px solid #f1f5f9;
}
tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #fafbff; }
tbody td { padding: 18px 20px; font-size: 13px; color: #334155; vertical-align: middle; }
.empty { text-align: center; color: #94a3b8; padding: 50px !important; }

.order-id { color: #2563eb; font-weight: 700; font-size: 13px; }

.customer-cell { display: flex; align-items: center; gap: 12px; }
.avatar {
    width: 38px; height: 38px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.customer-cell b { display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 2px; }
.customer-cell span { font-size: 12px; color: #94a3b8; }

.date-cell { color: #64748b; }
.total { font-size: 14px; font-weight: 700; color: #0f172a; }

.status-pill, .status-select {
    display: inline-block; font-size: 11px; font-weight: 600;
    padding: 5px 11px; border-radius: 20px; letter-spacing: 0.02em;
    border: none; outline: none; cursor: pointer;
}
.status-select {
    appearance: none;
    padding-right: 24px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='currentColor'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 12px;
}

.actions { display: flex; gap: 6px; }
.act-btn {
    width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0;
    background: white; cursor: pointer; display: flex;
    align-items: center; justify-content: center; color: #64748b; transition: all 0.2s;
}
.act-btn svg { width: 14px; height: 14px; }
.act-btn:hover { background: #f1f5f9; border-color: #cbd5e1; color: #2563eb; }
.act-btn.danger:hover { background: #fee2e2; border-color: #fecaca; color: #ef4444; }

/* FOOTER */
.table-footer {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 16px; flex-wrap: wrap; gap: 12px;
}
.showing { font-size: 13px; color: #64748b; }

.pagination { display: flex; gap: 6px; }
.pagination button {
    width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0;
    background: white; font-size: 13px; cursor: pointer; color: #334155; transition: all 0.2s;
}
.pagination button:hover { border-color: #2563eb; color: #2563eb; }
.pagination .active { background: #2563eb; border-color: #2563eb; color: white; }
.pagination .dots { border: none; background: transparent; cursor: default; }
.pagination .dots:hover { color: #334155; border-color: transparent; }

.revenue-chip {
    display: flex; align-items: center; gap: 10px;
    background: white; border: 1px solid #f1f5f9;
    padding: 10px 16px; border-radius: 12px;
}
.revenue-chip svg { width: 20px; height: 20px; color: #16a34a; }
.revenue-chip span { font-size: 10px; font-weight: 600; color: #94a3b8; letter-spacing: 0.06em; display: block; }
.revenue-chip b { font-size: 16px; font-weight: 700; color: #16a34a; }

/* ===== MODAL ===== */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(15,23,42,0.55);
    display: flex; align-items: center; justify-content: center;
    z-index: 1000; padding: 20px;
}
.modal {
    background: white; border-radius: 16px; width: 100%; max-width: 580px;
    box-shadow: 0 24px 60px rgba(0,0,0,0.18);
    animation: modalIn 0.22s cubic-bezier(.22,1,.36,1);
    max-height: 90vh; overflow-y: auto;
}
@keyframes modalIn {
    from { opacity: 0; transform: translateY(16px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 20px 24px 16px; border-bottom: 1px solid #f1f5f9;
    position: sticky; top: 0; background: white; z-index: 1;
}
.modal-sub { font-size: 12px; color: #94a3b8; margin: 0 0 4px; }
.modal-sub b { color: #2563eb; }
.modal-header h3 { font-size: 17px; font-weight: 700; color: #0f172a; margin: 0; }
.modal-close {
    background: none; border: none; font-size: 22px;
    color: #94a3b8; cursor: pointer; padding: 0; transition: color 0.2s; line-height: 1;
}
.modal-close:hover { color: #0f172a; }

.modal-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 14px; }

.section-title {
    font-size: 11px; font-weight: 700; color: #94a3b8;
    letter-spacing: 0.1em; text-transform: uppercase; padding-bottom: 4px;
    border-bottom: 1px solid #f1f5f9;
}

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 12px; font-weight: 600; color: #475569; }
.req { color: #ef4444; }

.form-group input,
.form-group select,
.form-group textarea {
    padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0;
    font-size: 13px; color: #0f172a; outline: none;
    transition: border-color 0.2s; background: #fff; font-family: sans-serif;
}
.form-group textarea { resize: vertical; }
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
}

.form-error {
    font-size: 12px; color: #ef4444; background: #fef2f2;
    border: 1px solid #fecaca; padding: 9px 12px; border-radius: 8px; margin: 0;
}

.modal-footer {
    display: flex; justify-content: flex-end; gap: 10px;
    padding: 16px 24px 20px; border-top: 1px solid #f1f5f9;
    position: sticky; bottom: 0; background: white;
}
.btn-cancel {
    padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0;
    background: white; font-size: 13px; font-weight: 600; color: #475569;
    cursor: pointer; transition: all 0.2s;
}
.btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
.btn-submit {
    padding: 10px 22px; border-radius: 8px; border: none;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: opacity 0.2s, transform 0.2s;
}
.btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }

/* RESPONSIVE */
@media (max-width: 768px) {
    .page { padding: 20px 16px; }
    .search-row { flex-direction: column; align-items: stretch; }
    .tabs { overflow-x: auto; }
    .table-wrap { overflow-x: auto; }
    table { min-width: 700px; }
    .form-row { grid-template-columns: 1fr; }
    .table-footer { flex-direction: column; align-items: flex-start; }
}
</style>