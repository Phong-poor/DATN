import api from '@/services/api'
import { productImageUrl } from '@/services/urls'

const TTL_MS = 30 * 60 * 1000
const STALE_TTL_MS = 24 * 60 * 60 * 1000
const STORAGE_KEY = 'predator_products_prefetch_cache'

let cache = null
let inFlight = null
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
  return Promise.all([
    api.get('/sanpham', { skipGlobalLoader: true }),
    api.get('/danhmuc', { skipGlobalLoader: true }),
    api.get('/thuonghieu', { skipGlobalLoader: true }),
    api.get('/sanpham/attribute-options', { skipGlobalLoader: true }),
  ])
    .then(([spRes, catRes, brandRes, attrRes]) => {
      cache = {
        fetchedAt: Date.now(),
        productsRaw: normalizeList(spRes.data),
        categories: catRes.data?.data || catRes.data || [],
        brands: brandRes.data?.data || brandRes.data || [],
        attrOptions: attrRes.data || {},
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

export const warmProductImages = (products = [], limit = 28) => {
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

export const findPrefetchedProductById = (productId) => {
  const warm = getPrefetchedProductsData()
  if (!warm) return null
  return (warm.productsRaw || []).find((item) => String(item.id_sanpham) === String(productId)) || null
}
