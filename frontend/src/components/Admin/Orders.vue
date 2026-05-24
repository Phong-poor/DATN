<script setup>

import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import * as XLSX from 'xlsx'
  
import api from '../../services/api'
import swal from '../../services/swal'
import echo from '../../services/echo'
import { onUnmounted } from 'vue'

const activeTab = ref('Tất cả')
const searchQuery = ref('')
const showViewModal = ref(false)
const viewOrder = ref(null)
const selectedMonthYear = ref('Tất cả')

// Pagination
const currentPage = ref(1)
const itemsPerPage = 5

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

const statusSequence = ['pending', 'confirmed', 'shipping', 'done']
const terminalStatuses = ['done', 'cancelled']

const getAllowedStatuses = (current) => {
    if (terminalStatuses.includes(current)) return [current]
    const idx = statusSequence.indexOf(current)
    if (idx === -1) return [current]
    if (idx === statusSequence.length - 1) return [current]
    // Return current and next one
    return [current, statusSequence[idx + 1]]
}

const getNextStatus = (current) => {
    const idx = statusSequence.indexOf(current)
    if (idx !== -1 && idx < statusSequence.length - 1) return statusSequence[idx + 1]
    return current
}

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
                raw: o,
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
            swal.success('Thành công', 'Cập nhật trạng thái đơn hàng thành công!')
        }
    } catch (error) {
        swal.error('Lỗi', error.response?.data?.message || 'Không thể cập nhật trạng thái')
    }
}

const confirmUpdateStatus = async (id, currentStatus) => {
    const next = getNextStatus(currentStatus)
    const label = getStatusLabel(next)
    const isConfirmed = await swal.confirm('Xác nhận cập nhật', `Bạn có chắc muốn cập nhật trạng thái đơn hàng sang: ${label}?`)
    if (isConfirmed) {
        updateOrderStatus(id, next)
    }
}

const confirmCancelOrder = async (id) => {
    const isConfirmed = await swal.confirm('Xác nhận HỦY', 'Bạn có chắc chắn muốn HỦY đơn hàng này?')
    if (isConfirmed) {
        updateOrderStatus(id, 'cancelled')
    }
}

onMounted(() => {
    fetchOrders()

    echo.channel('admin-orders')
        .listen('.order.placed', (e) => {
            console.log('New Order Received:', e.order)
            
            const newOrder = {
                id_backend: e.order.id_dathang,
                id: `#VT-2026-${String(e.order.id_dathang).padStart(3, '0')}`,
                name: e.order.user?.name || 'Ẩn danh',
                email: e.order.user?.email || '',
                avatar: (e.order.user?.name || 'NA').split(' ').map(w => w[0]).slice(-2).join('').toUpperCase(),
                date: new Date(e.order.created_at).toLocaleDateString('vi-VN'),
                total: new Intl.NumberFormat('vi-VN').format(e.order.tongtien) + 'đ',
                status: e.order.trangthai,
                phone: e.order.user?.phone || '',
                address: e.order.diachi || '',
                raw: e.order,
                note: '',
            }

            // Thêm vào đầu danh sách
            orders.value.unshift(newOrder)

            // Thông báo
            swal.toast(`🔔 Có đơn hàng mới từ ${newOrder.name}!`, 'info')
            
            // Nếu trình duyệt hỗ trợ âm thanh, có thể thêm ting ting ở đây
            try {
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3')
                audio.play()
            } catch (err) {
                console.log('Audio play failed')
            }
        })
})

onUnmounted(() => {
    echo.leaveChannel('admin-orders')
})

watch(searchQuery, () => {
    currentPage.value = 1
})

const totalRevenue = computed(() => {
    return orders.value
        .filter(o => o.status !== 'cancelled')
        .reduce((sum, o) => sum + Number(o.raw.tongtien || 0), 0)
})

const formatRevenue = (val) => {
    if (val >= 1000000000) return '+' + (val / 1000000000).toFixed(1) + 'B'
    if (val >= 1000000) return '+' + (val / 1000000).toFixed(1) + 'M'
    return '+' + val.toLocaleString('vi-VN')
}

const availableMonths = computed(() => {
    const months = new Set()
    orders.value.forEach(o => {
        const d = new Date(o.raw.created_at)
        const my = `Tháng ${d.getMonth() + 1}, ${d.getFullYear()}`
        months.add(my)
    })
    return ['Tất cả', ...Array.from(months)]
})

const filteredOrders = computed(() => {
    return orders.value.filter(o => {
        // Find the status key that matches the active tab's label
        const activeStatusKey = Object.keys(statusMap).find(k => statusMap[k].label === activeTab.value)
        const matchTab = activeTab.value === 'Tất cả' || o.status === activeStatusKey
        
        const matchSearch = o.id.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            o.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        
        const d = new Date(o.raw.created_at)
        const my = `Tháng ${d.getMonth() + 1}, ${d.getFullYear()}`
        const matchDate = selectedMonthYear.value === 'Tất cả' || my === selectedMonthYear.value

        return matchTab && matchSearch && matchDate
    })
})

const paginatedOrders = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    return filteredOrders.value.slice(start, start + itemsPerPage)
})

const totalPages = computed(() => Math.ceil(filteredOrders.value.length / itemsPerPage))



const openViewDetail = (order) => {
    viewOrder.value = order
    showViewModal.value = true
}

const closeViewModal = () => {
    showViewModal.value = false
    viewOrder.value = null
}

const changeTab = (tab) => {
    activeTab.value = tab
    currentPage.value = 1
}

const parseAttr = (json) => {
    try {
        const attr = JSON.parse(json)
        if (Array.isArray(attr)) return attr.map(a => `${a.ten_thuoctinh}: ${a.giatri}`).join(' | ')
        return ''
    } catch (e) { return '' }
}

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
                        @click="changeTab(tab)"
                    >{{ tab }}</button>
                </div>
            </div>

            <div class="date-filter-wrap">
                <div class="date-filter shadow-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <select v-model="selectedMonthYear" class="month-select" @change="currentPage = 1">
                        <option v-for="m in availableMonths" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>
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
                    <tr v-if="paginatedOrders.length === 0">
                        <td colspan="6" class="empty">Không tìm thấy đơn hàng nào.</td>
                    </tr>
                    <tr v-for="(o, i) in paginatedOrders" :key="o.id">

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
                            <span class="status-pill" :style="getStatusStyle(o.status)">
                                {{ getStatusLabel(o.status) }}
                            </span>
                        </td>

                        <td>
                            <div class="actions">
                                <button class="act-btn" @click="openViewDetail(o)" title="Xem chi tiết">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                                
                                <button v-if="!terminalStatuses.includes(o.status)" 
                                        class="act-btn" style="color: #2563eb;"
                                        @click="confirmUpdateStatus(o.id_backend, o.status)" 
                                        title="Chuyển trạng thái tiếp theo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
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
            <span class="showing" v-if="filteredOrders.length > 0">
                Hiển thị {{ (currentPage - 1) * itemsPerPage + 1 }} – {{ Math.min(currentPage * itemsPerPage, filteredOrders.length) }} của {{ filteredOrders.length }} đơn hàng
            </span>
            <span class="showing" v-else>Không có dữ liệu hiển thị</span>

            <div class="pagination" v-if="totalPages > 1">
                <button :disabled="currentPage === 1" @click="currentPage--">‹</button>
                <button 
                    v-for="p in totalPages" :key="p" 
                    :class="{ active: currentPage === p }"
                    @click="currentPage = p"
                >{{ p }}</button>
                <button :disabled="currentPage === totalPages" @click="currentPage++">›</button>
            </div>

            <div class="revenue-chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                    <polyline points="17 6 23 6 23 12"/>
                </svg>
                <div>
                    <span>TỔNG DOANH THU</span>
                    <b>{{ formatRevenue(totalRevenue) }}</b>
                </div>
            </div>
        </div>

        <!-- MODAL XEM CHI TIẾT ĐƠN HÀNG -->
        <Teleport to="body">
            <div v-if="showViewModal" class="modal-overlay" @click.self="closeViewModal">
                <div v-if="viewOrder" class="modal detail-modal">
                    <div class="modal-header">
                        <div>
                            <p class="modal-sub">Mã đơn: <b>{{ viewOrder.id }}</b></p>
                            <h3>Chi tiết đơn hàng</h3>
                        </div>
                        <button class="modal-close" @click="closeViewModal">×</button>
                    </div>

                    <div class="modal-body scrollable">
                        <div class="detail-section">
                            <div class="section-title">Thông tin giao hàng</div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Khách hàng:</label>
                                    <span>{{ viewOrder.name }}</span>
                                </div>
                                <div class="info-item">
                                    <label>Số điện thoại:</label>
                                    <span>{{ viewOrder.phone }}</span>
                                </div>
                                <div class="info-item" style="grid-column: span 2;">
                                    <label>Địa chỉ:</label>
                                    <span>{{ viewOrder.address }}</span>
                                </div>
                                <div class="info-item">
                                    <label>Ngày đặt:</label>
                                    <span>{{ viewOrder.date }}</span>
                                </div>
                                <div class="info-item">
                                    <label>Thanh toán:</label>
                                    <span>{{ viewOrder.raw.PTTT }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- LÝ DO HỦY ĐƠN -->
                        <div v-if="viewOrder.status === 'cancelled'" class="detail-section">
                            <div class="section-title" style="color: #dc2626;">Lý do hủy đơn</div>
                            <div class="cancel-reason-box">
                                {{ viewOrder.raw.lydo || 'Không có lý do cụ thể' }}
                            </div>
                        </div>

                        <div class="detail-section">
                            <div class="section-title">Danh sách sản phẩm</div>
                            <div class="items-list">
                                <div v-for="item in viewOrder.raw.chi_tiets" :key="item.id_chitiet" class="order-item">
                                    <img :src="item.bien_the?.san_pham?.hinhanh ? 'http://127.0.0.1:8000/storage/' + item.bien_the.san_pham.hinhanh : 'https://via.placeholder.com/60'" class="item-img" />
                                    <div class="item-info">
                                        <p class="item-name">{{ item.bien_the?.san_pham?.tenSP || 'Sản phẩm' }}</p>
                                        <p class="item-variant">{{ parseAttr(item.bien_the?.thuoc_tinh_json) }}</p>
                                    </div>
                                    <div class="item-price-qty">
                                        <span class="iq-price">{{ new Intl.NumberFormat('vi-VN').format(item.gia) }}đ</span>
                                        <span class="iq-qty">x{{ item.soluong }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="order-summary-box">
                            <div class="sum-row">
                                <span>Tổng cộng:</span>
                                <b class="final-total">{{ viewOrder.total }}</b>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn-cancel" @click="closeViewModal">Đóng</button>
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
    padding: 6px 14px; border-radius: 10px; border: 1px solid #e2e8f0;
    background: white; transition: all 0.2s;
}
.date-filter:hover { border-color: #2563eb; box-shadow: 0 4px 12px rgba(37,99,235,0.06); }
.date-filter svg { width: 14px; height: 14px; color: #64748b; }

.month-select {
    border: none; background: transparent; font-size: 13px; font-weight: 600;
    color: #334155; outline: none; cursor: pointer; font-family: inherit;
    padding: 2px 0;
}

/* TABLE */
.table-wrap { background: white; border-radius: 14px; border: 1px solid #f1f5f9; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
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
.pagination button:disabled { opacity: 0.4; cursor: not-allowed; }
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

/* ORDER DETAIL */
.detail-modal { max-width: 650px; }
.scrollable { max-height: 70vh; overflow-y: auto; padding-right: 10px; }
.scrollable::-webkit-scrollbar { width: 6px; }
.scrollable::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

.detail-section { margin-bottom: 24px; }
.info-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;
    background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;
}
.info-item { display: flex; flex-direction: column; gap: 4px; }
.info-item label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.info-item span { font-size: 13px; font-weight: 600; color: #334155; }

.items-list { display: flex; flex-direction: column; gap: 12px; }
.order-item {
    display: flex; align-items: center; gap: 15px; padding: 12px;
    background: white; border: 1px solid #f1f5f9; border-radius: 12px;
    transition: transform 0.2s;
}
.order-item:hover { transform: translateX(4px); border-color: #cbd5e1; }
.item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #f1f5f9; }
.item-info { flex: 1; }
.item-name { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
.item-variant { font-size: 12px; color: #64748b; margin: 0; }
.item-price-qty { text-align: right; }
.iq-price { display: block; font-size: 14px; font-weight: 700; color: #2563eb; }
.iq-qty { font-size: 12px; color: #94a3b8; font-weight: 600; }

.order-summary-box {
    margin-top: 10px; padding: 16px; background: #f0f9ff; border-radius: 12px;
    border: 1px solid #e0f2fe; display: flex; justify-content: flex-end;
}
.sum-row { display: flex; align-items: baseline; gap: 12px; }
.sum-row span { font-size: 13px; color: #0369a1; font-weight: 500; }
.final-total { font-size: 20px; font-weight: 800; color: #0369a1; }

.cancel-reason-box {
    margin-top: 8px;
    padding: 12px 16px;
    background: #fff1f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    color: #be123c;
    font-size: 13px;
    font-weight: 500;
    line-height: 1.5;
}
</style>