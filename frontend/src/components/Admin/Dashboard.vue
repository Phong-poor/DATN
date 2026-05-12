<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import api from '@/services/api'
import echo from '@/services/echo'

// ─── State ───────────────────────────────────────────────────────────────────
const period = ref('all')          // all | week | month | year
const loading = ref(true)
const data = ref(null)
const searchQuery = ref('')
const hoveredStatus = ref(null) // Để quản lý trạng thái đang hover

const today = new Date().toLocaleDateString('vi-VN', { day: '2-digit', month: 'long', year: 'numeric' })

// ─── Fetch ────────────────────────────────────────────────────────────────────
async function fetchDashboard() {
    loading.value = true
    try {
        const res = await api.get('/admin/dashboard', { 
            params: { period: period.value } 
        })

        data.value = res.data.data   // ✅ QUAN TRỌNG

    } catch (e) {
        console.error('Dashboard fetch error:', e)
    } finally {
        loading.value = false
    }
}
function getColor(status) {
    return {
        pending: '#facc15',
        confirmed: '#34d399',
        shipping: '#60a5fa',
        done: '#2563eb',
        cancelled: '#f87171'
    }[status] || '#ccc'
}
onMounted(() => {
    fetchDashboard()

    echo.channel('admin-orders')
        .listen('.order.placed', (e) => {
            if (!data.value) return
            
            const newOrder = {
                id: '#DH-' + String(e.order.id_dathang).padStart(4, '0'),
                khach: e.order.user?.name ?? 'N/A',
                tong: new Intl.NumberFormat('vi-VN').format(e.order.tongtien) + 'đ',
                status: e.order.trangthai,
                trangthai: 'Chờ xác nhận',
            }

            // Thêm vào đầu danh sách
            if (!data.value.don_hang) data.value.don_hang = []
            data.value.don_hang.unshift(newOrder)
            if (data.value.don_hang.length > 5) {
                data.value.don_hang.pop()
            }
            
            // Cập nhật stats (count tổng đơn hàng chờ xác nhận)
            const pendingStatus = data.value.trang_thai?.find(s => s.status === 'pending')
            if (pendingStatus) {
                pendingStatus.count++
            }
        })
})
onUnmounted(() => {
    echo.leaveChannel('admin-orders')
})
watch(period, fetchDashboard)

// ─── Stats cards ─────────────────────────────────────────────────────────────
const stats = computed(() => {
    if (!data.value) return []
    // Debug: mở DevTools > Console để xem API trả về key nào
    console.log('[Dashboard] API data:', JSON.stringify(data.value))
    return [
        { label: 'Doanh thu tổng', value: data.value.doanh_thu ?? '0đ', icon: '💰', color: '#dbeafe' },
        { label: 'Khách hàng', value: data.value.khach_hang ?? 0, icon: '👥', color: '#ede9fe' },
        { label: 'Sản phẩm kho', value: data.value.bien_the ?? 0, icon: '🗃️', color: '#f1f5f9' },
    ]
})

// ─── Donut chart helpers ──────────────────────────────────────────────────────
const cx = 60, cy = 60, r = 46
const circumference = 2 * Math.PI * r

const segments = computed(() => {
    if (!data.value?.trang_thai) return []
    const total = data.value.trang_thai.reduce((s, d) => s + d.count, 0) || 1
    let offset = 0
    return data.value.trang_thai.map(d => {
        const dash = (d.count / total) * circumference
        const gap = circumference - dash
        const seg = { ...d, dash, gap, offset, color: getColor(d.status) }
        offset += dash
        return seg
    })
})

const centerStat = computed(() => {
    const statusToShow = hoveredStatus.value || 'done'
    const found = data.value?.trang_thai?.find(t => t.status === statusToShow)
    return {
        pct: found?.pct ?? 0,
        label: found?.label?.toUpperCase() ?? 'THÀNH CÔNG'
    }
})

// ─── Bar chart helpers ────────────────────────────────────────────────────────
const barChartData = computed(() => {
    if (!data.value?.bieu_do?.length) return []
    const maxVal = Math.max(...data.value.bieu_do.map(d => d.total), 1)
    const arr = data.value.bieu_do
    const maxIdx = arr.reduce((mi, d, i) => d.total > arr[mi].total ? i : mi, 0)
    return arr.map((d, i) => ({
        label: d.label,
        val: Math.round((d.total / maxVal) * 95) + 5,   // 5–100 %
        total: d.total,
        highlight: i === maxIdx,
    }))
})

// ─── Status badge class ───────────────────────────────────────────────────────
function statusClass(s) {
    return { pending: 'warn', confirmed: 'confirmed', shipping: 'info', done: 'ok', cancelled: 'out' }[s] ?? 'warn'
}

// ─── Period label ─────────────────────────────────────────────────────────────
const periodLabel = computed(() => ({ all: 'Tất cả thời gian', week: 'Tuần này', month: 'Tháng này', year: 'Năm nay' }[period.value]))
</script>

<template>
    <div class="page">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="topbar-left">
                <h2>Tổng quan hệ thống</h2>
                <p>Chào mừng trở lại, hôm nay là {{ today }}</p>
            </div>
            <div class="topbar-right">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input v-model="searchQuery" placeholder="Tìm kiếm dữ liệu..." />
                </div>
                <button class="icon-btn">🔔</button>
                <router-link to="/" class="btn-home">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                    Trang chủ
                </router-link>
            </div>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="loading-wrap">
            <div class="spinner"></div>
            <span>Đang tải dữ liệu...</span>
        </div>

        <template v-else-if="data">

            <!-- PERIOD SELECTOR (global) -->
            <div class="period-bar">
                <span class="period-bar-label">Kỳ thống kê:</span>
                <div class="period-tabs">
                    <button v-for="p in [['all', 'Tất cả'], ['week', 'Tuần này'], ['month', 'Tháng này'], ['year', 'Năm nay']]" :key="p[0]"
                        :class="['period-tab', { active: period === p[0] }]" @click="period = p[0]">
                        {{ p[1] }}
                    </button>
                </div>
            </div>

            <!-- STAT CARDS -->
            <div class="stats-grid">
                <div class="stat-card" v-for="s in stats" :key="s.label">
                    <div class="stat-top">
                        <div class="stat-icon-wrap" :style="{ background: s.color }">{{ s.icon }}</div>
                    </div>
                    <p class="stat-label">{{ s.label }}</p>
                    <b class="stat-value">{{ s.value }}</b>
                </div>
            </div>

            <!-- CHARTS ROW -->
            <div class="charts-row">

                <!-- BAR CHART -->
                <div class="card chart-card">
                    <div class="chart-header">
                        <span class="chart-title">Biểu đồ doanh thu ({{ periodLabel }})</span>
                    </div>
                    <div class="bar-chart">
                        <div v-if="barChartData.length" class="bars">
                            <div class="bar-col" v-for="d in barChartData" :key="d.label">
                                <div class="bar-wrap">
                                    <span v-if="d.highlight" class="bar-tooltip">
                                        {{ Number(d.total).toLocaleString('vi-VN') }}đ
                                    </span>
                                    <div class="bar-fill"
                                        :style="{ height: d.val + '%', background: d.highlight ? '#2563eb' : '#bfdbfe' }">
                                    </div>
                                </div>
                                <span class="bar-label">{{ d.label }}</span>
                            </div>
                        </div>
                        <div v-else class="empty-chart">Chưa có dữ liệu trong kỳ này</div>
                    </div>
                </div>

                <!-- DONUT CHART -->
                <div class="card donut-card">
                    <div class="chart-title" style="margin-bottom:16px">Trạng thái đơn hàng</div>
                    <div class="donut-wrap">
                        <svg viewBox="0 0 120 120" class="donut-svg">
                            <circle cx="60" cy="60" r="46" fill="none" stroke="#f1f5f9" stroke-width="14" />
                            <circle v-for="seg in segments" :key="seg.status" cx="60" cy="60" r="46" fill="none"
                                :stroke="seg.color" stroke-width="14" :stroke-dasharray="`${seg.dash} ${seg.gap}`"
                                :stroke-dashoffset="-seg.offset" stroke-linecap="butt"
                                @mouseenter="hoveredStatus = seg.status"
                                @mouseleave="hoveredStatus = null"
                                style="transform: rotate(-90deg); transform-origin: 50% 50%; cursor: pointer; transition: stroke-width 0.2s;" 
                                :stroke-width="hoveredStatus === seg.status ? 18 : 14" />
                            <text x="60" y="55" text-anchor="middle" font-size="16" font-weight="800" fill="#0f172a">
                                {{ centerStat.pct }}%
                            </text>
                            <text x="60" y="70" text-anchor="middle" font-size="7" fill="#94a3b8" font-weight="700">
                                {{ centerStat.label }}
                            </text>
                        </svg>
                    </div>
                    <div class="donut-legend">
                        <div class="legend-item" v-for="d in data.trang_thai" :key="d.status">
                            <span class="legend-dot" :style="{ background: getColor(d.status) }"></span>
                            <span>{{ d.label }} ({{ d.count }})</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- BOTTOM ROW -->
            <div class="bottom-row">

                <!-- ORDERS TABLE -->
                <div class="card orders-card">
                    <div class="section-header">
                        <span class="section-title">Đơn hàng mới nhất</span>
                        <router-link to="/admin/orders" class="see-all">Xem tất cả</router-link>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>MÃ ĐƠN</th>
                                <th>KHÁCH HÀNG</th>
                                <th>TỔNG CỘNG</th>
                                <th>TRẠNG THÁI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="o in data.don_hang" :key="o.id">
                                <td class="order-id">{{ o.id }}</td>
                                <td class="order-customer">{{ o.khach }}</td>
                                <td class="order-total">{{ o.tong }}</td>
                                <td>
                                    <span class="status-badge" :class="statusClass(o.status)">
                                        {{ o.trangthai }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!data.don_hang?.length">
                                <td colspan="4" class="empty-row">Chưa có đơn hàng</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- TOP PRODUCTS -->
                <div class="card products-card">
                    <div class="section-header">
                        <span class="section-title">Sản phẩm bán chạy <small>({{ periodLabel }})</small></span>
                        <router-link to="/admin/products" class="see-all">Chi tiết</router-link>
                    </div>
                    <div class="product-list">
                        <div class="product-item" v-for="p in data.san_pham" :key="p.id">
                            <img v-if="p.img" :src="p.img" :alt="p.ten" />
                            <div v-else class="img-placeholder">📦</div>
                            <div class="product-info">
                                <b>{{ p.ten }}</b>
                                <span>Đã bán: {{ p.tong_ban }}</span>
                            </div>
                            <div class="product-right">
                                <b class="product-price">{{ p.gia }}</b>
                            </div>
                        </div>
                        <div v-if="!data.san_pham_ban_chay?.length" class="empty-row">
                            Chưa có dữ liệu trong kỳ này
                        </div>
                    </div>
                </div>

            </div>

        </template>
    </div>
</template>

<style scoped>
* {
    box-sizing: border-box;
}

.page {
    background: #f5f7fb;
    min-height: 100vh;
    font-family: sans-serif;
    padding: 0 0 40px;
}

/* TOPBAR */
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 28px 16px;
    background: #f5f7fb;
}

.topbar-left h2 {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 3px;
}

.topbar-left p {
    font-size: 12px;
    color: #94a3b8;
    margin: 0;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.search-box {
    position: relative;
}

.search-box svg {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 13px;
    height: 13px;
    color: #94a3b8;
    pointer-events: none;
}

.search-box input {
    padding: 8px 14px 8px 30px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size: 12px;
    color: #0f172a;
    outline: none;
    background: white;
    width: 200px;
}

.search-box input:focus {
    border-color: #2563eb;
}

.icon-btn {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
}

.icon-btn:hover {
    background: #e2e8f0;
}

.btn-home {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
    background: white;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-home svg {
    width: 14px;
    height: 14px;
}

.btn-home:hover {
    border-color: #2563eb;
    color: #2563eb;
    background: #f0f6ff;
}

/* LOADING */
.loading-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    height: 300px;
    color: #94a3b8;
    font-size: 14px;
}

.spinner {
    width: 24px;
    height: 24px;
    border: 3px solid #e2e8f0;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* PERIOD BAR */
.period-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 28px 14px;
}

.period-bar-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}

.period-tabs {
    display: flex;
    gap: 4px;
    background: #e9eef5;
    border-radius: 8px;
    padding: 3px;
}

.period-tab {
    padding: 5px 14px;
    border-radius: 6px;
    border: none;
    background: transparent;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.18s;
}

.period-tab.active {
    background: white;
    color: #2563eb;
    box-shadow: 0 1px 4px #0001;
}

/* STATS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 0 28px 16px;
}

.stat-card {
    background: white;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
    padding: 16px 18px;
}

.stat-top {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 10px;
}

.stat-icon-wrap {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.stat-label {
    font-size: 11px;
    color: #94a3b8;
    margin: 0 0 4px;
}

.stat-value {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
}

/* CHARTS ROW */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 12px;
    padding: 0 28px 16px;
}

.card {
    background: white;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
    padding: 18px 20px;
}

/* BAR CHART */
.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.chart-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.bar-chart {
    height: 160px;
    display: flex;
    align-items: flex-end;
}

.bars {
    display: flex;
    gap: 8px;
    align-items: flex-end;
    width: 100%;
    height: 100%;
}

.bar-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    height: 100%;
    justify-content: flex-end;
    gap: 6px;
}

.bar-wrap {
    position: relative;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    flex: 1;
}

.bar-fill {
    width: 80%;
    border-radius: 5px 5px 0 0;
    transition: height 0.4s ease;
    min-height: 4px;
}

.bar-label {
    font-size: 9px;
    color: #94a3b8;
    font-weight: 600;
    letter-spacing: 0.03em;
    white-space: nowrap;
}

.bar-tooltip {
    position: absolute;
    top: -28px;
    left: 50%;
    transform: translateX(-50%);
    background: #0f172a;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 7px;
    border-radius: 5px;
    white-space: nowrap;
}

.bar-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #0f172a;
}

.empty-chart {
    color: #94a3b8;
    font-size: 13px;
    margin: auto;
}

/* DONUT */
.donut-card {
    display: flex;
    flex-direction: column;
}

.donut-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 14px;
}

.donut-svg {
    width: 130px;
    height: 130px;
}

.donut-legend {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #475569;
}

.legend-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* BOTTOM ROW */
.bottom-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    padding: 0 28px;
}

/* ORDERS TABLE */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.section-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.section-title small {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 400;
}

.see-all {
    font-size: 12px;
    font-weight: 600;
    color: #2563eb;
    text-decoration: none;
}

.see-all:hover {
    text-decoration: underline;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead th {
    padding: 8px 10px;
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    text-align: left;
    letter-spacing: 0.06em;
    border-bottom: 1px solid #f1f5f9;
}

tbody tr {
    border-bottom: 1px solid #f8fafc;
    transition: background 0.15s;
}

tbody tr:last-child {
    border-bottom: none;
}

tbody tr:hover {
    background: #fafbff;
}

tbody td {
    padding: 12px 10px;
    font-size: 12px;
    vertical-align: middle;
}

.order-id {
    color: #2563eb;
    font-weight: 700;
    font-size: 12px;
}

.order-customer {
    color: #0f172a;
    font-weight: 500;
}

.order-total {
    color: #0f172a;
    font-weight: 700;
}

.empty-row {
    color: #94a3b8;
    font-size: 12px;
    text-align: center;
    padding: 20px !important;
}

.status-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 9px;
    border-radius: 20px;
}

.status-badge.ok {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.confirmed {
    background: #d1fae5;
    color: #059669;
}

.status-badge.warn {
    background: #fef9c3;
    color: #a16207;
}

.status-badge.info {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-badge.out {
    background: #fee2e2;
    color: #dc2626;
}

/* PRODUCTS */
.product-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-radius: 10px;
    transition: background 0.15s;
}

.product-item:hover {
    background: #f8fafc;
}

.product-item img {
    width: 46px;
    height: 46px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}

.img-placeholder {
    width: 46px;
    height: 46px;
    border-radius: 8px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.product-info {
    flex: 1;
}

.product-info b {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 2px;
}

.product-info span {
    font-size: 11px;
    color: #94a3b8;
}

.product-right {
    text-align: right;
    flex-shrink: 0;
}

.product-price {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .charts-row {
        grid-template-columns: 1fr;
    }

    .bottom-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
    }

    .stats-grid,
    .charts-row,
    .bottom-row,
    .period-bar {
        padding-left: 16px;
        padding-right: 16px;
    }
}
</style>