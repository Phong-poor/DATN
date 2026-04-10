<template>
  <Header />
  <div class="page">
    <div class="container">

      <div class="section-header">
        <h2 class="section-title">Sản phẩm yêu thích</h2>
        <button class="share-btn" @click="shareList">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <circle cx="18" cy="5" r="3" />
            <circle cx="6" cy="12" r="3" />
            <circle cx="18" cy="19" r="3" />
            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
          </svg>
          Chia sẻ danh sách
        </button>
      </div>
      <p class="section-sub">
        Hiện có <span>{{ wishlist.length }} sản phẩm</span> trong danh sách của bạn
      </p>

      <div class="wishlist-grid">
        <div v-if="isLoading" class="loading-text">Đang tải danh sách...</div>
        <div v-else-if="wishlist.length === 0" class="empty-text">Chưa có sản phẩm nào trong danh sách yêu thích.</div>

        <transition-group v-else name="card" tag="div" class="wishlist-inner">
          <div v-for="item in wishlist" :key="item.id" class="product-card">
            <div class="card-img-wrap">
              <span class="badge" :class="item.bienthe?.soluong > 0 ? 'in-stock' : 'out-stock'">
                {{ item.bienthe?.soluong > 0 ? 'Còn hàng' : 'Hết hàng' }}
              </span>
              <button class="delete-btn" title="Xoá" @click="removeItem(item.id)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6" />
                  <path d="M19 6l-1 14H6L5 6" />
                  <path d="M10 11v6" />
                  <path d="M14 11v6" />
                  <path d="M9 6V4h6v2" />
                </svg>
              </button>
              <img :src="getImage(item)" :alt="item.bienthe?.sanpham?.tenSP" @error="onImgError" />
            </div>

            <div class="card-body">
              <div class="card-name">{{ item.bienthe?.sanpham?.tenSP || 'Sản phẩm' }}</div>
              <div class="card-variant-name">
                <span class="variant-label">Phân loại:</span>

                <div v-if="item.bienthe?.ten_bienthe" class="variant-list">
                  <span class="variant-item" v-for="(val, index) in item.bienthe.ten_bienthe.split('-')" :key="index">
                    {{ val.trim() }}
                  </span>
                </div>

                <span v-else class="variant-item">Mặc định</span>
              </div>

              <div class="card-price" :class="{ out: item.bienthe?.soluong === 0 }">
                {{ formatPrice(item.bienthe?.gia) }}
              </div>

              <div class="qty-control">
                <span class="qty-label">SL:</span>
                <div class="qty-actions">
                  <button @click="updateQuantity(item, -1)" :disabled="item.soluong <= 1">-</button>
                  <span>{{ item.soluong }}</span>
                  <button @click="updateQuantity(item, 1)">+</button>
                </div>
              </div>

              <button v-if="item.bienthe?.soluong > 0" class="add-cart-btn" @click="moveToCart(item)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                  <circle cx="9" cy="21" r="1" />
                  <circle cx="20" cy="21" r="1" />
                  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                </svg>
                Thêm vào giỏ hàng
              </button>
              <button v-else class="notify-btn" @click="notifyMe(item)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                  <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                </svg>
                Thông báo khi có hàng
              </button>
            </div>
          </div>
        </transition-group>
      </div>

      <div class="suggest-header">
        <h3 class="suggest-title">Gợi ý cho bạn</h3>
        <div class="nav-btns">
          <button class="nav-btn" @click="slideSuggest(-1)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="15 18 9 12 15 6" />
            </svg>
          </button>
          <button class="nav-btn" @click="slideSuggest(1)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="9 18 15 12 9 6" />
            </svg>
          </button>
        </div>
      </div>

      <div class="suggest-grid">
        <div v-for="item in visibleSuggestions" :key="item.id" class="suggest-card">
          <div class="suggest-img">
            <img :src="item.image" :alt="item.name" @error="onImgError" />
          </div>
          <div class="suggest-body">
            <div class="suggest-name">{{ item.name }}</div>
            <div class="suggest-price">{{ formatPrice(item.price) }}</div>
          </div>
        </div>
      </div>

    </div>
  </div>
  <Footer />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import Header from '../Layout/Header.vue'
import Footer from '../Layout/Footer.vue'

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
    wishlist.value = res.data.data || res.data
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
    alert(err.response?.data?.message || 'Không thể cập nhật số lượng!')
  }
}

const removeItem = async (id) => {
  try {
    await api.delete(`/yeu-thich/xoa/${id}`)
    wishlist.value = wishlist.value.filter(item => item.id !== id)
    window.dispatchEvent(new Event('wishlist-updated'))
  } catch (err) {
    alert('Lỗi khi xoá sản phẩm!')
  }
}

const moveToCart = async (item) => {
  try {
    // 1. Gửi request thêm vào giỏ hàng
    await api.post('/gio-hang/them', {
      id_bienthe: item.id_bienthe,
      soluong: item.soluong
    })


    await removeItem(item.id)

    alert('Đã chuyển sản phẩm sang giỏ hàng thành công!')
    window.dispatchEvent(new Event('cart-updated')) // Cập nhật số đếm giỏ hàng nếu có
  } catch (err) {
    alert(err.response?.data?.message || 'Lỗi khi chuyển sang giỏ hàng!')
  }
}

const formatPrice = (value) => {
  if (!value) return '0₫'
  return parseInt(value).toLocaleString('vi-VN') + '₫'
}

const getImage = (item) => {
  // Ưu tiên ảnh của biến thể, nếu không có thì lấy ảnh mặc định của sản phẩm
  const imgPath = item.bienthe?.hinhanh || item.bienthe?.sanpham?.hinhanh
  return imgPath ? `http://127.0.0.1:8000/storage/${imgPath}` : ''
}

const slideSuggest = (dir) => {
  const total = suggestions.value.length
  suggestOffset.value = (suggestOffset.value + dir + total) % total
}

const notifyMe = (item) => {
  alert('Hệ thống sẽ gửi thông báo khi sản phẩm có hàng lại!')
}

const shareList = () => {
  if (navigator.share) {
    navigator.share({ title: 'Danh sách yêu thích', url: window.location.href })
  } else {
    navigator.clipboard?.writeText(window.location.href)
    alert('Đã sao chép link!')
  }
}

const onImgError = (e) => {
  e.target.style.display = 'none'
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap');

*,
*::before,
*::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.page {
  font-family: 'Be Vietnam Pro', sans-serif;
  background: #f0f2f5;
  min-height: 100vh;
  padding: 32px 16px 48px;
  color: #1a1a2e;
}

.container {
  max-width: 780px;
  margin: 0 auto;
}

/* Header */
.section-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 6px;
}

.section-title {
  font-size: 22px;
  font-weight: 700;
}

.share-btn {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 13px;
  font-weight: 600;
  color: #5b5ef4;
  background: none;
  border: none;
  cursor: pointer;
  transition: opacity .18s;
  font-family: inherit;
}

.share-btn:hover {
  opacity: .7;
}

.share-btn svg {
  width: 15px;
  height: 15px;
}

.section-sub {
  font-size: 13px;
  color: #888;
  margin-bottom: 20px;
}

.section-sub span {
  color: #5b5ef4;
  font-weight: 600;
}

.loading-text,
.empty-text {
  font-size: 14px;
  color: #666;
  text-align: center;
  padding: 40px 0;
}

/* Wishlist */
.wishlist-grid {
  margin-bottom: 40px;
  min-height: 200px;
}

.wishlist-inner {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

@media (max-width: 600px) {
  .wishlist-inner {
    grid-template-columns: 1fr 1fr;
  }

  .suggest-grid {
    grid-template-columns: 1fr 1fr !important;
  }
}

.product-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 16px rgba(91, 94, 244, .07);
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 28px rgba(91, 94, 244, .13);
}

.card-img-wrap {
  position: relative;
  background: #f6f7fb;
  height: 130px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.card-img-wrap img {
  max-height: 100px;
  max-width: 90%;
  object-fit: contain;
  transition: transform .3s;
}

.product-card:hover .card-img-wrap img {
  transform: scale(1.06);
}

.badge {
  position: absolute;
  top: 10px;
  left: 10px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .6px;
  text-transform: uppercase;
  padding: 3px 8px;
  border-radius: 20px;
  color: #fff;
}

.badge.in-stock {
  background: #22c55e;
}

.badge.out-stock {
  background: #ef4444;
}

.delete-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: none;
  background: rgba(255, 255, 255, .85);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #bbb;
  transition: background .18s, color .18s;
}

.delete-btn:hover {
  background: #fee2e2;
  color: #ef4444;
}

.delete-btn svg {
  width: 14px;
  height: 14px;
}

.card-body {
  padding: 12px 14px 14px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.card-name {
  font-size: 13.5px;
  font-weight: 600;
  line-height: 1.4;
  margin-bottom: 2px;
}

.card-variant-name {
  font-size: 11.5px;
  color: #6b7280;
  margin-bottom: 6px;
}

.card-price {
  font-size: 16px;
  font-weight: 700;
  color: #5b5ef4;
  margin-bottom: 12px;
}

.card-price.out {
  color: #aaa;
  text-decoration: line-through;
  font-weight: 500;
  font-size: 14px;
}

/* Control Số Lượng */
.qty-control {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  margin-top: auto;
}

.qty-label {
  font-size: 12.5px;
  font-weight: 500;
  color: #555;
}

.qty-actions {
  display: flex;
  align-items: center;
  border: 1px solid #e8eaf0;
  border-radius: 6px;
  overflow: hidden;
}

.qty-actions button {
  background: #f6f7fb;
  border: none;
  padding: 2px 8px;
  cursor: pointer;
  font-weight: 600;
  color: #555;
}

.qty-actions button:hover:not(:disabled) {
  background: #e2e5f1;
}

.qty-actions button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.qty-actions span {
  font-size: 13px;
  padding: 0 10px;
  font-weight: 500;
}

.add-cart-btn {
  width: 100%;
  padding: 9px 0;
  border-radius: 24px;
  border: none;
  background: #5b5ef4;
  color: #fff;
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: background .18s, transform .12s;
}

.add-cart-btn svg {
  width: 14px;
  height: 14px;
}

.add-cart-btn:hover {
  background: #4547d4;
  transform: scale(1.02);
}

.notify-btn {
  width: 100%;
  padding: 9px 0;
  border-radius: 24px;
  border: 1.5px solid #e8eaf0;
  background: #f6f7fb;
  color: #888;
  font-family: inherit;
  font-size: 12.5px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: border-color .18s, background .18s, color .18s;
}

.notify-btn svg {
  width: 13px;
  height: 13px;
}

.notify-btn:hover {
  border-color: #5b5ef4;
  background: #eef0ff;
  color: #5b5ef4;
}

/* Suggestions */
.suggest-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.suggest-title {
  font-size: 18px;
  font-weight: 700;
}

.nav-btns {
  display: flex;
  gap: 8px;
}

.nav-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1.5px solid #e8eaf0;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1a1a2e;
  transition: background .18s, border-color .18s, color .18s;
}

.nav-btn:hover {
  background: #5b5ef4;
  border-color: #5b5ef4;
  color: #fff;
}

.nav-btn svg {
  width: 14px;
  height: 14px;
}

.suggest-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

.suggest-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 16px rgba(91, 94, 244, .07);
  overflow: hidden;
  cursor: pointer;
  transition: transform .2s, box-shadow .2s;
}

.suggest-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(91, 94, 244, .13);
}

.suggest-img {
  height: 100px;
  background: #f6f7fb;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.suggest-img img {
  max-height: 80px;
  max-width: 85%;
  object-fit: contain;
  transition: transform .3s;
}

.suggest-card:hover .suggest-img img {
  transform: scale(1.07);
}

.suggest-body {
  padding: 10px 12px 12px;
}

.suggest-name {
  font-size: 12.5px;
  font-weight: 600;
  margin-bottom: 5px;
  line-height: 1.35;
}

.suggest-price {
  font-size: 14px;
  font-weight: 700;
  color: #5b5ef4;
}

/* Transition animations */
.card-enter-active {
  transition: all .35s ease;
}

.card-leave-active {
  transition: all .25s ease;
}

.card-enter-from {
  opacity: 0;
  transform: translateY(16px) scale(.95);
}

.card-leave-to {
  opacity: 0;
  transform: scale(.9);
}

.card-variant-name { 
  margin-bottom: 10px;
  display: flex; 
  align-items: center; 
  gap: 6px; 
  flex-wrap: wrap; 
}

</style>