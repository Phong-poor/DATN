<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
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

async function fetchActiveAdmins() {
  loadingAdmins.value = true
  try {
    const res = await api.get('/admin/account/active-admins')
    activeAdmins.value = res.data?.data || []
  } catch (e) {
    console.error('Failed to load active admins', e)
  } finally {
    loadingAdmins.value = false
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
    fetchActiveAdmins()
  }, 30000)
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
            <img v-if="getAvatarUrl(admin.anhdaidien)" :src="getAvatarUrl(admin.anhdaidien)" alt="Avatar" class="admin-avatar" />
            <div v-else class="admin-avatar-fallback">{{ admin.ten?.charAt(0).toUpperCase() }}</div>
            <span class="status-dot" :class="admin.is_online ? 'online' : 'offline'"></span>
          </div>
          <div class="admin-details">
            <div class="admin-name-row">
              <h4 class="admin-name">{{ admin.ten }}</h4>
              <span class="status-pill" :class="admin.is_online ? 'online' : 'offline'"></span>
            </div>
            <p class="admin-email">{{ admin.email }}</p>
            <span class="admin-status-text" :class="admin.is_online ? 'online' : 'offline'">
              {{ admin.is_online ? 'Đang hoạt động' : timeAgo(admin.hoat_dong_cuoi_luc) }}
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
                  <img v-if="getAvatarUrl(log.user?.anhdaidien)" :src="getAvatarUrl(log.user?.anhdaidien)" class="log-user-img" alt="" />
                  <div v-else class="log-user-fallback">{{ log.user?.ten?.charAt(0).toUpperCase() }}</div>
                  <div>
                    <div class="log-user-name">{{ log.user?.ten || 'Hệ thống' }}</div>
                    <div class="log-user-email">{{ log.user?.email || 'system' }}</div>
                  </div>
                </div>
              </td>
              <td class="col-action">
                <span class="action-tag" :class="getActionClass(log.hanhdong)">
                  {{ log.hanhdong }}
                </span>
              </td>
              <td class="col-model">
                <span class="model-badge">{{ log.tenmodel }}</span>
              </td>
              <td class="col-description">
                <p class="log-desc" v-html="formatDescription(log.mota)"></p>
              </td>
              <td class="col-network">
                <div class="network-info">
                  <span class="ip-address">{{ log.diachi_ip || '127.0.0.1' }}</span>
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
          Trang trước
        </button>
        <span class="page-indicator">Trang <b>{{ pagination.current_page }}</b> / {{ pagination.last_page }}</span>
        <button 
          :disabled="pagination.current_page === pagination.last_page" 
          class="btn-page"
          @click="fetchLogs(pagination.current_page + 1)"
        >
          Trang sau
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
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(226, 232, 240, 0.8);
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
  padding: 4px 4px 12px 4px;
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
}

.admins-horizontal-row::-webkit-scrollbar {
  display: none; /* Chrome, Safari and Opera */
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
  display: inline-block;
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
  display: inline-block;
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

/* Pagination */
.pagination-bar {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 20px;
}

.btn-page {
  border: 1px solid #cbd5e1;
  background: #fff;
  padding: 8px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  transition: all 0.2s ease;
}

.btn-page:hover:not(:disabled) {
  background: #f8fafc;
  color: #2563eb;
  border-color: #2563eb;
  box-shadow: 0 2px 6px rgba(37,99,235,0.08);
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-indicator {
  font-size: 13px;
  color: #64748b;
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
</style>
