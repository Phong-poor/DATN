import { getToken, getUser } from '@/services/auth'
import { prefetchProductDetail, prefetchProductsPage } from '@/services/productsPrefetch'

const idle = (task, timeout = 1600) => {
  if (typeof window === 'undefined') return
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(task, { timeout })
  } else {
    window.setTimeout(task, Math.min(timeout, 300))
  }
}

const hasSlowConnection = () => {
  const connection = navigator.connection || navigator.webkitConnection || navigator.mozConnection
  return Boolean(connection?.saveData || ['slow-2g', '2g'].includes(connection?.effectiveType))
}

const routePreloads = {
  '/': () => Promise.all([
    import('@/components/Layout/BoCucChinh.vue'),
    import('@/components/Web/TrangChu.vue'),
  ]),
  '/san-pham': () => routePreloads['/laptop'](),
  '/products': () => routePreloads['/san-pham'](),
  '/laptop': () => import('@/components/Web/TrangLaptop.vue'),

  '/san-pham/:id': () => import('@/components/Web/ChiTietSanPham.vue'),
  '/products/:id': () => routePreloads['/san-pham/:id'](),
  '/gio-hang': () => import('@/components/Web/GioHang.vue'),
  '/cart': () => routePreloads['/gio-hang'](),
  '/thanh-toan': () => import('@/components/Web/ThanhToan.vue'),
  '/checkout': () => routePreloads['/thanh-toan'](),
  '/tin-tuc': () => import('@/components/Web/TinTucKhachHang.vue'),
  '/news': () => routePreloads['/tin-tuc'](),
  '/tin-tuc/:id': () => import('@/components/Web/ChiTietTinTuc.vue'),
  '/news/:id': () => routePreloads['/tin-tuc/:id'](),
  '/lien-he': () => import('@/components/Web/LienHeKhachHang.vue'),
  '/contact': () => routePreloads['/lien-he'](),
  '/khuyen-mai': () => import('@/components/Web/KhuyenMaiKhachHang.vue'),
  '/tiep-thi-lien-ket': () => import('@/components/Web/TrungTamTiepThi.vue'),
  '/affiliate': () => routePreloads['/tiep-thi-lien-ket'](),
  '/dang-nhap': () => import('@/components/Auth/DangNhap.vue'),
  '/login': () => routePreloads['/dang-nhap'](),
  '/dang-ky': () => import('@/components/Auth/DangKy.vue'),
  '/register': () => routePreloads['/dang-ky'](),
  '/admin': () => Promise.all([
    import('@/components/Admin/Layout/AdminLayout.vue'),
    import('@/components/Admin/BangDieuKhien.vue'),
  ]),
  '/admin/quan-ly-san-pham': () => import('@/components/Admin/QuanLySanPham.vue'),
  '/admin/products': () => routePreloads['/admin/quan-ly-san-pham'](),
  '/admin/quan-ly-don-hang': () => import('@/components/Admin/QuanLyDonHang.vue'),
  '/admin/orders': () => routePreloads['/admin/quan-ly-don-hang'](),
  '/admin/quan-ly-nguoi-dung': () => import('@/components/Admin/QuanLyNguoiDung.vue'),
  '/admin/users': () => routePreloads['/admin/quan-ly-nguoi-dung'](),
  '/admin/quan-ly-tin-tuc': () => import('@/components/Admin/QuanLyTinTuc.vue'),
  '/admin/news': () => routePreloads['/admin/quan-ly-tin-tuc'](),
  '/admin/bien-the': () => import('@/components/Admin/BienTheSanPham.vue'),
  '/admin/variants': () => routePreloads['/admin/bien-the'](),
  '/admin/quan-ly-danh-muc': () => import('@/components/Admin/QuanLyDanhMuc.vue'),
  '/admin/categories': () => routePreloads['/admin/quan-ly-danh-muc'](),
  '/admin/quan-ly-khuyen-mai': () => import('@/components/Admin/QuanLyKhuyenMai.vue'),
  '/admin/promotions': () => routePreloads['/admin/quan-ly-khuyen-mai'](),
  '/admin/quan-ly-banner': () => import('@/components/Admin/QuanLyBanner.vue'),
  '/admin/banners': () => routePreloads['/admin/quan-ly-banner'](),
  '/admin/quan-ly-thuong-hieu': () => import('@/components/Admin/QuanLyThuongHieu.vue'),
  '/admin/brands': () => routePreloads['/admin/quan-ly-thuong-hieu'](),
  '/admin/quan-ly-lien-he': () => import('@/components/Admin/QuanLyLienHe.vue'),
  '/admin/contacts': () => routePreloads['/admin/quan-ly-lien-he'](),
  '/admin/reviews': () => import('@/components/Admin/QuanLyBinhLuan.vue'),
  '/admin/flash-sales': () => import('@/components/Admin/FlashSaleManagement.vue'),
  '/admin/flash-sale': () => routePreloads['/admin/flash-sales'](),
}

const preloadOnce = (() => {
  const loaded = new Set()
  return (path) => {
    const loader = routePreloads[path]
    if (!loader || loaded.has(path)) return
    loaded.add(path)
    loader().catch(() => loaded.delete(path))
  }
})()

const normalizePath = (href) => {
  try {
    const url = new URL(href, window.location.origin)
    if (url.origin !== window.location.origin) return null
    if (url.pathname.startsWith('/products/')) return '/products/:id'
    if (url.pathname.startsWith('/san-pham/')) return '/san-pham/:id'
    if (url.pathname.startsWith('/news/')) return '/news/:id'
    if (url.pathname.startsWith('/tin-tuc/')) return '/tin-tuc/:id'
    return url.pathname
  } catch {
    return null
  }
}

const getProductIdFromHref = (href) => {
  try {
    const url = new URL(href, window.location.origin)
    const match = url.pathname.match(/^\/(?:products|san-pham)\/([^/?#]+)/)
    return match?.[1] || null
  } catch {
    return null
  }
}

const prefetchRouteData = (path, href) => {
  if (['/', '/laptop'].includes(path)) {
    prefetchProductsPage({ forceRefresh: false }).catch(() => {})
    return
  }

  if (path === '/san-pham/:id' || path === '/products/:id') {
    const id = getProductIdFromHref(href)
    if (id) prefetchProductDetail(id).catch(() => {})
  }
}

const preloadLinkTarget = (event) => {
  const link = event.target?.closest?.('a[href]')
  if (!link) return
  const href = link.getAttribute('href')
  const path = normalizePath(href)
  if (!path) return
  preloadOnce(path)
  prefetchRouteData(path, href)
}

const installLinkPrefetch = () => {
  document.addEventListener('pointerover', preloadLinkTarget, { passive: true })
  document.addEventListener('touchstart', preloadLinkTarget, { passive: true })
}

const warmCoreRoutes = () => {
  if (hasSlowConnection()) return

  const user = getUser()
  const role = String(user?.vaitro || user?.role || '').toLowerCase()
  const isStaff = Boolean(getToken() && role && role !== 'user')
  const webQueue = ['/', '/laptop', '/tin-tuc']
  const adminQueue = isStaff
    ? ['/admin', '/admin/quan-ly-san-pham', '/admin/quan-ly-don-hang', '/admin/quan-ly-nguoi-dung', '/admin/bien-the', '/admin/quan-ly-banner']
    : []

  const queue = [...webQueue, ...adminQueue]
  let index = 0
  const step = () => {
    preloadOnce(queue[index])
    index += 1
    if (index < queue.length) idle(step, 1200)
  }
  step()
}

export const installPerformanceWarmup = () => {
  if (typeof window === 'undefined') return

  installLinkPrefetch()

  idle(() => {
    if (!hasSlowConnection()) {
      prefetchProductsPage({ forceRefresh: false }).catch(() => {})
    }
  }, 1800)

  idle(() => {
    warmCoreRoutes()
  }, 3200)
}
