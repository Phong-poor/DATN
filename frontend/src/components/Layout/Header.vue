<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api' 
import { getUser, clearAuth, getToken } from '@/services/auth'
import { storageUrl } from '@/services/urls'
import { prefetchProductsPage } from '@/services/productsPrefetch'

const router = useRouter()
const route = useRoute()

const getSwal = async () => {
  const module = await import('@/services/swal')
  return module.default
}

const showWishlist = ref(false)
const showUser = ref(false)
const isMobileMenuOpen = ref(false)

// ===================== ANNOUNCEMENT BAR =====================
const announcements = [
  { icon: '🚚', text: 'Giao hàng nhanh trong <strong>2 giờ</strong> nội thành TP.HCM & Hà Nội' },
  { icon: '💳', text: 'Trả góp <strong>0%</strong> lãi suất — Duyệt trong 5 phút' },
  { icon: '🎁', text: 'Giảm đến <strong>20 triệu</strong> cho Gaming Laptop RTX 5090' },
  { icon: '🛡️', text: '<strong>Bảo hành chính hãng</strong> toàn quốc · Đổi trả 7 ngày miễn phí' },
]
const annIdx = ref(0)
let annTimer = null

// ===================== SCROLL BEHAVIOR =====================
const isScrolled = ref(false)
const handleScroll = () => { isScrolled.value = window.scrollY > 20 }

// ===================== MEGA MENU =====================
const activeMegaMenu = ref(null)
let megaLeaveTimer = null

const megaMenuData = {
  gaming: {
    label: 'Gaming',
    accent: '#ef4444',
    accentBg: 'rgba(239,68,68,0.07)',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01M8 10v4M6 12h4m6-2v4m0-2h2"/></svg>`,
    img: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=400&q=80',
    sections: [
      {
        title: 'Theo GPU', icon: '🎮',
        items: [
          { label: 'RTX 5090', badge: 'NEW', q: 'RTX 5090' },
          { label: 'RTX 5080', badge: '', q: 'RTX 5080' },
          { label: 'RTX 5070', badge: '', q: 'RTX 5070' },
          { label: 'RTX 4070', badge: 'HOT', q: 'RTX 4070' },
        ]
      },
      {
        title: 'Thương hiệu', icon: '🏷️',
        items: [
          { label: 'ASUS ROG', badge: '', q: 'ASUS ROG' },
          { label: 'MSI Titan', badge: '', q: 'MSI Titan' },
          { label: 'Lenovo Legion', badge: 'HOT', q: 'Lenovo Legion' },
          { label: 'Acer Predator', badge: '', q: 'Acer Predator' },
        ]
      },
      {
        title: 'Mức giá', icon: '💰',
        items: [
          { label: 'Dưới 20 triệu', badge: '', q: 'gaming duoi 20 trieu' },
          { label: '20M – 35M', badge: '', q: 'gaming 20-35 trieu' },
          { label: '35M – 60M', badge: '', q: 'gaming 35-60 trieu' },
          { label: 'Trên 60 triệu', badge: 'PRO', q: 'gaming flagship' },
        ]
      },
    ],
    featured: { name: 'ASUS ROG Zephyrus G16', price: '52.990.000₫', oldPrice: '59.990.000₫', tag: 'HOT', img: 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=200&q=80' },
    quickLinks: ['RTX 5090 Gaming', 'Gaming dưới 20 triệu', 'So sánh gaming laptop'],
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
    featured: { name: 'Dell XPS 15 Core Ultra 9', price: '48.990.000₫', oldPrice: '', tag: 'NEW', img: 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=200&q=80' },
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
    featured: { name: 'MacBook Pro 16" M4 Max', price: '89.990.000₫', oldPrice: '', tag: 'PRO', img: 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=200&q=80' },
    quickLinks: ['MacBook Air M4 giá rẻ', 'So sánh MacBook Pro', 'MacBook cho sinh viên'],
  },
  workstation: {
    label: 'Workstation',
    accent: '#8b5cf6',
    accentBg: 'rgba(139,92,246,0.07)',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/><path d="M7 8h10M7 12h6"/></svg>`,
    img: 'https://images.unsplash.com/photo-1585776245991-cf89dd7fc73a?w=400&q=80',
    sections: [
      {
        title: 'Brands', icon: '🏷️',
        items: [
          { label: 'ThinkPad', badge: 'HOT', q: 'ThinkPad' },
          { label: 'Dell', badge: '', q: 'Dell Precision' },
          { label: 'HP ZBook', badge: '', q: 'HP ZBook' },
          { label: 'ASUS ProArt', badge: 'NEW', q: 'ASUS ProArt' },
        ]
      },
      {
        title: 'Use Cases', icon: '🎯',
        items: [
          { label: 'CAD', badge: '', q: 'CAD workstation' },
          { label: 'AI Training', badge: 'PRO', q: 'AI workstation' },
          { label: 'Rendering', badge: 'HOT', q: 'rendering workstation' },
          { label: 'SolidWorks', badge: '', q: 'SolidWorks' },
        ]
      },
      {
        title: 'Specifications', icon: '⚙️',
        items: [
          { label: 'RTX 5000 Ada', badge: 'PRO', q: 'RTX 5000 Ada' },
          { label: 'RTX 4000 Ada', badge: '', q: 'RTX 4000 Ada' },
          { label: '64GB RAM', badge: '', q: 'workstation 64GB' },
          { label: '128GB RAM', badge: 'PRO', q: 'workstation 128GB' },
        ]
      },
    ],
    featured: { name: 'ASUS ProArt Studiobook 16', price: '65.990.000₫', oldPrice: '', tag: 'PRO', img: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=200&q=80' },
    quickLinks: ['Workstation cho render 3D', 'So sánh Workstation', 'Trả góp Workstation'],
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
    featured: { name: 'Acer Nitro V RTX 4060', price: '22.990.000₫', oldPrice: '30.990.000₫', tag: 'SALE -25%', img: 'https://images.unsplash.com/photo-1593642632623-9f5b9e3c4a0c?w=200&q=80' },
    quickLinks: ['Laptop dưới 15 triệu', 'Sinh viên ưu đãi', 'Flash Sale hôm nay'],
  },
}

const visibleMegaMenuData = computed(() => {
  const { aipc, ...menus } = megaMenuData
  return menus
})

const openMega = (key) => {
  clearTimeout(megaLeaveTimer)
  activeMegaMenu.value = key
}
const closeMega = () => {
  megaLeaveTimer = setTimeout(() => { activeMegaMenu.value = null }, 250)
}
const keepMega = () => { clearTimeout(megaLeaveTimer) }

const menuCategoryMap = {
  gaming: 'Laptop Gaming',
  macbook: 'MacBook',
  workstation: 'Workstation',
}

const navToCategory = (key) => {
  activeMegaMenu.value = null
  if (key === 'sale') {
    router.push('/khuyen-mai')
  } else if (key === 'gaming') {
    router.push({ path: '/laptop', query: { line: 'gaming' } })
  } else if (key === 'macbook') {
    router.push('/macbook')
  } else if (key === 'workstation') {
    router.push('/workstation')
  } else {
    router.push({ path: '/products', query: { category: menuCategoryMap[key] || key } })
  }
}

const navToMegaItem = (key, keyword) => {
  activeMegaMenu.value = null
  if (key === 'sale') {
    router.push({ path: '/khuyen-mai', query: keyword ? { q: keyword } : {} })
    return
  }
  if (key === 'gaming') {
    router.push({ path: '/laptop', query: keyword ? { line: 'gaming', q: keyword } : { line: 'gaming' } })
    return
  }
  if (key === 'macbook') {
    router.push({ path: '/macbook', query: keyword ? { q: keyword } : {} })
    return
  }
  router.push({ path: '/products', query: { category: menuCategoryMap[key] || key, q: keyword } })
}

const mobileMenuTarget = (key) => {
  if (key === 'sale') return '/khuyen-mai'
  if (key === 'gaming') return { path: '/laptop', query: { line: 'gaming' } }
  if (key === 'macbook') return '/macbook'
  if (key === 'workstation') return '/workstation'
  return { path: '/products', query: { category: menuCategoryMap[key] || key } }
}

const isMenuCurrent = (key) => {
  if (key === 'sale') return route.path === '/khuyen-mai'
  if (key === 'workstation') {
    return route.path === '/workstation' ||
      (route.path === '/products' && String(route.query.category || '').toLowerCase() === 'workstation')
  }

  if (key === 'gaming') return route.path === '/gaming' || (route.path === '/laptop' && String(route.query.line || '').toLowerCase() === 'gaming')
  if (key === 'macbook') return route.path === '/macbook' ||
    (route.path === '/products' && String(route.query.category || '').toLowerCase() === 'macbook')
  if (!['gaming', 'macbook'].includes(key)) return false
  const currentCategory = String(route.query.category || '').toLowerCase()
  return route.path === '/products' && currentCategory === String(menuCategoryMap[key] || key).toLowerCase()
}

// ===================== TÌM KIẾM =====================
const searchQuery = ref('')
const searchFocused = ref(false)

const handleSearch = () => {
  const keyword = searchQuery.value.trim()
  if (!keyword) return
  router.push({ path: '/products', query: { q: keyword } })
  searchQuery.value = ''
  isMobileMenuOpen.value = false
  searchFocused.value = false
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
  return imgPath ? storageUrl(imgPath) : 'https://via.placeholder.com/150'
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
    prefetchProductsPage().catch(() => {})
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
}

const user = ref(null)

const getUserRole = (account) => String(account?.vaitro || account?.role || '').toLowerCase()
const isAdminAccount = computed(() => {
  const role = getUserRole(user.value)
  return Boolean(role && role !== 'user')
})
const accountBadge = computed(() => isAdminAccount.value ? 'Quản trị hệ thống' : 'Predator Member')

const avatarUrl = computed(() => {
  if (!user.value || !user.value.avatar) return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.value?.name || 'User') + '&background=6366f1&color=fff&bold=true'
  if (user.value.avatar.startsWith('http')) return user.value.avatar
  return storageUrl(user.value.avatar)
})

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
  router.push('/login')
}

const warmProductsPageNow = () => {
  const connection = navigator.connection || navigator.webkitConnection || navigator.mozConnection
  if (connection?.saveData || ['slow-2g', '2g'].includes(connection?.effectiveType)) return
  import('../Web/TrangLaptop.vue')
  prefetchProductsPage().catch(() => {})
}
</script>

<template>
  <!-- ===== ANNOUNCEMENT BAR ===== -->
  <div class="ann-bar">
    <div class="ann-container">
      <transition name="ann-slide" mode="out-in">
        <span
          class="ann-text"
          :key="annIdx"
          v-html="announcements[annIdx].icon + ' ' + announcements[annIdx].text"
        ></span>
      </transition>
      <div class="ann-right">
        <a href="tel:18009999" class="ann-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.08 6.08l.95-.95a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          1800 9999
        </a>
        <span class="ann-sep">|</span>
        <span class="ann-link">🇻🇳 VNĐ</span>
      </div>
    </div>
  </div>

  <!-- ===== MAIN HEADER ===== -->
  <header class="header" :class="{ scrolled: isScrolled }">
    <div class="header-inner">

      <!-- LOGO -->
      <router-link to="/" class="logo-wrap" aria-label="Predator Group">
        <img src="/predator_group_logo_header.png" alt="Predator Group" class="logo-img" />
      </router-link>

      <!-- MEGA MENU NAVIGATION -->
      <nav class="mega-nav">
        <div
          v-for="(menu, key) in visibleMegaMenuData"
          :key="key"
          class="mega-nav-item"
          @mouseenter="openMega(key)"
          @mouseleave="closeMega"
        >
          <button
            class="nav-btn"
            :class="{ active: activeMegaMenu === key, current: isMenuCurrent(key) }"
            @click="navToCategory(key)"
          >
            {{ menu.label }}
            <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>

          <!-- MEGA DROPDOWN -->
          <transition name="mega-drop">
            <div
              v-if="activeMegaMenu === key"
              class="mega-dropdown"
              :style="{ '--accent': menu.accent, '--accent-bg': menu.accentBg }"
              @mouseenter="keepMega"
              @mouseleave="closeMega"
            >
              <!-- BODY -->
              <div class="mega-body">
                <!-- SECTIONS (Columns 1, 2, 3) -->
                <div v-for="(section, sIdx) in menu.sections" :key="section.title" class="mega-col" :class="`col-${sIdx + 1}`">
                  <div class="mega-col-title">
                    {{ section.title }}
                  </div>
                  <ul class="mega-list">
                    <li v-for="it in section.items" :key="it.label">
                      <a
                        href="#"
                        class="mega-link"
                        @click.prevent="navToMegaItem(key, it.q)"
                      >
                        <span class="mega-link-text">{{ it.label }}</span>
                        <span v-if="it.badge" class="mega-item-badge" :class="it.badge.toLowerCase()">{{ it.badge }}</span>
                      </a>
                    </li>
                  </ul>
                </div>

                <!-- FEATURED PANEL (Column 4) -->
                <div class="mega-featured-panel">
                  <div class="mega-col-title">NỔI BẬT</div>
                  <div class="mfp-card" @click="navToMegaItem(key, menu.featured.name)">
                    <div class="mfp-img-box">
                      <img :src="menu.featured.img" :alt="menu.featured.name" class="mfp-img"
                        @error="e => e.target.style.display='none'" />
                      <span class="mfp-badge" :style="{ background: menu.accent }">{{ menu.featured.tag || 'PRO' }}</span>
                    </div>
                    <div class="mfp-info">
                      <div class="mfp-name">{{ menu.featured.name }}</div>
                      <div class="mfp-price" :style="{ color: menu.accent }">{{ menu.featured.price }}</div>
                      <button
                        class="mfp-btn"
                        :style="{ background: menu.accent }"
                      >
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
        <router-link to="/news" class="nav-plain-link" @mouseenter="warmProductsPageNow">Tin tức</router-link>
        <router-link to="/contact" class="nav-plain-link">Liên hệ</router-link>
      </nav>

      <!-- SEARCH BAR -->
      <div class="search-wrap" :class="{ focused: searchFocused }">
        <input
          type="text"
          class="search-input"
          placeholder="Tìm kiếm"
          v-model="searchQuery"
          @keyup.enter="handleSearch"
          @focus="searchFocused = true"
          @blur="searchFocused = false"
        />
        <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''; searchFocused = false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <span class="search-keyboard" aria-hidden="true"></span>
        <button type="button" class="search-submit" @click="handleSearch" aria-label="Tim kiem" title="Tim kiem">
          Tìm
        </button>
      </div>

      <!-- HEADER ACTIONS -->
      <div class="header-actions">

        <!-- WISHLIST -->
        <div class="dropdown-wrap" @mouseenter="showWishlist = true" @mouseleave="showWishlist = false">
          <button class="icon-action" :class="{ active: showWishlist }" @click.stop="toggleWishlist" title="Yêu thích">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
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
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                  <p>Chưa có sản phẩm yêu thích</p>
                </div>
                <div class="drop-item" v-for="item in wishlistItems" :key="item.id">
                  <img :src="getWishlistImg(item)" :alt="item.bienthe?.sanpham?.tenSP" />
                  <div class="drop-item-info">
                    <p class="di-name">{{ item.bienthe?.sanpham?.tenSP || 'Sản phẩm' }}</p>
                    <p class="di-price">{{ formatPrice(item.bienthe?.gia) }}</p>
                  </div>
                  <button class="di-remove" @click="removeWishlist(item.id)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                </div>
              </div>
              <div class="drop-foot" v-if="wishlistItems.length > 0">
                <router-link to="/wishlistpage" class="drop-cta" @click="showWishlist = false">Xem tất cả →</router-link>
              </div>
            </div>
          </transition>
        </div>

        <!-- CART -->
        <div class="dropdown-wrap" @mouseenter="showCartDropdown = true" @mouseleave="showCartDropdown = false">
          <button class="icon-action cart-action" :class="{ active: showCartDropdown }" @click="goToCart" title="Giỏ hàng">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6h15l-1.5 9h-13z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
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
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 6h15l-1.5 9h-13z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                  <p>Giỏ hàng của bạn đang trống</p>
                </div>
                <div class="drop-item" v-for="item in cartItems" :key="item.id_giohang">
                  <img :src="item.hinh_anh || 'https://via.placeholder.com/60'" :alt="item.ten_san_pham" />
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
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                  Xem giỏ hàng
                </router-link>
              </div>
            </div>
          </transition>
        </div>

        <!-- USER -->
        <div class="dropdown-wrap">
          <button class="icon-action user-action" :class="{ active: showUser }" @click.stop="toggleUser" title="Tài khoản">
            <img v-if="user" :src="avatarUrl" class="user-avatar" :alt="user.name" />
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </button>

          <transition name="drop">
            <div class="hdr-dropdown user-drop" v-if="showUser">
              <div class="user-card">
                <img :src="avatarUrl" :alt="user?.name" class="user-card-avatar" />
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
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                      Quản trị hệ thống
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                  </button>
                </template>
                <template v-else>
                  <router-link to="/profile" @click="showUser = false" class="um-item">
                    <span class="um-left">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                      Thông tin cá nhân
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                  </router-link>

                  <router-link to="/affiliate" @click="showUser = false" class="um-item">
                    <span class="um-left">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                      Affiliate Center
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                  </router-link>
                </template>
                <div class="um-divider"></div>
                <button class="um-item logout" @click="handleLogout">
                  <span class="um-left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Đăng xuất
                  </span>
                </button>
              </div>
            </div>
          </transition>
        </div>

        <!-- HAMBURGER (mobile) -->
        <button class="hamburger" :class="{ open: isMobileMenuOpen }" @click="isMobileMenuOpen = !isMobileMenuOpen" aria-label="Menu">
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
          <img src="/predator_group_logo_header.png" alt="Predator Group" />
        </router-link>
        <button class="mob-close" @click="isMobileMenuOpen = false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="mob-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input
          type="text"
          placeholder="Tìm kiếm sản phẩm..."
          v-model="searchQuery"
          @keyup.enter="handleSearch"
        />
      </div>

      <nav class="mob-nav">
        <div class="mob-nav-label">Danh mục</div>
        <router-link v-for="(menu, key) in megaMenuData" :key="key"
          :to="mobileMenuTarget(key)" @click="isMobileMenuOpen = false" class="mob-link" :class="{ current: isMenuCurrent(key) }">
          {{ menu.label }}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </router-link>
        <div class="mob-nav-label">Thêm</div>
        <router-link to="/news" @click="isMobileMenuOpen = false" class="mob-link">Tin tức</router-link>
        <router-link to="/contact" @click="isMobileMenuOpen = false" class="mob-link">Liên hệ</router-link>
      </nav>

      <div class="mob-footer">
        <router-link to="/cart" @click="isMobileMenuOpen = false" class="mob-cta">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-1.5 9h-13z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
          Giỏ hàng {{ cartCount > 0 ? '(' + cartCount + ')' : '' }}
        </router-link>
      </div>
    </div>
  </transition>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ============================= ANNOUNCEMENT BAR ============================= */
.ann-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1001;
  background: #0f172a;
  height: 34px;
  display: flex;
  align-items: center;
  overflow: hidden;
}
.ann-container {
  max-width: none;
  width: 100%;
  margin: auto;
  padding: 0 100px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.ann-text {
  font-family: 'Inter', sans-serif;
  font-size: 12.5px;
  color: #94a3b8;
  letter-spacing: 0.1px;
}
.ann-text :deep(strong) { color: #e2e8f0; font-weight: 600; }
.ann-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.ann-link {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 12px; color: #94a3b8; text-decoration: none; cursor: pointer;
  transition: color 0.2s; font-family: 'Inter', sans-serif;
}
.ann-link svg { width: 12px; height: 12px; }
.ann-link:hover { color: #e2e8f0; }
.ann-sep { color: #cbd5e1; font-size: 12px; }

.ann-slide-enter-active { transition: all 0.4s cubic-bezier(0.4,0,0.2,1); }
.ann-slide-leave-active { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
.ann-slide-enter-from { opacity: 0; transform: translateY(10px); }
.ann-slide-leave-to   { opacity: 0; transform: translateY(-10px); }

/* ============================= MAIN HEADER ============================= */
.header {
  position: fixed;
  top: 34px;
  left: 0;
  right: 0;
  z-index: 1000;
  background: rgba(13, 27, 46, 0.95);
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.header.scrolled {
  background: rgba(13, 27, 46, 0.99);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.35), 0 1px 4px rgba(0, 0, 0, 0.2);
  border-bottom-color: rgba(37, 99, 235, 0.2);
}

.header-inner {
  width: 100%;
  margin: 0 auto;
  padding: 0 100px;
  height: 68px;
  display: grid;
  grid-template-columns: minmax(270px, 320px) minmax(max-content, 1fr) minmax(260px, 330px) auto;
  align-items: center;
  gap: clamp(10px, 1.05vw, 18px);
}

/* LOGO */
.logo-wrap {
  grid-column: 1;
  justify-self: start;
  display: flex;
  align-items: center;
  width: clamp(255px, 16.5vw, 285px);
  height: 66px;
  text-decoration: none;
  flex-shrink: 0;
  overflow: visible;
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.logo-wrap:hover { transform: scale(1.04); }
.logo-img {
  width: 100%;
  height: 100%;
  max-height: 66px;
  object-fit: contain;
  object-position: left center;
  filter: drop-shadow(0 3px 8px rgba(0,0,0,0.25));
}

/* ============================= MEGA NAV ============================= */
.mega-nav {
  grid-column: 2;
  display: flex;
  align-items: center;
  gap: clamp(4px, 0.65vw, 12px);
  justify-content: center;
  min-width: 0;
}

.mega-nav-item { position: relative; }

.nav-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 8px clamp(8px, 0.65vw, 12px);
  border: none;
  background: transparent;
  font-family: 'Outfit', sans-serif;
  font-size: 13.5px;
  font-weight: 600;
  color: #cbd5e1;
  cursor: pointer;
  border-radius: 10px;
  transition: all 0.2s;
  white-space: nowrap;
}
.nav-btn:hover, .nav-btn.active {
  background: transparent;
  color: #60a5fa;
}
.nav-btn.current {
  background: transparent;
  color: #60a5fa;
  font-weight: 700;
}
.nav-chevron {
  width: 12px; height: 12px;
  transition: transform 0.2s;
  stroke: currentColor;
}
.nav-btn.active .nav-chevron { transform: rotate(180deg); }

.nav-plain-link {
  padding: 8px 10px;
  font-family: 'Outfit', sans-serif;
  font-size: 13.5px;
  font-weight: 600;
  color: #cbd5e1;
  text-decoration: none;
  border-radius: 10px;
  transition: all 0.2s;
  white-space: nowrap;
  position: relative;
}
.nav-plain-link:hover { background: transparent; color: #60a5fa; }

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
  background: rgba(8, 17, 30, 0.96);
  backdrop-filter: blur(30px) saturate(190%);
  -webkit-backdrop-filter: blur(30px) saturate(190%);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 20px;
  box-shadow: 
    0 24px 50px rgba(0, 0, 0, 0.55), 
    0 8px 24px rgba(0, 0, 0, 0.35),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  z-index: 9999;
  width: 860px;
  overflow: hidden;
  padding: 24px 28px;
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
  grid-template-columns: 1fr 1fr 1fr 1.35fr;
  gap: 24px;
  background: transparent;
}

.mega-col {
  padding: 0;
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
  display: none; /* Hide before featured panel */
}

.mega-col-title {
  font-family: 'Outfit', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: #94a3b8;
  margin-bottom: 14px;
  padding-left: 8px;
}

/* COMPACT CLEAN LINKS */
.mega-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.mega-link {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 8px;
  padding: 8px 12px;
  background: transparent;
  border-radius: 8px;
  text-decoration: none;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.mega-link-text {
  font-size: 13.5px;
  font-weight: 500;
  color: #cbd5e1;
  transition: all 0.2s ease;
}
.mega-link:hover {
  background: var(--accent-bg);
  transform: translateX(4px);
}
.mega-link:hover .mega-link-text {
  color: var(--accent);
  font-weight: 600;
}

/* ITEM BADGES */
.mega-item-badge {
  font-size: 9px;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 6px;
  letter-spacing: 0.5px;
  flex-shrink: 0;
  margin-left: 2px;
  text-transform: uppercase;
}
.mega-item-badge.hot  { background: rgba(239,68,68,0.18); color: #f87171; border: 1px solid rgba(239,68,68,0.15); }
.mega-item-badge.new  { background: rgba(34,197,94,0.18); color: #4ade80; border: 1px solid rgba(34,197,94,0.15); }
.mega-item-badge.pro  { background: rgba(139,92,246,0.18); color: #a78bfa; border: 1px solid rgba(139,92,246,0.15); }
.mega-item-badge.sale { background: rgba(249,115,22,0.18); color: #fb923c; border: 1px solid rgba(249,115,22,0.15); }

.mega-featured-panel {
  padding: 0;
  display: flex;
  flex-direction: column;
}
.mega-featured-panel .mega-col-title {
  padding-left: 0;
}

.mfp-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.mfp-card:hover {
  transform: translateY(-4px);
  background: rgba(255, 255, 255, 0.06);
  border-color: var(--accent);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4), 0 0 15px var(--accent-bg);
}

.mfp-img-box {
  position: relative;
  background: rgba(255, 255, 255, 0.04);
  border-radius: 10px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px;
  border: 1px solid rgba(255, 255, 255, 0.04);
  overflow: hidden;
}
.mfp-img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.mfp-card:hover .mfp-img {
  transform: scale(1.08);
}

.mfp-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  font-size: 8.5px;
  font-weight: 800;
  color: white;
  padding: 2.5px 7px;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
}

.mfp-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.mfp-name {
  font-size: 13px;
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
  font-size: 13.5px;
  font-weight: 800;
  letter-spacing: 0.2px;
}

.mfp-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 8px 12px;
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
.mega-drop-enter-active { transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1); }
.mega-drop-leave-active { transition: all 0.18s ease; }
.mega-drop-enter-from { opacity: 0; transform: translateX(-50%) translateY(-12px) scale(0.96); }
.mega-drop-leave-to   { opacity: 0; transform: translateX(-50%) translateY(-8px); }

/* ============================= SEARCH ============================= */
.search-wrap {
  grid-column: 3;
  width: 100%;
  max-width: 330px;
  justify-self: end;
  display: flex;
  align-items: center;
  gap: 0;
  background: #0f0f10;
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 99px;
  padding: 0 0 0 16px;
  height: 40px;
  overflow: hidden;
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,0.035),
    0 1px 2px rgba(0,0,0,0.35);
  transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
}
.search-wrap.focused {
  border-color: rgba(255, 255, 255, 0.12);
  background: #101011;
  box-shadow:
    0 0 0 2px rgba(255,255,255,0.035),
    inset 0 1px 0 rgba(255,255,255,0.04);
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
.search-input::placeholder { color: #68686b !important; opacity: 1; }
.search-clear {
  width: 22px; height: 22px;
  border: none; background: rgba(255,255,255,0.08); border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0; margin-right: 8px;
  transition: background 0.2s;
}
.search-clear:hover { background: rgba(239,68,68,0.15); }
.search-clear svg { width: 12px; height: 12px; color: #94a3b8; }
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
  border-left: 1px solid rgba(255,255,255,0.035);
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
  grid-column: 4;
  justify-self: end;
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.icon-action {
  position: relative;
  width: 40px; height: 40px;
  border-radius: 12px;
  border: 1.5px solid transparent;
  background: transparent;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  color: #94a3b8;
  transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
}
.icon-action svg { width: 20px; height: 20px; }
.icon-action:hover { background: rgba(37,99,235,0.12); border-color: rgba(37,99,235,0.2); color: #60a5fa; transform: translateY(-1px); }
.icon-action.active { background: rgba(37,99,235,0.15); border-color: rgba(37,99,235,0.25); color: #60a5fa; }

.cart-action {
  background: transparent;
  color: #94a3b8;
  border: 1.5px solid transparent;
}
.cart-action svg { stroke: currentColor; }
.cart-action:hover { background: rgba(37,99,235,0.12); border-color: rgba(37,99,235,0.2); color: #60a5fa; transform: translateY(-1px); }
.cart-action.active { background: rgba(37,99,235,0.15); border-color: rgba(37,99,235,0.25); color: #60a5fa; }

.user-action { padding: 0; overflow: hidden; border: 1.5px solid rgba(255,255,255,0.1); border-radius: 50%; }
.user-action.active { border-color: #2563eb; }
.user-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }

.action-badge {
  position: absolute; top: -5px; right: -5px;
  min-width: 18px; height: 18px; padding: 0 4px;
  color: white; font-size: 10px; font-weight: 800;
  border-radius: 999px; display: flex; align-items: center; justify-content: center;
  border: 2px solid #0d1b2e; z-index: 2;
}
.action-badge.red  { background: #ef4444; }
.action-badge.blue { background: #2563eb; }

/* ============================= DROPDOWNS ============================= */
.dropdown-wrap { position: relative; }

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
  position: absolute; top: -7px; right: 14px;
  width: 13px; height: 13px;
  background: rgba(8, 18, 32, 0.92);
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-bottom: none; border-right: none;
  transform: rotate(45deg);
}

.wishlist-drop { width: 330px; }
.cart-drop    { width: 330px; }
.user-drop    { width: 280px; min-width: 260px; }

/* DROP HEADER */
.drop-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 16px 10px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.drop-ttl { font-family: 'Outfit', sans-serif; font-size: 14.5px; font-weight: 800; color: #f1f5f9; }
.drop-cnt {
  font-size: 10.5px; font-weight: 700; color: #60a5fa;
  background: rgba(37,99,235,0.15); padding: 2px 8px; border-radius: 20px;
  border: 1px solid rgba(37,99,235,0.2);
}

/* DROP BODY */
.drop-body {
  max-height: 280px;
  overflow-y: auto;
  padding: 8px;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,0.1) transparent;
}
.drop-body::-webkit-scrollbar { width: 4px; }
.drop-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

/* EMPTY */
.drop-empty {
  display: flex; flex-direction: column; align-items: center;
  gap: 8px; padding: 24px 0; color: #94a3b8;
}
.drop-empty svg { width: 32px; height: 32px; }
.drop-empty p { font-size: 12.5px; font-weight: 500; }

/* DROP ITEM */
.drop-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px;
  border-radius: 12px;
  margin-bottom: 6px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  transition: all 0.2s;
}
.drop-item:hover { 
  background: rgba(37,99,235,0.08); 
  border-color: rgba(37,99,235,0.25); 
  transform: translateY(-1px); 
  box-shadow: 0 4px 12px rgba(37,99,235,0.12); 
}
.drop-item img { 
  width: 46px; 
  height: 46px; 
  border-radius: 8px; 
  object-fit: cover; 
  background: rgba(255,255,255,0.05); 
  border: 1px solid rgba(255,255,255,0.08); 
  flex-shrink: 0; 
}
.drop-item-info { flex: 1; min-width: 0; }
.di-name { font-size: 12px; font-weight: 700; color: #e2e8f0; margin-bottom: 2px; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.di-meta span { font-size: 9.5px; background: rgba(255,255,255,0.07); color: #94a3b8; padding: 1px 6px; border-radius: 5px; font-weight: 600; }
.di-price-row { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
.di-price { font-size: 13px; font-weight: 800; color: #60a5fa; }
.di-qty { 
  font-size: 10px; 
  font-weight: 700; 
  color: #60a5fa; 
  background: rgba(37,99,235,0.12); 
  padding: 1px 6px; 
  border-radius: 6px; 
  border: 1px solid rgba(37,99,235,0.2); 
}
.di-remove {
  width: 24px; height: 24px; border-radius: 7px;
  border: none; background: transparent;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; flex-shrink: 0; transition: background 0.15s;
}
.di-remove:hover { background: rgba(239,68,68,0.15); }
.di-remove svg { width: 11px; height: 11px; color: #ef4444; }

/* CART TOTAL ROW */
.cart-total-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0 2px; margin-bottom: 10px; font-size: 13px; font-weight: 600; color: #94a3b8;
}
.cart-total-val { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 800; color: #60a5fa; letter-spacing: -0.3px; }

/* DROP FOOTER */
.drop-foot {
  padding: 12px;
  border-top: 1px solid rgba(255,255,255,0.06);
  background: rgba(255,255,255,0.01);
}
.drop-cta {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  width: 100%; padding: 9px;
  border-radius: 10px;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white; font-family: 'Outfit', sans-serif;
  font-size: 13px; font-weight: 700; text-decoration: none;
  box-shadow: 0 4px 14px rgba(37,99,235,0.25);
  transition: all 0.22s;
}
.drop-cta svg { width: 14px; height: 14px; }
.drop-cta:hover { transform: translateY(-2.0px); box-shadow: 0 8px 20px rgba(37,99,235,0.4); }

/* USER CARD */
.user-card {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 14px 12px;
  background: rgba(15, 23, 42, 0.42);
  border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}
.user-card-avatar { width: 42px; height: 42px; border-radius: 10px; object-fit: cover; border: 2px solid rgba(96,165,250,0.75); flex-shrink: 0; box-shadow: 0 8px 18px rgba(2, 6, 23, 0.35); }
.uc-name { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 900; color: #ffffff; margin-bottom: 2px; text-shadow: 0 1px 8px rgba(0,0,0,0.35); }
.uc-email { font-size: 11.5px; font-weight: 650; color: #cbd5e1; margin-bottom: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 160px; }
.uc-badge { font-size: 8.5px; font-weight: 900; letter-spacing: 0.8px; color: #dbeafe; background: rgba(37,99,235,0.38); border: 1px solid rgba(96,165,250,0.55); padding: 2px 7px; border-radius: 20px; text-transform: uppercase; box-shadow: inset 0 1px 0 rgba(255,255,255,0.12); }

/* USER MENU */
.user-menu { padding: 8px; display: flex; flex-direction: column; gap: 3px; }
.um-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 12px; border-radius: 10px;
  font-family: 'Inter', sans-serif; font-size: 13.5px; font-weight: 800; color: #f8fafc;
  text-decoration: none; background: transparent;
  border: 1px solid transparent; cursor: pointer; width: 100%;
  transition: all 0.18s;
}
.um-left { display: flex; align-items: center; gap: 9px; }
.um-left svg { width: 15px; height: 15px; flex-shrink: 0; }
.um-item > svg { width: 13px; height: 13px; color: #cbd5e1; }
.um-item:hover { background: rgba(37,99,235,0.42); border-color: rgba(96,165,250,0.62); color: #ffffff; }
.um-item:hover > svg { color: #bfdbfe; }
.um-item.admin { background: rgba(99,102,241,0.24); border-color: rgba(129,140,248,0.28); color: #ede9fe; }
.um-item.admin:hover { background: rgba(99,102,241,0.42); border-color: rgba(167,139,250,0.55); color: #ffffff; }
.um-item.logout { color: #fecaca; background: rgba(239,68,68,0.20); border-color: rgba(239,68,68,0.34); }
.um-item.logout:hover { background: rgba(220,38,38,0.5); border-color: rgba(239,68,68,0.7); color: #ffffff; }
.um-divider { height: 1px; background: rgba(148,163,184,0.18); margin: 4px 0; }

/* DROPDOWN TRANSITION */
.drop-enter-active { transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1); }
.drop-leave-active { transition: all 0.16s ease; }
.drop-enter-from  { opacity: 0; transform: translateY(-10px) scale(0.97); }
.drop-leave-to    { opacity: 0; transform: translateY(-6px); }

/* ============================= HAMBURGER ============================= */
.hamburger {
  grid-column: 5;
  justify-self: end;
  display: none;
  flex-direction: column; justify-content: space-between;
  width: 22px; height: 15px;
  background: transparent; border: none; cursor: pointer; padding: 0; margin-left: 6px;
}
.hamburger span {
  width: 100%; height: 2px; background: #334155; border-radius: 2px;
  transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
}
.hamburger.open span:nth-child(1) { transform: translateY(6.5px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; transform: translateX(-10px); }
.hamburger.open span:nth-child(3) { transform: translateY(-6.5px) rotate(-45deg); }

/* ============================= MOBILE DRAWER ============================= */
.mob-overlay {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(6px);
  z-index: 9997;
}
.mob-drawer {
  position: fixed; top: 0; right: 0; bottom: 0;
  width: min(320px, 90vw);
  background: #0d1b2e;
  z-index: 9998;
  display: flex; flex-direction: column;
  box-shadow: -16px 0 48px rgba(0,0,0,0.5);
  overflow-y: auto;
}

.mob-head {
  display: flex; align-items: center; justify-content: space-between;
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
  width: 36px; height: 36px; border-radius: 10px;
  border: 1.5px solid #e2e8f0; background: #0d1b2e;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #475569; transition: all 0.2s;
}
.mob-close:hover { background: #fee2e2; border-color: #ef4444; color: #ef4444; }
.mob-close svg { width: 15px; height: 15px; }

.mob-search {
  display: flex; align-items: center; gap: 10px;
  margin: 16px 20px;
  background: #f1f5f9; border: 1.5px solid #e2e8f0; border-radius: 12px;
  padding: 10px 14px; flex-shrink: 0;
}
.mob-search svg { width: 15px; height: 15px; color: #94a3b8; flex-shrink: 0; }
.mob-search input { border: none; background: transparent; outline: none; font-size: 14px; flex: 1; color: #e2e8f0; font-family: 'Inter', sans-serif; }
.mob-search input::placeholder { color: #475569; }

.mob-nav { padding: 0 12px; flex: 1; }
.mob-nav-label {
  font-size: 9.5px; font-weight: 800; letter-spacing: 1.2px;
  text-transform: uppercase; color: #94a3b8;
  padding: 12px 8px 6px;
}
.mob-link {
  display: flex; align-items: center; justify-content: space-between;
  padding: 11px 12px; border-radius: 12px;
  font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 600; color: #cbd5e1;
  text-decoration: none; margin-bottom: 2px; transition: all 0.2s;
}
.mob-link svg { width: 14px; height: 14px; color: #94a3b8; }
.mob-link:hover,
.mob-link.router-link-active,
.mob-link.router-link-exact-active,
.mob-link.current {
  background: transparent;
  color: #2563eb;
  font-weight: 700;
}
.mob-link.labs { color: #6366f1; }

.mob-footer {
  padding: 16px 20px 24px;
  border-top: 1px solid #f1f5f9;
  flex-shrink: 0;
}
.mob-cta {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 13px; border-radius: 14px;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: white; font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700;
  text-decoration: none; box-shadow: 0 6px 18px rgba(99,102,241,0.25);
  transition: all 0.2s;
}
.mob-cta svg { width: 16px; height: 16px; }
.mob-cta:hover { transform: translateY(-1px); }

/* MOBILE TRANSITION */
.mob-slide-enter-active { transition: transform 0.32s cubic-bezier(0.4,0,0.2,1); }
.mob-slide-leave-active { transition: transform 0.25s cubic-bezier(0.4,0,0.2,1); }
.mob-slide-enter-from, .mob-slide-leave-to { transform: translateX(100%); }

/* ============================= RESPONSIVE ============================= */
@media (max-width: 1200px) {
  .ann-container {
    padding: 0 100px;
  }
  .header-inner {
    padding: 0 100px;
    grid-template-columns: auto minmax(220px, 1fr) auto auto;
  }
  .mega-nav { display: none; }
  .search-wrap {
    grid-column: 2;
    justify-self: center;
  }
  .header-actions { grid-column: 3; }
  .hamburger { grid-column: 4; }
  .hamburger { display: flex; }
  .search-wrap { max-width: 260px; }
}

@media (max-width: 900px) {
  .search-wrap { display: none; }
  .header-inner {
    grid-template-columns: auto 1fr auto;
    gap: 12px;
  }
  .header-actions {
    grid-column: 2;
    justify-self: end;
  }
  .hamburger { grid-column: 3; }
}

@media (max-width: 600px) {
  .ann-bar { display: none; }
  .header {
    top: 0;
  }
  .header-inner {
    width: calc(100% - 32px);
    padding: 0;
    height: 60px;
  }
}

@media (max-width: 400px) {
  .icon-action { width: 36px; height: 36px; border-radius: 10px; }
  .icon-action svg { width: 18px; height: 18px; }
}
</style>
