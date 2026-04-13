<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import api from '../../services/api' 
import { getUser, clearAuth } from '@/services/auth'

const router = useRouter()

const showWishlist = ref(false)
const showUser = ref(false)

// ===================== TÌM KIẾM =====================
const searchQuery = ref('')

const handleSearch = () => {
  const keyword = searchQuery.value.trim()
  if (!keyword) return
  // Chuyển sang trang sản phẩm với query tìm kiếm
  router.push({ path: '/products', query: { q: keyword } })
  searchQuery.value = ''
}

// ===================== GIỎ HÀNG BADGE =====================
const cartCount = ref(0)

const fetchCartCount = async () => {
  try {
    const token = localStorage.getItem('token')
    if (!token) { cartCount.value = 0; return }

    const res = await api.get('/gio-hang/dem')
    cartCount.value = res.data.count || 0
  } catch {
    cartCount.value = 0
  }
}

// ===================== WISHLIST (DỮ LIỆU THẬT) =====================
const wishlistItems = ref([])

const fetchWishlist = async () => {
  try {
    const token = localStorage.getItem('token')
    if (!token) { wishlistItems.value = []; return }

    const res = await api.get('/yeu-thich')
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
    return imgPath ? `http://127.0.0.1:8000/storage/${imgPath}` : 'https://via.placeholder.com/150'
}

const handleCartUpdated = () => { fetchCartCount() }
const handleWishlistUpdated = () => { fetchWishlist() }

onMounted(() => {
  fetchCartCount()
  fetchWishlist()

  window.addEventListener('cart-updated', handleCartUpdated)
  window.addEventListener('wishlist-updated', handleWishlistUpdated)
  window.addEventListener('user-updated', fetchUser)

  fetchUser()

  document.addEventListener('click', handleOutside)
})

onUnmounted(() => {
  window.removeEventListener('cart-updated', handleCartUpdated)
  window.removeEventListener('wishlist-updated', handleWishlistUpdated)
  window.removeEventListener('user-updated', fetchUser)
  document.removeEventListener('click', handleOutside)
})

const goToCart = () => {
  const token = localStorage.getItem('token')
  if (!token) {
    alert('Vui lòng đăng nhập trước!')
    router.push({ path: '/login', query: { redirect: '/cart' } })
    return
  }
  router.push('/cart')
}

const toggleWishlist = () => {
  const token = localStorage.getItem('token')
  if (!token) { router.push('/login'); return }
  showWishlist.value = !showWishlist.value
  if (showWishlist.value) showUser.value = false
}

const toggleUser = () => {
  const token = localStorage.getItem('token')
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
  return `http://127.0.0.1:8000/storage/${user.value.avatar}`
})

const fetchUser = () => {
    user.value = getUser()
}

const handleLogout = async () => {
  showUser.value = false
  try {
    await axios.post('http://127.0.0.1:8000/api/logout')
  } catch {
    console.log('Logout API lỗi (bỏ qua)')
  }
  clearAuth()
  localStorage.removeItem('remember_email')
  cartCount.value = 0
  wishlistItems.value = []
  router.push('/login')
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

      <div class="logo">
        <span class="logo-black">NextGen</span>
        <span class="logo-blue">Laptop</span>
      </div>

      <nav class="nav">
        <router-link to="/" :exact="true">Trang chủ</router-link>
        <router-link to="/products">Sản phẩm</router-link>
        <router-link to="/news">Tin tức</router-link>
        <router-link to="/contact">Liên hệ</router-link>
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

        <div class="dropdown-wrap">
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

        <div class="icon-btn-wrap">
          <button class="icon-btn" @click="goToCart">
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
                  <button class="admin-btn" @click="goAdmin">⚙️ Quản trị</button>
                  <button class="logout-btn" @click="handleLogout">Đăng xuất</button>
                </template>
                <template v-else>
                  <router-link to="/profile" @click="showUser = false" class="profile-btn">
                    👤 Thông tin cá nhân
                  </router-link>
                  <button class="logout-btn" @click="handleLogout">Đăng xuất</button>
                </template>
              </div>
              <div class="drop-divider"></div>
            </div>
          </transition>
        </div>

      </div>
    </div>
  </header>
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
.admin-btn { width: 100%; margin-bottom: 8px; padding: 10px; border-radius: 12px; border: 1px solid #2563eb; background: #eff6ff; color: #2563eb; font-weight: 600; cursor: pointer; transition: 0.2s; }
.admin-btn:hover { background: #2563eb; color: #fff; }
.header { background: #fff; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 1000; box-shadow: 0 1px 8px rgba(0,0,0,0.05); }
.container { max-width: 1300px; margin: auto; padding: 0 30px; height: 68px; display: flex; align-items: center; justify-content: space-between; }
.logo { font-size: 21px; font-weight: 800; letter-spacing: 0.5px; user-select: none; }
.logo-black { color: #0f172a; }
.logo-blue { color: #2563eb; }
.profile-btn { display: block; width: 92%; margin-bottom: 8px; padding: 10px; border-radius: 12px; background: #bec0c1; color: #334155; font-weight: 500; text-align: left; text-decoration: none; transition: 0.2s; }
.profile-btn:hover { background: #858788; }
.nav { display: flex; gap: 28px; }
.nav a { text-decoration: none; color: #6b7280; font-size: 14px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; transition: color 0.2s; position: relative; padding-bottom: 4px; }
.nav a:hover { color: #2563eb; }
.nav a.router-link-exact-active { color: #2563eb; }
.nav a.router-link-exact-active::after { content: ''; position: absolute; bottom: -4px; left: 0; right: 0; height: 2px; background: #2563eb; border-radius: 2px; }
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
.dropdown { position: absolute; top: calc(100% + 12px); right: 0; background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(15,23,42,0.14), 0 4px 16px rgba(15,23,42,0.06); border: 1px solid #f1f5f9; z-index: 2000; overflow: hidden; }
.dropdown::before { content: ''; position: absolute; top: -6px; right: 14px; width: 12px; height: 12px; background: #fff; border: 1px solid #f1f5f9; border-bottom: none; border-right: none; transform: rotate(45deg); }
.wishlist-drop { width: 320px; }
.drop-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px 12px; border-bottom: 1px solid #f1f5f9; }
.drop-title { font-size: 14px; font-weight: 700; color: #0f172a; }
.drop-count { font-size: 12px; color: #64748b; background: #f1f5f9; padding: 3px 8px; border-radius: 20px; font-weight: 500; }
.drop-body { max-height: 280px; overflow-y: auto; padding: 8px 0; }
.drop-body::-webkit-scrollbar { width: 4px; }
.drop-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.drop-empty { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 28px 0; color: #94a3b8; }
.drop-empty svg { width: 40px; height: 40px; stroke: #cbd5e1; stroke-width: 1.5; fill: none; }
.drop-empty p { font-size: 13px; }
.wish-item { display: flex; align-items: center; gap: 12px; padding: 10px 18px; transition: background 0.15s; }
.wish-item:hover { background: #f8fafc; }
.wish-item img { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; border: 1px solid #e5e7eb; flex-shrink: 0; }
.wish-info { flex: 1; min-width: 0; }
.wish-name { font-size: 13px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 3px; }
.wish-price { font-size: 12px; color: #2563eb; font-weight: 600; margin: 0; }
.wish-remove { width: 26px; height: 26px; border-radius: 50%; border: none; background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s; flex-shrink: 0; }
.wish-remove:hover { background: #fee2e2; }
.wish-remove svg { width: 14px; height: 14px; stroke: #ef4444; stroke-width: 2.5; }
.drop-footer { padding: 12px 18px; border-top: 1px solid #f1f5f9; }
.drop-action-btn { display: block; text-align: center; padding: 9px; border-radius: 10px; background: linear-gradient(135deg, #2563eb, #4f46e5); color: #fff; font-size: 13px; font-weight: 600; text-decoration: none; transition: opacity 0.2s, transform 0.15s; }
.drop-action-btn:hover { opacity: 0.9; transform: translateY(-1px); }
.user-drop { width: 280px; }
.user-profile-card { display: flex; align-items: center; gap: 12px; padding: 18px; }
.user-avatar { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 2px solid #dbeafe; flex-shrink: 0; }
.user-info { min-width: 0; }
.user-name { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 2px; }
.user-email { font-size: 12px; color: #64748b; margin: 0 0 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-badge { display: inline-block; font-size: 10px; font-weight: 700; color: #2563eb; background: #dbeafe; padding: 2px 8px; border-radius: 20px; letter-spacing: 0.04em; }
.drop-divider { height: 1px; background: #f1f5f9; }
.drop-footer-user { padding: 12px 18px; }
.logout-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border-radius: 10px; border: none; background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; font-size: 13.5px; font-weight: 600; cursor: pointer; transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s; box-shadow: 0 4px 14px rgba(239,68,68,0.3); }
.logout-btn:hover { opacity: 0.92; transform: translateY(-1px); }
.drop-enter-active { transition: opacity 0.2s ease, transform 0.22s cubic-bezier(0.34,1.4,0.64,1); }
.drop-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.drop-enter-from { opacity: 0; transform: translateY(-8px) scale(0.97); }
.drop-leave-to { opacity: 0; transform: translateY(-6px) scale(0.97); }
</style>