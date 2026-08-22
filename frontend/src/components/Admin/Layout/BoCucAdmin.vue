<template>
  <div
    :class="[
      'admin-layout',
      `density-${appearance.density}`,
      `width-${appearance.content_width}`,
      `sidebar-${appearance.sidebar_style}`,
      `anim-${appearance.animation_level}`,
      `theme-${adminTheme}`,
      adminTheme === 'dark' && 'dark',
      sidebarCollapsed && 'sidebar-collapsed',
      adminIntroActive && 'intro-active',
      adminHeaderHidden && 'admin-header-hidden',
    ]"
    :style="adminVars"
  >
    <aside class="sidebar">
      <button type="button" class="sidebar-collapse-btn"
        :aria-label="sidebarCollapsed ? 'Mở rộng thanh quản trị' : 'Thu gọn thanh quản trị'"
        :title="sidebarCollapsed ? 'Mở rộng menu' : 'Thu gọn menu'" @click="toggleSidebar">
        <ChevronsRight v-if="sidebarCollapsed" />
        <ChevronsLeft v-else />
      </button>

      <div class="sidebar-logo">
        <img src="/ChatGPT_Image_08_35_43_4_thg_6__2026-removebg-preview.png" alt="NextGen Group"
          class="admin-logo-img" />
      </div>

      <div class="menu-section">
        <p class="menu-label">Menu chính</p>
        <div v-for="item in filteredMenuConfig" :key="item.label || item.path" class="menu-wrapper">
          <!-- Normal Link -->
          <router-link v-if="!item.isDropdown" :to="item.path">
            <div :class="['item', isMenuPathActive(item.path) && 'active']">
              <component :is="item.icon" class="item-icon" />
              <span class="menu-text">{{ sentenceCaseLabel(item.label) }}</span>
            </div>
          </router-link>

          <!-- Dropdown Group -->
          <div v-else class="dropdown-group" :class="{ 'is-open': dropdownStates[item.label] }">
            <button type="button" class="item dropdown-toggle" :class="{ 'parent-active': isParentActive(item) }"
              @click="toggleDropdown(item.label)">
              <component :is="item.icon" class="item-icon" />
              <span class="menu-text">{{ sentenceCaseLabel(item.label) }}</span>
              <ChevronDown class="chevron-icon" />
            </button>

            <div class="submenu">
              <router-link v-for="sub in item.children" :key="sub.path" :to="sub.path">
                <div :class="['submenu-item', isMenuPathActive(sub.path) && 'active']">
                  <span class="bullet-dot"></span>
                  <span class="submenu-text">{{ sentenceCaseLabel(sub.label) }}</span>
                  <span v-if="sub.badge" translate="no"
                    :class="['submenu-badge', `badge-${sub.badge.toLowerCase().replace(/\s+/g, '-')}`]">
                    {{ sentenceCaseLabel(sub.badge) }}
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" y1="12" x2="9" y2="12" />
          </svg>
        </button>
      </div>
    </aside>

    <main ref="adminMainRef" class="main" @scroll.passive="handleAdminScroll">
      <section class="admin-topbar" :class="{ 'header-hidden': adminHeaderHidden }">
        <div class="admin-topbar-title">
          <h2>{{ pageTitle }}</h2>
          <p>Quản lý nội dung và điều hành hệ thống</p>
        </div>

        <div class="attendance-topbar-center">
          <div class="attendance-topbar-clock">
            <span>{{ adminClockDate }}</span>
            <strong>{{ adminClockTime }}</strong>
          </div>
          <button type="button" class="topbar-attendance-link" @click="quickAttendanceOpen = true">
            <Camera />
            <span>Chấm công</span>
          </button>
        </div>

        <div class="admin-topbar-actions">
          <router-link to="/" class="topbar-home-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
              <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            Trang chủ
          </router-link>
          <div class="topbar-icon-group">
            <button class="topbar-icon-button theme-toggle-button" type="button"
              :aria-label="adminTheme === 'dark' ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối'"
              :title="adminTheme === 'dark' ? 'Giao diện sáng' : 'Giao diện tối'" @click="toggleAdminTheme">
              <svg v-if="adminTheme === 'dark'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                aria-hidden="true">
                <circle cx="12" cy="12" r="4" />
                <path
                  d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41" />
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
              </svg>
            </button>
            <div class="topbar-popover" ref="notifyMenuRef">
              <button class="topbar-icon-button" :class="{ 'notification-alerting': unreadCount > 0 }" type="button"
                aria-label="Thông báo" @click="toggleNotifyMenu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
                  <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                </svg>
                <span class="icon-badge" v-if="unreadCount > 0">{{ unreadCount }}</span>
              </button>
              <div v-if="notifyMenuOpen" class="topbar-dropdown notify-menu">
                <div class="notify-head">
                  <b>Thông báo</b>
                  <button class="notify-mark-read" type="button" @click="markAllNotificationsRead">Đánh dấu đã
                    đọc</button>
                </div>
                <div class="notify-scroll">
                  <div v-if="!notifications.some(n => !n.read)" class="notify-empty">Chưa có thông báo mới</div>
                  <button v-for="item in notifications.filter(n => !n.read)" :key="item.id" type="button"
                    class="notify-item" :class="{ unread: !item.read }" @click="openNotification(item)">
                    <span class="notify-title">{{ item.title }}</span>
                    <span class="notify-time">{{ item.time }}</span>
                  </button>
                </div>
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
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9" />
              </svg>
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
                <button class="dropdown-item" type="button" @click="navigateUserMenu('/admin/ho-so-quan-tri')">Hồ sơ của
                  tôi</button>
                <button class="dropdown-item" type="button" @click="navigateUserMenu('/admin/cai-dat-he-thong')">Cài
                  đặt</button>
              </div>
              <button class="dropdown-item sign-out logout-item" type="button" @click="handleLogout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                  stroke-linejoin="round">
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

      <ChamCongNhanhModal v-if="quickAttendanceOpen" @close="quickAttendanceOpen = false"
        @success="handleQuickAttendanceSuccess" />

      <router-view v-slot="{ Component }">
        <div :key="routeKey" class="admin-page-shell">
          <component :is="Component" />
        </div>
      </router-view>
    </main>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { clearAuth, getUser, normalizeAuthUser, updateUser } from '@/services/auth'
import ChamCongNhanhModal from '@/components/Admin/Layout/ChamCongNhanhModal.vue'
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
  Coins,
  Camera,
  ClipboardCheck,
  ChevronsLeft,
  ChevronsRight,
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()

const refreshCounter = ref(0)
const routeKey = computed(() => route.fullPath + '-' + refreshCounter.value)
const adminMainRef = ref(null)
const adminHeaderHidden = ref(false)
let lastAdminScrollTop = 0

function handleAdminScroll(event) {
  const currentScrollTop = Math.max(0, event.currentTarget?.scrollTop || 0)

  if (currentScrollTop <= 12) {
    adminHeaderHidden.value = false
  } else if (Math.abs(currentScrollTop - lastAdminScrollTop) >= 6) {
    // Nội dung đi lên: ẩn header. Nội dung kéo xuống: hiện header.
    adminHeaderHidden.value = currentScrollTop > lastAdminScrollTop
  }

  lastAdminScrollTop = currentScrollTop
}

watch(
  () => route.path,
  async () => {
    await nextTick()
    adminMainRef.value?.scrollTo({ top: 0, left: 0, behavior: 'instant' })
    adminHeaderHidden.value = false
    lastAdminScrollTop = 0
  }
)

const pageTitle = computed(() => route.meta.title || 'Bảng quản trị')
const user = ref(getUser() || {})
const userMenuOpen = ref(false)
const notifyMenuOpen = ref(false)
const quickAttendanceOpen = ref(false)
const storedAdminTheme = localStorage.getItem('admin-theme')
const adminTheme = ref(
  storedAdminTheme === 'dark' || storedAdminTheme === 'light'
    ? storedAdminTheme
    : (window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
)
const adminIntroActive = ref(false)
const sidebarCollapsed = ref(localStorage.getItem('admin-sidebar-collapsed') === 'true')
let adminIntroTimer = null
const adminClock = ref(new Date())
let adminClockTimer = null
let notificationRefreshTimer = null
let notificationAudioContext = null
let notificationAudioUnlocked = false
let pendingNotificationSound = false
const adminClockTime = computed(() =>
  adminClock.value.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
)
const adminClockDate = computed(() =>
  adminClock.value.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' })
)

function applyAdminTheme(theme) {
  document.documentElement.dataset.adminTheme = theme
  document.documentElement.style.colorScheme = theme
}

function toggleAdminTheme() {
  adminTheme.value = adminTheme.value === 'dark' ? 'light' : 'dark'
  localStorage.setItem('admin-theme', adminTheme.value)
  applyAdminTheme(adminTheme.value)
}

const userMenuRef = ref(null)
const notifyMenuRef = ref(null)

const sentenceCaseLabel = (value) => {
  const text = String(value || '').trim().replace(/\s+/g, ' ')
  if (!text) return ''
  return text.charAt(0).toLocaleUpperCase('vi-VN') + text.slice(1).toLocaleLowerCase('vi-VN')
}

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
  { path: '/admin/bang-dieu-khien', label: 'Tổng quan', icon: LayoutDashboard },
  {
    label: 'Thủ kho',
    icon: Package,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-san-pham', label: 'Sản phẩm', badge: 'THỦ KHO' },
      { path: '/admin/quan-ly-danh-muc', label: 'Danh mục', badge: 'THỦ KHO' },
      { path: '/admin/quan-ly-thuong-hieu', label: 'Thương hiệu', badge: 'THỦ KHO' },
      { path: '/admin/bien-the-san-pham', label: 'Màu & biến thể', badge: 'THỦ KHO' },
    ]
  },
  {
    label: 'Đơn hàng',
    icon: ShoppingCart,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-don-hang', label: 'Đơn hàng', badge: 'ĐƠN HÀNG' },
      { path: '/admin/thong-ke-doanh-so-nhan-vien', label: 'Doanh số nhân viên', badge: 'ĐƠN HÀNG' }
    ]
  },
  {
    label: 'Marketing',
    icon: TicketPercent,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-khuyen-mai', label: 'Khuyến mãi', badge: 'MARKETING' },
      { path: '/admin/gui-ma-sinh-nhat', label: 'Gửi mã sinh nhật', badge: 'MARKETING' },
      { path: '/admin/quan-ly-combo', label: 'Quản lý Combo', badge: 'MARKETING' },
      { path: '/admin/flash-sales', label: 'Flash Sale', badge: 'MARKETING' },
    ]
  },
  {
    label: 'Xu & Minigame',
    icon: Coins,
    isDropdown: true,
    children: [
      { path: '/admin/xu', label: 'Cấu hình Xu', badge: 'MINIGAME' },
      { path: '/admin/vong-quay', label: 'Vòng quay may mắn', badge: 'MINIGAME' },
      { path: '/admin/diem-danh', label: 'Quản lý Điểm danh', badge: 'MINIGAME' },
    ]
  },
  {
    label: 'Tiếp thị',
    icon: Handshake,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-tiep-thi', label: 'Affiliate', badge: 'TIẾP THỊ' },
    ]
  },
  {
    label: 'Biên tập viên',
    icon: Newspaper,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-tin-tuc', label: 'Bài viết', badge: 'BIÊN TẬP' },
      { path: '/admin/quan-ly-binh-luan', label: 'Bình luận', badge: 'BIÊN TẬP' },
      { path: '/admin/quan-ly-banner', label: 'Banner', badge: 'BIÊN TẬP' },
    ]
  },
  {
    label: 'Tư vấn viên',
    icon: Mail,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-lien-he', label: 'Liên hệ', badge: 'TƯ VẤN' },
      { path: '/admin/quan-ly-chat', label: 'Tin nhắn', badge: 'TƯ VẤN' },
    ]
  },
  {
    label: 'Tài khoản',
    icon: Users,
    isDropdown: true,
    children: [
      { path: '/admin/quan-ly-nguoi-dung', label: 'Người dùng', badge: 'ADMIN' },
      { path: '/admin/quan-ly-vai-tro', label: 'Vai trò & quyền', badge: 'ADMIN' },
      { path: '/admin/quan-ly-cham-cong', label: 'Quản lý chấm công', badge: 'ADMIN' },
      { path: '/admin/quan-ly-don-xin-nghi', label: 'Quản lý đơn nghỉ', badge: 'ADMIN', superAdminOnly: true },
    ]
  },
  { path: '/admin/xin-nghi-phep', label: 'Xin nghỉ phép', icon: ClipboardCheck },
  { path: '/admin/cham-cong-camera', label: 'Xác thực nhân viên', icon: Camera },
  { path: '/admin/nhat-ky-hoat-dong', label: 'Nhật ký hệ thống', icon: Activity },
]

const filteredMenuConfig = computed(() => {
  const userPerms = user.value?.cac_quyen || []
  const isAdmin = Boolean(user.value?.vaitro && user.value.vaitro !== 'user')
  const isSuperAdmin = String(user.value?.vaitro || '').toLowerCase() === 'admin'

  const hasPerm = (required) => {
    if (isSuperAdmin) return true
    if (!required) return true
    const list = Array.isArray(required) ? required : [required]
    return list.some(p => userPerms.includes(p))
  }

  const pathPermissionMap = {
    '/admin/quan-ly-san-pham': ['san_pham_xem', 'san_pham_sua', 'nhap_xuat_kho'],
    '/admin/quan-ly-danh-muc': ['danh_muc_xem', 'danh_muc_sua'],
    '/admin/quan-ly-thuong-hieu': ['thuong_hieu_xem', 'thuong_hieu_sua'],
    '/admin/bien-the-san-pham': ['bien_the_xem', 'bien_the_sua'],

    '/admin/quan-ly-don-hang': ['don_hang_xem', 'don_hang_sua', 'hoa_don_xem'],

    '/admin/quan-ly-khuyen-mai': 'marketing_quan_ly',
    '/admin/gui-ma-sinh-nhat': 'marketing_quan_ly',
    '/admin/quan-ly-combo': 'marketing_quan_ly',
    '/admin/flash-sales': 'marketing_quan_ly',
    '/admin/flash-sale': 'marketing_quan_ly',
    '/admin/vong-quay': 'vong_quay_quan_ly',
    '/admin/diem-danh': 'diem_danh_quan_ly',

    '/admin/quan-ly-tiep-thi': 'affiliate_quan_ly',

    '/admin/quan-ly-tin-tuc': 'tin_tuc_quan_ly',
    '/admin/quan-ly-binh-luan': 'binh_luan_quan_ly',
    '/admin/quan-ly-banner': 'banner_quan_ly',

    '/admin/quan-ly-lien-he': 'lien_he_quan_ly',
    '/admin/quan-ly-chat': 'chat_quan_ly',

    '/admin/quan-ly-nguoi-dung': 'tai_khoan_quan_ly',
    '/admin/quan-ly-vai-tro': 'vai_tro_quan_ly',

    '/admin/nhat-ky-hoat-dong': 'nhat_ky_quan_ly',
    '/admin/xu': 'xu_quan_ly',
    '/admin/quan-ly-cham-cong': 'quan_ly_cham_cong',
    '/admin/quan-ly-don-xin-nghi': 'quan_ly_cham_cong',
  }

  return menuConfig.map(item => {
    if (item.path === '/admin' && !isAdmin) return null
    if (!item.isDropdown) {
      const required = pathPermissionMap[item.path]
      if (required && !hasPerm(required)) return null
      return item
    }

    const filteredChildren = item.children.filter(child => {
      if (child.superAdminOnly && !isSuperAdmin) return false
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
  'Thủ kho': false,
  'Đơn hàng': false,
  'Marketing': false,
  'Xu & Minigame': false,
  'Tiếp thị': false,
  'Biên tập viên': false,
  'Tư vấn viên': false,
  'Tài khoản': false,
})

function toggleDropdown(label) {
  if (sidebarCollapsed.value) {
    sidebarCollapsed.value = false
    localStorage.setItem('admin-sidebar-collapsed', 'false')
  }
  dropdownStates.value[label] = !dropdownStates.value[label]
}

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value
  localStorage.setItem('admin-sidebar-collapsed', String(sidebarCollapsed.value))
}

function isMenuPathActive(path) {
  if (path === '/admin') return route.name === 'admin-dashboard'
  const target = router.resolve(path)
  if (target.name && route.name) return target.name === route.name
  return route.path.replace(/\/$/, '') === target.path.replace(/\/$/, '')
}

function isParentActive(item) {
  if (!item.children) return false
  return item.children.some(child => isMenuPathActive(child.path))
}

function autoOpenDropdowns() {
  menuConfig.forEach(item => {
    if (item.isDropdown && item.children) {
      const hasActiveChild = item.children.some(child => isMenuPathActive(child.path))
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


const notifications = ref([])
const unreadCount = computed(() => notifications.value.filter((n) => !n.read).length)
const notificationStorageKey = computed(() => `admin-topbar-notifications-${user.value?.id || 'guest'}`)

async function unlockNotificationAudio() {
  if (notificationAudioUnlocked) return false
  try {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext
    if (!AudioContextClass) return
    notificationAudioContext ||= new AudioContextClass()
    if (notificationAudioContext.state === 'suspended') await notificationAudioContext.resume()
    notificationAudioUnlocked = notificationAudioContext.state === 'running'
    if (notificationAudioUnlocked && pendingNotificationSound) {
      pendingNotificationSound = false
      playNotificationChime()
      return true
    }
  } catch (_) {
    // Thiết bị/trình duyệt không hỗ trợ âm thanh thì chuông hình ảnh vẫn hoạt động.
  }
  return false
}

function playNotificationChime() {
  if (!notificationAudioUnlocked || !notificationAudioContext) return
  try {
    const context = notificationAudioContext
    const startAt = context.currentTime
    const gain = context.createGain()
    gain.gain.setValueAtTime(0.0001, startAt)
    gain.gain.exponentialRampToValueAtTime(0.12, startAt + 0.015)
    gain.gain.exponentialRampToValueAtTime(0.0001, startAt + 0.72)
    gain.connect(context.destination)

      ;[0, 0.18].forEach((delay, index) => {
        const oscillator = context.createOscillator()
        oscillator.type = 'triangle'
        oscillator.frequency.setValueAtTime(index === 0 ? 783.99 : 1046.5, startAt + delay)
        oscillator.connect(gain)
        oscillator.start(startAt + delay)
        oscillator.stop(startAt + delay + 0.42)
      })
  } catch (_) { }
}

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

const userName = computed(() => user.value?.ten || user.value?.name || 'Predator Staff')
const userRoleName = computed(() => {
  const role = user.value?.vaitro || 'admin'
  const mapping = {
    admin: 'Admin',
    user: 'Khách hàng',
    inventory: 'Thủ kho',
    order_manager: 'Xử lý đơn hàng',
    marketing: 'Marketing',
    affiliate_manager: 'Quản lý Affiliate',
    editor: 'Biên tập viên',
    support: 'Tư vấn viên',
    accountant: 'Kế toán'
  }
  return mapping[role.toLowerCase()] || 'Nhân viên'
})
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
  notifyMenuOpen.value = false
}

function toggleUserMenu() {
  userMenuOpen.value = !userMenuOpen.value
}

async function toggleNotifyMenu() {
  const playedPendingSound = await unlockNotificationAudio()
  if (unreadCount.value > 0 && !playedPendingSound) playNotificationChime()
  const next = !notifyMenuOpen.value
  closeTopMenus()
  notifyMenuOpen.value = next
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
  const saved = localStorage.getItem(notificationStorageKey.value)
  let savedList = []
  if (saved) {
    try {
      const parsed = JSON.parse(saved)
      if (Array.isArray(parsed)) savedList = parsed
    } catch (_) { }
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

  localStorage.setItem(notificationStorageKey.value, JSON.stringify(savedList))
}

function hydrateNotifications() {
  const saved = localStorage.getItem(notificationStorageKey.value)
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
    if (!rows.length) {
      notifications.value = []
      return
    }

    // Get existing notifications from localStorage to preserve read status
    const saved = localStorage.getItem(notificationStorageKey.value)
    let savedList = []
    if (saved) {
      try {
        const parsed = JSON.parse(saved)
        if (Array.isArray(parsed)) savedList = parsed
      } catch (_) { }
    }

    const mapped = rows.map((row, idx) => {
      // Generate unique, stable ID based on title and timestamp so it doesn't shift and reset
      const cleanTitle = (row.title || '').replace(/\s+/g, '').replace(/#/g, '')
      const cleanAt = (row.at || '').replace(/[^a-zA-Z0-9]/g, '')
      const id = row.id ? `log-${row.id}` : `log-${cleanTitle}-${cleanAt || idx}`
      const existing = savedList.find(n => n.id === id)
      return {
        id: id,
        title: row.title || row.description || 'Hoạt động mới',
        time: row.at ? new Date(row.at).toLocaleString('vi-VN') : 'Vừa xong',
        read: existing ? existing.read : false,
        path: row.path || (row.type === 'leave_request' ? '/admin/quan-ly-don-xin-nghi' : row.type === 'leave_result' ? '/admin/xin-nghi-phep' : '/admin/nhat-ky-hoat-dong'),
      }
    })
    const hasNewNotification = mapped.some((item) => !savedList.some((savedItem) => savedItem.id === item.id))
    notifications.value = mapped
    persistNotifications()
    if (hasNewNotification) {
      if (notificationAudioUnlocked) playNotificationChime()
      else pendingNotificationSound = true
    }
  } catch (e) {
    if (!notifications.value.length) {
      const saved = localStorage.getItem(notificationStorageKey.value)
      let savedList = []
      if (saved) {
        try {
          const parsed = JSON.parse(saved)
          if (Array.isArray(parsed)) savedList = parsed
        } catch (_) { }
      }
      notifications.value = String(user.value?.vaitro || '').toLowerCase() === 'admin'
        ? [
          { id: 'seed-1', title: 'Có đơn hàng mới cần xử lý', time: 'Vừa xong', read: savedList.find(n => n.id === 'seed-1')?.read || false, path: '/admin/quan-ly-don-hang' },
          { id: 'seed-2', title: 'Có liên hệ mới từ khách hàng', time: '5 phút trước', read: savedList.find(n => n.id === 'seed-2')?.read || false, path: '/admin/quan-ly-lien-he' },
        ]
        : []
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
  if (notifyMenuOpen.value && !isInside(notifyMenuRef, target)) notifyMenuOpen.value = false
}

async function handleLogout() {
  const isConfirmed = await swal.confirm(
    'Xác nhận đăng xuất',
    'Bạn có chắc chắn muốn thoát khỏi hệ thống quản trị?',
    'Đồng ý',
    'Hủy',
    { customClass: { popup: 'swal2-custom-popup logout-confirm-popup' } },
  )
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
      const latestUser = normalizeAuthUser(res.data.data)
      updateUser(latestUser)
      user.value = latestUser
    }
  } catch (err) {
    console.error('Failed to sync admin profile:', err)
  }
}

const handleSyncSuccess = () => {
  refreshCounter.value++
}

function handleQuickAttendanceSuccess() {
  loadNotifications()
}

onMounted(async () => {
  applyAdminTheme(adminTheme.value)
  adminClockTimer = window.setInterval(() => {
    adminClock.value = new Date()
  }, 1000)
  if (sessionStorage.getItem('admin_intro_animation') === '1') {
    sessionStorage.removeItem('admin_intro_animation')
    adminIntroActive.value = true
    adminIntroTimer = window.setTimeout(() => {
      adminIntroActive.value = false
      adminIntroTimer = null
    }, 520)
  }

  document.addEventListener('mousedown', handleClickOutside)
  document.addEventListener('pointerdown', unlockNotificationAudio, { once: true })
  document.addEventListener('keydown', unlockNotificationAudio, { once: true })
  window.addEventListener('user-updated', refreshUser)
  window.addEventListener('admin-settings-updated', handleSettingsUpdated)
  window.addEventListener('offline-sync-success', handleSyncSuccess)
  document.documentElement.lang = 'vi'
  hydrateNotifications()
  await Promise.allSettled([
    loadAppearanceSettings(),
    loadNotifications(),
    fetchLatestUserProfile(),
  ])
  notificationRefreshTimer = window.setInterval(loadNotifications, 30000)
})

onUnmounted(() => {
  delete document.documentElement.dataset.adminTheme
  document.documentElement.style.colorScheme = ''
  if (adminClockTimer) window.clearInterval(adminClockTimer)
  if (notificationRefreshTimer) window.clearInterval(notificationRefreshTimer)
  document.removeEventListener('pointerdown', unlockNotificationAudio)
  document.removeEventListener('keydown', unlockNotificationAudio)
  if (notificationAudioContext) {
    notificationAudioContext.close().catch(() => { })
    notificationAudioContext = null
  }
  if (adminIntroTimer) {
    clearTimeout(adminIntroTimer)
    adminIntroTimer = null
  }

  document.removeEventListener('mousedown', handleClickOutside)
  window.removeEventListener('user-updated', refreshUser)
  window.removeEventListener('admin-settings-updated', handleSettingsUpdated)
  window.removeEventListener('offline-sync-success', handleSyncSuccess)
})
</script>

<style scoped>
:global(html),
:global(body),
:global(#app) {
  height: 100% !important;
  max-height: 100vh !important;
  overflow: hidden !important;
}

* {
  box-sizing: border-box;
}

.admin-layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: #eef2f7;
  font-family: Inter, sans-serif;
}

.admin-layout.dark {
  background: #0b0f19;
}

.sidebar {
  width: 260px;
  min-width: 260px;
  background: #0b0d12;
  padding: 24px 16px;
  height: 100vh;
  overflow: hidden;
  position: relative;
  display: flex;
  flex-direction: column;
  border-right: 1px solid rgba(125, 211, 252, 0.14);
  transition: width 0.25s ease, min-width 0.25s ease, padding 0.25s ease;
}

.sidebar-collapse-btn {
  position: absolute;
  z-index: 3;
  top: 22px;
  right: 10px;
  width: 34px;
  height: 34px;
  padding: 0;
  border: 1px solid rgba(96, 165, 250, 0.34);
  border-radius: 50%;
  background: linear-gradient(145deg, rgba(59, 130, 246, 0.2), rgba(15, 23, 42, 0.92));
  color: #93c5fd;
  display: grid;
  place-items: center;
  cursor: pointer;
  box-shadow: 0 5px 16px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.08);
  transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.sidebar-collapse-btn:hover {
  transform: translateY(-1px) scale(1.06);
  border-color: rgba(96, 165, 250, 0.75);
  background: linear-gradient(145deg, #2563eb, #1d4ed8);
  color: #fff;
  box-shadow: 0 7px 20px rgba(37, 99, 235, 0.38), inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.sidebar-collapse-btn:active {
  transform: scale(0.94);
}

.sidebar-collapse-btn:focus-visible {
  outline: 2px solid #60a5fa;
  outline-offset: 3px;
}

.sidebar-collapse-btn svg {
  width: 17px;
  height: 17px;
  stroke-width: 2.3;
}

.sidebar-collapsed .sidebar {
  width: 76px;
  min-width: 76px;
  padding-left: 10px;
  padding-right: 10px;
}

.sidebar-collapsed .sidebar-collapse-btn {
  top: 19px;
  right: 21px;
}

.sidebar-collapsed .sidebar-logo {
  height: 52px;
  padding: 0 0 20px;
}

.sidebar-collapsed .admin-logo-img,
.sidebar-collapsed .menu-label,
.sidebar-collapsed .menu-text,
.sidebar-collapsed .chevron-icon,
.sidebar-collapsed .submenu,
.sidebar-collapsed .user-info,
.sidebar-collapsed .sidebar-logout-btn {
  display: none;
}

.sidebar-collapsed .menu-section {
  padding-top: 12px;
}

.sidebar-collapsed .item {
  justify-content: center;
  padding: 11px;
  gap: 0;
}

.sidebar-collapsed .item-icon {
  width: 20px;
  height: 20px;
}

.sidebar-collapsed .sidebar-user {
  justify-content: center;
  padding: 10px;
}

.sidebar-collapsed .sidebar-user .user-avatar {
  width: 34px;
  min-width: 34px;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px 20px;
  border-bottom: 1px solid rgba(125, 211, 252, 0.12);
  margin-bottom: 12px;
}

.admin-logo-img {
  width: 220px;
  max-width: 100%;
  height: 74px;
  object-fit: contain;
  display: block;
  filter: drop-shadow(0 8px 18px rgba(0, 0, 0, .24));
}

.menu-label {
  font-size: 10px;
  font-weight: 700;
  color: rgba(248, 250, 252, 0.35);
  letter-spacing: 1.5px;
  padding: 16px 12px 8px;
  margin: 0;
  text-transform: capitalize;
}

.admin-layout :deep(.item),
.admin-layout :deep(.submenu-item),
.admin-layout :deep(.submenu-badge),
.admin-layout :deep(.admin-topbar-title h2),
.admin-layout :deep(.card-title),
.admin-layout :deep(.section-title),
.admin-layout :deep(.chart-title),
.admin-layout :deep(.stat-label),
.admin-layout :deep(.stat-card p),
.admin-layout :deep(.stat-card span),
.admin-layout :deep(.stat-info p),
.admin-layout :deep(.stat-info span),
.admin-layout :deep(.period-tab),
.admin-layout :deep(.chart-nav-btn),
.admin-layout :deep(.status-badge),
.admin-layout :deep(.badge),
.admin-layout :deep(th),
.admin-layout :deep(td),
.admin-layout :deep(button) {
  text-transform: none !important;
}

.menu-label,
.menu-text,
.submenu-text,
.submenu-badge {
  text-transform: none !important;
}

.menu-section {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding-bottom: 16px;
}

.menu-section {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.menu-section::-webkit-scrollbar {
  width: 0;
  height: 0;
  display: none;
}

a {
  text-decoration: none;
}

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

.item-icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  stroke-width: 2;
  opacity: 0.85;
}

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
  padding-left: 12px;
}

.dropdown-group.is-open .submenu {
  max-height: 300px;
  transition: max-height 0.25s ease-in-out;
  margin-top: 4px;
  margin-bottom: 4px;
}

.submenu-item {
  padding: 8px 10px;
  border-radius: 8px;
  color: rgba(248, 250, 252, 0.6);
  font-size: 13px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 36px;
  transition: all 0.2s ease;
  white-space: nowrap;
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
  text-transform: capitalize;
  flex-shrink: 0;
}

.badge-thủ-kho {
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
}

.badge-đơn-hàng {
  background: rgba(34, 211, 238, 0.15);
  color: #3b82f6;
}

.badge-marketing {
  background: rgba(37, 99, 235, 0.15);
  color: #4ade80;
}

.badge-tiếp-thị {
  background: rgba(236, 72, 153, 0.15);
  color: #f472b6;
}

.badge-biên-tập {
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
}

.badge-tư-vấn {
  background: rgba(148, 163, 184, 0.15);
  color: #94a3b8;
}

.badge-admin {
  background: rgba(234, 179, 8, 0.15);
  color: #facc15;
}

.badge-minigame {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
}

.sidebar-user {
  margin-top: auto;
  padding: 12px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.055);
  border: 1px solid rgba(125, 211, 252, 0.13);
  display: flex;
  gap: 10px;
  align-items: center;
  flex-shrink: 0;
}

.user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #38bdf8, var(--admin-primary));
  color: #fff;
  display: grid;
  place-items: center;
  overflow: hidden;
  font-weight: 700;
  font-size: 13px;
}

.user-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-info {
  min-width: 0;
}

.sidebar-user .user-name {
  margin: 0;
  color: #fff;
  font-size: 13.5px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-user .user-role {
  margin: 2px 0 0;
  color: rgba(255, 255, 255, 0.5);
  font-size: 11px;
}

.sidebar-logout-btn {
  margin-left: auto;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid rgba(239, 68, 68, .25);
  background: rgba(239, 68, 68, .08);
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

.sidebar-logout-btn svg {
  width: 14px;
  height: 14px;
}

.main {
  --admin-page-bg: #f4f7fb;
  --admin-page-space: 24px;
  --admin-section-gap: 24px;
  --admin-panel-bg: #ffffff;
  --admin-panel-border: #e2e8f0;
  flex: 1;
  padding: 0 24px;
  height: 100vh;
  overflow-y: auto;
  overflow-x: hidden;
  background: var(--admin-page-bg);
  min-width: 0;
}

.admin-page-shell {
  width: 100%;
  min-height: calc(100vh - 77px);
  padding: var(--admin-page-space) 0 40px;
  background: transparent;
}

/* Every routed admin screen receives spacing and body color from the shell. */
.admin-page-shell> :deep(.admin),
.admin-page-shell> :deep(.page),
.admin-page-shell> :deep(.admin-page),
.admin-page-shell> :deep(.profile-page),
.admin-page-shell> :deep(.audit-log-container),
.admin-page-shell> :deep(.xu-config-page),
.admin-page-shell> :deep(.settings-v2),
.admin-page-shell> :deep(.attendance-page),
.admin-page-shell> :deep(.attendance-admin-page),
.admin-page-shell> :deep(.combo-management),
.admin-page-shell> :deep(.roles-management),
.admin-page-shell> :deep(.admin-vongquay-page),
.admin-page-shell> :deep(.affiliate-admin) {
  width: 100% !important;
  max-width: none !important;
  min-height: 0 !important;
  margin-left: 0 !important;
  margin-right: 0 !important;
  padding: 0 !important;
  gap: var(--admin-section-gap) !important;
  background: transparent !important;
}

/* Keep the primary content rails aligned across legacy admin screens. */
.admin-page-shell :deep(.page > .topbar),
.admin-page-shell :deep(.page > .breadcrumb),
.admin-page-shell :deep(.page > .page-header),
.admin-page-shell :deep(.page > .stats) {
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.admin-page-shell :deep(.page > .table-wrap),
.admin-page-shell :deep(.page > .filter-row),
.admin-page-shell :deep(.page > .users-bulk-toolbar) {
  margin-left: 0 !important;
  margin-right: 0 !important;
}

/* Variant management used an additional 32px inner rail on every section. */
.admin-page-shell :deep(.page > .top-tables),
.admin-page-shell :deep(.page > .tabs),
.admin-page-shell :deep(.page > .main-layout),
.admin-page-shell :deep(.page > .bottom-grid) {
  padding-left: 0 !important;
  padding-right: 0 !important;
}

/* Trang xác thực dùng hết vùng làm việc khi menu quản trị được thu gọn. */
.admin-layout.sidebar-collapsed .admin-page-shell :deep(.attendance-page),
.admin-layout.sidebar-collapsed .admin-page-shell :deep(.attendance-page > .attendance-today),
.admin-layout.sidebar-collapsed .admin-page-shell :deep(.attendance-page > .dashboard-grid),
.admin-layout.sidebar-collapsed .admin-page-shell :deep(.attendance-page > .verification-intro),
.admin-layout.sidebar-collapsed .admin-page-shell :deep(.attendance-page > .enrollment-banner),
.admin-layout.sidebar-collapsed .admin-page-shell :deep(.attendance-page > .employee-directory),
.admin-layout.sidebar-collapsed .admin-page-shell :deep(.attendance-page > .history-card) {
  width: 100% !important;
  max-width: none !important;
  margin-left: 0 !important;
  margin-right: 0 !important;
}

.admin-page-shell :deep(.dashboard-cluster),
.admin-page-shell :deep(.workbench-card),
.admin-page-shell :deep(.table-wrap),
.admin-page-shell :deep(.filter-row) {
  border-color: var(--admin-panel-border);
}
.admin-topbar { 
    position: sticky; 
    top: 0; 
    z-index: 8; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    gap: 14px; 
    margin: 0 -24px;
    padding: 10px 24px;
    width: calc(100% + 48px);
    background: #0b0d12; 
    border-bottom: 1px solid rgba(125, 211, 252, 0.14); 
    border-radius: 0;
    box-shadow: 0 12px 30px rgba(8, 43, 80, 0.18);
    transform: translateY(0);
    transition: transform 0.28s cubic-bezier(.4, 0, .2, 1), opacity 0.2s ease, box-shadow 0.28s ease, visibility 0s;
    will-change: transform;
}
.admin-topbar.header-hidden {
    transform: translateY(calc(-100% - 12px));
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    border-color: transparent;
    box-shadow: none;
    transition: transform 0.28s cubic-bezier(.4, 0, .2, 1), opacity 0.18s ease, box-shadow 0.28s ease, visibility 0s linear 0.28s;
}
@media (prefers-reduced-motion: reduce) {
    .admin-topbar { transition: none; }
}

.attendance-topbar-center {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  gap: 12px;
}

.attendance-topbar-clock {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 150px;
  pointer-events: none;
}

.attendance-topbar-clock span {
  color: #cbd5e1 !important;
  font-size: 10px;
  font-weight: 600;
  text-transform: capitalize;
}

.attendance-topbar-clock strong {
  margin-top: 1px;
  color: #ffffff !important;
  font-size: 20px;
  font-weight: 800;
  line-height: 1;
  letter-spacing: .04em;
  font-variant-numeric: tabular-nums;
  text-shadow: 0 0 14px rgba(96, 165, 250, .38);
  -webkit-text-fill-color: #ffffff;
}

.topbar-attendance-link {
  height: 36px;
  padding: 0 13px;
  border: 1px solid rgba(96, 165, 250, .48);
  border-radius: 10px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  text-decoration: none;
  font-size: 11.5px;
  font-weight: 750;
  white-space: nowrap;
  cursor: pointer;
  box-shadow: 0 5px 14px rgba(37, 99, 235, .25);
  transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

.topbar-attendance-link svg {
  width: 15px;
  height: 15px;
}

.topbar-attendance-link:hover {
  color: #fff;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  transform: translateY(-1px);
  box-shadow: 0 7px 18px rgba(37, 99, 235, .4);
}

@media (max-width: 1050px) {
  .attendance-topbar-clock span {
    display: none;
  }

  .attendance-topbar-clock strong {
    font-size: 16px;
  }

  .attendance-topbar-clock {
    min-width: 88px;
  }

  .topbar-attendance-link span {
    display: none;
  }

  .topbar-attendance-link {
    width: 36px;
    padding: 0;
  }
}

@media (max-width: 820px) {
  .attendance-topbar-center {
    display: none;
  }
}

.admin-topbar-title h2 {
  margin: 0;
  font-size: 19px;
  font-weight: 700;
  color: #ffffff;
}

.admin-topbar-title p {
  margin: 3px 0 0;
  color: rgba(219, 234, 254, 0.76);
  font-size: 11.5px;
}

.admin-topbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.topbar-home-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  height: 38px;
  padding: 0 14px;
  border-radius: 999px;
  border: 1px solid rgba(191, 219, 254, 0.24);
  background: rgba(255, 255, 255, 0.08);
  color: #e0f2fe;
  text-decoration: none;
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
  white-space: nowrap;
  transform: translateY(0);
  transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}

.topbar-home-link svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

.topbar-home-link:hover {
  background: rgba(37, 99, 235, 0.42);
  color: #ffffff;
  border-color: rgba(125, 211, 252, 0.46);
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.22);
  transform: translateY(-2px);
}

.topbar-home-link:active {
  transform: translateY(0);
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.16);
}

.topbar-icon-group {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 38px;
}

.topbar-popover {
  position: relative;
  display: flex;
  align-items: center;
}

.topbar-icon-button {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid rgba(191, 219, 254, 0.24);
  background: rgba(255, 255, 255, 0.08);
  color: #e0f2fe;
  display: grid;
  place-items: center;
  cursor: pointer;
  position: relative;
  transform: translateY(0) scale(1);
  transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}

.topbar-icon-button:hover,
.topbar-icon-button.active {
  background: rgba(37, 99, 235, 0.42);
  color: #ffffff;
  border-color: rgba(125, 211, 252, 0.46);
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

.topbar-icon-button svg {
  width: 18px;
  height: 18px;
}

.topbar-icon-button svg {
  transition: transform 0.18s ease;
}

.icon-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  min-width: 16px;
  height: 16px;
  border-radius: 999px;
  background: #ef4444;
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  display: grid;
  place-items: center;
  padding: 0 4px;
  border: 2px solid #fff;
  line-height: 1;
  animation: topbarBadgePulse 1.45s ease-in-out infinite;
  transform-origin: center;
}

.topbar-icon-button:hover .icon-badge {
  animation: topbarBadgePop 0.42s ease both, topbarBadgePulse 1.45s ease-in-out 0.42s infinite;
}

.topbar-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 280px;
  border: 1px solid rgba(15, 23, 42, .08);
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
  padding: 8px;
  z-index: 20;
}

.apps-dropdown {
  width: 190px;
  display: grid;
  gap: 6px;
}

.notify-menu {
  width: 320px;
}

.notify-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
  padding: 4px 4px 8px;
  border-bottom: 1px solid #edf2f7;
}

.notify-scroll {
  max-height: min(360px, calc(100vh - 150px));
  overflow-y: auto;
  overscroll-behavior: contain;
  padding-right: 3px;
  scrollbar-width: thin;
  scrollbar-color: #94a3b8 transparent;
}

.notify-scroll::-webkit-scrollbar {
  width: 6px;
}

.notify-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.notify-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 999px;
}

.notify-scroll::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.notify-mark-read {
  border: 0;
  background: transparent;
  color: #2563eb;
  font-size: 12px;
  cursor: pointer;
}

.notify-empty {
  color: #64748b;
  font-size: 13px;
  padding: 14px 6px;
}

.notify-item {
  width: 100%;
  border: 1px solid #eef2f7;
  background: #fff;
  border-radius: 10px;
  padding: 9px 10px;
  margin-top: 6px;
  text-align: left;
  cursor: pointer;
  display: grid;
  gap: 2px;
}

.notify-item.unread {
  border-color: #c7d2fe;
  background: #eef2ff;
}

.notify-title {
  font-size: 13px;
  color: #0f172a;
  font-weight: 600;
}

.notify-time {
  font-size: 11px;
  color: #64748b;
}

.topbar-divider {
  width: 1px;
  height: 28px;
  background: rgba(191, 219, 254, 0.22);
}

.topbar-user {
  position: relative;
  display: flex;
  align-items: center;
}

.topbar-user-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 38px;
  padding: 3px 10px 3px 3px;
  border-radius: 999px;
  border: 1px solid rgba(191, 219, 254, 0.24);
  background: rgba(255, 255, 255, 0.08);
  cursor: pointer;
  line-height: 1;
  transform: translateY(0);
  transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
}

.topbar-user-btn:hover {
  background: rgba(37, 99, 235, 0.42);
  border-color: rgba(125, 211, 252, 0.46);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.10);
  transform: translateY(-2px);
}

.topbar-user-btn:active {
  transform: translateY(0);
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
}

.topbar-user-btn svg {
  width: 15px;
  height: 15px;
  color: rgba(219, 234, 254, 0.72);
}

.topbar-user-btn .user-avatar {
  width: 32px;
  height: 32px;
  font-size: 12px;
  flex-shrink: 0;
}

.user-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.user-name {
  font-size: 12.5px;
  font-weight: 600;
  color: #ffffff;
}

.user-role {
  font-size: 10px;
  color: rgba(219, 234, 254, 0.72);
}

.user-dropdown {
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  width: 240px;
  padding: 10px;
  border-radius: 12px;
  background: #fff;
  border: 1px solid rgba(15, 23, 42, .08);
  box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
  z-index: 20;
}

.user-dropdown-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(15, 23, 42, .08);
  margin-bottom: 10px;
}

.user-dropdown-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #38bdf8, #2563eb);
  color: #fff;
  display: grid;
  place-items: center;
  overflow: hidden;
  font-weight: 700;
}

.dropdown-name {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.dropdown-email {
  margin: 2px 0 0;
  font-size: 11px;
  color: #64748b;
}

.user-dropdown-list {
  display: grid;
  gap: 6px;
  margin-bottom: 8px;
}

.dropdown-item {
  width: 100%;
  text-align: center;
  padding: 10px 12px;
  border-radius: 10px;
  border: none;
  background: #f8fafc;
  color: #0f172a;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dropdown-item.compact {
  text-align: left;
}

.dropdown-item.active {
  background: #e0e7ff;
  color: #1d4ed8;
  font-weight: 700;
}

.dropdown-item:hover {
  background: #eef2ff;
}

.dropdown-item.sign-out {
  background: #fef2f2;
  color: #dc2626;
  transition: background-color 0.2s, color 0.2s;
}

.dropdown-item.sign-out:hover {
  background: #fee2e2;
  color: #b91c1c;
}

.logout-item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.logout-item svg {
  width: 15px;
  height: 15px;
}

@keyframes topbarBadgePulse {

  0%,
  100% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.30);
    transform: scale(1);
  }

  50% {
    box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
    transform: scale(1.08);
  }
}

@keyframes topbarBadgePop {
  0% {
    transform: scale(1);
  }

  45% {
    transform: scale(1.28);
  }

  100% {
    transform: scale(1.08);
  }
}

@keyframes adminBellRing {

  0%,
  56%,
  100% {
    transform: rotate(0deg);
  }

  62% {
    transform: rotate(13deg);
  }

  68% {
    transform: rotate(-11deg);
  }

  74% {
    transform: rotate(8deg);
  }

  80% {
    transform: rotate(-6deg);
  }

  86% {
    transform: rotate(3deg);
  }

  92% {
    transform: rotate(0deg);
  }
}

.topbar-icon-button.notification-alerting>svg {
  transform-origin: 50% 12%;
  animation: adminBellRing 1.8s ease-in-out infinite;
}

.topbar-icon-button.notification-alerting:hover>svg,
.topbar-icon-button.notification-alerting:focus-visible>svg {
  animation-duration: 1.15s;
}

@media (prefers-reduced-motion: reduce) {
  .topbar-icon-button.notification-alerting>svg {
    animation: none;
  }
}

.admin-layout.dark .main,
.admin-layout.dark .admin-topbar {
  background: #0f172a;
  border-color: rgba(255, 255, 255, .05);
}

.admin-layout.dark .admin-topbar-title h2 {
  color: #fff;
}

.admin-layout.dark .admin-topbar-title p,
.admin-layout.dark .user-role {
  color: #94a3b8;
}

.admin-layout.dark .topbar-icon-button,
.admin-layout.dark .topbar-user-btn,
.admin-layout.dark .topbar-dropdown,
.admin-layout.dark .user-dropdown {
  background: #0f172a;
  border-color: rgba(255, 255, 255, .08);
  color: #fff;
}

.admin-layout.dark .topbar-home-link {
  background: #0f172a;
  border-color: rgba(255, 255, 255, .08);
  color: #38bdf8;
}

.admin-layout.dark .dropdown-item,
.admin-layout.dark .notify-item {
  background: #1e293b;
  border-color: rgba(255, 255, 255, .08);
  color: #e2e8f0;
}

.admin-layout.dark .notify-item.unread,
.admin-layout.dark .dropdown-item.active,
.admin-layout.dark .dropdown-item:hover {
  background: rgba(37, 99, 235, 0.15);
}

.admin-layout.theme-dark .notify-menu {
  background: #171a1f !important;
  border-color: #454c56 !important;
}

.admin-layout.theme-dark .notify-head {
  border-bottom-color: #59616c !important;
}

.admin-layout.theme-dark .notify-head b {
  color: #f8fafc !important;
}

.admin-layout.theme-dark .notify-mark-read {
  color: #60a5fa !important;
}

.admin-layout.theme-dark .notify-empty {
  color: #cbd5e1 !important;
}

.admin-layout.theme-dark .notify-item {
  background: #111315 !important;
  border-color: #454c56 !important;
}

.admin-layout.theme-dark .notify-item:hover,
.admin-layout.theme-dark .notify-item.unread {
  background: #1b2b45 !important;
  border-color: #3b82f6 !important;
}

.admin-layout.theme-dark .notify-title {
  color: #f8fafc !important;
  font-weight: 750 !important;
}

.admin-layout.theme-dark .notify-time {
  color: #b8c2d1 !important;
}

.admin-layout.intro-active {
  animation: adminIntroBase 0.42s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.admin-layout.intro-active .sidebar {
  animation: adminIntroSidebar 0.36s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.admin-layout.intro-active .admin-topbar {
  animation: adminIntroTopbar 0.32s cubic-bezier(0.16, 1, 0.3, 1) 0.06s both;
}

.admin-layout.intro-active .main> :not(.admin-topbar) {
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
  text-transform: capitalize;
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
  .admin-layout.intro-active .main> :not(.admin-topbar) {
    animation-duration: 0.01ms !important;
  }
}

.main :deep(.search-box) {
  position: relative !important;
  display: flex !important;
  align-items: center !important;
  background: #ffffff !important;
  border: 1.5px solid #cbd5e1 !important;
  border-radius: 10px !important;
  padding: 0 12px !important;
  width: 280px !important;
  height: 38px !important;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
  transition: all 0.2s ease !important;
}

.main :deep(.search-box:focus-within) {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14) !important;
  background: #ffffff !important;
}

.main :deep(.search-box svg) {
  position: static !important;
  transform: none !important;
  width: 15px !important;
  height: 15px !important;
  stroke: #64748b !important;
  color: #64748b !important;
  stroke-width: 2 !important;
  fill: none !important;
  flex-shrink: 0 !important;
  margin-right: 8px !important;
  pointer-events: none !important;
}

.main :deep(.search-box input) {
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
  font-size: 13px !important;
  color: #0f172a !important;
  background: transparent !important;
  width: 100% !important;
  height: 100% !important;
  padding: 0 !important;
  margin: 0 !important;
  border-radius: 0 !important;
}

.main :deep(.search-box input::placeholder) {
  color: #94a3b8 !important;
}

/* ════════════════════════════════════════════════════════════
   GLOBAL ADMIN PAGINATION DESIGN SYSTEM
   ════════════════════════════════════════════════════════════ */
.main :deep(.table-footer),
.main :deep(.table-pagination),
.main :deep(.pagination-wrap),
.main :deep(.pagination-container),
.main :deep(.pagination-footer) {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  padding: 14px 32px !important;
  background: transparent !important;
  border: none !important;
}

.main :deep(.showing),
.main :deep(.pagination-info),
.main :deep(.page-info) {
  font-size: 13px !important;
  font-weight: 500 !important;
  color: #64748b !important;
}

.main :deep(.pagination),
.main :deep(.pagination-nav),
.main :deep(.pagination-controls) {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
}

.main :deep(.pagination button),
.main :deep(.pagination .page-btn),
.main :deep(.pagination-nav button),
.main :deep(.pagination a),
.main :deep(.page-link) {
  min-width: 34px !important;
  height: 34px !important;
  padding: 0 8px !important;
  border-radius: 8px !important;
  border: 1.5px solid #cbd5e1 !important;
  background: #ffffff !important;
  color: #334155 !important;
  font-size: 13px !important;
  font-weight: 600 !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  transition: all 0.15s ease !important;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
  cursor: pointer !important;
}

.main :deep(.pagination button:hover:not(:disabled)),
.main :deep(.pagination .page-btn:hover:not(:disabled)),
.main :deep(.pagination-nav button:hover:not(:disabled)),
.main :deep(.page-link:hover) {
  border-color: #2563eb !important;
  color: #2563eb !important;
  background: #eff6ff !important;
}

.main :deep(.pagination button.active),
.main :deep(.pagination .page-btn.active),
.main :deep(.pagination-nav button.active),
.main :deep(.page-item.active .page-link) {
  background: #2563eb !important;
  border-color: #2563eb !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25) !important;
}

.main :deep(.pagination button:disabled),
.main :deep(.pagination .page-btn:disabled),
.main :deep(.pagination-nav button:disabled) {
  opacity: 0.45 !important;
  cursor: not-allowed !important;
  border-color: #e2e8f0 !important;
  background: #f8fafc !important;
  color: #94a3b8 !important;
  box-shadow: none !important;
}

.main :deep(.pagination .dots),
.main :deep(.pagination span.dots),
.main :deep(.page-indicator) {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  color: #64748b !important;
  font-weight: 500 !important;
  padding: 0 4px !important;
}

/* Một kiểu nút xuất báo cáo dùng thống nhất cho toàn bộ trang admin. */
.main :deep(.admin-report-export) {
  text-transform: none !important;
  width: auto !important;
  min-width: 122px !important;
  height: 38px !important;
  padding: 0 13px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 7px !important;
  flex-shrink: 0 !important;
  border: 1px solid #d7deea !important;
  border-radius: 12px !important;
  background: #ffffff !important;
  color: #172033 !important;
  font-size: 13px !important;
  line-height: 1 !important;
  font-weight: 700 !important;
  white-space: nowrap !important;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
  cursor: pointer !important;
  transition: border-color 0.18s ease, color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease !important;
}

.main :deep(.admin-report-export svg) {
  width: 15px !important;
  height: 15px !important;
  flex: 0 0 15px !important;
  color: #334155 !important;
  stroke-width: 1.8 !important;
}

.main :deep(.admin-report-export:hover:not(:disabled)) {
  color: #2563eb !important;
  border-color: #93b4f5 !important;
  background: #f8fbff !important;
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.10) !important;
  transform: translateY(-1px) !important;
}

.main :deep(.admin-report-export:focus-visible) {
  outline: 3px solid rgba(37, 99, 235, 0.18) !important;
  outline-offset: 2px !important;
}

.main :deep(.admin-report-export:disabled) {
  opacity: 0.55 !important;
  cursor: not-allowed !important;
  transform: none !important;
  box-shadow: none !important;
}

/* Native dark theme overrides for controls standardized by this layout. */
.admin-layout.theme-dark .main :deep(.search-box) {
  border-color: #454b54 !important;
  background: #171a1f !important;
  box-shadow: none !important;
}

.admin-layout.theme-dark .main :deep(.search-box:focus-within) {
  border-color: #5b8def !important;
  background: #171a1f !important;
  box-shadow: 0 0 0 3px rgba(91, 141, 239, 0.14) !important;
}

.admin-layout.theme-dark .main :deep(.search-box svg) {
  color: #929eae !important;
  stroke: #929eae !important;
}

.admin-layout.theme-dark .main :deep(.search-box input) {
  color: #e8edf4 !important;
  background: transparent !important;
}

.admin-layout.theme-dark .main :deep(.search-box input::placeholder) {
  color: #7f8a99 !important;
}

.admin-layout.theme-dark .main :deep(.admin-report-export) {
  border-color: #454c56 !important;
  background: #20242a !important;
  color: #e5eaf1 !important;
  box-shadow: none !important;
}

.admin-layout.theme-dark .main :deep(.admin-report-export svg) {
  color: #b6c0cd !important;
}

.admin-layout.theme-dark .main :deep(.admin-report-export:hover:not(:disabled)) {
  border-color: #5b8def !important;
  background: #282e37 !important;
  color: #8fb6ff !important;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.22) !important;
}

/* Keep the dashboard toolbar transparent in dark mode. */
.admin-layout.theme-dark .main :deep(.dashboard-controls),
.admin-layout.theme-dark .main :deep(.dashboard-controls::before),
.admin-layout.theme-dark .main :deep(.dashboard-controls::after) {
  background: transparent !important;
  border-color: transparent !important;
  box-shadow: none !important;
}

.admin-layout.theme-dark .main :deep(.dashboard-controls::before),
.admin-layout.theme-dark .main :deep(.dashboard-controls::after) {
  display: none !important;
}

.admin-layout.theme-dark .main :deep(.period-bar-label) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.period-tabs) {
  background: #252a31 !important;
  border: 1px solid #3c434d !important;
}

.admin-layout.theme-dark .main :deep(.period-tab) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.period-tab:hover:not(.active)) {
  background: #30363f !important;
  color: #f1f5f9 !important;
}

.admin-layout.theme-dark .main :deep(.period-tab.active) {
  background: #2563eb !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3) !important;
}

.admin-layout.theme-dark .main :deep(.daily-revenue-trigger) {
  border-color: #3f69a8 !important;
  background: #1b2736 !important;
  color: #8fb6ff !important;
}

.admin-layout.theme-dark .main :deep(.daily-revenue-trigger:hover) {
  border-color: #60a5fa !important;
  background: #233754 !important;
  color: #bfdbfe !important;
}

/* Customer age analytics: remove light controls and legend tiles in dark mode. */
.admin-layout.theme-dark .main :deep(.insight-highlight) {
  border: 1px solid #355d96 !important;
  background: #1b2d49 !important;
  color: #93c5fd !important;
}

.admin-layout.theme-dark .main :deep(.metric-switch) {
  border: 1px solid #3c4654 !important;
  background: #20252c !important;
}

.admin-layout.theme-dark .main :deep(.metric-switch button) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.metric-switch button:hover) {
  background: #2b323c !important;
  color: #e5eaf1 !important;
}

.admin-layout.theme-dark .main :deep(.metric-switch button.active),
.admin-layout.theme-dark .main :deep(.metric-switch .chart-tab-btn.active) {
  background: #1f4f99 !important;
  color: #dbeafe !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.24) !important;
}

.admin-layout.theme-dark .main :deep(.age-inline-legend > div),
.admin-layout.theme-dark .main :deep(.age-legend-row) {
  border-color: #3b4552 !important;
  background: #181c22 !important;
}

.admin-layout.theme-dark .main :deep(.age-inline-legend span),
.admin-layout.theme-dark .main :deep(.age-legend-row b) {
  color: #d7dee8 !important;
}

.admin-layout.theme-dark .main :deep(.age-inline-legend b),
.admin-layout.theme-dark .main :deep(.age-legend-row > strong) {
  color: #f8fafc !important;
}

.admin-layout.theme-dark .main :deep(.age-legend-row span) {
  color: #9ca8b7 !important;
}

/* Dark surfaces that are authored as light-only inside individual admin pages. */
.admin-layout.theme-dark .main :deep(.workflow-hint) {
  border-color: #36547d !important;
  background: #172842 !important;
  color: #dbe7f7 !important;
}

.admin-layout.theme-dark .main :deep(.workflow-arrow) {
  color: #60a5fa !important;
}

.admin-layout.theme-dark .main :deep(.promotion-empty-warning) {
  border-color: #8b6216 !important;
  background: #2a210f !important;
  color: #fde68a !important;
}

.admin-layout.theme-dark .main :deep(.promotion-empty-warning .inline-link) {
  color: #93c5fd !important;
}

.admin-layout.theme-dark .main :deep(.setup-stepper) {
  background: #20252c !important;
  border: 1px solid #3c434d !important;
}

.admin-layout.theme-dark .main :deep(.setup-step) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.setup-step > span) {
  background: #343b45 !important;
  color: #dce3ec !important;
}

.admin-layout.theme-dark .main :deep(.setup-step.done) {
  background: #153527 !important;
  color: #86efac !important;
}

.admin-layout.theme-dark .main :deep(.existing-profile-notice) {
  border-color: #315f4b !important;
  background: #153527 !important;
}

.admin-layout.theme-dark .main :deep(.existing-profile-notice strong) {
  color: #bbf7d0 !important;
}

.admin-layout.theme-dark .main :deep(.existing-profile-notice small) {
  color: #b9c8c0 !important;
}

.admin-layout.theme-dark .main :deep(.existing-profile-notice button) {
  border-color: #3d8063 !important;
  background: #1c4534 !important;
  color: #bbf7d0 !important;
}

.admin-layout.theme-dark .main :deep(.profile-card),
.admin-layout.theme-dark .main :deep(.identity-card) {
  border-color: #3c4b60 !important;
  background: #181b20 !important;
}

.admin-layout.theme-dark .main :deep(.identity-card) {
  background: #17202c !important;
}

.admin-layout.theme-dark .main :deep(.identity-upload) {
  border-color: #4d6f9f !important;
  background: #20252c !important;
}

.admin-layout.theme-dark .main :deep(.identity-upload:hover) {
  border-color: #60a5fa !important;
  background: #242c36 !important;
}

.admin-layout.theme-dark .main :deep(.identity-upload-placeholder small) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.password-visibility-button) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.password-visibility-button:hover) {
  background: #303946 !important;
  color: #93c5fd !important;
}

.admin-layout.theme-dark .main :deep(.unified-submit-area) {
  border-top-color: #454c56 !important;
  background: rgba(24, 27, 32, 0.96) !important;
  box-shadow: 0 -10px 26px rgba(0, 0, 0, 0.28) !important;
}

.admin-layout.theme-dark .main :deep(.unified-submit-area strong) {
  color: #f1f5f9 !important;
}

.admin-layout.theme-dark .main :deep(.unified-submit-area span) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(td > span:not(.status-badge):not(.badge):not(.role-badge):not(.discount-tag):not([class*='badge']):not([class*='pill']):not([class*='tag']):not([class*='chip']):not([class*='status'])),
.admin-layout.theme-dark .main :deep(.price),
.admin-layout.theme-dark .main :deep(.combo-product-stack span),
.admin-layout.theme-dark .main :deep(.combo-product-stack small),
.admin-layout.theme-dark .main :deep(.day-label),
.admin-layout.theme-dark .main :deep(.mo-ta-text),
.admin-layout.theme-dark .main :deep(.chat-sidebar-pane .user-name),
.admin-layout.theme-dark .main :deep(.field > span),
.admin-layout.theme-dark .main :deep(.weekday-field > span),
.admin-layout.theme-dark .main :deep(.employee-setup-form label > span),
.admin-layout.theme-dark .main :deep(.payroll-filter label) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.stat-card-btn),
.admin-layout.theme-dark .main :deep(.stat-card-btn span),
.admin-layout.theme-dark .main :deep(.stat-card-btn strong),
.admin-layout.theme-dark .main :deep(.stat-card-btn small) {
  color: #edf2f8 !important;
}

.admin-layout.theme-dark .main :deep(.badge-up) {
  background: #123b2b !important;
  color: #6ee7a8 !important;
}

.admin-layout.theme-dark .main :deep(.badge-down) {
  background: #442126 !important;
  color: #ff9a9a !important;
}

.admin-layout.theme-dark .main :deep(.badge-neutral) {
  background: #2a3038 !important;
  color: #b7c1ce !important;
}

.admin-layout.theme-dark .main :deep(.status-active),
.admin-layout.theme-dark .main :deep(.badge-success),
.admin-layout.theme-dark .main :deep(.badge-success-outline) {
  border-color: #276046 !important;
  background: #153527 !important;
  color: #6ee7a8 !important;
}

.admin-layout.theme-dark .main :deep(.text-purple) {
  color: #b69cff !important;
}

.admin-layout.theme-dark .main :deep(.profile-stats > div),
.admin-layout.theme-dark .main :deep(.tool-summary) {
  border-color: #383e46 !important;
  background: #181b20 !important;
  color: #e8edf4 !important;
}

.admin-layout.theme-dark .main :deep(.profile-stats span),
.admin-layout.theme-dark .main :deep(.tool-summary span) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.profile-stats strong),
.admin-layout.theme-dark .main :deep(.tool-summary strong) {
  color: #edf2f8 !important;
}

.admin-layout.theme-dark .main :deep(.stock-cell span),
.admin-layout.theme-dark .main :deep(.payroll-filters label > span) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.tab-btn span),
.admin-layout.theme-dark .main :deep(.tab-btn strong),
.admin-layout.theme-dark .main :deep(.tab span),
.admin-layout.theme-dark .main :deep(.parent-tab-btn span) {
  color: inherit !important;
}

.admin-layout.theme-dark .main :deep(.tabs .tab-btn.active) {
  background: #2563eb !important;
  color: #ffffff !important;
}

.admin-layout.theme-dark .main :deep(.tabs .tab-btn.active span) {
  background: rgba(255, 255, 255, 0.16) !important;
  color: #ffffff !important;
}

.admin-layout.theme-dark .main :deep(.publisher-row .money),
.admin-layout.theme-dark .main :deep(.money),
.admin-layout.theme-dark .main :deep(.positive) {
  color: #8fb6ff !important;
}

/* Nút thao tác trong bảng: tránh ô trắng gắt trên nền tối và phân biệt rõ chức năng. */
.admin-layout.theme-dark .main :deep(.actions .action-btn),
.admin-layout.theme-dark .main :deep(.action-buttons .action-btn),
.admin-layout.theme-dark .main :deep(.action-btns .action-btn),
.admin-layout.theme-dark .main :deep(td .act-btn),
.admin-layout.theme-dark .main :deep(td .icon-btn),
.admin-layout.theme-dark .main :deep(td .edit-btn) {
  border: 1px solid #36557f !important;
  background: #1b2736 !important;
  color: #8fb6ff !important;
  box-shadow: none !important;
}

.admin-layout.theme-dark .main :deep(.actions .action-btn svg),
.admin-layout.theme-dark .main :deep(.action-buttons .action-btn svg),
.admin-layout.theme-dark .main :deep(.action-btns .action-btn svg),
.admin-layout.theme-dark .main :deep(td .act-btn svg),
.admin-layout.theme-dark .main :deep(td .icon-btn svg),
.admin-layout.theme-dark .main :deep(td .edit-btn svg) {
  color: inherit !important;
  stroke: currentColor !important;
}

.admin-layout.theme-dark .main :deep(.actions .edit-btn:hover),
.admin-layout.theme-dark .main :deep(.action-buttons .edit-btn:hover),
.admin-layout.theme-dark .main :deep(td .act-btn:hover),
.admin-layout.theme-dark .main :deep(td .icon-btn:hover),
.admin-layout.theme-dark .main :deep(td .edit-btn:hover) {
  border-color: #6ea0ff !important;
  background: #2563eb !important;
  color: #ffffff !important;
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.28) !important;
  transform: translateY(-1px);
}

.admin-layout.theme-dark .main :deep(.action-delete),
.admin-layout.theme-dark .main :deep(.delete-btn),
.admin-layout.theme-dark .main :deep(.btn-delete),
.admin-layout.theme-dark .main :deep(.act-btn.danger),
.admin-layout.theme-dark .main :deep(.action-btn.danger),
.admin-layout.theme-dark .main :deep(.action-btn.delete),
.admin-layout.theme-dark .main :deep(.color-del-btn),
.admin-layout.theme-dark .main :deep(.btn-row-del),
.admin-layout.theme-dark .main :deep(.multi-preview-remove),
.admin-layout.theme-dark .main :deep(button[title*='Xóa']),
.admin-layout.theme-dark .main :deep(button[title*='Xoá']),
.admin-layout.theme-dark .main :deep(button[aria-label*='Xóa']),
.admin-layout.theme-dark .main :deep(button[aria-label*='Xoá']) {
  border-color: #70343b !important;
  background: #321d22 !important;
  color: #ff8e98 !important;
}

.admin-layout.theme-dark .main :deep(.action-delete:hover),
.admin-layout.theme-dark .main :deep(.delete-btn:hover),
.admin-layout.theme-dark .main :deep(.btn-delete:hover),
.admin-layout.theme-dark .main :deep(.act-btn.danger:hover),
.admin-layout.theme-dark .main :deep(.action-btn.danger:hover),
.admin-layout.theme-dark .main :deep(.action-btn.delete:hover),
.admin-layout.theme-dark .main :deep(.color-del-btn:hover),
.admin-layout.theme-dark .main :deep(.btn-row-del:hover),
.admin-layout.theme-dark .main :deep(.multi-preview-remove:hover),
.admin-layout.theme-dark .main :deep(button[title*='Xóa']:hover),
.admin-layout.theme-dark .main :deep(button[title*='Xoá']:hover),
.admin-layout.theme-dark .main :deep(button[aria-label*='Xóa']:hover),
.admin-layout.theme-dark .main :deep(button[aria-label*='Xoá']:hover) {
  border-color: #ef5b68 !important;
  background: #c93645 !important;
  color: #ffffff !important;
}

.admin-layout.theme-dark .main :deep(.action-email) {
  border-color: #276046 !important;
  background: #173326 !important;
  color: #6ee7a8 !important;
}

.admin-layout.theme-dark .main :deep(.action-email:hover) {
  border-color: #35b777 !important;
  background: #168653 !important;
  color: #ffffff !important;
}

.admin-layout.theme-dark .main :deep(button:disabled) {
  box-shadow: none !important;
  transform: none !important;
}

/* Quy ước toàn admin: Xem/Sửa = xanh, Xóa = đỏ (kể cả nút chỉ có icon). */
.admin-layout.theme-light .main :deep(button[title^='Xem']),
.admin-layout.theme-light .main :deep(a[title^='Xem']),
.admin-layout.theme-light .main :deep(button[title^='Sửa']),
.admin-layout.theme-light .main :deep(a[title^='Sửa']),
.admin-layout.theme-light .main :deep(button[title^='Chỉnh']),
.admin-layout.theme-light .main :deep(a[title^='Chỉnh']),
.admin-layout.theme-light .main :deep(.actions .edit-btn),
.admin-layout.theme-light .main :deep(.action-buttons .edit-btn),
.admin-layout.theme-light .main :deep(.btn-edit) {
  border-color: #bfdbfe !important;
  background: #eff6ff !important;
  color: #2563eb !important;
}

.admin-layout.theme-light .main :deep(button[title^='Xem']:hover),
.admin-layout.theme-light .main :deep(a[title^='Xem']:hover),
.admin-layout.theme-light .main :deep(button[title^='Sửa']:hover),
.admin-layout.theme-light .main :deep(a[title^='Sửa']:hover),
.admin-layout.theme-light .main :deep(button[title^='Chỉnh']:hover),
.admin-layout.theme-light .main :deep(a[title^='Chỉnh']:hover),
.admin-layout.theme-light .main :deep(.actions .edit-btn:hover),
.admin-layout.theme-light .main :deep(.action-buttons .edit-btn:hover),
.admin-layout.theme-light .main :deep(.btn-edit:hover) {
  border-color: #2563eb !important;
  background: #2563eb !important;
  color: #ffffff !important;
}

.admin-layout.theme-dark .main :deep(button[title^='Xem']),
.admin-layout.theme-dark .main :deep(a[title^='Xem']),
.admin-layout.theme-dark .main :deep(button[title^='Sửa']),
.admin-layout.theme-dark .main :deep(a[title^='Sửa']),
.admin-layout.theme-dark .main :deep(button[title^='Chỉnh']),
.admin-layout.theme-dark .main :deep(a[title^='Chỉnh']),
.admin-layout.theme-dark .main :deep(.btn-edit) {
  border-color: #36557f !important;
  background: #1b2736 !important;
  color: #8fb6ff !important;
}

.admin-layout.theme-dark .main :deep(button[title^='Xem']:hover),
.admin-layout.theme-dark .main :deep(a[title^='Xem']:hover),
.admin-layout.theme-dark .main :deep(button[title^='Sửa']:hover),
.admin-layout.theme-dark .main :deep(a[title^='Sửa']:hover),
.admin-layout.theme-dark .main :deep(button[title^='Chỉnh']:hover),
.admin-layout.theme-dark .main :deep(a[title^='Chỉnh']:hover),
.admin-layout.theme-dark .main :deep(.btn-edit:hover) {
  border-color: #6ea0ff !important;
  background: #2563eb !important;
  color: #ffffff !important;
}

.admin-layout.theme-light .main :deep(.action-delete),
.admin-layout.theme-light .main :deep(.delete-btn),
.admin-layout.theme-light .main :deep(.btn-delete),
.admin-layout.theme-light .main :deep(.act-btn.danger),
.admin-layout.theme-light .main :deep(.action-btn.danger),
.admin-layout.theme-light .main :deep(.action-btn.delete),
.admin-layout.theme-light .main :deep(.color-del-btn),
.admin-layout.theme-light .main :deep(.btn-row-del),
.admin-layout.theme-light .main :deep(.multi-preview-remove),
.admin-layout.theme-light .main :deep(button[title*='Xóa']),
.admin-layout.theme-light .main :deep(button[title*='Xoá']) {
  border-color: #fecaca !important;
  background: #fff1f2 !important;
  color: #dc2626 !important;
}

.admin-layout.theme-light .main :deep(.action-delete:hover),
.admin-layout.theme-light .main :deep(.delete-btn:hover),
.admin-layout.theme-light .main :deep(.btn-delete:hover),
.admin-layout.theme-light .main :deep(.act-btn.danger:hover),
.admin-layout.theme-light .main :deep(.action-btn.danger:hover),
.admin-layout.theme-light .main :deep(.action-btn.delete:hover),
.admin-layout.theme-light .main :deep(.color-del-btn:hover),
.admin-layout.theme-light .main :deep(.btn-row-del:hover),
.admin-layout.theme-light .main :deep(.multi-preview-remove:hover),
.admin-layout.theme-light .main :deep(button[title*='Xóa']:hover),
.admin-layout.theme-light .main :deep(button[title*='Xoá']:hover) {
  border-color: #dc2626 !important;
  background: #dc2626 !important;
  color: #ffffff !important;
}

.admin-layout .main :deep(button[title^='Xem'] svg),
.admin-layout .main :deep(a[title^='Xem'] svg),
.admin-layout .main :deep(button[title^='Sửa'] svg),
.admin-layout .main :deep(a[title^='Sửa'] svg),
.admin-layout .main :deep(button[title^='Chỉnh'] svg),
.admin-layout .main :deep(a[title^='Chỉnh'] svg),
.admin-layout .main :deep(button[title*='Xóa'] svg),
.admin-layout .main :deep(button[title*='Xoá'] svg),
.admin-layout .main :deep(.btn-edit svg),
.admin-layout .main :deep(.btn-delete svg),
.admin-layout .main :deep(.danger svg) {
  color: inherit !important;
  stroke: currentColor !important;
}

/* Đặt cuối cùng để thắng CSS cục bộ của cột Thao tác trên từng trang. */
.admin-layout.theme-dark .main :deep(.actions .action-delete),
.admin-layout.theme-dark .main :deep(.actions .delete-btn),
.admin-layout.theme-dark .main :deep(.actions .btn-delete),
.admin-layout.theme-dark .main :deep(.actions .danger),
.admin-layout.theme-dark .main :deep(.action-buttons .action-delete),
.admin-layout.theme-dark .main :deep(.action-buttons .delete-btn),
.admin-layout.theme-dark .main :deep(.action-buttons .btn-delete),
.admin-layout.theme-dark .main :deep(.action-buttons .danger),
.admin-layout.theme-dark .main :deep(.action-btns .action-delete),
.admin-layout.theme-dark .main :deep(.action-btns .delete-btn),
.admin-layout.theme-dark .main :deep(.action-btns .btn-delete),
.admin-layout.theme-dark .main :deep(.action-btns .danger),
.admin-layout.theme-dark .main :deep(td button[title*='Xóa']),
.admin-layout.theme-dark .main :deep(td button[title*='Xoá']) {
  border-color: #70343b !important;
  background: #321d22 !important;
  color: #ff8e98 !important;
}

.admin-layout.theme-dark .main :deep(.actions .action-delete:hover),
.admin-layout.theme-dark .main :deep(.actions .delete-btn:hover),
.admin-layout.theme-dark .main :deep(.actions .btn-delete:hover),
.admin-layout.theme-dark .main :deep(.actions .danger:hover),
.admin-layout.theme-dark .main :deep(.action-buttons .action-delete:hover),
.admin-layout.theme-dark .main :deep(.action-buttons .delete-btn:hover),
.admin-layout.theme-dark .main :deep(.action-buttons .btn-delete:hover),
.admin-layout.theme-dark .main :deep(.action-buttons .danger:hover),
.admin-layout.theme-dark .main :deep(.action-btns .action-delete:hover),
.admin-layout.theme-dark .main :deep(.action-btns .delete-btn:hover),
.admin-layout.theme-dark .main :deep(.action-btns .btn-delete:hover),
.admin-layout.theme-dark .main :deep(.action-btns .danger:hover),
.admin-layout.theme-dark .main :deep(td button[title*='Xóa']:hover),
.admin-layout.theme-dark .main :deep(td button[title*='Xoá']:hover) {
  border-color: #ef5b68 !important;
  background: #c93645 !important;
  color: #ffffff !important;
}

/* Khuyến mãi: bảo đảm chữ rõ trên nền badge và nút của thẻ gradient. */
.admin-layout .main :deep(.discount-tag.discount-percent),
.admin-layout .main :deep(.discount-tag.discount-maxprice) {
  border: 1px solid #f4cf67 !important;
  background: #fff3bf !important;
  color: #713b08 !important;
}

.admin-layout .main :deep(.discount-tag.discount-fixed) {
  border: 1px solid #93c5fd !important;
  background: #dbeafe !important;
  color: #1e3a8a !important;
}

.admin-layout .main :deep(.discount-tag.discount-freeship) {
  border: 1px solid #86efac !important;
  background: #dcfce7 !important;
  color: #14532d !important;
}

.admin-layout.theme-dark .main :deep(.stat-card-gradient .stat-card-btn) {
  border: 1px solid #ffffff !important;
  background: #ffffff !important;
  color: #1d4ed8 !important;
  box-shadow: none !important;
}

.admin-layout.theme-dark .main :deep(.stat-card-gradient .stat-card-btn:hover) {
  border-color: #bfdbfe !important;
  background: #dbeafe !important;
  color: #1e40af !important;
}

.admin-layout.theme-dark .main :deep(td .discount-tag.discount-percent),
.admin-layout.theme-dark .main :deep(td .discount-tag.discount-maxprice) {
  color: #713b08 !important;
}

.admin-layout.theme-dark .main :deep(td .discount-tag.discount-fixed) {
  color: #1e3a8a !important;
}

.admin-layout.theme-dark .main :deep(td .discount-tag.discount-freeship) {
  color: #14532d !important;
}

/* Bảng dữ liệu tối đồng nhất: loại bỏ các hàng trắng làm chữ phụ bị chìm. */
.admin-layout.theme-dark .main :deep(.table-card),
.admin-layout.theme-dark .main :deep(.table-wrap),
.admin-layout.theme-dark .main :deep(.table-container),
.admin-layout.theme-dark .main :deep(.orders-card table),
.admin-layout.theme-dark .main :deep(table) {
  border-color: #373d45 !important;
  background: #14171b !important;
}

.admin-layout.theme-dark .main :deep(thead tr),
.admin-layout.theme-dark .main :deep(thead th) {
  border-color: #3b424b !important;
  background: #20242a !important;
  color: #b8c2cf !important;
}

.admin-layout.theme-dark .main :deep(tbody tr) {
  border-color: #30363e !important;
  background: #15181c !important;
}

.admin-layout.theme-dark .main :deep(tbody tr:hover) {
  background: #1d2228 !important;
}

.admin-layout.theme-dark .main :deep(tbody tr.row-selected),
.admin-layout.theme-dark .main :deep(tbody tr.selected) {
  background: #172842 !important;
}

.admin-layout.theme-dark .main :deep(td),
.admin-layout.theme-dark .main :deep(td small),
.admin-layout.theme-dark .main :deep(td .date-text),
.admin-layout.theme-dark .main :deep(td .showing-count) {
  border-color: #30363e !important;
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(td strong),
.admin-layout.theme-dark .main :deep(td b),
.admin-layout.theme-dark .main :deep(td .promo-name),
.admin-layout.theme-dark .main :deep(td .product-name) {
  color: #edf2f8 !important;
}

.admin-layout.theme-dark .main :deep(.product-item),
.admin-layout.theme-dark .main :deep(.order-item),
.admin-layout.theme-dark .main :deep(.staff-row) {
  border-color: #363d46 !important;
  background: #171b20 !important;
}

.admin-layout.theme-dark .main :deep(.product-item span),
.admin-layout.theme-dark .main :deep(.order-item small),
.admin-layout.theme-dark .main :deep(.staff-row small),
.admin-layout.theme-dark .main :deep(.time-badge),
.admin-layout.theme-dark .main :deep(.showing-count),
.admin-layout.theme-dark .main :deep(.user-agent),
.admin-layout.theme-dark .main :deep(.empty-text) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.chip-ok) {
  border-color: #86efac !important;
  background: #dcfce7 !important;
  color: #166534 !important;
}

.admin-layout.theme-dark .main :deep(.status-badge.pending) {
  border-color: #fde68a !important;
  background: #fef3c7 !important;
  color: #854d0e !important;
}

.admin-layout.theme-dark .main :deep(.badge.badge-success) {
  border-color: #86efac !important;
  background: #dcfce7 !important;
  color: #166534 !important;
}

.admin-layout.theme-dark .main :deep(.moderation-tool h4),
.admin-layout.theme-dark .main :deep(.ai-ready h4),
.admin-layout.theme-dark .main :deep(.tool-card-head h4) {
  color: #f1f5f9 !important;
}

.admin-layout.theme-dark .main :deep(.banner-btn.warning.active) {
  border-color: #ff806e !important;
  background: #c93f32 !important;
  color: #ffffff !important;
}

.admin-layout.theme-dark .main :deep(.bottom-row .banner-card .banner-btn.warning.active) {
  color: #ffffff !important;
}

.admin-layout.theme-dark .main :deep(.variant-name),
.admin-layout.theme-dark .main :deep(td .name),
.admin-layout.theme-dark .main :deep(.slot-info .name) {
  color: #edf2f8 !important;
}

.admin-layout.theme-dark .main :deep(.nav-link),
.admin-layout.theme-dark .main :deep(.publisher-main span),
.admin-layout.theme-dark .main :deep(.xu-config-page label span) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.tab-count) {
  background: #334155 !important;
  color: #e2e8f0 !important;
}

.admin-layout.theme-dark .main :deep(.status-badge:not([class*='status-'])) {
  color: #8fb6ff !important;
}

.admin-layout.theme-dark .main :deep(td .status-badge:not([class*='status-'])) {
  border-color: #36557f !important;
  background: #1b2736 !important;
  color: #9fc0ff !important;
}

/* Nhật ký hoạt động: chip chi tiết thay đổi phải rõ trên nền bảng tối. */
.admin-layout.theme-dark .main :deep(.audit-table .log-desc) {
  color: #b8c2cf !important;
}

.admin-layout.theme-dark .main :deep(.audit-table .log-desc .highlight-text) {
  border: 1px solid #405a7c !important;
  background: #263448 !important;
  color: #dbeafe !important;
  box-decoration-break: clone;
  -webkit-box-decoration-break: clone;
  overflow-wrap: anywhere;
}

.admin-layout.theme-dark .main :deep(.audit-table .log-desc .arrow-indicator) {
  color: #60a5fa !important;
  text-shadow: none !important;
}

.admin-layout.theme-dark .main :deep(.audit-table .model-badge),
.admin-layout.theme-dark .main :deep(.audit-table .ip-address) {
  border-color: #455161 !important;
  background: #252c35 !important;
  color: #d9e2ee !important;
}

.admin-layout.theme-dark .main :deep(.admin-profile-capsule) {
  border-color: #414a56 !important;
  background: #20252c !important;
  color: #eef2f7 !important;
  box-shadow: none !important;
}

.admin-layout.theme-dark .main :deep(.admin-profile-capsule:hover) {
  border-color: #5b8def !important;
  background: #282f38 !important;
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.24) !important;
}

.admin-layout.theme-dark .main :deep(.admin-profile-capsule.online) {
  border-color: #2f7655 !important;
  background: #193529 !important;
}

.admin-layout.theme-dark .main :deep(.admin-profile-capsule.online:hover) {
  border-color: #49a979 !important;
  background: #214535 !important;
}

.admin-layout.theme-dark .main :deep(.admin-profile-capsule .admin-name) {
  color: #f1f5f9 !important;
}

.admin-layout.theme-dark .main :deep(.admin-profile-capsule .admin-email),
.admin-layout.theme-dark .main :deep(.admin-profile-capsule .last-seen) {
  color: #aeb8c6 !important;
}

.admin-layout.theme-dark .main :deep(.admin-profile-capsule .admin-status-text.online) {
  color: #6ee7a8 !important;
}

.admin-layout.theme-dark .main :deep(.admin-profile-capsule .admin-status-text.offline) {
  color: #aeb8c6 !important;
}
</style>
