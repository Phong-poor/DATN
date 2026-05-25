<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'
import { absoluteUrl, setSeo } from '@/services/seo'

const posts = ref([])
const popularPosts = ref([])
const selectedCategory = ref('Mới nhất')
const currentPage = ref(1)
const lastPage = ref(1)
const loading = ref(false)
const errorMessage = ref('')

const defaultCategories = ['Công nghệ', 'Sự kiện', 'Sản phẩm', 'Nội bộ']
const placeholderImage = 'https://via.placeholder.com/800x500?text=Tin+tuc'

const categories = computed(() => {
  const names = [...posts.value, ...popularPosts.value].map((item) => item?.category).filter(Boolean)
  return [...new Set([...defaultCategories, ...names])]
})
const tabs = computed(() => ['Mới nhất', ...categories.value.slice(0, 4)])

const applyListSeo = () => {
  const suffix = selectedCategory.value === 'Mới nhất' ? 'mới nhất' : selectedCategory.value.toLowerCase()
  setSeo({
    title: `Tin tức công nghệ ${suffix}`,
    description:
      'Cập nhật tin tức công nghệ, kinh nghiệm chọn laptop, đánh giá laptop gaming, laptop văn phòng và laptop đồ họa từ VinaTech.',
    keywords:
      'tin tức công nghệ, tư vấn laptop, laptop gaming, laptop văn phòng, laptop đồ họa, VinaTech',
    url: '/news',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'CollectionPage',
      name: 'Tin tức công nghệ VinaTech',
      description:
        'Cập nhật tin tức công nghệ, kinh nghiệm chọn laptop, đánh giá laptop gaming, laptop văn phòng và laptop đồ họa từ VinaTech.',
      url: absoluteUrl('/news'),
      isPartOf: {
        '@type': 'WebSite',
        name: 'VinaTech',
        url: absoluteUrl('/'),
      },
    },
  })
}

const imageUrl = (path) => {
  if (!path) return placeholderImage
  if (path.startsWith('http')) return path
  return `http://127.0.0.1:8000/storage/${path}`
}

const formatDate = (value) => {
  if (!value) return ''
  return new Date(value).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const fetchNews = async (page = 1) => {
  if (posts.value.length === 0) {
    loading.value = true
  }
  errorMessage.value = ''

  try {
    const params = { scope: 'public', per_page: 9, page }
    if (selectedCategory.value !== 'Mới nhất') params.category = selectedCategory.value

    const { data } = await api.get('/news', { params })
    posts.value = data.data || []
    currentPage.value = data.current_page || 1
    lastPage.value = data.last_page || 1
  } catch (error) {
    console.error('Lỗi tải tin tức:', error)
    posts.value = []
    currentPage.value = 1
    lastPage.value = 1
    errorMessage.value = 'Không thể tải tin tức. Vui lòng thử lại sau.'
  } finally {
    loading.value = false
  }
}

const fetchPopular = async () => {
  try {
    const { data } = await api.get('/news', {
      params: { scope: 'public', per_page: 20 },
    })
    popularPosts.value = (data.data || [])
      .sort((a, b) => (b.views || 0) - (a.views || 0))
      .slice(0, 3)
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
    const cached = localStorage.getItem('nextgen_news_cache')
    if (cached) {
      const parsed = JSON.parse(cached)
      if (parsed.posts) posts.value = parsed.posts
      if (parsed.popularPosts) popularPosts.value = parsed.popularPosts
    }
  } catch (e) {
    console.error('Lỗi load cache tin tức:', e)
  }
}

const saveCache = () => {
  try {
    localStorage.setItem('nextgen_news_cache', JSON.stringify({
      posts: posts.value,
      popularPosts: popularPosts.value
    }))
  } catch (e) {
    console.error('Lỗi save cache tin tức:', e)
  }
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
    <div class="top">
      <span class="badge">TẠP CHÍ CÔNG NGHỆ</span>
      <h1>Tin tức <span>công nghệ.</span></h1>

      <div class="tabs">
        <button
          v-for="tab in tabs"
          :key="tab"
          :class="{ active: selectedCategory === tab }"
          @click="selectTab(tab)"
        >
          {{ tab }}
        </button>
      </div>
    </div>

    <div class="container">
      <div class="left">
        <div v-if="loading" class="empty">Đang tải tin tức...</div>
        <div v-else-if="errorMessage" class="empty">{{ errorMessage }}</div>

        <div v-else class="grid">
          <article v-for="post in posts" :key="post.id" class="card">
            <img :src="imageUrl(post.image)" :alt="post.image_alt || post.title" />
            <span class="category">{{ post.category }}</span>
            <small>{{ formatDate(post.published_at || post.created_at) }}</small>
            <h3>{{ post.title }}</h3>
            <p v-if="post.excerpt">{{ post.excerpt }}</p>
            <RouterLink :to="`/news/${post.id}`">XEM THÊM →</RouterLink>
          </article>

          <div v-if="posts.length === 0" class="empty">Chưa có bài viết nào.</div>
        </div>

        <div class="pagination" v-if="lastPage > 1">
          <button :disabled="currentPage <= 1" @click="fetchNews(currentPage - 1)">‹</button>
          <button class="active">{{ currentPage }}</button>
          <button :disabled="currentPage >= lastPage" @click="fetchNews(currentPage + 1)">›</button>
        </div>
      </div>

      <aside class="right">
        <div class="box">
          <h4>• Bài viết phổ biến</h4>
          <div class="popular">
            <RouterLink
              v-for="item in popularPosts"
              :key="item.id"
              :to="`/news/${item.id}`"
              class="item"
            >
              <img :src="imageUrl(item.image)" :alt="item.image_alt || item.title" />
              <div>
                <p>{{ item.title }}</p>
                <span>{{ item.views || 0 }} lượt xem</span>
              </div>
            </RouterLink>
            <p v-if="popularPosts.length === 0" class="muted">Chưa có dữ liệu.</p>
          </div>
        </div>

        <div class="box">
          <h4>• Chuyên mục</h4>
          <div class="tags">
            <button v-for="tag in categories" :key="tag" @click="selectTab(tag)">
              {{ tag }}
            </button>
          </div>
        </div>

        <div class="cta">
          <h3>Đăng ký nhận tin</h3>
          <p>Cập nhật công nghệ mới nhất từ VinaTech</p>
          <input placeholder="Email của bạn" />
          <button>THAM GIA NGAY</button>
        </div>
      </aside>
    </div>
  </section>
</template>

<style scoped>
.news-page {
  background: #f5f7fb;
  padding: 40px 80px;
}

.top {
  margin-bottom: 30px;
}

.badge {
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
}

.top h1 {
  color: #0f172a;
  font-size: 36px;
  font-weight: 800;
}

.top h1 span {
  color: #4f46e5;
}

.tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-top: 10px;
}

.tabs button,
.tags button {
  background: transparent;
  border: 0;
  cursor: pointer;
  font: inherit;
}

.tabs button {
  color: #334155;
  font-size: 13px;
  padding: 0;
}

.tabs .active {
  color: #2563eb;
  font-weight: 700;
}

.container {
  display: grid;
  gap: 40px;
  grid-template-columns: 3fr 1fr;
}

.grid {
  display: grid;
  gap: 20px;
  grid-template-columns: repeat(3, 1fr);
}

.card {
  background: white;
  border-radius: 16px;
  padding: 15px;
  transition: transform 0.3s;
}

.card:hover {
  transform: translateY(-5px);
}

.card img {
  border-radius: 12px;
  height: 140px;
  object-fit: cover;
  width: 100%;
}

.category {
  color: #2563eb;
  display: block;
  font-size: 10px;
  font-weight: 700;
  margin-top: 8px;
}

.card small {
  color: #64748b;
}

.card h3 {
  color: #0f172a;
  font-size: 14px;
  margin: 8px 0;
}

.card p {
  color: #64748b;
  display: -webkit-box;
  font-size: 13px;
  line-height: 1.5;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.card a,
.item {
  color: inherit;
  text-decoration: none;
}

.card a {
  color: #2563eb;
  font-size: 12px;
  font-weight: 700;
}

.empty {
  background: white;
  border-radius: 12px;
  color: #64748b;
  grid-column: 1 / -1;
  padding: 24px;
}

.pagination {
  margin-top: 30px;
}

.pagination button {
  background: #e2e8f0;
  border: none;
  cursor: pointer;
  margin-right: 6px;
  padding: 6px 10px;
}

.pagination button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.pagination .active {
  background: #2563eb;
  color: white;
}

.box {
  background: white;
  border-radius: 12px;
  margin-bottom: 20px;
  padding: 20px;
}

.popular .item {
  display: flex;
  gap: 10px;
  margin-bottom: 12px;
}

.popular img {
  border-radius: 50%;
  height: 42px;
  object-fit: cover;
  width: 42px;
}

.popular p {
  color: #0f172a;
  margin: 0 0 4px;
}

.popular span,
.muted {
  color: #64748b;
  font-size: 12px;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tags button {
  background: #e2e8f0;
  border-radius: 6px;
  color: #334155;
  font-size: 12px;
  padding: 6px 10px;
}

.cta {
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  border-radius: 16px;
  color: white;
  padding: 20px;
}

.cta input {
  border: none;
  border-radius: 6px;
  margin: 10px 0;
  padding: 8px;
  width: 95%;
}

.cta button {
  background: white;
  border: none;
  border-radius: 6px;
  color: #2563eb;
  padding: 10px;
  width: 100%;
}

/* RESPONSIVE */
@media (max-width: 1024px) {
  .news-page {
    padding: 30px 24px;
  }
  .container {
    grid-template-columns: 1fr;
    gap: 30px;
  }
  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .news-page {
    padding: 20px 16px;
  }
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
