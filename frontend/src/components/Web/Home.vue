<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'

import { useRouter } from 'vue-router'
import { getToken } from '@/services/auth'


import GiftPopup from './GiftPopup.vue'
import api from '../../services/api'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'
const router = useRouter()
const showGift = ref(false)





const slides = [
    {
        eyebrow: 'PREMIUM LAPTOP STORE 2026',
        title: 'Laptop cao cấp cho',
        highlight: 'mọi nhu cầu chuyên sâu',
        desc: 'Từ gaming, văn phòng đến đồ hoạ chuyên nghiệp. Chọn đúng cấu hình, đúng trải nghiệm, đúng đẳng cấp.',
        img: 'https://storage-asset.msi.com/global/picture/banner/banner_1717400300a84dfc29c5b29db468165d70b55ec6b0.jpg',
        primary: 'Mua ngay',
        secondary: 'Xem bộ sưu tập'
    },
    {
        eyebrow: 'NEW GENERATION DEVICES',
        title: 'Hiệu năng mạnh mẽ',
        highlight: 'thiết kế dẫn đầu',
        desc: 'Sở hữu các dòng laptop mới nhất với màn hình sắc nét, pin bền bỉ và hiệu suất vượt trội.',
        img: 'https://storage-asset.msi.com/global/picture/banner/banner_1714115162a4d3fcb62d888f4e2f69e6b3eb900d70.jpeg',
        primary: 'Khám phá ngay',
        secondary: 'Tư vấn cấu hình'
    },
    {
        eyebrow: 'GAMING • CREATOR • BUSINESS',
        title: 'Công nghệ mới cho',
        highlight: 'trải nghiệm không giới hạn',
        desc: 'Cân mọi tác vụ từ chơi game AAA, dựng video 4K đến làm việc doanh nghiệp với độ ổn định tối ưu.',
        img: 'https://storage-asset.msi.com/global/picture/banner/banner_1713254924c871587595c25cfbb28f73b64c0527ea.jpg',
        primary: 'Xem ưu đãi',
        secondary: 'So sánh sản phẩm'
    }
]

const categories = ref([])

const getCategoryFallbackImage = (catName) => {
    const name = catName.toLowerCase();
    if (name.includes('gaming')) return 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=600';
    if (name.includes('văn phòng') || name.includes('office')) return 'https://images.unsplash.com/photo-1497215848906-c405a3c48a73?w=600';
    if (name.includes('macbook') || name.includes('apple')) return 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600';
    if (name.includes('đồ họa') || name.includes('design')) return 'https://images.unsplash.com/photo-1626218174358-7769486c4b79?w=600';
    if (name.includes('sinh viên') || name.includes('học sinh')) return 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=600';
    return 'https://images.unsplash.com/photo-1531297172864-d6211832077e?w=600';
};


const mapProducts = (rawProducts) => {
    const productVariants = rawProducts.map(p => {
        if (!p.bien_thes || p.bien_thes.length === 0) {
            return [{
                id: p.id_sanpham,
                key_id: String(p.id_sanpham),
                name: p.tenSP,
                fullName: p.tenSP,
                category: p.danh_muc?.ten_danhmuc || 'Sản phẩm',
                id_danhmuc: String(p.id_danhmuc || ''),
                id_thuonghieu: String(p.id_thuonghieu || ''),
                brandName: p.thuong_hieu?.ten_thuonghieu || '',
                weight: p.khoiluong,
                priceNum: 0,
                oldPriceNum: 0,
                specs: [],
                img: p.hinhanh ? storageUrl(p.hinhanh) : 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=500',
                badge: p.trangthai === 'Hot' ? 'HOT' : (p.trangthai === 'Mới' ? 'NEW' : ''),
                badgeColor: p.trangthai === 'Hot' ? '#dc2626' : '#2563eb'
            }];
        }

        return p.bien_thes.map(bt => {
            let ram = '', cpu = '', gpu = '', kichthuoc = '', dophan = '', tamnen = '', pin = '', sac = '', mausac = '';
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
                    else if (ten === 'màu sắc' || ten === 'màu') mausac = attr.giatri;
                });
            }

            // Lấy thông số kỹ thuật chung của sản phẩm (không phải biến thể)
            let generalSpecs = [];
            try {
                const tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || []);
                if (Array.isArray(tskt)) {
                    generalSpecs = tskt.map(item => item.giatri).filter(Boolean);
                }
            } catch (e) { console.error('Lỗi parse thong_so_ky_thuat:', e); }

            const fullName = [p.tenSP, ...generalSpecs].join(' ');
            
            const specs = [
                { label: 'RAM', value: ram },
                { label: 'CPU', value: cpu },
                { label: 'Màu', value: mausac }
            ].filter(s => s.value);

            return {
                id: p.id_sanpham,
                key_id: String(bt.id_bienthe),
                name: p.tenSP,
                fullName: fullName,
                category: p.danh_muc?.ten_danhmuc || 'Sản phẩm',
                id_danhmuc: String(p.id_danhmuc || ''),
                id_thuonghieu: String(p.id_thuonghieu || ''),
                brandName: p.thuong_hieu?.ten_thuonghieu || '',
                weight: p.khoiluong,
                priceNum: bt.gia || 0,
                oldPriceNum: bt.gia_khuyen_mai || 0,
                price: bt.gia > 0 ? new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(bt.gia) : 'Liên hệ',
                specs: specs,
                img: bt.hinhanh ? storageUrl(bt.hinhanh) : (p.hinhanh ? storageUrl(p.hinhanh) : 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500'),
                badge: p.trangthai === 'Hot' ? 'HOT' : (p.trangthai === 'Mới' ? 'NEW' : ''),
                badgeColor: p.trangthai === 'Hot' ? '#dc2626' : '#2563eb'
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


const INITIAL_MOCK_PRODUCTS = [
    {
        id: 1, key_id: 'mock1', fullName: "Laptop ASUS Vivobook S16 S3607VA-RP056W",
        priceNum: 22800000, price: '22.800.000 ₫',
        img: "https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=500",
        brandName: "Asus", weight: 2.5,
        specs: [{label: 'RAM', value: '32GB'}]
    },
    {
        id: 2, key_id: 'mock2', fullName: "Laptop ASUS TUF Gaming F16 FX608JHR-RV037W",
        priceNum: 19700000, price: '19.700.000 ₫',
        img: "https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500",
        brandName: "Asus", weight: 2.5,
        specs: [{label: 'RAM', value: '64GB'}, {label: 'CPU', value: 'Ryzen 7 7800X3D'}]
    },
    {
        id: 3, key_id: 'mock3', fullName: "MacBook Pro 14 M5 Pro Apple M3 GPU 1TB 14 inch",
        priceNum: 33800000, price: '33.800.000 ₫',
        img: "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500",
        brandName: "Apple", weight: 2.5,
        specs: [{label: 'RAM', value: '32GB'}, {label: 'CPU', value: 'Apple M2 Ultra'}]
    },
    {
        id: 4, key_id: 'mock4', fullName: "Laptop HP 15 RTX 4080 512GB 14 inch 2K OLED",
        priceNum: 22800000, price: '22.800.000 ₫',
        img: "https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500",
        brandName: "HP", weight: 2.5,
        specs: [{label: 'RAM', value: '8GB'}, {label: 'CPU', value: 'Intel Core i5'}]
    },
    {
        id: 5, key_id: 'mock5', fullName: "MacBook Air M4 RX 7800 XT 512GB 16 inch 2K",
        priceNum: 32800000, price: '32.800.000 ₫',
        img: "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500",
        brandName: "Apple", weight: 2.07,
        specs: [{label: 'RAM', value: '16GB'}, {label: 'CPU', value: 'Apple M2 Ultra'}]
    }
];
const featuredProducts = ref([...INITIAL_MOCK_PRODUCTS])
const latestNews = ref([])
const newsPlaceholderImage = 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800'

const newsImageUrl = (path) => {
    if (!path) return newsPlaceholderImage
    if (path.startsWith('http')) return path
    return `http://127.0.0.1:8000/storage/${path}`
}

const loadCache = () => {
    try {
        const cached = localStorage.getItem('nextgen_home_cache')
        if (cached) {
            const parsed = JSON.parse(cached)
            if (parsed.featuredProducts) featuredProducts.value = parsed.featuredProducts
            if (parsed.categories) categories.value = parsed.categories
            if (parsed.latestNews) latestNews.value = parsed.latestNews
        }
    } catch (e) {
        console.error('Lỗi load cache trang chủ:', e)
    }
}

const saveCache = () => {
    try {
        localStorage.setItem('nextgen_home_cache', JSON.stringify({
            featuredProducts: featuredProducts.value,
            categories: categories.value,
            latestNews: latestNews.value
        }))
    } catch (e) {
        console.error('Lỗi save cache trang chủ:', e)
    }
}

const initScrollReveal = () => {
    const reveals = document.querySelectorAll('.scroll-reveal')
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active')
                observer.unobserve(entry.target)
            }
        })
    }, {
        threshold: 0.05,
        rootMargin: '0px 0px -30px 0px'
    })
    reveals.forEach(el => observer.observe(el))
}

onMounted(async () => {
    // Tải cache ngay lập tức để hiển thị tức thì cho người dùng
    loadCache()

    // Khởi chạy scroll reveal sớm với dữ liệu cache đã kết xuất
    setTimeout(initScrollReveal, 120)

    setTimeout(() => {
        console.log('[Home.vue] 5s timer reached. showGift = true');
        showGift.value = true
    }, 5000)

    try {
        // Gọi song song toàn bộ API lấy dữ liệu ngầm
        const [newsRes, spRes, catRes] = await Promise.all([
            api.get('/news', { params: { scope: 'public', per_page: 3 } }),
            api.get('/sanpham'),
            api.get('/danhmuc')
        ])

        latestNews.value = newsRes.data?.data || []
        const allProducts = mapProducts(spRes.data)
        featuredProducts.value = allProducts.slice(0, 20)
        categories.value = (catRes.data?.data || catRes.data || []).slice(0, 4)

        // Lưu cache mới nhất để dùng cho lần chuyển trang sau
        saveCache()

        // Khởi chạy lại scroll reveal để lắng nghe các phần tử mới từ API
        setTimeout(initScrollReveal, 180)
    } catch (error) {
        console.error('Lỗi khi tải dữ liệu trang chủ:', error)
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

    const variantId = product.key_id || product.id_bienthe
    if (!variantId) {
        swal.error('Thông báo', 'Không xác định được cấu hình sản phẩm, vui lòng thử lại!')
        return
    }

    try {
        await api.post('/yeu-thich/them', {
            id_bienthe: variantId,
            soluong: 1
        })

        swal.success('Thành công', `Đã thêm ${product.fullName || product.name} vào danh sách yêu thích! ❤️`)
        window.dispatchEvent(new Event('wishlist-updated'))
    } catch (err) {
        swal.error('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra!')
    }
}

const formatPrice = (p) => new Intl.NumberFormat('vi-VN').format(p) + 'đ'

const themVaoGioHang = async (product) => {
    const token = getToken()
    if (!token) {
        swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!')
        localStorage.setItem('pendingCartItem', JSON.stringify({
            id_bienthe: product.key_id,
            soluong: 1
        }))
        router.push('/login')
        return
    }

    try {
        await api.post('/gio-hang/them', {
            id_bienthe: product.key_id,
            soluong: 1
        }, {
            headers: { Authorization: `Bearer ${token}` }
        })

        swal.success('Thành công', `Đã thêm ${product.fullName || product.name} vào giỏ hàng!`)
        window.dispatchEvent(new Event('cart-updated'))

    } catch (err) {
        console.error('Lỗi thêm giỏ hàng:', err)
        swal.error('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra, không thể thêm vào giỏ hàng!')
    }
}


const benefits = [
    { icon: '✔️', title: '100% chính hãng', desc: 'Cam kết sản phẩm mới, nguyên seal, đầy đủ chứng từ.' },
    { icon: '🛡️', title: 'Bảo hành toàn diện', desc: 'Hỗ trợ bảo hành nhanh, chính sách đổi trả rõ ràng.' },
    { icon: '💳', title: 'Trả góp linh hoạt', desc: 'Trả góp 0%, hồ sơ đơn giản, duyệt nhanh chóng.' },
    { icon: '🚚', title: 'Giao hàng toàn quốc', desc: 'Đóng gói an toàn, giao nhanh, hỗ trợ kiểm tra hàng.' }
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
const activeSlide = computed(() => slides[current.value] || {})
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
                        <img :src="activeSlide.img" alt="" />
                        <div class="hero-slide-overlay"></div>
                    </div>
                </transition>

                <div class="hero-inner">
                    <transition name="fade-slide" mode="out-in">
                        <div class="hero-content" :key="current">
                            <div class="hero-left">
                                <span class="hero-eyebrow">
                                    <span class="eyebrow-dot"></span>
                                    {{ activeSlide.eyebrow }}
                                </span>
                                <h1>
                                    {{ activeSlide.title }}
                                    <span>{{ activeSlide.highlight }}</span>
                                </h1>
                                <p>{{ activeSlide.desc }}</p>
                                <div class="hero-actions">
                                    <button class="btn btn-primary">{{ activeSlide.primary }}</button>
                                    <button class="btn btn-secondary">{{ activeSlide.secondary }}</button>
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
                                    <img :src="activeSlide.img" :alt="activeSlide.title" @error="$event.target.src='https://images.unsplash.com/photo-1497215848906-c405a3c48a73?w=800'" />
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
            <div class="container stats-grid scroll-reveal reveal-stagger">
                <div class="stat-card" v-for="(item, i) in stats" :key="i">
                    <h3>{{ item.value }}</h3>
                    <p>{{ item.label }}</p>
                </div>
            </div>
        </section>

        
        <!-- CATEGORY -->
        <section class="section">
            <div class="container">
                <div class="section-head scroll-reveal reveal-fade-up">
                    <div>
                        <span class="section-label">DANH MỤC</span>
                        <h2>Lựa chọn đúng dòng laptop cho bạn</h2>
                        <p>Thiết kế tối giản, cấu hình mạnh mẽ, tối ưu theo từng nhu cầu sử dụng.</p>
                    </div>
                    <router-link to="/products" class="section-link">Xem tất cả →</router-link>
                </div>
                
            <div class="container">
                <div class="category-slider-wrapper scroll-reveal reveal-fade-up">
                    <div class="category-slider">
                        <div class="cat-img-card" v-for="(c, idx) in categories" :key="c.id_danhmuc" 
                            @click="router.push(`/products?cat=${c.id_danhmuc}`)"
                            :style="{ backgroundImage: 'url(' + (c.hinhanh ? storageUrl(c.hinhanh) : getCategoryFallbackImage(c.ten_danhmuc)) + ')' }">
                            <div class="cat-img-overlay"></div>
                            <div class="cat-img-content">
                                <h3>{{ c.ten_danhmuc }}</h3>
                                <p>{{ c.mota || 'Khám phá ngay' }}</p>
                                <span class="btn-explore">Xem thêm ➔</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </section>

        
        
        <!-- COMPARE MODELS -->
        <section class="section compare-section">
            <div class="container">
                <div class="section-head center scroll-reveal reveal-fade-up">
                    <div>
                        <span class="section-label">TÌM KIẾM THEO NHU CẦU</span>
                        <h2>Lựa chọn cấu hình phù hợp với bạn</h2>
                        <p>Từ xử lý văn bản, sáng tạo nội dung đến đắm chìm trong thế giới ảo.</p>
                    </div>
                </div>
                <div class="compare-grid scroll-reveal reveal-stagger">
                    <div class="compare-card">
                        <img src="https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=400" alt="Office Laptop" class="compare-img" />
                        <h3>Office & Student</h3>
                        <p class="compare-desc">Gọn nhẹ, pin trâu, bền bỉ.</p>
                        <ul class="compare-specs">
                            <li>Intel Core i5 / AMD Ryzen 5</li>
                            <li>RAM 8GB - 16GB</li>
                            <li>Trọng lượng < 1.5kg</li>
                        </ul>
                        <button class="btn btn-outline">Xem ngay</button>
                    </div>
                    <div class="compare-card highlight">
                        <div class="compare-badge">Bán chạy nhất</div>
                        <img src="https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=400" alt="Gaming Laptop" class="compare-img" />
                        <h3>Gaming Series</h3>
                        <p class="compare-desc">Tản nhiệt tối ưu, tần số quét cao.</p>
                        <ul class="compare-specs">
                            <li>RTX 4050 - RTX 4090</li>
                            <li>Màn hình 144Hz - 240Hz</li>
                            <li>Tản nhiệt chất lỏng / buồng hơi</li>
                        </ul>
                        <button class="btn btn-primary">Khám phá</button>
                    </div>
                    <div class="compare-card">
                        
                        <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400" alt="Creator Laptop" class="compare-img" />\n                        <h3>Creator Pro</h3>
                        <p class="compare-desc">Chuẩn màu tuyệt đối, hiệu năng render.</p>
                        <ul class="compare-specs">
                            <li>Màn hình OLED / 100% DCI-P3</li>
                            <li>RAM 32GB - 64GB</li>
                            <li>Card đồ họa Studio</li>
                        </ul>
                        <button class="btn btn-outline">Xem ngay</button>
                    </div>
                </div>
            </div>
        </section>
<!-- SETUP SHOWCASE GALLERY -->
        <section class="section showcase-section">
            <div class="container">
                <div class="section-head center scroll-reveal reveal-fade-up">
                    <div>
                        <span class="section-label">INSPIRATION</span>
                        <h2>Góc Làm Việc Thực Tế</h2>
                        <p>Lấy cảm hứng từ những góc setup công nghệ cao cấp nhất của cộng đồng.</p>
                    </div>
                </div>
                <div class="showcase-grid scroll-reveal reveal-stagger">
                    <div class="showcase-item large">
                        <img src="https://images.unsplash.com/photo-1593640408182-31c70c8268f5?w=800" alt="Setup 1" />
                        <div class="showcase-overlay">
                            <h4>Minimalist Workspace</h4>
                            <p>Không gian tối giản, hiệu năng tối đa</p>
                        </div>
                    </div>
                    <div class="showcase-item">
                        <img src="https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=600" alt="Setup 2" />
                        <div class="showcase-overlay">
                            <h4>Macbook Pro Setup</h4>
                        </div>
                    </div>
                    <div class="showcase-item">
                        <img src="https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=600" alt="Setup 3" />
                        <div class="showcase-overlay">
                            <h4>Dual Monitor Gaming</h4>
                        </div>
                    </div>
                    
                </div>
            </div>
        </section>
<!-- FEATURED — 20 sản phẩm slider chia 5 cột -->
        <section class="section featured-section">
            <div class="container">
                <div class="section-head center scroll-reveal reveal-fade-up">
                    <div>
                        <span class="section-label">SẢN PHẨM NỔI BẬT</span>
                        <h2>Những mẫu laptop bán chạy nhất nhất</h2>
                        <p>Chọn lọc từ các dòng máy bán chạy với hiệu năng tốt, thiết kế đẹp và giá trị cao.</p>
                    </div>
                </div>

                <div class="product-slider-container scroll-reveal reveal-scale">
                    <button class="slider-btn prev" @click="prevFeaturedPage" :disabled="currentProductPage === 0">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>

                    <Transition name="slider-fade" mode="out-in">
                        <div class="product-slider-grid" :key="currentProductPage">
                            <article class="product-card" v-for="p in visibleFeaturedProducts" :key="p.key_id">
                                <span class="product-badge" v-if="p.badge" :style="{ background: p.badgeColor }">{{ p.badge }}</span>
                                <div class="product-thumb" @click="router.push(`/products/${p.id}?variant=${p.key_id}`)">
                                    <img :src="p.img" :alt="p.fullName" @error="$event.target.src='https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500'" />
                                </div>
                                <div class="product-body">
                                    <h3 @click="router.push(`/products/${p.id}?variant=${p.key_id}`)" :title="p.fullName">
                                        {{ p.fullName }}
                                    </h3>
                                    <p class="brand-txt">{{ p.brandName }} {{ p.weight ? '· ' + p.weight + 'kg' : '' }}</p>

                                    <!-- KHUNG THÔNG SỐ -->
                                    <div class="specs-box" v-if="p.specs && p.specs.length > 0">
                                        <div class="spec-item" v-for="s in p.specs" :key="s.label">
                                            <span class="spec-label">{{ s.label }}:</span>
                                            <span class="spec-value">{{ s.value }}</span>
                                        </div>
                                    </div>

                                    <div class="price-row">
                                        <span class="price" v-if="p.priceNum > 0">{{ formatPrice(p.priceNum) }}</span>
                                        <span class="price" v-else>Liên hệ</span>
                                        <span v-if="p.oldPriceNum > p.priceNum" class="old-price">{{ formatPrice(p.oldPriceNum) }}</span>
                                    </div>

                                    <div class="product-actions">
                                        <router-link :to="`/products/${p.id}?variant=${p.key_id}`" class="btn-detail">
                                            Chi tiết
                                        </router-link>

                                        <button class="btn-wishlist-circle" title="Yêu thích" @click="themVaoYeuThich(p)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                            </svg>
                                        </button>

                                        <button class="btn-cart-circle" title="Thêm vào giỏ hàng" @click.stop="themVaoGioHang(p)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="9" cy="21" r="1"></circle>
                                                <circle cx="20" cy="21" r="1"></circle>
                                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </Transition>

                    <button class="slider-btn next" @click="nextFeaturedPage"
                        :disabled="currentProductPage >= totalProductPages - 1">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>

                <div class="see-all-container">
                    <router-link to="/products" class="btn see-all-btn">
                        Xem tất cả sản phẩm →
                    </router-link>
                </div>
            </div>
        </section>

        
        <!-- ACCESSORIES SPOTLIGHT -->
        <section class="section accessories-section">
            <div class="container">
                <div class="split-layout scroll-reveal reveal-fade-up">
                    <div class="split-image">
                        <img src="https://images.unsplash.com/photo-1595225476474-87563907a212?w=800" alt="Mechanical Keyboard" />
                    </div>
                    <div class="split-content">
                        <span class="section-label">PHỤ KIỆN CAO CẤP</span>
                        <h2>Trang bị hoàn hảo cho cỗ máy của bạn</h2>
                        <p>Từ bàn phím cơ độ nhạy cao, chuột gaming siêu nhẹ đến tai nghe chuẩn studio. Tối đa hóa trải nghiệm của bạn với các phụ kiện thiết yếu.</p>
                        <ul class="acc-list">
                            <li><strong>Bàn phím cơ:</strong> Phản hồi xúc giác cực nhạy, RGB đa sắc.</li>
                            <li><strong>Chuột không dây:</strong> Trọng lượng siêu nhẹ, mắt đọc chính xác cao.</li>
                            <li><strong>Giá đỡ laptop:</strong> Tản nhiệt hiệu quả, nâng tầm góc nhìn.</li>
                        </ul>
                        <button class="btn btn-primary" style="margin-top:20px;">Khám phá Phụ kiện</button>
                    </div>
                </div>
            </div>
        </section>

        
        <!-- INTERACTIVE HOTSPOT -->
        <section class="section hotspot-section scroll-reveal reveal-fade-up">
            <div class="container">
                <div class="hotspot-container">
                    <img src="https://images.unsplash.com/photo-1547394765-185e1e68f34e?w=1200" alt="Interactive Setup" class="hotspot-main-img" />
                    
                    <div class="hotspot-point" style="top: 35%; left: 45%;">
                        <div class="hotspot-pulse"></div>
                        <div class="hotspot-tooltip">
                            <strong>Màn hình UltraWide</strong>
                            <p>Độ cong 1500R, 144Hz</p>
                        </div>
                    </div>
                    
                    <div class="hotspot-point" style="top: 70%; left: 35%;">
                        <div class="hotspot-pulse"></div>
                        <div class="hotspot-tooltip">
                            <strong>Bàn phím Cơ Custom</strong>
                            <p>Switch Linear, RGB 16.8M</p>
                        </div>
                    </div>

                    <div class="hotspot-point" style="top: 65%; left: 60%;">
                        <div class="hotspot-pulse"></div>
                        <div class="hotspot-tooltip">
                            <strong>Chuột Gaming Siêu nhẹ</strong>
                            <p>Cảm biến 26.000 DPI</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
<!-- HARDWARE PARALLAX -->
        <section class="hardware-parallax scroll-reveal reveal-scale">
            <div class="hw-bg"></div>
            <div class="hw-content container">
                <h2>Sức Mạnh Ẩn Giấu Bên Trong</h2>
                <p>Khám phá công nghệ lõi và cấu trúc tản nhiệt làm nên sức mạnh thực sự của các siêu phẩm.</p>
                <button class="btn btn-light" style="margin-top:20px;">Tìm hiểu thêm</button>
            </div>
        </section>
<!-- PROMO -->
        <section class="promo">
            <div class="container promo-box scroll-reveal reveal-scale">
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

        
        <!-- BENTO GRID BENEFITS -->
        <section class="section bento-section">
            <div class="container">
                <div class="section-head center scroll-reveal reveal-fade-up">
                    <div>
                        <span class="section-label">LÝ DO CHỌN CHÚNG TÔI</span>
                        <h2>Dịch vụ xứng tầm một hệ thống cao cấp</h2>
                    </div>
                </div>
                
                <div class="bento-grid scroll-reveal reveal-stagger">
                    <div class="bento-card bento-large bento-shipping">
                        <div class="bento-content">
                            <h3>Giao hàng hỏa tốc 2H</h3>
                            <p>Nhận máy ngay trong ngày tại nội thành. An toàn, nguyên seal, kiểm tra trực tiếp.</p>
                        </div>
                        <img src="https://images.unsplash.com/photo-1580674285054-bed31e145f59?w=600" alt="Shipping" />
                    </div>
                    
                    <div class="bento-card bento-small bento-warranty">
                        <div class="bento-icon">🛡️</div>
                        <div class="bento-content">
                            <h3>Bảo hành 24 Tháng</h3>
                            <p>Đổi mới 30 ngày đầu.</p>
                        </div>
                    </div>

                    <div class="bento-card bento-small bento-finance">
                        <div class="bento-icon">💳</div>
                        <div class="bento-content">
                            <h3>Trả góp 0%</h3>
                            <p>Không phí ẩn, duyệt hồ sơ online.</p>
                        </div>
                    </div>
                    
                    <div class="bento-card bento-medium bento-trade">
                        <div class="bento-content">
                            <h3>Thu cũ đổi mới - Trợ giá 2 Triệu</h3>
                            <p>Lên đời siêu phẩm với mức giá tiết kiệm nhất.</p>
                        </div>\n                        <img src="https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=600" alt="Trade In" />
                        
                    </div>
                </div>
            </div>
        </section>


        
        <!-- CINEMATIC VIDEO TEASER -->
        <section class="section video-teaser scroll-reveal reveal-scale">
            <div class="container">
                <div class="video-container">
                    <video class="video-bg" autoplay loop muted playsinline>
                          <source src="https://assets.mixkit.co/videos/preview/mixkit-software-developer-working-on-code-41695-large.mp4" type="video/mp4" />
                          <source src="https://www.w3schools.com/html/mov_bbb.mp4" type="video/mp4" />
                      </video>
                    <div class="video-overlay">
                        
                        <h2>NextGen Laptop Experience 2026</h2>
                        <p>Xem video để khám phá không gian mua sắm tương lai</p>
                    </div>
                </div>
            </div>
        </section>
<!-- NEWS -->
        <section class="section soft-bg">
            <div class="container">
                <div class="section-head scroll-reveal reveal-fade-up">
                    <div>
                        <span class="section-label">BLOG & TIN TỨC</span>
                        <h2>Cập nhật xu hướng công nghệ mới nhất</h2>
                        <p>Thông tin hữu ích giúp bạn chọn đúng laptop và khai thác hiệu quả hơn.</p>
                    </div>
                    <RouterLink to="/news" class="section-link">Xem thêm →</RouterLink>
                </div>
                <div class="news-grid scroll-reveal reveal-stagger">
                    <article class="news-card" v-for="n in latestNews" :key="n.id">
                        <div class="news-thumb"><img :src="newsImageUrl(n.image)" :alt="n.image_alt || n.title" /></div>
                        <div class="news-body">
                            <span class="news-tag">{{ n.category }}</span>
                            <h3>{{ n.title }}</h3>
                            <p>{{ n.excerpt || 'Đọc bài viết để xem nội dung chi tiết.' }}</p>
                            <RouterLink :to="`/news/${n.id}`">Đọc thêm →</RouterLink>
                        </div>
                    </article>
                    <div v-if="latestNews.length === 0" class="news-empty">Chưa có bài viết nào.</div>
                </div>
            </div>
        </section>

        <!-- REVIEWS -->
        <section class="section">
            <div class="container">
                <div class="section-head center scroll-reveal reveal-fade-up">
                    <div>
                        <span class="section-label">KHÁCH HÀNG NÓI GÌ</span>
                        <h2>Niềm tin của khách hàng là giá trị lớn nhất</h2>
                        <p>Trải nghiệm mua sắm cao cấp, tư vấn tận tâm và dịch vụ hậu mãi chuyên nghiệp.</p>
                    </div>
                </div>
                <div class="review-grid scroll-reveal reveal-stagger">
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
            <div class="container cta-box scroll-reveal reveal-scale">
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

/* Style cho thẻ sản phẩm đã được dời xuống phần dưới */

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
    padding: 0;
    background: var(--bg);
}

/* ─── HERO ─── */
.hero {
    position: relative;
    min-height: 82vh;
    display: flex;
    align-items: center;
    overflow: hidden;
    padding: 84px 48px 64px;
    border-radius: 0;
}

.hero-slide-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
    border-radius: 0;
    overflow: hidden;
}

.hero-slide-bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    animation: kenburn 8s ease-out forwards;
    border-radius: 0;
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
    border-radius: 0;
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

.slider-btn.prev svg {
    margin-right: 2px;
    width: 22px;
    height: 22px;
}

.slider-btn.next svg {
    margin-left: 2px;
    width: 22px;
    height: 22px;
}

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

/* ─── PRODUCT CARD (SYNC WITH PRODUCT PAGE) ─── */
.product-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    overflow: hidden;
    position: relative;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 36px rgba(0, 0, 0, 0.1);
}

.product-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 9px;
    border-radius: 6px;
    z-index: 1;
}

.product-thumb {
    background: #f8fafc;
    padding: 14px;
    cursor: pointer;
}

.product-thumb img {
    width: 100%;
    height: 148px;
    object-fit: cover;
    border-radius: 10px;
}

.product-body {
    padding: 13px 15px 15px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-body h3 {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px;
    cursor: pointer;
    line-height: 1.45;
    height: 58px; /* Fits exactly 3 lines of text */
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.brand-txt {
    font-size: 11px;
    color: #94a3b8;
    margin: 0 0 10px;
    height: 16px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.specs-box {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
    height: 48px; /* Fixed height for two rows of badges */
    align-content: flex-start;
    overflow: hidden;
}

.spec-item {
    padding: 3px 8px;
    background: #f1f5f9;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.spec-label {
    font-size: 10px;
    color: #64748b;
    font-weight: 600;
}

.spec-value {
    font-size: 10px;
    color: #0f172a;
    font-weight: 700;
}

.price-row {
    margin-top: auto; /* Push price and action buttons to the absolute bottom of the card */
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 12px;
}

.price {
    font-size: 15px;
    font-weight: 800;
    color: #2563eb;
}

.old-price {
    font-size: 11px;
    color: #cbd5e1;
    text-decoration: line-through;
}

.product-actions {
    display: flex;
    gap: 8px;
}

.btn-detail {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1.5px solid #2563eb;
    background: white;
    color: #2563eb;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    font-family: 'Inter', sans-serif;
    transition: all 0.2s;
}

.btn-detail:hover {
    background: #2563eb;
    color: white;
}

.btn-wishlist-circle, .btn-cart-circle {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.2s, transform 0.2s;
    flex-shrink: 0;
}

.btn-cart-circle {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
}

.btn-wishlist-circle {
    background: white;
    border: 1.5px solid #ff4d4f;
    color: #ff4d4f;
}

.btn-wishlist-circle:hover, .btn-cart-circle:hover {
    opacity: 0.9;
    transform: scale(1.06);
}

.btn-wishlist-circle svg, .btn-cart-circle svg {
    width: 14px;
    height: 14px;
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

.news-empty {
    grid-column: 1 / -1;
    padding: 24px;
    border: 1px dashed var(--border);
    border-radius: 12px;
    color: var(--text2);
    background: var(--bg-white);
    text-align: center;
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

/* ─── SCROLL REVEAL EFFECTS ─── */
.scroll-reveal {
    opacity: 0;
    transform: translateY(50px);
    transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: transform, opacity;
}

.scroll-reveal.reveal-fade-up {
    transform: translateY(60px);
}

.scroll-reveal.reveal-scale {
    transform: scale(0.96) translateY(30px);
}

.scroll-reveal.active {
    opacity: 1;
    transform: translateY(0) scale(1);
}

/* Stagger delay for grid items */
.reveal-stagger > * {
    opacity: 0;
    transform: translateY(35px);
    transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

.scroll-reveal.reveal-stagger.active > * {
    opacity: 1;
    transform: translateY(0);
}

.scroll-reveal.reveal-stagger.active > *:nth-child(1) { transition-delay: 0.08s; }
.reveal-stagger.active > *:nth-child(2) { transition-delay: 0.16s; }
.reveal-stagger.active > *:nth-child(3) { transition-delay: 0.24s; }
.reveal-stagger.active > *:nth-child(4) { transition-delay: 0.32s; }
.reveal-stagger.active > *:nth-child(5) { transition-delay: 0.40s; }
.reveal-stagger.active > *:nth-child(6) { transition-delay: 0.48s; }

/* PC BUILD BANNER */
.pc-float-img:hover {
    transform: rotate(0deg) scale(1.05);
}

/* BENTO GRID ACCESSORIES */
.bento-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-template-rows: 280px 280px;
    gap: 24px;
}
.bento-item {
    border-radius: 24px;
    overflow: hidden;
    position: relative;
    background-size: cover;
    background-position: center;
    background-color: #1e293b;
    color: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}
.bento-item::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.1) 60%, rgba(0,0,0,0) 100%);
    transition: 0.4s;
}
.bento-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12);
}
.bento-item:hover::before {
    background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.3) 100%);
}
.bento-content {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 32px;
    z-index: 2;
    transform: translateY(10px);
    transition: 0.4s;
}
.bento-item:hover .bento-content {
    transform: translateY(0);
}
.bento-content h3 {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 8px;
    color: white;
}
.bento-content p {
    font-size: 15px;
    color: #cbd5e1;
    margin-bottom: 12px;
    opacity: 0.8;
}
.bento-link {
    display: inline-block;
    color: #60a5fa;
    font-weight: 700;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0;
    transform: translateX(-10px);
    transition: 0.4s;
}
.bento-item:hover .bento-link {
    opacity: 1;
    transform: translateX(0);
}

/* Grid Layouts */
.bento-item.large {
    grid-column: span 2;
    grid-row: span 2;
}
.bento-item.wide {
    grid-column: span 2;
}

/* Background Images */
.b-keyboard { background-image: url('https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=800'); }
.b-mouse { background-image: url('https://images.unsplash.com/photo-1625842268584-8f3296236761?w=600'); }
.b-headset { background-image: url('https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600'); }
.b-monitor { background-image: url('https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600'); }

@media (max-width: 991px) {
    .bento-grid {
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: auto;
    }
    .bento-item { height: 280px; }
    .bento-item.large { grid-column: span 2; grid-row: span 1; }
    .bento-item.wide { grid-column: span 2; }
    .bento-content { transform: translateY(0); }
    .bento-link { opacity: 1; transform: translateX(0); }
}


/* EXPANDED BENTO GRID STYLES */
.bento-grid {
    grid-template-rows: 280px 280px 280px; /* 3 rows now */
}
.b-desk { background-image: url('https://images.unsplash.com/photo-1518444065439-e93166278d10?w=800'); }
.b-backpack { background-image: url('https://images.unsplash.com/photo-1622560480605-d83c853bc5c3?w=600'); }
.b-gamepad { background-image: url('https://images.unsplash.com/photo-1600080972464-8e5f35f63d08?w=600'); }
.b-speaker { background-image: url('https://images.unsplash.com/photo-1545454675-3531b543be5d?w=600'); }


/* CATEGORY SLIDER */
.category-slider-wrapper {
    width: 100%;
    position: relative;
    padding: 10px 0;
}
.category-slider {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    padding-bottom: 10px;
}


.category-slider .cat-img-card {
    height: 220px; /* Width handled by CSS Grid */
    border-radius: 20px;
    background-size: contain; background-repeat: no-repeat; background-color: #0f172a;
    background-position: center;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    transition: 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
    
}

@media (max-width: 1024px) {
    .category-slider { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .category-slider { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .category-slider { grid-template-columns: repeat(1, 1fr); }
}

.category-slider .cat-img-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(15,23,42,0.8) 0%, rgba(15,23,42,0.1) 40%, rgba(15,23,42,0) 100%);
    transition: 0.4s;
}
.category-slider .cat-img-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(59,130,246,0.2);
    border: 1px solid rgba(59,130,246,0.3);
}
.category-slider .cat-img-card:hover .cat-img-overlay {
    background: linear-gradient(to top, rgba(15,23,42,0.9) 0%, rgba(59,130,246,0.3) 100%);
}
.category-slider .cat-img-content {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    padding: 20px 24px;
    z-index: 2;
    color: white;
    transform: translateY(20px);
    transition: 0.4s ease-out;
}
.category-slider .cat-img-card:hover .cat-img-content {
    transform: translateY(0);
}
.category-slider .cat-img-content h3 {
    font-size: 20px; font-weight: 800; margin-bottom: 8px; color: white;
    text-shadow: 0 2px 10px rgba(0,0,0,0.5);
}
.category-slider .cat-img-content p {
    font-size: 14px; color: #cbd5e1; margin-bottom: 12px; opacity: 0; transition: 0.4s;
}
.category-slider .cat-img-card:hover .cat-img-content p {
    opacity: 1;
}
.category-slider .btn-explore {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: white;
    color: #0f172a;
    padding: 8px 20px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 13px;
    opacity: 0;
    transform: translateY(10px);
    transition: 0.4s delay-100ms;
}
.category-slider .cat-img-card:hover .btn-explore {
    opacity: 1;
    transform: translateY(0);
}


/* PREMIUM PRODUCT CARD STYLES */
.product-card {
    border-radius: 20px !important;
    border: 1px solid #f1f5f9;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03) !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    background: #ffffff;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.product-card:hover {
    transform: translateY(-8px) !important;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08) !important;
    border-color: #e2e8f0;
}
.product-thumb {
    height: 180px;
    padding: 0;
    background: #ffffff;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.product-thumb img {
    display: block;
    height: 100%;
    width: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) !important;
}
.product-card:hover .product-thumb img {
    transform: scale(1.1) !important;
}
.product-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.btn-detail {
    background: #f1f5f9 !important;
    color: #1e293b !important;
    border-radius: 12px !important;
    transition: 0.3s !important;
}
.btn-detail:hover {
    background: #3b82f6 !important;
    color: white !important;
}

.price-row { margin-top: auto !important; }

/* ─── NEW LANDING PAGE SECTIONS ─── */
.showcase-section { padding-bottom: 20px; }
.showcase-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-auto-rows: 250px;
    gap: 20px;
}
.showcase-item {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
}
.showcase-item.large {
    grid-column: span 2;
    grid-row: span 2;
}
.showcase-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.showcase-item:hover img {
    transform: scale(1.05);
}
.showcase-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 30px 20px 20px;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: white;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
}
.showcase-item:hover .showcase-overlay {
    opacity: 1;
    transform: translateY(0);
}
.showcase-overlay h4 { margin: 0 0 5px; font-size: 18px; font-weight: 700; }
.showcase-overlay p { margin: 0; font-size: 14px; opacity: 0.8; }

.split-layout {
    display: flex;
    align-items: center;
    gap: 60px;
    background: #ffffff;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 15px 30px rgba(0,0,0,0.03);
}
.split-image { flex: 1; border-radius: 16px; overflow: hidden; }
.split-image img { width: 100%; height: auto; display: block; transition: transform 0.5s ease; }
.split-image:hover img { transform: scale(1.03); }
.split-content { flex: 1; }
.split-content h2 { margin-bottom: 20px; font-size: 32px; font-weight: 800; color: var(--navy); }
.split-content p { color: var(--text2); line-height: 1.7; margin-bottom: 20px; font-size: 16px; }
.acc-list { list-style: none; padding: 0; margin: 0; }
.acc-list li { margin-bottom: 12px; padding-left: 24px; position: relative; color: var(--text); }
.acc-list li::before { content: '✓'; position: absolute; left: 0; color: var(--blue); font-weight: bold; }

.hardware-parallax {
    position: relative;
    padding: 120px 0;
    margin: 60px 0;
    overflow: hidden;
    border-radius: 0;
    text-align: center;
    color: white;
}
.hw-bg {
    position: absolute;
    inset: 0;
    background-image: url('https://images.unsplash.com/photo-1518770660439-4636190af475?w=1600');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    z-index: 0;
}
.hw-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(15, 43, 91, 0.75); /* Navy overlay */
}
.hw-content {
    position: relative;
    z-index: 1;
    max-width: 700px;
}
.hw-content h2 { font-size: 36px; font-weight: 800; margin-bottom: 20px; color: white; }
.hw-content p { font-size: 18px; line-height: 1.6; opacity: 0.9; color: white; }

@media (max-width: 992px) {
    .showcase-grid { grid-template-columns: repeat(2, 1fr); }
    .showcase-item.large { grid-column: span 2; }
    .split-layout { flex-direction: column; padding: 30px; gap: 30px; }
}
@media (max-width: 768px) {
    .showcase-grid { grid-template-columns: 1fr; }
    .showcase-item.large { grid-column: 1; grid-row: auto; height: 300px; }
    .hardware-parallax { padding: 80px 20px; }
    .hw-bg { background-attachment: scroll; }
}
\n
/* ─── MARQUEE ─── */
.brand-marquee {
    width: 100%;
    overflow: hidden;
    background: #ffffff;
    padding: 30px 0;
    border-bottom: 1px solid var(--border);
    margin-bottom: 20px;
}
.marquee-track {
    display: flex;
    align-items: center;
    gap: 80px;
    width: max-content;
    animation: marquee-scroll 40s linear infinite;
}
.marquee-track img {
    opacity: 0.4;
    filter: grayscale(100%);
    transition: all 0.3s;
}
.marquee-track img:hover {
    opacity: 1;
    filter: grayscale(0%);
}
@keyframes marquee-scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* ─── COMPARE MODELS ─── */
.compare-grid {
    display: flex;
    gap: 30px;
    margin-top: 40px;
    align-items: flex-end;
}
.compare-card {
    flex: 1;
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    text-align: center;
    border: 1px solid var(--border);
    transition: all 0.3s;
    position: relative;
}
.compare-card:hover {
    box-shadow: 0 20px 40px rgba(0,0,0,0.06);
    transform: translateY(-10px);
}
.compare-card.highlight {
    background: var(--navy);
    color: white;
    border: none;
    padding: 60px 30px;
    box-shadow: 0 20px 40px rgba(15, 43, 91, 0.2);
}
.compare-badge {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: #eab308;
    color: #1a1f36;
    font-weight: 700;
    font-size: 13px;
    padding: 6px 16px;
    border-radius: 20px;
    box-shadow: 0 5px 15px rgba(234, 179, 8, 0.3);
}
.compare-img {
    width: 80%;
    height: 120px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.compare-card h3 { font-size: 20px; font-weight: 800; margin-bottom: 10px; }
.compare-desc { font-size: 14px; opacity: 0.7; margin-bottom: 20px; }
.compare-specs { list-style: none; padding: 0; margin: 0 0 30px; text-align: left; }
.compare-specs li { margin-bottom: 12px; font-size: 14px; display: flex; align-items: center; gap: 8px; opacity: 0.9; }
.compare-specs li::before { content: '✓'; color: var(--blue); font-weight: bold; }
.compare-card.highlight .compare-specs li::before { color: #60a5fa; }
.compare-card.highlight h3 { color: #ffffff; }
.compare-card.highlight .compare-desc { color: rgba(255, 255, 255, 0.85); }
.compare-card.highlight .compare-specs li { color: rgba(255, 255, 255, 0.9); }

.compare-card .btn { width: 100%; }

/* ─── INTERACTIVE HOTSPOT ─── */
.hotspot-container {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.1);
}
.hotspot-main-img {
    width: 100%;
    height: auto;
    display: block;
}
.hotspot-point {
    position: absolute;
    width: 30px;
    height: 30px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 0 0 5px rgba(255,255,255,0.3);
}
.hotspot-point::after {
    content: '+';
    font-size: 20px;
    color: var(--navy);
    font-weight: bold;
}
.hotspot-pulse {
    position: absolute;
    inset: -10px;
    border-radius: 50%;
    border: 2px solid white;
    animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}
@keyframes pulse-ring {
    0% { transform: scale(0.5); opacity: 0; }
    50% { opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}
.hotspot-tooltip {
    position: absolute;
    bottom: calc(100% + 15px);
    left: 50%;
    transform: translateX(-50%) translateY(10px);
    background: var(--navy);
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
    width: 200px;
    text-align: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
    pointer-events: none;
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}
.hotspot-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -6px;
    border-width: 6px;
    border-style: solid;
    border-color: var(--navy) transparent transparent transparent;
}
.hotspot-point:hover .hotspot-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}
.hotspot-tooltip strong { display: block; font-size: 14px; margin-bottom: 4px; }
.hotspot-tooltip p { margin: 0; font-size: 12px; color: #94a3b8; }

/* ─── BENTO GRID ─── */
.bento-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    grid-template-rows: 200px 200px;
    gap: 20px;
}
.bento-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    position: relative;
    border: 1px solid var(--border);
    transition: transform 0.3s, box-shadow 0.3s;
}
.bento-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.06);
}
.bento-content {
    padding: 30px;
    position: relative;
    z-index: 2;
}
.bento-card h3 { font-size: 20px; font-weight: 800; margin-bottom: 10px; color: var(--navy); }
.bento-card p { font-size: 14.5px; color: var(--text2); line-height: 1.6; }
.bento-large {
    grid-row: span 2;
    display: flex;
    flex-direction: column;
}
.bento-large img {
    margin-top: auto;
    width: 100%;
    height: 250px;
    object-fit: cover;
}
.bento-medium {
    grid-column: span 2;
    display: flex;
    align-items: center;
}
.bento-medium .bento-content { width: 50%; }
.bento-medium img {
    position: absolute;
    right: -20px;
    bottom: -20px;
    width: 55%;
    height: 120%;
    object-fit: contain;
}
.bento-icon {
    font-size: 32px;
    padding: 30px 30px 0;
}
.bento-small .bento-content { padding-top: 15px; }

/* ─── VIDEO TEASER ─── */
.video-container {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    aspect-ratio: 21/9;
    box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}
.video-bg {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    filter: brightness(0.7);
    transition: filter 0.5s;
}
.yt-bg {
    pointer-events: none;
    transform: scale(1.35);
}
.video-container:hover .video-bg { filter: brightness(0.5); }
.video-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
}
.play-btn {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    transition: all 0.3s;
    margin-bottom: 24px;
}
.play-btn svg { width: 32px; height: 32px; margin-left: 4px; }
.play-btn:hover {
    background: white;
    color: var(--navy);
    transform: scale(1.1);
}
.video-overlay h2 { font-size: 32px; font-weight: 800; margin-bottom: 8px; color: white; }
.video-overlay p { font-size: 16px; opacity: 0.8; color: white; }

@media (max-width: 992px) {
    .compare-grid { flex-direction: column; align-items: stretch; }
    .compare-card.highlight { padding: 40px 30px; }
    .bento-grid { grid-template-columns: 1fr 1fr; grid-template-rows: auto; }
    .bento-large { grid-column: span 2; }
    .bento-medium { grid-column: span 2; }
}
@media (max-width: 768px) {
    .bento-grid { grid-template-columns: 1fr; }
    .bento-medium img { position: static; width: 100%; height: 200px; object-fit: cover; }
    .bento-medium .bento-content { width: 100%; }
    .video-container { aspect-ratio: 16/9; }
    .video-overlay h2 { font-size: 24px; }
}


.video-teaser .video-bg {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100vw;
    height: 100vh;
    object-fit: cover;
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 0;
}
.video-teaser .video-container {
    position: relative;
    overflow: hidden;
    border-radius: 20px;
    height: 500px; /* Force height so video fits nicely */
}
</style>

