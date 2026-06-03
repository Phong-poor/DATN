<template>
  <!-- Native floating button hidden, handled by FloatingContactMenu -->
  <div class="zalo-widget-hidden"></div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'

const zaloUrl = 'https://zalo.me/0397972161'
let swalModulePromise = null

const getSwal = async () => {
  if (!swalModulePromise) swalModulePromise = import('../../services/swal')
  return (await swalModulePromise).default
}

const openZaloConfirm = async () => {
  const swal = await getSwal()
  const confirmed = await swal.confirm(
    'Mở Zalo?',
    'Bạn có muốn chuyển sang Zalo để chat với cửa hàng không?',
    'Đồng ý',
    'Hủy'
  )

  if (confirmed) {
    window.open(zaloUrl, '_blank', 'noopener,noreferrer')
  }
}

onMounted(() => {
  window.addEventListener('open-zalo', openZaloConfirm)
})

onUnmounted(() => {
  window.removeEventListener('open-zalo', openZaloConfirm)
})
</script>

<style scoped>
.zalo-widget {
  position: fixed;
  right: 92px;
  bottom: 34px;
  z-index: 9998;
}

.zalo-button {
  width: 44px;
  height: 44px;
  border: 0;
  padding: 0;
  border-radius: 50%;
  background: #0b84ff;
  color: #fff;
  box-shadow: 0 10px 22px rgba(0, 104, 255, 0.24);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  position: relative;
}

.zalo-button::before {
  content: '';
  position: absolute;
  inset: -3px;
  border-radius: 50%;
  background: rgba(0, 104, 255, 0.14);
  z-index: -1;
  animation: zalo-pulse 2.8s ease-out infinite;
}

.zalo-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 26px rgba(0, 104, 255, 0.34);
}

.zalo-label {
  font-size: 11px;
  line-height: 1;
  font-weight: 800;
  letter-spacing: 0;
}

:global(.swal2-custom-popup) {
  background: rgba(255, 255, 255, 0.86) !important;
  backdrop-filter: blur(18px) saturate(180%) !important;
  -webkit-backdrop-filter: blur(18px) saturate(180%) !important;
  border: 1px solid rgba(255, 255, 255, 0.35) !important;
  border-radius: 24px !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
  padding: 2.5rem !important;
  text-align: center !important;
  align-items: center !important;
  font-family: Arial, Helvetica, sans-serif !important;
}

:global(.swal2-custom-title) {
  width: 100% !important;
  margin: 0 auto 0.75rem !important;
  padding: 0 !important;
  color: #0f172a !important;
  text-align: center !important;
  font-size: 1.65rem !important;
  font-weight: 700 !important;
}

:global(.swal2-custom-content) {
  width: 100% !important;
  margin: 0 auto !important;
  padding: 0 !important;
  color: #475569 !important;
  text-align: center !important;
  font-size: 1rem !important;
  line-height: 1.6 !important;
}

:global(.swal2-icon) {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  margin: 0 auto 1.5rem !important;
  border-width: 3px !important;
}

:global(.swal2-actions) {
  width: 100% !important;
  justify-content: center !important;
  gap: 12px !important;
  margin-top: 2rem !important;
}

:global(.swal2-confirm),
:global(.swal2-cancel) {
  border: 0 !important;
  border-radius: 14px !important;
  padding: 12px 28px !important;
  font-size: 0.95rem !important;
  font-weight: 600 !important;
  text-transform: none !important;
  box-shadow: 0 4px 8px rgba(15, 23, 42, 0.12) !important;
}

:global(.swal2-cancel) {
  background: #f1f5f9 !important;
  color: #475569 !important;
}

@keyframes zalo-pulse {
  0% {
    transform: scale(0.96);
    opacity: 0.65;
  }

  100% {
    transform: scale(1.32);
    opacity: 0;
  }
}

@media (max-width: 640px) {
  .zalo-widget {
    right: 82px;
    bottom: 24px;
  }
}
</style>
