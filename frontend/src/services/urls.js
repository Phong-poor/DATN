// Dùng đường dẫn tương đối để đi qua Vite proxy (loại bỏ CORS / preflight)
// Production: set VITE_API_BASE_URL=https://yourdomain.com/api trong .env.production
const DEFAULT_API_BASE = import.meta.env.DEV ? '/api' : 'http://127.0.0.1:8000/api'



const rawApiBaseUrl = import.meta.env.VITE_API_BASE_URL || DEFAULT_API_BASE
const rawBackendBaseUrl = import.meta.env.VITE_APP_URL
  || (import.meta.env.DEV ? '' : rawApiBaseUrl.replace(/\/api$/, ''))



export const apiBaseUrl = String(rawApiBaseUrl).replace(/\/+$/, '')

export const backendBaseUrl = String(rawBackendBaseUrl).replace(/\/+$/, '')



export const storageUrl = (path) => {

  if (!path) return ''

  let raw = String(path).trim()
  if (!raw) return ''

  // Globally replace seeder orange phone image with realistic laptop image
  if (raw.includes('photo-1611186871348-b1ce696e52c9')) {
    raw = raw.replace('photo-1611186871348-b1ce696e52c9', 'photo-1588872657578-7efd1f1555ed')
  }

  if (/^(https?:)?\/\//i.test(raw) || raw.startsWith('data:') || raw.startsWith('blob:')) {

    return raw

  }

  if (raw.startsWith('/') && !raw.startsWith('/storage/')) {
    return raw
  }



  const normalizedPath = raw

    .replace(/\\/g, '/')

    .replace(/^\/+/, '')

    .replace(/^public\//i, '')

    .replace(/^storage\//i, '')



  return `${backendBaseUrl}/storage/${normalizedPath}`

}

export const imageFallbackUrl = 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500'

export const normalizeImageUrl = (value, fallback = imageFallbackUrl) => {
  if (!value) return fallback

  const raw = String(value).trim()
  if (!raw) return fallback

  return storageUrl(raw)
}

export const firstImageUrl = (values = [], fallback = imageFallbackUrl) => {
  const found = values.find((value) => String(value || '').trim())
  return normalizeImageUrl(found, fallback)
}

export const withImageVersion = (url, version) => {
  if (!url || !version || url.startsWith('data:') || url.startsWith('blob:')) return url
  const [withoutHash, hash = ''] = String(url).split('#')
  const [base, query = ''] = withoutHash.split('?')
  const params = new URLSearchParams(query)
  params.set('v', version)
  return `${base}?${params.toString()}${hash ? `#${hash}` : ''}`
}

export const productImageUrl = (product = {}, variant = null, fallback = imageFallbackUrl) => {
  const gallery = product.hinh_anhs || product.hinhAnhs || []
  const firstGallery = Array.isArray(gallery)
    ? gallery.find((img) => img?.duongdan || img?.duong_dan || img?.url || img?.path || img?.image)
    : null
  const firstGalleryImage = firstGallery?.duongdan
    || firstGallery?.duong_dan
    || firstGallery?.url
    || firstGallery?.path
    || firstGallery?.image
    || ''

  const url = firstImageUrl([
    variant?.hinhanh,
    variant?.image_url,
    variant?.image,
    product.hinhanh,
    product.image_url,
    product.image,
    product.thumbnail,
    firstGalleryImage,
  ], fallback)

  return withImageVersion(url, product.updated_at || product.updatedAt || variant?.updated_at || variant?.updatedAt)
}

export const comboImageUrl = (combo = {}, fallback = imageFallbackUrl) => {
  const products = Array.isArray(combo.products) ? combo.products : []
  const productWithImage = products.find((product) =>
    product?.hinhanh || product?.image_url || product?.image || product?.thumbnail
  )

  const url = firstImageUrl([
    combo.hinhanh,
    combo.image_url,
    combo.image,
    combo.thumbnail,
    productWithImage?.hinhanh,
    productWithImage?.image_url,
    productWithImage?.image,
    productWithImage?.thumbnail,
  ], fallback)

  return withImageVersion(url, combo.updated_at || combo.updatedAt || productWithImage?.updated_at || productWithImage?.updatedAt)
}

export const handleImageFallback = (event, fallback = imageFallbackUrl) => {
  const target = event?.target
  if (!target || target.dataset.fallbackApplied === '1') return
  target.dataset.fallbackApplied = '1'
  target.src = fallback
}


