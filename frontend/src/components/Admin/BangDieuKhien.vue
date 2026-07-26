<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import {
    AlertTriangle,
    BarChart3,
    ClipboardList,
    DollarSign,
    Download,
    Package,
    PackageCheck,
    ShoppingCart,
    TrendingUp,
    UserPlus,
    Users,
} from 'lucide-vue-next'
import api from '@/services/api'
import echo from '@/services/echo'

// State
const period = ref('all')          // all | week | month | year
const DASHBOARD_CACHE_PREFIX = 'nextgen_admin_dashboard_v4_'
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
    ton_kho_canh_bao: [],
    don_can_xu_ly: [],
    thanh_toan: [],
    danh_muc_ban_chay: [],
    phan_tich: {
        gia_tri_don_trung_binh: '0đ',
        don_hoan_thanh: 0,
        tong_don: 0,
        ti_le_hoan_thanh: 0,
        doanh_thu: { current: '0đ', previous: '0đ', trend: 0 },
        don_hang: { current: 0, previous: 0, trend: 0 },
        khach_hang: { current: 0, previous: 0, trend: 0 },
    },
    nhan_su_hoat_dong: {
        online: 0,
        idle: 0,
        offline: 0,
        total: 0,
        items: [],
    },
    khach_hang_hoat_dong: {
        online: 0,
        recent: 0,
        visited_today: 0,
    },
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
const hoveredStatus = ref(null) // Quản lý trạng thái đang hover
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
        {
            label: 'Tổng đơn hàng',
            value: normalOrderTotal.value,
            to: '/admin/quan-ly-don-hang',
            hint: 'Xem đơn hàng',
            icon: ShoppingCart,
            iconBg: 'rgba(255,255,255,.16)',
            cardBg: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
            borderColor: 'transparent',
            labelColor: 'rgba(255,255,255,.88)'
        },
    ]
})

const statusCount = (status) => Number(data.value?.trang_thai?.find((item) => item.status === status)?.count || 0)

const normalOrderTotal = computed(() =>
    normalStatusesData.value.reduce((sum, item) => sum + Number(item.count || 0), 0)
)

const pendingOrders = computed(() => statusCount('pending'))
const processingOrders = computed(() => statusCount('confirmed') + statusCount('shipping'))
const completedOrders = computed(() => statusCount('done'))
const cancelledOrders = computed(() => statusCount('cancelled'))
const openRefunds = computed(() =>
    ['refund_pending', 'refund_pickup', 'refund_delivering'].reduce((sum, status) => sum + statusCount(status), 0)
)

const lowStockItems = computed(() => data.value?.ton_kho_canh_bao || [])
const handlingOrders = computed(() => data.value?.don_can_xu_ly || [])
const paymentMethods = computed(() => data.value?.thanh_toan || [])
const topCategories = computed(() => data.value?.danh_muc_ban_chay || [])
const staffActivity = computed(() => data.value?.nhan_su_hoat_dong || createDashboardShell(period.value).nhan_su_hoat_dong)
const activeStaffList = computed(() => staffActivity.value.items || [])
const customerActivity = computed(() => data.value?.khach_hang_hoat_dong || createDashboardShell(period.value).khach_hang_hoat_dong)

const staffStatusClass = (status) => {
    return {
        online: 'online',
        idle: 'idle',
        offline: 'offline',
    }[status] || 'offline'
}

const paymentChartRows = computed(() => {
    const rows = paymentMethods.value.map((item) => ({
        ...item,
        total: Number(item.total || 0),
    }))
    const max = Math.max(...rows.map((item) => item.total), 1)
    return rows.map((item) => ({
        ...item,
        pct: Math.round((item.total / max) * 100),
    }))
})

const categoryChartRows = computed(() => {
    const rows = topCategories.value.map((item) => ({
        ...item,
        total: Number(item.total || 0),
    }))
    const max = Math.max(...rows.map((item) => item.total), 1)
    return rows.map((item) => ({
        ...item,
        pct: Math.round((item.total / max) * 100),
    }))
})

const stockRiskRows = computed(() => lowStockItems.value.map((item) => {
    const quantity = Number(item.soluong || 0)
    const risk = Math.max(8, Math.min(100, Math.round(((5 - Math.min(quantity, 5)) / 5) * 100)))
    return {
        ...item,
        risk,
        tone: quantity <= 0 ? 'danger' : quantity <= 2 ? 'warn' : 'soft',
    }
}))

const orderPipelineRows = computed(() => {
    const rows = normalStatusesData.value.map((item) => ({
        ...item,
        count: Number(item.count || 0),
    }))
    const max = Math.max(...rows.map((item) => item.count), 1)
    return rows.map((item) => ({
        ...item,
        pct: Math.round((item.count / max) * 100),
        color: getColor(item.status),
    }))
})

const paymentDonut = computed(() => {
    const rows = paymentChartRows.value
    const total = rows.reduce((sum, item) => sum + item.total, 0)
    const leader = [...rows].sort((a, b) => b.total - a.total)[0]
    return {
        pct: total > 0 ? Math.round(((leader?.total || 0) / total) * 100) : 0,
        label: leader?.label || 'Chưa có dữ liệu',
    }
})

const categoryDonut = computed(() => {
    const rows = categoryChartRows.value
    const total = rows.reduce((sum, item) => sum + item.total, 0)
    const leader = [...rows].sort((a, b) => b.total - a.total)[0]
    return {
        pct: total > 0 ? Math.round(((leader?.total || 0) / total) * 100) : 0,
        label: leader?.label || 'Chưa có dữ liệu',
    }
})

const stockDonut = computed(() => {
    const rows = stockRiskRows.value
    const average = rows.length
        ? Math.round(rows.reduce((sum, item) => sum + item.risk, 0) / rows.length)
        : 0
    return { pct: average, label: rows.length ? 'Mức cảnh báo' : 'Kho ổn định' }
})

const orderDonut = computed(() => {
    const total = normalOrderTotal.value
    const done = completedOrders.value
    return {
        pct: total > 0 ? Math.round((done / total) * 100) : 0,
        label: 'Đã hoàn thành',
    }
})

const trendClass = (value) => {
    const trend = Number(value || 0)
    if (trend > 0) return 'up'
    if (trend < 0) return 'down'
    return 'flat'
}

const trendText = (value) => {
    const trend = Number(value || 0)
    if (trend > 0) return `+${trend}%`
    if (trend < 0) return `${trend}%`
    return '0%'
}

const numberFromMetric = (value) => {
    if (typeof value === 'number') return value
    if (value === null || value === undefined) return 0

    const normalized = String(value)
        .replace(/[^\d,.-]/g, '')
        .replace(/\./g, '')
        .replace(',', '.')

    const parsed = Number(normalized)
    return Number.isFinite(parsed) ? parsed : 0
}

const calcTrendPercent = (current, previous, fallback = 0) => {
    const fallbackTrend = Number(fallback || 0)
    if (fallbackTrend !== 0) return fallbackTrend

    const currentValue = numberFromMetric(current)
    const previousValue = numberFromMetric(previous)

    if (previousValue <= 0) {
        return currentValue > 0 ? 100 : 0
    }

    return Number((((currentValue - previousValue) / previousValue) * 100).toFixed(1))
}

const analysisCards = computed(() => {
    const insight = data.value?.phan_tich || createDashboardShell(period.value).phan_tich
    return [
        {
            label: 'Doanh thu kỳ này',
            value: insight.doanh_thu?.current || '0đ',
            previous: `Kỳ trước: ${insight.doanh_thu?.previous || '0đ'}`,
            trend: calcTrendPercent(insight.doanh_thu?.current, insight.doanh_thu?.previous, insight.doanh_thu?.trend),
        },
        {
            label: 'Đơn hàng kỳ này',
            value: insight.don_hang?.current || 0,
            previous: `Kỳ trước: ${insight.don_hang?.previous || 0} đơn`,
            trend: calcTrendPercent(insight.don_hang?.current, insight.don_hang?.previous, insight.don_hang?.trend),
        },
        {
            label: 'Khách mới kỳ này',
            value: insight.khach_hang?.current || 0,
            previous: `Kỳ trước: ${insight.khach_hang?.previous || 0} khách`,
            trend: calcTrendPercent(insight.khach_hang?.current, insight.khach_hang?.previous, insight.khach_hang?.trend),
        },
        {
            label: 'Giá trị đơn trung bình',
            value: insight.gia_tri_don_trung_binh || '0đ',
            previous: `${insight.don_hoan_thanh || 0}/${insight.tong_don || 0} đơn hoàn thành`,
            trend: insight.ti_le_hoan_thanh || 0,
            suffix: 'Hoàn thành',
        },
    ]
})

const completionRate = computed(() =>
    normalOrderTotal.value > 0 ? Math.round((completedOrders.value / normalOrderTotal.value) * 100) : 0
)

const cancelRate = computed(() =>
    normalOrderTotal.value > 0 ? Math.round((cancelledOrders.value / normalOrderTotal.value) * 100) : 0
)

const operationCards = computed(() => [
    {
        label: 'Tổng đơn hàng',
        value: normalOrderTotal.value,
        sub: 'Theo kỳ đang chọn',
        icon: ShoppingCart,
        tone: 'blue',
        to: '/admin/quan-ly-don-hang',
    },
    {
        label: 'Chờ xử lý',
        value: pendingOrders.value,
        sub: 'Đơn cần xác nhận',
        icon: ClipboardList,
        tone: 'amber',
        to: '/admin/quan-ly-don-hang',
    },
    {
        label: 'Tỷ lệ hoàn thành',
        value: `${completionRate.value}%`,
        sub: `${completedOrders.value} đơn hoàn thành`,
        icon: TrendingUp,
        tone: 'green',
        to: '/admin/quan-ly-don-hang',
    },
    {
        label: 'Rủi ro hủy/hoàn',
        value: `${cancelRate.value}%`,
        sub: `${cancelledOrders.value} hủy, ${openRefunds.value} hoàn trả`,
        icon: AlertTriangle,
        tone: 'red',
        to: '/admin/quan-ly-don-hang',
    },
])

const urgentTasks = computed(() => [
    {
        label: 'Xác nhận đơn mới',
        value: pendingOrders.value,
        detail: 'Ưu tiên xử lý trước khi chuyển giao vận.',
        tone: 'warn',
        to: '/admin/quan-ly-don-hang',
    },
    {
        label: 'Theo dõi hoàn trả',
        value: openRefunds.value,
        detail: 'Kiểm tra yêu cầu hoàn và trạng thái lấy hàng.',
        tone: 'danger',
        to: '/admin/quan-ly-don-hang',
    },
    {
        label: 'Đơn đang vận hành',
        value: processingOrders.value,
        detail: 'Theo dõi đơn đã xác nhận hoặc đang giao.',
        tone: 'info',
        to: '/admin/quan-ly-don-hang',
    },
    {
        label: 'Rà soát bán chạy',
        value: lowStockItems.value.length,
        detail: 'Bổ sung hàng cho biến thể còn ít hoặc đã hết.',
        tone: 'success',
        to: '/admin/quan-ly-san-pham',
    },
])

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
        label: found?.label ?? 'Hoàn thành'
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
        label: found?.label ?? 'Đã hoàn tiền'
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

    // Add horizontal padding inside the chart area so the first and last bars do not touch the axes.
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

const moneyToNumber = (value) => {
    if (typeof value === 'number') return value
    const normalized = String(value ?? '').replace(/[^\d-]/g, '')
    return Number(normalized) || 0
}

const numberToExport = (value) => Number(value || 0)
const digitsToNumber = (value) => Number(String(value ?? '').replace(/[^\d-]/g, '')) || 0

const exportMoney = (value) => moneyToNumber(value).toLocaleString('vi-VN') + 'đ'
const exportNumber = (value) => numberToExport(value).toLocaleString('vi-VN')

const escapeXml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;')

const xmlCell = (value, {
    style = 'Text',
    type = typeof value === 'number' ? 'Number' : 'String',
    mergeAcross = 0,
    index = null,
} = {}) => {
    const attrs = [
        `ss:StyleID="${style}"`,
        mergeAcross ? `ss:MergeAcross="${mergeAcross}"` : '',
        index ? `ss:Index="${index}"` : '',
    ].filter(Boolean).join(' ')
    return `<Cell ${attrs}><Data ss:Type="${type}">${escapeXml(value)}</Data></Cell>`
}

const xmlRow = (cells = [], height = null) => {
    const attrs = height ? ` ss:Height="${height}"` : ''
    return `<Row${attrs}>${cells.join('')}</Row>`
}

const xmlColumns = (widths) => widths.map((width) => `<Column ss:Width="${width}"/>`).join('')

const xmlWorksheet = (name, widths, rows) => `
<Worksheet ss:Name="${escapeXml(name).slice(0, 31)}">
    <Table>${xmlColumns(widths)}${rows.join('')}</Table>
    <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
        <FreezePanes/>
        <FrozenNoSplit/>
        <SplitHorizontal>6</SplitHorizontal>
        <TopRowBottomPane>6</TopRowBottomPane>
        <ActivePane>2</ActivePane>
        <Panes>
            <Pane><Number>3</Number></Pane>
            <Pane><Number>2</Number></Pane>
        </Panes>
        <ProtectObjects>False</ProtectObjects>
        <ProtectScenarios>False</ProtectScenarios>
    </WorksheetOptions>
</Worksheet>`

const tableSheetRows = (title, headers, rows, exportedAt) => {
    const normalizedRows = rows.length ? rows : [['Chưa có dữ liệu']]
    return [
        xmlRow([xmlCell(`NEXTGEN LAPTOP - ${title}`, { style: 'SheetTitle', mergeAcross: Math.max(headers.length - 1, 0) })], 30),
        xmlRow([xmlCell(`Kỳ báo cáo: ${periodLabel.value}`, { style: 'SheetMeta', mergeAcross: 1 }), xmlCell(`Ngày xuất: ${exportedAt.toLocaleString('vi-VN')}`, { style: 'SheetMeta', mergeAcross: Math.max(headers.length - 3, 0) })], 22),
        xmlRow([], 8),
        xmlRow(headers.map((header) => xmlCell(header, { style: 'TableHeader' })), 24),
        ...normalizedRows.map((row, index) => xmlRow(row.map((value) => xmlCell(value, { style: index % 2 ? 'TableCellAlt' : 'TableCell' })), 24)),
    ]
}

const downloadExcelXml = (xml, fileName) => {
    const blob = new Blob(['\ufeff', xml], { type: 'application/vnd.ms-excel;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
}

const exportDashboardExcel = () => {
    if (!data.value) return

    const dashboard = data.value
    const insight = dashboard.phan_tich || createDashboardShell(period.value).phan_tich
    const staff = dashboard.nhan_su_hoat_dong || createDashboardShell(period.value).nhan_su_hoat_dong
    const customers = dashboard.khach_hang_hoat_dong || createDashboardShell(period.value).khach_hang_hoat_dong
    const exportedAt = new Date()

    const bestPayment = [...(dashboard.thanh_toan || [])].sort((a, b) => numberToExport(b.total) - numberToExport(a.total))[0]
    const bestCategory = [...(dashboard.danh_muc_ban_chay || [])].sort((a, b) => numberToExport(b.total) - numberToExport(a.total))[0]
    const totalRevenue = moneyToNumber(dashboard.doanh_thu)
    const periodRevenue = moneyToNumber(insight.doanh_thu?.current)
    const previousRevenue = moneyToNumber(insight.doanh_thu?.previous)
    const averageOrderValue = moneyToNumber(insight.gia_tri_don_trung_binh)

    const reportRows = [
        xmlRow([xmlCell('NEXTGEN LAPTOP', { style: 'Brand', mergeAcross: 6 })], 26),
        xmlRow([xmlCell('BÁO CÁO TỔNG QUAN HỆ THỐNG', { style: 'Title', mergeAcross: 6 })], 38),
        xmlRow([
            xmlCell('Kỳ báo cáo', { style: 'MetaKey' }),
            xmlCell(periodLabel.value, { style: 'MetaValue', mergeAcross: 1 }),
            xmlCell('Ngày xuất', { style: 'MetaKey' }),
            xmlCell(exportedAt.toLocaleString('vi-VN'), { style: 'MetaValue', mergeAcross: 2 }),
        ], 24),
        xmlRow([
            xmlCell('Đơn vị lập', { style: 'MetaKey' }),
            xmlCell('NextGen Laptop', { style: 'MetaValue', mergeAcross: 1 }),
            xmlCell('Loại báo cáo', { style: 'MetaKey' }),
            xmlCell('Quản trị - kế toán', { style: 'MetaValue', mergeAcross: 2 }),
        ], 24),
        xmlRow([], 10),
        xmlRow([
            xmlCell('DOANH THU TỔNG', { style: 'KpiLabel', mergeAcross: 1 }),
            xmlCell('ĐƠN HÀNG', { style: 'KpiLabel', mergeAcross: 1 }),
            xmlCell('TỶ LỆ HOÀN THÀNH', { style: 'KpiLabel', mergeAcross: 1 }),
            xmlCell('GIÁ TRỊ ĐƠN TB', { style: 'KpiLabel' }),
        ], 24),
        xmlRow([
            xmlCell(exportMoney(totalRevenue), { style: 'KpiValue', mergeAcross: 1 }),
            xmlCell(exportNumber(normalOrderTotal.value), { style: 'KpiValue', mergeAcross: 1 }),
            xmlCell(`${completionRate.value}%`, { style: 'KpiValue', mergeAcross: 1 }),
            xmlCell(exportMoney(averageOrderValue), { style: 'KpiValue' }),
        ], 38),
        xmlRow([
            xmlCell('Đơn hoàn thành', { style: 'KpiSub' }),
            xmlCell(exportNumber(completedOrders.value), { style: 'KpiSub' }),
            xmlCell('Chờ xử lý', { style: 'KpiSub' }),
            xmlCell(exportNumber(pendingOrders.value), { style: 'KpiSub' }),
            xmlCell('Rủi ro hủy/hoàn', { style: 'KpiSub' }),
            xmlCell(`${cancelRate.value}%`, { style: 'KpiSub' }),
            xmlCell(`${insight.don_hoan_thanh || 0}/${insight.tong_don || 0} đơn`, { style: 'KpiSub' }),
        ], 24),
        xmlRow([], 10),
        xmlRow([xmlCell('I. TÓM TẮT CHỈ SỐ CHÍNH', { style: 'Section', mergeAcross: 6 })], 26),
        xmlRow(['Nhóm chỉ tiêu', 'Chỉ tiêu', 'Giá trị', 'Đơn vị', 'Diễn giải', 'Kỳ báo cáo', 'Nguồn dữ liệu'].map((header) => xmlCell(header, { style: 'TableHeader' })), 25),
        ...[
            ['Tài chính', 'Doanh thu tổng', exportMoney(totalRevenue), 'đ', 'Doanh thu từ đơn hàng hoàn thành', periodLabel.value, 'Đơn hàng'],
            ['Tài chính', 'Doanh thu kỳ này', exportMoney(periodRevenue), 'đ', 'Doanh thu dùng so sánh tăng trưởng', periodLabel.value, 'Đơn hàng'],
            ['Tài chính', 'Doanh thu kỳ trước', exportMoney(previousRevenue), 'đ', 'Mốc so sánh liền kề theo kỳ', periodLabel.value, 'Đơn hàng'],
            ['Tài chính', 'Tăng trưởng doanh thu', `${numberToExport(insight.doanh_thu?.trend)}%`, '%', 'So với kỳ trước', periodLabel.value, 'Đơn hàng'],
            ['Đơn hàng', 'Tổng đơn hàng', exportNumber(normalOrderTotal.value), 'đơn', 'Tổng đơn không tính nhóm hoàn trả nội bộ', periodLabel.value, 'Đơn hàng'],
            ['Đơn hàng', 'Đơn hoàn thành', exportNumber(completedOrders.value), 'đơn', 'Đơn đã hoàn tất giao dịch', periodLabel.value, 'Đơn hàng'],
            ['Đơn hàng', 'Tỷ lệ hoàn thành', `${completionRate.value}%`, '%', `${completedOrders.value}/${normalOrderTotal.value} đơn hoàn thành`, periodLabel.value, 'Đơn hàng'],
            ['Đơn hàng', 'Rủi ro hủy/hoàn', `${cancelRate.value}%`, '%', `${cancelledOrders.value} hủy, ${openRefunds.value} yêu cầu hoàn`, periodLabel.value, 'Đơn hàng'],
            ['Khách hàng', 'Tổng khách hàng', exportNumber(dashboard.khach_hang), 'khách', 'Tổng tài khoản khách hàng', periodLabel.value, 'Người dùng'],
            ['Khách hàng', 'Khách mới kỳ này', exportNumber(insight.khach_hang?.current), 'khách', 'Khách đăng ký trong kỳ', periodLabel.value, 'Người dùng'],
            ['Kho hàng', 'Sản phẩm kho', exportNumber(dashboard.bien_the), 'biến thể', 'Tổng biến thể đang quản lý', periodLabel.value, 'Kho hàng'],
            ['Kho hàng', 'Cảnh báo tồn kho', exportNumber(lowStockItems.value.length), 'sản phẩm', 'Biến thể còn ít hoặc đã hết', periodLabel.value, 'Kho hàng'],
        ].map((row, index) => xmlRow(row.map((cell) => xmlCell(cell, { style: index % 2 ? 'TableCellAlt' : 'TableCell' })), 24)),
        xmlRow([], 10),
        xmlRow([xmlCell('II. THEO DÕI KẾ TOÁN - THUẾ', { style: 'Section', mergeAcross: 6 })], 26),
        xmlRow(['Khoản mục', 'Giá trị', 'Đơn vị', 'Ghi chú', '', '', ''].map((header) => xmlCell(header, { style: header ? 'TableHeader' : 'TableHeaderBlank' })), 25),
        ...[
            ['Doanh thu đã hoàn thành', exportMoney(totalRevenue), 'đ', 'Cơ sở đối chiếu doanh thu theo đơn hoàn thành', '', '', ''],
            ['Giá trị đơn trung bình', exportMoney(averageOrderValue), 'đ', `${insight.don_hoan_thanh || 0}/${insight.tong_don || 0} đơn hoàn thành`, '', '', ''],
            ['Phương thức thanh toán chính', bestPayment?.label || 'Chưa có dữ liệu', 'phương thức', `${bestPayment?.total || 0} đơn`, '', '', ''],
            ['Danh mục bán chạy', bestCategory?.label || 'Chưa có dữ liệu', 'danh mục', `${bestCategory?.total || 0} sản phẩm`, '', '', ''],
        ].map((row, index) => xmlRow(row.map((cell) => xmlCell(cell, { style: index % 2 ? 'TableCellAlt' : 'TableCell' })), 24)),
        xmlRow([], 10),
        xmlRow([xmlCell('III. VẬN HÀNH & HOẠT ĐỘNG ONLINE', { style: 'Section', mergeAcross: 6 })], 26),
        xmlRow(['Khoản mục', 'Giá trị', 'Đơn vị', 'Ghi chú', '', '', ''].map((header) => xmlCell(header, { style: header ? 'TableHeader' : 'TableHeaderBlank' })), 25),
        ...[
            ['Chờ xử lý', exportNumber(pendingOrders.value), 'đơn', 'Đơn cần xác nhận', '', '', ''],
            ['Đơn đang vận hành', exportNumber(processingOrders.value), 'đơn', 'Đã xác nhận hoặc đang giao', '', '', ''],
            ['Khách online', exportNumber(customers.online), 'khách', 'Hoạt động trong 5 phút gần nhất', '', '', ''],
            ['Khách ghé hôm nay', exportNumber(customers.visited_today), 'khách', 'Có hoạt động trong ngày', '', '', ''],
            ['Nhân sự online', exportNumber(staff.online), 'người', `${staff.online || 0}/${staff.total || 0} nhân sự`, '', '', ''],
        ].map((row, index) => xmlRow(row.map((cell) => xmlCell(cell, { style: index % 2 ? 'TableCellAlt' : 'TableCell' })), 24)),
        xmlRow([], 10),
        xmlRow([xmlCell('IV. GHI CHÚ BÁO CÁO', { style: 'Section', mergeAcross: 6 })], 26),
        xmlRow([xmlCell('Báo cáo được xuất từ dashboard quản trị NextGen. Số liệu phục vụ theo dõi vận hành, kế toán nội bộ và đối chiếu khi cần.', { style: 'Note', mergeAcross: 6 })], 32),
        xmlRow([xmlCell('Các khoản doanh thu trong báo cáo được lấy theo đơn hàng có trạng thái hoàn thành tại thời điểm xuất file.', { style: 'Note', mergeAcross: 6 })], 32),
        xmlRow([], 18),
        xmlRow([
            xmlCell('Người lập báo cáo', { style: 'Sign', mergeAcross: 1 }),
            xmlCell('Kế toán/Quản lý duyệt', { style: 'Sign', mergeAcross: 2 }),
            xmlCell('Đại diện đơn vị', { style: 'Sign', mergeAcross: 1 }),
        ], 28),
        xmlRow([], 54),
        xmlRow([
            xmlCell('Ký, ghi rõ họ tên', { style: 'SignSub', mergeAcross: 1 }),
            xmlCell('Ký, ghi rõ họ tên', { style: 'SignSub', mergeAcross: 2 }),
            xmlCell('Ký, ghi rõ họ tên', { style: 'SignSub', mergeAcross: 1 }),
        ], 22),
    ]

    const worksheets = [
        xmlWorksheet('Tong quan', [96, 150, 96, 72, 246, 96, 96], reportRows),
        xmlWorksheet('Doanh thu ngay', [110, 140], tableSheetRows(
            'Doanh thu ngày',
            ['Ngày', 'Doanh thu'],
            (dashboard.bieu_do || []).map((item) => [item.label, exportMoney(item.total)]),
            exportedAt,
        )),
        xmlWorksheet('Trang thai don', [120, 190, 90, 90], tableSheetRows(
            'Trạng thái đơn',
            ['Mã trạng thái', 'Trạng thái', 'Số lượng', 'Tỷ lệ (%)'],
            (dashboard.trang_thai || []).map((item) => [item.status, item.label, exportNumber(item.count), `${item.pct}%`]),
            exportedAt,
        )),
        xmlWorksheet('Don hang moi', [120, 180, 140, 150], tableSheetRows(
            'Đơn hàng mới',
            ['Mã đơn', 'Khách hàng', 'Tổng tiền', 'Trạng thái'],
            (dashboard.don_hang || []).map((item) => [item.id, item.khach, exportMoney(item.tong), item.trangthai]),
            exportedAt,
        )),
        xmlWorksheet('Thanh toan', [180, 90], tableSheetRows(
            'Thanh toán',
            ['Phương thức', 'Số đơn'],
            (dashboard.thanh_toan || []).map((item) => [item.label, exportNumber(item.total)]),
            exportedAt,
        )),
        xmlWorksheet('San pham ban chay', [330, 140, 90], tableSheetRows(
            'Sản phẩm bán chạy',
            ['Sản phẩm', 'Giá bán', 'Đã bán'],
            (dashboard.san_pham || []).map((item) => [item.ten, exportMoney(item.gia), exportNumber(digitsToNumber(item.tong_ban))]),
            exportedAt,
        )),
        xmlWorksheet('Danh muc ban chay', [200, 120, 140], tableSheetRows(
            'Danh mục bán chạy',
            ['Danh mục', 'Số lượng bán', 'Doanh thu'],
            (dashboard.danh_muc_ban_chay || []).map((item) => [item.label, exportNumber(item.total), exportMoney(item.revenue)]),
            exportedAt,
        )),
        xmlWorksheet('Canh bao kho', [330, 90, 140], tableSheetRows(
            'Cảnh báo kho',
            ['Sản phẩm', 'Tồn kho', 'Giá bán'],
            (dashboard.ton_kho_canh_bao || []).map((item) => [item.ten, exportNumber(item.soluong), exportMoney(item.gia)]),
            exportedAt,
        )),
    ]

    const xml = `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">
    <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
        <Title>Báo cáo tổng quan hệ thống NextGen</Title>
        <Subject>Báo cáo kỳ ${escapeXml(periodLabel.value)}</Subject>
        <Author>NextGen Laptop</Author>
        <Company>NextGen Laptop</Company>
        <Created>${exportedAt.toISOString()}</Created>
    </DocumentProperties>
    <Styles>
        <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Arial" ss:Size="10" ss:Color="#0F172A"/></Style>
        <Style ss:ID="Brand"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="11" ss:Bold="1" ss:Color="#2563EB"/><Interior ss:Color="#EFF6FF" ss:Pattern="Solid"/></Style>
        <Style ss:ID="Title"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="20" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0F172A" ss:Pattern="Solid"/></Style>
        <Style ss:ID="MetaKey"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#475569"/><Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>
        <Style ss:ID="MetaValue"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#0F172A"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>
        <Style ss:ID="KpiLabel"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="9" ss:Bold="1" ss:Color="#DBEAFE"/><Interior ss:Color="#1E40AF" ss:Pattern="Solid"/></Style>
        <Style ss:ID="KpiValue"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="17" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#2563EB" ss:Pattern="Solid"/></Style>
        <Style ss:ID="KpiSub"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#334155"/><Interior ss:Color="#EFF6FF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/></Borders></Style>
        <Style ss:ID="Section"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="12" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#1D4ED8" ss:Pattern="Solid"/></Style>
        <Style ss:ID="TableHeader"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#2563EB" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1D4ED8"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/></Borders></Style>
        <Style ss:ID="TableHeaderBlank"><Interior ss:Color="#2563EB" ss:Pattern="Solid"/></Style>
        <Style ss:ID="TableCell"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Arial" ss:Size="10" ss:Color="#0F172A"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="TableCellAlt"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Arial" ss:Size="10" ss:Color="#0F172A"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="Note"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Arial" ss:Size="10" ss:Italic="1" ss:Color="#475569"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="Sign"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#0F172A"/><Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/></Style>
        <Style ss:ID="SignSub"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="9" ss:Italic="1" ss:Color="#64748B"/></Style>
        <Style ss:ID="SheetTitle"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="16" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0F172A" ss:Pattern="Solid"/></Style>
        <Style ss:ID="SheetMeta"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#475569"/><Interior ss:Color="#EFF6FF" ss:Pattern="Solid"/></Style>
    </Styles>
    ${worksheets.join('')}
</Workbook>`

    const fileDate = exportedAt.toISOString().slice(0, 10)
    downloadExcelXml(xml, `bao-cao-tong-quan-${period.value}-${fileDate}.xls`)
}

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

            <div class="dashboard-controls">
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
                <div class="topbar-right">
                    <button class="export-btn admin-report-export" type="button" @click="exportDashboardExcel">
                        <Download aria-hidden="true" />
                        Xuất báo cáo
                    </button>
                    <div class="search-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input v-model="searchQuery" placeholder="Tìm kiếm dữ liệu..." />
                    </div>
                </div>
            </div>

            <section class="dashboard-cluster overview-cluster">
                <div class="cluster-head">
                    <div>
                        <span class="cluster-kicker">Tổng quan</span>
                        <h3>Chỉ số chính</h3>
                    </div>
                    <small>{{ periodLabel }}</small>
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
                            <p class="stat-label" :style="{ color: s.labelColor }">{{ s.label }}</p>
                        </div>
                        <b class="stat-value">{{ s.value }}</b>
                        <span class="stat-card-action">{{ s.hint }}</span>
                    </router-link>
                </div>
            </section>

            <section class="dashboard-cluster performance-cluster">
                <div class="cluster-head">
                    <div>
                        <span class="cluster-kicker">Điều hành</span>
                        <h3>Vận hành và hiệu suất</h3>
                    </div>
                    <small>Cập nhật theo kỳ thống kê</small>
                </div>

                <div class="performance-layout">
                    <div class="metric-panel">
                        <div class="cluster-subhead">Tình trạng vận hành</div>
                        <div class="ops-grid">
                            <router-link
                                v-for="card in operationCards"
                                :key="card.label"
                                :to="card.to"
                                :class="['ops-card', `tone-${card.tone}`]"
                            >
                                <div class="ops-icon">
                                    <component :is="card.icon" aria-hidden="true" />
                                </div>
                                <div class="ops-copy">
                                    <span>{{ card.label }}</span>
                                    <b>{{ card.value }}</b>
                                    <small>{{ card.sub }}</small>
                                </div>
                            </router-link>
                        </div>
                    </div>

                    <div class="metric-panel">
                        <div class="cluster-subhead">So sánh kỳ trước</div>
                        <div class="analysis-grid">
                            <div v-for="card in analysisCards" :key="card.label" class="analysis-card">
                                <div class="analysis-head">
                                    <span>{{ card.label }}</span>
                                    <b :class="['trend-pill', trendClass(card.trend)]">
                                        {{ trendText(card.trend) }}
                                        <small v-if="card.suffix">{{ card.suffix }}</small>
                                    </b>
                                </div>
                                <strong>{{ card.value }}</strong>
                                <p>{{ card.previous }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="workbench-card">
                <div class="workbench-head">
                    <div>
                        <span class="eyebrow">Việc cần xử lý</span>
                        <h3>Ưu tiên vận hành hôm nay</h3>
                    </div>
                    <router-link to="/admin/quan-ly-don-hang">Mở đơn hàng</router-link>
                </div>

                <div class="workbench-layout">
                    <div class="task-column">
                        <div class="task-grid">
                            <router-link
                                v-for="task in urgentTasks"
                                :key="task.label"
                                :to="task.to"
                                :class="['task-item', `tone-${task.tone}`]"
                            >
                                <span class="task-count">{{ task.value }}</span>
                                <div>
                                    <b>{{ task.label }}</b>
                                    <small>{{ task.detail }}</small>
                                </div>
                            </router-link>
                        </div>

                        <div class="operation-flow-card">
                            <div class="flow-head">
                                <div>
                                    <span class="cluster-kicker">Nhịp vận hành</span>
                                    <h4>Tình hình xử lý nhanh</h4>
                                </div>
                                <strong>{{ completionRate }}%</strong>
                            </div>
                            <div class="flow-grid">
                                <div>
                                    <span>Chờ xác nhận</span>
                                    <b>{{ pendingOrders }}</b>
                                </div>
                                <div>
                                    <span>Đang vận hành</span>
                                    <b>{{ processingOrders }}</b>
                                </div>
                                <div>
                                    <span>Cảnh báo kho</span>
                                    <b>{{ lowStockItems.length }}</b>
                                </div>
                                <div>
                                    <span>Hoàn thành</span>
                                    <b>{{ completedOrders }}</b>
                                </div>
                            </div>
                            <div class="flow-progress">
                                <span :style="{ width: `${Math.max(6, Math.min(completionRate, 100))}%` }"></span>
                            </div>
                        </div>
                    </div>

                    <div class="activity-column">
                        <div class="staff-activity-card">
                            <div class="staff-head">
                                <div>
                                    <span class="cluster-kicker">Nhân sự</span>
                                    <h4>Trạng thái nhân viên</h4>
                                </div>
                                <strong>{{ staffActivity.online }}/{{ staffActivity.total }}</strong>
                            </div>

                            <div class="staff-summary">
                                <span class="online">Online: {{ staffActivity.online }}</span>
                                <span class="idle">Vắng: {{ staffActivity.idle }}</span>
                                <span class="offline">Offline: {{ staffActivity.offline }}</span>
                            </div>

                            <div class="staff-list">
                                <div
                                    v-for="staff in activeStaffList"
                                    :key="staff.id"
                                    class="staff-row"
                                >
                                    <img v-if="staff.avatar" :src="staff.avatar" :alt="staff.ten" />
                                    <span v-else class="staff-avatar">{{ String(staff.ten || 'N').slice(0, 1) }}</span>
                                    <div class="staff-main">
                                        <b>{{ staff.ten }}</b>
                                        <small>{{ staff.vaitro }} · {{ staff.last_active_text }}</small>
                                    </div>
                                    <em :class="staffStatusClass(staff.status)">{{ staff.status_label }}</em>
                                </div>
                                <div v-if="!activeStaffList.length" class="empty-mini">Chưa có dữ liệu nhân sự</div>
                            </div>
                        </div>

                        <div class="customer-activity-card">
                            <div class="customer-head">
                                <div>
                                    <span class="cluster-kicker">Khách hàng</span>
                                    <h4>Trạng thái truy cập</h4>
                                </div>
                                <strong>{{ customerActivity.online }}</strong>
                            </div>
                            <div class="customer-live-strip">
                                <div>
                                    <span>Đang online</span>
                                    <b>{{ customerActivity.online }}</b>
                                </div>
                                <div>
                                    <span>Hoạt động gần đây</span>
                                    <b>{{ customerActivity.recent }}</b>
                                </div>
                                <div>
                                    <span>Ghé hôm nay</span>
                                    <b>{{ customerActivity.visited_today }}</b>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                                <span><i class="dot revenue"></i>Doanh thu</span>
                                <span><i class="dot orders"></i>Đơn hàng</span>
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

            <div class="operations-row">
                <div class="card operation-panel">
                    <div class="section-header">
                        <span class="section-title">Đơn cần theo dõi</span>
                        <router-link to="/admin/quan-ly-don-hang" class="see-all">Xử lý đơn</router-link>
                    </div>
                    <div class="follow-list">
                        <router-link
                            v-for="order in handlingOrders"
                            :key="order.id"
                            to="/admin/quan-ly-don-hang"
                            class="follow-item"
                        >
                            <div>
                                <b>{{ order.id }}</b>
                                <span>{{ order.khach }} · {{ order.tong }}</span>
                            </div>
                            <div class="follow-right">
                                <small>{{ order.tuoi_don }}</small>
                                <span class="status-badge" :class="statusClass(order.status)">{{ order.trangthai }}</span>
                            </div>
                        </router-link>
                        <div v-if="!handlingOrders.length" class="empty-row">Không có đơn quá hạn cần xử lý</div>
                    </div>
                </div>

                <div class="card operation-panel">
                    <div class="section-header">
                        <span class="section-title">Cảnh báo tồn kho</span>
                        <router-link to="/admin/quan-ly-san-pham" class="see-all">Nhập hàng</router-link>
                    </div>
                    <div class="stock-list">
                        <router-link
                            v-for="item in lowStockItems"
                            :key="item.id"
                            to="/admin/quan-ly-san-pham"
                            class="stock-item"
                        >
                            <div>
                                <b>{{ item.ten }}</b>
                                <span>{{ item.gia }}</span>
                            </div>
                            <strong :class="{ danger: item.soluong <= 0 }">{{ item.soluong }}</strong>
                        </router-link>
                        <div v-if="!lowStockItems.length" class="empty-row">Kho đang ổn định</div>
                    </div>
                </div>

                <div class="card operation-panel">
                    <div class="section-header">
                        <span class="section-title">Góc nhìn kinh doanh</span>
                        <router-link to="/admin/quan-ly-san-pham" class="see-all">Chi tiết</router-link>
                    </div>
                    <div class="mini-section">
                        <b>Thanh toán</b>
                        <div v-for="item in paymentMethods" :key="item.label" class="mini-row">
                            <span>{{ item.label }}</span>
                            <strong>{{ item.total }}</strong>
                        </div>
                        <div v-if="!paymentMethods.length" class="empty-mini">Chưa có dữ liệu</div>
                    </div>
                    <div class="mini-section">
                        <b>Danh mục bán chạy</b>
                        <div v-for="item in topCategories" :key="item.label" class="mini-row">
                            <span>{{ item.label }}</span>
                            <strong>{{ item.total }}</strong>
                        </div>
                        <div v-if="!topCategories.length" class="empty-mini">Chưa có dữ liệu</div>
                    </div>
                </div>
            </div>

            <div class="insight-charts-row">
                <div class="card insight-chart-card">
                    <div class="section-header compact">
                        <span class="section-title">Biểu đồ thanh toán</span>
                    </div>
                    <div class="mini-donut-summary">
                        <div class="mini-donut" :style="{ '--donut-value': `${paymentDonut.pct}%` }">
                            <div><b>{{ paymentDonut.pct }}%</b><span>{{ paymentDonut.label }}</span></div>
                        </div>
                    </div>
                    <div class="horizontal-chart">
                        <div v-for="item in paymentChartRows" :key="item.label" class="chart-bar-row">
                            <div class="chart-bar-meta">
                                <span>{{ item.label }}</span>
                                <b>{{ item.total }}</b>
                            </div>
                            <div class="chart-track">
                                <span class="chart-fill blue" :style="{ width: `${item.pct}%` }"></span>
                            </div>
                        </div>
                        <div v-if="!paymentChartRows.length" class="empty-mini">Chưa có dữ liệu</div>
                    </div>
                </div>

                <div class="card insight-chart-card">
                    <div class="section-header compact">
                        <span class="section-title">Danh mục bán chạy</span>
                    </div>
                    <div class="mini-donut-summary">
                        <div class="mini-donut" :style="{ '--donut-value': `${categoryDonut.pct}%` }">
                            <div><b>{{ categoryDonut.pct }}%</b><span>{{ categoryDonut.label }}</span></div>
                        </div>
                    </div>
                    <div class="horizontal-chart">
                        <div v-for="item in categoryChartRows" :key="item.label" class="chart-bar-row">
                            <div class="chart-bar-meta">
                                <span>{{ item.label }}</span>
                                <b>{{ item.total }}</b>
                            </div>
                            <div class="chart-track">
                                <span class="chart-fill green" :style="{ width: `${item.pct}%` }"></span>
                            </div>
                            <small>{{ item.revenue }}</small>
                        </div>
                        <div v-if="!categoryChartRows.length" class="empty-mini">Chưa có dữ liệu</div>
                    </div>
                </div>

                <div class="card insight-chart-card">
                    <div class="section-header compact">
                        <span class="section-title">Rủi ro tồn kho</span>
                    </div>
                    <div class="mini-donut-summary">
                        <div class="mini-donut" :style="{ '--donut-value': `${stockDonut.pct}%` }">
                            <div><b>{{ stockDonut.pct }}%</b><span>{{ stockDonut.label }}</span></div>
                        </div>
                    </div>
                    <div class="horizontal-chart">
                        <div v-for="item in stockRiskRows" :key="item.id" class="chart-bar-row">
                            <div class="chart-bar-meta">
                                <span>{{ item.ten }}</span>
                                <b>{{ item.soluong }}</b>
                            </div>
                            <div class="chart-track">
                                <span :class="['chart-fill', item.tone]" :style="{ width: `${item.risk}%` }"></span>
                            </div>
                        </div>
                        <div v-if="!stockRiskRows.length" class="empty-mini">Kho đang ổn định</div>
                    </div>
                </div>

                <div class="card insight-chart-card">
                    <div class="section-header compact">
                        <span class="section-title">Luồng xử lý đơn</span>
                    </div>
                    <div class="mini-donut-summary">
                        <div class="mini-donut" :style="{ '--donut-value': `${orderDonut.pct}%` }">
                            <div><b>{{ orderDonut.pct }}%</b><span>{{ orderDonut.label }}</span></div>
                        </div>
                    </div>
                    <div class="horizontal-chart">
                        <div v-for="item in orderPipelineRows" :key="item.status" class="chart-bar-row">
                            <div class="chart-bar-meta">
                                <span>{{ item.label }}</span>
                                <b>{{ item.count }}</b>
                            </div>
                            <div class="chart-track">
                                <span class="chart-fill custom" :style="{ width: `${item.pct}%`, background: item.color }"></span>
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
                                <th>Mã đơn</th>
                                <th>KHÁCH HÀNG</th>
                                <th>Tổng cộng</th>
                                <th>Trạng thái</th>
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
                            <div v-else class="img-placeholder">??</div>
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
    background: transparent;
    min-height: calc(100vh - 77px);
    font-family: sans-serif;
    padding: 24px 0 40px;
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
    display: none;
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

.export-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 38px;
    padding: 0 14px;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
    color: #2563eb;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 10px 24px rgba(37, 99, 235, .08);
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}

.export-btn svg {
    width: 16px;
    height: 16px;
}

.export-btn:hover {
    transform: translateY(-1px);
    border-color: #2563eb;
    box-shadow: 0 14px 28px rgba(37, 99, 235, .14);
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
    margin: 0 0 16px;
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
.dashboard-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 0 0 16px;
}

.period-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0;
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
.dashboard-cluster {
    margin: 0 0 20px;
    padding: 20px;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.055);
}

.overview-cluster {
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.88) 0%, rgba(248, 251, 255, 0.94) 100%);
}

.performance-cluster {
    background:
        radial-gradient(circle at 8% 0%, rgba(37, 99, 235, 0.07), transparent 30%),
        linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.cluster-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.cluster-head h3 {
    margin: 3px 0 0;
    color: #0f172a;
    font-size: 18px;
    font-weight: 950;
}

.cluster-head small {
    color: #64748b;
    font-size: 12px;
    font-weight: 850;
    padding: 7px 10px;
    border-radius: 999px;
    background: #f1f5f9;
    white-space: nowrap;
}

.cluster-kicker {
    color: #2563eb;
    font-size: 11px;
    font-weight: 950;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.cluster-subhead {
    color: #64748b;
    font-size: 12px;
    font-weight: 950;
    margin-bottom: 12px;
}

.performance-layout {
    display: grid;
    grid-template-columns: minmax(0, 0.92fr) minmax(0, 1.08fr);
    gap: 16px;
    align-items: stretch;
}

.metric-panel {
    padding: 18px;
    border-radius: 14px;
    border: 1px solid #e8eef7;
    background: rgba(255, 255, 255, 0.86);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 16px !important;
    padding: 0;
}

.stat-card {
    min-height: 126px;
    border-radius: 14px;
    border: 1px solid transparent;
    padding: 22px 24px;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.16);
    color: #fff;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.overview-cluster .stat-card {
    min-width: 0;
    padding: 14px 18px !important;
}

.overview-cluster .stat-icon-wrap {
    width: 36px !important;
    height: 36px !important;
    min-width: 36px !important;
}

.overview-cluster .stat-icon-wrap svg {
    width: 19px;
    height: 19px;
}

.overview-cluster .stat-top {
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.overview-cluster .stat-label {
    margin: 0;
    min-width: 0;
    line-height: 1.25;
}

.overview-cluster .stat-value {
    max-width: 100%;
    font-size: clamp(24px, 1.8vw, 31px) !important;
    white-space: nowrap;
    overflow: visible;
    text-overflow: clip;
}

.overview-cluster .stat-card:first-child .stat-value {
    font-size: clamp(21px, 1.55vw, 27px) !important;
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
    margin-bottom: 12px;
}

.stat-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 12px;
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
    margin: 0 0 16px;
    text-transform: capitalize;
}

.stat-value {
    font-size: 31px;
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

.ops-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    padding: 0;
}

.ops-card {
    display: grid;
    align-content: center;
    gap: 12px;
    min-height: 118px;
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #e8eef7;
    background: #f8fafc;
    text-decoration: none;
    color: #0f172a;
    box-shadow: none;
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.ops-card:first-child {
    grid-column: 1 / -1;
    min-height: 118px;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 14px 30px rgba(37, 99, 235, 0.22);
}

.ops-card:hover,
.ops-card:focus-visible {
    transform: translateY(-2px);
    border-color: #93c5fd;
    box-shadow: 0 16px 30px rgba(15, 23, 42, 0.09);
    outline: none;
}

.ops-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ops-icon svg {
    width: 22px;
    height: 22px;
    stroke-width: 2.25;
}

.ops-copy {
    display: grid;
    gap: 4px;
    min-width: 0;
}

.ops-copy span {
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
}

.ops-copy b {
    color: #0f172a;
    font-size: 24px;
    line-height: 1;
    font-weight: 900;
}

.ops-copy small {
    color: #94a3b8;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.35;
}

.ops-card.tone-blue .ops-icon {
    background: #dbeafe;
    color: #2563eb;
}

.ops-card:first-child .ops-icon {
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
}

.ops-card:first-child .ops-copy span,
.ops-card:first-child .ops-copy b,
.ops-card:first-child .ops-copy small {
    color: #fff;
}

.ops-card:first-child .ops-copy span,
.ops-card:first-child .ops-copy small {
    opacity: 0.86;
}

.ops-card:first-child .ops-copy b {
    font-size: 34px;
}

.ops-card.tone-amber .ops-icon {
    background: #fef3c7;
    color: #d97706;
}

.ops-card.tone-green .ops-icon {
    background: #dcfce7;
    color: #16a34a;
}

.ops-card.tone-red .ops-icon {
    background: #fee2e2;
    color: #dc2626;
}

.analysis-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    padding: 0;
}

.analysis-card {
    min-height: 122px;
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #e8eef7;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    box-shadow: none;
    display: grid;
    align-content: space-between;
}

.analysis-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}

.analysis-head > span {
    color: #64748b;
    font-size: 12px;
    font-weight: 900;
}

.analysis-card > strong {
    display: block;
    color: #0f172a;
    font-size: 24px;
    line-height: 1;
    font-weight: 950;
    margin-bottom: 10px;
}

.analysis-card > p {
    margin: 0;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 750;
}

.trend-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    white-space: nowrap;
}

.trend-pill small {
    font-size: 9px;
    font-weight: 800;
}

.trend-pill.up {
    background: #dcfce7;
    color: #16a34a;
}

.trend-pill.down {
    background: #fee2e2;
    color: #dc2626;
}

.trend-pill.flat {
    background: #f1f5f9;
    color: #64748b;
}

.workbench-card {
    margin: 0 0 20px;
    padding: 16px;
    border-radius: 14px;
    border: 1px solid #dbeafe;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
}

.workbench-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 12px;
}

.workbench-head h3 {
    margin: 4px 0 0;
    color: #0f172a;
    font-size: 18px;
    font-weight: 900;
}

.workbench-head a {
    color: #2563eb;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
}

.eyebrow {
    color: #2563eb;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.task-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.workbench-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(320px, 0.75fr);
    gap: 14px;
    align-items: stretch;
}

.task-column {
    display: grid;
    gap: 12px;
    align-content: start;
}

.task-item {
    display: flex;
    gap: 12px;
    min-height: 112px;
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    text-decoration: none;
    color: #0f172a;
    transition: border-color 0.18s ease, transform 0.18s ease, background 0.18s ease;
}

.task-item:hover,
.task-item:focus-visible {
    transform: translateY(-2px);
    border-color: #93c5fd;
    outline: none;
}

.task-count {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
    font-weight: 900;
}

.task-item b {
    display: block;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    margin-bottom: 5px;
}

.task-item small {
    display: block;
    color: #64748b;
    font-size: 11px;
    line-height: 1.45;
    font-weight: 650;
}

.task-item.tone-warn .task-count {
    background: #fef3c7;
    color: #b45309;
}

.task-item.tone-danger .task-count {
    background: #fee2e2;
    color: #dc2626;
}

.task-item.tone-info .task-count {
    background: #dbeafe;
    color: #2563eb;
}

.task-item.tone-success .task-count {
    background: #dcfce7;
    color: #16a34a;
}

.operation-flow-card {
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #dbeafe;
    background:
        linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(248, 251, 255, 0.85)),
        #ffffff;
}

.flow-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.flow-head h4 {
    margin: 3px 0 0;
    color: #0f172a;
    font-size: 15px;
    font-weight: 950;
}

.flow-head strong {
    color: #2563eb;
    font-size: 22px;
    line-height: 1;
    font-weight: 950;
}

.flow-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 12px;
}

.flow-grid div {
    padding: 10px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid #e8eef7;
}

.flow-grid span {
    display: block;
    color: #64748b;
    font-size: 10px;
    font-weight: 850;
    line-height: 1.25;
    margin-bottom: 5px;
}

.flow-grid b {
    color: #0f172a;
    font-size: 19px;
    line-height: 1;
    font-weight: 950;
}

.flow-progress {
    height: 8px;
    border-radius: 999px;
    background: #dbeafe;
    overflow: hidden;
}

.flow-progress span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #2563eb, #60a5fa);
}

.activity-column {
    display: grid;
    gap: 12px;
}

.staff-activity-card,
.customer-activity-card {
    padding: 14px;
    border-radius: 14px;
    border: 1px solid #dbeafe;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.customer-activity-card {
    background:
        linear-gradient(135deg, rgba(14, 165, 233, 0.08), rgba(255, 255, 255, 0.92)),
        #ffffff;
}

.staff-head,
.customer-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.staff-head h4,
.customer-head h4 {
    margin: 3px 0 0;
    color: #0f172a;
    font-size: 15px;
    font-weight: 950;
}

.staff-head strong,
.customer-head strong {
    color: #2563eb;
    font-size: 18px;
    font-weight: 950;
}

.staff-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 10px;
}

.staff-summary span {
    padding: 8px 6px;
    border-radius: 10px;
    text-align: center;
    font-size: 11px;
    font-weight: 900;
}

.staff-summary .online {
    background: #dcfce7;
    color: #16a34a;
}

.staff-summary .idle {
    background: #fef3c7;
    color: #b45309;
}

.staff-summary .offline {
    background: #f1f5f9;
    color: #64748b;
}

.customer-live-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}

.customer-live-strip div {
    padding: 9px 8px;
    border-radius: 10px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
}

.customer-live-strip span {
    display: block;
    color: #64748b;
    font-size: 10px;
    font-weight: 850;
    line-height: 1.25;
    margin-bottom: 4px;
}

.customer-live-strip b {
    color: #2563eb;
    font-size: 17px;
    font-weight: 950;
}

.staff-list {
    display: grid;
    gap: 8px;
    max-height: 186px;
    overflow: auto;
    padding-right: 2px;
}

.staff-row {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    padding: 9px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
}

.staff-row img,
.staff-avatar {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    object-fit: cover;
    flex-shrink: 0;
}

.staff-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #dbeafe;
    color: #2563eb;
    font-size: 13px;
    font-weight: 950;
}

.staff-main {
    min-width: 0;
}

.staff-main b {
    display: block;
    color: #0f172a;
    font-size: 12px;
    font-weight: 900;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.staff-main small {
    display: block;
    color: #94a3b8;
    font-size: 10px;
    font-weight: 750;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.staff-row em {
    padding: 5px 8px;
    border-radius: 999px;
    font-size: 10px;
    font-style: normal;
    font-weight: 900;
    white-space: nowrap;
}

.staff-row em.online {
    background: #dcfce7;
    color: #16a34a;
}

.staff-row em.idle {
    background: #fef3c7;
    color: #b45309;
}

.staff-row em.offline {
    background: #f1f5f9;
    color: #64748b;
}

/* CHARTS ROW */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 16px;
    padding: 0 0 20px;
    align-items: stretch;
}

.right-col {
    display: flex;
    flex-direction: column;
    gap: 16px;
    height: 100%;
}

.card {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 20px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.045);
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
.operations-row {
    display: grid;
    grid-template-columns: 1.15fr 1fr 0.9fr;
    gap: 16px;
    padding: 0 0 20px;
}

.operation-panel {
    min-height: 250px;
}

.follow-list,
.stock-list {
    display: grid;
    gap: 10px;
}

.follow-item,
.stock-item {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
    color: #0f172a;
    text-decoration: none;
    transition: border-color 0.18s ease, transform 0.18s ease, background 0.18s ease;
}

.follow-item:hover,
.stock-item:hover,
.follow-item:focus-visible,
.stock-item:focus-visible {
    transform: translateY(-2px);
    background: #fff;
    border-color: #bfdbfe;
    outline: none;
}

.follow-item b,
.stock-item b {
    display: block;
    color: #0f172a;
    font-size: 12px;
    font-weight: 900;
    line-height: 1.35;
}

.follow-item span,
.stock-item span {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    margin-top: 4px;
}

.follow-right {
    display: grid;
    justify-items: end;
    align-content: center;
    gap: 6px;
    flex-shrink: 0;
}

.follow-right small {
    color: #94a3b8;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.stock-item strong {
    min-width: 38px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fef3c7;
    color: #b45309;
    font-size: 15px;
    font-weight: 900;
    flex-shrink: 0;
}

.stock-item strong.danger {
    background: #fee2e2;
    color: #dc2626;
}

.mini-section {
    padding: 10px 0 12px;
    border-top: 1px solid #f1f5f9;
}

.mini-section:first-of-type {
    border-top: none;
    padding-top: 0;
}

.mini-section > b {
    display: block;
    color: #0f172a;
    font-size: 12px;
    font-weight: 900;
    margin-bottom: 10px;
}

.mini-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 7px 0;
    color: #64748b;
    font-size: 11px;
    font-weight: 750;
}

.mini-row strong {
    color: #2563eb;
    font-size: 12px;
    font-weight: 900;
}

.empty-mini {
    color: #94a3b8;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 0;
}

.insight-charts-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    padding: 0 0 20px;
}

.insight-chart-card {
    min-height: 230px;
}

.section-header.compact {
    margin-bottom: 12px;
}

.mini-donut-summary {
    display: flex;
    justify-content: center;
    padding: 0 0 16px;
}

.mini-donut {
    --donut-value: 0%;
    width: 112px;
    height: 112px;
    padding: 14px;
    border-radius: 50%;
    background: conic-gradient(#3b82f6 0 var(--donut-value), #ec4899 var(--donut-value) 100%);
    transform: rotate(-90deg);
    box-shadow: 0 8px 22px rgba(59, 130, 246, 0.12);
}

.mini-donut > div {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    text-align: center;
    transform: rotate(90deg);
}

.mini-donut b {
    color: #0f172a;
    font-size: 20px;
    line-height: 1;
    font-weight: 900;
}

.mini-donut span {
    display: -webkit-box;
    max-width: 68px;
    overflow: hidden;
    color: #94a3b8;
    font-size: 8px;
    line-height: 1.2;
    font-weight: 700;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.horizontal-chart {
    display: grid;
    gap: 12px;
}

.chart-bar-row {
    display: grid;
    gap: 6px;
}

.chart-bar-row small {
    color: #94a3b8;
    font-size: 10px;
    font-weight: 800;
}

.chart-bar-meta {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
}

.chart-bar-meta span {
    min-width: 0;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chart-bar-meta b {
    color: #0f172a;
    font-size: 12px;
    font-weight: 900;
    flex-shrink: 0;
}

.chart-track {
    height: 8px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}

.chart-fill {
    display: block;
    height: 100%;
    min-width: 8px;
    border-radius: inherit;
    transition: width 0.24s ease;
}

.chart-fill.blue {
    background: linear-gradient(90deg, #2563eb, #60a5fa);
}

.chart-fill.green {
    background: linear-gradient(90deg, #16a34a, #86efac);
}

.chart-fill.warn {
    background: linear-gradient(90deg, #f59e0b, #fde68a);
}

.chart-fill.danger {
    background: linear-gradient(90deg, #dc2626, #fca5a5);
}

.chart-fill.soft {
    background: linear-gradient(90deg, #38bdf8, #bae6fd);
}

.bottom-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 0;
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
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }

    .performance-layout {
        grid-template-columns: 1fr;
    }

    .workbench-layout {
        grid-template-columns: 1fr;
    }

    .flow-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ops-grid,
    .analysis-grid,
    .task-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ops-card:first-child {
        grid-column: 1 / -1;
    }

    .charts-row {
        grid-template-columns: 1fr;
    }

    .bottom-row {
        grid-template-columns: 1fr;
    }

    .operations-row {
        grid-template-columns: 1fr;
    }

    .insight-charts-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .stats-grid {
        grid-template-columns: 1fr !important;
    }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
    }

    .dashboard-controls {
        align-items: stretch;
        flex-direction: column;
        padding: 0 0 16px;
    }

    .dashboard-controls .topbar-right {
        flex-wrap: wrap;
    }

    .dashboard-controls .search-box {
        flex: 1 1 190px;
    }

    .dashboard-controls .search-box input {
        width: 100%;
        height: 38px;
    }

    .charts-row,
    .operations-row,
    .insight-charts-row,
    .bottom-row {
        padding-left: 0;
        padding-right: 0;
    }

    .dashboard-cluster {
        margin-left: 0;
        margin-right: 0;
        padding: 16px;
    }

    .cluster-head {
        flex-direction: column;
        gap: 8px;
    }

    .staff-summary {
        grid-template-columns: 1fr;
    }

    .customer-live-strip {
        grid-template-columns: 1fr;
    }

    .staff-row {
        grid-template-columns: 34px minmax(0, 1fr);
    }

    .flow-grid {
        grid-template-columns: 1fr;
    }

    .staff-row em {
        grid-column: 2;
        justify-self: start;
    }

    .ops-grid,
    .analysis-grid,
    .task-grid,
    .insight-charts-row {
        grid-template-columns: 1fr;
    }

    .ops-card:first-child {
        grid-column: auto;
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


