<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import Header from '../Layout/Header.vue'
import Footer from '../Layout/Footer.vue'
import axios from 'axios'
import api from '@/services/api'

// ── Active tab ────────────────────────────────────────────
const activeTab = ref('profile')

const tabs = [
  { key: 'profile', label: 'Thông tin cá nhân', icon: 'person' },
  { key: 'orders', label: 'Đơn hàng', icon: 'orders' },
  { key: 'address', label: 'Địa chỉ', icon: 'map' },
  { key: 'promotions', label: 'Khuyến mãi', icon: 'tag' },
  { key: 'password', label: 'Đổi mật khẩu', icon: 'lock' },
]

// ── Toast ─────────────────────────────────────────────────
const toast = ref({ show: false, msg: '' })
const showToast = (msg) => {
  toast.value = { show: true, msg }
  setTimeout(() => {
    toast.value.show = false
  }, 2500)
}

// ── Cancellation state ────────────────────────────────────
const showCancelModal = ref(false)
const orderToCancel = ref(null)
const cancelReason = ref('')
const isSubmitting = ref(false)

// ════════════════════════════════════════════════
//  TAB 1 — PROFILE
// ════════════════════════════════════════════════
const user = ref({
  name: '',
  email: '',
  phone: '',
  birthday: '',
  gender: '',
  avatar: 'https://randomuser.me/api/portraits/men/32.jpg',
  memberSince: 'Thành viên',
  joinDate: '',
})

const sidebarAvatarUrl = computed(() => {
  if (!user.value.avatar) return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.value.name || 'User')
  if (user.value.avatar.startsWith('http')) return user.value.avatar
  return `http://127.0.0.1:8000/storage/${user.value.avatar}`
})

const formAvatarUrl = computed(() => {
  if (tempAvatarUrl.value) return tempAvatarUrl.value
  return sidebarAvatarUrl.value
})

const updateUserData = (apiUser) => {
  user.value = {
    ...user.value,
    ...apiUser,
    phone: apiUser.phone || '',
    birthday: apiUser.date_of_birth || '',
    gender: apiUser.gender || '',
    avatar: apiUser.avatar || user.value.avatar,
    memberSince: apiUser.memberSince || 'Thành viên',
    joinDate: apiUser.joinDate || user.value.joinDate,
  }
}

const fileInput = ref(null)
const selectedAvatarFile = ref(null)
const tempAvatarUrl = ref(null)

const triggerAvatarUpload = () => {
  fileInput.value.click()
}

const handleAvatarUpload = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']
  if (!allowedTypes.includes(file.type)) {
    showToast('Chỉ chấp nhận ảnh định dạng JPG hoặc PNG!')
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    showToast('Kích thước ảnh phải nhỏ hơn 2MB!')
    return
  }

  if (file) {
    if (tempAvatarUrl.value) {
      URL.revokeObjectURL(tempAvatarUrl.value);
    }
    selectedAvatarFile.value = file;
    tempAvatarUrl.value = URL.createObjectURL(file);
  }
}

const profileForm = ref({})
const editing = ref(false)
const savingProfile = ref(false)

const loadUser = async () => {
  try {
    const token = localStorage.getItem('token')

    if (!token) {
      const storedUser = localStorage.getItem('user')
      if (storedUser) {
        const parsed = JSON.parse(storedUser)
        user.value = {
          ...user.value,
          ...parsed,
          phone: parsed.phone || '',
          birthday: parsed.birthday || '',
          gender: parsed.gender || '',
          memberSince: parsed.memberSince || 'Thành viên',
          joinDate: parsed.joinDate || '',
          avatar: parsed.avatar || user.value.avatar,
        }
      }
      return
    }
    const res = await api.get('/user/profile')

    updateUserData(res.data)

    localStorage.setItem('user', JSON.stringify(user.value))
  } catch (error) {
    console.error('Load user lỗi:', error)

    const storedUser = localStorage.getItem('user')
    if (storedUser) {
      const parsed = JSON.parse(storedUser)
      user.value = {
        ...user.value,
        ...parsed,
        phone: parsed.phone || '',
        birthday: parsed.birthday || '',
        gender: parsed.gender || '',
        memberSince: parsed.memberSince || 'Thành viên',
        joinDate: parsed.joinDate || '',
        avatar: parsed.avatar || user.value.avatar,
      }
    }
  }
}

const fetchOrders = async () => {
  try {
    const res = await api.get('/orders')

    if (res.data.success) {
      orders.value = res.data.orders.map(order => {
        let statusKey = 'pending'
        if (order.trangthai === 'confirmed') statusKey = 'confirmed'
        if (order.trangthai === 'shipping') statusKey = 'shipping'
        if (order.trangthai === 'done' || order.trangthai === 'completed') statusKey = 'done'
        if (order.trangthai === 'cancelled') statusKey = 'cancelled'

        return {
          id_dathang: order.id_dathang,
          id: `VT-2026-${String(order.id_dathang).padStart(3, '0')}`,
          date: new Date(order.created_at).toLocaleDateString('vi-VN'),
          status: statusKey,
          trangthai: order.trangthai,
          total: new Intl.NumberFormat('vi-VN').format(order.tongtien) + 'đ',
          tongtien: order.tongtien,
          lydo: order.lydo,
          items: order.chi_tiets.map(item => ({
            name: item.bien_the?.ten_bienthe || 'Sản phẩm',
            qty: item.soluong,
            price: new Intl.NumberFormat('vi-VN').format(item.gia) + 'đ',
            img: item.bien_the?.san_pham?.hinhanh ? `http://127.0.0.1:8000/storage/${item.bien_the.san_pham.hinhanh}` : 'https://via.placeholder.com/200'
          })),
          steps: [
            { label: 'Đặt hàng', date: new Date(order.created_at).toLocaleString('vi-VN'), done: true },
            { label: 'Xác nhận', date: null, done: statusKey !== 'pending' },
            { label: 'Đang giao', date: null, done: statusKey === 'shipping' || statusKey === 'done' },
            { label: 'Hoàn thành', date: null, done: statusKey === 'done' },
          ]
        }
      })
    }
  } catch (error) {
    console.error('Lỗi tải đơn hàng:', error)
  }
}

const openCancelModal = (order) => {
  orderToCancel.value = order
  cancelReason.value = ''
  showCancelModal.value = true
}

const confirmCancel = async () => {
  if (!cancelReason.value.trim()) {
    showToast('Vui lòng nhập lý do hủy.')
    return
  }

  if (!confirm('Chắc chắn hủy đơn?')) return

  isSubmitting.value = true
  try {
    const res = await api.post(`/orders/${orderToCancel.value.id_dathang}/cancel`, {
      lydo: cancelReason.value.trim()
    })

    if (res.data.success) {
      showToast('Hủy đơn hàng thành công!')
      showCancelModal.value = false
      await fetchOrders()
    }
  } catch (err) {
    showToast(err.response?.data?.message || 'Lỗi khi hủy đơn.')
  } finally {
    isSubmitting.value = false
  }
}

const handleReorder = async (order) => {
  if (!confirm('Chắc chắn mua lại?')) return

  try {
    const res = await api.post(`/orders/${order.id_dathang}/reorder`)

    if (res.data.success) {
      showToast(res.data.message)
      window.location.href = '/cart'
    }
  } catch (err) {
    showToast('Lỗi khi mua lại sản phẩm.')
  }
}

onMounted(() => {
  loadUser()
  fetchOrders()
  fetchPromotions()
})

const startEdit = () => {
  profileForm.value = { ...user.value }
  if (profileForm.value.gender === 'Nam') profileForm.value.gender = 'male'
  if (profileForm.value.gender === 'Nữ') profileForm.value.gender = 'female'

  editing.value = true
}

const cancelEdit = () => {
  editing.value = false
  profileForm.value = {}
}

const saveProfile = async () => {
  try {
    savingProfile.value = true

    if (selectedAvatarFile.value) {
      const formData = new FormData()
      formData.append('avatar', selectedAvatarFile.value)

      const avatarRes = await api.post('/user/avatar', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      if (avatarRes.data.user) {
        updateUserData(avatarRes.data.user)
      }
    }

    const res = await api.put(
      '/user/profile',
      {
        name: profileForm.value.name,
        email: profileForm.value.email,
        phone: profileForm.value.phone,
        date_of_birth: profileForm.value.birthday,
        gender: profileForm.value.gender,
      }
    )

    updateUserData(res.data.user)
    localStorage.setItem('user', JSON.stringify(user.value))
    window.dispatchEvent(new Event('user-updated'))

    editing.value = false
    showToast('Cập nhật thành công!')

  } catch (error) {
    console.error('LỖI API:', error.response?.data)
    showToast(error.response?.data?.message || 'Lỗi cập nhật!')
  } finally {
    savingProfile.value = false
  }
}

const stats = computed(() => [
  { label: 'Đơn hàng', value: orders.value.length.toString(), icon: 'orders' },
  { label: 'Yêu thích', value: '8', icon: 'heart' },
  { label: 'Điểm thưởng', value: '1.250', icon: 'star' },
])

// ════════════════════════════════════════════════
//  TAB 2 — ORDERS
// ════════════════════════════════════════════════
const orderTab = ref('all')
const selectedOrder = ref(null)

const orderTabs = [
  { key: 'all', label: 'Tất cả' },
  { key: 'pending', label: 'Chờ xác nhận' },
  { key: 'confirmed', label: 'Đã xác nhận' },
  { key: 'shipping', label: 'Đang giao' },
  { key: 'done', label: 'Hoàn thành' },
  { key: 'cancelled', label: 'Đã hủy' },
]

const statusMap = {
  pending: { label: 'Chờ xác nhận', color: '#f59e0b', bg: '#fef3c7' },
  confirmed: { label: 'Đã xác nhận', color: '#0369a1', bg: '#e0f2fe' },
  shipping: { label: 'Đang giao', color: '#2563eb', bg: '#dbeafe' },
  done: { label: 'Hoàn thành', color: '#16a34a', bg: '#dcfce7' },
  cancelled: { label: 'Đã hủy', color: '#dc2626', bg: '#fee2e2' },
}

const orders = ref([])

const filteredOrders = computed(() =>
  orderTab.value === 'all'
    ? orders.value
    : orders.value.filter((o) => o.status === orderTab.value)
)

const currentPage = ref(1)
const itemsPerPage = 8
const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredOrders.value.slice(start, start + itemsPerPage)
})

const totalPages = computed(() => Math.ceil(filteredOrders.value.length / itemsPerPage))

watch(orderTab, () => {
  currentPage.value = 1
})

// ════════════════════════════════════════════════
//  TAB 3 — ADDRESS
// ════════════════════════════════════════════════
const showAddrForm = ref(false)
const editingAddrIdx = ref(null)
const savingAddr = ref(false)

const defaultAddrForm = () => ({
  name: '',
  phone: '',
  province: '',
  district: '',
  ward: '',
  detail: '',
  isDefault: false,
})

const addrForm = ref(defaultAddrForm())

const provinces = [
  'TP. Hồ Chí Minh',
  'Hà Nội',
  'Đà Nẵng',
  'Cần Thơ',
  'Hải Phòng',
  'Biên Hòa',
  'Buôn Ma Thuột',
]

const openAddAddr = () => {
  addrForm.value = defaultAddrForm()
  editingAddrIdx.value = null
  showAddrForm.value = true
}

const openEditAddr = (i) => {
  addrForm.value = { ...addresses.value[i] }
  editingAddrIdx.value = i
  showAddrForm.value = true
}

const cancelAddr = () => {
  showAddrForm.value = false
}

const saveAddr = async () => {
  savingAddr.value = true
  await new Promise((r) => setTimeout(r, 800))

  if (editingAddrIdx.value !== null) {
    addresses.value[editingAddrIdx.value] = { ...addrForm.value }
  } else {
    addresses.value.push({
      ...addrForm.value,
      id: Date.now(),
      isDefault: addresses.value.length === 0,
    })
  }

  savingAddr.value = false
  showAddrForm.value = false
  showToast('Địa chỉ đã được cập nhật!')
}

const setDefaultAddr = (i) => {
  addresses.value = addresses.value.map((a, idx) => ({
    ...a,
    isDefault: idx === i,
  }))
}

const removeAddr = (i) => {
  addresses.value.splice(i, 1)
}

// ════════════════════════════════════════════════
//  TAB 4 — PASSWORD
// ════════════════════════════════════════════════
const pwForm = ref({ current: '', newPass: '', confirm: '' })
const showPw = ref({ current: false, newPass: false, confirm: false })
const savingPw = ref(false)
const pwErrors = ref({})

const pwStrength = computed(() => {
  const p = pwForm.value.newPass
  if (!p) return 0
  let s = 0
  if (p.length >= 8) s++
  if (/[A-Z]/.test(p)) s++
  if (/[0-9]/.test(p)) s++
  if (/[^A-Za-z0-9]/.test(p)) s++
  return s
})

const pwStrengthLabel = computed(
  () => ['', 'Yếu', 'Trung bình', 'Mạnh', 'Rất mạnh'][pwStrength.value]
)

const pwStrengthColor = computed(
  () => ['', '#ef4444', '#f59e0b', '#2563eb', '#16a34a'][pwStrength.value]
)

const pwRequirements = computed(() => [
  { label: 'Tối thiểu 8 ký tự', ok: pwForm.value.newPass.length >= 8 },
  { label: 'Có chữ hoa (A-Z)', ok: /[A-Z]/.test(pwForm.value.newPass) },
  { label: 'Có số (0-9)', ok: /[0-9]/.test(pwForm.value.newPass) },
  { label: 'Có ký tự đặc biệt', ok: /[^A-Za-z0-9]/.test(pwForm.value.newPass) },
])

const savePw = async () => {
  pwErrors.value = {}

  if (!pwForm.value.current) {
    pwErrors.value.current = 'Vui lòng nhập mật khẩu hiện tại'
  }

  if (!pwForm.value.newPass) {
    pwErrors.value.newPass = 'Vui lòng nhập mật khẩu mới'
  } else if (pwStrength.value < 2) {
    pwErrors.value.newPass = 'Mật khẩu quá yếu'
  }

  if (pwForm.value.newPass !== pwForm.value.confirm) {
    pwErrors.value.confirm = 'Mật khẩu không khớp'
  }

  if (Object.keys(pwErrors.value).length) return

  savingPw.value = true
  await new Promise((r) => setTimeout(r, 1000))
  savingPw.value = false
  pwForm.value = { current: '', newPass: '', confirm: '' }
  showToast('Đổi mật khẩu thành công!')
}

// ════════════════════════════════════════════════
//  TAB 5 — PROMOTIONS
// ════════════════════════════════════════════════
const promotions = ref([])
const promoPage = ref(1)
const promoPerPage = 5

const fetchPromotions = async () => {
  try {
    const res = await api.get('/promotions')
    // API trả về array trực tiếp (không có wrapper success)
    promotions.value = Array.isArray(res.data) ? res.data : []
  } catch (error) {
    console.error('Lỗi tải khuyến mãi:', error)
  }
}

const paginatedPromos = computed(() => {
  const start = (promoPage.value - 1) * promoPerPage
  return promotions.value.slice(start, start + promoPerPage)
})

const totalPromoPages = computed(() =>
  Math.ceil(promotions.value.length / promoPerPage)
)

const promoStatusMap = {
  running: { label: 'Đang chạy',   color: '#16a34a', bg: '#dcfce7' },
  open:    { label: 'Sắp diễn ra', color: '#2563eb', bg: '#dbeafe' },
  ended:   { label: 'Đã kết thúc', color: '#94a3b8', bg: '#f1f5f9' },
}
</script>

<template>
  <Header />
  <div class="page">

    <!-- Global toast -->
    <transition name="toast">
      <div class="toast" v-if="toast.show">
        <svg viewBox="0 0 24 24" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        {{ toast.msg }}
      </div>
    </transition>

    <!-- Order detail modal -->
    <transition name="fade">
      <div class="overlay" v-if="selectedOrder" @click.self="selectedOrder = null">
        <div class="modal">
          <div class="modal-head">
            <div>
              <h2 class="modal-title">Chi tiết đơn hàng</h2>
              <p class="modal-id">Mã đơn: #VT-2026-{{ String(selectedOrder.id_dathang).padStart(3, '0') }}</p>
            </div>
            <button class="close-btn" @click="selectedOrder = null">
              <svg viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <div class="modal-status" :style="{ color: statusMap[selectedOrder.status].color, background: statusMap[selectedOrder.status].bg }">
              {{ statusMap[selectedOrder.status].label }}
            </div>

            <div v-if="selectedOrder.status === 'cancelled' && selectedOrder.lydo" class="alert alert-danger mb-4" style="font-size: 13px; padding: 12px; border-radius: 10px;">
              <strong>Lý do hủy:</strong> {{ selectedOrder.lydo }}
            </div>

            <div class="timeline">
              <div class="tl-item" v-for="(step, i) in selectedOrder.steps" :key="i" :class="{ done: step.done }">
                <div class="tl-col">
                  <div class="tl-dot"><svg v-if="step.done" viewBox="0 0 24 24" fill="none"><polyline points="20 6 9 17 4 12"/></svg></div>
                  <div class="tl-line" v-if="i < selectedOrder.steps.length - 1" :class="{ done: step.done }"></div>
                </div>
                <div class="tl-content">
                  <p class="tl-label">{{ step.label }}</p>
                  <p class="tl-date">{{ step.date || '—' }}</p>
                </div>
              </div>
            </div>
            <h3 class="section-title">Sản phẩm</h3>
            <div class="modal-item" v-for="item in selectedOrder.items" :key="item.name">
              <img :src="item.img" :alt="item.name" />
              <div class="modal-item-info">
                <p class="modal-item-name">{{ item.name }}</p>
                <p class="modal-item-qty">Số lượng: {{ item.qty }}</p>
              </div>
              <p class="modal-item-price">{{ item.price }}</p>
            </div>
            <div class="modal-footer">
              <div class="modal-btns">
                <button v-if="['pending', 'confirmed'].includes(selectedOrder.status)"
                  class="btn-modal-huy" @click="openCancelModal(selectedOrder)">Hủy đơn</button>
                <button v-if="['done', 'cancelled'].includes(selectedOrder.status)"
                  class="btn-modal-mua" @click="handleReorder(selectedOrder)">Mua lại</button>
              </div>
              <div class="modal-total-wrap">
                <span class="total-label">Tổng cộng</span>
                <span class="total-value">{{ selectedOrder.total }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Cancellation Modal -->
    <transition name="fade">
      <div class="overlay" v-if="showCancelModal" @click.self="showCancelModal = false" style="z-index: 1001;">
        <div class="modal mini-modal">
          <div class="modal-head">
            <h2 class="modal-title">Lý do hủy đơn</h2>
            <button class="close-btn" @click="showCancelModal = false">
              <svg viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <p class="mb-3 text-muted" style="font-size: 13px;">Vui lòng chọn lý do bạn muốn hủy đơn hàng này. Thao tác này không thể hoàn tác.</p>
            <textarea v-model="cancelReason" class="form-control cancel-textarea" placeholder="Nhập lý do hủy tại đây..." rows="3"></textarea>
            <div style="display: flex; justify-content: space-between; gap: 12px; margin-top: 24px;">
              <button class="btn-danger-confirm" @click="confirmCancel" :disabled="isSubmitting">
                {{ isSubmitting ? 'Đang xử lý...' : 'Xác nhận hủy' }}
              </button>
              <button class="btn-cancel" @click="showCancelModal = false">Quay lại</button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <div class="container">

      <!-- ── SIDEBAR ── -->
      <aside class="sidebar">
        <div class="avatar-section">
          <div class="avatar-sidebar-container">
            <div class="avatar-circle">
              <img :src="sidebarAvatarUrl" :alt="user.name" class="profile-avatar" />
            </div>
          </div>
          <h2 class="sidebar-name">{{ user.name }}</h2>
          <span class="sidebar-badge">{{ user.memberSince }}</span>
          <p class="sidebar-join">Thành viên từ {{ user.joinDate }}</p>
        </div>

        <div class="stat-grid">
          <div class="stat-card" v-for="s in stats" :key="s.label">
            <svg v-if="s.icon==='orders'" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m9 12 2 2 4-4"/></svg>
            <svg v-else-if="s.icon==='heart'" viewBox="0 0 24 24" fill="none"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <svg v-else viewBox="0 0 24 24" fill="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            <span class="stat-val">{{ s.value }}</span>
            <span class="stat-lbl">{{ s.label }}</span>
          </div>
        </div>

        <!-- NAV BUTTONS -->
        <nav class="side-nav">
          <button
            v-for="tab in tabs" :key="tab.key"
            class="side-btn"
            :class="{ active: activeTab === tab.key }"
            @click="activeTab = tab.key"
          >
            <!-- person -->
            <svg v-if="tab.icon==='person'" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <!-- orders -->
            <svg v-else-if="tab.icon==='orders'" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            <!-- map -->
            <svg v-else-if="tab.icon==='map'" viewBox="0 0 24 24" fill="none"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <!-- tag (promotions) -->
            <svg v-else-if="tab.icon==='tag'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            <!-- lock -->
            <svg v-else viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>{{ tab.label }}</span>
            <svg class="arrow" viewBox="0 0 24 24" fill="none"><path d="m9 18 6-6-6-6"/></svg>
          </button>
        </nav>
      </aside>

      <!-- ── MAIN CONTENT ── -->
      <main class="main">

        <!-- ════ TAB: PROFILE ════ -->
        <div v-if="activeTab === 'profile'" class="card">
          <div class="card-header">
            <div>
              <h1 class="card-title">Thông tin cá nhân</h1>
              <p class="card-sub">Quản lý thông tin hồ sơ của bạn</p>
            </div>
            <button v-if="!editing" class="btn-edit" @click="startEdit">
              <svg viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Chỉnh sửa
            </button>
          </div>
          <div v-if="!editing" class="info-grid">
            <div class="info-row"><span class="info-lbl">Họ và tên</span><span class="info-val">{{ user.name }}</span></div>
            <div class="info-row"><span class="info-lbl">Email</span><span class="info-val">{{ user.email }}</span></div>
            <div class="info-row"><span class="info-lbl">Số điện thoại</span><span class="info-val">{{ user.phone }}</span></div>
            <div class="info-row"><span class="info-lbl">Ngày sinh</span><span class="info-val">{{ user.birthday }}</span></div>
            <div class="info-row">
              <span class="info-lbl">Giới tính</span>
              <span class="info-val">
                {{ ['male', 'Nam'].includes(user.gender) ? 'Nam' : ['female', 'Nữ'].includes(user.gender) ? 'Nữ' : 'Khác' }}
              </span>
            </div>
          </div>
          <form v-else class="edit-form" @submit.prevent="saveProfile">
            <div class="form-avatar-section">
              <div class="form-avatar-dashed-border" @click="triggerAvatarUpload">
                <div class="form-avatar-circle">
                  <img :src="formAvatarUrl" :alt="user.name" class="form-avatar-img" />
                  <div class="form-avatar-plus-overlay">
                    <i class="fas fa-plus"></i>
                  </div>
                </div>
              </div>
              <p class="form-avatar-upload-text">Tải ảnh lên</p>
              <input
                type="file"
                ref="fileInput"
                class="d-none"
                accept="image/jpeg, image/png"
                @change="handleAvatarUpload"
              />
            </div>

            <div class="form-group"><label>Họ và tên</label><input v-model="profileForm.name" type="text" required /></div>
            <div class="form-group"><label>Email</label><input v-model="profileForm.email" type="email" required /></div>
            <div class="form-group"><label>Số điện thoại</label><input v-model="profileForm.phone" type="tel" /></div>
            <div class="form-row">
              <div class="form-group"><label>Ngày sinh</label><input v-model="profileForm.birthday" type="date" /></div>
              <div class="form-group">
                <label>Giới tính</label>
                <select v-model="profileForm.gender">
                  <option value="male">Nam</option>
                  <option value="female">Nữ</option>
                  <option value="other">Khác</option>
                </select>
              </div>
            </div>
            <div class="form-actions">
              <button type="button" class="btn-cancel" @click="cancelEdit">Hủy</button>
              <button type="submit" class="btn-save" :disabled="savingProfile">
                <svg v-if="savingProfile" class="spin" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                {{ savingProfile ? 'Đang lưu...' : 'Lưu thay đổi' }}
              </button>
            </div>
          </form>
        </div>

        <!-- ════ TAB: ORDERS ════ -->
        <div v-else-if="activeTab === 'orders'">
          <div class="page-header-inline" style="padding-bottom: 24px; border-bottom: 1px solid #f1f5f9; margin-bottom: 24px;">
            <h1 class="card-title" style="font-size: 26px; color: #1e293b;">Lịch Sử Đơn Hàng</h1>
          </div>
          <div class="order-tabs">
            <button v-for="t in orderTabs" :key="t.key" class="order-tab" :class="{ active: orderTab === t.key }" @click="orderTab = t.key">
              {{ t.label }}
              <span class="otab-count" v-if="t.key !== 'all'">{{ orders.filter(o => o.status === t.key).length }}</span>
            </button>
          </div>

          <div class="table-card">
            <table class="order-data-table">
              <thead>
                <tr>
                  <th>MÃ ĐƠN HÀNG</th>
                  <th>Ngày đặt</th>
                  <th>Tổng tiền</th>
                  <th>Trạng thái</th>
                  <th>Hành động</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredOrders.length === 0">
                  <td colspan="5" class="empty-state-cell">
                    <div class="empty-msg">
                      <svg viewBox="0 0 24 24" fill="none" class="empty-icon"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                      <p>Không có đơn hàng nào</p>
                    </div>
                  </td>
                </tr>
                <tr v-for="order in paginatedOrders" :key="order.id" class="order-row">
                  <td class="id-col"><span class="order-id">#VT-2026-{{ String(order.id_dathang).padStart(3, '0') }}</span></td>
                  <td>{{ order.date }}</td>
                  <td>{{ order.total }}</td>
                  <td>
                    <span class="status-cell" :style="{ color: statusMap[order.status].color }">
                      {{ statusMap[order.status].label }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <button class="btn-xem" @click="selectedOrder = order">Xem</button>
                      <button v-if="['done', 'cancelled'].includes(order.status)" class="btn-mua-lai" @click="handleReorder(order)">Mua lại</button>
                      <button v-if="['pending', 'confirmed'].includes(order.status)" class="btn-huy-don" @click="openCancelModal(order)">Hủy đơn</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="pagination-footer" v-if="totalPages > 1">
              <p class="pagination-info">Hiển thị {{ (currentPage - 1) * itemsPerPage + 1 }} – {{ Math.min(currentPage * itemsPerPage, filteredOrders.length) }} của {{ filteredOrders.length }} đơn hàng</p>
              <div class="pagination">
                <button class="p-arrow" :disabled="currentPage === 1" @click="currentPage--">‹ Trước</button>
                <div class="p-nums">
                  <button v-for="p in totalPages" :key="p" class="p-num" :class="{ active: currentPage === p }" @click="currentPage = p">{{ p }}</button>
                </div>
                <button class="p-arrow" :disabled="currentPage === totalPages" @click="currentPage++">Sau ›</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ════ TAB: ADDRESS ════ -->
        <div v-else-if="activeTab === 'address'">
          <div class="page-header-inline" style="display:flex;align-items:flex-start;justify-content:space-between;">
            <div><h1 class="card-title">Địa chỉ của tôi</h1><p class="card-sub">Quản lý địa chỉ giao hàng</p></div>
            <button class="btn-add" @click="openAddAddr" v-if="!showAddrForm">
              <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14"/></svg>
              Thêm địa chỉ
            </button>
          </div>
          <transition name="slide">
            <div class="card form-card" v-if="showAddrForm">
              <h2 class="form-title">{{ editingAddrIdx !== null ? 'Chỉnh sửa địa chỉ' : 'Thêm địa chỉ mới' }}</h2>
              <form @submit.prevent="saveAddr" class="form-grid">
                <div class="form-group"><label>Họ và tên người nhận</label><input v-model="addrForm.name" type="text" placeholder="Nhập họ tên" required /></div>
                <div class="form-group"><label>Số điện thoại</label><input v-model="addrForm.phone" type="tel" placeholder="Nhập số điện thoại" required /></div>
                <div class="form-group"><label>Tỉnh / Thành phố</label><select v-model="addrForm.province" required><option value="" disabled>Chọn tỉnh/thành</option><option v-for="p in provinces" :key="p" :value="p">{{ p }}</option></select></div>
                <div class="form-group"><label>Quận / Huyện</label><input v-model="addrForm.district" type="text" placeholder="Nhập quận/huyện" required /></div>
                <div class="form-group"><label>Phường / Xã</label><input v-model="addrForm.ward" type="text" placeholder="Nhập phường/xã" required /></div>
                <div class="form-group form-full"><label>Địa chỉ chi tiết</label><input v-model="addrForm.detail" type="text" placeholder="Số nhà, tên đường..." required /></div>
                <div class="form-group form-full">
                  <label class="checkbox-label"><input type="checkbox" v-model="addrForm.isDefault" /><span>Đặt làm địa chỉ mặc định</span></label>
                </div>
                <div class="form-actions form-full">
                  <button type="button" class="btn-cancel" @click="cancelAddr">Hủy</button>
                  <button type="submit" class="btn-save" :disabled="savingAddr">
                    <svg v-if="savingAddr" class="spin" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                    {{ savingAddr ? 'Đang lưu...' : 'Lưu địa chỉ' }}
                  </button>
                </div>
              </form>
            </div>
          </transition>
          <div class="addr-list">
            <div v-if="addresses.length === 0 && !showAddrForm" class="empty">
              <svg viewBox="0 0 24 24" fill="none"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <p>Chưa có địa chỉ nào</p>
            </div>
            <div class="addr-card" v-for="(addr, i) in addresses" :key="addr.id" :class="{ 'is-default': addr.isDefault }">
              <div class="addr-head">
                <div class="addr-name-wrap"><span class="addr-name">{{ addr.name }}</span><span class="addr-sep">|</span><span class="addr-phone">{{ addr.phone }}</span></div>
                <span class="default-badge" v-if="addr.isDefault">Mặc định</span>
              </div>
              <p class="addr-full">{{ addr.detail }}, {{ addr.ward }}, {{ addr.district }}, {{ addr.province }}</p>
              <div class="addr-actions">
                <button class="addr-btn" @click="openEditAddr(i)"><svg viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Chỉnh sửa</button>
                <button class="addr-btn addr-btn-default" v-if="!addr.isDefault" @click="setDefaultAddr(i)"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>Đặt mặc định</button>
                <button class="addr-btn addr-btn-delete" @click="removeAddr(i)"><svg viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>Xóa</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ════ TAB: PROMOTIONS ════ -->
        <div v-else-if="activeTab === 'promotions'">
          <div class="page-header-inline" style="padding-bottom: 24px; border-bottom: 1px solid #f1f5f9; margin-bottom: 24px;">
            <h1 class="card-title" style="font-size: 26px; color: #1e293b;">Khuyến Mãi</h1>
            <p class="card-sub">Danh sách mã và chương trình khuyến mãi hiện có</p>
          </div>

          <div class="table-card">
            <table class="order-data-table">
              <thead>
                <tr>
                  <th>TÊN</th>
                  <th>MÃ</th>
                  <th>LOẠI</th>
                  <th>GIÁ TRỊ</th>
                  <th>THỜI GIAN HẾT HẠN  </th>
                  <th>TRẠNG THÁI</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="promotions.length === 0">
                  <td colspan="6" class="empty-state-cell">
                    <div class="empty-msg">
                      <svg viewBox="0 0 24 24" fill="none" class="empty-icon" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                        <line x1="7" y1="7" x2="7.01" y2="7" stroke-width="2.5"/>
                      </svg>
                      <p>Không có khuyến mãi nào</p>
                    </div>
                  </td>
                </tr>
                <tr v-for="promo in paginatedPromos" :key="promo.id" class="order-row">
                  <td><span style="font-weight:600; color:#1e293b;">{{ promo.name }}</span></td>
                  <td><span class="promo-code-badge">{{ promo.code }}</span></td>
                  <td style="color:#64748b; font-size:13px;">
                    {{ promo.type === 'percent' ? 'Phần trăm' : 'Cố định' }}
                  </td>
                  <td style="font-weight:700; color:#2563eb;">
                    {{ promo.type === 'percent'
                      ? promo.value + '%'
                      : new Intl.NumberFormat('vi-VN').format(promo.value) + 'đ' }}
                  </td>
                  <td style="font-size:13px; color:#64748b;">
                    <span v-if="promo.end_date">{{ new Date(promo.end_date).toLocaleDateString('vi-VN') }}</span>
                    <span v-else>Không giới hạn</span>
                  </td>
                  <td>
                    <span :style="{
                      color: (promoStatusMap[promo.status] || promoStatusMap.ended).color,
                      background: (promoStatusMap[promo.status] || promoStatusMap.ended).bg,
                      padding: '4px 12px',
                      borderRadius: '99px',
                      fontSize: '12px',
                      fontWeight: '700',
                      display: 'inline-block'
                    }">
                      {{ (promoStatusMap[promo.status] || promoStatusMap.ended).label }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination-footer" v-if="totalPromoPages > 1">
              <p class="pagination-info">
                Hiển thị {{ (promoPage - 1) * promoPerPage + 1 }} –
                {{ Math.min(promoPage * promoPerPage, promotions.length) }}
                của {{ promotions.length }} khuyến mãi
              </p>
              <div class="pagination">
                <button class="p-arrow" :disabled="promoPage === 1" @click="promoPage--">‹ Trước</button>
                <div class="p-nums">
                  <button
                    v-for="p in totalPromoPages" :key="p"
                    class="p-num" :class="{ active: promoPage === p }"
                    @click="promoPage = p">{{ p }}</button>
                </div>
                <button class="p-arrow" :disabled="promoPage === totalPromoPages" @click="promoPage++">Sau ›</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ════ TAB: PASSWORD ════ -->
        <div v-else-if="activeTab === 'password'">
          <div class="page-header-inline"><h1 class="card-title">Đổi mật khẩu</h1><p class="card-sub">Cập nhật mật khẩu để bảo mật tài khoản</p></div>
          <div class="pw-layout">
            <div class="card">
              <form @submit.prevent="savePw" class="form">
                <div class="form-group" :class="{ error: pwErrors.current }">
                  <label>Mật khẩu hiện tại</label>
                  <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input :type="showPw.current ? 'text' : 'password'" v-model="pwForm.current" placeholder="••••••••" />
                    <button type="button" class="eye-btn" @click="showPw.current = !showPw.current">
                      <svg viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                  </div>
                  <span class="err-msg" v-if="pwErrors.current">{{ pwErrors.current }}</span>
                </div>
                <div class="form-group" :class="{ error: pwErrors.newPass }">
                  <label>Mật khẩu mới</label>
                  <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input :type="showPw.newPass ? 'text' : 'password'" v-model="pwForm.newPass" placeholder="••••••••" />
                    <button type="button" class="eye-btn" @click="showPw.newPass = !showPw.newPass">
                      <svg viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                  </div>
                  <div class="strength-bar" v-if="pwForm.newPass">
                    <div class="strength-track"><div class="strength-fill" :style="{ width: (pwStrength/4*100)+'%', background: pwStrengthColor }"></div></div>
                    <span class="strength-label" :style="{ color: pwStrengthColor }">{{ pwStrengthLabel }}</span>
                  </div>
                  <span class="err-msg" v-if="pwErrors.newPass">{{ pwErrors.newPass }}</span>
                </div>
                <div class="form-group" :class="{ error: pwErrors.confirm }">
                  <label>Xác nhận mật khẩu mới</label>
                  <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input :type="showPw.confirm ? 'text' : 'password'" v-model="pwForm.confirm" placeholder="••••••••" />
                    <button type="button" class="eye-btn" @click="showPw.confirm = !showPw.confirm">
                      <svg viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                  </div>
                  <span class="err-msg" v-if="pwErrors.confirm">{{ pwErrors.confirm }}</span>
                </div>
                <button type="submit" class="btn-save" style="margin-top:4px" :disabled="savingPw">
                  <svg v-if="savingPw" class="spin" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                  {{ savingPw ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}
                </button>
              </form>
            </div>
            <div>
              <div class="req-card">
                <h3 class="req-title">Yêu cầu mật khẩu</h3>
                <ul class="req-list">
                  <li v-for="req in pwRequirements" :key="req.label" :class="{ ok: req.ok }">
                    <svg v-if="req.ok" viewBox="0 0 24 24" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
                    <svg v-else viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10"/></svg>
                    {{ req.label }}
                  </li>
                </ul>
              </div>
              <div class="tip-card">
                <div class="tip-icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg></div>
                <div>
                  <h4 class="tip-title">Mẹo bảo mật</h4>
                  <ul class="tip-list">
                    <li>Không dùng thông tin cá nhân</li>
                    <li>Dùng mật khẩu riêng cho mỗi trang</li>
                    <li>Thay đổi định kỳ 3–6 tháng</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>
  <Footer />
</template>

<style scoped>
/* ── BASE ── */
.page { min-height:100vh; background:#f8fafc; padding:32px 30px; font-family:system-ui,sans-serif; }
.container { max-width:1100px; margin:auto; display:grid; grid-template-columns:280px 1fr; gap:24px; align-items:start; }

/* ── SIDEBAR ── */
.sidebar { background:#fff; border-radius:20px; border:1px solid #e5e7eb; overflow:hidden; position:sticky; top:20px; }
.avatar-section { padding:28px 24px 20px; text-align:center; border-bottom:1px solid #f1f5f9; }
.avatar-wrap { position:relative; display:inline-block; margin-bottom:12px; }
.avatar { width:88px; height:88px; border-radius:50%; object-fit:cover; border:3px solid #dbeafe; }
.avatar-edit { position:absolute; bottom:0; right:0; width:28px; height:28px; border-radius:50%; background:#2563eb; border:2px solid #fff; cursor:pointer; display:flex; align-items:center; justify-content:center; }
.avatar-edit svg { width:13px; height:13px; stroke:#fff; stroke-width:2; fill:none; }
.sidebar-name { font-size:16px; font-weight:700; color:#0f172a; margin:0 0 6px; }
.sidebar-badge { display:inline-block; font-size:11px; font-weight:700; color:#2563eb; background:#dbeafe; padding:3px 10px; border-radius:20px; }
.sidebar-join { font-size:12px; color:#94a3b8; margin:8px 0 0; }

.stat-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1px; background:#f1f5f9; border-top:1px solid #f1f5f9; border-bottom:1px solid #f1f5f9; }
.stat-card { background:#fff; padding:14px 8px; text-align:center; display:flex; flex-direction:column; align-items:center; gap:4px; }
.stat-card svg { width:18px; height:18px; stroke:#2563eb; stroke-width:1.8; fill:none; }
.stat-val { font-size:16px; font-weight:700; color:#0f172a; }
.stat-lbl { font-size:11px; color:#64748b; }

/* ── SIDEBAR NAV BUTTONS ── */
.side-nav { padding:10px 12px 16px; display:flex; flex-direction:column; gap:3px; }
.side-btn {
  width:100%; display:flex; align-items:center; gap:10px;
  padding:11px 14px; border-radius:12px; border:none;
  background:transparent; cursor:pointer;
  color:#374151; font-size:13.5px; font-weight:500;
  text-align:left; transition:all 0.18s;
}
.side-btn svg:not(.arrow) { width:17px; height:17px; stroke:#64748b; stroke-width:1.8; fill:none; flex-shrink:0; transition:stroke 0.18s; }
.side-btn span { flex:1; }
.side-btn .arrow { width:14px; height:14px; stroke:#d1d5db; stroke-width:2; fill:none; flex-shrink:0; opacity:0; transition:opacity 0.18s, stroke 0.18s; }
.side-btn:hover { background:#f1f5f9; color:#1e293b; }
.side-btn:hover svg:not(.arrow) { stroke:#374151; }
.side-btn:hover .arrow { opacity:1; }
.side-btn.active { background:#eff6ff; color:#2563eb; font-weight:600; }
.side-btn.active svg:not(.arrow) { stroke:#2563eb; }
.side-btn.active .arrow { opacity:1; stroke:#2563eb; }

/* ── MAIN ── */
.main { min-width:0; }
.card { background:#fff; border-radius:20px; border:1px solid #e5e7eb; padding:28px 32px; }
.page-header-inline { margin-bottom:20px; }
.card-title { font-size:20px; font-weight:700; color:#0f172a; margin:0 0 4px; }
.card-sub { font-size:13px; color:#64748b; margin:0; }
.card-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:28px; }

/* PROFILE */
.btn-edit { display:flex; align-items:center; gap:7px; padding:9px 18px; border-radius:10px; background:#f1f5f9; border:1px solid #e2e8f0; color:#374151; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.15s; }
.btn-edit:hover { background:#dbeafe; color:#2563eb; border-color:#bfdbfe; }
.btn-edit svg { width:14px; height:14px; stroke:currentColor; stroke-width:2; fill:none; }
.info-grid { display:flex; flex-direction:column; }
.info-row { display:flex; align-items:center; padding:16px 0; border-bottom:1px solid #f1f5f9; }
.info-row:last-child { border-bottom:none; }
.info-lbl { width:160px; flex-shrink:0; font-size:13px; color:#64748b; font-weight:500; }
.info-val { font-size:14px; color:#1e293b; font-weight:500; }

/* FORMS */
.edit-form,.form { display:flex; flex-direction:column; gap:18px; }
.form-row,.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-full { grid-column:1/-1; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group label { font-size:13px; font-weight:600; color:#374151; }
.form-group input,.form-group select { padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; color:#1e293b; outline:none; transition:border-color 0.2s,box-shadow 0.2s; background:#fff; }
.form-group input:focus,.form-group select:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
.form-group.error input { border-color:#ef4444; }
.checkbox-label { display:flex; align-items:center; gap:10px; cursor:pointer; font-size:14px; color:#374151; font-weight:500; }
.checkbox-label input[type="checkbox"] { width:16px; height:16px; accent-color:#2563eb; cursor:pointer; }
.form-actions { display:flex; gap:12px; justify-content:flex-end; padding-top:8px; }
.btn-cancel { padding:10px 22px; border-radius:10px; background:#f1f5f9; border:1px solid #e2e8f0; color:#374151; font-size:14px; font-weight:600; cursor:pointer; }
.btn-cancel:hover { background:#e2e8f0; }
.btn-save { display:flex; align-items:center; justify-content:center; gap:8px; padding:10px 24px; border-radius:10px; background:#2563eb; border:none; color:#fff; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.15s; }
.btn-save:hover { background:#1d4ed8; }
.btn-save:disabled { opacity:0.7; cursor:not-allowed; }
.spin { width:15px; height:15px; stroke:#fff; stroke-width:2.5; fill:none; animation:spin 0.8s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.err-msg { font-size:12px; color:#ef4444; font-weight:500; }

/* ORDERS */
.order-tabs { display:flex; gap:6px; background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:6px; margin-bottom:20px; flex-wrap:wrap; }
.order-tab { padding:8px 14px; border-radius:10px; border:none; background:transparent; font-size:13px; font-weight:500; color:#64748b; cursor:pointer; transition:all 0.15s; display:flex; align-items:center; gap:6px; }
.order-tab:hover { background:#f1f5f9; }
.order-tab.active { background:#2563eb; color:#fff; font-weight:600; }
.otab-count { background:rgba(255,255,255,0.25); padding:1px 7px; border-radius:99px; font-size:11px; }
.order-tab:not(.active) .otab-count { background:#f1f5f9; color:#64748b; }

.table-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.order-data-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
.order-data-table th { background: #f8fafc; padding: 16px 20px; font-weight: 600; color: #64748b; border-bottom: 1px solid #f1f5f9; }
.order-data-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
.order-row:hover { background: #fafafa; }
.id-col { font-weight: 700; color: #2563eb; }
.status-cell { font-weight: 600; font-size: 13px; }

.btn-group { display: flex; gap: 8px; }
.btn-xem { background: #2563eb; color: #fff; border: none; padding: 6px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; font-size: 13px; }
.btn-xem:hover { background: #1d4ed8; }
.btn-mua-lai { background: #10b981; color: #fff; border: none; padding: 6px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; font-size: 13px; }
.btn-mua-lai:hover { background: #059669; }
.btn-huy-don { background: #fff; color: #ef4444; border: 1px solid #ef4444; padding: 5px 15px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; }
.btn-huy-don:hover { background: #ef4444; color: #fff; }

.pagination-footer { padding: 20px; border-top: 1px solid #f1f5f9; display: flex; flex-direction: column; align-items: center; gap: 12px; background: #fff; }
.pagination-info { font-size: 13px; color: #64748b; margin: 0; }
.pagination { display: flex; align-items: center; gap: 10px; }
.p-arrow { background: #f1f5f9; border: none; padding: 6px 12px; border-radius: 6px; color: #64748b; font-weight: 600; cursor: pointer; font-size: 13px; }
.p-arrow:disabled { opacity: 0.5; cursor: not-allowed; }
.p-nums { display: flex; gap: 6px; }
.p-num { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-weight: 600; cursor: pointer; }
.p-num.active { background: #2563eb; border-color: #2563eb; color: #fff; }

.empty-state-cell { padding: 60px 0; }
.empty-msg { text-align: center; color: #94a3b8; }
.empty-icon { width: 44px; height: 44px; stroke: #cbd5e1; margin-bottom: 10px; display: block; margin: 0 auto; }

/* ADDRESS */
.btn-add { display:flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px; background:#2563eb; border:none; color:#fff; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.15s; }
.btn-add:hover { background:#1d4ed8; }
.btn-add svg { width:15px; height:15px; stroke:#fff; stroke-width:2.5; fill:none; }
.form-card { margin-bottom:16px; }
.form-title { font-size:17px; font-weight:700; color:#0f172a; margin:0 0 20px; }
.addr-list { display:flex; flex-direction:column; gap:12px; }
.addr-card { background:#fff; border-radius:16px; border:1.5px solid #e5e7eb; padding:18px 22px; }
.addr-card.is-default { border-color:#bfdbfe; }
.addr-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.addr-name-wrap { display:flex; align-items:center; gap:8px; }
.addr-name { font-size:14px; font-weight:700; color:#0f172a; }
.addr-sep { color:#cbd5e1; }
.addr-phone { font-size:13px; color:#64748b; }
.default-badge { font-size:11px; font-weight:700; color:#2563eb; background:#dbeafe; padding:3px 10px; border-radius:99px; }
.addr-full { font-size:13px; color:#374151; margin:0 0 14px; line-height:1.5; }
.addr-actions { display:flex; gap:8px; flex-wrap:wrap; }
.addr-btn { display:flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; border:1px solid #e2e8f0; background:#f8fafc; color:#374151; font-size:12px; font-weight:500; cursor:pointer; transition:all 0.15s; }
.addr-btn svg { width:13px; height:13px; stroke:currentColor; stroke-width:2; fill:none; }
.addr-btn:hover { background:#f1f5f9; }
.addr-btn-default:hover { background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }
.addr-btn-delete:hover { background:#fee2e2; color:#dc2626; border-color:#fecaca; }

/* PASSWORD */
.pw-layout { display:grid; grid-template-columns:1fr 300px; gap:16px; align-items:start; }
.input-wrap { position:relative; display:flex; align-items:center; }
.input-icon { position:absolute; left:14px; width:16px; height:16px; stroke:#94a3b8; stroke-width:1.8; fill:none; pointer-events:none; }
.input-wrap input { width:100%; padding:11px 44px 11px 40px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; color:#1e293b; outline:none; transition:border-color 0.2s,box-shadow 0.2s; box-sizing:border-box; }
.input-wrap input:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
.form-group.error .input-wrap input { border-color:#ef4444; }
.eye-btn { position:absolute; right:12px; width:28px; height:28px; border:none; background:transparent; cursor:pointer; display:flex; align-items:center; justify-content:center; }
.eye-btn svg { width:16px; height:16px; stroke:#94a3b8; stroke-width:1.8; fill:none; }
.strength-bar { display:flex; align-items:center; gap:10px; margin-top:4px; }
.strength-track { flex:1; height:5px; background:#e2e8f0; border-radius:99px; overflow:hidden; }
.strength-fill { height:100%; border-radius:99px; transition:width 0.3s,background 0.3s; }
.strength-label { font-size:12px; font-weight:600; min-width:72px; text-align:right; }
.req-card { background:#fff; border-radius:16px; border:1px solid #e5e7eb; padding:20px; margin-bottom:12px; }
.req-title { font-size:13px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px; margin:0 0 14px; }
.req-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:9px; }
.req-list li { display:flex; align-items:center; gap:10px; font-size:13px; color:#94a3b8; font-weight:500; transition:color 0.2s; }
.req-list li svg { width:16px; height:16px; stroke:#cbd5e1; stroke-width:2.5; fill:none; flex-shrink:0; transition:stroke 0.2s; }
.req-list li.ok { color:#16a34a; }
.req-list li.ok svg { stroke:#16a34a; }
.tip-card { background:#eff6ff; border:1px solid #bfdbfe; border-radius:16px; padding:18px; display:flex; gap:12px; }
.tip-icon svg { width:20px; height:20px; stroke:#2563eb; stroke-width:1.8; fill:none; }
.tip-title { font-size:13px; font-weight:700; color:#1e40af; margin:0 0 8px; }
.tip-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:6px; }
.tip-list li { font-size:12px; color:#3b82f6; padding-left:12px; position:relative; }
.tip-list li::before { content:'•'; position:absolute; left:0; }

/* PROMOTIONS */
.promo-code-badge {
  display: inline-block;
  background: #fef3c7;
  color: #b45309;
  border: 1px dashed #fbbf24;
  padding: 3px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.5px;
  font-family: monospace;
}

/* EMPTY */
.empty { text-align:center; padding:48px 0; color:#94a3b8; }
.empty svg { width:44px; height:44px; stroke:#cbd5e1; stroke-width:1.5; fill:none; margin-bottom:10px; }
.empty p { font-size:14px; }

/* MODAL */
.overlay { position:fixed; inset:0; background:rgba(15,23,42,0.5); z-index:9000; display:flex; align-items:center; justify-content:center; padding:20px; backdrop-filter:blur(3px); }
.modal { background:#fff; border-radius:20px; width:100%; max-width:520px; max-height:88vh; overflow-y:auto; }
.modal-head { display:flex; align-items:flex-start; justify-content:space-between; padding:22px 24px 0; }
.modal-title { font-size:17px; font-weight:700; color:#0f172a; margin:0 0 2px; }
.modal-id { font-size:12px; color:#94a3b8; margin:0; }
.close-btn { width:30px; height:30px; border-radius:50%; border:none; background:#f1f5f9; cursor:pointer; display:flex; align-items:center; justify-content:center; }
.close-btn svg { width:14px; height:14px; stroke:#64748b; stroke-width:2.5; }
.modal-body { padding:18px 24px 24px; }
.modal-status { display:inline-block; font-size:12px; font-weight:700; padding:5px 14px; border-radius:99px; margin-bottom:18px; }

/* TIMELINE */
.timeline { display:flex; flex-direction:column; margin-bottom:22px; }
.tl-item { display:flex; gap:14px; }
.tl-col { display:flex; flex-direction:column; align-items:center; flex-shrink:0; width:24px; }
.tl-dot { width:24px; height:24px; border-radius:50%; border:2px solid #e2e8f0; background:#f8fafc; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.tl-item.done .tl-dot { background:#2563eb; border-color:#2563eb; }
.tl-dot svg { width:12px; height:12px; stroke:#fff; stroke-width:3; fill:none; }
.tl-line { width:2px; flex:1; min-height:20px; background:#e2e8f0; margin:2px 0; }
.tl-line.done { background:#2563eb; }
.tl-content { padding-bottom:18px; flex:1; }
.tl-label { font-size:13px; font-weight:600; color:#1e293b; margin:3px 0 2px; }
.tl-date { font-size:11px; color:#94a3b8; margin:0; }
.section-title { font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin:0 0 10px; }
.modal-item { display:flex; align-items:center; gap:12px; padding:10px; background:#f8fafc; border-radius:12px; margin-bottom:8px; }
.modal-item img { width:48px; height:48px; border-radius:10px; object-fit:cover; border:1px solid #e5e7eb; flex-shrink:0; }
.modal-item-info { flex:1; min-width:0; }
.modal-item-name { font-size:13px; font-weight:600; color:#1e293b; margin:0 0 3px; }
.modal-item-qty { font-size:12px; color:#94a3b8; margin:0; }
.modal-item-price { font-size:14px; font-weight:700; color:#2563eb; }
.modal-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #f1f5f9; margin-top: 12px; }
.modal-btns { display: flex; gap: 12px; }
.btn-modal-huy { background: white; border: 1.2px solid #ef4444; color: #ef4444; padding: 7px 16px; border-radius: 9px; font-weight: 600; cursor: pointer; font-size: 13.5px; transition: all 0.2s; }
.btn-modal-huy:hover { background: #fee2e2; }
.btn-modal-mua { background: white; border: 1.5px solid #10b981; color: #10b981; padding: 7px 16px; border-radius: 9px; font-weight: 700; cursor: pointer; font-size: 13.5px; transition: all 0.2s; }
.btn-modal-mua:hover { background: #dcfce7; }
.modal-total-wrap { text-align: right; display: flex; flex-direction: column; gap: 2px; }
.total-label { font-size: 13px; color: #64748b; font-weight: 600; }
.total-value { font-size: 18px; font-weight: 800; color: #2563eb; }

/* TOAST */
.toast { position:fixed; top:24px; right:24px; z-index:9999; background:#0f172a; color:#fff; padding:12px 20px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:500; box-shadow:0 8px 24px rgba(0,0,0,0.2); }

/* SIDEBAR AVATAR */
.avatar-sidebar-container { width: 100px; height: 100px; margin: 0 auto 15px; }
.avatar-circle { width: 100%; height: 100%; border-radius: 50%; overflow: hidden; position: relative; border: 3px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.avatar-circle img { width: 100%; height: 100%; object-fit: cover; }

/* FORM AVATAR */
.form-avatar-section { display: flex; flex-direction: column; align-items: center; margin-bottom: 30px; }
.form-avatar-dashed-border { width: 120px; height: 120px; border: 2px dashed #cbd5e1; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; padding: 8px; }
.form-avatar-dashed-border:hover { border-color: #2563eb; transform: scale(1.02); }
.form-avatar-circle { width: 100%; height: 100%; background: #f1f5f9; border-radius: 50%; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.form-avatar-img { width: 100%; height: 100%; object-fit: cover; }
.form-avatar-plus-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(37, 99, 235, 0.1); display: flex; align-items: center; justify-content: center; color: #2563eb; font-size: 32px; opacity: 0.6; transition: all 0.3s ease; }
.form-avatar-dashed-border:hover .form-avatar-plus-overlay { opacity: 1; background: rgba(37, 99, 235, 0.2); }
.form-avatar-upload-text { margin-top: 12px; font-size: 14px; font-weight: 600; color: #2563eb; letter-spacing: 0.3px; }

.toast svg { width:18px; height:18px; stroke:#4ade80; stroke-width:2.5; fill:none; }
.toast-enter-active { transition:all 0.3s cubic-bezier(0.34,1.4,0.64,1); }
.toast-leave-active { transition:all 0.2s ease; }
.toast-enter-from { opacity:0; transform:translateY(-12px); }
.toast-leave-to { opacity:0; transform:translateY(-8px); }
.fade-enter-active { transition:opacity 0.2s; }
.fade-leave-active { transition:opacity 0.15s; }
.fade-enter-from,.fade-leave-to { opacity:0; }
.slide-enter-active { transition:all 0.22s ease; }
.slide-leave-active { transition:all 0.18s ease; }
.slide-enter-from { opacity:0; transform:translateY(-8px); }
.slide-leave-to { opacity:0; transform:translateY(-6px); }

.d-none { display: none; }
.d-flex { display: flex; align-items: center; }
.gap-2 { gap: 12px; }

/* CANCEL MODAL */
.cancel-textarea { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1.5px solid #2563eb; font-size: 14px; margin-bottom: 20px; background: #eff6ff; outline: none; font-family: inherit; transition: all 0.2s; resize: vertical; color: #1e293b; box-sizing: border-box; }
.cancel-textarea::placeholder { color: #94a3b8; }
.cancel-textarea:focus { box-shadow: 0 0 0 4px rgba(37,99,235,0.1); background: #fff; }
.btn-danger-confirm { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 24px; border-radius: 10px; background: #ef4444; border: none; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.btn-danger-confirm:hover { background: #dc2626; }
.btn-danger-confirm:disabled { opacity: 0.7; cursor: not-allowed; }
</style>