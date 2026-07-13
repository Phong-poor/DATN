<script setup>
import { onMounted, ref, computed } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'

const loading = ref(false)
const saving = ref(false)
const slots = ref([])
const vouchers = ref([])
const history = ref([])
const historyLoading = ref(false)
const historyPage = ref(1)
const historyTotalPages = ref(1)

// Edit form modal state
const isEditModalOpen = ref(false)
const isAddMode = ref(false)
const editForm = ref({
  id: null,
  ten: '',
  ti_le: 0,
  loai: 'retry',
  giatri: '',
  id_voucher: null,
  mau_sac: '#475569',
  mau_chu: '#ffffff'
})

// Statistics computed
const totalWeight = computed(() => {
  return slots.value.reduce((sum, item) => sum + parseFloat(item.ti_le || 0), 0)
})

async function fetchData() {
  loading.value = true
  try {
    const res = await api.get('/admin/vong-quay')
    if (res.data?.success) {
      slots.value = res.data.slots
      vouchers.value = res.data.vouchers
    }
  } catch (error) {
    swal.error('Lỗi tải dữ liệu', 'Không thể lấy cấu hình vòng quay từ hệ thống.')
  } finally {
    loading.value = false
  }
}

async function fetchHistory(page = 1) {
  historyLoading.value = true
  historyPage.value = page
  try {
    const res = await api.get(`/admin/vong-quay/lich-su?page=${page}`)
    if (res.data?.success) {
      history.value = res.data.data.data
      historyTotalPages.value = res.data.data.last_page || 1
    }
  } catch (error) {
    console.error('Không thể tải lịch sử quay:', error)
  } finally {
    historyLoading.value = false
  }
}

function openAdd() {
  isAddMode.value = true
  editForm.value = {
    id: null,
    ten: '',
    ti_le: 0,
    loai: 'coin',
    giatri: '',
    id_voucher: null,
    mau_sac: '#2563eb',
    mau_chu: '#ffffff'
  }
  isEditModalOpen.value = true
}

function openEdit(slot) {
  isAddMode.value = false
  editForm.value = {
    id: slot.id,
    ten: slot.ten,
    ti_le: parseFloat(slot.ti_le),
    loai: slot.loai,
    giatri: slot.giatri || '',
    id_voucher: slot.id_voucher || null,
    mau_sac: slot.mau_sac || '#475569',
    mau_chu: slot.mau_chu || '#ffffff'
  }
  isEditModalOpen.value = true
}

async function saveSlot() {
  if (!editForm.value.ten.trim()) {
    swal.warning('Thiếu dữ liệu', 'Vui lòng nhập tên phần quà.')
    return
  }

  // Validate specific fields based on prize type
  if (editForm.value.loai === 'voucher') {
    if (!editForm.value.id_voucher) {
      swal.warning('Thiếu dữ liệu', 'Vui lòng chọn Voucher giảm giá để liên kết.')
      return
    }
  } else if (editForm.value.loai === 'coin') {
    const val = Number(editForm.value.giatri)
    if (!editForm.value.giatri || isNaN(val) || val <= 0) {
      swal.warning('Dữ liệu không hợp lệ', 'Vui lòng nhập số xu Predator cộng thêm hợp lệ (lớn hơn 0).')
      return
    }
  } else if (editForm.value.loai === 'ticket') {
    const val = Number(editForm.value.giatri)
    if (!editForm.value.giatri || isNaN(val) || val <= 0) {
      swal.warning('Dữ liệu không hợp lệ', 'Vui lòng nhập số lượt quay cộng thêm hợp lệ (lớn hơn 0).')
      return
    }
  } else if (editForm.value.loai === 'gift') {
    if (!String(editForm.value.giatri || '').trim()) {
      swal.warning('Thiếu dữ liệu', 'Vui lòng nhập tên/thông tin quà tặng hiện vật.')
      return
    }
  }

  // Validate: total percentage of non-retry slots cannot exceed 100%
  const newTiLe = editForm.value.loai === 'retry' ? 0 : (parseFloat(editForm.value.ti_le) || 0)
  const currentTotalNonRetry = slots.value.reduce((sum, item) => {
    if (item.loai === 'retry') return sum
    if (!isAddMode.value && item.id === editForm.value.id) return sum
    return sum + parseFloat(item.ti_le || 0)
  }, 0)
  if (currentTotalNonRetry + newTiLe > 100) {
    swal.error('Tỷ lệ vượt giới hạn', `Tổng tỷ lệ các quà khác là ${currentTotalNonRetry.toFixed(2)}%. Bạn chỉ có thể đặt tối đa ${(100 - currentTotalNonRetry).toFixed(2)}% cho ô này.`)
    return
  }

  saving.value = true
  try {
    let res
    if (isAddMode.value) {
      res = await api.post('/admin/vong-quay', editForm.value)
    } else {
      res = await api.put(`/admin/vong-quay/${editForm.value.id}`, editForm.value)
    }
    if (res.data?.success) {
      await fetchData() // refresh
      isEditModalOpen.value = false
      swal.toast(isAddMode.value ? 'Đã thêm ô quà mới thành công!' : 'Đã cập nhật cấu hình ô vòng quay!')
    }
  } catch (error) {
    swal.error('Lỗi lưu cấu hình', error?.response?.data?.message || 'Có lỗi xảy ra.')
  } finally {
    saving.value = false
  }
}

async function deleteSlot(id) {
  const confirmed = await swal.confirm('Xác nhận xóa', 'Bạn có chắc chắn muốn xóa ô quà này khỏi vòng quay?')
  if (confirmed) {
    try {
      const res = await api.delete(`/admin/vong-quay/${id}`)
      if (res.data?.success) {
        await fetchData()
        swal.toast('Đã xóa ô quà thành công!')
      }
    } catch (error) {
      swal.error('Lỗi khi xóa', error?.response?.data?.message || 'Có lỗi xảy ra.')
    }
  }
}

function typeLabel(type) {
  const labels = {
    voucher: '🎫 Voucher / Mã giảm giá',
    gift: '🎁 Quà hiện vật (Balo/Chuột...)',
    coin: '🪙 Xu Predator',
    ticket: '⚡ Lượt quay',
    retry: '☘️ Lượt quay may mắn khác'
  }
  return labels[type] || type
}

onMounted(async () => {
  await fetchData()
  await fetchHistory()
})
</script>

<template>
  <div class="admin-vongquay-page">
    <div class="top-header">
      <div>
        <h2 class="page-title">Quản Lý Vòng Quay May Mắn</h2>
        <p class="page-subtitle">Cấu hình các ô trúng thưởng, tỷ lệ trúng và kết nối voucher giảm giá.</p>
      </div>
    </div>
    <!-- STATS & VALIDATION BANNER -->
    <div class="info-row">
      <div class="stat-card" :class="{ 'warning-border': Math.abs(totalWeight - 100) > 0.01 }">
        <span class="stat-label">TỔNG TỶ LỆ CÁC Ô TRÚNG</span>
        <h3 class="value" :class="{ 'error-color': Math.abs(totalWeight - 100) > 0.01 }">
          {{ totalWeight.toFixed(2) }}%
        </h3>
      </div>
    </div>


    <div class="admin-grid">
      <!-- LEFT COLUMN: LIST OF WHEEL SLOTS -->
      <div class="panel list-panel">
        <div class="panel-head" style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
          <h3>Cơ Cấu Ô Vòng Quay ({{ slots.length }} Ô)</h3>
          <button class="btn-primary" @click="openAdd">
            <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tạo mới
          </button>
        </div>

        <div v-if="loading" class="spinner-box">
          <div class="spinner"></div>
          <span>Đang tải danh sách ô...</span>
        </div>

        <table class="admin-table" v-else>
          <thead>
            <tr>
              <th style="width: 50px;">STT</th>
              <th>PHẦN QUÀ</th>
              <th>MÀU SẮC</th>
              <th>LOẠI QUÀ</th>
              <th>TỶ LỆ TRÚNG</th>
              <th>VOUCHER LIÊN KẾT</th>
              <th style="text-align: center;">THAO TÁC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(s, idx) in slots" :key="s.id">
              <td class="stt-cell">{{ idx + 1 }}</td>
              <td>
                <div class="prize-name-cell">
                  <span class="color-preview" :style="{ backgroundColor: s.mau_sac, color: s.mau_chu }">🎫</span>
                  <span class="name">{{ s.ten }}</span>
                </div>
              </td>
              <td>
                <span class="color-code" :style="{ borderLeftColor: s.mau_sac }">
                  Nền: {{ s.mau_sac }} | Chữ: {{ s.mau_chu }}
                </span>
              </td>
              <td>
                <span class="type-badge" :class="s.loai">{{ typeLabel(s.loai) }}</span>
              </td>
              <td class="weight-cell">{{ s.ti_le }}%</td>
              <td>
                <span class="voucher-tag" v-if="s.voucher">
                  🎟️ [{{ s.voucher.code }}] {{ s.voucher.ten }}
                </span>
                <span class="empty-text" v-else-if="s.loai === 'voucher'">
                  ❌ Chưa liên kết Voucher!
                </span>
                <span class="empty-text" v-else>—</span>
              </td>
              <td style="text-align: center;">
                <div class="actions" style="justify-content: center;">
                  <button class="action-btn" @click="openEdit(s)" title="Sửa">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button v-if="s.loai !== 'retry'" class="action-btn action-delete" @click="deleteSlot(s.id)" title="Xóa">
                    <svg viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- RIGHT COLUMN: SPIN HISTORY -->
      <div class="panel history-panel">
        <div class="panel-head">
          <h3>Nhật Ký Trúng Thưởng Gần Đây</h3>
        </div>

        <div v-if="historyLoading" class="spinner-box">
          <div class="spinner"></div>
          <span>Đang tải nhật ký...</span>
        </div>

        <div v-else-if="history.length === 0" class="empty-box">
          Chưa có người dùng nào trúng thưởng.
        </div>

        <div v-else class="history-content">
          <table class="admin-table">
            <thead>
              <tr>
                <th>KHÁCH HÀNG</th>
                <th>PHẦN QUÀ TRÚNG</th>
                <th>THỜI GIAN</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="h in history" :key="h.id_lichsu">
                <td>
                  <div class="user-cell">
                    <p class="u-name">{{ h.user?.name || 'Khách vãng lai' }}</p>
                    <p class="u-email">{{ h.user?.email || '—' }}</p>
                  </div>
                </td>
                <td>
                  <span class="reward-tag" :class="h.loai_qua">
                    {{ h.ten_qua }}
                  </span>
                  <span class="reward-val" v-if="h.loai_qua === 'voucher'">({{ h.gia_tri_qua }})</span>
                </td>
                <td class="time-cell">
                  {{ new Date(h.created_at).toLocaleString('vi-VN') }}
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div class="pagination-footer" v-if="historyTotalPages > 1">
            <button 
              class="page-btn" 
              :disabled="historyPage === 1" 
              @click="fetchHistory(historyPage - 1)"
            >
              Trước
            </button>
            <span class="page-indicator">Trang {{ historyPage }} / {{ historyTotalPages }}</span>
            <button 
              class="page-btn" 
              :disabled="historyPage === historyTotalPages" 
              @click="fetchHistory(historyPage + 1)"
            >
              Sau
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- EDIT MODAL OVERLAY -->
    <transition name="modal-fade">
      <div class="modal-overlay" v-if="isEditModalOpen">
        <div class="modal-card">
          <div class="modal-header">
            <h3>{{ isAddMode ? 'Thêm Ô Vòng Quay Mới' : `Chỉnh Sửa Ô Vòng Quay #${editForm.id}` }}</h3>
            <button class="close-btn" @click="isEditModalOpen = false">&times;</button>
          </div>

          <div class="modal-body">
            <div class="form-group">
              <label>Tên hiển thị trên vòng quay:</label>
              <input type="text" v-model="editForm.ten" class="form-control" placeholder="Ví dụ: Voucher 50K..." />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tỷ lệ trúng thưởng (%):</label>
                <input 
                  type="number" 
                  step="0.01" 
                  min="0" 
                  max="100" 
                  v-model.number="editForm.ti_le" 
                  class="form-control" 
                  :disabled="editForm.loai === 'retry'"
                />
                <p v-if="editForm.loai === 'retry'" class="field-hint" style="color: #64748b; margin-top: 4px;">
                  Tỷ lệ này tự động tính toán từ các ô quà khác.
                </p>
              </div>

              <div class="form-group">
                <label>Loại giải thưởng:</label>
                <select v-model="editForm.loai" class="form-control" :disabled="editForm.loai === 'retry'">
                  <option value="voucher">🎫 Voucher / Mã giảm giá</option>
                  <option value="gift">🎁 Hiện vật (Balo/Chuột...)</option>
                  <option value="coin">🪙 Xu Predator</option>
                  <option value="ticket">⚡ Cộng thêm lượt quay</option>
                  <option value="retry" v-if="editForm.loai === 'retry'">☘️ Chúc may mắn lần sau</option>
                </select>
              </div>
            </div>

            <!-- Conditional fields depending on type -->
            <div class="form-group" v-if="editForm.loai === 'voucher'">
              <label>Liên kết Voucher (Từ Database):</label>
              <select v-model="editForm.id_voucher" class="form-control">
                <option :value="null">-- Chọn Voucher giảm giá --</option>
                <option v-for="v in vouchers" :key="v.id" :value="v.id">
                  [{{ v.code }}] {{ v.ten }} - Trị giá: {{ v.giatri }}đ
                </option>
              </select>
              <p class="field-hint" style="color: #ef4444; margin-top: 4px;" v-if="!editForm.id_voucher">
                ⚠️ Cần liên kết với một Voucher có sẵn để hệ thống tự cấp mã code cho người trúng!
              </p>
            </div>

            <div class="form-group" v-if="editForm.loai === 'coin'">
              <label>Số xu Predator cộng vào ví:</label>
              <input type="number" v-model.number="editForm.giatri" class="form-control" placeholder="Nhập số xu, VD: 100..." />
            </div>

            <div class="form-group" v-if="editForm.loai === 'ticket'">
              <label>Số lượt quay cộng thêm:</label>
              <input type="number" v-model.number="editForm.giatri" class="form-control" placeholder="Nhập số lượt, VD: 1..." />
            </div>

            <div class="form-group" v-if="editForm.loai === 'gift'">
              <label>Thông tin / tên quà tặng hiện vật:</label>
              <input type="text" v-model="editForm.giatri" class="form-control" placeholder="Tên hiện vật, VD: Chuột Predator Cestus..." />
            </div>

            <!-- Colors config -->
            <div class="form-row">
              <div class="form-group">
                <label>Màu nền ô (Hex):</label>
                <div class="color-picker-group">
                  <input type="color" v-model="editForm.mau_sac" />
                  <input type="text" v-model="editForm.mau_sac" class="form-control text-upper" placeholder="#ffffff" />
                </div>
              </div>

              <div class="form-group">
                <label>Màu chữ ô (Hex):</label>
                <div class="color-picker-group">
                  <input type="color" v-model="editForm.mau_chu" />
                  <input type="text" v-model="editForm.mau_chu" class="form-control text-upper" placeholder="#ffffff" />
                </div>
              </div>
            </div>
            
            <div class="preview-box-modal" :style="{ backgroundColor: editForm.mau_sac, color: editForm.mau_chu }">
              Xem trước ô: {{ editForm.ten || 'Chưa nhập tên' }}
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-outline" @click="isEditModalOpen = false">Hủy Bỏ</button>
            <button class="btn btn-primary" :disabled="saving" @click="saveSlot">
              {{ saving ? 'Đang Lưu...' : 'Lưu Thay Đổi' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.admin-vongquay-page {
  padding: 24px;
  background: #f8fafc;
  min-height: 100vh;
  font-family: 'Plus Jakarta Sans', sans-serif;
}

.top-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
}

.page-subtitle {
  font-size: 13.5px;
  color: #64748b;
  margin-top: 4px;
}

/* STATS ROW */
.info-row {
  margin-bottom: 24px;
}

.stat-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 16px 20px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.stat-card.warning-border {
  border-color: #f97316;
  background: #fffaf0;
}

.stat-card .stat-label {
  font-size: 10.5px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: 1px;
}

.stat-card .value {
  font-size: 28px;
  font-weight: 800;
  color: #0f172a;
  margin: 6px 0;
}

.stat-card .value.error-color {
  color: #ef4444;
}

.stat-card .sub-text {
  font-size: 12.5px;
  color: #64748b;
  margin: 0;
}

.error-color {
  color: #ef4444 !important;
}

/* GRID LAYOUT */
.admin-grid {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.panel-head {
  padding: 18px 20px;
  border-bottom: 1px solid #edf2f7;
  background: #ffffff;
}

.panel-head h3 {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

/* TABLE STYLING */
.admin-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13.5px;
}

.admin-table th {
  background: #f8fafc;
  padding: 12px 16px;
  font-weight: 700;
  color: #475569;
  border-bottom: 1.5px solid #edf2f7;
}

.admin-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  color: #334155;
}

.stt-cell {
  font-weight: 700;
  color: #94a3b8;
}

.prize-name-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.color-preview {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  box-shadow: inset 0 0 4px rgba(0,0,0,0.15);
}

.prize-name-cell .name {
  font-weight: 700;
  color: #0f172a;
}

.color-code {
  font-family: monospace;
  font-size: 11.5px;
  color: #64748b;
  padding-left: 8px;
  border-left: 3.5px solid #cbd5e1;
}

.type-badge {
  display: inline-block;
  font-size: 10.5px;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 6px;
  background: #f1f5f9;
  color: #64748b;
}

.type-badge.voucher { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
.type-badge.gift { background: rgba(249, 115, 22, 0.1); color: #ea580c; }
.type-badge.coin { background: rgba(6, 182, 212, 0.1); color: #0891b2; }
.type-badge.ticket { background: rgba(16, 185, 129, 0.1); color: #059669; }
.type-badge.retry { background: rgba(71, 85, 105, 0.1); color: #475569; }

.weight-cell {
  font-weight: 700;
  color: #0f172a;
}

.voucher-tag {
  display: inline-block;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 4px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #16a34a;
}

.empty-text {
  font-size: 12px;
  color: #94a3b8;
  font-style: italic;
}

/* BUTTONS */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-sm {
  padding: 5px 10px;
  font-size: 12px;
  border-radius: 8px;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(37,99,235,0.3);
  transition: transform 0.15s;
}

.btn-primary:hover {
  transform: translateY(-1px);
}

.btn-primary svg {
  width: 14px;
  height: 14px;
  stroke: #fff;
  stroke-width: 2.5;
  fill: none;
}

.btn-outline {
  background: transparent;
  border: 1px solid #dbe2ea;
  color: #475569;
}

.btn-outline:hover {
  background: #f8fafc;
  color: #0f172a;
  border-color: #cbd5e1;
}

.actions {
  display: flex;
  gap: 6px;
}

.action-btn {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
}

.action-btn:hover {
  background: #f1f5f9;
}

.action-btn svg {
  width: 13px;
  height: 13px;
  stroke: #64748b;
  stroke-width: 1.8;
  fill: none;
}

.action-delete:hover {
  background: #fef2f2;
  border-color: #fca5a5;
}

.action-delete:hover svg {
  stroke: #ef4444;
}

/* HISTORY ROW */
.history-panel {
  display: flex;
  flex-direction: column;
}

.history-content {
  overflow-y: auto;
  flex-grow: 1;
}

.user-cell .u-name {
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.user-cell .u-email {
  font-size: 11px;
  color: #64748b;
  margin: 2px 0 0 0;
}

.reward-tag {
  display: inline-block;
  font-size: 12px;
  font-weight: 700;
}

.reward-tag.voucher { color: #f59e0b; }
.reward-tag.gift { color: #ea580c; }
.reward-tag.coin { color: #0891b2; }
.reward-tag.ticket { color: #10b981; }
.reward-tag.retry { color: #64748b; }

.reward-val {
  font-size: 11px;
  color: #94a3b8;
  margin-left: 4px;
}

.time-cell {
  font-size: 11.5px;
  color: #64748b;
}

.pagination-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  border-top: 1px solid #edf2f7;
  background: #ffffff;
}

.page-btn {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-indicator {
  font-size: 12px;
  color: #64748b;
}

/* MODAL DESIGN */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 10010;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-card {
  width: min(500px, 95vw);
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 16px 20px;
  border-bottom: 1px solid #edf2f7;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-header h3 {
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.close-btn {
  background: transparent;
  border: none;
  font-size: 24px;
  color: #94a3b8;
  cursor: pointer;
  line-height: 1;
}

.close-btn:hover {
  color: #0f172a;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  max-height: 70vh;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 12.5px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 6px;
}

.form-control {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 13.5px;
  font-family: inherit;
  transition: all 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.color-picker-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-picker-group input[type="color"] {
  width: 38px;
  height: 38px;
  padding: 0;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  cursor: pointer;
  background: none;
}

.text-upper {
  text-transform: uppercase;
}

.preview-box-modal {
  margin-top: 18px;
  padding: 12px;
  border-radius: 10px;
  text-align: center;
  font-weight: 700;
  font-size: 13.5px;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.modal-footer {
  padding: 14px 20px;
  border-top: 1px solid #edf2f7;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
}

.field-hint {
  font-size: 11px;
}

/* Spinner */
.spinner-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  gap: 12px;
  color: #64748b;
  font-size: 13.5px;
}

.spinner {
  width: 24px;
  height: 24px;
  border: 2.5px solid #cbd5e1;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-box {
  padding: 40px;
  text-align: center;
  color: #94a3b8;
  font-size: 13.5px;
}
</style>
