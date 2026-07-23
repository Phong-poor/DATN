<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import {
  isOnline,
  isSyncing,
  offlineQueue,
  syncQueue,
  restoreRequestToForm,
  deleteQueueItem
} from '@/services/offlineSync'

// Trạng thái hiển thị giao diện
const isPanelOpen = ref(false)
const showReconnectBanner = ref(false)
let bannerTimeout = null

// Lắng nghe sự kiện kết nối lại để hiển thị banner tạm thời
const handleOnlineEvent = () => {
  showReconnectBanner.value = true
  if (bannerTimeout) clearTimeout(bannerTimeout)
  bannerTimeout = setTimeout(() => {
    showReconnectBanner.value = false
  }, 4000)
}

onMounted(() => {
  window.addEventListener('online', handleOnlineEvent)
})

onUnmounted(() => {
  window.removeEventListener('online', handleOnlineEvent)
  if (bannerTimeout) clearTimeout(bannerTimeout)
})

// Kiểm tra xem trong hàng đợi có mục nào bị lỗi (failed) không
const hasFailedItems = () => {
  return offlineQueue.value.some(item => item.status === 'failed')
}

// Format thời gian thân thiện
const formatTime = (ts) => {
  if (!ts) return ''
  const date = new Date(ts)
  return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

// Bấm nút đóng/mở panel
const togglePanel = () => {
  isPanelOpen.value = !isPanelOpen.value
}

// Nhấn nút đồng bộ thủ công
const handleManualSync = async () => {
  if (!isOnline.value) {
    alert('Vẫn đang ngoại tuyến. Vui lòng kết nối mạng để đồng bộ!')
    return
  }
  await syncQueue()
}
</script>

<template>
  <div class="offline-sync-container">
    <!-- --- 1. BANNER TRẠNG THÁI MẠNG (ĐẦU TRANG) --- -->
    <transition name="slide-down">
      <div v-if="!isOnline" class="network-banner offline">
        <div class="banner-content">
          <svg class="banner-icon animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="1" y1="1" x2="23" y2="23"></line>
            <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.5"></path>
            <path d="M5 12.5a10.94 10.94 0 0 1 5.83-2.84"></path>
            <path d="M12 12.5a4.25 4.25 0 0 1 2-1"></path>
            <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
            <line x1="12" y1="20" x2="12.01" y2="20"></line>
          </svg>
          <span>Bạn đang ngoại tuyến. Hệ thống tự động chuyển sang chế độ lưu trữ cục bộ.</span>
        </div>
      </div>
      <div v-else-if="showReconnectBanner" class="network-banner online">
        <div class="banner-content">
          <svg class="banner-icon rotate-in" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 12.5a10.87 10.87 0 0 1 14 0"></path>
            <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
            <line x1="12" y1="20" x2="12.01" y2="20"></line>
          </svg>
          <span>Đã kết nối lại Internet. Đang tiến hành đồng bộ dữ liệu...</span>
        </div>
      </div>
    </transition>

    <!-- --- 2. NÚT TRÒN NỔI (GÓC DƯỚI BÊN PHẢI) --- -->
    <transition name="fade-in">
      <button 
        v-if="offlineQueue.length > 0 || !isOnline" 
        class="sync-floating-pill" 
        :class="{ 'has-error': hasFailedItems(), 'is-syncing': isSyncing, 'is-offline-empty': !isOnline && offlineQueue.length === 0 }"
        @click="togglePanel"
        title="Quản lý hàng đợi đồng bộ"
        type="button"
      >
        <div class="pill-icon-wrapper">
          <svg v-if="isSyncing" class="icon-sync animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
          </svg>
          <svg v-else class="icon-db" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
            <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"></path>
          </svg>
        </div>
        <span v-if="offlineQueue.length > 0" class="badge">{{ offlineQueue.length }}</span>
      </button>
    </transition>

    <!-- --- 3. BẢNG CHI TIẾT HÀNG ĐỢI (SLIDE UP PANEL) --- -->
    <transition name="slide-up">
      <div v-if="isPanelOpen && (offlineQueue.length > 0 || !isOnline)" class="sync-dashboard-panel">
        <div class="panel-header">
          <div class="header-title">
            <svg class="header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
              <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
              <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"></path>
            </svg>
            <div>
              <h3>Hàng đợi ngoại tuyến</h3>
              <p>Có {{ offlineQueue.length }} yêu cầu chưa được lưu lên máy chủ</p>
            </div>
          </div>
          <button class="close-btn" @click="togglePanel" type="button" aria-label="Đóng panel">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="panel-body">
          <div v-if="offlineQueue.length === 0" class="empty-queue-state">
            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <p>Hàng đợi trống</p>
            <span>Các thay đổi khi thêm/sửa form trong trạng thái ngoại tuyến sẽ được lưu tạm tại đây.</span>
          </div>
          <div v-else class="queue-list">
            <div 
              v-for="item in offlineQueue" 
              :key="item.id" 
              class="queue-item"
              :class="item.status"
            >
              <div class="item-info">
                <div class="item-meta">
                  <span class="method-badge" :class="item.method.toLowerCase()">{{ item.method }}</span>
                  <span class="time-stamp">{{ formatTime(item.timestamp) }}</span>
                </div>
                <div class="form-name">{{ item.formName }}</div>
                <div class="route-path">Đường dẫn: <code>{{ item.route }}</code></div>
                
                <!-- Báo lỗi cụ thể từ Server -->
                <div v-if="item.status === 'failed' && item.errorMessage" class="error-msg">
                  <strong>Lỗi:</strong> {{ item.errorMessage }}
                </div>
              </div>

              <div class="item-actions">
                <!-- Nút khôi phục vào form để điền tiếp -->
                <button 
                  v-if="item.status === 'failed'"
                  class="action-btn restore" 
                  @click="restoreRequestToForm(item)"
                  title="Khôi phục dữ liệu lại vào form để sửa đổi"
                  type="button"
                >
                  <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 2v6h6M21 22v-6h-6"></path>
                    <path d="M21 16c-1.38-2.62-4.19-4.5-7.5-4.5A7.5 7.5 0 0 0 6 19"></path>
                    <path d="M3 8c1.38 2.62 4.19 4.5 7.5 4.5a7.5 7.5 0 0 0 7.5-7.5"></path>
                  </svg>
                  <span>Sửa tiếp</span>
                </button>

                <!-- Nút xóa yêu cầu -->
                <button 
                  class="action-btn delete" 
                  @click="deleteQueueItem(item.id)"
                  title="Hủy bỏ yêu cầu này"
                  type="button"
                >
                  <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="panel-footer">
          <span class="network-status" :class="{ online: isOnline }">
            <span class="status-dot"></span>
            {{ isOnline ? 'Trực tuyến' : 'Ngoại tuyến' }}
          </span>
          <button 
            class="sync-now-btn" 
            :disabled="isSyncing || !isOnline"
            @click="handleManualSync"
            type="button"
          >
            <span v-if="isSyncing" class="spinner"></span>
            {{ isSyncing ? 'Đang đồng bộ...' : 'Đồng bộ ngay' }}
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.offline-sync-container {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  z-index: 99999;
  position: relative;
}

/* --- 1. BANNER TRẠNG THÁI MẠNG --- */
.network-banner {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13.5px;
  font-weight: 600;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  z-index: 100000;
  padding: 0 20px;
  text-align: center;
}
.network-banner.offline {
  background: #fef3c7;
  color: #92400e;
  border-bottom: 2px solid #f59e0b;
}
.network-banner.online {
  background: #dcfce7;
  color: #166534;
  border-bottom: 2px solid #22c55e;
}
.banner-content {
  display: flex;
  align-items: center;
  gap: 10px;
}
.banner-icon {
  width: 18px;
  height: 18px;
}

/* --- 2. NÚT TRÒN NỔI --- */
.sync-floating-pill {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 58px;
  height: 58px;
  border-radius: 50%;
  background: #2563eb;
  color: #ffffff;
  border: none;
  box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.sync-floating-pill:hover {
  transform: translateY(-4px) scale(1.05);
  box-shadow: 0 6px 24px rgba(37, 99, 235, 0.5);
}
.sync-floating-pill.has-error {
  background: #dc2626;
  box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4);
}
.sync-floating-pill.has-error:hover {
  box-shadow: 0 6px 24px rgba(220, 38, 38, 0.5);
}
.pill-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
}
.pill-icon-wrapper svg {
  width: 24px;
  height: 24px;
}
.sync-floating-pill .badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #f59e0b;
  color: #ffffff;
  font-size: 11px;
  font-weight: 800;
  min-width: 20px;
  height: 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ffffff;
  padding: 0 4px;
}

/* --- 3. BẢNG CHI TIẾT HÀNG ĐỢI --- */
.sync-dashboard-panel {
  position: fixed;
  bottom: 96px;
  right: 24px;
  width: 380px;
  max-width: calc(100vw - 48px);
  max-height: 520px;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
  z-index: 9998;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.panel-header {
  padding: 16px 20px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.7);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fafafa;
}
.header-title {
  display: flex;
  align-items: center;
  gap: 12px;
}
.header-icon {
  width: 20px;
  height: 20px;
  color: #475569;
}
.header-title h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}
.header-title p {
  margin: 2px 0 0;
  font-size: 11.5px;
  color: #64748b;
  font-weight: 500;
}
.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #94a3b8;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background-color 0.2s;
}
.close-btn:hover {
  background: #f1f5f9;
  color: #475569;
}
.close-btn svg {
  width: 18px;
  height: 18px;
}

.panel-body {
  padding: 16px;
  overflow-y: auto;
  flex: 1;
}
.queue-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.queue-item {
  border-radius: 14px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
  transition: all 0.2s;
}
.queue-item.syncing {
  border-color: #93c5fd;
  background: #f0f7ff;
}
.queue-item.failed {
  border-color: #fca5a5;
  background: #fff5f5;
}

.item-info {
  flex: 1;
  min-width: 0;
}
.item-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}
.method-badge {
  font-size: 9px;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 999px;
  text-transform: uppercase;
}
.method-badge.post { background: #dcfce7; color: #15803d; }
.method-badge.put { background: #dbeafe; color: #1d4ed8; }
.method-badge.delete { background: #fee2e2; color: #b91c1c; }
.method-badge.patch { background: #fef3c7; color: #b45309; }

.time-stamp {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 500;
}
.form-name {
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
  word-break: break-word;
}
.route-path {
  font-size: 11px;
  color: #64748b;
  word-break: break-all;
}
.route-path code {
  background: #f1f5f9;
  padding: 1px 4px;
  border-radius: 4px;
  font-size: 10px;
  color: #334155;
}

.error-msg {
  margin-top: 8px;
  font-size: 11.5px;
  color: #dc2626;
  background: rgba(220, 38, 38, 0.05);
  border-left: 3px solid #dc2626;
  padding: 6px 8px;
  border-radius: 0 6px 6px 0;
  word-break: break-word;
}

.item-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.action-btn {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  transition: all 0.2s;
}
.action-btn.restore {
  border-color: #2563eb;
  color: #2563eb;
  font-size: 11.5px;
  font-weight: 700;
  padding: 6px 10px;
}
.action-btn.restore:hover {
  background: #2563eb;
  color: #ffffff;
}
.action-btn.delete {
  color: #64748b;
}
.action-btn.delete:hover {
  border-color: #dc2626;
  color: #dc2626;
  background: #fff5f5;
}
.action-icon {
  width: 14px;
  height: 14px;
}

.panel-footer {
  padding: 14px 20px;
  border-top: 1px solid rgba(226, 232, 240, 0.7);
  background: #fafafa;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.network-status {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}
.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #cbd5e1;
}
.network-status.online .status-dot {
  background: #22c55e;
  box-shadow: 0 0 8px #22c55e;
}
.sync-now-btn {
  background: #0f172a;
  color: #ffffff;
  border: none;
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background-color 0.2s;
}
.sync-now-btn:hover:not(:disabled) {
  background: #1e293b;
}
.sync-now-btn:disabled {
  background: #cbd5e1;
  color: #94a3b8;
  cursor: not-allowed;
}

.spinner {
  width: 12px;
  height: 12px;
  border: 2px solid #ffffff;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

/* --- ANIMATIONS & TRANSITIONS --- */
@keyframes spin {
  to { transform: rotate(360deg); }
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}
.animate-spin {
  animation: spin 1s linear infinite;
}
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-down-enter-from,
.slide-down-leave-to {
  transform: translateY(-100%);
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(30px);
  opacity: 0;
}

.fade-in-enter-active,
.fade-in-leave-active {
  transition: opacity 0.3s ease;
}
.fade-in-enter-from,
.fade-in-leave-to {
  opacity: 0;
}

/* Custom styles for offline empty state */
.sync-floating-pill.is-offline-empty {
  background: #f59e0b;
  box-shadow: 0 4px 20px rgba(245, 158, 11, 0.4);
}
.sync-floating-pill.is-offline-empty:hover {
  background: #d97706;
  box-shadow: 0 6px 24px rgba(245, 158, 11, 0.5);
}

.empty-queue-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 30px 20px;
  color: #64748b;
}
.empty-queue-state .empty-icon {
  width: 40px;
  height: 40px;
  color: #94a3b8;
  margin-bottom: 12px;
}
.empty-queue-state p {
  margin: 0 0 6px;
  font-size: 14px;
  font-weight: 700;
  color: #334155;
}
.empty-queue-state span {
  font-size: 12px;
  color: #94a3b8;
  line-height: 1.4;
}
</style>
