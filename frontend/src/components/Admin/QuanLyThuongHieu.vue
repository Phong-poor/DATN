<template>
  <div class="page">
    <div class="topbar">
      <div class="topbar-left">
        <h2 class="topbar-title">Thương hiệu sản phẩm</h2>
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="text" placeholder="Tìm kiếm danh mục..." v-model="searchQuery"/>
        </div>
      </div>
    </div>

    <div class="hero">
      <div class="hero-text">
        <h1>Kiến trúc <span class="hero-accent">Hệ sinh thái</span><br/>Laptop</h1>
        <p>Quản lý và tối ưu hóa các phân khúc sản phẩm dựa trên nhu cầu của khách hàng.</p>
      </div>
      <div class="hero-actions">
        <button class="btn-primary" @click="openCreate">
          <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tạo thương hiệu mới
        </button>
      </div>
    </div>

    <BulkDeleteToolbar
      :selected-count="selectedIds.length"
      :total-count="filteredBrands.length"
      label="thương hiệu"
      :loading="isBulkDeleting"
      @clear="clearSelection"
      @delete-selected="removeSelected"
      @delete-all="removeAllFiltered"
    />

    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th class="select-col">
              <input type="checkbox" :checked="allCurrentPageSelected" :disabled="!filteredBrands.length" @change="toggleCurrentPageSelection" />
            </th>
            <th>ID</th>
            <th>LOGO</th>
            <th>TÊN THƯƠNG HIỆU</th>
            <th>THAO TÁC</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="th in filteredBrands" :key="th.id_thuonghieu" :class="{ 'row-selected': selectedIds.includes(th.id_thuonghieu) }">
            <td class="select-col">
              <input type="checkbox" :checked="selectedIds.includes(th.id_thuonghieu)" @change="toggleItemSelection(th.id_thuonghieu)" />
            </td>
            <td class="cat-name">
              #{{ th.id_thuonghieu }}
            </td>
            <td>
              <div class="brand-logo-cell">
                <img v-if="th.logo" :src="storageUrl(th.logo)" alt="logo" class="brand-logo-img" />
                <div v-else class="brand-logo-placeholder" :style="getPlaceholderStyle(th.ten_thuonghieu)">{{ th.ten_thuonghieu.charAt(0) }}</div>
              </div>
            </td>
            <td>
              <p class="cat-name">{{ th.ten_thuonghieu }}</p>
              <div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 4px;">
                <span v-if="!th.danh_muc_ids || th.danh_muc_ids.length === 0" style="font-size: 11px; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">Tất cả danh mục</span>
                <span v-else v-for="id in th.danh_muc_ids" :key="id" style="font-size: 11px; color: #4f46e5; background: #e0e7ff; padding: 2px 6px; border-radius: 4px;">
                  {{ getCategoryName(id) }}
                </span>
              </div>
            </td>
            <td>
              <div class="actions">
                 <button class="action-btn edit-btn" @click="openEdit(th)" title="Chỉnh sửa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button class="action-btn action-delete delete-btn" @click="deleteBrand(th.id_thuonghieu)" title="Xóa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </button>
              </div>
            </td>
          </tr>
          
          <tr v-if="!isLoading && filteredBrands.length === 0">
            <td colspan="5" class="empty-row">Không tìm thấy thương hiệu nào phù hợp.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <transition name="fade">
      <div class="overlay" v-if="showModal" @click.self="closeModal">
        <transition name="slide-up">
          <div class="modal" v-if="showModal">
            <div class="modal-header">
              <div class="modal-header-left">
                <div class="modal-icon" :class="isEdit ? 'modal-icon-edit' : 'modal-icon-create'">
                  <svg v-if="!isEdit" viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <div>
                  <h3 class="modal-title">{{ isEdit ? 'Chỉnh sửa thương hiệu' : 'Tạo thương hiệu mới' }}</h3>
                  <p class="modal-subtitle">{{ isEdit ? 'Cập nhật thông tin phân khúc thương hiệu sản phẩm' : 'Thêm mới một thương hiệu sản phẩm vào hệ thống' }}</p>
                </div>
              </div>
              <button class="modal-close" @click="closeModal" title="Đóng">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </div>

            <div class="modal-body">
              <div class="form-group">
                <label class="form-label">Tên thương hiệu <span class="required">*</span></label>
                <input class="form-input" type="text" v-model="form.ten_thuonghieu" placeholder="VD: Asus, HP, Lenovo, Apple..." />
              </div>
              
              <div class="form-group">
                <label class="form-label">Danh mục áp dụng (Chọn nhiều)</label>
                <select multiple class="form-input" v-model="form.danh_muc_ids" style="height: 120px;">
                  <option v-for="cat in categories" :key="cat.id_danhmuc" :value="cat.id_danhmuc">
                    {{ cat.parent_id ? '↳ ' : '' }}{{ cat.ten_danhmuc }}
                  </option>
                </select>
                <p style="font-size: 11px; color: #64748b; margin-top: 4px;">Giữ phím Ctrl (hoặc Cmd) để chọn nhiều. Bỏ trống để áp dụng cho tất cả danh mục.</p>
              </div>
              
              <div class="form-group">
                <label class="form-label">Logo thương hiệu</label>
                <div class="logo-upload-wrap">
                  <div class="logo-preview-box">
                    <img v-if="logoPreview" :src="logoPreview" alt="logo preview" class="logo-preview-img" />
                    <div v-else class="logo-placeholder-text">Chưa có logo</div>
                  </div>
                  <div class="upload-btn-wrap">
                    <button type="button" class="btn-upload-file" @click="$refs.fileInputRef.click()">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                      Chọn ảnh logo
                    </button>
                    <input type="file" ref="fileInputRef" style="display: none" accept="image/*" @change="handleFileChange" />
                    <span class="file-info-text" v-if="logoFile">{{ logoFile.name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button class="btn-cancel" @click="closeModal">Hủy bỏ</button>
              <button class="btn-save" @click="saveBrand">
                {{ isEdit ? 'Cập nhật thay đổi' : 'Lưu thương hiệu' }}
              </button>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '@/services/api';
import swal from '@/services/swal';
import { storageUrl } from '@/services/urls';
import BulkDeleteToolbar from './ThanhXoaHangLoat.vue';
import { useAdminBulkDelete } from '@/services/adminBulkDelete';

const getPlaceholderStyle = (name) => {
  const colors = [
    { bg: 'linear-gradient(135deg, #e0f2fe, #bae6fd)', text: '#0369a1' },
    { bg: 'linear-gradient(135deg, #dcfce7, #bbf7d0)', text: '#15803d' },
    { bg: 'linear-gradient(135deg, #fef9c3, #fef08a)', text: '#a16207' },
    { bg: 'linear-gradient(135deg, #fee2e2, #fecaca)', text: '#b91c1c' },
    { bg: 'linear-gradient(135deg, #f3e8ff, #e9d5ff)', text: '#7e22ce' },
    { bg: 'linear-gradient(135deg, #ede9fe, #ddd6fe)', text: '#6d28d9' }
  ];
  const charCode = name ? name.charCodeAt(0) : 65;
  const index = charCode % colors.length;
  return {
    background: colors[index].bg,
    color: colors[index].text
  };
};

// --- STATE QUẢN LÝ DỮ LIỆU ---
const thuonghieu = ref([]);
const categories = ref([]);
const isLoading = ref(true);
const searchQuery = ref('');

// --- STATE QUẢN LÝ MODAL ---
const showModal = ref(false);
const isEdit = ref(false);
const editId = ref(null);

// Form & Logo states
const defaultForm = () => ({
  ten_thuonghieu: '',
  danh_muc_ids: []
});
const form = ref(defaultForm());
const logoPreview = ref('');
const logoFile = ref(null);
const fileInputRef = ref(null);

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  logoFile.value = file;
  logoPreview.value = URL.createObjectURL(file);
};

// --- LẤY DỮ LIỆU TỪ DB ---
const fetchBrands = async () => {
  isLoading.value = true;
  try {
    const response = await api.get('/thuonghieu'); 
    thuonghieu.value = response.data.data || response.data;
  } catch (error) {
    console.error('Lỗi khi tải thương hiệu:', error);
  } finally {
    isLoading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const response = await api.get('/danhmuc'); 
    categories.value = response.data.data || response.data;
  } catch (error) {
    console.error('Lỗi khi tải danh mục:', error);
  }
};

onMounted(() => {
  fetchBrands();
  fetchCategories();
});

// Lấy tên danh mục để hiển thị
const getCategoryName = (id) => {
  const cat = categories.value.find(c => c.id_danhmuc == id);
  return cat ? cat.ten_danhmuc : 'Unknown';
};

// --- TÌM KIẾM ---
const filteredBrands = computed(() => {
  if (!searchQuery.value) return thuonghieu.value;
  return thuonghieu.value.filter(c =>
    c.ten_thuonghieu.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const {
  selectedIds,
  isBulkDeleting,
  allCurrentPageSelected,
  toggleItemSelection,
  toggleCurrentPageSelection,
  clearSelection,
  removeSelected,
  removeAllFiltered,
} = useAdminBulkDelete({
  items: thuonghieu,
  filteredItems: filteredBrands,
  getId: item => item.id_thuonghieu,
  endpoint: id => `/admin/thuonghieu/${id}`,
  entityLabel: 'thương hiệu',
  fetchItems: fetchBrands,
  cannotDeleteMessage: 'Một số thương hiệu có thể đang được sản phẩm sử dụng.',
});

// --- MỞ FORM THÊM MỚI ---
const openCreate = () => {
  isEdit.value = false;
  editId.value = null;
  form.value = defaultForm();
  logoPreview.value = '';
  logoFile.value = null;
  if (fileInputRef.value) fileInputRef.value.value = '';
  showModal.value = true;
};

// --- MỞ FORM CHỈNH SỬA ---
const openEdit = (th) => {
  isEdit.value = true;
  editId.value = th.id_thuonghieu;
  form.value = { 
    ten_thuonghieu: th.ten_thuonghieu,
    danh_muc_ids: Array.isArray(th.danh_muc_ids) ? [...th.danh_muc_ids] : []
  }; 
  logoPreview.value = th.logo ? storageUrl(th.logo) : '';
  logoFile.value = null;
  if (fileInputRef.value) fileInputRef.value.value = '';
  showModal.value = true;
};

// --- ĐÓNG FORM ---
const closeModal = () => {
  showModal.value = false;
};

// --- LƯU DỮ LIỆU (CREATE / UPDATE) ---
const saveBrand = async () => {
  if(!form.value.ten_thuonghieu) {
    swal.warning('Thiếu thông tin', 'Vui lòng nhập Tên thương hiệu!')
    return;
  }

  try {
    const fd = new FormData();
    fd.append('ten_thuonghieu', form.value.ten_thuonghieu);
    form.value.danh_muc_ids.forEach((id) => {
      fd.append('danh_muc_ids[]', String(id));
    });
    if (logoFile.value) {
      fd.append('logo', logoFile.value);
    }

    const config = { headers: { 'Content-Type': 'multipart/form-data' } };

    if (isEdit.value) {
      await api.post(`/admin/thuonghieu/${editId.value}`, fd, config);
      swal.success('Thành công', 'Cập nhật thương hiệu thành công!');
    } else {
      await api.post('/admin/thuonghieu', fd, config);
      swal.success('Thành công', 'Thêm mới thương hiệu thành công!');
    }
    closeModal();
    fetchBrands(); 
  } catch (error) {
    console.error('Lỗi khi lưu thương hiệu:', error);
    const errorMsg = error.response?.data?.message || 'Có lỗi xảy ra, vui lòng kiểm tra lại!';
    swal.error('Lỗi', errorMsg);
  }
};

// --- XÓA (DELETE) ---
const deleteBrand = async (id) => {
  const isConfirmed = await swal.confirm('Xác nhận xóa', 'Bạn có chắc chắn muốn xóa thương hiệu này? Thao tác không thể hoàn tác!')
  if (isConfirmed) {
    try {
      await api.delete(`/admin/thuonghieu/${id}`);
      swal.success('Đã xóa', 'Xóa thương hiệu thành công!')
      fetchBrands();
    } catch (error) {
      console.error('Lỗi khi xóa:', error);
      swal.error('Lỗi', 'Không thể xóa thương hiệu này!')
    }
  }
};
</script>

<style scoped>
/* Giữ nguyên toàn bộ CSS của bạn, mình chỉ làm gọn lại chút xíu cho dễ nhìn */
* { box-sizing: border-box; margin: 0; padding: 0; }
.page { padding: 24px 28px; background: #f0f4ff; min-height: 100vh; font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif; display: flex; flex-direction: column; gap: 20px; }
.topbar { display: flex; align-items: center; justify-content: space-between; }
.topbar-left { display: flex; align-items: center; gap: 16px; }
.topbar-title { font-size: 15px; font-weight: 600; color: #1e293b; white-space: nowrap; }
.search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 7px 14px; width: 220px; }
.search-box svg { width: 15px; height: 15px; stroke: #94a3b8; stroke-width: 2; fill: none; }
.search-box input { border: none; outline: none; font-size: 13px; color: #1e293b; background: transparent; width: 100%; }
.hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; }
.hero h1 { font-size: 32px; font-weight: 800; color: #0f172a; line-height: 1.25; margin-bottom: 12px; }
.hero-accent { color: #4f46e5; }
.hero-text p { font-size: 13.5px; color: #64748b; line-height: 1.7; }
.btn-primary { display: flex; align-items: center; gap: 7px; padding: 10px 20px; border-radius: 10px; border: none; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 14px rgba(79,70,229,0.35); transition: transform 0.15s; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(79,70,229,0.4); }
.btn-primary svg { width: 15px; height: 15px; stroke: #fff; stroke-width: 2.5; fill: none; }
.table-card { background: #fff; border-radius: 16px; border: 1px solid #e8edf5; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #f8faff; border-bottom: 1px solid #e8edf5; }
th { padding: 13px 20px; font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 0.6px; text-align: left; }
tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
tbody tr:hover { background: #fafbff; }
tbody tr.row-selected { background: #eff6ff; }
td { padding: 16px 20px; vertical-align: middle; }
.select-col { width: 44px; text-align: center; }
.select-col input { width: 16px; height: 16px; accent-color: #4f46e5; cursor: pointer; }
.cat-name { font-size: 14px; font-weight: 600; color: #1e293b; }
.cat-count { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.status-badge { font-size: 11px; font-weight: 700; padding: 5px 10px; border-radius: 6px; display: inline-block; }
.status-active,
.status-hidden {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  text-decoration: none;
  border: 1px solid transparent;
}
.status-active { color: #15803d; background: #dcfce7; border-color: #86efac; }
.status-hidden { color: #7e22ce; background: #f3e8ff; border-color: #d8b4fe; }
.actions { display: flex; gap: 6px; }
.action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.action-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.action-btn svg { width: 14px; height: 14px; stroke: #64748b; stroke-width: 1.8; fill: none; }
.action-delete:hover { background: #fef2f2; border-color: #fca5a5; }
.action-delete:hover svg { stroke: #ef4444; }
.empty-row { text-align: center; color: #94a3b8; font-size: 13px; padding: 30px; }

/* MODAL CSS */
.overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal { background: #fff; border-radius: 20px; width: 100%; max-width: 560px; box-shadow: 0 24px 60px rgba(0,0,0,0.18); display: flex; flex-direction: column; max-height: 90vh; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 22px 24px 20px; border-bottom: 1px solid #f1f5f9; }
.modal-header-left { display: flex; align-items: center; gap: 14px; }
.modal-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.modal-icon-create { background: linear-gradient(135deg, #4f46e5, #6366f1); }
.modal-icon-create svg { width: 20px; height: 20px; stroke: #fff; stroke-width: 2.5; }
.modal-icon-edit { background: linear-gradient(135deg, #f59e0b, #f97316); }
.modal-icon-edit svg { width: 20px; height: 20px; stroke: #fff; stroke-width: 2; }
.modal-title { font-size: 16px; font-weight: 700; color: #0f172a; }
.modal-subtitle { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.modal-close { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.modal-close:hover { background: #fee2e2; border-color: #fca5a5; }
.modal-close svg { width: 15px; height: 15px; stroke: #64748b; stroke-width: 2; }
.modal-close:hover svg { stroke: #ef4444; }
.modal-body { padding: 22px 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 18px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 13px; font-weight: 600; color: #374151; }
.required { color: #ef4444; }
.form-input { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px; color: #1e293b; width: 100%; outline: none; }
.form-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.form-textarea { resize: vertical; min-height: 80px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #f1f5f9; background: #fafbff; }
.btn-cancel { padding: 10px 20px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; font-size: 13px; font-weight: 500; cursor: pointer; }
.btn-cancel:hover { background: #f1f5f9; }
.btn-save { padding: 10px 22px; border-radius: 10px; border: none; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.slide-up-leave-active { transition: all 0.2s ease; }
.slide-up-enter-from { opacity: 0; transform: translateY(30px) scale(0.97); }
.slide-up-leave-to { opacity: 0; transform: translateY(10px) scale(0.98); }

/* Logo Table Cell Styling */
.brand-logo-cell {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  border: 1px solid #e8edf5;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
}

.brand-logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 4px;
}

.brand-logo-placeholder {
  font-size: 16px;
  font-weight: 700;
  color: #6366f1;
  text-transform: uppercase;
}

/* Logo Upload Section in Modal */
.logo-upload-wrap {
  display: flex;
  align-items: center;
  gap: 16px;
  background: #f8fafc;
  border: 1.5px dashed #cbd5e1;
  border-radius: 12px;
  padding: 14px 18px;
  transition: border-color 0.2s;
}

.logo-upload-wrap:hover {
  border-color: #6366f1;
}

.logo-preview-box {
  width: 64px;
  height: 64px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
}

.logo-preview-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 4px;
}

.logo-placeholder-text {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  text-align: center;
  line-height: 1.2;
  padding: 0 4px;
}

.upload-btn-wrap {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.btn-upload-file {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
  width: fit-content;
}

.btn-upload-file svg {
  width: 14px;
  height: 14px;
  stroke: currentColor;
}

.btn-upload-file:hover {
  border-color: #6366f1;
  color: #6366f1;
  background: #f5f3ff;
}

.file-info-text {
  font-size: 11px;
  color: #64748b;
  max-width: 250px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 500;
}
</style>
