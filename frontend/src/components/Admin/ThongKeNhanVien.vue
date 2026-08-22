<template>
    <div class="page">
        <!-- BREADCRUMB -->
        <div class="breadcrumb">
            <span>Admin</span>
            <span class="sep">›</span>
            <span class="active-crumb">Thống kê doanh số nhân viên</span>
        </div>

        <div class="top">
            <h1>Thống kê doanh số nhân viên</h1>
        </div>

        <!-- FILTERS -->
        <div class="filter-wrap">
            <div class="filter-grid">
                <!-- Employee select -->
                <div class="filter-item">
                    <label>Nhân viên</label>
                    <select v-model="selectedEmployeeId" @change="loadStats">
                        <option value="all">Tất cả nhân viên</option>
                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                            {{ emp.name }} ({{ emp.email }})
                        </option>
                    </select>
                </div>

                <!-- Start Date -->
                <div class="filter-item">
                    <label>Từ ngày</label>
                    <input type="date" v-model="startDate" @change="loadStats" />
                </div>

                <!-- End Date -->
                <div class="filter-item">
                    <label>Đến ngày</label>
                    <input type="date" v-model="endDate" @change="loadStats" />
                </div>

                <!-- Order Status -->
                <div class="filter-item">
                    <label>Trạng thái đơn hàng</label>
                    <select v-model="orderStatus" @change="loadStats">
                        <option value="done">Hoàn thành (Đã giao & đã thu tiền)</option>
                        <option value="shipping">Đang giao hàng</option>
                        <option value="confirmed">Đã xác nhận</option>
                        <option value="pending">Chờ xác nhận</option>
                        <option value="all">Tất cả trạng thái</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn-reset" @click="resetFilters">Đặt lại bộ lọc</button>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <span>Đang tải thống kê doanh số...</span>
        </div>

        <div v-else>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="card-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1" />
                            <circle cx="20" cy="21" r="1" />
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                        </svg>
                    </div>
                    <div class="card-info">
                        <span>Tổng số đơn hàng</span>
                        <h3>{{ statsSummary.total_orders || 0 }} đơn</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                        </svg>
                    </div>
                    <div class="card-info">
                        <span>Doanh thu tính công</span>
                        <h3>{{ formatMoney(statsSummary.total_revenue) }}</h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="card-icon purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                            <line x1="12" y1="22.08" x2="12" y2="12" />
                        </svg>
                    </div>
                    <div class="card-info">
                        <span>Số sản phẩm bán được</span>
                        <h3>{{ statsSummary.total_items || 0 }} cái</h3>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT AREA -->
            <div class="content-row">
                <!-- LEADERBOARD / EMPLOYEES COMPARISON (Only shown if 'all' is selected) -->
                <div v-if="selectedEmployeeId === 'all'" class="panel leaderboard-panel">
                    <div class="panel-header">
                        <h2>Xếp hạng doanh số nhân viên</h2>
                        <span class="panel-sub">Sắp xếp theo doanh thu từ cao xuống thấp</span>
                    </div>
                    <div class="panel-body">
                        <div v-if="leaderboard.length === 0" class="empty-state">
                            Chưa có dữ liệu bán hàng cho nhân viên nào trong thời gian này.
                        </div>
                        <div v-else class="leaderboard-list">
                            <div v-for="(leader, idx) in leaderboard" :key="leader.id" class="leader-row">
                                <div class="rank" :class="'rank-' + (idx + 1)">{{ idx + 1 }}</div>
                                <div class="leader-avatar">
                                    {{ getInitials(leader.name) }}
                                </div>
                                <div class="leader-details">
                                    <h4>{{ leader.name }}</h4>
                                    <span>{{ leader.email }}</span>
                                </div>
                                <div class="leader-metrics">
                                    <div>
                                        <small>Đơn hàng</small>
                                        <b>{{ leader.orders_count }}</b>
                                    </div>
                                    <div>
                                        <small>Sản phẩm</small>
                                        <b>{{ leader.items_count }}</b>
                                    </div>
                                    <div>
                                        <small>Doanh thu</small>
                                        <b class="revenue-text">{{ formatMoney(leader.revenue) }}</b>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SALES TIMELINE CHART -->
                <div class="panel chart-panel">
                    <div class="panel-header">
                        <h2>Lịch sử bán hàng theo ngày</h2>
                        <span class="panel-sub">Thống kê số lượng sản phẩm bán ra theo thời gian</span>
                    </div>
                    <div class="panel-body">
                        <div v-if="timeline.length === 0" class="empty-state">
                            Chưa có dữ liệu dòng thời gian.
                        </div>
                        <div v-else class="timeline-chart">
                            <div class="chart-bars">
                                <div v-for="day in timeline" :key="day.date" class="chart-bar-col">
                                    <div class="chart-bar-container">
                                        <div class="chart-bar-fill" 
                                             :style="{ height: getBarHeight(day.items_count) + '%' }" 
                                             :title="day.date + ': ' + day.items_count + ' sản phẩm, ' + formatMoney(day.revenue)">
                                             <span class="bar-tooltip">
                                                 <b>{{ day.items_count }} sản phẩm</b><br>
                                                 {{ formatMoney(day.revenue) }}
                                             </span>
                                        </div>
                                    </div>
                                    <span class="chart-bar-label">{{ formatDateLabel(day.date) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DETAILED PRODUCTS TABLE -->
            <div class="panel products-panel" style="margin-top: 24px;">
                <div class="panel-header">
                    <h2>Danh sách sản phẩm bán được</h2>
                    <span class="panel-sub">Chi tiết số lượng và doanh số từng sản phẩm</span>
                </div>
                <div class="panel-body table-wrap" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 70px;">Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Biến thể</th>
                                <th style="text-align: center;">Số lượng bán</th>
                                <th style="text-align: right;">Tổng doanh số</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="products.length === 0">
                                <td colspan="5" class="empty">Không có sản phẩm nào bán ra trong kỳ.</td>
                            </tr>
                            <tr v-for="prod in paginatedProducts" :key="prod.product_id + '_' + prod.variant_name">
                                <td>
                                    <img :src="getImageUrl(prod.image)" class="prod-img" alt="Sản phẩm" />
                                </td>
                                <td>
                                    <b class="prod-name">{{ prod.product_name }}</b>
                                </td>
                                <td>
                                    <span class="prod-variant">{{ prod.variant_name || 'Mặc định' }}</span>
                                </td>
                                <td style="text-align: center;">
                                    <b class="prod-qty">{{ prod.quantity }}</b>
                                </td>
                                <td style="text-align: right;">
                                    <b class="prod-revenue">{{ formatMoney(prod.revenue) }}</b>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-footer" style="background: #ffffff; border-top: 1px solid #f1f5f9; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px; padding: 10px 0;">
                    <PhanTrangAdmin 
                        v-model:currentPage="currentPage" 
                        :totalPages="totalPages" 
                        :totalItems="products.length"
                        :pageSize="itemsPerPage"
                        itemLabel="sản phẩm"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../../services/api'
import swal from '../../services/swal'
import { productImageUrl } from '@/services/urls'
import PhanTrangAdmin from './PhanTrangAdmin.vue'

const selectedEmployeeId = ref('all')
const startDate = ref('')
const endDate = ref('')
const orderStatus = ref('done')

const employees = ref([])
const leaderboard = ref([])
const timeline = ref([])
const products = ref([])
const statsSummary = ref({
    total_orders: 0,
    total_revenue: 0,
    total_items: 0
})
const isLoading = ref(false)

const currentPage = ref(1)
const itemsPerPage = 6

const paginatedProducts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    return products.value.slice(start, start + itemsPerPage)
})

const totalPages = computed(() => {
    return Math.ceil(products.value.length / itemsPerPage) || 1
})

const formatMoney = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ'

const getImageUrl = (img) => {
    if (!img) return 'https://placehold.co/50'
    if (img.startsWith('http')) return img
    return productImageUrl({ hinhanh: img }, null, 'https://placehold.co/50')
}

const getInitials = (name) => {
    if (!name) return 'NV'
    return name.split(' ').map(w => w[0]).slice(-2).join('').toUpperCase()
}

const formatDateLabel = (dateStr) => {
    if (!dateStr) return ''
    const parts = dateStr.split('-')
    if (parts.length < 3) return dateStr
    return `${parts[2]}/${parts[1]}`
}

const getBarHeight = (count) => {
    if (!timeline.value.length) return 0
    const maxVal = Math.max(...timeline.value.map(d => d.items_count), 1)
    return (count / maxVal) * 100
}
const fetchEmployees = async () => {
    try {
        const res = await api.get('/admin/account/active-admins?_t=' + Date.now(), { cache: false })
        if (res.data.success) {
            employees.value = res.data.admins || res.data.data || []
        }
    } catch (err) {
        console.error('Lỗi tải danh sách nhân viên:', err)
    }
}

const loadStats = async () => {
    try {
        isLoading.value = true
        currentPage.value = 1
        const params = {
            id_nhanvien: selectedEmployeeId.value,
            trangthai: orderStatus.value
        }
        if (startDate.value) params.start_date = startDate.value
        if (endDate.value) params.end_date = endDate.value

        const res = await api.get('/admin/orders/employee-stats?_t=' + Date.now(), { cache: false, params })
        if (res.data.success) {
            statsSummary.value = res.data.summary || { total_orders: 0, total_revenue: 0, total_items: 0 }
            products.value = res.data.products || []
            leaderboard.value = res.data.leaderboard || []
            timeline.value = res.data.timeline || []
        }
    } catch (err) {
        console.error('Lỗi tải thống kê doanh số:', err)
        swal.error('Lỗi', 'Không thể tải thống kê doanh số nhân viên.')
    } finally {
        isLoading.value = false
    }
}

const resetFilters = () => {
    selectedEmployeeId.value = 'all'
    startDate.value = ''
    endDate.value = ''
    orderStatus.value = 'done'
    currentPage.value = 1
    loadStats()
}

onMounted(() => {
    fetchEmployees()
    loadStats()
})
</script>

<style scoped>
.page {
    padding: 24px 20px;
    background: #f5f7fb;
    min-height: 100vh;
    font-family: sans-serif;
}

/* BREADCRUMB */
.breadcrumb {
    font-size: 12px;
    color: #94a3b8;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.sep {
    color: #cbd5e1;
}

.active-crumb {
    color: #2563eb;
    font-weight: 500;
}

/* TOP */
.top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.top h1 {
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.02em;
}

/* FILTERS */
.filter-wrap {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 16px 20px;
    margin-bottom: 24px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-item label {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.filter-item select,
.filter-item input {
    padding: 9px 12px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 13px;
    color: #0f172a;
    background: #f8fafc;
    outline: none;
    transition: all 0.2s;
    font-weight: 500;
}

.filter-item select:focus,
.filter-item input:focus {
    border-color: #2563eb;
    background: white;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.filter-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 14px;
}

.btn-reset {
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: white;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-reset:hover {
    background: #f1f5f9;
    color: #0f172a;
}

/* STATS CARDS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.02);
}

.card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-icon svg {
    width: 22px;
    height: 22px;
}

.card-icon.blue {
    background: #eff6ff;
    color: #2563eb;
}

.card-icon.green {
    background: #f0fdf4;
    color: #16a34a;
}

.card-icon.purple {
    background: #faf5ff;
    color: #9333ea;
}

.card-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.card-info span {
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
}

.card-info h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
}

/* CONTENT ROW */
.content-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
}

.panel {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.02);
    display: flex;
    flex-direction: column;
}

.panel-header {
    padding: 18px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.panel-header h2 {
    margin: 0 0 4px;
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
}

.panel-sub {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
}

.panel-body {
    padding: 20px;
    flex: 1;
}

/* LEADERBOARD LIST */
.leaderboard-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.leader-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.rank {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 13px;
    background: #e2e8f0;
    color: #475569;
}

.rank.rank-1 {
    background: #fef08a;
    color: #854d0e;
}

.rank.rank-2 {
    background: #e2e8f0;
    color: #475569;
}

.rank.rank-3 {
    background: #ffedd5;
    color: #9a3412;
}

.leader-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    flex-shrink: 0;
}

.leader-details {
    flex: 1;
    min-width: 0;
}

.leader-details h4 {
    margin: 0 0 2px;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.leader-details span {
    font-size: 11px;
    color: #64748b;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.leader-metrics {
    display: flex;
    gap: 14px;
    text-align: right;
}

.leader-metrics div {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.leader-metrics small {
    font-size: 10px;
    color: #94a3b8;
    font-weight: 600;
    text-transform: uppercase;
}

.leader-metrics b {
    font-size: 13px;
    color: #334155;
    font-weight: 700;
}

.leader-metrics b.revenue-text {
    color: #16a34a;
}

/* TIMELINE CHART */
.timeline-chart {
    height: 250px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.chart-bars {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 8px;
    height: 210px;
    overflow-x: auto;
    padding-bottom: 8px;
}

.chart-bar-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    min-width: 32px;
    height: 100%;
}

.chart-bar-container {
    flex: 1;
    width: 16px;
    background: #f1f5f9;
    border-radius: 4px;
    display: flex;
    align-items: flex-end;
    position: relative;
}

.chart-bar-fill {
    width: 100%;
    background: linear-gradient(to top, #3b82f6, #60a5fa);
    border-radius: 4px;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
}

.chart-bar-fill:hover {
    background: #2563eb;
}

.bar-tooltip {
    visibility: hidden;
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: #0f172a;
    color: white;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 11px;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 100;
    pointer-events: none;
    text-align: center;
}

.chart-bar-fill:hover .bar-tooltip {
    visibility: visible;
}

.chart-bar-label {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 6px;
    font-weight: 600;
}

/* PRODUCTS TABLE */
.table-wrap {
    overflow-x: auto;
    padding: 0;
    border-radius: 0 0 14px 14px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead th {
    background: #f8fafc;
    padding: 12px 20px;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-align: left;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    border-bottom: 1px solid #e2e8f0;
}

tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}

tbody tr:last-child {
    border-bottom: none;
}

tbody tr:hover {
    background: #fafbff;
}

tbody td {
    padding: 12px 20px;
    font-size: 13px;
    color: #334155;
    vertical-align: middle;
}

tbody td.empty {
    text-align: center;
    color: #94a3b8;
    font-style: italic;
    padding: 30px;
}

.prod-img {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}

.prod-name {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.prod-variant {
    background: #f1f5f9;
    color: #475569;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.prod-qty {
    color: #0f172a;
    font-weight: 800;
}

.prod-revenue {
    color: #16a34a;
    font-weight: 700;
}

/* MISC STATES */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px;
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    color: #64748b;
    gap: 16px;
    font-weight: 600;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #2563eb;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
    font-style: italic;
    font-size: 13px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
