import axios from 'axios'
import { clearAuth, getToken } from './auth'
import { apiBaseUrl } from './urls'

const api = axios.create({
  baseURL: apiBaseUrl,
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

const GET_CACHE_TTL_MS = 60 * 1000
const getCache = new Map()
const inFlightGetRequests = new Map()

const shouldShowGlobalLoader = (config = {}) => config.showGlobalLoader === true
const shouldCacheGet = (config = {}) => config.method?.toLowerCase?.() === 'get' && config.cache !== false
const getCacheKey = (url, config = {}) => {
  const params = config.params ? JSON.stringify(config.params) : ''
  return `${url || ''}?${params}`
}

api.interceptors.request.use((config) => {
  const method = config.method?.toLowerCase?.()
  if (method && method !== 'get') {
    getCache.clear()
    inFlightGetRequests.clear()
  }

  if (shouldShowGlobalLoader(config)) {
    window.dispatchEvent(new Event('global-loader-show'))
  }

  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}, (error) => {
  if (shouldShowGlobalLoader(error.config)) {
    window.dispatchEvent(new Event('global-loader-hide'))
  }
  return Promise.reject(error)
})

api.interceptors.response.use(
  (response) => {
    if (shouldShowGlobalLoader(response.config)) {
      window.dispatchEvent(new Event('global-loader-hide'))
    }
    return response
  },
  (error) => {
    if (shouldShowGlobalLoader(error.config)) {
      window.dispatchEvent(new Event('global-loader-hide'))
    }
    if (error.response?.status === 401) {
      clearAuth()
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

const rawGet = api.get.bind(api)
api.get = (url, config = {}) => {
  const requestConfig = { ...config, method: 'get' }
  if (!shouldCacheGet(requestConfig)) return rawGet(url, config)

  const key = getCacheKey(url, requestConfig)
  const cached = getCache.get(key)
  if (cached && Date.now() - cached.cachedAt < GET_CACHE_TTL_MS) {
    return Promise.resolve(cached.response)
  }

  const inFlight = inFlightGetRequests.get(key)
  if (inFlight) return inFlight

  const request = rawGet(url, config)
    .then((response) => {
      getCache.set(key, { cachedAt: Date.now(), response })
      return response
    })
    .finally(() => {
      inFlightGetRequests.delete(key)
    })

  inFlightGetRequests.set(key, request)
  return request
}

export default api
