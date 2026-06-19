<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import api from '@/services/api'
import echo from '@/services/echo'
import { getToken, getUser } from '@/services/auth'
import { onUnmounted } from 'vue'
import swal from '@/services/swal'
import { normalizeImageUrl, productImageUrl, storageUrl } from '@/services/urls'

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
    done: { label: 'Hoàn thành', color: '#16a34a', bg: '#dcfce7' },
    refund_pending: { label: 'Yêu cầu hoàn trả', color: '#f97316', bg: '#ffedd5' },
    refund_pickup: { label: 'Chờ lấy hàng hoàn', color: '#d97706', bg: '#fef3c7' },
    refund_delivering: { label: 'Đang giao hoàn', color: '#2563eb', bg: '#dbeafe' },
    refund_received: { label: 'Đã nhận hoàn', color: '#0369a1', bg: '#e0f2fe' },
    refunded: { label: 'Đã hoàn tiền', color: '#8b5cf6', bg: '#ede9fe' },
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

// Refund state
const showRefundModal = ref(false)
const orderToRefund = ref(null)
const refundReason = ref('')
const refundProofs = ref([]) // array of { file, url, type }
const refundSelectedItems = ref([])

const handleProofUpload = (e) => {
    const files = Array.from(e.target.files)
    files.forEach(file => {
        if (refundProofs.value.length >= 5) return // giới hạn 5 file
        refundProofs.value.push({
            file,
            url: URL.createObjectURL(file),
            type: file.type
        })
    })
    e.target.value = '' // reset input để có thể chọn lại cùng file
}

const removeProof = (index) => {
    URL.revokeObjectURL(refundProofs.value[index].url)
    refundProofs.value.splice(index, 1)
}

const openRefundModal = (order) => {
    orderToRefund.value = order
    refundReason.value = ''
    refundProofs.value.forEach(p => URL.revokeObjectURL(p.url))
    refundProofs.value = []
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
    if (refundProofs.value.length === 0) {
        swal.warning('Thông báo', 'Vui lòng tải lên ít nhất một ảnh/video bằng chứng.')
        return
    }

    isSubmitting.value = true
    try {
        const formData = new FormData()
        formData.append('lydo', refundReason.value)
        refundProofs.value.forEach(p => {
            formData.append('proofs[]', p.file)
        })
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
            window.location.href = '/cart'
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

const openDetail = (order) => { selectedOrder.value = order }
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
    return productImageUrl(sp, variant, 'https://via.placeholder.com/200')
}

const parseRefundProofs = (refundProof) => {
    if (!refundProof) return []
    try {
        const parsed = JSON.parse(refundProof)
        return Array.isArray(parsed) ? parsed : [refundProof]
    } catch {
        return [refundProof]
    }
}

onMounted(() => {
    fetchOrders()
    
    const user = getUser()

    if (getToken() && user && (user.id || user.id_user)) {
        const userId = user.id || user.id_user
        
        echo.private(`user.${userId}`)
            .listen('.order.status.updated', (e) => {
                // Cập nhật mảng orders
                const index = orders.value.findIndex(o => o.id_dathang === e.id_dathang)
                if (index !== -1) {
                    orders.value[index].trangthai = e.trangthai
                    // Cập nhật modal chi tiết nếu đang mở đúng đơn đó
                    if (selectedOrder.value && selectedOrder.value.id_dathang === e.id_dathang) {
                        selectedOrder.value.trangthai = e.trangthai
                    }
                }
            })
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
        <transition name="fade">
            <div class="overlay" v-if="showCancelModal" @click.self="showCancelModal = false">
                <div class="modal mini-modal">
                    <div class="modal-head">
                        <h2 class="modal-title">Lý do hủy đơn</h2>
                        <button class="close-btn" @click="showCancelModal = false">×</button>
                    </div>
                    <div class="modal-body">
                        <textarea v-model="cancelReason" class="form-control mb-3" rows="3" placeholder="Nhập lý do hủy đơn hàng..."></textarea>
                        <div class="d-flex gap-2 justify-content-end mt-3">
                            <button class="btn btn-secondary" @click="showCancelModal = false">Đóng</button>
                            <button class="btn btn-danger" @click="confirmCancel" :disabled="isSubmitting">
                                {{ isSubmitting ? 'Đang xử lý...' : 'Xác nhận hủy' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Refund Modal -->
        <transition name="fade">
            <div class="overlay" v-if="showRefundModal" @click.self="showRefundModal = false">
                <div style="background:#1e2d45;border-radius:20px;width:520px;max-width:95vw;max-height:90vh;display:flex;flex-direction:column;border:1px solid rgba(249,115,22,0.3);box-shadow:0 25px 60px rgba(0,0,0,0.6);overflow:hidden;">

                    <!-- Header -->
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(255,255,255,0.08);background:linear-gradient(135deg,rgba(249,115,22,0.07),transparent 60%);">
                        <div style="display:flex;align-items:center;gap:14px;">
                            <div style="width:44px;height:44px;border-radius:12px;background:rgba(249,115,22,0.15);border:1px solid rgba(249,115,22,0.3);display:flex;align-items:center;justify-content:center;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                            </div>
                            <div>
                                <div style="font-size:17px;font-weight:700;color:#f1f5f9;">Yêu cầu hoàn trả</div>
                                <div style="font-size:12px;color:#94a3b8;margin-top:2px;">Điền đầy đủ thông tin để gửi yêu cầu</div>
                            </div>
                        </div>
                        <button @click="showRefundModal = false" style="width:34px;height:34px;border-radius:50%;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.06);cursor:pointer;display:flex;align-items:center;justify-content:center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div style="padding:20px 24px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:20px;">

                        <!-- 1. Chon san pham -->
                        <div>
                            <div style="display:flex;align-items:center;gap:7px;margin-bottom:10px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                                <span style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.6px;">Chọn sản phẩm hoàn trả</span>
                                <span style="color:#f97316;font-size:13px;">*</span>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:8px;max-height:200px;overflow-y:auto;">
                                <label
                                    v-for="item in (orderToRefund?.chi_tiets || orderToRefund?.chiTiets || [])"
                                    :key="item.id_bienthe"
                                    :for="'refund_item_' + item.id_bienthe"
                                    :style="{
                                        display:'flex', alignItems:'center', gap:'12px',
                                        padding:'12px 14px', borderRadius:'12px',
                                        background: refundSelectedItems.includes(item.id_bienthe) ? 'rgba(249,115,22,0.12)' : 'rgba(255,255,255,0.04)',
                                        border: refundSelectedItems.includes(item.id_bienthe) ? '1.5px solid rgba(249,115,22,0.5)' : '1.5px solid rgba(255,255,255,0.08)',
                                        cursor:'pointer', transition:'all 0.18s'
                                    }"
                                >
                                    <input type="checkbox" :id="'refund_item_' + item.id_bienthe" :value="item.id_bienthe" v-model="refundSelectedItems" style="display:none;">
                                    <div :style="{
                                        width:'20px', height:'20px', borderRadius:'6px', flexShrink:'0',
                                        background: refundSelectedItems.includes(item.id_bienthe) ? '#f97316' : 'rgba(255,255,255,0.06)',
                                        border: refundSelectedItems.includes(item.id_bienthe) ? '2px solid #f97316' : '2px solid rgba(255,255,255,0.2)',
                                        display:'flex', alignItems:'center', justifyContent:'center'
                                    }">
                                        <svg v-if="refundSelectedItems.includes(item.id_bienthe)" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>
                                    <img :src="getProductImage(item)" style="width:46px;height:46px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,0.1);flex-shrink:0;">
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:13px;font-weight:600;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:3px;">{{ getFullProductName(item) }}</div>
                                        <div style="font-size:11px;color:#64748b;">{{ item.bien_the?.ten_bienthe || 'Mặc định' }} · SL: {{ item.soluong }}</div>
                                    </div>
                                    <div v-if="refundSelectedItems.includes(item.id_bienthe)" style="width:22px;height:22px;border-radius:50%;background:rgba(249,115,22,0.2);border:1.5px solid rgba(249,115,22,0.5);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 2. Ly do -->
                        <div>
                            <div style="display:flex;align-items:center;gap:7px;margin-bottom:10px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <span style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.6px;">Lý do hoàn trả</span>
                                <span style="color:#f97316;font-size:13px;">*</span>
                            </div>
                            <textarea
                                v-model="refundReason"
                                rows="3"
                                placeholder="Mô tả chi tiết lý do bạn muốn hoàn trả sản phẩm..."
                                style="width:100%;background:rgba(255,255,255,0.05);border:1.5px solid rgba(255,255,255,0.1);border-radius:12px;padding:12px 14px;font-size:13px;color:#e2e8f0;resize:vertical;outline:none;font-family:inherit;line-height:1.6;box-sizing:border-box;"
                            ></textarea>
                        </div>

                        <!-- 3. Upload -->
                        <div>
                            <div style="display:flex;align-items:center;gap:7px;margin-bottom:10px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                <span style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.6px;">Hình ảnh / Video bằng chứng</span>
                                <span style="color:#f97316;font-size:13px;">*</span>
                                <span style="margin-left:auto;font-size:11px;font-weight:700;color:#f97316;background:rgba(249,115,22,0.12);border:1px solid rgba(249,115,22,0.25);padding:2px 9px;border-radius:99px;">{{ refundProofs.length }}/5</span>
                            </div>

                            <!-- Drop zone khi chua co anh -->
                            <label v-if="refundProofs.length === 0" for="refund-proof-input" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;border:2px dashed rgba(255,255,255,0.12);border-radius:14px;padding:32px 20px;cursor:pointer;background:rgba(255,255,255,0.03);">
                                <div style="width:56px;height:56px;border-radius:16px;background:rgba(249,115,22,0.08);border:1px solid rgba(249,115,22,0.2);display:flex;align-items:center;justify-content:center;">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                </div>
                                <div style="font-size:14px;font-weight:600;color:#cbd5e1;">Nhấn để tải ảnh / video</div>
                                <div style="font-size:12px;color:#64748b;">PNG, JPG, MP4 · Tối đa 5 file · 20MB/file</div>
                            </label>

                            <!-- Grid preview -->
                            <div v-if="refundProofs.length > 0" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                                <div v-for="(proof, idx) in refundProofs" :key="idx" style="position:relative;border-radius:10px;overflow:hidden;aspect-ratio:1;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);">
                                    <img v-if="proof.type.startsWith('image/')" :src="proof.url" style="width:100%;height:100%;object-fit:cover;display:block;"/>
                                    <video v-else-if="proof.type.startsWith('video/')" :src="proof.url" style="width:100%;height:100%;object-fit:cover;display:block;"></video>
                                    <div v-if="proof.type.startsWith('video/')" style="position:absolute;bottom:4px;left:4px;font-size:9px;font-weight:700;color:#fff;background:rgba(0,0,0,0.65);padding:1px 5px;border-radius:4px;">VIDEO</div>
                                    <button @click="removeProof(idx)" type="button" style="position:absolute;top:4px;right:4px;width:22px;height:22px;border-radius:50%;background:rgba(220,38,38,0.85);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <label v-if="refundProofs.length < 5" for="refund-proof-input" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;border-radius:10px;border:2px dashed rgba(255,255,255,0.12);background:rgba(255,255,255,0.03);cursor:pointer;aspect-ratio:1;color:#64748b;font-size:11px;font-weight:600;">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    <span>Thêm</span>
                                </label>
                            </div>

                            <input id="refund-proof-input" type="file" @change="handleProofUpload" accept="image/*,video/*" multiple style="display:none;"/>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px 20px;border-top:1px solid rgba(255,255,255,0.07);gap:10px;">
                        <button @click="showRefundModal = false" style="display:flex;align-items:center;gap:7px;padding:10px 20px;border-radius:10px;border:1.5px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.04);color:#cbd5e1;font-size:13px;font-weight:600;cursor:pointer;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                            Quay lại
                        </button>
                        <button @click="confirmRefund" :disabled="isSubmitting" style="display:flex;align-items:center;gap:8px;padding:10px 26px;border-radius:10px;border:none;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 16px rgba(249,115,22,0.35);">
                            <template v-if="!isSubmitting">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                Gửi yêu cầu
                            </template>
                            <template v-else>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 0.8s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                Đang gửi...
                            </template>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

                <!-- Detail Modal -->
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

                        <!-- Cancellation info if cancelled -->
                        <div v-if="(selectedOrder.trangthai === 'cancelled' || selectedOrder.trangthai.startsWith('refund')) && selectedOrder.lydo" class="alert py-2 px-3 mb-4" :class="{'alert-danger': selectedOrder.trangthai === 'cancelled', 'alert-warning': selectedOrder.trangthai !== 'cancelled'}" style="font-size: 13px;">
                            <strong>Lý do:</strong> {{ selectedOrder.lydo }}
                            <div v-if="selectedOrder.refund_proof" class="mt-2">
                                <strong>Bằng chứng:</strong>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                                    <a
                                        v-for="(proof, pi) in parseRefundProofs(selectedOrder.refund_proof)"
                                        :key="pi"
                                        :href="storageUrl(proof)"
                                        target="_blank"
                                        style="display: inline-block;"
                                    >
                                        <img
                                            v-if="proof.match(/\.(jpeg|jpg|png|gif|webp)$/i)"
                                            :src="storageUrl(proof)"
                                            style="width: 52px; height: 52px; object-fit: cover; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); cursor: zoom-in;"
                                        />
                                        <span
                                            v-else
                                            style="display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #60a5fa; text-decoration: underline;"
                                        >File {{ pi + 1 }}</span>
                                    </a>
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
                      <p class="tl-date">{{ step.date || '—' }}</p>
                    </div>
                  </div>
                </div>
              </div>

<!-- Products -->
                        <div class="modal-section">
                            <h3 class="section-title">Sản phẩm</h3>
                            <div class="modal-item" v-for="item in (selectedOrder.chi_tiets || []).filter(i => !selectedOrder.trangthai?.startsWith('refund') || i.is_refund == 1)" :key="item.id_dathang_chi_tiet">
                                <img :src="getProductImage(item)" alt="product" />
                                <div class="modal-item-info">
                                    <p class="modal-item-name">
                                        {{ getFullProductName(item) }}
                                        <span v-if="item.is_refund == 1" style="margin-left: 6px; font-size: 10px; font-weight: bold; color: #dc2626; background: #fee2e2; padding: 2px 5px; border-radius: 4px;">Đã hoàn trả</span>
                                    </p>
                                    <p class="modal-item-variant">{{ item.bien_the?.ten_bienthe }}</p>
                                    <p class="modal-item-qty">Số lượng: {{ item.soluong }}</p>
                                </div>
                                <p class="modal-item-price">{{ formatPrice(item.gia) }}</p>
                            </div>
                        </div>

                        <div class="modal-total">
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

                    

                    <div class="order-foot">
                        <span class="order-total">Tổng: <strong>{{ formatPrice(order.tongtien) }}</strong></span>
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
    padding: 32px 30px;
}

.container {
    max-width: 860px;
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
    gap: 6px;
    background: #111f35;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    padding: 6px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.tab {
    padding: 8px 16px;
    border-radius: 10px;
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
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.07);
    overflow: hidden;
}

.order-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
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
    border-radius: 10px;
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
    border-radius: 9px;
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
    border-radius: 20px;
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
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 12px;
}

.modal-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: #0d1b2e;
    border-radius: 12px;
    margin-bottom: 8px;
}

.modal-item img {
    width: 52px;
    height: 52px;
    border-radius: 10px;
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
.cat-tab:hover { color: #4f46e5; }
.cat-tab.active { color: #4f46e5; border-bottom-color: #4f46e5; }
</style>
<style scoped>

.timeline { display: flex; align-items: flex-start; justify-content: space-between; padding: 20px 0 10px; }
.tl-item { display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1; position: relative; }
.tl-col { display: flex; align-items: center; width: 100%; position: relative; justify-content: center; margin-bottom: 10px; }
.tl-dot { width: 28px; height: 28px; border-radius: 50%; background: #fff; border: 2.5px solid #cbd5e1; z-index: 2; display: flex; align-items: center; justify-content: center; }
.tl-dot svg { width: 16px; height: 16px; }
.tl-item.done .tl-dot { background: #10b981; border-color: #10b981; }
.tl-line { position: absolute; top: 12px; left: 50%; width: 100%; height: 3px; background: #e2e8f0; z-index: 1; }
.tl-line.done { background: #10b981; }
.tl-content { padding: 0 10px; }
.tl-label { font-size: 13px; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
.tl-date { font-size: 11px; color: #94a3b8; margin: 0; }
.refund-timeline-wrap { background: #fff7ed; padding: 16px; border-radius: 12px; border: 1px dashed #fdba74; }
.refund-dot { border-color: #fdba74; }
.refund-label { color: #c2410c; }
</style>

<style scoped>
/* ====== REFUND MODAL ====== */
.refund-modal {
    background: #0f1e32;
    border-radius: 20px;
    width: 520px;
    max-width: 95vw;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(249, 115, 22, 0.2);
    box-shadow: 0 24px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.04);
    overflow: hidden;
    animation: refundSlideIn 0.28s cubic-bezier(0.16,1,0.3,1);
}

@keyframes refundSlideIn {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.refund-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    flex-shrink: 0;
}

.refund-modal-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.refund-icon-wrap {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(249,115,22,0.12);
    border: 1px solid rgba(249,115,22,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.refund-modal-title {
    font-size: 17px;
    font-weight: 700;
    color: #f1f5f9;
    margin: 0 0 2px;
}

.refund-modal-sub {
    font-size: 12px;
    color: #64748b;
    margin: 0;
}

.refund-close-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.04);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    transition: all 0.15s;
    flex-shrink: 0;
}
.refund-close-btn:hover {
    background: rgba(255,255,255,0.09);
    color: #cbd5e1;
}

.refund-modal-body {
    padding: 20px 24px;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* Scrollbar */
.refund-modal-body::-webkit-scrollbar { width: 5px; }
.refund-modal-body::-webkit-scrollbar-track { background: transparent; }
.refund-modal-body::-webkit-scrollbar-thumb { background: #1e3a5f; border-radius: 99px; }

.refund-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.refund-section-label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.refund-required {
    color: #f97316;
    font-size: 13px;
    margin-left: 2px;
}

.refund-count-badge {
    margin-left: auto;
    font-size: 11px;
    font-weight: 600;
    color: #f97316;
    background: rgba(249,115,22,0.1);
    border: 1px solid rgba(249,115,22,0.2);
    padding: 2px 8px;
    border-radius: 99px;
    letter-spacing: 0;
    text-transform: none;
}

/* Product List */
.refund-product-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 190px;
    overflow-y: auto;
    padding-right: 2px;
}
.refund-product-list::-webkit-scrollbar { width: 4px; }
.refund-product-list::-webkit-scrollbar-track { background: transparent; }
.refund-product-list::-webkit-scrollbar-thumb { background: #1e3a5f; border-radius: 99px; }

.refund-product-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 12px;
    background: #111f35;
    border: 1.5px solid rgba(255,255,255,0.05);
    cursor: pointer;
    transition: all 0.18s;
    position: relative;
}
.refund-product-item:hover {
    border-color: rgba(249,115,22,0.25);
    background: #142037;
}
.refund-product-item.selected {
    border-color: rgba(249,115,22,0.5);
    background: rgba(249,115,22,0.06);
}

.refund-checkbox {
    display: none;
}

.refund-product-img {
    width: 44px;
    height: 44px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.07);
    flex-shrink: 0;
}

.refund-product-info {
    flex: 1;
    min-width: 0;
}

.refund-product-name {
    font-size: 13px;
    font-weight: 600;
    color: #e2e8f0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 3px;
}

.refund-product-meta {
    font-size: 11px;
    color: #64748b;
}

.refund-check-icon {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(249,115,22,0.12);
    border: 1.5px solid rgba(249,115,22,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* Textarea */
.refund-textarea {
    width: 100%;
    background: #111f35;
    border: 1.5px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    padding: 12px 14px;
    font-size: 13px;
    color: #e2e8f0;
    resize: vertical;
    outline: none;
    transition: border-color 0.18s;
    font-family: inherit;
    line-height: 1.6;
}
.refund-textarea::placeholder { color: #475569; }
.refund-textarea:focus { border-color: rgba(249,115,22,0.4); }

/* Upload zone */
.refund-upload-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 2px dashed rgba(255,255,255,0.1);
    border-radius: 14px;
    padding: 28px 20px;
    cursor: pointer;
    transition: all 0.18s;
    background: #111f35;
}
.refund-upload-zone:hover {
    border-color: rgba(249,115,22,0.35);
    background: rgba(249,115,22,0.04);
}
.refund-upload-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,0.04);
    display: flex;
    align-items: center;
    justify-content: center;
}
.refund-upload-text {
    font-size: 13px;
    font-weight: 600;
    color: #94a3b8;
}
.refund-upload-hint {
    font-size: 11px;
    color: #475569;
}

/* Proof grid */
.refund-proof-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.refund-proof-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    aspect-ratio: 1;
    background: #111f35;
    border: 1px solid rgba(255,255,255,0.07);
}

.refund-proof-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.refund-proof-type-badge {
    position: absolute;
    bottom: 4px;
    left: 4px;
    font-size: 9px;
    font-weight: 700;
    color: #fff;
    background: rgba(0,0,0,0.6);
    padding: 1px 5px;
    border-radius: 4px;
    letter-spacing: 0.5px;
}

.refund-proof-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: rgba(0,0,0,0.65);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    transition: background 0.15s;
}
.refund-proof-remove:hover { background: #dc2626; }

.refund-add-more {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border-radius: 10px;
    border: 2px dashed rgba(255,255,255,0.1);
    background: #111f35;
    cursor: pointer;
    aspect-ratio: 1;
    transition: all 0.15s;
    font-size: 11px;
    color: #64748b;
}
.refund-add-more:hover {
    border-color: rgba(249,115,22,0.35);
    color: #f97316;
}
.refund-add-more span { font-size: 11px; font-weight: 600; }

/* Footer */
.refund-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px 20px;
    border-top: 1px solid rgba(255,255,255,0.06);
    flex-shrink: 0;
    gap: 10px;
}

.refund-btn-back {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 10px 20px;
    border-radius: 10px;
    border: 1.5px solid rgba(255,255,255,0.1);
    background: transparent;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}
.refund-btn-back:hover {
    background: rgba(255,255,255,0.05);
    color: #cbd5e1;
    border-color: rgba(255,255,255,0.15);
}

.refund-btn-submit {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s;
    box-shadow: 0 4px 14px rgba(249,115,22,0.3);
}
.refund-btn-submit:hover:not(:disabled) {
    background: linear-gradient(135deg, #fb923c, #f97316);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(249,115,22,0.4);
}
.refund-btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.refund-btn-submit span {
    display: flex;
    align-items: center;
    gap: 7px;
}

.refund-loading {
    display: flex;
    align-items: center;
    gap: 8px;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.spin { animation: spin 0.8s linear infinite; }
</style>
