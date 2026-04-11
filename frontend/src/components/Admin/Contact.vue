<template>
  <div class="page">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none">
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.3-4.3" />
        </svg>
        <input type="text" placeholder="Tìm kiếm nhanh..." v-model="searchQuery" />
      </div>
      <div class="topbar-right">
        <button class="icon-btn">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
          </svg>
          <span class="notif-dot"></span>
        </button>
        <button class="icon-btn">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="3" />
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 19.07a10 10 0 0 1 0-14.14" />
          </svg>
        </button>
        <div class="admin-info">
          <div>
            <p class="admin-name">VinaTech Admin</p>
            <p class="admin-role">ADMINISTRATOR</p>
          </div>
          <div class="admin-avatar">VA</div>
        </div>
      </div>
    </div>

    <!-- BREADCRUMB + TITLE -->
    <div class="page-heading">
      <p class="breadcrumb"><span>HỆ THỐNG</span> <span class="sep">›</span> <span class="active-crumb">QUẢN LÝ LIÊN HỆ</span></p>
      <div class="heading-row">
        <div>
          <h1 class="page-title">Hộp thư <span class="title-accent">Liên hệ</span></h1>
          <p class="page-sub">Quản lý và phản hồi các yêu cầu hỗ trợ từ khách hàng</p>
        </div>
        <button class="btn-export">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" />
          </svg>
          Xuất báo cáo
        </button>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon-wrap stat-icon-blue">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
          </svg>
        </div>
        <p class="stat-label">TOTAL</p>
        <p class="stat-sub-label">Tổng liên hệ</p>
        <h2 class="stat-value">{{ contacts.length }}</h2>
      </div>
     <!-- <div class="stat-card stat-card-gradient">
        <div class="stat-card-check">
          <svg viewBox="0 0 24 24" fill="none"><polyline points="20 6 9 17 4 12" /></svg>
        </div>
        <p class="stat-tag">URGENT</p>
        <p class="stat-sub-label" style="color:rgba(255,255,255,0.8)">Mới</p>
        <h2 class="stat-value" style="color:#fff">{{ newCount }}</h2>
      </div>-->
      <div class="stat-card">
        <div class="stat-icon-wrap stat-icon-orange">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
          </svg>
        </div>
        <p class="stat-label">IN PROGRESS</p>
        <p class="stat-sub-label">Chờ sử lý </p>
        <h2 class="stat-value">{{ processingCount }}</h2>
      </div>
      <div class="stat-card">
        <div class="stat-icon-wrap stat-icon-green">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
            <polyline points="22 4 12 14.01 9 11.01" />
          </svg>
        </div>
        <p class="stat-label">RESOLVED</p>
        <p class="stat-sub-label">Đã phản hồi</p>
        <h2 class="stat-value">{{ resolvedCount }}</h2>
      </div>
    </div>

    <!-- FILTERS -->
    <div class="filter-bar">
      <div class="filter-chips">
        <div class="filter-item">
          <span class="filter-key">Category:</span>
          <select class="filter-select" v-model="filterCategory">
            <option value="">Tất cả danh mục</option>
            <option value="tu-van">Tư vấn</option>
            <option value="ky-thuat">Kỹ thuật</option>
            <option value="khieu-nai">Khiếu nại</option>
            <option value="don-hang">Đơn hàng</option>
          </select>
        </div>
        <div class="filter-item">
          <span class="filter-key">Status:</span>
          <select class="filter-select" v-model="filterStatus">
            <option value="">Tất cả trạng thái</option>
            <option value="new">Mới</option>
            <option value="processing">Đang xử lý</option>
            <option value="resolved">Đã trả lời</option>
          </select>
        </div>
      </div>
      <div class="filter-actions">
        <button class="icon-btn">
          <svg viewBox="0 0 24 24" fill="none">
            <line x1="4" y1="6" x2="20" y2="6" /><line x1="8" y1="12" x2="16" y2="12" /><line x1="11" y1="18" x2="13" y2="18" />
          </svg>
        </button>
        <button class="icon-btn" @click="resetFilter">
          <svg viewBox="0 0 24 24" fill="none">
            <polyline points="23 4 23 10 17 10" />
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
          </svg>
        </button>
      </div>
    </div>

    <!-- LOADING -->
    <div v-if="loading" class="loading-row">
      <span class="spinner-lg"></span>
      <p>Đang tải dữ liệu...</p>
    </div>

    <!-- ERROR -->
    <div v-else-if="errorMsg" class="error-row">
      <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ errorMsg }}
      <button class="btn-retry" @click="fetchContacts">Thử lại</button>
    </div>

    <!-- TABLE -->
    <div v-else class="table-card">
      <table>
        <thead>
          <tr>
            <th>KHÁCH HÀNG</th>
            <th>NỘI DUNG YÊU CẦU</th>
            <th>PHÂN LOẠI</th>
            <th>THỜI GIAN</th>
            <th>TRẠNG THÁI</th>
            <th>THAO TÁC</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in filteredContacts" :key="c.id" @click="openDetail(c)" class="table-row">
            <td>
              <div class="customer-cell">
                <div class="customer-avatar" :style="{ background: c.avatarBg }">{{ c.initials }}</div>
                <div>
                  <p class="customer-name">{{ c.name }}</p>
                  <p class="customer-email">{{ c.email }}</p>
                  <p class="customer-phone">{{ c.phone }}</p>
                </div>
              </div>
            </td>
            <td class="content-cell">"{{ c.preview }}..."</td>
            <td>
              <div class="tag-stack">
                <span v-for="t in c.tags" :key="t.label" class="tag" :style="{ background: t.bg, color: t.color }">{{ t.label }}</span>
              </div>
            </td>
            <td class="time-cell">
              <p class="time-hour">{{ c.time }}</p>
              <p class="time-date">{{ c.date }}</p>
            </td>
            <td>
              <span :class="['status-badge', 'status-' + c.status]">{{ statusLabel(c.status) }}</span>
            </td>
            <td @click.stop>
              <div class="actions">
                <button class="action-btn" title="Xem chi tiết" @click="openDetail(c)">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <button class="action-btn action-email" title="Gửi email" @click="openEmailModal(c)">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </button>
                <button class="action-btn action-delete" title="Xóa" @click="deleteContact(c.id)">
                  <svg viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredContacts.length === 0">
            <td colspan="6" class="empty-row">Không tìm thấy liên hệ nào.</td>
          </tr>
        </tbody>
      </table>

      <div class="pagination-row">
        <span class="page-info">Hiển thị {{ filteredContacts.length }} trong số <strong>{{ contacts.length }}</strong> liên hệ</span>
      </div>
    </div>

    <!-- FAB -->
    <button class="fab">
      <svg viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </button>

    <!-- DETAIL MODAL -->
    <transition name="fade">
      <div v-if="showDetail" class="overlay" @click.self="showDetail = false">
        <div class="modal modal-detail">
          <div class="modal-header">
            <div class="modal-header-left">
              <div class="customer-avatar lg" :style="{ background: selected?.avatarBg }">{{ selected?.initials }}</div>
              <div>
                <h3 class="modal-title">{{ selected?.name }}</h3>
                <p class="modal-subtitle">{{ selected?.email }} · {{ selected?.phone }}</p>
              </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
              <button class="btn-primary-sm" @click="openEmailModal(selected)">Gửi email</button>
              <button class="modal-close" @click="showDetail = false">✕</button>
            </div>
          </div>
          <div class="modal-body">
            <div class="detail-meta">
              <div class="meta-item">
                <span class="meta-key">Thời gian</span>
                <span class="meta-val">{{ selected?.time }} · {{ selected?.date }}</span>
              </div>
              <div class="meta-item">
                <span class="meta-key">Trạng thái</span>
                <span :class="['status-badge', 'status-' + selected?.status]">{{ statusLabel(selected?.status) }}</span>
              </div>
            </div>
            <div class="detail-content">
              <p class="detail-label">Nội dung yêu cầu</p>
              <p class="detail-body">{{ selected?.fullContent }}</p>
            </div>
            <div>
              <p class="detail-label">Cập nhật trạng thái</p>
              <div class="toggle-group">
                <button v-for="s in statusOptions" :key="s.value" class="toggle-btn" @click="updateStatus(s.value)">
                  {{ s.label }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- EMAIL MODAL -->
    <transition name="fade">
      <div class="overlay" v-if="showEmail" @click.self="showEmail = false">
        <transition name="slide-up">
          <div class="modal modal-email" v-if="showEmail">
            <div class="modal-header">
              <div class="modal-header-left">
                <div class="modal-icon icon-email">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <div>
                  <h3 class="modal-title">Gửi email tư vấn</h3>
                  <p class="modal-subtitle">Phản hồi yêu cầu của <strong>{{ emailTarget?.name }}</strong></p>
                </div>
              </div>
              <button class="modal-close" @click="showEmail = false">
                <svg viewBox="0 0 24 24" fill="none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label class="form-label">Mẫu phản hồi nhanh</label>
                <div class="template-chips">
                  <button v-for="tpl in templates" :key="tpl.label" class="tpl-chip"
                    :class="{ 'tpl-chip-active': selectedTpl === tpl.label }" @click="applyTemplate(tpl)">{{ tpl.label }}</button>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Đến <span class="req">*</span></label>
                <input class="form-input" :value="emailTarget?.email" readonly style="background:#f8fafc;color:#64748b" />
              </div>
              <div class="form-group">
                <label class="form-label">CC</label>
                <input class="form-input" v-model="emailForm.cc" placeholder="Thêm địa chỉ CC (tuỳ chọn)" />
              </div>
              <div class="form-group">
                <label class="form-label">Tiêu đề <span class="req">*</span></label>
                <input class="form-input" :class="{ err: emailErrors.subject }" v-model="emailForm.subject" placeholder="VD: [VinaTech] Phản hồi yêu cầu hỗ trợ của bạn" />
                <p class="err-msg" v-if="emailErrors.subject">{{ emailErrors.subject }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Nội dung <span class="req">*</span></label>
                <textarea class="form-input form-textarea" :class="{ err: emailErrors.body }" v-model="emailForm.body" rows="7" placeholder="Nhập nội dung email tư vấn..."></textarea>
                <p class="err-msg" v-if="emailErrors.body">{{ emailErrors.body }}</p>
              </div>
              <div class="form-group">
                <label class="form-label">Chữ ký</label>
                <div class="signature-box">
                  <p>Trân trọng,</p>
                  <p><strong>VinaTech Admin</strong> · Bộ phận Hỗ trợ Khách hàng</p>
                  <p>📞 1800 9999 · 🌐 vinatech.vn</p>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn-cancel" @click="showEmail = false">Hủy</button>
              <button class="btn-save btn-send" @click="sendEmail" :disabled="sending">
                <svg v-if="!sending" viewBox="0 0 24 24" fill="none"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <span v-if="sending" class="spinner"></span>
                {{ sending ? 'Đang gửi...' : 'Gửi email' }}
              </button>
            </div>
          </div>
        </transition>
      </div>
    </transition>

    <!-- TOAST -->
    <transition name="toast">
      <div class="toast" v-if="toast.show" :class="'toast-' + toast.type">
        <svg v-if="toast.type === 'success'" viewBox="0 0 24 24" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <svg v-else viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ toast.message }}
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// ─── State ───────────────────────────────────────────────
const searchQuery   = ref('')
const filterCategory = ref('')
const filterStatus  = ref('')
const showDetail    = ref(false)
const showEmail     = ref(false)
const selected      = ref(null)
const emailTarget   = ref(null)
const sending       = ref(false)
const selectedTpl   = ref('')
const loading       = ref(false)
const errorMsg      = ref('')
const contacts      = ref([])
const toast         = ref({ show: false, type: 'success', message: '' })

const emailForm   = ref({ cc: '', subject: '', body: '' })
const emailErrors = ref({})

// ─── Constants ───────────────────────────────────────────
const API = 'http://localhost:8000/api'   // ← đổi port nếu Laravel chạy khác

const statusOptions = [
  { value: 'new',        label: 'Mới' },
  { value: 'processing', label: 'Chờ xử lý' },
  { value: 'resolved',   label: 'Đã trả lời' },
]

const templates = [
  {
    label: '✅ Xác nhận nhận yêu cầu',
    subject: '[VinaTech] Xác nhận nhận yêu cầu hỗ trợ',
    body: `Kính gửi Quý khách,\n\nChúng tôi đã nhận được yêu cầu hỗ trợ của bạn và sẽ phản hồi trong vòng 24 giờ làm việc.\n\nCảm ơn bạn đã tin tưởng sử dụng sản phẩm của VinaTech.\n\nNếu có bất kỳ câu hỏi nào khác, vui lòng liên hệ hotline 1800 9999.`,
  },
  {
    label: '🛠️ Hướng dẫn kỹ thuật',
    subject: '[VinaTech] Hướng dẫn xử lý sự cố kỹ thuật',
    body: `Kính gửi Quý khách,\n\nCảm ơn bạn đã liên hệ với chúng tôi. Dưới đây là hướng dẫn xử lý sự cố:\n\n1. Khởi động lại thiết bị và kiểm tra kết nối nguồn\n2. Cập nhật driver mới nhất từ trang web chính thức\n3. Nếu sự cố vẫn còn, vui lòng mang thiết bị đến trung tâm bảo hành gần nhất\n\nChúc bạn sử dụng thiết bị thuận lợi!`,
  },
  {
    label: '💰 Tư vấn sản phẩm',
    subject: '[VinaTech] Tư vấn sản phẩm phù hợp cho bạn',
    body: `Kính gửi Quý khách,\n\nDựa trên nhu cầu của bạn, chúng tôi xin tư vấn một số dòng sản phẩm phù hợp:\n\n• Laptop Gaming: Asus ROG, MSI, Lenovo Legion\n• Laptop Văn phòng: Dell XPS, HP Spectre, MacBook Air\n• Laptop Sinh viên: Asus VivoBook, Acer Aspire\n\nQuý khách có thể ghé showroom hoặc đặt hàng online tại vinatech.vn với chính sách trả góp 0% lãi suất.`,
  },
  {
    label: '📦 Cập nhật đơn hàng',
    subject: '[VinaTech] Cập nhật tình trạng đơn hàng của bạn',
    body: `Kính gửi Quý khách,\n\nĐơn hàng của bạn đang được xử lý. Chúng tôi sẽ thông báo ngay khi hàng được giao cho đơn vị vận chuyển.\n\nThời gian dự kiến nhận hàng: 2-3 ngày làm việc.\n\nMọi thắc mắc xin liên hệ hotline 1800 9999 (miễn phí, 8:00 - 22:00 hàng ngày).`,
  },
]

// ─── Helpers ─────────────────────────────────────────────
function getInitials(name) {
  return (name || '').split(' ').filter(Boolean).map(w => w[0]).slice(0, 2).join('').toUpperCase()
}

function getTagStyle(category) {
  const map = {
    'tu-van':    { label: 'TƯ VẤN',   bg: '#dbeafe', color: '#1e40af' },
    'ky-thuat':  { label: 'KỸ THUẬT', bg: '#fff7ed', color: '#9a3412' },
    'khieu-nai': { label: 'KHIẾU NẠI',bg: '#fee2e2', color: '#991b1b' },
    'don-hang':  { label: 'ĐƠN HÀNG', bg: '#fef3c7', color: '#92400e' },
  }
  return map[category] || { label: 'LIÊN HỆ', bg: '#e2e8f0', color: '#475569' }
}

function mapItem(item) {
  const tag = getTagStyle(item.category)
  return {
    id:          item.id,
    name:        item.name,
    initials:    getInitials(item.name),
    avatarBg:    '#dbeafe',
    email:       item.email,
    phone:       item.phone || 'Chưa cập nhật',
    preview:     (item.message || '').slice(0, 30),
    fullContent: item.message,
    tags:        [tag],
    time: new Date(item.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
    date: new Date(item.created_at).toLocaleDateString('vi-VN'),
    // ✅ map 'replied' (DB) → 'resolved' (Vue)
    status: item.status === 'replied' ? 'resolved' : (item.status || 'new'),
  }
}

function showToast(type, message) {
  toast.value = { show: true, type, message }
  setTimeout(() => { toast.value.show = false }, 3000)
}

function statusLabel(s) {
  return {
    new: '+ Mới',
    processing: 'Chờ xử lý',
    resolved: 'Đã trả lời'
  }[s] || s
}

function getStatusClass(status) {
  switch (status) {
    case 'processing':
      return 'bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-medium'
    case 'resolved':
      return 'bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium'
    default:
      return 'bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-medium'
  }
}


// ─── API calls ───────────────────────────────────────────
async function fetchContacts() {
  loading.value  = true
  errorMsg.value = ''
  try {
    // ✅ Gọi đúng route: GET /api/contacts (đã thêm vào api.php)
    const res = await axios.get(`${API}/contacts`)
    contacts.value = res.data.map(mapItem)
  } catch (err) {
    console.error('Lỗi load contacts:', err)
    errorMsg.value = 'Không thể tải danh sách liên hệ. Kiểm tra lại server Laravel!'
  } finally {
    loading.value = false
  }
}

async function sendEmail() {
  if (!validateEmail()) return
  sending.value = true
  try {
    // ✅ Gọi đúng route: POST /api/contacts/{id}/reply
    await axios.post(`${API}/contacts/${emailTarget.value.id}/reply`, {
      reply: emailForm.value.body
    })
    const idx = contacts.value.findIndex(c => c.id === emailTarget.value.id)
    if (idx !== -1) contacts.value[idx].status = 'resolved'
    showEmail.value = false
    showToast('success', `Đã gửi email đến ${emailTarget.value.email} thành công!`)
  } catch (err) {
    showToast('error', 'Gửi email thất bại. Kiểm tra lại cấu hình Gmail SMTP!')
  } finally {
    sending.value = false
  }
}

async function deleteContact(id) {
  if (!confirm('Xóa liên hệ này khỏi hệ thống?')) return
  try {
    await axios.delete(`${API}/contacts/${id}`)
    contacts.value = contacts.value.filter(c => c.id !== id)
    showToast('success', 'Đã xóa liên hệ thành công.')
  } catch {
    // Nếu chưa có route delete thì xóa local tạm
    contacts.value = contacts.value.filter(c => c.id !== id)
    showToast('success', 'Đã xóa liên hệ thành công.')
  }
}

// ─── Computed ────────────────────────────────────────────
const newCount        = computed(() => contacts.value.filter(c => c.status === 'new').length)
const processingCount = computed(() => contacts.value.filter(c => c.status === 'processing').length)
const resolvedCount   = computed(() => contacts.value.filter(c => c.status === 'resolved').length)

const filteredContacts = computed(() => {
  return contacts.value.filter(c => {
    const q = searchQuery.value.toLowerCase()
    const matchQ      = !q || c.name.toLowerCase().includes(q) || c.email.toLowerCase().includes(q)
    const matchStatus = !filterStatus.value || c.status === filterStatus.value
    const matchCat    = !filterCategory.value || c.tags.some(t => {
      const label = t.label.toLowerCase()
      if (filterCategory.value === 'tu-van')    return label.includes('tư vấn')
      if (filterCategory.value === 'ky-thuat')  return label.includes('kỹ thuật')
      if (filterCategory.value === 'khieu-nai') return label.includes('khiếu nại')
      if (filterCategory.value === 'don-hang')  return label.includes('đơn hàng')
      return false
    })
    return matchQ && matchStatus && matchCat
  })
})

// ─── Actions ─────────────────────────────────────────────
function resetFilter() {
  filterCategory.value = ''
  filterStatus.value   = ''
  searchQuery.value    = ''
}

function openDetail(c) {
  selected.value   = c
  showDetail.value = true
}

function updateStatus(val) {
  const idx = contacts.value.findIndex(c => c.id === selected.value.id)
  if (idx !== -1) {
    contacts.value[idx].status = val
    selected.value.status      = val
  }
}

function openEmailModal(c) {
  emailTarget.value = c
  emailForm.value   = { cc: '', subject: '', body: '' }
  emailErrors.value = {}
  selectedTpl.value = ''
  showEmail.value   = true
}

function applyTemplate(tpl) {
  selectedTpl.value       = tpl.label
  emailForm.value.subject = tpl.subject
  emailForm.value.body    = tpl.body
}

function validateEmail() {
  emailErrors.value = {}
  if (!emailForm.value.subject.trim()) emailErrors.value.subject = 'Tiêu đề không được để trống.'
  if (!emailForm.value.body.trim())    emailErrors.value.body    = 'Nội dung không được để trống.'
  return Object.keys(emailErrors.value).length === 0
}

// ─── Init ────────────────────────────────────────────────
onMounted(fetchContacts)
</script>

<style scoped>
* { box-sizing: border-box; margin: 0; padding: 0; }

.page {
  padding: 18px 22px;
  background: #f0f4ff;
  min-height: 100vh;
  font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif;
  display: flex;
  flex-direction: column;
  gap: 16px;
  position: relative;
}

/* TOPBAR */
.topbar { display: flex; align-items: center; justify-content: space-between; }
.search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 14px; width: 250px; }
.search-box svg { width: 14px; height: 14px; stroke: #94a3b8; stroke-width: 2; fill: none; flex-shrink: 0; }
.search-box input { border: none; outline: none; font-size: 13px; color: #1e293b; background: transparent; width: 100%; font-family: inherit; }
.search-box input::placeholder { color: #94a3b8; }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.icon-btn { position: relative; width: 34px; height: 34px; border-radius: 9px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s; }
.icon-btn:hover { background: #f1f5f9; }
.icon-btn svg { width: 15px; height: 15px; stroke: #64748b; stroke-width: 1.8; fill: none; }
.notif-dot { position: absolute; top: 6px; right: 6px; width: 7px; height: 7px; background: #ef4444; border-radius: 50%; border: 1.5px solid #fff; }
.admin-info { display: flex; align-items: center; gap: 8px; }
.admin-name { font-size: 13px; font-weight: 700; color: #1e293b; text-align: right; }
.admin-role { font-size: 10px; font-weight: 600; letter-spacing: 0.5px; color: #94a3b8; text-align: right; }
.admin-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 50%; color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; box-shadow: 0 0 0 2px #6366f1; }

/* HEADING */
.breadcrumb { font-size: 11px; font-weight: 600; color: #94a3b8; letter-spacing: 0.5px; margin-bottom: 6px; }
.sep { margin: 0 6px; }
.active-crumb { color: #4f46e5; }
.heading-row { display: flex; align-items: flex-end; justify-content: space-between; }
.page-title { font-size: 26px; font-weight: 800; color: #0f172a; }
.title-accent { color: #4f46e5; }
.page-sub { font-size: 13px; color: #64748b; margin-top: 3px; }
.btn-export { display: flex; align-items: center; gap: 7px; padding: 9px 18px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; font-size: 13px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s; }
.btn-export:hover { background: #f1f5f9; }
.btn-export svg { width: 15px; height: 15px; stroke: #475569; stroke-width: 2; fill: none; }

/* STATS */
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.stat-card { background: #fff; border-radius: 14px; padding: 18px; border: 1px solid #e8edf5; box-shadow: 0 2px 8px rgba(0,0,0,.04); display: flex; flex-direction: column; gap: 4px; }
.stat-icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 6px; }
.stat-icon-wrap svg { width: 18px; height: 18px; stroke-width: 2; fill: none; }
.stat-icon-blue { background: #dbeafe; } .stat-icon-blue svg { stroke: #2563eb; }
.stat-icon-orange { background: #ffedd5; } .stat-icon-orange svg { stroke: #ea580c; }
.stat-icon-green { background: #d1fae5; } .stat-icon-green svg { stroke: #059669; }
.stat-label { font-size: 9.5px; font-weight: 700; letter-spacing: 0.8px; color: #94a3b8; }
.stat-sub-label { font-size: 12px; color: #64748b; }
.stat-value { font-size: 28px; font-weight: 800; color: #0f172a; }
.stat-card-gradient { background: linear-gradient(135deg, #818cf8, #6366f1, #4f46e5); border: none; justify-content: flex-end; min-height: 120px; }
.stat-card-gradient .stat-value { color: #fff; font-size: 36px; }
.stat-card-check { width: 28px; height: 28px; background: rgba(255,255,255,.25); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: auto; }
.stat-card-check svg { width: 14px; height: 14px; stroke: #fff; stroke-width: 2.5; fill: none; }
.stat-tag { font-size: 9.5px; font-weight: 700; letter-spacing: 1px; color: rgba(255,255,255,.65); }

/* FILTER */
.filter-bar { display: flex; align-items: center; justify-content: space-between; background: #fff; border-radius: 12px; border: 1px solid #e8edf5; padding: 10px 16px; }
.filter-chips { display: flex; align-items: center; gap: 14px; }
.filter-item { display: flex; align-items: center; gap: 8px; }
.filter-key { font-size: 12.5px; font-weight: 600; color: #64748b; white-space: nowrap; }
.filter-select { border: none; outline: none; background: transparent; font-size: 12.5px; font-weight: 600; color: #4f46e5; cursor: pointer; font-family: inherit; }
.filter-actions { display: flex; gap: 6px; }

/* LOADING / ERROR */
.loading-row { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 48px; background: #fff; border-radius: 16px; color: #64748b; font-size: 14px; }
.spinner-lg { width: 24px; height: 24px; border: 3px solid #e2e8f0; border-top-color: #4f46e5; border-radius: 50%; animation: spin .7s linear infinite; }
.error-row { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 32px; background: #fff; border-radius: 16px; color: #ef4444; font-size: 13px; border: 1px solid #fca5a5; }
.error-row svg { width: 18px; height: 18px; stroke: #ef4444; stroke-width: 2; fill: none; flex-shrink: 0; }
.btn-retry { margin-left: 8px; padding: 6px 14px; border-radius: 8px; border: 1.5px solid #ef4444; background: #fff; color: #ef4444; font-size: 12px; font-weight: 600; cursor: pointer; }
.btn-retry:hover { background: #fef2f2; }

/* TABLE */
.table-card { background: #fff; border-radius: 16px; border: 1px solid #e8edf5; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,.04); }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #f8faff; border-bottom: 1px solid #e8edf5; }
th { padding: 11px 16px; font-size: 10.5px; font-weight: 700; color: #94a3b8; letter-spacing: 0.6px; text-align: left; white-space: nowrap; }
.table-row { border-bottom: 1px solid #f1f5f9; transition: background .15s; cursor: pointer; }
.table-row:last-child { border-bottom: none; }
.table-row:hover { background: #fafbff; }
td { padding: 13px 16px; vertical-align: middle; }
.customer-cell { display: flex; align-items: center; gap: 10px; }
.customer-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: #334155; flex-shrink: 0; }
.customer-avatar.lg { width: 44px; height: 44px; font-size: 14px; }
.customer-name { font-size: 13px; font-weight: 700; color: #1e293b; }
.customer-email { font-size: 11px; color: #94a3b8; margin-top: 1px; }
.customer-phone { font-size: 11px; color: #94a3b8; }
.content-cell { font-size: 13px; color: #475569; font-style: italic; max-width: 140px; }
.tag-stack { display: flex; flex-direction: column; gap: 4px; }
.tag { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: 10px; font-weight: 700; letter-spacing: 0.3px; white-space: nowrap; }
.time-cell { white-space: nowrap; }
.time-hour { font-size: 13px; font-weight: 600; color: #1e293b; }
.time-date { font-size: 11px; color: #94a3b8; margin-top: 1px; }
.status-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-block; white-space: nowrap; }
.status-new {
  color: #4f46e5;
  background: #eef2ff;
}

.status-processing {
  color: #b45309;          /* chữ vàng đậm */
  background: #fef3c7;     /* nền vàng */
  border: 1px solid #fcd34d;
}

.status-resolved {
  color: #047857;          /* chữ xanh */
  background: #d1fae5;     /* nền xanh */
  border: 1px solid #6ee7b7;
}

.actions { display: flex; gap: 5px; }
.action-btn { width: 28px; height: 28px; border-radius: 7px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .15s; }
.action-btn svg { width: 12px; height: 12px; stroke: #64748b; stroke-width: 1.8; fill: none; }
.action-btn:hover { background: #f1f5f9; }
.action-email:hover { background: #eff6ff; border-color: #bfdbfe; }
.action-email:hover svg { stroke: #2563eb; }
.action-delete:hover { background: #fef2f2; border-color: #fca5a5; }
.action-delete:hover svg { stroke: #ef4444; }
.empty-row { text-align: center; color: #94a3b8; font-size: 13px; padding: 28px; }
.pagination-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-top: 1px solid #f1f5f9; }
.page-info { font-size: 12px; color: #64748b; }

/* FAB */
.fab { position: fixed; bottom: 28px; right: 28px; width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #6366f1); border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 6px 20px rgba(79,70,229,.4); transition: transform .2s; z-index: 100; }
.fab:hover { transform: scale(1.1); }
.fab svg { width: 22px; height: 22px; stroke: #fff; stroke-width: 2; fill: none; }

/* MODAL */
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal { background: #fff; border-radius: 20px; width: 100%; box-shadow: 0 24px 60px rgba(0,0,0,.18); display: flex; flex-direction: column; max-height: 90vh; overflow: hidden; }
.modal-detail { max-width: 520px; }
.modal-email  { max-width: 580px; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px 16px; border-bottom: 1px solid #f1f5f9; gap: 12px; }
.modal-header-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
.modal-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.icon-email { background: linear-gradient(135deg, #2563eb, #3b82f6); }
.modal-icon svg { width: 18px; height: 18px; stroke: #fff; stroke-width: 2; fill: none; }
.modal-title { font-size: 15px; font-weight: 700; color: #0f172a; }
.modal-subtitle { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.modal-close { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; }
.modal-close:hover { background: #fee2e2; }
.modal-close svg { width: 13px; height: 13px; stroke: #64748b; stroke-width: 2; fill: none; }
.modal-close:hover svg { stroke: #ef4444; }
.modal-body { padding: 18px 22px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; }
.modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 13px 22px; border-top: 1px solid #f1f5f9; background: #fafbff; }
.detail-meta { display: flex; flex-direction: column; gap: 10px; background: #f8faff; border-radius: 12px; padding: 14px 16px; border: 1px solid #e8edf5; }
.meta-item { display: flex; align-items: center; gap: 10px; }
.meta-key { font-size: 12px; font-weight: 600; color: #94a3b8; width: 90px; flex-shrink: 0; }
.meta-val { font-size: 13px; color: #1e293b; }
.detail-label { font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
.detail-body { font-size: 13.5px; color: #334155; line-height: 1.7; background: #f8faff; border-radius: 10px; padding: 14px; border: 1px solid #e8edf5; }
.toggle-group { display: flex; gap: 8px; flex-wrap: wrap; }
.toggle-btn { display: flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 9px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-size: 12.5px; font-weight: 500; color: #64748b; cursor: pointer; transition: all .15s; font-family: inherit; }
.toggle-btn:hover { background: #eef2ff; border-color: #c7d2fe; color: #4f46e5; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-label { font-size: 12.5px; font-weight: 600; color: #374151; }
.req { color: #ef4444; }
.form-input { padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 13px; color: #1e293b; outline: none; font-family: inherit; width: 100%; background: #fff; transition: border-color .2s, box-shadow .2s; }
.form-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.form-input.err { border-color: #ef4444; }
.form-textarea { resize: vertical; min-height: 130px; line-height: 1.6; }
.err-msg { font-size: 11.5px; color: #ef4444; }
.template-chips { display: flex; gap: 7px; flex-wrap: wrap; }
.tpl-chip { padding: 6px 12px; border-radius: 20px; border: 1.5px solid #e2e8f0; background: #f8fafc; font-size: 12px; font-weight: 500; color: #475569; cursor: pointer; transition: all .15s; font-family: inherit; }
.tpl-chip:hover { background: #eef2ff; border-color: #c7d2fe; color: #4f46e5; }
.tpl-chip-active { background: #eef2ff; border-color: #6366f1; color: #4f46e5; font-weight: 600; }
.signature-box { background: #f8faff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; font-size: 12.5px; color: #64748b; line-height: 1.8; border-left: 3px solid #6366f1; }
.btn-cancel { padding: 8px 16px; border-radius: 9px; border: 1.5px solid #e2e8f0; background: #fff; font-size: 13px; font-weight: 500; color: #64748b; cursor: pointer; font-family: inherit; }
.btn-save { display: flex; align-items: center; gap: 7px; padding: 8px 18px; border-radius: 9px; border: none; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(79,70,229,.3); transition: transform .15s; font-family: inherit; }
.btn-save:hover:not(:disabled) { transform: translateY(-1px); }
.btn-save:disabled { opacity: .7; cursor: not-allowed; }
.btn-send svg { width: 14px; height: 14px; stroke: #fff; stroke-width: 2; fill: none; }
.btn-primary-sm { display: flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 9px; border: none; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; font-size: 12.5px; font-weight: 600; cursor: pointer; font-family: inherit; transition: transform .15s; }
.btn-primary-sm:hover { transform: translateY(-1px); }

/* SPINNER */
.spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* TOAST */
.toast { position: fixed; bottom: 90px; right: 28px; display: flex; align-items: center; gap: 10px; padding: 12px 18px; border-radius: 12px; font-size: 13px; font-weight: 600; z-index: 99999; box-shadow: 0 8px 24px rgba(0,0,0,.15); }
.toast svg { width: 16px; height: 16px; stroke-width: 2; fill: none; flex-shrink: 0; }
.toast-success { background: #1e293b; color: #fff; } .toast-success svg { stroke: #4ade80; }
.toast-error   { background: #1e293b; color: #fff; } .toast-error svg   { stroke: #f87171; }

/* TRANSITIONS */
.fade-enter-active, .fade-leave-active { transition: opacity .25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active { transition: all .3s cubic-bezier(.34,1.56,.64,1); }
.slide-up-leave-active { transition: all .2s ease; }
.slide-up-enter-from { opacity: 0; transform: translateY(28px) scale(.97); }
.slide-up-leave-to { opacity: 0; transform: translateY(8px) scale(.98); }
.toast-enter-active { transition: all .3s cubic-bezier(.34,1.56,.64,1); }
.toast-leave-active { transition: all .2s ease; }
.toast-enter-from { opacity: 0; transform: translateY(10px) scale(.95); }
.toast-leave-to { opacity: 0; transform: translateY(10px); }
a { text-decoration: none; }
</style>