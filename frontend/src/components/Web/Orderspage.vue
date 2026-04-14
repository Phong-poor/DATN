<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import api from '@/services/api'

const activeTab = ref('all')
const selectedOrder = ref(null)
const orders = ref([])
const isLoading = ref(true)

const tabs = [
    { key: 'all', label: 'Tất cả' },
    { key: 'pending', label: 'Chờ xác nhận' },
    { key: 'confirmed', label: 'Đã xác nhận' },
    { key: 'shipping', label: 'Đang giao' },
    { key: 'done', label: 'Hoàn thành' },
    { key: 'cancelled', label: 'Đã hủy' },
]

const statusMap = {
    pending: { label: 'Chờ xác nhận', color: '#f59e0b', bg: '#fef3c7' },
    confirmed: { label: 'Đã xác nhận', color: '#0ea5e9', bg: '#e0f2fe' },
    shipping: { label: 'Đang giao', color: '#2563eb', bg: '#dbeafe' },
    done: { label: 'Hoàn thành', color: '#16a34a', bg: '#dcfce7' },
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
        alert('Vui lòng nhập lý do hủy.')
        return
    }

    if (!confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) return

    isSubmitting.value = true
    try {
        const res = await api.post(`/orders/${orderToCancel.value.id_dathang}/cancel`, 
            { lydo: cancelReason.value }
        )

        if (res.data.success) {
            alert('Hủy đơn hàng thành công!')
            showCancelModal.value = false
            await fetchOrders()
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Có lỗi xảy ra khi hủy đơn.')
    } finally {
        isSubmitting.value = false
    }
}

const handleReorder = async (order) => {
    if (!confirm('Bạn có chắc chắn muốn mua lại các sản phẩm này?')) return

    try {
        const res = await api.post(`/orders/${order.id_dathang}/reorder`)

        if (res.data.success) {
            alert(res.data.message)
            // Redirect to cart
            window.location.href = '/cart'
        }
    } catch (err) {
        alert('Lỗi khi mua lại sản phẩm.')
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
        alert('Không thể tải danh sách đơn hàng.')
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

onMounted(fetchOrders)
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
                        <div v-if="selectedOrder.trangthai === 'cancelled' && selectedOrder.lydo" class="alert alert-danger py-2 px-3 mb-4" style="font-size: 13px;">
                            <strong>Lý do hủy:</strong> {{ selectedOrder.lydo }}
                        </div>

                        <!-- Products -->
                        <div class="modal-section">
                            <h3 class="section-title">Sản phẩm</h3>
                            <div class="modal-item" v-for="item in selectedOrder.chi_tiets" :key="item.id_dathang_chi_tiet">
                                <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=200" alt="product" />
                                <div class="modal-item-info">
                                    <p class="modal-item-name">{{ item.bien_the?.ten_bienthe || 'Sản phẩm' }}</p>
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
                            
                            <button v-if="['done', 'cancelled'].includes(selectedOrder.trangthai)" 
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

            <!-- Tabs -->
            <div class="tabs">
                <button v-for="tab in tabs" :key="tab.key" class="tab" :class="{ active: activeTab === tab.key }"
                    @click="activeTab = tab.key">
                    {{ tab.label }}
                    <span class="tab-count" v-if="tab.key !== 'all'">
                        {{orders.filter(o => o.trangthai === tab.key).length}}
                    </span>
                </button>
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
                        <div class="order-item" v-for="item in order.chi_tiets" :key="item.id_dathang_chi_tiet">
                            <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=200" alt="product" />
                            <div class="order-item-info">
                                <p class="order-item-name">{{ item.bien_the?.ten_bienthe || 'Sản phẩm' }}</p>
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
                            
                            <button v-if="['done', 'cancelled'].includes(order.trangthai)" 
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
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 3px;
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
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 3px;
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
</style>