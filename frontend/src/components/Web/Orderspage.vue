<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import api from '@/services/api'
import echo from '@/services/echo'
import { getToken, getUser } from '@/services/auth'
import { onUnmounted } from 'vue'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'

const activeTab = ref('all')
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
const refundProof = ref(null)
const refundProofUrl = ref(null)

const handleProofUpload = (e) => {
    const file = e.target.files[0]
    if (file) {
        refundProof.value = file
        if (refundProofUrl.value) URL.revokeObjectURL(refundProofUrl.value)
        refundProofUrl.value = URL.createObjectURL(file)
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
    showRefundModal.value = true
}

const confirmRefund = async () => {
    if (!refundReason.value.trim()) {
        swal.warning('Thông báo', 'Vui lòng nhập lý do hoàn trả.')
        return
    }
    if (!refundProof.value) {
        swal.warning('Thông báo', 'Vui lòng tải lên ảnh/video bằng chứng.')
        return
    }

    isSubmitting.value = true
    try {
        const formData = new FormData()
        formData.append('lydo', refundReason.value)
        formData.append('proof', refundProof.value)

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
    if (activeTab.value === 'all') return orders.value
    return orders.value.filter(o => o.trangthai === activeTab.value)
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
    return sp.hinhanh ? storageUrl(sp.hinhanh) : 'https://via.placeholder.com/200'
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
                <div class="modal mini-modal">
                    <div class="modal-head">
                        <h2 class="modal-title">Yêu cầu hoàn trả</h2>
                        <button class="close-btn" @click="showRefundModal = false">×</button>
                    </div>
                    <div class="modal-body">
                        <textarea v-model="refundReason" class="form-control mb-3" rows="3" placeholder="Nhập lý do hoàn trả..."></textarea>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 13px; font-weight: 600;">Hình ảnh / Video bằng chứng</label>
                            <input type="file" @change="handleProofUpload" class="form-control" accept="image/*,video/*" />
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">Hỗ trợ ảnh hoặc video (tối đa 20MB)</small>
                            
                            <div v-if="refundProofUrl" class="mt-3" style="text-align: center;">
                                <img v-if="refundProof && refundProof.type.startsWith('image/')" :src="refundProofUrl" alt="Bằng chứng" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" />
                                <video v-else-if="refundProof && refundProof.type.startsWith('video/')" :src="refundProofUrl" controls style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></video>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-3">
                            <button class="btn btn-secondary" @click="showRefundModal = false">Đóng</button>
                            <button class="btn btn-warning" style="color: white; font-weight: bold;" @click="confirmRefund" :disabled="isSubmitting">
                                {{ isSubmitting ? 'Đang gửi...' : 'Gửi yêu cầu' }}
                            </button>
                        </div>
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
                                <strong>Bằng chứng:</strong> <a :href="storageUrl(selectedOrder.refund_proof)" target="_blank">Xem file đính kèm</a>
                            </div>
                        </div>

                        <!-- Products -->
                        <div class="modal-section">
                            <h3 class="section-title">Sản phẩm</h3>
                            <div class="modal-item" v-for="item in (selectedOrder.chi_tiets || [])" :key="item.id_dathang_chi_tiet">
                                <img :src="getProductImage(item)" alt="product" />
                                <div class="modal-item-info">
                                    <p class="modal-item-name">{{ getFullProductName(item) }}</p>
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
                            
                            <button v-if="isRefundable(selectedOrder)" 
                                class="btn-refund w-100" @click="openRefundModal(selectedOrder)">Hoàn trả</button>

                            <button v-if="['done', 'cancelled', 'refunded'].includes(selectedOrder.trangthai)" 
                                class="btn-reorder w-100" @click="handleReorder(selectedOrder)">Mua lại</button>
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
                <div class="tabs-label" style="font-weight: 600; color: #1e293b; min-width: 70px; font-size: 14px;">Mua:</div>
                <div class="tabs" style="margin-bottom: 0;">
                    <button v-for="tab in tabs_mua" :key="tab.key" class="tab" :class="{ active: activeTab === tab.key }"
                        @click="activeTab = tab.key">
                        {{ tab.label }}
                        <span class="tab-count" v-if="tab.key !== 'all'">
                            {{orders.filter(o => o.trangthai === tab.key).length}}
                        </span>
                    </button>
                </div>
              </div>
              <div class="tabs-row" style="display: flex; align-items: center; gap: 10px;">
                <div class="tabs-label" style="font-weight: 600; color: #f97316; min-width: 70px; font-size: 14px;">Hoàn trả:</div>
                <div class="tabs" style="margin-bottom: 0;">
                    <button v-for="tab in tabs_hoantra" :key="tab.key" class="tab" :class="{ active: activeTab === tab.key }"
                        @click="activeTab = tab.key">
                        {{ tab.label }}
                        <span class="tab-count">
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
                                <p class="order-item-name">{{ getFullProductName(item) }}</p>
                                <p class="order-item-variant">{{ item.bien_the?.ten_bienthe }}</p>
                                <p class="order-item-qty">x{{ item.soluong }}</p>
                            </div>
                            <p class="order-item-price">{{ formatPrice(item.gia) }}</p>
                        </div>
                    </div>

                    <div class="order-foot">
                        <span class="order-total">Tổng: <strong>{{ formatPrice(order.tongtien) }}</strong></span>
                        <div class="d-flex gap-2">
                            <button v-if="['pending', 'confirmed'].includes(order.trangthai)" 
                                class="btn-cancel" @click="openCancelModal(order)">Hủy đơn</button>
                            
                            <button v-if="isRefundable(order)" 
                                class="btn-refund" @click="openRefundModal(order)">Hoàn trả</button>

                            <button v-if="['done', 'cancelled', 'refunded'].includes(order.trangthai)" 
                                class="btn-reorder" @click="handleReorder(order)">Mua lại</button>

                            <button class="btn-detail" @click="openDetail(order)">Chi tiết</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page {
    min-height: 100vh;
    background: #f8fafc;
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
    color: #0f172a;
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
    background: #fff;
    border: 1px solid #e5e7eb;
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
    background: #f1f5f9;
    color: #374151;
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
    background: #f1f5f9;
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
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.order-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.order-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.order-id {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
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
    border: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.order-item-info {
    flex: 1;
    min-width: 0;
}

.order-item-name {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
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
    border-top: 1px solid #f1f5f9;
    background: #f8fafc;
}

.order-total {
    font-size: 13px;
    color: #64748b;
}

.order-total strong {
    color: #0f172a;
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
    background: #fff;
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
    background: #fff;
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
    background: #fff;
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
    color: #0f172a;
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
    background: #f1f5f9;
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
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 8px;
}

.modal-item img {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid #e5e7eb;
}

.modal-item-info {
    flex: 1;
    min-width: 0;
}

.modal-item-name {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
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
    border-top: 1px solid #f1f5f9;
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
    border: 1px solid #e2e8f0;
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
