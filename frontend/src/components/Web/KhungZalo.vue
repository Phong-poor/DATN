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
