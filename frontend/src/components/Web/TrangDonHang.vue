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
const currentPage = ref(1)
const ordersPerPage = 6

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

const getOrderStatusMeta = (status) => statusMap[String(status || '').toLowerCase()] || {
    label: 'Đang cập nhật',
    color: '#475569',
    bg: '#e2e8f0',
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

// New multi-file state for images + video
const refundImages = ref([])        // Array of { file, url }
const refundVideo = ref(null)       // { file, url } or null
const isDraggingOver = ref(false)

const ALLOWED_IMAGE_TYPES = ['image/jpeg','image/jpg','image/png','image/gif','image/webp','image/svg+xml','image/bmp','image/heic','image/heif']
const ALLOWED_VIDEO_TYPES = ['video/mp4','video/quicktime','video/avi','video/x-msvideo','video/webm','video/x-matroska','video/x-flv','video/3gpp','video/x-m4v','video/wmv']

const isAllowedImageType = (file) => ALLOWED_IMAGE_TYPES.includes(file.type) || /\.(jpeg|jpg|png|gif|webp|svg|bmp|heic|heif)$/i.test(file.name)
const isAllowedVideoType = (file) => ALLOWED_VIDEO_TYPES.includes(file.type) || /\.(mp4|mov|avi|wmv|webm|mkv|flv|3gp|m4v)$/i.test(file.name)

const processProofFiles = (files) => {
    const invalidFiles = []
    const images = []
    const videos = []

    files.forEach(file => {
        if (isAllowedImageType(file)) {
            images.push(file)
        } else if (isAllowedVideoType(file)) {
            videos.push(file)
        } else {
            invalidFiles.push(file.name)
        }
    })

    if (invalidFiles.length > 0) {
        swal.warning('File không hợp lệ', `Các file sau không được hỗ trợ và đã bị bỏ qua:\n${invalidFiles.join('\n')}\n\nChỉ chấp nhận ảnh (JPG, PNG, GIF, WEBP...) và video (MP4, MOV, AVI, WEBM...).`)
    }

    // Add images (append)
    images.forEach(file => {
        refundImages.value.push({ file, url: URL.createObjectURL(file) })
    })

    // Handle video: only 1 allowed
    if (videos.length > 0) {
        if (videos.length > 1) {
            swal.warning('Thông báo', 'Chỉ được gửi 1 video duy nhất. Chỉ video đầu tiên được chọn.')
        }
        if (refundVideo.value?.url) URL.revokeObjectURL(refundVideo.value.url)
        refundVideo.value = { file: videos[0], url: URL.createObjectURL(videos[0]) }
    }
}

const handleProofDragOver = (e) => {
    e.preventDefault()
    isDraggingOver.value = true
}

const handleProofDragLeave = () => {
    isDraggingOver.value = false
}

const handleProofDrop = (e) => {
    e.preventDefault()
    isDraggingOver.value = false
    const files = Array.from(e.dataTransfer.files || [])
    if (files.length > 0) processProofFiles(files)
}

const handleProofFileInput = (e) => {
    const files = Array.from(e.target.files || [])
    if (files.length > 0) processProofFiles(files)
    e.target.value = ''
}

const handleVideoFileInput = (e) => {
    const files = Array.from(e.target.files || [])
    if (files.length === 0) return
    const videoFiles = files.filter(f => isAllowedVideoType(f))
    const invalidFiles = files.filter(f => !isAllowedVideoType(f))
    if (invalidFiles.length > 0) {
        swal.warning('File không hợp lệ', `Chỉ chấp nhận file video (MP4, MOV, AVI, WEBM...).`)
    }
    if (videoFiles.length > 0) {
        if (refundVideo.value?.url) URL.revokeObjectURL(refundVideo.value.url)
        refundVideo.value = { file: videoFiles[0], url: URL.createObjectURL(videoFiles[0]) }
    }
    e.target.value = ''
}

const removeRefundImage = (index) => {
    const img = refundImages.value.splice(index, 1)[0]
    if (img?.url) URL.revokeObjectURL(img.url)
}

const removeRefundVideo = () => {
    if (refundVideo.value?.url) URL.revokeObjectURL(refundVideo.value.url)
    refundVideo.value = null
}

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
    // Clear new file state
    refundImages.value.forEach(img => { if (img?.url) URL.revokeObjectURL(img.url) })
    refundImages.value = []
    if (refundVideo.value?.url) URL.revokeObjectURL(refundVideo.value.url)
    refundVideo.value = null
    isDraggingOver.value = false
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
    if (!refundVideo.value) {
        swal.warning('Thông báo', 'Video bằng chứng là bắt buộc. Vui lòng tải lên 1 video.')
        return
    }

    isSubmitting.value = true
    try {
        const formData = new FormData()
        formData.append('lydo', refundReason.value)
        // Append images
        refundImages.value.forEach(img => formData.append('proofs[]', img.file))
        // Append video (required)
        formData.append('proofs[]', refundVideo.value.file)
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
    if (refundReason.value.trim() || refundImages.value.length > 0 || refundVideo.value || refundSelectedItems.value.length > 0) {
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
    refundImages.value.forEach(img => { if (img?.url) URL.revokeObjectURL(img.url) })
    refundImages.value = []
    if (refundVideo.value?.url) URL.revokeObjectURL(refundVideo.value.url)
    refundVideo.value = null
    isDraggingOver.value = false
    refundSelectedItems.value = []
}

const isRefundable = (order) => {
    if (order.trangthai !== 'done') return false;
    const updated = new Date(order.updated_at).getTime();
    if (!Number.isFinite(updated)) return false;
    const now = new Date().getTime();
    const diffDays = (now - updated) / (1000 * 60 * 60 * 24);
    return diffDays <= 7;
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

const DEFAULT_SHIPPING_FEE = 30000
const getPaymentData = (order = {}) => {
    const raw = order.du_lieu_thanh_toan || order.payment_data || {}
    if (typeof raw !== 'string') return raw || {}
    try { return JSON.parse(raw) || {} } catch { return {} }
}
const getShippingFee = (order) => Number(
    getPaymentData(order)?.checkout?.shipping_payable
    ?? getPaymentData(order)?.shipping_demo?.fee
    ?? DEFAULT_SHIPPING_FEE
)
const getItemsPayable = (order) => Math.max(0, Number(order?.tongtien || 0) - getShippingFee(order))
const getDepositSummary = (order) => {
    const payment = getPaymentData(order)
    const deposit = payment.deposit || {}
    const reportedPaid = Number(deposit.reported_paid_amount ?? payment.manual_notice_amount ?? 0)
    const explicitChatbot = payment.checkout?.chatbot_order === true
        || payment.checkout?.order_source === 'chatbot'
    const legacyChatbotDeposit = payment.manual_notice_method === 'momo_personal_qr'
        && reportedPaid > 0 && reportedPaid < Number(order?.tongtien || 0)
    const isChatbot = explicitChatbot || legacyChatbotDeposit
    const required = Number(deposit.required_amount || (isChatbot ? reportedPaid : 0))
    return {
        isChatbot,
        paid: reportedPaid,
        required,
        hasTransferNotice: Boolean(payment.manual_notice_at || deposit.reported_at),
        remaining: Number(deposit.remaining_due ?? Math.max(0, Number(order?.tongtien || 0) - reportedPaid)),
    }
}
const isChatbotDepositOrder = (order) => getDepositSummary(order).isChatbot

const filtered = computed(() => {
    if (pageMode.value === 'orders') {
        if (activeTab.value === 'all') return orders.value.filter(o => !String(o.trangthai || '').startsWith('refund'))
        return orders.value.filter(o => o.trangthai === activeTab.value)
    } else {
        if (activeTab.value === 'all_refund') return orders.value.filter(o => String(o.trangthai || '').startsWith('refund'))
        return orders.value.filter(o => o.trangthai === activeTab.value)
    }
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / ordersPerPage)))
const paginatedOrders = computed(() => {
    const start = (currentPage.value - 1) * ordersPerPage
    return filtered.value.slice(start, start + ordersPerPage)
})
const visiblePages = computed(() => {
    const start = Math.max(1, Math.min(currentPage.value - 1, totalPages.value - 2))
    const end = Math.min(totalPages.value, start + 2)
    return Array.from({ length: end - start + 1 }, (_, index) => start + index)
})

const switchPageMode = (mode) => {
    pageMode.value = mode
    activeTab.value = mode === 'orders' ? 'all' : 'all_refund'
}

watch([activeTab, pageMode], () => { currentPage.value = 1 })
watch(totalPages, (pages) => {
    if (currentPage.value > pages) currentPage.value = pages
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
                <div class="modal refund-modal">
                    <div class="modal-head">
                        <h2 class="modal-title">Yêu cầu hoàn trả</h2>
                        <button class="close-btn" no-guard @click="closeRefundModal">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 13px; font-weight: 600;">Chọn sản phẩm hoàn trả</label>
                            <div class="refund-items-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px;">
                                <div v-for="item in (orderToRefund?.chi_tiets || orderToRefund?.chiTiets || [])" :key="item.id_bienthe" class="refund-item-option" :class="{ selected: refundSelectedItems.includes(item.id_bienthe) }">
                                    <input class="refund-item-checkbox" type="checkbox" :id="'refund_item_' + item.id_bienthe" :value="item.id_bienthe" v-model="refundSelectedItems">
                                    <label :for="'refund_item_' + item.id_bienthe" class="refund-item-label">
                                        <img :src="getProductImage(item)" class="refund-item-image">
                                        <div class="refund-item-copy">
                                            <div class="refund-item-name">{{ getFullProductName(item) }}</div>
                                            <div class="refund-item-meta">
                                                <span>{{ item.bien_the?.ten_bienthe || 'Mặc định' }}</span>
                                                <span>Số lượng: {{ item.soluong }}</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="refund-reason-field">
                            <label class="form-label" for="refund-reason">Lý do hoàn trả</label>
                            <textarea id="refund-reason" v-model="refundReason" class="cancel-textarea" rows="3" placeholder="Mô tả rõ tình trạng và lý do bạn muốn hoàn trả..."></textarea>
                            <small>Thông tin cụ thể sẽ giúp yêu cầu được xử lý nhanh hơn.</small>
                        </div>
                        
                        <!-- ===== Upload ảnh / video bằng chứng ===== -->
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 13px; font-weight: 600; color: #1e293b;">Hình ảnh / Video bằng chứng</label>

                            <!-- Drag & Drop Zone -->
                            <div
                                class="refund-dropzone"
                                :class="{ 'drag-over': isDraggingOver }"
                                @dragover="handleProofDragOver"
                                @dragleave="handleProofDragLeave"
                                @drop="handleProofDrop"
                            >
                                <div class="dropzone-icon">
                                    <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                </div>
                                <p class="dropzone-text">Kéo thả ảnh &amp; video vào đây</p>
                                <p class="dropzone-hint">Hoặc chọn thủ công bên dưới</p>
                                <div class="dropzone-btns">
                                    <label class="dropzone-btn btn-images">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        Chọn ảnh
                                        <input type="file" multiple accept="image/*" @change="handleProofFileInput" style="display:none;" />
                                    </label>
                                    <label class="dropzone-btn btn-video">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                                        {{ refundVideo ? 'Thay video' : 'Chọn video' }}
                                        <input type="file" accept="video/*" @change="handleVideoFileInput" style="display:none;" />
                                    </label>
                                </div>
                            </div>

                            <!-- Hints -->
                            <div style="display:flex; gap:16px; margin-top:8px; flex-wrap:wrap;">
                                <small style="font-size:11px; color:#64748b;">📸 Ảnh: JPG, PNG, GIF, WEBP... (nhiều ảnh)</small>
                                <small style="font-size:11px; color:#ef4444; font-weight:600;">🎥 Video: MP4, MOV, WEBM... (bắt buộc, tối đa 1 video)</small>
                            </div>

                            <!-- Preview: Images -->
                            <div v-if="refundImages.length > 0" style="margin-top:12px;">
                                <div style="font-size:12px; font-weight:600; color:#374151; margin-bottom:8px;">📸 Ảnh đính kèm ({{ refundImages.length }})</div>
                                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                                    <div v-for="(img, idx) in refundImages" :key="idx" style="position:relative; width:80px; height:80px; border-radius:8px; overflow:hidden; border:2px solid #e2e8f0; flex-shrink:0;">
                                        <img :src="img.url" style="width:100%; height:100%; object-fit:cover;" />
                                        <button @click.prevent="removeRefundImage(idx)" no-guard style="position:absolute; top:2px; right:2px; width:20px; height:20px; border-radius:50%; background:rgba(239,68,68,0.9); color:#fff; border:none; cursor:pointer; font-size:12px; line-height:1; display:flex; align-items:center; justify-content:center; padding:0;">×</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview: Video (Required) -->
                            <div style="margin-top:12px;">
                                <div style="font-size:12px; font-weight:600; margin-bottom:6px;" :style="refundVideo ? 'color:#374151;' : 'color:#ef4444;'">
                                    🎥 Video bằng chứng <span style="font-size:11px;">(bắt buộc)</span>
                                    <span v-if="!refundVideo" style="font-size:11px; color:#ef4444;"> — Chưa có video</span>
                                </div>
                                <div v-if="refundVideo" style="position:relative; display:inline-block; max-width:100%;">
                                    <video :src="refundVideo.url" controls style="max-width:100%; max-height:180px; border-radius:8px; border:2px solid #3b82f6; display:block;"></video>
                                    <button @click.prevent="removeRefundVideo" no-guard style="position:absolute; top:6px; right:6px; padding:3px 8px; background:rgba(239,68,68,0.9); color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:11px; font-weight:600;">✕ Xóa</button>
                                    <div style="font-size:11px; color:#6b7280; margin-top:4px; word-break:break-all;">{{ refundVideo.file.name }}</div>
                                </div>
                                <div v-else style="padding:14px; background:#fef2f2; border:1px dashed #fca5a5; border-radius:8px; text-align:center; font-size:12px; color:#ef4444;">
                                    ⚠️ Vui lòng tải lên 1 video bằng chứng để tiếp tục
                                </div>
                            </div>
                        </div>

                        <div class="refund-modal-actions">
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
                <div class="modal detail-modal">
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
                            :style="{ color: getOrderStatusMeta(selectedOrder.trangthai).color, background: getOrderStatusMeta(selectedOrder.trangthai).bg }">
                            {{ getOrderStatusMeta(selectedOrder.trangthai).label }}
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

                        <div class="payment-breakdown">
                            <div><span>Tiền hàng</span><strong>{{ formatPrice(getItemsPayable(selectedOrder)) }}</strong></div>
                            <div><span>Phí giao hàng toàn quốc</span><strong>{{ formatPrice(getShippingFee(selectedOrder)) }}</strong></div>
                        </div>

                        <div v-if="isChatbotDepositOrder(selectedOrder)" class="chatbot-deposit-flow">
                            <div class="deposit-flow-head">
                                <span>🤖 Đơn đặt qua chatbot · Cọc 50%</span>
                                <b>{{ getDepositSummary(selectedOrder).hasTransferNotice ? 'Đang xác minh tiền cọc' : 'Chờ chuyển tiền cọc' }}</b>
                            </div>
                            <div class="deposit-flow-grid">
                                <div>
                                    <span>{{ getDepositSummary(selectedOrder).hasTransferNotice ? 'Đã báo chuyển khoản' : 'Cần chuyển khoản' }}</span>
                                    <strong>{{ formatPrice(getDepositSummary(selectedOrder).hasTransferNotice ? getDepositSummary(selectedOrder).paid : getDepositSummary(selectedOrder).required) }}</strong>
                                </div>
                                <div>
                                    <span>Còn trả khi nhận hàng</span>
                                    <strong>{{ formatPrice(getDepositSummary(selectedOrder).remaining) }}</strong>
                                </div>
                            </div>
                            <p>Khoản còn lại đã bao gồm phí giao hàng {{ formatPrice(getShippingFee(selectedOrder)) }}. Bạn không cần chuyển lại phần tiền cọc đã báo.</p>
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

            <!-- Main order type tabs + contextual status filters -->
            <div class="tabs-group-wrapper">
                <div class="order-type-tabs">
                    <button type="button" class="order-type-tab purchase" :class="{ active: pageMode === 'orders' }"
                        @click="switchPageMode('orders')">
                        <span>Mua</span>
                        <b>{{ orders.filter(o => !String(o.trangthai || '').startsWith('refund')).length }}</b>
                    </button>
                    <button type="button" class="order-type-tab refund" :class="{ active: pageMode === 'refund' }"
                        @click="switchPageMode('refund')">
                        <span>Hoàn trả</span>
                        <b>{{ orders.filter(o => String(o.trangthai || '').startsWith('refund')).length }}</b>
                    </button>
                </div>

                <div class="tabs status-tabs" style="margin-bottom: 0;">
                    <button v-for="tab in (pageMode === 'orders' ? tabs_mua : [{key: 'all_refund', label: 'Tất cả'}, ...tabs_hoantra])"
                        :key="tab.key" class="tab" :class="{ active: activeTab === tab.key }" @click="activeTab = tab.key">
                        {{ tab.label }}
                        <span class="tab-count" v-if="!['all', 'all_refund'].includes(tab.key)">
                            {{ orders.filter(o => o.trangthai === tab.key).length }}
                        </span>
                    </button>
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

                <div class="order-card" v-for="order in paginatedOrders" :key="order.id_dathang">
                    <div class="order-head">
                        <div class="order-meta">
                            <span class="order-id">#VT-2026-{{ String(order.id_dathang).padStart(3, '0') }}</span>
                            <span class="order-date">{{ new Date(order.created_at).toLocaleDateString('vi-VN') }}</span>
                        </div>
                        <span class="order-badge"
                            :style="{ color: getOrderStatusMeta(order.trangthai).color, background: getOrderStatusMeta(order.trangthai).bg }">
                            {{ getOrderStatusMeta(order.trangthai).label }}
                        </span>
                    </div>

                    <div class="order-items">
                        <div class="order-item" v-for="item in (order.chi_tiets || [])" :key="item.id_dathang_chi_tiet">
                            <img :src="getProductImage(item)" alt="product" />
                            <div class="order-item-info">
                                <p class="order-item-name">
                                    {{ getFullProductName(item) }}
                                    <span v-if="item.hoantien == 1" style="margin-left: 6px; font-size: 10px; font-weight: bold; color: #dc2626; background: #fee2e2; padding: 2px 5px; border-radius: 4px;">Đã hoàn trả</span>
                                </p>
                                <p class="order-item-variant">{{ item.bien_the?.ten_bienthe }}</p>
                                <p class="order-item-qty">x{{ item.soluong }}</p>
                            </div>
                            <p class="order-item-price">{{ formatPrice(item.gia) }}</p>
                        </div>
                    </div>

                    <div class="order-foot">
                        <div class="order-info-coins">
                            <span v-if="order.xu_dung > 0" style="color:#f59e0b; display:inline-flex; align-items:center; gap:3px;">🪙 Đã dùng: -{{ order.xu_dung.toLocaleString('vi-VN') }} xu</span>
                        </div>
                        <div class="order-checkout-summary">
                            <span class="shipping-fee-note">Tiền hàng {{ formatPrice(getItemsPayable(order)) }} · Phí ship {{ formatPrice(getShippingFee(order)) }}</span>
                            <span v-if="isChatbotDepositOrder(order)" class="chatbot-order-note">
                                🤖 Đơn chatbot:
                                <template v-if="getDepositSummary(order).hasTransferNotice">
                                    đã báo chuyển {{ formatPrice(getDepositSummary(order).paid) }} · còn trả khi nhận {{ formatPrice(getDepositSummary(order).remaining) }}
                                </template>
                                <template v-else>
                                    cần cọc {{ formatPrice(getDepositSummary(order).required) }}
                                </template>
                            </span>
                            <span class="order-total">Tổng thanh toán <strong>{{ formatPrice(order.tongtien) }}</strong></span>
                        </div>
                        <div class="order-actions">
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

                <nav v-if="!isLoading && filtered.length > ordersPerPage" class="orders-pagination" aria-label="Phân trang đơn hàng">
                    <div class="pagination-meta">
                        <span class="pagination-summary">
                            Hiển thị {{ (currentPage - 1) * ordersPerPage + 1 }}–{{ Math.min(currentPage * ordersPerPage, filtered.length) }} trong {{ filtered.length }} đơn
                        </span>
                        <small>Trang {{ currentPage }}/{{ totalPages }}</small>
                    </div>
                    <div class="pagination-buttons">
                        <button type="button" :disabled="currentPage === 1" @click="currentPage--" aria-label="Trang trước">‹</button>
                        <button v-for="pageNumber in visiblePages" :key="pageNumber" type="button"
                            :class="{ active: currentPage === pageNumber }" @click="currentPage = pageNumber">
                            {{ pageNumber }}
                        </button>
                        <button type="button" :disabled="currentPage === totalPages" @click="currentPage++" aria-label="Trang sau">›</button>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</template>

<style scoped>

/* ===== REFUND DROPZONE ===== */
.refund-dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 20px 16px;
    text-align: center;
    background: #f8fafc;
    transition: border-color 0.2s, background 0.2s;
    cursor: default;
    user-select: none;
}
.refund-dropzone.drag-over {
    border-color: #3b82f6;
    background: #eff6ff;
}
.dropzone-icon {
    color: #94a3b8;
    margin-bottom: 8px;
    display: flex;
    justify-content: center;
}
.refund-dropzone.drag-over .dropzone-icon {
    color: #3b82f6;
}
.dropzone-text {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 2px;
}
.dropzone-hint {
    font-size: 11px;
    color: #94a3b8;
    margin: 0 0 12px;
}
.dropzone-btns {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}
.dropzone-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    border: none;
    white-space: nowrap;
}
.dropzone-btn.btn-images {
    background: #e0f2fe;
    color: #0284c7;
    border: 1px solid #bae6fd;
}
.dropzone-btn.btn-images:hover {
    background: #bae6fd;
}
.dropzone-btn.btn-video {
    background: #fef3c7;
    color: #d97706;
    border: 1px solid #fde68a;
}
.dropzone-btn.btn-video:hover {
    background: #fde68a;
}
/* ===== END REFUND DROPZONE ===== */

.page {
    min-height: 100vh;
    background: #f8fafc !important;
    padding-block-start: 32px;
    padding-block-end: 60px;
}

.page .container {
    max-width: 1240px !important;
    margin: 0 auto !important;
    padding: 16px;
}

.site-container {
    max-width: 1240px;
    margin: auto;
}

.page-header {
    margin-bottom: 24px;
}

.page-title {
    font-family: 'Outfit', 'Inter', sans-serif;
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.5px;
    margin: 0 0 6px;
}

.page-sub {
    font-size: 13.5px;
    color: #64748b;
    margin: 0;
}

/* TABS */
.tabs-group-wrapper {
    margin-bottom: 24px;
}

.order-type-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;
}

.order-type-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 11px 32px;
    min-width: 140px;
    border-radius: 14px;
    border: 1.5px solid #cbd5e1;
    background: #ffffff;
    font-size: 14px;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}

.order-type-tab:hover {
    border-color: #2563eb;
    color: #2563eb;
    background: #eff6ff;
}

.order-type-tab.active {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border-color: transparent;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28);
}

.order-type-tab b {
    background: rgba(0, 0, 0, 0.08);
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 12px;
}

.order-type-tab.active b {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}

.tabs {
    display: flex;
    width: 100%;
    gap: 6px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 14px;
    padding: 6px;
    margin-bottom: 0;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.tab {
    flex: 1;
    min-width: max-content;
    padding: 10px 14px;
    border-radius: 10px;
    border: none;
    background: transparent;
    font-size: 13.5px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
}

.tab:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.tab.active {
    background: #2563eb;
    color: #fff;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
}

.tab-count {
    background: rgba(255, 255, 255, 0.25);
    padding: 1px 7px;
    border-radius: 99px;
    font-size: 11px;
}

.tab:not(.active) .tab-count {
    background: #e2e8f0;
    color: #334155;
}

/* ORDER CARDS */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.orders-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-top: 6px;
    padding: 16px 4px 2px;
    border-top: 1px solid #e2e8f0;
    background: transparent;
}

.pagination-meta {
    display: flex;
    align-items: center;
    gap: 10px;
}

.pagination-summary {
    color: #475569;
    font-size: 12.5px;
    font-weight: 600;
}

.pagination-meta small {
    padding-left: 10px;
    border-left: 1px solid #cbd5e1;
    color: #94a3b8;
    font-size: 11.5px;
    font-weight: 600;
}

.pagination-buttons {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.pagination-buttons button {
    width: 34px;
    height: 34px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: #334155;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.15s ease;
}

.pagination-buttons button:hover:not(:disabled),
.pagination-buttons button.active {
    border-color: #2563eb;
    background: #2563eb;
    color: #ffffff;
}

.pagination-buttons button:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.empty {
    text-align: center;
    padding: 80px 0;
    background: #ffffff;
    border-radius: 20px;
    border: 1.5px dashed #cbd5e1;
    color: #64748b;
}

.empty svg {
    width: 56px;
    height: 56px;
    stroke: #94a3b8;
    stroke-width: 1.5;
    fill: none;
    margin-bottom: 14px;
}

.empty p {
    font-size: 15px;
    font-weight: 600;
}

.order-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1.5px solid #cbd5e1;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
    overflow: hidden;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s ease, border-color 0.2s ease;
}

.order-card:hover {
    transform: translateY(-3px);
    border-color: #93c5fd;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.1);
}

.order-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.order-meta {
    display: flex;
    align-items: center;
    gap: 14px;
}

.order-id {
    font-family: 'Outfit', 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.2px;
}

.order-date {
    font-size: 12.5px;
    color: #64748b;
    font-weight: 500;
}

.order-badge {
    font-size: 12px;
    font-weight: 700;
    padding: 5px 14px;
    border-radius: 20px;
    letter-spacing: 0.2px;
    border: 1px solid transparent;
}

.order-items {
    padding: 16px 24px;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 0;
}

.order-item + .order-item {
    border-top: 1px dashed #e2e8f0;
}

.order-item img {
    width: 68px;
    height: 68px;
    border-radius: 12px;
    object-fit: contain;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 6px;
    flex-shrink: 0;
}

.order-item-info {
    flex: 1;
    min-width: 0;
}

.order-item-name {
    font-size: 14.5px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px;
    line-height: 1.45;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.order-item-variant {
    font-size: 12.5px;
    color: #64748b;
    margin: 0 0 4px;
    font-weight: 500;
}

.order-item-qty {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    margin: 0;
}

.order-item-price {
    font-family: 'Outfit', sans-serif;
    font-size: 15.5px;
    font-weight: 800;
    color: #2563eb;
    flex-shrink: 0;
}

.order-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    min-height: 68px;
    padding: 14px 24px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.order-info-coins {
    min-width: 0;
    color: #d97706;
    font-size: 12.5px;
    font-weight: 600;
}

.order-checkout-summary {
    padding-right: 18px;
    border-right: 1px solid #cbd5e1;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.shipping-fee-note { color: #64748b; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
.chatbot-order-note {
    max-width: 540px;
    color: #1d4ed8;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 7px;
    padding: 5px 8px;
    font-size: 11.5px;
    font-weight: 700;
    text-align: right;
}

.order-total {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
    color: #64748b;
    font-size: 12px;
    white-space: nowrap;
    font-weight: 500;
}

.order-total strong {
    font-family: 'Outfit', sans-serif;
    color: #0f172a;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -0.3px;
}

.order-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.order-actions button,
.btn-detail, .btn-reorder, .btn-cancel, .btn-refund {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 96px;
    height: 38px !important;
    min-height: 38px !important;
    padding: 8px 18px !important;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-detail {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    color: #334155;
}

.btn-detail:hover {
    border-color: #2563eb;
    color: #2563eb;
    background: #eff6ff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
}

.btn-reorder {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border: none;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28);
}

.btn-reorder:hover {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    box-shadow: 0 6px 18px rgba(37, 99, 235, 0.4);
    transform: translateY(-1.5px);
}

.btn-cancel {
    background: #ffffff;
    border: 1.5px solid #fca5a5;
    color: #dc2626;
}

.btn-cancel:hover {
    background: #fef2f2;
    border-color: #ef4444;
    color: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
}

.btn-refund {
    background: #ffffff;
    border: 1.5px solid #fdba74;
    color: #ea580c;
}

.btn-refund:hover {
    background: #fff7ed;
    border-color: #f97316;
    color: #ea580c;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.15);
}

@media (max-width: 1100px) {
  .order-foot {
    grid-template-columns: minmax(0, 1fr) auto;
  }
  .order-info-coins {
    grid-column: 1 / -1;
  }
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

.payment-breakdown { margin-top: 12px; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; display: grid; gap: 8px; }
.payment-breakdown > div { display: flex; justify-content: space-between; gap: 16px; color: #64748b; font-size: 13px; }
.payment-breakdown strong { color: #0f172a; }
.chatbot-deposit-flow { margin-top: 12px; padding: 14px; border: 1px solid #93c5fd; border-radius: 12px; background: linear-gradient(135deg, #eff6ff, #f0f9ff); }
.deposit-flow-head { display: flex; justify-content: space-between; gap: 12px; align-items: center; color: #1e3a8a; font-size: 13px; font-weight: 800; }
.deposit-flow-head b { color: #0369a1; background: #e0f2fe; border-radius: 999px; padding: 4px 8px; font-size: 11px; }
.deposit-flow-grid { margin-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.deposit-flow-grid > div { padding: 10px; border-radius: 9px; background: #fff; border: 1px solid #dbeafe; display: grid; gap: 4px; }
.deposit-flow-grid span { color: #64748b; font-size: 11.5px; }
.deposit-flow-grid strong { color: #1d4ed8; font-size: 15px; }
.chatbot-deposit-flow p { margin: 10px 0 0; color: #475569; font-size: 12px; line-height: 1.5; }

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
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    padding: 14px 16px;
  }
  .order-checkout-summary {
    padding: 0 0 12px;
    border-right: 0;
    border-bottom: 1px solid #dbe4f0;
  }
  .order-total {
    align-items: flex-start;
  }
  .order-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .order-actions button,
  .btn-detail, .btn-reorder, .btn-cancel, .btn-refund {
    width: 100%;
    text-align: center;
  }
  .orders-pagination {
    align-items: stretch;
    flex-direction: column;
  }
  .pagination-meta {
    justify-content: space-between;
  }
  .pagination-buttons {
    width: fit-content;
    align-self: center;
    justify-content: center;
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

/* Premium light order detail modal */
.refund-modal {
  display: flex;
  flex-direction: column;
  width: min(680px, calc(100vw - 32px));
  max-width: 680px;
  max-height: min(88vh, 820px);
  overflow: hidden;
  border: 1px solid #dbe4f0;
  border-radius: 20px;
  background: #ffffff;
  color: #172033;
  box-shadow: 0 28px 80px rgba(15, 23, 42, 0.25);
}
.refund-modal .modal-head {
  position: relative;
  z-index: 2;
  align-items: center;
  flex: 0 0 auto;
  padding: 18px 22px;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #ffffff 0%, #fff7ed 100%);
}
.refund-modal .modal-title {
  color: #0f172a;
  font-size: 19px;
  letter-spacing: -0.3px;
}
.refund-modal .close-btn {
  width: 30px !important;
  height: 30px !important;
  min-width: 30px;
  padding: 0 !important;
  border: 1px solid #dbe4f0 !important;
  border-radius: 50% !important;
  background: #f8fafc !important;
  color: #64748b !important;
  box-shadow: none !important;
  font-size: 18px;
  line-height: 1;
  transform: none !important;
}
.refund-modal .close-btn:hover {
  border-color: #dc2626 !important;
  background: #dc2626 !important;
  color: #ffffff !important;
  box-shadow: 0 6px 16px rgba(220, 38, 38, 0.38) !important;
  transform: scale(1.06) !important;
}
.refund-modal .modal-body {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  padding: 18px 22px 0;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}
.refund-modal .form-label {
  display: block;
  margin-bottom: 8px;
  color: #334155 !important;
  font-weight: 700 !important;
}
.refund-modal .refund-items-list {
  max-height: 170px !important;
  padding: 8px !important;
  border-color: #dbe4f0 !important;
  background: #f8fafc;
}
.refund-modal .refund-items-list > div {
  margin-bottom: 0 !important;
  padding: 8px !important;
  border-bottom: 0 !important;
  border-radius: 10px;
  background: #ffffff;
}
.refund-modal .refund-items-list > div + div { margin-top: 6px !important; }
.refund-modal .refund-item-option {
  display: grid;
  grid-template-columns: 20px minmax(0, 1fr);
  align-items: center;
  gap: 10px;
  border: 1px solid transparent;
  transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}
.refund-modal .refund-item-option:hover {
  border-color: #bfdbfe;
  background: #f8fbff;
}
.refund-modal .refund-item-option.selected {
  border-color: #60a5fa;
  background: #eff6ff;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.08);
}
.refund-item-checkbox {
  width: 18px;
  height: 18px;
  margin: 0;
  accent-color: #2563eb;
  cursor: pointer;
}
.refund-item-label {
  display: grid;
  grid-template-columns: 52px minmax(0, 1fr);
  align-items: center;
  gap: 12px;
  min-width: 0;
  margin: 0;
  cursor: pointer;
}
.refund-item-image {
  width: 52px;
  height: 52px;
  object-fit: cover;
  border: 1px solid #dbe4f0;
  border-radius: 9px;
  background: #ffffff;
}
.refund-item-copy { min-width: 0; }
.refund-item-name {
  overflow: hidden;
  color: #172033;
  font-size: 12.5px;
  font-weight: 700;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
.refund-item-meta {
  display: flex;
  gap: 8px;
  margin-top: 5px;
  color: #64748b;
  font-size: 10.5px;
}
.refund-item-meta span + span {
  padding-left: 8px;
  border-left: 1px solid #cbd5e1;
}
.refund-reason-field { margin: 16px 0; }
.refund-reason-field .cancel-textarea { margin-bottom: 0 !important; }
.refund-reason-field small {
  display: block;
  margin-top: 6px;
  color: #94a3b8;
  font-size: 10.5px;
}
.refund-modal .cancel-textarea {
  min-height: 88px;
  margin-bottom: 16px !important;
  border-color: #dbe4f0;
  background: #ffffff;
}
.refund-modal .refund-dropzone {
  padding: 16px;
  border-color: #bfdbfe;
  background: #f8fbff;
}
.refund-modal .dropzone-icon { margin-bottom: 4px; }
.refund-modal .dropzone-text { color: #1e293b; }
.refund-modal .refund-modal-actions {
  position: sticky;
  z-index: 3;
  bottom: 0;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin: 18px -22px 0;
  padding: 12px 22px;
  border-top: 1px solid #e2e8f0;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(8px);
}
.refund-modal-actions .btn {
  min-width: 108px;
  min-height: 38px;
  border-radius: 10px;
  font-size: 12px;
}
.refund-modal-actions .btn-warning {
  background: #f97316;
  color: #ffffff;
}
.refund-modal-actions .btn-warning:disabled {
  background: #cbd5e1;
  color: #ffffff;
  cursor: not-allowed;
}
@media (max-width: 576px) {
  .refund-modal { width: calc(100vw - 20px); max-height: 92vh; border-radius: 16px; }
  .refund-modal .modal-head { padding: 15px 16px; }
  .refund-modal .modal-body { padding: 15px 16px 0; }
  .refund-modal .refund-modal-actions { margin-inline: -16px; padding: 11px 16px; }
  .refund-modal-actions .btn { flex: 1; min-width: 0; }
  .refund-item-label { grid-template-columns: 44px minmax(0, 1fr); gap: 9px; }
  .refund-item-image { width: 44px; height: 44px; }
  .refund-item-meta { flex-direction: column; gap: 2px; }
  .refund-item-meta span + span { padding-left: 0; border-left: 0; }
}

.detail-modal {
  max-width: 680px;
  border: 1px solid #dbe4f0;
  border-radius: 20px;
  background: #ffffff;
  color: #172033;
  box-shadow: 0 28px 80px rgba(15, 23, 42, 0.24);
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}
.detail-modal .modal-head {
  align-items: center;
  padding: 20px 22px;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #ffffff 0%, #f6f9ff 100%);
}
.detail-modal .modal-title {
  color: #0f172a;
  font-size: 19px;
  letter-spacing: -0.3px;
}
.detail-modal .modal-id {
  margin-top: 4px;
  color: #64748b;
  font-size: 12px;
}
.detail-modal .close-btn {
  width: 36px;
  height: 36px;
  border: 1px solid #dbe4f0;
  background: #ffffff;
  color: #475569;
  transition: 0.15s ease;
}
.detail-modal .close-btn svg {
  width: 18px;
  height: 18px;
  stroke: currentColor;
  stroke-width: 2;
}
.detail-modal .close-btn:hover {
  border-color: #fecaca;
  background: #fef2f2;
  color: #dc2626;
  transform: rotate(4deg);
}
.detail-modal .modal-body { padding: 20px 22px 22px; }
.detail-modal .modal-status {
  margin-bottom: 16px;
  padding: 6px 12px;
  border: 1px solid rgba(37, 99, 235, 0.1);
}
.detail-modal .timeline {
  margin-bottom: 20px;
  padding: 18px 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
}
.detail-modal .tl-label { color: #334155; }
.detail-modal .tl-item.done .tl-label { color: #1d4ed8; }
.detail-modal .tl-date { color: #64748b; }
.detail-modal .section-title {
  color: #334155;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
}
.detail-modal .modal-item {
  gap: 14px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 13px;
  background: #f8fafc;
}
.detail-modal .modal-item img {
  width: 60px;
  height: 60px;
  border: 1px solid #dbe4f0;
  border-radius: 10px;
  background: #ffffff;
}
.detail-modal .modal-item-name {
  color: #172033;
  line-height: 1.4;
}
.detail-modal .modal-item-variant { color: #64748b; }
.detail-modal .modal-item-qty { color: #475569; }
.detail-modal .modal-item-price {
  color: #1d4ed8;
  white-space: nowrap;
}
.detail-modal .modal-breakdown { color: #475569 !important; }
.detail-modal .modal-total {
  margin-top: 6px;
  padding: 16px !important;
  border: 1px solid #dbeafe !important;
  border-radius: 13px;
  background: #eff6ff !important;
  color: #475569;
}/* Compact order status filters */
.tabs-group-wrapper {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 12px !important;
  margin-bottom: 24px !important;
  width: 100% !important;
}
.order-type-tabs {
  display: flex;
  align-items: center;
  gap: 10px;
}
.order-type-tab {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 40px;
  padding: 10px 28px !important;
  min-width: 130px !important;
  border-radius: 12px;
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  color: #475569;
  font-size: 13.5px !important;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.order-type-tab b {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 20px;
  padding: 0 7px;
  border-radius: 999px;
  background: #e2e8f0;
  color: #475569;
  font-size: 11px;
}
.order-type-tab.purchase.active {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: #ffffff;
  border-color: transparent;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28);
}
.order-type-tab.purchase.active b {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}
.order-type-tab.refund.active {
  background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
  color: #ffffff;
  border-color: transparent;
  box-shadow: 0 4px 14px rgba(234, 88, 12, 0.28);
}
.order-type-tab.refund.active b {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}
.status-tabs {
  width: 100% !important;
  max-width: 100% !important;
  display: flex !important;
}
.tabs-row { gap: 8px !important; width: 100% !important; }
.tabs {
  display: flex !important;
  width: 100% !important;
  gap: 6px !important;
  padding: 6px !important;
  border-radius: 14px !important;
  border: 1.5px solid #cbd5e1 !important;
  background: #ffffff !important;
}
.tab {
  flex: 1 1 0% !important;
  min-width: 0 !important;
  min-height: 38px !important;
  padding: 8px 12px !important;
  border-radius: 10px !important;
  font-size: 13px !important;
  font-weight: 600 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 6px !important;
  white-space: nowrap !important;
}
.tab-count {
  min-width: 20px;
  padding: 0 6px;
  font-size: 10px;
  line-height: 18px;
  text-align: center;
}
</style>
