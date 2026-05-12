<script setup>
import { ref, computed } from 'vue'

const searchQuery = ref('')
const selectedCategory = ref('Tất cả danh mục')
const selectedStatus = ref('Mọi trạng thái')
const showModal = ref(false)
const formError = ref('')

const categories = ['Tất cả danh mục', 'Công nghệ', 'Sự kiện', 'Sản phẩm', 'Nội bộ']
const statuses = ['Mọi trạng thái', 'Đã xuất bản', 'Sắp xuất bản', 'Bản nháp']

const catStyle = {
    'Công nghệ': { bg: '#dbeafe', color: '#1d4ed8' },
    'Sự kiện':   { bg: '#dcfce7', color: '#15803d' },
    'Sản phẩm':  { bg: '#fef9c3', color: '#a16207' },
    'Nội bộ':    { bg: '#ede9fe', color: '#6d28d9' },
}

const statusStyle = {
    'Đã xuất bản':  { bg: '#dcfce7', color: '#15803d' },
    'Sắp xuất bản': { bg: '#fef9c3', color: '#a16207' },
    'Bản nháp':     { bg: '#f1f5f9', color: '#64748b' },
}

const avatarColors = ['#dbeafe','#dcfce7','#ede9fe','#fef9c3','#fee2e2']
const avatarText   = ['#1d4ed8','#15803d','#6d28d9','#a16207','#b91c1c']
const getAvatarStyle = (name) => {
    const i = name.charCodeAt(0) % avatarColors.length
    return { background: avatarColors[i], color: avatarText[i] }
}
const initials = (name) => name.trim().split(' ').map(w=>w[0]).slice(-2).join('').toUpperCase()

const posts = ref([
    {
        id: 1,
        title: 'Xu hướng liên kiện máy tính 2026: Sức mạnh...',
        date: '12/05/2024',
        category: 'Công nghệ',
        author: 'Minh Khôi',
        views: 2402,
        comments: 48,
        status: 'Đã xuất bản',
        img: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=80'
    },
    {
        id: 2,
        title: 'Khai trương trung tâm dữ liệu thứ 3 tại Đà Nẵ...',
        date: '09/05/2024',
        category: 'Sự kiện',
        author: 'Thanh Hồng',
        views: 856,
        comments: 12,
        status: 'Sắp xuất bản',
        img: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=80'
    },
    {
        id: 3,
        title: 'Mô bản siêu phẩm VinaPhone Pro với màn hi...',
        date: '08/05/2024',
        category: 'Sản phẩm',
        author: 'Hoàng Nam',
        views: 5110,
        comments: 332,
        status: 'Đã xuất bản',
        img: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=80'
    },
])

const filtered = computed(() =>
    posts.value.filter(p => {
        const matchSearch = p.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            p.author.toLowerCase().includes(searchQuery.value.toLowerCase())
        const matchCat = selectedCategory.value === 'Tất cả danh mục' || p.category === selectedCategory.value
        const matchStatus = selectedStatus.value === 'Mọi trạng thái' || p.status === selectedStatus.value
        return matchSearch && matchCat && matchStatus
    })
)

const removePost = (i) => posts.value.splice(i, 1)

// MODAL
const imgPreview = ref('')
const fileRef = ref(null)
const defaultForm = () => ({
    title: '', category: '', author: '', status: 'Bản nháp',
    date: new Date().toLocaleDateString('vi-VN'), img: '', content: ''
})
const form = ref(defaultForm())

const onFileChange = (e) => {
    const f = e.target.files[0]; if (!f) return
    const r = new FileReader()
    r.onload = ev => { imgPreview.value = ev.target.result; form.value.img = ev.target.result }
    r.readAsDataURL(f)
}
const removeImg = () => { imgPreview.value = ''; form.value.img = ''; if (fileRef.value) fileRef.value.value = '' }

const openModal = () => { form.value = defaultForm(); imgPreview.value = ''; formError.value = ''; showModal.value = true }
const closeModal = () => showModal.value = false

const submitForm = () => {
    if (!form.value.title.trim())    { formError.value = 'Vui lòng nhập tiêu đề bài viết.'; return }
    if (!form.value.category)        { formError.value = 'Vui lòng chọn danh mục.'; return }
    if (!form.value.author.trim())   { formError.value = 'Vui lòng nhập tên tác giả.'; return }
    posts.value.unshift({
        id: Date.now(),
        title: form.value.title.trim(),
        date: form.value.date,
        category: form.value.category,
        author: form.value.author.trim(),
        views: 0,
        comments: 0,
        status: form.value.status,
        img: form.value.img || 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=80'
    })
    closeModal()
}
</script>

<template>
    <div class="page">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input v-model="searchQuery" placeholder="Tìm kiếm bài viết, tác giả..." />
            </div>
            <div class="topbar-right">
                <button class="icon-btn">🔔</button>
                <button class="icon-btn">🌙</button>
                <button class="icon-btn">❓</button>
                <div class="admin-wrap">
                    <div class="admin-text">
                        <b>Admin VinaTech</b>
                        <span>Quản trị viên</span>
                    </div>
                    <div class="avatar-circle">AV</div>
                </div>
            </div>
        </div>

        <!-- BREADCRUMB -->
        <div class="breadcrumb">
            <span>Hệ thống</span>
            <span class="sep">›</span>
            <span class="crumb-active">Quản lý tin tức</span>
        </div>

        <!-- HEADER -->
        <div class="page-header">
            <div>
                <h1>Bài viết &amp; Tin tức</h1>
                <p>Quản lý nội dung truyền thông, cập nhật công nghệ và thông tin nội bộ của VinaTech.</p>
            </div>
            <button class="btn-new" @click="openModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Viết bài mới
            </button>
        </div>

        <!-- STATS -->
        <div class="stats">
            <div class="stat-card">
                <div>
                    <p>TỔNG BÀI VIẾT</p>
                    <b>1,284</b>
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
            <div class="stat-card">
                <div>
                    <p>ĐÃ XUẤT BẢN</p>
                    <b>1,120</b>
                </div>
                <div class="stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card">
                <div>
                    <p>LƯỢT XEM THÁNG</p>
                    <b>42.5K</b>
                </div>
                <div class="stat-icon purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
            </div>
            <div class="stat-card">
                <div>
                    <p>BẢN NHÁP</p>
                    <b>64</b>
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
                <select v-model="selectedCategory">
                    <option v-for="c in categories" :key="c">{{ c }}</option>
                </select>
                <select v-model="selectedStatus">
                    <option v-for="s in statuses" :key="s">{{ s }}</option>
                </select>
                <button class="btn-advanced">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/>
                        <line x1="11" y1="18" x2="13" y2="18"/>
                    </svg>
                    Bộ lọc nâng cao
                </button>
            </div>
            <span class="showing-count">HIỂN THỊ 10/1, {{ posts.length }} BÀI VIẾT</span>
        </div>

        <!-- TABLE -->
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
                    <tr v-if="filtered.length === 0">
                        <td colspan="6" class="empty">Không tìm thấy bài viết nào.</td>
                    </tr>
                    <tr v-for="(p, i) in filtered" :key="p.id">

                        <!-- BÀI VIẾT -->
                        <td>
                            <div class="post-cell">
                                <img :src="p.img" :alt="p.title" />
                                <div>
                                    <b>{{ p.title }}</b>
                                    <span>Ngày đăng: {{ p.date }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- DANH MỤC -->
                        <td>
                            <span class="cat-badge"
                                :style="{ background: catStyle[p.category]?.bg, color: catStyle[p.category]?.color }">
                                {{ p.category }}
                            </span>
                        </td>

                        <!-- TÁC GIẢ -->
                        <td>
                            <div class="author-cell">
                                <div class="author-avatar" :style="getAvatarStyle(p.author)">
                                    {{ initials(p.author) }}
                                </div>
                                <span>{{ p.author }}</span>
                            </div>
                        </td>

                        <!-- THỐNG SỐ -->
                        <td>
                            <div class="stats-cell">
                                <span class="stat-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    {{ p.views.toLocaleString() }}
                                </span>
                                <span class="stat-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    </svg>
                                    {{ p.comments }}
                                </span>
                            </div>
                        </td>

                        <!-- TRẠNG THÁI -->
                        <td>
                            <span class="status-badge"
                                :style="{ background: statusStyle[p.status]?.bg, color: statusStyle[p.status]?.color }">
                                {{ p.status }}
                            </span>
                        </td>

                        <!-- ACTIONS -->
                        <td>
                            <div class="actions">
                                <button class="act-btn" title="Xem">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                                <button class="act-btn" title="Sửa">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button class="act-btn danger" title="Xóa" @click="removePost(i)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                    </svg>
                                </button>
                            </div>
                        </td>

                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div class="table-footer">
            <span class="showing">Hiển thị trang 1 trên 129</span>
            <div class="pagination">
                <button>‹</button>
                <button class="active">1</button>
                <button>2</button>
                <button>3</button>
                <button class="dots">...</button>
                <button>129</button>
                <button>›</button>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="page-footer">
            © 2024 VINATECH ECOSYSTEM • QUẢN LÝ HẠ TẦNG NỘI DUNG
        </div>

        <!-- MODAL VIẾT BÀI MỚI -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal">

                    <div class="modal-header">
                        <h3>Viết bài mới</h3>
                        <button class="modal-close" @click="closeModal">×</button>
                    </div>

                    <div class="modal-body">

                        <!-- ẢNH THUMBNAIL -->
                        <div class="form-group">
                            <label>ẢNH THUMBNAIL</label>
                            <input ref="fileRef" type="file" accept="image/*" style="display:none" @change="onFileChange" />
                            <div v-if="!imgPreview" class="upload-zone" @click="fileRef.click()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                <p>Kéo thả hoặc <span>chọn ảnh</span></p>
                                <small>PNG, JPG, WEBP — khuyến khích 1200×630px</small>
                            </div>
                            <div v-else class="img-preview-wrap">
                                <img :src="imgPreview" class="img-preview" />
                                <div class="img-actions">
                                    <button class="img-change" @click="fileRef.click()">Đổi ảnh</button>
                                    <button class="img-remove" @click="removeImg">Xóa</button>
                                </div>
                            </div>
                        </div>

                        <!-- TIÊU ĐỀ -->
                        <div class="form-group">
                            <label>TIÊU ĐỀ BÀI VIẾT <span class="req">*</span></label>
                            <input v-model="form.title" placeholder="Nhập tiêu đề bài viết..." />
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>DANH MỤC <span class="req">*</span></label>
                                <select v-model="form.category">
                                    <option value="">-- Chọn danh mục --</option>
                                    <option>Công nghệ</option>
                                    <option>Sự kiện</option>
                                    <option>Sản phẩm</option>
                                    <option>Nội bộ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>TRẠNG THÁI</label>
                                <select v-model="form.status">
                                    <option>Bản nháp</option>
                                    <option>Đã xuất bản</option>
                                    <option>Sắp xuất bản</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>TÁC GIẢ <span class="req">*</span></label>
                                <input v-model="form.author" placeholder="VD: Minh Khôi" />
                            </div>
                            <div class="form-group">
                                <label>NGÀY ĐĂNG</label>
                                <input v-model="form.date" placeholder="DD/MM/YYYY" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>NỘI DUNG TÓM TẮT</label>
                            <textarea v-model="form.content" rows="4" placeholder="Nhập mô tả ngắn về bài viết..."></textarea>
                        </div>

                        <p v-if="formError" class="form-error">⚠ {{ formError }}</p>

                    </div>

                    <div class="modal-footer">
                        <button class="btn-draft" @click="() => { form.status = 'Bản nháp'; submitForm() }">Lưu nháp</button>
                        <div style="display:flex;gap:8px">
                            <button class="btn-cancel" @click="closeModal">Hủy</button>
                            <button class="btn-submit" @click="submitForm">Xuất bản</button>
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
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 32px; background: white; border-bottom: 1px solid #f1f5f9;
}
.search-box { position: relative; width: 240px; }
.search-box svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #94a3b8; pointer-events: none; }
.search-box input { width: 100%; padding: 8px 12px 8px 32px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; color: #0f172a; outline: none; background: #f8fafc; }
.search-box input:focus { border-color: #2563eb; background: white; }

.topbar-right { display: flex; align-items: center; gap: 8px; }
.icon-btn { background: none; border: none; font-size: 15px; cursor: pointer; padding: 6px 8px; border-radius: 8px; }
.icon-btn:hover { background: #f1f5f9; }
.admin-wrap { display: flex; align-items: center; gap: 8px; margin-left: 4px; }
.admin-text b { display: block; font-size: 12px; color: #0f172a; font-weight: 600; }
.admin-text span { font-size: 10px; color: #94a3b8; }
.avatar-circle { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg,#2563eb,#4f46e5); color: white; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }

/* BREADCRUMB */
.breadcrumb { padding: 16px 32px 0; font-size: 12px; color: #94a3b8; display: flex; gap: 6px; align-items: center; }
.sep { color: #cbd5e1; }
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
.stat-card { background: white; border-radius: 14px; border: 1px solid #f1f5f9; padding: 20px; display: flex; align-items: center; justify-content: space-between; }
.stat-card p { font-size: 10px; font-weight: 700; color: #94a3b8; letter-spacing: 0.08em; margin: 0 0 6px; }
.stat-card b { font-size: 24px; font-weight: 800; color: #0f172a; }
.stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-icon svg { width: 20px; height: 20px; }
.stat-icon.blue   { background: #dbeafe; color: #2563eb; }
.stat-icon.green  { background: #dcfce7; color: #16a34a; }
.stat-icon.purple { background: #ede9fe; color: #7c3aed; }
.stat-icon.amber  { background: #fef9c3; color: #d97706; }

/* FILTER */
.filter-bar { display: flex; align-items: center; justify-content: space-between; padding: 0 32px 14px; }
.filter-left { display: flex; gap: 8px; align-items: center; }
.filter-left select {
    padding: 8px 14px; border-radius: 8px; border: 1px solid #e2e8f0;
    background: white; font-size: 13px; color: #334155; outline: none; cursor: pointer;
}
.filter-left select:focus { border-color: #2563eb; }
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
thead tr { background: #f8fafc; }
thead th { padding: 12px 16px; font-size: 11px; font-weight: 700; color: #94a3b8; text-align: left; letter-spacing: 0.07em; border-bottom: 1px solid #f1f5f9; }
tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #fafbff; }
tbody td { padding: 16px; font-size: 13px; color: #334155; vertical-align: middle; }
.empty { text-align: center; color: #94a3b8; padding: 50px !important; }

/* POST CELL */
.post-cell { display: flex; align-items: center; gap: 12px; }
.post-cell img { width: 52px; height: 52px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
.post-cell b { display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 3px; }
.post-cell span { font-size: 11px; color: #94a3b8; }

.cat-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px; }

.author-cell { display: flex; align-items: center; gap: 8px; }
.author-avatar { width: 30px; height: 30px; border-radius: 50%; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.author-cell span { font-size: 13px; font-weight: 500; color: #334155; }

.stats-cell { display: flex; flex-direction: column; gap: 5px; }
.stat-item { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #64748b; }
.stat-item svg { width: 12px; height: 12px; }

.status-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }

.actions { display: flex; gap: 5px; }
.act-btn { width: 30px; height: 30px; border-radius: 7px; border: 1px solid #e2e8f0; background: white; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; transition: all 0.2s; }
.act-btn svg { width: 13px; height: 13px; }
.act-btn:hover { background: #f1f5f9; border-color: #cbd5e1; color: #2563eb; }
.act-btn.danger:hover { background: #fee2e2; border-color: #fecaca; color: #ef4444; }

/* TABLE FOOTER */
.table-footer { display: flex; align-items: center; justify-content: space-between; padding: 16px 32px; }
.showing { font-size: 13px; color: #64748b; }
.pagination { display: flex; gap: 6px; }
.pagination button { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; font-size: 13px; cursor: pointer; color: #334155; transition: all 0.2s; }
.pagination button:hover { border-color: #2563eb; color: #2563eb; }
.pagination .active { background: #2563eb; border-color: #2563eb; color: white; }
.pagination .dots { border: none; background: transparent; cursor: default; }
.pagination .dots:hover { color: #334155; border-color: transparent; }

/* PAGE FOOTER */
.page-footer { text-align: center; padding: 20px; font-size: 11px; color: #94a3b8; letter-spacing: 0.06em; border-top: 1px solid #f1f5f9; margin-top: 8px; }

/* ===== MODAL ===== */
.modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.55); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px; }
.modal { background: white; border-radius: 16px; width: 100%; max-width: 600px; box-shadow: 0 24px 60px rgba(0,0,0,0.18); animation: modalIn 0.22s cubic-bezier(.22,1,.36,1); max-height: 90vh; overflow-y: auto; }
@keyframes modalIn { from { opacity:0; transform:translateY(16px) scale(0.97); } to { opacity:1; transform:translateY(0) scale(1); } }

.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px 16px; border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; background: white; z-index: 1; }
.modal-header h3 { font-size: 17px; font-weight: 700; color: #0f172a; margin: 0; }
.modal-close { background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer; padding: 0; line-height: 1; transition: color 0.2s; }
.modal-close:hover { color: #0f172a; }

.modal-body { padding: 20px 24px; display: flex; flex-direction: column; gap: 14px; }

.upload-zone { border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 28px; text-align: center; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 8px; background: #f8fafc; }
.upload-zone:hover { border-color: #2563eb; background: #f0f6ff; }
.upload-zone svg { width: 32px; height: 32px; color: #94a3b8; }
.upload-zone p { font-size: 13px; color: #475569; margin: 0; }
.upload-zone p span { color: #2563eb; font-weight: 600; }
.upload-zone small { font-size: 11px; color: #94a3b8; }

.img-preview-wrap { display: flex; align-items: center; gap: 14px; background: #f8fafc; border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; }
.img-preview { width: 120px; height: 68px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.img-actions { display: flex; flex-direction: column; gap: 8px; }
.img-change { padding: 6px 14px; border-radius: 7px; border: 1px solid #e2e8f0; background: white; font-size: 12px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s; }
.img-change:hover { border-color: #2563eb; color: #2563eb; }
.img-remove { padding: 6px 14px; border-radius: 7px; border: 1px solid #fecaca; background: #fef2f2; font-size: 12px; font-weight: 600; color: #ef4444; cursor: pointer; transition: all 0.2s; }
.img-remove:hover { background: #fee2e2; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 10px; font-weight: 700; color: #94a3b8; letter-spacing: 0.08em; }
.req { color: #ef4444; }
.form-group input, .form-group select, .form-group textarea { padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; color: #0f172a; outline: none; transition: border-color 0.2s; background: #f8fafc; font-family: sans-serif; }
.form-group textarea { resize: vertical; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #2563eb; background: white; box-shadow: 0 0 0 3px rgba(37,99,235,0.08); }
.form-error { font-size: 12px; color: #ef4444; background: #fef2f2; border: 1px solid #fecaca; padding: 9px 12px; border-radius: 8px; margin: 0; }

.modal-footer { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 16px 24px 20px; border-top: 1px solid #f1f5f9; position: sticky; bottom: 0; background: white; }
.btn-draft { padding: 10px 16px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; font-size: 13px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s; }
.btn-draft:hover { background: #f8fafc; border-color: #cbd5e1; }
.btn-cancel { padding: 10px 18px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; font-size: 13px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s; }
.btn-cancel:hover { background: #f8fafc; }
.btn-submit { padding: 10px 20px; border-radius: 8px; border: none; background: linear-gradient(135deg,#2563eb,#4f46e5); color: white; font-size: 13px; font-weight: 600; cursor: pointer; transition: opacity 0.2s, transform 0.2s; }
.btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }

/* RESPONSIVE */
@media (max-width: 900px) {
    .stats { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 640px) {
    .page-header { flex-direction: column; gap: 14px; padding: 12px 16px 16px; }
    .stats { padding: 0 16px 16px; }
    .filter-bar { padding: 0 16px 12px; flex-wrap: wrap; gap: 10px; }
    .table-wrap { margin: 0 16px; overflow-x: auto; }
    table { min-width: 680px; }
    .table-footer { padding: 14px 16px; flex-direction: column; gap: 10px; align-items: flex-start; }
    .form-row { grid-template-columns: 1fr; }
    .topbar { padding: 12px 16px; }
    .breadcrumb { padding: 14px 16px 0; }
}
</style>