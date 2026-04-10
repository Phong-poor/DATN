<template>
  <div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

      <!-- LOGO -->
      <div class="sidebar-logo">
        <div class="logo-icon">N</div>
        <span class="logo-text">NextGen LabTop</span>
      </div>

      <!-- MAIN MENU -->
      <div class="menu-section">
        <p class="menu-label">MAIN MENU</p>

        <router-link to="/admin" v-slot="{ isExactActive }">
          <div :class="['item', isExactActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="3" width="7" height="7" rx="1" />
              <rect x="14" y="3" width="7" height="7" rx="1" />
              <rect x="3" y="14" width="7" height="7" rx="1" />
              <rect x="14" y="14" width="7" height="7" rx="1" />
            </svg>
            Dashboard
          </div>
        </router-link>

        <router-link to="/admin/products" v-slot="{ isActive }">
          <div :class="['item', isActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
              <line x1="3" y1="6" x2="21" y2="6" />
              <path d="M16 10a4 4 0 0 1-8 0" />
            </svg>
            Sản Phẩm
          </div>
        </router-link>

        <router-link to="/admin/orders" v-slot="{ isActive }">
          <div :class="['item', isActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none">
              <circle cx="9" cy="21" r="1" />
              <circle cx="20" cy="21" r="1" />
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
            </svg>
            Đơn Hàng
          </div>
        </router-link>

        <router-link to="/admin/news" v-slot="{ isActive }">
          <div :class="['item', isActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
              <polyline points="14 2 14 8 20 8" />
              <line x1="16" y1="13" x2="8" y2="13" />
              <line x1="16" y1="17" x2="8" y2="17" />
              <polyline points="10 9 9 9 8 9" />
            </svg>
            Bài Viết
          </div>
        </router-link>

        <router-link to="/admin/categories" v-slot="{ isActive }">
          <div :class="['item', isActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none">
              <polygon points="12 2 2 7 12 12 22 7 12 2" />
              <polyline points="2 17 12 22 22 17" />
              <polyline points="2 12 12 17 22 12" />
            </svg>
            Danh Mục
          </div>
        </router-link>

        <router-link to="/admin/brands" v-slot="{ isActive }">
          <div :class="['item', isActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none">
              <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
              <line x1="7" y1="7" x2="7.01" y2="7" />
            </svg>
            Thương Hiệu
          </div>
        </router-link>

        <router-link to="/admin/promotions" v-slot="{ isActive }">
          <div :class="['item', isActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
            Khuyến Mãi
          </div>
        </router-link>

        <router-link to="/admin/variants" v-slot="{ isActive }">
          <div :class="['item', isActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none">
              <circle cx="13.5" cy="6.5" r="2.5" />
              <circle cx="6.5" cy="12" r="2.5" />
              <circle cx="13.5" cy="17.5" r="2.5" />
              <path d="M11 6.5H3M11 17.5H3M16 12h5" />
            </svg>
            Màu & Biến Thể
          </div>
        </router-link>


        <router-link to="/admin/users" v-slot="{ isActive }">
          <div :class="['item', isActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            User
          </div>
        </router-link>

        <router-link to="/admin/reviews" v-slot="{ isActive }">
          <div :class="['item', isActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              <line x1="9" y1="9" x2="15" y2="9" />
              <line x1="9" y1="13" x2="13" y2="13" />
            </svg>
            Bình Luận
          </div>
        </router-link>

        <router-link to="/admin/contacts" v-slot="{ isActive }">
          <div :class="['item', isActive && 'active']">
            <svg class="item-icon" viewBox="0 0 24 24" fill="none">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
            </svg>
            Liên Hệ
          </div>
        </router-link>

      </div>

      <!-- HỆ THỐNG -->
      <div class="menu-section">
        <p class="menu-label">HỆ THỐNG</p>

        <div class="item logout" @click="handleLogout">
          <svg class="item-icon" viewBox="0 0 24 24" fill="none">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" y1="12" x2="9" y2="12" />
          </svg>
          Đăng Xuất
        </div>
      </div>

      <!-- USER INFO -->
      <div class="sidebar-user">
        <div class="user-avatar">AD</div>
        <div class="user-info">
          <p class="user-name">Admin</p>
          <p class="user-role">Quản trị viên</p>
        </div>
      </div>

    </aside>

    <!-- CONTENT -->
    <main class="main">
      <router-view />
    </main>

  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

function handleLogout() {
  localStorage.removeItem('token')
  router.push('/login')
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.admin-layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: #f5f7fb;
  font-family: 'Inter', sans-serif;
}

/* ─────────────── SIDEBAR ─────────────── */
.sidebar {
  width: 220px;
  min-width: 220px;
  background: #071d52;
  padding: 20px 14px;
  height: 100vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.sidebar::-webkit-scrollbar {
  width: 4px;
}

.sidebar::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
  background: #1e293b;
  border-radius: 4px;
}

/* LOGO */
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 6px 16px;
  border-bottom: 1px solid #1e293b;
  margin-bottom: 4px;
}

.logo-icon {
  width: 34px;
  height: 34px;
  background: linear-gradient(135deg, #10b981, #059669);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 15px;
}

.logo-text {
  color: #f1f5f9;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 0.3px;
}

/* MENU SECTION */
.menu-section {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.menu-label {
  font-size: 10px;
  font-weight: 700;
  color: #475569;
  letter-spacing: 1px;
  padding: 10px 10px 4px;
  margin: 0;
}

/* ITEM */
a {
  text-decoration: none;
}

.item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  color: #94a3b8;
  cursor: pointer;
  font-size: 13.5px;
  font-weight: 500;
  transition: all 0.18s ease;
}

.item:hover {
  background: #1e293b;
  color: #e2e8f0;
}

.item-icon {
  width: 17px;
  height: 17px;
  stroke: currentColor;
  stroke-width: 1.8;
  fill: none;
  flex-shrink: 0;
  stroke-linecap: round;
  stroke-linejoin: round;
}

/* ACTIVE */
.item.active {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: #ffffff;
  font-weight: 600;
}

.item.active .item-icon {
  stroke: #ffffff;
}

/* ĐĂNG XUẤT */
.logout {
  color: #f87171;
  margin-top: 2px;
}

.logout:hover {
  background: #1e293b;
  color: #ef4444;
}

/* USER INFO */
.sidebar-user {
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px solid #1e293b;
  display: flex;
  align-items: center;
  gap: 10px;
  padding-left: 6px;
}

.user-avatar {
  width: 34px;
  height: 34px;
  background: linear-gradient(135deg, #10b981, #059669);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 12px;
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  margin: 0;
  font-size: 13px;
  font-weight: 600;
  color: #e2e8f0;
}

.user-role {
  margin: 0;
  font-size: 11px;
  color: #64748b;
}

/* MAIN */
.main {
  flex: 1;
  padding: 30px;
  height: 100vh;
  overflow-y: auto;
}
</style>