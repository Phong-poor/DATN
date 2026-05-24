<template>
  <div class="zalo-widget">
    <button
      type="button"
      class="zalo-button"
      @click="openZaloConfirm"
    >
      <img :src="zaloLogo" alt="Zalo" class="zalo-logo" />
    </button>
  </div>
</template>

<script setup>
import swal from '../../services/swal'

const zaloUrl = 'https://zalo.me/0397972161'
const zaloLogo = 'http://localhost/DATN/backend/storage/app/public/uploads/avatar/zalologo.png'

const openZaloConfirm = async () => {
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
</script>

<style scoped>
.zalo-widget {
  position: fixed;
  right: 110px;
  top: 725px;
  z-index: 10000;
}

.zalo-button {
  width: 60px;
  height: 60px;
  border: 0;
  padding: 0;
  border-radius: 50%;
  background: transparent;
  box-shadow: 0 8px 24px rgba(0, 104, 255, 0.28);
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
  inset: -5px;
  border-radius: 50%;
  background: rgba(0, 104, 255, 0.22);
  z-index: -1;
  animation: zalo-pulse 2s ease-out infinite;
}

.zalo-button:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 12px 30px rgba(0, 104, 255, 0.45);
}

.zalo-logo {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  display: block;
  box-shadow: 0 2px 8px rgba(0, 104, 255, 0.2);
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
</style>
