<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import swal from '@/services/swal'
import ComboSelectionModal from './HopThoaiChonCombo.vue'
import { productImageUrl, comboImageUrl, imageFallbackUrl, storageUrl } from '@/services/urls'
import { getToken } from '@/services/auth'
import { isWishlisted, findWishlistItem, fetchWishlistState, wishlistItems } from '@/services/wishlistStore'
import { mapNewsPosts } from '@/services/newsMapper'
import { prefetchProductDetail, prefetchProductsPage, primeProductDetailFromCard } from '@/services/productsPrefetch'
import {
  Tag,
  Flame,
  TicketPercent,
  Truck,
  Copy,
  Check,
  ShoppingBag,
  Clock,
  ArrowRight,
  ShieldCheck,
  Cpu,
  Layers,
  Sparkles,
  Mail,
  Heart,
  BadgeAlert,
  Gift,
  ChevronLeft,
  ChevronRight,
  ChevronUp,
  Grid,
  Laptop,
  Apple as AppleIcon,
  Briefcase,
  Monitor,
  Star,
  AlertCircle,
  SlidersHorizontal,
  Percent
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()

// ===================== STATE MANAGEMENT =====================
const isLoading = ref(true)
const products = ref([])
const backendPromotions = ref([])
const newsletterEmail = ref('')
const activeCategoryTab = ref('all')

// Countdown timers state
const fsDays = ref('00')
const fsHours = ref('04')
const fsMinutes = ref('12')
const fsSeconds = ref('45')

const esHours = ref('01')
const esMinutes = ref('32')
const esSeconds = ref('08')

// Claimed voucher state
const claimedVoucherId = ref(null)
const claimingId = ref(null)
const copiedVoucherCode = ref(null)
// user's owned vouchers: { id_promotion, trang_thai, ngay_nhan, promotion: {...} }
const userVouchers = ref([])
// Set of promotion IDs user already owns with trang_thai != het_han
const ownedActiveIds = ref(new Set())
// Loading user vouchers
const isLoadingUserVouchers = ref(false)

// Combos State & Helpers
const combos = ref([])
const showComboModal = ref(false)
const selectedCombo = ref(null)

const openCombo = (combo) => {
  selectedCombo.value = combo
  showComboModal.value = true
}

const getComboImage = (combo) => comboImageUrl(combo, imageFallbackUrl)

const toFiniteNumber = (value) => {
  const number = Number.parseFloat(value)
  return Number.isFinite(number) ? number : 0
}

const getProductPrice = (product) => {
  const baseVariant = Array.isArray(product?.bien_thes) && product.bien_thes.length > 0
    ? product.bien_thes[0]
    : null
  return toFiniteNumber(
    product?.giaKM ||
    product?.gia_khuyen_mai ||
    product?.gia_km ||
    product?.giaSP ||
    product?.gia ||
    baseVariant?.gia_khuyen_mai ||
    baseVariant?.giaKM ||
    baseVariant?.gia
  )
}

const getProductOriginalPrice = (product, currentPrice) => {
  const baseVariant = Array.isArray(product?.bien_thes) && product.bien_thes.length > 0
    ? product.bien_thes[0]
    : null
  const originalPrice = toFiniteNumber(
    product?.gia_goc ||
    product?.giaGoc ||
    product?.giaSP ||
    product?.oldPrice ||
    baseVariant?.gia_goc ||
    baseVariant?.giaGoc
  )
  if (originalPrice > currentPrice) return originalPrice
  return currentPrice > 0 ? Math.floor(currentPrice * 1.2) : 0
}

const formatCurrency = (value) => {
  const number = toFiniteNumber(value)
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number)
}

const getDiscountPercent = (product) => {
  const price = toFiniteNumber(product?.gia)
  const oldPrice = toFiniteNumber(product?.oldPrice)
  if (price <= 0 || oldPrice <= price) return 0
  return Math.round((1 - price / oldPrice) * 100)
}

const hasValidPrice = (product) => toFiniteNumber(product?.gia) > 0

const getOriginalPrice = (combo) => {
  if (!combo.products) return 0
  return combo.products.reduce((sum, p) => {
    const firstVariantPrice = p.bien_thes?.[0]?.gia || p.gia || 0
    return sum + Number(firstVariantPrice)
  }, 0)
}

const getComboDiscountPercent = (combo) => {
  const orig = getOriginalPrice(combo)
  if (!orig || orig <= combo.giakhuyenmai) return 0
  return Math.round(((orig - combo.giakhuyenmai) / orig) * 100)
}

const getItemPrice = (item) => {
  return item.gia || item.giaSP || item.bien_thes?.[0]?.gia || 0
}

// Flash Sale State & Helpers
const flashSaleSession = ref(null)
const flashSaleProductsList = ref([])
const isFlashSaleActive = ref(false)

// Stats counters (for dynamic increment count animation)
const displayedProductsCount = ref(0)
const displayedVouchersCount = ref(0)

// ===================== MOCK DATA =====================
// High-fidelity fallback / static details
const statsData = [
  { value: '500+', label: 'Sản phẩm giảm giá', icon: Flame, color: '#ef4444' },
  { value: '100+', label: 'Voucher độc quyền', icon: TicketPercent, color: '#3b82f6' },
  { value: 'Mỗi ngày', label: 'Flash Sale giờ vàng', icon: Sparkles, color: '#00e5ff' },
  { value: 'Miễn phí', label: 'Vận chuyển toàn quốc', icon: Truck, color: '#2563eb' }
]

const fallbackVouchers = [
  { id: 'v1', code: 'NEXTGEN500', name: 'Giảm 500K', desc: 'Áp dụng cho đơn hàng Laptop Gaming từ 15 Triệu.', category: 'product', type: 'fixed', value: 500000, status: 'running' },
  { id: 'v2', code: 'NEXTGEN1M', name: 'Giảm 1 Triệu', desc: 'Đặc quyền mua cấu hình RTX 50-Series trở lên.', category: 'product', type: 'fixed', value: 1000000, status: 'running' },
  { id: 'v3', code: 'NEXTGEN0PCT', name: 'Trả góp 0%', desc: 'Hỗ trợ trả góp 0% qua thẻ tín dụng hoặc HD Saison.', category: 'payment', type: 'percentage', value: 0, status: 'running' },
  { id: 'v4', code: 'NEXTGENSHIP', name: 'Freeship tối đa 100K', desc: 'Miễn phí vận chuyển cho đơn hàng thanh toán trước.', category: 'shipping', type: 'fixed', value: 100000, status: 'running' }
]

const comboDetailsList = [
  {
    title: 'NextGen Cyberpunk Bundle',
    desc: 'Giải phóng sức mạnh tối đa của chiến binh Gaming với trọn bộ vũ khí cao cấp.',
    items: [
      { name: 'Chuột Gaming NextGen', img: 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=200&q=80' },
      { name: 'Bàn phím cơ NextGen', img: 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=200&q=80' },
      { name: 'Tai nghe NextGen 7.1', img: 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=200&q=80' }
    ],
    originalPrice: 4200000,
    comboPrice: 2200000,
    discountBadge: 'Tiết kiệm 2.000.000đ'
  },
  {
    title: 'NextGen Creator Bundle',
    desc: 'Thiết kế sang trọng, tối ưu hiệu suất làm việc đa nhiệm cho các nhà sáng tạo nội dung.',
    items: [
      { name: 'Chuột Logitech MX Master 3S', img: 'https://images.unsplash.com/photo-1625842268584-8f3290447001?w=200&q=80' },
      { name: 'Đế Tản Nhiệt Cao Cấp', img: 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=200&q=80' },
      { name: 'Cáp chuyển đổi HyperDrive 9-in-1', img: 'https://images.unsplash.com/photo-1468495244123-6c6c332eeece?w=200&q=80' }
    ],
    originalPrice: 5800000,
    comboPrice: 3800000,
    discountBadge: 'Tiết kiệm 2.000.000đ'
  }
]

const realNewsArticles = ref([])

const defaultMagazineArticles = [
  { id: 1, category: 'Tin khuyến mãi', title: 'Đại tiệc siêu sale công nghệ: Săn voucher 1 Triệu độc quyền NextGen Group', excerpt: 'Chi tiết lịch săn mã giảm giá và cách tối ưu hóa giỏ hàng để nhận ưu đãi lên tới 35% cho các dòng máy gaming flagship.', views: 5200, date: '03/06/2026', img: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=600&q=80' },
  { id: 2, category: 'Đánh giá công nghệ', title: 'Acer NextGen Helios Neo 16 có thực sự bá chủ phân khúc dưới 40 Triệu?', excerpt: 'Đánh giá chi tiết hiệu năng thực tế, nhiệt độ tỏa ra khi chơi game nặng và thời lượng pin thực tế của dòng Helios Neo 2026.', views: 3800, date: '01/06/2026', img: 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500&q=80' },
  { id: 3, category: 'Xu hướng phần cứng', title: 'Kiến trúc GPU Blackwell RTX 5090 hứa hẹn bước nhảy vọt như thế nào?', excerpt: 'Tổng hợp tất cả thông số rò rỉ, mức tiêu thụ điện năng dự kiến và hiệu năng Ray Tracing thế hệ mới của card đồ họa khủng nhất năm.', views: 2900, date: '29/05/2026', img: 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?w=500&q=80' },
  { id: 4, category: 'Công nghệ', title: 'Bảo quản pin laptop đúng cách: sạc, nhiệt độ và thói quen sử dụng', excerpt: 'Cẩm nang kéo dài tuổi thọ pin laptop, hạn chế chai pin tối đa khi sử dụng hàng ngày.', views: 2400, date: '25/05/2026', img: 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=500&q=80' },
  { id: 5, category: 'Sản phẩm', title: 'Cách chọn laptop đồ họa cho designer: màn hình, RAM và GPU cần biết', excerpt: 'Hướng dẫn chọn mua laptop đồ họa chuyên nghiệp cho thiết kế 2D, 3D, dựng phim và render.', views: 1900, date: '20/05/2026', img: 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=500&q=80' }
]

const magazineArticles = computed(() => {
  const source = realNewsArticles.value.length > 0 ? realNewsArticles.value : defaultMagazineArticles
  
  // Sort descending by views (lượt xem)
  const sorted = [...source].sort((a, b) => Number(b.views || b.luotxem || 0) - Number(a.views || a.luotxem || 0))

  return sorted.slice(0, 5).map(item => {
    let imageSrc = item.image || item.thumbnail || item.hinhanh || item.img || ''
    if (imageSrc && !imageSrc.startsWith('http')) imageSrc = storageUrl(imageSrc)
    if (!imageSrc) imageSrc = 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=600&q=80'
    return {
      id: item.id || item.slug || Math.random(),
      title: item.title || item.tieude || '',
      category: item.category || item.danhmuc || 'Tin tức',
      excerpt: item.excerpt || item.summary || item.tomtat || '',
      views: Number(item.views || item.luotxem || 0),
      date: item.published_at || item.dang_luc || item.created_at || item.date || '',
      img: imageSrc
    }
  })
})

const goToNewsDetail = (id) => {
  if (id) {
    router.push(`/tin-tuc/${id}`)
  }
}

const scrollToPromotionSection = () => {
  const sectionId = String(route.query.section || '')
  if (!sectionId) return
  nextTick(() => {
    window.setTimeout(() => {
      document.getElementById(sectionId)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }, 80)
  })
}

// ===================== LIFECYCLE METHODS =====================
onMounted(() => {
  fetchPromotionsData()
  startTimers()
  fetchUserVouchers()
})

watch(() => route.query.section, scrollToPromotionSection)

let scrollRevealListener = null

onUnmounted(() => {
  if (countdownInterval) clearInterval(countdownInterval)
  if (scrollRevealListener) window.removeEventListener('scroll', scrollRevealListener)
})

// ===================== DATA FETCHING =====================
async function fetchPromotionsData() {
  isLoading.value = true
  try {
    // 1. Load vouchers from backend API
    const promoResponse = await api.get('/promotions')
    if (promoResponse.data && Array.isArray(promoResponse.data.data)) {
      backendPromotions.value = promoResponse.data.data
    } else if (promoResponse.data && Array.isArray(promoResponse.data)) {
      backendPromotions.value = promoResponse.data
    }
    // 2. Load products from API
    const prodResponse = await api.get('/sanpham')
    let rawProducts = []
    if (prodResponse.data && Array.isArray(prodResponse.data.data)) {
      rawProducts = prodResponse.data.data
    } else if (prodResponse.data && Array.isArray(prodResponse.data)) {
      rawProducts = prodResponse.data
    }

    if (rawProducts.length > 0) {
      // Find products with active discount or fallback to first 8 products
      let filtered = rawProducts.filter(p => {
        const giaKM = toFiniteNumber(p.giaKM || p.gia_khuyen_mai || p.gia_km || p.bien_thes?.[0]?.gia_khuyen_mai || p.bien_thes?.[0]?.giaKM)
        const giaSP = toFiniteNumber(p.giaSP || p.gia || p.bien_thes?.[0]?.gia)
        return giaKM > 0 && giaSP > 0 && giaKM < giaSP
      })

      if (filtered.length === 0) {
        filtered = rawProducts.slice(0, 8)
      }

      products.value = filtered.map(p => {
        const baseVariant = (p.bien_thes && p.bien_thes.length > 0) ? p.bien_thes[0] : null
        const giaSP = getProductPrice(p)
        const oldPrice = getProductOriginalPrice(p, giaSP)
        
        // Extract specs attributes
        const generalSpecs = []
        if (p.bien_thes && p.bien_thes.length > 0) {
          try {
            const bt = p.bien_thes[0]
            const tt = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || [])
            if (Array.isArray(tt)) {
              tt.forEach(attr => {
                generalSpecs.push(attr.giatri)
              })
            }
          } catch (e) {}
        }

        // Image resolver

        let ram = '16GB'
        let ssd = '512GB'
        if (p.bien_thes && p.bien_thes.length > 0) {
          try {
            const bt = p.bien_thes[0]
            const tt = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || [])
            if (Array.isArray(tt)) {
              tt.forEach(attr => {
                const name = (attr.ten_thuoctinh || '').toLowerCase()
                if (name.includes('ram')) ram = attr.giatri
                if (name.includes('ssd') || name.includes('ổ cứng')) ssd = attr.giatri
              })
            }
          } catch (e) {}
        }

        // Extract thong_so_ky_thuat (real technical specs)
        let tskt = []
        try {
          tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || [])
        } catch (e) { tskt = [] }

        return {
          ...p,
          id: p.id_sanpham,
          id_sanpham: p.id_sanpham,
          id_bienthe: baseVariant?.id_bienthe || p.id_bienthe || null,
          tenSP: p.tenSP,
          brand: p.thuong_hieu?.ten_thuonghieu || p.thuonghieu?.tenTH || p.brand || 'ASUS',
          category: p.danh_muc?.ten_danhmuc || p.danhmuc?.tenDM || p.category || 'Laptop Gaming',
          gia: giaSP,
          oldPrice,
          thong_so_ky_thuat: Array.isArray(tskt) ? tskt : [],
          specs: generalSpecs.length > 0 ? generalSpecs.slice(0, 4) : [ram, ssd, 'IPS FHD'],
          image: productImageUrl(p, null, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500'),
          rating: p.rating_avg !== undefined && p.rating_avg !== null ? Number(p.rating_avg) : 4.8,
          reviews: p.rating_count !== undefined && p.rating_count !== null ? Number(p.rating_count) : 0,
          promo: p.mota_ngan || 'Tặng kèm Balo NextGen + Chuột Gaming',
          inStock: p.trangthai === 'hoat_dong' || p.soluong > 0
        }
      })
    } else {
      products.value = generateFallbackProducts()
    }

    // 3. Load combos from API
    try {
      const comboResponse = await api.get('/combos')
      if (comboResponse.data && Array.isArray(comboResponse.data.data)) {
        combos.value = comboResponse.data.data
      }
    } catch (comboErr) {
      console.error('Lỗi khi tải danh sách combos:', comboErr)
    }

    // Load news articles dynamically from API
    try {
      const newsRes = await api.get('/news', { params: { scope: 'public', per_page: 6 } })
      const items = newsRes.data?.data || newsRes.data?.items || newsRes.data?.posts || (Array.isArray(newsRes.data) ? newsRes.data : [])
      if (Array.isArray(items) && items.length > 0) {
        realNewsArticles.value = mapNewsPosts(items)
      }
    } catch (newsErr) {
      console.error('Lỗi khi tải tin tức:', newsErr)
    }

    // 4. Load current flash sale session from API
    try {
      const fsRes = await api.get('/flash-sale/current')
      if (fsRes.data && fsRes.data.success && fsRes.data.status === 'active') {
        flashSaleSession.value = fsRes.data.session
        flashSaleProductsList.value = (fsRes.data.products || []).map(p => {
          const limit = p.so_luong_gioi_han || 1
          const sold = p.so_luong_da_ban || 0
          const soldPercent = Math.min(Math.round((sold / limit) * 100), 100)
          const remainingCount = Math.max(limit - sold, 0)
          const gia = toFiniteNumber(p.gia_flash_sale || p.giaFlashSale || p.giaKM || p.gia_khuyen_mai || p.gia || p.bien_thes?.[0]?.gia)
          const oldPrice = getProductOriginalPrice(p, gia)
          return {
            ...p,
            id: p.id_sanpham || p.san_pham?.id_sanpham || p.id,
            id_sanpham: p.id_sanpham || p.san_pham?.id_sanpham || p.id,
            id_bienthe: p.id_bienthe || p.id_bien_the || p.bienthe?.id_bienthe || p.bien_the?.id_bienthe || p.bien_thes?.[0]?.id_bienthe || null,
            tenSP: p.tenSP || p.san_pham?.tenSP || p.ten_sanpham || 'Sản phẩm khuyến mãi',
            brand: p.thuong_hieu?.ten_thuonghieu || p.thuonghieu?.tenTH || p.brand || p.san_pham?.thuong_hieu?.ten_thuonghieu || 'NextGen',
            category: p.danh_muc?.ten_danhmuc || p.danhmuc?.tenDM || p.category || p.san_pham?.danh_muc?.ten_danhmuc || 'Khuyến mãi',
            gia,
            oldPrice,
            image: p.image || productImageUrl(p, null, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500'),
            soldPercent,
            remainingCount
          }
        })
        isFlashSaleActive.value = true
        startFlashSaleCountdown(fsRes.data.session.thoi_gian_ket_thuc)
      } else {
        isFlashSaleActive.value = false
        flashSaleSession.value = null
        flashSaleProductsList.value = []
      }
    } catch (fsErr) {
      console.error('Lỗi khi tải thông tin flash sale:', fsErr)
      isFlashSaleActive.value = false
      flashSaleSession.value = null
      flashSaleProductsList.value = []
    }
  } catch (err) {
    console.error('Lỗi khi tải dữ liệu trang Khuyến Mãi:', err)
    products.value = generateFallbackProducts()
  } finally {
    isLoading.value = false
    nextTick(() => {
      initScrollReveal()
      initStatsObserver()
      scrollToPromotionSection()
    })
  }
}

function generateFallbackProducts() {
  return [
    { id: 101, tenSP: 'Laptop Gaming ASUS ROG Strix G16 RTX 4060', brand: 'ASUS', category: 'Laptop Gaming', gia: 32990000, oldPrice: 39990000, specs: ['Intel Core i7', 'RTX 4060', '16GB RAM', '512GB SSD'], image: 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500', rating: 4.9, reviews: 42, promo: 'Tặng balo ROG + Chuột Gaming', inStock: true },
    { id: 102, tenSP: 'MacBook Pro 14 inch M3 Space Gray 2024', brand: 'Apple', category: 'MacBook', gia: 42990000, oldPrice: 49990000, specs: ['Apple M3 Chip', '16GB RAM', '512GB SSD', 'Liquid Retina'], image: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500', rating: 4.8, reviews: 29, promo: 'Ưu đãi trả góp 0% lãi suất', inStock: true },
    { id: 103, tenSP: 'Laptop Dell Alienware m16 R2 Premium', brand: 'Dell', category: 'Laptop Gaming', gia: 68990000, oldPrice: 79990000, specs: ['Intel Ultra i9', 'RTX 4070', '32GB RAM', '1TB SSD'], image: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500', rating: 5.0, reviews: 14, promo: 'Tặng chuột Alienware AW610M', inStock: true },
    { id: 104, tenSP: 'Laptop MSI Stealth 16 Studio Đồ Họa', brand: 'MSI', category: 'Workstation', gia: 54990000, oldPrice: 62900000, specs: ['Core i7', 'RTX 4060', '32GB RAM', '240Hz OLED'], image: 'https://images.unsplash.com/photo-1555617117-08c39bb051aa?w=500', rating: 4.7, reviews: 19, promo: 'Tặng kèm chuột MSI + Balo Stealth', inStock: true },
    { id: 105, tenSP: 'Laptop Lenovo Legion Slim 5 Core i7', brand: 'Lenovo', category: 'Laptop Gaming', gia: 35490000, oldPrice: 41990000, specs: ['Core i7', 'RTX 4060', '16GB RAM', '1TB SSD'], image: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=500', rating: 4.8, reviews: 33, promo: 'Tặng tai nghe Legion H200', inStock: true },
    { id: 106, tenSP: 'MacBook Air 15 inch M3 Midnight 2024', brand: 'Apple', category: 'MacBook', gia: 34990000, oldPrice: 38990000, specs: ['Apple M3 Chip', '16GB RAM', '512GB SSD', 'Retina IPS'], image: 'https://images.unsplash.com/photo-1527430253228-e93688616381?w=500', rating: 4.9, reviews: 56, promo: 'Tặng cáp chuyển đổi USB-C Multi', inStock: true },
    { id: 107, tenSP: 'Laptop Văn Phòng HP Envy x360 AI', brand: 'HP', category: 'Laptop Văn Phòng', gia: 22490000, oldPrice: 26900000, specs: ['AMD Ryzen 7', '16GB RAM', '512GB SSD', 'Cảm ứng 360 độ'], image: 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=500', rating: 4.7, reviews: 22, promo: 'Tặng túi chống sốc cao cấp', inStock: true },
    { id: 108, tenSP: 'Laptop ASUS Zenbook 14 OLED Ultra 7', brand: 'ASUS', category: 'Laptop Văn Phòng', gia: 28990000, oldPrice: 32900000, specs: ['Core Ultra 7', '16GB RAM', '1TB SSD', 'OLED 3K 120Hz'], image: 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?w=500', rating: 4.8, reviews: 45, promo: 'Tặng chuột không dây ASUS Silent', inStock: true }
  ]
}

// ===================== COMPUTED PROPERTIES =====================
// Helper: format date
function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(d)
}

function voucherTone(v) {
  const category = String(v?.danhmuc ?? v?.category ?? '').toLowerCase()
  const type = String(v?.loai ?? v?.type ?? '').toLowerCase()
  if (category === 'freeship' || category === 'shipping' || type === 'freeship') return 'shipping'
  if (category === 'payment') return 'payment'
  return 'product'
}

function voucherCategoryLabel(v) {
  const tone = voucherTone(v)
  if (tone === 'shipping') return 'Freeship'
  if (tone === 'payment') return 'Thanh toán'
  return 'Giảm giá'
}

function voucherValueLabel(v) {
  const type = String(v?.loai ?? v?.type ?? '').toLowerCase()
  const value = toFiniteNumber(v?.giatri ?? v?.value)
  if (type === 'percent' || type === 'percentage') return `${value}%`
  if (type === 'freeship' && value <= 0) return 'Miễn phí'
  if (type === 'maxprice') return `Tối đa ${formatCurrency(value)}`
  return value > 0 ? formatCurrency(value) : 'Ưu đãi'
}

function voucherEndDate(v) {
  return v?.ngayketthuc ?? v?.end_date ?? null
}

async function copyVoucherCode(v) {
  const code = String(v?.code || '')
  if (!code) return
  try {
    await navigator.clipboard.writeText(code)
    copiedVoucherCode.value = code
    window.setTimeout(() => {
      if (copiedVoucherCode.value === code) copiedVoucherCode.value = null
    }, 1600)
  } catch (_) {
    swal.info('Mã voucher', code)
  }
}

// Helper: check if a voucher is still valid (not expired)
function isVoucherValid(v) {
  if (!v.ngayketthuc) return true // no end date = always valid
  return new Date(v.ngayketthuc) >= new Date()
}

// Helper: check if a voucher has started
function isVoucherStarted(v) {
  if (!v.ngaybatdau) return true
  return new Date(v.ngaybatdau) <= new Date()
}

const allVouchers = computed(() => {
  const list = [...backendPromotions.value]
  // Only add fallbacks if no backend data
  if (list.length === 0) {
    fallbackVouchers.forEach(fv => {
      if (!list.some(v => v.code === fv.code)) list.push(fv)
    })
  }

  return list.filter(v => {
    // 0. Ẩn voucher không công khai (hỗ trợ cả tên trường cũ và tên trường API hiện tại)
    if (
      v.congkhai === 0 || v.congkhai === '0' || v.congkhai === false ||
      v.is_public === 0 || v.is_public === '0' || v.is_public === false
    ) return false

    // 1. Tuyệt đối không hiện voucher sinh nhật
    const voucherCategory = String(v.danhmuc ?? v.category ?? '').trim().toLowerCase()
    if (voucherCategory === 'birthday') return false

    // 2. Voucher phải còn hạn
    if (!isVoucherValid(v) || !isVoucherStarted(v)) return false

    // 3. Nếu user chưa đăng nhập → hiện tất cả voucher còn hạn
    const token = getToken()
    if (!token) return true

    // 4. Nếu user đã đăng nhập:
    // - Chưa sở hữu → hiện
    // - Đã sở hữu (bất kể trạng thái) → không hiện
    const owned = userVouchers.value.find(uv =>
      Number(uv.id_voucher) === Number(v.id)
    )
    if (owned) return false // đã sở hữu -> không hiện
    return true // chưa sở hữu -> hiện
  })
})

// ===================== VOUCHER SLIDE & VIEW ALL PAGINATION =====================
const isViewAllVouchers = ref(false)
const currentVoucherSlide = ref(0)
const voucherItemsPerPage = 4

const totalVoucherSlides = computed(() => {
  if (!allVouchers.value || allVouchers.value.length === 0) return 1
  return Math.ceil(allVouchers.value.length / voucherItemsPerPage)
})

const currentSlideVouchers = computed(() => {
  if (isViewAllVouchers.value) return allVouchers.value
  const start = currentVoucherSlide.value * voucherItemsPerPage
  return allVouchers.value.slice(start, start + voucherItemsPerPage)
})

const nextVoucherSlide = () => {
  if (currentVoucherSlide.value < totalVoucherSlides.value - 1) {
    currentVoucherSlide.value++
  } else {
    currentVoucherSlide.value = 0
  }
}

const prevVoucherSlide = () => {
  if (currentVoucherSlide.value > 0) {
    currentVoucherSlide.value--
  } else {
    currentVoucherSlide.value = totalVoucherSlides.value - 1
  }
}

const toggleViewAllVouchers = () => {
  isViewAllVouchers.value = !isViewAllVouchers.value
  if (!isViewAllVouchers.value) {
    currentVoucherSlide.value = 0
  }
}

const filteredProducts = computed(() => {
  if (activeCategoryTab.value === 'all') return products.value
  return products.value.filter(p => {
    const cat = p.category.toLowerCase()
    const name = p.tenSP.toLowerCase()
    if (activeCategoryTab.value === 'gaming') {
      return cat.includes('gaming') || cat.includes('chơi game') || name.includes('rog') || name.includes('legion') || name.includes('alienware')
    }
    if (activeCategoryTab.value === 'macbook') {
      return cat.includes('macbook') || p.brand.toLowerCase() === 'apple'
    }
    if (activeCategoryTab.value === 'office') {
      return cat.includes('văn phòng') || cat.includes('học sinh') || name.includes('zenbook') || name.includes('envy')
    }
    if (activeCategoryTab.value === 'workstation') {
      return cat.includes('workstation') || cat.includes('máy trạm') || name.includes('stealth') || name.includes('studio') || name.includes('proart')
    }
    if (activeCategoryTab.value === 'accessories') {
      return cat.includes('accessor') || cat.includes('ph\u1ee5') || name.includes('chu\u1ed9t') || name.includes('b\u00e0n ph\u00edm') || name.includes('tai nghe') || name.includes('keyboard') || name.includes('mouse') || name.includes('headset') || name.includes('balo') || name.includes('hub')
    }
    return true
  })
})

const flashSaleProducts = computed(() => {
  return flashSaleProductsList.value
})

// ===================== PRODUCT FULL TITLE (Tên SP + Thông số kỹ thuật) =====================
const getProductFullTitle = (prod) => {
  if (!prod) return ''
  const baseName = prod.tenSP || ''
  // Ưu tiên lấy từ thong_so_ky_thuat (thông số kỹ thuật thật)
  let specValues = []
  if (Array.isArray(prod.thong_so_ky_thuat) && prod.thong_so_ky_thuat.length > 0) {
    specValues = prod.thong_so_ky_thuat.map(s => s.giatri || '').filter(Boolean)
  }
  // Nếu không có thong_so_ky_thuat → fallback về specs (biến thể)
  if (specValues.length === 0 && Array.isArray(prod.specs) && prod.specs.length > 0) {
    specValues = prod.specs.map(s => (typeof s === 'string' ? s : s?.value || '')).filter(Boolean)
  }
  // Chỉ ghép thêm những specs chưa có trong tên sản phẩm
  const missingSpecs = specValues.filter(val => val && !baseName.toLowerCase().includes(val.toLowerCase()))
  if (missingSpecs.length > 0) {
    return `${baseName} ${missingSpecs.join(' ')}`
  }
  return baseName
}

// ===================== TIMERS CONTROLLER =====================
let countdownInterval = null
const startTimers = () => {
  // Empty stub since we now start timers dynamically when active session is fetched
}

const startFlashSaleCountdown = (endTimeStr) => {
  if (countdownInterval) clearInterval(countdownInterval)
  
  const updateTimer = () => {
    const now = new Date().getTime()
    const end = new Date(endTimeStr).getTime()
    const diff = end - now
    
    if (diff <= 0) {
      fsDays.value = '00'
      fsHours.value = '00'
      fsMinutes.value = '00'
      fsSeconds.value = '00'
      isFlashSaleActive.value = false
      if (countdownInterval) clearInterval(countdownInterval)
      return
    }
    
    const days = Math.floor(diff / (1000 * 60 * 60 * 24))
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
    const seconds = Math.floor((diff % (1000 * 60)) / 1000)
    
    fsDays.value = String(days).padStart(2, '0')
    fsHours.value = String(hours).padStart(2, '0')
    fsMinutes.value = String(minutes).padStart(2, '0')
    fsSeconds.value = String(seconds).padStart(2, '0')
  }
  
  updateTimer()
  countdownInterval = setInterval(updateTimer, 1000)
}

// ===================== INTERACTIVE ACTIONS =====================
// Fetch user's already-owned vouchers
async function fetchUserVouchers() {
  const token = getToken()
  if (!token) return
  isLoadingUserVouchers.value = true
  try {
    const res = await api.get('/user/vouchers')
    userVouchers.value = Array.isArray(res.data?.vouchers)
      ? res.data.vouchers
      : Array.isArray(res.data) ? res.data : []
    // Build set of active-owned IDs
    ownedActiveIds.value = new Set(
      userVouchers.value
        .filter(uv => {
          const s = String(uv.trang_thai)
          return s !== '2' && s !== 'het_han' && s !== 'expired'
        })
        .map(uv => Number(uv.id_voucher))
    )
  } catch (e) {
    // Not logged in or error → ignore
  } finally {
    isLoadingUserVouchers.value = false
  }
}

// Claim (nhận) a voucher
const claimVoucher = async (v) => {
  const token = getToken()
  if (!token) {
    swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để nhận voucher!')
    router.push('/dang-nhap')
    return
  }
  if (claimingId.value === v.id) return
  claimingId.value = v.id
  try {
    await api.post('/user/vouchers/claim', { id_voucher: v.id })
    claimedVoucherId.value = v.id
    swal.success('Chúc mừng!', 'Chúc mừng nhận voucher thành công, đã lưu vô thông tin cá nhân!')
    await fetchUserVouchers()
    setTimeout(() => {
      if (claimedVoucherId.value === v.id) claimedVoucherId.value = null
    }, 3000)
  } catch (err) {
    const msg = err.response?.data?.message || 'Không thể nhận voucher này.'
    swal.error('Lỗi', msg)
  } finally {
    claimingId.value = null
  }
}

const toggleWishlist = async (product) => {
  const token = getToken()
  if (!token) {
    swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để lưu sản phẩm yêu thích!')
    router.push('/dang-nhap')
    return
  }

  try {
    const existing = findWishlistItem(product)
    if (existing) {
      await api.delete(`/yeu-thich/xoa/${existing.id}`)
      await fetchWishlistState()
      window.dispatchEvent(new Event('wishlist-updated'))
      swal.toast('Đã bỏ sản phẩm khỏi danh sách yêu thích', 'success')
      return
    }

    const variantId = product.id_bienthe || product.id
    await api.post('/yeu-thich/them', {
      id_bienthe: variantId,
      soluong: 1
    })
    await fetchWishlistState()
    window.dispatchEvent(new Event('wishlist-updated'))
    swal.toast('Đã thêm vào sản phẩm yêu thích', 'success')
  } catch (err) {
    console.error('Lỗi yêu thích:', err)
    swal.error('Thông báo', err.response?.data?.message || 'Đã xảy ra sự cố.')
  }
}

const addToCart = async (product) => {
  const token = getToken()
  if (!token) {
    swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để tiến hành mua ngay!')
    router.push({ path: '/dang-nhap', query: { redirect: '/thanh-toan' } })
    return
  }
  try {
    const variantId = product.id_bienthe
    if (!variantId) {
      swal.error('Thất bại', 'Sản phẩm này chưa có biến thể.')
      return
    }
    const res = await api.post('/gio-hang/them', {
      id_bienthe: variantId,
      soluong: 1
    })
    window.dispatchEvent(new Event('cart-updated'))

    const cartItemId = res?.data?.id_giohang || res?.data?.item?.id_giohang || res?.data?.data?.id_giohang || ''
    if (cartItemId) {
      router.push(`/thanh-toan?buy_now=1&cart_item=${cartItemId}`)
    } else {
      router.push(`/thanh-toan?buy_now=1&variant=${variantId}`)
    }
  } catch (err) {
    console.error('Lỗi mua hàng:', err)
    swal.error('Thất bại', err.response?.data?.message || 'Không thể tiến hành mua sản phẩm.')
  }
}

const warmDetail = (product) => {
  if (!product || typeof product !== 'object') return
  primeProductDetailFromCard(product)
  const productId = product.id_sanpham || product.id || product.san_pham?.id_sanpham
  if (productId) prefetchProductDetail(productId).catch(() => {})
}

const goToDetail = (product) => {
  const productId = typeof product === 'object'
    ? (product.id_sanpham || product.id || product.san_pham?.id_sanpham)
    : product
  const variantId = typeof product === 'object'
    ? (product.id_bienthe || product.id_bien_the || product.bienthe?.id_bienthe || product.bien_the?.id_bienthe)
    : null

  if (!productId) {
    swal.error('Không mở được sản phẩm', 'Sản phẩm này chưa có mã chi tiết hợp lệ.')
    return
  }

  warmDetail(product)

  router.push({
    path: `/san-pham/${productId}`,
    query: variantId ? { variant: variantId } : {}
  })
}

const submitNewsletter = async () => {
  if (!newsletterEmail.value || !newsletterEmail.value.includes('@')) {
    swal.info('Email không hợp lệ', 'Vui lòng nhập địa chỉ email chính xác.')
    return
  }
  try {
    await api.post('/subscribe', { email: newsletterEmail.value })
    swal.success('Đăng ký thành công', 'Cảm ơn bạn đã quan tâm tới các ưu đãi của NextGen!')
    newsletterEmail.value = ''
  } catch (err) {
    swal.success('Đăng ký thành công', 'Hệ thống đã lưu thông tin email của bạn!')
    newsletterEmail.value = ''
  }
}

// ===================== ANIMATIONS & OBSERVERS =====================
const initStatsObserver = () => {
  const statsSection = document.querySelector('.stats-banner-wrapper')
  if (!statsSection) return

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounters()
        observer.unobserve(entry.target)
      }
    })
  }, { threshold: 0.15 })

  observer.observe(statsSection)
}

const animateCounters = () => {
  const targetProducts = 500
  const targetVouchers = 100

  let currentProducts = 0
  const prodInterval = setInterval(() => {
    currentProducts += Math.ceil(targetProducts / 30)
    if (currentProducts >= targetProducts) {
      displayedProductsCount.value = targetProducts
      clearInterval(prodInterval)
    } else {
      displayedProductsCount.value = currentProducts
    }
  }, 25)

  let currentVouchers = 0
  const vouchInterval = setInterval(() => {
    currentVouchers += Math.ceil(targetVouchers / 30)
    if (currentVouchers >= targetVouchers) {
      displayedVouchersCount.value = targetVouchers
      clearInterval(vouchInterval)
    } else {
      displayedVouchersCount.value = currentVouchers
    }
  }, 35)
}

const initScrollReveal = () => {
  const revealElements = document.querySelectorAll('.scroll-reveal')
  
  scrollRevealListener = () => {
    revealElements.forEach(el => {
      const rect = el.getBoundingClientRect()
      const windowHeight = window.innerHeight
      if (rect.top <= windowHeight * 0.88) {
        el.classList.add('active')
      }
    })
  }

  window.addEventListener('scroll', scrollRevealListener)
  setTimeout(scrollRevealListener, 100)
}
</script>

<template>
  <div class="promotions-shell">
    <!-- 1. HERO BANNER -->
    <section class="hero-banner-section">
      <div class="tech-matrix-overlay"></div>
      <div class="ambient-glow-orb orb-top-left"></div>
      <div class="ambient-glow-orb orb-bottom-right"></div>

      <div class="grid-container hero-layout-grid">
        <div class="hero-intro-text scroll-reveal reveal-fade-up">
          <span class="ambient-label">
            <Sparkles class="pill-icon" />
            NextGen Premium Hub
          </span>
          <h1>
            Trung tâm
            <span class="gradient-text">ưu đãi công nghệ</span>
          </h1>
          <p class="hero-description">
            Khám phá hàng trăm ưu đãi đặc quyền dành cho Laptop Gaming, MacBook Pro và Workstation cấu hình cực khủng. Nâng tầm hiệu suất, tối ưu ngân sách.
          </p>

          <div class="hero-buttons">
            <a href="#discount-grid" class="btn btn-primary-glass">
              <ShoppingBag class="btn-icon" />
              Xem ưu đãi
            </a>
            <a href="#flash-sale" class="btn btn-secondary-neon">
              <Flame class="btn-icon" />
              Săn flash sale
            </a>
          </div>
        </div>

        <div class="hero-device-wrapper scroll-reveal reveal-scale-up">
          <div class="device-showcase-card">
            <img
              src="https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&q=80"
              alt="NextGen Showcase Hardware"
              class="showcase-image"
            />
            <div class="hardware-glow-edge"></div>
            <div class="product-floating-badge">
              <Percent class="badge-icon" />
              <span>Up to -35%</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 2. STATS OVERVIEW -->
    <section class="stats-banner-wrapper">
      <div class="grid-container stats-grid">
        <div class="stat-box-card scroll-reveal reveal-fade-up">
          <div class="stat-visual">
            <Flame class="stat-icon" style="color: #ef4444;" />
          </div>
          <div class="stat-info">
            <h3>{{ displayedProductsCount > 0 ? `${displayedProductsCount}+` : '0+' }}</h3>
            <p>Sản phẩm giảm giá</p>
          </div>
        </div>

        <div class="stat-box-card scroll-reveal reveal-fade-up" style="transition-delay: 0.1s;">
          <div class="stat-visual">
            <TicketPercent class="stat-icon" style="color: #3b82f6;" />
          </div>
          <div class="stat-info">
            <h3>{{ displayedVouchersCount > 0 ? `${displayedVouchersCount}+` : '0+' }}</h3>
            <p>Voucher độc quyền</p>
          </div>
        </div>

        <div class="stat-box-card scroll-reveal reveal-fade-up" style="transition-delay: 0.2s;">
          <div class="stat-visual">
            <Clock class="stat-icon" style="color: #00e5ff;" />
          </div>
          <div class="stat-info">
            <h3>Flash Sale</h3>
            <p>Làm mới mỗi ngày</p>
          </div>
        </div>

        <div class="stat-box-card scroll-reveal reveal-fade-up" style="transition-delay: 0.3s;">
          <div class="stat-visual">
            <Truck class="stat-icon" style="color: #2563eb;" />
          </div>
          <div class="stat-info">
            <h3>Miễn phí ship</h3>
            <p>Toàn quốc từ 15 triệu</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 3. CATEGORIES SECTION -->
    <section class="section categories-section">
      <div class="grid-container">
        <div class="section-header scroll-reveal reveal-fade-up">
          <span class="ambient-label">
            <SlidersHorizontal class="pill-icon" />
            Danh mục khuyến mãi
          </span>
          <h2>Săn ưu đãi theo nhu cầu</h2>
          <p class="section-sub" style="color: #f1f5f9 !important; -webkit-text-fill-color: #f1f5f9 !important; opacity: 1 !important;">Những dòng laptop hiệu năng cao, linh kiện chất lượng nhất đang được áp dụng mức giá cực sốc.</p>
        </div>

        <div class="categories-bento-grid scroll-reveal reveal-stagger">
          <div class="bento-item gaming-bento" @click="activeCategoryTab = 'gaming'">
            <div class="bento-image-bg">
              <img src="https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=600&q=80" alt="Laptop Gaming Sale" />
            </div>
            <div class="bento-content-overlay">
              <div class="bento-badge">Gaming Pro</div>
              <h3>Laptop Gaming Sale</h3>
              <a href="#discount-grid" class="bento-buy-btn" @click.stop="activeCategoryTab = 'gaming'">Mua ngay</a>
              <span class="bento-link">
                Khám phá ngay
                <ChevronRight class="chevron-link-icon" />
              </span>
            </div>
          </div>

          <div class="bento-item mac-bento" @click="activeCategoryTab = 'macbook'">
            <div class="bento-image-bg">
              <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&q=80" alt="MacBook Pro Sale" />
            </div>
            <div class="bento-content-overlay">
              <div class="bento-badge">Apple Premium</div>
              <h3>MacBook Pro / Air</h3>
              <a href="#discount-grid" class="bento-buy-btn" @click.stop="activeCategoryTab = 'macbook'">Mua ngay</a>
              <span class="bento-link">
                Khám phá ngay
                <ChevronRight class="chevron-link-icon" />
              </span>
            </div>
          </div>

          <div class="bento-item office-bento" @click="activeCategoryTab = 'office'">
            <div class="bento-image-bg">
              <img src="https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=600&q=80" alt="Laptop Văn Phòng Sale" />
            </div>
            <div class="bento-content-overlay">
              <div class="bento-badge">Office & Student</div>
              <a href="#discount-grid" class="bento-buy-btn" @click.stop="activeCategoryTab = 'office'">Mua ngay</a>
              <h3>Laptop Văn Phòng</h3>
              <span class="bento-link">
                Khám phá ngay
                <ChevronRight class="chevron-link-icon" />
              </span>
            </div>
          </div>

          <div class="bento-item workstation-bento" @click="activeCategoryTab = 'workstation'">
            <div class="bento-image-bg">
              <img src="/hero_gaming_parts.png" alt="Workstation Sale" />
            </div>
            <div class="bento-content-overlay">
              <div class="bento-badge">Extreme Performance</div>
              <a href="#discount-grid" class="bento-buy-btn" @click.stop="activeCategoryTab = 'workstation'">Mua ngay</a>
              <h3>Máy Trạm Đồ Họa</h3>
              <span class="bento-link">
                Khám phá ngay
                <ChevronRight class="chevron-link-icon" />
              </span>
            </div>
          </div>

          <div class="bento-item accessories-bento" @click="activeCategoryTab = 'accessories'">
            <div class="bento-image-bg">
              <img src="/elite_accessories.png" alt="Accessories Sale" />
            </div>
            <div class="bento-content-overlay">
              <div class="bento-badge">Accessories Deal</div>
              <h3>Gaming Accessories</h3>
              <a href="#discount-grid" class="bento-buy-btn" @click.stop="activeCategoryTab = 'accessories'">Mua ngay</a>
              <span class="bento-link">
                Khám phá ngay
                <ChevronRight class="chevron-link-icon" />
              </span>
            </div>
          </div>

          <div class="bento-item setup-bento" @click="activeCategoryTab = 'accessories'">
            <div class="bento-image-bg">
              <img src="/elite_unboxing.png" alt="Setup bundle sale" />
            </div>
            <div class="bento-content-overlay">
              <div class="bento-badge">Setup Bundle</div>
              <h3>Setup Gear Sale</h3>
              <a href="#discount-grid" class="bento-buy-btn" @click.stop="activeCategoryTab = 'accessories'">Mua ngay</a>
              <span class="bento-link">
                Khám phá ngay
                <ChevronRight class="chevron-link-icon" />
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 4. FLASH SALE TODAY -->
    <section id="flash-sale" class="section flash-sale-dark-section" v-if="isFlashSaleActive && flashSaleProducts && flashSaleProducts.length">
      <div class="grid-container">
        <div class="flash-header-row scroll-reveal reveal-fade-up">
          <div class="flash-header-left">
            <span class="ambient-label flash-badge">
              <Flame class="pill-icon" />
              Flash sale hôm nay
            </span>
            <h2>Cơ hội cuối - chớp mắt là bỏ lỡ</h2>
          </div>

          <div class="countdown-clock">
            <span class="clock-label">Kết thúc sau:</span>
            <div class="timer-numbers">
              <span class="timer-segment">{{ fsDays }}</span>
              <span class="timer-colon">:</span>
              <span class="timer-segment">{{ fsHours }}</span>
              <span class="timer-colon">:</span>
              <span class="timer-segment">{{ fsMinutes }}</span>
              <span class="timer-colon">:</span>
              <span class="timer-segment">{{ fsSeconds }}</span>
            </div>
          </div>
        </div>

        <div class="flash-sale-grid scroll-reveal reveal-stagger">
          <div v-for="prod in flashSaleProducts" :key="prod.id" class="flash-sale-card" @click="goToDetail(prod)">
            <div class="flash-img-box">
              <img :src="prod.image" :alt="prod.tenSP" />
              <div class="discount-absolute-badge" v-if="getDiscountPercent(prod) > 0">
                -{{ getDiscountPercent(prod) }}%
              </div>
            </div>

            <div class="flash-card-info">
              <span class="product-brand">{{ prod.brand }}</span>
              <h3 class="product-title">{{ getProductFullTitle(prod) }}</h3>

              <div class="price-flex-group">
                <span class="price-new">{{ hasValidPrice(prod) ? formatCurrency(prod.gia) : 'Liên hệ' }}</span>
                <span class="price-old" v-if="prod.oldPrice > prod.gia">{{ formatCurrency(prod.oldPrice) }}</span>
              </div>

              <div class="stock-progress-container">
                <div class="stock-info-row">
                  <span>Đã bán {{ prod.soldPercent }}%</span>
                  <span>Còn lại {{ prod.remainingCount }} máy</span>
                </div>
                <div class="progress-track">
                  <div class="progress-fill" :style="{ width: prod.soldPercent + '%' }"></div>
                </div>
              </div>

              <button
                @click.stop="addToCart(prod)"
                class="btn-add-cart-flash"
                :disabled="!prod.inStock"
              >
                <ShoppingBag class="cart-btn-icon" />
                {{ prod.inStock ? 'Săn ngay' : 'Hết hàng' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 5. VOUCHER CENTER -->
    <section id="voucher-center" class="section voucher-center-section">
      <div class="grid-container">
        <div class="voucher-header-flex scroll-reveal reveal-fade-up">
          <div class="section-header-left">
            <span class="ambient-label">
              <Tag class="pill-icon" />
              Voucher Center
            </span>
            <h2>Trung tâm mã giảm giá</h2>
            <p class="section-sub" style="color: #f1f5f9 !important; -webkit-text-fill-color: #f1f5f9 !important; opacity: 1 !important;">Nhấn <strong style="color: #ffffff !important; -webkit-text-fill-color: #ffffff !important;">Nhận voucher</strong> để lưu mã vào tài khoản và áp dụng ở bước thanh toán để nhận thêm ưu đãi cực kỳ hấp dẫn.</p>
          </div>

          <div class="voucher-toolbar-actions">
            <!-- Nút Slide Controls: chỉ hiện khi ở chế độ Slide & có hơn 1 slide -->
            <div v-if="!isViewAllVouchers && totalVoucherSlides > 1" class="voucher-slide-nav">
              <button 
                type="button" 
                class="slide-nav-btn" 
                @click="prevVoucherSlide"
                title="Slide trước"
              >
                <ChevronLeft />
              </button>

              <span class="slide-indicator">
                <strong>{{ currentVoucherSlide + 1 }}</strong> / {{ totalVoucherSlides }}
              </span>

              <button 
                type="button" 
                class="slide-nav-btn" 
                @click="nextVoucherSlide"
                title="Slide sau"
              >
                <ChevronRight />
              </button>
            </div>

            <!-- Nút Xem Tất Cả -->
            <button 
              type="button" 
              class="btn-view-all-vouchers"
              @click="toggleViewAllVouchers"
            >
              <span>{{ isViewAllVouchers ? 'Thu gọn (Xem slide)' : 'Xem tất cả' }}</span>
              <component :is="isViewAllVouchers ? ChevronUp : Grid" class="view-all-icon" />
            </button>
          </div>
        </div>

        <div v-if="allVouchers.length === 0" style="text-align:center; padding: 40px 0; color: #94a3b8; font-size: 15px;">
          Hiện không có voucher nào phù hợp.
        </div>

        <transition name="voucher-fade-slide" mode="out-in">
          <div :key="isViewAllVouchers ? 'grid' : currentVoucherSlide" class="vouchers-glass-grid scroll-reveal active">
            <article v-for="v in currentSlideVouchers" :key="v.id" class="voucher-glass-card" :class="voucherTone(v)">
              <div class="voucher-glow-accent"></div>
              <div class="voucher-ticket-side" aria-hidden="true">
                <span class="voucher-badge">{{ voucherCategoryLabel(v) }}</span>
                <div class="voucher-ticket-icon">
                  <TicketPercent />
                </div>
                <span class="voucher-side-value">{{ voucherValueLabel(v) }}</span>
              </div>

              <div class="voucher-ticket-content">
                <div class="voucher-code-row">
                  <span class="voucher-code-caption">Mã voucher</span>
                  <button
                    type="button"
                    class="voucher-copy-button"
                    :class="{ copied: copiedVoucherCode === v.code }"
                    :title="copiedVoucherCode === v.code ? 'Đã sao chép' : 'Sao chép mã'"
                    @click.stop="copyVoucherCode(v)"
                  >
                    <Check v-if="copiedVoucherCode === v.code" />
                    <Copy v-else />
                  </button>
                </div>

                <h3 class="voucher-code">{{ v.code }}</h3>
                <p class="voucher-name">{{ v.ten || v.name }}</p>

                <div class="voucher-meta-grid">
                  <div class="voucher-meta-item">
                    <Tag />
                    <span>Giá trị ưu đãi<strong>{{ voucherValueLabel(v) }}</strong></span>
                  </div>
                  <div class="voucher-meta-separator"></div>
                  <div class="voucher-meta-item">
                    <Clock />
                    <span>Hạn sử dụng<strong>{{ voucherEndDate(v) ? formatDate(voucherEndDate(v)) : 'Không giới hạn' }}</strong></span>
                  </div>
                </div>

                <p class="voucher-description">{{ v.mota || v.desc || 'Áp dụng theo điều kiện của chương trình.' }}</p>

                <div class="voucher-footer">
                  <button
                    @click="claimVoucher(v)"
                    class="btn-copy-code"
                    :class="{ 'copied': claimedVoucherId === v.id, 'loading': claimingId === v.id }"
                    :disabled="claimingId === v.id"
                  >
                    <template v-if="claimedVoucherId === v.id">
                      <Check class="copy-icon" />
                      Đã nhận voucher
                    </template>
                    <template v-else-if="claimingId === v.id">
                      <span class="voucher-button-spinner"></span>
                      Đang xử lý...
                    </template>
                    <template v-else>
                      <Gift class="copy-icon" />
                      Nhận voucher
                    </template>
                  </button>
                </div>
              </div>
            </article>
          </div>
        </transition>
      </div>
    </section>

    <!-- 6. COMBO SECTION -->
    <section id="combo-offers" class="section combo-section">
      <div class="grid-container">
        <div class="section-header scroll-reveal reveal-fade-up">
          <span class="ambient-label">
            <Gift class="pill-icon" />
            Combo độc quyền
          </span>
          <h2>Mua kèm giá sốc - tiết kiệm tối đa</h2>
          <p class="section-sub" style="color: #f1f5f9 !important; -webkit-text-fill-color: #f1f5f9 !important; opacity: 1 !important;">Sở hữu trọn bộ trang bị chuyên nghiệp cho lập trình viên và game thủ với mức chiết khấu cực sâu.</p>
        </div>

        <div class="combos-bento-layout scroll-reveal reveal-stagger" v-if="combos && combos.length">
          <div v-for="combo in combos" :key="combo.id_combo" class="combo-bento-card">
            <div class="combo-card-glow"></div>
            <div class="combo-main-content">
              <div class="combo-details">
                <div class="combo-header-badges">
                  <span class="combo-discount-badge" v-if="getOriginalPrice(combo) > combo.giakhuyenmai">
                    <Flame class="badge-icon" />
                    Tiết kiệm {{ formatCurrency(getOriginalPrice(combo) - combo.giakhuyenmai) }}
                  </span>
                  <span class="combo-percent-badge" v-if="getComboDiscountPercent(combo) > 0">
                    -{{ getComboDiscountPercent(combo) }}%
                  </span>
                </div>

                <h3 class="combo-title">{{ combo.ten_combo }}</h3>
                <p class="combo-desc">{{ combo.mota }}</p>
                
                <div class="combo-perks-list">
                  <span class="perk-item"><Check class="perk-icon" /> Hàng chính hãng</span>
                  <span class="perk-item"><Truck class="perk-icon" /> Giao nhanh 2h</span>
                  <span class="perk-item"><ShieldCheck class="perk-icon" /> Đổi trả 7 ngày</span>
                </div>

                <div class="combo-pricing-group">
                  <div class="price-block">
                    <span class="price-label">Giá Combo Ưu Đãi</span>
                    <span class="price-val">{{ formatCurrency(combo.giakhuyenmai) }}</span>
                  </div>
                  <div class="price-block old-price-block" v-if="getOriginalPrice(combo) > combo.giakhuyenmai">
                    <span class="price-label">Tổng Giá Gốc</span>
                    <span class="price-val-old">{{ formatCurrency(getOriginalPrice(combo)) }}</span>
                  </div>
                </div>

                <button type="button" class="combo-action-btn" @click="openCombo(combo)">
                  <span>Mua trọn bộ combo</span>
                  <ChevronRight class="btn-chevron" />
                </button>
              </div>

              <div class="combo-visual-connector" v-if="combo.products && combo.products.length">
                <div v-for="(item, itemIdx) in combo.products" :key="itemIdx" class="connector-node-wrapper">
                  <div class="connector-node">
                    <div class="node-image-box">
                      <img
                        :src="productImageUrl(item)"
                        :alt="item.tenSP"
                        loading="lazy"
                        @error="handleImageFallback"
                      />
                    </div>
                    <span class="node-title" :title="item.tenSP">{{ item.tenSP }}</span>
                    <span class="node-price" v-if="getItemPrice(item)">{{ formatCurrency(getItemPrice(item)) }}</span>
                  </div>
                  <div v-if="itemIdx < combo.products.length - 1" class="node-plus-sign">
                    <span>+</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="combo-empty-state scroll-reveal reveal-fade-up" style="text-align: center; padding: 40px 20px;">
          <div class="combo-empty-icon" style="font-size: 48px; margin-bottom: 16px;">🎁</div>
          <h3>Combo phụ kiện giá sốc đang được cập nhật</h3>
          <p>Hiện chưa có gói combo nào trong hệ thống. Vui lòng thêm combo trong trang quản trị.</p>
        </div>
      </div>
    </section>

    <!-- 7. PRODUCTS GRID -->
    <section id="discount-grid" class="section discount-products-section">
      <div class="grid-container">
        <div class="section-header scroll-reveal reveal-fade-up">
          <span class="ambient-label">
            <ShoppingBag class="pill-icon" />
            Danh sách ưu đãi
          </span>
          <h2>Sản phẩm khuyến mãi nổi bật</h2>
          <p class="section-sub" style="color: #f1f5f9 !important; -webkit-text-fill-color: #f1f5f9 !important; opacity: 1 !important;">Tất cả dòng máy chính hãng cao cấp từ ASUS, Apple, Dell, Lenovo và MSI đều đang sale chạm đáy.</p>
        </div>

        <div class="filter-bar-navigation scroll-reveal reveal-fade-up">
          <button
            v-for="tab in [
              { id: 'all', label: 'Tất cả', icon: ShoppingBag },
              { id: 'gaming', label: 'Gaming Laptop', icon: Laptop },
              { id: 'macbook', label: 'MacBook Pro/Air', icon: AppleIcon },
              { id: 'office', label: 'Văn phòng', icon: Briefcase },
              { id: 'workstation', label: 'Workstation', icon: Monitor },
              { id: 'accessories', label: 'Accessories', icon: Gift }
            ]"
            :key="tab.id"
            @click="activeCategoryTab = tab.id"
            class="filter-tab-btn"
            :class="{ 'active': activeCategoryTab === tab.id }"
          >
            <component :is="tab.icon" class="tab-btn-icon" />
            <span>{{ tab.label }}</span>
          </button>
        </div>

        <div v-if="filteredProducts.length > 0" class="promotions-product-grid scroll-reveal reveal-stagger">
          <div v-for="prod in filteredProducts" :key="prod.id" class="promo-product-card" @click="goToDetail(prod)">
            <div class="prod-img-wrap">
              <img :src="prod.image" :alt="prod.tenSP" />
              <div class="badge-percent-overlay" v-if="getDiscountPercent(prod) > 0">
                -{{ getDiscountPercent(prod) }}%
              </div>
              <button
                class="wishlist-fav-btn"
                :class="{ 'is-wishlisted': isWishlisted(prod) }"
                :title="isWishlisted(prod) ? 'Bỏ yêu thích' : 'Thêm vào yêu thích'"
                @click.stop="toggleWishlist(prod)"
              >
                <Heart
                  class="fav-icon"
                  :fill="isWishlisted(prod) ? '#ef4444' : 'none'"
                  :stroke="isWishlisted(prod) ? '#ef4444' : '#ef4444'"
                />
              </button>
            </div>

            <div class="prod-info-details">
              <span class="prod-category-label">{{ prod.category }}</span>
              <h3 class="prod-name-title">{{ getProductFullTitle(prod) }}</h3>

              <div class="prod-rating-row">
                <div class="stars-group">
                  <Star v-for="star in 5" :key="star" class="star-icon" :class="{ 'filled': star <= Math.floor(prod.rating) }" />
                </div>
                <span class="rating-val-text">{{ prod.rating }} ({{ prod.reviews }})</span>
              </div>

              <div class="prod-specs-badges">
                <span v-for="spec in prod.specs" :key="spec" class="spec-pill">{{ spec }}</span>
              </div>

              <div class="prod-promo-msg-box">
                <Gift class="gift-msg-icon" />
                <span>{{ prod.promo }}</span>
              </div>

              <div class="prod-price-action-row">
                <div class="price-flex-block">
                  <span class="price-main-val">{{ hasValidPrice(prod) ? formatCurrency(prod.gia) : 'Liên hệ' }}</span>
                  <span class="price-old-val" v-if="prod.oldPrice > prod.gia">{{ formatCurrency(prod.oldPrice) }}</span>
                </div>
                <button
                  @click.stop="addToCart(prod)"
                  class="btn-add-cart-action"
                  :disabled="!prod.inStock"
                >
                  <ShoppingBag class="cart-action-icon" />
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="no-products-fallback scroll-reveal reveal-fade-up">
          <AlertCircle class="fallback-icon" />
          <h3>Không tìm thấy sản phẩm nào</h3>
          <p>Hiện không có sản phẩm nào thuộc danh mục này được khuyến mãi. Vui lòng quay lại sau!</p>
        </div>
      </div>
    </section>

    <!-- 8. TECH INSIGHTS -->
    <section class="section magazine-section">
      <div class="grid-container">
        <div class="section-header scroll-reveal reveal-fade-up">
          <span class="ambient-label">
            <Sparkles class="pill-icon" />
            Tech Insights Magazine
          </span>
          <h2>Tin tức công nghệ</h2>
          <p class="section-sub" style="color: #f1f5f9 !important; -webkit-text-fill-color: #f1f5f9 !important; opacity: 1 !important;">Những bài đánh giá chuyên sâu và cẩm nang bổ ích giúp bạn lựa chọn thiết bị phù hợp nhất.</p>
        </div>

        <div class="magazine-layout-grid scroll-reveal reveal-stagger">
          <article class="magazine-main-article" v-if="magazineArticles[0]" @click="goToNewsDetail(magazineArticles[0].id)">
            <div class="main-art-visual">
              <img :src="magazineArticles[0].img" :alt="magazineArticles[0].title" />
              <span class="art-badge-tag">{{ magazineArticles[0].category }}</span>
            </div>
            <div class="main-art-info">
              <h3>{{ magazineArticles[0].title }}</h3>
              <p>{{ magazineArticles[0].excerpt }}</p>
              <div class="art-meta-bottom">
                <RouterLink :to="`/tin-tuc/${magazineArticles[0].id}`" class="art-deep-link" @click.stop>
                  Xem chi tiết bài viết
                  <ArrowRight class="art-arrow-icon" />
                </RouterLink>
                <span class="art-views-badge" v-if="magazineArticles[0].views > 0">
                  👁 {{ magazineArticles[0].views.toLocaleString() }} lượt xem
                </span>
              </div>
            </div>
          </article>

          <div class="magazine-secondary-column">
            <article v-for="n in magazineArticles.slice(1, 5)" :key="n.id" class="magazine-mini-article" @click="goToNewsDetail(n.id)">
              <div class="mini-art-thumb">
                <img :src="n.img" :alt="n.title" />
              </div>
              <div class="mini-art-info">
                <div class="mini-tag-row">
                  <span class="mini-tag">{{ n.category }}</span>
                  <span class="mini-views-count" v-if="n.views > 0">👁 {{ n.views.toLocaleString() }}</span>
                </div>
                <h3>{{ n.title }}</h3>
                <RouterLink :to="`/tin-tuc/${n.id}`" class="mini-art-link" @click.stop>
                  Đọc tiếp
                  <ArrowRight class="mini-arrow-icon" />
                </RouterLink>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>



    <!-- Combo Selection variant modal -->
    <ComboSelectionModal
      v-if="selectedCombo"
      :combo="selectedCombo"
      :show="showComboModal"
      @close="showComboModal = false"
    />
  </div>
</template>

<style scoped>

/* ============================================================
   COLOR SYSTEM & GLOBAL TOKENS
   ============================================================ */
.promotions-shell {
  --primary: #061a3a;
  --secondary: #0d2c63;
  --accent: #3b82f6;
  --highlight: #00e5ff;
  --text-light: #ffffff;
  --bg-light: #f8fafc;
  --bg-dark: #070e1b;
  --border-glass: rgba(255, 255, 255, 0.08);
  --font-sans: 'Be Vietnam Pro', 'Inter', sans-serif;

  background-color: var(--bg-dark);
  color: var(--text-light);
  font-family: var(--font-sans);
  min-height: 100vh;
  overflow-x: hidden;
  position: relative;
}

.grid-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px;
  width: 100%;
}

.section {
  padding: 80px 0;
  position: relative;
}

.section-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-bottom: 48px;
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
}

.section-header h2 {
  font-size: clamp(28px, 3.5vw, 42px);
  font-weight: 800;
  letter-spacing: -0.02em;
  text-transform: capitalize;
  margin: 16px 0 12px 0;
  background: linear-gradient(135deg, var(--text-light) 0%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.section-sub {
  font-size: 15.5px;
  line-height: 1.6;
  color: #cbd5e1 !important;
  font-weight: 500;
}

.ambient-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 30px;
  background: rgba(59, 130, 246, 0.08);
  border: 1px solid rgba(59, 130, 246, 0.2);
  color: var(--accent);
  font-size: 12.5px;
  font-weight: 700;
  text-transform: capitalize;
  letter-spacing: 0.05em;
}

.pill-icon {
  width: 15px;
  height: 15px;
}

/* ============================================================
   1. HERO BANNER
   ============================================================ */
.hero-banner-section {
  position: relative;
  min-height: clamp(430px, 50vh, 560px);
  padding: 48px 0;
  display: flex;
  align-items: center;
  background:
    linear-gradient(90deg, rgba(7, 14, 27, 0.52) 0%, rgba(7, 14, 27, 0.28) 46%, rgba(7, 14, 27, 0.04) 100%),
    linear-gradient(180deg, rgba(7, 14, 27, 0) 0%, rgba(7, 14, 27, 0.26) 100%),
    url('https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1800&q=85');
  background-size: cover;
  background-position: center 42%;
  border-bottom: 1px solid var(--border-glass);
  overflow: hidden;
}

.hero-banner-section .ambient-label {
  background: rgba(15, 23, 42, 0.42);
  border-color: rgba(248, 250, 252, 0.28);
  color: #f8fafc;
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.22);
}

.hero-banner-section .pill-icon {
  color: #f8fafc;
}

.tech-matrix-overlay {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(59, 130, 246, 0.1) 1.5px, transparent 1.5px);
  background-size: 32px 32px;
  opacity: 0;
  pointer-events: none;
  z-index: 1;
}

.ambient-glow-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  opacity: 0;
  pointer-events: none;
  z-index: 1;
}

.orb-top-left {
  width: 500px;
  height: 500px;
  background: var(--accent);
  top: -100px;
  left: -100px;
}

.orb-bottom-right {
  width: 600px;
  height: 600px;
  background: var(--highlight);
  bottom: -200px;
  right: -100px;
}

.hero-layout-grid {
  display: grid;
  grid-template-columns: minmax(0, 720px);
  align-items: center;
  position: relative;
  z-index: 2;
}

.hero-intro-text {
  text-align: left;
}

.hero-intro-text h1 {
  font-size: clamp(34px, 4.4vw, 56px);
  font-weight: 850;
  line-height: 1.1;
  letter-spacing: 0;
  margin: 16px 0;
  color: #f8fafc;
  text-shadow: 0 6px 22px rgba(0, 0, 0, 0.48);
}

.gradient-text {
  display: block;
  background: none;
  -webkit-background-clip: initial;
  -webkit-text-fill-color: currentColor;
  color: #f8fafc;
}

.hero-description {
  font-size: clamp(15px, 1.8vw, 17px);
  line-height: 1.65;
  color: #e2e8f0;
  margin-bottom: 26px;
  font-weight: 400;
  max-width: 660px;
}

.hero-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 28px;
  border-radius: 12px;
  font-size: 14.5px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer;
}

.btn-primary-glass {
  background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
  color: var(--text-light);
  box-shadow: 0 4px 20px rgba(59, 130, 246, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.btn-primary-glass:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(59, 130, 246, 0.5);
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.btn-secondary-neon {
  background: rgba(255, 255, 255, 0.03);
  color: var(--text-light);
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.btn-secondary-neon:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--highlight);
  box-shadow: 0 0 15px rgba(0, 229, 255, 0.25);
  transform: translateY(-2px);
}

.btn-icon {
  width: 17px;
  height: 17px;
}

.hero-device-wrapper {
  display: none;
  justify-content: center;
  align-items: center;
  position: relative;
}

.device-showcase-card {
  position: relative;
  width: 100%;
  max-width: 500px;
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(59, 130, 246, 0.15);
  background: var(--secondary);
  aspect-ratio: 16 / 10;
}

.showcase-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.5s ease;
}

.device-showcase-card:hover .showcase-image {
  transform: scale(1.03);
}

.hardware-glow-edge {
  position: absolute;
  inset: 0;
  border: 1px solid transparent;
  background: linear-gradient(135deg, rgba(0, 229, 255, 0.3) 0%, transparent 60%) border-box;
  -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: destination-out;
  mask-composite: exclude;
  pointer-events: none;
}

.product-floating-badge {
  position: absolute;
  bottom: 20px;
  left: 20px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 12px;
  background: rgba(7, 14, 27, 0.85);
  backdrop-filter: blur(8px);
  border: 1px solid var(--border-glass);
  font-size: 13px;
  font-weight: 700;
  color: var(--highlight);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

.badge-icon {
  width: 15px;
  height: 15px;
}

/* ============================================================
   2. STATS OVERVIEW
   ============================================================ */
.stats-banner-wrapper {
  background: #091222;
  border-top: 1px solid var(--border-glass);
  border-bottom: 1px solid var(--border-glass);
  padding: 24px 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.stat-box-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 24px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.045);
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

.stat-box-card:hover {
  background: rgba(255, 255, 255, 0.07);
  border-color: rgba(255, 255, 255, 0.16);
}

.stat-visual {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.04);
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-icon {
  width: 22px;
  height: 22px;
}

.stat-info h3 {
  font-size: 22px;
  font-weight: 800;
  margin: 0 0 2px 0;
  letter-spacing: -0.01em;
  color: #f8fafc;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
}

.stat-info p {
  font-size: 13px;
  color: #cbd5e1;
  margin: 0;
  font-weight: 500;
}

/* ============================================================
   3. CATEGORIES SECTION (Bento Grid)
   ============================================================ */
.categories-section {
  background: var(--bg-dark);
}

.categories-bento-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.bento-item {
  position: relative;
  border-radius: 20px;
  height: 380px;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid var(--border-glass);
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.gaming-bento {
  grid-column: span 2;
}

.setup-bento {
  grid-column: span 2;
}

.bento-image-bg {
  position: absolute;
  inset: 0;
  z-index: 1;
}

.bento-image-bg img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.bento-item:hover .bento-image-bg img {
  transform: scale(1.05);
}

.bento-content-overlay {
  position: absolute;
  inset: 0;
  z-index: 2;
  background: linear-gradient(to top, rgba(6, 26, 58, 0.9) 0%, rgba(6, 26, 58, 0.3) 60%, transparent 100%);
  padding: 24px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  align-items: flex-start;
  transition: all 0.3s ease;
}

.bento-item:hover .bento-content-overlay {
  background: linear-gradient(to top, rgba(59, 130, 246, 0.85) 0%, rgba(6, 26, 58, 0.4) 60%, transparent 100%);
}

.bento-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--text-light);
  text-transform: capitalize;
  margin-bottom: 12px;
  letter-spacing: 0.05em;
}

.bento-content-overlay h3 {
  font-size: clamp(20px, 2.5vw, 24px);
  font-weight: 800;
  margin: 0 0 12px 0;
  letter-spacing: -0.01em;
  color: var(--text-light);
}

.bento-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 13.5px;
  font-weight: 700;
  color: var(--highlight);
  opacity: 0.85;
  transition: all 0.2s ease;
}

.bento-buy-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  margin: 0 0 10px;
  padding: 8px 14px;
  border-radius: 9px;
  background: #2563eb;
  color: #ffffff;
  font-size: 13px;
  font-weight: 800;
  line-height: 1;
  text-decoration: none;
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32);
  transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.bento-buy-btn:hover {
  background: #1d4ed8;
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 14px 28px rgba(37, 99, 235, 0.42);
}

.bento-item:hover .bento-link {
  color: var(--text-light);
  opacity: 1;
  transform: translateX(4px);
}

.chevron-link-icon {
  width: 14px;
  height: 14px;
}

/* ============================================================
   4. FLASH SALE TODAY (Dark Blue background, Countdown)
   ============================================================ */
.flash-sale-dark-section {
  background: #050d1a;
  border-top: 1px solid var(--border-glass);
  border-bottom: 1px solid var(--border-glass);
}

.flash-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 40px;
  gap: 24px;
}

.flash-header-left {
  text-align: left;
}

.flash-header-left h2 {
  font-size: clamp(26px, 3vw, 36px);
  font-weight: 800;
  margin: 12px 0 0 0;
  text-transform: capitalize;
  background: linear-gradient(135deg, var(--text-light) 0%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.flash-badge {
  background: rgba(239, 68, 68, 0.08);
  border-color: rgba(239, 68, 68, 0.2);
  color: #f87171;
}

.countdown-clock {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #081529;
  padding: 8px 18px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
}

.clock-label {
  font-size: 11.5px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.05em;
}

.timer-numbers {
  display: flex;
  align-items: center;
  gap: 4px;
}

.timer-segment {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-light);
  background: rgba(255, 255, 255, 0.05);
  padding: 4px 8px;
  border-radius: 6px;
  min-width: 32px;
  text-align: center;
  font-family: monospace;
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.timer-colon {
  color: #ef4444;
  font-weight: 800;
  animation: pulse-timer 1s infinite;
}

@keyframes pulse-timer {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.35; }
}

.flash-sale-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 14px;
}

.flash-sale-card {
  background: #081529;
  border-radius: 12px;
  border: 1px solid var(--border-glass);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer;
}

.flash-sale-card:hover {
  transform: translateY(-4px);
  border-color: rgba(239, 68, 68, 0.3);
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.35), 0 0 16px rgba(239, 68, 68, 0.08);
}

.flash-img-box {
  position: relative;
  aspect-ratio: 4 / 3;
  background: var(--tn-surface);
  margin: 10px;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.flash-img-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.flash-sale-card:hover .flash-img-box img {
  transform: scale(1.03);
}

.discount-absolute-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
  color: var(--text-light);
  font-size: 10px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 30px;
  box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
  z-index: 2;
}

.flash-card-info {
  padding: 0 10px 12px 10px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.product-brand {
  font-size: 10.5px;
  font-weight: 700;
  color: var(--accent);
  text-transform: capitalize;
  letter-spacing: 0.02em;
  margin-bottom: 4px;
}

.product-title {
  font-size: 13px;
  font-weight: 700;
  line-height: 1.32;
  color: var(--text-light);
  margin: 0 0 8px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.price-flex-group {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.price-new {
  font-size: 14px;
  font-weight: 800;
  color: #ef4444;
}

.price-old {
  font-size: 11px;
  text-decoration: line-through;
  color: #64748b;
}

.stock-progress-container {
  margin-bottom: 10px;
}

.stock-info-row {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  font-size: 10px;
  color: #94a3b8;
  font-weight: 600;
  margin-bottom: 5px;
}

.progress-track {
  height: 5px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 30px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #ef4444 0%, #f97316 100%);
  border-radius: 30px;
  transition: width 1s ease;
}

.btn-add-cart-flash {
  width: 100%;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--text-light);
  border-radius: 8px;
  padding: 8px 0;
  font-size: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.22s ease;
}

.btn-add-cart-flash:hover:not(:disabled) {
  background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
  border-color: transparent;
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.btn-add-cart-flash:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cart-btn-icon {
  width: 13px;
  height: 13px;
}

/* ============================================================
   5. VOUCHER CENTER (Glassmorphism layout)
   ============================================================ */
.voucher-center-section {
  background: var(--bg-dark);
}

.voucher-header-flex {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 28px;
  flex-wrap: wrap;
}

.voucher-toolbar-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.voucher-slide-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  padding: 4px 10px;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
}

.slide-nav-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: rgba(255, 255, 255, 0.06);
  color: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.slide-nav-btn:hover {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
  transform: scale(1.08);
}

.slide-nav-btn svg {
  width: 16px;
  height: 16px;
}

.slide-indicator {
  font-size: 13px;
  color: #94a3b8;
  padding: 0 6px;
  user-select: none;
}

.slide-indicator strong {
  color: #60a5fa;
}

.btn-view-all-vouchers {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 18px;
  border-radius: 999px;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.25) 0%, rgba(59, 130, 246, 0.15) 100%);
  border: 1px solid rgba(96, 165, 250, 0.35);
  color: #f8fafc;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.15);
}

.btn-view-all-vouchers:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  border-color: transparent;
  color: #ffffff;
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
  transform: translateY(-2px);
}

.view-all-icon {
  width: 16px;
  height: 16px;
}

.voucher-fade-slide-enter-active,
.voucher-fade-slide-leave-active {
  transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1), transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.voucher-fade-slide-enter-from {
  opacity: 0;
  transform: translateY(16px) scale(0.98);
}

.voucher-fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-16px) scale(0.98);
}

.vouchers-glass-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.voucher-glass-card {
  --ticket-accent: #4387ff;
  --ticket-accent-rgb: 67, 135, 255;
  position: relative;
  display: grid;
  grid-template-columns: 92px minmax(0, 1fr);
  min-height: 204px;
  background:
    radial-gradient(circle at 82% 8%, rgba(var(--ticket-accent-rgb), 0.13), transparent 34%),
    linear-gradient(135deg, #071b3a 0%, #061126 58%, #07152c 100%);
  border: 1.5px solid rgba(var(--ticket-accent-rgb), 0.8);
  border-radius: 20px;
  overflow: hidden;
  isolation: isolate;
  box-shadow:
    0 18px 42px rgba(0, 0, 0, 0.34),
    0 0 0 1px rgba(var(--ticket-accent-rgb), 0.12) inset,
    0 0 28px rgba(var(--ticket-accent-rgb), 0.12);
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s ease, box-shadow 0.3s ease;
}

.voucher-glass-card.shipping {
  --ticket-accent: #22d3ee;
  --ticket-accent-rgb: 34, 211, 238;
}

.voucher-glass-card.payment {
  --ticket-accent: #38bdf8;
  --ticket-accent-rgb: 56, 189, 248;
}

.voucher-glass-card::before,
.voucher-glass-card::after {
  content: '';
  position: absolute;
  top: 50%;
  z-index: 5;
  width: 30px;
  height: 30px;
  border: 1.5px solid rgba(var(--ticket-accent-rgb), 0.85);
  border-radius: 50%;
  background: var(--bg-dark);
  transform: translateY(-50%);
}

.voucher-glass-card::before {
  left: -17px;
}

.voucher-glass-card::after {
  right: -17px;
}

.voucher-glass-card:hover {
  transform: translateY(-6px);
  border-color: var(--ticket-accent);
  box-shadow: 0 24px 55px rgba(0, 0, 0, 0.42), 0 0 34px rgba(var(--ticket-accent-rgb), 0.23);
}

.voucher-glow-accent {
  position: absolute;
  top: -70px;
  right: -40px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: var(--ticket-accent);
  filter: blur(70px);
  opacity: 0.17;
  pointer-events: none;
}

.voucher-ticket-side {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  padding: 10px 8px;
  background:
    radial-gradient(circle at 50% 78%, rgba(255, 255, 255, 0.18), transparent 37%),
    linear-gradient(145deg, rgba(var(--ticket-accent-rgb), 0.98), rgba(30, 75, 200, 0.87));
}

.voucher-ticket-side::after {
  content: '';
  position: absolute;
  top: 0;
  right: -1px;
  bottom: 0;
  border-right: 2px dashed rgba(137, 190, 255, 0.55);
  filter: drop-shadow(0 0 4px rgba(var(--ticket-accent-rgb), 0.6));
}

.voucher-badge {
  align-self: flex-start;
  padding: 5px 7px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 9px;
  background: rgba(7, 27, 67, 0.5);
  color: #fff !important;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: none;
  box-shadow: 0 6px 16px rgba(0, 28, 91, 0.22);
}

.voucher-ticket-icon {
  display: grid;
  width: 40px;
  height: 40px;
  place-items: center;
  color: #c8ddff;
  filter: drop-shadow(0 0 12px rgba(219, 234, 254, 0.34));
}

.voucher-ticket-icon svg {
  width: 100%;
  height: 100%;
  stroke-width: 1.35;
}

.voucher-side-value {
  color: #fff;
  font-size: 16px;
  font-weight: 900;
  letter-spacing: -0.02em;
  text-shadow: 0 2px 12px rgba(0, 27, 91, 0.45);
}

.voucher-ticket-content {
  position: relative;
  z-index: 1;
  display: flex;
  min-width: 0;
  flex-direction: column;
  padding: 8px 12px;
}

.voucher-code-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
}

.voucher-code-caption {
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: #9fb0ca;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.11em;
  text-transform: none;
}

.voucher-code-caption::before,
.voucher-code-caption::after {
  content: '';
  width: 18px;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(var(--ticket-accent-rgb), 0.75));
}

.voucher-code-caption::after {
  transform: rotate(180deg);
}

.voucher-copy-button {
  display: grid;
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  place-items: center;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.045);
  color: #c7d5ea;
  cursor: pointer;
  transition: 0.2s ease;
}

.voucher-copy-button:hover,
.voucher-copy-button.copied {
  border-color: rgba(var(--ticket-accent-rgb), 0.65);
  background: rgba(var(--ticket-accent-rgb), 0.16);
  color: #fff;
  box-shadow: 0 0 16px rgba(var(--ticket-accent-rgb), 0.2);
}

.voucher-copy-button svg {
  width: 18px;
  height: 18px;
}

.voucher-code {
  margin: 4px 0 1px;
  overflow: hidden;
  color: #fff !important;
  font-size: clamp(19px, 1.55vw, 25px);
  font-weight: 900;
  letter-spacing: 0.035em;
  line-height: 1.15;
  text-align: center;
  text-overflow: ellipsis;
  text-shadow: 0 0 18px rgba(255, 255, 255, 0.13);
  white-space: nowrap;
}

.voucher-name {
  margin: 1px 0 4px;
  overflow: hidden;
  color: #b9c7dc !important;
  font-size: 10.5px;
  line-height: 1.4;
  text-align: center;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.voucher-meta-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 1px minmax(0, 1fr);
  align-items: center;
  min-height: 34px;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
  padding: 4px 0;
}

.voucher-meta-item {
  display: flex;
  min-width: 0;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.voucher-meta-item svg {
  width: 16px;
  height: 16px;
  flex: 0 0 16px;
  color: #b7c8e5;
}

.voucher-meta-item span {
  display: flex;
  min-width: 0;
  flex-direction: column;
  color: #8495ae;
  font-size: 8.5px;
  line-height: 1.3;
}

.voucher-meta-item strong {
  overflow: hidden;
  margin-top: 2px;
  color: var(--ticket-accent) !important;
  font-size: 12px;
  font-weight: 900;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.voucher-meta-separator {
  width: 1px;
  height: 27px;
  background: rgba(148, 163, 184, 0.22);
}

.voucher-description {
  display: none;
  margin: 0;
  overflow: hidden;
  color: #8fa1ba !important;
  display: -webkit-box;
  font-size: 10.5px;
  line-height: 1.35;
  text-align: center;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
}

.voucher-footer {
  margin-top: auto;
}

.btn-copy-code {
  width: 100%;
  min-height: 34px;
  border: 1px solid #ffb04a;
  border-radius: 12px;
  background: linear-gradient(135deg, #ff8a00, #f45d0b);
  color: #fff !important;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  box-shadow: 0 10px 24px rgba(249, 115, 22, 0.34), inset 0 1px 0 rgba(255, 255, 255, 0.3);
  transition: all 0.22s ease;
}

.btn-copy-code:hover:not(:disabled) {
  background: linear-gradient(135deg, #ff9f1c, #ff6b00);
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(249, 115, 22, 0.46), 0 0 0 2px rgba(255, 178, 71, 0.16), inset 0 1px 0 rgba(255, 255, 255, 0.34);
}

.btn-copy-code.copied {
  background: linear-gradient(135deg, #10b981, #059669) !important;
  color: white !important;
  border-color: transparent !important;
}

.btn-copy-code:disabled {
  cursor: wait;
  opacity: 0.78;
}

.copy-icon {
  width: 18px;
  height: 18px;
}

.voucher-button-spinner {
  width: 17px;
  height: 17px;
  border: 2px solid rgba(255, 255, 255, 0.38);
  border-top-color: #fff;
  border-radius: 50%;
  animation: voucher-spin 0.75s linear infinite;
}

@keyframes voucher-spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 1450px) {
  .vouchers-glass-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .voucher-glass-card {
    grid-template-columns: 125px minmax(0, 1fr);
  }

  .voucher-ticket-side {
    padding-inline: 15px;
  }
}

@media (max-width: 640px) {
  .vouchers-glass-grid {
    gap: 20px;
  }

  .voucher-glass-card {
    grid-template-columns: 1fr;
    min-height: 0;
  }

  .voucher-glass-card::before,
  .voucher-glass-card::after {
    top: 112px;
  }

  .voucher-ticket-side {
    min-height: 112px;
    flex-direction: row;
    padding: 18px 24px;
  }

  .voucher-ticket-side::after {
    top: auto;
    right: 0;
    bottom: -1px;
    left: 0;
    border-right: 0;
    border-bottom: 2px dashed rgba(137, 190, 255, 0.55);
  }

  .voucher-ticket-icon {
    width: 58px;
    height: 58px;
  }

  .voucher-side-value {
    font-size: 18px;
  }

  .voucher-ticket-content {
    padding: 21px 22px 23px;
  }

  .voucher-code {
    font-size: 24px;
  }
}

/* ============================================================
   6. COMBO SECTION
   ============================================================ */
.combo-section {
  background: #030816;
  border-top: 1px solid var(--border-glass);
  border-bottom: 1px solid var(--border-glass);
  position: relative;
}

.combos-bento-layout {
  display: flex;
  flex-direction: column;
  gap: 36px;
}

.combo-bento-card {
  background: linear-gradient(145deg, rgba(13, 27, 46, 0.95) 0%, rgba(8, 17, 32, 0.98) 100%);
  border-radius: 24px;
  border: 1px solid rgba(59, 130, 246, 0.25);
  padding: 36px;
  overflow: hidden;
  position: relative;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.combo-bento-card:hover {
  border-color: rgba(99, 102, 241, 0.5);
  box-shadow: 0 20px 45px rgba(37, 99, 235, 0.25), 0 0 30px rgba(99, 102, 241, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.15);
  transform: translateY(-3px);
}

.combo-card-glow {
  position: absolute;
  top: -60px;
  right: -60px;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.18) 0%, rgba(0, 0, 0, 0) 70%);
  pointer-events: none;
  filter: blur(40px);
}

.combo-main-content {
  display: grid;
  grid-template-columns: 1fr 1.1fr;
  gap: 40px;
  align-items: center;
  position: relative;
  z-index: 2;
}

.combo-details {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-align: left;
}

.combo-header-badges {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.combo-discount-badge {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 20px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4);
  letter-spacing: 0.02em;
}

.badge-icon {
  width: 14px;
  height: 14px;
  color: #fff;
}

.combo-percent-badge {
  background: rgba(234, 179, 8, 0.15);
  border: 1px solid rgba(234, 179, 8, 0.4);
  color: #facc15;
  font-size: 12px;
  font-weight: 800;
  padding: 5px 12px;
  border-radius: 20px;
}

.combo-title {
  font-size: 24px;
  font-weight: 800;
  margin: 0 0 10px 0;
  color: #f8fafc;
  line-height: 1.3;
  letter-spacing: -0.01em;
}

.combo-desc {
  font-size: 14px;
  line-height: 1.6;
  color: #94a3b8;
  margin-bottom: 18px;
}

.combo-perks-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 22px;
}

.perk-item {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 5px 12px;
  border-radius: 8px;
  font-size: 12px;
  color: #cbd5e1;
  font-weight: 500;
}

.perk-icon {
  width: 13px;
  height: 13px;
  color: #38bdf8;
}

.combo-pricing-group {
  display: flex;
  align-items: flex-end;
  gap: 24px;
  margin-bottom: 24px;
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 14px 20px;
  border-radius: 14px;
  width: 100%;
}

.price-block {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.price-label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.price-val {
  font-size: 26px;
  font-weight: 850;
  color: #38bdf8;
  text-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

.price-val-old {
  font-size: 18px;
  font-weight: 600;
  color: #64748b;
  text-decoration: line-through;
}

.combo-action-btn {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: #ffffff;
  font-weight: 700;
  font-size: 14px;
  padding: 14px 28px;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
}

.combo-action-btn:hover {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  box-shadow: 0 8px 25px rgba(37, 99, 235, 0.6);
  transform: translateY(-2px);
}

.btn-chevron {
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}

.combo-action-btn:hover .btn-chevron {
  transform: translateX(4px);
}

.combo-visual-connector {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  position: relative;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.7) 0%, rgba(30, 41, 59, 0.5) 100%);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 24px 20px;
  border-radius: 20px;
  backdrop-filter: blur(10px);
  min-height: 220px;
  width: 100%;
}

.connector-node-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.connector-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  position: relative;
  flex: 1;
  min-width: 0;
  transition: transform 0.3s ease;
}

.connector-node:hover {
  transform: translateY(-4px);
}

.node-image-box {
  width: 110px;
  height: 110px;
  border-radius: 16px;
  background: #ffffff;
  padding: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3), inset 0 0 0 1px rgba(0, 0, 0, 0.05);
  border: 2px solid rgba(255, 255, 255, 0.15);
  position: relative;
  transition: all 0.3s ease;
}

.connector-node:hover .node-image-box {
  border-color: #38bdf8;
  box-shadow: 0 12px 30px rgba(56, 189, 248, 0.3);
}

.node-image-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
}

.node-title {
  font-size: 12px;
  font-weight: 600;
  color: #e2e8f0;
  margin-top: 10px;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
  max-width: 130px;
}

.node-price {
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  margin-top: 2px;
}

.node-plus-sign {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.25) 0%, rgba(99, 102, 241, 0.35) 100%);
  border: 1px solid rgba(56, 189, 248, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #38bdf8;
  font-size: 18px;
  font-weight: 800;
  flex-shrink: 0;
  box-shadow: 0 0 12px rgba(56, 189, 248, 0.2);
}

/* ============================================================
   7. PRODUCT GRID WITH FILTER
   ============================================================ */
.discount-products-section {
  background: var(--bg-dark);
}

.filter-bar-navigation {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 40px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-glass);
  padding: 8px;
  border-radius: 16px;
  width: fit-content;
  margin-left: auto;
  margin-right: auto;
}

.filter-tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: 1px solid transparent;
  color: #94a3b8;
  padding: 10px 20px;
  border-radius: 10px;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
}

.filter-tab-btn:hover {
  color: var(--text-light);
  background: rgba(255, 255, 255, 0.03);
}

.filter-tab-btn.active {
  background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
  color: var(--text-light);
  border-color: rgba(255, 255, 255, 0.06);
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25);
}

.tab-btn-icon {
  width: 15px;
  height: 15px;
}

.promotions-product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

.promo-product-card {
  background: #081529;
  border-radius: 16px;
  border: 1px solid var(--border-glass);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer;
}

.promo-product-card:hover {
  transform: translateY(-6px);
  border-color: rgba(59, 130, 246, 0.25);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
}

.prod-img-wrap {
  position: relative;
  aspect-ratio: 16 / 11;
  background: var(--tn-surface);
  margin: 12px;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.prod-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.promo-product-card:hover .prod-img-wrap img {
  transform: scale(1.03);
}

.badge-percent-overlay {
  position: absolute;
  top: 10px;
  left: 10px;
  background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
  color: var(--text-light);
  font-size: 10px;
  font-weight: 800;
  padding: 4px 8px;
  border-radius: 30px;
  box-shadow: 0 4px 8px rgba(239, 68, 68, 0.25);
}

.wishlist-fav-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(7, 14, 27, 0.65);
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.wishlist-fav-btn:hover {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.3);
  color: #ef4444;
  transform: scale(1.05);
}

.fav-icon {
  width: 14.5px;
  height: 14.5px;
}

.prod-info-details {
  padding: 0 16px 18px 16px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.prod-category-label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: capitalize;
  letter-spacing: 0.02em;
  margin-bottom: 4px;
}

.prod-name-title {
  font-size: 14.5px;
  font-weight: 700;
  line-height: 1.4;
  color: var(--text-light);
  margin: 0 0 8px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.prod-rating-row {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 12px;
}

.stars-group {
  display: flex;
  gap: 2px;
}

.star-icon {
  width: 12px;
  height: 12px;
  color: #475569;
  fill: currentColor;
}

.star-icon.filled {
  color: #fbbf24;
}

.rating-val-text {
  font-size: 11.5px;
  font-weight: 600;
  color: #64748b;
}

.prod-specs-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 14px;
  height: 24px;
  overflow: hidden;
}

.spec-pill {
  font-size: 10px;
  font-weight: 600;
  color: #cbd5e1;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding: 2px 8px;
  border-radius: 4px;
}

.prod-promo-msg-box {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(37, 99, 235, 0.05);
  border: 1px solid rgba(37, 99, 235, 0.12);
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 11.5px;
  font-weight: 600;
  color: #93c5fd;
  margin-bottom: 16px;
}

.gift-msg-icon {
  width: 14px;
  height: 14px;
  color: var(--accent);
  flex-shrink: 0;
}

.prod-price-action-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
  padding-top: 14px;
}

.price-flex-block {
  display: flex;
  flex-direction: column;
  gap: 2px;
  align-items: flex-start;
}

.price-main-val {
  font-size: 16px;
  font-weight: 800;
  color: var(--highlight);
}

.price-old-val {
  font-size: 12px;
  text-decoration: line-through;
  color: #64748b;
}

.btn-add-cart-action {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: var(--text-light);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.22s ease;
}

.btn-add-cart-action:hover:not(:disabled) {
  background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
  border-color: transparent;
  color: white;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-add-cart-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cart-action-icon {
  width: 15px;
  height: 15px;
}

.no-products-fallback {
  text-align: center;
  padding: 60px 0;
  background: #081529;
  border: 1px solid var(--border-glass);
  border-radius: 24px;
  max-width: 600px;
  margin: 0 auto;
}

.fallback-icon {
  width: 48px;
  height: 48px;
  color: #475569;
  margin-bottom: 16px;
}

.no-products-fallback h3 {
  font-size: 18px;
  font-weight: 700;
  margin: 0 0 8px 0;
}

.no-products-fallback p {
  font-size: 13.5px;
  color: #64748b;
  margin: 0;
}

/* ============================================================
   8. TECH INSIGHTS (Magazine style)
   ============================================================ */
.magazine-section {
  background: var(--bg-dark);
}

.magazine-layout-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
}

.magazine-main-article {
  background: #081529;
  border-radius: 24px;
  border: 1px solid var(--border-glass);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  transition: all 0.3s ease;
}

.magazine-main-article:hover {
  border-color: rgba(59, 130, 246, 0.2);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
}

.main-art-visual {
  position: relative;
  aspect-ratio: 16 / 9;
  overflow: hidden;
}

.main-art-visual img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.magazine-main-article:hover .main-art-visual img {
  transform: scale(1.02);
}

.art-badge-tag {
  position: absolute;
  top: 16px;
  left: 16px;
  background: var(--accent);
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 6px;
  text-transform: capitalize;
  letter-spacing: 0.02em;
}

.main-art-info {
  padding: 24px;
  text-align: left;
}

.main-art-info h3 {
  font-size: 22px;
  font-weight: 800;
  line-height: 1.35;
  margin: 0 0 10px 0;
  color: #ffffff !important;
  -webkit-text-fill-color: #ffffff !important;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.4) !important;
}

.main-art-info p {
  font-size: 13.5px;
  line-height: 1.6;
  color: #94a3b8;
  margin: 0 0 20px 0;
}

.art-deep-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 700;
  color: var(--highlight);
}

.art-arrow-icon {
  width: 14px;
  height: 14px;
  transition: transform 0.2s ease;
}

.magazine-main-article:hover .art-arrow-icon {
  transform: translateX(4px);
}

.magazine-secondary-column {
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 100%;
  justify-content: space-between;
}

.magazine-mini-article {
  display: flex;
  gap: 14px;
  padding: 12px 14px;
  border-radius: 14px;
  background: #081529;
  border: 1px solid var(--border-glass);
  cursor: pointer;
  transition: all 0.3s ease;
  align-items: center;
}

.magazine-mini-article:hover {
  border-color: rgba(59, 130, 246, 0.2);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
}

.mini-art-thumb {
  width: 78px;
  height: 78px;
  border-radius: 10px;
  overflow: hidden;
  flex-shrink: 0;
}

.mini-art-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.magazine-mini-article:hover .mini-art-thumb img {
  transform: scale(1.04);
}

.mini-art-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-align: left;
  flex-grow: 1;
}

.mini-tag-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  margin-bottom: 3px;
}

.mini-tag {
  font-size: 9.5px;
  font-weight: 700;
  color: var(--accent);
  text-transform: capitalize;
  margin-bottom: 0;
}

.mini-views-count {
  font-size: 10px;
  color: #64748b;
  font-weight: 600;
}

.art-meta-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  margin-top: 8px;
}

.art-views-badge {
  font-size: 12px;
  color: #94a3b8;
  font-weight: 600;
}

.mini-art-info h3 {
  font-size: 14.5px;
  font-weight: 750;
  line-height: 1.4;
  margin: 0 0 8px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  height: 40px;
  color: #ffffff !important;
  -webkit-text-fill-color: #ffffff !important;
}

.mini-art-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11.5px;
  font-weight: 700;
  color: #94a3b8;
  transition: all 0.2s ease;
}

.magazine-mini-article:hover .mini-art-link {
  color: var(--highlight);
}

.mini-arrow-icon {
  width: 12px;
  height: 12px;
  transition: transform 0.2s ease;
}

.magazine-mini-article:hover .mini-arrow-icon {
  transform: translateX(3px);
}

/* ============================================================
   9. NEWSLETTER
   ============================================================ */
.newsletter-section {
  background: var(--bg-dark);
  padding-bottom: 100px;
}

.newsletter-glass-card {
  position: relative;
  border-radius: 28px;
  padding: 56px;
  background: linear-gradient(135deg, #081529 0%, #0b1b33 100%);
  color: #f8fafc;
  border: 1px solid rgba(96, 165, 250, 0.18);
  overflow: hidden;
  box-shadow: 0 18px 42px rgba(0, 0, 0, 0.32);
}

.newsletter-glow-orb {
  position: absolute;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 70%);
  filter: blur(40px);
  top: -100px;
  right: -100px;
  pointer-events: none;
}

.newsletter-layout {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  align-items: center;
  gap: 40px;
  position: relative;
  z-index: 2;
}

.newsletter-headline {
  text-align: left;
}

.newsletter-headline h2 {
  font-size: clamp(24px, 3.2vw, 36px);
  font-weight: 850;
  margin: 0 0 12px 0;
  color: #f8fafc;
  letter-spacing: -0.01em;
  text-transform: capitalize;
}

.newsletter-headline p {
  font-size: 14.5px;
  line-height: 1.65;
  color: #aebed3;
  margin: 0;
}

.newsletter-form-group {
  display: flex;
  justify-content: flex-end;
}

.newsletter-interactive-form {
  display: flex;
  gap: 8px;
  width: 100%;
  max-width: 440px;
  background: #061225;
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 12px;
  padding: 6px;
  transition: all 0.3s ease;
}

.newsletter-interactive-form:focus-within {
  background: #08172b;
  border-color: var(--accent);
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
}

.newsletter-interactive-form input {
  flex-grow: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #f8fafc;
  font-size: 14px;
  padding: 0 12px;
  font-weight: 500;
}

.newsletter-interactive-form input::placeholder {
  color: #94a3b8;
}

.btn-newsletter-submit {
  background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-newsletter-submit:hover {
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  filter: brightness(1.05);
}

/* ============================================================
   ANIMATIONS AND TRANSITIONS
   ============================================================ */
.scroll-reveal {
  opacity: 1;
  transform: none;
  transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

.reveal-fade-up {
  transform: none;
}

.reveal-scale-up {
  transform: none;
}

.scroll-reveal.active {
  opacity: 1;
  transform: translateY(0) scale(1);
}

.reveal-stagger > * {
  opacity: 1;
  transform: none;
  transition: opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1), transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.scroll-reveal.reveal-stagger.active > * {
  opacity: 1;
  transform: translateY(0);
}

.scroll-reveal.reveal-stagger.active > *:nth-child(1) { transition-delay: 0.08s; }
.scroll-reveal.reveal-stagger.active > *:nth-child(2) { transition-delay: 0.16s; }
.scroll-reveal.reveal-stagger.active > *:nth-child(3) { transition-delay: 0.24s; }
.scroll-reveal.reveal-stagger.active > *:nth-child(4) { transition-delay: 0.32s; }
.scroll-reveal.reveal-stagger.active > *:nth-child(5) { transition-delay: 0.40s; }
.scroll-reveal.reveal-stagger.active > *:nth-child(6) { transition-delay: 0.48s; }

/* ============================================================
   RESPONSIVE LAYOUT BREAKPOINTS
   ============================================================ */
@media (max-width: 1200px) {
  .hero-banner-section {
    min-height: 50vh;
    padding: 44px 0;
  }
  .combos-bento-layout { gap: 24px; }
}

@media (max-width: 1024px) {
  .hero-layout-grid {
    grid-template-columns: 1fr;
  }

  .hero-intro-text {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .hero-buttons { justify-content: center; }

  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .categories-bento-grid { grid-template-columns: repeat(2, 1fr); }
  .gaming-bento, .setup-bento { grid-column: span 2; }
  .flash-sale-grid { grid-template-columns: repeat(2, 1fr); }
  .vouchers-glass-grid { grid-template-columns: repeat(2, 1fr); }
  .promotions-product-grid { grid-template-columns: repeat(2, 1fr); }
  
  .combo-main-content {
    grid-template-columns: 1fr;
    gap: 28px;
  }

  .combo-details { align-items: center; text-align: center; }
  .combo-pricing-group { justify-content: center; }

  .magazine-layout-grid {
    grid-template-columns: 1fr;
    gap: 28px;
  }

  .newsletter-layout {
    grid-template-columns: 1fr;
    text-align: center;
    gap: 24px;
  }
  .newsletter-headline { text-align: center; }
  .newsletter-form-group { justify-content: center; }
}

@media (max-width: 640px) {
  .hero-banner-section {
    min-height: 52vh;
    padding: 36px 0;
    background-position: center;
  }

  .hero-intro-text h1 {
    font-size: clamp(30px, 10vw, 42px);
  }

  .hero-description {
    font-size: 14px;
  }

  .stats-grid { grid-template-columns: 1fr; }
  .categories-bento-grid { grid-template-columns: 1fr; }
  .gaming-bento, .setup-bento { grid-column: span 1; }
  .flash-sale-grid { grid-template-columns: 1fr; }
  .vouchers-glass-grid { grid-template-columns: 1fr; }
  .promotions-product-grid { grid-template-columns: 1fr; }
  
  .flash-header-row {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  .flash-header-left { text-align: center; }

  .combo-bento-card { padding: 20px; }
  .combo-pricing-group { gap: 16px; flex-wrap: wrap; }
  .combo-visual-connector { flex-direction: column; gap: 24px; }
  .node-plus-sign { right: auto; bottom: -20px; top: auto; transform: rotate(90deg); }

  .newsletter-glass-card { padding: 32px 20px; }
  .newsletter-interactive-form { flex-direction: column; gap: 12px; background: transparent; border: none; padding: 0; }
  .newsletter-interactive-form input {
    background: var(--tn-surface-2);
    border: 1px solid var(--tn-border);
    border-radius: 10px;
    padding: 12px;
    width: 100%;
  }
  .btn-newsletter-submit { width: 100%; padding: 12px 0; }
}

/* Voucher button loading state */
.btn-copy-code.loading {
  opacity: 0.7;
  cursor: not-allowed;
  pointer-events: none;
}

/* Compact and consistent spacing across promotion sections. */
.promotions-shell .section {
  padding-top: 52px;
  padding-bottom: 52px;
}

.promotions-shell .section-header {
  margin-bottom: 32px;
}

.promotions-shell .categories-section {
  padding-bottom: 28px;
}

.promotions-shell .flash-sale-dark-section {
  padding-top: 40px;
}

.promotions-shell .flash-header-row {
  margin-bottom: 28px;
}

/* Fixed header must not cover sections opened from hero/category links. */
.promotions-shell #flash-sale,
.promotions-shell #voucher-center,
.promotions-shell #combo-offers,
.promotions-shell #discount-grid {
  scroll-margin-top: 120px;
}

/* Keep the ending of the page compact and prevent an empty dark band. */
.promotions-shell .magazine-section {
  padding-bottom: 28px;
}

.promotions-shell .magazine-layout-grid {
  align-items: start;
}

.promotions-shell .newsletter-section {
  padding-top: 28px;
  padding-bottom: 52px;
}

.promotions-shell .newsletter-glass-card {
  padding: 44px 48px;
}

@media (max-width: 992px) {
  .promotions-shell .section {
    padding-top: 44px;
    padding-bottom: 44px;
  }

  .promotions-shell .categories-section {
    padding-bottom: 24px;
  }

  .promotions-shell .flash-sale-dark-section {
    padding-top: 36px;
  }

  .promotions-shell .magazine-section {
    padding-bottom: 24px;
  }

  .promotions-shell .newsletter-section {
    padding-top: 24px;
    padding-bottom: 44px;
  }
}

@media (max-width: 640px) {
  .promotions-shell .section {
    padding-top: 36px;
    padding-bottom: 36px;
  }

  .promotions-shell .section-header,
  .promotions-shell .flash-header-row {
    margin-bottom: 24px;
  }

  .promotions-shell .magazine-section {
    padding-bottom: 20px;
  }

  .promotions-shell .newsletter-section {
    padding-top: 20px;
    padding-bottom: 36px;
  }

  .promotions-shell .newsletter-glass-card {
    padding: 28px 20px;
  }

  .promotions-shell .newsletter-interactive-form input {
    background: #061225;
    border-color: rgba(148, 163, 184, 0.22);
    color: #f8fafc;
  }
}
</style>
