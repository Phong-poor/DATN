import { createRouter, createWebHistory } from 'vue-router'

// Vô hiệu hóa tính năng tự động khôi phục vị trí cuộn của trình duyệt khi reload trang
if (typeof window !== 'undefined' && 'scrollRestoration' in window.history) {
  window.history.scrollRestoration = 'manual'
}

// ── Layout ──
const MainLayout = () => import('../components/Layout/MainLayout.vue')

// ── Web Pages ──
const Home = () => import('../components/Web/Home.vue')
const Producpage = () => import('../components/Web/ProductsPremiumPage.vue')
const GamingPage = () => import('../components/Web/GamingPage.vue')
const LandingPage = () => import('../components/Web/LandingPage.vue')
const News = () => import('../components/Web/News.vue')
const NewsDetail = () => import('../components/Web/NewsDetail.vue')
const Cart = () => import('../components/Web/Cart.vue')
const Checkout = () => import('../components/Web/Checkout.vue')
const ProductDetail = () => import('../components/Web/ProductDetail.vue')
const Contact = () => import('../components/Web/Contact.vue')
const Profile = () => import('../components/Web/Profile.vue')
const ChatbotWidget = () => import('../components/Web/ChatbotWidget.vue')
const Orderspage = () => import('../components/Web/Orderspage.vue')
const Passwordpage = () => import('../components/Web/Passwordpage.vue')
const LoginSuccess = () => import('../components/Web/LoginSuccess.vue')
const WishlistPage = () => import('../components/Web/WishlistPage.vue')
const ThankYou = () => import('../components/Web/ThankYou.vue')
const PaymentFailed = () => import('../components/Web/PaymentFailed.vue')
const InteractiveLabs = () => import('../components/Web/InteractiveLabs.vue')
const AffiliateCenter = () => import('../components/Web/AffiliateCenter.vue')
const Promotions = () => import('../components/Web/Promotions.vue')
const Workstation = () => import('../components/Web/Workstation.vue')


// ── Auth ──
const Login = () => import('../components/Auth/Login.vue')
const Register = () => import('../components/Auth/Register.vue')
const ForgotPassword = () => import('../components/Auth/ForgotPassword.vue')
const OtpVerify = () => import('../components/Auth/OtpVerify.vue')
const ResetPassword = () => import('../components/Auth/ResetPassword.vue')

import { getUser, getToken } from '../services/auth'

// ── Admin ──
const AdminLayout = () => import('../components/Admin/Layout/AdminLayout.vue')
const AdminDashboard = () => import('../components/Admin/Dashboard.vue')

const routes = [
  // ── WEB ──
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', name: 'home', component: Home },
      { path: 'products', name: 'products', component: Producpage },
      { path: 'gaming', name: 'gaming', component: GamingPage },
      { path: 'macbook', name: 'macbook', component: Producpage, meta: { category: 'MacBook' } },
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
      { path: 'khuyen-mai', name: 'promotions', component: Promotions },
      { path: 'workstation', name: 'workstation', component: Workstation },
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
      { path: 'combos', name: 'admin-combos', component: () => import('../components/Admin/ComboManagement.vue'), meta: { title: 'Quản lý Combo' } },
      { path: 'banners', name: 'admin-banners', component: () => import('../components/Admin/Banners.vue'), meta: { title: 'Quản lý banner' } },
      { path: 'contacts', name: 'admin-contacts', component: () => import('../components/Admin/Contact.vue'), meta: { title: 'Quản lý liên hệ' } },
      { path: 'brands', name: 'admin-brands', component: () => import('../components/Admin/Brands.vue'), meta: { title: 'Quản lý thương hiệu' } },
      { path: 'reviews', name: 'admin-reviews', component: () => import('../components/Admin/ReviewManagement.vue'), meta: { title: 'Quản lý bình luận' } },
      { path: 'affiliates', name: 'admin-affiliates', component: () => import('../components/Admin/Affiliates.vue'), meta: { title: 'Quản lý affiliate' } },
    ],
  },

  // 404 fallback
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0, left: 0 }
  }
})

const forceScrollTop = () => {
  window.scrollTo({ top: 0, left: 0, behavior: 'instant' })
}

router.afterEach(() => {
  // Cưỡng bức cuộn lên đầu trang ngay khi chuyển trang xong ở mức router
  forceScrollTop()
  requestAnimationFrame(forceScrollTop)
  
  // Thực hiện cuộn phụ sau 120ms để bù đắp sự thay đổi chiều cao do các tiến trình render bất đồng bộ (API/Transitions)
  setTimeout(() => {
    forceScrollTop()
    window.dispatchEvent(new Event('global-loader-hide'))
  }, 120)
})

router.beforeEach((to, from, next) => {
    forceScrollTop()
    const user = getUser()
    const token = getToken()

    const publicPages = [
      '/',
      '/products',
      '/gaming',
      '/macbook',
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
      '/khuyen-mai',
      '/workstation',
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
