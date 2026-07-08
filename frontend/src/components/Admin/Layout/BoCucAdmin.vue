<template>
  <div
    :class="[
      'admin-layout',
      `density-${appearance.density}`,
      `width-${appearance.content_width}`,
      `sidebar-${appearance.sidebar_style}`,
      `anim-${appearance.animation_level}`,
      adminIntroActive && 'intro-active',
    ]"
    :style="adminVars"
  >
    <aside class="sidebar">
      <div class="sidebar-logo">
        <img
          src="/ChatGPT_Image_08_35_43_4_thg_6__2026-removebg-preview.png"
          alt="NextGen Group"
          class="admin-logo-img"
        />
      </div>

      <div class="menu-section">
        <p class="menu-label">MAIN MENU</p>
        <div v-for="item in filteredMenuConfig" :key="item.label || item.path" class="menu-wrapper">
          <!-- Normal Link -->
          <router-link
            v-if="!item.isDropdown"
            :to="item.path"
            v-slot="{ isActive, isExactActive }"
          >
            <div :class="['item', (item.path === '/admin' ? isExactActive : isActive) && 'active']">
              <component :is="item.icon" class="item-icon" />
              <span>{{ item.label }}</span>
            </div>
          </router-link>

          <!-- Dropdown Group -->
          <div v-else class="dropdown-group" :class="{ 'is-open': dropdownStates[item.label] }">
            <button
              type="button"
              class="item dropdown-toggle"
              :class="{ 'parent-active': isParentActive(item) }"
              @click="toggleDropdown(item.label)"
            >
              <component :is="item.icon" class="item-icon" />
              <span>{{ item.label }}</span>
              <ChevronDown class="chevron-icon" />
            </button>

            <div class="submenu">
              <router-link
                v-for="sub in item.children"
                :key="sub.path"
                :to="sub.path"
                v-slot="{ isActive }"
              >
                <div :class="['submenu-item', isActive && 'active']">
                  <span class="bullet-dot"></span>
                  <span>{{ sub.label }}</span>
                  <span v-if="sub.badge" :class="['submenu-badge', `badge-${sub.badge.toLowerCase()}`]">
                    {{ sub.badge }}
                  </span>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <div class="sidebar-user">
        <div class="user-avatar">
          <img v-if="avatarUrl" :src="avatarUrl" alt="Admin Avatar" class="user-avatar-img" />
          <span v-else>{{ userInitials }}</span>
        </div>
        <div class="user-info">
          <p class="user-name">{{ userName }}</p>
          <p class="user-role">{{ userRoleName }}</p>
        </div>
        <button class="sidebar-logout-btn" type="button" @click="handleLogout" aria-label="Đăng xuất">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" y1="12" x2="9" y2="12" />
          </svg>
        </button>
      </div>
    </aside>

    <main class="main">
      <section class="admin-topbar">
        <div class="admin-topbar-title">
          <h2>{{ pageTitle }}</h2>
          <p>Quản lý nội dung và điều hành hệ thống</p>
        </div>

        <div class="admin-topbar-actions">
          <router-link to="/" class="topbar-home-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
              <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            Trang chủ
          </router-link>
          <div class="topbar-icon-group">
            <div class="topbar-popover" ref="langMenuRef">
              <button class="topbar-icon-button" type="button" aria-label="Ngôn ngữ" @click="toggleLangMenu">
                <span class="icon-flag">{{ localeBadge }}</span>
              </button>
              <div v-if="langMenuOpen" class="topbar-dropdown compact-menu">
                <button class="dropdown-item compact" type="button" :class="{ active: currentLocale === 'vi' }" @click="setLocale('vi')">Tiếng Việt</button>
                <button class="dropdown-item compact" type="button" :class="{ active: currentLocale === 'en' }" @click="setLocale('en')">English</button>
              </div>
            </div>

            <div class="topbar-popover" ref="appsMenuRef">
              <button class="topbar-icon-button" type="button" aria-label="Ứng dụng" @click="toggleAppsMenu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /></svg>
              </button>
              <div v-if="appsMenuOpen" class="topbar-dropdown apps-dropdown">
                <button
                  v-for="app in quickApps"
                  :key="app.path"
                  class="dropdown-item compact"
                  type="button"
                  @click="openQuickApp(app.path)"
                >
                  {{ app.label }}
                </button>
              </div>
            </div>

            <AdminChatManager />

            <div class="topbar-popover" ref="notifyMenuRef">
              <button class="topbar-icon-button" type="button" aria-label="Thông báo" @click="toggleNotifyMenu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" /></svg>
                <span class="icon-badge" v-if="unreadCount > 0">{{ unreadCount }}</span>
              </button>
              <div v-if="notifyMenuOpen" class="topbar-dropdown notify-menu">
                <div class="notify-head">
                  <b>Thông báo</b>
                  <button class="notify-mark-read" type="button" @click="markAllNotificationsRead">Đánh dấu đã đọc</button>
                </div>
                <div v-if="!notifications.some(n => !n.read)" class="notify-empty">Chưa có thông báo mới</div>
                <button
                  v-for="item in notifications.filter(n => !n.read)"
                  :key="item.id"
                  type="button"
                  class="notify-item"
                  :class="{ unread: !item.read }"
                  @click="openNotification(item)"
                >
                  <span class="notify-title">{{ item.title }}</span>
                  <span class="notify-time">{{ item.time }}</span>
                </button>
              </div>
            </div>
          </div>

          <div class="topbar-divider"></div>

          <div class="topbar-user" ref="userMenuRef">
            <button class="topbar-user-btn" type="button" @click="toggleUserMenu" :aria-expanded="userMenuOpen">
              <div class="user-avatar">
                <img v-if="avatarUrl" :src="avatarUrl" alt="Admin Avatar" class="user-avatar-img" />
                <span v-else>{{ userInitials }}</span>
              </div>
              <div class="user-meta">
                <span class="user-name">{{ userName }}</span>
                <span class="user-role">{{ userRoleName }}</span>
              </div>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
            </button>
            <div class="user-dropdown" v-if="userMenuOpen">
              <div class="user-dropdown-header">
                <div class="user-dropdown-avatar">
                  <img v-if="avatarUrl" :src="avatarUrl" alt="Admin Avatar" class="user-avatar-img" />
                  <span v-else>{{ userInitials }}</span>
                </div>
                <div class="user-dropdown-info">
                  <p class="dropdown-name">{{ userName }}</p>
                  <p class="dropdown-email">{{ userEmail }}</p>
                </div>
              </div>
              <div class="user-dropdown-list">
                <button class="dropdown-item" type="button" @click="navigateUserMenu('/admin/ho-so-quan-tri')">Hồ sơ của tôi</button>
                <button class="dropdown-item" type="button" @click="navigateUserMenu('/admin/cai-dat-he-thong')">Cài đặt</button>
                <button class="dropdown-item" type="button" @click="navigateUserMenu('/admin/nhat-ky-hoat-dong')">Nhật ký hoạt động</button>
                <button class="dropdown-item" type="button" @click="navigateUserMenu('/admin/hoa-don')">Thanh toán</button>
              </div>
              <button class="dropdown-item sign-out logout-item" type="button" @click="handleLogout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                  <polyline points="16 17 21 12 16 7" />
                  <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                <span>Đăng xuất</span>
              </button>
            </div>
          </div>
        </div>
      </section>

      <router-view v-slot="{ Component }">
        <transition name="page-fade">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { clearAuth, getUser } from '@/services/auth'
import AdminChatManager from '@/components/Admin/Layout/QuanLyChatAdmin.vue'
import { storageUrl } from '@/services/urls'
import { getLocale, setLocale as setAppLocale } from '@/services/i18n'
import api from '@/services/api'
import swal from '@/services/swal'
import {
  LayoutDashboard,
  Package,
  ShoppingCart,
  Newspaper,
  FolderTree,
  Tag,
  TicketPercent,
  Image,
  Palette,
  Users,
  MessageSquare,
  Handshake,
  Mail,
  Activity,
  Gift,
  ChevronDown,
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()

const pageTitle = computed(() => route.meta.title || 'Bảng quản trị')
const user = ref(getUser() || {})
const userMenuOpen = ref(false)
const langMenuOpen = ref(false)
const appsMenuOpen = ref(false)
const notifyMenuOpen = ref(false)
const adminIntroActive = ref(false)
let adminIntroTimer = null

const userMenuRef = ref(null)
const langMenuRef = ref(null)
const appsMenuRef = ref(null)
const notifyMenuRef = ref(null)

const currentLocale = ref(getLocale())
const localeBadge = computed(() => (currentLocale.value === 'en' ? 'US' : 'VN'))

const appearance = ref({
  primary_color: '#2563eb',
  accent_color: '#2563eb',
  border_radius: 12,
  card_shadow: 'medium',
  density: 'comfortable',
  content_width: 'fluid',
  sidebar_style: 'solid',
  animation_level: 'normal',
})

const menuConfig = [
  { path: '/admin', label: 'Tổng quan', icon: LayoutDashboard },
  {
    label: 'Sản phẩm',
    icon: Package,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-san-pham', label: 'Sản phẩm', badge: 'CORE' },
      { path: '/admin/quan-ly-danh-muc', label: 'Danh mục', badge: 'CONFIG' },
      { path: '/admin/quan-ly-thuong-hieu', label: 'Thương hiệu', badge: 'CONFIG' },
      { path: '/admin/bien-the-san-pham', label: 'Màu & biến thể', badge: 'CONFIG' },
    ]
  },
  {
    label: 'Bán hàng',
    icon: ShoppingCart,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-don-hang', label: 'Đơn hàng', badge: 'CORE' },
      { path: '/admin/quan-ly-khuyen-mai', label: 'Khuyến mãi', badge: 'SALES' },
      { path: '/admin/gui-ma-sinh-nhat', label: 'Gửi mã sinh nhật', badge: 'SALES' },
      { path: '/admin/quan-ly-combo', label: 'Quản lý Combo', badge: 'SALES' },
      { path: '/admin/quan-ly-tiep-thi', label: 'Affiliate', badge: 'MARKETING' },
    ]
  },
  {
    label: 'Nội dung',
    icon: Newspaper,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-tin-tuc', label: 'Bài viết', badge: 'CONTENT' },
      { path: '/admin/quan-ly-binh-luan', label: 'Bình luận', badge: 'CONTENT' },
    ]
  },
  {
    label: 'Người dùng',
    icon: Users,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-nguoi-dung', label: 'User', badge: 'ADMIN' },
      { path: '/admin/quan-ly-vai-tro', label: 'Vai trò & quyền', badge: 'ADMIN' },
      { path: '/admin/quan-ly-lien-he', label: 'Liên hệ', badge: 'SUPPORT' },
    ]
  },
  { path: '/admin/quan-ly-banner', label: 'Banner', icon: Image },
  { path: '/admin/nhat-ky-hoat-dong', label: 'Nhật ký hệ thống', icon: Activity },
]

const filteredMenuConfig = computed(() => {
  const userPerms = user.value?.cac_quyen || []
  const isAdmin = user.value?.vaitro === 'admin'

  const hasPerm = (perm) => isAdmin || userPerms.includes(perm)

  const pathPermissionMap = {
    '/admin/quan-ly-san-pham': 'san_pham_xem',
    '/admin/quan-ly-danh-muc': 'danh_muc_xem',
    '/admin/quan-ly-thuong-hieu': 'thuong_hieu_xem',
    '/admin/bien-the-san-pham': 'bien_the_xem',
    '/admin/bien-the': 'bien_the_xem',
    
    '/admin/quan-ly-don-hang': 'don_hang_xem',
    '/admin/hoa-don': 'hoa_don_xem',
    
    '/admin/quan-ly-khuyen-mai': 'marketing_quan_ly',
    '/admin/gui-ma-sinh-nhat': 'marketing_quan_ly',
    '/admin/quan-ly-combo': 'marketing_quan_ly',
    '/admin/quan-ly-tiep-thi': 'affiliate_quan_ly',
    
    '/admin/quan-ly-tin-tuc': 'tin_tuc_quan_ly',
    '/admin/quan-ly-binh-luan': 'binh_luan_quan_ly',
    
    '/admin/quan-ly-nguoi-dung': 'tai_khoan_quan_ly',
    '/admin/quan-ly-vai-tro': 'vai_tro_quan_ly',
    '/admin/quan-ly-lien-he': 'lien_he_quan_ly',
    
    '/admin/quan-ly-banner': 'banner_quan_ly',
    '/admin/nhat-ky-hoat-dong': 'nhat_ky_quan_ly',
  }

  return menuConfig.map(item => {
    if (!item.isDropdown) {
      const required = pathPermissionMap[item.path]
      if (required && !hasPerm(required)) return null
      return item
    }

    const filteredChildren = item.children.filter(child => {
      const required = pathPermissionMap[child.path]
      if (required && !hasPerm(required)) return false
      return true
    })

    if (filteredChildren.length === 0) return null

    return {
      ...item,
      children: filteredChildren
    }
  }).filter(Boolean)
})

const dropdownStates = ref({
  'Sản phẩm': false,
  'Bán hàng': false,
  'Nội dung': false,
  'Người dùng': false,
})

function toggleDropdown(label) {
  dropdownStates.value[label] = !dropdownStates.value[label]
}

function isParentActive(item) {
  if (!item.children) return false
  return item.children.some(child => route.path === child.path)
}

function autoOpenDropdowns() {
  menuConfig.forEach(item => {
    if (item.isDropdown && item.children) {
      const hasActiveChild = item.children.some(child => route.path === child.path)
      if (hasActiveChild) {
        dropdownStates.value[item.label] = true
      }
    }
  })
}

watch(
  () => route.path,
  () => {
    autoOpenDropdowns()
  },
  { immediate: true }
)

const quickApps = [
  { label: 'Tổng quan', path: '/admin' },
  { label: 'Sản phẩm', path: '/admin/quan-ly-san-pham' },
  { label: 'Đơn hàng', path: '/admin/quan-ly-don-hang' },
  { label: 'Khuyến mãi', path: '/admin/quan-ly-khuyen-mai' },
  { label: 'Banner', path: '/admin/quan-ly-banner' },
  { label: 'User', path: '/admin/quan-ly-nguoi-dung' },
  { label: 'Settings', path: '/admin/cai-dat-he-thong' },
]

const notifications = ref([])
const unreadCount = computed(() => notifications.value.filter((n) => !n.read).length)

const adminVars = computed(() => {
  const shadowMap = {
    none: 'none',
    soft: '0 6px 16px rgba(15, 23, 42, 0.06)',
    medium: '0 10px 24px rgba(15, 23, 42, 0.10)',
    strong: '0 16px 30px rgba(15, 23, 42, 0.16)',
  }
  return {
    '--admin-primary': appearance.value.primary_color,
    '--admin-accent': appearance.value.accent_color,
    '--admin-radius': `${appearance.value.border_radius || 12}px`,
    '--admin-card-shadow': shadowMap[appearance.value.card_shadow] || shadowMap.medium,
  }
})

const userName = computed(() => 'NextGen Group')
const userEmail = computed(() => user.value?.email || user.value?.username || '')
const userInitials = computed(() =>
  userName.value
    .split(' ')
    .filter(Boolean)
    .slice(-2)
    .map((part) => part[0]?.toUpperCase())
    .join('')
)
const avatarUrl = computed(() => {
  const avatar = user.value?.avatar
  if (!avatar) return ''
  return avatar.startsWith('http') ? avatar : storageUrl(avatar)
})

function refreshUser() {
  user.value = getUser() || {}
}

function closeTopMenus() {
  langMenuOpen.value = false
  appsMenuOpen.value = false
  notifyMenuOpen.value = false
}

function toggleUserMenu() {
  userMenuOpen.value = !userMenuOpen.value
}

function toggleLangMenu() {
  const next = !langMenuOpen.value
  closeTopMenus()
  langMenuOpen.value = next
}

function toggleAppsMenu() {
  const next = !appsMenuOpen.value
  closeTopMenus()
  appsMenuOpen.value = next
}

function toggleNotifyMenu() {
  const next = !notifyMenuOpen.value
  closeTopMenus()
  notifyMenuOpen.value = next
}

function setLocale(locale) {
  currentLocale.value = locale
  setAppLocale(locale)
  langMenuOpen.value = false
}

function openQuickApp(path) {
  appsMenuOpen.value = false
  router.push(path)
}

function markAllNotificationsRead() {
  notifications.value = notifications.value.map((n) => ({ ...n, read: true }))
  persistNotifications()
}

function openNotification(item) {
  notifications.value = notifications.value.map((n) => (n.id === item.id ? { ...n, read: true } : n))
  persistNotifications()
  notifyMenuOpen.value = false
  if (item.path) router.push(item.path)
}

function persistNotifications() {
  const saved = localStorage.getItem('admin-topbar-notifications')
  let savedList = []
  if (saved) {
    try {
      const parsed = JSON.parse(saved)
      if (Array.isArray(parsed)) savedList = parsed
    } catch (_) {}
  }

  // Merge notifications.value into savedList to preserve state across page reloads
  notifications.value.forEach(item => {
    const idx = savedList.findIndex(n => n.id === item.id)
    if (idx !== -1) {
      savedList[idx] = { ...savedList[idx], ...item }
    } else {
      savedList.push(item)
    }
  })

  // Limit savedList size to 100 to prevent local storage bloat
  if (savedList.length > 100) {
    savedList.splice(0, savedList.length - 100)
  }

  localStorage.setItem('admin-topbar-notifications', JSON.stringify(savedList))
}

function hydrateNotifications() {
  const saved = localStorage.getItem('admin-topbar-notifications')
  if (!saved) return
  try {
    const parsed = JSON.parse(saved)
    if (Array.isArray(parsed)) {
      // Set to notifications.value, showing only unread notifications (or up to 6 of them)
      notifications.value = parsed.filter(n => !n.read).slice(0, 6)
    }
  } catch (e) {
    // ignore invalid local storage
  }
}

async function loadNotifications() {
  try {
    const res = await api.get('/admin/account/activity-log')
    const rows = Array.isArray(res.data?.data) ? res.data.data.slice(0, 6) : []
    if (!rows.length) return

    // Get existing notifications from localStorage to preserve read status
    const saved = localStorage.getItem('admin-topbar-notifications')
    let savedList = []
    if (saved) {
      try {
        const parsed = JSON.parse(saved)
        if (Array.isArray(parsed)) savedList = parsed
      } catch (_) {}
    }

    const mapped = rows.map((row, idx) => {
      // Generate unique, stable ID based on title and timestamp so it doesn't shift and reset
      const cleanTitle = (row.title || '').replace(/\s+/g, '').replace(/#/g, '')
      const cleanAt = (row.at || '').replace(/[^a-zA-Z0-9]/g, '')
      const id = `log-${cleanTitle}-${cleanAt || idx}`
      const existing = savedList.find(n => n.id === id)
      return {
        id: id,
        title: row.title || row.description || 'Hoạt động mới',
        time: row.at ? new Date(row.at).toLocaleString('vi-VN') : 'Vừa xong',
        read: existing ? existing.read : false,
        path: '/admin/nhat-ky-hoat-dong',
      }
    })
    notifications.value = mapped
    persistNotifications()
  } catch (e) {
    if (!notifications.value.length) {
      const saved = localStorage.getItem('admin-topbar-notifications')
      let savedList = []
      if (saved) {
        try {
          const parsed = JSON.parse(saved)
          if (Array.isArray(parsed)) savedList = parsed
        } catch (_) {}
      }
      notifications.value = [
        { id: 'seed-1', title: 'Có đơn hàng mới cần xử lý', time: 'Vừa xong', read: savedList.find(n => n.id === 'seed-1')?.read || false, path: '/admin/quan-ly-don-hang' },
        { id: 'seed-2', title: 'Có liên hệ mới từ khách hàng', time: '5 phút trước', read: savedList.find(n => n.id === 'seed-2')?.read || false, path: '/admin/quan-ly-lien-he' },
      ]
    }
  }
}

function navigateUserMenu(path) {
  userMenuOpen.value = false
  router.push(path)
}

function isInside(refEl, target) {
  return refEl?.value && refEl.value.contains(target)
}

function handleClickOutside(event) {
  const target = event.target
  if (userMenuOpen.value && !isInside(userMenuRef, target)) userMenuOpen.value = false
  if (langMenuOpen.value && !isInside(langMenuRef, target)) langMenuOpen.value = false
  if (appsMenuOpen.value && !isInside(appsMenuRef, target)) appsMenuOpen.value = false
  if (notifyMenuOpen.value && !isInside(notifyMenuRef, target)) notifyMenuOpen.value = false
}

async function handleLogout() {
  const isConfirmed = await swal.confirm('Xác nhận đăng xuất', 'Bạn có chắc chắn muốn thoát khỏi hệ thống quản trị?')
  if (!isConfirmed) return
  userMenuOpen.value = false
  api.post('/logout').catch((err) => console.log('Logout API lỗi (bỏ qua):', err))
  clearAuth()
  router.push('/dang-nhap')
}

async function loadAppearanceSettings() {
  try {
    const res = await api.get('/admin/account/settings')
    const ap = res.data?.data?.appearance
    if (ap) appearance.value = { ...appearance.value, ...ap }
  } catch (e) {
    // keep defaults
  }
}

function handleSettingsUpdated(event) {
  const ap = event?.detail?.appearance
  if (ap) appearance.value = { ...appearance.value, ...ap }
}

async function fetchLatestUserProfile() {
  try {
    const res = await api.get('/admin/account/profile')
    if (res.data?.success && res.data?.data) {
      const latestUser = res.data.data
      localStorage.setItem('user', JSON.stringify(latestUser))
      user.value = latestUser
    }
  } catch (err) {
    console.error('Failed to sync admin profile:', err)
  }
}

onMounted(async () => {
  if (sessionStorage.getItem('admin_intro_animation') === '1') {
    sessionStorage.removeItem('admin_intro_animation')
    adminIntroActive.value = true
    adminIntroTimer = window.setTimeout(() => {
      adminIntroActive.value = false
      adminIntroTimer = null
    }, 520)
  }

  document.addEventListener('mousedown', handleClickOutside)
  window.addEventListener('user-updated', refreshUser)
  window.addEventListener('admin-settings-updated', handleSettingsUpdated)
  document.documentElement.lang = currentLocale.value
  await loadAppearanceSettings()
  hydrateNotifications()
  await loadNotifications()
  await fetchLatestUserProfile()
})

onUnmounted(() => {
  if (adminIntroTimer) {
    clearTimeout(adminIntroTimer)
    adminIntroTimer = null
  }

  document.removeEventListener('mousedown', handleClickOutside)
  window.removeEventListener('user-updated', refreshUser)
  window.removeEventListener('admin-settings-updated', handleSettingsUpdated)
})
</script>

<style scoped>
* { box-sizing: border-box; }
.admin-layout { display: flex; height: 100vh; overflow: hidden; background: #f8fafc; font-family: Inter, sans-serif; }
.admin-layout.dark { background: #0b0f19; }
.sidebar { 
    width: 260px; 
    min-width: 260px; 
    background: #081225; 
    padding: 24px 16px; 
    height: 100vh; 
    overflow: hidden; 
    position: relative; 
    display: flex; 
    flex-direction: column; 
    border-right: 1px solid rgba(255, 255, 255, 0.04);
}
.sidebar-logo { 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    padding: 0 4px 20px; 
    border-bottom: 1px solid rgba(255, 255, 255, 0.05); 
    margin-bottom: 12px; 
}
.admin-logo-img { width: 220px; max-width: 100%; height: 74px; object-fit: contain; display: block; filter: drop-shadow(0 8px 18px rgba(0,0,0,.24)); }
.menu-label { 
    font-size: 10px; 
    font-weight: 700; 
    color: rgba(248, 250, 252, 0.35); 
    letter-spacing: 1.5px; 
    padding: 16px 12px 8px; 
    margin: 0; 
    text-transform: uppercase;
}
.menu-section { display: flex; flex-direction: column; gap: 6px; flex: 1; min-height: 0; overflow-y: auto; padding-bottom: 16px; }
.menu-section { -ms-overflow-style: none; scrollbar-width: none; }
.menu-section::-webkit-scrollbar { width: 0; height: 0; display: none; }
a { text-decoration: none; }
.item { 
    padding: 11px 14px; 
    border-radius: 10px; 
    color: rgba(248, 250, 252, 0.7); 
    font-size: 13.5px; 
    font-weight: 500; 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    min-height: 44px; 
    transition: all 0.2s ease;
}
.item-icon { width: 18px; height: 18px; flex-shrink: 0; stroke-width: 2; opacity: 0.85; }
.item:hover { 
    background: rgba(255, 255, 255, 0.04); 
    color: #ffffff;
}
.item.active { 
    background: linear-gradient(135deg, #2563eb, #3b82f6); 
    color: #ffffff;
    font-weight: 600; 
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}
.dropdown-toggle {
  width: 100%;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
}
.dropdown-toggle.parent-active {
  color: #38bdf8;
}
.chevron-icon {
  margin-left: auto;
  width: 15px;
  height: 15px;
  transition: transform 0.25s ease;
  color: rgba(248, 250, 252, 0.45);
}
.dropdown-group.is-open .chevron-icon {
  transform: rotate(180deg);
  color: #fff;
}
.submenu {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.25s cubic-bezier(0, 1, 0, 1);
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding-left: 18px;
}
.dropdown-group.is-open .submenu {
  max-height: 300px;
  transition: max-height 0.25s ease-in-out;
  margin-top: 4px;
  margin-bottom: 4px;
}
.submenu-item {
  padding: 8px 14px;
  border-radius: 8px;
  color: rgba(248, 250, 252, 0.6);
  font-size: 13px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 36px;
  transition: all 0.2s ease;
}
.submenu-item:hover {
  background: rgba(255, 255, 255, 0.03);
  color: #fff;
}
.submenu-item.active {
  background: rgba(59, 130, 246, 0.12);
  color: #38bdf8;
  font-weight: 600;
}
.bullet-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background-color: rgba(248, 250, 252, 0.3);
  transition: all 0.2s ease;
  flex-shrink: 0;
}
.submenu-item.active .bullet-dot {
  background-color: #38bdf8;
  box-shadow: 0 0 6px #38bdf8;
}
.submenu-badge {
  margin-left: auto;
  font-size: 8px;
  font-weight: 700;
  padding: 1.5px 5px;
  border-radius: 4px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  flex-shrink: 0;
}
.badge-core {
  background: rgba(239, 68, 68, 0.1);
  color: #f87171;
}
.badge-sales {
  background: rgba(37, 99, 235, 0.1);
  color: #4ade80;
}
.badge-config {
  background: rgba(59, 130, 246, 0.1);
  color: #60a5fa;
}
.badge-content {
  background: rgba(168, 85, 247, 0.1);
  color: #c084fc;
}
.badge-admin {
  background: rgba(234, 179, 8, 0.1);
  color: #facc15;
}
.badge-support {
  background: rgba(148, 163, 184, 0.1);
  color: #94a3b8;
}
.badge-marketing {
  background: rgba(236, 72, 153, 0.1);
  color: #f472b6;
}
.sidebar-user { 
    margin-top: auto; 
    padding: 12px; 
    border-radius: 12px; 
    background: rgba(255, 255, 255, 0.03); 
    border: 1px solid rgba(255, 255, 255, 0.05); 
    display: flex; 
    gap: 10px; 
    align-items: center; 
    flex-shrink: 0; 
}
.user-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #38bdf8, var(--admin-primary)); color: #fff; display: grid; place-items: center; overflow: hidden; font-weight: 700; font-size: 13px; }
.user-avatar-img { width: 100%; height: 100%; object-fit: cover; }
.user-info { min-width: 0; }
.sidebar-user .user-name { margin: 0; color: #fff; font-size: 13.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sidebar-user .user-role { margin: 2px 0 0; color: rgba(255, 255, 255, 0.5); font-size: 11px; }
.sidebar-logout-btn { 
    margin-left: auto; 
    width: 28px; 
    height: 28px; 
    border-radius: 6px; 
    border: 1px solid rgba(239,68,68,.25); 
    background: rgba(239,68,68,.08); 
    color: #ef4444; 
    cursor: pointer; 
    display: inline-flex; 
    align-items: center; 
    justify-content: center;
    transition: all 0.2s ease;
}
.sidebar-logout-btn:hover {
    background: #ef4444;
    color: #fff;
    border-color: #ef4444;
}
.sidebar-logout-btn svg { width: 14px; height: 14px; }
.main { flex: 1; padding: 0 32px 32px; height: 100vh; overflow-y: auto; background: #f8fafc; }
.admin-topbar { 
    position: sticky; 
    top: 0; 
    z-index: 8; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    gap: 18px; 
    margin: 0 -32px 24px; 
    padding: 18px 32px; 
    width: calc(100% + 64px); 
    background: #ffffff; 
    border-bottom: 1px solid rgba(15,23,42,.05); 
    box-shadow: 0 2px 10px rgba(15,23,42,0.02);
}
.admin-topbar-title h2 { margin: 0; font-size: 22px; font-weight: 700; color: #0f172a; }
.admin-topbar-title p { margin: 4px 0 0; color: #64748b; font-size: 12.5px; }
.admin-topbar-actions { display: flex; align-items: center; gap: 10px; }
.topbar-home-link { 
    display: inline-flex; 
    align-items: center; 
    justify-content: center;
    gap: 6px; 
    height: 44px; 
    padding: 0 18px; 
    border-radius: 999px; 
    border: 1px solid rgba(37, 99, 235, 0.2); 
    background: rgba(37, 99, 235, 0.04); 
    color: #2563eb; 
    text-decoration: none; 
    font-size: 13px; 
    font-weight: 600; 
    line-height: 1;
    white-space: nowrap;
    transform: translateY(0);
    transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}
.topbar-home-link svg { width: 16px; height: 16px; flex-shrink: 0; }
.topbar-home-link:hover { background: #2563eb; color: #ffffff; border-color: #2563eb; box-shadow: 0 10px 22px rgba(37, 99, 235, 0.22); transform: translateY(-2px); }
.topbar-home-link:active { transform: translateY(0); box-shadow: 0 4px 10px rgba(37, 99, 235, 0.16); }
.topbar-icon-group { display: flex; align-items: center; gap: 10px; height: 44px; }
.topbar-popover { position: relative; display: flex; align-items: center; }
.topbar-icon-button { 
    width: 44px; 
    height: 44px; 
    border-radius: 50%; 
    border: 1px solid rgba(15,23,42,.12); 
    background: #ffffff; 
    color: #475569; 
    display: grid; 
    place-items: center; 
    cursor: pointer; 
    position: relative; 
    transform: translateY(0) scale(1);
    transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}
.topbar-icon-button:hover,
.topbar-icon-button.active {
    background: #eef2ff;
    color: #2563eb;
    border-color: rgba(37, 99, 235, 0.35);
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.16);
    transform: translateY(-2px);
}
.topbar-icon-button:active {
    transform: translateY(0) scale(0.96);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.12);
}
.topbar-icon-button:hover svg {
    transform: scale(1.08);
}
.topbar-icon-button svg { width: 20px; height: 20px; }
.topbar-icon-button svg { transition: transform 0.18s ease; }
.icon-flag { font-size: 12px; font-weight: 700; letter-spacing: .4px; line-height: 1; }
.icon-badge { position: absolute; top: 3px; right: 3px; min-width: 18px; height: 18px; border-radius: 999px; background: #ef4444; color: #fff; font-size: 10px; font-weight: 700; display: grid; place-items: center; padding: 0 5px; border: 2px solid #fff; line-height: 1; animation: topbarBadgePulse 1.45s ease-in-out infinite; transform-origin: center; }
.topbar-icon-button:hover .icon-badge {
    animation: topbarBadgePop 0.42s ease both, topbarBadgePulse 1.45s ease-in-out 0.42s infinite;
}
.topbar-dropdown { 
    position: absolute; 
    top: calc(100% + 8px); 
    right: 0; 
    width: 280px; 
    border: 1px solid rgba(15,23,42,.08); 
    border-radius: 12px; 
    background: #ffffff; 
    box-shadow: 0 12px 30px rgba(15,23,42,.08); 
    padding: 8px; 
    z-index: 20; 
}
.compact-menu { width: 150px; }
.apps-dropdown { width: 190px; display: grid; gap: 6px; }
.notify-menu { width: 320px; }
.notify-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding: 4px 4px 8px; border-bottom: 1px solid #edf2f7; }
.notify-mark-read { border: 0; background: transparent; color: #2563eb; font-size: 12px; cursor: pointer; }
.notify-empty { color: #64748b; font-size: 13px; padding: 14px 6px; }
.notify-item { width: 100%; border: 1px solid #eef2f7; background: #fff; border-radius: 10px; padding: 9px 10px; margin-top: 6px; text-align: left; cursor: pointer; display: grid; gap: 2px; }
.notify-item.unread { border-color: #c7d2fe; background: #eef2ff; }
.notify-title { font-size: 13px; color: #0f172a; font-weight: 600; }
.notify-time { font-size: 11px; color: #64748b; }
.topbar-divider { width: 1px; height: 32px; background: rgba(15,23,42,.08); }
.topbar-user { position: relative; display: flex; align-items: center; }
.topbar-user-btn { 
    display: inline-flex; 
    align-items: center; 
    gap: 10px; 
    height: 44px;
    padding: 4px 12px 4px 4px; 
    border-radius: 999px; 
    border: 1px solid rgba(15,23,42,.08); 
    background: #ffffff; 
    cursor: pointer; 
    line-height: 1;
    transform: translateY(0);
    transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
}
.topbar-user-btn:hover { background: #f8fafc; border-color: rgba(37, 99, 235, 0.22); box-shadow: 0 10px 22px rgba(15, 23, 42, 0.10); transform: translateY(-2px); }
.topbar-user-btn:active { transform: translateY(0); box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08); }
.topbar-user-btn svg { width: 15px; height: 15px; color: #64748b; }
.topbar-user-btn .user-avatar {
    width: 36px;
    height: 36px;
    font-size: 13px;
    flex-shrink: 0;
}
.user-meta { display: flex; flex-direction: column; align-items: flex-start; }
.user-name { font-size: 13.5px; font-weight: 600; color: #0f172a; }
.user-role { font-size: 11px; color: #64748b; }
.user-dropdown { position: absolute; right: 0; top: calc(100% + 8px); width: 240px; padding: 10px; border-radius: 12px; background: #fff; border: 1px solid rgba(15,23,42,.08); box-shadow: 0 12px 30px rgba(15,23,42,.08); z-index: 20; }
.user-dropdown-header { display: flex; align-items: center; gap: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(15,23,42,.08); margin-bottom: 10px; }
.user-dropdown-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #38bdf8, #2563eb); color: #fff; display: grid; place-items: center; overflow: hidden; font-weight: 700; }
.dropdown-name { margin: 0; font-size: 14px; font-weight: 700; color: #0f172a; }
.dropdown-email { margin: 2px 0 0; font-size: 11px; color: #64748b; }
.user-dropdown-list { display: grid; gap: 6px; margin-bottom: 8px; }
.dropdown-item { width: 100%; text-align: center; padding: 10px 12px; border-radius: 10px; border: none; background: #f8fafc; color: #0f172a; font-size: 13px; cursor: pointer; transition: all 0.2s ease; }
.dropdown-item.compact { text-align: left; }
.dropdown-item.active { background: #e0e7ff; color: #1d4ed8; font-weight: 700; }
.dropdown-item:hover { background: #eef2ff; }
.dropdown-item.sign-out { background: #fef2f2; color: #dc2626; transition: background-color 0.2s, color 0.2s; }
.dropdown-item.sign-out:hover { background: #fee2e2; color: #b91c1c; }
.logout-item { display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.logout-item svg { width: 15px; height: 15px; }

@keyframes topbarBadgePulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.30);
    transform: scale(1);
  }
  50% {
    box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
    transform: scale(1.08);
  }
}

@keyframes topbarBadgePop {
  0% { transform: scale(1); }
  45% { transform: scale(1.28); }
  100% { transform: scale(1.08); }
}
.admin-layout.dark .main, .admin-layout.dark .admin-topbar { background: #0f172a; border-color: rgba(255,255,255,.05); }
.admin-layout.dark .admin-topbar-title h2 { color: #fff; }
.admin-layout.dark .admin-topbar-title p, .admin-layout.dark .user-role { color: #94a3b8; }
.admin-layout.dark .topbar-icon-button, .admin-layout.dark .topbar-user-btn, .admin-layout.dark .topbar-dropdown, .admin-layout.dark .user-dropdown { background: #0f172a; border-color: rgba(255,255,255,.08); color: #fff; }
.admin-layout.dark .topbar-home-link { background: #0f172a; border-color: rgba(255,255,255,.08); color: #38bdf8; }
.admin-layout.dark .dropdown-item, .admin-layout.dark .notify-item { background: #1e293b; border-color: rgba(255,255,255,.08); color: #e2e8f0; }
.admin-layout.dark .notify-item.unread, .admin-layout.dark .dropdown-item.active, .admin-layout.dark .dropdown-item:hover { background: rgba(37, 99, 235, 0.15); }

.admin-layout.intro-active {
  animation: adminIntroBase 0.42s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.admin-layout.intro-active .sidebar {
  animation: adminIntroSidebar 0.36s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.admin-layout.intro-active .admin-topbar {
  animation: adminIntroTopbar 0.32s cubic-bezier(0.16, 1, 0.3, 1) 0.06s both;
}

.admin-layout.intro-active .main > :not(.admin-topbar) {
  animation: adminIntroContent 0.36s cubic-bezier(0.16, 1, 0.3, 1) 0.08s both;
}

@keyframes adminIntroBase {
  0% {
    background: #eef4ff;
  }
  100% {
    background: #f8fafc;
  }
}

@keyframes adminIntroSidebar {
  0% {
    opacity: 0;
    transform: translate3d(-34px, 0, 0);
    filter: saturate(0.82);
  }
  100% {
    opacity: 1;
    transform: translate3d(0, 0, 0);
    filter: saturate(1);
  }
}

@keyframes adminIntroTopbar {
  0% {
    opacity: 0;
    transform: translate3d(0, -18px, 0);
  }
  100% {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

@keyframes adminIntroContent {
  0% {
    opacity: 0;
    transform: translate3d(0, 22px, 0) scale(0.992);
  }
  100% {
    opacity: 1;
    transform: translate3d(0, 0, 0) scale(1);
  }
}

/* Admin stat cards: one shared blue system for every admin page. */
.main :deep(.stats),
.main :deep(.stats-grid),
.main :deep(.stats-row),
.main :deep(.metrics-grid) {
    --admin-stat-blue-start: #2148bf;
    --admin-stat-blue-mid: #2f66df;
    --admin-stat-blue-end: #3b82f6;
    --admin-stat-circle: rgba(255, 255, 255, 0.14);
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    align-items: stretch;
}

.main :deep(.stat-card),
.main :deep(.stat-card.stat-blue),
.main :deep(.stat-card.stat-teal),
.main :deep(.stat-card.stat-orange),
.main :deep(.stat-card.stat-green),
.main :deep(.stat-card.stat-purple),
.main :deep(.stat-card.stat-amber),
.main :deep(.stat-card.stat-active),
.main :deep(.stat-card.stat-budget),
.main :deep(.stat-card.stat-card-gradient),
.main :deep(.stat-card.dark-stat),
.main :deep(.stat-card.highlight),
.main :deep(.stat-card.gold),
.main :deep(.stat-card.blue),
.main :deep(.stat-card.violet),
.main :deep(.stat-card.green),
.main :deep(.metric-card),
.main :deep(.metric-card.blue),
.main :deep(.metric-card.amber),
.main :deep(.metric-card.teal),
.main :deep(.metric-card.violet) {
    height: 160px !important;
    min-height: 160px !important;
    border-radius: 8px;
    border: 1px solid rgba(37, 99, 235, 0.08);
    padding: 26px 28px;
    background: linear-gradient(135deg, var(--admin-stat-blue-start) 0%, var(--admin-stat-blue-mid) 58%, var(--admin-stat-blue-end) 100%) !important;
    color: #fff;
    box-shadow: 0 14px 30px rgba(37, 99, 235, 0.18);
    position: relative;
    overflow: hidden;
}

.main :deep(.stat-card::after),
.main :deep(.metric-card::after) {
    content: '';
    position: absolute;
    width: 188px;
    height: 188px;
    border-radius: 999px;
    top: -68px;
    right: -36px;
    background: var(--admin-stat-circle);
    pointer-events: none;
}

.main :deep(.stat-card p),
.main :deep(.stat-label),
.main :deep(.stat-card-tag),
.main :deep(.stat-card span:first-child),
.main :deep(.metric-card span),
.main :deep(.metric-card small) {
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 12px !important;
    line-height: 1.25;
    font-weight: 800 !important;
    letter-spacing: 0.03em !important;
    text-transform: uppercase;
}

.main :deep(.stat-card b),
.main :deep(.stat-value),
.main :deep(.stat-number),
.main :deep(.big-growth),
.main :deep(.metric-card strong) {
    color: #fff !important;
    font-size: 34px !important;
    line-height: 1 !important;
    font-weight: 800 !important;
}

.main :deep(.stat-icon:not(svg)),
.main :deep(.stat-icon-wrap),
.main :deep(.stat-icon-wrapper),
.main :deep(.metric-icon) {
    width: 48px !important;
    height: 48px !important;
    min-width: 48px;
    border-radius: 10px !important;
    background: rgba(255, 255, 255, 0.18) !important;
    color: #fff !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.main :deep(.stat-icon svg),
.main :deep(svg.stat-icon),
.main :deep(.stat-icon-wrap svg),
.main :deep(.stat-icon-wrapper svg),
.main :deep(.metric-icon svg) {
    width: 24px !important;
    height: 24px !important;
    stroke: currentColor;
    background: transparent !important;
    border-radius: 0 !important;
    min-width: 0;
}

.main :deep(.stat-card:has(.stat-icon-wrapper) .stat-data) {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.main :deep(.stat-card:has(.stat-icon-wrapper) .stat-label) {
    margin: 0 !important;
}

.main :deep(.stat-card:has(.stat-icon-wrapper) .stat-number-row) {
    align-items: center;
    gap: 14px;
}

.main :deep(.badge-up),
.main :deep(.badge-down),
.main :deep(.badge-neutral),
.main :deep(.stat-trend),
.main :deep(.stat-sub),
.main :deep(.stat-card-btn) {
    background: rgba(255, 255, 255, 0.92) !important;
    color: #1d4ed8 !important;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 800;
}

.main :deep(.stat-card:hover),
.main :deep(.metric-card:hover) {
    transform: none !important;
}

@media (prefers-reduced-motion: reduce) {
  .admin-layout.intro-active,
  .admin-layout.intro-active .sidebar,
  .admin-layout.intro-active .admin-topbar,
  .admin-layout.intro-active .main > :not(.admin-topbar) {
    animation-duration: 0.01ms !important;
  }
}
</style>
