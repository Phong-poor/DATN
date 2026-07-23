<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'

// === STATES ===
const logs = ref([])
const loading = ref(false)
const searchQuery = ref('')
const filterDate = ref(new Date().toISOString().split('T')[0]) // Mặc định xem hôm nay

const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)
const perPage = ref(15)

// Thống kê nhanh hôm nay
const stats = ref({
  total_staff: 0,
  present: 0,
  late: 0,
  absent: 0
})

// === FUNCTIONS ===
function getAvatarUrl(avatar, name) {
  if (!avatar) return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'Staff')}&background=0D8ABC&color=fff`
  return avatar.startsWith('http') ? avatar : storageUrl(avatar)
}

function formatTime(timeStr) {
  if (!timeStr) return '--:--'
  const parts = timeStr.split(':')
  if (parts.length >= 2) return `${parts[0]}:${parts[1]}`
  return timeStr
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    let url = `/admin/quan-ly-cham-cong?page=${page}`
    if (searchQuery.value) {
      url += `&search=${encodeURIComponent(searchQuery.value)}`
    }
    if (filterDate.value) {
      url += `&date=${filterDate.value}`
    }

    const res = await api.get(url)
    if (res.data.success) {
      logs.value = res.data.data.data || []
      currentPage.value = res.data.data.current_page || 1
      lastPage.value = res.data.data.last_page || 1
      totalItems.value = res.data.data.total || 0
      perPage.value = res.data.data.per_page || 15

      // Tính toán thống kê nhanh phía client dựa trên danh sách hôm nay
      calculateStats()
    }
  } catch (error) {
    console.error('Không thể tải lịch sử chấm công:', error)
    swal.error('Lỗi', 'Không thể tải danh sách lịch sử chấm công.')
  } finally {
    loading.value = false
  }
}

function calculateStats() {
  // Thống kê đơn giản dựa trên trang hiện tại
  stats.value.total_staff = logs.value.length
  stats.value.present = logs.value.filter(l => l.gio_vao !== null).length
  stats.value.late = logs.value.filter(l => l.di_tre_phut > 0).length
  stats.value.absent = logs.value.filter(l => l.gio_vao === null).length
}

function viewImage(url, title) {
  swal.image(storageUrl(url), title)
}

watch([searchQuery, filterDate], () => {
  fetchLogs(1)
})

onMounted(() => {
  fetchLogs(1)
})
</script>

<template>
  <div class="attendance-admin-page">
    <div class="page-header">
      <div>
        <h2 class="page-title">Quản lý Chấm công Nhân viên</h2>
        <p class="page-subtitle">Xem toàn bộ lịch sử chấm công, đi trễ, số giờ làm và công của nhân viên.</p>
      </div>
      
      <!-- Cấu hình các bộ lọc -->
      <div class="filter-bar">
        <div class="search-box">
          <input type="text" placeholder="Tìm kiếm nhân viên..." v-model="searchQuery" />
        </div>
        <div class="date-box">
          <input type="date" v-model="filterDate" />
        </div>
      </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="stats-grid">
      <div class="chamcong-stat-card">
        <span class="stat-lbl">Tổng nhân sự có mặt</span>
        <span class="stat-val text-blue">{{ stats.present }} người</span>
      </div>
      <div class="chamcong-stat-card">
        <span class="stat-lbl">Tổng số lượt đi muộn</span>
        <span class="stat-val text-red">{{ stats.late }} lượt</span>
      </div>
      <div class="chamcong-stat-card">
        <span class="stat-lbl">Tổng số công tích lũy</span>
        <span class="stat-val text-gold">
          +{{ logs.reduce((sum, item) => sum + parseFloat(item.tong_cong || 0), 0).toFixed(1) }}
        </span>
      </div>
      <div class="chamcong-stat-card">
        <span class="stat-lbl">Tổng giờ làm việc thực tế</span>
        <span class="stat-val text-purple">
          {{ logs.reduce((sum, item) => sum + parseFloat(item.tong_gio || 0), 0).toFixed(1) }}h
        </span>
      </div>
    </div>

    <!-- Bảng Dữ liệu chính -->
    <div class="chamcong-table-card">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải danh sách chấm công...</p>
      </div>

      <div v-else-if="logs.length === 0" class="empty-state">
        Không tìm thấy bản ghi chấm công nào khớp với bộ lọc.
      </div>

      <div v-else class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Nhân viên</th>
              <th>Ngày</th>
              <th>Giờ Vào</th>
              <th>Ảnh Vào</th>
              <th>Giờ Ra</th>
              <th>Ảnh Ra</th>
              <th>Đi Trễ</th>
              <th>Số Giờ Làm</th>
              <th>Tổng Công</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td>
                <div class="user-info">
                  <img :src="getAvatarUrl(log.user?.anhdaidien, log.user?.ten)" class="user-avatar" />
                  <div>
                    <p class="user-name">{{ log.user?.ten || 'Không xác định' }}</p>
                    <p class="user-email">{{ log.user?.email || '' }}</p>
                  </div>
                </div>
              </td>
              <td>{{ formatDate(log.ngay_cham_cong) }}</td>
              <td class="text-blue">{{ formatTime(log.gio_vao) }}</td>
              <td>
                <img v-if="log.anh_vao" :src="storageUrl(log.anh_vao)" class="history-thumb" alt="Checkin" @click="viewImage(log.anh_vao, `Ảnh Check-in - ${log.user?.ten}`)" />
                <span v-else class="text-gray">Không có</span>
              </td>
              <td class="text-purple">{{ formatTime(log.gio_ra) }}</td>
              <td>
                <img v-if="log.anh_ra" :src="storageUrl(log.anh_ra)" class="history-thumb" alt="Checkout" @click="viewImage(log.anh_ra, `Ảnh Check-out - ${log.user?.ten}`)" />
                <span v-else class="text-gray">Không có</span>
              </td>
              <td>
                <span v-if="log.di_tre_phut > 0" class="badge-danger">Trễ {{ log.di_tre_phut }} phút</span>
                <span v-else class="badge-success">Đúng giờ</span>
              </td>
              <td class="font-bold">{{ log.tong_gio }}h</td>
              <td>
                <span class="badge-gold">+{{ log.tong_cong }} công</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-footer" v-if="lastPage > 1">
        <p class="pagination-info">Hiển thị trang {{ currentPage }} / {{ lastPage }} (Tổng số {{ totalItems }} bản ghi)</p>
        <div class="pagination">
          <button class="p-arrow" :disabled="currentPage === 1" @click="fetchLogs(currentPage - 1)">‹ Trước</button>
          <div class="p-nums">
            <button v-for="p in lastPage" :key="p" class="p-num" :class="{ active: currentPage === p }" @click="fetchLogs(p)">{{ p }}</button>
          </div>
          <button class="p-arrow" :disabled="currentPage === lastPage" @click="fetchLogs(currentPage + 1)">Sau ›</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.attendance-admin-page {
  padding: 24px;
  background: #f8fafc;
  min-height: 100%;
  color: #1e293b;
  font-family: Inter, sans-serif;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.page-title {
  font-size: 24px;
  font-weight: 800;
  margin: 0;
  color: #0f172a;
}

.page-subtitle {
  font-size: 13.5px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.filter-bar {
  display: flex;
  gap: 12px;
}

.search-box input, .date-box input {
  padding: 10px 16px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  color: #0f172a;
  font-size: 13.5px;
  outline: none;
  transition: all 0.2s;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

.search-box input:focus, .date-box input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

/* === STATS GRID === */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.chamcong-stat-card {
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.stat-lbl {
  font-size: 12px;
  color: #64748b !important;
  font-weight: 600 !important;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-val {
  font-size: 22px;
  font-weight: 800 !important;
}

.text-blue { color: #2563eb !important; }
.text-red { color: #dc2626 !important; }
.text-gold { color: #d97706 !important; }
.text-purple { color: #7c3aed !important; }

/* === TABLE CARD === */
.chamcong-table-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.025);
  overflow: hidden;
  padding: 16px;
}

.table-container {
  overflow-x: auto;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.admin-table th {
  padding: 14px 16px;
  border-bottom: 1px solid #e2e8f0;
  color: #475569;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  background: #f8fafc;
}

.admin-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13.5px;
  vertical-align: middle;
  color: #1e293b;
}

.admin-table tr:hover td {
  background: #f8fafc;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 1.5px solid #e2e8f0;
}

.user-name {
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.user-email {
  font-size: 11.5px;
  color: #64748b;
  margin: 2px 0 0 0;
}

.history-thumb {
  width: 48px;
  height: 36px;
  border-radius: 6px;
  object-fit: cover;
  cursor: pointer;
  border: 1px solid #cbd5e1;
  transition: transform 0.2s;
}

.history-thumb:hover {
  transform: scale(1.1);
}

/* Badges */
.badge-danger {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fca5a5;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}

.badge-success {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}

.badge-gold {
  background: #fffbeb;
  color: #d97706;
  border: 1px solid #fde68a;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #64748b;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-left-color: #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px auto;
}

@keyframes spin {
  100% { transform: rotate(360deg); }
}

/* Pagination */
.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 4px 4px 4px;
  border-top: 1px solid #e2e8f0;
  margin-top: 12px;
}

.pagination-info {
  font-size: 12.5px;
  color: #64748b;
  margin: 0;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 6px;
}

.p-arrow {
  padding: 6px 12px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12.5px;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.2s;
}

.p-arrow:hover:not(:disabled) {
  background: #f1f5f9;
}

.p-arrow:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.p-nums {
  display: flex;
  gap: 4px;
}

.p-num {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12.5px;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.2s;
}

.p-num:hover {
  background: #f1f5f9;
}

.p-num.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
}

.font-bold { font-weight: 700; }
.text-gray { color: #64748b; }
</style>
