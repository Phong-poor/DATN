<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'

import { useRouter } from 'vue-router'
import { getToken } from '@/services/auth'


import GiftPopup from './GiftPopup.vue'
import api from '../../services/api'
import swal from '@/services/swal'
const router = useRouter()
const showGift = ref(false)





const slides = [
    {
        eyebrow: 'PREMIUM LAPTOP STORE 2026',
        title: 'Laptop cao cấp cho',
        highlight: 'mọi nhu cầu chuyên sâu',
        desc: 'Từ gaming, văn phòng đến đồ hoạ chuyên nghiệp. Chọn đúng cấu hình, đúng trải nghiệm, đúng đẳng cấp.',
        img: 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=1200',
        primary: 'Mua ngay',
        secondary: 'Xem bộ sưu tập'
    },
    {
        eyebrow: 'NEW GENERATION DEVICES',
        title: 'Hiệu năng mạnh mẽ',
        highlight: 'thiết kế dẫn đầu',
        desc: 'Sở hữu các dòng laptop mới nhất với màn hình sắc nét, pin bền bỉ và hiệu suất vượt trội.',
        img: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=1200',
        primary: 'Khám phá ngay',
        secondary: 'Tư vấn cấu hình'
    },
    {
        eyebrow: 'GAMING • CREATOR • BUSINESS',
        title: 'Công nghệ mới cho',
        highlight: 'trải nghiệm không giới hạn',
        desc: 'Cân mọi tác vụ từ chơi game AAA, dựng video 4K đến làm việc doanh nghiệp với độ ổn định tối ưu.',
        img: 'https://images.unsplash.com/photo-1511385348-a52b4a160dc2?w=1200',
        primary: 'Xem ưu đãi',
        secondary: 'So sánh sản phẩm'
    }
]

const categories = [
    { name: 'Gaming', desc: 'RTX mạnh mẽ, tản nhiệt tối ưu', icon: '🎮' },
    { name: 'Văn phòng', desc: 'Mỏng nhẹ, pin lâu, bền bỉ', icon: '💼' },
    { name: 'Đồ hoạ', desc: 'Màn đẹp, render nhanh, chuẩn màu', icon: '🎨' },
    { name: 'Doanh nhân', desc: 'Thiết kế cao cấp, bảo mật tốt', icon: '👔' }
]

const mapProducts = (rawProducts) => {
    const productVariants = rawProducts.map(p => {
        if (!p.bien_thes || p.bien_thes.length === 0) {
            return [{
                id: p.id_sanpham,
                key_id: String(p.id_sanpham),
                name: p.tenSP,
                category: p.danh_muc?.ten_danhmuc || 'Sản phẩm',
                id_danhmuc: String(p.id_danhmuc || p.danh_muc?.id_danhmuc || ''),
                id_thuonghieu: String(p.id_thuonghieu || p.thuong_hieu?.id_thuonghieu || ''),
                brandName: p.thuong_hieu?.ten_thuonghieu === 'Levono' ? 'Lenovo' : (p.thuong_hieu?.ten_thuonghieu || ''),
                weight: p.khoiluong,
                price: new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(0),
                img: p.hinhanh ? 'http://127.0.0.1:8000/storage/' + p.hinhanh : 'https://via.placeholder.com/300',
                badge: p.trangthai === 'Hot' ? 'HOT' : (p.trangthai === 'Mới' ? 'NEW' : '')
            }];
        }

        return p.bien_thes.map(bt => {
            let ram = '', cpu = '', gpu = '', kichthuoc = '', dophan = '', tamnen = '', pin = '', sac = '';
            let thuoc_tinh = [];
            try { thuoc_tinh = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || []); } catch (e) { }

            if (Array.isArray(thuoc_tinh)) {
                thuoc_tinh.forEach(attr => {
                    const ten = (attr.ten_thuoctinh || '').toLowerCase();
                    if (ten === 'ram') ram = attr.giatri;
                    else if (ten === 'cpu') cpu = attr.giatri;
                    else if (ten === 'gpu') gpu = attr.giatri;
                    else if (ten === 'kích thước') kichthuoc = attr.giatri;
                    else if (ten === 'độ phân giải') dophan = attr.giatri;
                    else if (ten === 'tấm nền') tamnen = attr.giatri;
                    else if (ten === 'pin') pin = attr.giatri;
                    else if (ten === 'sạc') sac = attr.giatri;
                });
            }
            
            const nameExt = [ram, cpu, gpu, kichthuoc, dophan, tamnen, pin, sac].filter(Boolean).join(' - ');
            const fullName = nameExt ? `${p.tenSP} (${nameExt})` : p.tenSP;

            return {
                id: p.id_sanpham,
                key_id: String(bt.id_bienthe || (p.id_sanpham + '_' + Math.random())),
                name: fullName,
                category: p.danh_muc?.ten_danhmuc || 'Sản phẩm',
                id_danhmuc: String(p.id_danhmuc || p.danh_muc?.id_danhmuc || ''),
                id_thuonghieu: String(p.id_thuonghieu || p.thuong_hieu?.id_thuonghieu || ''),
                brandName: p.thuong_hieu?.ten_thuonghieu === 'Levono' ? 'Lenovo' : (p.thuong_hieu?.ten_thuonghieu || ''),
                weight: p.khoiluong,
                price: bt.gia > 0 ? new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(bt.gia) : 'Liên hệ',
                img: p.hinhanh ? 'http://127.0.0.1:8000/storage/' + p.hinhanh : 'https://via.placeholder.com/300',
                badge: p.trangthai === 'Hot' ? 'HOT' : (p.trangthai === 'Mới' ? 'NEW' : '')
            };
        });
    });

    const flatList = [];
    let hasMore = true;
    let variantIndex = 0;
    while (hasMore) {
        hasMore = false;
        for (let i = 0; i < productVariants.length; i++) {
            if (productVariants[i].length > variantIndex) {
                flatList.push(productVariants[i][variantIndex]);
                hasMore = true;
            }
        }
        variantIndex++;
    }

    return flatList;
}

const featuredProducts = ref([])
onMounted(async () => {
    setTimeout(() => {
        console.log('[Home.vue] 5s timer reached. showGift = true');
        showGift.value = true
    }, 5000)



    try {
        const response = await api.get('/sanpham')
        const allProducts = mapProducts(response.data)
        featuredProducts.value = allProducts.slice(0, 20)
    } catch (error) {
        console.error('Lỗi khi tải sản phẩm:', error)
    }
})
// === SLIDER LOGIC ===
const currentProductPage = ref(0)
const itemsPerSliderFrame = 5
const totalProductPages = computed(() => Math.ceil(featuredProducts.value.length / itemsPerSliderFrame))

const visibleFeaturedProducts = computed(() => {
    if (featuredProducts.value.length === 0) return []
    const start = currentProductPage.value * itemsPerSliderFrame
    return featuredProducts.value.slice(start, start + itemsPerSliderFrame)
})

const nextFeaturedPage = () => {
    if (currentProductPage.value < totalProductPages.value - 1) currentProductPage.value++
}
const prevFeaturedPage = () => {
    if (currentProductPage.value > 0) currentProductPage.value--
}
// 👉 Bổ sung hàm xử lý Yêu thích
const themVaoYeuThich = async (product) => {
    const token = getToken()
    if (!token) {
        swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để thêm vào yêu thích!')
        router.push('/login')
        return
    }

    if (!product.id_bienthe) {
        swal.warning('Thông báo', 'Sản phẩm này chưa có cấu hình, không thể thêm!')
        return
    }

    try {
        await api.post('/yeu-thich/them', {
            id_bienthe: product.id_bienthe,
            soluong: 1
        })
        swal.success('Thành công', `Đã thêm ${product.name} vào danh sách yêu thích! ❤️`)
        // Kích hoạt sự kiện để Header tự động cập nhật số lượng trái tim
        window.dispatchEvent(new Event('wishlist-updated'))
    } catch (err) {
        swal.error('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra!')
    }
}


const benefits = [
    { icon: '✔️', title: '100% chính hãng', desc: 'Cam kết sản phẩm mới, nguyên seal, đầy đủ chứng từ.' },
    { icon: '🛡️', title: 'Bảo hành toàn diện', desc: 'Hỗ trợ bảo hành nhanh, chính sách đổi trả rõ ràng.' },
    { icon: '💳', title: 'Trả góp linh hoạt', desc: 'Trả góp 0%, hồ sơ đơn giản, duyệt nhanh chóng.' },
    { icon: '🚚', title: 'Giao hàng toàn quốc', desc: 'Đóng gói an toàn, giao nhanh, hỗ trợ kiểm tra hàng.' }
]

const news = [
    { title: 'Top laptop gaming đáng mua nhất năm 2026', desc: 'Những lựa chọn cân bằng giữa hiệu năng, nhiệt độ và giá bán cho game thủ hiện đại.', img: 'https://images.unsplash.com/photo-1581092335397-9583eb92d232?w=800', tag: 'ĐÁNH GIÁ' },
    { title: 'Laptop AI mới thay đổi trải nghiệm làm việc như thế nào?', desc: 'Khám phá các tính năng AI hỗ trợ xử lý văn bản, hình ảnh và tối ưu hiệu suất hệ thống.', img: 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800', tag: 'TIN TỨC' },
    { title: 'Cách chọn laptop đồ hoạ phù hợp cho designer', desc: 'Từ màn hình, CPU, GPU đến RAM và tản nhiệt, đây là những yếu tố bạn không nên bỏ qua.', img: 'https://images.unsplash.com/photo-1492724441997-5dc865305da7?w=800', tag: 'HƯỚNG DẪN' }
]

const reviews = [
    { name: 'Trần Minh Quân', role: 'Creative Designer', content: 'Website đẹp, mua hàng dễ, tư vấn đúng nhu cầu. Máy nhận được đúng như mong đợi.', avatar: 'https://randomuser.me/api/portraits/men/32.jpg' },
    { name: 'Nguyễn Phương Anh', role: 'Marketing Manager', content: 'Mình rất thích cách trình bày sản phẩm và trải nghiệm đặt hàng. Nhìn cực kỳ cao cấp.', avatar: 'https://randomuser.me/api/portraits/women/44.jpg' },
    { name: 'Lê Hoàng Nam', role: 'Pro Gamer', content: 'Laptop mạnh, giá tốt, giao hàng nhanh. Phần gaming nhìn rất chuyên nghiệp.', avatar: 'https://randomuser.me/api/portraits/men/52.jpg' }
]

const stats = [
    { value: '15K+', label: 'Khách hàng hài lòng' },
    { value: '500+', label: 'Mẫu laptop cao cấp' },
    { value: '24/7', label: 'Tư vấn kỹ thuật' },
    { value: '99%', label: 'Đánh giá tích cực' }
]

const current = ref(0)
let interval = null
const nextSlide = () => { current.value = (current.value + 1) % slides.length }
const prevSlide = () => { current.value = (current.value - 1 + slides.length) % slides.length }
const start = () => { stop(); interval = setInterval(nextSlide, 5000) }
const stop = () => { if (interval) clearInterval(interval) }
onMounted(start)
onUnmounted(stop)
</script>

<template>
    <GiftPopup v-if="showGift" :delay="0" />

    <main class="home">

        <!-- TOPBAR PROMO -->
        <div class="topbar-promo">
            <div class="topbar-track">
                <span>🚚 <b>Miễn phí</b> giao hàng cho đơn từ 300k</span>
                <span class="sep">•</span>
                <span>🔄 Thu cũ giá ngon – <b>Lên đời tiết kiệm</b></span>
                <span class="sep">•</span>
                <span>🛡️ Sản phẩm <b>Chính hãng</b> – Xuất VAT đầy đủ</span>
                <span class="sep">•</span>
                <span>⚡ <b>Giao nhanh 2h</b> nội thành toàn quốc</span>
                <span class="sep">•</span>
                <span>💳 Trả góp <b>0%</b> – Duyệt nhanh trong 5 phút</span>
                <span class="sep">•</span>
                <span>🚚 <b>Miễn phí</b> giao hàng cho đơn từ 300k</span>
                <span class="sep">•</span>
                <span>🔄 Thu cũ giá ngon – <b>Lên đời tiết kiệm</b></span>
                <span class="sep">•</span>
                <span>🛡️ Sản phẩm <b>Chính hãng</b> – Xuất VAT đầy đủ</span>
                <span class="sep">•</span>
                <span>⚡ <b>Giao nhanh 2h</b> nội thành toàn quốc</span>
            </div>
        </div>

        <!-- HERO BANNER (thụt lề 2 bên) -->
        <div class="hero-wrapper">
            <section class="hero" @mouseenter="stop" @mouseleave="start">
                <transition name="bg-fade" mode="out-in">
                    <div class="hero-slide-bg" :key="'bg-' + current">
                        <img :src="slides[current].img" alt="" />
                        <div class="hero-slide-overlay"></div>
                    </div>
                </transition>

                <div class="hero-inner">
                    <transition name="fade-slide" mode="out-in">
                        <div class="hero-content" :key="current">
                            <div class="hero-left">
                                <span class="hero-eyebrow">
                                    <span class="eyebrow-dot"></span>
                                    {{ slides[current].eyebrow }}
                                </span>
                                <h1>
                                    {{ slides[current].title }}
                                    <span>{{ slides[current].highlight }}</span>
                                </h1>
                                <p>{{ slides[current].desc }}</p>
                                <div class="hero-actions">
                                    <button class="btn btn-primary">{{ slides[current].primary }}</button>
                                    <button class="btn btn-secondary">{{ slides[current].secondary }}</button>
                                </div>
                                <div class="hero-metrics">
                                    <div class="metric">
                                        <strong>Miễn phí</strong>
                                        <span>giao hàng toàn quốc</span>
                                    </div>
                                    <div class="metric-div"></div>
                                    <div class="metric">
                                        <strong>0%</strong>
                                        <span>trả góp linh hoạt</span>
                                    </div>
                                    <div class="metric-div"></div>
                                    <div class="metric">
                                        <strong>24 tháng</strong>
                                        <span>bảo hành uy tín</span>
                                    </div>
                                </div>
                            </div>
                            <div class="hero-right">
                                <div class="hero-image-card">
                                    <img :src="slides[current].img" :alt="slides[current].title" />
                                </div>
                                <div class="floating-card top">
                                    <span>Xu hướng</span>
                                    <strong>Laptop 2026</strong>
                                </div>
                                <div class="floating-card bottom">
                                    <span>Deal nổi bật</span>
                                    <strong>Giảm đến 20%</strong>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <div class="hero-controls">
                        <button class="nav-btn" @click="prevSlide">‹</button>
                        <div class="dots">
                            <span v-for="(slide, i) in slides" :key="i" :class="{ active: i === current }"
                                @click="current = i"></span>
                        </div>
                        <button class="nav-btn" @click="nextSlide">›</button>
                    </div>
                </div>
            </section>
        </div>

        <!-- STATS -->
        <section class="stats">
            <div class="container stats-grid">
                <div class="stat-card" v-for="(item, i) in stats" :key="i">
                    <h3>{{ item.value }}</h3>
                    <p>{{ item.label }}</p>
                </div>
            </div>
        </section>

        <!-- CATEGORY -->
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <span class="section-label">DANH MỤC</span>
                        <h2>Lựa chọn đúng dòng laptop cho bạn</h2>
                        <p>Thiết kế tối giản, cấu hình mạnh mẽ, tối ưu theo từng nhu cầu sử dụng.</p>
                    </div>
                    <a href="#" class="section-link">Xem tất cả →</a>
                </div>
                <div class="category-grid">
                    <div class="category-card" v-for="(c, i) in categories" :key="i">
                        <div class="category-icon">{{ c.icon }}</div>
                        <h3>{{ c.name }}</h3>
                        <p>{{ c.desc }}</p>
                        <a href="#">Khám phá →</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURED — 20 sản phẩm slider chia 5 cột -->
        <section class="section featured-section">
            <div class="container">
                <div class="section-head center">
                    <div>
                        <span class="section-label">SẢN PHẨM NỔI BẬT</span>
                        <h2>Những mẫu laptop bán chạy nhất nhất</h2>
                        <p>Chọn lọc từ các dòng máy bán chạy với hiệu năng tốt, thiết kế đẹp và giá trị cao.</p>
                    </div>
                </div>
                
                <div class="product-slider-container">
                    <button class="slider-btn prev" @click="prevFeaturedPage" :disabled="currentProductPage === 0">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>

                    <Transition name="slider-fade" mode="out-in">
                        <div class="product-slider-grid" :key="currentProductPage">
                            <article class="product-card" v-for="p in visibleFeaturedProducts" :key="p.key_id">
                                <span class="product-badge" v-if="p.badge">{{ p.badge }}</span>
                                <div class="product-thumb"><img :src="p.img" :alt="p.name" /></div>
                                <div class="product-body">
                                    <span class="product-category">{{ p.category }}</span>
                                    <h3 :title="p.name">{{ p.name }}</h3>
                                    <p class="product-price">{{ p.price }}</p>
                                    
                                    <div class="product-actions">
                                        <button class="btn btn-primary small">Mua ngay</button>
                                        <router-link :to="`/products/${p.id}?variant=${p.key_id}`" class="btn btn-secondary small">
                                            Chi tiết
                                        </router-link>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </Transition>

                    <button class="slider-btn next" @click="nextFeaturedPage" :disabled="currentProductPage >= totalProductPages - 1">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>

                <div class="see-all-container">
                    <router-link to="/products" class="btn see-all-btn">
                        Xem tất cả sản phẩm →
                    </router-link>
                </div>
            </div>
        </section>

        <!-- PROMO -->
        <section class="promo">
            <div class="container promo-box">
                <div class="promo-text">
                    <span class="section-label light">ƯU ĐÃI ĐẶC BIỆT</span>
                    <h2>Nâng cấp trải nghiệm làm việc và giải trí ngay hôm nay</h2>
                    <p>Giảm giá trực tiếp, hỗ trợ trả góp, quà tặng đi kèm và dịch vụ kỹ thuật tận tâm cho mọi đơn hàng
                        laptop cao cấp.</p>
                </div>
                <div class="promo-actions">
                    <button class="btn btn-light">Nhận ưu đãi</button>
                    <button class="btn btn-outline-light">Liên hệ tư vấn</button>
                </div>
            </div>
        </section>

        <!-- BENEFITS -->
        <section class="section">
            <div class="container">
                <div class="section-head center">
                    <div>
                        <span class="section-label">LÝ DO CHỌN CHÚNG TÔI</span>
                        <h2>Dịch vụ xứng tầm một hệ thống bán laptop chuyên nghiệp</h2>
                    </div>
                </div>
                <div class="benefits-grid">
                    <div class="benefit-card" v-for="(b, i) in benefits" :key="i">
                        <div class="benefit-icon">{{ b.icon }}</div>
                        <h3>{{ b.title }}</h3>
                        <p>{{ b.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEWS -->
        <section class="section soft-bg">
            <div class="container">
                <div class="section-head">
                    <div>
                        <span class="section-label">BLOG & TIN TỨC</span>
                        <h2>Cập nhật xu hướng công nghệ mới nhất</h2>
                        <p>Thông tin hữu ích giúp bạn chọn đúng laptop và khai thác hiệu quả hơn.</p>
                    </div>
                    <a href="#" class="section-link">Xem thêm →</a>
                </div>
                <div class="news-grid">
                    <article class="news-card" v-for="(n, i) in news" :key="i">
                        <div class="news-thumb"><img :src="n.img" :alt="n.title" /></div>
                        <div class="news-body">
                            <span class="news-tag">{{ n.tag }}</span>
                            <h3>{{ n.title }}</h3>
                            <p>{{ n.desc }}</p>
                            <a href="#">Đọc thêm →</a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- REVIEWS -->
        <section class="section">
            <div class="container">
                <div class="section-head center">
                    <div>
                        <span class="section-label">KHÁCH HÀNG NÓI GÌ</span>
                        <h2>Niềm tin của khách hàng là giá trị lớn nhất</h2>
                        <p>Trải nghiệm mua sắm cao cấp, tư vấn tận tâm và dịch vụ hậu mãi chuyên nghiệp.</p>
                    </div>
                </div>
                <div class="review-grid">
                    <article class="review-card" v-for="(r, i) in reviews" :key="i">
                        <div class="stars">★★★★★</div>
                        <p class="review-content">"{{ r.content }}"</p>
                        <div class="review-user">
                            <img :src="r.avatar" :alt="r.name" />
                            <div><strong>{{ r.name }}</strong><span>{{ r.role }}</span></div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="cta">
            <div class="container cta-box">
                <div>
                    <span class="section-label light">SẴN SÀNG NÂNG CẤP?</span>
                    <h2>Tìm chiếc laptop hoàn hảo cho công việc và phong cách của bạn</h2>
                </div>
                <div class="cta-actions">
                    <button class="btn btn-light">Xem sản phẩm</button>
                    <button class="btn btn-outline-light">Tư vấn miễn phí</button>
                </div>
            </div>
        </section>

    </main>

</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap');

*,
*::before,
*::after {
    box-sizing: border-box;
}
.product-actions {
    display: flex;
    gap: 8px;
    align-items: stretch; /* Đảm bảo các nút cao bằng nhau */
}

.product-actions .btn {
    flex: 1;
    display: flex; /* Cần thiết cho thẻ <a> router-link canh giữa chữ */
    align-items: center;
    justify-content: center;
}

/* CSS riêng cho nút Yêu thích ở Home */
.btn-wishlist {
    flex: 0 0 auto !important; /* Không tự giãn như 2 nút kia */
    width: 36px;
    padding: 0 !important;
    background: #fff;
    border: 1px solid #ff4d4f;
    color: #ff4d4f;
    border-radius: 7px;
}

.btn-wishlist:hover {
    background: #fff1f0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 77, 79, 0.2);
}

.btn-wishlist svg {
    width: 15px;
    height: 15px;
}

/* ─── VARIABLES ─── */
/* 
  Bảng màu:
  - Navy chủ đạo: #0f2b5b (header) / #1a3a6e (hover)
  - Accent xanh sáng: #1e6be6 (button, label, link)
  - Body nền: #f5f7fa (trắng xám nhẹ)
  - Card: #ffffff
  - Text chính: #1a1f36
  - Text phụ: #6b7280
  - Border: #e4e9f0
*/
.home {
    --navy: #0f2b5b;
    --navy-dark: #091e40;
    --navy-light: #1a3a6e;
    --blue: #1e6be6;
    --blue-light: #3b82f6;
    --blue-hover: #1558cc;
    --bg: #f5f7fa;
    --bg-white: #ffffff;
    --bg-soft: #eef2f8;
    --border: #e4e9f0;
    --text: #1a1f36;
    --text2: #6b7280;
    --text3: #9ca3af;
    font-family: 'Be Vietnam Pro', sans-serif;
    background: var(--bg);
    color: var(--text);
}

a.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.container {
    width: min(1200px, calc(100% - 32px));
    margin: 0 auto;
}

/* ─── TOPBAR ─── */
.topbar-promo {
    background: var(--navy);
    padding: 8px 0;
    overflow: hidden;
}

.topbar-track {
    display: inline-flex;
    align-items: center;
    gap: 20px;
    white-space: nowrap;
    font-size: 12.5px;
    color: rgba(255, 255, 255, 0.85);
    animation: topbar-run 32s linear infinite;
}

.topbar-track b {
    font-weight: 700;
    color: #fff;
}

.topbar-track .sep {
    color: rgba(255, 255, 255, 0.3);
}

@keyframes topbar-run {
    from {
        transform: translateX(0);
    }

    to {
        transform: translateX(-50%);
    }
}

/* ─── HERO WRAPPER (thụt lề 2 bên) ─── */
.hero-wrapper {
    padding: 24px 32px;
    background: var(--bg);
}

/* ─── HERO ─── */
.hero {
    position: relative;
    min-height: 82vh;
    display: flex;
    align-items: center;
    overflow: hidden;
    padding: 64px 48px 44px;
    border-radius: 20px;
}

.hero-slide-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
    border-radius: 20px;
    overflow: hidden;
}

.hero-slide-bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    animation: kenburn 8s ease-out forwards;
    border-radius: 20px;
}

@keyframes kenburn {
    from {
        transform: scale(1.07);
    }

    to {
        transform: scale(1);
    }
}

.hero-slide-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(105deg,
            rgba(9, 30, 64, 0.96) 0%,
            rgba(9, 30, 64, 0.84) 38%,
            rgba(9, 30, 64, 0.52) 62%,
            rgba(9, 30, 64, 0.20) 100%);
    border-radius: 20px;
}

.hero-inner {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

.hero-content {
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    gap: 52px;
    align-items: center;
}

/* Left */
.hero-left {
    max-width: 580px;
}

.hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 22px;
    padding: 7px 14px 7px 10px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.7);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
    backdrop-filter: blur(8px);
}

.eyebrow-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--blue-light);
    box-shadow: 0 0 8px var(--blue-light);
    animation: pulse-dot 2s ease-in-out infinite;
    flex-shrink: 0;
}

@keyframes pulse-dot {

    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }

    50% {
        opacity: 0.4;
        transform: scale(0.7);
    }
}

.hero-left h1 {
    margin: 0 0 18px;
    font-size: 54px;
    line-height: 1.08;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: #ffffff;
}

.hero-left h1 span {
    display: block;
    background: linear-gradient(120deg, #60a5fa, var(--blue-light), #93c5fd);
    -webkit-background-clip: text;
    color: transparent;
}

.hero-left p {
    margin: 0 0 28px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 15px;
    line-height: 1.8;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 30px;
}

.hero-metrics {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 14px 20px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    width: fit-content;
}

.metric {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.metric strong {
    font-size: 15px;
    font-weight: 800;
    color: #ffffff;
}

.metric span {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.45);
}

.metric-div {
    width: 1px;
    height: 28px;
    background: rgba(255, 255, 255, 0.1);
}

/* Right */
.hero-right {
    position: relative;
    display: flex;
    justify-content: center;
}

.hero-image-card {
    width: 100%;
    max-width: 480px;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 32px 80px rgba(0, 0, 0, 0.5);
}

.hero-image-card img {
    width: 100%;
    height: 390px;
    object-fit: cover;
    display: block;
}

.floating-card {
    position: absolute;
    padding: 11px 15px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid var(--border);
    backdrop-filter: blur(14px);
    box-shadow: 0 10px 28px rgba(15, 43, 91, 0.2);
    animation: float-y 4s ease-in-out infinite;
}

.floating-card.bottom {
    animation-delay: 2s;
}

@keyframes float-y {

    0%,
    100% {
        transform: translateY(0)
    }

    50% {
        transform: translateY(-7px)
    }
}

.floating-card span {
    display: block;
    color: var(--text2);
    font-size: 11px;
    margin-bottom: 3px;
}

.floating-card strong {
    font-size: 14px;
    color: var(--navy);
    font-weight: 700;
}

.floating-card.top {
    top: 18px;
    right: -14px;
}

.floating-card.bottom {
    bottom: 18px;
    left: -14px;
}

/* Controls */
.hero-controls {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 14px;
}

.nav-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    cursor: pointer;
    font-size: 20px;
    display: grid;
    place-items: center;
    backdrop-filter: blur(8px);
    transition: all 0.2s;
}

.nav-btn:hover {
    background: var(--blue);
    border-color: var(--blue);
    color: #fff;
}

.dots {
    display: flex;
    align-items: center;
    gap: 6px;
}

.dots span {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.3);
    cursor: pointer;
    transition: all 0.3s;
}

.dots span.active {
    width: 30px;
    background: var(--blue-light);
}

/* Transitions */
.bg-fade-enter-active,
.bg-fade-leave-active {
    transition: opacity 0.85s ease;
}

.bg-fade-enter-from,
.bg-fade-leave-to {
    opacity: 0;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-14px);
}

/* ─── BUTTONS ─── */
.btn {
    border: none;
    outline: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border-radius: 7px;
    font-weight: 700;
    font-family: inherit;
    padding: 12px 20px;
    font-size: 14px;
}

.btn-primary {
    background: var(--blue);
    color: #fff;
    box-shadow: 0 6px 20px rgba(30, 107, 230, 0.35);
}

.btn-primary:hover {
    background: var(--blue-hover);
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(30, 107, 230, 0.45);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Secondary in white context */
.product-actions .btn-secondary,
.cta-actions .btn-secondary {
    background: #fff;
    color: var(--navy);
    border: 1px solid var(--border);
}

.product-actions .btn-secondary:hover {
    background: var(--bg-soft);
}

.btn-light {
    background: #fff;
    color: var(--navy);
    font-weight: 700;
}

.btn-light:hover {
    background: #e8eef8;
}

.btn-outline-light {
    background: transparent;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.35);
}

.btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.1);
}

.small {
    padding: 9px 14px;
    font-size: 12.5px;
}

/* ─── SECTION ─── */
.section {
    padding: 64px 0;
    background: var(--bg);
}

.soft-bg {
    background: var(--bg-soft);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}

.section-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 28px;
}

.section-head.center {
    justify-content: center;
    text-align: center;
}

.section-label {
    display: inline-flex;
    align-items: center;
    padding: 5px 11px;
    border-radius: 4px;
    background: rgba(30, 107, 230, 0.09);
    color: var(--blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.1em;
    margin-bottom: 11px;
    border: 1px solid rgba(30, 107, 230, 0.18);
}

.section-label.light {
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.2);
}

.section-head h2,
.promo-text h2,
.cta-box h2 {
    margin: 0 0 10px;
    font-size: 28px;
    line-height: 1.25;
    font-weight: 800;
    color: var(--text);
}

.promo-text h2,
.cta-box h2 {
    color: #fff;
}

.section-head p,
.promo-text p {
    margin: 0;
    color: var(--text2);
    font-size: 14px;
    line-height: 1.7;
    max-width: 580px;
}

.promo-text p {
    color: rgba(255, 255, 255, 0.65);
}

.section-link {
    white-space: nowrap;
    color: var(--blue);
    font-weight: 700;
    text-decoration: none;
    font-size: 13px;
    padding: 7px 14px;
    border-radius: 6px;
    border: 1px solid rgba(30, 107, 230, 0.25);
    transition: all 0.2s;
}

.section-link:hover {
    background: rgba(30, 107, 230, 0.07);
}

/* ─── STATS ─── */
.stats {
    padding: 0;
    background: var(--navy);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}

.stat-card {
    padding: 24px 20px;
    text-align: center;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-card:last-child {
    border-right: none;
}

.stat-card h3 {
    margin: 0 0 5px;
    font-size: 28px;
    font-weight: 800;
    color: #60a5fa;
}

.stat-card p {
    margin: 0;
    color: rgba(255, 255, 255, 0.55);
    font-size: 13px;
}

/* ─── CATEGORIES ─── */
.category-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.category-card {
    padding: 22px 18px;
    border-radius: 12px;
    background: var(--bg-white);
    border: 1px solid var(--border);
    transition: all 0.22s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(15, 43, 91, 0.05);
}

.category-card:hover {
    border-color: var(--blue);
    background: #f0f6ff;
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(30, 107, 230, 0.12);
}

.category-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 11px;
    font-size: 22px;
    margin-bottom: 13px;
    background: rgba(30, 107, 230, 0.08);
    border: 1px solid rgba(30, 107, 230, 0.15);
}

.category-card h3 {
    margin: 0 0 6px;
    font-size: 15px;
    font-weight: 700;
    color: var(--text);
}

.category-card p {
    margin: 0 0 13px;
    color: var(--text2);
    line-height: 1.6;
    font-size: 13px;
}

.category-card a {
    color: var(--blue);
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

/* ─── PRODUCTS — 4 cột × 2 hàng = 8 sản phẩm ─── */
.featured-section {
    padding-top: 0;
    background: var(--bg-soft);
}

.product-slider-container {
    display: flex;
    align-items: center;
    gap: 15px;
}
.product-slider-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    flex: 1;
}
.slider-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 2px solid var(--blue);
    background: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--blue);
    box-shadow: 0 4px 16px rgba(30, 107, 230, 0.15);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
    z-index: 10;
}
.slider-btn.prev svg { margin-right: 2px; width: 22px; height: 22px; }
.slider-btn.next svg { margin-left: 2px; width: 22px; height: 22px; }
.slider-btn:hover:not(:disabled) {
    background: var(--blue);
    color: #fff;
    box-shadow: 0 8px 24px rgba(30, 107, 230, 0.3);
    transform: scale(1.1);
}
.slider-btn:disabled {
    opacity: 0.25;
    border-color: var(--border);
    color: var(--text3);
    cursor: not-allowed;
    box-shadow: none;
}

/* SLIDER TRANSITION */
.slider-fade-enter-active,
.slider-fade-leave-active {
    transition: all 0.3s ease;
}
.slider-fade-enter-from,
.slider-fade-leave-to {
    opacity: 0;
    transform: scale(0.96) translateY(10px);
}
.see-all-container {
    margin-top: 36px;
    display: flex;
    justify-content: center;
}
.see-all-btn {
    background: #fff;
    color: var(--navy);
    border: 1px solid var(--border);
    padding: 12px 28px;
    font-size: 14px;
}
.see-all-btn:hover {
    background: var(--bg-soft);
}
.product-price {
    font-weight: 800;
    color: var(--blue);
    margin: 8px 0 16px;
    font-size: 15px;
}
.product-body h3 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 40px;
}

.product-card {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    background: var(--bg-white);
    border: 1px solid var(--border);
    transition: all 0.22s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(15, 43, 91, 0.06);
}

.product-card:hover {
    border-color: var(--blue);
    transform: translateY(-5px);
    box-shadow: 0 14px 36px rgba(30, 107, 230, 0.14);
}

.product-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 2;
    padding: 4px 9px;
    border-radius: 4px;
    background: var(--navy);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.05em;
}

.product-thumb {
    padding: 14px 14px 8px;
    background: var(--bg-soft);
}

.product-thumb img {
    width: 100%;
    height: 170px;
    object-fit: cover;
    border-radius: 8px;
    display: block;
    transition: transform 0.28s;
}

.product-card:hover .product-thumb img {
    transform: scale(1.05);
}

.product-body {
    padding: 12px 14px 16px;
}

.product-category {
    display: inline-block;
    margin-bottom: 5px;
    color: var(--blue);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
}

.product-body h3 {
    margin: 0 0 10px;
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.4;
}

.specs {
    list-style: none;
    padding: 0;
    margin: 0 0 11px;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.specs li {
    padding: 3px 8px;
    border-radius: 4px;
    background: var(--bg-soft);
    border: 1px solid var(--border);
    color: var(--text2);
    font-size: 11px;
}

.price-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.price-row strong {
    font-size: 16px;
    font-weight: 800;
    color: var(--blue);
}

.price-row span {
    color: var(--text3);
    font-size: 12px;
    text-decoration: line-through;
}

.product-actions {
    display: flex;
    gap: 8px;
}

.product-actions .btn {
    flex: 1;
}

/* ─── PROMO ─── */
.promo {
    padding: 14px 0;
}

.promo-box,
.cta-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    padding: 42px;
    border-radius: 16px;
    background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy) 50%, var(--navy-light) 100%);
    border: 1px solid rgba(30, 107, 230, 0.25);
    box-shadow: 0 20px 50px rgba(15, 43, 91, 0.18);
    color: #fff;
    position: relative;
    overflow: hidden;
}

.promo-box::before,
.cta-box::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 15% 50%, rgba(30, 107, 230, 0.28) 0%, transparent 60%);
    pointer-events: none;
}

.promo-actions,
.cta-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    flex-shrink: 0;
}

/* ─── BENEFITS ─── */
.benefits-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.benefit-card {
    padding: 24px 18px;
    text-align: center;
    border-radius: 12px;
    background: var(--bg-white);
    border: 1px solid var(--border);
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(15, 43, 91, 0.05);
}

.benefit-card:hover {
    border-color: var(--blue);
    box-shadow: 0 6px 20px rgba(30, 107, 230, 0.1);
    transform: translateY(-3px);
}

.benefit-icon {
    width: 52px;
    height: 52px;
    margin: 0 auto 14px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    font-size: 22px;
    background: rgba(30, 107, 230, 0.08);
    border: 1px solid rgba(30, 107, 230, 0.15);
}

.benefit-card h3 {
    margin: 0 0 7px;
    font-size: 14.5px;
    font-weight: 700;
    color: var(--text);
}

.benefit-card p {
    margin: 0;
    color: var(--text2);
    line-height: 1.7;
    font-size: 13px;
}

/* ─── NEWS ─── */
.news-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.news-card {
    overflow: hidden;
    border-radius: 12px;
    background: var(--bg-white);
    border: 1px solid var(--border);
    transition: all 0.22s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(15, 43, 91, 0.05);
}

.news-card:hover {
    border-color: var(--blue);
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(30, 107, 230, 0.12);
}

.news-thumb img {
    width: 100%;
    height: 195px;
    object-fit: cover;
    display: block;
}

.news-body {
    padding: 16px;
}

.news-tag {
    display: inline-block;
    margin-bottom: 9px;
    background: rgba(30, 107, 230, 0.09);
    color: var(--blue);
    font-size: 10px;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 3px;
    border: 1px solid rgba(30, 107, 230, 0.18);
    letter-spacing: 0.06em;
}

.news-body h3 {
    margin: 0 0 7px;
    font-size: 15px;
    line-height: 1.45;
    font-weight: 700;
    color: var(--text);
}

.news-body p {
    margin: 0 0 12px;
    color: var(--text2);
    line-height: 1.7;
    font-size: 13px;
}

.news-body a {
    color: var(--blue);
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

/* ─── REVIEWS ─── */
.review-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.review-card {
    padding: 22px 20px;
    border-radius: 12px;
    background: var(--bg-white);
    border: 1px solid var(--border);
    box-shadow: 0 2px 8px rgba(15, 43, 91, 0.05);
}

.stars {
    margin-bottom: 11px;
    color: #f59e0b;
    font-size: 15px;
    letter-spacing: 1px;
}

.review-content {
    min-height: 78px;
    margin: 0 0 14px;
    color: var(--text2);
    line-height: 1.8;
    font-size: 13.5px;
}

.review-user {
    display: flex;
    align-items: center;
    gap: 10px;
}

.review-user img {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border);
}

.review-user strong {
    display: block;
    margin-bottom: 2px;
    font-size: 13.5px;
    color: var(--text);
    font-weight: 700;
}

.review-user span {
    color: var(--text2);
    font-size: 12px;
}

/* ─── CTA ─── */
.cta {
    padding: 8px 0 56px;
    background: var(--bg);
}

.cta-box h2 {
    font-size: 24px;
    max-width: 480px;
    color: #fff;
}

/* ─── RESPONSIVE ─── */
@media (max-width: 1100px) {
    .hero-content {
        grid-template-columns: 1fr;
    }

    .hero-left h1 {
        font-size: 44px;
    }

    .stats-grid,
    .category-grid,
    .product-grid,
    .benefits-grid,
    .review-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stat-card {
        border-right: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .stat-card:nth-child(odd) {
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    .news-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .section {
        padding: 44px 0;
    }

    .hero-wrapper {
        padding: 12px 16px;
    }

    .hero {
        padding: 36px 24px 28px;
        min-height: auto;
    }

    .hero-left h1 {
        font-size: 32px;
    }

    .hero-image-card img {
        height: 230px;
    }

    .floating-card {
        display: none;
    }

    .hero-metrics {
        flex-wrap: wrap;
    }

    .metric-div {
        display: none;
    }

    .stats-grid,
    .category-grid,
    .product-grid,
    .benefits-grid,
    .news-grid,
    .review-grid {
        grid-template-columns: 1fr;
    }

    .section-head,
    .promo-box,
    .cta-box {
        flex-direction: column;
        align-items: flex-start;
    }

    .section-head.center {
        align-items: center;
    }

    .product-actions {
        flex-direction: column;
    }

    .promo-box,
    .cta-box {
        padding: 24px;
    }
}
</style>