<template>
  <div class="page">

    <!-- ═══════════════════════════════════
         VIEW: DANH SÁCH KHUYẾN MÃI
    ═══════════════════════════════════ -->
    <template v-if="currentView === 'list'">

      <!-- TOPBAR -->
      <div class="topbar">
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
          </svg>
          <input type="text" placeholder="Tìm kiếm chương trình..." v-model="searchQuery" />
        </div>
        <div class="topbar-right">
          <button class="icon-btn">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
              <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            <span class="notif-dot"></span>
          </button>
          <button class="icon-btn"><svg viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="3" />
              <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 19.07a10 10 0 0 1 0-14.14" />
            </svg></button>
          <div class="admin-info">
            <div>
              <p class="admin-name">Admin Vina</p>
              <p class="admin-role">Quản trị viên</p>
            </div>
            <div class="admin-avatar">AV</div>
          </div>
        </div>
      </div>

      <!-- TOAST -->
      <transition name="toast">
        <div class="toast" v-if="toast.show" :class="toast.type">
          <svg v-if="toast.type === 'success'" viewBox="0 0 24 24" fill="none">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          {{ toast.msg }}
        </div>
      </transition>

      <!-- STATS ROW -->
      <div class="stats-row">
        <div class="stat-card stat-active">
          <p class="stat-label">ĐANG HOẠT ĐỘNG</p>
          <h2 class="stat-value">{{ activeCount }}</h2>
          <p class="stat-sub green">↑ Tổng khuyến mãi: {{ promos.length }}</p>
        </div>
        <div class="stat-card stat-budget">
          <p class="stat-label">TỔNG NGÂN SÁCH SALE</p>
          <h2 class="stat-value">2.4B <span class="stat-unit">VNĐ</span></h2>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width:72%"></div>
          </div>
        </div>
        <div class="stat-card stat-card-gradient">
          <p class="stat-card-tag">Chiến dịch tiếp theo</p>
          <p class="stat-card-desc">Tết Nguyên Đán 2026 sẽ bắt đầu sau <strong>14 ngày nữa</strong>.</p>
          <button class="stat-card-btn">Xem chi tiết</button>
        </div>
      </div>

      <!-- LIST HEADER -->
      <div class="list-header">
        <div>
          <h2 class="list-title">Danh sách Khuyến mãi</h2>
          <p class="list-sub">Quản lý các chương trình ưu đãi và giảm giá toàn hệ thống.</p>
        </div>
        <div class="list-actions">
          <button class="btn-filter">
            <svg viewBox="0 0 24 24" fill="none">
              <line x1="4" y1="6" x2="20" y2="6" />
              <line x1="8" y1="12" x2="16" y2="12" />
              <line x1="11" y1="18" x2="13" y2="18" />
            </svg>
            Lọc
          </button>
          <button class="btn-primary" @click="openCreate">
            <svg viewBox="0 0 24 24" fill="none">
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Tạo mới
          </button>
        </div>
      </div>

      <BulkDeleteToolbar :selected-count="selectedIds.length" :total-count="filteredPromos.length" label="khuyến mãi"
        :loading="isBulkDeleting" @clear="clearSelection" @delete-selected="removeSelected"
        @delete-all="removeAllFiltered" />

      <!-- TABLE -->
      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th class="select-col">
                <input type="checkbox" :checked="allCurrentPageSelected" :disabled="!filteredPromos.length"
                  @change="toggleCurrentPageSelection" />
              </th>
              <th>CHƯƠNG TRÌNH</th>
              <th>LOẠI ƯU ĐÃI</th>
              <th>BẮT ĐẦU</th>
              <th>KẾT THÚC</th>
              <th>HÌNH THỨC</th>
              <th>TRẠNG THÁI</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="empty-row">
                <div class="loading-spinner"></div>
                Đang tải...
              </td>
            </tr>
            <tr v-else-if="filteredPromos.length === 0">
              <td colspan="8" class="empty-row">Không tìm thấy chương trình nào.</td>
            </tr>
            <tr v-else v-for="p in filteredPromos" :key="p.id" :class="{ 'row-selected': selectedIds.includes(p.id) }">
              <td class="select-col">
                <input type="checkbox" :checked="selectedIds.includes(p.id)" @change="toggleItemSelection(p.id)" />
              </td>
              <td>
                <div class="promo-name-cell">
                  <div class="promo-icon" :style="{ background: p.iconBg }">
                    <span>{{ p.icon }}</span>
                  </div>
                  <div>
                    <p class="promo-name">{{ p.ten }}</p>

                    <p class="promo-code">{{ p.code }}</p>
                  </div>
                </div>
              </td>
              <td>
                <span class="discount-tag" :style="{ background: p.tagBg, color: p.tagColor }">{{ p.discount ||
                  discountLabel(p) }}</span>
              </td>
              <td class="date-cell">{{ p.danhmuc === 'birthday' ? '—' : (p.ngaybatdau || '—') }}</td>
              <td class="date-cell">{{ p.danhmuc === 'birthday' ? '—' : (p.ngayketthuc || '—') }}</td>
              <td>
                <span :class="['status-badge', p.congkhai == 1 ? 'status-running' : 'status-open']">
                  {{ p.congkhai == 1 ? 'Công khai' : 'Có điều kiện' }}
                </span>
              </td>
              <td>
                <span :class="['status-badge', statusClass(p.trangthai)]">
                  {{ statusLabel(p.trangthai) }}
                </span>
              </td>
              <td>
                <div class="actions">
                  <button class="action-btn" @click="openEdit(p)" title="Sửa">
                    <svg viewBox="0 0 24 24" fill="none">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                  <button class="action-btn action-delete" @click="deletePromo(p.id)" title="Xóa">
                    <svg viewBox="0 0 24 24" fill="none">
                      <polyline points="3 6 5 6 21 6" />
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                      <path d="M10 11v6" />
                      <path d="M14 11v6" />
                      <path d="M9 6V4h6v2" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="pagination-row">
          <span class="page-info">Hiển thị 1-{{ filteredPromos.length }} trên <strong>{{ promos.length }}</strong>
            khuyến mãi</span>
          <div class="pagination">
            <button class="page-btn">‹</button>
            <button class="page-btn active">1</button>
            <button class="page-btn">›</button>
          </div>
        </div>
      </div>

      <!-- BOTTOM CARDS -->
      <div class="bottom-row">
        <div class="bottom-card">
          <div class="bottom-card-header">
            <h3>Chiến dịch hiệu quả nhất</h3>
            <button class="icon-btn-sm"><svg viewBox="0 0 24 24" fill="none">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
              </svg></button>
          </div>
          <div class="rank-list">
            <div class="rank-item" v-for="(r, i) in topPromos" :key="r.id">
              <span class="rank-num">#{{ i + 1 }}</span>
              <div class="rank-bar-wrap">
                <p class="rank-name">{{ r.ten }}</p>
                <div class="rank-bar">
                  <div class="rank-fill" :style="{ width: r.roi + '%', background: i === 0 ? '#4f46e5' : '#e2e8f0' }">
                  </div>
                </div>
              </div>
              <span class="rank-roi" :style="{ color: i === 0 ? '#2563eb' : '#3b82f6' }">+{{ r.roi }}% ROI</span>
            </div>
          </div>
        </div>

        <div class="bottom-card bottom-card-gradient">
          <button class="dist-add-btn">
            <svg viewBox="0 0 24 24" fill="none">
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
          </button>
          <p class="dist-label">HỆ THỐNG PHÂN PHỐI</p>
          <h2 class="dist-title">Phủ sóng 100% các kênh bán lẻ</h2>
          <div class="dist-stats">
            <div class="dist-stat">
              <p class="dist-num">85%</p>
              <p class="dist-sub">TRỰC TUYẾN</p>
            </div>
            <div class="dist-divider"></div>
            <div class="dist-stat">
              <p class="dist-num">15%</p>
              <p class="dist-sub">CỬA HÀNG</p>
            </div>
          </div>
        </div>
      </div>

    </template><!-- end list view -->

    <template v-if="currentView === 'promo-form'">
      <!-- Inline form header -->
      <div class="inline-form-header">
        <button class="back-btn" @click="closeModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
            <path d="M15 18l-6-6 6-6" />
          </svg>
          Quay lại danh sách
        </button>
        <h1>{{ isEdit ? '✏️ Chỉnh sửa khuyến mãi' : '➕ Tạo khuyến mãi mới' }}</h1>
        <p>{{ isEdit ? 'Cập nhật thông tin chương trình ưu đãi' : 'Điền đầy đủ thông tin để tạo chương trình mới' }}</p>
      </div>

      <div class="inline-form-body">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Loại Voucher <span class="req">*</span></label>
            <select class="form-input" v-model="form.danhmuc" @change="onCategoryChange">
              <option value="product">Giảm giá sản phẩm</option>
              <option value="birthday">Mã Sinh nhật</option>
              <option value="freeship">Miễn phí vận chuyển (Freeship)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tên Voucher <span class="req">*</span></label>
            <input class="form-input" :class="{ err: errors.ten }" v-model="form.ten" placeholder="VD: Tết 2026 Sale"
              @input="autoCode" />
            <p class="err-msg" v-if="errors.ten">{{ errors.ten }}</p>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Mã Voucher <span class="req">*</span></label>
          <div class="code-input-row">
            <input class="form-input mono" :class="{ err: errors.code }" v-model="form.code" placeholder="VD: TET-2026"
              style="text-transform:uppercase" :readonly="form.danhmuc === 'birthday'" />
            <button type="button" class="btn-gen-code" @click="generateRandomCode"
              :disabled="form.danhmuc === 'birthday'">Tạo ngẫu nhiên</button>
          </div>
          <p class="err-msg" v-if="errors.code">{{ errors.code }}</p>
          <p class="form-hint">Mã sẽ tự động sinh khi bạn gõ tên. Bạn cũng có thể bấm nút Tạo ngẫu nhiên.</p>
        </div>

        <div class="form-row" v-if="form.danhmuc !== 'freeship'">
          <div class="form-group">
            <label class="form-label">Loại ưu đãi <span class="req">*</span></label>
            <select class="form-input" v-model="form.loai" :disabled="form.danhmuc === 'birthday'">
              <option value="percent">Giảm %</option>
              <option value="fixed">Giảm theo giá tiền</option>
              <option value="maxprice">Giảm % tối đa</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Giá trị <span class="req">*</span></label>
            <div class="input-suffix-wrap">
              <input class="form-input" :class="{ err: errors.giatri }" type="text"
                :value="form.loai === 'percent' || form.loai === 'maxprice' ? form.giatri : formatVND(form.giatri)"
                @input="form.giatri = (form.loai === 'percent' || form.loai === 'maxprice') ? $event.target.value : parseVND($event.target.value)"
                placeholder="50" />
              <span class="input-suffix">{{ form.loai === 'percent' || form.loai === 'maxprice' ? '%' : 'VNĐ' }}</span>
            </div>
            <p class="err-msg" v-if="errors.giatri">{{ errors.giatri }}</p>
          </div>
        </div>

        <!-- Hình thức hiển thị / phát hành -->
        <div class="form-row">
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Hình thức hiển thị / phát hành <span class="req">*</span></label>
            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 8px;">
              <label style="cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <input type="radio" :value="1" v-model="form.congkhai" />
                Công khai (Hiện trên web)
              </label>
              <label style="cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <input type="radio" :value="0" v-model="form.congkhai" />
                Tặng khi đủ điều kiện
              </label>
            </div>
            <p class="form-hint">Tặng có điều kiện sẽ tự động tặng cho khách khi đặt hàng thành công.</p>
          </div>
        </div>

        <div class="form-row condition-row" style="background: #fffbfa; border: 1px dashed #f87171;"
          v-if="form.congkhai === 0">
          <div class="form-group">
            <label class="form-label">Mức đơn hàng để tặng (VNĐ)</label>
            <div class="input-suffix-wrap">
              <input class="form-input" type="text" :value="formatVND(form.dieu_kien_tang)"
                @input="form.dieu_kien_tang = parseVND($event.target.value)" placeholder="VD: 1.000.000" />
              <span class="input-suffix">đ</span>
            </div>
            <p class="form-hint">Tổng tiền phải thanh toán của đơn hàng (sau giảm giá) đạt mức này sẽ được tặng voucher.
            </p>
          </div>
          <div class="form-group">
            <label class="form-label">Giới hạn số lượng phát</label>
            <input class="form-input" v-model.number="form.so_luong_phat" type="number" min="1"
              placeholder="Để trống = Không giới hạn" />
            <p class="form-hint">Tổng số lượng khách được tặng mã này.</p>
          </div>
        </div>

        <!-- Điều kiện đơn hàng (để sử dụng voucher) -->
        <div class="form-row condition-row" :class="{ 'freeship-row': form.danhmuc === 'freeship' }"
          v-if="form.danhmuc === 'product' || form.danhmuc === 'freeship'">
          <div class="form-group" v-if="form.danhmuc === 'product'">
            <label class="form-label">
              <span class="condition-badge">🎯 Điều kiện đơn hàng</span>
            </label>
            <select class="form-input condition-select" v-model="form.loai_dieu_kien">
              <option value=">=">≥ Tạm tính từ (lớn hơn hoặc bằng)</option>
              <option value=">">＞ Tạm tính hơn (lớn hơn)</option>
              <option value="=">＝ Tạm tính bằng đúng</option>
            </select>
          </div>
          <div class="form-group" v-if="form.danhmuc === 'freeship'">
            <label class="form-label">
              <span class="condition-badge freeship-badge">🚚 Điều kiện miễn phí ship</span>
            </label>
            <p class="form-hint" style="margin-bottom:4px">Khách hàng phải đạt tạm tính tối thiểu mới dùng được mã
              freeship này.</p>
          </div>
          <div class="form-group">
            <label class="form-label">
              {{ form.danhmuc === 'freeship' ? 'Đơn hàng tối thiểu (VNĐ)' : 'Giá trị điều kiện (VNĐ)' }}
            </label>
            <div class="input-suffix-wrap">
              <input class="form-input condition-input"
                :class="{ err: errors.dieu_kien, 'freeship-input': form.danhmuc === 'freeship' }" type="text"
                :value="formatVND(form.dieu_kien)" @input="form.dieu_kien = parseVND($event.target.value)"
                :placeholder="form.danhmuc === 'freeship' ? 'VD: 300.000' : 'VD: 500.000'" />
              <span class="input-suffix">đ</span>
            </div>
            <p class="err-msg" v-if="errors.dieu_kien">{{ errors.dieu_kien }}</p>
            <p class="form-hint">
              {{ form.danhmuc === 'freeship'
                ? 'Để trống = freeship cho mọi đơn hàng. Nhập số để giới hạn điều kiện tối thiểu.'
                : 'Để trống nếu không cần điều kiện tạm tính.' }}
            </p>
          </div>
        </div>

        <!-- Ngày bắt đầu & Kết thúc -->
        <div class="form-row" v-if="form.danhmuc !== 'birthday'">
          <div class="form-group">
            <label class="form-label">Ngày bắt đầu</label>
            <input class="form-input" type="date" v-model="form.ngaybatdau" />
          </div>
          <div class="form-group">
            <label class="form-label">Ngày kết thúc</label>
            <input class="form-input" type="date" v-model="form.ngayketthuc" />
          </div>
        </div>
        <div class="form-group" v-if="form.danhmuc === 'birthday'">
          <div class="birthday-status-info">
            <span class="birthday-icon">BD</span>
            <span>Mã sinh nhật sẽ <strong>luôn mở</strong> và không có thời hạn.</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Mô tả</label>
          <textarea class="form-input form-textarea" v-model="form.mota" rows="4"
            placeholder="Mô tả ngắn về chương trình khuyến mãi..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Biểu tượng</label>
          <div class="icon-picker">
            <button v-for="ic in iconOptions" :key="ic.icon" class="icon-option"
              :class="{ 'icon-option-active': form.icon === ic.icon }" :style="{ background: ic.bg }"
              @click="form.icon = ic.icon; form.iconBg = ic.bg">{{ ic.icon }}</button>
          </div>
        </div>

        <!-- Inline footer actions -->
        <div class="inline-form-footer">
          <button class="btn-cancel" @click="closeModal">Hủy</button>
          <button class="btn-save" @click="savePromo" :disabled="saving">
            <svg v-if="saving" class="spin" viewBox="0 0 24 24" fill="none">
              <path d="M21 12a9 9 0 1 1-6.219-8.56" />
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
              <polyline points="17 21 17 13 7 13 7 21" />
              <polyline points="7 3 7 8 15 8" />
            </svg>
            {{ saving ? 'Đang lưu...' : (isEdit ? 'Lưu thay đổi' : 'Tạo khuyến mãi') }}
          </button>
        </div>

      </div><!-- end inline-form-body -->
    </template><!-- end promo-form -->

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'
import BulkDeleteToolbar from './ThanhXoaHangLoat.vue'
import { useAdminBulkDelete } from '@/services/adminBulkDelete'

const searchQuery = ref('')
const currentView = ref('list') // 'list' | 'promo-form'
const isEdit = ref(false)
const editId = ref(null)
const loading = ref(false)
const saving = ref(false)

// ── Toast ──────────────────────────────────────
const toast = ref({ show: false, msg: '', type: 'success' })
const showToast = (msg, type = 'success') => {
  toast.value = { show: true, msg, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const statusOptions = [
  { value: 'running', label: 'Đang chạy', color: '#2563eb', bg: '#f0fdf4' },
  { value: 'open', label: 'Luôn mở', color: '#2563eb', bg: '#eff6ff' },
]

const iconOptions = [
  { icon: 'SALE', bg: '#fef3c7' },
  { icon: 'BAG', bg: '#fce7f3' },
  { icon: 'EDU', bg: '#dbeafe' },
  { icon: 'FAST', bg: '#fef9c3' },
  { icon: 'GIFT', bg: '#d1fae5' },
  { icon: 'HOT', bg: '#fee2e2' },
  { icon: 'VIP', bg: '#ede9fe' },
  { icon: 'NEW', bg: '#e0f2fe' },
]

const defaultForm = () => ({
  ten: '', danhmuc: 'product', code: '', loai: 'percent', giatri: '',
  ngaybatdau: '', ngayketthuc: '', trangthai: 'running',
  mota: '', icon: 'SALE', iconBg: '#fef3c7',
  loai_dieu_kien: '>=', dieu_kien: '',
  congkhai: 1, dieu_kien_tang: '', so_luong_phat: ''
})

const form = ref(defaultForm())
const errors = ref({})
const promos = ref([])

// ================= FETCH DATA =================
const fetchPromos = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/promotions')
    promos.value = res.data.map(p => ({
      ...p,
      ngaybatdau: formatDate(p.ngaybatdau),
      ngayketthuc: formatDate(p.ngayketthuc),
      discount: discountLabel(p),
      ...tagColors(p.loai),
      icon: 'SALE',
      iconBg: '#fef3c7',
      roi: 20
    }))
  } catch (err) {
    swal.error('Lỗi', 'Lỗi tải danh sách khuyến mãi!')
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(fetchPromos)

// ================= COMPUTED =================
const filteredPromos = computed(() => {
  if (!searchQuery.value) return promos.value
  const q = searchQuery.value.toLowerCase()
  return promos.value.filter(p =>
    p.ten.toLowerCase().includes(q) ||
    p.code.toLowerCase().includes(q)
  )
})

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
  items: promos,
  filteredItems: filteredPromos,
  getId: item => item.id,
  endpoint: id => `/admin/promotions/${id}`,
  entityLabel: 'khuyến mãi',
  fetchItems: fetchPromos,
})

const activeCount = computed(() =>
  promos.value.filter(p => p.trangthai === 'running' || p.trangthai === 'open').length
)

const topPromos = computed(() =>
  [...promos.value].sort((a, b) => b.roi - a.roi).slice(0, 2)
)

// ================= HELPERS =================
function statusClass(s) {
  return { running: 'status-running', expired: 'status-expired', open: 'status-open' }[s] || ''
}

function statusLabel(s) {
  return { running: '● Đang chạy', expired: '◌ Hết hạn', open: '● Luôn mở' }[s] || s
}

function autoCode() {
  if (!isEdit.value && form.value.danhmuc !== 'birthday') {
    const base = form.value.ten
      .toUpperCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/Đ/gi, 'D')
      .replace(/[^A-Z0-9\s]/g, '')
      .trim().replace(/\s+/g, '-')
      .slice(0, 15)

    if (base) {
      form.value.code = base + '-' + Math.floor(1000 + Math.random() * 9000)
    }
  }
}

function generateRandomCode() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  let result = ''
  for (let i = 0; i < 8; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  form.value.code = result
}

function onCategoryChange() {
  if (form.value.danhmuc === 'freeship') {
    form.value.loai = 'percent'
    form.value.giatri = 100
    form.value.trangthai = 'running'
  } else if (form.value.danhmuc === 'birthday') {
    form.value.loai = 'fixed'
    form.value.code = 'BIRTHDAY'
    form.value.trangthai = 'open'
    form.value.ngaybatdau = ''
    form.value.ngayketthuc = ''
  } else {
    form.value.trangthai = 'running'
  }
}

function discountLabel(f) {
  if (f.loai === 'percent') return `Giảm ${f.giatri}%`
  if (f.loai === 'fixed') return `Cố định ${f.giatri}đ`
  if (f.loai === 'maxprice') return `giảm theo giá tiền  ${f.giatri}%`
  if (f.loai === 'freeship') return `Freeship ${f.giatri}đ`
  return ''
}

function tagColors(type) {
  return {
    percent: { tagBg: '#fef3c7', tagColor: '#92400e' },
    fixed: { tagBg: '#dbeafe', tagColor: '#1e40af' },
    maxprice: { tagBg: '#fef9c3', tagColor: '#854d0e' },
    freeship: { tagBg: '#dcfce7', tagColor: '#166534' },
  }[type] || {}
}

function formatDate(d) {
  if (!d) return ''
  const date = new Date(d)
  const dd = String(date.getDate()).padStart(2, '0')
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const yyyy = date.getFullYear()
  return `${dd}/${mm}/${yyyy}`
}

function formatVND(val) {
  if (!val && val !== 0) return '';
  const numStr = String(val).replace(/\D/g, '');
  if (!numStr) return '';
  return new Intl.NumberFormat('vi-VN').format(Number(numStr));
}

function parseVND(val) {
  if (!val) return '';
  return Number(String(val).replace(/\D/g, ''));
}

// ================= VALIDATE =================
function validate() {
  errors.value = {}
  if (!form.value.ten.trim()) errors.value.ten = 'Tên không được để trống.'
  if (!form.value.code.trim()) errors.value.code = 'Mã không được để trống.'
  if (form.value.giatri === '' || form.value.giatri === null)
    errors.value.giatri = 'Vui lòng nhập giá trị.'
  return Object.keys(errors.value).length === 0
}

// ================= CRUD =================
function openCreate() {
  isEdit.value = false
  editId.value = null
  form.value = defaultForm()
  errors.value = {}
  currentView.value = 'promo-form'
}

function openEdit(p) {
  isEdit.value = true
  editId.value = p.id

  // Chuyển dd/mm/yyyy → yyyy-mm-dd cho input[type=date]
  const toInputDate = (str) => {
    if (!str) return ''
    const parts = str.split('/')
    if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`
    return str
  }

  form.value = {
    ...p,
    danhmuc: p.danhmuc || 'product',
    ngaybatdau: toInputDate(p.ngaybatdau),
    ngayketthuc: toInputDate(p.ngayketthuc),
    loai_dieu_kien: p.loai_dieu_kien || '>=',
    dieu_kien: p.dieu_kien || '',
    congkhai: p.congkhai !== undefined ? Number(p.congkhai) : 1,
    dieu_kien_tang: p.dieu_kien_tang || '',
    so_luong_phat: p.so_luong_phat || ''
  }
  errors.value = {}
  currentView.value = 'promo-form'
}

function closeModal() {
  currentView.value = 'list'
}

async function savePromo() {
  if (!validate()) return

  saving.value = true

  // Birthday luôn mở, freeship/product dùng ngày
  const isBirthday = form.value.danhmuc === 'birthday'
  const data = {
    ten: form.value.ten,
    danhmuc: form.value.danhmuc,
    code: form.value.code.toUpperCase(),
    loai: form.value.loai,
    giatri: form.value.giatri,
    ngaybatdau: isBirthday ? null : (form.value.ngaybatdau || null),
    ngayketthuc: isBirthday ? null : (form.value.ngayketthuc || null),
    trangthai: isBirthday ? 'open' : 'running',
    mota: form.value.mota,
    loai_dieu_kien: form.value.danhmuc === 'product' ? (form.value.loai_dieu_kien || '>=') : null,
    dieu_kien: (form.value.danhmuc === 'product' || form.value.danhmuc === 'freeship') ? (form.value.dieu_kien || null) : null,
    congkhai: form.value.congkhai,
    dieu_kien_tang: form.value.congkhai === 0 ? (form.value.dieu_kien_tang || null) : null,
    so_luong_phat: form.value.congkhai === 0 ? (form.value.so_luong_phat || null) : null,
  }

  try {
    if (isEdit.value) {
      // PUT /api/admin/promotions/{id} — cần token admin
      await api.put(`/admin/promotions/${editId.value}`, data)
      swal.success('Cập nhật khuyến mãi thành công!')
    } else {
      // POST /api/admin/promotions — cần token admin
      await api.post('/admin/promotions', data)
      swal.success('Tạo khuyến mãi thành công!')
    }

    await fetchPromos()
    closeModal()

  } catch (err) {
    const msg = err.response?.data?.message || 'Lỗi khi lưu khuyến mãi!'
    swal.error('Lỗi', msg)
    console.error(err.response?.data)
  } finally {
    saving.value = false
  }
}

async function deletePromo(id) {
  const isConfirmed = await swal.confirm('Xác nhận xóa', 'Bạn chắc chắn muốn xóa khuyến mãi này?')
  if (!isConfirmed) return

  try {
    // DELETE /api/admin/promotions/{id} — cần token admin
    await api.delete(`/admin/promotions/${id}`)
    swal.success('Xóa khuyến mãi thành công!')
    await fetchPromos()
  } catch (err) {
    swal.error('Lỗi', 'Lỗi khi xóa khuyến mãi!')
    console.error(err)
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.page {
  padding: 20px 24px;
  background: #f0f4ff;
  min-height: 100vh;
  font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/* TOPBAR */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 8px 14px;
  width: 260px;
}

.search-box svg {
  width: 15px;
  height: 15px;
  stroke: #94a3b8;
  stroke-width: 2;
  fill: none;
  flex-shrink: 0;
}

.search-box input {
  border: none;
  outline: none;
  font-size: 13px;
  color: #1e293b;
  background: transparent;
  width: 100%;
}

.search-box input::placeholder {
  color: #94a3b8;
}

.topbar-right {
  display: none !important;
}

.icon-btn {
  position: relative;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.icon-btn svg {
  width: 16px;
  height: 16px;
  stroke: #64748b;
  stroke-width: 1.8;
  fill: none;
}

.notif-dot {
  position: absolute;
  top: 7px;
  right: 7px;
  width: 7px;
  height: 7px;
  background: #ef4444;
  border-radius: 50%;
  border: 1.5px solid #fff;
}

.admin-info {
  display: flex;
  align-items: center;
  gap: 9px;
}

.admin-name {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  text-align: right;
}

.admin-role {
  font-size: 11px;
  color: #94a3b8;
  text-align: right;
}

.admin-avatar {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #3b82f6, #3b82f6);
  border-radius: 50%;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* TOAST */
.toast {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 99999;
  padding: 12px 20px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.toast.success {
  background: #0f172a;
  color: #fff;
}

.toast.success svg {
  stroke: #4ade80;
}

.toast.error {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.toast.error svg {
  stroke: #dc2626;
}

.toast svg {
  width: 18px;
  height: 18px;
  stroke-width: 2.5;
  fill: none;
  flex-shrink: 0;
}

.toast-enter-active {
  transition: all 0.3s cubic-bezier(0.34, 1.4, 0.64, 1);
}

.toast-leave-active {
  transition: all 0.2s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(-12px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* STATS */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(220px, 1fr));
  gap: 20px;
}

.stat-card {
  min-height: 136px;
  background: #fff;
  border-radius: 16px;
  padding: 26px 28px;
  border: 1px solid transparent;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 10px;
  position: relative;
  overflow: hidden;
}

.stat-card.stat-active {
  background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
  border: none;
}

.stat-card.stat-active::after,
.stat-card.stat-budget::after,
.stat-card-gradient::after {
  content: '';
  position: absolute;
  width: 150px;
  height: 150px;
  border-radius: 999px;
  top: -54px;
  right: -28px;
  background: rgba(255, 255, 255, 0.13);
  pointer-events: none;
}

.stat-card.stat-active .stat-label {
  color: rgba(255, 255, 255, 0.88);
}

.stat-card.stat-active .stat-value {
  color: #fff;
}

.stat-card.stat-active .stat-sub.green {
  color: #ecfeff;
}

.stat-card.stat-budget {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  border: none;
}

.stat-card.stat-budget .stat-label {
  color: rgba(255, 255, 255, 0.88);
}

.stat-card.stat-budget .stat-value {
  color: #fff;
}

.stat-card.stat-budget .stat-unit {
  color: #dbeafe;
}

.stat-card.stat-budget .stat-bar {
  background: rgba(255, 255, 255, 0.22);
}

.stat-card.stat-budget .stat-bar-fill {
  background: linear-gradient(90deg, #bfdbfe, #ffffff);
}

.stat-label {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.03em;
  color: rgba(255, 255, 255, 0.88);
  text-transform: uppercase;
}

.stat-value {
  font-size: 34px;
  line-height: 1;
  font-weight: 800;
  color: #0f172a;
}

.stat-unit {
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
}

.stat-sub {
  font-size: 12px;
  color: #64748b;
}

.stat-sub.green {
  color: #2563eb;
  font-weight: 600;
}

.stat-bar {
  height: 5px;
  background: #dfe3e7;
  border-radius: 99px;
  overflow: hidden;
  margin-top: 4px;
}

.stat-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #3b82f6);
  border-radius: 99px;
}

.stat-card-gradient {
  background: linear-gradient(135deg, #0f2747 0%, #1e3a5f 55%, #0f172a 100%);
  border: none;
  justify-content: center;
}

.stat-card-tag {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.03em;
  color: rgba(255, 255, 255, 0.7);
  text-transform: uppercase;
}

.stat-card-desc {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.6;
}

.stat-card-desc strong {
  color: #fff;
}

.stat-card-btn {
  align-self: flex-start;
  padding: 7px 16px;
  border-radius: 20px;
  border: none;
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  backdrop-filter: blur(6px);
  transition: background 0.2s;
}

.stat-card-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* LIST HEADER */
.list-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
}

.list-title {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
}

.list-sub {
  font-size: 12.5px;
  color: #64748b;
  margin-top: 3px;
}

.list-actions {
  display: flex;
  gap: 10px;
}

.btn-filter {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 16px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
}

.btn-filter svg {
  width: 14px;
  height: 14px;
  stroke: #475569;
  stroke-width: 2;
  fill: none;
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
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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

/* TABLE */
.table-card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e8edf5;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
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
  padding: 12px 18px;
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

tbody tr:last-child {
  border-bottom: none;
}

tbody tr:hover {
  background: #fafbff;
}

tbody tr.row-selected {
  background: #eff6ff;
}

td {
  padding: 14px 18px;
  vertical-align: middle;
}

.select-col {
  width: 44px;
  text-align: center;
}

.select-col input {
  width: 16px;
  height: 16px;
  accent-color: #2563eb;
  cursor: pointer;
}

.promo-name-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.promo-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .03em;
  flex-shrink: 0;
}

.promo-name {
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
}

.promo-code {
  font-size: 11px;
  color: #94a3b8;
  font-family: monospace;
  margin-top: 2px;
}

.discount-tag {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 700;
}

.date-cell {
  font-size: 13px;
  color: #475569;
}

.status-badge {
  font-size: 11.5px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  display: inline-block;
}

.status-running {
  color: #2563eb;
  background: #dcfce7;
}

.status-expired {
  color: #dc2626;
  background: #fee2e2;
}

.status-open {
  color: #2563eb;
  background: #dbeafe;
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

.empty-row {
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
  padding: 28px;
}

.loading-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid #e2e8f0;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  margin-right: 8px;
  vertical-align: middle;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.pagination-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 13px 18px;
  border-top: 1px solid #f1f5f9;
}

.page-info {
  font-size: 12.5px;
  color: #64748b;
}

.pagination {
  display: flex;
  gap: 4px;
}

.page-btn {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 12.5px;
  color: #475569;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-btn.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
  font-weight: 600;
}

/* BOTTOM */
.bottom-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.bottom-card {
  background: #fff;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #e8edf5;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.bottom-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.bottom-card-header h3 {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}

.icon-btn-sm {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.icon-btn-sm svg {
  width: 14px;
  height: 14px;
  stroke: #64748b;
  stroke-width: 2;
  fill: none;
}

.rank-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.rank-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.rank-num {
  font-size: 12px;
  font-weight: 800;
  color: #2563eb;
  width: 24px;
  flex-shrink: 0;
}

.rank-bar-wrap {
  flex: 1;
}

.rank-name {
  font-size: 12.5px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 4px;
}

.rank-bar {
  height: 4px;
  background: #f1f5f9;
  border-radius: 99px;
  overflow: hidden;
}

.rank-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.6s ease;
}

.rank-roi {
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.bottom-card-gradient {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 60%, #93c5fd 100%);
  border: none;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
}

.dist-add-btn {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.dist-add-btn svg {
  width: 16px;
  height: 16px;
  stroke: #fff;
  stroke-width: 2.5;
  fill: none;
}

.dist-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  color: rgba(255, 255, 255, 0.6);
}

.dist-title {
  font-size: 22px;
  font-weight: 800;
  color: #fff;
  line-height: 1.3;
  margin: 8px 0 16px;
}

.dist-stats {
  display: flex;
  align-items: center;
  gap: 20px;
}

.dist-stat {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.dist-num {
  font-size: 28px;
  font-weight: 800;
  color: #fff;
}

.dist-sub {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.8px;
  color: rgba(255, 255, 255, 0.65);
}

.dist-divider {
  width: 1px;
  height: 40px;
  background: rgba(255, 255, 255, 0.25);
}

/* ═══ MODAL ═══ */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal {
  background: #fff;
  border-radius: 20px;
  width: 100%;
  max-width: 560px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 22px 18px;
  border-bottom: 1px solid #f1f5f9;
}

.modal-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-create {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}

.icon-edit {
  background: linear-gradient(135deg, #f59e0b, #f97316);
}

.modal-icon svg {
  width: 19px;
  height: 19px;
  stroke: #fff;
  stroke-width: 2.2;
  fill: none;
}

.modal-title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.modal-subtitle {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 2px;
}

.modal-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.modal-close svg {
  width: 14px;
  height: 14px;
  stroke: #64748b;
  stroke-width: 2;
  fill: none;
}

.modal-close:hover {
  background: #fee2e2;
}

.modal-close:hover svg {
  stroke: #ef4444;
}

.modal-body {
  padding: 20px 22px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.form-label {
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
}

.req {
  color: #ef4444;
}

.form-input {
  padding: 9px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  font-family: inherit;
  width: 100%;
  background: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input.err {
  border-color: #ef4444;
}

.form-textarea {
  resize: vertical;
  min-height: 72px;
}

.mono {
  font-family: monospace;
}

select.form-input {
  cursor: pointer;
}

.err-msg {
  font-size: 11.5px;
  color: #ef4444;
}

.form-hint {
  font-size: 11px;
  color: #94a3b8;
}

.input-suffix-wrap {
  position: relative;
}

.input-suffix-wrap .form-input {
  padding-right: 44px;
}

.input-suffix {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 12px;
  font-weight: 600;
  color: #94a3b8;
  pointer-events: none;
}

/* CONDITION ROW */
.condition-row {
  margin-top: 2px;
  background: #f5f3ff;
  border: 1.5px dashed #a5b4fc;
  border-radius: 12px;
  padding: 12px 14px;
  grid-template-columns: 1.3fr 1fr;
}

.condition-row.freeship-row {
  background: #f0fdf4;
  border-color: #86efac;
}

.condition-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
  background: #ede9fe;
  padding: 3px 10px;
  border-radius: 20px;
}

.freeship-badge {
  color: #1E40AF;
  background: #d1fae5;
}

.condition-select option {
  font-size: 12.5px;
}

.condition-input {
  border-color: #c4b5fd !important;
}

.condition-input:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
}

.condition-input.freeship-input {
  border-color: #6ee7b7 !important;
}

.condition-input.freeship-input:focus {
  border-color: #1D4ED8 !important;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12) !important;
}

.toggle-group {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.toggle-btn {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 8px 14px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  background: #f8fafc;
  font-size: 12.5px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}

.toggle-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #cbd5e1;
  flex-shrink: 0;
}

.icon-picker {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.icon-option {
  width: 48px;
  height: 40px;
  border-radius: 10px;
  border: 2px solid transparent;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .03em;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.icon-option:hover {
  border-color: rgba(37, 99, 235, .35);
}

.icon-option-active {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 22px;
  border-top: 1px solid #f1f5f9;
  background: #fafbff;
}

.btn-cancel {
  padding: 9px 18px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  background: #fff;
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  font-family: inherit;
}

.btn-save {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 9px 20px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
  transition: transform 0.15s;
  font-family: inherit;
}

.btn-save:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn-save:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-save svg {
  width: 14px;
  height: 14px;
  stroke: #fff;
  stroke-width: 2;
  fill: none;
}

.spin {
  animation: spin 0.7s linear infinite;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.slide-up-leave-active {
  transition: all 0.2s ease;
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(28px) scale(0.97);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.98);
}

.birthday-status-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border: 1.5px solid #f59e0b;
  border-radius: 12px;
  font-size: 13px;
  color: #92400e;
}

.birthday-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(146, 64, 14, .12);
  font-size: 11px;
  font-weight: 800;
  flex-shrink: 0;
}

.birthday-status-info strong {
  color: #b45309;
}

/* ═══ INLINE FORM ═══ */
.inline-form-header {
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.inline-form-header h1 {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  margin: 8px 0 4px;
}

.inline-form-header p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: none;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 7px 14px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 12px;
}

.back-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #0f172a;
}

.inline-form-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.inline-form-body>.form-group,
.inline-form-body>.form-row>.form-group {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #edf0f7;
  padding: 20px 22px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.inline-form-body>.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.inline-form-body .form-label {
  font-size: 11.5px;
  font-weight: 700;
  color: #6b7280;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.inline-form-body .form-input {
  padding: 11px 14px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  font-size: 14px;
  color: #0f172a;
  outline: none;
  transition: all 0.2s;
  background: #f9fafb;
  font-family: inherit;
  width: 100%;
  box-sizing: border-box;
}

.inline-form-body .form-input:focus {
  border-color: #2563eb;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.inline-form-body .form-input.err {
  border-color: #f87171;
  background: #fff5f5;
}

.inline-form-body .form-textarea {
  resize: vertical;
  min-height: 90px;
}

.code-input-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.code-input-row .form-input {
  flex: 1;
}

.btn-gen-code {
  padding: 0 16px;
  height: 44px;
  background: #f1f5f9;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-gen-code:hover:not(:disabled) {
  background: #e2e8f0;
  border-color: #94a3b8;
}

.btn-gen-code:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.inline-form-body>.condition-row {
  background: #f5f3ff;
  border: 1.5px dashed #a5b4fc;
  border-radius: 14px;
  padding: 16px 18px;
}

.inline-form-body>.condition-row.freeship-row {
  background: #f0fdf4;
  border-color: #86efac;
}

.inline-form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
  margin-top: 8px;
}
</style>
