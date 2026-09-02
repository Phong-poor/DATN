import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import swal from './swal'
import { getToken } from './auth'
import { apiBaseUrl } from './urls'
import { isFormDirty } from './unsavedChanges'

// --- Callback đăng ký để gọi ngược về api.js tránh vòng lặp circular dependency ---
let syncSuccessCallback = null
export const registerSyncSuccessCallback = (cb) => {
  syncSuccessCallback = cb
}

// --- 1. Trạng thái kết nối và hàng đợi phản ứng ---
export const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true)
export const isSyncing = ref(false)

const QUEUE_STORAGE_KEY = 'offline_requests_queue'

const loadQueue = () => {
  if (typeof localStorage === 'undefined') return []
  try {
    const raw = localStorage.getItem(QUEUE_STORAGE_KEY)
    return raw ? JSON.parse(raw) : []
  } catch (e) {
    console.error('Lỗi khi đọc hàng đợi offline từ localStorage:', e)
    return []
  }
}

export const offlineQueue = ref(loadQueue())

export const saveQueueToStorage = () => {
  if (typeof localStorage === 'undefined') return
  localStorage.setItem(QUEUE_STORAGE_KEY, JSON.stringify(offlineQueue.value))
}

// --- 2. Khởi tạo Axios client chuyên dụng cho đồng bộ ---
const syncClient = axios.create({
  baseURL: apiBaseUrl,
  timeout: 20000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Đính kèm token hiện tại vào yêu cầu đồng bộ
syncClient.interceptors.request.use((config) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}, (err) => Promise.reject(err))

// --- 3. Đánh tên thân thiện cho Form ---
const getFormFriendlyName = (url, method) => {
  const path = String(url || '').toLowerCase()
  const m = String(method || '').toLowerCase()
  const isPost = m === 'post'
  const action = isPost ? 'Thêm mới' : (m === 'delete' ? 'Xóa' : 'Cập nhật')

  if (path.includes('/admin/vaitro')) return `${action} Vai trò & Quyền`
  if (path.includes('/admin/account/profile')) return 'Cập nhật Hồ sơ Admin'
  if (path.includes('/admin/sanpham') || path.includes('/admin/products')) return `${action} Sản phẩm`
  if (path.includes('/admin/danhmuc') || path.includes('/admin/categories')) return `${action} Danh mục`
  if (path.includes('/admin/thuonghieu') || path.includes('/admin/brands')) return `${action} Thương hiệu`
  if (path.includes('/admin/donhang') || path.includes('/admin/orders')) return `Xử lý Đơn hàng`
  if (path.includes('/admin/khuyenmai') || path.includes('/admin/promotions')) return `${action} Khuyến mãi`
  if (path.includes('/admin/banners')) return `${action} Banner`
  if (path.includes('/admin/tintuc') || path.includes('/admin/news')) return `${action} Tin tức / Bài viết`
  if (path.includes('/user/avatar')) return 'Đổi ảnh đại diện'
  if (path.includes('/don-hang') || path.includes('/checkout')) return 'Gửi Đơn hàng mới'

  return `${action} dữ liệu (${url})`
}

// --- 4. Thêm yêu cầu vào hàng đợi ngoại tuyến ---
export const enqueueRequest = (config) => {
  const data = typeof config.data === 'string' ? JSON.parse(config.data) : config.data
  
  // Tránh thêm trùng lặp yêu cầu giống hệt nhau liên tiếp
  const isDuplicate = offlineQueue.value.some(item => 
    item.url === config.url && 
    item.method === config.method && 
    JSON.stringify(item.data) === JSON.stringify(data) &&
    item.status === 'pending'
  )
  
  if (isDuplicate) {
    console.log('Yêu cầu trùng lặp đã tồn tại trong hàng đợi.')
    return
  }

  const formName = getFormFriendlyName(config.url, config.method)
  const item = {
    id: Date.now() + Math.random().toString(36).substr(2, 9),
    url: config.url,
    method: config.method,
    data: data,
    headers: {
      'Content-Type': config.headers?.['Content-Type'] || config.headers?.['content-type'] || 'application/json'
    },
    timestamp: Date.now(),
    formName: formName,
    route: window.location.pathname,
    status: 'pending',
    errorMessage: ''
  }

  offlineQueue.value.push(item)
  saveQueueToStorage()

  // Báo Toast nhanh
  swal.toast(`Đã lưu tạm offline: ${formName}`, 'warning')
  
  // Phát sự kiện cập nhật hàng đợi
  window.dispatchEvent(new Event('offline-queue-updated'))
}

// --- 5. Thực hiện đồng bộ hóa hàng đợi ---
export const syncQueue = async () => {
  if (typeof navigator !== 'undefined') {
    isOnline.value = navigator.onLine
  }
  const online = isOnline.value && (typeof navigator !== 'undefined' ? navigator.onLine : true)
  if (isSyncing.value || offlineQueue.value.length === 0 || !online) return
  isSyncing.value = true

  console.log('Bắt đầu đồng bộ hàng đợi ngoại tuyến...')
  const queue = [...offlineQueue.value]

  for (let i = 0; i < queue.length; i++) {
    const item = queue[i]
    if (item.status === 'success') continue

    // Chỉ đồng bộ các mục đang chờ hoặc đã lỗi trước đó
    item.status = 'syncing'
    saveQueueToStorage()
    window.dispatchEvent(new Event('offline-queue-updated'))

    try {
      await syncClient({
        url: item.url,
        method: item.method,
        data: item.data,
        headers: item.headers,
      })

      // Đồng bộ thành công
      item.status = 'success'
      // Xóa khỏi hàng đợi phản ứng
      offlineQueue.value = offlineQueue.value.filter(q => q.id !== item.id)
      saveQueueToStorage()
      
      swal.toast(`Đồng bộ thành công: ${item.formName}`, 'success')
      
      // Xóa bản nháp liên quan nếu có
      localStorage.removeItem(`global_form_draft_${item.route}`)
      localStorage.removeItem(`form_draft_key_${item.route}`)
      localStorage.removeItem(`form_draft_${item.route}`)
      
      // Xóa bộ nhớ cache Axios để tải dữ liệu mới
      if (syncSuccessCallback) {
        syncSuccessCallback()
      }
      
      // Phát sự kiện để màn hình hiện tại biết để refresh dữ liệu nếu cần
      window.dispatchEvent(new CustomEvent('offline-sync-success', { detail: item }))
    } catch (error) {
      console.error(`Lỗi đồng bộ mục ${item.id}:`, error)
      const status = error.response?.status

      if (status >= 400 && status < 500) {
        // Lỗi nghiệp vụ hoặc validation (ví dụ: thiếu thông tin, trùng tên, dữ liệu không đủ)
        // Đánh dấu thất bại để người dùng xem và bấm khôi phục để sửa tiếp
        item.status = 'failed'
        item.errorMessage = error.response?.data?.message || error.response?.data?.error || 'Dữ liệu không đầy đủ hoặc không hợp lệ.'
        saveQueueToStorage()
        
        swal.confirm(
          'Đồng bộ thất bại',
          `Mục "${item.formName}" bị từ chối: ${item.errorMessage}. Bạn có muốn khôi phục dữ liệu lại vào form để sửa đổi và gửi lại không?`,
          'Khôi phục ngay',
          'Để sau'
        ).then((confirmed) => {
          if (confirmed) {
            restoreRequestToForm(item)
          }
        })
      } else {
        // Lỗi kết nối tiếp (mất mạng lại) hoặc server 500
        // Đặt lại chờ đồng bộ và dừng tiến trình đồng bộ các mục tiếp theo
        item.status = 'pending'
        saveQueueToStorage()
        break
      }
    }
  }

  isSyncing.value = false
  window.dispatchEvent(new Event('offline-queue-updated'))
}

// --- 6. Khôi phục yêu cầu lỗi về Form để làm tiếp ---
export const restoreRequestToForm = (item) => {
  // Lưu payload vào khóa đặc biệt để khôi phục khi chuyển đến trang tương ứng
  localStorage.setItem('pending_restore_form', JSON.stringify({
    route: item.route,
    data: item.data,
    url: item.url
  }))

  // Xóa khỏi hàng đợi
  offlineQueue.value = offlineQueue.value.filter(q => q.id !== item.id)
  saveQueueToStorage()
  window.dispatchEvent(new Event('offline-queue-updated'))

  // Chuyển trang nếu đang ở trang khác
  if (window.location.pathname !== item.route) {
    window.location.href = item.route
  } else {
    // Nếu đã ở đúng trang, kích hoạt sự kiện khôi phục ngay lập tức
    window.dispatchEvent(new Event('restore-form-trigger'))
  }
}

// Xóa một yêu cầu ra khỏi hàng đợi
export const deleteQueueItem = (id) => {
  offlineQueue.value = offlineQueue.value.filter(q => q.id !== id)
  saveQueueToStorage()
  window.dispatchEvent(new Event('offline-queue-updated'))
}

// --- 7. Thiết lập Interceptor chặn các API lỗi mạng ---
export const initOfflineInterceptor = (apiInstance) => {
  // Đánh chặn trước khi gửi yêu cầu đi
  apiInstance.interceptors.request.use((config) => {
    if (typeof navigator !== 'undefined') {
      isOnline.value = navigator.onLine
    }
    const method = config.method?.toLowerCase?.()
    const isMutation = method === 'post' || method === 'put' || method === 'delete' || method === 'patch'
    const isAdminRoute = typeof window !== 'undefined' && window.location.pathname.startsWith('/admin')
    
    // Bỏ qua các cuộc gọi chạy ngầm (heartbeat hoặc kiểm tra phiên)
    const url = String(config.url || '').toLowerCase()
    if (url.includes('/user/heartbeat') || url.includes('/auth/session')) {
      return config
    }

    // Nếu đang không có mạng và là yêu cầu thay đổi dữ liệu (trừ yêu cầu bypass) trên trang admin
    if (isAdminRoute && !isOnline.value && isMutation && config.bypassOffline !== true) {
      enqueueRequest(config)
      
      const err = new Error('OFFLINE_QUEUED')
      err.isOfflineQueue = true
      err.config = config
      err.response = {
        status: 202,
        data: {
          success: false,
          message: 'Bạn đang ngoại tuyến. Dữ liệu đã được lưu tạm và sẽ tự động đồng bộ khi có mạng.'
        }
      }
      return Promise.reject(err)
    }
    return config
  }, (err) => Promise.reject(err))

  // Đánh chặn lỗi phản hồi để bắt sự cố mất mạng đột ngột giữa chừng
  apiInstance.interceptors.response.use(
    (response) => response,
    (error) => {
      if (typeof navigator !== 'undefined') {
        isOnline.value = navigator.onLine
      }
      const config = error.config
      if (!config) return Promise.reject(error)

      // Bỏ qua các cuộc gọi chạy ngầm
      const url = String(config.url || '').toLowerCase()
      if (url.includes('/user/heartbeat') || url.includes('/auth/session')) {
        return Promise.reject(error)
      }

      const method = config.method?.toLowerCase?.()
      const isMutation = method === 'post' || method === 'put' || method === 'delete' || method === 'patch'
      const isAdminRoute = typeof window !== 'undefined' && window.location.pathname.startsWith('/admin')
      
      // Kiểm tra xem có phải lỗi mất kết nối mạng hay không
      const isNetworkError = !error.response && error.code !== 'ECONNABORTED'

      if (isAdminRoute && isNetworkError && isMutation && config.bypassOffline !== true) {
        enqueueRequest(config)
        
        const err = new Error('OFFLINE_QUEUED')
        err.isOfflineQueue = true
        err.config = config
        err.response = {
          status: 202,
          data: {
            success: false,
            message: 'Mất kết nối mạng. Yêu cầu của bạn đã được đưa vào hàng đợi tự động gửi khi có mạng lại.'
          }
        }
        return Promise.reject(err)
      }
      return Promise.reject(error)
    }
  )
}

// --- 8. Lắng nghe sự kiện Online / Offline của trình duyệt ---
if (typeof window !== 'undefined') {
  window.addEventListener('online', () => {
    isOnline.value = true
    syncQueue()
  })

  window.addEventListener('offline', () => {
    isOnline.value = false
  })
}

// --- 9. Hàm tiện ích để đăng ký Lưu bản nháp Form phản ứng (Vue watch) ---
export const registerOfflineForm = (reactiveForm, uniqueKey) => {
  const draftKey = `form_draft_key_${uniqueKey}`

  const tryRestore = () => {
    // 1. Kiểm tra khôi phục từ hàng đợi lỗi mạng
    const pendingRestore = localStorage.getItem('pending_restore_form')
    if (pendingRestore) {
      try {
        const parsed = JSON.parse(pendingRestore)
        if (parsed.route === window.location.pathname) {
          reactiveForm.value = { ...reactiveForm.value, ...parsed.data }
          localStorage.removeItem('pending_restore_form')
          autoOpenModal()
          swal.success('Khôi phục thành công', 'Đã khôi phục dữ liệu từ hàng đợi để tiếp tục sửa.')
          return true
        }
      } catch (e) {
        console.error('Lỗi khi parse dữ liệu khôi phục:', e)
      }
    }

    // 2. Kiểm tra khôi phục từ bản nháp tự động lưu
    const draft = localStorage.getItem(draftKey)
    if (draft) {
      try {
        const parsed = JSON.parse(draft)
        
        swal.confirm(
          'Phát hiện bản nháp chưa lưu',
          'Bạn có một bản nháp chưa lưu từ trước trên trang này. Bạn có muốn khôi phục không?',
          'Khôi phục',
          'Bỏ qua'
        ).then((confirmed) => {
          if (confirmed) {
            reactiveForm.value = { ...reactiveForm.value, ...parsed }
            autoOpenModal()
            swal.toast('Đã khôi phục bản nháp', 'success')
          } else {
            localStorage.removeItem(draftKey)
          }
        })
        return true
      } catch (e) {
        console.error('Lỗi khi parse bản nháp:', e)
      }
    }
    return false
  }

  // Khôi phục khi mounted
  onMounted(() => {
    tryRestore()
    
    // Lắng nghe sự kiện khôi phục thủ công (nếu người dùng bấm nút khôi phục khi đang ở đúng trang)
    window.addEventListener('restore-form-trigger', tryRestore)
  })

  // Watch thay đổi để tự động lưu bản nháp với debounce 1.2s
  let timer = null
  watch(reactiveForm, (newVal) => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => {
      // Kiểm tra form có trống hoàn toàn không
      const isEmpty = Object.values(newVal).every((v) => {
        if (v === null || v === undefined) return true
        if (Array.isArray(v) && v.length === 0) return true
        if (typeof v === 'string' && v.trim() === '') return true
        return false
      })

      if (!isEmpty) {
        localStorage.setItem(draftKey, JSON.stringify(newVal))
      } else {
        localStorage.removeItem(draftKey)
      }
    }, 1200)
  }, { deep: true })
}

export const clearFormDraft = (uniqueKey) => {
  localStorage.removeItem(`form_draft_key_${uniqueKey}`)
}

// ==========================================
// --- 10. QUẢN LÝ BẢN NHÁP TOÀN CỤC (DOM-BASED DRAFT MANAGER) ---
// ==========================================
let isRestoringDraft = false
let currentDraftData = []

// Các trang đã tích hợp registerOfflineForm riêng sẽ bị bỏ qua ở cơ chế toàn cục để tránh trùng lặp
const GLOBAL_DRAFT_BLACKLIST = [
  '/admin/ho-so-quan-tri',
  '/admin/quan-ly-vai-tro',
  '/admin/quan-ly-danh-muc',
  '/admin/quan-ly-thuong-hieu',
  '/admin/quan-ly-san-pham'
]

// Hàm tạo bộ chọn CSS duy nhất cho một phần tử
const getUniqueSelector = (el) => {
  if (!el || el.nodeType !== Node.ELEMENT_NODE) return null
  const path = []
  let current = el
  while (current && current.nodeType === Node.ELEMENT_NODE) {
    let selector = current.nodeName.toLowerCase()
    if (current.id) {
      selector += '#' + current.id
      path.unshift(selector)
      break 
    } else {
      let sib = current, sibIndex = 1
      while (sib = sib.previousElementSibling) {
        if (sib.nodeName.toLowerCase() === current.nodeName.toLowerCase()) {
          sibIndex++
        }
      }
      if (sibIndex > 1 || current.nextElementSibling) {
        selector += `:nth-of-type(${sibIndex})`
      }
    }
    path.unshift(selector)
    current = current.parentNode
  }
  return path.join(' > ')
}

// Tìm các input cần lưu nháp trên trang hiện tại
const getInputsToSave = () => {
  if (typeof document === 'undefined') return []
  const all = Array.from(document.querySelectorAll('input, textarea, select'))
  return all.filter(el => {
    const tagName = el.tagName.toLowerCase()
    // Bỏ qua các nút bấm, ảnh, file, mật khẩu hoặc input ẩn
    if (tagName === 'input' && ['submit', 'button', 'image', 'file', 'password', 'hidden'].includes(el.type)) {
      return false
    }
    // Bỏ qua các ô tìm kiếm
    if (el.type === 'search' || el.classList.contains('search-input') || el.getAttribute('role') === 'search' || el.hasAttribute('no-guard')) {
      return false
    }
    // Bỏ qua ô nhập trong form tìm kiếm
    const form = el.closest('form')
    if (form && (form.getAttribute('role') === 'search' || form.classList.contains('search-form'))) {
      return false
    }
    return true
  })
}

// Lưu bản nháp trang hiện tại
export const saveGlobalFormDraft = () => {
  if (typeof window === 'undefined') return
  // Nếu form không bẩn (đã được lưu hoặc chưa sửa đổi gì mới), không lưu nháp đè lên
  if (!isFormDirty.value) return

  const pathname = window.location.pathname
  if (!pathname.startsWith('/admin') || GLOBAL_DRAFT_BLACKLIST.includes(pathname)) return

  const inputs = getInputsToSave()
  if (inputs.length === 0) return

  const draftData = inputs.map(el => ({
    selector: getUniqueSelector(el),
    value: el.value,
    type: el.type,
    checked: el.checked
  }))

  const hasContent = draftData.some(d => d.value && d.value.trim() !== '')
  if (hasContent) {
    // Thu thập thêm thông tin ngữ cảnh (Tab đang active và Tiêu đề Modal đang mở)
    const activeTabEl = document.querySelector('.cat-tab.active, .tab.active, .nav-link.active, .active-tab')
    const activeTabText = activeTabEl ? activeTabEl.innerText.trim() : null

    const modalTitleEl = document.querySelector('.modal-title, .modal h3, .modal-header h3')
    const modalTitleText = modalTitleEl ? modalTitleEl.innerText.trim() : null

    const payload = {
      activeTabText,
      modalTitleText,
      inputs: draftData
    }

    localStorage.setItem(`global_form_draft_${pathname}`, JSON.stringify(payload))
  } else {
    localStorage.removeItem(`global_form_draft_${pathname}`)
  }
}

// Hàm tìm kiếm phần tử với cơ chế tìm tương đối (fallback) để tránh lỗi lệch cấu trúc DOM sau khi load lại trang
const findElementWithFallback = (item) => {
  if (!item.selector) return null
  
  // 1. Thử tìm bằng selector tuyệt đối trước
  let el = document.querySelector(item.selector)
  if (el) return el

  // 2. Nếu thất bại, tìm tương đối trong modal hoặc form đang mở
  try {
    const parts = item.selector.split(' > ')
    const lastPart = parts[parts.length - 1]
    
    const container = document.querySelector('.modal, form, .overlay, .admin')
    if (container) {
      // Thử tìm theo class cuối cùng của input
      el = container.querySelector(lastPart)
      if (el) return el
      
      // Thử tìm theo tag name của input
      const tagName = lastPart.split('.')[0]
      const inputs = container.querySelectorAll(tagName)
      // Nếu chỉ có 1 input cùng loại, trả về luôn
      if (inputs.length === 1) return inputs[0]
    }
  } catch (e) {
    console.warn('Lỗi khi chạy fallback finder:', e)
  }
  
  return null
}

// Thử điền dữ liệu khôi phục vào DOM
const performRestoreStep = () => {
  if (!isRestoringDraft || currentDraftData.length === 0) return 0
  
  let restoredCount = 0
  currentDraftData.forEach(item => {
    if (item.restored) {
      restoredCount++
      return
    }
    
    try {
      const el = findElementWithFallback(item)
      if (el) {
        if (el.type === 'checkbox' || el.type === 'radio') {
          el.checked = item.checked
          el.dispatchEvent(new Event('change', { bubbles: true }))
        } else {
          el.value = item.value
          el.dispatchEvent(new Event('input', { bubbles: true }))
          el.dispatchEvent(new Event('change', { bubbles: true }))
        }
        item.restored = true
        restoredCount++
      }
    } catch (e) {
      console.warn('Không thể khôi phục phần tử:', item.selector, e)
    }
  })

  if (restoredCount === currentDraftData.length) {
    isRestoringDraft = false
    currentDraftData = []
    swal.toast('Khôi phục bản nháp hoàn tất!', 'success')
  }

  return restoredCount
}

// Tự động chuyển tab dựa trên tiêu đề tab được lưu
const autoClickTab = (tabText) => {
  if (typeof document === 'undefined' || !tabText) return
  
  const tabs = Array.from(document.querySelectorAll('.cat-tab, .tab, .nav-link, .tab-btn'))
  for (const tab of tabs) {
    if (tab.innerText?.trim().toLowerCase().includes(tabText.toLowerCase())) {
      console.log('Tự động chuyển tab:', tab)
      tab.click()
      break
    }
  }
}

// Tự động tìm và click nút mở modal/form (ví dụ: "Tạo...", "Thêm...")
const autoOpenModal = (modalTitleText) => {
  if (typeof document === 'undefined') return
  
  // Danh sách các từ khóa thường dùng cho nút mở form
  const keywords = ['tạo', 'thêm', 'create', 'add', 'new', 'mới']
  
  // Trích xuất từ khóa đặc trưng từ modalTitleText để chọn đúng nút trên tab tương ứng
  let subKeyword = null
  if (modalTitleText) {
    const titleLower = modalTitleText.toLowerCase()
    if (titleLower.includes('gốc') || titleLower.includes('cha') || titleLower.includes('parent')) {
      subKeyword = 'gốc'
    } else if (titleLower.includes('con') || titleLower.includes('child')) {
      subKeyword = 'con'
    }
  }

  // Tìm tất cả các button hoặc thẻ a có vai trò button
  const buttons = Array.from(document.querySelectorAll('button, .btn, .btn-primary, [role="button"]'))
  
  for (const btn of buttons) {
    const text = btn.innerText?.toLowerCase() || ''
    
    // Kiểm tra xem nút có chứa từ khóa chính
    const hasBaseKeyword = keywords.some(k => text.includes(k))
    
    // Nếu có subKeyword (ví dụ: 'gốc' hoặc 'con'), kiểm tra nút có chứa subKeyword không
    const matchesSubKeyword = !subKeyword || text.includes(subKeyword)
    
    const isTarget = hasBaseKeyword && 
                     matchesSubKeyword &&
                     !text.includes('xóa') && 
                     !text.includes('hủy') && 
                     !text.includes('tìm') &&
                     !text.includes('search') &&
                     !text.includes('delete')
                     
    if (isTarget) {
      console.log('Tự động nhấn nút mở modal:', btn)
      btn.click()
      break
    }
  }
}

// Bắt đầu khôi phục nháp
const startGlobalRestore = (draftPayload) => {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('restore-offline-form', { detail: draftPayload }))
  }
  if (!draftPayload) return

  const isObject = draftPayload && !Array.isArray(draftPayload)
  const rawInputs = isObject ? draftPayload.inputs : draftPayload
  const inputs = Array.isArray(rawInputs) ? rawInputs : []
  const activeTabText = isObject ? draftPayload.activeTabText : null
  const modalTitleText = isObject ? draftPayload.modalTitleText : null

  currentDraftData = [...inputs]
  if (currentDraftData.length > 0) {
    isRestoringDraft = true

    // 1. Tự động click chuyển tab trước nếu có ngữ cảnh
    if (activeTabText) {
      autoClickTab(activeTabText)
    }

    // 2. Chờ 150ms để tab cập nhật DOM rồi mới click mở form tương ứng
    setTimeout(() => {
      autoOpenModal(modalTitleText)

      // 3. Chờ tiếp 250ms để DOM cập nhật modal trước khi điền dữ liệu
      setTimeout(() => {
        performRestoreStep()
        isRestoringDraft = false
        currentDraftData = []
        swal.toast('Đã khôi phục bản nháp thành công!', 'success')
      }, 250)
    }, 150)
  }
}

// Kiểm tra và hỏi khôi phục
export const checkAndPromptRestore = () => {
  if (typeof window === 'undefined') return
  const pathname = window.location.pathname
  if (!pathname.startsWith('/admin') || GLOBAL_DRAFT_BLACKLIST.includes(pathname)) return

  const key = `global_form_draft_${pathname}`
  const draftStr = localStorage.getItem(key)
  if (!draftStr) return

  try {
    const draftPayload = JSON.parse(draftStr)
    const inputs = Array.isArray(draftPayload) ? draftPayload : draftPayload.inputs
    if (!inputs || inputs.length === 0) return

    swal.confirm(
      'Phát hiện bản nháp chưa lưu',
      'Bạn có một bản nháp chưa lưu từ trước trên trang này. Bạn có muốn khôi phục không?',
      'Khôi phục',
      'Xóa nháp'
    ).then((confirmed) => {
      if (confirmed) {
        startGlobalRestore(draftPayload)
      } else {
        localStorage.removeItem(key)
      }
    })
  } catch (e) {
    console.error('Lỗi parse global draft:', e)
  }
}

// Khởi tạo trình quản lý nháp toàn cục
export const initGlobalDraftManager = (router) => {
  if (typeof window === 'undefined') return

  // 1. Tự động lưu nháp khi gõ
  let saveTimer = null
  const handleInput = () => {
    if (saveTimer) clearTimeout(saveTimer)
    saveTimer = setTimeout(() => {
      saveGlobalFormDraft()
    }, 1200)
  }

  document.addEventListener('input', handleInput)
  document.addEventListener('change', handleInput)

  // 2. Tự động kiểm tra bản nháp khi chuyển trang
  if (router) {
    router.afterEach(() => {
      isRestoringDraft = false
      currentDraftData = []
      
      setTimeout(() => {
        checkAndPromptRestore()
      }, 600)
    })
  }

  // 3. Lắng nghe click để điền tiếp các input động (khi mở modal, mở tab)
  document.addEventListener('click', () => {
    if (isRestoringDraft) {
      setTimeout(() => {
        performRestoreStep()
      }, 100)
    }
  })
}

