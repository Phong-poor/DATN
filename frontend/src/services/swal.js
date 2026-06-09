import Swal from 'sweetalert2'

const icons = {
  success: `
    <svg class="swal2-svg-icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M20 6L9 17l-5-5" />
    </svg>
  `,
  error: `
    <svg class="swal2-svg-icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M18 6L6 18M6 6l12 12" />
    </svg>
  `,
  warning: `
    <svg class="swal2-svg-icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 8v5" />
      <path d="M12 17h.01" />
    </svg>
  `,
  info: `
    <svg class="swal2-svg-icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 11v5" />
      <path d="M12 8h.01" />
    </svg>
  `,
  question: `
    <svg class="swal2-svg-icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M9.5 9a2.5 2.5 0 1 1 3.4 2.33c-.62.27-.9.7-.9 1.42V14" />
      <path d="M12 17h.01" />
    </svg>
  `,
}

const iconConfig = (icon) => ({
  icon,
  iconHtml: icons[icon],
})

const commonConfig = {
  buttonsStyling: false,
  width: 'min(400px, calc(100vw - 32px))',
  padding: '0',
  background: '#ffffff',
  color: '#0f172a',
  showClass: {
    popup: 'swal2-show',
    backdrop: 'swal2-backdrop-show',
  },
  hideClass: {
    popup: 'swal2-hide',
    backdrop: 'swal2-backdrop-hide',
  },
  customClass: {
    popup: 'swal2-custom-popup',
    icon: 'swal2-custom-icon',
    title: 'swal2-custom-title',
    htmlContainer: 'swal2-custom-content',
    confirmButton: 'swal2-custom-confirm',
    cancelButton: 'swal2-custom-cancel',
  },
}

const swal = {
  success(title, text = '') {
    return Swal.fire({
      ...iconConfig('success'),
      title,
      text,
      ...commonConfig,
      timer: 2200,
      showConfirmButton: false,
    })
  },

  error(title, text = '') {
    return Swal.fire({
      ...iconConfig('error'),
      title,
      text,
      ...commonConfig,
    })
  },

  warning(title, text = '') {
    return Swal.fire({
      ...iconConfig('warning'),
      title,
      text,
      ...commonConfig,
    })
  },

  info(title, text = '') {
    return Swal.fire({
      ...iconConfig('info'),
      title,
      text,
      ...commonConfig,
    })
  },

  async confirm(title, text = '', confirmButtonText = 'Đồng ý', cancelButtonText = 'Hủy') {
    const result = await Swal.fire({
      ...iconConfig('question'),
      title,
      text,
      showCancelButton: true,
      confirmButtonText,
      cancelButtonText,
      ...commonConfig,
    })

    return result.isConfirmed
  },

  toast(title, icon = 'success') {
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      background: '#ffffff',
      color: '#0f172a',
      customClass: {
        popup: 'swal2-toast-popup',
        title: 'swal2-toast-title',
      },
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      },
    })

    return Toast.fire({ icon, title })
  },
}

export default swal
