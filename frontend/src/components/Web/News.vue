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

const getReadingTime = (content) => {
  if (!content) return 3
  const words = content.replace(/<[^>]*>/g, '').trim().split(/\s+/).length
  return Math.ceil(words / 200) || 1
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

  // Reading progress indicator script setup
  window.addEventListener('scroll', () => {
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
    const bar = document.getElementById('newsProgressBar');
    if (bar) bar.style.width = scrolled + '%';
  });
})
</script>

<template>
  <!-- TOP READING PROGRESS BAR -->
  <div class="reading-progress-container">
    <div class="progress-bar" id="newsProgressBar"></div>
  </div>

  <section class="news-page">

    <!-- ===== EDITORIAL HEADER ===== -->
    <div class="news-header">
      <div class="news-header-inner">
        <div class="magazine-breadcrumb">
          <router-link to="/">Trang chủ</router-link>
          <span class="sep">/</span>
          <span class="current">Tạp chí công nghệ</span>
        </div>

        <span class="header-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" style="display: inline-block; vertical-align: middle; margin-right: 4px; color: #3b82f6;">
            <circle cx="12" cy="12" r="2"/>
            <path d="M12 2a10 10 0 0 0-7.07 17.07l1.41-1.41a8 8 0 1 1 11.32 0l1.41 1.41A10 10 0 0 0 12 2Z"/>
            <path d="M12 6a6 6 0 0 0-4.24 10.24l1.41-1.41a4 4 0 1 1 5.66 0l1.41 1.41A6 6 0 0 0 12 6Z"/>
          </svg>
          PREDATOR CYBER-MEDIA HUB
        </span>
        <h1>Trải nghiệm <span>vũ trụ công nghệ.</span></h1>
        <p class="header-description">Kênh truyền thông chính thức của Predator - Cập nhật những phát kiến phần cứng, trí tuệ nhân tạo local và cẩm nang công nghệ chuyên sâu.</p>

        <nav class="tabs">
          <button
            v-for="tab in tabs"
            :key="tab"
            :class="{ active: selectedCategory === tab }"
            @click="selectTab(tab)"
          >
            <span class="tab-text">{{ tab }}</span>
            <span class="tab-indicator"></span>
          </button>
        </nav>
      </div>
    </div>

    <!-- ===== BODY ===== -->
    <div class="news-body">
      <!-- Main Feed (Left) -->
      <div class="news-main">
        <!-- Loading -->
        <div v-if="loading" class="magazine-loader">
          <div class="loader-ripple"></div>
          <p>Đang giải mã bản tin công nghệ...</p>
        </div>

        <!-- Error -->
        <div v-else-if="errorMessage" class="magazine-error-state">
          <span class="error-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="display: inline-block; vertical-align: middle; color: #ef4444;">
              <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
              <line x1="12" y1="9" x2="12" y2="13"/>
              <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
          </span>
          <p>{{ errorMessage }}</p>
        </div>

        <template v-else>
          <!-- FEATURED STORY (EDITORIAL HERO) -->
          <div v-if="randomPost" class="editorial-hero-showcase">
            <RouterLink :to="'/news/' + randomPost.id" class="hero-link-wrapper">
              <div class="hero-split-grid">
                <!-- Left: Media Viewport -->
                <div class="hero-media-side">
                  <div class="image-gradient-overlay"></div>
                  <span class="hero-editorial-label">FEATURED BRIEF</span>
                  <img :src="imageUrl(randomPost.hinhanh)" :alt="randomPost.mota_hinhanh || randomPost.tieude" class="hero-featured-img" @error="e => e.target.src = placeholderImage" />
                </div>

                 <!-- Right: Content Side -->
                  <div class="hero-content-side">
                    <div class="hero-meta-row">
                      <span class="hero-category-tag">{{ randomPost.danhmuc }}</span>
                      <span class="meta-dot">•</span>
                      <span class="hero-reading-time">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                          <circle cx="12" cy="12" r="10"/>
                          <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        {{ getReadingTime(randomPost.noidung) }} phút đọc
                      </span>
                    </div>

                   <h2 class="hero-headline">{{ randomPost.tieude }}</h2>
                   <p class="hero-abstract" v-if="randomPost.tomtat">{{ randomPost.tomtat }}</p>

                   <div class="hero-author-row">
                     <div class="author-avatar">NG</div>
                     <div class="author-info">
                       <span class="author-name">{{ randomPost.tacgia || 'Predator Staff' }}</span>
                       <span class="published-date">{{ formatDate(randomPost.dang_luc || randomPost.created_at) }}</span>
                     </div>
                   </div>

                  <span class="hero-action-link">ĐỌC BÀI VIẾT ĐẦY ĐỦ <span class="arrow">→</span></span>
                </div>
              </div>
            </RouterLink>
          </div>

          <!-- TRENDING MAGAZINE RADAR (Buying Guides & Expert picks) -->
          <div class="tech-radar-section" v-if="posts.length > 0">
            <div class="radar-header">
              <h3>
                <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20" style="display: inline-block; vertical-align: middle; margin-right: 8px; color: #f59e0b;">
                  <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
                Tech Radar: Predator Khuyên Dùng
              </h3>
              <p>Các cẩm nang lựa chọn thiết bị công nghệ chuyên nghiệp được biên soạn bởi chuyên gia Predator</p>
            </div>

            <div class="radar-grid">
              <div class="radar-card radar-glow-1">
                <div class="radar-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                    <line x1="2" y1="20" x2="22" y2="20"/>
                    <line x1="12" y1="17" x2="12" y2="20"/>
                  </svg>
                </div>
                <h4>AI Laptops 2026</h4>
                <p>Xu thế laptop tích hợp NPU tối ưu hóa sâu các công việc trí tuệ nhân tạo local.</p>
                <span class="radar-link">Khám phá radar ➜</span>
              </div>
              <div class="radar-card radar-glow-2">
                <div class="radar-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="6" y1="12" x2="10" y2="12"/>
                    <line x1="8" y1="10" x2="8" y2="14"/>
                    <line x1="15" y1="13" x2="15.01" y2="13"/>
                    <line x1="18" y1="11" x2="18.01" y2="11"/>
                    <rect x="2" y="6" width="20" height="12" rx="3"/>
                  </svg>
                </div>
                <h4>RTX 5000 Series</h4>
                <p>Phân tích hiệu năng dòng card đồ họa kiến trúc Blackwell sắp ra mắt trên laptop gaming.</p>
                <span class="radar-link">Xem đánh giá ➜</span>
              </div>
              <div class="radar-card radar-glow-3">
                <div class="radar-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4.5 16.5c-1.5 1.25-2.5 3.5-2.5 3.5s2.25-1 3.5-2.5" />
                    <path d="M12 12c2-2 3-5.5 3-5.5s-3.5 1-5.5 3" />
                    <path d="M19 5c1.8-1.8 3-5 3-5s-3.2 1.2-5 3" />
                    <path d="M14 15l-3.5-3.5" />
                    <path d="M6.5 12.5l3.5 3.5" />
                    <path d="M14 9l-2.5-2.5" />
                    <path d="M9 14l2.5 2.5" />
                  </svg>
                </div>
                <h4>Lunar Lake Guide</h4>
                <p>Bí quyết khai phóng hiệu suất vi xử lý Intel Core Ultra thế hệ mới tiết kiệm pin tối đa.</p>
                <span class="radar-link">Đọc cẩm nang ➜</span>
              </div>
            </div>
          </div>

          <!-- LISTING SECTION HEADER -->
          <div class="magazine-section-title" v-if="posts.length > 0">
            <h3>LATEST BRIEFINGS / BÀI VIẾT MỚI NHẤT</h3>
            <span class="line-decorator"></span>
          </div>

          <!-- GRID LISTING CARDS -->
          <div class="magazine-grid">
            <RouterLink v-for="post in posts" :key="post.id" :to="'/news/' + post.id" class="magazine-card">
              <div class="card-media-viewport">
                <img :src="imageUrl(post.hinhanh)" :alt="post.mota_hinhanh || post.tieude" class="card-featured-img" loading="lazy" @error="e => e.target.src = placeholderImage" />
                <span class="card-category-badge">{{ post.danhmuc }}</span>
              </div>
              <div class="card-editorial-body">
                <div class="card-meta-line">
                  <span class="card-pub-date">{{ formatDate(post.dang_luc || post.created_at) }}</span>
                  <span class="meta-dot">•</span>
                  <span class="card-read-time">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                      <circle cx="12" cy="12" r="10"/>
                      <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    {{ getReadingTime(post.noidung) }} phút đọc
                  </span>
                </div>
                <h3 class="card-headline">{{ post.tieude }}</h3>
                <p class="card-excerpt" v-if="post.tomtat">{{ post.tomtat }}</p>

                <div class="card-bottom-row">
                  <span class="card-author-name">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                      <circle cx="12" cy="7" r="4"/>
                    </svg>
                    {{ post.tacgia || 'Predator staff' }}
                  </span>
                  <span class="card-read-more-link">Khám phá <span class="arrow">→</span></span>
                </div>
              </div>
            </RouterLink>

            <div v-if="posts.length === 0" class="empty-state-card" style="grid-column: 1 / -1">
              <span class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48" style="color: #94a3b8; display: inline-block; margin-bottom: 12px;">
                  <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>
                  <path d="M18 14h-8"/>
                  <path d="M15 18h-5"/>
                  <path d="M10 6h8v4h-8V6Z"/>
                </svg>
              </span>
              <h4>Không tìm thấy bài viết</h4>
              <p>Hiện chưa có bài viết nào thuộc chuyên mục này. Hãy quay lại sau để cập nhật các tin tức mới nhất.</p>
            </div>
          </div>

          <!-- PREMIUM PAGINATION CONTROLLER -->
          <div class="magazine-pagination" v-if="lastPage > 1">
            <button
              class="pagination-arrow-btn prev-btn"
              :disabled="currentPage <= 1"
              @click="fetchNews(currentPage - 1)"
              aria-label="Trang trước"
            >
              &laquo; Trước
            </button>

            <div class="pagination-numbers-row">
              <template v-for="(page, idx) in displayedPages" :key="idx">
                <span v-if="page === '...'" class="pagination-dots">...</span>
                <button
                  v-else
                  class="pagination-num-btn"
                  :class="{ active: currentPage === page }"
                  @click="fetchNews(page)"
                >
                  {{ page }}
                </button>
              </template>
            </div>

            <button
              class="pagination-arrow-btn next-btn"
              :disabled="currentPage >= lastPage"
              @click="fetchNews(currentPage + 1)"
              aria-label="Trang sau"
            >
              Sau &raquo;
            </button>
          </div>
        </template>
      </div>

      <!-- DISCOVERY EDITORIAL SIDEBAR (Right) -->
      <aside class="magazine-sidebar">
        <!-- Editor's Weekly Picks -->
        <div class="sidebar-magazine-widget">
          <h4 class="widget-magazine-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12" style="display: inline-block; vertical-align: middle; margin-right: 4px; color: #ef4444;">
              <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>
            </svg>
            BÀI VIẾT NỔI BẬT TRONG TUẦN
          </h4>
          <div class="popular-magazine-list">
            <RouterLink
              v-for="(item, idx) in popularPosts"
              :key="item.id"
              :to="'/news/' + item.id"
              class="popular-magazine-item"
            >
              <div class="pop-rank-circle" :class="'rank-color-' + (idx+1)">{{ idx + 1 }}</div>
              <div class="pop-magazine-thumb">
                <img :src="imageUrl(item.hinhanh)" :alt="item.mota_hinhanh || item.tieude" @error="e => e.target.src = placeholderImage" />
              </div>
              <div class="pop-magazine-info">
                <h5>{{ item.tieude }}</h5>
                <div class="pop-views-badge">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" style="display: inline-block; vertical-align: middle; margin-right: 4px; color: #94a3b8;">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                  {{ item.luotxem || 0 }} lượt xem
                </div>
              </div>
            </RouterLink>

            <div v-if="popularPosts.length === 0" class="sidebar-empty-fallback">
              Chưa có dữ liệu bài viết phổ biến.
            </div>
          </div>
        </div>

        <!-- Chuyên mục (Sidebar tags) -->
        <div class="sidebar-magazine-widget">
          <h4 class="widget-magazine-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12" style="display: inline-block; vertical-align: middle; margin-right: 4px; color: #3b82f6;">
              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            KHÁM PHÁ CHUYÊN MỤC
          </h4>
          <div class="cyber-tags-cloud">
            <button
              v-for="tag in categories"
              :key="tag"
              :class="{ active: selectedCategory === tag }"
              @click="selectTab(tag)"
            >
              <span class="tag-hash">#</span>
              <span class="tag-label">{{ tag }}</span>
            </button>
          </div>
        </div>

        <!-- Xuương tìm kiếm -->
        <div class="sidebar-magazine-widget">
          <h4 class="widget-magazine-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12" style="display: inline-block; vertical-align: middle; margin-right: 4px; color: #10b981;">
              <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
              <polyline points="16 7 22 7 22 13"/>
            </svg>
            XU HƯỚNG TÌM KIẾM
          </h4>
          <div class="search-trends-list">
            <a href="/products?search=RTX%2040" class="trend-item">
              <span class="trend-rank">#1</span>
              <span class="trend-query">Card đồ họa RTX 40-Series</span>
              <span class="trend-icon">↗</span>
            </a>
            <a href="/products?search=OLED" class="trend-item">
              <span class="trend-rank">#2</span>
              <span class="trend-query">Màn hình OLED 240Hz</span>
              <span class="trend-icon">↗</span>
            </a>
            <a href="/products?search=Ultra" class="trend-item">
              <span class="trend-rank">#3</span>
              <span class="trend-query">Intel Core Ultra AI</span>
              <span class="trend-icon">↗</span>
            </a>
            <a href="/products?search=MacBook" class="trend-item">
              <span class="trend-rank">#4</span>
              <span class="trend-query">MacBook Pro M3 Max</span>
              <span class="trend-icon">↗</span>
            </a>
          </div>
        </div>

        <!-- PREMIUM MEMBERSHIP NEWSLETTER CARD -->
        <div class="premium-subscribe-block">
          <div class="glow-accent-overlay"></div>
          <div class="newsletter-brand-header">
            <span class="badge-mini">NEWSLETTER</span>
            <h3>Join Predator Cyber-Tech Weekly</h3>
            <p>Tham gia cùng hơn 50.000+ kỹ sư và người yêu công nghệ nhận bản tin phân tích độc quyền hàng tuần.</p>
          </div>

          <ul class="subscribe-benefits-list">
            <li>
              <span class="check-icon">✓</span>
              <span class="benefit-txt">Tin tức trí tuệ nhân tạo AI local</span>
            </li>
            <li>
              <span class="check-icon">✓</span>
              <span class="benefit-txt">Cẩm nang chọn cấu hình Laptop chuyên sâu</span>
            </li>
            <li>
              <span class="check-icon">✓</span>
              <span class="benefit-txt">Đánh giá linh kiện & phần cứng tương lai</span>
            </li>
            <li>
              <span class="check-icon">✓</span>
              <span class="benefit-txt">Mã giảm giá mua sắm độc quyền Predator</span>
            </li>
          </ul>

          <div class="newsletter-form-wrapper">
            <input type="email" placeholder="Địa chỉ Email của bạn" class="newsletter-cyber-input" aria-label="Địa chỉ email nhận tin" />
            <button type="button" class="newsletter-cyber-btn">
              THAM GIA NGAY ➜
            </button>
          </div>

          <span class="privacy-note">Cam kết bảo mật 100% · Hủy đăng ký bất kỳ lúc nào</span>
        </div>
      </aside>
    </div>
  </section>
</template>

<style scoped>

@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap');

/* ==================== GENERAL CONFIG ==================== */
.news-page {
  --primary: #2563EB;
  --primary-glow: rgba(37, 99, 235, 0.15);
  --secondary: #06B6D4;
  --secondary-glow: rgba(6, 182, 212, 0.15);
  --accent: #f59e0b;
  --dark-bg: #0F172A;
  --dark-surface: #111827;
  --light-bg: #0d1b2e;
  --light-surface: #111f35;
  --text-primary: #0F172A;
  --text-secondary: #475569;
  --border-color: #cbd5e1;
  --card-glow: 0px 8px 30px rgba(0, 0, 0, 0.04);
  --font-heading: 'Outfit', 'Inter', sans-serif;
  --font-body: 'Inter', sans-serif;
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

  background-color: var(--tn-bg);
  color: var(--text-primary);
  font-family: var(--font-body);
  overflow-x: hidden;
  position: relative;
  min-height: 100vh;
}

/* ==================== TOAST & READING PROGRESS ==================== */
.toast {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 10000;
  padding: 16px 24px;
  border-radius: 16px;
  font-family: var(--font-heading);
  font-size: 15px;
  font-weight: 600;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
  color: white;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}
.toast.error {
  background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
}

.reading-progress-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: rgba(226, 232, 240, 0.5);
  z-index: 9999;
}
.progress-bar {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
  transition: width 0.1s ease-out;
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: var(--transition);
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* ==================== EDITORIAL HEADER ==================== */
.news-header {
  background: var(--tn-surface);
  border-bottom: 1px solid #e2e8f0;
  padding: 24px 0 0 0;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
}
.news-header-inner {
  max-width: 1300px;
  margin: 0 auto;
  padding: 0 24px;
}

.magazine-breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-secondary);
  margin-bottom: 24px;
}
.magazine-breadcrumb a {
  color: var(--text-secondary);
  text-decoration: none;
  transition: var(--transition);
}
.magazine-breadcrumb a:hover {
  color: var(--primary);
}
.magazine-breadcrumb .sep {
  color: #cbd5e1;
}
.magazine-breadcrumb .current {
  color: var(--primary);
}

.header-badge {
  display: inline-block;
  font-family: var(--font-heading);
  font-size: 11px;
  font-weight: 800;
  color: var(--primary);
  letter-spacing: 2px;
  margin-bottom: 8px;
  background: rgba(37, 99, 235, 0.05);
  padding: 4px 12px;
  border-radius: 30px;
}

.news-header h1 {
  font-family: var(--font-heading);
  font-size: 42px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 12px 0;
  letter-spacing: -1.5px;
}
.news-header h1 span {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.header-description {
  font-size: 15px;
  color: #475569;
  line-height: 1.6;
  max-width: 700px;
  margin: 0 0 32px 0;
}

.tabs {
  display: flex;
  gap: 8px;
}
.tabs button {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 14px 20px;
  color: var(--text-secondary);
  font-family: var(--font-heading);
  font-size: 14px;
  font-weight: 600;
  position: relative;
  transition: var(--transition);
}
.tabs button:hover {
  color: var(--primary);
}
.tab-indicator {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: var(--primary);
  border-radius: 3px 3px 0 0;
  transform: scaleX(0);
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.tabs button.active {
  color: var(--primary);
  font-weight: 800;
}
.tabs button.active .tab-indicator {
  transform: scaleX(1);
}

/* ==================== BODY LAYOUT ==================== */
.news-body {
  max-width: 1300px;
  margin: 0 auto;
  padding: 40px 24px;
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 40px;
  align-items: start;
}

.news-main {
  display: flex;
  flex-direction: column;
  gap: 40px;
}

/* ==================== STATE BOXES ==================== */
.magazine-loader {
  padding: 60px;
  text-align: center;
  background: var(--tn-surface);
  border-radius: 24px;
  border: 1px solid #e6eef6;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}
.loader-ripple {
  width: 48px;
  height: 48px;
  border: 3.5px solid rgba(37,99,235,0.1);
  border-radius: 50%;
  border-top-color: var(--primary);
  animation: spin 1s infinite linear;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.magazine-loader p {
  font-family: var(--font-heading);
  font-size: 15px;
  font-weight: 600;
  color: var(--text-secondary);
}

.magazine-error-state {
  padding: 40px;
  text-align: center;
  background: #fef2f2;
  border-radius: 20px;
  border: 1px solid #fecaca;
  color: #dc2626;
  font-weight: 600;
}

/* ==================== FEATURED HERO STORY ==================== */
.editorial-hero-showcase {
  border-radius: 28px;
  background: var(--tn-surface);
  overflow: hidden;
  border: 1px solid #e6eef6;
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
  transition: var(--transition);
}
.hero-link-wrapper {
  text-decoration: none;
  color: inherit;
}
.hero-split-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  min-height: 400px;
}
.hero-media-side {
  position: relative;
  overflow: hidden;
  background: var(--tn-bg);
}
.hero-featured-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.editorial-hero-showcase:hover .hero-featured-img {
  transform: scale(1.04);
}
.image-gradient-overlay {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background: linear-gradient(to right, rgba(0,0,0,0.3) 0%, transparent 100%);
  z-index: 1;
}
.hero-editorial-label {
  position: absolute;
  top: 24px; left: 24px;
  padding: 6px 12px;
  border-radius: 4px;
  background: var(--primary);
  color: white;
  font-family: var(--font-heading);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1px;
  z-index: 2;
  box-shadow: 0 8px 16px rgba(37, 99, 235, 0.25);
}

.hero-content-side {
  padding: 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: var(--tn-surface);
}
.hero-meta-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}
.hero-category-tag {
  font-family: var(--font-heading);
  font-size: 12px;
  font-weight: 800;
  color: var(--secondary);
}
.meta-dot {
  color: #94a3b8;
}
.hero-reading-time {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}

.hero-headline {
  font-family: var(--font-heading);
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.3;
  margin: 0 0 14px 0;
  letter-spacing: -0.5px;
  transition: color 0.2s ease;
}
.editorial-hero-showcase:hover .hero-headline {
  color: var(--primary);
}

.hero-abstract {
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0 0 24px 0;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.hero-author-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 28px;
  padding-top: 16px;
  border-top: 1px solid #e6eef6;
}
.author-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
}
.author-info {
  display: flex;
  flex-direction: column;
}
.author-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
}
.published-date {
  font-size: 11.5px;
  color: var(--text-secondary);
  margin-top: 2px;
}

.hero-action-link {
  font-family: var(--font-heading);
  font-size: 12px;
  font-weight: 800;
  color: var(--primary);
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: auto;
  transition: var(--transition);
}
.editorial-hero-showcase:hover .hero-action-link {
  color: #1d4ed8;
}
.editorial-hero-showcase:hover .hero-action-link .arrow {
  transform: translateX(4px);
}
.hero-action-link .arrow {
  transition: transform 0.2s ease;
}

.editorial-hero-showcase:hover {
  box-shadow: 0 30px 60px rgba(15, 23, 42, 0.08);
  transform: translateY(-2px);
}

/* ==================== TECH RADAR SECTION ==================== */
.tech-radar-section {
  padding: 24px;
  border-radius: 24px;
  background: var(--tn-surface);
  border: 1px solid #e6eef6;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
}
.radar-header {
  margin-bottom: 24px;
}
.radar-header h3 {
  font-family: var(--font-heading);
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 6px 0;
}
.radar-header p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}
.radar-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}
.radar-card {
  padding: 20px;
  border-radius: 16px;
  background: var(--tn-bg);
  border: 1px solid #e6eef6;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  flex-direction: column;
}
.radar-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.radar-icon svg {
  width: 22px;
  height: 22px;
  transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Color codes for each Tech Radar theme */
.radar-glow-1 .radar-icon {
  background: rgba(59, 130, 246, 0.08);
  color: #3b82f6;
}
.radar-glow-2 .radar-icon {
  background: rgba(139, 92, 246, 0.08);
  color: #8b5cf6;
}
.radar-glow-3 .radar-icon {
  background: rgba(249, 115, 22, 0.08);
  color: #f97316;
}

/* Hover micro-animations */
.radar-card:hover .radar-icon {
  transform: scale(1.1) rotate(4deg);
}
.radar-card:hover .radar-icon svg {
  transform: scale(1.1);
}
.radar-card.radar-glow-1:hover {
  transform: translateY(-4px);
  background: #eff6ff;
  border-color: #3b82f6;
  box-shadow: 0 12px 24px rgba(59, 130, 246, 0.12);
}
.radar-card.radar-glow-1:hover .radar-icon {
  background: #3b82f6;
  color: #ffffff;
}
.radar-card.radar-glow-2:hover {
  transform: translateY(-4px);
  background: #f5f3ff;
  border-color: #8b5cf6;
  box-shadow: 0 12px 24px rgba(139, 92, 246, 0.12);
}
.radar-card.radar-glow-2:hover .radar-icon {
  background: #8b5cf6;
  color: #ffffff;
}
.radar-card.radar-glow-3:hover {
  transform: translateY(-4px);
  background: #fff7ed;
  border-color: #f97316;
  box-shadow: 0 12px 24px rgba(249, 115, 22, 0.12);
}
.radar-card.radar-glow-3:hover .radar-icon {
  background: #f97316;
  color: #ffffff;
}
.radar-card h4 {
  font-family: var(--font-heading);
  font-size: 14px;
  font-weight: 800;
  margin: 0 0 6px 0;
  color: var(--text-primary);
}
.radar-card p {
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0 0 16px 0;
  flex-grow: 1;
}
.radar-link {
  font-family: var(--font-heading);
  font-size: 11px;
  font-weight: 800;
  color: var(--primary);
  margin-top: auto;
}

/* ==================== LISTING DIVIDER ==================== */
.magazine-section-title {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 12px;
}
.magazine-section-title h3 {
  font-family: var(--font-heading);
  font-size: 13px;
  font-weight: 800;
  color: var(--text-secondary);
  letter-spacing: 1px;
  white-space: nowrap;
}
.line-decorator {
  flex-grow: 1;
  height: 1px;
  background: #cbd5e1;
}

/* ==================== MAGAZINE ARTICLE GRID ==================== */
.magazine-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.magazine-card {
  background: var(--tn-surface);
  border-radius: 20px;
  border: 1px solid #e6eef6;
  overflow: hidden;
  text-decoration: none;
  display: flex;
  flex-direction: column;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
  transition: var(--transition);
}

.card-media-viewport {
  position: relative;
  aspect-ratio: 16/10;
  overflow: hidden;
  background: #e2e8f0;
}
.card-featured-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.magazine-card:hover .card-featured-img {
  transform: scale(1.05);
}
.card-category-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  padding: 4px 10px;
  border-radius: 4px;
  background: rgba(37, 99, 235, 0.12);
  backdrop-filter: blur(4px);
  color: var(--primary);
  font-family: var(--font-heading);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.5px;
  border: 1px solid rgba(37, 99, 235, 0.18);
}

.card-editorial-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}
.card-meta-line {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 10px;
}
.card-headline {
  font-family: var(--font-heading);
  font-size: 16px;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.4;
  margin: 0 0 8px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  height: 44px;
  transition: color 0.2s ease;
}
.magazine-card:hover .card-headline {
  color: var(--primary);
}

.card-excerpt {
  font-size: 12.5px;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0 0 18px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  height: 38px;
}

.card-bottom-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px solid #e6eef6;
}
.card-author-name {
  font-size: 11.5px;
  font-weight: 700;
  color: #64748b;
}
.card-read-more-link {
  font-family: var(--font-heading);
  font-size: 11.5px;
  font-weight: 800;
  color: var(--primary);
  display: flex;
  align-items: center;
  gap: 2px;
  transition: var(--transition);
}
.magazine-card:hover .card-read-more-link {
  color: #1d4ed8;
}
.magazine-card:hover .card-read-more-link .arrow {
  transform: translateX(3px);
}
.card-read-more-link .arrow {
  transition: transform 0.2s ease;
}

.magazine-card:hover {
  box-shadow: 0 15px 35px rgba(15, 23, 42, 0.06);
  transform: translateY(-4px);
  border-color: var(--primary);
}

.empty-state-card {
  padding: 60px;
  text-align: center;
  background: var(--tn-surface);
  border-radius: 20px;
  border: 1.5px dashed #e6eef6;
  color: #64748b;
  max-width: 480px;
  margin: 0 auto;
}
.empty-state-card .icon {
  font-size: 40px;
  margin-bottom: 12px;
  display: block;
}
.empty-state-card h4 {
  font-family: var(--font-heading);
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 6px 0;
}
.empty-state-card p {
  font-size: 13px;
  line-height: 1.5;
  margin: 0;
}

/* ==================== PAGINATION ==================== */
.magazine-pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}
.pagination-arrow-btn {
  padding: 10px 18px;
  border-radius: 12px;
  border: 1px solid #e6eef6;
  background: var(--tn-surface);
  cursor: pointer;
  font-family: var(--font-heading);
  font-size: 13px;
  font-weight: 700;
  color: #475569;
  transition: var(--transition);
}
.pagination-arrow-btn:hover:not(:disabled) {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
  box-shadow: 0 6px 15px var(--primary-glow);
}
.pagination-arrow-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.pagination-numbers-row {
  display: flex;
  gap: 6px;
}
.pagination-num-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid #e6eef6;
  background: var(--tn-surface);
  cursor: pointer;
  font-family: var(--font-heading);
  font-size: 14px;
  font-weight: 700;
  color: #475569;
  transition: var(--transition);
}
.pagination-num-btn.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
  box-shadow: 0 6px 15px var(--primary-glow);
}
.pagination-dots {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-weight: 700;
}

/* ==================== SIDEBAR ==================== */
.magazine-sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: sticky;
  top: 20px;
}

.sidebar-magazine-widget {
  background: var(--tn-surface);
  border-radius: 20px;
  padding: 24px;
  border: 1px solid #e6eef6;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
}
.widget-magazine-title {
  font-family: var(--font-heading);
  font-size: 12px;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: 1px;
  margin: 0 0 18px 0;
  padding-bottom: 12px;
  border-bottom: 1.5px solid #f1f5f9;
}

/* Sidebar Popular Items */
.popular-magazine-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.popular-magazine-item {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  background: var(--tn-bg);
  border-radius: 16px;
  padding: 14px 12px;
  transition: var(--transition);
}
.pop-rank-circle {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #cbd5e1;
  color: white;
  font-family: var(--font-heading);
  font-size: 12px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.pop-rank-circle.rank-color-1 { background: var(--primary); }
.pop-rank-circle.rank-color-2 { background: var(--secondary); }
.pop-rank-circle.rank-color-3 { background: var(--accent); }

.pop-magazine-thumb {
  width: 52px;
  height: 52px;
  border-radius: 8px;
  overflow: hidden;
  background: var(--tn-bg);
  border: 1px solid #e6eef6;
  flex-shrink: 0;
}
.pop-magazine-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.pop-magazine-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.pop-magazine-info h5 {
  font-size: 12px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  transition: color 0.2s;
}
.popular-magazine-item:hover h5 {
  color: var(--primary);
}
.pop-views-badge {
  font-size: 10px;
  color: var(--text-secondary);
}

.sidebar-empty-fallback {
  font-size: 12px;
  color: #64748b;
  text-align: center;
}

/* Sidebar tags cloud */
.cyber-tags-cloud {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.cyber-tags-cloud button {
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid #e6eef6;
  background: var(--tn-bg);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 2px;
  font-family: var(--font-heading);
  font-size: 11px;
  font-weight: 700;
  color: #475569;
  transition: var(--transition);
}
.cyber-tags-cloud .tag-hash {
  color: var(--primary);
}
.cyber-tags-cloud button:hover,
.cyber-tags-cloud button.active {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
  box-shadow: 0 4px 10px var(--primary-glow);
}
.cyber-tags-cloud button:hover .tag-hash,
.cyber-tags-cloud button.active .tag-hash {
  color: white;
}

/* ==================== PREMIUM SUBSCRIPTION BLOCK ==================== */
.premium-subscribe-block {
  position: relative;
  padding: 30px;
  border-radius: 24px;
  background: var(--tn-surface);
  border: 1px solid rgba(2,6,23,0.06);
  box-shadow: 0 10px 30px rgba(2,6,23,0.06);
  color: var(--text-primary);
  overflow: hidden;
}
.premium-subscribe-block .glow-accent-overlay {
  position: absolute;
  top: -50%; right: -20%;
  width: 90%; height: 180%;
  background: radial-gradient(circle, rgba(6, 182, 212, 0.06) 0%, transparent 60%);
  pointer-events: none;
}

.newsletter-brand-header {
  position: relative;
  z-index: 1;
  margin-bottom: 20px;
}
.newsletter-brand-header .badge-mini {
  display: inline-block;
  font-family: var(--font-heading);
  font-size: 9px;
  font-weight: 800;
  color: var(--secondary);
  letter-spacing: 1px;
  padding: 3px 8px;
  background: rgba(6, 182, 212, 0.1);
  border-radius: 4px;
  margin-bottom: 8px;
}
.newsletter-brand-header h3 {
  font-family: var(--font-heading);
  font-size: 18px;
  font-weight: 800;
  margin: 0 0 6px 0;
  line-height: 1.3;
}
.newsletter-brand-header p {
  font-size: 11.5px;
  color: #475569;
  line-height: 1.5;
  margin: 0;
}

.subscribe-benefits-list {
  position: relative;
  z-index: 1;
  list-style: none;
  padding: 0;
  margin: 0 0 24px 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.subscribe-benefits-list li {
  display: flex;
  align-items: center;
  gap: 8px;
}
.subscribe-benefits-list .check-icon {
  font-size: 12px;
  font-weight: 800;
  color: var(--secondary);
}
.subscribe-benefits-list .benefit-txt {
  font-size: 12px;
  color: #475569;
}

.newsletter-form-wrapper {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.newsletter-cyber-input {
  width: 100%;
  padding: 12px 16px;
  border-radius: 12px;
  background: var(--tn-surface);
  border: 1px solid #e6eef6;
  color: var(--text-primary);
  font-size: 13px;
  outline: none;
  font-family: inherit;
  transition: var(--transition);
}
.newsletter-cyber-input:focus {
  border-color: var(--secondary);
  background: var(--tn-surface);
}
.newsletter-cyber-input::placeholder {
  color: #94a3b8;
}

.newsletter-cyber-btn {
  width: 100%;
  padding: 12px;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
  color: white;
  font-family: var(--font-heading);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.5px;
  cursor: pointer;
  box-shadow: 0 8px 16px rgba(37, 99, 235, 0.12);
  transition: var(--transition);
}
.newsletter-cyber-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 18px rgba(37, 99, 235, 0.18);
}

.privacy-note {
  position: relative;
  z-index: 1;
  display: block;
  text-align: center;
  font-size: 9.5px;
  color: #64748b;
  margin-top: 12px;
}

/* ==================== RESPONSIVE LAYOUTS ==================== */
@media (max-width: 1100px) {
  .news-body {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  .magazine-sidebar {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    position: static;
  }
  .premium-subscribe-block {
    grid-column: 1 / -1;
  }
}

@media (max-width: 900px) {
  .hero-split-grid {
    grid-template-columns: 1fr;
  }
  .hero-media-side {
    aspect-ratio: 16/9;
  }
  .magazine-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .news-header h1 {
    font-size: 32px;
  }
  .tech-radar-grid {
    grid-template-columns: 1fr;
  }
  .magazine-sidebar {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 580px) {
  .magazine-grid {
    grid-template-columns: 1fr;
  }
  .news-header-inner {
    padding: 0 16px;
  }
  .news-body {
    padding: 24px 16px;
  }
  .tabs button {
    padding: 12px;
    font-size: 13px;
  }
}

/* Search Trends List */
.search-trends-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.trend-item {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-secondary);
  padding: 10px 14px;
  border-radius: 10px;
  background: var(--tn-bg);
  border: 1px solid #e6eef6;
  transition: var(--transition);
}
.trend-item:hover {
  background: var(--primary-glow);
  border-color: var(--primary);
  color: var(--primary);
  transform: translateX(4px);
}
.trend-rank {
  font-weight: 800;
  color: var(--primary);
  font-size: 12px;
}
.trend-query {
  flex-grow: 1;
}
.trend-icon {
  font-size: 12px;
  color: #94a3b8;
  transition: var(--transition);
}
.trend-item:hover .trend-icon {
  color: var(--primary);
  transform: translate(2px, -2px);
}
</style>
