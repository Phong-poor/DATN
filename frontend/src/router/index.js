import { createRouter, createWebHistory } from 'vue-router'

// ── Layout ──
import MainLayout from '../components/Layout/MainLayout.vue'

// ── Web Pages ──
import Home from '../components/Web/Home.vue'
import Producpage from '../components/Web/Producpage.vue'
import News from '../components/Web/News.vue'
import NewsDetail from '../components/Web/NewsDetail.vue'
import Cart from '../components/Web/Cart.vue'
import Checkout from '../components/Web/Checkout.vue'
import ProductDetail from '../components/Web/ProductDetail.vue'
import Contact from '../components/Web/Contact.vue'
import Profile from '../components/Web/Profile.vue'
import ChatbotWidget from '../components/Web/ChatbotWidget.vue'
import Orderspage from '../components/Web/Orderspage.vue'
import Passwordpage from '../components/Web/Passwordpage.vue'
import LoginSuccess from '../components/Web/LoginSuccess.vue'
import WishlistPage from '../components/Web/WishlistPage.vue'
import ThankYou from '../components/Web/ThankYou.vue'
import PaymentFailed from '../components/Web/PaymentFailed.vue'
import InteractiveLabs from '../components/Web/InteractiveLabs.vue'
import AffiliateCenter from '../components/Web/AffiliateCenter.vue'

// ── Auth ──
import Login from '../components/Auth/Login.vue'
import Register from '../components/Auth/Register.vue'
import ForgotPassword from '../components/Auth/ForgotPassword.vue'
import OtpVerify from '../components/Auth/OtpVerify.vue'
import ResetPassword from '../components/Auth/ResetPassword.vue'

import { getUser, getToken } from '../services/auth'

// ── Admin ──
import AdminLayout from '../components/Admin/Layout/AdminLayout.vue'
import AdminDashboard from '../components/Admin/Dashboard.vue'

const routes = [
  // ── WEB ──
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', name: 'home', component: Home },
      { path: 'products', name: 'products', component: Producpage },
      { path: 'products/:id', name: 'product-detail', component: ProductDetail },
      { path: 'news', name: 'news', component: News },
      { path: 'news/:id', name: 'news-detail', component: NewsDetail },
      { path: 'contact', name: 'contact', component: Contact },
      { path: 'cart', name: 'cart', component: Cart },
      { path: 'checkout', name: 'checkout', component: Checkout },
      { path: 'profile', name: 'profile', component: Profile },
      { path: 'chat', name: 'chat', component: ChatbotWidget },
      { path: 'orderspage', name: 'orderspage', component: Orderspage },
      { path: 'passwordpage', name: 'passwordpage', component: Passwordpage },
      { path: 'wishlistpage', name: 'wishlistpage', component: WishlistPage },
      { path: 'thank-you', name: 'thank-you', component: ThankYou },
      { path: 'payment-failed', name: 'payment-failed', component: PaymentFailed },
      { path: 'interactive-labs', name: 'interactive-labs', component: InteractiveLabs },
      { path: 'affiliate', name: 'affiliate', component: AffiliateCenter },
    ],
  },

  // ── AUTH ──
  { path: '/login', name: 'login', component: Login },
  { path: '/register', name: 'register', component: Register },
  { path: '/forgot-password', name: 'forgot-password', component: ForgotPassword },
  { path: '/otp-verify', name: 'otp-verify', component: OtpVerify },
  { path: '/reset-password', name: 'reset-password', component: ResetPassword },
  { path: '/login-success', name: 'login-success', component: LoginSuccess },

  // ── ADMIN ──
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAdmin: true },
    children: [
      { path: '', name: 'admin-dashboard', component: AdminDashboard, meta: { title: 'Tổng quan hệ thống' } },
      { path: 'products', name: 'admin-products', component: () => import('../components/Admin/Products.vue'), meta: { title: 'Quản lý sản phẩm' } },
      { path: 'orders', name: 'admin-orders', component: () => import('../components/Admin/Orders.vue'), meta: { title: 'Quản lý đơn hàng' } },
      { path: 'users', name: 'admin-users', component: () => import('../components/Admin/Users.vue'), meta: { title: 'Quản lý người dùng' } },
      { path: 'news', name: 'admin-news', component: () => import('../components/Admin/News.vue'), meta: { title: 'Quản lý bài viết' } },
      { path: 'settings', name: 'admin-settings', component: () => import('../components/Admin/Settings.vue'), meta: { title: 'Cài đặt hệ thống' } },
      { path: 'profile', name: 'admin-profile', component: () => import('../components/Admin/AdminProfile.vue'), meta: { title: 'Hồ sơ quản trị' } },
      { path: 'activity-log', name: 'admin-activity-log', component: () => import('../components/Admin/AdminActivityLog.vue'), meta: { title: 'Nhật ký hoạt động' } },
      { path: 'billing', name: 'admin-billing', component: () => import('../components/Admin/AdminBilling.vue'), meta: { title: 'Billing quản trị' } },
      { path: 'variants', name: 'admin-variants', component: () => import('../components/Admin/ProductVariants.vue'), meta: { title: 'Quản lý biến thể' } },
      { path: 'categories', name: 'admin-categories', component: () => import('../components/Admin/Categories.vue'), meta: { title: 'Quản lý danh mục' } },
      { path: 'promotions', name: 'admin-promotions', component: () => import('../components/Admin/Promotions.vue'), meta: { title: 'Quản lý khuyến mãi' } },
      { path: 'banners', name: 'admin-banners', component: () => import('../components/Admin/Banners.vue'), meta: { title: 'Quản lý banner' } },
      { path: 'contacts', name: 'admin-contacts', component: () => import('../components/Admin/Contact.vue'), meta: { title: 'Quản lý liên hệ' } },
      { path: 'brands', name: 'admin-brands', component: () => import('../components/Admin/Brands.vue'), meta: { title: 'Quản lý thương hiệu' } },
      { path: 'reviews', name: 'admin-reviews', component: () => import('../components/Admin/ReviewManagement.vue'), meta: { title: 'Quản lý bình luận' } },
      { path: 'affiliates', name: 'admin-affiliates', component: () => import('../components/Admin/Affiliates.vue'), meta: { title: 'Quản lý affiliate' } },
    ],
  },

  // ── 404 fallback ──
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.afterEach(() => {
  setTimeout(() => {
    window.dispatchEvent(new Event('global-loader-hide'))
  }, 120)
})

router.beforeEach((to, from, next) => {
  window.dispatchEvent(new Event('global-loader-show'))
  const user = getUser()
  const token = getToken()

  const publicPages = [
    '/',
    '/products',
    '/login',
    '/register',
    '/forgot-password',
    '/otp-verify',
    '/reset-password',
    '/login-success',
    '/news',
    '/contact',
    '/cart',
    '/thank-you',
    '/payment-failed',
    '/interactive-labs',
  ]

  const isPublic =
    publicPages.includes(to.path) ||
    to.path.startsWith('/products/') ||
    to.path.startsWith('/news/')

  if (!isPublic && !token) {
    return next('/login')
  }

  if (to.matched.some(route => route.meta.requiresAdmin)) {
    if (!user || !token) return next('/login')
    if (user.role !== 'admin') return next('/')
  }

  next()
})

export default router
