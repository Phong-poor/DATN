  <script setup>
  import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
  import { getUser } from '@/services/auth'
  import api from '@/services/api'
  import { normalizeImageUrl, productImageUrl, storageUrl } from '@/services/urls'
  import { invalidateProductsPrefetchCache } from '@/services/productsPrefetch'
  import { registerOfflineForm } from '@/services/offlineSync'
  import PhanTrangAdmin from './PhanTrangAdmin.vue'

  const user = ref(getUser() || {})
  const hasPermission = (perm) => {
    if (user.value?.vaitro && user.value.vaitro !== 'user') return true
    return user.value?.cac_quyen?.includes(perm)
  }

  const PRODUCTS_CACHE_KEY = 'nextgen_admin_products_cache'
  const PRODUCTS_CACHE_TTL = 2 * 60 * 1000
  let xlsxModulePromise = null
  let swalModulePromise = null

  const loadXlsx = async () => {
    if (!xlsxModulePromise) xlsxModulePromise = import('xlsx')
    const mod = await xlsxModulePromise
    return mod.default || mod
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
  const pageSize = PER_PAGE

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

  const updatingLowStockVariantId = ref(null)

  const openLowStockVariantsModal = (product) => {
    if (product && Array.isArray(product.bienThes)) {
      product.bienThes.forEach(v => {
        v._addStock = ''
      })
    }
    selectedLowStockProduct.value = product
    showLowStockVariantsModal.value = true
  }

  const closeLowStockVariantsModal = () => {
    showLowStockVariantsModal.value = false
    selectedLowStockProduct.value = null
  }

  const updateLowStockVariant = async (v, product) => {
    const addQty = Number(v._addStock || 0)
    if (isNaN(addQty) || addQty <= 0) {
      swal.warning('Số lượng không hợp lệ', 'Vui lòng nhập số lượng cộng thêm lớn hơn 0')
      return
    }
    const currentQty = Number(v.soluong || 0)
    const finalQty = currentQty + addQty

    try {
      const variantId = v.id_bienthe || v.id
      updatingLowStockVariantId.value = variantId
      const payload = {
        id_sanpham: product.id,
        ten_bienthe: v.ten_bienthe || '',
        gia: v.gia ?? 0,
        soluong: finalQty
      }
      await api.put(`/admin/bienthe/${variantId}`, payload)
      v.soluong = finalQty
      v._addStock = ''
      invalidateProductCaches(product.id)
      swal.success('Thành công', `Đã cộng thêm ${addQty} sản phẩm! Số lượng thực tế là ${finalQty}.`)
      await fetchProducts()
    } catch (error) {
      console.error(error)
      swal.error('Lỗi', getErrorMessage(error, 'Không thể cập nhật số lượng biến thể.'))
    } finally {
      updatingLowStockVariantId.value = null
    }
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

  /* ==========================================
     BÁO CÁO TỒN KHO & XUẤT FILE EXCEL CAO CẤP
     ========================================== */
  const parseSpecsString = (spJson) => {
    const specPairs = []
    try {
      const spSpecs = typeof spJson === 'string' ? JSON.parse(spJson) : spJson
      if (Array.isArray(spSpecs)) {
        spSpecs.forEach(item => {
          const key = item.ten_thuoctinh || item.id_thuoctinh
          const val = item.giatri
          if (key && val) specPairs.push(`${key}: ${val}`)
        })
      } else if (spSpecs && typeof spSpecs === 'object') {
        Object.entries(spSpecs).forEach(([key, val]) => {
          if (key && val) specPairs.push(`${key}: ${val}`)
        })
      }
    } catch (e) { }
    return specPairs.length ? specPairs.join(' | ') : '—'
  }

  const escapeXml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;')

  const xmlCell = (value, {
    style = 'TableCell',
    type = typeof value === 'number' ? 'Number' : 'String',
    mergeAcross = 0,
    mergeDown = 0,
    index = null,
  } = {}) => {
    const attrs = [
      `ss:StyleID="${style}"`,
      mergeAcross ? `ss:MergeAcross="${mergeAcross}"` : '',
      mergeDown ? `ss:MergeDown="${mergeDown}"` : '',
      index ? `ss:Index="${index}"` : '',
    ].filter(Boolean).join(' ')
    return `<Cell ${attrs}><Data ss:Type="${type}">${escapeXml(value)}</Data></Cell>`
  }

  const xmlRow = (cells = [], height = null) => {
    const attrs = height ? ` ss:Height="${height}"` : ''
    return `<Row${attrs}>${cells.join('')}</Row>`
  }

  const xmlColumns = (widths) => widths.map((width) => `<Column ss:Width="${width}"/>`).join('')

  const xmlWorksheet = (name, widths, rows, freezeRow = 8) => `
<Worksheet ss:Name="${escapeXml(name).slice(0, 31)}">
    <Table>${xmlColumns(widths)}${rows.join('')}</Table>
    <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
        <FreezePanes/>
        <FrozenNoSplit/>
        <SplitHorizontal>${freezeRow}</SplitHorizontal>
        <TopRowBottomPane>${freezeRow}</TopRowBottomPane>
        <ActivePane>2</ActivePane>
        <Panes>
            <Pane><Number>3</Number></Pane>
            <Pane><Number>2</Number></Pane>
        </Panes>
        <ProtectObjects>False</ProtectObjects>
        <ProtectScenarios>False</ProtectScenarios>
    </WorksheetOptions>
</Worksheet>`

  const downloadExcelXml = (xml, fileName) => {
    const blob = new Blob(['\ufeff', xml], { type: 'application/vnd.ms-excel;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  }

  const handleExportExcel = async () => {
    if (isExporting.value) return
    isExporting.value = true

    try {
      const res = await api.get('/admin/sanpham/export-inventory')
      const rawData = res.data || []

      if (!rawData.length) {
        swal.warning('Không có dữ liệu', 'Không có dữ liệu tồn kho để xuất báo cáo')
        return
      }

      const now = new Date()
      const dateStr = now.toLocaleDateString('vi-VN')
      const timeStr = now.toLocaleTimeString('vi-VN')
      const yyyymmdd = now.getFullYear().toString() + String(now.getMonth() + 1).padStart(2, '0') + String(now.getDate()).padStart(2, '0')
      const rand = Math.floor(1000 + Math.random() * 9000)
      const reportCode = `BC-TK-${yyyymmdd}-${rand}`

      let totalQuantity = 0
      let totalValue = 0
      let outOfStockCount = 0
      let lowStockCount = 0
      let safeStockCount = 0

      // Grouping accumulators
      const catMap = {}
      const brandMap = {}

      const processedItems = rawData.map((item, index) => {
        const price = Number(item.gia) || 0
        const quantity = Number(item.soluong) || 0
        const totalInventoryValue = price * quantity
        const sku = item.sku || `SKU-${item.id_bienthe}`
        const category = item.ten_danhmuc || 'Chưa phân loại'
        const brand = item.tenthuonghieu || 'Chưa có'
        const specs = parseSpecsString(item.thong_so_ky_thuat)
        const variantSpecs = parseSpecsString(item.thuoc_tinh_json)
        const variantName = (variantSpecs && variantSpecs !== '—') ? variantSpecs : (item.ten_bienthe || 'Cấu hình tiêu chuẩn')

        let stockStatusText = 'An toàn'
        let statusStyle = 'StatusSafe'
        let warningLevel = 'Bình thường'
        let suggestStock = 'Đủ hàng bán'

        if (quantity === 0) {
          stockStatusText = 'Hết hàng'
          statusStyle = 'StatusOut'
          warningLevel = 'Khẩn cấp (Hết)'
          suggestStock = 'Nhập ngay ≥ 10 máy'
          outOfStockCount++
        } else if (quantity <= 5) {
          stockStatusText = 'Sắp hết'
          statusStyle = 'StatusLow'
          warningLevel = 'Cảnh báo (≤ 5)'
          suggestStock = `Nhập thêm +${15 - quantity} máy`
          lowStockCount++
        } else {
          safeStockCount++
        }

        totalQuantity += quantity
        totalValue += totalInventoryValue

        // Category summary
        if (!catMap[category]) catMap[category] = { count: 0, qty: 0, val: 0 }
        catMap[category].count++
        catMap[category].qty += quantity
        catMap[category].val += totalInventoryValue

        // Brand summary
        if (!brandMap[brand]) brandMap[brand] = { count: 0, qty: 0, val: 0 }
        brandMap[brand].count++
        brandMap[brand].qty += quantity
        brandMap[brand].val += totalInventoryValue

        return {
          stt: index + 1,
          id_sanpham: item.id_sanpham,
          sku,
          tenSP: item.tenSP || '—',
          category,
          brand,
          variantName,
          specs,
          price,
          quantity,
          totalInventoryValue,
          stockStatusText,
          statusStyle,
          warningLevel,
          suggestStock
        }
      })

      // Count variants per product & track product order for vertical cell merging (Merge & Center)
      let productCounter = 0
      const prodVariantCounts = {}
      const prodGroupIndices = {}

      processedItems.forEach(item => {
        const key = item.id_sanpham || item.tenSP
        if (!prodVariantCounts[key]) {
          productCounter++
          prodGroupIndices[key] = productCounter
          prodVariantCounts[key] = 0
        }
        prodVariantCounts[key]++
      })
      const mergedTracker = {}

      /* ─── SHEET 1: TỔNG QUAN TỒN KHO ─── */
      const detailHeaders = [
        'STT',
        'Mã SKU',
        'Tên sản phẩm',
        'Danh mục',
        'Thương hiệu',
        'Thông số kỹ thuật',
        'Cấu hình / Biến thể',
        'Giá niêm yết (VNĐ)',
        'Số lượng tồn',
        'Giá trị tồn kho (VNĐ)',
        'Trạng thái kho'
      ]

      const mainSheetRows = [
        xmlRow([xmlCell('BÁO CÁO CHI TIẾT TỒN KHO HỆ THỐNG NEXTGEN LAPTOP', { style: 'Title', mergeAcross: 10 })], 26),
        xmlRow([
          xmlCell('Mã báo cáo', { style: 'MetaKey' }),
          xmlCell(reportCode, { style: 'MetaValue', mergeAcross: 1 }),
          xmlCell('Thời điểm xuất', { style: 'MetaKey' }),
          xmlCell(`${timeStr} - ${dateStr}`, { style: 'MetaValue', mergeAcross: 2 }),
          xmlCell('Người xuất', { style: 'MetaKey' }),
          xmlCell('Quản trị viên', { style: 'MetaValue', mergeAcross: 1 }),
          xmlCell('Phạm vi', { style: 'MetaKey' }),
          xmlCell('Toàn bộ kho hàng', { style: 'MetaValue' }),
        ], 20),
        xmlRow([
          xmlCell(`Tổng biến thể: ${processedItems.length} mã`, { style: 'KpiCompactBlue', mergeAcross: 1 }),
          xmlCell(`Tổng tồn kho: ${totalQuantity.toLocaleString('vi-VN')} máy`, { style: 'KpiCompactBlue', mergeAcross: 2 }),
          xmlCell(`Tổng giá trị tồn: ${totalValue.toLocaleString('vi-VN')} đ`, { style: 'KpiCompactBlue', mergeAcross: 2 }),
          xmlCell(`Kho an toàn: ${safeStockCount} mã`, { style: 'KpiCompactGreen', mergeAcross: 1 }),
          xmlCell(`Sắp hết: ${lowStockCount} | Hết hàng: ${outOfStockCount}`, { style: 'KpiCompactOrange', mergeAcross: 1 }),
        ], 22),
        xmlRow(detailHeaders.map(h => xmlCell(h, { style: 'TableHeader' })), 25),
        // Data rows
        ...processedItems.map((item, idx) => {
          const isAlt = idx % 2 === 1
          const textStyle = isAlt ? 'TableCellAlt' : 'TableCell'
          const centerStyle = isAlt ? 'TableCellCenterAlt' : 'TableCellCenter'
          const rightStyle = isAlt ? 'TableCellRightAlt' : 'TableCellRight'
          const boldStyle = isAlt ? 'TableCellBoldAlt' : 'TableCellBold'

          const prodKey = item.id_sanpham || item.tenSP
          const vCount = prodVariantCounts[prodKey] || 1
          const pIndex = prodGroupIndices[prodKey] || (idx + 1)
          const isFirstInGroup = !mergedTracker[prodKey]

          const rowCells = []

          if (isFirstInGroup) {
            mergedTracker[prodKey] = true
            const mDown = vCount > 1 ? vCount - 1 : 0
            rowCells.push(xmlCell(pIndex, { style: centerStyle, mergeDown: mDown }))
            rowCells.push(xmlCell(item.sku, { style: isAlt ? 'TableCellSkuAlt' : 'TableCellSku', mergeDown: mDown }))
            rowCells.push(xmlCell(item.tenSP, { style: boldStyle, mergeDown: mDown }))
            rowCells.push(xmlCell(item.category, { style: centerStyle, mergeDown: mDown }))
            rowCells.push(xmlCell(item.brand, { style: centerStyle, mergeDown: mDown }))
            rowCells.push(xmlCell(item.specs, { style: textStyle, mergeDown: mDown }))
          }

          rowCells.push(
            xmlCell(item.variantName, { style: textStyle, index: !isFirstInGroup ? 7 : null }),
            xmlCell(`${item.price.toLocaleString('vi-VN')} đ`, { style: rightStyle }),
            xmlCell(item.quantity, { style: centerStyle }),
            xmlCell(`${item.totalInventoryValue.toLocaleString('vi-VN')} đ`, { style: rightStyle }),
            xmlCell(item.stockStatusText, { style: item.statusStyle })
          )

          return xmlRow(rowCells, 22)
        }),
        // Summary row
        xmlRow([
          xmlCell('TỔNG CỘNG', { style: 'SummaryRowCenter', mergeAcross: 7 }),
          xmlCell(`${totalQuantity.toLocaleString('vi-VN')} máy`, { style: 'SummaryRowCenter' }),
          xmlCell(`${totalValue.toLocaleString('vi-VN')} đ`, { style: 'SummaryRowRight' }),
          xmlCell(`${outOfStockCount} hết | ${lowStockCount} sắp hết`, { style: 'SummaryRowCenter' }),
        ], 24),
        xmlRow([], 10),
        // Notes & Signatures
        xmlRow([xmlCell('• Ghi chú: Dữ liệu tồn kho thời gian thực từ cơ sở dữ liệu NextGen Laptop. Giá trị tồn kho = Giá niêm yết × Số lượng thực tế.', { style: 'Note', mergeAcross: 10 })], 20),
        xmlRow([], 12),
        xmlRow([
          xmlCell('Người lập báo cáo', { style: 'Sign', mergeAcross: 2 }),
          xmlCell('Thủ kho phụ trách', { style: 'Sign', mergeAcross: 3 }),
          xmlCell('Ban Giám đốc / Quản trị duyệt', { style: 'Sign', mergeAcross: 3 }),
        ], 22),
        xmlRow([], 36),
        xmlRow([
          xmlCell('Ký, ghi rõ họ tên', { style: 'SignSub', mergeAcross: 2 }),
          xmlCell('Ký, ghi rõ họ tên', { style: 'SignSub', mergeAcross: 3 }),
          xmlCell('Ký, đóng dấu họ tên', { style: 'SignSub', mergeAcross: 3 }),
        ], 18),
      ]

      /* ─── SHEET 2: CẢNH BÁO SẮP HẾT & HẾT HÀNG ─── */
      const alertItems = processedItems.filter(i => i.quantity <= 5)
      const alertHeaders = ['STT', 'Mã SKU', 'Tên sản phẩm', 'Cấu hình / Biến thể', 'Danh mục', 'Thương hiệu', 'Tồn kho', 'Giá bán (VNĐ)', 'Mức độ cảnh báo', 'Kế hoạch đề xuất']
      const alertSheetRows = [
        xmlRow([xmlCell('DANH SÁCH SẢN PHẨM CẦN NHẬP KHO GẤP (TỒN KHO ≤ 5 MÁY)', { style: 'TitleAlert', mergeAcross: 9 })], 26),
        xmlRow([
          xmlCell(`Thời điểm xuất: ${timeStr} - ${dateStr} | Tổng số sản phẩm cần nhập: ${alertItems.length} mã (${outOfStockCount} hết hàng, ${lowStockCount} sắp hết)`, { style: 'SheetMeta', mergeAcross: 9 })
        ], 20),
        xmlRow(alertHeaders.map(h => xmlCell(h, { style: 'TableHeaderAlert' })), 25),
        ...(alertItems.length ? alertItems.map((item, idx) => {
          const isAlt = idx % 2 === 1
          const centerStyle = isAlt ? 'TableCellCenterAlt' : 'TableCellCenter'
          const rightStyle = isAlt ? 'TableCellRightAlt' : 'TableCellRight'
          return xmlRow([
            xmlCell(idx + 1, { style: centerStyle }),
            xmlCell(item.sku, { style: isAlt ? 'TableCellSkuAlt' : 'TableCellSku' }),
            xmlCell(item.tenSP, { style: isAlt ? 'TableCellBoldAlt' : 'TableCellBold' }),
            xmlCell(item.variantName, { style: isAlt ? 'TableCellAlt' : 'TableCell' }),
            xmlCell(item.category, { style: centerStyle }),
            xmlCell(item.brand, { style: centerStyle }),
            xmlCell(`${item.quantity} máy`, { style: item.statusStyle }),
            xmlCell(`${item.price.toLocaleString('vi-VN')} đ`, { style: rightStyle }),
            xmlCell(item.warningLevel, { style: item.statusStyle }),
            xmlCell(item.suggestStock, { style: isAlt ? 'TableCellAlt' : 'TableCell' }),
          ], 22)
        }) : [xmlRow([xmlCell('Hiện tại tất cả các mã sản phẩm đều ở mức tồn kho an toàn (kho > 5 máy).', { style: 'TableCellCenter', mergeAcross: 9 })], 24)]),
      ]

      /* ─── SHEET 3: THEO DANH MỤC ─── */
      const catList = Object.entries(catMap).map(([name, stat], idx) => ({
        stt: idx + 1,
        name,
        count: stat.count,
        qty: stat.qty,
        val: stat.val,
        pct: totalValue > 0 ? ((stat.val / totalValue) * 100).toFixed(1) : '0.0'
      })).sort((a, b) => b.val - a.val)

      const catHeaders = ['STT', 'Danh mục sản phẩm', 'Số lượng biến thể', 'Tổng máy tồn kho', 'Tổng giá trị tồn kho (VNĐ)', 'Tỷ lệ giá trị (%)']
      const catSheetRows = [
        xmlRow([xmlCell('TỔNG HỢP TỒN KHO THEO DANH MỤC SẢN PHẨM', { style: 'Title', mergeAcross: 5 })], 26),
        xmlRow([xmlCell(`Thời điểm xuất: ${timeStr} - ${dateStr} | Tổng số danh mục: ${catList.length}`, { style: 'SheetMeta', mergeAcross: 5 })], 20),
        xmlRow(catHeaders.map(h => xmlCell(h, { style: 'TableHeader' })), 25),
        ...catList.map((c, idx) => {
          const isAlt = idx % 2 === 1
          return xmlRow([
            xmlCell(c.stt, { style: isAlt ? 'TableCellCenterAlt' : 'TableCellCenter' }),
            xmlCell(c.name, { style: isAlt ? 'TableCellBoldAlt' : 'TableCellBold' }),
            xmlCell(`${c.count} mã`, { style: isAlt ? 'TableCellCenterAlt' : 'TableCellCenter' }),
            xmlCell(`${c.qty.toLocaleString('vi-VN')} máy`, { style: isAlt ? 'TableCellCenterAlt' : 'TableCellCenter' }),
            xmlCell(`${c.val.toLocaleString('vi-VN')} đ`, { style: isAlt ? 'TableCellRightAlt' : 'TableCellRight' }),
            xmlCell(`${c.pct}%`, { style: isAlt ? 'TableCellCenterAlt' : 'TableCellCenter' }),
          ], 22)
        }),
        xmlRow([
          xmlCell('TỔNG CỘNG', { style: 'SummaryRowCenter', mergeAcross: 1 }),
          xmlCell(`${processedItems.length} mã`, { style: 'SummaryRowCenter' }),
          xmlCell(`${totalQuantity.toLocaleString('vi-VN')} máy`, { style: 'SummaryRowCenter' }),
          xmlCell(`${totalValue.toLocaleString('vi-VN')} đ`, { style: 'SummaryRowRight' }),
          xmlCell('100.0%', { style: 'SummaryRowCenter' }),
        ], 24)
      ]

      /* ─── SHEET 4: THEO THƯƠNG HIỆU ─── */
      const brandList = Object.entries(brandMap).map(([name, stat], idx) => ({
        stt: idx + 1,
        name,
        count: stat.count,
        qty: stat.qty,
        val: stat.val,
        pct: totalValue > 0 ? ((stat.val / totalValue) * 100).toFixed(1) : '0.0'
      })).sort((a, b) => b.val - a.val)

      const brandHeaders = ['STT', 'Thương hiệu sản phẩm', 'Số lượng biến thể', 'Tổng máy tồn kho', 'Tổng giá trị tồn kho (VNĐ)', 'Tỷ lệ giá trị (%)']
      const brandSheetRows = [
        xmlRow([xmlCell('TỔNG HỢP TỒN KHO THEO THƯƠNG HIỆU SẢN PHẨM', { style: 'Title', mergeAcross: 5 })], 26),
        xmlRow([xmlCell(`Thời điểm xuất: ${timeStr} - ${dateStr} | Tổng số thương hiệu: ${brandList.length}`, { style: 'SheetMeta', mergeAcross: 5 })], 20),
        xmlRow(brandHeaders.map(h => xmlCell(h, { style: 'TableHeader' })), 25),
        ...brandList.map((b, idx) => {
          const isAlt = idx % 2 === 1
          return xmlRow([
            xmlCell(b.stt, { style: isAlt ? 'TableCellCenterAlt' : 'TableCellCenter' }),
            xmlCell(b.name, { style: isAlt ? 'TableCellBoldAlt' : 'TableCellBold' }),
            xmlCell(`${b.count} mã`, { style: isAlt ? 'TableCellCenterAlt' : 'TableCellCenter' }),
            xmlCell(`${b.qty.toLocaleString('vi-VN')} máy`, { style: isAlt ? 'TableCellCenterAlt' : 'TableCellCenter' }),
            xmlCell(`${b.val.toLocaleString('vi-VN')} đ`, { style: isAlt ? 'TableCellRightAlt' : 'TableCellRight' }),
            xmlCell(`${b.pct}%`, { style: isAlt ? 'TableCellCenterAlt' : 'TableCellCenter' }),
          ], 22)
        }),
        xmlRow([
          xmlCell('TỔNG CỘNG', { style: 'SummaryRowCenter', mergeAcross: 1 }),
          xmlCell(`${processedItems.length} mã`, { style: 'SummaryRowCenter' }),
          xmlCell(`${totalQuantity.toLocaleString('vi-VN')} máy`, { style: 'SummaryRowCenter' }),
          xmlCell(`${totalValue.toLocaleString('vi-VN')} đ`, { style: 'SummaryRowRight' }),
          xmlCell('100.0%', { style: 'SummaryRowCenter' }),
        ], 24)
      ]

      /* ─── WORKBOOK ASSEMBLY WITH STYLES ─── */
      const worksheets = [
        xmlWorksheet('Ton kho tong the', [46, 96, 210, 110, 100, 180, 260, 120, 90, 140, 110], mainSheetRows, 4),
        xmlWorksheet('Canh bao nhap hang', [46, 96, 210, 180, 110, 100, 90, 120, 120, 140], alertSheetRows, 3),
        xmlWorksheet('Theo danh muc', [46, 180, 120, 120, 170, 110], catSheetRows, 3),
        xmlWorksheet('Theo thuong hieu', [46, 180, 120, 120, 170, 110], brandSheetRows, 3),
      ]

      const xml = `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">
    <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
        <Title>Báo cáo tồn kho NextGen Laptop</Title>
        <Subject>Kiểm kê tồn kho và định giá tài sản</Subject>
        <Author>NextGen Laptop</Author>
        <Company>NextGen Laptop</Company>
        <Created>${now.toISOString()}</Created>
    </DocumentProperties>
    <Styles>
        <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Segoe UI" ss:Size="9.5" ss:Color="#0F172A"/></Style>
        <Style ss:ID="Title"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="12" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#1E3A8A" ss:Pattern="Solid"/></Style>
        <Style ss:ID="TitleAlert"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="12" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#991B1B" ss:Pattern="Solid"/></Style>
        <Style ss:ID="SheetMeta"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#475569"/><Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>
        <Style ss:ID="MetaKey"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#475569"/><Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>
        <Style ss:ID="MetaValue"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#0F172A"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>
        <Style ss:ID="KpiCompactBlue"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#1E40AF"/><Interior ss:Color="#EFF6FF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/></Borders></Style>
        <Style ss:ID="KpiCompactGreen"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#166534"/><Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#86EFAC"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#86EFAC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#86EFAC"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#86EFAC"/></Borders></Style>
        <Style ss:ID="KpiCompactOrange"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#991B1B"/><Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCA5A5"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCA5A5"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCA5A5"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCA5A5"/></Borders></Style>
        <Style ss:ID="TableHeader"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Segoe UI" ss:Size="9.5" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#2563EB" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1D4ED8"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#93C5FD"/></Borders></Style>
        <Style ss:ID="TableHeaderAlert"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Segoe UI" ss:Size="9.5" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#DC2626" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#B91C1C"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCA5A5"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCA5A5"/></Borders></Style>
        <Style ss:ID="TableCell"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="TableCellAlt"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="TableCellCenter"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="TableCellCenterAlt"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="TableCellRight"><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="TableCellRightAlt"><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="TableCellBold"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#0F172A"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="TableCellBoldAlt"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#0F172A"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="TableCellSku"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#2563EB"/><Interior ss:Color="#EFF6FF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/></Borders></Style>
        <Style ss:ID="TableCellSkuAlt"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#2563EB"/><Interior ss:Color="#EFF6FF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BFDBFE"/></Borders></Style>
        <Style ss:ID="StatusSafe"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#15803D"/><Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#86EFAC"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#86EFAC"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#86EFAC"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#86EFAC"/></Borders></Style>
        <Style ss:ID="StatusLow"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#B45309"/><Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCD34D"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCD34D"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCD34D"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCD34D"/></Borders></Style>
        <Style ss:ID="StatusOut"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#B91C1C"/><Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCA5A5"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCA5A5"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCA5A5"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FCA5A5"/></Borders></Style>
        <Style ss:ID="SummaryRowCenter"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9.5" ss:Bold="1" ss:Color="#1E3A8A"/><Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/><Borders><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#93C5FD"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#93C5FD"/></Borders></Style>
        <Style ss:ID="SummaryRowRight"><Alignment ss:Horizontal="Right" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9.5" ss:Bold="1" ss:Color="#1E3A8A"/><Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/><Borders><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#93C5FD"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#93C5FD"/></Borders></Style>
        <Style ss:ID="Note"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Italic="1" ss:Color="#475569"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
        <Style ss:ID="Sign"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="9.5" ss:Bold="1" ss:Color="#0F172A"/><Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/></Style>
        <Style ss:ID="SignSub"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="8.5" ss:Italic="1" ss:Color="#64748B"/></Style>
    </Styles>
    ${worksheets.join('')}
</Workbook>`

      const fileDateStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
      downloadExcelXml(xml, `Bao_cao_ton_kho_NextGen_${fileDateStr}.xls`)
      swal.success('Xuất file thành công', 'Báo cáo chi tiết tồn kho đã được tải xuống với giao diện chuẩn chuyên nghiệp.')
    } catch (error) {
      console.error(error)
      swal.error('Lỗi', 'Không thể xuất báo cáo tồn kho từ máy chủ')
    } finally {
      isExporting.value = false
    }
  }

  // Excel Bulk Import & Image Suggestion State
  const showImportModal = ref(false)
  const importParsedItems = ref([])
  const isSubmittingImport = ref(false)
  const excelDbCategories = ref([])
  const excelDbBrands = ref([])
  const excelDbAttributes = ref([])

  const validImportItemsCount = computed(() => {
    return importParsedItems.value ? importParsedItems.value.filter(i => i.isValid).length : 0
  })

  const checkRowAttributesAndSpecs = (thongSoStr, variantStr, dbAttrs) => {
    const newSpecAttrs = []
    const newVariantAttrs = []

    // Kiểm tra thông số kỹ thuật
    if (thongSoStr && dbAttrs && dbAttrs.length > 0) {
      const specParts = thongSoStr.split(/[|;\n\r]+/).map(s => s.trim()).filter(Boolean)
      specParts.forEach(part => {
        let specKey = ''
        if (part.includes(':')) {
          specKey = part.split(':')[0].trim()
        } else {
          specKey = part
        }
        if (specKey) {
          const exists = dbAttrs.some(a => (a.ten_thuoctinh || a.name || '').toString().trim().toLowerCase() === specKey.toLowerCase())
          if (!exists && !newSpecAttrs.includes(specKey)) {
            newSpecAttrs.push(specKey)
          }
        }
      })
    }

    // Kiểm tra loại thuộc tính biến thể
    if (variantStr && dbAttrs && dbAttrs.length > 0) {
      const varParts = variantStr.split(/[\/\-]+/).map(s => s.trim()).filter(Boolean)
      varParts.forEach(part => {
        let attrType = ''
        if (/\d+\s*(GB|TB|MB)/i.test(part)) {
          attrType = /TB|128|256|512|1024/i.test(part) ? 'Dung lượng' : 'RAM'
        } else if (/Intel|AMD|Apple|Ryzen|Core|i3|i5|i7|i9|M1|M2|M3|M4|Snapdragon/i.test(part)) {
          attrType = 'CPU'
        } else if (/Màu|Đen|Trắng|Vàng|Xám|Bạc|Platinum|Graphite|Gold|Silver|Stealth|Eclipse|Onyx|Blue|Red/i.test(part)) {
          attrType = 'Màu sắc'
        }

        if (attrType) {
          const exists = dbAttrs.some(a => (a.ten_thuoctinh || a.name || '').toString().trim().toLowerCase() === attrType.toLowerCase())
          if (!exists && !newVariantAttrs.includes(attrType)) {
            newVariantAttrs.push(attrType)
          }
        }
      })
    }

    return {
      hasNewSpecAttr: newSpecAttrs.length > 0,
      newSpecAttrs,
      hasNewVariantAttr: newVariantAttrs.length > 0,
      newVariantAttrs
    }
  }

  const parseVariantStringToStructuredArray = (variantStr, dbAttrs) => {
    if (!variantStr) return []
    const parts = variantStr.split(/[\/\-]+/).map(s => s.trim()).filter(Boolean)
    return parts.map(part => {
      let attrName = 'Thuộc tính'
      let val = part
      if (/\d+\s*(GB|TB|MB)/i.test(part)) {
        attrName = /TB|128|256|512|1024/i.test(part) ? 'Dung lượng' : 'RAM'
      } else if (/Intel|AMD|Apple|Ryzen|Core|i3|i5|i7|i9|M1|M2|M3|M4|Snapdragon/i.test(part)) {
        attrName = 'CPU'
      } else if (/Màu|Đen|Trắng|Vàng|Xám|Bạc|Platinum|Graphite|Gold|Silver|Stealth|Eclipse|Onyx|Blue|Red/i.test(part)) {
        attrName = 'Màu sắc'
        val = part.replace(/^Màu\s+/i, '')
      }

      const matchedAttr = dbAttrs ? dbAttrs.find(a => (a.ten_thuoctinh || a.name || '').toString().trim().toLowerCase() === attrName.toLowerCase()) : null

      return {
        attr_id: matchedAttr ? (matchedAttr.id_thuoctinh || matchedAttr.id) : '',
        attr_name: matchedAttr ? matchedAttr.ten_thuoctinh : attrName,
        value: val
      }
    })
  }

  const parseSpecStringToStructuredArray = (specStr, dbAttrs) => {
    if (!specStr) return []
    const parts = specStr.split(/[|;\n\r]+/).map(s => s.trim()).filter(Boolean)
    return parts.map(part => {
      let key = 'Thông số'
      let val = part
      if (part.includes(':')) {
        const sp = part.split(':')
        key = sp[0].trim()
        val = sp.slice(1).join(':').trim()
      }
      const matchedAttr = dbAttrs ? dbAttrs.find(a => (a.ten_thuoctinh || a.name || '').toString().trim().toLowerCase() === key.toLowerCase()) : null

      return {
        attr_id: matchedAttr ? (matchedAttr.id_thuoctinh || matchedAttr.id) : '',
        attr_name: matchedAttr ? matchedAttr.ten_thuoctinh : key,
        value: val
      }
    })
  }

  const updateVariantItemString = (item) => {
    if (item.parsedVariantAttrs) {
      item.ten_bienthe = item.parsedVariantAttrs
        .filter(a => a.value)
        .map(a => a.value)
        .join(' - ')
    }
    revalidateItemCategoryBrand(item)
  }

  const updateSpecItemString = (item) => {
    if (item.parsedSpecAttrs) {
      item.thong_so_ky_thuat = item.parsedSpecAttrs
        .filter(a => a.attr_name && a.value)
        .map(a => `${a.attr_name}: ${a.value}`)
        .join(' | ')
    }
    revalidateItemCategoryBrand(item)
  }

  const addVariantAttrToItem = (item) => {
    if (!item.parsedVariantAttrs) item.parsedVariantAttrs = []
    const defaultAttr = excelDbAttributes.value.length > 0 ? excelDbAttributes.value[0].ten_thuoctinh : 'Thuộc tính'
    item.parsedVariantAttrs.push({
      attr_id: '',
      attr_name: defaultAttr,
      value: ''
    })
    updateVariantItemString(item)
  }

  const removeVariantAttrFromItem = (item, idx) => {
    if (item.parsedVariantAttrs) {
      item.parsedVariantAttrs.splice(idx, 1)
      updateVariantItemString(item)
    }
  }

  const addSpecAttrToItem = (item) => {
    if (!item.parsedSpecAttrs) item.parsedSpecAttrs = []
    const defaultAttr = excelDbAttributes.value.length > 0 ? excelDbAttributes.value[0].ten_thuoctinh : 'GPU'
    item.parsedSpecAttrs.push({
      attr_id: '',
      attr_name: defaultAttr,
      value: ''
    })
    updateSpecItemString(item)
  }

  const removeSpecAttrFromItem = (item, idx) => {
    if (item.parsedSpecAttrs) {
      item.parsedSpecAttrs.splice(idx, 1)
      updateSpecItemString(item)
    }
  }

  // State & Handler cho Modal chỉnh sửa thuộc tính & thông số kỹ thuật riêng biệt
  const showAttrEditModal = ref(false)
  const activeEditItem = ref(null)
  const activeAttrTab = ref('specs') // 'specs' hoặc 'variant'
  const syncToSameProductGroup = ref(true)

  const openEditSpecsModal = (item, tab = 'specs') => {
    activeEditItem.value = item
    activeAttrTab.value = tab
    showAttrEditModal.value = true
  }

  const saveAttrEditModal = () => {
    if (activeEditItem.value) {
      updateSpecItemString(activeEditItem.value)
      updateVariantItemString(activeEditItem.value)

      // Đồng bộ thông số kỹ thuật sang tất cả biến thể cùng nhóm sản phẩm nếu chọn đồng bộ
      if (syncToSameProductGroup.value && importParsedItems.value) {
        const pId = activeEditItem.value.product_id
        const pName = (activeEditItem.value.tenSP || '').toLowerCase()
        importParsedItems.value.forEach(row => {
          if (row !== activeEditItem.value && (row.product_id === pId || (row.tenSP && row.tenSP.toLowerCase() === pName))) {
            row.thong_so_ky_thuat = activeEditItem.value.thong_so_ky_thuat
            row.parsedSpecAttrs = JSON.parse(JSON.stringify(activeEditItem.value.parsedSpecAttrs || []))
            revalidateItemCategoryBrand(row)
          }
        })
      }
    }
    showAttrEditModal.value = false
  }

  const triggerImportItemsUpdate = () => {
    if (importParsedItems.value) {
      importParsedItems.value = [...importParsedItems.value]
    }
  }

  const revalidateExcelRow = (item) => {
    const hasName = Boolean((item.tenSP || '').toString().trim())
    const hasCategory = Boolean((item.ten_danhmuc || '').toString().trim())
    const hasPrice = Number(item.gia) > 0
    const hasStock = Number(item.soluong) >= 0
    const hasImage = Boolean((item.anhdaidien || '').toString().trim())

    let isValid = true
    let errorMessage = ''

    if (!hasName) {
      isValid = false
      errorMessage = 'Thiếu Tên sản phẩm'
    } else if (!hasCategory) {
      isValid = false
      errorMessage = 'Thiếu Danh mục'
    } else if (!hasPrice) {
      isValid = false
      errorMessage = 'Giá bán phải > 0'
    } else if (!hasStock) {
      isValid = false
      errorMessage = 'Kho không hợp lệ'
    } else if (!hasImage) {
      isValid = false
      errorMessage = 'Thiếu hình ảnh'
    }

    item.isValid = isValid
    item.errorMessage = errorMessage
    return isValid
  }

  const revalidateItemCategoryBrand = (item) => {
    const catNameLower = (item.ten_danhmuc || '').toString().trim().toLowerCase()
    const brandNameLower = (item.tenthuonghieu || '').toString().trim().toLowerCase()

    const matchedCat = catNameLower ? excelDbCategories.value.find(c => {
      const name = (c?.ten_danhmuc || c?.name || c?.label || '').toString().trim().toLowerCase()
      return name === catNameLower
    }) : null

    const matchedBrand = brandNameLower ? excelDbBrands.value.find(b => {
      const name = (b?.ten_thuonghieu || b?.name || b?.label || '').toString().trim().toLowerCase()
      return name === brandNameLower
    }) : null

    if (matchedCat) {
      item.ten_danhmuc = matchedCat.ten_danhmuc || matchedCat.name || matchedCat.label || item.ten_danhmuc
    }
    if (matchedBrand) {
      item.tenthuonghieu = matchedBrand.ten_thuonghieu || matchedBrand.name || matchedBrand.label || item.tenthuonghieu
    }

    item.isNewCategory = Boolean(item.ten_danhmuc) && !matchedCat
    item.isNewBrand = Boolean(item.tenthuonghieu) && !matchedBrand

    revalidateExcelRow(item)

    // Đồng bộ tức thì tới tất cả biến thể cùng nhóm sản phẩm
    if (importParsedItems.value && importParsedItems.value.length > 0) {
      importParsedItems.value.forEach(row => {
        if (row !== item && (row.product_id === item.product_id || (row.tenSP && item.tenSP && row.tenSP.toLowerCase() === item.tenSP.toLowerCase()))) {
          row.ten_danhmuc = item.ten_danhmuc
          row.tenthuonghieu = item.tenthuonghieu
          row.isNewCategory = item.isNewCategory
          row.isNewBrand = item.isNewBrand
          revalidateExcelRow(row)
        }
      })
    }
  }

  // Image Suggestion Modal State
  const suggestImageModal = ref(false)
  const isSuggestingImages = ref(false)
  const currentSuggestItem = ref(null)
  const suggestedImagesList = ref([])
  const selectedMainImage = ref('')
  const selectedGalleryImages = ref([])

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
        const jsonData = XLSX.utils.sheet_to_json(firstSheet, { defval: '' })

        if (!jsonData || !jsonData.length) {
          swal.warning('Không hợp lệ', 'File Excel không có dữ liệu')
          return
        }

        // Tải danh sách Danh mục, Thương hiệu và Thuộc tính mới nhất trực tiếp từ Database
        let dbCategories = []
        let dbBrands = []
        let dbAttributes = []
        try {
          const [catRes, brandRes, attrRes] = await Promise.all([
            api.get('/danhmuc').catch(() => ({ data: [] })),
            api.get('/thuonghieu').catch(() => ({ data: [] })),
            api.get('/thuoctinh').catch(() => ({ data: [] }))
          ])
          dbCategories = Array.isArray(catRes.data?.data) ? catRes.data.data : (Array.isArray(catRes.data) ? catRes.data : [])
          dbBrands = Array.isArray(brandRes.data?.data) ? brandRes.data.data : (Array.isArray(brandRes.data) ? brandRes.data : [])
          dbAttributes = Array.isArray(attrRes.data?.data) ? attrRes.data.data : (Array.isArray(attrRes.data) ? attrRes.data : [])
          excelDbCategories.value = dbCategories
          excelDbBrands.value = dbBrands
          excelDbAttributes.value = dbAttributes
        } catch (err) {
          console.warn('Could not load categories/brands/attributes for import validation:', err)
        }

        let lastProductGroup = {
          productId: null,
          tenSP: '',
          tenDanhmuc: '',
          tenthuonghieu: '',
          thongSo: '',
          khoiluong: 1.5,
          anhdaidien: '',
          hinhAnhsStr: ''
        }

        const parsed = jsonData.map((row, index) => {
          const stt = row['STT'] || (index + 1)
          let rawProductId = row['ID Sản phẩm'] || row['ID'] || row['Mã sản phẩm']
          
          let tenSP = (row['Tên sản phẩm'] || row['Tên SP'] || '').toString().trim()
          let tenDanhmuc = (row['Danh mục'] || '').toString().trim()
          let tenthuonghieu = (row['Thương hiệu'] || '').toString().trim()
          let thongSo = (row['Thông số kỹ thuật'] || row['Thông số'] || '').toString().trim()
          let khoiluong = row['Khối lượng (kg)'] || row['Khối lượng'] || ''

          let anhdaidien = (row['URL Ảnh đại diện'] || row['Ảnh đại diện'] || row['Hình ảnh'] || row['URL Ảnh'] || row['URL Hình ảnh'] || row['Ảnh'] || row['Image'] || row['Main Image'] || '').toString().trim()
          let hinhAnhsStr = (row['URL Ảnh phụ'] || row['Ảnh phụ'] || row['Album ảnh'] || row['Album'] || row['Gallery'] || row['Sub Images'] || row['Extra Images'] || '').toString().trim()

          // Auto-inherit from previous product group ONLY if Tên sản phẩm is blank OR if Tên sản phẩm is identical
          const isSameGroupId = rawProductId && (String(rawProductId) === String(lastProductGroup.productId))
          const isSameName = tenSP && lastProductGroup.tenSP && (tenSP.toLowerCase() === lastProductGroup.tenSP.toLowerCase())
          
          if (!tenSP && (isSameGroupId || lastProductGroup.tenSP)) {
            tenSP = lastProductGroup.tenSP
          }

          // If the user specified a NEW distinct product name, start a new product group instead of inheriting old data
          const isInheritAllowed = !tenSP || isSameName || (isSameGroupId && !row['Tên sản phẩm'] && !row['Tên SP'])

          if (isInheritAllowed) {
            if (!tenDanhmuc && lastProductGroup.tenDanhmuc) tenDanhmuc = lastProductGroup.tenDanhmuc
            if (!tenthuonghieu && lastProductGroup.tenthuonghieu) tenthuonghieu = lastProductGroup.tenthuonghieu
            if (!thongSo && lastProductGroup.thongSo) thongSo = lastProductGroup.thongSo
            if (!khoiluong && lastProductGroup.khoiluong) khoiluong = lastProductGroup.khoiluong
            if (!anhdaidien && lastProductGroup.anhdaidien) anhdaidien = lastProductGroup.anhdaidien
            if (!hinhAnhsStr && lastProductGroup.hinhAnhsStr) hinhAnhsStr = lastProductGroup.hinhAnhsStr
          }
          khoiluong = Number(khoiluong) || 1.5

          const productId = rawProductId || lastProductGroup.productId || stt

          // Update last product group tracker with current row's distinct info
          if (tenSP) {
            lastProductGroup = {
              productId,
              tenSP,
              tenDanhmuc,
              tenthuonghieu,
              thongSo,
              khoiluong,
              anhdaidien,
              hinhAnhsStr
            }
          }

          const sku = (row['Mã SKU'] || row['SKU'] || '').toString().trim()
          const tenBienthe = (row['Cấu hình / Biến thể'] || row['Biến thể'] || 'Cấu hình tiêu chuẩn').toString().trim()
          const gia = Number(row['Giá bán (VNĐ)'] || row['Giá bán'] || row['Giá'] || 0)
          const soluong = Number(row['Số lượng kho'] || row['Số lượng'] || row['Kho'] || 0)

          const catNameLower = (tenDanhmuc || '').toString().trim().toLowerCase()
          const brandNameLower = (tenthuonghieu || '').toString().trim().toLowerCase()

          // Tìm danh mục trùng khớp trong DB (Case-insensitive)
          const matchedCategoryObj = catNameLower ? dbCategories.find(c => {
            const name = (c?.ten_danhmuc || c?.name || c?.label || '').toString().trim().toLowerCase()
            return name === catNameLower
          }) : null

          // Tìm thương hiệu trùng khớp trong DB (Case-insensitive)
          const matchedBrandObj = brandNameLower ? dbBrands.find(b => {
            const name = (b?.ten_thuonghieu || b?.name || b?.label || '').toString().trim().toLowerCase()
            return name === brandNameLower
          }) : null

          // Chuẩn hóa tên danh mục và thương hiệu trùng khớp với DB
          if (matchedCategoryObj) {
            tenDanhmuc = matchedCategoryObj.ten_danhmuc || matchedCategoryObj.name || matchedCategoryObj.label || tenDanhmuc
          }
          if (matchedBrandObj) {
            tenthuonghieu = matchedBrandObj.ten_thuonghieu || matchedBrandObj.name || matchedBrandObj.label || tenthuonghieu
          }

          const existsCategory = Boolean(matchedCategoryObj) || !tenDanhmuc
          const existsBrand = Boolean(matchedBrandObj) || !tenthuonghieu

          const attrCheck = checkRowAttributesAndSpecs(thongSo, tenBienthe, dbAttributes)
          const parsedVariantAttrs = parseVariantStringToStructuredArray(tenBienthe, dbAttributes)
          const parsedSpecAttrs = parseSpecStringToStructuredArray(thongSo, dbAttributes)

          const rowItem = {
            stt,
            product_id: productId,
            sku,
            tenSP,
            ten_danhmuc: tenDanhmuc,
            tenthuonghieu,
            isNewCategory: tenDanhmuc ? !existsCategory : false,
            isNewBrand: tenthuonghieu ? !existsBrand : false,
            hasNewSpecAttr: attrCheck.hasNewSpecAttr,
            newSpecAttrs: attrCheck.newSpecAttrs,
            hasNewVariantAttr: attrCheck.hasNewVariantAttr,
            newVariantAttrs: attrCheck.newVariantAttrs,
            parsedVariantAttrs,
            parsedSpecAttrs,
            khoiluong,
            ten_bienthe: tenBienthe,
            gia,
            soluong,
            anhdaidien,
            hinh_anhs_str: hinhAnhsStr,
            thong_so_ky_thuat: thongSo,
            isValid: true,
            errorMessage: ''
          }

          revalidateExcelRow(rowItem)
          return rowItem
        })

        importParsedItems.value = parsed
        showImportModal.value = true

      } catch (error) {
        console.error(error)
        swal.error('Lỗi', 'Lỗi khi đọc file Excel. Vui lòng kiểm tra định dạng file .xlsx!')
      } finally {
        isImporting.value = false
        e.target.value = ''
      }
    }

    reader.readAsArrayBuffer(file)
  }

  // Auto Image Search & Selection Modal
  const suggestImagePage = ref(1)

  const openSuggestImages = async (item, page = 1) => {
    currentSuggestItem.value = item
    suggestImageModal.value = true
    isSuggestingImages.value = true
    suggestImagePage.value = page

    const pName = item.tenSP || item.ten_san_pham || item.ten_sanpham || ''
    const pBrand = item.tenthuonghieu || item.ten_thuonghieu || (typeof item.thuong_hieu === 'object' ? item.thuong_hieu?.ten_thuonghieu : item.thuong_hieu) || item.brand || ''
    const pCategory = item.ten_danhmuc || item.tenDanhmuc || (typeof item.danh_muc === 'object' ? item.danh_muc?.ten_danhmuc : item.danh_muc) || item.category || ''

    if (page === 1) {
      suggestedImagesList.value = []
      selectedMainImage.value = item.anhdaidien || item.hinhanh || ''
      selectedGalleryImages.value = item.hinh_anhs_str ? item.hinh_anhs_str.split(',') : []
    }

    try {
      const res = await api.get('/admin/sanpham/suggest-images', {
        params: {
          keyword: pName,
          brand: pBrand,
          category: pCategory,
          page
        }
      })
      const newImgs = res.data?.images || []
      suggestedImagesList.value = newImgs

      if (!selectedMainImage.value && suggestedImagesList.value.length > 0) {
        selectedMainImage.value = suggestedImagesList.value[0]
      }
    } catch (err) {
      console.error(err)
    } finally {
      isSuggestingImages.value = false
    }
  }

  /* ─────────────────────────────────────────────────────────────
     LOCAL FOLDER IMAGE UPLOAD FOR EXCEL IMPORT
  ───────────────────────────────────────────────────────────── */
  const localExcelFileInput = ref(null)
  const targetUploadItem = ref(null)
  const isUploadingExcelImages = ref(false)

  const openLocalImagePicker = (item) => {
    targetUploadItem.value = item || (importParsedItems.value.length > 0 ? importParsedItems.value[0] : null)
    if (localExcelFileInput.value) {
      localExcelFileInput.value.value = ''
      localExcelFileInput.value.click()
    }
  }

  const onLocalExcelFilesSelected = async (e) => {
    const files = e.target.files
    if (!files || files.length === 0) return

    isUploadingExcelImages.value = true
    const formData = new FormData()
    for (let i = 0; i < files.length; i++) {
      formData.append('images[]', files[i])
    }

    try {
      const res = await api.post('/admin/sanpham/upload-excel-images', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      const urls = res.data?.urls || []
      if (urls.length > 0) {
        const main = urls[0]
        const gallery = urls.slice(1).join(',')

        if (targetUploadItem.value) {
          const targetPid = targetUploadItem.value.product_id
          const targetName = (targetUploadItem.value.tenSP || '').toString().trim().toLowerCase()

          // Synchronize uploaded images across all variant rows of the same product
          importParsedItems.value.forEach(item => {
            const isMatchId = targetPid && item.product_id && (String(item.product_id) === String(targetPid))
            const isMatchName = targetName && (item.tenSP || '').toString().trim().toLowerCase() === targetName

            if (isMatchId || isMatchName) {
              item.anhdaidien = main
              if (gallery) {
                item.hinh_anhs_str = item.hinh_anhs_str ? item.hinh_anhs_str + ',' + gallery : gallery
              }
              revalidateExcelRow(item)
            }
          })
          swal.success('Thành công', `Đã tải lên và gán ${urls.length} ảnh từ máy tính cho sản phẩm!`)
        } else {
          const missingItem = importParsedItems.value.find(i => !i.anhdaidien) || importParsedItems.value[0]
          if (missingItem) {
            missingItem.anhdaidien = main
            if (gallery) missingItem.hinh_anhs_str = gallery
            revalidateExcelRow(missingItem)
          }
          swal.success('Thành công', `Đã tải lên ${urls.length} ảnh từ máy tính!`)
        }
      }
    } catch (err) {
      console.error(err)
      swal.error('Lỗi tải ảnh', 'Không thể tải ảnh từ máy tính lên hệ thống.')
    } finally {
      isUploadingExcelImages.value = false
      targetUploadItem.value = null
    }
  }

  /* ─────────────────────────────────────────────────────────────
     IMAGE DETAIL MODAL & VISUALIZATION
  ───────────────────────────────────────────────────────────── */
  const showImageDetailModal = ref(false)
  const activeDetailItem = ref(null)

  const openViewImageDetailModal = (item) => {
    activeDetailItem.value = item
    showImageDetailModal.value = true
  }

  const setItemMainImage = (url) => {
    if (!activeDetailItem.value) return
    const targetPid = activeDetailItem.value.product_id
    const targetName = (activeDetailItem.value.tenSP || '').toString().trim().toLowerCase()

    const oldMain = activeDetailItem.value.anhdaidien
    let galleryArr = activeDetailItem.value.hinh_anhs_str ? activeDetailItem.value.hinh_anhs_str.split(',').filter(u => u && u !== url) : []
    if (oldMain && oldMain !== url && !galleryArr.includes(oldMain)) {
      galleryArr.push(oldMain)
    }

    importParsedItems.value.forEach(item => {
      const isMatchId = targetPid && item.product_id && (String(item.product_id) === String(targetPid))
      const isMatchName = targetName && (item.tenSP || '').toString().trim().toLowerCase() === targetName
      if (isMatchId || isMatchName) {
        item.anhdaidien = url
        item.hinh_anhs_str = galleryArr.join(',')
        revalidateExcelRow(item)
      }
    })
  }

  const removeDetailImage = (url) => {
    if (!activeDetailItem.value) return
    const targetPid = activeDetailItem.value.product_id
    const targetName = (activeDetailItem.value.tenSP || '').toString().trim().toLowerCase()

    importParsedItems.value.forEach(item => {
      const isMatchId = targetPid && item.product_id && (String(item.product_id) === String(targetPid))
      const isMatchName = targetName && (item.tenSP || '').toString().trim().toLowerCase() === targetName

      if (isMatchId || isMatchName) {
        if (item.anhdaidien === url) {
          const galleryArr = item.hinh_anhs_str ? item.hinh_anhs_str.split(',').filter(Boolean) : []
          item.anhdaidien = galleryArr.length > 0 ? galleryArr[0] : ''
          item.hinh_anhs_str = galleryArr.slice(1).join(',')
        } else if (item.hinh_anhs_str) {
          const galleryArr = item.hinh_anhs_str.split(',').filter(u => u && u !== url)
          item.hinh_anhs_str = galleryArr.join(',')
        }
        revalidateExcelRow(item)
      }
    })
  }

  const reloadNextImagePage = () => {
    if (currentSuggestItem.value) {
      openSuggestImages(currentSuggestItem.value, suggestImagePage.value + 1)
    }
  }

  const handleImageError = (event) => {
    if (event && event.target && event.target.closest) {
      const card = event.target.closest('.suggest-card-box')
      if (card) card.style.display = 'none'
    }
  }

  const selectMainImage = (url) => {
    if (selectedMainImage.value === url) {
      selectedMainImage.value = ''
    } else {
      selectedMainImage.value = url
      selectedGalleryImages.value = selectedGalleryImages.value.filter(u => u !== url)
    }
  }

  const toggleGalleryImage = (url) => {
    if (selectedMainImage.value === url) {
      selectedMainImage.value = ''
      selectedGalleryImages.value.push(url)
    } else if (selectedGalleryImages.value.includes(url)) {
      selectedGalleryImages.value = selectedGalleryImages.value.filter(u => u !== url)
    } else {
      selectedGalleryImages.value.push(url)
    }
  }

  const confirmImageSelection = () => {
    if (currentSuggestItem.value) {
      const targetPid = currentSuggestItem.value.product_id
      const targetName = (currentSuggestItem.value.tenSP || '').toString().trim().toLowerCase()

      // Synchronize chosen images across all variant rows of the same product
      importParsedItems.value.forEach(item => {
        const isMatchId = targetPid && item.product_id && (String(item.product_id) === String(targetPid))
        const isMatchName = targetName && (item.tenSP || '').toString().trim().toLowerCase() === targetName
        
        if (isMatchId || isMatchName) {
          item.anhdaidien = selectedMainImage.value
          item.hinh_anhs_str = selectedGalleryImages.value.join(',')
          revalidateExcelRow(item)
        }
      })
    }
    suggestImageModal.value = false
  }

  const autoFetchAllMissingImages = async () => {
    isSuggestingImages.value = true
    const fetchedCache = {}
    try {
      for (const item of importParsedItems.value) {
        const pKey = (item.product_id ? 'ID_' + item.product_id : 'NAME_' + (item.tenSP || '')).toString().trim().toLowerCase()

        if (!item.anhdaidien || !item.hinh_anhs_str) {
          if (fetchedCache[pKey]) {
            if (!item.anhdaidien) item.anhdaidien = fetchedCache[pKey].main
            if (!item.hinh_anhs_str) item.hinh_anhs_str = fetchedCache[pKey].gallery
          } else if (item.tenSP || item.ten_san_pham) {
            const pName = item.tenSP || item.ten_san_pham || item.ten_sanpham || ''
            const pBrand = item.tenthuonghieu || item.ten_thuonghieu || (typeof item.thuong_hieu === 'object' ? item.thuong_hieu?.ten_thuonghieu : item.thuong_hieu) || item.brand || ''
            const pCategory = item.ten_danhmuc || item.tenDanhmuc || (typeof item.danh_muc === 'object' ? item.danh_muc?.ten_danhmuc : item.danh_muc) || item.category || ''

            const res = await api.get('/admin/sanpham/suggest-images', {
              params: {
                keyword: pName,
                brand: pBrand,
                category: pCategory
              }
            })
            const imgs = res.data?.images || []
            if (imgs.length > 0) {
              const main = imgs[0]
              const gallery = imgs.slice(1, 6).join(',')
              fetchedCache[pKey] = { main, gallery }
              if (!item.anhdaidien) item.anhdaidien = main
              if (!item.hinh_anhs_str) item.hinh_anhs_str = gallery
            }
          }
        }
      }

      // Final pass: ensure all items with same key share both main and gallery images
      importParsedItems.value.forEach(item => {
        const pKey = (item.product_id ? 'ID_' + item.product_id : 'NAME_' + (item.tenSP || '')).toString().trim().toLowerCase()
        if (fetchedCache[pKey]) {
          if (!item.anhdaidien) item.anhdaidien = fetchedCache[pKey].main
          if (!item.hinh_anhs_str) item.hinh_anhs_str = fetchedCache[pKey].gallery
        }
        revalidateExcelRow(item)
      })

      swal.success('Thành công', 'Đã tự động tìm và gán trọn bộ Ảnh chính & Album ảnh phụ cho tất cả dòng sản phẩm!')
    } catch (err) {
      console.error(err)
      swal.error('Lỗi', 'Không thể tự động lấy ảnh gợi ý.')
    } finally {
      isSuggestingImages.value = false
    }
  }

  const confirmBulkImport = async () => {
    const validItems = importParsedItems.value.filter(i => i.isValid)
    if (validItems.length === 0) {
      swal.warning('Không có dữ liệu hợp lệ', 'Không có sản phẩm hợp lệ nào để lưu.')
      return
    }

    const isConfirmed = await swal.confirm(
      'Xác nhận nhập Excel',
      `Bạn có chắc muốn nhập ${validItems.length} dòng biến thể sản phẩm hợp lệ vào CSDL?`
    )

    if (!isConfirmed) return

    isSubmittingImport.value = true
    try {
      const res = await api.post('/admin/sanpham/import-bulk', { items: validItems })
      swal.success('Nhập Excel thành công!', res.data?.message || 'Đã tạo mới và cập nhật sản phẩm vào hệ thống.')
      showImportModal.value = false
      await fetchProducts()
    } catch (err) {
      console.error(err)
      swal.error('Lỗi', err.response?.data?.message || 'Không thể lưu dữ liệu từ Excel.')
    } finally {
      isSubmittingImport.value = false
    }
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
    const columnHeaders = ['STT', ...headers.map(h => h.label), 'Giá (VNĐ)', 'Kho']
    const rows = generatedRows.value.map((row, index) => [
      index + 1,
      ...headers.map(h => row.attrs[h.id] || ''),
      Number(row.price) || 0,
      Number(row.stock) || 0,
    ])
    const lastColumn = XLSX.utils.encode_col(columnHeaders.length - 1)
    const ws = XLSX.utils.aoa_to_sheet([
      [`BÁO CÁO BIẾN THỂ - ${(form.value.name || 'SẢN PHẨM').toUpperCase()}`],
      [`Thời điểm xuất: ${new Date().toLocaleString('vi-VN')} | Tổng cấu hình: ${rows.length}`],
      [],
      columnHeaders,
      ...rows,
    ])
    ws['!merges'] = [
      { s: { r: 0, c: 0 }, e: { r: 0, c: columnHeaders.length - 1 } },
      { s: { r: 1, c: 0 }, e: { r: 1, c: columnHeaders.length - 1 } },
    ]
    ws['!cols'] = [{ wch: 7 }, ...headers.map(() => ({ wch: 20 })), { wch: 18 }, { wch: 12 }]
    ws['!autofilter'] = { ref: `A4:${lastColumn}${rows.length + 4}` }
    rows.forEach((_, index) => {
      const priceCell = ws[`${XLSX.utils.encode_col(columnHeaders.length - 2)}${index + 5}`]
      if (priceCell) priceCell.z = '#,##0'
    })
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Biến thể')
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
                label: c.ten || c.name,
                value: c.ten || c.name,
                hex: c.mamau || c.hex || c.hex_code,
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
      formError.value = getErrorMessage(error, 'Không tải được dữ liệu thuộc tính.')
    } finally {
      variantLoading.value = false
    }
  }

  /* ─────────────────────────────────────────────────────────────
     MODAL THÊM GIÁ TRỊ THUỘC TÍNH (TẠM THỜI & TẠO MỚI 2 TAB)
  ───────────────────────────────────────────────────────────── */
  const showAddAttrValueModal = ref(false)
  const activeAttrModalTab = ref('other') // 'other' | 'new'
  const selectedAttrTarget = ref(null) // Object t
  const searchOtherCategoryVal = ref('')
  const selectedOtherCategoryVals = ref(new Set())
  const isSubmittingAttrVal = ref(false)

  const newAttrValForm = ref({
    giatri: '',
    hex: '#2563eb',
  })

  // Tìm tất cả giá trị thuộc tính khả dụng từ danh mục khác trên toàn hệ thống
  const otherCategoryOptions = computed(() => {
    if (!selectedAttrTarget.value) return []
    const targetId = String(selectedAttrTarget.value.id)
    const currentOptionsSet = new Set((selectedAttrTarget.value.options || []).map(getOptionValue))

    const result = []

    if (targetId === 'color-type') {
      colors.value.forEach(c => {
        const val = c.ten || c.name
        if (val && !currentOptionsSet.has(val)) {
          result.push({
            value: val,
            label: val,
            hex: c.mamau || c.hex || c.hex_code
          })
        }
      })
    } else {
      baseAttributeGroups.value.forEach(g => {
        g.attrTypes.forEach(attr => {
          if (String(attr.id) === targetId || String(attr.label).trim().toLowerCase() === String(selectedAttrTarget.value.label).trim().toLowerCase()) {
            attr.options.forEach(opt => {
              const val = getOptionValue(opt)
              if (val && !currentOptionsSet.has(val) && !result.some(r => r.value === val)) {
                result.push({
                  value: val,
                  label: getOptionLabel(opt),
                  hex: getOptionHex(opt)
                })
              }
            })
          }
        })
      })
    }

    if (!searchOtherCategoryVal.value.trim()) return result

    const kw = searchOtherCategoryVal.value.trim().toLowerCase()
    return result.filter(r => String(r.label).toLowerCase().includes(kw))
  })

  const openAddAttrValueModal = (t) => {
    selectedAttrTarget.value = t
    activeAttrModalTab.value = 'other'
    searchOtherCategoryVal.value = ''
    selectedOtherCategoryVals.value = new Set()
    newAttrValForm.value = { giatri: '', hex: '#2563eb' }
    showAddAttrValueModal.value = true
  }

  const closeAddAttrValueModal = () => {
    showAddAttrValueModal.value = false
    selectedAttrTarget.value = null
  }

  const toggleSelectOtherCategoryVal = (val) => {
    const next = new Set(selectedOtherCategoryVals.value)
    if (next.has(val)) {
      next.delete(val)
    } else {
      next.add(val)
    }
    selectedOtherCategoryVals.value = next
  }

  // Tab 1: Áp dụng tạm thời giá trị từ danh mục khác
  const applyOtherCategoryVals = () => {
    if (!selectedAttrTarget.value || selectedOtherCategoryVals.value.size === 0) {
      swal.warning('Thông báo', 'Vui lòng chọn ít nhất 1 giá trị thuộc tính tạm thời.')
      return
    }

    const t = selectedAttrTarget.value
    const tIdStr = String(t.id)

    selectedOtherCategoryVals.value.forEach(val => {
      // Bổ sung vào t.options nếu chưa có
      const exists = (t.options || []).some(opt => getOptionValue(opt) === val)
      if (!exists) {
        const foundObj = otherCategoryOptions.value.find(r => r.value === val)
        const optObj = foundObj ? { label: val, value: val, hex: foundObj.hex } : { label: val, value: val }
        t.options.push(optObj)
      }

      // Tự động tích chọn giá trị này cho sản phẩm hiện tại
      if (!selectedOptions.value[tIdStr]) {
        selectedOptions.value[tIdStr] = new Set()
      }
      selectedOptions.value[tIdStr].add(val)
    })

    selectedOptions.value = { ...selectedOptions.value }
    swal.toast(`Đã thêm ${selectedOtherCategoryVals.value.size} giá trị tạm thời vào thuộc tính ${t.label}!`, 'success')
    closeAddAttrValueModal()
  }

  // Tab 2: Tạo mới giá trị thuộc tính gán cho danh mục hiện tại
  const createNewAttrValue = async () => {
    const val = newAttrValForm.value.giatri.trim()
    if (!val) {
      swal.warning('Thông báo', 'Vui lòng nhập tên giá trị thuộc tính mới.')
      return
    }

    const t = selectedAttrTarget.value
    const tIdStr = String(t.id)
    isSubmittingAttrVal.value = true

    try {
      if (tIdStr === 'color-type') {
        const hex = newAttrValForm.value.hex || '#2563eb'
        colors.value.push({ ten: val, mamau: hex })
        const optObj = { label: val, value: val, hex: hex }
        t.options.push(optObj)

        if (!selectedOptions.value['color-type']) {
          selectedOptions.value['color-type'] = new Set()
        }
        selectedOptions.value['color-type'].add(val)
      } else {
        const catId = Number(form.value.category) || null
        await api.post('/thuoctinh/add-giatri', {
          id_thuoctinh: Number(tIdStr),
          giatri: val,
          danh_muc_ids: catId ? [catId] : []
        })

        const optObj = { label: val, value: val, danh_muc_ids: catId ? [catId] : [] }
        t.options.push(optObj)

        if (!selectedOptions.value[tIdStr]) {
          selectedOptions.value[tIdStr] = new Set()
        }
        selectedOptions.value[tIdStr].add(val)
      }

      selectedOptions.value = { ...selectedOptions.value }
      swal.toast(`Đã tạo thành công giá trị "${val}" cho thuộc tính ${t.label}!`, 'success')
      closeAddAttrValueModal()
    } catch (err) {
      console.error(err)
      swal.error('Lỗi', getErrorMessage(err, 'Không thể tạo giá trị thuộc tính mới.'))
    } finally {
      isSubmittingAttrVal.value = false
    }
  }

  /* ─────────────────────────────────────────────────────────────
     MODAL & FORM
  ───────────────────────────────────────────────────────────── */
  const showModal = ref(false)
  const currentView = ref('list') // 'list' | 'product-form'
  const activeFormTab = ref('basic') // 'basic' | 'attributes' | 'description'
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
    description: '',
  })

  const form = ref(defaultForm())
  registerOfflineForm(form, 'quan-ly-san-pham')
  const fieldErrors = ref({})

  const suggestedSku = computed(() => {
    const brandObj = brands.value.find(b => String(b.id_thuonghieu) === String(form.value.brand))
    const brandPrefix = brandObj ? brandObj.ten_thuonghieu.substring(0, 3).toUpperCase().replace(/[^A-Z0-9]/g, '') : 'SP'
    const nameParts = (form.value.name || '').trim().split(/\s+/).map(p => p.substring(0, 3).toUpperCase().replace(/[^A-Z0-9]/g, '')).filter(Boolean).slice(0, 3).join('-')
    return nameParts ? `${brandPrefix}-${nameParts}` : `${brandPrefix}-SKU`
  })

  const tabHasErrors = (tabName) => {
    if (tabName === 'basic') {
      return Boolean(fieldErrors.value.name || fieldErrors.value.category || fieldErrors.value.brand || fieldErrors.value.img || fieldErrors.value.images || fieldErrors.value.weight)
    }
    if (tabName === 'attributes') {
      return Boolean(fieldErrors.value.variants || fieldErrors.value.variantRows)
    }
    return false
  }

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
      }
    } else {
      form.value.parentCategory = ''
    }
    filterBrandsLocally()
    buildAttributeGroups()

    // Mặc định khởi tạo Màu sắc làm biến thể nếu chưa có thuộc tính biến thể nào và KHÔNG trong quá trình khôi phục bản nháp
    if (!isEditMode.value && !isRestoringDraft.value) {
      if (!variationTierIds.value || variationTierIds.value.size === 0) {
        variationTierIds.value = new Set(['color-type'])
      }
    }
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
      errors.img = 'Vui lòng chọn ảnh đại diện sản phẩm'
    }

    const imagesArr = Array.isArray(form.value.images) ? form.value.images : []
    if (imagesArr.length > MAX_EXTRA_IMAGES) {
      errors.images = `Chỉ được chọn tối đa ${MAX_EXTRA_IMAGES} ảnh phụ`
    }

    const nameVal = form.value.name ? String(form.value.name).trim() : ''
    if (!nameVal) {
      errors.name = 'Tên sản phẩm không được để trống'
    } else if (nameVal.length < 3) {
      errors.name = 'Tên sản phẩm phải có ít nhất 3 ký tự'
    } else if (nameVal.length > 255) {
      errors.name = 'Tên sản phẩm không được vượt quá 255 ký tự'
    }

    if (!form.value.brand) {
      errors.brand = 'Vui lòng chọn thương hiệu'
    }

    if (!form.value.category) {
      errors.category = 'Vui lòng chọn danh mục sản phẩm'
    }

    if (!['Đang bán', 'Nháp'].includes(form.value.status)) {
      errors.status = 'Trạng thái không hợp lệ'
    }

    if (form.value.weight !== '' && form.value.weight !== null && form.value.weight !== undefined) {
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

  /* ─────────────────────────────────────────────────────────────
     VARIANT STATE & CENTRAL MATRIX
  ───────────────────────────────────────────────────────────── */
  const vsPhase = ref(1)
  const selectedGroupId = ref(null)
  const selectedOptions = ref({})
  const variationTierIds = ref(new Set(['color-type'])) // Mặc định Màu sắc là biến thể, tối đa 3
  const isRestoringDraft = ref(false)
  const generatedRows = ref([])
  const editVariantHeaders = ref([])
  const VARIANTS_PER_PAGE = 15
  const variantCurrentPage = ref(1)
  const selectedOptionsSnapshot = ref({})
  const basePrice = ref('')
  const baseStock = ref('')

  const activeVariants = computed(() => {
    return generatedRows.value.filter(r => r.enabled !== false)
  })

  const activeVariantsCount = computed(() => activeVariants.value.length)

  const minPrice = computed(() => {
    const prices = activeVariants.value.map(r => Number(r.price)).filter(p => !isNaN(p) && p > 0)
    return prices.length ? Math.min(...prices) : 0
  })

  const maxPrice = computed(() => {
    const prices = activeVariants.value.map(r => Number(r.price)).filter(p => !isNaN(p) && p > 0)
    return prices.length ? Math.max(...prices) : 0
  })

  const totalVariantStock = computed(() => {
    return activeVariants.value.reduce((sum, r) => sum + (Number(r.stock) || 0), 0)
  })

  const isLaptopProduct = computed(() => {
    const name = String(form.value?.name || '').toLowerCase()
    const parentCat = String(parentCategories.value?.find(p => String(p.id_danhmuc_cha) === String(form.value?.parentCategory))?.ten_danhmuc || '').toLowerCase()
    const cat = String(categories.value?.find(c => String(c.id_danhmuc) === String(form.value?.category))?.ten_danhmuc || '').toLowerCase()
    return name.includes('laptop') || name.includes('macbook') || parentCat.includes('laptop') || cat.includes('laptop') || cat.includes('xách tay')
  })

  const setAllVariantsEnabled = (val) => {
    generatedRows.value.forEach(r => {
      r.enabled = val
    })
  }

  const toggleVariationTier = (typeId) => {
    const tIdStr = String(typeId)
    const tIdNum = Number(typeId)
    if (isVariationTier(typeId)) {
      variationTierIds.value.delete(tIdStr)
      if (!isNaN(tIdNum)) variationTierIds.value.delete(tIdNum)
      // Thu gọn về tối đa 1 giá trị khi chuyển thành Thông số kỹ thuật
      if (selectedOptions.value[tIdStr] && selectedOptions.value[tIdStr].size > 1) {
        const firstVal = Array.from(selectedOptions.value[tIdStr])[0]
        selectedOptions.value[tIdStr] = new Set([firstVal])
        selectedOptions.value = { ...selectedOptions.value }
      }
    } else {
      if (variationTierIds.value.size >= 3) {
        swal.warning('Giới hạn biến thể', 'Chỉ được chọn tối đa 3 cấp biến thể bán (ví dụ: Màu sắc + RAM + CPU). Các thuộc tính khác sẽ được lưu vào Thông số kỹ thuật chung.')
        return
      }
      variationTierIds.value.add(tIdStr)
    }
    variationTierIds.value = new Set(variationTierIds.value)
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
      swal.warning('Thiếu thông tin', 'Vui lòng chọn ít nhất 1 loại thuộc tính làm Biến thể bán trước khi thêm.')
      return
    }

    const attrs = {}
    headers.forEach(h => {
      attrs[h.id] = Array.from(selectedOptions.value[h.id] || [])[0] || null
    })

    generatedRows.value.push({
      id: `manual-${Date.now()}`,
      attrs,
      price: basePrice.value || '',
      stock: baseStock.value || 0,
      ten_bienthe: headers.map(h => attrs[h.id]).filter(Boolean).join(' - '),
      isExisting: false,
      enabled: true,
      _manualPrice: true,
      _manualStock: true
    })

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
    return allSelectedAttrTypes.value.filter(t => isVariationTier(t.id))
  })

  const specificationAttrs = computed(() => {
    return allSelectedAttrTypes.value.filter(t => !isVariationTier(t.id))
  })

  const technicalSpecsList = computed(() => {
    return specificationAttrs.value.map(attr => ({
      label: attr.label,
      value: Array.from(selectedOptions.value[attr.id] || []).join(', ')
    })).filter(s => s.value)
  })

  const tableHeaders = computed(() => {
    if (isEditMode.value && editVariantHeaders.value.length) return editVariantHeaders.value
    return variationHeaders.value
  })

  const comboCount = computed(() => {
    if (!variationHeaders.value.length) return 0

    return variationHeaders.value.reduce(
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

  const exportSelectedOptionsForDraft = (source) => {
    const result = {}
    Object.entries(source || {}).forEach(([key, set]) => {
      if (set instanceof Set) {
        result[key] = Array.from(set)
      } else if (Array.isArray(set)) {
        result[key] = set
      }
    })
    return result
  }

  const importSelectedOptionsFromDraft = (source) => {
    const result = {}
    Object.entries(source || {}).forEach(([key, arr]) => {
      if (Array.isArray(arr)) {
        result[key] = new Set(arr)
      }
    })
    return result
  }

  const isVariationTier = (typeId) => {
    if (!variationTierIds.value) return false
    return variationTierIds.value.has(String(typeId))
  }

  const validateAttributeSelection = () => {
    let isValid = true
    const groupErrors = {}

    const totalSelectedCount = Object.values(selectedOptions.value || {}).reduce(
      (sum, set) => sum + (set?.size || 0),
      0
    )

    if (totalSelectedCount === 0) {
      swal.warning('Chưa chọn thuộc tính', 'Vui lòng chọn ít nhất 1 giá trị thuộc tính (RAM, CPU, GPU, SSD, Màu sắc...) trước khi tiếp tục.')
      return false
    }

    allAttrTypes.value.forEach(t => {
      const tIdStr = String(t.id)
      if (isVariationTier(t.id)) {
        const count = selectedOptions.value[tIdStr]?.size || 0
        if (count === 0) {
          groupErrors[tIdStr] = `Vui lòng chọn ít nhất 1 giá trị cho thuộc tính biến thể "${t.label}"`
          isValid = false
        }
      }
    })

    fieldErrors.value = {
      ...fieldErrors.value,
      variantGroups: groupErrors,
      variants: isValid ? '' : 'Vui lòng chọn đủ giá trị cho các thuộc tính biến thể đã bật'
    }

    if (!isValid) {
      const firstErrorMsg = Object.values(groupErrors)[0]
      if (firstErrorMsg) {
        swal.warning('Thuộc tính chưa hợp lệ', firstErrorMsg)
      }
    }

    return isValid
  }

  const generateVariants = () => {
    if (!validateAttributeSelection()) return

    const headers = [...tableHeaders.value]

    // Nếu có 0 thuộc tính biến thể (Sản phẩm đơn thể / Không phân loại): Sinh 1 dòng Cấu hình tiêu chuẩn
    if (!headers.length) {
      const oldDefaultRow = generatedRows.value.find(r => !Object.keys(r.attrs || {}).length)

      generatedRows.value = [{
        id: oldDefaultRow?.id ?? `${Date.now()}-default`,
        attrs: {},
        price: oldDefaultRow?.price ?? '',
        stock: oldDefaultRow?.stock ?? '',
        ten_bienthe: 'Cấu hình tiêu chuẩn',
        isExisting: oldDefaultRow?.isExisting ?? false,
        enabled: true,
        _manualPrice: oldDefaultRow?._manualPrice ?? false,
        _manualStock: oldDefaultRow?._manualStock ?? false,
      }]

      selectedOptionsSnapshot.value = cloneSelectedOptions(selectedOptions.value)
      variantCurrentPage.value = 1
      vsPhase.value = 2
      return
    }

    // Nếu có 1 đến 3 thuộc tính biến thể: Sinh ma trận tổ hợp
    const arrays = headers.map(t => [...(selectedOptions.value[t.id] || selectedOptions.value[String(t.id)] || [])])
    if (arrays.some(a => a.length === 0)) {
      swal.warning('Chưa chọn giá trị', 'Vui lòng chọn ít nhất 1 giá trị cho tất cả thuộc tính biến thể đã bật.')
      return
    }

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
        ten_bienthe: oldRow?.ten_bienthe ?? headers.map(h => attrs[h.id]).filter(Boolean).join(' - '),
        isExisting: oldRow?.isExisting ?? false,
        enabled: oldRow?.enabled !== undefined ? oldRow.enabled : true,
        _manualPrice: oldRow?._manualPrice ?? false,
        _manualStock: oldRow?._manualStock ?? false,
      }
    })

    selectedOptionsSnapshot.value = cloneSelectedOptions(selectedOptions.value)
    variantCurrentPage.value = 1
    vsPhase.value = 2
  }

  const isStep1Completed = computed(() => {
    return !!(form.value.name?.trim() && (selectedCategory.value || form.value.category) && form.value.brand)
  })

  const isStep2Completed = computed(() => {
    return generatedRows.value.length > 0 && generatedRows.value.some(r => r.enabled !== false && Number(r.price) > 0)
  })

  const isStep3Completed = computed(() => {
    return !!(form.value.description && form.value.description.trim())
  })

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
    selectedOptions.value[String(typeId)]?.has(value) ?? selectedOptions.value[typeId]?.has(value) ?? false

  const toggleOption = (typeId, value) => {
    const tIdStr = String(typeId)
    if (!selectedOptions.value[tIdStr]) selectedOptions.value[tIdStr] = new Set()
    const set = selectedOptions.value[tIdStr]

    const isTier = isVariationTier(typeId)

    if (isTier) {
      if (set.has(value)) set.delete(value)
      else set.add(value)
    } else {
      if (set.has(value)) {
        set.delete(value)
      } else {
        set.clear()
        set.add(value)
      }
    }

    if (set.size > 1 && !isTier) {
      if (variationTierIds.value.size < 3) {
        variationTierIds.value.add(tIdStr)
        variationTierIds.value = new Set(Array.from(variationTierIds.value).map(String))
      }
    }

    selectedOptions.value = { ...selectedOptions.value }

    if (fieldErrors.value.variantGroups?.[tIdStr]) {
      const nextErrors = { ...(fieldErrors.value.variantGroups || {}) }
      if ((selectedOptions.value[tIdStr]?.size ?? 0) > 0) {
        delete nextErrors[tIdStr]
      }
      fieldErrors.value.variantGroups = nextErrors
      if (!Object.keys(nextErrors).length) {
        fieldErrors.value.variants = ''
      }
    }
  }

  const selectedCountInGroup = g =>
    g.attrTypes.reduce((s, t) => s + (selectedOptions.value[t.id]?.size ?? 0), 0)

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

  const rebuildSelectedOptionsFromRows = () => {
    const nextSelectedOptions = {}

    // 1. Bảo tồn các Thông số kỹ thuật hiện có
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

  const formatCurrency = (val) => {
    if (val === '' || val === null || val === undefined) return ''
    const num = Math.round(Number(val))
    if (isNaN(num)) return ''
    return num.toLocaleString('vi-VN')
  }

  const parseCurrency = (val) => {
    if (!val) return ''
    return String(val).replace(/\D/g, '')
  }

  const applyRulesToAll = (override = true) => {
    if (basePrice.value === '' && baseStock.value === '') {
      swal.warning('Thiếu thông tin', 'Vui lòng nhập Giá chung hoặc Kho chung trước khi áp dụng')
      return
    }

    generatedRows.value.forEach(row => {
      if (row.enabled === false) return

      if (basePrice.value !== '') {
        if (override || row.price === '' || row.price === null || row.price === undefined) {
          row.price = Number(basePrice.value)
          row._manualPrice = true
        }
      }

      if (baseStock.value !== '') {
        if (override || row.stock === '' || row.stock === null || row.stock === undefined) {
          row.stock = Number(baseStock.value)
          row._manualStock = true
        }
      }
    })
  }

  const markManualPrice = (row) => {
    row._manualPrice = true
  }

  const markManualStock = (row) => {
    row._manualStock = true
  }

  const validateVariantRows = () => {
    if (!generatedRows.value.length) {
      fieldErrors.value.variantRows = 'Vui lòng cấu hình ít nhất 1 biến thể sản phẩm'
      return false
    }

    const activeRows = generatedRows.value.filter(r => r.enabled !== false)
    if (!activeRows.length) {
      fieldErrors.value.variantRows = 'Vui lòng bật ít nhất 1 cấu hình thực tế bán'
      return false
    }

    const invalidRow = activeRows.find(row => {
      const hasPrice = row.price !== '' && row.price !== null && row.price !== undefined
      const hasStock = row.stock !== '' && row.stock !== null && row.stock !== undefined

      if (!hasPrice || !hasStock) return true
      if (hasPrice && Number(row.price) <= 0) return true
      if (hasStock && Number(row.stock) < 0) return true

      return false
    })

    if (invalidRow) {
      fieldErrors.value.variantRows = 'Vui lòng nhập đủ giá bán (> 0) và số lượng kho (>= 0) cho tất cả cấu hình đang bán'
      return false
    }

    fieldErrors.value.variantRows = ''
    return true
  }

  /* ─────────────────────────────────────────────────────────────
     NAVIGATION & WIZARD ACTIONS
  ───────────────────────────────────────────────────────────── */
  const goToTab = (tab) => {
    if (tab === activeFormTab.value) return
    if (tab === 'attributes') {
      if (!validateTopForm()) {
        activeFormTab.value = 'basic'
        return
      }
    }
    if (tab === 'description') {
      if (!validateTopForm()) {
        activeFormTab.value = 'basic'
        return
      }
      if (vsPhase.value === 1) {
        if (!validateAttributeSelection()) return
        generateVariants()
        if (vsPhase.value !== 2) return
      } else {
        if (!validateVariantRows()) return
      }
    }
    activeFormTab.value = tab
  }

  const nextTab = () => {
    if (activeFormTab.value === 'basic') {
      if (!validateTopForm()) return
      activeFormTab.value = 'attributes'
    } else if (activeFormTab.value === 'attributes') {
      if (vsPhase.value === 1) {
        if (!validateAttributeSelection()) return
        generateVariants()
        if (vsPhase.value === 2) {
          activeFormTab.value = 'description'
        }
      } else {
        if (!validateVariantRows()) return
        activeFormTab.value = 'description'
      }
    }
  }

  const prevTab = () => {
    if (activeFormTab.value === 'description') activeFormTab.value = 'attributes'
    else if (activeFormTab.value === 'attributes') activeFormTab.value = 'basic'
  }

  /* ─────────────────────────────────────────────────────────────
     RESET / OPEN / CLOSE / SUBMIT
  ───────────────────────────────────────────────────────────── */
  const resetForm = () => {
    form.value = defaultForm()
    imgPreview.value = ''
    extraImagePreviews.value = []
    formError.value = ''
    resetFieldErrors()

    activeFormTab.value = 'basic'
    variantCurrentPage.value = 1
    vsPhase.value = 1
    selectedOptions.value = {}
    selectedOptionsSnapshot.value = {}
    generatedRows.value = []
    editVariantHeaders.value = []
    variationTierIds.value = new Set(['color-type'])

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

      if (!baseAttributeGroups.value.length) {
        await Promise.allSettled([
          fetchAttributeGroups(),
          fetchColors(),
        ])
      }

      const res = await api.get(`/sanpham/${id}`)
      const product = res.data

      isEditMode.value = true
      editingProductId.value = id

      mapProductToForm(product)
      showModal.value = true
      currentView.value = 'product-form'
      activeFormTab.value = 'basic'
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
      description: product?.mota || product?.mo_ta || product?.description || '',
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
        enabled: true,
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

    variationTierIds.value = new Set(editVariantHeaders.value.map(h => String(h.id)))
    if (variationTierIds.value.size === 0) {
      variationTierIds.value.add('color-type')
    }

    rebuildSelectedOptionsFromRows()

    let specs = []
    if (Array.isArray(product?.thong_so_ky_thuat)) {
      specs = product.thong_so_ky_thuat
    } else if (typeof product?.thong_so_ky_thuat === 'string') {
      try { specs = JSON.parse(product.thong_so_ky_thuat) } catch (e) { }
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

    // Đảm bảo tất cả các giá trị thuộc tính đã chọn (kể cả tạm thời/tùy chỉnh) đều có mặt trong options để hiển thị nút
    Object.entries(selectedOptions.value || {}).forEach(([attrId, valSet]) => {
      const targetType = allAttrTypes.value.find(t => String(t.id) === String(attrId))
      if (targetType && targetType.options) {
        valSet.forEach(val => {
          const exists = targetType.options.some(opt => getOptionValue(opt) === val)
          if (!exists) {
            targetType.options.push({ label: val, value: val })
          }
        })
      }
    })

    selectedOptionsSnapshot.value = cloneSelectedOptions(selectedOptions.value)
    variantCurrentPage.value = 1
    vsPhase.value = 2
  }

  const openModal = async () => {
    localStorage.removeItem('global_form_draft_/admin/quan-ly-san-pham')
    const draftKey = 'form_draft_key_product_form'
    const savedDraftStr = localStorage.getItem(draftKey)

    resetForm()
    isEditMode.value = false
    editingProductId.value = null
    currentView.value = 'product-form'
    activeFormTab.value = 'basic'

    if (!baseAttributeGroups.value.length) {
      await Promise.allSettled([
        fetchAttributeGroups(),
        fetchColors(),
      ])
    }

    if (attributeGroups.value.length > 0) {
      selectedGroupId.value = attributeGroups.value[0].id
    }

    if (savedDraftStr) {
      try {
        const parsed = JSON.parse(savedDraftStr)
        const draftForm = parsed.form || parsed
        if (draftForm && (draftForm.name || draftForm.brand || draftForm.category || draftForm.description || draftForm.weight || parsed.imgPreview || draftForm.img || parsed.generatedRows?.length || parsed.selectedOptions)) {
          const confirmed = await swal.confirm(
            'Phát hiện bản nháp sản phẩm chưa lưu',
            `Bạn có bản nháp sản phẩm chưa lưu${draftForm.name ? ' ("' + draftForm.name + '")' : ''}. Bạn có muốn khôi phục lại dữ liệu không?`,
            'Khôi phục ngay',
            'Tạo mới từ đầu'
          )
          if (confirmed) {
            isRestoringDraft.value = true
            try {
              if (draftForm.name) form.value.name = draftForm.name
              if (draftForm.brand) form.value.brand = String(draftForm.brand)
              if (draftForm.category) form.value.category = String(draftForm.category)
              if (draftForm.parentCategory) form.value.parentCategory = String(draftForm.parentCategory)
              if (draftForm.weight) form.value.weight = draftForm.weight
              if (draftForm.description) form.value.description = draftForm.description
              if (draftForm.category) selectedCategory.value = String(draftForm.category)

              if (parsed.imgPreview) imgPreview.value = parsed.imgPreview
              if (draftForm.img) form.value.img = draftForm.img
              if (Array.isArray(parsed.extraImagePreviews)) extraImagePreviews.value = parsed.extraImagePreviews

              // Khôi phục Bước 2: Thuộc tính & danh sách biến thể bán theo đúng Giai đoạn (Phase)
              const phase = parsed.vsPhase || (parsed.generatedRows?.length > 0 ? 2 : 1)
              vsPhase.value = phase

              if (parsed.selectedOptions) {
                selectedOptions.value = importSelectedOptionsFromDraft(parsed.selectedOptions)
                selectedOptionsSnapshot.value = cloneSelectedOptions(selectedOptions.value)
              }
              if (Array.isArray(parsed.variationTierIds) && parsed.variationTierIds.length > 0) {
                variationTierIds.value = new Set(parsed.variationTierIds.map(String))
              }

              if (phase === 2) {
                if (Array.isArray(parsed.generatedRows) && parsed.generatedRows.length > 0) {
                  generatedRows.value = parsed.generatedRows
                }
                if (Array.isArray(parsed.editVariantHeaders) && parsed.editVariantHeaders.length > 0) {
                  editVariantHeaders.value = parsed.editVariantHeaders
                }
              } else {
                generatedRows.value = []
                editVariantHeaders.value = []
              }

              swal.toast('Đã khôi phục dữ liệu bản nháp thành công!', 'success')
            } finally {
              setTimeout(() => {
                isRestoringDraft.value = false
              }, 500)
            }
          } else {
            localStorage.removeItem(draftKey)
          }
        }
      } catch (e) {
        console.error('Lỗi khôi phục bản nháp:', e)
      }
    }
  }

  watch(
    [form, selectedCategory, imgPreview, extraImagePreviews, generatedRows, selectedOptions, variationTierIds, vsPhase],
    () => {
      if (currentView.value === 'product-form' && !isEditMode.value) {
        const hasFormContent = form.value.name || form.value.brand || form.value.description || selectedCategory.value || imgPreview.value || generatedRows.value?.length || Object.keys(selectedOptions.value || {}).length > 0

        if (hasFormContent) {
          try {
            const draftPayload = {
              form: {
                name: form.value.name,
                parentCategory: form.value.parentCategory,
                category: form.value.category || selectedCategory.value,
                brand: form.value.brand,
                weight: form.value.weight,
                status: form.value.status,
                description: form.value.description,
                img: form.value.img,
              },
              imgPreview: imgPreview.value,
              extraImagePreviews: extraImagePreviews.value,
              selectedOptions: exportSelectedOptionsForDraft(selectedOptions.value),
              variationTierIds: Array.from(variationTierIds.value || []),
              vsPhase: vsPhase.value,
            }

            if (vsPhase.value === 2) {
              draftPayload.generatedRows = generatedRows.value
              draftPayload.editVariantHeaders = editVariantHeaders.value
            }

            localStorage.setItem('form_draft_key_product_form', JSON.stringify(draftPayload))
          } catch (e) {
            console.warn('Dung lượng hình ảnh/biến thể lớn vượt quá bộ nhớ nháp tạm:', e)
          }
        }
      }
    },
    { deep: true }
  )

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
      if (error.isOfflineQueue) {
        localStorage.removeItem(`global_form_draft_${window.location.pathname}`)
        resetForm()
        closeModal()
        await swal.info('Chế độ ngoại tuyến', 'Yêu cầu thêm/sửa sản phẩm đã được lưu tạm vào hàng đợi. Hệ thống sẽ tự động gửi lên máy chủ khi có mạng trở lại.')
      } else {
        console.error(error)
        swal.error('Lỗi', getErrorMessage(error, isEditMode.value
          ? 'Có lỗi xảy ra khi cập nhật sản phẩm'
          : 'Có lỗi xảy ra khi thêm sản phẩm'))
      }
    }
  }

  const syncSuccessHandler = () => {
    fetchProducts()
  }

  onMounted(() => {
    localStorage.removeItem('global_form_draft_/admin/quan-ly-san-pham')
    loadProductsCache()
    fetchProducts()
    window.addEventListener('offline-sync-success', syncSuccessHandler)
    Promise.allSettled([
      fetchParentCategories(),
      fetchCategories(),
      fetchBrands(),
    ])
  })

  onBeforeUnmount(() => {
    window.removeEventListener('offline-sync-success', syncSuccessHandler)
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
          <!-- Nút Xuất báo cáo (Giữ nguyên) -->
          <button class="btn-excel btn-export admin-report-export" @click="handleExportExcel" :disabled="isExporting">
            <svg v-if="!isExporting" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2"
              fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="7 10 12 15 17 10"></polyline>
              <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <span v-else class="spinner-sm"></span>
            {{ isExporting ? 'Đang xuất...' : 'Xuất báo cáo' }}
          </button>

          <button v-if="hasPermission('nhap_xuat_kho')" class="btn-excel btn-import" @click="triggerImportExcel"
            :disabled="isImporting">
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

          <button v-if="hasPermission('san_pham_sua')" class="add-btn" @click="openModal">+ Th&#234;m s&#7843;n
            ph&#7849;m</button>
        </div>
      </div>

      <div class="stats">
        <div class="stat-card stat-blue">
          <span class="stat-icon blue" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round">
              <path
                d="M21 8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round">
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
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round">
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
        <button class="parent-tab-btn" :class="{ active: selectedParentTab === '' }" @click="selectParentTab('')">
          T&#7845;t c&#7843; s&#7843;n ph&#7849;m
        </button>
        <button v-for="parentCat in parentCategories" :key="parentCat.id_danhmuc_cha" class="parent-tab-btn"
          :class="{ active: String(selectedParentTab) === String(parentCat.id_danhmuc_cha) }"
          @click="selectParentTab(parentCat.id_danhmuc_cha)">
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
          <div class="dropdown-trigger"
            @click.stop="isOpenStatusDropdown = !isOpenStatusDropdown; isOpenCategoryDropdown = false">
            <span>{{ selectedStatus || 'T\u1ea5t c\u1ea3 tr\u1ea1ng th\u00e1i' }}</span>
            <svg class="chevron" :class="{ open: isOpenStatusDropdown }" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
          <transition name="fade-slide">
            <ul v-if="isOpenStatusDropdown" class="dropdown-menu">
              <li :class="{ active: selectedStatus === '' }" @click="selectedStatus = ''; isOpenStatusDropdown = false">
                T&#7845;t c&#7843; tr&#7841;ng th&#225;i</li>
              <li :class="{ active: selectedStatus === '\u0110ang b\u00e1n' }"
                @click="selectedStatus = '\u0110ang b\u00e1n'; isOpenStatusDropdown = false">&#272;ang b&#225;n</li>
              <li :class="{ active: selectedStatus === 'Nh\u00e1p' }"
                @click="selectedStatus = 'Nh\u00e1p'; isOpenStatusDropdown = false">Nh&#225;p</li>
            </ul>
          </transition>
        </div>

        <!-- Custom Category Dropdown -->
        <div class="custom-dropdown">
          <div class="dropdown-trigger"
            @click.stop="isOpenCategoryDropdown = !isOpenCategoryDropdown; isOpenStatusDropdown = false">
            <span>{{ getSelectedCategoryLabel() }}</span>
            <svg class="chevron" :class="{ open: isOpenCategoryDropdown }" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
          <transition name="fade-slide">
            <ul v-if="isOpenCategoryDropdown" class="dropdown-menu">
              <li :class="{ active: selectedCategory === '' }"
                @click="selectedCategory = ''; isOpenCategoryDropdown = false">T&#7845;t c&#7843; danh m&#7909;c</li>
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
              <td><span class="status-badge" :class="p.status === '\u0110ang b\u00e1n' ? 'active' : 'draft'">{{ p.status
                  }}</span>
              </td>
              <td>
                <div class="actions">
                  <button v-if="hasPermission('san_pham_sua')" class="act-btn" title="S&#7917;a"
                    @click="openEditModal(p.id)">
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

      <PhanTrangAdmin v-model:currentPage="currentPage" :total-pages="totalPages" :total-items="filteredProducts.length"
        :page-size="pageSize" item-label="sản phẩm" @change-page="goToPage" />

    </template><!-- end list view -->

    <!-- ═══════════════════════════════════════════════════════
         VIEW: FORM SẢN PHẨM (Thêm / Sửa 3 Tab Wizard)
    ═══════════════════════════════════════════════════════ -->
    <template v-if="currentView === 'product-form'">
      <div class="inline-form-header">
        <div class="header-left">
          <button class="back-btn" @click="closeModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
              <path d="M15 18l-6-6 6-6" />
            </svg>
            Quay lại danh sách
          </button>
          <h1>{{ isEditMode ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' }}</h1>
          <p>{{ isEditMode ? 'Cập nhật thông tin, hình ảnh, thông số kỹ thuật và cấu hình biến thể' : 'Thiết lập đầy đủ thông tin để đăng bán sản phẩm lên hệ thống NextGen' }}</p>
        </div>
      </div>

      <!-- ─── TAB NAVIGATION WIZARD ─── -->
      <div class="form-wizard-tabs">
        <button class="wizard-tab-btn"
          :class="{ active: activeFormTab === 'basic', 'has-error': tabHasErrors('basic'), 'is-done': isStep1Completed }" @click="goToTab('basic')">
          <span class="tab-step-num" :class="{ 'step-success': isStep1Completed }">
            <template v-if="isStep1Completed">✓</template>
            <template v-else>1</template>
          </span>
          <div class="tab-text-wrap">
            <span class="tab-main-title">Thông tin & Hình ảnh</span>
            <span class="tab-sub-title">Tên, danh mục, hãng, ảnh đại diện</span>
          </div>
          <span v-if="tabHasErrors('basic')" class="tab-error-dot" title="Có thông tin chưa hợp lệ">!</span>
        </button>

        <button class="wizard-tab-btn"
          :class="{ active: activeFormTab === 'attributes', 'has-error': tabHasErrors('attributes'), 'is-done': isStep2Completed }"
          @click="goToTab('attributes')">
          <span class="tab-step-num" :class="{ 'step-success': isStep2Completed }">
            <template v-if="isStep2Completed">✓</template>
            <template v-else>2</template>
          </span>
          <div class="tab-text-wrap">
            <span class="tab-main-title">Thuộc tính & Biến thể bán</span>
            <span class="tab-sub-title">Thông số chung, cấu hình, giá & kho</span>
          </div>
          <span v-if="tabHasErrors('attributes')" class="tab-error-dot" title="Có thông tin chưa hợp lệ">!</span>
        </button>

        <button class="wizard-tab-btn" :class="{ active: activeFormTab === 'description', 'is-done': isStep3Completed }"
          @click="goToTab('description')">
          <span class="tab-step-num" :class="{ 'step-success': isStep3Completed }">
            <template v-if="isStep3Completed">✓</template>
            <template v-else>3</template>
          </span>
          <div class="tab-text-wrap">
            <span class="tab-main-title">Xem trước đăng bán</span>
            <span class="tab-sub-title">Mô phỏng thẻ sản phẩm ngoài trang bán</span>
          </div>
        </button>
      </div>

      <div class="inline-form-body">
        <!-- ═══════════════════════════════════════════════════════
             TAB 1: THÔNG TIN CƠ BẢN & HÌNH ẢNH
        ═══════════════════════════════════════════════════════ -->
        <div v-show="activeFormTab === 'basic'" class="tab-content-panel">
          <div class="tab-grid-2col">
            <!-- Left col: Text fields -->
            <div class="form-card">
              <div class="form-card-header">
                <span class="fch-icon">📋</span>
                <div>
                  <h3>Thông tin sản phẩm</h3>
                  <p>Thiết lập tên, phân loại danh mục và thương hiệu</p>
                </div>
              </div>

              <div class="form-group">
                <div class="label-with-badge">
                  <label>Tên sản phẩm <span class="required">*</span></label>
                  <span class="sku-hint-badge">Mã SKU gợi ý: <b>{{ suggestedSku }}</b></span>
                </div>
                <input v-model="form.name" @input="fieldErrors.name = ''" placeholder="VD: MacBook Air 15 inch M4"
                  :class="{ 'input-error': fieldErrors.name }" />
                <p v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</p>
              </div>

              <div class="form-group">
                <label>Danh mục sản phẩm <span class="required">*</span></label>
                <div class="tree-select-static-container" :class="{ 'has-error': fieldErrors.category }">
                  <div class="tree-search-wrapper">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="8" />
                      <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input v-model="treeSearchQuery" placeholder="Tìm kiếm nhanh danh mục..."
                      class="tree-search-input" />
                    <button v-if="treeSearchQuery" @click="treeSearchQuery = ''" class="clear-search-btn">×</button>
                  </div>
                  <div class="tree-list-container">
                    <div v-if="filteredTreeCategories.length === 0" class="tree-empty">Không tìm thấy danh mục phù hợp.
                    </div>
                    <div v-for="parent in filteredTreeCategories" :key="parent.id_danhmuc_cha" class="tree-parent-node">
                      <div class="tree-parent-row" @click="toggleParentExpand(parent.id_danhmuc_cha)">
                        <span class="tree-toggle-icon">{{ isParentExpanded(parent.id_danhmuc_cha) ? '▾' : '▸' }}</span>
                        <span class="tree-folder-icon">📁</span>
                        <span class="tree-parent-name">{{ parent.ten_danhmuc }}</span>
                      </div>
                      <transition name="collapse">
                        <div v-show="isParentExpanded(parent.id_danhmuc_cha)" class="tree-children-list">
                          <div v-for="child in parent.children" :key="child.id_danhmuc" class="tree-child-node"
                            :class="{ selected: String(form.category) === String(child.id_danhmuc) }"
                            @click="selectTreeCategory(child)">
                            <span class="tree-leaf-icon">📄</span>
                            <span class="tree-child-name">{{ child.ten_danhmuc }}</span>
                            <span v-if="String(form.category) === String(child.id_danhmuc)"
                              class="selected-check">✓</span>
                          </div>
                        </div>
                      </transition>
                    </div>
                  </div>
                </div>
                <p v-if="fieldErrors.category" class="field-error">{{ fieldErrors.category }}</p>
              </div>

              <div class="form-fields-row-3">
                <div class="form-group">
                  <label>Thương hiệu <span class="required">*</span></label>
                  <select v-model="form.brand" @change="fieldErrors.brand = ''"
                    :class="{ 'input-error': fieldErrors.brand }" :disabled="!form.category">
                    <option value="">-- Chọn thương hiệu --</option>
                    <option v-for="brand in brands" :key="brand.id_thuonghieu" :value="brand.id_thuonghieu">
                      {{ brand.ten_thuonghieu }}
                    </option>
                  </select>
                  <p v-if="fieldErrors.brand" class="field-error">{{ fieldErrors.brand }}</p>
                </div>
                <div class="form-group">
                  <label>Khối lượng (kg)</label>
                  <input v-model="form.weight" type="number" min="0" step="0.01" @input="fieldErrors.weight = ''"
                    placeholder="VD: 1.4" />
                  <p v-if="fieldErrors.weight" class="field-error">{{ fieldErrors.weight }}</p>
                </div>
                <div class="form-group">
                  <label>Trạng thái</label>
                  <select v-model="form.status" @change="fieldErrors.status = ''">
                    <option>Đang bán</option>
                    <option>Nháp</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Right col: Images uploader -->
            <div class="form-card">
              <div class="form-card-header">
                <span class="fch-icon">🖼️</span>
                <div>
                  <h3>Hình ảnh & Thư viện</h3>
                  <p>Tải lên ảnh đại diện bìa và các góc nhìn phụ</p>
                </div>
              </div>

              <div class="form-group">
                <label>Ảnh đại diện chính <span class="required">*</span></label>
                <input id="product-main-image-input" ref="fileInputRef" type="file"
                  accept="image/png,image/jpeg,image/jpg,image/webp" class="visually-hidden-file"
                  @change="onFileChange" />
                <label v-if="!imgPreview" class="upload-zone upload-zone-compact" for="product-main-image-input"
                  @dragover.prevent @drop.prevent="onMainImageDrop">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                  </svg>
                  <p>Kéo thả hoặc <span>chọn ảnh bìa</span></p>
                  <small>PNG, JPG, WEBP - tối đa 5MB</small>
                </label>
                <div v-else class="img-preview-wrap">
                  <div class="img-action-buttons">
                    <button type="button" class="img-change-btn" @click="triggerFileInput">Đổi ảnh</button>
                    <button type="button" class="img-remove-btn" @click="removeImg">Xóa</button>
                  </div>
                  <img :src="imgPreview" class="img-preview" alt="Ảnh sản phẩm" />
                </div>
                <p v-if="fieldErrors.img" class="field-error">{{ fieldErrors.img }}</p>
              </div>

              <div class="form-group">
                <label>Thư viện ảnh phụ (Tối đa 10 ảnh)</label>
                <input id="product-extra-images-input" ref="extraFileInputRef" type="file"
                  accept="image/png,image/jpeg,image/jpg,image/webp" multiple class="visually-hidden-file"
                  @change="onExtraFilesChange" />
                <label class="upload-zone upload-zone-compact" for="product-extra-images-input" @dragover.prevent
                  @drop.prevent="onExtraImagesDrop">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" y1="3" x2="12" y2="15" />
                  </svg>
                  <p>Kéo thả hoặc <span>chọn nhiều ảnh phụ</span></p>
                  <small>Chọn nhiều góc chụp, mở hộp, cổng kết nối...</small>
                </label>
                <p v-if="fieldErrors.images" class="field-error">{{ fieldErrors.images }}</p>
                <div v-if="extraImagePreviews.length" class="multi-preview-wrap">
                  <div v-for="(img, index) in extraImagePreviews" :key="index" class="multi-preview-item">
                    <img :src="img" class="multi-preview-img" :alt="'Ảnh phụ ' + (index + 1)" />
                    <button class="multi-preview-remove" @click="removeExtraImage(index)">×</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             TAB 2: THUỘC TÍNH, THÔNG SỐ & BIẾN THỂ BÁN
        ═══════════════════════════════════════════════════════ -->
        <div v-show="activeFormTab === 'attributes'" class="tab-content-panel">
          <div v-if="!form.category" class="empty-category-card">
            <span class="empty-icon">⚠️</span>
            <h3>Chưa chọn danh mục sản phẩm</h3>
            <p>Vui lòng quay lại <b>Tab 1 (Thông tin & Hình ảnh)</b> và chọn danh mục để nạp bộ thuộc tính tương ứng.
            </p>
            <button class="btn-primary-sm" @click="activeFormTab = 'basic'">← Quay lại chọn danh mục</button>
          </div>

          <div v-else class="attributes-central-container">
            <!-- Instruction Banner -->
            <div class="guide-banner">
              <div class="gb-content">
                <b>Hướng dẫn cấu hình:</b> Mặc định tất cả thuộc tính là <b>Thông số kỹ thuật chung</b>. Bật công tắc
                <b>[Biến thể bán]</b> (tối đa 3 thuộc tính: Màu sắc, RAM, CPU,...) cho các thuộc tính cần tạo phân
                loại bán. Ở bảng bên dưới, bạn có thể <b>Bật / Tắt</b> để chỉ bán đúng các phiên bản thực tế có trong
                kho.
              </div>
            </div>

            <!-- Attribute Groups Accordion (Phase 1) -->
            <div v-show="vsPhase === 1" class="form-card">
              <div class="form-card-header">
                <div>
                  <h3>Thiết lập thuộc tính biến thể</h3>
                  <p>Chọn giá trị thông số kỹ thuật và kích hoạt các thuộc tính dùng làm biến thể bán</p>
                </div>
                <div class="tier-badge-pill" :class="{ 'tier-full': variationTierIds.size >= 3 }">
                  Cấp biến thể đã bật: <b>{{ variationTierIds.size }}/3</b>
                </div>
              </div>

              <div v-if="variantLoading" class="group-placeholder"><span>Đang tải dữ liệu thuộc tính...</span></div>
              <div v-else-if="attributeGroups.length === 0" class="group-placeholder"><span>Không tìm thấy thuộc tính
                  nào cho danh mục này.</span></div>
              <div v-else class="accordion-container">
                <div v-for="g in attributeGroups" :key="g.id" class="accordion-item"
                  :class="{ 'is-open': activeAccordionGroups.has(String(g.id)) }">
                  <div class="accordion-header" @click="toggleAccordionGroup(g.id)">
                    <div class="accordion-title">
                      <span class="accordion-icon">{{ g.icon }}</span>
                      <span class="accordion-name">{{ g.name }}</span>
                      <span v-if="selectedCountInGroup(g) > 0" class="accordion-badge">Đang chọn {{
                        selectedCountInGroup(g) }}</span>
                    </div>
                    <svg class="chevron" :class="{ open: activeAccordionGroups.has(String(g.id)) }" viewBox="0 0 24 24"
                      fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                  </div>
                  <transition name="collapse">
                    <div v-show="activeAccordionGroups.has(String(g.id))" class="accordion-body">
                      <div class="flat-select-table">
                        <div v-for="t in g.attrTypes" :key="t.id" class="fst-row"
                          :class="{ 'is-variant-tier': isVariationTier(t.id) }">
                          <div class="fst-label">
                            <div class="fst-label-top">
                              <span class="type-pill" :class="'tp-' + t.color">{{ t.label }}</span>
                              <span v-if="selectedOptions[t.id]?.size" class="fst-count">{{ selectedOptions[t.id].size
                              }}</span>
                            </div>
                            <div class="mode-switch-wrapper" title="Bật để chọn nhiều giá trị và tạo tổ hợp bán">
                              <span class="mode-label" :class="{ active: isVariationTier(t.id) }">
                                {{ isVariationTier(t.id) ? 'Biến thể bán' : 'Thông số kỹ thuật' }}
                              </span>
                              <label class="switch-control">
                                <input type="checkbox" :checked="isVariationTier(t.id)"
                                  @change="toggleVariationTier(t.id)" />
                                <span class="switch-slider"></span>
                              </label>
                            </div>
                          </div>
                          <div class="fst-options-wrap">
                            <div v-if="t.id === 'color-type'" class="color-swatches-grid">
                              <button v-for="opt in t.options" :key="getOptionValue(opt)" class="color-swatch-btn"
                                :class="{ selected: isSelected(t.id, getOptionValue(opt)) }"
                                @click="toggleOption(t.id, getOptionValue(opt))">
                                <span class="swatch-circle"
                                  :style="{ backgroundColor: getOptionHex(opt) || '#ccc' }"><span
                                    v-if="isSelected(t.id, getOptionValue(opt))" class="swatch-check">✓</span></span>
                                <span class="swatch-label">{{ getOptionLabel(opt) }}</span>
                              </button>
                            </div>
                            <div v-else class="fst-options">
                              <button v-for="opt in t.options" :key="getOptionValue(opt)" class="vbtn"
                                :class="['vbtn-' + t.color, { 'vbtn-on': isSelected(t.id, getOptionValue(opt)) }]"
                                @click="toggleOption(t.id, getOptionValue(opt))">
                                <span>{{ getOptionLabel(opt) }}</span>
                              </button>
                            </div>
                            <p v-if="fieldErrors.variantGroups && fieldErrors.variantGroups[t.id]" class="field-error">
                              {{ fieldErrors.variantGroups[t.id] }}</p>
                          </div>
                          <div class="fst-actions-col">
                            <button type="button" class="quick-act-btn add-val-btn" title="Thêm giá trị thuộc tính" @click.stop="openAddAttrValueModal(t)">+</button>
                            <button class="quick-act-btn select-all" @click="selectAllOptions(t.id, t.options)">Chọn tất
                              cả</button>
                            <button class="quick-act-btn clear-all" :disabled="!selectedOptions[t.id]?.size"
                              @click="clearAllOptions(t.id)">Bỏ chọn</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </transition>
                </div>
              </div>

              <!-- Button generate combinations -->
              <div class="generate-bar">
                <div class="gb-summary">
                  <span v-if="variationHeaders.length > 0">
                    Thuộc tính tạo biến thể: <b v-for="(vh, idx) in variationHeaders" :key="vh.id">{{ vh.label }}<span
                        v-if="idx < variationHeaders.length - 1"> × </span></b>
                    ➜ Dự kiến: <b>{{ comboCount }} tổ hợp</b>
                  </span>
                  <span v-else class="text-muted">Chưa bật thuộc tính biến thể nào. Mặc định hệ thống sẽ dùng Màu sắc
                    làm biến thể.</span>
                </div>
                <button class="btn-generate-main" @click="generateVariants">
                  🔄 {{ generatedRows.length ? 'Cập nhật danh sách tổ hợp' : 'Sinh danh sách biến thể bán' }}
                </button>
              </div>
            </div>

            <!-- Variants Table Matrix (Phase 2) -->
            <div v-show="vsPhase === 2 && generatedRows.length" class="form-card matrix-card">
              <div class="form-card-header">
                <span class="fch-icon">📦</span>
                <div>
                  <h3>Ma trận biến thể & Giá kho thực tế</h3>
                  <p>Bật/tắt các cấu hình thực tế bán, điền giá và tồn kho</p>
                </div>
                <div class="header-right-badges">
                  <button class="btn-xl-sm btn-xl-edit-attrs" title="Chỉnh sửa lại các thuộc tính đã chọn"
                    @click="backToSelect">⚙️ Chỉnh sửa thuộc tính</button>
                  <span class="active-badge">Đang bán: <b>{{ activeVariantsCount }} / {{ generatedRows.length }}</b> cấu
                    hình</span>
                </div>
              </div>

              <!-- Bulk Toolbar -->
              <div class="matrix-bulk-toolbar">
                <div class="bulk-inputs">
                  <span class="bulk-title">Điền hàng loạt:</span>
                  <input :value="formatCurrency(basePrice)" @input="basePrice = parseCurrency($event.target.value)"
                    class="bulk-input-field" placeholder="Giá chung (VNĐ)" />
                  <input v-model="baseStock" class="bulk-input-field bulk-num" type="number" min="0"
                    placeholder="Kho chung" />
                  <button class="btn-apply-bulk" @click="applyRulesToAll(true)">⚡ Áp dụng cho các cấu hình đang
                    bật</button>
                </div>
                <div class="bulk-quick-actions">
                  <button class="btn-quick-toggle" @click="setAllVariantsEnabled(true)">Bật tất cả</button>
                  <button class="btn-quick-toggle" @click="setAllVariantsEnabled(false)">Tắt tất cả</button>
                  <button class="btn-add-manual" @click="addManualVariant">+ Thêm cấu hình</button>
                </div>
              </div>

              <!-- Variant Table -->
              <div class="vt-scroll">
                <table class="vt-table">
                  <thead>
                    <tr>
                      <th class="th-toggle">Bán</th>
                      <th class="th-no">#</th>
                      <th v-for="t in tableHeaders" :key="t.id"><span class="type-pill" :class="'tp-' + t.color">{{
                        t.label }}</span></th>
                      <th class="th-price">Giá bán (VNĐ) <span class="required">*</span></th>
                      <th class="th-stock">Kho hàng <span class="required">*</span></th>
                      <th class="th-del">Xóa</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, ri) in paginatedVariants" :key="row.id" class="vt-row"
                      :class="{ 'row-disabled': row.enabled === false }">
                      <td class="td-toggle">
                        <label class="toggle-checkbox">
                          <input type="checkbox" v-model="row.enabled" />
                          <span class="checkmark"></span>
                        </label>
                      </td>
                      <td class="td-no"><span class="row-no">{{ (variantCurrentPage - 1) * VARIANTS_PER_PAGE + ri + 1
                          }}</span></td>
                      <td v-for="t in tableHeaders" :key="t.id">
                        <span class="val-chip" :class="'vc-' + t.color">{{ row.attrs[t.id] || '' }}</span>
                      </td>
                      <td>
                        <input :value="formatCurrency(row.price)" type="text" class="vt-input"
                          :disabled="row.enabled === false" placeholder="0 đ"
                          @input="(e) => { row.price = parseCurrency(e.target.value); markManualPrice(row) }" />
                      </td>
                      <td>
                        <input :value="row.stock" type="number" min="0" class="vt-input vt-num"
                          :disabled="row.enabled === false" placeholder="0"
                          @input="(e) => { row.stock = e.target.value; markManualStock(row) }" />
                      </td>
                      <td class="td-del">
                        <button class="btn-row-del" @click="removeVariantRow(ri)" title="Xóa cấu hình này">×</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div v-if="generatedRows.length > VARIANTS_PER_PAGE" class="variant-pagination">
                <button :disabled="variantCurrentPage === 1"
                  @click="goToVariantPage(variantCurrentPage - 1)">&lt;</button>
                <span class="pg-active page-indicator">{{ variantCurrentPage }}/{{ variantTotalPages }}</span>
                <button :disabled="variantCurrentPage === variantTotalPages"
                  @click="goToVariantPage(variantCurrentPage + 1)">&gt;</button>
              </div>
              <p v-if="fieldErrors.variantRows" class="field-error" style="margin-top: 12px;">{{ fieldErrors.variantRows
              }}</p>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             TAB 3: XEM TRƯỚC GIAO DIỆN ĐĂNG BÁN
        ═══════════════════════════════════════════════════════ -->
        <div v-show="activeFormTab === 'description'" class="tab-content-panel">
          <div class="preview-centered-wrapper" style="max-width: 680px; margin: 0 auto;">
            <!-- Live Preview Card -->
            <div class="form-card live-preview-card">
              <div class="form-card-header">
                <div>
                  <h3>Xem trước giao diện đăng bán</h3>
                  <p>Mô phỏng sản phẩm hiển thị ngoài trang bán hàng</p>
                </div>
              </div>

              <div class="preview-product-box">
                <div class="ppb-image-wrap" :class="{ 'is-laptop-preview': isLaptopProduct }">
                  <img :src="imgPreview || 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=300'"
                    alt="Preview" />
                  <span class="ppb-status-tag" :class="form.status === 'Đang bán' ? 'active' : 'draft'">{{ form.status
                  }}</span>
                </div>
                <div class="ppb-details">
                  <div class="ppb-meta">
                    <span class="ppb-brand">{{brands.find(b => String(b.id_thuonghieu) ===
                      String(form.brand))?.ten_thuonghieu || 'Thương hiệu'}}</span>
                    <span class="ppb-dot">•</span>
                    <span class="ppb-cat">{{ getSelectedCategoryName() }}</span>
                  </div>
                  <h4 class="ppb-title">{{ form.name || 'Tên sản phẩm mẫu' }}</h4>
                  <div class="ppb-price-box">
                    <span v-if="minPrice > 0" class="ppb-price">
                      {{ minPrice === maxPrice ? `${minPrice.toLocaleString('vi-VN')} đ` : `Từ
                      ${minPrice.toLocaleString('vi-VN')} đ - ${maxPrice.toLocaleString('vi-VN')} đ` }}
                    </span>
                    <span v-else class="ppb-price-placeholder">Chưa cập nhật giá</span>
                    <span class="ppb-stock-badge">Tổng tồn: <b>{{ totalVariantStock }}</b> máy</span>
                  </div>

                  <!-- Active variants preview -->
                  <div v-if="activeVariants.length" class="ppb-variants-section">
                    <div class="ppb-sub-title">Cấu hình đang bán ({{ activeVariants.length }} bản):</div>
                    <div class="ppb-variant-tags">
                      <span v-for="(av, idx) in activeVariants.slice(0, 6)" :key="idx" class="ppb-v-tag">
                        {{ av.ten_bienthe || Object.values(av.attrs).filter(Boolean).join(' / ') }}
                        <b v-if="av.price">({{ (Number(av.price) / 1000000).toFixed(1) }}tr)</b>
                      </span>
                      <span v-if="activeVariants.length > 6" class="ppb-v-more">+{{ activeVariants.length - 6 }} cấu
                        hình khác</span>
                    </div>
                  </div>

                  <!-- Specs preview -->
                  <div v-if="technicalSpecsList.length" class="ppb-specs-section">
                    <div class="ppb-sub-title">Thông số kỹ thuật chung:</div>
                    <div class="ppb-specs-grid">
                      <div v-for="(spec, sidx) in technicalSpecsList.slice(0, 4)" :key="sidx" class="ppb-spec-item">
                        <span class="ppb-spec-k">{{ spec.label }}:</span>
                        <span class="ppb-spec-v">{{ spec.value }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <p v-if="formError" class="form-error">{{ formError }}</p>

        <!-- ─── INLINE FORM FOOTER (WIZARD ACTIONS) ─── -->
        <div class="inline-form-footer">
          <button class="btn-cancel" @click="closeModal">Hủy bỏ</button>
          <div class="footer-nav-buttons">
            <button v-if="activeFormTab !== 'basic'" class="btn-prev-tab" @click="prevTab">← Quay lại bước
              trước</button>
            <button v-if="activeFormTab !== 'description'" class="btn-next-tab" @click="nextTab">
              {{ activeFormTab === 'basic' ? 'Tiếp theo: Thuộc tính & Biến thể →' : 'Tiếp theo: Xem trước đăng bán →' }}
            </button>
            <button v-if="activeFormTab === 'description'" class="btn-submit" @click="submitForm">{{ isEditMode ? 'Lưu thay đổi sản phẩm' : 'Đăng bán sản phẩm'
              }}</button>
          </div>
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
        <div class="modal modal-wide" style="max-width: 720px;">
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
                    <th style="text-align: center;">SL ban đầu</th>
                    <th style="text-align: center;">SL cộng thêm</th>
                    <th style="text-align: center;">SL thực tế</th>
                    <th style="text-align: center;">Thao tác</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="v in selectedLowStockProduct?.bienThes.filter(v => Number(v.soluong ?? 0) < 10)"
                    :key="v.id_bienthe || v.id">
                    <td><b>{{ v.ten_bienthe || 'Biến thể' }}</b></td>
                    <td style="text-align: center;"><b style="color: #ef4444;">{{ v.soluong }}</b></td>
                    <td style="text-align: center;">
                      <input type="number" min="1" placeholder="+0" v-model.number="v._addStock"
                        style="width: 90px; padding: 6px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-weight: 600; text-align: center;"
                        @keyup.enter="updateLowStockVariant(v, selectedLowStockProduct)" />
                    </td>
                    <td style="text-align: center;">
                      <b style="color: #16a34a; font-size: 14px;">
                        {{ Number(v.soluong || 0) + (Number(v._addStock) > 0 ? Number(v._addStock) : 0) }}
                      </b>
                    </td>
                    <td style="text-align: center;">
                      <button class="btn-apply-solid"
                        style="padding: 6px 14px; font-size: 12px; border-radius: 6px; border: none; background: #2563eb; color: white; cursor: pointer; font-weight: 600;"
                        :disabled="updatingLowStockVariantId === (v.id_bienthe || v.id)"
                        @click="updateLowStockVariant(v, selectedLowStockProduct)">
                        {{ updatingLowStockVariantId === (v.id_bienthe || v.id) ? 'Đang lưu...' : 'Cập nhật' }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ==================== MODAL THÊM GIÁ TRỊ THUỘC TÍNH (2 TAB) ==================== -->
    <Teleport to="body">
      <div v-if="showAddAttrValueModal" class="custom-modal-backdrop" @click.self="closeAddAttrValueModal">
        <div class="custom-modal-card attr-value-modal-card">
          <div class="modal-card-header">
            <div class="header-left">
              <h3 class="modal-title-text">Thêm giá trị cho {{ selectedAttrTarget?.label }}</h3>
              <span class="modal-subtitle-text">Bổ sung giá trị tạm thời hoặc tạo giá trị mới cho thuộc tính</span>
            </div>
            <button type="button" class="modal-close-icon-btn" @click="closeAddAttrValueModal">✕</button>
          </div>

          <!-- 2 Tab Navigation Header -->
          <div class="attr-modal-tab-nav">
            <button type="button" class="attr-tab-btn" :class="{ active: activeAttrModalTab === 'other' }" @click="activeAttrModalTab = 'other'">
              <span>Từ danh mục khác</span>
              <small>(Mặc định - Tạm thời)</small>
            </button>
            <button type="button" class="attr-tab-btn" :class="{ active: activeAttrModalTab === 'new' }" @click="activeAttrModalTab = 'new'">
              <span>Tạo giá trị mới</span>
              <small>(Gán cho danh mục hiện tại)</small>
            </button>
          </div>

          <div class="modal-card-body attr-modal-body">
            <!-- TAB 1: TỪ DANH MỤC KHÁC (TẠM THỜI) -->
            <div v-if="activeAttrModalTab === 'other'" class="attr-tab-panel">
              <p class="tab-help-text">
                Tích chọn các giá trị đã có sẵn trên hệ thống thuộc danh mục khác để dùng <b>tạm thời</b> cho sản phẩm này. Dữ liệu danh mục gốc của giá trị trong CSDL sẽ <b>không bị thay đổi</b>.
              </p>

              <div class="temp-val-search-box">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8" />
                  <path d="m21 21-4.35-4.35" />
                </svg>
                <input v-model="searchOtherCategoryVal" placeholder="Tìm kiếm giá trị thuộc tính..." />
              </div>

              <div v-if="otherCategoryOptions.length === 0" class="temp-val-empty">
                <span>Không có giá trị thuộc tính khả dụng nào khác để chọn.</span>
              </div>
              <div v-else class="temp-val-grid">
                <div v-for="opt in otherCategoryOptions" :key="opt.value" class="temp-val-chip" :class="{ selected: selectedOtherCategoryVals.has(opt.value) }" @click="toggleSelectOtherCategoryVal(opt.value)">
                  <span v-if="opt.hex" class="swatch-mini" :style="{ backgroundColor: opt.hex }"></span>
                  <span class="tv-label">{{ opt.label }}</span>
                  <span class="tv-check">{{ selectedOtherCategoryVals.has(opt.value) ? '✓' : '+' }}</span>
                </div>
              </div>
            </div>

            <!-- TAB 2: TẠO GIÁ TRỊ MỚI -->
            <div v-else-if="activeAttrModalTab === 'new'" class="attr-tab-panel">
              <p class="tab-help-text">
                Tạo mới một giá trị thuộc tính hoàn toàn. Dữ liệu mới sẽ được <b>lưu chính thức vào CSDL</b> và tự động liên kết với <b>Danh mục con hiện tại</b> của sản phẩm.
              </p>

              <form @submit.prevent="createNewAttrValue" class="new-val-form-grid">
                <div class="form-group">
                  <label class="form-label">Tên giá trị thuộc tính mới <span class="required">*</span></label>
                  <input v-model="newAttrValForm.giatri" placeholder="Nhập tên giá trị (ví dụ: 24GB, Ryzen 7 9800X3D...)" class="form-input" required />
                </div>

                <div v-if="selectedAttrTarget?.id === 'color-type'" class="form-group">
                  <label class="form-label">Mã màu (Hex Code)</label>
                  <div class="color-picker-row">
                    <input type="color" v-model="newAttrValForm.hex" class="color-picker-input" />
                    <input type="text" v-model="newAttrValForm.hex" placeholder="#2563eb" class="form-input color-hex-text" />
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Modal Footer Actions -->
          <div class="modal-card-footer">
            <button type="button" class="btn-cancel" @click="closeAddAttrValueModal">Hủy bỏ</button>
            
            <button v-if="activeAttrModalTab === 'other'" type="button" class="btn-submit" :disabled="selectedOtherCategoryVals.size === 0" @click="applyOtherCategoryVals">
              Áp dụng {{ selectedOtherCategoryVals.size ? `(${selectedOtherCategoryVals.size})` : '' }} tạm thời
            </button>
            <button v-else type="button" class="btn-submit" :disabled="isSubmittingAttrVal || !newAttrValForm.giatri.trim()" @click="createNewAttrValue">
              {{ isSubmittingAttrVal ? 'Đang tạo...' : 'Tạo mới & Gán vào danh mục' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ==================== MODAL 1: EXCEL IMPORT PREVIEW ==================== -->
    <Teleport to="body">
      <div class="custom-modal-backdrop" v-if="showImportModal" @click.self="showImportModal = false">
        <div class="custom-modal-card xl-width excel-preview-modal-card" style="max-width: 1400px; width: 95vw; height: 88vh; display: flex; flex-direction: column; overflow: hidden;">
          <div class="modal-card-header" style="flex-shrink: 0;">
            <div class="header-left">
              <h3 class="modal-title-text">📊 Bảng Xem Trước & Kiểm Tra Dữ Liệu Excel</h3>
              <span class="modal-subtitle-text">Hệ thống đã đọc {{ importParsedItems.length }} dòng dữ liệu từ file Excel</span>
            </div>
            <button class="modal-close-icon-btn" @click="showImportModal = false">✕</button>
          </div>

          <div class="modal-card-body" style="padding: 20px 24px; flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
            <div class="import-actions-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 12px; flex-wrap: wrap; flex-shrink: 0;">
              <div class="import-stats" style="display: flex; gap: 10px; font-size: 13px; font-weight: 700;">
                <span class="stat-badge-valid" style="padding: 6px 14px; border-radius: 8px;">
                  ✓ Hợp lệ: {{ validImportItemsCount }} dòng
                </span>
                <span class="stat-badge-new-db" style="padding: 6px 14px; border-radius: 8px; background: #fef3c7; color: #b45309; border: 1px solid #fde68a;" v-if="importParsedItems.filter(i => (i.isNewCategory || i.isNewBrand)).length > 0">
                  ⚠️ DM/TH Mới (Sẽ khởi tạo): {{ importParsedItems.filter(i => (i.isNewCategory || i.isNewBrand)).length }} dòng
                </span>
                <span class="stat-badge-invalid" style="padding: 6px 14px; border-radius: 8px;" v-if="importParsedItems.filter(i => !i.isValid).length > 0">
                  ⚠ Có lỗi: {{ importParsedItems.filter(i => !i.isValid).length }} dòng
                </span>
              </div>
              <div style="display: flex; gap: 10px;">
                <button class="btn-magic-ai" @click="autoFetchAllMissingImages" :disabled="isSuggestingImages" style="padding: 8px 16px; background: linear-gradient(135deg, #6366f1, #a855f7); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                  <span v-if="isSuggestingImages" class="spinner-sm"></span>
                  <span v-else>✨</span>
                  Tự động gợi ý ảnh Web (Shopee, CellphoneS, GearVN...)
                </button>
                <button class="btn-magic-ai" @click="openLocalImagePicker(importParsedItems[0])" :disabled="isUploadingExcelImages" style="padding: 8px 16px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                  <span v-if="isUploadingExcelImages" class="spinner-sm"></span>
                  <span v-else>📁</span>
                  Chọn ảnh từ thư mục máy tính
                </button>
              </div>
            </div>

            <!-- Hidden Local File Input for Excel Preview Image Upload -->
            <input type="file" ref="localExcelFileInput" accept="image/*" multiple style="display: none;" @change="onLocalExcelFilesSelected" />

            <div class="preview-table-container" style="flex: 1; min-height: 0; overflow-x: auto; overflow-y: auto; border-radius: 12px;">
              <table class="modern-preview-table" style="min-width: 1420px; width: 100%; border-collapse: collapse; font-size: 12.5px;">
                <thead>
                  <tr style="white-space: nowrap;">
                    <th style="padding: 8px 10px; text-align: center; width: 50px;">STT</th>
                    <th style="padding: 8px 10px; text-align: center; width: 90px;">ID Sản Phẩm</th>
                    <th style="padding: 8px 10px; text-align: left; width: 120px;">Mã SKU</th>
                    <th style="padding: 8px 10px; text-align: left; width: 220px;">Tên sản phẩm & TH</th>
                    <th style="padding: 8px 10px; text-align: left; width: 140px;">Danh mục</th>
                    <th style="padding: 8px 10px; text-align: left; width: 240px;">Thông số kỹ thuật</th>
                    <th style="padding: 8px 10px; text-align: left; width: 220px;">Cấu hình / Biến thể</th>
                    <th style="padding: 8px 10px; text-align: right; width: 120px;">Giá bán</th>
                    <th style="padding: 8px 10px; text-align: center; width: 70px;">Kho</th>
                    <th style="padding: 8px 10px; text-align: center; width: 160px;">Hình ảnh</th>
                    <th style="padding: 8px 10px; text-align: center; width: 100px;">Trạng thái</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in importParsedItems" :key="idx" :class="{'row-valid': item.isValid, 'row-invalid': !item.isValid}">
                    <td style="padding: 6px 8px; text-align: center; font-weight: 700; white-space: nowrap;">{{ item.stt }}</td>
                    <td style="padding: 6px 8px; text-align: center; white-space: nowrap;"><span class="badge-pid">{{ item.product_id }}</span></td>
                    <td style="padding: 6px 10px; font-weight: 600; color: #3b82f6; white-space: nowrap;">{{ item.sku || 'Tự sinh' }}</td>
                    
                    <!-- Cột 4: Tên sản phẩm & Thương hiệu -->
                    <td style="padding: 6px 10px; max-width: 230px;">
                      <div class="cell-product-name" style="font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" :title="item.tenSP">{{ item.tenSP }}</div>
                      <div class="cell-muted-text" style="font-size: 11px; margin-top: 3px; display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                        <span style="font-weight: 600;">TH:</span>
                        <select v-model="item.tenthuonghieu" @change="revalidateItemCategoryBrand(item)" class="select-inline-edit" style="font-size: 11px; padding: 2px 4px; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff; color: #0f172a; cursor: pointer; max-width: 120px;" title="Bấm để đổi Thương hiệu có sẵn trong DB hoặc giữ nguyên">
                          <option value="">-- Chọn TH --</option>
                          <option v-for="b in excelDbBrands" :key="b.id || b.ten_thuonghieu || b.name" :value="b.ten_thuonghieu || b.name">
                            {{ b.ten_thuonghieu || b.name }}
                          </option>
                          <option v-if="item.tenthuonghieu && !excelDbBrands.some(b => (b.ten_thuonghieu||b.name||'').toLowerCase() === item.tenthuonghieu.toLowerCase())" :value="item.tenthuonghieu">
                            ➕ {{ item.tenthuonghieu }} (Tạo mới)
                          </option>
                        </select>
                        <span v-if="item.isNewBrand" class="badge-new-db" style="font-size: 9.5px; background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 1px 6px; border-radius: 4px; font-weight: 800;" title="Thương hiệu chưa có trong DB (sẽ tự động tạo khi bấm nhập)">⚠️ Mới TH</span>
                      </div>
                    </td>

                    <!-- Cột 5: Danh mục -->
                    <td style="padding: 6px 10px; white-space: nowrap;">
                      <div style="display: flex; align-items: center; gap: 4px;">
                        <select v-model="item.ten_danhmuc" @change="revalidateItemCategoryBrand(item)" class="select-inline-edit" style="font-size: 11.5px; font-weight: 600; padding: 3px 6px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #0f172a; cursor: pointer; max-width: 150px;" title="Bấm để đổi Danh mục có sẵn trong DB hoặc giữ nguyên">
                          <option value="">-- Chọn DM --</option>
                          <option v-for="c in excelDbCategories" :key="c.id || c.ten_danhmuc || c.name" :value="c.ten_danhmuc || c.name">
                            {{ c.ten_danhmuc || c.name }}
                          </option>
                          <option v-if="item.ten_danhmuc && !excelDbCategories.some(c => (c.ten_danhmuc||c.name||'').toLowerCase() === item.ten_danhmuc.toLowerCase())" :value="item.ten_danhmuc">
                            ➕ {{ item.ten_danhmuc }} (Tạo mới)
                          </option>
                        </select>
                        <span v-if="item.isNewCategory" class="badge-new-db" style="font-size: 9.5px; background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 1px 6px; border-radius: 4px; font-weight: 800;" title="Danh mục chưa có trong DB (sẽ tự động tạo khi bấm nhập)">⚠️ Mới DM</span>
                      </div>
                    </td>

                    <!-- Cột 6: Thông số kỹ thuật -->
                    <td style="padding: 6px 10px; font-size: 11.5px; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" class="cell-specs-preview" :title="item.thong_so_ky_thuat">
                      {{ item.thong_so_ky_thuat || '—' }}
                    </td>

                    <!-- Cột 7: Cấu hình / Biến thể -->
                    <td style="padding: 6px 10px; font-size: 11.5px; font-weight: 600; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" class="cell-variant-preview" :title="item.ten_bienthe">
                      {{ item.ten_bienthe || '—' }}
                    </td>

                    <!-- Cột 8: Giá bán -->
                    <td style="padding: 6px 10px; text-align: right; font-weight: 700; color: #059669; white-space: nowrap;">{{ item.gia ? item.gia.toLocaleString('vi-VN') + 'đ' : '0đ' }}</td>

                    <!-- Cột 9: Kho -->
                    <td style="padding: 6px 8px; text-align: center; font-weight: 700; white-space: nowrap;">{{ item.soluong }}</td>

                    <!-- Cột 10: Song song Gợi ý ảnh Web (Shopee) & Chọn ảnh từ thư mục máy tính -->
                    <td style="padding: 6px 10px; text-align: center; white-space: nowrap;">
                      <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <!-- Ảnh chính đại diện -->
                        <div v-if="item.anhdaidien" style="position: relative; width: 34px; height: 34px; border: 2px solid #2563eb; border-radius: 6px; overflow: hidden; background: white; cursor: pointer;" @click="openViewImageDetailModal(item)" title="👑 Ảnh đại diện chính">
                          <img :src="item.anhdaidien" style="width: 100%; height: 100%; object-fit: cover;" />
                          <span style="position: absolute; bottom: 0; left: 0; right: 0; background: #2563eb; color: white; font-size: 8px; font-weight: 900; text-align: center; line-height: 10px;">👑</span>
                        </div>

                        <!-- Các ảnh phụ trong Album -->
                        <template v-if="item.hinh_anhs_str">
                          <div v-for="(gUrl, gIdx) in item.hinh_anhs_str.split(/[,;\n\r]+/).map(s=>s.trim()).filter(Boolean).slice(0, 2)" :key="gIdx"
                               style="position: relative; width: 28px; height: 28px; border: 1.5px solid #7c3aed; border-radius: 6px; overflow: hidden; background: white; cursor: pointer;" @click="openViewImageDetailModal(item)" :title="'📸 Ảnh phụ ' + (gIdx + 1)">
                            <img :src="gUrl" style="width: 100%; height: 100%; object-fit: cover;" />
                            <span style="position: absolute; bottom: 0; left: 0; right: 0; background: #7c3aed; color: white; font-size: 7.5px; font-weight: 800; text-align: center; line-height: 9px;">📸</span>
                          </div>
                          <!-- Số lượng ảnh phụ còn lại -->
                          <span v-if="item.hinh_anhs_str.split(/[,;\n\r]+/).map(s=>s.trim()).filter(Boolean).length > 2" @click="openViewImageDetailModal(item)" style="font-size: 10px; font-weight: 800; color: #7c3aed; background: #f5f3ff; border: 1px solid #ddd6fe; padding: 2px 5px; border-radius: 6px; cursor: pointer;">
                            +{{ item.hinh_anhs_str.split(/[,;\n\r]+/).map(s=>s.trim()).filter(Boolean).length - 2 }}
                          </span>
                        </template>

                        <!-- Thẻ trạng thái Chưa có ảnh -->
                        <span v-if="!item.anhdaidien && !item.hinh_anhs_str" style="font-size: 11px; color: #94a3b8; font-style: italic;">Chưa chọn ảnh</span>

                        <!-- Các nút thao tác kết hợp cả Web (Shopee) và Máy tính -->
                        <div style="display: flex; gap: 4px; margin-left: 2px;">
                          <button v-if="item.anhdaidien || item.hinh_anhs_str" class="btn-suggest-img" @click="openViewImageDetailModal(item)" style="padding: 3px 7px; font-size: 11px; background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700; cursor: pointer;" title="Xem chi tiết phân loại Ảnh chính và Album ảnh phụ">
                            👁️ Xem bộ ảnh
                          </button>
                          <button class="btn-suggest-img" @click="openSuggestImages(item)" style="padding: 3px 7px; font-size: 11px; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; border-radius: 6px; font-weight: 700; cursor: pointer;" title="Tìm kiếm & gợi ý ảnh chụp thật từ Shopee / Web">
                            🔍 Gợi ý Web
                          </button>
                          <button class="btn-suggest-img" @click="openLocalImagePicker(item)" :disabled="isUploadingExcelImages" style="padding: 3px 7px; font-size: 11px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 6px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                            <span v-if="isUploadingExcelImages && targetUploadItem === item" class="spinner-sm"></span>
                            <span v-else>📁 Máy tính</span>
                          </button>
                        </div>
                      </div>
                    </td>

                    <!-- Cột 11: Trạng thái -->
                    <td style="padding: 6px 10px; text-align: center; white-space: nowrap;">
                      <span v-if="item.isValid" style="color: #16a34a; font-weight: 700;">✓ Hợp lệ</span>
                      <span v-else style="color: #dc2626; font-weight: 700; font-size: 11px;">⚠ {{ item.errorMessage }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="modal-card-footer" style="display: flex; justify-content: flex-end; gap: 12px; padding: 16px; border-top: 1px solid #e2e8f0;">
            <button class="btn-cancel" @click="showImportModal = false" style="padding: 10px 20px; border-radius: 8px; border: 1.5px solid #cbd5e1; background: white; font-weight: 700; cursor: pointer;">Hủy bỏ</button>
            <button class="btn-confirm-import" @click="confirmBulkImport" :disabled="isSubmittingImport || validImportItemsCount === 0" style="padding: 10px 24px; border-radius: 8px; border: none; background: #2563eb; color: white; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
              <span v-if="isSubmittingImport" class="spinner-sm"></span>
              Xác nhận nhập {{ validImportItemsCount }} sản phẩm hợp lệ
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ==================== MODAL 2: CHI TIẾT BỘ ẢNH CHÍNH & PHỤ ==================== -->
    <Teleport to="body">
      <div class="custom-modal-backdrop" v-if="showImageDetailModal && activeDetailItem" @click.self="showImageDetailModal = false" style="z-index: 99999;">
        <div class="custom-modal-card image-detail-modal-card" style="max-width: 800px; width: 90vw; border-radius: 16px; overflow: hidden;">
          <div class="modal-card-header" style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h3 class="modal-title-text" style="margin: 0; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                🖼️ Chi Tiết Bộ Ảnh Đã Chọn
              </h3>
              <span class="modal-subtitle-text" style="font-size: 12px; font-weight: 600; margin-top: 2px; display: block;">
                Sản phẩm: {{ activeDetailItem.tenSP }} (ID: {{ activeDetailItem.product_id }})
              </span>
            </div>
            <button class="modal-close-btn" @click="showImageDetailModal = false" style="background: none; border: none; font-size: 18px; cursor: pointer; font-weight: 800;">✕</button>
          </div>

          <div class="modal-card-body" style="padding: 20px; max-height: 70vh; overflow-y: auto;">
            <!-- Phần 1: Ảnh đại diện chính (👑) -->
            <div style="margin-bottom: 24px;">
              <h4 style="margin: 0 0 10px 0; font-size: 13.5px; font-weight: 800; color: #3b82f6; display: flex; align-items: center; gap: 6px;">
                👑 Ảnh Đại Diện Chính (anhdaidien)
                <span style="font-size: 11px; font-weight: 600; color: #2563eb; background: rgba(37,99,235,0.12); padding: 2px 8px; border-radius: 6px; border: 1px solid rgba(37,99,235,0.25);">
                  Hiển thị đại diện cho sản phẩm trên website
                </span>
              </h4>

              <div v-if="activeDetailItem.anhdaidien" class="detail-main-img-box" style="display: flex; gap: 16px; align-items: center; padding: 14px; border-radius: 12px; border: 2px solid #2563eb;">
                <div style="width: 100px; height: 100px; border-radius: 8px; overflow: hidden; flex-shrink: 0; background: white;">
                  <img :src="activeDetailItem.anhdaidien" style="width: 100%; height: 100%; object-fit: cover;" />
                </div>
                <div style="flex: 1; overflow: hidden;">
                  <div style="font-size: 12px; font-weight: 700; color: #10b981; margin-bottom: 4px;">✓ Đã thiết lập làm Ảnh Chính</div>
                  <div class="cell-muted-text" style="font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" :title="activeDetailItem.anhdaidien">
                    {{ activeDetailItem.anhdaidien }}
                  </div>
                </div>
              </div>

              <div v-else style="background: rgba(244,63,94,0.12); border: 1px dashed #f43f5e; padding: 14px; border-radius: 12px; text-align: center; color: #f43f5e; font-weight: 700; font-size: 13px;">
                ⚠ Chưa có ảnh chính đại diện! Hãy chọn từ máy tính hoặc bấm "👑 Đặt làm ảnh chính" từ album bên dưới.
              </div>
            </div>

            <!-- Phần 2: Album ảnh phụ (📸) -->
            <div>
              <h4 style="margin: 0 0 10px 0; font-size: 13.5px; font-weight: 800; color: #a855f7; display: flex; align-items: center; gap: 6px;">
                📸 Album Ảnh Phụ Chi Tiết (hinh_anhs_str)
                <span style="font-size: 11px; font-weight: 600; color: #8b5cf6; background: rgba(139,92,246,0.12); padding: 2px 8px; border-radius: 6px; border: 1px solid rgba(139,92,246,0.25);">
                  {{ activeDetailItem.hinh_anhs_str ? activeDetailItem.hinh_anhs_str.split(',').filter(Boolean).length : 0 }} ảnh phụ
                </span>
              </h4>

              <div v-if="activeDetailItem.hinh_anhs_str && activeDetailItem.hinh_anhs_str.split(',').filter(Boolean).length > 0"
                   style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">
                <div v-for="(imgUrl, idx) in activeDetailItem.hinh_anhs_str.split(',').filter(Boolean)" :key="idx"
                     class="detail-album-card" style="border-radius: 10px; padding: 8px; display: flex; flex-direction: column; align-items: center; position: relative;">
                  <div style="width: 100%; height: 90px; border-radius: 6px; overflow: hidden; background: white; margin-bottom: 8px;">
                    <img :src="imgUrl" style="width: 100%; height: 100%; object-fit: cover;" />
                  </div>
                  <span style="font-size: 10px; font-weight: 800; color: #a855f7; margin-bottom: 6px;">Ảnh phụ {{ idx + 1 }}</span>
                  <div style="display: flex; gap: 4px; width: 100%;">
                    <button @click="setItemMainImage(imgUrl)" style="flex: 1; padding: 4px 2px; font-size: 10px; font-weight: 700; background: rgba(37,99,235,0.12); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); border-radius: 4px; cursor: pointer;" title="Đổi ảnh này thành Ảnh Đại Diện Chính">
                      👑 Làm chính
                    </button>
                    <button @click="removeDetailImage(imgUrl)" style="padding: 4px 6px; font-size: 10px; font-weight: 700; background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(248,113,113,0.3); border-radius: 4px; cursor: pointer;" title="Xóa ảnh này khỏi album">
                      🗑️
                    </button>
                  </div>
                </div>
              </div>

              <div v-else class="detail-empty-box" style="border: 1px dashed #475569; padding: 14px; border-radius: 12px; text-align: center; font-weight: 600; font-size: 12.5px;">
                Chưa có ảnh phụ nào trong Album. Bạn có thể chọn nhiều ảnh từ máy tính để thêm vào Album.
              </div>
            </div>
          </div>

          <div class="modal-card-footer" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;">
            <button @click="openLocalImagePicker(activeDetailItem)" :disabled="isUploadingExcelImages" style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 12.5px; display: flex; align-items: center; gap: 6px;">
              📁 Tải thêm ảnh từ máy tính
            </button>
            <button class="btn-cancel" @click="showImageDetailModal = false" style="padding: 8px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 12.5px;">
              ✓ Đã hiểu / Đóng
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ==================== MODAL 3: AUTO IMAGE SUGGESTION ==================== -->
    <Teleport to="body">
      <div class="custom-modal-backdrop" v-if="suggestImageModal" @click.self="suggestImageModal = false">
        <div class="custom-modal-card medium-width image-suggest-modal-card" style="max-width: 650px; width: 90%;">
          <div class="modal-card-header">
            <div class="header-left">
              <h3 class="modal-title-text">🔍 Tìm Kiếm Hình Ảnh Thực Tế Trên Web</h3>
              <span class="modal-subtitle-text">Sản phẩm: <b class="cell-product-name">{{ currentSuggestItem?.tenSP }}</b></span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <button @click="reloadNextImagePage" :disabled="isSuggestingImages" class="btn-web-suggest" style="padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <span v-if="isSuggestingImages" class="spinner-sm"></span>
                <span v-else>🔄</span> Đổi bộ ảnh khác
              </button>
              <button class="modal-close-icon-btn" @click="suggestImageModal = false">✕</button>
            </div>
          </div>

          <div class="modal-card-body" style="padding: 20px;">
            <!-- PINNED SELECTED IMAGES STRIP -->
            <div v-if="selectedMainImage || selectedGalleryImages.length > 0"
                 class="pinned-images-strip" style="margin-bottom: 14px; padding: 10px 14px; border: 1.5px dashed #cbd5e1; border-radius: 10px;">
              <div style="font-size: 12px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                <span class="cell-product-name">📌 Các bức ảnh bạn đã chốt chọn (Giữ nguyên không bị mất khi bấm Đổi bộ ảnh):</span>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span class="cell-muted-text" style="font-size: 11px; font-weight: 600;">Ảnh chính: {{ selectedMainImage ? '1' : '0' }} | Album phụ: {{ selectedGalleryImages.length }}</span>
                  <button @click="selectedMainImage = ''; selectedGalleryImages = []" style="background: rgba(220,38,38,0.15); color: #f87171; border: 1px solid rgba(248,113,113,0.3); padding: 2px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 700; cursor: pointer;" title="Xóa toàn bộ các ảnh đã chọn trên đây để chọn lại từ đầu">
                    🧹 Xóa chọn lại
                  </button>
                </div>
              </div>

              <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <!-- Main image pinned preview -->
                <div v-if="selectedMainImage" style="position: relative; width: 64px; height: 64px; border: 2px solid #2563eb; border-radius: 8px; overflow: hidden; background: white;" title="Ảnh chính đại diện">
                  <img :src="selectedMainImage" style="width: 100%; height: 100%; object-fit: cover;" />
                  <span style="position: absolute; bottom: 0; left: 0; right: 0; background: #2563eb; color: white; font-size: 9px; font-weight: 800; text-align: center;">👑 Chính</span>
                  <button @click="selectedMainImage = ''" title="Bỏ chọn ảnh chính" style="position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
                </div>

                <!-- Gallery images pinned previews -->
                <div v-for="(gUrl, gIdx) in selectedGalleryImages" :key="gIdx" style="position: relative; width: 64px; height: 64px; border: 2px solid #7c3aed; border-radius: 8px; overflow: hidden; background: white;" title="Ảnh phụ Album">
                  <img :src="gUrl" style="width: 100%; height: 100%; object-fit: cover;" />
                  <span style="position: absolute; bottom: 0; left: 0; right: 0; background: #7c3aed; color: white; font-size: 9px; font-weight: 800; text-align: center;">📸 Phụ</span>
                  <button @click="toggleGalleryImage(gUrl)" title="Bỏ ảnh phụ này" style="position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
                </div>
              </div>
            </div>

            <p class="suggest-guide-text cell-muted-text" style="font-size: 13px; margin-bottom: 12px; line-height: 1.5;">
              💡 <b>Lựa chọn linh hoạt</b>: Chọn <b style="color: #3b82f6;">👑 Đặt ảnh chính</b> hoặc <b style="color: #a855f7;">+ Album</b>. Bấm <b style="color: #3b82f6;">🔄 Đổi bộ ảnh khác</b> để xem thêm các góc ảnh mới mà **không làm mất các ảnh bạn đã chốt ở trên**!
            </p>

            <div v-if="isSuggestingImages" class="suggest-loading-box" style="padding: 40px; text-align: center;">
              <div class="spinner-lg" style="margin: 0 auto 12px;"></div>
              <span class="cell-muted-text">Đang quét tìm kiếm bộ ảnh khác nét nhất trên Internet...</span>
            </div>

            <div v-else class="suggested-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
              <div v-for="(imgUrl, iIndex) in suggestedImagesList" :key="iIndex"
                   :class="['suggest-card-box', { isMain: selectedMainImage === imgUrl, isGallery: selectedGalleryImages.includes(imgUrl) }]"
                   style="border-radius: 10px; padding: 8px; position: relative; text-align: center;">
                <img :src="imgUrl" @error="handleImageError($event)" style="width: 100%; height: 110px; object-fit: cover; border-radius: 8px; cursor: pointer;" @click="selectMainImage(imgUrl)" />
                
                <div v-if="selectedMainImage === imgUrl" style="position: absolute; top: 6px; right: 6px; background: #2563eb; color: white; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 20px; box-shadow: 0 2px 6px rgba(37,99,235,0.4);">
                  👑 Ảnh chính
                </div>
                <div v-else-if="selectedGalleryImages.includes(imgUrl)" style="position: absolute; top: 6px; right: 6px; background: #7c3aed; color: white; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 20px; box-shadow: 0 2px 6px rgba(124,58,237,0.4);">
                  📸 Ảnh phụ
                </div>

                <div style="margin-top: 6px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                  <button @click="selectMainImage(imgUrl)"
                          :style="{
                            padding: '3px 8px',
                            fontSize: '11px',
                            fontWeight: '700',
                            borderRadius: '6px',
                            cursor: 'pointer',
                            border: '1px solid #bfdbfe',
                            background: selectedMainImage === imgUrl ? '#2563eb' : '#eff6ff',
                            color: selectedMainImage === imgUrl ? 'white' : '#2563eb'
                          }">
                    {{ selectedMainImage === imgUrl ? '👑 Ảnh chính' : 'Đặt ảnh chính' }}
                  </button>

                  <button @click="toggleGalleryImage(imgUrl)"
                          :style="{
                            padding: '3px 8px',
                            fontSize: '11px',
                            fontWeight: '700',
                            borderRadius: '6px',
                            cursor: 'pointer',
                            border: '1px solid #ddd6fe',
                            background: selectedGalleryImages.includes(imgUrl) ? '#7c3aed' : '#f5f3ff',
                            color: selectedGalleryImages.includes(imgUrl) ? 'white' : '#7c3aed'
                          }">
                    {{ selectedGalleryImages.includes(imgUrl) ? '📸 Bỏ album' : '+ Album' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-card-footer" style="display: flex; justify-content: flex-end; gap: 12px; padding: 16px;">
            <button class="btn-cancel" @click="suggestImageModal = false" style="padding: 8px 18px; border-radius: 8px; font-weight: 700; cursor: pointer;">Hủy</button>
            <button class="btn-confirm-img" @click="confirmImageSelection" style="padding: 8px 20px; border-radius: 8px; border: none; background: #2563eb; color: white; font-weight: 700; cursor: pointer;">✓ Áp dụng bộ ảnh này</button>
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

/* ==================== EXCEL IMPORT & IMAGE SUGGESTION MODALS ==================== */
:global(.custom-modal-backdrop) {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  z-index: 999999 !important;
  background: rgba(15, 23, 42, 0.75) !important;
  backdrop-filter: blur(8px) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 20px !important;
  box-sizing: border-box !important;
}

:global(.custom-modal-card) {
  background: #ffffff;
  color: #0f172a;
  border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-height: 90vh;
  transition: all 0.25s ease;
}

:global(.modal-card-header) {
  padding: 16px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

:global(.modal-title-text) {
  font-size: 17px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 4px 0;
}

:global(.modal-subtitle-text) {
  font-size: 13px;
  color: #64748b;
}

:global(.modal-close-icon-btn) {
  background: transparent;
  border: none;
  font-size: 18px;
  font-weight: 700;
  color: #64748b;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  transition: all 0.15s ease;
}

:global(.modal-close-icon-btn:hover) {
  background: #e2e8f0;
  color: #0f172a;
}

:global(.modal-card-body) {
  padding: 20px 24px;
  overflow-y: auto;
  flex: 1;
}

:global(.modal-card-footer) {
  padding: 16px 24px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
}

/* ==================== DARK MODE OVERRIDES FOR MODALS ==================== */
:global(.theme-dark .custom-modal-card),
:global(body.theme-dark .custom-modal-card),
:global(.admin-layout.theme-dark .custom-modal-card) {
  background: #0f172a !important;
  color: #f8fafc !important;
  border-color: #1e293b !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7) !important;
}

:global(.theme-dark .modal-card-header),
:global(body.theme-dark .modal-card-header),
:global(.admin-layout.theme-dark .modal-card-header),
:global(.theme-dark .modal-card-footer),
:global(body.theme-dark .modal-card-footer),
:global(.admin-layout.theme-dark .modal-card-footer) {
  background: #1e293b !important;
  border-color: #334155 !important;
}

:global(.theme-dark .modal-title-text),
:global(body.theme-dark .modal-title-text),
:global(.admin-layout.theme-dark .modal-title-text) {
  color: #f8fafc !important;
}

:global(.theme-dark .modal-subtitle-text),
:global(body.theme-dark .modal-subtitle-text),
:global(.admin-layout.theme-dark .modal-subtitle-text) {
  color: #94a3b8 !important;
}

:global(.theme-dark .modal-close-icon-btn),
:global(body.theme-dark .modal-close-icon-btn) {
  color: #94a3b8 !important;
}

:global(.theme-dark .preview-table-container),
:global(body.theme-dark .preview-table-container) {
  border-color: #334155 !important;
  background: #0f172a !important;
}

:global(.theme-dark .modern-preview-table thead),
:global(body.theme-dark .modern-preview-table thead) {
  background: #1e293b !important;
}

:global(.theme-dark .modern-preview-table th),
:global(body.theme-dark .modern-preview-table th) {
  color: #94a3b8 !important;
  border-bottom-color: #334155 !important;
  background: #1e293b !important;
}

:global(.theme-dark .modern-preview-table tr),
:global(body.theme-dark .modern-preview-table tr) {
  border-bottom-color: #1e293b !important;
}

:global(.theme-dark .modern-preview-table td),
:global(body.theme-dark .modern-preview-table td) {
  color: #e2e8f0 !important;
}

:global(.theme-dark .suggest-card-box),
:global(body.theme-dark .suggest-card-box) {
  background: #1e293b !important;
  border-color: #334155 !important;
}

:global(.theme-dark .suggest-card-box.isMain),
:global(body.theme-dark .suggest-card-box.isMain) {
  background: #1e1b4b !important;
  border-color: #6366f1 !important;
}

:global(.theme-dark .btn-cancel),
:global(body.theme-dark .btn-cancel) {
  background: #1e293b !important;
  color: #cbd5e1 !important;
  border-color: #334155 !important;
}

/* ─── Inline Form Header ─── */
.inline-form-header {
  margin-bottom: 24px;
  padding-bottom: 16px;
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
  background: #ffffff;
  border: 1px solid #cbd5e1;
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

/* ─── Inline Form Body ─── */
.inline-form-body {
  background: #f8fafc;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 28px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

/* ─── Inline Form Footer ─── */
.inline-form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 28px;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   INLINE FORM â€” Form Elements Redesign
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

/* ─── Wizard Tab Navigation (Light Mode Default) ─── */
.form-wizard-tabs {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 14px;
  margin-bottom: 24px;
  background: #f1f5f9;
  padding: 8px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
}

.wizard-tab-btn {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 18px;
  border-radius: 12px;
  border: 1.5px solid transparent;
  background: transparent;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
  position: relative;
}

.wizard-tab-btn:hover {
  background: rgba(255, 255, 255, 0.6);
}

.wizard-tab-btn.active {
  background: #ffffff;
  border-color: #2563eb;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
}

.tab-step-num {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.tab-step-num.step-success {
  background: #10b981 !important;
  color: #ffffff !important;
  font-weight: 900 !important;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.45) !important;
}

:global(html[data-admin-theme='dark']) .tab-step-num.step-success,
:global(.admin-layout.theme-dark) .tab-step-num.step-success,
:global(.admin-layout.dark) .tab-step-num.step-success,
:global(.dark) .tab-step-num.step-success {
  background: #059669 !important;
  color: #ffffff !important;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.55) !important;
}

.wizard-tab-btn.active .tab-step-num {
  background: #2563eb;
  color: #ffffff;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
}

.wizard-tab-btn.active .tab-step-num.step-success {
  background: #10b981 !important;
  color: #ffffff !important;
}

.tab-text-wrap {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.tab-main-title {
  font-size: 14px;
  font-weight: 700;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: color 0.2s;
}

.wizard-tab-btn.active .tab-main-title {
  color: #0f172a;
}

.tab-sub-title {
  font-size: 11px;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.wizard-tab-btn.active .tab-sub-title {
  color: #2563eb;
}

.tab-error-dot {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #ef4444;
  color: white;
  font-size: 11px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ─── Tab Content Panels ─── */
.tab-content-panel {
  display: flex;
  flex-direction: column;
  gap: 24px;
  animation: fadeIn 0.25s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(4px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tab-grid-2col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  align-items: start;
}

/* ─── Form Cards (Light Mode Default) ─── */
.form-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f1f5f9;
}

.fch-icon {
  font-size: 22px;
}

.form-card-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 2px;
}

.form-card-header p {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}

.label-with-badge {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.sku-hint-badge {
  font-size: 11px;
  color: #2563eb;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 2px 8px;
  border-radius: 6px;
}

/* ─── Static Tree Select (Danh mục dạng cây - Light Mode) ─── */
.tree-select-static-container {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: border-color 0.2s;
}

.tree-select-static-container.has-error {
  border-color: #f87171;
}

.tree-search-wrapper {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 8px 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.search-icon {
  width: 16px;
  height: 16px;
  color: #64748b;
  flex-shrink: 0;
}

.tree-search-input {
  background: #ffffff !important;
  color: #0f172a !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 6px !important;
  padding: 6px 10px !important;
  font-size: 13px !important;
  width: 100% !important;
  outline: none;
}

.clear-search-btn {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 16px;
  cursor: pointer;
  padding: 0 4px;
}

.tree-list-container {
  max-height: 220px;
  overflow-y: auto;
  padding: 6px;
}

.tree-empty {
  padding: 16px;
  text-align: center;
  color: #64748b;
  font-size: 13px;
}

.tree-parent-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  border-radius: 6px;
  color: #1e293b;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}

.tree-parent-row:hover {
  background: #f1f5f9;
}

.tree-toggle-icon {
  font-size: 11px;
  color: #64748b;
  width: 14px;
}

.tree-folder-icon,
.tree-leaf-icon {
  font-size: 14px;
}

.tree-children-list {
  padding-left: 22px;
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-top: 2px;
}

.tree-child-node {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 6px;
  color: #475569;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s;
}

.tree-child-node:hover {
  background: #f1f5f9;
  color: #2563eb;
}

.tree-child-node.selected {
  background: #eff6ff;
  color: #2563eb;
  font-weight: 700;
}

.selected-check {
  margin-left: auto;
  color: #2563eb;
  font-weight: 800;
}

.upload-zone-compact {
  padding: 24px 16px !important;
}

.tier-badge-pill {
  margin-left: auto;
  font-size: 12px;
  color: #1e40af;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 4px 12px;
  border-radius: 20px;
}

.tier-badge-pill.tier-full {
  color: #b45309;
  background: #fffbeb;
  border-color: #fde68a;
}

:global(html[data-admin-theme='dark']) .tier-badge-pill,
:global(.admin-layout.theme-dark) .tier-badge-pill,
:global(.admin-layout.dark) .tier-badge-pill,
:global(.dark) .tier-badge-pill {
  background: rgba(30, 58, 138, 0.45) !important;
  border-color: rgba(96, 165, 250, 0.5) !important;
  color: #93c5fd !important;
}

:global(html[data-admin-theme='dark']) .tier-badge-pill b,
:global(.admin-layout.theme-dark) .tier-badge-pill b,
:global(.admin-layout.dark) .tier-badge-pill b,
:global(.dark) .tier-badge-pill b {
  color: #ffffff !important;
}

:global(html[data-admin-theme='dark']) .tier-badge-pill.tier-full,
:global(.admin-layout.theme-dark) .tier-badge-pill.tier-full,
:global(.admin-layout.dark) .tier-badge-pill.tier-full,
:global(.dark) .tier-badge-pill.tier-full {
  background: rgba(180, 83, 9, 0.45) !important;
  border-color: rgba(251, 191, 36, 0.5) !important;
  color: #fde047 !important;
}

.guide-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  padding: 14px 18px;
  color: #1e40af;
  font-size: 13px;
  line-height: 1.5;
  margin-bottom: 18px;
}

.generate-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 18px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  margin-top: 10px;
}

.gb-summary {
  font-size: 13px;
  color: #334155;
}

.btn-generate-main {
  padding: 9px 18px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-generate-main:hover {
  background: #1d4ed8;
}

/* ─── Matrix Table Card ─── */
.matrix-card {
  margin-top: 8px;
}

.header-right-badges {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 8px;
}

.active-badge {
  font-size: 12px;
  font-weight: 600;
  color: #15803d;
  background: #dcfce7;
  border: 1px solid #86efac;
  padding: 4px 10px;
  border-radius: 8px;
}

.matrix-bulk-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px 16px;
}

.bulk-inputs {
  display: flex;
  align-items: center;
  gap: 10px;
}

.bulk-title {
  font-size: 12.5px;
  font-weight: 700;
  color: #475569;
}

.bulk-input-field {
  width: 140px !important;
  padding: 7px 12px !important;
  font-size: 13px !important;
  border-radius: 8px !important;
  background: #ffffff !important;
  border: 1px solid #cbd5e1 !important;
  color: #0f172a !important;
}

.bulk-quick-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-apply-bulk {
  padding: 7px 14px;
  background: #0f172a;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-apply-bulk:hover {
  background: #1e293b;
}

.btn-quick-toggle {
  padding: 6px 12px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-quick-toggle:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.btn-add-manual {
  padding: 6px 12px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
  cursor: pointer;
}

/* ─── Toggle checkbox in table ─── */
.th-toggle,
.td-toggle {
  width: 48px;
  text-align: center;
}

.toggle-checkbox {
  display: inline-block;
  position: relative;
  cursor: pointer;
  user-select: none;
}

.toggle-checkbox input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

.checkmark {
  display: inline-block;
  width: 20px;
  height: 20px;
  background-color: #e2e8f0;
  border-radius: 6px;
  transition: all 0.2s;
  position: relative;
}

.toggle-checkbox:hover input~.checkmark {
  background-color: #cbd5e1;
}

.toggle-checkbox input:checked~.checkmark {
  background-color: #2563eb;
}

.checkmark:after {
  content: "";
  position: absolute;
  display: none;
  left: 7px;
  top: 3px;
  width: 5px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.toggle-checkbox input:checked~.checkmark:after {
  display: block;
}

.row-disabled {
  opacity: 0.45;
  background: #f8fafc;
}

/* ─── Live Preview Card ─── */
.live-preview-card {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.preview-product-box {
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  background: #ffffff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.ppb-image-wrap {
  position: relative;
  height: 280px;
  background: #ffffff;
  border-radius: 12px;
  margin: 12px 12px 0 12px;
  width: calc(100% - 24px);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 12px;
}

.ppb-image-wrap img {
  width: 100%;
  height: 100%;
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  transform: scale(1.65);
  transition: transform 0.3s ease;
}

.ppb-image-wrap.is-laptop-preview img {
  transform: scale(1.3) !important;
}

.ppb-status-tag {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
}

.ppb-status-tag.active {
  background: #16a34a;
  color: white;
}

.ppb-status-tag.draft {
  background: #64748b;
  color: white;
}

.ppb-details {
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.ppb-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
}

.ppb-brand {
  font-weight: 700;
  color: #2563eb;
}

.ppb-dot {
  color: #94a3b8;
}

.ppb-cat {
  color: #64748b;
}

.ppb-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.4;
}

.ppb-price-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
}

.ppb-price {
  font-size: 16px;
  font-weight: 800;
  color: #15803d;
}

.ppb-price-placeholder {
  font-size: 13px;
  color: #94a3b8;
  font-style: italic;
}

.ppb-stock-badge {
  font-size: 12px;
  color: #166534;
}

.ppb-sub-title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
  margin-bottom: 6px;
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
}

.ppb-variant-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.ppb-v-tag {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  background: #f1f5f9;
  color: #334155;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.ppb-v-more {
  font-size: 11px;
  color: #64748b;
  padding: 3px 6px;
}

.ppb-specs-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
}

.ppb-spec-item {
  font-size: 11.5px;
  background: #f8fafc;
  padding: 4px 8px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
}

.ppb-spec-k {
  color: #64748b;
}

.ppb-spec-v {
  font-weight: 600;
  color: #0f172a;
}

/* ─── Footer Buttons ─── */
.footer-nav-buttons {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-left: auto;
}

.btn-prev-tab {
  padding: 10px 18px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-prev-tab:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.btn-next-tab {
  padding: 10px 20px;
  background: #eff6ff;
  border: 1.5px solid #bfdbfe;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #2563eb;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-next-tab:hover {
  background: #dbeafe;
}

/* 3-column row for basic info fields */
.form-fields-row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 16px;
}

/* Label */
.inline-form-body .form-group label {
  font-size: 11.5px;
  font-weight: 700;
  color: #475569;
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
  background: #ffffff;
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
  padding: 36px 24px;
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
  width: 38px;
  height: 38px;
  color: #3b82f6;
}

.inline-form-body .upload-zone p {
  font-size: 13.5px;
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
  font-size: 11.5px;
  color: #94a3b8;
}

/* Field errors */
.inline-form-body .field-error {
  font-size: 12px;
  color: #f87171;
  margin: 2px 0 0;
}

.inline-form-body .form-error {
  font-size: 13px;
  color: #fca5a5;
  background: rgba(239, 68, 68, 0.15);
  border: 1.5px solid #ef4444;
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

.btn-xl-edit-attrs {
  color: #1d4ed8;
  background: #eff6ff;
  border-color: #bfdbfe;
}

.btn-xl-edit-attrs:hover {
  background: #dbeafe;
  border-color: #93c5fd;
}

:global(html[data-admin-theme='dark']) .btn-xl-edit-attrs,
:global(.admin-layout.theme-dark) .btn-xl-edit-attrs,
:global(.admin-layout.dark) .btn-xl-edit-attrs,
:global(.dark) .btn-xl-edit-attrs {
  background: rgba(30, 58, 138, 0.4) !important;
  color: #93c5fd !important;
  border-color: rgba(96, 165, 250, 0.4) !important;
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

.img-action-buttons {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
}

.img-change-btn {
  padding: 7px 14px;
  border-radius: 7px;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  font-size: 12px;
  font-weight: 600;
  color: #2563eb;
  cursor: pointer;
}

.img-change-btn:hover {
  background: #dbeafe;
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

.parent-tab-btn {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
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
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.parent-tab-btn.active {
  color: #2563eb;
  background: #ffffff;
  border: 1.5px solid #2563eb;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.14);
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
  background: transparent;
  backdrop-filter: none;
  padding: 0;
  border-radius: 12px;
  border: none;
  box-shadow: none;
  width: fit-content;
}

.parent-tab-btn {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
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
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.parent-tab-btn.active {
  color: #2563eb;
  background: #ffffff;
  border: 1.5px solid #2563eb;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.14);
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
  position: static;
  transform: none;
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
  height: 22px;
  /* stops at the horizontal line */
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

.switch-control input:checked+.switch-slider {
  background: linear-gradient(135deg, #2563eb, #2563eb);
}

.switch-control input:checked+.switch-slider:before {
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
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
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

/* ══════════════════════════════════════════════════════════════
   DARK MODE ADAPTIVE OVERRIDES (Giao diện tối tự động)
   ══════════════════════════════════════════════════════════════ */
:global(html[data-admin-theme='dark']) .admin,
:global(.admin-layout.dark) .admin,
:global(.dark) .admin {
  background: transparent !important;
}

:global(html[data-admin-theme='dark']) .inline-form-header,
:global(.admin-layout.dark) .inline-form-header,
:global(.dark) .inline-form-header {
  border-bottom-color: rgba(255, 255, 255, 0.1) !important;
}

:global(html[data-admin-theme='dark']) .inline-form-header h1,
:global(.admin-layout.dark) .inline-form-header h1,
:global(.dark) .inline-form-header h1 {
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .inline-form-header p,
:global(.admin-layout.dark) .inline-form-header p,
:global(.dark) .inline-form-header p {
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .back-btn,
:global(.admin-layout.theme-dark) .back-btn,
:global(.admin-layout.dark) .back-btn,
:global(.dark) .back-btn {
  background: #1e293b !important;
  border-color: #334155 !important;
  color: #f1f5f9 !important;
}

:global(html[data-admin-theme='dark']) .back-btn:hover,
:global(.admin-layout.theme-dark) .back-btn:hover,
:global(.admin-layout.dark) .back-btn:hover,
:global(.dark) .back-btn:hover {
  background: #334155 !important;
  border-color: #475569 !important;
  color: #ffffff !important;
}

:global(html[data-admin-theme='dark']) .inline-form-body,
:global(.admin-layout.dark) .inline-form-body,
:global(.dark) .inline-form-body {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
}

:global(html[data-admin-theme='dark']) .form-wizard-tabs,
:global(.admin-layout.dark) .form-wizard-tabs,
:global(.dark) .form-wizard-tabs {
  background: #0f172a !important;
  border-color: #1e293b !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
}

:global(html[data-admin-theme='dark']) .wizard-tab-btn,
:global(.admin-layout.dark) .wizard-tab-btn,
:global(.dark) .wizard-tab-btn {
  background: #0f172a !important;
  border-color: transparent !important;
}

:global(html[data-admin-theme='dark']) .wizard-tab-btn:hover,
:global(.admin-layout.dark) .wizard-tab-btn:hover,
:global(.dark) .wizard-tab-btn:hover {
  background: rgba(30, 41, 59, 0.8) !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .wizard-tab-btn.active,
:global(.admin-layout.dark) .wizard-tab-btn.active,
:global(.dark) .wizard-tab-btn.active {
  background: #1e293b !important;
  border-color: #3b82f6 !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4) !important;
}

:global(html[data-admin-theme='dark']) .tab-step-num,
:global(.admin-layout.dark) .tab-step-num,
:global(.dark) .tab-step-num {
  background: #334155 !important;
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .wizard-tab-btn.active .tab-step-num,
:global(.admin-layout.dark) .wizard-tab-btn.active .tab-step-num,
:global(.dark) .wizard-tab-btn.active .tab-step-num {
  background: #2563eb !important;
  color: #ffffff !important;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.5) !important;
}

:global(html[data-admin-theme='dark']) .tab-main-title,
:global(.admin-layout.dark) .tab-main-title,
:global(.dark) .tab-main-title {
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .wizard-tab-btn.active .tab-main-title,
:global(.admin-layout.dark) .wizard-tab-btn.active .tab-main-title,
:global(.dark) .wizard-tab-btn.active .tab-main-title {
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .tab-sub-title,
:global(.admin-layout.dark) .tab-sub-title,
:global(.dark) .tab-sub-title {
  color: #64748b !important;
}

:global(html[data-admin-theme='dark']) .wizard-tab-btn.active .tab-sub-title,
:global(.admin-layout.dark) .wizard-tab-btn.active .tab-sub-title,
:global(.dark) .wizard-tab-btn.active .tab-sub-title {
  color: #93c5fd !important;
}

:global(html[data-admin-theme='dark']) .form-card,
:global(.admin-layout.dark) .form-card,
:global(.dark) .form-card {
  background: #111827 !important;
  border-color: #1f2937 !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
}

:global(html[data-admin-theme='dark']) .form-card-header,
:global(.admin-layout.dark) .form-card-header,
:global(.dark) .form-card-header {
  border-bottom-color: #1f2937 !important;
}

:global(html[data-admin-theme='dark']) .form-card-header h3,
:global(.admin-layout.dark) .form-card-header h3,
:global(.dark) .form-card-header h3 {
  color: #f9fafb !important;
}

:global(html[data-admin-theme='dark']) .form-card-header p,
:global(.admin-layout.dark) .form-card-header p,
:global(.dark) .form-card-header p {
  color: #9ca3af !important;
}

:global(html[data-admin-theme='dark']) .sku-hint-badge,
:global(.admin-layout.dark) .sku-hint-badge,
:global(.dark) .sku-hint-badge {
  background: rgba(37, 99, 235, 0.25) !important;
  border-color: #1e40af !important;
  color: #93c5fd !important;
}

:global(html[data-admin-theme='dark']) label,
:global(html[data-admin-theme='dark']) .form-group label,
:global(html[data-admin-theme='dark']) .inline-form-body .form-group label,
:global(.admin-layout.dark) label,
:global(.admin-layout.dark) .form-group label,
:global(.admin-layout.dark) .inline-form-body .form-group label,
:global(.dark) label,
:global(.dark) .form-group label,
:global(.dark) .inline-form-body .form-group label {
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) input:not([type='file']),
:global(html[data-admin-theme='dark']) select,
:global(html[data-admin-theme='dark']) textarea,
:global(html[data-admin-theme='dark']) .inline-form-body .form-group input:not([type='file']),
:global(html[data-admin-theme='dark']) .inline-form-body .form-group select,
:global(html[data-admin-theme='dark']) .inline-form-body .form-group textarea,
:global(.admin-layout.dark) input:not([type='file']),
:global(.admin-layout.dark) select,
:global(.admin-layout.dark) textarea,
:global(.admin-layout.dark) .inline-form-body .form-group input:not([type='file']),
:global(.admin-layout.dark) .inline-form-body .form-group select,
:global(.admin-layout.dark) .inline-form-body .form-group textarea,
:global(.dark) input:not([type='file']),
:global(.dark) select,
:global(.dark) textarea,
:global(.dark) .inline-form-body .form-group input:not([type='file']),
:global(.dark) .inline-form-body .form-group select,
:global(.dark) .inline-form-body .form-group textarea {
  background: #1f2937 !important;
  border-color: #374151 !important;
  color: #f9fafb !important;
}

:global(html[data-admin-theme='dark']) input:focus,
:global(html[data-admin-theme='dark']) select:focus,
:global(html[data-admin-theme='dark']) textarea:focus,
:global(.admin-layout.dark) input:focus,
:global(.admin-layout.dark) select:focus,
:global(.admin-layout.dark) textarea:focus,
:global(.dark) input:focus,
:global(.dark) select:focus,
:global(.dark) textarea:focus {
  background: #111827 !important;
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
}

:global(html[data-admin-theme='dark']) .tree-select-static-container,
:global(.admin-layout.dark) .tree-select-static-container,
:global(.dark) .tree-select-static-container {
  background: #1f2937 !important;
  border-color: #374151 !important;
}

:global(html[data-admin-theme='dark']) .tree-search-wrapper,
:global(.admin-layout.dark) .tree-search-wrapper,
:global(.dark) .tree-search-wrapper {
  background: #1f2937 !important;
  border-bottom-color: #374151 !important;
}

:global(html[data-admin-theme='dark']) .tree-search-input,
:global(.admin-layout.dark) .tree-search-input,
:global(.dark) .tree-search-input {
  background: #111827 !important;
  border-color: #374151 !important;
  color: #f9fafb !important;
}

:global(html[data-admin-theme='dark']) .tree-parent-row,
:global(.admin-layout.dark) .tree-parent-row,
:global(.dark) .tree-parent-row {
  color: #e5e7eb !important;
}

:global(html[data-admin-theme='dark']) .tree-parent-row:hover,
:global(.admin-layout.dark) .tree-parent-row:hover,
:global(.dark) .tree-parent-row:hover {
  background: #374151 !important;
}

:global(html[data-admin-theme='dark']) .tree-child-node,
:global(.admin-layout.dark) .tree-child-node,
:global(.dark) .tree-child-node {
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) .tree-child-node:hover,
:global(.admin-layout.dark) .tree-child-node:hover,
:global(.dark) .tree-child-node:hover {
  background: #374151 !important;
  color: #60a5fa !important;
}

:global(html[data-admin-theme='dark']) .tree-child-node.selected,
:global(.admin-layout.dark) .tree-child-node.selected,
:global(.dark) .tree-child-node.selected {
  background: rgba(37, 99, 235, 0.25) !important;
  color: #60a5fa !important;
}

:global(html[data-admin-theme='dark']) .upload-zone,
:global(html[data-admin-theme='dark']) .inline-form-body .upload-zone,
:global(.admin-layout.dark) .upload-zone,
:global(.admin-layout.dark) .inline-form-body .upload-zone,
:global(.dark) .upload-zone,
:global(.dark) .inline-form-body .upload-zone {
  background: #111827 !important;
  border-color: #374151 !important;
}

:global(html[data-admin-theme='dark']) .upload-zone:hover,
:global(html[data-admin-theme='dark']) .inline-form-body .upload-zone:hover,
:global(.admin-layout.dark) .upload-zone:hover,
:global(.admin-layout.dark) .inline-form-body .upload-zone:hover,
:global(.dark) .upload-zone:hover,
:global(.dark) .inline-form-body .upload-zone:hover {
  background: #1e293b !important;
  border-color: #3b82f6 !important;
}

:global(html[data-admin-theme='dark']) .upload-zone p,
:global(html[data-admin-theme='dark']) .inline-form-body .upload-zone p,
:global(.admin-layout.dark) .upload-zone p,
:global(.admin-layout.dark) .inline-form-body .upload-zone p,
:global(.dark) .upload-zone p,
:global(.dark) .inline-form-body .upload-zone p {
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) .upload-zone p span,
:global(html[data-admin-theme='dark']) .inline-form-body .upload-zone p span,
:global(.admin-layout.dark) .upload-zone p span,
:global(.admin-layout.dark) .inline-form-body .upload-zone p span,
:global(.dark) .upload-zone p span,
:global(.dark) .inline-form-body .upload-zone p span {
  color: #60a5fa !important;
}

:global(html[data-admin-theme='dark']) .upload-zone small,
:global(html[data-admin-theme='dark']) .inline-form-body .upload-zone small,
:global(.admin-layout.dark) .upload-zone small,
:global(.admin-layout.dark) .inline-form-body .upload-zone small,
:global(.dark) .upload-zone small,
:global(.dark) .inline-form-body .upload-zone small {
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .guide-banner,
:global(.admin-layout.dark) .guide-banner,
:global(.dark) .guide-banner {
  background: rgba(30, 58, 138, 0.25) !important;
  border-color: #1e3a8a !important;
  color: #93c5fd !important;
}

:global(html[data-admin-theme='dark']) .accordion-item,
:global(.admin-layout.dark) .accordion-item,
:global(.dark) .accordion-item {
  background: #111827 !important;
  border-color: #1f2937 !important;
}

:global(html[data-admin-theme='dark']) .accordion-header,
:global(.admin-layout.dark) .accordion-header,
:global(.dark) .accordion-header {
  background: #1e293b !important;
  color: #f9fafb !important;
}

:global(html[data-admin-theme='dark']) .accordion-header:hover,
:global(.admin-layout.dark) .accordion-header:hover,
:global(.dark) .accordion-header:hover {
  background: #334155 !important;
}

:global(html[data-admin-theme='dark']) .accordion-name,
:global(.admin-layout.dark) .accordion-name,
:global(.dark) .accordion-name {
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .accordion-body,
:global(.admin-layout.dark) .accordion-body,
:global(.dark) .accordion-body {
  background: #111827 !important;
}

:global(html[data-admin-theme='dark']) .mode-switch-wrapper,
:global(.admin-layout.dark) .mode-switch-wrapper,
:global(.dark) .mode-switch-wrapper {
  background: #0f172a !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .color-swatch-btn,
:global(.admin-layout.dark) .color-swatch-btn,
:global(.dark) .color-swatch-btn {
  background: #0f172a !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .swatch-label,
:global(.admin-layout.dark) .swatch-label,
:global(.dark) .swatch-label {
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) .color-swatch-btn.selected,
:global(.admin-layout.dark) .color-swatch-btn.selected,
:global(.dark) .color-swatch-btn.selected {
  background: #1e3a8a !important;
  border-color: #3b82f6 !important;
}

:global(html[data-admin-theme='dark']) .matrix-bulk-toolbar,
:global(.admin-layout.dark) .matrix-bulk-toolbar,
:global(.dark) .matrix-bulk-toolbar {
  background: #1e293b !important;
  border-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .bulk-title,
:global(.admin-layout.dark) .bulk-title,
:global(.dark) .bulk-title {
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) .bulk-input-field,
:global(.admin-layout.dark) .bulk-input-field,
:global(.dark) .bulk-input-field {
  background: #111827 !important;
  border-color: #475569 !important;
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .btn-quick-toggle,
:global(.admin-layout.dark) .btn-quick-toggle,
:global(.dark) .btn-quick-toggle {
  background: #334155 !important;
  border-color: #475569 !important;
  color: #f1f5f9 !important;
}

:global(html[data-admin-theme='dark']) .back-btn,
:global(.admin-layout.theme-dark) .back-btn,
:global(.admin-layout.dark) .back-btn,
:global(.dark) .back-btn,
:global(html[data-admin-theme='dark']) .btn-prev-tab,
:global(.admin-layout.theme-dark) .btn-prev-tab,
:global(.admin-layout.dark) .btn-prev-tab,
:global(.dark) .btn-prev-tab {
  background: #181d24 !important;
  border: 1px solid #334155 !important;
  color: #cbd5e1 !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
}

:global(html[data-admin-theme='dark']) .back-btn:hover,
:global(.admin-layout.theme-dark) .back-btn:hover,
:global(.admin-layout.dark) .back-btn:hover,
:global(.dark) .back-btn:hover,
:global(html[data-admin-theme='dark']) .btn-prev-tab:hover,
:global(.admin-layout.theme-dark) .btn-prev-tab:hover,
:global(.admin-layout.dark) .btn-prev-tab:hover,
:global(.dark) .btn-prev-tab:hover {
  background: #202632 !important;
  border-color: #60a5fa !important;
  color: #60a5fa !important;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2) !important;
}

:global(html[data-admin-theme='dark']) .vt-table th,
:global(.admin-layout.dark) .vt-table th,
:global(.dark) .vt-table th {
  background: #1e293b !important;
  color: #94a3b8 !important;
  border-bottom-color: #334155 !important;
}

:global(html[data-admin-theme='dark']) .vt-table td,
:global(.admin-layout.dark) .vt-table td,
:global(.dark) .vt-table td {
  border-bottom-color: #1f2937 !important;
  color: #f1f5f9 !important;
}

:global(html[data-admin-theme='dark']) .vt-input,
:global(.admin-layout.dark) .vt-input,
:global(.dark) .vt-input {
  background: #111827 !important;
  border-color: #374151 !important;
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .live-preview-card,
:global(.admin-layout.theme-dark) .live-preview-card,
:global(.admin-layout.dark) .live-preview-card,
:global(.dark) .live-preview-card {
  background: #171b20 !important;
  border-color: #2e3540 !important;
}

:global(html[data-admin-theme='dark']) .preview-product-box,
:global(.admin-layout.theme-dark) .preview-product-box,
:global(.admin-layout.dark) .preview-product-box,
:global(.dark) .preview-product-box {
  background: #1e242d !important;
  border-color: #333c4b !important;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3) !important;
}

:global(html[data-admin-theme='dark']) .ppb-image-wrap,
:global(.admin-layout.theme-dark) .ppb-image-wrap,
:global(.admin-layout.dark) .ppb-image-wrap,
:global(.dark) .ppb-image-wrap {
  background: #121519 !important;
}

:global(html[data-admin-theme='dark']) .ppb-title,
:global(.admin-layout.theme-dark) .ppb-title,
:global(.admin-layout.dark) .ppb-title,
:global(.dark) .ppb-title {
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .ppb-brand,
:global(.admin-layout.theme-dark) .ppb-brand,
:global(.admin-layout.dark) .ppb-brand,
:global(.dark) .ppb-brand {
  color: #60a5fa !important;
}

:global(html[data-admin-theme='dark']) .ppb-cat,
:global(.admin-layout.theme-dark) .ppb-cat,
:global(.admin-layout.dark) .ppb-cat,
:global(.dark) .ppb-cat {
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .ppb-price-box,
:global(.admin-layout.theme-dark) .ppb-price-box,
:global(.admin-layout.dark) .ppb-price-box,
:global(.dark) .ppb-price-box {
  background: rgba(16, 185, 129, 0.12) !important;
  border-color: rgba(16, 185, 129, 0.3) !important;
}

:global(html[data-admin-theme='dark']) .ppb-price,
:global(.admin-layout.theme-dark) .ppb-price,
:global(.admin-layout.dark) .ppb-price,
:global(.dark) .ppb-price {
  color: #34d399 !important;
}

:global(html[data-admin-theme='dark']) .ppb-stock-badge,
:global(.admin-layout.theme-dark) .ppb-stock-badge,
:global(.admin-layout.dark) .ppb-stock-badge,
:global(.dark) .ppb-stock-badge {
  color: #6ee7b7 !important;
}

:global(html[data-admin-theme='dark']) .ppb-stock-badge b,
:global(.admin-layout.theme-dark) .ppb-stock-badge b {
  color: #a7f3d0 !important;
}

:global(html[data-admin-theme='dark']) .ppb-sub-title,
:global(.admin-layout.theme-dark) .ppb-sub-title,
:global(.admin-layout.dark) .ppb-sub-title,
:global(.dark) .ppb-sub-title {
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) .ppb-v-tag,
:global(.admin-layout.theme-dark) .ppb-v-tag,
:global(.admin-layout.dark) .ppb-v-tag,
:global(.dark) .ppb-v-tag {
  background: #28303d !important;
  border-color: #3d4756 !important;
  color: #e2e8f0 !important;
}

:global(html[data-admin-theme='dark']) .ppb-v-tag b,
:global(.admin-layout.theme-dark) .ppb-v-tag b {
  color: #60a5fa !important;
}

:global(html[data-admin-theme='dark']) .ppb-v-more,
:global(.admin-layout.theme-dark) .ppb-v-more {
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .ppb-spec-item,
:global(.admin-layout.theme-dark) .ppb-spec-item,
:global(.admin-layout.dark) .ppb-spec-item,
:global(.dark) .ppb-spec-item {
  background: #242c38 !important;
  border-color: #364152 !important;
}

:global(html[data-admin-theme='dark']) .ppb-spec-k,
:global(.admin-layout.theme-dark) .ppb-spec-k,
:global(.admin-layout.dark) .ppb-spec-k,
:global(.dark) .ppb-spec-k {
  color: #94a3b8 !important;
}

:global(html[data-admin-theme='dark']) .ppb-spec-v,
:global(.admin-layout.theme-dark) .ppb-spec-v,
:global(.admin-layout.dark) .ppb-spec-v,
:global(.dark) .ppb-spec-v {
  color: #f8fafc !important;
}

:global(html[data-admin-theme='dark']) .inline-form-footer,
:global(.admin-layout.theme-dark) .inline-form-footer,
:global(.admin-layout.dark) .inline-form-footer,
:global(.dark) .inline-form-footer {
  border-top-color: #1e293b !important;
}

:global(html[data-admin-theme='dark']) .btn-cancel,
:global(.admin-layout.theme-dark) .btn-cancel,
:global(.admin-layout.dark) .btn-cancel,
:global(.dark) .btn-cancel {
  background: #242c38 !important;
  border-color: #364152 !important;
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) .btn-prev-tab,
:global(.admin-layout.theme-dark) .btn-prev-tab,
:global(.admin-layout.dark) .btn-prev-tab,
:global(.dark) .btn-prev-tab {
  background: #242c38 !important;
  border-color: #364152 !important;
  color: #cbd5e1 !important;
}

:global(html[data-admin-theme='dark']) .btn-prev-tab:hover,
:global(.admin-layout.theme-dark) .btn-prev-tab:hover,
:global(.admin-layout.dark) .btn-prev-tab:hover,
:global(.dark) .btn-prev-tab:hover {
  background: #2d3748 !important;
  color: #ffffff !important;
}

:global(html[data-admin-theme='dark']) .btn-next-tab,
:global(.admin-layout.dark) .btn-next-tab,
:global(.dark) .btn-next-tab {
  background: #1e3a8a !important;
  border-color: #2563eb !important;
  color: #93c5fd !important;
}

:global(html[data-admin-theme='dark']) .btn-next-tab:hover,
:global(.admin-layout.dark) .btn-next-tab:hover,
:global(.dark) .btn-next-tab:hover {
  background: #2563eb !important;
  color: #ffffff !important;
}
</style>

<!-- ══════════════════════════════════════════════════════════════
     UNSCOPED DARK MODE OVERRIDES (Đảm bảo áp dụng 100% trên trình duyệt)
     ══════════════════════════════════════════════════════════════ -->
<style>
/* Loại bỏ khung viền ngoài kép (double border) cho form ở cả Chế độ Sáng và Tối */
.admin:has(.inline-form-header),
.admin:has(.form-wizard-tabs) {
  background: transparent !important;
  background-color: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
}

.inline-form-body {
  background: transparent !important;
  background-color: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
}

.inline-form-header {
  background: transparent !important;
  background-color: transparent !important;
  border: none !important;
}

html[data-admin-theme='dark'] .admin,
.admin-layout.dark .admin,
.admin-layout.theme-dark .admin,
.dark .admin,
.theme-dark .admin {
  background: transparent !important;
  background-color: transparent !important;
  box-shadow: none !important;
}

html[data-admin-theme='dark'] .inline-form-body,
.admin-layout.dark .inline-form-body,
.admin-layout.theme-dark .inline-form-body,
.dark .inline-form-body,
.theme-dark .inline-form-body {
  background: transparent !important;
  background-color: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
}

html[data-admin-theme='dark'] .inline-form-header,
.admin-layout.dark .inline-form-header,
.admin-layout.theme-dark .inline-form-header,
.dark .inline-form-header,
.theme-dark .inline-form-header {
  background: transparent !important;
  background-color: transparent !important;
}

html[data-admin-theme='dark'] .form-wizard-tabs,
.admin-layout.dark .form-wizard-tabs,
.admin-layout.theme-dark .form-wizard-tabs,
.dark .form-wizard-tabs,
.theme-dark .form-wizard-tabs {
  background: #0f172a !important;
  border-color: #1e293b !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
}

html[data-admin-theme='dark'] .wizard-tab-btn,
.admin-layout.dark .wizard-tab-btn,
.admin-layout.theme-dark .wizard-tab-btn,
.dark .wizard-tab-btn,
.theme-dark .wizard-tab-btn {
  background: #0f172a !important;
  border-color: transparent !important;
}

html[data-admin-theme='dark'] .wizard-tab-btn.active,
.admin-layout.dark .wizard-tab-btn.active,
.admin-layout.theme-dark .wizard-tab-btn.active,
.dark .wizard-tab-btn.active,
.theme-dark .wizard-tab-btn.active {
  background: #1e293b !important;
  border-color: #3b82f6 !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4) !important;
}

html[data-admin-theme='dark'] .tab-main-title,
.admin-layout.dark .tab-main-title,
.admin-layout.theme-dark .tab-main-title,
.dark .tab-main-title,
.theme-dark .tab-main-title {
  color: #e2e8f0 !important;
}

html[data-admin-theme='dark'] .wizard-tab-btn.active .tab-main-title,
.admin-layout.dark .wizard-tab-btn.active .tab-main-title,
.admin-layout.theme-dark .wizard-tab-btn.active .tab-main-title,
.dark .wizard-tab-btn.active .tab-main-title,
.theme-dark .wizard-tab-btn.active .tab-main-title {
  color: #f8fafc !important;
}

html[data-admin-theme='dark'] .tab-sub-title,
.admin-layout.dark .tab-sub-title,
.admin-layout.theme-dark .tab-sub-title,
.dark .tab-sub-title,
.theme-dark .tab-sub-title {
  color: #94a3b8 !important;
}

html[data-admin-theme='dark'] .wizard-tab-btn.active .tab-sub-title,
.admin-layout.dark .wizard-tab-btn.active .tab-sub-title,
.admin-layout.theme-dark .wizard-tab-btn.active .tab-sub-title,
.dark .wizard-tab-btn.active .tab-sub-title,
.theme-dark .wizard-tab-btn.active .tab-sub-title {
  color: #93c5fd !important;
}

html[data-admin-theme='dark'] .tree-select-static-container,
.admin-layout.dark .tree-select-static-container,
.admin-layout.theme-dark .tree-select-static-container,
.dark .tree-select-static-container,
.theme-dark .tree-select-static-container {
  background: #1f2937 !important;
  border-color: #374151 !important;
}

html[data-admin-theme='dark'] .tree-search-wrapper,
.admin-layout.dark .tree-search-wrapper,
.admin-layout.theme-dark .tree-search-wrapper,
.dark .tree-search-wrapper,
.theme-dark .tree-search-wrapper {
  background: #1f2937 !important;
  border-bottom-color: #374151 !important;
}

html[data-admin-theme='dark'] .tree-search-input,
.admin-layout.dark .tree-search-input,
.admin-layout.theme-dark .tree-search-input,
.dark .tree-search-input,
.theme-dark .tree-search-input {
  background: #111827 !important;
  border-color: #374151 !important;
  color: #f9fafb !important;
}

html[data-admin-theme='dark'] .tree-search-input::placeholder,
.admin-layout.dark .tree-search-input::placeholder,
.admin-layout.theme-dark .tree-search-input::placeholder,
.dark .tree-search-input::placeholder,
.theme-dark .tree-search-input::placeholder {
  color: #94a3b8 !important;
}

html[data-admin-theme='dark'] .tree-parent-row,
.admin-layout.dark .tree-parent-row,
.admin-layout.theme-dark .tree-parent-row,
.dark .tree-parent-row,
.theme-dark .tree-parent-row,
html[data-admin-theme='dark'] .tree-parent-name,
.admin-layout.dark .tree-parent-name,
.admin-layout.theme-dark .tree-parent-name,
.dark .tree-parent-name,
.theme-dark .tree-parent-name {
  color: #f8fafc !important;
}

html[data-admin-theme='dark'] .tree-parent-row:hover,
.admin-layout.dark .tree-parent-row:hover,
.admin-layout.theme-dark .tree-parent-row:hover,
.dark .tree-parent-row:hover,
.theme-dark .tree-parent-row:hover {
  background: #1e293b !important;
  color: #60a5fa !important;
}

html[data-admin-theme='dark'] .tree-child-node,
.admin-layout.dark .tree-child-node,
.admin-layout.theme-dark .tree-child-node,
.dark .tree-child-node,
.theme-dark .tree-child-node,
html[data-admin-theme='dark'] .tree-child-name,
.admin-layout.dark .tree-child-name,
.admin-layout.theme-dark .tree-child-name,
.dark .tree-child-name,
.theme-dark .tree-child-name {
  color: #e2e8f0 !important;
}

html[data-admin-theme='dark'] .tree-child-node:hover,
.admin-layout.dark .tree-child-node:hover,
.admin-layout.theme-dark .tree-child-node:hover,
.dark .tree-child-node:hover,
.theme-dark .tree-child-node:hover {
  background: #1e293b !important;
  color: #60a5fa !important;
}

html[data-admin-theme='dark'] .tree-child-node.selected,
.admin-layout.dark .tree-child-node.selected,
.admin-layout.theme-dark .tree-child-node.selected,
.dark .tree-child-node.selected,
.theme-dark .tree-child-node.selected,
html[data-admin-theme='dark'] .tree-parent-row.selected,
.admin-layout.dark .tree-parent-row.selected,
.admin-layout.theme-dark .tree-parent-row.selected,
.dark .tree-parent-row.selected,
.theme-dark .tree-parent-row.selected {
  background: #1e3a8a !important;
  border-color: #2563eb !important;
  color: #ffffff !important;
}

html[data-admin-theme='dark'] .tree-child-node.selected .tree-child-name,
.admin-layout.dark .tree-child-node.selected .tree-child-name,
.admin-layout.theme-dark .tree-child-node.selected .tree-child-name,
.dark .tree-child-node.selected .tree-child-name,
.theme-dark .tree-child-node.selected .tree-child-name,
html[data-admin-theme='dark'] .tree-parent-row.selected .tree-parent-name,
.admin-layout.dark .tree-parent-row.selected .tree-parent-name,
.admin-layout.theme-dark .tree-parent-row.selected .tree-parent-name,
.dark .tree-parent-row.selected .tree-parent-name,
.theme-dark .tree-parent-row.selected .tree-parent-name {
  color: #ffffff !important;
  font-weight: 700 !important;
}

html[data-admin-theme='dark'] .selected-check,
.admin-layout.dark .selected-check,
.admin-layout.theme-dark .selected-check,
.dark .selected-check,
.theme-dark .selected-check {
  color: #60a5fa !important;
  font-weight: 800 !important;
}

html[data-admin-theme='dark'] .tree-toggle-icon,
.admin-layout.dark .tree-toggle-icon,
.admin-layout.theme-dark .tree-toggle-icon,
.dark .tree-toggle-icon,
.theme-dark .tree-toggle-icon {
  color: #cbd5e1 !important;
}

html[data-admin-theme='dark'] .selected-category-badge,
.admin-layout.dark .selected-category-badge,
.admin-layout.theme-dark .selected-category-badge,
.dark .selected-category-badge,
.theme-dark .selected-category-badge {
  background: #1f2937 !important;
  border-color: #374151 !important;
  color: #cbd5e1 !important;
}

html[data-admin-theme='dark'] .sku-hint-badge,
.admin-layout.dark .sku-hint-badge,
.admin-layout.theme-dark .sku-hint-badge,
.dark .sku-hint-badge,
.theme-dark .sku-hint-badge {
  background: rgba(37, 99, 235, 0.25) !important;
  border-color: #1e40af !important;
  color: #93c5fd !important;
}

html[data-admin-theme='dark'] .btn-next-tab,
.admin-layout.dark .btn-next-tab,
.admin-layout.theme-dark .btn-next-tab,
.dark .btn-next-tab,
.theme-dark .btn-next-tab {
  background: #1e3a8a !important;
  border-color: #2563eb !important;
  color: #93c5fd !important;
}

html[data-admin-theme='dark'] .img-preview-wrap,
.admin-layout.dark .img-preview-wrap,
.admin-layout.theme-dark .img-preview-wrap,
.dark .img-preview-wrap,
.theme-dark .img-preview-wrap {
  background: #1f2937 !important;
  border-color: #374151 !important;
}

html[data-admin-theme='dark'] .img-change-btn,
html[data-admin-theme='dark'] .img-change,
.admin-layout.dark .img-change-btn,
.admin-layout.dark .img-change,
.admin-layout.theme-dark .img-change-btn,
.admin-layout.theme-dark .img-change,
.dark .img-change-btn,
.dark .img-change,
.theme-dark .img-change-btn,
.theme-dark .img-change {
  background: #111827 !important;
  border-color: #374151 !important;
  color: #60a5fa !important;
}

html[data-admin-theme='dark'] .img-change-btn:hover,
html[data-admin-theme='dark'] .img-change:hover,
.admin-layout.dark .img-change-btn:hover,
.admin-layout.dark .img-change:hover,
.admin-layout.theme-dark .img-change-btn:hover,
.admin-layout.theme-dark .img-change:hover,
.dark .img-change-btn:hover,
.dark .img-change:hover,
.theme-dark .img-change-btn:hover,
.theme-dark .img-change:hover {
  background: #1e293b !important;
  border-color: #3b82f6 !important;
}

html[data-admin-theme='dark'] .img-remove-btn,
html[data-admin-theme='dark'] .img-remove,
.admin-layout.dark .img-remove-btn,
.admin-layout.dark .img-remove,
.admin-layout.theme-dark .img-remove-btn,
.admin-layout.theme-dark .img-remove,
.dark .img-remove-btn,
.dark .img-remove,
.theme-dark .img-remove-btn,
.theme-dark .img-remove {
  background: rgba(239, 68, 68, 0.15) !important;
  border-color: rgba(239, 68, 68, 0.3) !important;
  color: #f87171 !important;
}

html[data-admin-theme='dark'] .extra-img-item,
.admin-layout.dark .extra-img-item,
.admin-layout.theme-dark .extra-img-item,
.dark .extra-img-item,
.theme-dark .extra-img-item {
  background: #1f2937 !important;
  border-color: #374151 !important;
}

/* ─── TAB 2: THUỘC TÍNH & BIẾN THỂ BÁN (DARK MODE OVERRIDES) ─── */
html[data-admin-theme='dark'] .guide-banner,
.admin-layout.dark .guide-banner,
.admin-layout.theme-dark .guide-banner,
.dark .guide-banner,
.theme-dark .guide-banner {
  background: rgba(30, 58, 138, 0.3) !important;
  border-color: #1e3a8a !important;
  color: #93c5fd !important;
}

html[data-admin-theme='dark'] .accordion-item,
.admin-layout.dark .accordion-item,
.admin-layout.theme-dark .accordion-item,
.dark .accordion-item,
.theme-dark .accordion-item {
  background: #111827 !important;
  border-color: #1f2937 !important;
}

html[data-admin-theme='dark'] .accordion-item.is-open,
.admin-layout.dark .accordion-item.is-open,
.admin-layout.theme-dark .accordion-item.is-open,
.dark .accordion-item.is-open,
.theme-dark .accordion-item.is-open {
  background: #111827 !important;
  border-color: #374151 !important;
}

html[data-admin-theme='dark'] .accordion-header,
.admin-layout.dark .accordion-header,
.admin-layout.theme-dark .accordion-header,
.dark .accordion-header,
.theme-dark .accordion-header,
html[data-admin-theme='dark'] .accordion-item.is-open .accordion-header,
.admin-layout.dark .accordion-item.is-open .accordion-header,
.admin-layout.theme-dark .accordion-item.is-open .accordion-header,
.dark .accordion-item.is-open .accordion-header,
.theme-dark .accordion-item.is-open .accordion-header {
  background: #1e293b !important;
  border-bottom-color: #334155 !important;
  color: #f8fafc !important;
}

html[data-admin-theme='dark'] .accordion-header:hover,
.admin-layout.dark .accordion-header:hover,
.admin-layout.theme-dark .accordion-header:hover,
.dark .accordion-header:hover,
.theme-dark .accordion-header:hover {
  background: #334155 !important;
}

html[data-admin-theme='dark'] .accordion-name,
.admin-layout.dark .accordion-name,
.admin-layout.theme-dark .accordion-name,
.dark .accordion-name,
.theme-dark .accordion-name {
  color: #f8fafc !important;
}

html[data-admin-theme='dark'] .accordion-body,
.admin-layout.dark .accordion-body,
.admin-layout.theme-dark .accordion-body,
.dark .accordion-body,
.theme-dark .accordion-body {
  background: #111827 !important;
}

html[data-admin-theme='dark'] .flat-select-table,
.admin-layout.dark .flat-select-table,
.admin-layout.theme-dark .flat-select-table,
.dark .flat-select-table,
.theme-dark .flat-select-table {
  background: #111827 !important;
  border-color: #1f2937 !important;
}

html[data-admin-theme='dark'] .fst-row,
.admin-layout.dark .fst-row,
.admin-layout.theme-dark .fst-row,
.dark .fst-row,
.theme-dark .fst-row {
  background: #111827 !important;
  border-bottom-color: #1f2937 !important;
}

html[data-admin-theme='dark'] .fst-row.is-variant-tier,
.admin-layout.dark .fst-row.is-variant-tier,
.admin-layout.theme-dark .fst-row.is-variant-tier,
.dark .fst-row.is-variant-tier,
.theme-dark .fst-row.is-variant-tier {
  background: rgba(30, 58, 138, 0.2) !important;
}

html[data-admin-theme='dark'] .mode-switch-wrapper,
.admin-layout.dark .mode-switch-wrapper,
.admin-layout.theme-dark .mode-switch-wrapper,
.dark .mode-switch-wrapper,
.theme-dark .mode-switch-wrapper {
  background: #0f172a !important;
  border-color: #334155 !important;
}

html[data-admin-theme='dark'] .mode-label,
.admin-layout.dark .mode-label,
.admin-layout.theme-dark .mode-label,
.dark .mode-label,
.theme-dark .mode-label {
  color: #94a3b8 !important;
}

html[data-admin-theme='dark'] .mode-label.active,
.admin-layout.dark .mode-label.active,
.admin-layout.theme-dark .mode-label.active,
.dark .mode-label.active,
.theme-dark .mode-label.active {
  color: #60a5fa !important;
}

html[data-admin-theme='dark'] .vbtn,
.admin-layout.dark .vbtn,
.admin-layout.theme-dark .vbtn,
.dark .vbtn,
.theme-dark .vbtn {
  background: #1f2937 !important;
  border-color: #374151 !important;
  color: #e2e8f0 !important;
}

html[data-admin-theme='dark'] .vbtn:hover,
.admin-layout.dark .vbtn:hover,
.admin-layout.theme-dark .vbtn:hover,
.dark .vbtn:hover,
.theme-dark .vbtn:hover {
  background: #374151 !important;
  border-color: #3b82f6 !important;
}

html[data-admin-theme='dark'] .vbtn.vbtn-on,
.admin-layout.dark .vbtn.vbtn-on,
.admin-layout.theme-dark .vbtn.vbtn-on,
.dark .vbtn.vbtn-on,
.theme-dark .vbtn.vbtn-on {
  background: #1e3a8a !important;
  border-color: #3b82f6 !important;
  color: #ffffff !important;
}

html[data-admin-theme='dark'] .color-swatch-btn,
.admin-layout.dark .color-swatch-btn,
.admin-layout.theme-dark .color-swatch-btn,
.dark .color-swatch-btn,
.theme-dark .color-swatch-btn {
  background: #1f2937 !important;
  border-color: #374151 !important;
}

html[data-admin-theme='dark'] .swatch-label,
.admin-layout.dark .swatch-label,
.admin-layout.theme-dark .swatch-label,
.dark .swatch-label,
.theme-dark .swatch-label {
  color: #cbd5e1 !important;
}

html[data-admin-theme='dark'] .color-swatch-btn.selected,
.admin-layout.dark .color-swatch-btn.selected,
.admin-layout.theme-dark .color-swatch-btn.selected,
.dark .color-swatch-btn.selected,
.theme-dark .color-swatch-btn.selected {
  background: #1e3a8a !important;
  border-color: #3b82f6 !important;
}

html[data-admin-theme='dark'] .color-swatch-btn.selected .swatch-label,
.admin-layout.dark .color-swatch-btn.selected .swatch-label,
.admin-layout.theme-dark .color-swatch-btn.selected .swatch-label,
.dark .color-swatch-btn.selected .swatch-label,
.theme-dark .color-swatch-btn.selected .swatch-label {
  color: #ffffff !important;
}

html[data-admin-theme='dark'] .quick-act-btn.select-all,
.admin-layout.dark .quick-act-btn.select-all,
.admin-layout.theme-dark .quick-act-btn.select-all,
.dark .quick-act-btn.select-all,
.theme-dark .quick-act-btn.select-all {
  color: #60a5fa !important;
}

html[data-admin-theme='dark'] .quick-act-btn.clear-all,
.admin-layout.dark .quick-act-btn.clear-all,
.admin-layout.theme-dark .quick-act-btn.clear-all,
.dark .quick-act-btn.clear-all,
.theme-dark .quick-act-btn.clear-all {
  color: #94a3b8 !important;
}

html[data-admin-theme='dark'] .generate-bar,
.admin-layout.dark .generate-bar,
.admin-layout.theme-dark .generate-bar,
.dark .generate-bar,
.theme-dark .generate-bar {
  background: #1e293b !important;
  border-color: #334155 !important;
}

html[data-admin-theme='dark'] .gb-summary,
.admin-layout.dark .gb-summary,
.admin-layout.theme-dark .gb-summary,
.dark .gb-summary,
.theme-dark .gb-summary {
  color: #cbd5e1 !important;
}

html[data-admin-theme='dark'] .gb-summary .text-muted,
.admin-layout.dark .gb-summary .text-muted,
.admin-layout.theme-dark .gb-summary .text-muted,
.dark .gb-summary .text-muted,
.theme-dark .gb-summary .text-muted {
  color: #94a3b8 !important;
}

/* ============================================================
   DARK MODE OVERRIDES: MA TRẬN BIẾN THỂ & GIÁ KHO THỰC TẾ
   ============================================================ */
html[data-admin-theme='dark'] .active-badge,
.admin-layout.theme-dark .active-badge,
.admin-layout.dark .active-badge,
.dark .active-badge {
  background: rgba(22, 101, 52, 0.35) !important;
  color: #86efac !important;
  border: 1px solid rgba(34, 197, 94, 0.4) !important;
}

html[data-admin-theme='dark'] .btn-xl-export,
.admin-layout.theme-dark .btn-xl-export,
.admin-layout.dark .btn-xl-export,
.dark .btn-xl-export {
  background: rgba(30, 58, 138, 0.4) !important;
  color: #93c5fd !important;
  border: 1px solid rgba(96, 165, 250, 0.4) !important;
}

html[data-admin-theme='dark'] .btn-xl-export:hover,
.admin-layout.theme-dark .btn-xl-export:hover,
.admin-layout.dark .btn-xl-export:hover,
.dark .btn-xl-export:hover {
  background: rgba(37, 99, 235, 0.5) !important;
  color: #ffffff !important;
}

html[data-admin-theme='dark'] .btn-xl-import,
.admin-layout.theme-dark .btn-xl-import,
.admin-layout.dark .btn-xl-import,
.dark .btn-xl-import {
  background: rgba(16, 185, 129, 0.15) !important;
  color: #6ee7b7 !important;
  border: 1px solid rgba(52, 211, 153, 0.4) !important;
}

html[data-admin-theme='dark'] .btn-xl-import:hover,
.admin-layout.theme-dark .btn-xl-import:hover,
.admin-layout.dark .btn-xl-import:hover,
.dark .btn-xl-import:hover {
  background: rgba(16, 185, 129, 0.3) !important;
  color: #ffffff !important;
}

html[data-admin-theme='dark'] .btn-xl-edit-attrs,
.admin-layout.theme-dark .btn-xl-edit-attrs,
.admin-layout.dark .btn-xl-edit-attrs,
.dark .btn-xl-edit-attrs {
  background: rgba(30, 58, 138, 0.4) !important;
  color: #93c5fd !important;
  border: 1px solid rgba(96, 165, 250, 0.4) !important;
}

html[data-admin-theme='dark'] .btn-xl-edit-attrs:hover,
.admin-layout.theme-dark .btn-xl-edit-attrs:hover,
.admin-layout.dark .btn-xl-edit-attrs:hover,
.dark .btn-xl-edit-attrs:hover {
  background: rgba(37, 99, 235, 0.5) !important;
  color: #ffffff !important;
}

html[data-admin-theme='dark'] .matrix-bulk-toolbar,
.admin-layout.theme-dark .matrix-bulk-toolbar,
.admin-layout.dark .matrix-bulk-toolbar,
.dark .matrix-bulk-toolbar {
  background: #0f172a !important;
  border-color: #1e293b !important;
}

html[data-admin-theme='dark'] .bulk-title,
.admin-layout.theme-dark .bulk-title,
.admin-layout.dark .bulk-title,
.dark .bulk-title {
  color: #cbd5e1 !important;
}

html[data-admin-theme='dark'] .bulk-input-field,
.admin-layout.theme-dark .bulk-input-field,
.admin-layout.dark .bulk-input-field,
.dark .bulk-input-field {
  background: #1e293b !important;
  border-color: #334155 !important;
  color: #f8fafc !important;
}

html[data-admin-theme='dark'] .bulk-input-field:focus,
.admin-layout.theme-dark .bulk-input-field:focus,
.admin-layout.dark .bulk-input-field:focus,
.dark .bulk-input-field:focus {
  border-color: #3b82f6 !important;
  background: #0f172a !important;
}

html[data-admin-theme='dark'] .btn-quick-toggle,
.admin-layout.theme-dark .btn-quick-toggle,
.admin-layout.dark .btn-quick-toggle,
.dark .btn-quick-toggle {
  background: #1e293b !important;
  border-color: #334155 !important;
  color: #cbd5e1 !important;
}

html[data-admin-theme='dark'] .btn-quick-toggle:hover,
.admin-layout.theme-dark .btn-quick-toggle:hover,
.admin-layout.dark .btn-quick-toggle:hover,
.dark .btn-quick-toggle:hover {
  background: #334155 !important;
  color: #ffffff !important;
}

html[data-admin-theme='dark'] .btn-apply-bulk,
.admin-layout.theme-dark .btn-apply-bulk,
.admin-layout.dark .btn-apply-bulk,
.dark .btn-apply-bulk {
  background: #1e3a8a !important;
  color: #93c5fd !important;
  border: 1px solid #2563eb !important;
}

html[data-admin-theme='dark'] .btn-apply-bulk:hover,
.admin-layout.theme-dark .btn-apply-bulk:hover,
.admin-layout.dark .btn-apply-bulk:hover,
.dark .btn-apply-bulk:hover {
  background: #2563eb !important;
  color: #ffffff !important;
}

html[data-admin-theme='dark'] .btn-add-manual,
.admin-layout.theme-dark .btn-add-manual,
.admin-layout.dark .btn-add-manual,
.dark .btn-add-manual {
  background: #1e293b !important;
  color: #f1f5f9 !important;
  border-color: #475569 !important;
}

html[data-admin-theme='dark'] .btn-add-manual:hover,
.admin-layout.theme-dark .btn-add-manual:hover,
.admin-layout.dark .btn-add-manual:hover,
.dark .btn-add-manual:hover {
  background: #334155 !important;
  color: #ffffff !important;
}

html[data-admin-theme='dark'] .vt-scroll,
.admin-layout.theme-dark .vt-scroll,
.admin-layout.dark .vt-scroll,
.dark .vt-scroll {
  border-color: #1e293b !important;
}

html[data-admin-theme='dark'] .vt-table th,
.admin-layout.theme-dark .vt-table th,
.admin-layout.dark .vt-table th,
.dark .vt-table th {
  background: #1e293b !important;
  color: #94a3b8 !important;
  border-bottom: 2px solid #334155 !important;
}

html[data-admin-theme='dark'] .vt-table td,
.admin-layout.theme-dark .vt-table td,
.admin-layout.dark .vt-table td,
.dark .vt-table td {
  border-bottom: 1px solid #1e293b !important;
  color: #f1f5f9 !important;
}

html[data-admin-theme='dark'] .vt-table tr:hover td,
.admin-layout.theme-dark .vt-table tr:hover td,
.admin-layout.dark .vt-table tr:hover td,
.dark .vt-table tr:hover td {
  background: rgba(30, 41, 59, 0.5) !important;
}

html[data-admin-theme='dark'] .vt-table tr.disabled-row td,
.admin-layout.theme-dark .vt-table tr.disabled-row td,
.admin-layout.dark .vt-table tr.disabled-row td,
.dark .vt-table tr.disabled-row td {
  background: rgba(15, 23, 42, 0.6) !important;
  opacity: 0.55;
}

html[data-admin-theme='dark'] .fst-val-badge,
.admin-layout.theme-dark .fst-val-badge,
.admin-layout.dark .fst-val-badge,
.dark .fst-val-badge,
html[data-admin-theme='dark'] .vt-val-tag,
.admin-layout.theme-dark .vt-val-tag,
.admin-layout.dark .vt-val-tag,
.dark .vt-val-tag,
html[data-admin-theme='dark'] .variant-attr-pill,
.admin-layout.theme-dark .variant-attr-pill,
.admin-layout.dark .variant-attr-pill,
.dark .variant-attr-pill {
  background: #1e293b !important;
  color: #f1f5f9 !important;
  border: 1px solid #334155 !important;
}

html[data-admin-theme='dark'] .vt-input,
.admin-layout.theme-dark .vt-input,
.admin-layout.dark .vt-input,
.dark .vt-input {
  background: #0f172a !important;
  border-color: #334155 !important;
  color: #f8fafc !important;
}

html[data-admin-theme='dark'] .vt-input:focus,
.admin-layout.theme-dark .vt-input:focus,
.admin-layout.dark .vt-input:focus,
.dark .vt-input:focus {
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25) !important;
}

html[data-admin-theme='dark'] .btn-row-del,
.admin-layout.theme-dark .btn-row-del,
.admin-layout.dark .btn-row-del,
.dark .btn-row-del {
  background: rgba(239, 68, 68, 0.15) !important;
  color: #fca5a5 !important;
  border: 1px solid rgba(239, 68, 68, 0.3) !important;
}

html[data-admin-theme='dark'] .btn-row-del:hover,
.admin-layout.theme-dark .btn-row-del:hover,
.admin-layout.dark .btn-row-del:hover,
.dark .btn-row-del:hover {
  background: #ef4444 !important;
  color: #ffffff !important;
}

/* ==================== EXCEL IMPORT PREVIEW MODAL STYLES & DARK MODE ==================== */
.excel-preview-modal-card {
  background: #ffffff;
  color: #0f172a;
  border: 1px solid #e2e8f0;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  border-radius: 16px;
}

.excel-preview-modal-card .modal-card-header {
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}

.excel-preview-modal-card .modal-card-footer {
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.preview-table-container {
  border: 1px solid #e2e8f0;
  background: #ffffff;
}

.modern-preview-table thead {
  background: #f8fafc;
  position: sticky;
  top: 0;
  z-index: 5;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.modern-preview-table th {
  color: #475569;
  background: #f8fafc;
  font-weight: 700;
  border-bottom: 2px solid #e2e8f0;
}

.modern-preview-table tr.row-valid {
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
}

.modern-preview-table tr.row-invalid {
  background: #fff5f5;
  border-bottom: 1px solid #fee2e2;
}

.stat-badge-valid {
  color: #16a34a;
  background: #dcfce7;
}

.stat-badge-invalid {
  color: #dc2626;
  background: #fee2e2;
}

.badge-pid {
  background: #e0f2fe;
  color: #0369a1;
  padding: 2px 8px;
  border-radius: 6px;
  font-weight: 700;
}

.cell-product-name {
  color: #0f172a;
}

.cell-cat-name {
  color: #0f172a;
}

.cell-variant-name {
  color: #475569;
}

.cell-muted-text {
  color: #64748b;
}

.cell-price {
  color: #059669;
}

/* DARK MODE ENHANCEMENTS FOR EXCEL MODAL */
html[data-admin-theme='dark'] .excel-preview-modal-card,
.admin-layout.theme-dark .excel-preview-modal-card,
.admin-layout.dark .excel-preview-modal-card,
.dark .excel-preview-modal-card {
  background: #0f172a !important;
  color: #f8fafc !important;
  border-color: #334155 !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7) !important;
}

html[data-admin-theme='dark'] .excel-preview-modal-card .modal-card-header,
.admin-layout.theme-dark .excel-preview-modal-card .modal-card-header,
.admin-layout.dark .excel-preview-modal-card .modal-card-header,
.dark .excel-preview-modal-card .modal-card-header {
  background: #1e293b !important;
  border-bottom-color: #334155 !important;
  color: #f8fafc !important;
}

html[data-admin-theme='dark'] .excel-preview-modal-card .modal-title-text,
.admin-layout.theme-dark .excel-preview-modal-card .modal-title-text,
.admin-layout.dark .excel-preview-modal-card .modal-title-text,
.dark .excel-preview-modal-card .modal-title-text {
  color: #f8fafc !important;
}

html[data-admin-theme='dark'] .excel-preview-modal-card .modal-subtitle-text,
.admin-layout.theme-dark .excel-preview-modal-card .modal-subtitle-text,
.admin-layout.dark .excel-preview-modal-card .modal-subtitle-text,
.dark .excel-preview-modal-card .modal-subtitle-text {
  color: #94a3b8 !important;
}

html[data-admin-theme='dark'] .excel-preview-modal-card .modal-card-footer,
.admin-layout.theme-dark .excel-preview-modal-card .modal-card-footer,
.admin-layout.dark .excel-preview-modal-card .modal-card-footer,
.dark .excel-preview-modal-card .modal-card-footer {
  background: #1e293b !important;
  border-top-color: #334155 !important;
}

html[data-admin-theme='dark'] .preview-table-container,
.admin-layout.theme-dark .preview-table-container,
.admin-layout.dark .preview-table-container,
.dark .preview-table-container {
  border-color: #334155 !important;
  background: #0f172a !important;
}

html[data-admin-theme='dark'] .modern-preview-table thead,
.admin-layout.theme-dark .modern-preview-table thead,
.admin-layout.dark .modern-preview-table thead,
.dark .modern-preview-table thead {
  background: #1e293b !important;
  border-bottom: 2px solid #334155 !important;
}

html[data-admin-theme='dark'] .modern-preview-table th,
.admin-layout.theme-dark .modern-preview-table th,
.admin-layout.dark .modern-preview-table th,
.dark .modern-preview-table th {
  color: #cbd5e1 !important;
  background: #1e293b !important;
}

html[data-admin-theme='dark'] .modern-preview-table tr.row-valid,
.admin-layout.theme-dark .modern-preview-table tr.row-valid,
.admin-layout.dark .modern-preview-table tr.row-valid,
.dark .modern-preview-table tr.row-valid {
  background: #0f172a !important;
  border-bottom: 1px solid #1e293b !important;
}

html[data-admin-theme='dark'] .modern-preview-table tr.row-invalid,
.admin-layout.theme-dark .modern-preview-table tr.row-invalid,
.admin-layout.dark .modern-preview-table tr.row-invalid,
.dark .modern-preview-table tr.row-invalid {
  background: rgba(153, 27, 27, 0.25) !important;
  border-bottom: 1px solid #450a0a !important;
}

html[data-admin-theme='dark'] .modern-preview-table tr:hover,
.admin-layout.theme-dark .modern-preview-table tr:hover,
.admin-layout.dark .modern-preview-table tr:hover,
.dark .modern-preview-table tr:hover {
  background: #1e293b !important;
}

html[data-admin-theme='dark'] .badge-pid,
.admin-layout.theme-dark .badge-pid,
.admin-layout.dark .badge-pid,
.dark .badge-pid {
  background: rgba(3, 105, 161, 0.25) !important;
  color: #38bdf8 !important;
  border: 1px solid rgba(56, 189, 248, 0.3) !important;
}

html[data-admin-theme='dark'] .cell-product-name,
.admin-layout.theme-dark .cell-product-name,
.admin-layout.dark .cell-product-name,
.dark .cell-product-name {
  color: #f8fafc !important;
}

html[data-admin-theme='dark'] .cell-cat-name,
.admin-layout.theme-dark .cell-cat-name,
.admin-layout.dark .cell-cat-name,
.dark .cell-cat-name {
  color: #f8fafc !important;
}

html[data-admin-theme='dark'] .cell-variant-name,
.admin-layout.theme-dark .cell-variant-name,
.admin-layout.dark .cell-variant-name,
.dark .cell-variant-name {
  color: #cbd5e1 !important;
}

html[data-admin-theme='dark'] .cell-muted-text,
.admin-layout.theme-dark .cell-muted-text,
.admin-layout.dark .cell-muted-text,
.dark .cell-muted-text {
  color: #94a3b8 !important;
}

html[data-admin-theme='dark'] .cell-price,
.admin-layout.theme-dark .cell-price,
.admin-layout.dark .cell-price,
.dark .cell-price {
  color: #34d399 !important;
}

html[data-admin-theme='dark'] .stat-badge-valid,
.admin-layout.theme-dark .stat-badge-valid,
.admin-layout.dark .stat-badge-valid,
.dark .stat-badge-valid {
  background: rgba(22, 163, 74, 0.2) !important;
  color: #4ade80 !important;
  border: 1px solid rgba(74, 222, 128, 0.3) !important;
}

html[data-admin-theme='dark'] .stat-badge-invalid,
.admin-layout.theme-dark .stat-badge-invalid,
.admin-layout.dark .stat-badge-invalid,
.dark .stat-badge-invalid {
  background: rgba(220, 38, 38, 0.2) !important;
  color: #f87171 !important;
  border: 1px solid rgba(248, 113, 113, 0.3) !important;
}

html[data-admin-theme='dark'] .btn-cancel,
.admin-layout.theme-dark .btn-cancel,
.admin-layout.dark .btn-cancel,
.dark .btn-cancel {
  background: #1e293b !important;
  color: #cbd5e1 !important;
  border: 1px solid #475569 !important;
}
</style>

<style>
/* Unscoped Global CSS for Teleported Excel & Image Modals in Dark Mode */
html[data-theme='dark'] .excel-preview-modal-card,
html[data-admin-theme='dark'] .excel-preview-modal-card,
body.dark-mode .excel-preview-modal-card,
body.dark .excel-preview-modal-card,
html[data-theme='dark'] .image-suggest-modal-card,
html[data-admin-theme='dark'] .image-suggest-modal-card,
body.dark-mode .image-suggest-modal-card,
body.dark .image-suggest-modal-card,
html[data-theme='dark'] .image-detail-modal-card,
html[data-admin-theme='dark'] .image-detail-modal-card,
body.dark-mode .image-detail-modal-card,
body.dark .image-detail-modal-card,
html[data-theme='dark'] .custom-modal-card,
html[data-admin-theme='dark'] .custom-modal-card,
body.dark-mode .custom-modal-card,
body.dark .custom-modal-card {
  background: #0f172a !important;
  color: #f8fafc !important;
  border: 1px solid #334155 !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.75) !important;
}

html[data-theme='dark'] .custom-modal-card .modal-card-header,
html[data-admin-theme='dark'] .custom-modal-card .modal-card-header,
body.dark-mode .custom-modal-card .modal-card-header,
body.dark .custom-modal-card .modal-card-header {
  background: #1e293b !important;
  border-bottom: 1px solid #334155 !important;
  color: #f8fafc !important;
}

html[data-theme='dark'] .custom-modal-card .modal-title-text,
html[data-admin-theme='dark'] .custom-modal-card .modal-title-text,
body.dark-mode .custom-modal-card .modal-title-text,
body.dark .custom-modal-card .modal-title-text {
  color: #f8fafc !important;
}

html[data-theme='dark'] .custom-modal-card .modal-subtitle-text,
html[data-admin-theme='dark'] .custom-modal-card .modal-subtitle-text,
body.dark-mode .custom-modal-card .modal-subtitle-text,
body.dark .custom-modal-card .modal-subtitle-text {
  color: #94a3b8 !important;
}

html[data-theme='dark'] .custom-modal-card .modal-card-body,
html[data-admin-theme='dark'] .custom-modal-card .modal-card-body,
body.dark-mode .custom-modal-card .modal-card-body,
body.dark .custom-modal-card .modal-card-body {
  background: #0f172a !important;
  color: #f8fafc !important;
}

html[data-theme='dark'] .custom-modal-card .modal-card-footer,
html[data-admin-theme='dark'] .custom-modal-card .modal-card-footer,
body.dark-mode .custom-modal-card .modal-card-footer,
body.dark .custom-modal-card .modal-card-footer {
  background: #1e293b !important;
  border-top: 1px solid #334155 !important;
}

html[data-theme='dark'] .preview-table-container,
html[data-admin-theme='dark'] .preview-table-container,
body.dark-mode .preview-table-container,
body.dark .preview-table-container {
  border-color: #334155 !important;
  background: #0f172a !important;
}

html[data-theme='dark'] .modern-preview-table thead,
html[data-admin-theme='dark'] .modern-preview-table thead,
body.dark-mode .modern-preview-table thead,
body.dark .modern-preview-table thead {
  background: #1e293b !important;
  border-bottom: 2px solid #334155 !important;
}

html[data-theme='dark'] .modern-preview-table th,
html[data-admin-theme='dark'] .modern-preview-table th,
body.dark-mode .modern-preview-table th,
body.dark .modern-preview-table th {
  color: #cbd5e1 !important;
  background: #1e293b !important;
}

html[data-theme='dark'] .modern-preview-table tr.row-valid,
html[data-admin-theme='dark'] .modern-preview-table tr.row-valid,
body.dark-mode .modern-preview-table tr.row-valid,
body.dark .modern-preview-table tr.row-valid {
  background: #0f172a !important;
  border-bottom: 1px solid #1e293b !important;
}

html[data-theme='dark'] .modern-preview-table tr.row-invalid,
html[data-admin-theme='dark'] .modern-preview-table tr.row-invalid,
body.dark-mode .modern-preview-table tr.row-invalid,
body.dark .modern-preview-table tr.row-invalid {
  background: rgba(153, 27, 27, 0.25) !important;
  border-bottom: 1px solid #450a0a !important;
}

html[data-theme='dark'] .modern-preview-table tr:hover,
html[data-admin-theme='dark'] .modern-preview-table tr:hover,
body.dark-mode .modern-preview-table tr:hover,
body.dark .modern-preview-table tr:hover {
  background: #1e293b !important;
}

html[data-theme='dark'] .pinned-images-strip,
html[data-admin-theme='dark'] .pinned-images-strip,
body.dark-mode .pinned-images-strip,
body.dark .pinned-images-strip {
  background: #1e293b !important;
  border-color: #475569 !important;
}

html[data-theme='dark'] .suggest-card-box,
html[data-admin-theme='dark'] .suggest-card-box,
body.dark-mode .suggest-card-box,
body.dark .suggest-card-box {
  background: #1e293b !important;
  border-color: #334155 !important;
}

html[data-theme='dark'] .suggest-card-box.isMain,
html[data-admin-theme='dark'] .suggest-card-box.isMain,
body.dark-mode .suggest-card-box.isMain,
body.dark .suggest-card-box.isMain {
  background: rgba(37, 99, 235, 0.25) !important;
  border-color: #2563eb !important;
}

html[data-theme='dark'] .suggest-card-box.isGallery,
html[data-admin-theme='dark'] .suggest-card-box.isGallery,
body.dark-mode .suggest-card-box.isGallery,
body.dark .suggest-card-box.isGallery {
  background: rgba(124, 58, 237, 0.25) !important;
  border-color: #7c3aed !important;
}

html[data-theme='dark'] .badge-pid,
html[data-admin-theme='dark'] .badge-pid,
body.dark-mode .badge-pid,
body.dark .badge-pid {
  background: rgba(3, 105, 161, 0.25) !important;
  color: #38bdf8 !important;
  border: 1px solid rgba(56, 189, 248, 0.3) !important;
}

html[data-theme='dark'] .cell-product-name,
html[data-admin-theme='dark'] .cell-product-name,
body.dark-mode .cell-product-name,
body.dark .cell-product-name {
  color: #f8fafc !important;
}

html[data-theme='dark'] .cell-cat-name,
html[data-admin-theme='dark'] .cell-cat-name,
body.dark-mode .cell-cat-name,
body.dark .cell-cat-name {
  color: #f8fafc !important;
}

html[data-theme='dark'] .cell-variant-name,
html[data-admin-theme='dark'] .cell-variant-name,
body.dark-mode .cell-variant-name,
body.dark .cell-variant-name {
  color: #cbd5e1 !important;
}

html[data-theme='dark'] .cell-muted-text,
html[data-admin-theme='dark'] .cell-muted-text,
body.dark-mode .cell-muted-text,
body.dark .cell-muted-text {
  color: #94a3b8 !important;
}

html[data-theme='dark'] .cell-price,
html[data-admin-theme='dark'] .cell-price,
body.dark-mode .cell-price,
body.dark .cell-price {
  color: #34d399 !important;
}

html[data-theme='dark'] .btn-cancel,
html[data-admin-theme='dark'] .btn-cancel,
body.dark-mode .btn-cancel,
body.dark .btn-cancel {
  background: #1e293b !important;
  color: #cbd5e1 !important;
  border: 1px solid #475569 !important;
}

html[data-theme='dark'] .badge-match-db,
html[data-admin-theme='dark'] .badge-match-db,
body.dark-mode .badge-match-db,
body.dark .badge-match-db {
  background: rgba(22, 163, 74, 0.25) !important;
  color: #4ade80 !important;
  border-color: rgba(74, 222, 128, 0.35) !important;
}

html[data-theme='dark'] .badge-new-db,
html[data-admin-theme='dark'] .badge-new-db,
body.dark-mode .badge-new-db,
body.dark .badge-new-db {
  background: rgba(217, 119, 6, 0.25) !important;
  color: #fbbf24 !important;
  border-color: rgba(251, 191, 36, 0.35) !important;
}

html[data-theme='dark'] .stat-badge-match-db,
html[data-admin-theme='dark'] .stat-badge-match-db,
body.dark-mode .stat-badge-match-db,
body.dark .stat-badge-match-db {
  background: rgba(22, 163, 74, 0.25) !important;
  color: #4ade80 !important;
  border-color: rgba(74, 222, 128, 0.35) !important;
}

html[data-theme='dark'] .stat-badge-new-db,
html[data-admin-theme='dark'] .stat-badge-new-db,
body.dark-mode .stat-badge-new-db,
body.dark .stat-badge-new-db {
  background: rgba(217, 119, 6, 0.25) !important;
  color: #fbbf24 !important;
  border-color: rgba(251, 191, 36, 0.35) !important;
}

html[data-theme='dark'] .select-inline-edit,
html[data-admin-theme='dark'] .select-inline-edit,
body.dark-mode .select-inline-edit,
body.dark .select-inline-edit,
html[data-theme='dark'] .input-inline-edit,
html[data-admin-theme='dark'] .input-inline-edit,
body.dark-mode .input-inline-edit,
body.dark .input-inline-edit {
  background: #1e293b !important;
  color: #f8fafc !important;
  border-color: #475569 !important;
}

/* Dark Mode: Chữ xem trước sáng nổi bật & Nút bấm nền tối êm mắt */
html[data-theme='dark'] .cell-specs-preview,
html[data-admin-theme='dark'] .cell-specs-preview,
body.dark-mode .cell-specs-preview,
body.dark .cell-specs-preview {
  color: #94a3b8 !important;
}

html[data-theme='dark'] .cell-variant-preview,
html[data-admin-theme='dark'] .cell-variant-preview,
body.dark-mode .cell-variant-preview,
body.dark .cell-variant-preview {
  color: #cbd5e1 !important;
}

html[data-theme='dark'] .btn-open-attr-modal.btn-specs,
html[data-admin-theme='dark'] .btn-open-attr-modal.btn-specs,
body.dark-mode .btn-open-attr-modal.btn-specs,
body.dark .btn-open-attr-modal.btn-specs {
  background: rgba(2, 132, 199, 0.2) !important;
  color: #38bdf8 !important;
  border-color: rgba(56, 189, 248, 0.35) !important;
}

html[data-theme='dark'] .btn-open-attr-modal.btn-variant,
html[data-admin-theme='dark'] .btn-open-attr-modal.btn-variant,
body.dark-mode .btn-open-attr-modal.btn-variant,
body.dark .btn-open-attr-modal.btn-variant {
  background: rgba(124, 58, 237, 0.2) !important;
  color: #c084fc !important;
  border-color: rgba(192, 132, 252, 0.35) !important;
}

html[data-theme='dark'] .attr-edit-modal-card,
html[data-admin-theme='dark'] .attr-edit-modal-card,
body.dark-mode .attr-edit-modal-card,
body.dark .attr-edit-modal-card {
  background: #0f172a !important;
  color: #f8fafc !important;
  border-color: #334155 !important;
}

html[data-theme='dark'] .attr-edit-modal-card .modal-card-header,
html[data-theme='dark'] .attr-edit-modal-card .modal-card-footer,
html[data-admin-theme='dark'] .attr-edit-modal-card .modal-card-header,
html[data-admin-theme='dark'] .attr-edit-modal-card .modal-card-footer,
body.dark-mode .attr-edit-modal-card .modal-card-header,
body.dark-mode .attr-edit-modal-card .modal-card-footer,
body.dark .attr-edit-modal-card .modal-card-header,
body.dark .attr-edit-modal-card .modal-card-footer {
  background: #1e293b !important;
  border-color: #334155 !important;
}

html[data-theme='dark'] .attr-edit-modal-card .modal-title-text,
html[data-admin-theme='dark'] .attr-edit-modal-card .modal-title-text,
body.dark-mode .attr-edit-modal-card .modal-title-text,
body.dark .attr-edit-modal-card .modal-title-text {
  color: #f8fafc !important;
}
</style>
