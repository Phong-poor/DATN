const AUTO_REVEAL_SELECTORS = [
  '.premium-product-card',
  '.news-card',
  '.article-card',
  '.cart-item',
  '.checkout-card',
  '.profile-card',
  '.wishlist-card',
  '.order-card',
  '.contact-card',
  '.catalog-panel',
  '.catalog-sidebar',
  '.filter-panel',
  '.section-header',
  '.premium-toolbar',
  '.product-detail-section',
  '.product-info-panel',
  '.related-products',
  '.admin-card',
  '.dashboard-card',
]

const AUTO_REVEAL_CONTAINERS = [
  '.catalog-product-grid',
  '.products-grid',
  '.news-grid',
  '.wishlist-grid',
  '.orders-list',
  '.cart-layout',
  '.checkout-layout',
  '.profile-layout',
]

let observer = null
let mutationObserver = null
let refreshTimer = null

const canAnimate = () => {
  if (typeof window === 'undefined') return false
  return !window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
}

const revealElement = (element) => {
  element.classList.add('is-visible', 'active', 'revealed')
}

const getRevealTargets = () => {
  const root = document.getElementById('app')
  if (!root) return []

  const explicit = root.querySelectorAll(
    '[data-scroll-effect], .scroll-reveal, .reveal-el, .global-scroll-effect'
  )
  const auto = root.querySelectorAll(AUTO_REVEAL_SELECTORS.join(','))
  const containers = root.querySelectorAll(AUTO_REVEAL_CONTAINERS.join(','))

  return [...explicit, ...auto, ...containers]
    .filter((element) => element instanceof HTMLElement)
    .filter((element) => !element.closest('[data-no-scroll-effect]'))
}

const prepareElement = (element, index) => {
  if (element.dataset.scrollEffectBound === '1') return

  element.dataset.scrollEffectBound = '1'
  element.classList.add('global-scroll-effect')

  const delay = Math.min(index % 8, 7) * 55
  element.style.setProperty('--scroll-effect-delay', `${delay}ms`)

  observer.observe(element)
}

export const refreshScrollEffects = () => {
  if (!canAnimate()) return

  window.clearTimeout(refreshTimer)
  refreshTimer = window.setTimeout(() => {
    if (!observer) return
    getRevealTargets().forEach(prepareElement)
  }, 60)
}

export const installScrollEffects = (router) => {
  if (!canAnimate() || observer) return

  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return
        revealElement(entry.target)
        observer.unobserve(entry.target)
      })
    },
    {
      root: null,
      threshold: 0.12,
      rootMargin: '0px 0px -8% 0px',
    }
  )

  refreshScrollEffects()

  router?.afterEach?.(() => {
    window.setTimeout(refreshScrollEffects, 120)
    window.setTimeout(refreshScrollEffects, 450)
  })

  mutationObserver = new MutationObserver(() => refreshScrollEffects())
  const appRoot = document.getElementById('app')
  if (appRoot) {
    mutationObserver.observe(appRoot, { childList: true, subtree: true })
  }

  window.addEventListener('load', refreshScrollEffects, { once: true })
}
