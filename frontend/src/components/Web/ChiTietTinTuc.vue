<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import api from '@/services/api'
import { absoluteUrl, setSeo, truncateText } from '@/services/seo'
import { storageUrl } from '@/services/urls'

const route = useRoute()
const post = ref(null)
const relatedPosts = ref([])
const loading = ref(true)
const errorMessage = ref('')
const sharing = ref(false)

const placeholderImage = 'https://placehold.co/1200x650?text=Tin+tuc'

const imageUrl = (path) => {
  if (!path) return placeholderImage
  if (path.startsWith('http')) return path
  return storageUrl(path)
}

const formatDate = (value) => {
  if (!value) return ''
  return new Date(value).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const parseInlineLinks = (text = '') => {
  const segments = []
  const linkPattern = /\[([^\]]+)]\(([^)]+)\)/g
  let lastIndex = 0
  let match = linkPattern.exec(text)

  while (match) {
    if (match.index > lastIndex) {
      segments.push({ type: 'text', text: text.slice(lastIndex, match.index) })
    }

    const href = match[2].trim()
    segments.push({
      type: 'link',
      text: match[1].trim(),
      href,
      external: /^https?:\/\//i.test(href),
    })

    lastIndex = match.index + match[0].length
    match = linkPattern.exec(text)
  }

  if (lastIndex < text.length) {
    segments.push({ type: 'text', text: text.slice(lastIndex) })
  }

  return segments.length ? segments : [{ type: 'text', text }]
}

const parseArticleContent = (content = '') => content
  .split(/\n+/)
  .map((paragraph) => paragraph.trim())
  .filter(Boolean)
  .map((paragraph) => {
    const imageMatch = paragraph.match(/^!\[([^\]]+)]\(([^)]+)\)$/)
    if (imageMatch) {
      return {
        type: 'image',
        alt: imageMatch[1].trim(),
        src: imageMatch[2].trim(),
      }
    }

    if (paragraph.startsWith('### ')) return { type: 'h3', text: paragraph.replace(/^###\s+/, '') }
    if (paragraph.startsWith('## ')) return { type: 'h2', text: paragraph.replace(/^##\s+/, '') }
    return { type: 'p', segments: parseInlineLinks(paragraph) }
  })

const articleBlocks = computed(() => {
  return parseArticleContent(post.value?.noidung || '')
})

const articleImages = computed(() => articleBlocks.value.filter((block) => block.type === 'image'))
const heroAlt = computed(() => post.value?.mota_hinhanh || post.value?.tieude || 'Ảnh minh họa bài viết VinaTech')

const compactTitle = (title = '') => {
  const cleanTitle = title.trim()
  const titleBeforeColon = cleanTitle.split(':')[0]
  const candidate = titleBeforeColon.length >= 20 ? titleBeforeColon : cleanTitle
  return truncateText(candidate, 58)
}

const articleDescription = (article) => {
  const base = article.tomtat || truncateText(article.noidung || article.tieude, 180)
  if (base.length >= 160) return truncateText(base, 240)

  return truncateText(
    `${base} Xem phân tích chi tiết, tiêu chí lựa chọn, kinh nghiệm sử dụng và lời khuyên mua laptop phù hợp tại VinaTech.`,
    240,
  )
}

const seoSummary = computed(() => {
  if (!post.value) return []

  return [
    `Bài viết "${post.value.tieude}" thuộc chuyên mục ${post.value.danhmuc}, tập trung vào nhu cầu tìm hiểu công nghệ và lựa chọn laptop thực tế.`,
    'Nội dung được biên tập để giúp người đọc nắm nhanh tiêu chí quan trọng, tránh mua sai cấu hình và hiểu rõ yếu tố ảnh hưởng đến trải nghiệm sử dụng.',
    'Bạn có thể dùng các gợi ý trong bài để so sánh sản phẩm, chuẩn bị ngân sách và chọn thiết bị phù hợp hơn cho học tập, làm việc hoặc giải trí.',
  ]
})

const applyArticleSeo = (article) => {
  const description = article.seo_description || articleDescription(article)
  const canonicalPath = article.canonical_url || `/tin-tuc/${article.slug || article.id}`
  const fullImageUrl = imageUrl(article.hinhanh)
  const contentImages = parseArticleContent(article.noidung)
    .filter((block) => block.type === 'image')
    .map((block) => imageUrl(block.src))

  setSeo({
    title: article.seo_title || compactTitle(article.tieude),
    description,
    keywords: `${article.danhmuc}, tin tức công nghệ, tư vấn laptop, laptop VinaTech`,
    image: fullImageUrl,
    url: canonicalPath,
    type: 'article',
    publishedTime: article.dang_luc,
    modifiedTime: article.updated_at,
    author: article.tacgia || 'VinaTech',
    schema: {
      '@context': 'https://schema.org',
      '@type': 'Article',
      headline: article.tieude,
      description,
      image: [fullImageUrl, ...contentImages],
      datePublished: article.dang_luc || article.created_at,
      dateModified: article.updated_at || article.dang_luc || article.created_at,
      author: {
        '@type': 'Person',
        name: article.tacgia || 'VinaTech',
      },
      publisher: {
        '@type': 'Organization',
        name: 'VinaTech',
        logo: {
          '@type': 'ImageObject',
          url: absoluteUrl('/favicon.svg'),
        },
      },
      mainEntityOfPage: {
        '@type': 'WebPage',
        '@id': absoluteUrl(canonicalPath),
      },
    },
  })
}

const fetchRelated = async (currentPost) => {
  if (!currentPost?.danhmuc) {
    relatedPosts.value = []
    return
  }

  try {
    const { data } = await api.get('/news', {
      params: {
        scope: 'public',
        per_page: 4,
        danhmuc: currentPost.danhmuc,
      },
    })

    relatedPosts.value = (data.data || [])
      .filter((item) => item.id !== currentPost.id)
      .slice(0, 3)
  } catch (error) {
    console.error('Lỗi tải bài viết liên quan:', error)
    relatedPosts.value = []
  }
}

const loadCache = (id) => {
  try {
    const cached = localStorage.getItem(`nextgen_news_detail_cache_${id}`)
    if (cached) {
      const parsed = JSON.parse(cached)
      if (parsed.post) post.value = parsed.post
      if (parsed.relatedPosts) relatedPosts.value = parsed.relatedPosts
      return true
    }
  } catch (e) {
    console.error('Lỗi load cache chi tiết tin tức:', e)
  }
  return false
}

const saveCache = (id) => {
  try {
    localStorage.setItem(`nextgen_news_detail_cache_${id}`, JSON.stringify({
      post: post.value,
      relatedPosts: relatedPosts.value
    }))
  } catch (e) {
    console.error('Lỗi save cache chi tiết tin tức:', e)
  }
}

const fetchPost = async () => {
  const articleId = route.params.id
  if (!articleId) {
    post.value = null
    relatedPosts.value = []
    errorMessage.value = 'Không tìm thấy bài viết hoặc bài viết chưa được xuất bản.'
    loading.value = false
    return
  }

  const hasCache = loadCache(articleId)
  errorMessage.value = ''
  if (hasCache) {
    loading.value = false
  } else {
    loading.value = true
  }

  try {
    const { data } = await api.get(`/news/${articleId}`, { skipGlobalLoader: true })
    post.value = data
    relatedPosts.value = data.related || []
    errorMessage.value = ''
    applyArticleSeo(data)
    
    // Tải bài viết liên quan
    if (!relatedPosts.value.length && data?.danhmuc) {
      await fetchRelated(data)
    }
    api.post(`/news/${data.id}/track`, { event: 'read' }, { skipGlobalLoader: true }).catch(() => {})
    
    saveCache(articleId)
    loading.value = false
  } catch (error) {
    console.error('Lỗi tải chi tiết tin tức:', error)
    if (!post.value) {
      post.value = null
      relatedPosts.value = []
      errorMessage.value = 'Không tìm thấy bài viết hoặc bài viết chưa được xuất bản.'
    } else {
      errorMessage.value = ''
    }
    loading.value = false
  }
}

const shareArticle = async () => {
  if (!post.value || sharing.value) return
  sharing.value = true
  const url = absoluteUrl(`/tin-tuc/${post.value.slug || post.value.id}`)
  try {
    if (navigator.share) await navigator.share({ title: post.value.tieude, text: post.value.tomtat, url })
    else await navigator.clipboard.writeText(url)
    await api.post(`/news/${post.value.id}/track`, { event: 'share' }, { skipGlobalLoader: true })
  } finally { sharing.value = false }
}

watch(() => route.params.id, () => {
  window.scrollTo(0, 0)
  fetchPost()
})

onMounted(() => {
  window.scrollTo(0, 0)
  fetchPost()
})
</script>

<template>
  <section class="detail-page">
    <div v-if="loading" class="state-box">Đang tải bài viết...</div>

    <div v-else-if="errorMessage" class="state-box">
      <p>{{ errorMessage }}</p>
      <RouterLink to="/tin-tuc">Quay lại tin tức</RouterLink>
    </div>

    <article v-else class="article-wrap h-entry hentry">
      <RouterLink to="/tin-tuc" class="back-link u-url">← Quay lại tin tức</RouterLink>

      <header class="article-head">
        <span class="category p-category">{{ post.danhmuc }}</span>
        <h1 class="p-name entry-title">{{ post.tieude }}</h1>
        <div class="meta">
          <span class="p-author h-card author">{{ post.tacgia || 'Admin' }}</span>
          <time class="dt-published published" :datetime="post.dang_luc || post.created_at">
            {{ formatDate(post.dang_luc || post.created_at) }}
          </time>
          <time v-if="post.updated_at" class="dt-updated updated" :datetime="post.updated_at">
            Cập nhật {{ formatDate(post.updated_at) }}
          </time>
          <span>{{ post.luotxem || 0 }} lượt xem</span>
        </div>
      </header>

      <div class="article-tags"><span v-for="tag in post.tags" :key="tag.id">#{{ tag.name }}</span><span>{{ post.reading_time || 1 }} phút đọc</span><button type="button" :disabled="sharing" @click="shareArticle">{{ sharing ? 'Đang chia sẻ...' : 'Chia sẻ bài viết' }}</button></div>
      <img class="hero-img u-photo" :src="imageUrl(post.hinhanh)" :alt="heroAlt" />

      <div class="article-body e-content entry-content">
        <p v-if="post.tomtat" class="lead p-summary">{{ post.tomtat }}</p>
        <template v-for="(block, index) in articleBlocks" :key="index">
          <h2 v-if="block.type === 'h2'">{{ block.text }}</h2>
          <h3 v-else-if="block.type === 'h3'">{{ block.text }}</h3>
          <figure v-else-if="block.type === 'image'" class="content-figure">
            <img :src="imageUrl(block.src)" :alt="block.alt" loading="lazy" decoding="async" />
            <figcaption>{{ block.alt }}</figcaption>
          </figure>
          <p v-else>
            <template v-for="(segment, segmentIndex) in block.segments" :key="segmentIndex">
              <a
                v-if="segment.type === 'link'"
                :href="segment.href"
                :target="segment.external ? '_blank' : null"
                :rel="segment.external ? 'noopener noreferrer' : null"
              >
                {{ segment.text }}
              </a>
              <span v-else>{{ segment.text }}</span>
            </template>
          </p>
        </template>
      </div>

      <section class="article-gallery" v-if="articleImages.length">
        <h2>Hình ảnh minh họa trong bài</h2>
        <div class="gallery-grid">
          <figure v-for="image in articleImages" :key="image.src">
            <img :src="imageUrl(image.src)" :alt="image.alt" loading="lazy" decoding="async" />
            <figcaption>{{ image.alt }}</figcaption>
          </figure>
        </div>
      </section>

      <section class="seo-summary">
        <h2>Tóm tắt nhanh</h2>
        <p v-for="item in seoSummary" :key="item">{{ item }}</p>
      </section>

      <aside v-if="relatedPosts.length" class="related">
        <h3>Bài viết liên quan</h3>
        <div class="related-grid">
          <RouterLink
            v-for="item in relatedPosts"
            :key="item.id"
            :to="`/tin-tuc/${item.id}`"
            class="related-card"
          >
            <img :src="imageUrl(item.hinhanh)" :alt="item.tieude" />
            <div>
              <span>{{ item.danhmuc }}</span>
              <h4>{{ item.tieude }}</h4>
            </div>
          </RouterLink>
        </div>
      </aside>
    </article>
  </section>
</template>

<style scoped>

.detail-page {
  background: #f5f7fb;
  min-height: 100vh;
  padding: 40px 80px;
}

.article-wrap,
.state-box {
  margin: 0 auto;
  max-width: 980px;
}

.state-box {
  background: var(--tn-surface);
  border: 1px solid var(--tn-border);
  border-radius: 12px;
  color: #334155;
  padding: 28px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
}

.state-box a,
.back-link {
  color: #2563eb;
  text-decoration: none;
}

.back-link {
  display: inline-block;
  font-size: 14px;
  margin-bottom: 24px;
}

.article-head {
  margin-bottom: 24px;
}

.category {
  color: #2563eb;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: capitalize;
}

.article-head h1 {
  color: #0f172a;
  font-size: 42px;
  line-height: 1.12;
  margin: 12px 0;
}

.meta {
  color: #64748b;
  display: flex;
  flex-wrap: wrap;
  font-size: 14px;
  gap: 12px;
}

.hero-img {
  aspect-ratio: 16 / 8;
  border-radius: 18px;
  object-fit: cover;
  width: 100%;
}
.article-tags{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 16px}.article-tags span{background:#e8f0ff;color:#1d4ed8;border-radius:999px;padding:6px 10px;font-size:12px}.article-tags button{margin-left:auto;border:0;border-radius:9px;background:#2563eb;color:#fff;padding:8px 12px;cursor:pointer}

.article-body {
  background: var(--tn-surface);
  border: 1px solid var(--tn-border);
  border-radius: 16px;
  color: #334155;
  font-size: 16px;
  line-height: 1.8;
  margin-top: 24px;
  padding: 32px;
  box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
}

.article-body .lead {
  color: #0f172a;
  font-size: 19px;
  font-weight: 600;
}

.article-body h2 {
  color: #0f172a;
  font-size: 26px;
  line-height: 1.35;
  margin: 30px 0 12px;
}

.article-body h3 {
  color: #1d4ed8;
  font-size: 20px;
  line-height: 1.4;
  margin: 24px 0 10px;
}

.article-body a {
  color: #2563eb;
  font-weight: 700;
  text-decoration: none;
  border-bottom: 1px solid rgba(37, 99, 235, 0.28);
}

.article-body a:hover {
  border-bottom-color: #2563eb;
}

.content-figure {
  margin: 28px 0;
}

.content-figure img {
  border-radius: 16px;
  display: block;
  max-height: 520px;
  object-fit: cover;
  width: 100%;
}

.content-figure figcaption,
.article-gallery figcaption {
  color: #64748b;
  font-size: 13px;
  line-height: 1.5;
  margin-top: 8px;
  text-align: center;
}

.article-gallery {
  background: var(--tn-surface);
  border: 1px solid var(--tn-border);
  border-radius: 16px;
  margin-top: 20px;
  padding: 24px;
  box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
}

.article-gallery h2 {
  color: #0f172a;
  font-size: 22px;
  margin: 0 0 16px;
}

.gallery-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(2, 1fr);
}

.gallery-grid figure {
  margin: 0;
}

.gallery-grid img {
  aspect-ratio: 16 / 10;
  border-radius: 14px;
  object-fit: cover;
  width: 100%;
}

.seo-summary {
  background: #eef6ff;
  border: 1px solid #dbeafe;
  border-radius: 16px;
  color: #334155;
  line-height: 1.75;
  margin-top: 20px;
  padding: 24px 30px;
}

.seo-summary h2 {
  color: #0f172a;
  font-size: 22px;
  margin: 0 0 10px;
}

.seo-summary p {
  margin: 8px 0;
}

.related {
  margin-top: 34px;
}

.related h3 {
  color: #0f172a;
  font-size: 22px;
}

.related-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(3, 1fr);
}

.related-card {
  background: var(--tn-surface);
  border: 1px solid var(--tn-border);
  border-radius: 14px;
  color: inherit;
  overflow: hidden;
  text-decoration: none;
}

.related-card img {
  height: 130px;
  object-fit: cover;
  width: 100%;
}

.related-card div {
  padding: 14px;
}

.related-card span {
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
}

.related-card h4 {
  color: #0f172a;
  font-size: 14px;
  margin: 6px 0 0;
}

@media (max-width: 768px) {
  .detail-page {
    padding: 24px 16px;
  }

  .article-head h1 {
    font-size: 30px;
  }

  .article-body {
    padding: 22px;
  }

  .related-grid {
    grid-template-columns: 1fr;
  }

  .gallery-grid {
    grid-template-columns: 1fr;
  }
}
</style>
