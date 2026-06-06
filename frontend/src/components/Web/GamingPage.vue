<template>
  <main class="gaming-page">
    <!-- Breadcrumbs -->
    <nav class="gaming-breadcrumbs">
      <div class="gaming-container">
        <router-link to="/">Trang chủ</router-link>
        <span class="separator">&gt;</span>
        <span class="current">Gaming Laptop</span>
      </div>
    </nav>

    <!-- SECTION 1 - HERO GRID -->
    <section class="gaming-hero-section">
      <div class="gaming-container hero-grid">
        <!-- Sidebar trái (Danh mục Gaming Laptop) -->
        <aside class="hero-sidebar scroll-reveal">
          <div class="sidebar-header">
            <h3>Danh mục Gaming Laptop</h3>
          </div>
          <ul class="sidebar-list">
            <li 
              v-for="item in sidebarItems" 
              :key="item.value"
              :class="{ active: currentSidebarFilter === item.value }"
              @click="selectSidebarFilter(item.value)"
            >
              <component :is="item.icon" class="item-icon" />
              <span>{{ item.label }}</span>
            </li>
            
            <!-- Khuyến mãi hot -->
            <li class="sidebar-promo-item" @click="scrollToPromotions">
              <Flame class="promo-fire-icon" />
              <span>Khuyến mãi hot</span>
            </li>
          </ul>
        </aside>

        <!-- Cột phải: Banner + Hàng tiện ích dịch vụ -->
        <div class="hero-banner-column scroll-reveal stagger-1">
          <!-- Hero Banner phải (Slider sáng, hiện đại) -->
          <div class="hero-banner-slider">
            <div 
              class="slider-wrapper" 
              :style="{ transform: `translateX(-${activeSlideIndex * 100}%)` }"
            >
              <div 
                v-for="(slide, index) in heroSlides" 
                :key="index"
                class="hero-slide"
                :style="{ backgroundImage: `url(${slide.background})` }"
              >
                <div class="slide-content-left">
                  <span class="slide-badge">{{ slide.badge }}</span>
                  <h1 class="slide-title" v-html="slide.title"></h1>
                  <p class="slide-desc">{{ slide.desc }}</p>
                  <div class="slide-actions">
                    <button class="btn btn-primary-gaming" @click="goToProductsSection">{{ slide.primaryCTA }}</button>
                    <button class="btn btn-secondary-gaming" @click="scrollToPromotions">{{ slide.secondaryCTA }}</button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Navigation controls (Nút tròn trắng) -->
            <button class="slider-control prev" @click="prevSlide" aria-label="Slide trước">
              <ChevronLeft />
            </button>
            <button class="slider-control next" @click="nextSlide" aria-label="Slide tiếp theo">
              <ChevronRight />
            </button>
            
            <!-- Dots indicators -->
            <div class="slider-dots">
              <span 
                v-for="(slide, index) in heroSlides" 
                :key="index"
                class="dot"
                :class="{ active: activeSlideIndex === index }"
                @click="activeSlideIndex = index"
              ></span>
            </div>
          </div>

          <!-- Hàng 4 tiện ích dịch vụ nằm trực tiếp dưới banner -->
          <div class="benefits-grid">
            <div 
              v-for="(benefit, idx) in serviceBenefits" 
              :key="idx" 
              class="benefit-card scroll-reveal"
              :class="'stagger-' + (idx % 4)"
            >
              <div class="benefit-icon-wrapper">
                <component :is="benefit.icon" class="benefit-icon" />
              </div>
              <div class="benefit-info">
                <h4>{{ benefit.title }}</h4>
                <p>{{ benefit.desc }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 3 & 4 - SẢN PHẨM NỔI BẬT & GRID -->
    <section class="gaming-catalog-section" id="products-section">
      <div class="gaming-container">
        
        <!-- Header sản phẩm nổi bật có tab lọc và xem tất cả -->
        <div class="catalog-filter-bar">
          <div class="filter-header-left">
            <h2>Sản phẩm nổi bật</h2>
          </div>
          
          <div class="filter-tabs">
            <button 
              v-for="tab in filterTabs" 
              :key="tab.value"
              class="filter-tab-btn"
              :class="{ active: activeTabFilter === tab.value }"
              @click="selectTabFilter(tab.value)"
            >
              {{ tab.label }}
            </button>
          </div>
          
          <div class="filter-header-right">
            <button class="btn-view-all-link" @click="resetFilters">
              Xem tất cả <span class="arrow">&gt;</span>
            </button>
          </div>
        </div>

        <div class="gaming-products-scroll-panel">
        <!-- Product Grid / Loading / Empty State -->
        <div v-if="isLoading" class="gaming-loading-container">
          <div class="gaming-skeleton-card" v-for="n in 8" :key="n">
            <div class="skeleton-image"></div>
            <div class="skeleton-title"></div>
            <div class="skeleton-specs"></div>
            <div class="skeleton-price"></div>
          </div>
        </div>
        
        <div v-else-if="filteredProducts.length === 0" class="gaming-empty-container">
          <Search class="empty-icon" />
          <h3>Không tìm thấy sản phẩm</h3>
          <p>Thử điều chỉnh bộ lọc hoặc chọn danh mục khác</p>
          <button class="btn btn-primary-gaming" @click="resetFilters">Đặt lại bộ lọc</button>
        </div>

        <div v-else class="gaming-product-grid">
          <div 
            v-for="(prod, idx) in paginatedProducts" 
            :key="prod.id_sanpham" 
            class="gaming-product-card scroll-reveal"
            :class="'stagger-' + (idx % 5)"
            @click="viewDetail(prod.id_sanpham)"
          >
            <!-- Badge giảm giá góc trên trái -->
            <div class="product-badge" :class="getBadgeClass(prod)">
              {{ getBadgeText(prod) }}
            </div>
            
            <!-- Tim yêu thích góc trên phải -->
            <button 
              class="wishlist-heart-btn" 
              :class="{ active: isInWishlist(prod) }"
              @click.stop="toggleWishlistLocal(prod)"
              title="Yêu thích"
            >
              <Heart :fill="isInWishlist(prod) ? '#ef4444' : 'none'" />
            </button>
            
            <!-- Hình ảnh nền trắng sạch -->
            <div class="product-image-wrapper">
              <img :src="prod.image" :alt="prod.tenSP" loading="lazy" class="product-img" />
            </div>

            <!-- Tên sản phẩm -->
            <h3 class="product-name">{{ prod.tenSP }}</h3>

            <!-- Thông số nhanh (Specs pills bo tròn) -->
            <div class="product-specs-pills">
              <span 
                v-for="(spec, sIdx) in prod.specs" 
                :key="sIdx" 
                class="spec-pill"
              >
                {{ spec }}
              </span>
            </div>

            <!-- Đánh giá sao -->
            <div class="product-rating">
              <div class="stars">
                <span v-for="star in 5" :key="star" class="star" :class="{ filled: star <= Math.round(prod.rating) }">★</span>
              </div>
              <span class="reviews-count">({{ prod.reviews }})</span>
            </div>

            <!-- Giá cả và nút giỏ hàng góc dưới phải -->
            <div class="product-bottom-row">
              <div class="product-pricing">
                <div class="price-row">
                  <span class="price-new">{{ formatPrice(prod.gia) }}</span>
                </div>
                <div class="price-old-row" v-if="prod.oldPrice">
                  <span class="price-old">{{ formatPrice(prod.oldPrice) }}</span>
                </div>
                <div class="card-badge-row-1">
                  <span class="badge-chinh-hang">✓ Chính Hãng</span>
                </div>
                <div class="card-badge-row-2">
                  <span class="badge-ship-warranty">⚡ Freeship 2H</span>
                  <span class="badge-ship-warranty">✦ BH 24T</span>
                </div>
              </div>
              
              <!-- Nút giỏ hàng góc dưới phải -->
              <button 
                class="card-cart-btn" 
                @click.stop="addToCart(prod)" 
                title="Thêm vào giỏ hàng"
              >
                <ShoppingCart />
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container" v-if="filteredProducts.length > itemsPerPage">
          <button 
            class="page-btn" 
            :disabled="currentPage === 1" 
            @click="changePage(currentPage - 1)"
          >
            <ChevronLeft /> Trước
          </button>
          <span class="page-info">Trang {{ currentPage }} / {{ totalPages }}</span>
          <button 
            class="page-btn" 
            :disabled="currentPage === totalPages" 
            @click="changePage(currentPage + 1)"
          >
            Sau <ChevronRight />
          </button>
        </div>
        </div>

      </div>
    </section>

    <!-- SECTION 5 - KHUYẾN MÃI GAMING -->
    <section class="gaming-promotions-section" id="promotions-section">
      <div class="gaming-container">
        <div class="section-header-center scroll-reveal">
          <span class="accent-label">ƯU ĐÃI THÀNH VIÊN</span>
          <h2>Ưu Đãi Gaming Độc Quyền</h2>
        </div>
        <div class="promotions-grid">
          <div 
            v-for="(promo, pIdx) in promoCards" 
            :key="pIdx" 
            class="promo-card-custom scroll-reveal"
            :class="'stagger-' + (pIdx % 4)"
          >
            <div class="promo-icon-box">
              <component :is="promo.icon" class="promo-icon" />
            </div>
            <h3>{{ promo.title }}</h3>
            <p>{{ promo.desc }}</p>
            <span class="promo-code" v-if="promo.code">Code: <strong>{{ promo.code }}</strong></span>
            <button class="promo-btn" @click="claimPromo(promo)">Nhận ngay</button>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 6 - THƯƠNG HIỆU NỔI BẬT -->
    <section class="gaming-brands-section scroll-reveal">
      <div class="gaming-container">
        <div class="section-header-center scroll-reveal">
          <span class="accent-label">ĐỐI TÁC CHIẾN LƯỢC</span>
          <h2>Thương Hiệu Nổi Bật</h2>
        </div>
        <div class="brands-slider-container">
          <div class="brands-track">
            <div 
              v-for="(brand, bIdx) in brandLogos" 
              :key="bIdx" 
              class="brand-logo-item"
              @click="filterByBrandFromLogo(brand.filterVal)"
            >
              <component :is="brand.logoIcon" class="brand-logo-icon" />
              <span class="brand-logo-name">{{ brand.name }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 7 - SẢN PHẨM THEO THƯƠNG HIỆU -->
    <section class="gaming-brand-subgrids-section">
      <div class="gaming-container">
        
        <!-- ASUS Grid -->
        <div class="brand-subgrid-row accessory-slider-row" v-if="accessoryProducts.length > 0">
          <div class="subgrid-header accessory-slider-header">
            <div>
              <span class="accessory-eyebrow">Gaming gear</span>
              <h3>Phụ kiện gaming</h3>
            </div>
            <div class="accessory-slider-actions">
              <button class="accessory-slider-btn" @click="scrollAccessorySlider('prev')" aria-label="Phụ kiện trước">
                <ChevronLeft />
              </button>
              <button class="accessory-slider-btn" @click="scrollAccessorySlider('next')" aria-label="Phụ kiện tiếp theo">
                <ChevronRight />
              </button>
            </div>
          </div>
          <div class="subgrid-products accessory-slider-viewport" ref="accessorySliderRef">
            <div 
              v-for="(prod, idx) in accessoryProducts" 
              :key="prod.id_sanpham" 
              class="gaming-product-card accessory-slide-card scroll-reveal"
              :class="'stagger-' + (idx % 4)"
              @click="viewDetail(prod.id_sanpham)"
            >
              <div class="product-badge" :class="getBadgeClass(prod)">
                {{ getBadgeText(prod) }}
              </div>
              <button 
                class="wishlist-heart-btn" 
                :class="{ active: isInWishlist(prod) }"
                @click.stop="toggleWishlistLocal(prod)"
              >
                <Heart :fill="isInWishlist(prod) ? '#ef4444' : 'none'" />
              </button>
              <div class="product-image-wrapper">
                <img :src="prod.image" :alt="prod.tenSP" loading="lazy" class="product-img" />
              </div>
              <h3 class="product-name">{{ prod.tenSP }}</h3>
              <div class="product-specs-pills">
                <span v-for="(spec, sIdx) in prod.specs" :key="sIdx" class="spec-pill">{{ spec }}</span>
              </div>
              <div class="product-rating">
                <span class="star filled">★</span>
                <span class="reviews-count">({{ prod.reviews }})</span>
              </div>
              <div class="product-bottom-row">
                <div class="product-pricing">
                  <span class="price-new">{{ formatPrice(prod.gia) }}</span>
                  <div class="price-old-row" v-if="prod.oldPrice">
                    <span class="price-old">{{ formatPrice(prod.oldPrice) }}</span>
                  </div>
                  <div class="card-badge-row-1">
                    <span class="badge-chinh-hang">✓ Chính Hãng</span>
                  </div>
                  <div class="card-badge-row-2">
                    <span class="badge-ship-warranty">⚡ Freeship 2H</span>
                    <span class="badge-ship-warranty">✦ BH 24T</span>
                  </div>
                </div>
                <button class="card-cart-btn" @click.stop="addToCart(prod)"><ShoppingCart /></button>
              </div>
            </div>
          </div>
        </div>

        <!-- MSI Grid -->
        <div class="brand-subgrid-row" v-if="msiProducts.length > 0">
          <div class="subgrid-header">
            <h3>MSI Gaming</h3>
            <button class="btn-view-all" @click="filterByBrandFromLogo('MSI')">Xem tất cả</button>
          </div>
          <div class="subgrid-products">
            <div 
              v-for="(prod, idx) in msiProducts.slice(0, 4)" 
              :key="prod.id_sanpham" 
              class="gaming-product-card scroll-reveal"
              :class="'stagger-' + (idx % 4)"
              @click="viewDetail(prod.id_sanpham)"
            >
              <div class="product-badge" :class="getBadgeClass(prod)">{{ getBadgeText(prod) }}</div>
              <button 
                class="wishlist-heart-btn" 
                :class="{ active: isInWishlist(prod) }"
                @click.stop="toggleWishlistLocal(prod)"
              >
                <Heart :fill="isInWishlist(prod) ? '#ef4444' : 'none'" />
              </button>
              <div class="product-image-wrapper">
                <img :src="prod.image" :alt="prod.tenSP" loading="lazy" class="product-img" />
              </div>
              <h3 class="product-name">{{ prod.tenSP }}</h3>
              <div class="product-specs-pills">
                <span v-for="(spec, sIdx) in prod.specs" :key="sIdx" class="spec-pill">{{ spec }}</span>
              </div>
              <div class="product-rating">
                <span class="star filled">★</span>
                <span class="reviews-count">({{ prod.reviews }})</span>
              </div>
              <div class="product-bottom-row">
                <div class="product-pricing">
                  <span class="price-new">{{ formatPrice(prod.gia) }}</span>
                  <div class="price-old-row" v-if="prod.oldPrice">
                    <span class="price-old">{{ formatPrice(prod.oldPrice) }}</span>
                  </div>
                  <div class="card-badge-row-1">
                    <span class="badge-chinh-hang">✓ Chính Hãng</span>
                  </div>
                  <div class="card-badge-row-2">
                    <span class="badge-ship-warranty">⚡ Freeship 2H</span>
                    <span class="badge-ship-warranty">✦ BH 24T</span>
                  </div>
                </div>
                <button class="card-cart-btn" @click.stop="addToCart(prod)"><ShoppingCart /></button>
              </div>
            </div>
          </div>
        </div>

        <!-- Acer Predator Grid -->
        <div class="brand-subgrid-row" v-if="acerProducts.length > 0">
          <div class="subgrid-header">
            <h3>Acer Predator</h3>
            <button class="btn-view-all" @click="filterByBrandFromLogo('Acer')">Xem tất cả</button>
          </div>
          <div class="subgrid-products">
            <div 
              v-for="(prod, idx) in acerProducts.slice(0, 4)" 
              :key="prod.id_sanpham" 
              class="gaming-product-card scroll-reveal"
              :class="'stagger-' + (idx % 4)"
              @click="viewDetail(prod.id_sanpham)"
            >
              <div class="product-badge" :class="getBadgeClass(prod)">{{ getBadgeText(prod) }}</div>
              <button 
                class="wishlist-heart-btn" 
                :class="{ active: isInWishlist(prod) }"
                @click.stop="toggleWishlistLocal(prod)"
              >
                <Heart :fill="isInWishlist(prod) ? '#ef4444' : 'none'" />
              </button>
              <div class="product-image-wrapper">
                <img :src="prod.image" :alt="prod.tenSP" loading="lazy" class="product-img" />
              </div>
              <h3 class="product-name">{{ prod.tenSP }}</h3>
              <div class="product-specs-pills">
                <span v-for="(spec, sIdx) in prod.specs" :key="sIdx" class="spec-pill">{{ spec }}</span>
              </div>
              <div class="product-rating">
                <span class="star filled">★</span>
                <span class="reviews-count">({{ prod.reviews }})</span>
              </div>
              <div class="product-bottom-row">
                <div class="product-pricing">
                  <span class="price-new">{{ formatPrice(prod.gia) }}</span>
                  <div class="price-old-row" v-if="prod.oldPrice">
                    <span class="price-old">{{ formatPrice(prod.oldPrice) }}</span>
                  </div>
                  <div class="card-badge-row-1">
                    <span class="badge-chinh-hang">✓ Chính Hãng</span>
                  </div>
                  <div class="card-badge-row-2">
                    <span class="badge-ship-warranty">⚡ Freeship 2H</span>
                    <span class="badge-ship-warranty">✦ BH 24T</span>
                  </div>
                </div>
                <button class="card-cart-btn" @click.stop="addToCart(prod)"><ShoppingCart /></button>
              </div>
            </div>
          </div>
        </div>

        <!-- Lenovo Legion Grid -->
        <div class="brand-subgrid-row" v-if="lenovoProducts.length > 0">
          <div class="subgrid-header">
            <h3>Lenovo Legion</h3>
            <button class="btn-view-all" @click="filterByBrandFromLogo('Lenovo')">Xem tất cả</button>
          </div>
          <div class="subgrid-products">
            <div 
              v-for="(prod, idx) in lenovoProducts.slice(0, 4)" 
              :key="prod.id_sanpham" 
              class="gaming-product-card scroll-reveal"
              :class="'stagger-' + (idx % 4)"
              @click="viewDetail(prod.id_sanpham)"
            >
              <div class="product-badge" :class="getBadgeClass(prod)">{{ getBadgeText(prod) }}</div>
              <button 
                class="wishlist-heart-btn" 
                :class="{ active: isInWishlist(prod) }"
                @click.stop="toggleWishlistLocal(prod)"
              >
                <Heart :fill="isInWishlist(prod) ? '#ef4444' : 'none'" />
              </button>
              <div class="product-image-wrapper">
                <img :src="prod.image" :alt="prod.tenSP" loading="lazy" class="product-img" />
              </div>
              <h3 class="product-name">{{ prod.tenSP }}</h3>
              <div class="product-specs-pills">
                <span v-for="(spec, sIdx) in prod.specs" :key="sIdx" class="spec-pill">{{ spec }}</span>
              </div>
              <div class="product-rating">
                <span class="star filled">★</span>
                <span class="reviews-count">({{ prod.reviews }})</span>
              </div>
              <div class="product-bottom-row">
                <div class="product-pricing">
                  <span class="price-new">{{ formatPrice(prod.gia) }}</span>
                  <div class="price-old-row" v-if="prod.oldPrice">
                    <span class="price-old">{{ formatPrice(prod.oldPrice) }}</span>
                  </div>
                  <div class="card-badge-row-1">
                    <span class="badge-chinh-hang">✓ Chính Hãng</span>
                  </div>
                  <div class="card-badge-row-2">
                    <span class="badge-ship-warranty">⚡ Freeship 2H</span>
                    <span class="badge-ship-warranty">✦ BH 24T</span>
                  </div>
                </div>
                <button class="card-cart-btn" @click.stop="addToCart(prod)"><ShoppingCart /></button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- SECTION 8 - CTA CUỐI TRANG -->
    <section class="gaming-cta-section scroll-reveal">
      <div class="cta-bg-glow"></div>
      <div class="gaming-container cta-content-wrapper">
        <div class="cta-left">
          <h2>Tìm Chiếc Laptop Gaming Phù Hợp Nhất Cho Bạn</h2>
          <p>Đội ngũ chuyên gia Predator Group luôn sẵn sàng hỗ trợ lựa chọn cấu hình phù hợp với nhu cầu và ngân sách của bạn.</p>
          
          <div class="expert-profile">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100" alt="Bùi Quang Huy" class="expert-avatar" />
            <div class="expert-info">
              <span class="expert-name">Bùi Quang Huy</span>
              <span class="expert-title">Trưởng Bộ Phận Tư Vấn Gaming</span>
            </div>
          </div>
        </div>
        
        <div class="cta-right">
          <form @submit.prevent="submitConsultation" class="cta-consult-form">
            <h3>Đăng ký tư vấn miễn phí</h3>
            <div class="form-group">
              <input type="text" v-model="consultant.name" placeholder="Họ và tên của bạn" required />
            </div>
            <div class="form-group">
              <input type="tel" v-model="consultant.phone" placeholder="Số điện thoại liên hệ" required />
            </div>
            <div class="form-group">
              <input type="email" v-model="consultant.email" placeholder="Địa chỉ email" required />
            </div>
            <div class="form-group">
              <select v-model="consultant.budget">
                <option value="" disabled selected>Chọn mức ngân sách dự kiến</option>
                <option value="under-20">Dưới 20 triệu</option>
                <option value="20-30">Từ 20 - 30 triệu</option>
                <option value="30-45">Từ 30 - 45 triệu</option>
                <option value="above-45">Trên 45 triệu</option>
              </select>
            </div>
            <button type="submit" class="btn btn-consult-submit">Tư vấn ngay</button>
          </form>
        </div>
      </div>
    </section>

  </main>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { prefetchProductsPage } from '@/services/productsPrefetch'
import { productImageUrl } from '@/services/urls'
import { getToken } from '@/services/auth'
import {
  Laptop,
  Cpu,
  Flame,
  Shield,
  Target,
  Award,
  Monitor,
  Compass,
  Zap,
  Activity,
  ChevronLeft,
  ChevronRight,
  ShoppingCart,
  Heart,
  Truck,
  RotateCcw,
  CreditCard,
  Headphones,
  Search,
  Percent,
  Tag,
  Gift,
  Keyboard,
  Mouse
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()

// ===================== STATE =====================
const isLoading = ref(true)
const products = ref([])
const activeSlideIndex = ref(0)
const currentSidebarFilter = ref('all')
const activeTabFilter = ref('best-seller')
const sortBy = ref('featured')
const currentPage = ref(1)
const itemsPerPage = 20

const localWishlistIds = ref([])
const accessorySliderRef = ref(null)

const consultant = ref({
  name: '',
  phone: '',
  email: '',
  budget: ''
})

// ===================== HERO SLIDES (Nền sáng, laptop gaming render bên phải) =====================
const heroSlides = [
  {
    background: '/095e653a-a4cc-45ea-9e96-c2aea03aa08d.jpg',
    badge: 'CÔNG NGHỆ GAMING',
    title: 'Hiệu năng đỉnh cao<br>Chơi game cực chất',
    desc: 'Khám phá các mẫu laptop gaming chính hãng với hiệu năng mạnh mẽ, màn hình tốc độ cao và thiết kế đậm chất game thủ.',
    primaryCTA: 'Mua ngay',
    secondaryCTA: 'Xem ưu đãi'
  },
  {
    background: '/Gemini_Generated_Image_j1cibhj1cibhj1ci.png',
    badge: 'SIÊU CẤU HÌNH RTX 40',
    title: 'Đồ họa chân thực<br>Ray Tracing đỉnh cao',
    desc: 'Trải nghiệm mượt mà những tựa game AAA nặng nhất với card đồ họa NVIDIA GeForce RTX thế hệ mới nhất.',
    primaryCTA: 'Mua ngay',
    secondaryCTA: 'Xem ưu đãi'
  },
  {
    background: '/Gemini_Generated_Image_kn4b52kn4b52kn4b.png',
    badge: 'SHOWROOM GAMING',
    title: 'Trai nghiem truc tiep<br>Chon may dung nhu cau',
    desc: 'So sanh nhieu dong laptop gaming tu RTX 4050 den RTX 4090, kem tu van cau hinh ro rang theo ngan sach.',
    primaryCTA: 'Xem san pham',
    secondaryCTA: 'Nhan uu dai'
  }
]

// ===================== SIDEBAR ITEMS =====================
const sidebarItems = [
  { label: 'Laptop Gaming RTX 4050', value: 'rtx4050', icon: Cpu },
  { label: 'Laptop Gaming RTX 4060', value: 'rtx4060', icon: Cpu },
  { label: 'Laptop Gaming RTX 4070', value: 'rtx4070', icon: Cpu },
  { label: 'Laptop Gaming RTX 4080', value: 'rtx4080', icon: Cpu },
  { label: 'Laptop Gaming RTX 4090', value: 'rtx4090', icon: Cpu },
  { label: 'ASUS ROG / TUF', value: 'ASUS', icon: Shield },
  { label: 'MSI Gaming', value: 'MSI', icon: Flame },
  { label: 'Acer Predator', value: 'Acer', icon: Target },
  { label: 'Lenovo Legion', value: 'Lenovo', icon: Zap },
  { label: 'Bàn phím cơ Gaming', value: 'banphim', icon: Keyboard },
  { label: 'Chuột Gaming', value: 'chuot', icon: Mouse },
  { label: 'Tai nghe Gaming', value: 'tainghe', icon: Headphones },
  { label: 'Lót chuột Gaming', value: 'lotchuot', icon: Laptop }
]

// ===================== SERVICE BENEFITS =====================
const serviceBenefits = [
  { title: 'Miễn phí vận chuyển', desc: 'Cho đơn hàng từ 499.000đ', icon: Truck },
  { title: 'Đổi trả dễ dàng', desc: 'Đổi trả trong 7 ngày', icon: RotateCcw },
  { title: 'Thanh toán an toàn', desc: 'Nhiều phương thức thanh toán', icon: CreditCard },
  { title: 'Hỗ trợ 24/7', desc: 'Hotline: 1900 1234', icon: Headphones }
]

// ===================== FILTER TABS (Bán chạy, Mới nhất, Giá tốt, Được đánh giá cao) =====================
const filterTabs = [
  { label: 'Bán chạy', value: 'best-seller' },
  { label: 'Mới nhất', value: 'newest' },
  { label: 'Giá tốt', value: 'best-price' },
  { label: 'Được đánh giá cao', value: 'high-rating' }
]

const promoCards = [
  { title: 'Voucher Gaming', desc: 'Giảm ngay 1.000.000đ khi mua laptop RTX 4070 trở lên.', code: 'PREDATOR1M', icon: Tag },
  { title: 'Tặng Gaming Gear', desc: 'Tặng combo Chuột Gaming + Bàn di chuột Predator cho mọi đơn hàng.', code: 'FREEGEAR24H', icon: Gift },
  { title: 'Trả góp 0%', desc: 'Hỗ trợ trả góp lãi suất 0% qua thẻ tín dụng và các công ty tài chính.', code: 'PREDATOR0PCT', icon: CreditCard },
  { title: 'Giảm giá sinh viên', desc: 'Giảm thêm 500k cho học sinh, sinh viên khi xuất trình thẻ.', code: 'STUDENT500K', icon: Percent }
]

const brandLogos = [
  { name: 'ASUS ROG', filterVal: 'ASUS', logoIcon: Shield },
  { name: 'MSI', filterVal: 'MSI', logoIcon: Flame },
  { name: 'Acer Predator', filterVal: 'Acer', logoIcon: Target },
  { name: 'Lenovo Legion', filterVal: 'Lenovo', logoIcon: Zap },
  { name: 'Alienware', filterVal: 'Alienware', logoIcon: Compass },
  { name: 'HP Victus', filterVal: 'HP', logoIcon: Award },
  { name: 'Dell Gaming', filterVal: 'Dell', logoIcon: Monitor }
]

// ===================== FALLBACK HIGH-FIDELITY DATA =====================
const fallbackProducts = [
  {
    id_sanpham: 101,
    tenSP: 'Laptop Gaming ASUS ROG Strix G16 G614JI-N4084W',
    brand: 'ASUS',
    category: 'Laptop Gaming',
    gia: 39990000,
    oldPrice: 45990000,
    specs: ['Core i7', 'RTX 4060', '16GB RAM', '1TB SSD', '165Hz'],
    image: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=600&q=80',
    rating: 4.8,
    reviews: 98,
    promo: 'Tặng kèm Balo ROG + Chuột Gaming',
    inStock: true
  },
  {
    id_sanpham: 102,
    tenSP: 'Laptop Gaming Dell Alienware x16 R2 Flagship',
    brand: 'Alienware',
    category: 'Laptop Gaming',
    gia: 98990000,
    oldPrice: 110000000,
    specs: ['Core i9', 'RTX 4090', '32GB RAM', '240Hz OLED'],
    image: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=600&q=80',
    rating: 5.0,
    reviews: 57,
    promo: 'Tặng Chuột Alienware AW610M + Balo',
    inStock: true
  },
  {
    id_sanpham: 103,
    tenSP: 'Laptop Gaming Lenovo Legion Slim 7 16IRH8',
    brand: 'Lenovo',
    category: 'Laptop Gaming',
    gia: 54990000,
    oldPrice: 60990000,
    specs: ['Core i7', 'RTX 4070', '16GB RAM', '1TB SSD', '240Hz'],
    image: 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?auto=format&fit=crop&w=600&q=80',
    rating: 4.8,
    reviews: 79,
    promo: 'Tặng kèm tai nghe Legion H200',
    inStock: true
  },
  {
    id_sanpham: 104,
    tenSP: 'Laptop Gaming MSI Stealth 16 Studio A13VG',
    brand: 'MSI',
    category: 'Laptop Gaming',
    gia: 69990000,
    oldPrice: 77990000,
    specs: ['Core i9', 'RTX 4080', '32GB RAM', '1TB SSD', '165Hz'],
    image: 'https://images.unsplash.com/photo-1555617117-08c39bb051aa?auto=format&fit=crop&w=600&q=80',
    rating: 4.9,
    reviews: 42,
    promo: 'Tặng Chuột MSI M99 + Balo Stealth',
    inStock: true
  },
  {
    id_sanpham: 105,
    tenSP: 'Laptop Gaming Acer Predator Helios Neo 16 PH16-71',
    brand: 'Acer',
    category: 'Laptop Gaming',
    gia: 34990000,
    oldPrice: 39990000,
    specs: ['Core i7', 'RTX 4060', '16GB RAM', '512GB SSD', '165Hz'],
    image: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80',
    rating: 4.7,
    reviews: 145,
    promo: 'Tặng Balo Predator Utility trị giá 2 triệu',
    inStock: true
  },
  {
    id_sanpham: 106,
    tenSP: 'Laptop Gaming HP Victus 16-r0129TX',
    brand: 'HP',
    category: 'Laptop Gaming',
    gia: 22990000,
    oldPrice: 26990000,
    specs: ['Core i5', 'RTX 4050', '16GB RAM', '512GB SSD', '144Hz'],
    image: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=600&q=80',
    rating: 4.5,
    reviews: 112,
    promo: 'Tặng chuột gaming HP M270',
    inStock: true
  },
  {
    id_sanpham: 107,
    tenSP: 'Laptop Gaming Dell Gaming G15 5530',
    brand: 'Dell',
    category: 'Laptop Gaming',
    gia: 25990000,
    oldPrice: 29990000,
    specs: ['Core i7', 'RTX 4050', '16GB RAM', '512GB SSD', '165Hz'],
    image: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=600&q=80',
    rating: 4.6,
    reviews: 68,
    promo: 'Tặng Chuột Gaming Dell',
    inStock: true
  },
  {
    id_sanpham: 108,
    tenSP: 'Laptop Gaming MSI Katana 15 RTX 4070',
    brand: 'MSI',
    category: 'Laptop Gaming',
    gia: 31990000,
    oldPrice: 36990000,
    specs: ['Core i7', 'RTX 4070', '16GB RAM', '1TB SSD', '144Hz'],
    image: 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?auto=format&fit=crop&w=600&q=80',
    rating: 4.7,
    reviews: 130,
    promo: 'Tặng kèm chuột MSI Gaming',
    inStock: true
  },
  {
    id_sanpham: 201,
    tenSP: 'Bàn phím cơ không dây ASUS ROG Strix Scope II 96 Wireless',
    brand: 'ASUS',
    category: 'Phụ kiện',
    gia: 3490000,
    oldPrice: 3990000,
    specs: ['ROG RX Switch', 'Hotswap', 'RGB Aura Sync', 'Kết nối 3 chế độ'],
    image: 'https://images.unsplash.com/photo-1618384887929-16ec33fab9ef?auto=format&fit=crop&w=600&q=80',
    rating: 4.9,
    reviews: 46,
    promo: 'Tặng kèm kê tay da cao cấp',
    inStock: true
  },
  {
    id_sanpham: 202,
    tenSP: 'Chuột Gaming không dây Razer DeathAdder V3 Pro Black',
    brand: 'Razer',
    category: 'Phụ kiện',
    gia: 3890000,
    oldPrice: 4290000,
    specs: ['63g Siêu nhẹ', 'Focus Pro 30K', 'Optical Gen-3', '90 giờ pin'],
    image: 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=600&q=80',
    rating: 4.8,
    reviews: 115,
    promo: 'Tặng bộ grip tape chính hãng',
    inStock: true
  },
  {
    id_sanpham: 203,
    tenSP: 'Tai nghe Gaming không dây ASUS ROG Cetra True Wireless SpeedNova',
    brand: 'ASUS',
    category: 'Phụ kiện',
    gia: 4690000,
    oldPrice: 5190000,
    specs: ['ANC Chủ động', 'SpeedNova 2.4GHz', 'Bluetooth', 'RGB LED'],
    image: 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&w=600&q=80',
    rating: 4.7,
    reviews: 32,
    promo: 'Tặng hộp bảo vệ silicone',
    inStock: true
  },
  {
    id_sanpham: 204,
    tenSP: 'Bàn di chuột Predator Gaming Mousepad Size L',
    brand: 'Acer',
    category: 'Phụ kiện',
    gia: 450000,
    oldPrice: 600000,
    specs: ['CORDURA Fabric', '450x400x3mm', 'Chống nước', 'Viền bo khâu'],
    image: 'https://images.unsplash.com/photo-1631009185129-e4a993e7724a?auto=format&fit=crop&w=600&q=80',
    rating: 4.6,
    reviews: 87,
    promo: 'Mua kèm giảm thêm 10%',
    inStock: true
  }
]

// ===================== DATA ACTIONS =====================
const getSwal = async () => {
  const module = await import('@/services/swal')
  return module.default
}

const formatPrice = (price) => {
  if (!price) return 'Liên hệ'
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

const isGaming = (p) => {
  const cat = (p.danh_muc?.ten_danhmuc || p.danhmuc?.tenDM || p.category || '').toLowerCase()
  const name = (p.tenSP || '').toLowerCase()
  return cat.includes('gaming') || 
         cat.includes('laptop gaming') || 
         name.includes('tuf') || 
         name.includes('rog') || 
         name.includes('predator') || 
         name.includes('alienware') || 
         name.includes('legion') || 
         name.includes('victus') || 
         name.includes('aorus') || 
         name.includes('katana') || 
         name.includes('cyborg') || 
         name.includes('stealth')
}

// Load products
const loadData = async () => {
  isLoading.value = true
  try {
    const cache = await prefetchProductsPage()
    let rawList = []
    if (cache && cache.productsRaw) {
      rawList = cache.productsRaw.filter(p => isGaming(p))
    }
    
    const mapped = rawList.map(p => {
      let generalSpecs = []
      try {
        const tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || [])
        if (Array.isArray(tskt)) {
          generalSpecs = tskt.map(item => item.giatri).filter(Boolean)
        }
      } catch (e) {}

      const variants = Array.isArray(p.bien_thes) ? p.bien_thes : []
      const premiumVariant = variants.length
        ? variants.slice().sort((a, b) => Number(b.gia || 0) - Number(a.gia || 0))[0]
        : null

      const giaSP = Number(premiumVariant?.gia || p.gia || 25000000)

      let ram = '16GB'
      let ssd = '512GB'
      let variantSpecs = []
      if (premiumVariant) {
        try {
          const bt = premiumVariant
          const tt = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || [])
          if (Array.isArray(tt)) {
            tt.forEach(attr => {
              const name = (attr.ten_thuoctinh || '').toLowerCase()
              if (name.includes('ram')) ram = attr.giatri
              if (name.includes('ssd') || name.includes('ổ cứng')) ssd = attr.giatri
            })
            variantSpecs = tt.map(attr => attr.giatri).filter(Boolean)
          }
        } catch (e) {}
      }

      return {
        id_sanpham: p.id_sanpham,
        id_bienthe: premiumVariant?.id_bienthe,
        variantName: premiumVariant?.ten_bienthe,
        tenSP: p.tenSP,
        brand: p.thuong_hieu?.ten_thuonghieu || p.thuonghieu?.tenTH || p.brand || 'Khác',
        category: 'Laptop Gaming',
        gia: giaSP,
        oldPrice: Math.floor(giaSP * 1.15),
        specs: variantSpecs.length > 0 ? variantSpecs.slice(0, 4) : (generalSpecs.length > 0 ? generalSpecs.slice(0, 4) : [ram, ssd, '144Hz']),
        image: productImageUrl(p, premiumVariant, 'https://via.placeholder.com/600'),
        rating: 4.7,
        reviews: Math.floor(Math.random() * 80) + 15,
        promo: p.mota_ngan || 'Tặng kèm Balo cao cấp + Chuột Wireless',
        inStock: p.trangthai === 'hoat_dong' || Number(premiumVariant?.soluong || 0) > 0,
        ram,
        ssd
      }
    })

    const existingIds = new Set(mapped.map(m => m.id_sanpham))
    const uniqueFallbacks = fallbackProducts.filter(fb => !existingIds.has(fb.id_sanpham))
    products.value = [...mapped, ...uniqueFallbacks]
  } catch (err) {
    console.error('Lỗi khi tải sản phẩm:', err)
    products.value = [...fallbackProducts]
  } finally {
    isLoading.value = false
  }
}

// ===================== DYNAMIC FILTER COMPUTEDS =====================
const filteredProducts = computed(() => {
  let list = products.value

  // Apply Sidebar brand/GPU/accessory filters
  if (currentSidebarFilter.value !== 'all') {
    const val = currentSidebarFilter.value.toLowerCase().replace(/\s+/g, '')
    if (val.startsWith('rtx')) {
      list = list.filter(p => {
        const titleNormalized = p.tenSP.toLowerCase().replace(/\s+/g, '')
        const specsNormalized = p.specs.some(s => s.toLowerCase().replace(/\s+/g, '').includes(val))
        return titleNormalized.includes(val) || specsNormalized
      })
    } else if (val === 'banphim') {
      list = list.filter(p => p.tenSP.toLowerCase().includes('bàn phím') || p.tenSP.toLowerCase().includes('keyboard'))
    } else if (val === 'chuot') {
      list = list.filter(p => (p.tenSP.toLowerCase().includes('chuột') || p.tenSP.toLowerCase().includes('mouse')) && !p.tenSP.toLowerCase().includes('lót chuột') && !p.tenSP.toLowerCase().includes('bàn di') && !p.tenSP.toLowerCase().includes('mousepad'))
    } else if (val === 'tainghe') {
      list = list.filter(p => p.tenSP.toLowerCase().includes('tai nghe') || p.tenSP.toLowerCase().includes('headphone') || p.tenSP.toLowerCase().includes('headset') || p.tenSP.toLowerCase().includes('cetra'))
    } else if (val === 'lotchuot') {
      list = list.filter(p => p.tenSP.toLowerCase().includes('lót chuột') || p.tenSP.toLowerCase().includes('bàn di') || p.tenSP.toLowerCase().includes('mousepad'))
    } else {
      list = list.filter(p => p.brand.toLowerCase().replace(/\s+/g, '').includes(val))
    }
  }

  // Apply Tab filters: Bán chạy, Mới nhất, Giá tốt, Được đánh giá cao
  const tabVal = activeTabFilter.value
  if (tabVal === 'best-seller') {
    list = list.filter(p => p.reviews > 65)
  } else if (tabVal === 'newest') {
    // Sort by id_sanpham descending
    list = [...list].sort((a, b) => b.id_sanpham - a.id_sanpham)
  } else if (tabVal === 'best-price') {
    // Has a solid discount percentage or lower price range
    list = list.filter(p => p.gia < 35000000)
  } else if (tabVal === 'high-rating') {
    list = list.filter(p => p.rating >= 4.7)
  }

  return list
})

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredProducts.value.slice(start, start + itemsPerPage)
})

const totalPages = computed(() => {
  return Math.ceil(filteredProducts.value.length / itemsPerPage)
})

// Accessory slider products
const isAccessoryProduct = (p) => {
  const text = `${p.tenSP || ''} ${p.category || ''}`.toLowerCase()
  return (
    text.includes('phụ kiện') ||
    text.includes('phu kien') ||
    text.includes('bàn phím') ||
    text.includes('ban phim') ||
    text.includes('keyboard') ||
    text.includes('chuột') ||
    text.includes('chuot') ||
    text.includes('mouse') ||
    text.includes('tai nghe') ||
    text.includes('headphone') ||
    text.includes('headset') ||
    text.includes('mousepad') ||
    text.includes('bàn di') ||
    text.includes('ban di')
  )
}

const accessoryProducts = computed(() => products.value.filter(isAccessoryProduct))
const msiProducts = computed(() => [])
const acerProducts = computed(() => [])
const lenovoProducts = computed(() => [])

// ===================== EVENT ACTIONS =====================
const selectSidebarFilter = (val) => {
  currentSidebarFilter.value = currentSidebarFilter.value === val ? 'all' : val
  currentPage.value = 1
  goToProductsSection()
}

const selectTabFilter = (val) => {
  activeTabFilter.value = val
  currentPage.value = 1
  scrollProductsPanelToTop()
}

const resetFilters = () => {
  currentSidebarFilter.value = 'all'
  activeTabFilter.value = 'best-seller'
  currentPage.value = 1
  scrollProductsPanelToTop()
}

const filterByBrandFromLogo = (brandVal) => {
  currentSidebarFilter.value = brandVal
  currentPage.value = 1
  goToProductsSection()
}

const changePage = (page) => {
  currentPage.value = page
  scrollProductsPanelToTop()
}

const goToProductsSection = () => {
  const el = document.getElementById('products-section')
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const scrollProductsPanelToTop = () => {
  requestAnimationFrame(() => {
    const panel = document.querySelector('.gaming-products-scroll-panel')
    if (panel) {
      panel.scrollTo({ top: 0, behavior: 'smooth' })
    }
  })
}

const scrollToPromotions = () => {
  const el = document.getElementById('promotions-section')
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const scrollAccessorySlider = (direction) => {
  const el = accessorySliderRef.value
  if (!el) return
  const distance = Math.max(300, Math.floor(el.clientWidth * 0.75))
  el.scrollBy({
    left: direction === 'next' ? distance : -distance,
    behavior: 'smooth'
  })
}

// Hero Slide Controls (Autoplay disabled as requested)
const nextSlide = () => {
  activeSlideIndex.value = (activeSlideIndex.value + 1) % heroSlides.length
}

const prevSlide = () => {
  activeSlideIndex.value = (activeSlideIndex.value - 1 + heroSlides.length) % heroSlides.length
}

// Badge helpers
const getBadgeText = (product) => {
  if (product.gia >= 70000000) return 'PREMIUM'
  if (product.oldPrice && (product.oldPrice - product.gia) / product.oldPrice >= 0.12) {
    return `-${Math.round(((product.oldPrice - product.gia) / product.oldPrice) * 100)}%`
  }
  if (product.id_sanpham % 3 === 0) return 'HOT'
  if (product.id_sanpham % 3 === 1) return 'NEW'
  return 'FLASH SALE'
}

const getBadgeClass = (product) => {
  const text = getBadgeText(product)
  if (text === 'PREMIUM') return 'badge-premium'
  if (text.startsWith('-')) return 'badge-discount'
  if (text === 'HOT') return 'badge-hot'
  if (text === 'NEW') return 'badge-new'
  return 'badge-flash-sale'
}

const viewDetail = (id) => {
  router.push(`/products/${id}`)
}

// Cart, Buy, Wishlist Operations
const addToCart = async (product, options = {}) => {
  const token = getToken()
  const swal = await getSwal()
  if (!token) {
    swal.confirm('Yêu cầu đăng nhập', 'Vui lòng đăng nhập trước khi mua hàng!', 'Đăng nhập')
      .then((isConfirmed) => {
        if (isConfirmed) {
          router.push({ path: '/login', query: { redirect: route.fullPath } })
        }
      })
    return
  }

  try {
    let variantId = product.id_bienthe
    if (!variantId) {
      const res = await api.get(`/sanpham/${product.id_sanpham}`, { skipGlobalLoader: true })
      const variants = res.data.bien_thes || res.data.bienThes || []
      if (variants.length > 0) {
        variantId = variants.slice().sort((a, b) => Number(b.gia || 0) - Number(a.gia || 0))[0].id_bienthe
      }
    }

    if (!variantId) throw new Error('Sản phẩm chưa có biến thể để thêm vào giỏ hàng.')

    const addResponse = await api.post('/gio-hang/them', {
      id_bienthe: variantId,
      soluong: 1,
      buy_now: options.buyNow === true
    })

    if (!options.silent) {
      swal.toast('Đã thêm sản phẩm vào giỏ hàng!', 'success')
    }
    window.dispatchEvent(new Event('cart-updated'))
    
    if (options.redirectTo) {
      const target = typeof options.redirectTo === 'function'
        ? options.redirectTo(addResponse.data?.item, variantId)
        : options.redirectTo
      router.push(target)
    }
  } catch (err) {
    console.error('Lỗi khi thêm vào giỏ hàng:', err)
    swal.error('Thất bại', err.response?.data?.message || 'Có lỗi xảy ra, vui lòng thử lại.')
  }
}

// Wishlist local interactive sync
const toggleWishlistLocal = async (product) => {
  const token = getToken()
  const swal = await getSwal()
  if (!token) {
    swal.confirm('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để lưu sản phẩm yêu thích!', 'Đăng nhập')
      .then((isConfirmed) => {
        if (isConfirmed) {
          router.push('/login')
        }
      })
    return
  }

  const isFav = isInWishlist(product)
  if (isFav) {
    const idx = localWishlistIds.value.indexOf(product.id_sanpham)
    if (idx > -1) localWishlistIds.value.splice(idx, 1)
    swal.toast('Đã bỏ yêu thích!', 'info')
  } else {
    try {
      let variantId = product.id_sanpham
      const res = await api.get(`/sanpham/${product.id_sanpham}`, { skipGlobalLoader: true })
      if (res.data) {
        const variants = res.data.bien_thes || res.data.bienThes || []
        if (variants.length > 0) {
          variantId = variants[0].id_bienthe
        }
      }
      await api.post('/yeu-thich/them', {
        id_bienthe: variantId,
        soluong: 1
      })
      localWishlistIds.value.push(product.id_sanpham)
      swal.toast('Đã thêm vào danh sách yêu thích!', 'success')
      window.dispatchEvent(new Event('wishlist-updated'))
    } catch (err) {
      console.error(err)
      swal.info('Thông báo', err.response?.data?.message || 'Đã xảy ra sự cố.')
    }
  }
}

const isInWishlist = (product) => {
  return localWishlistIds.value.includes(product.id_sanpham)
}

// Promotions & Consultation Forms
const claimPromo = async (promo) => {
  const swal = await getSwal()
  if (promo.code) {
    navigator.clipboard.writeText(promo.code)
    swal.toast(`Đã copy mã: ${promo.code}!`, 'success')
  } else {
    swal.fire({
      title: promo.title,
      text: promo.desc,
      icon: 'info',
      confirmButtonText: 'Đóng'
    })
  }
}

const submitConsultation = async () => {
  const swal = await getSwal()
  try {
    swal.fire({
      title: 'Đăng ký thành công!',
      text: `Cảm ơn ${consultant.value.name}. Chuyên gia Bùi Quang Huy sẽ liên hệ lại với bạn qua số ${consultant.value.phone} trong vòng 15 phút!`,
      icon: 'success',
      confirmButtonColor: '#2563EB',
      confirmButtonText: 'Tuyệt vời'
    })
    consultant.value = { name: '', phone: '', email: '', budget: '' }
  } catch (err) {
    console.error(err)
  }
}

// ===================== SCROLL REVEAL OBSERVER =====================
let observer = null
if (typeof window !== 'undefined') {
  observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed')
      }
    })
  }, { threshold: 0.05, rootMargin: '0px 0px -40px 0px' })
}

const triggerScrollReveal = () => {
  if (!observer) return
  nextTick(() => {
    setTimeout(() => {
      document.querySelectorAll('.scroll-reveal:not(.revealed)').forEach(el => {
        observer.observe(el)
      })
    }, 150)
  })
}

// Watchers to trigger scroll reveal when elements are dynamically rendered
watch([isLoading, currentPage, currentSidebarFilter, activeTabFilter], () => {
  triggerScrollReveal()
})

onMounted(() => {
  loadData()
  triggerScrollReveal()
})
</script>

<style scoped>
/* BASE STYLES & BRIGHT THEME */
.gaming-page {
  background-color: #f8fafc;
  color: #1e293b;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  padding-bottom: 80px;
}

.gaming-container {
  max-width: min(1640px, calc(100vw - 200px));
  margin: 0 auto;
  padding: 0 12px;
}

/* BREADCRUMBS (Sạch, nhỏ, xám nhạt, hover xanh) */
.gaming-breadcrumbs {
  display: none;
  padding: 16px 0 8px;
}
.gaming-breadcrumbs .gaming-container {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}
.gaming-breadcrumbs a {
  color: #64748b;
  text-decoration: none;
  transition: color 0.2s ease;
}
.gaming-breadcrumbs a:hover {
  color: #2563eb;
}
.gaming-breadcrumbs .separator {
  color: #cbd5e1;
}
.gaming-breadcrumbs .current {
  color: #1e293b;
  font-weight: 500;
}

/* SECTION 1 - HERO GRID */
.gaming-hero-section {
  margin-bottom: 24px;
}
.hero-grid {
  display: grid;
  grid-template-columns: 280px minmax(0, 1fr);
  gap: 16px;
  align-items: stretch;
}

/* SIDEBAR LEFT (Card trắng bo góc 16-20px) */
.hero-sidebar {
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  display: flex;
  flex-direction: column;
  padding: 20px 0;
  border-radius: 16px; /* Bo góc mềm */
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03); /* Shadow nhẹ */
  height: 100%;
  box-sizing: border-box;
  transition: box-shadow 0.3s ease, border-color 0.3s ease;
}
.hero-sidebar:hover {
  border-color: rgba(37, 99, 235, 0.15);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}
.sidebar-header {
  padding: 0 16px 12px;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 4px;
}
.sidebar-header h3 {
  font-size: 13.5px;
  font-weight: 700;
  margin: 0;
  color: #0f172a;
}
.sidebar-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.sidebar-list li {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 16px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.sidebar-list li:hover {
  background: rgba(37, 99, 235, 0.05);
  color: #2563eb;
  padding-left: 19px;
}
.sidebar-list li.active {
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-weight: 600;
  border-left: 3px solid #2563eb;
  padding-left: 15px;
}
.item-icon {
  width: 14px;
  height: 14px;
  opacity: 0.7;
  transition: color 0.2s ease;
}
.sidebar-list li:hover .item-icon {
  color: #2563eb;
  opacity: 1;
}

/* Khuyến mãi hot cuối sidebar */
.sidebar-promo-item {
  border-top: 1px dashed #e2e8f0;
  margin-top: 6px !important;
  padding-top: 8px !important;
  color: #ef4444 !important;
  font-weight: 700 !important;
}
.sidebar-promo-item:hover {
  background: #fef2f2 !important;
  color: #dc2626 !important;
}
.promo-fire-icon {
  width: 14px;
  height: 14px;
  color: #ef4444;
}

/* HERO BANNER COLUMN WRAPPER */
.hero-banner-column {
  display: flex;
  flex-direction: column;
  gap: 16px;
  height: 100%;
}

/* HERO BANNER SLIDER RIGHT (Nền sáng, bo góc 18-24px) */
.hero-banner-slider {
  position: relative;
  overflow: hidden;
  border-radius: 20px; /* Bo góc lớn */
  border: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #f0f9ff 0%, #f8fafc 100%); /* Nền sáng, cao cấp */
  box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
  flex: 1 1 auto;
  min-height: 460px;
}
.slider-wrapper {
  display: flex;
  height: 100%;
  transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.hero-slide {
  flex: 0 0 100%;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding: 40px 56px;
  box-sizing: border-box;
  background-position: center;
  background-size: cover;
  background-repeat: no-repeat;
}
.hero-slide::before {
  content: none;
}
.hero-slide::after {
  content: '';
  position: absolute;
  inset: 0;
  background: 
    linear-gradient(90deg, rgba(8, 15, 30, 0.85) 0%, rgba(8, 15, 30, 0.4) 55%, transparent 100%),
    radial-gradient(circle at 80% 25%, rgba(37, 99, 235, 0.15) 0%, transparent 60%),
    radial-gradient(circle at 90% 75%, rgba(239, 68, 68, 0.12) 0%, transparent 50%);
  z-index: 1;
  pointer-events: none;
}
.slide-content-left {
  max-width: 560px;
  position: relative;
  z-index: 2;
  text-align: left;
  padding: 0;
}
.slide-badge {
  display: inline-block;
  background: rgba(37, 99, 235, 0.12) !important;
  color: #3b82f6 !important;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1.5px;
  padding: 6px 14px;
  border-radius: 999px;
  margin-bottom: 16px;
  text-transform: uppercase;
  border: 1px solid rgba(37, 99, 235, 0.35) !important;
  box-shadow: 0 0 10px rgba(37, 99, 235, 0.15);
  animation: eyebrowPulse 2.5s infinite ease-in-out;
}
.slide-title {
  font-size: 40px;
  font-weight: 800;
  line-height: 1.2;
  margin: 0 0 14px;
  color: #ffffff;
  background: linear-gradient(135deg, #ffffff 65%, #93c5fd 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
}
.slide-desc {
  font-size: 14px;
  line-height: 1.6;
  color: #cbd5e1;
  margin: 0 0 24px;
  max-width: 520px;
  font-weight: 600;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}
.slide-actions {
  display: flex;
  gap: 12px;
}

/* BUTTONS */
.btn {
  font-weight: 700;
  font-size: 13.5px;
  padding: 12px 24px;
  border: none;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}
.btn-primary-gaming {
  background: #2563eb;
  color: #ffffff;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
  position: relative;
  overflow: hidden;
}
.btn-primary-gaming:hover {
  background: #1d4ed8;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.55);
}
.btn-primary-gaming::after {
  content: '';
  position: absolute;
  top: -50%;
  left: -60%;
  width: 20%;
  height: 200%;
  background: linear-gradient(to right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0) 100%);
  transform: rotate(30deg);
  animation: buttonShine 4s infinite ease-in-out;
}
.btn-secondary-gaming {
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}
.btn-secondary-gaming:hover {
  background: #f8fafc;
  color: #0f172a;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* BANNER CONTROLS */
.slider-control {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  box-shadow: 0 4px 12px rgba(15,23,42,0.06);
  transition: all 0.2s ease;
}
.slider-control:hover {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
}
.slider-control.prev {
  left: 16px;
}
.slider-control.next {
  right: 16px;
}
.slider-dots {
  position: absolute;
  bottom: 20px;
  left: 56px;
  display: flex;
  gap: 8px;
  z-index: 10;
}
.slider-dots .dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #cbd5e1;
  cursor: pointer;
  transition: all 0.2s ease;
}
.slider-dots .dot.active {
  background: #2563eb;
  width: 20px;
  border-radius: 99px;
}

/* SECTION 2 - UTILITY BENEFITS (Miễn phí vận chuyển...) */
.gaming-benefits-section {
  margin-bottom: 32px;
}
.benefits-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.benefit-card {
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.benefit-card:hover {
  transform: translateY(-3px);
  border-color: rgba(37, 99, 235, 0.2);
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06), 0 4px 12px rgba(37, 99, 235, 0.02);
}
.benefit-icon-wrapper {
  background: rgba(37, 99, 235, 0.08);
  border: 1px solid rgba(37, 99, 235, 0.15);
  width: 38px;
  height: 38px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #2563eb;
  transition: all 0.3s ease;
  flex-shrink: 0;
}
.benefit-card:hover .benefit-icon-wrapper {
  background: rgba(37, 99, 235, 0.15);
  transform: scale(1.05);
  color: #1d4ed8;
}
.benefit-icon {
  width: 18px;
  height: 18px;
}
.benefit-info h4 {
  font-size: 13px;
  font-weight: 700;
  margin: 0 0 2px;
  color: #0f172a;
}
.benefit-info p {
  font-size: 11px;
  color: #64748b;
  margin: 0;
}

/* SECTION 3 - FEATURED PRODUCTS HEADER */
.gaming-catalog-section {
  padding: 24px 0;
}
.catalog-filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 104px;
  z-index: 25;
  margin-bottom: 0;
  border-bottom: 2px solid #f1f5f9;
  padding: 14px 0 12px;
  background: rgba(248, 250, 252, 0.96);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}
.gaming-products-scroll-panel {
  max-height: calc(100vh - 280px);
  overflow-y: auto;
  overflow-x: hidden;
  padding: 28px 8px 8px 0;
  scrollbar-gutter: stable;
  scroll-behavior: smooth;
}
.gaming-products-scroll-panel::-webkit-scrollbar {
  width: 8px;
}
.gaming-products-scroll-panel::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 999px;
}
.gaming-products-scroll-panel::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 999px;
}
.gaming-products-scroll-panel::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
.filter-header-left h2 {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  position: relative;
  padding-bottom: 14px;
}
.filter-header-left h2::after {
  content: '';
  position: absolute;
  bottom: -14px;
  left: 0;
  width: 100%;
  height: 2px;
  background: #2563eb;
}
.filter-tabs {
  display: flex;
  gap: 4px;
}
.filter-tab-btn {
  background: none;
  border: none;
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
  padding: 8px 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 99px;
}
.filter-tab-btn:hover {
  color: #0f172a;
  background: #f1f5f9;
}
.filter-tab-btn.active {
  background: #2563eb;
  color: #ffffff;
}
.btn-view-all-link {
  background: none;
  border: none;
  color: #2563eb;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  transition: transform 0.2s ease;
}
.btn-view-all-link:hover {
  transform: translateX(3px);
}

/* SECTION 4 - PRODUCT GRID & CARDS (Bo góc 14-18px) */
.gaming-product-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 40px;
}
.gaming-product-card {
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 16px; /* Bo góc 16px */
  padding: 14px;
  position: relative;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease, border-color 0.3s ease;
  box-sizing: border-box;
}
.gaming-product-card:hover {
  transform: translateY(-6px);
  border-color: rgba(37, 99, 235, 0.35);
  box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08), 0 4px 12px rgba(37, 99, 235, 0.04);
}

/* Badges & Hearts */
.product-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  padding: 3px 8px;
  font-size: 10px;
  font-weight: 800;
  color: #ffffff;
  z-index: 5;
  border-radius: 4px;
  text-transform: uppercase;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}
.badge-discount {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
  box-shadow: 0 2px 6px rgba(239, 68, 68, 0.25);
}
.badge-premium {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
}
.badge-hot {
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
  box-shadow: 0 2px 6px rgba(249, 115, 22, 0.25);
}
.badge-new {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
  box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
}
.badge-flash-sale {
  background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%) !important;
  box-shadow: 0 2px 6px rgba(234, 179, 8, 0.25);
}

.wishlist-heart-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  z-index: 5;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
}
.wishlist-heart-btn:hover {
  color: #ef4444;
  background: #fef2f2;
  border-color: #fca5a5;
  transform: scale(1.08);
  box-shadow: 0 4px 10px rgba(239, 68, 68, 0.15);
}
.wishlist-heart-btn.active {
  color: #ef4444;
  background: #fef2f2;
  border-color: #fca5a5;
}
.wishlist-heart-btn svg {
  width: 15px;
  height: 15px;
}

/* Image background white clean */
.product-image-wrapper {
  height: 142px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 12px;
  margin-bottom: 16px;
  background: #ffffff;
  overflow: hidden;
}
.product-img {
  max-height: 100%;
  max-width: 100%;
  object-fit: contain;
  transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.gaming-product-card:hover .product-img {
  transform: scale(1.05);
}

/* Name */
.product-name {
  font-size: 13px;
  font-weight: 700;
  line-height: 1.4;
  height: 38px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  margin: 0 0 8px;
  color: #1e293b;
  transition: color 0.2s ease;
}
.gaming-product-card:hover .product-name {
  color: #2563eb;
}

/* Specs bo tròn */
.product-specs-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-bottom: 12px;
}
.spec-pill {
  background: #f8fafc;
  color: #64748b;
  border: 1px solid #e2e8f0;
  font-size: 10px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 999px; /* Tròn */
  transition: all 0.2s ease;
}
.gaming-product-card:hover .spec-pill {
  background: rgba(37, 99, 235, 0.05);
  border-color: rgba(37, 99, 235, 0.15);
  color: #2563eb;
}

/* Stars */
.product-rating {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: #94a3b8;
  margin-bottom: 14px;
  margin-top: auto;
}
.stars {
  color: #e2e8f0;
}
.star.filled {
  color: #fbbf24;
}

/* Pricing bottom-row (Góc phải dưới có nút giỏ hàng) */
.product-bottom-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}
.product-pricing {
  display: flex;
  flex-direction: column;
}
.price-new {
  color: #ef4444; /* Đỏ nổi bật */
  font-size: 14.5px;
  font-weight: 800;
}
.price-old {
  color: #94a3b8;
  font-size: 12px;
  text-decoration: line-through;
}
.card-cart-btn {
  width: 34px;
  height: 34px;
  background: rgba(37, 99, 235, 0.08);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #2563eb;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  border-radius: 8px;
}
.card-cart-btn svg {
  width: 16px;
  height: 16px;
}
.card-cart-btn:hover {
  background: #2563eb;
  color: #ffffff;
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.gaming-product-grid {
  gap: 12px;
}

.gaming-product-card {
  background: rgba(226, 232, 240, 0.94);
  border: 1px solid rgba(148, 163, 184, 0.26);
  border-radius: 10px;
  padding: 8px;
  overflow: visible;
  box-shadow: none;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.gaming-product-card:hover {
  background: rgba(241, 245, 249, 0.98);
  border-color: rgba(37, 99, 235, 0.32);
  transform: translateY(-3px);
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.14);
}

.gaming-product-card .product-image-wrapper {
  width: 100%;
  aspect-ratio: 1 / 0.78;
  height: auto;
  margin: 0 0 9px;
  background: #ffffff;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.gaming-product-card .product-img {
  max-width: 86%;
  max-height: 82%;
}

.gaming-product-card .wishlist-heart-btn {
  top: 18px;
  right: 18px;
  width: 38px;
  height: 38px;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #ffffff;
  opacity: 0;
  transform: translateY(-4px);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
  transition: all 0.2s ease;
}

.gaming-product-card:hover .wishlist-heart-btn,
.gaming-product-card .wishlist-heart-btn.active {
  opacity: 1;
  transform: translateY(0);
}

.gaming-product-card .wishlist-heart-btn:hover,
.gaming-product-card .wishlist-heart-btn.active {
  background: #ef4444;
  border-color: #ef4444;
  color: #ffffff;
}

.gaming-product-card .wishlist-heart-btn svg {
  width: 16px;
  height: 16px;
}

.gaming-product-card .product-name {
  color: #0f172a;
  font-size: 12.5px;
  font-weight: 700;
  line-height: 1.35;
  height: 34px;
  margin: 0 0 5px;
}

.gaming-product-card .product-specs-pills {
  gap: 4px;
  margin-bottom: 9px;
}

.gaming-product-card .spec-pill {
  background: rgba(255, 255, 255, 0.76);
  border: 1px solid #e2e8f0;
  color: #64748b;
  font-size: 9.5px;
  font-weight: 600;
  padding: 2px 6px;
}

.gaming-product-card .product-rating {
  font-size: 11.5px;
  margin-bottom: 9px;
}

.gaming-product-card .product-bottom-row {
  gap: 8px;
}

.gaming-product-card .product-pricing {
  min-width: 0;
}

.gaming-product-card .price-row {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.gaming-product-card .price-new {
  color: #0f172a;
  font-size: 14.5px;
  font-weight: 700;
}

.gaming-product-card .price-old {
  color: #475569;
  font-size: 11.5px;
  font-weight: 500;
}

.gaming-product-card .card-badge-row-1 {
  margin-top: 6px;
}

.gaming-product-card .badge-chinh-hang {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  color: #1d4ed8;
  font-size: 9.5px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
}

.gaming-product-card .card-badge-row-2 {
  display: flex;
  gap: 4px;
  margin-top: 5px;
  flex-wrap: wrap;
}

.gaming-product-card .badge-ship-warranty {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  background: rgba(255, 255, 255, 0.76);
  border: 1px solid rgba(148, 163, 184, 0.22);
  color: #111827;
  font-size: 9.5px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
}

.gaming-product-card .card-cart-btn {
  position: absolute;
  right: 8px;
  bottom: 8px;
  width: 34px;
  height: 34px;
  background: #2563eb;
  color: #ffffff;
  border-radius: 50%;
  opacity: 0;
  transform: scale(0.8);
  z-index: 5;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);
  transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.gaming-product-card:hover .card-cart-btn {
  opacity: 1;
  transform: scale(1);
}

.gaming-product-card .card-cart-btn svg {
  width: 15px;
  height: 15px;
}

.gaming-product-card .card-cart-btn:hover {
  background: #1d4ed8;
  transform: scale(1.06);
}

/* PAGINATION */
.pagination-container {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}
.page-btn {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  color: #475569;
  border-radius: 8px;
  transition: all 0.2s ease;
}
.page-btn:hover:not(:disabled) {
  background: #eff6ff;
  color: #2563eb;
  border-color: #2563eb;
}
.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* SKELETONS */
.gaming-loading-container {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.gaming-skeleton-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 16px;
  height: 320px;
  border-radius: 16px;
}
.skeleton-image {
  height: 140px;
  background: #f1f5f9;
  margin-bottom: 12px;
  border-radius: 8px;
}
.skeleton-title {
  height: 16px;
  background: #f1f5f9;
  width: 80%;
  margin-bottom: 8px;
  border-radius: 4px;
}
.skeleton-specs {
  height: 20px;
  background: #f1f5f9;
  width: 60%;
  margin-bottom: 8px;
  border-radius: 4px;
}
.skeleton-price {
  height: 22px;
  background: #f1f5f9;
  width: 40%;
  border-radius: 4px;
}
.gaming-empty-container {
  text-align: center;
  padding: 56px 20px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
}
.empty-icon {
  width: 44px;
  height: 44px;
  color: #94a3b8;
  margin-bottom: 12px;
}

/* SECTION 5 - PROMOTIONS GRID */
.gaming-promotions-section {
  background: #172554;
  padding: 56px 0 60px;
  color: #ffffff;
  border-radius: 0;
  margin: 24px 0;
}
.section-header-center {
  text-align: center;
  margin-bottom: 36px;
}
.accent-label {
  color: #bfdbfe;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 2px;
  text-transform: uppercase;
  margin-bottom: 6px;
  display: block;
}
.section-header-center h2 {
  font-size: 28px;
  font-weight: 800;
  margin: 0;
  color: #ffffff;
}
.promotions-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  padding: 0 16px;
}
.promo-card-custom {
  background: rgba(30, 58, 138, 0.65);
  border: 1px solid rgba(191, 219, 254, 0.15);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  padding: 28px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s ease, background 0.3s ease, box-shadow 0.3s ease;
  border-radius: 14px;
  box-shadow: 0 16px 32px rgba(15, 23, 42, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.05);
}
.promo-card-custom:hover {
  transform: translateY(-5px);
  background: rgba(30, 58, 138, 0.85);
  border-color: rgba(96, 165, 250, 0.45);
  box-shadow: 0 18px 36px rgba(0, 0, 0, 0.3), 0 0 15px rgba(37, 99, 235, 0.15);
}
.promo-icon-box {
  width: 52px;
  height: 52px;
  background: rgba(37, 99, 235, 0.15);
  border: 1px solid rgba(37, 99, 235, 0.3);
  color: #60a5fa;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  border-radius: 50%;
  transition: transform 0.3s ease, background-color 0.3s ease, color 0.3s ease;
}
.promo-icon {
  width: 24px;
  height: 24px;
}
.promo-card-custom:hover .promo-icon-box {
  transform: scale(1.08) rotate(5deg);
  background: rgba(37, 99, 235, 0.25);
  color: #ffffff;
}
.promo-card-custom h3 {
  font-size: 16px;
  font-weight: 800;
  margin: 0 0 10px;
  color: #ffffff;
}
.promo-card-custom p {
  font-size: 12.5px;
  color: #e0f2fe;
  margin: 0 0 18px;
  line-height: 1.6;
  font-weight: 600;
}
.promo-code {
  font-size: 10.5px;
  color: #ffffff;
  background: rgba(15, 23, 42, 0.28);
  border: 1px solid rgba(219, 234, 254, 0.18);
  padding: 5px 10px;
  display: inline-block;
  margin-bottom: 16px;
  border-radius: 4px;
}
.promo-btn {
  background: #ffffff;
  color: #1d4ed8;
  border: none;
  font-size: 13px;
  font-weight: 800;
  padding: 10px 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  width: 100%;
  margin-top: auto;
  border-radius: 8px;
}
.promo-btn:hover {
  background: #dbeafe;
  color: #1e40af;
}

/* SECTION 6 - BRANDS CAROUSEL */
.gaming-brands-section {
  padding: 44px 42px;
  background: #ffffff;
  border-radius: 20px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  margin-bottom: 24px;
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.04);
}
.gaming-brands-section .section-header-center h2 {
  color: #0f172a;
}
.brands-slider-container {
  overflow: hidden;
  padding: 0;
}
.brands-track {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  align-items: center;
  gap: 14px;
}
.brand-logo-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 112px;
  padding: 18px 12px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  color: #475569;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 14px;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
}
.brand-logo-item:hover {
  transform: translateY(-4px);
  color: #2563eb;
  background: #ffffff;
  border-color: rgba(37, 99, 235, 0.45);
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06), 0 0 15px rgba(37, 99, 235, 0.15);
}
.brand-logo-icon {
  width: 34px;
  height: 34px;
  margin-bottom: 12px;
  stroke-width: 1.9;
  color: #64748b;
  transition: all 0.3s ease;
}
.brand-logo-item:hover .brand-logo-icon {
  color: #2563eb;
  transform: scale(1.08);
}
.brand-logo-name {
  font-size: 11.5px;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  text-align: center;
}

/* SECTION 7 - BRAND SUB-GRIDS */
.gaming-brand-subgrids-section {
  padding: 24px 0;
}
.brand-subgrid-row {
  margin-bottom: 40px;
}
.subgrid-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  border-left: 4px solid #2563eb;
  padding-left: 12px;
}
.subgrid-header h3 {
  font-size: 18px;
  font-weight: 800;
  margin: 0;
  color: #0f172a;
}
.btn-view-all {
  background: none;
  border: 1px solid #cbd5e1;
  color: #475569;
  font-size: 12px;
  font-weight: 600;
  padding: 6px 14px;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s ease;
}
.btn-view-all:hover {
  background: #eff6ff;
  color: #2563eb;
  border-color: #2563eb;
}
.subgrid-products {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.accessory-slider-row {
  margin-bottom: 48px;
}
.accessory-slider-header > div:first-child {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.accessory-eyebrow {
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1.6px;
  text-transform: uppercase;
}
.accessory-slider-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}
.accessory-slider-btn {
  width: 38px;
  height: 38px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #1e293b;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
}
.accessory-slider-btn:hover {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
}
.accessory-slider-btn svg {
  width: 18px;
  height: 18px;
}
.accessory-slider-viewport {
  display: flex;
  grid-template-columns: none;
  gap: 18px;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
  padding: 4px 2px 18px;
  -webkit-overflow-scrolling: touch;
}
.accessory-slider-viewport::-webkit-scrollbar {
  height: 8px;
}
.accessory-slider-viewport::-webkit-scrollbar-track {
  background: #e5e7eb;
  border-radius: 999px;
}
.accessory-slider-viewport::-webkit-scrollbar-thumb {
  background: #2563eb;
  border-radius: 999px;
}
.accessory-slide-card {
  flex: 0 0 calc((100% - 72px) / 5);
  min-width: 0;
  scroll-snap-align: start;
}

/* SECTION 8 - CTA CUỐI TRANG */
.gaming-cta-section {
  background:
    radial-gradient(circle at 18% 24%, rgba(59, 130, 246, 0.2), transparent 34%),
    linear-gradient(135deg, #07111f 0%, #0f172a 52%, #101b31 100%);
  padding: 64px 0;
  position: relative;
  overflow: hidden;
  color: #ffffff;
  border-radius: 0;
}
.cta-bg-glow {
  position: absolute;
  top: 50%;
  left: 0;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(37, 99, 235, 0.12) 0%, rgba(37, 99, 235, 0) 70%);
  transform: translateY(-50%);
  pointer-events: none;
}
.cta-content-wrapper {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 48px;
  position: relative;
  align-items: center;
  z-index: 2;
  padding: 0 24px;
}
.cta-left h2 {
  font-size: 32px;
  font-weight: 800;
  line-height: 1.2;
  margin: 0 0 14px;
  color: #ffffff;
  text-shadow: 0 10px 32px rgba(15, 23, 42, 0.45);
}
.cta-left p {
  font-size: 14.5px;
  color: #dbeafe;
  line-height: 1.6;
  margin: 0 0 32px;
  max-width: 760px;
}
.expert-profile {
  display: flex;
  align-items: center;
  gap: 12px;
}
.expert-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #2563eb;
}
.expert-name {
  display: block;
  font-size: 15px;
  font-weight: 700;
  color: #ffffff;
}
.expert-title {
  display: block;
  font-size: 12px;
  color: #bfdbfe;
}
.cta-consult-form {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid rgba(148, 163, 184, 0.38);
  width: min(100%, 520px);
  margin-left: auto;
  padding: 26px 28px;
  border-radius: 14px;
  box-shadow: 0 24px 60px rgba(2, 6, 23, 0.38), 0 0 0 1px rgba(255, 255, 255, 0.72) inset;
}
.cta-consult-form h3 {
  font-size: 20px;
  font-weight: 800;
  margin: 0 0 20px;
  text-align: center;
  color: #0f172a;
}
.form-group {
  margin-bottom: 10px;
}
.form-group input,
.form-group select {
  width: 100%;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 11px 13px;
  font-size: 13.5px;
  color: #0f172a;
  outline: none;
  border-radius: 7px;
  transition: all 0.2s ease;
  box-sizing: border-box;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.form-group input::placeholder {
  color: #64748b;
  opacity: 1;
}
.form-group select option {
  background: #ffffff;
  color: #0f172a;
}
.form-group input:focus,
.form-group select:focus {
  border-color: #2563eb;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
}
.btn-consult-submit {
  width: 100%;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: #ffffff;
  font-weight: 800;
  padding: 12px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 7px;
  box-shadow: 0 12px 22px rgba(37, 99, 235, 0.28);
}
.btn-consult-submit:hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 28px rgba(37, 99, 235, 0.36);
}

/* RESPONSIVE LAYOUTS */
@media (max-width: 1400px) {
  .gaming-product-grid {
    grid-template-columns: repeat(4, 1fr);
  }
  .accessory-slide-card {
    flex-basis: calc((100% - 54px) / 4);
  }
}

@media (max-width: 1200px) {
  .hero-grid {
    grid-template-columns: 240px minmax(0, 1fr);
  }
  .brands-track {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
  .gaming-product-grid,
  .subgrid-products {
    grid-template-columns: repeat(3, 1fr);
  }
  .promotions-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .accessory-slide-card {
    flex-basis: calc((100% - 36px) / 3);
  }
}

@media (max-width: 992px) {
  .hero-grid {
    grid-template-columns: 1fr;
  }
  .hero-sidebar {
    height: auto;
  }
  .sidebar-list {
    display: flex;
    flex-wrap: wrap;
    padding: 0 16px;
  }
  .sidebar-list li {
    padding: 8px 12px;
    border-radius: 6px;
    margin: 2px;
  }
  .hero-banner-slider {
    height: 460px;
  }
  .hero-slide {
    padding: 24px 36px;
  }
  .slide-title {
    font-size: 28px;
  }
  .benefits-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .gaming-product-grid,
  .subgrid-products {
    grid-template-columns: repeat(2, 1fr);
  }
  .cta-content-wrapper {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  .brands-track {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .brand-logo-item {
    min-height: 96px;
  }
  .accessory-slide-card {
    flex-basis: calc((100% - 18px) / 2);
  }
}

@media (max-width: 768px) {
  .hero-banner-slider {
    height: 400px;
  }
  .slide-title {
    font-size: 24px;
  }
  .slide-desc {
    font-size: 13px;
    margin-bottom: 16px;
  }
  .benefits-grid {
    grid-template-columns: 1fr;
  }
  .catalog-filter-bar {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
    position: static;
    padding-top: 0;
  }
  .gaming-products-scroll-panel {
    max-height: none;
    overflow: visible;
    padding-right: 0;
  }
  .accessory-slide-card {
    flex-basis: min(82vw, 320px);
  }
}

@media (max-width: 576px) {
  .gaming-product-grid,
  .subgrid-products {
    grid-template-columns: 1fr;
  }
  .hero-slide {
    padding: 24px;
  }
  .slide-content-left {
    max-width: 100%;
  }
  .slide-actions {
    flex-direction: column;
    width: 100%;
  }
  .btn {
    width: 100%;
  }
  .slider-dots {
    left: 24px;
    bottom: 12px;
  }
  .promotions-grid {
    grid-template-columns: 1fr;
  }
  .brand-logo-item {
    min-height: 92px;
  }
  .brands-track {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .cta-consult-form {
    padding: 20px 16px;
  }
}
/* KEYFRAMES & ANIMATIONS */
@keyframes buttonShine {
  0% { left: -60%; }
  15% { left: 140%; }
  100% { left: 140%; }
}

@keyframes eyebrowPulse {
  0%, 100% {
    box-shadow: 0 0 6px rgba(37, 99, 235, 0.15);
    border-color: rgba(37, 99, 235, 0.2) !important;
  }
  50% {
    box-shadow: 0 0 15px rgba(37, 99, 235, 0.35);
    border-color: rgba(37, 99, 235, 0.6) !important;
  }
}

/* SCROLL REVEAL ANIMATIONS */
.scroll-reveal {
  opacity: 0;
  transform: translateY(18px);
  transition: opacity 0.75s cubic-bezier(0.16, 1, 0.3, 1), transform 0.75s cubic-bezier(0.16, 1, 0.3, 1);
  will-change: opacity, transform;
}

.scroll-reveal.revealed {
  opacity: 1;
  transform: translateY(0);
}

.stagger-1 { transition-delay: 0.08s; }
.stagger-2 { transition-delay: 0.16s; }
.stagger-3 { transition-delay: 0.24s; }
.stagger-4 { transition-delay: 0.32s; }
</style>
