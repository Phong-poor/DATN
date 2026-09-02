// Dùng đường dẫn tương đối để đi qua Vite proxy (loại bỏ CORS / preflight)
// Production: set VITE_API_BASE_URL=https://yourdomain.com/api trong .env.production
// Cùng domain là cấu hình deploy mặc định an toàn và gọn nhất.
// Khi tách API sang domain khác, chỉ cần khai báo VITE_API_BASE_URL.
const DEFAULT_API_BASE = '/api'



const rawApiBaseUrl = import.meta.env.VITE_API_BASE_URL || DEFAULT_API_BASE
const rawBackendBaseUrl = import.meta.env.VITE_APP_URL
  || (import.meta.env.DEV ? '' : rawApiBaseUrl.replace(/\/api$/, ''))



export const apiBaseUrl = String(rawApiBaseUrl).replace(/\/+$/, '')

export const backendBaseUrl = String(rawBackendBaseUrl).replace(/\/+$/, '')



const unwrapImageValue = (value) => {
  if (Array.isArray(value)) {
    return unwrapImageValue(value.find((item) => String(unwrapImageValue(item) || '').trim()))
  }

  if (value && typeof value === 'object') {
    return unwrapImageValue(
      value.duongdan
      || value.duong_dan
      || value.hinhanh
      || value.hinh_anh
      || value.image_url
      || value.thumbnail
      || value.url
      || value.path
      || value.image
    )
  }

  const raw = String(value || '').trim()
  if (!raw) return ''

  if (/^[\[{]/.test(raw)) {
    try {
      return unwrapImageValue(JSON.parse(raw))
    } catch {
      return raw
    }
  }

  return raw
}

export const storageUrl = (path) => {
  if (!path) return ''

  let raw = unwrapImageValue(path)
  if (!raw) return ''

  // Globally replace seeder orange phone image with realistic laptop image
  if (raw.includes('photo-1611186871348-b1ce696e52c9')) {
    raw = raw.replace('photo-1611186871348-b1ce696e52c9', 'photo-1588872657578-7efd1f1555ed')
  }

  // 1. Direct Web URLs (http://, https://, data:, blob:)
  if (/^(https?:)?\/\//i.test(raw) || raw.startsWith('data:') || raw.startsWith('blob:')) {
    return raw
  }

  // Product catalogs can also reference files kept directly in backend/public.
  // These paths must not be rewritten to /storage, otherwise Laravel returns 404.
  const slashPath = raw.replace(/\\/g, '/')
  const lowerPath = slashPath.toLocaleLowerCase('vi')
  const backendStorageMarker = '/backend/storage/app/public/'
  const backendPublicMarker = '/backend/public/'
  const frontendPublicMarker = '/frontend/public/'
  const publicStorageMarker = '/public/storage/'

  // Dữ liệu cũ đôi khi chứa đường dẫn tuyệt đối của máy Windows. Trình duyệt
  // không đọc được C:\\..., vì vậy ánh xạ nó về URL public của ứng dụng.
  if (lowerPath.includes(backendStorageMarker)) {
    const relative = slashPath.slice(lowerPath.indexOf(backendStorageMarker) + backendStorageMarker.length)
    return backendBaseUrl ? `${backendBaseUrl}/storage/${relative}` : `/storage/${relative}`
  }
  if (lowerPath.includes(publicStorageMarker)) {
    const relative = slashPath.slice(lowerPath.indexOf(publicStorageMarker) + publicStorageMarker.length)
    return backendBaseUrl ? `${backendBaseUrl}/storage/${relative}` : `/storage/${relative}`
  }
  if (lowerPath.includes(backendPublicMarker)) {
    const relative = slashPath.slice(lowerPath.indexOf(backendPublicMarker) + backendPublicMarker.length)
    return backendBaseUrl ? `${backendBaseUrl}/${relative}` : `/${relative}`
  }
  if (lowerPath.includes(frontendPublicMarker)) {
    const relative = slashPath.slice(lowerPath.indexOf(frontendPublicMarker) + frontendPublicMarker.length)
    return `/${relative}`
  }

  const directPublicPath = slashPath.replace(/^\/+/, '')
  if (directPublicPath.startsWith('ảnh laptop/')) {
    return backendBaseUrl ? `${backendBaseUrl}/${directPublicPath}` : `/${directPublicPath}`
  }

  // Các catalog mới được người quản trị đặt trực tiếp trong frontend/public.
  if (/^(dell|tainghe|lotchuot)\//i.test(directPublicPath)) {
    return `/${directPublicPath}`
  }

  // 2. Local uploaded files (stored in storage/app/public/... and accessed via /storage/...)
  const normalizedPath = raw
    .replace(/\\/g, '/')
    .replace(/^\/+/, '')
    .replace(/^public\//i, '')
    .replace(/^storage\//i, '')

  return backendBaseUrl ? `${backendBaseUrl}/storage/${normalizedPath}` : `/storage/${normalizedPath}`
}

export const imageFallbackUrl = 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500'

export const normalizeImageUrl = (value, fallback = imageFallbackUrl) => {
  if (!value) return fallback

  const raw = unwrapImageValue(value)
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
    ? gallery.find((img) => unwrapImageValue(img))
    : null
  const firstGalleryImage = unwrapImageValue(firstGallery)

  const url = firstImageUrl([
    variant?.hinhanh,
    variant?.hinh_anh,
    variant?.image_url,
    variant?.thumbnail,
    variant?.image,
    product.hinhanh,
    product.hinh_anh,
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
    product?.hinhanh || product?.hinh_anh || product?.image_url || product?.image || product?.thumbnail
  )

  const url = firstImageUrl([
    combo.hinhanh,
    combo.hinh_anh,
    combo.image_url,
    combo.image,
    combo.thumbnail,
    productWithImage?.hinhanh,
    productWithImage?.hinh_anh,
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


