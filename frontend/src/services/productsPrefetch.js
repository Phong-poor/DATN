import api from '@/services/api'
import { clearApiGetCache } from '@/services/api'
import { productImageUrl } from '@/services/urls'

const TTL_MS = 30 * 60 * 1000
const STALE_TTL_MS = 24 * 60 * 60 * 1000
const STORAGE_KEY = 'nextgen_products_prefetch_cache'
const DETAIL_CACHE_VERSION = 'detail-complete-v5'

let cache = null
let inFlight = null
let productDetailInFlight = new Map()
const warmedImages = new Set()

const normalizeList = (payload) => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  return []
}

export const prefetchProductsPage = async ({ forceRefresh = false } = {}) => {
  if (cache && Date.now() - cache.fetchedAt < TTL_MS) {
    warmProductImages(cache.productsRaw)
    return cache
  }

  const stored = getPrefetchedProductsData()
  if (stored && !forceRefresh) {
    warmProductImages(stored.productsRaw)
    if (Date.now() - stored.fetchedAt < TTL_MS) return stored
    if (!inFlight) {
      inFlight = fetchProductsBundle().finally(() => {
        inFlight = null
      })
    }
    return stored
  }

  if (inFlight) return inFlight

  inFlight = fetchProductsBundle().finally(() => {
    inFlight = null
  })

  return inFlight
}

const fetchProductsBundle = () => {
  return api.get('/sanpham/init', { skipGlobalLoader: true })
    .then((initRes) => {
      const payload = initRes.data || {}
      cache = {
        fetchedAt: Date.now(),
        productsRaw: normalizeList(payload.products),
        categories: payload.categories || [],
        brands: payload.brands || [],
        attrOptions: payload.attributes || {},
      }
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cache))
      } catch {
        // Ignore storage quota/private mode errors; memory cache still works.
      }
      warmProductImages(cache.productsRaw)
      return cache
    })
}

const idle = (task) => {
  if (typeof window === 'undefined') return
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(task, { timeout: 1400 })
  } else {
    window.setTimeout(task, 160)
  }
}

const preloadImage = (src) => {
  if (!src || warmedImages.has(src) || typeof Image === 'undefined') return
  warmedImages.add(src)
  const image = new Image()
  image.decoding = 'async'
  image.loading = 'eager'
  image.src = src
}

export const warmProductImages = (products = [], limit = 8) => {
  if (!Array.isArray(products) || !products.length) return
  idle(() => {
    products.slice(0, limit).forEach((product) => {
      const variants = product.bien_thes || product.bienThes || product.bienthes || []
      const firstVariant = Array.isArray(variants) ? variants[0] : null
      preloadImage(productImageUrl(product, firstVariant))
    })
  })
}

export const getPrefetchedProductsData = () => {
  if (!cache) {
    try {
      const stored = localStorage.getItem(STORAGE_KEY)
      if (stored) cache = JSON.parse(stored)
    } catch {
      cache = null
    }
  }

  if (!cache) return null
  if (Date.now() - cache.fetchedAt > STALE_TTL_MS) return null
  return cache
}

export const primeProductsCache = (data) => {
  cache = {
    fetchedAt: Date.now(),
    productsRaw: normalizeList(data.productsRaw),
    categories: data.categories || [],
    brands: data.brands || [],
    attrOptions: data.attrOptions || {},
  }

  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(cache))
  } catch {
    // Ignore storage quota/private mode errors; memory cache still works.
  }

  warmProductImages(cache.productsRaw)
  return cache
}

const getProductDetailCacheKey = (productId) => `nextgen_product_detail_cache_${productId}`

export const invalidateProductsPrefetchCache = (productId = null) => {
  cache = null
  inFlight = null
  productDetailInFlight.clear()
  warmedImages.clear()
  clearApiGetCache()

  try {
    localStorage.removeItem(STORAGE_KEY)
    localStorage.removeItem('nextgen_admin_products_cache')
    localStorage.removeItem('nextgen_products_cache')
    localStorage.removeItem('premium_home_cache')
    localStorage.removeItem('nextgen_labs_real_products')

    if (productId) {
      localStorage.removeItem(getProductDetailCacheKey(productId))
    } else {
      Object.keys(localStorage)
        .filter((key) => key.startsWith('nextgen_product_detail_cache_'))
        .forEach((key) => localStorage.removeItem(key))
    }
  } catch {
    // Cache invalidation should never block product updates.
  }
}

export const preloadProductDetailPage = () => import('@/components/Web/ChiTietSanPham.vue')

const specLabelForValue = (value = '') => {
  const text = String(value).toLowerCase()
  if (text.includes('rtx') || text.includes('gtx') || text.includes('gpu')) return 'GPU'
  if (text.includes('core') || text.includes('ryzen') || text.includes('intel') || text.includes('amd')) return 'CPU'
  if (text.includes('ram') || text.includes('gb ram')) return 'RAM'
  if (text.includes('ssd') || text.includes('tb') || text.includes('storage')) return 'SSD'
  if (text.includes('hz')) return 'Tần số quét'
  if (text.includes('oled') || text.includes('ips') || text.includes('inch')) return 'Màn hình'
  return 'Thông số'
}

const specsArrayToTechnicalSpecs = (specs = []) => {
  if (!Array.isArray(specs)) return []
  return specs.filter(Boolean).map((value, index) => ({
    id_thuoctinh: `card-spec-${index}`,
    ten_thuoctinh: specLabelForValue(value),
    giatri: String(value),
  }))
}

export const prefetchProductDetail = async (productId, { forceRefresh = false } = {}) => {
  if (!productId) return null

  const id = String(productId)
  const cacheKey = getProductDetailCacheKey(id)

  if (!forceRefresh) {
    try {
      const cached = localStorage.getItem(cacheKey)
      if (cached) {
        const parsed = JSON.parse(cached)
        if (parsed?.product?.tenSP) return parsed.product
      }
    } catch {
      // Ignore malformed cache and fetch a fresh detail payload.
    }
  }

  if (productDetailInFlight.has(id)) return productDetailInFlight.get(id)

  const request = api.get(`/sanpham/${id}`, { skipGlobalLoader: true })
    .then((response) => {
      const data = response.data || {}
      const variants = data.bien_thes || data.bienThes || []
      const product = { ...data, bienThes: variants }

      try {
        localStorage.setItem(cacheKey, JSON.stringify({
          cacheVersion: DETAIL_CACHE_VERSION,
          product,
          reviews: [],
          recentlyViewedProducts: [],
          relatedProducts: [],
          combos: [],
        }))
      } catch {
        // Detail prefetch is a speed boost only; storage failure should not block UI.
      }

      warmProductImages([product], 1)
      return product
    })
    .finally(() => {
      productDetailInFlight.delete(id)
    })

  productDetailInFlight.set(id, request)
  return request
}

export const primeProductDetailFromCard = (product) => {
  const id = product?.id_sanpham || product?.id
  if (!id || !product?.tenSP) return null

  const sourceVariants = product.bien_thes || product.bienThes || []
  const technicalSpecs = Array.isArray(product.thong_so_ky_thuat) && product.thong_so_ky_thuat.length > 0
    ? product.thong_so_ky_thuat
    : specsArrayToTechnicalSpecs(product.specs)
  const variants = product.id_bienthe
    ? [{
        id_bienthe: product.id_bienthe,
        ten_bienthe: product.variantName || product.tenSP,
        gia: product.gia,
        soluong: product.inStock === false ? 0 : 1,
        hinhanh: product.image || product.hinhanh || '',
        thuoc_tinh: technicalSpecs,
      }]
    : sourceVariants

  const instantProduct = {
    ...product,
    id_sanpham: id,
    hinhanh: product.hinhanh || product.image || '',
    gia: product.gia || variants[0]?.gia || 0,
    id_danhmuc: product.id_danhmuc,
    id_thuonghieu: product.id_thuonghieu,
    danh_muc: product.danh_muc || (product.category ? { ten_danhmuc: product.category } : null),
    thuong_hieu: product.thuong_hieu || (product.brand ? { ten_thuonghieu: product.brand } : null),
    hinh_anhs: product.hinh_anhs || product.hinhAnhs || [],
    bienThes: variants,
    hinhAnhs: product.hinhAnhs || product.hinh_anhs || [],
    thong_so_ky_thuat: technicalSpecs,
    isInstantCardCache: true,
  }

  try {
    localStorage.setItem(getProductDetailCacheKey(id), JSON.stringify({
      cacheVersion: DETAIL_CACHE_VERSION,
      product: instantProduct,
      reviews: [],
      recentlyViewedProducts: [],
      relatedProducts: [],
      combos: [],
    }))
  } catch {
    // Instant card cache is only a temporary render hint.
  }

  warmProductImages([instantProduct], 1)
  return instantProduct
}

export const findPrefetchedProductById = (productId) => {
  const warm = getPrefetchedProductsData()
  if (!warm) return null
  return (warm.productsRaw || []).find((item) => String(item.id_sanpham) === String(productId)) || null
}
