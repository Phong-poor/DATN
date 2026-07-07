export const mapNewsPost = (post = {}) => ({
  ...post,
  id: post.id,
  title: post.title || post.tieude || '',
  slug: post.slug || '',
  category: post.category || post.danhmuc || 'Công nghệ',
  author: post.author || post.author_name || post.tacgia || 'NextGen',
  author_name: post.author_name || post.tacgia || post.author || 'NextGen',
  image: post.image || post.thumbnail || post.hinhanh || '',
  image_alt: post.image_alt || post.mota_hinhanh || post.tieude || post.title || 'Ảnh bài viết',
  excerpt: post.excerpt || post.summary || post.tomtat || '',
  content: post.content || post.noidung || '',
  published_at: post.published_at || post.dang_luc || post.created_at || '',
  views: Number(post.views ?? post.luotxem ?? 0),
})

export const mapNewsPosts = (posts = []) => (
  Array.isArray(posts) ? posts.map(mapNewsPost) : []
)
