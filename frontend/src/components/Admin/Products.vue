<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import * as XLSX from 'xlsx'
import api from '@/services/api'
import { storageUrl } from '@/services/urls'
import swal from '@/services/swal'

/* ═══════════════════════════════════════
   DANH SÁCH SẢN PHẨM
═══════════════════════════════════════ */
const searchQuery = ref('')
const selectedStatus = ref('')
const selectedCategory = ref('')
const selectedParentTab = ref('')

const selectParentTab = (id_danhmuc_cha) => {
  selectedParentTab.value = id_danhmuc_cha
  selectedCategory.value = ''
}

const isOpenStatusDropdown = ref(false)
const isOpenCategoryDropdown = ref(false)

const getSelectedCategoryLabel = () => {
  if (!selectedCategory.value) return 'Tất cả danh mục'
  const found = categories.value.find(c => String(c.id_danhmuc) === String(selectedCategory.value))
  return found ? found.ten_danhmuc : 'Tất cả danh mục'
}

const closeDropdowns = (e) => {
  if (!e.target.closest('.custom-dropdown')) {
    isOpenStatusDropdown.value = false
    isOpenCategoryDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeDropdowns)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', closeDropdowns)
})

const currentPage = ref(1)
const PER_PAGE = 10

const products = ref([])
const categories = ref([])
const parentCategories = ref([])
const childCategories = ref([])
const allBrands = ref([]) // Lưu toàn bộ brand
const brands = ref([]) // Hiển thị trên select
const colors = ref([])
const readingExtraImages = ref(false)
const variantLoading = ref(false)
const isExporting = ref(false)
const isImporting = ref(false)
const isBulkDeleting = ref(false)
const selectedProductIds = ref([])
const importExcelRef = ref(null)
const importVariantsExcelRef = ref(null)

const filteredCategoriesForDropdown = computed(() => {
  if (!selectedParentTab.value) return categories.value
  return categories.value.filter(c => String(c.id_danhmuc_cha) === String(selectedParentTab.value))
})

const filteredProducts = computed(() =>
  products.value.filter(p => {
    const s = searchQuery.value.toLowerCase()

    return (!s || p.name.toLowerCase().includes(s) || p.sku.toLowerCase().includes(s))
      && (!selectedStatus.value || p.status === selectedStatus.value)
      && (!selectedParentTab.value || String(p.parentCategoryId) === String(selectedParentTab.value))
      && (!selectedCategory.value || String(p.categoryId) === String(selectedCategory.value))
  })
)

const allVariants = computed(() =>
  products.value.flatMap(p => Array.isArray(p.bienThes) ? p.bienThes : [])
)

const totalProductStats = computed(() => allVariants.value.length)
const lowStockStats = computed(() =>
  allVariants.value.filter(v => Number(v.soluong ?? 0) < 10).length
)
const totalInventoryStats = computed(() =>
  allVariants.value.reduce((sum, v) => sum + Number(v.soluong ?? 0), 0)
)

const showLowStockModal = ref(false)
const selectedLowStockProduct = ref(null)
const showLowStockVariantsModal = ref(false)

const lowStockProducts = computed(() => {
  return products.value.filter(p => {
    return Array.isArray(p.bienThes) && p.bienThes.some(v => Number(v.soluong ?? 0) < 10)
  })
})

const openLowStockModal = () => {
  showLowStockModal.value = true
}

const closeLowStockModal = () => {
  showLowStockModal.value = false
}

const openLowStockVariantsModal = (product) => {
  selectedLowStockProduct.value = product
  showLowStockVariantsModal.value = true
}

const closeLowStockVariantsModal = () => {
  showLowStockVariantsModal.value = false
  selectedLowStockProduct.value = null
}

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredProducts.value.length / PER_PAGE))
)

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * PER_PAGE
  return filteredProducts.value.slice(start, start + PER_PAGE)
})

const allCurrentPageSelected = computed(() =>
  paginatedProducts.value.length > 0 &&
  paginatedProducts.value.every(p => selectedProductIds.value.includes(p.id))
)

const pageItems = computed(() => {
  const total = totalPages.value
  const current = currentPage.value

  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }

  if (current <= 3) {
    return [1, 2, 3, '...', total - 2, total - 1, total]
  }

  if (current >= total - 2) {
    return [1, 2, 3, '...', total - 2, total - 1, total]
  }

  return [1, '...', current - 1, current, current + 1, '...', total]
})

const goToPage = (page) => {
  if (page < 1) {
    currentPage.value = 1
    return
  }
  if (page > totalPages.value) {
    currentPage.value = totalPages.value
    return
  }
  currentPage.value = page
}

const toggleProductSelection = (id) => {
  selectedProductIds.value = selectedProductIds.value.includes(id)
    ? selectedProductIds.value.filter(item => item !== id)
    : [...selectedProductIds.value, id]
}

const toggleCurrentPageSelection = () => {
  const pageIds = paginatedProducts.value.map(p => p.id)

  if (allCurrentPageSelected.value) {
    selectedProductIds.value = selectedProductIds.value.filter(id => !pageIds.includes(id))
    return
  }

  selectedProductIds.value = Array.from(new Set([...selectedProductIds.value, ...pageIds]))
}

const clearProductSelection = () => {
  selectedProductIds.value = []
}

watch([searchQuery, selectedStatus, selectedCategory, selectedParentTab], () => {
  currentPage.value = 1
})

const getErrorMessage = (error, fallback) => {
  if (error?.response?.data?.message) return error.response.data.message

  const errors = error?.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const firstKey = Object.keys(errors)[0]
    if (firstKey && Array.isArray(errors[firstKey]) && errors[firstKey][0]) {
      return errors[firstKey][0]
    }
  }

  return fallback
}

/* ═══════════════════════════════════════
   NHẬP XUẤT EXCEL
   ═══════════════════════════════════════ */
const handleExportExcel = async () => {
  if (isExporting.value) return
  isExporting.value = true
  try {
    const res = await api.get('/admin/sanpham/export-inventory')
    const data = res.data

    if (!data || data.length === 0) {
      swal.warning('Không có dữ liệu', 'Không có dữ liệu để xuất')
      return
    }

    // Map data to user-friendly column names
    const worksheetData = data.map(item => ({
      'ID Biến Thể': item.id_bienthe,
      'Tên Sản Phẩm': item.tenSP,
      'Biến Thể': item.ten_bienthe,
      'Giá': item.gia,
      'Số Lượng': item.soluong
    }))

    const worksheet = XLSX.utils.json_to_sheet(worksheetData)
    const workbook = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(workbook, worksheet, "Inventory")

    // Download file
    XLSX.writeFile(workbook, `Kho_Hang_NextGen_${new Date().toLocaleDateString('vi-VN').replace(/\//g, '-')}.xlsx`)
  } catch (error) {
    console.error(error)
    swal.error('Lỗi', 'Lỗi khi xuất file Excel')
  } finally {
    isExporting.value = false
  }
}

const triggerImportExcel = () => {
  importExcelRef.value?.click()
}

const handleImportExcel = async (e) => {
  const file = e.target.files[0]
  if (!file) return

  isImporting.value = true
  const reader = new FileReader()

  reader.onload = async (event) => {
    try {
      const data = new Uint8Array(event.target.result)
      const workbook = XLSX.read(data, { type: 'array' })
      const firstSheet = workbook.Sheets[workbook.SheetNames[0]]
      const jsonData = XLSX.utils.sheet_to_json(firstSheet)

      // Transform to backend format
      const updates = jsonData.map(row => ({
        id_bienthe: row['ID Biến Thể'],
        gia: row['Giá'],
        soluong: row['Số Lượng']
      })).filter(item => item.id_bienthe)

      if (updates.length === 0) {
        swal.warning('Không hợp lệ', "Không tìm thấy dữ liệu hợp lệ trong file Excel")
        return
      }

      const isConfirmed = await swal.confirm('Xác nhận cập nhật', `Bạn có chắc muốn cập nhật ${updates.length} biến thể từ Excel?`)
      if (!isConfirmed) {
        return
      }

      const res = await api.post('/admin/sanpham/import-stock', { updates })
      swal.success('Thành công', res.data.message || 'Cập nhật tồn kho thành công')
      await fetchProducts()
    } catch (error) {
      console.error(error)
      swal.error('Lỗi', 'Lỗi khi đọc hoặc import file Excel. Hãy kiểm tra định dạng file.')
    } finally {
      isImporting.value = false
      e.target.value = ''
    }
  }

  reader.readAsArrayBuffer(file)
}

/* ═══════════════════════════════════════
   NHẬP XUẤT EXCEL BIẾN THỂ (TRONG MODAL)
   ═══════════════════════════════════════ */
const handleExportVariantsExcel = () => {
  const headers = tableHeaders.value
  if (!headers.length) {
    swal.warning('Không có dữ liệu', 'Không có thuộc tính để xuất')
    return
  }

  const data = generatedRows.value.map(row => {
    const item = {}
    headers.forEach(h => {
      item[h.label] = row.attrs[h.id] || ''
    })
    item['Giá'] = row.price
    item['Kho'] = row.stock
    return item
  })

  const ws = XLSX.utils.json_to_sheet(data)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, "Variants")
  XLSX.writeFile(wb, `${form.value.name || 'SanPham'}_BienThe.xlsx`)
}

const triggerImportVariantsExcel = () => {
  importVariantsExcelRef.value?.click()
}

const handleImportVariantsExcel = (e) => {
  const file = e.target.files[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (event) => {
    try {
      const data = new Uint8Array(event.target.result)
      const wb = XLSX.read(data, { type: 'array' })
      const ws = wb.Sheets[wb.SheetNames[0]]
      const jsonData = XLSX.utils.sheet_to_json(ws)

      const headers = tableHeaders.value
      let added = 0
      let skipped = 0

      jsonData.forEach(rowData => {
        const attrs = {}
        let hasValue = false
        headers.forEach(h => {
          const val = String(rowData[h.label] || '').trim()
          attrs[h.id] = val
          if (val) hasValue = true
        })

        if (!hasValue) return // Skip empty rows

        const key = buildVariantKeyFromAttrs(attrs, headers)
        const exists = generatedRows.value.some(r => buildVariantKeyFromAttrs(r.attrs, headers) === key)

        if (exists) {
          skipped++
        } else {
          generatedRows.value.push({
            id: `${Date.now()}-${Math.random()}`,
            attrs,
            price: rowData['Giá'] || '',
            stock: rowData['Kho'] || '',
            ten_bienthe: headers.map(h => attrs[h.id]).join(' - '),
            isExisting: false,
            _manualPrice: rowData['Giá'] !== undefined,
            _manualStock: rowData['Kho'] !== undefined
          })
          added++
        }
      })

      swal.success('Nhập biến thể', `Thêm mới ${added} cấu hình, bỏ qua ${skipped} cấu hình đã có.`)
    } catch (err) {
      console.error(err)
      swal.error('Lỗi', 'Lỗi khi đọc file Excel biến thể')
    } finally {
      e.target.value = ''
    }
  }
  reader.readAsArrayBuffer(file)
}

const fetchProducts = async () => {
  try {
    const res = await api.get('/sanpham')

    products.value = res.data.map(p => {
      const bienThes = Array.isArray(p.bien_thes) ? p.bien_thes : []
      const variantCount = bienThes.length

      return {
        id: p.id_sanpham,
        name: p.tenSP,
        sku: p.SKU || '',
        category: p.danh_muc?.ten_danhmuc || 'Chưa có danh mục',
        categoryId: p.id_danhmuc ?? '',
        parentCategoryId: p.danh_muc?.id_danhmuc_cha ?? '',
        brand: p.thuong_hieu?.ten_thuonghieu || 'Chưa có thương hiệu',
        totalVariants: variantCount,
        bienThes,
        status: String(p.trangthai) === '1' ? 'Đang bán' : 'Nháp',
        img: p.hinhanh
        ? storageUrl(p.hinhanh)
        : Array.isArray(p.hinh_anhs || p.hinhAnhs) && (p.hinh_anhs || p.hinhAnhs)[0]?.duongdan
          ? storageUrl((p.hinh_anhs || p.hinhAnhs)[0].duongdan)
          : 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=200',
      }
    })
  } catch (error) {
    formError.value = getErrorMessage(error, 'Không tải được danh sách sản phẩm.')
  }
}

const fetchCategories = async () => {
  try {
    const res = await api.get('/danhmuc')
    categories.value = Array.isArray(res.data)
      ? res.data
      : Array.isArray(res.data?.data)
        ? res.data.data
        : []
  } catch (error) {
    console.error(error)
  }
}

const fetchBrands = async () => {
  try {
    const res = await api.get('/thuonghieu')
    allBrands.value = Array.isArray(res.data)
      ? res.data
      : Array.isArray(res.data?.data)
        ? res.data.data
        : []
    brands.value = [...allBrands.value]
  } catch (error) {
    console.error(error)
  }
}

/**
 * Fetch parent categories (danh mục cha)
 */
const fetchParentCategories = async () => {
  try {
    const res = await api.get('/danhmuc/parents')
    parentCategories.value = Array.isArray(res.data)
      ? res.data
      : Array.isArray(res.data?.data)
        ? res.data.data
        : []
  } catch (error) {
    console.error(error)
  }
}

/**
 * Fetch child categories for a parent category
 */
const fetchChildCategories = async (parentId) => {
  try {
    if (!parentId) {
      childCategories.value = []
      return
    }
    const res = await api.get(`/danhmuc/${parentId}/children`)
    childCategories.value = Array.isArray(res.data)
      ? res.data
      : Array.isArray(res.data?.data)
        ? res.data.data
        : []
  } catch (error) {
    console.error(error)
    childCategories.value = []
  }
}

/**
 * Filter brands locally based on selected categories
 */
const filterBrandsLocally = () => {
  const currentCategoryId = Number(form.value.category) || 0
  const currentParentId = Number(form.value.parentCategory) || 0

  if (!currentCategoryId && !currentParentId) {
    brands.value = [...allBrands.value]
    return
  }

  brands.value = allBrands.value.filter(brand => {
    if (!brand.danh_muc_ids || brand.danh_muc_ids.length === 0) return true // Áp dụng tất cả
    const ids = brand.danh_muc_ids.map(Number)
    return ids.includes(currentCategoryId) || ids.includes(currentParentId)
  })

  // Check if selected brand is still valid
  if (form.value.brand && !brands.value.find(b => b.id_thuonghieu == form.value.brand)) {
    form.value.brand = ''
  }
}

const fetchColors = async () => {
  try {
    const res = await api.get('/colors')
    colors.value = Array.isArray(res.data)
      ? res.data
      : Array.isArray(res.data?.data)
        ? res.data.data
        : []

    buildAttributeGroups()
  } catch (error) {
    console.error(error)
  }
}

const removeProduct = async (id, name = '') => {
  const confirmed = await swal.confirm(
    'Xác nhận xóa',
    `Bạn có chắc muốn xóa sản phẩm${name ? ` "${name}"` : ''} không?`
  )

  if (!confirmed) return

  try {
    await api.delete(`/admin/sanpham/${id}`)
    swal.success('Thành công', 'Xóa sản phẩm thành công')
    await fetchProducts()
  } catch (error) {
    const msg = getErrorMessage(error, 'Không xóa được sản phẩm.')
    formError.value = msg
    swal.error('Xóa thất bại', msg)
  }
}

const deleteProductsByIds = async (ids) => {
  const uniqueIds = Array.from(new Set(ids))
  const results = await Promise.allSettled(
    uniqueIds.map(id => api.delete(`/admin/sanpham/${id}`))
  )

  const failed = results.filter(result => result.status === 'rejected')

  await fetchProducts()
  selectedProductIds.value = selectedProductIds.value.filter(id =>
    products.value.some(p => p.id === id)
  )

  if (failed.length) {
    swal.warning(
      'Xóa chưa hoàn tất',
      `Đã xóa ${uniqueIds.length - failed.length}/${uniqueIds.length} sản phẩm. Một số sản phẩm có thể đang có dữ liệu liên quan.`
    )
    return
  }

  swal.success('Thành công', `Đã xóa ${uniqueIds.length} sản phẩm.`)
}

const removeSelectedProducts = async () => {
  if (!selectedProductIds.value.length || isBulkDeleting.value) return

  const confirmed = await swal.confirm(
    'Xóa sản phẩm đã chọn',
    `Bạn có chắc muốn xóa ${selectedProductIds.value.length} sản phẩm đã chọn không?`
  )

  if (!confirmed) return

  isBulkDeleting.value = true
  try {
    await deleteProductsByIds(selectedProductIds.value)
  } finally {
    isBulkDeleting.value = false
  }
}

const removeAllFilteredProducts = async () => {
  if (!filteredProducts.value.length || isBulkDeleting.value) return

  const total = filteredProducts.value.length
  const confirmed = await swal.confirm(
    'Xóa toàn bộ sản phẩm',
    selectedStatus.value || selectedCategory.value || searchQuery.value
      ? `Bạn có chắc muốn xóa toàn bộ ${total} sản phẩm đang lọc không?`
      : `Bạn có chắc muốn xóa toàn bộ ${total} sản phẩm trong danh sách không?`
  )

  if (!confirmed) return

  isBulkDeleting.value = true
  try {
    await deleteProductsByIds(filteredProducts.value.map(p => p.id))
  } finally {
    isBulkDeleting.value = false
  }
}

/* ═══════════════════════════════════════
   NHÓM THUỘC TÍNH & LOẠI THUỘC TÍNH
═══════════════════════════════════════ */
const baseAttributeGroups = ref([])
const attributeGroups = ref([])

const groupIconMap = {
  'Cấu hình': '💻',
  'Màn hình': '🖥️',
  'Pin & Sạc': '🔋',
  'Kết nối': '📡',
  'Phụ kiện': '🖱️',
  'Màu sắc': '🎨',
}

const colorPool = ['blue', 'green', 'amber', 'pink', 'purple', 'teal']

const getGroupIcon = (name) => {
  return groupIconMap[name] || '📦'
}

const getTypeColor = (name) => {
  if (!name) return 'blue'
  const index =
    String(name)
      .split('')
      .reduce((sum, ch) => sum + ch.charCodeAt(0), 0) % colorPool.length

  return colorPool[index]
}

const buildAttributeGroups = () => {
  const currentCategoryId = Number(form.value.category) || 0
  const currentParentId = Number(form.value.parentCategory) || 0

  const filteredBaseGroups = baseAttributeGroups.value.filter(group => {
    let groupIds = group.danh_muc_ids || []
    if (typeof groupIds === 'string') {
      try { groupIds = JSON.parse(groupIds) } catch (e) { groupIds = [] }
    }
    if (!groupIds || groupIds.length === 0) return true
    if (currentParentId === 0) return true
    const ids = groupIds.map(Number)
    return ids.includes(currentParentId)
  }).map(group => {
    return {
      ...group,
      attrTypes: group.attrTypes.map(attr => ({
        ...attr,
        options: attr.options.filter(opt => {
          let optIds = opt.danh_muc_ids || []
          if (typeof optIds === 'string') {
            try { optIds = JSON.parse(optIds) } catch (e) { optIds = [] }
          }
          if (!optIds || optIds.length === 0) return true
          if (currentCategoryId === 0 && currentParentId === 0) return true
          
          const ids = optIds.map(Number)
          return ids.includes(currentCategoryId) || ids.includes(currentParentId)
        })
      })).filter(attr => attr.options.length > 0)
    }
  }).filter(group => group.attrTypes.length > 0)

  const colorGroup =
    colors.value.length > 0
      ? {
        id: 'color-group',
        name: 'Màu sắc',
        icon: getGroupIcon('Màu sắc'),
        attrTypes: [
          {
            id: 'color-type',
            label: 'Màu sắc',
            color: 'pink',
            options: colors.value.map((c) => ({
              label: c.name,
              value: c.name,
              hex: c.hex_code,
            })),
          },
        ],
      }
      : null

  attributeGroups.value = colorGroup
    ? [...filteredBaseGroups, colorGroup]
    : [...filteredBaseGroups]

  if (!selectedGroupId.value && attributeGroups.value.length > 0) {
    selectedGroupId.value = attributeGroups.value[0].id
  }
}



const normalizeAttributeGroups = (payload) => {
  const nhoms = Array.isArray(payload) ? payload : []

  baseAttributeGroups.value = nhoms.map((group) => {
    const thuocTinhs = Array.isArray(group.thuoc_tinhs)
      ? group.thuoc_tinhs
      : Array.isArray(group.thuocTinhs)
        ? group.thuocTinhs
        : []

    return {
      id: group.id_nhom,
      name: group.ten_nhom,
      danh_muc_ids: group.danh_muc_ids || [],
      icon: getGroupIcon(group.ten_nhom),
      attrTypes: thuocTinhs.map((attr) => {
        const giaTris = Array.isArray(attr.giatri_thuoc_tinhs)
          ? attr.giatri_thuoc_tinhs
          : Array.isArray(attr.giatriThuocTinhs)
            ? attr.giatriThuocTinhs
            : []

        return {
          id: String(attr.id_thuoctinh),
          label: attr.ten_thuoctinh,
          color: getTypeColor(attr.ten_thuoctinh),
          options: giaTris.map((item) => ({
            value: item.giatri,
            label: item.giatri,
            gia_cong_them: item.gia_cong_them || 0,
            danh_muc_ids: item.danh_muc_ids || []
          }))
        }
      }),
    }
  })

  buildAttributeGroups()
}

const fetchAttributeGroups = async () => {
  variantLoading.value = true

  try {
    const res = await api.get('/thuoctinh-all')
    normalizeAttributeGroups(res.data)

    if (!selectedGroupId.value && attributeGroups.value.length > 0) {
      selectedGroupId.value = attributeGroups.value[0].id
    }
  } catch (error) {
    formError.value = getErrorMessage(error, 'Không tải được dữ liệu biến thể.')
  } finally {
    variantLoading.value = false
  }
}

/* ═══════════════════════════════════════
   MODAL & FORM
═══════════════════════════════════════ */
const showModal = ref(false)
const currentView = ref('list') // 'list' | 'product-form'
const formError = ref('')
const isEditMode = ref(false)
const editingProductId = ref(null)
const imgPreview = ref('')
const fileInputRef = ref(null)
const extraFileInputRef = ref(null)
const extraImagePreviews = ref([])

const defaultForm = () => ({
  name: '',
  parentCategory: '',
  category: '',
  brand: '',
  status: 'Đang bán',
  img: '',
  images: [],
  weight: '',
})

const form = ref(defaultForm())
const fieldErrors = ref({})

/**
 * Watch parent category change - load child categories and reset child category selection
 */
watch(() => form.value.parentCategory, async (newParentId) => {
  if (newParentId) {
    await fetchChildCategories(newParentId)
  } else {
    childCategories.value = []
  }
  // Reset child category and brand when parent changes
  form.value.category = ''
  filterBrandsLocally()
})

/**
 * Watch child category change - filter brands and rebuild attribute groups
 */
watch(() => form.value.category, async (newCategoryId) => {
  filterBrandsLocally()
  buildAttributeGroups()
})

const defaultFieldErrors = () => ({
  img: '',
  images: '',
  name: '',
  brand: '',
  category: '',
  status: '',
  weight: '',
  variants: '',
  variantGroups: {},
  variantRows: '',
})

const resetFieldErrors = () => {
  fieldErrors.value = defaultFieldErrors()
}

const allowedImageTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp']
const MAX_MAIN_IMAGE_SIZE = 5 * 1024 * 1024
const MAX_EXTRA_IMAGE_SIZE = 5 * 1024 * 1024
const MAX_EXTRA_IMAGES = 10

const validateTopForm = () => {
  const errors = defaultFieldErrors()

  if (!imgPreview.value && !form.value.img) {
    errors.img = 'Vui lòng chọn ảnh sản phẩm'
  }

  if (form.value.images.length > MAX_EXTRA_IMAGES) {
    errors.images = `Chỉ được chọn tối đa ${MAX_EXTRA_IMAGES} ảnh phụ`
  }

  if (!form.value.name.trim()) {
    errors.name = 'Tên sản phẩm không được để trống'
  } else if (form.value.name.trim().length < 3) {
    errors.name = 'Tên sản phẩm phải có ít nhất 3 ký tự'
  } else if (form.value.name.trim().length > 255) {
    errors.name = 'Tên sản phẩm không được vượt quá 255 ký tự'
  }

  if (!form.value.brand) {
    errors.brand = 'Vui lòng chọn thương hiệu'
  }

  if (!form.value.parentCategory) {
    errors.parentCategory = 'Vui lòng chọn danh mục cha'
  }

  if (!form.value.category) {
    errors.category = 'Vui lòng chọn danh mục con'
  }

  if (!['Đang bán', 'Nháp'].includes(form.value.status)) {
    errors.status = 'Trạng thái không hợp lệ'
  }

  if (form.value.weight !== '' && form.value.weight !== null) {
    const weight = Number(form.value.weight)

    if (Number.isNaN(weight)) {
      errors.weight = 'Khối lượng phải là số'
    } else if (weight <= 0) {
      errors.weight = 'Khối lượng phải lớn hơn 0'
    } else if (weight > 1000) {
      errors.weight = 'Khối lượng không hợp lệ'
    }
  }

  fieldErrors.value = {
    ...fieldErrors.value,
    ...errors,
  }

  return !Object.entries(errors).some(([key, val]) => {
    if (key === 'variantGroups') return false
    return Boolean(val)
  })
}

const onFileChange = e => {
  const file = e.target.files[0]
  if (!file) return

  fieldErrors.value.img = ''

  if (!allowedImageTypes.includes(file.type)) {
    fieldErrors.value.img = 'Ảnh sản phẩm chỉ chấp nhận PNG, JPG, JPEG, WEBP'
    if (fileInputRef.value) fileInputRef.value.value = ''
    return
  }

  if (file.size > MAX_MAIN_IMAGE_SIZE) {
    fieldErrors.value.img = 'Ảnh sản phẩm không được vượt quá 5MB'
    if (fileInputRef.value) fileInputRef.value.value = ''
    return
  }

  const r = new FileReader()
  r.onload = ev => {
    imgPreview.value = ev.target.result
    form.value.img = ev.target.result
  }
  r.readAsDataURL(file)
}

const onExtraFilesChange = async (e) => {
  const files = Array.from(e.target.files || [])
  if (!files.length) return

  fieldErrors.value.images = ''

  const totalAfterAdd = form.value.images.length + files.length
  if (totalAfterAdd > MAX_EXTRA_IMAGES) {
    fieldErrors.value.images = `Chỉ được chọn tối đa ${MAX_EXTRA_IMAGES} ảnh phụ`
    if (extraFileInputRef.value) extraFileInputRef.value.value = ''
    return
  }

  const invalidTypeFile = files.find(file => !allowedImageTypes.includes(file.type))
  if (invalidTypeFile) {
    fieldErrors.value.images = 'Ảnh phụ chỉ chấp nhận PNG, JPG, JPEG, WEBP'
    if (extraFileInputRef.value) extraFileInputRef.value.value = ''
    return
  }

  const invalidSizeFile = files.find(file => file.size > MAX_EXTRA_IMAGE_SIZE)
  if (invalidSizeFile) {
    fieldErrors.value.images = 'Mỗi ảnh phụ không được vượt quá 5MB'
    if (extraFileInputRef.value) extraFileInputRef.value.value = ''
    return
  }

  readingExtraImages.value = true

  try {
    const base64Images = await Promise.all(
      files.map(file => {
        return new Promise((resolve, reject) => {
          const reader = new FileReader()

          reader.onload = (ev) => resolve(ev.target.result)
          reader.onerror = () => reject(new Error('Không đọc được file ảnh'))

          reader.readAsDataURL(file)
        })
      })
    )

    form.value.images = [...form.value.images, ...files]
    extraImagePreviews.value = [...extraImagePreviews.value, ...base64Images]
  } catch (error) {
    console.error(error)
    fieldErrors.value.images = 'Không đọc được một hoặc nhiều ảnh phụ'
  } finally {
    readingExtraImages.value = false

    if (extraFileInputRef.value) {
      extraFileInputRef.value.value = ''
    }
  }
}

const triggerFileInput = () => fileInputRef.value?.click()
const triggerExtraFileInput = () => extraFileInputRef.value?.click()

const removeImg = () => {
  imgPreview.value = ''
  form.value.img = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const removeExtraImage = index => {
  extraImagePreviews.value.splice(index, 1)
  form.value.images.splice(index, 1)

  if (form.value.images.length === 0 && extraFileInputRef.value) {
    extraFileInputRef.value.value = ''
  }
}

/* ═══════════════════════════════════════
   VARIANT STATE
═══════════════════════════════════════ */
const vsPhase = ref(1)
const selectedGroupId = ref(null)
const selectedOptions = ref({})
const variationTierIds = ref(new Set()) // Lưu ID thuộc tính dùng làm Biến thể (max 2)
const generatedRows = ref([])
const editVariantHeaders = ref([])
const VARIANTS_PER_PAGE = 15
const variantCurrentPage = ref(1)
const selectedOptionsSnapshot = ref({})
const basePrice = ref('')
const baseStock = ref('')

const toggleVariationTier = (typeId) => {
  if (variationTierIds.value.has(typeId)) {
    variationTierIds.value.delete(typeId)
  } else {
    if (variationTierIds.value.size >= 3) {
      swal.warning('Giới hạn biến thể', 'Chỉ được chọn tối đa 3 cấp biến thể. Các thuộc tính khác sẽ được lưu vào Thông số kỹ thuật.')
      return
    }
    variationTierIds.value.add(typeId)
  }
}


const variantTotalPages = computed(() =>
  Math.max(1, Math.ceil(generatedRows.value.length / VARIANTS_PER_PAGE))
)

const paginatedVariants = computed(() => {
  const start = (variantCurrentPage.value - 1) * VARIANTS_PER_PAGE
  return generatedRows.value.slice(start, start + VARIANTS_PER_PAGE)
})

const removeVariantRow = (index) => {
  const actualIndex = (variantCurrentPage.value - 1) * VARIANTS_PER_PAGE + index
  generatedRows.value.splice(actualIndex, 1)
  if (paginatedVariants.value.length === 0 && variantCurrentPage.value > 1) {
    variantCurrentPage.value--
  }
}

const addManualVariant = () => {
  let headers = [...tableHeaders.value]
  if (!headers.length) {
    const selectedTypes = allSelectedAttrTypes.value
    if (selectedTypes.length > 0) {
      selectedTypes.slice(0, 3).forEach(t => {
        variationTierIds.value.add(t.id)
      })
      headers = allSelectedAttrTypes.value.filter(t => variationTierIds.value.has(t.id))
    }
  }

  if (!headers.length) {
    swal.warning('Thiếu thông tin', 'Vui lòng chọn ít nhất 1 loại thuộc tính làm Biến thể (SKU) trước khi thêm thủ công.')
    return
  }
  
  const attrs = {}
  headers.forEach(h => {
    // Lấy giá trị đầu tiên trong bộ chọn để làm mặc định
    attrs[h.id] = Array.from(selectedOptions.value[h.id] || [])[0] || null
  })

  generatedRows.value.push({
    id: `manual-${Date.now()}`,
    attrs,
    price: '',
    stock: ''
  })
  
  // Chuyển đến trang cuối
  variantCurrentPage.value = variantTotalPages.value
}


const variantPageItems = computed(() => {
  const total = variantTotalPages.value
  const current = variantCurrentPage.value

  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }

  if (current <= 3) {
    return [1, 2, 3, '...', total - 2, total - 1, total]
  }

  if (current >= total - 2) {
    return [1, 2, 3, '...', total - 2, total - 1, total]
  }

  return [1, '...', current - 1, current, current + 1, '...', total]
})

const goToVariantPage = (page) => {
  if (page < 1) {
    variantCurrentPage.value = 1
    return
  }
  if (page > variantTotalPages.value) {
    variantCurrentPage.value = variantTotalPages.value
    return
  }
  variantCurrentPage.value = page
}

const selectedGroup = computed(() =>
  attributeGroups.value.find(g => g.id === selectedGroupId.value) || null
)

const allAttrTypes = computed(() => {
  return attributeGroups.value.flatMap(group =>
    (group.attrTypes || []).map(type => ({
      ...type,
      groupId: group.id,
      groupName: group.name,
    }))
  )
})

const displayAttrTypes = computed(() => {
  if (!selectedGroup.value) return []
  return selectedGroup.value.attrTypes || []
})

const allSelectedAttrTypes = computed(() => {
  return allAttrTypes.value.filter(
    t => (selectedOptions.value[t.id]?.size ?? 0) > 0
  )
})

const variationHeaders = computed(() => {
  return allSelectedAttrTypes.value.filter(t => variationTierIds.value.has(t.id))
})

const specificationAttrs = computed(() => {
  return allSelectedAttrTypes.value.filter(t => !variationTierIds.value.has(t.id))
})

const tableHeaders = computed(() => {
  if (isEditMode.value) return editVariantHeaders.value
  return variationHeaders.value
})

const comboCount = computed(() => {
  if (!allSelectedAttrTypes.value.length) return 0

  return allSelectedAttrTypes.value.reduce(
    (prod, t) => prod * (selectedOptions.value[t.id]?.size ?? 0),
    1
  )
})

const getOptionValue = (opt) => typeof opt === 'object' ? opt.value : opt
const getOptionLabel = (opt) => typeof opt === 'object' ? opt.label : opt
const getOptionHex = (opt) => typeof opt === 'object' ? opt.hex : null

const findAttrTypeByName = (name) => {
  const normalized = String(name || '').trim().toLowerCase()

  if (
    normalized === 'màu sắc' ||
    normalized === 'mau sac' ||
    normalized === 'màu' ||
    normalized === 'mau'
  ) {
    return allAttrTypes.value.find(t => String(t.id) === 'color-type') || null
  }

  return allAttrTypes.value.find(
    t => String(t.label || '').trim().toLowerCase() === normalized
  ) || null
}

const selectGroup = groupId => {
  if (selectedGroupId.value === groupId) return
  selectedGroupId.value = groupId
}

const cloneSelectedOptions = (source) => {
  const result = {}

  Object.entries(source || {}).forEach(([key, set]) => {
    result[key] = new Set([...(set || new Set())])
  })

  return result
}

const serializeSelectedOptions = (source) => {
  const normalized = {}

  Object.keys(source || {})
    .sort()
    .forEach((key) => {
      normalized[key] = [...(source[key] || new Set())]
        .map(String)
        .sort()
    })

  return JSON.stringify(normalized)
}

const hasVariantSelectionChanged = computed(() => {
  return serializeSelectedOptions(selectedOptions.value) !== serializeSelectedOptions(selectedOptionsSnapshot.value)
})

const isSelected = (typeId, value) =>
  selectedOptions.value[typeId]?.has(value) ?? false

const toggleOption = (typeId, value) => {
  if (!selectedOptions.value[typeId]) selectedOptions.value[typeId] = new Set()
  const set = selectedOptions.value[typeId]

  const isTier = variationTierIds.value.has(typeId)

  if (isTier) {
    // Chế độ chọn nhiều (Biến thể)
    if (set.has(value)) set.delete(value)
    else set.add(value)
  } else {
    // Chế độ chọn duy nhất (Thông số kỹ thuật)
    if (set.has(value)) {
      set.delete(value)
    } else {
      set.clear()
      set.add(value)
    }
  }

  selectedOptions.value = { ...selectedOptions.value }

  if (fieldErrors.value.variantGroups?.[typeId]) {
    const nextErrors = { ...(fieldErrors.value.variantGroups || {}) }

    if ((selectedOptions.value[typeId]?.size ?? 0) > 0) {
      delete nextErrors[typeId]
    }

    fieldErrors.value.variantGroups = nextErrors

    if (!Object.keys(nextErrors).length) {
      fieldErrors.value.variants = ''
    }
  }
}

const selectedCountInGroup = g =>
  g.attrTypes.reduce((s, t) => s + (selectedOptions.value[t.id]?.size ?? 0), 0)

const groupsHavingSelection = computed(() => {
  return attributeGroups.value.filter(group =>
    group.attrTypes.some(type => (selectedOptions.value[type.id]?.size ?? 0) > 0)
  )
})

const validateVariantSelections = () => {
  const variantGroupErrors = {}
  let isValid = true

  if (!groupsHavingSelection.value.length) {
    fieldErrors.value.variants = 'Vui lòng chọn ít nhất 1 giá trị thuộc tính'
    fieldErrors.value.variantGroups = {}
    return false
  }

  groupsHavingSelection.value.forEach((group) => {
    group.attrTypes.forEach((type) => {
      const selectedCount = selectedOptions.value[type.id]?.size ?? 0

      if (selectedCount < 1) {
        variantGroupErrors[type.id] = `Vui lòng chọn ít nhất 1 giá trị cho ${type.label}`
        isValid = false
      }
    })
  })

  fieldErrors.value.variantGroups = variantGroupErrors
  fieldErrors.value.variants = isValid
    ? ''
    : 'Trong mỗi nhóm đã chọn, mọi loại thuộc tính phải có ít nhất 1 giá trị'

  return isValid
}

const cartesian = arrays => {
  if (arrays.length === 0) return [[]]
  const [first, ...rest] = arrays
  const tail = cartesian(rest)
  return first.flatMap(v => tail.map(c => [v, ...c]))
}

const buildVariantKeyFromAttrs = (attrs, headers) => {
  return headers
    .map(t => `${t.id}:${String(attrs?.[t.id] ?? '')}`)
    .join('||')
}

const continueVariantTable = () => {
  variantCurrentPage.value = 1
  vsPhase.value = 2
}

const generateVariants = () => {
  const isValid = validateVariantSelections()
  if (!isValid) return

  let headers = [...tableHeaders.value]
  if (!headers.length) {
    const selectedTypes = allSelectedAttrTypes.value
    if (selectedTypes.length > 0) {
      selectedTypes.slice(0, 3).forEach(t => {
        variationTierIds.value.add(t.id)
      })
      headers = allSelectedAttrTypes.value.filter(t => variationTierIds.value.has(t.id))
    }
  }

  if (!headers.length) {
    swal.warning('Thiếu thông tin', 'Vui lòng chọn ít nhất 1 loại thuộc tính làm Biến thể (SKU)')
    return
  }

  const arrays = headers.map(t => [...selectedOptions.value[t.id]])
  const combos = cartesian(arrays)

  const oldRowsMap = new Map(
    generatedRows.value.map(row => {
      const oldKey = buildVariantKeyFromAttrs(row.attrs || {}, headers)
      return [oldKey, row]
    })
  )

  generatedRows.value = combos.map((combo, i) => {
    const attrs = {}

    headers.forEach((t, ti) => {
      attrs[t.id] = combo[ti]
    })

    const key = buildVariantKeyFromAttrs(attrs, headers)
    const oldRow = oldRowsMap.get(key)

    return {
      id: oldRow?.id ?? `${Date.now()}-${i}`,
      attrs,
      price: oldRow?.price ?? '',
      stock: oldRow?.stock ?? '',
      ten_bienthe: oldRow?.ten_bienthe ?? headers.map(h => attrs[h.id]).join(' - '),
      isExisting: oldRow?.isExisting ?? false,
      _manualPrice: oldRow?._manualPrice ?? false,
      _manualStock: oldRow?._manualStock ?? false,
    }
  })

  selectedOptionsSnapshot.value = cloneSelectedOptions(selectedOptions.value)
  variantCurrentPage.value = 1
  vsPhase.value = 2

  if (basePrice.value !== '' || baseStock.value !== '') {
    applyRulesToAll(false)
  }
}

const rebuildSelectedOptionsFromRows = () => {
  const nextSelectedOptions = {}

  // 1. Bảo tồn các Thông số kỹ thuật hiện có (không phải Biến thể)
  Object.keys(selectedOptions.value || {}).forEach(attrId => {
    if (!variationTierIds.value.has(String(attrId))) {
      nextSelectedOptions[String(attrId)] = new Set(selectedOptions.value[attrId])
    }
  })

  // 2. Nạp lại các Biến thể từ cấu hình bảng
  generatedRows.value.forEach((row) => {
    Object.entries(row.attrs || {}).forEach(([attrId, value]) => {
      attrId = String(attrId)
      if (!nextSelectedOptions[attrId]) {
        nextSelectedOptions[attrId] = new Set()
      }

      if (value !== null && value !== undefined && value !== '') {
        nextSelectedOptions[attrId].add(value)
      }
    })
  })

  selectedOptions.value = nextSelectedOptions
  selectedOptionsSnapshot.value = cloneSelectedOptions(nextSelectedOptions)
}

const backToSelect = () => {
  rebuildSelectedOptionsFromRows()
  vsPhase.value = 1
}

const removeRow = (globalIndex) => {
  if (generatedRows.value.length <= 1) return

  generatedRows.value.splice(globalIndex, 1)

  if (variantCurrentPage.value > variantTotalPages.value) {
    variantCurrentPage.value = variantTotalPages.value
  }
}

// ===== BASE / RULE =====
const formatCurrency = (val) => {
  if (!val) return ''
  const num = String(val).replace(/\D/g, '')
  if (!num) return ''
  return Number(num).toLocaleString('vi-VN')
}

const parseCurrency = (val) => {
  if (!val) return ''
  return String(val).replace(/\D/g, '')
}
const calculateVariantPrice = (row) => {
  if (basePrice.value === '' || basePrice.value === null) return null

  let base = Number(basePrice.value)

  let extra = 0

  Object.entries(row.attrs || {}).forEach(([attrId, value]) => {
    const attrType = allAttrTypes.value.find(t => String(t.id) === String(attrId))

    const option = attrType?.options?.find(opt => {
      const v = typeof opt === 'object' ? opt.value : opt
      return String(v) === String(value)
    })

    if (option && typeof option === 'object' && option.gia_cong_them) {
      extra += Number(option.gia_cong_them)
    }
  })

  return base + extra
}

const calculateVariantStock = () => {
  if (baseStock.value === '' || baseStock.value === null) return null
  return Number(baseStock.value)
}

const applyRulesToAll = (override = true) => {
  generatedRows.value.forEach(row => {
    const newPrice = calculateVariantPrice(row)
    const newStock = calculateVariantStock()

    if (
      newPrice !== null &&
      (override || !row._manualPrice || row.price === '' || row.price === null)
    ) {
      row.price = newPrice
      row._manualPrice = false
    }

    if (
      newStock !== null &&
      (override || !row._manualStock || row.stock === '' || row.stock === null)
    ) {
      row.stock = newStock
      row._manualStock = false
    }
  })
}
watch(basePrice, (val) => {
  if (vsPhase.value !== 2 || !generatedRows.value.length) return
  if (val === '') return

  applyRulesToAll(false)
})

watch(baseStock, (val) => {
  if (vsPhase.value !== 2 || !generatedRows.value.length) return
  if (val === '') return

  applyRulesToAll(false)
})
const markManualPrice = (row) => {
  row._manualPrice = true
}

const markManualStock = (row) => {
  row._manualStock = true
}

const validateVariantRows = () => {
  if (!generatedRows.value.length) {
    fieldErrors.value.variantRows = 'Vui lòng tạo ít nhất 1 biến thể'
    return false
  }

  const invalidRow = generatedRows.value.find(row => {
    const hasPrice = row.price !== '' && row.price !== null && row.price !== undefined
    const hasStock = row.stock !== '' && row.stock !== null && row.stock !== undefined

    if (hasPrice && Number(row.price) <= 0) return true
    if (hasStock && Number(row.stock) < 0) return true

    return false
  })

  if (invalidRow) {
    fieldErrors.value.variantRows = 'Giá phải > 0, kho phải >= 0 nếu có nhập'
    return false
  }

  fieldErrors.value.variantRows = ''
  return true
}

/* ═══════════════════════════════════════
   RESET / OPEN / CLOSE / SUBMIT
═══════════════════════════════════════ */
const resetForm = () => {
  form.value = defaultForm()
  imgPreview.value = ''
  extraImagePreviews.value = []
  formError.value = ''
  resetFieldErrors()

  variantCurrentPage.value = 1
  vsPhase.value = 1
  selectedOptions.value = {}
  selectedOptionsSnapshot.value = {}
  generatedRows.value = []
  editVariantHeaders.value = []

  basePrice.value = ''
  baseStock.value = ''
  selectedGroupId.value = attributeGroups.value.length > 0
    ? attributeGroups.value[0].id
    : null

  if (fileInputRef.value) fileInputRef.value.value = ''
  if (extraFileInputRef.value) extraFileInputRef.value.value = ''
}

const openEditModal = async (id) => {
  try {
    formError.value = ''
    resetForm()

    if (!categories.value.length) {
      await fetchCategories()
    }

    if (!brands.value.length) {
      await fetchBrands()
    }

    const res = await api.get(`/sanpham/${id}`)
    const product = res.data

    isEditMode.value = true
    editingProductId.value = id

    mapProductToForm(product)
    showModal.value = true
    currentView.value = 'product-form'
  } catch (error) {
    console.error(error)
    swal.error('Lỗi', getErrorMessage(error, 'Không tải được thông tin sản phẩm để sửa.'))
  }
}

const mapProductToForm = (product) => {
  form.value = {
    name: product?.tenSP || '',
    parentCategory: String(product?.danh_muc?.id_danhmuc_cha ?? ''),
    category: String(product?.id_danhmuc ?? product?.danh_muc?.id_danhmuc ?? ''),
    brand: String(product?.id_thuonghieu ?? product?.thuong_hieu?.id_thuonghieu ?? ''),
    status: String(product?.trangthai ?? product?.trang_thai ?? '') === '1' ? 'Đang bán' : 'Nháp',
    img: '',
    images: [],
    weight: product?.khoiluong ?? '',
  }

  const productImages = Array.isArray(product?.hinh_anhs)
    ? product.hinh_anhs
    : Array.isArray(product?.hinhAnhs)
      ? product.hinhAnhs
      : []

  imgPreview.value = product?.hinhanh
    ? storageUrl(product.hinhanh)
    : productImages[0]?.duongdan
      ? storageUrl(productImages[0].duongdan)
      : ''

  extraImagePreviews.value = productImages
    .map(img => img?.duongdan ? storageUrl(img.duongdan) : '')
    .filter(Boolean)

  const bienThes = Array.isArray(product?.bien_thes) ? product.bien_thes : []

  generatedRows.value = bienThes.map((row, i) => {
    const attrs = {}

    if (Array.isArray(row?.thuoc_tinh)) {
      row.thuoc_tinh.forEach((item, attrIndex) => {
        const matchedAttr = findAttrTypeByName(item?.ten_thuoctinh)
        const attrId = matchedAttr?.id ?? item?.id_thuoctinh ?? item?.ten_thuoctinh ?? `attr_${attrIndex}`

        if (attrId) {
          attrs[attrId] = item.giatri
        }
      })
    }

    return {
      id: row.id_bienthe ?? `${Date.now()}-${i}`,
      attrs,
      price: row.gia ?? '',
      stock: row.soluong ?? 0,
      ten_bienthe: row.ten_bienthe ?? '',
      isExisting: true,
      _manualPrice: true,
      _manualStock: true,
    }
  })

  const firstVariant = bienThes[0]

  if (firstVariant?.thuoc_tinh?.length) {
    editVariantHeaders.value = firstVariant.thuoc_tinh.map((item, index) => {
      const matchedAttr = findAttrTypeByName(item?.ten_thuoctinh)

      return {
        id: matchedAttr?.id ?? item.id_thuoctinh ?? item.ten_thuoctinh ?? `attr_${index}`,
        label: item.ten_thuoctinh ?? `Thuộc tính ${index + 1}`,
        color: matchedAttr?.color ?? colorPool[index % colorPool.length],
      }
    })
  } else {
    editVariantHeaders.value = []
  }

  // 1. Phân biệt các thuộc tính đang làm Biến thể
  variationTierIds.value = new Set(editVariantHeaders.value.map(h => String(h.id)))

  // 2. Nạp giá trị biến thể vào selectedOptions
  rebuildSelectedOptionsFromRows()

  // 3. Nạp Thông số kỹ thuật vào selectedOptions
  let specs = []
  if (Array.isArray(product?.thong_so_ky_thuat)) {
    specs = product.thong_so_ky_thuat
  } else if (typeof product?.thong_so_ky_thuat === 'string') {
    try { specs = JSON.parse(product.thong_so_ky_thuat) } catch(e) {}
  }
  
  if (specs && specs.length > 0) {
    const nextSelected = { ...selectedOptions.value }
    specs.forEach(spec => {
      const matchedAttr = findAttrTypeByName(spec.ten_thuoctinh)
      const attrId = String(matchedAttr?.id ?? spec.id_thuoctinh ?? spec.ten_thuoctinh)
      
      if (attrId && !variationTierIds.value.has(attrId)) {
        if (!nextSelected[attrId]) {
          nextSelected[attrId] = new Set()
        }
        String(spec.giatri).split(',').forEach(v => {
          if (v.trim()) nextSelected[attrId].add(v.trim())
        })
      }
    })
    selectedOptions.value = nextSelected
  }

  // 4. Lưu Snapshot sau khi đã nạp đầy đủ Biến thể + Thông số
  selectedOptionsSnapshot.value = cloneSelectedOptions(selectedOptions.value)

  variantCurrentPage.value = 1
  vsPhase.value = 2
}

const openModal = () => {
  resetForm()
  isEditMode.value = false
  editingProductId.value = null
  currentView.value = 'product-form'

  if (attributeGroups.value.length > 0) {
    selectedGroupId.value = attributeGroups.value[0].id
  }
}

const closeModal = () => {
  resetFieldErrors()
  currentView.value = 'list'
}

const submitForm = async () => {
  formError.value = ''

  const isTopFormValid = validateTopForm()
  const isVariantRowsValid = validateVariantRows()
  if (!isTopFormValid || !isVariantRowsValid) {
    formError.value = 'Vui lòng kiểm tra lại thông tin sản phẩm'
    return
  }

  try {
    const payload = {
      id_danhmuc: Number(form.value.category),
      id_thuonghieu: Number(form.value.brand),
      tenSP: form.value.name.trim(),
      trangthai: form.value.status === 'Đang bán' ? 1 : 0,
      hinhanh: form.value.img || imgPreview.value || '',
      khoiluong: form.value.weight ? Number(form.value.weight) : null,

      hinh_anhs: extraImagePreviews.value.map((img, index) => ({
        duongdan: img,
        thutu: index,
      })),

      thong_so_ky_thuat: specificationAttrs.value.map(attr => {
        const selectedVals = Array.from(selectedOptions.value[attr.id] || [])
        return {
          id_thuoctinh: attr.id,
          ten_thuoctinh: attr.label,
          giatri: selectedVals.join(', ')
        }
      }).filter(s => s.giatri),

      bienthes: generatedRows.value.map((row) => ({
        ten_bienthe: row.ten_bienthe || Object.values(row.attrs || {}).join(' - '),
        gia: row.price === '' || row.price === null ? null : Number(row.price),
        soluong: row.stock === '' || row.stock === null ? null : Number(row.stock),
        thuoc_tinh: Object.entries(row.attrs || {}).map(([attrId, value]) => {
          const attrMeta = allAttrTypes.value.find(t => String(t.id) === String(attrId))
          const optionMeta = attrMeta?.options?.find(opt => {
            const optValue = typeof opt === 'object' ? opt.value : opt
            return String(optValue) === String(value)
          })

          return {
            id_thuoctinh: attrMeta?.id ?? null,
            ten_thuoctinh: attrMeta?.label ?? null,
            giatri: value,
            hex: optionMeta?.hex || null,
          }
        }),
      }))
    }

    if (isEditMode.value && editingProductId.value) {
      await api.put(`/admin/sanpham/${editingProductId.value}`, payload)
      swal.success('Thành công', 'Cập nhật sản phẩm thành công')
    } else {
      await api.post('/admin/sanpham', payload)
      swal.success('Thành công', 'Thêm sản phẩm thành công')
    }

    await fetchProducts()
    resetForm()
    closeModal()
  } catch (error) {
    console.error(error)
    swal.error('Lỗi', getErrorMessage(error, isEditMode.value
      ? 'Có lỗi xảy ra khi cập nhật sản phẩm'
      : 'Có lỗi xảy ra khi thêm sản phẩm'))
  }
}

onMounted(() => {
  fetchProducts()
  fetchParentCategories()
  fetchCategories()
  fetchBrands()
  fetchAttributeGroups()
  fetchColors()
})
</script>

<template>
  <div class="admin">

    <!-- ══════════════════════════════════════════════════════
         VIEW: DANH SÁCH sản phẩm (top, stats, filter, table)
    ══════════════════════════════════════════════════════ -->
    <template v-if="currentView === 'list'">

    <div class="top">
      <div>
        <h1>Quản lý sản phẩm</h1>
        <p>Cập nhật và theo dõi danh mục thiết bị công nghệ 2026</p>
      </div>
      <div class="excel-actions">
        <button class="btn-excel btn-export" @click="handleExportExcel" :disabled="isExporting">
          <svg v-if="!isExporting" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2"
            fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          <span v-else class="spinner-sm"></span>
          {{ isExporting ? 'Đang xuất...' : 'Xuất Excel' }}
        </button>

        <button class="btn-excel btn-import" @click="triggerImportExcel" :disabled="isImporting">
          <svg v-if="!isImporting" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2"
            fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
          <span v-else class="spinner-sm"></span>
          {{ isImporting ? 'Đang nhập...' : 'Nhập Excel' }}
        </button>
        <input type="file" ref="importExcelRef" style="display: none" accept=".xlsx, .xls"
          @change="handleImportExcel" />

        <button class="add-btn" @click="openModal">+ Thêm sản phẩm</button>
      </div>
    </div>

    <div class="stats">
      <div class="stat-card stat-blue">
        <span class="stat-icon blue">📦</span>
        <div>
          <p>Tổng sản phẩm</p>
          <b>{{ totalProductStats.toLocaleString('vi-VN') }}</b>
        </div>
      </div>
      <div class="stat-card stat-orange clickable-stat" @click="openLowStockModal">
        <span class="stat-icon red">⚠️</span>
        <div>
          <p>Sắp hết hàng</p>
          <b>{{ lowStockStats.toLocaleString('vi-VN') }}</b>
        </div>
      </div>

      <div class="stat-card stat-teal">
        <span class="stat-icon purple">🏭</span>
        <div>
          <p>Kho lưu trữ</p>
          <b>{{ totalInventoryStats.toLocaleString('vi-VN') }}</b>
        </div>
      </div>
    </div>

    <!-- Tabs danh mục cha -->
    <div class="parent-tabs">
      <button 
        class="parent-tab-btn" 
        :class="{ active: selectedParentTab === '' }" 
        @click="selectParentTab('')"
      >
        Tất cả sản phẩm
      </button>
      <button 
        v-for="parentCat in parentCategories" 
        :key="parentCat.id_danhmuc_cha"
        class="parent-tab-btn" 
        :class="{ active: String(selectedParentTab) === String(parentCat.id_danhmuc_cha) }" 
        @click="selectParentTab(parentCat.id_danhmuc_cha)"
      >
        {{ parentCat.ten_danhmuc }}
      </button>
    </div>

    <div class="filter-bar">
      <div class="search-wrap">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round">
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.35-4.35" />
        </svg>
        <input v-model="searchQuery" placeholder="Tìm kiếm tên sản phẩm, SKU..." />
      </div>
      <!-- Custom Status Dropdown -->
      <div class="custom-dropdown">
        <div class="dropdown-trigger" @click.stop="isOpenStatusDropdown = !isOpenStatusDropdown; isOpenCategoryDropdown = false">
          <span>{{ selectedStatus || 'Tất cả trạng thái' }}</span>
          <svg class="chevron" :class="{ open: isOpenStatusDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
        <transition name="fade-slide">
          <ul v-if="isOpenStatusDropdown" class="dropdown-menu">
            <li :class="{ active: selectedStatus === '' }" @click="selectedStatus = ''; isOpenStatusDropdown = false">Tất cả trạng thái</li>
            <li :class="{ active: selectedStatus === 'Đang bán' }" @click="selectedStatus = 'Đang bán'; isOpenStatusDropdown = false">Đang bán</li>
            <li :class="{ active: selectedStatus === 'Nháp' }" @click="selectedStatus = 'Nháp'; isOpenStatusDropdown = false">Nháp</li>
          </ul>
        </transition>
      </div>

      <!-- Custom Category Dropdown -->
      <div class="custom-dropdown">
        <div class="dropdown-trigger" @click.stop="isOpenCategoryDropdown = !isOpenCategoryDropdown; isOpenStatusDropdown = false">
          <span>{{ getSelectedCategoryLabel() }}</span>
          <svg class="chevron" :class="{ open: isOpenCategoryDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
        <transition name="fade-slide">
          <ul v-if="isOpenCategoryDropdown" class="dropdown-menu">
            <li :class="{ active: selectedCategory === '' }" @click="selectedCategory = ''; isOpenCategoryDropdown = false">Tất cả danh mục</li>
            <li v-for="category in filteredCategoriesForDropdown" :key="category.id_danhmuc" 
                :class="{ active: String(selectedCategory) === String(category.id_danhmuc) }" 
                @click="selectedCategory = category.id_danhmuc; isOpenCategoryDropdown = false">
              {{ category.ten_danhmuc }}
            </li>
          </ul>
        </transition>
      </div>
    </div>

    <div class="product-bulk-toolbar">
      <div class="bulk-summary">
        <span class="bulk-summary-icon">✓</span>
        <div>
          <b>{{ selectedProductIds.length }} sản phẩm đã chọn</b>
          <p>Tick từng dòng hoặc chọn toàn bộ {{ paginatedProducts.length }} sản phẩm trên trang hiện tại.</p>
        </div>
      </div>
      <div class="bulk-toolbar-actions">
        <button class="bulk-btn ghost" :disabled="!selectedProductIds.length || isBulkDeleting" @click="clearProductSelection">
          Bỏ chọn
        </button>
        <button class="bulk-btn danger" :disabled="!selectedProductIds.length || isBulkDeleting" @click="removeSelectedProducts">
          {{ isBulkDeleting ? 'Đang xóa...' : `Xóa đã chọn (${selectedProductIds.length})` }}
        </button>
        <button class="bulk-btn danger-outline" :disabled="!filteredProducts.length || isBulkDeleting" @click="removeAllFilteredProducts">
          Xóa toàn bộ
        </button>
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th class="select-col">
              <input type="checkbox" :checked="allCurrentPageSelected" :disabled="!paginatedProducts.length" @change="toggleCurrentPageSelection" />
            </th>
            <th>STT</th>
            <th>Sản phẩm</th>
            <th>Danh mục</th>
            <th>Thương hiệu</th>
            <th>Tổng biến thể</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!paginatedProducts.length">
            <td colspan="8" class="empty">Không tìm thấy sản phẩm nào.</td>
          </tr>
          <tr v-for="(p, i) in paginatedProducts" :key="p.id" :class="{ 'row-selected': selectedProductIds.includes(p.id) }">
            <td class="select-col">
              <input type="checkbox" :checked="selectedProductIds.includes(p.id)" @change="toggleProductSelection(p.id)" />
            </td>
            <td>
              {{ (currentPage - 1) * PER_PAGE + i + 1 }}
            </td>
            <td>
              <div class="product-cell">
                <img :src="p.img" :alt="p.name" />
                <div><b>{{ p.name }}</b><span>SKU: {{ p.sku }}</span></div>
              </div>
            </td>
            <td><span class="badge">{{ p.category }}</span></td>
            <td><span class="price">{{ p.brand }}</span></td>
            <td>
              <div class="stock-cell">
                <span>{{ p.totalVariants }} biến thể</span>
              </div>
            </td>
            <td><span class="status-badge" :class="p.status === 'Đang bán' ? 'active' : 'draft'">{{ p.status }}</span>
            </td>
            <td>
              <div class="actions">
                <button class="act-btn" title="Sửa" @click="openEditModal(p.id)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                  </svg>
                </button>
                <button class="act-btn danger" title="Xóa" @click="removeProduct(p.id, p.name)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
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

    <div class="pagination">
      <button :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">‹</button>

      <button v-for="(p, index) in pageItems" :key="`${p}-${index}`"
        :class="{ 'pg-active': p === currentPage, 'pg-dots': p === '...' }" :disabled="p === '...'"
        @click="p !== '...' && goToPage(p)">
        {{ p }}
      </button>

      <button :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">›</button>
    </div>

    </template><!-- end list view -->

    <!-- ══════════════════════════════════════════════════════
         VIEW: FORM SẢN PHẨM (Thêm / Sửa)
    ══════════════════════════════════════════════════════ -->
    <template v-if="currentView === 'product-form'">
      <!-- Inline form header -->
      <div class="inline-form-header">
        <button class="back-btn" @click="closeModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M15 18l-6-6 6-6"/></svg>
          Quay lại danh sách
        </button>
        <h1>{{ isEditMode ? '✏️ Chỉnh sửa sản phẩm' : '➕ Thêm sản phẩm mới' }}</h1>
        <p>{{ isEditMode ? 'Cập nhật thông tin và biến thể của sản phẩm' : 'Điền đầy đủ thông tin để thêm sản phẩm vào hệ thống' }}</p>
      </div>

      <div class="inline-form-body">

            <div class="form-group">
              <label>Ảnh sản phẩm</label>
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
              <p v-if="fieldErrors.img" class="field-error">{{ fieldErrors.img }}</p>
            </div>


            <div class="form-group">
              <label>Hình ảnh phụ</label>
              <input ref="extraFileInputRef" type="file" accept="image/*" multiple style="display:none"
                @change="onExtraFilesChange" />
              <div class="upload-zone" @click="triggerExtraFileInput">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="17 8 12 3 7 8" />
                  <line x1="12" y1="3" x2="12" y2="15" />
                </svg>
                <p>Kéo thả hoặc <span>bấm để chọn nhiều ảnh</span></p>
                <small>PNG, JPG, WEBP — có thể chọn nhiều ảnh</small>
              </div>
              <p v-if="fieldErrors.images" class="field-error">{{ fieldErrors.images }}</p>
              <div v-if="extraImagePreviews.length" class="multi-preview-wrap">
                <div v-for="(img, index) in extraImagePreviews" :key="index" class="multi-preview-item">
                  <img :src="img" class="multi-preview-img" :alt="`preview-${index}`" />
                  <button class="multi-preview-remove" @click="removeExtraImage(index)">×</button>
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tên sản phẩm <span class="required">*</span></label>
                <input v-model="form.name" placeholder="VD: VinaPro Laptop X2"
                  :class="{ 'input-error': fieldErrors.name }" />
                <p v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</p>
              </div>
              <div class="form-group">
                <label>Danh mục Cha <span class="required">*</span></label>
                <select v-model="form.parentCategory" :class="{ 'input-error': fieldErrors.parentCategory }">
                  <option value="">-- Chọn danh mục cha --</option>
                  <option v-for="parentCat in parentCategories" :key="parentCat.id_danhmuc_cha"
                    :value="String(parentCat.id_danhmuc_cha)">
                    {{ parentCat.ten_danhmuc }}
                  </option>
                </select>
                <p v-if="fieldErrors.parentCategory" class="field-error">{{ fieldErrors.parentCategory }}</p>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Danh mục Con <span class="required">*</span></label>
                <select v-model="form.category" :class="{ 'input-error': fieldErrors.category }"
                  :disabled="!form.parentCategory">
                  <option value="">-- Chọn danh mục con --</option>
                  <option v-for="category in childCategories" :key="category.id_danhmuc"
                    :value="String(category.id_danhmuc)">
                    {{ category.ten_danhmuc }}
                  </option>
                </select>
                <p v-if="fieldErrors.category" class="field-error">{{ fieldErrors.category }}</p>
              </div>
              <div class="form-group">
                <label>Thương hiệu <span class="required">*</span></label>
                <select v-model="form.brand" :class="{ 'input-error': fieldErrors.brand }" :disabled="!form.category">
                  <option value="">-- Chọn thương hiệu --</option>
                  <option v-for="brand in brands" :key="brand.id_thuonghieu" :value="brand.id_thuonghieu">
                    {{ brand.ten_thuonghieu }}
                  </option>
                </select>
                <p v-if="fieldErrors.brand" class="field-error">{{ fieldErrors.brand }}</p>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Khối lượng</label>
                <input v-model="form.weight" type="number" min="0" step="0.01" placeholder="VD: 2.5" />
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select v-model="form.status" :class="{ 'input-error': fieldErrors.status }">
                  <option>Đang bán</option>
                  <option>Nháp</option>
                </select>
                <p v-if="fieldErrors.status" class="field-error">{{ fieldErrors.status }}</p>
              </div>
            </div>

            <div class="vs-wrapper" v-if="form.category">
              <div class="vs-header">
                <div class="vs-title">
                  <span class="vs-bar"></span>
                  Biến thể sản phẩm
                  <span class="vs-tier-count" :class="{ 'at-limit': variationTierIds.size >= 3 }">
                    Cấp biến thể: {{ variationTierIds.size }}/3
                  </span>
                </div>

                <div class="vs-steps">
                  <span class="vss" :class="{ active: vsPhase === 1, done: vsPhase === 2 }">
                    <span class="vss-dot">{{ vsPhase === 2 ? '✓' : '1' }}</span>
                    Chọn giá trị
                  </span>
                  <span class="vss-line"></span>
                  <span class="vss" :class="{ active: vsPhase === 2 }">
                    <span class="vss-dot">2</span>
                    Điền giá &amp; kho
                  </span>
                </div>
              </div>

              <template v-if="vsPhase === 1">
                <div v-if="variantLoading" class="group-placeholder">
                  <span>Đang tải dữ liệu biến thể...</span>
                </div>

                <div v-else class="group-tabs">
                  <button v-for="g in attributeGroups" :key="g.id" class="gtab"
                    :class="{ 'gtab-active': selectedGroupId === g.id }" @click="selectGroup(g.id)">
                    <span class="gtab-icon">{{ g.icon }}</span>
                    <span class="gtab-name">{{ g.name }}</span>
                    <span v-if="selectedCountInGroup(g) > 0" class="gtab-badge">
                      {{ selectedCountInGroup(g) }}
                    </span>
                  </button>
                </div>

                <div v-if="displayAttrTypes.length" class="flat-select-table">
                  <div v-for="t in displayAttrTypes" :key="t.id" class="fst-row">
                    <div class="fst-label">
                      <div class="fst-label-top">
                        <span class="type-pill" :class="'tp-' + t.color">{{ t.label }}</span>
                        <span v-if="selectedOptions[t.id]?.size" class="fst-count">
                          {{ selectedOptions[t.id].size }}
                        </span>
                      </div>
                      <button class="tier-toggle-btn" :class="{ 'is-tier': variationTierIds.has(t.id) }"
                        @click="toggleVariationTier(t.id)"
                        title="Dùng thuộc tính này để tạo các phiên bản (SKU) khác nhau về giá/kho">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12"
                          height="12">
                          <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                        </svg>
                        <span>{{ variationTierIds.has(t.id) ? 'Đang làm Biến thể' : 'Làm Biến thể?' }}</span>
                      </button>
                    </div>

                    <div class="fst-options-wrap">
                      <div class="fst-options">
                        <button v-for="opt in t.options" :key="getOptionValue(opt)" class="vbtn" :class="[
                          'vbtn-' + t.color,
                          { 'vbtn-on': isSelected(t.id, getOptionValue(opt)) }
                        ]" @click="toggleOption(t.id, getOptionValue(opt))">
                          <svg v-if="isSelected(t.id, getOptionValue(opt))" viewBox="0 0 10 10" fill="none"
                            stroke="currentColor" stroke-width="2.2" stroke-linecap="round" width="9" height="9">
                            <polyline points="1,5 3.5,7.5 9,2" />
                          </svg>

                          <span v-if="getOptionHex(opt)" class="color-option">
                            <span class="color-dot" :style="{ background: getOptionHex(opt) }"></span>
                            {{ getOptionLabel(opt) }}
                          </span>
                          <span v-else>
                            {{ getOptionLabel(opt) }}
                          </span>
                        </button>
                      </div>

                      <p v-if="fieldErrors.variantGroups && fieldErrors.variantGroups[t.id]" class="field-error">
                        {{ fieldErrors.variantGroups[t.id] }}
                      </p>
                    </div>
                  </div>
                </div>

                <div v-else class="group-placeholder">
                  <span>Không có dữ liệu loại thuộc tính</span>
                </div>

                <div class="p1-footer">
                  <div v-if="allSelectedAttrTypes.length > 0" class="combo-bar">
                    <span class="combo-formula">
                      <template v-for="(t, i) in allSelectedAttrTypes" :key="t.id">
                        <span class="cf-item">
                          <span class="type-pill-sm" :class="'tp-' + t.color">{{ t.label }}</span>
                          <b>{{ selectedOptions[t.id]?.size }}</b>
                        </span>
                        <span v-if="i < allSelectedAttrTypes.length - 1" class="cf-x">×</span>
                      </template>
                      <span class="cf-eq">= <b>{{ comboCount }} biến thể</b></span>
                    </span>
                  </div>

                  <div class="p1-actions">
                    <span v-if="fieldErrors.variants" class="field-error">
                      {{ fieldErrors.variants }}
                    </span>
                    <span v-else class="p1-hint">
                      Có thể chọn nhiều tab; hệ thống sẽ gộp tất cả lựa chọn để tạo biến thể
                    </span>

                    <div class="p1-action-buttons">
                      <button v-if="isEditMode && !hasVariantSelectionChanged" class="btn-back-variants"
                        @click="continueVariantTable">
                        Quay lại biến thể
                      </button>

                      <button class="btn-generate" @click="generateVariants">
                        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.2"
                          stroke-linecap="round" width="13" height="13">
                          <rect x="1" y="1" width="12" height="12" rx="2" />
                          <polyline points="3.5,7 5.5,9 10.5,4.5" />
                        </svg>
                        {{ isEditMode ? 'Cập nhật tổ hợp' : `Tự động sinh tổ hợp` }}
                      </button>
                    </div>
                  </div>
                </div>
              </template>

              <template v-if="vsPhase === 2">
                <div class="p2-toolbar">
                  <button class="btn-back" @click="backToSelect">
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                      width="11" height="11">
                      <polyline points="7.5,1.5 3,6 7.5,10.5" />
                    </svg>
                    {{ isEditMode ? 'Quay lại chọn / chỉnh biến thể' : 'Chỉnh lại lựa chọn' }}
                  </button>

                  <div class="modal-excel-actions">
                    <button class="btn-xl-sm btn-xl-export" title="Xuất danh sách biến thể ra Excel"
                      @click="handleExportVariantsExcel">
                      <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5"
                        fill="none">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                      </svg>
                      Xuất Excel
                    </button>
                    <button class="btn-xl-sm btn-xl-import"
                      title="Nhập danh sách biến thể từ Excel (Tự động check trùng)"
                      @click="triggerImportVariantsExcel">
                      <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5"
                        fill="none">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="17 8 12 3 7 8" />
                        <line x1="12" y1="3" x2="12" y2="15" />
                      </svg>
                      Nhập Excel (Check trùng)
                    </button>
                    <input type="file" ref="importVariantsExcelRef" style="display: none" accept=".xlsx, .xls"
                      @change="handleImportVariantsExcel" />
                  </div>
                </div>

                <div class="p2-controls">
                  <div class="p2-info">
                    <template v-if="isEditMode">
                      Đang hiển thị <b>{{ generatedRows.length }}</b> biến thể hiện có.
                    </template>
                    <template v-else>
                      Đã tạo <b>{{ generatedRows.length }}</b> tổ hợp thực tế.
                    </template>
                  </div>

                  <div class="bulk-stack">
                    <div class="bulk-bar">
                      <span class="bulk-lbl">Giá/kho chung:</span>
                      <input :value="formatCurrency(basePrice)" @input="basePrice = parseCurrency($event.target.value)" class="bulk-in" placeholder="Giá chung (₫)" />
                      <input v-model="baseStock" class="bulk-in bulk-num" type="number" min="0" placeholder="Kho chung" />
                    </div>
                    <div class="bulk-actions">
                      <button class="btn-apply-outline" @click="applyRulesToAll(false)">
                        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"
                          stroke-linecap="round" width="12" height="12">
                          <circle cx="7" cy="7" r="5.5" />
                          <polyline points="4.5,7 6,8.5 9.5,5" />
                        </svg>
                        Chỉ điền ô trống
                      </button>
                      <button class="btn-apply-solid" @click="applyRulesToAll(true)">
                        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"
                          stroke-linecap="round" width="12" height="12">
                          <polyline points="1.5,7 5,10.5 12.5,3" />
                        </svg>
                        Áp dụng tất cả
                      </button>
                    </div>
                  </div>
                </div>


                <div class="vt-scroll">

                  <table class="vt-table">
                    <thead>
                      <tr>
                        <th class="th-no">#</th>
                        <th v-for="t in tableHeaders" :key="t.id">
                          <span class="type-pill" :class="'tp-' + t.color">{{ t.label }}</span>
                        </th>
                        <th class="th-price">Giá riêng (₫)</th>
                        <th class="th-stock">Kho</th>
                        <th class="th-del"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, ri) in paginatedVariants" :key="row.id" class="vt-row">
                        <td class="td-no">
                          <span class="row-no">
                            {{ (variantCurrentPage - 1) * VARIANTS_PER_PAGE + ri + 1 }}
                          </span>
                        </td>

                        <td v-for="t in tableHeaders" :key="t.id">
                          <span class="val-chip" :class="'vc-' + t.color">
                            {{ row.attrs[t.id] || '' }}
                          </span>
                        </td>

                        <td>
                          <input :value="formatCurrency(row.price)" type="text" class="vt-input"
                            @input="(e) => { row.price = parseCurrency(e.target.value); markManualPrice(row) }" />
                        </td>

                        <td>
                          <input :value="row.stock" type="number" min="0" class="vt-input vt-num"
                            @input="(e) => { row.stock = e.target.value; markManualStock(row) }" />
                        </td>

                        <td class="td-del">
                          <button class="btn-row-del" @click="removeVariantRow(ri)" title="Xóa phiên bản này">
                            ×
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div v-if="generatedRows.length > VARIANTS_PER_PAGE" class="variant-pagination">
                  <button :disabled="variantCurrentPage === 1" @click="goToVariantPage(variantCurrentPage - 1)">
                    ‹
                  </button>

                  <button v-for="(p, index) in variantPageItems" :key="`variant-${p}-${index}`"
                    :class="{ 'pg-active': p === variantCurrentPage, 'pg-dots': p === '...' }" :disabled="p === '...'"
                    @click="p !== '...' && goToVariantPage(p)">
                    {{ p }}
                  </button>

                  <button :disabled="variantCurrentPage === variantTotalPages"
                    @click="goToVariantPage(variantCurrentPage + 1)">
                    ›
                  </button>
                </div>

                <div class="p2-foot">
                  <span class="p2-count">
                    <b>{{ generatedRows.length }}</b> biến thể —
                    trang <b>{{ variantCurrentPage }}</b>/{{ variantTotalPages }}
                  </span>
                </div>
              </template>
            </div>
            
            <div v-else class="vs-wrapper" style="text-align: center; padding: 40px; color: #94a3b8;">
              <p>Vui lòng chọn danh mục sản phẩm trước khi cấu hình biến thể.</p>
            </div>

            <p v-if="fieldErrors.variantRows" class="field-error">
              {{ fieldErrors.variantRows }}
            </p>
            <p v-if="formError" class="form-error">⚠ {{ formError }}</p>

          <!-- Inline footer actions -->
          <div class="inline-form-footer">
            <button class="btn-cancel" @click="closeModal">Hủy</button>
            <button class="btn-submit" @click="submitForm">
              {{ isEditMode ? 'Lưu thay đổi' : 'Thêm sản phẩm' }}
            </button>
          </div>
      </div><!-- end inline-form-body -->
    </template><!-- end product-form -->

    <!-- Modal Danh sách sản phẩm sắp hết hàng -->
    <Teleport to="body">
      <div v-if="showLowStockModal" class="modal-overlay" @click.self="closeLowStockModal">
        <div class="modal modal-wide">
          <div class="modal-header">
            <h3>Danh sách sản phẩm sắp hết hàng</h3>
            <button class="modal-close" @click="closeLowStockModal">×</button>
          </div>
          <div class="modal-body">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>STT</th>
                    <th>Hình ảnh</th>
                    <th>Tên SP</th>
                    <th>Hành động</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!lowStockProducts.length">
                    <td colspan="4" class="empty">Không có sản phẩm nào sắp hết hàng.</td>
                  </tr>
                  <tr v-for="(p, i) in lowStockProducts" :key="p.id">
                    <td>{{ i + 1 }}</td>
                    <td>
                      <img :src="p.img" :alt="p.name"
                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;" />
                    </td>
                    <td><b>{{ p.name }}</b><br><span style="font-size: 11px; color: #94a3b8;">SKU: {{ p.sku }}</span>
                    </td>
                    <td>
                      <button class="btn-apply-solid"
                        style="padding: 6px 12px; font-size: 12px; border-radius: 6px; border: none; background: #2563eb; color: white; cursor: pointer;"
                        @click="openLowStockVariantsModal(p)">Xem chi tiết</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Danh sách biến thể sắp hết hàng -->
    <Teleport to="body">
      <div v-if="showLowStockVariantsModal" class="modal-overlay" @click.self="closeLowStockVariantsModal">
        <div class="modal">
          <div class="modal-header">
            <h3>Biến thể sắp hết hàng - {{ selectedLowStockProduct?.name }}</h3>
            <button class="modal-close" @click="closeLowStockVariantsModal">×</button>
          </div>
          <div class="modal-body">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Tên biến thể</th>
                    <th>Số lượng</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="v in selectedLowStockProduct?.bienThes.filter(v => Number(v.soluong ?? 0) < 10)"
                    :key="v.id_bienthe || v.id">
                    <td><b>{{ v.ten_bienthe || 'Biến thể' }}</b></td>
                    <td><b style="color: #ef4444;">{{ v.soluong }}</b></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.admin {
  padding: 32px 48px;
  background: #f5f7fb;
  min-height: 100vh;
  font-family: 'Segoe UI', sans-serif;
}

/* ── Inline Form Header ── */
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

/* ═══════════════════════════════════════
   INLINE FORM — Form Elements Redesign
══════════════════════════════════════ */

/* Mỗi form-group trong inline-form-body là 1 card riêng */
.inline-form-body > .form-group,
.inline-form-body > .products-selection-section {
  background: white;
  border-radius: 14px;
  border: 1px solid #edf0f7;
  padding: 22px 24px !important;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  border-top: none !important;
  margin-top: 0 !important;
  gap: 12px;
}

/* 2-col grid */
.inline-form-body > .form-row,
.inline-form-body > .form-cols-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.inline-form-body > .form-row > .form-group,
.inline-form-body > .form-cols-2 > .form-group {
  background: white;
  border-radius: 14px;
  border: 1px solid #edf0f7;
  padding: 22px 24px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  gap: 10px;
}

/* Label */
.inline-form-body .form-group label {
  font-size: 11.5px;
  font-weight: 700;
  color: #6b7280;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  margin-bottom: 4px;
}

/* Input / Select / Textarea */
.inline-form-body .form-group input:not([type='file']),
.inline-form-body .form-group select,
.inline-form-body .form-group textarea {
  padding: 12px 16px;
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

.inline-form-body .form-group input:focus,
.inline-form-body .form-group select:focus,
.inline-form-body .form-group textarea:focus {
  border-color: #4f46e5;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.inline-form-body .form-group input.input-error,
.inline-form-body .form-group select.input-error {
  border-color: #f87171;
  background: #fff5f5;
}

/* Upload zone redesign */
.inline-form-body .upload-zone {
  border: 2px dashed #c7d2fe;
  background: linear-gradient(135deg, #f0f1ff 0%, #fafbff 100%);
  border-radius: 14px;
  padding: 44px 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.25s;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.inline-form-body .upload-zone:hover {
  border-color: #4f46e5;
  background: linear-gradient(135deg, #ebe8ff 0%, #f0f4ff 100%);
}

.inline-form-body .upload-zone svg {
  width: 44px;
  height: 44px;
  color: #6366f1;
}

.inline-form-body .upload-zone p {
  font-size: 14px;
  color: #475569;
  margin: 0;
  font-weight: 500;
}

.inline-form-body .upload-zone p span {
  color: #4f46e5;
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 3px;
}

.inline-form-body .upload-zone small {
  font-size: 12px;
  color: #94a3b8;
}

/* Field errors */
.inline-form-body .field-error {
  font-size: 12px;
  color: #ef4444;
  margin: 2px 0 0;
}

.inline-form-body .form-error {
  font-size: 13px;
  color: #dc2626;
  background: #fef2f2;
  border: 1.5px solid #fecaca;
  padding: 12px 16px;
  border-radius: 10px;
  margin: 0;
  font-weight: 500;
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

.excel-actions {
  display: flex;
  gap: 10px;
}

.btn-excel {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid #e2e8f0;
  background: white;
}

.btn-excel:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-export {
  color: #16a34a;
}

.btn-export:hover:not(:disabled) {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.btn-import {
  color: #2563eb;
}

.btn-import:hover:not(:disabled) {
  background: #eff6ff;
  border-color: #bfdbfe;
}

.modal-excel-actions {
  display: flex;
  gap: 8px;
  margin-left: 12px;
}

.btn-xl-sm {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid #e2e8f0;
  background: white;
}

.btn-xl-export {
  color: #16a34a;
}

.btn-xl-export:hover {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.btn-xl-import {
  color: #2563eb;
}

.btn-xl-import:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
}

.spinner-sm {
  width: 14px;
  height: 14px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  border: 1px solid transparent;
  position: relative;
  overflow: hidden;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}

.stat-card::after {
  content: '';
  position: absolute;
  width: 110px;
  height: 110px;
  border-radius: 999px;
  right: -24px;
  top: -24px;
  background: rgba(255, 255, 255, 0.12);
}

.stat-card.stat-blue {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  color: #fff;
}

.stat-card.stat-orange {
  background: linear-gradient(135deg, #c2410c 0%, #f97316 100%);
  color: #fff;
}

.stat-card.stat-teal {
  background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
  color: #fff;
}

.clickable-stat {
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.clickable-stat:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 28px rgba(15, 23, 42, 0.2);
}

.stat-card p {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.88);
  margin: 0 0 4px;
}

.stat-card b {
  font-size: 22px;
  font-weight: 700;
  color: #fff;
}

.stat-icon {
  font-size: 22px;
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon.blue {
  background: rgba(255, 255, 255, 0.18)
}

.stat-icon.green {
  background: #dcfce7
}

.stat-icon.red {
  background: rgba(255, 255, 255, 0.18)
}

.stat-icon.purple {
  background: rgba(255, 255, 255, 0.18)
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

/* ── Custom Premium Dropdown ── */
.custom-dropdown {
  position: relative;
  min-width: 170px;
  user-select: none;
}

.dropdown-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 13px;
  color: #334155;
  cursor: pointer;
  transition: all .2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.dropdown-trigger:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.dropdown-trigger .chevron {
  width: 16px;
  height: 16px;
  color: #64748b;
  transition: transform .2s ease;
}

.dropdown-trigger .chevron.open {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  z-index: 1000;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 6px;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 2px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
  max-height: 240px;
  overflow-y: auto;
}

/* Custom Scrollbar for Dropdown Menu */
.dropdown-menu::-webkit-scrollbar {
  width: 6px;
}

.dropdown-menu::-webkit-scrollbar-track {
  background: transparent;
}

.dropdown-menu::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

.dropdown-menu li {
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.12s ease;
  text-align: left;
}

.dropdown-menu li:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.dropdown-menu li.active {
  background: #475569;
  color: white;
  font-weight: 600;
}

/* Dropdown Transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all .2s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.product-bulk-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin: 0 0 16px;
  padding: 14px 16px;
  background: #ffffff;
  border: 1px solid #e8edf5;
  border-radius: 14px;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
}

.bulk-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.bulk-summary-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  color: #2563eb;
  font-weight: 800;
  flex-shrink: 0;
}

.bulk-summary b {
  display: block;
  font-size: 13px;
  color: #0f172a;
  margin-bottom: 2px;
}

.bulk-summary p {
  margin: 0;
  font-size: 12px;
  color: #64748b;
}

.bulk-toolbar-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  flex-wrap: wrap;
}

.bulk-btn {
  min-height: 34px;
  padding: 8px 12px;
  border-radius: 9px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all .18s;
  border: 1px solid transparent;
  font-family: inherit;
  white-space: nowrap;
}

.bulk-btn:disabled {
  opacity: .48;
  cursor: not-allowed;
  transform: none !important;
}

.bulk-btn.ghost {
  background: #f8fafc;
  color: #334155;
  border-color: #e2e8f0;
}

.bulk-btn.ghost:hover:not(:disabled) {
  background: #eff6ff;
  color: #2563eb;
  border-color: #bfdbfe;
}

.bulk-btn.danger {
  background: #ef4444;
  color: #ffffff;
  border-color: #ef4444;
}

.bulk-btn.danger:hover:not(:disabled) {
  background: #dc2626;
  border-color: #dc2626;
  transform: translateY(-1px);
}

.bulk-btn.danger-outline {
  background: #fff1f2;
  color: #dc2626;
  border-color: #fecaca;
}

.bulk-btn.danger-outline:hover:not(:disabled) {
  background: #ffe4e6;
  border-color: #fca5a5;
}

.table-wrap {
  background: white;
  border-radius: 14px;
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead tr {
  background: #f8fafc;
}

thead th {
  padding: 13px 16px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-align: left;
  letter-spacing: .04em;
  border-bottom: 1px solid #f1f5f9;
}

tbody tr {
  border-bottom: 1px solid #f8fafc;
  transition: background .15s;
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

tbody td {
  padding: 14px 16px;
  font-size: 13px;
  color: #334155;
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

.empty {
  text-align: center;
  color: #94a3b8;
  padding: 40px !important;
}

.product-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.product-cell img {
  width: 42px;
  height: 42px;
  border-radius: 8px;
  object-fit: cover;
  flex-shrink: 0;
}

.product-cell b {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 2px;
}

.product-cell span {
  font-size: 11px;
  color: #94a3b8;
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 999px;
  background: #dbeafe;
  color: #1d4ed8;
  white-space: nowrap;
}

.price {
  font-weight: 600;
  color: #0f172a;
}

.stock-cell span {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  display: block;
  margin-bottom: 5px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 999px;
  white-space: nowrap;
}

.status-badge.active {
  background: #dcfce7;
  color: #16a34a;
}

.status-badge.draft {
  background: #f1f5f9;
  color: #64748b;
}

.actions {
  display: flex;
  gap: 6px;
}

.act-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: all .2s;
}

.act-btn svg {
  width: 14px;
  height: 14px;
}

.act-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #2563eb;
}

.act-btn.danger:hover {
  background: #fee2e2;
  border-color: #fecaca;
  color: #ef4444;
}

.pagination {
  display: flex;
  gap: 6px;
  margin-top: 20px;
  justify-content: flex-end;
}

.pagination button {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 13px;
  cursor: pointer;
  color: #334155;
  transition: all .2s;
}

.pagination button:hover {
  border-color: #2563eb;
  color: #2563eb;
}

.pg-active {
  background: #2563eb !important;
  border-color: #2563eb !important;
  color: white !important;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, .6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
}

.modal {
  background: white;
  border-radius: 18px;
  width: 100%;
  max-width: 600px;
  box-shadow: 0 32px 80px rgba(0, 0, 0, .22);
  animation: modalIn .22s cubic-bezier(.22, 1, .36, 1);
  max-height: 94vh;
  overflow-y: auto;
}

.modal-wide {
  max-width: 960px;
}

@keyframes modalIn {
  from {
    opacity: 0;
    transform: translateY(16px) scale(.97)
  }

  to {
    opacity: 1;
    transform: none
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px 16px;
  border-bottom: 1px solid #f1f5f9;
  position: sticky;
  top: 0;
  background: white;
  z-index: 2;
  border-radius: 18px 18px 0 0;
}

.modal-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  color: #94a3b8;
  cursor: pointer;
  line-height: 1;
  padding: 0;
}

.modal-close:hover {
  color: #0f172a;
}

.modal-body {
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px 20px;
  border-top: 1px solid #f1f5f9;
  position: sticky;
  bottom: 0;
  background: white;
  z-index: 2;
  border-radius: 0 0 18px 18px;
}

.upload-zone {
  border: 2px dashed #cbd5e1;
  border-radius: 10px;
  padding: 26px 20px;
  text-align: center;
  cursor: pointer;
  transition: border-color .2s, background .2s;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.upload-zone:hover {
  border-color: #2563eb;
  background: #f0f6ff;
}

.upload-zone svg {
  width: 36px;
  height: 36px;
  color: #94a3b8;
}

.upload-zone p {
  font-size: 14px;
  color: #475569;
  margin: 0;
}

.upload-zone p span {
  color: #2563eb;
  font-weight: 600;
}

.upload-zone small {
  font-size: 12px;
  color: #94a3b8;
}

.img-preview-wrap {
  display: flex;
  align-items: center;
  gap: 16px;
  background: #f8fafc;
  border-radius: 10px;
  padding: 14px;
  border: 1px solid #e2e8f0;
}

.img-preview {
  width: 88px;
  height: 88px;
  object-fit: cover;
  border-radius: 8px;
  flex-shrink: 0;
}

.img-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.img-change {
  padding: 7px 14px;
  border-radius: 7px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
}

.img-change:hover {
  border-color: #2563eb;
  color: #2563eb;
}

.img-remove-btn {
  padding: 7px 14px;
  border-radius: 7px;
  border: 1px solid #fecaca;
  background: #fef2f2;
  font-size: 12px;
  font-weight: 600;
  color: #ef4444;
  cursor: pointer;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  letter-spacing: .03em;
}

.required {
  color: #ef4444;
}

.form-group input,
.form-group select {
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  font-size: 13px;
  color: #0f172a;
  outline: none;
  transition: border-color .2s;
  background: #fff;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, .08);
}

.form-error {
  font-size: 12px;
  color: #ef4444;
  background: #fef2f2;
  border: 1px solid #fecaca;
  padding: 8px 12px;
  border-radius: 8px;
  margin: 0;
}

.btn-cancel {
  padding: 10px 20px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
}

.btn-cancel:hover {
  background: #f8fafc;
}

.btn-submit {
  padding: 10px 22px;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: white;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.btn-submit:hover {
  opacity: .9;
  transform: translateY(-1px);
}

.vs-wrapper {
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  padding: 18px 20px;
  background: #fafbff;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.vs-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
}

.vs-title {
  display: flex;
  align-items: center;
  gap: 9px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.vs-bar {
  display: inline-block;
  width: 3px;
  height: 17px;
  background: linear-gradient(180deg, #2563eb, #4f46e5);
  border-radius: 2px;
  flex-shrink: 0;
}

.vs-steps {
  display: flex;
  align-items: center;
  gap: 8px;
}

.vss {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #94a3b8;
}

.vss.active {
  color: #2563eb;
}

.vss.done {
  color: #16a34a;
}

.vss-dot {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid #e2e8f0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  background: white;
}

.vss.active .vss-dot {
  border-color: #2563eb;
  background: #2563eb;
  color: white;
}

.vss.done .vss-dot {
  border-color: #16a34a;
  background: #16a34a;
  color: white;
}

.fst-label {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 140px;
}

.fst-label-top {
  display: flex;
  align-items: center;
  gap: 8px;
}

.tier-toggle-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  width: fit-content;
}

.tier-toggle-btn svg {
  opacity: 0.6;
}

.tier-toggle-btn:hover {
  background: #e2e8f0;
  color: #475569;
}

.tier-toggle-btn.is-tier {
  background: #eff6ff;
  color: #2563eb;
  border-color: #bfdbfe;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.tier-toggle-btn.is-tier svg {
  opacity: 1;
}

.vss-line {
  width: 24px;
  height: 2px;
  background: #e2e8f0;
  border-radius: 1px;
}

.group-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.gtab {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 13px;
  border-radius: 8px;
  border: 1.5px solid #e2e8f0;
  background: white;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all .15s;
  font-family: inherit;
}

.gtab:hover {
  border-color: #93c5fd;
  color: #2563eb;
}

.p2-header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 16px 0;
  padding: 0 4px;
}

.btn-add-manual {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f0f9ff;
  color: #0369a1;
  border: 1px solid #bae6fd;
  padding: 8px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-add-manual:hover {
  background: #e0f2fe;
  border-color: #7dd3fc;
}

.th-del {
  width: 40px;
}

.td-del {
  text-align: center;
}

.btn-row-del {
  width: 26px;
  height: 26px;
  border-radius: 6px;
  border: 1px solid #fee2e2;
  background: #fff1f2;
  color: #ef4444;
  font-size: 18px;
  line-height: 1;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-row-del:hover {
  background: #ffe4e6;
  border-color: #fecaca;
  transform: scale(1.1);
}

.gtab-active {
  border-color: #2563eb !important;
  background: #eff6ff !important;
  color: #1d4ed8 !important;
}

.gtab-icon {
  font-size: 15px;
}

.gtab-name {
  white-space: nowrap;
}

.gtab-badge {
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  padding: 0 5px;
  background: #2563eb;
  color: white;
  font-size: 10px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.flat-select-table {
  background: white;
  border: 1px solid #e8edf5;
  border-radius: 12px;
  overflow: hidden;
}

.fst-row {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 11px 16px;
  border-bottom: 1px solid #f1f5f9;
}

.fst-row:last-child {
  border-bottom: none;
}

.fst-label {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  min-width: 72px;
  flex-shrink: 0;
  padding-top: 2px;
}

.fst-count {
  font-size: 10px;
  font-weight: 700;
  color: #2563eb;
  background: #dbeafe;
  padding: 1px 6px;
  border-radius: 8px;
}

.fst-options {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  flex: 1;
}

.type-pill {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
}

.tp-blue {
  background: #dbeafe;
  color: #1d4ed8;
}

.tp-green {
  background: #dcfce7;
  color: #166534;
}

.tp-amber {
  background: #fef3c7;
  color: #92400e;
}

.tp-pink {
  background: #fce7f3;
  color: #be185d;
}

.tp-purple {
  background: #f3e8ff;
  color: #6b21a8;
}

.tp-teal {
  background: #ccfbf1;
  color: #0f766e;
}

.tp-red {
  background: #fee2e2;
  color: #991b1b;
}

.type-pill-sm {
  display: inline-flex;
  align-items: center;
  padding: 2px 7px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 700;
}

.type-pill-sm.tp-blue {
  background: #dbeafe;
  color: #1d4ed8;
}

.type-pill-sm.tp-green {
  background: #dcfce7;
  color: #166534;
}

.type-pill-sm.tp-amber {
  background: #fef3c7;
  color: #92400e;
}

.type-pill-sm.tp-pink {
  background: #fce7f3;
  color: #be185d;
}

.type-pill-sm.tp-purple {
  background: #f3e8ff;
  color: #6b21a8;
}

.type-pill-sm.tp-teal {
  background: #ccfbf1;
  color: #0f766e;
}

.vbtn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 11px;
  border-radius: 6px;
  border: 1.5px solid #e2e8f0;
  background: white;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all .12s;
  white-space: nowrap;
  font-family: inherit;
}

.vbtn-blue:hover {
  border-color: #93c5fd;
  color: #2563eb;
}

.vbtn-green:hover {
  border-color: #86efac;
  color: #166534;
}

.vbtn-amber:hover {
  border-color: #fcd34d;
  color: #92400e;
}

.vbtn-pink:hover {
  border-color: #f9a8d4;
  color: #be185d;
}

.vbtn-purple:hover {
  border-color: #d8b4fe;
  color: #6b21a8;
}

.vbtn-teal:hover {
  border-color: #5eead4;
  color: #0f766e;
}

.vbtn-red:hover {
  border-color: #fca5a5;
  color: #991b1b;
}

.vbtn-blue.vbtn-on {
  border-color: #2563eb;
  background: #2563eb;
  color: white;
  font-weight: 600;
}

.vbtn-green.vbtn-on {
  border-color: #16a34a;
  background: #16a34a;
  color: white;
  font-weight: 600;
}

.vbtn-amber.vbtn-on {
  border-color: #d97706;
  background: #d97706;
  color: white;
  font-weight: 600;
}

.vbtn-pink.vbtn-on {
  border-color: #db2777;
  background: #db2777;
  color: white;
  font-weight: 600;
}

.vbtn-purple.vbtn-on {
  border-color: #7c3aed;
  background: #7c3aed;
  color: white;
  font-weight: 600;
}

.vbtn-teal.vbtn-on {
  border-color: #0d9488;
  background: #0d9488;
  color: white;
  font-weight: 600;
}

.vbtn-red.vbtn-on {
  border-color: #dc2626;
  background: #dc2626;
  color: white;
  font-weight: 600;
}

.color-option {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.color-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid rgba(15, 23, 42, 0.18);
  flex-shrink: 0;
}

.group-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 28px 20px;
  color: #94a3b8;
  font-size: 12px;
  background: white;
  border: 1.5px dashed #e2e8f0;
  border-radius: 10px;
}

.p1-footer {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 13px 16px;
  background: white;
  border: 1px solid #e8edf5;
  border-radius: 10px;
}

.combo-formula {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.cf-item {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.cf-item b {
  font-size: 13px;
  color: #0f172a;
}

.cf-x {
  font-size: 13px;
  color: #94a3b8;
  font-weight: 600;
}

.cf-eq {
  font-size: 12px;
  color: #64748b;
  padding-left: 4px;
}

.cf-eq b {
  font-size: 13px;
  color: #2563eb;
}

.p1-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.p1-hint {
  font-size: 12px;
  color: #94a3b8;
}

.btn-generate {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: white;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(37, 99, 235, .28);
  transition: opacity .2s, transform .2s;
  font-family: inherit;
}

.btn-generate:hover {
  opacity: .9;
  transform: translateY(-1px);
}

.btn-generate:disabled {
  background: #e2e8f0;
  color: #94a3b8;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.p2-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 13px;
  border-radius: 8px;
  border: 1.5px solid #e2e8f0;
  background: white;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all .2s;
  font-family: inherit;
}

.btn-back:hover {
  border-color: #2563eb;
  color: #2563eb;
  background: #eff6ff;
}

.bulk-bar {
  display: flex;
  align-items: center;
  gap: 7px;
  flex-wrap: wrap;
  padding: 7px 11px;
  background: #f8fafc;
  border-radius: 9px;
  border: 1px solid #f1f5f9;
}

.bulk-lbl {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  white-space: nowrap;
}

.bulk-in {
  padding: 6px 9px;
  border-radius: 7px;
  border: 1px solid #e2e8f0;
  font-size: 12px;
  color: #0f172a;
  outline: none;
  background: white;
  transition: border-color .2s;
  width: 108px;
  font-family: inherit;
}

.bulk-in:focus {
  border-color: #2563eb;
}

.bulk-num {
  width: 68px;
  text-align: right;
}

.btn-apply {
  padding: 6px 13px;
  border-radius: 7px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: white;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  font-family: inherit;
}

.btn-apply:hover {
  opacity: .88;
}

.p2-info {
  font-size: 12px;
  color: #92400e;
  padding: 8px 13px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 8px;
}

.p2-info b {
  color: #92400e;
}

.vt-scroll {
  overflow-x: auto;
  border: 1px solid #e8edf5;
  border-radius: 12px;
  background: white;
}

.vt-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 480px;
}

.vt-table thead tr {
  background: #f8fafc;
}

.vt-table thead th {
  padding: 9px 12px;
  font-size: 10px;
  font-weight: 700;
  color: #94a3b8;
  text-align: left;
  letter-spacing: .06em;
  border-bottom: 1px solid #f1f5f9;
  white-space: nowrap;
}

.th-no {
  width: 38px;
  text-align: center;
}

.th-price {
  min-width: 138px;
}

.th-stock {
  width: 76px;
}

.th-act {
  width: 44px;
  text-align: center;
}

.vt-row {
  border-bottom: 1px solid #f8fafc;
  transition: background .12s;
}

.vbtn-red.vbtn-on {
  border-color: #dc2626;
  background: #dc2626;
  color: white;
  font-weight: 600;
}

/* Placeholder */
.group-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 28px 20px;
  color: #94a3b8;
  font-size: 12px;
  background: white;
  border: 1.5px dashed #e2e8f0;
  border-radius: 10px;
}

/* ── PHASE 1 FOOTER ── */
.p1-footer {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 13px 16px;
  background: white;
  border: 1px solid #e8edf5;
  border-radius: 10px;
}

.combo-formula {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.td-act {
  text-align: center;
}

.row-no {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.val-chip {
  display: inline-block;
  font-size: 12px;
  font-weight: 500;
  padding: 3px 9px;
  border-radius: 5px;
  white-space: nowrap;
  border: 1px solid;
}

.vc-blue {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1d4ed8;
}

.vc-green {
  background: #f0fdf4;
  border-color: #bbf7d0;
  color: #166534;
}

.vc-amber {
  background: #fffbeb;
  border-color: #fde68a;
  color: #92400e;
}

.vc-pink {
  background: #fdf2f8;
  border-color: #f9a8d4;
  color: #be185d;
}

.vc-purple {
  background: #faf5ff;
  border-color: #e9d5ff;
  color: #6b21a8;
}

.vc-teal {
  background: #f0fdfa;
  border-color: #99f6e4;
  color: #0f766e;
}

.vc-red {
  background: #fef2f2;
  border-color: #fecaca;
  color: #991b1b;
}

.vt-input {
  width: 100%;
  padding: 7px 9px;
  border-radius: 7px;
  border: 1px solid #e2e8f0;
  font-size: 12px;
  color: #0f172a;
  outline: none;
  background: #fafbff;
  transition: border-color .2s;
  box-sizing: border-box;
  font-family: inherit;
}

.cf-eq b {
  font-size: 13px;
  color: #2563eb;
}

.vt-num {
  width: 50px;
  text-align: right;
}

.ra-del {
  width: 26px;
  height: 26px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  transition: all .2s;
}

.bulk-bar {
  display: flex;
  align-items: center;
  gap: 7px;
  flex-wrap: wrap;
  padding: 7px 11px;
  background: #f8fafc;
  border-radius: 9px;
  border: 1px solid #f1f5f9;
}

.p2-foot {
  display: flex;
  justify-content: flex-end;
}

.p2-count {
  font-size: 12px;
  color: #64748b;
}

.p2-count b {
  color: #0f172a;
}

.multi-preview-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 12px;
}

.multi-preview-item {
  position: relative;
  width: 88px;
  height: 88px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
}

.multi-preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.multi-preview-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 22px;
  height: 22px;
  border: none;
  border-radius: 50%;
  background: rgba(15, 23, 42, 0.75);
  color: white;
  font-size: 14px;
  line-height: 1;
  cursor: pointer;
}

.pagination button:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.pg-dots {
  border-color: transparent !important;
  background: transparent !important;
  color: #94a3b8 !important;
}

.variant-pagination {
  display: flex;
  gap: 6px;
  margin-top: 12px;
  justify-content: flex-end;
}

.variant-pagination button {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 13px;
  cursor: pointer;
  color: #334155;
  transition: all .2s;
}

.variant-pagination button:hover {
  border-color: #2563eb;
  color: #2563eb;
}

.variant-pagination button:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.p1-action-buttons {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-back-variants {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 10px;
  border: 1.5px solid #cbd5e1;
  background: white;
  color: #475569;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all .2s;
  font-family: inherit;
}

.btn-back-variants:hover {
  border-color: #2563eb;
  color: #2563eb;
  background: #eff6ff;
}

.input-error {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.08) !important;
}

.field-error {
  font-size: 12px;
  color: #ef4444;
  margin: 4px 0 0;
  line-height: 1.4;
}

.fst-options-wrap {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.bulk-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-apply-outline {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 8px;
  border: 1.5px solid #2563eb;
  background: white;
  color: #2563eb;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
  font-family: inherit;
}

.btn-apply-outline:hover {
  background: #eff6ff;
}

.btn-apply-solid {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: white;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 3px 10px rgba(37, 99, 235, .25);
  transition: all .2s;
  font-family: inherit;
}

.btn-apply-solid:hover {
  opacity: .88;
  transform: translateY(-1px);
}

@media (max-width: 768px) {
  .admin {
    padding: 20px 16px;
  }

  .stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .filter-bar {
    flex-wrap: wrap;
  }

  .table-wrap {
    overflow-x: auto;
  }

  table {
    min-width: 700px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .modal-wide {
    max-width: 98vw;
  }

  .vs-steps {
    display: none;
  }

  .p2-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .fst-row {
    flex-direction: column;
    gap: 8px;
  }
}

.vs-tier-count {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  background: #f1f5f9;
  color: #64748b;
  border-radius: 20px;
  transition: all 0.3s;
  margin-left: 10px;
}

.vs-tier-count.at-limit {
  background: #fff7ed;
  color: #ea580c;
  border: 1px solid #ffedd5;
}

.p2-controls {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin: 14px 0 10px;
}

.p2-controls .bulk-stack {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
}

.p2-controls .bulk-bar {
  display: flex;
  align-items: center;
  gap: 7px;
}

.p2-controls .bulk-actions {
  display: flex;
  gap: 8px;
}

/* ── PARENT CATEGORY TABS ── */
.parent-tabs {
  display: flex;
  gap: 12px;
  margin: 24px 0 16px;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(10px);
  padding: 6px;
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
  width: fit-content;
}

.parent-tab-btn {
  background: transparent;
  border: none;
  padding: 10px 22px;
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  border-radius: 9px;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: inherit;
}

.parent-tab-btn:hover {
  color: #0f172a;
  background: rgba(241, 245, 249, 0.8);
}

.parent-tab-btn.active {
  color: #2563eb;
  background: #ffffff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
  border: 1px solid rgba(37, 99, 235, 0.1);
}

</style>
