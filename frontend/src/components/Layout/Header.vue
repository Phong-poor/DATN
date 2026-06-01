<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api' 
import { getUser, clearAuth, getToken } from '@/services/auth'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'
import { prefetchProductsPage } from '@/services/productsPrefetch'

const router = useRouter()

const showWishlist = ref(false)
const showUser = ref(false)
const isMobileMenuOpen = ref(false)

// ===================== TÌM KIẾM =====================
const searchQuery = ref('')

const handleSearch = () => {
  const keyword = searchQuery.value.trim()
  if (!keyword) return
  // Chuyển sang trang sản phẩm với query tìm kiếm
  router.push({ path: '/products', query: { q: keyword } })
  searchQuery.value = ''
  isMobileMenuOpen.value = false
}

// ===================== GIỎ HÀNG BADGE =====================
const cartCount = ref(0)
const cartItems = ref([])
const cartTotal = ref(0)
const showCartDropdown = ref(false)

const fetchCart = async () => {
  try {
    const token = getToken()
    if (!token) {
      cartCount.value = 0
      cartItems.value = []
      cartTotal.value = 0
      return
    }

    const res = await api.get('/gio-hang', { skipGlobalLoader: true })
    if (res.data?.success) {
      cartItems.value = res.data.gio_hang || []
      
      const comboGroups = new Set()
      let count = 0
      cartItems.value.forEach(item => {
        if (item.id_combo && item.combo_group_id) {
            if (!comboGroups.has(item.combo_group_id)) {
                comboGroups.add(item.combo_group_id)
                count++
            }
        } else {
            count++
        }
      })
      cartCount.value = count
      
      cartTotal.value = res.data.tong_tien || 0
    }
  } catch {
    cartCount.value = 0
    cartItems.value = []
    cartTotal.value = 0
  }
}

// ===================== WISHLIST (DỮ LIỆU THẬT) =====================
const wishlistItems = ref([])

const fetchWishlist = async () => {
  try {
  const token = getToken()
    if (!token) { wishlistItems.value = []; return }

    const res = await api.get('/yeu-thich', { skipGlobalLoader: true })
    wishlistItems.value = res.data.data || res.data || []
  } catch {
    wishlistItems.value = []
  }
}

const removeWishlist = async (id) => {
  try {
    await api.delete(`/yeu-thich/xoa/${id}`)
    wishlistItems.value = wishlistItems.value.filter(i => i.id !== id)
    window.dispatchEvent(new Event('wishlist-updated'))
  } catch (err) {
    console.error('Lỗi khi xóa khỏi yêu thích', err)
  }
}

const formatPrice = (value) => {
    if(!value) return '0₫'
    return parseInt(value).toLocaleString('vi-VN') + '₫'
}

const getWishlistImg = (item) => {
    const imgPath = item.bienthe?.hinhanh || item.bienthe?.sanpham?.hinhanh
    return imgPath ? storageUrl(imgPath) : 'https://via.placeholder.com/150'
}

const handleCartUpdated = () => { fetchCart() }
const handleWishlistUpdated = () => { fetchWishlist() }

onMounted(() => {
  fetchUser()
  if (!getToken()) {
    cartCount.value = 0
    cartItems.value = []
    cartTotal.value = 0
    wishlistItems.value = []
  }

  window.addEventListener('cart-updated', handleCartUpdated)
  window.addEventListener('wishlist-updated', handleWishlistUpdated)
  window.addEventListener('user-updated', fetchUser)

  const warmProductsPage = () => {
    import('../Web/Producpage.vue')
    prefetchProductsPage().catch(() => {})
  }
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(warmProductsPage, { timeout: 1200 })
  } else {
    setTimeout(warmProductsPage, 700)
  }

  document.addEventListener('click', handleOutside)
})

onUnmounted(() => {
  window.removeEventListener('cart-updated', handleCartUpdated)
  window.removeEventListener('wishlist-updated', handleWishlistUpdated)
  window.removeEventListener('user-updated', fetchUser)
  document.removeEventListener('click', handleOutside)
})

const goToCart = () => {
  const token = getToken()
  if (!token) {
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
  if (!e.target.closest('.dropdown-wrap')) {
    showWishlist.value = false
    showUser.value = false
  }
}

const user = ref(null)

const avatarUrl = computed(() => {
  if (!user.value || !user.value.avatar) return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.value?.name || 'User')
  if (user.value.avatar.startsWith('http')) return user.value.avatar
  return storageUrl(user.value.avatar)
})

const fetchUser = () => {
    user.value = getUser()
    if (user.value) {
        fetchCart()
        fetchWishlist()
    }
}

const handleLogout = async () => {
  const isConfirmed = await swal.confirm(
    'Xác nhận đăng xuất',
    'Bạn có chắc chắn muốn thoát khỏi hệ thống?'
  )
  if (!isConfirmed) return

  showUser.value = false
  try {
    await api.post('/logout')
  } catch {
    console.log('Logout API lỗi (bỏ qua)')
  }
  clearAuth()
  localStorage.removeItem('remember_email')
  cartCount.value = 0
  wishlistItems.value = []
  router.push('/login')
}

const warmProductsPageNow = () => {
  import('../Web/Producpage.vue')
  prefetchProductsPage().catch(() => {})
}
</script>

<template>
  <div class="topbar">
    <div class="topbar-container">
      <span class="topbar-message">
        Chào mừng đến với <strong>NextGen Laptop 2026</strong> — Giao hàng nhanh chỉ trong 2 giờ nội thành
      </span>
      <div class="topbar-right">
        <span class="topbar-item">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.08 6.08l.95-.95a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z" />
          </svg>
          1800 9999
        </span>
        <span class="topbar-divider">|</span>
        <span class="topbar-item">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" />
            <path d="M12 2a14.5 14.5 0 0 1 0 20A14.5 14.5 0 0 1 12 2" />
            <path d="M2 12h20" />
          </svg>
          Việt Nam | VNĐ
        </span>
      </div>
    </div>
  </div>

  <header class="header">
    <div class="container">

      <div class="logo logo-3d">
        <div class="logo-inner">
          <span class="logo-black">NextGen</span>
          <span class="logo-blue">Laptop</span>
        </div>
      </div>

      <nav class="nav">
        <router-link to="/" :exact="true">Trang chủ</router-link>
        <router-link to="/products" @mouseenter="warmProductsPageNow" @focus="warmProductsPageNow">Sản phẩm</router-link>
        <router-link to="/news">Tin tức</router-link>
        <router-link to="/contact">Liên hệ</router-link>
        <router-link to="/interactive-labs">Interactive Labs</router-link>
      </nav>

      <div class="right">

        <div class="search">
          <input
            type="text"
            placeholder="Tìm kiếm sản phẩm..."
            v-model="searchQuery"
            @keyup.enter="handleSearch"
          />
          <svg viewBox="0 0 24 24" fill="none" @click="handleSearch" style="cursor: pointer;">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
          </svg>
        </div>

        <div class="dropdown-wrap" @mouseenter="showWishlist = true" @mouseleave="showWishlist = false">
          <button class="icon-btn" @click.stop="toggleWishlist" :class="{ active: showWishlist }">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
            <span class="badge badge-red" v-if="wishlistItems.length > 0">{{ wishlistItems.length }}</span>
          </button>

          <transition name="drop">
            <div class="dropdown wishlist-drop" v-if="showWishlist">
              <div class="drop-header">
                <span class="drop-title">Yêu thích</span>
                <span class="drop-count">{{ wishlistItems.length }} sản phẩm</span>
              </div>
              <div class="drop-body">
                <div v-if="wishlistItems.length === 0" class="drop-empty">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                  </svg>
                  <p>Chưa có sản phẩm yêu thích</p>
                </div>
                
                <div class="wish-item" v-for="item in wishlistItems" :key="item.id">
                  <img :src="getWishlistImg(item)" :alt="item.bienthe?.sanpham?.tenSP" />
                  <div class="wish-info">
                    <p class="wish-name">{{ item.bienthe?.sanpham?.tenSP || 'Sản phẩm' }}</p>
                    <p class="wish-price">{{ formatPrice(item.bienthe?.gia) }}</p>
                  </div>
                  <button class="wish-remove" @click="removeWishlist(item.id)" title="Xóa">
                    <svg viewBox="0 0 24 24" fill="none">
                      <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
              <div class="drop-footer" v-if="wishlistItems.length > 0">
                <router-link to="/wishlistpage" class="drop-action-btn" @click="showWishlist = false">
                  Xem tất cả
                </router-link>
              </div>
            </div>
          </transition>
        </div>

        <div class="dropdown-wrap" @mouseenter="showCartDropdown = true" @mouseleave="showCartDropdown = false">
          <div class="icon-btn-wrap">
            <button class="icon-btn" @click="goToCart" :class="{ active: showCartDropdown }">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M6 6h15l-1.5 9h-13z" />
                <circle cx="9" cy="20" r="1" />
                <circle cx="18" cy="20" r="1" />
              </svg>
            </button>
            <span class="badge badge-blue" v-if="cartCount > 0">
              {{ cartCount > 99 ? '99+' : cartCount }}
            </span>
          </div>

          <transition name="drop">
            <div class="dropdown cart-drop" v-if="showCartDropdown">
              <div class="drop-header">
                <span class="drop-title">Giỏ hàng</span>
                <span class="drop-count">{{ cartCount }} sản phẩm</span>
              </div>
              <div class="drop-body">
                <div v-if="cartItems.length === 0" class="drop-empty">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M6 6h15l-1.5 9h-13z" />
                    <circle cx="9" cy="20" r="1" />
                    <circle cx="18" cy="20" r="1" />
                  </svg>
                  <p>Giỏ hàng của bạn đang trống</p>
                </div>
                
                <div class="wish-item" v-for="item in cartItems" :key="item.id_giohang">
                  <img :src="item.hinh_anh || 'https://via.placeholder.com/150'" :alt="item.ten_san_pham" />
                  <div class="wish-info">
                    <p class="wish-name">{{ item.ten_san_pham }}</p>
                    <div class="cart-item-meta" v-if="item.ten_bienthe">
                      <span class="meta-tag">{{ item.ten_bienthe }}</span>
                    </div>
                    <div class="cart-price-qty-wrap">
                      <span class="wish-price">{{ formatPrice(item.gia) }}</span>
                      <span class="qty-badge">x{{ item.soluong }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="drop-footer" v-if="cartItems.length > 0">
                <div class="total-wrap">
                  <span class="total-label">Tổng cộng:</span>
                  <span class="total-val">{{ formatPrice(cartTotal) }}</span>
                </div>
                <router-link to="/cart" class="drop-action-btn" @click="showCartDropdown = false">
                  Xem giỏ hàng
                </router-link>
              </div>
            </div>
          </transition>
        </div>

        <div class="dropdown-wrap">
          <div class="icon-btn-wrap">
            <button class="icon-btn icon-btn-user" @click.stop="toggleUser" :class="{ active: showUser }">
              <img v-if="user" :src="avatarUrl" class="avatar-img" :alt="user.name" />
              <svg v-else viewBox="0 0 24 24" fill="none">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
            </button>
          </div>

          <transition name="drop">
            <div class="dropdown user-drop" v-if="showUser">
              <div class="user-profile-card">
                <img v-if="user" :src="avatarUrl" :alt="user.name" class="user-avatar" />
                <div class="user-info">
                  <p class="user-name">{{ user?.name }}</p>
                  <p class="user-email">{{ user?.email }}</p>
                  <span class="user-badge">{{ user?.memberSince }}</span>
                </div>
              </div>
              <div class="drop-divider"></div>
              <div class="drop-footer-user">
                <template v-if="user && user.role === 'admin'">
                  <button class="admin-btn" @click="goAdmin">
                    <span class="btn-left">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                      </svg>
                      Quản trị hệ thống
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="chevron-icon">
                      <polyline points="9 18 15 12 9 6" />
                    </svg>
                  </button>
                  <button class="logout-btn" @click="handleLogout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                      <polyline points="16 17 21 12 16 7" />
                      <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Đăng xuất
                  </button>
                </template>
                <template v-else>
                  <router-link to="/profile" @click="showUser = false" class="profile-btn">
                    <span class="btn-left">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                      </svg>
                      Thông tin cá nhân
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="chevron-icon">
                      <polyline points="9 18 15 12 9 6" />
                    </svg>
                  </router-link>
                  <router-link to="/affiliate" @click="showUser = false" class="profile-btn">
                    <span class="btn-left">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                      </svg>
                      Affiliate Center
                    </span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="chevron-icon">
                      <polyline points="9 18 15 12 9 6" />
                    </svg>
                  </router-link>
                  <button class="logout-btn" @click="handleLogout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                      <polyline points="16 17 21 12 16 7" />
                      <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Đăng xuất
                  </button>
                </template>
              </div>
              <div class="drop-divider"></div>
            </div>
          </transition>
        </div>

        <button class="hamburger-btn" @click="isMobileMenuOpen = !isMobileMenuOpen" :class="{ active: isMobileMenuOpen }" aria-label="Toggle menu">
          <span></span>
          <span></span>
          <span></span>
        </button>

      </div>
    </div>
  </header>

  <!-- Mobile Drawer Overlay & Menu -->
  <div class="mobile-menu-overlay" v-if="isMobileMenuOpen" @click="isMobileMenuOpen = false"></div>
  <transition name="slide-drawer">
    <div class="mobile-menu-drawer" v-if="isMobileMenuOpen">
      <div class="drawer-header">
        <div class="logo logo-3d">
          <div class="logo-inner">
            <span class="logo-black">NextGen</span>
            <span class="logo-blue">Laptop</span>
          </div>
        </div>
        <button class="drawer-close" @click="isMobileMenuOpen = false">×</button>
      </div>
      <div class="drawer-body">
        <div class="mobile-search">
          <input
            type="text"
            placeholder="Tìm kiếm sản phẩm..."
            v-model="searchQuery"
            @keyup.enter="handleSearch"
          />
          <svg viewBox="0 0 24 24" fill="none" @click="handleSearch" style="cursor: pointer;">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
          </svg>
        </div>
        <nav class="mobile-nav">
          <router-link to="/" :exact="true" @click="isMobileMenuOpen = false">Trang chủ</router-link>
          <router-link to="/products" @click="isMobileMenuOpen = false">Sản phẩm</router-link>
          <router-link to="/news" @click="isMobileMenuOpen = false">Tin tức</router-link>
          <router-link to="/contact" @click="isMobileMenuOpen = false">Liên hệ</router-link>
          <router-link to="/interactive-labs" @click="isMobileMenuOpen = false">Interactive Labs</router-link>
        </nav>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.topbar {
  background: #0f172a;
  color: #cbd5e1;
  font-size: 13px;
  height: 36px;
  display: flex;
  align-items: center;
}
.topbar-container {
  max-width: 1300px;
  width: 100%;
  margin: auto;
  padding: 0 30px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.topbar-message {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #94a3b8;
  letter-spacing: 0.2px;
}
.topbar-message strong { color: #e2e8f0; font-weight: 600; }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.topbar-item { display: flex; align-items: center; gap: 5px; color: #94a3b8; cursor: pointer; transition: color 0.2s; }
.topbar-item:hover { color: #e2e8f0; }
.topbar-item svg { width: 13px; height: 13px; stroke: currentColor; stroke-width: 2; fill: none; }
.topbar-divider { color: #334155; }
/* admin-btn styles moved to profile/user group */
.header { background: #fff; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 1000; box-shadow: 0 1px 8px rgba(0,0,0,0.05); }
.container { max-width: 1300px; margin: auto; padding: 0 30px; height: 68px; display: flex; align-items: center; justify-content: space-between; }
.logo {
  font-size: 24px;
  font-weight: 800;
  letter-spacing: -0.5px;
  user-select: none;
  display: flex;
  align-items: center;
  gap: 2px;
  cursor: pointer;
  perspective: 900px;
}

.logo-3d {
  perspective: 900px;
}

.logo-inner {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 2px;
  transform-style: preserve-3d;
  animation: logo-spin 8s linear infinite;
}

.logo-inner::after {
  content: 'NextGen Laptop';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: rgba(15, 23, 42, 0.12);
  transform: rotateY(180deg) translateZ(12px);
  backface-visibility: hidden;
}

.logo:hover .logo-inner {
  animation-play-state: paused;
}

.logo-black,
.logo-blue {
  display: inline-block;
  transform: translateZ(12px);
  backface-visibility: hidden;
}

.logo-black {
  color: #0f172a;
}

.logo-blue {
  background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

@keyframes logo-spin {
  0% {
    transform: rotateY(0deg);
  }
  100% {
    transform: rotateY(360deg);
  }
}
/* profile-btn styles moved to footer section styles */
.nav { display: flex; gap: 28px; }
.nav a { text-decoration: none; color: #1e293b; font-size: 15px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; transition: color 0.2s; position: relative; padding-bottom: 4px; }
.nav a:hover { color: #2563eb; }
.nav a.router-link-exact-active { color: #2563eb; }
.nav a.router-link-exact-active::after { content: ''; position: absolute; bottom: -4px; left: 0; right: 0; height: 2.5px; background: #2563eb; border-radius: 2px; }
.right { display: flex; align-items: center; gap: 10px; }
.search { display: flex; align-items: center; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 999px; padding: 6px 14px; width: 230px; transition: border-color 0.2s, box-shadow 0.2s; }
.search:focus-within { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); background: #fff; }
.search input { border: none; background: transparent; outline: none; font-size: 13px; flex: 1; color: #1e293b; }
.search input::placeholder { color: #94a3b8; }
.search svg { width: 15px; height: 15px; stroke: #94a3b8; stroke-width: 2; }
.icon-btn-wrap { position: relative; display: inline-flex; }
.icon-btn { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f1f5f9; cursor: pointer; transition: background 0.2s, transform 0.15s, box-shadow 0.2s; text-decoration: none; border: none; position: relative; }
.icon-btn:hover { background: #e2e8f0; transform: translateY(-1px); }
.icon-btn.active { background: #dbeafe; box-shadow: 0 0 0 2px #2563eb; }
.icon-btn svg { width: 18px; height: 18px; stroke: #475569; stroke-width: 1.8; fill: none; }
.icon-btn-user { background: #2563eb; padding: 0; }
.icon-btn-user svg { stroke: #fff; }
.icon-btn-user:hover { background: #1d4ed8; }
.icon-btn-user.active { box-shadow: 0 0 0 2px #93c5fd; }
.avatar-img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; display: block; }
.badge { position: absolute; top: -5px; right: -5px; min-width: 18px; height: 18px; padding: 0 4px; color: #fff; font-size: 10px; font-weight: 700; border-radius: 999px; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; line-height: 1; z-index: 10; }
.badge-blue { background: #2563eb; }
.badge-red { background: #ef4444; }
.dropdown-wrap { position: relative; }
.dropdown {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border-radius: 16px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16), 0 4px 16px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.5);
  z-index: 2000;
  overflow: hidden;
  padding: 6px 0;
}

.dropdown::before {
  content: '';
  position: absolute;
  top: -6px;
  right: 14px;
  width: 12px;
  height: 12px;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-bottom: none;
  border-right: none;
  transform: rotate(45deg);
  backdrop-filter: blur(20px);
}

.wishlist-drop {
  width: 360px;
}

.drop-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px 10px;
  border-bottom: 1px solid rgba(15, 23, 42, 0.05);
}

.drop-title {
  font-size: 15px;
  font-weight: 800;
  color: #0f172a;
}

.drop-count {
  font-size: 11.5px;
  color: #2563eb;
  background: rgba(37, 99, 235, 0.08);
  padding: 3px 10px;
  border-radius: 99px;
  font-weight: 700;
  border: 1px solid rgba(37, 99, 235, 0.1);
}

.drop-body {
  max-height: 320px;
  overflow-y: auto;
  padding: 12px 8px;
}

.drop-body::-webkit-scrollbar {
  width: 6px;
}

.drop-body::-webkit-scrollbar-track {
  background: transparent;
}

.drop-body::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.35);
  border-radius: 99px;
}

.drop-body::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.6);
}

.drop-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 28px 0;
  color: #94a3b8;
}

.drop-empty svg {
  width: 40px;
  height: 40px;
  stroke: #cbd5e1;
  stroke-width: 1.5;
  fill: none;
}

.drop-empty p {
  font-size: 13px;
}

.wish-item {
  display: flex;
  align-items: center;
  gap: 14px;
  background: rgba(255, 255, 255, 0.55);
  border: 1px solid rgba(15, 23, 42, 0.04);
  border-radius: 14px;
  margin: 0 12px 10px;
  padding: 12px;
  box-sizing: border-box;
  width: calc(100% - 24px);
  transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.wish-item:last-child {
  margin-bottom: 2px;
}

.wish-item:hover {
  background: #ffffff;
  border-color: rgba(37, 99, 235, 0.25);
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08), 0 2px 6px rgba(0, 0, 0, 0.02);
  transform: translateY(-2px);
}

.wish-item img {
  width: 58px;
  height: 58px;
  border-radius: 10px;
  border: 1px solid rgba(15, 23, 42, 0.05);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  object-fit: cover;
  transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.wish-item:hover img {
  transform: scale(1.04);
}

.wish-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.wish-name {
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  white-space: normal;
  text-overflow: ellipsis;
}

.wish-price {
  font-size: 14px;
  color: #2563eb;
  font-weight: 800;
  letter-spacing: -0.2px;
}

.wish-remove {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
  flex-shrink: 0;
}

.wish-remove:hover {
  background: #fee2e2;
}

.wish-remove svg {
  width: 14px;
  height: 14px;
  stroke: #ef4444;
  stroke-width: 2.5;
}

.drop-footer {
  padding: 16px 20px 14px;
  border-top: 1px solid rgba(15, 23, 42, 0.05);
  background: rgba(248, 250, 252, 0.4);
}

.drop-action-btn {
  background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%) !important;
  font-size: 13.5px;
  font-weight: 750;
  border: none;
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.22) !important;
  border-radius: 12px;
  padding: 11px 0;
  text-align: center;
  display: block;
  text-decoration: none;
  color: #fff;
  transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.drop-action-btn:hover {
  box-shadow: 0 12px 30px rgba(37, 99, 235, 0.32), 0 2px 8px rgba(124, 58, 237, 0.15) !important;
  transform: translateY(-2px) scale(1.015);
  opacity: 0.98;
}
.user-drop {
  width: 290px;
}
.user-profile-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 20px 18px;
}
.user-avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #fff;
  box-shadow: 0 0 0 2px #2563eb, 0 4px 12px rgba(37, 99, 235, 0.18);
  flex-shrink: 0;
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.user-profile-card:hover .user-avatar {
  transform: scale(1.06);
}
.user-info {
  min-width: 0;
}
.user-name {
  font-size: 15px;
  font-weight: 750;
  color: #0f172a;
  margin: 0 0 3px;
  letter-spacing: -0.2px;
}
.user-email {
  font-size: 12px;
  color: #64748b;
  margin: 0 0 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.user-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  color: #2563eb;
  background: #eff6ff;
  border: 1px solid #dbeafe;
  padding: 2px 8px;
  border-radius: 20px;
  letter-spacing: 0.02em;
}
.drop-divider {
  height: 1px;
  background: linear-gradient(90deg, rgba(241,245,249,0.3) 0%, #f1f5f9 50%, rgba(241,245,249,0.3) 100%);
}
.drop-footer-user {
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.profile-btn, .admin-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  box-sizing: border-box;
  padding: 11px 14px;
  border-radius: 10px;
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 13.5px;
  text-decoration: none;
  border: 1px solid #f1f5f9;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.profile-btn:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #2563eb;
  transform: translateY(-1.5px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.06);
}
.admin-btn {
  background: #faf5ff;
  border-color: #f3e8ff;
  color: #7c3aed;
}
.admin-btn:hover {
  background: #f3e8ff;
  border-color: #e9d5ff;
  color: #6d28d9;
  transform: translateY(-1.5px);
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.08);
}
.btn-left {
  display: flex;
  align-items: center;
  gap: 10px;
}
.btn-icon {
  width: 17px;
  height: 17px;
  stroke: currentColor;
  stroke-width: 2;
  fill: none;
  transition: transform 0.25s ease;
}
.profile-btn:hover .btn-icon, .admin-btn:hover .btn-icon {
  transform: scale(1.1);
}
.chevron-icon {
  width: 14px;
  height: 14px;
  stroke: #94a3b8;
  stroke-width: 2.5;
  fill: none;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.profile-btn:hover .chevron-icon {
  stroke: #2563eb;
  transform: translateX(2px);
}
.admin-btn:hover .chevron-icon {
  stroke: #7c3aed;
  transform: translateX(2px);
}
.logout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  box-sizing: border-box;
  padding: 11px;
  border-radius: 10px;
  border: 1px solid #fee2e2;
  background: #fff5f5;
  color: #ef4444;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.logout-btn:hover {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  border-color: #dc2626;
  color: #fff;
  transform: translateY(-1.5px);
  box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25);
}
.logout-btn .btn-icon {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  stroke-width: 2.2;
  fill: none;
  transition: transform 0.25s ease;
}
.logout-btn:hover .btn-icon {
  transform: translateX(-2px);
}
.drop-enter-active {
  transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.drop-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.drop-enter-from {
  opacity: 0;
  transform: translateY(-8px) scale(0.97);
}
.drop-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.97);
}

/* ===================== RESPONSIVE STYLES ===================== */
@media (max-width: 1024px) {
  .topbar {
    display: none;
  }
  .nav {
    display: none;
  }
  .search {
    display: none;
  }
  .container {
    padding: 0 20px;
    height: 60px;
  }
  .right {
    gap: 8px;
  }
  .hamburger-btn {
    display: flex;
  }
  .dropdown {
    right: -10px;
  }
  .dropdown::before {
    right: 24px;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 0 12px;
  }
  .logo {
    font-size: 19px;
  }
  .logo-black {
    font-size: 19px;
  }
  .logo-blue {
    font-size: 19px;
  }
  .right {
    gap: 6px;
  }
  .icon-btn {
    width: 34px;
    height: 34px;
  }
  .icon-btn svg {
    width: 16px;
    height: 16px;
  }
  .avatar-img {
    width: 34px;
    height: 34px;
  }
}

@media (max-width: 360px) {
  .logo {
    font-size: 17px;
  }
  .logo-black {
    font-size: 17px;
  }
  .logo-blue {
    font-size: 17px;
  }
  .right {
    gap: 4px;
  }
  .icon-btn {
    width: 30px;
    height: 30px;
  }
  .icon-btn svg {
    width: 14px;
    height: 14px;
  }
  .avatar-img {
    width: 30px;
    height: 30px;
  }
}

/* Hamburger Button styles */
.hamburger-btn {
  display: none;
  flex-direction: column;
  justify-content: space-between;
  width: 22px;
  height: 15px;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0;
  z-index: 10001;
  margin-left: 6px;
}
.hamburger-btn span {
  width: 100%;
  height: 2.2px;
  background-color: #0f172a;
  border-radius: 2px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hamburger-btn.active span:nth-child(1) {
  transform: translateY(6px) rotate(45deg);
}
.hamburger-btn.active span:nth-child(2) {
  opacity: 0;
  transform: translateX(-10px);
}
.hamburger-btn.active span:nth-child(3) {
  transform: translateY(-6.8px) rotate(-45deg);
}

/* Drawer overlay & content styles */
.mobile-menu-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(5px);
  z-index: 9998;
}
.mobile-menu-drawer {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 280px;
  background: rgba(255, 255, 255, 0.98);
  box-shadow: -10px 0 30px rgba(15, 23, 42, 0.08);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  padding: 24px;
  box-sizing: border-box;
}
.drawer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}
.drawer-close {
  font-size: 32px;
  color: #64748b;
  background: transparent;
  border: none;
  cursor: pointer;
  line-height: 0.8;
}
.drawer-body {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.mobile-search {
  display: flex;
  align-items: center;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  padding: 7px 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.mobile-search:focus-within {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
  background: #fff;
}
.mobile-search input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 13.5px;
  flex: 1;
  color: #1e293b;
}
.mobile-search svg {
  width: 15px;
  height: 15px;
  stroke: #94a3b8;
  stroke-width: 2;
}
.mobile-nav {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.mobile-nav a {
  text-decoration: none;
  color: #1e293b;
  font-size: 14.5px;
  font-weight: 700;
  letter-spacing: 0.3px;
  text-transform: uppercase;
  padding: 10px 14px;
  border-radius: 10px;
  transition: all 0.2s;
}
.mobile-nav a:hover, .mobile-nav a.router-link-exact-active {
  background: #eff6ff;
  color: #2563eb;
}

/* Slide drawer animation */
.slide-drawer-enter-active, .slide-drawer-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-drawer-enter-from, .slide-drawer-leave-to {
  transform: translateX(100%);
}

/* Mini Cart Dropdown Styles */
.cart-drop {
  width: 370px;
  background: rgba(255, 255, 255, 0.85) !important;
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.5) !important;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16), 0 4px 16px rgba(0, 0, 0, 0.04) !important;
  padding: 6px 0;
}

.cart-drop::before {
  background: rgba(255, 255, 255, 0.85) !important;
  border-left: 1px solid rgba(255, 255, 255, 0.5) !important;
  border-top: 1px solid rgba(255, 255, 255, 0.5) !important;
  backdrop-filter: blur(20px);
}

.cart-drop .drop-header {
  border-bottom: 1px solid rgba(15, 23, 42, 0.05);
  padding: 12px 20px 10px;
}

.cart-drop .drop-title {
  font-size: 15px;
  font-weight: 800;
  color: #0f172a;
}

.cart-drop .drop-count {
  font-size: 11.5px;
  color: #2563eb;
  background: rgba(37, 99, 235, 0.08);
  padding: 3px 10px;
  border-radius: 99px;
  font-weight: 700;
  border: 1px solid rgba(37, 99, 235, 0.1);
}

.cart-drop .drop-body {
  max-height: 320px;
  padding: 12px 8px;
}

.cart-drop .drop-body::-webkit-scrollbar {
  width: 6px;
}
.cart-drop .drop-body::-webkit-scrollbar-track {
  background: transparent;
}
.cart-drop .drop-body::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.35);
  border-radius: 99px;
}
.cart-drop .drop-body::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.6);
}

/* Floating Card style for items */
.cart-drop .wish-item {
  display: flex;
  align-items: center;
  gap: 14px;
  background: rgba(255, 255, 255, 0.55);
  border: 1px solid rgba(15, 23, 42, 0.04);
  border-radius: 14px;
  margin: 0 12px 10px;
  padding: 12px;
  box-sizing: border-box;
  width: calc(100% - 24px);
  transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.cart-drop .wish-item:last-child {
  margin-bottom: 2px;
}

.cart-drop .wish-item:hover {
  background: #ffffff;
  border-color: rgba(37, 99, 235, 0.25);
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08), 0 2px 6px rgba(0, 0, 0, 0.02);
  transform: translateY(-2px);
}

.cart-drop .wish-item img {
  width: 58px;
  height: 58px;
  border-radius: 10px;
  border: 1px solid rgba(15, 23, 42, 0.05);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  object-fit: cover;
  transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.cart-drop .wish-item:hover img {
  transform: scale(1.04);
}

.cart-drop .wish-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.cart-drop .wish-name {
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  white-space: normal;
  text-overflow: ellipsis;
}

.cart-item-meta {
  display: inline-flex;
  margin-bottom: 5px;
}

.cart-item-meta .meta-tag {
  font-size: 10px;
  background: rgba(148, 163, 184, 0.12);
  color: #475569;
  padding: 2px 8px;
  border-radius: 6px;
  font-weight: 600;
  border: 1px solid rgba(148, 163, 184, 0.08);
}

.cart-price-qty-wrap {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
}

.cart-drop .wish-price {
  font-size: 14px;
  color: #2563eb;
  font-weight: 800;
  letter-spacing: -0.2px;
}

.qty-badge {
  font-size: 10.5px;
  font-weight: 750;
  color: #4f46e5;
  background: rgba(79, 70, 229, 0.09);
  padding: 1.5px 8px;
  border-radius: 12px;
  border: 1px solid rgba(79, 70, 229, 0.08);
}

.cart-drop .drop-footer {
  padding: 16px 20px 14px;
  border-top: 1px solid rgba(15, 23, 42, 0.05);
  background: rgba(248, 250, 252, 0.4);
}

.total-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
  padding: 0 4px;
}

.total-label {
  font-size: 14px;
  font-weight: 700;
  color: #475569;
}

.total-val {
  font-size: 17px;
  font-weight: 900;
  color: #ef4444;
  letter-spacing: -0.3px;
}

.cart-drop .drop-action-btn {
  background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%) !important;
  font-size: 13.5px;
  font-weight: 750;
  border: none;
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.22) !important;
  border-radius: 12px;
  padding: 11px 0;
  text-align: center;
  transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.cart-drop .drop-action-btn:hover {
  box-shadow: 0 12px 30px rgba(37, 99, 235, 0.32), 0 2px 8px rgba(124, 58, 237, 0.15) !important;
  transform: translateY(-2px) scale(1.015);
  opacity: 0.98;
}
</style>
