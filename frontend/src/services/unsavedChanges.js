import { ref } from 'vue'
import swal from './swal'

export const isFormDirty = ref(false)

export const initUnsavedChangesGuard = (router) => {
  // --- 1. Lắng nghe sự kiện nhập liệu toàn cục ---
  const markDirty = (e) => {
    const target = e.target
    if (!target) return

    // CHỈ áp dụng khi đang ở các trang quản trị Admin hoặc trang cá nhân trên Web
    const currentPath = window.location.pathname + window.location.hash
    const isAllowedPath = 
      currentPath.includes('/admin') || 
      currentPath.includes('/trang-ca-nhan') || 
      currentPath.includes('/profile') ||
      currentPath.includes('/don-hang') ||
      currentPath.includes('/orderspage') ||
      currentPath.includes('/lien-he') ||
      currentPath.includes('/contact')
    if (!isAllowedPath) {
      return
    }

    const tagName = target.tagName.toLowerCase()
    if (tagName !== 'input' && tagName !== 'textarea' && tagName !== 'select') return

    // Bỏ qua các ô tìm kiếm nhanh
    if (
      target.type === 'search' ||
      target.hasAttribute('no-guard') ||
      target.getAttribute('role') === 'search' ||
      target.classList.contains('search-input')
    ) {
      return
    }

    // Bỏ qua nếu nằm trong form tìm kiếm
    const form = target.closest('form')
    if (form && (form.getAttribute('role') === 'search' || form.classList.contains('search-form'))) {
      return
    }

    // Đánh dấu form có thay đổi chưa lưu
    isFormDirty.value = true
  }

  document.addEventListener('input', markDirty)
  document.addEventListener('change', markDirty)

  // --- 2. Tự động tắt cảnh báo khi nhấn nút Lưu/Submit ---
  document.addEventListener('submit', () => {
    isFormDirty.value = false
  })

  // Lắng nghe sự kiện click vào các nút lưu/cập nhật/thêm mới để tắt cảnh báo (khi lưu thành công)
  document.addEventListener('click', (e) => {
    const button = e.target.closest('button, input[type="submit"], input[type="button"]')
    if (!button) return

    const text = (button.textContent || button.value || '').toLowerCase().trim()
    const isSaveAction = 
      button.type === 'submit' ||
      text.includes('lưu') ||
      text.includes('cập nhật') ||
      text.includes('thêm mới') ||
      text.includes('hoàn thành') ||
      text.includes('xác nhận') ||
      text.includes('gửi') ||
      button.classList.contains('btn-save') ||
      button.classList.contains('btn-submit') ||
      button.classList.contains('btn-warning-confirm') ||
      button.classList.contains('btn-danger-confirm')

    if (isSaveAction) {
      isFormDirty.value = false
    }
  })

  // Lắng nghe ở phase CAPTURING (useCapture = true) để chặn các nút Quay lại / Hủy trước khi Vue thay đổi state view
  let isBypassing = false
  document.addEventListener('click', async (e) => {
    if (isBypassing) return

    // Bỏ qua các click phát sinh bên trong thông báo SweetAlert
    if (e.target.closest('.swal2-container, .swal2-popup')) return

    const currentPath = window.location.pathname + window.location.hash
    const isAllowedPath = 
      currentPath.includes('/admin') || 
      currentPath.includes('/trang-ca-nhan') || 
      currentPath.includes('/profile') ||
      currentPath.includes('/don-hang') ||
      currentPath.includes('/orderspage') ||
      currentPath.includes('/lien-he') ||
      currentPath.includes('/contact')
    if (!isAllowedPath) return

    const target = e.target.closest('button, a, .back-btn, .btn-back, .btn-cancel')
    if (!target) return

    if (target.hasAttribute('no-guard') || target.closest('[no-guard]')) return

    const text = (target.textContent || '').toLowerCase().replace(/\s+/g, ' ').trim()
    const isBackAction = 
      text.includes('quay lại danh sách') ||
      text.includes('quay lại') ||
      text === 'hủy' ||
      text === 'hủy bỏ' ||
      target.classList.contains('back-btn') ||
      target.classList.contains('btn-back') ||
      target.classList.contains('btn-cancel')

    if (isBackAction && isFormDirty.value) {
      e.stopPropagation()
      e.preventDefault()

      const confirmed = await swal.confirm(
        'Xác nhận quay lại',
        'Bạn đang có thay đổi chưa lưu trong form. Nếu quay lại, các dữ liệu đã nhập sẽ bị mất. Bạn vẫn muốn tiếp tục chứ?',
        'Có, quay lại',
        'Không, ở lại'
      )
      if (confirmed) {
        isFormDirty.value = false
        isBypassing = true
        target.click() // Phát lại sự kiện click để Vue chạy xử lý bình thường
        isBypassing = false
      }
    }
  }, true)

  // --- 3. Cảnh báo khi F5 hoặc đóng tab (Chỉ kích hoạt ở /admin) ---
  window.addEventListener('beforeunload', (e) => {
    const currentPath = window.location.pathname + window.location.hash
    const isAllowedPath = 
      currentPath.includes('/admin') || 
      currentPath.includes('/trang-ca-nhan') || 
      currentPath.includes('/profile') ||
      currentPath.includes('/don-hang') ||
      currentPath.includes('/orderspage') ||
      currentPath.includes('/lien-he') ||
      currentPath.includes('/contact')
    if (isAllowedPath && isFormDirty.value) {
      e.preventDefault()
      e.returnValue = '' // Kích hoạt cảnh báo mặc định của trình duyệt
    }
  })

  // --- 4. Tích hợp Vue Router Guard (Chỉ chặn khi từ trang Admin đi) ---
  router.beforeEach(async (to, from, next) => {
    const isFromAdmin = from.path.includes('/admin')
    const isFromProfile = 
      from.path.includes('/trang-ca-nhan') || 
      from.path.includes('/profile') ||
      from.path.includes('/don-hang') ||
      from.path.includes('/orderspage') ||
      from.path.includes('/lien-he') ||
      from.path.includes('/contact')
    if ((isFromAdmin || isFromProfile) && isFormDirty.value) {
      const confirmed = await swal.confirm(
        'Xác nhận rời trang',
        'Bạn đang có thay đổi chưa lưu trong form. Nếu rời đi, các dữ liệu đã nhập sẽ bị mất. Bạn vẫn muốn tiếp tục chứ?',
        'Có, rời trang',
        'Không, ở lại'
      )
      if (confirmed) {
        isFormDirty.value = false // Tắt cờ để cho phép chuyển trang
        // Hiển thị loading overlay sau khi người dùng xác nhận rời trang
        window.dispatchEvent(
          new CustomEvent('global-loader-show', {
            detail: { immediate: true, minDuration: 260 },
          })
        )
        next()
      } else {
        next(false) // Hủy bỏ chuyển trang
      }
    } else {
      next()
    }
  })
}
