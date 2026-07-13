<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { BarChart3, DollarSign, Package, PackageCheck, UserPlus, Users } from 'lucide-vue-next'
import api from '@/services/api'
import echo from '@/services/echo'

// State
const period = ref('all')          // all | week | month | year
const DASHBOARD_CACHE_PREFIX = 'nextgen_admin_dashboard_'
const DASHBOARD_CACHE_TTL_MS = 5 * 60 * 1000

const statusLabels = [
    ['pending', 'Chờ xác nhận'],
    ['confirmed', 'Đã xác nhận'],
    ['shipping', 'Đang giao'],
    ['done', 'Hoàn thành'],
    ['cancelled', 'Hủy đơn'],
    ['refund_pending', 'Yêu cầu hoàn trả'],
    ['refund_pickup', 'Chờ lấy hàng hoàn'],
    ['refund_delivering', 'Đang giao hoàn'],
    ['refund_received', 'Đã nhận hoàn'],
    ['refunded', 'Đã hoàn tiền'],
]

const createDashboardShell = (selectedPeriod = 'all') => ({
    period: selectedPeriod,
    doanh_thu: '0đ',
    khach_hang: 0,
    bien_the: 0,
    trang_thai: statusLabels.map(([status, label]) => ({ status, label, count: 0, pct: 0 })),
    bieu_do: [],
    bieu_do_khach_hang: [],
    bieu_do_san_pham: [],
    don_hang: [],
    san_pham: [],
})

const getDashboardCacheKey = (selectedPeriod) => `${DASHBOARD_CACHE_PREFIX}${selectedPeriod}`

const readDashboardCache = (selectedPeriod) => {
    try {
        const raw = localStorage.getItem(getDashboardCacheKey(selectedPeriod))
        if (!raw) return null
        const cached = JSON.parse(raw)
        if (!cached?.data || Date.now() - cached.cachedAt > DASHBOARD_CACHE_TTL_MS) return null
        return cached.data
    } catch (_) {
        return null
    }
}

const writeDashboardCache = (selectedPeriod, payload) => {
    try {
        localStorage.setItem(getDashboardCacheKey(selectedPeriod), JSON.stringify({
            cachedAt: Date.now(),
            data: payload,
        }))
    } catch (_) {
        // Dashboard cache is a speed boost only.
    }
}

const loading = ref(false)
const data = ref(readDashboardCache(period.value) || createDashboardShell(period.value))
const errorMessage = ref('')
const searchQuery = ref('')
const hoveredStatus = ref(null) // Để quản lý trạng thái đang hover
const hoveredChartPoint = ref(null)
const chartTab = ref('sales')   // sales | customers | products

const today = new Date().toLocaleDateString('vi-VN', { day: '2-digit', month: 'long', year: 'numeric' })

// Fetch
async function fetchDashboard() {
    const selectedPeriod = period.value
    const cached = readDashboardCache(selectedPeriod)
    if (cached) {
        data.value = cached
    } else if (!data.value || data.value.period !== selectedPeriod) {
        data.value = createDashboardShell(selectedPeriod)
    }

    loading.value = true
    errorMessage.value = ''
    try {
        const res = await api.get('/admin/dashboard', { 
            params: { period: selectedPeriod },
            cache: false,
        })

        if (period.value !== selectedPeriod) return

        if (!res.data?.data) {
            throw new Error(res.data?.message || 'Máy chủ chưa trả dữ liệu dashboard.')
        }

        data.value = {
            ...createDashboardShell(selectedPeriod),
            ...(res.data.data || {}),
        }
        writeDashboardCache(selectedPeriod, data.value)

    } catch (e) {
        console.error('Dashboard fetch error:', e)
        errorMessage.value = e.response?.data?.message
            || e.message
            || 'Không thể kết nối database để tải dữ liệu dashboard.'
    } finally {
        loading.value = false
    }
}
function getColor(status) {
    return {
        pending: '#facc15',
        confirmed: '#93c5fd',
        shipping: '#60a5fa',
        done: '#2563eb',
        cancelled: '#f87171',
        refund_pending: '#3b82f6',
        refund_pickup: '#fb923c',
        refund_delivering: '#3b82f6',
        refund_received: '#2563eb',
        refunded: '#ec4899'
    }[status] || '#ccc'
}
const isOpenPeriodDropdown = ref(false)
const closePeriodDropdown = (e) => {
    if (!e.target.closest('.chart-period-dropdown')) {
        isOpenPeriodDropdown.value = false
    }
}

onMounted(() => {
    fetchDashboard()
    document.addEventListener('click', closePeriodDropdown)

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
    document.removeEventListener('click', closePeriodDropdown)
})
watch(period, () => {
    fetchDashboard()
})

// Stats cards
const stats = computed(() => {
    if (!data.value) return []
    // Debug: mở DevTools > Console để xem API trả về key nào
    return [
        {
            label: 'Doanh thu tổng',
            value: data.value.doanh_thu ?? '0đ',
            to: '/admin/quan-ly-don-hang',
            hint: 'Xem đơn hàng',
            icon: DollarSign,
            iconBg: 'rgba(255,255,255,.16)',
            cardBg: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
            borderColor: 'transparent',
            labelColor: 'rgba(255,255,255,.88)'
        },
        {
            label: 'Khách hàng',
            value: data.value.khach_hang ?? 0,
            to: '/admin/quan-ly-nguoi-dung',
            hint: 'Xem người dùng',
            icon: Users,
            iconBg: 'rgba(255,255,255,.16)',
            cardBg: 'linear-gradient(135deg, #c2410c 0%, #f97316 100%)',
            borderColor: 'transparent',
            labelColor: 'rgba(255,255,255,.88)'
        },
        {
            label: 'Sản phẩm kho',
            value: data.value.bien_the ?? 0,
            to: '/admin/quan-ly-san-pham',
            hint: 'Xem sản phẩm',
            icon: Package,
            iconBg: 'rgba(255,255,255,.16)',
            cardBg: 'linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%)',
            borderColor: 'transparent',
            labelColor: 'rgba(255,255,255,.88)'
        },
    ]
})

// Donut chart helpers
const cx = 60, cy = 60, r = 46
const circumference = 2 * Math.PI * r

const REFUND_STATUSES = ['refund_pending', 'refund_pickup', 'refund_delivering', 'refund_received', 'refunded']

const normalStatusesData = computed(() => {
    if (!data.value?.trang_thai) return []
    return data.value.trang_thai.filter(t => !REFUND_STATUSES.includes(t.status))
})

const refundStatusesData = computed(() => {
    if (!data.value?.trang_thai) return []
    return data.value.trang_thai.filter(t => REFUND_STATUSES.includes(t.status))
})

const normalSegments = computed(() => {
    const list = normalStatusesData.value
    const total = list.reduce((s, d) => s + d.count, 0) || 1
    let offset = 0
    return list.map(d => {
        const dash = (d.count / total) * circumference
        const gap = circumference - dash
        const seg = { ...d, dash, gap, offset, color: getColor(d.status) }
        offset += dash
        return seg
    })
})

const refundSegments = computed(() => {
    const list = refundStatusesData.value
    const total = list.reduce((s, d) => s + d.count, 0) || 1
    let offset = 0
    return list.map(d => {
        const dash = (d.count / total) * circumference
        const gap = circumference - dash
        const seg = { ...d, dash, gap, offset, color: getColor(d.status) }
        offset += dash
        return seg
    })
})

const normalCenterStat = computed(() => {
    const statusToShow = (hoveredStatus.value && !REFUND_STATUSES.includes(hoveredStatus.value)) 
        ? hoveredStatus.value 
        : 'done'
    const found = normalStatusesData.value.find(t => t.status === statusToShow)
    const list = normalStatusesData.value
    const total = list.reduce((s, d) => s + d.count, 0) || 0
    const pct = total > 0 ? Math.round(((found?.count ?? 0) / total) * 100) : 0
    return {
        pct,
        label: found?.label?.toUpperCase() ?? 'HOÀN THÀNH'
    }
})

const refundCenterStat = computed(() => {
    const statusToShow = (hoveredStatus.value && REFUND_STATUSES.includes(hoveredStatus.value)) 
        ? hoveredStatus.value 
        : 'refunded'
    const found = refundStatusesData.value.find(t => t.status === statusToShow)
    const list = refundStatusesData.value
    const total = list.reduce((s, d) => s + d.count, 0) || 0
    const pct = total > 0 ? Math.round(((found?.count ?? 0) / total) * 100) : 0
    return {
        pct,
        label: found?.label?.toUpperCase() ?? 'ĐÃ HOÀN TIỀN'
    }
})

// Bar chart helpers
const formatChartAxisLabel = (rawLabel) => {
    const label = String(rawLabel ?? '').trim()
    if (!label) return ''

    const dateMatch = label.match(/^(\d{4})[-/](\d{1,2})(?:[-/](\d{1,2}))?/)
    if (dateMatch) {
        const [, year, month, day] = dateMatch
        if (day) return `${String(day).padStart(2, '0')}/${String(month).padStart(2, '0')}`
        return `T${Number(month)}/${String(year).slice(-2)}`
    }

    return label.length > 10 ? `${label.slice(0, 10)}...` : label
}

const shouldShowChartAxisLabel = (index, total) => {
    return false
}

const withChartAxisLabel = (item, index, total) => ({
    ...item,
    axisLabel: formatChartAxisLabel(item.label),
    showAxisLabel: shouldShowChartAxisLabel(index, total),
})

const setChartHover = (chart, point) => {
    hoveredChartPoint.value = { chart, ...point }
}

const clearChartHover = () => {
    hoveredChartPoint.value = null
}

const activeChartPoint = (chart) => hoveredChartPoint.value?.chart === chart ? hoveredChartPoint.value : null

const chartTooltipX = (chart, point) => {
    if (!chart || !point) return 0
    return Math.min(Math.max(point.x, chart.left + 72), chart.width - 72)
}

const chartTooltipY = (chart, point, key) => {
    if (!chart || !point) return 0
    return Math.max(chart.top + 22, (point[key] ?? chart.top) - 28)
}

const barChartData = computed(() => {
    if (!data.value?.bieu_do?.length) return []
    const maxVal = Math.max(...data.value.bieu_do.map(d => d.total), 1)
    const arr = data.value.bieu_do
    const maxIdx = arr.reduce((mi, d, i) => d.total > arr[mi].total ? i : mi, 0)
    return arr.map((d, i) => ({
        label: d.label,
        val: Math.round((d.total / maxVal) * 95) + 5,   // 5-100 %
        total: d.total,
        highlight: i === maxIdx,
    }))
})

const revenueChart = computed(() => {
    if (!data.value?.bieu_do?.length) return null

    const items = data.value.bieu_do.map((d) => ({
        label: d.label,
        revenue: Number(d.total) || 0,
    }))
    const itemCount = items.length

    const width = 760
    const height = 280
    const left = 75 // Increased from 54 to prevent label clipping
    const right = 55 // Adjusted slightly
    const top = 24
    const bottom = 44
    const innerW = width - left - right
    const innerH = height - top - bottom
    const maxRevenue = Math.max(...items.map((i) => i.revenue), 1)
    const avgTicket = 2000000
    const orders = items.map((i) => Math.max(1, Math.round(i.revenue / avgTicket)))
    const maxOrders = Math.max(...orders, 1)

    // Add horizontal padding inside the chart area so the first and last bars don't touch the axes
    const paddingX = itemCount === 1 ? 0 : 40
    const plotW = innerW - 2 * paddingX

    const colWidth = itemCount === 1
        ? 24
        : Math.max(8, Math.min(18, (innerW / itemCount) * 0.48))

    const points = items.map((item, idx) => {
        const x = itemCount === 1
            ? left + innerW / 2
            : left + paddingX + ((plotW * idx) / Math.max(itemCount - 1, 1))
        const yRevenue = top + innerH - (item.revenue / maxRevenue) * innerH
        const yOrders = top + innerH - (orders[idx] / maxOrders) * innerH
        return withChartAxisLabel({ ...item, orders: orders[idx], x, yRevenue, yOrders }, idx, itemCount)
    })

    const line = points.map((p) => `${p.x},${p.yOrders}`).join(' ')
    const yTicks = [0, 0.25, 0.5, 0.75, 1].map((ratio) => ({
        y: top + innerH - ratio * innerH,
        revenueValue: Math.round(maxRevenue * ratio),
        orderValue: Math.round(maxOrders * ratio),
    }))

    return { width, height, left, right, top, innerW, innerH, colWidth, points, line, yTicks }
})

const customerChart = computed(() => {
    if (!data.value?.bieu_do_khach_hang?.length) return null

    const items = data.value.bieu_do_khach_hang.map((d) => ({
        label: d.label,
        total: Number(d.total) || 0,
    }))
    const itemCount = items.length

    const width = 760
    const height = 280
    const left = 75
    const right = 55
    const top = 24
    const bottom = 44
    const innerW = width - left - right
    const innerH = height - top - bottom
    const maxVal = Math.max(...items.map((i) => i.total), 1)

    const paddingX = itemCount === 1 ? 0 : 40
    const plotW = innerW - 2 * paddingX

    const points = items.map((item, idx) => {
        const x = itemCount === 1
            ? left + innerW / 2
            : left + paddingX + ((plotW * idx) / Math.max(itemCount - 1, 1))
        const y = top + innerH - (item.total / maxVal) * innerH
        return withChartAxisLabel({ ...item, x, y }, idx, itemCount)
    })

    const linePath = points.map((p, idx) => `${idx === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ')
    const areaPath = points.length ? `${linePath} L ${points[points.length - 1].x} ${top + innerH} L ${points[0].x} ${top + innerH} Z` : ''

    const yTicks = [0, 0.25, 0.5, 0.75, 1].map((ratio) => ({
        y: top + innerH - ratio * innerH,
        val: Math.round(maxVal * ratio),
    }))

    return { width, height, left, right, top, innerW, innerH, points, linePath, areaPath, yTicks }
})

const productChart = computed(() => {
    if (!data.value?.bieu_do_san_pham?.length) return null

    const items = data.value.bieu_do_san_pham.map((d) => ({
        label: d.label,
        total: Number(d.total) || 0,
    }))
    const itemCount = items.length

    const width = 760
    const height = 280
    const left = 75
    const right = 55
    const top = 24
    const bottom = 44
    const innerW = width - left - right
    const innerH = height - top - bottom
    const maxVal = Math.max(...items.map((i) => i.total), 1)

    const paddingX = itemCount === 1 ? 0 : 40
    const plotW = innerW - 2 * paddingX

    const colWidth = itemCount === 1
        ? 24
        : Math.max(8, Math.min(18, (innerW / itemCount) * 0.48))

    const points = items.map((item, idx) => {
        const x = itemCount === 1
            ? left + innerW / 2
            : left + paddingX + ((plotW * idx) / Math.max(itemCount - 1, 1))
        const y = top + innerH - (item.total / maxVal) * innerH
        return withChartAxisLabel({ ...item, x, y }, idx, itemCount)
    })

    const yTicks = [0, 0.25, 0.5, 0.75, 1].map((ratio) => ({
        y: top + innerH - ratio * innerH,
        val: Math.round(maxVal * ratio),
    }))

    return { width, height, left, right, top, innerW, innerH, colWidth, points, yTicks }
})

// Status badge class
function statusClass(s) {
    return { pending: 'warn', confirmed: 'confirmed', shipping: 'info', done: 'ok', cancelled: 'out' }[s] ?? 'warn'
}

// Period label
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
            </div>
        </div>

        <!-- Subtle background loader only, content remains visible while refreshing -->
        <div v-if="loading" class="background-loader-bar"></div>

        <div v-if="errorMessage" class="dashboard-error">
            <div>
                <b>Chưa lấy được dữ liệu database</b>
                <span>{{ errorMessage }}</span>
            </div>
            <button type="button" @click="fetchDashboard">Tải lại</button>
        </div>

        <template v-if="data">

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
                <router-link class="stat-card stat-card-link" v-for="s in stats" :key="s.label" :to="s.to"
                    :style="{ background: s.cardBg, borderColor: s.borderColor }"
                    :aria-label="`${s.label}: ${s.value}. ${s.hint}`">
                    <div class="stat-top">
                        <div class="stat-icon-wrap" :style="{ background: s.iconBg }">
                            <component :is="s.icon" aria-hidden="true" />
                        </div>
                    </div>
                    <p class="stat-label" :style="{ color: s.labelColor }">{{ s.label }}</p>
                    <b class="stat-value">{{ s.value }}</b>
                    <span class="stat-card-action">{{ s.hint }}</span>
                </router-link>
            </div>

            <!-- CHARTS ROW -->
            <div class="charts-row">

                <!-- BAR CHART -->
                <div class="card chart-card">
                    <div class="chart-header">
                        <div class="chart-tabs-nav">
                            <button :class="['chart-nav-btn', { active: chartTab === 'sales' }]" @click="chartTab = 'sales'">
                                <BarChart3 aria-hidden="true" />
                                Doanh thu & Đơn hàng
                            </button>
                            <button :class="['chart-nav-btn', { active: chartTab === 'customers' }]" @click="chartTab = 'customers'">
                                <UserPlus aria-hidden="true" />
                                Khách hàng mới
                            </button>
                            <button :class="['chart-nav-btn', { active: chartTab === 'products' }]" @click="chartTab = 'products'">
                                <PackageCheck aria-hidden="true" />
                                Lượng sản phẩm bán
                            </button>
                        </div>
                        <div class="custom-dropdown chart-period-dropdown">
                            <div class="dropdown-trigger chart-period-trigger" @click.stop="isOpenPeriodDropdown = !isOpenPeriodDropdown">
                                <span>{{ periodLabel }}</span>
                                <svg class="chevron" :class="{ open: isOpenPeriodDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                            <transition name="fade-slide">
                                <ul v-if="isOpenPeriodDropdown" class="dropdown-menu">
                                    <li :class="{ active: period === 'all' }" @click="period = 'all'; isOpenPeriodDropdown = false">
                                        Tất cả thời gian
                                    </li>
                                    <li :class="{ active: period === 'week' }" @click="period = 'week'; isOpenPeriodDropdown = false">
                                        Tuần này
                                    </li>
                                    <li :class="{ active: period === 'month' }" @click="period = 'month'; isOpenPeriodDropdown = false">
                                        Tháng này
                                    </li>
                                    <li :class="{ active: period === 'year' }" @click="period = 'year'; isOpenPeriodDropdown = false">
                                        Năm nay
                                    </li>
                                </ul>
                            </transition>
                        </div>
                    </div>

                    <div class="bar-chart">
                        <!-- CHART 1: SALES & ORDERS -->
                        <div v-if="chartTab === 'sales' && revenueChart" class="revenue-chart-wrap">
                            <div class="revenue-legend">
                                <span><i class="dot revenue"></i>Revenue</span>
                                <span><i class="dot orders"></i>Orders</span>
                            </div>
                            <svg class="revenue-svg" :viewBox="`0 0 ${revenueChart.width} ${revenueChart.height}`" preserveAspectRatio="none">
                                <defs>
                                    <!-- Gradient for Revenue Bars -->
                                    <linearGradient id="revenueBarGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#2563eb" />
                                        <stop offset="100%" stop-color="#93c5fd" />
                                    </linearGradient>
                                    <!-- Soft Glow Filter for Line Chart -->
                                    <filter id="emeraldGlow" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="4" stdDeviation="4" flood-color="#2563eb" flood-opacity="0.3" />
                                    </filter>
                                </defs>

                                <line v-for="tick in revenueChart.yTicks" :key="`grid-${tick.y}`" :x1="revenueChart.left"
                                    :x2="revenueChart.left + revenueChart.innerW" :y1="tick.y" :y2="tick.y" class="revenue-grid" />
                                
                                <!-- Revenue Bars with Gradients and Tooltips -->
                                <rect v-for="p in revenueChart.points" :key="`bar-${p.label}`"
                                    :x="p.x - revenueChart.colWidth / 2"
                                    :y="p.yRevenue"
                                    :width="revenueChart.colWidth"
                                    :height="revenueChart.top + revenueChart.innerH - p.yRevenue"
                                    class="revenue-bar"
                                    @mouseenter="setChartHover('sales', p)"
                                    @mouseleave="clearChartHover">
                                    <title>Doanh thu {{ p.label }}: {{ new Intl.NumberFormat('vi-VN').format(p.revenue) }}đ</title>
                                </rect>

                                <!-- Orders Line and Points with Glow and Tooltips -->
                                <polyline :points="revenueChart.line" class="orders-line" />
                                <circle v-for="p in revenueChart.points" :key="`pt-${p.label}`" 
                                    :cx="p.x" :cy="p.yOrders" r="5" 
                                    class="orders-point"
                                    @mouseenter="setChartHover('sales', p)"
                                    @mouseleave="clearChartHover">
                                    <title>Đơn hàng {{ p.label }}: {{ p.orders }} đơn</title>
                                </circle>

                                <g v-if="activeChartPoint('sales')" class="chart-hover-tooltip"
                                    :transform="`translate(${chartTooltipX(revenueChart, activeChartPoint('sales')) - 58} ${chartTooltipY(revenueChart, activeChartPoint('sales'), 'yRevenue') - 34})`">
                                    <rect width="116" height="48" rx="8" />
                                    <text x="58" y="18" text-anchor="middle" class="tooltip-date">{{ activeChartPoint('sales').axisLabel }}</text>
                                    <text x="58" y="35" text-anchor="middle" class="tooltip-value">
                                        {{ new Intl.NumberFormat('vi-VN').format(activeChartPoint('sales').revenue) }}đ
                                    </text>
                                </g>

                                <!-- Left Y-Axis: Revenue (formatted in Million 'M' or Thousand 'k') -->
                                <text v-for="tick in revenueChart.yTicks" :key="`left-${tick.y}`" :x="revenueChart.left - 12" :y="tick.y + 4" text-anchor="end" class="axis-label">
                                    {{ tick.revenueValue === 0 ? '0đ' : (tick.revenueValue >= 1000000 ? (tick.revenueValue / 1000000).toFixed(1) + 'Mđ' : (tick.revenueValue / 1000).toFixed(0) + 'kđ') }}
                                </text>

                                <!-- Right Y-Axis: Orders Count -->
                                <text v-for="tick in revenueChart.yTicks" :key="`right-${tick.y}`" :x="revenueChart.left + revenueChart.innerW + 12" :y="tick.y + 4" text-anchor="start" class="axis-label">
                                    {{ tick.orderValue }} đơn
                                </text>

                                <!-- X-Axis Labels: Aligned perfectly inside SVG -->
                                <text v-for="p in revenueChart.points" v-show="p.showAxisLabel" :key="`x-lbl-${p.label}`"
                                    :x="p.x" :y="revenueChart.height - 26" text-anchor="end"
                                    :transform="`rotate(-58 ${p.x} ${revenueChart.height - 26})`"
                                    class="axis-label x-axis-label">
                                    <title>{{ p.label }}</title>
                                    {{ p.axisLabel }}
                                </text>
                            </svg>
                        </div>

                        <!-- CHART 2: NEW CUSTOMERS -->
                        <div v-else-if="chartTab === 'customers' && customerChart" class="revenue-chart-wrap">
                            <div class="revenue-legend">
                                <span><i class="dot customer-legend-dot"></i>Khách hàng mới đăng ký</span>
                            </div>
                            <svg class="revenue-svg" :viewBox="`0 0 ${customerChart.width} ${customerChart.height}`" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="customerAreaGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#f97316" stop-opacity="0.4" />
                                        <stop offset="100%" stop-color="#f97316" stop-opacity="0.0" />
                                    </linearGradient>
                                    <filter id="customerGlow" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="4" stdDeviation="4" flood-color="#f97316" flood-opacity="0.3" />
                                    </filter>
                                </defs>
                                <line v-for="tick in customerChart.yTicks" :key="`grid-cust-${tick.y}`" :x1="customerChart.left"
                                    :x2="customerChart.left + customerChart.innerW" :y1="tick.y" :y2="tick.y" class="revenue-grid" />
                                
                                <!-- Area under the curve -->
                                <path :d="customerChart.areaPath" fill="url(#customerAreaGradient)" />
                                <!-- Line chart -->
                                <path :d="customerChart.linePath" fill="none" stroke="#f97316" stroke-width="3.5" stroke-linecap="round" filter="url(#customerGlow)" />
                                
                                <!-- Customer points -->
                                <circle v-for="p in customerChart.points" :key="`pt-cust-${p.label}`" 
                                    :cx="p.x" :cy="p.y" r="5" class="customer-point"
                                    @mouseenter="setChartHover('customers', p)"
                                    @mouseleave="clearChartHover">
                                    <title>Khách hàng mới {{ p.label }}: {{ p.total }} người</title>
                                </circle>

                                <g v-if="activeChartPoint('customers')" class="chart-hover-tooltip"
                                    :transform="`translate(${chartTooltipX(customerChart, activeChartPoint('customers')) - 58} ${chartTooltipY(customerChart, activeChartPoint('customers'), 'y') - 34})`">
                                    <rect width="116" height="48" rx="8" />
                                    <text x="58" y="18" text-anchor="middle" class="tooltip-date">{{ activeChartPoint('customers').axisLabel }}</text>
                                    <text x="58" y="35" text-anchor="middle" class="tooltip-value">
                                        {{ activeChartPoint('customers').total }} người
                                    </text>
                                </g>

                                <!-- Left Axis: Customer Count -->
                                <text v-for="tick in customerChart.yTicks" :key="`left-cust-${tick.y}`" :x="customerChart.left - 12" :y="tick.y + 4" text-anchor="end" class="axis-label">
                                    {{ tick.val }} người
                                </text>

                                <!-- X-Axis Labels -->
                                <text v-for="p in customerChart.points" v-show="p.showAxisLabel" :key="`x-lbl-cust-${p.label}`"
                                    :x="p.x" :y="customerChart.height - 26" text-anchor="end"
                                    :transform="`rotate(-58 ${p.x} ${customerChart.height - 26})`"
                                    class="axis-label x-axis-label">
                                    <title>{{ p.label }}</title>
                                    {{ p.axisLabel }}
                                </text>
                            </svg>
                        </div>

                        <!-- CHART 3: PRODUCT SALES -->
                        <div v-else-if="chartTab === 'products' && productChart" class="revenue-chart-wrap">
                            <div class="revenue-legend">
                                <span><i class="dot product-legend-dot"></i>Số sản phẩm bán ra</span>
                            </div>
                            <svg class="revenue-svg" :viewBox="`0 0 ${productChart.width} ${productChart.height}`" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="productBarGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#3b82f6" />
                                        <stop offset="100%" stop-color="#1d4ed8" />
                                    </linearGradient>
                                </defs>
                                <line v-for="tick in productChart.yTicks" :key="`grid-prod-${tick.y}`" :x1="productChart.left"
                                    :x2="productChart.left + productChart.innerW" :y1="tick.y" :y2="tick.y" class="revenue-grid" />
                                
                                <!-- Product Bars -->
                                <rect v-for="p in productChart.points" :key="`bar-prod-${p.label}`"
                                    :x="p.x - productChart.colWidth / 2" :y="p.y"
                                    :width="productChart.colWidth" :height="productChart.top + productChart.innerH - p.y"
                                    class="product-bar"
                                    @mouseenter="setChartHover('products', p)"
                                    @mouseleave="clearChartHover">
                                    <title>Sản phẩm bán {{ p.label }}: {{ p.total }} cái</title>
                                </rect>

                                <g v-if="activeChartPoint('products')" class="chart-hover-tooltip"
                                    :transform="`translate(${chartTooltipX(productChart, activeChartPoint('products')) - 58} ${chartTooltipY(productChart, activeChartPoint('products'), 'y') - 34})`">
                                    <rect width="116" height="48" rx="8" />
                                    <text x="58" y="18" text-anchor="middle" class="tooltip-date">{{ activeChartPoint('products').axisLabel }}</text>
                                    <text x="58" y="35" text-anchor="middle" class="tooltip-value">
                                        {{ activeChartPoint('products').total }} cái
                                    </text>
                                </g>

                                <!-- Left Axis: Product Count -->
                                <text v-for="tick in productChart.yTicks" :key="`left-prod-${tick.y}`" :x="productChart.left - 12" :y="tick.y + 4" text-anchor="end" class="axis-label">
                                    {{ tick.val }} cái
                                </text>

                                <!-- X-Axis Labels -->
                                <text v-for="p in productChart.points" v-show="p.showAxisLabel" :key="`x-lbl-prod-${p.label}`"
                                    :x="p.x" :y="productChart.height - 26" text-anchor="end"
                                    :transform="`rotate(-58 ${p.x} ${productChart.height - 26})`"
                                    class="axis-label x-axis-label">
                                    <title>{{ p.label }}</title>
                                    {{ p.axisLabel }}
                                </text>
                            </svg>
                        </div>

                        <div v-else class="empty-chart">Chưa có dữ liệu trong kỳ này</div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="right-col">
                    <!-- DONUT CHART 1: Trạng thái đơn hàng -->
                    <div class="card donut-card">
                        <div class="chart-title" style="margin-bottom: 10px;">Trạng thái đơn hàng</div>
                        <div class="donut-wrap">
                            <svg viewBox="0 0 120 120" class="donut-svg">
                                <circle cx="60" cy="60" r="46" fill="none" stroke="#f1f5f9" stroke-width="14" />
                                <circle v-for="seg in normalSegments" :key="seg.status" cx="60" cy="60" r="46" fill="none"
                                    :stroke="seg.color" stroke-width="14" :stroke-dasharray="`${seg.dash} ${seg.gap}`"
                                    :stroke-dashoffset="-seg.offset" stroke-linecap="butt"
                                    @mouseenter="hoveredStatus = seg.status"
                                    @mouseleave="hoveredStatus = null"
                                    style="transform: rotate(-90deg); transform-origin: 50% 50%; cursor: pointer; transition: stroke-width 0.2s;" 
                                    :stroke-width="hoveredStatus === seg.status ? 18 : 14" />
                                <text x="60" y="55" text-anchor="middle" font-size="16" font-weight="800" fill="#0f172a">
                                    {{ normalCenterStat.pct }}%
                                </text>
                                <text x="60" y="70" text-anchor="middle" font-size="7" fill="#94a3b8" font-weight="700">
                                    {{ normalCenterStat.label }}
                                </text>
                            </svg>
                        </div>
                        <div class="donut-legend">
                            <div class="legend-item" v-for="d in normalStatusesData" :key="d.status">
                                <span class="legend-dot" :style="{ background: getColor(d.status) }"></span>
                                <span>{{ d.label }} ({{ d.count }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- DONUT CHART 2: Trạng thái hoàn trả -->
                    <div class="card donut-card">
                        <div class="chart-title" style="margin-bottom: 10px;">Trạng thái hoàn trả</div>
                        <div class="donut-wrap">
                            <svg viewBox="0 0 120 120" class="donut-svg">
                                <circle cx="60" cy="60" r="46" fill="none" stroke="#f1f5f9" stroke-width="14" />
                                <circle v-for="seg in refundSegments" :key="seg.status" cx="60" cy="60" r="46" fill="none"
                                    :stroke="seg.color" stroke-width="14" :stroke-dasharray="`${seg.dash} ${seg.gap}`"
                                    :stroke-dashoffset="-seg.offset" stroke-linecap="butt"
                                    @mouseenter="hoveredStatus = seg.status"
                                    @mouseleave="hoveredStatus = null"
                                    style="transform: rotate(-90deg); transform-origin: 50% 50%; cursor: pointer; transition: stroke-width 0.2s;" 
                                    :stroke-width="hoveredStatus === seg.status ? 18 : 14" />
                                <text x="60" y="55" text-anchor="middle" font-size="16" font-weight="800" fill="#0f172a">
                                    {{ refundCenterStat.pct }}%
                                </text>
                                <text x="60" y="70" text-anchor="middle" font-size="7" fill="#94a3b8" font-weight="700">
                                    {{ refundCenterStat.label }}
                                </text>
                            </svg>
                        </div>
                        <div class="donut-legend">
                            <div class="legend-item" v-for="d in refundStatusesData" :key="d.status">
                                <span class="legend-dot" :style="{ background: getColor(d.status) }"></span>
                                <span>{{ d.label }} ({{ d.count }})</span>
                            </div>
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
                        <router-link to="/admin/quan-ly-don-hang" class="see-all">Xem tất cả</router-link>
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
                        <router-link to="/admin/quan-ly-san-pham" class="see-all">Chi tiết</router-link>
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
                        <div v-if="!data.san_pham?.length" class="empty-row">
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
    position: relative;
}

.background-loader-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #2563eb, #3b82f6, #2563eb);
    background-size: 200% 100%;
    animation: background-slide 1.2s infinite linear;
    z-index: 99999;
}

@keyframes background-slide {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
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

.dashboard-error {
    margin: 0 28px 16px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #991b1b;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.dashboard-error div {
    display: grid;
    gap: 3px;
}

.dashboard-error b {
    font-size: 13px;
}

.dashboard-error span {
    font-size: 12px;
    color: #b91c1c;
}

.dashboard-error button {
    border: none;
    border-radius: 999px;
    background: #dc2626;
    color: white;
    font-size: 12px;
    font-weight: 700;
    padding: 8px 12px;
    cursor: pointer;
    flex-shrink: 0;
}

.dashboard-error button:hover {
    background: #b91c1c;
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
    grid-template-columns: repeat(3, minmax(220px, 1fr));
    gap: 20px;
    padding: 0 32px 20px;
}

.stat-card {
    min-height: 136px;
    border-radius: 16px;
    border: 1px solid transparent;
    padding: 26px 28px;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.14);
    color: #fff;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.stat-card-link {
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
}

.stat-card-link:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 34px rgba(15, 23, 42, 0.2);
    filter: saturate(1.05);
}

.stat-card-link:focus-visible {
    outline: 3px solid rgba(37, 99, 235, 0.28);
    outline-offset: 3px;
}

.stat-card::after {
    content: '';
    position: absolute;
    width: 150px;
    height: 150px;
    right: -28px;
    top: -54px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.13);
}

.stat-top {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 14px;
}

.stat-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    backdrop-filter: blur(6px);
}

.stat-icon-wrap svg {
    width: 24px;
    height: 24px;
    stroke-width: 2.2;
}

.stat-label {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.03em;
    margin: 0 0 18px;
    text-transform: uppercase;
}

.stat-value {
    font-size: 34px;
    line-height: 1;
    font-weight: 800;
    color: #fff;
}

.stat-card-action {
    position: absolute;
    right: 22px;
    bottom: 18px;
    color: rgba(255, 255, 255, 0.82);
    font-size: 12px;
    font-weight: 800;
    opacity: 0;
    transform: translateX(-4px);
    transition: opacity 0.18s ease, transform 0.18s ease;
    z-index: 1;
}

.stat-card-link:hover .stat-card-action,
.stat-card-link:focus-visible .stat-card-action {
    opacity: 1;
    transform: translateX(0);
}

/* CHARTS ROW */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 12px;
    padding: 0 28px 16px;
    align-items: stretch;
}

.right-col {
    display: flex;
    flex-direction: column;
    gap: 12px;
    height: 100%;
}

.card {
    background: white;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
    padding: 18px 20px;
}

.chart-card {
    align-self: stretch;
    display: flex;
    flex-direction: column;
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

.chart-period {
    font-size: 12px;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 6px 10px;
    background: #fff;
}

.bar-chart {
    flex: 1;
    min-height: 420px;
    display: flex;
    align-items: stretch;
    padding: 8px 2px 0;
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

.revenue-chart-wrap {
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: 10px;
    width: 100%;
    min-height: 0;
}

.revenue-svg {
    width: 100%;
    flex: 1;
    height: 100%;
    min-height: 360px;
}

.revenue-grid {
    stroke: #e2e8f0;
    stroke-width: 1;
    stroke-dasharray: 4 4;
}

.revenue-legend {
    display: flex;
    justify-content: flex-end;
    gap: 14px;
    font-size: 12px;
    color: #334155;
}

.revenue-legend span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.dot.revenue {
    background: #2563eb;
}

.dot.orders {
    background: #2563eb;
}

.revenue-bar {
    fill: url(#revenueBarGradient);
    opacity: 0.88;
    rx: 6px;
    filter: drop-shadow(0 4px 10px rgba(37, 99, 235, 0.15));
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.revenue-bar:hover {
    opacity: 1;
    fill: #2563eb;
    filter: drop-shadow(0 6px 15px rgba(37, 99, 235, 0.35));
}

.orders-line {
    fill: none;
    stroke: #2563eb;
    stroke-width: 3.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: url(#emeraldGlow);
}

.orders-point {
    fill: #ffffff;
    stroke: #2563eb;
    stroke-width: 3;
    r: 5;
    transition: all 0.2s ease;
    cursor: pointer;
}

.orders-point:hover {
    r: 7;
    fill: #2563eb;
    stroke: #ffffff;
}

.axis-label {
    font-size: 11px;
    fill: #64748b;
    font-weight: 600;
}

.x-axis-label {
    fill: #475569;
    font-weight: 700;
}

.chart-hover-tooltip {
    pointer-events: none;
    filter: drop-shadow(0 10px 22px rgba(15, 23, 42, 0.18));
}

.chart-hover-tooltip rect {
    fill: #0f172a;
}

.chart-hover-tooltip .tooltip-date {
    fill: #dbeafe;
    font-size: 11px;
    font-weight: 800;
}

.chart-hover-tooltip .tooltip-value {
    fill: #ffffff;
    font-size: 10px;
    font-weight: 700;
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
    justify-content: space-between;
}

.right-col .donut-card {
    flex: 1;
}

.donut-wrap {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 8px;
}

.donut-svg {
    width: 110px !important;
    height: 110px !important;
    flex-shrink: 0;
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
    color: #2563eb;
}

.status-badge.confirmed {
    background: #d1fae5;
    color: #1D4ED8;
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

/* Navigation tabs inside the chart card */
.chart-tabs-nav {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    padding: 3px;
    border-radius: 8px;
}

.chart-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 6px;
    border: none;
    background: transparent;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.chart-nav-btn svg {
    width: 14px;
    height: 14px;
    stroke-width: 2.2;
}

.chart-nav-btn:hover {
    color: #0f172a;
    background: rgba(255,255,255,0.4);
}

.chart-nav-btn.active {
    background: white;
    color: #2563eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Customer Chart Specific Elements */
.dot.customer-legend-dot {
    background: #f97316;
}

.customer-point {
    fill: #ffffff;
    stroke: #f97316;
    stroke-width: 3;
    r: 5;
    transition: all 0.2s ease;
    cursor: pointer;
}

.customer-point:hover {
    r: 7;
    fill: #f97316;
    stroke: #ffffff;
}

/* Product Chart Specific Elements */
.dot.product-legend-dot {
    background: #3b82f6;
}

.product-bar {
    fill: url(#productBarGradient);
    opacity: 0.88;
    rx: 6px;
    filter: drop-shadow(0 4px 10px rgba(20, 184, 166, 0.15));
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.product-bar:hover {
    opacity: 1;
    fill: #3b82f6;
    filter: drop-shadow(0 6px 15px rgba(20, 184, 166, 0.35));
}

/* Custom Dropdown for Statistical Period */
.chart-period-dropdown {
    position: relative;
    user-select: none;
    min-width: 145px;
}

.chart-period-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 11px;
    color: #475569;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 5px 12px;
    background: #fff;
    cursor: pointer;
    font-weight: 700;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.chart-period-trigger:hover {
    border-color: #2563eb;
    color: #2563eb;
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.06);
}

.chart-period-trigger .chevron {
    width: 12px;
    height: 12px;
    color: #64748b;
    transition: transform 0.2s ease;
}

.chart-period-trigger .chevron.open {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    z-index: 1000;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 5px;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 2px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    min-width: 145px;
}

.dropdown-menu li {
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    border-radius: 6px;
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
    font-weight: 700;
}

/* Transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all .2s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>


