import Swal from 'sweetalert2'

const commonConfig = {
    confirmButtonColor: '#3b82f6', // Bright modern blue
    cancelButtonColor: '#94a3b8',
    customClass: {
        popup: 'swal2-custom-popup',
        title: 'swal2-custom-title',
        htmlContainer: 'swal2-custom-content', // sweetalert2 renamed 'content' to 'htmlContainer' in v11
    },
    buttonsStyling: true,
    padding: '0',
    background: 'transparent', // Handled by CSS
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
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        })

        Toast.fire({
            icon,
            title
        })
    }
}

export default swal
