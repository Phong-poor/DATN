<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import api from '@/services/api'
import echo from '@/services/echo'
import { getToken, getUser } from '@/services/auth'
import { isFormDirty } from '@/services/unsavedChanges'
import { onUnmounted } from 'vue'
import swal from '@/services/swal'
import { normalizeImageUrl, productImageUrl, storageUrl, backendBaseUrl } from '@/services/urls'

const activeTab = ref('all')
const pageMode = ref('orders')

const orderSteps = computed(() => {
    const o = selectedOrder.value
    if (!o) return null
    const statusKey = o.trangthai
    return [
        { label: 'Đặt hàng', date: o.date || (o.created_at ? new Date(o.created_at).toLocaleString('vi-VN') : null) || '—', done: true },
        { label: 'Xác nhận', date: null, done: statusKey !== 'pending' },
        { label: 'Đang giao', date: null, done: statusKey === 'shipping' || statusKey === 'done' || statusKey.startsWith('refund') },
        { label: 'Hoàn thành', date: null, done: statusKey === 'done' || statusKey.startsWith('refund') },
    ]
})

const refundSteps = computed(() => {
    const o = selectedOrder.value
    if (!o) return null
    const statusKey = o.trangthai
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

const selectedOrder = ref(null)
const orders = ref([])
const isLoading = ref(true)

const tabs_mua = [
    { key: 'all', label: 'Tất cả' },
    { key: 'pending', label: 'Chờ xác nhận' },
    { key: 'confirmed', label: 'Đã xác nhận' },
    { key: 'shipping', label: 'Đang giao' },
    { key: 'done', label: 'Hoàn thành' },
    { key: 'cancelled', label: 'Đã hủy' },
]

const tabs_hoantra = [
    { key: 'refund_pending', label: 'Yêu cầu hoàn trả' },
    { key: 'refund_pickup', label: 'Chờ lấy hàng hoàn' },
    { key: 'refund_delivering', label: 'Đang giao hoàn' },
    { key: 'refund_received', label: 'Đã nhận hoàn' },
    { key: 'refunded', label: 'Đã hoàn tiền' },
]

const statusMap = {
    pending: { label: 'Chờ xác nhận', color: '#f59e0b', bg: '#fef3c7' },
    confirmed: { label: 'Đã xác nhận', color: '#0ea5e9', bg: '#e0f2fe' },
    shipping: { label: 'Đang giao', color: '#2563eb', bg: '#dbeafe' },
    done: { label: 'Hoàn thành', color: '#2563eb', bg: '#dcfce7' },
    refund_pending: { label: 'Yêu cầu hoàn trả', color: '#f97316', bg: '#ffedd5' },
    refund_pickup: { label: 'Chờ lấy hàng hoàn', color: '#d97706', bg: '#fef3c7' },
    refund_delivering: { label: 'Đang giao hoàn', color: '#2563eb', bg: '#dbeafe' },
    refund_received: { label: 'Đã nhận hoàn', color: '#0369a1', bg: '#e0f2fe' },
    refunded: { label: 'Đã hoàn tiền', color: '#3b82f6', bg: '#ede9fe' },
    refund_rejected: { label: 'Từ chối hoàn trả', color: '#dc2626', bg: '#fee2e2' },
    cancelled: { label: 'Đã hủy', color: '#dc2626', bg: '#fee2e2' },
}

// Cancellation state
const showCancelModal = ref(false)
const orderToCancel = ref(null)
const cancelReason = ref('')
const isSubmitting = ref(false)

const openCancelModal = (order) => {
    orderToCancel.value = order
    cancelReason.value = ''
    showCancelModal.value = true
}

const confirmCancel = async () => {
    if (!cancelReason.value.trim()) {
        swal.warning('Thông báo', 'Vui lòng nhập lý do hủy.')
        return
    }

    const isConfirmed = await swal.confirm('Xác nhận hủy', 'Bạn có chắc chắn muốn hủy đơn hàng này?')
    if (!isConfirmed) return

    isSubmitting.value = true
    try {
        const res = await api.post(`/orders/${orderToCancel.value.id_dathang}/cancel`, 
            { lydo: cancelReason.value }
        )

        if (res.data.success) {
            swal.success('Thành công', 'Hủy đơn hàng thành công!')
            showCancelModal.value = false
            await fetchOrders()
        }
    } catch (err) {
        swal.error('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra khi hủy đơn.')
    } finally {
        isSubmitting.value = false
    }
}

const closeCancelModal = async () => {
    if (cancelReason.value.trim()) {
        const confirmed = await swal.confirm(
            'Xác nhận đóng',
            'Bạn đã nhập lý do hủy đơn. Nếu đóng, nội dung này sẽ bị mất. Bạn vẫn muốn tiếp tục chứ?',
            'Có, đóng lại',
            'Không, ở lại'
        )
        if (!confirmed) return
    }
    showCancelModal.value = false
    cancelReason.value = ''
}

// Refund state
const showRefundModal = ref(false)
const orderToRefund = ref(null)
const refundReason = ref('')
const refundProof = ref(null)
const refundProofUrl = ref(null)
const refundSelectedItems = ref([])

const handleProofUpload = (e) => {
    const files = Array.from(e.target.files || [])
    if (files.length > 0) {
        refundProof.value = files.length === 1 ? files[0] : files
        if (refundProofUrl.value) URL.revokeObjectURL(refundProofUrl.value)
        refundProofUrl.value = URL.createObjectURL(files[0])
    } else {
        refundProof.value = null
        if (refundProofUrl.value) URL.revokeObjectURL(refundProofUrl.value)
        refundProofUrl.value = null
    }
}

const openRefundModal = (order) => {
    orderToRefund.value = order
    refundReason.value = ''
    refundProof.value = null
    if (refundProofUrl.value) URL.revokeObjectURL(refundProofUrl.value)
    refundProofUrl.value = null
    refundSelectedItems.value = []
    showRefundModal.value = true
}

const confirmRefund = async () => {
    if (refundSelectedItems.value.length === 0) {
        swal.warning('Thông báo', 'Vui lòng chọn ít nhất một sản phẩm để hoàn trả.')
        return
    }
    if (!refundReason.value.trim()) {
        swal.warning('Thông báo', 'Vui lòng nhập lý do hoàn trả.')
        return
    }
    if (!refundProof.value || (Array.isArray(refundProof.value) && refundProof.value.length === 0)) {
        swal.warning('Thông báo', 'Vui lòng tải lên ảnh/video bằng chứng.')
        return
    }

    isSubmitting.value = true
    try {
        const formData = new FormData()
        formData.append('lydo', refundReason.value)
        if (Array.isArray(refundProof.value)) {
            refundProof.value.forEach(f => formData.append('proofs[]', f))
        } else {
            formData.append('proof', refundProof.value)
        }
        refundSelectedItems.value.forEach(id => {
            formData.append('item_ids[]', id)
        })

        const res = await api.post(`/orders/${orderToRefund.value.id_dathang}/refund`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })

        if (res.data.success) {
            swal.success('Thành công', 'Đã gửi yêu cầu hoàn trả!')
            showRefundModal.value = false
            await fetchOrders()
            if (selectedOrder.value && selectedOrder.value.id_dathang === orderToRefund.value.id_dathang) {
                closeDetail()
            }
        }
    } catch (err) {
        swal.error('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra khi yêu cầu hoàn trả.')
    } finally {
        isSubmitting.value = false
    }
}

const closeRefundModal = async () => {
    if (refundReason.value.trim() || refundProof.value || refundSelectedItems.value.length > 0) {
        const confirmed = await swal.confirm(
            'Xác nhận đóng',
            'Bạn đang điền thông tin yêu cầu hoàn trả. Nếu đóng, các thông tin này sẽ bị mất. Bạn vẫn muốn tiếp tục chứ?',
            'Có, đóng lại',
            'Không, ở lại'
        )
        if (!confirmed) return
    }
    showRefundModal.value = false
    refundReason.value = ''
    refundProof.value = null
    if (refundProofUrl.value) {
        URL.revokeObjectURL(refundProofUrl.value)
        refundProofUrl.value = null
    }
    refundSelectedItems.value = []
}

const isRefundable = (order) => {
    if (order.trangthai !== 'done') return false;
    const updated = new Date(order.updated_at).getTime();
    const now = new Date().getTime();
    const diffHours = (now - updated) / (1000 * 60 * 60);
    return diffHours <= 42;
}

const handleReorder = async (order) => {
    const isConfirmed = await swal.confirm('Xác nhận mua lại', 'Bạn có chắc chắn muốn mua lại các sản phẩm này?')
    if (!isConfirmed) return

    try {
        const res = await api.post(`/orders/${order.id_dathang}/reorder`)

        if (res.data.success) {
            swal.success('Thành công', res.data.message)
            // Redirect to cart
            window.location.href = '/gio-hang'
        }
    } catch (err) {
        swal.error('Lỗi', 'Lỗi khi mua lại sản phẩm.')
    }
}

const fetchOrders = async () => {
    isLoading.value = true
    try {
        const res = await api.get('/orders')
        if (res.data.success) {
            orders.value = res.data.orders
            if (selectedOrder.value) {
                const fresh = orders.value.find(o => (o.id_dathang || o.id) === (selectedOrder.value.id_dathang || selectedOrder.value.id))
                if (fresh) {
                    selectedOrder.value = { ...fresh }
                }
            }
        }
    } catch (err) {
        console.error('Lỗi lấy đơn hàng:', err)
        swal.error('Lỗi', 'Không thể tải danh sách đơn hàng.')
    } finally {
        isLoading.value = false
    }
}

const formatPrice = (val) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val)
}

const filtered = computed(() => {
    if (pageMode.value === 'orders') {
        if (activeTab.value === 'all') return orders.value.filter(o => !o.trangthai.startsWith('refund'))
        return orders.value.filter(o => o.trangthai === activeTab.value)
    } else {
        if (activeTab.value === 'all_refund') return orders.value.filter(o => o.trangthai.startsWith('refund'))
        return orders.value.filter(o => o.trangthai === activeTab.value)
    }
})

const openDetail = async (order) => {
    selectedOrder.value = { ...order }
    const id = order.id_dathang || order.id
    if (!id) return
    try {
        const res = await api.get(`/orders/${id}`)
        if (res.data && res.data.order) {
            selectedOrder.value = { ...res.data.order }
        }
    } catch (e) { }
}
const closeDetail = () => { selectedOrder.value = null }

const getFullProductName = (item) => {
    const sp = item.bien_the?.san_pham || item.bien_the?.sanPham || {}
    let name = sp.tenSP || 'Sản phẩm'
    let specs = []
    try {
        const tskt = typeof sp.thong_so_ky_thuat === 'string' 
            ? JSON.parse(sp.thong_so_ky_thuat || '[]') 
            : (sp.thong_so_ky_thuat || [])
        if (Array.isArray(tskt)) {
            specs = tskt.map(s => s.giatri).filter(Boolean)
        }
    } catch (e) { console.error('Lỗi parse thong_so_ky_thuat:', e) }
    
    return specs.length > 0 ? `${name} ${specs.join(' ')}` : name
}

const getProductImage = (item) => {
    const sp = item.bien_the?.san_pham || item.bien_the?.sanPham || {}
    const variant = item.bien_the || item.bienThe || null
    return productImageUrl(sp, variant, 'https://placehold.co/200')
}

const getRefundModalItems = (order) => {
    if (!order) return []
    const items = order.chi_tiets || order.items || order.chiTiets || []
    const status = String(order.trangthai || order.status || '')
    if (!status.startsWith('refund')) {
        return items
    }
    const filtered = items.filter(i => i.is_refund == 1 || i.is_refund === true || i.hoantien == 1 || i.hoantien === true)
    return filtered.length > 0 ? filtered : items
}

const isRefundItem = (item) => {
    return item?.is_refund == 1 || item?.is_refund === true || item?.hoantien == 1 || item?.hoantien === true
}

const getRefundProofFiles = (order) => {
    if (!order) return []

    if (typeof order === 'string') {
        const trimmed = order.trim()
        if (!trimmed) return []
        if ((trimmed.startsWith('[') && trimmed.endsWith(']')) || (trimmed.startsWith('{') && trimmed.endsWith('}'))) {
            try {
                const parsed = JSON.parse(trimmed)
                return getRefundProofFiles(parsed)
            } catch (e) { }
        }
        if (trimmed.includes(',')) {
            return trimmed.split(',').map(s => s.trim()).filter(Boolean)
        }
        return [trimmed]
    }

    if (Array.isArray(order)) {
        return order.flatMap(item => getRefundProofFiles(item)).filter(Boolean)
    }

    if (typeof order === 'object') {
        const raw = order.raw || order
        let payData = raw.du_lieu_thanh_toan || raw.payment_data
        if (typeof payData === 'string') {
            try { payData = JSON.parse(payData) } catch (e) { }
        }

        const candidate = raw.minh_chung_hoan_tien
            || raw.refund_proof
            || raw.refund_proofs
            || raw.minh_chung
            || raw.proof
            || raw.proofs
            || payData?.minh_chung_hoan_tien
            || payData?.refund_proof
            || payData?.refund_proofs

        if (candidate && candidate !== order && candidate !== raw) {
            return getRefundProofFiles(candidate)
        }
    }

    return []
}

const uploadRefundProof = async (event, order) => {
    const files = event.target.files
    if (!files || files.length === 0) return
    const id = order.id_dathang || order.id
    if (!id) return

    const formData = new FormData()
    for (let i = 0; i < files.length; i++) {
        formData.append('proofs[]', files[i])
    }

    try {
        swal.loading('Đang tải lên tệp bằng chứng...')
        const res = await api.post(`/donhang/${id}/refund-proof`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        swal.closeLoading()
        if (res.data && res.data.success) {
            swal.success('Tải lên bằng chứng thành công!')
            if (res.data.order) {
                const updated = res.data.order
                const proofVal = updated.minh_chung_hoan_tien || updated.refund_proof
                order.minh_chung_hoan_tien = proofVal
                order.refund_proof = proofVal
                if (selectedOrder.value) {
                    selectedOrder.value = {
                        ...selectedOrder.value,
                        minh_chung_hoan_tien: proofVal,
                        refund_proof: proofVal
                    }
                }
            }
            await fetchOrders()
        } else {
            swal.error(res.data?.message || 'Không thể tải lên tệp.')
        }
    } catch (err) {
        swal.closeLoading()
        swal.error('Lỗi khi tải lên tệp bằng chứng.')
    }
}

const getProofMediaUrl = (file) => {
    if (!file) return ''
    if (/^(https?:)?\/\//i.test(file) || file.startsWith('data:') || file.startsWith('blob:')) {
        return file
    }
    const cleanPath = String(file).replace(/\\/g, '/').replace(/^\/+/, '').replace(/^public\//i, '').replace(/^storage\//i, '')
    return storageUrl(cleanPath)
}

const getProofProxyUrl = (file) => {
    if (!file) return ''
    if (/^(https?:)?\/\//i.test(file) || file.startsWith('data:') || file.startsWith('blob:')) {
        return file
    }
    const cleanPath = String(file).replace(/\\/g, '/').replace(/^\/+/, '').replace(/^public\//i, '').replace(/^storage\//i, '')
    return `${backendBaseUrl}/api/refund-file?path=${encodeURIComponent(cleanPath)}`
}

const isImageFile = (file) => {
    if (!file) return false
    const f = String(file).toLowerCase().trim()
    return /\.(jpeg|jpg|png|gif|webp|svg|bmp|heic|heif)$/i.test(f)
}

const isVideoFile = (file) => {
    if (!file) return false
    const f = String(file).toLowerCase().trim()
    if (isImageFile(file)) return false
    return /\.(mp4|mov|avi|wmv|webm|mkv|flv|3gp|m4v|quicktime)$/i.test(f)
        || f.includes('screen recording')
        || f.startsWith('refund_proofs/')
        || f.startsWith('refunds/')
        || !f.includes('.')
}

onMounted(() => {
    fetchOrders()
    
    const user = getUser()

    if (user && (user.id || user.id_khachhang || user.id_user)) {
        const userId = user.id || user.id_khachhang || user.id_user
        
        let isFetching = false
        const handleStatusUpdate = async (data) => {
            if (data && data.id_dathang) {
                const idx = orders.value.findIndex(o => Number(o.id_dathang) === Number(data.id_dathang))
                if (idx !== -1 && data.trangthai) {
                    orders.value[idx].trangthai = data.trangthai
                }
            }
            if (isFetching) return
            isFetching = true
            try {
                await fetchOrders()
            } finally {
                setTimeout(() => { isFetching = false }, 1000)
            }
        }

        if (getToken()) {
            try { echo.private(`user.${userId}`).listen('.order.status.updated', handleStatusUpdate) } catch (e) {}
        }
        try { echo.channel(`user-orders.${userId}`).listen('.order.status.updated', handleStatusUpdate) } catch (e) {}
        try { echo.channel('admin-orders').listen('.order.status.updated', handleStatusUpdate) } catch (e) {}
    }
})

onUnmounted(() => {
    const user = getUser()
    const userId = user?.id || user?.id_user
    if (userId) {
        echo.leave(`user.${userId}`)
    }
})
</script>

<template>
    <div class="page">
        <!-- Cancellation Modal -->
        <Teleport to="body">
            <transition name="fade">
            <div class="overlay" v-if="showCancelModal" @click.self="closeCancelModal">
                <div class="modal mini-modal">
                    <div class="modal-head">
                        <h2 class="modal-title">Lý do hủy đơn</h2>
                        <button class="close-btn" no-guard @click="closeCancelModal">×</button>
                    </div>
                    <div class="modal-body">
                        <textarea v-model="cancelReason" class="cancel-textarea mb-3" rows="3" placeholder="Nhập lý do hủy đơn hàng..."></textarea>
                        <div class="d-flex gap-2 justify-content-end mt-3">
                            <button class="btn btn-secondary" no-guard @click="closeCancelModal">Đóng</button>
                            <button class="btn btn-danger" @click="confirmCancel" :disabled="isSubmitting">
                                {{ isSubmitting ? 'Đang xử lý...' : 'Xác nhận hủy' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
        </Teleport>

        <!-- Refund Modal -->
        <Teleport to="body">
            <transition name="fade">
            <div class="overlay" v-if="showRefundModal" @click.self="closeRefundModal">
                <div class="modal mini-modal">
                    <div class="modal-head">
                        <h2 class="modal-title">Yêu cầu hoàn trả</h2>
                        <button class="close-btn" no-guard @click="closeRefundModal">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 13px; font-weight: 600;">Chọn sản phẩm hoàn trả</label>
                            <div class="refund-items-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px;">
                                <div v-for="item in (orderToRefund?.chi_tiets || orderToRefund?.chiTiets || [])" :key="item.id_bienthe" class="d-flex align-items-center gap-2 mb-2 pb-2" style="border-bottom: 1px solid #f1f5f9;">
                                    <input type="checkbox" :id="'refund_item_' + item.id_bienthe" :value="item.id_bienthe" v-model="refundSelectedItems" style="width: 16px; height: 16px; cursor: pointer;">
                                    <label :for="'refund_item_' + item.id_bienthe" class="d-flex align-items-center gap-2 m-0" style="cursor: pointer; flex: 1;">
                                        <img :src="getProductImage(item)" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;">
                                        <div>
                                            <div style="font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">{{ getFullProductName(item) }}</div>
                                            <div style="font-size: 11px; color: #64748b;">Phân loại: {{ item.bien_the?.ten_bienthe || 'Mặc định' }} | SL: {{ item.soluong }}</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <textarea v-model="refundReason" class="cancel-textarea mb-3" rows="3" placeholder="Nhập lý do hoàn trả..."></textarea>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 13px; font-weight: 600;">Hình ảnh / Video bằng chứng</label>
                            <input type="file" multiple @change="handleProofUpload" class="form-control" accept="image/*,video/*" />
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">Hỗ trợ ảnh hoặc video (tối đa 20MB)</small>
                            
                            <div v-if="refundProofUrl" class="mt-3" style="text-align: center;">
                                <img v-if="refundProof && refundProof.type.startsWith('image/')" :src="refundProofUrl" alt="Bằng chứng" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" />
                                <video v-else-if="refundProof && refundProof.type.startsWith('video/')" :src="refundProofUrl" controls style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></video>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-3">
                            <button class="btn btn-secondary" no-guard @click="closeRefundModal">Đóng</button>
                            <button class="btn btn-warning" style="color: white; font-weight: bold;" @click="confirmRefund" :disabled="isSubmitting">
                                {{ isSubmitting ? 'Đang gửi...' : 'Gửi yêu cầu' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
        </Teleport>

        <!-- Detail Modal -->
        <Teleport to="body">
            <transition name="fade">
            <div class="overlay" v-if="selectedOrder" @click.self="closeDetail">
                <div class="modal">
                    <div class="modal-head">
                        <div>
                            <h2 class="modal-title">Chi tiết đơn hàng</h2>
                            <p class="modal-id">Mã đơn: #VT-2026-{{ String(selectedOrder.id_dathang).padStart(3, '0') }}</p>
                        </div>
                        <button class="close-btn" @click="closeDetail">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="modal-body">
                        <!-- Status badge -->
                        <div class="modal-status"
                            :style="{ color: statusMap[selectedOrder.trangthai].color, background: statusMap[selectedOrder.trangthai].bg }">
                            {{ statusMap[selectedOrder.trangthai].label }}
                        </div>

                        <!-- Cancellation info if cancelled or refund -->
                        <div v-if="selectedOrder.trangthai === 'cancelled' || selectedOrder.trangthai?.startsWith('refund') || getRefundProofFiles(selectedOrder).length > 0" style="margin-bottom: 18px; font-size: 14px; color: #1e293b; line-height: 1.5;">
                            <div style="margin-bottom: 8px;">
                                <strong style="color: #0f172a; font-weight: 700;">Lý do:</strong> <span style="color: #334155;">{{ selectedOrder.lydo || 'Khách hàng không nhập lý do' }}</span>
                            </div>

                            <!-- Ảnh / Video bằng chứng nằm ở ngay dưới lý do -->
                            <div v-if="getRefundProofFiles(selectedOrder).length > 0" class="proof-media-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-top: 10px;">
                                <div v-for="(file, pIdx) in getRefundProofFiles(selectedOrder)" :key="pIdx" class="proof-media-item" style="border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative;">
                                    <template v-if="isImageFile(file)">
                                        <a :href="getProofMediaUrl(file)" target="_blank" title="Bấm để xem ảnh phóng to" style="display: block; text-align: center; background: #f8fafc;">
                                            <img :src="getProofMediaUrl(file)" @error="$event.target.src = getProofProxyUrl(file)" alt="Bằng chứng" style="width: 100%; height: 140px; object-fit: cover; transition: transform 0.2s;" />
                                        </a>
                                    </template>
                                    <template v-else-if="isVideoFile(file)">
                                        <video controls style="width: 100%; height: 140px; object-fit: cover; background: #000; display: block;" preload="metadata">
                                            <source :src="getProofMediaUrl(file)" />
                                            <source :src="getProofProxyUrl(file)" />
                                            Trình duyệt không hỗ trợ xem video.
                                        </video>
                                    </template>
                                    <template v-else>
                                        <div style="padding: 20px 10px; text-align: center;">
                                            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="#2563eb" stroke-width="2" style="margin: 0 auto 8px;">
                                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                                <polyline points="13 2 13 9 20 9"></polyline>
                                            </svg>
                                            <a :href="getProofProxyUrl(file)" target="_blank" style="color: #2563eb; font-size: 12px; font-weight: 600; text-decoration: underline; word-break: break-all;">Tải file bằng chứng #{{ pIdx + 1 }}</a>
                                        </div>
                                    </template>
                                    <a :href="getProofProxyUrl(file)" target="_blank" style="display: block; padding: 4px 6px; font-size: 11px; text-align: center; background: #f8fafc; color: #2563eb; font-weight: 600; text-decoration: underline; border-top: 1px solid #e2e8f0;">
                                        🔍 Mở tệp gốc / Tải về
                                    </a>
                                </div>
                            </div>

                            <!-- Nếu đơn chưa có tệp bằng chứng -> Hiện nút bấm tải lên ngay dưới lý do -->
                            <div v-else style="margin-top: 10px; padding: 12px 14px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; font-size: 13px; color: #475569;">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                    <span>📁 <strong>Bằng chứng:</strong> Chưa có tệp đính kèm</span>
                                    <label style="margin: 0; padding: 6px 14px; background: linear-gradient(135deg, #0284c7, #2563eb); color: #ffffff; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(37,99,235,0.2);">
                                        <span>📤 Tải lên ảnh / video</span>
                                        <input type="file" multiple accept="image/*,video/*" @change="uploadRefundProof($event, selectedOrder)" style="display: none;" />
                                    </label>
                                </div>
                            </div>
                        </div>

              <div class="timeline" v-if="orderSteps && !selectedOrder.trangthai?.startsWith('refund')">
                <div class="tl-item" v-for="(step, i) in orderSteps" :key="i" :class="{ done: step.done }">
                  <div class="tl-col">
                    <div class="tl-dot"><svg v-if="step.done" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <div class="tl-line" v-if="i < orderSteps.length - 1" :class="{ done: step.done }"></div>
                  </div>
                  <div class="tl-content">
                    <p class="tl-label">{{ step.label }}</p>
                    <p class="tl-date">{{ step.date || '—' }}</p>
                  </div>
                </div>
              </div>

              <!-- Quá trình hoàn trả (Chiều dọc - Vertical Timeline) -->
              <div class="refund-timeline-vertical" v-if="refundSteps" style="margin-top: 18px; margin-bottom: 22px;">
                <h3 class="section-title" style="color: #ea580c; font-size: 14px; font-weight: 700; margin-bottom: 14px; display: flex; align-items: center; gap: 6px;">
                  <span>🔄</span> Quá trình hoàn trả
                </h3>
                <div style="display: flex; flex-direction: column; gap: 0; padding-left: 8px;">
                  <div v-for="(step, i) in refundSteps" :key="'rv'+i" style="display: flex; align-items: flex-start; gap: 14px; position: relative; padding-bottom: 18px;">
                    <!-- Vertical line -->
                    <div v-if="i < refundSteps.length - 1" style="position: absolute; left: 13px; top: 26px; bottom: 0; width: 2px;" :style="step.done ? 'background: #f97316;' : 'background: #e2e8f0;'"></div>
                    
                    <!-- Dot icon -->
                    <div style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; z-index: 2; transition: all 0.2s;" :style="step.done ? 'background: #f97316; color: #fff; box-shadow: 0 2px 6px rgba(249,115,22,0.35);' : 'background: #ffffff; border: 2px solid #cbd5e1; color: #94a3b8;'">
                      <svg v-if="step.done" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                      </svg>
                      <span v-else style="font-size: 11px; font-weight: 700;">{{ i + 1 }}</span>
                    </div>

                    <!-- Label and Date -->
                    <div style="flex: 1; min-width: 0; padding-top: 3px;">
                      <div style="font-size: 13.5px; font-weight: 600; line-height: 1.3;" :style="step.done ? 'color: #c2410c;' : 'color: #64748b;'">
                        {{ step.label }}
                      </div>
                      <div style="font-size: 11.5px; margin-top: 2px;" :style="step.done ? 'color: #ea580c;' : 'color: #94a3b8;'">
                        {{ step.date || '—' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

<!-- Products -->
                        <div class="modal-section">
                            <h3 class="section-title">Sản phẩm</h3>
                            <div class="modal-item" v-for="item in getRefundModalItems(selectedOrder)" :key="item.id_dathang_chi_tiet || item.id_bienthe">
                                <img :src="getProductImage(item)" alt="product" />
                                <div class="modal-item-info">
                                    <p class="modal-item-name">
                                        {{ getFullProductName(item) }}
                                        <span v-if="isRefundItem(item)" style="margin-left: 6px; font-size: 10px; font-weight: bold; color: #dc2626; background: #fee2e2; padding: 2px 5px; border-radius: 4px;">Đã hoàn trả</span>
                                    </p>
                                    <p class="modal-item-variant">{{ item.bien_the?.ten_bienthe }}</p>
                                    <p class="modal-item-qty">Số lượng: {{ item.soluong }}</p>
                                </div>
                                <p class="modal-item-price">{{ formatPrice(item.gia) }}</p>
                            </div>
                        </div>

                        <div class="modal-breakdown" style="border-top: none; padding-top:10px; margin-bottom:10px; font-size:13px; color:#cbd5e1; display:flex; flex-direction:column; gap:5px; box-sizing:border-box;" v-if="selectedOrder.xu_dung > 0">
                            <div class="d-flex justify-content-between">
                                <span>Sử dụng xu:</span>
                                <span style="color:#f59e0b;">-{{ selectedOrder.xu_dung.toLocaleString('vi-VN') }} xu (-{{ formatPrice(selectedOrder.xu_dung) }})</span>
                            </div>
                        </div>

                        <!-- Thành tiền / Tổng cộng (Bỏ thanh ngang và bỏ viền ngoài) -->
                        <div class="modal-total" style="border-top: none; border: none; background: transparent; padding: 10px 0 0; box-shadow: none;">
                            <span>Tổng cộng</span>
                            <span class="total-val">{{ formatPrice(selectedOrder.tongtien) }}</span>
                        </div>

                        <!-- Action buttons in modal -->
                        <div class="modal-foot mt-4 d-flex gap-2">
                            <button v-if="['pending', 'confirmed'].includes(selectedOrder.trangthai)" 
                                class="btn-cancel w-100" @click="openCancelModal(selectedOrder)">Hủy đơn</button>
                            
                            <button v-if="['done', 'cancelled', 'refunded', 'refund_rejected'].includes(selectedOrder.trangthai)" 
                                class="btn-reorder w-100" @click="handleReorder(selectedOrder)">Mua lại</button>
                            
                            <button v-if="isRefundable(selectedOrder)" 
                                class="btn-refund w-100" @click="openRefundModal(selectedOrder)">Hoàn trả</button>
                        </div>
                        <div v-if="selectedOrder.trangthai === 'refund_rejected'" class="mt-2 w-100">
                            <button class="btn-refund w-100" disabled style="opacity: 0.6; cursor: not-allowed; background: #e5e7eb; color: #9ca3af; border-color: #d1d5db; white-space: nowrap;">Bị từ chối</button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
        </Teleport>

        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Đơn hàng của tôi</h1>
                <p class="page-sub">Theo dõi và quản lý đơn hàng</p>
            </div>

            <!-- Tabs Group -->
            <div class="tabs-group-wrapper" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
              <div class="tabs-row" style="display: flex; align-items: center; gap: 10px;">
                <div class="tabs-label" style="font-weight: 600; color: #1e293b; min-width: 100px; font-size: 14px;">
                    Mua ({{ orders.filter(o => !o.trangthai.startsWith('refund')).length }}):
                </div>
                <div class="tabs" style="margin-bottom: 0;">
                    <button v-for="tab in tabs_mua" :key="tab.key" class="tab" :class="{ active: activeTab === tab.key }"
                        @click="activeTab = tab.key; pageMode = 'orders'">
                        {{ tab.label }}
                        <span class="tab-count" v-if="tab.key !== 'all'">
                            {{orders.filter(o => o.trangthai === tab.key).length}}
                        </span>
                    </button>
                </div>
            </div>
              <div class="tabs-row" style="display: flex; align-items: center; gap: 10px;">
                <div class="tabs-label" style="font-weight: 600; color: #f97316; min-width: 100px; font-size: 14px;">
                    Hoàn trả ({{ orders.filter(o => o.trangthai.startsWith('refund')).length }}):
                </div>
                <div class="tabs" style="margin-bottom: 0;">
                    <button v-for="tab in [{key: 'all_refund', label: 'Tất cả'}, ...tabs_hoantra]" :key="tab.key" class="tab" :class="{ active: activeTab === tab.key }"
                        @click="activeTab = tab.key; pageMode = 'refund'">
                        {{ tab.label }}
                        <span class="tab-count" v-if="tab.key !== 'all_refund'">
                            {{orders.filter(o => o.trangthai === tab.key).length}}
                        </span>
                    </button>
                </div>
            </div>
            </div>

            <!-- Order list -->
            <div class="orders-list">
                <div v-if="isLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Đang tải đơn hàng...</p>
                </div>

                <div v-else-if="filtered.length === 0" class="empty">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="2" y="3" width="20" height="14" rx="2" />
                        <path d="M8 21h8M12 17v4" />
                    </svg>
                    <p>Không có đơn hàng nào</p>
                </div>

                <div class="order-card" v-for="order in filtered" :key="order.id_dathang">
                    <div class="order-head">
                        <div class="order-meta">
                            <span class="order-id">#VT-2026-{{ String(order.id_dathang).padStart(3, '0') }}</span>
                            <span class="order-date">{{ new Date(order.created_at).toLocaleDateString('vi-VN') }}</span>
                        </div>
                        <span class="order-badge"
                            :style="{ color: statusMap[order.trangthai].color, background: statusMap[order.trangthai].bg }">
                            {{ statusMap[order.trangthai].label }}
                        </span>
                    </div>

                    <div class="order-items">
                        <div class="order-item" v-for="item in (order.chi_tiets || [])" :key="item.id_dathang_chi_tiet">
                            <img :src="getProductImage(item)" alt="product" />
                            <div class="order-item-info">
                                <p class="order-item-name">
                                    {{ getFullProductName(item) }}
                                    <span v-if="item.is_refund == 1" style="margin-left: 6px; font-size: 10px; font-weight: bold; color: #dc2626; background: #fee2e2; padding: 2px 5px; border-radius: 4px;">Đã hoàn trả</span>
                                </p>
                                <p class="order-item-variant">{{ item.bien_the?.ten_bienthe }}</p>
                                <p class="order-item-qty">x{{ item.soluong }}</p>
                            </div>
                            <p class="order-item-price">{{ formatPrice(item.gia) }}</p>
                        </div>
                    </div>

                    <div class="order-foot" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; box-sizing:border-box;">
                        <div class="order-info-coins" style="font-size:12px; text-align:left; display:flex; gap:15px; flex:1;">
                            <span v-if="order.xu_dung > 0" style="color:#f59e0b; display:inline-flex; align-items:center; gap:3px;">🪙 Đã dùng: -{{ order.xu_dung.toLocaleString('vi-VN') }} xu</span>

                        </div>
                        <span class="order-total" style="white-space:nowrap; margin-left:auto;">Tổng: <strong>{{ formatPrice(order.tongtien) }}</strong></span>
                        <div class="d-flex gap-2">
                            <button v-if="['pending', 'confirmed'].includes(order.trangthai)" 
                                class="btn-cancel" @click="openCancelModal(order)">Hủy đơn</button>
                            
                            <button v-if="['done', 'cancelled', 'refunded', 'refund_rejected'].includes(order.trangthai)" 
                                class="btn-reorder" @click="handleReorder(order)">Mua lại</button>

                            <button class="btn-detail" @click="openDetail(order)">Chi tiết</button>
                            
                            <button v-if="isRefundable(order)" 
                                class="btn-refund" @click="openRefundModal(order)">Hoàn trả</button>
                        </div>
                    </div>
                    <div v-if="order.trangthai === 'refund_rejected'" style="padding: 0 20px 20px 20px;">
                        <button class="btn-refund w-100" disabled style="opacity: 0.6; cursor: not-allowed; background: #e5e7eb; color: #9ca3af; border-color: #d1d5db; white-space: nowrap;">Bị từ chối</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

.page {
    min-height: 100vh;
    background: #0d1b2e;
    padding-block-start: var(--section-padding-mobile);
    padding-block-end: var(--section-padding-mobile);
    padding-inline: var(--container-padding-desktop);
}

.site-container {
    max-width: var(--container-max-width);
    margin: auto;
}

.page-header {
    margin-bottom: 24px;
}

.page-title {
    font-size: 22px;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0 0 4px;
}

.page-sub {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

/* TABS */
.tabs {
    display: flex;
    gap: var(--space-2);
    background: #111f35;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: var(--radius-md);
    padding: 6px;
    margin-bottom: var(--space-5);
    flex-wrap: wrap;
}

.tab {
    padding: 8px 16px;
    border-radius: var(--radius-sm);
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.tab:hover {
    background: #111f35;
    color: #cbd5e1;
}

.tab.active {
    background: #2563eb;
    color: #fff;
    font-weight: 600;
}

.tab-count {
    background: rgba(255, 255, 255, 0.25);
    padding: 1px 7px;
    border-radius: 99px;
    font-size: 11px;
}

.tab:not(.active) .tab-count {
    background: #111f35;
    color: #64748b;
}

/* ORDER CARDS */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.empty {
    text-align: center;
    padding: 60px 0;
    color: #94a3b8;
}

.empty svg {
    width: 48px;
    height: 48px;
    stroke: #cbd5e1;
    stroke-width: 1.5;
    fill: none;
    margin-bottom: 12px;
}

.empty p {
    font-size: 14px;
}

.order-card {
    background: #111f35;
    border-radius: var(--radius-lg);
    border: 1px solid rgba(255,255,255,0.07);
    overflow: hidden;
}

.order-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-3) var(--container-padding-desktop);
    border-bottom: 1px solid rgba(255,255,255,0.07);
}

.order-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.order-id {
    font-size: 13px;
    font-weight: 700;
    color: #f1f5f9;
}

.order-date {
    font-size: 12px;
    color: #94a3b8;
}

.order-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 99px;
}

.order-items {
    padding: 12px 20px;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 8px 0;
}

.order-item img {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-sm);
    object-fit: cover;
    border: 1px solid rgba(255,255,255,0.07);
    flex-shrink: 0;
}

.order-item-info {
    flex: 1;
    min-width: 0;
}

.order-item-name {
    font-size: 14px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0 0 2px;
}

.order-item-variant {
    font-size: 12px;
    color: #64748b;
    margin: 0 0 4px;
}

.order-item-qty {
    font-size: 12px;
    color: #94a3b8;
    margin: 0;
}

.order-item-price {
    font-size: 14px;
    font-weight: 700;
    color: #2563eb;
    flex-shrink: 0;
}

.order-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    border-top: 1px solid rgba(255,255,255,0.07);
    background: #0d1b2e;
}

.order-total {
    font-size: 13px;
    color: #64748b;
}

.order-total strong {
    color: #f1f5f9;
    font-size: 15px;
}

.btn-detail, .btn-reorder, .btn-cancel {
    padding: 8px 18px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    border: 1.5px solid transparent;
}

.btn-detail {
    background: #111f35;
    border-color: #2563eb;
    color: #2563eb;
}

.btn-detail:hover {
    background: #2563eb;
    color: #fff;
}

.btn-reorder {
    background: #2563eb;
    color: #fff;
}

.btn-reorder:hover {
    background: #1d4ed8;
}

.btn-cancel {
    background: #111f35;
    border-color: #dc2626;
    color: #dc2626;
}

.btn-cancel:hover {
    background: #dc2626;
    color: #fff;
}

.btn-refund {
    background: #fff;
    border-color: #f97316;
    color: #f97316;
    border: 1.5px solid #f97316;
}

.btn-refund:hover {
    background: #f97316;
    color: #fff;
}

/* MODAL */
.overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    z-index: 9000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    backdrop-filter: blur(3px);
}

.modal {
    background: #111f35;
    border-radius: var(--radius-xl);
    width: 100%;
    max-width: 560px;
    max-height: 90vh;
    overflow-y: auto;
}

.mini-modal {
    max-width: 400px;
}

.modal-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 24px 24px 0;
}

.modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0;
}

.modal-id {
    font-size: 12px;
    color: #94a3b8;
    margin: 0;
}

.close-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: #111f35;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 20px;
    color: #64748b;
}

.modal-body {
    padding: 20px 24px 24px;
}

.modal-status {
    display: inline-block;
    font-size: 12px;
    font-weight: 700;
    padding: 5px 14px;
    border-radius: 99px;
    margin-bottom: 20px;
}

.modal-section {
    margin-bottom: 20px;
}

.section-title {
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    text-transform: capitalize;
    letter-spacing: 0.5px;
    margin: 0 0 12px;
}

.modal-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: #0d1b2e;
    border-radius: var(--radius-md);
    margin-bottom: 8px;
}

.modal-item img {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-sm);
    object-fit: cover;
    border: 1px solid rgba(255,255,255,0.07);
}

.modal-item-info {
    flex: 1;
    min-width: 0;
}

.modal-item-name {
    font-size: 13px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0 0 2px;
}

.modal-item-variant {
    font-size: 11px;
    color: #64748b;
    margin: 0 0 4px;
}

.modal-item-qty {
    font-size: 12px;
    color: #94a3b8;
    margin: 0;
}

.modal-item-price {
    font-size: 14px;
    font-weight: 700;
    color: #2563eb;
}

.modal-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0 0;
    border-top: 1px solid rgba(255,255,255,0.07);
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
}

.total-val {
    font-size: 18px;
    font-weight: 800;
    color: #2563eb;
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.form-control {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.07);
    font-size: 14px;
}

.btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    cursor: pointer;
}

.btn-secondary {
    background: #94a3b8;
    color: #fff;
}

.btn-danger {
    background: #dc2626;
    color: #fff;
}

/* ===================== RESPONSIVE STYLES ===================== */
@media (max-width: 768px) {
  .page {
    padding: 20px 16px;
  }
  .tabs {
    flex-direction: column;
    align-items: stretch;
    gap: 4px;
    padding: 4px;
  }
  .tab {
    justify-content: center;
  }
  .order-head {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    padding: 14px 16px;
  }
  .order-meta {
    width: 100%;
    justify-content: space-between;
  }
  .order-items {
    padding: 10px 16px;
  }
  .order-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  .order-item img {
    width: 60px;
    height: 60px;
  }
  .order-item-price {
    align-self: flex-end;
    margin-top: -24px;
  }
  .order-foot {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    padding: 14px 16px;
  }
  .order-foot .d-flex {
    flex-direction: column;
    width: 100%;
  }
  .btn-detail, .btn-reorder, .btn-cancel {
    width: 100%;
    text-align: center;
  }
}

@media (max-width: 576px) {
  .modal {
    width: calc(100% - 24px);
    margin: 12px;
  }
  .modal-head {
    padding: 18px 18px 0;
  }
  .modal-body {
    padding: 16px 18px 18px;
  }
  .modal-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  .modal-item img {
    width: 60px;
    height: 60px;
  }
  .modal-item-price {
    align-self: flex-end;
    margin-top: -24px;
  }
  .modal-total {
    font-size: 13px;
  }
  .total-val {
    font-size: 16px;
  }
}
</style>

<style scoped>

.category-tabs { display: flex; gap: 12px; margin-bottom: -4px; border-bottom: 2px solid #e2e8f0; padding-bottom: 0; }
.cat-tab { background: transparent; border: none; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
.cat-tab:hover { color: #2563eb; }
.cat-tab.active { color: #2563eb; border-bottom-color: #2563eb; }
</style>
<style scoped>

.timeline { display: flex; align-items: flex-start; justify-content: space-between; padding: 20px 0 10px; }
.tl-item { display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; position: relative; }
.tl-col { display: flex; align-items: center; width: 100%; position: relative; justify-content: center; margin-bottom: 10px; }
.tl-dot { width: 28px; height: 28px; border-radius: 50%; background: #fff; border: 2.5px solid #cbd5e1; z-index: 2; display: flex; align-items: center; justify-content: center; }
.tl-dot svg { width: 16px; height: 16px; }
.tl-item.done .tl-dot { background: #2563eb; border-color: #2563eb; }
.tl-line { position: absolute; top: 12px; left: 50%; width: 100%; height: 3px; background: #e2e8f0; z-index: 1; }
.tl-line.done { background: #2563eb; }
.tl-content { padding: 0 10px; }
.tl-label { font-size: 13px; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
.tl-date { font-size: 11px; color: #94a3b8; margin: 0; }
.refund-timeline-wrap { background: transparent; padding: 0; border: none; border-radius: 0; }
.refund-dot { border-color: #fdba74; }
.refund-label { color: #c2410c; }

/* MODAL TEXTAREA */
.cancel-textarea {
  width: 100%;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1.5px solid #cbd5e1;
  background: #ffffff;
  color: #1e293b;
  font-size: 13.5px;
  outline: none;
  transition: all 0.2s ease;
  resize: none;
}
.cancel-textarea:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}
</style>
