import { createRouter, createWebHistory } from 'vue-router'
import { getUser, getToken } from '../services/auth'
import { isFormDirty } from '../services/unsavedChanges'

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
const SepayPayment = () => import('../components/Web/ThanhToanSepay.vue')
const Promotions = () => import('../components/Web/KhuyenMaiKhachHang.vue')
const AffiliateCenter = () => import('../components/Web/TrungTamTiepThi.vue')

const Login = () => import('../components/Auth/DangNhap.vue')
const Register = () => import('../components/Auth/DangKy.vue')
const ForgotPassword = () => import('../components/Auth/QuenMatKhau.vue')
const OtpVerify = () => import('../components/Auth/XacThucOtp.vue')
const TwoFactorVerify = () => import('../components/Auth/XacThuc2FA.vue')
const ResetPassword = () => import('../components/Auth/DatLaiMatKhau.vue')

const AdminLayout = () => import('../components/Admin/Layout/BoCucAdmin.vue')

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

const adminChildren = [
  { path: '', redirect: () => getDefaultAdminPath() },
  { path: 'bang-dieu-khien', alias: ['dashboard'], name: 'admin-dashboard', component: () => import('../components/Admin/BangDieuKhien.vue'), meta: { title: 'Bảng điều khiển' } },
  { path: 'quan-ly-san-pham', alias: ['products'], name: 'admin-products', component: () => import('../components/Admin/QuanLySanPham.vue'), meta: { title: 'Quản lý sản phẩm' } },
  { path: 'quan-ly-don-hang', alias: ['orders'], name: 'admin-orders', component: () => import('../components/Admin/QuanLyDonHang.vue'), meta: { title: 'Quản lý đơn hàng' } },
  { path: 'thong-ke-doanh-so-nhan-vien', name: 'admin-employee-stats', component: () => import('../components/Admin/ThongKeNhanVien.vue'), meta: { title: 'Doanh số nhân viên' } },
  { path: 'quan-ly-nguoi-dung', alias: ['users'], name: 'admin-users', component: () => import('../components/Admin/QuanLyNguoiDung.vue'), meta: { title: 'Quản lý người dùng' } },
  { path: 'quan-ly-tin-tuc', alias: ['news'], name: 'admin-news', component: () => import('../components/Admin/QuanLyTinTuc.vue'), meta: { title: 'Quản lý bài viết' } },
  { path: 'bien-the', alias: ['variants', 'bien-the-san-pham'], name: 'admin-variants', component: () => import('../components/Admin/BienTheSanPham.vue'), meta: { title: 'Quản lý biến thể' } },
  { path: 'quan-ly-danh-muc', alias: ['categories'], name: 'admin-categories', component: () => import('../components/Admin/QuanLyDanhMuc.vue'), meta: { title: 'Quản lý danh mục' } },
  { path: 'quan-ly-khuyen-mai', alias: ['promotions'], name: 'admin-promotions', component: () => import('../components/Admin/QuanLyKhuyenMai.vue'), meta: { title: 'Quản lý khuyến mãi' } },
  { path: 'quan-ly-banner', alias: ['banners'], name: 'admin-banners', component: () => import('../components/Admin/QuanLyBanner.vue'), meta: { title: 'Quản lý banner' } },
  { path: 'quan-ly-lien-he', alias: ['contacts'], name: 'admin-contacts', component: () => import('../components/Admin/QuanLyLienHe.vue'), meta: { title: 'Quản lý liên hệ' } },
  { path: 'quan-ly-chat', alias: ['chat', 'chat-admin', 'quan-ly-tin-nhan'], name: 'admin-chat', component: () => import('../components/Admin/QuanLyChat.vue'), meta: { title: 'Quản lý tin nhắn' } },
  { path: 'quan-ly-thuong-hieu', alias: ['brands'], name: 'admin-brands', component: () => import('../components/Admin/QuanLyThuongHieu.vue'), meta: { title: 'Quản lý thương hiệu' } },
  { path: 'reviews', alias: ['quan-ly-binh-luan'], name: 'admin-reviews', component: () => import('../components/Admin/QuanLyBinhLuan.vue'), meta: { title: 'Quản lý bình luận' } },
  { path: 'cai-dat-he-thong', alias: ['settings'], name: 'admin-settings', component: () => import('../components/Admin/CaiDatHeThong.vue'), meta: { title: 'Cài đặt hệ thống' } },
  { path: 'ho-so-quan-tri', alias: ['profile'], name: 'admin-profile', component: () => import('../components/Admin/HoSoAdmin.vue'), meta: { title: 'Hồ sơ quản trị' } },
  { path: 'nhat-ky-hoat-dong', alias: ['activity-log'], name: 'admin-activity-log', component: () => import('../components/Admin/NhatKyHoatDongAdmin.vue'), meta: { title: 'Nhật ký hoạt động' } },
  { path: 'quan-ly-tiep-thi', alias: ['affiliates'], name: 'admin-affiliates', component: () => import('../components/Admin/TiepThiLienKet.vue'), meta: { title: 'Quản lý tiếp thị liên kết' } },
  { path: 'hoa-don', alias: ['billing'], name: 'admin-billing', component: () => import('../components/Admin/HoaDonAdmin.vue'), meta: { title: 'Hóa đơn' } },
  { path: 'flash-sales', alias: ['flash-sale'], name: 'admin-flash-sales', component: () => import('../components/Admin/FlashSaleManagement.vue'), meta: { title: 'Flash sale' } },
  { path: 'gui-ma-sinh-nhat', alias: ['birthdays', 'birthday-codes'], name: 'admin-birthday-codes', component: () => import('../components/Admin/GuiMaSinhNhat.vue'), meta: { title: 'Mã sinh nhật' } },
  { path: 'combos', alias: ['quan-ly-combo'], name: 'admin-combos', component: () => import('../components/Admin/QuanLyCombo.vue'), meta: { title: 'Quản lý combo' } },
  { path: 'xu', name: 'admin-xu', component: () => import('../components/Admin/AdminXu.vue'), meta: { title: 'Cấu hình hệ thống Xu' } },
  { path: 'vong-quay', name: 'admin-vongquay', component: () => import('../components/Admin/QuanLyVongQuay.vue'), meta: { title: 'Quản lý Vòng quay' } },
  { path: 'diem-danh', name: 'admin-diemdanh', component: () => import('../components/Admin/QuanLyDiemDanh.vue'), meta: { title: 'Quản lý Điểm danh' } },
  { path: 'quan-ly-vai-tro', alias: ['roles', 'vaitro'], name: 'admin-roles', component: () => import('../components/Admin/QuanLyVaiTro.vue'), meta: { title: 'Quản lý vai trò' } },
  { path: 'cham-cong-camera', name: 'admin-chamcong-camera', component: () => import('../components/Admin/ChamCongCamera.vue'), meta: { title: 'Xác thực nhân viên' } },
  { path: 'quan-ly-cham-cong', name: 'admin-quanly-chamcong', component: () => import('../components/Admin/QuanLyChamCong.vue'), meta: { title: 'Quản lý chấm công' } },
  { path: 'quan-ly-don-xin-nghi', name: 'admin-quanly-don-xin-nghi', component: () => import('../components/Admin/QuanLyDonXinNghi.vue'), meta: { title: 'Quản lý đơn nghỉ', requiresSuperAdmin: true } },
  { path: 'xin-nghi-phep', name: 'admin-xin-nghi-phep', component: () => import('../components/Admin/XinNghiPhep.vue'), meta: { title: 'Xin nghỉ phép' } },
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
      { path: 'thanh-toan', alias: ['/checkout'], name: 'checkout', component: Checkout, meta: { requiresAuth: true } },
      { path: 'thanh-toan/sepay/:id', name: 'sepay-payment', component: SepayPayment, meta: { requiresAuth: true } },
      { path: 'trang-ca-nhan', alias: ['/profile'], name: 'profile', component: Profile, meta: { requiresAuth: true } },
      { path: 'chat', name: 'chat', component: ChatbotWidget },
      { path: 'don-hang', alias: ['/orderspage'], name: 'orderspage', component: Orderspage, meta: { requiresAuth: true } },
      { path: 'doi-mat-khau', alias: ['/passwordpage'], name: 'passwordpage', component: Passwordpage },
      { path: 'danh-sach-yeu-thich', alias: ['/wishlistpage', '/yeu-thich'], name: 'wishlistpage', component: WishlistPage, meta: { requiresAuth: true } },
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
  { path: '/xac-thuc-2fa', alias: ['/two-factor-challenge'], name: 'two-factor-challenge', component: TwoFactorVerify },
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
      detail: { immediate: true, minDuration: 160 },
    })
  )
}

let routeLoaderTimer = null

const scheduleRouteLoader = () => {
  if (routeLoaderTimer) window.clearTimeout(routeLoaderTimer)
  routeLoaderTimer = window.setTimeout(() => {
    routeLoaderTimer = null
    showRouteLoader()
  }, 120)
}

const cancelScheduledRouteLoader = () => {
  if (!routeLoaderTimer) return
  window.clearTimeout(routeLoaderTimer)
  routeLoaderTimer = null
}

router.afterEach(() => {
  cancelScheduledRouteLoader()
  forceScrollTop()
  requestAnimationFrame(forceScrollTop)

  setTimeout(() => {
    forceScrollTop()
    window.dispatchEvent(new Event('global-loader-force-hide'))
  }, 120)
})

router.beforeEach((to, from, next) => {
  const isAdminInternalNavigation =
    to.path.startsWith('/admin') && from.path.startsWith('/admin')
  const shouldShowRouteLoader =
    to.fullPath !== from.fullPath &&
    !isAdminInternalNavigation &&
    !to.path.startsWith('/products/') &&
    !to.path.startsWith('/san-pham/')

  if (shouldShowRouteLoader && !isFormDirty.value) {
    scheduleRouteLoader()
  }

  forceScrollTop()

  const user = getUser()
  const token = getToken()
  const requiresAuth = to.matched.some((route) => route.meta.requiresAuth)

  if (requiresAuth && !token) {
    return next({ path: '/dang-nhap', query: { redirect: to.fullPath } })
  }

  if (to.matched.some((route) => route.meta.requiresAdmin)) {
    if (!user || !token) return next({ path: '/dang-nhap', query: { redirect: to.fullPath } })
    const role = getUserRole(user)

    if (to.matched.some((route) => route.meta.requiresSuperAdmin) && role !== 'admin') {
      return next(getDefaultAdminPath(user))
    }

    if (role === 'user') return next('/')

    if (!role) return next('/')

    if (role !== 'admin') {
      const pathPermissionMap = {
        '/admin/quan-ly-san-pham': ['san_pham_xem', 'san_pham_sua', 'nhap_xuat_kho'],
        '/admin/products': ['san_pham_xem', 'san_pham_sua', 'nhap_xuat_kho'],
        '/admin/quan-ly-danh-muc': ['danh_muc_xem', 'danh_muc_sua'],
        '/admin/categories': ['danh_muc_xem', 'danh_muc_sua'],
        '/admin/quan-ly-thuong-hieu': ['thuong_hieu_xem', 'thuong_hieu_sua'],
        '/admin/brands': ['thuong_hieu_xem', 'thuong_hieu_sua'],
        '/admin/bien-the': ['bien_the_xem', 'bien_the_sua'],
        '/admin/variants': ['bien_the_xem', 'bien_the_sua'],
        '/admin/bien-the-san-pham': ['bien_the_xem', 'bien_the_sua'],
        '/admin/quan-ly-don-hang': ['don_hang_xem', 'don_hang_sua', 'hoa_don_xem'],
        '/admin/orders': ['don_hang_xem', 'don_hang_sua', 'hoa_don_xem'],
        '/admin/hoa-don': ['hoa_don_xem', 'don_hang_xem'],
        '/admin/billing': ['hoa_don_xem', 'don_hang_xem'],
        '/admin/quan-ly-khuyen-mai': 'marketing_quan_ly',
        '/admin/promotions': 'marketing_quan_ly',
        '/admin/gui-ma-sinh-nhat': 'marketing_quan_ly',
        '/admin/birthdays': 'marketing_quan_ly',
        '/admin/birthday-codes': 'marketing_quan_ly',
        '/admin/combos': 'marketing_quan_ly',
        '/admin/quan-ly-combo': 'marketing_quan_ly',
        '/admin/flash-sales': 'marketing_quan_ly',
        '/admin/flash-sale': 'marketing_quan_ly',
        '/admin/quan-ly-tiep-thi': 'affiliate_quan_ly',
        '/admin/affiliates': 'affiliate_quan_ly',
        '/admin/quan-ly-tin-tuc': 'tin_tuc_quan_ly',
        '/admin/news': 'tin_tuc_quan_ly',
        '/admin/reviews': 'binh_luan_quan_ly',
        '/admin/quan-ly-binh-luan': 'binh_luan_quan_ly',
        '/admin/quan-ly-banner': 'banner_quan_ly',
        '/admin/banners': 'banner_quan_ly',
        '/admin/quan-ly-lien-he': 'lien_he_quan_ly',
        '/admin/contacts': 'lien_he_quan_ly',
        '/admin/quan-ly-chat': 'chat_quan_ly',
        '/admin/quan-ly-nguoi-dung': 'tai_khoan_quan_ly',
        '/admin/users': 'tai_khoan_quan_ly',
        '/admin/quan-ly-vai-tro': 'vai_tro_quan_ly',
        '/admin/roles': 'vai_tro_quan_ly',
        '/admin/vaitro': 'vai_tro_quan_ly',
        '/admin/nhat-ky-hoat-dong': 'nhat_ky_quan_ly',
        '/admin/activity-log': 'nhat_ky_quan_ly',
        '/admin/xu': 'xu_quan_ly',
        '/admin/vong-quay': 'vong_quay_quan_ly',
        '/admin/diem-danh': 'diem_danh_quan_ly',
        '/admin/quan-ly-cham-cong': 'quan_ly_cham_cong',
        '/admin/quan-ly-don-xin-nghi': 'quan_ly_cham_cong',
      }

      const basicPaths = [
        '/admin',
        '/admin/bang-dieu-khien',
        '/admin/ho-so-quan-tri',
        '/admin/profile',
        '/admin/cai-dat-he-thong',
        '/admin/settings',
        '/admin/cham-cong-camera',
        '/admin/xin-nghi-phep'
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
          const permList = Array.isArray(requiredPerm) ? requiredPerm : [requiredPerm]
          const hasAccess = permList.some(p => userPerms.includes(p))
          if (!hasAccess) {
            cancelScheduledRouteLoader()
            import('@/services/swal')
              .then(({ default: swal }) => swal.error('Từ chối truy cập', 'Chức vụ của bạn không có quyền vào chức năng này!'))
              .catch(() => {})
            return next(false)
          }
        }
      }
    }
  }

  next()
})

export default router
