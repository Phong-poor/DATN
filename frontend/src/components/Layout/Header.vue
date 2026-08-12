<script setup>
import { ref, onMounted, onUnmounted, computed, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Apple,
  BadgeCheck,
  Briefcase,
  Code2,
  Cpu,
  Gamepad2,
  Gift,
  GraduationCap,
  Headphones,
  Laptop,
  Medal,
  Monitor,
  Mouse,
  Package,
  Sparkles,
  Star,
  Tags,
} from 'lucide-vue-next'
import api from '../../services/api'
import { getUser, clearAuth, getToken } from '@/services/auth'
import { productImageUrl, storageUrl, withImageVersion } from '@/services/urls'
import { prefetchProductsPage, getPrefetchedProductsData } from '@/services/productsPrefetch'

const router = useRouter()
const route = useRoute()

const getSwal = async () => {
  const module = await import('@/services/swal')
  return module.default
}

const showWishlist = ref(false)
const showUser = ref(false)
const isMobileMenuOpen = ref(false)

// ===================== SCROLL HIDE/SHOW HEADER =====================
const isHeaderHidden = ref(false)
const isScrolled = ref(false)
let lastScrollY = 0

const handleScroll = () => {
  const currentScrollY = window.scrollY || window.pageYOffset || 0
  isScrolled.value = currentScrollY > 20

  if (currentScrollY <= 100) {
    isHeaderHidden.value = false
    lastScrollY = currentScrollY
    return
  }

  if (currentScrollY > lastScrollY && currentScrollY > 100) {
    if (!isHeaderHidden.value) {
      isHeaderHidden.value = true
      activeMegaMenu.value = null
      showWishlist.value = false
      showUser.value = false
      showSearchSuggestions.value = false
    }
  } else if (currentScrollY < lastScrollY) {
    isHeaderHidden.value = false
  }

  lastScrollY = currentScrollY
}

// ===================== ANNOUNCEMENT BAR =====================
const announcementIcons = {
  delivery: '<svg class="ann-code-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><path d="M5 16a2 2 0 1 0 4 0"/><path d="M16 16a2 2 0 1 0 4 0"/></svg>',
  payment: '<svg class="ann-code-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/></svg>',
  sale: '<svg class="ann-code-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12l-8 8-8-8V4h8z"/><path d="M9 9h.01"/></svg>',
  warranty: '<svg class="ann-code-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-5"/></svg>',
}

const announcements = [
  { icon: '🚚', text: 'Giao nhanh · Trong <strong>2 giờ</strong>' },
  { icon: '💳', text: 'Trả góp <strong>0%</strong> · Duyệt 5 phút' },
  { icon: '🎁', text: 'RTX 5090 · Giảm <strong>20 triệu</strong>' },
  { icon: '🛡️', text: 'Bảo hành toàn quốc · <strong>Đổi trả 7 ngày</strong>' },
]
announcements[0].icon = announcementIcons.delivery
announcements[1].icon = announcementIcons.payment
announcements[2].icon = announcementIcons.sale
announcements[3].icon = announcementIcons.warranty
const annIdx = ref(0)
let annTimer = null

// ===================== MEGA MENU =====================
const activeMegaMenu = ref(null)
let megaLeaveTimer = null

const megaMenuData = reactive({
  laptop: {
    label: 'Laptop',
    accent: '#2563eb',
    accentBg: 'rgba(37,99,235,0.08)',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01M8 10v4M6 12h4m6-2v4m0-2h2"/></svg>`,
    img: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=400&q=80',
    sections: [
      {
        title: 'Dòng laptop', icon: '🎮',
        items: [
          { label: 'Laptop Gaming RTX', badge: 'HOT', line: 'gaming' },
          { label: 'MacBook Pro & Air', badge: 'NEW', line: 'macbook' },
          { label: 'Laptop văn phòng', badge: '', line: 'office' },
          { label: 'Laptop học tập', badge: '', line: 'student' },
        ]
      },
      {
        title: 'Thương hiệu', icon: '🏷️',
        items: [
          { label: 'ASUS ROG', badge: '', brand: 'Asus', q: 'ROG' },
          { label: 'Apple MacBook', badge: 'PRO', brand: 'Apple' },
          { label: 'Lenovo Legion', badge: 'HOT', brand: 'Lenovo', q: 'Legion' },
          { label: 'Dell Gaming', badge: '', brand: 'Dell', q: 'Gaming' },
        ]
      },
      {
        title: 'Nhu cầu', icon: '💰',
        items: [
          { label: 'Workstation đồ họa', badge: 'PRO', q: 'workstation' },
          { label: 'Laptop AI PC', badge: 'NEW', q: 'AI' },
          { label: 'Phụ kiện laptop', badge: '', line: 'accessory' },
          { label: 'Flagship Premium', badge: 'PRO', q: 'premium' },
        ]
      },
    ],
    featured: { name: 'Laptop Gaming + MacBook Premium', price: 'Từ 19.990.000đ', oldPrice: '', tag: 'NEW', img: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=200&q=80' },
    quickLinks: ['Laptop Gaming', 'MacBook Pro', 'Laptop văn phòng'],
  },
  aipc: {
    label: 'AI PC',
    accent: '#6366f1',
    accentBg: 'rgba(99,102,241,0.07)',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>`,
    img: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=400&q=80',
    sections: [
      {
        title: 'Dòng chip', icon: '⚡',
        items: [
          { label: 'Intel Core Ultra 9', badge: 'NEW', q: 'Intel Core Ultra 9' },
          { label: 'AMD Ryzen AI 9', badge: 'NEW', q: 'AMD Ryzen AI 9' },
          { label: 'Snapdragon X Elite', badge: '', q: 'Snapdragon X Elite' },
          { label: 'Intel Core Ultra 5', badge: '', q: 'Intel Core Ultra 5' },
        ]
      },
      {
        title: 'Thương hiệu', icon: '🏷️',
        items: [
          { label: 'Dell XPS', badge: 'HOT', q: 'Dell XPS' },
          { label: 'HP Spectre', badge: '', q: 'HP Spectre' },
          { label: 'Samsung Galaxy Book', badge: '', q: 'Samsung Galaxy Book' },
          { label: 'Microsoft Surface', badge: '', q: 'Microsoft Surface' },
        ]
      },
      {
        title: 'Tính năng AI', icon: '🤖',
        items: [
          { label: 'NPU tích hợp 45 TOPs', badge: '', q: 'NPU laptop' },
          { label: 'Copilot+ Certified', badge: '', q: 'Copilot plus' },
          { label: 'On-device AI LLM', badge: 'PRO', q: 'on device AI' },
          { label: 'AI Creator Suite', badge: '', q: 'AI creator' },
        ]
      },
    ],
    featured: null,
    quickLinks: ['AI PC Copilot+', 'Intel Core Ultra so sánh', 'AI PC cho lập trình'],
  },
  macbook: {
    label: 'MacBook',
    accent: '#0ea5e9',
    accentBg: 'rgba(14,165,233,0.07)',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 20h8M12 18v2"/></svg>`,
    img: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400&q=80',
    sections: [
      {
        title: 'Dòng sản phẩm', icon: '🍎',
        items: [
          { label: 'MacBook Air M4', badge: 'NEW', q: 'MacBook Air M4' },
          { label: 'MacBook Pro M4', badge: 'HOT', q: 'MacBook Pro M4' },
          { label: 'MacBook Pro M4 Pro', badge: '', q: 'MacBook Pro M4 Pro' },
          { label: 'MacBook Pro M4 Max', badge: 'PRO', q: 'MacBook Pro M4 Max' },
        ]
      },
      {
        title: 'Kích thước', icon: '📐',
        items: [
          { label: '13 inch – Air', badge: '', q: 'MacBook 13 inch' },
          { label: '14 inch – Pro', badge: 'HOT', q: 'MacBook 14 inch' },
          { label: '15 inch – Air', badge: '', q: 'MacBook 15 inch' },
          { label: '16 inch – Pro Max', badge: '', q: 'MacBook 16 inch' },
        ]
      },
      {
        title: 'Cấu hình RAM', icon: '💾',
        items: [
          { label: '8GB / 256GB', badge: '', q: 'MacBook 8GB' },
          { label: '16GB / 512GB', badge: 'HOT', q: 'MacBook 16GB' },
          { label: '24GB / 1TB', badge: '', q: 'MacBook 24GB' },
          { label: '48GB / 2TB', badge: 'PRO', q: 'MacBook 48GB' },
        ]
      },
    ],
    featured: null,
    quickLinks: ['MacBook Air M4 giá rẻ', 'So sánh MacBook Pro', 'MacBook cho sinh viên'],
  },
  'phu-kien': {
    label: 'Phụ kiện',
    accent: '#0ea5e9',
    accentBg: 'rgba(14,165,233,0.07)',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>`,
    img: 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=400&q=80',
    sections: [
      {
        title: 'Loại phụ kiện', icon: '🖱️',
        items: [
          { label: 'Chuột', badge: 'HOT', line: 'mouse' },
          { label: 'Bàn phím', badge: 'NEW', line: 'keyboard' },
          { label: 'Tai nghe', badge: '', line: 'headphone' },
          { label: 'Lót chuột', badge: '', line: 'pad' },
        ]
      },
      {
        title: 'Thương hiệu', icon: '🏷️',
        items: [
          { label: 'Logitech', badge: '', brand: 'Logitech' },
          { label: 'Razer', badge: 'PRO', brand: 'Razer' },
          { label: 'Akko / DareU', badge: '', brand: 'Akko' },
          { label: 'Corsair / HyperX', badge: '', brand: 'HyperX' },
        ]
      },
    ],
    featured: null,
    quickLinks: ['Bàn phím cơ giá rẻ', 'Chuột gaming Logitech', 'Phụ kiện Ugreen'],
  },

  sale: {
    label: 'Khuyến mãi',
    accent: '#f59e0b',
    accentBg: 'rgba(245,158,11,0.07)',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>`,
    img: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=400&q=80',
    sections: [
      {
        title: '🔥 Flash Sale', icon: '🔥',
        items: [
          { label: 'Giảm đến 30% hôm nay', badge: 'HOT', q: 'sale 30%' },
          { label: 'Clearance Stock', badge: '', q: 'clearance laptop' },
          { label: 'Thanh lý kho cuối năm', badge: '', q: 'thanh ly laptop' },
          { label: 'Open-box giá sốc', badge: 'NEW', q: 'open box laptop' },
        ]
      },
      {
        title: 'Trả góp 0%', icon: '💳',
        items: [
          { label: '6 tháng không lãi', badge: '', q: 'tra gop 6 thang' },
          { label: '12 tháng 0% lãi', badge: 'HOT', q: 'tra gop 12 thang' },
          { label: '24 tháng ưu đãi', badge: '', q: 'tra gop 24 thang' },
          { label: '36 tháng duyệt nhanh', badge: '', q: 'tra gop 36 thang' },
        ]
      },
      {
        title: 'Combo ưu đãi', icon: '🎁',
        items: [
          { label: 'Laptop + Chuột Gaming', badge: '', q: 'combo laptop chuot' },
          { label: 'Laptop + Balo + Hub', badge: 'HOT', q: 'combo laptop balo' },
          { label: 'Laptop + Màn hình 4K', badge: '', q: 'combo laptop man hinh' },
          { label: 'Laptop + Bàn phím cơ', badge: '', q: 'combo laptop ban phim' },
        ]
      },
    ],
    featured: null,
    quickLinks: ['Laptop dưới 15 triệu', 'Sinh viên ưu đãi', 'Flash Sale hôm nay'],
  },
})

const visibleMegaMenuData = computed(() => ({
  laptop: megaMenuData.laptop,
  'phu-kien': megaMenuData['phu-kien'],
  sale: megaMenuData.sale,
}))

const normalizeIconText = (value) => String(value || '')
  .toLocaleLowerCase('vi-VN')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')

const megaSectionIcon = (title) => {
  const text = normalizeIconText(title)
  if (text.includes('thuong') || text.includes('hi')) return Tags
  if (text.includes('nhu') || text.includes('cau')) return Cpu
  if (text.includes('noi') || text.includes('bat')) return Star
  if (text.includes('combo') || text.includes('sale') || text.includes('uu')) return Gift
  if (text.includes('phu') || text.includes('loai')) return Mouse
  return Laptop
}

const megaItemIcon = (label, sectionTitle = '') => {
  const text = `${normalizeIconText(label)} ${normalizeIconText(sectionTitle)}`
  if (text.includes('gaming') || text.includes('rtx') || text.includes('esports')) return Gamepad2
  if (text.includes('macbook') || text.includes('apple')) return Apple
  if (text.includes('van phong') || text.includes('doanh nghiep') || text.includes('workstation')) return Briefcase
  if (text.includes('hoc') || text.includes('sinh vien')) return GraduationCap
  if (text.includes('ai') || text.includes('ultra') || text.includes('npu') || text.includes('copilot')) return Cpu
  if (text.includes('lap trinh') || text.includes('code')) return Code2
  if (text.includes('tai nghe')) return Headphones
  if (text.includes('chuot') || text.includes('ban phim') || text.includes('logitech') || text.includes('razer')) return Mouse
  if (text.includes('sale') || text.includes('combo') || text.includes('flash') || text.includes('giam')) return Gift
  if (text.includes('asus') || text.includes('rog')) return Medal
  if (text.includes('lenovo') || text.includes('dell') || text.includes('hp') || text.includes('msi') || text.includes('acer') || text.includes('gigabyte')) return BadgeCheck
  if (text.includes('man hinh')) return Monitor
  if (text.includes('balo') || text.includes('hub')) return Package
  if (text.includes('mong') || text.includes('premium') || text.includes('flagship')) return Sparkles
  return Laptop
}

const megaBrandLogo = (label = '') => {
  const text = normalizeIconText(label)
  if (text.includes('asus') || text.includes('rog')) return '/ASUS_Logo.svg.png'
  if (text.includes('apple') || text.includes('macbook')) return '/Apple_logo_black.svg.png'
  if (text.includes('lenovo') || text.includes('legion')) return '/Lenovo_logo_2015.svg.png'
  if (text.includes('dell')) return '/Dell_Logo.svg.png'
  if (text.includes('msi')) return '/brands/msi.svg'
  if (text.includes('acer') || text.includes('predator')) return '/brands/acer.svg'
  if (text.includes('hp')) return '/brands/hp.svg'
  if (text.includes('gigabyte')) return '/brands/gigabyte.svg'
  if (text.includes('logitech')) return '/brands/logitech.svg'
  if (text.includes('razer')) return '/brands/razer.svg'
  if (text.includes('akko') || text.includes('dareu')) return '/brands/akko.png'
  if (text.includes('corsair') || text.includes('hyperx')) return '/brands/corsair.svg'
  return ''
}

const openMega = (key) => {
  clearTimeout(megaLeaveTimer)
  activeMegaMenu.value = key
}
const toggleMega = (key) => {
  clearTimeout(megaLeaveTimer)
  activeMegaMenu.value = activeMegaMenu.value === key ? null : key
}
const closeMega = () => {
  megaLeaveTimer = setTimeout(() => { activeMegaMenu.value = null }, 250)
}
const keepMega = () => { clearTimeout(megaLeaveTimer) }

const menuCategoryMap = {
  laptop: 'Laptop',
  gaming: 'Laptop Gaming',
  macbook: 'MacBook',
  'phu-kien': 'Phụ kiện',
}

const navToCategory = (key) => {
  activeMegaMenu.value = null
  if (key === 'sale') {
    router.push('/khuyen-mai')
  } else if (key === 'laptop') {
    router.push('/laptop')
  } else if (key === 'phu-kien') {
    router.push('/phu-kien')
  } else if (key === 'gaming') {
    router.push({ path: '/laptop', query: { line: 'gaming' } })
  } else if (key === 'macbook') {
    router.push('/macbook')
  } else {
    router.push({ path: '/laptop', query: { category: menuCategoryMap[key] || key } })
  }
}

const navToFeaturedItem = async (key, featured) => {
  activeMegaMenu.value = null
  const name = featured?.name
  if (!name) return

  if (featured.productId) {
    router.push({
      path: `/san-pham/${featured.productId}`,
      query: featured.variantId ? { variant: featured.variantId } : {}
    })
    return
  }

  if (key === 'laptop') {
    router.push('/laptop')
    return
  }

  try {
    const res = await api.get('/sanpham/search', {
      params: { q: name },
      skipGlobalLoader: true
    })
    const items = Array.isArray(res.data) ? res.data : (res.data?.data || [])
    if (items.length > 0) {
      const product = items[0]
      const variants = productVariants(product)
      const variant = variants.length ? variants[0] : null
      router.push({
        path: `/san-pham/${product.id_sanpham}`,
        query: variant?.id_bienthe ? { variant: variant.id_bienthe } : {}
      })
      return
    }
  } catch (err) {
    console.error('Lỗi khi định tuyến sản phẩm nổi bật:', err)
  }

  navToMegaItem(key, name)
}

const isAccessory = (p) => {
  const cat = String(p.category || p.ten_danhmuc || p.danh_muc?.ten_danhmuc || '').toLowerCase()
  const name = String(p.tenSP || '').toLowerCase()
  const accessoryCats = ['chuột', 'bàn phím', 'tai nghe', 'lót chuột', 'ổ cứng ssd', 'ram', 'màn hình', 'hub chuyển đổi', 'webcam', 'balo laptop', 'router', 'microphone', 'phụ kiện', 'accessory']
  if (accessoryCats.some(c => cat.includes(c))) return true
  if (cat === 'laptop' && (name.includes('chuột') || name.includes('bàn phím') || name.includes('tai nghe') || name.includes('lót chuột') || name.includes('mouse') || name.includes('keyboard') || name.includes('headphone'))) {
    return true
  }
  return false
}

const productVariants = (product) => {
  if (Array.isArray(product?.bien_thes)) return product.bien_thes
  if (Array.isArray(product?.bienThes)) return product.bienThes
  if (Array.isArray(product?.bienthes)) return product.bienthes
  return []
}

const variantImage = (product, variant) => {
  return productImageUrl(product || {}, variant || null, 'https://placehold.co/150')
}

const resolveProductPrice = (product) => {
  const variants = productVariants(product)
  if (variants.length > 0) {
    const sorted = variants.slice().sort((a, b) => Number(a.gia || 0) - Number(b.gia || 0))
    return Number(sorted[0].gia || 0)
  }
  return Number(product.gia || 0)
}

const featuredIdentity = (product, variant) => ({
  productId: product?.id_sanpham || product?.id || null,
  variantId: variant?.id_bienthe || null,
})

const updateFeaturedProducts = (productsList) => {
  if (!Array.isArray(productsList) || !productsList.length) return
  const safeProducts = productsList.filter((product) => product && typeof product === 'object')
  if (!safeProducts.length) return
  const formatVnd = (num) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(num || 0))

  // Laptop nổi bật: giữ ID thật để card mở thẳng trang chi tiết.
  const laptops = safeProducts.filter(p => !isAccessory(p)).sort((a, b) => {
    const aName = String(a.tenSP || '').toLowerCase()
    const bName = String(b.tenSP || '').toLowerCase()
    const aPriority = /gaming|rtx|rog|macbook/.test(aName) ? 1 : 0
    const bPriority = /gaming|rtx|rog|macbook/.test(bName) ? 1 : 0
    return (bPriority - aPriority) || (resolveProductPrice(b) - resolveProductPrice(a))
  })
  if (laptops.length > 0) {
    const p = laptops[0]
    const variants = productVariants(p)
    megaMenuData.laptop.featured = {
      ...featuredIdentity(p, variants[0]),
      name: p.tenSP,
      price: formatVnd(resolveProductPrice(p)),
      oldPrice: p.gia_truockhuyenmai ? formatVnd(p.gia_truockhuyenmai) : '',
      tag: 'NỔI BẬT',
      img: variantImage(p, variants[0])
    }
  }

  // 1. MacBook
  const macbooks = safeProducts.filter(p => {
    const text = String(p.tenSP || '').toLowerCase()
    return text.includes('macbook')
  }).sort((a, b) => resolveProductPrice(b) - resolveProductPrice(a))
  if (macbooks.length > 0) {
    const p = macbooks[0]
    const variants = productVariants(p)
    megaMenuData.macbook.featured = {
      ...featuredIdentity(p, variants[0]),
      name: p.tenSP,
      price: formatVnd(resolveProductPrice(p)),
      oldPrice: p.gia_truockhuyenmai ? formatVnd(p.gia_truockhuyenmai) : '',
      tag: 'MACBOOK',
      img: variantImage(p, variants[0])
    }
  }

  // 2. Workstation
  const workstations = safeProducts.filter(p => {
    const nameText = String(p.tenSP || '').toLowerCase()
    const catText = String(p.category || p.danh_muc?.ten_danhmuc || '').toLowerCase()
    return nameText.includes('workstation') || catText.includes('workstation') || nameText.includes('precision') || nameText.includes('zbook')
  }).sort((a, b) => resolveProductPrice(b) - resolveProductPrice(a))
  if (megaMenuData.workstation && workstations.length > 0) {
    const p = workstations[0]
    const variants = productVariants(p)
    megaMenuData.workstation.featured = {
      ...featuredIdentity(p, variants[0]),
      name: p.tenSP,
      price: formatVnd(resolveProductPrice(p)),
      oldPrice: '',
      tag: 'WORKSTATION',
      img: variantImage(p, variants[0])
    }
  }

  // 3. Phụ kiện
  const accessories = safeProducts.filter(isAccessory).sort((a, b) => resolveProductPrice(b) - resolveProductPrice(a))
  if (accessories.length > 0) {
    const p = accessories[0]
    const variants = productVariants(p)
    megaMenuData['phu-kien'].featured = {
      ...featuredIdentity(p, variants[0]),
      name: p.tenSP,
      price: formatVnd(resolveProductPrice(p)),
      oldPrice: '',
      tag: 'BESTSELLER',
      img: variantImage(p, variants[0])
    }
  }

  // 4. AI PC
  const aipcs = safeProducts.filter(p => {
    const text = String(p.tenSP || '').toLowerCase()
    return text.includes('ultra') || text.includes('ai') || text.includes('npu')
  }).sort((a, b) => resolveProductPrice(b) - resolveProductPrice(a))
  if (aipcs.length > 0) {
    const p = aipcs[0]
    const variants = productVariants(p)
    megaMenuData.aipc.featured = {
      ...featuredIdentity(p, variants[0]),
      name: p.tenSP,
      price: formatVnd(resolveProductPrice(p)),
      oldPrice: '',
      tag: 'AIPC',
      img: variantImage(p, variants[0])
    }
  }

  // 5. Sale
  const saleItems = safeProducts.filter(p => p.gia_truockhuyenmai && resolveProductPrice(p) < p.gia_truockhuyenmai).sort((a, b) => (b.gia_truockhuyenmai - resolveProductPrice(b)) - (a.gia_truockhuyenmai - resolveProductPrice(a)))
  if (saleItems.length > 0) {
    const p = saleItems[0]
    const variants = productVariants(p)
    megaMenuData.sale.featured = {
      ...featuredIdentity(p, variants[0]),
      name: p.tenSP,
      price: formatVnd(resolveProductPrice(p)),
      oldPrice: formatVnd(p.gia_truockhuyenmai),
      tag: 'SALE HOT',
      img: variantImage(p, variants[0])
    }
  }

  // 6. Gaming
  const gamingLaptops = safeProducts.filter(p => {
    const text = String(p.tenSP || '').toLowerCase()
    return text.includes('gaming') || text.includes('rtx') || text.includes('rog')
  }).sort((a, b) => resolveProductPrice(b) - resolveProductPrice(a))
  if (megaMenuData.gaming && gamingLaptops.length > 0) {
    const p = gamingLaptops[0]
    const variants = productVariants(p)
    megaMenuData.gaming.featured = {
      ...featuredIdentity(p, variants[0]),
      name: p.tenSP,
      price: formatVnd(resolveProductPrice(p)),
      oldPrice: '',
      tag: 'GAMING',
      img: variantImage(p, variants[0])
    }
  }
}

try {
  const warm = getPrefetchedProductsData()
  if (warm && Array.isArray(warm.productsRaw)) {
    updateFeaturedProducts(warm.productsRaw)
  }
} catch (error) {
  console.error('Khong the nap cache san pham cho header:', error)
  try {
    localStorage.removeItem('nextgen_products_prefetch_cache')
    localStorage.removeItem('premium_home_cache')
  } catch {
    // Ignore cache cleanup failures.
  }
}

const navToMegaItem = (key, item) => {
  activeMegaMenu.value = null

  // Backward compatibility check
  const it = typeof item === 'string' ? { q: item } : item

  const query = {}
  if (it.line) query.line = it.line
  if (it.brand) query.brand = it.brand
  if (it.q) query.q = it.q

  if (key === 'sale') {
    const qValue = it.q || ''
    const normalizedKeyword = normalizeIconText(qValue)
    const section = normalizedKeyword.includes('combo')
      ? 'combo-offers'
      : normalizedKeyword.includes('tra gop')
        ? 'voucher-center'
        : normalizedKeyword.includes('flash') || normalizedKeyword.includes('30%')
          ? 'flash-sale'
          : 'discount-grid'
    router.push({
      path: '/khuyen-mai',
      query: { ...(qValue ? { q: qValue } : {}), section }
    })
    return
  }
  if (key === 'laptop') {
    router.push({ path: '/laptop', query })
    return
  }
  if (key === 'phu-kien') {
    router.push({ path: '/phu-kien', query })
    return
  }
  if (key === 'gaming') {
    router.push({ path: '/laptop', query: { line: 'gaming', ...query } })
    return
  }
  if (key === 'macbook') {
    router.push({ path: '/laptop', query: { line: 'macbook', ...query } })
    return
  }
  router.push({ path: '/laptop', query: { category: menuCategoryMap[key] || key, ...query } })
}

const mobileMenuTarget = (key) => {
  if (key === 'sale') return '/khuyen-mai'
  if (key === 'laptop') return '/laptop'
  if (key === 'phu-kien') return '/phu-kien'
  if (key === 'gaming') return { path: '/laptop', query: { line: 'gaming' } }
  if (key === 'macbook') return '/macbook'
  if (key === 'workstation') return { path: '/laptop', query: { category: 'workstation' } }
  return { path: '/laptop', query: { category: menuCategoryMap[key] || key } }
}

const isMenuCurrent = (key) => {
  if (key === 'sale') return route.path === '/khuyen-mai'
  if (key === 'laptop') return ['/laptop', '/labtop', '/gaming', '/macbook'].includes(route.path)
  if (key === 'phu-kien') return route.path === '/phu-kien'
  if (key === 'workstation') return route.path === '/laptop' && String(route.query.category || '').toLowerCase() === 'workstation'

  if (key === 'gaming') return route.path === '/gaming' || (route.path === '/laptop' && String(route.query.line || '').toLowerCase() === 'gaming')
  if (key === 'macbook') return route.path === '/macbook' ||
    (route.path === '/laptop' && String(route.query.category || '').toLowerCase() === 'macbook')
  if (!['gaming', 'macbook'].includes(key)) return false
  const currentCategory = String(route.query.category || '').toLowerCase()
  return route.path === '/laptop' && currentCategory === String(menuCategoryMap[key] || key).toLowerCase()
}

// ===================== TÌM KIẾM =====================
const searchQuery = ref('')
const searchFocused = ref(false)
const searchSuggestions = ref([])
const isSearchingSuggestions = ref(false)
const showSearchSuggestions = ref(false)
let debounceTimeout = null

const fetchSearchSuggestions = async () => {
  const keyword = searchQuery.value.trim()
  if (!keyword) {
    searchSuggestions.value = []
    showSearchSuggestions.value = false
    return
  }
  isSearchingSuggestions.value = true
  showSearchSuggestions.value = true
  try {
    const res = await api.get('/sanpham/search', {
      params: { q: keyword },
      skipGlobalLoader: true
    })
    const items = Array.isArray(res.data) ? res.data : (res.data?.data || [])
    searchSuggestions.value = items.slice(0, 3).map(p => {
      const variants = productVariants(p)
      const variant = variants.length
        ? variants.slice().sort((a, b) => Number(b.gia || 0) - Number(a.gia || 0))[0]
        : null
      const price = Number(variant?.gia || p.gia || 0)

      const image = variantImage(p, variant)
      return {
        id_sanpham: p.id_sanpham,
        id_bienthe: variant?.id_bienthe || null,
        tenSP: p.tenSP,
        gia: price,
        image
      }
    })
  } catch (err) {
    console.error('Lỗi tìm kiếm gợi ý:', err)
    searchSuggestions.value = []
  } finally {
    isSearchingSuggestions.value = false
  }
}

const onSearchInput = () => {
  clearTimeout(debounceTimeout)
  if (!searchQuery.value.trim()) {
    searchSuggestions.value = []
    showSearchSuggestions.value = false
    return
  }
  showSearchSuggestions.value = true
  debounceTimeout = setTimeout(() => {
    fetchSearchSuggestions()
  }, 300)
}

const onSearchFocus = () => {
  searchFocused.value = true
  if (searchQuery.value.trim()) {
    showSearchSuggestions.value = true
    if (searchSuggestions.value.length === 0) {
      fetchSearchSuggestions()
    }
  }
}

const onSearchBlur = () => {
  searchFocused.value = false
  setTimeout(() => {
    showSearchSuggestions.value = false
  }, 200)
}

const handleSearch = () => {
  const keyword = searchQuery.value.trim()
  if (!keyword) return
  router.push({ path: '/laptop', query: { q: keyword, scroll: 'catalog' } })
  searchQuery.value = ''
  isMobileMenuOpen.value = false
  searchFocused.value = false
  showSearchSuggestions.value = false
}

const goToProductDetail = (product) => {
  router.push({
    path: `/san-pham/${product.id_sanpham}`,
    query: product.id_bienthe ? { variant: product.id_bienthe } : {}
  })
  showSearchSuggestions.value = false
  searchFocused.value = false
  searchQuery.value = ''
}

const goToMoreResults = () => {
  const keyword = searchQuery.value.trim()
  if (!keyword) return
  router.push({ path: '/laptop', query: { q: keyword, scroll: 'catalog' } })
  showSearchSuggestions.value = false
  searchFocused.value = false
  searchQuery.value = ''
}

// ===================== GIỎ HÀNG BADGE =====================
const cartCount = ref(0)
const cartItems = ref([])
const cartTotal = ref(0)
const showCartDropdown = ref(false)

const fetchCart = async () => {
  try {
    const token = getToken()
    if (!token) { cartCount.value = 0; cartItems.value = []; cartTotal.value = 0; return }
    const res = await api.get('/gio-hang', { skipGlobalLoader: true })
    if (res.data?.success) {
      cartItems.value = res.data.gio_hang || []

      const comboGroups = new Set()
      let count = 0
      cartItems.value.forEach(item => {
        if (item.id_combo && item.id_nhom_combo) {
          if (!comboGroups.has(item.id_nhom_combo)) {
            comboGroups.add(item.id_nhom_combo)
            count++
          }
        } else {
          count++
        }
      })
      cartCount.value = count

      cartTotal.value = res.data.tong_tien || 0
    }
  } catch { cartCount.value = 0; cartItems.value = []; cartTotal.value = 0 }
}

// ===================== WISHLIST =====================
const wishlistItems = ref([])

const fetchWishlist = async () => {
  try {
    const token = getToken()
    if (!token) { wishlistItems.value = []; return }
    const res = await api.get('/yeu-thich', { skipGlobalLoader: true })
    wishlistItems.value = res.data.data || res.data || []
  } catch { wishlistItems.value = [] }
}

const removeWishlist = async (id) => {
  try {
    await api.delete(`/yeu-thich/xoa/${id}`)
    wishlistItems.value = wishlistItems.value.filter(i => i.id !== id)
    window.dispatchEvent(new Event('wishlist-updated'))
  } catch (err) { console.error('Lỗi khi xóa khỏi yêu thích', err) }
}

const formatPrice = (value) => {
  if (!value) return '0₫'
  return parseInt(value).toLocaleString('vi-VN') + '₫'
}

const getWishlistImg = (item) => {
  const imgPath = item.bienthe?.hinhanh || item.bienthe?.sanpham?.hinhanh
  return imgPath ? storageUrl(imgPath) : 'https://placehold.co/150'
}

const handleCartUpdated = () => fetchCart()
const handleWishlistUpdated = () => fetchWishlist()
let headerDataIdleId = null
let headerDataTimeoutId = null

const scheduleHeaderDataHydration = () => {
  const hydrate = () => {
    headerDataIdleId = null
    headerDataTimeoutId = null
    fetchUser()
  }

  if ('requestIdleCallback' in window) {
    headerDataIdleId = window.requestIdleCallback(hydrate, { timeout: 1200 })
  } else {
    headerDataTimeoutId = setTimeout(hydrate, 300)
  }
}

onMounted(() => {
  if (!getToken()) {
    cartCount.value = 0
    cartItems.value = []
    cartTotal.value = 0
    wishlistItems.value = []
  }
  window.addEventListener('cart-updated', handleCartUpdated)
  window.addEventListener('wishlist-updated', handleWishlistUpdated)
  window.addEventListener('user-updated', fetchUser)
  window.addEventListener('scroll', handleScroll, { passive: true })
  scheduleHeaderDataHydration()

  // Announcement bar rotation
  annTimer = setInterval(() => {
    annIdx.value = (annIdx.value + 1) % announcements.length
  }, 3500)
  const warmProductsPage = () => {
    const connection = navigator.connection || navigator.webkitConnection || navigator.mozConnection
    if (connection?.saveData || ['slow-2g', '2g'].includes(connection?.effectiveType)) return
    import('../Web/TrangLaptop.vue')
    prefetchProductsPage().then(res => {
      try {
        if (res && Array.isArray(res.productsRaw)) {
          updateFeaturedProducts(res.productsRaw)
        }
      } catch (error) {
        console.error('Khong the cap nhat mega menu tu cache san pham:', error)
      }
    }).catch(() => { })
  }
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(warmProductsPage, { timeout: 900 })
  } else {
    setTimeout(warmProductsPage, 500)
  }

  document.addEventListener('click', handleOutside)
})

onUnmounted(() => {
  window.removeEventListener('cart-updated', handleCartUpdated)
  window.removeEventListener('wishlist-updated', handleWishlistUpdated)
  window.removeEventListener('user-updated', fetchUser)
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', handleOutside)
  if (headerDataIdleId !== null && 'cancelIdleCallback' in window) {
    window.cancelIdleCallback(headerDataIdleId)
  }
  if (headerDataTimeoutId !== null) {
    clearTimeout(headerDataTimeoutId)
  }
  clearInterval(annTimer)
  clearTimeout(megaLeaveTimer)
})

const goToCart = async () => {
  const token = getToken()
  if (!token) {
    const swal = await getSwal()
    swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập trước!')
    router.push({ path: '/login', query: { redirect: '/cart' } })
    return
  }
  router.push('/cart')
}

const toggleWishlist = () => {
  const token = getToken()
  if (!token) { router.push('/login'); return }
  showWishlist.value = !showWishlist.value
  if (showWishlist.value) showUser.value = false
}

const toggleUser = () => {
  const token = getToken()
  if (!token) { router.push('/login'); return }
  showUser.value = !showUser.value
  if (showUser.value) showWishlist.value = false
}

const goAdmin = () => {
  showUser.value = false
  router.push('/admin')
}

const handleOutside = (e) => {
  if (!e.target.closest('.dropdown-wrap') && !e.target.closest('.mega-nav-item')) {
    showWishlist.value = false
    showUser.value = false
    activeMegaMenu.value = null
  }
  if (!e.target.closest('.search-container')) {
    showSearchSuggestions.value = false
  }
}

const user = ref(null)

const getUserRole = (account) => String(account?.vaitro || account?.role || '').toLowerCase()
const isAdminAccount = computed(() => {
  const role = getUserRole(user.value)
  return Boolean(role && role !== 'user')
})
const accountBadge = computed(() => isAdminAccount.value ? 'Quản trị hệ thống' : 'Predator Member')

const avatarUrl = computed(() => {
  const avatarPath = user.value?.avatar || user.value?.anhdaidien
  if (!user.value || !avatarPath) {
    return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.value?.name || user.value?.ten || 'User') + '&background=6366f1&color=fff&bold=true'
  }
  const rawAvatar = String(avatarPath).startsWith('http') ? avatarPath : storageUrl(avatarPath)
  return withImageVersion(rawAvatar, user.value.updated_at || user.value.updatedAt)
})

const handleAvatarError = (e) => {
  const target = e?.target
  if (!target || target.dataset.fallbackApplied === 'true') return
  target.dataset.fallbackApplied = 'true'
  target.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.value?.name || user.value?.ten || 'User') + '&background=6366f1&color=fff&bold=true'
}

const fetchUser = () => {
  user.value = getUser()
  if (user.value) { fetchCart(); fetchWishlist() }
}

const handleLogout = async () => {
  const swal = await getSwal()
  const isConfirmed = await swal.confirm('Xác nhận đăng xuất', 'Bạn có chắc chắn muốn thoát khỏi hệ thống?')
  if (!isConfirmed) return
  showUser.value = false
  api.post('/logout').catch((err) => console.log('Logout API lỗi (bỏ qua):', err))
  clearAuth()
  localStorage.removeItem('remember_email')
  cartCount.value = 0
  wishlistItems.value = []

  const publicPages = [
    '/',
    '/laptop',
    '/phu-kien',
    '/gaming',
    '/login',
    '/dang-nhap',
    '/register',
    '/dang-ky',
    '/forgot-password',
    '/quen-mat-khau',
    '/otp-verify',
    '/xac-thuc-otp',
    '/reset-password',
    '/reset_password',
    '/dat-lai-mat-khau',
    '/login-success',
    '/dang-nhap-thanh-cong',
    '/news',
    '/tin-tuc',
    '/contact',
    '/lien-he',
    '/cart',
    '/gio-hang',
    '/thank-you',
    '/cam-on',
    '/payment-failed',
    '/thanh-toan-that-bai',
    '/khuyen-mai',
  ]

  const isPublic =
    publicPages.includes(route.path) ||
    route.path.startsWith('/products/') ||
    route.path.startsWith('/san-pham/') ||
    route.path.startsWith('/news/') ||
    route.path.startsWith('/tin-tuc/')

  if (!isPublic) {
    router.push('/')
  }
}

const warmProductsPageNow = () => {
  const connection = navigator.connection || navigator.webkitConnection || navigator.mozConnection
  if (connection?.saveData || ['slow-2g', '2g'].includes(connection?.effectiveType)) return
  import('../Web/TrangLaptop.vue')
  prefetchProductsPage().catch(() => { })
}

const openLuckyWheel = () => {
  window.dispatchEvent(new Event('open-lucky-wheel'))
}

const openLuckyWheelMobile = () => {
  isMobileMenuOpen.value = false
  openLuckyWheel()
}
</script>

<template>
  <!-- ===== ANNOUNCEMENT BAR ===== -->
  <div class="ann-bar">
    <div class="ann-container">
      <div class="ann-right">
        <a href="tel:18009999" class="ann-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path
              d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.08 6.08l.95-.95a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z" />
          </svg>
          1800 9999
        </a>
        <span class="ann-sep">|</span>
        <span class="ann-link">🇻🇳 VNĐ</span>
      </div>
    </div>
  </div>

  <!-- ===== MAIN HEADER ===== -->
  <header class="header" :class="{ 'header--hidden': isHeaderHidden, 'header--scrolled': isScrolled }">
    <div class="header-inner">

      <!-- LOGO -->
      <router-link to="/" class="logo-wrap" aria-label="NextGen Laptop">
        <img src="/nextgen_logo_header.png" alt="NextGen Laptop" class="logo-img" />
      </router-link>

      <!-- MEGA MENU NAVIGATION -->
      <nav class="mega-nav">
        <div class="mega-nav-links">
          <router-link to="/" class="nav-plain-link nav-discover-link" active-class="nav-discover-parent"
            exact-active-class="router-link-exact-active">
            Khám phá
          </router-link>
          <div v-for="(menu, key) in visibleMegaMenuData" :key="key" class="mega-nav-item" @mouseenter="openMega(key)"
            @mouseleave="closeMega">
            <button class="nav-btn" :class="{ active: activeMegaMenu === key, current: isMenuCurrent(key) }"
              :aria-expanded="activeMegaMenu === key" @click="navToCategory(key)">
              {{ menu.label }}
              <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </button>

            <!-- MEGA DROPDOWN -->
            <transition name="mega-drop">
              <div v-if="activeMegaMenu === key" class="mega-dropdown" :style="{
                '--accent': menu.accent,
                '--accent-bg': menu.accentBg,
                width: (menu.sections.length + (menu.featured ? 1 : 0)) === 2
                  ? '500px'
                  : (menu.sections.length + (menu.featured ? 1 : 0)) === 3
                    ? '760px'
                    : '920px'
              }" @mouseenter="keepMega" @mouseleave="closeMega">
                <!-- BODY -->
                <div class="mega-body" :style="{
                  gridTemplateColumns: menu.featured
                    ? `repeat(${menu.sections.length}, minmax(190px, 1fr)) minmax(230px, 1.25fr)`
                    : `repeat(${menu.sections.length}, minmax(0, 1fr))`
                }">
                  <!-- SECTIONS (Columns 1, 2, 3) -->
                  <div v-for="(section, sIdx) in menu.sections" :key="section.title" class="mega-col"
                    :class="`col-${sIdx + 1}`">
                    <div class="mega-col-title">
                      <span class="mega-title-icon">
                        <component :is="megaSectionIcon(section.title)" :size="17" :stroke-width="2.2" />
                      </span>
                      {{ section.title }}
                    </div>
                    <ul class="mega-list">
                      <li v-for="it in section.items" :key="it.label">
                        <a href="#" class="mega-link" @click.prevent="navToMegaItem(key, it)">
                          <span v-if="megaBrandLogo(it.label)" class="mega-link-brand-logo" aria-hidden="true">
                            <img :src="megaBrandLogo(it.label)" :alt="`${it.label} logo`" />
                          </span>
                          <span v-else class="mega-link-icon" aria-hidden="true">
                            <component :is="megaItemIcon(it.label, section.title)" :size="18" :stroke-width="2.35" />
                          </span>
                          <span class="mega-link-text">{{ it.label }}</span>
                          <span v-if="it.badge" class="mega-item-badge" :class="it.badge.toLowerCase()">{{ it.badge
                            }}</span>
                        </a>
                      </li>
                    </ul>
                  </div>

                  <!-- FEATURED PANEL (Column 4) -->
                  <div v-if="menu.featured" class="mega-featured-panel">
                    <div class="mega-col-title">NỔI BẬT</div>
                    <div class="mfp-card" @click="navToFeaturedItem(key, menu.featured)">
                      <div class="mfp-img-box">
                        <img :src="menu.featured.img" :alt="menu.featured.name" class="mfp-img"
                          @error="e => e.target.style.display = 'none'" />
                        <span class="mfp-badge" :style="{ background: menu.accent }">{{ menu.featured.tag || 'PRO'
                          }}</span>
                      </div>
                      <div class="mfp-info">
                        <div class="mfp-name">{{ menu.featured.name }}</div>
                        <div class="mfp-price" :style="{ color: menu.accent }">{{ menu.featured.price }}</div>
                        <button class="mfp-btn" :style="{ background: menu.accent }">
                          Xem ngay →
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </transition>
          </div>

          <!-- Extra links -->
          <router-link to="/tin-tuc" class="nav-plain-link" @mouseenter="warmProductsPageNow">Tin tức</router-link>
          <router-link to="/lien-he" class="nav-plain-link">Liên hệ</router-link>
        </div>

        <transition name="ann-slide" mode="out-in">
          <span class="nav-announcement" :key="annIdx"
            v-html="announcements[annIdx].icon + ' ' + announcements[annIdx].text"></span>
        </transition>
      </nav>

      <!-- SEARCH BAR -->
      <div class="search-container">
        <div class="search-wrap" :class="{ focused: searchFocused }">
          <input type="text" class="search-input" placeholder="Tìm kiếm" v-model="searchQuery" @input="onSearchInput"
            @keyup.enter="handleSearch" @focus="onSearchFocus" @blur="onSearchBlur" />
          <button v-if="searchQuery" class="search-clear"
            @click="searchQuery = ''; searchFocused = false; searchSuggestions = []; showSearchSuggestions = false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
          <span class="search-keyboard" aria-hidden="true"></span>
          <button type="button" class="search-submit" @click="handleSearch" aria-label="Tim kiem" title="Tim kiem">
            Tìm
          </button>
        </div>

        <!-- SUGGESTIONS DROPDOWN -->
        <transition name="suggest-fade">
          <div class="search-suggestions"
            v-if="showSearchSuggestions && searchQuery.trim() && (searchSuggestions.length > 0 || isSearchingSuggestions)">
            <div v-if="isSearchingSuggestions" class="suggest-loading">
              <span>Đang tìm kiếm...</span>
            </div>
            <div v-else class="suggest-list">
              <div v-for="product in searchSuggestions" :key="product.id_sanpham" class="suggest-item"
                @mousedown="goToProductDetail(product)">
                <img :src="product.image" class="suggest-img" alt="product" />
                <div class="suggest-info">
                  <p class="suggest-name">{{ product.tenSP }}</p>
                  <p class="suggest-price">{{ formatPrice(product.gia) }}</p>
                </div>
              </div>
              <div class="suggest-more" @mousedown="goToMoreResults">
                Xem thêm &quot;{{ searchQuery }}&quot; &rarr;
              </div>
            </div>
          </div>
        </transition>
      </div>

      <!-- HEADER ACTIONS -->
      <div class="header-actions">
        <div class="header-contact">
          <div class="header-contact-primary">
            <a href="tel:18009999" class="ann-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path
                  d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.08 6.08l.95-.95a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z" />
              </svg>
              1800 9999
            </a>
            <span class="ann-sep">|</span>
            <span class="ann-link header-currency">VND</span>
          </div>
          <a href="mailto:support@nextgenlaptop.vn" class="header-email">support@nextgenlaptop.vn</a>
        </div>

        <!-- WISHLIST -->
        <div class="dropdown-wrap" @mouseenter="showWishlist = true" @mouseleave="showWishlist = false">
          <button class="icon-action" :class="{ active: showWishlist }" @click.stop="toggleWishlist" title="Yêu thích">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path
                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
            <span class="action-badge red" v-if="wishlistItems.length > 0">{{ wishlistItems.length }}</span>
          </button>

          <transition name="drop">
            <div class="hdr-dropdown wishlist-drop" v-if="showWishlist">
              <div class="drop-head">
                <span class="drop-ttl">Yêu thích</span>
                <span class="drop-cnt">{{ wishlistItems.length }} sp</span>
              </div>
              <div class="drop-body">
                <div v-if="wishlistItems.length === 0" class="drop-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path
                      d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                  </svg>
                  <p>Chưa có sản phẩm yêu thích</p>
                </div>
                <div class="drop-item" v-for="item in wishlistItems" :key="item.id">
                  <img :src="getWishlistImg(item)" :alt="item.bienthe?.sanpham?.tenSP" />
                  <div class="drop-item-info">
                    <p class="di-name">{{ item.bienthe?.sanpham?.tenSP || 'Sản phẩm' }}</p>
                    <p class="di-price">{{ formatPrice(item.bienthe?.gia) }}</p>
                  </div>
                  <button class="di-remove" @click="removeWishlist(item.id)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <line x1="18" y1="6" x2="6" y2="18" />
                      <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                  </button>
                </div>
              </div>
              <div class="drop-foot" v-if="wishlistItems.length > 0">
                <router-link to="/wishlistpage" class="drop-cta" @click="showWishlist = false">Xem tất cả
                  →</router-link>
              </div>
            </div>
          </transition>
        </div>

        <!-- CART -->
        <div class="dropdown-wrap" @mouseenter="showCartDropdown = true" @mouseleave="showCartDropdown = false">
          <button class="icon-action cart-action" :class="{ active: showCartDropdown }" @click="goToCart"
            title="Giỏ hàng">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M6 6h15l-1.5 9h-13z" />
              <circle cx="9" cy="20" r="1" />
              <circle cx="18" cy="20" r="1" />
            </svg>
            <span class="action-badge blue" v-if="cartCount > 0">{{ cartCount > 99 ? '99+' : cartCount }}</span>
          </button>

          <transition name="drop">
            <div class="hdr-dropdown cart-drop" v-if="showCartDropdown">
              <div class="drop-head">
                <span class="drop-ttl">Giỏ hàng</span>
                <span class="drop-cnt">{{ cartCount }} sp</span>
              </div>
              <div class="drop-body">
                <div v-if="cartItems.length === 0" class="drop-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M6 6h15l-1.5 9h-13z" />
                    <circle cx="9" cy="20" r="1" />
                    <circle cx="18" cy="20" r="1" />
                  </svg>
                  <p>Giỏ hàng của bạn đang trống</p>
                </div>
                <div class="drop-item" v-for="item in cartItems" :key="item.id_giohang">
                  <img :src="item.hinh_anh || 'https://placehold.co/60'" :alt="item.ten_san_pham" />
                  <div class="drop-item-info">
                    <p class="di-name">{{ item.ten_san_pham }}</p>
                    <div class="di-meta" v-if="item.ten_bienthe"><span>{{ item.ten_bienthe }}</span></div>
                    <div class="di-price-row">
                      <span class="di-price">{{ formatPrice(item.gia) }}</span>
                      <span class="di-qty">×{{ item.soluong }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="drop-foot" v-if="cartItems.length > 0">
                <div class="cart-total-row">
                  <span>Tổng cộng</span>
                  <span class="cart-total-val">{{ formatPrice(cartTotal) }}</span>
                </div>
                <router-link to="/cart" class="drop-cta" @click="showCartDropdown = false">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1" />
                    <circle cx="20" cy="21" r="1" />
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                  </svg>
                  Xem giỏ hàng
                </router-link>
              </div>
            </div>
          </transition>
        </div>

        <!-- USER -->
        <div class="dropdown-wrap">
          <button class="icon-action user-action" :class="{ active: showUser }" @click.stop="toggleUser"
            title="Tài khoản">
            <img v-if="user" :src="avatarUrl" @error="handleAvatarError" class="user-avatar" :alt="user.name || 'User'" />
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
              <circle cx="12" cy="7" r="4" />
            </svg>
          </button>

          <transition name="drop">
            <div class="hdr-dropdown user-drop" v-if="showUser">
              <div class="user-card">
                <img :src="avatarUrl" @error="handleAvatarError" :alt="user?.name || 'User'" class="user-card-avatar" />
                <div class="user-card-info">
                  <p class="uc-name">{{ user?.name || 'Khách hàng' }}</p>
                  <p class="uc-email">{{ user?.email }}</p>
                  <span class="uc-badge">{{ accountBadge }}</span>
                </div>
              </div>
              <div class="user-menu">
                <template v-if="isAdminAccount">
                  <button class="um-item admin" @click="goAdmin">
                    <span class="um-left">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3" />
                        <path
                          d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                      </svg>
                      Quản trị hệ thống
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <polyline points="9 18 15 12 9 6" />
                    </svg>
                  </button>
                </template>
                <template v-else>
                  <router-link to="/profile" @click="showUser = false" class="um-item">
                    <span class="um-left">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                      </svg>
                      Thông tin cá nhân
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <polyline points="9 18 15 12 9 6" />
                    </svg>
                  </router-link>

                  <router-link to="/affiliate" @click="showUser = false" class="um-item">
                    <span class="um-left">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2" />
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                      </svg>
                      Affiliate Center
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <polyline points="9 18 15 12 9 6" />
                    </svg>
                  </router-link>
                </template>
                <div class="um-divider"></div>
                <button class="um-item logout" @click="handleLogout">
                  <span class="um-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                      <polyline points="16 17 21 12 16 7" />
                      <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Đăng xuất
                  </span>
                </button>
              </div>
            </div>
          </transition>
        </div>

        <!-- HAMBURGER (mobile) -->
        <button class="hamburger" :class="{ open: isMobileMenuOpen }" @click="isMobileMenuOpen = !isMobileMenuOpen"
          aria-label="Menu">
          <span></span><span></span><span></span>
        </button>

      </div>
    </div>
  </header>

  <!-- ===== MOBILE DRAWER ===== -->
  <div class="mob-overlay" v-if="isMobileMenuOpen" @click="isMobileMenuOpen = false"></div>
  <transition name="mob-slide">
    <div class="mob-drawer" v-if="isMobileMenuOpen">
      <div class="mob-head">
        <router-link to="/" class="mob-logo" @click="isMobileMenuOpen = false">
          <img src="/nextgen_logo_header.png" alt="NextGen Laptop" />
        </router-link>
        <button class="mob-close" @click="isMobileMenuOpen = false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </div>

      <div class="mob-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.3-4.3" />
        </svg>
        <input type="text" placeholder="Tìm kiếm sản phẩm..." v-model="searchQuery" @keyup.enter="handleSearch" />
      </div>

      <nav class="mob-nav">
        <div class="mob-nav-label">Danh mục</div>
        <router-link to="/" @click="isMobileMenuOpen = false" class="mob-link" active-class="mob-discover-parent"
          exact-active-class="router-link-exact-active">
          Khám phá
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </router-link>
        <router-link v-for="(menu, key) in megaMenuData" :key="key" :to="mobileMenuTarget(key)"
          @click="isMobileMenuOpen = false" class="mob-link" :class="{ current: isMenuCurrent(key) }">
          {{ menu.label }}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </router-link>
        <div class="mob-nav-label">Thêm</div>
        <router-link to="/news" @click="isMobileMenuOpen = false" class="mob-link">Tin tức</router-link>
        <router-link to="/contact" @click="isMobileMenuOpen = false" class="mob-link">Liên hệ</router-link>
        <a href="#" @click.prevent="openLuckyWheelMobile" class="mob-link">Vòng quay may mắn</a>
      </nav>

      <div class="mob-footer">
        <router-link to="/cart" @click="isMobileMenuOpen = false" class="mob-cta">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 6h15l-1.5 9h-13z" />
            <circle cx="9" cy="20" r="1" />
            <circle cx="18" cy="20" r="1" />
          </svg>
          Giỏ hàng {{ cartCount > 0 ? '(' + cartCount + ')' : '' }}
        </router-link>
      </div>
    </div>
  </transition>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap');

*,
*::before,
*::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

/* ============================= ANNOUNCEMENT BAR ============================= */
.ann-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1001;
  background: #0f172a;
  height: 40px;
  display: none;
  align-items: center;
  overflow: hidden;
}

.ann-container {
  width: min(calc(100% - 64px), 1360px);
  max-width: 1360px;
  margin: auto;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.ann-container:has(.ann-right:only-child) {
  justify-content: flex-end;
}

.ann-text {
  font-family: 'Inter', sans-serif;
  font-size: 12.5px;
  color: #94a3b8;
  letter-spacing: 0.1px;
}

.ann-text :deep(.ann-code-icon) {
  width: 14px;
  height: 14px;
  margin-right: 6px;
  color: #38bdf8;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
  vertical-align: -2px;
}

.ann-text :deep(strong) {
  color: #e2e8f0;
  font-weight: 600;
}

.ann-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.ann-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: #94a3b8;
  text-decoration: none;
  cursor: pointer;
  transition: color 0.2s;
  font-family: 'Inter', sans-serif;
}

.ann-link svg {
  width: 12px;
  height: 12px;
}

.ann-right>.ann-link:last-child {
  gap: 6px;
  font-size: 0;
}

.ann-right>.ann-link:last-child::before {
  content: "VND";
  display: inline-grid;
  place-items: center;
  width: 22px;
  height: 14px;
  border: 1px solid currentColor;
  border-radius: 4px;
  font-size: 8px;
  font-weight: 700;
  line-height: 1;
}

.ann-right>.ann-link:last-child::after {
  content: "VND";
  font-size: 12px;
}

.ann-link:hover {
  color: #e2e8f0;
}

.ann-sep {
  color: #cbd5e1;
  font-size: 12px;
}

.ann-slide-enter-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.ann-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.ann-slide-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.ann-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* ============================= MAIN HEADER ============================= */
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background-color: #0d1b2e;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
  border-bottom: 0;
  transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
  will-change: transform;
  height: 128px;
}

.header.header--hidden {
  transform: translateY(-100%);
}

.header.header--scrolled {
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.header-inner {
  position: relative;
  width: min(calc(100% - 64px), 1360px);
  max-width: 1360px;
  margin: 0 auto;
  padding: 0;
  height: 128px;
  display: grid;
  grid-template-columns: minmax(224px, 1fr) minmax(480px, 760px) minmax(224px, 1fr);
  grid-template-rows: 80px 48px;
  align-items: center;
  column-gap: 32px;
  row-gap: 0;
}

/* LOGO */
.logo-wrap {
  grid-column: 1;
  grid-row: 1;
  justify-self: start;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 224px;
  min-width: 224px;
  height: 56px;
  text-decoration: none;
  flex-shrink: 0;
  overflow: visible;
  transition: filter 0.2s ease;
  margin-left: 16px;
}

.logo-wrap:hover {
  filter: brightness(1.05);
}

.logo-img {
  width: 100%;
  height: 100%;
  max-height: 56px;
  object-fit: contain;
  object-position: left center;
  filter: drop-shadow(0 3px 8px rgba(0, 0, 0, 0.25));
  transform: scale(1.2);
  transform-origin: left center;
}

/* ============================= MEGA NAV ============================= */
.mega-nav {
  position: relative;
  grid-column: 1 / -1;
  grid-row: 2;
  display: flex;
  align-items: center;
  justify-self: stretch;
  width: 100%;
  max-width: none;
  margin-left: 0;
  gap: 24px;
  justify-content: center;
  border-top: 1px solid rgba(148, 163, 184, 0.16);
  height: 40px;
  padding: 0;
  align-self: start;
  transform: translateY(-24px);
  min-width: 0;
  z-index: 2;
}

.mega-nav-item {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.mega-nav-links {
  width: min(100%, 760px);
  height: 100%;
  margin-inline: auto;
  display: flex;
  align-items: center;
  justify-content: space-evenly;
  gap: 0;
}

.nav-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  height: 40px;
  min-width: 76px;
  padding: 8px;
  border: none;
  background: transparent;
  font-family: 'Outfit', sans-serif;
  font-size: 14px;
  font-weight: 600;
  color: #cbd5e1;
  cursor: pointer;
  border-radius: 10px;
  transition: all 0.2s;
  white-space: nowrap;
  text-transform: none !important;
}

.nav-btn:hover,
.nav-btn.active {
  background: transparent;
  color: #60a5fa;
}

.nav-btn.current {
  background: transparent;
  color: #60a5fa;
  font-weight: 700;
}

.nav-chevron {
  width: 12px;
  height: 12px;
  transition: transform 0.2s;
  stroke: currentColor;
}

.nav-btn.active .nav-chevron {
  transform: rotate(180deg);
}

.nav-plain-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 40px;
  min-width: 76px;
  padding: 8px 8px;
  font-family: 'Outfit', sans-serif;
  font-size: 14px;
  font-weight: 600;
  color: #cbd5e1;
  text-decoration: none;
  border-radius: 10px;
  transition: all 0.2s;
  white-space: nowrap;
  position: relative;
  text-transform: none !important;
}

.nav-plain-link:hover {
  background: transparent;
  color: #60a5fa;
}

/* Active route highlight */
.nav-plain-link.router-link-active,
.nav-plain-link.router-link-exact-active {
  color: #60a5fa;
  background: transparent;
  font-weight: 700;
}

/* MEGA DROPDOWN */
.mega-dropdown {
  position: absolute;
  top: calc(100% + 12px);
  left: 50%;
  transform: translateX(-50%);
  background: #08111e;
  backdrop-filter: blur(30px) saturate(190%);
  -webkit-backdrop-filter: blur(30px) saturate(190%);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 17px;
  box-shadow:
    0 24px 50px rgba(0, 0, 0, 0.55),
    0 8px 24px rgba(0, 0, 0, 0.35),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  z-index: 9999;
  width: min(760px, calc(100vw - 48px));
  max-width: calc(100vw - 32px);
  overflow: hidden;
  padding: 20px;
}

/* Bridge trong suốt lấp khoảng trống giữa button và dropdown */
.mega-dropdown::before {
  content: '';
  position: absolute;
  top: -16px;
  left: 0;
  right: 0;
  height: 16px;
  background: transparent;
}

/* BODY LAYOUT - 4 COLUMNS WITH DIVIDERS */
.mega-body {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1.25fr;
  gap: 0;
  background: transparent;
}

.mega-col {
  min-width: 0;
  padding: 0 11px;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  position: relative;
}

/* Vertical dividers using pseudo-elements for precision styling */
.mega-col::after {
  content: '';
  position: absolute;
  top: 4px;
  bottom: 4px;
  right: -12px;
  width: 1px;
  background: rgba(255, 255, 255, 0.08);
}

.mega-body .mega-col:nth-child(3)::after {
  display: none;
  /* Hide before featured panel */
}

.mega-col-title {
  font-family: 'Outfit', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: capitalize;
  color: #ffffff;
  min-height: 26px;
  margin-bottom: 10px;
  padding: 0 6px;
  border-bottom: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.mega-title-icon {
  width: 22px;
  height: 22px;
  color: #8fc3ff;
  border: 1px solid rgba(96, 165, 250, 0.34);
  border-radius: 6px;
  background: rgba(96, 165, 250, 0.1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
}

.mega-title-icon svg,
.mega-link-icon svg {
  display: block;
}

/* COMPACT CLEAN LINKS */
.mega-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.mega-link {
  display: grid;
  grid-template-columns: 32px minmax(0, 1fr);
  align-items: center;
  gap: 11px;
  min-height: 52px;
  padding: 7px 38px 7px 8px;
  box-sizing: border-box;
  position: relative;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 9px;
  text-decoration: none;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.mega-link-icon {
  width: 32px;
  height: 32px;
  box-sizing: border-box;
  color: #93c5fd;
  background: rgba(37, 99, 235, 0.16);
  border: 1px solid rgba(96, 165, 250, 0.24);
  border-radius: 8px;
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  filter: drop-shadow(0 0 10px rgba(96, 165, 250, 0.18));
  transition: color 0.15s ease, transform 0.15s ease;
}

.mega-link-brand-logo {
  width: 30px;
  height: 30px;
  padding: 4px;
  box-sizing: border-box;
  border-radius: 8px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(226, 232, 240, 0.9));
  border: 1px solid rgba(191, 219, 254, 0.38);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.16), inset 0 1px 0 rgba(255, 255, 255, 0.9);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.mega-link-brand-logo img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.mega-link-brand-logo+.mega-link-text {
  white-space: nowrap;
  font-size: 12px;
}

.mega-list li:first-child .mega-link {
  background: rgba(96, 165, 250, 0.1);
  border-color: rgba(96, 165, 250, 0.22);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.mega-link-text {
  font-size: 13px;
  font-weight: 600;
  line-height: 1.45;
  color: #ffffff;
  flex: 1 1 auto;
  min-width: 0;
  overflow-wrap: anywhere;
  transition: color 0.15s ease;
}

.nav-announcement {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  width: auto;
  max-width: 280px;
  min-width: 0;
  padding: 0 8px;
  color: #cbd5e1;
  background: transparent;
  border: 0;
  border-radius: 0;
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
  white-space: nowrap;
  overflow: hidden;
  box-shadow: none;
}

.nav-announcement :deep(strong) {
  color: #60a5fa;
  font-weight: 700;
}

.nav-announcement :deep(.ann-code-icon) {
  width: 14px;
  height: 14px;
  margin-right: 8px;
  color: #38bdf8;
  flex: 0 0 auto;
}

.mega-link:hover {
  background: var(--accent-bg);
  border-color: rgba(96, 165, 250, 0.22);
  transform: translateX(4px);
}

.mega-link:hover .mega-link-text {
  color: var(--accent);
  font-weight: 600;
}

.mega-link:hover .mega-link-icon {
  color: #ffffff;
  background: rgba(37, 99, 235, 0.32);
  border-color: rgba(96, 165, 250, 0.42);
  transform: scale(1.08);
}

.mega-link:hover .mega-link-brand-logo {
  transform: scale(1.05);
  border-color: rgba(96, 165, 250, 0.55);
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.94);
}

/* ITEM BADGES */
.mega-item-badge {
  position: absolute;
  top: 6px;
  right: 6px;
  font-size: 8px;
  line-height: 1;
  font-weight: 800;
  padding: 3px 4px;
  border-radius: 4px;
  letter-spacing: 0.2px;
  text-transform: capitalize;
}

.mega-item-badge.hot {
  background: rgba(239, 68, 68, 0.18);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.15);
}

.mega-item-badge.new {
  background: rgba(34, 197, 94, 0.18);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.15);
}

.mega-item-badge.pro {
  background: rgba(139, 92, 246, 0.18);
  color: #a78bfa;
  border: 1px solid rgba(139, 92, 246, 0.15);
}

.mega-item-badge.sale {
  background: rgba(249, 115, 22, 0.18);
  color: #fb923c;
  border: 1px solid rgba(249, 115, 22, 0.15);
}

.mega-featured-panel {
  min-width: 0;
  padding: 0 0 0 14px;
  display: flex;
  flex-direction: column;
}

.mega-featured-panel .mega-col-title {
  padding-left: 0;
}

.mfp-card {
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 9px;
  padding: 10px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mfp-card:hover {
  transform: translateY(-4px);
  background: rgba(255, 255, 255, 0.06);
  border-color: var(--accent);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4), 0 0 15px var(--accent-bg);
}

.mfp-img-box {
  position: relative;
  background: rgba(255, 255, 255, 0.16);
  border-radius: 8px;
  height: 92px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border: 1px solid rgba(255, 255, 255, 0.04);
  overflow: hidden;
}

.mfp-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.mfp-card:hover .mfp-img {
  transform: scale(1.08);
}

.mfp-badge {
  position: absolute;
  top: 4px;
  right: 4px;
  font-size: 9px;
  font-weight: 800;
  color: white;
  padding: 2.5px 7px;
  border-radius: 6px;
  text-transform: capitalize;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
}

.mfp-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.mfp-name {
  font-size: 12.5px;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.4;
  transition: color 0.2s ease;
}

.mfp-card:hover .mfp-name {
  color: var(--accent);
}

.mfp-price {
  font-family: 'Outfit', sans-serif;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.2px;
}

.mfp-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 7px;
  border: none;
  border-radius: 8px;
  color: white;
  font-family: 'Outfit', sans-serif;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  margin-top: 4px;
}

.mfp-btn:hover {
  filter: brightness(1.1);
  box-shadow: 0 4px 12px var(--accent-bg);
}

/* MEGA DROPDOWN TRANSITIONS */
.mega-drop-enter-active {
  transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.mega-drop-leave-active {
  transition: all 0.18s ease;
}

.mega-drop-enter-from {
  opacity: 0;
  transform: translateX(-50%) translateY(-12px) scale(0.96);
}

.mega-drop-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-8px);
}

/* ============================= SEARCH ============================= */
.search-container {
  position: absolute;
  left: 50%;
  top: 16px;
  transform: translateX(-50%);
  width: min(580px, calc(100% - 700px));
  max-width: 580px;
  z-index: 1000;
}

.search-wrap {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0;
  background: #0f0f10;
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 99px;
  padding: 0 0 0 24px;
  height: 48px;
  overflow: hidden;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.035),
    0 1px 2px rgba(0, 0, 0, 0.35);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.search-wrap.focused {
  border-color: rgba(255, 255, 255, 0.12);
  background: #101011;
  box-shadow:
    0 0 0 2px rgba(255, 255, 255, 0.035),
    inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.search-suggestions {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 100%;
  background: #141415;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
  overflow: hidden;
  z-index: 1001;
}

.suggest-loading {
  padding: 16px;
  text-align: center;
  color: #94a3b8;
  font-size: 14px;
}

.suggest-list {
  display: flex;
  flex-direction: column;
}

.suggest-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
}

.suggest-item:hover {
  background: rgba(255, 255, 255, 0.04);
  transform: translateX(4px);
}

.suggest-img {
  width: 44px;
  height: 44px;
  object-fit: cover;
  border-radius: 6px;
  background: #1e1e1f;
  flex-shrink: 0;
}

.suggest-info {
  flex: 1;
  min-width: 0;
}

.suggest-name {
  font-size: 13.5px;
  font-weight: 500;
  color: #e2e8f0;
  margin: 0 0 2px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: left;
}

.suggest-price {
  font-size: 12px;
  font-weight: 600;
  color: #3b82f6;
  margin: 0;
  text-align: left;
}

.suggest-more {
  padding: 12px;
  text-align: center;
  font-size: 13px;
  font-weight: 600;
  color: #3b82f6;
  cursor: pointer;
  background: rgba(59, 130, 246, 0.05);
  transition: background 0.2s, color 0.2s;
  border-top: 1px solid rgba(255, 255, 255, 0.03);
}

.suggest-more:hover {
  background: rgba(59, 130, 246, 0.1);
  color: #60a5fa;
}

/* Transitions */
.suggest-fade-enter-active,
.suggest-fade-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}

.suggest-fade-enter-from,
.suggest-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-family: 'Inter', sans-serif;
  font-size: 15px;
  font-weight: 500;
  color: #e7e7e7 !important;
  padding: 0 10px 0 0;
  min-width: 0;
  height: 100%;
}

.search-input::placeholder {
  color: #68686b !important;
  opacity: 1;
  text-align: center;
}

.search-clear {
  width: 22px;
  height: 22px;
  border: none;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  margin-right: 8px;
  transition: background 0.2s;
}

.search-clear:hover {
  background: rgba(239, 68, 68, 0.15);
}

.search-clear svg {
  width: 12px;
  height: 12px;
  color: #94a3b8;
}

.search-keyboard {
  position: relative;
  width: 21px;
  height: 14px;
  margin-right: 10px;
  flex-shrink: 0;
  opacity: 0.78;
}

.search-keyboard::before {
  content: '';
  position: absolute;
  inset: 1px 0 2px;
  border-radius: 2px;
  background:
    linear-gradient(#a9a9a9, #a9a9a9) 3px 3px / 3px 1.5px no-repeat,
    linear-gradient(#a9a9a9, #a9a9a9) 8px 3px / 3px 1.5px no-repeat,
    linear-gradient(#a9a9a9, #a9a9a9) 13px 3px / 3px 1.5px no-repeat,
    linear-gradient(#a9a9a9, #a9a9a9) 17px 3px / 3px 1.5px no-repeat,
    linear-gradient(#a9a9a9, #a9a9a9) 3px 7px / 3px 1.5px no-repeat,
    linear-gradient(#a9a9a9, #a9a9a9) 8px 7px / 3px 1.5px no-repeat,
    linear-gradient(#a9a9a9, #a9a9a9) 13px 7px / 3px 1.5px no-repeat,
    linear-gradient(#a9a9a9, #a9a9a9) 17px 7px / 3px 1.5px no-repeat,
    linear-gradient(#a9a9a9, #a9a9a9) 6px 10px / 10px 1.5px no-repeat,
    #7a7a7d;
  box-shadow: inset 0 0 0 1px #57575a;
}

.search-submit {
  position: relative;
  width: 56px;
  height: 100%;
  padding: 0;
  border: none;
  border-left: 1px solid rgba(255, 255, 255, 0.035);
  border-radius: 0 99px 99px 0;
  background: #282426;
  color: white;
  font-size: 0;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.2s;
}

.search-submit::before {
  content: '';
  width: 16px;
  height: 16px;
  border: 2.3px solid currentColor;
  border-radius: 50%;
  position: absolute;
  top: 10px;
  left: 18px;
}

.search-submit::after {
  content: '';
  width: 10px;
  height: 2.3px;
  background: currentColor;
  border-radius: 999px;
  position: absolute;
  top: 25px;
  left: 32px;
  transform: rotate(45deg);
  transform-origin: left center;
}

.search-submit:hover {
  background: #302b2e;
}

/* ============================= HEADER ACTIONS ============================= */
.header-actions {
  grid-column: 3;
  grid-row: 1;
  justify-self: end;
  display: flex;
  align-items: center;
  height: 40px;
  gap: 8px;
  flex-shrink: 0;
  min-width: max-content;
  margin-right: 16px;
}

.header-contact {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  margin-right: 8px;
  padding-right: 16px;
  border-right: 1px solid rgba(148, 163, 184, 0.2);
  white-space: nowrap;
}

.header-contact-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.header-contact .ann-link {
  color: #94a3b8;
  font-size: 12px;
}

.header-email {
  color: #64748b;
  font-family: 'Inter', sans-serif;
  font-size: 11px;
  line-height: 1;
  text-align: center;
  text-decoration: none;
  transition: color 0.2s;
}

.header-email:hover {
  color: #60a5fa;
}

.header-currency {
  min-width: 32px;
  justify-content: center;
}

.icon-action {
  position: relative;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1.5px solid rgba(96, 165, 250, 0.42);
  background: rgba(37, 99, 235, 0.07);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #94a3b8;
  box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.04), 0 3px 10px rgba(2, 8, 23, 0.16);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.icon-action svg {
  width: 20px;
  height: 20px;
}

.icon-action:hover {
  background: rgba(37, 99, 235, 0.16);
  border-color: #60a5fa;
  color: #60a5fa;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10), 0 6px 16px rgba(2, 8, 23, 0.24);
  transform: translateY(-1px);
}

.icon-action.active {
  background: rgba(37, 99, 235, 0.18);
  border-color: #60a5fa;
  color: #60a5fa;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
}

.cart-action {
  background: rgba(37, 99, 235, 0.07);
  color: #94a3b8;
  border-color: rgba(96, 165, 250, 0.42);
}

.cart-action svg {
  stroke: currentColor;
}

.cart-action:hover {
  background: rgba(37, 99, 235, 0.16);
  border-color: #60a5fa;
  color: #60a5fa;
  transform: translateY(-1px);
}

.cart-action.active {
  background: rgba(37, 99, 235, 0.18);
  border-color: #60a5fa;
  color: #60a5fa;
}

.user-action {
  padding: 0;
  overflow: hidden;
  border: 1.5px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
}

.user-action:hover {
  transform: none;
}

.user-action.active {
  border-color: #2563eb;
}

.user-avatar {
  width: 36px;
  min-width: 36px;
  height: 36px;
  border-radius: 10px;
  object-fit: cover;
  object-position: center;
  display: block;
}

.action-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  color: white;
  font-size: 10px;
  font-weight: 800;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #0d1b2e;
  z-index: 2;
}

.action-badge.red {
  background: #ef4444;
}

.action-badge.blue {
  background: #2563eb;
}

/* ============================= DROPDOWNS ============================= */
.dropdown-wrap {
  position: relative;
}

.hdr-dropdown {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  background: rgba(8, 18, 32, 0.92);
  backdrop-filter: blur(18px) saturate(150%);
  -webkit-backdrop-filter: blur(18px) saturate(150%);
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-radius: 18px;
  box-shadow:
    0 28px 70px rgba(0, 0, 0, 0.62),
    0 6px 18px rgba(0, 0, 0, 0.36),
    inset 0 1px 1px rgba(255, 255, 255, 0.12);
  z-index: 9998;
  overflow: hidden;
  min-width: 300px;
}

.hdr-dropdown::before {
  content: '';
  position: absolute;
  top: -7px;
  right: 14px;
  width: 13px;
  height: 13px;
  background: rgba(8, 18, 32, 0.92);
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-bottom: none;
  border-right: none;
  transform: rotate(45deg);
}

.wishlist-drop {
  width: 330px;
}

.cart-drop {
  width: 330px;
}

.user-drop {
  width: 280px;
  min-width: 260px;
}

/* DROP HEADER */
.drop-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.drop-ttl {
  font-family: 'Outfit', sans-serif;
  font-size: 14.5px;
  font-weight: 800;
  color: #f1f5f9;
}

.drop-cnt {
  font-size: 10.5px;
  font-weight: 700;
  color: #60a5fa;
  background: rgba(37, 99, 235, 0.15);
  padding: 2px 8px;
  border-radius: 20px;
  border: 1px solid rgba(37, 99, 235, 0.2);
}

/* DROP BODY */
.drop-body {
  max-height: 280px;
  overflow-y: auto;
  padding: 8px;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
}

.drop-body::-webkit-scrollbar {
  width: 4px;
}

.drop-body::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 10px;
}

/* EMPTY */
.drop-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 24px 0;
  color: #94a3b8;
}

.drop-empty svg {
  width: 32px;
  height: 32px;
}

.drop-empty p {
  font-size: 12.5px;
  font-weight: 500;
}

/* DROP ITEM */
.drop-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 12px;
  margin-bottom: 6px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: all 0.2s;
}

.drop-item:hover {
  background: rgba(37, 99, 235, 0.08);
  border-color: rgba(37, 99, 235, 0.25);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
}

.drop-item img {
  width: 46px;
  height: 46px;
  border-radius: 8px;
  object-fit: cover;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  flex-shrink: 0;
}

.drop-item-info {
  flex: 1;
  min-width: 0;
}

.di-name {
  font-size: 12px;
  font-weight: 700;
  color: #e2e8f0;
  margin-bottom: 2px;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.di-meta span {
  font-size: 9.5px;
  background: rgba(255, 255, 255, 0.07);
  color: #94a3b8;
  padding: 1px 6px;
  border-radius: 5px;
  font-weight: 600;
}

.di-price-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 4px;
}

.di-price {
  font-size: 13px;
  font-weight: 800;
  color: #60a5fa;
}

.di-qty {
  font-size: 10px;
  font-weight: 700;
  color: #60a5fa;
  background: rgba(37, 99, 235, 0.12);
  padding: 1px 6px;
  border-radius: 6px;
  border: 1px solid rgba(37, 99, 235, 0.2);
}

.di-remove {
  width: 24px;
  height: 24px;
  border-radius: 7px;
  border: none;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.15s;
}

.di-remove:hover {
  background: rgba(239, 68, 68, 0.15);
}

.di-remove svg {
  width: 11px;
  height: 11px;
  color: #ef4444;
}

/* CART TOTAL ROW */
.cart-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 2px;
  margin-bottom: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
}

.cart-total-val {
  font-family: 'Outfit', sans-serif;
  font-size: 17px;
  font-weight: 800;
  color: #60a5fa;
  letter-spacing: -0.3px;
}

/* DROP FOOTER */
.drop-foot {
  padding: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.01);
}

.drop-cta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  padding: 9px;
  border-radius: 10px;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  font-family: 'Outfit', sans-serif;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
  transition: all 0.22s;
}

.drop-cta svg {
  width: 14px;
  height: 14px;
}

.drop-cta:hover {
  transform: translateY(-2.0px);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
}

/* USER CARD */
.user-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 14px 12px;
  background: rgba(15, 23, 42, 0.42);
  border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}

.user-card-avatar {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  object-fit: cover;
  border: 2px solid rgba(96, 165, 250, 0.75);
  flex-shrink: 0;
  box-shadow: 0 8px 18px rgba(2, 6, 23, 0.35);
}

.uc-name {
  font-family: 'Outfit', sans-serif;
  font-size: 15px;
  font-weight: 900;
  color: #ffffff;
  margin-bottom: 2px;
  text-shadow: 0 1px 8px rgba(0, 0, 0, 0.35);
}

.uc-email {
  font-size: 11.5px;
  font-weight: 650;
  color: #cbd5e1;
  margin-bottom: 5px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 160px;
}

.uc-badge {
  font-size: 8.5px;
  font-weight: 900;
  letter-spacing: 0.8px;
  color: #dbeafe;
  background: rgba(37, 99, 235, 0.38);
  border: 1px solid rgba(96, 165, 250, 0.55);
  padding: 2px 7px;
  border-radius: 20px;
  text-transform: capitalize;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

/* USER MENU */
.user-menu {
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.um-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border-radius: 10px;
  font-family: 'Inter', sans-serif;
  font-size: 13.5px;
  font-weight: 800;
  color: #f8fafc;
  text-decoration: none;
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  width: 100%;
  transition: all 0.18s;
}

.um-left {
  display: flex;
  align-items: center;
  gap: 9px;
}

.um-left svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

.um-item>svg {
  width: 13px;
  height: 13px;
  color: #cbd5e1;
}

.um-item:hover {
  background: rgba(37, 99, 235, 0.42);
  border-color: rgba(96, 165, 250, 0.62);
  color: #ffffff;
}

.um-item:hover>svg {
  color: #bfdbfe;
}

.um-item.admin {
  background: rgba(99, 102, 241, 0.24);
  border-color: rgba(129, 140, 248, 0.28);
  color: #ede9fe;
}

.um-item.admin:hover {
  background: rgba(99, 102, 241, 0.42);
  border-color: rgba(167, 139, 250, 0.55);
  color: #ffffff;
}

.um-item.logout {
  color: #fecaca;
  background: rgba(239, 68, 68, 0.20);
  border-color: rgba(239, 68, 68, 0.34);
}

.um-item.logout:hover {
  background: rgba(220, 38, 38, 0.5);
  border-color: rgba(239, 68, 68, 0.7);
  color: #ffffff;
}

.um-divider {
  height: 1px;
  background: rgba(148, 163, 184, 0.18);
  margin: 4px 0;
}

/* DROPDOWN TRANSITION */
.drop-enter-active {
  transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.drop-leave-active {
  transition: all 0.16s ease;
}

.drop-enter-from {
  opacity: 0;
  transform: translateY(-10px) scale(0.97);
}

.drop-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* ============================= HAMBURGER ============================= */
.hamburger {
  grid-column: 5;
  justify-self: end;
  display: none;
  flex-direction: column;
  justify-content: space-between;
  width: 22px;
  height: 15px;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0;
  margin-left: 6px;
}

.hamburger span {
  width: 100%;
  height: 2px;
  background: #334155;
  border-radius: 2px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hamburger.open span:nth-child(1) {
  transform: translateY(6.5px) rotate(45deg);
}

.hamburger.open span:nth-child(2) {
  opacity: 0;
  transform: translateX(-10px);
}

.hamburger.open span:nth-child(3) {
  transform: translateY(-6.5px) rotate(-45deg);
}

/* ============================= MOBILE DRAWER ============================= */
.mob-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(6px);
  z-index: 9997;
}

.mob-drawer {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: min(320px, 90vw);
  background: #0d1b2e;
  z-index: 9998;
  display: flex;
  flex-direction: column;
  box-shadow: -16px 0 48px rgba(0, 0, 0, 0.5);
  overflow-y: auto;
}

.mob-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 20px 16px;
  border-bottom: 1px solid #f1f5f9;
  flex-shrink: 0;
}

.mob-logo img {
  width: 285px;
  max-width: 76vw;
  height: 66px;
  object-fit: contain;
  object-position: left center;
}

.mob-close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  background: #0d1b2e;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #475569;
  transition: all 0.2s;
}

.mob-close:hover {
  background: #fee2e2;
  border-color: #ef4444;
  color: #ef4444;
}

.mob-close svg {
  width: 15px;
  height: 15px;
}

.mob-search {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 16px 20px;
  background: #f1f5f9;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px 14px;
  flex-shrink: 0;
}

.mob-search svg {
  width: 15px;
  height: 15px;
  color: #94a3b8;
  flex-shrink: 0;
}

.mob-search input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 14px;
  flex: 1;
  color: #e2e8f0;
  font-family: 'Inter', sans-serif;
}

.mob-search input::placeholder {
  color: #475569;
}

.mob-nav {
  padding: 0 12px;
  flex: 1;
}

.mob-nav-label {
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 1.2px;
  text-transform: capitalize;
  color: #94a3b8;
  padding: 12px 8px 6px;
}

.mob-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 11px 12px;
  border-radius: 12px;
  font-family: 'Outfit', sans-serif;
  font-size: 14px;
  font-weight: 600;
  color: #cbd5e1;
  text-decoration: none;
  margin-bottom: 2px;
  transition: all 0.2s;
}

.mob-link svg {
  width: 14px;
  height: 14px;
  color: #94a3b8;
}

.mob-link:hover,
.mob-link.router-link-active,
.mob-link.router-link-exact-active,
.mob-link.current {
  background: transparent;
  color: #2563eb;
  font-weight: 700;
}

.mob-link.labs {
  color: #6366f1;
}

.mob-footer {
  padding: 16px 20px 24px;
  border-top: 1px solid #f1f5f9;
  flex-shrink: 0;
}

.mob-cta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 13px;
  border-radius: 14px;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: white;
  font-family: 'Outfit', sans-serif;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 6px 18px rgba(99, 102, 241, 0.25);
  transition: all 0.2s;
}

.mob-cta svg {
  width: 16px;
  height: 16px;
}

.mob-cta:hover {
  transform: translateY(-1px);
}

/* MOBILE TRANSITION */
.mob-slide-enter-active {
  transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1);
}

.mob-slide-leave-active {
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.mob-slide-enter-from,
.mob-slide-leave-to {
  transform: translateX(100%);
}

/* ============================= RESPONSIVE ============================= */
@media (max-width: 1120px) {
  .ann-container {
    width: calc(100% - 64px);
    padding: 0;
  }

  .header-inner {
    width: calc(100% - 64px);
    padding: 0;
    height: 72px;
    grid-template-columns: 200px minmax(280px, 1fr) auto auto;
    grid-template-rows: 72px;
    column-gap: 16px;
  }

  .header {
    height: 72px;
  }

  .logo-wrap,
  .search-container,
  .header-actions {
    grid-row: 1;
  }

  .mega-nav {
    display: none;
  }

  .search-container {
    position: relative;
    left: auto;
    top: auto;
    transform: none;
    width: 100%;
    grid-column: 2;
    justify-self: center;
    max-width: 480px;
  }

  .header-actions {
    grid-column: 3;
  }

  .header-contact {
    display: none;
  }

  .hamburger {
    grid-column: 4;
  }

  .hamburger {
    display: flex;
  }
}

@media (max-width: 900px) {
  .search-container {
    display: none;
  }

  .header-inner {
    grid-template-columns: auto 1fr auto;
    gap: 16px;
  }

  .logo-wrap {
    width: 200px;
    min-width: 200px;
  }

  .header-actions {
    grid-column: 2;
    justify-self: end;
  }

  .hamburger {
    grid-column: 3;
  }
}

@media (max-width: 600px) {
  .ann-bar {
    display: none;
  }

  .header {
    top: 0;
    height: 64px;
  }

  .header-inner {
    width: calc(100% - 32px);
    padding: 0;
    height: 64px;
  }

  .logo-wrap {
    width: 176px;
    min-width: 176px;
    height: 56px;
  }
}

@media (max-width: 400px) {
  .icon-action {
    width: 36px;
    height: 36px;
    border-radius: 10px;
  }

  .icon-action svg {
    width: 18px;
    height: 18px;
  }
}
</style>
