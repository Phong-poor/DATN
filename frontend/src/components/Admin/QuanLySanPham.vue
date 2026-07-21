<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { getUser } from '@/services/auth'
import api from '@/services/api'
import { normalizeImageUrl, productImageUrl, storageUrl } from '@/services/urls'

const user = ref(getUser() || {})
const hasPermission = (perm) => {
  if (user.value?.vaitro === 'admin') return true
  return user.value?.cac_quyen?.includes(perm)
}
import { invalidateProductsPrefetchCache } from '@/services/productsPrefetch'

const PRODUCTS_CACHE_KEY = 'nextgen_admin_products_cache'
const PRODUCTS_CACHE_TTL = 2 * 60 * 1000
let xlsxModulePromise = null
let swalModulePromise = null

const loadXlsx = async () => {
  if (!xlsxModulePromise) xlsxModulePromise = import('xlsx')
  return xlsxModulePromise
}

const getSwal = async () => {
  if (!swalModulePromise) swalModulePromise = import('@/services/swal')
  return (await swalModulePromise).default
}

const swal = new Proxy({}, {
  get: (_, method) => async (...args) => {
    const service = await getSwal()
    return service[method](...args)
  },
})

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   DANH SÁCH SẢN PHẨM
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
  if (!selectedCategory.value) return 'T\u1ea5t c\u1ea3 danh m\u1ee5c'
  const found = categories.value.find(c => String(c.id_danhmuc) === String(selectedCategory.value))
  return found ? found.ten_danhmuc : 'T\u1ea5t c\u1ea3 danh m\u1ee5c'
}

// Custom Tree Select States
const treeSearchQuery = ref('')
const expandedParentIds = ref(new Set())

// Custom Accordion & Variant UI states
const activeAccordionGroups = ref(new Set())

const toggleAccordionGroup = (groupId) => {
  const gIdStr = String(groupId)
  if (activeAccordionGroups.value.has(gIdStr)) {
    activeAccordionGroups.value.delete(gIdStr)
  } else {
    activeAccordionGroups.value.add(gIdStr)
  }
  activeAccordionGroups.value = new Set(activeAccordionGroups.value)
}

const selectAllOptions = (typeId, options) => {
  const tIdStr = String(typeId)
  if (!selectedOptions.value[tIdStr]) {
    selectedOptions.value[tIdStr] = new Set()
  }
  const set = selectedOptions.value[tIdStr]
  options.forEach(opt => {
    set.add(getOptionValue(opt))
  })
  
  // Auto switch to Variant mode if multiple options exist
  if (options.length > 1 && !variationTierIds.value.has(tIdStr)) {
    if (variationTierIds.value.size >= 3) {
      swal.warning('Giới hạn biến thể', 'Chỉ được chọn tối đa 3 cấp biến thể. Các thuộc tính khác sẽ được lưu vào Thông số kỹ thuật.')
      return
    }
    variationTierIds.value.add(tIdStr)
  }
  
  selectedOptions.value = { ...selectedOptions.value }
}

const clearAllOptions = (typeId) => {
  const tIdStr = String(typeId)
  if (selectedOptions.value[tIdStr]) {
    selectedOptions.value[tIdStr].clear()
    selectedOptions.value = { ...selectedOptions.value }
  }
}

const liveComboPreview = computed(() => {
  const headers = variationHeaders.value
  if (!headers.length) return []
  
  const arrays = headers.map(t => [...(selectedOptions.value[t.id] || [])])
  if (arrays.some(a => a.length === 0)) return []
  
  const combos = cartesian(arrays)
  return combos.map(combo => combo.join(' - '))
})

const toggleParentExpand = (parentId) => {
  const pIdStr = String(parentId)
  if (expandedParentIds.value.has(pIdStr)) {
    expandedParentIds.value.delete(pIdStr)
  } else {
    expandedParentIds.value.add(pIdStr)
  }
  expandedParentIds.value = new Set(expandedParentIds.value)
}

const isParentExpanded = (parentId) => {
  return expandedParentIds.value.has(String(parentId))
}

const selectTreeCategory = (child) => {
  form.value.category = String(child.id_danhmuc)
  treeSearchQuery.value = ''
}

const getSelectedCategoryName = () => {
  if (!form.value.category) return 'Chọn danh mục'
  const child = categories.value.find(c => String(c.id_danhmuc) === String(form.value.category))
  if (!child) return 'Chọn danh mục'
  const parent = parentCategories.value.find(p => String(p.id_danhmuc_cha) === String(child.id_danhmuc_cha))
  return parent ? `${parent.ten_danhmuc} > ${child.ten_danhmuc}` : child.ten_danhmuc
}

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
const isProductsFetching = ref(false)
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
const importExcelRef = ref(null)
const importVariantsExcelRef = ref(null)

const filteredCategoriesForDropdown = computed(() => {
  if (!selectedParentTab.value) return categories.value
  return categories.value.filter(c => String(c.id_danhmuc_cha) === String(selectedParentTab.value))
})

const categoriesTree = computed(() => {
  if (!parentCategories.value.length || !categories.value.length) return []
  return parentCategories.value.map(parent => {
    return {
      ...parent,
      children: categories.value.filter(child => String(child.id_danhmuc_cha) === String(parent.id_danhmuc_cha))
    }
  }).filter(parent => parent.children.length > 0)
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

watch([searchQuery, selectedStatus, selectedCategory, selectedParentTab], () => {
  currentPage.value = 1
})

const getErrorMessage = (error, fallback) => {
  const errors = error?.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const firstKey = Object.keys(errors)[0]
    if (firstKey && Array.isArray(errors[firstKey]) && errors[firstKey][0]) {
      return errors[firstKey][0]
    }
  }

  if (error?.response?.data?.message) return error.response.data.message
  if (error?.response?.data?.error) return error.response.data.error

  return fallback
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   NHẬP XUẤT EXCEL
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const handleExportExcel = async () => {
  if (isExporting.value) return
  isExporting.value = true
  try {
    const XLSX = await loadXlsx()
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
      const XLSX = await loadXlsx()
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
        swal.warning('Không hợp lệ', 'Không tìm thấy dữ liệu hợp lệ trong file Excel')
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   NHẬP XUẤT EXCEL BIẾN THỂ (TRONG MODAL)
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const handleExportVariantsExcel = async () => {
  const headers = tableHeaders.value
  if (!headers.length) {
    swal.warning('Không có dữ liệu', 'Không có thuộc tính để xuất')
    return
  }

  const XLSX = await loadXlsx()
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
  reader.onload = async (event) => {
    try {
      const XLSX = await loadXlsx()
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

const loadProductsCache = () => {
  try {
    const raw = localStorage.getItem(PRODUCTS_CACHE_KEY)
    if (!raw) return false
    const cached = JSON.parse(raw)
    if (!cached?.products || Date.now() - cached.fetchedAt > PRODUCTS_CACHE_TTL) return false
    products.value = cached.products
    return true
  } catch {
    return false
  }
}

const saveProductsCache = () => {
  try {
    localStorage.setItem(PRODUCTS_CACHE_KEY, JSON.stringify({
      fetchedAt: Date.now(),
      products: products.value,
    }))
  } catch {
    // Ignore storage errors.
  }
}

const invalidateProductCaches = (productId = null) => {
  try {
    localStorage.removeItem(PRODUCTS_CACHE_KEY)
  } catch {
    // Ignore storage errors.
  }

  invalidateProductsPrefetchCache(productId)
}

const fetchProducts = async () => {
  if (isProductsFetching.value) return
  isProductsFetching.value = true
  try {
    const res = await api.get('/sanpham', { skipGlobalLoader: true })

    products.value = res.data.map(p => {
      const bienThes = Array.isArray(p.bien_thes) ? p.bien_thes : []
      const variantCount = bienThes.length

      return {
        id: p.id_sanpham,
        name: p.tenSP,
        sku: p.SKU || '',
        category: p.danh_muc?.ten_danhmuc || 'Ch\u01b0a c\u00f3 danh m\u1ee5c',
        categoryId: p.id_danhmuc ?? '',
        parentCategoryId: p.danh_muc?.id_danhmuc_cha ?? '',
        brand: p.thuong_hieu?.ten_thuonghieu || 'Ch\u01b0a c\u00f3 th\u01b0\u01a1ng hi\u1ec7u',
        totalVariants: variantCount,
        updated_at: p.updated_at,
        bienThes,
        status: String(p.trangthai) === '1' ? '\u0110ang b\u00e1n' : 'Nh\u00e1p',
        img: productImageUrl(p, null, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=200'),
      }
    })
    saveProductsCache()
  } catch (error) {
    formError.value = getErrorMessage(error, 'Không tải được danh sách sản phẩm.')
  } finally {
    isProductsFetching.value = false
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   NHÓM THUỘC TÍNH & LOẠI THUỘC TÍNH
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
  return groupIconMap[name] || 'PKG'
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

  if (attributeGroups.value.length > 0) {
    const nextActive = new Set(activeAccordionGroups.value)
    attributeGroups.value.forEach(g => nextActive.add(String(g.id)))
    activeAccordionGroups.value = nextActive
  }

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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   MODAL & FORM
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
  status: '\u0110ang b\u00e1n',
  img: '',
  images: [],
  weight: '',
})

const form = ref(defaultForm())
const fieldErrors = ref({})

/**
 * Watch child category change - automatically assign parent category, filter brands, and rebuild attribute groups
 */
watch(() => form.value.category, async (newCategoryId) => {
  fieldErrors.value.category = ''
  fieldErrors.value.parentCategory = ''
  if (newCategoryId) {
    const child = categories.value.find(c => String(c.id_danhmuc) === String(newCategoryId))
    if (child && child.id_danhmuc_cha) {
      form.value.parentCategory = String(child.id_danhmuc_cha)
      
      const parent = parentCategories.value.find(p => String(p.id_danhmuc_cha) === String(child.id_danhmuc_cha))
      if (parent && parent.ten_danhmuc.toLowerCase().includes('phụ kiện')) {
        variationTierIds.value.add('color-type')
      }
    }
  } else {
    form.value.parentCategory = ''
  }
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
    errors.img = 'Vui l\u00f2ng ch\u1ecdn \u1ea3nh s\u1ea3n ph\u1ea9m'
  }

  const imagesArr = Array.isArray(form.value.images) ? form.value.images : []
  if (imagesArr.length > MAX_EXTRA_IMAGES) {
    errors.images = `Chỉ được chọn tối đa ${MAX_EXTRA_IMAGES} ảnh phụ`
  }

  const nameVal = form.value.name ? String(form.value.name).trim() : ''
  if (!nameVal) {
    errors.name = 'T\u00ean s\u1ea3n ph\u1ea9m kh\u00f4ng \u0111\u01b0\u1ee3c \u0111\u1ec3 tr\u1ed1ng'
  } else if (nameVal.length < 3) {
    errors.name = 'T\u00ean s\u1ea3n ph\u1ea9m ph\u1ea3i c\u00f3 \u00edt nh\u1ea5t 3 k\u00fd t\u1ef1'
  } else if (nameVal.length > 255) {
    errors.name = 'T\u00ean s\u1ea3n ph\u1ea9m kh\u00f4ng \u0111\u01b0\u1ee3c v\u01b0\u1ee3t qu\u00e1 255 k\u00fd t\u1ef1'
  }

  if (!form.value.brand) {
    errors.brand = 'Vui l\u00f2ng ch\u1ecdn th\u01b0\u01a1ng hi\u1ec7u'
  }

  if (!form.value.parentCategory) {
    errors.parentCategory = 'Vui l\u00f2ng ch\u1ecdn danh m\u1ee5c cha'
  }

  if (!form.value.category) {
    errors.category = 'Vui l\u00f2ng ch\u1ecdn danh m\u1ee5c con'
  }

  if (!['\u0110ang b\u00e1n', 'Nh\u00e1p'].includes(form.value.status)) {
    errors.status = 'Tr\u1ea1ng th\u00e1i kh\u00f4ng h\u1ee3p l\u1ec7'
  }

  if (form.value.weight !== '' && form.value.weight !== null && form.value.weight !== undefined) {
    const weight = Number(form.value.weight)

    if (Number.isNaN(weight)) {
      errors.weight = 'Kh\u1ed1i l\u01b0\u1ee3ng ph\u1ea3i l\u00e0 s\u1ed1'
    } else if (weight <= 0) {
      errors.weight = 'Kh\u1ed1i l\u01b0\u1ee3ng ph\u1ea3i l\u1edbn h\u01a1n 0'
    } else if (weight > 1000) {
      errors.weight = 'Kh\u1ed1i l\u01b0\u1ee3ng kh\u00f4ng h\u1ee3p l\u1ec7'
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
    fieldErrors.value.img = '\u1ea2nh s\u1ea3n ph\u1ea9m ch\u1ec9 ch\u1ea5p nh\u1eadn PNG, JPG, JPEG, WEBP'
    if (fileInputRef.value) fileInputRef.value.value = ''
    return
  }

  if (file.size > MAX_MAIN_IMAGE_SIZE) {
    fieldErrors.value.img = '\u1ea2nh s\u1ea3n ph\u1ea9m kh\u00f4ng \u0111\u01b0\u1ee3c v\u01b0\u1ee3t qu\u00e1 5MB'
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

const onMainImageDrop = (event) => {
  const file = event.dataTransfer?.files?.[0]
  if (!file) return
  onFileChange({ target: { files: [file] } })
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
    fieldErrors.value.images = '\u1ea2nh ph\u1ee5 ch\u1ec9 ch\u1ea5p nh\u1eadn PNG, JPG, JPEG, WEBP'
    if (extraFileInputRef.value) extraFileInputRef.value.value = ''
    return
  }

  const invalidSizeFile = files.find(file => file.size > MAX_EXTRA_IMAGE_SIZE)
  if (invalidSizeFile) {
    fieldErrors.value.images = 'M\u1ed7i \u1ea3nh ph\u1ee5 kh\u00f4ng \u0111\u01b0\u1ee3c v\u01b0\u1ee3t qu\u00e1 5MB'
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
          reader.onerror = () => reject(new Error('Kh\u00f4ng \u0111\u1ecdc \u0111\u01b0\u1ee3c file \u1ea3nh'))
          reader.readAsDataURL(file)
        })
      })
    )

    form.value.images = [...form.value.images, ...files]
    extraImagePreviews.value = [...extraImagePreviews.value, ...base64Images]
  } catch (error) {
    console.error(error)
    fieldErrors.value.images = 'Kh\u00f4ng \u0111\u1ecdc \u0111\u01b0\u1ee3c m\u1ed9t ho\u1eb7c nhi\u1ec1u \u1ea3nh ph\u1ee5'
  } finally {
    readingExtraImages.value = false
    if (extraFileInputRef.value) extraFileInputRef.value.value = ''
  }
}

const onExtraImagesDrop = (event) => {
  const files = Array.from(event.dataTransfer?.files || [])
  if (!files.length) return
  onExtraFilesChange({ target: { files } })
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

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   VARIANT STATE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
  const tIdStr = String(typeId)
  if (variationTierIds.value.has(tIdStr)) {
    variationTierIds.value.delete(tIdStr)
    // Coerce selected options of this type to at most 1 element when switched to Specification mode
    if (selectedOptions.value[tIdStr] && selectedOptions.value[tIdStr].size > 1) {
      const firstVal = Array.from(selectedOptions.value[tIdStr])[0]
      selectedOptions.value[tIdStr] = new Set([firstVal])
      selectedOptions.value = { ...selectedOptions.value }
    }
  } else {
    if (variationTierIds.value.size >= 3) {
      swal.warning('Giới hạn biến thể', 'Chỉ được chọn tối đa 3 cấp biến thể. Các thuộc tính khác sẽ được lưu vào Thông số kỹ thuật.')
      return
    }
    variationTierIds.value.add(tIdStr)
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

    if (!hasPrice || !hasStock) return true
    if (hasPrice && Number(row.price) <= 0) return true
    if (hasStock && Number(row.stock) < 0) return true

    return false
  })

  if (invalidRow) {
    fieldErrors.value.variantRows = 'Vui lòng nhập đủ giá riêng > 0 và kho >= 0 cho tất cả biến thể'
    return false
  }

  fieldErrors.value.variantRows = ''
  return true
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   RESET / OPEN / CLOSE / SUBMIT
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
  variationTierIds.value = new Set()

  basePrice.value = ''
  baseStock.value = ''
  selectedGroupId.value = attributeGroups.value.length > 0
    ? attributeGroups.value[0].id
    : null

  treeSearchQuery.value = ''
  expandedParentIds.value = new Set()

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
    status: String(product?.trangthai ?? product?.trang_thai ?? '') === '1' ? '\u0110ang b\u00e1n' : 'Nh\u00e1p',
    img: '',
    images: [],
    weight: product?.khoiluong ?? '',
  }

  if (product?.danh_muc?.id_danhmuc_cha) {
    expandedParentIds.value = new Set([String(product.danh_muc.id_danhmuc_cha)])
  } else {
    expandedParentIds.value = new Set()
  }

  const productImages = Array.isArray(product?.hinh_anhs)
    ? product.hinh_anhs
    : Array.isArray(product?.hinhAnhs)
      ? product.hinhAnhs
      : []

  imgPreview.value = productImageUrl(product, null, '')

  extraImagePreviews.value = productImages
    .map(img => normalizeImageUrl(img?.duongdan || img?.duong_dan, ''))
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
      trangthai: form.value.status === '\u0110ang b\u00e1n' ? 1 : 0,
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
        id_bienthe: row.isExisting ? Number(row.id) : null,
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
      invalidateProductCaches(editingProductId.value)
      await api.put(`/admin/sanpham/${editingProductId.value}`, payload)
      invalidateProductCaches(editingProductId.value)
      swal.success('Thành công', 'Cập nhật sản phẩm thành công')
    } else {
      const response = await api.post('/admin/sanpham', payload)
      invalidateProductCaches(response.data?.data?.id_sanpham || null)
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
  loadProductsCache()
  fetchProducts()
  Promise.allSettled([
    fetchParentCategories(),
    fetchCategories(),
    fetchBrands(),
    fetchAttributeGroups(),
    fetchColors(),
  ])
})
</script>

<template>
  <div class="admin">

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         VIEW: DANH SÁCH sản phẩm (top, stats, filter, table)
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <template v-if="currentView === 'list'">

    <div class="top">
      <div class="excel-actions">
        <button class="btn-excel btn-export" @click="handleExportExcel" :disabled="isExporting">
          <svg v-if="!isExporting" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2"
            fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          <span v-else class="spinner-sm"></span>
          {{ isExporting ? '\u0110ang xu\u1ea5t...' : 'Xu\u1ea5t Excel' }}
        </button>

        <button v-if="hasPermission('nhap_xuat_kho')" class="btn-excel btn-import" @click="triggerImportExcel" :disabled="isImporting">
          <svg v-if="!isImporting" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2"
            fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
          <span v-else class="spinner-sm"></span>
          {{ isImporting ? '\u0110ang nh\u1eadp...' : 'Nh\u1eadp Excel' }}
        </button>
        <input type="file" ref="importExcelRef" style="display: none" accept=".xlsx, .xls"
          @change="handleImportExcel" />

        <button v-if="hasPermission('san_pham_sua')" class="add-btn" @click="openModal">+ Th&#234;m s&#7843;n ph&#7849;m</button>
      </div>
    </div>

    <div class="stats">
      <div class="stat-card stat-blue">
        <span class="stat-icon blue" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
            <path d="m3.3 7 8.7 5 8.7-5" />
            <path d="M12 22V12" />
          </svg>
        </span>
        <div>
          <p>T&#7893;ng s&#7843;n ph&#7849;m</p>
          <b>{{ totalProductStats.toLocaleString('vi-VN') }}</b>
        </div>
      </div>
      <div class="stat-card stat-orange clickable-stat" @click="openLowStockModal">
        <span class="stat-icon red" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
          </svg>
        </span>
        <div>
          <p>S&#7855;p h&#7871;t h&#224;ng</p>
          <b>{{ lowStockStats.toLocaleString('vi-VN') }}</b>
        </div>
      </div>

      <div class="stat-card stat-teal">
        <span class="stat-icon purple" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 21h18" />
            <path d="M5 21V8l6 4V8l6 4v9" />
            <path d="M17 21v-5h2v5" />
            <path d="M7 16h2" />
            <path d="M11 16h2" />
          </svg>
        </span>
        <div>
          <p>Kho l&#432;u tr&#7919;</p>
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
        T&#7845;t c&#7843; s&#7843;n ph&#7849;m
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
        <input v-model="searchQuery" placeholder="T&#236;m ki&#7871;m t&#234;n s&#7843;n ph&#7849;m, SKU..." />
      </div>
      <!-- Custom Status Dropdown -->
      <div class="custom-dropdown">
        <div class="dropdown-trigger" @click.stop="isOpenStatusDropdown = !isOpenStatusDropdown; isOpenCategoryDropdown = false">
          <span>{{ selectedStatus || 'T\u1ea5t c\u1ea3 tr\u1ea1ng th\u00e1i' }}</span>
          <svg class="chevron" :class="{ open: isOpenStatusDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
        <transition name="fade-slide">
          <ul v-if="isOpenStatusDropdown" class="dropdown-menu">
            <li :class="{ active: selectedStatus === '' }" @click="selectedStatus = ''; isOpenStatusDropdown = false">T&#7845;t c&#7843; tr&#7841;ng th&#225;i</li>
            <li :class="{ active: selectedStatus === '\u0110ang b\u00e1n' }" @click="selectedStatus = '\u0110ang b\u00e1n'; isOpenStatusDropdown = false">&#272;ang b&#225;n</li>
            <li :class="{ active: selectedStatus === 'Nh\u00e1p' }" @click="selectedStatus = 'Nh\u00e1p'; isOpenStatusDropdown = false">Nh&#225;p</li>
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
            <li :class="{ active: selectedCategory === '' }" @click="selectedCategory = ''; isOpenCategoryDropdown = false">T&#7845;t c&#7843; danh m&#7909;c</li>
            <li v-for="category in filteredCategoriesForDropdown" :key="category.id_danhmuc" 
                :class="{ active: String(selectedCategory) === String(category.id_danhmuc) }" 
                @click="selectedCategory = category.id_danhmuc; isOpenCategoryDropdown = false">
              {{ category.ten_danhmuc }}
            </li>
          </ul>
        </transition>
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>STT</th>
            <th>S&#7843;n ph&#7849;m</th>
            <th>Danh m&#7909;c</th>
            <th>Th&#432;&#417;ng hi&#7879;u</th>
            <th>T&#7893;ng bi&#7871;n th&#7875;</th>
            <th>Tr&#7841;ng th&#225;i</th>
            <th>Thao t&#225;c</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!paginatedProducts.length">
            <td colspan="7" class="empty">Kh&#244;ng t&#236;m th&#7845;y s&#7843;n ph&#7849;m n&#224;o.</td>
          </tr>
          <tr v-for="(p, i) in paginatedProducts" :key="p.id">
            <td>
              {{ (currentPage - 1) * PER_PAGE + i + 1 }}
            </td>
            <td>
              <div class="product-cell">
                <img :src="p.img" :alt="p.name" loading="lazy" decoding="async" />
                <div><b>{{ p.name }}</b><span>SKU: {{ p.sku }}</span></div>
              </div>
            </td>
            <td><span class="badge">{{ p.category }}</span></td>
            <td><span class="price">{{ p.brand }}</span></td>
            <td>
              <div class="stock-cell">
                <span>{{ p.totalVariants }} bi&#7871;n th&#7875;</span>
              </div>
            </td>
            <td><span class="status-badge" :class="p.status === '\u0110ang b\u00e1n' ? 'active' : 'draft'">{{ p.status }}</span>
            </td>
            <td>
              <div class="actions">
                <button v-if="hasPermission('san_pham_sua')" class="act-btn" title="S&#7917;a" @click="openEditModal(p.id)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                  </svg>
                </button>
              </div>
            </td>

          </tr>
        </tbody>
      </table>
    </div>

    <div class="pagination">
      <button :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">&#8249;</button>

      <span class="pg-active page-indicator">{{ currentPage }}/{{ totalPages }}</span>

      <button :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">&#8250;</button>
    </div>

    </template><!-- end list view -->

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         VIEW: FORM SẢN PHẨM (Thêm / Sửa)
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <template v-if="currentView === 'product-form'">
      <div class="inline-form-header">
        <button class="back-btn" @click="closeModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
            <path d="M15 18l-6-6 6-6"/>
          </svg>
          Quay l&#7841;i danh s&#225;ch
        </button>
        <h1 v-if="isEditMode">Ch&#7881;nh s&#7917;a s&#7843;n ph&#7849;m</h1>
        <h1 v-else>Th&#234;m s&#7843;n ph&#7849;m m&#7899;i</h1>
        <p v-if="isEditMode">C&#7853;p nh&#7853;t th&#244;ng tin v&#224; bi&#7871;n th&#7875; c&#7911;a s&#7843;n ph&#7849;m</p>
        <p v-else>&#272;i&#7873;n &#273;&#7847;y &#273;&#7911; th&#244;ng tin &#273;&#7875; th&#234;m s&#7843;n ph&#7849;m v&#224;o h&#7879; th&#7889;ng</p>
      </div>

      <div class="inline-form-body">
        <div class="form-section-card images-section-card">
          <div class="form-section-title">H&#236;nh &#7843;nh s&#7843;n ph&#7849;m</div>
          <div class="form-group">
            <label>&#7842;nh s&#7843;n ph&#7849;m <span class="required">*</span></label>
            <input id="product-main-image-input" ref="fileInputRef" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" class="visually-hidden-file" @change="onFileChange" />
            <label
              v-if="!imgPreview"
              class="upload-zone"
              for="product-main-image-input"
              @dragover.prevent
              @drop.prevent="onMainImageDrop"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="17 8 12 3 7 8" />
                <line x1="12" y1="3" x2="12" y2="15" />
              </svg>
              <p>K&#233;o th&#7843; ho&#7863;c <span>b&#7845;m &#273;&#7875; ch&#7885;n &#7843;nh</span></p>
              <small>PNG, JPG, WEBP - t&#7889;i &#273;a 5MB</small>
            </label>
            <div v-else class="img-preview-wrap">
              <button class="img-remove-btn" @click="removeImg">X&#243;a</button>
              <img :src="imgPreview" class="img-preview" alt="Ảnh sản phẩm" />
            </div>
            <p v-if="fieldErrors.img" class="field-error">{{ fieldErrors.img }}</p>
          </div>

          <div class="form-group">
            <label>H&#236;nh &#7843;nh ph&#7909;</label>
            <input id="product-extra-images-input" ref="extraFileInputRef" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" multiple class="visually-hidden-file" @change="onExtraFilesChange" />
            <label
              class="upload-zone"
              for="product-extra-images-input"
              @dragover.prevent
              @drop.prevent="onExtraImagesDrop"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="17 8 12 3 7 8" />
                <line x1="12" y1="3" x2="12" y2="15" />
              </svg>
              <p>K&#233;o th&#7843; ho&#7863;c <span>b&#7845;m &#273;&#7875; ch&#7885;n nhi&#7873;u &#7843;nh</span></p>
              <small>PNG, JPG, WEBP - c&#243; th&#7875; ch&#7885;n nhi&#7873;u &#7843;nh</small>
            </label>
            <p v-if="fieldErrors.images" class="field-error">{{ fieldErrors.images }}</p>
            <div v-if="extraImagePreviews.length" class="multi-preview-wrap">
              <div v-for="(img, index) in extraImagePreviews" :key="index" class="multi-preview-item">
                <img :src="img" class="multi-preview-img" :alt="'Ảnh phụ ' + (index + 1)" />
                <button class="multi-preview-remove" @click="removeExtraImage(index)">x</button>
              </div>
            </div>
          </div>
        </div>

        <div class="product-form-grid">
          <div class="form-main-col">
            <div class="form-section-card">
              <div class="form-section-title">Th&#244;ng tin c&#417; b&#7843;n</div>
              <div class="form-group">
                <label>T&#234;n s&#7843;n ph&#7849;m <span class="required">*</span></label>
                <input v-model="form.name" @input="fieldErrors.name = ''" placeholder="VD: MacBook Pro 14 inch M4 Pro" :class="{ 'input-error': fieldErrors.name }" />
                <p v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</p>
              </div>
              <div class="form-fields-row-3">
                <div class="form-group">
                  <label>Th&#432;&#417;ng hi&#7879;u <span class="required">*</span></label>
                  <select v-model="form.brand" @change="fieldErrors.brand = ''" :class="{ 'input-error': fieldErrors.brand }" :disabled="!form.category">
                    <option value="">-- Ch&#7885;n th&#432;&#417;ng hi&#7879;u --</option>
                    <option v-for="brand in brands" :key="brand.id_thuonghieu" :value="brand.id_thuonghieu">
                      {{ brand.ten_thuonghieu }}
                    </option>
                  </select>
                  <p v-if="fieldErrors.brand" class="field-error">{{ fieldErrors.brand }}</p>
                </div>
                <div class="form-group">
                  <label>Kh&#7889;i l&#432;&#7907;ng (kg)</label>
                  <input v-model="form.weight" type="number" min="0" step="0.01" @input="fieldErrors.weight = ''" placeholder="VD: 2.5" />
                  <p v-if="fieldErrors.weight" class="field-error">{{ fieldErrors.weight }}</p>
                </div>
                <div class="form-group">
                  <label>Tr&#7841;ng th&#225;i</label>
                  <select v-model="form.status" @change="fieldErrors.status = ''" :class="{ 'input-error': fieldErrors.status }">
                    <option>&#272;ang b&#225;n</option>
                    <option>Nh&#225;p</option>
                  </select>
                  <p v-if="fieldErrors.status" class="field-error">{{ fieldErrors.status }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="form-sidebar-col">
            <div class="form-section-card sticky-sidebar-card">
              <div class="form-section-title">Danh m&#7909;c s&#7843;n ph&#7849;m <span class="required">*</span></div>
              <div class="selected-category-badge" :class="{ 'has-selected': form.category }">
                <span class="badge-icon">&#128193;</span>
                <span class="badge-text">{{ form.category ? getSelectedCategoryName() : 'Chưa chọn danh mục sản phẩm' }}</span>
              </div>
              <div class="tree-select-static-container" :class="{ 'has-error': fieldErrors.category }">
                <div class="tree-search-wrapper">
                  <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.35-4.35" />
                  </svg>
                  <input v-model="treeSearchQuery" placeholder="T&#236;m ki&#7871;m nhanh danh m&#7909;c..." class="tree-search-input" />
                  <button v-if="treeSearchQuery" @click="treeSearchQuery = ''" class="clear-search-btn">x</button>
                </div>
                <div class="tree-list-container">
                  <div v-if="filteredTreeCategories.length === 0" class="tree-empty">Kh&#244;ng t&#236;m th&#7845;y danh m&#7909;c n&#224;o.</div>
                  <div v-for="parent in filteredTreeCategories" :key="parent.id_danhmuc_cha" class="tree-parent-node">
                    <div class="tree-parent-row" @click="toggleParentExpand(parent.id_danhmuc_cha)">
                      <span class="tree-toggle-icon">{{ isParentExpanded(parent.id_danhmuc_cha) ? 'v' : '>' }}</span>
                      <span class="tree-folder-icon">&#128193;</span>
                      <span class="tree-parent-name">{{ parent.ten_danhmuc }}</span>
                    </div>
                    <transition name="collapse">
                      <div v-show="isParentExpanded(parent.id_danhmuc_cha)" class="tree-children-list">
                        <div v-for="child in parent.children" :key="child.id_danhmuc" class="tree-child-node" :class="{ selected: String(form.category) === String(child.id_danhmuc) }" @click="selectTreeCategory(child)">
                          <span class="tree-leaf-icon">&#128196;</span>
                          <span class="tree-child-name">{{ child.ten_danhmuc }}</span>
                        </div>
                      </div>
                    </transition>
                  </div>
                </div>
              </div>
              <p v-if="fieldErrors.category" class="field-error">{{ fieldErrors.category }}</p>
            </div>
          </div>
        </div>

        <div class="form-section-card variants-section-card" v-if="form.category">
          <div class="form-section-title">Bi&#7871;n th&#7875; s&#7843;n ph&#7849;m</div>
          <div class="vs-wrapper">
            <div class="vs-header">
              <div class="vs-title">
                <span class="vs-bar"></span>
                Bi&#7871;n th&#7875; s&#7843;n ph&#7849;m
                <span class="vs-tier-count" :class="{ 'at-limit': variationTierIds.size >= 3 }">C&#7845;p bi&#7871;n th&#7875;: {{ variationTierIds.size }}/3</span>
              </div>
              <div class="vs-steps">
                <span class="vss" :class="{ active: vsPhase === 1, done: vsPhase === 2 }"><span class="vss-dot">{{ vsPhase === 2 ? 'OK' : '1' }}</span>Ch&#7885;n gi&#225; tr&#7883;</span>
                <span class="vss-line"></span>
                <span class="vss" :class="{ active: vsPhase === 2 }"><span class="vss-dot">2</span>&#272;i&#7873;n gi&#225; &amp; kho</span>
              </div>
            </div>

            <template v-if="vsPhase === 1">
              <div v-if="variantLoading" class="group-placeholder"><span>&#272;ang t&#7843;i d&#7919; li&#7879;u bi&#7871;n th&#7875;...</span></div>
              <div v-else-if="attributeGroups.length === 0" class="group-placeholder"><span>Kh&#244;ng t&#236;m th&#7845;y nh&#243;m thu&#7897;c t&#237;nh t&#432;&#417;ng th&#237;ch cho danh m&#7909;c n&#224;y.</span></div>
              <div v-else class="accordion-container">
                <div v-for="g in attributeGroups" :key="g.id" class="accordion-item" :class="{ 'is-open': activeAccordionGroups.has(String(g.id)) }">
                  <div class="accordion-header" @click="toggleAccordionGroup(g.id)">
                    <div class="accordion-title">
                      <span class="accordion-icon">{{ g.icon }}</span>
                      <span class="accordion-name">{{ g.name }}</span>
                      <span v-if="selectedCountInGroup(g) > 0" class="accordion-badge">&#272;ang ch&#7885;n {{ selectedCountInGroup(g) }}</span>
                    </div>
                    <svg class="chevron" :class="{ open: activeAccordionGroups.has(String(g.id)) }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <transition name="collapse">
                    <div v-show="activeAccordionGroups.has(String(g.id))" class="accordion-body">
                      <div class="flat-select-table">
                        <div v-for="t in g.attrTypes" :key="t.id" class="fst-row">
                          <div class="fst-label">
                            <div class="fst-label-top">
                              <span class="type-pill" :class="'tp-' + t.color">{{ t.label }}</span>
                              <span v-if="selectedOptions[t.id]?.size" class="fst-count">{{ selectedOptions[t.id].size }}</span>
                            </div>
                            <div class="mode-switch-wrapper" title="Biến thể SKU: chọn nhiều để tạo tổ hợp. Thông số: chọn tối đa 1.">
                              <span class="mode-label" :class="{ active: variationTierIds.has(String(t.id)) }">{{ variationTierIds.has(String(t.id)) ? 'Biến thể' : 'Thông số' }}</span>
                              <label class="switch-control">
                                <input type="checkbox" :checked="variationTierIds.has(String(t.id))" @change="toggleVariationTier(t.id)" />
                                <span class="switch-slider"></span>
                              </label>
                            </div>
                          </div>
                          <div class="fst-options-wrap">
                            <div v-if="t.id === 'color-type'" class="color-swatches-grid">
                              <button v-for="opt in t.options" :key="getOptionValue(opt)" class="color-swatch-btn" :class="{ selected: isSelected(t.id, getOptionValue(opt)) }" @click="toggleOption(t.id, getOptionValue(opt))">
                                <span class="swatch-circle" :style="{ backgroundColor: getOptionHex(opt) || '#ccc' }"><span v-if="isSelected(t.id, getOptionValue(opt))" class="swatch-check">OK</span></span>
                                <span class="swatch-label">{{ getOptionLabel(opt) }}</span>
                              </button>
                            </div>
                            <div v-else class="fst-options">
                              <button v-for="opt in t.options" :key="getOptionValue(opt)" class="vbtn" :class="['vbtn-' + t.color, { 'vbtn-on': isSelected(t.id, getOptionValue(opt)) }]" @click="toggleOption(t.id, getOptionValue(opt))">
                                <span>{{ getOptionLabel(opt) }}</span>
                              </button>
                            </div>
                            <p v-if="fieldErrors.variantGroups && fieldErrors.variantGroups[t.id]" class="field-error">{{ fieldErrors.variantGroups[t.id] }}</p>
                          </div>
                          <div class="fst-actions-col">
                            <button class="quick-act-btn select-all" @click="selectAllOptions(t.id, t.options)">Ch&#7885;n t&#7845;t c&#7843;</button>
                            <button class="quick-act-btn clear-all" :disabled="!selectedOptions[t.id]?.size" @click="clearAllOptions(t.id)">B&#7887; ch&#7885;n</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </transition>
                </div>
              </div>

              <div class="p1-footer">
                <div v-if="allSelectedAttrTypes.length > 0" class="combo-bar">
                  <span class="combo-formula">
                    <template v-for="(t, i) in allSelectedAttrTypes" :key="t.id">
                      <span class="cf-item"><span class="type-pill-sm" :class="'tp-' + t.color">{{ t.label }}</span><b>{{ selectedOptions[t.id]?.size }}</b></span>
                      <span v-if="i < allSelectedAttrTypes.length - 1" class="cf-x">x</span>
                    </template>
                    <span class="cf-eq">= <b>{{ comboCount }} bi&#7871;n th&#7875;</b></span>
                  </span>
                </div>
                <div v-if="liveComboPreview.length > 0" class="live-preview-panel">
                  <div class="preview-title">Xem tr&#432;&#7899;c c&#225;c t&#7893; h&#7907;p ph&#226;n lo&#7841;i (t&#7889;i &#273;a 15):</div>
                  <div class="preview-tags-list">
                    <span v-for="(name, index) in liveComboPreview.slice(0, 15)" :key="index" class="preview-tag">{{ name }}</span>
                    <span v-if="liveComboPreview.length > 15" class="preview-tag-more">+ {{ liveComboPreview.length - 15 }} t&#7893; h&#7907;p kh&#225;c...</span>
                  </div>
                </div>
                <div class="p1-actions">
                  <span v-if="fieldErrors.variants" class="field-error">{{ fieldErrors.variants }}</span>
                  <span v-else class="p1-hint">M&#7903; r&#7897;ng c&#225;c nh&#243;m thu&#7897;c t&#237;nh b&#234;n tr&#234;n; h&#7879; th&#7889;ng s&#7869; t&#7921; &#273;&#7897;ng g&#7897;p t&#7845;t c&#7843; l&#7921;a ch&#7885;n &#273;&#7875; t&#7841;o SKU.</span>
                  <div class="p1-action-buttons">
                    <button v-if="isEditMode && !hasVariantSelectionChanged" class="btn-back-variants" @click="continueVariantTable">Quay l&#7841;i bi&#7871;n th&#7875;</button>
                    <button class="btn-generate" @click="generateVariants">{{ isEditMode ? 'Cập nhật tổ hợp' : 'Tự động sinh tổ hợp' }}</button>
                  </div>
                </div>
              </div>
            </template>

            <template v-if="vsPhase === 2">
              <div class="p2-toolbar">
                <button class="btn-back" @click="backToSelect">{{ isEditMode ? 'Quay lại chọn / chỉnh biến thể' : 'Chỉnh lại lựa chọn' }}</button>
                <div class="modal-excel-actions">
                  <button class="btn-xl-sm btn-xl-export" title="Xuất danh sách biến thể ra Excel" @click="handleExportVariantsExcel">Xu&#7845;t Excel</button>
                  <button class="btn-xl-sm btn-xl-import" title="Nhập danh sách biến thể từ Excel" @click="triggerImportVariantsExcel">Nh&#7853;p Excel</button>
                  <input type="file" ref="importVariantsExcelRef" style="display: none" accept=".xlsx, .xls" @change="handleImportVariantsExcel" />
                </div>
              </div>
              <div class="p2-controls">
                <div class="p2-info">
                  <template v-if="isEditMode">&#272;ang hi&#7875;n th&#7883; <b>{{ generatedRows.length }}</b> bi&#7871;n th&#7875; hi&#7879;n c&#243;.</template>
                  <template v-else>&#272;&#227; t&#7841;o <b>{{ generatedRows.length }}</b> t&#7893; h&#7907;p th&#7921;c t&#7871;.</template>
                </div>
                <div class="bulk-stack">
                  <div class="bulk-bar">
                    <span class="bulk-lbl">Gi&#225;/kho chung:</span>
                    <input :value="formatCurrency(basePrice)" @input="basePrice = parseCurrency($event.target.value)" class="bulk-in" placeholder="Giá chung (đ)" />
                    <input v-model="baseStock" class="bulk-in bulk-num" type="number" min="0" placeholder="Kho chung" />
                  </div>
                  <div class="bulk-actions">
                    <button class="btn-apply-outline" @click="applyRulesToAll(false)">Ch&#7881; &#273;i&#7873;n &#244; tr&#7889;ng</button>
                    <button class="btn-apply-solid" @click="applyRulesToAll(true)">&#193;p d&#7909;ng t&#7845;t c&#7843;</button>
                  </div>
                </div>
              </div>
              <div class="vt-scroll">
                <table class="vt-table">
                  <thead>
                    <tr>
                      <th class="th-no">#</th>
                      <th v-for="t in tableHeaders" :key="t.id"><span class="type-pill" :class="'tp-' + t.color">{{ t.label }}</span></th>
                      <th class="th-price">Gi&#225; ri&#234;ng (&#273;)</th>
                      <th class="th-stock">Kho</th>
                      <th class="th-del"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, ri) in paginatedVariants" :key="row.id" class="vt-row">
                      <td class="td-no"><span class="row-no">{{ (variantCurrentPage - 1) * VARIANTS_PER_PAGE + ri + 1 }}</span></td>
                      <td v-for="t in tableHeaders" :key="t.id"><span class="val-chip" :class="'vc-' + t.color">{{ row.attrs[t.id] || '' }}</span></td>
                      <td><input :value="formatCurrency(row.price)" type="text" class="vt-input" @input="(e) => { row.price = parseCurrency(e.target.value); markManualPrice(row) }" /></td>
                      <td><input :value="row.stock" type="number" min="0" class="vt-input vt-num" @input="(e) => { row.stock = e.target.value; markManualStock(row) }" /></td>
                      <td class="td-del"><button class="btn-row-del" @click="removeVariantRow(ri)" title="Xóa phiên bản này">x</button></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-if="generatedRows.length > VARIANTS_PER_PAGE" class="variant-pagination">
                <button :disabled="variantCurrentPage === 1" @click="goToVariantPage(variantCurrentPage - 1)">&lt;</button>
                <span class="pg-active page-indicator">{{ variantCurrentPage }}/{{ variantTotalPages }}</span>
                <button :disabled="variantCurrentPage === variantTotalPages" @click="goToVariantPage(variantCurrentPage + 1)">&gt;</button>
              </div>
              <div class="p2-foot"><span class="p2-count"><b>{{ generatedRows.length }}</b> bi&#7871;n th&#7875; - trang <b>{{ variantCurrentPage }}</b>/{{ variantTotalPages }}</span></div>
            </template>
          </div>
        </div>

        <div class="form-section-card variants-section-card empty-placeholder" v-else>
          <div class="form-section-title">Bi&#7871;n th&#7875; s&#7843;n ph&#7849;m</div>
          <div class="vs-wrapper" style="text-align: center; padding: 40px; color: #94a3b8; border: none; background: transparent;">
            <p>Vui l&#242;ng ch&#7885;n danh m&#7909;c s&#7843;n ph&#7849;m &#7903; c&#7897;t b&#234;n ph&#7843;i tr&#432;&#7899;c khi c&#7845;u h&#236;nh bi&#7871;n th&#7875;.</p>
          </div>
        </div>

        <p v-if="fieldErrors.variantRows" class="field-error">{{ fieldErrors.variantRows }}</p>
        <p v-if="formError" class="form-error">{{ formError }}</p>
        <div class="inline-form-footer">
          <button class="btn-cancel" @click="closeModal">H&#7911;y</button>
          <button class="btn-submit" @click="submitForm">{{ isEditMode ? 'Lưu thay đổi' : 'Thêm sản phẩm' }}</button>
        </div>
      </div>
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
                      <img :src="p.img" :alt="p.name" loading="lazy" decoding="async"
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

/* â”€â”€ Inline Form Header â”€â”€ */
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

/* â”€â”€ Inline Form Body â”€â”€ */
.inline-form-body {
  background: #f8fafc;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 28px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}

/* â”€â”€ Inline Form Footer â”€â”€ */
.inline-form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   INLINE FORM â€” Form Elements Redesign
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

/* Product Form Grid Layout */
.product-form-grid {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 24px;
  align-items: start;
  margin-bottom: 24px;
}

.form-main-col {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-sidebar-col {
  position: sticky;
  top: 20px;
}

/* Section Card Design */
.form-section-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section-title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 10px;
  margin-bottom: 4px;
}

/* Grid for images uploader */
.images-upload-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

/* 3-column row for basic info fields */
.form-fields-row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 16px;
}

.images-section-card {
  margin-bottom: 24px;
}

.variants-section-card {
  margin-top: 24px;
}

.variants-section-card .vs-wrapper {
  border: none;
  background: transparent;
  padding: 0;
  box-shadow: none;
}

/* Label */
.inline-form-body .form-group label {
  font-size: 11.5px;
  font-weight: 700;
  color: #6b7280;
  letter-spacing: 0.06em;
  text-transform: capitalize;
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
  border-color: #2563eb;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
  border-color: #2563eb;
  background: linear-gradient(135deg, #ebe8ff 0%, #f0f4ff 100%);
}

.inline-form-body .upload-zone svg {
  width: 44px;
  height: 44px;
  color: #3b82f6;
}

.inline-form-body .upload-zone p {
  font-size: 14px;
  color: #475569;
  margin: 0;
  font-weight: 500;
}

.inline-form-body .upload-zone p span {
  color: #2563eb;
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
  justify-content: flex-end;
  align-items: center;
  margin-bottom: 20px;
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
  color: #2563eb;
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
  color: #2563eb;
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
  grid-template-columns: repeat(3, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
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

.stat-card.stat-orange {
  background: linear-gradient(135deg, #c2410c 0%, #f97316 100%);
  color: #fff;
}

.stat-card.stat-teal {
  background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
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

.stat-icon.blue {
  background: rgba(255, 255, 255, 0.18)
}

.stat-icon.green {
  background: rgba(255, 255, 255, 0.18)
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

/* â”€â”€ Custom Premium Dropdown â”€â”€ */
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


tbody td {
  padding: 14px 16px;
  font-size: 13px;
  color: #334155;
  vertical-align: middle;
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
  color: #2563eb;
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

.visually-hidden-file {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
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
  background: linear-gradient(135deg, #2563eb, #2563eb);
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
  background: linear-gradient(180deg, #2563eb, #2563eb);
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
  color: #2563eb;
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
  border-color: #2563eb;
  background: #2563eb;
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
  color: #1d4ed8;
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
  color: #1d4ed8;
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
  color: #1d4ed8;
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
  border-color: #2563eb;
  background: #2563eb;
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
  border-color: #2563eb;
  background: #2563eb;
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
  background: linear-gradient(135deg, #2563eb, #2563eb);
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
  background: linear-gradient(135deg, #2563eb, #2563eb);
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
  width: 108px;
  min-width: 108px;
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

/* â”€â”€ PHASE 1 FOOTER â”€â”€ */
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
  color: #1d4ed8;
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
  width: 100%;
  min-width: 76px;
  text-align: center;
  font-variant-numeric: tabular-nums;
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
  background: linear-gradient(135deg, #2563eb, #2563eb);
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

/* â”€â”€ PARENT CATEGORY TABS â”€â”€ */
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

/* Custom Tree Select Component (Static View) */
.always-visible-tree-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.selected-category-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
  transition: all 0.2s ease;
  width: 100%;
  box-sizing: border-box;
}

.selected-category-badge.has-selected {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1d4ed8;
  font-weight: 600;
}

.selected-category-badge .badge-icon {
  font-size: 15px;
}

.tree-select-static-container {
  width: 100%;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  background-color: #ffffff;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.tree-select-static-container:focus-within {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}

.tree-select-static-container.has-error {
  border-color: #f87171;
  background: #fff5f5;
  box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.08);
}

/* Search Bar */
.tree-search-wrapper {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  border-bottom: 1px solid #f1f5f9;
  background-color: #f8fafc;
  gap: 8px;
}

.tree-search-wrapper .search-icon {
  width: 16px;
  height: 16px;
  color: #94a3b8;
  flex-shrink: 0;
}

.tree-search-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 13px;
  color: #0f172a;
  outline: none;
  padding: 4px 0;
  font-family: inherit;
}

.clear-search-btn {
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 16px;
  cursor: pointer;
  padding: 0 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.15s;
}

.clear-search-btn:hover {
  color: #475569;
}

/* Tree List Container */
.tree-list-container {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
  max-height: 320px;
}

.tree-empty {
  padding: 24px;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
}

/* Tree Parent Node */
.tree-parent-node {
  display: flex;
  flex-direction: column;
}

.tree-parent-row {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.15s;
  user-select: none;
}

.tree-parent-row:hover {
  background-color: #f1f5f9;
}

.tree-toggle-icon {
  font-size: 10px;
  color: #64748b;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 4px;
  flex-shrink: 0;
}

.tree-folder-icon {
  font-size: 16px;
  margin-right: 8px;
  flex-shrink: 0;
}

.tree-parent-name {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}

/* Children Container */
.tree-children-list {
  padding-left: 20px;
  display: flex;
  flex-direction: column;
  position: relative;
}

/* Tree Leaf Nodes (Children) */
.tree-child-node {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  border-radius: 8px;
  cursor: pointer;
  position: relative;
  transition: background-color 0.15s, color 0.15s;
  user-select: none;
  margin-bottom: 2px;
}

.tree-child-node:hover {
  background-color: #f8fafc;
}

.tree-child-node.selected {
  background-color: #eff6ff;
  color: #2563eb;
}

.tree-child-node.selected .tree-child-name {
  font-weight: 600;
  color: #2563eb;
}

.tree-leaf-icon {
  font-size: 14px;
  margin-right: 8px;
  flex-shrink: 0;
  z-index: 2;
}

.tree-child-name {
  font-size: 13px;
  color: #475569;
  z-index: 2;
}

/* WinRAR Connector Lines (Dashed Lines) */
.tree-child-node::before {
  content: '';
  position: absolute;
  left: -12px;
  top: -6px;
  height: calc(100% + 10px);
  width: 0;
  border-left: 1.5px dashed #cbd5e1;
  z-index: 1;
}

.tree-child-node:last-child::before {
  height: 22px; /* stops at the horizontal line */
}

.tree-child-node::after {
  content: '';
  position: absolute;
  left: -12px;
  top: 16px;
  width: 14px;
  height: 0;
  border-top: 1.5px dashed #cbd5e1;
  z-index: 1;
}

/* Accordion Container */
.accordion-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
}

.accordion-item {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease-in-out;
}

.accordion-item.is-open {
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
}

.accordion-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: #f8fafc;
  cursor: pointer;
  user-select: none;
  border-bottom: 1px solid transparent;
  transition: all 0.2s ease;
}

.accordion-item.is-open .accordion-header {
  background: #f1f5f9;
  border-bottom-color: #e2e8f0;
}

.accordion-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.accordion-icon {
  font-size: 18px;
}

.accordion-name {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}

.accordion-badge {
  font-size: 11px;
  font-weight: 600;
  background: #eff6ff;
  color: #2563eb;
  padding: 3px 10px;
  border-radius: 20px;
  border: 1px solid #dbeafe;
}

.accordion-header .chevron {
  width: 18px;
  height: 18px;
  color: #64748b;
  transition: transform 0.2s ease;
}

.accordion-header .chevron.open {
  transform: rotate(180deg);
}

.accordion-body {
  padding: 20px;
  background: #ffffff;
}

/* Switch Control (Spec vs Variant) */
.mode-switch-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 8px;
  background: #f8fafc;
  padding: 6px 12px;
  border-radius: 20px;
  border: 1px solid #f1f5f9;
  width: fit-content;
}

.mode-label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  min-width: 76px;
  transition: color 0.15s;
}

.mode-label.active {
  color: #2563eb;
  font-weight: 700;
}

.switch-control {
  position: relative;
  display: inline-block;
  width: 38px;
  height: 20px;
  flex-shrink: 0;
}

.switch-control input {
  opacity: 0;
  width: 0;
  height: 0;
}

.switch-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  transition: .25s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 20px;
}

.switch-slider:before {
  position: absolute;
  content: "";
  height: 14px;
  width: 14px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .25s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

.switch-control input:checked + .switch-slider {
  background: linear-gradient(135deg, #2563eb, #2563eb);
}

.switch-control input:checked + .switch-slider:before {
  transform: translateX(18px);
}

/* Actions Column (Select All / Clear) */
.fst-actions-col {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100px;
  flex-shrink: 0;
  justify-content: center;
  align-items: flex-end;
}

.quick-act-btn {
  background: none;
  border: none;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.15s ease;
  font-family: inherit;
}

.quick-act-btn.select-all {
  color: #2563eb;
}

.quick-act-btn.select-all:hover {
  background: #eff6ff;
}

.quick-act-btn.clear-all {
  color: #64748b;
}

.quick-act-btn.clear-all:hover:not(:disabled) {
  background: #f1f5f9;
  color: #334155;
}

.quick-act-btn.clear-all:disabled {
  color: #cbd5e1;
  cursor: not-allowed;
}

/* Color Swatches Grid */
.color-swatches-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.color-swatch-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px 6px 8px;
  border: 1.5px solid #e2e8f0;
  background: #ffffff;
  border-radius: 30px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: inherit;
}

.color-swatch-btn:hover {
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

.color-swatch-btn.selected {
  border-color: #2563eb;
  background-color: #eff6ff;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
}

.swatch-circle {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 1px solid rgba(15, 23, 42, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.15s;
}

.color-swatch-btn:hover .swatch-circle {
  transform: scale(1.1);
}

.swatch-check {
  color: #ffffff;
  font-size: 11px;
  font-weight: 900;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.swatch-label {
  font-size: 12.5px;
  font-weight: 500;
  color: #475569;
}

.color-swatch-btn.selected .swatch-label {
  color: #2563eb;
  font-weight: 600;
}

/* Live Combo Preview Panel */
.live-preview-panel {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 14px 16px;
  margin: 10px 0;
}

.preview-title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
  margin-bottom: 8px;
}

.preview-tags-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.preview-tag {
  font-size: 11px;
  font-weight: 600;
  color: #1e293b;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 4px 10px;
  border-radius: 6px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.preview-tag-more {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  padding: 4px 8px;
  align-self: center;
}

/* Collapse Transition */
.collapse-enter-active,
.collapse-leave-active {
  transition: max-height 0.25s ease-in-out, opacity 0.25s ease-in-out;
  max-height: 500px;
  overflow: hidden;
}

.collapse-enter-from,
.collapse-leave-to {
  max-height: 0;
  opacity: 0;
}

</style>
