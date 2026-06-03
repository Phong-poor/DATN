import api from '@/services/api'

const TTL_MS = 2 * 60 * 1000
const STORAGE_KEY = 'nextgen_products_prefetch_cache'

let cache = null
let inFlight = null

const normalizeList = (payload) => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  return []
}

export const prefetchProductsPage = async () => {
  if (cache && Date.now() - cache.fetchedAt < TTL_MS) return cache
  if (inFlight) return inFlight

  const stored = getPrefetchedProductsData()
  if (stored) return stored

  inFlight = Promise.all([
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
      return cache
    })
    .finally(() => {
      inFlight = null
    })

  return inFlight
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
  if (Date.now() - cache.fetchedAt > TTL_MS) return null
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

  return cache
}

export const findPrefetchedProductById = (productId) => {
  const warm = getPrefetchedProductsData()
  if (!warm) return null
  return (warm.productsRaw || []).find((item) => String(item.id_sanpham) === String(productId)) || null
}
