const SITE_NAME = 'NextGen Laptop'
const DEFAULT_DESCRIPTION =
  'Tin tức công nghệ, tư vấn chọn laptop, đánh giá laptop gaming, laptop văn phòng và laptop đồ họa từ VinaTech.'

const getOrigin = () => {
  if (typeof window === 'undefined') return ''
  return window.location.origin
}

export const absoluteUrl = (value = '') => {
  if (!value) return ''
  if (/^https?:\/\//i.test(value)) return value
  const path = value.startsWith('/') ? value : `/${value}`
  return `${getOrigin()}${path}`
}

const setMeta = (selector, attrs) => {
  let element = document.head.querySelector(selector)
  if (!element) {
    element = document.createElement('meta')
    document.head.appendChild(element)
  }

  Object.entries(attrs).forEach(([key, value]) => {
    if (value !== undefined && value !== null) element.setAttribute(key, value)
  })
}

const setLink = (rel, href) => {
  let element = document.head.querySelector(`link[rel="${rel}"]`)
  if (!element) {
    element = document.createElement('link')
    element.setAttribute('rel', rel)
    document.head.appendChild(element)
  }
  element.setAttribute('href', href)
}

const setJsonLd = (schema) => {
  document.head.querySelectorAll('script[data-seo-jsonld="true"]').forEach((item) => item.remove())
  if (!schema) return

  const script = document.createElement('script')
  script.type = 'application/ld+json'
  script.dataset.seoJsonld = 'true'
  script.textContent = JSON.stringify(schema)
  document.head.appendChild(script)
}

const removeMeta = (selector) => {
  document.head.querySelectorAll(selector).forEach((item) => item.remove())
}

export const stripHtml = (value = '') => value.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim()

export const truncateText = (value = '', max = 260) => {
  const text = stripHtml(value)
  if (text.length <= max) return text
  return `${text.slice(0, max - 1).trim()}…`
}

export const setSeo = ({
  title = SITE_NAME,
  description = DEFAULT_DESCRIPTION,
  keywords = 'VinaTech, laptop, laptop gaming, laptop văn phòng, tin tức công nghệ',
  image = '/favicon.svg',
  url,
  type = 'website',
  robots = 'index, follow',
  publishedTime,
  modifiedTime,
  author,
  schema,
} = {}) => {
  const fullTitle = title.includes(SITE_NAME) ? title : `${title} | ${SITE_NAME}`
  const finalDescription = truncateText(description || DEFAULT_DESCRIPTION, 260)
  const canonicalUrl = absoluteUrl(url || window.location.pathname)
  const imageUrl = absoluteUrl(image)

  document.documentElement.setAttribute('lang', 'vi')
  document.title = fullTitle

  setMeta('meta[name="description"]', { name: 'description', content: finalDescription })
  setMeta('meta[name="keywords"]', { name: 'keywords', content: keywords })
  setMeta('meta[name="robots"]', { name: 'robots', content: robots })
  setMeta('meta[name="author"]', { name: 'author', content: author || SITE_NAME })
  setLink('canonical', canonicalUrl)

  setMeta('meta[property="og:site_name"]', { property: 'og:site_name', content: SITE_NAME })
  setMeta('meta[property="og:type"]', { property: 'og:type', content: type })
  setMeta('meta[property="og:title"]', { property: 'og:title', content: fullTitle })
  setMeta('meta[property="og:description"]', { property: 'og:description', content: finalDescription })
  setMeta('meta[property="og:url"]', { property: 'og:url', content: canonicalUrl })
  setMeta('meta[property="og:image"]', { property: 'og:image', content: imageUrl })
  setMeta('meta[property="og:locale"]', { property: 'og:locale', content: 'vi_VN' })

  setMeta('meta[name="twitter:card"]', { name: 'twitter:card', content: 'summary_large_image' })
  setMeta('meta[name="twitter:title"]', { name: 'twitter:title', content: fullTitle })
  setMeta('meta[name="twitter:description"]', { name: 'twitter:description', content: finalDescription })
  setMeta('meta[name="twitter:image"]', { name: 'twitter:image', content: imageUrl })

  if (type === 'article') {
    if (publishedTime) {
      setMeta('meta[property="article:published_time"]', {
        property: 'article:published_time',
        content: publishedTime,
      })
    }
    if (modifiedTime) {
      setMeta('meta[property="article:modified_time"]', {
        property: 'article:modified_time',
        content: modifiedTime,
      })
    }
    if (author) {
      setMeta('meta[property="article:author"]', {
        property: 'article:author',
        content: author,
      })
    }
  } else {
    removeMeta('meta[property="article:published_time"]')
    removeMeta('meta[property="article:modified_time"]')
    removeMeta('meta[property="article:author"]')
  }

  setJsonLd(schema)
}

export const websiteSchema = (url = '/') => ({
  '@context': 'https://schema.org',
  '@type': 'WebSite',
  name: SITE_NAME,
  url: absoluteUrl(url),
  potentialAction: {
    '@type': 'SearchAction',
    target: `${absoluteUrl('/tin-tuc')}?q={search_term_string}`,
    'query-input': 'required name=search_term_string',
  },
})
