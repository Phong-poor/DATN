const STORAGE_KEY = 'affiliate_video_attribution'
const ATTRIBUTION_DAYS = 30

export const rememberAffiliateVideo = (videoId) => {
  const id = Number(videoId)
  if (!Number.isInteger(id) || id <= 0) return

  localStorage.setItem(STORAGE_KEY, JSON.stringify({
    video_id: id,
    expires_at: Date.now() + ATTRIBUTION_DAYS * 24 * 60 * 60 * 1000,
  }))
}

export const getAffiliateVideoId = () => {
  try {
    const attribution = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null')
    const id = Number(attribution?.video_id)
    if (!Number.isInteger(id) || id <= 0 || Number(attribution?.expires_at) <= Date.now()) {
      localStorage.removeItem(STORAGE_KEY)
      return null
    }
    return id
  } catch {
    localStorage.removeItem(STORAGE_KEY)
    return null
  }
}

export const clearAffiliateVideo = () => localStorage.removeItem(STORAGE_KEY)
