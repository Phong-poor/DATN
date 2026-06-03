<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'
import { getToken } from '@/services/auth'
import { storageUrl } from '@/services/urls'
import ComboSelectionModal from './ComboSelectionModal.vue'



const route = useRoute()
const isLoading = ref(true)
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
const getVariantAttributes = (variant) =>
    variant?.thuoc_tinh || variant?.attributes ||
    (variant?.thuoc_tinh_json
        ? (typeof variant.thuoc_tinh_json === 'string'
            ? JSON.parse(variant.thuoc_tinh_json)
            : variant.thuoc_tinh_json)
        : [])

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


const handleSelectOption = (groupName, value) => {
    selectedOptions.value = { ...selectedOptions.value, [groupName]: value }
    const matched = findMatchingVariant()
    if (matched) {
        selectedVariant.value = matched
        if (matched.hinhanh) {
            selectedImage.value = getImageUrl(matched.hinhanh)
        }
        router.replace({ query: { ...route.query, variant: matched.id_bienthe } })
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
    if (!path) return 'https://via.placeholder.com/600'
    if (path.startsWith('http') || path.startsWith('data:image')) return path
    return storageUrl(path)
}

const allImages = computed(() => {
    if (!product.value) return []
    let images = []
    if (product.value.hinhanh) images.push(getImageUrl(product.value.hinhanh))
    const listHinhAnh = product.value.hinh_anhs || product.value.hinhAnhs || []
    listHinhAnh.forEach(img => images.push(getImageUrl(img.duongdan)))
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

// ===================== FETCH SẢN PHẨM =====================
const loadCache = (productId) => {
    try {
        const cached = localStorage.getItem(`nextgen_product_detail_cache_${productId}`)
        if (cached) {
            const parsed = JSON.parse(cached)
            if (parsed.product) product.value = parsed.product
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
        localStorage.setItem(`nextgen_product_detail_cache_${productId}`, JSON.stringify({
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

const fetchProductDetail = async () => {
    try {
        const productId = route.params.id || 1
        const response = await api.get(`/sanpham/${productId}`, { skipGlobalLoader: true })
        const data = response.data
        const variants = data.bien_thes || data.bienThes || []
        product.value = { ...data, bienThes: variants }

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
            await fetchRelatedProducts(data.id_danhmuc, data.id_sanpham)
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

const loadPageData = async () => {
    const productId = route.params.id || 1
    // Tải cache ngay lập tức để hiển thị tức thì
    const hasCache = loadCache(productId)
    if (hasCache) {
        isLoading.value = false
    } else {
        isLoading.value = true
    }

    try {
        await Promise.all([
            fetchProductDetail(),
            fetchRecentlyViewed(),
            fetchReviews(),
            fetchProductCombos(productId)
        ]);
        saveCache(productId)
        isLoading.value = false
    } catch (e) {
        console.error('Lỗi khi tải dữ liệu chi tiết sản phẩm:', e)
        isLoading.value = false
    }
}

onMounted(() => {
    window.scrollTo(0, 0)
    loadPageData()
    startAutoSlide()
})

onUnmounted(() => {
    stopAutoSlide()
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
                    img: bt.hinhanh ? getImageUrl(bt.hinhanh) : getImageUrl(p.hinhanh),
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
                        img: bt.hinhanh ? getImageUrl(bt.hinhanh) : getImageUrl(p.hinhanh),
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
        router.replace({ query: { ...route.query, variant: matched.id_bienthe } })
        soLuongMua.value = 1
        hienThiThongBao('success', `✨ Đã chuyển cấu hình: ${matched.ten_bienthe}`)
    }
}

</script>

<template>

    <transition name="slide-down">
        <div v-if="thongBao.show" :class="['toast', thongBao.type]">
            {{ thongBao.message }}
        </div>
    </transition>

    <!-- TOP GLOW DECORATOR -->
    <div class="tech-glow-top"></div>

    <div class="page" v-if="!isLoading && product.tenSP">
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
                        <div class="main-image-viewport">
                            <div class="neon-glow-backdrop"></div>
                            
                            <!-- Badges Overlay -->
                            <div class="gallery-badges">
                                <span class="badge badge-glow" v-if="product.thuong_hieu">{{ product.thuong_hieu.ten_thuonghieu }}</span>
                                <span class="badge badge-tech">ORIGINAL BRAND</span>
                            </div>

                            <img :src="selectedImage" :alt="product.tenSP" class="main-showcase-image" />
                            
                            <div v-if="selectedVariant && selectedVariant.soluong === 0" class="premium-out-of-stock-badge">
                                HẾT HÀNG
                            </div>

                            <!-- Navigation Arrows -->
                            <button @click="selectedImage = allImages[(allImages.indexOf(selectedImage) - 1 + allImages.length) % allImages.length]" class="gallery-nav-arrow arrow-left" aria-label="Ảnh trước">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </button>
                            <button @click="selectedImage = allImages[(allImages.indexOf(selectedImage) + 1) % allImages.length]" class="gallery-nav-arrow arrow-right" aria-label="Ảnh sau">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>


                            <!-- Slide Indicator Dots -->
                            <div class="slide-dots">
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

                        <!-- Trust signals block -->
                        <div class="premium-trust-signals-grid">
                            <div class="signal-item">
                                <span class="signal-icon">🛡️</span>
                                <span class="signal-text">Chính hãng 100%</span>
                            </div>
                            <div class="signal-item">
                                <span class="signal-icon">⚡</span>
                                <span class="signal-text">Giao siêu tốc 2h</span>
                            </div>
                            <div class="signal-item">
                                <span class="signal-icon">🛠️</span>
                                <span class="signal-text">Bảo hành 24 tháng</span>
                            </div>
                            <div class="signal-item">
                                <span class="signal-icon">💎</span>
                                <span class="signal-text">Hỗ trợ trọn đời</span>
                            </div>
                        </div>

                        <!-- Variant Selectors Option Groups -->
                        <div class="premium-selectors-wrapper" v-if="product.bienThes && product.bienThes.length > 0">
                            <div class="premium-option-group" v-for="group in variantGroups" :key="group.name">
                                <div class="option-header-row">
                                    <span class="option-label-title">{{ group.name }}</span>
                                    <span class="option-selected-value">{{ selectedOptions[group.name] }}</span>
                                </div>

                                <!-- Color Dot Custom Buttons -->
                                <div v-if="group.name === 'Màu sắc'" class="premium-color-selectors">
                                    <button v-for="item in group.values" :key="item.giatri" 
                                            :class="['color-selector-btn', { active: selectedOptions[group.name] === item.giatri }]"
                                            @click="handleSelectOptionWithReset(group.name, item.giatri)"
                                            :title="item.giatri">
                                        <span class="color-core" :style="{ backgroundColor: item.ma_mau || '#ccc' }"></span>
                                        <span class="color-ring-glow"></span>
                                    </button>
                                </div>

                                <!-- Custom Pill Buttons for RAM / SSD / Specs -->
                                <div v-else class="premium-pill-selectors">
                                    <button v-for="item in group.values" :key="item.giatri" 
                                            :class="['pill-selector-btn', { active: selectedOptions[group.name] === item.giatri }]"
                                            @click="handleSelectOptionWithReset(group.name, item.giatri)">
                                        <span class="pill-text">{{ item.giatri }}</span>
                                        <span class="active-indicator"></span>
                                    </button>
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
                                    <span class="icon">{{ dangThemYeuThich ? '⏳' : '❤️' }}</span>
                                    <span>{{ dangThemYeuThich ? 'Đang lưu...' : 'Yêu thích' }}</span>
                                </button>
                                
                                <button class="shortcut-action-btn compare-toggle" @click="openCompareModal" title="So sánh tính năng">
                                    <span class="icon">🔁</span>
                                    <span>So sánh specs</span>
                                </button>
                            </div>
                        </div>

<!-- BANNER ƯU ĐÃI VIP KÈM CẤU HÌNH BIẾN THỂ -->
                        <div v-if="selectedVariantOffers.length > 0" class="variant-offers-box">
                            <div class="offer-header-vip">
                                <span class="badge-vip">🎁 ĐẶC QUYỀN VIP</span>
                                <h3>Quà Tặng Độc Quyền Cho Phiên Bản Này!</h3>
                            </div>
                            <div v-for="offer in selectedVariantOffers" :key="offer.id_combo" class="variant-offer-card">
                                <div class="offer-left">
                                    <h4 class="offer-title">{{ offer.mota_uudai || 'Món Quà Tri Ân Đặc Biệt' }}</h4>
                                    <p class="offer-combo-name">⚡ Combo: <b>{{ offer.ten_combo }}</b></p>
                                    <div class="offer-products-list">
                                        <span v-for="p in offer.products" :key="p.id_sanpham" class="offer-p-item">
                                            🎁 {{ p.tenSP }}
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
                            <span class="teaser-icon">💡</span>
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
                            <h3 class="box-title">🎁 Deal Siêu Hời - Mua Theo Combo</h3>
                            <div v-for="combo in combos" :key="combo.id_combo" class="related-combo-card">
                                <div class="combo-left">
                                    <span class="badge-discount">🔥 Tiết kiệm hơn</span>
                                    <h4 class="clickable-combo" @click="openCombo(combo)" title="Xem chi tiết & cấu hình combo">
                                        {{ combo.ten_combo }} <span class="info-icon">ℹ️</span>
                                    </h4>
                                    <div class="combo-products-inline">
                                        <span v-for="(p, i) in combo.products" :key="p.id_sanpham" class="p-item">
                                            🔹 <span class="clickable-product" @click="router.push('/products/' + p.id_sanpham)" title="Xem chi tiết sản phẩm">{{ p.tenSP }}</span><span v-if="i < combo.products.length - 1" class="plus"> + </span>
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

                    <div v-if="specsPanelMode === 'info'" class="machine-info-matrix">
                        <div
                            v-for="(spec, idx) in machineInfoGridRows"
                            :key="`${spec.group}-${spec.label}-${idx}`"
                            :class="['machine-info-cell', { 'is-placeholder': spec.label === 'Đang cập nhật' }]">
                            <span class="machine-spec-group">{{ spec.group }}</span>
                            <span class="machine-spec-name">{{ spec.label }}</span>
                            <strong class="machine-spec-value">{{ spec.value }}</strong>
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
                            <div class="h-card-icon">🚀</div>
                            <h3>Hiệu năng bứt phá mọi giới hạn</h3>
                            <p>Tăng tốc tối đa các tác vụ nặng như render video 4K, compile source code cực nhanh nhờ tích hợp AI thông minh thế hệ mới nhất.</p>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon">👁️</div>
                            <h3>Màn hình Retina sắc nét đỉnh cao</h3>
                            <p>Không gian màu 100% DCI-P3 chuẩn xác, tần số quét cao siêu mượt cho trải nghiệm thiết kế đồ họa đỉnh cao chân thực.</p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon">🔋</div>
                            <h3>Năng lượng bền bỉ suốt cả ngày</h3>
                            <p>Tối ưu hóa năng lượng siêu hiệu quả cùng chế độ sạc nhanh Type-C thông minh, giúp bạn tự tin làm việc di động không lo hết pin.</p>
                        </div>
                    </div>

                    <!-- Feature 4 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon">🧠</div>
                            <h3>Trí tuệ nhân tạo AI Ready thế hệ mới</h3>
                            <p>Vi xử lý tích hợp bộ gia tốc NPU riêng biệt, hỗ trợ đắc lực cho các thuật toán học máy và trợ lý ảo làm việc tối ưu nhất.</p>
                        </div>
                    </div>

                    <!-- Feature 5 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon">💎</div>
                            <h3>Thiết kế nhôm CNC cấp tàu vũ trụ</h3>
                            <p>Chế tác tinh xảo, đường cắt kim cương chuẩn xác, mỏng nhẹ thời thượng nhưng vô cùng bền bỉ đạt chuẩn quân đội.</p>
                        </div>
                    </div>

                    <!-- Feature 6 -->
                    <div class="highlight-item-card">
                        <div class="h-card-inner">
                            <div class="h-card-icon">❄️</div>
                            <h3>Hệ thống tản nhiệt buồng hơi vượt trội</h3>
                            <p>Các cánh quạt siêu mỏng cùng buồng hơi kép giữ cho nhiệt độ luôn mát mẻ ngay cả khi chịu tải nặng kéo dài nhiều giờ liền.</p>
                        </div>
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
                        <span class="story-tag">NEXT GEN INTELLIGENCE</span>
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
                        <div class="cskh-avatar">🤝</div>
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
                                <h3>📊 So Sánh Hiệu Năng & Chi Tiết Phần Cứng</h3>
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
                                    <span class="icon">📊</span>
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
    <div class="immersive-loader-screen" v-else>
        <div class="loader-ripple-glow"></div>
        <div class="loader-content-wrap">
            <span class="loading-spinner"></span>
            <h3>Đang giải mã dữ liệu sản phẩm...</h3>
            <p>Vui lòng đợi trong khi chúng tôi chuẩn bị giao diện trải nghiệm sản phẩm cao cấp.</p>
        </div>
    </div>

</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap');

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
    
    background-color: #ffffff;
    color: var(--text-primary);
    font-family: var(--font-body);
    overflow-x: hidden;
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
    grid-template-columns: 1.1fr 0.9fr;
    gap: 48px;
    align-items: start;
}

/* ==================== GALLERY COLUMN ==================== */
.gallery-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.main-image-viewport {
    position: relative;
    aspect-ratio: 4/3;
    border-radius: 28px;
    background: #ffffff;
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
    background: #ffffff;
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
    background: #ffffff;
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
    gap: 8px;
    margin-bottom: 16px;
}
.pill-spec-badge {
    padding: 6px 12px;
    border-radius: 30px;
    background: rgba(37, 99, 235, 0.06);
    border: 1px solid rgba(37, 99, 235, 0.12);
    color: var(--primary);
    font-size: 11px;
    font-weight: 700;
    font-family: var(--font-heading);
    letter-spacing: 0.5px;
}

.brand-subtitle {
    font-family: var(--font-heading);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 2px;
    color: var(--secondary);
    margin-bottom: 8px;
}

.premium-product-title {
    font-family: var(--font-heading);
    font-size: 32px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.25;
    margin: 0 0 16px 0;
    letter-spacing: -0.5px;
}

.premium-rating-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary);
    cursor: pointer;
    margin-bottom: 24px;
    width: fit-content;
    padding: 4px 12px;
    background: #ffffff;
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
    padding: 24px;
    border-radius: 24px;
    background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.premium-price-container::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 80%;
    height: 200%;
    background: radial-gradient(circle, rgba(6, 182, 212, 0.12) 0%, transparent 60%);
    pointer-events: none;
}
.price-label {
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: block;
    margin-bottom: 4px;
}
.price-value-glow {
    font-family: var(--font-heading);
    font-size: 38px;
    font-weight: 800;
    color: white;
    line-height: 1;
    margin-bottom: 12px;
    background: linear-gradient(to right, #ffffff 30%, #38bdf8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.price-badges-row {
    display: flex;
    gap: 12px;
}
.premium-badge-check {
    font-size: 12px;
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
    background: #ffffff;
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
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 24px;
}
.premium-option-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.option-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-weight: 800;
}
.option-label-title {
    color: var(--text-primary);
}
.option-selected-value {
    color: var(--primary);
    font-weight: 800;
}

.premium-color-selectors {
    display: flex;
    gap: 12px;
}
.color-selector-btn {
    width: 38px;
    height: 38px;
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
    width: 24px;
    height: 24px;
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
/* Hide quick-view buttons in related/recent lists since whole card is clickable */
.btn-quick-view {
    display: none !important;
}
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
    gap: 10px;
}
.pill-selector-btn {
    padding: 12px 20px;
    border-radius: 14px;
    background: #ffffff;
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
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
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
    box-shadow: 0 4px 15px var(--primary-glow);
}
.pill-selector-btn.active .pill-text {
    color: white;
}
.pill-selector-btn.active .active-indicator {
    transform: scaleX(1);
}

.updating-text {
    font-size: 12px;
    color: var(--text-secondary);
}

/* ==================== STOCK & CTAS ==================== */
.premium-stock-banner {
    margin-bottom: 24px;
}
.stock-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 14px;
    font-size: 12.5px;
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
    width: 8px;
    height: 8px;
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
    gap: 16px;
}

.premium-qty-stepper {
    display: flex;
    align-items: center;
    border: 1.5px solid #cbd5e1;
    border-radius: 14px;
    overflow: hidden;
    width: fit-content;
    background: #ffffff;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
}
.stepper-btn {
    width: 44px;
    height: 44px;
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
    min-width: 48px;
    text-align: center;
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
}

.actions-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 16px;
}

.btn-buy-now {
    height: 54px;
    border-radius: 16px;
    border: none;
    background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
    color: white;
    font-family: var(--font-heading);
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 0.5px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 25px var(--primary-glow);
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}
.btn-buy-now:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 15px 30px rgba(37, 99, 235, 0.35);
}
.btn-buy-now:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    box-shadow: none;
}
.btn-content-text {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-installment {
    height: 54px;
    border-radius: 16px;
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
    font-size: 9px;
    font-weight: 800;
    color: var(--primary);
    letter-spacing: 0.5px;
}
.btn-installment .main-text {
    font-family: var(--font-heading);
    font-size: 12px;
    font-weight: 800;
    color: var(--text-primary);
}
.btn-installment:hover {
    background: rgba(37, 99, 235, 0.04);
    transform: translateY(-2px);
}

.floating-shortcuts-row {
    display: flex;
    gap: 16px;
}
.shortcut-action-btn {
    flex: 1;
    height: 44px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.07);
    background: #111f35;
    font-size: 13px;
    font-weight: 800;
    color: #e2e8f0;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: var(--transition);
}
.shortcut-action-btn:hover {
    border-color: var(--primary);
    color: #ffffff;
    background: #1d4ed8;
}

/* ==================== SPECIFICATIONS SECTION ==================== */
.premium-specs-section {
    padding: 80px 0;
    background: #ffffff;
    border-top: 1px solid #e6eef6;
    border-bottom: 1px solid #e6eef6;
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
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 2px;
    color: var(--primary);
    display: block;
    margin-bottom: 8px;
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
    background: #ffffff;
    border: 1px solid #dbe6f3;
    border-radius: 18px;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.specs-panel-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 16px 22px;
    background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
    border-bottom: 1px solid #dbe6f3;
}

.specs-panel-topbar .section-main-title {
    font-size: 26px;
    margin-bottom: 0;
}

.specs-mode-tabs {
    display: flex;
    gap: 10px;
    padding: 6px;
    border-radius: 14px;
    background: #e8eef7;
    flex-shrink: 0;
}

.specs-mode-btn {
    border: none;
    border-radius: 10px;
    padding: 11px 18px;
    background: transparent;
    color: #334155;
    font-family: var(--font-heading);
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: var(--transition);
}

.specs-mode-btn:hover {
    color: var(--primary);
    background: #ffffff;
}

.specs-mode-btn.active {
    background: var(--primary);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
}

.machine-info-table-wrap,
.inline-compare-table-wrap {
    overflow-x: auto;
}

.machine-info-matrix {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 1px;
    background: #dbe6f3;
}

.machine-info-cell {
    min-height: 92px;
    padding: 12px 14px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 6px;
}

.machine-info-cell:nth-child(10n + 1),
.machine-info-cell:nth-child(10n + 2),
.machine-info-cell:nth-child(10n + 3),
.machine-info-cell:nth-child(10n + 4),
.machine-info-cell:nth-child(10n + 5) {
    background: #f8fafc;
}

.machine-info-cell.is-placeholder {
    background: #f1f5f9;
}

.machine-info-cell.is-placeholder .machine-spec-group,
.machine-info-cell.is-placeholder .machine-spec-name,
.machine-info-cell.is-placeholder .machine-spec-value {
    color: #64748b;
}

.machine-info-table,
.inline-compare-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 720px;
}

.machine-info-table th,
.machine-info-table td,
.inline-compare-table th,
.inline-compare-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #e5edf6;
    text-align: left;
    vertical-align: top;
}

.machine-info-table th,
.inline-compare-table th {
    background: #0f172a;
    color: #ffffff;
    font-family: var(--font-heading);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.machine-info-table tr:nth-child(even) td,
.inline-compare-table tr:nth-child(even) td {
    background: #f8fafc;
}

.machine-info-table tr:last-child td,
.inline-compare-table tr:last-child td {
    border-bottom: none;
}

.machine-spec-group {
    width: auto;
    color: var(--primary);
    font-size: 9.5px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    line-height: 1.1;
}

.machine-spec-name {
    width: auto;
    color: #334155;
    font-size: 11.5px;
    font-weight: 800;
    line-height: 1.25;
}

.machine-spec-value {
    color: #0f172a;
    font-family: var(--font-heading);
    font-size: 14.5px;
    font-weight: 900;
    line-height: 1.25;
    overflow-wrap: anywhere;
}

.inline-compare-area {
    display: grid;
    grid-template-columns: 320px 1fr;
    min-height: 420px;
}

.inline-compare-filter {
    padding: 20px;
    background: #f8fafc;
    border-right: 1px solid #dbe6f3;
}

.inline-filter-title {
    color: #0f172a;
    font-family: var(--font-heading);
    font-size: 15px;
    font-weight: 800;
    margin-bottom: 4px;
}

.inline-filter-note {
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
    margin: 0 0 14px;
}

.inline-compare-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 520px;
    overflow-y: auto;
    padding-right: 4px;
}

.inline-compare-item {
    display: grid;
    grid-template-columns: 18px 48px 1fr;
    grid-template-areas:
        "check image name"
        "check image price";
    gap: 4px 10px;
    align-items: center;
    padding: 10px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: var(--transition);
}

.inline-compare-item:hover {
    border-color: var(--primary);
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.12);
}

.inline-compare-item input {
    grid-area: check;
    accent-color: var(--primary);
}

.inline-compare-item img {
    grid-area: image;
    width: 48px;
    height: 48px;
    object-fit: contain;
}

.inline-compare-name {
    grid-area: name;
    color: #0f172a;
    font-size: 12px;
    font-weight: 800;
    line-height: 1.35;
}

.inline-compare-price {
    grid-area: price;
    color: var(--primary);
    font-size: 12px;
    font-weight: 800;
}

.empty-inline-compare {
    height: 100%;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    padding: 24px;
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
    background: #f8fafc;
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
    background: #ffffff;
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
    background: #ffffff;
}

.highlights-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.highlight-item-card {
    border-radius: 24px;
    background: #ffffff;
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
    font-size: 32px;
    margin-bottom: 16px;
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
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
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
.product-image-box {
    aspect-ratio: 1.1;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    background: #f8fafc;
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
    color: #334155;
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
}
.product-card-rating .score {
    color: #334155;
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
    text-transform: uppercase;
}
.price-side .price-tag {
    font-family: var(--font-heading);
    font-size: 15px;
    font-weight: 800;
    color: var(--primary);
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
    border: 1px solid #cbd5e1;
    background: #111f35;
    cursor: pointer;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-secondary);
    transition: var(--transition);
}
.pag-btn:hover:not(:disabled) {
    background: #111f35;
    color: var(--text-primary);
}
.pag-btn:disabled {
    opacity: 0.4;
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
    background: #111f35;
    cursor: pointer;
    font-family: var(--font-heading);
    font-size: 13px;
    font-weight: 700;
    color: var(--text-secondary);
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
    background: #111f35;
    border-radius: 28px;
    box-shadow: 0 30px 60px rgba(0,0,0,0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.compare-modal-header {
    padding: 24px 30px;
    border-bottom: 1px solid #cbd5e1;
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
    background: #111f35;
    border: none;
    font-size: 16px;
    cursor: pointer;
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
    border-right: 1px solid #cbd5e1;
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
    border: 1px solid rgba(255,255,255,0.05);
    cursor: pointer;
    transition: var(--transition);
}
.picker-item-row:hover {
    background: #0d1b2e;
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
    background: #0d1b2e;
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
    background: #111f35;
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
    background: #ffffff;
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
        border-bottom: 1px solid #cbd5e1;
        max-height: 200px;
    }
}

@media (max-width: 480px) {
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
    max-width: 92%;
}

/* Responsive product title */
.premium-product-title {
    font-size: clamp(20px, 3.6vw, 32px);
}

/* Keep gallery visible while the purchase details scroll on wide screens */
.gallery-column {
    position: sticky;
    top: 112px;
    align-self: start;
    z-index: 2;
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
    border: 1px solid #e2e8f0;
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
    background: #f1f5f9;
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
    .gallery-column { position: static; top: auto; }
    .main-image-viewport { aspect-ratio: 16/10; }

}
</style>
