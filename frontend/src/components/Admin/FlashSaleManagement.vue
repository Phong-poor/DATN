<template>
  <div class="page">
    <!-- ══════════════════════════════════════════════════════
         VIEW 1: DANH SÁCH ĐỢT FLASH SALE & SẢN PHẨM TRONG ĐỢT
    ══════════════════════════════════════════════════════ -->
    <template v-if="currentView === 'list'">
      <div class="top">
        <div>
          <h1>Quản lý Flash Sale</h1>
          <p>Lên lịch và thiết lập các khung giờ vàng giảm giá sốc cho sản phẩm</p>
        </div>
        <div class="excel-actions">
          <button class="add-btn" @click="openCreateSession">+ Tạo Đợt Flash Sale</button>
        </div>
      </div>

      <!-- TABS CHÍNH -->
      <div class="category-tabs">
        <button :class="['cat-tab', { active: activeTab === 'sessions' }]" @click="activeTab = 'sessions'">
          Danh sách Đợt Flash Sale
        </button>
        <button :class="['cat-tab', { active: activeTab === 'products' }]" @click="switchTabToProducts" :disabled="!selectedSession">
          Sản phẩm trong đợt: {{ selectedSession ? selectedSession.ten_dot : 'Chưa chọn' }}
        </button>
      </div>

      <!-- TAB 1: DANH SÁCH ĐỢT FLASH SALE -->
      <div v-if="activeTab === 'sessions'" class="table-card">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>TÊN ĐỢT SALE</th>
              <th>THỜI GIAN BẮT ĐẦU</th>
              <th>THỜI GIAN KẾT THÚC</th>
              <th>SỐ SẢN PHẨM</th>
              <th>TRẠNG THÁI</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in sessions" :key="s.id_session">
              <td>#{{ s.id_session }}</td>
              <td class="cat-name">{{ s.ten_dot }}</td>
              <td>{{ formatDateTime(s.thoi_gian_bat_dau) }}</td>
              <td>{{ formatDateTime(s.thoi_gian_ket_thuc) }}</td>
              <td><b>{{ s.products_count || 0 }}</b> sản phẩm</td>
              <td>
                <span :class="getSessionStatusClass(s)">
                  {{ getSessionStatusText(s) }}
                </span>
              </td>
              <td>
                <div class="actions">
                  <button class="action-btn select-btn" @click="manageSessionProducts(s)" title="Quản lý sản phẩm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M5 20h.01M19 4H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zM9 9l3 3-3 3"/></svg>
                  </button>
                  <button class="action-btn edit-btn" @click="openEditSession(s)" title="Chỉnh sửa đợt">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button class="action-btn action-delete delete-btn" @click="deleteSession(s)" title="Xóa đợt">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="sessions.length === 0 && !isLoading">
              <td colspan="7" class="empty-row">Không tìm thấy đợt Flash Sale nào.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- TAB 2: SẢN PHẨM TRONG ĐỢT ĐANG CHỌN -->
      <div v-if="activeTab === 'products'" class="tab-products-container">
        <div class="session-info-card">
          <div class="session-meta">
            <h3>Đợt Flash Sale: {{ selectedSession.ten_dot }}</h3>
            <p>
              Thời gian: <b>{{ formatDateTime(selectedSession.thoi_gian_bat_dau) }}</b> đến 
              <b>{{ formatDateTime(selectedSession.thoi_gian_ket_thuc) }}</b>
            </p>
          </div>
          <button class="add-btn" @click="openAddProductsModal">+ Thêm sản phẩm vào đợt sale</button>
        </div>

        <div class="table-card">
          <table>
            <thead>
              <tr>
                <th>Hình ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Cấu hình / Màu</th>
                <th>Giá gốc</th>
                <th>Giá Flash Sale</th>
                <th>Giới hạn kho</th>
                <th>Đã bán</th>
                <th>Tiến trình</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in sessionProducts" :key="p.id_sanpham_flashsale">
                <td class="prod-img-td">
                  <img :src="p.bien_the?.hinhanh ? getStorageUrl(p.bien_the.hinhanh) : (p.bien_the?.san_pham?.hinhanh ? getStorageUrl(p.bien_the.san_pham.hinhanh) : 'https://via.placeholder.com/100')" alt="" class="table-thumb" />
                </td>
                <td>
                  <p class="cat-name">{{ p.bien_the?.san_pham?.tenSP || 'N/A' }}</p>
                  <p style="font-size: 12px; color: #64748b;">Hãng: {{ p.bien_the?.san_pham?.thuong_hieu?.ten_thuonghieu || 'N/A' }}</p>
                </td>
                <td>
                  <span class="spec-tag">{{ p.bien_the?.ten_bienthe }}</span>
                </td>
                <td>{{ formatPrice(p.bien_the?.gia) }}</td>
                <td style="color: #ef4444; font-weight: 700;">{{ formatPrice(p.gia_flash_sale) }}</td>
                <td><b>{{ p.so_luong_gioi_han }}</b> máy</td>
                <td><b>{{ p.so_luong_da_ban }}</b> máy</td>
                <td>
                  <div class="progress-wrap">
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: Math.min((p.so_luong_da_ban / p.so_luong_gioi_han) * 100, 100) + '%' }"></div>
                    </div>
                    <span class="progress-txt">{{ Math.round((p.so_luong_da_ban / p.so_luong_gioi_han) * 100) }}%</span>
                  </div>
                </td>
                <td>
                  <div class="actions">
                    <button class="action-btn action-delete delete-btn" @click="removeProductFromSession(p)" title="Xóa khỏi đợt sale">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="sessionProducts.length === 0 && !isLoadingProducts">
                <td colspan="9" class="empty-row">Chưa có sản phẩm nào được chọn trong đợt sale này.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════
         VIEW 2: FORM TẠO/SỬA ĐỢT FLASH SALE (INLINE VIEW)
    ══════════════════════════════════════════════════════ -->
    <template v-else-if="currentView === 'session-form'">
      <div class="inline-form-header">
        <button class="back-btn" @click="closeSessionModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M15 18l-6-6 6-6"/></svg>
          Quay lại danh sách
        </button>
        <h1>{{ isEditSession ? '✏️ Chỉnh sửa đợt Flash Sale' : '➕ Tạo đợt Flash Sale mới' }}</h1>
        <p>Thiết lập các thông số khung giờ vàng giảm giá cho cửa hàng</p>
      </div>

      <div class="inline-form-body">
        <div class="form-section-card">
          <div class="form-section-title">📅 Thông tin đợt Flash Sale</div>
          
          <div class="form-group">
            <label class="form-label">Tên đợt khuyến mãi <span class="required">*</span></label>
            <input class="form-input" type="text" v-model="sessionForm.ten_dot" placeholder="VD: Sale Sập Sàn Đêm Trăng Tròn" />
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Thời gian bắt đầu <span class="required">*</span></label>
              <input class="form-input" type="datetime-local" v-model="sessionForm.thoi_gian_bat_dau" />
            </div>
            <div class="form-group">
              <label class="form-label">Thời gian kết thúc <span class="required">*</span></label>
              <input class="form-input" type="datetime-local" v-model="sessionForm.thoi_gian_ket_thuc" />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Trạng thái đợt sale</label>
            <select class="form-input" v-model="sessionForm.trang_thai">
              <option :value="1">Kích hoạt (Cho phép hiển thị đợt sale)</option>
              <option :value="0">Tắt đợt sale</option>
            </select>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-cancel" @click="closeSessionModal">Hủy bỏ</button>
          <button class="btn-save" @click="saveSession">Lưu đợt sale</button>
        </div>
      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════
         VIEW 3: THÊM SẢN PHẨM VÀO ĐỢT SALE (INLINE VIEW)
    ══════════════════════════════════════════════════════ -->
    <template v-else-if="currentView === 'add-products'">
      <div class="inline-form-header">
        <button class="back-btn" @click="closeAddProductsModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M15 18l-6-6 6-6"/></svg>
          Quay lại danh sách
        </button>
        <h1>➕ Thêm sản phẩm vào đợt: {{ selectedSession?.ten_dot }}</h1>
        <p>Chọn cấu hình chi tiết và thiết lập giá giảm riêng biệt cho từng biến thể</p>
      </div>

      <div class="inline-form-body">
        <!-- CHỌN SẢN PHẨM MẸ QUA CÂY THƯ MỤC -->
        <div class="form-section-card">
          <div class="form-section-title">📦 Chọn sản phẩm muốn Flash Sale</div>
          
          <div class="selection-layout">
            <!-- Cột trái: Cây thư mục danh mục -->
            <div class="tree-sidebar">
              <div class="tree-title">Danh mục sản phẩm</div>
              
              <!-- Ô tìm kiếm danh mục -->
              <div class="tree-search-wrapper">
                <input 
                  v-model="treeSearchQuery" 
                  placeholder="Tìm kiếm danh mục..." 
                  class="tree-search-input"
                />
              </div>

              <!-- Cây danh mục -->
              <div class="tree-list-container">
                <div class="tree-all-node" :class="{ active: !selectedCategory }" @click="selectedCategory = null">
                  📂 Tất cả sản phẩm
                </div>
                
                <div v-for="parent in filteredTreeCategories" :key="parent.id_danhmuc_cha" class="tree-parent-node">
                  <div class="parent-label-row" @click="toggleParentExpand(parent.id_danhmuc_cha)">
                    <span class="chevron-icon" :class="{ expanded: isParentExpanded(parent.id_danhmuc_cha) }">▸</span>
                    <span class="parent-label" :class="{ active: selectedCategory === 'parent_' + parent.id_danhmuc_cha }">
                      💻 {{ parent.ten_danhmuc }}
                    </span>
                  </div>
                  <div class="child-nodes-list" v-if="isParentExpanded(parent.id_danhmuc_cha)">
                    <div 
                      v-for="child in parent.children" 
                      :key="child.id_danhmuc" 
                      class="child-node"
                      :class="{ active: selectedCategory === String(child.id_danhmuc) }"
                      @click.stop="selectedCategory = String(child.id_danhmuc)"
                    >
                      <span class="bullet-dot"></span>
                      {{ child.ten_danhmuc }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cột phải: Danh sách sản phẩm mẹ -->
            <div class="product-selection-list">
              <div class="prod-search-row">
                <div class="search-input-wrapper">
                  <input v-model="productSearchQuery" placeholder="Tìm sản phẩm theo tên, thương hiệu..." class="prod-search-input" />
                  <button v-if="productSearchQuery" @click="productSearchQuery = ''" class="clear-search-btn">✕</button>
                </div>
                <div class="selected-product-info" v-if="targetProduct">
                  Đang cấu hình: <b>{{ targetProduct.tenSP }}</b>
                </div>
              </div>

              <div class="products-grid-scroll">
                <div v-if="filteredProductsToSelect.length === 0" class="empty-products-msg">
                  Không tìm thấy sản phẩm nào trong danh mục này.
                </div>
                <div 
                  v-for="p in filteredProductsToSelect" 
                  :key="p.id_sanpham" 
                  class="product-item-card"
                  :class="{ active: targetProductId === p.id_sanpham }"
                  @click="selectTargetProduct(p)"
                >
                  <div class="prod-card-img-box">
                    <img :src="p.hinhanh ? getStorageUrl(p.hinhanh) : 'https://via.placeholder.com/100'" alt="" />
                  </div>
                  <div class="prod-card-info">
                    <span class="prod-card-brand">{{ p.thuong_hieu?.ten_thuonghieu || p.brand || 'Laptop' }}</span>
                    <span class="prod-card-title" :title="p.tenSP">{{ p.tenSP }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CÔNG CỤ ÁP DỤNG HÀNG LOẠT (BULK APPLY) - CHỈ HIỆN KHI ĐÃ CHỌN SẢN PHẨM MẸ -->
        <div v-if="variants.length > 0" class="form-section-card bulk-apply-card">
          <div class="bulk-apply-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Công Cụ Áp Dụng Hàng Loạt (Bulk Apply)
          </div>
          <p style="font-size: 12px; color: #475569; margin-bottom: 12px;">
            Lọc theo các thuộc tính của các biến thể, nhập giá trị và nhấn nút để tự điền nhanh cho tất cả dòng khớp điều kiện lọc.
          </p>
          <div class="bulk-apply-row">
            <div class="bulk-field">
              <label>Lọc RAM</label>
              <select v-model="bulkFilter.ram">
                <option value="">Tất cả</option>
                <option v-for="r in uniqueAttributes.rams" :key="r" :value="r">{{ r }}</option>
              </select>
            </div>
            <div class="bulk-field">
              <label>Lọc CPU</label>
              <select v-model="bulkFilter.cpu">
                <option value="">Tất cả</option>
                <option v-for="c in uniqueAttributes.cpus" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>
            <div class="bulk-field">
              <label>Lọc Màu sắc</label>
              <select v-model="bulkFilter.mausac">
                <option value="">Tất cả</option>
                <option v-for="m in uniqueAttributes.mausac" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>
            <div class="bulk-field">
              <label>Số tiền giảm (đ)</label>
              <input type="text" :value="formatCurrency(bulkData.gia_flash_sale)" @input="bulkData.gia_flash_sale = parseCurrency($event.target.value)" placeholder="VD: 2.000.000" />
            </div>
            <div class="bulk-field">
              <label>Giới hạn kho</label>
              <input type="number" v-model.number="bulkData.so_luong_gioi_han" placeholder="VD: 10" />
            </div>
            <button class="btn-bulk-apply" @click="applyBulkData">Áp dụng nhanh</button>
          </div>
        </div>

        <!-- BẢNG BIẾN THỂ ĐỂ ADMIN CẤU HÌNH -->
        <div v-if="variants.length > 0" class="form-section-card">
          <div class="form-section-title">⚙️ Cấu hình chi tiết biến thể Flash Sale</div>
          <div class="variants-table-container">
            <table class="variants-table">
              <thead>
                <tr>
                  <th class="select-col">
                    <input type="checkbox" :checked="isAllVariantsChecked" @change="toggleSelectAllVariants" />
                  </th>
                  <th>Cấu hình biến thể</th>
                  <th>Thuộc tính</th>
                  <th>Giá gốc</th>
                  <th>Tồn kho gốc</th>
                  <th>Giá Flash Sale (đ)</th>
                  <th>Kho Flash Sale</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="v in variants" :key="v.id_bienthe" :class="{ 'row-selected': v.selected }">
                  <td class="select-col">
                    <input type="checkbox" v-model="v.selected" />
                  </td>
                  <td class="variant-name-td"><b>{{ v.ten_bienthe }}</b></td>
                  <td>
                    <div class="attr-badges">
                      <span class="attr-badge r-badge" v-if="v.ram">RAM: {{ v.ram }}</span>
                      <span class="attr-badge c-badge" v-if="v.cpu">CPU: {{ v.cpu }}</span>
                      <span class="attr-badge m-badge" v-if="v.mausac">Màu: {{ v.mausac }}</span>
                    </div>
                  </td>
                  <td>{{ formatPrice(v.gia) }}</td>
                  <td>{{ v.soluong }} máy</td>
                  <td>
                    <input type="text" :value="formatCurrency(v.gia_flash_sale)" @input="v.gia_flash_sale = parseCurrency($event.target.value)" class="table-input" placeholder="Nhập giá sale" />
                  </td>
                  <td>
                    <input type="number" v-model.number="v.so_luong_gioi_han" class="table-input" placeholder="Nhập kho sale" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-else-if="targetProductId" class="form-section-card loading-box">
          Đang tải các cấu hình biến thể của sản phẩm...
        </div>

        <div v-else class="form-section-card select-empty-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;color:#94a3b8;margin-bottom:12px;"><circle cx="12" cy="12" r="10"/><path d="m9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          <p>Vui lòng chọn sản phẩm mẹ từ bảng ở trên để cấu hình các biến thể Flash Sale</p>
        </div>

        <div class="form-actions">
          <button class="btn-cancel" @click="closeAddProductsModal">Hủy bỏ</button>
          <button class="btn-save" @click="saveProductsToSession" :disabled="variants.length === 0">Lưu sản phẩm sale</button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'

// Navigation & views
const currentView = ref('list') // 'list' | 'session-form' | 'add-products'
const activeTab = ref('sessions') // 'sessions' or 'products'

// Data lists
const sessions = ref([])
const sessionProducts = ref([])
const allProducts = ref([])
const variants = ref([])

// Category tree & products select state
const categories = ref([])
const parentCategories = ref([])
const treeSearchQuery = ref('')
const expandedParentIds = ref(new Set())
const selectedCategory = ref(null) // 'parent_id' or 'child_id' or null
const productSearchQuery = ref('')

// Active states
const selectedSession = ref(null)
const isLoading = ref(true)
const isLoadingProducts = ref(false)
const targetProductId = ref('')

// Session Form state
const isEditSession = ref(false)
const sessionForm = ref({
  id_session: null,
  ten_dot: '',
  thoi_gian_bat_dau: '',
  thoi_gian_ket_thuc: '',
  trang_thai: 1
})

// Bulk Apply tool state
const bulkFilter = ref({
  ram: '',
  cpu: '',
  mausac: ''
})
const bulkData = ref({
  gia_flash_sale: '',
  so_luong_gioi_han: ''
})

// Lifecycle
onMounted(() => {
  fetchSessions()
  fetchAllProducts()
  fetchCategories()
  fetchParentCategories()
})

// Fetch Flash Sale sessions
const fetchSessions = async () => {
  isLoading.value = true
  try {
    const res = await api.get('/admin/flash-sales')
    sessions.value = res.data?.sessions || []
  } catch (e) {
    console.error('Lỗi khi tải danh sách đợt sale:', e)
  } finally {
    isLoading.value = false
  }
}

// Fetch public products (to select inside form)
const fetchAllProducts = async () => {
  try {
    const res = await api.get('/sanpham')
    allProducts.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Lỗi khi tải danh sách sản phẩm:', e)
  }
}

// Fetch categories for tree list
const fetchCategories = async () => {
  try {
    const res = await api.get('/danhmuc')
    categories.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Lỗi khi tải danh mục:', e)
  }
}

// Fetch parent categories
const fetchParentCategories = async () => {
  try {
    const res = await api.get('/danhmuc/parents')
    parentCategories.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Lỗi khi tải danh mục cha:', e)
  }
}

// Fetch products currently inside selected session
const fetchSessionProducts = async (sessionId) => {
  isLoadingProducts.value = true
  try {
    const res = await api.get(`/admin/flash-sales/${sessionId}`)
    sessionProducts.value = res.data?.products || []
  } catch (e) {
    console.error('Lỗi tải sản phẩm trong phiên:', e)
  } finally {
    isLoadingProducts.value = false
  }
}

// Category Tree structure
const categoriesTree = computed(() => {
  if (!parentCategories.value.length || !categories.value.length) return []
  return parentCategories.value.map(parent => {
    return {
      ...parent,
      children: categories.value.filter(child => String(child.id_danhmuc_cha) === String(parent.id_danhmuc_cha))
    }
  }).filter(parent => parent.children.length > 0)
})

const filteredTreeCategories = computed(() => {
  const query = treeSearchQuery.value.trim().toLowerCase()
  if (!query) {
    return categoriesTree.value
  }

  const result = []
  categoriesTree.value.forEach(parent => {
    const parentMatches = parent.ten_danhmuc.toLowerCase().includes(query)
    const matchingChildren = parent.children.filter(child => child.ten_danhmuc.toLowerCase().includes(query))

    if (parentMatches || matchingChildren.length > 0) {
      expandedParentIds.value.add(String(parent.id_danhmuc_cha))
      result.push({
        ...parent,
        children: parentMatches ? parent.children : matchingChildren
      })
    }
  })

  expandedParentIds.value = new Set(expandedParentIds.value)
  return result
})

const toggleParentExpand = (parentId) => {
  const pIdStr = String(parentId)
  if (expandedParentIds.value.has(pIdStr)) {
    expandedParentIds.value.delete(pIdStr)
  } else {
    expandedParentIds.value.add(pIdStr)
  }
  expandedParentIds.value = new Set(expandedParentIds.value)
  // Lọc theo parent category
  selectedCategory.value = 'parent_' + pIdStr
}

const isParentExpanded = (parentId) => {
  return expandedParentIds.value.has(String(parentId))
}

// Filter products based on selected category and productSearchQuery
const filteredProductsToSelect = computed(() => {
  return allProducts.value.filter(p => {
    // 1. Filter by category tree selection
    if (selectedCategory.value) {
      const catVal = selectedCategory.value
      if (catVal.startsWith('parent_')) {
        const parentId = catVal.replace('parent_', '')
        // Find matching child categories
        const matchedCat = categories.value.find(c => String(c.id_danhmuc) === String(p.id_danhmuc))
        if (!matchedCat || String(matchedCat.id_danhmuc_cha) !== String(parentId)) {
          return false
        }
      } else {
        // Child category filter
        if (String(p.id_danhmuc) !== String(catVal)) {
          return false
        }
      }
    }

    // 2. Filter by name/brand search query
    if (productSearchQuery.value) {
      const q = productSearchQuery.value.trim().toLowerCase()
      const nameMatch = p.tenSP?.toLowerCase().includes(q)
      const brandName = p.thuong_hieu?.ten_thuonghieu || p.brand || ''
      const brandMatch = brandName.toLowerCase().includes(q)
      return nameMatch || brandMatch
    }

    return true
  })
})

const targetProduct = computed(() => {
  if (!targetProductId.value) return null
  return allProducts.value.find(p => p.id_sanpham === targetProductId.value) || null
})

const selectTargetProduct = (prod) => {
  targetProductId.value = prod.id_sanpham
  onProductSelected()
}

// Navigate to products tab for a session
const manageSessionProducts = (session) => {
  selectedSession.value = session
  fetchSessionProducts(session.id_session)
  activeTab.value = 'products'
}

// Fallback safety for tab switching
const switchTabToProducts = () => {
  if (selectedSession.value) {
    activeTab.value = 'products'
  }
}

// Format Datetime
const formatDateTime = (dateStr) => {
  if (!dateStr) return 'Chưa xác định'
  const date = new Date(dateStr)
  return date.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPrice = (p) => {
  if (!p) return '0đ'
  return new Intl.NumberFormat('vi-VN').format(p) + 'đ'
}

const formatCurrency = (val) => {
  if (val === undefined || val === null || val === '') return ''
  const numVal = Number(val)
  if (isNaN(numVal)) {
    const num = String(val).replace(/\D/g, '')
    if (!num) return ''
    return Number(num).toLocaleString('vi-VN')
  }
  return Math.round(numVal).toLocaleString('vi-VN')
}

const parseCurrency = (val) => {
  if (!val) return ''
  return String(val).replace(/\D/g, '')
}

const getStorageUrl = (path) => {
  if (!path) return 'https://via.placeholder.com/100'
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/${path}`
}

// Session statuses helper
const getSessionStatusText = (s) => {
  const now = new Date()
  const start = new Date(s.thoi_gian_bat_dau)
  const end = new Date(s.thoi_gian_ket_thuc)

  if (s.trang_thai === 0) return 'Tạm ẩn'
  if (now < start) return 'Sắp diễn ra'
  if (now > end) return 'Đã kết thúc'
  return 'Đang chạy'
}

const getSessionStatusClass = (s) => {
  const text = getSessionStatusText(s)
  if (text === 'Đang chạy') return 'status-active'
  if (text === 'Sắp diễn ra') return 'status-pending'
  if (text === 'Đã kết thúc') return 'status-expired'
  return 'status-hidden'
}

// Open Create Session View
const openCreateSession = () => {
  isEditSession.value = false
  sessionForm.value = {
    id_session: null,
    ten_dot: '',
    thoi_gian_bat_dau: '',
    thoi_gian_ket_thuc: '',
    trang_thai: 1
  }
  currentView.value = 'session-form'
}

// Open Edit Session View
const openEditSession = (session) => {
  isEditSession.value = true
  
  // Format local datetimes for input type="datetime-local"
  const formatDateForInput = (dateStr) => {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    const hours = String(d.getHours()).padStart(2, '0')
    const minutes = String(d.getMinutes()).padStart(2, '0')
    return `${year}-${month}-${day}T${hours}:${minutes}`
  }

  sessionForm.value = {
    id_session: session.id_session,
    ten_dot: session.ten_dot,
    thoi_gian_bat_dau: formatDateForInput(session.thoi_gian_bat_dau),
    thoi_gian_ket_thuc: formatDateForInput(session.thoi_gian_ket_thuc),
    trang_thai: session.trang_thai
  }
  currentView.value = 'session-form'
}

const closeSessionModal = () => {
  currentView.value = 'list'
}

// Save Session (Store / Update)
const saveSession = async () => {
  const f = sessionForm.value
  if (!f.ten_dot || !f.thoi_gian_bat_dau || !f.thoi_gian_ket_thuc) {
    swal.error('Lỗi', 'Vui lòng nhập đầy đủ các trường bắt buộc!')
    return
  }

  try {
    if (isEditSession.value) {
      await api.put(`/admin/flash-sales/${f.id_session}`, f)
      swal.success('Thành công', 'Đã cập nhật thông tin đợt Flash Sale!')
    } else {
      await api.post('/admin/flash-sales', f)
      swal.success('Thành công', 'Đã tạo đợt Flash Sale mới!')
    }
    currentView.value = 'list'
    fetchSessions()
  } catch (e) {
    console.error('Lỗi lưu đợt sale:', e)
    swal.error('Lỗi', e.response?.data?.message || 'Không thể lưu đợt sale!')
  }
}

// Delete Session
const deleteSession = async (session) => {
  const confirm = await swal.confirm(
    'Xác nhận xóa',
    `Bạn có chắc chắn muốn xóa đợt Flash Sale "${session.ten_dot}"? Thao tác này sẽ xóa tất cả sản phẩm sale thuộc đợt này.`
  )
  if (confirm) {
    try {
      await api.delete(`/admin/flash-sales/${session.id_session}`)
      swal.success('Thành công', 'Đã xóa đợt Flash Sale!')
      if (selectedSession.value?.id_session === session.id_session) {
        selectedSession.value = null
        activeTab.value = 'sessions'
      }
      fetchSessions()
    } catch (e) {
      console.error('Lỗi xóa đợt sale:', e)
      swal.error('Lỗi', 'Không thể xóa đợt sale này.')
    }
  }
}

// Open Add Products View
const openAddProductsModal = () => {
  targetProductId.value = ''
  variants.value = []
  selectedCategory.value = null
  treeSearchQuery.value = ''
  productSearchQuery.value = ''
  bulkFilter.value = { ram: '', cpu: '', mausac: '' }
  bulkData.value = { gia_flash_sale: '', so_luong_gioi_han: '' }
  currentView.value = 'add-products'
}

const closeAddProductsModal = () => {
  currentView.value = 'list'
}

// Fetch variants of selected product
const onProductSelected = async () => {
  if (!targetProductId.value) return
  variants.value = []
  try {
    const res = await api.get(`/bienthe/sanpham/${targetProductId.value}`)
    const list = res.data || []
    
    // Parse attributes for easy display/filtering
    variants.value = list.map(v => {
      let ram = ''
      let cpu = ''
      let mausac = ''
      
      try {
        const tt = typeof v.thuoc_tinh_json === 'string' 
            ? JSON.parse(v.thuoc_tinh_json) 
            : (v.thuoc_tinh_json || [])
        if (Array.isArray(tt)) {
          tt.forEach(attr => {
            const name = strtolower(attr.ten_thuoctinh || '')
            if (name.includes('ram')) ram = attr.giatri
            if (name.includes('cpu')) cpu = attr.giatri
            if (name.includes('màu')) mausac = attr.giatri
          })
        }
      } catch (err) {}

      // Find if this variant is already sale configured in this session to pre-fill
      const existing = sessionProducts.value.find(sp => sp.id_bienthe === v.id_bienthe)

      return {
        id_bienthe: v.id_bienthe,
        ten_bienthe: v.ten_bienthe,
        gia: v.gia,
        soluong: v.soluong,
        ram,
        cpu,
        mausac,
        selected: !!existing,
        gia_flash_sale: existing ? existing.gia_flash_sale : Math.round(v.gia * 0.8), // 20% discount as default suggestion
        so_luong_gioi_han: existing ? existing.so_luong_gioi_han : 5
      }
    })
  } catch (e) {
    console.error('Lỗi tải biến thể:', e)
    swal.error('Lỗi', 'Không thể tải các cấu hình của sản phẩm này.')
  }
}

// Extract unique attributes for bulk filters
const uniqueAttributes = computed(() => {
  const rams = new Set()
  const cpus = new Set()
  const mausac = new Set()

  variants.value.forEach(v => {
    if (v.ram) rams.add(v.ram)
    if (v.cpu) cpus.add(v.cpu)
    if (v.mausac) mausac.add(v.mausac)
  })

  return {
    rams: Array.from(rams),
    cpus: Array.from(cpus),
    mausac: Array.from(mausac)
  }
})

// Bulk selection checkbox helper
const isAllVariantsChecked = computed(() => {
  return variants.value.length > 0 && variants.value.every(v => v.selected)
})

const toggleSelectAllVariants = (e) => {
  const checked = e.target.checked
  variants.value.forEach(v => {
    v.selected = checked
  })
}

// Helper: lowercase string safe
const strtolower = (str) => {
  return (str || '').toLowerCase()
}

// Bulk Apply execution
const applyBulkData = () => {
  const f = bulkFilter.value
  const d = bulkData.value

  if (!d.gia_flash_sale && !d.so_luong_gioi_han) {
    swal.info('Lưu ý', 'Vui lòng nhập ít nhất Số tiền giảm hoặc Giới hạn kho để áp dụng!')
    return
  }

  let count = 0
  variants.value.forEach(v => {
    // Check if matches the filters
    const matchRam = !f.ram || v.ram === f.ram
    const matchCpu = !f.cpu || v.cpu === f.cpu
    const matchMausac = !f.mausac || v.mausac === f.mausac

    if (matchRam && matchCpu && matchMausac) {
      if (d.gia_flash_sale) {
        // Calculate: Flash Sale Price = Original Price - Discount Amount
        v.gia_flash_sale = Math.max(v.gia - Number(d.gia_flash_sale), 0)
      }
      if (d.so_luong_gioi_han) v.so_luong_gioi_han = Number(d.so_luong_gioi_han)
      v.selected = true // Automatically select matched rows
      count++
    }
  })

  swal.success('Thành công', `Đã áp dụng thông số hàng loạt cho ${count} cấu hình phù hợp!`)
}

// Save products to database session
const saveProductsToSession = async () => {
  const selected = variants.value.filter(v => v.selected)

  if (selected.length === 0) {
    swal.error('Lỗi', 'Vui lòng tích chọn ít nhất 1 cấu hình biến thể sản phẩm để sale!')
    return
  }

  // Validate values
  for (let v of selected) {
    if (!v.gia_flash_sale || Number(v.gia_flash_sale) <= 0) {
      swal.error('Lỗi', `Cấu hình "${v.ten_bienthe}" phải có giá Flash Sale lớn hơn 0.`)
      return
    }
    if (!v.so_luong_gioi_han || v.so_luong_gioi_han <= 0) {
      swal.error('Lỗi', `Cấu hình "${v.ten_bienthe}" phải có kho giới hạn lớn hơn 0.`)
      return
    }
  }

  const payload = {
    products: selected.map(v => ({
      id_bienthe: v.id_bienthe,
      gia_flash_sale: Number(v.gia_flash_sale),
      so_luong_gioi_han: Number(v.so_luong_gioi_han)
    }))
  }

  try {
    await api.post(`/admin/flash-sales/${selectedSession.value.id_session}/products`, payload)
    swal.success('Thành công', 'Đã thêm các cấu hình sản phẩm vào đợt Flash Sale!')
    currentView.value = 'list'
    fetchSessionProducts(selectedSession.value.id_session)
  } catch (e) {
    console.error('Lỗi lưu sản phẩm sale:', e)
    swal.error('Lỗi', 'Không thể lưu cấu hình sản phẩm.')
  }
}

// Remove variant from session
const removeProductFromSession = async (prod) => {
  const confirm = await swal.confirm(
    'Xác nhận xóa',
    `Xóa sản phẩm "${prod.bien_the?.san_pham?.tenSP} (${prod.bien_the?.ten_bienthe})" khỏi đợt Flash Sale này?`
  )
  if (confirm) {
    try {
      await api.delete(`/admin/flash-sales/${selectedSession.value.id_session}/products/${prod.id_sanpham_flashsale}`)
      swal.success('Thành công', 'Đã xóa sản phẩm khỏi đợt Flash Sale.')
      fetchSessionProducts(selectedSession.value.id_session)
    } catch (e) {
      console.error('Lỗi khi xóa sản phẩm sale:', e)
      swal.error('Lỗi', 'Không thể xóa sản phẩm khỏi đợt sale.')
    }
  }
}
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

.top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.top h1 {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
}

.top p {
  font-size: 13px;
  color: #64748b;
  margin-top: 4px;
}

.add-btn {
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity .2s, transform .2s;
}

.add-btn:hover {
  opacity: .9;
  transform: translateY(-1px);
}

/* Category Tabs */
.category-tabs { 
  display: flex; 
  gap: 12px; 
  border-bottom: 2px solid #e2e8f0; 
  padding-bottom: 0; 
}

.cat-tab { 
  background: transparent; 
  border: none; 
  padding: 12px 20px; 
  font-size: 14px; 
  font-weight: 600; 
  color: #64748b; 
  cursor: pointer; 
  border-bottom: 2px solid transparent; 
  margin-bottom: -2px; 
  transition: all 0.2s; 
}

.cat-tab:hover:not(:disabled) { 
  color: #4f46e5; 
}

.cat-tab.active { 
  color: #4f46e5; 
  border-bottom-color: #4f46e5; 
}

.cat-tab:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Table Card */
.table-card { 
  background: #fff; 
  border-radius: 16px; 
  border: 1px solid #e8edf5; 
  overflow: hidden; 
  box-shadow: 0 2px 12px rgba(0,0,0,0.04); 
}

table { 
  width: 100%; 
  border-collapse: collapse; 
}

thead tr { 
  background: #f8faff; 
  border-bottom: 1px solid #e8edf5; 
}

th { 
  padding: 13px 20px; 
  font-size: 11px; 
  font-weight: 700; 
  color: #94a3b8; 
  letter-spacing: 0.6px; 
  text-align: left; 
}

tbody tr { 
  border-bottom: 1px solid #f1f5f9; 
  transition: background 0.15s; 
}

tbody tr:hover { 
  background: #fafbff; 
}

td { 
  padding: 16px 20px; 
  vertical-align: middle; 
  font-size: 13.5px;
  color: #334155;
}

.cat-name { 
  font-size: 14px; 
  font-weight: 600; 
  color: #1e293b; 
}

/* Status Badges */
.status-active,
.status-pending,
.status-expired,
.status-hidden {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
}

.status-active { color: #15803d; background: #dcfce7; border: 1px solid #86efac; }
.status-pending { color: #2563eb; background: #dbeafe; border: 1px solid #93c5fd; }
.status-expired { color: #b45309; background: #fef3c7; border: 1px solid #fde68a; }
.status-hidden { color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; }

.actions { display: flex; gap: 6px; }

.action-btn { 
  width: 32px; 
  height: 32px; 
  border-radius: 8px; 
  border: 1px solid #e2e8f0; 
  background: #fff; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  cursor: pointer; 
  transition: all 0.15s; 
}

.action-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.action-btn svg { width: 14px; height: 14px; stroke: #64748b; stroke-width: 1.8; fill: none; }
.action-delete:hover { background: #fef2f2; border-color: #fca5a5; }
.action-delete:hover svg { stroke: #ef4444; }
.select-btn:hover { background: #eff6ff; border-color: #93c5fd; }
.select-btn:hover svg { stroke: #2563eb; }

.empty-row { text-align: center; color: #94a3b8; font-size: 13px; padding: 40px; }

/* Inline Forms styling */
.inline-form-header {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 8px;
  background: white;
  padding: 24px;
  border-radius: 16px;
  border: 1px solid #e8edf5;
  box-shadow: 0 2px 12px rgba(0,0,0,0.02);
}

.inline-form-header h1 {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 10px;
}

.inline-form-header p {
  font-size: 13px;
  color: #64748b;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: none;
  color: #2563eb;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  width: fit-content;
  margin-bottom: 4px;
  transition: color 0.15s;
}

.back-btn:hover {
  color: #1d4ed8;
}

.inline-form-body {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e8edf5;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.02);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-section-title {
  font-size: 15px;
  font-weight: 700;
  color: #1e293b;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 12px;
  margin-bottom: 4px;
}

.form-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

@media (max-width: 768px) {
  .form-grid-2 {
    grid-template-columns: 1fr;
  }
}

.form-group { 
  display: flex; 
  flex-direction: column; 
  gap: 6px; 
}

.form-label { 
  font-size: 13px; 
  font-weight: 600; 
  color: #374151; 
}

.required { 
  color: #ef4444; 
}

.form-input { 
  padding: 11px 14px; 
  border: 1.5px solid #e2e8f0; 
  border-radius: 10px; 
  font-size: 13.5px; 
  color: #1e293b; 
  width: 100%; 
  outline: none; 
  transition: all 0.15s;
}

.form-input:focus { 
  border-color: #2563eb; 
  box-shadow: 0 0 0 3px rgba(37,99,235,0.1); 
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: white;
  padding: 16px 24px;
  border-radius: 16px;
  border: 1px solid #e8edf5;
  box-shadow: 0 2px 12px rgba(0,0,0,0.02);
}

.btn-cancel { 
  padding: 11px 22px; 
  border-radius: 10px; 
  border: 1.5px solid #e2e8f0; 
  background: #fff; 
  font-size: 13px; 
  font-weight: 600; 
  color: #475569;
  cursor: pointer; 
  transition: background 0.15s;
}

.btn-cancel:hover { 
  background: #f8fafc; 
}

.btn-save { 
  padding: 11px 24px; 
  border-radius: 10px; 
  border: none; 
  background: linear-gradient(135deg, #2563eb, #4f46e5); 
  color: #fff; 
  font-size: 13px; 
  font-weight: 600; 
  cursor: pointer; 
  transition: opacity .15s;
}

.btn-save:hover { 
  opacity: 0.95; 
}

.btn-save:disabled { 
  opacity: 0.5; 
  cursor: not-allowed; 
}

/* Layout 2 cột cho phần chọn sản phẩm */
.selection-layout {
  display: flex;
  gap: 24px;
  margin-top: 12px;
  min-height: 400px;
}

@media (max-width: 992px) {
  .selection-layout {
    flex-direction: column;
  }
  .tree-sidebar {
    width: 100% !important;
    max-height: 250px;
  }
}

.tree-sidebar {
  width: 280px;
  min-width: 280px;
  border-right: 1px solid #e2e8f0;
  padding-right: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.tree-title {
  font-size: 13px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tree-search-wrapper {
  position: relative;
}

.tree-search-input {
  width: 100%;
  padding: 8px 12px;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12.5px;
  outline: none;
  transition: all 0.15s;
}

.tree-search-input:focus {
  border-color: #2563eb;
}

.tree-list-container {
  overflow-y: auto;
  flex: 1;
  max-height: 350px;
  padding-right: 4px;
}

.tree-all-node {
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: background 0.15s;
  margin-bottom: 6px;
}

.tree-all-node:hover, .tree-all-node.active {
  background: #eff6ff;
  color: #2563eb;
}

.tree-parent-node {
  margin-bottom: 8px;
}

.parent-label-row {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s;
}

.parent-label-row:hover {
  background: #f8fafc;
}

.chevron-icon {
  font-size: 10px;
  color: #64748b;
  transition: transform 0.2s;
  display: inline-block;
}

.chevron-icon.expanded {
  transform: rotate(90deg);
}

.parent-label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}

.parent-label.active {
  color: #2563eb;
}

.child-nodes-list {
  padding-left: 20px;
  margin-top: 4px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.child-node {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  border-radius: 6px;
  font-size: 12.5px;
  color: #64748b;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
}

.child-node:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.child-node.active {
  background: #eff6ff;
  color: #2563eb;
  font-weight: 600;
}

.child-node.active .bullet-dot {
  background: #2563eb;
  box-shadow: 0 0 6px #2563eb;
}

.bullet-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background-color: #cbd5e1;
  transition: all 0.15s;
  flex-shrink: 0;
}

/* Cột phải: danh sách sản phẩm mẹ */
.product-selection-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.prod-search-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.search-input-wrapper {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.prod-search-input {
  width: 100%;
  padding: 9px 12px;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  transition: all 0.15s;
}

.prod-search-input:focus {
  border-color: #2563eb;
}

.clear-search-btn {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: none;
  color: #94a3b8;
  cursor: pointer;
  font-size: 12px;
}

.clear-search-btn:hover {
  color: #ef4444;
}

.selected-product-info {
  font-size: 13px;
  color: #475569;
}

.selected-product-info b {
  color: #2563eb;
  background: #eff6ff;
  padding: 4px 8px;
  border-radius: 6px;
}

.products-grid-scroll {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
  overflow-y: auto;
  max-height: 350px;
  padding-right: 4px;
}

.empty-products-msg {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  color: #94a3b8;
  font-style: italic;
  font-size: 13.5px;
}

.product-item-card {
  border: 1.5px solid #e8edf5;
  border-radius: 12px;
  padding: 12px;
  display: flex;
  gap: 12px;
  align-items: center;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
}

.product-item-card:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
  transform: translateY(-1px);
}

.product-item-card.active {
  border-color: #2563eb;
  background: #eff6ff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
}

.prod-card-img-box {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  flex-shrink: 0;
}

.prod-card-img-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.prod-card-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
}

.prod-card-brand {
  font-size: 10px;
  font-weight: 700;
  color: #2563eb;
  text-transform: uppercase;
}

.prod-card-title {
  font-size: 12.5px;
  font-weight: 600;
  color: #1e293b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Table Card */
.table-card { 
  background: #fff; 
  border-radius: 16px; 
  border: 1px solid #e8edf5; 
  overflow: hidden; 
  box-shadow: 0 2px 12px rgba(0,0,0,0.04); 
}

table { 
  width: 100%; 
  border-collapse: collapse; 
}

thead tr { 
  background: #f8faff; 
  border-bottom: 1px solid #e8edf5; 
}

th { 
  padding: 13px 20px; 
  font-size: 11px; 
  font-weight: 700; 
  color: #94a3b8; 
  letter-spacing: 0.6px; 
  text-align: left; 
}

tbody tr { 
  border-bottom: 1px solid #f1f5f9; 
  transition: background 0.15s; 
}

tbody tr:hover { 
  background: #fafbff; 
}

td { 
  padding: 16px 20px; 
  vertical-align: middle; 
  font-size: 13.5px;
  color: #334155;
}

.cat-name { 
  font-size: 14px; 
  font-weight: 600; 
  color: #1e293b; 
}

/* Status Badges */
.status-active,
.status-pending,
.status-expired,
.status-hidden {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
}

.status-active { color: #15803d; background: #dcfce7; border: 1px solid #86efac; }
.status-pending { color: #2563eb; background: #dbeafe; border: 1px solid #93c5fd; }
.status-expired { color: #b45309; background: #fef3c7; border: 1px solid #fde68a; }
.status-hidden { color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; }

.actions { display: flex; gap: 6px; }

.action-btn { 
  width: 32px; 
  height: 32px; 
  border-radius: 8px; 
  border: 1px solid #e2e8f0; 
  background: #fff; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  cursor: pointer; 
  transition: all 0.15s; 
}

.action-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.action-btn svg { width: 14px; height: 14px; stroke: #64748b; stroke-width: 1.8; fill: none; }
.action-delete:hover { background: #fef2f2; border-color: #fca5a5; }
.action-delete:hover svg { stroke: #ef4444; }
.select-btn:hover { background: #eff6ff; border-color: #93c5fd; }
.select-btn:hover svg { stroke: #2563eb; }

.empty-row { text-align: center; color: #94a3b8; font-size: 13px; padding: 40px; }

/* Products Tab Content */
.session-info-card {
  background: white;
  border: 1px solid #e8edf5;
  border-radius: 16px;
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 12px rgba(0,0,0,0.02);
}

.session-meta h3 {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 6px;
}

.session-meta p {
  font-size: 13px;
  color: #64748b;
}

.tab-products-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.table-thumb {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  object-fit: cover;
  border: 1px solid #e2e8f0;
}

.prod-img-td {
  width: 60px;
  padding-right: 0;
}

.spec-tag {
  background: #f1f5f9;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
}

.progress-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 120px;
}

.progress-bar {
  flex: 1;
  height: 6px;
  background: #f1f5f9;
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #f59e0b, #ef4444);
  border-radius: 3px;
}

.progress-txt {
  font-size: 11px;
  font-weight: 700;
  color: #475569;
}

.loading-box {
  text-align: center;
  padding: 40px;
  color: #64748b;
  font-style: italic;
}

/* Bulk Apply card styling */
.bulk-apply-card {
  background: #f0f7ff;
  border: 1px dashed #2563eb;
  border-radius: 12px;
  padding: 20px 24px;
}

.bulk-apply-title {
  font-size: 14px;
  font-weight: 700;
  color: #1d4ed8;
  margin-bottom: 6px;
}

.bulk-apply-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: flex-end;
}

.bulk-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  min-width: 120px;
}

.bulk-field label {
  font-size: 11px;
  font-weight: 600;
  color: #475569;
}

.bulk-field select,
.bulk-field input {
  padding: 8px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12.5px;
  outline: none;
  background: white;
}

.bulk-field select:focus,
.bulk-field input:focus {
  border-color: #2563eb;
}

.btn-bulk-apply {
  background: #2563eb;
  color: white;
  border: none;
  padding: 9px 16px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  height: 35px;
  transition: background 0.15s;
}

.btn-bulk-apply:hover {
  background: #1d4ed8;
}

/* Variants Table styling */
.variants-table-container {
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  overflow: hidden;
  max-height: 400px;
  overflow-y: auto;
}

.variants-table {
  width: 100%;
  border-collapse: collapse;
}

.variants-table th {
  background: #f8fafc;
  padding: 12px 16px;
  font-size: 11px;
  color: #475569;
  border-bottom: 1px solid #cbd5e1;
}

.variants-table td {
  padding: 12px 16px;
  font-size: 13px;
  border-bottom: 1px solid #e2e8f0;
}

.variants-table tr.row-selected {
  background: #f0f7ff;
}

.variant-name-td {
  font-size: 13px;
  color: #0f172a;
}

.table-input {
  width: 100%;
  padding: 6px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 12.5px;
  outline: none;
}

.table-input:focus {
  border-color: #2563eb;
}

.attr-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.attr-badge {
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
}

.r-badge { background: #fef3c7; color: #d97706; }
.c-badge { background: #e0f2fe; color: #0284c7; }
.m-badge { background: #fce7f3; color: #db2777; }
</style>
