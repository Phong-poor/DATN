<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { getToken } from '@/services/auth'

import api from '../../services/api'
import swal from '@/services/swal'
import { handleImageFallback, imageFallbackUrl, normalizeImageUrl, productImageUrl, storageUrl } from '@/services/urls'
import { prefetchProductsPage } from '@/services/productsPrefetch'

const router = useRouter()

const tickerIcons = [
    '<svg class="ticker-code-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><path d="M5 16a2 2 0 1 0 4 0"/><path d="M16 16a2 2 0 1 0 4 0"/></svg>',
    '<svg class="ticker-code-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-5"/></svg>',
    '<svg class="ticker-code-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 7h11l-3-3"/><path d="M18 7l-3 3"/><path d="M17 17H6l3 3"/><path d="M6 17l3-3"/></svg>',
    '<svg class="ticker-code-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/></svg>',
]

const tickerItems = [
    '🚚 MIỄN PHÍ GIAO HÀNG HỎA TỐC CHO ĐƠN HÀNG TỪ 300K',
    '🛡️ BẢO HÀNH TOÀN DIỆN 24 THÁNG - 1 ĐỔI 1 TRONG 30 NGÀY',
    '🔄 THU CŨ ĐỔI MỚI - TRỢ GIÁ LÊN ĐẾN 2 TRIỆU ĐỒNG',
    '💳 TRẢ GÓP 0% LÃI SUẤT - DUYỆT HỒ SƠ CHỈ TRONG 5 PHÚT'
]

// Proactive error handler for robust loading
const handleImgError = (event, fallbackUrl) => handleImageFallback(event, fallbackUrl || imageFallbackUrl)

// Highly tailored premium slideshow in professional Vietnamese
const defaultSlides = [
    {
        eyebrow: 'PREMIUM LAPTOP STORE 2026',
        title: 'Sức Mạnh Hội Tụ',
        highlight: 'Sự Tinh Tế Chuyên Sâu',
        desc: 'Laptop cao cấp chế tác riêng cho nhà sáng tạo, game thủ chuyên nghiệp và kỹ sư công nghệ. Trải nghiệm hiệu năng vượt giới hạn vật lý với màn hình OLED đỉnh cao.',
        img: '/Gemini_Generated_Image_v5vppjv5vppjv5vp (1).png',
        deviceImg: '/hero_3d_laptop.png',
        primary: 'Mua ngay',
        secondary: 'Xem bộ sưu tập'
    },
    {
        eyebrow: 'NEW GENERATION CHIPS',
        title: 'Hiệu Năng Vượt Trội',
        highlight: 'Kiến Trúc AI Thế Hệ Mới',
        desc: 'Sở hữu ngay các cỗ máy tối tân trang bị NPU tăng tốc AI cục bộ đến 45 TOPs. Đáp ứng hoàn hảo mọi tác vụ deep learning và dựng hình 3D real-time.',
        img: '/Gemini_Generated_Image_7xfvdr7xfvdr7xfv.png',
        deviceImg: '/hero_macbook_setup.png',
        primary: 'Khám phá ngay',
        secondary: 'Tư vấn cấu hình'
    },
    {
        eyebrow: 'NEBULA DISPLAY TECHNOLOGY',
        title: 'Trải Nghiệm Đắm Chìm',
        highlight: 'Nebula OLED 240Hz',
        desc: 'Độ sâu màu 10-bit đích thực, độ tương phản tuyệt đối 1.000.000:1 cùng tần số quét 240Hz siêu mượt. Sắc sảo trong từng chuyển động game AAA.',
        img: '/Gemini_Generated_Image_j1cibhj1cibhj1ci.png',
        deviceImg: '/hero_gaming_parts.png',
        primary: 'Xem ưu đãi',
        secondary: 'So sánh sản phẩm'
    },
    {
        eyebrow: 'NEXTGEN SHOWROOM',
        title: 'Trải Nghiệm Đắm Chìm',
        highlight: 'Không Gian Cao Cấp',
        desc: 'Khám phá không gian laptop hiện đại với các dòng máy cao cấp được trưng bày thực tế cho game, sáng tạo và công việc chuyên nghiệp.',
        img: '/Gemini_Generated_Image_dp15ytdp15ytdp15.png',
        deviceImg: '/showroom_experience.png',
        primary: 'Xem showroom',
        secondary: 'Liên hệ tư vấn'
    }
]

const defaultCategories = [
    { id_danhmuc: 'gaming', ten_danhmuc: 'Laptop Gaming', mota: 'Cau hinh RTX, tan nhiet tot va man hinh tan so quet cao cho game thu.' },
    { id_danhmuc: 'macbook', ten_danhmuc: 'MacBook', mota: 'Thiet ke mong nhe, pin lau va hieu nang on dinh cho cong viec hang ngay.' },
    { id_danhmuc: 'office', ten_danhmuc: 'Laptop Van Phong', mota: 'Lua chon gon nhe, ben bi va toi uu chi phi cho hoc tap, lam viec.' }
]

const categories = ref([...defaultCategories])

// Highly premium matching stock photos for categories
const getCategoryFallbackImage = (catName) => {
    if (!catName) return 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=800';
    
    // Chuẩn hóa và loại bỏ dấu tiếng Việt mạnh mẽ để xử lý cả NFC & NFD
    const name = catName.normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .replace(/[đĐ]/g, 'd')
                        .toLowerCase()
                        .trim();
                        
    if (name.includes('gaming')) {
        // Ảnh laptop gaming cao cấp với bàn phím LED cơ động cực đẹp
        return 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800';
    }
    if (name.includes('van phong') || name.includes('office') || name.includes('business') || name.includes('mong nhe') || name.includes('ultrabook')) {
        // Laptop doanh nhân siêu mỏng Dell XPS sang trọng trên bàn gỗ tối
        return 'https://images.unsplash.com/photo-1585776245991-cf89dd7fc73a?w=800';
    }
    if (name.includes('macbook') || name.includes('apple') || name.includes('mac')) {
        // Ảnh MacBook Apple mở màn hình cực kỳ nghệ thuật
        return 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800';
    }
    if (name.includes('do hoa') || name.includes('creator') || name.includes('thiet ke') || name.includes('graphic') || name.includes('proart')) {
        // Laptop đồ họa sắc nét cùng bảng vẽ stylus chuyên nghiệp
        return 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800';
    }
    if (name.includes('hoc sinh') || name.includes('sinh vien') || name.includes('student') || name.includes('hoc tap') || name.includes('education')) {
        // Laptop học sinh/sinh viên trong không gian học tập năng động
        return 'https://images.unsplash.com/photo-1513258496099-48168024aec0?w=800';
    }
    
    // Mặc định: Laptop cao cấp tinh tế
    return 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=800';
};

const getCategoryTarget = (category) => {
    const id = String(category?.id_danhmuc || '').toLowerCase()
    const name = String(category?.ten_danhmuc || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
    if (id === 'gaming' || name.includes('gaming')) return '/gaming'
    if (id === 'macbook' || name.includes('macbook')) return '/macbook'
    if (id === 'office' || name.includes('van phong')) return '/san-pham?category=Laptop+van+phong'
    return `/san-pham?cat=${category.id_danhmuc}`
}

const mapProducts = (rawProducts = []) => {
    const list = Array.isArray(rawProducts) ? rawProducts : []
    const productVariants = list.map(p => {
        const variants = Array.isArray(p?.bien_thes)
            ? p.bien_thes
            : (Array.isArray(p?.bienThes) ? p.bienThes : [])

        if (variants.length === 0) {
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
                img: productImageUrl(p, null, 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=500'),
                badge: p.trangthai === 'Hot' ? 'HOT' : (p.trangthai === 'Mới' ? 'NEW' : ''),
                badgeColor: p.trangthai === 'Hot' ? '#ef4444' : '#2563eb',
                rating_avg: p.rating_avg !== undefined && p.rating_avg !== null ? Number(p.rating_avg) : 0,
                rating_count: p.rating_count !== undefined && p.rating_count !== null ? Number(p.rating_count) : 0
            }];
        }

        return variants.map(bt => {
            let ram = '', cpu = '', gpu = '', screen = '', color = '';
            let attributes = [];
            try { 
                attributes = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || []); 
            } catch (e) {}

            if (Array.isArray(attributes)) {
                attributes.forEach(attr => {
                    const attrName = (attr.ten_thuoctinh || '').toLowerCase();
                    if (attrName === 'ram') ram = attr.giatri;
                    else if (attrName === 'cpu') cpu = attr.giatri;
                    else if (attrName === 'gpu') gpu = attr.giatri;
                    else if (attrName === 'kích thước' || attrName === 'màn hình' || attrName === 'độ phân giải') screen = attr.giatri;
                    else if (attrName === 'màu sắc' || attrName === 'màu') color = attr.giatri;
                });
            }

            let generalSpecs = [];
            try {
                const tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || []);
                if (Array.isArray(tskt)) {
                    generalSpecs = tskt.map(item => item.giatri).filter(Boolean);
                }
            } catch (e) {}

            const fullName = [p.tenSP, ...generalSpecs].join(' ');
            
            const specs = [
                { label: 'RAM', value: ram },
                { label: 'CPU', value: cpu },
                { label: 'Card đồ họa', value: gpu },
                { label: 'Màn hình', value: screen }
            ].filter(s => s.value);

            return {
                id: p.id_sanpham,
                key_id: String(bt.id_bienthe),
                name: p.tenSP,
                fullName: fullName,
                category: p.danh_muc?.ten_danhmuc || 'Laptops',
                id_danhmuc: String(p.id_danhmuc || ''),
                id_thuonghieu: String(p.id_thuonghieu || ''),
                brandName: p.thuong_hieu?.ten_thuonghieu || '',
                weight: p.khoiluong,
                priceNum: Number(bt.gia_khuyen_mai || 0) > 0 ? Number(bt.gia_khuyen_mai) : Number(bt.gia || 0),
                oldPriceNum: Number(bt.gia || 0),
                specs: specs,
                img: productImageUrl(p, bt),
                badge: p.trangthai === 'Hot' ? 'HOT' : (p.trangthai === 'Mới' ? 'NEW' : ''),
                badgeColor: p.trangthai === 'Hot' ? '#ef4444' : '#2563eb',
                rating_avg: p.rating_avg !== undefined && p.rating_avg !== null ? Number(p.rating_avg) : 0,
                rating_count: p.rating_count !== undefined && p.rating_count !== null ? Number(p.rating_count) : 0
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
const featuredAccessories = ref([])
const allHomeProducts = ref([])
const bannerSlides = ref([])
const latestNews = ref([])
const affiliateVideos = ref([])
const hoveredAffiliateVideoId = ref(null)
const affiliateVideoSlider = ref(null)
const affiliateVideoSliderPrimed = ref(false)
const affiliateVideoDrag = {
    active: false,
    startX: 0,
    scrollLeft: 0,
    moved: false,
}
const newsPlaceholderImage = 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800'

const newsImageUrl = (path) => {
    if (!path) return newsPlaceholderImage
    return normalizeImageUrl(path, newsPlaceholderImage)
}

const normalizeMediaType = (value, url = '') => {
    const type = String(value || '').toLowerCase()
    if (type.includes('video')) return 'video'
    if (/\.(mp4|webm|mov|avi)(\?|#|$)/i.test(String(url || ''))) return 'video'
    return 'image'
}

const isVideoSlide = (slide = {}) => normalizeMediaType(slide.mediaType, slide.img) === 'video'
const isPlayableVideoSrc = (src = '') => {
    const value = String(src || '').trim()
    return Boolean(value) && (
        value.startsWith('/storage/')
        || value.startsWith('blob:')
        || /\.(mp4|webm|mov|avi|m4v|mkv)(\?|#|$)/i.test(value)
    )
}

const affiliateVideoSrc = (video = {}) => storageUrl(video.video_src || video.video_url || video.video_path)
const affiliateThumbnailSrc = (video = {}) => storageUrl(video.thumbnail_src || video.thumbnail_path || video.product?.hinhanh || '')
const viewedAffiliateVideoIds = new Set()
const audibleAffiliateVideoIds = ref(new Set())

const getYoutubeId = (url = '') => {
    const value = String(url || '').trim()
    if (!value) return ''

    const patterns = [
        /youtube\.com\/watch\?v=([^&]+)/i,
        /youtube\.com\/shorts\/([^?&/]+)/i,
        /youtu\.be\/([^?&/]+)/i,
        /youtube\.com\/embed\/([^?&/]+)/i,
    ]

    for (const pattern of patterns) {
        const match = value.match(pattern)
        if (match?.[1]) return match[1]
    }

    return ''
}

const externalVideoUrl = (video = {}) => video.video_url || video.video_src || video.video_path || ''

const affiliateVideoEmbedSrc = (video = {}, autoplay = false, audible = false) => {
    const id = getYoutubeId(externalVideoUrl(video))
    if (!id) return ''

    const params = new URLSearchParams({
        controls: '0',
        fs: '1',
        rel: '0',
        modestbranding: '1',
        playsinline: '1',
    })

    if (autoplay) {
        params.set('autoplay', '1')
        params.set('mute', audible ? '0' : '1')
    }

    return `https://www.youtube.com/embed/${id}?${params.toString()}`
}

const playAffiliateVideoPreview = (event, video = {}) => {
    hoveredAffiliateVideoId.value = video.id || null
    const media = event.currentTarget?.querySelector?.('video')
    if (media) media.play().catch(() => {})

    if (video.id && !viewedAffiliateVideoIds.has(video.id)) {
        viewedAffiliateVideoIds.add(video.id)
        api.post(`/affiliate-videos/${video.id}/track`, { type: 'view' }).catch(() => {})
    }
}

const pauseAffiliateVideoPreview = (event) => {
    hoveredAffiliateVideoId.value = null
    const media = event.currentTarget?.querySelector?.('video')
    if (!media) return
    media.pause()
}

const enableAffiliateVideoSound = (event, video = {}) => {
    if (video.id) {
        const next = new Set(audibleAffiliateVideoIds.value)
        next.add(video.id)
        audibleAffiliateVideoIds.value = next
    }

    const media = event.currentTarget?.querySelector?.('video')
    if (!media) return
    media.muted = false
    media.volume = 1
    media.play().catch(() => {})
}

const scrollAffiliateVideos = (direction = 1) => {
    const slider = affiliateVideoSlider.value
    if (!slider) return
    const firstCard = slider.querySelector('.affiliate-video-card')
    const amount = firstCard ? firstCard.offsetWidth + 24 : Math.max(380, slider.clientWidth * 0.42)
    slider.scrollBy({ left: amount * direction, behavior: 'smooth' })
}

const primeAffiliateVideoSlider = async () => {
    if (affiliateVideoSliderPrimed.value || affiliateVideos.value.length < 4) return
    await nextTick()
    requestAnimationFrame(() => {
        const slider = affiliateVideoSlider.value
        if (!slider) return
        slider.scrollLeft = 0
        affiliateVideoSliderPrimed.value = true
    })
}

const startAffiliateVideoDrag = (event) => {
    const slider = affiliateVideoSlider.value
    if (!slider || event.button > 0 || event.target?.closest?.('button, iframe, video')) return
    affiliateVideoDrag.active = true
    affiliateVideoDrag.startX = event.clientX
    affiliateVideoDrag.scrollLeft = slider.scrollLeft
    affiliateVideoDrag.moved = false
    slider.classList.add('is-dragging')
    slider.setPointerCapture?.(event.pointerId)
}

const moveAffiliateVideoDrag = (event) => {
    const slider = affiliateVideoSlider.value
    if (!slider || !affiliateVideoDrag.active) return
    const distance = event.clientX - affiliateVideoDrag.startX
    if (Math.abs(distance) > 4) affiliateVideoDrag.moved = true
    slider.scrollLeft = affiliateVideoDrag.scrollLeft - distance
}

const stopAffiliateVideoDrag = (event) => {
    const slider = affiliateVideoSlider.value
    if (!slider || !affiliateVideoDrag.active) return
    affiliateVideoDrag.active = false
    slider.classList.remove('is-dragging')
    slider.releasePointerCapture?.(event.pointerId)
}

const openAffiliateVideoProduct = async (video = {}) => {
    if (video.id) {
        api.post(`/affiliate-videos/${video.id}/track`, { type: 'click' }).catch(() => {})
    }

    const productId = video.product?.id_sanpham || video.product_id || video.id_sanpham
    if (!productId) {
        swal.info('Chưa gắn sản phẩm', 'Video YouTube này chưa được cộng tác viên gắn sản phẩm.')
        return
    }

    try {
        const { data: product } = await api.get(`/sanpham/${productId}`, {
            cache: false,
            skipGlobalLoader: true,
        })
        if (!product?.id_sanpham) throw new Error('PRODUCT_NOT_FOUND')

        const variants = product.bienThes || product.bien_thes || []
        const availableVariant = variants.find(item => Number(item.soluong) > 0) || variants[0]
        router.push({
            path: `/san-pham/${product.id_sanpham}`,
            query: availableVariant?.id_bienthe ? { variant: availableVariant.id_bienthe } : {},
        })
    } catch (error) {
        swal.error('Không tìm thấy sản phẩm', 'Sản phẩm gắn với video hiện không còn tồn tại hoặc chưa thể tải.')
    }
}

const mapNewsPost = (post = {}) => ({
    ...post,
    id: post.id,
    title: post.title || post.tieude || 'Bài viết công nghệ',
    excerpt: post.excerpt || post.tomtat || post.mota || '',
    image: post.image || post.hinhanh || post.thumbnail || '',
    category: post.category || post.danhmuc || 'Công nghệ',
    views: post.views ?? post.luotxem ?? 0,
    publishedAt: post.publishedAt || post.dang_luc || post.created_at || '',
})

const mapBannerToSlide = (banner = {}) => ({
    id: banner.id,
    eyebrow: banner.chudenho || 'PREMIUM LAPTOP STORE 2026',
    title: banner.tieude || 'Sức Mạnh Hội Tụ',
    highlight: banner.noibat || banner.phude || 'Sự Tinh Tế Chuyên Sâu',
    desc: banner.mota || banner.phude || '',
    img: normalizeImageUrl(banner.hinhanh, '/Gemini_Generated_Image_v5vppjv5vppjv5vp (1).png'),
    mobileImg: normalizeImageUrl(banner.hinhanh_mobile || banner.hinhanh, '/Gemini_Generated_Image_v5vppjv5vppjv5vp (1).png'),
    mediaType: normalizeMediaType(banner.loaimedia, banner.hinhanh),
    mobileMediaType: normalizeMediaType(banner.loai_media_mobile || banner.loaimedia, banner.hinhanh_mobile || banner.hinhanh),
    link: banner.duongdan || '',
    productId: banner.id_sanpham ? String(banner.id_sanpham) : '',
    primary: banner.nhanchinh || 'Mua ngay',
    secondary: banner.nhanphu || 'Xem bộ sưu tập',
    productBadge: banner.huyhieu_sanpham || 'TRENDING NOW',
    productFeature: banner.dactinh_sanpham || 'RTX 40-Series',
})

const mapApiBannerToSlide = (banner = {}) => ({
    id: banner.id,
    eyebrow: banner.eyebrow || banner.chudenho || 'PREMIUM LAPTOP STORE 2026',
    title: banner.title || banner.tieude || 'Sức Mạnh Hội Tụ',
    highlight: banner.highlight || banner.phude || banner.noibat || 'Sự Tinh Tế Chuyên Sâu',
    desc: banner.description || banner.mota || banner.phude || '',
    img: normalizeImageUrl(banner.image || banner.hinhanh, '/Gemini_Generated_Image_v5vppjv5vppjv5vp (1).png'),
    mobileImg: normalizeImageUrl(banner.mobile_image || banner.hinhanh_mobile || banner.image || banner.hinhanh, '/Gemini_Generated_Image_v5vppjv5vppjv5vp (1).png'),
    mediaType: normalizeMediaType(banner.media_type || banner.loaimedia, banner.image || banner.hinhanh),
    mobileMediaType: normalizeMediaType(banner.mobile_media_type || banner.loai_media_mobile || banner.media_type || banner.loaimedia, banner.mobile_image || banner.hinhanh_mobile || banner.image || banner.hinhanh),
    link: banner.link_url || banner.duongdan || '',
    productId: banner.product_id || banner.id_sanpham ? String(banner.product_id || banner.id_sanpham) : '',
    primary: banner.primary_label || banner.nhanchinh || 'Mua ngay',
    secondary: banner.secondary_label || banner.nhanphu || 'Xem bộ sưu tập',
    productBadge: banner.product_badge || banner.huyhieu_sanpham || 'TRENDING NOW',
    productFeature: banner.product_feature || banner.dactinh_sanpham || 'RTX 40-Series',
})

const cachedArray = (value) => Array.isArray(value) ? value : null

const loadCache = () => {
    try {
        const cached = localStorage.getItem('premium_home_cache')
        if (cached) {
            const parsed = JSON.parse(cached)
            const cachedProducts = cachedArray(parsed.featuredProducts)
            const cachedAccessories = cachedArray(parsed.featuredAccessories)
            const cachedCategories = cachedArray(parsed.categories)
            const cachedNews = cachedArray(parsed.latestNews)
            const cachedBanners = cachedArray(parsed.bannerSlides)
            const cachedVideos = cachedArray(parsed.affiliateVideos)

            if (cachedProducts) featuredProducts.value = cachedProducts
            if (cachedAccessories) featuredAccessories.value = cachedAccessories
            if (cachedCategories?.length) categories.value = cachedCategories
            if (cachedNews) latestNews.value = cachedNews
            if (cachedBanners) bannerSlides.value = cachedBanners
            if (cachedVideos) {
                affiliateVideos.value = cachedVideos
                primeAffiliateVideoSlider()
            }
        }
    } catch (e) {
        console.error('Lỗi tải cache trang chủ:', e)
    }
}

const saveCache = () => {
    try {
        localStorage.setItem('premium_home_cache', JSON.stringify({
            featuredProducts: featuredProducts.value,
            featuredAccessories: featuredAccessories.value,
            categories: categories.value,
            latestNews: latestNews.value,
            bannerSlides: bannerSlides.value,
            affiliateVideos: affiliateVideos.value
        }))
    } catch (e) {
        console.error('Lỗi lưu cache trang chủ:', e)
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
        rootMargin: '0px 0px -40px 0px'
    })
    reveals.forEach(el => observer.observe(el))
}

onMounted(async () => {
    loadCache()
    setTimeout(initScrollReveal, 150)
    setTimeout(initStatsObserver, 200)


    try {
        // Gọi song song toàn bộ API lấy dữ liệu ngầm
        const [newsRes, productsBundle, bannersRes, affiliateVideoRes] = await Promise.all([
            api.get('/news', { params: { scope: 'public', per_page: 4 } }).catch(e => { console.error('News API failed', e); return { data: { data: [] } }; }),
            prefetchProductsPage().catch(e => { console.error('Products bundle API failed', e); return { productsRaw: [], categories: [] }; }),
            api.get('/banners').catch(e => { console.error('Banners API failed', e); return { data: [] }; }),
            api.get('/affiliate-videos/public', { params: { limit: 12 } }).catch(e => { console.error('Affiliate videos API failed', e); return { data: [] }; })
        ])

        latestNews.value = (newsRes.data?.data || []).map(mapNewsPost)
        
        const rawProducts = productsBundle?.productsRaw || []
        const allProducts = mapProducts(rawProducts)
        allHomeProducts.value = allProducts
        
        const laptopsList = allProducts.filter(p => {
            const cat = (p.category || '').toLowerCase();
            return !cat.includes('phụ kiện') && !cat.includes('phu kien');
        })
        featuredProducts.value = laptopsList.slice(0, 16)
        
        const accessoriesList = allProducts.filter(p => {
            const cat = (p.category || '').toLowerCase();
            return cat.includes('phụ kiện') || cat.includes('phu kien');
        })
        featuredAccessories.value = accessoriesList.slice(0, 10)
        
        const apiCategories = (productsBundle?.categories || []).slice(0, 4)
        categories.value = apiCategories.length ? apiCategories : [...defaultCategories]

        const apiBanners = Array.isArray(bannersRes.data) ? bannersRes.data : (bannersRes.data?.data || [])
        bannerSlides.value = apiBanners.map(mapApiBannerToSlide)
        affiliateVideos.value = Array.isArray(affiliateVideoRes.data) ? affiliateVideoRes.data : (affiliateVideoRes.data?.data || [])
        primeAffiliateVideoSlider()
        saveCache()
        setTimeout(initScrollReveal, 200)
    } catch (error) {
        console.error('Lỗi khi tải dữ liệu trang chủ:', error)
    }
})

// Tab sliders and tabs logic
const activeCategoryTab = ref('all')
const filteredFeaturedProducts = computed(() => {
    const products = Array.isArray(featuredProducts.value) ? featuredProducts.value : []
    if (activeCategoryTab.value === 'all') return products
    
    return products.filter(p => {
        const name = (p.fullName || p.name || '').toLowerCase();
        const brand = (p.brandName || '').toLowerCase();
        const cat = (p.category || '').toLowerCase();
        
        if (activeCategoryTab.value === 'gaming') {
            return name.includes('gaming') || name.includes('tuf') || name.includes('rog') || name.includes('nitro') || name.includes('nextgen') || name.includes('rtx') || cat.includes('gaming');
        }
        if (activeCategoryTab.value === 'office') {
            return name.includes('vivobook') || name.includes('zenbook') || name.includes('hp 15') || name.includes('student') || name.includes('sinh viên') || name.includes('văn phòng') || cat.includes('office') || cat.includes('văn phòng');
        }
        if (activeCategoryTab.value === 'macbook') {
            return name.includes('macbook') || name.includes('apple') || brand.includes('apple');
        }
        if (activeCategoryTab.value === 'creator') {
            return name.includes('creator') || name.includes('studio') || name.includes('oled') || name.includes('proart') || name.includes('design') || cat.includes('creator') || cat.includes('đồ họa');
        }
        return true;
    });
})

const changeProductTab = (tab) => {
    activeCategoryTab.value = tab
}

const themVaoYeuThich = async (product) => {
    const token = getToken()
    if (!token) {
        swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để thêm sản phẩm vào danh sách yêu thích!')
        router.push('/dang-nhap')
        return
    }

    const variantId = product.key_id || product.id_bienthe
    if (!variantId) {
        swal.error('Lỗi', 'Không tìm thấy cấu hình phù hợp. Vui lòng thử lại sau!')
        return
    }

    try {
        await api.post('/yeu-thich/them', { id_bienthe: variantId, soluong: 1 })
        swal.success('Thành công', `Đã thêm ${product.name} vào danh sách yêu thích! ❤️`)
        window.dispatchEvent(new Event('wishlist-updated'))
    } catch (err) {
        swal.error('Lỗi', err.response?.data?.message || 'Không thể thực hiện tác vụ.')
    }
}

const formatPrice = (p) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(p)

const themVaoGioHang = async (product) => {
    const token = getToken()
    if (!token) {
        swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để tiến hành mua hàng!')
        localStorage.setItem('pendingCartItem', JSON.stringify({ id_bienthe: product.key_id, soluong: 1 }))
        router.push('/dang-nhap')
        return
    }

    try {
        const res = await api.post('/gio-hang/them', { id_bienthe: product.key_id, soluong: 1 }, {
            headers: { Authorization: `Bearer ${token}` }
        })
        window.dispatchEvent(new Event('cart-updated'))
        // Lấy id_giohang từ response để chỉ checkout 1 sản phẩm
        const cartItemId = res?.data?.id_giohang || res?.data?.item?.id_giohang || res?.data?.data?.id_giohang || ''
        if (cartItemId) {
            router.push(`/thanh-toan?buy_now=1&cart_item=${cartItemId}`)
        } else {
            // Fallback: dùng variant id nếu không có cart item id
            router.push(`/thanh-toan?buy_now=1&variant=${product.key_id}`)
        }
    } catch (err) {
        swal.error('Lỗi', err.response?.data?.message || 'Không thể mua sản phẩm.')
    }
}

// ===== STATS COUNT-UP ANIMATION =====
const statsData = [
    { end: 15, suffix: 'K+', label: 'Khách Hàng Hài Lòng', decimals: 0 },
    { end: 500, suffix: '+', label: 'Sản Phẩm Cao Cấp', decimals: 0 },
    { end: 24, suffix: '/7', label: 'Hỗ Trợ Chuyên Sâu', decimals: 0 },
    { end: 99, suffix: '%', label: 'Đánh Giá Tích Cực', decimals: 0 },
]
const statsDisplayed = ref(statsData.map(() => 0))
const statsStarted = ref(false)

const runCountUp = () => {
    if (statsStarted.value) return
    statsStarted.value = true
    const duration = 2000
    const fps = 60
    const steps = Math.round(duration / (1000 / fps))
    statsData.forEach((stat, idx) => {
        let step = 0
        const timer = setInterval(() => {
            step++
            // easeOutExpo
            const progress = step === steps ? 1 : 1 - Math.pow(2, -10 * step / steps)
            statsDisplayed.value[idx] = Math.round(stat.end * progress)
            if (step >= steps) {
                statsDisplayed.value[idx] = stat.end
                clearInterval(timer)
            }
        }, 1000 / fps)
    })
}

const initStatsObserver = () => {
    const section = document.querySelector('.trust-bar-section')
    if (!section) return
    const observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting) {
                runCountUp()
                observer.disconnect()
            }
        },
        { threshold: 0.3 }
    )
    observer.observe(section)
}

const reviews = [
    { name: 'Trần Minh Quân', role: 'Creative Director', content: 'Thiết kế đẹp, mua hàng dễ, tư vấn đúng nhu cầu. Máy nhận được đúng như mong đợi và hiệu suất render vượt trội.', avatar: 'https://randomuser.me/api/portraits/men/32.jpg' },
    { name: 'Nguyễn Phương Anh', role: 'Marketing Manager', content: 'Mình rất thích cách trình bày sản phẩm và trải nghiệm đặt hàng. Không gian hiển thị tối giản nhưng cực sang trọng.', avatar: 'https://randomuser.me/api/portraits/women/44.jpg' },
    { name: 'Lê Hoàng Nam', role: 'Pro Gamer', content: 'Cấu hình cực mạnh, tản nhiệt tốt, giao hàng siêu nhanh. Phần gaming bento nhìn cực kỳ kích thích và chuyên nghiệp.', avatar: 'https://randomuser.me/api/portraits/men/52.jpg' }
]

// Flash Sale Countdown Logic
const days = ref('00')
const hours = ref('04')
const minutes = ref('25')
const seconds = ref('10')
let countdownInterval = null

const startCountdown = () => {
    // End time is 4 hours from now
    const endTime = Date.now() + 4 * 60 * 60 * 1000
    countdownInterval = setInterval(() => {
        const diff = endTime - Date.now()
        if (diff <= 0) {
            clearInterval(countdownInterval)
            days.value = '00'
            hours.value = '00'
            minutes.value = '00'
            seconds.value = '00'
            return
        }
        const d = Math.floor(diff / (1000 * 60 * 60 * 24))
        const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
        const s = Math.floor((diff % (1000 * 60)) / 1000)
        
        days.value = String(d).padStart(2, '0')
        hours.value = String(h).padStart(2, '0')
        minutes.value = String(m).padStart(2, '0')
        seconds.value = String(s).padStart(2, '0')
    }, 1000)
}

const flashSaleProducts = computed(() => {
    const products = Array.isArray(featuredProducts.value) ? featuredProducts.value : []
    // Lọc các sản phẩm có giảm giá (oldPriceNum > priceNum) làm sản phẩm Flash Sale
    return products.filter(p => p.oldPriceNum > p.priceNum).slice(0, 4)
})

const current = ref(0)
const slides = computed(() => {
    const list = Array.isArray(bannerSlides.value) ? bannerSlides.value : []
    return list.length ? list : defaultSlides
})
const activeSlide = computed(() => slides.value[current.value] || slides.value[0] || {})
const heroProductFallbackImage = '/hero_3d_laptop.png'
const activeHeroProduct = computed(() => {
    const allProducts = Array.isArray(allHomeProducts.value) ? allHomeProducts.value : []
    const featured = Array.isArray(featuredProducts.value) ? featuredProducts.value : []
    const products = allProducts.length ? allProducts : featured
    if (!products.length) return null
    const slideProductId = activeSlide.value?.productId
    if (slideProductId) {
        const matched = products.find(product => String(product.id) === String(slideProductId))
        if (matched) return matched
    }
    return products[current.value % products.length]
})

const getHeroSpecsList = (product) => {
    if (!product) return []
    let list = Array.isArray(product.specs) ? [...product.specs] : []
    
    if (Array.isArray(product.thong_so_ky_thuat)) {
        product.thong_so_ky_thuat.forEach(item => {
            if (item.ten_thuoctinh && item.giatri && !list.some(s => s.label && s.label.toLowerCase() === item.ten_thuoctinh.toLowerCase())) {
                list.push({ label: item.ten_thuoctinh, value: item.giatri })
            }
        })
    }
    
    const nameStr = `${product.name || ''} ${product.fullName || ''}`
    
    if (!list.some(s => s.label && s.label.toUpperCase() === 'RAM')) {
        const ramMatch = nameStr.match(/(\d+\s*GB)\s*(RAM|DDR\d)?/i) || nameStr.match(/RAM\s*(\d+\s*GB)/i)
        if (ramMatch) list.push({ label: 'RAM', value: ramMatch[1].toUpperCase() })
    }
    
    if (!list.some(s => s.label && s.label.toUpperCase() === 'CPU')) {
        const cpuMatch = nameStr.match(/(Intel\s+Core\s+i\d-\w+|i[3579]-\w+|Ryzen\s+[3579]\s+\w+|Ultra\s+[579]\s+\w+|M[12345]\s+(?:Pro|Max)?)/i)
        if (cpuMatch) list.push({ label: 'CPU', value: cpuMatch[1] })
    }
    
    if (!list.some(s => s.label && (s.label.toUpperCase().includes('SSD') || s.label.toUpperCase().includes('CỨNG')))) {
        const ssdMatch = nameStr.match(/(256GB|512GB|1TB|2TB)\s*(SSD)?/i)
        if (ssdMatch) list.push({ label: 'SSD', value: ssdMatch[0].toUpperCase() })
    }
    
    if (!list.some(s => s.label && (s.label.toUpperCase().includes('GPU') || s.label.toUpperCase().includes('CARD') || s.label.toUpperCase().includes('VGA')))) {
        const gpuMatch = nameStr.match(/(RTX\s*\d{4}(?:\s*Ti)?|GTX\s*\d{4}|Radeon\s*\w+)/i)
        if (gpuMatch) list.push({ label: 'GPU', value: gpuMatch[0].toUpperCase() })
    }

    if (!list.length) {
        list = [
            { label: 'CPU', value: 'Intel Core i7' },
            { label: 'RAM', value: '16GB DDR5' },
            { label: 'SSD', value: '512GB NVMe' },
            { label: 'GPU', value: 'RTX 4060' }
        ]
    }
    return list
}

const getHeroSpecsText = (product) => {
    const list = getHeroSpecsList(product)
    return list.map(s => s.value).filter(Boolean).join(' ')
}

const getHeroFullTitle = (product) => {
    if (!product) return ''
    const baseName = product.name || ''
    const specsList = getHeroSpecsList(product)
    
    const missingSpecs = specsList
        .map(s => s.value)
        .filter(Boolean)
        .filter(val => !baseName.toLowerCase().includes(val.toLowerCase()))
    
    if (missingSpecs.length > 0) {
        return `${baseName} ${missingSpecs.join(' ')}`
    }
    return baseName
}

const handlePrimaryClick = (slide) => {
    if (slide?.link) {
        router.push(slide.link)
        return
    }
    if (slide.primary === 'Khám phá ngay') {
        router.push('/san-pham')
    } else {
        router.push('/san-pham')
    }
}

const handleSecondaryClick = (slide) => {
    router.push(slide?.link || '/san-pham')
}

const checkoutHeroProduct = (product) => {
    if (!product?.key_id) {
        router.push(product?.id ? `/san-pham/${product.id}` : '/san-pham')
        return
    }
    themVaoGioHang(product)
}

let interval = null
const nextSlide = () => {
    const total = slides.value.length || 1
    current.value = (current.value + 1) % total
}
const prevSlide = () => {
    const total = slides.value.length || 1
    current.value = (current.value - 1 + total) % total
}
const start = () => { stop(); interval = setInterval(nextSlide, 6000) }
const stop = () => { if (interval) clearInterval(interval) }

onMounted(() => {
    start()
    startCountdown()
})
onUnmounted(() => {
    stop()
    if (countdownInterval) clearInterval(countdownInterval)
})
</script>

<template>
  <div class="home-wrapper">
    <main class="premium-theme">

        <!-- Top Ticker Promo (Always Dark/High Contrast) -->
        <div class="ticker-bar">
            <div class="ticker-track">
                <template v-for="loop in 2" :key="loop">
                    <span v-for="(item, itemIndex) in tickerItems" :key="`${loop}-${itemIndex}`" class="ticker-item">
                        <span class="ticker-icon-wrap" v-html="tickerIcons[itemIndex % tickerIcons.length]"></span>
                        <span class="ticker-label">{{ item.replace(/^\S+\s*/, '') }}</span>
                        <span class="ticker-dot">•</span>
                    </span>
                </template>
            </div>
        </div>

        <!-- 1. CINEMATIC HERO SECTION (Always Premium Dark Luxury themed) -->
        <div class="hero-viewport" @mouseenter="stop" @mouseleave="start">
            <transition name="ambient-fade" mode="out-in">
                <div class="hero-bg-wrapper" :key="current">
                    <video
                        v-if="isVideoSlide(activeSlide)"
                        :src="activeSlide.img"
                        class="hero-bg-img hero-bg-video"
                        muted
                        autoplay
                        loop
                        playsinline
                        preload="metadata"
                    ></video>
                    <img v-else :src="activeSlide.img" alt="" class="hero-bg-img" @error="handleImgError($event, 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=1200')" />
                    <div class="hero-overlay-curtain"></div>
                </div>
            </transition>

            <div class="hero-container">
                <transition name="hero-content-slide" mode="out-in">
                    <div class="hero-content" :key="current">
                        <div class="hero-text-block">
                            <div class="hero-badge">
                                <span class="badge-glow-dot"></span>
                                {{ activeSlide.eyebrow }}
                            </div>
                            <h1 class="hero-title">
                                {{ activeSlide.title }}
                                <span class="gradient-text">{{ activeSlide.highlight }}</span>
                            </h1>
                            <p class="hero-description">{{ activeSlide.desc }}</p>
                            
                            <div class="hero-buttons">
                                <button class="btn btn-premium-glow" @click="handlePrimaryClick(activeSlide)">
                                    {{ activeSlide.primary }}
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                                <button class="btn btn-premium-glass" @click="handleSecondaryClick(activeSlide)">
                                    {{ activeSlide.secondary }}
                                </button>
                            </div>

                            <!-- Trust Indicators (Inside Hero) -->
                            <div class="hero-trust-indicators">
                                <div class="trust-pill">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span>Giao Hàng Miễn Phí</span>
                                </div>
                                <div class="trust-pill">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <span>Bảo Hành 24 Tháng</span>
                                </div>
                                <div class="trust-pill">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    <span>Trả Góp 0%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Device Showcase Panel -->
                        <div class="hero-device-wrapper">
                            <div class="glow-orb"></div>
                            <div class="device-showcase-card hero-product-card">
                                <!-- Top-Right Badge: TRENDING NOW / RTX 40-Series -->
                                <div class="ambient-card float-top">
                                    <span>{{ activeSlide.productBadge || 'Trending Now' }}</span>
                                    <strong>{{ activeSlide.productFeature || 'RTX 40-Series' }}</strong>
                                </div>

                                <div class="hero-product-visual">
                                    <img :src="activeHeroProduct?.img || activeSlide.deviceImg || activeSlide.img || heroProductFallbackImage" :alt="activeHeroProduct?.name || activeSlide.title" @error="handleImgError($event, heroProductFallbackImage)" />
                                </div>
                                <div class="hero-product-info" v-if="activeHeroProduct">
                                    <div class="hero-product-copy">
                                        <span class="hero-product-brand">{{ activeHeroProduct.brandName || activeHeroProduct.category }}</span>
                                        <strong class="hero-product-title">{{ getHeroFullTitle(activeHeroProduct) }}</strong>
                                        
                                        <!-- Row of Specs tags with inline SVG icons -->
                                        <div class="hero-product-specs">
                                            <span class="spec-tag" v-for="spec in getHeroSpecsList(activeHeroProduct)" :key="spec.label">
                                                <svg v-if="spec.label === 'CPU'" class="spec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="4" y="4" width="16" height="16" rx="2" ry="2" stroke-width="2"></rect><rect x="9" y="9" width="6" height="6" stroke-width="2"></rect><path stroke-linecap="round" d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 15h3M1 9h3M1 15h3" stroke-width="2"></path></svg>
                                                <svg v-else-if="spec.label === 'Màn hình' || spec.label === 'Screen'" class="spec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3" y="3" width="18" height="13" rx="2" stroke-width="2"></rect><path stroke-linecap="round" d="M12 16v5M8 21h8" stroke-width="2"></path></svg>
                                                <svg v-else-if="spec.label === 'RAM'" class="spec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9h14M5 15h14M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                                                <svg v-else class="spec-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                                <span>{{ spec.value }}</span>
                                            </span>
                                        </div>

                                        <em class="hero-product-price">{{ activeHeroProduct.priceNum > 0 ? formatPrice(activeHeroProduct.priceNum) : 'Liên hệ' }}</em>
                                    </div>
                                    
                                    <!-- Bottom Row: Fan badge on the left, Checkout button on the right -->
                                    <div class="hero-product-bottom-row">
                                        <div class="ambient-card float-bottom">
                                            <svg class="badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M3 12h18M5.636 5.636l12.728 12.728M5.636 18.364L18.364 5.636" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                            <div class="badge-text">
                                                <span>Tản Nhiệt Cao Cấp</span>
                                                <strong>Buồng Hơi Vapor</strong>
                                            </div>
                                        </div>
                                        
                                        <button class="hero-checkout-btn" type="button" @click.stop="checkoutHeroProduct(activeHeroProduct)">
                                            <span>Thanh toán ngay</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Navigation dots & controls -->
                <div class="hero-navigation">
                    <button class="arrow-control" @click="prevSlide" aria-label="Previous slide">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div class="bullet-dots">
                        <span v-for="(slide, i) in slides" :key="i" :class="{ active: i === current }" @click="current = i"></span>
                    </div>
                    <button class="arrow-control" @click="nextSlide" aria-label="Next slide">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. TRUST BAR (Solid dark visual frame) -->
        <section class="trust-bar-section">
            <div class="grid-container scroll-reveal reveal-stagger">
                <div class="trust-card" v-for="(stat, i) in statsData" :key="i">
                    <h3 class="stat-counter">
                        <span class="counter-num">{{ statsDisplayed[i] }}</span><span class="counter-suffix">{{ stat.suffix }}</span>
                    </h3>
                    <p>{{ stat.label }}</p>
                </div>
            </div>
        </section>

        <!-- 2.5. FLASH SALE (Dark Cyber-Luxury) -->
        <section class="section flashsale-section" v-if="flashSaleProducts.length > 0">
            <div class="grid-container">
                <div class="section-header scroll-reveal reveal-fade-up">
                    <div class="label-wrapper flex-row-align">
                        <div>
                            <span class="ambient-label flash-badge">⚡ GIỚI HẠN THỜI GIAN</span>
                            <h2>Flash Sale Đang Diễn Ra</h2>
                            <p>Sở hữu ngay các siêu phẩm công nghệ với mức giá tốt nhất trong ngày.</p>
                        </div>
                        <div class="countdown-clock">
                            <span class="timer-segment">{{ days }}</span>
                            <span class="timer-colon">:</span>
                            <span class="timer-segment">{{ hours }}</span>
                            <span class="timer-colon">:</span>
                            <span class="timer-segment">{{ minutes }}</span>
                            <span class="timer-colon">:</span>
                            <span class="timer-segment">{{ seconds }}</span>
                        </div>
                    </div>
                </div>

                <div class="flash-cyber-grid scroll-reveal">
                    <article class="flash-cyber-card" v-for="p in flashSaleProducts" :key="p.key_id" @click="router.push(`/san-pham/${p.id}?variant=${p.key_id}`)">

                        <!-- Image Box -->
                        <div class="flash-img-box">
                            <span class="flash-discount-badge" v-if="p.oldPriceNum > p.priceNum">
                                -{{ Math.round(((p.oldPriceNum - p.priceNum) / p.oldPriceNum) * 100) }}%
                            </span>
                            <img :src="p.img" :alt="p.fullName" loading="lazy"
                                 @error="handleImgError($event, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500')" />
                        </div>

                        <!-- Card Body -->
                        <div class="flash-card-body">
                            <!-- Name -->
                            <p class="flash-brand">{{ p.brandName }}</p>
                            <h3 class="flash-product-name">{{ p.name }}</h3>

                            <!-- Stock Progress -->
                            <div class="flash-stock-wrap">
                                <div class="flash-stock-labels">
                                    <span>Đã bán <strong>23</strong></span>
                                    <span>Còn <strong>{{ p.priceNum > 0 ? 7 : '?' }}</strong></span>
                                </div>
                                <div class="flash-progress-track">
                                    <div class="flash-progress-fill" style="width: 76%"></div>
                                </div>
                            </div>

                            <!-- Price + Cart -->
                            <div class="flash-bottom-row">
                                <div class="flash-price-block">
                                    <span class="flash-old-price" v-if="p.oldPriceNum > p.priceNum">{{ formatPrice(p.oldPriceNum) }}</span>
                                    <span class="flash-current-price" v-if="p.priceNum > 0">{{ formatPrice(p.priceNum) }}</span>
                                    <span class="flash-current-price" v-else>Liên Hệ</span>
                                </div>
                                <button class="flash-cart-btn" @click.stop="themVaoGioHang(p)" aria-label="Thêm vào giỏ hàng">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                    </article>
                </div>
            </div>
        </section>

        <!-- 3. PRODUCT CATEGORIES (Rich dark luxury grid backdrop) -->
        <section class="section category-section">
            <div class="grid-container">
                <div class="section-header scroll-reveal reveal-fade-up">
                    <div class="label-wrapper">
                        <span class="ambient-label">AFFILIATE VIDEO</span>
                        <h2>Video Review Từ Cộng Tác Viên</h2>
                        <p>Khám phá trải nghiệm thực tế, mẹo chọn máy và góc nhìn sử dụng từ cộng đồng NextGen.</p>
                    </div>
                </div>

                <div v-if="affiliateVideos.length" class="affiliate-video-slider-shell scroll-reveal reveal-stagger">
                    <button class="affiliate-slider-nav prev" type="button" aria-label="Video trước" @click="scrollAffiliateVideos(-1)">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div
                        ref="affiliateVideoSlider"
                        class="affiliate-video-showcase-grid"
                        @pointerdown="startAffiliateVideoDrag"
                        @pointermove="moveAffiliateVideoDrag"
                        @pointerup="stopAffiliateVideoDrag"
                        @pointercancel="stopAffiliateVideoDrag"
                        @pointerleave="stopAffiliateVideoDrag"
                    >
                        <article
                            v-for="video in affiliateVideos"
                            :key="video.id"
                            class="affiliate-video-card"
                            :class="{ 'is-playing-embed': affiliateVideoEmbedSrc(video) && hoveredAffiliateVideoId === video.id }"
                            @mouseenter="playAffiliateVideoPreview($event, video)"
                            @mouseleave="pauseAffiliateVideoPreview"
                            @click="enableAffiliateVideoSound($event, video)"
                        >
                            <video
                                v-if="isPlayableVideoSrc(affiliateVideoSrc(video))"
                                :src="affiliateVideoSrc(video)"
                                :poster="affiliateThumbnailSrc(video)"
                                muted
                                playsinline
                                loop
                                preload="metadata"
                            ></video>
                            <iframe
                                v-else-if="affiliateVideoEmbedSrc(video) && hoveredAffiliateVideoId === video.id"
                                class="affiliate-video-embed"
                                :src="affiliateVideoEmbedSrc(video, true, audibleAffiliateVideoIds.has(video.id))"
                                :title="video.title || video.tieu_de"
                                allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                allowfullscreen
                            ></iframe>
                            <img v-else :src="affiliateThumbnailSrc(video) || getCategoryFallbackImage(video.product?.tenSP)" :alt="video.title" />
                            <button
                                v-if="hoveredAffiliateVideoId === video.id && !audibleAffiliateVideoIds.has(video.id)"
                                class="affiliate-sound-toggle"
                                type="button"
                                @click.stop="enableAffiliateVideoSound($event, video)"
                            >
                                Bật tiếng
                            </button>
                            <div class="affiliate-video-scrim"></div>
                            <div class="affiliate-video-content">
                                <span class="affiliate-video-badge">Affiliate Video</span>
                                <h3>{{ video.title || video.tieu_de }}</h3>
                                <p>{{ video.product?.tenSP || 'Video review từ cộng tác viên NextGen' }}</p>
                                <div class="affiliate-creator-row">
                                    <span>{{ video.affiliate_user?.ten || video.affiliate_user?.name || 'NextGen Creator' }}</span>
                                    <button type="button" @click.stop="openAffiliateVideoProduct(video)">Xem sản phẩm</button>
                                </div>
                            </div>
                            <button class="affiliate-floating-product-btn" type="button" @click.stop="openAffiliateVideoProduct(video)">Xem sản phẩm</button>
                        </article>
                    </div>
                    <button class="affiliate-slider-nav next" type="button" aria-label="Video tiếp theo" @click="scrollAffiliateVideos(1)">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <div v-else class="category-cards-grid scroll-reveal reveal-stagger">
                    <div class="category-premium-card" v-for="c in categories" :key="c.id_danhmuc"
                        @click="router.push(getCategoryTarget(c))">
                        <div class="card-bg-image" :style="{ backgroundImage: 'url(' + getCategoryFallbackImage(c.ten_danhmuc) + ')' }"></div>
                        <div class="card-gradient-shield"></div>
                        <div class="category-card-content">
                            <h3>{{ c.ten_danhmuc }}</h3>
                            <p>{{ c.mota || 'Khám phá ngay cỗ máy lý tưởng phù hợp với phong cách của bạn.' }}</p>
                            <span class="interactive-anchor">
                                Xem Bộ Sưu Tập
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- 4. BEST SELLERS (Clean crisp white body background) -->
        <section class="section product-section">
            <div class="grid-container">
                <div class="section-header center scroll-reveal reveal-fade-up">
                    <div class="label-wrapper center">
                        <span class="ambient-label">SẢN PHẨM NỔI BẬT</span>
                        <h2>Siêu Phẩm Bán Chạy Nhất</h2>
                        <p>Tuyển chọn những cỗ máy dẫn đầu phân khúc với chất liệu bền bỉ và hiệu suất tối tân.</p>
                    </div>
                </div>

                <!-- Product Filters Tabs -->
                <div class="premium-tabs-strip scroll-reveal reveal-fade-up">
                    <button class="tab-pill" :class="{ active: activeCategoryTab === 'all' }" @click="changeProductTab('all')">
                        Tất Cả
                    </button>
                    <button class="tab-pill" :class="{ active: activeCategoryTab === 'gaming' }" @click="changeProductTab('gaming')">
                        Gaming Series
                    </button>
                    <button class="tab-pill" :class="{ active: activeCategoryTab === 'office' }" @click="changeProductTab('office')">
                        Văn Phòng & UltraBook
                    </button>
                    <button class="tab-pill" :class="{ active: activeCategoryTab === 'macbook' }" @click="changeProductTab('macbook')">
                        MacBook & Apple
                    </button>
                    <button class="tab-pill" :class="{ active: activeCategoryTab === 'creator' }" @click="changeProductTab('creator')">
                        Creator Pro
                    </button>
                </div>

                <!-- Best Sellers Grid (Sitting on clean white body background) -->
                <div class="premium-products-grid scroll-reveal">
                    <article class="premium-product-card light-card" v-for="p in filteredFeaturedProducts.slice(0, 8)" :key="p.key_id">
                        <div class="product-visuals" @click="router.push(`/san-pham/${p.id}?variant=${p.key_id}`)">
                            <span class="badge-tag" v-if="p.badge" :style="{ background: p.badgeColor }">{{ p.badge }}</span>
                            <div class="discount-pill" v-if="p.oldPriceNum > p.priceNum">
                                -{{ Math.round(((p.oldPriceNum - p.priceNum) / p.oldPriceNum) * 100) }}%
                            </div>
                            <img :src="p.img" :alt="p.fullName" class="product-main-img" loading="lazy" @error="handleImgError($event, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500')" />
                            
                            <button class="action-circle-btn wishlist-corner-btn" @click.stop="themVaoYeuThich(p)" title="Thêm vào yêu thích">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </button>

                            <!-- Hover Quick Menu -->
                            <div class="hover-action-overlay">
                                <button class="action-rect-btn" @click.stop="router.push(`/san-pham/${p.id}?variant=${p.key_id}`)">
                                    Xem nhanh ->
                                </button>
                            </div>
                        </div>

                        <div class="product-metadata">
                            <span class="brand-sub">{{ p.brandName }}</span>
                            <h3 class="product-item-title" @click="router.push(`/san-pham/${p.id}?variant=${p.key_id}`)" :title="p.fullName">
                                {{ p.fullName }}
                            </h3>

                            <!-- Rating stars -->
                            <div class="rating-strip">
                                <span class="stars">
                                    <span v-for="star in 5" :key="star" :style="{ color: star <= Math.round(p.rating_avg || 5) ? '#fbbf24' : '#cbd5e1' }">★</span>
                                </span>
                                <span class="rating-val" style="margin-left: 4px;">{{ p.rating_avg > 0 ? p.rating_avg.toFixed(1) : '5.0' }}</span>
                                <span class="rating-count">({{ p.rating_count || 0 }} đánh giá)</span>
                            </div>

                            <!-- Installment badge -->
                            <div class="card-indicators-row">
                                <span class="installment-badge text-success">Trả góp 0% chỉ từ {{ formatPrice(Math.round(p.priceNum / 12)) }}/th</span>
                            </div>

                            <!-- Specs badges -->
                            <div class="specs-pill-box">
                                <span class="spec-p-badge" v-for="(spec, idx) in p.specs.slice(0, 3)" :key="idx">
                                    <strong class="spec-lbl">{{ spec.label }}:</strong>
                                    <span class="spec-val">{{ spec.value }}</span>
                                </span>
                            </div>

                            <div class="product-pricing-strip">
                                <div class="price-stack">
                                    <span class="current-price" v-if="p.priceNum > 0">{{ formatPrice(p.priceNum) }}</span>
                                    <span class="current-price" v-else>Liên Hệ</span>
                                    <span v-if="p.oldPriceNum > p.priceNum" class="strike-price">{{ formatPrice(p.oldPriceNum) }}</span>
                                </div>
                                <button class="buy-button" @click="themVaoGioHang(p)" aria-label="Thêm vào giỏ hàng">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    <span>Mua</span>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="global-action-row">
                    <router-link to="/san-pham" class="btn btn-premium-glass">
                        Xem Tất Cả Sản Phẩm →
                    </router-link>
                </div>
            </div>
        </section>

        <!-- 5. FEATURED ECOSYSTEM (Rich dark technology setup) -->
        <section class="section ecosystem-section">
            <div class="grid-container">
                <div class="section-header center scroll-reveal reveal-fade-up">
                    <div class="label-wrapper center">
                        <span class="ambient-label">HỆ SINH THÁI CAO CẤP</span>
                        <h2>Kiến Tạo Góc Setup Trong Mơ</h2>
                        <p>Hoàn thiện không gian chiến game và làm việc chuyên sâu với các thiết bị ngoại vi đồng bộ cao cấp.</p>
                    </div>
                </div>

                <div class="bento-asymmetrical-grid scroll-reveal reveal-stagger">
                    <div class="bento-block block-xl" style="background-image: url('https://images.unsplash.com/photo-1593640408182-31c70c8268f5?w=1000')">
                        <div class="block-tint"></div>
                        <div class="bento-text">
                            <span class="bento-category-tag">TRUNG TÂM SETUP</span>
                            <h3>Không Gian Tối Giản</h3>
                            <p>Tối đa diện tích, đồng bộ cổng kết nối và tối ưu không gian đa nhiệm đỉnh cao.</p>
                            <router-link to="/san-pham?cat=phu-kien" class="bento-cta-link">Khám phá ngay ➔</router-link>
                        </div>
                    </div>

                    <div class="bento-block block-medium" style="background-image: url('https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600')">
                        <div class="block-tint"></div>
                        <div class="bento-text">
                            <span class="bento-category-tag">ÂM THANH</span>
                            <h3>Tai Nghe Chuẩn Studio</h3>
                            <p>Âm thanh vòm cinematic 7.1 tích hợp mic khử ồn AI thông minh.</p>
                            <router-link to="/san-pham?cat=phu-kien" class="bento-cta-link">Xem mẫu ➔</router-link>
                        </div>
                    </div>

                    <div class="bento-block block-medium" style="background-image: url('https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=600')">
                        <div class="block-tint"></div>
                        <div class="bento-text">
                            <span class="bento-category-tag">BÀN PHÍM CƠ</span>
                            <h3>Bàn Phím Custom NX</h3>
                            <p>Xúc giác cực nhạy, phản hồi tức thì với LED RGB tùy biến 16.8 triệu màu.</p>
                            <router-link to="/san-pham?cat=phu-kien" class="bento-cta-link">Sở hữu ngay ➔</router-link>
                        </div>
                    </div>

                    <div class="bento-block block-wide" style="background-image: url('https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800')">
                        <div class="block-tint"></div>
                        <div class="bento-text">
                            <span class="bento-category-tag">MÀN HÌNH</span>
                            <h3>Màn Hình Cực Đỉnh OLED</h3>
                            <p>Tần số quét 240Hz phản hồi siêu tốc, màu sắc chuẩn điện ảnh HDR chuyên nghiệp.</p>
                            <router-link to="/san-pham?cat=phu-kien" class="bento-cta-link">Xem màn hình ➔</router-link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. WHY CHOOSE US (Sitting on clean white body background) -->
        <section class="section values-section">
            <div class="grid-container">
                <div class="section-header center scroll-reveal reveal-fade-up">
                    <div class="label-wrapper center">
                        <span class="ambient-label">TIÊU CHUẨN DỊCH VỤ</span>
                        <h2>Giá Trị Xứng Tầm Thương Hiệu</h2>
                    </div>
                </div>

                <div class="values-cards-grid scroll-reveal reveal-stagger">
                    <div class="value-feature-card">
                        <div class="value-icon-shield">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h3>100% Chính Hãng</h3>
                        <p>Cam kết toàn bộ máy mới nguyên seal, đầy đủ chứng từ xuất xứ, hóa đơn VAT rõ ràng.</p>
                    </div>

                    <div class="value-feature-card">
                        <div class="value-icon-shield">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12H4s1 0-1-1a9 9 0 018-8v8H5zm6 0h5s-1 0 1-1a9 9 0 00-8-8v8h7z"/></svg>
                        </div>
                        <h3>Bảo Hành 24 Tháng</h3>
                        <p>An tâm tuyệt đối với chính sách bảo hành chính hãng lâu dài và quy trình sửa chữa siêu tốc.</p>
                    </div>

                    <div class="value-feature-card">
                        <div class="value-icon-shield">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <h3>Giao Hàng Siêu Tốc 2H</h3>
                        <p>Hỗ trợ giao hỏa tốc an toàn trong khu vực nội thành, được quyền kiểm tra máy tại chỗ trước khi nhận.</p>
                    </div>

                    <div class="value-feature-card">
                        <div class="value-icon-shield">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <h3>Tư Vấn Kỹ Thuật Chuyên Sâu</h3>
                        <p>Đội ngũ kỹ sư am hiểu phần cứng luôn sẵn sàng tối ưu hóa cỗ máy phù hợp với compile stack của bạn.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. DEEP TECH INSIGHTS (Sitting on clean soft-white background) -->
        <section class="section magazine-news-section">
            <div class="grid-container">
                <div class="section-header scroll-reveal reveal-fade-up">
                    <div class="label-wrapper">
                        <span class="ambient-label">KNOWLEDGE BASE</span>
                        <h2>Tech Insights Magazine</h2>
                        <p>Phân tích chuyên sâu về kiến trúc phần cứng, đánh giá hiệu năng đồ họa và định cấu hình máy trạm tối ưu.</p>
                    </div>
                    <RouterLink to="/tin-tuc" class="magazine-explore-btn">Xem tất cả bài viết ➔</RouterLink>
                </div>

                <div class="magazine-layout-grid scroll-reveal reveal-stagger">
                    <!-- Featured Article -->
                    <article class="magazine-main-article" v-if="latestNews.length > 0">
                        <div class="main-art-visual">
                            <img :src="newsImageUrl(latestNews[0].image)" :alt="latestNews[0].title" @error="handleImgError($event, newsPlaceholderImage)" />
                            <span class="art-badge-tag">{{ latestNews[0].category || 'Nổi bật' }}</span>
                        </div>
                        <div class="main-art-info">
                            <h3>{{ latestNews[0].title }}</h3>
                            <p>{{ latestNews[0].excerpt || 'Khám phá các bài phân tích sâu về hiệu năng và các công nghệ cốt lõi mới nhất.' }}</p>
                            <RouterLink :to="`/tin-tuc/${latestNews[0].id}`" class="art-deep-link">Xem chi tiết bài viết ➔</RouterLink>
                        </div>
                    </article>

                    <!-- Secondary articles list -->
                    <div class="magazine-secondary-column" v-if="latestNews.length > 1">
                        <article class="magazine-mini-article" v-for="n in latestNews.slice(1, 4)" :key="n.id">
                            <div class="mini-art-thumb">
                                <img :src="newsImageUrl(n.image)" :alt="n.title" @error="handleImgError($event, newsPlaceholderImage)" />
                            </div>
                            <div class="mini-art-info">
                                <span class="mini-tag">{{ n.category || 'Công nghệ' }}</span>
                                <h3>{{ n.title }}</h3>
                                <RouterLink :to="`/tin-tuc/${n.id}`">Đọc bài viết ➔</RouterLink>
                            </div>
                        </article>
                    </div>
                </div>
                <div v-if="latestNews.length === 0" class="no-articles-panel">
                    Hiện chưa có bài viết nào được đăng tải trên hệ thống dữ liệu.
                </div>
            </div>
        </section>

        <!-- 8. CUSTOMER REVIEWS (Sitting on clean white background) -->
        <section class="section reviews-slider-section">
            <div class="grid-container">
                <div class="section-header center scroll-reveal reveal-fade-up">
                    <div class="label-wrapper center">
                        <span class="ambient-label">Ý KIẾN KHÁCH HÀNG</span>
                        <h2>Đồng Hành Cùng Mọi Luồng Công Việc</h2>
                    </div>
                </div>

                <div class="reviews-editorial-grid scroll-reveal reveal-stagger">
                    <article class="editorial-review-card" v-for="(r, i) in reviews" :key="i">
                        <div class="stars-row">★★★★★</div>
                        <p class="review-quote">"{{ r.content }}"</p>
                        <div class="review-author-pill">
                            <img :src="r.avatar" :alt="r.name" class="reviewer-avatar" />
                            <div class="reviewer-meta">
                                <strong>{{ r.name }}</strong>
                                <span>{{ r.role }}</span>
                            </div>
                            <span class="verified-token">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                Đã Mua
                            </span>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- 9. CYBER ECOSYSTEM NEWSLETTER CTA (Dynamic dark container sitting on white background) -->
        <section class="cyber-newsletter-section">
            <div class="grid-container scroll-reveal reveal-scale">
                <div class="newsletter-neon-box">
                    <div class="newsletter-bg-glow"></div>
                    <div class="newsletter-layout">
                        <div class="newsletter-headline">
                            <span class="ambient-label light">KẾT NỐI HỆ THỐNG</span>
                            <h2>Dẫn Đầu Xu Hướng Công Nghệ</h2>
                            <p>Nhận ngay bản tin phân tích phần cứng mới nhất, thông tin ưu đãi private độc quyền và mã giảm giá VIP sớm nhất.</p>
                        </div>
                        <div class="newsletter-interactive-form">
                            <div class="input-glow-group">
                                <input type="email" placeholder="Nhập địa chỉ email của bạn" aria-label="Địa chỉ email đăng ký" />
                                <button class="btn btn-premium-glow">Đăng Ký</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


    </main>
  </div>
</template>

<style scoped>

/* ─── ENTERPRISE HYBRID "DARK STORYTELLING & WHITE BODY" DESIGN SYSTEM ─── */

.premium-theme {
    /* Premium Redesigned Tokens */
    --bg-primary-dark: #071426;
    --bg-secondary-dark: #0B1B31;
    --bg-card-dark: #10243D;
    
    --bg-primary-light: #ffffff;
    --bg-secondary-light: #ffffff;
    --bg-card-light: #ffffff;
    
    --accent-blue: #2563EB;
    --accent-cyan: #3B82F6;
    
    --text-dark: #0f172a;
    --text-muted-dark: #64748b;
    --text-light: #f8fafc;
    --text-muted-light: #94a3b8;

    /* Legacy compatibility mappings */
    --col-primary: var(--tn-bg);
    --col-secondary: #f8fafc;
    --col-accent: var(--accent-blue);
    --col-highlight: var(--accent-cyan);
    --col-success: #2563EB;
    --col-warning: #F59E0B;
    --col-text: var(--tn-text);
    --col-muted: var(--tn-text-muted);
    --col-border: var(--tn-border);
    --glass-bg-dark: rgba(17, 24, 39, 0.7);
    --glass-bg-light: rgba(255, 255, 255, 0.05);
    
    background-color: var(--tn-bg);
    color: var(--tn-text);
    font-family: 'Be Vietnam Pro', sans-serif;
    overflow-x: hidden;
}

/* ─── FLASH SALE LIGHT CONVERSION ─── */
.flashsale-section {
    background: var(--tn-surface) !important;
    border-top: 1px solid var(--tn-border);
    border-bottom: 1px solid var(--tn-border);
}
.flashsale-section h2 {
    color: var(--tn-text) !important;
}
.flashsale-section p {
    color: var(--tn-text-muted) !important;
}
.flex-row-align {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 32px;
}
.flash-badge {
    background: rgba(37, 99, 235, 0.08) !important;
    border-color: rgba(37, 99, 235, 0.25) !important;
    color: var(--accent-blue) !important;
}
.countdown-clock {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--tn-surface);
    padding: 8px 16px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    border: 1px solid var(--tn-border);
}
.timer-segment {
    font-size: 20px;
    font-weight: 800;
    color: var(--tn-text);
    font-family: monospace !important;
    min-width: 2.2ch;
    text-align: center;
    background: var(--tn-surface-2);
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid var(--tn-border);
}
.timer-colon {
    font-size: 18px;
    font-weight: 800;
    color: var(--accent-blue);
    animation: flash-pulse 1s infinite;
}
@keyframes flash-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

/* ─── FLASH CYBER CARD GRID ─── */
.flash-cyber-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}
@media (max-width: 1024px) { .flash-cyber-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px)  { .flash-cyber-grid { grid-template-columns: 1fr; } }

.flash-cyber-card {
    background: var(--tn-surface);
    border-radius: 16px;
    border: 1px solid var(--tn-border);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}
.flash-cyber-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(37, 99, 235, 0.08);
    border-color: rgba(37, 99, 235, 0.35);
}

/* Image Box — crisp white square container */
.flash-img-box {
    position: relative;
    background: var(--tn-bg);
    border-radius: 12px;
    margin: 14px 14px 0;
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.flash-img-box img {
    max-width: 85%;
    max-height: 85%;
    object-fit: contain;
    display: block;
    transition: transform 0.3s ease;
}
.flash-cyber-card:hover .flash-img-box img {
    transform: scale(1.04);
}

/* Red discount badge */
.flash-discount-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #ef4444;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.5px;
    padding: 3px 9px;
    border-radius: 6px;
    z-index: 2;
    line-height: 1.6;
}

/* Card Body */
.flash-card-body {
    padding: 14px 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}
.flash-brand {
    font-size: 11px;
    font-weight: 600;
    color: var(--tn-text-muted);
    text-transform: capitalize;
    letter-spacing: 0.8px;
    margin: 0;
}
.flash-product-name {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--tn-text);
    line-height: 1.4;
    margin: 0;
    min-height: 38px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    white-space: normal;
    word-break: break-word;
}

/* Stock progress */
.flash-stock-wrap {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.flash-stock-labels {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: var(--tn-text-muted);
}
.flash-stock-labels strong {
    color: var(--tn-text);
    font-weight: 700;
}
.flash-progress-track {
    width: 100%;
    height: 5px;
    background: rgba(15, 23, 42, 0.06);
    border-radius: 99px;
    overflow: hidden;
}
.flash-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
    border-radius: 99px;
    transition: width 0.5s ease;
}

/* Bottom row: price + cart button */
.flash-bottom-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 8px;
    margin-top: auto;
    padding-top: 4px;
}
.flash-price-block {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.flash-old-price {
    font-size: 11px;
    color: var(--tn-text-muted);
    text-decoration: line-through;
    font-weight: 500;
}
.flash-current-price {
    font-size: 16px;
    font-weight: 800;
    color: #ef4444;
    line-height: 1.2;
}
.flash-cart-btn {
    width: 38px;
    height: 38px;
    min-width: 38px;
    border-radius: 10px;
    background: var(--tn-surface-2);
    border: 1px solid var(--tn-border);
    color: var(--tn-text);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s ease, transform 0.15s ease;
    flex-shrink: 0;
}
.flash-cart-btn:hover {
    background: var(--accent-blue);
    color: #ffffff;
    border-color: var(--accent-blue);
    transform: scale(1.08);
}

/* ─── REDESIGNED HIGH-CONTRAST LIGHT PRODUCT CARDS ─── */
.premium-product-card.light-card {
    background: var(--bg-card-light) !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03) !important;
}
.premium-product-card.light-card:hover {
    border-color: rgba(37, 99, 235, 0.25) !important;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08) !important;
}
.light-card .product-visuals {
    min-height: 230px;
    background: var(--tn-bg) !important;
    border-bottom: 1px solid var(--tn-border);
}
.light-card .product-main-img {
    width: calc(100% - 28px);
    height: calc(100% - 28px);
    object-fit: cover;
    border-radius: 14px;
}
.light-card .brand-sub {
    color: var(--text-muted-dark) !important;
}
.light-card .product-item-title {
    color: var(--text-dark) !important;
}
.light-card .product-item-title:hover {
    color: var(--accent-blue) !important;
}
.light-card .spec-p-badge {
    background: var(--tn-surface-2) !important;
    border: 1px solid var(--tn-border) !important;
}
.light-card .spec-lbl {
    color: var(--text-muted-dark) !important;
}
.light-card .spec-val {
    color: var(--text-dark) !important;
}
.light-card .strike-price {
    color: var(--text-muted-dark) !important;
}
.light-card .buy-button {
    background: var(--accent-blue) !important;
}
.light-card .buy-button:hover {
    background: #1d4ed8 !important;
    box-shadow: 0 4px 15px rgba(37,99,235,0.25) !important;
}

/* ─── RATING STRIPS ─── */
.rating-strip {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
}
.rating-strip .stars {
    color: #f59e0b;
    font-size: 13px;
    letter-spacing: 1px;
}
.rating-strip .rating-val {
    font-size: 12px;
    font-weight: 800;
    color: var(--text-dark);
}
.rating-strip .rating-count {
    font-size: 11px;
    color: var(--text-muted-dark);
}

/* ─── CARD INDICATORS (INSTALLMENTS & PROGRESS) ─── */
.card-indicators-row {
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.installment-badge {
    display: inline-flex;
    align-self: flex-start;
    font-size: 10.5px;
    font-weight: 800;
    color: #2563EB !important;
    background: rgba(37, 99, 235, 0.06);
    border: 1px solid rgba(37, 99, 235, 0.18);
    padding: 3px 8px;
    border-radius: 6px;
}
.sale-progress-bar {
    width: 100%;
    height: 14px;
    background: #e2e8f0;
    border-radius: 99px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #ef4444 0%, #f97316 100%);
    border-radius: 99px;
}
.progress-txt {
    position: absolute;
    width: 100%;
    text-align: center;
    font-size: 9px;
    font-weight: 800;
    color: #ffffff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
    text-transform: capitalize;
    letter-spacing: 0.05em;
}
.flash-sale-tag {
    background: #ef4444 !important;
}


.grid-container {
    width: min(1280px, calc(100% - 32px));
    margin: 0 auto;
}

/* ─── TICKER PROMO (Always Premium Dark Luxury themed) ─── */
.ticker-bar {
    background: var(--tn-surface);
    border-bottom: 1px solid #e2e8f0;
    height: 26px;
    padding: 0;
    overflow: hidden;
    display: flex;
    align-items: center;
}
.ticker-track {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    font-size: 10.5px;
    line-height: 1;
    font-weight: 800;
    letter-spacing: 0.12em;
    color: #334155;
    min-width: max-content;
    height: 26px;
    will-change: transform;
    animation: run-ticker 34s linear infinite;
}
.ticker-item {
    display: inline-flex;
    align-items: center;
    height: 26px;
    gap: 10px;
    padding-right: 32px;
}
.ticker-item > span {
    display: inline-flex;
    align-items: center;
}
.ticker-label {
    height: 26px;
    line-height: 1;
    align-items: center;
    padding-top: 1px;
}
.ticker-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--tn-primary);
    flex: 0 0 auto;
    width: 14px;
    height: 26px;
}
.ticker-icon-wrap :deep(.ticker-code-icon) {
    width: 13px;
    height: 13px;
    display: block;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.ticker-dot {
    color: var(--tn-primary);
    margin-left: 22px;
    height: 26px;
    line-height: 1;
    align-items: center;
    padding-top: 1px;
}
@keyframes run-ticker {
    from { transform: translateX(0); }
    to { transform: translateX(-50%); }
}

@keyframes hero-bg-pan {
    0% {
        transform: scale(1.04) translateX(-14px);
    }
    50% {
        transform: scale(1.07) translateX(14px);
    }
    100% {
        transform: scale(1.05) translateX(0);
    }
}

/* ─── 1. CINEMATIC HERO SECTION (Always Premium Dark Luxury themed) ─── */
.hero-viewport {
    background-color: #0B1220;
    position: relative;
    min-height: calc(100vh - 150px);
    display: flex;
    align-items: center;
    padding: 72px 0 56px;
    overflow: hidden;
    color: #ffffff;
}
.hero-bg-wrapper {
    position: absolute;
    inset: 0;
    z-index: 0;
}
.hero-bg-img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: brightness(0.92) saturate(1.08) contrast(1.02);
    transition: all 1s ease-in-out;
    animation: hero-bg-pan 6s ease-in-out both;
    transform-origin: center;
}
.hero-bg-video {
    pointer-events: none;
}
.hero-overlay-curtain {
    position: absolute;
    inset: 0;
    background: 
        linear-gradient(90deg, 
            rgba(11, 18, 32, 0.42) 0%,
            rgba(11, 18, 32, 0.24) 36%,
            rgba(11, 18, 32, 0.08) 72%,
            rgba(11, 18, 32, 0.02) 100%),
        radial-gradient(circle at 85% 45%, rgba(37, 99, 235, 0.08) 0%, transparent 56%),
        linear-gradient(to top, rgba(11, 18, 32, 0.35) 0%, transparent 18%);
    z-index: 1;
}
.hero-container {
    position: relative;
    z-index: 2;
    width: min(calc(100% - 96px), 1280px);
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.hero-content {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(420px, 0.95fr);
    gap: clamp(48px, 6vw, 104px);
    align-items: start;
    width: 100%;
}
.hero-text-block {
    max-width: 720px;
    justify-self: start;
}
.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 99px;
    background: rgba(37, 99, 235, 0.1);
    border: 1px solid rgba(37, 99, 235, 0.25);
    color: var(--col-highlight);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.1em;
    margin-bottom: 16px;
}
.badge-glow-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--col-highlight);
    box-shadow: 0 0 10px var(--col-highlight);
    animation: blink-pulse 2s infinite;
}
@keyframes blink-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
}
.hero-title {
    font-size: clamp(38px, 4vw, 52px);
    font-weight: 800;
    line-height: 1.06;
    letter-spacing: -0.03em;
    margin-bottom: 16px;
    color: #ffffff;
}
.gradient-text {
    display: block;
    color: #38bdf8;
    -webkit-text-fill-color: currentColor;
    text-shadow: 0 8px 26px rgba(56, 189, 248, 0.24);
}
.hero-description {
    font-size: 15px;
    line-height: 1.65;
    color: #94A3B8;
    margin-bottom: 24px;
}
.hero-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 28px;
}
.hero-trust-indicators {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding-top: 18px;
}
.trust-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #94A3B8;
    font-size: 13px;
    font-weight: 600;
}
.trust-pill svg {
    width: 18px;
    height: 18px;
    color: var(--col-highlight);
}

/* Device showcase display */
.hero-device-wrapper {
    position: relative;
    display: flex;
    justify-content: flex-end;
    align-items: flex-start;
    justify-self: end;
    width: 100%;
}
.glow-orb {
    position: absolute;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(37,99,235,0.2) 0%, transparent 70%);
    filter: blur(40px);
    z-index: 0;
}
.device-showcase-card {
    position: relative;
    z-index: 1;
    border-radius: 28px;
    overflow: visible;
    border: 1px solid rgba(34, 211, 238, 0.15);
    box-shadow:
        0 30px 60px rgba(0, 0, 0, 0.5),
        0 0 30px rgba(34, 211, 238, 0.06);
    background: rgba(11, 19, 32, 0.82);
    backdrop-filter: blur(16px);
    animation: slow-floating 8s ease-in-out infinite;
    width: min(100%, 510px);
}

.hero-product-card {
    padding: 16px;
    animation: none;
}

.hero-product-visual {
    position: relative;
    height: 238px;
    min-height: 238px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 18px;
    background: linear-gradient(145deg, rgba(248, 250, 252, 0.08), rgba(15, 23, 42, 0.5));
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.hero-product-visual img {
    position: relative;
    z-index: 1;
    width: calc(100% - 36px);
    height: calc(100% - 36px);
    max-width: none;
    max-height: none;
    object-fit: cover;
    border-radius: 14px;
    display: block;
    mix-blend-mode: normal;
    filter: drop-shadow(0 16px 24px rgba(0, 0, 0, 0.34));
    transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.device-showcase-card:hover .hero-product-visual img {
    transform: scale(1.05);
}

.hero-product-info {
    position: relative;
    z-index: 4;
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 16px;
    padding: 0;
    border: none;
    background: transparent;
    backdrop-filter: none;
    box-shadow: none;
}

.hero-product-copy {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.hero-product-brand {
    color: #3b82f6 !important;
    font-size: 10px;
    font-weight: 700;
    text-transform: capitalize;
    letter-spacing: .08em;
    display: block;
    -webkit-text-fill-color: currentColor;
    text-shadow: none;
}

.hero-product-title {
    color: #ffffff !important;
    font-size: 14px;
    line-height: 1.35;
    font-weight: 700;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 2px 0 6px 0;
    -webkit-text-fill-color: currentColor;
    text-shadow: none;
}

.hero-product-name-text {
    color: #ffffff !important;
}

.hero-title-spec-inline {
    font-size: 12px;
    font-weight: 600;
    color: #60a5fa !important;
    background: rgba(37, 99, 235, 0.2);
    border: 1px solid rgba(59, 130, 246, 0.4);
    padding: 2px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    -webkit-text-fill-color: currentColor;
}

.hero-product-specs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 4px 0;
}

.spec-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(30, 41, 59, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.05);
    color: #94a3b8;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 500;
    line-height: 1;
}

.spec-tag .spec-icon {
    width: 11px;
    height: 11px;
    color: #3b82f6;
    flex-shrink: 0;
}

.hero-product-price {
    font-size: 18px;
    color: #f97316 !important;
    font-weight: 700;
    font-style: normal;
    margin-top: 2px;
    display: block;
    -webkit-text-fill-color: currentColor;
    text-shadow: none;
}

.hero-checkout-btn {
    position: relative;
    min-height: 40px;
    padding: 0 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #2563eb;
    color: #ffffff;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.hero-checkout-btn:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}

.hero-checkout-btn svg {
    width: 12px;
    height: 12px;
    transition: transform 0.2s ease;
}

.hero-checkout-btn:hover svg {
    transform: translateX(3px);
}

.hero-product-bottom-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    width: 100%;
    margin-top: 8px;
}

@keyframes slow-floating {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-12px); }
}

.ambient-card {
    position: absolute;
    z-index: 2;
    background: rgba(17, 24, 39, 0.65);
    border: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(12px);
    padding: 12px 18px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    animation: slow-floating 8s ease-in-out infinite;
}

.device-showcase-card .float-top {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 5;
    background: rgba(15, 23, 42, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    padding: 6px 12px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    animation: none;
}

.device-showcase-card .float-top span {
    display: block;
    font-size: 8px;
    color: #f97316;
    font-weight: 700;
    text-transform: capitalize;
    letter-spacing: 0.05em;
    line-height: 1.2;
}

.device-showcase-card .float-top strong {
    display: block;
    font-size: 11px;
    color: #ffffff;
    font-weight: 700;
    line-height: 1.2;
    margin-top: 1px;
}

.hero-product-bottom-row .float-bottom {
    position: static;
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(30, 41, 59, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    padding: 6px 12px;
    border-radius: 10px;
    box-shadow: none;
    animation: none;
}

.hero-product-bottom-row .float-bottom .badge-icon {
    width: 18px;
    height: 18px;
    color: #3b82f6;
    flex-shrink: 0;
}

.hero-product-bottom-row .float-bottom .badge-text {
    display: flex;
    flex-direction: column;
}

.hero-product-bottom-row .float-bottom .badge-text span {
    font-size: 8px;
    color: #94a3b8;
    text-transform: capitalize;
    letter-spacing: 0.05em;
    line-height: 1.2;
    display: block;
}

.hero-product-bottom-row .float-bottom .badge-text strong {
    font-size: 11px;
    color: #ffffff;
    font-weight: 700;
    line-height: 1.2;
    display: block;
}

.hero-navigation {
    margin-top: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
}
.arrow-control {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(17, 24, 39, 0.65);
    border: 1px solid rgba(255,255,255,0.08);
    color: #ffffff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(8px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.arrow-control svg {
    width: 18px;
    height: 18px;
}
.arrow-control:hover {
    background: var(--col-accent);
    border-color: var(--col-accent);
    box-shadow: 0 0 20px rgba(37,99,235,0.4);
}
.bullet-dots {
    display: flex;
    gap: 8px;
}
.bullet-dots span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    cursor: pointer;
    transition: all 0.3s ease;
}
.bullet-dots span.active {
    width: 28px;
    border-radius: 99px;
    background: var(--col-highlight);
}

/* ─── 2. TRUST BAR (Always Premium Dark Luxury themed) ─── */
.trust-bar-section {
    background: var(--bg-secondary-dark);
    border-top: 1px solid rgba(255,255,255,0.05);
    border-bottom: 1px solid rgba(255,255,255,0.05);
    padding: 0;
}
.trust-bar-section .grid-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}
.trust-card {
    padding: 36px 20px;
    text-align: center;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
}
.trust-card:last-child {
    border-right: none;
}
.trust-card h3 {
    font-size: 32px;
    font-weight: 800;
    color: var(--col-highlight);
    margin: 0 0 4px;
}
.trust-card p {
    font-size: 13px;
    color: #cbd5e1;
    font-weight: 700;
    margin: 0;
    text-transform: capitalize;
    letter-spacing: 0.05em;
}

/* ─── SECTION STYLES ─── */
.section {
    padding: 96px 0;
}
.section-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 32px;
    margin-bottom: 56px;
}
.section-header.center {
    justify-content: flex-start;
    text-align: left;
}
.label-wrapper {
    max-width: 680px;
}
.label-wrapper.center {
    margin: 0;
}
.ambient-label {
    display: inline-flex;
    padding: 4px 12px;
    border-radius: 4px;
    background: rgba(37,99,235,0.05);
    border: 1px solid rgba(37,99,235,0.12);
    color: var(--col-accent);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.15em;
    margin-bottom: 14px;
}
.ambient-label.light {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.15);
    color: #ffffff;
}
.section-header h2 {
    font-size: 38px;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin-bottom: 12px;
    color: var(--col-text);
}
.section-header p {
    font-size: 15px;
    line-height: 1.7;
    color: var(--col-muted);
    margin: 0;
}



.section-header :is(h1, h2, h3),
.ambient-label,
.tab-pill,
.interactive-anchor,
.btn-premium-glow,
.btn-premium-glass {
    text-transform: lowercase !important;
}

.section-header :is(h1, h2, h3)::first-letter,
.ambient-label::first-letter,
.tab-pill::first-letter,
.interactive-anchor::first-letter,
.btn-premium-glow::first-letter,
.btn-premium-glass::first-letter {
    text-transform: uppercase !important;
}

/* ─── 3. PRODUCT CATEGORIES (Light Theme Conversion) ─── */
.category-section {
    background: var(--tn-bg);
}
.category-section h2 {
    color: var(--tn-text) !important;
}
.category-section p {
    color: var(--tn-text-muted) !important;
}
.category-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}
.affiliate-video-slider-shell {
    position: relative;
    margin: 0;
    padding: 0;
    overflow: hidden;
}
.affiliate-video-showcase-grid {
    display: flex;
    gap: 24px;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 4px 0 24px;
    scroll-behavior: smooth;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
    cursor: grab;
    user-select: none;
    touch-action: pan-x;
    scroll-padding-left: 0;
}
.affiliate-video-showcase-grid::-webkit-scrollbar {
    display: none;
}
.affiliate-video-showcase-grid.is-dragging {
    cursor: grabbing;
    scroll-snap-type: none;
    scroll-behavior: auto;
}
.affiliate-slider-nav {
    position: absolute;
    top: 50%;
    z-index: 8;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 1px solid rgba(191,219,254,0.95);
    background: rgba(255,255,255,0.94);
    color: #2563eb;
    display: grid;
    place-items: center;
    cursor: pointer;
    box-shadow: 0 14px 34px rgba(15,23,42,0.16);
    transform: translateY(-50%);
    transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
}
.affiliate-slider-nav:hover {
    background: #ffffff;
    box-shadow: 0 18px 42px rgba(37,99,235,0.24);
}
.affiliate-slider-nav.prev {
    left: 0;
}
.affiliate-slider-nav.next {
    right: 0;
}
.affiliate-slider-nav svg {
    width: 22px;
    height: 22px;
}
.affiliate-video-card {
    flex: 0 0 calc((100% - 72px) / 4);
    height: clamp(360px, 27vw, 430px);
    border-radius: 22px;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(37,99,235,0.14);
    background: #0f172a;
    box-shadow: 0 18px 45px rgba(15,23,42,0.12);
    cursor: default;
    scroll-snap-align: start;
    transition: transform .35s ease, box-shadow .35s ease, border-color .35s ease;
}
.affiliate-video-card:hover {
    transform: none;
    border-color: rgba(37,99,235,0.42);
    box-shadow: 0 24px 60px rgba(37,99,235,0.18);
}
.affiliate-video-card video,
.affiliate-video-card img,
.affiliate-video-embed {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border: 0;
}
.affiliate-video-embed {
    position: absolute;
    inset: 0;
    z-index: 5;
    pointer-events: auto;
}
.affiliate-sound-toggle {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 9;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(15, 23, 42, 0.78);
    color: #ffffff;
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 800;
    line-height: 1;
    cursor: pointer;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 24px rgba(2, 6, 23, 0.26);
    transition: background 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
}
.affiliate-sound-toggle:hover {
    background: #2563eb;
    border-color: #60a5fa;
    transform: translateY(-1px);
}
.affiliate-video-scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(2,6,23,0.9) 0%, rgba(2,6,23,0.15) 58%, rgba(37,99,235,0.1) 100%);
    z-index: 1;
    transition: opacity .2s ease;
}
.affiliate-play-mark {
    position: absolute;
    top: 22px;
    right: 22px;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: rgba(255,255,255,0.92);
    color: #2563eb;
    display: grid;
    place-items: center;
    z-index: 2;
    box-shadow: 0 10px 30px rgba(15,23,42,0.22);
    transition: opacity .2s ease, transform .2s ease;
}
.affiliate-play-mark svg {
    width: 21px;
    height: 21px;
    transform: translateX(1px);
}
.affiliate-video-content {
    position: absolute;
    left: 22px;
    right: 22px;
    bottom: 22px;
    z-index: 3;
    color: #ffffff;
    transition: opacity .2s ease, transform .2s ease;
}
.affiliate-video-card.is-playing-embed .affiliate-video-scrim,
.affiliate-video-card.is-playing-embed .affiliate-play-mark,
.affiliate-video-card.is-playing-embed .affiliate-video-content {
    opacity: 0;
    pointer-events: none;
}
.affiliate-video-card.is-playing-embed .affiliate-play-mark,
.affiliate-video-card.is-playing-embed .affiliate-video-content {
    transform: translateY(6px);
}
.affiliate-floating-product-btn {
    position: absolute;
    right: 22px;
    bottom: 18px;
    z-index: 7;
    border: 0;
    border-radius: 10px;
    background: #ffffff;
    color: #2563eb;
    font-size: 12px;
    font-weight: 900;
    padding: 9px 12px;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 12px 28px rgba(15,23,42,0.18);
    opacity: 0;
    pointer-events: none;
    transform: translateY(6px);
    transition: opacity .2s ease, transform .2s ease;
}
.affiliate-video-card.is-playing-embed .affiliate-floating-product-btn {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}
.affiliate-video-badge {
    display: inline-flex;
    align-items: center;
    height: 24px;
    padding: 0 10px;
    border-radius: 99px;
    background: rgba(37,99,235,0.92);
    font-size: 10px;
    font-weight: 900;
    text-transform: capitalize;
    letter-spacing: .08em;
    margin-bottom: 12px;
}
.affiliate-video-content h3 {
    font-size: 22px;
    line-height: 1.18;
    margin: 0 0 9px;
    color: #ffffff;
}
.affiliate-video-content p {
    color: rgba(226,232,240,0.86) !important;
    font-size: 13px;
    line-height: 1.45;
    min-height: 38px;
    margin-bottom: 16px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.affiliate-creator-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.affiliate-creator-row span {
    min-width: 0;
    color: #bfdbfe;
    font-size: 12px;
    font-weight: 800;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.affiliate-creator-row button {
    border: 0;
    border-radius: 10px;
    background: #ffffff;
    color: #2563eb;
    font-size: 12px;
    font-weight: 900;
    padding: 9px 12px;
    cursor: pointer;
    white-space: nowrap;
}
.category-premium-card {
    height: 380px;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    border: 1px solid rgba(255,255,255,0.03);
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.card-bg-image {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 1;
}
.card-gradient-shield {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(7,12,22,0.9) 0%, rgba(7,12,22,0.2) 60%, transparent 100%);
    transition: all 0.5s ease;
    z-index: 2;
}
.category-premium-card:hover {
    transform: translateY(-8px);
    border-color: rgba(37,99,235,0.4);
    box-shadow: 0 20px 40px rgba(37,99,235,0.15);
}
.category-premium-card:hover .card-bg-image {
    transform: scale(1.08); /* Zoom nhẹ 3D mang lại cảm giác cực sống động, sang trọng */
}
.category-premium-card:hover .card-gradient-shield {
    background: linear-gradient(to top, rgba(7,12,22,0.95) 0%, rgba(37,99,235,0.25) 100%);
}
.category-card-content {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 32px;
    z-index: 3;
}
.category-card-content h3 {
    font-size: 22px;
    font-weight: 800;
    margin: 0 0 10px;
    color: #ffffff;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}
.category-card-content p {
    font-size: 13.5px;
    line-height: 1.6;
    color: #94A3B8;
    margin-bottom: 20px;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.4s ease;
}
.category-premium-card:hover .category-card-content p {
    opacity: 1;
    transform: translateY(0);
}
.interactive-anchor {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--col-highlight);
    font-size: 13px;
    font-weight: 700;
    opacity: 0;
    transform: translateX(-10px);
    transition: all 0.4s ease 0.1s;
}
.interactive-anchor svg {
    width: 14px;
    height: 14px;
}
.category-premium-card:hover .interactive-anchor {
    opacity: 1;
    transform: translateX(0);
}

/* ─── 4. BEST SELLERS (Clean crisp white body background) ─── */
.product-section {
    background: var(--bg-secondary-light);
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
}
.product-section h2 {
    color: var(--text-dark) !important;
}
.product-section p {
    color: var(--text-muted-dark) !important;
}
.premium-tabs-strip {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 48px;
}

.tab-pill {
    background: var(--tn-surface);
    border: 1px solid #cbd5e1;
    color: var(--text-muted-dark);
    padding: 10px 22px;
    border-radius: 99px;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.tab-pill:hover {
    color: var(--text-dark);
    border-color: var(--accent-blue);
}
.tab-pill.active {
    background: var(--accent-blue);
    border-color: var(--accent-blue);
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(37,99,235,0.15);
}

.premium-products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}
.premium-product-card {
    background: #111f35;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.05);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.01);
}
.premium-product-card:hover {
    transform: translateY(-8px);
    border-color: rgba(255,255,255,0.07);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.04);
}
.product-visuals {
    position: relative;
    height: 220px;
    background: #0d1b2e;
    overflow: hidden;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.product-main-img {
    height: 80%;
    width: 80%;
    object-fit: contain;
    transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.premium-product-card.light-card .product-visuals {
    height: 230px;
}
.premium-product-card.light-card .product-main-img {
    width: calc(100% - 28px);
    height: calc(100% - 28px);
    object-fit: cover;
    border-radius: 14px;
}
.premium-product-card:hover .product-main-img {
    transform: scale(1.08);
}
.badge-tag {
    position: absolute;
    top: 14px;
    left: 14px;
    font-size: 10px;
    font-weight: 800;
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    z-index: 2;
    letter-spacing: 0.05em;
}
.discount-pill {
    position: absolute;
    top: 14px;
    right: 60px;
    background: var(--col-warning);
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    padding: 4px 8px;
    border-radius: 4px;
    z-index: 2;
}
.hover-action-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.24);
    display: block;
    padding: 0;
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 3;
}
.product-visuals:hover .hover-action-overlay {
    opacity: 1;
}
.action-circle-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #111f35;
    border: 1px solid var(--col-border);
    color: var(--col-text);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
}
.wishlist-corner-btn {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 4;
    opacity: 0;
    transform: translateY(-4px);
    background: #0f172a;
    border-color: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
}
.product-visuals:hover .wishlist-corner-btn {
    opacity: 1;
    transform: translateY(0);
}
.action-circle-btn svg {
    width: 18px;
    height: 18px;
}
.action-circle-btn:hover {
    background: #ff4d4f;
    border-color: #ff4d4f;
    color: white;
}
.action-rect-btn {
    position: absolute;
    left: 24px;
    bottom: 24px;
    background: transparent;
    color: #ffffff;
    padding: 0;
    border-radius: 0;
    font-size: 15px;
    font-weight: 750;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: none;
    text-shadow: 0 2px 8px rgba(15, 23, 42, 0.35);
}
.action-rect-btn:hover {
    background: transparent;
    color: var(--accent-blue);
    text-decoration: underline;
    text-underline-offset: 4px;
}

.product-metadata {
    padding: 24px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.brand-sub {
    font-size: 11px;
    font-weight: 800;
    color: var(--col-muted);
    text-transform: capitalize;
    letter-spacing: 0.1em;
    margin-bottom: 6px;
}
.product-item-title {
    font-size: 13px;
    font-weight: 800;
    line-height: 17px;
    color: var(--col-text);
    margin: 0 0 12px;
    cursor: pointer;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 34px;
    min-height: 34px;
    max-height: 34px;
    white-space: normal;
    word-break: break-word;
    transition: color 0.2s;
}
.product-item-title:hover {
    color: var(--col-accent);
}
.specs-pill-box {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 20px;
    height: 48px;
    align-content: flex-start;
    overflow: hidden;
}
.spec-p-badge {
    background: #111f35;
    border: 1px solid rgba(255,255,255,0.07);
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 10px;
}
.spec-lbl {
    color: var(--col-muted);
    font-weight: 600;
    margin-right: 2px;
}
.spec-val {
    color: var(--col-text);
    font-weight: 700;
}
.product-pricing-strip {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.price-stack {
    display: flex;
    flex-direction: column;
}
.current-price {
    font-size: 17px;
    font-weight: 800;
    color: var(--col-accent);
}
.strike-price {
    font-size: 11px;
    color: var(--col-muted);
    text-decoration: line-through;
    margin-top: 1px;
}
.buy-button {
    background: var(--col-accent);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.buy-button svg {
    width: 14px;
    height: 14px;
}
.buy-button:hover {
    background: #1d4ed8;
    box-shadow: 0 4px 15px rgba(37,99,235,0.25);
}

.global-action-row {
    margin-top: 48px;
    display: flex;
    justify-content: center;
}
.global-action-row .btn-premium-glass {
    color: #000000 !important;
    border-color: rgba(0, 0, 0, 0.15);
    background: rgba(0, 0, 0, 0.04);
}
.global-action-row .btn-premium-glass:hover {
    background: rgba(0, 0, 0, 0.08);
    border-color: rgba(0, 0, 0, 0.3);
    transform: translateY(-2px);
}



/* ─── 5. FEATURED ECOSYSTEM (Light Background Conversion) ─── */
.ecosystem-section {
    background: var(--tn-bg);
}
.ecosystem-section h2 {
    color: var(--tn-text) !important;
}
.ecosystem-section p {
    color: var(--tn-text-muted) !important;
}
.bento-asymmetrical-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    grid-template-rows: 250px 250px;
    gap: 24px;
}
.bento-block {
    border-radius: 24px;
    background-size: cover;
    background-position: center;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.03);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    cursor: pointer;
}
.block-tint {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(7,12,22,0.92) 0%, rgba(7,12,22,0.4) 60%, transparent 100%);
    transition: all 0.4s ease;
}
.bento-block:hover {
    transform: translateY(-5px);
    border-color: #3b82f6;
    box-shadow: 0 18px 42px rgba(59, 130, 246, 0.22);
}
.bento-block:hover .block-tint {
    background: linear-gradient(to top, rgba(7,12,22,0.95) 0%, rgba(59, 130, 246, 0.14) 100%);
}
.bento-text {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 32px;
    z-index: 2;
}
.bento-category-tag {
    display: block;
    font-size: 9.5px;
    font-weight: 800;
    color: #60a5fa;
    letter-spacing: 0.15em;
    margin-bottom: 6px;
}
.bento-text h3 {
    font-size: 22px;
    font-weight: 800;
    color: white;
    margin: 0 0 6px;
}
.bento-text p {
    font-size: 13.5px;
    line-height: 1.6;
    color: #cbd5e1;
    margin: 0 0 14px;
}
.bento-cta-link {
    display: inline-block;
    color: #60a5fa;
    font-weight: 700;
    font-size: 13px;
    text-transform: capitalize;
    letter-spacing: 0.05em;
    transition: all 0.3s;
}
.bento-block:hover .bento-cta-link {
    color: #bfdbfe;
    transform: translateX(4px);
}

.block-xl {
    grid-row: span 2;
}
.block-wide {
    grid-column: span 2;
}

/* ─── 6. VALUES SECTION (Clean crisp white body background) ─── */
.values-section {
    background: var(--bg-primary-light);
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}
.values-section h2 {
    color: var(--text-dark) !important;
}
.values-section p {
    color: var(--text-muted-dark) !important;
}
.values-section .values-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 24px;
    max-width: 1280px;
    margin: 0 auto;
}
.value-feature-card {
    position: relative;
    background: var(--tn-surface);
    padding: 32px 24px;
    border-radius: 20px;
    border: 1px solid var(--tn-border);
    text-align: center;
    overflow: hidden;
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.02);
}
.value-feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -48%;
    width: 48%;
    height: 4px;
    border-radius: 999px;
    background: #2563eb;
    opacity: 0;
    filter: drop-shadow(0 0 8px rgba(37, 99, 235, 0.55));
    transition: opacity 0.25s ease;
    z-index: 2;
}
.value-feature-card::after {
    display: none;
}
.value-feature-card > * {
    position: relative;
    z-index: 1;
}
.value-feature-card:hover {
    background: rgba(239, 246, 255, 0.9);
    border-color: rgba(37,99,235,0.45);
    transform: translateY(-4px);
    box-shadow: 0 16px 36px rgba(37, 99, 235, 0.12);
}
.value-feature-card:hover::before {
    opacity: 1;
    animation: value-top-chase 1.15s linear infinite;
}
.value-feature-card:hover::after {
    display: none;
}
.value-feature-card:hover .value-icon-shield {
    background: #2563eb;
    border-color: #60a5fa;
    color: #ffffff;
    box-shadow: 0 10px 26px rgba(37, 99, 235, 0.24);
}
@keyframes value-top-chase {
    0% {
        transform: translateX(0);
    }
    to {
        transform: translateX(310%);
    }
}
.value-icon-shield {
    width: 56px;
    height: 56px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgba(37,99,235,0.06);
    border: 1px solid rgba(37,99,235,0.18);
    color: var(--accent-blue);
    transition: all 0.3s ease;
}
.value-icon-shield svg {
    width: 24px;
    height: 24px;
}
.value-feature-card h3 {
    font-size: 16px;
    font-weight: 800;
    margin: 0 0 10px;
    color: var(--text-dark);
}
.value-feature-card p {
    font-size: 13px;
    line-height: 1.7;
    color: var(--text-muted-dark);
    margin: 0;
}

/* ─── 7. TECH INSIGHTS (Clean soft-white background) ─── */
.magazine-news-section {
    background: var(--bg-secondary-light);
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}
.magazine-news-section h2 {
    color: var(--text-dark) !important;
}
.magazine-news-section p {
    color: var(--text-muted-dark) !important;
}
.magazine-explore-btn {
    color: var(--accent-blue);
    font-weight: 700;
    font-size: 13.5px;
    text-decoration: none;
    padding: 8px 18px;
    border-radius: 8px;
    border: 1px solid rgba(37,99,235,0.25);
    transition: all 0.3s;
}
.magazine-explore-btn:hover {
    background: rgba(37,99,235,0.05);
}
.magazine-layout-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 32px;
    align-items: stretch;
}
.magazine-main-article {
    position: relative;
    background: var(--tn-surface);
    border-radius: 24px;
    overflow: hidden;
    border: 1px solid var(--tn-border);
    transition: all 0.3s;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.02);
    height: 100%;
    display: flex;
    flex-direction: column;
}
.magazine-main-article::before,
.magazine-mini-article::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 6px;
    background: #2563eb;
    transform: scaleX(0);
    transform-origin: left center;
    transition: transform 0.85s ease;
    z-index: 2;
}
.magazine-main-article:hover::before,
.magazine-mini-article:hover::before {
    transform: scaleX(1);
}
.magazine-main-article:hover {
    border-color: rgba(37,99,235,0.2);
}
.main-art-visual {
    position: relative;
    height: 292px;
    overflow: hidden;
    flex-shrink: 0;
}
.main-art-visual img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}
.magazine-main-article:hover .main-art-visual img {
    transform: scale(1.03);
}
.art-badge-tag {
    position: absolute;
    bottom: 16px;
    left: 20px;
    background: var(--accent-blue);
    color: white;
    font-size: 10px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 4px;
    letter-spacing: 0.05em;
}
.main-art-info {
    padding: 28px 32px 30px;
    display: flex;
    flex: 1;
    flex-direction: column;
}
.main-art-info h3 {
    font-size: 24px;
    font-weight: 800;
    margin: 0 0 10px;
    line-height: 1.35;
    color: var(--text-dark);
}
.main-art-info p {
    font-size: 14.5px;
    line-height: 1.7;
    color: var(--text-muted-dark);
    margin-bottom: 20px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.art-deep-link {
    color: var(--accent-blue);
    font-weight: 700;
    font-size: 13.5px;
    text-decoration: none;
    margin-top: auto;
}
.magazine-secondary-column {
    display: grid;
    grid-template-rows: repeat(3, 1fr);
    gap: 20px;
    height: 100%;
}
.magazine-mini-article {
    position: relative;
    display: flex;
    align-items: center;
    gap: 18px;
    background: var(--tn-surface);
    min-height: 0;
    padding: 16px 18px;
    border-radius: 16px;
    border: 1px solid var(--tn-border);
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.02);
}
.magazine-mini-article:hover {
    border-color: rgba(37,99,235,0.25);
}
.mini-art-thumb {
    width: 112px;
    height: 112px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}
.mini-art-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.mini-art-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    height: 100%;
}
.mini-tag {
    font-size: 9.5px;
    font-weight: 800;
    color: var(--accent-blue);
    margin-bottom: 6px;
    letter-spacing: 0.05em;
}
.mini-art-info h3 {
    font-size: 14.5px;
    font-weight: 800;
    line-height: 1.4;
    color: var(--text-dark);
    margin: 0 0 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.mini-art-info a {
    color: var(--col-muted);
    font-weight: 700;
    font-size: 12px;
    text-decoration: none;
    transition: color 0.2s;
}
.mini-art-info:hover a {
    color: var(--col-accent);
}
.no-articles-panel {
    border: 1px dashed var(--col-border);
    border-radius: 20px;
    padding: 48px;
    text-align: center;
    color: var(--col-muted);
    font-weight: 600;
}

/* ─── 8. REVIEWS (Immersive Dark transitioning section) ─── */
/* ─── 8. REVIEWS (Light background Conversion) ─── */
.reviews-slider-section {
    background: var(--tn-bg);
    border-top: 1px solid var(--tn-border);
    border-bottom: 1px solid var(--tn-border);
}
.reviews-editorial-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.editorial-review-card {
    background: var(--tn-surface);
    padding: 36px 32px;
    border-radius: 24px;
    border: 1px solid var(--tn-border);
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
}
.stars-row {
    color: var(--col-warning);
    font-size: 14px;
    margin-bottom: 16px;
    letter-spacing: 2px;
}
.review-quote {
    font-size: 14.5px;
    line-height: 1.75;
    color: var(--tn-text);
    margin: 0 0 24px;
    flex-grow: 1;
}
.review-author-pill {
    display: flex;
    align-items: center;
    gap: 12px;
    border-top: 1px solid var(--tn-border);
    padding-top: 20px;
}
.reviewer-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(0, 0, 0, 0.05);
}
.reviewer-meta {
    display: flex;
    flex-direction: column;
}
.reviewer-meta strong {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--tn-text);
}
.reviewer-meta span {
    font-size: 11px;
    color: var(--tn-text-muted);
    font-weight: 600;
}
.verified-token {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 800;
    color: var(--col-success);
    background: rgba(37, 99, 235, 0.05);
    border: 1px solid rgba(37, 99, 235, 0.12);
    padding: 3px 8px;
    border-radius: 4px;
}
.verified-token svg {
    width: 12px;
    height: 12px;
}

/* ─── 9. CYBER ECOSYSTEM NEWSLETTER CTA (Always Premium Dark Luxury themed) ─── */
.cyber-newsletter-section {
    background: var(--bg-primary-light);
    padding: 96px 0 120px;
}
.newsletter-neon-box {
    position: relative;
    border-radius: 32px;
    padding: 64px;
    background: #071426;
    border: 1px solid rgba(255, 255, 255, 0.08);
    overflow: hidden;
    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.25);
}
.newsletter-bg-glow {
    display: block;
    position: absolute;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(37,99,235,0.15) 0%, transparent 70%);
    filter: blur(40px);
    right: -50px;
    top: -50px;
}
.newsletter-layout {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 48px;
    align-items: center;
}
.newsletter-headline h2 {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 12px;
    color: #ffffff !important;
}
.newsletter-headline p {
    font-size: 14.5px;
    line-height: 1.7;
    color: #cbd5e1 !important;
    margin: 0;
}
.newsletter-interactive-form {
    display: flex;
    justify-content: flex-end;
}
.input-glow-group {
    display: flex;
    width: 100%;
    max-width: 440px;
    background: rgba(255, 255, 255, 0.03) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 12px;
    padding: 6px;
    transition: all 0.3s;
}
.input-glow-group:focus-within {
    border-color: var(--col-highlight) !important;
    box-shadow: 0 6px 18px rgba(34, 211, 238, 0.15) !important;
}
.input-glow-group input {
    flex-grow: 1;
    background: transparent;
    border: none;
    outline: none;
    color: #ffffff !important;
    font-size: 14px;
    padding: 0 16px;
}
.input-glow-group input::placeholder {
    color: #64748b !important;
}

/* ─── GENERAL BUTTONS ─── */
.btn {
    border: none;
    outline: none;
    cursor: pointer;
    font-family: inherit;
    font-weight: 750;
    font-size: 13.5px;
    padding: 14px 28px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.btn-premium-glow {
    background: var(--col-accent);
    color: white;
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.3);
}
.btn-premium-glow svg {
    width: 16px;
    height: 16px;
    transition: transform 0.25s ease;
}
.btn-premium-glow:hover {
    background: var(--col-highlight);
    box-shadow: 0 8px 30px rgba(96, 165, 250, 0.45);
    transform: translateY(-2px);
}
.btn-premium-glow:hover svg {
    transform: translateX(4px);
}
.btn-premium-glass {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: white;
    backdrop-filter: blur(8px);
}
.btn-premium-glass:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.25);
}

/* ─── SLIDE TRANSITION ANIMATIONS ─── */
.ambient-fade-enter-active,
.ambient-fade-leave-active {
    transition: opacity 1s cubic-bezier(0.25, 1, 0.5, 1);
}
.ambient-fade-enter-from,
.ambient-fade-leave-to {
    opacity: 0;
}

.hero-content-slide-enter-active,
.hero-content-slide-leave-active {
    transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.hero-content-slide-enter-from {
    opacity: 0;
    transform: translateY(18px);
}
.hero-content-slide-leave-to {
    opacity: 0;
    transform: translateY(-14px);
}

/* ─── SCROLL REVEAL TIMING REGISTRY ─── */
.scroll-reveal {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 1s cubic-bezier(0.16, 1, 0.3, 1), transform 1s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: transform, opacity;
}
.scroll-reveal.reveal-fade-up {
    transform: translateY(40px);
}
.scroll-reveal.reveal-scale {
    transform: scale(0.97) translateY(20px);
}
.scroll-reveal.active {
    opacity: 1;
    transform: translateY(0) scale(1);
}
.reveal-stagger > * {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 1s cubic-bezier(0.16, 1, 0.3, 1), transform 1s cubic-bezier(0.16, 1, 0.3, 1);
}
.scroll-reveal.reveal-stagger.active > * {
    opacity: 1;
    transform: translateY(0);
}
.scroll-reveal.reveal-stagger.active > *:nth-child(1) { transition-delay: 0.08s; }
.reveal-stagger.active > *:nth-child(2) { transition-delay: 0.16s; }
.reveal-stagger.active > *:nth-child(3) { transition-delay: 0.24s; }
.reveal-stagger.active > *:nth-child(4) { transition-delay: 0.32s; }

/* ─── RESPONSIVE OVERRIDES ─── */
@media (max-width: 1200px) {
    .hero-title { font-size: 42px; }
    .hero-container {
        width: calc(100% - 64px);
    }
    .hero-content {
        grid-template-columns: minmax(0, 1fr) minmax(360px, 0.84fr);
        gap: 40px;
    }
    .device-showcase-card {
        width: min(100%, 430px);
    }
    .category-cards-grid,
    .premium-products-grid,
    .reviews-editorial-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .affiliate-video-card {
        flex-basis: calc((100% - 48px) / 3);
        height: 390px;
    }
    .values-section .values-cards-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
    .bento-asymmetrical-grid {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto;
    }
    .block-xl, .block-wide {
        grid-column: span 2;
    }
    .trust-bar-section .grid-container {
        grid-template-columns: repeat(2, 1fr);
    }
    .trust-card:nth-child(even) {
        border-right: none;
    }
    .trust-card {
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .trust-card:nth-child(3), .trust-card:nth-child(4) {
        border-bottom: none;
    }
}

@media (max-width: 992px) {
    .section { padding: 72px 0; }
    .hero-viewport {
        min-height: auto;
        padding: 54px 0 44px;
    }
    .hero-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    .hero-text-block { max-width: 100%; text-align: center; }
    .hero-badge, .hero-buttons, .hero-trust-indicators { justify-content: center; }
    .hero-device-wrapper {
        justify-content: center;
        justify-self: center;
    }
    .device-showcase-card {
        width: min(100%, 380px);
    }
    .hero-product-visual {
        height: 200px;
        min-height: 200px;
    }
    .hero-product-visual img {
        width: calc(100% - 28px);
        height: calc(100% - 28px);
    }
    .magazine-layout-grid {
        grid-template-columns: 1fr;
    }
    .magazine-secondary-column {
        grid-template-rows: none;
    }
    .magazine-mini-article {
        min-height: 140px;
    }
    .newsletter-layout {
        grid-template-columns: 1fr;
        gap: 32px;
        text-align: center;
    }
    .newsletter-interactive-form {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .hero-title { font-size: 34px; }
    .hero-viewport { padding: 40px 0 34px; }
    .hero-description { margin-bottom: 20px; }
    .hero-buttons { margin-bottom: 22px; }
    .hero-product-card {
        padding: 12px;
    }
    .hero-product-visual {
        height: 160px;
        min-height: 160px;
    }
    .hero-product-visual img {
        width: calc(100% - 24px);
        height: calc(100% - 24px);
    }
    .hero-product-info {
        align-items: flex-start;
        flex-direction: column;
        gap: 10px;
        margin: -6px 4px 2px;
    }
    .hero-product-bottom-row {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    .hero-product-bottom-row .float-bottom {
        justify-content: center;
    }
    .hero-checkout-btn {
        width: 100%;
        transform: none;
    }
    .hero-checkout-btn:hover {
        transform: translateY(-2px);
    }
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    .section-header h2 { font-size: 28px; }
    .category-cards-grid,
    .premium-products-grid,
    .reviews-editorial-grid,
    .trust-bar-section .grid-container {
        grid-template-columns: 1fr;
    }
    .affiliate-video-card {
        flex-basis: min(78vw, 330px);
        height: 400px;
    }
    .affiliate-slider-nav {
        width: 42px;
        height: 42px;
    }
    .values-section .values-cards-grid {
        grid-template-columns: 1fr !important;
    }
    .trust-card {
        border-right: none !important;
        border-bottom: 1px solid rgba(255,255,255,0.05) !important;
        padding: 24px 16px;
    }
    .trust-card:last-child {
        border-bottom: none !important;
    }
    .bento-asymmetrical-grid {
        grid-template-columns: 1fr;
    }
    .block-xl, .block-wide {
        grid-column: span 1;
    }
    .category-premium-card, .bento-block {
        height: 300px;
    }
    .newsletter-neon-box { padding: 36px 24px; }
    .input-glow-group { flex-direction: column; gap: 12px; border: none; padding: 0; background: transparent; }
    .input-glow-group input {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        padding: 12px 16px;
    }
}

/* ===== STATS COUNT-UP ===== */
.stat-counter {
    font-variant-numeric: tabular-nums;
    display: inline-flex;
    align-items: baseline;
    gap: 1px;
}
.counter-num {
    display: inline-block;
    min-width: 2ch;
    text-align: right;
    transition: none;
}
.counter-suffix {
    opacity: 0.85;
    font-size: 0.82em;
    letter-spacing: 0;
}

/* ─── HOME COMBOS ─── */
/* ─── HOME COMBOS ─── */
.combos-section {
    background: var(--tn-bg);
    padding: 64px 0;
    border-top: 1px solid var(--tn-border);
    border-bottom: 1px solid var(--tn-border);
}

.combos-section .grid-container {
    position: relative;
    z-index: 1;
}

.combos-section .ambient-label {
    background: rgba(37, 99, 235, 0.08);
    border-color: rgba(37, 99, 235, 0.2);
    color: var(--accent-blue);
}

.combos-section h2 {
    color: var(--tn-text);
}

.combo-carousel-shell {
    position: relative;
    margin-top: 26px;
    --combo-card-gap: 24px;
}

.combo-carousel-viewport {
    overflow-x: auto;
    overflow-y: hidden;
    scroll-behavior: smooth;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
    cursor: grab;
    -webkit-overflow-scrolling: touch;
    padding: 0 2px 8px;
}

.combo-carousel-viewport::-webkit-scrollbar {
    display: none;
}

.combo-carousel-viewport.dragging {
    cursor: grabbing;
    scroll-behavior: auto;
}

.combos-grid {
    display: flex;
    gap: var(--combo-card-gap);
    margin: 0;
    width: 100%;
    min-width: 100%;
}

.combo-carousel-btn {
    position: absolute;
    top: 50%;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: #1f2937;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 8;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18);
    transition: transform 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
}

.combo-carousel-btn.prev {
    left: -22px;
}

.combo-carousel-btn.next {
    right: -22px;
}

.combo-carousel-btn svg {
    width: 18px;
    height: 18px;
}

.combo-carousel-btn:hover {
    background: #2563eb;
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 18px 36px rgba(37, 99, 235, 0.28);
}

.combo-home-card {
    background: var(--tn-surface);
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid var(--tn-border);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    display: flex;
    flex-direction: column;
    flex: 0 0 calc((100% - (var(--combo-card-gap) * 3)) / 4);
    min-width: 0;
    max-width: none;
    scroll-snap-align: start;
}

.combo-home-card:hover {
    transform: translateY(-4px);
    border-color: rgba(37, 99, 235, 0.35);
    box-shadow: 0 15px 35px rgba(37, 99, 235, 0.08);
}

.combo-home-card .badge-discount {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    font-size: 10px;
    font-weight: 800;
    padding: 5px 10px;
    border-radius: 30px;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.28);
    letter-spacing: 0.5px;
}

.combo-home-img {
    width: 100%;
    height: 150px;
    overflow: hidden;
    position: relative;
}

.combo-home-img::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0; height: 24px;
    background: linear-gradient(180deg, transparent, rgba(255, 255, 255, 0.9));
    z-index: 2;
}

.combo-home-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.combo-home-card:hover .combo-home-img img {
    transform: scale(1.08);
}

.combo-home-info {
    padding: 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.combo-home-info h3 {
    font-size: 15px;
    font-weight: 800;
    color: var(--tn-text);
    margin-bottom: 6px;
    line-height: 1.32;
    transition: color 0.3s;
    min-height: 60px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.combo-home-card:hover h3 {
    color: var(--accent-blue);
}

.combo-home-info .desc {
    font-size: 12.5px;
    color: var(--tn-text-muted);
    margin-bottom: 12px;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bundle-items {
    background: var(--tn-bg);
    border: 1px solid var(--tn-border);
    border-radius: 10px;
    padding: 9px 12px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    overflow: hidden;
}

.b-item-line {
    width: 100%;
    font-size: 11px;
    font-weight: 600;
    color: var(--tn-text-soft);
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.clickable-product {
    cursor: pointer;
    transition: all 0.2s ease;
    color: #2563eb;
}

.clickable-product:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.b-item-inline .sep {
    color: var(--accent-blue);
    margin: 0 4px;
    font-weight: 800;
}

.combo-home-info .price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    border-top: 1px solid var(--tn-border);
    padding-top: 12px;
}

.combo-home-info .price-box {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.combo-home-info .price-box .lbl {
    font-size: 9px;
    font-weight: 600;
    color: var(--tn-text-muted);
    text-transform: capitalize;
    letter-spacing: 0.5px;
}

.combo-home-info .price-box .price {
    font-size: 16px;
    font-weight: 800;
    color: #ef4444;
}

.combo-home-info .btn {
    padding: 8px 12px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    border-radius: 8px !important;
    height: auto !important;
    min-height: 0 !important;
    line-height: 1.2 !important;
    background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
    color: white !important;
    border: none !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3) !important;
    transition: all 0.3s !important;
}

.combo-home-info .btn:hover {
    background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4) !important;
    transform: translateY(-1px);
}

@media (max-width: 1280px) {
    .combo-carousel-shell {
        --combo-card-gap: 20px;
    }

    .combo-home-card {
        flex-basis: calc((100% - (var(--combo-card-gap) * 2)) / 3);
    }
}

@media (max-width: 1024px) {
    .combo-home-card {
        flex-basis: calc((100% - var(--combo-card-gap)) / 2);
    }
}

@media (max-width: 768px) {
    .combo-carousel-shell {
        --combo-card-gap: 14px;
    }

    .combo-home-card {
        flex-basis: min(82vw, 360px);
        min-width: min(82vw, 360px);
    }

    .combo-carousel-btn {
        width: 38px;
        height: 38px;
    }

    .combo-carousel-btn.prev {
        left: -8px;
    }

    .combo-carousel-btn.next {
        right: -8px;
    }
}

@media (max-width: 520px) {
    .combo-home-card {
        flex-basis: 86vw;
        min-width: 86vw;
    }
}

@media (max-width: 600px) {
    .hero-container {
        width: calc(100% - 32px);
    }
}

/* Unify the home canvas and remove stacked vertical whitespace. */
.premium-theme,
.premium-theme .section,
.premium-theme .product-section,
.premium-theme .category-section,
.premium-theme .ecosystem-section,
.premium-theme .values-section,
.premium-theme .magazine-news-section,
.premium-theme .reviews-slider-section,
.premium-theme .combos-section,
.premium-theme .cyber-newsletter-section {
    background-color: #ffffff;
}

.premium-theme .section {
    padding-top: 48px;
    padding-bottom: 48px;
}

.premium-theme .section-header {
    margin-bottom: 32px;
}

.premium-theme .premium-tabs-strip {
    margin-bottom: 28px;
}

.premium-theme .global-action-row {
    margin-top: 28px;
}

.premium-theme .combos-section,
.premium-theme .cyber-newsletter-section {
    padding-top: 48px;
    padding-bottom: 48px;
}

.premium-theme .trust-card {
    padding-top: 24px;
    padding-bottom: 24px;
}

/* Keep the affiliate carousel canvas flush with the white section. */
.premium-theme .category-section > .grid-container,
.premium-theme .affiliate-video-slider-shell,
.premium-theme .affiliate-video-showcase-grid {
    background: #ffffff !important;
    background-color: #ffffff !important;
    border: 0 !important;
    box-shadow: none;
}

.premium-theme .category-section {
    border: 0 !important;
}

.premium-theme .affiliate-video-showcase-grid {
    padding-top: 4px;
    padding-bottom: 0;
}

.premium-theme .affiliate-video-card,
.premium-theme .affiliate-video-card:hover {
    box-shadow: none;
}

@media (max-width: 992px) {
    .premium-theme .section,
    .premium-theme .combos-section,
    .premium-theme .cyber-newsletter-section {
        padding-top: 40px;
        padding-bottom: 40px;
    }

    .premium-theme .section-header {
        margin-bottom: 28px;
    }
}

@media (max-width: 640px) {
    .premium-theme .section,
    .premium-theme .combos-section,
    .premium-theme .cyber-newsletter-section {
        padding-top: 32px;
        padding-bottom: 32px;
    }

    .premium-theme .section-header,
    .premium-theme .premium-tabs-strip {
        margin-bottom: 24px;
    }
}
</style>
