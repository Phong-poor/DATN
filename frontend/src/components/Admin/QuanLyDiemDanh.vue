<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'

const logs = ref([])
const stats = ref({
  total_checkins_today: 0,
  total_xu_today: 0,
  max_streak: 0
})

const configList = ref([])
const loadingConfig = ref(false)
const savingConfig = ref(false)

const loading = ref(false)
const searchQuery = ref('')
const filterDate = ref('')
const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)

// Helper to format avatar image URL
function getAvatarUrl(avatar, name) {
  if (!avatar) return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'User')}`
  return avatar.startsWith('http') ? avatar : storageUrl(avatar)
}

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    let url = `/admin/diem-danh?page=${page}`
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
      
      if (res.data.stats) {
        stats.value = res.data.stats
      }
    }
  } catch (error) {
    console.error('Không thể tải lịch sử điểm danh:', error)
    swal.error('Lỗi', 'Không thể tải danh sách lịch sử điểm danh.')
  } finally {
    loading.value = false
  }
}

async function fetchConfig() {
  loadingConfig.value = true
  try {
    const res = await api.get('/admin/diem-danh/cauhinh')
    if (res.data.success) {
      configList.value = res.data.settings || []
    }
  } catch (error) {
    console.error('Không thể tải cấu hình điểm danh:', error)
  } finally {
    loadingConfig.value = false
  }
}

async function saveSettings() {
  // Kiểm tra tính hợp lệ: Không được trống, nhỏ hơn hoặc bằng 0, hoặc không phải số nguyên
  const hasInvalid = configList.value.some(item => {
    const val = Number(item.so_xu_thuong)
    return isNaN(val) || val <= 0 || !Number.isInteger(val)
  })

  if (hasInvalid) {
    swal.error('Lỗi nhập liệu', 'Số xu thưởng cho mỗi ngày bắt buộc phải là số nguyên lớn hơn 0!')
    return
  }

  savingConfig.value = true
  try {
    const res = await api.put('/admin/diem-danh/cauhinh', {
      settings: configList.value
    })
    if (res.data.success) {
      configList.value = res.data.settings || []
      await swal.success('Thành công', 'Đã cập nhật cấu hình xu thưởng tuần thành công!')
    }
  } catch (error) {
    console.error('Lưu cấu hình lỗi:', error)
    swal.error('Lỗi', error.response?.data?.message || 'Không thể lưu cấu hình xu thưởng.')
  } finally {
    savingConfig.value = false
  }
}

// Watch filters to reload data
watch([searchQuery, filterDate], () => {
  fetchLogs(1)
})

onMounted(() => {
  fetchLogs(1)
  fetchConfig()
})
</script>

<template>
  <div class="page">
    <div class="topbar">
      <div class="topbar-left">
        <h2 class="topbar-title">Quản lý Điểm danh</h2>
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.3-4.3"/>
          </svg>
          <input type="text" placeholder="Tìm kiếm theo tên, email..." v-model="searchQuery"/>
        </div>
        <div class="date-filter">
          <input type="date" v-model="filterDate" class="date-input" />
        </div>
      </div>
    </div>

    <!-- CRUD Configurations Panel -->
    <div class="panel config-panel">
      <div class="panel-head">
        <div>
          <h3>Cấu hình Xu thưởng theo Thứ</h3>
          <p>Thiết lập số lượng Xu thưởng cho khách hàng khi điểm danh từ Thứ Hai đến Chủ Nhật.</p>
        </div>
        <button class="save-btn" :disabled="savingConfig || loadingConfig" type="button" @click="saveSettings">
          {{ savingConfig ? 'Đang lưu...' : 'Lưu cấu hình xu thưởng' }}
        </button>
      </div>

      <div v-if="loadingConfig" class="state-loading">Đang tải cấu hình xu thưởng...</div>
      <div v-else class="config-grid">
        <div v-for="item in configList" :key="item.thu_tu" class="config-item">
          <span class="day-label">{{ item.ten_ngay }}</span>
          <div class="input-wrap-coin">
            <input v-model.number="item.so_xu_thuong" type="text" inputmode="numeric" pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
            <span class="coin-unit">🪙 Xu</span>
          </div>
        </div>
      </div>
    </div>


    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>KHÁCH HÀNG</th>
            <th>NGÀY ĐIỂM DANH</th>
            <th>SỐ XU NHẬN</th>
            <th>CHUỖI (STREAK)</th>
            <th>THỜI GIAN THỰC HIỆN</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id">
            <td>#{{ log.id }}</td>
            <td>
              <div class="user-cell">
                <img :src="getAvatarUrl(log.user?.anhdaidien, log.user?.ten)" class="user-avatar" />
                <div class="user-info">
                  <p class="user-name">{{ log.user?.ten || 'Không xác định' }}</p>
                  <p class="user-email">{{ log.user?.email || '' }}</p>
                </div>
              </div>
            </td>
            <td>
              <span class="date-badge">{{ log.ngay_diem_danh }}</span>
            </td>
            <td>
              <span class="coin-badge">+{{ log.so_xu_nhan }} Xu</span>
            </td>
            <td>
              <span class="streak-badge">Chuỗi {{ log.streak }} ngày</span>
            </td>
            <td>
              {{ new Date(log.created_at).toLocaleString('vi-VN') }}
            </td>
          </tr>
          
          <tr v-if="!loading && logs.length === 0">
            <td colspan="6" class="empty-row">Không tìm thấy lịch sử điểm danh nào phù hợp.</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination-footer" v-if="lastPage > 1">
        <p class="pagination-info">Hiển thị trang {{ currentPage }} / {{ lastPage }} (Tổng số {{ totalItems }} lượt)</p>
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
.page {
  padding: 24px;
}
.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.topbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}
.topbar-title {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}
.search-box {
  position: relative;
  width: 260px;
}
.search-box svg {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  stroke: #64748b;
}
.search-box input {
  width: 100%;
  padding: 8px 12px 8px 36px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 13.5px;
  outline: none;
  background: #fff;
  transition: all 0.2s;
}
.search-box input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.date-filter {
  display: flex;
  align-items: center;
}
.date-input {
  padding: 8px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 13.5px;
  outline: none;
  background: #fff;
}

/* Config panel */
.config-panel {
  background: #fff;
  border: 1px solid #dfe7f2;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
  margin-bottom: 24px;
}
.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #edf2f7;
  flex-wrap: wrap;
  gap: 12px;
}
.panel-head h3 {
  margin: 0;
  font-size: 16px;
  color: #0f172a;
}
.panel-head p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 12.5px;
}
.save-btn {
  border-radius: 10px;
  padding: 8px 14px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
  transition: all 0.2s;
}
.save-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3);
}
.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.state-loading {
  padding: 20px;
  color: #64748b;
  font-size: 13.5px;
}
.config-grid {
  padding: 20px;
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 12px;
}
.config-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.day-label {
  font-size: 12.5px;
  font-weight: 700;
  color: #475569;
  text-align: center;
}
.input-wrap-coin {
  position: relative;
  display: flex;
  align-items: center;
}
.input-wrap-coin input {
  width: 100%;
  padding: 8px 32px 8px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  text-align: center;
  background: #fff;
}
.input-wrap-coin input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}
.coin-unit {
  position: absolute;
  right: 6px;
  font-size: 11px;
  color: #64748b;
  pointer-events: none;
}

/* Stats grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}
.stat-card {
  background: #fff;
  border: 1px solid #dfe7f2;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
}
.stat-card-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}
.today-checkins {
  background: #eff6ff;
}
.today-xu {
  background: #fef9c3;
}
.max-streak {
  background: #ffedd5;
}
.stat-card-content {
  flex: 1;
}
.stat-lbl {
  font-size: 12.5px;
  color: #64748b;
  margin: 0 0 4px;
}
.stat-val {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

/* Table card */
.table-card {
  background: #fff;
  border: 1px solid #dfe7f2;
  border-radius: 16px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
  overflow: hidden;
  padding: 8px;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th {
  background: #f8fafc;
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
  text-transform: capitalize;
  letter-spacing: 0.5px;
  padding: 14px 16px;
  text-align: left;
  border-bottom: 1px solid #edf2f7;
}
td {
  padding: 14px 16px;
  border-bottom: 1px solid #edf2f7;
  font-size: 13.5px;
  color: #334155;
  vertical-align: middle;
}
.user-cell {
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
  font-size: 12px;
  color: #64748b;
  margin: 2px 0 0;
}

.date-badge {
  background: #f1f5f9;
  color: #334155;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}
.coin-badge {
  background: #fef8e7;
  color: #b45309;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
}
.streak-badge {
  background: #ecfdf5;
  color: #065f46;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}
.empty-row {
  text-align: center;
  color: #64748b;
  padding: 32px;
}

/* Pagination */
.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-top: 1px solid #edf2f7;
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
  background: #fff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12.5px;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s;
}
.p-arrow:hover:not(:disabled) {
  border-color: #94a3b8;
  background: #f8fafc;
}
.p-arrow:disabled {
  opacity: 0.5;
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
  background: #fff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12.5px;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s;
}
.p-num:hover {
  border-color: #94a3b8;
  background: #f8fafc;
}
.p-num.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
}

@media (max-width: 992px) {
  .config-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
@media (max-width: 768px) {
  .config-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}
@media (max-width: 480px) {
  .config-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
