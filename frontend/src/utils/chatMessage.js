import api from '@/services/api'
import { apiBaseUrl } from '@/services/urls'

const IMAGE_EXT = /\.(jpe?g|png|gif|webp|bmp|svg)$/i
const DEFAULT_NOTICE_TITLE = '💬 Có tin nhắn mới'

let originalTitle = ''
let noticeTimer = null
let noticeIndex = 0

export const hasChatAttachment = (msg) =>
  !!(msg?.duongdan_dinhkem_url || msg?.duongdan_dinhkem)

export const isChatImageAttachment = (msg) => {
  if (!hasChatAttachment(msg)) return false
  const name = String(msg.ten_dinhkem || msg.duongdan_dinhkem || '').toLowerCase()
  if (IMAGE_EXT.test(name)) return true
  const url = String(msg.duongdan_dinhkem_url || '')
  return url.startsWith('data:image/') || url.startsWith('blob:')
}

export const chatAttachmentUrl = (msg) => {
  if (!msg) return ''

  const path = String(msg.duongdan_dinhkem || '').trim().replace(/\\/g, '/')
  if (path) {
    const filename = path.split('/').pop()
    if (filename) {
      return `${apiBaseUrl}/chat/attachments/${encodeURIComponent(filename)}`
    }
  }

  const raw = String(msg.duongdan_dinhkem_url || '').trim()
  if (raw.startsWith('data:') || raw.startsWith('blob:')) {
    return raw
  }

  return ''
}

export const conversationPreviewFromMessage = (msg) => {
  if (msg?.noidung) return msg.noidung
  if (!hasChatAttachment(msg)) return ''
  return isChatImageAttachment(msg) ? '[Hình ảnh]' : '[Tệp đính kèm]'
}

export const readFileAsDataURL = (file) =>
  new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = (ev) => resolve(ev.target.result)
    reader.onerror = reject
    reader.readAsDataURL(file)
  })

export const isImageFile = (file) => file?.type?.startsWith('image/')

export const sendChatMessage = (endpoint, { conversationId, text, items = [] }) => {
  if (items.length > 0) {
    return api.post(endpoint, {
      id_cuoc_tro_chuyen: conversationId,
      noidung: text,
      attachments_base64: items.map((i) => i.dataUrl),
      attachment_names: items.map((i) => i.name),
    })
  }
  return api.post(endpoint, { id_cuoc_tro_chuyen: conversationId, noidung: text })
}

const pushOptimisticAttachments = (messages, authUserId, items, caption = '') => {
  items.forEach((item, index) => {
    messages.push({
      id_nguoigui: authUserId,
      noidung: index === 0 ? caption : '',
      created_at: new Date().toISOString(),
      duongdan_dinhkem_url: item.dataUrl,
      ten_dinhkem: item.name,
    })
  })
}

const mergeServerMessages = (messages, serverMessages, previews) => {
  serverMessages.forEach((serverMsg, index) => {
    const previewIdx = messages.findIndex((m) => !m.id && m.duongdan_dinhkem_url === previews[index])
    if (previewIdx !== -1) {
      messages[previewIdx] = serverMsg
    } else {
      messages.push(serverMsg)
    }
  })
}

export const patchMessage = (messages, updated) => {
  const idx = messages.findIndex((m) => Number(m.id) === Number(updated.id))
  if (idx !== -1) messages[idx] = updated
}

export const removeMessageById = (messages, id) => {
  const idx = messages.findIndex((m) => Number(m.id) === Number(id))
  if (idx !== -1) messages.splice(idx, 1)
}

export const startChatTitleNotice = (message = DEFAULT_NOTICE_TITLE) => {
  if (typeof document === 'undefined') return

  if (!noticeTimer) {
    originalTitle = document.title
  }

  const frames = [
    message,
    `${message} •`,
    `${message} ••`,
    `${message} •••`,
  ]

  clearInterval(noticeTimer)
  noticeIndex = 0
  document.title = frames[noticeIndex]
  noticeTimer = setInterval(() => {
    noticeIndex = (noticeIndex + 1) % frames.length
    document.title = frames[noticeIndex]
  }, 700)
}

export const stopChatTitleNotice = () => {
  if (typeof document === 'undefined') return
  if (!noticeTimer) return

  clearInterval(noticeTimer)
  noticeTimer = null
  noticeIndex = 0
  document.title = originalTitle || document.title
  originalTitle = ''
}

const responseMessages = (res) => {
  const data = res?.data || {}
  if (Array.isArray(data.messages)) return data.messages
  if (data.message && typeof data.message === 'object') return [data.message]
  return []
}

export const submitChatComposer = async ({
  endpoint,
  conversationId,
  text,
  items,
  messagesRef,
  authUserId,
}) => {
  const list = messagesRef.value
  const previews = (items || []).map((i) => i.dataUrl)

  if (previews.length > 0) {
    pushOptimisticAttachments(list, authUserId, items, text)
    const res = await sendChatMessage(endpoint, { conversationId, text, items })
    const serverMessages = responseMessages(res)
    mergeServerMessages(list, serverMessages, previews)
    messagesRef.value = [...list]
    return text || (items.length > 1 ? `[Đính kèm] (${items.length})` : (items[0]?.isImage ? '[Hình ảnh]' : `[Tệp] ${items[0]?.name || ''}`))
  }

  if (text) {
    const clientKey = `tmp-${Date.now()}-${Math.random().toString(36).slice(2)}`
    const tempMsg = {
      _clientKey: clientKey,
      id_nguoigui: authUserId,
      noidung: text,
      created_at: new Date().toISOString(),
    }
    list.push(tempMsg)
    const res = await sendChatMessage(endpoint, { conversationId, text, items: [] })
    const serverMsg = responseMessages(res)[0]
    const idx = list.findIndex((m) => m._clientKey === clientKey)
    if (idx !== -1 && serverMsg) {
      list[idx] = serverMsg
      messagesRef.value = [...list]
    }
    return text
  }

  return ''
}

export const bindChatChannel = (echo, channelName, messagesRef, authUserId, onIncoming) => {
  if (!echo || !channelName) return

  echo.leaveChannel(channelName)

  echo.private(channelName)
    .listen('.message.sent', (e) => {
      if (Number(e.message.id_nguoigui) === Number(authUserId)) return
      const list = messagesRef.value
      if (!list.some((m) => Number(m.id) === Number(e.message.id))) {
        list.push(e.message)
      }
      onIncoming?.(e.message)
    })
    .listen('.message.read', (e) => {
      const list = messagesRef.value
      let changed = false
      const readerId = e.readByUserId
      list.forEach((m) => {
        if (!readerId || Number(m.id_nguoigui) !== Number(readerId)) {
          if (!m.daxem) {
            m.daxem = true
            changed = true
          }
        }
      })
      if (changed) {
        messagesRef.value = [...list]
      }
    })
    .listen('.message.updated', (e) => {
      patchMessage(messagesRef.value, e.message)
    })
    .listen('.message.deleted', (e) => {
      removeMessageById(messagesRef.value, e.id)
    })
}
