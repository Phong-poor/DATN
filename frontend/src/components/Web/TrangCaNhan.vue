<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

import api from '@/services/api'
import { getUser, updateUser, getToken } from '@/services/auth'
import echo from '@/services/echo'
import swal from '@/services/swal'
import AddressMapPicker from './TrinhChonBanDoDiaChi.vue'
import { normalizeImageUrl, productImageUrl, storageUrl } from '@/services/urls'
import { searchSuggestions, geocodeArea, geocodeWithFallback } from '@/services/geocode'
import { fetchProvinces as fetchAddressProvinces, fetchWardsByProvince as fetchAddressWardsByProvince } from '@/services/addressService'

// ── Active tab ────────────────────────────────────────────
const route = useRoute()
const activeTab = ref(route.query.tab && ['profile', 'orders', 'address', 'promotions', 'password'].includes(route.query.tab) ? route.query.tab : 'profile')

watch(() => route.query.tab, (newTab) => {
  if (newTab && ['profile', 'orders', 'address', 'promotions', 'password'].includes(newTab)) {
    activeTab.value = newTab
  }
})

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

// ── Review state ──────────────────────────────────────────
const showReviewModal = ref(false)
const reviewForm = ref({
  id_dathang: null,
  id_bienthe: null,
  productName: '',
  rating: 5,
  comment: ''
})
const hoverRating = ref(0)
const isSubmittingReview = ref(false)

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
  xu: 0,
})

const tempAvatarUrl = ref('')

const sidebarAvatarUrl = computed(() => {
  if (!user.value.avatar) return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.value.name || 'User')
  if (user.value.avatar.startsWith('http')) return user.value.avatar
  return normalizeImageUrl(user.value.avatar, '')
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
    xu: apiUser.xu !== undefined ? apiUser.xu : (user.value.xu || 0),
    memberSince: apiUser.role === 'admin' ? 'Quản trị viên' : 'Thành viên',
    joinDate: apiUser.created_at
      ? new Date(apiUser.created_at).toLocaleDateString('vi-VN')
      : user.value.joinDate,
  }
}

const fileInput = ref(null)
const selectedAvatarFile = ref(null)
const isUploadingAvatar = ref(false)

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

  // Thu hồi ảnh xem trước cũ nếu có
  if (tempAvatarUrl.value) {
    URL.revokeObjectURL(tempAvatarUrl.value)
    tempAvatarUrl.value = ''
  }

  // Tạo ảnh xem trước cục bộ ngay lập tức
  const previewUrl = URL.createObjectURL(file)
  tempAvatarUrl.value = previewUrl

  isUploadingAvatar.value = true
  try {
    const formData = new FormData()
    formData.append('avatar', file)
    
    const avatarRes = await api.post('/user/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    if (avatarRes.data.user) {
      updateUserData(avatarRes.data.user)
      updateUser(user.value)
      window.dispatchEvent(new Event('user-updated'))
      showToast('Cập nhật ảnh đại diện thành công!')
      // Không thu hồi tempAvatarUrl ngay để giữ giao diện xem trước mượt mà, tức thời
    }
  } catch (error) {
    console.error('Lỗi upload avatar:', error)
    showToast('Lỗi cập nhật ảnh đại diện!')
    // Nếu lỗi thì thu hồi và reset ảnh xem trước
    if (tempAvatarUrl.value) {
      URL.revokeObjectURL(tempAvatarUrl.value)
      tempAvatarUrl.value = ''
    }
  } finally {
    isUploadingAvatar.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

const profileForm = ref({})
const editing = ref(false)
const savingProfile = ref(false)

const loadUser = async () => {
  try {
    const token = getToken()

    if (!token) {
      const parsed = getUser()
      if (parsed) {
        user.value = {
          ...user.value,
          ...parsed,
          phone: parsed.phone || '',
          birthday: parsed.birthday || '',
          gender: parsed.gender || '',
          xu: parsed.xu || 0,
          memberSince: parsed.role === 'admin' ? 'Quản trị viên' : 'Thành viên',
          joinDate: parsed.created_at
            ? new Date(parsed.created_at).toLocaleDateString('vi-VN')
            : (parsed.joinDate || ''),
          avatar: parsed.avatar || user.value.avatar,
        }
      }
      return
    }
    const res = await api.get('/user/profile')

    updateUserData(res.data)

    updateUser(user.value)
  } catch (error) {
    console.error('Load user lỗi:', error)

    const parsed = getUser()
    if (parsed) {
      user.value = {
        ...user.value,
        ...parsed,
        phone: parsed.phone || '',
        birthday: parsed.birthday || '',
        gender: parsed.gender || '',
        memberSince: parsed.role === 'admin' ? 'Quản trị viên' : 'Thành viên',
        joinDate: parsed.created_at
          ? new Date(parsed.created_at).toLocaleDateString('vi-VN')
          : (parsed.joinDate || ''),
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
        if (order.trangthai === 'refund_pending') statusKey = 'refund_pending'
        if (order.trangthai === 'refund_pickup') statusKey = 'refund_pickup'
        if (order.trangthai === 'refund_delivering') statusKey = 'refund_delivering'
        if (order.trangthai === 'refund_received') statusKey = 'refund_received'
        if (order.trangthai === 'refunded') statusKey = 'refunded'
        if (order.trangthai === 'refund_rejected') statusKey = 'refund_rejected'
        if (order.trangthai === 'cancelled') statusKey = 'cancelled'

        return {
          id_dathang: order.id_dathang,
          id: `VT-2026-${String(order.id_dathang).padStart(3, '0')}`,
          date: new Date(order.created_at).toLocaleDateString('vi-VN'),
          status: statusKey,
          trangthai: order.trangthai,
          updated_at: order.updated_at,
          total: new Intl.NumberFormat('vi-VN').format(order.tongtien) + 'đ',
          tongtien: order.tongtien,
          giam_gia: order.giam_gia || 0,
          lydo: order.lydo,
          refund_proof: order.refund_proof,
          xu_dung: order.xu_dung || 0,
          items: (order.chi_tiets || []).map(item => {
            let fullName = item.bien_the?.san_pham ? item.bien_the.san_pham.tenSP : 'Sản phẩm'
            
            if (item.bien_the && item.bien_the.thuoc_tinh_json) {
              try {
                const thuocTinhs = typeof item.bien_the.thuoc_tinh_json === 'string'
                  ? JSON.parse(item.bien_the.thuoc_tinh_json)
                  : item.bien_the.thuoc_tinh_json

                if (Array.isArray(thuocTinhs) && thuocTinhs.length > 0) {
                  const colorAttr = thuocTinhs.find(t => t.ten_thuoctinh.toLowerCase().includes('màu') || t.ten_thuoctinh.toLowerCase().includes('color'))
                  const otherAttrs = thuocTinhs.filter(t => !t.ten_thuoctinh.toLowerCase().includes('màu') && !t.ten_thuoctinh.toLowerCase().includes('color')).map(t => t.giatri).join(' - ')
                  
                  if (colorAttr) {
                    fullName += ` - ${colorAttr.giatri}`
                  }
                  if (otherAttrs) {
                    fullName += ` (${otherAttrs})`
                  }
                } else if (item.bien_the.ten_bienthe) {
                  fullName += ` (${item.bien_the.ten_bienthe})`
                }
              } catch (e) {
                 if (item.bien_the.ten_bienthe) fullName += ` (${item.bien_the.ten_bienthe})`
              }
            } else if (item.bien_the && item.bien_the.ten_bienthe) {
              fullName += ` (${item.bien_the.ten_bienthe})`
            }

            return {
              id_bienthe: item.id_bienthe,
              is_reviewed: item.is_reviewed,
              is_refund: item.is_refund,
              name: fullName,
              qty: item.soluong,
              price: new Intl.NumberFormat('vi-VN').format(item.gia) + 'đ',
              img: productImageUrl(item.bien_the?.san_pham || item.bien_the?.sanPham || {}, item.bien_the, 'https://via.placeholder.com/200')
            }
          }),
          steps: [
            { label: 'Đặt hàng', date: new Date(order.created_at).toLocaleString('vi-VN'), done: true },
            { label: 'Xác nhận', date: null, done: statusKey !== 'pending' },
            { label: 'Đang giao', date: null, done: statusKey === 'shipping' || statusKey === 'done' || statusKey.startsWith('refund') },
            { label: 'Hoàn thành', date: null, done: statusKey === 'done' || statusKey.startsWith('refund') },
          ],
          refundSteps: statusKey.startsWith('refund') ? [
            { label: 'Yêu cầu hoàn trả', date: null, done: ['refund_pending', 'refund_pickup', 'refund_delivering', 'refund_received', 'refunded'].indexOf(statusKey) >= 0 },
            { label: 'Chờ lấy hàng hoàn', date: null, done: ['refund_pending', 'refund_pickup', 'refund_delivering', 'refund_received', 'refunded'].indexOf(statusKey) >= 1 },
            { label: 'Đang giao hoàn', date: null, done: ['refund_pending', 'refund_pickup', 'refund_delivering', 'refund_received', 'refunded'].indexOf(statusKey) >= 2 },
            { label: 'Đã nhận hoàn', date: null, done: ['refund_pending', 'refund_pickup', 'refund_delivering', 'refund_received', 'refunded'].indexOf(statusKey) >= 3 },
            { label: 'Đã hoàn tiền', date: null, done: ['refund_pending', 'refund_pickup', 'refund_delivering', 'refund_received', 'refunded'].indexOf(statusKey) >= 4 },
          ] : null
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

  const isConfirmed = await swal.confirm('Xác nhận hủy', 'Bạn có chắc chắn muốn hủy đơn hàng này?')
  if (!isConfirmed) return

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

// Refund state
const showRefundModal = ref(false)
const orderToRefund = ref(null)
const refundReason = ref('')
const refundProof = ref(null)
const refundProofUrl = ref(null)
const refundSelectedItems = ref([])

const handleProofUpload = (e) => {
  const file = e.target.files[0]
  if (!file) return
  if (file.size > 20 * 1024 * 1024) {
    showToast('Kích thước file không được vượt quá 20MB')
    return
  }
  refundProof.value = file
  if (refundProofUrl.value) URL.revokeObjectURL(refundProofUrl.value)
  refundProofUrl.value = URL.createObjectURL(file)
}

const openRefundModal = (order) => {
    orderToRefund.value = order
    refundReason.value = ''
    refundProof.value = null
    if (refundProofUrl.value) URL.revokeObjectURL(refundProofUrl.value)
    refundProofUrl.value = null
    refundSelectedItems.value = []
    showRefundModal.value = true
}

const confirmRefund = async () => {
    if (refundSelectedItems.value.length === 0) {
        showToast('Vui lòng chọn ít nhất một sản phẩm để hoàn trả.')
        return
    }
    if (!refundReason.value.trim()) {
        showToast('Vui lòng nhập lý do hoàn trả.')
        return
    }
    if (!refundProof.value) {
        showToast('Vui lòng tải lên ảnh/video bằng chứng.')
        return
    }

    isSubmitting.value = true
    try {
        const formData = new FormData()
        formData.append('lydo', refundReason.value)
        formData.append('proof', refundProof.value)
        refundSelectedItems.value.forEach(id => {
            formData.append('item_ids[]', id)
        })

        const res = await api.post(`/orders/${orderToRefund.value.id_dathang}/refund`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })

        if (res.data.success) {
            showToast('Đã gửi yêu cầu hoàn trả!')
            showRefundModal.value = false
            await fetchOrders()
            if (selectedOrder.value && selectedOrder.value.id_dathang === orderToRefund.value.id_dathang) {
                selectedOrder.value = null
            }
        }
    } catch (err) {
        showToast(err.response?.data?.message || 'Có lỗi xảy ra khi yêu cầu hoàn trả.')
    } finally {
        isSubmitting.value = false
    }
}

const isRefundable = (order) => {
    if (order.trangthai !== 'done') return false;
    const updated = new Date(order.updated_at).getTime();
    const now = new Date().getTime();
    const diffHours = (now - updated) / (1000 * 60 * 60);
    return diffHours <= 42;
}

const handleReorder = async (order) => {
  const isConfirmed = await swal.confirm('Xác nhận mua lại', 'Bạn có chắc chắn muốn mua lại đơn hàng này?')
  if (!isConfirmed) return

  try {
    const res = await api.post(`/orders/${order.id_dathang}/reorder`)

    if (res.data.success) {
      showToast(res.data.message)
      window.location.href = '/gio-hang'
    }
  } catch (err) {
    showToast('Lỗi khi mua lại sản phẩm.')
  }
}

const openReviewModal = (order, item) => {
  reviewForm.value = {
    id_dathang: order.id_dathang,
    id_bienthe: item.id_bienthe,
    productName: item.name,
    rating: 5,
    comment: ''
  }
  showReviewModal.value = true
}

const submitReview = async () => {
  if (reviewForm.value.rating < 1) {
    showToast('Vui lòng chọn số sao đánh giá.')
    return
  }

  isSubmittingReview.value = true
  try {
    const res = await api.post('/danh-gia', {
      id_dathang: reviewForm.value.id_dathang,
      id_bienthe: reviewForm.value.id_bienthe,
      danhgia: reviewForm.value.rating,
      binhluan: reviewForm.value.comment
    })

    if (res.data.success) {
      showToast('Cảm ơn bạn đã đánh giá sản phẩm! ❤️')
      showReviewModal.value = false
      
      // Cập nhật trạng thái item ngay lập tức trong UI
      if (selectedOrder.value) {
        const item = selectedOrder.value.items.find(i => i.id_bienthe === reviewForm.value.id_bienthe)
        if (item) item.is_reviewed = true
      }
      
      // Tải lại toàn bộ đơn hàng để cập nhật danh sách chính
      await fetchOrders()
    }
  } catch (err) {
    showToast(err.response?.data?.message || 'Có lỗi xảy ra khi gửi đánh giá.')
  } finally {
    isSubmittingReview.value = false
  }
}

const wishlistCount = ref(0)

const fetchWishlistCount = async () => {
  try {
    const res = await api.get('/yeu-thich')
    if (res.data && Array.isArray(res.data.data)) {
      wishlistCount.value = res.data.data.length
    } else if (res.data && Array.isArray(res.data)) {
      wishlistCount.value = res.data.length
    }
  } catch (error) {
    console.error('Lỗi tải danh sách yêu thích:', error)
  }
}

onMounted(() => {
  loadUser()
  fetchOrders()
  fetchWishlistCount()
  fetchPromotions()
  fetchAddresses()
  loadPwCaptcha()

  const userData = getUser()
  if (getToken() && userData && (userData.id || userData.id_user)) {
    const userId = userData.id || userData.id_user
    
    echo.private(`user.${userId}`)
      .listen('.order.status.updated', (e) => {
        const index = orders.value.findIndex(o => o.id_dathang === e.id_dathang)
        if (index !== -1) {
          orders.value[index].trangthai = e.trangthai
          // Map to frontend status keys if needed
          let statusKey = 'pending'
          if (e.trangthai === 'confirmed') statusKey = 'confirmed'
          if (e.trangthai === 'shipping') statusKey = 'shipping'
          if (e.trangthai === 'done' || e.trangthai === 'completed') statusKey = 'done'
          if (e.trangthai === 'refund_pending') statusKey = 'refund_pending'
          if (e.trangthai === 'refund_pickup') statusKey = 'refund_pickup'
          if (e.trangthai === 'refund_delivering') statusKey = 'refund_delivering'
          if (e.trangthai === 'refund_received') statusKey = 'refund_received'
          if (e.trangthai === 'refunded') statusKey = 'refunded'
          if (e.trangthai === 'refund_rejected') statusKey = 'refund_rejected'
          if (e.trangthai === 'cancelled') statusKey = 'cancelled'
          orders.value[index].status = statusKey

          if (selectedOrder.value && selectedOrder.value.id_dathang === e.id_dathang) {
            selectedOrder.value.trangthai = e.trangthai
            selectedOrder.value.status = statusKey
          }
        }
      })
  }
})

onUnmounted(() => {
  if (tempAvatarUrl.value) {
    URL.revokeObjectURL(tempAvatarUrl.value)
  }
  const userData = getUser()
  const userId = userData?.id || userData?.id_user
  if (userId) {
    echo.leave(`user.${userId}`)
  }
})

const showXuHistoryModal = ref(false)
const xuHistoryList = ref([])
const xuHistoryLoading = ref(false)
const xuHistoryPage = ref(1)
const xuHistoryTotalPages = ref(1)

const openXuHistoryModal = () => {
  showXuHistoryModal.value = true
  fetchXuHistory(1)
}

const fetchXuHistory = async (page) => {
  try {
    xuHistoryLoading.value = true
    const res = await api.get(`/xu/history?page=${page}`)
    if (res.data.success) {
      xuHistoryList.value = res.data.data.data || []
      xuHistoryPage.value = res.data.data.current_page || 1
      xuHistoryTotalPages.value = res.data.data.last_page || 1
    }
  } catch (error) {
    console.error('Lỗi khi lấy lịch sử xu:', error)
  } finally {
    xuHistoryLoading.value = false
  }
}

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
    updateUser(user.value)
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

const rewardPoints = computed(() => {
  // Tính tổng tiền từ các đơn hàng hoàn thành (done hoặc completed)
  const completedOrders = orders.value.filter(o => o.status === 'done' || o.trangthai === 'completed' || o.trangthai === 'done')
  const totalSpent = completedOrders.reduce((sum, o) => sum + (o.tongtien || 0), 0)

  // Quy đổi: 10.000đ = 1 điểm thưởng
  const points = Math.floor(totalSpent / 10000)

  return new Intl.NumberFormat('vi-VN').format(points)
})

const stats = computed(() => [
  { label: 'Đơn hàng', value: orders.value.length.toString(), icon: 'orders' },
  { label: 'Yêu thích', value: wishlistCount.value.toString(), icon: 'heart' },
  { label: 'Điểm thưởng', value: rewardPoints.value, icon: 'star' },
])

// ════════════════════════════════════════════════
//  TAB 2 — ORDERS
// ════════════════════════════════════════════════
const orderTab = ref('all')
const selectedOrder = ref(null)

const orderTabs_mua = [
  { key: 'all', label: 'Tất cả' },
  { key: 'pending', label: 'Chờ xác nhận' },
  { key: 'confirmed', label: 'Đã xác nhận' },
  { key: 'shipping', label: 'Đang giao' },
  { key: 'done', label: 'Hoàn thành' },
  { key: 'cancelled', label: 'Đã hủy' },
]

const orderTabs_hoantra = [
  { key: 'refund_pending', label: 'Yêu cầu hoàn trả' },
  { key: 'refund_pickup', label: 'Chờ lấy hàng hoàn' },
  { key: 'refund_delivering', label: 'Đang giao hoàn' },
  { key: 'refund_received', label: 'Đã nhận hoàn' },
  { key: 'refunded', label: 'Đã hoàn tiền' },
]

const statusMap = {
  pending: { label: 'Chờ xác nhận', color: '#f59e0b', bg: '#fef3c7' },
  confirmed: { label: 'Đã xác nhận', color: '#0369a1', bg: '#e0f2fe' },
  shipping: { label: 'Đang giao', color: '#2563eb', bg: '#dbeafe' },
  done: { label: 'Hoàn thành', color: '#16a34a', bg: '#dcfce7' },
  refund_pending: { label: 'Yêu cầu hoàn trả', color: '#f97316', bg: '#ffedd5' },
  refund_pickup: { label: 'Chờ lấy hàng hoàn', color: '#d97706', bg: '#fef3c7' },
  refund_delivering: { label: 'Đang giao hoàn', color: '#2563eb', bg: '#dbeafe' },
  refund_received: { label: 'Đã nhận hoàn', color: '#0369a1', bg: '#e0f2fe' },
  refunded: { label: 'Đã hoàn tiền', color: '#8b5cf6', bg: '#ede9fe' },
  refund_rejected: { label: 'Từ chối hoàn trả', color: '#dc2626', bg: '#fee2e2' },
  cancelled: { label: 'Đã hủy', color: '#dc2626', bg: '#fee2e2' },
}

const orders = ref([])

const orderMode = ref('mua')

const filteredOrders = computed(() => {
  if (orderMode.value === 'mua') {
    return orderTab.value === 'all'
      ? orders.value.filter((o) => !o.status.startsWith('refund'))
      : orders.value.filter((o) => o.status === orderTab.value)
  } else {
    return orderTab.value === 'all'
      ? orders.value.filter((o) => o.status.startsWith('refund'))
      : orders.value.filter((o) => o.status === orderTab.value)
  }
})

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
const loadingAddresses = ref(false)
const showMapPicker = ref(false)
const loadingProvinces = ref(false)
const loadingWards = ref(false)
const locatingSelectedArea = ref(false)
const selectedProvinceCode = ref('')
const selectedWardCode = ref('')
const provinces = ref([])
const wards = ref([])
const mapInitialPosition = ref(null)

const defaultAddrForm = () => ({
  id: null,
  province: '',
  district: '',
  ward: '',
  detail: '',
  fullAddress: '',
  latitude: null,
  longitude: null,
  type: 'home',
  isDefault: false,
})

const addrForm = ref(defaultAddrForm())

const addresses = ref([])

const addressApiBaseUrl = 'https://provinces.open-api.vn/api/v2'

const addressSuggestions = ref([])
const showSuggestions = ref(false)
const detailWarning = ref('')
const searchingDetail = ref(false)
let searchDetailTimeout = null
let currentSearchController = null
let searchRequestId = 0

const handleDetailInput = () => {
  showSuggestions.value = false
  detailWarning.value = ''
  
  if (searchDetailTimeout) clearTimeout(searchDetailTimeout)
  if (currentSearchController) {
    currentSearchController.abort()
  }
  
  const detailLength = addrForm.value.detail ? addrForm.value.detail.trim().length : 0;
  if (detailLength < 3) {
    addressSuggestions.value = []
    searchingDetail.value = false
    return
  }
  
  searchDetailTimeout = setTimeout(async () => {
    searchingDetail.value = true
    const controller = new AbortController()
    currentSearchController = controller
    searchRequestId++
    const currentReqId = searchRequestId
    
    try {
      const parts = [addrForm.value.detail, addrForm.value.ward, addrForm.value.district, addrForm.value.province]
        .filter(item => item && item !== 'Không xác định')
      const query = [...parts, 'Việt Nam'].join(', ')
      
      const data = await searchSuggestions(query, controller.signal, {
        province: addrForm.value.province !== 'Không xác định' ? addrForm.value.province : '',
        ward: addrForm.value.ward !== 'Không xác định' ? addrForm.value.ward : ''
      })
      
      if (controller.signal.aborted || currentReqId !== searchRequestId) return;
      
      let validResults = [];
      if (data && data.length > 0) {
        validResults = data.filter(item => (item.title || item.display_name || item.subtitle) && item.lat && item.lng);
      }
      
      if (validResults.length > 0) {
        addressSuggestions.value = validResults
        showSuggestions.value = true
        detailWarning.value = ''
      } else {
        addressSuggestions.value = []
        showSuggestions.value = false
        detailWarning.value = 'Không tìm thấy địa chỉ cụ thể, bản đồ sẽ ghim ở khu vực gần nhất.'
        
        // Cố gắng tìm vị trí fallback (Phường/Quận) và ghim bản đồ
        const fallbackRes = await geocodeWithFallback(
          addrForm.value.detail, 
          addrForm.value.ward, 
          addrForm.value.district, 
          addrForm.value.province
        )
        if (fallbackRes && fallbackRes.lat && fallbackRes.lng && currentReqId === searchRequestId) {
          addrForm.value.latitude = Number(fallbackRes.lat)
          addrForm.value.longitude = Number(fallbackRes.lng)
          mapInitialPosition.value = { lat: addrForm.value.latitude, lng: addrForm.value.longitude }
        }
      }
    } catch (error) {
      if (error.name !== 'CanceledError' && error.code !== 'ERR_CANCELED' && !controller.signal.aborted) {
        console.error('Lỗi tìm kiếm gợi ý:', error)
      }
      addressSuggestions.value = []
      showSuggestions.value = false
    } finally {
      if (currentSearchController === controller) {
        currentSearchController = null
        searchingDetail.value = false
      }
    }
  }, 900)
}

const selectSuggestion = (item) => {
  showSuggestions.value = false
  detailWarning.value = ''
  
  if (item.title || item.display_name) {
    addrForm.value.detail = item.title || item.display_name
  }

  const lat = Number(item.lat)
  const lng = Number(item.lng)
  mapInitialPosition.value = { lat, lng }
  addrForm.value.latitude = lat
  addrForm.value.longitude = lng
}

const normalizeApiList = (data, keys = []) => {
  if (Array.isArray(data)) return data

  for (const key of keys) {
    if (Array.isArray(data?.[key])) return data[key]
  }

  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data?.results)) return data.results
  return []
}

const fetchProvinces = async () => {
  if (provinces.value.length) return

  loadingProvinces.value = true
  try {
    provinces.value = await fetchAddressProvinces()
  } catch (error) {
    console.error('Lỗi tải tỉnh/thành:', error)
    showToast('Không thể tải danh sách tỉnh/thành.')
  } finally {
    loadingProvinces.value = false
  }
}

const fetchWardsByProvince = async (provinceCode) => {
  if (!provinceCode) {
    wards.value = []
    return
  }

  loadingWards.value = true
  try {
    wards.value = await fetchAddressWardsByProvince(provinceCode)
  } catch (error) {
    console.error('Lỗi tải phường/xã:', error)
    showToast('Không thể tải danh sách phường/xã.')
  } finally {
    loadingWards.value = false
  }
}

const normalizeAddressName = (name = '') => name
  .toString()
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .replace(/^(tinh|thanh pho|tp|quan|huyen|thi xa|phuong|xa|thi tran)\s+/i, '')
  .replace(/\s+/g, ' ')
  .trim()
  .toLowerCase()

const findAddressCodeByName = (items, name) => {
  const normalizedName = normalizeAddressName(name)
  if (!normalizedName) return ''

  return items.find((item) => {
    const itemName = normalizeAddressName(item.name)
    return itemName === normalizedName
      || itemName.includes(normalizedName)
      || normalizedName.includes(itemName)
  })?.code || ''
}

const findProvinceCodeByName = (name) => findAddressCodeByName(provinces.value, name)
const findWardCodeByName = (name) => findAddressCodeByName(wards.value, name)

const handleProvinceChange = async () => {
  const province = provinces.value.find((item) => String(item.code) === String(selectedProvinceCode.value))
  addrForm.value.province = province?.name || ''
  addrForm.value.district = ''
  addrForm.value.ward = ''
  addrForm.value.fullAddress = addrForm.value.province
  selectedWardCode.value = ''
  mapInitialPosition.value = null
  await fetchWardsByProvince(selectedProvinceCode.value)
  await prepareMapInitialPosition()
}

const handleWardChange = async () => {
  const ward = wards.value.find((item) => String(item.code) === String(selectedWardCode.value))
  addrForm.value.ward = ward?.name || ''
  addrForm.value.district = ward?.districtName || ''
  addrForm.value.fullAddress = [addrForm.value.province, addrForm.value.ward].filter(Boolean).join(', ')
  mapInitialPosition.value = null
  await prepareMapInitialPosition()
}

const prepareAddressSelectors = async (address = null) => {
  await fetchProvinces()

  selectedProvinceCode.value = address?.province ? findProvinceCodeByName(address.province) : ''
  if (!selectedProvinceCode.value) {
    selectedWardCode.value = ''
    wards.value = []
    return
  }

  await fetchWardsByProvince(selectedProvinceCode.value)
  selectedWardCode.value = address?.ward ? findWardCodeByName(address.ward) : ''
}

const geocodeSelectedArea = async () => {
  locatingSelectedArea.value = true
  try {
    const res = await geocodeWithFallback('', addrForm.value.ward, addrForm.value.district, addrForm.value.province)
    if (res && res.lat && res.lng) {
      return { 
        lat: Number(res.lat), 
        lng: Number(res.lng),
        geojson: res.geojson,
        boundingbox: res.boundingbox 
      }
    }
    return null
  } catch (error) {
    console.error('Lỗi tìm vị trí khu vực:', error)
    return null
  } finally {
    locatingSelectedArea.value = false
  }
}

const prepareMapInitialPosition = async () => {
  mapInitialPosition.value = await geocodeSelectedArea()
}

const openMapPicker = async () => {
  if (!addrForm.value.province) {
    showToast('Vui lòng chọn tỉnh/thành phố trước khi ghim vị trí.')
    return
  }

  mapInitialPosition.value = await geocodeSelectedArea()
  showMapPicker.value = true
}


const mapAddressFromApi = (addr) => ({
  id: addr.id_diachi,
  province: addr.tinh_thanhpho || '',
  district: addr.quan_huyen || '',
  ward: addr.phuong_xa || '',
  detail: addr.diachi_cuthe || '',
  fullAddress: [addr.phuong_xa, addr.quan_huyen, addr.tinh_thanhpho].filter((item) => item && item !== 'Không xác định').join(', '),
  latitude: addr.latitude ?? null,
  longitude: addr.longitude ?? null,
  type: addr.loai_diachi || 'home',
  isDefault: Boolean(addr.mac_dinh),
})

const mapAddressToApi = () => ({
  tinh_thanhpho: addrForm.value.province || addrForm.value.fullAddress || 'Không xác định',
  quan_huyen: addrForm.value.district || '',
  phuong_xa: addrForm.value.ward || addrForm.value.fullAddress || 'Không xác định',
  diachi_cuthe: addrForm.value.detail,
  latitude: addrForm.value.latitude,
  longitude: addrForm.value.longitude,
  loai_diachi: addrForm.value.type,
  mac_dinh: addrForm.value.isDefault,
})

const applyMapAddress = (address) => {
  const selectedProvince = provinces.value.find((item) => String(item.code) === String(selectedProvinceCode.value))
  const selectedWard = wards.value.find((item) => String(item.code) === String(selectedWardCode.value))

  addrForm.value.province = selectedProvince?.name || address.province || addrForm.value.province || ''
  addrForm.value.district = address.district || addrForm.value.district || ''
  addrForm.value.ward = selectedWard?.name || address.ward || addrForm.value.ward || ''
  addrForm.value.fullAddress = [
    addrForm.value.ward,
    addrForm.value.district,
    addrForm.value.province,
  ].filter((item) => item && item !== 'Không xác định').join(', ') || address.fullAddress || ''
  addrForm.value.latitude = address.latitude ?? addrForm.value.latitude
  addrForm.value.longitude = address.longitude ?? addrForm.value.longitude
  mapInitialPosition.value = address.latitude && address.longitude
    ? { lat: Number(address.latitude), lng: Number(address.longitude) }
    : mapInitialPosition.value

  if (!addrForm.value.detail && address.detail) {
    addrForm.value.detail = address.detail
  }
}

const fetchAddresses = async () => {
  loadingAddresses.value = true
  try {
    const res = await api.get('/user/dia-chi')
    addresses.value = (res.data.data || []).map(mapAddressFromApi)
  } catch (error) {
    console.error('Lỗi tải địa chỉ:', error)
    showToast(error.response?.data?.message || 'Không thể tải danh sách địa chỉ.')
  } finally {
    loadingAddresses.value = false
  }
}

const openAddAddr = async () => {
  addrForm.value = defaultAddrForm()
  editingAddrIdx.value = null
  showAddrForm.value = true
  await prepareAddressSelectors()
}

const openEditAddr = async (i) => {
  addrForm.value = { ...addresses.value[i] }
  editingAddrIdx.value = i
  mapInitialPosition.value = addrForm.value.latitude && addrForm.value.longitude
    ? { lat: Number(addrForm.value.latitude), lng: Number(addrForm.value.longitude) }
    : null
  showAddrForm.value = true
  await prepareAddressSelectors(addrForm.value)
  if (!mapInitialPosition.value) {
    await prepareMapInitialPosition()
  }
}

const cancelAddr = () => {
  showAddrForm.value = false
  addressSuggestions.value = []
  showSuggestions.value = false
  detailWarning.value = ''
}

const saveAddr = async () => {
  savingAddr.value = true
  try {
    if (editingAddrIdx.value !== null) {
      const id = addresses.value[editingAddrIdx.value].id
      await api.put(`/user/dia-chi/${id}`, mapAddressToApi())
    } else {
      await api.post('/user/dia-chi', mapAddressToApi())
    }

    await fetchAddresses()
    showAddrForm.value = false
    showToast('Địa chỉ đã được cập nhật!')
  } catch (error) {
    const message = error.response?.data?.message
      || Object.values(error.response?.data?.errors || {})?.[0]?.[0]
      || 'Thao tác thất bại, vui lòng thử lại'
    showToast(message)
  } finally {
    savingAddr.value = false
  }
}

const setDefaultAddr = (i) => {
  api.patch(`/user/dia-chi/${addresses.value[i].id}/mac-dinh`)
    .then(async () => {
      await fetchAddresses()
      showToast('Đã cập nhật địa chỉ mặc định!')
    })
    .catch((error) => {
      showToast(error.response?.data?.message || 'Thao tác thất bại, vui lòng thử lại')
    })
}

const removeAddr = (i) => {
  swal.confirm('Xóa địa chỉ', 'Bạn có chắc chắn muốn xóa địa chỉ này?')
    .then(async (isConfirmed) => {
      if (!isConfirmed) return
      try {
        await api.delete(`/user/dia-chi/${addresses.value[i].id}`)
        await fetchAddresses()
        showToast('Đã xóa địa chỉ!')
      } catch (error) {
        showToast(error.response?.data?.message || 'Thao tác thất bại, vui lòng thử lại')
      }
    })
}

// ════════════════════════════════════════════════
//  TAB 4 — PASSWORD
// ════════════════════════════════════════════════
const pwForm = ref({ current: '', newPass: '', confirm: '' })
const showPw = ref({ current: false, newPass: false, confirm: false })
const savingPw = ref(false)
const pwErrors = ref({})
const pwCaptcha = ref({ question: '', answer: '' })
const loadingPwCaptcha = ref(false)
const captchaVerified = ref(false)
const verifyingCaptcha = ref(false)

const solveCaptchaQuestion = (question) => {
  const expression = String(question || '').match(/(-?\d+)\s*([+\-xX*])\s*(-?\d+)/)
  if (!expression) return ''

  const left = Number(expression[1])
  const operator = expression[2]
  const right = Number(expression[3])

  if (operator === '+') return String(left + right)
  if (operator === '-') return String(left - right)
  return String(left * right)
}

const loadPwCaptcha = async () => {
  loadingPwCaptcha.value = true
  captchaVerified.value = false
  try {
    const res = await api.get('/user/change-password/captcha')
    pwCaptcha.value = {
      question: res.data?.question || '',
      answer: '',
    }
  } catch (error) {
    console.error('Lỗi tải captcha đổi mật khẩu:', error)
    pwCaptcha.value = { question: '', answer: '' }
  } finally {
    loadingPwCaptcha.value = false
  }
}

const toggleHumanCaptcha = async () => {
  pwErrors.value.captcha = ''

  if (captchaVerified.value) {
    captchaVerified.value = false
    pwCaptcha.value.answer = ''
    return
  }

  verifyingCaptcha.value = true
  try {
    if (!pwCaptcha.value.question) {
      await loadPwCaptcha()
    }

    const answer = solveCaptchaQuestion(pwCaptcha.value.question)
    if (!answer) {
      captchaVerified.value = false
      pwErrors.value.captcha = 'Không thể xác minh captcha, vui lòng thử lại'
      return
    }

    pwCaptcha.value.answer = answer
    captchaVerified.value = true
  } finally {
    verifyingCaptcha.value = false
  }
}

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

  if (!pwCaptcha.value.answer) {
    pwErrors.value.captcha = 'Vui lòng nhập captcha'
  }

  if (Object.keys(pwErrors.value).length) return

  savingPw.value = true
  try {
    const res = await api.put('/user/change-password', {
      current_password: pwForm.value.current,
      new_password: pwForm.value.newPass,
      new_password_confirmation: pwForm.value.confirm,
      captcha_answer: pwCaptcha.value.answer,
    })

    pwForm.value = { current: '', newPass: '', confirm: '' }
    await loadPwCaptcha()
    showToast(res.data?.message || 'Đổi mật khẩu thành công!')
  } catch (error) {
    const data = error.response?.data || {}

    if (error.response?.status === 422) {
      if (data.errors?.current_password?.[0]) {
        pwErrors.value.current = data.errors.current_password[0]
        await loadPwCaptcha()
      }

      if (data.errors?.new_password?.[0]) {
        pwErrors.value.newPass = data.errors.new_password[0]
      }

      if (data.errors?.captcha_answer?.[0]) {
        pwErrors.value.captcha = data.errors.captcha_answer[0]
        await loadPwCaptcha()
      }

      if (!Object.keys(pwErrors.value).length && data.message) {
        const message = data.message
        if (message.toLowerCase().includes('captcha')) {
          pwErrors.value.captcha = message
          await loadPwCaptcha()
        } else if (message.toLowerCase().includes('current') || message.includes('hiện tại')) {
          pwErrors.value.current = message
          await loadPwCaptcha()
        } else {
          pwErrors.value.newPass = message
        }
      }
      return
    }

    showToast(data.message || 'Có lỗi xảy ra khi đổi mật khẩu!')
  } finally {
    savingPw.value = false
  }
}

// ════════════════════════════════════════════════
//  TAB 5 — PROMOTIONS
// ════════════════════════════════════════════════
const promotions = ref([])
const promoPage = ref(1)
const promoPerPage = 5

const fetchPromotions = async () => {
  try {
    const res = await api.get('/user/vouchers')
    if (res.data.success) {
      promotions.value = res.data.vouchers || []
    } else {
      promotions.value = []
    }
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
  0: { label: 'Chưa sử dụng', color: '#16a34a', bg: '#dcfce7' },
  1: { label: 'Đã sử dụng',   color: '#94a3b8', bg: '#f1f5f9' },
  expired: { label: 'Hết hạn', color: '#dc2626', bg: '#fee2e2' },
}
</script>

<template>
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

            <div v-if="(selectedOrder.status === 'cancelled' || selectedOrder.status === 'refund_pending' || selectedOrder.status === 'refunded') && selectedOrder.lydo" class="alert mb-4" :class="{'alert-danger': selectedOrder.status === 'cancelled', 'alert-warning': selectedOrder.status !== 'cancelled'}" style="font-size: 13px; padding: 12px; border-radius: 10px;">
              <strong>Lý do:</strong> {{ selectedOrder.lydo }}
              <div v-if="selectedOrder.refund_proof" class="mt-2">
                <strong>Bằng chứng:</strong> <a :href="storageUrl(selectedOrder.refund_proof)" target="_blank">Xem file đính kèm</a>
              </div>
            </div>

            <div class="timeline" v-if="selectedOrder.steps && !selectedOrder.status.startsWith('refund')">
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

            <div class="refund-timeline-wrap" v-if="selectedOrder.refundSteps" style="margin-top: 15px;">
              <h3 class="section-title" style="color: #f97316;">Quá trình hoàn trả</h3>
              <div class="timeline refund-timeline">
                <div class="tl-item" v-for="(step, i) in selectedOrder.refundSteps" :key="'r'+i" :class="{ done: step.done }">
                  <div class="tl-col">
                    <div class="tl-dot refund-dot" :style="step.done ? 'background:#f97316; border-color:#f97316;' : ''"><svg v-if="step.done" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <div class="tl-line refund-line" v-if="i < selectedOrder.refundSteps.length - 1" :style="step.done ? 'background:#f97316;' : ''"></div>
                  </div>
                  <div class="tl-content">
                    <p class="tl-label refund-label">{{ step.label }}</p>
                    <p class="tl-date">{{ step.date || '—' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <h3 class="section-title">Sản phẩm</h3>
            <div class="modal-item" v-for="item in selectedOrder.items.filter(i => !selectedOrder.status.startsWith('refund') || i.is_refund == 1)" :key="item.id_bienthe">
              <img :src="item.img" :alt="item.name" />
              <div class="modal-item-info">
                <p class="modal-item-name">
                  {{ item.name }}
                  <span v-if="item.is_refund == 1" style="margin-left: 6px; font-size: 10px; font-weight: bold; color: #dc2626; background: #fee2e2; padding: 2px 5px; border-radius: 4px;">Đã hoàn trả</span>
                </p>
                <p class="modal-item-qty">Số lượng: {{ item.qty }}</p>
              </div>
              <div class="modal-item-right" style="text-align: right;">
                <p class="modal-item-price">{{ item.price }}</p>
                <button v-if="selectedOrder.status === 'done' && !item.is_reviewed" 
                  class="btn-review-small" @click="openReviewModal(selectedOrder, item)">Đánh giá</button>
                <span v-else-if="item.is_reviewed" class="reviewed-tag">Đã đánh giá</span>
              </div>
            </div>
            <div class="modal-footer">
              <div class="modal-btns">
                <button v-if="['pending', 'confirmed'].includes(selectedOrder.status)"
                  class="btn-modal-huy" @click="openCancelModal(selectedOrder)">Hủy đơn</button>
                <button v-if="isRefundable(selectedOrder)"
                  class="btn-modal-hoantra" @click="openRefundModal(selectedOrder)">Hoàn trả</button>
                <button v-if="['done', 'cancelled', 'refunded', 'refund_rejected'].includes(selectedOrder.status)"
                  class="btn-modal-mua" @click="handleReorder(selectedOrder)">Mua lại</button>
              </div>
              
              <div class="modal-total-wrap" style="width: 100%; display: flex; flex-direction: column; gap: 6px; align-items: flex-end;">
                <div class="modal-breakdown" style="border-top: 1px dashed rgba(255,255,255,0.1); padding-top:10px; width: 100%; font-size: 13px; color: #94a3b8;" v-if="selectedOrder.xu_dung > 0 || selectedOrder.giam_gia > 0">
                  <div class="d-flex justify-content-between mb-1" v-if="selectedOrder.giam_gia > 0" style="display: flex; justify-content: space-between; width: 100%;">
                    <span>Giảm giá voucher:</span>
                    <span style="color:#ef4444;">-{{ new Intl.NumberFormat('vi-VN').format(selectedOrder.giam_gia) }}đ</span>
                  </div>
                  <div class="d-flex justify-content-between" v-if="selectedOrder.xu_dung > 0" style="display: flex; justify-content: space-between; width: 100%;">
                    <span>Sử dụng xu:</span>
                    <span style="color:#f59e0b;">-{{ selectedOrder.xu_dung.toLocaleString('vi-VN') }} xu (-{{ new Intl.NumberFormat('vi-VN').format(selectedOrder.xu_dung) }}đ)</span>
                  </div>
                </div>
                <div style="display:flex; justify-content:space-between; width: 100%; font-weight: bold; border-top: 1px solid rgba(255,255,255,0.1); padding-top:8px;">
                  <span class="total-label">Thành tiền</span>
                  <span class="total-value" style="font-size: 18px; color: #2563eb;">{{ selectedOrder.total }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Cancellation Modal -->
    <transition name="fade">
      <div class="overlay" v-if="showCancelModal" @click.self="showCancelModal = false" style="z-index: 9005;">
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

    <!-- Xu History Modal -->
    <transition name="fade">
      <div class="overlay" v-if="showXuHistoryModal" @click.self="showXuHistoryModal = false" style="z-index: 9005;">
        <div class="modal" style="max-width: 550px;">
          <div class="modal-head">
            <div>
              <h2 class="modal-title">Lịch sử giao dịch Xu</h2>
              <p class="modal-id" style="color: #64748b; font-size: 12px; margin-top: 2px;">Theo dõi các giao dịch sử dụng và hoàn trả xu của bạn</p>
            </div>
            <button class="close-btn" @click="showXuHistoryModal = false">
              <svg viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="modal-body" style="padding-top: 10px;">
            <div v-if="xuHistoryLoading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"></div>
              <p class="mt-2 text-muted" style="font-size: 13px;">Đang tải lịch sử giao dịch...</p>
            </div>
            <div v-else-if="xuHistoryList.length === 0" class="text-center py-4" style="color: #64748b;">
              <p style="font-size: 14px; margin: 0;">Bạn chưa có giao dịch xài xu nào.</p>
            </div>
            <div v-else>
              <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                <table class="table table-hover" style="font-size: 13px; margin: 0;">
                  <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0;">
                      <th style="font-weight: 600; color: #475569; padding: 10px 8px;">Thời gian</th>
                      <th style="font-weight: 600; color: #475569; padding: 10px 8px; text-align: right;">Số xu</th>
                      <th style="font-weight: 600; color: #475569; padding: 10px 8px;">Chi tiết giao dịch</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in xuHistoryList" :key="item.id_lichsu" style="border-bottom: 1px solid #f1f5f9; align-items: center;">
                      <td style="padding: 10px 8px; color: #64748b;">
                        {{ new Date(item.created_at).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' }) }}
                      </td>
                      <td style="padding: 10px 8px; text-align: right; font-weight: 600; white-space: nowrap;" :style="item.so_xu > 0 ? 'color: #10b981;' : 'color: #ef4444;'">
                        {{ item.so_xu > 0 ? '+' : '' }}{{ item.so_xu.toLocaleString('vi-VN') }}
                      </td>
                      <td style="padding: 10px 8px; color: #1e293b; font-weight: 500;">
                        {{ item.mo_ta }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <!-- Pagination -->
              <div class="d-flex justify-content-between align-items-center mt-3 pt-2" style="border-top: 1px solid #e2e8f0; font-size: 12px;" v-if="xuHistoryTotalPages > 1">
                <span class="text-muted">Trang {{ xuHistoryPage }}/{{ xuHistoryTotalPages }}</span>
                <div class="d-flex gap-1">
                  <button class="btn btn-sm btn-outline-secondary py-1 px-2" :disabled="xuHistoryPage === 1" @click="fetchXuHistory(xuHistoryPage - 1)">
                    ‹ Trước
                  </button>
                  <button class="btn btn-sm btn-outline-secondary py-1 px-2" :disabled="xuHistoryPage === xuHistoryTotalPages" @click="fetchXuHistory(xuHistoryPage + 1)">
                    Sau ›
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Refund Modal -->
    <transition name="fade">
      <div class="overlay" v-if="showRefundModal" @click.self="showRefundModal = false" style="z-index: 9005;">
        <div class="modal mini-modal">
          <div class="modal-head">
            <h2 class="modal-title">Yêu cầu hoàn trả</h2>
            <button class="close-btn" @click="showRefundModal = false">
              <svg viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 600;">Chọn sản phẩm hoàn trả</label>
                <div class="refund-items-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px;">
                    <div v-for="item in (orderToRefund?.items || [])" :key="item.id_bienthe" class="d-flex align-items-center gap-2 mb-2 pb-2" style="border-bottom: 1px solid #f1f5f9;">
                        <label :for="'refund_item_' + item.id_bienthe" class="d-flex align-items-center gap-2 m-0" style="cursor: pointer; flex: 1; justify-content: space-between;">
                            <div class="d-flex align-items-center gap-2">
                                <img :src="item.img" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">{{ item.name }}</div>
                                    <div style="font-size: 11px; color: #64748b;">SL: {{ item.qty }}</div>
                                </div>
                            </div>
                            <input type="checkbox" :id="'refund_item_' + item.id_bienthe" :value="item.id_bienthe" v-model="refundSelectedItems" style="width: 16px; height: 16px; cursor: pointer;">
                        </label>
                    </div>
                </div>
            </div>
            <p class="mb-3 text-muted" style="font-size: 13px;">Vui lòng nhập lý do và đính kèm bằng chứng.</p>
            <textarea v-model="refundReason" class="form-control cancel-textarea mb-3" placeholder="Nhập lý do hoàn trả tại đây..." rows="3"></textarea>
            
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px;">Hình ảnh / Video bằng chứng</label>
                <input type="file" @change="handleProofUpload" class="form-control" accept="image/*,video/*" />
                <small class="text-muted d-block mt-1" style="font-size: 11px;">Hỗ trợ ảnh hoặc video (tối đa 20MB)</small>
                
                <div v-if="refundProofUrl" class="mt-3" style="text-align: center;">
                    <img v-if="refundProof && refundProof.type.startsWith('image/')" :src="refundProofUrl" alt="Bằng chứng" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" />
                    <video v-else-if="refundProof && refundProof.type.startsWith('video/')" :src="refundProofUrl" controls style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></video>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; gap: 12px; margin-top: 24px;">
              <button class="btn-warning-confirm" @click="confirmRefund" :disabled="isSubmitting" style="flex: 1; padding: 10px 16px; background: #f97316; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                {{ isSubmitting ? 'Đang gửi...' : 'Gửi yêu cầu' }}
              </button>
              <button class="btn-cancel" @click="showRefundModal = false">Quay lại</button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Review Modal -->
    <transition name="fade">
      <div class="overlay" v-if="showReviewModal" @click.self="showReviewModal = false" style="z-index: 9010;">
        <div class="modal review-modal">
          <div class="modal-head">
            <h2 class="modal-title">Đánh giá sản phẩm</h2>
            <button class="close-btn" @click="showReviewModal = false">
              <svg viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <div class="review-product-info">
              <p class="review-product-name">{{ reviewForm.productName }}</p>
            </div>

            <div class="rating-selector">
              <span class="rating-label">Chất lượng sản phẩm</span>
              <div class="stars-input">
                <button 
                  v-for="i in 5" 
                  :key="i" 
                  class="star-btn" 
                  :class="{ filled: i <= (hoverRating || reviewForm.rating) }"
                  @mouseenter="hoverRating = i"
                  @mouseleave="hoverRating = 0"
                  @click="reviewForm.rating = i"
                  type="button"
                >
                  <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                  </svg>
                </button>
                <span class="rating-text" v-if="reviewForm.rating">
                  {{ ['Tệ', 'Không hài lòng', 'Bình thường', 'Hài lòng', 'Tuyệt vời'][reviewForm.rating - 1] }}
                </span>
              </div>
            </div>

            <div class="form-group mb-0">
              <label>Bình luận</label>
              <textarea 
                v-model="reviewForm.comment" 
                class="form-control" 
                placeholder="Hãy chia sẻ trải nghiệm của bạn về sản phẩm nhé..." 
                rows="4"
              ></textarea>
            </div>

            <div class="modal-footer pt-4" style="border:none; padding-bottom:0;">
              <button class="btn-save w-100" @click="submitReview" :disabled="isSubmittingReview">
                {{ isSubmittingReview ? 'Đang gửi...' : 'Gửi đánh giá' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div class="overlay" v-if="showAddrForm" @click.self="cancelAddr" style="z-index: 9015;">
        <div class="modal address-modal">
          <div class="modal-head">
            <h2 class="modal-title">{{ editingAddrIdx !== null ? 'Chỉnh sửa địa chỉ' : 'Thêm địa chỉ mới' }}</h2>
            <button class="close-btn" @click="cancelAddr">
              <svg viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveAddr" class="address-modal-form">
              <div class="form-group form-full">
                <div class="region-picker-row">
                  <div class="region-picker-field">
                    <label>Tỉnh/Thành phố</label>
                    <select v-model="selectedProvinceCode" :disabled="loadingProvinces" required @change="handleProvinceChange">
                      <option value="" disabled>{{ loadingProvinces ? 'Đang tải tỉnh/thành...' : 'Chọn tỉnh/thành phố' }}</option>
                      <option v-for="province in provinces" :key="province.code" :value="province.code">{{ province.name }}</option>
                    </select>
                  </div>
                  <div class="region-picker-field">
                    <label>Phường/Xã</label>
                    <select v-model="selectedWardCode" :disabled="!selectedProvinceCode || loadingWards" required @change="handleWardChange">
                      <option value="" disabled>{{ loadingWards ? 'Đang tải phường/xã...' : 'Chọn phường/xã' }}</option>
                      <option v-for="ward in wards" :key="ward.code" :value="ward.code">{{ ward.name }}</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="form-group form-full" style="position: relative;">
                <label>Địa chỉ chi tiết</label>
                <input v-model="addrForm.detail" @input="handleDetailInput" type="text" placeholder="Số nhà, tên đường..." required autocomplete="off" />
                <small v-if="searchingDetail" style="color: #64748b; margin-top: 4px; display: block;">Đang tìm kiếm gợi ý...</small>
                <small v-if="detailWarning" style="color: #dc2626; margin-top: 4px; display: block;">{{ detailWarning }}</small>
                
                <div v-if="showSuggestions && addressSuggestions.length > 0" class="suggestions-dropdown" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 1050; max-height: 250px; overflow-y: auto; margin-top: 4px;">
                  <div v-for="(item, idx) in addressSuggestions" :key="idx" @click="selectSuggestion(item)" style="padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <strong style="font-size: 13px; color: #334155; display: block; margin-bottom: 2px;">{{ item.title || item.display_name || item.subtitle }}</strong>
                    <span style="font-size: 11px; color: #64748b; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ item.subtitle || item.display_name }}</span>
                  </div>
                </div>
              </div>
              <div class="form-group form-full">
                <label>Vị trí giao hàng</label>
                <div class="inline-map-field">
                  <AddressMapPicker inline :initial-position="mapInitialPosition" @selected="applyMapAddress" @open="openMapPicker" />
                  <small v-if="locatingSelectedArea">Đang tìm vị trí khu vực...</small>
                  <small v-else-if="addrForm.fullAddress">{{ addrForm.fullAddress }}</small>
                </div>
              </div>
              <div class="form-group"><label>Loại địa chỉ</label><select v-model="addrForm.type" required><option value="home">Nhà riêng</option><option value="company">Công ty</option></select></div>
              <div class="form-group form-full">
                <label class="checkbox-label"><input type="checkbox" v-model="addrForm.isDefault" /><span>Đặt làm địa chỉ mặc định</span></label>
              </div>
              <div class="form-actions form-full address-modal-actions">
                <button type="button" class="btn-cancel" @click="cancelAddr">Hủy</button>
                <button type="submit" class="btn-save" :disabled="savingAddr">
                  <svg v-if="savingAddr" class="spin" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                  {{ savingAddr ? 'Đang lưu...' : 'Lưu địa chỉ' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>

    <AddressMapPicker v-model="showMapPicker" :initial-position="mapInitialPosition" @selected="applyMapAddress" />

    <div class="container">

      <!-- ── SIDEBAR ── -->
      <aside class="sidebar">
        <!-- Input ẩn cho avatar upload -->
        <input 
          type="file" 
          ref="fileInput" 
          class="d-none" 
          style="display:none"
          accept="image/jpeg, image/png"
          @change="handleAvatarUpload" 
        />
        <div class="avatar-section">
          <div class="avatar-sidebar-container">
            <div class="avatar-circle" @click="triggerAvatarUpload" style="cursor:pointer; position:relative; overflow: hidden;" title="Nhấn để thay đổi ảnh đại diện">
              <img :src="formAvatarUrl" :alt="user.name" class="profile-avatar" />
              <div v-if="isUploadingAvatar" class="avatar-hover-overlay" style="position:absolute; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; color:#fff;">
                <svg class="spin" viewBox="0 0 24 24" fill="none" style="width:24px;height:24px;animation: spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              </div>
              <div v-else class="avatar-hover-overlay" style="position:absolute; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s; color:#fff;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                <i class="fas fa-camera"></i> <span style="font-size: 12px; font-weight: 600; margin-left: 4px;">Đổi ảnh</span>
              </div>
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
            <div class="info-row"><span class="info-lbl">Họ và tên</span><span class="info-val" :class="{ 'not-set': !user.name }">{{ user.name || 'Chưa cập nhật' }}</span></div>
            <div class="info-row">
              <span class="info-lbl">🪙 Xu tích lũy</span>
              <span class="info-val" style="display: flex; align-items: center; gap: 12px; margin-top: 6px;">
                <b style="color:#eab308; font-size:18px; font-weight: 700;">{{ (user.xu || 0).toLocaleString('vi-VN') }} Xu</b>
                <button type="button" class="btn-xem-lich-su-xu" @click="openXuHistoryModal" style="font-size: 11px; color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; padding: 4px 12px; cursor: pointer; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(37,99,235,0.05);">
                  <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Lịch sử
                </button>
              </span>
            </div>
            <div class="info-row"><span class="info-lbl">Email</span><span class="info-val" :class="{ 'not-set': !user.email }">{{ user.email || 'Chưa cập nhật' }}</span></div>
            <div class="info-row"><span class="info-lbl">Số điện thoại</span><span class="info-val" :class="{ 'not-set': !user.phone }">{{ user.phone || 'Chưa cập nhật' }}</span></div>
            <div class="info-row"><span class="info-lbl">Ngày sinh</span><span class="info-val" :class="{ 'not-set': !user.birthday }">{{ user.birthday || 'Chưa cập nhật' }}</span></div>
            <div class="info-row">
              <span class="info-lbl">Giới tính</span>
              <span class="info-val" :class="{ 'not-set': !user.gender }">
                {{ user.gender ? (['male', 'Nam'].includes(user.gender) ? 'Nam' : ['female', 'Nữ'].includes(user.gender) ? 'Nữ' : 'Khác') : 'Chưa cập nhật' }}
              </span>
            </div>
          </div>
          <form v-else class="edit-form" @submit.prevent="saveProfile">
            <div class="form-avatar-section">
              <div class="form-avatar-dashed-border" @click="triggerAvatarUpload">
                <div class="form-avatar-circle" style="position: relative; overflow: hidden;">
                  <img :src="formAvatarUrl" :alt="user.name" class="form-avatar-img" />
                  <div v-if="isUploadingAvatar" class="avatar-hover-overlay" style="position:absolute; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; color:#fff;">
                    <svg class="spin" viewBox="0 0 24 24" fill="none" style="width:24px;height:24px;animation: spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </div>
                  <div v-else class="form-avatar-plus-overlay">
                    <i class="fas fa-plus"></i>
                  </div>
                </div>
              </div>
              <p class="form-avatar-upload-text">Tải ảnh lên</p>
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
          <div class="page-header-inline" style="padding-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,0.07); margin-bottom: 24px;">
            <h1 class="card-title" style="font-size: 26px; color: #e2e8f0;">Lịch Sử Đơn Hàng</h1>
          </div>
          
          <div class="category-tabs" style="margin-bottom: 20px;">
            <button :class="['cat-tab', { active: orderMode === 'mua' }]" @click="orderMode = 'mua'; orderTab = 'all'" style="position: relative;">
              Đơn mua hàng
              <span class="badge-cart-like">{{ orders.filter(o => !o.status.startsWith('refund')).length }}</span>
            </button>
            <button :class="['cat-tab', { active: orderMode === 'hoantra' }]" @click="orderMode = 'hoantra'; orderTab = 'all'" style="position: relative;">
              Đơn hoàn trả
              <span class="badge-cart-like">{{ orders.filter(o => o.status.startsWith('refund')).length }}</span>
            </button>
          </div>

          <div class="tabs-group-wrapper" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px;">
            <div class="order-tabs" style="margin-bottom: 0;">
              <button v-for="t in (orderMode === 'mua' ? orderTabs_mua : [{key: 'all', label: 'Tất cả'}, ...orderTabs_hoantra])" :key="t.key" class="order-tab" :class="{ active: orderTab === t.key }" @click="orderTab = t.key">
                {{ t.label }}
                <span class="otab-count" v-if="t.key !== 'all'">{{ orders.filter(o => o.status === t.key).length }}</span>
              </button>
            </div>
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
                    <div class="empty-state-container">
                      <div class="empty-icon-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="empty-icon-custom">
                          <rect x="2" y="3" width="20" height="14" rx="2"/>
                          <path d="M8 21h8M12 17v4"/>
                        </svg>
                      </div>
                      <h3 class="empty-state-title">Bạn chưa có đơn hàng nào</h3>
                      <p class="empty-state-desc">Hãy khám phá các sản phẩm laptop cao cấp tại NextGen Group</p>
                      <router-link to="/" class="btn-shop-now">Tiếp tục mua sắm</router-link>
                    </div>
                  </td>
                </tr>
                <tr v-for="order in paginatedOrders" :key="order.id" class="order-row">
                  <td class="id-col"><span class="order-id">#VT-2026-{{ String(order.id_dathang).padStart(3, '0') }}</span></td>
                  <td>{{ order.date }}</td>
                  <td>
                    <div style="font-weight: 600;">{{ order.total }}</div>
                    <div v-if="order.xu_dung > 0" style="font-size: 11px; color: #f59e0b; margin-top: 2px; white-space: nowrap;">
                      🪙 Đã dùng: -{{ order.xu_dung.toLocaleString('vi-VN') }} xu
                    </div>
                  </td>
                  <td>
                    <span class="status-cell" :style="{ color: statusMap[order.status].color }">
                      {{ statusMap[order.status].label }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <button class="btn-xem" @click="selectedOrder = order">Xem</button>
                      <button v-if="['done', 'cancelled', 'refunded', 'refund_rejected'].includes(order.status)" class="btn-mua-lai" @click="handleReorder(order)">Mua lại</button>
                      <button v-if="['pending', 'confirmed'].includes(order.status)" class="btn-huy-don" @click="openCancelModal(order)">Hủy đơn</button>
                      <button v-if="isRefundable(order)" class="btn-hoan-tra" @click="openRefundModal(order)">Hoàn trả</button>
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
            <button class="btn-add" @click="openAddAddr">
              <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14"/></svg>
              Thêm địa chỉ
            </button>
          </div>
          <div class="addr-list">
            <div v-if="loadingAddresses" class="empty">
              <p>Đang tải địa chỉ...</p>
            </div>
            <div v-else-if="addresses.length === 0" class="empty">
              <svg viewBox="0 0 24 24" fill="none"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <p>Chưa có địa chỉ nào</p>
            </div>
            <div class="addr-card" v-for="(addr, i) in addresses" :key="addr.id" :class="{ 'is-default': addr.isDefault }">
              <div class="addr-head">
                <div class="addr-name-wrap" style="flex: 1; margin-right: 12px;"><span class="addr-name" style="line-height: 1.4; word-break: break-word;">{{ [addr.detail, addr.ward, addr.district, addr.province].filter(v => v && v !== 'Không xác định').join(', ') }}</span></div>
                <span class="default-badge" v-if="addr.isDefault">Mặc định</span>
              </div>
              <p class="addr-full" style="color: #64748b; font-weight: 500; margin-top: 4px;">{{ addr.type === 'company' ? 'Công ty' : 'Nhà riêng' }}</p>
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
          <div class="page-header-inline" style="padding-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,0.07); margin-bottom: 24px;">
            <h1 class="card-title" style="font-size: 26px; color: #e2e8f0;">Khuyến Mãi</h1>
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
                <tr v-for="item in paginatedPromos" :key="item.id" class="order-row">
                  <td><span style="font-weight:600; color: #e2e8f0;">{{ item.promotion?.name }}</span></td>
                  <td><span class="promo-code-badge">{{ item.promotion?.code }}</span></td>
                  <td style="color: #64748b; font-size:13px;">
                    {{ item.promotion?.type === 'percent' ? 'Phần trăm' : 'Cố định' }}
                  </td>
                  <td style="font-weight:700; color:#2563eb;">
                    {{ item.promotion?.type === 'percent'
                      ? item.promotion?.value + '%'
                      : new Intl.NumberFormat('vi-VN').format(item.promotion?.value) + 'đ' }}
                  </td>
                  <td style="font-size:13px; color: #64748b;">
                    <span v-if="item.promotion?.end_date">{{ new Date(item.promotion?.end_date).toLocaleDateString('vi-VN') }}</span>
                    <span v-else>Không giới hạn</span>
                  </td>
                  <td>
                    <span v-if="item.promotion?.end_date && new Date(item.promotion?.end_date) < new Date()" :style="{
                      color: promoStatusMap.expired.color,
                      background: promoStatusMap.expired.bg,
                      padding: '4px 12px',
                      borderRadius: '99px',
                      fontSize: '12px',
                      fontWeight: '700',
                      display: 'inline-block'
                    }">
                      {{ promoStatusMap.expired.label }}
                    </span>
                    <span v-else :style="{
                      color: (promoStatusMap[item.trang_thai] || promoStatusMap[1]).color,
                      background: (promoStatusMap[item.trang_thai] || promoStatusMap[1]).bg,
                      padding: '4px 12px',
                      borderRadius: '99px',
                      fontSize: '12px',
                      fontWeight: '700',
                      display: 'inline-block'
                    }">
                      {{ (promoStatusMap[item.trang_thai] || promoStatusMap[1]).label }}
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
                <div class="form-group" :class="{ error: pwErrors.captcha }">
                  <label>Xác minh</label>
                  <div class="turnstile-box" :class="{ checked: captchaVerified, loading: loadingPwCaptcha || verifyingCaptcha }">
                    <button
                      type="button"
                      class="turnstile-check"
                      :aria-pressed="captchaVerified"
                      :disabled="loadingPwCaptcha || verifyingCaptcha"
                      @click="toggleHumanCaptcha"
                    >
                      <svg v-if="captchaVerified" viewBox="0 0 24 24" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
                      <span v-else-if="loadingPwCaptcha || verifyingCaptcha" class="turnstile-spinner"></span>
                    </button>
                    <span class="turnstile-text">Xác minh bạn là con người</span>
                    <div class="turnstile-brand">
                      <div class="cloudflare-mark">
                        <span></span>
                      </div>
                      <strong>CLOUDFLARE</strong>
                      <small>Quyền riêng tư · Điều khoản</small>
                      <button type="button" class="turnstile-refresh" title="Tải lại xác minh" @click="loadPwCaptcha">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 0 1-9 9 9 9 0 0 1-8.49-6"/><path d="M3 12a9 9 0 0 1 15.49-6"/><path d="M21 3v6h-6"/><path d="M3 21v-6h6"/></svg>
                      </button>
                    </div>
                  </div>
                  <span class="err-msg" v-if="pwErrors.captcha">{{ pwErrors.captcha }}</span>
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
</template>

<style scoped>

/* ── BASE ── */
.page {
  min-height: 100vh;
  background: radial-gradient(circle at 10% 20%, #0c192c 0%, #050b15 100%);
  padding: 30px 24px;
  font-family: 'Inter', system-ui, sans-serif;
}
.container {
  max-width: 1080px;
  margin: auto;
  display: grid;
  grid-template-columns: 250px minmax(0, 1fr);
  gap: 28px;
  align-items: start;
}

/* ── SIDEBAR ── */
.sidebar {
  background: rgba(17, 31, 53, 0.6);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-radius: 20px;
  border: 1px solid rgba(56, 189, 248, 0.12);
  overflow: hidden;
  position: sticky;
  top: 20px;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.25);
}
.avatar-section {
  padding: 26px 20px 20px;
  text-align: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.avatar-sidebar-container {
  width: 84px;
  height: 84px;
  margin: 0 auto 14px;
}
.avatar-circle {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  overflow: hidden;
  position: relative;
  border: 3px solid rgba(34, 211, 238, 0.8);
  box-shadow: 0 0 20px rgba(34, 211, 238, 0.35);
  transition: transform 0.3s ease;
}
.avatar-circle:hover {
  transform: scale(1.03);
}
.profile-avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.avatar-hover-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.25s ease;
  color: #ffffff;
}
.avatar-hover-overlay i {
  font-size: 16px;
  margin-bottom: 4px;
}
.sidebar-name {
  font-size: 17px;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 8px;
  letter-spacing: -0.2px;
}
.sidebar-badge {
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  color: #22d3ee;
  background: linear-gradient(135deg, rgba(6, 182, 212, 0.15), rgba(37, 99, 235, 0.15));
  border: 1px solid rgba(6, 182, 212, 0.3);
  padding: 3px 12px;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.sidebar-join {
  font-size: 12px;
  color: #64748b;
  margin: 8px 0 0;
}

/* ── STAT GRID ── */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 7px;
  padding: 14px;
  background: transparent;
  border: none;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.stat-card {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 12px;
  padding: 10px 5px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  transition: all 0.25s ease;
  cursor: pointer;
}
.stat-card:hover {
  background: rgba(56, 189, 248, 0.08);
  border-color: rgba(56, 189, 248, 0.25);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.stat-card svg {
  width: 16px;
  height: 16px;
  stroke: #38bdf8;
  stroke-width: 2;
  fill: none;
  transition: all 0.25s ease;
}
.stat-card:hover svg {
  stroke: #22d3ee;
  filter: drop-shadow(0 0 4px rgba(34, 211, 238, 0.5));
}
.stat-val {
  font-size: 14px;
  font-weight: 700;
  color: #ffffff;
}
.stat-lbl {
  font-size: 10px;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* ── SIDEBAR NAV BUTTONS ── */
.side-nav {
  padding: 10px 12px 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.side-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 13px;
  border-radius: 12px;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  color: #94a3b8;
  font-size: 13px;
  font-weight: 600;
  text-align: left;
  transition: all 0.2s ease;
}
.side-btn svg:not(.arrow) {
  width: 17px;
  height: 17px;
  stroke: #94a3b8;
  stroke-width: 2;
  fill: none;
  flex-shrink: 0;
  transition: all 0.2s ease;
}
.side-btn span {
  flex: 1;
}
.side-btn .arrow {
  width: 14px;
  height: 14px;
  stroke: #64748b;
  stroke-width: 2.5;
  fill: none;
  flex-shrink: 0;
  opacity: 0;
  transform: translateX(-4px);
  transition: all 0.2s ease;
}
.side-btn:hover {
  background: rgba(255, 255, 255, 0.03);
  color: #ffffff;
}
.side-btn:hover svg:not(.arrow) {
  stroke: #38bdf8;
}
.side-btn:hover .arrow {
  opacity: 1;
  transform: translateX(0);
}
.side-btn.active {
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(6, 182, 212, 0.12));
  border: 1px solid rgba(56, 189, 248, 0.15);
  color: #22d3ee;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  font-weight: 700;
}
.side-btn.active svg:not(.arrow) {
  stroke: #22d3ee;
  filter: drop-shadow(0 0 4px rgba(34, 211, 238, 0.35));
}
.side-btn.active .arrow {
  opacity: 1;
  stroke: #22d3ee;
  transform: translateX(0);
}

/* ── MAIN ── */
.main {
  min-width: 0;
}
.card {
  background: rgba(17, 31, 53, 0.65);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-radius: 20px;
  border: 1px solid rgba(56, 189, 248, 0.12);
  padding: 26px;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.25);
}
.page-header-inline {
  margin-bottom: 24px;
}
.card-title {
  font-size: 20px;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 6px;
  letter-spacing: -0.3px;
}
.card-sub {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}
.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 22px;
  gap: 16px;
}

/* PROFILE */
.btn-edit {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 17px;
  border-radius: 11px;
  background: transparent;
  border: 1.5px solid rgba(56, 189, 248, 0.4);
  color: #38bdf8;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
}
.btn-edit:hover {
  background: linear-gradient(135deg, #0284c7 0%, #0891b2 100%);
  border-color: transparent;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(6, 182, 212, 0.25);
}
.btn-edit svg {
  width: 14px;
  height: 14px;
  stroke: currentColor;
  stroke-width: 2.5;
  fill: none;
}
.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
  padding-top: 4px;
}
.info-row {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 5px;
  padding: 13px 16px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 14px;
  transition: all 0.2s ease;
}
.info-row:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(56, 189, 248, 0.15);
}
.info-lbl {
  font-size: 10.5px;
  color: #64748b;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}
.info-val {
  font-size: 13.5px;
  color: #e2e8f0;
  font-weight: 600;
}
.info-val.not-set {
  color: #64748b;
  font-style: italic;
  font-weight: 500;
}

/* FORMS */
.edit-form, .form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.form-row, .form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.form-full {
  grid-column: 1 / -1;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-group label {
  font-size: 12.5px;
  font-weight: 600;
  color: #94a3b8;
  letter-spacing: 0.2px;
}
.form-group input, .form-group select {
  padding: 10px 14px;
  border: 1.5px solid rgba(255, 255, 255, 0.12);
  border-radius: 11px;
  font-size: 13.5px;
  color: #ffffff !important;
  outline: none;
  transition: all 0.2s ease;
  background: rgba(13, 27, 46, 0.5);
}
.form-group input:disabled, .form-group select:disabled {
  opacity: 0.5;
  color: #64748b !important;
  cursor: not-allowed;
}
.form-group select option {
  background-color: #0f1c2e;
  color: #e2e8f0;
}
.form-group select option:disabled {
  color: #64748b;
}
.form-group input:focus, .form-group select:focus {
  border-color: #38bdf8;
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
  background: rgba(13, 27, 46, 0.8);
}
.form-group.error input {
  border-color: #ef4444;
}
.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  color: #cbd5e1;
  font-weight: 600;
  user-select: none;
}
.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #38bdf8;
  cursor: pointer;
}
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}
.btn-cancel {
  padding: 9px 19px;
  border-radius: 11px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.07);
  color: #94a3b8;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-cancel:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.15);
}
.btn-save {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 9px 21px;
  border-radius: 11px;
  background: linear-gradient(135deg, #0284c7 0%, #0891b2 100%);
  border: none;
  color: #ffffff;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(6, 182, 212, 0.2);
}
.btn-save:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 15px rgba(6, 182, 212, 0.3);
}
.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}
.spin {
  width: 16px;
  height: 16px;
  stroke: #ffffff;
  stroke-width: 2.5;
  fill: none;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.err-msg {
  font-size: 12px;
  color: #ef4444;
  font-weight: 600;
  margin-top: 2px;
}

/* ── CATEGORY TABS ── */
.category-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding-bottom: 0;
}
.cat-tab {
  background: transparent;
  border: none;
  padding: 12px 24px;
  font-size: 14px;
  font-weight: 700;
  color: #94a3b8;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: all 0.25s ease;
  display: flex;
  align-items: center;
}
.cat-tab:hover {
  color: #38bdf8;
}
.cat-tab.active {
  color: #22d3ee;
  border-bottom-color: #22d3ee;
}
.badge-cart-like {
  background-color: #f43f5e;
  color: #ffffff;
  font-size: 10px;
  font-weight: 800;
  line-height: 1;
  padding: 2.5px 5.5px;
  border-radius: 9999px;
  border: 1.5px solid #111f35;
  min-width: 18px;
  text-align: center;
  margin-left: 6px;
}

/* ── ORDER TABS (Segmented Control) ── */
.order-tabs {
  display: flex;
  gap: 4px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 16px;
  padding: 4px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.order-tab {
  padding: 8px 16px;
  border-radius: 12px;
  border: none;
  background: transparent;
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}
.order-tab:hover {
  background: rgba(255, 255, 255, 0.02);
  color: #ffffff;
}
.order-tab.active {
  background: #0284c7;
  color: #ffffff;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
}
.otab-count {
  background: rgba(255, 255, 255, 0.15);
  padding: 1.5px 6px;
  border-radius: 99px;
  font-size: 10px;
  font-weight: 700;
}
.order-tab.active .otab-count {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}

/* ── TABLES ── */
.table-card {
  background: transparent;
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}
.order-data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13.5px;
}
.order-data-table th {
  background: rgba(255, 255, 255, 0.02);
  padding: 16px 20px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.8px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.order-data-table td {
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  color: #cbd5e1;
  vertical-align: middle;
}
.order-row {
  transition: all 0.2s ease;
}
.order-row:hover {
  background: rgba(255, 255, 255, 0.015);
}
.id-col {
  font-weight: 700;
}
.order-id {
  color: #38bdf8;
  text-shadow: 0 0 8px rgba(56, 189, 248, 0.15);
}
.status-cell {
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 99px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid currentColor;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Buttons inside tables */
.btn-group {
  display: flex;
  gap: 8px;
}
.btn-xem {
  background: #0284c7;
  color: #ffffff;
  border: none;
  padding: 6px 14px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 12px;
}
.btn-xem:hover {
  background: #0369a1;
  box-shadow: 0 0 10px rgba(2, 132, 199, 0.35);
}
.btn-hoan-tra {
  background: transparent;
  color: #f97316;
  border: 1.5px solid rgba(249, 115, 22, 0.5);
  padding: 5px 14px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 12px;
}
.btn-hoan-tra:hover {
  background: #f97316;
  color: #ffffff;
  border-color: transparent;
  box-shadow: 0 0 10px rgba(249, 115, 22, 0.35);
}
.btn-mua-lai {
  background: #059669;
  color: #ffffff;
  border: none;
  padding: 6px 14px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 12px;
}
.btn-mua-lai:hover {
  background: #047857;
  box-shadow: 0 0 10px rgba(5, 150, 105, 0.35);
}
.btn-huy-don {
  background: transparent;
  color: #ef4444;
  border: 1.5px solid rgba(239, 68, 68, 0.5);
  padding: 5px 14px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 12px;
}
.btn-huy-don:hover {
  background: #ef4444;
  color: #ffffff;
  border-color: transparent;
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.35);
}

/* Pagination */
.pagination-footer {
  padding: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  background: rgba(255, 255, 255, 0.01);
}
.pagination-info {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}
.pagination {
  display: flex;
  align-items: center;
  gap: 10px;
}
.p-arrow {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 6px 14px;
  border-radius: 8px;
  color: #cbd5e1;
  font-weight: 600;
  cursor: pointer;
  font-size: 13px;
  transition: all 0.2s ease;
}
.p-arrow:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}
.p-arrow:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.p-nums {
  display: flex;
  gap: 6px;
}
.p-num {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.02);
  color: #94a3b8;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}
.p-num:hover:not(.active) {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}
.p-num.active {
  background: #0284c7;
  border-color: #0284c7;
  color: #ffffff;
  font-weight: 700;
}
.empty-state-cell {
  padding: 0;
}

/* Empty State Custom styles */
.empty-state-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 24px;
  text-align: center;
}
.empty-icon-wrapper {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(34, 211, 238, 0.06);
  border: 1px dashed rgba(34, 211, 238, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
  color: #22d3ee;
  box-shadow: 0 0 20px rgba(34, 211, 238, 0.05);
}
.empty-icon-custom {
  width: 36px;
  height: 36px;
}
.empty-state-title {
  font-size: 18px;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 8px;
  letter-spacing: -0.2px;
}
.empty-state-desc {
  font-size: 13.5px;
  color: #64748b;
  max-width: 340px;
  margin: 0 0 24px;
  line-height: 1.5;
}
.btn-shop-now {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 11px 26px;
  background: linear-gradient(135deg, #0284c7 0%, #0891b2 100%);
  color: #ffffff;
  font-size: 14px;
  font-weight: 700;
  border-radius: 12px;
  text-decoration: none;
  transition: all 0.25s ease;
  box-shadow: 0 4px 14px rgba(6, 182, 212, 0.25);
}
.btn-shop-now:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4);
}

/* ADDRESS */
.btn-add {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 12px;
  background: linear-gradient(135deg, #0284c7 0%, #0891b2 100%);
  border: none;
  color: #ffffff;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-add:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(6, 182, 212, 0.25);
}
.btn-add svg {
  width: 15px;
  height: 15px;
  stroke: #ffffff;
  stroke-width: 2.5;
  fill: none;
}
.addr-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 16px;
}
.addr-card {
  background: rgba(255, 255, 255, 0.02);
  border-radius: 18px;
  border: 1.5px solid rgba(255, 255, 255, 0.06);
  padding: 20px 24px;
  transition: all 0.25s ease;
}
.addr-card.is-default {
  border-color: rgba(56, 189, 248, 0.3);
  background: rgba(56, 189, 248, 0.02);
}
.addr-card:hover {
  border-color: rgba(56, 189, 248, 0.2);
  background: rgba(255, 255, 255, 0.03);
}
.addr-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.addr-name {
  font-size: 14.5px;
  font-weight: 700;
  color: #ffffff;
}
.default-badge {
  font-size: 11px;
  font-weight: 700;
  color: #22d3ee;
  background: rgba(6, 182, 212, 0.12);
  border: 1px solid rgba(6, 182, 212, 0.25);
  padding: 3px 10px;
  border-radius: 99px;
}
.addr-full {
  font-size: 13.5px;
  color: #94a3b8;
  margin: 0 0 16px;
  line-height: 1.5;
}
.addr-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding-top: 14px;
}
.addr-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.07);
  background: rgba(13, 27, 46, 0.4);
  color: #cbd5e1;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}
.addr-btn svg {
  width: 13px;
  height: 13px;
  stroke: currentColor;
  stroke-width: 2;
  fill: none;
}
.addr-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.15);
}
.addr-btn-default:hover {
  background: rgba(34, 211, 238, 0.1);
  color: #22d3ee;
  border-color: rgba(34, 211, 238, 0.25);
}
.addr-btn-delete:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.25);
}

/* PASSWORD */
.pw-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 24px;
  align-items: start;
}
.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.input-icon {
  position: absolute;
  left: 14px;
  width: 16px;
  height: 16px;
  stroke: #64748b;
  stroke-width: 2;
  fill: none;
  pointer-events: none;
}
.input-wrap input {
  width: 100%;
  padding: 11px 44px 11px 40px;
  border: 1.5px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  font-size: 14px;
  color: #ffffff;
  background: rgba(13, 27, 46, 0.5);
  outline: none;
  transition: all 0.2s ease;
  box-sizing: border-box;
}
.input-wrap input:focus {
  border-color: #38bdf8;
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
  background: rgba(13, 27, 46, 0.8);
}
.form-group.error .input-wrap input {
  border-color: #ef4444;
}
.form-group.error .captcha-input {
  border-color: #ef4444;
}
.eye-btn {
  position: absolute;
  right: 12px;
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.eye-btn svg {
  width: 16px;
  height: 16px;
  stroke: #94a3b8;
  stroke-width: 2;
  fill: none;
}
.captcha-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.captcha-question {
  flex: 1;
  min-height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px dashed rgba(56, 189, 248, 0.25);
  border-radius: 12px;
  background: rgba(13, 27, 46, 0.5);
  color: #ffffff;
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 0.5px;
}
.captcha-refresh {
  width: 42px;
  height: 42px;
  border: 1px solid rgba(56, 189, 248, 0.25);
  border-radius: 12px;
  background: rgba(56, 189, 248, 0.06);
  color: #38bdf8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s ease;
}
.captcha-refresh:hover:not(:disabled) {
  background: rgba(56, 189, 248, 0.12);
  color: #22d3ee;
}
.captcha-refresh:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.captcha-refresh svg {
  width: 18px;
  height: 18px;
  stroke: currentColor;
  stroke-width: 2;
  fill: none;
}
.captcha-input {
  width: 100%;
  margin-top: 8px;
  padding: 11px 14px;
  border: 1.5px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  background: rgba(13, 27, 46, 0.5);
  color: #ffffff;
  font-size: 14px;
  outline: none;
  box-sizing: border-box;
  transition: all 0.2s ease;
}
.captcha-input:focus {
  border-color: #38bdf8;
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
  background: rgba(13, 27, 46, 0.8);
}
.strength-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 4px;
}
.strength-track {
  flex: 1;
  height: 5px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 99px;
  overflow: hidden;
}
.strength-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.3s ease, background 0.3s ease;
}
.strength-label {
  font-size: 12px;
  font-weight: 700;
  min-width: 72px;
  text-align: right;
}
.req-card {
  background: rgba(255, 255, 255, 0.02);
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 20px;
  margin-bottom: 16px;
}
.req-title {
  font-size: 12px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin: 0 0 14px;
}
.req-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.req-list li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #64748b;
  font-weight: 600;
  transition: color 0.2s ease;
}
.req-list li svg {
  width: 16px;
  height: 16px;
  stroke: #64748b;
  stroke-width: 2.5;
  fill: none;
  flex-shrink: 0;
  transition: stroke 0.2s ease;
}
.req-list li.ok {
  color: #10b981;
}
.req-list li.ok svg {
  stroke: #10b981;
}
.tip-card {
  background: rgba(56, 189, 248, 0.03);
  border: 1px solid rgba(56, 189, 248, 0.15);
  border-radius: 18px;
  padding: 18px;
  display: flex;
  gap: 12px;
}
.tip-icon svg {
  width: 20px;
  height: 20px;
  stroke: #38bdf8;
  stroke-width: 2;
  fill: none;
}
.tip-title {
  font-size: 13px;
  font-weight: 700;
  color: #38bdf8;
  margin: 0 0 8px;
}
.tip-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.tip-list li {
  font-size: 12px;
  color: #64748b;
  padding-left: 12px;
  position: relative;
  font-weight: 500;
}
.tip-list li::before {
  content: '•';
  position: absolute;
  left: 0;
  color: #38bdf8;
}

/* PROMOTIONS */
.promo-code-badge {
  display: inline-block;
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
  border: 1px dashed rgba(251, 191, 36, 0.5);
  padding: 3px 12px;
  border-radius: 6px;
  font-size: 12.5px;
  font-weight: 700;
  letter-spacing: 0.5px;
  font-family: monospace;
}

/* EMPTY */
.empty {
  text-align: center;
  padding: 48px 0;
  color: #64748b;
}
.empty svg {
  width: 44px;
  height: 44px;
  stroke: #64748b;
  stroke-width: 1.5;
  fill: none;
  margin-bottom: 10px;
}
.empty p {
  font-size: 14px;
}

/* MODAL OVERLAY */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(5, 11, 21, 0.75);
  z-index: 9000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}
.modal {
  background: #0f1c30;
  border: 1px solid rgba(56, 189, 248, 0.15);
  box-shadow: 0 24px 50px rgba(0, 0, 0, 0.4);
  border-radius: 24px;
  width: 100%;
  max-width: 520px;
  max-height: 88vh;
  overflow-y: auto;
}
.modal-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 24px 28px 0;
}
.modal-title {
  font-size: 18px;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 4px;
  letter-spacing: -0.2px;
}
.modal-id {
  font-size: 12.5px;
  color: #64748b;
  margin: 0;
}
.close-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.04);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  transition: all 0.2s ease;
}
.close-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}
.close-btn svg {
  width: 14px;
  height: 14px;
  stroke: currentColor;
  stroke-width: 2.5;
}
.modal-body {
  padding: 20px 28px 28px;
}
.modal-status {
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  padding: 5px 14px;
  border-radius: 99px;
  margin-bottom: 20px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border: 1px solid currentColor;
  background: rgba(255, 255, 255, 0.03);
}

.address-modal {
  max-width: 720px;
}
.address-modal-form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.address-modal-form .form-group {
  margin: 0;
}
.address-modal-form input,
.address-modal-form select {
  width: 100%;
  box-sizing: border-box;
}
.address-modal-actions {
  justify-content: flex-end;
  margin-top: 12px;
}
.btn-modal-mua {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #ffffff;
  border: none;
  padding: 11px 24px;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s ease;
}
.btn-modal-mua:hover {
  box-shadow: 0 0 15px rgba(5, 150, 105, 0.35);
}
.btn-modal-hoantra {
  background: transparent;
  color: #f97316;
  border: 1.5px solid rgba(249, 115, 22, 0.6);
  padding: 10px 24px;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s ease;
}
.btn-modal-hoantra:hover {
  background: #f97316;
  color: #ffffff;
  border-color: transparent;
  box-shadow: 0 0 15px rgba(249, 115, 22, 0.35);
}
.region-picker-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.region-picker-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}
.inline-map-field small {
  display: block;
  margin-top: 6px;
  color: #64748b;
  font-size: 12px;
}

/* TIMELINE */
.timeline {
  display: flex;
  flex-direction: column;
  margin-bottom: 24px;
}
.tl-item {
  display: flex;
  gap: 16px;
}
.tl-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
  width: 24px;
}
.tl-dot {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.12);
  background: #0f1c30;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.25s ease;
}
.tl-item.done .tl-dot {
  background: #38bdf8;
  border-color: #38bdf8;
  box-shadow: 0 0 12px rgba(56, 189, 248, 0.4);
}
.tl-dot svg {
  width: 12px;
  height: 12px;
  stroke: #ffffff;
  stroke-width: 3;
  fill: none;
}
.tl-line {
  width: 2px;
  flex: 1;
  min-height: 24px;
  background: rgba(255, 255, 255, 0.12);
  margin: 2px 0;
}
.tl-line.done {
  background: #38bdf8;
}
.tl-content {
  padding-bottom: 20px;
  flex: 1;
}
.tl-label {
  font-size: 13.5px;
  font-weight: 700;
  color: #ffffff;
  margin: 2px 0;
}
.tl-date {
  font-size: 11px;
  color: #64748b;
  margin: 0;
}
.section-title {
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin: 0 0 12px;
}
.modal-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  margin-bottom: 10px;
}
.modal-item img {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  object-fit: cover;
  border: 1px solid rgba(255, 255, 255, 0.08);
  flex-shrink: 0;
}
.modal-item-info {
  flex: 1;
  min-width: 0;
}
.modal-item-name {
  font-size: 13.5px;
  font-weight: 600;
  color: #ffffff;
  margin: 0 0 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.modal-item-qty {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}
.modal-item-price {
  font-size: 14px;
  font-weight: 700;
  color: #38bdf8;
}
.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  margin-top: 16px;
}
.modal-btns {
  display: flex;
  gap: 12px;
}
.btn-modal-huy {
  background: transparent;
  border: 1.5px solid rgba(239, 68, 68, 0.5);
  color: #ef4444;
  padding: 8px 18px;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  font-size: 13.5px;
  transition: all 0.2s ease;
}
.btn-modal-huy:hover {
  background: rgba(239, 68, 68, 0.1);
  border-color: transparent;
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.25);
}
.modal-total-wrap {
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.total-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.total-value {
  font-size: 20px;
  font-weight: 800;
  color: #38bdf8;
  text-shadow: 0 0 12px rgba(56, 189, 248, 0.2);
}

/* TOAST STYLE */
.toast {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 9999;
  background: #0f1c30;
  border: 1px solid rgba(56, 189, 248, 0.25);
  color: #ffffff;
  padding: 14px 24px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
  font-weight: 600;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
}
.toast svg {
  width: 18px;
  height: 18px;
  stroke: #4ade80;
  stroke-width: 2.5;
  fill: none;
}

/* REVIEW STYLES */
.btn-review-small {
  background: rgba(56, 189, 248, 0.06);
  border: 1px solid rgba(56, 189, 248, 0.3);
  color: #38bdf8;
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  margin-top: 6px;
  transition: all 0.2s ease;
}
.btn-review-small:hover {
  background: #38bdf8;
  color: #ffffff;
  border-color: transparent;
}
.reviewed-tag {
  display: inline-block;
  font-size: 11px;
  color: #10b981;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.2);
  padding: 2.5px 8px;
  border-radius: 999px;
  font-weight: 700;
  margin-top: 6px;
}
.review-modal {
  max-width: 480px !important;
}
.review-product-info {
  background: rgba(13, 27, 46, 0.5);
  padding: 14px;
  border-radius: 12px;
  margin-bottom: 20px;
  border: 1px solid rgba(255, 255, 255, 0.05);
}
.review-product-name {
  font-weight: 700;
  font-size: 14.5px;
  color: #ffffff;
  margin: 0;
}
.rating-selector {
  margin-bottom: 24px;
  text-align: center;
}
.rating-label {
  display: block;
  font-size: 13.5px;
  color: #94a3b8;
  margin-bottom: 12px;
  font-weight: 600;
}
.stars-input {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.star-btn {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  color: rgba(255, 255, 255, 0.15);
  transition: all 0.2s ease;
}
.star-btn svg {
  width: 32px;
  height: 32px;
}
.star-btn.filled {
  color: #fbbf24;
  transform: scale(1.15);
  filter: drop-shadow(0 0 8px rgba(251, 191, 36, 0.35));
}
.rating-text {
  margin-left: 12px;
  font-size: 14px;
  font-weight: 700;
  color: #fbbf24;
  min-width: 100px;
  text-align: left;
}
.w-100 {
  width: 100%;
}

/* Animations and transitions */
.toast-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.4, 0.64, 1); }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from { opacity: 0; transform: translateY(-12px); }
.toast-leave-to { opacity: 0; transform: translateY(-8px); }
.fade-enter-active { transition: opacity 0.2s; }
.fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.d-none { display: none; }

/* ===================== RESPONSIVE STYLES ===================== */
@media (max-width: 1024px) {
  .container {
    grid-template-columns: 1fr;
    gap: 24px;
  }
  .sidebar {
    position: static;
  }
}

@media (max-width: 768px) {
  .page {
    padding: 24px 16px;
  }
  .card {
    padding: 24px 20px;
  }
  .form-row, .form-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .pw-layout {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  .table-card {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .order-data-table {
    min-width: 600px;
  }
}

@media (max-width: 576px) {
  .info-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .info-row {
    padding: 12px 16px;
  }
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .btn-edit {
    width: 100%;
    justify-content: center;
  }
  .order-tabs {
    flex-direction: column;
    align-items: stretch;
    gap: 4px;
    padding: 4px;
  }
  .order-tab {
    width: 100%;
    justify-content: center;
  }
  .modal {
    width: calc(100% - 24px);
    margin: 12px;
  }
  .modal-head {
    padding: 18px 20px 0;
  }
  .modal-body {
    padding: 16px 20px 20px;
  }
  .modal-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    padding: 12px;
  }
  .modal-item-right {
    width: 100%;
    text-align: left !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid rgba(255,255,255,0.07);
    padding-top: 8px;
    margin-top: 4px;
  }
  .modal-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  .modal-btns {
    flex-direction: column;
    gap: 8px;
  }
  .btn-modal-huy, .btn-modal-mua, .btn-modal-hoantra {
    width: 100%;
    text-align: center;
  }
  .modal-total-wrap {
    text-align: left;
  }
}
</style>

<style scoped>

.category-tabs { display: flex; gap: 12px; margin-bottom: -4px; border-bottom: 2px solid #e2e8f0; padding-bottom: 0; }
.cat-tab { background: transparent; border: none; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
.cat-tab:hover { color: #4f46e5; }
.cat-tab.active { color: #4f46e5; border-bottom-color: #4f46e5; }

.empty-msg {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  color: #64748b;
  font-size: 15px;
}
.empty-icon {
  width: 64px;
  height: 64px;
  margin-bottom: 16px;
  stroke-width: 1.2;
}

.turnstile-box {
  width: 100%;
  max-width: 390px;
  min-height: 50px;
  display: grid;
  grid-template-columns: 28px minmax(0, 1fr) 102px;
  align-items: center;
  gap: 10px;
  padding: 7px 11px;
  border: 1px solid #d1d5db;
  border-radius: 3px;
  background: #fafafa;
  box-shadow: 0 1px 1px rgba(15, 23, 42, 0.04);
  margin: 0 auto;
}

.turnstile-box.checked {
  border-color: #cbd5e1;
  background: #ffffff;
}

.turnstile-check {
  width: 24px;
  height: 24px;
  border: 2px solid #64748b;
  border-radius: 3px;
  background: #f8fafc;
  color: #16a34a;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 0;
}

.turnstile-check:disabled {
  cursor: wait;
  opacity: 0.8;
}

.turnstile-check svg {
  width: 17px;
  height: 17px;
  stroke: currentColor;
  stroke-width: 3;
}

.turnstile-text {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
  line-height: 1.3;
}

.turnstile-brand {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  color: #1f2937;
  text-align: center;
  line-height: 1.05;
}

.cloudflare-mark {
  width: 28px;
  height: 19px;
  position: relative;
}

.cloudflare-mark::before,
.cloudflare-mark::after,
.cloudflare-mark span {
  content: '';
  position: absolute;
  background: #f97316;
}

.cloudflare-mark::before {
  width: 17px;
  height: 10px;
  border-radius: 12px 12px 4px 4px;
  left: 7px;
  top: 6px;
}

.cloudflare-mark::after {
  width: 11px;
  height: 11px;
  border-radius: 50%;
  left: 2px;
  top: 7px;
}

.cloudflare-mark span {
  width: 13px;
  height: 13px;
  border-radius: 50%;
  left: 9px;
  top: 1px;
}

.turnstile-brand strong {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 1px;
}

.turnstile-brand small {
  font-size: 8px;
  color: #475569;
  text-decoration: underline;
}

.turnstile-refresh {
  width: 15px;
  height: 15px;
  border: 0;
  background: transparent;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  cursor: pointer;
}

.turnstile-refresh svg {
  width: 12px;
  height: 12px;
  stroke: currentColor;
  stroke-width: 2.2;
}

.turnstile-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid #cbd5e1;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@media (max-width: 576px) {
  .turnstile-box {
    grid-template-columns: 34px minmax(0, 1fr);
  }

  .turnstile-brand {
    grid-column: 1 / -1;
    flex-direction: row;
    justify-content: flex-end;
  }

  .cloudflare-mark {
    transform: scale(0.82);
  }
}
</style>

<style scoped>
/* Light customer account theme */
.page {
  background: #f5f7fb;
  padding: 32px 24px 64px;
  color: #0f172a;
}

.container {
  max-width: 1240px;
  grid-template-columns: 280px minmax(0, 1fr);
  gap: 28px;
}

.sidebar,
.card,
.modal,
.req-card,
.tip-card,
.table-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
}

.sidebar {
  border-radius: 16px;
  top: 18px;
}

.avatar-section,
.stat-grid,
.form-actions,
.modal-footer,
.pagination-footer {
  border-color: #e2e8f0;
}

.avatar-circle {
  border-color: #0ea5e9;
  box-shadow: 0 8px 22px rgba(14, 165, 233, 0.18);
}

.sidebar-name,
.card-title,
.page-header-inline .card-title,
.modal-title,
.modal-item-name,
.order-data-table td,
.info-val,
.req-title,
.tip-title {
  color: #0f172a !important;
}

.sidebar-badge {
  color: #0369a1;
  background: #e0f2fe;
  border-color: #bae6fd;
}

.sidebar-join,
.card-sub,
.info-lbl,
.info-val.not-set,
.stat-lbl,
.form-group label,
.checkbox-label,
.pagination-info,
.modal-id,
.modal-item-qty,
.tip-list,
.req-list li,
.empty-msg {
  color: #64748b !important;
}

.stat-card,
.info-row,
.modal-item,
.review-product-info,
.captcha-question {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.stat-card:hover,
.info-row:hover {
  background: #f1f5f9;
  border-color: #bfdbfe;
  box-shadow: none;
}

.stat-val {
  color: #0f172a;
}

.stat-card svg,
.side-btn svg:not(.arrow),
.side-btn:hover svg:not(.arrow),
.side-btn.active svg:not(.arrow) {
  stroke: #0284c7;
  filter: none;
}

.side-btn {
  color: #475569;
  border-radius: 10px;
}

.side-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.side-btn.active {
  background: #e0f2fe;
  border-color: #bae6fd;
  color: #0369a1;
  box-shadow: none;
}

.side-btn .arrow,
.side-btn.active .arrow {
  stroke: #0284c7;
}

.card {
  border-radius: 16px;
  padding: 32px;
}

.btn-edit {
  background: #ffffff;
  border-color: #0ea5e9;
  color: #0284c7;
}

.btn-edit:hover,
.btn-save,
.order-tab.active,
.btn-xem,
.p-num.active,
.btn-add,
.btn-modal-mua {
  background: #0284c7;
  color: #ffffff;
  border-color: #0284c7;
  box-shadow: none;
}

.btn-save:hover,
.btn-xem:hover,
.btn-add:hover,
.btn-modal-mua:hover {
  background: #0369a1;
  box-shadow: none;
}

.btn-cancel,
.p-arrow,
.p-num,
.close-btn {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #475569;
}

.btn-cancel:hover,
.p-arrow:hover:not(:disabled),
.p-num:hover,
.close-btn:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
  color: #0f172a;
}

.form-group input,
.form-group select,
.captcha-input,
.input-wrap input,
.address-modal-form input,
.address-modal-form select {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #0f172a !important;
}

.form-group input:focus,
.form-group select:focus,
.captcha-input:focus,
.input-wrap input:focus,
.address-modal-form input:focus,
.address-modal-form select:focus {
  background: #ffffff;
  border-color: #0284c7;
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.14);
}

.form-group input:disabled,
.form-group select:disabled {
  color: #94a3b8 !important;
  background: #f8fafc;
}

.form-group select option {
  background: #ffffff;
  color: #0f172a;
}

.checkbox-label input[type="checkbox"] {
  accent-color: #0284c7;
}

.category-tabs {
  border-bottom-color: #e2e8f0;
}

.cat-tab {
  color: #64748b;
}

.cat-tab:hover,
.cat-tab.active {
  color: #0284c7;
}

.cat-tab.active {
  border-bottom-color: #0284c7;
}

.badge-cart-like {
  border-color: #ffffff;
}

.order-tabs {
  background: #f1f5f9;
  border-color: #e2e8f0;
}

.order-tab {
  color: #475569;
}

.order-tab:hover {
  background: #ffffff;
  color: #0f172a;
}

.order-data-table th {
  background: #f8fafc;
  color: #64748b;
  border-bottom-color: #e2e8f0;
}

.order-data-table td {
  border-bottom-color: #e2e8f0;
}

.order-row:hover {
  background: #f8fafc;
}

.order-id,
.modal-item-price,
.total-amount,
.promo-code-badge,
.tip-icon {
  color: #0284c7;
  text-shadow: none;
}

.status-cell {
  background: #ffffff;
}

.pagination-footer {
  background: #ffffff;
}

.pw-layout {
  align-items: start;
}

.input-wrap {
  background: #ffffff;
}

.input-icon,
.eye-btn svg {
  stroke: #64748b;
}

.req-list li.ok,
.req-list li.ok svg {
  color: #16a34a;
  stroke: #16a34a;
}

.tip-card {
  background: #f8fafc;
}

.overlay {
  background: rgba(15, 23, 42, 0.45);
}

.modal {
  border-radius: 16px;
}

.modal-status {
  border: 1px solid currentColor;
}

.modal-total-wrap span:first-child {
  color: #64748b;
}

.timeline-dot {
  background: #ffffff;
  border-color: #cbd5e1;
}

.timeline-line {
  background: #e2e8f0;
}

.review-rating .star {
  color: #cbd5e1;
}

.review-rating .star.active,
.review-rating .star:hover {
  color: #f59e0b;
}

@media (max-width: 900px) {
  .container {
    grid-template-columns: 1fr;
  }

  .sidebar {
    position: static;
  }
}

@media (max-width: 576px) {
  .page {
    padding: 20px 14px 48px;
  }

  .card {
    padding: 22px 16px;
  }

  .modal-item-right {
    border-top-color: #e2e8f0;
  }
}
</style>
