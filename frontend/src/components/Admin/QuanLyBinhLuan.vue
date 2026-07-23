<template>
    <div class="page">
        <div class="container">

            <!-- -- Top Header -- -->
            <div class="top-header">
                <div class="header-left">
                    <h1 class="page-title">Quản lý Bình luận<br />&amp; Đánh giá</h1>
                    <p class="page-sub">Theo dõi và phản hồi các đánh giá từ khách hàng của NextGen</p>
                </div>
            </div>

            <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-label">TỔNG CỘNG</div>
                        <div class="stat-value">{{ statsData.total }}</div>
                    </div>
                    <div class="stat-card highlight">
                        <div class="stat-label">CHỜ DUYỆT</div>
                        <div class="stat-value">{{ statsData.pending }}</div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-label">ĐÁNH GIÁ TB</div>
                        <div class="stat-value">{{ statsData.avg }} <span class="star">★</span></div>
                    </div>
            </div>

            <!-- -- Table Card -- -->
            <div class="table-card">
                <div class="table-header">
                    <h3 class="table-title">Danh sách đánh giá gần đây</h3>
                    <div class="filter-tabs">
                        <button v-for="tab in tabs" :key="tab.key" class="tab-btn"
                            :class="{ active: activeTab === tab.key }" @click="activeTab = tab.key">{{ tab.label
                            }}</button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>KHÁCH HÀNG</th>
                                <th>SẢN PHẨM</th>
                                <th>ĐÁNH GIÁ</th>
                                <th>NỘI DUNG</th>
                                <th>NGÀY</th>
                                <th>TRẠNG THÁI</th>
                                <th>THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="review in filteredReviews" :key="review.id_danhgia" class="review-row">

                                <!-- Customer -->
                                <td class="td-customer">
                                    <div class="customer-info">
                                        <div class="avatar" style="background: #4f8ef7">
                                            <span>{{ review.user?.name?.charAt(0) }}</span>
                                        </div>
                                        <div>
                                            <div class="customer-name">{{ review.user?.name || 'Ẩn danh' }}</div>
                                            <div class="customer-email">{{ review.user?.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Product -->
                                                                <td><span class="product-link">{{ review.bien_the?.san_pham?.tenSP || 'Sản phẩm' }}</span></td>

                                <!-- Stars -->
                                <td>
                                    <div class="stars">
                                        <span v-for="s in 5" :key="s" class="star-icon"
                                            :class="s <= review.danhgia ? 'filled' : 'empty'">★</span>
                                    </div>
                                </td>

                                <!-- Content -->
                                <td>
                                    <p class="review-text">
                                        {{ review.binhluan || '(Không có nội dung)' }}
                                    </p>
                                </td>

                                <!-- Date -->
                                <td><span class="date-text">{{ new Date(review.created_at).toLocaleDateString() }}</span></td>

                                <!-- Status -->
                                <td>
                                    <span class="status-badge" :class="statusClass(review.trangthai)">
                                        {{ statusLabel(review.trangthai) }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td>
                                    <div class="action-btns">
                                        <button v-if="isPendingLike(review)" class="action-btn approve"
                                            @click="approveReview(review)">DUYỆT<br />NGAY</button>
                                        
                                        <button v-if="review.trangthai !== 'spam'" class="action-btn icon-btn" style="background:#fff7ed; color:#f97316" title="Đánh dấu Spam" @click="markAsSpam(review)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                            </svg>
                                        </button>

                                        <button v-if="review.trangthai === 'spam'" class="action-btn icon-btn" style="background:#ecfdf5; color:#2563eb" title="Đưa về chờ duyệt thủ công" @click="undoReview(review)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 10h10a5 5 0 0 1 5 5v2"/><polyline points="7 6 3 10 7 14"/>
                                            </svg>
                                        </button>

                                        <button class="action-btn icon-btn delete" title="Xoá"
                                            @click="deleteReview(review.id_danhgia)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14H6L5 6" /><path d="M10 11v6" /><path d="M14 11v6" /><path d="M9 6V4h6v2" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <PhanTrangAdmin
                    v-model:currentPage="currentPage"
                    :total-pages="pagination.last_page"
                    :total-items="pagination.total"
                    :page-size="pagination.per_page || 10"
                    item-label="đánh giá"
                />
            </div>

            <!-- -- Bottom Banners -- -->
            <div class="bottom-row">
                <div class="banner-card ai-ready">
                    <div class="banner-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M9 12l2 2 4-4" />
                            <path d="M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z" />
                        </svg>
                    </div>
                    <h4>Tất cả đã sẵn sàng!</h4>
                    <p>Hiện không có bình luận nào cần kiểm duyệt gấp. Hệ thống AI đang tự động lọc các nội dung spam thô tục.</p>
                </div>

                <div class="banner-card moderation-tool">
                    <div class="tool-card-head">
                        <div>
                            <div class="banner-badge">TOOL</div>
                            <h4>Công cụ<br />Duyệt bình luận</h4>
                        </div>
                        <button
                            class="tool-switch"
                            :class="{ active: toolIsOn }"
                            :disabled="toolStatusLoading"
                            type="button"
                            @click="toggleTool"
                        >
                            <span class="tool-dot"></span>
                            <strong v-if="toolStatusLoading">Đang cập nhật</strong>
                            <strong v-else-if="toolIsOn">Đang bật</strong>
                            <strong v-else>Đang tắt</strong>
                        </button>
                    </div>
                    <p>
                        {{ toolIsOn
                            ? 'Tool đang kiểm soát đánh giá khách hàng. Đánh giá tốt sẽ được tự duyệt, nội dung tục tĩu/spam/công kích sẽ bị ẩn.'
                            : 'Tool đang tắt. Đánh giá mới sẽ chờ admin kiểm duyệt thủ công khi bạn không có nhu cầu chạy kiểm soát tự động.' }}
                    </p>
                    <div class="tool-summary">
                        <span>Chờ duyệt toàn hệ thống</span>
                        <strong>{{ statsData.pending }}</strong>
                    </div>
                    <div class="tool-actions">
                        <button
                            class="banner-btn warning"
                            :class="{ active: toolIsOn }"
                            :disabled="toolStatusLoading"
                            @click="toggleTool"
                        >
                            {{ toolActionText }}
                        </button>
                        <button class="banner-btn primary" @click="openPendingQueue">
                            XEM CHỜ DUYỆT
                        </button>
                        <button class="banner-btn success" :disabled="pendingOnPage.length === 0 || bulkLoading" @click="approvePendingOnPage">
                            DUYỆT TRANG NÀY ({{ pendingOnPage.length }})
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Toast Hoàn Tác -->
        <transition name="slide-down">
            <div v-if="toast.show" class="toast-undo">
                <span class="toast-message">{{ toast.message }}</span>
                <button class="toast-btn-undo" @click="triggerUndo">HOÀN TÁC</button>
            </div>
        </transition>

    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import api from '../../services/api'
import swal from '@/services/swal'
import PhanTrangAdmin from './PhanTrangAdmin.vue'

const activeTab = ref('all')
const currentPage = ref(1)
const isLoading = ref(false)
const bulkLoading = ref(false)
const autoModerationLoading = ref(false)
const toolStatusLoading = ref(false)
const toolEnabled = ref(false)
let autoRefreshTimer = null

const toolIsOn = computed(() => toolEnabled.value === true)
const toolActionText = computed(() => {
    if (toolStatusLoading.value) return 'ĐANG CẬP NHẬT...'
    return toolIsOn.value ? 'TẮT TOOL' : 'BẬT TOOL'
})

const tabs = [
    { key: 'all', label: 'Tất cả' },
    { key: 'pending', label: 'Chờ duyệt' },
    { key: 'approved', label: 'Đã duyệt' },
]

const reviews = ref([])
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0
})
const statsData = ref({
    total: 0,
    pending: 0,
    avg: 0
})

const toast = ref({
    show: false,
    message: '',
    reviewId: null,
    oldStatus: null,
    timeout: null
})

const filteredReviews = computed(() => {
    return reviews.value
})

const normalizeToolStatus = (value) => {
    return value === true || value === 1 || value === '1' || value === 'true'
}

const pendingOnPage = computed(() => {
    return reviews.value.filter(review => isPendingLike(review))
})

const isPendingLike = (review) => {
    return ['pending', 'spam'].includes(review?.trangthai)
}

const compactPageNumbers = computed(() => {
    const total = Number(pagination.value.last_page || 1)
    const current = Number(currentPage.value || 1)

    if (total <= 7) {
        return Array.from({ length: total }, (_, index) => index + 1)
    }

    const pages = new Set([1, total])
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)

    for (let page = start; page <= end; page++) {
        pages.add(page)
    }

    if (current <= 3) {
        pages.add(2)
        pages.add(3)
        pages.add(4)
    }

    if (current >= total - 2) {
        pages.add(total - 3)
        pages.add(total - 2)
        pages.add(total - 1)
    }

    return [...pages]
        .filter(page => page >= 1 && page <= total)
        .sort((a, b) => a - b)
        .reduce((items, page, index, sorted) => {
            if (index > 0 && page - sorted[index - 1] > 1) {
                items.push('...')
            }
            items.push(page)
            return items
        }, [])
})

const fetchReviews = async () => {
    if (isLoading.value) return
    isLoading.value = true
    try {
        const res = await api.get('/admin/reviews', {
            params: {
                status: activeTab.value,
                page: currentPage.value
            }
        })
        reviews.value = res.data.reviews || []
        pagination.value = res.data.pagination || pagination.value
        statsData.value = res.data.stats || statsData.value
    } catch (err) {
        console.error('Lỗi khi tải đánh giá:', err)
    } finally {
        isLoading.value = false
    }
}

const statusLabel = (status) => {
    const map = { approved: 'ĐÃ DUYỆT', pending: 'CHỜ DUYỆT', spam: 'CHỜ DUYỆT' }
    return map[status] || status
}

const statusClass = (status) => {
    return status === 'spam' ? 'pending' : status
}

/* eslint-disable no-unused-vars */
const updateStatusDropdown = async (review, newStatus) => {
    try {
        const res = await api.put(`/admin/reviews/${review.id_danhgia}/status`, { trangthai: newStatus })
        if (res.data.success) {
            review.trangthai = newStatus
            fetchReviews()
        }
    } catch (err) {
        swal.error('Lỗi', 'Lỗi cập nhật trạng thái: ' + (err.response?.data?.message || err.message))
    }
}

const undoReview = async (review) => {
    try {
        const res = await api.put(`/admin/reviews/${review.id_danhgia}/status`, { trangthai: 'pending' })
        if (res.data.success) {
            review.trangthai = 'pending'
            fetchReviews()
        }
    } catch (err) {
        swal.error('Lỗi', 'Lỗi khôi phục: ' + (err.response?.data?.message || err.message))
    }
}

const showUndoToast = (review, oldStatus) => {
    if (toast.value.timeout) clearTimeout(toast.value.timeout);
    toast.value.show = true;
    toast.value.message = 'Đã chuyển bình luận vào mục SPAM.';
    toast.value.reviewId = review.id_danhgia;
    toast.value.oldStatus = oldStatus;

    toast.value.timeout = setTimeout(() => {
        toast.value.show = false;
    }, 5000);
}

const triggerUndo = async () => {
    const reviewId = toast.value.reviewId;
    const targetStatus = toast.value.oldStatus;
    toast.value.show = false;

    const review = reviews.value.find(r => r.id_danhgia === reviewId);
    if (!review) return;

    try {
        const res = await api.put(`/admin/reviews/${reviewId}/status`, { trangthai: targetStatus });
        if (res.data.success) review.trangthai = targetStatus;
    } catch (err) {
        swal.error('Lỗi', 'Lỗi hoàn tác: ' + err.message);
    }
}

const approveReview = async (review) => {
    const isConfirmed = await swal.confirm('Xác nhận duyệt', 'Bạn có chắc chắn muốn duyệt đánh giá này không?')
    if (!isConfirmed) return;

    try {
        const res = await api.put(`/admin/reviews/${review.id_danhgia}/status`, {
            trangthai: 'approved'
        })
        if (res.data.success) {
            review.trangthai = 'approved'
            swal.success('Thành công', 'Duyệt đánh giá thành công!')
            fetchReviews()
        }
    } catch (err) {
        swal.error('Lỗi', 'Lỗi khi duyệt đánh giá: ' + (err.response?.data?.message || err.message))
    }
}

const markAsSpam = async (review) => {
    const oldStatus = review.trangthai;
    try {
        const res = await api.put(`/admin/reviews/${review.id_danhgia}/status`, {
            trangthai: 'spam'
        })
        if (res.data.success) {
            review.trangthai = 'spam'
            showUndoToast(review, oldStatus);
            fetchReviews()
        }
    } catch (err) {
        swal.error('Lỗi', 'Lỗi khi đánh dấu spam: ' + (err.response?.data?.message || err.message))
    }
}

const deleteReview = async (id) => {
    const isConfirmed = await swal.confirm('Xác nhận xóa', 'Bạn có chắc chắn muốn xóa vĩnh viễn đánh giá này?')
    if (!isConfirmed) return;

    try {
        const res = await api.delete(`/admin/reviews/${id}`)
        if (res.data.success) {
            swal.success('Đã xóa', 'Đã xóa đánh giá thành công!')
            fetchReviews()
        }
    } catch (err) {
        swal.error('Lỗi', 'Lỗi khi xóa đánh giá: ' + (err.response?.data?.message || err.message))
    }
}

const openPendingQueue = () => {
    activeTab.value = 'pending'
    currentPage.value = 1
    fetchReviews()
}

const approvePendingOnPage = async () => {
    const ids = pendingOnPage.value.map(review => review.id_danhgia)
    if (!ids.length) {
        swal.info('Không có bình luận chờ duyệt', 'Trang hiện tại chưa có bình luận nào cần duyệt.')
        return
    }

    const isConfirmed = await swal.confirm(
        'Duyệt bình luận trên trang này',
        `Bạn có chắc muốn duyệt ${ids.length} bình luận đang chờ trên trang hiện tại?`
    )
    if (!isConfirmed) return

    bulkLoading.value = true
    try {
        const res = await api.put('/admin/reviews/bulk-status', {
            ids,
            trangthai: 'approved',
        })

        if (res.data.success) {
            swal.success('Thành công', `Đã duyệt ${res.data.updated || ids.length} bình luận.`)
            await fetchReviews()
        }
    } catch (err) {
        swal.error('Lỗi', 'Lỗi duyệt hàng loạt: ' + (err.response?.data?.message || err.message))
    } finally {
        bulkLoading.value = false
    }
}

const fetchToolStatus = async () => {
    if (toolStatusLoading.value) return
    try {
        const res = await api.get('/admin/reviews/ai-status')
        toolEnabled.value = normalizeToolStatus(res.data.active)
    } catch (err) {
        console.error('Lỗi khi tải trạng thái tool tự duyệt:', err)
    }
}

const runAutoModeration = async () => {
    if (!toolEnabled.value) {
        swal.info('Tool đang tắt', 'Hãy bật tool tự duyệt trước khi chạy kiểm soát đánh giá.')
        return
    }

    autoModerationLoading.value = true
    try {
        const res = await api.post('/admin/reviews/auto-moderate', { limit: 200 })
        const summary = res.data.summary || {}
        await swal.success(
            'Tool tự duyệt đã chạy',
            `Đã quét ${summary.scanned || 0} đánh giá. Duyệt: ${summary.approved || 0}, Spam: ${summary.spam || 0}, còn chờ: ${summary.pending || 0}.`
        )
        await fetchReviews()
    } catch (err) {
        swal.error('Lỗi', 'Không chạy được tool tự duyệt: ' + (err.response?.data?.message || err.message))
    } finally {
        autoModerationLoading.value = false
    }
}

const toggleTool = async () => {
    toolStatusLoading.value = true
    const nextStatus = !toolEnabled.value
    toolEnabled.value = nextStatus

    try {
        const res = await api.post('/admin/reviews/ai-status', { active: nextStatus })
        toolEnabled.value = normalizeToolStatus(res.data.active)
        swal.success(
            toolEnabled.value ? 'Đã bật tool tự duyệt' : 'Đã tắt tool tự duyệt',
            toolEnabled.value
                ? 'Hệ thống sẽ tự kiểm soát đánh giá mới của khách hàng.'
                : 'Đánh giá mới sẽ chuyển sang trạng thái chờ duyệt thủ công.'
        )
    } catch (err) {
        toolEnabled.value = !nextStatus
        swal.error('Lỗi', 'Không cập nhật được trạng thái tool: ' + (err.response?.data?.message || err.message))
    } finally {
        toolStatusLoading.value = false
        await fetchToolStatus()
    }
}

watch(activeTab, () => {
    currentPage.value = 1;
    fetchReviews();
})

watch(currentPage, () => {
    fetchReviews();
})

onMounted(() => {
    fetchReviews()
    fetchToolStatus()
    autoRefreshTimer = window.setInterval(() => {
        fetchToolStatus()
        fetchReviews()
    }, 5000)
})

onUnmounted(() => {
    if (autoRefreshTimer) {
        window.clearInterval(autoRefreshTimer)
        autoRefreshTimer = null
    }
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap');

*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* -- Layout -- */
.page {
    font-family: 'Be Vietnam Pro', sans-serif;
    background: #f0f2f7;
    min-height: 100vh;
    padding: 32px 16px 48px;
    color: #1a1a2e;
}

.container {
    max-width: 1060px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* -- Header -- */
.top-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}

.page-title {
    font-size: 26px;
    font-weight: 800;
    line-height: 1.25;
    color: #0f172a;
}

.page-sub {
    font-size: 13px;
    color: #64748b;
    margin-top: 6px;
    max-width: 240px;
}

/* -- Stat Cards -- */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(220px, 1fr));
    gap: 20px;
}

.stat-card {
    min-height: 136px;
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    border-radius: 16px;
    padding: 26px 28px;
    text-align: left;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 12px 26px rgba(15, 23, 42, .12);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.stat-card::after {
    content: '';
    position: absolute;
    width: 150px;
    height: 150px;
    border-radius: 999px;
    right: -28px;
    top: -54px;
    background: rgba(255, 255, 255, .13);
    pointer-events: none;
}

.stat-card.highlight {
    background: linear-gradient(135deg, #0f2747 0%, #1e3a5f 55%, #0f172a 100%);
}

.stat-card.gold {
    background: linear-gradient(135deg, #c2410c 0%, #f97316 100%);
}

.stat-label {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .03em;
    color: rgba(255, 255, 255, .88);
    margin-bottom: 20px;
    text-transform: capitalize;
}

.stat-value {
    font-size: 34px;
    font-weight: 800;
    color: #fff;
    line-height: 1;
}

.stat-card.highlight .stat-value {
    color: #fff;
}

.stat-card.gold .stat-value {
    color: #fff;
}

.star {
    font-size: 18px;
}

/* -- Table Card -- */
.table-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0, 0, 0, .06);
    overflow: hidden;
}

.table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px 14px;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 10px;
}

.table-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

/* Filter tabs */
.filter-tabs {
    display: flex;
    gap: 6px;
}

.tab-btn {
    padding: 6px 16px;
    border-radius: 20px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all .18s;
}

.tab-btn.active,
.tab-btn:hover {
    background: #1e293b;
    border-color: #1e293b;
    color: #fff;
}

/* Table */
.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead tr {
    background: #f8fafc;
}

th {
    padding: 10px 16px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .6px;
    color: #94a3b8;
    text-align: left;
    white-space: nowrap;
}

td {
    padding: 14px 16px;
    vertical-align: middle;
}

.review-row {
    border-bottom: 1px solid #f1f5f9;
    transition: background .15s;
}

.review-row:last-child {
    border-bottom: none;
}

.review-row:hover {
    background: #f8fafc;
}

/* Customer cell */
.customer-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 160px;
}

.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    color: #fff;
    flex-shrink: 0;
}

.avatar svg {
    width: 20px;
    height: 20px;
}

.customer-name {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
}

.customer-name.anonymous {
    color: #94a3b8;
    font-style: italic;
}

.customer-email {
    font-size: 11.5px;
    color: #94a3b8;
}

/* Product */
.product-link {
    font-size: 12.5px;
    font-weight: 600;
    color: #3b82f6;
    white-space: nowrap;
}

/* Stars */
.stars {
    display: flex;
    gap: 1px;
}

.star-icon {
    font-size: 14px;
}

.star-icon.filled {
    color: #f59e0b;
}

.star-icon.empty {
    color: #e2e8f0;
}

/* Review text */
.review-text {
    font-size: 12.5px;
    color: #475569;
    max-width: 280px;
    line-height: 1.5;
    /* Hi?n th? t?i da 2 dòng và thêm d?u 3 ch?m */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
}

.review-text.spam {
    color: #ef4444;
}

/* Date */
.date-text {
    font-size: 12px;
    color: #94a3b8;
    white-space: nowrap;
}

/* Status badge */
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .4px;
    white-space: nowrap;
}

.status-badge.approved {
    background: #dcfce7;
    color: #2563eb;
}

.status-badge.pending {
    background: #fef9c3;
    color: #ca8a04;
}

.status-badge.spam {
    background: #fee2e2;
    color: #dc2626;
}

/* Action buttons */
.action-btns {
    display: flex;
    align-items: center;
    gap: 6px;
}

.action-btn {
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-family: inherit;
    font-size: 10px;
    font-weight: 700;
    transition: all .18s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn.approve {
    padding: 6px 10px;
    background: #1e293b;
    color: #fff;
    line-height: 1.3;
    text-align: center;
    letter-spacing: .4px;
}

.action-btn.approve:hover {
    background: #0f172a;
}

.action-btn.icon-btn {
    width: 30px;
    height: 30px;
    padding: 0;
}

.action-btn.icon-btn svg {
    width: 14px;
    height: 14px;
}

.action-btn.reply {
    background: #eff6ff;
    color: #3b82f6;
}

.action-btn.reply:hover {
    background: #dbeafe;
}

.action-btn.delete {
    background: #fee2e2;
    color: #ef4444;
}

.action-btn.delete:hover {
    background: #fecaca;
}

/* Pagination */
.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 24px;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 10px;
}

.page-info {
    font-size: 12.5px;
    color: #94a3b8;
}

.page-btns {
    display: flex;
    gap: 4px;
    align-items: center;
}

.page-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all .15s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.page-btn:hover {
    border-color: #1e293b;
    color: #1e293b;
}

.page-btn.active {
    background: #1e293b;
    border-color: #1e293b;
    color: #fff;
}

.page-btn.arrow {
    font-size: 11px;
}

.page-dots {
    font-size: 13px;
    color: #94a3b8;
    padding: 0 4px;
}

/* -- Bottom Banners -- */
.bottom-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.banner-card {
    border-radius: 16px;
    padding: 28px 28px 24px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
}

.banner-card.ai-ready {
    background: #fff;
    box-shadow: 0 2px 16px rgba(0, 0, 0, .06);
}

.banner-card.moderation-tool {
    background: #0f172a;
    color: #fff;
}

.tool-card-head {
    width: 100%;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.tool-switch {
    min-width: 124px;
    height: 36px;
    padding: 0 12px 0 6px;
    border: 1px solid rgba(148, 163, 184, .36);
    border-radius: 999px;
    background: rgba(148, 163, 184, .12);
    color: #cbd5e1;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    white-space: nowrap;
    transition: all .18s;
}

.tool-switch .tool-dot {
    width: 22px;
    height: 22px;
    border-radius: 999px;
    background: #94a3b8;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
    flex: 0 0 auto;
}

.tool-switch.active {
    border-color: rgba(34, 197, 94, .5);
    background: rgba(34, 197, 94, .16);
    color: #bbf7d0;
}

.tool-switch.active .tool-dot {
    background: #22c55e;
}

.tool-switch strong {
    font: inherit;
}

.tool-switch:disabled {
    opacity: .62;
    cursor: not-allowed;
}

.banner-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #eff6ff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3b82f6;
}

.banner-icon svg {
    width: 22px;
    height: 22px;
}

.banner-badge {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .8px;
    color: #a78bfa;
    background: rgba(167, 139, 250, .12);
    padding: 3px 10px;
    border-radius: 20px;
}

.banner-card h4 {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.3;
}

.banner-card.moderation-tool h4 {
    color: #fff;
}

.banner-card p {
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
}

.banner-card.moderation-tool p {
    color: #94a3b8;
}

.tool-summary {
    width: 100%;
    margin-top: 4px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgba(255, 255, 255, .08);
    border: 1px solid rgba(255, 255, 255, .1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.tool-summary span {
    font-size: 12px;
    font-weight: 700;
    color: #cbd5e1;
}

.tool-summary strong {
    font-size: 24px;
    line-height: 1;
    color: #fff;
}

.tool-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
}

.banner-btn {
    margin-top: 4px;
    padding: 9px 20px;
    border-radius: 24px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .4px;
    cursor: pointer;
    transition: all .18s;
}

.banner-btn.outline {
    border: 1.5px solid #e2e8f0;
    background: #fff;
    color: #475569;
}

.banner-btn.outline:hover {
    border-color: #1e293b;
    color: #1e293b;
}

.banner-btn.primary {
    border: none;
    background: #3b82f6;
    color: #fff;
}

.banner-btn.primary:hover {
    background: #2563eb;
    transform: scale(1.03);
}

.banner-btn.success {
    border: none;
    background: #22c55e;
    color: #052e16;
}

.banner-btn.success:hover:not(:disabled) {
    background: #86efac;
    transform: scale(1.03);
}

.banner-btn.warning {
    border: none;
    background: #f59e0b;
    color: #111827;
}

.banner-btn.warning:hover:not(:disabled) {
    background: #fbbf24;
    transform: scale(1.03);
}

.banner-btn.warning.active {
    background: #ef4444;
    color: #fff;
}

.banner-btn.warning.active:hover:not(:disabled) {
    background: #dc2626;
}

.banner-btn:disabled {
    opacity: .55;
    cursor: not-allowed;
    transform: none;
}

.banner-btn.active-btn {
    border: none;
    background: #2563eb;
    color: #fff;
}

.banner-btn.active-btn:hover {
    background: #1D4ED8;
    transform: scale(1.03);
}

/* -- Responsive -- */
@media (max-width: 600px) {
    .bottom-row {
        grid-template-columns: 1fr;
    }

    .stats-row {
        flex-wrap: wrap;
    }
}

/* -- Toast Undo -- */
.toast-undo {
    position: fixed;
    bottom: 40px;
    right: 40px;
    background: #1e293b;
    color: #fff;
    padding: 14px 20px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 16px;
    z-index: 9999;
}
.toast-message { font-size: 14px; font-weight: 500; }
.toast-btn-undo {
    background: #3b82f6;
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s;
    letter-spacing: 0.3px;
}
.toast-btn-undo:hover { background: #60a5fa; color: #0f172a; }

.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(30px);
}
</style>

