<template>
    <div class="page">
        <div class="container">

            <!-- ── Top Header ── -->
            <div class="top-header">
                <div class="header-left">
                    <h1 class="page-title">Quản lý Bình luận<br />&amp; Đánh giá</h1>
                    <p class="page-sub">Theo dõi và phản hồi các đánh giá từ khách hàng của VinaTech</p>
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
            </div>

            <!-- ── Table Card ── -->
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
                                    <p class="review-text" :class="{ spam: review.trangthai === 'spam' }">
                                        {{ review.binhluan || '(Không có nội dung)' }}
                                    </p>
                                </td>

                                <!-- Date -->
                                <td><span class="date-text">{{ new Date(review.created_at).toLocaleDateString() }}</span></td>

                                <!-- Status -->
                                <td>
                                    <span class="status-badge" :class="review.trangthai">
                                        {{ statusLabel(review.trangthai) }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td>
                                    <div class="action-btns">
                                        <button v-if="review.trangthai === 'pending'" class="action-btn approve"
                                            @click="approveReview(review)">DUYỆT<br />NGAY</button>
                                        
                                        <button v-if="review.trangthai !== 'spam'" class="action-btn icon-btn" style="background:#fff7ed; color:#f97316" title="Đánh dấu Spam" @click="markAsSpam(review)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                            </svg>
                                        </button>

                                        <button v-if="review.trangthai === 'spam'" class="action-btn icon-btn" style="background:#ecfdf5; color:#2563eb" title="Khôi phục trạng thái" @click="undoReview(review)">
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
                <div class="pagination" v-if="pagination.last_page > 1">
                    <span class="page-info">Trang {{ pagination.current_page }} / {{ pagination.last_page }} (Tổng {{ pagination.total }} đánh giá)</span>
                    <div class="page-btns">
                        <button class="page-btn arrow" :disabled="currentPage === 1" @click="currentPage--">&lt;</button>
                        <template v-for="(p, index) in compactPageNumbers" :key="`${p}-${index}`">
                            <span v-if="p === '...'" class="page-dots">...</span>
                            <button v-else class="page-btn" :class="{ active: currentPage === p }"
                                @click="currentPage = p">{{ p }}</button>
                        </template>
                        <button class="page-btn arrow" :disabled="currentPage === pagination.last_page" @click="currentPage++">&gt;</button>
                    </div>
                </div>
            </div>

            <!-- ── Bottom Banners ── -->
            <div class="bottom-row">
                <div class="banner-card ai-ready">
                    <div class="banner-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M9 12l2 2 4-4" />
                            <path d="M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z" />
                        </svg>
                    </div>
                    <h4>Tất cả đã sẵn sàng!</h4>
                    <p>Hiện không có bình luận nào cần kiểm duyệt gấp. Hệ thống AI đang tự động lọc các nội dung spam
                        thô tục.</p>
                    <button class="banner-btn outline" @click="activeTab = 'spam'">KIỂM TRA BỘ LỌC AI</button>
                </div>

                <div class="banner-card smart-reply">
                    <div class="banner-badge">✦ AI</div>
                    <h4>Trợ lý AI<br />Smart Reply</h4>
                    <p>Tự động soạn thảo câu trả lời dựa trên nội dung khách hàng bình luận.</p>
                    <button 
                        class="banner-btn" 
                        :class="isAiActive ? 'active-btn' : 'primary'"
                        @click="toggleAiStatus"
                    >
                        {{ isAiActive ? 'HỦY KÍCH HOẠT' : 'KÍCH HOẠT NGAY' }}
                    </button>
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
import { ref, computed, watch, onMounted } from 'vue'
import api from '../../services/api'
import swal from '@/services/swal'

const activeTab = ref('all')
const currentPage = ref(1)
const isLoading = ref(false)

const tabs = [
    { key: 'all', label: 'Tất cả' },
    { key: 'pending', label: 'Chờ duyệt' },
    { key: 'approved', label: 'Đã duyệt' },
    { key: 'spam', label: 'Spam' },
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
    const map = { approved: 'ĐÃ DUYỆT', pending: 'CHỜ DUYỆT', spam: 'SPAM' }
    return map[status] || status
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

const isAiActive = ref(false)

const fetchAiStatus = async () => {
    try {
        const res = await api.get('/admin/reviews/ai-status')
        if (res.data.success) {
            isAiActive.value = res.data.active
        }
    } catch (err) {
        console.error('Lỗi khi tải trạng thái AI:', err)
    }
}

const toggleAiStatus = async () => {
    const nextState = !isAiActive.value
    const confirmTitle = nextState ? 'Kích hoạt Trợ lý AI' : 'Hủy kích hoạt Trợ lý AI'
    const confirmMsg = nextState 
        ? 'Bạn có chắc muốn kích hoạt Trợ lý AI Smart Reply để tự động phê duyệt và trả lời cảm ơn khách hàng đã mua sắm?' 
        : 'Bạn có chắc muốn hủy kích hoạt Trợ lý AI Smart Reply? Các bình luận mới sẽ phải duyệt thủ công.'
    
    const isConfirmed = await swal.confirm(confirmTitle, confirmMsg)
    if (!isConfirmed) return

    try {
        const res = await api.post('/admin/reviews/ai-status', { active: nextState })
        if (res.data.success) {
            isAiActive.value = res.data.active
            swal.success('Thành công', res.data.message)
        }
    } catch (err) {
        swal.error('Lỗi', 'Lỗi thiết lập AI: ' + (err.response?.data?.message || err.message))
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
    fetchAiStatus()
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

/* ── Layout ── */
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

/* ── Header ── */
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

/* ── Stat Cards ── */
.stats-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 14px 22px;
    text-align: center;
    min-width: 110px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
}

.stat-card.highlight {
    background: #1e293b;
}

.stat-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .8px;
    color: #94a3b8;
    margin-bottom: 6px;
}

.stat-value {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.stat-card.highlight .stat-value {
    color: #fff;
}

.stat-card.gold .stat-value {
    color: #f59e0b;
}

.star {
    font-size: 18px;
}

/* ── Table Card ── */
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
    /* Hiển thị tối đa 2 dòng và thêm dấu 3 chấm */
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

/* ── Bottom Banners ── */
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

.banner-card.smart-reply {
    background: #0f172a;
    color: #fff;
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

.banner-card.smart-reply h4 {
    color: #fff;
}

.banner-card p {
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
}

.banner-card.smart-reply p {
    color: #94a3b8;
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

.banner-btn.active-btn {
    border: none;
    background: #2563eb;
    color: #fff;
}

.banner-btn.active-btn:hover {
    background: #1D4ED8;
    transform: scale(1.03);
}

/* ── Responsive ── */
@media (max-width: 600px) {
    .bottom-row {
        grid-template-columns: 1fr;
    }

    .stats-row {
        flex-wrap: wrap;
    }
}

/* ── Toast Undo ── */
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
