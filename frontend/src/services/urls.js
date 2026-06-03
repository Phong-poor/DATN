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



  const normalizedPath = raw

    .replace(/\\/g, '/')

    .replace(/^\/+/, '')

    .replace(/^public\//i, '')

    .replace(/^storage\//i, '')



  return `${backendBaseUrl}/storage/${normalizedPath}`

}


