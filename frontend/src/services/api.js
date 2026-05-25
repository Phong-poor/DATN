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

const shouldShowGlobalLoader = (config = {}) => config.showGlobalLoader === true

api.interceptors.request.use((config) => {
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

export default api
