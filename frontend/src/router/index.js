import { createRouter, createWebHistory } from 'vue-router'

// ── Layout ──
import MainLayout from '../components/Layout/MainLayout.vue'

// ── Web Pages ──
import Home from '../components/Web/Home.vue'
import Producpage from '../components/Web/Producpage.vue'
import News from '../components/Web/News.vue'
import Cart from '../components/Web/Cart.vue'
import Checkout from '../components/Web/Checkout.vue'
import ProductDetail from '../components/Web/ProductDetail.vue'
import Contact from '../components/Web/Contact.vue'
import Profile from '../components/Web/Profile.vue'
import Chatbot from '../components/Web/Chatbot.vue'
import Orderspage from '../components/Web/Orderspage.vue'
import Addresspage from '../components/Web/Addresspage.vue'
import Passwordpage from '../components/Web/Passwordpage.vue'
import LoginSuccess from '../components/Web/LoginSuccess.vue'
import WishlistPage from '../components/Web/WishlistPage.vue'

// ── Auth ──
import Login from '../components/Auth/Login.vue'
import Register from '../components/Auth/Register.vue'
import ForgotPassword from '../components/Auth/ForgotPassword.vue'
import OtpVerify from '../components/Auth/OtpVerify.vue'
import ResetPassword from '../components/Auth/ResetPassword.vue'

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
      { path: 'contact', name: 'contact', component: Contact },
      { path: 'cart', name: 'cart', component: Cart },
      { path: 'checkout', name: 'checkout', component: Checkout },
      { path: 'profile', name: 'profile', component: Profile },
      { path: 'chat', name: 'chat', component: Chatbot },
      { path: 'orderspage', name: 'orderspage', component: Orderspage },
      { path: 'addresspage', name: 'addresspage', component: Addresspage },
      { path: 'passwordpage', name: 'passwordpage', component: Passwordpage },
      { path: 'wishlistpage', name: 'wishlistpage', component: WishlistPage },
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
      { path: '', name: 'admin-dashboard', component: AdminDashboard },
      { path: 'products', name: 'admin-products', component: () => import('../components/Admin/Products.vue') },
      { path: 'orders', name: 'admin-orders', component: () => import('../components/Admin/Orders.vue') },
      { path: 'users', name: 'admin-users', component: () => import('../components/Admin/Users.vue') },
      { path: 'news', name: 'admin-news', component: () => import('../components/Admin/News.vue') },
      { path: 'settings', name: 'admin-settings', component: () => import('../components/Admin/Settings.vue') },
      { path: 'variants', name: 'admin-variants', component: () => import('../components/Admin/ProductVariants.vue') },
      { path: 'categories', name: 'admin-categories', component: () => import('../components/Admin/Categories.vue') },
      { path: 'promotions', name: 'admin-promotions', component: () => import('../components/Admin/Promotions.vue') },
      { path: 'contacts', name: 'admin-contacts', component: () => import('../components/Admin/Contact.vue') },
      { path: 'brands', name: 'admin-brands', component: () => import('../components/Admin/Brands.vue') },
      { path: 'reviews', name: 'admin-reviews', component: () => import('../components/Admin/ReviewManagement.vue') },
    ],
  },

  // ── 404 fallback ──
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const user = JSON.parse(localStorage.getItem('user') || 'null')
  const token = localStorage.getItem('token')

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
  ]

  const isPublic =
    publicPages.includes(to.path) ||
    to.path.startsWith('/products/')

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