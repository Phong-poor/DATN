import { createRouter, createWebHistory } from 'vue-router'
import swal from '@/services/swal'
import { getUser, getToken } from '../services/auth'

if (typeof window !== 'undefined' && 'scrollRestoration' in window.history) {
  window.history.scrollRestoration = 'manual'
}

const MainLayout = () => import('../components/Layout/BoCucChinh.vue')

const Home = () => import('../components/Web/TrangChu.vue')
const LaptopPage = () => import('../components/Web/TrangLaptop.vue')
const News = () => import('../components/Web/TinTucKhachHang.vue')
const NewsDetail = () => import('../components/Web/ChiTietTinTuc.vue')
const Cart = () => import('../components/Web/GioHang.vue')
const Checkout = () => import('../components/Web/ThanhToan.vue')
const ProductDetail = () => import('../components/Web/ChiTietSanPham.vue')
const Contact = () => import('../components/Web/LienHeKhachHang.vue')
const Profile = () => import('../components/Web/TrangCaNhan.vue')
const ChatbotWidget = () => import('../components/Web/KhungChatbot.vue')
const Orderspage = () => import('../components/Web/TrangDonHang.vue')
const Passwordpage = () => import('../components/Web/TrangMatKhau.vue')
const LoginSuccess = () => import('../components/Web/DangNhapThanhCong.vue')
const WishlistPage = () => import('../components/Web/TrangDanhSachYeuThich.vue')
const ThankYou = () => import('../components/Web/CamOn.vue')
const PaymentFailed = () => import('../components/Web/ThanhToanThatBai.vue')
const Promotions = () => import('../components/Web/KhuyenMaiKhachHang.vue')
const AffiliateCenter = () => import('../components/Web/TrungTamTiepThi.vue')

const Login = () => import('../components/Auth/DangNhap.vue')
const Register = () => import('../components/Auth/DangKy.vue')
const ForgotPassword = () => import('../components/Auth/QuenMatKhau.vue')
const OtpVerify = () => import('../components/Auth/XacThucOtp.vue')
const ResetPassword = () => import('../components/Auth/DatLaiMatKhau.vue')

const AdminLayout = () => import('../components/Admin/Layout/AdminLayout.vue')

const getUserRole = (user) => String(user?.vaitro || user?.role || '').toLowerCase()

const getDefaultAdminPath = (user = getUser()) => {
  const role = getUserRole(user)
  const defaults = {
    admin: '/admin/bang-dieu-khien',
    inventory: '/admin/quan-ly-san-pham',
    order_manager: '/admin/quan-ly-don-hang',
    marketing: '/admin/quan-ly-khuyen-mai',
    affiliate_manager: '/admin/affiliates',
    editor: '/admin/quan-ly-tin-tuc',
    support: '/admin/quan-ly-lien-he',
    accountant: '/admin/quan-ly-don-hang',
  }

  return defaults[role] || '/admin/bang-dieu-khien'
}

const publicPages = [
  '/',
  '/laptop',
  '/phu-kien',
  '/gaming',
  '/login',
  '/dang-nhap',
  '/register',
  '/dang-ky',
  '/forgot-password',
  '/quen-mat-khau',
  '/otp-verify',
  '/xac-thuc-otp',
  '/reset-password',
  '/reset_password',
  '/dat-lai-mat-khau',
  '/login-success',
  '/dang-nhap-thanh-cong',
  '/news',
  '/tin-tuc',
  '/contact',
  '/lien-he',
  '/cart',
  '/gio-hang',
  '/thank-you',
  '/cam-on',
  '/payment-failed',
  '/thanh-toan-that-bai',
  '/khuyen-mai',
]

const adminChildren = [
  { path: '', redirect: () => getDefaultAdminPath() },
  { path: 'bang-dieu-khien', alias: ['dashboard'], name: 'admin-dashboard', component: () => import('../components/Admin/BangDieuKhien.vue'), meta: { title: 'Bang dieu khien' } },
  { path: 'quan-ly-san-pham', alias: ['products'], name: 'admin-products', component: () => import('../components/Admin/QuanLySanPham.vue'), meta: { title: 'Quan ly san pham' } },
  { path: 'quan-ly-don-hang', alias: ['orders'], name: 'admin-orders', component: () => import('../components/Admin/QuanLyDonHang.vue'), meta: { title: 'Quan ly don hang' } },
  { path: 'quan-ly-nguoi-dung', alias: ['users'], name: 'admin-users', component: () => import('../components/Admin/QuanLyNguoiDung.vue'), meta: { title: 'Quan ly nguoi dung' } },
  { path: 'quan-ly-tin-tuc', alias: ['news'], name: 'admin-news', component: () => import('../components/Admin/QuanLyTinTuc.vue'), meta: { title: 'Quan ly bai viet' } },
  { path: 'bien-the', alias: ['variants'], name: 'admin-variants', component: () => import('../components/Admin/BienTheSanPham.vue'), meta: { title: 'Quan ly bien the' } },
  { path: 'quan-ly-danh-muc', alias: ['categories'], name: 'admin-categories', component: () => import('../components/Admin/QuanLyDanhMuc.vue'), meta: { title: 'Quan ly danh muc' } },
  { path: 'quan-ly-khuyen-mai', alias: ['promotions'], name: 'admin-promotions', component: () => import('../components/Admin/QuanLyKhuyenMai.vue'), meta: { title: 'Quan ly khuyen mai' } },
  { path: 'quan-ly-banner', alias: ['banners'], name: 'admin-banners', component: () => import('../components/Admin/QuanLyBanner.vue'), meta: { title: 'Quan ly banner' } },
  { path: 'quan-ly-lien-he', alias: ['contacts'], name: 'admin-contacts', component: () => import('../components/Admin/QuanLyLienHe.vue'), meta: { title: 'Quan ly lien he' } },
  { path: 'quan-ly-thuong-hieu', alias: ['brands'], name: 'admin-brands', component: () => import('../components/Admin/QuanLyThuongHieu.vue'), meta: { title: 'Quan ly thuong hieu' } },
  { path: 'reviews', name: 'admin-reviews', component: () => import('../components/Admin/QuanLyBinhLuan.vue'), meta: { title: 'Quan ly binh luan' } },
  { path: 'cai-dat-he-thong', alias: ['settings'], name: 'admin-settings', component: () => import('../components/Admin/CaiDatHeThong.vue'), meta: { title: 'Cai dat' } },
  { path: 'ho-so-quan-tri', alias: ['profile'], name: 'admin-profile', component: () => import('../components/Admin/HoSoAdmin.vue'), meta: { title: 'Ho so admin' } },
  { path: 'nhat-ky-hoat-dong', alias: ['activity-log'], name: 'admin-activity-log', component: () => import('../components/Admin/NhatKyHoatDongAdmin.vue'), meta: { title: 'Nhat ky hoat dong' } },
  { path: 'quan-ly-tiep-thi', alias: ['affiliates'], name: 'admin-affiliates', component: () => import('../components/Admin/TiepThiLienKet.vue'), meta: { title: 'Tiep thi lien ket' } },
  { path: 'hoa-don', alias: ['billing'], name: 'admin-billing', component: () => import('../components/Admin/HoaDonAdmin.vue'), meta: { title: 'Hoa don' } },
  { path: 'flash-sales', alias: ['flash-sale'], name: 'admin-flash-sales', component: () => import('../components/Admin/FlashSaleManagement.vue'), meta: { title: 'Flash sale' } },
  { path: 'gui-ma-sinh-nhat', alias: ['birthdays', 'birthday-codes'], name: 'admin-birthday-codes', component: () => import('../components/Admin/GuiMaSinhNhat.vue'), meta: { title: 'Ma sinh nhat' } },
  { path: 'combos', name: 'admin-combos', component: () => import('../components/Admin/QuanLyCombo.vue'), meta: { title: 'Quan ly combo' } },
  { path: 'xu', name: 'admin-xu', component: () => import('../components/Admin/AdminXu.vue'), meta: { title: 'Cấu hình hệ thống Xu' } },
  { path: 'vong-quay', name: 'admin-vongquay', component: () => import('../components/Admin/QuanLyVongQuay.vue'), meta: { title: 'Quản lý Vòng quay' } },
  { path: 'quan-ly-vai-tro', alias: ['roles', 'vaitro'], name: 'admin-roles', component: () => import('../components/Admin/QuanLyVaiTro.vue'), meta: { title: 'Quan ly vai tro' } },
]

const routes = [
  {
    path: '/',
    component: MainLayout,
    children: [
      { path: '', name: 'home', component: Home },
      { path: 'san-pham', alias: ['/products'], name: 'products', redirect: '/laptop' },
      { path: 'laptop', name: 'laptop', component: LaptopPage },
      { path: 'phu-kien', name: 'phu-kien', component: LaptopPage },
      { path: 'gaming', name: 'gaming', redirect: '/laptop' },
      { path: 'macbook', name: 'macbook', redirect: '/laptop' },
      { path: 'san-pham/:id', alias: ['/products/:id'], name: 'product-detail', component: ProductDetail },
      { path: 'tin-tuc', alias: ['/news'], name: 'news', component: News },
      { path: 'tin-tuc/:id', alias: ['/news/:id'], name: 'news-detail', component: NewsDetail },
      { path: 'lien-he', alias: ['/contact'], name: 'contact', component: Contact },
      { path: 'gio-hang', alias: ['/cart'], name: 'cart', component: Cart },
      { path: 'thanh-toan', alias: ['/checkout'], name: 'checkout', component: Checkout },
      { path: 'trang-ca-nhan', alias: ['/profile'], name: 'profile', component: Profile },
      { path: 'chat', name: 'chat', component: ChatbotWidget },
      { path: 'don-hang', alias: ['/orderspage'], name: 'orderspage', component: Orderspage },
      { path: 'doi-mat-khau', alias: ['/passwordpage'], name: 'passwordpage', component: Passwordpage },
      { path: 'danh-sach-yeu-thich', alias: ['/wishlistpage', '/yeu-thich'], name: 'wishlistpage', component: WishlistPage },
      { path: 'cam-on', alias: ['/thank-you'], name: 'thank-you', component: ThankYou },
      { path: 'thanh-toan-that-bai', alias: ['/payment-failed'], name: 'payment-failed', component: PaymentFailed },
      { path: 'khuyen-mai', name: 'promotions', component: Promotions },
      { path: 'tiep-thi-lien-ket', alias: ['/affiliate'], name: 'affiliate-center', component: AffiliateCenter },
    ],
  },

  { path: '/dang-nhap', alias: ['/login'], name: 'login', component: Login },
  { path: '/dang-ky', alias: ['/register'], name: 'register', component: Register },
  { path: '/quen-mat-khau', alias: ['/forgot-password'], name: 'forgot-password', component: ForgotPassword },
  { path: '/xac-thuc-otp', alias: ['/otp-verify'], name: 'otp-verify', component: OtpVerify },
  { path: '/dat-lai-mat-khau', alias: ['/reset-password', '/reset_password'], name: 'reset-password', component: ResetPassword },
  { path: '/dang-nhap-thanh-cong', alias: ['/login-success'], name: 'login-success', component: LoginSuccess },

  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAdmin: true },
    children: adminChildren,
  },

  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0, left: 0 }
  },
})

const forceScrollTop = () => {
  if (typeof window === 'undefined') return
  window.scrollTo({ top: 0, left: 0, behavior: 'instant' })
}

const showRouteLoader = () => {
  if (typeof window === 'undefined') return

  window.dispatchEvent(
    new CustomEvent('global-loader-show', {
      detail: { immediate: true, minDuration: 260 },
    })
  )
}

router.afterEach(() => {
  forceScrollTop()
  requestAnimationFrame(forceScrollTop)

  setTimeout(() => {
    forceScrollTop()
    window.dispatchEvent(new Event('global-loader-force-hide'))
  }, 120)
})

router.beforeEach((to, from, next) => {
  const shouldShowRouteLoader =
    to.fullPath !== from.fullPath && !to.path.startsWith('/products/') && !to.path.startsWith('/san-pham/')

  if (shouldShowRouteLoader) {
    showRouteLoader()
  }

  forceScrollTop()

  const user = getUser()
  const token = getToken()
  const isPublic =
    publicPages.includes(to.path) ||
    to.path.startsWith('/products/') ||
    to.path.startsWith('/san-pham/') ||
    to.path.startsWith('/news/') ||
    to.path.startsWith('/tin-tuc/')

  if (!isPublic && !token) {
    return next({ path: '/dang-nhap', query: { redirect: to.fullPath } })
  }

  if (to.matched.some((route) => route.meta.requiresAdmin)) {
    if (!user || !token) return next({ path: '/dang-nhap', query: { redirect: to.fullPath } })
    const role = getUserRole(user)

    if (role === 'user') return next('/')

    if (!role) return next('/')

    if (role !== 'admin') {
      const pathPermissionMap = {
        '/admin/quan-ly-san-pham': 'san_pham_xem',
        '/admin/products': 'san_pham_xem',
        '/admin/quan-ly-danh-muc': 'danh_muc_xem',
        '/admin/categories': 'danh_muc_xem',
        '/admin/quan-ly-thuong-hieu': 'thuong_hieu_xem',
        '/admin/brands': 'thuong_hieu_xem',
        '/admin/bien-the': 'bien_the_xem',
        '/admin/variants': 'bien_the_xem',
        '/admin/quan-ly-don-hang': 'don_hang_xem',
        '/admin/orders': 'don_hang_xem',
        '/admin/hoa-don': 'hoa_don_xem',
        '/admin/billing': 'hoa_don_xem',
        '/admin/quan-ly-khuyen-mai': 'marketing_quan_ly',
        '/admin/promotions': 'marketing_quan_ly',
        '/admin/gui-ma-sinh-nhat': 'marketing_quan_ly',
        '/admin/birthdays': 'marketing_quan_ly',
        '/admin/birthday-codes': 'marketing_quan_ly',
        '/admin/combos': 'marketing_quan_ly',
        '/admin/flash-sales': 'marketing_quan_ly',
        '/admin/flash-sale': 'marketing_quan_ly',
        '/admin/quan-ly-tiep-thi': 'affiliate_quan_ly',
        '/admin/affiliates': 'affiliate_quan_ly',
        '/admin/quan-ly-tin-tuc': 'tin_tuc_quan_ly',
        '/admin/news': 'tin_tuc_quan_ly',
        '/admin/reviews': 'binh_luan_quan_ly',
        '/admin/quan-ly-banner': 'banner_quan_ly',
        '/admin/banners': 'banner_quan_ly',
        '/admin/quan-ly-lien-he': 'lien_he_quan_ly',
        '/admin/contacts': 'lien_he_quan_ly',
        '/admin/quan-ly-nguoi-dung': 'tai_khoan_quan_ly',
        '/admin/users': 'tai_khoan_quan_ly',
        '/admin/quan-ly-vai-tro': 'vai_tro_quan_ly',
        '/admin/roles': 'vai_tro_quan_ly',
        '/admin/vaitro': 'vai_tro_quan_ly',
        '/admin/nhat-ky-hoat-dong': 'nhat_ky_quan_ly',
        '/admin/activity-log': 'nhat_ky_quan_ly',
      }

      const basicPaths = [
        '/admin',
        '/admin/bang-dieu-khien',
        '/admin/ho-so-quan-tri',
        '/admin/profile',
        '/admin/cai-dat-he-thong',
        '/admin/settings'
      ]

      const cleanPath = to.path.replace(/\/$/, '')
      const isBasic = basicPaths.some(path => {
        if (path === '/admin') return cleanPath === '/admin'
        return cleanPath === path || cleanPath.startsWith(path + '/')
      })

      if (!isBasic) {
        let requiredPerm = null
        for (const [routePath, permission] of Object.entries(pathPermissionMap)) {
          if (cleanPath === routePath || cleanPath.startsWith(routePath + '/')) {
            requiredPerm = permission
            break
          }
        }

        if (requiredPerm) {
          const userPerms = user.cac_quyen || []
          if (!userPerms.includes(requiredPerm)) {
            swal.error('Từ chối truy cập', 'Chức vụ của bạn không có quyền vào chức năng này!')
            return next(false)
          }
        }
      }
    }
  }

  next()
})

export default router
