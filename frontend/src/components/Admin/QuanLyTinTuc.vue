<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'
import { getUser, updateUser } from '@/services/auth'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'
import PhanTrangAdmin from './PhanTrangAdmin.vue'

const searchQuery = ref('')
const selectedCategory = ref('all')
const selectedStatus = ref('all')
const showModal = ref(false)
const formError = ref('')
const editorMode = ref('write')
const autosaveState = ref('')
const revisions = ref([])
const showRevisions = ref(false)
let autosaveTimer = null

const isOpenCategoryDropdown = ref(false)
const isOpenStatusDropdown = ref(false)

const closeDropdowns = (e) => {
  if (!e.target.closest('.custom-dropdown')) {
    isOpenCategoryDropdown.value = false
    isOpenStatusDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeDropdowns)
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdowns)
})

const categories = ['Tất cả danh mục', 'Công nghệ', 'Sự kiện', 'Sản phẩm', 'Nội bộ']
const statuses = ['Mọi trạng thái', 'Đã xuất bản', 'Sắp xuất bản', 'Bản nháp']
const loading = ref(false)
const submitting = ref(false)
const editingPost = ref(null)
const imgPreview = ref('')
const fileRef = ref(null)
const contentImageRef = ref(null)
const contentTextareaRef = ref(null)
const contentImageAlt = ref('')
const uploadingContentImage = ref(false)
const posts = ref([])
const currentPage = ref(1)
const lastPage = ref(1)
const totalPosts = ref(0)
const stats = ref({ total: 0, published: 0, scheduled: 0, draft: 0, views: 0 })
const currentUser = ref(getUser())
let searchTimer = null

const defaultCategories = ['Công nghệ', 'Sự kiện', 'Sản phẩm', 'Nội bộ']
const statusText = {
  published: 'Đã xuất bản',
  scheduled: 'Sắp xuất bản',
  draft: 'Bản nháp',
}
const statusOptions = [
  { value: 'all', label: 'Mọi trạng thái' },
  { value: 'published', label: statusText.published },
  { value: 'scheduled', label: statusText.scheduled },
  { value: 'draft', label: statusText.draft },
]
const categoryOptions = computed(() => {
  const merged = [...new Set([...defaultCategories, ...posts.value.map((post) => post.danhmuc).filter(Boolean)])]
  return [{ value: 'all', label: 'Tất cả danh mục' }, ...merged.map((name) => ({ value: name, label: name }))]
})

const catStyle = {
  'Công nghệ': { bg: '#dbeafe', color: '#1d4ed8' },
  'Sự kiện': { bg: '#dcfce7', color: '#1d4ed8' },
  'Sản phẩm': { bg: '#fef9c3', color: '#a16207' },
  'Nội bộ': { bg: '#ede9fe', color: '#1d4ed8' },
}
const statusStyle = {
  published: { bg: '#dcfce7', color: '#1d4ed8' },
  scheduled: { bg: '#fef9c3', color: '#a16207' },
  draft: { bg: '#f1f5f9', color: '#64748b' },
}
const avatarColors = ['#dbeafe', '#dcfce7', '#ede9fe', '#fef9c3', '#fee2e2']
const avatarText = ['#1d4ed8', '#15803d', '#6d28d9', '#a16207', '#b91c1c']
const placeholderImage = 'https://placehold.co/160x100?text=News'

const currentAuthorName = computed(() => String(currentUser.value?.name || '').trim() || 'Admin')

const defaultForm = () => ({
  tieude: '',
  danhmuc: 'Công nghệ',
  tacgia: currentAuthorName.value,
  trangthai: 'draft',
  dang_luc: '',
  tomtat: '',
  noidung: '',
  hinhanh: '',
  mota_hinhanh: '',
  tagsText: '', seo_title: '', seo_description: '', seo_keywords: '', canonical_url: '',
  no_index: false, noi_bat: false, ghim: false, workflow_status: 'draft', revision_note: '',
})
const form = ref(defaultForm())

const vietnameseStopWords = new Set([
  'a', 'anh', 'bai', 'ban', 'bang', 'bi', 'bo', 'cac', 'cach', 'can', 'cho', 'chuan', 'cua', 'de',
  'den', 'dung', 'duoc', 'gi', 'giup', 'hon', 'khi', 'la', 'lam', 'moi', 'mot', 'mua', 'nam',
  'nen', 'nhung', 'noi', 'o', 'phan', 'sao', 'tai', 'the', 'theo', 'thi', 'toi', 'trong', 'tu',
  'uu', 'va', 've', 'voi',
])

const stripAccents = (value = '') => value
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .replace(/đ/g, 'd')
  .replace(/Đ/g, 'D')

const cleanText = (value = '') => value
  .normalize('NFC')
  .replace(/[^\p{L}\p{N}\s-]/gu, ' ')
  .replace(/\s+/g, ' ')
  .trim()

const extractSeoKeyword = (value = '') => {
  const words = cleanText(value).split(' ').filter(Boolean)
  const importantWords = words.filter((word) => !vietnameseStopWords.has(stripAccents(word).toLowerCase()))
  return (importantWords.length ? importantWords : words).slice(0, 5).join(' ')
}

const mainSeoKeyword = computed(() => extractSeoKeyword(form.value.tieude) || cleanText(form.value.danhmuc) || 'laptop')
const shortArticleTitle = computed(() => cleanText((form.value.tieude || '').split(/[:|-]/)[0]).slice(0, 90))

const buildSmartAlt = (type = 'content') => {
  const danhmuc = cleanText(form.value.danhmuc).toLowerCase() || 'tin tức công nghệ'
  const keyword = mainSeoKeyword.value
  const tieude = shortArticleTitle.value

  if (type === 'thumbnail') {
    return `Ảnh đại diện ${danhmuc} về ${keyword}`
  }

  return tieude
    ? `Ảnh minh họa ${danhmuc} về ${keyword} trong bài ${tieude}`
    : `Ảnh minh họa ${danhmuc} về ${keyword}`
}

const smartThumbnailAlt = computed(() => buildSmartAlt('thumbnail'))
const smartContentAlt = computed(() => buildSmartAlt('content'))
const articleWordCount = computed(() => cleanText(
  form.value.noidung.replace(/!\[[^\]]*\]\([^)]+\)/g, ' '),
).split(' ').filter(Boolean).length)
const contentImageCount = computed(() => (
  form.value.noidung.match(/!\[[^\]]*\]\([^)]+\)/g) || []
).length)
const publishQualityReady = computed(() => articleWordCount.value >= 600 && contentImageCount.value >= 2)

const imageUrl = (path) => {
  if (!path) return placeholderImage
  if (path.startsWith('http') || path.startsWith('data:image')) return path
  return storageUrl(path)
}
const formatDate = (value) => {
  if (!value) return 'Chưa đặt lịch'
  return new Date(value).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
const toDateInput = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toISOString().slice(0, 10)
}
const formatNumber = (value) => new Intl.NumberFormat('vi-VN').format(value || 0)
const applyNewsStatFilter = (status) => {
  searchQuery.value = ''
  selectedCategory.value = 'all'
  selectedStatus.value = status
}
const initials = (name = 'Admin') => name.trim().split(' ').map((word) => word[0]).slice(-2).join('').toUpperCase()
const getAvatarStyle = (name = 'Admin') => {
  const i = name.charCodeAt(0) % avatarColors.length
  return { background: avatarColors[i], color: avatarText[i] }
}

const fetchCurrentUser = async () => {
  try {
    const { data } = await api.get('/user/profile')
    const user = data?.user || data
    if (user?.name) {
      currentUser.value = user
      updateUser(user)
      form.value.tacgia = user.name
    }
  } catch (error) {
    console.error('Lỗi tải thông tin admin đang đăng nhập:', error)
  }
}

const fetchStats = async () => {
  try {
    const { data } = await api.get('/admin/news-stats')
    stats.value = data
  } catch (error) {
    console.error('Lỗi tải thống kê tin tức:', error)
  }
}

const fetchPosts = async (page = 1) => {
  loading.value = true
  try {
    const params = { per_page: 10, page }
    if (searchQuery.value.trim()) params.q = searchQuery.value.trim()
    if (selectedCategory.value !== 'all') params.danhmuc = selectedCategory.value
    if (selectedStatus.value !== 'all') params.trangthai = selectedStatus.value

    const { data } = await api.get('/admin/news', { params })
    posts.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
    totalPosts.value = data.total || posts.value.length
  } catch (error) {
    console.error('Lỗi tải danh sách tin tức:', error)
    swal.error('Lỗi', error.response?.data?.message || 'Không thể tải danh sách tin tức.')
  } finally {
    loading.value = false
  }
}

const reload = async (page = currentPage.value) => {
  await Promise.all([fetchPosts(page), fetchStats()])
}

watch([searchQuery, selectedCategory, selectedStatus], () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => fetchPosts(1), 350)
})

watch(() => form.value.trangthai, (trangthai) => {
  if (trangthai !== 'scheduled') {
    form.value.dang_luc = ''
  }
})

watch(currentAuthorName, (name) => {
  form.value.tacgia = name
})

watch(form, () => {
  if (!showModal.value || !editingPost.value) return
  clearTimeout(autosaveTimer)
  autosaveState.value = 'Có thay đổi chưa lưu'
  autosaveTimer = setTimeout(async () => {
    try {
      autosaveState.value = 'Đang tự động lưu...'
      await api.patch(`/admin/news/${editingPost.value.id}/autosave`, {
        tieude: form.value.tieude || 'Bản nháp chưa đặt tên', danhmuc: form.value.danhmuc,
        tomtat: form.value.tomtat || null, noidung: form.value.noidung || null,
        tags: form.value.tagsText.split(',').map((tag) => tag.trim()).filter(Boolean),
      })
      autosaveState.value = `Đã tự động lưu lúc ${new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}`
    } catch { autosaveState.value = 'Tự động lưu thất bại' }
  }, 1800)
}, { deep: true })

const insertMarkdown = async (before, after = '') => {
  const textarea = contentTextareaRef.value
  const content = form.value.noidung || ''
  const start = textarea?.selectionStart ?? content.length
  const end = textarea?.selectionEnd ?? start
  const selected = content.slice(start, end) || 'nội dung'
  form.value.noidung = `${content.slice(0, start)}${before}${selected}${after}${content.slice(end)}`
  await nextTick(); textarea?.focus()
}

const loadRevisions = async () => {
  const { data } = await api.get(`/admin/news/${editingPost.value.id}/revisions`)
  revisions.value = data.data || []; showRevisions.value = true
}

const restoreRevision = async (revision) => {
  const confirmed = await swal.confirm('Khôi phục phiên bản?', `Khôi phục bản ${revision.version}?`, 'Khôi phục', 'Hủy')
  if (!confirmed) return
  const { data } = await api.post(`/admin/news/${editingPost.value.id}/revisions/${revision.id}/restore`)
  openEditModal(data.data); showRevisions.value = false
}

const onFileChange = (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (readerEvent) => {
    imgPreview.value = readerEvent.target.result
    form.value.hinhanh = readerEvent.target.result
  }
  reader.readAsDataURL(file)
}
const insertContentAtCursor = async (text) => {
  const textarea = contentTextareaRef.value
  const currentContent = form.value.noidung || ''

  if (!textarea) {
    form.value.noidung = `${currentContent}${currentContent ? '\n\n' : ''}${text}\n\n`
    return
  }

  const start = textarea.selectionStart ?? currentContent.length
  const end = textarea.selectionEnd ?? start
  const before = currentContent.slice(0, start)
  const after = currentContent.slice(end)
  const prefix = before && !before.endsWith('\n') ? '\n\n' : ''
  const suffix = after && !after.startsWith('\n') ? '\n\n' : '\n\n'

  form.value.noidung = `${before}${prefix}${text}${suffix}${after}`

  await nextTick()
  const cursorPosition = (before + prefix + text + suffix).length
  textarea.focus()
  textarea.setSelectionRange(cursorPosition, cursorPosition)
}
const chooseContentImage = () => {
  contentImageRef.value?.click()
}
const readFileAsDataUrl = (file) => new Promise((resolve, reject) => {
  const reader = new FileReader()
  reader.onload = (readerEvent) => resolve(readerEvent.target.result)
  reader.onerror = reject
  reader.readAsDataURL(file)
})

const onContentImageChange = async (event) => {
  const files = Array.from(event.target.files || [])
  if (!files.length) return

  uploadingContentImage.value = true
  try {
    const markdownImages = []
    for (const [index, file] of files.entries()) {
      const image = await readFileAsDataUrl(file)
      const baseAlt = contentImageAlt.value.trim() || smartContentAlt.value
      const { data } = await api.post('/admin/news/upload-image', {
        image,
        alt: files.length > 1 ? `${baseAlt} - ảnh ${index + 1}` : baseAlt,
        title: form.value.tieude,
        category: form.value.danhmuc,
      })
      markdownImages.push(data.data.markdown)
    }

    await insertContentAtCursor(markdownImages.join('\n\n'))
    contentImageAlt.value = ''
    swal.success('Thành công', `Đã chèn ${markdownImages.length} ảnh vào bài viết.`)
  } catch (error) {
    console.error('Lỗi upload ảnh nội dung:', error)
    swal.error('Lỗi', error.response?.data?.message || 'Không thể upload ảnh nội dung.')
  } finally {
    uploadingContentImage.value = false
    if (contentImageRef.value) contentImageRef.value.value = ''
  }
}
const removeImg = () => {
  imgPreview.value = ''
  form.value.hinhanh = ''
  if (fileRef.value) fileRef.value.value = ''
}
const openModal = () => {
  editingPost.value = null
  form.value = defaultForm()
  imgPreview.value = ''
  contentImageAlt.value = ''
  formError.value = ''
  editorMode.value = 'write'; autosaveState.value = 'Bài mới sẽ tự lưu sau lần lưu đầu tiên'
  showModal.value = true
}
const openEditModal = (post) => {
  editingPost.value = post
  form.value = {
    tieude: post.tieude || '',
    danhmuc: post.danhmuc || 'Công nghệ',
    tacgia: post.tacgia || currentAuthorName.value,
    trangthai: post.trangthai || 'draft',
    dang_luc: toDateInput(post.dang_luc),
    tomtat: post.tomtat || '',
    noidung: post.noidung || '',
    hinhanh: post.hinhanh || '',
    mota_hinhanh: post.mota_hinhanh || post.tieude || '',
    tagsText: (post.tags || []).map((tag) => tag.name).join(', '),
    seo_title: post.seo_title || '', seo_description: post.seo_description || '',
    seo_keywords: post.seo_keywords || '', canonical_url: post.canonical_url || '',
    no_index: Boolean(post.no_index), noi_bat: Boolean(post.noi_bat), ghim: Boolean(post.ghim),
    workflow_status: post.workflow_status || 'draft', revision_note: '',
  }
  imgPreview.value = post.hinhanh ? imageUrl(post.hinhanh) : ''
  contentImageAlt.value = ''
  formError.value = ''
  editorMode.value = 'write'; autosaveState.value = 'Tự động lưu đang bật'
  showModal.value = true
}
const closeModal = () => {
  showModal.value = false
  submitting.value = false
}
const validateForm = () => {
  if (!form.value.tieude.trim()) return 'Vui lòng nhập tiêu đề bài viết.'
  if (!form.value.danhmuc.trim()) return 'Vui lòng chọn danh mục.'
  if (!currentAuthorName.value) return 'Không tìm thấy tên tài khoản đang đăng nhập.'
  if (form.value.trangthai === 'scheduled' && !form.value.dang_luc) return 'Vui lòng chọn ngày đăng cho bài viết hẹn lịch.'
  if (['published', 'scheduled'].includes(form.value.trangthai) && articleWordCount.value < 600) return `Bài xuất bản cần tối thiểu 600 từ. Hiện có ${articleWordCount.value} từ.`
  if (['published', 'scheduled'].includes(form.value.trangthai) && contentImageCount.value < 2) return `Bài xuất bản cần ít nhất 2 ảnh trong nội dung. Hiện có ${contentImageCount.value} ảnh.`
  return ''
}
const submitForm = async (forcedStatus = null) => {
  if (forcedStatus) form.value.trangthai = forcedStatus
  formError.value = validateForm()
  if (formError.value) return

  submitting.value = true
  const payload = {
    tieude: form.value.tieude.trim(),
    danhmuc: form.value.danhmuc.trim(),
    tacgia: form.value.tacgia.trim(),
    trangthai: form.value.trangthai,
    dang_luc: form.value.trangthai === 'scheduled' ? form.value.dang_luc : null,
    tomtat: form.value.tomtat.trim() || null,
    noidung: form.value.noidung.trim() || null,
    hinhanh: form.value.hinhanh || null,
    mota_hinhanh: form.value.mota_hinhanh.trim() || smartThumbnailAlt.value,
    tags: form.value.tagsText.split(',').map((tag) => tag.trim()).filter(Boolean),
    seo_title: form.value.seo_title.trim() || null, seo_description: form.value.seo_description.trim() || null,
    seo_keywords: form.value.seo_keywords.trim() || null, canonical_url: form.value.canonical_url.trim() || null,
    no_index: form.value.no_index, noi_bat: form.value.noi_bat, ghim: form.value.ghim,
    workflow_status: form.value.workflow_status, revision_note: form.value.revision_note.trim() || null,
  }

  try {
    if (editingPost.value) {
      await api.put(`/admin/news/${editingPost.value.id}`, payload)
      swal.success('Thành công', 'Đã cập nhật bài viết.')
    } else {
      await api.post('/admin/news', payload)
      swal.success('Thành công', 'Đã tạo bài viết mới.')
    }
    closeModal()
    await reload(editingPost.value ? currentPage.value : 1)
  } catch (error) {
    console.error('Lỗi lưu bài viết:', error)
    formError.value = error.response?.data?.message || 'Không thể lưu bài viết.'
  } finally {
    submitting.value = false
  }
}
const removePost = async (post) => {
  const confirmed = await swal.confirm('Xóa bài viết?', `Bài viết "${post.tieude}" sẽ bị xóa khỏi hệ thống.`, 'Xóa', 'Hủy')
  if (!confirmed) return

  try {
    const { data } = await api.delete(`/admin/news/${post.id}`)
    swal.success('Thành công', data?.message || 'Đã xóa bài viết.')
    await reload(posts.value.length === 1 && currentPage.value > 1 ? currentPage.value - 1 : currentPage.value)
  } catch (error) {
    console.error('Lỗi xóa bài viết:', error)
    swal.error('Lỗi', error.response?.data?.message || 'Không thể xóa bài viết.')
  }
}

onMounted(async () => {
  await Promise.all([fetchCurrentUser(), reload(1)])
})
</script>

<template>
  <div class="page">
    <div class="page-header">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.35-4.35" />
        </svg>
        <input v-model="searchQuery" placeholder="Tìm kiếm bài viết, tác giả..." />
      </div>
      <button class="btn-new" @click="openModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="12" y1="5" x2="12" y2="19" />
          <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Viết bài mới
      </button>
    </div>

    <!-- STATS -->
    <div class="stats">
      <button type="button" class="stat-card stat-blue stat-card-btn" :class="{ active: selectedStatus === 'all' }"
        @click="applyNewsStatFilter('all')">
        <div>
          <p>TỔNG BÀI VIẾT</p>
          <b>{{ formatNumber(stats.total) }}</b>
        </div>
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="16" y1="13" x2="8" y2="13" />
            <line x1="16" y1="17" x2="8" y2="17" />
            <polyline points="10 9 9 9 8 9" />
          </svg>
        </div>
      </button>
      <button type="button" class="stat-card stat-green stat-card-btn"
        :class="{ active: selectedStatus === 'published' }" @click="applyNewsStatFilter('published')">
        <div>
          <p>ĐÃ XUẤT BẢN</p>
          <b>{{ formatNumber(stats.published) }}</b>
        </div>
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <polyline points="20 6 9 17 4 12" />
          </svg>
        </div>
      </button>
      <button type="button" class="stat-card stat-purple stat-card-btn" @click="applyNewsStatFilter('all')">
        <div>
          <p>LƯỢT XEM</p>
          <b>{{ formatNumber(stats.views) }}</b>
        </div>
        <div class="stat-icon purple">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
        </div>
      </button>
      <button type="button" class="stat-card stat-amber stat-card-btn" :class="{ active: selectedStatus === 'draft' }"
        @click="applyNewsStatFilter('draft')">
        <div>
          <p>BẢN NHÁP</p>
          <b>{{ formatNumber(stats.draft) }}</b>
        </div>
        <div class="stat-icon amber">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
          </svg>
        </div>
      </button>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <div class="filter-left">
        <!-- Custom Category Dropdown -->
        <div class="custom-dropdown">
          <div class="dropdown-trigger"
            @click.stop="isOpenCategoryDropdown = !isOpenCategoryDropdown; isOpenStatusDropdown = false">
            <span>{{categoryOptions.find(o => o.value === selectedCategory)?.label || 'Tất cả danh mục'}}</span>
            <svg class="chevron" :class="{ open: isOpenCategoryDropdown }" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
          <transition name="fade-slide">
            <ul v-if="isOpenCategoryDropdown" class="dropdown-menu">
              <li v-for="c in categoryOptions" :key="c.value" :class="{ active: selectedCategory === c.value }"
                @click="selectedCategory = c.value; isOpenCategoryDropdown = false">
                {{ c.label }}
              </li>
            </ul>
          </transition>
        </div>

        <!-- Custom Status Dropdown -->
        <div class="custom-dropdown">
          <div class="dropdown-trigger"
            @click.stop="isOpenStatusDropdown = !isOpenStatusDropdown; isOpenCategoryDropdown = false">
            <span>{{statusOptions.find(o => o.value === selectedStatus)?.label || 'Mọi trạng thái'}}</span>
            <svg class="chevron" :class="{ open: isOpenStatusDropdown }" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
          <transition name="fade-slide">
            <ul v-if="isOpenStatusDropdown" class="dropdown-menu">
              <li v-for="s in statusOptions" :key="s.value" :class="{ active: selectedStatus === s.value }"
                @click="selectedStatus = s.value; isOpenStatusDropdown = false">
                {{ s.label }}
              </li>
            </ul>
          </transition>
        </div>
        <button class="btn-advanced" @click="reload(1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="4" y1="6" x2="20" y2="6" />
            <line x1="8" y1="12" x2="16" y2="12" />
            <line x1="11" y1="18" x2="13" y2="18" />
          </svg>
          Làm mới
        </button>
      </div>
      <span class="showing-count">HIỂN THỊ {{ posts.length }}/{{ totalPosts }} BÀI VIẾT</span>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>BÀI VIẾT</th>
            <th>DANH MỤC</th>
            <th>TÁC GIẢ</th>
            <th>THỐNG SỐ</th>
            <th>TRẠNG THÁI</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="empty">Đang tải bài viết...</td>
          </tr>
          <tr v-else-if="posts.length === 0">
            <td colspan="6" class="empty">Không tìm thấy bài viết nào.</td>
          </tr>
          <template v-else>
            <tr v-for="post in posts" :key="post.id">
              <td>
                <div class="post-cell">
                  <img :src="imageUrl(post.hinhanh)" :alt="post.mota_hinhanh || post.tieude" />
                  <div><b>{{ post.tieude }}</b><span>Ngày đăng: {{ formatDate(post.dang_luc || post.created_at)
                      }}</span></div>
                </div>
              </td>
              <td><span class="cat-badge"
                  :style="catStyle[post.danhmuc] || { background: '#e2e8f0', color: '#475569' }">{{ post.danhmuc
                  }}</span></td>
              <td>
                <div class="author-cell">
                  <div class="author-avatar" :style="getAvatarStyle(post.tacgia)">{{ initials(post.tacgia) }}</div>
                  <span>{{ post.tacgia }}</span>
                </div>
              </td>
              <td>
                <div class="stats-cell">
                  <span class="stat-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                    {{ formatNumber(post.luotxem) }}
                  </span>
                </div>
              </td>
              <td><span class="status-badge" :style="statusStyle[post.trangthai] || statusStyle.draft">{{
                statusText[post.trangthai] || post.trangthai }}</span></td>
              <td>
                <div class="actions">
                  <RouterLink v-if="post.trangthai === 'published'" class="act-btn" title="Xem"
                    :to="`/tin-tuc/${post.id}`">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  </RouterLink>
                  <button class="act-btn" title="Sửa" @click="openEditModal(post)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                  <button class="act-btn danger" title="Xóa" @click="removePost(post)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6" />
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                      <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <PhanTrangAdmin v-model:currentPage="currentPage" :total-pages="lastPage" :total-items="totalPosts" :page-size="10"
      item-label="bài viết" @change-page="fetchPosts" />

    <div class="page-footer">© 2026 NEXTGEN ECOSYSTEM • QUẢN LÝ NỘI DUNG</div>

    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay">
        <div class="modal">
          <div class="modal-header">
            <h3>{{ editingPost ? 'Sửa bài viết' : 'Viết bài mới' }}</h3>
            <button class="modal-close" @click="closeModal">×</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>ẢNH THUMBNAIL</label>
              <input ref="fileRef" type="file" accept="image/*" style="display:none" @change="onFileChange" />
              <div v-if="!imgPreview" class="upload-zone" @click="fileRef.click()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="17 8 12 3 7 8" />
                  <line x1="12" y1="3" x2="12" y2="15" />
                </svg>
                <p>Kéo thả hoặc <span>chọn ảnh</span></p><small>PNG, JPG, WEBP - khuyến khích 1200x630px</small>
              </div>
              <div v-else class="img-preview-wrap">
                <img :src="imgPreview" class="img-preview" alt="Preview" />
                <div class="img-actions"><button class="img-change" @click="fileRef.click()">Đổi ảnh</button><button
                    class="img-remove" @click="removeImg">Xóa</button></div>
              </div>
            </div>
            <div class="form-group">
              <label>TIÊU ĐỀ BÀI VIẾT <span class="req">*</span></label>
              <input v-model="form.tieude" placeholder="Nhập tiêu đề bài viết..." />
            </div>
            <div class="form-group">
              <label>ALT ẢNH ĐẠI DIỆN</label>
              <input v-model="form.mota_hinhanh" :placeholder="smartThumbnailAlt" />
              <small class="field-hint">Bỏ trống để hệ thống tự sinh ALT SEO theo tiêu đề, danh mục và từ khóa
                chính.</small>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>DANH MỤC <span class="req">*</span></label>
                <select v-model="form.danhmuc">
                  <option v-for="category in defaultCategories" :key="category" :value="category">{{ category }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>TRẠNG THÁI</label>
                <select v-model="form.trangthai">
                  <option value="draft">Bản nháp</option>
                  <option value="published">Đã xuất bản</option>
                  <option value="scheduled">Sắp xuất bản</option>
                </select>
              </div>
            </div>
            <div class="form-row" :class="{ 'single-column': form.trangthai !== 'scheduled' }">
              <div class="form-group">
                <label>TÁC GIẢ ĐANG ĐĂNG NHẬP <span class="req">*</span></label>
                <input v-model="form.tacgia" readonly placeholder="Tên tài khoản đang đăng nhập" />
              </div>
              <div v-if="form.trangthai === 'scheduled'" class="form-group">
                <label>NGÀY ĐĂNG <span class="req">*</span></label>
                <input v-model="form.dang_luc" type="date" />
              </div>
            </div>
            <div class="form-group">
              <label>NỘI DUNG TÓM TẮT</label>
              <textarea v-model="form.tomtat" rows="3" placeholder="Nhập mô tả ngắn về bài viết..."></textarea>
            </div>
            <div class="form-group">
              <label>NỘI DUNG CHI TIẾT</label>
              <div class="advanced-editor-fields">
                <div class="form-row">
                  <div class="form-group"><label>TAG (phân cách bằng dấu phẩy)</label><input v-model="form.tagsText"
                      placeholder="laptop gaming, tư vấn, RTX" /></div>
                  <div class="form-group"><label>QUY TRÌNH BIÊN TẬP</label><select v-model="form.workflow_status">
                      <option value="draft">Đang viết</option>
                      <option value="review">Chờ duyệt</option>
                      <option value="approved">Đã duyệt</option>
                      <option value="published">Hoàn tất</option>
                    </select></div>
                </div>
                <div class="editor-heading"><span>{{ autosaveState }}</span><button v-if="editingPost" type="button"
                    @click="loadRevisions">Lịch sử phiên bản</button></div>
                <div class="editor-tabs"><button type="button" :class="{ active: editorMode === 'write' }"
                    @click="editorMode = 'write'">Soạn thảo</button><button type="button"
                    :class="{ active: editorMode === 'preview' }" @click="editorMode = 'preview'">Xem trước</button>
                </div>
                <div v-if="editorMode === 'write'" class="format-toolbar"><button type="button"
                    @click="insertMarkdown('## ')">H2</button><button type="button"
                    @click="insertMarkdown('### ')">H3</button><button type="button"
                    @click="insertMarkdown('**', '**')"><b>B</b></button><button type="button"
                    @click="insertMarkdown('*', '*')"><i>I</i></button><button type="button"
                    @click="insertMarkdown('- ')">Danh sách</button><button type="button"
                    @click="insertMarkdown('[', '](https://)')">Liên kết</button></div>
              </div>
              <div class="content-image-tools">
                <input v-model="contentImageAlt" :placeholder="smartContentAlt" />
                <button type="button" :disabled="uploadingContentImage" @click="chooseContentImage">
                  {{ uploadingContentImage ? 'Đang upload...' : 'Chèn ảnh từ máy' }}
                </button>
                <input ref="contentImageRef" type="file" accept="image/*" multiple style="display:none"
                  @change="onContentImageChange" />
              </div>
              <small class="field-hint">Có thể chọn nhiều ảnh cùng lúc. Bỏ trống ALT thì hệ thống tự sinh theo
                SEO.</small>
              <div class="content-quality" :class="{ ready: publishQualityReady }">
                <span>{{ articleWordCount }}/600 từ</span>
                <span>{{ contentImageCount }}/2 ảnh nội dung</span>
                <b>{{ publishQualityReady ? 'Đủ chuẩn xuất bản' : 'Cần bổ sung trước khi xuất bản' }}</b>
              </div>
              <textarea v-show="editorMode === 'write'" ref="contentTextareaRef" v-model="form.noidung" rows="8"
                placeholder="Nhập nội dung bài viết..."></textarea>
              <div v-if="editorMode === 'preview'" class="content-preview">
                <h1>{{ form.tieude || 'Tiêu đề bài viết' }}</h1>
                <p>{{ form.tomtat }}</p>
                <pre>{{ form.noidung || 'Chưa có nội dung.' }}</pre>
              </div>
            </div>
            <details class="seo-panel">
              <summary>SEO và hiển thị nâng cao</summary>
              <div class="form-group"><label>SEO TITLE ({{ form.seo_title.length }}/70)</label><input
                  v-model="form.seo_title" maxlength="70" :placeholder="form.tieude" /></div>
              <div class="form-group"><label>META DESCRIPTION ({{ form.seo_description.length }}/320)</label><textarea
                  v-model="form.seo_description" maxlength="320" rows="3" :placeholder="form.tomtat"></textarea></div>
              <div class="form-row">
                <div class="form-group"><label>TỪ KHÓA SEO</label><input v-model="form.seo_keywords" /></div>
                <div class="form-group"><label>CANONICAL URL</label><input v-model="form.canonical_url" /></div>
              </div>
              <div class="display-options"><label><input v-model="form.noi_bat" type="checkbox" /> Bài nổi
                  bật</label><label><input v-model="form.ghim" type="checkbox" /> Ghim đầu danh
                  sách</label><label><input v-model="form.no_index" type="checkbox" /> Không lập chỉ mục</label></div>
              <div class="form-group"><label>GHI CHÚ PHIÊN BẢN</label><input v-model="form.revision_note" /></div>
            </details>
            <p v-if="formError" class="form-error">{{ formError }}</p>
          </div>
          <div class="modal-footer">
            <button class="btn-draft" :disabled="submitting" @click="submitForm('draft')">Lưu nháp</button>
            <div class="modal-actions">
              <button class="btn-cancel" :disabled="submitting" @click="closeModal">Hủy</button>
              <button class="btn-submit" :disabled="submitting" @click="submitForm()">{{ submitting ? 'Đang lưu...' : 'Lưu bài viết' }}</button>
              <button class="btn-submit" :disabled="submitting" @click="submitForm('published')">Xuất bản</button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="showRevisions" class="modal-overlay revision-overlay" @click.self="showRevisions = false">
        <div class="revision-modal">
          <div class="modal-header">
            <h3>Lịch sử phiên bản</h3><button class="modal-close" @click="showRevisions = false">×</button>
          </div>
          <div class="revision-list">
            <div v-for="revision in revisions" :key="revision.id" class="revision-item">
              <div><b>Phiên bản {{ revision.version }}</b><span>{{ revision.note }} · {{ revision.editor }} · {{
                formatDate(revision.created_at) }}</span></div><button type="button"
                @click="restoreRevision(revision)">Khôi phục</button>
            </div>
            <p v-if="!revisions.length">Chưa có phiên bản.</p>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.editor-heading,
.display-options {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap
}

.editor-heading {
  justify-content: flex-end;
  margin-top: 8px;
  font-size: 12px;
  color: #64748b
}

.editor-heading button {
  border: 1px solid #cbd5e1;
  background: #fff;
  border-radius: 8px;
  padding: 7px 10px;
  cursor: pointer
}

.editor-tabs,
.format-toolbar {
  display: flex;
  gap: 6px;
  margin: 8px 0;
  flex-wrap: wrap
}

.editor-tabs button,
.format-toolbar button {
  border: 1px solid #dbe3ee;
  background: #fff;
  border-radius: 7px;
  padding: 7px 10px;
  cursor: pointer
}

.editor-tabs button.active {
  background: #2563eb;
  color: #fff;
  border-color: #2563eb
}

.content-preview {
  min-height: 260px;
  border: 1px solid #dbe3ee;
  border-radius: 10px;
  padding: 22px;
  background: #fff
}

.content-preview pre {
  white-space: pre-wrap;
  font: inherit;
  line-height: 1.7
}

.seo-panel {
  border: 1px solid #dbe3ee;
  border-radius: 10px;
  padding: 14px;
  margin-top: 16px
}

.seo-panel summary {
  font-weight: 700;
  cursor: pointer;
  margin-bottom: 14px
}

.display-options label {
  font-size: 13px;
  color: #334155
}

.revision-overlay {
  z-index: 10001
}

.revision-modal {
  background: #fff;
  border-radius: 14px;
  width: min(680px, 92vw);
  max-height: 80vh;
  overflow: auto
}

.revision-list {
  padding: 18px
}

.revision-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #eef2f7;
  padding: 14px 0;
  gap: 16px
}

.revision-item span {
  display: block;
  color: #64748b;
  font-size: 12px;
  margin-top: 4px
}

.revision-item button {
  border: 0;
  border-radius: 8px;
  background: #e8f0ff;
  color: #1d4ed8;
  padding: 8px 12px;
  cursor: pointer
}

.page {
  background: #f5f7fb;
  min-height: 100vh;
  font-family: sans-serif;
  padding-bottom: 0;
}

/* TOPBAR */
.topbar {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding: 12px 32px;
  background: white;
  border-bottom: 1px solid #f1f5f9;
}

.search-box {
  position: relative;
  width: 240px;
}

.search-box svg {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 14px;
  height: 14px;
  color: #94a3b8;
  pointer-events: none;
}

.search-box input {
  width: 100%;
  padding: 8px 12px 8px 32px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  font-size: 13px;
  color: #0f172a;
  outline: none;
  background: #f8fafc;
}

.search-box input:focus {
  border-color: #2563eb;
  background: white;
}

.topbar-right {
  display: none !important;
}

.icon-btn {
  background: none;
  border: none;
  font-size: 15px;
  cursor: pointer;
  padding: 6px 8px;
  border-radius: 8px;
}

.page {
  background: #f5f7fb;
  min-height: 100vh;
  font-family: sans-serif;
}

.topbar {
  align-items: center;
  background: white;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  padding: 12px 32px;
}

.search-box {
  position: relative;
  width: 260px;
}

.search-box svg {
  color: #94a3b8;
  height: 14px;
  left: 10px;
  pointer-events: none;
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 14px;
}

.search-box input {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #0f172a;
  font-size: 13px;
  outline: none;
  padding: 8px 12px 8px 32px;
  width: 100%;
}

.topbar-right,
.admin-wrap,
.filter-left,
.actions,
.author-cell,
.stat-item,
.modal-actions {
  align-items: center;
  display: flex;
  gap: 8px;
}

.icon-btn {
  background: none;
  border: 0;
  border-radius: 8px;
  cursor: pointer;
  font-size: 15px;
  padding: 6px 8px;
}

.icon-btn:hover {
  background: #f1f5f9;
}

.admin-text b {
  color: #0f172a;
  display: block;
  font-size: 12px;
  font-weight: 600;
}

.admin-text span,
.post-cell span {
  color: #94a3b8;
  font-size: 11px;
}

.avatar-circle {
  align-items: center;
  background: linear-gradient(135deg, #2563eb, #2563eb);
  border-radius: 50%;
  color: white;
  display: flex;
  font-size: 11px;
  font-weight: 700;
  height: 34px;
  justify-content: center;
  width: 34px;
}

.breadcrumb {
  align-items: center;
  color: #94a3b8;
  display: flex;
  font-size: 12px;
  gap: 6px;
  padding: 16px 32px 0;
}

.crumb-active {
  color: #2563eb;
  font-weight: 500;
}

/* PAGE HEADER */
.page-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
  padding: 12px 32px 20px;
}

.page-header .search-box {
  width: 300px;
}

.page-header h1 {
  font-size: 28px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 6px;
  letter-spacing: -0.02em;
}

.page-header p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
  max-width: 460px;
  line-height: 1.5;
}

.btn-new {
  display: flex;
  align-items: center;
  gap: 7px;
  white-space: nowrap;
  padding: 11px 20px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #2563eb);
  color: white;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.2s;
}

.btn-new svg {
  width: 14px;
  height: 14px;
}

.btn-new:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

/* STATS */
.stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(220px, 1fr));
  gap: 20px;
  padding: 0 32px 20px;
}

.stat-card {
  min-height: 136px;
  border-radius: 16px;
  border: none;
  padding: 26px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
  color: #fff;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
}

.stat-card-btn {
  width: 100%;
  text-align: left;
  font-family: inherit;
  cursor: pointer;
  transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
}

.stat-card-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 18px 34px rgba(15, 23, 42, .2);
  filter: saturate(1.05);
}

.stat-card-btn:focus-visible {
  outline: 3px solid rgba(37, 99, 235, .28);
  outline-offset: 3px;
}

.stat-card-btn.active {
  box-shadow: 0 18px 34px rgba(37, 99, 235, .28);
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
  background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
}

.stat-card.stat-green {
  background: linear-gradient(135deg, #c2410c 0%, #f97316 100%);
}

.stat-card.stat-purple {
  background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
}

.stat-card.stat-amber {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
}

.stat-card p {
  font-size: 12px;
  font-weight: 800;
  color: rgba(255, 255, 255, .88);
  letter-spacing: 0.03em;
  margin: 0 0 20px;
  text-transform: capitalize;
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
}

.stat-icon svg {
  width: 24px;
  height: 24px;
}

.stat-icon.blue,
.stat-icon.green,
.stat-icon.purple,
.stat-icon.amber {
  background: rgba(255, 255, 255, .18);
  color: #fff;
}

/* FILTER */
.filter-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px 14px;
}

.filter-left {
  display: flex;
  gap: 8px;
  align-items: center;
}

/* ── Custom Premium Dropdown ── */
.custom-dropdown {
  position: relative;
  display: inline-block;
  min-width: 175px;
  user-select: none;
}

.dropdown-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 14px;
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

.dropdown-trigger:hover {
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.06);
}

.dropdown-trigger .chevron {
  width: 14px;
  height: 14px;
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
  font-weight: 600;
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

.btn-advanced {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 13px;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-advanced svg {
  width: 14px;
  height: 14px;
}

.btn-advanced:hover {
  border-color: #2563eb;
  color: #2563eb;
}

.showing-count {
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.06em;
}

/* TABLE */
.table-wrap {
  margin: 0 32px;
  background: white;
  border-radius: 14px;
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

table {
  width: 100%;
  border-collapse: collapse;
}

.page-header {
  align-items: flex-start;
  display: flex;
  justify-content: space-between;
  padding: 12px 32px 20px;
}

.page-header h1 {
  color: #0f172a;
  font-size: 28px;
  font-weight: 800;
  margin: 0 0 6px;
}

.page-header p {
  color: #64748b;
  font-size: 13px;
  line-height: 1.5;
  margin: 0;
  max-width: 470px;
}

.btn-new,
.btn-submit {
  background: linear-gradient(135deg, #2563eb, #2563eb);
  border: 0;
  border-radius: 10px;
  color: white;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  padding: 11px 20px;
}

.btn-new {
  align-items: center;
  display: flex;
  gap: 7px;
}

.btn-new svg {
  height: 14px;
  width: 14px;
}

.stats {
  display: grid;
  gap: 20px;
  grid-template-columns: repeat(4, minmax(220px, 1fr));
  padding: 0 32px 20px;
}

.stat-card {
  align-items: center;
  border: 1px solid transparent;
  border-radius: 16px;
  display: flex;
  justify-content: space-between;
  min-height: 136px;
  overflow: hidden;
  padding: 26px 28px;
  position: relative;
  box-shadow: 0 12px 26px rgba(15, 23, 42, .12);
  color: #fff;
}

.stat-card::after {
  content: '';
  position: absolute;
  width: 150px;
  height: 150px;
  border-radius: 999px;
  right: -28px;
  top: -54px;
  background: rgba(255, 255, 255, .13);
  pointer-events: none;
}

.stat-card p {
  color: rgba(255, 255, 255, .88);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .03em;
  margin: 0 0 20px;
  text-transform: capitalize;
}

.stat-card b {
  color: #fff;
  font-size: 34px;
  line-height: 1;
  font-weight: 800;
}

.stat-icon {
  align-items: center;
  border-radius: 14px;
  display: flex;
  height: 48px;
  justify-content: center;
  width: 48px;
}

.stat-icon svg {
  height: 24px;
  width: 24px;
}

.blue {
  background: #dbeafe;
  color: #2563eb;
}

.green {
  background: #dcfce7;
  color: #2563eb;
}

.purple {
  background: #ede9fe;
  color: #2563eb;
}

.amber {
  background: #fef9c3;
  color: #d97706;
}

.filter-bar {
  align-items: center;
  display: flex;
  justify-content: space-between;
  padding: 0 32px 14px;
}

.filter-left select,
.btn-advanced {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #334155;
  cursor: pointer;
  font-size: 13px;
  padding: 8px 14px;
}

.btn-advanced {
  align-items: center;
  display: flex;
  gap: 6px;
}

.btn-advanced svg {
  height: 14px;
  width: 14px;
}

.showing-count {
  color: #94a3b8;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .06em;
}

.table-wrap {
  background: white;
  border: 1px solid #f1f5f9;
  border-radius: 14px;
  margin: 0 32px;
  overflow: hidden;
}

table {
  border-collapse: collapse;
  width: 100%;
}

thead tr {
  background: #f8fafc;
}

thead th {
  border-bottom: 1px solid #f1f5f9;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .07em;
  padding: 12px 16px;
  text-align: left;
}

tbody tr {
  border-bottom: 1px solid #f8fafc;
}

tbody tr:hover {
  background: #fafbff;
}

tbody td {
  color: #334155;
  font-size: 13px;
  padding: 16px;
  vertical-align: middle;
}

.empty {
  color: #94a3b8;
  padding: 50px !important;
  text-align: center;
}

.post-cell {
  align-items: center;
  display: flex;
  gap: 12px;
}

.post-cell img {
  border-radius: 8px;
  flex-shrink: 0;
  height: 52px;
  object-fit: cover;
  width: 52px;
}

.post-cell b {
  color: #0f172a;
  display: block;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 3px;
}

.cat-badge,
.status-badge {
  display: inline-block;
  font-size: 11px;
  font-weight: 600;
  padding: 4px 10px;
}

.cat-badge {
  border-radius: 6px;
}

.status-badge {
  border-radius: 20px;
}

.author-avatar {
  align-items: center;
  border-radius: 50%;
  display: flex;
  flex-shrink: 0;
  font-size: 10px;
  font-weight: 700;
  height: 30px;
  justify-content: center;
  width: 30px;
}

.stats-cell {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.stat-item {
  color: #64748b;
  font-size: 12px;
}

.stat-item svg {
  height: 12px;
  width: 12px;
}

.act-btn {
  align-items: center;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 7px;
  color: #64748b;
  cursor: pointer;
  display: flex;
  height: 30px;
  justify-content: center;
  text-decoration: none;
  width: 30px;
}

.act-btn svg {
  height: 13px;
  width: 13px;
}

.act-btn:hover {
  background: #f1f5f9;
  color: #2563eb;
}

.act-btn.danger:hover {
  background: #fee2e2;
  color: #ef4444;
}

.table-footer {
  align-items: center;
  display: flex;
  justify-content: space-between;
  padding: 16px 32px;
}

.showing {
  color: #64748b;
  font-size: 13px;
}

.pagination {
  display: flex;
  gap: 6px;
}

.pagination button {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #334155;
  cursor: pointer;
  font-size: 13px;
  height: 34px;
  width: 34px;
}

.pagination button:disabled {
  cursor: not-allowed;
  opacity: .5;
}

.pagination .active {
  background: #2563eb;
  border-color: #2563eb;
  color: white;
}

.page-footer {
  border-top: 1px solid #f1f5f9;
  color: #94a3b8;
  font-size: 11px;
  letter-spacing: .06em;
  margin-top: 8px;
  padding: 20px;
  text-align: center;
}

.modal-overlay {
  align-items: center;
  background: rgba(15, 23, 42, .55);
  display: flex;
  inset: 0;
  justify-content: center;
  padding: 20px;
  position: fixed;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 16px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, .18);
  max-height: 90vh;
  max-width: 640px;
  overflow-y: auto;
  width: 100%;
}

.modal-header,
.modal-footer {
  align-items: center;
  background: white;
  display: flex;
  justify-content: space-between;
  padding: 18px 24px;
  position: sticky;
  z-index: 1;
}

.modal-header {
  border-bottom: 1px solid #f1f5f9;
  top: 0;
}

.modal-footer {
  border-top: 1px solid #f1f5f9;
  bottom: 0;
  gap: 10px;
}

.modal-header h3 {
  color: #0f172a;
  font-size: 17px;
  margin: 0;
}

.modal-close {
  background: none;
  border: 0;
  color: #94a3b8;
  cursor: pointer;
  font-size: 22px;
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 20px 24px;
}

.upload-zone {
  align-items: center;
  background: #f8fafc;
  border: 1.5px dashed #cbd5e1;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 28px;
  text-align: center;
}

.upload-zone svg {
  color: #94a3b8;
  height: 32px;
  width: 32px;
}

.upload-zone p {
  color: #475569;
  font-size: 13px;
  margin: 0;
}

.upload-zone span {
  color: #2563eb;
  font-weight: 600;
}

.upload-zone small {
  color: #94a3b8;
  font-size: 11px;
}

.img-preview-wrap {
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  display: flex;
  gap: 14px;
  padding: 12px;
}

.img-preview {
  border-radius: 6px;
  height: 68px;
  object-fit: cover;
  width: 120px;
}

.img-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.img-change,
.img-remove,
.btn-draft,
.btn-cancel {
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  padding: 8px 14px;
}

.img-change,
.btn-draft,
.btn-cancel {
  background: white;
  border: 1px solid #e2e8f0;
  color: #475569;
}

.img-remove {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #ef4444;
}

.form-row {
  display: grid;
  gap: 14px;
  grid-template-columns: 1fr 1fr;
}

.form-row.single-column {
  grid-template-columns: 1fr;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .08em;
}

.req {
  color: #ef4444;
}

.form-group input,
.form-group select,
.form-group textarea {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #0f172a;
  font-family: sans-serif;
  font-size: 13px;
  outline: none;
  padding: 10px 12px;
}

.form-group input[readonly] {
  background: #eef2f7;
  color: #475569;
  cursor: not-allowed;
}

.form-group textarea {
  resize: vertical;
}

.content-image-tools {
  display: grid;
  gap: 8px;
  grid-template-columns: 1fr auto;
}

.content-image-tools input {
  min-width: 0;
}

.content-image-tools button {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  color: #1d4ed8;
  cursor: pointer;
  font-size: 12px;
  font-weight: 700;
  padding: 10px 14px;
  white-space: nowrap;
}

.content-image-tools button:hover:not(:disabled) {
  background: #dbeafe;
}

.field-hint {
  color: #64748b;
  font-size: 11px;
  line-height: 1.5;
}

.content-quality {
  align-items: center;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 9px;
  color: #9a3412;
  display: flex;
  flex-wrap: wrap;
  font-size: 11px;
  gap: 10px;
  margin: 8px 0;
  padding: 9px 11px;
}

.content-quality span {
  background: rgba(255, 255, 255, .72);
  border-radius: 999px;
  padding: 4px 8px;
}

.content-quality b {
  margin-left: auto;
}

.content-quality.ready {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.form-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #ef4444;
  font-size: 12px;
  margin: 0;
  padding: 9px 12px;
}

button:disabled {
  cursor: not-allowed;
  opacity: .65;
}

@media (max-width: 900px) {
  .stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {

  .topbar,
  .page-header,
  .filter-bar,
  .table-footer {
    padding-left: 16px;
    padding-right: 16px;
  }

  .page-header,
  .filter-bar,
  .table-footer,
  .modal-footer {
    align-items: flex-start;
    flex-direction: column;
    gap: 12px;
  }

  .breadcrumb {
    padding-left: 16px;
  }

  .stats {
    grid-template-columns: 1fr;
    padding: 0 16px 16px;
  }

  .table-wrap {
    margin: 0 16px;
    overflow-x: auto;
  }

  table {
    min-width: 760px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .content-image-tools {
    grid-template-columns: 1fr;
  }
}
</style>
