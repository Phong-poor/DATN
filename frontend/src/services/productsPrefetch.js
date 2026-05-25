import api from '@/services/api'

const TTL_MS = 2 * 60 * 1000

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
      return cache
    })
    .finally(() => {
      inFlight = null
    })

  return inFlight
}

export const getPrefetchedProductsData = () => {
  if (!cache) return null
  if (Date.now() - cache.fetchedAt > TTL_MS) return null
  return cache
}
