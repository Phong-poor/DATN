<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'
import { getToken } from '@/services/auth'
import { imageFallbackUrl, normalizeImageUrl, productImageUrl, withImageVersion } from '@/services/urls'
import { findPrefetchedProductById, prefetchProductsPage } from '@/services/productsPrefetch'
import ComboSelectionModal from './ComboSelectionModal.vue'



const route = useRoute()
const isLoading = ref(false)
const router = useRouter()

// ===================== STATE COMBO =====================
const combos = ref([])
const showComboModal = ref(false)
const selectedCombo = ref(null)
const selectedTriggerVariant = ref(null) // Biến thể là laptop kích hoạt ưu đãi (nếu có)

const openCombo = (combo, triggerVariant = null) => {
    selectedCombo.value = combo
    selectedTriggerVariant.value = triggerVariant
    showComboModal.value = true
}

const fetchProductCombos = async (productId) => {
    try {
        const res = await api.get('/combos', { skipGlobalLoader: true })
        const allCombos = res.data?.data || []
        combos.value = allCombos.filter(combo =>
            combo.products.some(p => String(p.id_sanpham) === String(productId))
        )
    } catch (e) {
        console.error('Lỗi khi tải combo liên quan:', e)
    }
}

// ===================== STATE GIỎ HÀNG =====================
const soLuongMua = ref(1)
const dangThem = ref(false)
const thongBao = ref({ show: false, type: '', message: '' })

const hienThiThongBao = (type, message) => {
    thongBao.value = { show: true, type, message }
    setTimeout(() => { thongBao.value.show = false }, 3000)
}

// ===================== STATE SẢN PHẨM =====================
const product = ref({
    id_sanpham: route.params.id || null,
    tenSP: 'Đang tải...',
    gia: 0,
    SKU: '',
    hinhanh: '',
    hinhAnhs: [],
    bienThes: [],
    thong_so_ky_thuat: []
})

const selectedImage = ref('https://via.placeholder.com/600')
const selectedVariant = ref(null)
const selectedOptions = ref({})

const activeDropdown = ref(null)
const toggleDropdown = (groupName) => {
    if (activeDropdown.value === groupName) {
        activeDropdown.value = null
    } else {
        activeDropdown.value = groupName
    }
}
const selectOptionAndClose = (groupName, value) => {
    handleSelectOptionWithReset(groupName, value)
    activeDropdown.value = null
}
const closeAllDropdowns = (e) => {
    if (!e.target.closest('.premium-variant-dropdown')) {
        activeDropdown.value = null
    }
}


// ===================== REVIEWS STATE =====================
const reviews = ref([])
const fetchReviews = async () => {
    try {
        const productId = route.params.id || 1
        const res = await api.get(`/sanpham/${productId}/reviews`, { skipGlobalLoader: true })
        reviews.value = res.data.reviews || []
    } catch (error) {
        console.error('Lỗi khi tải đánh giá:', error)
    }
}

const averageRating = computed(() => {
    if (reviews.value.length === 0) return 0
    const sum = reviews.value.reduce((acc, r) => acc + r.danhgia, 0)
    return (sum / reviews.value.length).toFixed(1)
})

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}


// ===================== HELPERS BIẾN THỂ =====================
const getVariantAttributes = (variant) => {
    let attr = variant?.thuoc_tinh || variant?.attributes;
    if (!attr && variant?.thuoc_tinh_json) {
        try {
            attr = typeof variant.thuoc_tinh_json === 'string' 
                ? JSON.parse(variant.thuoc_tinh_json) 
                : variant.thuoc_tinh_json;
        } catch (e) {
            attr = [];
        }
    }
    return Array.isArray(attr) ? attr : [];
}

const specLabelForValue = (value = '') => {
    const text = String(value).toLowerCase()
    if (text.includes('rtx') || text.includes('gtx') || text.includes('gpu')) return 'GPU'
    if (text.includes('core') || text.includes('ryzen') || text.includes('intel') || text.includes('amd')) return 'CPU'
    if (text.includes('ram') || text.includes('gb ram')) return 'RAM'
    if (text.includes('ssd') || text.includes('tb') || text.includes('storage')) return 'SSD'
    if (text.includes('hz')) return 'Tần số quét'
    if (text.includes('oled') || text.includes('ips') || text.includes('inch')) return 'Màn hình'
    return 'Thông số'
}

const specsArrayToTechnicalSpecs = (specs = []) => {
    if (!Array.isArray(specs)) return []
    return specs
        .filter(Boolean)
        .map((value, index) => ({
            id_thuoctinh: `card-spec-${index}`,
            ten_thuoctinh: specLabelForValue(value),
            giatri: String(value),
        }))
}

const enrichProductForDetail = (data = {}) => {
    const cardSpecs = Array.isArray(data.specs) ? data.specs : []
    const technicalSpecs = Array.isArray(data.thong_so_ky_thuat) && data.thong_so_ky_thuat.length > 0
        ? data.thong_so_ky_thuat
        : specsArrayToTechnicalSpecs(cardSpecs)
    const existingVariants = data.bien_thes || data.bienThes || []
    const fallbackVariant = {
        id_bienthe: data.id_bienthe || data.id_sanpham || route.params.id,
        ten_bienthe: data.variantName || data.tenSP || 'Cấu hình tiêu chuẩn',
        gia: data.gia || 0,
        soluong: data.inStock === false ? 0 : 1,
        hinhanh: data.hinhanh || data.image || '',
        thuoc_tinh: technicalSpecs.map((spec) => ({
            id_thuoctinh: spec.id_thuoctinh,
            ten_thuoctinh: spec.ten_thuoctinh,
            giatri: spec.giatri,
        })),
    }
    const variants = existingVariants.length > 0 ? existingVariants : (cardSpecs.length > 0 || data.isFallbackProduct ? [fallbackVariant] : [])

    return {
        ...data,
        hinhanh: data.hinhanh || data.image || '',
        hinh_anhs: data.hinh_anhs || data.hinhAnhs || [],
        hinhAnhs: data.hinhAnhs || data.hinh_anhs || [],
        thong_so_ky_thuat: technicalSpecs,
        bienThes: variants,
    }
}

const variantGroups = computed(() => {
    const variants = product.value.bienThes || []
    const map = new Map()

    variants.forEach(variant => {
        getVariantAttributes(variant).forEach(attr => {
            if (!map.has(attr.ten_thuoctinh)) map.set(attr.ten_thuoctinh, [])
            const exists = map.get(attr.ten_thuoctinh).some(v => v.giatri === attr.giatri)
            if (!exists) map.get(attr.ten_thuoctinh).push({ giatri: attr.giatri, ma_mau: attr.ma_mau || null })
        })
    })

    return Array.from(map.entries())
        .map(([name, values]) => ({ name, values }))
        .sort((a, b) => (a.name === 'Màu sắc' ? -1 : b.name === 'Màu sắc' ? 1 : 0))
})

const findMatchingVariant = () => {
    const variants = product.value.bienThes || []
    return variants.find(variant =>
        Object.entries(selectedOptions.value).every(([attrName, attrValue]) => {
            const found = getVariantAttributes(variant).find(i => i.ten_thuoctinh === attrName)
            return found && found.giatri === attrValue
        })
    ) || null
}

const replaceVariantQueryWithoutScroll = (variantId) => {
    if (typeof window === 'undefined') return
    const url = new URL(window.location.href)
    url.searchParams.set('variant', variantId)
    window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`)
}


const handleSelectOption = (groupName, value) => {
    selectedOptions.value = { ...selectedOptions.value, [groupName]: value }
    const matched = findMatchingVariant()
    if (matched) {
        selectedVariant.value = matched
        if (matched.hinhanh) {
            selectedImage.value = getImageUrl(matched.hinhanh)
        }
        replaceVariantQueryWithoutScroll(matched.id_bienthe)
    }
}

// Reset số lượng khi đổi biến thể
const handleSelectOptionWithReset = (groupName, value) => {
    handleSelectOption(groupName, value)
    soLuongMua.value = 1
}

// ===================== SỐ LƯỢNG MUA =====================
const giamSoLuong = () => { if (soLuongMua.value > 1) soLuongMua.value-- }
const tangSoLuong = () => {
    const maxTonKho = selectedVariant.value?.soluong ?? 999
    if (soLuongMua.value < maxTonKho) soLuongMua.value++
}

// THÊM GIỎ HÀNG
const themVaoGioHang = async () => {

    // ✅ CHECK ĐĂNG NHẬP TRƯỚC
    const token = getToken()
    if (!token) {
        hienThiThongBao('error', 'Vui lòng đăng nhập trước!')
        if (selectedVariant.value) {
            localStorage.setItem('pendingCartItem', JSON.stringify({
                id_bienthe: selectedVariant.value.id_bienthe,
                soluong: soLuongMua.value,
            }));
        }
        setTimeout(() => {
            router.push({
                path: '/login',
                query: { redirect: route.fullPath }
            })
        }, 1000)
        return
    }

    // ======================
    if (!selectedVariant.value) {
        hienThiThongBao('error', 'Vui lòng chọn biến thể sản phẩm!')
        return
    }

    if (selectedVariant.value.soluong === 0) {
        hienThiThongBao('error', 'Sản phẩm này đã hết hàng!')
        return
    }

    dangThem.value = true

    try {
        await api.post('/gio-hang/them', {
            id_bienthe: selectedVariant.value.id_bienthe,
            soluong: soLuongMua.value,
        })

        hienThiThongBao('success', '✅ Đã thêm vào giỏ hàng!')

        // 🔥 cập nhật badge header
        window.dispatchEvent(new Event('cart-updated'))

    } catch (err) {
        const msg = err.response?.data?.message || 'Có lỗi xảy ra, vui lòng thử lại!'
        hienThiThongBao('error', msg)
    } finally {
        dangThem.value = false
    }
}
// ===================== FORMAT =====================
const formatPrice = (price) => {
    if (!price) return '0đ'
    return new Intl.NumberFormat('vi-VN').format(price) + 'đ'
}

const getSpecValue = (name) => {
    if (!product.value || !product.value.thong_so_ky_thuat) return ''
    const specs = Array.isArray(product.value.thong_so_ky_thuat) ? product.value.thong_so_ky_thuat : []
    const found = specs.find(s => (s.ten_thuoctinh || '').toLowerCase().includes(name.toLowerCase()))
    return found ? found.giatri : ''
}

const getImageUrl = (path) => {
    return withImageVersion(
        normalizeImageUrl(path, 'https://via.placeholder.com/600'),
        product.value?.updated_at || product.value?.updatedAt
    )
}

const allImages = computed(() => {
    if (!product.value) return []
    const images = []
    const addImage = (path) => {
        const url = getImageUrl(path)
        if (url && !images.includes(url)) images.push(url)
    }

    addImage(product.value.hinhanh || product.value.image_url || product.value.image || product.value.thumbnail)

    const listHinhAnh = product.value.hinh_anhs || product.value.hinhAnhs || []
    listHinhAnh.forEach(img => {
        addImage(img?.duongdan || img?.duong_dan || img?.url || img?.path || img?.image)
    })

    return images.length > 0 ? images : ['https://via.placeholder.com/600']
})

// ===================== THUMB SLIDER =====================
const thumbIndex = ref(0)
const thumbLimit = 4
const visibleThumbs = computed(() => {
    return allImages.value.slice(thumbIndex.value, thumbIndex.value + thumbLimit)
})
const nextThumbs = () => {
    if (thumbIndex.value + thumbLimit < allImages.value.length) {
        thumbIndex.value++
    }
}
const prevThumbs = () => {
    if (thumbIndex.value > 0) {
        thumbIndex.value--
    }
}

// ===================== AUTO SLIDER =====================
let autoSlideInterval = null

const startAutoSlide = () => {
    stopAutoSlide()
    autoSlideInterval = setInterval(() => {
        if (allImages.value.length > 1) {
            const currentIndex = allImages.value.indexOf(selectedImage.value)
            const nextIndex = (currentIndex + 1) % allImages.value.length
            selectedImage.value = allImages.value[nextIndex]

            // Sync thumb slider
            if (nextIndex >= thumbIndex.value + thumbLimit || nextIndex < thumbIndex.value) {
                thumbIndex.value = Math.min(nextIndex, Math.max(0, allImages.value.length - thumbLimit))
            }
        }
    }, 2000)
}

const stopAutoSlide = () => {
    if (autoSlideInterval) {
        clearInterval(autoSlideInterval)
        autoSlideInterval = null
    }
}

// ===================== 3D PRODUCT VIEWER LOGIC =====================
const is3DMode = ref(false)
const active3DIndex = ref(0)
const target3DIndex = ref(0)
const isHovering3D = ref(false)
const tiltX = ref(0)
const tiltY = ref(0)
const targetTiltX = ref(0)
const targetTiltY = ref(0)
const current3DRatio = ref(0)
const target3DRatio = ref(0)

const toggle3DMode = () => {
    is3DMode.value = !is3DMode.value
    if (!is3DMode.value) {
        resetTilt()
        isHovering3D.value = false
    } else {
        isHovering3D.value = true
        targetTiltX.value = 15
        targetTiltY.value = -10
        startTiltSmoothing()
    }
}

const tiltStyle = computed(() => {
    if (!is3DMode.value && tiltX.value === 0 && tiltY.value === 0) {
        return {
            transform: 'perspective(1200px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)',
            transition: 'transform 0.45s cubic-bezier(0.22, 1, 0.36, 1)'
        }
    }
    if (is3DMode.value && tiltX.value === 0 && tiltY.value === 0) {
        return {
            transform: 'perspective(1200px) rotateX(-10deg) rotateY(15deg) scale3d(1.05, 1.05, 1.05)',
            transition: 'transform 0.45s cubic-bezier(0.22, 1, 0.36, 1)',
            boxShadow: '0 25px 50px -12px rgba(0,0,0,0.5)'
        }
    }
    return {
        transform: `perspective(1200px) rotateX(${tiltY.value}deg) rotateY(${tiltX.value}deg) scale3d(1.05, 1.05, 1.05)`,
        transition: 'transform 0.18s ease-out',
        boxShadow: is3DMode.value ? '0 25px 50px -12px rgba(0,0,0,0.5)' : 'none'
    }
})

const activeShowcaseImage = computed(() => {
    if ((isHovering3D.value || is3DMode.value) && allImages.value.length > 0) {
        return allImages.value[active3DIndex.value] || selectedImage.value
    }
    return selectedImage.value
})

let rotationAnimationFrame = null
let tiltAnimationFrame = null

const startTiltSmoothing = () => {
    if (tiltAnimationFrame) return

    const animate = () => {
        const smoothness = 0.07
        tiltX.value += (targetTiltX.value - tiltX.value) * smoothness
        tiltY.value += (targetTiltY.value - tiltY.value) * smoothness

        if (
            Math.abs(targetTiltX.value - tiltX.value) < 0.02 &&
            Math.abs(targetTiltY.value - tiltY.value) < 0.02
        ) {
            tiltX.value = targetTiltX.value
            tiltY.value = targetTiltY.value
        }

        if (isHovering3D.value || tiltX.value !== 0 || tiltY.value !== 0) {
            tiltAnimationFrame = requestAnimationFrame(animate)
        } else {
            tiltAnimationFrame = null
        }
    }

    tiltAnimationFrame = requestAnimationFrame(animate)
}

const stopTiltSmoothing = () => {
    if (tiltAnimationFrame) {
        cancelAnimationFrame(tiltAnimationFrame)
        tiltAnimationFrame = null
    }
}

const startRotationLoop = () => {
    if (rotationAnimationFrame) return

    const animate = () => {
        const total = allImages.value.length
        if (total <= 1) {
            rotationAnimationFrame = null
            return
        }

        const ratioDiff = target3DRatio.value - current3DRatio.value
        if (Math.abs(ratioDiff) > 0.001) {
            current3DRatio.value += ratioDiff * 0.018
        } else {
            current3DRatio.value = target3DRatio.value
        }

        const frameIndex = Math.round(current3DRatio.value * (total - 1))
        active3DIndex.value = Math.max(0, Math.min(frameIndex, total - 1))

        if (isHovering3D.value || current3DRatio.value !== target3DRatio.value) {
            rotationAnimationFrame = requestAnimationFrame(animate)
        } else {
            rotationAnimationFrame = null
        }
    }

    rotationAnimationFrame = requestAnimationFrame(animate)
}

const stopRotationLoop = () => {
    if (rotationAnimationFrame) {
        cancelAnimationFrame(rotationAnimationFrame)
        rotationAnimationFrame = null
    }
}

const handleMouseMove = (e) => {
    if (!is3DMode.value) return;
    const el = e.currentTarget
    if (!el) return
    const rect = el.getBoundingClientRect()
    
    stopAutoSlide()
    isHovering3D.value = true
    
    const clientX = e.clientX || (e.touches && e.touches[0].clientX);
    const clientY = e.clientY || (e.touches && e.touches[0].clientY);

    const ratioX = (clientX - rect.left) / rect.width
    const ratioY = (clientY - rect.top) / rect.height
    
    if (allImages.value.length > 0) {
        target3DRatio.value = Math.max(0, Math.min(ratioX, 1))
    }
    
    startRotationLoop()
    
    const maxTiltX = 15
    const maxTiltY = 10
    
    targetTiltX.value = (ratioX - 0.5) * maxTiltX * 2
    targetTiltY.value = -(ratioY - 0.5) * maxTiltY * 2
    startTiltSmoothing()
}

const resetTilt = () => {
    if (!is3DMode.value) {
        isHovering3D.value = false
        stopRotationLoop()
        targetTiltX.value = 0
        targetTiltY.value = 0
        startTiltSmoothing()
        
        const idx = allImages.value.indexOf(selectedImage.value)
        const targetIdx = idx !== -1 ? idx : 0
        const targetRatio = allImages.value.length > 1 ? targetIdx / (allImages.value.length - 1) : 0
        target3DIndex.value = targetIdx
        active3DIndex.value = targetIdx
        current3DRatio.value = targetRatio
        target3DRatio.value = targetRatio
        
        startAutoSlide()
    } else {
        targetTiltX.value = 0
        targetTiltY.value = 0
        startTiltSmoothing()
        stopRotationLoop()
    }
}

// ===================== FETCH SẢN PHẨM =====================
const loadCache = (productId) => {
    try {
        const cached = localStorage.getItem(`predator_product_detail_cache_${productId}`)
        if (cached) {
            const parsed = JSON.parse(cached)
            if (parsed.product) product.value = enrichProductForDetail(parsed.product)
            if (parsed.reviews) reviews.value = parsed.reviews
            if (parsed.recentlyViewedProducts) recentlyViewedProducts.value = parsed.recentlyViewedProducts
            if (parsed.relatedProducts) relatedProducts.value = parsed.relatedProducts
            if (parsed.combos) combos.value = parsed.combos
            if (product.value && product.value.tenSP) {
                window.dispatchEvent(new CustomEvent('page-title-updated', { detail: product.value.tenSP }))
            }

            // Cập nhật ảnh đại diện và biến thể từ cache
            if (allImages.value.length > 0) selectedImage.value = allImages.value[0]
            const variants = product.value.bienThes || []
            if (variants.length > 0) {
                const variantId = route.query.variant
                let targetVariant = variants[0]
                if (variantId) {
                    const found = variants.find(v => String(v.id_bienthe) === String(variantId))
                    if (found) targetVariant = found
                }
                selectedVariant.value = targetVariant
                const options = {}
                getVariantAttributes(targetVariant).forEach(attr => {
                    options[attr.ten_thuoctinh] = attr.giatri
                })
                selectedOptions.value = options
                if (targetVariant.hinhanh) {
                    selectedImage.value = getImageUrl(targetVariant.hinhanh)
                }
            }
            return true
        }
    } catch (e) {
        console.error('Lỗi load cache chi tiết sản phẩm:', e)
    }
    return false
}

const saveCache = (productId) => {
    try {
        localStorage.setItem(`predator_product_detail_cache_${productId}`, JSON.stringify({
            product: product.value,
            reviews: reviews.value,
            recentlyViewedProducts: recentlyViewedProducts.value,
            relatedProducts: relatedProducts.value,
            combos: combos.value
        }))
    } catch (e) {
        console.error('Lỗi save cache chi tiết sản phẩm:', e)
    }
}

const applyProductPayload = (data) => {
    if (!data?.tenSP) return false
    const enriched = enrichProductForDetail(data)
    const variants = enriched.bienThes || []
    product.value = enriched

    if (product.value.tenSP) {
        window.dispatchEvent(new CustomEvent('page-title-updated', { detail: product.value.tenSP }))
    }

    if (allImages.value.length > 0) selectedImage.value = allImages.value[0]

    if (variants.length > 0) {
        const variantId = route.query.variant
        let targetVariant = variants[0]

        if (variantId) {
            const found = variants.find(v => String(v.id_bienthe) === String(variantId))
            if (found) targetVariant = found
        }

        selectedVariant.value = targetVariant
        const options = {}
        getVariantAttributes(targetVariant).forEach(attr => {
            options[attr.ten_thuoctinh] = attr.giatri
        })
        selectedOptions.value = options

        if (targetVariant.hinhanh) {
            selectedImage.value = getImageUrl(targetVariant.hinhanh)
        }
    }

    return true
}

const fetchProductDetail = async () => {
    try {
        const productId = route.params.id || 1
        const response = await api.get(`/sanpham/${productId}`, { skipGlobalLoader: true })
        const data = response.data
        applyProductPayload(data)
        const enriched = enrichProductForDetail(data)
        const variants = enriched.bienThes || []
        product.value = enriched

        if (product.value && product.value.tenSP) {
            window.dispatchEvent(new CustomEvent('page-title-updated', { detail: product.value.tenSP }))
        }

        if (allImages.value.length > 0) selectedImage.value = allImages.value[0]

        if (variants.length > 0) {
            const variantId = route.query.variant
            let targetVariant = variants[0]

            if (variantId) {
                const found = variants.find(v => String(v.id_bienthe) === String(variantId))
                if (found) targetVariant = found
            }

            selectedVariant.value = targetVariant
            const options = {}
            getVariantAttributes(targetVariant).forEach(attr => {
                options[attr.ten_thuoctinh] = attr.giatri
            })
            selectedOptions.value = options

            if (targetVariant.hinhanh) {
                selectedImage.value = getImageUrl(targetVariant.hinhanh)
            }
        }

        // Tải sản phẩm tương tự
        if (data.id_danhmuc) {
            fetchRelatedProducts(data.id_danhmuc, data.id_sanpham)
                .then(() => saveCache(productId))
                .catch((err) => console.error('Lỗi khi tải sản phẩm tương tự:', err))
        }

        // --- GHI NHẬN LƯỢT XEM SẢN PHẨM ---
        if (getToken()) {
            try {
                await api.post(`/sanpham-daxem/${productId}`, {}, { skipGlobalLoader: true });
            } catch (err) {
                console.error('Lỗi khi ghi nhận lượt xem:', err);
            }
        }

    } catch (error) {
        console.error('Lỗi khi tải chi tiết sản phẩm:', error)
    }
}

const applyWarmProduct = (data) => {
    if (!data?.tenSP) return false
    const enriched = enrichProductForDetail(data)
    const variants = enriched.bienThes || []
    product.value = enriched

    if (allImages.value.length > 0) selectedImage.value = allImages.value[0]

    if (variants.length > 0) {
        const variantId = route.query.variant
        let targetVariant = variants[0]

        if (variantId) {
            const found = variants.find(v => String(v.id_bienthe) === String(variantId))
            if (found) targetVariant = found
        }

        selectedVariant.value = targetVariant
        const options = {}
        getVariantAttributes(targetVariant).forEach(attr => {
            options[attr.ten_thuoctinh] = attr.giatri
        })
        selectedOptions.value = options

        if (targetVariant.hinhanh) {
            selectedImage.value = getImageUrl(targetVariant.hinhanh)
        }
    }

    return true
}

const showInstantDetailShell = (productId) => {
    product.value = {
        id_sanpham: productId,
        tenSP: 'Dang tai san pham...',
        gia: 0,
        SKU: '',
        hinhanh: '',
        hinhAnhs: [],
        bienThes: [],
        thong_so_ky_thuat: [],
        isPendingDetail: true,
    }
    selectedImage.value = 'https://via.placeholder.com/600'
    selectedVariant.value = null
    selectedOptions.value = {}
    isLoading.value = false
}

const hydrateFromProductsList = async (productId) => {
    try {
        const cache = await prefetchProductsPage()
        const warmProduct = (cache?.productsRaw || []).find((item) => String(item.id_sanpham) === String(productId))
        if (applyWarmProduct(warmProduct)) {
            saveCache(productId)
        }
    } catch (error) {
        console.error('Loi tai nhanh san pham tu danh sach:', error)
    }
}

const loadPageData = async () => {
    const productId = route.params.id || 1
    // Tải cache ngay lập tức để hiển thị tức thì
    const hasCache = loadCache(productId)
    if (hasCache) {
        isLoading.value = false
    } else {
        const warmProduct = findPrefetchedProductById(productId)
        if (applyWarmProduct(warmProduct)) {
            isLoading.value = false
        } else {
            showInstantDetailShell(productId)
            hydrateFromProductsList(productId)
        }
    }

    if (product.value?.isFallbackProduct) {
        saveCache(productId)
        isLoading.value = false
        return
    }

    fetchProductDetail().then(() => {
        if (!product.value?.id_sanpham) {
            product.value = {
                ...product.value,
                tenSP: 'Khong tim thay san pham',
                isNotFound: true,
            }
        }
        saveCache(productId)
        isLoading.value = false

        Promise.allSettled([
            fetchRecentlyViewed(),
            fetchReviews(),
            fetchProductCombos(productId)
        ]).then(() => {
            saveCache(productId)
        })
    }).catch((e) => {
        console.error('Lỗi khi tải dữ liệu chi tiết sản phẩm:', e)
        isLoading.value = false
    })
}

const showStickyBar = ref(false)
const activeSpecTab = ref(0)
const handleScrollSticky = () => {
    showStickyBar.value = window.scrollY > 600
}

const categorizedSpecs = computed(() => {
    const categories = {
        performance: { title: 'Hiệu năng', icon: '⚡', items: [] },
        screen: { title: 'Màn hình', icon: '🖥️', items: [] },
        storage: { title: 'Lưu trữ & RAM', icon: '💾', items: [] },
        connectivity: { title: 'Kết nối', icon: '🔌', items: [] },
        warranty: { title: 'Bảo hành & Khác', icon: '🛡️', items: [] }
    }

    const rows = [...machineInfoRows.value]
    rows.forEach(row => {
        const labelLower = row.label.toLowerCase()
        if (labelLower.includes('cpu') || labelLower.includes('gpu') || labelLower.includes('card') || labelLower.includes('bộ vi xử lý') || labelLower.includes('vi xử lý') || labelLower.includes('đồ họa') || labelLower.includes('hiệu năng') || labelLower.includes('bộ nhớ đệm') || labelLower.includes('chipset')) {
            categories.performance.items.push(row)
        } else if (labelLower.includes('màn hình') || labelLower.includes('độ phân giải') || labelLower.includes('tần số quét') || labelLower.includes('hz') || labelLower.includes('oled') || labelLower.includes('ips') || labelLower.includes('hiển thị')) {
            categories.screen.items.push(row)
        } else if (labelLower.includes('ssd') || labelLower.includes('ổ cứng') || labelLower.includes('dung lượng') || labelLower.includes('lưu trữ') || labelLower.includes('hdd') || labelLower.includes('ram') || labelLower.includes('bộ nhớ trong')) {
            categories.storage.items.push(row)
        } else if (labelLower.includes('cổng') || labelLower.includes('kết nối') || labelLower.includes('giao tiếp') || labelLower.includes('usb') || labelLower.includes('hdmi') || labelLower.includes('wi-fi') || labelLower.includes('wifi') || labelLower.includes('bluetooth') || labelLower.includes('mạng') || labelLower.includes('thunderbolt')) {
            categories.connectivity.items.push(row)
        } else {
            categories.warranty.items.push(row)
        }
    })

    return Object.values(categories).filter(c => c.items.length > 0)
})

const whyBuyThisSpecs = computed(() => {
    return [
        { icon: '🚀', title: 'Hiệu năng đỉnh cao', desc: 'Trang bị cấu hình phần cứng mới nhất giúp vận hành mọi tác vụ cực độ mà không có độ trễ.' },
        { icon: '🖥️', title: 'Màn hình chuẩn màu', desc: 'Đáp ứng 100% không gian màu thiết kế, tần số quét mượt mà cho trải nghiệm thị giác vô tận.' },
        { icon: '❄️', title: 'Tản nhiệt buồng hơi kép', desc: 'Hệ thống cánh quạt tản nhiệt siêu mỏng giúp giảm nhiệt độ máy đến 15°C khi chịu tải nặng liên tục.' },
        { icon: '💾', title: 'Nâng cấp dễ dàng', desc: 'Thiết kế bo mạch linh hoạt hỗ trợ mở rộng thêm ổ cứng SSD và RAM dung lượng lớn để lưu trữ không giới hạn.' },
        { icon: '💼', title: 'Đa nhiệm hoàn hảo', desc: 'Phù hợp hoàn toàn cho các lập trình viên chuyên nghiệp, designer sáng tạo và game thủ chiến mọi tựa game.' },
        { icon: '🛡️', title: 'Hậu mãi chuẩn 5 sao', desc: 'Bảo hành chính hãng 24 tháng, cam kết 1 đổi 1 trong vòng 7 ngày đầu nếu có lỗi phần cứng.' }
    ]
})

const benchmarkData = computed(() => {
    return [
        { label: 'Gaming (FPS trung bình ở Ultra 1080p)', score: 85, color: '#f97316', desc: 'CS2: 240+ FPS | Cyberpunk 2077: 75+ FPS' },
        { label: 'Render 3D (Blender / V-Ray Cycles)', score: 92, color: '#2563eb', desc: 'Render thời gian thực cực mượt với nhân Ray Tracing' },
        { label: 'Đồ họa & Dựng Phim (Premiere Pro / DaVinci)', score: 88, color: '#00e5ff', desc: 'Xử lý video RAW 4K 10-bit không cần proxy' },
        { label: 'AI & Lập trình (PyTorch / Xcode compiler)', score: 90, color: '#10b981', desc: 'Gia tốc NPU riêng biệt chạy mô hình LLM local' }
    ]
})

onMounted(() => {
    window.scrollTo(0, 0)
    loadPageData()
    startAutoSlide()
    window.addEventListener('scroll', handleScrollSticky, { passive: true })
    
    // Load Model Viewer Script cho chế độ 3D
    if (!document.querySelector('script[src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js"]')) {
        const script = document.createElement('script')
        script.type = 'module'
        script.src = 'https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js'
        document.head.appendChild(script)
    }
})

onUnmounted(() => {
    stopAutoSlide()
    stopRotationLoop()
    stopTiltSmoothing()
    window.removeEventListener('scroll', handleScrollSticky)
    document.removeEventListener('click', closeAllDropdowns)
})

watch(() => route.fullPath, (newPath, oldPath) => {
    if (route.path.startsWith('/products/')) {
        selectedVariant.value = null;
        selectedOptions.value = {};
        thumbIndex.value = 0;
        loadPageData();
        window.scrollTo(0, 0);
    }
})

const recentlyViewedProducts = ref([])
const currentRecentlyViewedPage = ref(1)
const recentlyViewedItemsPerPage = 5

const paginatedRecentlyViewedProducts = computed(() => {
    const start = (currentRecentlyViewedPage.value - 1) * recentlyViewedItemsPerPage
    return recentlyViewedProducts.value.slice(start, start + recentlyViewedItemsPerPage)
})

const totalRecentlyViewedPages = computed(() => Math.ceil(recentlyViewedProducts.value.length / recentlyViewedItemsPerPage))

const fetchRecentlyViewed = async () => {
    if (!getToken()) return; // Chỉ lấy cho user đăng nhập

    try {
        const res = await api.get('/sanpham-daxem', { skipGlobalLoader: true })
        const allProducts = res.data || []

        // Lọc để ẩn sản phẩm hiện đang xem
        const currentProductId = route.params.id || 1;
        const filtered = allProducts.filter(p => p.id_sanpham != currentProductId);

        const variants = []
        filtered.forEach(p => {
            let generalSpecs = []
            try {
                const tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || []);
                if (Array.isArray(tskt)) {
                    generalSpecs = tskt.map(item => item.giatri).filter(Boolean);
                }
            } catch (e) { }
            const fullNameBase = [p.tenSP, ...generalSpecs].join(' ');

            if (p.bien_thes && p.bien_thes.length > 0) {
                // Lấy biến thể đầu tiên để đại diện cho SP đã xem
                const bt = p.bien_thes[0];
                let ram = '', cpu = '', mausac = '';
                let thuoc_tinh = [];
                try { thuoc_tinh = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || []); } catch (e) { }

                if (Array.isArray(thuoc_tinh)) {
                    thuoc_tinh.forEach(attr => {
                        const ten = (attr.ten_thuoctinh || '').toLowerCase();
                        if (ten === 'ram') ram = attr.giatri;
                        else if (ten === 'cpu') cpu = attr.giatri;
                        else if (ten === 'màu sắc' || ten === 'màu') mausac = attr.giatri;
                    });
                }

                const specText = [ram, cpu, mausac].filter(Boolean).join(' · ');

                variants.push({
                    id: p.id_sanpham,
                    key_id: bt.id_bienthe,
                    fullName: fullNameBase,
                    specText: specText,
                    price: bt.gia,
                    img: productImageUrl(p, bt, imageFallbackUrl),
                })
            }
        })

        recentlyViewedProducts.value = variants
    } catch (error) {
        console.error('Lỗi tải sản phẩm đã xem gần đây:', error)
    }
}

const relatedProducts = ref([])
const currentRelatedPage = ref(1)
const relatedItemsPerPage = 5
const selectedCategory = ref(null)

const filteredRelatedProducts = computed(() => {
    if (!selectedCategory.value) return relatedProducts.value
    return relatedProducts.value.filter(p => p.category === selectedCategory.value)
})

const paginatedRelatedProducts = computed(() => {
    const start = (currentRelatedPage.value - 1) * relatedItemsPerPage
    return filteredRelatedProducts.value.slice(start, start + relatedItemsPerPage)
})

const totalRelatedPages = computed(() => Math.ceil(filteredRelatedProducts.value.length / relatedItemsPerPage))

// Lấy danh sách các danh mục duy nhất từ sản phẩm tương tự
const uniqueCategories = computed(() => {
    const categories = new Set()
    relatedProducts.value.forEach(p => {
        if (p.category) categories.add(p.category)
    })
    return Array.from(categories).sort()
})

// Phân bố đánh giá theo số sao
const ratingDistribution = computed(() => {
    const dist = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
    reviews.value.forEach(r => {
        if (r.danhgia >= 1 && r.danhgia <= 5) {
            dist[r.danhgia]++
        }
    })
    return dist
})

// Biểu tượng cho từng loại máy
const getCategoryIcon = (category) => {
    const cat = category.toLowerCase()
    if (cat.includes('gaming')) return '🎮'
    if (cat.includes('văn phòng') || cat.includes('office')) return '💼'
    if (cat.includes('sinh viên') || cat.includes('student')) return '📚'
    if (cat.includes('macbook') || cat.includes('apple')) return '🍎'
    if (cat.includes('đồ họa') || cat.includes('design') || cat.includes('creator')) return '🎨'
    if (cat.includes('mỏng')) return '💫'
    if (cat.includes('2 in 1')) return '🔄'
    return '💻'
}

const scrollToRelated = () => {
    const relatedElement = document.querySelector('.related')
    if (relatedElement) {
        relatedElement.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
}



const fetchRelatedProducts = async (id_danhmuc, currentProductId) => {
    try {
        const res = await api.get('/sanpham', { skipGlobalLoader: true })
        const allProducts = res.data || []

        // Lọc theo danh mục và loại bỏ sản phẩm hiện tại
        const filtered = allProducts.filter(p =>
            p.id_danhmuc === id_danhmuc &&
            p.id_sanpham !== currentProductId
        )

        const variants = []
        filtered.forEach(p => {
            // Parse thông số chung để ghép tên
            let generalSpecs = []
            let tsktArray = []
            try {
                const tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || []);
                if (Array.isArray(tskt)) {
                    tsktArray = tskt
                    generalSpecs = tskt.map(item => item.giatri).filter(Boolean);
                }
            } catch (e) { }
            const fullNameBase = [p.tenSP, ...generalSpecs].join(' ');

            if (p.bien_thes && p.bien_thes.length > 0) {
                p.bien_thes.forEach(bt => {
                    let ram = '', cpu = '', mausac = '';
                    let thuoc_tinh = [];
                    let attributes = {}; // Store all variant attributes
                    try { thuoc_tinh = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || []); } catch (e) { }

                    if (Array.isArray(thuoc_tinh)) {
                        thuoc_tinh.forEach(attr => {
                            const ten = (attr.ten_thuoctinh || '').toLowerCase();
                            attributes[ten] = attr.giatri; // Store normalized with lowercase key
                            if (ten === 'ram') ram = attr.giatri;
                            else if (ten === 'cpu') cpu = attr.giatri;
                            else if (ten === 'màu sắc' || ten === 'màu') mausac = attr.giatri;
                        });
                    }

                    const specText = [ram, cpu, mausac].filter(Boolean).join(' · ');

                    variants.push({
                        id: p.id_sanpham,
                        key_id: bt.id_bienthe,
                        fullName: fullNameBase,
                        specText: specText,
                        price: bt.gia,
                        img: productImageUrl(p, bt, imageFallbackUrl),
                        attributes: attributes, // Add variant attributes
                        thong_so_ky_thuat: tsktArray // Add product technical specs
                    })
                })
            }
        })

        relatedProducts.value = variants
        currentRelatedPage.value = 1
    } catch (error) {
        console.error('Lỗi tải sản phẩm tương tự:', error)
    }
}
const dangThemYeuThich = ref(false)

const themVaoYeuThich = async () => {
    // 1. Check đăng nhập
    const token = getToken()
    if (!token) {
        hienThiThongBao('error', 'Vui lòng đăng nhập trước!')
        setTimeout(() => {
            router.push({ path: '/login', query: { redirect: route.fullPath } })
        }, 1000)
        return
    }

    // 2. Check xem khách đã chọn biến thể (RAM, SSD, Màu...) chưa
    if (!selectedVariant.value) {
        hienThiThongBao('error', 'Vui lòng chọn biến thể sản phẩm trước khi yêu thích!')
        return
    }

    dangThemYeuThich.value = true
    try {
        // 3. Gọi API thêm vào Database
        await api.post('/yeu-thich/them', {
            id_bienthe: selectedVariant.value.id_bienthe,
            soluong: soLuongMua.value, // Thích bao nhiêu cái thì truyền bấy nhiêu
        })

        // 4. Báo thành công và update Header
        hienThiThongBao('success', '❤️ Đã lưu vào danh sách yêu thích từ trang chi tiết!')

        window.dispatchEvent(new Event('wishlist-updated'))

    } catch (err) {
        hienThiThongBao('error', err.response?.data?.message || 'Có lỗi xảy ra!')
    } finally {
        dangThemYeuThich.value = false
    }
}

// ===================== SO SÁNH CẤU HÌNH =====================
// Hàm trích xuất tất cả thuộc tính từ biến thể để so sánh
const extractAllAttributes = (variant) => {
    const attrs = {}
    const thuocTinh = getVariantAttributes(variant)
    thuocTinh.forEach(attr => {
        const key = (attr.ten_thuoctinh || '').toLowerCase()
        attrs[key] = attr.giatri
    })
    return attrs
}

// Lấy danh sách tất cả các thuộc tính và thông số kỹ thuật để so sánh (normalize keys)
const allAttributeKeys = computed(() => {
    const keysMap = new Map() // Use Map to normalize keys (case-insensitive)

    // Thêm thông số kỹ thuật sản phẩm hiện tại
    if (product.value && product.value.thong_so_ky_thuat && Array.isArray(product.value.thong_so_ky_thuat)) {
        product.value.thong_so_ky_thuat.forEach(spec => {
            if (spec.ten_thuoctinh) {
                const normalizedKey = (spec.ten_thuoctinh || '').toLowerCase()
                if (!keysMap.has(normalizedKey)) {
                    keysMap.set(normalizedKey, spec.ten_thuoctinh)
                }
            }
        })
    }

    if (selectedVariant.value) {
        const attrs = extractAllAttributes(selectedVariant.value)
        Object.keys(attrs).forEach(k => {
            if (!keysMap.has(k)) {
                keysMap.set(k, k)
            }
        })
    }
    // Thêm thuộc tính và thông số từ sản phẩm tương tự để hiển thị đầy đủ
    relatedProducts.value.forEach(p => {
        const variantAttrs = p.attributes || {}
        Object.keys(variantAttrs).forEach(k => {
            const normalizedKey = k.toLowerCase()
            if (!keysMap.has(normalizedKey)) {
                keysMap.set(normalizedKey, k)
            }
        })

        if (p.thong_so_ky_thuat && Array.isArray(p.thong_so_ky_thuat)) {
            p.thong_so_ky_thuat.forEach(spec => {
                if (spec.ten_thuoctinh) {
                    const normalizedKey = spec.ten_thuoctinh.toLowerCase()
                    if (!keysMap.has(normalizedKey)) {
                        keysMap.set(normalizedKey, spec.ten_thuoctinh)
                    }
                }
            })
        }
    })
    return Array.from(keysMap.values()).sort()
})

const machineInfoRows = computed(() => {
    const rows = []
    const seen = new Set()
    const addRow = (group, label, value) => {
        if (value === undefined || value === null || value === '') return
        const normalized = `${group}-${label}`.toLowerCase()
        if (seen.has(normalized)) return
        seen.add(normalized)
        rows.push({ group, label, value })
    }

    addRow('Tổng quan', 'Tên sản phẩm', product.value.tenSP)
    addRow('Tổng quan', 'Thương hiệu', product.value.thuong_hieu?.ten_thuonghieu)
    addRow('Tổng quan', 'Danh mục', product.value.danh_muc?.ten_danhmuc)
    addRow('Tổng quan', 'Mã sản phẩm', product.value.SKU || product.value.sku || product.value.id_sanpham)
    addRow('Giá & kho', 'Giá đang chọn', selectedVariant.value ? formatPrice(selectedVariant.value.gia) : formatPrice(product.value.gia))
    addRow('Giá & kho', 'Tình trạng', selectedVariant.value ? (selectedVariant.value.soluong > 0 ? `Còn ${selectedVariant.value.soluong} sản phẩm` : 'Hết hàng') : 'Đang cập nhật')
    addRow('Biến thể', 'Mã biến thể', selectedVariant.value?.SKU || selectedVariant.value?.sku || selectedVariant.value?.id_bienthe)

    if (selectedVariant.value) {
        getVariantAttributes(selectedVariant.value).forEach(attr => {
            addRow('Biến thể', attr.ten_thuoctinh, attr.giatri)
        })
    }

    if (Array.isArray(product.value.thong_so_ky_thuat)) {
        product.value.thong_so_ky_thuat.forEach(spec => {
            addRow('Cấu hình', spec.ten_thuoctinh, spec.giatri)
        })
    }

    return rows
})

const specsColumns = computed(() => {
    const col1 = []
    const col2 = []
    const col3 = []

    machineInfoRows.value.forEach(row => {
        if (row.group === 'Tổng quan' || row.group === 'Giá & kho') {
            col1.push(row)
        } else if (row.group === 'Biến thể') {
            col2.push(row)
        } else {
            col3.push(row)
        }
    })

    return [
        { title: 'Thương Hiệu & Chung', items: col1 },
        { title: 'Phiên Bản & Phân Loại', items: col2 },
        { title: 'Thông Số Kỹ Thuật', items: col3 }
    ]
})

const getItemBadge = (item) => {
    if (!item) return null
    const valLower = String(item.value).toLowerCase()
    const labelLower = String(item.label).toLowerCase()
    
    if (labelLower.includes('giá') || labelLower.includes('tình trạng')) {
        return { text: 'HOT', type: 'hot' }
    }
    if (valLower.includes('m4') || valLower.includes('ultra') || valLower.includes('oled') || valLower.includes('mini-led') || valLower.includes('100wh') || valLower.includes('128gb') || valLower.includes('rtx')) {
        return { text: 'PRO', type: 'pro' }
    }
    if (labelLower.includes('biến thể') || valLower.includes('16 inch') || valLower.includes('65w') || labelLower.includes('màu') || valLower.includes('new')) {
        return { text: 'NEW', type: 'new' }
    }
    return null
}

const machineInfoGridRows = computed(() => {
    const columns = 5
    const rows = [...machineInfoRows.value]
    const missing = rows.length % columns === 0 ? 0 : columns - (rows.length % columns)

    for (let i = 0; i < missing; i++) {
        rows.push({
            group: 'Thông tin thêm',
            label: 'Đang cập nhật',
            value: 'Liên hệ tư vấn'
        })
    }

    return rows
})

// Tạo dữ liệu so sánh: so sánh sản phẩm hiện tại với các sản phẩm khác
const comparisonData = computed(() => {
    const data = []

    if (selectedVariant.value && relatedProducts.value.length > 0) {
        const currentAttrs = extractAllAttributes(selectedVariant.value)

        relatedProducts.value.slice(0, 4).forEach(relatedProd => {
            const relatedAttrs = relatedProd.attributes || {}
            data.push({
                id: relatedProd.id,
                name: relatedProd.fullName,
                price: relatedProd.price,
                specText: relatedProd.specText,
                img: relatedProd.img,
                attributes: relatedAttrs
            })
        })
    }

    return data
})

// ====== COMPARE MODAL STATE & HELPERS ======
const showCompareModal = ref(false)
const showFullSpecs = ref(false)
const specsPanelMode = ref('info')
const compareSelection = ref([]) // array of key_id to compare
const maxCompare = 3

const toggleCompareSelection = (keyId) => {
    const idx = compareSelection.value.indexOf(keyId)
    if (idx === -1) {
        if (compareSelection.value.length < maxCompare) compareSelection.value.push(keyId)
    } else {
        compareSelection.value.splice(idx, 1)
    }
}

const openCompareModal = () => {
    // preselect first related product if none
    if (compareSelection.value.length === 0 && relatedProducts.value.length > 0) {
        compareSelection.value = [relatedProducts.value[0].key_id]
    }
    showCompareModal.value = true
}

const closeCompareModal = () => { showCompareModal.value = false; compareSelection.value = [] }

const compareProducts = computed(() => {
    return relatedProducts.value.filter(p => compareSelection.value.includes(p.key_id)).slice(0, maxCompare)
})

const modalComparisonData = computed(() => {
    const data = []
    if (selectedVariant.value && compareProducts.value.length > 0) {
        const currentAttrs = extractAllAttributes(selectedVariant.value)
        const currentSpecs = {}

        // Lấy thông số kỹ thuật sản phẩm hiện tại
        if (product.value && product.value.thong_so_ky_thuat && Array.isArray(product.value.thong_so_ky_thuat)) {
            product.value.thong_so_ky_thuat.forEach(spec => {
                if (spec.ten_thuoctinh) currentSpecs[spec.ten_thuoctinh] = spec.giatri
            })
        }

        compareProducts.value.forEach(p => {
            // Kết hợp thông số kỹ thuật và thuộc tính biến thể
            const combinedSpecs = {}

            // Thêm thông số kỹ thuật sản phẩm
            if (p.thong_so_ky_thuat && Array.isArray(p.thong_so_ky_thuat)) {
                p.thong_so_ky_thuat.forEach(spec => {
                    if (spec.ten_thuoctinh) combinedSpecs[spec.ten_thuoctinh] = spec.giatri
                })
            }

            // Thêm thuộc tính biến thể
            if (p.attributes) {
                Object.assign(combinedSpecs, p.attributes)
            }

            data.push({
                id: p.key_id,
                name: p.fullName,
                price: p.price,
                img: p.img,
                attributes: p.attributes || {},
                thong_so_ky_thuat: p.thong_so_ky_thuat || [],
                combinedSpecs: combinedSpecs
            })
        })
    }
    return data
})


const selectedVariantOffers = computed(() => {
    return selectedVariant.value?.combo_offers || []
})

const otherVariantsWithOffers = computed(() => {
    const list = []
    const variants = product.value?.bienThes || []
    variants.forEach(v => {
        if (v.id_bienthe !== selectedVariant.value?.id_bienthe && v.combo_offers && v.combo_offers.length > 0) {
            list.push({
                id_bienthe: v.id_bienthe,
                ten_bienthe: v.ten_bienthe,
                offers: v.combo_offers
            })
        }
    })
    return list
})

const handleSelectVariantById = (idBienThe) => {
    const variants = product.value.bienThes || []
    const matched = variants.find(v => String(v.id_bienthe) === String(idBienThe))
    if (matched) {
        selectedVariant.value = matched
        const options = {}
        getVariantAttributes(matched).forEach(attr => {
            options[attr.ten_thuoctinh] = attr.giatri
        })
        selectedOptions.value = options
        if (matched.hinhanh) {
            selectedImage.value = getImageUrl(matched.hinhanh)
        }
        replaceVariantQueryWithoutScroll(matched.id_bienthe)
        soLuongMua.value = 1
        hienThiThongBao('success', `✨ Đã chuyển cấu hình: ${matched.ten_bienthe}`)
    }
}

</script>

<template>
  <div class="product-detail-wrapper">

    <!-- STICKY BUY BAR FOR HIGH CONVERSIONS -->
    <transition name="fade-slide-bar">
        <div v-show="showStickyBar" class="sticky-buy-bar">
            <div class="container sticky-bar-flex">
                <div class="sticky-info-left">
                    <img :src="selectedImage" :alt="product.tenSP" class="sticky-thumb" />
                    <div class="sticky-meta">
                        <h4 class="sticky-title">{{ product.tenSP }}</h4>
                        <span class="sticky-variant-name" v-if="selectedVariant">{{ selectedVariant.ten_bienthe }}</span>
                    </div>
                </div>
                <div class="sticky-actions-right">
                    <div class="sticky-price-glow">
                        {{ selectedVariant ? formatPrice(selectedVariant.gia) : formatPrice(product.gia) }}
                    </div>
                    <button class="btn btn-premium-glass sticky-cart-icon-btn" @click="themVaoGioHang" :disabled="dangThem || !selectedVariant || selectedVariant.soluong === 0" aria-label="Thêm vào giỏ hàng" title="Thêm vào giỏ hàng">
                        <svg class="sticky-cart-icon" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path>
                        </svg>
                    </button>
                    <button class="btn btn-premium-glow" @click="themVaoGioHang" :disabled="dangThem || !selectedVariant || selectedVariant.soluong === 0">
                        Mua ngay
                    </button>
                </div>
            </div>
        </div>
    </transition>

    <transition name="slide-down">
        <div v-if="thongBao.show" :class="['toast', thongBao.type]">
            {{ thongBao.message }}
        </div>
    </transition>

    <!-- TOP GLOW DECORATOR -->
    <div class="tech-glow-top"></div>

    <!-- 3D MODAL POPUP -->
    <transition name="fade">
        <div v-if="is3DMode" class="premium-3d-modal-overlay" @click="toggle3DMode">
            <div class="premium-3d-modal-content" @click.stop
                 @mousemove="handleMouseMove"
                 @mouseleave="resetTilt"
                 @touchmove="handleMouseMove"
                 @touchend="resetTilt">
                <button class="modal-close-btn" @click="toggle3DMode">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <div class="modal-3d-header">
                    <h3 class="modal-3d-title">Góc nhìn 360 độ</h3>
                    <p class="modal-3d-hint">Kéo thả hoặc di chuyển chuột trên ảnh để xoay sản phẩm</p>
                </div>
                <div class="modal-3d-viewport">
                    <img :src="activeShowcaseImage" :alt="product.tenSP" class="modal-3d-image" :style="tiltStyle" />
                </div>
            </div>
        </div>
    </transition>

    <div class="page">
        <div class="premium-hero-container">
            <div class="container">
                <!-- Premium Breadcrumbs -->
                <div class="premium-breadcrumb">
                    <router-link to="/">Trang chủ</router-link>
                    <span class="sep">/</span>
                    <router-link :to="`/products?cat=${product.id_danhmuc}`">{{ product.danh_muc?.ten_danhmuc || 'Sản phẩm' }}</router-link>
                    <span class="sep">/</span>
                    <span class="current">{{ product.tenSP }}</span>
                </div>

                <!-- MAIN DETAIL HERO GRID -->
                <div class="detail-hero-grid">
                    <!-- GALLERY COLUMN (Left) -->
                    <div class="gallery-column">
                        <div class="main-image-viewport"
                             :class="{ 'is-3d-active': is3DMode }"
                             @mousemove="handleMouseMove"
                             @mouseleave="resetTilt"
                             @touchmove="handleMouseMove"
                             @touchend="resetTilt">
                            <div class="neon-glow-backdrop"></div>

                            <!-- 3D Toggle Button -->
                            <button class="btn-toggle-3d" :class="{ active: is3DMode }" @click.stop="toggle3DMode">
                                <svg v-if="!is3DMode" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span>{{ is3DMode ? 'Chế độ 2D' : 'Xem 3D' }}</span>
                            </button>

                            <!-- Badges Overlay -->
                            <div class="gallery-badges">
                                <span class="badge badge-glow" v-if="product.thuong_hieu">{{ product.thuong_hieu.ten_thuonghieu }}</span>
                                <span class="badge badge-tech">ORIGINAL BRAND</span>
                            </div>


                            <img :src="activeShowcaseImage" :alt="product.tenSP" class="main-showcase-image" :style="tiltStyle" />

                            <div v-if="selectedVariant && selectedVariant.soluong === 0" class="premium-out-of-stock-badge">
                                HẾT HÀNG
                            </div>

                            <!-- Navigation Arrows -->
                            <button v-if="!is3DMode" @click="selectedImage = allImages[(allImages.indexOf(selectedImage) - 1 + allImages.length) % allImages.length]" class="gallery-nav-arrow arrow-left" aria-label="Ảnh trước">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </button>
                            <button v-if="!is3DMode" @click="selectedImage = allImages[(allImages.indexOf(selectedImage) + 1) % allImages.length]" class="gallery-nav-arrow arrow-right" aria-label="Ảnh sau">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>

                            <!-- Slide Indicator Dots -->
                            <div class="slide-dots" v-if="!is3DMode">
                                <span v-for="(img, idx) in allImages" :key="idx"
                                      :class="['dot', { active: selectedImage === img }]"
                                      @click="selectedImage = img"></span>
                            </div>

                        </div>

                        <!-- Thumbnails Slider -->
                        <div class="premium-thumbs-container">
                            <button class="thumb-arrow-btn" @click="prevThumbs" :disabled="thumbIndex === 0">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="14" height="14">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </button>

                            <div class="premium-thumbs-scroll">
                                <div v-for="(img, i) in visibleThumbs" :key="i"
                                     :class="['thumb-card', { active: selectedImage === img }]"
                                     @click="selectedImage = img; startAutoSlide ? startAutoSlide() : null">
                                    <img :src="img" alt="Thumbnail" />
                                </div>

                            </div>

                            <button class="thumb-arrow-btn" @click="nextThumbs" :disabled="thumbIndex + thumbLimit >= allImages.length">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="14" height="14">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- INFO & PURCHASE COLUMN (Right) -->
                    <div class="purchase-column">
                        <!-- Dynamic Premium Tag & Specs Badges -->
                        <div class="tech-spec-badges">
                            <span class="pill-spec-badge animate-shine">Intel Core Ultra</span>
                            <span class="pill-spec-badge">RTX 4060 Ready</span>
                            <span class="pill-spec-badge">AI Boost</span>
                            <span class="pill-spec-badge">24M Warranty</span>
                        </div>

                        <div class="brand-subtitle" v-if="product.thuong_hieu">
                            {{ product.thuong_hieu.ten_thuonghieu.toUpperCase() }} WORKSTATION
                        </div>

                        <h1 class="premium-product-title">{{ product.tenSP }}</h1>

                        <!-- Star Rating Interactive Link -->
                        <div class="premium-rating-bar" @click="scrollToRelated()">
                            <div class="stars-gold">
                                <span v-for="i in 5" :key="i" class="star-icon">
                                    {{ i <= Math.round(averageRating) ? '★' : '☆' }}
                                </span>
                            </div>
                            <span class="rating-numeric">{{ averageRating }}/5</span>
                            <span class="rating-separator">|</span>
                            <span class="rating-reviews-count">{{ reviews.length }} Đánh giá</span>
                        </div>

                        <!-- Modern Gradient Price Tag -->
                        <div class="premium-price-container">
                            <span class="price-label">Giá sở hữu</span>
                            <div class="price-value-glow">
                                {{ selectedVariant ? formatPrice(selectedVariant.gia) : formatPrice(product.gia) }}
                            </div>
                            <div class="price-badges-row">
                                <span class="premium-badge-check">✓ Trả góp 0%</span>
                                <span class="premium-badge-check">✓ Miễn phí giao hàng</span>
                            </div>
                        </div>

                        <!-- Variant Selectors Option Groups -->
                        <div class="premium-selectors-wrapper" v-if="product.bienThes && product.bienThes.length > 0">
                            <div class="premium-option-group" v-for="group in variantGroups" :key="group.name">
                                <div class="option-header-row">
                                    <span class="option-label-title">{{ group.name }}</span>
                                </div>

                                <div class="premium-variant-dropdown">
                                    <div class="dropdown-trigger" @click.stop="toggleDropdown(group.name)" :class="{ active: activeDropdown === group.name }">
                                        <div class="selected-info-container">
                                            <span v-if="group.name === 'Màu sắc' && selectedOptions[group.name]" class="selected-color-dot" :style="{ backgroundColor: (group.values.find(v => v.giatri === selectedOptions[group.name])?.ma_mau || '#ccc') }"></span>
                                            <span class="selected-value-text">{{ selectedOptions[group.name] || 'Chọn ' + group.name }}</span>
                                        </div>
                                        <span class="dropdown-arrow-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 14px; height: 14px;">
                                                <polyline points="6 9 12 15 18 9"></polyline>
                                            </svg>
                                        </span>
                                    </div>
                                    <transition name="dropdown-fade">
                                        <div class="dropdown-menu-list" v-show="activeDropdown === group.name">
                                            <div v-for="item in group.values" :key="item.giatri"
                                                 :class="['dropdown-item-option', { active: selectedOptions[group.name] === item.giatri }]"
                                                 @click="selectOptionAndClose(group.name, item.giatri)">
                                                <span v-if="group.name === 'Màu sắc'" class="item-color-dot" :style="{ backgroundColor: item.ma_mau || '#ccc' }"></span>
                                                <span class="item-text-label">{{ item.giatri }}</span>
                                                <span v-if="selectedOptions[group.name] === item.giatri" class="checkmark-active">✓</span>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>

                        <!-- Option Group fallback if none -->
                        <div class="premium-selectors-wrapper" v-else>
                            <p class="updating-text">Thông số biến thể đang được đồng bộ...</p>
                        </div>

                        <!-- Stock Status -->
                        <div class="premium-stock-banner" v-if="selectedVariant">
                            <div v-if="selectedVariant.soluong > 0" class="stock-status in-stock">
                                <span class="pulse-green-dot"></span>
                                <span class="stock-text">Hệ thống sẵn sàng: Còn {{ selectedVariant.soluong }} sản phẩm tại cửa hàng</span>
                            </div>
                            <div v-else class="stock-status out-of-stock">
                                <span class="pulse-red-dot"></span>
                                <span class="stock-text">Hiện tại hết hàng - Liên hệ CSKH hỗ trợ</span>
                            </div>
                        </div>

                        <!-- Qty and CTAs buy buttons -->
                        <div class="purchase-actions-box" v-if="selectedVariant && selectedVariant.soluong > 0">
                            <!-- Qty Control -->
                            <div class="premium-qty-stepper">
                                <button @click="giamSoLuong" :disabled="soLuongMua <= 1" class="stepper-btn" aria-label="Giảm số lượng">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </button>
                                <span class="stepper-value">{{ soLuongMua }}</span>
                                <button @click="tangSoLuong" :disabled="soLuongMua >= selectedVariant.soluong" class="stepper-btn" aria-label="Tăng số lượng">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </button>
                            </div>

                            <!-- Buttons action grid -->
                            <div class="actions-grid">
                                <button class="btn-buy-now btn-glow-primary"
                                        :disabled="!selectedVariant || selectedVariant.soluong === 0 || dangThem"
                                        @click="themVaoGioHang">
                                    <span class="btn-ripple-bg"></span>
                                    <span v-if="dangThem" class="loading-spin-circle"></span>
                                    <span v-else class="btn-content-text">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-icon">
                                            <circle cx="9" cy="21" r="1"></circle>
                                            <circle cx="20" cy="21" r="1"></circle>
                                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                        </svg>
                                        THÊM VÀO GIỎ HÀNG
                                    </span>
                                </button>

                                <button class="btn-installment">
                                    <span class="top-tag">LÃI SUẤT 0%</span>
                                    <span class="main-text">TRẢ GÓP ONLINE</span>
                                </button>
                            </div>

                            <!-- Wishlist & Compare floating actions -->
                            <div class="floating-shortcuts-row">
                                <button class="shortcut-action-btn wishlist-toggle" :disabled="dangThemYeuThich" @click="themVaoYeuThich" title="Lưu yêu thích">
                                    <span class="icon">
                                        <svg v-if="dangThemYeuThich" class="animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle;">
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                        </svg>
                                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle;">
                                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                                        </svg>
                                    </span>
                                    <span>{{ dangThemYeuThich ? 'Đang lưu...' : 'Yêu thích' }}</span>
                                </button>

                                <button class="shortcut-action-btn compare-toggle" @click="openCompareModal" title="So sánh tính năng">
                                    <span class="icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle;">
                                            <path d="m17 2 4 4-4 4"/>
                                            <path d="M3 11v-1a4 4 0 0 1 4-4h14"/>
                                            <path d="m7 22-4-4 4-4"/>
                                            <path d="M21 13v1a4 4 0 0 1-4 4H3"/>
                                        </svg>
                                    </span>
                                    <span>So sánh specs</span>
                                </button>
                            </div>
                        </div>

                        <!-- BOX ƯU ĐÃI ĐI KÈM (VIP Bundles Up-sell) -->
                        <div class="product-benefits-box">
                            <h4 class="benefits-title">
                                <svg class="title-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; display:inline-block; vertical-align:middle; margin-right:6px;">
                                    <polyline points="20 12 20 22 4 22 4 12"/>
                                    <rect x="2" y="7" width="20" height="5"/>
                                    <line x1="12" y1="22" x2="12" y2="7"/>
                                    <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/>
                                    <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
                                </svg>
                                ƯU ĐÃI ĐI KÈM ĐẶC BIỆT:
                            </h4>
                            <ul class="benefits-list">
                                <li>
                                    <span class="benefit-icon">
                                        <svg viewBox="0 0 24 24" stroke="#e11d48">
                                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"/>
                                        </svg>
                                    </span>
                                    <span class="benefit-text">Tặng ngay Balo Predator chống nước cao cấp trị giá 850.000đ.</span>
                                </li>
                                <li>
                                    <span class="benefit-icon">
                                        <svg viewBox="0 0 24 24" stroke="#f59e0b">
                                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                            <line x1="1" y1="10" x2="23" y2="10"/>
                                        </svg>
                                    </span>
                                    <span class="benefit-text">Giảm thêm <b>500.000đ</b> khi thanh toán online qua VNPay/Momo.</span>
                                </li>
                                <li>
                                    <span class="benefit-icon">
                                        <svg viewBox="0 0 24 24" stroke="#10b981">
                                            <line x1="19" y1="5" x2="5" y2="19"/>
                                            <circle cx="6.5" cy="6.5" r="2.5"/>
                                            <circle cx="17.5" cy="17.5" r="2.5"/>
                                        </svg>
                                    </span>
                                    <span class="benefit-text">Trả góp <b>0% lãi suất</b> bằng thẻ tín dụng (kỳ hạn đến 12 tháng).</span>
                                </li>
                                <li>
                                    <span class="benefit-icon">
                                        <svg viewBox="0 0 24 24" stroke="#6366f1">
                                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                        </svg>
                                    </span>
                                    <span class="benefit-text">Miễn phí trọn đời dịch vụ cài đặt Windows, Office bản quyền & vệ sinh máy định kỳ.</span>
                                </li>
                                <li>
                                    <span class="benefit-icon">
                                        <svg viewBox="0 0 24 24" stroke="#f43f5e">
                                            <rect x="1" y="3" width="15" height="13"/>
                                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                            <circle cx="5.5" cy="18.5" r="2.5"/>
                                            <circle cx="18.5" cy="18.5" r="2.5"/>
                                        </svg>
                                    </span>
                                    <span class="benefit-text">Miễn phí giao hàng toàn quốc hoặc <b>Giao nhanh Hỏa Tốc trong vòng 2H</b>.</span>
                                </li>
                            </ul>
                        </div>

                        <!-- BOX CAM KẾT CHẤT LƯỢNG (Trust Badges) -->
                        <div class="product-guarantees-box">
                            <div class="guarantee-card">
                                <span class="g-icon">
                                    <svg viewBox="0 0 24 24" stroke="#2563eb">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        <path d="m9 11 2 2 4-4"/>
                                    </svg>
                                </span>
                                <div class="g-text-wrap">
                                    <span class="g-title">100% Chính Hãng</span>
                                    <span class="g-desc">Đầy đủ VAT & xuất xứ</span>
                                </div>
                            </div>
                            <div class="guarantee-card">
                                <span class="g-icon">
                                    <svg viewBox="0 0 24 24" stroke="#8b5cf6">
                                        <circle cx="12" cy="12" r="3"/>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                    </svg>
                                </span>
                                <div class="g-text-wrap">
                                    <span class="g-title">Bảo Hành 24T</span>
                                    <span class="g-desc">Chính hãng tại trung tâm</span>
                                </div>
                            </div>
                            <div class="guarantee-card">
                                <span class="g-icon">
                                    <svg viewBox="0 0 24 24" stroke="#3b82f6">
                                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                                    </svg>
                                </span>
                                <div class="g-text-wrap">
                                    <span class="g-title">1 Đổi 1 7 Ngày</span>
                                    <span class="g-desc">Nếu phát sinh lỗi NSX</span>
                                </div>
                            </div>
                            <div class="guarantee-card">
                                <span class="g-icon">
                                    <svg viewBox="0 0 24 24" stroke="#0ea5e9">
                                        <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                                        <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
                                    </svg>
                                </span>
                                <div class="g-text-wrap">
                                    <span class="g-title">Hỗ Trợ 24/7</span>
                                    <span class="g-desc">Trọn đời từ Predator</span>
                                </div>
                            </div>
                        </div>


<!-- BANNER ƯU ĐÃI VIP KÈM CẤU HÌNH BIẾN THỂ -->
                        <div v-if="selectedVariantOffers.length > 0" class="variant-offers-box">
                            <div class="offer-header-vip">
                                <span class="badge-vip">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;">
                                        <rect x="3" y="11" width="18" height="10" rx="2"/>
                                        <path d="M12 2v9"/>
                                        <path d="M12 2a3 3 0 0 0-3 3c0 2 3 3 3 3"/>
                                        <path d="M12 2a3 3 0 0 1 3 3c0 2-3 3-3 3"/>
                                        <path d="M3 11h18"/>
                                    </svg>
                                    ĐẶC QUYỀN VIP
                                </span>
                                <h3>Quà Tặng Độc Quyền Cho Phiên Bản Này!</h3>
                            </div>
                            <div v-for="offer in selectedVariantOffers" :key="offer.id_combo" class="variant-offer-card">
                                <div class="offer-left">
                                    <h4 class="offer-title">{{ offer.mota_uudai || 'Món Quà Tri Ân Đặc Biệt' }}</h4>
                                    <p class="offer-combo-name">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px; color: #f59e0b;">
                                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                                        </svg>
                                        Combo: <b>{{ offer.ten_combo }}</b>
                                    </p>
                                    <div class="offer-products-list">
                                        <span v-for="p in offer.products" :key="p.id_sanpham" class="offer-p-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px; color: #ef4444;">
                                                <rect x="3" y="11" width="18" height="10" rx="2"/>
                                                <path d="M12 2v9"/>
                                                <path d="M12 2a3 3 0 0 0-3 3c0 2 3 3 3 3"/>
                                                <path d="M12 2a3 3 0 0 1 3 3c0 2-3 3-3 3"/>
                                                <path d="M3 11h18"/>
                                            </svg>
                                            {{ p.tenSP }}
                                        </span>
                                    </div>
                                </div>
                                <div class="offer-right">
                                    <div class="price-label-free">
                                        <span class="old-price" v-if="offer.giakhuyenmai > 0">Trị giá: {{ formatPrice(offer.giakhuyenmai) }}</span>
                                        <span class="free-badge-text">MIỄN PHÍ 0đ</span>
                                    </div>
                                    <button class="btn-claim-offer" @click="openCombo(offer, selectedVariant)">
                                        Nhận Quà Ngay
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- GỢI Ý NÂNG CẤP CẤU HÌNH (UPSELLING TEASER) -->
                        <div v-else-if="otherVariantsWithOffers.length > 0" class="upsell-teaser-box">
                            <span class="teaser-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle;">
                                    <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A5 5 0 0 0 8 8c0 1 .3 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/>
                                    <path d="M9 18h6"/>
                                    <path d="M10 22h4"/>
                                </svg>
                            </span>
                            <div class="teaser-content">
                                <p class="teaser-text">
                                    <b>Gợi ý nâng cấp cấu hình:</b> Chọn phiên bản
                                    <span class="highlight-variant" @click="handleSelectVariantById(otherVariantsWithOffers[0].id_bienthe)" title="Click để chọn phiên bản này ngay">
                                        "{{ otherVariantsWithOffers[0].ten_bienthe }}"
                                    </span>
                                    để nhận ngay Quà Tặng
                                    <span class="free-text-badge">MIỄN PHÍ 0đ</span>:
                                    <b>{{ otherVariantsWithOffers[0].offers[0].ten_combo }}</b>!
                                </p>
                            </div>
                        </div>

                        <!-- KHUNG COMBO LIÊN QUAN -->
                        <div v-if="combos.length > 0" class="related-combos-box">
                            <h3 class="box-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 6px; color: #ef4444;">
                                    <rect x="3" y="11" width="18" height="10" rx="2"/>
                                    <path d="M12 2v9"/>
                                    <path d="M12 2a3 3 0 0 0-3 3c0 2 3 3 3 3"/>
                                    <path d="M12 2a3 3 0 0 1 3 3c0 2-3 3-3 3"/>
                                    <path d="M3 11h18"/>
                                </svg>
                                Deal Siêu Hời - Mua Theo Combo
                            </h3>
                            <div v-for="combo in combos" :key="combo.id_combo" class="related-combo-card">
                                <div class="combo-left">
                                    <span class="badge-discount">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 4px;">
                                            <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>
                                        </svg>
                                        Tiết kiệm hơn
                                    </span>
                                    <h4 class="clickable-combo" @click="openCombo(combo)" title="Xem chi tiết & cấu hình combo">
                                        {{ combo.ten_combo }} 
                                        <span class="info-icon">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-left: 2px;">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M12 16v-4"/>
                                                <path d="M12 8h.01"/>
                                            </svg>
                                        </span>
                                    </h4>
                                    <div class="combo-products-inline">
                                        <span v-for="(p, i) in combo.products" :key="p.id_sanpham" class="p-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 10px; height: 10px; display: inline-block; vertical-align: middle; margin-right: 4px; color: #3b82f6;"><polyline points="20 6 9 17 4 12"/></svg>
                                            <span class="clickable-product" @click="router.push('/products/' + p.id_sanpham)" title="Xem chi tiết sản phẩm">{{ p.tenSP }}</span><span v-if="i < combo.products.length - 1" class="plus"> + </span>
                                        </span>
                                    </div>
                                </div>
                                <div class="combo-right">
                                    <div class="price-box">
                                        <span class="label">Trọn bộ chỉ:</span>
                                        <span class="price">{{ formatPrice(combo.giakhuyenmai) }}</span>
                                    </div>
                                    <div class="combo-action-btns">
                                        <button class="btn-view-combo" @click="openCombo(combo)">
                                            Xem chi tiết
                                        </button>
                                        <button class="btn-buy-combo" @click="openCombo(combo)">
                                            Mua ngay
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- HIGH-TECH SPECIFICATIONS TABLE SECTION -->
        <div class="premium-specs-section" v-if="product.thong_so_ky_thuat && product.thong_so_ky_thuat.length > 0">
            <div class="container">
                <div class="specs-table-panel">
                    <div class="specs-panel-topbar">
                        <div>
                            <span class="accent-subtitle">SPECIFICATIONS</span>
                            <h2 class="section-main-title">Bảng Thông Tin Máy</h2>
                        </div>
                        <div class="specs-mode-tabs">
                            <button
                                :class="['specs-mode-btn', { active: specsPanelMode === 'info' }]"
                                @click="specsPanelMode = 'info'">
                                Xem thông tin máy
                            </button>
                            <button
                                :class="['specs-mode-btn', { active: specsPanelMode === 'compare' }]"
                                @click="specsPanelMode = 'compare'">
                                Lọc so sánh máy
                            </button>
                        </div>
                    </div>

                    <!-- TABBED SPECIFICATIONS CARDS -->
                    <div v-if="specsPanelMode === 'info'" class="tabbed-specs-layout">
                        <div class="specs-columns-grid">
                            <div v-for="(col, index) in specsColumns" :key="index" class="specs-column">
                                <h4 class="specs-column-title">{{ col.title }}</h4>
                                <div class="specs-column-list">
                                    <div v-for="(item, iIdx) in col.items" :key="iIdx" class="specs-column-item">
                                        <div class="specs-item-meta">
                                            <span class="specs-item-label">{{ item.label }}</span>
                                            <div class="specs-item-value-wrap">
                                                <strong class="specs-item-value">{{ item.value }}</strong>
                                                <span v-if="getItemBadge(item)" :class="['spec-badge', getItemBadge(item).type]">
                                                    {{ getItemBadge(item).text }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="inline-compare-area">
                        <div class="inline-compare-filter">
                            <div class="inline-filter-title">Chọn máy để so sánh</div>
                            <p class="inline-filter-note">Tối đa {{ maxCompare }} máy tương tự.</p>
                            <div class="no-related-msg" v-if="relatedProducts.length === 0">Không tìm thấy máy tương tự để so sánh.</div>
                            <div class="inline-compare-list" v-else>
                                <label v-for="p in relatedProducts" :key="p.key_id" class="inline-compare-item">
                                    <input
                                        type="checkbox"
                                        :value="p.key_id"
                                        v-model="compareSelection"
                                        :disabled="compareSelection.length >= maxCompare && !compareSelection.includes(p.key_id)" />
                                    <img :src="p.img" :alt="p.fullName" />
                                    <span class="inline-compare-name">{{ p.fullName }}</span>
                                    <span class="inline-compare-price">{{ formatPrice(p.price) }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="inline-compare-table-wrap">
                            <div class="empty-inline-compare" v-if="compareSelection.length === 0">
                                Chọn ít nhất một máy ở bên trái để xem bảng so sánh.
                            </div>
                            <table v-else class="inline-compare-table">
                                <thead>
                                    <tr>
                                        <th>Thông số</th>
                                        <th>Máy đang xem</th>
                                        <th v-for="p in compareProducts" :key="p.key_id">{{ p.fullName }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="attr in allAttributeKeys" :key="attr">
                                        <td class="machine-spec-name">{{ attr }}</td>
                                        <td class="machine-spec-value">
                                            <span v-if="product.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())">
                                                {{ product.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())?.giatri }}
                                            </span>
                                            <span v-else-if="selectedVariant && extractAllAttributes(selectedVariant)[attr.toLowerCase()]">
                                                {{ extractAllAttributes(selectedVariant)[attr.toLowerCase()] }}
                                            </span>
                                            <span v-else class="cell-no-value">-</span>
                                        </td>
                                        <td v-for="p in compareProducts" :key="p.key_id" class="machine-spec-value">
                                            <span v-if="p.thong_so_ky_thuat && p.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())">
                                                {{ p.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())?.giatri }}
                                            </span>
                                            <span v-else-if="p.attributes && p.attributes[attr]">{{ p.attributes[attr] }}</span>
                                            <span v-else class="cell-no-value">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                                    </div>
            </div>
        </div>

        <!-- NEW PRODUCT HIGHLIGHTS SECTION -->
        <div class="premium-highlights-section">
            <div class="container">
                <div class="section-title-wrap text-center">
                    <span class="accent-subtitle">FEATURES HIGHLIGHT</span>
                    <h2 class="section-main-title">Tại Sao Nên Chọn Sản Phẩm Này?</h2>
                    <p class="section-description-text text-center">Được chế tạo để dẫn đầu xu thế, mang lại trải nghiệm đỉnh cao cho mọi lập trình viên và nhà sáng tạo nội dung.</p>
                </div>

                <div class="highlights-grid">
                    <!-- Feature 1 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon icon-performance">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4.5 16.5c-1.5 1.25-2.5 3.5-2.5 3.5s2.25-1 3.5-2.5" />
                                    <path d="M12 12c2-2 3-5.5 3-5.5s-3.5 1-5.5 3" />
                                    <path d="M19 5c1.8-1.8 3-5 3-5s-3.2 1.2-5 3" />
                                    <path d="M14 15l-3.5-3.5" />
                                    <path d="M6.5 12.5l3.5 3.5" />
                                    <path d="M14 9l-2.5-2.5" />
                                    <path d="M9 14l2.5 2.5" />
                                </svg>
                            </div>
                            <h3>Hiệu năng bứt phá mọi giới hạn</h3>
                            <p>Tăng tốc tối đa các tác vụ nặng như render video 4K, compile source code cực nhanh nhờ tích hợp AI thông minh thế hệ mới nhất.</p>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon icon-screen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </div>
                            <h3>Màn hình Retina sắc nét đỉnh cao</h3>
                            <p>Không gian màu 100% DCI-P3 chuẩn xác, tần số quét cao siêu mượt cho trải nghiệm thiết kế đồ họa đỉnh cao chân thực.</p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon icon-battery">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="16" height="10" x="2" y="7" rx="2" ry="2" />
                                    <path d="M22 11v2" />
                                </svg>
                            </div>
                            <h3>Năng lượng bền bỉ suốt cả ngày</h3>
                            <p>Tối ưu hóa năng lượng siêu hiệu quả cùng chế độ sạc nhanh Type-C thông minh, giúp bạn tự tin làm việc di động không lo hết pin.</p>
                        </div>
                    </div>

                    <!-- Feature 4 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon icon-brain">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96-.44 2.5 2.5 0 0 1 0-3.12 3 3 0 0 1 0-4.88 2.5 2.5 0 0 1 0-3.12A2.5 2.5 0 0 1 9.5 2Z" />
                                    <path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96-.44 2.5 2.5 0 0 0 0-3.12 3 3 0 0 0 0-4.88 2.5 2.5 0 0 0 0-3.12A2.5 2.5 0 0 0 14.5 2Z" />
                                </svg>
                            </div>
                            <h3>Trí tuệ nhân tạo AI Ready thế hệ mới</h3>
                            <p>Vi xử lý tích hợp bộ gia tốc NPU riêng biệt, hỗ trợ đắc lực cho các thuật toán học máy và trợ lý ảo làm việc tối ưu nhất.</p>
                        </div>
                    </div>

                    <!-- Feature 5 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon icon-design">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 3h12l4 6-10 12L2 9z" />
                                    <path d="M11 3 8 9l4 12 4-12-3-6" />
                                    <path d="M2 9h20" />
                                </svg>
                            </div>
                            <h3>Thiết kế nhôm CNC cấp tàu vũ trụ</h3>
                            <p>Chế tác tinh xảo, đường cắt kim cương chuẩn xác, mỏng nhẹ thời thượng nhưng vô cùng bền bỉ đạt chuẩn quân đội.</p>
                        </div>
                    </div>

                    <!-- Feature 6 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon icon-cooling">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="2" x2="12" y2="22" />
                                    <line x1="2" y1="12" x2="22" y2="12" />
                                    <path d="m20 16-4-4 4-4" />
                                    <path d="m4 8 4 4-4 4" />
                                    <path d="m16 4-4 4-4-4" />
                                    <path d="m8 20 4-4 4 4" />
                                </svg>
                            </div>
                            <h3>Hệ thống tản nhiệt buồng hơi vượt trội</h3>
                            <p>Các cánh quạt siêu mỏng cùng buồng hơi kép giữ cho nhiệt độ luôn mát mẻ ngay cả khi chịu tải nặng kéo dài nhiều giờ liền.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BENCHMARK SYSTEM SECTION -->
        <div class="premium-benchmark-section">
            <div class="container">
                <div class="premium-section-header text-center">
                    <span class="section-label">Performance Core</span>
                    <h2>Kiểm Tra Hiệu Năng Thực Tế</h2>
                    <p class="section-description-text text-center">Điểm số benchmark đo đạc trực tiếp trên cấu hình phần cứng tối tân của máy.</p>
                </div>

                <div class="benchmark-container-grid">
                    <div v-for="(bench, idx) in benchmarkData" :key="idx" class="benchmark-progress-card">
                        <div class="bench-meta-row">
                            <span class="bench-label">{{ bench.label }}</span>
                            <span class="bench-score" :style="{ color: bench.color }">{{ bench.score }}%</span>
                        </div>
                        <div class="bench-progress-track">
                            <div class="bench-progress-fill" :style="{ width: bench.score + '%', backgroundColor: bench.color }"></div>
                        </div>
                        <p class="bench-desc">{{ bench.desc }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW PRODUCT STORYTELLING BLOCK SECTIONS (Apple Style) -->
        <div class="premium-storytelling-section">
            <!-- Story Block 1 -->
            <div class="story-block">
                <div class="container grid-2-columns">
                    <div class="story-content">
                        <span class="story-tag">DESIGN ARCHITECTURE</span>
                        <h2 class="story-title">Kiệt tác chế tác nhôm nguyên khối bền bỉ.</h2>
                        <p class="story-p">
                            Từng milimet của chiếc laptop này được điêu khắc tinh xảo từ một khối nhôm duy nhất bằng công nghệ cắt CNC chính xác cấp độ micrometer. Không chỉ mang lại một bộ khung mỏng nhẹ tuyệt đối, nó còn mang trong mình độ bền bỉ phi thường, chống chịu mọi va đập hàng ngày, sẵn sàng đồng hành cùng bạn trên mọi hành trình kiến tạo tương lai.
                        </p>
                        <div class="story-stats-row">
                            <div class="stat-unit">
                                <span class="num">1.49 kg</span>
                                <span class="desc">Siêu mỏng nhẹ</span>
                            </div>
                            <div class="stat-unit">
                                <span class="num">14.9 mm</span>
                                <span class="desc">Độ mỏng ấn tượng</span>
                            </div>
                        </div>
                    </div>
                    <div class="story-media-box">
                        <div class="glass-media-card">
                            <img :src="allImages[0]" alt="Thiết kế khung nhôm CNC" class="story-img" />
                        </div>
                    </div>
                </div>
            </div>


                <!-- Modal Chọn Biến Thể Combo -->
                <ComboSelectionModal
                    v-if="selectedCombo"
                    :combo="selectedCombo"
                    :show="showComboModal"
                    :triggerVariant="selectedTriggerVariant"
                    @close="showComboModal = false; selectedTriggerVariant = null"
                />

            <!-- Story Block 2 -->
            <div class="story-block alt-direction">
                <div class="container grid-2-columns">
                    <div class="story-media-box">
                        <div class="glass-media-card">
                            <img :src="allImages[1] || allImages[0]" alt="Sức mạnh xử lý AI NPU" class="story-img" />
                        </div>
                    </div>
                    <div class="story-content">
                        <span class="story-tag">PREDATOR INTELLIGENCE</span>
                        <h2 class="story-title">Kỷ nguyên trí tuệ nhân tạo local dẫn đầu xu hướng.</h2>
                        <p class="story-p">
                            Khởi động và vận hành các tác vụ trí tuệ nhân tạo AI cực nhanh mà không cần kết nối đám mây nhờ bộ vi xử lý được thiết kế chuyên biệt. Cho dù bạn đang xử lý thuật toán học sâu phức tạp hay tối ưu hình ảnh, bộ nhân NPU thế hệ mới sẽ giải quyết mọi thứ trong tích tắc với mức tiêu thụ điện năng tối thiểu nhất.
                        </p>
                        <div class="story-stats-row">
                            <div class="stat-unit">
                                <span class="num">38 TOPS</span>
                                <span class="desc">Sức mạnh tính toán AI</span>
                            </div>
                            <div class="stat-unit">
                                <span class="num">2.5X</span>
                                <span class="desc">Hiệu suất render AI</span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- REVIEWS RATING & FEEDBACK SECTION -->
        <div class="premium-reviews-section" id="reviews-section">
            <div class="container">
                <div class="section-title-wrap">
                    <span class="accent-subtitle">CUSTOMER REVIEWS</span>
                    <h2 class="section-main-title">Ý Kiến Từ Người Sử Dụng</h2>
                    <p class="section-description-text">Chúng tôi trân trọng từng phản hồi để mang đến sản phẩm và dịch vụ công nghệ hoàn hảo nhất.</p>
                </div>

                <!-- Main dashboard summary -->
                <div class="reviews-dashboard-grid">
                    <!-- Left Score Card -->
                    <div class="rating-overall-card">
                        <span class="card-label">ĐIỂM ĐÁNH GIÁ TRUNG BÌNH</span>
                        <div class="overall-score-number">{{ averageRating }}</div>
                        <div class="overall-stars">
                            <span v-for="i in 5" :key="i" class="dashboard-star">
                                {{ i <= Math.round(averageRating) ? '★' : '☆' }}
                            </span>
                        </div>
                        <span class="overall-total-count">Dựa trên {{ reviews.length }} phản hồi thực tế</span>
                    </div>

                    <!-- Center distribution meters -->
                    <div class="rating-meters-card">
                        <div class="meters-title">Phân tích sao đánh giá</div>

                        <div class="rating-meters-wrapper" v-if="reviews.length > 0">
                            <div v-for="stars in [5,4,3,2,1]" :key="stars" class="meter-bar-row">
                                <span class="stars-label">{{ stars }} ★</span>
                                <div class="meter-track">
                                    <div class="meter-fill" :style="{ width: (ratingDistribution[stars] / reviews.length * 100) + '%' }"></div>
                                </div>
                                <span class="percent-label">{{ Math.round((ratingDistribution[stars] || 0) / reviews.length * 100) }}%</span>
                            </div>
                        </div>
                        <div class="no-reviews-fallback" v-else>
                            <p>Không có dữ liệu phân tích. Hãy mua và để lại bình luận đầu tiên.</p>
                        </div>
                    </div>

                    <!-- Right Trust card -->
                    <div class="reviews-cskh-card">
                        <div class="cskh-avatar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 32px; height: 32px; color: #10b981; display: inline-block;">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <h3>Chính Sách Đánh Giá Khách Quan</h3>
                        <p>100% nhận xét đều xuất phát từ người mua đã hoàn thành thanh toán hóa đơn sản phẩm. Đội ngũ kỹ sư sẽ hỗ trợ phản hồi trong vòng 24 giờ.</p>
                    </div>
                </div>

                <!-- Reviews User Feedbacks list -->
                <div class="reviews-list-wrapper" v-if="reviews.length > 0">
                    <div class="review-feedback-card" v-for="review in reviews" :key="review.id_danhgia">
                        <div class="card-glow-outline"></div>
                        <div class="card-top-header">
                            <div class="user-profile-meta">
                                <div class="user-avatar-circle">
                                    {{ review.user?.name?.charAt(0).toUpperCase() || 'U' }}
                                </div>
                                <div class="name-date-stack">
                                    <b class="username">{{ review.user?.name || 'Khách hàng ẩn danh' }}</b>
                                    <span class="review-timestamp">✓ Đã mua hàng · {{ formatDate(review.created_at) }}</span>
                                </div>
                            </div>
                            <div class="review-badge-stars">
                                <span v-for="s in 5" :key="s" class="star">
                                    {{ s <= review.danhgia ? '★' : '☆' }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body-text">
                            <p class="comment-p">"{{ review.binhluan || 'Sản phẩm hoạt động cực tốt, cấu hình cực mạnh, màn hình siêu nét đúng như mô tả của website.' }}"</p>
                        </div>
                    </div>
                </div>

                <!-- Empty reviews visual state -->
                <div v-else class="empty-reviews-state">
                    <div class="empty-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 10.742h.008v.008h-.008v-.008zm.37 0h.008v.008h-.008v-.008zM12 20.25c4.556 0 8.25-3.694 8.25-8.25S16.556 3.75 12 3.75 3.75 7.444 3.75 12s3.694 8.25 8.25 8.25z" />
                        </svg>
                    </div>
                    <h4>Chưa có đánh giá nào cho siêu phẩm này</h4>
                    <p>Hãy là những người đầu tiên sở hữu để chia sẻ những cảm nhận và đánh giá chân thực nhất với cộng đồng.</p>
                </div>
            </div>
        </div>

        <!-- RELATED PRODUCTS SECTION -->
        <div class="premium-related-products-section" v-if="filteredRelatedProducts.length > 0">
            <div class="container">
                <div class="related-section-header">
                    <div class="title-side">
                        <span class="subtitle-tag">RECOMMENDATIONS</span>
                        <h2 class="main-title" v-if="selectedCategory">Sản Phẩm Tương Tự: {{ selectedCategory }}</h2>
                        <h2 class="main-title" v-else>Có Thể Bạn Sẽ Thích</h2>
                    </div>
                    <router-link to="/products" class="action-all-link">Xem tất cả sản phẩm <span class="arrow">→</span></router-link>
                </div>

                <!-- Grid layout of Related items -->
                <div class="related-products-grid">
                    <div class="premium-product-card" v-for="p in paginatedRelatedProducts" :key="p.key_id"
                         @click="router.push(`/products/${p.id}?variant=${p.key_id}`)">
                        <div class="card-glow-outline"></div>
                        <div class="badge-row">
                            <span class="tag-badge badge-glow">HOT PROMO</span>
                        </div>

                        <div class="product-image-box">
                            <img :src="p.img" :alt="p.fullName" class="card-main-image" />
                        </div>

                        <div class="product-info-box">
                            <h4 class="product-card-title">{{ p.fullName }}</h4>
                            <p class="product-card-specs">{{ p.specText }}</p>

                            <!-- Rating stars for small related card -->
                            <div class="product-card-rating">
                                <span class="stars">⭐⭐⭐⭐⭐</span>
                                <span class="score">5.0</span>
                            </div>

                            <div class="product-card-bottom-row">
                                <div class="price-side">
                                    <span class="price-title">Giá từ</span>
                                    <span class="price-tag">{{ formatPrice(p.price) }}</span>
                                </div>
                                <button class="btn-quick-view" aria-label="Xem chi tiết sản phẩm">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Pagination links -->
                <div class="premium-pagination-container" v-if="totalRelatedPages > 1">
                    <button class="pag-btn" :disabled="currentRelatedPage === 1" @click="currentRelatedPage--">
                        &laquo; Trước
                    </button>
                    <div class="pag-numbers-box">
                        <button v-for="p in totalRelatedPages" :key="p"
                                :class="['pag-number-btn', { active: currentRelatedPage === p }]"
                                @click="currentRelatedPage = p">
                            {{ p }}
                        </button>
                    </div>
                    <button class="pag-btn" :disabled="currentRelatedPage === totalRelatedPages" @click="currentRelatedPage++">
                        Sau &raquo;
                    </button>
                </div>
            </div>
        </div>

        <!-- RECENTLY VIEWED PRODUCTS SECTION -->
        <div class="premium-related-products-section" v-if="recentlyViewedProducts.length > 0">
            <div class="container">
                <div class="related-section-header">
                    <div class="title-side">
                        <span class="subtitle-tag">HISTORY</span>
                        <h2 class="main-title">Sản Phẩm Đã Xem Gần Đây</h2>
                    </div>
                </div>

                <!-- Grid of recently viewed items -->
                <div class="related-products-grid">
                    <div class="premium-product-card" v-for="p in paginatedRecentlyViewedProducts" :key="p.key_id"
                         @click="router.push(`/products/${p.id}?variant=${p.key_id}`)">
                        <div class="card-glow-outline"></div>
                        <div class="product-image-box">
                            <img :src="p.img" :alt="p.fullName" class="card-main-image" />
                        </div>

                        <div class="product-info-box">
                            <h4 class="product-card-title">{{ p.fullName }}</h4>
                            <p class="product-card-specs">{{ p.specText }}</p>

                            <div class="product-card-bottom-row">
                                <div class="price-side">
                                    <span class="price-title">Giá từ</span>
                                    <span class="price-tag">{{ formatPrice(p.price) }}</span>
                                </div>
                                <button class="btn-quick-view" aria-label="Xem chi tiết sản phẩm">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Viewed pagination links -->
                <div class="premium-pagination-container" v-if="totalRecentlyViewedPages > 1">
                    <button class="pag-btn" :disabled="currentRecentlyViewedPage === 1" @click="currentRecentlyViewedPage--">
                        &laquo; Trước
                    </button>
                    <div class="pag-numbers-box">
                        <button v-for="p in totalRecentlyViewedPages" :key="p"
                                :class="['pag-number-btn', { active: currentRecentlyViewedPage === p }]"
                                @click="currentRecentlyViewedPage = p">
                            {{ p }}
                        </button>
                    </div>
                    <button class="pag-btn" :disabled="currentRecentlyViewedPage === totalRecentlyViewedPages" @click="currentRecentlyViewedPage++">
                        Sau &raquo;
                    </button>
                </div>
            </div>
        </div>

        <!-- POPUP SO SÁNH MODAL (High-tech full responsive design) -->
        <div class="compare-modal-wrapper">
            <transition name="fade">
                <div class="compare-modal-overlay" v-if="showCompareModal">
                    <div class="compare-modal-card">
                        <div class="modal-glow-boundary"></div>

                        <div class="compare-modal-header">
                            <div class="header-titles">
                                <h3>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px;">
                                        <line x1="18" y1="20" x2="18" y2="10"/>
                                        <line x1="12" y1="20" x2="12" y2="4"/>
                                        <line x1="6" y1="20" x2="6" y2="14"/>
                                    </svg>
                                    So Sánh Hiệu Năng & Chi Tiết Phần Cứng
                                </h3>
                                <p>Chọn sản phẩm tương tự để so sánh trực tiếp các chỉ số (tối đa {{ maxCompare }} sản phẩm)</p>
                            </div>
                            <button class="close-modal-btn" @click="closeCompareModal" aria-label="Đóng cửa sổ">✕</button>
                        </div>

                        <div class="compare-modal-body">
                            <!-- Left sidebar selector list -->
                            <div class="compare-products-picker-panel">
                                <div class="panel-section-title">Danh sách sản phẩm tương đồng:</div>
                                <div class="no-related-msg" v-if="relatedProducts.length === 0">Không tìm thấy máy tương đương cấu hình.</div>

                                <div class="picker-list-wrapper" v-else>
                                    <label v-for="p in relatedProducts" :key="p.key_id" class="picker-item-row">
                                        <input type="checkbox" :value="p.key_id" v-model="compareSelection"
                                               :disabled="compareSelection.length >= maxCompare && !compareSelection.includes(p.key_id)"
                                               class="custom-tech-checkbox" />
                                        <div class="p-thumb-box"><img :src="p.img" :alt="p.fullName" /></div>
                                        <div class="p-info-side">
                                            <span class="p-name">{{ p.fullName }}</span>
                                            <span class="p-price">{{ formatPrice(p.price) }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Right comparison table -->
                            <div class="compare-results-table-panel">
                                <div class="empty-compare-selection-state" v-if="compareSelection.length === 0">
                                    <span class="icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; color: #94a3b8; display: inline-block;">
                                            <line x1="18" y1="20" x2="18" y2="10"/>
                                            <line x1="12" y1="20" x2="12" y2="4"/>
                                            <line x1="6" y1="20" x2="6" y2="14"/>
                                        </svg>
                                    </span>
                                    <h4>Chưa chọn sản phẩm so sánh</h4>
                                    <p>Vui lòng tích chọn ít nhất một cấu hình máy tính ở bảng bên trái để bắt đầu đo thông số kỹ thuật.</p>
                                </div>

                                <div class="table-scroll-view" v-else>
                                    <table class="comparison-tech-table">
                                        <thead>
                                            <tr>
                                                <th class="attribute-col">Thông số phần cứng</th>
                                                <th class="product-showcase-col active-current">
                                                    <div class="col-product-card">
                                                        <img :src="selectedImage" alt="Sản phẩm hiện tại" />
                                                        <span class="label-now">HIỆN TẠI</span>
                                                        <span class="name">{{ product.tenSP }}</span>
                                                        <span class="price-val">{{ selectedVariant ? formatPrice(selectedVariant.gia) : formatPrice(product.gia) }}</span>
                                                    </div>
                                                </th>
                                                <th class="product-showcase-col" v-for="p in compareProducts" :key="p.key_id">
                                                    <div class="col-product-card">
                                                        <img :src="p.img" :alt="p.fullName" />
                                                        <span class="name">{{ p.fullName }}</span>
                                                        <span class="price-val">{{ formatPrice(p.price) }}</span>
                                                    </div>
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr v-for="attr in allAttributeKeys" :key="attr">
                                                <td class="attribute-col">{{ attr.toUpperCase() }}</td>
                                                <td class="product-showcase-col active-current">
                                                    <!-- Check spec array first -->
                                                    <span v-if="product.thong_so_ky_thuat && product.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())" class="cell-value-text">
                                                        {{ product.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())?.giatri }}
                                                    </span>
                                                    <!-- Else check variant attr -->
                                                    <span v-else-if="extractAllAttributes(selectedVariant)[attr.toLowerCase()]" class="cell-value-text">
                                                        {{ extractAllAttributes(selectedVariant)[attr.toLowerCase()] }}
                                                    </span>
                                                    <span v-else class="cell-no-value">—</span>
                                                </td>
                                                <td class="product-showcase-col" v-for="p in compareProducts" :key="p.key_id">
                                                    <!-- Check spec array first -->
                                                    <span v-if="p.thong_so_ky_thuat && p.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())" class="cell-value-text">
                                                        {{ p.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())?.giatri }}
                                                    </span>
                                                    <!-- Else check variant attr -->
                                                    <span v-else-if="(p.attributes || {})[attr.toLowerCase()]" class="cell-value-text">
                                                        {{ (p.attributes || {})[attr.toLowerCase()] }}
                                                    </span>
                                                    <span v-else class="cell-no-value">—</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>

    <!-- MAIN LOADING SCREEN -->
    <div class="immersive-loader-screen" v-if="false">
        <div class="loader-ripple-glow"></div>
        <div class="loader-content-wrap">
            <span class="loading-spinner"></span>
            <h3>Đang giải mã dữ liệu sản phẩm...</h3>
            <p>Vui lòng đợi trong khi chúng tôi chuẩn bị giao diện trải nghiệm sản phẩm cao cấp.</p>
        </div>
    </div>
  </div>
</template>

<style scoped>

@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap');

/* ==================== STICKY BUY BAR & NEW CONVERSION SECTIONS ==================== */
.sticky-buy-bar {
    position: fixed;
    top: 102px; /* Đặt dưới header trên desktop (34px announcement bar + 68px main header) */
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid #E2E8F0;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
    z-index: 999; /* Dưới header (1000) để khi ẩn/hiện trượt phía sau header trông mượt mà hơn */
    padding: 12px 0;
    transform: translateY(0);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@media (max-width: 600px) {
    .sticky-buy-bar {
        top: 60px; /* Đặt dưới header trên mobile (chiều cao header mobile là 60px, top: 0) */
    }
}

.sticky-bar-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.sticky-info-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.sticky-thumb {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--tn-border);
}

.sticky-meta {
    display: flex;
    flex-direction: column;
}

.sticky-title {
    font-size: 15px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
}

.sticky-variant-name {
    font-size: 12px;
    color: #64748B;
}

.sticky-actions-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.sticky-price-glow {
    font-size: 18px;
    font-weight: 800;
    color: #2563EB;
}

.sticky-cart-icon-btn {
    width: 48px;
    min-width: 48px;
    height: 44px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    color: #2563EB;
    background: var(--tn-surface);
    border: 1px solid #dbeafe;
    overflow: visible;
}

.sticky-cart-icon {
    width: 22px;
    height: 22px;
    display: block;
    flex: 0 0 auto;
    opacity: 1;
    stroke: #2563eb;
    fill: none;
    pointer-events: none;
}

/* Benefits Box */
.product-benefits-box {
    background: var(--tn-bg);
    border: 1px solid var(--tn-border);
    border-radius: 12px;
    padding: 14px 18px;
    margin: 14px 0;
}

.benefits-title {
    font-size: 13px;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 8px 0;
}

.benefits-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.benefits-list li {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12.5px;
    color: #475569;
}

.benefit-icon {
    font-size: 14px;
    flex-shrink: 0;
}

/* Guarantees Box */
.product-guarantees-box {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin: 14px 0;
}

.guarantee-card {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--tn-surface);
    border: 1px solid var(--tn-border);
    border-radius: 10px;
    padding: 8px 12px;
}

.g-icon {
    font-size: 18px;
}

.g-text-wrap {
    display: flex;
    flex-direction: column;
}

.g-title {
    font-size: 12px;
    font-weight: 700;
    color: #0F172A;
}

.g-desc {
    font-size: 10px;
    color: #64748B;
}

/* Categorized Specs Tabs */
.tabbed-specs-layout {
    padding: 32px;
}

.specs-category-tabs {
    display: flex;
    gap: 12px;
    border-bottom: 2px solid #f1f5f9;
    padding-bottom: 16px;
    margin-bottom: 28px;
    overflow-x: auto;
    scrollbar-width: none; /* Firefox */
}

.specs-category-tabs::-webkit-scrollbar {
    display: none; /* Safari and Chrome */
}

.spec-tab-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 22px;
    border-radius: 14px;
    border: 1px solid var(--tn-border);
    background: var(--tn-bg);
    color: #64748B;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.spec-tab-btn:hover {
    color: #2563EB;
    background: #f0f6ff;
    border-color: #bfdbfe;
    transform: translateY(-1px);
}

.spec-tab-btn.active {
    color: #ffffff;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border-color: #2563eb;
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.25);
}

.spec-tab-content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.spec-detail-card {
    background: var(--tn-surface);
    border: 1px solid var(--tn-border);
    border-left: 4px solid #2563eb; /* Premium blue accent line */
    border-radius: 16px;
    padding: 20px 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.spec-detail-card:hover {
    transform: translateY(-4px);
    border-color: #cbd5e1;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
}

.spec-row-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 6px;
}

.spec-row-value {
    font-size: 15px;
    font-weight: 850;
    color: #0F172A;
    line-height: 1.4;
}

/* Benchmark system styling */
.premium-benchmark-section {
    padding: 80px 0;
    background: var(--tn-surface);
    border-top: 1px solid #E2E8F0;
    border-bottom: 1px solid #E2E8F0;
}

.benchmark-container-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    margin-top: 40px;
}

@media (max-width: 768px) {
    .benchmark-container-grid {
        grid-template-columns: 1fr;
    }
}

.benchmark-progress-card {
    background: var(--tn-bg);
    border: 1px solid var(--tn-border);
    border-radius: 16px;
    padding: 24px;
}

.bench-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.bench-label {
    font-weight: 700;
    color: #0F172A;
    font-size: 14.5px;
}

.bench-score {
    font-weight: 800;
    font-size: 16px;
}

.bench-progress-track {
    height: 10px;
    background: #E2E8F0;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 12px;
}

.bench-progress-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 1s ease-in-out;
}

.bench-desc {
    font-size: 12.5px;
    color: #64748B;
    margin: 0;
}

/* Fade Slide transition for Sticky Bar */
.fade-slide-bar-enter-active,
.fade-slide-bar-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-slide-bar-enter-from,
.fade-slide-bar-leave-to {
    opacity: 0;
    transform: translateY(-100%);
}



/* ==================== GLOBAL PREMIUM VARIABLES ==================== */
.page {
    --primary: #2563EB;
    --primary-glow: rgba(37, 99, 235, 0.15);
    --secondary: #06B6D4;
    --secondary-glow: rgba(6, 182, 212, 0.15);
    --accent: #f59e0b;
    --dark-bg: #0F172A;
    --dark-surface: #111827;
    --light-bg: #f8fafc;
    --light-surface: #ffffff;
    --text-primary: #0F172A;
    --text-secondary: #475569;
    --border-color: rgba(255,255,255,0.07);
    --card-glow: 0px 8px 30px rgba(0, 0, 0, 0.04);
    --font-heading: 'Outfit', 'Inter', sans-serif;
    --font-body: 'Inter', sans-serif;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    background: var(--tn-surface);
    color: var(--text-primary);
    font-family: var(--font-body);
    overflow-x: clip;
    position: relative;
}

/* ==================== TOAST & DECORATIONS ==================== */
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
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    animation: toast-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes toast-in {
    from { transform: translateY(-20px) scale(0.9); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}
.toast.success {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
}
.toast.error {
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
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

.tech-glow-top {
    position: absolute;
    top: 0;
    left: 10%;
    width: 80%;
    height: 400px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.05) 0%, rgba(6, 182, 212, 0.02) 50%, transparent 100%);
    pointer-events: none;
    z-index: 0;
}

.container {
    max-width: 1300px;
    margin: 0 auto;
    padding: 0 24px;
    position: relative;
    z-index: 1;
}

/* ==================== BREADCRUMBS ==================== */
.premium-breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    padding: 24px 0 16px 0;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-secondary);
}
.premium-breadcrumb a {
    color: var(--text-secondary);
    text-decoration: none;
    transition: var(--transition);
}
.premium-breadcrumb a:hover {
    color: var(--primary);
}
.premium-breadcrumb .sep {
    color: #cbd5e1;
    font-size: 11px;
}
.premium-breadcrumb .current {
    color: var(--primary);
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 250px;
}

/* ==================== IMMERSIVE HERO WRAPPER ==================== */
.premium-hero-container {
    position: relative;
    padding-bottom: 40px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.detail-hero-grid {
    display: grid;
    grid-template-columns: 1.02fr 0.98fr;
    gap: 48px;
    align-items: start;
}

/* ==================== GALLERY COLUMN ==================== */
.gallery-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
    position: sticky;
    top: 120px;
    max-width: 530px;
}

.main-image-viewport {
    position: relative;
    aspect-ratio: 1/1;
    border-radius: 28px;
    background: var(--tn-surface);
    border: 1px solid rgba(15, 23, 42, 0.08);
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.06), inset 0 2px 4px rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px;
    overflow: hidden;
}

.neon-glow-backdrop {
    position: absolute;
    width: 60%;
    height: 60%;
    background: radial-gradient(circle, rgba(6, 182, 212, 0.08) 0%, transparent 70%);
    filter: blur(40px);
    pointer-events: none;
    z-index: 0;
}

.main-showcase-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    z-index: 1;
    transition: transform 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.main-image-viewport:hover .main-showcase-image {
    transform: scale(1.05);
}

.gallery-badges {
    position: absolute;
    top: 20px;
    left: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 2;
}
.badge {
    padding: 6px 12px;
    border-radius: 30px;
    font-family: var(--font-heading);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
}
.badge-glow {
    background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
}
.badge-tech {
    background: #0F172A;
    color: #e2e8f0;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.premium-out-of-stock-badge {
    position: absolute;
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: white;
    font-family: var(--font-heading);
    font-weight: 800;
    letter-spacing: 2px;
    font-size: 18px;
    padding: 16px 36px;
    border-radius: 50px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    z-index: 3;
}

.gallery-nav-arrow {
    position: absolute;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255,255,255,0.07);
    color: var(--text-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    transition: var(--transition);
    z-index: 2;
}
.gallery-nav-arrow svg {
    width: 20px;
    height: 20px;
}
.gallery-nav-arrow:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
    transform: translateY(-2px);
}
.arrow-left { left: 20px; }
.arrow-right { right: 20px; }

.slide-dots {
    position: absolute;
    bottom: 20px;
    display: flex;
    gap: 6px;
    z-index: 2;
}
.slide-dots .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
    cursor: pointer;
    transition: var(--transition);
}
.slide-dots .dot.active {
    width: 24px;
    border-radius: 10px;
    background: var(--primary);
}

.premium-thumbs-container {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
}
.thumb-arrow-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--tn-surface);
    border: 1px solid #cbd5e1;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
}
.thumb-arrow-btn:hover:not(:disabled) {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
.thumb-arrow-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.premium-thumbs-scroll {
    display: flex;
    gap: 12px;
}
.thumb-card {
    width: 72px;
    height: 72px;
    border-radius: 16px;
    background: var(--tn-surface);
    border: 2px solid transparent;
    cursor: pointer;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
    transition: var(--transition);
}
.thumb-card img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.thumb-card.active {
    border-color: var(--primary);
    box-shadow: 0 4px 15px var(--primary-glow);
    transform: translateY(-2px);
}

/* ==================== PURCHASE COLUMN ==================== */
.purchase-column {
    display: flex;
    flex-direction: column;
}

.tech-spec-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
}
.pill-spec-badge {
    padding: 4px 10px;
    border-radius: 30px;
    background: rgba(37, 99, 235, 0.06);
    border: 1px solid rgba(37, 99, 235, 0.12);
    color: var(--primary);
    font-size: 10.5px;
    font-weight: 700;
    font-family: var(--font-heading);
    letter-spacing: 0.5px;
}

.brand-subtitle {
    font-family: var(--font-heading);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: var(--secondary);
    margin-bottom: 6px;
}

.premium-product-title {
    font-family: var(--font-heading);
    font-size: 26px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.25;
    margin: 0 0 10px 0;
    letter-spacing: -0.5px;
}

.premium-rating-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    cursor: pointer;
    margin-bottom: 16px;
    width: fit-content;
    padding: 3px 10px;
    background: var(--tn-surface);
    border-radius: 30px;
    border: 1px solid rgba(15, 23, 42, 0.08);
}
.stars-gold {
    color: var(--accent);
    letter-spacing: 1.5px;
}
.rating-numeric {
    color: var(--text-primary);
}
.rating-separator {
    color: #cbd5e1;
}

.premium-price-container {
    padding: 16px 20px;
    border-radius: 16px;
    background: #111f35;
    box-shadow: 0 15px 30px rgba(15, 23, 42, 0.12);
    margin-bottom: 16px;
    position: relative;
    overflow: hidden;
}
.premium-price-container::before {
    display: none;
}
.price-label {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: block;
    margin-bottom: 2px;
}
.price-value-glow {
    font-family: var(--font-heading);
    font-size: 30px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    margin-bottom: 8px;
}
.price-badges-row {
    display: flex;
    gap: 12px;
}
.premium-badge-check {
    font-size: 11px;
    font-weight: 600;
    color: #38bdf8;
}

.premium-trust-signals-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 28px;
}
.signal-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 14px;
    background: var(--tn-surface);
    border: 1px solid rgba(15, 23, 42, 0.08);
}
.signal-icon {
    font-size: 16px;
}
.signal-text {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
}

/* ==================== SELECTORS ==================== */
.premium-selectors-wrapper {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px 12px;
    margin: 14px 0 16px;
}
.premium-option-group {
    display: grid;
    grid-template-columns: minmax(70px, 0.65fr) minmax(0, 1.35fr);
    align-items: center;
    gap: 6px 10px;
    min-height: 40px;
    padding: 6px 10px;
    border: 1px solid #dbe5f0;
    border-radius: 10px;
    background: #ffffff;
}
.option-header-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 2px;
    min-width: 0;
    font-size: 12px;
    font-weight: 750;
}
.option-label-title {
    overflow: hidden;
    max-width: 100%;
    color: var(--text-primary);
    font-weight: 850;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.option-selected-value {
    color: var(--primary);
    font-size: 11px;
    font-weight: 800;
    line-height: 1.25;
}

.premium-color-selectors {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 8px;
    min-width: 0;
}
.color-selector-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: transparent;
    position: relative;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.color-core {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.15);
    z-index: 1;
}
.color-ring-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border: 2px solid transparent;
    border-radius: 50%;
    transition: var(--transition);
}
.color-selector-btn.active .color-ring-glow {
    border-color: var(--primary);
    transform: scale(1.05);
}

.premium-pill-selectors {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 6px;
    min-width: 0;
}
.pill-selector-btn {
    min-height: 34px;
    max-width: 100%;
    padding: 7px 12px;
    border-radius: 10px;
    background: var(--tn-surface);
    border: 1.5px solid #d6e0ec;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}
.pill-text {
    overflow: hidden;
    max-width: 100%;
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
    text-overflow: ellipsis;
    white-space: nowrap;
    z-index: 1;
}
.active-indicator {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--primary);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.25s ease-out;
    z-index: 0;
}
.pill-selector-btn:hover {
    border-color: var(--primary);
    background: #eff6ff;
    transform: translateY(-1px);
}
.pill-selector-btn.active {
    border-color: var(--primary);
    box-shadow: 0 8px 16px rgba(37, 99, 235, 0.18);
}
.pill-selector-btn.active .pill-text {
    color: white;
}
.pill-selector-btn.active .active-indicator {
    transform: scaleX(1);
}

.updating-text {
    font-size: 11px;
    color: var(--text-secondary);
}

/* ==================== STOCK & CTAS ==================== */
.premium-stock-banner {
    margin-bottom: 16px;
}
.stock-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 10px;
    font-size: 11.5px;
    font-weight: 600;
}
.stock-status.in-stock {
    background: #ecfdf5;
    color: #065f46;
}
.stock-status.out-of-stock {
    background: #fef2f2;
    color: #991b1b;
}

.pulse-green-dot, .pulse-red-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    animation: dot-pulse 1.5s infinite;
}
.pulse-green-dot { background-color: #10B981; }
.pulse-red-dot { background-color: #EF4444; }

@keyframes dot-pulse {
    0% { transform: scale(0.95); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.5; }
    100% { transform: scale(0.95); opacity: 1; }
}

.purchase-actions-box {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.premium-qty-stepper {
    display: flex;
    align-items: center;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    overflow: hidden;
    width: fit-content;
    background: var(--tn-surface);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
}
.stepper-btn {
    width: 34px;
    height: 34px;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0f172a;
    transition: var(--transition);
}
.stepper-btn:hover:not(:disabled) {
    background: #eff6ff;
    color: var(--primary);
}
.stepper-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.stepper-value {
    min-width: 36px;
    text-align: center;
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
}

.actions-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 12px;
}

.btn-buy-now {
    height: 44px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
    color: white;
    font-family: var(--font-heading);
    font-size: 12.5px;
    font-weight: 800;
    letter-spacing: 0.5px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    box-shadow: 0 6px 18px var(--primary-glow);
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}
.btn-buy-now:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
}
.btn-buy-now:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    box-shadow: none;
}
.btn-content-text {
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-installment {
    height: 44px;
    border-radius: 10px;
    border: 1.5px solid var(--primary);
    background: transparent;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}
.btn-installment .top-tag {
    font-family: var(--font-heading);
    font-size: 8px;
    font-weight: 800;
    color: var(--primary);
    letter-spacing: 0.5px;
}
.btn-installment .main-text {
    font-family: var(--font-heading);
    font-size: 11px;
    font-weight: 800;
    color: var(--text-primary);
}
.btn-installment:hover {
    background: rgba(37, 99, 235, 0.04);
    transform: translateY(-1px);
}

.floating-shortcuts-row {
    display: flex;
    gap: 12px;
}
.shortcut-action-btn {
    flex: 1;
    height: 36px;
    border-radius: 8px;
    border: 1px solid var(--tn-border);
    background: var(--tn-bg);
    font-size: 12px;
    font-weight: 800;
    color: #475569;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: var(--transition);
}
.shortcut-action-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(37, 99, 235, 0.05);
}

/* ==================== SPECIFICATIONS SECTION ==================== */
.premium-specs-section {
    padding: 80px 0;
    background: var(--tn-bg);
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.section-title-wrap {
    margin-bottom: 48px;
    max-width: 600px;
}
.section-title-wrap.text-center {
    margin-left: auto;
    margin-right: auto;
}
.accent-subtitle {
    font-family: var(--font-heading);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.15em;
    color: var(--primary);
    display: block;
    margin-bottom: 8px;
    text-transform: uppercase;
}
.section-main-title {
    font-family: var(--font-heading);
    font-size: 36px;
    font-weight: 800;
    color: var(--text-primary);
    margin: 0 0 12px 0;
    letter-spacing: -0.5px;
}
.section-description-text {
    font-size: 15px;
    color: var(--text-secondary);
    line-height: 1.6;
    margin: 0;
}

.specs-table-panel {
    background: var(--tn-surface);
    border: 1px solid var(--tn-border);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
    overflow: hidden;
}

.specs-panel-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 18px 24px;
    background: var(--tn-surface);
    border-bottom: 1px solid #e2e8f0;
}

.specs-panel-topbar .section-main-title {
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 0;
    color: #0f172a;
    letter-spacing: -0.5px;
}

.specs-mode-tabs {
    display: flex;
    gap: 4px;
    padding: 3px;
    border-radius: 8px;
    background: var(--tn-surface-2);
    border: 1px solid var(--tn-border);
    flex-shrink: 0;
}

.specs-mode-btn {
    border: none;
    border-radius: 6px;
    padding: 5px 10px;
    background: transparent;
    color: #475569;
    font-family: var(--font-heading);
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
}

.specs-mode-btn:hover {
    color: var(--primary);
}

.specs-mode-btn.active {
    background: var(--tn-surface);
    color: var(--primary);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.tabbed-specs-layout {
    padding: 24px;
    background: var(--tn-surface);
}

.specs-columns-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
    border-radius: 14px;
    background: var(--tn-bg);
    border: 1px solid var(--tn-border);
    overflow: hidden;
}

.specs-column {
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    position: relative;
}

.specs-column:not(:last-child) {
    border-right: 1px solid #e2e8f0;
}

.specs-column-title {
    font-family: var(--font-heading);
    font-size: 10px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin: 0 0 2px 0;
    border-bottom: 1.5px solid #e2e8f0;
    padding-bottom: 6px;
}

.specs-column-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.specs-column-item {
    display: flex;
    flex-direction: column;
    padding: 4px 0;
    background: transparent;
    border: none;
    transition: var(--transition);
}

.specs-column-item:hover {
    transform: translateX(2px);
}

.specs-item-meta {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.specs-item-label {
    font-size: 8.5px;
    font-weight: 750;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.specs-item-value-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.specs-item-value {
    font-size: 12.5px;
    font-weight: 750;
    color: #1e293b;
    line-height: 1.25;
}

.spec-badge {
    font-size: 7.5px;
    font-weight: 800;
    padding: 1px 3px;
    border-radius: 3px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.spec-badge.hot {
    background: rgba(244, 63, 94, 0.08);
    color: #e11d48;
    border: 1px solid rgba(244, 63, 94, 0.1);
}

.spec-badge.pro {
    background: rgba(99, 102, 241, 0.08);
    color: #4f46e5;
    border: 1px solid rgba(99, 102, 241, 0.1);
}

.spec-badge.new {
    background: rgba(16, 185, 129, 0.08);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.1);
}

.inline-compare-area {
    display: grid;
    grid-template-columns: 280px 1fr;
    min-height: 400px;
    background: var(--tn-surface);
    border-top: 1px solid #e2e8f0;
}

.inline-compare-filter {
    padding: 16px;
    background: var(--tn-bg);
    border-right: 1px solid #e2e8f0;
}

.inline-filter-title {
    color: #0f172a;
    font-family: var(--font-heading);
    font-size: 14px;
    font-weight: 800;
    margin-bottom: 4px;
}

.inline-filter-note {
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
    margin: 0 0 12px;
}

.inline-compare-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 480px;
    overflow-y: auto;
    padding-right: 4px;
}

.inline-compare-item {
    display: grid;
    grid-template-columns: 18px 40px 1fr;
    grid-template-areas:
        "check image name"
        "check image price";
    gap: 4px 8px;
    align-items: center;
    padding: 8px;
    border-radius: 8px;
    background: var(--tn-surface);
    border: 1px solid var(--tn-border);
    cursor: pointer;
    transition: var(--transition);
}

.inline-compare-item:hover {
    border-color: var(--primary);
    background: #f0f6ff;
}

.inline-compare-item input {
    grid-area: check;
    accent-color: var(--primary);
}

.inline-compare-item img {
    grid-area: image;
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.inline-compare-name {
    grid-area: name;
    color: #0f172a;
    font-size: 11px;
    font-weight: 800;
    line-height: 1.3;
}

.inline-compare-price {
    grid-area: price;
    color: var(--primary);
    font-size: 11px;
    font-weight: 800;
}

.empty-inline-compare {
    height: 100%;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    padding: 24px;
}

.inline-compare-table-wrap {
    overflow-x: auto;
}

.inline-compare-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

.inline-compare-table th {
    background: var(--tn-bg);
    color: #0f172a;
    font-family: var(--font-heading);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    vertical-align: top;
}

.inline-compare-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    vertical-align: top;
    color: #334155;
}

.inline-compare-table tr:nth-child(even) td {
    background: var(--tn-bg);
}

.inline-compare-table tr:last-child td {
    border-bottom: none;
}

.machine-spec-name {
    width: auto;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    line-height: 1.2;
}

.machine-spec-value {
    color: #0f172a;
    font-family: var(--font-heading);
    font-size: 13px;
    font-weight: 800;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.cell-no-value {
    color: #94a3b8;
}

@media (max-width: 992px) {
    .specs-columns-grid {
        grid-template-columns: 1fr;
    }
    .specs-column {
        padding: 16px 0;
    }
    .specs-column:not(:last-child) {
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
    }
}

.specs-modern-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.spec-card {
    position: relative;
    padding: 24px;
    border-radius: 20px;
    background: var(--tn-bg);
    border: 1px solid #e6eef6;
    overflow: hidden;
    transition: var(--transition);
}
.card-glow-layer {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at 10% 10%, rgba(37, 99, 235, 0.04) 0%, transparent 60%);
    pointer-events: none;
}
.spec-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--tn-surface);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 16px rgba(15, 23, 42, 0.03);
    margin-bottom: 16px;
}
.spec-card-icon svg {
    width: 22px;
    height: 22px;
}
.spec-card-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.spec-card-content .label {
    font-size: 11px;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.spec-card-content .value {
    font-family: var(--font-heading);
    font-size: 15px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.4;
}
.spec-card:hover {
    transform: translateY(-4px);
    border-color: var(--primary);
    box-shadow: 0 15px 30px var(--primary-glow);
}

/* ==================== PRODUCT HIGHLIGHTS ==================== */
.premium-highlights-section {
    padding: 80px 0;
    background: var(--tn-surface);
}

.highlights-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.highlight-item-card {
    border-radius: 24px;
    background: var(--tn-surface);
    border: 1px solid #e6eef6;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    transition: var(--transition);
}
.highlight-item-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 30px rgba(37, 99, 235, 0.08);
    border-color: var(--secondary);
}
.h-card-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.h-card-icon svg {
    width: 28px;
    height: 28px;
    stroke-width: 2;
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Individual Icon Color Themes with Light Gradient Backgrounds */
.icon-performance {
    background: rgba(249, 115, 22, 0.08);
    color: #f97316;
}
.icon-screen {
    background: rgba(139, 92, 246, 0.08);
    color: #8b5cf6;
}
.icon-battery {
    background: rgba(16, 185, 129, 0.08);
    color: #10b981;
}
.icon-brain {
    background: rgba(236, 72, 153, 0.08);
    color: #ec4899;
}
.icon-design {
    background: rgba(59, 130, 246, 0.08);
    color: #3b82f6;
}
.icon-cooling {
    background: rgba(6, 182, 212, 0.08);
    color: #06b6d4;
}

.highlight-item-card:hover .h-card-icon {
    transform: scale(1.1) rotate(5deg);
}
.highlight-item-card:hover .h-card-icon svg {
    transform: scale(1.15);
}
.highlight-item-card:hover .icon-performance {
    background: #f97316;
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(249, 115, 22, 0.25);
}
.highlight-item-card:hover .icon-screen {
    background: #8b5cf6;
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(139, 92, 246, 0.25);
}
.highlight-item-card:hover .icon-battery {
    background: #10b981;
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
}
.highlight-item-card:hover .icon-brain {
    background: #ec4899;
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(236, 72, 153, 0.25);
}
.highlight-item-card:hover .icon-design {
    background: #3b82f6;
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.25);
}
.highlight-item-card:hover .icon-cooling {
    background: #06b6d4;
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(6, 182, 212, 0.25);
}
.highlight-item-card h3 {
    font-family: var(--font-heading);
    font-size: 18px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 8px 0;
}
.highlight-item-card p {
    font-size: 13.5px;
    color: var(--text-secondary);
    line-height: 1.6;
    margin: 0;
}

/* ==================== STORYTELLING SECTION ==================== */
.premium-storytelling-section {
    padding: 40px 0;
    display: flex;
    flex-direction: column;
    gap: 120px;
}
.story-block {
    position: relative;
}
.grid-2-columns {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 64px;
    align-items: center;
}
.story-content {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.story-tag {
    font-family: var(--font-heading);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    color: var(--primary);
}
.story-title {
    font-family: var(--font-heading);
    font-size: 40px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.15;
    letter-spacing: -1px;
    margin: 0;
}
.story-p {
    font-size: 15px;
    color: var(--text-secondary);
    line-height: 1.7;
    margin: 0;
}
.story-stats-row {
    display: flex;
    gap: 40px;
    margin-top: 16px;
}
.stat-unit {
    display: flex;
    flex-direction: column;
}
.stat-unit .num {
    font-family: var(--font-heading);
    font-size: 32px;
    font-weight: 800;
    color: var(--primary);
    line-height: 1;
}
.stat-unit .desc {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-top: 4px;
}

.glass-media-card {
    border-radius: 28px;
    background: #111f35;
    border: 1px solid rgba(255,255,255,0.05);
    padding: 24px;
    box-shadow: var(--card-glow);
    display: flex;
    align-items: center;
    justify-content: center;
}
.story-img {
    max-width: 100%;
    border-radius: 16px;
    transition: transform 0.5s ease;
}
.glass-media-card:hover .story-img {
    transform: scale(1.03);
}

.alt-direction .story-content {
    order: 2;
}
.alt-direction .story-media-box {
    order: 1;
}

/* ==================== REVIEWS SECTION ==================== */
.premium-reviews-section {
    padding: 80px 0;
    background:
        radial-gradient(circle at 18% 12%, rgba(37, 99, 235, 0.18), transparent 34%),
        linear-gradient(180deg, #13223a 0%, #0f1c31 100%);
    border-top: 1px solid rgba(96,165,250,0.16);
    color: #f8fafc;
}

.premium-reviews-section .section-title-wrap {
    margin-bottom: 42px;
}

.premium-reviews-section .accent-subtitle {
    color: #60a5fa;
    text-shadow: 0 0 18px rgba(96,165,250,0.34);
}

.premium-reviews-section .section-main-title {
    color: #f8fafc;
}

.premium-reviews-section .section-description-text {
    color: #cbd5e1;
    max-width: 720px;
}

.reviews-dashboard-grid {
    display: grid;
    grid-template-columns: 0.8fr 1.2fr 1fr;
    gap: 30px;
    margin-bottom: 48px;
}

.rating-overall-card, .rating-meters-card, .reviews-cskh-card {
    padding: 30px;
    border-radius: 24px;
    background: rgba(8, 18, 33, 0.78);
    border: 1px solid rgba(148, 163, 184, 0.18);
    box-shadow: 0 18px 45px rgba(2, 6, 23, 0.26), inset 0 1px 0 rgba(255,255,255,0.04);
}

.rating-overall-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}
.rating-overall-card .card-label {
    font-size: 11px;
    font-weight: 700;
    color: #a9bbd3;
    letter-spacing: 0.5px;
}
.overall-score-number {
    font-family: var(--font-heading);
    font-size: 64px;
    font-weight: 800;
    color: var(--primary);
    line-height: 1;
    margin: 12px 0;
}
.overall-stars {
    color: var(--accent);
    font-size: 18px;
    letter-spacing: 2px;
    margin-bottom: 12px;
}
.overall-total-count {
    font-size: 12.5px;
    font-weight: 600;
    color: #a9bbd3;
}

.rating-meters-card {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.meters-title {
    font-family: var(--font-heading);
    font-size: 15px;
    font-weight: 700;
    color: #eef5ff;
    margin-bottom: 16px;
}
.rating-meters-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.meter-bar-row {
    display: grid;
    grid-template-columns: 45px 1fr 45px;
    align-items: center;
    gap: 12px;
}
.stars-label {
    font-size: 12px;
    font-weight: 700;
    color: #cbd5e1;
}
.meter-track {
    height: 6px;
    border-radius: 10px;
    background: rgba(226, 232, 240, 0.82);
    overflow: hidden;
}
.meter-fill {
    height: 100%;
    background: var(--primary);
    border-radius: 10px;
}
.percent-label {
    font-size: 12px;
    font-weight: 700;
    color: #dbeafe;
    text-align: right;
}

.reviews-cskh-card {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.cskh-avatar {
    font-size: 32px;
    margin-bottom: 12px;
}
.reviews-cskh-card h3 {
    font-family: var(--font-heading);
    font-size: 16px;
    font-weight: 700;
    color: #f8fafc;
    margin: 0 0 8px 0;
}
.reviews-cskh-card p {
    font-size: 13.5px;
    color: #cbd5e1;
    line-height: 1.65;
    margin: 0;
}

.reviews-list-wrapper {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.review-feedback-card {
    position: relative;
    border-radius: 20px;
    background: rgba(8, 18, 33, 0.74);
    border: 1px solid rgba(148, 163, 184, 0.18);
    padding: 24px;
    transition: var(--transition);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.035);
}
.review-feedback-card:hover {
    border-color: rgba(96,165,250,0.55);
    box-shadow: 0 16px 38px rgba(37,99,235,0.18);
}
.card-top-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.user-profile-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}
.user-avatar-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}
.name-date-stack {
    display: flex;
    flex-direction: column;
}
.name-date-stack .username {
    font-size: 14px;
    font-weight: 700;
    color: #f8fafc;
}
.name-date-stack .review-timestamp {
    font-size: 11px;
    font-weight: 500;
    color: #a9bbd3;
    margin-top: 2px;
}
.review-badge-stars {
    color: var(--accent);
    letter-spacing: 1px;
}
.comment-p {
    font-size: 14px;
    line-height: 1.6;
    color: #dbe6f5;
    margin: 0;
    font-style: italic;
}

.empty-reviews-state {
    padding: 60px;
    text-align: center;
    border-radius: 24px;
    background: #0d1b2e;
    border: 1.5px dashed #cbd5e1;
    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 500px;
    margin: 0 auto;
}
.empty-icon-wrap {
    width: 60px;
    height: 60px;
    color: #94a3b8;
    margin-bottom: 16px;
}
.empty-reviews-state h4 {
    font-family: var(--font-heading);
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 8px 0;
}
.empty-reviews-state p {
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.5;
    margin: 0;
}

/* ==================== RELATED PRODUCTS ==================== */
.premium-related-products-section {
    padding: 60px 0 20px 0;
}
.related-section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 30px;
}
.related-section-header .subtitle-tag {
    font-family: var(--font-heading);
    font-size: 11px;
    font-weight: 800;
    color: var(--primary);
    letter-spacing: 2px;
    display: block;
    margin-bottom: 4px;
}
.related-section-header .main-title {
    font-family: var(--font-heading);
    font-size: 28px;
    font-weight: 800;
    color: var(--text-primary);
    letter-spacing: -0.5px;
    margin: 0;
}
.action-all-link {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--primary);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: var(--transition);
}
.action-all-link:hover {
    color: #1d4ed8;
}
.action-all-link:hover .arrow {
    transform: translateX(4px);
}
.action-all-link .arrow {
    transition: transform 0.2s ease;
}

.related-products-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
}

.premium-product-card {
    border-radius: 20px;
    background: var(--tn-surface-2);
    border: 1px solid var(--tn-border);
    padding: 16px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
}
.badge-row {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 2;
}
.tag-badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-family: var(--font-heading);
    font-size: 9px;
    font-weight: 800;
    color: white;
}

.tag-badge.badge-glow {
    background: #2563eb;
    color: #ffffff;
    border: 1px solid #1d4ed8;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22);
    text-shadow: none;
}
.product-image-box {
    aspect-ratio: 1.1;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    background: var(--tn-bg);
}
.card-main-image {
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
    transition: transform 0.4s ease;
}
.premium-product-card:hover .card-main-image {
    transform: scale(1.06);
}

.product-info-box {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.product-card-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    background: transparent;
    line-height: 1.35;
    margin: 0 0 6px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 36px;
}
.product-card-specs {
    font-size: 11px;
    color: #64748b;
    background: transparent;
    margin: 0 0 10px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.product-card-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 600;
    margin-bottom: 12px;
}
.product-card-rating .stars {
    letter-spacing: 0.5px;
    color: #f59e0b;
}
.product-card-rating .score {
    color: #0f172a;
    background: transparent;
}

.product-card-bottom-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: auto;
}
.price-side {
    display: flex;
    flex-direction: column;
}
.price-side .price-title {
    font-size: 9px;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    text-transform: uppercase;
}
.price-side .price-tag {
    font-family: var(--font-heading);
    font-size: 15px;
    font-weight: 800;
    color: var(--primary);
    background: transparent;
}
.btn-quick-view {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #cbd5e1;
    border: none;
    color: #334155;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
}
.premium-product-card:hover .btn-quick-view {
    background: #94a3b8;
    color: white;
}
.premium-product-card:hover {
    transform: translateY(-4px);
    border-color: var(--primary);
    box-shadow: var(--card-glow);
}

.premium-pagination-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    margin-top: 32px;
}
.pag-btn {
    padding: 8px 16px;
    border-radius: 10px;
    border: 1px solid #94a3b8;
    background: var(--tn-surface);
    cursor: pointer;
    font-size: 12.5px;
    font-weight: 800;
    color: #0f172a;
    transition: var(--transition);
}
.pag-btn:hover:not(:disabled) {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
}
.pag-btn:disabled {
    opacity: 1;
    background: #e2e8f0;
    color: #64748b;
    border-color: #cbd5e1;
    cursor: not-allowed;
}
.pag-numbers-box {
    display: flex;
    gap: 6px;
}
.pag-number-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: var(--tn-surface);
    cursor: pointer;
    font-family: var(--font-heading);
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    transition: var(--transition);
}
.pag-number-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
    box-shadow: 0 4px 10px var(--primary-glow);
}

/* ==================== COMPARE MODAL ==================== */
.compare-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(8px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.compare-modal-card {
    position: relative;
    width: 100%;
    max-width: 1100px;
    max-height: 85vh;
    background: #ffffff;
    border-radius: 28px;
    box-shadow: 0 30px 60px rgba(0,0,0,0.12);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.compare-modal-header {
    padding: 24px 30px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.header-titles h3 {
    font-family: var(--font-heading);
    font-size: 20px;
    font-weight: 800;
    margin: 0 0 4px 0;
}
.header-titles p {
    font-size: 12px;
    color: var(--text-secondary);
    margin: 0;
}
.close-modal-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    font-size: 16px;
    cursor: pointer;
    color: #475569;
    transition: var(--transition);
}
.close-modal-btn:hover {
    background: #dc2626;
    color: white;
}

.compare-modal-body {
    display: grid;
    grid-template-columns: 320px 1fr;
    flex-grow: 1;
    overflow: hidden;
}

.compare-products-picker-panel {
    padding: 24px 30px;
    border-right: 1px solid #e2e8f0;
    overflow-y: auto;
}
.panel-section-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    margin-bottom: 16px;
}
.picker-list-wrapper {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.picker-item-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: var(--transition);
}
.picker-item-row:hover {
    background: #f1f5f9;
}
.custom-tech-checkbox {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    cursor: pointer;
}
.p-thumb-box {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.p-thumb-box img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.p-info-side {
    display: flex;
    flex-direction: column;
}
.p-info-side .p-name {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.3;
}
.p-info-side .p-price {
    font-size: 11px;
    font-weight: 600;
    color: var(--primary);
    margin-top: 2px;
}

.compare-results-table-panel {
    padding: 24px 30px;
    overflow-y: auto;
    background: #f8fafc;
}

.empty-compare-selection-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    height: 100%;
    color: var(--text-secondary);
}
.empty-compare-selection-state .icon {
    font-size: 48px;
    margin-bottom: 12px;
}
.empty-compare-selection-state h4 {
    font-family: var(--font-heading);
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 6px 0;
    color: var(--text-primary);
}
.empty-compare-selection-state p {
    font-size: 12.5px;
    line-height: 1.5;
    margin: 0;
}

.comparison-tech-table {
    width: 100%;
    border-collapse: collapse;
}
.comparison-tech-table th, .comparison-tech-table td {
    padding: 16px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}
.comparison-tech-table th {
    background: #ffffff;
    font-family: var(--font-heading);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    vertical-align: bottom;
}
.attribute-col {
    width: 180px;
    font-size: 11px;
    font-weight: 700;
    color: var(--text-secondary);
}
.product-showcase-col {
    min-width: 200px;
}
.product-showcase-col.active-current {
    background: rgba(37, 99, 235, 0.02);
}
.col-product-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
.col-product-card img {
    height: 80px;
    object-fit: contain;
    margin-bottom: 12px;
}
.col-product-card .label-now {
    padding: 3px 6px;
    border-radius: 4px;
    background: var(--primary);
    color: white;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.col-product-card .name {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.3;
    height: 32px;
    overflow: hidden;
}
.col-product-card .price-val {
    font-family: var(--font-heading);
    font-size: 13px;
    font-weight: 800;
    color: var(--primary);
    margin-top: 4px;
}

.cell-value-text {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.4;
}
.cell-no-value {
    color: #94a3b8;
}

/* ==================== LOADING SCREEN ==================== */
.immersive-loader-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: #0F172A;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
.loading-spinner {
    width: 50px;
    height: 50px;
    border: 3px solid rgba(255,255,255,0.1);
    border-radius: 50%;
    border-top-color: var(--secondary);
    animation: loader-spin 1s infinite linear;
    display: inline-block;
    margin-bottom: 24px;
}
@keyframes loader-spin {
    to { transform: rotate(360deg); }
}
.loader-content-wrap {
    text-align: center;
    max-width: 320px;
}
.loader-content-wrap h3 {
    font-family: var(--font-heading);
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px 0;
}
.loader-content-wrap p {
    font-size: 13px;
    color: #94a3b8;
    line-height: 1.5;
    margin: 0;
}

/* ==================== RESPONSIVE LAYOUTS ==================== */
@media (max-width: 1024px) {
    .detail-hero-grid {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    .specs-panel-topbar {
        align-items: flex-start;
        flex-direction: column;
    }
    .specs-mode-tabs {
        width: 100%;
    }
    .specs-mode-btn {
        flex: 1;
    }
    .inline-compare-area {
        grid-template-columns: 1fr;
    }
    .inline-compare-filter {
        border-right: none;
        border-bottom: 1px solid #dbe6f3;
    }
    .machine-info-matrix {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .specs-modern-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .highlights-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .related-products-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .compare-modal-card {
        max-height: 90vh;
    }
}

/* CYBER NEWSLETTER (light variant for product page) */
.cyber-newsletter-section {
    background: var(--light-bg, #f8fafc);
    padding: 56px 0 72px;
}
.newsletter-neon-box {
    position: relative;
    border-radius: 24px;
    padding: 40px;
    background: var(--tn-surface);
    border: 1px solid rgba(15, 23, 42, 0.06);
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}
.newsletter-bg-glow { display: none; }
.newsletter-layout {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 28px;
    align-items: center;
}
.newsletter-headline h2 {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 8px;
    color: #0f172a;
}
.newsletter-headline p {
    font-size: 14px;
    line-height: 1.6;
    color: #616e7a;
    margin: 0;
}
.newsletter-interactive-form { display: flex; justify-content: flex-end; }
.input-glow-group {
    display: flex;
    width: 100%;
    max-width: 520px;
    background: #f3f6f9;
    border: 1px solid #e6eef6;
    border-radius: 12px;
    padding: 6px;
}
.input-glow-group input {
    flex-grow: 1;
    background: transparent;
    border: none;
    outline: none;
    color: #0f172a;
    font-size: 14px;
    padding: 0 12px;
}
.input-glow-group input::placeholder { color: #94a3b8; }

@media (max-width: 992px) {
    .newsletter-layout { grid-template-columns: 1fr; text-align: center; }
    .newsletter-interactive-form { justify-content: center; }
}

@media (max-width: 768px) {
    .premium-product-title {
        font-size: 26px;
    }
    .premium-selectors-wrapper {
        grid-template-columns: 1fr;
    }
    .premium-option-group {
        grid-template-columns: minmax(70px, 0.65fr) minmax(0, 1.35fr);
    }
    .grid-2-columns {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    .alt-direction .story-content {
        order: 1;
    }
    .alt-direction .story-media-box {
        order: 2;
    }
    .reviews-dashboard-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .compare-modal-body {
        grid-template-columns: 1fr;
    }
    .compare-products-picker-panel {
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
        max-height: 200px;
    }
}

@media (max-width: 480px) {
    .premium-option-group {
        grid-template-columns: 1fr;
        align-items: flex-start;
    }
    .premium-pill-selectors,
    .premium-color-selectors {
        justify-content: flex-start;
    }
    .machine-info-matrix {
        grid-template-columns: 1fr;
    }
    .machine-info-cell {
        min-height: 82px;
    }
    .specs-modern-grid {
        grid-template-columns: 1fr;
    }
    .highlights-grid {
        grid-template-columns: 1fr;
    }
    .related-products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .actions-grid {
        grid-template-columns: 1fr;
    }
    .btn-buy-now {
        order: 1;
    }
    .btn-installment {
        order: 2;
    }
    .story-title {
        font-size: 28px;
    }
}

/* ==================== DETAILED SPECS ACCORDION ==================== */
.detailed-specs-accordion {
    grid-column: 1 / -1;
    margin-top: 40px;
    border-radius: 20px;
    background: #111f35;
    border: 1px solid rgba(255,255,255,0.07);
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
    transition: var(--transition);
}
.detailed-specs-accordion:hover {
    border-color: var(--primary);
    box-shadow: 0 15px 35px var(--primary-glow);
}
.toggle-accordion-btn {
    width: 100%;
    padding: 20px 28px;
    background: #0d1b2e;
    border: none;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    text-align: left;
    transition: var(--transition);
}
.toggle-accordion-btn:hover {
    background: #111f35;
}
.toggle-accordion-btn .btn-text {
    font-family: var(--font-heading);
    font-size: 15px;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 12px;
}
.toggle-accordion-btn .accordion-icon {
    font-size: 12px;
    color: var(--primary);
    transition: var(--transition);
}
.glow-accent-line {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
    opacity: 0.8;
}

.full-specs-table-wrapper {
    padding: 24px 28px;
    background: #111f35;
}

.specs-table-cyber {
    width: 100%;
    border-collapse: collapse;
}
.specs-table-cyber th, .specs-table-cyber td {
    padding: 14px 18px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.07);
}
.specs-table-cyber th {
    background: #0d1b2e;
    font-family: var(--font-heading);
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    border-radius: 8px;
}
.specs-table-cyber tr:hover {
    background: rgba(37, 99, 235, 0.01);
}
.specs-table-cyber tr:last-child td {
    border-bottom: none;
}
.specs-table-cyber .lbl-col {
    width: 280px;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-secondary);
}
.specs-table-cyber .val-col {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.5;
}

/* Accordion expand transitions */
.expand-specs-enter-active,
.expand-specs-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    max-height: 1000px;
}
.expand-specs-enter-from,
.expand-specs-leave-to {
    opacity: 0;
    max-height: 0px;
    padding-top: 0px;
    padding-bottom: 0px;
    overflow: hidden;
}

/* ==================== Quick UI Improvements ==================== */
/* Make gallery image bigger and breathe a bit */
.main-image-viewport {
    padding: 20px;
}
.main-showcase-image {
    max-width: 100%;
    max-height: 100%;
}

/* Responsive product title */
.premium-product-title {
    font-size: clamp(20px, 3.6vw, 32px);
}


/* Slightly smaller, interactive thumbnails */
.thumb-card {
    width: 64px;
    height: 64px;
    border-radius: 12px;
}
.thumb-card img {
    transition: transform 0.25s ease;
}
.thumb-card:hover img { transform: scale(1.06); }


/* ===== IMAGE SLIDE TRANSITION ===== */
.slide-left-enter-active,
.slide-left-leave-active {
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.slide-left-enter-from {
    opacity: 0;
    transform: translateX(40px);
}
.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-40px);
}

/* ===== RELATED COMBOS BOX ===== */
.related-combos-box {
    margin-top: 24px;
    background: #edf2f7;
    border: 1px dashed #2563eb;
    border-radius: 16px;
    padding: 16px;
}

.box-title {
    font-size: 15px;
    font-weight: 800;
    color: #0f2b5b;
    margin: 0 0 12px 0;
}

.related-combo-card {
    background: white;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid var(--tn-border);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
    gap: 16px;
}

.related-combo-card:not(:last-child) {
    margin-bottom: 12px;
}

.clickable-product {
    cursor: pointer;
    transition: all 0.2s ease;
}

.clickable-product:hover {
    color: #2563eb;
    text-decoration: underline;
}

.clickable-combo {
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.clickable-combo:hover {
    color: #2563eb;
    text-decoration: underline;
}

.clickable-combo .info-icon {
    font-size: 12px;
    opacity: 0.6;
    transition: opacity 0.2s ease;
}

.clickable-combo:hover .info-icon {
    opacity: 1;
}

.combo-action-btns {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn-view-combo {
    background: var(--tn-surface-2);
    color: #475569;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-view-combo:hover {
    background: #e2e8f0;
    color: #0f172a;
    border-color: #94a3b8;
}

.combo-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: flex-start;
    text-align: left;
}

.combo-left .badge-discount {
    background: linear-gradient(135deg, #ef4444, #f97316);
    color: white;
    font-size: 10px;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 30px;
    box-shadow: 0 3px 8px rgba(239, 68, 68, 0.15);
}

.combo-left h4 {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    line-height: 1.4;
}

.combo-products-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}

.combo-products-inline .plus {
    color: #2563eb;
    font-weight: 800;
}

.combo-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.combo-right .price-box {
    display: flex;
    flex-direction: column;
    text-align: right;
}

.combo-right .price-box .label {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
}

.combo-right .price-box .price {
    font-size: 16px;
    font-weight: 800;
    color: #2563eb;
}

.btn-buy-combo {
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s ease;
}

.btn-buy-combo:hover {
    background: #1d4ed8;
}

/* ===== VARIANT OFFERS BOX (VIP SPEC OFFER) ===== */
.variant-offers-box {
    margin-top: 24px;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #22c55e;
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 10px 25px rgba(34, 197, 94, 0.1);
}

.offer-header-vip {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}

.badge-vip {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    font-size: 10px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 30px;
}

.offer-header-vip h3 {
    font-size: 14px;
    font-weight: 800;
    color: #14532d;
    margin: 0;
}

.variant-offer-card {
    background: white;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.offer-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
    align-items: flex-start;
    text-align: left;
}

.offer-title {
    font-size: 14px;
    font-weight: 700;
    color: #166534;
    margin: 0;
}

.offer-combo-name {
    font-size: 12px;
    color: #475569;
    margin: 0;
}

.offer-products-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 4px;
}

.offer-p-item {
    background: #f0fdf4;
    border: 1px solid #dcfce7;
    color: #15803d;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
}

.offer-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}

.price-label-free {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.price-label-free .old-price {
    font-size: 10px;
    color: #94a3b8;
    text-decoration: line-through;
}

.price-label-free .free-badge-text {
    font-size: 16px;
    font-weight: 800;
    color: #22c55e;
}

.btn-claim-offer {
    background: #22c55e;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(34, 197, 94, 0.2);
    transition: background 0.2s ease;
}

.btn-claim-offer:hover {
    background: #16a34a;
}

/* ===== UPSELL TEASER BOX ===== */
.upsell-teaser-box {
    margin-top: 24px;
    background: #eff6ff;
    border: 1.5px dashed #3b82f6;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.teaser-icon {
    font-size: 20px;
}

.teaser-content {
    flex: 1;
    text-align: left;
}

.teaser-text {
    font-size: 12.5px;
    color: #1e3a8a;
    margin: 0;
    line-height: 1.5;
}

.highlight-variant {
    color: #2563eb;
    font-weight: 800;
    cursor: pointer;
    text-decoration: underline;
    transition: color 0.2s;
}

.highlight-variant:hover {
    color: #1d4ed8;
}

.free-text-badge {
    background: #22c55e;
    color: white;
    font-size: 9px;
    font-weight: 800;
    padding: 1px 6px;
    border-radius: 4px;
    display: inline-block;
    margin: 0 2px;
}


/* Tighter grid gap and responsive stack */
.detail-hero-grid { gap: 36px; }
@media (max-width: 1024px) {
    .detail-hero-grid { grid-template-columns: 1fr; gap: 24px; }
    .gallery-column { position: static; top: auto; max-width: none; }
    .main-image-viewport { aspect-ratio: 16/10; }

}

/* ==================== PREMIUM 3D VIEW PORT STYLE ==================== */
.main-image-viewport {
    position: relative;
    transform-style: preserve-3d;
    perspective: 1200px;
    transition: transform 0.15s ease-out, box-shadow 0.3s ease, border-color 0.3s ease;
    border: 2px solid transparent;
}

.main-image-viewport.is-3d-active {
    border-color: rgba(37, 99, 235, 0.4);
    box-shadow: 0 15px 35px rgba(37, 99, 235, 0.15), inset 0 0 20px rgba(37, 99, 235, 0.05);
}

/* 3D Showcase Image transitions */
.main-showcase-image {
    transform-style: preserve-3d;
    backface-visibility: hidden;
    transition: transform 1.4s cubic-bezier(0.1, 0.8, 0.2, 1);
}

/* 3D Indicator Badge */
.badge-3d-indicator {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 20px;
    font-family: var(--font-heading);
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    pointer-events: none;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.badge-3d-indicator.is-active {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.9) 0%, rgba(29, 78, 216, 0.9) 100%);
    border-color: transparent;
    color: white;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
    transform: translateY(-2px);
}

.badge-3d-indicator .pulse-icon {
    font-size: 13px;
    animation: pulseGlow 2s infinite ease-in-out;
}

@keyframes pulseGlow {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.2); opacity: 1; }
    100% { transform: scale(1); opacity: 0.8; }
}

/* Custom styling for SVGs replacing Emojis */
.g-icon svg {
    width: 22px;
    height: 22px;
    fill: none;
    stroke-width: 2.2;
    stroke-linecap: round;
    stroke-linejoin: round;
    display: block;
}

.benefit-icon svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke-width: 2.2;
    stroke-linecap: round;
    stroke-linejoin: round;
    display: inline-block;
    vertical-align: middle;
}

.btn-toggle-3d {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 50;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 30px;
    font-family: var(--font-heading);
    font-weight: 600;
    font-size: 13px;
    color: #1e293b;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.btn-toggle-3d:hover {
    background: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}
.btn-toggle-3d.active {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.9) 0%, rgba(29, 78, 216, 0.9) 100%);
    color: white;
    border-color: transparent;
    box-shadow: 0 8px 20px rgba(37,99,235,0.3);
}

/* 3D Modal CSS */
.premium-3d-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(10, 15, 30, 0.9);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.premium-3d-modal-content {
    background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    width: 90vw;
    max-width: 900px;
    height: 80vh;
    max-height: 700px;
    position: relative;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}
.modal-close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255,255,255,0.1);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
}
.modal-close-btn:hover {
    background: rgba(255,255,255,0.2);
    transform: scale(1.05);
}
.modal-3d-header {
    text-align: center;
    padding: 24px;
    z-index: 2;
}
.modal-3d-title {
    color: white;
    font-size: 24px;
    margin: 0 0 8px 0;
    font-family: var(--font-heading);
}
.modal-3d-hint {
    color: #94a3b8;
    font-size: 14px;
    margin: 0;
}
.modal-3d-viewport {
    flex-grow: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    position: relative;
    perspective: 1200px;
}
.modal-3d-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    user-select: none;
    -webkit-user-drag: none;
    transform-style: preserve-3d;
}
</style>
