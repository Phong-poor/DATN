import { ref } from 'vue'
import api from './api'
import { getToken } from './auth'

export const wishlistItems = ref([])
export const wishlistedProductIds = ref(new Set())
export const wishlistedVariantIds = ref(new Set())

export const fetchWishlistState = async () => {
  const token = getToken()
  if (!token) {
    wishlistItems.value = []
    wishlistedProductIds.value = new Set()
    wishlistedVariantIds.value = new Set()
    return
  }

  try {
    const res = await api.get('/yeu-thich', { skipGlobalLoader: true })
    const items = Array.isArray(res.data?.data) ? res.data.data : (Array.isArray(res.data) ? res.data : [])
    wishlistItems.value = items

    const pSet = new Set()
    const vSet = new Set()

    items.forEach(i => {
      const pId = i.id_sanpham || i.bienthe?.id_sanpham || i.bienthe?.sanpham?.id_sanpham
      const vId = i.id_bienthe || i.bienthe?.id_bienthe
      if (pId != null) pSet.add(Number(pId))
      if (vId != null) vSet.add(Number(vId))
    })

    wishlistedProductIds.value = pSet
    wishlistedVariantIds.value = vSet
  } catch (e) {
    wishlistItems.value = []
    wishlistedProductIds.value = new Set()
    wishlistedVariantIds.value = new Set()
  }
}

export const isWishlisted = (productOrId) => {
  if (!productOrId) return false
  if (typeof productOrId === 'number' || typeof productOrId === 'string') {
    const num = Number(productOrId)
    return wishlistedProductIds.value.has(num) || wishlistedVariantIds.value.has(num)
  }
  const pId = Number(productOrId.id || productOrId.id_sanpham || productOrId.id_bienthe || productOrId.key_id)
  const vId = Number(productOrId.id_bienthe || productOrId.key_id)
  return (pId && wishlistedProductIds.value.has(pId)) || (vId && wishlistedVariantIds.value.has(vId)) || (pId && wishlistedVariantIds.value.has(pId))
}

export const findWishlistItem = (product) => {
  if (!product) return null
  const pId = Number(product.id || product.id_sanpham)
  const vId = Number(product.id_bienthe || product.key_id)

  return wishlistItems.value.find(i => {
    const itemPId = Number(i.id_sanpham || i.bienthe?.id_sanpham || i.bienthe?.sanpham?.id_sanpham)
    const itemVId = Number(i.id_bienthe || i.bienthe?.id_bienthe)
    if (vId && itemVId === vId) return true
    if (pId && itemPId === pId) return true
    return false
  })
}

if (typeof window !== 'undefined') {
  window.addEventListener('wishlist-updated', fetchWishlistState)
  window.addEventListener('user-updated', fetchWishlistState)
  fetchWishlistState()
}
