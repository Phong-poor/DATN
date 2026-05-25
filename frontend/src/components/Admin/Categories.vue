<template>
  <div class="page">
    <div class="topbar">
      <div class="topbar-left">
        <h2 class="topbar-title">Danh mục sản phẩm</h2>
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
        <button class="btn-secondary" @click="openCreateParent" style="margin-right: 10px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          Tạo Danh mục Gốc
        </button>
        <button class="btn-primary" @click="openCreateChild">
          <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tạo Danh mục Con
        </button>
      </div>
    </div>

    <BulkDeleteToolbar
      :selected-count="selectedIds.length"
      :total-count="filteredCategories.length"
      label="danh mục"
      :loading="isBulkDeleting"
      @clear="clearSelection"
      @delete-selected="removeSelected"
      @delete-all="removeAllFiltered"
    />

    <div class="category-tabs">
      <button :class="['cat-tab', { active: activeTab === 'child' }]" @click="activeTab = 'child'">
        Danh mục Con (Cấp 2)
      </button>
      <button :class="['cat-tab', { active: activeTab === 'parent' }]" @click="activeTab = 'parent'">
        Danh mục Gốc (Cấp 1)
      </button>
    </div>

    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th class="select-col">
              <input type="checkbox" :checked="allCurrentPageSelected" :disabled="!filteredCategories.length" @change="toggleCurrentPageSelection" />
            </th>
            <th>ID</th>
            <th>TÊN DANH MỤC</th>
            <th>TRẠNG THÁI</th>
            <th>THAO TÁC</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="dm in filteredCategories" :key="dm.id_danhmuc" :class="{ 'row-selected': selectedIds.includes(dm.id_danhmuc) }">
            <td class="select-col">
              <input type="checkbox" :checked="selectedIds.includes(dm.id_danhmuc)" @change="toggleItemSelection(dm.id_danhmuc)" />
            </td>
            <td class="cat-name">
              #{{ dm.id_danhmuc }}
            </td>
            <td>
              <p class="cat-name">{{ dm.ten_danhmuc }}</p>
              <p class="cat-count" v-if="activeTab === 'child' && dm.parent_id" style="color: #64748b; font-size: 12px; margin-top: 4px;">
                ↳ Thuộc nhóm: <b>{{ getParentName(dm.parent_id) }}</b>
              </p>
              <p class="cat-count" v-if="activeTab === 'parent'" style="color: #4f46e5; font-size: 12px; margin-top: 4px;">
                Có {{ getChildCount(dm.id_danhmuc) }} danh mục con
              </p>
            </td>
            <td>
              <span :class="dm.trangthai === 'active' ? 'status-active' : 'status-hidden'">
                {{ dm.trangthai === 'active' ? 'Hoạt động' : 'Tạm ẩn' }}
              </span>
            </td>
            <td>
              <div class="actions">
                 <button class="action-btn edit-btn" @click="openEdit(dm)" title="Chỉnh sửa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button class="action-btn action-delete delete-btn" @click="deleteCategory(dm.id_danhmuc)" title="Xóa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </button>
              </div>
            </td>
          </tr>
          
          <tr v-if="!isLoading && filteredCategories.length === 0">
            <td colspan="5" class="empty-row">Không tìm thấy danh mục nào phù hợp.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <transition name="fade">
      <div class="overlay" v-if="showModal">
        <transition name="slide-up">
          <div class="modal" v-if="showModal" @click.stop @mousedown.stop>
            <div class="modal-header">
              <div class="modal-header-left">
                <div class="modal-icon" :class="isEdit ? 'modal-icon-edit' : 'modal-icon-create'">
                  <svg v-if="!isEdit" viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <div>
                  <h3 class="modal-title">
                    {{ isEdit ? (isCreatingParent ? 'Chỉnh sửa Danh mục Gốc' : 'Chỉnh sửa Danh mục Con') : (isCreatingParent ? 'Tạo Danh mục Gốc mới' : 'Tạo Danh mục Con mới') }}
                  </h3>
                  <p class="modal-subtitle">{{ isEdit ? 'Cập nhật thông tin phân khúc sản phẩm' : 'Thêm mới một phân khúc sản phẩm vào hệ thống' }}</p>
                </div>
              </div>
              <button class="modal-close" @click="closeModal" title="Đóng">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </div>

            <div class="modal-body">
              <div class="form-group">
                <label class="form-label">Tên danh mục <span class="required">*</span></label>
                <input class="form-input" type="text" v-model="form.ten_danhmuc" @input="autoSlug" placeholder="VD: Laptop Gaming, Văn phòng..." />
              </div>
              <div class="form-group" v-if="!isCreatingParent">
                <label class="form-label">Thuộc Danh mục Gốc <span class="required">*</span></label>
                <select class="form-input" v-model="form.parent_id">
                  <option value="" disabled>-- Chọn danh mục gốc --</option>
                  <option v-for="p in parentCategories.filter(c => c.id_danhmuc !== editId)" :key="p.id_danhmuc" :value="p.id_danhmuc">
                    {{ p.ten_danhmuc }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Trạng thái</label>
                <select class="form-input" v-model="form.trangthai">
                  <option value="active">Hoạt động (Hiển thị cho khách hàng)</option>
                  <option value="hidden">Tạm ẩn (Chỉ nội bộ xem)</option>
                </select>
              </div>
            </div>

            <div class="modal-footer">
              <button class="btn-cancel" @click="closeModal">Hủy bỏ</button>
              <button class="btn-save" @click="saveCategory">
                {{ isEdit ? 'Cập nhật thay đổi' : 'Lưu danh mục' }}
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
import BulkDeleteToolbar from './BulkDeleteToolbar.vue';
import { useAdminBulkDelete } from '@/services/adminBulkDelete';

// --- STATE QUẢN LÝ DỮ LIỆU ---
const categories = ref([]);
const isLoading = ref(true);
const searchQuery = ref('');
const activeTab = ref('child'); // 'parent' or 'child'

// --- STATE QUẢN LÝ MODAL ---
const showModal = ref(false);
const isEdit = ref(false);
const editId = ref(null);
const isCreatingParent = ref(false);

// Form mặc định
const defaultForm = () => ({
  ten_danhmuc: '',
  slug: '',
  mo_ta: '',
  trangthai: 'active', // 'active' hoặc 'hidden'
  trang_thai: 'active', // 'active' hoặc 'hidden'
  trangthai: 'active',
  parent_id: '',
});
const form = ref(defaultForm());

// --- LẤY DỮ LIỆU TỪ DB ---
const fetchCategories = async () => {
  isLoading.value = true;
  try {
    const response = await api.get('/danhmuc'); 
    categories.value = response.data.data || response.data;
  } catch (error) {
    console.error('Lỗi khi tải danh mục:', error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchCategories();
});

// --- TÌM KIẾM & LỌC THEO TAB ---
const filteredCategories = computed(() => {
  let list = categories.value;
  
  // Lọc theo tab
  if (activeTab.value === 'parent') {
    list = list.filter(c => !c.parent_id);
  } else {
    list = list.filter(c => c.parent_id);
  }

  // Lọc theo từ khóa tìm kiếm
  if (!searchQuery.value) return list;
  return list.filter(c =>
    c.ten_danhmuc.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    (c.slug || '').toLowerCase().includes(searchQuery.value.toLowerCase())
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
  items: categories,
  filteredItems: filteredCategories,
  getId: item => item.id_danhmuc,
  endpoint: id => `/admin/danhmuc/${id}`,
  entityLabel: 'danh mục',
  fetchItems: fetchCategories,
  cannotDeleteMessage: 'Một số danh mục có thể đang được sản phẩm sử dụng.',
});

// Lọc danh mục gốc (không có cha)
const parentCategories = computed(() => {
  return categories.value.filter(c => !c.parent_id);
});

// Hàm lấy tên danh mục cha
const getParentName = (parentId) => {
  const p = categories.value.find(c => c.id_danhmuc == parentId);
  return p ? p.ten_danhmuc : '';
};

// Đếm số lượng danh mục con
const getChildCount = (parentId) => {
  return categories.value.filter(c => c.parent_id == parentId).length;
};

// --- MỞ FORM THÊM MỚI ---
const openCreateParent = () => {
  isEdit.value = false;
  editId.value = null;
  form.value = defaultForm();
  form.value.parent_id = '';
  isCreatingParent.value = true;
  showModal.value = true;
};

const openCreateChild = () => {
  isEdit.value = false;
  editId.value = null;
  form.value = defaultForm();
  isCreatingParent.value = false;
  showModal.value = true;
};

// --- MỞ FORM CHỈNH SỬA ---
const openEdit = (dm) => {
  isEdit.value = true;
  editId.value = dm.id_danhmuc;
  form.value = { 
    ten_danhmuc: dm.ten_danhmuc,
    slug: dm.slug || '',
    mo_ta: dm.mo_ta || '',
    trang_thai: dm.trang_thai,
    trangthai: dm.trangthai || dm.trang_thai,
    parent_id: dm.parent_id || '',
  }; 
  isCreatingParent.value = !dm.parent_id;
  showModal.value = true;
};

// --- ĐÓNG FORM ---
const closeModal = () => {
  showModal.value = false;
};

const autoSlug = () => {
  form.value.slug = (form.value.ten_danhmuc || '')
    .toLowerCase()
    .trim()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
}

// --- LƯU DỮ LIỆU (CREATE / UPDATE) ---
const saveCategory = async () => {
  if(!form.value.ten_danhmuc) {
    swal.error("Thiếu thông tin", "Vui lòng nhập Tên danh mục!");
    return;
  }
  if (!form.value.trangthai) form.value.trangthai = 'active';

  try {
    const payload = { ...form.value };
    if (isCreatingParent.value) {
      payload.parent_id = null; // Đảm bảo danh mục gốc không có parent_id
    } else {
      if (!payload.parent_id) {
        swal.error("Thiếu thông tin", "Vui lòng chọn Danh mục Gốc!");
        return;
      }
    }

    if (isEdit.value) {
      await api.put(`/admin/danhmuc/${editId.value}`, form.value);
      await api.put(`/danhmuc/${editId.value}`, payload);
      swal.success('Thành công', 'Cập nhật danh mục thành công!');
    } else {
      await api.post('/admin/danhmuc', form.value);
      await api.post('/danhmuc', payload);
      swal.success('Thành công', 'Thêm mới danh mục thành công!');
    }
    closeModal();
    fetchCategories(); 
  } catch (error) {
    console.error('Lỗi khi lưu danh mục:', error);
    const errorMsg = error.response?.data?.message || 'Có lỗi xảy ra, vui lòng kiểm tra lại!';
    swal.error('Lỗi', errorMsg);
  }
};

// --- XÓA (DELETE) ---
const deleteCategory = async (id) => {
  const isConfirmed = await swal.confirm('Xác nhận xóa', 'Bạn có chắc chắn muốn xóa danh mục này? Thao tác không thể hoàn tác!')
  if (isConfirmed) {
    try {
      await api.delete(`/admin/danhmuc/${id}`);
      swal.success('Đã xóa', 'Xóa danh mục thành công!');
      fetchCategories();
    } catch (error) {
      console.error('Lỗi khi xóa:', error);
      swal.error('Lỗi', 'Không thể xóa danh mục này!');
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

/* Tabs */
.category-tabs { display: flex; gap: 12px; margin-bottom: -4px; border-bottom: 2px solid #e2e8f0; padding-bottom: 0; }
.cat-tab { background: transparent; border: none; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
.cat-tab:hover { color: #4f46e5; }
.cat-tab.active { color: #4f46e5; border-bottom-color: #4f46e5; }

.btn-secondary { display: flex; align-items: center; gap: 7px; padding: 10px 20px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }
.btn-secondary svg { width: 15px; height: 15px; }

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
</style>
