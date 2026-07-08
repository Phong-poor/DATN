<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  BadgeCheck,
  ChevronLeft,
  ChevronRight,
  Flame,
  Heart,
  Headphones,
  Laptop,
  Monitor,
  Search,
  ShieldCheck,
  ShoppingCart,
  SlidersHorizontal,
  Truck,
  Zap,
} from 'lucide-vue-next'
import api from '@/services/api'
import { getToken } from '@/services/auth'
import { prefetchProductsPage } from '@/services/productsPrefetch'
import { handleImageFallback, productImageUrl } from '@/services/urls'
import ComboSelectionModal from './HopThoaiChonCombo.vue'

const router = useRouter()
const route = useRoute()

const rawProductsList = ref([])
const isLoading = ref(true)
const activeLine = ref('all')
const activeSort = ref('popular')
const searchQuery = ref('')
const selectedBrands = ref([])
const selectedCpus = ref([])
const maxPrice = ref(200000000)
const currentPage = ref(1)
const catalogResults = ref(null)
const itemsPerPage = 12

const isAccessoryPage = computed(() => route.path.includes('phu-kien'))

const combos = ref([])
const showComboModal = ref(false)
const selectedCombo = ref(null)

const getOriginalPrice = (combo) => {
  if (!combo.products) return 0
  return combo.products.reduce((sum, p) => {
    const firstVariantPrice = p.bien_thes?.[0]?.gia || 0
    return sum + Number(firstVariantPrice)
  }, 0)
}

const openCombo = (combo) => {
  selectedCombo.value = combo
  showComboModal.value = true
}

const products = computed(() => {
  if (isAccessoryPage.value) {
    return rawProductsList.value.filter(isProductAccessory).map(normalizeProduct)
  } else {
    const laptopRaw = rawProductsList.value.filter(isProductLaptop)
    if (laptopRaw.length === 0) return []
    const groups = expandAllVariants(laptopRaw)
    return interleaveVariants(groups)
  }
})

const isProductAccessory = (product) => {
  const cat = String(
    product.category || 
    product.danh_muc?.ten_danhmuc || 
    product.danhmuc?.tenDM || 
    ''
  ).toLowerCase()
  const name = String(product.tenSP || '').toLowerCase()
  const accessoryCats = ['chuột', 'bàn phím', 'tai nghe', 'lót chuột', 'ổ cứng ssd', 'ram', 'màn hình', 'hub chuyển đổi', 'webcam', 'balo laptop', 'router', 'microphone', 'phụ kiện', 'accessory']
  if (accessoryCats.some(c => cat.includes(c))) return true
  if (cat === 'laptop' && (name.includes('chuột') || name.includes('bàn phím') || name.includes('tai nghe') || name.includes('lót chuột') || name.includes('mouse') || name.includes('keyboard') || name.includes('headphone'))) {
    return true
  }
  return false
}

const isProductLaptop = (product) => {
  return !isProductAccessory(product)
}

const laptopLines = [
  { key: 'all', label: 'Tất cả laptop', icon: Laptop, q: '' },
  { key: 'gaming', label: 'Laptop Gaming RTX', icon: Zap, q: 'gaming rtx' },
  { key: 'macbook', label: 'MacBook Pro & Air', icon: Monitor, q: 'macbook apple' },
  { key: 'office', label: 'Laptop văn phòng', icon: BadgeCheck, q: 'van phong' },
  { key: 'student', label: 'Laptop học tập', icon: ShieldCheck, q: 'hoc sinh sinh vien' },
  { key: 'accessory', label: 'Phụ kiện laptop', icon: Headphones, q: 'phu kien chuot ban phim tai nghe' },
]

const accessoryLines = [
  { key: 'all', label: 'Tất cả phụ kiện', icon: Headphones, q: '' },
  { key: 'mouse', label: 'Chuột', icon: Zap, q: 'chuot mouse' },
  { key: 'keyboard', label: 'Bàn phím', icon: Monitor, q: 'ban phim keyboard' },
  { key: 'headphone', label: 'Tai nghe', icon: Headphones, q: 'tai nghe headphone' },
  { key: 'pad', label: 'Lót chuột', icon: ShieldCheck, q: 'lot chuot mousepad' },
  { key: 'other', label: 'Phụ kiện khác', icon: SlidersHorizontal, q: 'o cung ram main nguon case hub cap ugreen' },
]

const activeLinesList = computed(() => isAccessoryPage.value ? accessoryLines : laptopLines)
const visibleLines = computed(() => isAccessoryPage.value ? accessoryLines : laptopLines)

const tabs = [
  { key: 'popular', label: 'Bán chạy' },
  { key: 'newest', label: 'Mới nhất' },
  { key: 'price_asc', label: 'Giá tốt' },
  { key: 'rating', label: 'Được đánh giá cao' },
]

const serviceCards = [
  { icon: Truck, title: 'Miễn phí vận chuyển', desc: 'Cho đơn hàng từ 499.000đ' },
  { icon: ShieldCheck, title: 'Đổi trả dễ dàng', desc: 'Đổi trả trong 7 ngày' },
  { icon: BadgeCheck, title: 'Thanh toán an toàn', desc: 'Nhiều phương thức thanh toán' },
  { icon: Headphones, title: 'Hỗ trợ 24/7', desc: 'Hotline: 1900 1234' },
]

const heroCategories = [
  { label: 'Laptop Gaming RTX 4050', icon: BadgeCheck, line: 'gaming', q: 'rtx 4050' },
  { label: 'Laptop Gaming RTX 4060', icon: BadgeCheck, line: 'gaming', q: 'rtx 4060' },
  { label: 'Laptop Gaming RTX 4070', icon: BadgeCheck, line: 'gaming', q: 'rtx 4070' },
  { label: 'Laptop Gaming RTX 4080', icon: BadgeCheck, line: 'gaming', q: 'rtx 4080' },
  { label: 'Laptop Gaming RTX 4090', icon: BadgeCheck, line: 'gaming', q: 'rtx 4090' },
  { label: 'ASUS ROG / TUF', icon: ShieldCheck, line: 'gaming', q: 'asus rog tuf' },
  { label: 'MSI Gaming', icon: Flame, line: 'gaming', q: 'msi gaming' },
  { label: 'Acer NextGen', icon: BadgeCheck, line: 'gaming', q: 'acer predator' },
  { label: 'Lenovo Legion', icon: Zap, line: 'gaming', q: 'lenovo legion' },
  { label: 'Bàn phím cơ Gaming', icon: Monitor, line: 'accessory', q: 'ban phim gaming' },
  { label: 'Chuột Gaming', icon: Search, line: 'accessory', q: 'chuot gaming' },
  { label: 'Tai nghe Gaming', icon: Headphones, line: 'accessory', q: 'tai nghe gaming' },
  { label: 'Lót chuột Gaming', icon: Monitor, line: 'accessory', q: 'lot chuot gaming' },
]

const heroAccessoryCategories = [
  { label: 'Chuột Gaming Logitech', icon: BadgeCheck, line: 'mouse', q: 'logitech mouse' },
  { label: 'Bàn phím cơ Akko', icon: ShieldCheck, line: 'keyboard', q: 'akko keyboard' },
  { label: 'Tai nghe chụp tai Razer', icon: Headphones, line: 'headphone', q: 'razer headphone' },
  { label: 'Lót chuột cỡ lớn', icon: Monitor, line: 'pad', q: 'lot chuot pad' },
  { label: 'Ugreen Hub & cáp sạc', icon: BadgeCheck, line: 'other', q: 'ugreen hub cap' },
]

const heroCategoriesToDisplay = computed(() => isAccessoryPage.value ? heroAccessoryCategories : heroCategories)

const showroomHighlights = [
  {
    text: 'Trải nghiệm trực quan',
    desc: 'Đầy đủ laptop gaming và MacBook sẵn sàng dùng thử thực tế.',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
  },
  {
    text: 'Không gian demo chuyên nghiệp',
    desc: 'Setup ánh sáng hiện đại, bàn trải nghiệm rộng và đầy đủ phụ kiện đi kèm.',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A5 5 0 0 0 8 8c0 1 .3 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>',
  },
  {
    text: 'Tư vấn cấu hình 1-1',
    desc: 'Kỹ thuật viên hỗ trợ chọn máy đúng nhu cầu học tập, làm việc, đồ họa hoặc gaming.',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 11v1"/><path d="M19 16v1"/></svg>',
  },
]

const brandShowcase = [
  { name: 'ASUS', query: 'asus rog', logo: '/ASUS_Logo.svg.png' },
  { name: 'MSI', query: 'msi gaming', logo: '/images%20(1).png' },
  { name: 'Acer', query: 'acer predator', logo: '/Acer_2011.svg.png' },
  { name: 'Lenovo', query: 'lenovo legion', logo: '/images.png' },
  { name: 'HP', query: 'hp victus', logo: '/HP_logo_2012.svg.png' },
  { name: 'Dell', query: 'dell gaming', logo: '/Dell_Logo.svg.png' },
  { name: 'Apple', query: 'apple macbook', logo: '/Apple_logo_black.svg.png' },
]

const fallbackProducts = [
  {
    id_sanpham: 101,
    tenSP: 'Laptop Gaming ASUS ROG Strix G16 G614JI-N4084W',
    brand: 'Asus',
    category: 'Laptop Gaming',
    gia: 39990000,
    oldPrice: 45990000,
    specs: ['Core i7', 'RTX 4060', '16GB RAM', '1TB SSD'],
    image: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=700&q=80',
    rating: 4.8,
    reviews: 98,
    id_bienthe: null,
  },
  {
    id_sanpham: 102,
    tenSP: 'MacBook Pro 14 inch M4 Pro',
    brand: 'Apple',
    category: 'MacBook',
    gia: 59990000,
    oldPrice: 68990000,
    specs: ['Apple M4 Pro', '24GB RAM', '1TB SSD', 'Liquid Retina'],
    image: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=700&q=80',
    rating: 4.9,
    reviews: 126,
    id_bienthe: null,
  },
]

const normalizeVariant = (p, variant) => {
  let specs = []
  try {
    const attrs = typeof variant?.thuoc_tinh_json === 'string'
      ? JSON.parse(variant.thuoc_tinh_json || '[]')
      : (variant?.thuoc_tinh_json || [])
    if (Array.isArray(attrs)) specs = attrs.map(attr => attr.giatri).filter(Boolean)
  } catch {}

  if (specs.length === 0) {
    try {
      const tech = typeof p.thong_so_ky_thuat === 'string'
        ? JSON.parse(p.thong_so_ky_thuat || '[]')
        : (p.thong_so_ky_thuat || [])
      if (Array.isArray(tech)) specs = tech.map(item => item.giatri).filter(Boolean)
    } catch {}
  }

  const price = Number(variant?.gia || p.gia || 0) || 19990000

  // Extract CPU and RAM for subtitle display
  const cpuSpec = specs.find(s => /i[3579][-\s]|ryzen|core ultra|xeon|m[1-4]|celeron|pentium/i.test(s)) || ''
  const ramSpec = specs.find(s => /\d+gb.*ram|ram.*\d+gb|\d+gb(?!.*ssd|.*nvme|.*storage)/i.test(s)) || ''

  return {
    id_sanpham: p.id_sanpham,
    id_bienthe: variant?.id_bienthe,
    tenSP: p.tenSP,
    brand: p.thuong_hieu?.ten_thuonghieu || p.thuonghieu?.tenTH || p.brand || 'NextGen',
    category: p.danh_muc?.ten_danhmuc || p.danhmuc?.tenDM || p.category || 'Laptop',
    gia: price,
    oldPrice: Math.floor(price * 1.13),
    specs: specs.slice(0, 5).length ? specs.slice(0, 5) : ['16GB RAM', '512GB SSD', 'FHD IPS'],
    cpu: cpuSpec,
    ram: ramSpec,
    image: productImageUrl(p, variant, 'https://placehold.co/600x420?text=NextGen+Laptop'),
    rating: p.rating_avg !== undefined && p.rating_avg !== null ? Number(p.rating_avg) : 4.8,
    reviews: p.rating_count !== undefined && p.rating_count !== null ? Number(p.rating_count) : 12,
  }
}

// Legacy single-variant fallback
const normalizeProduct = (p) => {
  const variants = Array.isArray(p.bien_thes) ? p.bien_thes : []
  const variant = variants.length
    ? variants.slice().sort((a, b) => Number(b.gia || 0) - Number(a.gia || 0))[0]
    : null
  return normalizeVariant(p, variant)
}

/**
 * Expand all variants: mỗi biến thể = 1 card riêng
 */
const expandAllVariants = (rawProducts) => {
  const result = []
  for (const p of rawProducts) {
    const variants = Array.isArray(p.bien_thes) ? p.bien_thes : []
    if (variants.length === 0) {
      result.push({ items: [normalizeProduct(p)], productId: p.id_sanpham })
    } else {
      result.push({
        items: variants.map(v => normalizeVariant(p, v)),
        productId: p.id_sanpham
      })
    }
  }
  return result
}

const interleaveVariants = (groups) => {
  // Trộn các nhóm sản phẩm một cách nhất quán (deterministic shuffle) để tránh việc các sản phẩm
  // ở đầu danh sách database chiếm giữ hết trang đầu tiên.
  const shuffledGroups = [...groups].sort((a, b) => {
    const hashA = (a.productId * 9301 + 49297) % 233280
    const hashB = (b.productId * 9301 + 49297) % 233280
    return hashA - hashB
  })

  // Tạo queue cho từng sản phẩm
  const queues = shuffledGroups.map(g => [...g.items])
  const result = []
  const history = [] // Lưu lịch sử id_sanpham của các card vừa thêm

  while (queues.some(q => q.length > 0)) {
    let chosen = null

    // Tìm kiếm sản phẩm phù hợp sao cho khoảng cách giữa các biến thể cùng loại là lớn nhất có thể (tối đa 4 card cách nhau).
    // Nếu không tìm được sản phẩm nào thỏa mãn khoảng cách tối đa, ta giảm dần khoảng cách xuống cho đến khi tìm được.
    for (let dist = 4; dist >= 0; dist--) {
      const activeHistory = history.slice(-dist)
      const eligible = queues
        .map((q, i) => ({ q, i, productId: shuffledGroups[i].productId }))
        .filter(({ q, productId }) => q.length > 0 && !activeHistory.includes(productId))

      if (eligible.length > 0) {
        // Ưu tiên hàng đợi còn nhiều biến thể nhất để giải phóng sớm các sản phẩm có nhiều cấu hình
        eligible.sort((a, b) => b.q.length - a.q.length)
        chosen = eligible[0]
        break
      }
    }

    if (chosen) {
      result.push(chosen.q.shift())
      history.push(chosen.productId)
    } else {
      break
    }
  }

  return result
}

const loadProducts = async () => {
  isLoading.value = true
  try {
    const cache = await prefetchProductsPage()
    if (cache?.productsRaw?.length) {
      rawProductsList.value = cache.productsRaw
    } else {
      rawProductsList.value = [...fallbackProducts]
    }
  } catch (error) {
    console.error('Khong tai duoc danh sach laptop:', error)
    rawProductsList.value = [...fallbackProducts]
  } finally {
    isLoading.value = false
  }

  // Load combos
  try {
    const comboResponse = await api.get('/combos')
    if (comboResponse.data && Array.isArray(comboResponse.data.data)) {
      combos.value = comboResponse.data.data
    }
  } catch (comboErr) {
    console.error('Loi tai combo:', comboErr)
  }
}

const canonicalText = (value) => String(value || '').toLowerCase()

const lineMatcher = (product, line = activeLine.value) => {
  const text = canonicalText(`${product.tenSP} ${product.brand} ${product.category} ${product.specs.join(' ')}`)
  if (line === 'all') return true
  
  if (isAccessoryPage.value) {
    if (line === 'mouse') return text.includes('chuot') || text.includes('mouse')
    if (line === 'keyboard') return text.includes('ban phim') || text.includes('keyboard')
    if (line === 'headphone') return text.includes('tai nghe') || text.includes('headphone') || text.includes('tai-nghe')
    if (line === 'pad') return text.includes('lot chuot') || text.includes('mousepad') || text.includes('pad')
    if (line === 'other') return !['chuot', 'mouse', 'ban phim', 'keyboard', 'tai nghe', 'headphone', 'lot chuot', 'mousepad'].some(k => text.includes(k))
    return true
  }

  if (line === 'gaming') return text.includes('gaming') || text.includes('rtx') || text.includes('rog') || text.includes('legion')
  if (line === 'macbook') return text.includes('macbook') || text.includes('apple')
  if (line === 'office') return text.includes('van phong') || text.includes('vivobook') || text.includes('inspiron') || text.includes('ideapad')
  if (line === 'student') return text.includes('hoc') || text.includes('student') || text.includes('air') || text.includes('thin')
  if (line === 'accessory') return text.includes('chuot') || text.includes('ban phim') || text.includes('tai nghe') || text.includes('phu kien')
  return true
}

const brandOptions = computed(() => {
  const list = isAccessoryPage.value 
    ? products.value.filter(isProductAccessory) 
    : products.value.filter(isProductLaptop)
  const names = list.map(p => p.brand).filter(Boolean)
  return [...new Set(names)].slice(0, 10)
})

const cpuOptions = ['Apple M4', 'Apple M3', 'Intel Core Ultra', 'Ryzen 9']

const lineCount = (lineKey) => {
  const list = isAccessoryPage.value 
    ? products.value.filter(isProductAccessory) 
    : products.value.filter(isProductLaptop)
  return list.filter(product => lineMatcher(product, lineKey)).length
}

const filteredProducts = computed(() => {
  const keyword = canonicalText(searchQuery.value)
  const sourceProducts = isAccessoryPage.value 
    ? products.value.filter(isProductAccessory) 
    : products.value.filter(isProductLaptop)
    
  let list = sourceProducts.filter(product => {
    const text = canonicalText(`${product.tenSP} ${product.brand} ${product.category} ${product.specs.join(' ')}`)
    const matchLine = lineMatcher(product)
    const matchBrand = selectedBrands.value.length === 0 || selectedBrands.value.includes(product.brand)
    const matchPrice = Number(product.gia || 0) <= Number(maxPrice.value || 200000000)
    const matchCpu = selectedCpus.value.length === 0 || selectedCpus.value.some(cpu => text.includes(canonicalText(cpu)))
    const matchSearch = !keyword || text.includes(keyword)
    return matchLine && matchBrand && matchPrice && matchCpu && matchSearch
  })

  if (activeSort.value === 'price_asc') list = list.sort((a, b) => a.gia - b.gia)
  if (activeSort.value === 'newest') list = list.sort((a, b) => b.id_sanpham - a.id_sanpham)
  if (activeSort.value === 'rating') list = list.sort((a, b) => b.rating - a.rating)
  if (activeSort.value === 'popular') list = list.sort((a, b) => b.reviews - a.reviews)
  return list
})

const pageCount = computed(() => Math.max(1, Math.ceil(filteredProducts.value.length / itemsPerPage)))
const paginatedProducts = computed(() => {
  console.log('Laptop itemsPerPage is:', itemsPerPage)
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredProducts.value.slice(start, start + itemsPerPage)
})


const compactPages = computed(() => {
  const total = pageCount.value
  const current = currentPage.value
  if (total <= 4) return Array.from({ length: total }, (_, i) => i + 1)
  const pages = [1]
  
  let start = Math.max(2, current - 1)
  let end = Math.min(total - 1, current + 1)

  if (current <= 2) {
    end = 3
  } else if (current >= total - 1) {
    start = total - 2
  }

  if (start > 2) pages.push('...')
  for (let page = start; page <= end; page++) pages.push(page)
  if (end < total - 1) pages.push('...')
  pages.push(total)
  return pages
})

const heroProducts = computed(() => filteredProducts.value.slice(0, 5))
const flagshipProducts = computed(() => {
  const list = isAccessoryPage.value 
    ? products.value.filter(isProductAccessory) 
    : products.value.filter(isProductLaptop)
  return list.slice().sort((a, b) => b.gia - a.gia).slice(0, 5)
})
const accessoryProducts = computed(() => products.value.filter(p => isProductAccessory(p)).slice(0, 10))

const selectLine = (line) => {
  activeLine.value = line
  currentPage.value = 1
}

const selectHeroCategory = (category) => {
  activeLine.value = category.line || 'all'
  searchQuery.value = category.q || ''
  currentPage.value = 1
}

const toggleBrand = (brand) => {
  const index = selectedBrands.value.indexOf(brand)
  index >= 0 ? selectedBrands.value.splice(index, 1) : selectedBrands.value.push(brand)
}

const toggleCpu = (cpu) => {
  const index = selectedCpus.value.indexOf(cpu)
  index >= 0 ? selectedCpus.value.splice(index, 1) : selectedCpus.value.push(cpu)
  currentPage.value = 1
}

const clearFilters = () => {
  selectedBrands.value = []
  selectedCpus.value = []
  maxPrice.value = isAccessoryPage.value ? 15000000 : 200000000
  searchQuery.value = ''
  activeLine.value = 'all'
  activeSort.value = 'popular'
  currentPage.value = 1
}

const formatPrice = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'd'
const viewDetail = (product) => router.push({ path: `/san-pham/${product.id_sanpham}`, query: product.id_bienthe ? { variant: product.id_bienthe } : {} })

const getSwal = async () => (await import('@/services/swal')).default

const resolveVariantId = async (product) => {
  if (product.id_bienthe) return product.id_bienthe
  const res = await api.get(`/sanpham/${product.id_sanpham}`, { skipGlobalLoader: true })
  const variants = res.data.bien_thes || res.data.bienThes || []
  return variants[0]?.id_bienthe
}

const addToCart = async (product) => {
  const token = getToken()
  const swal = await getSwal()
  if (!token) {
    swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập trước khi thêm giỏ hàng.')
    router.push({ path: '/dang-nhap', query: { redirect: route.fullPath } })
    return
  }

  try {
    const variantId = await resolveVariantId(product)
    if (!variantId) throw new Error('Sản phẩm chưa có biến thể.')

    await api.post('/gio-hang/them', { id_bienthe: variantId, soluong: 1 })
    window.dispatchEvent(new Event('cart-updated'))
    swal.success('Đã thêm vào giỏ', 'Sản phẩm đã được thêm vào giỏ hàng.')
  } catch (error) {
    swal.error('Lỗi giỏ hàng', error.response?.data?.message || error.message)
  }
}

const addToWishlist = async (product) => {
  const token = getToken()
  const swal = await getSwal()
  if (!token) {
    swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập trước khi thêm yêu thích.')
    router.push({ path: '/dang-nhap', query: { redirect: route.fullPath } })
    return
  }

  try {
    const variantId = await resolveVariantId(product)
    if (!variantId) throw new Error('Sản phẩm chưa có biến thể.')
    await api.post('/yeu-thich/them', { id_bienthe: variantId, soluong: 1 })
    window.dispatchEvent(new Event('wishlist-updated'))
    swal.success('Đã thêm yêu thích', 'Sản phẩm đã được lưu vào danh sách yêu thích.')
  } catch (error) {
    swal.error('Lỗi yêu thích', error.response?.data?.message || error.message)
  }
}

const scrollToCatalog = () => {
  setTimeout(() => {
    const el = document.getElementById('catalog')
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, 250)
}

watch(() => route.fullPath, () => {
  const line = String(route.query.line || route.meta?.line || '').toLowerCase()
  if (line && activeLinesList.value.some(item => item.key === line)) activeLine.value = line
  searchQuery.value = route.query.q ? String(route.query.q) : ''
  if (route.query.scroll === 'catalog' || route.query.q) {
    scrollToCatalog()
  }
})

watch(isAccessoryPage, (newVal) => {
  maxPrice.value = newVal ? 15000000 : 200000000
  activeLine.value = 'all'
  searchQuery.value = ''
  selectedBrands.value = []
  selectedCpus.value = []
  currentPage.value = 1
})

watch(filteredProducts, () => {
  currentPage.value = 1
})

onMounted(() => {
  const line = String(route.query.line || route.meta?.line || '').toLowerCase()
  if (line && activeLinesList.value.some(item => item.key === line)) activeLine.value = line
  searchQuery.value = route.query.q ? String(route.query.q) : ''
  loadProducts()
  if (route.query.scroll === 'catalog' || route.query.q) {
    scrollToCatalog()
  }
})
</script>

<template>
  <main class="laptop-page">
    <section class="lp-hero">
      <aside class="lp-sidebar">
        <h3>{{ isAccessoryPage ? 'Danh mục Phụ kiện' : 'Danh mục Gaming Laptop' }}</h3>
        <button
          v-for="category in heroCategoriesToDisplay"
          :key="category.label"
          class="line-btn"
          :class="{ active: searchQuery === category.q }"
          @click="selectHeroCategory(category)"
        >
          <component :is="category.icon" />
          <span>{{ category.label }}</span>
        </button>
        <button class="line-btn promo" @click="router.push('/khuyen-mai')">
          <Flame />
          <span>Khuyến mãi hot</span>
        </button>
      </aside>

      <div class="lp-hero-main">
        <div class="lp-hero-panel">
          <button class="hero-nav prev" type="button" aria-label="Slide trước">
            <ChevronLeft />
          </button>
          <div class="hero-copy">
            <span class="hero-kicker">{{ isAccessoryPage ? 'Phụ kiện cao cấp' : 'Công nghệ gaming' }}</span>
            <h1>{{ isAccessoryPage ? 'Trải nghiệm đỉnh cao Phụ kiện cực chất' : 'Hiệu năng đỉnh cao Chơi game cực chất' }}</h1>
            <p>{{ isAccessoryPage ? 'Khám phá chuột, bàn phím, tai nghe chính hãng với chất lượng vượt trội.' : 'Khám phá các mẫu laptop gaming chính hãng với hiệu năng mạnh mẽ, màn hình tốc độ cao và thiết kế đậm chất game thủ.' }}</p>
            <div class="hero-actions">
              <button @click="isAccessoryPage ? selectLine('all') : selectLine('gaming')">Mua ngay</button>
              <button class="secondary" @click="router.push('/khuyen-mai')">Xem ưu đãi</button>
            </div>
          </div>
          <button class="hero-nav next" type="button" aria-label="Slide sau">
            <ChevronRight />
          </button>
          <div class="hero-dots" aria-hidden="true">
            <span class="active"></span>
            <span></span>
            <span></span>
          </div>
        </div>

        <section class="lp-services">
          <article v-for="service in serviceCards" :key="service.title">
            <component :is="service.icon" />
            <div>
              <strong>{{ service.title }}</strong>
              <span>{{ service.desc }}</span>
            </div>
          </article>
        </section>
      </div>
    </section>

    <section class="lp-brands">
      <p>Đối tác chiến lược</p>
      <h2>Thương Hiệu Nổi Bật</h2>
      <div class="brand-marquee" aria-label="Laptop brand logos">
        <div class="brand-track">
          <div v-for="sequence in 2" :key="sequence" class="brand-sequence" aria-hidden="true">
            <button
              v-for="brand in brandShowcase"
              :key="`${brand.name}-${sequence}`"
              class="brand-logo-card"
              @click="searchQuery = brand.query"
            >
              <img :src="brand.logo" :alt="brand.name" loading="lazy" @error="$event.currentTarget.classList.add('is-broken')" />
              <span>{{ brand.name }}</span>
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="lp-flagship">
      <div class="section-copy">
        <span></span>
        <div>
          <h2>{{ isAccessoryPage ? 'Phụ kiện cao cấp bán chạy' : 'Máy flagship đắt tiền nhất' }}</h2>
          <p>{{ isAccessoryPage ? 'Những phụ kiện đỉnh cấp và chuyên nghiệp nhất dành cho góc máy của bạn.' : 'Những cấu hình cao cấp nhất trong kho, gồm cả laptop gaming và MacBook.' }}</p>
        </div>
      </div>
      <div class="flagship-row">
        <article v-for="product in flagshipProducts" :key="product.id_sanpham" class="flag-card" @click="viewDetail(product)">
          <img :src="product.image" :alt="product.tenSP" @error="handleImageFallback($event, 'https://placehold.co/600x420?text=NextGen+Laptop')" />
          <h3>{{ product.tenSP }}</h3>
          <div class="specs">
            <span v-for="spec in product.specs.slice(0, 3)" :key="spec">{{ spec }}</span>
          </div>
          <strong class="product-price">{{ formatPrice(product.gia) }}</strong>
          <div class="flag-actions">
            <button @click.stop="viewDetail(product)">Cấu hình</button>
            <button class="buy" @click.stop="addToCart(product)">Mua ngay</button>
          </div>
        </article>
      </div>
    </section>

    <section class="lp-catalog" id="catalog">
      <div class="catalog-title">
        <div>
          <span>{{ isAccessoryPage ? 'Accessories catalog' : 'Premium catalog' }}</span>
          <h2>{{ isAccessoryPage ? 'Danh sách Phụ kiện Premium' : 'Danh sách Laptop Premium' }}</h2>
        </div>
        <label class="search-box">
          <Search />
          <input v-model="searchQuery" placeholder="Tìm kiếm model, CPU, RTX..." />
        </label>
      </div>

      <div class="catalog-layout">
        <aside class="filter-card">
          <div class="filter-head">
            <div>
              <strong>Bộ lọc</strong>
              <span>Tinh chỉnh lựa chọn</span>
            </div>
            <button @click="clearFilters">Xóa tất cả</button>
          </div>
          <div class="filter-group filter-checks">
            <h4>{{ isAccessoryPage ? 'Loại phụ kiện' : 'Dòng máy' }}</h4>
            <label v-for="line in visibleLines" :key="line.key" :class="{ active: activeLine === line.key }">
              <span class="check-ui">
                <input type="checkbox" :checked="activeLine === line.key" @change="selectLine(line.key)" />
                <i></i>
              </span>
              <span>{{ line.label }}</span>
              <small>{{ lineCount(line.key) }}</small>
            </label>
          </div>
          <div class="filter-group filter-pills">
            <h4>Thương hiệu</h4>
            <button v-for="brand in brandOptions.slice(0, 6)" :key="brand" :class="{ active: selectedBrands.includes(brand) }" @click="toggleBrand(brand)">
              {{ brand }}
            </button>
          </div>
          <div class="filter-group filter-price">
            <h4>Giá</h4>
            <input v-model.number="maxPrice" type="range" :min="isAccessoryPage ? 100000 : 10000000" :max="isAccessoryPage ? 15000000 : 200000000" :step="isAccessoryPage ? 100000 : 1000000" @input="currentPage = 1" />
            <div>
              <span>{{ isAccessoryPage ? '100.000đ' : '10.000.000đ' }}</span>
              <span>{{ formatPrice(maxPrice) }}</span>
            </div>
          </div>
          <div v-if="!isAccessoryPage" class="filter-group filter-tags">
            <h4>CPU Type</h4>
            <button v-for="cpu in cpuOptions" :key="cpu" :class="{ active: selectedCpus.includes(cpu) }" @click="toggleCpu(cpu)">
              {{ cpu }}
            </button>
          </div>
          <button class="apply-filter" @click="currentPage = 1">
            <SlidersHorizontal />
            Áp dụng bộ lọc
          </button>
        </aside>

        <div ref="catalogResults" class="catalog-results">
          <div class="sort-row">
            <span>Đang hiển thị {{ paginatedProducts.length }}/{{ filteredProducts.length }} sản phẩm</span>
            <select v-model="activeSort">
              <option value="popular">Bán chạy nhất</option>
              <option value="newest">Mới nhất</option>
              <option value="price_asc">Giá từ thấp đến cao</option>
              <option value="rating">Đánh giá cao</option>
            </select>
          </div>

          <div v-if="isLoading" class="skeleton-grid" aria-label="Đang tải dữ liệu laptop">
            <article v-for="n in 8" :key="n" class="skeleton-card">
              <span class="skeleton-line skeleton-badge"></span>
              <span class="skeleton-media"></span>
              <span class="skeleton-line skeleton-title"></span>
              <span class="skeleton-line skeleton-text"></span>
              <span class="skeleton-line skeleton-price"></span>
            </article>
          </div>
          <div v-else class="product-grid">
            <article v-for="product in paginatedProducts" :key="product.id_bienthe ? 'v-' + product.id_bienthe : 'p-' + product.id_sanpham" class="product-card" @click="viewDetail(product)">
              <span class="discount">-13%</span>
              <img :src="product.image" :alt="product.tenSP" @error="handleImageFallback($event, 'https://placehold.co/600x420?text=NextGen+Laptop')" />
              <h3>
                {{ product.tenSP }}
                <span v-if="product.cpu || product.ram" class="title-specs-suffix">
                  ({{ [product.cpu, product.ram].filter(Boolean).join(' / ') }})
                </span>
              </h3>
              <div class="stars">★ {{ product.rating.toFixed(1) }} <span>({{ product.reviews }} đánh giá)</span></div>
              <div class="specs">
                <span v-for="spec in product.specs.slice(0, 4)" :key="spec">{{ spec }}</span>
              </div>
              <strong class="product-price">{{ formatPrice(product.gia) }}</strong>
              <del>{{ formatPrice(product.oldPrice) }}</del>
              <div class="mini-badges">
                <span>✓ Chính hãng</span>
                <span>Freeship 2H</span>
                <span>BH 24T</span>
              </div>
              <button class="hover-action-btn wishlist-btn" title="Yêu thích" @click.stop="addToWishlist(product)">
                <Heart />
              </button>
              <div class="product-actions">
                <button class="hover-action-btn cart-btn" title="Thêm giỏ hàng" @click.stop="addToCart(product)">
                  <ShoppingCart />
                </button>
              </div>
            </article>
          </div>

        </div>
      </div>

      <div class="pagination" v-if="pageCount > 1">
        <button class="page-nav" :disabled="currentPage === 1" @click="currentPage--">Trước</button>
        
        <template v-for="(page, idx) in compactPages" :key="idx">
          <span v-if="page === '...'" class="page-ellipsis">...</span>
          <button 
            v-else 
            class="page-number" 
            :class="{ active: page === currentPage }"
            @click="currentPage = page"
          >
            {{ page }}
          </button>
        </template>

        <button class="page-nav" :disabled="currentPage === pageCount" @click="currentPage++">Sau</button>
      </div>
    </section>

    <section v-if="accessoryProducts.length && !isAccessoryPage" class="lp-accessories">
      <div class="section-copy compact">
        <span></span>
        <div>
          <small>Gaming gear</small>
          <h2>Phụ kiện</h2>
        </div>
      </div>
      <div class="accessory-strip">
        <article v-for="product in accessoryProducts" :key="product.id_sanpham" class="product-card" @click="viewDetail(product)">
          <img :src="product.image" :alt="product.tenSP" @error="handleImageFallback($event, 'https://placehold.co/600x420?text=NextGen+Laptop')" />
          <h3>{{ product.tenSP }}</h3>
          <div class="stars">★ {{ product.rating.toFixed(1) }} <span>({{ product.reviews }})</span></div>
          <strong class="product-price">{{ formatPrice(product.gia) }}</strong>
        </article>
      </div>
    </section>

    <section v-if="isAccessoryPage" id="combos-section" class="lp-combos">
      <div class="combos-header">
        <span class="ambient-label">🎁 Combo Độc Quyền</span>
        <h2>MUA KÈM GIÁ SỐC - TIẾT KIỆM TỐI ĐA</h2>
        <p class="section-sub">Sở hữu trọn bộ trang bị chuyên nghiệp cho lập trình viên và game thủ với mức chiết khấu cực sâu.</p>
      </div>

      <div class="combos-bento-layout" v-if="combos && combos.length">
        <div v-for="combo in combos" :key="combo.id_combo" class="combo-bento-card">
          <div class="combo-main-content">
            <div class="combo-details">
              <span class="combo-discount-badge" v-if="getOriginalPrice(combo) > combo.giakhuyenmai">
                Tiết kiệm {{ formatPrice(getOriginalPrice(combo) - combo.giakhuyenmai) }}
              </span>
              <h3>{{ combo.ten_combo }}</h3>
              <p>{{ combo.mota }}</p>
              
              <div class="combo-pricing-group">
                <div class="price-block">
                  <span class="price-label">Giá Combo:</span>
                  <span class="price-val">{{ formatPrice(combo.giakhuyenmai) }}</span>
                </div>
                <div class="price-block old-price-block" v-if="getOriginalPrice(combo) > combo.giakhuyenmai">
                  <span class="price-label">Tổng Giá gốc:</span>
                  <span class="price-val-old">{{ formatPrice(getOriginalPrice(combo)) }}</span>
                </div>
              </div>

              <button type="button" class="combo-action-btn" @click="openCombo(combo)">
                Mua Trọn Bộ Combo
                <ChevronRight class="btn-chevron" />
              </button>
            </div>

            <div class="combo-visual-connector" v-if="combo.products && combo.products.length">
              <div v-for="(item, itemIdx) in combo.products" :key="itemIdx" class="connector-node">
                <div class="node-image-box">
                  <img :src="item.hinhanh || productImageUrl(item) || 'https://placehold.co/600x420?text=Product'" :alt="item.tenSP" />
                </div>
                <span class="node-title">{{ item.tenSP }}</span>
                <div v-if="itemIdx < combo.products.length - 1" class="node-plus-sign">+</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="combo-empty-state">
        <div class="combo-empty-icon">🎁</div>
        <h3>Combo phụ kiện giá sốc đang được cập nhật</h3>
        <p>Hiện chưa có gói combo nào trong hệ thống. Vui lòng thêm combo trong trang quản trị.</p>
      </div>
    </section>

    <section v-else id="showroom-section" class="lp-showroom">
      <div class="lp-showroom-copy">
        <small>NEXTGEN SHOWROOM</small>
        <h2>Trải nghiệm trực tiếp tại Showroom NextGen</h2>
        <p>
          Đến cửa hàng để tận tay dùng thử laptop, so sánh cấu hình thực tế và nhận tư vấn chọn máy phù hợp từ đội ngũ kỹ thuật.
        </p>

        <div class="lp-showroom-list">
          <div v-for="item in showroomHighlights" :key="item.text" class="lp-showroom-item">
            <span v-html="item.icon"></span>
            <div>
              <strong>{{ item.text }}</strong>
              <p>{{ item.desc }}</p>
            </div>
          </div>
        </div>

        <button class="lp-showroom-btn" @click="router.push('/lien-he')">Đăng ký trải nghiệm ngay</button>
      </div>

      <div class="lp-showroom-visual">
        <img src="/Gemini_Generated_Image_v5vppjv5vppjv5vp (2).png" alt="NextGen laptop showroom" />
      </div>
    </section>

    <ComboSelectionModal v-if="selectedCombo" :combo="selectedCombo" :show="showComboModal" @close="showComboModal = false; selectedCombo = null" />
  </main>
</template>

<style scoped>
.laptop-page {
  min-height: 100vh;
  background: #f4f7fb;
  color: #0f172a;
  font-family: 'Inter', 'Be Vietnam Pro', system-ui, sans-serif;
  padding-bottom: 64px;
  overflow-x: hidden;
}

.laptop-page *,
.laptop-page *::before,
.laptop-page *::after {
  box-sizing: border-box;
}

.lp-hero,
.lp-services,
.lp-featured-head,
.lp-brands,
.lp-flagship,
.lp-accessories,
.lp-showroom,
.lp-catalog {
  width: 100%;
  margin: 0 auto;
  padding-left: clamp(28px, 5vw, 96px);
  padding-right: clamp(28px, 5vw, 96px);
}

.lp-hero {
  display: grid;
  grid-template-columns: 250px minmax(0, 1fr);
  gap: 16px;
  padding-top: 34px;
}

.lp-sidebar,
.lp-hero-panel,
.lp-services article,
.lp-brands,
.lp-flagship,
.lp-accessories,
.lp-catalog,
.filter-card,
.sort-row,
.product-card,
.flag-card {
  background: #fff;
  border: 1px solid #dde6f2;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.07);
}

.lp-sidebar {
  border-radius: 14px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.lp-sidebar h3 {
  font-size: 13px;
  margin: 0 0 8px;
}

.line-btn {
  height: 34px;
  border: 0;
  background: transparent;
  color: #64748b;
  border-radius: 9px;
  padding: 0 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  font-size: 12px;
  cursor: pointer;
  text-align: left;
}

.line-btn svg {
  width: 15px;
  height: 15px;
}

.line-btn:hover,
.line-btn.active {
  background: #eff6ff;
  color: #2563eb;
}

.line-btn.promo {
  color: #ef4444;
  margin-top: 8px;
  border-top: 1px solid #edf2f7;
  padding-top: 12px;
  border-radius: 0;
}

.lp-hero-panel {
  min-height: clamp(460px, 52vh, 680px);
  border-radius: 16px;
  overflow: hidden;
  position: relative;
  background:
    linear-gradient(90deg, rgba(15, 23, 42, 0.88), rgba(15, 23, 42, 0.24)),
    url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1500&q=80') center/cover;
}

.hero-copy {
  position: relative;
  z-index: 2;
  width: min(520px, 58%);
  padding: 82px 46px;
  color: #fff;
}

.hero-kicker {
  display: inline-flex;
  color: #93c5fd;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 11px;
  font-weight: 900;
  margin-bottom: 14px;
}

.hero-copy h1 {
  font-size: clamp(34px, 5vw, 58px);
  line-height: 0.95;
  margin: 0 0 18px;
}

.hero-copy p {
  color: #dbeafe;
  line-height: 1.7;
  margin: 0;
}

.hero-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}

.hero-actions button,
.flag-actions button,
.link-btn {
  border: 0;
  border-radius: 10px;
  background: #2563eb;
  color: white;
  height: 42px;
  padding: 0 18px;
  font-weight: 900;
  cursor: pointer;
}

.hero-actions .secondary,
.flag-actions button:first-child {
  background: #fff;
  color: #0f172a;
}

.hero-preview {
  position: absolute;
  right: 28px;
  bottom: 26px;
  display: flex;
  gap: 10px;
}

.hero-preview article {
  width: 132px;
  height: 92px;
  border-radius: 12px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.88);
  cursor: pointer;
}

.hero-preview img {
  width: 100%;
  height: 66px;
  object-fit: cover;
}

.hero-preview strong {
  display: block;
  padding: 3px 8px;
  font-size: 11px;
}

.lp-services {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-top: 16px;
}

.lp-services article {
  border-radius: 12px;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.lp-services svg {
  width: 28px;
  height: 28px;
  color: #2563eb;
}

.lp-services strong,
.lp-services span {
  display: block;
}

.lp-services strong {
  font-size: 14px;
}

.lp-services span {
  color: #64748b;
  font-size: 12px;
  margin-top: 2px;
}

.lp-featured-head {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  margin-top: 64px;
  gap: 20px;
}

.lp-featured-head h2,
.lp-brands h2,
.section-copy h2,
.catalog-title h2 {
  margin: 0;
  font-size: 30px;
}

.tab-row {
  display: flex;
  gap: 10px;
}

.tab-row button {
  border: 0;
  height: 38px;
  border-radius: 999px;
  padding: 0 20px;
  background: transparent;
  color: #64748b;
  font-weight: 900;
  cursor: pointer;
}

.tab-row button.active,
.tab-row button:hover {
  background: #2563eb;
  color: #fff;
}

.link-btn {
  justify-self: end;
  background: transparent;
  color: #2563eb;
  padding: 0;
}

.lp-brands {
  border-radius: 0 0 18px 18px;
  margin-top: 46px;
  padding: 44px 46px;
  text-align: center;
}

.lp-brands p,
.catalog-title span,
.section-copy small {
  margin: 0 0 6px;
  color: #7aa2ff;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  font-size: 11px;
  font-weight: 900;
}

.brand-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 12px;
  margin-top: 28px;
}

.brand-grid button {
  height: 84px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-weight: 900;
  color: #475569;
  cursor: pointer;
}

.brand-grid button:hover {
  border-color: #2563eb;
  color: #2563eb;
  transform: translateY(-2px);
}

.lp-flagship,
.lp-accessories,
.lp-catalog {
  border-radius: 20px;
  margin-top: 42px;
  padding: 42px;
  width: calc(100% - clamp(56px, 10vw, 192px));
}

.lp-brands {
  width: calc(100% - clamp(56px, 10vw, 192px));
}

.lp-featured-head {
  width: calc(100% - clamp(56px, 10vw, 192px));
  padding-left: 0;
  padding-right: 0;
}

.section-copy {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 30px;
}

.section-copy > span {
  width: 16px;
  height: 16px;
  background: #2563eb;
  transform: rotate(45deg);
  margin-top: 6px;
  flex: 0 0 auto;
}

.section-copy p {
  color: #94a3b8;
  font-size: 18px;
  margin: 12px 0 0;
}

.flagship-row,
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 16px;
}

.flag-card,
.product-card {
  border-radius: 14px;
  padding: 14px;
  position: relative;
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.flag-card:hover,
.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 24px 52px rgba(37, 99, 235, 0.14);
}

.badge-row {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 10px;
}

.badge-row span,
.badge-row small,
.discount {
  background: #0f172a;
  color: #fff;
  border-radius: 7px;
  padding: 7px 10px;
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
}

.badge-row small {
  background: #f59e0b;
}

.flag-card img,
.product-card img {
  width: 100%;
  aspect-ratio: 1.36;
  object-fit: cover;
  border-radius: 12px;
  background: #f1f5f9;
}

.flag-card h3,
.product-card h3 {
  font-size: 15px;
  line-height: 1.35;
  min-height: 42px;
  margin: 14px 0 8px;
}

.title-specs-suffix {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
  margin-left: 4px;
}

.specs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin: 8px 0;
}

.specs span,
.mini-badges span {
  border-radius: 7px;
  background: #edf4ff;
  color: #475569;
  padding: 5px 8px;
  font-size: 11px;
  font-weight: 800;
}

.flag-card strong,
.product-card strong {
  display: block;
  font-size: 20px;
  margin: 12px 0 10px;
}

.flag-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.flag-actions .buy {
  background: #1d4ed8;
}

.section-copy.compact {
  align-items: center;
}

.accessory-strip {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: 210px;
  gap: 14px;
  overflow-x: auto;
  padding-bottom: 16px;
}

.lp-catalog {
  background: #f8fafc;
}

.catalog-title {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  align-items: center;
  margin-bottom: 24px;
}

.search-box {
  width: min(360px, 100%);
  height: 42px;
  background: #fff;
  border: 1px solid #dbe4f0;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 14px;
}

.search-box svg {
  width: 17px;
  color: #94a3b8;
}

.search-box input {
  border: 0;
  outline: 0;
  width: 100%;
  font-weight: 700;
}

.catalog-layout {
  display: grid;
  grid-template-columns: 210px minmax(0, 1fr);
  gap: 18px;
  align-items: start;
}

.filter-card {
  border-radius: 12px;
  padding: 12px 14px;
  align-self: start;
  position: sticky;
  top: 124px;
}

.filter-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filter-head button {
  border: 0;
  background: transparent;
  color: #2563eb;
  font-weight: 900;
  cursor: pointer;
}

.filter-group {
  border-top: 1px solid #edf2f7;
  margin-top: 10px;
  padding-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-group h4 {
  margin: 0 0 4px;
  font-size: 12px;
  text-transform: uppercase;
  color: #0f172a;
}

.filter-group button {
  border: 0;
  background: #f1f5f9;
  border-radius: 9px;
  min-height: 34px;
  padding: 0 10px;
  text-align: left;
  color: #475569;
  font-weight: 800;
  cursor: pointer;
}

.filter-group button.active,
.filter-group button:hover {
  background: #2563eb;
  color: #fff;
}

.sort-row {
  border-radius: 14px;
  padding: 14px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.sort-row span {
  color: #64748b;
  font-weight: 700;
}

.sort-row select {
  width: 210px;
  height: 42px;
  border: 1px solid #dbe4f0;
  border-radius: 10px;
  padding: 0 14px;
  font-weight: 800;
}

.discount {
  position: absolute;
  left: 16px;
  top: 16px;
  background: #ef4444;
  z-index: 2;
}

.stars {
  color: #f59e0b;
  font-size: 13px;
  font-weight: 900;
}

.stars span,
.product-card del {
  color: #94a3b8;
  font-weight: 700;
}

.product-card del {
  display: block;
  margin-top: -4px;
}

.mini-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 12px;
  padding-right: 44px;
}

.cart-btn {
  position: absolute;
  right: 14px;
  bottom: 14px;
  width: 40px;
  height: 40px;
  border: 0;
  border-radius: 999px;
  background: #2563eb;
  color: #fff;
  display: grid;
  place-items: center;
  cursor: pointer;
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.35);
}

.cart-btn svg {
  width: 20px;
}

.loading-box {
  min-height: 220px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  color: #64748b;
  font-weight: 900;
  background: #fff;
  border: 1px dashed #cbd5e1;
}

.pagination {
  display: flex;
  gap: 8px;
  justify-content: center;
  margin-top: 28px;
}

.pagination button {
  min-width: 38px;
  height: 38px;
  border-radius: 10px;
  border: 1px solid #dbe4f0;
  background: #fff;
  font-weight: 900;
  cursor: pointer;
}

.pagination button.active {
  background: #2563eb;
  color: #fff;
}

.pagination svg {
  width: 16px;
}

/* Premium polish layer: keeps the page structure intact and unifies visual rhythm. */
.laptop-page {
  --lp-primary: #2563eb;
  --lp-primary-dark: #1d4ed8;
  --lp-price: #2563eb;
  --lp-accent: #3b82f6;
  --lp-ink: #0f172a;
  --lp-muted: #64748b;
  --lp-soft: #f8fafc;
  --lp-card: rgba(255, 255, 255, 0.96);
  --lp-border: rgba(148, 163, 184, 0.28);
  --lp-radius: 18px;
  --lp-radius-sm: 12px;
  --lp-shadow: 0 12px 34px rgba(15, 23, 42, 0.07), 0 2px 8px rgba(15, 23, 42, 0.04);
  --lp-shadow-hover: 0 22px 48px rgba(37, 99, 235, 0.13), 0 8px 18px rgba(15, 23, 42, 0.06);
  --lp-ease: 220ms cubic-bezier(0.22, 1, 0.36, 1);
  background:
    radial-gradient(circle at 12% 8%, rgba(37, 99, 235, 0.08), transparent 32%),
    radial-gradient(circle at 88% 26%, rgba(37, 99, 235, 0.07), transparent 28%),
    linear-gradient(180deg, #f8fbff 0%, #f3f6fb 46%, #f8fafc 100%);
}

.lp-hero,
.lp-services,
.lp-featured-head,
.lp-brands,
.lp-flagship,
.lp-accessories,
.lp-catalog {
  padding-left: clamp(32px, 5.5vw, 104px);
  padding-right: clamp(32px, 5.5vw, 104px);
}

.lp-hero {
  gap: 24px;
  padding-top: 34px;
}

.lp-services,
.lp-featured-head,
.lp-brands,
.lp-flagship,
.lp-accessories,
.lp-catalog {
  margin-top: 88px;
}

.lp-services {
  margin-top: 32px;
}

.lp-sidebar,
.lp-hero-panel,
.lp-services article,
.lp-brands,
.lp-flagship,
.lp-accessories,
.lp-catalog,
.filter-card,
.sort-row,
.product-card,
.flag-card {
  background: var(--lp-card);
  border-color: var(--lp-border);
  box-shadow: var(--lp-shadow);
}

.lp-sidebar,
.lp-hero-panel,
.lp-services article,
.lp-brands,
.lp-flagship,
.lp-accessories,
.lp-catalog,
.filter-card,
.sort-row {
  border-radius: var(--lp-radius);
}

.lp-sidebar {
  padding: 20px;
  gap: 8px;
}

.lp-hero .lp-sidebar {
  align-self: start;
  padding: 16px;
}

.lp-hero .line-btn {
  min-height: 36px;
  font-size: 12px;
}

.line-btn,
.tab-row button,
.brand-grid button,
.filter-head button,
.filter-group button,
.pagination button,
.hero-actions button,
.flag-actions button,
.link-btn,
.cart-btn {
  border-radius: var(--lp-radius-sm);
  transition:
    transform var(--lp-ease),
    box-shadow var(--lp-ease),
    border-color var(--lp-ease),
    background var(--lp-ease),
    color var(--lp-ease),
    opacity var(--lp-ease);
}

.line-btn {
  min-height: 40px;
  padding: 0 12px;
}

.line-btn:hover {
  transform: translateX(3px);
}

.line-btn.active,
.filter-group button.active,
.filter-group button:hover,
.tab-row button.active,
.tab-row button:hover {
  background: linear-gradient(135deg, var(--lp-primary), #3b82f6);
  color: #fff;
  box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
}

.line-btn.promo:hover {
  color: var(--lp-primary-dark);
  background: linear-gradient(135deg, #eff6ff, #ffffff);
  transform: translateX(3px);
}

.lp-hero-panel {
  min-height: clamp(360px, 48vh, 520px);
  background:
    linear-gradient(100deg, rgba(10, 18, 33, 0.9), rgba(15, 23, 42, 0.45) 48%, rgba(15, 23, 42, 0.12)),
    url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1800&q=84') center/cover;
}

.hero-copy {
  padding: clamp(42px, 5vw, 72px) clamp(34px, 4vw, 56px);
}

.hero-copy h1 {
  letter-spacing: -0.02em;
  line-height: 1.03;
  font-size: clamp(30px, 4vw, 48px);
}

.hero-actions button,
.flag-actions button {
  min-height: 44px;
  padding: 0 20px;
  background: linear-gradient(135deg, var(--lp-primary), var(--lp-primary-dark));
  box-shadow: 0 14px 28px rgba(37, 99, 235, 0.24);
}

.hero-actions button:hover,
.flag-actions button:hover,
.cart-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--lp-shadow-hover);
}

.hero-actions .secondary,
.flag-actions button:first-child {
  background: linear-gradient(135deg, #ffffff, #eef4ff);
  color: var(--lp-ink);
  border: 1px solid rgba(255, 255, 255, 0.6);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.hero-preview article {
  border: 1px solid rgba(255, 255, 255, 0.66);
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.18);
  transition: transform var(--lp-ease), box-shadow var(--lp-ease);
}

.hero-preview article:hover {
  transform: translateY(-6px) scale(1.02);
}

.hero-preview img {
  aspect-ratio: 16 / 9;
  height: auto;
}

.lp-services {
  gap: 24px;
}

.lp-services article {
  min-height: 92px;
  padding: 20px;
  background: linear-gradient(145deg, #ffffff, #f6f9ff);
  transition: transform var(--lp-ease), box-shadow var(--lp-ease), border-color var(--lp-ease);
}

.lp-services article:hover,
.brand-grid button:hover,
.flag-card:hover,
.product-card:hover {
  border-color: rgba(37, 99, 235, 0.34);
  transform: translateY(-6px);
  box-shadow: var(--lp-shadow-hover);
}

.lp-services svg {
  width: 32px;
  height: 32px;
  padding: 6px;
  border-radius: 10px;
  background: linear-gradient(135deg, #eaf2ff, #f5fbff);
}

.lp-featured-head,
.lp-brands,
.lp-flagship,
.lp-accessories,
.lp-catalog {
  width: calc(100% - clamp(48px, 8vw, 160px));
}

.lp-featured-head {
  row-gap: 24px;
}

.tab-row {
  gap: 8px;
  padding: 6px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.76);
  border: 1px solid rgba(148, 163, 184, 0.2);
}

.tab-row button {
  min-height: 40px;
  padding: 0 22px;
}

.link-btn:hover {
  color: var(--lp-primary-dark);
  transform: translateX(4px);
}

.lp-brands,
.lp-flagship,
.lp-accessories,
.lp-catalog {
  padding: clamp(40px, 5vw, 64px);
}

.brand-grid {
  gap: 16px;
}

.brand-grid button {
  min-height: 92px;
  background: linear-gradient(145deg, #ffffff, #f8fbff);
}

.section-copy {
  margin-bottom: 40px;
}

.flagship-row,
.product-grid {
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 24px;
}

.flag-card,
.product-card,
.skeleton-card {
  min-height: 442px;
  display: flex;
  flex-direction: column;
  border-radius: var(--lp-radius);
  padding: 16px;
  overflow: hidden;
}

.flag-card img,
.product-card img {
  aspect-ratio: 4 / 3;
  object-fit: contain;
  padding: 12px;
  background: linear-gradient(145deg, #f8fbff, #eaf1fb);
  transition: transform var(--lp-ease), filter var(--lp-ease);
}

.flag-card:hover img,
.product-card:hover img {
  transform: scale(1.035);
  filter: saturate(1.06);
}

.flag-card h3,
.product-card h3 {
  min-height: 46px;
  margin: 16px 0 8px;
  color: var(--lp-ink);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.specs {
  min-height: 58px;
  align-content: flex-start;
  gap: 8px;
}

.flag-card strong,
.product-card strong {
  margin-top: auto;
}

.mini-badges {
  min-height: 58px;
}

.badge-row span,
.badge-row small,
.discount {
  border-radius: 8px;
}

.badge-row small {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.discount {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  box-shadow: 0 10px 18px rgba(37, 99, 235, 0.22);
}

.cart-btn {
  background: linear-gradient(135deg, var(--lp-primary), var(--lp-accent));
}

.catalog-layout {
  gap: 24px;
}

.filter-card {
  padding: 20px;
}

.filter-group {
  gap: 8px;
  margin-top: 16px;
  padding-top: 16px;
}

.filter-group button {
  min-height: 40px;
}

.search-box,
.sort-row select {
  height: 48px;
  border-radius: var(--lp-radius-sm);
}

.sort-row {
  min-height: 80px;
}

.skeleton-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 24px;
}

.skeleton-card {
  background: #fff;
  border: 1px solid var(--lp-border);
  box-shadow: var(--lp-shadow);
}

.skeleton-media,
.skeleton-line {
  position: relative;
  overflow: hidden;
  display: block;
  border-radius: 12px;
  background: linear-gradient(90deg, #eef3fb 0%, #f8fafc 45%, #eef3fb 100%);
  background-size: 220% 100%;
  animation: lp-shimmer 1.25s ease-in-out infinite;
}

.skeleton-media {
  width: 100%;
  aspect-ratio: 4 / 3;
  margin: 10px 0 18px;
}

.skeleton-line {
  height: 14px;
  margin-bottom: 12px;
}

.skeleton-badge {
  width: 56px;
  height: 24px;
  border-radius: 8px;
}

.skeleton-title {
  width: 88%;
  height: 18px;
}

.skeleton-text {
  width: 68%;
}

.skeleton-price {
  width: 46%;
  height: 22px;
  margin-top: auto;
}

@keyframes lp-shimmer {
  0% {
    background-position: 120% 0;
  }

  100% {
    background-position: -120% 0;
  }
}

.pagination button:hover:not(:disabled),
.pagination button.active {
  background: linear-gradient(135deg, var(--lp-primary), var(--lp-primary-dark));
  color: #fff;
  box-shadow: 0 12px 22px rgba(37, 99, 235, 0.18);
  transform: translateY(-2px);
}

.lp-flagship {
  padding: clamp(28px, 3.5vw, 44px);
}

.lp-flagship .section-copy {
  align-items: center;
  gap: 14px;
  margin-bottom: 32px;
}

.lp-flagship .section-copy h2 {
  font-size: clamp(28px, 2.6vw, 42px);
}

.lp-flagship .section-copy p {
  font-size: clamp(15px, 1.35vw, 22px);
  margin: 0;
}

.lp-flagship .flagship-row {
  grid-template-columns: repeat(auto-fit, minmax(168px, 1fr));
  gap: 20px;
}

.lp-flagship .flag-card {
  min-height: 374px;
  padding: 12px;
  border-radius: 16px;
}

.lp-flagship .badge-row {
  margin-bottom: 8px;
}

.lp-flagship .badge-row span,
.lp-flagship .badge-row small {
  padding: 6px 9px;
  font-size: 10px;
}

.lp-flagship .flag-card img {
  aspect-ratio: 1.45;
  padding: 8px;
  border-radius: 12px;
}

.lp-flagship .flag-card h3 {
  min-height: 38px;
  font-size: 13px;
  line-height: 1.28;
  margin: 12px 0 8px;
}

.lp-flagship .specs {
  min-height: 34px;
  gap: 5px;
  margin: 5px 0;
}

.lp-flagship .specs span {
  padding: 4px 7px;
  border-radius: 6px;
  font-size: 9px;
  line-height: 1.15;
}

.lp-flagship .flag-card strong {
  font-size: 18px;
  margin: 10px 0 8px;
}

.lp-flagship .flag-actions {
  gap: 8px;
}

.lp-flagship .flag-actions button {
  min-height: 38px;
  height: 38px;
  padding: 0 10px;
  font-size: 13px;
  line-height: 1.1;
}

.lp-accessories {
  padding: clamp(28px, 3.5vw, 44px);
}

.lp-accessories .section-copy {
  margin-bottom: 28px;
}

.lp-accessories .accessory-strip {
  grid-auto-columns: 188px;
  gap: 16px;
  padding-bottom: 10px;
}

.lp-accessories .product-card {
  min-height: 286px;
  padding: 12px;
  border-radius: 16px;
}

.lp-accessories .product-card img {
  aspect-ratio: 1.45;
  padding: 8px;
  border-radius: 12px;
}

.lp-accessories .product-card h3 {
  min-height: 38px;
  font-size: 13px;
  line-height: 1.28;
  margin: 12px 0 8px;
}

.lp-accessories .stars {
  font-size: 12px;
  line-height: 1.2;
  margin-bottom: 8px;
}

.lp-accessories .product-card strong {
  margin: 0;
  font-size: 18px;
  line-height: 1.2;
}

.lp-showroom {
  display: grid;
  grid-template-columns: minmax(0, 0.95fr) minmax(360px, 1fr);
  gap: clamp(28px, 4vw, 56px);
  align-items: center;
  margin-top: clamp(28px, 4vw, 54px);
  padding-top: clamp(34px, 4.5vw, 58px);
  padding-bottom: clamp(34px, 4.5vw, 58px);
  border-radius: 22px;
  background:
    radial-gradient(circle at 100% 0%, rgba(37, 99, 235, 0.18), transparent 36%),
    linear-gradient(135deg, #0f172a 0%, #111c31 52%, #08111f 100%);
  color: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.18);
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
  overflow: hidden;
}

.lp-showroom-copy small {
  display: block;
  color: #38bdf8;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 0.05em;
  margin-bottom: 12px;
}

.lp-showroom-copy h2 {
  max-width: 720px;
  margin: 0 0 16px;
  font-size: clamp(28px, 3vw, 42px);
  line-height: 1.08;
  color: #ffffff;
}

.lp-showroom-copy > p {
  max-width: 680px;
  margin: 0 0 28px;
  color: #cbd5e1;
  font-size: 16px;
  line-height: 1.7;
}

.lp-showroom-list {
  display: grid;
  gap: 18px;
  margin-bottom: 30px;
}

.lp-showroom-item {
  display: grid;
  grid-template-columns: 32px minmax(0, 1fr);
  gap: 14px;
  align-items: start;
}

.lp-showroom-item > span {
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.12);
}

.lp-showroom-item svg {
  width: 17px;
  height: 17px;
}

.lp-showroom-item strong {
  display: block;
  margin-bottom: 3px;
  color: #ffffff;
  font-size: 15px;
}

.lp-showroom-item p {
  margin: 0;
  color: #94a3b8;
  font-size: 14px;
  line-height: 1.55;
}

.lp-showroom-btn {
  min-height: 48px;
  padding: 0 26px;
  border: 0;
  border-radius: 10px;
  background: #2563eb;
  color: #ffffff;
  font-weight: 800;
  font-size: 15px;
  cursor: pointer;
  box-shadow: 0 14px 28px rgba(37, 99, 235, 0.28);
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.lp-showroom-btn:hover {
  transform: translateY(-2px);
  background: #1d4ed8;
  box-shadow: 0 18px 34px rgba(37, 99, 235, 0.36);
}

.lp-showroom-visual {
  position: relative;
  min-height: 320px;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 22px 50px rgba(0, 0, 0, 0.28);
}

.lp-showroom-visual::after {
  content: "";
  position: absolute;
  inset: auto 18px 18px auto;
  width: 26px;
  height: 26px;
  background: rgba(255, 255, 255, 0.9);
  clip-path: polygon(50% 0, 61% 35%, 100% 50%, 61% 65%, 50% 100%, 39% 65%, 0 50%, 39% 35%);
  opacity: 0.78;
}

.lp-showroom-visual img {
  width: 100%;
  height: 100%;
  min-height: 320px;
  object-fit: cover;
  display: block;
}

.lp-brands {
  width: 100vw;
  margin-left: calc(50% - 50vw);
  margin-right: calc(50% - 50vw);
  border-left: 0;
  border-right: 0;
  border-radius: 0;
  padding: clamp(34px, 4vw, 54px) 0;
  overflow: hidden;
  background:
    linear-gradient(180deg, rgba(19, 35, 59, 0.98), rgba(8, 18, 34, 0.98)),
    #0b1628;
  box-shadow: inset 0 1px rgba(255, 255, 255, 0.08), inset 0 -1px rgba(255, 255, 255, 0.06);
}

.lp-brands p,
.lp-brands h2 {
  padding-left: clamp(32px, 5.5vw, 104px);
  padding-right: clamp(32px, 5.5vw, 104px);
}

.lp-brands p {
  color: #7dd3fc;
}

.lp-brands h2 {
  color: #ffffff;
}

.brand-marquee {
  position: relative;
  width: min(100% - clamp(48px, 10vw, 192px), 1680px);
  margin-top: 32px;
  overflow: hidden;
  margin-left: auto;
  margin-right: auto;
  padding: 8px 0;
}

.brand-track {
  width: max-content;
  display: flex;
  gap: 18px;
  animation: brand-loop 28s linear infinite;
  will-change: transform;
}

.brand-sequence {
  display: flex;
  gap: 18px;
  flex: 0 0 auto;
}

.brand-logo-card {
  position: relative;
  width: clamp(116px, 10vw, 176px);
  height: 58px;
  border: 1px solid rgba(226, 232, 240, 0.12);
  border-radius: 8px;
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  display: grid;
  place-items: center;
  padding: 9px 22px;
  cursor: pointer;
  box-shadow: 0 12px 22px rgba(0, 0, 0, 0.24);
  transition: transform var(--lp-ease), background var(--lp-ease), filter var(--lp-ease), border-color var(--lp-ease);
  flex: 0 0 auto;
}

.brand-logo-card:hover {
  transform: translateY(-2px);
  background: linear-gradient(180deg, #ffffff, #eef6ff);
  border-color: rgba(125, 211, 252, 0.4);
  filter: drop-shadow(0 10px 18px rgba(14, 165, 233, 0.18));
}

.brand-logo-card img {
  max-width: 124px;
  max-height: 36px;
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: contrast(1.05);
}

.brand-logo-card span {
  position: absolute;
  inset: 0;
  display: none;
  place-items: center;
  color: #0f172a;
  font-size: 22px;
  font-weight: 900;
  letter-spacing: 0.02em;
}

.brand-logo-card img.is-broken {
  display: none;
}

.brand-logo-card img.is-broken + span {
  display: grid;
}

@keyframes brand-loop {
  from {
    transform: translateX(0);
  }

  to {
    transform: translateX(calc(-50% - 9px));
  }
}

.lp-catalog {
  width: 100%;
  margin-top: 72px;
  padding: clamp(28px, 4vw, 52px) clamp(32px, 5.5vw, 104px);
  background: transparent;
  border: 0;
  border-radius: 0;
  box-shadow: none;
}

.lp-catalog .catalog-title {
  margin-bottom: 18px;
}

.lp-catalog .catalog-title h2 {
  font-size: clamp(22px, 2.1vw, 30px);
}

.lp-catalog .catalog-title span {
  font-size: 9px;
}

.lp-catalog .catalog-layout {
  grid-template-columns: 236px minmax(0, 1fr);
  gap: 18px;
}

.lp-catalog .filter-card {
  padding: 14px;
  border-radius: 14px;
  top: 104px;
}

.lp-catalog .filter-head strong,
.lp-catalog .filter-group h4 {
  font-size: 11px;
}

.lp-catalog .filter-group {
  gap: 6px;
  margin-top: 12px;
  padding-top: 12px;
}

.lp-catalog .filter-group button {
  min-height: 34px;
  padding: 0 10px;
  border-radius: 10px;
  font-size: 12px;
}

.lp-catalog .sort-row {
  min-height: 58px;
  padding: 10px 14px;
  margin-bottom: 16px;
  border-radius: 14px;
}

.lp-catalog .sort-row span,
.lp-catalog .sort-row select {
  font-size: 12px;
}

.lp-catalog .sort-row select {
  width: 190px;
  height: 38px;
}

.lp-catalog .product-grid,
.lp-catalog .skeleton-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
}

.lp-catalog .product-card {
  min-height: 330px;
  padding: 12px;
  border-radius: 14px;
}

.lp-catalog .discount {
  left: 12px;
  top: 12px;
  padding: 6px 8px;
  font-size: 10px;
}

.lp-catalog .product-card img {
  aspect-ratio: 1.5;
  padding: 0;
  width: 100%;
  object-fit: cover;
  object-position: center;
  border-radius: 12px;
}

.lp-catalog .product-card h3 {
  min-height: 36px;
  font-size: 12.5px;
  line-height: 1.25;
  margin: 10px 0 6px;
}

.lp-catalog .stars {
  font-size: 11px;
  line-height: 1.2;
}

.lp-catalog .specs {
  min-height: 38px;
  gap: 5px;
  margin: 6px 0;
}

.lp-catalog .specs span,
.lp-catalog .mini-badges span {
  padding: 4px 7px;
  border-radius: 6px;
  font-size: 9px;
  line-height: 1.15;
}

.lp-catalog .product-card strong {
  font-size: 17px;
  margin: 8px 0 4px;
}

.lp-catalog .product-card del {
  font-size: 11px;
  margin-top: 0;
}

.lp-catalog .mini-badges {
  min-height: 42px;
  gap: 5px;
  margin-top: 8px;
  padding-right: 0;
}

.lp-catalog .catalog-layout {
  align-items: start;
  position: relative;
  overflow: visible;
}

.lp-catalog .filter-card {
  position: sticky;
  top: 118px;
  height: fit-content;
  max-height: none;
  overflow: visible;
  padding: 18px;
  border-radius: 8px;
  background: linear-gradient(180deg, #edf4ff, #e7f0ff);
  border: 1px solid rgba(191, 208, 232, 0.9);
  box-shadow: 0 20px 42px rgba(37, 99, 235, 0.12);
}

.lp-catalog .filter-head {
  align-items: flex-start;
  padding-bottom: 14px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.22);
}

.lp-catalog .filter-head strong {
  display: block;
  font-size: 12px;
  color: #0f172a;
  margin-bottom: 3px;
}

.lp-catalog .filter-head span {
  display: block;
  font-size: 9px;
  color: #64748b;
}

.lp-catalog .filter-head button {
  font-size: 10px;
  color: #2563eb;
}

.lp-catalog .filter-group {
  border-top: 0;
  padding-top: 14px;
  margin-top: 10px;
}

.lp-catalog .filter-group h4 {
  position: relative;
  padding-left: 10px;
  margin-bottom: 8px;
  color: #0f172a;
  font-size: 11px;
  letter-spacing: 0;
}

.lp-catalog .filter-group h4::before {
  content: '';
  position: absolute;
  left: 0;
  top: 2px;
  width: 2px;
  height: 15px;
  border-radius: 999px;
  background: #2563eb;
}

.filter-checks label {
  min-height: 28px;
  display: grid;
  grid-template-columns: 18px 1fr auto;
  align-items: center;
  gap: 8px;
  color: #475569;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
}

.filter-checks label small {
  color: #64748b;
  font-size: 9px;
  font-weight: 800;
}

.check-ui {
  position: relative;
  width: 14px;
  height: 14px;
}

.check-ui input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.check-ui i {
  position: absolute;
  inset: 1px;
  border-radius: 3px;
  border: 1px solid #cbd5e1;
  background: #fff;
}

.filter-checks label.active .check-ui i {
  border-color: #2563eb;
  background: #2563eb;
}

.filter-checks label.active .check-ui i::after {
  content: '✓';
  position: absolute;
  inset: -2px 0 0;
  text-align: center;
  color: #fff;
  font-size: 11px;
  font-weight: 900;
}

.filter-pills,
.filter-tags {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 7px;
}

.filter-pills h4,
.filter-tags h4,
.filter-price h4 {
  grid-column: 1 / -1;
}

.lp-catalog .filter-pills button,
.lp-catalog .filter-tags button {
  min-height: 26px;
  padding: 0 8px;
  border-radius: 5px;
  border: 1px solid #c9d7ee;
  background: rgba(255, 255, 255, 0.62);
  color: #2563eb;
  font-size: 9px;
  font-weight: 800;
}

.lp-catalog .filter-pills button.active,
.lp-catalog .filter-tags button.active {
  border-color: #2563eb;
  background: #2563eb;
  color: #fff;
}

.filter-price input[type='range'] {
  width: 100%;
  accent-color: #2563eb;
  cursor: pointer;
}

.filter-price > div {
  display: flex;
  justify-content: space-between;
  color: #64748b;
  font-size: 8.5px;
  font-weight: 700;
}

.apply-filter {
  width: 100%;
  height: 38px;
  margin-top: 14px;
  border: 0;
  border-radius: 6px;
  background: linear-gradient(135deg, #0f6be8, #004cc5);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-weight: 900;
  cursor: pointer;
  box-shadow: 0 14px 24px rgba(37, 99, 235, 0.22);
}

.apply-filter svg {
  width: 15px;
  height: 15px;
}

.lp-catalog .product-card strong,
.lp-flagship .flag-card strong,
.lp-accessories .product-card strong {
  color: #0ea5e9;
}

@media (min-width: 1101px) {
  .laptop-page {
    --lp-catalog-sticky-top: 118px;
  }

  .lp-catalog {
    padding-bottom: 42px;
  }

  .lp-catalog .catalog-layout {
    height: auto;
    min-height: 0;
    overflow: visible;
    align-items: start;
    border-radius: 18px;
    isolation: isolate;
  }

  .lp-catalog .filter-card {
    position: sticky;
    top: var(--lp-catalog-sticky-top);
    height: fit-content;
    max-height: none;
    overflow: visible;
    align-self: start;
    overscroll-behavior: auto;
    scrollbar-gutter: auto;
    padding-bottom: 28px;
    scrollbar-width: none;
  }

  .catalog-results {
    height: auto;
    min-height: 0;
    overflow: visible;
    padding: 0;
    scroll-behavior: smooth;
    overscroll-behavior: auto;
    scrollbar-gutter: auto;
    -webkit-overflow-scrolling: touch;
    contain: none;
  }

  .catalog-results::-webkit-scrollbar {
    width: 0;
  }

  .lp-catalog .filter-card::-webkit-scrollbar {
    width: 7px;
  }

  .catalog-results::-webkit-scrollbar-track {
    background: transparent;
  }

  .lp-catalog .filter-card::-webkit-scrollbar-track {
    background: rgba(226, 232, 240, 0.55);
    border-radius: 999px;
  }

  .catalog-results::-webkit-scrollbar-thumb {
    background: transparent;
    border: 0;
  }

  .lp-catalog .filter-card::-webkit-scrollbar-thumb {
    background: rgba(37, 99, 235, 0.55);
    border: 2px solid rgba(226, 232, 240, 0.72);
    border-radius: 999px;
  }

  .lp-catalog .sort-row {
    position: relative;
    top: auto;
    z-index: 1;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(12px);
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
  }

  .catalog-results .product-grid,
  .catalog-results .skeleton-grid {
    padding-bottom: 18px;
  }
}

.product-actions {
  position: absolute;
  right: 10px;
  bottom: 10px;
  display: flex;
  gap: 8px;
  opacity: 0;
  pointer-events: none;
  transform: translateY(8px);
  transition: opacity var(--lp-ease), transform var(--lp-ease);
}

.lp-catalog .product-card:hover .product-actions {
  opacity: 1;
  pointer-events: auto;
  transform: translateY(0);
}

.lp-catalog .wishlist-btn {
  position: absolute;
  right: 10px;
  top: 10px;
  opacity: 0;
  pointer-events: none;
  transform: translateY(-6px) scale(0.96);
  transition: opacity var(--lp-ease), transform var(--lp-ease), background var(--lp-ease);
  z-index: 3;
}

.lp-catalog .product-card:hover .wishlist-btn {
  opacity: 1;
  pointer-events: auto;
  transform: translateY(0) scale(1);
}

.hover-action-btn,
.product-actions .cart-btn {
  position: static;
  width: 36px;
  height: 36px;
  border: 0;
  border-radius: 12px;
  display: grid;
  place-items: center;
  cursor: pointer;
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.16);
}

.hover-action-btn svg,
.product-actions .cart-btn svg {
  width: 17px;
  height: 17px;
}

.wishlist-btn {
  background: rgba(255, 255, 255, 0.95);
  color: #ef4444;
}

.wishlist-btn:hover {
  background: linear-gradient(135deg, #fff1f2, #ffffff);
  transform: translateY(-2px) scale(1.03);
}

.product-actions .cart-btn {
  background: linear-gradient(135deg, var(--lp-primary), var(--lp-accent));
  color: #fff;
}

.lp-catalog .product-grid {
  align-items: stretch;
}

.lp-catalog .product-card {
  display: flex;
  flex-direction: column;
  min-height: 348px;
  padding: 14px;
  border-radius: 16px;
  background: linear-gradient(180deg, #ffffff, #f9fbff);
  border-color: rgba(203, 213, 225, 0.85);
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
}

.lp-catalog .product-card:hover {
  transform: translateY(-5px);
  border-color: rgba(14, 165, 233, 0.42);
  box-shadow: 0 24px 48px rgba(14, 165, 233, 0.14);
}

.lp-catalog .product-card img {
  flex: 0 0 auto;
  height: 168px;
  aspect-ratio: auto;
  background: #f1f6fd;
  border: 0;
  object-fit: contain;
  padding: 10px;
}

.lp-catalog .product-card h3 {
  min-height: 42px;
  margin: 12px 0 7px;
  font-size: 12.8px;
  line-height: 1.28;
}

.lp-catalog .stars {
  margin: 0 0 7px;
  color: #f59e0b;
  font-weight: 900;
}

.lp-catalog .stars span {
  color: #94a3b8;
}

.lp-catalog .specs {
  min-height: 52px;
  align-content: start;
  margin: 0 0 10px;
}

.lp-catalog .product-card strong {
  margin-top: auto;
  color: #0284c7;
  font-size: 19px;
  line-height: 1.15;
}

.lp-catalog .product-card del {
  color: #94a3b8;
  font-weight: 800;
}

.lp-catalog .mini-badges {
  min-height: 46px;
  margin-top: 10px;
  padding-right: 42px;
}

.lp-catalog .mini-badges span {
  background: #edf6ff;
}

.lp-catalog .discount {
  background: #f43f5e;
  box-shadow: 0 12px 22px rgba(244, 63, 94, 0.26);
}

.lp-catalog .wishlist-btn {
  right: 12px;
  top: 12px;
  width: 38px;
  height: 38px;
  border-radius: 12px;
  color: #ef4444;
  background: #ffffff;
  box-shadow: 0 14px 26px rgba(15, 23, 42, 0.14);
}

.lp-catalog .wishlist-btn:hover {
  background: #ef4444;
  color: #ffffff;
  transform: translateY(-2px) scale(1.04);
  box-shadow: 0 18px 30px rgba(239, 68, 68, 0.34);
}

.lp-catalog .wishlist-btn:hover svg {
  fill: currentColor;
}

.lp-catalog .product-actions {
  right: 12px;
  bottom: 14px;
}

.lp-catalog .product-actions .cart-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: #0ea5e9;
  color: #ffffff;
  box-shadow: 0 14px 26px rgba(14, 165, 233, 0.26);
}

.lp-catalog .product-actions .cart-btn:hover {
  background: #0284c7;
  transform: translateY(-2px);
  box-shadow: 0 18px 30px rgba(14, 165, 233, 0.34);
}

.lp-catalog .pagination {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin: 36px 0 12px;
  align-items: center;
  flex-wrap: wrap;
}

.lp-catalog .pagination .page-nav,
.lp-catalog .pagination .page-indicator,
.lp-catalog .pagination .page-number,
.lp-catalog .pagination .page-ellipsis {
  height: 38px;
  border-radius: 10px;
  border: 1px solid #d8e4f4;
  background: #ffffff;
  color: #0f172a;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
}

.lp-catalog .pagination .page-nav {
  min-width: 78px;
  padding: 0 14px;
}

.lp-catalog .pagination .page-number {
  min-width: 42px;
  padding: 0 12px;
}

.lp-catalog .pagination .page-indicator {
  min-width: 64px;
  padding: 0 14px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  border-color: #2563eb;
  color: #ffffff;
  box-shadow: 0 14px 26px rgba(37, 99, 235, 0.24);
}

.lp-catalog .pagination .page-number.active {
  min-width: 48px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  border-color: #2563eb;
  color: #ffffff;
  box-shadow: 0 14px 26px rgba(37, 99, 235, 0.24);
}

.lp-catalog .pagination .page-ellipsis {
  min-width: 30px;
  border-color: transparent;
  background: transparent;
  color: #64748b;
  box-shadow: none;
}

.lp-catalog .pagination .page-nav:hover:not(:disabled),
.lp-catalog .pagination .page-number:hover:not(.active) {
  background: #e0f2fe;
  color: #2563eb;
  transform: translateY(-2px);
}

.lp-catalog .pagination .page-nav:disabled {
  opacity: 0.48;
  cursor: not-allowed;
}

.lp-hero {
  width: min(100%, 1720px);
  grid-template-columns: 250px minmax(0, 1fr);
  gap: 18px;
  align-items: stretch;
  padding: 26px clamp(28px, 5vw, 96px) 0;
}

.lp-hero-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.lp-hero .lp-sidebar {
  min-height: 384px;
  padding: 14px 16px;
  border-radius: 14px;
  gap: 2px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
}

.lp-hero .lp-sidebar h3 {
  font-size: 12px;
  margin-bottom: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid #edf2f7;
}

.lp-hero .line-btn {
  min-height: 27px;
  height: 27px;
  border-radius: 8px;
  padding: 0 4px;
  gap: 8px;
  font-size: 10.5px;
  font-weight: 700;
  background: transparent;
}

.lp-hero .line-btn svg {
  width: 12px;
  height: 12px;
  color: #94a3b8;
}

.lp-hero .line-btn:hover,
.lp-hero .line-btn.active {
  background: #f1f6ff;
  color: #2563eb;
}

.lp-hero .line-btn.promo {
  min-height: 36px;
  margin-top: 6px;
  padding-top: 10px;
  color: #ef4444;
}

.lp-hero-panel {
  min-height: 410px;
  border-radius: 14px;
  background:
    linear-gradient(90deg, rgba(3, 7, 18, 0.94) 0%, rgba(15, 23, 42, 0.78) 42%, rgba(15, 23, 42, 0.38) 68%, rgba(15, 23, 42, 0.16) 100%),
    url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1500&q=80') center/cover;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
}

.lp-hero-panel::after {
  content: '';
  position: absolute;
  right: 18px;
  bottom: 20px;
  width: 26px;
  height: 26px;
  background: linear-gradient(135deg, transparent 40%, rgba(255, 255, 255, 0.72));
  clip-path: polygon(50% 0, 62% 38%, 100% 50%, 62% 62%, 50% 100%, 38% 62%, 0 50%, 38% 38%);
}

.lp-hero .hero-copy {
  width: min(460px, 58%);
  padding: 66px 46px 52px;
}

.lp-hero .hero-kicker {
  font-size: 10px;
  padding: 6px 12px;
  margin-bottom: 14px;
  border-radius: 999px;
  background: rgba(37, 99, 235, 0.48);
  color: #dbeafe;
  border: 1px solid rgba(147, 197, 253, 0.5);
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.24);
}

.lp-hero .hero-copy h1 {
  max-width: 420px;
  font-size: clamp(26px, 3vw, 42px);
  line-height: 1.06;
  margin-bottom: 14px;
  color: #ffffff;
  text-shadow: 0 5px 24px rgba(0, 0, 0, 0.82), 0 1px 2px rgba(0, 0, 0, 0.9);
}

.lp-hero .hero-copy p {
  max-width: 470px;
  font-size: 12px;
  line-height: 1.65;
  color: #ffffff;
  font-weight: 650;
  text-shadow: 0 3px 14px rgba(0, 0, 0, 0.82), 0 1px 2px rgba(0, 0, 0, 0.88);
}

.lp-hero .hero-actions {
  gap: 10px;
  margin-top: 20px;
}

.lp-hero .hero-actions button {
  height: 38px;
  border-radius: 8px;
  padding: 0 20px;
  font-size: 12px;
}

.hero-nav {
  position: absolute;
  top: 50%;
  z-index: 4;
  width: 34px;
  height: 34px;
  transform: translateY(-50%);
  border: 0;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.94);
  color: #0f172a;
  display: grid;
  place-items: center;
  cursor: pointer;
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
  transition: transform var(--lp-ease), background var(--lp-ease);
}

.hero-nav:hover {
  transform: translateY(-50%) scale(1.05);
  background: #fff;
}

.hero-nav svg {
  width: 18px;
  height: 18px;
}

.hero-nav.prev {
  left: 18px;
}

.hero-nav.next {
  right: 18px;
}

.hero-dots {
  position: absolute;
  left: 46px;
  bottom: 20px;
  display: flex;
  gap: 6px;
  z-index: 3;
}

.hero-dots span {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.74);
}

.hero-dots span.active {
  width: 22px;
  background: #2563eb;
}

.lp-hero .hero-preview {
  right: 64px;
  bottom: 24px;
  gap: 8px;
}

.lp-hero .hero-preview article {
  width: 96px;
  height: 68px;
  border-radius: 9px;
}

.lp-hero .hero-preview img {
  height: 50px;
}

.lp-hero .hero-preview strong {
  font-size: 9px;
  padding: 2px 6px;
}

.lp-hero .lp-services {
  width: 100%;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.lp-hero .lp-services article {
  min-height: 58px;
  border-radius: 10px;
  padding: 10px 12px;
  gap: 10px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
}

.lp-hero .lp-services svg {
  width: 24px;
  height: 24px;
}

.lp-hero .lp-services strong {
  font-size: 11px;
}

.lp-hero .lp-services span {
  font-size: 10px;
}

.lp-catalog .product-card .product-price {
  color: var(--lp-price);
}

.laptop-page .flag-card strong,
.laptop-page .product-card strong,
.laptop-page .product-card .product-price,
.laptop-page .hero-preview strong {
  color: var(--lp-price);
}

.laptop-page .lp-catalog .product-card strong.product-price,
.laptop-page .lp-catalog .product-grid .product-card .product-price {
  color: var(--lp-price) !important;
}

.laptop-page .product-price {
  color: var(--lp-price) !important;
}

.laptop-page .flag-card img,
.laptop-page .product-card img,
.laptop-page .lp-catalog .product-card img,
.laptop-page .lp-accessories .product-card img,
.laptop-page .lp-flagship .flag-card img {
  width: 100%;
  object-fit: contain !important;
  object-position: center;
  background: linear-gradient(145deg, #f8fbff, #eaf1fb);
  padding: 6px;
}

.laptop-page .lp-flagship .flag-card img {
  height: 174px;
  aspect-ratio: 16 / 10;
}

.laptop-page .lp-catalog .product-card img {
  height: 184px;
  aspect-ratio: 16 / 10;
}

.laptop-page .lp-accessories .product-card img {
  height: 142px;
  aspect-ratio: 16 / 10;
}

.laptop-page .lp-catalog .product-card img,
.laptop-page .lp-flagship .flag-card img,
.laptop-page .lp-accessories .product-card img {
  display: block;
  object-fit: cover !important;
  object-position: center;
  padding: 0;
  border: 0;
  background: #eef4fb;
}

.laptop-page .lp-catalog .product-card img {
  height: 190px;
  border-radius: 14px;
}

.laptop-page .lp-flagship .flag-card img {
  height: 188px;
  border-radius: 14px;
}

.laptop-page .lp-accessories .product-card img {
  height: 148px;
  border-radius: 12px;
}



.laptop-page {
  -webkit-font-smoothing: antialiased;
  text-rendering: geometricPrecision;
}

.laptop-page .product-card,
.laptop-page .flag-card {
  backface-visibility: hidden;
  transform: translateZ(0);
  will-change: transform;
}

.laptop-page .lp-catalog .product-card:hover,
.laptop-page .flag-card:hover,
.laptop-page .product-card:hover {
  transition-duration: 160ms;
}

.laptop-page .cart-btn,
.laptop-page .product-actions .cart-btn,
.laptop-page .lp-catalog .product-actions .cart-btn {
  background: var(--lp-price);
  color: #ffffff;
  box-shadow: 0 14px 26px rgba(37, 99, 235, 0.28);
}

.laptop-page .cart-btn:hover,
.laptop-page .product-actions .cart-btn:hover,
.laptop-page .lp-catalog .product-actions .cart-btn:hover {
  background: #1d4ed8;
  color: #ffffff;
  box-shadow: 0 18px 30px rgba(37, 99, 235, 0.34);
}

.lp-hero > .lp-sidebar {
  align-self: stretch;
  height: 482px;
  min-height: 482px;
}

.lp-hero-main {
  height: 482px;
}

.lp-hero-main .lp-hero-panel {
  flex: 0 0 410px;
}

.lp-hero-main .lp-services {
  flex: 0 0 58px;
}

.lp-hero-main .lp-services article {
  height: 58px;
}

.lp-flagship .section-copy {
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 28px;
}

.lp-flagship .section-copy h2 {
  font-size: clamp(24px, 2.2vw, 34px);
  line-height: 1.12;
  white-space: nowrap;
  margin-bottom: 8px;
}

.lp-flagship .section-copy p {
  font-size: clamp(13px, 1.25vw, 18px);
  line-height: 1.45;
  margin: 0;
}

.lp-flagship .section-copy > span {
  width: 14px;
  height: 14px;
  margin-top: 8px;
}

@media (max-width: 1100px) {
  .lp-hero,
  .catalog-layout {
    grid-template-columns: 1fr;
  }

  .lp-sidebar,
  .filter-card {
    position: static;
  }

  .lp-services,
  .flagship-row,
  .brand-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .lp-showroom {
    grid-template-columns: 1fr;
  }

  .lp-catalog .product-grid,
  .lp-catalog .skeleton-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .lp-featured-head,
  .catalog-title {
    grid-template-columns: 1fr;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }

  .link-btn {
    justify-self: start;
  }
}

@media (max-width: 640px) {
  .hero-copy {
    width: 100%;
    padding: 56px 24px;
  }

  .hero-preview {
    display: none;
  }

  .lp-services,
  .flagship-row,
  .brand-grid {
    grid-template-columns: 1fr;
  }

  .lp-catalog .product-grid,
  .lp-catalog .skeleton-grid {
    grid-template-columns: 1fr;
  }

  .lp-flagship,
  .lp-accessories,
  .lp-showroom,
  .lp-catalog {
    padding: 24px;
  }

  .lp-showroom-copy h2 {
    font-size: 27px;
  }

  .lp-showroom-visual,
  .lp-showroom-visual img {
    min-height: 230px;
  }
}

@media (min-width: 1101px) {
  .lp-catalog .product-grid,
  .lp-catalog .skeleton-grid {
    max-height: 640px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 8px;
    scroll-behavior: smooth;
    overscroll-behavior: contain;
    scrollbar-width: thin;
  }

  .lp-catalog .product-grid::-webkit-scrollbar,
  .lp-catalog .skeleton-grid::-webkit-scrollbar {
    width: 6px;
  }

  .lp-catalog .product-grid::-webkit-scrollbar-thumb,
  .lp-catalog .skeleton-grid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 99px;
  }

  .lp-catalog .product-grid::-webkit-scrollbar-track,
  .lp-catalog .skeleton-grid::-webkit-scrollbar-track {
    background: transparent;
  }
}

/* ============================================================
   COMBOS SECTION (for Accessories page)
   ============================================================ */
.lp-combos {
  margin-top: 56px;
  padding: 40px;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
  border: 1px solid #e2e8f0;
}

.combos-header {
  text-align: center;
  margin-bottom: 36px;
}

.combos-header h2 {
  font-size: clamp(24px, 3vw, 32px);
  font-weight: 800;
  color: #0f172a;
  margin: 12px 0 8px;
  text-transform: uppercase;
}

.combos-header .section-sub {
  font-size: 14.5px;
  color: #64748b;
  max-width: 600px;
  margin: 0 auto;
}

.combos-header .ambient-label {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
  font-size: 12px;
  font-weight: 700;
}

.combos-bento-layout {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.combo-bento-card {
  background: #f8fafc;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 24px;
  transition: all 0.25s ease;
}

.combo-bento-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
}

.combo-main-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
  align-items: center;
}

@media (max-width: 900px) {
  .combo-main-content {
    grid-template-columns: 1fr;
    gap: 24px;
  }
}

.combo-details {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.combo-discount-badge {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  margin-bottom: 12px;
}

.combo-details h3 {
  font-size: 20px;
  font-weight: 800;
  margin: 0 0 8px 0;
  color: #0f172a;
}

.combo-details p {
  font-size: 13.5px;
  color: #64748b;
  margin-bottom: 16px;
  line-height: 1.5;
}

.combo-pricing-group {
  display: flex;
  gap: 24px;
  margin-bottom: 20px;
  border-top: 1px solid #e2e8f0;
  padding-top: 16px;
  width: 100%;
}

.price-block {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.price-label {
  font-size: 10px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
}

.price-val {
  font-size: 20px;
  font-weight: 800;
  color: #2563eb;
}

.price-val-old {
  font-size: 17px;
  font-weight: 650;
  color: #94a3b8;
  text-decoration: line-through;
}

.combo-action-btn {
  padding: 10px 20px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: opacity 0.2s;
}

.combo-action-btn:hover {
  opacity: 0.9;
}

.btn-chevron {
  width: 14px;
  height: 14px;
}

.combo-visual-connector {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 20px;
  border-radius: 14px;
}

.connector-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex: 1;
}

.node-image-box {
  width: 80px;
  height: 80px;
  border-radius: 10px;
  background: #f8fafc;
  padding: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.node-image-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.node-title {
  font-size: 11px;
  font-weight: 600;
  color: #475569;
  margin-top: 8px;
  text-align: center;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  height: 32px;
  line-height: 1.4;
}

.node-plus-sign {
  position: absolute;
  right: -18px;
  top: 24px;
  font-size: 18px;
  font-weight: 800;
  color: #94a3b8;
}

.combo-empty-state {
  text-align: center;
  padding: 30px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px dashed #cbd5e1;
}

.combo-empty-icon {
  font-size: 36px;
  margin-bottom: 12px;
}
</style>
