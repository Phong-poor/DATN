<template>
  <div class="page">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="text" placeholder="Tìm kiếm chương trình..." v-model="searchQuery"/>
      </div>
      <div class="topbar-right">
        <button class="icon-btn">
          <svg viewBox="0 0 24 24" fill="none"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="notif-dot"></span>
        </button>
        <button class="icon-btn"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 19.07a10 10 0 0 1 0-14.14"/></svg></button>
        <div class="admin-info">
          <div>
            <p class="admin-name">Admin Vina</p>
            <p class="admin-role">Quản trị viên</p>
          </div>
          <div class="admin-avatar">AV</div>
        </div>
      </div>
    </div>

    <!-- STATS ROW -->
    <div class="stats-row">
      <div class="stat-card">
        <p class="stat-label">ĐANG HOẠT ĐỘNG</p>
        <h2 class="stat-value">{{ activeCount }}</h2>
        <p class="stat-sub green">↑ +2 tháng này</p>
      </div>
      <div class="stat-card">
        <p class="stat-label">TỔNG NGÂN SÁCH SALE</p>
        <h2 class="stat-value">2.4B <span class="stat-unit">VNĐ</span></h2>
        <div class="stat-bar"><div class="stat-bar-fill" style="width:72%"></div></div>
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
          <svg viewBox="0 0 24 24" fill="none"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
          Lọc
        </button>
        <button class="btn-primary" @click="openCreate">
          <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tạo mới
        </button>
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>CHƯƠNG TRÌNH</th>
            <th>LOẠI ƯU ĐÃI</th>
            <th>BẮT ĐẦU</th>
            <th>KẾT THÚC</th>
            <th>TRẠNG THÁI</th>
            <th>THAO TÁC</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in filteredPromos" :key="p.id">
            <td>
              <div class="promo-name-cell">
                <div class="promo-icon" :style="{ background: p.iconBg }">
                  <span>{{ p.icon }}</span>
                </div>
                <div>
                  <p class="promo-name">{{ p.name }}</p>
                  <p class="promo-code">{{ p.code }}</p>
                </div>
              </div>
            </td>
            <td>
              <span class="discount-tag" :style="{ background: p.tagBg, color: p.tagColor }">{{ p.discount }}</span>
            </td>
            <td class="date-cell">{{ p.startDate }}</td>
            <td class="date-cell">{{ p.endDate }}</td>
            <td>
              <span :class="['status-badge', statusClass(p.status)]">
                {{ statusLabel(p.status) }}
              </span>
            </td>
            <td>
              <div class="actions">
                <button class="action-btn" @click="openEdit(p)" title="Sửa">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button class="action-btn action-delete" @click="deletePromo(p.id)" title="Xóa">
                  <svg viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredPromos.length === 0">
            <td colspan="6" class="empty-row">Không tìm thấy chương trình nào.</td>
          </tr>
        </tbody>
      </table>

      <div class="pagination-row">
        <span class="page-info">Hiển thị 1-{{ filteredPromos.length }} trên <strong>{{ promos.length }}</strong> khuyến mãi</span>
        <div class="pagination">
          <button class="page-btn">‹</button>
          <button class="page-btn active">1</button>
          <button class="page-btn">2</button>
          <button class="page-btn">3</button>
          <button class="page-btn">›</button>
        </div>
      </div>
    </div>

    <!-- BOTTOM CARDS -->
    <div class="bottom-row">
      <!-- Best campaign -->
      <div class="bottom-card">
        <div class="bottom-card-header">
          <h3>Chiến dịch hiệu quả nhất</h3>
          <button class="icon-btn-sm"><svg viewBox="0 0 24 24" fill="none"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></button>
        </div>
        <div class="rank-list">
          <div class="rank-item" v-for="(r, i) in topPromos" :key="r.id">
            <span class="rank-num">#{{ i + 1 }}</span>
            <div class="rank-bar-wrap">
              <p class="rank-name">{{ r.name }}</p>
              <div class="rank-bar"><div class="rank-fill" :style="{ width: r.roi + '%', background: i === 0 ? '#4f46e5' : '#e2e8f0' }"></div></div>
            </div>
            <span class="rank-roi" :style="{ color: i === 0 ? '#4f46e5' : '#22c55e' }">+{{ r.roi }}% ROI</span>
          </div>
        </div>
      </div>

      <!-- Distribution -->
      <div class="bottom-card bottom-card-gradient">
        <button class="dist-add-btn">
          <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
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

    <!-- ═══════════ MODAL ═══════════ -->
    <transition name="fade">
      <div class="overlay" v-if="showModal" @click.self="closeModal">
        <transition name="slide-up">
          <div class="modal" v-if="showModal">

            <div class="modal-header">
              <div class="modal-header-left">
                <div class="modal-icon" :class="isEdit ? 'icon-edit' : 'icon-create'">
                  <svg v-if="!isEdit" viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <div>
                  <h3 class="modal-title">{{ isEdit ? 'Chỉnh sửa khuyến mãi' : 'Tạo khuyến mãi mới' }}</h3>
                  <p class="modal-subtitle">{{ isEdit ? 'Cập nhật thông tin chương trình ưu đãi' : 'Điền đầy đủ thông tin để tạo chương trình mới' }}</p>
                </div>
              </div>
              <button class="modal-close" @click="closeModal">
                <svg viewBox="0 0 24 24" fill="none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>

            <div class="modal-body">

              <!-- Tên -->
              <div class="form-group">
                <label class="form-label">Tên chương trình <span class="req">*</span></label>
                <input class="form-input" :class="{ err: errors.name }" v-model="form.name" placeholder="VD: Tết 2026 Sale" @input="autoCode"/>
                <p class="err-msg" v-if="errors.name">{{ errors.name }}</p>
              </div>

              <!-- Mã code -->
              <div class="form-group">
                <label class="form-label">Mã chương trình <span class="req">*</span></label>
                <input class="form-input mono" :class="{ err: errors.code }" v-model="form.code" placeholder="VD: TET-2026" style="text-transform:uppercase"/>
                <p class="err-msg" v-if="errors.code">{{ errors.code }}</p>
                <p class="form-hint">Mã ngắn dùng nhận dạng hệ thống. Chỉ hoa, số, dấu gạch ngang.</p>
              </div>

              <!-- Loại + Giá trị -->
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Loại ưu đãi <span class="req">*</span></label>
                  <select class="form-input" v-model="form.type">
                    <option value="percent">Giảm %</option>
                    <option value="fixed">Cố định</option>
                    <option value="maxprice">Lên đến</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Giá trị <span class="req">*</span></label>
                  <div class="input-suffix-wrap">
                    <input class="form-input" :class="{ err: errors.value }" v-model.number="form.value" type="number" min="0" placeholder="50"/>
                    <span class="input-suffix">{{ form.type === 'percent' || form.type === 'maxprice' ? '%' : 'VNĐ' }}</span>
                  </div>
                  <p class="err-msg" v-if="errors.value">{{ errors.value }}</p>
                </div>
              </div>

              <!-- Ngày bắt đầu + kết thúc -->
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Ngày bắt đầu <span class="req">*</span></label>
                  <input class="form-input" :class="{ err: errors.startDate }" type="date" v-model="form.startDate"/>
                  <p class="err-msg" v-if="errors.startDate">{{ errors.startDate }}</p>
                </div>
                <div class="form-group">
                  <label class="form-label">Ngày kết thúc <span class="req">*</span></label>
                  <input class="form-input" :class="{ err: errors.endDate }" type="date" v-model="form.endDate"/>
                  <p class="err-msg" v-if="errors.endDate">{{ errors.endDate }}</p>
                </div>
              </div>

              <!-- Trạng thái -->
              <div class="form-group">
                <label class="form-label">Trạng thái</label>
                <div class="toggle-group">
                  <button v-for="s in statusOptions" :key="s.value"
                    class="toggle-btn"
                    :class="{ 'toggle-active': form.status === s.value }"
                    :style="form.status === s.value ? { borderColor: s.color, background: s.bg, color: s.color } : {}"
                    @click="form.status = s.value">
                    <span class="toggle-dot" :style="form.status === s.value ? { background: s.color } : {}"></span>
                    {{ s.label }}
                  </button>
                </div>
              </div>

              <!-- Mô tả -->
              <div class="form-group">
                <label class="form-label">Mô tả</label>
                <textarea class="form-input form-textarea" v-model="form.description" rows="3" placeholder="Mô tả ngắn về chương trình khuyến mãi..."></textarea>
              </div>

              <!-- Icon -->
              <div class="form-group">
                <label class="form-label">Biểu tượng</label>
                <div class="icon-picker">
                  <button
                    v-for="ic in iconOptions" :key="ic.icon"
                    class="icon-option"
                    :class="{ 'icon-option-active': form.icon === ic.icon }"
                    :style="{ background: ic.bg }"
                    @click="form.icon = ic.icon; form.iconBg = ic.bg"
                  >{{ ic.icon }}</button>
                </div>
              </div>

            </div>

            <div class="modal-footer">
              <button class="btn-cancel" @click="closeModal">Hủy</button>
              <button class="btn-save" @click="savePromo">
                <svg viewBox="0 0 24 24" fill="none"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                {{ isEdit ? 'Lưu thay đổi' : 'Tạo khuyến mãi' }}
              </button>
            </div>

          </div>
        </transition>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const searchQuery = ref('')
const showModal = ref(false)
const isEdit = ref(false)
const editId = ref(null)

const statusOptions = [
  { value: 'running', label: 'Đang chạy', color: '#16a34a', bg: '#f0fdf4' },
  { value: 'expired', label: 'Hết hạn',   color: '#dc2626', bg: '#fef2f2' },
  { value: 'open',    label: 'Luôn mở',   color: '#2563eb', bg: '#eff6ff' },
]

const iconOptions = [
  { icon: '🏮', bg: '#fef3c7' },
  { icon: '🛍️', bg: '#fce7f3' },
  { icon: '🎓', bg: '#dbeafe' },
  { icon: '⚡', bg: '#fef9c3' },
  { icon: '🎁', bg: '#d1fae5' },
  { icon: '🔥', bg: '#fee2e2' },
  { icon: '💎', bg: '#ede9fe' },
  { icon: '🚀', bg: '#e0f2fe' },
]

const defaultForm = () => ({
  name: '', code: '', type: 'percent', value: '',
  startDate: '', endDate: '', status: 'running',
  description: '', icon: '🏮', iconBg: '#fef3c7',
})

const form = ref(defaultForm())
const errors = ref({})
const promos = ref([])


// ================= FETCH DATA =================
const fetchPromos = async () => {
  try {
    const res = await axios.get('http://localhost:8000/api/promotions')

    promos.value = res.data.map(p => ({
      ...p,
      startDate: formatDate(p.start_date),
      endDate: formatDate(p.end_date),
      discount: discountLabel(p),
      ...tagColors(p.type),
      icon: '🏮',
      iconBg: '#fef3c7',
      roi: 20
    }))

  } catch (err) {
    console.error(err)
  }
}

onMounted(fetchPromos)


// ================= COMPUTED =================
const filteredPromos = computed(() => {
  if (!searchQuery.value) return promos.value
  const q = searchQuery.value.toLowerCase()
  return promos.value.filter(p =>
    p.name.toLowerCase().includes(q) ||
    p.code.toLowerCase().includes(q)
  )
})

const activeCount = computed(() =>
  promos.value.filter(p => p.status === 'running' || p.status === 'open').length
)

const topPromos = computed(() =>
  [...promos.value].sort((a, b) => b.roi - a.roi).slice(0, 2)
)


// ================= FUNCTIONS =================
function statusClass(s) {
  return {
    running: 'status-running',
    expired: 'status-expired',
    open: 'status-open'
  }[s] || ''
}

function statusLabel(s) {
  return {
    running: '● Đang chạy',
    expired: '◌ Hết hạn',
    open: '● Luôn mở'
  }[s] || s
}

function autoCode() {
  if (!isEdit.value) {
    form.value.code = form.value.name
      .toUpperCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/Đ/gi, 'D')
      .replace(/[^A-Z0-9\s]/g, '')
      .trim().replace(/\s+/g, '-')
      .slice(0, 20)
  }
}

function discountLabel(f) {
  if (f.type === 'percent') return `Giảm ${f.value}%`
  if (f.type === 'fixed') return `Cố định ${f.value}%`
  if (f.type === 'maxprice') return `Lên đến ${f.value}%`
  return ''
}

function tagColors(type) {
  return {
    percent:  { tagBg: '#fef3c7', tagColor: '#92400e' },
    fixed:    { tagBg: '#dbeafe', tagColor: '#1e40af' },
    maxprice: { tagBg: '#fef9c3', tagColor: '#854d0e' },
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


// ================= VALIDATE =================
function validate() {
  errors.value = {}

  if (!form.value.name.trim()) errors.value.name = 'Tên không được để trống.'
  if (!form.value.code.trim()) errors.value.code = 'Mã không được để trống.'
  if (form.value.value === '' || form.value.value === null)
    errors.value.value = 'Vui lòng nhập giá trị.'
  if (!form.value.startDate) errors.value.startDate = 'Chọn ngày bắt đầu.'
  if (!form.value.endDate) errors.value.endDate = 'Chọn ngày kết thúc.'

  return Object.keys(errors.value).length === 0
}


// ================= CRUD =================
function openCreate() {
  isEdit.value = false
  editId.value = null
  form.value = defaultForm()
  errors.value = {}
  showModal.value = true
}

function openEdit(p) {
  isEdit.value = true
  editId.value = p.id

  const [dd, mm, yyyy] = p.startDate.split('/')
  const [dd2, mm2, yyyy2] = p.endDate.split('/')

  form.value = {
    ...p,
    startDate: `${yyyy}-${mm}-${dd}`,
    endDate: `${yyyy2}-${mm2}-${dd2}`
  }

  errors.value = {}
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function savePromo() {
  if (!validate()) return

  const data = {
    name: form.value.name,
    code: form.value.code,
    type: form.value.type,
    value: form.value.value,
    start_date: form.value.startDate,
    end_date: form.value.endDate,
    status: form.value.status,
    description: form.value.description,
  }

  try {
    if (isEdit.value) {
      await axios.put(`http://localhost:8000/api/promotions/${editId.value}`, data)
    } else {
      await axios.post(`http://localhost:8000/api/promotions`, data)
    }

    alert('Thêm thành công ✅')
    await fetchPromos()
    closeModal()

  } catch (err) {
    console.log(err.response) // 👈 QUAN TRỌNG
    alert('Lỗi rồi ❌ xem console')
  }
}

async function deletePromo(id) {
  if (!confirm('Xóa?')) return

  await axios.delete(`http://localhost:8000/api/promotions/${id}`)
  await fetchPromos()
}
</script>

<style scoped>
* { box-sizing: border-box; margin: 0; padding: 0; }
.page {
  padding: 20px 24px;
  background: #f0f4ff;
  min-height: 100vh;
  font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif;
  display: flex; flex-direction: column; gap: 18px;
}

/* TOPBAR */
.topbar { display: flex; align-items: center; justify-content: space-between; }
.search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 14px; width: 260px; }
.search-box svg { width: 15px; height: 15px; stroke: #94a3b8; stroke-width: 2; fill: none; flex-shrink: 0; }
.search-box input { border: none; outline: none; font-size: 13px; color: #1e293b; background: transparent; width: 100%; }
.search-box input::placeholder { color: #94a3b8; }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.icon-btn { position: relative; width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.icon-btn svg { width: 16px; height: 16px; stroke: #64748b; stroke-width: 1.8; fill: none; }
.notif-dot { position: absolute; top: 7px; right: 7px; width: 7px; height: 7px; background: #ef4444; border-radius: 50%; border: 1.5px solid #fff; }
.admin-info { display: flex; align-items: center; gap: 9px; }
.admin-name { font-size: 13px; font-weight: 600; color: #1e293b; text-align: right; }
.admin-role { font-size: 11px; color: #94a3b8; text-align: right; }
.admin-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 50%; color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }

/* STATS */
.stats-row { display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 14px; }
.stat-card { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid #e8edf5; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-direction: column; gap: 6px; }
.stat-label { font-size: 10px; font-weight: 700; letter-spacing: 0.8px; color: #94a3b8; }
.stat-value { font-size: 30px; font-weight: 800; color: #0f172a; }
.stat-unit { font-size: 14px; font-weight: 600; color: #64748b; }
.stat-sub { font-size: 12px; color: #64748b; }
.stat-sub.green { color: #16a34a; font-weight: 600; }
.stat-bar { height: 5px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-top: 4px; }
.stat-bar-fill { height: 100%; background: linear-gradient(90deg, #4f46e5, #6366f1); border-radius: 99px; }
.stat-card-gradient { background: linear-gradient(135deg, #818cf8, #6366f1, #4f46e5); border: none; justify-content: space-between; }
.stat-card-tag { font-size: 10px; font-weight: 700; letter-spacing: 0.8px; color: rgba(255,255,255,0.7); }
.stat-card-desc { font-size: 13px; color: rgba(255,255,255,0.9); line-height: 1.6; }
.stat-card-desc strong { color: #fff; }
.stat-card-btn { align-self: flex-start; padding: 7px 16px; border-radius: 20px; border: none; background: rgba(255,255,255,0.2); color: #fff; font-size: 12px; font-weight: 600; cursor: pointer; backdrop-filter: blur(6px); transition: background 0.2s; }
.stat-card-btn:hover { background: rgba(255,255,255,0.3); }

/* LIST HEADER */
.list-header { display: flex; align-items: flex-end; justify-content: space-between; }
.list-title { font-size: 20px; font-weight: 800; color: #0f172a; }
.list-sub { font-size: 12.5px; color: #64748b; margin-top: 3px; }
.list-actions { display: flex; gap: 10px; }
.btn-filter { display: flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; font-size: 13px; font-weight: 500; color: #475569; cursor: pointer; }
.btn-filter svg { width: 14px; height: 14px; stroke: #475569; stroke-width: 2; fill: none; }
.btn-primary { display: flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 10px; border: none; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(79,70,229,0.3); transition: transform 0.15s; }
.btn-primary:hover { transform: translateY(-1px); }
.btn-primary svg { width: 14px; height: 14px; stroke: #fff; stroke-width: 2.5; fill: none; }

/* TABLE */
.table-card { background: #fff; border-radius: 16px; border: 1px solid #e8edf5; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #f8faff; border-bottom: 1px solid #e8edf5; }
th { padding: 12px 18px; font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 0.6px; text-align: left; }
tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #fafbff; }
td { padding: 14px 18px; vertical-align: middle; }
.promo-name-cell { display: flex; align-items: center; gap: 12px; }
.promo-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.promo-name { font-size: 13.5px; font-weight: 700; color: #1e293b; }
.promo-code { font-size: 11px; color: #94a3b8; font-family: monospace; margin-top: 2px; }
.discount-tag { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
.date-cell { font-size: 13px; color: #475569; }
.status-badge { font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-block; }
.status-running { color: #16a34a; background: #dcfce7; }
.status-expired { color: #dc2626; background: #fee2e2; }
.status-open    { color: #2563eb; background: #dbeafe; }
.actions { display: flex; gap: 6px; }
.action-btn { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.action-btn:hover { background: #f1f5f9; }
.action-btn svg { width: 13px; height: 13px; stroke: #64748b; stroke-width: 1.8; fill: none; }
.action-delete:hover { background: #fef2f2; border-color: #fca5a5; }
.action-delete:hover svg { stroke: #ef4444; }
.empty-row { text-align: center; color: #94a3b8; font-size: 13px; padding: 28px; }
.pagination-row { display: flex; align-items: center; justify-content: space-between; padding: 13px 18px; border-top: 1px solid #f1f5f9; }
.page-info { font-size: 12.5px; color: #64748b; }
.pagination { display: flex; gap: 4px; }
.page-btn { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; font-size: 12.5px; color: #475569; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.page-btn.active { background: #4f46e5; border-color: #4f46e5; color: #fff; font-weight: 600; }

/* BOTTOM */
.bottom-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.bottom-card { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid #e8edf5; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.bottom-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.bottom-card-header h3 { font-size: 14px; font-weight: 700; color: #1e293b; }
.icon-btn-sm { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.icon-btn-sm svg { width: 14px; height: 14px; stroke: #64748b; stroke-width: 2; fill: none; }
.rank-list { display: flex; flex-direction: column; gap: 12px; }
.rank-item { display: flex; align-items: center; gap: 10px; }
.rank-num { font-size: 12px; font-weight: 800; color: #4f46e5; width: 24px; flex-shrink: 0; }
.rank-bar-wrap { flex: 1; }
.rank-name { font-size: 12.5px; font-weight: 600; color: #1e293b; margin-bottom: 4px; }
.rank-bar { height: 4px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.rank-fill { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
.rank-roi { font-size: 12px; font-weight: 700; white-space: nowrap; }
.bottom-card-gradient { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 60%, #818cf8 100%); border: none; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; }
.dist-add-btn { position: absolute; top: 16px; right: 16px; width: 32px; height: 32px; border-radius: 50%; border: none; background: rgba(255,255,255,0.2); color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.dist-add-btn svg { width: 16px; height: 16px; stroke: #fff; stroke-width: 2.5; fill: none; }
.dist-label { font-size: 10px; font-weight: 700; letter-spacing: 1px; color: rgba(255,255,255,0.6); }
.dist-title { font-size: 22px; font-weight: 800; color: #fff; line-height: 1.3; margin: 8px 0 16px; }
.dist-stats { display: flex; align-items: center; gap: 20px; }
.dist-stat { display: flex; flex-direction: column; gap: 2px; }
.dist-num { font-size: 28px; font-weight: 800; color: #fff; }
.dist-sub { font-size: 10px; font-weight: 700; letter-spacing: 0.8px; color: rgba(255,255,255,0.65); }
.dist-divider { width: 1px; height: 40px; background: rgba(255,255,255,0.25); }

/* ═══ MODAL ═══ */
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.55); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal { background: #fff; border-radius: 20px; width: 100%; max-width: 560px; box-shadow: 0 24px 60px rgba(0,0,0,0.18); display: flex; flex-direction: column; max-height: 90vh; overflow: hidden; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 22px 18px; border-bottom: 1px solid #f1f5f9; }
.modal-header-left { display: flex; align-items: center; gap: 12px; }
.modal-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.icon-create { background: linear-gradient(135deg, #4f46e5, #6366f1); }
.icon-edit { background: linear-gradient(135deg, #f59e0b, #f97316); }
.modal-icon svg { width: 19px; height: 19px; stroke: #fff; stroke-width: 2.2; fill: none; }
.modal-title { font-size: 15px; font-weight: 700; color: #0f172a; }
.modal-subtitle { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.modal-close { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.modal-close svg { width: 14px; height: 14px; stroke: #64748b; stroke-width: 2; fill: none; }
.modal-close:hover { background: #fee2e2; }
.modal-close:hover svg { stroke: #ef4444; }
.modal-body { padding: 20px 22px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-label { font-size: 12.5px; font-weight: 600; color: #374151; }
.req { color: #ef4444; }
.form-input { padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13px; color: #1e293b; outline: none; font-family: inherit; width: 100%; background: #fff; transition: border-color 0.2s, box-shadow 0.2s; }
.form-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.form-input.err { border-color: #ef4444; }
.form-textarea { resize: vertical; min-height: 72px; }
.mono { font-family: monospace; }
select.form-input { cursor: pointer; }
.err-msg { font-size: 11.5px; color: #ef4444; }
.form-hint { font-size: 11px; color: #94a3b8; }
.input-suffix-wrap { position: relative; }
.input-suffix-wrap .form-input { padding-right: 44px; }
.input-suffix { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; font-weight: 600; color: #94a3b8; pointer-events: none; }
.toggle-group { display: flex; gap: 6px; flex-wrap: wrap; }
.toggle-btn { display: flex; align-items: center; gap: 7px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-size: 12.5px; font-weight: 500; color: #64748b; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.toggle-dot { width: 7px; height: 7px; border-radius: 50%; background: #cbd5e1; flex-shrink: 0; }
.icon-picker { display: flex; gap: 8px; flex-wrap: wrap; }
.icon-option { width: 40px; height: 40px; border-radius: 10px; border: 2px solid transparent; font-size: 18px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.icon-option:hover { transform: scale(1.1); }
.icon-option-active { border-color: #4f46e5; transform: scale(1.1); }
.modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 14px 22px; border-top: 1px solid #f1f5f9; background: #fafbff; }
.btn-cancel { padding: 9px 18px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; font-size: 13px; font-weight: 500; color: #64748b; cursor: pointer; font-family: inherit; }
.btn-save { display: flex; align-items: center; gap: 7px; padding: 9px 20px; border-radius: 10px; border: none; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(79,70,229,0.3); transition: transform 0.15s; font-family: inherit; }
.btn-save:hover { transform: translateY(-1px); }
.btn-save svg { width: 14px; height: 14px; stroke: #fff; stroke-width: 2; fill: none; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.slide-up-leave-active { transition: all 0.2s ease; }
.slide-up-enter-from { opacity: 0; transform: translateY(28px) scale(0.97); }
.slide-up-leave-to { opacity: 0; transform: translateY(8px) scale(0.98); }
a { text-decoration: none; }
</style>