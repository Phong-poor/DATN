import { getToken, getUser } from '@/services/auth'
import { prefetchProductsPage } from '@/services/productsPrefetch'

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
    import('@/components/Layout/MainLayout.vue'),
    import('@/components/Web/Home.vue'),
  ]),
  '/products': () => Promise.all([
    import('@/components/Layout/MainLayout.vue'),
    import('@/components/Web/ProductsPremiumPage.vue'),
  ]),
  '/gaming': () => Promise.all([
    import('@/components/Layout/MainLayout.vue'),
    import('@/components/Web/GamingPage.vue'),
  ]),
  '/macbook': () => Promise.all([
    import('@/components/Layout/MainLayout.vue'),
    import('@/components/Web/ProductsPremiumPage.vue'),
  ]),
  '/products/:id': () => import('@/components/Web/ProductDetail.vue'),
  '/cart': () => import('@/components/Web/Cart.vue'),
  '/checkout': () => import('@/components/Web/Checkout.vue'),
  '/news': () => import('@/components/Web/News.vue'),
  '/news/:id': () => import('@/components/Web/NewsDetail.vue'),
  '/contact': () => import('@/components/Web/Contact.vue'),
  '/khuyen-mai': () => import('@/components/Web/Promotions.vue'),
  '/workstation': () => import('@/components/Web/Workstation.vue'),
  '/interactive-labs': () => import('@/components/Web/InteractiveLabs.vue'),
  '/login': () => import('@/components/Auth/Login.vue'),
  '/register': () => import('@/components/Auth/Register.vue'),
  '/admin': () => Promise.all([
    import('@/components/Admin/Layout/AdminLayout.vue'),
    import('@/components/Admin/Dashboard.vue'),
  ]),
  '/admin/products': () => import('@/components/Admin/Products.vue'),
  '/admin/orders': () => import('@/components/Admin/Orders.vue'),
  '/admin/users': () => import('@/components/Admin/Users.vue'),
  '/admin/news': () => import('@/components/Admin/News.vue'),
  '/admin/variants': () => import('@/components/Admin/ProductVariants.vue'),
  '/admin/categories': () => import('@/components/Admin/Categories.vue'),
  '/admin/promotions': () => import('@/components/Admin/Promotions.vue'),
  '/admin/banners': () => import('@/components/Admin/Banners.vue'),
  '/admin/brands': () => import('@/components/Admin/Brands.vue'),
  '/admin/contacts': () => import('@/components/Admin/Contact.vue'),
  '/admin/reviews': () => import('@/components/Admin/ReviewManagement.vue'),
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
    if (url.pathname.startsWith('/news/')) return '/news/:id'
    return url.pathname
  } catch {
    return null
  }
}

const preloadLinkTarget = (event) => {
  const link = event.target?.closest?.('a[href]')
  if (!link) return
  const path = normalizePath(link.getAttribute('href'))
  if (!path) return
  preloadOnce(path)
  prefetchRouteData(path)
}

const prefetchRouteData = (path) => {
  if (['/', '/products', '/gaming', '/macbook', '/workstation', '/products/:id'].includes(path)) {
    prefetchProductsPage({ forceRefresh: false }).catch(() => {})
  }
}

const installLinkPrefetch = () => {
  document.addEventListener('pointerover', preloadLinkTarget, { passive: true })
  document.addEventListener('touchstart', preloadLinkTarget, { passive: true })
}

const warmCoreRoutes = () => {
  if (hasSlowConnection()) return

  const user = getUser()
  const isAdmin = Boolean(getToken() && user?.vaitro && user.vaitro !== 'user')
  const webQueue = ['/', '/products', '/gaming', '/macbook', '/news']
  const adminQueue = isAdmin
    ? ['/admin', '/admin/products', '/admin/orders', '/admin/users', '/admin/variants', '/admin/banners', '/admin/brands']
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
