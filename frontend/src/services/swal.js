import Swal from 'sweetalert2'

const commonConfig = {
  confirmButtonColor: '#2563eb',
  cancelButtonColor: '#94a3b8',
  buttonsStyling: true,
  padding: '0',
  background: 'transparent',
  color: '#0f172a',
  customClass: {
    popup: 'swal2-custom-popup',
    title: 'swal2-custom-title',
    htmlContainer: 'swal2-custom-content',
    confirmButton: 'swal2-custom-confirm',
    cancelButton: 'swal2-custom-cancel',
  },
}

const swal = {
  success(title, text = '') {
    return Swal.fire({
      icon: 'success',
      title,
      text,
      ...commonConfig,
      timer: 2500,
      showConfirmButton: false,
    })
  },

  error(title, text = '') {
    return Swal.fire({
      icon: 'error',
      title,
      text,
      ...commonConfig,
    })
  },

  warning(title, text = '') {
    return Swal.fire({
      icon: 'warning',
      title,
      text,
      ...commonConfig,
    })
  },

  info(title, text = '') {
    return Swal.fire({
      icon: 'info',
      title,
      text,
      ...commonConfig,
    })
  },

  async confirm(title, text = '', confirmButtonText = 'Đồng ý', cancelButtonText = 'Hủy') {
    const result = await Swal.fire({
      title,
      text,
      icon: 'question',
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
