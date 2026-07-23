<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'
import { getUser } from '@/services/auth'
import { registerOfflineForm, clearFormDraft } from '@/services/offlineSync'

// ─── STATE ───────────────────────────
const roles = ref([])
const loading = ref(false)
const searchQuery = ref('')
const currentUser = ref(getUser() || {})

// Modals
const showModal = ref(false)
const isEditMode = ref(false)
const editingRoleId = ref(null)
const formError = ref('')

// Form State
const form = ref({
  ten_vaitro: '',
  ma_vaitro: '',
  mo_ta: '',
  quyen: []
})

registerOfflineForm(form, 'vai-tro-form')

// ─── PERMISSION SCHEMA ────────────────
const permissionGroups = [
  {
    title: 'Sản phẩm & Tồn kho',
    permissions: [
      { code: 'san_pham_xem', name: 'Xem danh sách sản phẩm', desc: 'Cho phép truy cập xem danh sách, chi tiết và xuất báo cáo sản phẩm.' },
      { code: 'san_pham_sua', name: 'Thêm, sửa, xóa sản phẩm', desc: 'Cho phép tạo mới, chỉnh sửa và xóa thông tin sản phẩm.' },
      { code: 'nhap_xuat_kho', name: 'Nhập & xuất kho hàng', desc: 'Chức năng của Thủ kho, cho phép nhập số lượng qua file Excel.' }
    ]
  },
  {
    title: 'Danh mục & Thương hiệu',
    permissions: [
      { code: 'danh_muc_xem', name: 'Xem danh mục sản phẩm', desc: 'Xem danh sách danh mục (cần thiết khi lọc sản phẩm).' },
      { code: 'danh_muc_sua', name: 'Thêm, sửa, xóa danh mục', desc: 'Tạo mới, sửa đổi hoặc xóa danh mục cha/con.' },
      { code: 'thuong_hieu_xem', name: 'Xem thương hiệu', desc: 'Xem danh sách các thương hiệu sản phẩm.' },
      { code: 'thuong_hieu_sua', name: 'Thêm, sửa, xóa thương hiệu', desc: 'Tạo mới, chỉnh sửa hoặc xóa thương hiệu.' }
    ]
  },
  {
    title: 'Biến thể & Thuộc tính',
    permissions: [
      { code: 'bien_the_xem', name: 'Xem biến thể & thuộc tính', desc: 'Xem màu sắc, kích thước và thông số kỹ thuật sản phẩm.' },
      { code: 'bien_the_sua', name: 'Thêm, sửa, xóa biến thể', desc: 'Tạo mới, sửa đổi hoặc xóa thuộc tính/biến thể.' }
    ]
  },
  {
    title: 'Bán hàng & Đơn hàng',
    permissions: [
      { code: 'don_hang_xem', name: 'Xem danh sách đơn hàng', desc: 'Xem danh sách các đơn đặt hàng và hóa đơn.' },
      { code: 'don_hang_sua', name: 'Xử lý và duyệt đơn hàng', desc: 'Duyệt đơn, cập nhật trạng thái đơn hàng và trạng thái thanh toán.' },
      { code: 'hoa_don_xem', name: 'Xem thống kê doanh thu', desc: 'Truy cập biểu đồ, hóa đơn và doanh thu (Billing).' }
    ]
  },
  {
    title: 'Tiếp thị & Khuyến mãi',
    permissions: [
      { code: 'marketing_quan_ly', name: 'Quản lý marketing', desc: 'Thiết lập Khuyến mãi, Combo, Flash sale và chạy gửi mã sinh nhật.' },
      { code: 'affiliate_quan_ly', name: 'Quản lý Affiliate', desc: 'Duyệt yêu cầu đăng ký đối tác tiếp thị liên kết, duyệt rút tiền.' }
    ]
  },
  {
    title: 'Xu & Minigame',
    permissions: [
      { code: 'xu_quan_ly', name: 'Quản lý cấu hình Xu', desc: 'Thiết lập tỷ lệ quy đổi và các cấu hình hệ thống tích lũy xu.' },
      { code: 'vong_quay_quan_ly', name: 'Quản lý Vòng quay may mắn', desc: 'Cấu hình các phần quà, tỷ lệ trúng thưởng và xem lịch sử quay thưởng.' },
      { code: 'diem_danh_quan_ly', name: 'Quản lý Điểm danh', desc: 'Thiết lập và cấu hình số xu thưởng điểm danh hàng ngày.' }
    ]
  },
  {
    title: 'Nội dung & Bình luận',
    permissions: [
      { code: 'tin_tuc_quan_ly', name: 'Quản lý bài viết tin tức', desc: 'Viết bài mới, biên tập bài viết công nghệ và tin tức.' },
      { code: 'binh_luan_quan_ly', name: 'Quản lý bình luận & review', desc: 'Duyệt, ẩn bình luận khách hàng, cấu hình trợ lý AI trả lời.' },
      { code: 'banner_quan_ly', name: 'Quản lý banner quảng cáo', desc: 'Thay đổi và cập nhật ảnh banner chạy slide trang chủ.' }
    ]
  },
  {
    title: 'Chăm sóc khách hàng',
    permissions: [
      { code: 'lien_he_quan_ly', name: 'Hỗ trợ khách hàng', desc: 'Xem phản hồi liên hệ của khách và viết email trả lời.' }
    ]
  },
  {
    title: 'Hệ thống & Bảo mật',
    permissions: [
      { code: 'tai_khoan_quan_ly', name: 'Quản lý tài khoản nhân viên', desc: 'Tạo mới nhân viên, khóa hoặc mở khóa tài khoản nhân viên.' },
      { code: 'vai_tro_quan_ly', name: 'Quản lý vai trò & quyền', desc: 'Được quyền tạo mới, chỉnh sửa quyền hạn các chức vụ trong hệ thống.' },
      { code: 'nhat_ky_quan_ly', name: 'Xem nhật ký hoạt động', desc: 'Xem lịch sử các thao tác của nhân viên trên hệ thống.' },
      { code: 'quan_ly_cham_cong', name: 'Quản lý chấm công', desc: 'Cho phép truy cập trang quản lý chấm công, xem lịch sử chấm công của tất cả nhân viên.' }
    ]
  }
]

// Fetch data
const fetchRoles = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/vaitro')
    if (res.data?.success) {
      roles.value = res.data.data
    }
  } catch (err) {
    console.error('Fetch roles failed:', err)
    swal.error('Lỗi', 'Không thể tải danh sách vai trò')
  } finally {
    loading.value = false
  }
}

let syncSuccessHandler = null

const handleRestoreTrigger = () => {
  setTimeout(() => {
    if (form.value.ten_vaitro || form.value.ma_vaitro) {
      let isEdit = false
      try {
        const parsed = JSON.parse(localStorage.getItem('pending_restore_form'))
        if (parsed && parsed.method.toLowerCase() === 'put') {
          isEdit = true
          const parts = parsed.url.split('/')
          const id = parts[parts.length - 1]
          if (id && !isNaN(id)) {
            editingRoleId.value = parseInt(id)
          }
        }
      } catch (e) {}
      
      isEditMode.value = isEdit
      showModal.value = true
    }
  }, 50)
}

onMounted(() => {
  fetchRoles()
  
  // Lắng nghe sự kiện khôi phục yêu cầu để tự động mở Modal
  window.addEventListener('restore-form-trigger', handleRestoreTrigger)
  handleRestoreTrigger()
  
  // Tải lại danh sách vai trò khi hàng đợi đồng bộ thành công nền
  syncSuccessHandler = (e) => {
    if (e.detail?.url && e.detail.url.includes('/admin/vaitro')) {
      fetchRoles()
    }
  }
  window.addEventListener('offline-sync-success', syncSuccessHandler)
})

onUnmounted(() => {
  window.removeEventListener('restore-form-trigger', handleRestoreTrigger)
  if (syncSuccessHandler) {
    window.removeEventListener('offline-sync-success', syncSuccessHandler)
  }
})

// Search
const filteredRoles = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!q) return roles.value
  return roles.value.filter(r => 
    r.ten_vaitro.toLowerCase().includes(q) || 
    r.ma_vaitro.toLowerCase().includes(q) ||
    (r.mo_ta && r.mo_ta.toLowerCase().includes(q))
  )
})

// Open Modal
const openAddModal = () => {
  isEditMode.value = false
  editingRoleId.value = null
  formError.value = ''
  form.value = {
    ten_vaitro: '',
    ma_vaitro: '',
    mo_ta: '',
    quyen: []
  }
  showModal.value = true
}

const openEditModal = (role) => {
  isEditMode.value = true
  editingRoleId.value = role.id_vaitro
  formError.value = ''
  form.value = {
    ten_vaitro: role.ten_vaitro,
    ma_vaitro: role.ma_vaitro,
    mo_ta: role.mo_ta || '',
    quyen: Array.isArray(role.quyen) ? [...role.quyen] : []
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

// Toggle permission selection
const togglePermission = (code) => {
  const idx = form.value.quyen.indexOf(code)
  if (idx > -1) {
    form.value.quyen.splice(idx, 1)
  } else {
    form.value.quyen.push(code)
  }
}

const toggleGroupAll = (group, isChecked) => {
  group.permissions.forEach(p => {
    const idx = form.value.quyen.indexOf(p.code)
    if (isChecked) {
      if (idx === -1) form.value.quyen.push(p.code)
    } else {
      if (idx > -1) form.value.quyen.splice(idx, 1)
    }
  })
}

const isGroupAllChecked = (group) => {
  return group.permissions.every(p => form.value.quyen.includes(p.code))
}

const isGroupPartiallyChecked = (group) => {
  const checkedCount = group.permissions.filter(p => form.value.quyen.includes(p.code)).length
  return checkedCount > 0 && checkedCount < group.permissions.length
}

// Submit Form
const submitForm = async () => {
  if (!form.value.ten_vaitro.trim()) {
    formError.value = 'Vui lòng nhập tên vai trò'
    return
  }
  if (!form.value.ma_vaitro.trim()) {
    formError.value = 'Vui lòng nhập mã vai trò'
    return
  }

  loading.value = true
  try {
    if (isEditMode.value) {
      const res = await api.put(`/admin/vaitro/${editingRoleId.value}`, form.value)
      if (res.data?.success) {
        swal.success('Thành công', 'Cập nhật vai trò thành công')
        await fetchRoles()
        closeModal()
        clearFormDraft('vai-tro-form')
      }
    } else {
      const res = await api.post('/admin/vaitro', form.value)
      if (res.data?.success) {
        swal.success('Thành công', 'Thêm vai trò mới thành công')
        await fetchRoles()
        closeModal()
        clearFormDraft('vai-tro-form')
      }
    }
  } catch (err) {
    if (err.isOfflineQueue) {
      clearFormDraft('vai-tro-form')
      closeModal()
      await swal.info('Chế độ ngoại tuyến', 'Đã lưu tạm yêu cầu thay đổi vai trò ngoại tuyến. Dữ liệu sẽ tự động đồng bộ khi có mạng.')
    } else {
      formError.value = err.response?.data?.message || 'Có lỗi xảy ra khi lưu vai trò'
    }
  } finally {
    loading.value = false
  }
}

// Delete Role
const deleteRole = async (role) => {
  if (role.ma_vaitro === 'admin') {
    swal.error('Từ chối', 'Không thể xóa vai trò Quản trị viên tối cao của hệ thống')
    return
  }

  const isConfirmed = await swal.confirm(
    'Xác nhận xóa',
    `Bạn có chắc chắn muốn xóa vai trò "${role.ten_vaitro}"? Các nhân viên gán vai trò này sẽ bị mất hết các quyền hạn.`
  )
  if (!isConfirmed) return

  loading.value = true
  try {
    const res = await api.delete(`/admin/vaitro/${role.id_vaitro}`)
    if (res.data?.success) {
      swal.success('Thành công', 'Đã xóa vai trò')
      await fetchRoles()
    }
  } catch (err) {
    swal.error('Lỗi', err.response?.data?.message || 'Lỗi khi xóa vai trò')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="roles-management">
    <!-- Header -->
    <div class="top-header">
      <div>
        <h1>Quản lý vai trò & quyền hạn</h1>
        <p>Phân chia các quyền hành động chi tiết cho từng vị trí nhân viên quản trị hệ thống DATN 2026</p>
      </div>
      <button class="add-btn" @click="openAddModal">
        <span>+</span> Tạo vai trò mới
      </button>
    </div>

    <!-- Search bar -->
    <div class="filter-bar">
      <div class="search-wrap">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.35-4.35" />
        </svg>
        <input v-model="searchQuery" placeholder="Tìm kiếm tên, mã hoặc mô tả chức vụ..." />
      </div>
    </div>

    <!-- Table content -->
    <div class="table-card">
      <div v-if="loading && roles.length === 0" class="loading-state">
        <span class="spinner"></span>
        <p>Đang tải danh sách vai trò...</p>
      </div>

      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width: 80px;">STT</th>
              <th style="width: 200px;">Tên vai trò</th>
              <th style="width: 150px;">Mã vai trò</th>
              <th>Mô tả chức năng</th>
              <th style="width: 120px; text-align: center;">Số quyền</th>
              <th style="width: 150px; text-align: right;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredRoles.length === 0">
              <td colspan="6" class="empty-state">Không tìm thấy vai trò nào phù hợp.</td>
            </tr>
            <tr v-for="(r, idx) in filteredRoles" :key="r.id_vaitro">
              <td>{{ idx + 1 }}</td>
              <td>
                <span class="role-name-badge" :class="{ 'admin-badge': r.ma_vaitro === 'admin' }">
                  {{ r.ten_vaitro }}
                </span>
              </td>
              <td><code>{{ r.ma_vaitro }}</code></td>
              <td><span class="mo-ta-text">{{ r.mo_ta || '(Chưa có mô tả)' }}</span></td>
              <td style="text-align: center;">
                <span class="perms-count-badge">
                  {{ Array.isArray(r.quyen) ? r.quyen.length : 0 }} quyền
                </span>
              </td>
              <td>
                <div class="actions">
                  <button class="act-btn btn-edit" title="Chỉnh sửa quyền" @click="openEditModal(r)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                  <button 
                    v-if="r.ma_vaitro !== 'admin'" 
                    class="act-btn btn-delete" 
                    title="Xóa chức vụ" 
                    @click="deleteRole(r)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6" />
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit/Add Modal -->
    <Transition name="fade-scale">
      <div v-if="showModal" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h3>{{ isEditMode ? 'Cấu hình quyền hạn vai trò' : 'Tạo vai trò quản trị mới' }}</h3>
            <button class="close-btn" @click="closeModal">&times;</button>
          </div>
          <div class="modal-body">
            <!-- Form metadata -->
            <div class="form-row">
              <div class="form-group">
                <label>TÊN VAI TRÒ <span class="required">*</span></label>
                <input v-model="form.ten_vaitro" placeholder="VD: Nhân viên Thủ kho, Xử lý đơn hàng..." />
              </div>
              <div class="form-group">
                <label>MÃ VAI TRÒ (SLUG) <span class="required">*</span></label>
                <input 
                  v-model="form.ma_vaitro" 
                  placeholder="VD: inventory_staff, order_manager" 
                  :disabled="isEditMode && form.ma_vaitro === 'admin'" 
                />
                <small v-if="form.ma_vaitro === 'admin'" class="warn-msg">Không thể đổi mã của quản trị tối cao</small>
              </div>
            </div>
            <div class="form-group">
              <label>MÔ TẢ CHI TIẾT CHỨC VỤ</label>
              <textarea v-model="form.mo_ta" placeholder="Mô tả tóm tắt nhiệm vụ của vai trò này..." rows="2"></textarea>
            </div>

            <!-- Permission checkbox grid -->
            <div class="perms-matrix-section">
              <h4>BẢNG THIẾT LẬP QUYỀN HẠN (PERMISSION MATRIX)</h4>
              <p class="matrix-info">Hãy tích chọn các quyền cụ thể mà chức vụ này được phép thực hiện trên hệ thống.</p>
              
              <div class="matrix-grid">
                <div v-for="group in permissionGroups" :key="group.title" class="matrix-card">
                  <div class="matrix-card-header">
                    <label class="group-select-label">
                      <input 
                        type="checkbox" 
                        :checked="isGroupAllChecked(group)" 
                        :indeterminate="isGroupPartiallyChecked(group)"
                        @change="e => toggleGroupAll(group, e.target.checked)"
                        :disabled="isEditMode && form.ma_vaitro === 'admin'"
                      />
                      <span>{{ group.title }}</span>
                    </label>
                  </div>
                  <div class="matrix-card-body">
                    <div v-for="p in group.permissions" :key="p.code" class="perm-row-item">
                      <label class="perm-checkbox-label" :title="p.desc">
                        <input 
                          type="checkbox" 
                          :value="p.code" 
                          :checked="form.quyen.includes(p.code)"
                          @change="togglePermission(p.code)"
                          :disabled="isEditMode && form.ma_vaitro === 'admin'"
                        />
                        <div class="perm-info-wrap">
                          <span class="perm-name">{{ p.name }}</span>
                          <span class="perm-code-lbl">({{ p.code }})</span>
                        </div>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <p v-if="formError" class="form-error">⚠ {{ formError }}</p>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="closeModal">Hủy bỏ</button>
            <button class="btn-submit" @click="submitForm" :disabled="loading">
              {{ loading ? 'Đang xử lý...' : (isEditMode ? 'Lưu cấu hình' : 'Tạo vai trò') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.roles-management {
  padding: 24px 0;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.top-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.top-header h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
}

.top-header p {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 13px;
}

.add-btn {
  background: #2563eb;
  color: #fff;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.18);
  transition: all 0.2s ease;
}

.add-btn:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.filter-bar {
  display: flex;
  background: #ffffff;
  padding: 12px 16px;
  border-radius: 10px;
  border: 1px solid rgba(15, 23, 42, 0.05);
}

.search-wrap {
  position: relative;
  flex: 1;
  max-width: 450px;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
}

.search-wrap input {
  width: 100%;
  padding: 8px 12px 8px 36px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.2s;
}

.search-wrap input:focus {
  border-color: #2563eb;
}

.table-card {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid rgba(15, 23, 42, 0.05);
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.02);
  overflow: hidden;
}

.table-wrap {
  width: 100%;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

th, td {
  padding: 14px 18px;
  font-size: 13.5px;
  border-bottom: 1px solid #f1f5f9;
}

th {
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
}

tr:hover td {
  background: #f8fafc;
}

.role-name-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12.5px;
  font-weight: 600;
  color: #0f766e;
  background: #ccfbf1;
}

.role-name-badge.admin-badge {
  color: #b91c1c;
  background: #fee2e2;
}

code {
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 12.5px;
  color: #0f172a;
}

.mo-ta-text {
  color: #475569;
  font-size: 13px;
  line-height: 1.4;
}

.perms-count-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4f46e5;
  background: #e0e7ff;
}

.actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.act-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  display: grid;
  place-items: center;
  cursor: pointer;
  transition: all 0.2s;
}

.act-btn svg {
  width: 14px;
  height: 14px;
}

.btn-edit:hover {
  background: #e0e7ff;
  color: #4f46e5;
  border-color: #c7d2fe;
}

.btn-delete:hover {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fca5a5;
}

/* Modal styling */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  z-index: 99;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-container {
  background: #ffffff;
  width: 90%;
  max-width: 950px;
  max-height: 90vh;
  border-radius: 14px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.modal-header {
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  font-size: 16.5px;
  font-weight: 700;
  color: #0f172a;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #94a3b8;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 14px;
}

.form-group label {
  font-size: 11px;
  font-weight: 700;
  color: #475569;
  letter-spacing: 0.5px;
}

.form-group input, .form-group textarea {
  padding: 10px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13.5px;
  outline: none;
  transition: border-color 0.2s;
}

.form-group input:focus, .form-group textarea:focus {
  border-color: #2563eb;
}

.warn-msg {
  color: #ea580c;
  font-size: 11.5px;
  margin-top: 2px;
}

.perms-matrix-section {
  margin-top: 24px;
  border-top: 1px solid #e2e8f0;
  padding-top: 20px;
}

.perms-matrix-section h4 {
  margin: 0;
  font-size: 12px;
  font-weight: 800;
  color: #1e293b;
  letter-spacing: 0.8px;
}

.matrix-info {
  margin: 4px 0 16px;
  font-size: 12.5px;
  color: #64748b;
}

.matrix-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.matrix-card {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  background: #f8fafc;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.matrix-card-header {
  background: #f1f5f9;
  padding: 10px 12px;
  border-bottom: 1px solid #e2e8f0;
}

.group-select-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.group-select-label span {
  font-size: 12.5px;
  font-weight: 700;
  color: #1e293b;
}

.matrix-card-body {
  padding: 12px;
  background: #ffffff;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.perm-row-item {
  display: flex;
}

.perm-checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  width: 100%;
}

.perm-checkbox-label input {
  margin-top: 3px;
  flex-shrink: 0;
}

.perm-info-wrap {
  display: flex;
  flex-direction: column;
}

.perm-name {
  font-size: 13px;
  color: #334155;
  font-weight: 500;
}

.perm-code-lbl {
  font-size: 10.5px;
  color: #94a3b8;
  font-family: monospace;
}

.form-error {
  color: #dc2626;
  font-size: 13px;
  font-weight: 600;
  margin-top: 14px;
}

.modal-footer {
  padding: 16px 20px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: #f8fafc;
}

.btn-cancel {
  padding: 10px 20px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover {
  background: #f1f5f9;
}

.btn-submit {
  padding: 10px 20px;
  border-radius: 8px;
  background: #2563eb;
  color: #fff;
  border: none;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
}

.btn-submit:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading-state, .empty-state {
  padding: 40px;
  text-align: center;
  color: #64748b;
}

.spinner {
  display: inline-block;
  width: 28px;
  height: 28px;
  border: 3px solid rgba(37, 99, 235, 0.15);
  border-radius: 50%;
  border-top-color: #2563eb;
  animation: spin 0.8s linear infinite;
  margin-bottom: 10px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Transitions */
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: all 0.25s ease-out;
}

.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
