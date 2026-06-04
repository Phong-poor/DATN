<template>
  <div
    :class="[
      'admin-layout',
      `density-${appearance.density}`,
      `width-${appearance.content_width}`,
      `sidebar-${appearance.sidebar_style}`,
      `anim-${appearance.animation_level}`,
    ]"
    :style="adminVars"
  >
    <aside class="sidebar">
      <div class="sidebar-logo">
        <img
          src="/ChatGPT_Image_08_35_43_4_thg_6__2026-removebg-preview.png"
          alt="Predator Group"
          class="admin-logo-img"
        />
      </div>

      <div class="menu-section">
        <p class="menu-label">MAIN MENU</p>
        <div v-for="item in menuConfig" :key="item.label || item.path" class="menu-wrapper">
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
          <p class="user-role">Quản trị viên</p>
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
                <span class="user-role">Administrator</span>
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
                <button class="dropdown-item" type="button" @click="navigateUserMenu('/admin/profile')">My Profile</button>
                <button class="dropdown-item" type="button" @click="navigateUserMenu('/admin/settings')">Settings</button>
                <button class="dropdown-item" type="button" @click="navigateUserMenu('/admin/activity-log')">Activity Log</button>
                <button class="dropdown-item" type="button" @click="navigateUserMenu('/admin/billing')">Billing</button>
              </div>
              <button class="dropdown-item sign-out logout-item" type="button" @click="handleLogout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                  <polyline points="16 17 21 12 16 7" />
                  <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                <span>Sign Out</span>
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
import AdminChatManager from '@/components/Admin/Layout/AdminChatManager.vue'
import { storageUrl } from '@/services/urls'
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

const userMenuRef = ref(null)
const langMenuRef = ref(null)
const appsMenuRef = ref(null)
const notifyMenuRef = ref(null)

const currentLocale = ref(localStorage.getItem('admin-locale') || 'vi')
const localeBadge = computed(() => (currentLocale.value === 'en' ? 'US' : 'VN'))

const appearance = ref({
  primary_color: '#2563eb',
  accent_color: '#7c3aed',
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
      { path: '/admin/products', label: 'Sản phẩm', badge: 'CORE' },
      { path: '/admin/categories', label: 'Danh mục', badge: 'CONFIG' },
      { path: '/admin/brands', label: 'Thương hiệu', badge: 'CONFIG' },
      { path: '/admin/variants', label: 'Màu & biến thể', badge: 'CONFIG' },
    ]
  },
  {
    label: 'Bán hàng',
    icon: ShoppingCart,
    isDropdown: true,
    children: [
      { path: '/admin/orders', label: 'Đơn hàng', badge: 'CORE' },
      { path: '/admin/promotions', label: 'Khuyến mãi', badge: 'SALES' },
      { path: '/admin/combos', label: 'Quản lý Combo', badge: 'SALES' },
      { path: '/admin/affiliates', label: 'Affiliate', badge: 'MARKETING' },
    ]
  },
  {
    label: 'Nội dung',
    icon: Newspaper,
    isDropdown: true,
    children: [
      { path: '/admin/news', label: 'Bài viết', badge: 'CONTENT' },
      { path: '/admin/reviews', label: 'Bình luận', badge: 'CONTENT' },
    ]
  },
  {
    label: 'Người dùng',
    icon: Users,
    isDropdown: true,
    children: [
      { path: '/admin/users', label: 'User', badge: 'ADMIN' },
      { path: '/admin/contacts', label: 'Liên hệ', badge: 'SUPPORT' },
    ]
  },
  { path: '/admin/banners', label: 'Banner', icon: Image },
  { path: '/admin/activity-log', label: 'Nhật ký hệ thống', icon: Activity },
]

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
  { label: 'Sản phẩm', path: '/admin/products' },
  { label: 'Đơn hàng', path: '/admin/orders' },
  { label: 'Khuyến mãi', path: '/admin/promotions' },
  { label: 'Banner', path: '/admin/banners' },
  { label: 'User', path: '/admin/users' },
  { label: 'Settings', path: '/admin/settings' },
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

const userName = computed(() => 'Predator Group')
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
  localStorage.setItem('admin-locale', locale)
  document.documentElement.lang = locale
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
        path: '/admin/activity-log',
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
        { id: 'seed-1', title: 'Có đơn hàng mới cần xử lý', time: 'Vừa xong', read: savedList.find(n => n.id === 'seed-1')?.read || false, path: '/admin/orders' },
        { id: 'seed-2', title: 'Có liên hệ mới từ khách hàng', time: '5 phút trước', read: savedList.find(n => n.id === 'seed-2')?.read || false, path: '/admin/contacts' },
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
  clearAuth()
  router.push('/login')
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

onMounted(async () => {
  document.addEventListener('mousedown', handleClickOutside)
  window.addEventListener('user-updated', refreshUser)
  window.addEventListener('admin-settings-updated', handleSettingsUpdated)
  document.documentElement.lang = currentLocale.value
  await loadAppearanceSettings()
  hydrateNotifications()
  await loadNotifications()
})

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside)
  window.removeEventListener('user-updated', refreshUser)
  window.removeEventListener('admin-settings-updated', handleSettingsUpdated)
})
</script>

<style scoped>
* { box-sizing: border-box; }
.admin-layout { display: flex; height: 100vh; overflow: hidden; background: #f5f7fb; font-family: Inter, sans-serif; }
.admin-layout.dark { background: #0f172a; }
.sidebar { width: 260px; min-width: 260px; background: #071d52; padding: 20px 14px; height: 100vh; overflow: hidden; position: relative; display: flex; flex-direction: column; }
.sidebar-logo { display: flex; align-items: center; justify-content: center; padding: 2px 4px 16px; border-bottom: 1px solid #1e293b; margin-bottom: 4px; }
.admin-logo-img { width: 220px; max-width: 100%; height: 74px; object-fit: contain; display: block; filter: drop-shadow(0 8px 18px rgba(0,0,0,.24)); }
.menu-label { font-size: 11px; font-weight: 700; color: rgba(248, 250, 252, 0.8); letter-spacing: 1px; padding: 12px 12px 8px; margin: 0; }
.menu-section { display: flex; flex-direction: column; gap: 6px; flex: 1; min-height: 0; overflow-y: auto; padding-bottom: 10px; }
.menu-section { -ms-overflow-style: none; scrollbar-width: none; }
.menu-section::-webkit-scrollbar { width: 0; height: 0; display: none; }
a { text-decoration: none; }
.item { padding: 13px 16px; border-radius: 12px; color: #f8fafc; font-size: 14.5px; font-weight: 500; display: flex; align-items: center; gap: 12px; min-height: 46px; }
.item-icon { width: 19px; height: 19px; flex-shrink: 0; stroke-width: 2; }
.item:hover { background: rgba(255, 255, 255, 0.08); }
.item.active { background: linear-gradient(135deg, #10b981, #059669); font-weight: 600; }
.dropdown-toggle {
  width: 100%;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
}
.dropdown-toggle.parent-active {
  color: #34d399;
}
.chevron-icon {
  margin-left: auto;
  width: 16px;
  height: 16px;
  transition: transform 0.25s ease;
  color: rgba(248, 250, 252, 0.6);
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
  padding-left: 20px;
}
.dropdown-group.is-open .submenu {
  max-height: 300px;
  transition: max-height 0.25s ease-in-out;
  margin-top: 4px;
  margin-bottom: 4px;
}
.submenu-item {
  padding: 10px 16px;
  border-radius: 10px;
  color: rgba(248, 250, 252, 0.75);
  font-size: 13.5px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 38px;
  transition: all 0.2s ease;
}
.submenu-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
}
.submenu-item.active {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
  font-weight: 600;
}
.bullet-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background-color: rgba(248, 250, 252, 0.4);
  transition: all 0.2s ease;
  flex-shrink: 0;
}
.submenu-item.active .bullet-dot {
  background-color: #34d399;
  box-shadow: 0 0 8px #34d399;
}
.submenu-badge {
  margin-left: auto;
  font-size: 8px;
  font-weight: 700;
  padding: 1px 4px;
  border-radius: 3px;
  letter-spacing: 0.3px;
  flex-shrink: 0;
}
.badge-core {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
}
.badge-sales {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
}
.badge-config {
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
}
.badge-content {
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
}
.badge-admin {
  background: rgba(234, 179, 8, 0.15);
  color: #facc15;
}
.badge-support {
  background: rgba(148, 163, 184, 0.15);
  color: #94a3b8;
}
.badge-marketing {
  background: rgba(236, 72, 153, 0.15);
  color: #f472b6;
}
.sidebar-user { margin-top: 10px; padding: 12px; border-radius: 16px; background: rgba(255,255,255,.08); display: flex; gap: 10px; align-items: center; flex-shrink: 0; }
.user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #38bdf8, var(--admin-primary)); color: #fff; display: grid; place-items: center; overflow: hidden; font-weight: 700; }
.user-avatar-img { width: 100%; height: 100%; object-fit: cover; }
.user-info { min-width: 0; }
.sidebar-user .user-name { margin: 0; color: #fff; font-size: 14px; font-weight: 700; }
.sidebar-user .user-role { margin: 0; color: #d1d5db; font-size: 12px; }
.sidebar-logout-btn { margin-left: auto; width: 34px; height: 34px; border-radius: 50%; border: 1px solid rgba(239,68,68,.35); background: rgba(239,68,68,.12); color: #ef4444; cursor: pointer; }
.sidebar-logout-btn { display: inline-flex; align-items: center; justify-content: center; }
.sidebar-logout-btn svg { width: 16px; height: 16px; }
.main { flex: 1; padding: 0 30px 30px; height: 100vh; overflow-y: auto; }
.admin-topbar { position: sticky; top: 0; z-index: 8; display: flex; justify-content: space-between; align-items: center; gap: 18px; margin: 0 -30px 24px; padding: 20px 30px; width: calc(100% + 60px); background: #fff; border-bottom: 1px solid rgba(15,23,42,.08); }
.admin-topbar-title h2 { margin: 0; font-size: 24px; color: #0f172a; }
.admin-topbar-title p { margin: 6px 0 0; color: #64748b; font-size: 13px; }
.admin-topbar-actions { display: flex; align-items: center; gap: 12px; }
.topbar-home-link { display: inline-flex; align-items: center; gap: 6px; height: 40px; padding: 0 14px; border-radius: 10px; border: 1px solid #dbe2ea; background: #fff; color: #2563eb; text-decoration: none; font-size: 13px; font-weight: 700; }
.topbar-home-link svg { width: 14px; height: 14px; }
.topbar-home-link:hover { background: #eff6ff; border-color: #bfdbfe; }
.topbar-icon-group { display: flex; align-items: center; gap: 10px; }
.topbar-popover { position: relative; }
.topbar-icon-button { width: 44px; height: 44px; border-radius: 50%; border: 1px solid rgba(15,23,42,.12); background: #fff; color: #1f2937; display: grid; place-items: center; cursor: pointer; position: relative; }
.topbar-icon-button svg { width: 20px; height: 20px; }
.icon-flag { font-size: 12px; font-weight: 700; letter-spacing: .5px; }
.icon-badge { position: absolute; top: 8px; right: 8px; min-width: 16px; height: 16px; border-radius: 999px; background: #ef4444; color: #fff; font-size: 10px; display: grid; place-items: center; padding: 0 4px; }
.topbar-dropdown { position: absolute; top: calc(100% + 10px); right: 0; width: 280px; border: 1px solid rgba(15,23,42,.1); border-radius: 12px; background: #fff; box-shadow: 0 12px 26px rgba(15,23,42,.14); padding: 8px; z-index: 20; }
.compact-menu { width: 150px; }
.apps-dropdown { width: 190px; display: grid; gap: 6px; }
.notify-menu { width: 320px; }
.notify-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding: 4px 4px 8px; border-bottom: 1px solid #edf2f7; }
.notify-mark-read { border: 0; background: transparent; color: #4f46e5; font-size: 12px; cursor: pointer; }
.notify-empty { color: #64748b; font-size: 13px; padding: 14px 6px; }
.notify-item { width: 100%; border: 1px solid #eef2f7; background: #fff; border-radius: 10px; padding: 9px 10px; margin-top: 6px; text-align: left; cursor: pointer; display: grid; gap: 2px; }
.notify-item.unread { border-color: #c7d2fe; background: #eef2ff; }
.notify-title { font-size: 13px; color: #0f172a; font-weight: 600; }
.notify-time { font-size: 11px; color: #64748b; }
.topbar-divider { width: 1px; height: 36px; background: rgba(15,23,42,.12); }
.topbar-user { position: relative; }
.topbar-user-btn { display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 999px; border: 1px solid rgba(15,23,42,.12); background: #fff; cursor: pointer; }
.topbar-user-btn svg { width: 16px; height: 16px; }
.user-meta { display: flex; flex-direction: column; align-items: flex-start; }
.user-name { font-size: 14px; font-weight: 700; color: #0f172a; }
.user-role { font-size: 12px; color: #64748b; }
.user-dropdown { position: absolute; right: 0; top: calc(100% + 10px); width: 240px; padding: 10px; border-radius: 12px; background: #fff; border: 1px solid rgba(15,23,42,.1); box-shadow: 0 12px 26px rgba(15,23,42,.14); z-index: 20; }
.user-dropdown-header { display: flex; align-items: center; gap: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(15,23,42,.08); margin-bottom: 10px; }
.user-dropdown-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #38bdf8, #2563eb); color: #fff; display: grid; place-items: center; overflow: hidden; font-weight: 700; }
.dropdown-name { margin: 0; font-size: 14px; font-weight: 700; color: #0f172a; }
.dropdown-email { margin: 2px 0 0; font-size: 11px; color: #64748b; }
.user-dropdown-list { display: grid; gap: 6px; margin-bottom: 8px; }
.dropdown-item { width: 100%; text-align: center; padding: 10px 12px; border-radius: 10px; border: none; background: #f8fafc; color: #0f172a; font-size: 13px; cursor: pointer; }
.dropdown-item.compact { text-align: left; }
.dropdown-item.active { background: #e0e7ff; color: #4338ca; font-weight: 700; }
.dropdown-item:hover { background: #eef2ff; }
.dropdown-item.sign-out { background: #fef2f2; color: #dc2626; transition: background-color 0.2s, color 0.2s; }
.dropdown-item.sign-out:hover { background: #fee2e2; color: #b91c1c; }
.logout-item { display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.logout-item svg { width: 15px; height: 15px; }
.admin-layout.dark .main, .admin-layout.dark .admin-topbar { background: #111827; border-color: rgba(255,255,255,.08); }
.admin-layout.dark .admin-topbar-title h2 { color: #fff; }
.admin-layout.dark .admin-topbar-title p, .admin-layout.dark .user-role { color: #94a3b8; }
.admin-layout.dark .topbar-icon-button, .admin-layout.dark .topbar-user-btn, .admin-layout.dark .topbar-dropdown, .admin-layout.dark .user-dropdown { background: #0f172a; border-color: rgba(255,255,255,.12); color: #fff; }
.admin-layout.dark .topbar-home-link { background: #0f172a; border-color: rgba(255,255,255,.12); color: #93c5fd; }
.admin-layout.dark .dropdown-item, .admin-layout.dark .notify-item { background: #111827; border-color: rgba(255,255,255,.12); color: #e2e8f0; }
.admin-layout.dark .notify-item.unread, .admin-layout.dark .dropdown-item.active, .admin-layout.dark .dropdown-item:hover { background: rgba(79,70,229,.22); }
</style>
