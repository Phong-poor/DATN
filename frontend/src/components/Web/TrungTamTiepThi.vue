<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'
import { getUser } from '@/services/auth'
import { vietnamBanks } from '@/constants/vietnamBanks'
import {
  Users,
  DollarSign,
  Clock,
  CheckCircle,
  CreditCard,
  Copy,
  Check,
  History,
  AlertCircle,
  Info,
  TrendingUp,
  Award,
  Wallet,
  Video,
  UploadCloud,
  Trash2,
  Pencil,
  Building2,
  ChevronDown,
  Search
} from 'lucide-vue-next'

const loading = ref(true)
const activating = ref(false)
const activeTab = ref('overview')
const currentUser = ref(getUser())
const avatarBroken = ref(false)
const data = ref({
  active: false,
  profile: null,
  ref_link: '',
  stats: {
    total_referrals: 0,
    pending_commission: 0,
    approved_commission: 0,
    paid_commission: 0,
  },
})
const referrals = ref([])
const commissions = ref([])
const withdraws = ref([])
const affiliateVideos = ref([])
const withdrawForm = ref({
  amount: '',
  bank_name: '',
  bank_account_name: '',
  bank_account_number: '',
})
const videoForm = ref({
  title: '',
  description: '',
  product_id: '',
  video_url: '',
  video: null,
  thumbnail: null,
})
const videoPreviewUrl = ref('')
const thumbnailPreviewUrl = ref('')
const videoSubmitting = ref(false)
const editingAffiliateVideoId = ref(null)
const withdrawLoading = ref(false)
const bankDropdownOpen = ref(false)
const bankSearch = ref('')
const error = ref('')
const copied = ref(false)

// Product Affiliate Link Generator variables
const shopProducts = ref([])
const customLinkInput = ref('')
const selectedProductId = ref('')
const generatedLink = ref('')
const genCopied = ref(false)

const filteredBanks = computed(() => {
  const keyword = bankSearch.value.trim().toLocaleLowerCase('vi')
  if (!keyword) return vietnamBanks
  return vietnamBanks.filter(bank => bank.toLocaleLowerCase('vi').includes(keyword))
})

const selectBank = (bank) => {
  withdrawForm.value.bank_name = bank
  bankSearch.value = ''
  bankDropdownOpen.value = false
}

const closeBankDropdown = (event) => {
  if (event?.currentTarget?.contains(event.relatedTarget)) return
  bankDropdownOpen.value = false
  bankSearch.value = ''
}

const affiliateUser = computed(() => data.value.profile?.user || currentUser.value || {})
const affiliateUserName = computed(() => affiliateUser.value?.name || affiliateUser.value?.ten || 'NextGen')
const affiliateInitial = computed(() => String(affiliateUserName.value || 'N').trim().charAt(0).toUpperCase())
const affiliateAvatarSrc = computed(() => {
  if (avatarBroken.value) return ''
  const avatar = affiliateUser.value?.avatar
    || affiliateUser.value?.anhdaidien
    || affiliateUser.value?.anh_dai_dien
    || affiliateUser.value?.avatar_url
  return avatar ? storageUrl(avatar) : ''
})

const handleAffiliateAvatarError = () => {
  avatarBroken.value = true
}

const getApiErrorMessage = (error, fallback = 'Vui lòng thử lại sau.') => {
  const data = error?.response?.data
  const firstError = data?.errors
    ? Object.values(data.errors).flat().find(Boolean)
    : ''
  return firstError || data?.message || fallback
}

const playInlineVideo = (event) => {
  const video = event.currentTarget?.querySelector?.('video')
  if (!video) return
  video.currentTime = video.currentTime || 0
  video.play().catch(() => {})
}

const pauseInlineVideo = (event) => {
  const video = event.currentTarget?.querySelector?.('video')
  if (!video) return
  video.pause()
}

const isPlayableVideoSrc = (src) => {
  const value = String(src || '').trim()
  return Boolean(value) && (
    value.startsWith('/storage/')
    || value.startsWith('blob:')
    || /\.(mp4|webm|mov|avi|m4v|mkv)(\?|#|$)/i.test(value)
  )
}

const isFileObject = (value) => {
  return typeof File !== 'undefined' && value instanceof File
}

const fetchShopProducts = async () => {
  try {
    const res = await api.get('/sanpham')
    const raw = Array.isArray(res.data) ? res.data : (res.data.data || [])
    shopProducts.value = raw
  } catch (err) {
    console.error('Không thể tải danh sách sản phẩm tiếp thị', err)
  }
}

const generateCustomLink = () => {
  if (!customLinkInput.value.trim()) {
    generatedLink.value = ''
    return
  }
  
  selectedProductId.value = '' // Clear select box
  const code = data.value.profile?.affiliate_code
  if (!code) return
  
  let val = customLinkInput.value.trim()
  
  try {
    if (val.startsWith('http://') || val.startsWith('https://')) {
      const url = new URL(val)
      url.searchParams.set('ref', code)
      generatedLink.value = url.toString()
    } else {
      let path = val
      if (path.includes('?')) {
        const parts = path.split('?')
        const basePart = parts[0]
        const queryPart = parts[1]
        const params = new URLSearchParams(queryPart)
        params.set('ref', code)
        generatedLink.value = `${window.location.origin}${basePart.startsWith('/') ? '' : '/'}${basePart}?${params.toString()}`
      } else {
        generatedLink.value = `${window.location.origin}${path.startsWith('/') ? '' : '/'}${path}?ref=${code}`
      }
    }
  } catch (e) {
    generatedLink.value = `${window.location.origin}${val.startsWith('/') ? '' : '/'}${val}?ref=${code}`
  }
}

const onProductSelectChange = () => {
  if (!selectedProductId.value) {
    generatedLink.value = ''
    return
  }
  
  customLinkInput.value = '' // Clear custom input
  const code = data.value.profile?.affiliate_code
  if (!code) return
  
  generatedLink.value = `${window.location.origin}/products/${selectedProductId.value}?ref=${code}`
}

const fallbackCopyTextToClipboard = (text) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  textArea.style.position = 'fixed'
  textArea.style.top = '0'
  textArea.style.left = '0'
  textArea.style.opacity = '0'
  textArea.style.pointerEvents = 'none'
  document.body.appendChild(textArea)
  textArea.focus()
  textArea.select()
  try {
    const successful = document.execCommand('copy')
    document.body.removeChild(textArea)
    return successful
  } catch (err) {
    console.error('Fallback copy failed:', err)
    document.body.removeChild(textArea)
    return false
  }
}

const copyGeneratedLink = async () => {
  if (!generatedLink.value) return
  try {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(generatedLink.value)
    } else {
      if (!fallbackCopyTextToClipboard(generatedLink.value)) {
        throw new Error('Fallback copy failed')
      }
    }
    genCopied.value = true
    swal.toast('Đã sao chép link tiếp thị!', 'success')
    setTimeout(() => {
      genCopied.value = false
    }, 2000)
  } catch (e) {
    console.warn('Không thể copy bằng navigator.clipboard, thử copy bằng fallback...', e)
    if (fallbackCopyTextToClipboard(generatedLink.value)) {
      genCopied.value = true
      swal.toast('Đã sao chép link tiếp thị!', 'success')
      setTimeout(() => {
        genCopied.value = false
      }, 2000)
    } else {
      swal.error('Lỗi sao chép', 'Không thể tự động copy vào bộ nhớ tạm.')
    }
  }
}

const formatMoney = (value) => Number(value || 0).toLocaleString('vi-VN') + 'd'

const summaryCards = computed(() => [
  { label: 'Người được giới thiệu', value: data.value.stats.total_referrals },
  { label: 'Hoa hồng chờ duyệt', value: formatMoney(data.value.stats.pending_commission) },
  { label: 'Hoa hồng đã duyệt', value: formatMoney(data.value.stats.approved_commission) },
  { label: 'Đã thanh toán', value: formatMoney(data.value.stats.paid_commission) },
])

const selectedVideoProduct = computed(() => {
  if (!videoForm.value.product_id) return null
  return shopProducts.value.find(p => String(p.id_sanpham) === String(videoForm.value.product_id)) || null
})

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    const [meRes, refRes, comRes, wdRes, videoRes] = await Promise.all([
      api.get('/affiliate/me'),
      api.get('/affiliate/referrals'),
      api.get('/affiliate/commissions'),
      api.get('/affiliate/withdraws'),
      api.get('/affiliate/videos'),
    ])
    data.value = meRes.data
    referrals.value = refRes.data
    commissions.value = comRes.data
    withdraws.value = wdRes.data
    affiliateVideos.value = videoRes.data

    if (data.value.active && data.value.profile?.affiliate_code) {
      fetchShopProducts()
    }
  } catch (e) {
    error.value = e?.response?.data?.message || 'Không tải được dữ liệu affiliate.'
  } finally {
    loading.value = false
  }
}

const refreshAffiliateDataKeepingScroll = async () => {
  const left = window.scrollX
  const top = window.scrollY
  await loadAll()
  await nextTick()
  requestAnimationFrame(() => {
    window.scrollTo({ left, top, behavior: 'auto' })
  })
}

const submitWithdraw = async () => {
  if (
    !withdrawForm.value.amount ||
    !withdrawForm.value.bank_name ||
    !withdrawForm.value.bank_account_name ||
    !withdrawForm.value.bank_account_number
  ) {
    swal.error('Lỗi nhập liệu', 'Vui lòng điền đầy đủ tất cả thông tin yêu cầu rút tiền.')
    return
  }

  const amountNum = Number(withdrawForm.value.amount || 0)
  const minimumWithdrawal = Number(data.value.rules?.minimum_withdrawal || 100000)
  if (amountNum < minimumWithdrawal) {
    swal.error('Số tiền không hợp lệ', `Số tiền rút tối thiểu phải từ ${formatMoney(minimumWithdrawal)} trở lên.`)
    return
  }

  const available = Number(data.value.stats?.available_balance || 0)
  if (amountNum > available) {
    swal.error('Số dư không đủ', `Số dư khả dụng của bạn (${formatMoney(available)}) không đủ để rút số tiền này.`)
    return
  }

  const isConfirmed = await swal.confirm(
    'Xác nhận rút tiền',
    `Bạn có chắc chắn muốn gửi yêu cầu rút ${formatMoney(amountNum)} về tài khoản ngân hàng ${withdrawForm.value.bank_name}?`
  )
  if (!isConfirmed) return

  withdrawLoading.value = true
  try {
    await api.post('/affiliate/withdraws', {
      amount: amountNum,
      bank_name: withdrawForm.value.bank_name,
      bank_account_name: withdrawForm.value.bank_account_name,
      bank_account_number: withdrawForm.value.bank_account_number,
    })
    withdrawForm.value = {
      amount: '',
      bank_name: '',
      bank_account_name: '',
      bank_account_number: '',
    }
    await loadAll()
    swal.success('Đã gửi yêu cầu!', 'Yêu cầu rút tiền của bạn đã được tiếp nhận và chờ phê duyệt.')
  } catch (e) {
    swal.error('Lỗi gửi yêu cầu', e?.response?.data?.message || 'Gửi yêu cầu rút tiền thất bại.')
  } finally {
    withdrawLoading.value = false
  }
}

const handleAffiliateVideoFile = (event) => {
  const file = event.target.files?.[0] || null
  videoForm.value.video = file
  if (videoPreviewUrl.value) URL.revokeObjectURL(videoPreviewUrl.value)
  videoPreviewUrl.value = file ? URL.createObjectURL(file) : ''
}

const handleAffiliateThumbnailFile = (event) => {
  const file = event.target.files?.[0] || null
  videoForm.value.thumbnail = file
  if (thumbnailPreviewUrl.value) URL.revokeObjectURL(thumbnailPreviewUrl.value)
  thumbnailPreviewUrl.value = file ? URL.createObjectURL(file) : ''
}

const resetAffiliateVideoForm = () => {
  videoForm.value = {
    title: '',
    description: '',
    product_id: '',
    video_url: '',
    video: null,
    thumbnail: null,
  }
  if (videoPreviewUrl.value) URL.revokeObjectURL(videoPreviewUrl.value)
  if (thumbnailPreviewUrl.value) URL.revokeObjectURL(thumbnailPreviewUrl.value)
  videoPreviewUrl.value = ''
  thumbnailPreviewUrl.value = ''
  editingAffiliateVideoId.value = null
}

const editAffiliateVideo = (video) => {
  editingAffiliateVideoId.value = video.id
  videoForm.value = {
    title: video.title || video.tieu_de || '',
    description: video.description || video.mo_ta || '',
    product_id: video.product_id || video.id_sanpham || '',
    video_url: video.video_url || (!isPlayableVideoSrc(video.video_src) ? (video.video_src || '') : ''),
    video: null,
    thumbnail: null,
  }
  if (videoPreviewUrl.value) URL.revokeObjectURL(videoPreviewUrl.value)
  if (thumbnailPreviewUrl.value) URL.revokeObjectURL(thumbnailPreviewUrl.value)
  videoPreviewUrl.value = ''
  thumbnailPreviewUrl.value = ''
  requestAnimationFrame(() => {
    document.querySelector('.video-submit-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

const submitAffiliateVideo = async () => {
  if (!videoForm.value.title.trim()) {
    swal.error('Thiếu tiêu đề', 'Vui lòng nhập tiêu đề video affiliate.')
    return
  }

  if (!editingAffiliateVideoId.value && !videoForm.value.video && !videoForm.value.video_url.trim()) {
    swal.error('Thiếu video', 'Vui lòng tải video lên hoặc nhập link video.')
    return
  }

  if (videoForm.value.video && !isFileObject(videoForm.value.video)) {
    swal.error('File video không hợp lệ', 'Vui lòng chọn lại file video từ máy của bạn.')
    return
  }

  const formData = new FormData()
  formData.append('title', videoForm.value.title.trim())
  formData.append('description', videoForm.value.description.trim())
  if (videoForm.value.product_id) formData.append('product_id', videoForm.value.product_id)
  if (videoForm.value.video_url.trim()) formData.append('video_url', videoForm.value.video_url.trim())
  if (isFileObject(videoForm.value.video)) formData.append('video', videoForm.value.video)
  if (isFileObject(videoForm.value.thumbnail)) formData.append('thumbnail', videoForm.value.thumbnail)
  if (editingAffiliateVideoId.value) formData.append('_method', 'PUT')

  videoSubmitting.value = true
  try {
    const endpoint = editingAffiliateVideoId.value
      ? `/affiliate/videos/${editingAffiliateVideoId.value}`
      : '/affiliate/videos'
    await api.post(endpoint, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const wasEditing = Boolean(editingAffiliateVideoId.value)
    resetAffiliateVideoForm()
    await refreshAffiliateDataKeepingScroll()
    activeTab.value = 'videos'
    swal.success(wasEditing ? 'Đã cập nhật video' : 'Đã gửi video', wasEditing ? 'Video đã được cập nhật và chuyển về trạng thái chờ admin duyệt lại.' : 'Video affiliate của bạn đã được gửi và đang chờ admin duyệt.')
  } catch (e) {
    swal.error('Không gửi được video', getApiErrorMessage(e, 'Vui lòng kiểm tra lại file hoặc link video.'))
  } finally {
    videoSubmitting.value = false
  }
}

const deleteAffiliateVideo = async (video) => {
  const ok = await swal.confirm('Xóa video affiliate', `Bạn muốn xóa video "${video.title || video.tieu_de}"?`)
  if (!ok) return

  try {
    await api.delete(`/affiliate/videos/${video.id}`)
    await loadAll()
    swal.toast('Đã xóa video affiliate.', 'success')
  } catch (e) {
    swal.error('Không xóa được video', e?.response?.data?.message || 'Vui lòng thử lại sau.')
  }
}

const activate = async () => {
  const isConfirmed = await swal.confirm(
    'Kích hoạt Affiliate',
    'Bạn muốn tham gia chương trình tiếp thị liên kết để bắt đầu gia tăng thu nhập thụ động cùng NextGen?'
  )
  if (!isConfirmed) return

  activating.value = true
  try {
    await api.post('/affiliate/activate')
    await loadAll()
    swal.success('Đã gửi đăng ký', 'Hồ sơ affiliate đang chờ quản trị viên duyệt trước khi hoạt động.')
  } catch (e) {
    swal.error('Lỗi kích hoạt', e?.response?.data?.message || 'Kích hoạt tiếp thị liên kết thất bại.')
  } finally {
    activating.value = false
  }
}

const copyLink = async () => {
  if (!data.value.ref_link) return
  try {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(data.value.ref_link)
    } else {
      if (!fallbackCopyTextToClipboard(data.value.ref_link)) {
        throw new Error('Fallback copy failed')
      }
    }
    copied.value = true
    swal.toast('Đã sao chép link giới thiệu!', 'success')
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (e) {
    console.warn('Không thể copy bằng navigator.clipboard, thử copy bằng fallback...', e)
    if (fallbackCopyTextToClipboard(data.value.ref_link)) {
      copied.value = true
      swal.toast('Đã sao chép link giới thiệu!', 'success')
      setTimeout(() => {
        copied.value = false
      }, 2000)
    } else {
      swal.error('Lỗi sao chép', 'Không thể tự động copy vào bộ nhớ tạm.')
    }
  }
}

// Helpers for UI rendering
const getStatIcon = (index) => {
  const icons = [Users, Clock, CheckCircle, DollarSign]
  return icons[index] || Users
}

const getStatIconClass = (index) => {
  const classes = ['icon-blue', 'icon-amber', 'icon-emerald', 'icon-indigo']
  return classes[index] || 'icon-blue'
}

const getCommissionStatusClass = (status) => {
  if (status === 'pending') return 'status-warning'
  if (status === 'approved') return 'status-success'
  if (status === 'processing') return 'status-warning'
  if (status === 'paid') return 'status-info'
  if (status === 'rejected') return 'status-danger'
  return ''
}

const getCommissionStatusLabel = (status) => {
  if (status === 'pending') return 'Chờ duyệt'
  if (status === 'approved') return 'Đã duyệt'
  if (status === 'paid') return 'Đã thanh toán'
  return status
}

const getWithdrawStatusClass = (status) => {
  if (status === 'pending') return 'status-warning'
  if (status === 'approved') return 'status-success'
  if (status === 'paid') return 'status-info'
  return ''
}

const getWithdrawStatusLabel = (status) => {
  if (status === 'pending') return 'Chờ duyệt'
  if (status === 'approved') return 'Đã duyệt, chờ chi'
  if (status === 'processing') return 'Đang chuyển tiền'
  if (status === 'paid') return 'Đã chuyển tiền'
  if (status === 'rejected') return 'Đã từ chối'
  return status
}

const getVideoStatusClass = (status) => {
  if (status === 'pending') return 'status-warning'
  if (status === 'approved') return 'status-success'
  if (status === 'rejected') return 'status-danger'
  if (status === 'hidden') return 'status-info'
  return ''
}

const getVideoStatusLabel = (status) => {
  if (status === 'pending') return 'Chờ duyệt'
  if (status === 'approved') return 'Đã duyệt'
  if (status === 'rejected') return 'Từ chối'
  if (status === 'hidden') return 'Đã ẩn'
  return status || '-'
}

onMounted(loadAll)
</script>

<template>
  <div class="affiliate-page">
    <!-- Header Banner -->
    <div class="heading-banner shadow-sm">
      <div class="heading-content">
        <span class="badge-tag">Chương Trình Đối Tác</span>
        <h1>Affiliate Center</h1>
        <p>Kiếm tiền thụ động không giới hạn bằng việc tiếp thị sản phẩm của NextGen tới cộng đồng của bạn.</p>
      </div>
      <div class="heading-avatar" aria-label="Ảnh đại diện cộng tác viên">
        <img
          v-if="affiliateAvatarSrc"
          :src="affiliateAvatarSrc"
          :alt="affiliateUserName"
          @error="handleAffiliateAvatarError"
        />
        <span v-else>{{ affiliateInitial }}</span>
      </div>
    </div>

    <!-- Main Container -->
    <div class="container-body">
      <!-- Loading State -->
      <div v-if="loading" class="card card-loading">
        <div class="spinner-box">
          <div class="double-bounce1"></div>
          <div class="double-bounce2"></div>
        </div>
        <p>Đang tải dữ liệu, vui lòng đợi...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="card card-error-status">
        <AlertCircle class="error-status-icon" />
        <h3>Đã xảy ra sự cố</h3>
        <p>{{ error }}</p>
        <button class="btn btn-primary" @click="loadAll">Tải lại trang</button>
      </div>

      <!-- Active Status Check -->
      <template v-else>
        <!-- Non-activated State -->
        <div class="card activation-card shadow-sm" v-if="!data.active">
          <div class="activation-icon-box">
            <Award class="award-icon" />
          </div>
          <h3>Đăng Ký Cộng Tác Viên Tiếp Thị</h3>
          <p class="activation-desc">
            Nhận mức chia sẻ hoa hồng ưu đãi trọn đời lên tới <strong>{{ data.profile?.commission_rate || 5 }}%</strong> cho mỗi đơn hàng phát sinh thành công từ mạng lưới tiếp thị của bạn.
          </p>
          <button class="btn btn-primary btn-lg" :disabled="activating || data.profile?.status === 'pending'" @click="activate">
            <Award class="icon-inline" v-if="!activating" />
            <span>{{ data.profile?.status === 'pending' ? 'Đang chờ quản trị viên duyệt' : (activating ? 'Đang gửi đăng ký...' : 'Đăng ký làm affiliate') }}</span>
          </button>
        </div>

        <!-- Activated / Dashboard State -->
        <div class="dashboard-container" v-else>
          <!-- Navigation Tabs Bar -->
          <div class="dashboard-tabs">
            <button :class="['tab-btn', { active: activeTab === 'overview' }]" @click="activeTab = 'overview'">
              <TrendingUp class="tab-icon" />
              <span>Tổng quan</span>
            </button>
            <button :class="['tab-btn', { active: activeTab === 'referrals' }]" @click="activeTab = 'referrals'">
              <Users class="tab-icon" />
              <span>Thành viên ({{ referrals.length }})</span>
            </button>
            <button :class="['tab-btn', { active: activeTab === 'commissions' }]" @click="activeTab = 'commissions'">
              <DollarSign class="tab-icon" />
              <span>Hoa hồng ({{ commissions.length }})</span>
            </button>
            <button :class="['tab-btn', { active: activeTab === 'withdraw' }]" @click="activeTab = 'withdraw'">
              <CreditCard class="tab-icon" />
              <span>Rút tiền</span>
            </button>
            <button :class="['tab-btn', { active: activeTab === 'videos' }]" @click="activeTab = 'videos'">
              <Video class="tab-icon" />
              <span>Video Affiliate ({{ affiliateVideos.length }})</span>
            </button>
          </div>

          <!-- Tab Panels Container -->
          <div class="tab-content-panel shadow-sm">
            <!-- TAB 1: OVERVIEW -->
            <div v-if="activeTab === 'overview'" class="tab-pane fade-in">
              <div class="welcome-row">
                <div class="welcome-meta">
                  <h2>Chào mừng trở lại, {{ data.profile?.name || 'Cộng tác viên' }}!</h2>
                  <p>Hãy theo dõi liên kết giới thiệu và trạng thái tài chính của bạn tại đây.</p>
                </div>
                <div class="code-badges">
                  <div class="info-badge">
                    <span class="info-badge-label">Mã CTV:</span>
                    <span class="info-badge-value">{{ data.profile?.affiliate_code }}</span>
                  </div>
                  <div class="info-badge highlight">
                    <span class="info-badge-label">Hoa hồng:</span>
                    <span class="info-badge-value">{{ data.profile?.commission_rate }}%</span>
                  </div>
                </div>
              </div>

              <!-- Link Copy Block -->
              <div class="link-sharing-card">
                <div class="link-card-body">
                  <div class="link-info-text">
                    <h4>Mã liên kết giới thiệu của bạn</h4>
                    <p>Chia sẻ đường dẫn này cho mọi người. Khi họ đăng ký tài khoản qua liên kết này, bạn sẽ nhận được hoa hồng từ mỗi đơn hàng mua thành công.</p>
                  </div>
                  <div class="link-copy-box">
                    <input class="link-input" :value="data.ref_link" readonly />
                    <button :class="['btn btn-copy', { 'copied': copied }]" @click="copyLink">
                      <component :is="copied ? Check : Copy" class="btn-icon" />
                      <span>{{ copied ? 'Đã sao chép' : 'Sao chép link' }}</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Product Affiliate Link Generator -->
              <div class="link-sharing-card product-generator-card" style="margin-top: 20px;">
                <div class="link-card-body">
                  <div class="link-info-text">
                    <h4>Công cụ tạo Link Tiếp Thị Sản Phẩm</h4>
                    <p>Chia sẻ trực tiếp bất kỳ trang sản phẩm hoặc trang nội dung nào. Khi khách hàng click vào link tiếp thị này, họ sẽ được dẫn thẳng đến trang đó để xem hoặc mua hàng, đồng thời hệ thống vẫn tự động ghi nhận hoa hồng cho bạn.</p>
                  </div>
                  
                  <div class="generator-container">
                    <div class="generator-row">
                      <div class="generator-col">
                        <label class="generator-label">Cách 1: Chọn sản phẩm từ danh sách</label>
                        <select class="generator-select" v-model="selectedProductId" @change="onProductSelectChange">
                          <option value="">-- Chọn sản phẩm tiếp thị --</option>
                          <option v-for="prod in shopProducts" :key="prod.id_sanpham" :value="prod.id_sanpham">
                            {{ prod.tenSP }}
                          </option>
                        </select>
                      </div>
                      
                      <div class="generator-col">
                        <label class="generator-label">Cách 2: Dán đường dẫn trang web bất kỳ</label>
                        <input class="generator-input" v-model="customLinkInput" placeholder="Ví dụ: /products/12 hoặc https://tenmien.com/products" @input="generateCustomLink" />
                      </div>
                    </div>

                    <!-- Generated Result -->
                    <div class="generated-box-wrapper" v-if="generatedLink">
                      <span class="result-badge">Đường dẫn tiếp thị của bạn (Đã tích hợp mã CTV):</span>
                      <div class="link-copy-box generated-copy-box">
                        <input class="link-input generated-input" :value="generatedLink" readonly />
                        <button :class="['btn btn-copy', { 'copied': genCopied }]" @click="copyGeneratedLink">
                          <component :is="genCopied ? Check : Copy" class="btn-icon" />
                          <span>{{ genCopied ? 'Đã sao chép' : 'Sao chép link' }}</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Stats Dashboard Grid -->
              <div class="stats-grid">
                <div class="stat-card shadow-xs" v-for="(item, index) in summaryCards" :key="index">
                  <div :class="['stat-icon-wrapper', getStatIconClass(index)]">
                    <component :is="getStatIcon(index)" class="stat-icon" />
                  </div>
                  <div class="stat-details">
                    <div class="stat-label">{{ item.label }}</div>
                    <div class="stat-value">{{ item.value }}</div>
                  </div>
                </div>
              </div>

              <!-- General Info notice card -->
              <div class="notice-info-card">
                <Info class="notice-icon" />
                <div class="notice-text">
                  <strong>Chu kỳ duyệt hoa hồng:</strong> Giao dịch hoa hồng phát sinh sẽ hiển thị ở trạng thái <strong>Chờ duyệt</strong>. Sau khi đơn hàng hoàn thành thành công và qua 7 ngày đổi trả, số dư sẽ chuyển thành <strong>Đã duyệt (Khả dụng)</strong> để bạn có thể rút về tài khoản ngân hàng.
                </div>
              </div>
            </div>

            <!-- TAB 2: REFERRALS LIST -->
            <div v-else-if="activeTab === 'referrals'" class="tab-pane fade-in">
              <div class="section-header">
                <h3>Thành viên được bạn giới thiệu</h3>
                <p>Tổng quan danh sách những khách hàng đăng ký tài khoản thành công qua liên kết giới thiệu của bạn.</p>
              </div>
              <div class="table-container">
                <table class="modern-table">
                  <thead>
                    <tr>
                      <th>Tên thành viên</th>
                      <th>Email</th>
                      <th>Ngày tham gia</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="r in referrals" :key="r.id">
                      <td class="font-semibold">{{ r.referred_user?.name || '-' }}</td>
                      <td class="text-muted">{{ r.referred_user?.email || '-' }}</td>
                      <td>{{ r.registered_at ? new Date(r.registered_at).toLocaleString('vi-VN') : '-' }}</td>
                    </tr>
                    <tr v-if="referrals.length === 0">
                      <td colspan="3" class="table-empty">
                        <Users class="empty-icon" />
                        <p>Chưa có thành viên nào đăng ký qua liên kết giới thiệu của bạn.</p>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- TAB 3: COMMISSION HISTORY -->
            <div v-else-if="activeTab === 'commissions'" class="tab-pane fade-in">
              <div class="section-header">
                <h3>Lịch sử hoa hồng</h3>
                <p>Chi tiết các khoản tiền thưởng tích lũy từ các hóa đơn thanh toán của người được giới thiệu.</p>
              </div>
              <div class="table-container">
                <table class="modern-table">
                  <thead>
                    <tr>
                      <th>Đơn hàng</th>
                      <th>Khách hàng</th>
                      <th>Số tiền hoa hồng</th>
                      <th>Trạng thái</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="c in commissions" :key="c.id">
                      <td class="order-code">#{{ c.order_id }}</td>
                      <td class="font-semibold">{{ c.referred_user?.name || '-' }}</td>
                      <td class="commission-amount">{{ formatMoney(c.amount) }}</td>
                      <td>
                        <span :class="['badge-status', getCommissionStatusClass(c.status)]">
                          {{ getCommissionStatusLabel(c.status) }}
                        </span>
                      </td>
                    </tr>
                    <tr v-if="commissions.length === 0">
                      <td colspan="4" class="table-empty">
                        <DollarSign class="empty-icon" />
                        <p>Hiện tại bạn chưa phát sinh bất kỳ khoản hoa hồng nào.</p>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- TAB 4: AFFILIATE VIDEOS -->
            <div v-else-if="activeTab === 'videos'" class="tab-pane fade-in">
              <div class="section-header">
                <h3>Video Affiliate</h3>
                <p>Đăng video review, unbox hoặc hướng dẫn chọn laptop. Admin duyệt xong video sẽ được hiển thị ngoài trang chủ kèm link tiếp thị của bạn.</p>
              </div>

              <div class="affiliate-video-layout">
                <div class="video-submit-card">
                  <div class="video-submit-head">
                    <UploadCloud />
                    <div>
                      <h4>{{ editingAffiliateVideoId ? 'Sửa video affiliate' : 'Gửi video mới' }}</h4>
                      <p>{{ editingAffiliateVideoId ? 'Cập nhật nội dung sẽ chuyển video về trạng thái chờ admin duyệt lại.' : 'Hỗ trợ MP4/WebM/MOV tối đa 50MB hoặc link YouTube/TikTok.' }}</p>
                    </div>
                  </div>

                  <div class="video-form-grid">
                    <label class="input-group">
                      <span>Tiêu đề video *</span>
                      <input v-model="videoForm.title" placeholder="VD: Test ASUS TUF F16 sau 7 ngày sử dụng" />
                    </label>

                    <label class="input-group">
                      <span>Sản phẩm gắn kèm</span>
                      <select v-model="videoForm.product_id">
                        <option value="">-- Chọn sản phẩm muốn tiếp thị --</option>
                        <option v-for="prod in shopProducts" :key="prod.id_sanpham" :value="prod.id_sanpham">
                          {{ prod.tenSP }}
                        </option>
                      </select>
                    </label>

                    <label class="input-group full">
                      <span>Mô tả ngắn</span>
                      <textarea v-model="videoForm.description" rows="3" placeholder="Nêu điểm mạnh, trải nghiệm thực tế hoặc lý do khách nên xem sản phẩm này..."></textarea>
                    </label>

                    <label class="input-group">
                      <span>Tải video lên</span>
                      <input type="file" accept="video/mp4,video/webm,video/quicktime,video/*" @change="handleAffiliateVideoFile" />
                    </label>

                    <label class="input-group">
                      <span>Hoặc dán link video</span>
                      <input v-model="videoForm.video_url" placeholder="Https://youtube.com/shorts/..." />
                    </label>

                    <label class="input-group">
                      <span>Ảnh thumbnail</span>
                      <input type="file" accept="image/*" @change="handleAffiliateThumbnailFile" />
                    </label>
                  </div>

                  <div v-if="videoPreviewUrl || thumbnailPreviewUrl || selectedVideoProduct" class="video-preview-box">
                    <video v-if="videoPreviewUrl" :src="videoPreviewUrl" controls></video>
                    <img v-else-if="thumbnailPreviewUrl" :src="thumbnailPreviewUrl" alt="Thumbnail preview" />
                    <div class="preview-meta">
                      <strong>{{ videoForm.title || 'Video affiliate mới' }}</strong>
                      <span v-if="selectedVideoProduct">Sản phẩm: {{ selectedVideoProduct.tenSP }}</span>
                      <span>Trạng thái sau khi gửi: Chờ admin duyệt</span>
                    </div>
                  </div>

                  <div class="video-form-actions">
                    <button class="btn btn-primary video-submit-btn" :class="{ loading: videoSubmitting }" type="button" :disabled="videoSubmitting" @click="submitAffiliateVideo">
                      <span v-if="videoSubmitting" class="btn-loading-spinner" aria-hidden="true"></span>
                      <UploadCloud class="icon-inline" v-if="!videoSubmitting" />
                      <span>{{ videoSubmitting ? (editingAffiliateVideoId ? 'Đang cập nhật...' : 'Đang gửi video...') : (editingAffiliateVideoId ? 'Cập nhật video' : 'Gửi video duyệt') }}</span>
                    </button>
                    <button class="btn btn-light" type="button" :disabled="videoSubmitting" @click="resetAffiliateVideoForm">{{ editingAffiliateVideoId ? 'Hủy sửa' : 'Làm mới' }}</button>
                  </div>
                </div>

                <div class="video-list-card">
                  <div class="section-header border-none">
                    <h3>Video đã gửi</h3>
                    <p>Theo dõi trạng thái duyệt và hiệu suất video affiliate của bạn.</p>
                  </div>

                  <div class="affiliate-video-list">
                    <article
                      v-for="video in affiliateVideos"
                      :key="video.id"
                      class="affiliate-video-row"
                      @mouseenter="playInlineVideo"
                      @mouseleave="pauseInlineVideo"
                    >
                      <div class="affiliate-video-thumb">
                        <video
                          v-if="isPlayableVideoSrc(video.video_src)"
                          :src="storageUrl(video.video_src)"
                          :poster="storageUrl(video.thumbnail_src)"
                          muted
                          playsinline
                          loop
                          preload="metadata"
                        ></video>
                        <img v-else-if="video.thumbnail_src" :src="storageUrl(video.thumbnail_src)" alt="" />
                        <Video v-else />
                      </div>
                      <div class="affiliate-video-info">
                        <div class="video-row-title">
                          <strong>{{ video.title || video.tieu_de }}</strong>
                          <span :class="['badge-status', getVideoStatusClass(video.status)]">
                            {{ getVideoStatusLabel(video.status) }}
                          </span>
                        </div>
                        <p>{{ video.description || video.mo_ta || 'Chưa có mô tả.' }}</p>
                        <small>
                          {{ video.product?.tenSP || 'Chưa gắn sản phẩm' }} · {{ video.views || 0 }} lượt xem · {{ video.clicks || 0 }} click
                        </small>
                        <small v-if="video.reject_reason" class="reject-note">Lý do từ chối: {{ video.reject_reason }}</small>
                      </div>
                      <div class="video-row-actions">
                        <button class="video-action-btn edit" type="button" title="Sửa video" @click="editAffiliateVideo(video)">
                          <Pencil :size="16" />
                        </button>
                        <button class="video-action-btn delete" type="button" title="Xóa video" @click="deleteAffiliateVideo(video)">
                          <Trash2 :size="16" />
                        </button>
                      </div>
                    </article>

                    <div v-if="affiliateVideos.length === 0" class="table-empty video-empty">
                      <Video class="empty-icon" />
                      <p>Bạn chưa gửi video affiliate nào.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- TAB 5: WITHDRAW & HISTORY -->
            <div v-else-if="activeTab === 'withdraw'" class="tab-pane fade-in">
              <div class="withdraw-dashboard">
                <!-- Request Form Card -->
                <div class="withdraw-form-card">
                  <div class="withdraw-balance-box">
                    <div class="balance-meta">
                      <span class="balance-label">Số dư khả dụng để rút:</span>
                      <h2 class="balance-val">{{ formatMoney(data.stats?.available_balance || 0) }}</h2>
                    </div>
                    <Wallet class="balance-icon" />
                  </div>

                  <div class="withdraw-inputs">
                    <div class="input-group">
                      <label>Số tiền rút (VNĐ) <span class="required">*</span></label>
                      <div class="input-wrapper">
                        <DollarSign class="input-icon" />
                        <input v-model="withdrawForm.amount" type="number" :min="data.rules?.minimum_withdrawal || 100000" placeholder="Số tiền rút (tối thiểu 100.000đ)" />
                      </div>
                    </div>

                    <div class="input-group">
                      <label>Tên Ngân hàng <span class="required">*</span></label>
                      <div class="bank-select" :class="{ open: bankDropdownOpen }" @focusout="closeBankDropdown">
                        <button
                          type="button"
                          class="bank-select-trigger"
                          :aria-expanded="bankDropdownOpen"
                          @click="bankDropdownOpen = !bankDropdownOpen"
                        >
                          <Building2 class="bank-trigger-icon" />
                          <span :class="{ placeholder: !withdrawForm.bank_name }">
                            {{ withdrawForm.bank_name || 'Chọn ngân hàng nhận tiền' }}
                          </span>
                          <ChevronDown class="bank-chevron" />
                        </button>

                        <div v-if="bankDropdownOpen" class="bank-dropdown-panel">
                          <div class="bank-search-box">
                            <Search />
                            <input v-model="bankSearch" autofocus placeholder="Tìm tên ngân hàng..." @keydown.esc="bankDropdownOpen = false" />
                          </div>
                          <div class="bank-options" role="listbox">
                            <button
                              v-for="bank in filteredBanks"
                              :key="bank"
                              type="button"
                              class="bank-option"
                              :class="{ selected: withdrawForm.bank_name === bank }"
                              @mousedown.prevent="selectBank(bank)"
                              @keydown.enter.prevent="selectBank(bank)"
                              @keydown.space.prevent="selectBank(bank)"
                            >
                              <span class="bank-option-logo">{{ bank.charAt(0) }}</span>
                              <span>{{ bank }}</span>
                              <Check v-if="withdrawForm.bank_name === bank" class="bank-option-check" />
                            </button>
                            <div v-if="filteredBanks.length === 0" class="bank-empty">Không tìm thấy ngân hàng phù hợp</div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="input-group">
                      <label>Tên Chủ tài khoản <span class="required">*</span></label>
                      <input v-model="withdrawForm.bank_account_name" placeholder="Ví dụ: NGUYEN VAN A" />
                    </div>

                    <div class="input-group">
                      <label>Số tài khoản <span class="required">*</span></label>
              <input v-model="withdrawForm.bank_account_number" placeholder="Nhập chính xác số tài khoản ngân hàng" />
                    </div>
                  </div>

                  <button class="btn btn-primary btn-block btn-lg" :disabled="withdrawLoading" @click="submitWithdraw">
                    <CreditCard class="icon-inline" v-if="!withdrawLoading" />
                    <span>{{ withdrawLoading ? 'Đang gửi yêu cầu...' : 'Gửi yêu cầu rút tiền' }}</span>
                  </button>
                </div>

                <!-- Withdraw requests History table -->
                <div class="withdraw-history-box">
                  <div class="withdraw-history-header">
                    <div class="history-heading-icon"><History /></div>
                    <div class="history-heading-copy">
                      <h3>Yêu cầu rút tiền của bạn</h3>
                      <p>Theo dõi quá trình phê duyệt và chi trả tiền hoa hồng tiếp thị liên kết.</p>
                    </div>
                    <span class="history-count">{{ withdraws.length }} yêu cầu</span>
                  </div>
                  <div class="table-container">
                    <table class="modern-table">
                      <thead>
                        <tr>
                          <th>Số tiền</th>
                          <th>Tài khoản thụ hưởng</th>
                          <th>Trạng thái</th>
                          <th>Ngày tạo</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="w in withdraws" :key="w.id">
                          <td class="font-bold text-dark">
                            {{ formatMoney(w.amount) }}
                            <small class="d-block">{{ w.request_code || `AFF-${w.id}` }}</small>
                          </td>
                          <td>
                            <div class="bank-meta-text">
                              <strong>{{ w.bank_name }}</strong>
                              <span>{{ w.bank_account_number }} - {{ w.bank_account_name }}</span>
                            </div>
                          </td>
                          <td>
                            <span :class="['badge-status', getWithdrawStatusClass(w.status)]">
                              {{ getWithdrawStatusLabel(w.status) }}
                            </span>
                            <small v-if="w.transaction_id" class="d-block">GD: {{ w.transaction_id }}</small>
                          </td>
                          <td>{{ new Date(w.created_at).toLocaleString('vi-VN') }}</td>
                        </tr>
                        <tr v-if="withdraws.length === 0">
                          <td colspan="4" class="table-empty">
                            <div class="withdraw-empty-state">
                              <div class="withdraw-empty-icon"><History /></div>
                              <h4>Chưa có yêu cầu rút tiền</h4>
                              <p>Các yêu cầu bạn gửi sẽ xuất hiện tại đây để tiện theo dõi trạng thái xử lý.</p>
                              <span>Hãy điền thông tin bên cạnh để tạo yêu cầu đầu tiên.</span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.affiliate-page {
  font-family: 'Inter', sans-serif;
  width: 100%;
  margin: 0;
  padding: 0;
  color: #0f172a;
  background: #ffffff;
}

/* Heading Banner */
.heading-banner {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 32px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-left: none;
  border-right: none;
  border-radius: 0;
  padding: 32px max(32px, calc((100vw - 1240px) / 2));
  overflow: hidden;
  color: #0f172a;
  margin: 0 0 24px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
}
.heading-avatar {
  width: clamp(120px, 11vw, 160px);
  height: clamp(120px, 11vw, 160px);
  border-radius: 26px;
  background: #eff6ff;
  border: 2px solid #bfdbfe;
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.12);
  display: grid;
  place-items: center;
  color: #2563eb;
  font-size: clamp(40px, 5vw, 68px);
  font-weight: 800;
  overflow: hidden;
  flex: 0 0 auto;
  z-index: 1;
}
.heading-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  border-radius: 22px;
  display: block;
}
.heading-content {
  position: relative;
  z-index: 2;
  max-width: 700px;
}
.badge-tag {
  display: inline-block;
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: capitalize;
  padding: 6px 16px;
  border-radius: 99px;
  margin-bottom: 16px;
}
.heading-content h1 {
  font-size: 36px;
  font-weight: 800;
  margin: 0 0 10px;
  letter-spacing: -0.5px;
  color: #0f172a;
}
.heading-content p {
  font-size: 16px;
  color: #475569;
  margin: 0;
  line-height: 1.5;
}

/* General Layout Elements */
.container-body {
  min-height: 350px;
  max-width: 1240px;
  margin: 0 auto;
  padding: 0 24px 48px;
}

.card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
}

/* Loading status */
.card-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #64748b;
  gap: 16px;
}
.spinner-box {
  width: 40px;
  height: 40px;
  position: relative;
}
.double-bounce1, .double-bounce2 {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background-color: #3b82f6;
  opacity: 0.6;
  position: absolute;
  top: 0;
  left: 0;
  animation: sk-bounce 2.0s infinite ease-in-out;
}
.double-bounce2 {
  animation-delay: -1.0s;
}
@keyframes sk-bounce {
  0%, 100% { transform: scale(0.0) }
  50% { transform: scale(1.0) }
}

/* Error status */
.card-error-status {
  text-align: center;
  padding: 50px 30px;
}
.error-status-icon {
  width: 54px;
  height: 54px;
  color: #ef4444;
  margin-bottom: 16px;
}
.card-error-status h3 {
  font-size: 20px;
  margin: 0 0 8px;
}
.card-error-status p {
  color: #64748b;
  font-size: 14px;
  margin: 0 0 24px;
}

/* Activation Card */
.activation-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 48px 34px;
  max-width: 650px;
  margin: 20px auto;
}
.activation-icon-box {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #eff6ff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
}
.award-icon {
  width: 36px;
  height: 36px;
}
.activation-card h3 {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 12px;
  color: #0f172a;
}
.activation-desc {
  font-size: 15px;
  color: #475569;
  line-height: 1.6;
  margin-bottom: 32px;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  padding: 10px 20px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-primary {
  background: #2563eb;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}
.btn-primary:hover {
  opacity: 0.95;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}
.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
.btn-primary.loading {
  position: relative;
  overflow: hidden;
  opacity: 1;
  background: #2563eb;
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.24);
}
.btn-primary.loading::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transform: translateX(-100%);
  animation: uploadButtonShine 1.35s ease-in-out infinite;
}
.btn-primary.loading > * {
  position: relative;
  z-index: 1;
}
.btn-loading-spinner {
  width: 17px;
  height: 17px;
  border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.45);
  border-top-color: #ffffff;
  flex: 0 0 auto;
  animation: uploadSpinner 0.75s linear infinite;
}
@keyframes uploadSpinner {
  to { transform: rotate(360deg); }
}
@keyframes uploadButtonShine {
  to { transform: translateX(100%); }
}
.btn-light {
  background: #f8fafc;
  color: #475569;
  border: 1px solid #cbd5e1;
}
.btn-light:hover {
  background: #eef6ff;
  color: #2563eb;
}
.btn-lg {
  padding: 14px 28px;
  font-size: 15px;
  border-radius: 12px;
}
.icon-inline {
  width: 18px;
  height: 18px;
}

/* Dashboard Container */
.dashboard-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Navigation Tabs */
.dashboard-tabs {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 6px;
  background: #ffffff;
  padding: 6px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
}
.tab-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  background: transparent;
  border: none;
  border-radius: 10px;
  padding: 12px 8px;
  font-size: 13.5px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}
.tab-btn:hover {
  color: #2563eb;
  background: #f8fbff;
}
.tab-btn.active {
  background: #eff6ff;
  color: #2563eb;
  box-shadow: none;
}
.tab-icon {
  width: 18px;
  height: 18px;
  stroke-width: 2.2;
}

/* Tab Panel */
.tab-content-panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px;
  min-height: 300px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
}

/* Tab 1: Overview Panel styling */
.welcome-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}
.welcome-meta h2 {
  font-size: 22px;
  font-weight: 700;
  margin: 0 0 6px;
  color: #0f172a;
}
.welcome-meta p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}
.code-badges {
  display: flex;
  gap: 10px;
}
.info-badge {
  background: #f8fbff;
  border: 1px solid #dbeafe;
  border-radius: 10px;
  padding: 8px 14px;
  display: flex;
  flex-direction: column;
  min-width: 100px;
}
.info-badge.highlight {
  background: #eff6ff;
  border-color: #bfdbfe;
}
.info-badge-label {
  font-size: 10px;
  text-transform: capitalize;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 2px;
}
.info-badge.highlight .info-badge-label {
  color: #2563eb;
}
.info-badge-value {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}
.info-badge.highlight .info-badge-value {
  color: #1e3a8a;
}

/* Link Sharing Card */
.link-sharing-card {
  background: #f8fbff;
  border: 1px solid #dbeafe;
  border-radius: 14px;
  padding: 20px;
  margin-bottom: 28px;
}
.link-info-text h4 {
  font-size: 15px;
  font-weight: 700;
  margin: 0 0 4px;
  color: #0f172a;
}
.link-info-text p {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 16px;
  line-height: 1.4;
}
.link-copy-box {
  display: flex;
  gap: 8px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 6px;
  overflow: hidden;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
.link-input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 8px 10px;
  font-size: 13.5px;
  color: #0f172a;
  outline: none;
  font-family: monospace;
}
.btn-copy {
  flex-shrink: 0;
  padding: 8px 16px;
  border-radius: 8px;
  background: #0f172a;
  color: #ffffff;
}
.btn-copy:hover {
  background: #1e293b;
}
.btn-copy.copied {
  background: #2563eb;
}
.btn-icon {
  width: 14px;
  height: 14px;
}

/* Stats dashboard cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
.stat-card {
  background: #ffffff;
  border: 1px solid #dbeafe;
  border-radius: 14px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01), 0 2px 4px -1px rgba(0,0,0,0.01);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04), 0 4px 6px -2px rgba(0,0,0,0.01);
}
.stat-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-icon {
  width: 20px;
  height: 20px;
}
.icon-blue { background: #eff6ff; color: #2563eb; }
.icon-amber { background: #fffbeb; color: #d97706; }
.icon-emerald { background: #ecfdf5; color: #1D4ED8; }
.icon-indigo { background: #eef2ff; color: #2563eb; }

.stat-details {
  min-width: 0;
}
.stat-label {
  font-size: 12px;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.stat-value {
  font-size: 16px;
  font-weight: 700;
  margin-top: 2px;
  color: #0f172a;
}

/* Notice Panel */
.notice-info-card {
  display: flex;
  gap: 12px;
  background: #f8fbff;
  border-left: 4px solid #3b82f6;
  border-top: 1px solid #dbeafe;
  border-right: 1px solid #dbeafe;
  border-bottom: 1px solid #dbeafe;
  border-radius: 10px;
  padding: 16px;
  margin-top: 20px;
}
.notice-icon {
  width: 20px;
  height: 20px;
  color: #3b82f6;
  flex-shrink: 0;
}
.notice-text {
  font-size: 13px;
  color: #475569;
  line-height: 1.5;
}

.affiliate-video-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(340px, 0.95fr);
  gap: 22px;
  align-items: start;
}
.video-submit-card,
.video-list-card {
  background: #ffffff;
  border: 1px solid #dbeafe;
  border-radius: 20px;
  padding: 22px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}
.video-submit-head {
  display: flex;
  gap: 14px;
  align-items: flex-start;
  margin-bottom: 18px;
}
.video-submit-head > svg {
  width: 42px;
  height: 42px;
  padding: 10px;
  border-radius: 14px;
  color: #2563eb;
  background: #eff6ff;
}
.video-submit-head h4 {
  margin: 0 0 5px;
  color: #0f172a;
  font-size: 18px;
  font-weight: 800;
}
.video-submit-head p,
.video-list-card .section-header p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
}
.video-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}
.video-form-grid .full {
  grid-column: 1 / -1;
}
.video-form-grid .input-group:has(input[accept*="video/"]) {
  display: none;
}
.video-form-grid .input-group:has(input[placeholder*="youtube.com"]) {
  grid-column: 1 / -1;
}
.video-form-grid textarea {
  resize: vertical;
  min-height: 88px;
}
.video-form-grid .input-group > span {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}
.video-form-grid input,
.video-form-grid select,
.video-form-grid textarea {
  width: 100% !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 10px !important;
  background: #ffffff !important;
  color: #0f172a !important;
  padding: 10px 12px !important;
  font-size: 13.5px !important;
  outline: none !important;
}
.video-form-grid input:focus,
.video-form-grid select:focus,
.video-form-grid textarea:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
}
.video-preview-box {
  margin-top: 16px;
  display: grid;
  grid-template-columns: 160px minmax(0, 1fr);
  gap: 14px;
  align-items: center;
  padding: 12px;
  border: 1px solid #bfdbfe;
  border-radius: 16px;
  background: #f8fbff;
}
.video-preview-box video,
.video-preview-box img {
  width: 160px;
  height: 96px;
  object-fit: cover;
  border-radius: 12px;
  background: #0f172a;
}
.preview-meta {
  display: flex;
  flex-direction: column;
  gap: 5px;
  color: #64748b;
  font-size: 12.5px;
}
.preview-meta strong {
  color: #0f172a;
  font-size: 14px;
}
.video-form-actions {
  display: flex;
  gap: 12px;
  margin-top: 18px;
  flex-wrap: wrap;
}
.affiliate-video-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  height: 520px;
  max-height: 520px;
  overflow-y: scroll;
  overscroll-behavior: contain;
  padding-right: 8px;
  scrollbar-width: thin;
  scrollbar-color: #94a3b8 #f1f5f9;
}
.affiliate-video-list::-webkit-scrollbar {
  width: 8px;
}
.affiliate-video-list::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 999px;
}
.affiliate-video-list::-webkit-scrollbar-thumb {
  background: #94a3b8;
  border: 2px solid #f1f5f9;
  border-radius: 999px;
}
.affiliate-video-list::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}
.affiliate-video-row {
  display: grid;
  grid-template-columns: 94px minmax(0, 1fr) 38px;
  gap: 12px;
  align-items: center;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 10px;
  background: #f8fafc;
}
.affiliate-video-thumb {
  width: 94px;
  height: 118px;
  border-radius: 14px;
  overflow: hidden;
  display: grid;
  place-items: center;
  color: #2563eb;
  background: #eaf4ff;
}
.affiliate-video-thumb video,
.affiliate-video-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.affiliate-video-info {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.video-row-title {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: space-between;
}
.video-row-title strong {
  color: #0f172a;
  font-size: 14px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.affiliate-video-info p {
  margin: 0;
  color: #475569;
  font-size: 12.5px;
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.affiliate-video-info small {
  color: #64748b;
  font-size: 11.5px;
}
.affiliate-video-info .reject-note {
  color: #ef4444;
}
.video-row-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: center;
}
.video-action-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-grid;
  place-items: center;
  cursor: pointer;
}
.video-action-btn.edit {
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #2563eb;
}
.video-action-btn.delete {
  border: 1px solid #fecaca;
  background: #fff5f5;
  color: #ef4444;
}
.video-empty {
  min-height: 180px;
}

/* Section headers */
.section-header {
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e2e8f0;
}
.section-header h3 {
  font-size: 18px;
  font-weight: 700;
  margin: 0 0 4px;
  color: #0f172a;
}
.section-header p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}
.border-none {
  border-bottom: none;
  padding-bottom: 0;
}

/* Modern Tables styling */
.table-container {
  overflow-x: auto;
  border: 1px solid #dbeafe;
  border-radius: 12px;
}
.modern-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13.5px;
}
.modern-table th {
  background: #f8fbff;
  color: #64748b;
  font-weight: 600;
  padding: 14px 18px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 12.5px;
  text-transform: capitalize;
  letter-spacing: 0.5px;
}
.modern-table td {
  padding: 14px 18px;
  border-bottom: 1px solid #e2e8f0;
  color: #334155;
  vertical-align: middle;
}
.modern-table tr:last-child td {
  border-bottom: none;
}
.modern-table tbody tr:hover td {
  background: #f8fbff;
}
.font-semibold { font-weight: 600; }
.text-muted { color: #64748b; }
.order-code { font-family: monospace; font-weight: 700; color: #2563eb; }
.commission-amount { font-weight: 700; color: #2563eb; }

.table-empty {
  text-align: center;
  padding: 40px 20px !important;
  color: #64748b;
}
.empty-icon {
  width: 38px;
  height: 38px;
  margin-bottom: 12px;
  color: #cbd5e1;
}

/* Status Badges */
.badge-status {
  display: inline-flex;
  align-items: center;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 99px;
  line-height: 1;
}
.status-warning {
  background: #fef9c3;
  color: #a16207;
}
.status-success {
  background: #d1fae5;
  color: #065f46;
}
.status-info {
  background: #dbeafe;
  color: #1e40af;
}
.status-danger {
  background: #fee2e2;
  color: #991b1b;
}

/* Withdraw Layout Panels */
.withdraw-dashboard {
  display: grid;
  grid-template-columns: 1.1fr 1.9fr;
  gap: 24px;
  align-items: start;
}
.withdraw-form-card {
  background: #ffffff;
  border: 1px solid #dbeafe;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
}
.withdraw-history-box {
  overflow: hidden;
  border: 1px solid #dbeafe;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
}
.withdraw-history-header {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 20px 22px;
  border-bottom: 1px solid #e8eef7;
  background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
}
.history-heading-icon {
  display: grid;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  place-items: center;
  border-radius: 12px;
  background: #eaf2ff;
  color: #2563eb;
}
.history-heading-icon svg {
  width: 21px;
  height: 21px;
}
.history-heading-copy {
  min-width: 0;
  flex: 1;
}
.history-heading-copy h3 {
  margin: 0 0 4px;
  color: #0f172a;
  font-size: 18px;
  font-weight: 800;
}
.history-heading-copy p {
  margin: 0;
  color: #64748b;
  font-size: 12.5px;
  line-height: 1.5;
}
.history-count {
  flex: 0 0 auto;
  padding: 6px 10px;
  border: 1px solid #bfdbfe;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 11px;
  font-weight: 700;
}
.withdraw-history-box .table-container {
  border: 0;
  border-radius: 0;
}
.withdraw-history-box .modern-table th {
  padding-top: 15px;
  padding-bottom: 15px;
  background: #f8fafc;
  color: #475569;
  font-weight: 700;
  letter-spacing: 0;
  text-transform: none;
}
.withdraw-history-box .table-empty {
  padding: 0 !important;
  background: #fff !important;
}
.withdraw-empty-state {
  display: flex;
  min-height: 245px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 34px 24px;
  text-align: center;
}
.withdraw-empty-icon {
  display: grid;
  width: 62px;
  height: 62px;
  margin-bottom: 16px;
  place-items: center;
  border: 1px solid #dbeafe;
  border-radius: 20px;
  background: linear-gradient(145deg, #eff6ff, #fff);
  color: #3b82f6;
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.1);
}
.withdraw-empty-icon svg {
  width: 29px;
  height: 29px;
}
.withdraw-empty-state h4 {
  margin: 0 0 7px;
  color: #0f172a;
  font-size: 16px;
  font-weight: 800;
}
.withdraw-empty-state p {
  max-width: 430px;
  margin: 0 0 10px;
  color: #64748b;
  font-size: 13px;
  line-height: 1.6;
}
.withdraw-empty-state > span {
  color: #2563eb;
  font-size: 11.5px;
  font-weight: 650;
}
.withdraw-balance-box {
  background: #f8fbff;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  padding: 16px 20px;
  color: #0f172a;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.balance-label {
  font-size: 11px;
  color: #64748b;
  font-weight: 500;
}
.balance-val {
  font-size: 20px;
  font-weight: 700;
  margin: 4px 0 0;
  color: #2563eb;
}
.balance-icon {
  width: 26px;
  height: 26px;
  color: #3b82f6;
  opacity: 0.9;
}

.withdraw-inputs {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-bottom: 20px;
}
.input-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.input-group label {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}
.required {
  color: #ef4444;
}
.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}
.input-icon {
  position: absolute;
  left: 12px;
  width: 16px;
  height: 16px;
  color: #64748b;
}
.input-group input, .input-group select, .input-wrapper input {
  width: 100% !important;
  height: 42px !important;
  max-height: 42px !important;
  box-sizing: border-box !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 8px !important;
  padding: 9px 12px !important;
  font-size: 13.5px !important;
  background: #ffffff !important;
  color: #0f172a !important;
  outline: none !important;
  transition: all 0.2s ease !important;
}
.input-wrapper input {
  padding-left: 36px;
}
.input-group input:focus, .input-group select:focus, .input-wrapper input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.bank-select {
  position: relative;
  z-index: 20;
}
.bank-select.open {
  z-index: 50;
}
.bank-select-trigger {
  display: flex;
  width: 100%;
  height: 44px;
  align-items: center;
  gap: 10px;
  padding: 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #fff;
  color: #0f172a;
  font: inherit;
  text-align: left;
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.bank-select.open .bank-select-trigger {
  border-color: #2563eb;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.11);
}
.bank-select-trigger span {
  min-width: 0;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.bank-select-trigger .placeholder {
  color: #94a3b8;
}
.bank-trigger-icon,
.bank-chevron {
  width: 18px;
  height: 18px;
  flex: 0 0 auto;
  color: #64748b;
}
.bank-chevron {
  transition: transform 0.2s ease;
}
.bank-select.open .bank-chevron {
  transform: rotate(180deg);
  color: #2563eb;
}
.bank-dropdown-panel {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  left: 0;
  overflow: hidden;
  border: 1px solid #dbe4f0;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
  animation: bankDropdownIn 0.16s ease-out;
}
.bank-search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 10px;
  padding: 0 11px;
  border: 1px solid #dbe4f0;
  border-radius: 10px;
  background: #f8fafc;
}
.bank-search-box svg {
  width: 16px;
  color: #64748b;
}
.bank-search-box input {
  height: 38px !important;
  padding: 0 !important;
  border: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
}
.bank-options {
  max-height: 250px;
  overflow-y: auto;
  padding: 0 7px 8px;
  scrollbar-width: thin;
  scrollbar-color: #94a3b8 transparent;
}
.bank-option {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border: 0;
  border-radius: 9px;
  background: transparent;
  color: #334155;
  font: inherit;
  font-size: 13px;
  text-align: left;
  cursor: pointer;
}
.bank-option:hover {
  background: #eff6ff;
  color: #1d4ed8;
}
.bank-option.selected {
  background: #dbeafe;
  color: #1d4ed8;
  font-weight: 700;
}
.bank-option-logo {
  display: grid;
  width: 28px;
  height: 28px;
  flex: 0 0 28px;
  place-items: center;
  border-radius: 8px;
  background: linear-gradient(135deg, #2563eb, #60a5fa);
  color: #fff;
  font-size: 12px;
  font-weight: 800;
}
.bank-option-check {
  width: 16px;
  height: 16px;
  margin-left: auto;
  color: #2563eb;
}
.bank-empty {
  padding: 22px 12px;
  color: #94a3b8;
  font-size: 13px;
  text-align: center;
}
@keyframes bankDropdownIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
.btn-block {
  width: 100%;
}
.bank-meta-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.bank-meta-text span {
  font-size: 11px;
  color: #64748b;
}
.font-bold { font-weight: 700; }
.text-dark { color: #0f172a; }

/* Micro-animations */
.fade-in {
  animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Product Affiliate Link Generator styles */
.product-generator-card {
  border: 1px solid #dbeafe;
  background: #f8fbff !important;
}
.generator-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-top: 15px;
}
.generator-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}
.generator-col {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.generator-label {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}
.generator-select, .generator-input {
  width: 100% !important;
  height: 48px !important;
  max-height: 48px !important;
  padding: 12px 16px !important;
  box-sizing: border-box !important;
  border-radius: 12px !important;
  border: 1px solid #cbd5e1 !important;
  background: #ffffff !important;
  font-size: 14px !important;
  color: #0f172a !important;
  outline: none !important;
  transition: all 0.2s ease !important;
  line-height: normal !important;
}
.generator-select:focus, .generator-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.generated-box-wrapper {
  background: #eff6ff;
  border: 1px dashed #3b82f6;
  padding: 20px;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  animation: fadeIn 0.25s ease-out;
}
.result-badge {
  font-size: 13px;
  font-weight: 700;
  color: #1d4ed8;
}
.generated-copy-box {
  background: #ffffff !important;
}
.generated-input {
  color: #1d4ed8 !important;
  font-weight: 600;
}

/* Responsive breakdowns */
@media (max-width: 991px) {
  .withdraw-dashboard {
    grid-template-columns: 1fr;
  }
  .affiliate-video-layout {
    grid-template-columns: 1fr;
  }
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 767px) {
  .affiliate-video-list {
    height: 440px;
    max-height: 440px;
  }
  .heading-banner {
    align-items: flex-start;
    padding: 30px 20px;
    gap: 18px;
  }
  .heading-avatar {
    width: 82px;
    height: 82px;
    border-radius: 18px;
    font-size: 34px;
  }
  .heading-content h1 {
    font-size: 28px;
  }
  .heading-content p {
    font-size: 14px;
  }
  .dashboard-tabs {
    grid-template-columns: 1fr;
    gap: 4px;
  }
  .tab-btn {
    padding: 10px;
  }
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .welcome-row {
    flex-direction: column;
    align-items: stretch;
  }
  .code-badges {
    justify-content: space-between;
  }
  .generator-row {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  .video-form-grid,
  .video-preview-box {
    grid-template-columns: 1fr;
  }
  .video-preview-box video,
  .video-preview-box img {
    width: 100%;
  }
  .affiliate-video-row {
    grid-template-columns: 76px minmax(0, 1fr);
  }
  .video-row-actions {
    grid-column: 2;
    justify-self: start;
    flex-direction: row;
  }
}
</style>
