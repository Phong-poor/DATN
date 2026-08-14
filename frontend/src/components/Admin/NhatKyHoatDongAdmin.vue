<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import api from '@/services/api'
import { normalizeImageUrl } from '@/services/urls'

const loading = ref(false)
const loadingAdmins = ref(false)
const logs = ref([])
const activeAdmins = ref([])
const keyword = ref('')
const actionFilter = ref('')
const modelFilter = ref('')

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})

// Các danh mục thao tác phục vụ bộ lọc
const actionTypes = ['Thêm mới', 'Cập nhật', 'Xóa']
const modelTypes = ['Sản phẩm', 'Danh mục', 'Thương hiệu', 'Banner', 'Khuyến mãi', 'Đơn hàng', 'Thành viên']

function fmtDate(iso) {
  if (!iso) return '--'
  return new Date(iso).toLocaleString('vi-VN')
}

function timeAgo(isoString) {
  if (!isoString) return 'Chưa hoạt động'
  const now = new Date()
  const past = new Date(isoString)
  const diffMs = now - past
  const diffMins = Math.floor(diffMs / 60000)
  
  if (diffMins < 1) return 'Vừa xong'
  if (diffMins < 60) return `${diffMins} phút trước`
  
  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return `${diffHours} giờ trước`
  
  return fmtDate(isoString)
}

function getAvatarUrl(avatar) {
  if (!avatar) return null
  return normalizeImageUrl(avatar, null)
}

async function fetchActiveAdmins({ silent = false } = {}) {
  if (!silent) loadingAdmins.value = true
  try {
    await api.post('/user/heartbeat', {}, { showGlobalLoader: false, invalidateCache: false })
    const res = await api.get('/admin/account/active-admins')
    activeAdmins.value = res.data?.data || []
  } catch (e) {
    console.error('Failed to load active admins', e)
  } finally {
    if (!silent) loadingAdmins.value = false
  }
}

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/admin/account/system-activity-logs', {
      params: {
        page,
        keyword: keyword.value,
        action_filter: actionFilter.value,
        model_filter: modelFilter.value
      }
    })
    const responseData = res.data?.data
    logs.value = responseData?.data || []
    pagination.value = {
      current_page: responseData?.current_page || 1,
      last_page: responseData?.last_page || 1,
      total: responseData?.total || 0
    }
  } catch (e) {
    console.error('Failed to load activity logs', e)
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  fetchLogs(1)
}

function resetFilters() {
  keyword.value = ''
  actionFilter.value = ''
  modelFilter.value = ''
  fetchLogs(1)
}

// Hàm phân loại class CSS cho Thao tác để xử lý case-insensitive an toàn
function getActionClass(action) {
  if (!action) return 'action-default'
  const act = action.toLowerCase()
  if (act.includes('thêm') || act.includes('create')) return 'action-create'
  if (act.includes('cập nhật') || act.includes('sửa') || act.includes('update')) return 'action-update'
  if (act.includes('xóa') || act.includes('delete')) return 'action-delete'
  return 'action-default'
}

// Định dạng nâng cao mô tả hoạt động để tạo điểm nhấn trực quan
function formatDescription(desc) {
  if (!desc) return ''
  // 1. In đậm và tạo phong cách riêng cho các thực thể nằm trong ngoặc vuông [Entity]
  let formatted = desc.replace(/\[(.*?)\]/g, '<strong class="highlight-text">[$1]</strong>')
  // 2. Làm đẹp mũi tên chuyển đổi giá trị ➔
  formatted = formatted.replace(/➔/g, '<span class="arrow-indicator">➔</span>')
  return formatted
}

// Thiết lập tự động làm mới trạng thái trực tuyến của Admin sau mỗi 30 giây
let refreshInterval = null
onMounted(() => {
  fetchActiveAdmins()
  fetchLogs(1)
  refreshInterval = setInterval(() => {
    fetchActiveAdmins({ silent: true })
  }, 10000)
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})
</script>

<template>
  <div class="audit-log-container">
    <!-- Phân hệ 1: Quản trị viên đang hoạt động (Nằm ngang phía trên) -->
    <div class="section-card admins-card">
      <div class="card-header">
        <div class="title-with-pulse">
          <div class="pulse-indicator-green"></div>
          <h3>Quản Trị Viên Hệ Thống</h3>
        </div>
        <button class="btn-refresh" @click="fetchActiveAdmins" :disabled="loadingAdmins">
          <span :class="{ 'spinning': loadingAdmins }">↻</span> Cập nhật trạng thái
        </button>
      </div>
      
      <div v-if="loadingAdmins && !activeAdmins.length" class="admins-skeleton">
        Đang quét hệ thống...
      </div>
      
      <div v-else class="admins-horizontal-row">
        <div v-for="admin in activeAdmins" :key="admin.id" class="admin-profile-capsule" :class="{ 'online': admin.is_online }">
          <div class="avatar-wrapper">
            <img v-if="getAvatarUrl(admin.avatar)" :src="getAvatarUrl(admin.avatar)" alt="Avatar" class="admin-avatar" />
            <div v-else class="admin-avatar-fallback">{{ admin.name?.charAt(0).toUpperCase() }}</div>
            <span class="status-dot" :class="admin.is_online ? 'online' : 'offline'"></span>
          </div>
          <div class="admin-details">
            <div class="admin-name-row">
              <h4 class="admin-name">{{ admin.name }}</h4>
              <span class="status-pill" :class="admin.is_online ? 'online' : 'offline'"></span>
            </div>
            <p class="admin-email">{{ admin.email }}</p>
            <span class="admin-status-text" :class="admin.is_online ? 'online' : 'offline'">
              {{ admin.is_online ? 'Đang hoạt động' : timeAgo(admin.last_active_at) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Phân hệ 2: Nhật ký thao tác chi tiết (Nằm bên dưới) -->
    <div class="section-card logs-card">
      <div class="card-header">
        <div class="title-group">
          <h3>Nhật Ký Thao Tác Hệ Thống</h3>
          <span class="total-count">Tổng số bản ghi: <b>{{ pagination.total }}</b></span>
        </div>
      </div>

      <!-- Bộ lọc nâng cao -->
      <div class="filters-bar">
        <div class="search-input-wrapper">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input 
            v-model="keyword" 
            type="text" 
            placeholder="Tìm theo mô tả, tên admin, địa chỉ IP..." 
            class="input-field search-input"
            @keyup.enter="applyFilters"
          />
        </div>
        
        <div class="select-wrapper">
          <select v-model="actionFilter" class="input-field select-field" @change="applyFilters">
            <option value="">-- Tất cả thao tác --</option>
            <option v-for="action in actionTypes" :key="action" :value="action">{{ action }}</option>
          </select>
        </div>

        <div class="select-wrapper">
          <select v-model="modelFilter" class="input-field select-field" @change="applyFilters">
            <option value="">-- Tất cả phân hệ --</option>
            <option v-for="model in modelTypes" :key="model" :value="model">{{ model }}</option>
          </select>
        </div>

        <div class="filter-actions">
          <button class="btn btn-primary" @click="applyFilters">Tìm kiếm</button>
          <button class="btn btn-secondary" @click="resetFilters">Làm mới</button>
        </div>
      </div>

      <!-- Bảng nhật ký nâng cao -->
      <div v-if="loading" class="state-placeholder">
        <div class="spinner"></div> Đang tải nhật ký thao tác...
      </div>
      
      <div v-else-if="!logs.length" class="state-placeholder">
        Không có dữ liệu thao tác nào khớp với bộ lọc.
      </div>
      
      <div v-else class="table-responsive">
        <table class="audit-table">
          <thead>
            <tr>
              <th>Quản trị viên</th>
              <th>Thao tác</th>
              <th>Phân hệ</th>
              <th>Chi tiết nội dung thay đổi</th>
              <th>Mạng (IP / Trình duyệt)</th>
              <th>Thời gian</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id" class="log-row">
              <td class="col-user">
                <div class="log-user-info">
                  <img v-if="getAvatarUrl(log.user?.avatar)" :src="getAvatarUrl(log.user?.avatar)" class="log-user-img" alt="" />
                  <div v-else class="log-user-fallback">{{ log.user?.name?.charAt(0).toUpperCase() }}</div>
                  <div>
                    <div class="log-user-name">{{ log.user?.name || 'Hệ thống' }}</div>
                    <div class="log-user-email">{{ log.user?.email || 'system' }}</div>
                  </div>
                </div>
              </td>
              <td class="col-action">
                <span class="action-tag" :class="getActionClass(log.action)">
                  {{ log.action }}
                </span>
              </td>
              <td class="col-model">
                <span class="model-badge">{{ log.model_name }}</span>
              </td>
              <td class="col-description">
                <p class="log-desc" v-html="formatDescription(log.description)"></p>
              </td>
              <td class="col-network">
                <div class="network-info">
                  <span class="ip-address">{{ log.ip_address || '127.0.0.1' }}</span>
                  <span class="user-agent" :title="log.user_agent">
                    {{ log.user_agent ? (log.user_agent.includes('Chrome') ? 'Google Chrome' : (log.user_agent.includes('Safari') ? 'Safari' : 'Web Browser')) : 'API Client' }}
                  </span>
                </div>
              </td>
              <td class="col-time">
                <span class="time-text">{{ fmtDate(log.created_at) }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Phân trang -->
      <div v-if="pagination.last_page > 1" class="pagination-bar">
        <button 
          :disabled="pagination.current_page === 1" 
          class="btn-page"
          @click="fetchLogs(pagination.current_page - 1)"
        >
          <ChevronLeft :size="16" />
          <span>Trang trước</span>
        </button>
        <span class="page-indicator">
          <span class="page-text">Trang</span>&nbsp;<b class="current-page-num">{{ pagination.current_page }}</b>&nbsp;<span class="page-slash">/</span>&nbsp;<span class="total-page-num">{{ pagination.last_page }}</span>
        </span>
        <button 
          :disabled="pagination.current_page === pagination.last_page" 
          class="btn-page"
          @click="fetchLogs(pagination.current_page + 1)"
        >
          <span>Trang sau</span>
          <ChevronRight :size="16" />
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.audit-log-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
  animation: fadeIn 0.4s ease-out;
}

.section-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.04);
  transition: all 0.3s ease;
}

.section-card:hover {
  box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.06);
  transform: translateY(-2px);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 16px;
}

.title-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.card-header h3 {
  margin: 0;
  font-size: 19px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.total-count {
  font-size: 13px;
  color: #64748b;
}

.total-count b {
  color: #2563eb;
}

.title-with-pulse {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Pulse animation for green online status */
.pulse-indicator-green {
  width: 10px;
  height: 10px;
  background-color: #3b82f6;
  border-radius: 50%;
  box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7);
  animation: pulse-green 2s infinite;
}

@keyframes pulse-green {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 8px rgba(37, 99, 235, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
  }
}

.btn-refresh {
  border: 1px solid #e2e8f0;
  background: #fff;
  padding: 8px 16px;
  border-radius: 12px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
  color: #475569;
}

.btn-refresh:hover {
  background: #f8fafc;
  color: #2563eb;
  border-color: #2563eb;
}

.spinning {
  display: inline-block;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Active Admins Horizontal Dock */
.admins-horizontal-row {
  display: flex;
  gap: 16px;
  overflow-x: auto;
  padding: 4px 4px 14px 4px;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}

.admins-horizontal-row::-webkit-scrollbar {
  height: 7px;
  display: block;
}

.admins-horizontal-row::-webkit-scrollbar-track {
  background: rgba(241, 245, 249, 0.7);
  border-radius: 999px;
}

.admins-horizontal-row::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 999px;
  transition: background 0.2s ease;
}

.admins-horizontal-row::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.admin-profile-capsule {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  border-radius: 999px; /* Tròn dạng Capsule */
  background: rgba(248, 250, 252, 0.75);
  border: 1px solid rgba(226, 232, 240, 0.6);
  flex-shrink: 0;
  min-width: 230px;
  transition: all 0.25s ease;
}

.admin-profile-capsule:hover {
  transform: translateY(-4px);
  background: #fff;
  border-color: rgba(37, 99, 235, 0.25);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}

.admin-profile-capsule.online {
  border-color: rgba(37, 99, 235, 0.25);
  background: rgba(240, 253, 244, 0.75);
}

.admin-profile-capsule.online:hover {
  border-color: rgba(37, 99, 235, 0.45);
  background: #fff;
}

.avatar-wrapper {
  position: relative;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
}

.admin-avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
  border: 2px solid #fff;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.admin-avatar-fallback {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #3b82f6, #3b82f6);
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 15px;
  border: 2px solid #fff;
}

.status-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 11px;
  height: 11px;
  border-radius: 50%;
  border: 2px solid #fff;
}

.status-dot.online {
  background-color: #3b82f6;
}

.status-dot.offline {
  background-color: #94a3b8;
}

.admin-details {
  display: flex;
  flex-direction: column;
  gap: 0px;
  flex: 1;
  min-width: 0;
}

.admin-name-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
}

.admin-name {
  margin: 0;
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status-pill {
  width: 7px;
  height: 7px;
  border-radius: 50%;
}

.status-pill.online {
  background: #3b82f6;
  box-shadow: 0 0 4px #3b82f6;
}

.status-pill.offline {
  background: #cbd5e1;
}

.admin-email {
  margin: 0;
  font-size: 10.5px;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.admin-status-text {
  font-size: 10px;
  font-weight: 600;
  margin-top: 1px;
  width: fit-content;
}

.admin-status-text.online {
  color: #166534;
}

.admin-status-text.offline {
  color: #64748b;
}

.admins-skeleton {
  padding: 16px;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
}

/* Filters Bar */
.filters-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
  background: #f8fafc;
  padding: 16px;
  border-radius: 16px;
  align-items: center;
}

.search-input-wrapper {
  position: relative;
  flex: 1;
  min-width: 240px;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  padding-left: 40px !important;
}

.input-field {
  width: 100%;
  border: 1px solid #cbd5e1;
  background: #fff;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 14px;
  color: #1e293b;
  outline: none;
  transition: all 0.2s ease;
}

.input-field:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.select-wrapper {
  width: 170px;
}

.filter-actions {
  display: flex;
  gap: 8px;
}

.btn {
  padding: 10px 16px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.btn-primary {
  background: #2563eb;
  color: #fff;
}

.btn-primary:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #e2e8f0;
  color: #475569;
}

.btn-secondary:hover {
  background: #cbd5e1;
  transform: translateY(-1px);
}

/* Table Design */
.table-responsive {
  width: 100%;
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
}

.audit-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 14px;
}

.audit-table th {
  background: #f8fafc;
  padding: 14px 18px;
  font-weight: 700;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
  letter-spacing: 0.02em;
}

.audit-table td {
  padding: 14px 18px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.audit-table th:nth-child(2),
.audit-table td.col-action {
  width: 130px;
  min-width: 130px;
}

.audit-table th:nth-child(3),
.audit-table td.col-model {
  width: 145px;
  min-width: 145px;
}

.audit-table td.col-action,
.audit-table td.col-model {
  white-space: nowrap;
}

.log-row {
  transition: background-color 0.2s ease;
}

.log-row:hover {
  background: rgba(248, 250, 252, 0.6);
}

/* Col Admin */
.log-user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.log-user-img {
  width: 36px;
  height: 36px;
  object-fit: cover;
  border-radius: 50%;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.log-user-fallback {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 13px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.log-user-name {
  font-weight: 700;
  color: #1e293b;
  font-size: 13.5px;
}

:global(.admin-layout.dark) .log-user-name,
:global(.admin-layout.theme-dark) .log-user-name,
:global(html[data-admin-theme='dark']) .log-user-name,
:global(.theme-dark) .log-user-name,
:global(.dark) .log-user-name {
  color: #f8fafc !important;
}

.log-user-email {
  font-size: 11px;
  color: #64748b;
}

/* Col Action Tags (normalized case-insensitive styling) */
.action-tag {
  font-size: 11px;
  font-weight: 800;
  padding: 5px 12px;
  border-radius: 8px;
  text-transform: capitalize;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 96px;
  white-space: nowrap;
  letter-spacing: 0.5px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.action-tag.action-create {
  background: #dcfce7;
  color: #1d4ed8;
}

.action-tag.action-update {
  background: #e0f2fe;
  color: #0369a1;
}

.action-tag.action-delete {
  background: #fee2e2;
  color: #b91c1c;
}

.action-tag.action-default {
  background: #f1f5f9;
  color: #475569;
}

/* Col Model Badge */
.model-badge {
  background: rgba(241, 245, 249, 0.6);
  color: #475569;
  font-size: 11px;
  font-weight: 700;
  padding: 5px 12px;
  border-radius: 8px;
  border: 1px solid rgba(226, 232, 240, 0.7);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 108px;
  white-space: nowrap;
  letter-spacing: 0.5px;
}

/* Col Description (Premium styling for highlights and arrows) */
.log-desc {
  margin: 0;
  color: #334155;
  font-size: 13.5px;
  line-height: 1.55;
  max-width: 440px;
  word-wrap: break-word;
}

/* Dùng v-html styling trong scoped CSS */
:deep(.highlight-text) {
  color: #1e293b;
  font-weight: 750;
  background: rgba(239, 246, 255, 0.8);
  border-radius: 4px;
  padding: 1px 5px;
  border: 1px solid rgba(191, 219, 254, 0.3);
  font-size: 12.5px;
}

:deep(.arrow-indicator) {
  display: inline-block;
  margin: 0 4px;
  color: #3b82f6;
  font-weight: 800;
  font-size: 14px;
  transform: scale(1.1);
  text-shadow: 0 0 10px rgba(59, 130, 246, 0.2);
}

/* Col Network */
.network-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.ip-address {
  font-weight: 700;
  color: #475569;
  font-family: 'Courier New', Courier, monospace;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 6px;
  font-size: 12px;
  width: fit-content;
  border: 1px solid #e2e8f0;
}

.user-agent {
  font-size: 11px;
  color: #94a3b8;
  cursor: help;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 120px;
}

/* Col Time */
.time-text {
  font-size: 13px;
  color: #64748b;
  white-space: nowrap;
  font-weight: 500;
}

/* Pagination Bar Styling */
.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-top: 24px;
  padding: 14px 24px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.btn-page {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  padding: 9px 18px;
  border-radius: 10px;
  cursor: pointer;
  font-size: 13.5px;
  font-weight: 600;
  color: #334155;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.btn-page:hover:not(:disabled) {
  background: #eff6ff;
  color: #2563eb;
  border-color: #93c5fd;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
}

.btn-page:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  background: #f1f5f9;
  border-color: #e2e8f0;
  color: #94a3b8;
  box-shadow: none;
}

.page-indicator {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: #475569;
  font-weight: 500;
}

.page-text {
  color: #64748b;
  font-weight: 500;
}

.current-page-num {
  color: #1d4ed8;
  font-weight: 800;
  font-size: 15px;
  padding: 2px 8px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 6px;
}

.page-slash {
  color: #94a3b8;
  font-weight: 600;
  margin: 0 4px;
}

.total-page-num {
  color: #0f172a;
  font-weight: 700;
  font-size: 15px;
}

.state-placeholder {
  padding: 40px;
  text-align: center;
  color: #64748b;
  font-size: 15px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #cbd5e1;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ============================= DARK THEME STYLES ============================= */
:global(.admin-layout.dark) .section-card,
:global(.admin-layout.theme-dark) .section-card,
:global(html[data-admin-theme='dark']) .section-card {
  background: rgba(24, 24, 27, 0.85);
  border-color: rgba(63, 63, 70, 0.7);
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
}

:global(.admin-layout.dark) .card-header,
:global(.admin-layout.theme-dark) .card-header,
:global(html[data-admin-theme='dark']) .card-header {
  border-bottom-color: rgba(63, 63, 70, 0.6);
}

:global(.admin-layout.dark) .card-header h3,
:global(.admin-layout.theme-dark) .card-header h3,
:global(html[data-admin-theme='dark']) .card-header h3 {
  color: #f4f4f5;
}

:global(.admin-layout.dark) .total-count,
:global(.admin-layout.theme-dark) .total-count,
:global(html[data-admin-theme='dark']) .total-count {
  color: #a1a1aa;
}

:global(.admin-layout.dark) .btn-refresh,
:global(.admin-layout.theme-dark) .btn-refresh,
:global(html[data-admin-theme='dark']) .btn-refresh {
  background: #27272a;
  border-color: #3f3f46;
  color: #e4e4e7;
}

:global(.admin-layout.dark) .btn-refresh:hover,
:global(.admin-layout.theme-dark) .btn-refresh:hover,
:global(html[data-admin-theme='dark']) .btn-refresh:hover {
  background: #3f3f46;
  color: #60a5fa;
  border-color: #60a5fa;
}

:global(.admin-layout.dark) .admins-horizontal-row,
:global(.admin-layout.theme-dark) .admins-horizontal-row,
:global(html[data-admin-theme='dark']) .admins-horizontal-row {
  scrollbar-color: #475569 transparent;
}

:global(.admin-layout.dark) .admins-horizontal-row::-webkit-scrollbar-track,
:global(.admin-layout.theme-dark) .admins-horizontal-row::-webkit-scrollbar-track,
:global(html[data-admin-theme='dark']) .admins-horizontal-row::-webkit-scrollbar-track {
  background: rgba(24, 24, 27, 0.6);
}

:global(.admin-layout.dark) .admins-horizontal-row::-webkit-scrollbar-thumb,
:global(.admin-layout.theme-dark) .admins-horizontal-row::-webkit-scrollbar-thumb,
:global(html[data-admin-theme='dark']) .admins-horizontal-row::-webkit-scrollbar-thumb {
  background: #3f3f46;
}

:global(.admin-layout.dark) .admins-horizontal-row::-webkit-scrollbar-thumb:hover,
:global(.admin-layout.theme-dark) .admins-horizontal-row::-webkit-scrollbar-thumb:hover,
:global(html[data-admin-theme='dark']) .admins-horizontal-row::-webkit-scrollbar-thumb:hover {
  background: #52525b;
}

:global(.admin-layout.dark) .admin-profile-capsule,
:global(.admin-layout.theme-dark) .admin-profile-capsule,
:global(html[data-admin-theme='dark']) .admin-profile-capsule {
  background: rgba(39, 39, 42, 0.75);
  border-color: rgba(63, 63, 70, 0.7);
}

:global(.admin-layout.dark) .admin-profile-capsule:hover,
:global(.admin-layout.theme-dark) .admin-profile-capsule:hover,
:global(html[data-admin-theme='dark']) .admin-profile-capsule:hover {
  background: #27272a;
  border-color: rgba(59, 130, 246, 0.5);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

:global(.admin-layout.dark) .admin-profile-capsule.online,
:global(.admin-layout.theme-dark) .admin-profile-capsule.online,
:global(html[data-admin-theme='dark']) .admin-profile-capsule.online {
  background: rgba(20, 83, 45, 0.3);
  border-color: rgba(34, 197, 94, 0.4);
}

:global(.admin-layout.dark) .admin-profile-capsule.online:hover,
:global(.admin-layout.theme-dark) .admin-profile-capsule.online:hover,
:global(html[data-admin-theme='dark']) .admin-profile-capsule.online:hover {
  background: rgba(20, 83, 45, 0.5);
  border-color: rgba(34, 197, 94, 0.7);
}

:global(.admin-layout.dark) .admin-name,
:global(.admin-layout.theme-dark) .admin-name,
:global(html[data-admin-theme='dark']) .admin-name {
  color: #f4f4f5;
}

:global(.admin-layout.dark) .admin-email,
:global(.admin-layout.theme-dark) .admin-email,
:global(html[data-admin-theme='dark']) .admin-email {
  color: #a1a1aa;
}

:global(.admin-layout.dark) .admin-status-text.online,
:global(.admin-layout.theme-dark) .admin-status-text.online,
:global(html[data-admin-theme='dark']) .admin-status-text.online {
  color: #4ade80;
}

:global(.admin-layout.dark) .admin-status-text.offline,
:global(.admin-layout.theme-dark) .admin-status-text.offline,
:global(html[data-admin-theme='dark']) .admin-status-text.offline {
  color: #a1a1aa;
}

:global(.admin-layout.dark) .filters-bar,
:global(.admin-layout.theme-dark) .filters-bar,
:global(html[data-admin-theme='dark']) .filters-bar {
  background: rgba(39, 39, 42, 0.6);
}

:global(.admin-layout.dark) .input-field,
:global(.admin-layout.theme-dark) .input-field,
:global(html[data-admin-theme='dark']) .input-field {
  background: #18181b;
  border-color: #3f3f46;
  color: #f4f4f5;
}

:global(.admin-layout.dark) .input-field option,
:global(.admin-layout.theme-dark) .input-field option,
:global(html[data-admin-theme='dark']) .input-field option {
  background: #18181b;
  color: #f4f4f5;
}

:global(.admin-layout.dark) .btn-secondary,
:global(.admin-layout.theme-dark) .btn-secondary,
:global(html[data-admin-theme='dark']) .btn-secondary {
  background: #3f3f46;
  color: #e4e4e7;
}

:global(.admin-layout.dark) .btn-secondary:hover,
:global(.admin-layout.theme-dark) .btn-secondary:hover,
:global(html[data-admin-theme='dark']) .btn-secondary:hover {
  background: #52525b;
}

:global(.admin-layout.dark) .table-responsive,
:global(.admin-layout.theme-dark) .table-responsive,
:global(html[data-admin-theme='dark']) .table-responsive {
  border-color: #3f3f46;
}

:global(.admin-layout.dark) .audit-table th,
:global(.admin-layout.theme-dark) .audit-table th,
:global(html[data-admin-theme='dark']) .audit-table th {
  background: #27272a;
  color: #d4d4d8;
  border-bottom-color: #3f3f46;
}

:global(.admin-layout.dark) .audit-table td,
:global(.admin-layout.theme-dark) .audit-table td,
:global(html[data-admin-theme='dark']) .audit-table td {
  border-bottom-color: #27272a;
}

:global(.admin-layout.dark) .log-row:hover,
:global(.admin-layout.theme-dark) .log-row:hover,
:global(html[data-admin-theme='dark']) .log-row:hover {
  background: rgba(39, 39, 42, 0.7);
}

:global(.admin-layout.dark) .log-user-name,
:global(.admin-layout.theme-dark) .log-user-name,
:global(html[data-admin-theme='dark']) .log-user-name {
  color: #f4f4f5 !important;
}

:global(.admin-layout.dark) .log-user-email,
:global(.admin-layout.theme-dark) .log-user-email,
:global(html[data-admin-theme='dark']) .log-user-email {
  color: #a1a1aa;
}

:global(.admin-layout.dark) .model-badge,
:global(.admin-layout.theme-dark) .model-badge,
:global(html[data-admin-theme='dark']) .model-badge {
  background: rgba(39, 39, 42, 0.8);
  color: #d4d4d8;
  border-color: #3f3f46;
}

:global(.admin-layout.dark) .log-desc,
:global(.admin-layout.theme-dark) .log-desc,
:global(html[data-admin-theme='dark']) .log-desc {
  color: #e4e4e7;
}

:global(.admin-layout.dark) :deep(.highlight-text),
:global(.admin-layout.theme-dark) :deep(.highlight-text),
:global(html[data-admin-theme='dark']) :deep(.highlight-text) {
  color: #93c5fd;
  background: rgba(30, 58, 138, 0.6);
  border-color: rgba(59, 130, 246, 0.4);
}

:global(.admin-layout.dark) :deep(.arrow-indicator),
:global(.admin-layout.theme-dark) :deep(.arrow-indicator),
:global(html[data-admin-theme='dark']) :deep(.arrow-indicator) {
  color: #60a5fa;
}

:global(.admin-layout.dark) .ip-address,
:global(.admin-layout.theme-dark) .ip-address,
:global(html[data-admin-theme='dark']) .ip-address {
  background: #27272a;
  color: #d4d4d8;
  border-color: #3f3f46;
}

:global(.admin-layout.dark) .user-agent,
:global(.admin-layout.theme-dark) .user-agent,
:global(html[data-admin-theme='dark']) .user-agent {
  color: #a1a1aa;
}

:global(.admin-layout.dark) .time-text,
:global(.admin-layout.theme-dark) .time-text,
:global(html[data-admin-theme='dark']) .time-text {
  color: #d4d4d8;
}

:global(.admin-layout.dark) .pagination-bar,
:global(.admin-layout.theme-dark) .pagination-bar,
:global(html[data-admin-theme='dark']) .pagination-bar {
  background: #1e293b !important;
  border-color: #334155 !important;
}

:global(.admin-layout.dark) .btn-page,
:global(.admin-layout.theme-dark) .btn-page,
:global(html[data-admin-theme='dark']) .btn-page {
  background: #0f172a !important;
  border-color: #334155 !important;
  color: #f8fafc !important;
  box-shadow: none !important;
}

:global(.admin-layout.dark) .btn-page:hover:not(:disabled),
:global(.admin-layout.theme-dark) .btn-page:hover:not(:disabled),
:global(html[data-admin-theme='dark']) .btn-page:hover:not(:disabled) {
  background: #2563eb !important;
  color: #ffffff !important;
  border-color: #3b82f6 !important;
  transform: translateY(-1px);
}

:global(.admin-layout.dark) .btn-page:disabled,
:global(.admin-layout.theme-dark) .btn-page:disabled,
:global(html[data-admin-theme='dark']) .btn-page:disabled {
  background: #0f172a !important;
  border-color: #1e293b !important;
  color: #475569 !important;
  cursor: not-allowed !important;
}

:global(.admin-layout.dark) .page-indicator,
:global(.admin-layout.theme-dark) .page-indicator,
:global(html[data-admin-theme='dark']) .page-indicator {
  background: rgba(255, 255, 255, 0.05) !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: #cbd5e1 !important;
}

:global(.admin-layout.dark) .page-text,
:global(.admin-layout.theme-dark) .page-text,
:global(html[data-admin-theme='dark']) .page-text {
  color: #94a3b8 !important;
}

:global(.admin-layout.dark) .current-page-num,
:global(.admin-layout.theme-dark) .current-page-num,
:global(html[data-admin-theme='dark']) .current-page-num {
  color: #60a5fa !important;
  background: rgba(59, 130, 246, 0.25) !important;
  font-weight: 800 !important;
}

:global(.admin-layout.dark) .page-slash,
:global(.admin-layout.theme-dark) .page-slash,
:global(html[data-admin-theme='dark']) .page-slash {
  color: #64748b !important;
}

:global(.admin-layout.dark) .total-page-num,
:global(.admin-layout.theme-dark) .total-page-num,
:global(html[data-admin-theme='dark']) .total-page-num {
  color: #ffffff !important;
  font-weight: 700 !important;
}

:global(.admin-layout.dark) .admin-name,
:global(.admin-layout.theme-dark) .admin-name,
:global(html[data-admin-theme='dark']) .admin-name,
:global(.admin-layout.dark) .log-user-name,
:global(.admin-layout.theme-dark) .log-user-name,
:global(html[data-admin-theme='dark']) .log-user-name {
  color: #f8fafc !important;
  font-weight: 700 !important;
}

:global(.admin-layout.dark) .admin-email,
:global(.admin-layout.theme-dark) .admin-email,
:global(html[data-admin-theme='dark']) .admin-email,
:global(.admin-layout.dark) .log-user-email,
:global(.admin-layout.theme-dark) .log-user-email,
:global(html[data-admin-theme='dark']) .log-user-email {
  color: #94a3b8 !important;
}

:global(.admin-layout.dark) .admin-profile-capsule,
:global(.admin-layout.theme-dark) .admin-profile-capsule,
:global(html[data-admin-theme='dark']) .admin-profile-capsule {
  background: #0f172a !important;
  border-color: #334155 !important;
}

:global(.admin-layout.dark) .admin-profile-capsule.online,
:global(.admin-layout.theme-dark) .admin-profile-capsule.online,
:global(html[data-admin-theme='dark']) .admin-profile-capsule.online {
  background: rgba(30, 41, 59, 0.8) !important;
  border-color: rgba(59, 130, 246, 0.4) !important;
}
</style>
