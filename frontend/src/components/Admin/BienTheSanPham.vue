<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { getUser } from '@/services/auth'
import api from '@/services/api'
import * as XLSX from 'xlsx'
import swal from '@/services/swal'

const user = ref(getUser() || {})
const hasPermission = (perm) => {
  if (user.value?.vaitro === 'admin') return true
  return user.value?.cac_quyen?.includes(perm)
}

const activeTab = ref('Biến thể cấu hình')
const newButtonLabel = computed(() => activeTab.value === 'Biến thể cấu hình' ? 'Thêm biến thể mới' : 'Thêm màu mới')
const newButtonModalType = computed(() => activeTab.value === 'Biến thể cấu hình' ? 'variant' : 'color')
const isOpenAttributeDropdown = ref(false)

const showModal = ref(false)
const modalType = ref('variant')
const formError = ref('')
const loading = ref(false)

const typePalette = [
  { bg: '#dbeafe', color: '#1d4ed8' },
  { bg: '#dcfce7', color: '#1d4ed8' },
  { bg: '#ede9fe', color: '#1d4ed8' },
  { bg: '#fef3c7', color: '#b45309' },
  { bg: '#fee2e2', color: '#b91c1c' },
  { bg: '#e0f2fe', color: '#0369a1' },
]

const getTypeStyle = (name) => {
  if (!name) return { bg: '#e5e7eb', color: '#374151' }
  const index =
    String(name)
      .split('')
      .reduce((sum, ch) => sum + ch.charCodeAt(0), 0) % typePalette.length
  return typePalette[index]
}

const namedColorMap = {
  black: '#111827',
  den: '#111827',
  'mau den': '#111827',
  white: '#ffffff',
  trang: '#ffffff',
  'mau trang': '#ffffff',
  silver: '#c0c0c0',
  bac: '#c0c0c0',
  gray: '#6b7280',
  grey: '#6b7280',
  xam: '#6b7280',
  blue: '#2563eb',
  xanh: '#2563eb',
  red: '#dc2626',
  do: '#dc2626',
  green: '#16a34a',
  'xanh la': '#16a34a',
  yellow: '#facc15',
  vang: '#facc15',
  pink: '#ec4899',
  hong: '#ec4899',
  purple: '#7c3aed',
  tim: '#7c3aed',
  orange: '#f97316',
  cam: '#f97316',
  gold: '#d97706',
}

const stripVietnamese = (value = '') =>
  String(value)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/g, 'd')
    .replace(/Đ/g, 'D')
    .toLowerCase()
    .trim()

const normalizeHex = (value, fallbackName = '') => {
  const raw = String(value || '').trim()
  if (/^#[0-9a-fA-F]{6}$/.test(raw)) return raw.toUpperCase()
  if (/^[0-9a-fA-F]{6}$/.test(raw)) return `#${raw}`.toUpperCase()
  const key = stripVietnamese(fallbackName)
  return namedColorMap[key] || '#E5E7EB'
}

const normalizeColor = (color = {}) => {
  const name = color.name || color.ten || color.ten_mau || color.tenMau || color.label || 'Chưa đặt tên'
  return {
    id: color.id || color.id_mau || color.id_mau_sac,
    name,
    hex: normalizeHex(color.hex || color.hex_code || color.mamau || color.ma_mau || color.color_code, name),
    stock: color.stock || color.trangthai || 'Khả dụng',
  }
}
// ── Data ──
const variants = ref([])
const groups = ref([])
const attrs = ref([])
const colors = ref([])
const categories = ref([])
const parentCategories = computed(() => categories.value.filter(c => !c.parent_id))
const selectedColor = ref(null)

// ── Filter ──
const selectedAttribute = ref('')
const selectedGroup = ref('')
const variantSearchQuery = ref('')
const filterSearchQuery = ref('')

const selectGroup = (groupName) => {
  selectedGroup.value = groupName
  selectedAttribute.value = ''
  isOpenAttributeDropdown.value = false
}

const selectAttribute = (attrName) => {
  selectedGroup.value = ''
  selectedAttribute.value = attrName
  isOpenAttributeDropdown.value = false
}

const clearFilters = () => {
  selectedGroup.value = ''
  selectedAttribute.value = ''
  isOpenAttributeDropdown.value = false
}

const filteredGroupedAttributes = computed(() => {
  const query = filterSearchQuery.value.toLowerCase().trim()
  const map = {}
  attrs.value.forEach(a => {
    if (query && !a.name.toLowerCase().includes(query) && !a.group.toLowerCase().includes(query)) {
      return
    }
    if (!map[a.group]) {
      map[a.group] = []
    }
    map[a.group].push(a.name)
  })
  return map
})

const filteredVariants = computed(() => {
  let result = variants.value
  if (selectedGroup.value) {
    const groupAttrs = attrs.value
      .filter(a => a.group === selectedGroup.value)
      .map(a => a.name)
    result = result.filter(item => groupAttrs.includes(item.type))
  } else if (selectedAttribute.value) {
    result = result.filter(item => item.type === selectedAttribute.value)
  }
  if (variantSearchQuery.value.trim()) {
    const q = variantSearchQuery.value.toLowerCase().trim()
    result = result.filter(item => 
      item.name.toLowerCase().includes(q) || 
      item.type.toLowerCase().includes(q)
    )
  }
  return result
})

// Reset về trang 1 khi đổi filter
watch([selectedAttribute, selectedGroup, variantSearchQuery], () => { variantPage.value = 1 })

// ── Pagination ──
const PER_PAGE = 6

const variantPage = ref(1)
const colorPage = ref(1)
const groupPage = ref(1)
const attrPage = ref(1)

const usePagination = (listRef, pageRef) => {
  const total = computed(() => listRef.value.length)
  const totalPages = computed(() => Math.max(1, Math.ceil(total.value / PER_PAGE)))
  const pagedData = computed(() => {
    const start = (pageRef.value - 1) * PER_PAGE
    return listRef.value.slice(start, start + PER_PAGE)
  })
  const from = computed(() => (total.value === 0 ? 0 : (pageRef.value - 1) * PER_PAGE + 1))
  const to = computed(() => (total.value === 0 ? 0 : Math.min(pageRef.value * PER_PAGE, total.value)))
  const goToPage = (page) => {
    if (page < 1) { pageRef.value = 1; return }
    if (page > totalPages.value) { pageRef.value = totalPages.value; return }
    pageRef.value = page
  }
  const prevPage = () => goToPage(pageRef.value - 1)
  const nextPage = () => goToPage(pageRef.value + 1)
  return { total, totalPages, pagedData, from, to, goToPage, prevPage, nextPage }
}

// ← dùng filteredVariants thay vì variants
const variantPagination = usePagination(filteredVariants, variantPage)
const colorPagination = usePagination(colors, colorPage)
const groupPagination = usePagination(groups, groupPage)
const attrPagination = usePagination(attrs, attrPage)

const pagedVariants = computed(() => variantPagination.pagedData.value)
const pagedColors = computed(() => colorPagination.pagedData.value)
const pagedGroups = computed(() => groupPagination.pagedData.value)
const pagedAttrs = computed(() => attrPagination.pagedData.value)

const variantPages = computed(() => variantPagination.totalPages.value)
const colorPages = computed(() => colorPagination.totalPages.value)
const groupPages = computed(() => groupPagination.totalPages.value)
const attrPages = computed(() => attrPagination.totalPages.value)

const variantTypeOptions = computed(() => attrs.value.map((a) => a.name))

// ── Forms ──
const defaultVariantForm = () => ({
  name: '',
  type: variantTypeOptions.value[0] || '',
  status: 'Hoạt động',
  gia_cong_them: 0,
  danh_muc_ids: []
})
const defaultColorForm = () => ({ name: '', hex: '#000000', stock: 'Khả dụng' })
const defaultGroupForm = () => ({ name: '', danh_muc_ids: [] })
const defaultAttrForm = () => ({ name: '', group: groups.value[0]?.name || '', status: 'Hoạt động' })

const variantForm = ref(defaultVariantForm())
const colorForm = ref(defaultColorForm())
const groupForm = ref(defaultGroupForm())
const attrForm = ref(defaultAttrForm())
let editingId = null

// ── Duplicate Check Helpers ──
const isDuplicateVariant = (name, type, excludeId = null) => {
  return variants.value.some(v =>
    v.name.trim().toLowerCase() === name.trim().toLowerCase() &&
    v.type === type &&
    v.id !== excludeId
  )
}

const isDuplicateColor = (name, excludeId = null) => {
  return colors.value.some(c =>
    c.name.trim().toLowerCase() === name.trim().toLowerCase() &&
    c.id !== excludeId
  )
}

const isDuplicateGroup = (name, excludeId = null) => {
  return groups.value.some(g =>
    g.name.trim().toLowerCase() === name.trim().toLowerCase() &&
    g.id !== excludeId
  )
}

const isDuplicateAttr = (name, groupName, excludeId = null) => {
  return attrs.value.some(a =>
    a.name.trim().toLowerCase() === name.trim().toLowerCase() &&
    a.group === groupName &&
    a.id !== excludeId
  )
}

// ── Helpers ──
const buildPageItems = (currentPage, totalPages) => {
  if (totalPages <= 7) return Array.from({ length: totalPages }, (_, i) => i + 1)
  if (currentPage <= 4) return [1, 2, 3, 4, '...', totalPages - 2, totalPages - 1, totalPages]
  if (currentPage >= totalPages - 3) return [1, 2, 3, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages]
  return [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages]
}

const variantPageItems = computed(() => buildPageItems(variantPage.value, variantPages.value))
const colorPageItems = computed(() => buildPageItems(colorPage.value, colorPages.value))
const groupPageItems = computed(() => buildPageItems(groupPage.value, groupPages.value))
const attrPageItems = computed(() => buildPageItems(attrPage.value, attrPages.value))

const normalizeData = (payload) => {
  const nhoms = Array.isArray(payload) ? payload : []

  const normalizedGroups = nhoms.map((g) => {
    const thuocTinhs = Array.isArray(g.thuoc_tinhs) ? g.thuoc_tinhs : Array.isArray(g.thuocTinhs) ? g.thuocTinhs : []
    let dsId = g.danh_muc_ids || []
    if (typeof dsId === 'string') {
      try { dsId = JSON.parse(dsId) } catch(e) { dsId = [] }
    }
    dsId = Array.isArray(dsId) ? dsId : []
    return { id: g.id_nhom, name: g.ten_nhom, attrCount: thuocTinhs.length, danh_muc_ids: dsId }
  })

  const normalizedAttrs = []
  const normalizedVariants = []

  nhoms.forEach((g) => {
    const thuocTinhs = Array.isArray(g.thuoc_tinhs) ? g.thuoc_tinhs : Array.isArray(g.thuocTinhs) ? g.thuocTinhs : []
    thuocTinhs.forEach((a) => {
      normalizedAttrs.push({
        id: a.id_thuoctinh,
        name: a.ten_thuoctinh,
        group: g.ten_nhom,
        groupId: g.id_nhom,
        status: Number(a.trangthai) === 1 ? 'Hoạt động' : 'Nháp'
      })
      const giaTris = Array.isArray(a.giatri_thuoc_tinhs) ? a.giatri_thuoc_tinhs : Array.isArray(a.giatriThuocTinhs) ? a.giatriThuocTinhs : []
      giaTris.forEach((v) => {
        normalizedVariants.push({
          id: v.id_giatri,
          name: v.giatri,
          type: a.ten_thuoctinh,
          attrId: a.id_thuoctinh,
          status: Number(v.trangthai) === 1 ? 'Hoạt động' : 'Nháp',
          gia_cong_them: Number(v.gia_cong_them || 0),
          danh_muc_ids: Array.isArray(v.danh_muc_ids) ? v.danh_muc_ids : []
        })
      })
    })
  })

  groups.value = normalizedGroups
  attrs.value = normalizedAttrs
  variants.value = normalizedVariants

  if (!selectedColor.value && colors.value.length > 0) selectedColor.value = colors.value[0]
  if (!variantForm.value.type && normalizedAttrs.length > 0) variantForm.value.type = normalizedAttrs[0].name
  if (!attrForm.value.group && normalizedGroups.length > 0) attrForm.value.group = normalizedGroups[0].name

  groupPagination.goToPage(groupPage.value)
  attrPagination.goToPage(attrPage.value)
  variantPagination.goToPage(variantPage.value)
  colorPagination.goToPage(colorPage.value)
}

const getErrorMessage = (error, fallback) => {
  if (error?.response?.data?.message) return error.response.data.message
  const errors = error?.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const firstKey = Object.keys(errors)[0]
    if (firstKey && Array.isArray(errors[firstKey]) && errors[firstKey][0]) return errors[firstKey][0]
  }
  return fallback
}

const fetchAll = async () => {
  loading.value = true
  try {
    const res = await api.get('/thuoctinh-all')
    normalizeData(res.data)
  } catch (error) {
    formError.value = getErrorMessage(error, 'Không tải được dữ liệu từ API.')
  } finally {
    loading.value = false
  }
}

const fetchColors = async () => {
  try {
    const res = await api.get('/colors')
    const list = Array.isArray(res.data) ? res.data : (res.data?.data || [])
    colors.value = list.map(normalizeColor)
    if (!selectedColor.value && colors.value.length > 0) selectedColor.value = colors.value[0]
    colorPagination.goToPage(1)
  } catch (error) {
    formError.value = getErrorMessage(error, 'Không tải được danh sách màu.')
  }
}

const fetchCategories = async () => {
  try {
    const res = await api.get('/danhmuc')
    categories.value = Array.isArray(res.data) ? res.data : (res.data?.data || [])
  } catch (error) {
    console.error('Không tải được danh mục')
  }
}

// ── Modal ──
const openModal = (type, item = null) => {
  modalType.value = type
  formError.value = ''
  editingId = item ? Number(item.id ?? item.id_giatri ?? 0) : null

  if (type === 'variant') variantForm.value = defaultVariantForm()
  else if (type === 'editVariant' && item) {
    variantForm.value = {
      ...item,
      gia_cong_them: item.gia_cong_them ?? 0,
      danh_muc_ids: Array.isArray(item.danh_muc_ids) ? [...item.danh_muc_ids] : []
    }
  }
  else if (type === 'color') colorForm.value = defaultColorForm()
  else if (type === 'editColor' && item) colorForm.value = { ...item }
  else if (type === 'group') groupForm.value = defaultGroupForm()
  else if (type === 'editGroup' && item)    groupForm.value = { 
      name: item.name,
      danh_muc_ids: Array.isArray(item.danh_muc_ids) ? [...item.danh_muc_ids] : []
    }
  else if (type === 'attr') attrForm.value = defaultAttrForm()
  else if (type === 'editAttr' && item) attrForm.value = { name: item.name, group: item.group, status: item.status }

  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  formError.value = ''
}

// ── Submits ──
const submitVariant = async () => {
  if (!variantForm.value.name.trim()) {
    formError.value = 'Vui lòng nhập tên biến thể.'
    return
  }
  if (!variantForm.value.type) {
    formError.value = 'Vui lòng chọn loại thuộc tính.'
    return
  }
  const selectedAttr = attrs.value.find((a) => a.name === variantForm.value.type)
  if (!selectedAttr) {
    formError.value = 'Không tìm thấy loại thuộc tính tương ứng.'
    return
  }

  if (isDuplicateVariant(variantForm.value.name, variantForm.value.type, editingId)) {
    formError.value = `Biến thể "${variantForm.value.name}" đã tồn tại trong loại "${variantForm.value.type}".`
    return
  }

  const payload = {
    id_thuoctinh: selectedAttr.id,
    giatri: variantForm.value.name,
    gia_cong_them: Number(variantForm.value.gia_cong_them || 0),
    trangthai: variantForm.value.status === 'Hoạt động' ? 1 : 0,
    danh_muc_ids: variantForm.value.danh_muc_ids
  }

  try {
    if (modalType.value === 'editVariant') {
      if (!editingId) {
        formError.value = 'Không tìm thấy ID biến thể để cập nhật.'
        return
      }
      await api.put(`/admin/giatrithuoctinh/${editingId}`, payload)
    } else {
      await api.post('/admin/giatrithuoctinh', payload)
    }
    await fetchAll()
    variantPagination.goToPage(1)
    closeModal()
  } catch (error) {
    formError.value = getErrorMessage(error, modalType.value === 'editVariant' ? 'Không cập nhật được biến thể.' : 'Không thêm được biến thể.')
  }
}

const submitColor = async () => {
  if (!colorForm.value.name.trim()) {
    formError.value = 'Vui lòng nhập tên màu.'
    return
  }
  if (isDuplicateColor(colorForm.value.name, editingId)) {
    formError.value = `Màu sắc "${colorForm.value.name}" đã tồn tại.`
    return
  }
  try {
    const payload = {
      name: colorForm.value.name,
      hex_code: colorForm.value.hex,
      ten: colorForm.value.name,
      mamau: normalizeHex(colorForm.value.hex),
    }
    if (modalType.value === 'editColor') {
      await api.put(`/admin/colors/${editingId}`, payload)
    } else {
      await api.post('/admin/colors', payload)
    }
    await fetchColors()
    closeModal()
  } catch (error) {
    formError.value = getErrorMessage(error, modalType.value === 'editColor' ? 'Không cập nhật được màu.' : 'Không thêm được màu.')
  }
}

const submitGroup = async () => {
  if (!groupForm.value.name.trim()) {
    formError.value = 'Vui lòng nhập tên nhóm.'
    return
  }
  if (isDuplicateGroup(groupForm.value.name, editingId)) {
    formError.value = `Nhóm thuộc tính "${groupForm.value.name}" đã tồn tại.`
    return
  }
  try {
    const payload = { 
      ten_nhom: groupForm.value.name,
      danh_muc_ids: groupForm.value.danh_muc_ids
    }
    if (modalType.value === 'editGroup') {
      await api.put(`/admin/nhomthuoctinh/${editingId}`, payload)
    } else {
      await api.post('/admin/nhomthuoctinh', payload)
    }
    await fetchAll()
    groupPagination.goToPage(1)
    closeModal()
  } catch (error) {
    formError.value = getErrorMessage(error, modalType.value === 'editGroup' ? 'Không cập nhật được nhóm thuộc tính.' : 'Không tạo được nhóm thuộc tính.')
  }
}

const submitAttr = async () => {
  if (!attrForm.value.name.trim()) {
    formError.value = 'Vui lòng nhập tên thuộc tính.'
    return
  }
  if (!attrForm.value.group) {
    formError.value = 'Vui lòng chọn nhóm thuộc tính.'
    return
  }
  const selectedGroup = groups.value.find((g) => g.name === attrForm.value.group)
  if (!selectedGroup) {
    formError.value = 'Không tìm thấy nhóm thuộc tính tương ứng.'
    return
  }
  if (isDuplicateAttr(attrForm.value.name, attrForm.value.group, editingId)) {
    formError.value = `Thuộc tính "${attrForm.value.name}" đã tồn tại trong nhóm "${attrForm.value.group}".`
    return
  }

  const payload = {
    ten_thuoctinh: attrForm.value.name,
    id_nhom: selectedGroup.id,
    trangthai: attrForm.value.status === 'Hoạt động' ? 1 : 0
  }

  try {
    if (modalType.value === 'editAttr') {
      await api.put(`/admin/thuoctinh/${editingId}`, payload)
    } else {
      await api.post('/admin/thuoctinh', payload)
    }
    await fetchAll()
    attrPagination.goToPage(1)
    closeModal()
  } catch (error) {
    formError.value = getErrorMessage(error, modalType.value === 'editAttr' ? 'Không cập nhật được loại thuộc tính.' : 'Không tạo được loại thuộc tính.')
  }
}

const handleSubmit = () => {
  if (['variant', 'editVariant'].includes(modalType.value)) return submitVariant()
  if (['color', 'editColor'].includes(modalType.value)) return submitColor()
  if (['group', 'editGroup'].includes(modalType.value)) return submitGroup()
  if (['attr', 'editAttr'].includes(modalType.value)) return submitAttr()
}

// ── Delete ──
const removeVariant = async (id) => {
  const ok = await swal.confirm(
    'Xác nhận xóa',
    'Bạn có chắc chắn muốn xóa biến thể này không? Thao tác này không thể hoàn tác.',
    'Xóa',
    'Hủy'
  )
  if (!ok) return

  try {
    await api.delete(`/admin/giatrithuoctinh/${id}`)
    await fetchAll()
    variantPagination.goToPage(variantPage.value)
    swal.success('Xóa biến thể thành công')
  } catch (error) {
    const msg = getErrorMessage(error, 'Không xóa được biến thể.')
    swal.error('Lỗi', msg)
  }
}

const removeColor = async (id) => {
  const ok = await swal.confirm(
    'Xác nhận xóa',
    'Bạn có chắc chắn muốn xóa màu sắc này không? Thao tác này không thể hoàn tác.',
    'Xóa',
    'Hủy'
  )
  if (!ok) return

  try {
    await api.delete(`/admin/colors/${id}`)
    if (selectedColor.value?.id === id) selectedColor.value = colors.value.find(c => c.id !== id) || null
    await fetchColors()
    swal.success('Xóa màu sắc thành công')
  } catch (error) {
    const msg = getErrorMessage(error, 'Không xóa được màu.')
    swal.error('Lỗi', msg)
  }
}

const removeGroup = async (id) => {
  const ok = await swal.confirm(
    'Xác nhận xóa',
    'Bạn có chắc chắn muốn xóa nhóm thuộc tính này không? Tất cả thuộc tính con thuộc nhóm này cũng sẽ bị ảnh hưởng.',
    'Xóa',
    'Hủy'
  )
  if (!ok) return

  try {
    await api.delete(`/admin/nhomthuoctinh/${id}`)
    await fetchAll()
    groupPagination.goToPage(groupPage.value)
    swal.success('Xóa nhóm thuộc tính thành công')
  } catch (error) {
    const msg = getErrorMessage(error, 'Không xóa được nhóm thuộc tính.')
    swal.error('Lỗi', msg)
  }
}

const removeAttr = async (id) => {
  const ok = await swal.confirm(
    'Xác nhận xóa',
    'Bạn có chắc chắn muốn xóa loại thuộc tính này không? Các giá trị biến thể thuộc về loại thuộc tính này cũng sẽ bị xóa.',
    'Xóa',
    'Hủy'
  )
  if (!ok) return

  try {
    await api.delete(`/admin/thuoctinh/${id}`)
    await fetchAll()
    attrPagination.goToPage(attrPage.value)
    swal.success('Xóa loại thuộc tính thành công')
  } catch (error) {
    const msg = getErrorMessage(error, 'Không xóa được loại thuộc tính.')
    swal.error('Lỗi', msg)
  }
}

const isEditModal = computed(() => modalType.value.startsWith('edit'))

const modalTitle = computed(() => ({
  variant: 'Thêm biến thể mới',
  editVariant: 'Chỉnh sửa biến thể',
  color: 'Thêm màu sắc mới',
  editColor: 'Chỉnh sửa màu sắc',
  group: 'Tạo nhóm thuộc tính',
  editGroup: 'Chỉnh sửa nhóm thuộc tính',
  attr: 'Tạo loại thuộc tính',
  editAttr: 'Chỉnh sửa loại thuộc tính',
})[modalType.value] || '')

const modalBtnLabel = computed(() =>
  isEditModal.value ? 'Lưu thay đổi' : ({
    variant: 'Thêm biến thể',
    color: 'Thêm màu sắc',
    group: 'Tạo nhóm',
    attr: 'Tạo thuộc tính',
  })[modalType.value] || 'Lưu'
)

const closeAttributeDropdown = (e) => {
  if (!e.target.closest('.attribute-filter-dropdown')) {
    isOpenAttributeDropdown.value = false
  }
}

onMounted(() => {
  fetchAll()
  fetchColors()
  document.addEventListener('click', closeAttributeDropdown)
})

onUnmounted(() => {
  document.removeEventListener('click', closeAttributeDropdown)
  fetchCategories()
})

// ══════════════════════════════════════════════════════
// XUẤT / IMPORT EXCEL
// ══════════════════════════════════════════════════════

function exportVariants() {
  const today = new Date().toLocaleDateString('vi-VN')
  const title = [`DANH SÁCH BIẾN THỂ CẤU HÌNH (xuất ngày ${today})`]
  const header = ['Tên biến thể', 'Loại thuộc tính', 'Trạng thái', 'Giá cộng thêm']
  const rows = variants.value.map(v => [v.name, v.type, v.status, v.gia_cong_them || 0])
  const ws = XLSX.utils.aoa_to_sheet([title, [], header, ...rows])
  ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 3 } }]
  ws['!cols'] = [{ wch: 24 }, { wch: 20 }, { wch: 14 }, { wch: 15 }]
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Biến thể')
  XLSX.writeFile(wb, `bien-the-${Date.now()}.xlsx`)
}

function exportColors() {
  const today = new Date().toLocaleDateString('vi-VN')
  const title = [`DANH SÁCH MÀU SẮC SẢN PHẨM (xuất ngày ${today})`]
  const header = ['Tên màu', 'Mã màu (HEX)']
  const rows = colors.value.map(c => [c.name, c.hex])
  const ws = XLSX.utils.aoa_to_sheet([title, [], header, ...rows])
  ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 1 } }]
  ws['!cols'] = [{ wch: 20 }, { wch: 14 }]
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Màu sắc')
  XLSX.writeFile(wb, `mau-sac-${Date.now()}.xlsx`)
}

// ── Import ──
const importLoading = ref(false)
const importError = ref('')
const importSuccess = ref('')
const importFileRef = ref(null)

function triggerImport() {
  importError.value = ''
  importSuccess.value = ''
  importFileRef.value.click()
}

async function handleImportFile(e) {
  const file = e.target.files[0]
  if (!file) return
  e.target.value = ''

  importLoading.value = true
  importError.value = ''
  importSuccess.value = ''

  try {
    const data = await file.arrayBuffer()
    const wb = XLSX.read(data)
    const ws = wb.Sheets[wb.SheetNames[0]]
    const rows = XLSX.utils.sheet_to_json(ws, { header: 1 })

    const headerIdx = rows.findIndex(r =>
      r.some(cell => typeof cell === 'string' && (cell.includes('Tên biến thể') || cell.includes('Tên màu')))
    )
    if (headerIdx === -1) throw new Error('Không tìm thấy header hợp lệ trong file.')

    const headers = rows[headerIdx].map(h => String(h || '').trim())
    const dataRows = rows.slice(headerIdx + 1).filter(r => r.some(Boolean))

    const isColorFile = headers.includes('Mã màu (HEX)')

    let successCount = 0
    let skipCount = 0
    let failCount = 0

    for (const row of dataRows) {
      const obj = {}
      headers.forEach((h, i) => { obj[h] = row[i] ?? '' })

      try {
        if (isColorFile) {
          const name = String(obj['Tên màu'] || '').trim()
          const hex = normalizeHex(obj['Mã màu (HEX)'], name)
          if (!name) { skipCount++; continue }

          const existingColor = colors.value.find(c => c.name.trim().toLowerCase() === name.toLowerCase())
          const payload = { name, hex_code: hex, ten: name, mamau: hex }
          if (existingColor) {
            // Cập nhật màu hiện có
            await api.put(`/admin/colors/${existingColor.id}`, payload)
          } else {
            // Thêm màu mới
            await api.post('/admin/colors', payload)
          }
        } else {
          const varName = String(obj['Tên biến thể'] || '').trim()
          const attrName = String(obj['Loại thuộc tính'] || '').trim()
          const statusStr = String(obj['Trạng thái'] || '').trim()
          const giaCongThem = Number(obj['Giá cộng thêm'] || 0)
          
          if (!varName || !attrName) { skipCount++; continue }
          const attr = attrs.value.find(a => a.name === attrName)
          if (!attr) { skipCount++; continue }

          const statusVal = statusStr === 'Hoạt động' ? 1 : 0
          const existingVar = variants.value.find(v => 
            v.name.trim().toLowerCase() === varName.toLowerCase() && 
            v.type === attrName
          )

          if (existingVar) {
            // Cập nhật biến thể hiện có
            await api.put(`/admin/giatrithuoctinh/${existingVar.id}`, {
              id_thuoctinh: attr.id,
              giatri: varName,
              gia_cong_them: giaCongThem,
              trangthai: statusVal
            })
          } else {
            // Thêm biến thể mới
            await api.post('/admin/giatrithuoctinh', { 
              id_thuoctinh: attr.id, 
              giatri: varName,
              gia_cong_them: giaCongThem,
              trangthai: statusVal
            })
          }
        }
        successCount++
      } catch {
        failCount++
      }
    }

    await fetchAll()
    await fetchColors()

    const parts = [`Nhập thành công/Cập nhật ${successCount} dòng`]
    if (skipCount) parts.push(`bỏ qua ${skipCount} dòng`)
    if (failCount) parts.push(`thất bại ${failCount} dòng`)
    importSuccess.value = parts.join(', ') + '.'
    await swal.success('Nhập dữ liệu thành công', importSuccess.value)
  } catch (err) {
    importError.value = err.message || 'Đọc file thất bại. Hãy dùng file xuất từ hệ thống.'
    await swal.error('Nhập dữ liệu thất bại', importError.value)
  } finally {
    importLoading.value = false
  }
}
</script>

<template>
  <div class="page">
    <!-- TOPBAR -->
    <div class="topbar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.35-4.35" />
        </svg>
        <input placeholder="Tìm kiếm hệ thống..." />
      </div>
      <div class="topbar-right">
        <span class="nav-link">Inventory</span>
        <span class="nav-link">Orders</span>
        <span class="nav-link">Analytics</span>
        <span class="nav-link">Customers</span>
        <button class="icon-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
          </svg>
          <span class="notif-dot"></span>
        </button>
        <button class="icon-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3" />
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14" />
          </svg>
        </button>
        <div class="av">AV</div>
      </div>
    </div>

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
      <span>Hệ thống</span>
      <span class="sep">›</span>
      <span class="active-crumb">Quản lý sản phẩm</span>
    </div>

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div>
        <h1>Quản lý biến thể &amp; Màu sắc</h1>
        <p>Cấu hình các thuộc tính kỹ thuật và dải màu sắc dành cho dòng sản phẩm cao cấp VinaTech 2026.</p>
      </div>
    </div>

    <!-- ══ TOP TABLES: GROUP + ATTR ══ -->
    <div class="top-tables">
      <!-- NHÓM THUỘC TÍNH -->
      <div class="card top-card">
        <div class="card-header">
          <div class="card-title"><span class="bar green"></span>Nhóm thuộc tính</div>
          <button class="btn-new-sm green-btn" @click="openModal('group')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Tạo nhóm mới
          </button>
        </div>
        <table>
          <thead>
            <tr>
              <th>TÊN NHÓM</th>
              <th>DANH MỤC CHA</th>
              <th>SỐ THUỘC TÍNH</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="pagedGroups.length === 0">
              <td colspan="3" class="empty">{{ loading ? 'Đang tải dữ liệu...' : 'Chưa có nhóm nào.' }}</td>
            </tr>
            <tr v-for="g in pagedGroups" :key="g.id">
              <td>
                <div class="name-cell">
                  <div class="cell-icon green-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="3" width="7" height="7" rx="1" />
                      <rect x="14" y="3" width="7" height="7" rx="1" />
                      <rect x="3" y="14" width="7" height="7" rx="1" />
                      <rect x="14" y="14" width="7" height="7" rx="1" />
                    </svg>
                  </div>
                  <span class="variant-name">{{ g.name }}</span>
                </div>
              </td>
              <td>
                <div class="badges">
                  <span v-for="id in g.danh_muc_ids" :key="id" class="badge bg-blue">
                    {{ parentCategories.find(c => String(c.id_danhmuc) === String(id))?.ten_danhmuc || 'N/A' }}
                  </span>
                  <span v-if="!g.danh_muc_ids || g.danh_muc_ids.length === 0" class="badge bg-gray">Tất cả</span>
                </div>
              </td>
              <td><span class="count-badge">{{ g.attrCount }}</span></td>
              <td>
                <div class="actions">
                  <button class="act-btn" @click="openModal('editGroup', g)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                  <button class="act-btn danger" @click="removeGroup(g.id)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                      <polyline points="3 6 5 6 21 6" />
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                      <path d="M10 11v6M14 11v6" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="table-footer">
          <span class="page-info">{{ groups.length }} nhóm</span>
          <div class="pagination">
            <button class="page-btn" :disabled="groupPage === 1" @click="groupPagination.prevPage()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="15 18 9 12 15 6" />
              </svg>
            </button>
            <span class="page-btn active page-indicator">{{ groupPage }}/{{ groupPages }}</span>
            <button class="page-btn" :disabled="groupPage === groupPages" @click="groupPagination.nextPage()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="9 18 15 12 9 6" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- LOẠI THUỘC TÍNH -->
      <div class="card top-card">
        <div class="card-header">
          <div class="card-title"><span class="bar purple2"></span>Loại thuộc tính</div>
          <button class="btn-new-sm purple-btn" @click="openModal('attr')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Tạo loại mới
          </button>
        </div>
        <table>
          <thead>
            <tr>
              <th>TÊN THUỘC TÍNH</th>
              <th>NHÓM</th>
              <th>TRẠNG THÁI</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="pagedAttrs.length === 0">
              <td colspan="4" class="empty">{{ loading ? 'Đang tải dữ liệu...' : 'Chưa có loại thuộc tính nào.' }}</td>
            </tr>
            <tr v-for="a in pagedAttrs" :key="a.id">
              <td>
                <div class="name-cell">
                  <div class="cell-icon purple-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                      <line x1="7" y1="7" x2="7.01" y2="7" />
                    </svg>
                  </div>
                  <span class="variant-name">{{ a.name }}</span>
                </div>
              </td>
              <td><span class="group-tag">{{ a.group }}</span></td>
              <td>
                <span class="status-dot" :class="a.status === 'Hoạt động' ? 'active' : 'draft'">● {{ a.status }}</span>
              </td>
              <td>
                <div class="actions">
                  <button class="act-btn" @click="openModal('editAttr', a)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                  <button class="act-btn danger" @click="removeAttr(a.id)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                      <polyline points="3 6 5 6 21 6" />
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                      <path d="M10 11v6M14 11v6" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="table-footer">
          <span class="page-info">{{ attrs.length }} thuộc tính</span>
          <div class="pagination">
            <button class="page-btn" :disabled="attrPage === 1" @click="attrPagination.prevPage()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="15 18 9 12 15 6" />
              </svg>
            </button>
            <span class="page-btn active page-indicator">{{ attrPage }}/{{ attrPages }}</span>
            <button class="page-btn" :disabled="attrPage === attrPages" @click="attrPagination.nextPage()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="9 18 15 12 9 6" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TABS -->
    <div class="tabs">
      <div class="tab-buttons">
        <button class="tab" :class="{ active: activeTab === 'Biến thể cấu hình' }"
          @click="activeTab = 'Biến thể cấu hình'">Biến thể cấu hình</button>
        <button class="tab" :class="{ active: activeTab === 'Bảng màu sản phẩm' }"
          @click="activeTab = 'Bảng màu sản phẩm'">Bảng màu sản phẩm</button>
      </div>
      <button v-if="hasPermission('bien_the_sua')" class="btn-new" @click="openModal(newButtonModalType)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="12" y1="5" x2="12" y2="19" />
          <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        {{ newButtonLabel }}
      </button>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="main-layout">
      <div class="left-col">
        <!-- VARIANT TABLE -->
        <div v-if="activeTab === 'Biến thể cấu hình'" class="card">
          <div class="card-header">
            <div class="card-title"><span class="bar blue"></span>Danh sách biến thể</div>
            <div class="card-header-right">
              <!-- Search box for variants -->
              <div class="variant-search-wrap">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8" />
                  <path d="m21 21-4.35-4.35" />
                </svg>
                <input v-model="variantSearchQuery" placeholder="Tìm tên biến thể..." class="variant-search-input" />
              </div>
              
              <!-- ── FILTER SELECT ── -->
              <div class="filter-wrap attribute-filter-dropdown">
                <div class="dropdown-trigger" @click.stop="isOpenAttributeDropdown = !isOpenAttributeDropdown">
                  <svg class="filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                  </svg>
                  <span class="selected-val">{{ selectedGroup ? `Nhóm: ${selectedGroup}` : (selectedAttribute || 'Tất cả loại') }}</span>
                  <svg class="chevron" :class="{ open: isOpenAttributeDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </div>
                <transition name="fade-slide">
                  <ul v-if="isOpenAttributeDropdown" class="dropdown-menu">
                    <!-- Search input inside dropdown -->
                    <div class="dropdown-search" @click.stop>
                      <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                      </svg>
                      <input v-model="filterSearchQuery" placeholder="Tìm thuộc tính..." />
                    </div>
                    
                    <li :class="{ active: selectedAttribute === '' && selectedGroup === '' }" @click="clearFilters">
                      Tất cả loại
                    </li>
                    
                    <div v-for="(groupAttrs, groupName) in filteredGroupedAttributes" :key="groupName" class="dropdown-group">
                      <div class="dropdown-group-title" :class="{ active: selectedGroup === groupName }" @click="selectGroup(groupName)">
                        {{ groupName }}
                      </div>
                      <li v-for="opt in groupAttrs" :key="opt"
                        :class="{ active: selectedAttribute === opt }"
                        @click="selectAttribute(opt)">
                        {{ opt }}
                      </li>
                    </div>
                  </ul>
                </transition>
              </div>
              <div class="card-tools">
                <button class="tool-btn">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="4" y1="6" x2="20" y2="6" />
                    <line x1="8" y1="12" x2="16" y2="12" />
                    <line x1="11" y1="18" x2="13" y2="18" />
                  </svg>
                </button>
                <button class="tool-btn">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M3 6h18M6 12h12M9 18h6" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>TÊN BIẾN THỂ</th>
                <th>LOẠI THUỘC TÍNH</th>
                <th>DANH MỤC ÁP DỤNG</th>
                <th>TRẠNG THÁI</th>
                <th>THAO TÁC</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="pagedVariants.length === 0">
                <td colspan="4" class="empty">{{ loading ? 'Đang tải dữ liệu...' : 'Chưa có biến thể nào.' }}</td>
              </tr>
              <tr v-for="v in pagedVariants" :key="v.id">
                <td class="variant-name">{{ v.name }}</td>
                <td>
                  <span class="type-badge"
                    :style="{ background: getTypeStyle(v.type).bg, color: getTypeStyle(v.type).color }">{{ v.type }}</span>
                </td>
                <td>
                  <span v-if="v.danh_muc_ids && v.danh_muc_ids.length > 0" style="font-size: 12px; color: #64748b;">
                    {{ v.danh_muc_ids.length }} danh mục
                  </span>
                  <span v-else style="font-size: 12px; color: #64748b;">Tất cả</span>
                </td>
                <td>
                  <span class="status-dot" :class="v.status === 'Hoạt động' ? 'active' : 'draft'">● {{ v.status }}</span>
                </td>
                <td>
                  <div class="actions">
                    <button v-if="hasPermission('bien_the_sua')" class="act-btn" @click="openModal('editVariant', v)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                      </svg>
                    </button>
                    <button v-if="hasPermission('bien_the_sua')" class="act-btn danger" @click="removeVariant(v.id)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        <path d="M10 11v6M14 11v6" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="table-footer">
            <span class="page-info">Hiển thị {{ variantPagination.from }}–{{ variantPagination.to }} / {{ variantPagination.total }}</span>
            <div class="pagination">
              <button class="page-btn" :disabled="variantPage === 1" @click="variantPagination.prevPage()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="15 18 9 12 15 6" />
                </svg>
              </button>
              <span class="page-btn active page-indicator">{{ variantPage }}/{{ variantPages }}</span>
              <button class="page-btn" :disabled="variantPage === variantPages" @click="variantPagination.nextPage()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- COLOR TABLE -->
        <div v-else class="card">
          <div class="card-header">
            <div class="card-title"><span class="bar purple"></span>Bảng màu sản phẩm</div>
          </div>
          <table>
            <thead>
              <tr>
                <th>MÀU</th>
                <th>TÊN</th>
                <th>THAO TÁC</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in pagedColors" :key="c.id" @click="selectedColor = c" style="cursor:pointer">
                <td>
                  <div class="color-swatch-cell" :style="{ background: c.hex || '#E5E7EB' }"></div>
                </td>
                <td class="variant-name">{{ c.name || 'Chưa đặt tên' }}</td>
                <td>
                  <div class="actions">
                    <button v-if="hasPermission('bien_the_sua')" class="act-btn" @click.stop="openModal('editColor', c)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                      </svg>
                    </button>
                    <button v-if="hasPermission('bien_the_sua')" class="act-btn danger" @click.stop="removeColor(c.id)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        <path d="M10 11v6M14 11v6" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="table-footer">
            <span class="page-info">Hiển thị {{ colorPagination.from }}–{{ colorPagination.to }} / {{ colorPagination.total }}</span>
            <div class="pagination">
              <button class="page-btn" :disabled="colorPage === 1" @click="colorPagination.prevPage()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="15 18 9 12 15 6" />
                </svg>
              </button>
              <span class="page-btn active page-indicator">{{ colorPage }}/{{ colorPages }}</span>
              <button class="page-btn" :disabled="colorPage === colorPages" @click="colorPagination.nextPage()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="9 18 15 12 9 6" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT SIDEBAR -->
      <div class="right-col">
        <div class="card side-card">
          <div class="side-header">
            <div class="card-title" style="font-size:14px"><span class="bar purple"></span>Bảng màu</div>
            <button v-if="hasPermission('bien_the_sua')" class="btn-add-sm" @click="openModal('color')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                style="width:11px;height:11px;flex-shrink:0">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
              </svg>
              Thêm màu
            </button>
          </div>
          <div class="color-list">
            <div v-for="c in colors" :key="c.id" class="color-row-item"
              :class="{ 'color-selected': selectedColor?.id === c.id }" @click="selectedColor = c">
              <div class="color-dot" :style="{ background: c.hex || '#E5E7EB' }"></div>
              <div class="color-info"><b>{{ c.name || 'Chưa đặt tên' }}</b><span>{{ c.hex || '#E5E7EB' }}</span></div>
              <div class="color-row-actions">
                <span :class="c.stock === 'Khả dụng' ? 'stock-ok' : 'stock-out'" style="font-size:11px">
                  {{ c.stock === 'Khả dụng' ? '●' : '○' }}
                </span>
                <button v-if="hasPermission('bien_the_sua')" class="color-del-btn" @click.stop="removeColor(c.id)" title="Xóa màu">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    <path d="M10 11v6M14 11v6" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card preview-card">
          <div class="preview-label">PREVIEW: VINABOOK PRO 2026</div>
          <div class="preview-img-wrap">
            <div class="preview-laptop"
              :style="{ background: `linear-gradient(135deg,${selectedColor?.hex || '#1a1a1a'}cc,${selectedColor?.hex || '#1a1a1a'})` }">
              <div class="laptop-screen"></div>
              <div class="laptop-base"></div>
            </div>
          </div>
          <div class="preview-footer">
            <div>
              <b>{{ selectedColor?.name || 'Space Black' }} Edition</b>
              <span>{{ selectedColor?.count || 0 }} biến thể khả dụng</span>
            </div>
            <span :class="selectedColor?.stock === 'Khả dụng' ? 'chip-ok' : 'chip-out'">
              {{ selectedColor?.stock || 'Khả dụng' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- BOTTOM -->
    <div class="bottom-grid">
      <div class="bottom-card">
        <div class="bottom-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" />
          </svg>
        </div>
        <div style="flex:1;min-width:0">
          <h4>Xuất dữ liệu</h4>
          <p>Tải xuống danh sách cấu hình (Excel)</p>
          <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
            <button class="export-sm-btn" @click="exportVariants">Xuất biến thể</button>
            <button class="export-sm-btn purple-sm" @click="exportColors">Xuất màu sắc</button>
          </div>
        </div>
      </div>

      <div class="bottom-card">
        <div class="bottom-icon purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" style="transform:rotate(180deg);transform-origin:12px 9px" />
          </svg>
        </div>
        <div style="flex:1;min-width:0">
          <h4>Nhập dữ liệu</h4>
          <p>Import biến thể hoặc màu từ file Excel</p>
          <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;align-items:center">
            <button class="export-sm-btn green-sm" :disabled="importLoading" @click="triggerImport">
              <span v-if="importLoading" class="btn-loading-wrap">
                <span class="btn-spinner"></span>
                Đang nhập...
              </span>
              <span v-else>Chọn file Excel</span>
            </button>
            <span v-if="importSuccess" style="font-size:11px;color:#2563eb;font-weight:600">✓ {{ importSuccess }}</span>
            <span v-if="importError" style="font-size:11px;color:#dc2626;font-weight:600">✗ {{ importError }}</span>
          </div>
          <input ref="importFileRef" type="file" accept=".xlsx,.xls" style="display:none" @change="handleImportFile" />
        </div>
      </div>

      <div class="bottom-card dark-bottom">
        <div class="bottom-icon white-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="12" cy="12" r="10" />
            <path d="M12 8v4l3 3" />
          </svg>
        </div>
        <div>
          <h4>Thư viện màu</h4>
          <p>Quản lý và mở rộng bộ màu sắc toàn bộ thị tác</p>
        </div>
      </div>
    </div>

    <!-- MODAL -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showModal" class="modal-overlay">
          <transition name="slide-up">
            <div class="modal" v-if="showModal">
              <div class="modal-header">
                <div class="modal-header-left">
                  <div class="modal-icon"
                    :class="isEditModal ? 'icon-edit' : ['group', 'editGroup'].includes(modalType) ? 'icon-group' : ['attr', 'editAttr'].includes(modalType) ? 'icon-attr' : 'icon-add'">
                    <svg v-if="['group', 'editGroup'].includes(modalType)" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                      <rect x="3" y="3" width="7" height="7" rx="1" />
                      <rect x="14" y="3" width="7" height="7" rx="1" />
                      <rect x="3" y="14" width="7" height="7" rx="1" />
                      <rect x="14" y="14" width="7" height="7" rx="1" />
                    </svg>
                    <svg v-else-if="['attr', 'editAttr'].includes(modalType)" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                      <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                      <line x1="7" y1="7" x2="7.01" y2="7" />
                    </svg>
                    <svg v-else-if="['color', 'editColor'].includes(modalType)" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                      <circle cx="13.5" cy="6.5" r="2.5" />
                      <circle cx="6.5" cy="12" r="2.5" />
                      <circle cx="13.5" cy="17.5" r="2.5" />
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                      <line x1="12" y1="5" x2="12" y2="19" />
                      <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="modal-title">{{ modalTitle }}</h3>
                    <p class="modal-subtitle">
                      {{ ['group', 'editGroup'].includes(modalType) ? 'Nhóm các thuộc tính liên quan vào một danh mục'
                        : ['attr', 'editAttr'].includes(modalType) ? 'Loại thuộc tính thuộc về một nhóm cụ thể'
                          : ['color', 'editColor'].includes(modalType) ? 'Màu sắc hiển thị cho sản phẩm ngoài cửa hàng'
                            : 'Thông số kỹ thuật áp dụng cho sản phẩm' }}
                    </p>
                  </div>
                </div>
                <button class="modal-close" @click="closeModal">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                  </svg>
                </button>
              </div>

              <div class="modal-body">
                <!-- VARIANT -->
                <template v-if="['variant', 'editVariant'].includes(modalType)">
                  <div class="form-group">
                    <label>TÊN BIẾN THỂ <span class="req">*</span></label>
                    <input v-model="variantForm.name" placeholder="VD: 16GB Unified Memory" />
                  </div>
                  <div class="form-group">
                    <label>GIÁ CỘNG THÊM (₫)</label>
                    <input v-model.number="variantForm.gia_cong_them" type="number" min="0" placeholder="VD: 2000000" />
                  </div>
                  <div class="form-group">
                    <label>DANH MỤC ÁP DỤNG (Bỏ trống để áp dụng cho tất cả)</label>
                    <select v-model="variantForm.danh_muc_ids" multiple style="height: 100px; padding: 8px;">
                      <option v-for="cat in categories" :key="cat.id_danhmuc" :value="cat.id_danhmuc">{{ cat.ten_danhmuc }}</option>
                    </select>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label>LOẠI THUỘC TÍNH <span class="req">*</span></label>
                      <select v-model="variantForm.type">
                        <option v-for="opt in variantTypeOptions" :key="opt" :value="opt">{{ opt }}</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>TRẠNG THÁI</label>
                      <div class="toggle-group">
                        <button type="button" class="toggle-btn"
                          :class="{ 'tg-green': variantForm.status === 'Hoạt động' }"
                          @click="variantForm.status = 'Hoạt động'">
                          <span class="tdot"></span>Hoạt động
                        </button>
                        <button type="button" class="toggle-btn" :class="{ 'tg-yellow': variantForm.status === 'Nháp' }"
                          @click="variantForm.status = 'Nháp'">
                          <span class="tdot"></span>Nháp
                        </button>
                      </div>
                    </div>
                  </div>
                </template>

                <!-- COLOR -->
                <template v-else-if="['color', 'editColor'].includes(modalType)">
                  <div class="color-preview-big" :style="{ background: colorForm.hex }">
                    <span class="color-preview-name">{{ colorForm.name || 'Tên màu' }}</span>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label>TÊN MÀU <span class="req">*</span></label>
                      <input v-model="colorForm.name" placeholder="VD: Space Black" />
                    </div>
                    <div class="form-group">
                      <label>MÃ MÀU</label>
                      <div class="color-input-wrap">
                        <div class="color-preview-sm" :style="{ background: colorForm.hex }"></div>
                        <input v-model="colorForm.hex" type="text" placeholder="#000000" />
                        <input type="color" v-model="colorForm.hex" class="native-color" />
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>TRẠNG THÁI KHO</label>
                    <div class="toggle-group">
                      <button type="button" class="toggle-btn" :class="{ 'tg-green': colorForm.stock === 'Khả dụng' }"
                        @click="colorForm.stock = 'Khả dụng'">
                        <span class="tdot"></span>Khả dụng
                      </button>
                      <button type="button" class="toggle-btn" :class="{ 'tg-red': colorForm.stock === 'Hết hàng' }"
                        @click="colorForm.stock = 'Hết hàng'">
                        <span class="tdot"></span>Hết hàng
                      </button>
                    </div>
                  </div>
                </template>

                <!-- GROUP -->
                <template v-else-if="['group', 'editGroup'].includes(modalType)">
                  <div class="preview-row green-preview">
                    <div class="prev-icon green-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1" />
                        <rect x="14" y="3" width="7" height="7" rx="1" />
                        <rect x="3" y="14" width="7" height="7" rx="1" />
                        <rect x="14" y="14" width="7" height="7" rx="1" />
                      </svg>
                    </div>
                    <div>
                      <p class="prev-name">{{ groupForm.name || 'Tên nhóm thuộc tính' }}</p>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>TÊN NHÓM THUỘC TÍNH <span class="req">*</span></label>
                    <input v-model="groupForm.name" placeholder="VD: Cấu hình Laptop" />
                  </div>
                  <div class="form-group">
                    <label>ÁP DỤNG CHO DANH MỤC CHA <span class="req">*</span></label>
                    <select v-model="groupForm.danh_muc_ids" multiple class="custom-select" style="height: 100px;">
                      <option v-for="cat in parentCategories" :key="cat.id_danhmuc" :value="cat.id_danhmuc">{{ cat.ten_danhmuc }}</option>
                    </select>
                    <p style="font-size: 12px; color: #6b7280; margin-top: 4px;">Giữ Ctrl/Cmd để chọn nhiều danh mục</p>
                  </div>
                </template>

                <!-- ATTR -->
                <template v-else-if="['attr', 'editAttr'].includes(modalType)">
                  <div class="preview-row purple-preview">
                    <div class="prev-icon purple-icon-prev">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                        <line x1="7" y1="7" x2="7.01" y2="7" />
                      </svg>
                    </div>
                    <div>
                      <p class="prev-name">{{ attrForm.name || 'Tên loại thuộc tính' }}</p>
                      <p class="prev-desc">Thuộc nhóm: <strong>{{ attrForm.group || '—' }}</strong></p>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>TÊN LOẠI THUỘC TÍNH <span class="req">*</span></label>
                    <input v-model="attrForm.name" placeholder="VD: RAM 16GB" />
                  </div>
                  <div class="form-group">
                    <label>THUỘC NHÓM <span class="req">*</span></label>
                    <select v-model="attrForm.group">
                      <option v-for="g in groups" :key="g.id" :value="g.name">{{ g.name }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>TRẠNG THÁI</label>
                    <div class="toggle-group">
                      <button type="button" class="toggle-btn" :class="{ 'tg-green': attrForm.status === 'Hoạt động' }"
                        @click="attrForm.status = 'Hoạt động'">
                        <span class="tdot"></span>Hoạt động
                      </button>
                      <button type="button" class="toggle-btn" :class="{ 'tg-yellow': attrForm.status === 'Nháp' }"
                        @click="attrForm.status = 'Nháp'">
                        <span class="tdot"></span>Nháp
                      </button>
                    </div>
                  </div>
                </template>

                <p v-if="formError" class="form-error">⚠ {{ formError }}</p>
              </div>

              <div class="modal-footer">
                <button class="btn-cancel" @click="closeModal">Hủy</button>
                <button class="btn-submit" @click="handleSubmit">
                  <svg v-if="isEditModal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                  </svg>
                  {{ modalBtnLabel }}
                </button>
              </div>
            </div>
          </transition>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.page {
  background: #f5f7fb;
  min-height: 100vh;
  font-family: 'Be Vietnam Pro', sans-serif;
  padding-bottom: 40px;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 11px 32px;
  background: white;
  border-bottom: 1px solid #f1f5f9;
}

.search-box {
  position: relative;
  width: 220px;
}

.search-box svg {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 13px;
  height: 13px;
  color: #94a3b8;
  pointer-events: none;
}

.search-box input {
  width: 100%;
  padding: 7px 12px 7px 30px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  font-size: 12px;
  color: #0f172a;
  outline: none;
  background: #f8fafc;
  font-family: inherit;
}

.topbar-right { display: none !important; }

.nav-link {
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  padding: 6px 10px;
  border-radius: 6px;
  cursor: pointer;
}

.nav-link:hover {
  color: #2563eb;
  background: #f0f6ff;
}

.icon-btn {
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  padding: 7px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
}

.icon-btn svg {
  width: 16px;
  height: 16px;
}

.icon-btn:hover {
  background: #f1f5f9;
}

.notif-dot {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 7px;
  height: 7px;
  background: #ef4444;
  border-radius: 50%;
  border: 1.5px solid #fff;
}

.av {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #2563eb);
  color: white;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.breadcrumb {
  padding: 14px 32px 0;
  font-size: 12px;
  color: #94a3b8;
  display: flex;
  gap: 6px;
}

.sep {
  color: #cbd5e1;
}

.active-crumb {
  color: #2563eb;
  font-weight: 500;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px 32px 16px;
  gap: 16px;
}

.page-header h1 {
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 5px;
  letter-spacing: -0.02em;
}

.page-header p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
  max-width: 420px;
  line-height: 1.5;
}

.action-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.btn-new {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 10px 18px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #2563eb);
  color: white;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  font-family: inherit;
  box-shadow: 0 4px 12px rgba(37, 99, 235, .3);
  transition: transform .15s, opacity .15s;
}

.btn-new svg {
  width: 14px;
  height: 14px;
}

.btn-new:hover {
  opacity: .9;
  transform: translateY(-1px);
}

.top-tables {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  padding: 0 32px 16px;
}

.top-card {
  padding: 18px 20px;
}

.tabs {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 32px 14px;
}

.tab-buttons {
  display: flex;
  gap: 4px;
}

.tab {
  padding: 9px 18px;
  border-radius: 9px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all .2s;
  font-family: inherit;
}

.tab:hover {
  color: #334155;
  border-color: #cbd5e1;
}

.tab.active {
  background: #2563eb;
  border-color: #2563eb;
  color: white;
  font-weight: 600;
}

.main-layout {
  display: grid;
  grid-template-columns: 1fr 280px;
  gap: 16px;
  padding: 0 32px;
}

.card {
  background: white;
  border-radius: 14px;
  border: 1px solid #f1f5f9;
  padding: 20px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, .04);
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
  gap: 10px;
}

.card-header-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ── FILTER SELECT ── */
.attribute-filter-dropdown {
  position: relative;
  display: inline-block;
  min-width: 140px;
  user-select: none;
}

.attribute-filter-dropdown .dropdown-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 7px 12px 7px 28px;
  border-radius: 8px;
  border: 1.5px solid #cbd5e1;
  background: white;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  transition: all .2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.attribute-filter-dropdown .dropdown-trigger:hover {
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(37,99,235,0.06);
}

.attribute-filter-dropdown .filter-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 12px;
  height: 12px;
  color: #64748b;
  pointer-events: none;
  z-index: 1;
}

.attribute-filter-dropdown .dropdown-trigger .chevron {
  width: 14px;
  height: 14px;
  color: #64748b;
  transition: transform .2s ease;
}

.attribute-filter-dropdown .dropdown-trigger .chevron.open {
  transform: rotate(180deg);
}

.attribute-filter-dropdown .dropdown-menu {
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
  min-width: 210px;
}

/* Custom Scrollbar for Dropdown Menu */
.attribute-filter-dropdown .dropdown-menu::-webkit-scrollbar {
  width: 6px;
}

.attribute-filter-dropdown .dropdown-menu::-webkit-scrollbar-track {
  background: transparent;
}

.attribute-filter-dropdown .dropdown-menu::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

.attribute-filter-dropdown .dropdown-menu li {
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.12s ease;
  text-align: left;
}

.attribute-filter-dropdown .dropdown-menu li:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.attribute-filter-dropdown .dropdown-menu li.active {
  background: #475569;
  color: white;
  font-weight: 600;
}

/* ── Search inputs and grouped items ── */
.variant-search-wrap {
  position: relative;
  width: 180px;
}

.variant-search-wrap svg.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 14px;
  height: 14px;
  color: #94a3b8;
  pointer-events: none;
}

.variant-search-wrap input.variant-search-input {
  width: 100%;
  padding: 7px 12px 7px 30px;
  border-radius: 8px;
  border: 1.5px solid #cbd5e1;
  font-size: 13px;
  font-weight: 500;
  color: #0f172a;
  outline: none;
  background: white;
  transition: all .2s ease;
  font-family: inherit;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.variant-search-wrap input.variant-search-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(37,99,235,0.06);
}

.attribute-filter-dropdown .dropdown-search {
  position: relative;
  padding: 4px;
  margin-bottom: 6px;
  border-bottom: 1px solid #f1f5f9;
}

.attribute-filter-dropdown .dropdown-search svg.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 12px;
  height: 12px;
  color: #94a3b8;
  pointer-events: none;
}

.attribute-filter-dropdown .dropdown-search input {
  width: 100%;
  padding: 6px 10px 6px 26px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  font-size: 12px;
  color: #0f172a;
  outline: none;
  background: #f8fafc;
  font-family: inherit;
}

.attribute-filter-dropdown .dropdown-search input:focus {
  border-color: #3b82f6;
  background: white;
}

.attribute-filter-dropdown .dropdown-group {
  margin-top: 4px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.attribute-filter-dropdown .dropdown-group-title {
  padding: 6px 12px;
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: capitalize;
  letter-spacing: .05em;
  background: #f8fafc;
  border-radius: 6px;
  margin: 2px 0;
  cursor: pointer;
  transition: all 0.15s ease;
}

.attribute-filter-dropdown .dropdown-group-title:hover {
  background: #f1f5f9;
  color: #475569;
}

.attribute-filter-dropdown .dropdown-group-title.active {
  background: #dbeafe;
  color: #1d4ed8;
}

.attribute-filter-dropdown .dropdown-group li {
  padding-left: 20px;
  position: relative;
}

.attribute-filter-dropdown .dropdown-group li::before {
  content: "•";
  position: absolute;
  left: 10px;
  color: #cbd5e1;
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

.card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.bar {
  width: 4px;
  height: 16px;
  border-radius: 2px;
  flex-shrink: 0;
}

.bar.blue { background: #2563eb; }
.bar.purple { background: #2563eb; }
.bar.purple2 { background: #9333ea; }
.bar.green { background: #2563eb; }

.card-tools {
  display: flex;
  gap: 6px;
}

.tool-btn {
  width: 30px;
  height: 30px;
  border-radius: 7px;
  border: 1px solid #e2e8f0;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
}

.tool-btn svg {
  width: 13px;
  height: 13px;
}

.tool-btn:hover {
  background: #f1f5f9;
}

.btn-new-sm {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1.5px solid;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: all .15s;
}

.btn-new-sm svg {
  width: 11px;
  height: 11px;
}

.green-btn {
  border-color: #2563eb;
  background: #f0fdf4;
  color: #1d4ed8;
}

.green-btn:hover { background: #dcfce7; }

.purple-btn {
  border-color: #2563eb;
  background: #faf5ff;
  color: #1d4ed8;
}

.purple-btn:hover { background: #ede9fe; }

table {
  width: 100%;
  border-collapse: collapse;
}

thead th {
  padding: 9px 12px;
  font-size: 10px;
  font-weight: 700;
  color: #94a3b8;
  text-align: left;
  letter-spacing: .07em;
  border-bottom: 1px solid #f1f5f9;
}

tbody tr {
  border-bottom: 1px solid #f8fafc;
  transition: background .15s;
}

tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #fafbff; }

tbody td {
  padding: 12px 12px;
  font-size: 13px;
  vertical-align: middle;
}

.empty {
  text-align: center;
  color: #94a3b8;
  padding: 32px !important;
}

.variant-name {
  font-weight: 600;
  color: #0f172a;
  font-size: 13px;
}

.type-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  padding: 4px 9px;
  border-radius: 6px;
}

.status-dot {
  font-size: 12px;
  font-weight: 600;
}

.status-dot.active { color: #2563eb; }
.status-dot.draft { color: #d97706; }

.color-swatch-cell {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 2px solid rgba(15, 23, 42, .08);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .8);
}

.count-badge {
  background: #f0fdf4;
  color: #1d4ed8;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 20px;
  border: 1px solid #bbf7d0;
}

.group-tag {
  background: #ede9fe;
  color: #1d4ed8;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 20px;
}

.name-cell {
  display: flex;
  align-items: center;
  gap: 9px;
}

.cell-icon {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.cell-icon svg {
  width: 13px;
  height: 13px;
}

.green-icon { background: #f0fdf4; }
.green-icon svg { stroke: #2563eb; }
.purple-icon { background: #faf5ff; }
.purple-icon svg { stroke: #2563eb; }

.actions { display: flex; gap: 5px; }

.act-btn {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  border: 1px solid #e2e8f0;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: all .15s;
}

.act-btn svg {
  width: 12px;
  height: 12px;
}

.act-btn:hover {
  background: #f1f5f9;
  color: #2563eb;
  border-color: #cbd5e1;
}

.act-btn.danger:hover {
  background: #fee2e2;
  border-color: #fecaca;
  color: #ef4444;
}

.table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 12px 2px;
  border-top: 1px solid #f1f5f9;
  margin-top: 4px;
}

.page-info {
  font-size: 11.5px;
  color: #94a3b8;
}

.pagination {
  display: flex;
  gap: 4px;
  align-items: center;
}

.page-btn {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: all .12s;
  font-family: inherit;
}

.page-btn svg {
  width: 12px;
  height: 12px;
}

.page-btn:hover:not(:disabled) {
  border-color: #2563eb;
  color: #2563eb;
}

.page-btn.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
}

.page-btn:disabled {
  opacity: .3;
  cursor: default;
}

.page-btn.dots {
  cursor: default;
  border-color: transparent;
  background: transparent;
  color: #94a3b8;
}

.page-btn.dots:hover {
  border-color: transparent;
  color: #94a3b8;
  background: transparent;
}

.side-card { padding: 16px; }

.side-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.btn-add-sm {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 5px 9px;
  border-radius: 7px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 11px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  font-family: inherit;
  transition: all .15s;
}

.btn-add-sm:hover {
  border-color: #2563eb;
  color: #2563eb;
}

.color-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.color-row-item {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 52px;
  padding: 9px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: all .15s;
  border: 1px solid #edf2f7;
  background: #fff;
}

.color-row-item:hover {
  background: #f8fafc;
  border-color: #bfdbfe;
}

.color-row-item.color-selected {
  background: #f0f6ff;
  border-color: #bfdbfe;
}

.color-dot {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  flex-shrink: 0;
  border: 2px solid rgba(15, 23, 42, .08);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .8);
}

.color-info { flex: 1; min-width: 0; }
.color-info b {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.color-info span {
  display: block;
  margin-top: 2px;
  font-size: 11px;
  color: #64748b;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
}

.color-row-actions {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.color-del-btn {
  width: 22px;
  height: 22px;
  border-radius: 5px;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
  transition: all .15s;
  opacity: 0;
}

.color-del-btn svg {
  width: 10px;
  height: 10px;
}

.color-row-item:hover .color-del-btn { opacity: 1; }
.color-del-btn:hover {
  background: #fee2e2;
  border-color: #fecaca;
  color: #ef4444;
}

.stock-ok { color: #2563eb; font-size: 12px; font-weight: 600; }
.stock-out { color: #dc2626; font-size: 12px; font-weight: 600; }

.preview-card { padding: 14px; }
.preview-label {
  font-size: 9px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: .1em;
  margin-bottom: 12px;
}

.preview-img-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 12px;
}

.preview-laptop {
  width: 140px;
  height: 90px;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  transition: background .4s;
}

.laptop-screen {
  width: 110px;
  height: 65px;
  border-radius: 4px;
  background: rgba(0, 0, 0, .3);
  border: 2px solid rgba(255, 255, 255, .15);
}

.laptop-base {
  width: 120px;
  height: 6px;
  border-radius: 0 0 4px 4px;
  background: rgba(0, 0, 0, .2);
}

.preview-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.preview-footer b {
  display: block;
  font-size: 12px;
  font-weight: 700;
  color: #0f172a;
}

.preview-footer span {
  font-size: 10px;
  color: #94a3b8;
  display: block;
  margin-top: 2px;
}

.chip-ok {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 20px;
  background: #dcfce7;
  color: #2563eb;
  white-space: nowrap;
}

.chip-out {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 20px;
  background: #fee2e2;
  color: #dc2626;
  white-space: nowrap;
}

.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 14px;
  padding: 16px 32px 0;
}

.bottom-card {
  background: white;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
  padding: 18px;
  display: flex;
  gap: 14px;
  align-items: flex-start;
}

.bottom-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bottom-icon svg {
  width: 18px;
  height: 18px;
}

.bottom-icon.blue { background: #dbeafe; color: #2563eb; }
.bottom-icon.purple { background: #ede9fe; color: #2563eb; }

.bottom-card h4 {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px;
}

.bottom-card p {
  font-size: 12px;
  color: #64748b;
  margin: 0;
  line-height: 1.4;
}

.dark-bottom {
  background: linear-gradient(135deg, #1e40af, #2563eb);
  border-color: transparent;
}

.dark-bottom h4 { color: white; }
.dark-bottom p { color: rgba(255, 255, 255, .7); }
.white-icon { background: rgba(255, 255, 255, .15); color: white; }

.export-sm-btn {
  padding: 5px 12px;
  border-radius: 7px;
  border: 1.5px solid #2563eb;
  background: #fff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: all .15s;
  white-space: nowrap;
}

.export-sm-btn:hover:not(:disabled) { background: #2563eb; color: #fff; }
.export-sm-btn:disabled { opacity: .5; cursor: not-allowed; }
.export-sm-btn.purple-sm { border-color: #2563eb; color: #2563eb; }
.export-sm-btn.purple-sm:hover { background: #2563eb; color: #fff; }
.export-sm-btn.green-sm { border-color: #2563eb; color: #2563eb; }
.export-sm-btn.green-sm:hover:not(:disabled) { background: #2563eb; color: #fff; }
.btn-loading-wrap { display: inline-flex; align-items: center; gap: 8px; }
.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  display: inline-block;
  animation: spin .75s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, .55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal {
  background: white;
  border-radius: 18px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 24px 60px rgba(0, 0, 0, .18);
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 22px 14px;
  border-bottom: 1px solid #f1f5f9;
  gap: 12px;
}

.modal-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-icon {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.modal-icon svg {
  width: 17px;
  height: 17px;
}

.icon-add { background: linear-gradient(135deg, #2563eb, #2563eb); }
.icon-edit { background: linear-gradient(135deg, #f59e0b, #f97316); }
.icon-group { background: linear-gradient(135deg, #2563eb, #1D4ED8); }
.icon-attr { background: linear-gradient(135deg, #2563eb, #1d4ed8); }

.modal-title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.modal-subtitle {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 2px;
}

.modal-close {
  width: 30px;
  height: 30px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all .15s;
}

.modal-close svg {
  width: 14px;
  height: 14px;
  stroke: #64748b;
}

.modal-close:hover {
  background: #fee2e2;
  border-color: #fecaca;
}

.modal-close:hover svg { stroke: #ef4444; }

.modal-body {
  padding: 18px 22px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 14px 22px 18px;
  border-top: 1px solid #f1f5f9;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.form-group label {
  font-size: 10px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: .08em;
}

.req { color: #ef4444; }

.form-group input,
.form-group select,
.form-group textarea {
  padding: 9px 11px;
  border-radius: 8px;
  border: 1.5px solid #e2e8f0;
  font-size: 13px;
  color: #0f172a;
  outline: none;
  background: #f8fafc;
  transition: border-color .2s;
  font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #2563eb;
  background: white;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, .08);
}

.color-input-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 11px 4px 4px;
  border-radius: 8px;
  border: 1.5px solid #e2e8f0;
  background: #f8fafc;
}

.color-preview-sm {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  flex-shrink: 0;
  border: 1px solid rgba(0, 0, 0, .08);
}

.color-input-wrap input[type="text"] {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: 13px;
  color: #0f172a;
  padding: 0;
}

.native-color {
  width: 22px;
  height: 22px;
  border: none;
  padding: 0;
  cursor: pointer;
  background: none;
  border-radius: 4px;
}

.color-preview-big {
  border-radius: 12px;
  height: 68px;
  display: flex;
  align-items: flex-end;
  padding: 10px 14px;
  transition: background .3s;
}

.color-preview-name {
  font-size: 13px;
  font-weight: 700;
  color: rgba(255, 255, 255, .9);
  text-shadow: 0 1px 4px rgba(0, 0, 0, .3);
}

.preview-row {
  display: flex;
  align-items: center;
  gap: 12px;
  border-radius: 12px;
  padding: 13px;
  border: 1px solid #e8ecf4;
}

.green-preview { background: #f0fdf4; }
.purple-preview { background: #faf5ff; }

.prev-icon {
  width: 42px;
  height: 42px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.prev-icon svg {
  width: 19px;
  height: 19px;
}

.purple-icon-prev { background: #ede9fe; }
.purple-icon-prev svg { stroke: #2563eb; }

.prev-name {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 2px;
}

.prev-desc {
  font-size: 12px;
  color: #64748b;
}

.toggle-group { display: flex; gap: 6px; }

.toggle-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1.5px solid #e2e8f0;
  background: #f8fafc;
  font-size: 12.5px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  font-family: inherit;
  transition: all .15s;
}

.tdot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #cbd5e1;
  flex-shrink: 0;
  transition: background .15s;
}

.tg-green { border-color: #2563eb; background: #f0fdf4; color: #1d4ed8; }
.tg-green .tdot { background: #2563eb; }
.tg-yellow { border-color: #d97706; background: #fffbeb; color: #b45309; }
.tg-yellow .tdot { background: #d97706; }
.tg-red { border-color: #dc2626; background: #fef2f2; color: #b91c1c; }
.tg-red .tdot { background: #dc2626; }

.form-error {
  font-size: 12px;
  color: #ef4444;
  background: #fef2f2;
  border: 1px solid #fecaca;
  padding: 8px 12px;
  border-radius: 8px;
}

.btn-cancel {
  padding: 9px 18px;
  border-radius: 8px;
  border: 1.5px solid #e2e8f0;
  background: white;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  font-family: inherit;
}

.btn-cancel:hover { background: #f8fafc; }

.btn-submit {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 9px 20px;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #2563eb);
  color: white;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: opacity .2s;
}

.btn-submit svg {
  width: 14px;
  height: 14px;
}

.btn-submit:hover { opacity: .9; }

.fade-enter-active,
.fade-leave-active { transition: opacity .22s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

.slide-up-enter-active {
  transition: all .28s cubic-bezier(.34, 1.56, .64, 1);
}

.slide-up-leave-active { transition: all .18s ease; }

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(24px) scale(.97);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(.98);
}

@media (max-width:1100px) {
  .top-tables { grid-template-columns: 1fr; }
}

@media (max-width:900px) {
  .main-layout {
    grid-template-columns: 1fr;
    padding: 0 16px;
  }

  .top-tables { padding: 0 16px 14px; }

  .bottom-grid {
    grid-template-columns: 1fr 1fr;
    padding: 14px 16px 0;
  }

  .page-header {
    flex-direction: column;
    gap: 12px;
    padding: 12px 16px 14px;
  }

  .tabs,
  .breadcrumb {
    padding-left: 16px;
    padding-right: 16px;
  }

  .action-row { flex-wrap: wrap; }
}

@media (max-width:640px) {
  .bottom-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
}
</style>

