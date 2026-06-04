<template>
  <div class="wishlist-page">
    <div class="wishlist-container">

      <!-- ===== PAGE HEADER ===== -->
      <div class="page-header">
        <div class="header-left">
          <div class="header-label">
            <svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
              <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            </svg>
            Bộ sưu tập của bạn
          </div>
          <h1 class="page-title">Yêu Thích</h1>
        </div>
        <div class="header-actions">
          <button class="btn-secondary" @click="shareList">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" />
              <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" /><line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
            </svg>
            Chia sẻ
          </button>
          <button
            v-if="wishlist.length > 0"
            class="btn-primary"
            @click="wishlist.forEach(i => i.bienthe?.soluong > 0 && moveToCart(i))"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" />
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
            </svg>
            Thêm tất cả vào giỏ
          </button>
        </div>
      </div>

      <!-- ===== STATS SECTION ===== -->
      <div class="stats-row" v-if="!isLoading && wishlist.length > 0">
        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
              <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
            </svg>
          </div>
          <div class="stat-body">
            <div class="stat-val">{{ wishlist.length }}</div>
            <div class="stat-lbl">Sản phẩm đã lưu</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <div class="stat-body">
            <div class="stat-val">{{ wishlist.filter(i => i.bienthe?.soluong > 0).length }}</div>
            <div class="stat-lbl">Sản phẩm còn hàng</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
              <rect x="2" y="5" width="20" height="14" rx="2"/>
              <line x1="2" y1="10" x2="22" y2="10"/>
            </svg>
          </div>
          <div class="stat-body">
            <div class="stat-val">{{ formatPrice(wishlist.reduce((s, i) => s + (i.bienthe?.gia || 0) * (i.soluong || 1), 0)) }}</div>
            <div class="stat-lbl">Tổng giá trị</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div class="stat-body">
            <div class="stat-val">Hôm nay</div>
            <div class="stat-lbl">Cập nhật gần nhất</div>
          </div>
        </div>
      </div>

      <!-- ===== LOADING STATE ===== -->
      <div v-if="isLoading" class="grid-wrapper">
        <div v-for="n in 6" :key="n" class="skeleton-card">
          <div class="sk-img"></div>
          <div class="sk-body">
            <div class="sk-line long"></div>
            <div class="sk-line med"></div>
            <div class="sk-block"></div>
            <div class="sk-line short"></div>
            <div class="sk-btn"></div>
          </div>
        </div>
      </div>

      <!-- ===== EMPTY STATE ===== -->
      <div v-else-if="wishlist.length === 0" class="empty-state">
        <div class="empty-illustration">
          <div class="empty-circle">
            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M32 56s-24-14-24-28a12 12 0 0 1 24 0 12 12 0 0 1 24 0C56 42 32 56 32 56z" fill="url(#heartGrad)" />
              <defs>
                <linearGradient id="heartGrad" x1="8" y1="16" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#818cf8"/>
                  <stop offset="1" stop-color="#6366f1"/>
                </linearGradient>
              </defs>
            </svg>
          </div>
        </div>
        <h2 class="empty-title">Danh sách yêu thích trống</h2>
        <p class="empty-desc">Hãy khám phá cửa hàng và thêm những sản phẩm bạn yêu thích vào đây.</p>
        <a href="/products" class="btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
          </svg>
          Khám phá sản phẩm
        </a>
      </div>

      <!-- ===== PRODUCT GRID ===== -->
      <div v-else class="grid-wrapper">
        <transition-group name="card" tag="div" class="product-grid">
          <div v-for="item in wishlist" :key="item.id" class="product-card">

            <!-- RECENTLY ADDED RIBBON -->
            <div class="recently-added-tag">Đã lưu</div>

            <!-- IMAGE AREA -->
            <div class="card-image-wrap">
              <div class="stock-badge" :class="item.bienthe?.soluong > 0 ? 'in-stock' : 'out-stock'">
                {{ item.bienthe?.soluong > 0 ? '● Còn hàng' : '● Hết hàng' }}
              </div>
              <button class="btn-remove" title="Xoá khỏi yêu thích" @click="removeItem(item.id)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>
                </svg>
              </button>
              <div class="img-container">
                <img :src="getImage(item)" :alt="item.fullName" @error="onImgError" class="product-img" />
              </div>
            </div>

            <!-- CARD BODY -->
            <div class="card-body">
              <!-- BRAND + NAME -->
              <div class="brand-label">{{ item.brandName }}</div>
              <h3 class="product-name">{{ item.fullName || item.bienthe?.sanpham?.tenSP || 'Sản phẩm' }}</h3>

              <!-- SPECS GRID -->
              <div class="specs-grid" v-if="item.processedSpecs && item.processedSpecs.length > 0">
                <div class="spec-row" v-for="s in item.processedSpecs" :key="s.label">
                  <span class="spec-key">{{ s.label }}</span>
                  <span class="spec-val">{{ s.value }}</span>
                </div>
                <div class="spec-row" v-if="item.weight">
                  <span class="spec-key">Trọng lượng</span>
                  <span class="spec-val">{{ item.weight }} kg</span>
                </div>
              </div>

              <!-- PRICE SECTION -->
              <div class="price-section">
                <div class="price-current" :class="{ 'price-out': item.bienthe?.soluong === 0 }">
                  {{ formatPrice(item.bienthe?.gia) }}
                </div>
                <div class="price-installment" v-if="item.bienthe?.gia">
                  Góp từ {{ formatPrice(Math.round(item.bienthe.gia / 12)) }}/tháng
                </div>
              </div>

              <!-- QUANTITY CONTROL -->
              <div class="qty-row">
                <span class="qty-label">Số lượng</span>
                <div class="qty-stepper">
                  <button @click="updateQuantity(item, -1)" :disabled="item.soluong <= 1">−</button>
                  <span>{{ item.soluong }}</span>
                  <button @click="updateQuantity(item, 1)">+</button>
                </div>
              </div>

              <!-- ACTION BUTTONS -->
              <div class="card-actions">
                <button v-if="item.bienthe?.soluong > 0" class="btn-add-cart" @click="moveToCart(item)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                  </svg>
                  Thêm vào giỏ
                </button>
                <button v-else class="btn-notify" @click="notifyMe(item)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                  </svg>
                  Thông báo khi có hàng
                </button>
              </div>
            </div>

          </div>
        </transition-group>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'

// --- STATE ---
const wishlist = ref([])
const isLoading = ref(true)
const suggestOffset = ref(0)
const suggestPageSize = 4

// Dữ liệu gợi ý tĩnh
const suggestions = ref([
  { id: 101, name: 'Surface Laptop Studio 2', price: 56000000, image: 'https://cdn.tgdd.vn/Products/Images/44/290792/microsoft-surface-laptop-studio-2-i7-16gb-512gb-thumbnew-600x600.jpg' },
  { id: 102, name: 'Lenovo ThinkPad X1 Carbon', price: 34990000, image: 'https://cdn.tgdd.vn/Products/Images/44/295360/lenovo-thinkpad-x1-carbon-gen-11-i7-1365u-16gb-512gb-thumbnew-600x600.jpg' },
  { id: 103, name: 'iPad Pro M4 13-inch', price: 31490000, image: 'https://cdn.tgdd.vn/Products/Images/522/316703/apple-ipad-pro-m4-13-inch-wifi-256gb-thumbnew-600x600.jpg' },
  { id: 104, name: 'LG Gram 16" (2024)', price: 29800000, image: 'https://cdn.tgdd.vn/Products/Images/44/304651/lg-gram-16-2024-i7-155h-16gb-512gb-thumbnew-600x600.jpg' },
  { id: 105, name: 'HP Spectre x360 14', price: 41500000, image: 'https://cdn.tgdd.vn/Products/Images/44/289028/hp-spectre-x360-14-eu0055tu-i7-1355u-16gb-512gb-thumbnew-600x600.jpg' },
])

// --- COMPUTED ---
const visibleSuggestions = computed(() => {
  const total = suggestions.value.length
  if (total === 0) return []
  const start = suggestOffset.value % total
  const result = []
  for (let i = 0; i < suggestPageSize; i++) {
    result.push(suggestions.value[(start + i) % total])
  }
  return result
})


onMounted(() => {
  fetchWishlist()
})


const fetchWishlist = async () => {
  try {
    isLoading.value = true
    const res = await api.get('/yeu-thich')
    const rawData = res.data.data || res.data

    wishlist.value = rawData.map(item => {
      const p = item.bienthe?.sanpham || {}
      const bt = item.bienthe || {}

      let generalSpecs = []
      try {
        const tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || [])
        if (Array.isArray(tskt)) {
          generalSpecs = tskt.map(s => s.giatri).filter(Boolean)
        }
      } catch (e) { console.error('Lỗi parse thong_so_ky_thuat:', e) }

      const fullName = [p.tenSP, ...generalSpecs].join(' ')

      let ram = '', cpu = '', mausac = ''
      try {
        const tt = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || [])
        if (Array.isArray(tt)) {
          tt.forEach(attr => {
            const ten = (attr.ten_thuoctinh || '').toLowerCase()
            if (ten === 'ram') ram = attr.giatri
            else if (ten === 'cpu') cpu = attr.giatri
            else if (ten === 'màu sắc' || ten === 'màu') mausac = attr.giatri
          })
        }
      } catch (e) { console.error('Lỗi parse thuoc_tinh_json:', e) }

      const specs = [
        { label: 'RAM', value: ram },
        { label: 'CPU', value: cpu },
        { label: 'Màu', value: mausac }
      ].filter(s => s.value)

      return {
        ...item,
        fullName,
        processedSpecs: specs,
        brandName: p.thuong_hieu?.ten_thuonghieu || '',
        weight: p.khoiluong
      }
    })
  } catch (error) {
    console.error('Lỗi khi tải danh sách yêu thích:', error)
  } finally {
    isLoading.value = false
  }
}

const updateQuantity = async (item, change) => {
  const newQty = item.soluong + change
  if (newQty < 1) return

  try {
    await api.put(`/yeu-thich/cap-nhat/${item.id}`, { soluong: newQty })
    item.soluong = newQty
    window.dispatchEvent(new Event('wishlist-updated'))
  } catch (err) {
    swal.error('Lỗi', err.response?.data?.message || 'Không thể cập nhật số lượng!')
  }
}

const removeItem = async (id) => {
  try {
    await api.delete(`/yeu-thich/xoa/${id}`)
    wishlist.value = wishlist.value.filter(item => item.id !== id)
    window.dispatchEvent(new Event('wishlist-updated'))
  } catch (err) {
    swal.error('Lỗi', 'Lỗi khi xoá sản phẩm!')
  }
}

const moveToCart = async (item) => {
  try {
    await api.post('/gio-hang/them', {
      id_bienthe: item.id_bienthe,
      soluong: item.soluong
    })

    await removeItem(item.id)

    swal.success('Thành công', 'Đã chuyển sản phẩm sang giỏ hàng thành công!')
    window.dispatchEvent(new Event('cart-updated'))
  } catch (err) {
    swal.error('Lỗi', err.response?.data?.message || 'Lỗi khi chuyển sang giỏ hàng!')
  }
}

const formatPrice = (value) => {
  if (!value) return '0₫'
  return parseInt(value).toLocaleString('vi-VN') + '₫'
}

const getImage = (item) => {
  const imgPath = item.bienthe?.hinhanh || item.bienthe?.sanpham?.hinhanh
  return imgPath ? storageUrl(imgPath) : ''
}

const slideSuggest = (dir) => {
  const total = suggestions.value.length
  suggestOffset.value = (suggestOffset.value + dir + total) % total
}

const notifyMe = (item) => {
  swal.info('Thông báo', 'Hệ thống sẽ gửi thông báo khi sản phẩm có hàng lại!')
}

const fallbackCopyTextToClipboard = (text) => {
  const textArea = document.createElement('textarea')
  textArea.value = text
  textArea.style.position = 'fixed'
  textArea.style.top = '0'
  textArea.style.left = '0'
  textArea.style.opacity = '0'
  textArea.style.pointerEvents = 'none'
  document.body.appendChild(textArea)
  textArea.focus()
  textArea.select()
  try {
    const successful = document.execCommand('copy')
    document.body.removeChild(textArea)
    return successful
  } catch (err) {
    console.error('Fallback copy failed:', err)
    document.body.removeChild(textArea)
    return false
  }
}

const shareList = () => {
  if (navigator.share) {
    navigator.share({ title: 'Danh sách yêu thích', url: window.location.href })
  } else {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(window.location.href)
        .then(() => swal.toast('Đã sao chép link!'))
        .catch((err) => {
          console.warn('Clipboard writeText failed, trying fallback:', err)
          if (fallbackCopyTextToClipboard(window.location.href)) {
            swal.toast('Đã sao chép link!')
          } else {
            swal.error('Lỗi', 'Không thể copy link tự động')
          }
        })
    } else {
      if (fallbackCopyTextToClipboard(window.location.href)) {
        swal.toast('Đã sao chép link!')
      } else {
        swal.error('Lỗi', 'Không thể copy link tự động')
      }
    }
  }
}

const onImgError = (e) => {
  e.target.style.display = 'none'
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap');

*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

/* ===================== PAGE LAYOUT ===================== */
.wishlist-page {
  --primary: #6366f1;
  --primary-dark: #4f46e5;
  --primary-light: #eef2ff;
  --primary-glow: rgba(99, 102, 241, 0.18);
  --success: #22c55e;
  --danger: #ef4444;
  --text-1: #0f172a;
  --text-2: #475569;
  --text-3: #94a3b8;
  --border: #e2e8f0;
  --card-bg: #ffffff;
  --page-bg: #f8fafc;
  --font: 'Inter', 'Outfit', sans-serif;
  --radius-card: 24px;
  --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);

  font-family: var(--font);
  background: var(--page-bg);
  min-height: 100vh;
  padding: 40px 20px 80px;
  color: var(--text-1);
}

.wishlist-container {
  max-width: 1280px;
  margin: 0 auto;
}

/* ===================== PAGE HEADER ===================== */
.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 32px;
}

.header-label {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--primary);
  margin-bottom: 6px;
  font-family: 'Outfit', sans-serif;
}

.page-title {
  font-family: 'Outfit', sans-serif;
  font-size: 38px;
  font-weight: 800;
  letter-spacing: -1.5px;
  color: var(--text-1);
  line-height: 1.1;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

/* ===================== BUTTONS ===================== */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 11px 22px;
  border-radius: 14px;
  border: none;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: #fff;
  font-family: 'Outfit', sans-serif;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  box-shadow: 0 6px 18px var(--primary-glow);
  transition: var(--transition);
}
.btn-primary svg { width: 15px; height: 15px; }
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(99, 102, 241, 0.35);
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 11px 18px;
  border-radius: 14px;
  border: 1.5px solid var(--border);
  background: var(--card-bg);
  color: var(--text-2);
  font-family: 'Outfit', sans-serif;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
}
.btn-secondary svg { width: 15px; height: 15px; }
.btn-secondary:hover {
  border-color: var(--primary);
  color: var(--primary);
  background: var(--primary-light);
  transform: translateY(-1px);
}

/* ===================== STATS ROW ===================== */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 40px;
}

.stat-card {
  background: var(--card-bg);
  border-radius: 20px;
  padding: 20px;
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.03);
  transition: var(--transition);
}
.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.06);
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
  width: 22px;
  height: 22px;
}
.stat-icon.purple { background: rgba(139, 92, 246, 0.1); color: rgb(139, 92, 246); }
.stat-icon.green  { background: rgba(34, 197, 94, 0.1); color: rgb(34, 197, 94); }
.stat-icon.indigo { background: rgba(99, 102, 241, 0.1); color: rgb(99, 102, 241); }
.stat-icon.amber  { background: rgba(245, 158, 11, 0.1); color: rgb(245, 158, 11); }

.stat-val {
  font-family: 'Outfit', sans-serif;
  font-size: 18px;
  font-weight: 800;
  color: var(--text-1);
  line-height: 1.2;
}

.stat-lbl {
  font-size: 11px;
  color: var(--text-3);
  font-weight: 500;
  margin-top: 2px;
}

/* ===================== SKELETON LOADING ===================== */
.grid-wrapper {
  width: 100%;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.skeleton-card {
  background: var(--card-bg);
  border-radius: var(--radius-card);
  border: 1px solid var(--border);
  overflow: hidden;
}

.sk-img {
  height: 200px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e8edf5 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.sk-body { padding: 20px; display: flex; flex-direction: column; gap: 10px; }

.sk-line {
  height: 12px;
  border-radius: 6px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e8edf5 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}
.sk-line.long  { width: 90%; }
.sk-line.med   { width: 60%; }
.sk-line.short { width: 40%; }

.sk-block {
  height: 70px;
  border-radius: 10px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e8edf5 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.sk-btn {
  height: 40px;
  border-radius: 14px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e8edf5 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  margin-top: 6px;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ===================== EMPTY STATE ===================== */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 80px 24px;
}

.empty-illustration {
  margin-bottom: 24px;
}

.empty-circle {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 22px;
}
.empty-circle svg { width: 100%; height: 100%; }

.empty-title {
  font-family: 'Outfit', sans-serif;
  font-size: 24px;
  font-weight: 800;
  color: var(--text-1);
  margin-bottom: 10px;
  letter-spacing: -0.5px;
}

.empty-desc {
  font-size: 14px;
  color: var(--text-3);
  line-height: 1.6;
  max-width: 360px;
  margin: 0 auto 28px;
}

/* ===================== PRODUCT CARD ===================== */
.product-card {
  background: var(--card-bg);
  border-radius: var(--radius-card);
  border: 1px solid var(--border);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  position: relative;
  box-shadow: 0 4px 20px rgba(0,0,0,0.04);
  transition: var(--transition);
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 16px 40px rgba(99, 102, 241, 0.12);
  border-color: #c7d2fe;
}

/* Recently Added tag */
.recently-added-tag {
  position: absolute;
  top: 14px;
  left: -1px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: white;
  font-family: 'Outfit', sans-serif;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 1px;
  text-transform: uppercase;
  padding: 4px 10px 4px 11px;
  border-radius: 0 6px 6px 0;
  z-index: 3;
  box-shadow: 0 2px 8px var(--primary-glow);
}

/* IMAGE AREA */
.card-image-wrap {
  position: relative;
  background: linear-gradient(145deg, #f8fafc 0%, #eef2ff 100%);
  height: 150px;
  overflow: hidden;
}

.img-container {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px;
}

.product-img {
  max-height: 115px;
  max-width: 85%;
  object-fit: contain;
  transition: transform 0.45s cubic-bezier(0.165, 0.84, 0.44, 1);
  filter: drop-shadow(0 6px 12px rgba(0,0,0,0.08));
}
.product-card:hover .product-img {
  transform: scale(1.06);
}

.stock-badge {
  position: absolute;
  top: 14px;
  right: 14px;
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 0.5px;
  padding: 4px 10px;
  border-radius: 20px;
  color: #fff;
  z-index: 3;
}
.stock-badge.in-stock  { background: var(--success); box-shadow: 0 2px 8px rgba(34,197,94,0.35); }
.stock-badge.out-stock { background: var(--danger);  box-shadow: 0 2px 8px rgba(239,68,68,0.35); }

.btn-remove {
  position: absolute;
  bottom: 14px;
  right: 14px;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  border: 1.5px solid var(--border);
  background: rgba(255,255,255,0.95);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-3);
  transition: var(--transition);
  z-index: 3;
  backdrop-filter: blur(4px);
}
.btn-remove:hover {
  background: #fef2f2;
  border-color: var(--danger);
  color: var(--danger);
}
.btn-remove svg { width: 14px; height: 14px; }

/* CARD BODY */
.card-body {
  padding: 13px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.brand-label {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: var(--primary);
  margin-bottom: 4px;
}

.product-name {
  font-family: 'Outfit', sans-serif;
  font-size: 12.5px;
  font-weight: 700;
  color: var(--text-1);
  line-height: 1.35;
  margin-bottom: 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* SPECS GRID */
.specs-grid {
  background: #0d1b2e;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 8px 10px;
  margin-bottom: 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.spec-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 10.5px;
}
.spec-key {
  color: var(--text-3);
  font-weight: 500;
}
.spec-val {
  color: var(--text-2);
  font-weight: 700;
  text-align: right;
  max-width: 60%;
}

/* PRICE SECTION */
.price-section {
  margin-bottom: 10px;
  margin-top: auto;
}

.price-current {
  font-family: 'Outfit', sans-serif;
  font-size: 16px;
  font-weight: 800;
  color: var(--primary);
  letter-spacing: -0.3px;
  line-height: 1.2;
}
.price-current.price-out {
  color: var(--text-3);
  text-decoration: line-through;
  font-size: 13px;
  font-weight: 500;
}

.price-installment {
  font-size: 10px;
  color: var(--text-3);
  margin-top: 2px;
  font-weight: 500;
}

/* QUANTITY STEPPER */
.qty-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  padding: 7px 10px;
  background: #0d1b2e;
  border-radius: 10px;
  border: 1px solid var(--border);
}

.qty-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-2);
}

.qty-stepper {
  display: flex;
  align-items: center;
  gap: 2px;
}
.qty-stepper button {
  width: 22px;
  height: 22px;
  border-radius: 6px;
  border: 1.5px solid var(--border);
  background: #111f35;
  cursor: pointer;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-2);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
  line-height: 1;
}
.qty-stepper button:hover:not(:disabled) {
  border-color: var(--primary);
  color: var(--primary);
  background: var(--primary-light);
}
.qty-stepper button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}
.qty-stepper span {
  min-width: 24px;
  text-align: center;
  font-size: 12px;
  font-weight: 700;
  color: var(--text-1);
}

/* ACTION BUTTONS */
.card-actions {
  margin-top: 4px;
}

.btn-add-cart {
  width: 100%;
  padding: 9px;
  border-radius: 11px;
  border: none;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: #fff;
  font-family: 'Outfit', sans-serif;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  box-shadow: 0 3px 10px var(--primary-glow);
  transition: var(--transition);
}
.btn-add-cart svg { width: 13px; height: 13px; }
.btn-add-cart:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
}

.btn-notify {
  width: 100%;
  padding: 9px;
  border-radius: 11px;
  border: 1.5px solid var(--border);
  background: #0d1b2e;
  color: var(--text-3);
  font-family: 'Outfit', sans-serif;
  font-size: 11.5px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  transition: var(--transition);
}
.btn-notify svg { width: 12px; height: 12px; }
.btn-notify:hover {
  border-color: var(--primary);
  background: var(--primary-light);
  color: var(--primary);
}

/* ===================== TRANSITION ANIMATIONS ===================== */
.card-enter-active { transition: all 0.38s cubic-bezier(0.4, 0, 0.2, 1); }
.card-leave-active { transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1); }
.card-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.96);
}
.card-leave-to {
  opacity: 0;
  transform: scale(0.92);
}

/* ===================== RESPONSIVE ===================== */
@media (max-width: 1280px) {
  .product-grid { grid-template-columns: repeat(4, 1fr); gap: 14px; }
}

@media (max-width: 1024px) {
  .product-grid { grid-template-columns: repeat(3, 1fr); }
  .stats-row { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
  .wishlist-page { padding: 24px 14px 60px; }
  .page-title { font-size: 26px; }
  .page-header { align-items: flex-start; }
  .product-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .stats-row { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .stat-card { padding: 14px; }
  .stat-val { font-size: 14px; }
}

@media (max-width: 500px) {
  .product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .stats-row { grid-template-columns: 1fr 1fr; }
  .header-actions { width: 100%; }
  .btn-primary, .btn-secondary { flex: 1; justify-content: center; }
}
</style>
