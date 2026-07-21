<script setup>

import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import Swal from 'sweetalert2'
  
import api from '../../services/api'
import swal from '../../services/swal'
import echo from '../../services/echo'
import { productImageUrl, storageUrl } from '@/services/urls'
import BulkDeleteToolbar from './ThanhXoaHangLoat.vue'
import { useAdminBulkDelete } from '@/services/adminBulkDelete'

const getOrderItemImage = (item) => {
  const bt = item?.bien_the || item?.bienThe
  const sp = bt?.san_pham || bt?.sanPham
  return productImageUrl(sp, bt, 'https://placehold.co/60')
}

const getOrderItemName = (item) => {
  const bt = item?.bien_the || item?.bienThe
  const sp = bt?.san_pham || bt?.sanPham
  return sp?.tenSP || 'Sản phẩm'
}

const getOrderItemVariant = (item) => {
  const bt = item?.bien_the || item?.bienThe
  return parseAttr(bt?.thuoc_tinh_json)
}

const activeTab = ref('Tất cả')
const searchQuery = ref('')
const showViewModal = ref(false)
const viewOrder = ref(null)
const selectedMonthYear = ref('Tất cả')
const isOpenDateDropdown = ref(false)

const closeDateDropdown = (e) => {
    if (!e.target.closest('.date-filter-dropdown')) {
        isOpenDateDropdown.value = false
    }
}

// Pagination
const currentPage = ref(1)
const itemsPerPage = 5

const pageMode = ref('orders')

const orderSteps = computed(() => {
    const o = viewOrder.value
    if (!o) return null
    const statusKey = o.status
    return [
        { label: 'Đặt hàng', date: o.date || (o.created_at ? new Date(o.created_at).toLocaleString('vi-VN') : null) || '-', done: true },
        { label: 'Xác nhận', date: null, done: statusKey !== 'pending' },
        { label: 'Đang giao', date: null, done: statusKey === 'shipping' || statusKey === 'done' || statusKey.startsWith('refund') },
        { label: 'Hoàn thành', date: null, done: statusKey === 'done' || statusKey.startsWith('refund') },
    ]
})

const refundSteps = computed(() => {
    const o = viewOrder.value
    if (!o) return null
    const statusKey = o.status
    if (!statusKey.startsWith('refund')) return null
    const keys = ['refund_pending', 'refund_pickup', 'refund_delivering', 'refund_received', 'refunded']
    return [
        { label: 'Yêu cầu hoàn trả', date: null, done: keys.indexOf(statusKey) >= 0 },
        { label: 'Chờ lấy hàng hoàn', date: null, done: keys.indexOf(statusKey) >= 1 },
        { label: 'Đang giao hoàn', date: null, done: keys.indexOf(statusKey) >= 2 },
        { label: 'Đã nhận hoàn', date: null, done: keys.indexOf(statusKey) >= 3 },
        { label: 'Đã hoàn tiền', date: null, done: keys.indexOf(statusKey) >= 4 },
    ]
})

const shippingTimeline = computed(() => {
    const shipment = getShipment(viewOrder.value)
    if (!shipment?.timeline?.length) return []

    return shipment.timeline.map(step => ({
        label: getShipmentStatusLabel(step.status, step.label),
        note: getShipmentNote(step.status, step.note),
        date: step.time ? new Date(step.time).toLocaleString('vi-VN') : '-',
    }))
})

const formatMoney = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ'

const tabs_mua = ['Tất cả', 'Chờ xác nhận', 'Đã xác nhận', 'Đang giao', 'Hoàn thành', 'Đã hủy']
const tabs_hoantra = ['Tất cả', 'Yêu cầu hoàn trả', 'Chờ lấy hàng hoàn', 'Đang giao hoàn', 'Đã nhận hoàn', 'Đã hoàn tiền']
const currentTabs = computed(() => pageMode.value === 'orders' ? tabs_mua : tabs_hoantra)

const statusMap = {
    pending: { label: 'Chờ xác nhận', bg: '#fef9c3', color: '#ca8a04' },
    confirmed: { label: 'Đã xác nhận', bg: '#e0f2fe', color: '#0369a1' },
    shipping: { label: 'Đang giao', bg: '#dbeafe', color: '#2563eb' },
    done: { label: 'Hoàn thành', bg: '#dcfce7', color: '#15803d' },
    refund_pending: { label: 'Yêu cầu hoàn trả', bg: '#ffedd5', color: '#f97316' },
    refund_pickup: { label: 'Chờ lấy hàng hoàn', bg: '#fef3c7', color: '#d97706' },
    refund_delivering: { label: 'Đang giao hoàn', bg: '#dbeafe', color: '#2563eb' },
    refund_received: { label: 'Đã nhận hoàn', bg: '#e0f2fe', color: '#0369a1' },
    refunded: { label: 'Đã hoàn tiền', bg: '#ede9fe', color: '#3b82f6' },
    refund_rejected: { label: 'Từ chối hoàn trả', bg: '#fee2e2', color: '#dc2626' },
    cancelled: { label: 'Đã hủy', bg: '#fee2e2', color: '#dc2626' },
}

const getStatusLabel = (s) => statusMap[s]?.label || s
const getStatusStyle = (s) => ({ background: statusMap[s]?.bg, color: statusMap[s]?.color })

const shipmentStyleMap = {
    created: { bg: '#eef2ff', color: '#4f46e5' },
    waiting_pickup: { bg: '#fef3c7', color: '#b45309' },
    picked_up: { bg: '#e0f2fe', color: '#0369a1' },
    delivering: { bg: '#dbeafe', color: '#2563eb' },
    delivered: { bg: '#dcfce7', color: '#15803d' },
    delivery_failed: { bg: '#fee2e2', color: '#dc2626' },
    returning: { bg: '#ffedd5', color: '#ea580c' },
    returned: { bg: '#ede9fe', color: '#7c3aed' },
}

const shipmentLabelMap = {
    created: 'Đã tạo vận đơn',
    waiting_pickup: 'Chờ lấy hàng',
    picked_up: 'Đã lấy hàng',
    delivering: 'Đang giao hàng',
    delivered: 'Giao thành công',
    delivery_failed: 'Giao thất bại',
    returning: 'Đang hoàn về',
    returned: 'Đã hoàn về kho',
}

const shipmentNoteMap = {
    created: 'Cửa hàng đã tạo vận đơn trên hệ thống NextGen Express.',
    waiting_pickup: 'Đơn hàng đang chờ nhân viên kho bàn giao cho đơn vị vận chuyển.',
    picked_up: 'Đơn vị vận chuyển đã lấy hàng tại kho.',
    delivering: 'Shipper đang giao hàng đến địa chỉ của khách.',
    delivered: 'Khách hàng đã nhận hàng thành công.',
    delivery_failed: 'Shipper giao không thành công, cần liên hệ lại khách hoặc hẹn giao lại.',
    returning: 'Đơn hàng đang được hoàn về kho.',
    returned: 'Đơn hàng đã hoàn về kho.',
}

const getShipmentStatusLabel = (status, fallback = '') => shipmentLabelMap[status] || fallback || status || 'Đang cập nhật'
const getShipmentNote = (status, fallback = '') => fallback || shipmentNoteMap[status] || 'Đã cập nhật trạng thái vận chuyển.'

const getShipment = (order) => order?.raw?.du_lieu_thanh_toan?.shipping_demo || order?.shipping || null
const hasShipment = (order) => Boolean(getShipment(order)?.tracking_code)
const getShipmentFailureReason = (order) => {
    const shipment = getShipment(order)
    if (shipment?.failure_reason) return shipment.failure_reason
    const failedStep = shipment?.timeline?.findLast?.(step => step.status === 'delivery_failed')
        || [...(shipment?.timeline || [])].reverse().find(step => step.status === 'delivery_failed')
    const note = failedStep?.note || ''
    if (!note || note.includes('Shipper giao không thành công')) return 'Không liên hệ được người nhận'
    return note.replace(/^Giao hàng thất bại:\s*/i, '')
}
const getShipmentAttempts = (order) => Number(getShipment(order)?.delivery_attempts || 0)
const getShipmentAttemptText = (order) => {
    const attempts = getShipmentAttempts(order)
    return attempts > 0 ? `Đã giao thất bại ${attempts}/3 lần` : ''
}
const getShipmentRefundNote = (order) => getShipment(order)?.refund_note || ''
const getShipmentStatusStyle = (order) => {
    const status = getShipment(order)?.status
    return shipmentStyleMap[status] || getStatusStyle(order.status)
}

const getDisplayStatusLabel = (order) => {
    const shipment = getShipment(order)
    if (shipment?.status && ['confirmed', 'shipping', 'done'].includes(order.status)) {
        return getShipmentStatusLabel(shipment.status)
    }
    return getStatusLabel(order.status)
}

const mapOrder = (o) => ({
    id_backend: o.id_dathang,
    id: `#VT-2026-${String(o.id_dathang).padStart(3, '0')}`,
    name: o.user?.name || 'Ẩn danh',
    email: o.user?.email || '',
    avatar: (o.user?.name || 'NA').split(' ').map(w => w[0]).slice(-2).join('').toUpperCase(),
    date: new Date(o.created_at).toLocaleDateString('vi-VN'),
    total: formatMoney(o.tongtien),
    status: o.trangthai,
    phone: o.user?.phone || '',
    address: o.diachi || '',
    shipping: o.du_lieu_thanh_toan?.shipping_demo || null,
    raw: o,
    note: '',
})

const syncOrderFromApi = (backendOrder) => {
    if (!backendOrder) return
    const mapped = mapOrder(backendOrder)
    const idx = orders.value.findIndex(o => o.id_backend === mapped.id_backend)
    if (idx !== -1) {
        orders.value[idx] = mapped
        if (viewOrder.value?.id_backend === mapped.id_backend) viewOrder.value = mapped
    } else {
        orders.value.unshift(mapped)
    }
}

const statusSequence = ['pending', 'confirmed', 'shipping', 'done']
const terminalStatuses = ['done', 'cancelled', 'refunded', 'refund_rejected']

const getAllowedStatuses = (current) => {
    if (terminalStatuses.includes(current)) return [current]
    const idx = statusSequence.indexOf(current)
    if (idx === -1) return [current]
    if (idx === statusSequence.length - 1) return [current]
    // Return current and next one
    return [current, statusSequence[idx + 1]]
}

const getNextStatus = (current) => {
    let idx = statusSequence.indexOf(current)
    if (idx !== -1 && idx < statusSequence.length - 1) return statusSequence[idx + 1]
    
    const returnSequence = ['refund_pickup', 'refund_delivering', 'refund_received', 'refunded']
    idx = returnSequence.indexOf(current)
    if (idx !== -1 && idx < returnSequence.length - 1) return returnSequence[idx + 1]
    
    return current
}

const orders = ref([])
const isLoading = ref(false)
let autoRefreshTimer = null

const fetchOrders = async () => {
    try {
        isLoading.value = true
        const res = await api.get('/admin/orders')
        if (res.data.success) {
            orders.value = res.data.orders.map(mapOrder)
        }
    } catch (error) {
        console.error('Loi tai don hang:', error)
    } finally {
        isLoading.value = false
    }
}
const updateOrderStatus = async (orderId, newStatus) => {
    try {
        const res = await api.put(`/admin/orders/${orderId}/status`, { trangthai: newStatus })
        if (res.data.success) {
            if (res.data.order) {
                syncOrderFromApi(res.data.order)
            } else {
                const idx = orders.value.findIndex(o => o.id_backend === orderId)
                if (idx !== -1) orders.value[idx].status = newStatus
            }
            swal.success('Thành công', 'Cập nhật trạng thái đơn hàng thành công!')
        }
    } catch (error) {
        swal.error('Lỗi', error.response?.data?.message || 'Không thể cập nhật trạng thái')
    }
}

const createShipment = async (order) => {
    const ok = await swal.confirm('Tạo vận đơn demo', `Tạo vận đơn NextGen Express cho ${order.id}?`)
    if (!ok) return

    try {
        const res = await api.post(`/admin/orders/${order.id_backend}/shipment`)
        if (res.data.success) {
            syncOrderFromApi(res.data.order)
            swal.success('Thành công', res.data.message || 'Đã tạo vận đơn.')
        }
    } catch (error) {
        swal.error('Lỗi', error.response?.data?.message || 'Không thể tạo vận đơn')
    }
}

const advanceShipment = async (order) => {
    try {
        const res = await api.post(`/admin/orders/${order.id_backend}/shipment/advance`)
        if (res.data.success) {
            syncOrderFromApi(res.data.order)
            swal.success('Thành công', res.data.message || 'Đã cập nhật vận chuyển.')
        }
    } catch (error) {
        swal.error('Lỗi', error.response?.data?.message || 'Không thể cập nhật vận chuyển')
    }
}

const failShipment = async (order) => {
    const reasons = {
        'Không liên hệ được người nhận': 'Không liên hệ được người nhận',
        'Khách hẹn giao lại': 'Khách hẹn giao lại',
        'Khách từ chối nhận hàng': 'Khách từ chối nhận hàng',
        'Địa chỉ giao hàng không chính xác': 'Địa chỉ giao hàng không chính xác',
        'Không có người nhận tại địa chỉ': 'Không có người nhận tại địa chỉ',
        'Khác': 'Khác',
    }

    const result = await Swal.fire({
        title: 'Ghi nhận giao thất bại',
        text: `Chọn lý do cho ${order.id}`,
        input: 'select',
        inputOptions: reasons,
        inputValue: 'Không liên hệ được người nhận',
        inputPlaceholder: 'Chọn lý do',
        showCancelButton: true,
        confirmButtonText: 'Ghi nhận',
        cancelButtonText: 'Hủy',
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-custom-popup',
            title: 'swal2-custom-title',
            htmlContainer: 'swal2-custom-content',
            confirmButton: 'swal2-custom-confirm',
            cancelButton: 'swal2-custom-cancel',
        },
        preConfirm: (value) => {
            if (!value) {
                Swal.showValidationMessage('Vui lòng chọn lý do giao thất bại.')
                return false
            }
            return value
        },
    })

    if (!result.isConfirmed) return

    let reason = result.value
    if (reason === 'Khác') {
        const custom = await Swal.fire({
            title: 'Nhập lý do cụ thể',
            input: 'textarea',
            inputPlaceholder: 'VD: Khách đi công tác, hẹn giao lại vào ngày mai...',
            inputAttributes: { maxlength: 255 },
            showCancelButton: true,
            confirmButtonText: 'Tiếp tục',
            cancelButtonText: 'Hủy',
            buttonsStyling: false,
            customClass: {
                popup: 'swal2-custom-popup',
                title: 'swal2-custom-title',
                htmlContainer: 'swal2-custom-content',
                confirmButton: 'swal2-custom-confirm',
                cancelButton: 'swal2-custom-cancel',
            },
            preConfirm: (value) => {
                if (!String(value || '').trim()) {
                    Swal.showValidationMessage('Vui lòng nhập lý do cụ thể.')
                    return false
                }
                return String(value).trim()
            },
        })

        if (!custom.isConfirmed) return
        reason = custom.value
    }

    try {
        const res = await api.post(`/admin/orders/${order.id_backend}/shipment/fail`, { reason })
        if (res.data.success) {
            syncOrderFromApi(res.data.order)
            swal.success('Đã ghi nhận', res.data.message || 'Đã cập nhật vận chuyển.')
        }
    } catch (error) {
        swal.error('Lỗi', error.response?.data?.message || 'Không thể ghi nhận giao thất bại')
    }
}

const retryShipment = async (order) => {
    const attempts = getShipmentAttempts(order)
    const ok = await swal.confirm(
        'Sắp xếp giao lại',
        `Giao lại ${order.id} lần ${attempts + 1}/3?`
    )
    if (!ok) return

    try {
        const res = await api.post(`/admin/orders/${order.id_backend}/shipment/retry`)
        if (res.data.success) {
            syncOrderFromApi(res.data.order)
            swal.success('Thành công', res.data.message || 'Đã sắp xếp giao lại.')
        }
    } catch (error) {
        swal.error('Lỗi', error.response?.data?.message || 'Không thể sắp xếp giao lại')
    }
}

const canCreateShipment = (order) => !hasShipment(order)
    && ['pending', 'confirmed'].includes(order.status)
    && !String(order.status).startsWith('refund')

const canAdvanceShipment = (order) => {
    const status = getShipment(order)?.status
    return hasShipment(order) && ['created', 'waiting_pickup', 'picked_up', 'delivering'].includes(status)
}

const canFailShipment = (order) => {
    const status = getShipment(order)?.status
    return hasShipment(order) && ['picked_up', 'delivering'].includes(status)
}

const canRetryShipment = (order) => {
    const shipment = getShipment(order)
    return hasShipment(order)
        && shipment?.status === 'delivery_failed'
        && getShipmentAttempts(order) < 3
        && shipment?.can_retry !== false
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
    const isConfirmed = await swal.confirm('Xác nhận hủy', 'Bạn có chắc chắn muốn hủy đơn hàng này?')
    if (isConfirmed) {
        updateOrderStatus(id, 'cancelled')
    }
}

const confirmApproveRefund = async (id) => {
    const isConfirmed = await swal.confirm('Xác nhận hoàn trả', 'Bạn có chắc chắn chấp nhận yêu cầu hoàn trả này? Đơn sẽ chuyển sang chờ lấy hàng hoàn.')
    if (isConfirmed) {
        updateOrderStatus(id, 'refund_pickup')
    }
}

const confirmRejectRefund = async (id) => {
    const isConfirmed = await swal.confirm('Từ chối hoàn trả', 'Từ chối yêu cầu và giữ đơn hàng ở trạng thái hoàn thành?')
    if (isConfirmed) {
        updateOrderStatus(id, 'refund_rejected')
    }
}

const deleteOrder = async (id) => {
    const confirmed = await swal.confirm('Xóa đơn hàng', 'Bạn có chắc muốn xóa đơn hàng này không?')
    if (!confirmed) return

    try {
        await api.delete(`/admin/orders/${id}`)
        await fetchOrders()
        selectedIds.value = selectedIds.value.filter(selectedId => selectedId !== id)
        swal.success('Thành công', 'Đã xóa đơn hàng.')
    } catch (error) {
        swal.error('Lỗi', error.response?.data?.message || 'Không thể xóa đơn hàng')
    }
}

onMounted(() => {
    fetchOrders()
    autoRefreshTimer = window.setInterval(() => {
        fetchOrders()
    }, 20000)
    document.addEventListener('click', closeDateDropdown)

    echo.channel('admin-orders')
        .listen('.order.placed', (e) => {
            const newOrder = mapOrder(e.order)

            orders.value.unshift(newOrder)

            swal.toast(`Có đơn hàng mới từ ${newOrder.name}!`, 'info')
            
            try {
                const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3')
                audio.play()
            } catch (err) {
                console.warn('Order notification audio could not be played')
            }
        })
})

onUnmounted(() => {
    if (autoRefreshTimer) {
        window.clearInterval(autoRefreshTimer)
        autoRefreshTimer = null
    }
    echo.leaveChannel('admin-orders')
    document.removeEventListener('click', closeDateDropdown)
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
        const activeStatusKey = Object.keys(statusMap).find(k => statusMap[k].label === activeTab.value)
        
        let matchTab = false;
        if (pageMode.value === 'orders') {
            matchTab = activeTab.value === 'Tất cả' 
                ? !o.status.startsWith('refund') 
                : o.status === activeStatusKey
        } else {
            matchTab = activeTab.value === 'Tất cả'
                ? o.status.startsWith('refund')
                : o.status === activeStatusKey
        }
        
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
    items: orders,
    filteredItems: filteredOrders,
    pageItems: paginatedOrders,
    getId: item => item.id_backend,
    endpoint: id => `/admin/orders/${id}`,
    entityLabel: 'đơn hàng',
    fetchItems: fetchOrders,
})

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

const getTabCount = (tabLabel) => {
    if (tabLabel === 'Tất cả') {
        if (pageMode.value === 'orders') return orders.value.filter(o => !o.status.startsWith('refund')).length;
        return orders.value.filter(o => o.status.startsWith('refund')).length;
    }
    const activeStatusKey = Object.keys(statusMap).find(k => statusMap[k].label === tabLabel)
    return orders.value.filter(o => o.status === activeStatusKey).length
}

const parseAttr = (json) => {
    try {
        const attr = JSON.parse(json)
        if (Array.isArray(attr)) return attr.map(a => `${a.ten_thuoctinh}: ${a.giatri}`).join(' | ')
        return ''
    } catch (e) { return '' }
}

const avatarColors = ['#dbeafe', '#dcfce7', '#fef9c3', '#ede9fe', '#fee2e2', '#ffedd5']
const avatarTextColors = ['#1d4ed8', '#1d4ed8', '#a16207', '#1d4ed8', '#b91c1c', '#c2410c']
const getAvatarStyle = (name) => {
    const idx = name.charCodeAt(0) % avatarColors.length
    return { background: avatarColors[idx], color: avatarTextColors[idx] }
}

async function exportExcel() {
    const XLSX = await import('xlsx')
    const today = new Date().toLocaleDateString('vi-VN')
    const tabLabel = activeTab.value

    const titleRow = [`Báo cáo đơn hàng - ${tabLabel} (xuất ngày ${today})`]
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
        getDisplayStatusLabel(o),
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
            <span class="active-crumb">Quản lý đơn hàng</span>
        </div>

        <!-- CATEGORY TABS -->
        <div class="category-tabs" style="margin-bottom: 20px;">
            <div class="category-tab-list">
                <button :class="['cat-tab', { active: pageMode === 'orders' }]" @click="pageMode = 'orders'; activeTab = 'Tất cả'">
                    Đơn mua hàng
                </button>
                <button :class="['cat-tab', { active: pageMode === 'refunds' }]" @click="pageMode = 'refunds'; activeTab = 'Tất cả'">
                    Đơn hoàn trả
                </button>
            </div>
            <button class="btn-export" @click="exportExcel">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                Xuất báo cáo
            </button>
        </div>

      
        
        <!-- FILTER -->
        <div class="filter-wrap">
            <div class="search-row">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />
                    </svg>
                    <input v-model="searchQuery" placeholder="Tìm kiếm mã đơn hàng, khách hàng..." />
                </div>

                <div class="tabs">
                    <button
                        v-for="tab in currentTabs" :key="tab"
                        class="tab" :class="{ active: activeTab === tab }"
                        @click="changeTab(tab)"
                    >{{ tab }} <span class="tab-count" v-if="tab !== 'Tất cả'">{{ getTabCount(tab) }}</span></button>
                </div>
            </div>

            <div class="date-filter-wrap">
                <div class="custom-dropdown date-filter-dropdown">
                    <div class="dropdown-trigger" @click.stop="isOpenDateDropdown = !isOpenDateDropdown">
                        <svg class="calendar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span>{{ selectedMonthYear || 'Tất cả' }}</span>
                        <svg class="chevron" :class="{ open: isOpenDateDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <transition name="fade-slide">
                        <ul v-if="isOpenDateDropdown" class="dropdown-menu">
                            <li v-for="m in availableMonths" :key="m" 
                                :class="{ active: selectedMonthYear === m }" 
                                @click="selectedMonthYear = m; currentPage = 1; isOpenDateDropdown = false">
                                {{ m }}
                            </li>
                        </ul>
                    </transition>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <BulkDeleteToolbar
            :selected-count="selectedIds.length"
            :total-count="filteredOrders.length"
            label="đơn hàng"
            :loading="isBulkDeleting"
            @clear="clearSelection"
            @delete-selected="removeSelected"
            @delete-all="removeAllFiltered"
        />

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="select-col">
                            <input
                                type="checkbox"
                                :checked="allCurrentPageSelected"
                                :disabled="!paginatedOrders.length"
                                @change="toggleCurrentPageSelection"
                            />
                        </th>
                        <th>Mã đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Ngày đặt hàng</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="paginatedOrders.length === 0">
                        <td colspan="7" class="empty">Không tìm thấy đơn hàng nào.</td>
                    </tr>
                    <tr v-for="(o, i) in paginatedOrders" :key="o.id" :class="{ 'row-selected': selectedIds.includes(o.id_backend) }">

                        <td class="select-col">
                            <input
                                type="checkbox"
                                :checked="selectedIds.includes(o.id_backend)"
                                @change="toggleItemSelection(o.id_backend)"
                            />
                        </td>

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
                            <div class="status-stack">
                                <span class="status-pill" :style="hasShipment(o) ? getShipmentStatusStyle(o) : getStatusStyle(o.status)">
                                    {{ getDisplayStatusLabel(o) }}
                                </span>
                                <span v-if="hasShipment(o)" class="tracking-code">
                                    {{ getShipment(o).provider }} - {{ getShipment(o).tracking_code }}
                                </span>
                                <span v-if="getShipment(o)?.status === 'delivery_failed'" class="failure-reason">
                                    Lý do: {{ getShipmentFailureReason(o) || 'Không liên hệ được người nhận' }}
                                    <small v-if="getShipmentAttemptText(o)">{{ getShipmentAttemptText(o) }}</small>
                                </span>
                                <span v-if="getShipment(o)?.status === 'returned' && getShipment(o)?.return_reason" class="return-note">
                                    {{ getShipment(o).return_reason }}<small v-if="getShipmentRefundNote(o)">{{ getShipmentRefundNote(o) }}</small>
                                </span>
                            </div>
                        </td>

                        <td>
                            <div class="actions">
                                <button class="act-btn" @click="openViewDetail(o)" title="Xem chi tiết">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                                
                                <button v-if="canCreateShipment(o)"
                                        class="act-btn logistics"
                                        @click="createShipment(o)"
                                        title="Tạo vận đơn demo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 7h11v9H3z"/>
                                        <path d="M14 10h4l3 3v3h-7z"/>
                                        <circle cx="7" cy="18" r="2"/>
                                        <circle cx="18" cy="18" r="2"/>
                                    </svg>
                                </button>

                                <button v-if="canAdvanceShipment(o)"
                                        class="act-btn logistics"
                                        @click="advanceShipment(o)"
                                        title="Cập nhật bước vận chuyển tiếp theo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </button>

                                <button v-if="canFailShipment(o)"
                                        class="act-btn danger"
                                        @click="failShipment(o)"
                                        title="Ghi nhận giao thất bại">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M12 9v4"/>
                                        <path d="M12 17h.01"/>
                                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    </svg>
                                </button>

                                <button v-if="canRetryShipment(o)"
                                        class="act-btn retry"
                                        @click="retryShipment(o)"
                                        :title="`Giao lại lần ${getShipmentAttempts(o) + 1}/3`">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 12a9 9 0 1 1-3-6.7"/>
                                        <path d="M21 3v6h-6"/>
                                    </svg>
                                </button>

                                <button v-if="!hasShipment(o) && !terminalStatuses.includes(o.status) && o.status !== 'refund_pending'"
                                        class="act-btn" style="color: #2563eb;"
                                        @click="confirmUpdateStatus(o.id_backend, o.status)" 
                                        title="Chuyển trạng thái tiếp theo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </button>

                                <!-- Nút xử lý hoàn trả -->
                                <button v-if="o.status === 'refund_pending'" 
                                        class="act-btn" style="color: #2563eb;"
                                        @click="confirmApproveRefund(o.id_backend)" 
                                        title="Chấp nhận hoàn trả">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </button>

                                <button v-if="o.status === 'refund_pending'" 
                                        class="act-btn" style="color: #dc2626;"
                                        @click="confirmRejectRefund(o.id_backend)" 
                                        title="Từ chối hoàn trả">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>

                                <button
                                    class="act-btn danger"
                                    @click="deleteOrder(o.id_backend)"
                                    title="Xóa đơn hàng"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M3 6h18"/>
                                        <path d="M8 6V4h8v2"/>
                                        <path d="M19 6l-1 14H6L5 6"/>
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
                Hiển thị {{ (currentPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPage * itemsPerPage, filteredOrders.length) }} của {{ filteredOrders.length }} đơn hàng
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
                    <span>Tổng doanh thu</span>
                    <b>{{ formatRevenue(totalRevenue) }}</b>
                </div>
            </div>
        </div>

        <!-- Modal xem chi tiết đơn hàng -->
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
                                    <span class="info-label">Khách hàng</span>
                                    <span class="info-value">{{ viewOrder.name }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Số điện thoại</span>
                                    <span class="info-value">{{ viewOrder.phone }}</span>
                                </div>
                                <div class="info-item" style="grid-column: span 2;">
                                    <span class="info-label">Địa chỉ giao hàng</span>
                                    <span class="info-value">{{ viewOrder.address }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Ngày đặt hàng</span>
                                    <span class="info-value">{{ viewOrder.date }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Thanh toán</span>
                                    <span class="info-value">{{ viewOrder.raw.PTTT }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <div class="section-title">Vận chuyển demo</div>
                            <div v-if="hasShipment(viewOrder)" class="shipment-card">
                                <div class="shipment-head">
                                    <div>
                                        <span class="shipment-provider">{{ getShipment(viewOrder).provider }}</span>
                                        <strong>{{ getShipment(viewOrder).tracking_code }}</strong>
                                    </div>
                                    <span class="status-pill" :style="getShipmentStatusStyle(viewOrder)">
                                        {{ getShipmentStatusLabel(getShipment(viewOrder).status, getShipment(viewOrder).status_label) }}
                                    </span>
                                </div>
                                <div v-if="getShipment(viewOrder).status === 'delivery_failed'" class="shipment-failure-box">
                                    <span>Lý do giao thất bại</span>
                                    <b>{{ getShipmentFailureReason(viewOrder) || 'Không liên hệ được người nhận' }}</b>
                                    <p v-if="getShipmentAttemptText(viewOrder)">{{ getShipmentAttemptText(viewOrder) }}. Tối đa 3 lần trước khi chuyển hoàn.</p>
                                </div>
                                <div v-if="getShipment(viewOrder).status === 'returned'" class="shipment-failure-box is-returned">
                                    <span>Đơn đã chuyển hoàn</span>
                                    <b>{{ getShipment(viewOrder).return_reason || 'Đã hoàn về kho' }}</b>
                                    <p v-if="getShipmentRefundNote(viewOrder)">{{ getShipmentRefundNote(viewOrder) }}</p>
                                </div>
                                <div class="shipment-grid">
                                    <div>
                                        <span>Phí giao hàng</span>
                                        <b>{{ formatMoney(getShipment(viewOrder).fee) }}</b>
                                    </div>
                                    <div>
                                        <span>Thu hộ COD</span>
                                        <b>{{ formatMoney(getShipment(viewOrder).cod_amount) }}</b>
                                    </div>
                                    <div>
                                        <span>Dự kiến giao</span>
                                        <b>{{ getShipment(viewOrder).expected_delivery_date || '-' }}</b>
                                    </div>
                                    <div>
                                        <span>Khu vực giao</span>
                                        <b>{{ getShipment(viewOrder).service_area || 'Tiêu chuẩn' }}</b>
                                    </div>
                                    <div>
                                        <span>Gói vận chuyển</span>
                                        <b>{{ getShipment(viewOrder).service_level || 'Giao tiêu chuẩn' }}</b>
                                    </div>
                                    <div>
                                        <span>Đồng bộ cuối</span>
                                        <b>{{ getShipment(viewOrder).last_sync_at ? new Date(getShipment(viewOrder).last_sync_at).toLocaleString('vi-VN') : '-' }}</b>
                                    </div>
                                </div>
                                <div class="shipment-events">
                                    <div v-for="(event, idx) in shippingTimeline" :key="idx" class="shipment-event">
                                        <span class="event-dot"></span>
                                        <div>
                                            <b>{{ event.label }}</b>
                                            <p>{{ event.note || 'Đã cập nhật trạng thái vận chuyển.' }}</p>
                                            <small>{{ event.date }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="shipment-empty">
                                <b>Chưa tạo vận đơn</b>
                                <p>Hệ thống sẽ tạo mã tracking demo và mô phỏng các bước lấy hàng, giao hàng, hoàn tất.</p>
                                <button v-if="canCreateShipment(viewOrder)" class="btn-export" @click="createShipment(viewOrder)">
                                    Tạo vận đơn ngay
                                </button>
                            </div>
                        </div>

                        <!-- Lý do hủy đơn hoặc hoàn trả -->
                        <div v-if="['cancelled', 'refund_pending', 'refunded'].includes(viewOrder.status)" class="detail-section">
                            <div class="section-title" :style="viewOrder.status === 'cancelled' ? 'color: #dc2626;' : 'color: #f97316;'">Lý do {{ viewOrder.status === 'cancelled' ? 'hủy đơn' : 'hoàn trả' }}</div>
                            <div class="cancel-reason-box" style="margin-bottom: 10px;">
                                {{ viewOrder.raw.lydo || 'Không có lý do cụ thể' }}
                            </div>
                            <div v-if="viewOrder.raw.refund_proof" class="cancel-reason-box" style="margin-top: 10px;">
                                <strong>Bằng chứng hoàn trả:</strong>
                                <div class="proof-preview" style="margin-top: 8px;">
                                    <template v-if="viewOrder.raw.refund_proof.match(/\.(jpeg|jpg|png|gif|webp)$/i)">
                                        <a :href="storageUrl(viewOrder.raw.refund_proof)" target="_blank" title="Nhấn để xem ảnh lớn">
                                            <img :src="storageUrl(viewOrder.raw.refund_proof)" alt="Bằng chứng" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb; cursor: zoom-in;" />
                                        </a>
                                    </template>
                                    <template v-else-if="viewOrder.raw.refund_proof.match(/\.(mp4|mov|avi|wmv)$/i)">
                                        <video :src="storageUrl(viewOrder.raw.refund_proof)" controls style="max-width: 100%; max-height: 250px; border-radius: 8px; border: 1px solid #e5e7eb; background: #000;"></video>
                                    </template>
                                    <template v-else>
                                        <a :href="storageUrl(viewOrder.raw.refund_proof)" target="_blank" style="color: #2563eb; text-decoration: underline; font-size: 13px;">Tải file bằng chứng đính kèm</a>
                                    </template>
                                </div>
                            </div>
                        </div>

                        
              <div class="timeline" v-if="orderSteps">
                <div class="tl-item" v-for="(step, i) in orderSteps" :key="i" :class="{ done: step.done }">
                  <div class="tl-col">
                    <div class="tl-dot"><svg v-if="step.done" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <div class="tl-line" v-if="i < orderSteps.length - 1" :class="{ done: step.done }"></div>
                  </div>
                  <div class="tl-content">
                    <p class="tl-label">{{ step.label }}</p>
                    <p class="tl-date">{{ step.date || '-' }}</p>
                  </div>
                </div>
              </div>

              <div class="refund-timeline-wrap" v-if="refundSteps" style="margin-top: 15px;">
                <h3 class="section-title" style="color: #f97316; font-size: 15px; margin-bottom: 12px;">Quá trình hoàn trả</h3>
                <div class="timeline refund-timeline">
                  <div class="tl-item" v-for="(step, i) in refundSteps" :key="'r'+i" :class="{ done: step.done }">
                    <div class="tl-col">
                      <div class="tl-dot refund-dot" :style="step.done ? 'background:#f97316; border-color:#f97316;' : ''"><svg v-if="step.done" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                      <div class="tl-line refund-line" v-if="i < refundSteps.length - 1" :style="step.done ? 'background:#f97316;' : ''"></div>
                    </div>
                    <div class="tl-content">
                      <p class="tl-label refund-label">{{ step.label }}</p>
                      <p class="tl-date">{{ step.date || '-' }}</p>
                    </div>
                  </div>
                </div>
              </div>

<div class="detail-section">
                            <div class="section-title">Danh sách sản phẩm</div>
                            <div class="items-list">
                                <div v-for="item in (viewOrder.raw.chi_tiets || viewOrder.raw.chiTiets || [])" :key="item.id_chitiet" class="order-item">
                                    <img :src="getOrderItemImage(item)" class="item-img" />
                                    <div class="item-info">
                                        <p class="item-name">
                                            {{ getOrderItemName(item) }}
                                            <span v-if="item.is_refund == 1" style="margin-left: 8px; font-size: 11px; font-weight: bold; color: #dc2626; background: #fee2e2; padding: 2px 6px; border-radius: 4px;">Đã chọn hoàn trả</span>
                                        </p>
                                        <p class="item-variant">{{ getOrderItemVariant(item) }}</p>
                                    </div>
                                    <div class="item-price-qty">
                                        <span class="iq-price">{{ formatMoney(item.gia) }}</span>
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
    background: linear-gradient(135deg, #2563eb, #2563eb);
    color: white; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: opacity 0.2s, transform 0.2s;
}
.btn-create:hover { opacity: 0.9; transform: translateY(-1px); }

/* FILTER */
.filter-wrap {
    background: white; border-radius: 14px; border: 1px solid #e2e8f0;
    padding: 16px 20px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 14px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
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
    outline: none; transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
    background: #ffffff;
}
.search-box input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.tabs { display: flex; gap: 6px; flex-wrap: wrap; }
.tab {
    padding: 8px 14px; border-radius: 8px; border: 1px solid #dbe4f0;
    background: #ffffff; font-size: 13px; font-weight: 600;
    color: #64748b; cursor: pointer; transition: all 0.2s; white-space: nowrap;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    display: flex; align-items: center; gap: 6px;
}
.tab:hover {
    background: #f8fafc;
    border-color: #93c5fd;
    color: #1e3a8a;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
}
.tab:focus-visible {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
}
.tab.active {
    background: #2563eb;
    border-color: #1d4ed8;
    color: white;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.22);
}

.tab-count {
    background: #e2e8f0;
    color: #475569;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1;
}
.tab.active .tab-count {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

/* â”€â”€ Custom Premium Dropdown â”€â”€ */
.custom-dropdown {
    position: relative;
    display: inline-block;
    min-width: 185px;
    user-select: none;
}

.dropdown-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 9px 16px;
    border-radius: 12px;
    border: 1.5px solid #3b82f6; /* Premium brand blue outline */
    background: white;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    transition: all .2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.dropdown-trigger:hover {
    border-color: #2563eb;
    box-shadow: 0 4px 12px rgba(37,99,235,0.06);
}

.dropdown-trigger .calendar-icon {
    width: 15px;
    height: 15px;
    color: #3b82f6;
}

.dropdown-trigger .chevron {
    width: 14px;
    height: 14px;
    color: #64748b;
    transition: transform .2s ease;
}

.dropdown-trigger .chevron.open {
    transform: rotate(180deg);
}

.dropdown-menu {
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
.dropdown-menu::-webkit-scrollbar {
    width: 6px;
}

.dropdown-menu::-webkit-scrollbar-track {
    background: transparent;
}

.dropdown-menu::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.dropdown-menu li {
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.12s ease;
    text-align: left;
}

.dropdown-menu li:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.dropdown-menu li.active {
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
tbody tr.row-selected { background: #eff6ff; }
tbody td { padding: 18px 20px; font-size: 13px; color: #334155; vertical-align: middle; }
.empty { text-align: center; color: #94a3b8; padding: 50px !important; }

.select-col { width: 44px; text-align: center; }
.select-col input { width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer; }
.select-col input:disabled { cursor: not-allowed; opacity: 0.45; }

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

.status-stack {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
}

.tracking-code {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
    max-width: 190px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.failure-reason {
    max-width: 260px;
    font-size: 12px;
    color: #dc2626;
    font-weight: 800;
    line-height: 1.35;
}
.failure-reason small,
.return-note small {
    display: block;
    margin-top: 3px;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
}
.return-note {
    max-width: 260px;
    font-size: 12px;
    color: #7c3aed;
    font-weight: 800;
    line-height: 1.35;
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
.act-btn.logistics { color: #2563eb; border-color: #bfdbfe; background: #eff6ff; }
.act-btn.logistics:hover { background: #dbeafe; border-color: #60a5fa; color: #1d4ed8; }
.act-btn.retry { color: #16a34a; border-color: #bbf7d0; background: #f0fdf4; }
.act-btn.retry:hover { background: #dcfce7; border-color: #86efac; color: #15803d; }
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
.revenue-chip svg { width: 20px; height: 20px; color: #2563eb; }
.revenue-chip span { font-size: 10px; font-weight: 600; color: #94a3b8; letter-spacing: 0.06em; display: block; }
.revenue-chip b { font-size: 16px; font-weight: 700; color: #2563eb; }

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
    letter-spacing: 0.1em; text-transform: capitalize; padding-bottom: 4px;
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
    background: linear-gradient(135deg, #2563eb, #2563eb);
    color: white; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: opacity 0.2s, transform 0.2s;
}
.btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }

/* RESPONSIVE */
@media (max-width: 768px) {
    .page { padding: 20px 16px; }
    .category-tabs { align-items: stretch; flex-direction: column; gap: 8px; }
    .category-tab-list { overflow-x: auto; }
    .category-tabs > .btn-export { align-self: flex-end; margin-bottom: 8px; }
    .search-row { flex-direction: column; align-items: stretch; }
    .tabs { overflow-x: auto; }
    .table-wrap { overflow-x: auto; }
    table { min-width: 700px; }
    .form-row { grid-template-columns: 1fr; }
    .table-footer { flex-direction: column; align-items: flex-start; }
}

/* ORDER DETAIL */
.detail-modal { max-width: 650px; }
.scrollable { max-height: 70vh; overflow-y: auto; padding-right: 24px; }
.scrollable::-webkit-scrollbar { width: 6px; }
.scrollable::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

.detail-section { margin-bottom: 24px; }
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    background: #f8fafc;
    padding: 20px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
}
.info-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.info-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: capitalize;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.info-value {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    padding: 8px 12px;
    background: white;
    border-radius: 8px;
    border: 1px solid #edf2f7;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    min-height: 35px;
    display: flex;
    align-items: center;
}

.shipment-card,
.shipment-empty {
    background: #f8fafc;
    border: 1px solid #dbe4f0;
    border-radius: 14px;
    padding: 18px;
}

.shipment-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 14px;
}

.shipment-head strong {
    display: block;
    color: #0f172a;
    font-size: 15px;
    margin-top: 4px;
}

.shipment-failure-box {
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid #fecaca;
    background: #fff1f2;
    margin: 0 0 14px;
}

.shipment-failure-box span {
    display: block;
    color: #ef4444;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.shipment-failure-box b {
    color: #991b1b;
    font-size: 14px;
}

.shipment-failure-box p {
    margin: 6px 0 0;
    color: #7f1d1d;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.45;
}

.shipment-failure-box.is-returned {
    border-color: #ddd6fe;
    background: #f5f3ff;
}

.shipment-failure-box.is-returned span {
    color: #7c3aed;
}

.shipment-failure-box.is-returned b,
.shipment-failure-box.is-returned p {
    color: #5b21b6;
}

.shipment-provider,
.shipment-grid span {
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.03em;
}

.shipment-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}

.shipment-grid > div {
    background: white;
    border: 1px solid #edf2f7;
    border-radius: 10px;
    padding: 10px 12px;
}

.shipment-grid b {
    display: block;
    color: #0f172a;
    font-size: 13px;
    margin-top: 4px;
}

.shipment-events {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.shipment-event {
    display: grid;
    grid-template-columns: 14px 1fr;
    gap: 10px;
    position: relative;
}

.shipment-event:not(:last-child)::before {
    content: "";
    position: absolute;
    left: 6px;
    top: 16px;
    bottom: -12px;
    width: 2px;
    background: #bfdbfe;
}

.event-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #2563eb;
    border: 3px solid #dbeafe;
    margin-top: 3px;
    z-index: 1;
}

.shipment-event b {
    color: #0f172a;
    font-size: 13px;
}

.shipment-event p {
    margin: 3px 0;
    color: #475569;
    font-size: 12px;
    line-height: 1.45;
}

.shipment-event small {
    color: #94a3b8;
    font-weight: 600;
}

.shipment-empty {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
}

.shipment-empty b { color: #0f172a; }
.shipment-empty p { margin: 0; color: #64748b; font-size: 13px; line-height: 1.5; }

.items-list { display: flex; flex-direction: column; gap: 12px; }
.order-item {
    display: flex; align-items: center; gap: 16px; padding: 14px;
    background: white; border: 1px solid #edf2f7; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease;
}
.order-item:hover { transform: translateY(-2px); border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.item-img { width: 64px; height: 64px; object-fit: cover; border-radius: 10px; border: 1px solid #edf2f7; background: #f8fafc; }
.item-info { flex: 1; }
.item-name { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
.item-variant { font-size: 12px; color: #64748b; margin: 0; }
.item-price-qty { text-align: right; }
.iq-price { display: block; font-size: 14px; font-weight: 700; color: #2563eb; }
.iq-qty { font-size: 12px; color: #94a3b8; font-weight: 600; }

.order-summary-box {
    margin-top: 14px; padding: 16px 20px; background: linear-gradient(135deg, #e0f2fe, #bae6fd);
    border-radius: 14px; border: 1px solid #bae6fd; display: flex; justify-content: flex-end;
    align-items: center;
}
.sum-row { display: flex; align-items: baseline; gap: 12px; }
.sum-row span { font-size: 13px; color: #0369a1; font-weight: 600; }
.final-total { font-size: 20px; font-weight: 800; color: #0369a1; }

.cancel-reason-box {
    margin-top: 8px;
    padding: 14px 18px;
    background: #fff5f5;
    border: 1.5px dashed #fecaca;
    border-radius: 12px;
    color: #e11d48;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>


<style scoped>
.category-tabs { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: -4px; border-bottom: 2px solid #e2e8f0; padding-bottom: 0; }
.category-tab-list { display: flex; align-items: flex-end; gap: 12px; }
.category-tabs > .btn-export { flex-shrink: 0; margin-bottom: 8px; }
.cat-tab { background: transparent; border: none; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
.cat-tab:hover { color: #2563eb; }
.cat-tab.active { color: #2563eb; border-bottom-color: #2563eb; }
</style>

<style scoped>
.timeline { display: flex; align-items: flex-start; justify-content: space-between; padding: 20px 0 10px; }
.tl-item { display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; position: relative; }
.tl-col { display: flex; align-items: center; width: 100%; position: relative; justify-content: center; margin-bottom: 10px; }

.tl-dot { width: 28px; height: 28px; border-radius: 50%; background: #fff; border: 2.5px solid #cbd5e1; z-index: 2; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 13px; color: white; }

.tl-dot svg { width: 16px; height: 16px; }
.tl-item.done .tl-dot { background: #2563eb; border-color: #2563eb; }
.tl-line { position: absolute; top: 12px; left: 50%; width: 100%; height: 3px; background: #e2e8f0; z-index: 1; }
.tl-line.done { background: #2563eb; }
.tl-content { padding: 0 10px; }
.tl-label { font-size: 13px; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
.tl-date { font-size: 11px; color: #94a3b8; margin: 0; }
.refund-timeline-wrap { background: #fff7ed; padding: 16px; border-radius: 12px; border: 1px dashed #fdba74; }
.refund-dot { border-color: #fdba74; }
.refund-label { color: #c2410c; }
</style>
