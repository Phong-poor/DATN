<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'
import { absoluteUrl, setSeo } from '@/services/seo'
import { storageUrl } from '@/services/urls'

const posts = ref([])
const popularPosts = ref([])
const randomPost = ref(null)
const selectedCategory = ref('Mới nhất')
const currentPage = ref(1)
const lastPage = ref(1)
const loading = ref(false)
const errorMessage = ref('')

const defaultCategories = ['Công nghệ', 'Sự kiện', 'Sản phẩm', 'Nội bộ']
const placeholderImage = 'https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?auto=format&fit=crop&w=800&q=80'

const categories = computed(() => {
  const names = [...posts.value, ...popularPosts.value].map((item) => item?.danhmuc).filter(Boolean)
  return [...new Set([...defaultCategories, ...names])]
})
const tabs = computed(() => ['Mới nhất', ...categories.value.slice(0, 4)])

const displayedPages = computed(() => {
  const range = []
  const delta = 2
  
  for (let i = 1; i <= lastPage.value; i++) {
    if (
      i === 1 ||
      i === lastPage.value ||
      (i >= currentPage.value - delta && i <= currentPage.value + delta)
    ) {
      range.push(i)
    } else if (
      (i === 2 && currentPage.value - delta > 2) ||
      (i === lastPage.value - 1 && currentPage.value + delta < lastPage.value - 1)
    ) {
      range.push('...')
    }
  }
  
  const result = []
  for (let i = 0; i < range.length; i++) {
    if (range[i] === '...' && result[result.length - 1] === '...') {
      continue
    }
    result.push(range[i])
  }
  return result
})

const applyListSeo = () => {
  const suffix = selectedCategory.value === 'Mới nhất' ? 'mới nhất' : selectedCategory.value.toLowerCase()
  setSeo({
    title: `Tin tức công nghệ ${suffix}`,
    description: 'Cập nhật tin tức công nghệ, kinh nghiệm chọn laptop, đánh giá laptop gaming, laptop văn phòng và laptop đồ họa từ VinaTech.',
    keywords: 'tin tức công nghệ, tư vấn laptop, laptop gaming, laptop văn phòng, laptop đồ họa, VinaTech',
    url: '/news',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'CollectionPage',
      name: 'Tin tức công nghệ VinaTech',
      description: 'Cập nhật tin tức công nghệ, kinh nghiệm chọn laptop, đánh giá laptop gaming, laptop văn phòng và laptop đồ họa từ VinaTech.',
      url: absoluteUrl('/news'),
      isPartOf: { '@type': 'WebSite', name: 'VinaTech', url: absoluteUrl('/') },
    },
  })
}

const imageUrl = (path) => {
  if (!path) return placeholderImage
  if (path.startsWith('http')) return path
  return storageUrl(path)
}

const formatDate = (value) => {
  if (!value) return ''
  return new Date(value).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const pickRandomPost = () => {
  if (!posts.value.length) { randomPost.value = null; return }
  randomPost.value = posts.value[Math.floor(Math.random() * posts.value.length)]
}

const fetchNews = async (page = 1) => {
  if (posts.value.length === 0) loading.value = true
  errorMessage.value = ''
  try {
    const params = { scope: 'public', per_page: 6, page }
    if (selectedCategory.value !== 'Mới nhất') params.danhmuc = selectedCategory.value
    const { data } = await api.get('/news', { params })
    posts.value = data.data || []
    posts.value.forEach(post => {
      try {
        const cachedStr = localStorage.getItem(`predator_news_detail_cache_${post.id}`)
        let cached = cachedStr ? JSON.parse(cachedStr) : {}
        if (!cached.post) cached.post = post
        localStorage.setItem(`predator_news_detail_cache_${post.id}`, JSON.stringify(cached))
      } catch (e) {}
    })
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
    pickRandomPost()
  } catch (error) {
    console.error('Lỗi tải tin tức:', error)
    posts.value = []
    randomPost.value = null
    currentPage.value = 1
    lastPage.value = 1
    errorMessage.value = 'Không thể tải tin tức. Vui lòng thử lại sau.'
  } finally {
    loading.value = false
  }
}

const fetchPopular = async () => {
  try {
    const { data } = await api.get('/news', { params: { scope: 'public', per_page: 20 } })
    popularPosts.value = (data.data || []).sort((a, b) => (b.luotxem || 0) - (a.luotxem || 0)).slice(0, 3)
    popularPosts.value.forEach(post => {
      try {
        const cachedStr = localStorage.getItem(`predator_news_detail_cache_${post.id}`)
        let cached = cachedStr ? JSON.parse(cachedStr) : {}
        if (!cached.post) cached.post = post
        localStorage.setItem(`predator_news_detail_cache_${post.id}`, JSON.stringify(cached))
      } catch (e) {}
    })
  } catch (error) {
    console.error('Lỗi tải bài phổ biến:', error)
    popularPosts.value = []
  }
}

const selectTab = async (tab) => {
  selectedCategory.value = tab
  applyListSeo()
  await fetchNews(1)
}

const loadCache = () => {
  try {
    const cached = localStorage.getItem('predator_news_cache')
    if (cached) {
      const parsed = JSON.parse(cached)
      if (parsed.posts) posts.value = parsed.posts
      if (parsed.popularPosts) popularPosts.value = parsed.popularPosts
    }
  } catch (e) {}
}

const saveCache = () => {
  try {
    localStorage.setItem('predator_news_cache', JSON.stringify({ posts: posts.value, popularPosts: popularPosts.value }))
  } catch (e) {}
}

onMounted(async () => {
  applyListSeo()
  loadCache()
  try {
    await Promise.all([fetchNews(), fetchPopular()])
    saveCache()
  } catch (error) {
    console.error('Lỗi tải tin tức:', error)
  }
})
</script>

<template>
  <section class="news-page">

    <!-- ===== HEADER ===== -->
    <div class="news-header">
      <div class="news-header-inner">
        <span class="header-badge">📡 TẠP CHÍ CÔNG NGHỆ</span>
        <h1>Tin tức <span>công nghệ.</span></h1>
        <nav class="tabs">
          <button
            v-for="tab in tabs"
            :key="tab"
            :class="{ active: selectedCategory === tab }"
            @click="selectTab(tab)"
          >{{ tab }}</button>
        </nav>
      </div>
    </div>

    <!-- ===== BODY ===== -->
    <div class="news-body">

      <!-- LEFT: Main content -->
      <div class="news-main">

        <!-- Loading -->
        <div v-if="loading" class="state-box">
          <div class="loading-dots"><span></span><span></span><span></span></div>
          <p>Đang tải tin tức...</p>
        </div>

        <!-- Error -->
        <div v-else-if="errorMessage" class="state-box error">{{ errorMessage }}</div>

        <template v-else>
          <!-- Hero featured card -->
          <RouterLink v-if="randomPost" :to="`/news/${randomPost.id}`" class="hero-card">
            <div class="hero-img">
              <img :src="imageUrl(randomPost.hinhanh)" :alt="randomPost.mota_hinhanh || randomPost.tieude" @error="e => e.target.src = placeholderImage" />
              <span class="hero-cat">{{ randomPost.danhmuc }}</span>
              <span class="hero-badge">⭐ Nổi bật</span>
            </div>
            <div class="hero-body">
              <p class="hero-date">{{ formatDate(randomPost.dang_luc || randomPost.created_at) }}</p>
              <h2>{{ randomPost.tieude }}</h2>
              <p class="hero-excerpt" v-if="randomPost.tomtat">{{ randomPost.tomtat }}</p>
              <span class="hero-cta">Đọc bài viết đầy đủ →</span>
            </div>
          </RouterLink>

          <!-- Section divider -->
          <div class="section-label" v-if="posts.length > 0">
            <span>Tất cả bài viết</span>
            <div class="section-line"></div>
          </div>

          <!-- Grid cards -->
          <div class="news-grid">
            <RouterLink v-for="post in posts" :key="post.id" :to="`/news/${post.id}`" class="news-card">
              <div class="card-thumb">
                <img :src="imageUrl(post.hinhanh)" :alt="post.mota_hinhanh || post.tieude" loading="lazy" @error="e => e.target.src = placeholderImage" />
                <span class="card-cat-badge">{{ post.danhmuc }}</span>
              </div>
              <div class="card-content">
                <time class="card-date">{{ formatDate(post.dang_luc || post.created_at) }}</time>
                <h3>{{ post.tieude }}</h3>
                <p v-if="post.tomtat">{{ post.tomtat }}</p>
                <span class="card-read">Xem thêm →</span>
              </div>
            </RouterLink>

            <div v-if="posts.length === 0" class="state-box" style="grid-column:1/-1">
              Chưa có bài viết nào trong danh mục này.
            </div>
          </div>

          <!-- Pagination -->
          <div class="pagination" v-if="lastPage > 1">
            <button 
              class="pg-btn pg-prev" 
              :disabled="currentPage <= 1" 
              @click="fetchNews(currentPage - 1)"
              aria-label="Trang trước"
            >
              ‹
            </button>
            
            <template v-for="(page, idx) in displayedPages" :key="idx">
              <span v-if="page === '...'" class="pg-dots">...</span>
              <button 
                v-else 
                class="pg-btn" 
                :class="{ 'pg-active': currentPage === page }" 
                @click="fetchNews(page)"
              >
                {{ page }}
              </button>
            </template>

            <button 
              class="pg-btn pg-next" 
              :disabled="currentPage >= lastPage" 
              @click="fetchNews(currentPage + 1)"
              aria-label="Trang sau"
            >
              ›
            </button>
          </div>
        </template>
      </div>

      <!-- RIGHT: Sidebar -->
      <aside class="news-sidebar">

        <!-- Popular -->
        <div class="sidebar-widget">
          <h4 class="widget-title">🔥 Bài viết phổ biến</h4>
          <div class="popular-list">
            <RouterLink
              v-for="(item, idx) in popularPosts"
              :key="item.id"
              :to="`/news/${item.id}`"
              class="popular-item"
            >
              <span class="pop-rank" :class="`rank-${idx+1}`">{{ idx + 1 }}</span>
              <div class="pop-thumb">
                <img :src="imageUrl(item.hinhanh)" :alt="item.mota_hinhanh || item.tieude" @error="e => e.target.src = placeholderImage" />
              </div>
              <div class="pop-info">
                <p>{{ item.tieude }}</p>
                <span>{{ item.luotxem || 0 }} lượt xem</span>
              </div>
            </RouterLink>
            <p v-if="popularPosts.length === 0" class="muted-text">Chưa có dữ liệu.</p>
          </div>
        </div>

        <!-- Categories -->
        <div class="sidebar-widget">
          <h4 class="widget-title">📂 Chuyên mục</h4>
          <div class="cat-tags">
            <button
              v-for="tag in categories"
              :key="tag"
              :class="{ active: selectedCategory === tag }"
              @click="selectTab(tag)"
            >{{ tag }}</button>
          </div>
        </div>

        <!-- CTA subscribe -->
        <div class="subscribe-cta">
          <div class="cta-emoji">📬</div>
          <h3>Đăng ký nhận tin</h3>
          <p>Cập nhật công nghệ mới nhất từ VinaTech</p>
          <input type="email" placeholder="Email của bạn" />
          <button type="button">THAM GIA NGAY</button>
        </div>

      </aside>
    </div>
  </section>
</template>

<style scoped>

/* ========== BASE ========== */
* { box-sizing: border-box; }

.news-page {
  background: var(--tn-surface-2);
  min-height: 100vh;
}

/* ========== HEADER ========== */
.news-header {
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  padding: 20px 0 0;
}

.news-header-inner {
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 24px;
}

.header-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #2563eb;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.news-header h1 {
  font-size: 30px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 12px;
  line-height: 1.2;
}

.news-header h1 span { color: #4f46e5; }

.tabs {
  display: flex;
  gap: 0;
}

.tabs button {
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font: 600 13px/1 inherit;
  color: #64748b;
  padding: 10px 16px;
  transition: color 0.2s, border-color 0.2s;
}

.tabs button:hover { color: #2563eb; }
.tabs button.active { color: #2563eb; border-bottom-color: #2563eb; font-weight: 700; }

/* ========== BODY LAYOUT ========== */
.news-body {
  max-width: 1180px;
  margin: 0 auto;
  padding: 20px 24px;
  display: grid;
  grid-template-columns: 1fr 280px;
  gap: 20px;
  align-items: start;
}

/* ========== STATE ========== */
.state-box {
  background: white;
  border-radius: 12px;
  border: 1px solid var(--tn-border);
  padding: 28px;
  text-align: center;
  color: #64748b;
  font-size: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.state-box.error { background: #fef2f2; border-color: #fecaca; color: #dc2626; }

.loading-dots { display: flex; gap: 6px; }
.loading-dots span {
  width: 9px; height: 9px;
  border-radius: 50%;
  background: #2563eb;
  animation: bounce 1s ease-in-out infinite;
}
.loading-dots span:nth-child(2) { animation-delay: .2s; }
.loading-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes bounce {
  0%, 80%, 100% { transform: scale(.6); opacity: .4; }
  40% { transform: scale(1); opacity: 1; }
}

/* ========== HERO CARD ========== */
.hero-card {
  display: grid;
  grid-template-columns: 340px 1fr;
  background: white;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid var(--tn-border);
  text-decoration: none;
  margin-bottom: 16px;
  transition: box-shadow 0.25s, transform 0.25s;
}

.hero-card:hover {
  box-shadow: 0 8px 28px rgba(37, 99, 235, 0.12);
  transform: translateY(-2px);
}

.hero-img {
  position: relative;
  height: 240px;
  overflow: hidden;
  background: var(--tn-bg);
}

.hero-img img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.4s;
}

.hero-card:hover .hero-img img { transform: scale(1.05); }

.hero-cat {
  position: absolute; top: 10px; left: 10px;
  background: #2563eb; color: #fff;
  font-size: 9px; font-weight: 800; letter-spacing: 0.07em;
  text-transform: uppercase; padding: 3px 10px; border-radius: 20px;
}

.hero-badge {
  position: absolute; top: 10px; right: 10px;
  background: rgba(255,255,255,0.9); color: #0f172a;
  font-size: 10px; font-weight: 700;
  padding: 3px 8px; border-radius: 20px;
  backdrop-filter: blur(4px);
}

.hero-body {
  padding: 20px 22px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.hero-date { font-size: 11px; color: #94a3b8; font-weight: 500; margin: 0 0 8px; }

.hero-body h2 {
  font-size: 19px; font-weight: 800; color: #0f172a;
  line-height: 1.35; margin: 0 0 8px;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.hero-excerpt {
  font-size: 13px; color: #475569; line-height: 1.6;
  display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
  overflow: hidden; margin: 0 0 14px;
}

.hero-cta {
  font-size: 12px; font-weight: 700; color: #2563eb;
  margin-top: auto;
}

/* ========== SECTION LABEL ========== */
.section-label {
  display: flex; align-items: center; gap: 12px;
  margin-bottom: 12px;
}

.section-label span {
  font-size: 12px; font-weight: 800; color: #475569;
  white-space: nowrap; text-transform: uppercase; letter-spacing: 0.06em;
}

.section-line { flex: 1; height: 1px; background: #e2e8f0; }

/* ========== NEWS GRID ========== */
.news-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}

.news-card {
  background: white;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid var(--tn-border);
  text-decoration: none;
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.22s, transform 0.22s, border-color 0.22s;
}

.news-card:hover {
  box-shadow: 0 6px 22px rgba(37, 99, 235, 0.1);
  transform: translateY(-3px);
  border-color: #bfdbfe;
}

.card-thumb {
  position: relative;
  height: 150px;
  overflow: hidden;
  background: var(--tn-bg);
}

.card-thumb img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.4s;
}

.news-card:hover .card-thumb img { transform: scale(1.07); }

.card-cat-badge {
  position: absolute; top: 7px; left: 7px;
  background: rgba(37, 99, 235, 0.88); color: #fff;
  font-size: 8px; font-weight: 800; letter-spacing: 0.06em;
  text-transform: uppercase; padding: 2px 7px; border-radius: 10px;
}

.card-content {
  padding: 12px;
  display: flex; flex-direction: column; flex: 1;
}

.card-date { font-size: 10px; color: #94a3b8; font-weight: 500; display: block; margin-bottom: 5px; }

.card-content h3 {
  font-size: 13px; font-weight: 700; color: #0f172a;
  line-height: 1.4; margin: 0 0 5px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-content p {
  font-size: 12px; color: #64748b; line-height: 1.5;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden; margin: 0 0 8px; flex: 1;
}

.card-read { font-size: 11px; font-weight: 700; color: #2563eb; margin-top: auto; }

/* ========== PAGINATION ========== */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-top: 32px;
  padding: 10px 0;
}

.pg-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 38px;
  height: 38px;
  padding: 0 6px;
  border-radius: 10px;
  border: 1px solid var(--tn-border);
  background: white;
  font-size: 14px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
}

.pg-btn:hover:not(:disabled) {
  border-color: #2563eb;
  color: #2563eb;
  background: var(--tn-bg);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
}

.pg-btn.pg-active {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  border-color: #2563eb;
  color: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.pg-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: var(--tn-surface-2);
  border-color: #e2e8f0;
  box-shadow: none;
  transform: none;
}

.pg-dots {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  font-size: 14px;
  color: #94a3b8;
  font-weight: 600;
}

/* ========== SIDEBAR ========== */
.news-sidebar {
  display: flex; flex-direction: column; gap: 14px;
  position: sticky; top: 16px;
}

.sidebar-widget {
  background: white;
  border-radius: 14px;
  padding: 16px;
  border: 1px solid var(--tn-border);
}

.widget-title {
  font-size: 12px; font-weight: 800; color: #0f172a;
  margin: 0 0 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f1f5f9;
}

/* Popular */
.popular-list { display: flex; flex-direction: column; gap: 10px; }

.popular-item {
  display: flex; align-items: center; gap: 8px;
  text-decoration: none;
  transition: opacity 0.2s;
}

.popular-item:hover { opacity: 0.7; }

.pop-rank {
  font-size: 13px; font-weight: 900; width: 18px;
  text-align: center; flex-shrink: 0; color: #cbd5e1;
}

.rank-1 { color: #f59e0b; }
.rank-2 { color: #94a3b8; }
.rank-3 { color: #cd7c2f; }

.pop-thumb {
  width: 48px; height: 48px; border-radius: 8px;
  overflow: hidden; background: var(--tn-bg); flex-shrink: 0;
  border: 1px solid var(--tn-border);
}

.pop-thumb img { width: 100%; height: 100%; object-fit: cover; }

.pop-info p {
  font-size: 12px; font-weight: 600; color: #0f172a;
  margin: 0 0 2px; line-height: 1.35;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
  overflow: hidden;
}

.pop-info span, .muted-text { font-size: 10px; color: #94a3b8; }

/* Cat tags */
.cat-tags { display: flex; flex-wrap: wrap; gap: 6px; }

.cat-tags button {
  background: var(--tn-surface-2); border: 1px solid var(--tn-border);
  border-radius: 8px; color: #475569;
  font-size: 11px; font-weight: 600; padding: 5px 10px;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}

.cat-tags button:hover,
.cat-tags button.active { background: #2563eb; border-color: #2563eb; color: white; }

/* CTA */
.subscribe-cta {
  background: linear-gradient(135deg, #1d4ed8 0%, #4f46e5 100%);
  border-radius: 14px; color: white; padding: 18px; text-align: center;
}

.cta-emoji { font-size: 26px; margin-bottom: 6px; }

.subscribe-cta h3 { font-size: 15px; font-weight: 800; margin: 0 0 5px; }

.subscribe-cta p {
  font-size: 11px; color: rgba(255,255,255,0.75);
  margin: 0 0 12px; line-height: 1.5;
}

.subscribe-cta input {
  width: 100%; border: 1px solid rgba(255,255,255,0.3);
  border-radius: 8px; padding: 9px 11px;
  font-size: 12px; background: rgba(255,255,255,0.12);
  color: white; outline: none; margin-bottom: 8px; font-family: inherit;
}

.subscribe-cta input::placeholder { color: rgba(255,255,255,0.55); }

.subscribe-cta button {
  width: 100%; background: white; border: none;
  border-radius: 8px; color: #1d4ed8;
  font-size: 11px; font-weight: 800; padding: 9px;
  cursor: pointer; letter-spacing: 0.05em;
  transition: all 0.2s; font-family: inherit;
}

.subscribe-cta button:hover { background: #eff6ff; transform: translateY(-1px); }

/* ========== RESPONSIVE ========== */
@media (max-width: 1024px) {
  .news-body {
    grid-template-columns: 1fr;
    padding: 16px 20px;
  }
  .news-sidebar {
    position: static;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  .subscribe-cta { grid-column: 1 / -1; }
  .hero-card { grid-template-columns: 280px 1fr; }
  .hero-img { height: 200px; }
}

@media (max-width: 768px) {
  .news-grid { grid-template-columns: repeat(2, 1fr); }
  .hero-card { grid-template-columns: 1fr; }
  .hero-img { height: 200px; }
}

@media (max-width: 560px) {
  .news-header h1 { font-size: 24px; }
  .news-body { padding: 12px 16px; }
  .news-grid { grid-template-columns: 1fr; }
  .news-sidebar { grid-template-columns: 1fr; }
  .tabs button { padding: 8px 10px; font-size: 12px; }
}
</style>
