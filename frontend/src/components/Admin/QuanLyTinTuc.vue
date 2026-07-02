<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'
import { getUser, updateUser } from '@/services/auth'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'

const searchQuery = ref('')
const selectedCategory = ref('all')
const selectedStatus = ref('all')
const showModal = ref(false)
const formError = ref('')

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
  const merged = [...new Set([...defaultCategories, ...posts.value.map((post) => post.category).filter(Boolean)])]
  return [{ value: 'all', label: 'Tất cả danh mục' }, ...merged.map((name) => ({ value: name, label: name }))]
})

const catStyle = {
  'Công nghệ': { bg: '#dbeafe', color: '#1d4ed8' },
  'Sự kiện': { bg: '#dcfce7', color: '#15803d' },
  'Sản phẩm': { bg: '#fef9c3', color: '#a16207' },
  'Nội bộ': { bg: '#ede9fe', color: '#6d28d9' },
}
const statusStyle = {
  published: { bg: '#dcfce7', color: '#15803d' },
  scheduled: { bg: '#fef9c3', color: '#a16207' },
  draft: { bg: '#f1f5f9', color: '#64748b' },
}
const avatarColors = ['#dbeafe', '#dcfce7', '#ede9fe', '#fef9c3', '#fee2e2']
const avatarText = ['#1d4ed8', '#15803d', '#6d28d9', '#a16207', '#b91c1c']
const placeholderImage = 'https://via.placeholder.com/160x100?text=News'
const currentAuthorName = computed(() => String(currentUser.value?.name || '').trim() || 'Admin')

const defaultForm = () => ({
  title: '',
  category: 'Công nghệ',
  author: currentAuthorName.value,
  status: 'draft',
  published_at: '',
  excerpt: '',
  content: '',
  image: '',
  image_alt: '',
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

const mainSeoKeyword = computed(() => extractSeoKeyword(form.value.title) || cleanText(form.value.category) || 'laptop')
const shortArticleTitle = computed(() => cleanText((form.value.title || '').split(/[:|-]/)[0]).slice(0, 90))

const buildSmartAlt = (type = 'content') => {
  const category = cleanText(form.value.category).toLowerCase() || 'tin tức công nghệ'
  const keyword = mainSeoKeyword.value
  const title = shortArticleTitle.value

  if (type === 'thumbnail') {
    return `Ảnh đại diện ${category} về ${keyword}`
  }

  return title
    ? `Ảnh minh họa ${category} về ${keyword} trong bài ${title}`
    : `Ảnh minh họa ${category} về ${keyword}`
}

const smartThumbnailAlt = computed(() => buildSmartAlt('thumbnail'))
const smartContentAlt = computed(() => buildSmartAlt('content'))

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
      form.value.author = user.name
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
    if (selectedCategory.value !== 'all') params.category = selectedCategory.value
    if (selectedStatus.value !== 'all') params.status = selectedStatus.value

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

watch(() => form.value.status, (status) => {
  if (status !== 'scheduled') {
    form.value.published_at = ''
  }
})

watch(currentAuthorName, (name) => {
  form.value.author = name
})

const onFileChange = (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (readerEvent) => {
    imgPreview.value = readerEvent.target.result
    form.value.image = readerEvent.target.result
  }
  reader.readAsDataURL(file)
}
const insertContentAtCursor = async (text) => {
  const textarea = contentTextareaRef.value
  const currentContent = form.value.content || ''

  if (!textarea) {
    form.value.content = `${currentContent}${currentContent ? '\n\n' : ''}${text}\n\n`
    return
  }

  const start = textarea.selectionStart ?? currentContent.length
  const end = textarea.selectionEnd ?? start
  const before = currentContent.slice(0, start)
  const after = currentContent.slice(end)
  const prefix = before && !before.endsWith('\n') ? '\n\n' : ''
  const suffix = after && !after.startsWith('\n') ? '\n\n' : '\n\n'

  form.value.content = `${before}${prefix}${text}${suffix}${after}`

  await nextTick()
  const cursorPosition = (before + prefix + text + suffix).length
  textarea.focus()
  textarea.setSelectionRange(cursorPosition, cursorPosition)
}
const chooseContentImage = () => {
  contentImageRef.value?.click()
}
const onContentImageChange = (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = async (readerEvent) => {
    uploadingContentImage.value = true
    try {
      const alt = contentImageAlt.value.trim() || smartContentAlt.value
      const { data } = await api.post('/admin/news/upload-image', {
        image: readerEvent.target.result,
        alt,
        title: form.value.title,
        category: form.value.category,
      })

      await insertContentAtCursor(data.data.markdown)
      contentImageAlt.value = ''
      swal.success('Thành công', 'Đã chèn ảnh vào nội dung bài viết.')
    } catch (error) {
      console.error('Lỗi upload ảnh nội dung:', error)
      swal.error('Lỗi', error.response?.data?.message || 'Không thể upload ảnh nội dung.')
    } finally {
      uploadingContentImage.value = false
      if (contentImageRef.value) contentImageRef.value.value = ''
    }
  }
  reader.readAsDataURL(file)
}
const removeImg = () => {
  imgPreview.value = ''
  form.value.image = ''
  if (fileRef.value) fileRef.value.value = ''
}
const openModal = () => {
  editingPost.value = null
  form.value = defaultForm()
  imgPreview.value = ''
  contentImageAlt.value = ''
  formError.value = ''
  showModal.value = true
}
const openEditModal = (post) => {
  editingPost.value = post
  form.value = {
    title: post.title || '',
    category: post.category || 'Công nghệ',
    author: currentAuthorName.value,
    status: post.status || 'draft',
    published_at: toDateInput(post.published_at),
    excerpt: post.excerpt || '',
    content: post.content || '',
    image: post.image || '',
    image_alt: post.image_alt || post.title || '',
  }
  imgPreview.value = post.image ? imageUrl(post.image) : ''
  contentImageAlt.value = ''
  formError.value = ''
  showModal.value = true
}
const closeModal = () => {
  showModal.value = false
  submitting.value = false
}
const validateForm = () => {
  if (!form.value.title.trim()) return 'Vui lòng nhập tiêu đề bài viết.'
  if (!form.value.category.trim()) return 'Vui lòng chọn danh mục.'
  if (!currentAuthorName.value) return 'Không tìm thấy tên tài khoản đang đăng nhập.'
  if (form.value.status === 'scheduled' && !form.value.published_at) return 'Vui lòng chọn ngày đăng cho bài viết hẹn lịch.'
  return ''
}
const submitForm = async (forcedStatus = null) => {
  if (forcedStatus) form.value.status = forcedStatus
  formError.value = validateForm()
  if (formError.value) return

  submitting.value = true
  const payload = {
    title: form.value.title.trim(),
    category: form.value.category.trim(),
    author: currentAuthorName.value,
    status: form.value.status,
    published_at: form.value.status === 'scheduled' ? form.value.published_at : null,
    excerpt: form.value.excerpt.trim() || null,
    content: form.value.content.trim() || null,
    image: form.value.image || null,
    image_alt: form.value.image_alt.trim() || smartThumbnailAlt.value,
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
  const confirmed = await swal.confirm('Xóa bài viết?', `Bài viết "${post.title}" sẽ bị xóa khỏi hệ thống.`, 'Xóa', 'Hủy')
  if (!confirmed) return

  try {
    await api.delete(`/admin/news/${post.id}`)
    swal.success('Thành công', 'Đã xóa bài viết.')
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
    <div class="topbar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />
        </svg>
        <input v-model="searchQuery" placeholder="Tìm kiếm bài viết, tác giả..." />
      </div>
      <div class="topbar-right">
        <button class="icon-btn">🔔</button>
        <button class="icon-btn">🌙</button>
        <button class="icon-btn">?</button>
        <div class="admin-wrap">
          <div class="admin-text"><b>{{ currentAuthorName }}</b><span>Quản trị viên</span></div>
          <div class="avatar-circle">{{ initials(currentAuthorName) }}</div>
        </div>
      </div>
    </div>

    <div class="breadcrumb">
      <span>Hệ thống</span><span class="sep">›</span><span class="crumb-active">Quản lý tin tức</span>
    </div>

    <div class="page-header">
      <div>
        <h1>Bài viết &amp; Tin tức</h1>
        <p>Quản lý nội dung truyền thông, cập nhật công nghệ và thông tin nội bộ của VinaTech.</p>
      </div>
      <button class="btn-new" @click="openModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Viết bài mới
      </button>
    </div>

        <!-- STATS -->
        <div class="stats">
            <div class="stat-card stat-blue">
                <div>
                    <p>TỔNG BÀI VIẾT</p>
                    <b>{{ formatNumber(stats.total) }}</b>
                </div>
                <div class="stat-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card stat-green">
                <div>
                    <p>ĐÃ XUẤT BẢN</p>
                    <b>{{ formatNumber(stats.published) }}</b>
                </div>
                <div class="stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card stat-purple">
                <div>
                    <p>LƯỢT XEM</p>
                    <b>{{ formatNumber(stats.views) }}</b>
                </div>
                <div class="stat-icon purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card stat-amber">
                <div>
                    <p>BẢN NHÁP</p>
                    <b>{{ formatNumber(stats.draft) }}</b>
                </div>
                <div class="stat-icon amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-bar">
            <div class="filter-left">
                <!-- Custom Category Dropdown -->
                <div class="custom-dropdown">
                    <div class="dropdown-trigger" @click.stop="isOpenCategoryDropdown = !isOpenCategoryDropdown; isOpenStatusDropdown = false">
                        <span>{{ categoryOptions.find(o => o.value === selectedCategory)?.label || 'Tất cả danh mục' }}</span>
                        <svg class="chevron" :class="{ open: isOpenCategoryDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <transition name="fade-slide">
                        <ul v-if="isOpenCategoryDropdown" class="dropdown-menu">
                            <li v-for="c in categoryOptions" :key="c.value"
                                :class="{ active: selectedCategory === c.value }"
                                @click="selectedCategory = c.value; isOpenCategoryDropdown = false">
                                {{ c.label }}
                            </li>
                        </ul>
                    </transition>
                </div>

                <!-- Custom Status Dropdown -->
                <div class="custom-dropdown">
                    <div class="dropdown-trigger" @click.stop="isOpenStatusDropdown = !isOpenStatusDropdown; isOpenCategoryDropdown = false">
                        <span>{{ statusOptions.find(o => o.value === selectedStatus)?.label || 'Mọi trạng thái' }}</span>
                        <svg class="chevron" :class="{ open: isOpenStatusDropdown }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <transition name="fade-slide">
                        <ul v-if="isOpenStatusDropdown" class="dropdown-menu">
                            <li v-for="s in statusOptions" :key="s.value"
                                :class="{ active: selectedStatus === s.value }"
                                @click="selectedStatus = s.value; isOpenStatusDropdown = false">
                                {{ s.label }}
                            </li>
                        </ul>
                    </transition>
                </div>
                <button class="btn-advanced" @click="reload(1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/>
                        <line x1="11" y1="18" x2="13" y2="18"/>
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
            <th>BÀI VIẾT</th><th>DANH MỤC</th><th>TÁC GIẢ</th><th>THỐNG SỐ</th><th>TRẠNG THÁI</th><th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="6" class="empty">Đang tải bài viết...</td></tr>
          <tr v-else-if="posts.length === 0"><td colspan="6" class="empty">Không tìm thấy bài viết nào.</td></tr>
          <template v-else>
            <tr v-for="post in posts" :key="post.id">
              <td>
                <div class="post-cell">
                <img :src="imageUrl(post.image)" :alt="post.image_alt || post.title" />
                  <div><b>{{ post.title }}</b><span>Ngày đăng: {{ formatDate(post.published_at || post.created_at) }}</span></div>
                </div>
              </td>
              <td><span class="cat-badge" :style="catStyle[post.category] || { background: '#e2e8f0', color: '#475569' }">{{ post.category }}</span></td>
              <td>
                <div class="author-cell">
                  <div class="author-avatar" :style="getAvatarStyle(post.author)">{{ initials(post.author) }}</div>
                  <span>{{ post.author }}</span>
                </div>
              </td>
              <td>
                <div class="stats-cell">
                  <span class="stat-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg>
                    {{ formatNumber(post.views) }}
                  </span>
                </div>
              </td>
              <td><span class="status-badge" :style="statusStyle[post.status] || statusStyle.draft">{{ statusText[post.status] || post.status }}</span></td>
              <td>
                <div class="actions">
                  <RouterLink v-if="post.status === 'published'" class="act-btn" title="Xem" :to="`/tin-tuc/${post.id}`">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg>
                  </RouterLink>
                  <button class="act-btn" title="Sửa" @click="openEditModal(post)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" /></svg>
                  </button>
                  <button class="act-btn danger" title="Xóa" @click="removePost(post)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" /></svg>
                  </button>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div class="table-footer">
      <span class="showing">Hiển thị trang {{ currentPage }} trên {{ lastPage }}</span>
      <div class="pagination">
        <button :disabled="currentPage <= 1" @click="fetchPosts(currentPage - 1)">‹</button>
        <button class="active">{{ currentPage }}</button>
        <button :disabled="currentPage >= lastPage" @click="fetchPosts(currentPage + 1)">›</button>
      </div>
    </div>

    <div class="page-footer">© 2026 VINATECH ECOSYSTEM • QUẢN LÝ NỘI DUNG</div>

    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" /></svg>
                <p>Kéo thả hoặc <span>chọn ảnh</span></p><small>PNG, JPG, WEBP - khuyến khích 1200x630px</small>
              </div>
              <div v-else class="img-preview-wrap">
                <img :src="imgPreview" class="img-preview" alt="Preview" />
                <div class="img-actions"><button class="img-change" @click="fileRef.click()">Đổi ảnh</button><button class="img-remove" @click="removeImg">Xóa</button></div>
              </div>
            </div>
            <div class="form-group">
              <label>TIÊU ĐỀ BÀI VIẾT <span class="req">*</span></label>
              <input v-model="form.title" placeholder="Nhập tiêu đề bài viết..." />
            </div>
            <div class="form-group">
              <label>ALT ẢNH ĐẠI DIỆN</label>
              <input v-model="form.image_alt" :placeholder="smartThumbnailAlt" />
              <small class="field-hint">Bỏ trống để hệ thống tự sinh ALT SEO theo tiêu đề, danh mục và từ khóa chính.</small>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>DANH MỤC <span class="req">*</span></label>
                <select v-model="form.category"><option v-for="category in defaultCategories" :key="category" :value="category">{{ category }}</option></select>
              </div>
              <div class="form-group">
                <label>TRẠNG THÁI</label>
                <select v-model="form.status"><option value="draft">Bản nháp</option><option value="published">Đã xuất bản</option><option value="scheduled">Sắp xuất bản</option></select>
              </div>
            </div>
            <div class="form-row" :class="{ 'single-column': form.status !== 'scheduled' }">
              <div class="form-group">
                <label>TÁC GIẢ ĐANG ĐĂNG NHẬP <span class="req">*</span></label>
                <input v-model="form.author" readonly placeholder="Tên tài khoản đang đăng nhập" />
              </div>
              <div v-if="form.status === 'scheduled'" class="form-group">
                <label>NGÀY ĐĂNG <span class="req">*</span></label>
                <input v-model="form.published_at" type="date" />
              </div>
            </div>
            <div class="form-group">
              <label>NỘI DUNG TÓM TẮT</label>
              <textarea v-model="form.excerpt" rows="3" placeholder="Nhập mô tả ngắn về bài viết..."></textarea>
            </div>
            <div class="form-group">
              <label>NỘI DUNG CHI TIẾT</label>
              <div class="content-image-tools">
                <input
                  v-model="contentImageAlt"
                  :placeholder="smartContentAlt"
                />
                <button type="button" :disabled="uploadingContentImage" @click="chooseContentImage">
                  {{ uploadingContentImage ? 'Đang upload...' : 'Chèn ảnh từ máy' }}
                </button>
                <input
                  ref="contentImageRef"
                  type="file"
                  accept="image/*"
                  style="display:none"
                  @change="onContentImageChange"
                />
              </div>
              <small class="field-hint">Bỏ trống ALT thì hệ thống tự sinh theo SEO. Ảnh sẽ được chèn vào nội dung theo dạng ![ALT ảnh](đường-dẫn-ảnh) và hiển thị đúng trong khung bài viết.</small>
              <textarea
                ref="contentTextareaRef"
                v-model="form.content"
                rows="8"
                placeholder="Nhập nội dung bài viết..."
              ></textarea>
            </div>
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
    </Teleport>
  </div>
</template>

<style scoped>
* { box-sizing: border-box; }

.page {
    background: #f5f7fb; min-height: 100vh;
    font-family: sans-serif; padding-bottom: 0;
}

/* TOPBAR */
.topbar {
    display: flex; align-items: center; justify-content: flex-start;
    padding: 12px 32px; background: white; border-bottom: 1px solid #f1f5f9;
}
.search-box { position: relative; width: 240px; }
.search-box svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #94a3b8; pointer-events: none; }
.search-box input { width: 100%; padding: 8px 12px 8px 32px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; color: #0f172a; outline: none; background: #f8fafc; }
.search-box input:focus { border-color: #2563eb; background: white; }

.topbar-right { display: none !important; }
.icon-btn { background: none; border: none; font-size: 15px; cursor: pointer; padding: 6px 8px; border-radius: 8px; }
.page { background: #f5f7fb; min-height: 100vh; font-family: sans-serif; }
.topbar { align-items: center; background: white; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; padding: 12px 32px; }
.search-box { position: relative; width: 260px; }
.search-box svg { color: #94a3b8; height: 14px; left: 10px; pointer-events: none; position: absolute; top: 50%; transform: translateY(-50%); width: 14px; }
.search-box input { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; color: #0f172a; font-size: 13px; outline: none; padding: 8px 12px 8px 32px; width: 100%; }
.topbar-right, .admin-wrap, .filter-left, .actions, .author-cell, .stat-item, .modal-actions { align-items: center; display: flex; gap: 8px; }
.icon-btn { background: none; border: 0; border-radius: 8px; cursor: pointer; font-size: 15px; padding: 6px 8px; }
.icon-btn:hover { background: #f1f5f9; }
.admin-text b { color: #0f172a; display: block; font-size: 12px; font-weight: 600; }
.admin-text span, .post-cell span { color: #94a3b8; font-size: 11px; }
.avatar-circle { align-items: center; background: linear-gradient(135deg,#2563eb,#4f46e5); border-radius: 50%; color: white; display: flex; font-size: 11px; font-weight: 700; height: 34px; justify-content: center; width: 34px; }
.breadcrumb { align-items: center; color: #94a3b8; display: flex; font-size: 12px; gap: 6px; padding: 16px 32px 0; }
.crumb-active { color: #2563eb; font-weight: 500; }

/* PAGE HEADER */
.page-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 12px 32px 20px; }
.page-header h1 { font-size: 28px; font-weight: 800; color: #0f172a; margin: 0 0 6px; letter-spacing: -0.02em; }
.page-header p { font-size: 13px; color: #64748b; margin: 0; max-width: 460px; line-height: 1.5; }

.btn-new {
    display: flex; align-items: center; gap: 7px; white-space: nowrap;
    padding: 11px 20px; border-radius: 10px; border: none;
    background: linear-gradient(135deg,#2563eb,#4f46e5);
    color: white; font-size: 13px; font-weight: 600; cursor: pointer; transition: opacity 0.2s, transform 0.2s;
}
.btn-new svg { width: 14px; height: 14px; }
.btn-new:hover { opacity: 0.9; transform: translateY(-1px); }

/* STATS */
.stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; padding: 0 32px 20px; }
.stat-card {
    border-radius: 14px;
    border: none;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    color: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
}
.stat-card::after {
    content: '';
    position: absolute;
    width: 120px;
    height: 120px;
    border-radius: 999px;
    right: -28px;
    top: -28px;
    background: rgba(255, 255, 255, 0.12);
}
.stat-card.stat-blue { background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); }
.stat-card.stat-green { background: linear-gradient(135deg, #c2410c 0%, #f97316 100%); }
.stat-card.stat-purple { background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); }
.stat-card.stat-amber { background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); }
.stat-card p { font-size: 10px; font-weight: 700; color: rgba(255,255,255,.82); letter-spacing: 0.08em; margin: 0 0 6px; }
.stat-card b { font-size: 24px; font-weight: 800; color: #fff; }
.stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-icon svg { width: 20px; height: 20px; }
.stat-icon.blue,
.stat-icon.green,
.stat-icon.purple,
.stat-icon.amber { background: rgba(255,255,255,.18); color: #fff; }

/* FILTER */
.filter-bar { display: flex; align-items: center; justify-content: space-between; padding: 0 32px 14px; }
.filter-left { display: flex; gap: 8px; align-items: center; }
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
    box-shadow: 0 4px 12px rgba(37,99,235,0.06);
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
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 8px; border: 1px solid #e2e8f0;
    background: white; font-size: 13px; color: #334155; cursor: pointer; transition: all 0.2s;
}
.btn-advanced svg { width: 14px; height: 14px; }
.btn-advanced:hover { border-color: #2563eb; color: #2563eb; }
.showing-count { font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 0.06em; }

/* TABLE */
.table-wrap { margin: 0 32px; background: white; border-radius: 14px; border: 1px solid #f1f5f9; overflow: hidden; }
table { width: 100%; border-collapse: collapse; }
.page-header { align-items: flex-start; display: flex; justify-content: space-between; padding: 12px 32px 20px; }
.page-header h1 { color: #0f172a; font-size: 28px; font-weight: 800; margin: 0 0 6px; }
.page-header p { color: #64748b; font-size: 13px; line-height: 1.5; margin: 0; max-width: 470px; }
.btn-new, .btn-submit { background: linear-gradient(135deg,#2563eb,#4f46e5); border: 0; border-radius: 10px; color: white; cursor: pointer; font-size: 13px; font-weight: 600; padding: 11px 20px; }
.btn-new { align-items: center; display: flex; gap: 7px; }
.btn-new svg { height: 14px; width: 14px; }
.stats { display: grid; gap: 16px; grid-template-columns: repeat(4,1fr); padding: 0 32px 20px; }
.stat-card { align-items: center; background: white; border: 1px solid #f1f5f9; border-radius: 14px; display: flex; justify-content: space-between; padding: 20px; }
.stat-card p { color: #94a3b8; font-size: 10px; font-weight: 700; letter-spacing: .08em; margin: 0 0 6px; }
.stat-card b { color: #0f172a; font-size: 24px; font-weight: 800; }
.stat-icon { align-items: center; border-radius: 12px; display: flex; height: 44px; justify-content: center; width: 44px; }
.stat-icon svg { height: 20px; width: 20px; }
.blue { background: #dbeafe; color: #2563eb; }
.green { background: #dcfce7; color: #16a34a; }
.purple { background: #ede9fe; color: #7c3aed; }
.amber { background: #fef9c3; color: #d97706; }
.filter-bar { align-items: center; display: flex; justify-content: space-between; padding: 0 32px 14px; }
.filter-left select, .btn-advanced { background: white; border: 1px solid #e2e8f0; border-radius: 8px; color: #334155; cursor: pointer; font-size: 13px; padding: 8px 14px; }
.btn-advanced { align-items: center; display: flex; gap: 6px; }
.btn-advanced svg { height: 14px; width: 14px; }
.showing-count { color: #94a3b8; font-size: 11px; font-weight: 700; letter-spacing: .06em; }
.table-wrap { background: white; border: 1px solid #f1f5f9; border-radius: 14px; margin: 0 32px; overflow: hidden; }
table { border-collapse: collapse; width: 100%; }
thead tr { background: #f8fafc; }
thead th { border-bottom: 1px solid #f1f5f9; color: #94a3b8; font-size: 11px; font-weight: 700; letter-spacing: .07em; padding: 12px 16px; text-align: left; }
tbody tr { border-bottom: 1px solid #f8fafc; }
tbody tr:hover { background: #fafbff; }
tbody td { color: #334155; font-size: 13px; padding: 16px; vertical-align: middle; }
.empty { color: #94a3b8; padding: 50px !important; text-align: center; }
.post-cell { align-items: center; display: flex; gap: 12px; }
.post-cell img { border-radius: 8px; flex-shrink: 0; height: 52px; object-fit: cover; width: 52px; }
.post-cell b { color: #0f172a; display: block; font-size: 13px; font-weight: 600; margin-bottom: 3px; }
.cat-badge, .status-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 4px 10px; }
.cat-badge { border-radius: 6px; }
.status-badge { border-radius: 20px; }
.author-avatar { align-items: center; border-radius: 50%; display: flex; flex-shrink: 0; font-size: 10px; font-weight: 700; height: 30px; justify-content: center; width: 30px; }
.stats-cell { display: flex; flex-direction: column; gap: 5px; }
.stat-item { color: #64748b; font-size: 12px; }
.stat-item svg { height: 12px; width: 12px; }
.act-btn { align-items: center; background: white; border: 1px solid #e2e8f0; border-radius: 7px; color: #64748b; cursor: pointer; display: flex; height: 30px; justify-content: center; text-decoration: none; width: 30px; }
.act-btn svg { height: 13px; width: 13px; }
.act-btn:hover { background: #f1f5f9; color: #2563eb; }
.act-btn.danger:hover { background: #fee2e2; color: #ef4444; }
.table-footer { align-items: center; display: flex; justify-content: space-between; padding: 16px 32px; }
.showing { color: #64748b; font-size: 13px; }
.pagination { display: flex; gap: 6px; }
.pagination button { background: white; border: 1px solid #e2e8f0; border-radius: 8px; color: #334155; cursor: pointer; font-size: 13px; height: 34px; width: 34px; }
.pagination button:disabled { cursor: not-allowed; opacity: .5; }
.pagination .active { background: #2563eb; border-color: #2563eb; color: white; }
.page-footer { border-top: 1px solid #f1f5f9; color: #94a3b8; font-size: 11px; letter-spacing: .06em; margin-top: 8px; padding: 20px; text-align: center; }
.modal-overlay { align-items: center; background: rgba(15,23,42,.55); display: flex; inset: 0; justify-content: center; padding: 20px; position: fixed; z-index: 1000; }
.modal { background: white; border-radius: 16px; box-shadow: 0 24px 60px rgba(0,0,0,.18); max-height: 90vh; max-width: 640px; overflow-y: auto; width: 100%; }
.modal-header, .modal-footer { align-items: center; background: white; display: flex; justify-content: space-between; padding: 18px 24px; position: sticky; z-index: 1; }
.modal-header { border-bottom: 1px solid #f1f5f9; top: 0; }
.modal-footer { border-top: 1px solid #f1f5f9; bottom: 0; gap: 10px; }
.modal-header h3 { color: #0f172a; font-size: 17px; margin: 0; }
.modal-close { background: none; border: 0; color: #94a3b8; cursor: pointer; font-size: 22px; }
.modal-body { display: flex; flex-direction: column; gap: 14px; padding: 20px 24px; }
.upload-zone { align-items: center; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 10px; cursor: pointer; display: flex; flex-direction: column; gap: 8px; padding: 28px; text-align: center; }
.upload-zone svg { color: #94a3b8; height: 32px; width: 32px; }
.upload-zone p { color: #475569; font-size: 13px; margin: 0; }
.upload-zone span { color: #2563eb; font-weight: 600; }
.upload-zone small { color: #94a3b8; font-size: 11px; }
.img-preview-wrap { align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; gap: 14px; padding: 12px; }
.img-preview { border-radius: 6px; height: 68px; object-fit: cover; width: 120px; }
.img-actions { display: flex; flex-direction: column; gap: 8px; }
.img-change, .img-remove, .btn-draft, .btn-cancel { border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600; padding: 8px 14px; }
.img-change, .btn-draft, .btn-cancel { background: white; border: 1px solid #e2e8f0; color: #475569; }
.img-remove { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; }
.form-row { display: grid; gap: 14px; grid-template-columns: 1fr 1fr; }
.form-row.single-column { grid-template-columns: 1fr; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { color: #94a3b8; font-size: 10px; font-weight: 700; letter-spacing: .08em; }
.req { color: #ef4444; }
.form-group input, .form-group select, .form-group textarea { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; color: #0f172a; font-family: sans-serif; font-size: 13px; outline: none; padding: 10px 12px; }
.form-group input[readonly] { background: #eef2f7; color: #475569; cursor: not-allowed; }
.form-group textarea { resize: vertical; }
.content-image-tools { display: grid; gap: 8px; grid-template-columns: 1fr auto; }
.content-image-tools input { min-width: 0; }
.content-image-tools button { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; color: #1d4ed8; cursor: pointer; font-size: 12px; font-weight: 700; padding: 10px 14px; white-space: nowrap; }
.content-image-tools button:hover:not(:disabled) { background: #dbeafe; }
.field-hint { color: #64748b; font-size: 11px; line-height: 1.5; }
.form-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #ef4444; font-size: 12px; margin: 0; padding: 9px 12px; }
button:disabled { cursor: not-allowed; opacity: .65; }
@media (max-width: 900px) { .stats { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 640px) {
  .topbar, .page-header, .filter-bar, .table-footer { padding-left: 16px; padding-right: 16px; }
  .page-header, .filter-bar, .table-footer, .modal-footer { align-items: flex-start; flex-direction: column; gap: 12px; }
  .breadcrumb { padding-left: 16px; }
  .stats { grid-template-columns: 1fr; padding: 0 16px 16px; }
  .table-wrap { margin: 0 16px; overflow-x: auto; }
  table { min-width: 760px; }
  .form-row { grid-template-columns: 1fr; }
  .content-image-tools { grid-template-columns: 1fr; }
}
</style>


