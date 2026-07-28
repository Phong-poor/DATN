<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import api from '@/services/api'
import { storageUrl } from '@/services/urls'
import swal from '@/services/swal'
import { registerOfflineForm } from '@/services/offlineSync'
import PhanTrangAdmin from './PhanTrangAdmin.vue'

// --- Tabs State ---
const activeTab = ref('combos') // 'combos' | 'offers'

// --- View State ---
// 'list' | 'combo-form' | 'offer-form'
const currentView = ref('list')

// --- State (Combos) ---
const combos = ref([])
const search = ref('')
const comboCurrentPage = ref(1)
const comboItemsPerPage = ref(5)
const isEditMode = ref(false)
const editingComboId = ref(null)
const productsPool = ref([]) // Toàn bộ sản phẩm phụ kiện để chọn
const productSearchQuery = ref('')

const defaultForm = () => ({
  ten_combo: '',
  giakhuyenmai: '',
  mota: '',
  hinhanh: '',
  trangthai: 1,
  product_ids: []
})

const form = ref(defaultForm())
registerOfflineForm(form, 'quan-ly-combo')
const imgPreview = ref('')
const fileInputRef = ref(null)
const fieldErrors = ref({})
const formError = ref('')
const isSubmitting = ref(false)

const isNameManuallyEdited = ref(false)
const isPriceManuallyEdited = ref(false)
const selectedPoolCategory = ref(null) // null = Tất cả

// --- State (Promotional Combo Offers) ---
const offers = ref([])
const offersSearch = ref('')
const isOfferEditMode = ref(false)
const editingOfferId = ref(null)

// Detail modal state
const selectedOfferDetail = ref(null)
const showDetailModal = ref(false)

const viewOfferDetail = (offer) => {
  selectedOfferDetail.value = offer
  showDetailModal.value = true
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedOfferDetail.value = null
}

const defaultOfferForm = () => ({
  id_bienthe: '',
  id_combo: '',
  loai_uudai: 'free',
  giakhuyenmai_override: '',
  mota_uudai: '',
  gioi_han_soluong: '',
  ngay_het_han: '',
  trangthai: 1
})

const offerForm = ref(defaultOfferForm())
const offerFieldErrors = ref({})
const offerFormError = ref('')
const isOfferSubmitting = ref(false)

const allProductsPool = ref([]) // Toàn bộ sản phẩm chính trong hệ thống
const selectedOfferProduct = ref(null) // ID sản phẩm chính đang chọn
const availableOfferVariants = ref([]) // Biến thể tương ứng của sản phẩm chính
const isVariantCollapsed = ref(false) // Trạng thái đóng/mở danh sách biến thể

const getConfigName = (name) => {
  if (!name) return ''
  const parts = name.split(' - ')
  if (parts.length > 1) {
    return parts.slice(0, -1).join(' - ')
  }
  return name
}

const groupedOfferVariants = computed(() => {
  const map = {}
  availableOfferVariants.value.forEach(v => {
    const configName = getConfigName(v.ten_bienthe)
    if (!map[configName]) {
      map[configName] = {
        configName: configName,
        id_bienthe: v.id_bienthe,
        gia: v.gia,
        ten_bienthe: v.ten_bienthe,
        allVariants: []
      }
    }
    map[configName].allVariants.push(v)
  })
  return Object.values(map)
})

const isVariantSelected = (groupedVar) => {
  if (!offerForm.value.id_bienthe) return false
  return groupedVar.allVariants.some(child => Number(child.id_bienthe) === Number(offerForm.value.id_bienthe))
}

// --- Stats ---
const totalCombos = computed(() => combos.value.length)
const activeCombos = computed(() => combos.value.filter(c => c.trangthai === 1).length)
const avgPrice = computed(() => {
  if (combos.value.length === 0) return 0
  const total = combos.value.reduce((sum, c) => sum + Number(c.giakhuyenmai || 0), 0)
  return Math.round(total / combos.value.length)
})

const totalOffers = computed(() => offers.value.length)
const activeOffers = computed(() => offers.value.filter(o => o.trangthai === 1).length)

// --- Filtering Pool of Products ---
const filteredProductsPool = computed(() => {
  let list = productsPool.value
  
  // 1. Lọc theo danh mục con đang chọn trên Tab
  if (selectedPoolCategory.value !== null) {
    list = list.filter(p => Number(p.id_danhmuc) === Number(selectedPoolCategory.value))
  }
  
  // 2. Lọc theo từ khóa tìm kiếm
  if (productSearchQuery.value.trim()) {
    const q = productSearchQuery.value.toLowerCase()
    list = list.filter(p => p.tenSP.toLowerCase().includes(q) || (p.SKU && p.SKU.toLowerCase().includes(q)))
  }
  
  // Tránh lag giao diện khi hiển thị quá nhiều, giới hạn 30 sản phẩm đầu
  return list.slice(0, 30)
})

// --- Filtered Combos List ---
const filteredCombos = computed(() => {
  if (!search.value.trim()) return combos.value
  const q = search.value.toLowerCase()
  return combos.value.filter(c => c.ten_combo.toLowerCase().includes(q) || (c.mota && c.mota.toLowerCase().includes(q)))
})

const comboTotalPages = computed(() => Math.max(1, Math.ceil(filteredCombos.value.length / comboItemsPerPage.value)))
const comboPageStart = computed(() => filteredCombos.value.length ? (comboCurrentPage.value - 1) * comboItemsPerPage.value + 1 : 0)
const comboPageEnd = computed(() => Math.min(comboCurrentPage.value * comboItemsPerPage.value, filteredCombos.value.length))
const paginatedCombos = computed(() => {
  const start = (comboCurrentPage.value - 1) * comboItemsPerPage.value
  return filteredCombos.value.slice(start, start + comboItemsPerPage.value)
})
const comboVisiblePages = computed(() => {
  const total = comboTotalPages.value
  const current = comboCurrentPage.value
  const from = Math.max(1, current - 2)
  const to = Math.min(total, from + 4)
  return Array.from({ length: to - from + 1 }, (_, index) => from + index)
})

const changeComboPage = (page) => {
  comboCurrentPage.value = Math.min(Math.max(1, page), comboTotalPages.value)
}

// --- Filtered Promo Offers List ---
const filteredOffers = computed(() => {
  if (!offersSearch.value.trim()) return offers.value
  const q = offersSearch.value.toLowerCase()
  return offers.value.filter(o => 
    o.combo_ten.toLowerCase().includes(q) || 
    o.sanpham_ten.toLowerCase().includes(q) || 
    (o.ten_bienthe && o.ten_bienthe.toLowerCase().includes(q))
  )
})

// --- Computed Price & Watches ---
const selectedOriginalPriceTotal = computed(() => {
  return productsPool.value
    .filter(p => form.value.product_ids.includes(p.id_sanpham))
    .reduce((sum, p) => {
      const basePrice = p.bien_thes && p.bien_thes.length > 0
        ? Math.min(...p.bien_thes.map(v => Number(v.gia || 0)))
        : 0
      return sum + basePrice
    }, 0)
})

watch(() => [...form.value.product_ids], (newIds) => {
  if (isNameManuallyEdited.value) return
  
  const selectedProds = productsPool.value.filter(p => newIds.includes(p.id_sanpham))
  if (selectedProds.length > 0) {
    const names = selectedProds.map(p => p.tenSP).join(' + ')
    form.value.ten_combo = `Combo ${names}`
  } else {
    form.value.ten_combo = ''
  }
})

watch(selectedOriginalPriceTotal, (newTotal) => {
  if (isPriceManuallyEdited.value) return
  
  if (newTotal > 0) {
    // Khuyến nghị giảm 15% trọn gói combo, làm tròn đến hàng nghìn
    const suggested = Math.round((newTotal * 0.85) / 1000) * 1000
    form.value.giakhuyenmai = suggested
  } else {
    form.value.giakhuyenmai = ''
  }
})

watch(search, () => {
  comboCurrentPage.value = 1
})

watch(comboTotalPages, (total) => {
  if (comboCurrentPage.value > total) {
    comboCurrentPage.value = total
  }
})

// Watch selectedOfferProduct to load variants
watch(selectedOfferProduct, (newProductId) => {
  isVariantCollapsed.value = false
  if (!newProductId) {
    availableOfferVariants.value = []
    offerForm.value.id_bienthe = ''
    return
  }
  const prod = allProductsPool.value.find(p => p.id_sanpham === Number(newProductId))
  if (prod && prod.bien_thes) {
    availableOfferVariants.value = prod.bien_thes
  } else {
    availableOfferVariants.value = []
  }
  
  if (!isOfferEditMode.value) {
    if (availableOfferVariants.value.length > 0) {
      offerForm.value.id_bienthe = availableOfferVariants.value[0].id_bienthe
    } else {
      offerForm.value.id_bienthe = ''
    }
  }
})

// --- API Calls ---
const fetchCombos = async () => {
  try {
    const res = await api.get('/admin/combos')
    if (res.data && res.data.success) {
      combos.value = res.data.data
    }
  } catch (error) {
    console.error('Lỗi fetch combos:', error)
  }
}

const fetchProductsPool = async () => {
  try {
    const res = await api.get('/sanpham')
    const allProducts = res.data || []
    // Lọc chỉ lấy các sản phẩm phụ kiện (id_danhmuc_cha = 9 hoặc id_danhmuc = 9)
    productsPool.value = allProducts.filter(p => {
      const parentId = p.danh_muc?.id_danhmuc_cha;
      const catId = p.id_danhmuc || p.danh_muc?.id_danhmuc;
      return Number(parentId) === 9 || Number(catId) === 9;
    })
  } catch (error) {
    console.error('Lỗi fetch products pool:', error)
  }
}

const fetchOffers = async () => {
  try {
    const res = await api.get('/admin/combo-offers')
    if (res.data && res.data.success) {
      offers.value = res.data.data
    }
  } catch (error) {
    console.error('Lỗi fetch combo offers:', error)
  }
}

const fetchAllProducts = async () => {
  try {
    const res = await api.get('/sanpham')
    allProductsPool.value = res.data || []
  } catch (error) {
    console.error('Lỗi fetch all products:', error)
  }
}

// --- Image Handling ---
const triggerFileInput = () => fileInputRef.value?.click()

const onFileChange = (e) => {
  const file = e.target.files[0]
  if (!file) return

  if (!['image/png', 'image/jpeg', 'image/jpg', 'image/webp'].includes(file.type)) {
    swal.error('Lỗi', 'Ảnh chỉ chấp nhận PNG, JPG, JPEG, WEBP')
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    swal.error('Lỗi', 'Ảnh không được vượt quá 5MB')
    return
  }

  const reader = new FileReader()
  reader.onload = (ev) => {
    imgPreview.value = ev.target.result
    form.value.hinhanh = ev.target.result
  }
  reader.readAsDataURL(file)
}

const removeImg = () => {
  imgPreview.value = ''
  form.value.hinhanh = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
}

// --- Product Selection in Combo ---
const toggleProductSelection = (id) => {
  if (form.value.product_ids.includes(id)) {
    form.value.product_ids = form.value.product_ids.filter(pId => pId !== id)
  } else {
    // Tìm sản phẩm trong pool để đối chiếu danh mục con
    const prodToSelect = productsPool.value.find(p => p.id_sanpham === id)
    if (!prodToSelect) return

    // Tìm sản phẩm cùng danh mục con đã được chọn
    const sameCatProd = productsPool.value.find(p => 
      form.value.product_ids.includes(p.id_sanpham) && p.id_danhmuc === prodToSelect.id_danhmuc
    )
    if (sameCatProd) {
      swal.warning(
        'Trùng danh mục con',
        `Mỗi danh mục chỉ chọn tối đa 1 sản phẩm! Danh mục này đã có sản phẩm "${sameCatProd.tenSP}" được chọn.`
      )
      return
    }
    form.value.product_ids.push(id)
  }
}

const isProductSelected = (id) => form.value.product_ids.includes(id)

const getSelectedProductName = (id) => {
  const p = productsPool.value.find(prod => prod.id_sanpham === id)
  return p ? p.tenSP : 'Sản phẩm #' + id
}

const getProductDisplayPrice = (p) => {
  if (!p.bien_thes || p.bien_thes.length === 0) return 'Chưa có giá'
  const prices = p.bien_thes.map(v => Number(v.gia || 0))
  const minPrice = Math.min(...prices)
  const maxPrice = Math.max(...prices)
  if (minPrice === maxPrice) {
    return minPrice.toLocaleString('vi-VN') + 'đ'
  }
  return `${minPrice.toLocaleString('vi-VN')}đ - ${maxPrice.toLocaleString('vi-VN')}đ`
}

// --- Combo Actions ---
const validateForm = () => {
  const errors = {}
  if (!form.value.ten_combo.trim()) {
    errors.ten_combo = 'Tên combo không được để trống'
  }
  if (!form.value.giakhuyenmai || Number(form.value.giakhuyenmai) <= 0) {
    errors.giakhuyenmai = 'Giá khuyến mãi phải lớn hơn 0'
  }
  if (!form.value.product_ids.length || form.value.product_ids.length < 2) {
    errors.product_ids = 'Vui lòng chọn ít nhất 2 sản phẩm để tạo combo'
  }

  fieldErrors.value = errors
  return Object.keys(errors).length === 0
}

const openAddModal = () => {
  isEditMode.value = false
  editingComboId.value = null
  form.value = defaultForm()
  imgPreview.value = ''
  formError.value = ''
  fieldErrors.value = {}
  isNameManuallyEdited.value = false
  isPriceManuallyEdited.value = false
  selectedPoolCategory.value = null
  currentView.value = 'combo-form'
}

const openEditModal = (combo) => {
  isEditMode.value = true
  editingComboId.value = combo.id_combo
  form.value = {
    ten_combo: combo.ten_combo,
    giakhuyenmai: combo.giakhuyenmai,
    mota: combo.mota || '',
    hinhanh: '',
    trangthai: combo.trangthai,
    product_ids: combo.products.map(p => p.id_sanpham)
  }
  imgPreview.value = combo.hinhanh ? storageUrl(combo.hinhanh) : ''
  formError.value = ''
  fieldErrors.value = {}
  isNameManuallyEdited.value = true
  isPriceManuallyEdited.value = true
  selectedPoolCategory.value = null
  currentView.value = 'combo-form'
}

const closeModal = () => {
  currentView.value = 'list'
}

const submitForm = async () => {
  if (!validateForm()) return
  
  isSubmitting.value = true
  formError.value = ''

  const payload = {
    ten_combo: form.value.ten_combo.trim(),
    giakhuyenmai: Number(form.value.giakhuyenmai),
    mota: form.value.mota,
    trangthai: form.value.trangthai,
    product_ids: form.value.product_ids,
    hinhanh: form.value.hinhanh || imgPreview.value || null
  }

  try {
    if (isEditMode.value) {
      await api.put(`/admin/combos/${editingComboId.value}`, payload)
      swal.success('Thành công', 'Cập nhật combo thành công!')
    } else {
      await api.post('/admin/combos', payload)
      swal.success('Thành công', 'Tạo combo mới thành công!')
    }
    await fetchCombos()
    currentView.value = 'list'
  } catch (error) {
    console.error(error)
    formError.value = error.response?.data?.message || 'Có lỗi xảy ra khi lưu combo.'
  } finally {
    isSubmitting.value = false
  }
}

const deleteCombo = async (id) => {
  const confirmed = await swal.confirm('Xác nhận xóa', 'Bạn có chắc chắn muốn xóa combo này không?')
  if (!confirmed) return

  try {
    const res = await api.delete(`/admin/combos/${id}`)
    if (res.data && res.data.success) {
      swal.success('Thành công', 'Đã xóa combo thành công!')
      await fetchCombos()
    }
  } catch (error) {
    console.error(error)
    swal.error('Lỗi', 'Không thể xóa combo này.')
  }
}

// --- Search & Filter Laptop Products for Trigger ---
const offerProductSearchQuery = ref('')

const filteredLaptopProducts = computed(() => {
  // 1. Lọc chỉ lấy các sản phẩm Laptop (id_danhmuc_cha = 1 hoặc tên có chứa Laptop/MacBook)
  const laptops = allProductsPool.value.filter(p => {
    const parentId = p.danh_muc?.id_danhmuc_cha;
    const catId = p.id_danhmuc || p.danh_muc?.id_danhmuc;
    const catName = p.danh_muc?.ten_danhmuc?.toLowerCase() || '';
    const name = p.tenSP.toLowerCase();
    
    return Number(parentId) === 1 || 
           Number(catId) === 1 || 
           catName.includes('laptop') || 
           name.includes('laptop') || 
           name.includes('macbook');
  })
  
  // 2. Tìm kiếm theo từ khóa
  if (!offerProductSearchQuery.value.trim()) return laptops.slice(0, 30)
  const q = offerProductSearchQuery.value.toLowerCase()
  return laptops.filter(p => 
    p.tenSP.toLowerCase().includes(q) || 
    (p.SKU && p.SKU.toLowerCase().includes(q))
  ).slice(0, 30)
})

const selectOfferProductAction = (p) => {
  selectedOfferProduct.value = p.id_sanpham
}

const getSelectedOfferProductName = (id) => {
  const p = allProductsPool.value.find(prod => prod.id_sanpham === id)
  return p ? p.tenSP : 'Sản phẩm #' + id
}

const getSelectedVariantName = (id) => {
  const v = availableOfferVariants.value.find(varItem => varItem.id_bienthe === Number(id))
  return v ? getConfigName(v.ten_bienthe) : 'Cấu hình #' + id
}

const getSelectedVariantPrice = (id) => {
  const v = availableOfferVariants.value.find(varItem => varItem.id_bienthe === Number(id))
  return v ? Number(v.gia).toLocaleString('vi-VN') + 'đ' : ''
}

const selectVariantAction = (groupedVar) => {
  if (isOfferEditMode.value) return
  offerForm.value.id_bienthe = groupedVar.id_bienthe
  isVariantCollapsed.value = true
}

// --- Offer Actions ---
const validateOfferForm = () => {
  const errors = {}
  if (!selectedOfferProduct.value) {
    errors.id_sanpham = 'Vui lòng chọn sản phẩm chính'
  }
  if (!offerForm.value.id_bienthe) {
    errors.id_bienthe = 'Vui lòng chọn cấu hình kích hoạt'
  }
  if (!offerForm.value.id_combo) {
    errors.id_combo = 'Vui lòng chọn combo phụ kiện được ưu đãi'
  }
  if (offerForm.value.loai_uudai === 'discount') {
    if (offerForm.value.giakhuyenmai_override === '' || Number(offerForm.value.giakhuyenmai_override) < 0) {
      errors.giakhuyenmai_override = 'Giá trị ưu đãi không hợp lệ'
    }
  }
  if (offerForm.value.gioi_han_soluong !== '' && offerForm.value.gioi_han_soluong !== null && Number(offerForm.value.gioi_han_soluong) <= 0) {
    errors.gioi_han_soluong = 'Giới hạn số lượng phải lớn hơn 0'
  }

  offerFieldErrors.value = errors
  return Object.keys(errors).length === 0
}

const openAddOfferModal = () => {
  isOfferEditMode.value = false
  editingOfferId.value = null
  offerForm.value = defaultOfferForm()
  selectedOfferProduct.value = null
  isVariantCollapsed.value = false
  offerFormError.value = ''
  offerFieldErrors.value = {}
  currentView.value = 'offer-form'
}

const openEditOfferModal = (offer) => {
  isOfferEditMode.value = true
  editingOfferId.value = offer.id
  selectedOfferProduct.value = offer.id_sanpham
  isVariantCollapsed.value = true
  
  offerForm.value = {
    id_bienthe: offer.id_bienthe,
    id_combo: offer.id_combo,
    loai_uudai: offer.loai_uudai,
    giakhuyenmai_override: offer.giakhuyenmai_override,
    mota_uudai: offer.mota_uudai || '',
    gioi_han_soluong: offer.gioi_han_soluong || '',
    ngay_het_han: offer.ngay_het_han ? offer.ngay_het_han.replace(' ', 'T').substring(0, 16) : '',
    trangthai: offer.trangthai
  }
  offerFormError.value = ''
  offerFieldErrors.value = {}
  currentView.value = 'offer-form'
}

const closeOfferModal = () => {
  currentView.value = 'list'
}

const submitOfferForm = async () => {
  if (!validateOfferForm()) return
  
  isOfferSubmitting.value = true
  offerFormError.value = ''

  const payload = {
    id_bienthe: Number(offerForm.value.id_bienthe),
    id_combo: Number(offerForm.value.id_combo),
    loai_uudai: offerForm.value.loai_uudai,
    giakhuyenmai_override: offerForm.value.loai_uudai === 'free' ? 0 : Number(offerForm.value.giakhuyenmai_override),
    mota_uudai: offerForm.value.mota_uudai,
    gioi_han_soluong: offerForm.value.gioi_han_soluong ? Number(offerForm.value.gioi_han_soluong) : null,
    ngay_het_han: offerForm.value.ngay_het_han ? offerForm.value.ngay_het_han.replace('T', ' ') : null,
    trangthai: Number(offerForm.value.trangthai)
  }

  try {
    if (isOfferEditMode.value) {
      await api.put(`/admin/combo-offers/${editingOfferId.value}`, payload)
      swal.success('Thành công', 'Cập nhật ưu đãi thành công!')
    } else {
      await api.post('/admin/combo-offers', payload)
      swal.success('Thành công', 'Thiết lập cấu hình ưu đãi thành công!')
    }
    await fetchOffers()
    currentView.value = 'list'
  } catch (error) {
    console.error(error)
    offerFormError.value = error.response?.data?.message || 'Có lỗi xảy ra khi lưu ưu đãi.'
  } finally {
    isOfferSubmitting.value = false
  }
}

const deleteOffer = async (id) => {
  const confirmed = await swal.confirm('Xác nhận xóa', 'Bạn có chắc chắn muốn gỡ bỏ ưu đãi này không? Combo phụ kiện sẽ quay trở lại bán lẻ như bình thường.')
  if (!confirmed) return

  try {
    const res = await api.delete(`/admin/combo-offers/${id}`)
    if (res.data && res.data.success) {
      swal.success('Thành công', 'Đã xóa cấu hình ưu đãi thành công!')
      await fetchOffers()
    }
  } catch (error) {
    console.error(error)
    swal.error('Lỗi', 'Không thể xóa ưu đãi này.')
  }
}

const formatOfferDate = (dateStr) => {
  if (!dateStr) return ''
  try {
    const d = new Date(dateStr.replace(' ', 'T'))
    return d.toLocaleString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (e) {
    return dateStr
  }
}

const syncSuccessHandler = () => {
  fetchCombos()
  fetchProductsPool()
  fetchOffers()
  fetchAllProducts()
}

onMounted(() => {
  fetchCombos()
  fetchProductsPool()
  fetchOffers()
  fetchAllProducts()
  window.addEventListener('offline-sync-success', syncSuccessHandler)
})

onBeforeUnmount(() => {
  window.removeEventListener('offline-sync-success', syncSuccessHandler)
})
</script>

<template>
  <div class="admin combo-management">

    <!-- ══════════════════════════════════════════════════════
         VIEW: DANH SÁCH (list, stats, tabs, table/grid)
    ══════════════════════════════════════════════════════ -->
    <template v-if="currentView === 'list'">

    <!-- Tab switcher navigation -->
    <div class="tabs-navigation">
      <div class="tab-nav-list">
        <button 
          class="tab-nav-btn" 
          :class="{ active: activeTab === 'combos' }" 
          @click="activeTab = 'combos'"
        >
          Quản lý Combo Bán Lẻ
        </button>
        <button 
          class="tab-nav-btn" 
          :class="{ active: activeTab === 'offers' }" 
          @click="activeTab = 'offers'"
        >
          Cấu hình Ưu đãi Biến thể
        </button>
      </div>
      <button v-if="activeTab === 'combos'" class="add-btn" @click="openAddModal">+ Thêm Combo mới</button>
      <button v-else class="add-btn" @click="openAddOfferModal">+ Tạo Ưu Đãi Mới</button>
    </div>

    <!-- Stats Cards -->
    <div class="stats">
      <div class="stat-card stat-blue">
        <span class="stat-icon blue" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
            <path d="M3.3 7 12 12l8.7-5" />
            <path d="M12 22V12" />
          </svg>
        </span>
        <div>
          <p>Tổng số Combo</p>
          <b>{{ totalCombos }}</b>
        </div>
      </div>
      <div class="stat-card stat-teal">
        <span class="stat-icon green" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 12 20 22 4 22 4 12" />
            <rect x="2" y="7" width="20" height="5" />
            <line x1="12" y1="22" x2="12" y2="7" />
            <path d="M12 7H7.5a2.5 2.5 0 1 1 2.15-3.78L12 7Z" />
            <path d="M12 7h4.5a2.5 2.5 0 1 0-2.15-3.78L12 7Z" />
          </svg>
        </span>
        <div>
          <p>Ưu đãi đang chạy</p>
          <b>{{ activeOffers }}</b>
        </div>
      </div>
      <div class="stat-card stat-orange">
        <span class="stat-icon orange" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="2" x2="12" y2="22" />
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6" />
          </svg>
        </span>
        <div>
          <p>Giá trị TB Combo</p>
          <b>{{ avgPrice.toLocaleString('vi-VN') }}đ</b>
        </div>
      </div>
    </div>

    <!-- TAB 1: RETAIL COMBOS -->
    <div v-if="activeTab === 'combos'" class="tab-content-pane">
      <!-- Toolbar / Filter -->
      <div class="filter-bar">
        <div class="search-wrap">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input v-model="search" placeholder="Tìm kiếm tên combo..." />
        </div>
      </div>

      <!-- Combos Admin List -->
      <div class="combo-list-panel">
        <div v-if="!filteredCombos.length" class="empty-state">
          <div class="empty-icon">📦</div>
          <h3>Không tìm thấy combo nào</h3>
          <p>Hãy thử từ khóa khác hoặc tạo combo phụ kiện mới.</p>
        </div>

        <table v-else class="combo-admin-table">
          <thead>
            <tr>
              <th style="width: 86px;">Ảnh</th>
              <th>Thông tin combo</th>
              <th>Sản phẩm trong combo</th>
              <th style="width: 150px;">Giá combo</th>
              <th style="width: 140px;">Trạng thái</th>
              <th style="width: 120px; text-align: center;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="combo in paginatedCombos" :key="combo.id_combo" :class="{ 'row-inactive': combo.trangthai === 0 || combo.is_in_stock === false }">
              <td>
                <img class="combo-list-thumb" :src="storageUrl(combo.hinhanh) || 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400'" :alt="combo.ten_combo" />
              </td>
              <td>
                <div class="combo-list-info">
                  <b>{{ combo.ten_combo }}</b>
                  <span>{{ combo.mota || 'Không có mô tả cho combo này.' }}</span>
                </div>
              </td>
              <td>
                <div class="combo-product-stack">
                  <span v-for="p in combo.products" :key="p.id_sanpham">{{ p.tenSP }}</span>
                  <small v-if="!combo.products || combo.products.length === 0">Chưa có sản phẩm</small>
                </div>
              </td>
              <td>
                <b class="combo-list-price">{{ Number(combo.giakhuyenmai).toLocaleString('vi-VN') }}đ</b>
              </td>
              <td>
                <div class="combo-status-stack">
                  <span class="badge" :class="combo.trangthai === 1 ? 'badge-success' : 'badge-draft'">
                    {{ combo.trangthai === 1 ? 'Hoạt động' : 'Ngừng chạy' }}
                  </span>
                  <span v-if="combo.is_in_stock === false" class="badge badge-expired-red">Thiếu hàng</span>
                </div>
              </td>
              <td>
                <div class="actions" style="justify-content: center;">
                  <button class="act-btn" title="Sửa" @click="openEditModal(combo)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                  <button class="act-btn danger" title="Xóa" @click="deleteCombo(combo.id_combo)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16">
                      <polyline points="3 6 5 6 21 6" />
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                      <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <PhanTrangAdmin
          v-if="filteredCombos.length"
          v-model:currentPage="comboCurrentPage"
          :total-pages="comboTotalPages"
          :total-items="filteredCombos.length"
          :page-size="comboItemsPerPage"
          item-label="combo"
          @change-page="changeComboPage"
        />
        <div v-if="false" v-for="combo in filteredCombos" :key="combo.id_combo" class="combo-card" :class="{ inactive: combo.trangthai === 0 || combo.is_in_stock === false }">
          <div class="combo-badge" :class="combo.trangthai === 1 ? 'active' : 'draft'">
            {{ combo.trangthai === 1 ? 'Hoạt động' : 'Ngừng chạy' }}
          </div>
          <div v-if="combo.is_in_stock === false" class="combo-badge" style="right: auto; left: 12px; background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;">
            ⚠️ Hết hàng phụ kiện
          </div>
          <div class="combo-img">
            <img :src="storageUrl(combo.hinhanh) || 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400'" :alt="combo.ten_combo" />
          </div>
          <div class="combo-details">
            <h3>{{ combo.ten_combo }}</h3>
            <p class="desc">{{ combo.mota || 'Không có mô tả cho combo này.' }}</p>
            
            <div class="products-list">
              <h4>Mặt hàng trong combo:</h4>
              <ul>
                <li v-for="p in combo.products" :key="p.id_sanpham">
                  🔹 {{ p.tenSP }}
                </li>
              </ul>
            </div>
            
            <div class="combo-footer">
              <div class="price-box">
                <span class="lbl">Giá Combo:</span>
                <span class="price">{{ Number(combo.giakhuyenmai).toLocaleString('vi-VN') }}đ</span>
              </div>
              <div class="actions">
                <button class="act-btn" title="Sửa" @click="openEditModal(combo)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                  </svg>
                </button>
                <button class="act-btn danger" title="Xóa" @click="deleteCombo(combo.id_combo)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="16" height="16">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: PROMOTIONAL VARIANT OFFERS -->
    <div v-else class="tab-content-pane">
      <!-- Toolbar / Filter -->
      <div class="filter-bar">
        <div class="search-wrap">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input v-model="offersSearch" placeholder="Tìm kiếm theo sản phẩm, biến thể hoặc combo..." />
        </div>
      </div>

      <!-- Offers Table -->
      <div class="offers-table-wrap">
        <div v-if="!filteredOffers.length" class="empty-state">
          <div class="empty-icon">🎁</div>
          <h3>Không tìm thấy ưu đãi biến thể nào</h3>
          <p>Nhấp vào nút "+ Tạo Ưu Đãi Mới" để bắt đầu cấu hình quà tặng!</p>
        </div>

        <table v-else class="offers-table">
          <thead>
            <tr>
              <th>Combo áp dụng</th>
              <th>Laptop & Cấu hình kích hoạt</th>
              <th>Giá trị ưu đãi</th>
              <th>Giới hạn & Thời hạn</th>
              <th>Hiệu lực chiến dịch</th>
              <th style="text-align: center; width: 140px; min-width: 140px;">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="offer in filteredOffers" :key="offer.id" :class="{ 'row-inactive': offer.trangthai === 0 || !offer.is_valid }">
              <td>
                <div class="product-target">
                  <span class="combo-tag">{{ offer.combo_ten }}</span>
                  <small class="block text-gray" style="margin-top: 6px;">
                    Giá gốc: <b>{{ Number(offer.combo_gia).toLocaleString('vi-VN') }}đ</b>
                  </small>
                  <div v-if="offer.is_combo_in_stock === false" style="margin-top: 6px;">
                    <span class="badge badge-expired-red">
                      ⚠️ Thiếu hàng phụ kiện
                    </span>
                  </div>
                </div>
              </td>
              <td>
                <div class="product-target">
                  <span class="product-name">{{ offer.sanpham_ten }}</span>
                  <span class="variant-spec-badge" style="margin-top: 6px;">
                    💻 {{ getConfigName(offer.ten_bienthe) }}
                  </span>
                </div>
              </td>
              <td>
                <div class="product-target">
                  <span class="badge" :class="offer.loai_uudai === 'free' ? 'badge-green' : 'badge-orange'">
                    {{ offer.loai_uudai === 'free' ? '🎁 Tặng 0đ' : '🏷️ Mua kèm' }}
                  </span>
                  <b class="price-text block" :class="{ 'free-text': offer.loai_uudai === 'free' }" style="margin-top: 6px; font-size: 14px;">
                    {{ offer.loai_uudai === 'free' ? 'Miễn phí' : Number(offer.giakhuyenmai_override).toLocaleString('vi-VN') + 'đ' }}
                  </b>
                </div>
              </td>
              <td>
                <div class="limit-expiry">
                  <!-- Usage limit and progress bar -->
                  <div class="usage-progress-container" style="width: 100%; display: flex; flex-direction: column; gap: 6px; align-items: flex-start;">
                    <div class="usage-info" style="font-size: 11.5px; color: #475569; font-weight: 500;">
                      Đã dùng: <b>{{ offer.da_su_dung }}</b> / {{ offer.gioi_han_soluong || '∞' }} suất
                    </div>
                    <div v-if="offer.gioi_han_soluong" class="usage-bar-bg" style="width: 100px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; margin-top: 2px;">
                      <div class="usage-bar-fill" :style="{ width: Math.min((offer.da_su_dung / offer.gioi_han_soluong) * 100, 100) + '%' }" style="height: 100%; background: linear-gradient(90deg, #2563eb, #3b82f6); border-radius: 3px;"></div>
                    </div>
                  </div>
                  
                  <!-- Expiry Date -->
                  <div v-if="offer.ngay_het_han" class="expiry-box" style="margin-top: 8px; display: flex; align-items: center; gap: 4px; font-size: 11.5px; color: #475569; font-weight: 500;" :title="'Thời điểm hết hạn: ' + offer.ngay_het_han">
                    ⏳ Hạn: <b>{{ formatOfferDate(offer.ngay_het_han) }}</b>
                  </div>
                  <div v-else class="expiry-box" style="margin-top: 8px; font-size: 11.5px; color: #94a3b8; font-weight: 500;">
                    ♾️ Vô thời hạn
                  </div>
                </div>
              </td>
              <td>
                <div class="product-target">
                  <span class="badge" :class="offer.trangthai === 1 ? 'badge-success' : 'badge-draft'">
                    {{ offer.trangthai === 1 ? 'Đang chạy' : 'Tạm ẩn' }}
                  </span>
                  <span v-if="offer.trangthai === 1" class="badge" :class="offer.is_valid ? 'badge-active-green' : 'badge-expired-red'" style="margin-top: 6px;">
                    {{ offer.is_valid ? '🟢 Khả dụng' : '🔴 Vô hiệu' }}
                  </span>
                </div>
              </td>
              <td style="width: 140px; min-width: 140px;">
                <div class="actions" style="justify-content: center; gap: 8px;">
                  <button class="act-btn info" title="Xem chi tiết" @click="viewOfferDetail(offer)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" width="14" height="14">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  </button>
                  <button class="act-btn" title="Sửa ưu đãi" @click="openEditOfferModal(offer)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" width="14" height="14">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                  <button class="act-btn danger" title="Xóa" @click="deleteOffer(offer.id)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" width="14" height="14">
                      <polyline points="3 6 5 6 21 6" />
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                      <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    </template><!-- end v-if list -->

    <!-- ══════════════════════════════════════════════════════
         VIEW: FORM COMBO (Thêm / Sửa Combo)
    ══════════════════════════════════════════════════════ -->
    <template v-if="currentView === 'combo-form'">
      <!-- Inline form header -->
      <div class="inline-form-header">
        <button class="back-btn" @click="closeModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M15 18l-6-6 6-6"/></svg>
          Quay lại danh sách
        </button>
        <h1>{{ isEditMode ? '✏️ Chỉnh sửa Combo' : '➕ Tạo Combo mới' }}</h1>
        <p>{{ isEditMode ? 'Cập nhật thông tin và sản phẩm trong combo' : 'Ghép sản phẩm phụ kiện thành combo khuyến mãi' }}</p>
      </div>

      <div class="inline-form-body">
          <!-- 1. Chọn sản phẩm cho Combo -->
          <div class="form-group products-selection-section" style="border-top: none; padding-top: 0; margin-top: 0;">
            <label>Bước 1: Chọn sản phẩm phụ kiện ghép Combo (Chọn từ 2 sản phẩm trở lên, không trùng danh mục con) <span class="required">*</span></label>
            <p v-if="fieldErrors.product_ids" class="field-error">{{ fieldErrors.product_ids }}</p>

              <!-- Selected Tags -->
            <div v-if="form.product_ids.length" class="selected-tags" style="margin-top: 8px;">
                <span v-for="id in form.product_ids" :key="id" class="p-tag">
                  {{ getSelectedProductName(id) }}
                  <button type="button" @click="toggleProductSelection(id)">×</button>
                </span>
              </div>

              <!-- Category Tabs to Filter Accessory Pool -->
              <div class="pool-category-tabs" style="display: flex; gap: 8px; margin-top: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                <button 
                  type="button" 
                  class="tab-btn" 
                  :class="{ active: selectedPoolCategory === null }" 
                  @click="selectedPoolCategory = null"
                >
                  Tất cả phụ kiện
                </button>
                <button 
                  type="button" 
                  class="tab-btn" 
                  :class="{ active: selectedPoolCategory === 10 }" 
                  @click="selectedPoolCategory = 10"
                >
                  🖱️ Chuột
                </button>
                <button 
                  type="button" 
                  class="tab-btn" 
                  :class="{ active: selectedPoolCategory === 11 }" 
                  @click="selectedPoolCategory = 11"
                >
                  ⌨️ Bàn phím
                </button>
                <button 
                  type="button" 
                  class="tab-btn" 
                  :class="{ active: selectedPoolCategory === 12 }" 
                  @click="selectedPoolCategory = 12"
                >
                  🎧 Tai nghe
                </button>
                <button 
                  type="button" 
                  class="tab-btn" 
                  :class="{ active: selectedPoolCategory === 13 }" 
                  @click="selectedPoolCategory = 13"
                >
                  🔲 Lót chuột
                </button>
              </div>

              <!-- Product Search & List -->
              <div class="pool-search-box" style="margin-top: 8px;">
                <input v-model="productSearchQuery" placeholder="🔍 Nhập tên sản phẩm để tìm nhanh..." />
              </div>

              <div class="products-pool">
                <div 
                  v-for="p in filteredProductsPool" 
                  :key="p.id_sanpham" 
                  class="pool-item" 
                  :class="{ selected: isProductSelected(p.id_sanpham) }"
                  @click="toggleProductSelection(p.id_sanpham)"
                >
                  <div class="chk">
                    <span v-if="isProductSelected(p.id_sanpham)">✓</span>
                  </div>
                  <div class="p-info">
                    <b>{{ p.tenSP }}</b>
                    <span>SKU: {{ p.SKU || 'N/A' }} | {{ p.danh_muc?.ten_danhmuc || 'Không có danh mục con' }}</span>
                    <span class="p-pool-price" style="font-size: 11.5px; font-weight: 800; color: #2563eb; margin-top: 4px; display: block;">
                      Giá bán lẻ: {{ getProductDisplayPrice(p) }}
                    </span>
                  </div>
                </div>
              </div>
          </div>

          <!-- 2. Tải lên hình ảnh đại diện -->
            <div class="form-group" style="border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 8px;">
              <label>Bước 2: Ảnh đại diện Combo</label>
              <input ref="fileInputRef" type="file" accept="image/*" style="display:none" @change="onFileChange" />
              <div v-if="!imgPreview" class="upload-zone" @click="triggerFileInput">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="17 8 12 3 7 8" />
                  <line x1="12" y1="3" x2="12" y2="15" />
                </svg>
                <p>Kéo thả hoặc <span>bấm để chọn ảnh</span></p>
                <small>PNG, JPG, WEBP — tối đa 5MB</small>
              </div>
              <div v-else class="img-preview-wrap">
                <img :src="imgPreview" class="img-preview" alt="preview" />
                <div class="img-actions">
                  <button class="img-change" @click="triggerFileInput">Đổi ảnh</button>
                  <button class="img-remove-btn" @click="removeImg">Xóa</button>
                </div>
              </div>
            </div>

            <!-- 3. Tên và Giá Combo -->
            <div class="form-cols-2" style="border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 8px;">
              <div class="form-group">
                <label>Bước 3: Tên Combo <span class="required">*</span></label>
                <input v-model="form.ten_combo" @input="isNameManuallyEdited = true" placeholder="VD: Combo Bàn phím + Chuột gaming" :class="{ 'input-error': fieldErrors.ten_combo }" />
                <p v-if="fieldErrors.ten_combo" class="field-error">{{ fieldErrors.ten_combo }}</p>
                <small style="color: #64748b; font-size:11px; margin-top:4px; display:block; line-height:1.4">Tên combo sẽ tự động ghép từ các sản phẩm phụ kiện đã chọn trừ khi bạn chỉnh sửa thủ công.</small>
              </div>

              <div class="form-group">
                <label>Bước 3.2: Giá khuyến mãi Combo (đ) <span class="required">*</span></label>
                <input v-model="form.giakhuyenmai" type="number" @input="isPriceManuallyEdited = true" placeholder="VD: 550000" :class="{ 'input-error': fieldErrors.giakhuyenmai }" />
                <p v-if="fieldErrors.giakhuyenmai" class="field-error">{{ fieldErrors.giakhuyenmai }}</p>
                <div v-if="selectedOriginalPriceTotal > 0" style="color: #166534; font-size:11px; margin-top:4px; display:flex; flex-direction:column; gap:2px; text-align:left; background:#f0fdf4; padding:6px 10px; border-radius:8px; border:1px solid #bbf7d0; line-height:1.4">
                  <span>💡 Tổng giá gốc mua lẻ: <b>{{ selectedOriginalPriceTotal.toLocaleString('vi-VN') }}đ</b></span>
                  <span>🚀 Đã tự động đề xuất giảm <b>15%</b></span>
                </div>
              </div>
            </div>

            <!-- 4. Mô tả Combo -->
            <div class="form-group" style="border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 8px;">
              <label>Bước 4: Mô tả combo</label>
              <textarea v-model="form.mota" placeholder="Mô tả các sản phẩm & ưu đãi của combo..." rows="3"></textarea>
            </div>

            <!-- 5. Trạng thái -->
            <div class="form-group" style="border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 8px;">
              <label>Bước 5: Trạng thái hoạt động</label>
              <select v-model="form.trangthai">
                <option :value="1">Hoạt động (Được mở bán)</option>
                <option :value="0">Tạm ẩn (Ngừng bán)</option>
              </select>
            </div>

          <p v-if="formError" class="form-error">⚠ {{ formError }}</p>

          <!-- Footer actions -->
          <div class="inline-form-footer">
            <button class="btn-cancel" @click="closeModal">Hủy</button>
            <button class="btn-submit" @click="submitForm" :disabled="isSubmitting">
              {{ isSubmitting ? 'Đang lưu...' : (isEditMode ? 'Lưu thay đổi' : 'Tạo Combo') }}
            </button>
          </div>
      </div>
    </template><!-- end combo-form -->

    <!-- ══════════════════════════════════════════════════════
         VIEW: FORM ƯU ĐÃI (Thêm / Sửa Ưu đãi biến thể)
    ══════════════════════════════════════════════════════ -->
    <template v-if="currentView === 'offer-form'">
      <!-- Inline form header -->
      <div class="inline-form-header">
        <button class="back-btn" @click="closeOfferModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M15 18l-6-6 6-6"/></svg>
          Quay lại danh sách
        </button>
        <h1>{{ isOfferEditMode ? '✏️ Chỉnh sửa Ưu đãi' : '🎁 Tạo Ưu đãi mới' }}</h1>
        <p>{{ isOfferEditMode ? 'Cập nhật cấu hình ưu đãi biến thể' : 'Gắn combo phụ kiện làm quà tặng cho biến thể sản phẩm chính' }}</p>
      </div>

      <div class="inline-form-body">
            <!-- Product selection -->
            <div class="form-group">
              <label>Bước 1: Chọn Laptop chính kích hoạt ưu đãi <span class="required">*</span></label>
              <p v-if="offerFieldErrors.id_sanpham" class="field-error">{{ offerFieldErrors.id_sanpham }}</p>
              
              <!-- Search box if no product chosen -->
              <div v-if="!selectedOfferProduct">
                <div class="pool-search-box" style="margin-bottom: 4px;">
                  <input v-model="offerProductSearchQuery" placeholder="🔍 Nhập tên Laptop để tìm kiếm..." />
                </div>
                <div class="products-pool" style="max-height: 160px; margin-top: 4px;">
                  <div 
                    v-for="p in filteredLaptopProducts" 
                    :key="p.id_sanpham" 
                    class="pool-item" 
                    @click="selectOfferProductAction(p)"
                  >
                    <div class="p-info" style="text-align: left;">
                      <b style="font-size: 12px; color: #1e293b; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ p.tenSP }}</b>
                      <span style="font-size: 10px; color: #64748b;">SKU: {{ p.SKU || 'N/A' }} | {{ p.danh_muc?.ten_danhmuc || 'Laptop' }}</span>
                    </div>
                  </div>
                  <div v-if="!filteredLaptopProducts.length" style="padding: 14px; text-align: center; color: #64748b; font-size: 12.5px;">
                    Không tìm thấy sản phẩm laptop nào phù hợp.
                  </div>
                </div>
              </div>

              <!-- Selected product badge/info -->
              <div v-else class="selected-product-info" style="display: flex; justify-content: space-between; align-items: center; background: #eff6ff; border: 1px solid #bfdbfe; padding: 12px 16px; border-radius: 10px;">
                <div style="text-align: left;">
                  <span style="font-size: 10px; color: #2563eb; font-weight: 700; display: block; text-transform: capitalize; letter-spacing: 0.05em; margin-bottom: 2px;">Laptop Đang Chọn:</span>
                  <b style="font-size: 13px; color: #1e293b; display: block; line-height: 1.4;">{{ getSelectedOfferProductName(selectedOfferProduct) }}</b>
                </div>
                <button v-if="!isOfferEditMode" type="button" @click="selectedOfferProduct = null" class="img-remove-btn" style="padding: 6px 12px; font-size: 11px; margin: 0;">
                  Thay đổi
                </button>
              </div>
            </div>

            <!-- Variant selection -->
            <div class="form-group" v-if="selectedOfferProduct">
              <label>Bước 2: Chọn Biến thể / Cấu hình áp dụng ưu đãi <span class="required">*</span></label>
              <p v-if="offerFieldErrors.id_bienthe" class="field-error" style="margin-bottom: 4px;">{{ offerFieldErrors.id_bienthe }}</p>
              
              <!-- Selected variant badge/info (similar to Selected Laptop box) -->
              <div v-if="offerForm.id_bienthe && isVariantCollapsed" class="selected-product-info" style="display: flex; justify-content: space-between; align-items: center; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px 16px; border-radius: 10px;">
                <div style="text-align: left;">
                  <span style="font-size: 10px; color: #166534; font-weight: 700; display: block; text-transform: capitalize; letter-spacing: 0.05em; margin-bottom: 2px;">Cấu Hình Đang Chọn:</span>
                  <b style="font-size: 13px; color: #1e293b; display: block; line-height: 1.4;">{{ getSelectedVariantName(offerForm.id_bienthe) }}</b>
                  <span style="font-size: 11px; color: #166534; font-weight: 700; margin-top: 4px; display: block;">
                    Giá bán lẻ lẻ: {{ getSelectedVariantPrice(offerForm.id_bienthe) }}
                  </span>
                </div>
                <button v-if="!isOfferEditMode" type="button" @click="isVariantCollapsed = false" class="img-change" style="padding: 6px 12px; font-size: 11px; margin: 0; background: white; border: 1px solid #cbd5e1; border-radius: 6px;">
                  Thay đổi
                </button>
              </div>

              <!-- Available variant grid to select from -->
              <div v-else>
                <div v-if="groupedOfferVariants.length" class="variant-offer-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 4px;">
                  <div 
                    v-for="v in groupedOfferVariants" 
                    :key="v.configName"
                    class="variant-offer-card"
                    :class="{ 
                      selected: isVariantSelected(v),
                      disabled: isOfferEditMode && !isVariantSelected(v)
                    }"
                    @click="selectVariantAction(v)"
                    style="border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px; cursor: pointer; transition: all 0.2s; position: relative; text-align: left; background: white;"
                  >
                    <!-- Checkbox circle badge -->
                    <div 
                      class="variant-select-chk" 
                      style="position: absolute; top: 10px; right: 10px; width: 18px; height: 18px; border-radius: 50%; border: 1.5px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-size: 10px; color: white; background: white; font-weight: bold; transition: all 0.2s;"
                      :style="isVariantSelected(v) ? { borderColor: '#3b82f6', backgroundColor: '#3b82f6' } : {}"
                    >
                      <span v-if="isVariantSelected(v)">✓</span>
                    </div>
                    
                    <div style="font-weight: 700; font-size: 12px; color: #1e293b; padding-right: 20px; line-height: 1.4; transition: color 0.2s;">
                      {{ v.configName }}
                    </div>
                    
                    <div style="font-size: 11px; font-weight: 800; color: #2563eb; margin-top: 8px;">
                      Lẻ: {{ Number(v.gia).toLocaleString('vi-VN') }}đ
                    </div>
                  </div>
                </div>
                <div v-else style="padding: 14px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; text-align: center; color: #64748b; font-size: 12.5px;">
                  Sản phẩm laptop này chưa có cấu hình biến thể nào.
                </div>
              </div>
            </div>

            <!-- Combo selection -->
            <div class="form-group">
              <label>Bước 3: Chọn Combo phụ kiện ưu đãi tương ứng <span class="required">*</span></label>
              <select v-model="offerForm.id_combo" :class="{ 'input-error': offerFieldErrors.id_combo }">
                <option value="">-- Chọn Combo phụ kiện đã tạo --</option>
                <option v-for="c in combos" :key="c.id_combo" :value="c.id_combo">
                  {{ c.ten_combo }} — (Bán lẻ: {{ Number(c.giakhuyenmai).toLocaleString('vi-VN') }}đ)
                </option>
              </select>
              <p v-if="offerFieldErrors.id_combo" class="field-error">{{ offerFieldErrors.id_combo }}</p>
              <small style="color: #64748b; font-size:11px; margin-top:2px;">⚠️ Phải tạo Combo phụ kiện tại Tab 1 trước khi gắn ưu đãi tại đây.</small>
            </div>

            <!-- Offer Type radio list -->
            <div class="form-group" style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 4px;">
              <label>Bước 4: Loại ưu đãi</label>
              <div class="offer-type-cards" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 10px; width: 100%;">
                <label class="offer-type-card" :class="{ active: offerForm.loai_uudai === 'free' }" style="display: flex; flex-direction: column; align-items: flex-start; padding: 16px 20px; border-radius: 12px; border: 2px solid #e2e8f0; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.02); position: relative; text-align: left; font-weight: normal;">
                  <input type="radio" value="free" v-model="offerForm.loai_uudai" style="position: absolute; top: 16px; right: 16px; width: 18px; height: 18px; accent-color: #2563eb; margin: 0; cursor: pointer;" />
                  <span style="font-size: 20px; margin-bottom: 8px;">🎁</span>
                  <b style="font-size: 14px; color: #1e293b; font-weight: 700; margin-bottom: 4px; text-transform: none; letter-spacing: normal;">Tặng miễn phí (0đ)</b>
                  <span style="font-size: 11.5px; color: #64748b; line-height: 1.4;">Combo phụ kiện con được tặng 100% miễn phí khi khách mua sản phẩm này.</span>
                </label>
                <label class="offer-type-card" :class="{ active: offerForm.loai_uudai === 'discount' }" style="display: flex; flex-direction: column; align-items: flex-start; padding: 16px 20px; border-radius: 12px; border: 2px solid #e2e8f0; background: white; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.02); position: relative; text-align: left; font-weight: normal;">
                  <input type="radio" value="discount" v-model="offerForm.loai_uudai" style="position: absolute; top: 16px; right: 16px; width: 18px; height: 18px; accent-color: #2563eb; margin: 0; cursor: pointer;" />
                  <span style="font-size: 20px; margin-bottom: 8px;">🏷️</span>
                  <b style="font-size: 14px; color: #1e293b; font-weight: 700; margin-bottom: 4px; text-transform: none; letter-spacing: normal;">Mua kèm giá đặc biệt</b>
                  <span style="font-size: 11.5px; color: #64748b; line-height: 1.4;">Khách hàng được mua kèm combo phụ kiện này với một mức giá ưu đãi tự chọn.</span>
                </label>
              </div>
            </div>

            <!-- Custom price if discount -->
            <div class="form-group" v-if="offerForm.loai_uudai === 'discount'">
              <label>Giá trị ưu đãi mua kèm (đ) <span class="required">*</span></label>
              <input type="number" v-model="offerForm.giakhuyenmai_override" placeholder="VD: 99000" :class="{ 'input-error': offerFieldErrors.giakhuyenmai_override }" />
              <p v-if="offerFieldErrors.giakhuyenmai_override" class="field-error">{{ offerFieldErrors.giakhuyenmai_override }}</p>
            </div>

            <!-- Description tag (VIP Banner display text) -->
            <div class="form-group" style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 4px;">
              <label>Bước 5: Tiêu đề / Nội dung hiển thị VIP Banner (Khách hàng xem) <span class="required">*</span></label>
              <input v-model="offerForm.mota_uudai" placeholder="VD: Tặng combo Phím Chuột Gaming trị giá 650k khi chọn phiên bản RAM 16GB" />
              <small style="color: #64748b; font-size:11px; margin-top:2px;">Dòng này sẽ được hiển thị nổi bật tại trang chi tiết sản phẩm chính để thu hút khách nâng cấp cấu hình.</small>
            </div>

            <!-- Limit & Expiry Date (2 columns) -->
            <div class="form-cols-2" style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 4px;">
              <div class="form-group">
                <label>Giới hạn số lượng (suất)</label>
                <input v-model="offerForm.gioi_han_soluong" type="number" placeholder="Không giới hạn" :class="{ 'input-error': offerFieldErrors.gioi_han_soluong }" />
                <p v-if="offerFieldErrors.gioi_han_soluong" class="field-error">{{ offerFieldErrors.gioi_han_soluong }}</p>
                <small style="color: #64748b; font-size:11px; margin-top:2px; display:block;">Ưu đãi tự động đóng khi đạt giới hạn.</small>
              </div>

              <div class="form-group">
                <label>Thời hạn ưu đãi</label>
                <input v-model="offerForm.ngay_het_han" type="datetime-local" :class="{ 'input-error': offerFieldErrors.ngay_het_han }" />
                <p v-if="offerFieldErrors.ngay_het_han" class="field-error">{{ offerFieldErrors.ngay_het_han }}</p>
                <small style="color: #64748b; font-size:11px; margin-top:2px; display:block;">Ưu đãi tự động hết hạn sau thời điểm này.</small>
              </div>
            </div>

            <!-- Status -->
            <div class="form-group" style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 4px;">
              <label>Trạng thái hoạt động</label>
              <select v-model="offerForm.trangthai">
                <option :value="1">Kích hoạt (Đang diễn ra)</option>
                <option :value="0">Tạm ẩn (Tạm dừng chiến dịch)</option>
              </select>
            </div>

          <p v-if="offerFormError" class="form-error">⚠ {{ offerFormError }}</p>

          <!-- Footer actions -->
          <div class="inline-form-footer">
            <button class="btn-cancel" @click="closeOfferModal">Hủy</button>
            <button class="btn-submit" @click="submitOfferForm" :disabled="isOfferSubmitting">
              {{ isOfferSubmitting ? 'Đang lưu...' : (isOfferEditMode ? 'Lưu thay đổi' : 'Tạo ưu đãi') }}
            </button>
          </div>
      </div>
    </template><!-- end offer-form -->

    <!-- ══════════════════════════════════════════════════════
         POPUP MODAL: CHI TIẾT CHIẾN DỊCH ƯU ĐÃI
    ══════════════════════════════════════════════════════ -->
    <teleport to="body">
      <div v-if="showDetailModal && selectedOfferDetail" class="modal-overlay" @click.self="closeDetailModal">
        <div class="modal">
          <div class="modal-header">
            <h3>🔍 Chi tiết Chiến dịch Ưu đãi</h3>
            <button class="modal-close" @click="closeDetailModal">&times;</button>
          </div>
          <div class="modal-body" style="gap: 14px;">
            <div class="detail-modal-card">
              <!-- Banner Title Section -->
              <div class="vip-banner-box" style="max-width: 100%; width: 100%; box-sizing: border-box; margin-bottom: 8px;">
                📢 <b>Nội dung hiển thị VIP Banner (Khách xem):</b><br/>
                <span style="font-size: 13px; display: inline-block; margin-top: 6px; font-weight: normal; color: #475569;">
                  {{ selectedOfferDetail.mota_uudai || 'Chưa thiết lập banner' }}
                </span>
              </div>

              <!-- Section 1: Activation Condition -->
              <div class="detail-section">
                <div class="detail-section-title">💻 Điều kiện kích hoạt (Sản phẩm chính)</div>
                <div class="detail-info-row">
                  <span class="detail-info-label">Laptop áp dụng</span>
                  <span class="detail-info-value" style="color: #2563eb; font-weight: bold;">{{ selectedOfferDetail.sanpham_ten }}</span>
                </div>
                <div class="detail-info-row">
                  <span class="detail-info-label">Phiên bản / Cấu hình</span>
                  <span class="detail-info-value">
                    <span class="variant-spec-badge" style="margin: 0; font-size: 11px; padding: 4px 8px;">
                      💻 {{ getConfigName(selectedOfferDetail.ten_bienthe) }}
                    </span>
                  </span>
                </div>
              </div>

              <!-- Section 2: Applied Combo details -->
              <div class="detail-section">
                <div class="detail-section-title">🎁 Combo quà tặng / mua kèm</div>
                <div class="detail-info-row">
                  <span class="detail-info-label">Combo áp dụng</span>
                  <span class="detail-info-value" style="color: #2563eb; font-weight: bold;">{{ selectedOfferDetail.combo_ten }}</span>
                </div>
                <div class="detail-info-row">
                  <span class="detail-info-label">Giá trị Combo gốc</span>
                  <span class="detail-info-value" style="text-decoration: line-through; color: #94a3b8;">
                    {{ Number(selectedOfferDetail.combo_gia).toLocaleString('vi-VN') }}đ
                  </span>
                </div>
                <div class="detail-info-row">
                  <span class="detail-info-label">Loại ưu đãi</span>
                  <span class="detail-info-value">
                    <span class="badge" :class="selectedOfferDetail.loai_uudai === 'free' ? 'badge-green' : 'badge-orange'" style="padding: 4px 8px; font-size: 10px;">
                      {{ selectedOfferDetail.loai_uudai === 'free' ? '🎁 Tặng 0đ' : '🏷️ Mua kèm' }}
                    </span>
                  </span>
                </div>
                <div class="detail-info-row">
                  <span class="detail-info-label">Giá ưu đãi thực tế</span>
                  <span class="detail-info-value" :class="{ 'free-text': selectedOfferDetail.loai_uudai === 'free' }" style="font-size: 14.5px; font-weight: 800; color: #ef4444;">
                    {{ selectedOfferDetail.loai_uudai === 'free' ? 'Miễn phí (0đ)' : Number(selectedOfferDetail.giakhuyenmai_override).toLocaleString('vi-VN') + 'đ' }}
                  </span>
                </div>
              </div>

              <!-- Section 3: Limits & Duration -->
              <div class="detail-section">
                <div class="detail-section-title">📊 Giới hạn & Thời gian chiến dịch</div>
                <div class="detail-info-row">
                  <span class="detail-info-label">Đã sử dụng</span>
                  <span class="detail-info-value">
                    <b>{{ selectedOfferDetail.da_su_dung }}</b> / {{ selectedOfferDetail.gioi_han_soluong || 'Không giới hạn' }} suất
                  </span>
                </div>
                <div class="detail-info-row" v-if="selectedOfferDetail.gioi_han_soluong">
                  <span class="detail-info-label">Tiến độ sử dụng</span>
                  <span class="detail-info-value">
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <div class="usage-bar-bg" style="width: 100px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; display: inline-block;">
                        <div class="usage-bar-fill" :style="{ width: Math.min((selectedOfferDetail.da_su_dung / selectedOfferDetail.gioi_han_soluong) * 100, 100) + '%' }" style="height: 100%; background: linear-gradient(90deg, #2563eb, #3b82f6); border-radius: 3px;"></div>
                      </div>
                      <span style="font-size: 11px; color: #475569; font-weight: bold;">
                        {{ Math.round((selectedOfferDetail.da_su_dung / selectedOfferDetail.gioi_han_soluong) * 100) }}%
                      </span>
                    </div>
                  </span>
                </div>
                <div class="detail-info-row">
                  <span class="detail-info-label">Thời hạn hết hạn</span>
                  <span class="detail-info-value" style="font-weight: 600;">
                    {{ selectedOfferDetail.ngay_het_han ? formatOfferDate(selectedOfferDetail.ngay_het_han) : 'Vô thời hạn ♾️' }}
                  </span>
                </div>
              </div>

              <!-- Section 4: Status and Validity -->
              <div class="detail-section">
                <div class="detail-section-title">⚙️ Trạng thái & Tính hợp lệ</div>
                <div class="detail-info-row">
                  <span class="detail-info-label">Trạng thái cấu hình</span>
                  <span class="detail-info-value">
                    <span class="badge" :class="selectedOfferDetail.trangthai === 1 ? 'badge-success' : 'badge-draft'" style="padding: 4px 8px; font-size: 10px;">
                      {{ selectedOfferDetail.trangthai === 1 ? 'Đang kích hoạt' : 'Tạm ẩn' }}
                    </span>
                  </span>
                </div>
                <div class="detail-info-row">
                  <span class="detail-info-label">Hiệu lực hệ thống</span>
                  <span class="detail-info-value">
                    <span class="badge" :class="selectedOfferDetail.is_valid ? 'badge-active-green' : 'badge-expired-red'" style="padding: 4px 8px; font-size: 10px;">
                      {{ selectedOfferDetail.is_valid ? '🟢 Khả dụng' : '🔴 Vô hiệu' }}
                    </span>
                  </span>
                </div>
                <div v-if="selectedOfferDetail.is_combo_in_stock === false" class="detail-info-row" style="background: #fff1f2; border-radius: 8px; padding: 10px 12px; margin-top: 8px; border: 1px solid #fecdd3;">
                  <span class="detail-info-label" style="color: #e11d48; font-weight: bold;">Cảnh báo kho hàng</span>
                  <span class="detail-info-value" style="color: #e11d48; font-weight: bold;">
                    ⚠️ Thiếu hàng phụ kiện trong combo!
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="closeDetailModal" style="padding: 10px 20px; font-size: 13px;">Đóng</button>
          </div>
        </div>
      </div>
    </teleport>

  </div>
</template>

<style scoped>
/* ── Admin Page Layout & Common Styles ── */
.combo-management {
  padding: 32px 48px;
  background: #f5f7fb;
  min-height: 100vh;
  font-family: 'Segoe UI', sans-serif;
  box-sizing: border-box;
}

/* ── Inline Form Header (thay thế modal header) ── */
.inline-form-header {
  margin-bottom: 28px;
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

/* ── Inline Form Body ── */
.inline-form-body {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 28px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}

/* ── Inline Form Footer ── */
.inline-form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
}

.top h1 {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px;
}

.top p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.add-btn {
  background: linear-gradient(135deg, #2563eb, #2563eb);
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

.stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  min-height: 136px;
  border-radius: 16px;
  padding: 26px 28px;
  display: flex;
  align-items: center;
  gap: 18px;
  border: 1px solid transparent;
  position: relative;
  overflow: hidden;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
}

.stat-card::after {
  content: '';
  position: absolute;
  width: 150px;
  height: 150px;
  border-radius: 999px;
  right: -28px;
  top: -54px;
  background: rgba(255, 255, 255, 0.13);
  pointer-events: none;
}

.stat-card.stat-blue {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  color: #fff;
}

.stat-card.stat-teal {
  background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
  color: #fff;
}

.stat-card.stat-orange {
  background: linear-gradient(135deg, #c2410c 0%, #f97316 100%);
  color: #fff;
}

.stat-card p {
  font-size: 12px;
  line-height: 1.2;
  color: rgba(255, 255, 255, 0.88);
  font-weight: 800;
  letter-spacing: .03em;
  text-transform: capitalize;
  margin: 0 0 20px;
}

.stat-card b {
  font-size: 34px;
  line-height: 1;
  font-weight: 800;
  color: #fff;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #fff;
  background: rgba(255, 255, 255, 0.18);
}

.stat-icon svg {
  width: 24px;
  height: 24px;
}

.filter-bar {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}

.search-wrap {
  flex: 1;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 13px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
  pointer-events: none;
}

.search-wrap input {
  width: 100%;
  padding: 10px 14px 10px 38px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 13px;
  color: #0f172a;
  outline: none;
  transition: border-color .2s;
  box-sizing: border-box;
}

.search-wrap input:focus {
  border-color: #2563eb;
}

/* ── Combo Grid & Cards ── */
.combo-list-panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  overflow-x: auto;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
}

.combo-admin-table {
  width: 100%;
  min-width: 980px;
  border-collapse: collapse;
  text-align: left;
}

.combo-admin-table thead {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.combo-admin-table th {
  padding: 14px 16px;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
  text-transform: capitalize;
  letter-spacing: .04em;
}

.combo-admin-table td {
  padding: 14px 16px;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.combo-admin-table tbody tr {
  transition: background-color .2s ease;
}

.combo-admin-table tbody tr:hover {
  background: #f8fafc;
}

.combo-admin-table tbody tr:last-child td {
  border-bottom: none;
}

.combo-admin-table tr.row-inactive {
  opacity: .68;
  background: #fafafa;
}

.combo-list-thumb {
  width: 58px;
  height: 58px;
  border-radius: 10px;
  object-fit: cover;
  display: block;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}

.combo-list-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 240px;
}

.combo-list-info b {
  color: #0f172a;
  font-size: 14px;
  font-weight: 800;
  line-height: 1.35;
}

.combo-list-info span {
  color: #64748b;
  font-size: 12.5px;
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.combo-product-stack {
  display: flex;
  flex-direction: column;
  gap: 5px;
  max-width: 360px;
}

.combo-product-stack span,
.combo-product-stack small {
  color: #475569;
  font-size: 12.5px;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.combo-product-stack span::before {
  content: "•";
  color: #2563eb;
  font-weight: 900;
  margin-right: 7px;
}

.combo-list-price {
  color: #2563eb;
  font-size: 15px;
  font-weight: 800;
  white-space: nowrap;
}

.combo-status-stack {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
}

.combo-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
}

.pagination-summary {
  color: #64748b;
  font-size: 12.5px;
  font-weight: 600;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 6px;
}

.page-btn {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  border: 1px solid #dbe3ef;
  background: #ffffff;
  color: #475569;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  transition: all .2s ease;
}

.page-btn:hover:not(:disabled),
.page-btn.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
  box-shadow: 0 8px 16px rgba(37, 99, 235, .18);
}

.page-btn:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.combo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 24px;
  margin-top: 24px;
}

.combo-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
  transition: all 0.3s ease;
  position: relative;
  display: flex;
  flex-direction: column;
}

.combo-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
}

.combo-card.inactive {
  opacity: 0.65;
}

.combo-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  z-index: 10;
}

.combo-badge.active {
  background: #f0fdf4;
  color: #166534;
}

.combo-badge.draft {
  background: #f1f5f9;
  color: #475569;
}

.combo-img {
  width: 100%;
  height: 180px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-bottom: 1px solid #f1f5f9;
}

.combo-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.combo-details {
  padding: 20px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.combo-details h3 {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
  text-align: left;
}

.combo-details .desc {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 16px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-align: left;
}

.products-list {
  background: #f8fafc;
  padding: 12px;
  border-radius: 12px;
  margin-bottom: 16px;
  flex: 1;
  text-align: left;
}

.products-list h4 {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
  margin-bottom: 8px;
}

.products-list ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.products-list li {
  font-size: 12.5px;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.combo-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid #f1f5f9;
  padding-top: 16px;
}

.price-box {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.price-box .lbl {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
}

.price-box .price {
  font-size: 17px;
  font-weight: 800;
  color: #2563eb;
}

.actions {
  display: flex;
  gap: 6px;
}

.act-btn {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid #cbd5e1;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #475569;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.act-btn svg {
  width: 14px;
  height: 14px;
}

.act-btn:hover {
  background: #f8fafc;
  border-color: #2563eb;
  color: #2563eb;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
  transform: translateY(-1px);
}

.act-btn.danger:hover {
  background: #fff5f5;
  border-color: #ef4444;
  color: #ef4444;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.08);
  transform: translateY(-1px);
}

.act-btn.info {
  border-color: #bfdbfe !important;
  background: #eff6ff !important;
  color: #1d4ed8 !important;
}

.act-btn.info:hover {
  background: #dbeafe !important;
  border-color: #2563eb !important;
  color: #2563eb !important;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08) !important;
  transform: translateY(-1px);
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 80px 20px;
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 18px;
  color: #1e293b;
  margin-bottom: 8px;
}

.empty-state p {
  color: #64748b;
  font-size: 14px;
}

/* ── Modals & Overlay (Teleported to body) ── */
:global(.modal-overlay) {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, .6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 16px;
  backdrop-filter: blur(2px);
}

:global(.modal) {
  background: white;
  border-radius: 18px;
  width: 100%;
  max-width: 600px;
  box-shadow: 0 32px 80px rgba(0, 0, 0, .22);
  animation: modalIn .22s cubic-bezier(.22, 1, .36, 1);
  max-height: 94vh;
  overflow-y: auto;
  box-sizing: border-box;
}

:global(.modal-wide) {
  max-width: 800px;
}

@keyframes modalIn {
  from {
    opacity: 0;
    transform: translateY(16px) scale(.97);
  }
  to {
    opacity: 1;
    transform: none;
  }
}

:global(.modal-header) {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px 16px;
  border-bottom: 1px solid #f1f5f9;
  position: sticky;
  top: 0;
  background: white;
  z-index: 10;
  border-radius: 18px 18px 0 0;
}

:global(.modal-header h3) {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

:global(.modal-close) {
  background: none;
  border: none;
  font-size: 24px;
  color: #94a3b8;
  cursor: pointer;
  line-height: 1;
  padding: 0;
}

:global(.modal-close:hover) {
  color: #0f172a;
}

:global(.modal-body) {
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-sizing: border-box;
}

:global(.modal-footer) {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px 20px;
  border-top: 1px solid #f1f5f9;
  position: sticky;
  bottom: 0;
  background: white;
  z-index: 10;
  border-radius: 0 0 18px 18px;
}

/* ── Form components ── */
/* ── Form Layouts & Grids ── */
.form-cols-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

/* Card-like wrappers for each form-group in inline-form-body */
.inline-form-body > .form-group,
.inline-form-body > .products-selection-section,
.inline-form-body > .form-cols-2 > .form-group,
.inline-form-body > .form-row > .form-group {
  background: white;
  border-radius: 14px;
  border: 1px solid #edf0f7;
  padding: 22px 24px !important;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  box-sizing: border-box;
}

/* Specific styling for nested form groups within grid to prevent double shadow/borders */
.form-cols-2 > .form-group {
  margin-bottom: 0 !important;
}

.inline-form-body .form-group label {
  font-size: 11.5px;
  font-weight: 700;
  color: #64748b;
  letter-spacing: 0.06em;
  text-transform: capitalize;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.required {
  color: #ef4444;
  margin-left: 2px;
}

/* Inputs, Selects and Textareas */
.inline-form-body .form-group input:not([type='file']),
.inline-form-body .form-group select,
.inline-form-body .form-group textarea {
  padding: 12px 16px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  font-size: 14px;
  color: #0f172a;
  outline: none;
  transition: all 0.2s ease;
  background: #f9fafb;
  font-family: inherit;
  width: 100%;
  box-sizing: border-box;
}

.inline-form-body .form-group input:focus,
.inline-form-body .form-group select:focus,
.inline-form-body .form-group textarea:focus {
  border-color: #2563eb;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.inline-form-body .form-group input.input-error,
.inline-form-body .form-group select.input-error {
  border-color: #f87171 !important;
  background: #fff5f5 !important;
}

/* Custom pills for Category tabs in Accessory selection */
.pool-category-tabs {
  display: flex;
  gap: 8px;
  margin-top: 10px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.tab-btn {
  padding: 8px 16px;
  border-radius: 20px;
  border: 1px solid #cbd5e1;
  background: white;
  color: #475569;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.tab-btn:hover {
  border-color: #2563eb;
  color: #2563eb;
  background: #f5f3ff;
}

.tab-btn.active {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: white;
  border-color: #2563eb;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

/* Accessory selection tags */
.selected-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}

.p-tag {
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  color: #1d4ed8;
  padding: 8px 14px;
  border-radius: 20px;
  font-size: 12.5px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
  transition: all 0.2s ease;
}

.p-tag:hover {
  background: #e0e7ff;
  transform: translateY(-1px);
}

.p-tag button {
  background: #c7d2fe;
  border: none;
  font-size: 14px;
  color: #1d4ed8;
  cursor: pointer;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  font-weight: 700;
  line-height: 1;
  transition: all 0.2s ease;
}

.p-tag button:hover {
  background: #93c5fd;
  color: white;
}

/* Pool Search */
.pool-search-box {
  margin-bottom: 12px;
}

.pool-search-box input {
  width: 100%;
  padding: 12px 16px;
  border-radius: 10px;
  border: 1.5px solid #cbd5e1;
  font-size: 13.5px;
  font-family: inherit;
  outline: none;
  transition: all 0.2s ease;
  background: white;
  box-sizing: border-box;
}

.pool-search-box input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Product selection pool grid */
.products-pool {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 12px;
  max-height: 240px;
  overflow-y: auto;
  padding: 8px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1.5px solid #cbd5e1;
}

.pool-item {
  background: white;
  border: 1.5px solid #e2e8f0;
  padding: 12px 16px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.pool-item:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
  transform: translateY(-1px);
}

.pool-item.selected {
  border-color: #2563eb;
  background: #f5f3ff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.06);
}

.pool-item .chk {
  width: 20px;
  height: 20px;
  border-radius: 6px;
  border: 2px solid #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  color: white;
  background: white;
  font-weight: 700;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.pool-item.selected .chk {
  border-color: #2563eb;
  background: #2563eb;
}

.pool-item .p-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
  flex: 1;
  min-width: 0;
  text-align: left;
}

.pool-item .p-info b {
  font-size: 12.5px;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 600;
}

.pool-item .p-info span {
  font-size: 11px;
  color: #64748b;
}

.pool-item .p-info .p-pool-price {
  font-size: 12px;
  font-weight: 700;
  color: #2563eb !important;
  margin-top: 4px;
}

/* Upload zone premium */
.upload-zone {
  border: 2px dashed #c7d2fe;
  background: linear-gradient(135deg, #f0f1ff 0%, #fafbff 100%);
  border-radius: 14px;
  padding: 40px 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.25s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.upload-zone:hover {
  border-color: #2563eb;
  background: linear-gradient(135deg, #ebe8ff 0%, #f0f4ff 100%);
}

.upload-zone svg {
  width: 44px;
  height: 44px;
  color: #3b82f6;
}

.upload-zone p {
  font-size: 14px;
  color: #475569;
  margin: 0;
  font-weight: 500;
}

.upload-zone p span {
  color: #2563eb;
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 3px;
}

.upload-zone small {
  font-size: 12px;
  color: #94a3b8;
}

/* Image preview premium style */
.img-preview-wrap {
  display: flex;
  align-items: center;
  gap: 20px;
  background: #f8fafc;
  border-radius: 12px;
  padding: 16px;
  border: 1.5px solid #e2e8f0;
}

.img-preview {
  width: 96px;
  height: 96px;
  object-fit: cover;
  border-radius: 10px;
  flex-shrink: 0;
  border: 1px solid #cbd5e1;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.img-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.img-change {
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: white;
  font-size: 12.5px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s ease;
}

.img-change:hover {
  border-color: #2563eb;
  color: #2563eb;
  background: #f5f3ff;
}

.img-remove-btn {
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid #fecaca;
  background: #fef2f2;
  font-size: 12.5px;
  font-weight: 600;
  color: #ef4444;
  cursor: pointer;
  transition: all 0.2s ease;
}

.img-remove-btn:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}

/* Buttons style */
.btn-cancel {
  padding: 12px 24px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  background: white;
  font-size: 13.5px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-cancel:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #0f172a;
}

.btn-submit {
  padding: 12px 26px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: white;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.2);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ── Tab Navigation Styles ── */
.tabs-navigation {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 28px;
  width: 100%;
}

.tab-nav-list {
  display: flex;
  gap: 8px;
  padding: 4px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f1f5f9;
}

@media (max-width: 768px) {
  .tabs-navigation {
    align-items: stretch;
    flex-direction: column;
  }

  .tab-nav-list {
    overflow-x: auto;
  }

  .tabs-navigation > .add-btn {
    align-self: flex-end;
  }
}

.tab-nav-btn {
  padding: 10px 24px;
  font-size: 13.5px;
  font-weight: 600;
  background: transparent;
  border: none;
  color: #64748b;
  border-radius: 9px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  outline: none;
}

.tab-nav-btn:hover {
  color: #0f172a;
}

.tab-nav-btn.active {
  background: white;
  color: #2563eb;
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.05);
}

/* ── Promotional Offers Table Styles ── */
.offers-table-wrap {
  background: white;
  border-radius: 16px;
  border: 1px solid #edf2f7;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
  overflow-x: auto;
  margin-top: 16px;
}

.offers-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}

.offers-table th {
  background: #f8fafc;
  padding: 16px 20px;
  font-weight: 700;
  color: #475569;
  border-bottom: 1.5px solid #edf2f7;
  text-transform: capitalize;
  font-size: 11px;
  letter-spacing: 0.05em;
}

.offers-table td {
  padding: 18px 20px;
  border-bottom: 1px solid #edf2f7;
  color: #0f172a;
  vertical-align: middle;
}

.offers-table tr {
  transition: all 0.2s ease;
}

.offers-table tr:hover {
  background: #fbfcfe;
}

.offers-table tr.row-inactive {
  opacity: 0.55;
  background: #f8fafc;
}

/* ── Rich Target Product & Callouts ── */
.product-target {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
}

.product-name {
  font-weight: 700;
  color: #1e293b;
  font-size: 13px;
  line-height: 1.45;
  text-align: left;
  max-width: 200px !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  display: inline-block !important;
}

.vip-banner-box {
  background: linear-gradient(135deg, #fff1f2 0%, #fff5f5 100%);
  border-left: 4px solid #f43f5e;
  padding: 10px 14px;
  border-radius: 4px 10px 10px 4px;
  font-size: 12px;
  color: #9f1239;
  font-weight: 600;
  line-height: 1.5;
  text-align: left;
  max-width: 220px;
  word-break: break-word;
  box-shadow: 0 2px 6px rgba(244, 63, 94, 0.04);
}

/* Specific truncation for VIP Banner column inside table */
.offers-table .vip-banner-box {
  max-width: 160px !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  display: inline-block !important;
}

/* ── Limit & Expiry Styling ── */
.limit-expiry {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
}

/* ── Badges ── */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
  line-height: 1;
  text-transform: capitalize;
  letter-spacing: 0.03em;
}

.badge-green {
  background: #dcfce7;
  color: #1d4ed8;
  border: 1.5px solid #bbf7d0;
}

.badge-active-green {
  background: #dcfce7;
  color: #1d4ed8;
  border: 1.5px solid #bbf7d0;
}

.badge-expired-red {
  background: #fee2e2;
  color: #b91c1c;
  border: 1.5px solid #fecaca;
}

.badge-orange {
  background: #ffedd5;
  color: #9a3412;
  border: 1.5px solid #fed7aa;
}

.badge-success {
  background: #dbeafe;
  color: #1e40af;
  border: 1.5px solid #bfdbfe;
}

.badge-draft {
  background: #f1f5f9;
  color: #475569;
  border: 1.5px solid #cbd5e1;
}

/* ── Tag and Badge Styles ── */
.combo-tag {
  background: #f5f3ff;
  color: #5b21b6;
  border: 1.5px solid #ddd6fe;
  padding: 8px 12px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 12.5px;
  line-height: 1.4;
  display: inline-block !important;
  max-width: 180px !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.01);
}

.variant-spec-badge {
  background: #eff6ff;
  color: #1d4ed8;
  padding: 6px 10px;
  border-radius: 6px;
  font-weight: 700;
  font-size: 11.5px;
  border: 1.5px solid #bfdbfe;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.price-text {
  font-size: 14px;
  font-weight: 800;
  color: #ef4444;
}

.free-text {
  color: #2563eb !important;
}

.mota-cell {
  max-width: 250px;
}

.mota-text {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
  color: #64748b;
}

.block {
  display: block;
}

.text-gray {
  color: #64748b;
}

/* ── Variant Offer Cards Styles ── */
.variant-offer-card {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.variant-offer-card:hover:not(.disabled) {
  border-color: #cbd5e1 !important;
  background: #f8fafc !important;
  transform: translateY(-1px);
}

.variant-offer-card.selected {
  border-color: #3b82f6 !important;
  background: #eff6ff !important;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08);
}

.variant-offer-card.selected .variant-select-chk {
  border-color: #3b82f6 !important;
  background: #3b82f6 !important;
}

.variant-offer-card.disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f1f5f9 !important;
}

/* ── Offer Type Cards Styles ── */
.offer-type-card:hover {
  border-color: #cbd5e1 !important;
  background: #f8fafc !important;
  transform: translateY(-1px);
}

.offer-type-card.active {
  border-color: #2563eb !important;
  background: #f5f3ff !important;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08) !important;
}

/* ── Detail Modal styling ── */
.detail-modal-card {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
}

.detail-section {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 18px;
  text-align: left;
}

.detail-section-title {
  font-size: 10.5px;
  font-weight: 700;
  text-transform: capitalize;
  color: #64748b;
  letter-spacing: 0.05em;
  margin-bottom: 8px;
  border-bottom: 1px solid #edf2f7;
  padding-bottom: 6px;
}

.detail-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px dashed #e2e8f0;
  font-size: 13px;
}

.detail-info-row:last-child {
  border-bottom: none;
}

.detail-info-label {
  font-weight: 500;
  color: #475569;
}

.detail-info-value {
  font-weight: 600;
  color: #0f172a;
}
</style>
