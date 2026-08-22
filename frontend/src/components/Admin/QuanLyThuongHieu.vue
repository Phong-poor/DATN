<template>
  <div class="page">
    <!-- HERO HEADER -->
    <div class="hero">
      <div class="hero-text">
        <h1>Quản lý <span class="hero-accent">thương hiệu</span></h1>
        <p>Cấu hình danh mục thương hiệu sản phẩm và thiết lập phạm vi phân vùng hệ thống 2026.</p>
      </div>
      <div class="hero-actions">
        <!-- FILTER THEO DANH MỤC -->
        <div class="filter-category-box">
          <select v-model="selectedCategoryFilter" class="filter-select">
            <option value="">📁 Tất cả danh mục</option>
            <option v-for="cat in categories" :key="'filter_cat_' + cat.id_danhmuc" :value="cat.id_danhmuc">
              {{ cat.id_danhmuc_cha || cat.parent_id ? '└─ ' : '📁 ' }}{{ cat.ten_danhmuc }}
            </option>
          </select>
        </div>

        <!-- TÌM KIẾM TÊN THƯƠNG HIỆU -->
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="text" placeholder="Tìm kiếm thương hiệu..." v-model="searchQuery"/>
        </div>

        <button v-if="hasPermission('thuong_hieu_sua')" class="btn-primary" @click="openCreate">
          <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tạo thương hiệu mới
        </button>
      </div>
    </div>

    <!-- BULK DELETE TOOLBAR -->
    <BulkDeleteToolbar
      :selected-count="selectedIds.length"
      :total-count="filteredBrands.length"
      label="thương hiệu"
      :loading="isBulkDeleting"
      @clear="clearSelection"
      @delete-selected="removeSelected"
      @delete-all="removeAllFiltered"
    />

    <!-- DATA TABLE CARD -->
    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th class="select-col">
              <input type="checkbox" :checked="allCurrentPageSelected" :disabled="!pagedBrands.length" @change="toggleCurrentPageSelection" />
            </th>
            <th>STT</th>
            <th>LOGO</th>
            <th>TÊN THƯƠNG HIỆU</th>
            <th>DANH MỤC ÁP DỤNG</th>
            <th>THAO TÁC</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(th, index) in pagedBrands" :key="th.id_thuonghieu" :class="{ 'row-selected': selectedIds.includes(th.id_thuonghieu) }">
            <td class="select-col">
              <input type="checkbox" :checked="selectedIds.includes(th.id_thuonghieu)" @change="toggleItemSelection(th.id_thuonghieu)" />
            </td>
            <td class="stt-cell">
              {{ (currentPage - 1) * PER_PAGE + index + 1 }}
            </td>
            <td class="logo-cell-wrap">
              <div class="brand-logo-cell">
                <img v-if="th.logo" :src="storageUrl(th.logo)" alt="logo" class="brand-logo-img" />
                <div v-else class="brand-logo-placeholder" :style="getPlaceholderStyle(th.ten_thuonghieu)">
                  {{ th.ten_thuonghieu ? th.ten_thuonghieu.charAt(0).toUpperCase() : 'B' }}
                </div>
              </div>
            </td>
            <td>
              <p class="cat-name">{{ th.ten_thuonghieu }}</p>
            </td>
            <td>
              <div class="table-badges-wrap">
                <span v-if="!th.danh_muc_ids || th.danh_muc_ids.length === 0" class="badge-pill bg-global">
                  ✔ Tất cả danh mục
                </span>
                <template v-else>
                  <span v-for="id in th.danh_muc_ids" :key="id" class="badge-pill bg-cat">
                    {{ getCategoryName(id) }}
                  </span>
                </template>
              </div>
            </td>
            <td>
              <div class="actions">
                 <button v-if="hasPermission('thuong_hieu_sua')" class="action-btn edit-btn" @click="openEdit(th)" title="Chỉnh sửa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button v-if="hasPermission('thuong_hieu_sua')" class="action-btn action-delete delete-btn" @click="deleteBrand(th.id_thuonghieu)" title="Xóa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </button>
              </div>
            </td>
          </tr>
          
          <tr v-if="!isLoading && filteredBrands.length === 0">
            <td colspan="6" class="empty-row">Không tìm thấy thương hiệu nào phù hợp.</td>
          </tr>
        </tbody>
      </table>

      <!-- PHÂN TRANG -->
      <PhanTrangAdmin
        v-model:currentPage="currentPage"
        :total-pages="totalPages"
        :total-items="filteredBrands.length"
        :page-size="PER_PAGE"
        item-label="thương hiệu"
      />
    </div>

    <!-- MODAL TẠO / SỬA THƯƠNG HIỆU -->
    <transition name="fade">
      <div class="overlay" v-if="showModal">
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
              
              <!-- PHẠM VI ÁP DỤNG DANH MỤC -->
              <div class="form-group">
                <div class="scope-toggle-wrap">
                  <div class="scope-text-info">
                    <strong class="scope-title">Phạm vi áp dụng danh mục</strong>
                    <small class="scope-sub">Bật để áp dụng thương hiệu này cho tất cả danh mục trên hệ thống</small>
                  </div>
                  <label class="toggle-label">
                    <input type="checkbox" v-model="isGlobalScope" class="toggle-checkbox" />
                    <span>Áp dụng Toàn cục</span>
                  </label>
                </div>

                <!-- BADGES CÁC DANH MỤC ĐANG CHỌN -->
                <div class="selected-category-badges-box">
                  <span class="box-title">Đang chọn ({{ form.danh_muc_ids ? form.danh_muc_ids.length : 0 }} danh mục):</span>
                  <div class="badges-wrap">
                    <template v-if="!isGlobalScope && form.danh_muc_ids && form.danh_muc_ids.length">
                      <span 
                        v-for="cat in getSelectedCategoryBadges(form.danh_muc_ids)" 
                        :key="cat.id" 
                        class="cat-pill-badge"
                        :class="cat.isParent ? 'parent-pill' : 'child-pill'"
                      >
                        <span class="pill-name">{{ cat.isParent ? '📁 ' : '└─ ' }}{{ cat.name }}</span>
                        <button type="button" class="btn-remove-pill" @click="removeCategorySelection(cat.id)" title="Bỏ chọn danh mục này">✕</button>
                      </span>
                    </template>
                    <span v-else-if="!isGlobalScope" class="warn-notice">
                      ⚠️ Chưa chọn danh mục nào. Vui lòng tích chọn danh mục bên dưới
                    </span>
                    <span v-else class="all-cats-notice">
                      ✔ Áp dụng Toàn cục cho <b>TẤT CẢ SẢN PHẨM &amp; DANH MỤC</b>
                    </span>
                  </div>
                </div>

                <!-- CHECKBOX TREE TÍCH CHỌN DANH MỤC (ẨN KHI TOÀN CỤC) -->
                <div v-if="!isGlobalScope" class="category-checkbox-tree-box">
                  <div v-for="parent in parentCategories" :key="'p_tree_' + parent.id_danhmuc" class="tree-parent-group">
                    <label class="checkbox-row parent-row">
                      <input 
                        type="checkbox" 
                        :checked="isCategorySelected(parent.id_danhmuc)" 
                        @change="toggleCategorySelection(parent.id_danhmuc)"
                      />
                      <span class="parent-title">📁 {{ parent.ten_danhmuc }} (Tất cả {{ parent.ten_danhmuc }})</span>
                    </label>
                    <div class="tree-children-rows">
                      <label 
                        v-for="child in getChildCategoriesOf(parent.id_danhmuc)" 
                        :key="'c_tree_' + child.id_danhmuc" 
                        class="checkbox-row child-row"
                      >
                        <input 
                          type="checkbox" 
                          :checked="isCategorySelected(child.id_danhmuc)" 
                          @change="toggleCategorySelection(child.id_danhmuc)"
                        />
                        <span>└─ {{ child.ten_danhmuc }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- UPLOAD LOGO THƯƠNG HIỆU -->
              <div class="form-group">
                <label class="form-label">Logo thương hiệu</label>
                <div 
                  class="logo-upload-wrap" 
                  :class="{ 'is-dragging': isDragging }"
                  @dragover.prevent="isDragging = true"
                  @dragleave.prevent="isDragging = false"
                  @drop.prevent="handleDrop"
                >
                  <div class="logo-preview-box">
                    <img v-if="logoPreview" :src="logoPreview" alt="logo preview" class="logo-preview-img" />
                    <div v-else class="logo-placeholder-text">Kéo thả ảnh hoặc chọn</div>
                  </div>
                  <div class="upload-btn-wrap">
                    <div style="display: flex; gap: 8px; align-items: center;">
                      <button type="button" class="btn-upload-file" @click="$refs.fileInputRef.click()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Chọn ảnh logo
                      </button>
                      <button v-if="logoPreview" type="button" class="btn-remove-logo" @click="clearLogo" title="Xóa logo này">
                        Xóa logo
                      </button>
                    </div>
                    <input type="file" ref="fileInputRef" style="display: none" accept="image/*" @change="handleFileChange" />
                    <span class="file-info-text" v-if="logoFile">{{ logoFile.name }}</span>
                    <small class="upload-hint">Định dạng PNG, JPG, WEBP. Tối đa 2MB.</small>
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
import { ref, onMounted, computed, watch, onBeforeUnmount } from 'vue';
import { getUser } from '@/services/auth';
import api from '@/services/api';
import swal from '@/services/swal';
import { storageUrl } from '@/services/urls';
import BulkDeleteToolbar from './ThanhXoaHangLoat.vue';
import PhanTrangAdmin from './PhanTrangAdmin.vue';
import { useAdminBulkDelete } from '@/services/adminBulkDelete';
import { registerOfflineForm } from '@/services/offlineSync';

const user = ref(getUser() || {});
const hasPermission = (perm) => {
  if (user.value?.vaitro && user.value.vaitro !== 'user') return true;
  return user.value?.cac_quyen?.includes(perm);
};

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
const selectedCategoryFilter = ref('');

// --- PHÂN TRANG ---
const PER_PAGE = 8;
const currentPage = ref(1);

// --- STATE QUẢN LÝ MODAL ---
const showModal = ref(false);
const isEdit = ref(false);
const editId = ref(null);
const isGlobalScope = ref(true);

// Form & Logo states
const defaultForm = () => ({
  ten_thuonghieu: '',
  danh_muc_ids: []
});

const form = ref(defaultForm());
registerOfflineForm(form, 'quan-ly-thuong-hieu');
const logoPreview = ref('');
const logoFile = ref(null);
const fileInputRef = ref(null);
const isDragging = ref(false);

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  logoFile.value = file;
  logoPreview.value = URL.createObjectURL(file);
};

const handleDrop = (e) => {
  isDragging.value = false;
  const file = e.dataTransfer.files[0];
  if (!file) return;
  if (!file.type.startsWith('image/')) {
    swal.warning('Định dạng tệp không hợp lệ', 'Vui lòng kéo thả file hình ảnh!');
    return;
  }
  logoFile.value = file;
  logoPreview.value = URL.createObjectURL(file);
};

const clearLogo = () => {
  logoFile.value = null;
  logoPreview.value = '';
  if (fileInputRef.value) fileInputRef.value.value = '';
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

const syncSuccessHandler = () => {
  fetchBrands();
  fetchCategories();
};

onMounted(() => {
  fetchBrands();
  fetchCategories();
  window.addEventListener('offline-sync-success', syncSuccessHandler);
});

onBeforeUnmount(() => {
  window.removeEventListener('offline-sync-success', syncSuccessHandler);
});

// Lấy tên danh mục để hiển thị
const getCategoryName = (id) => {
  const cat = categories.value.find(c => String(c.id_danhmuc) === String(id) || String(c.id) === String(id));
  return cat ? cat.ten_danhmuc : `Danh mục #${id}`;
};

// Phân chia Danh mục Cha / Con
const parentCategories = computed(() => {
  return categories.value.filter(c => !c.id_danhmuc_cha && !c.parent_id);
});

const getChildCategoriesOf = (parentId) => {
  return categories.value.filter(c => String(c.id_danhmuc_cha) === String(parentId) || String(c.parent_id) === String(parentId));
};

const getSelectedCategoryBadges = (selectedIds) => {
  if (!Array.isArray(selectedIds) || !selectedIds.length) return [];
  return selectedIds.map(id => {
    const found = categories.value.find(c => String(c.id_danhmuc) === String(id) || String(c.id) === String(id));
    const isParent = found ? (!found.id_danhmuc_cha && !found.parent_id) : false;
    return found 
      ? { id: Number(found.id_danhmuc || found.id), name: found.ten_danhmuc, isParent } 
      : { id: Number(id), name: `Danh mục #${id}`, isParent: false };
  });
};

const isCategorySelected = (id) => {
  if (!Array.isArray(form.value.danh_muc_ids)) return false;
  return form.value.danh_muc_ids.map(Number).includes(Number(id));
};

const toggleCategorySelection = (id) => {
  const numId = Number(id);
  if (!Array.isArray(form.value.danh_muc_ids)) form.value.danh_muc_ids = [];

  const isParent = parentCategories.value.some(p => Number(p.id_danhmuc || p.id) === numId);

  if (isParent) {
    const childIds = getChildCategoriesOf(numId).map(c => Number(c.id_danhmuc || c.id));
    const isSelected = form.value.danh_muc_ids.map(Number).includes(numId);

    if (isSelected) {
      form.value.danh_muc_ids = form.value.danh_muc_ids.filter(i => Number(i) !== numId && !childIds.includes(Number(i)));
    } else {
      const set = new Set([...form.value.danh_muc_ids.map(Number), numId, ...childIds]);
      form.value.danh_muc_ids = Array.from(set);
    }
  } else {
    const index = form.value.danh_muc_ids.findIndex(i => Number(i) === numId);
    if (index >= 0) {
      form.value.danh_muc_ids.splice(index, 1);
      const childObj = categories.value.find(c => Number(c.id_danhmuc || c.id) === numId);
      if (childObj && (childObj.id_danhmuc_cha || childObj.parent_id)) {
        const parentId = Number(childObj.id_danhmuc_cha || childObj.parent_id);
        const pIndex = form.value.danh_muc_ids.findIndex(i => Number(i) === parentId);
        if (pIndex >= 0) form.value.danh_muc_ids.splice(pIndex, 1);
      }
    } else {
      form.value.danh_muc_ids.push(numId);
      const childObj = categories.value.find(c => Number(c.id_danhmuc || c.id) === numId);
      if (childObj && (childObj.id_danhmuc_cha || childObj.parent_id)) {
        const parentId = Number(childObj.id_danhmuc_cha || childObj.parent_id);
        const siblingIds = getChildCategoriesOf(parentId).map(c => Number(c.id_danhmuc || c.id));
        const allSiblingsSelected = siblingIds.every(sId => form.value.danh_muc_ids.map(Number).includes(sId));
        if (allSiblingsSelected && !form.value.danh_muc_ids.map(Number).includes(parentId)) {
          form.value.danh_muc_ids.push(parentId);
        }
      }
    }
  }
};

const removeCategorySelection = (id) => {
  const numId = Number(id);
  if (!Array.isArray(form.value.danh_muc_ids)) return;

  const isParent = parentCategories.value.some(p => Number(p.id_danhmuc || p.id) === numId);
  if (isParent) {
    const childIds = getChildCategoriesOf(numId).map(c => Number(c.id_danhmuc || c.id));
    form.value.danh_muc_ids = form.value.danh_muc_ids.filter(i => Number(i) !== numId && !childIds.includes(Number(i)));
  } else {
    form.value.danh_muc_ids = form.value.danh_muc_ids.filter(i => Number(i) !== numId);
    const childObj = categories.value.find(c => Number(c.id_danhmuc || c.id) === numId);
    if (childObj && (childObj.id_danhmuc_cha || childObj.parent_id)) {
      const parentId = Number(childObj.id_danhmuc_cha || childObj.parent_id);
      form.value.danh_muc_ids = form.value.danh_muc_ids.filter(i => Number(i) !== parentId);
    }
  }
};

// --- TÌM KIẾM & LỌC ---
const filteredBrands = computed(() => {
  let result = thuonghieu.value;

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    result = result.filter(c => c.ten_thuonghieu.toLowerCase().includes(q));
  }

  if (selectedCategoryFilter.value) {
    const catId = Number(selectedCategoryFilter.value);
    result = result.filter(c => {
      if (!c.danh_muc_ids || c.danh_muc_ids.length === 0) return true; // Áp dụng toàn cục
      return c.danh_muc_ids.map(Number).includes(catId);
    });
  }

  return result;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredBrands.value.length / PER_PAGE)));

const pagedBrands = computed(() => {
  const start = (currentPage.value - 1) * PER_PAGE;
  return filteredBrands.value.slice(start, start + PER_PAGE);
});

watch([searchQuery, selectedCategoryFilter], () => {
  currentPage.value = 1;
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
  filteredItems: pagedBrands,
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
  isGlobalScope.value = true;
  logoPreview.value = '';
  logoFile.value = null;
  if (fileInputRef.value) fileInputRef.value.value = '';
  showModal.value = true;
};

// --- MỞ FORM CHỈNH SỬA ---
const openEdit = (th) => {
  isEdit.value = true;
  editId.value = th.id_thuonghieu;
  const dsId = Array.isArray(th.danh_muc_ids) ? [...th.danh_muc_ids] : [];
  form.value = { 
    ten_thuonghieu: th.ten_thuonghieu,
    danh_muc_ids: dsId
  }; 
  isGlobalScope.value = !dsId || dsId.length === 0;
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
  if (!form.value.ten_thuonghieu.trim()) {
    swal.warning('Thiếu thông tin', 'Vui lòng nhập Tên thương hiệu!');
    return;
  }

  if (!isGlobalScope.value && (!form.value.danh_muc_ids || form.value.danh_muc_ids.length === 0)) {
    swal.warning('Chưa chọn danh mục', 'Vui lòng tích chọn ít nhất 1 danh mục áp dụng hoặc bật "Áp dụng Toàn cục"!');
    return;
  }

  try {
    const fd = new FormData();
    fd.append('ten_thuonghieu', form.value.ten_thuonghieu.trim());
    
    const finalCatIds = isGlobalScope.value ? [] : form.value.danh_muc_ids;
    finalCatIds.forEach((id) => {
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
  const isConfirmed = await swal.confirm('Xác nhận xóa', 'Bạn có chắc chắn muốn xóa thương hiệu này? Thao tác không thể hoàn tác!');
  if (isConfirmed) {
    try {
      await api.delete(`/admin/thuonghieu/${id}`);
      swal.success('Đã xóa', 'Xóa thương hiệu thành công!');
      fetchBrands();
    } catch (error) {
      console.error('Lỗi khi xóa:', error);
      swal.error('Lỗi', 'Không thể xóa thương hiệu này!');
    }
  }
};
</script>

<style scoped>
* { box-sizing: border-box; margin: 0; padding: 0; }

.page { 
  padding: 24px 28px; 
  background: #f0f4ff; 
  min-height: 100vh; 
  font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif; 
  display: flex; 
  flex-direction: column; 
  gap: 20px; 
}

/* HERO TOP SECTION */
.hero { 
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  gap: 20px; 
  flex-wrap: wrap; 
}

.hero-text h1 { 
  font-size: 26px; 
  font-weight: 800; 
  color: #0f172a; 
  line-height: 1.2; 
  margin-bottom: 6px; 
}

.hero-accent { color: #2563eb; }

.hero-text p { 
  font-size: 13px; 
  color: #64748b; 
  line-height: 1.5; 
}

.hero-actions { 
  display: flex; 
  align-items: center; 
  gap: 12px; 
  flex-wrap: wrap; 
}

.filter-category-box { min-width: 180px; }

.filter-select {
  width: 100%;
  padding: 9px 14px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  background: white;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  font-weight: 500;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.filter-select:focus { border-color: #2563eb; }

.search-box { 
  display: flex; 
  align-items: center; 
  gap: 8px; 
  background: #fff; 
  border: 1px solid #cbd5e1; 
  border-radius: 10px; 
  padding: 8px 14px; 
  width: 240px; 
}

.search-box svg { width: 15px; height: 15px; stroke: #94a3b8; stroke-width: 2; fill: none; }
.search-box input { border: none; outline: none; font-size: 13px; color: #1e293b; background: transparent; width: 100%; }

.btn-primary { 
  display: flex; 
  align-items: center; 
  gap: 7px; 
  padding: 10px 20px; 
  border-radius: 10px; 
  border: none; 
  background: linear-gradient(135deg, #2563eb, #3b82f6); 
  color: #fff; 
  font-size: 13px; 
  font-weight: 600; 
  cursor: pointer; 
  box-shadow: 0 4px 14px rgba(37,99,235,0.35); 
  transition: transform 0.15s; 
}

.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(37,99,235,0.4); }
.btn-primary svg { width: 15px; height: 15px; stroke: #fff; stroke-width: 2.5; fill: none; }

/* TABLE CARD */
.table-card { background: #fff; border-radius: 16px; border: 1px solid #e8edf5; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #f8faff; border-bottom: 1px solid #e8edf5; }
th { padding: 13px 20px; font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 0.6px; text-align: left; }
tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
tbody tr:hover { background: #fafbff; }
tbody tr.row-selected { background: #eff6ff; }
td { padding: 14px 20px; vertical-align: middle; }

.select-col { width: 44px; text-align: center; }
.select-col input { width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer; }
.stt-cell { font-weight: 700; font-size: 13.5px; color: #64748b; width: 60px; }
.cat-name { font-size: 14px; font-weight: 600; color: #1e293b; }

.table-badges-wrap { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.badge-pill {
  font-size: 11.5px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  line-height: 1.2;
}
.bg-global {
  color: #047857;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
}
.bg-cat {
  color: #1d4ed8;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
}

.actions { display: flex; gap: 6px; }
.action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.action-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.action-btn svg { width: 14px; height: 14px; stroke: #64748b; stroke-width: 1.8; fill: none; }
.action-delete:hover { background: #fef2f2; border-color: #fca5a5; }
.action-delete:hover svg { stroke: #ef4444; }
.edit-btn:hover { background: #eff6ff; border-color: #93c5fd; }
.edit-btn:hover svg { stroke: #2563eb; }
.empty-row { text-align: center; color: #94a3b8; font-size: 13px; padding: 30px; }
@media (max-width: 820px) { .page { padding: 16px; } .hero-actions, .hero-actions .search-box { width: 100%; } .btn-primary { flex: 1; justify-content: center; } .table-card { overflow-x: auto; } table { min-width: 680px; } }

/* BRAND LOGO CELL */
.brand-logo-cell { width: 44px; height: 44px; border-radius: 8px; border: 1px solid #e8edf5; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.brand-logo-img { width: 100%; height: 100%; object-fit: contain; padding: 3px; }
.brand-logo-placeholder { font-size: 16px; font-weight: 700; text-transform: uppercase; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }

/* MODAL STYLES */
.overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(5px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal { background: #fff; border-radius: 20px; width: 100%; max-width: 580px; box-shadow: 0 24px 60px rgba(0,0,0,0.18); display: flex; flex-direction: column; max-height: 90vh; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
.modal-header-left { display: flex; align-items: center; gap: 14px; }
.modal-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.modal-icon-create { background: linear-gradient(135deg, #2563eb, #3b82f6); }
.modal-icon-create svg { width: 20px; height: 20px; stroke: #fff; stroke-width: 2.5; }
.modal-icon-edit { background: linear-gradient(135deg, #f59e0b, #f97316); }
.modal-icon-edit svg { width: 20px; height: 20px; stroke: #fff; stroke-width: 2; }
.modal-title { font-size: 16px; font-weight: 700; color: #0f172a; }
.modal-subtitle { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.modal-close { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.modal-close:hover { background: #fee2e2; border-color: #fca5a5; }
.modal-close svg { width: 15px; height: 15px; stroke: #64748b; stroke-width: 2; }
.modal-close:hover svg { stroke: #ef4444; }

.modal-body { padding: 20px 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 18px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 13px; font-weight: 600; color: #374151; }
.required { color: #ef4444; }
.form-input { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13.5px; color: #1e293b; width: 100%; outline: none; }
.form-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

/* SCOPE TOGGLE & CATEGORY TREE */
.scope-toggle-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  background: #f8fafc;
  padding: 12px 16px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}
.scope-text-info { display: flex; flex-direction: column; gap: 2px; }
.scope-title { font-size: 13px; font-weight: 700; color: #1e293b; }
.scope-sub { color: #64748b; font-size: 11.5px; line-height: 1.4; }
.toggle-label { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: #2563eb; flex-shrink: 0; }
.toggle-checkbox { width: 16px; height: 16px; cursor: pointer; accent-color: #2563eb; }

.selected-category-badges-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px 16px;
}
.box-title { font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px; display: block; }
.badges-wrap { display: flex; flex-wrap: wrap; gap: 6px; }
.cat-pill-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}
.parent-pill { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.child-pill { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
.btn-remove-pill {
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 11px;
  cursor: pointer;
  padding: 0 2px;
  line-height: 1;
}
.btn-remove-pill:hover { color: #ef4444; }

.all-cats-notice { 
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  background: #ecfdf5;
  color: #047857;
  font-size: 12.5px;
  font-weight: 600;
  border: 1px solid #a7f3d0;
}
.warn-notice { 
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  background: #fffbeb;
  color: #b45309;
  font-size: 12.5px;
  font-weight: 600;
  border: 1px solid #fde68a;
}

.category-checkbox-tree-box {
  max-height: 180px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 14px;
  background: #ffffff;
}
.tree-parent-group { margin-bottom: 8px; }
.checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; padding: 4px 0; }
.checkbox-row input { width: 15px; height: 15px; cursor: pointer; accent-color: #2563eb; }
.parent-title { font-weight: 700; color: #1e293b; }
.tree-children-rows { margin-left: 20px; display: flex; flex-direction: column; gap: 2px; }
.child-row span { color: #475569; font-size: 12.5px; }

/* LOGO UPLOAD STYLES */
.logo-upload-wrap {
  display: flex;
  align-items: center;
  gap: 16px;
  background: #f8fafc;
  border: 1.5px dashed #cbd5e1;
  border-radius: 12px;
  padding: 14px 18px;
  transition: all 0.2s;
}
.logo-upload-wrap.is-dragging {
  border-color: #2563eb;
  background: #eff6ff;
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
.logo-preview-img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }
.logo-placeholder-text { font-size: 10.5px; font-weight: 600; color: #94a3b8; text-align: center; line-height: 1.2; padding: 0 4px; }
.upload-btn-wrap { display: flex; flex-direction: column; gap: 6px; }
.btn-upload-file {
  display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; font-size: 13px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s;
}
.btn-upload-file:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
.btn-upload-file svg { width: 14px; height: 14px; stroke: currentColor; }
.btn-remove-logo {
  padding: 8px 12px; border-radius: 8px; border: 1px solid #fca5a5; background: #fef2f2; font-size: 12.5px; font-weight: 600; color: #dc2626; cursor: pointer;
}
.btn-remove-logo:hover { background: #fee2e2; }
.file-info-text { font-size: 11px; color: #64748b; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
.upload-hint { font-size: 11.5px; color: #94a3b8; }

.modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #f1f5f9; background: #fafbff; }
.btn-cancel { padding: 10px 20px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; font-size: 13px; font-weight: 500; cursor: pointer; }
.btn-cancel:hover { background: #f1f5f9; }
.btn-save { padding: 10px 22px; border-radius: 10px; border: none; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.slide-up-leave-active { transition: all 0.2s ease; }
.slide-up-enter-from { opacity: 0; transform: translateY(30px) scale(0.97); }
.slide-up-leave-to { opacity: 0; transform: translateY(10px) scale(0.98); }

/* ══════════════════════════════════════════════════════════════ */
/* ── DARK MODE OVERRIDES ── */
/* ══════════════════════════════════════════════════════════════ */
:global(html[data-admin-theme='dark']) .page,
:global(.admin-layout.dark) .page,
:global(.dark) .page {
  background: #0b0f19;
  color: #f1f5f9;
}

:global(html[data-admin-theme='dark']) .hero-text h1,
:global(.admin-layout.dark) .hero-text h1,
:global(.dark) .hero-text h1 {
  color: #f8fafc;
}

:global(html[data-admin-theme='dark']) .hero-text p,
:global(.admin-layout.dark) .hero-text p,
:global(.dark) .hero-text p {
  color: #94a3b8;
}

:global(html[data-admin-theme='dark']) .search-box,
:global(.admin-layout.dark) .search-box,
:global(.dark) .search-box,
:global(html[data-admin-theme='dark']) .filter-select,
:global(.admin-layout.dark) .filter-select,
:global(.dark) .filter-select {
  background: #1e293b;
  border-color: #334155;
  color: #f8fafc;
}

:global(html[data-admin-theme='dark']) .table-card,
:global(.admin-layout.dark) .table-card,
:global(.dark) .table-card {
  background: #1e293b;
  border-color: #334155;
}

:global(html[data-admin-theme='dark']) thead tr,
:global(.admin-layout.dark) thead tr,
:global(.dark) thead tr {
  background: #0f172a;
  border-bottom-color: #334155;
}

:global(html[data-admin-theme='dark']) th,
:global(.admin-layout.dark) th,
:global(.dark) th {
  color: #94a3b8;
}

:global(html[data-admin-theme='dark']) tbody tr,
:global(.admin-layout.dark) tbody tr,
:global(.dark) tbody tr {
  border-bottom-color: #334155;
}

:global(html[data-admin-theme='dark']) tbody tr:hover,
:global(.admin-layout.dark) tbody tr:hover,
:global(.dark) tbody tr:hover {
  background: #334155;
}

:global(html[data-admin-theme='dark']) tbody tr.row-selected,
:global(.admin-layout.dark) tbody tr.row-selected,
:global(.dark) tbody tr.row-selected {
  background: #1e3a8a;
}

:global(html[data-admin-theme='dark']) .stt-cell,
:global(.admin-layout.dark) .stt-cell,
:global(.dark) .stt-cell {
  color: #94a3b8;
}

:global(html[data-admin-theme='dark']) .cat-name,
:global(.admin-layout.dark) .cat-name,
:global(.dark) .cat-name {
  color: #f1f5f9 !important;
}

:global(html[data-admin-theme='dark']) .brand-logo-cell,
:global(.admin-layout.dark) .brand-logo-cell,
:global(.dark) .brand-logo-cell {
  background: #0f172a !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .bg-global,
:global(.admin-layout.dark) .bg-global,
:global(.dark) .bg-global {
  background: rgba(16, 185, 129, 0.15) !important;
  color: #34d399 !important;
  border-color: rgba(16, 185, 129, 0.3) !important;
}

:global(html[data-admin-theme='dark']) .bg-cat,
:global(.admin-layout.dark) .bg-cat,
:global(.dark) .bg-cat {
  background: rgba(59, 130, 246, 0.15) !important;
  color: #60a5fa !important;
  border-color: rgba(59, 130, 246, 0.3) !important;
}

:global(html[data-admin-theme='dark']) .action-btn,
:global(.admin-layout.dark) .action-btn,
:global(.dark) .action-btn {
  background: #1e293b !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .action-btn svg,
:global(.admin-layout.dark) .action-btn svg,
:global(.dark) .action-btn svg {
  stroke: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .modal,
:global(.admin-layout.dark) .modal,
:global(.dark) .modal {
  background: #1e293b !important;
  color: #f8fafc !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7) !important;
}

:global(html[data-admin-theme='dark']) .modal-header,
:global(.admin-layout.dark) .modal-header,
:global(.dark) .modal-header,
:global(html[data-admin-theme='dark']) .modal-footer,
:global(.admin-layout.dark) .modal-footer,
:global(.dark) .modal-footer {
  border-color: #334155 !important;
  background: #1e293b !important;
}

:global(html[data-admin-theme='dark']) .modal-title,
:global(.admin-layout.dark) .modal-title,
:global(.dark) .modal-title {
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .modal-subtitle,
:global(.admin-layout.dark) .modal-subtitle,
:global(.dark) .modal-subtitle {
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .modal-close,
:global(.admin-layout.dark) .modal-close,
:global(.dark) .modal-close {
  background: #0f172a !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .modal-close svg,
:global(.admin-layout.dark) .modal-close svg,
:global(.dark) .modal-close svg {
  stroke: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .form-label,
:global(.admin-layout.dark) .form-label,
:global(.dark) .form-label {
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) .form-input,
:global(.admin-layout.dark) .form-input,
:global(.dark) .form-input {
  background: #0f172a !important;
  border-color: #334155 !important;
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .scope-toggle-wrap,
:global(.admin-layout.dark) .scope-toggle-wrap,
:global(.dark) .scope-toggle-wrap {
  background: #0f172a !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .scope-title,
:global(.admin-layout.dark) .scope-title,
:global(.dark) .scope-title {
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .scope-sub,
:global(.admin-layout.dark) .scope-sub,
:global(.dark) .scope-sub {
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .toggle-label,
:global(.admin-layout.dark) .toggle-label,
:global(.dark) .toggle-label {
  color: #60a5fa !important;
}

:global(html[data-admin-theme='dark']) .selected-category-badges-box,
:global(.admin-layout.dark) .selected-category-badges-box,
:global(.dark) .selected-category-badges-box {
  background: #0f172a !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .box-title,
:global(.admin-layout.dark) .box-title,
:global(.dark) .box-title {
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .all-cats-notice,
:global(.admin-layout.dark) .all-cats-notice,
:global(.dark) .all-cats-notice {
  background: rgba(16, 185, 129, 0.15) !important;
  color: #34d399 !important;
  border-color: rgba(16, 185, 129, 0.3) !important;
}

:global(html[data-admin-theme='dark']) .all-cats-notice b,
:global(.admin-layout.dark) .all-cats-notice b,
:global(.dark) .all-cats-notice b {
  color: #6ee7b7 !important;
}

:global(html[data-admin-theme='dark']) .warn-notice,
:global(.admin-layout.dark) .warn-notice,
:global(.dark) .warn-notice {
  background: rgba(245, 158, 11, 0.15) !important;
  color: #fbbf24 !important;
  border-color: rgba(245, 158, 11, 0.3) !important;
}

:global(html[data-admin-theme='dark']) .parent-pill,
:global(.admin-layout.dark) .parent-pill,
:global(.dark) .parent-pill {
  background: #1e3a8a !important;
  color: #93c5fd !important;
  border-color: #1d4ed8 !important;
}

:global(html[data-admin-theme='dark']) .child-pill,
:global(.admin-layout.dark) .child-pill,
:global(.dark) .child-pill {
  background: #334155 !important;
  color: #cbd5e1 !important;
  border-color: #475569 !important;
}

:global(html[data-admin-theme='dark']) .category-checkbox-tree-box,
:global(.admin-layout.dark) .category-checkbox-tree-box,
:global(.dark) .category-checkbox-tree-box {
  background: #0f172a !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .parent-title,
:global(.admin-layout.dark) .parent-title,
:global(.dark) .parent-title {
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .child-row span,
:global(.admin-layout.dark) .child-row span,
:global(.dark) .child-row span {
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) .logo-upload-wrap,
:global(.admin-layout.dark) .logo-upload-wrap,
:global(.dark) .logo-upload-wrap {
  background: #0f172a !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .logo-upload-wrap.is-dragging,
:global(.admin-layout.dark) .logo-upload-wrap.is-dragging,
:global(.dark) .logo-upload-wrap.is-dragging {
  background: #1e3a8a !important;
  border-color: #3b82f6 !important;
}

:global(html[data-admin-theme='dark']) .logo-preview-box,
:global(.admin-layout.dark) .logo-preview-box,
:global(.dark) .logo-preview-box {
  background: #1e293b !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .logo-placeholder-text,
:global(.admin-layout.dark) .logo-placeholder-text,
:global(.dark) .logo-placeholder-text {
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .btn-upload-file,
:global(.admin-layout.dark) .btn-upload-file,
:global(.dark) .btn-upload-file,
:global(html[data-admin-theme='dark']) .btn-cancel,
:global(.admin-layout.dark) .btn-cancel,
:global(.dark) .btn-cancel {
  background: #0f172a !important;
  border-color: #334155 !important;
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) .btn-upload-file:hover,
:global(.admin-layout.dark) .btn-upload-file:hover,
:global(.dark) .btn-upload-file:hover,
:global(html[data-admin-theme='dark']) .btn-cancel:hover,
:global(.admin-layout.dark) .btn-cancel:hover,
:global(.dark) .btn-cancel:hover {
  background: #334155 !important;
  color: #f8fafc !important;
}
</style>

<style>
/* UN-SCOPED DARK MODE OVERRIDES FOR BRAND MODAL */
html[data-admin-theme='dark'] .scope-toggle-wrap,
.admin-layout.theme-dark .scope-toggle-wrap,
.admin-layout.dark .scope-toggle-wrap,
.dark .scope-toggle-wrap {
  background: #111827 !important;
  border-color: #374151 !important;
  color: #f9fafb !important;
}

html[data-admin-theme='dark'] .scope-title,
.admin-layout.theme-dark .scope-title,
.admin-layout.dark .scope-title,
.dark .scope-title {
  color: #f9fafb !important;
}

html[data-admin-theme='dark'] .scope-sub,
.admin-layout.theme-dark .scope-sub,
.admin-layout.dark .scope-sub,
.dark .scope-sub {
  color: #9ca3af !important;
}

html[data-admin-theme='dark'] .toggle-label,
.admin-layout.theme-dark .toggle-label,
.admin-layout.dark .toggle-label,
.dark .toggle-label {
  color: #60a5fa !important;
}

html[data-admin-theme='dark'] .selected-category-badges-box,
.admin-layout.theme-dark .selected-category-badges-box,
.admin-layout.dark .selected-category-badges-box,
.dark .selected-category-badges-box {
  background: #111827 !important;
  border-color: #374151 !important;
  color: #f9fafb !important;
}

html[data-admin-theme='dark'] .box-title,
.admin-layout.theme-dark .box-title,
.admin-layout.dark .box-title,
.dark .box-title {
  color: #9ca3af !important;
}

html[data-admin-theme='dark'] .all-cats-notice,
.admin-layout.theme-dark .all-cats-notice,
.admin-layout.dark .all-cats-notice,
.dark .all-cats-notice {
  background: rgba(16, 185, 129, 0.15) !important;
  color: #34d399 !important;
  border-color: rgba(16, 185, 129, 0.3) !important;
}

html[data-admin-theme='dark'] .all-cats-notice b,
.admin-layout.theme-dark .all-cats-notice b,
.admin-layout.dark .all-cats-notice b,
.dark .all-cats-notice b {
  color: #6ee7b7 !important;
}

html[data-admin-theme='dark'] .warn-notice,
.admin-layout.theme-dark .warn-notice,
.admin-layout.dark .warn-notice,
.dark .warn-notice {
  background: rgba(245, 158, 11, 0.15) !important;
  color: #fbbf24 !important;
  border-color: rgba(245, 158, 11, 0.3) !important;
}

html[data-admin-theme='dark'] .parent-pill,
.admin-layout.theme-dark .parent-pill,
.admin-layout.dark .parent-pill,
.dark .parent-pill {
  background: #1e3a8a !important;
  color: #93c5fd !important;
  border-color: #1d4ed8 !important;
}

html[data-admin-theme='dark'] .child-pill,
.admin-layout.theme-dark .child-pill,
.admin-layout.dark .child-pill,
.dark .child-pill {
  background: #374151 !important;
  color: #e5e7eb !important;
  border-color: #4b5563 !important;
}

html[data-admin-theme='dark'] .category-checkbox-tree-box,
.admin-layout.theme-dark .category-checkbox-tree-box,
.admin-layout.dark .category-checkbox-tree-box,
.dark .category-checkbox-tree-box {
  background: #111827 !important;
  border-color: #374151 !important;
  color: #f9fafb !important;
}

html[data-admin-theme='dark'] .parent-title,
.admin-layout.theme-dark .parent-title,
.admin-layout.dark .parent-title,
.dark .parent-title {
  color: #f9fafb !important;
}

html[data-admin-theme='dark'] .child-row span,
.admin-layout.theme-dark .child-row span,
.admin-layout.dark .child-row span,
.dark .child-row span {
  color: #d1d5db !important;
}

html[data-admin-theme='dark'] .logo-upload-wrap,
.admin-layout.theme-dark .logo-upload-wrap,
.admin-layout.dark .logo-upload-wrap,
.dark .logo-upload-wrap {
  background: #111827 !important;
  border-color: #374151 !important;
}

html[data-admin-theme='dark'] .logo-preview-box,
.admin-layout.theme-dark .logo-preview-box,
.admin-layout.dark .logo-preview-box,
.dark .logo-preview-box {
  background: #1f2937 !important;
  border-color: #374151 !important;
}
</style>
