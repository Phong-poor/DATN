<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'
import { getToken } from '@/services/auth'
import { storageUrl } from '@/services/urls'



const route = useRoute()
const isLoading = ref(true)
const router = useRouter()
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

// ===================== FETCH SẢN PHẨM =====================
const loadCache = (productId) => {
    try {
        const cached = localStorage.getItem(`predator_product_detail_cache_${productId}`)
        if (cached) {
            const parsed = JSON.parse(cached)
            if (parsed.product) product.value = parsed.product
            if (parsed.reviews) reviews.value = parsed.reviews
            if (parsed.recentlyViewedProducts) recentlyViewedProducts.value = parsed.recentlyViewedProducts
            if (parsed.relatedProducts) relatedProducts.value = parsed.relatedProducts
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
            relatedProducts: relatedProducts.value
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
            fetchReviews()
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


</script>

<template>

    <transition name="slide-down">
        <div v-if="thongBao.show" :class="['toast', thongBao.type]">
            {{ thongBao.message }}
        </div>
    </transition>

    <!-- BANNER CHI TIẾT SẢN PHẨM -->
    <div class="banner-section" v-if="!isLoading && product.tenSP">
        <div class="banner-bg" :style="{ backgroundImage: `url(${selectedImage})` }"></div>
        <div class="banner-overlay"></div>
        <div class="banner-content">
            <div class="container">
                <div class="breadcrumb">
                    <router-link to="/">Trang chủ</router-link>
                    <span>/</span>
                    <router-link :to="`/products?cat=${product.id_danhmuc}`">{{ product.danh_muc?.ten_danhmuc || 'Sản phẩm' }}</router-link>
                    <span>/</span>
                    <span>{{ product.tenSP }}</span>
                </div>
                <h1 class="banner-title">{{ product.tenSP }}</h1>
                <div class="banner-meta">
                    <span class="brand" v-if="product.thuong_hieu">{{ product.thuong_hieu.ten_thuonghieu }}</span>
                    <span class="rating" v-if="reviews.length > 0">
                        <span class="stars">⭐ {{ averageRating }}/5</span>
                        <span class="count">({{ reviews.length }} đánh giá)</span>
                    </span>
                    <span class="price-badge">{{ selectedVariant ? formatPrice(selectedVariant.gia) : formatPrice(product.gia) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="page">
        <div class="container">

            <div v-if="isLoading" style="text-align: center; padding: 50px;">
                Đang tải dữ liệu sản phẩm...
            </div>

            <div v-else>
                <!-- PHẦN LỌC LOẠI MÁY -->
                <div class="filter-section" v-if="relatedProducts.length > 0">
                    <div class="filter-header">
                        <h3>🔍 Tìm kiếm sản phẩm tương tự</h3>
                        <p>Chọn loại máy phù hợp với nhu cầu của bạn</p>
                    </div>
                    <div class="filter-categories">
                        <button 
                            class="filter-btn all-products"
                            @click="currentRelatedPage = 1; scrollToRelated()"
                            title="Xem tất cả sản phẩm tương tự">
                            <span class="filter-icon">📱</span>
                            <span>Tất cả</span>
                        </button>
                        <button 
                            v-for="cat in uniqueCategories"
                            :key="cat"
                            class="filter-btn"
                            @click="selectedCategory = selectedCategory === cat ? null : cat; currentRelatedPage = 1; scrollToRelated()"
                            :title="`Lọc sản phẩm ${cat}`">
                            <span class="filter-icon">{{ getCategoryIcon(cat) }}</span>
                            <span>{{ cat }}</span>
                        </button>
                    </div>
                </div>

                <div class="detail">

                    <!-- ẢNH - CAROUSEL CẢI THIỆN -->
                    <div class="image-section">
                        <div class="main-img-wrapper">
                            <div class="main-img">
                                <img :src="selectedImage" :alt="product.tenSP" />
                                <div v-if="selectedVariant && selectedVariant.soluong === 0" class="out-of-stock-badge">
                                    HẾT HÀNG
                                </div>
                            </div>
                            <div class="image-nav">
                                <button @click="selectedImage = allImages[(allImages.indexOf(selectedImage) - 1 + allImages.length) % allImages.length]" class="nav-arrow">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                </button>
                                <button @click="selectedImage = allImages[(allImages.indexOf(selectedImage) + 1) % allImages.length]" class="nav-arrow">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="thumb-wrapper">
                            <button class="thumb-nav p-left" @click="prevThumbs" :disabled="thumbIndex === 0">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"
                                    height="16">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </button>
                            <div class="thumbs">
                                <img v-for="(img, i) in visibleThumbs" :key="i" :src="img" @click="selectedImage = img"
                                    :class="{ active: selectedImage === img }" />
                            </div>
                            <button class="thumb-nav p-right" @click="nextThumbs"
                                :disabled="thumbIndex + thumbLimit >= allImages.length">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"
                                    height="16">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- THÔNG TIN -->
                    <div>
                        <span class="tag" v-if="product.thuong_hieu">{{ product.thuong_hieu.ten_thuonghieu }}</span>
                        <span class="tag" v-else>MỚI NHẤT 2026</span>

                        <h1>{{ product.tenSP }}</h1>

                        <div class="rating">
                            <span class="stars-gold">
                                <template v-for="i in 5">
                                    <span v-if="i <= Math.round(averageRating)">★</span>
                                    <span v-else style="color: #e2e8f0;">★</span>
                                </template>
                            </span>
                            <span class="rating-count">
                                ({{ reviews.length > 0 ? averageRating + '/5' : 'Chưa có đánh giá' }})
                                <span v-if="reviews.length > 0"> • {{ reviews.length }} đánh giá</span>
                            </span>
                        </div>

                        <div class="price">
                            {{ selectedVariant ? formatPrice(selectedVariant.gia) : formatPrice(product.gia) }}
                        </div>


                        <div class="variant-stock" v-if="selectedVariant">
                            <span v-if="selectedVariant.soluong > 0" class="in-stock">
                                ✅ Còn hàng: {{ selectedVariant.soluong }} sản phẩm
                            </span>
                            <span v-else class="out-stock">❌ Hết hàng</span>
                        </div>

                        <div class="product-options" v-if="product.bienThes && product.bienThes.length > 0">
                            <div class="option-group" v-for="group in variantGroups" :key="group.name">
                                <p class="option-label">{{ group.name }}:</p>

                                <div v-if="group.name === 'Màu sắc'" class="color-list">
                                    <button v-for="item in group.values" :key="item.giatri" class="color-btn"
                                        :class="{ active: selectedOptions[group.name] === item.giatri }"
                                        @click="handleSelectOptionWithReset(group.name, item.giatri)"
                                        :title="item.giatri">
                                        <span class="color-dot"
                                            :style="{ backgroundColor: item.ma_mau || '#ccc' }"></span>
                                    </button>
                                </div>

                                <div v-else class="variant-grid">
                                    <button v-for="item in group.values" :key="item.giatri" class="variant-item-btn"
                                        :class="{ active: selectedOptions[group.name] === item.giatri }"
                                        @click="handleSelectOptionWithReset(group.name, item.giatri)">
                                        {{ item.giatri }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="product-options" v-else>
                            <p style="color:#666; font-size:14px;">Sản phẩm đang cập nhật tùy chọn.</p>
                        </div>


                        <div class="qty-wrap" v-if="selectedVariant && selectedVariant.soluong > 0">
                            <p class="option-label">Số lượng:</p>
                            <div class="qty-control">
                                <button @click="giamSoLuong" :disabled="soLuongMua <= 1">−</button>
                                <span>{{ soLuongMua }}</span>
                                <button @click="tangSoLuong"
                                    :disabled="soLuongMua >= selectedVariant.soluong">+</button>
                            </div>
                        </div>


                        <div class="actions">
                            <button class="add"
                                :disabled="!selectedVariant || selectedVariant.soluong === 0 || dangThem"
                                :class="{ 'disabled-btn': !selectedVariant || selectedVariant.soluong === 0 }"
                                @click="themVaoGioHang">
                                <span v-if="dangThem"> Đang thêm...</span>
                                <span v-else> Thêm vào giỏ hàng</span>
                            </button>
                            <button class="install">Trả góp 0%</button>
                            
                            <button class="wishlist-btn" :disabled="dangThemYeuThich" @click="themVaoYeuThich"
                                title="Thêm vào yêu thích">
                                <span v-if="dangThemYeuThich">⏳</span>
                                <span v-else>❤️</span>
                            </button>

                            <button class="compare-btn" title="So sánh sản phẩm" @click="openCompareModal">
                                🔁 So sánh
                            </button>
                        </div>

                        <div class="info">
                            <span>Giao nhanh 2h</span>
                            <span>Bảo hành 24 tháng</span>
                        </div>
                    </div>
                </div>

                <!-- THÔNG SỐ KỸ THUẬT -->
                <div class="specifications" v-if="product.thong_so_ky_thuat && product.thong_so_ky_thuat.length > 0">
                    <div class="spec-header">
                        <h2>Thông số kỹ thuật</h2>
                    </div>
                    <div class="spec-table-wrap">
                        <table class="spec-table">
                            <tbody>
                                <tr v-for="(spec, idx) in product.thong_so_ky_thuat" :key="idx">
                                    <td class="spec-label">{{ spec.ten_thuoctinh }}</td>
                                    <td class="spec-value">{{ spec.giatri }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>



                <!-- Popup so sánh: hiển thị khi người dùng nhấn nút So sánh -->
                <div>
                    <transition name="fade">
                        <div class="compare-modal-overlay" v-if="showCompareModal">
                            <div class="compare-modal">
                                <div class="compare-modal-header">
                                    <h3>Chọn sản phẩm để so sánh (tối đa {{ maxCompare }})</h3>
                                    <button class="close" @click="closeCompareModal">✕</button>
                                </div>

                                <div class="compare-modal-body">
                                    <div class="compare-left">
                                        <div v-if="relatedProducts.length === 0">Không có sản phẩm tương tự để so sánh.</div>
                                        <div class="compare-list">
                                            <label v-for="p in relatedProducts" :key="p.key_id" class="compare-item">
                                                <input type="checkbox" :value="p.key_id" v-model="compareSelection"
                                                    :disabled="compareSelection.length >= maxCompare && !compareSelection.includes(p.key_id)" />
                                                <img :src="p.img" :alt="p.fullName" />
                                                <div class="meta">
                                                    <div class="name">{{ p.fullName }}</div>
                                                    <div class="spec">{{ p.specText }}</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="compare-right">
                                        

                                        <div class="compare-result" v-if="compareSelection.length > 0">
                                            <div class="comparison-table-wrapper">
                                                <table class="comparison-table">
                                                <thead>
                                                    <tr>
                                                        <th class="attr-col">Thông số</th>
                                                        <th class="product-col">
                                                            <div class="prod-header">
                                                                <img :src="selectedImage" />
                                                                <div class="prod-info">
                                                                    <div class="prod-name">{{ product.tenSP }}</div>
                                                                    <div class="prod-price">{{ formatPrice(selectedVariant.gia) }}</div>
                                                                </div>
                                                            </div>
                                                        </th>
                                                        <th class="product-col" v-for="p in compareProducts" :key="p.key_id">
                                                            <div class="prod-header">
                                                                <img :src="p.img" />
                                                                <div class="prod-info">
                                                                    <div class="prod-name">{{ p.fullName }}</div>
                                                                    <div class="prod-price">{{ formatPrice(p.price) }}</div>
                                                                </div>
                                                            </div>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                
                                                <tbody>
                                                    <tr v-for="attr in allAttributeKeys" :key="attr">
                                                        <td class="attr-col">{{ attr }}</td>
                                                        <td class="product-col">
                                                            <!-- Kiểm tra thông số kỹ thuật sản phẩm hiện tại trước (case-insensitive) -->
                                                            <span v-if="product.thong_so_ky_thuat && product.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())" class="value">
                                                                {{ product.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())?.giatri }}
                                                            </span>
                                                            <!-- Nếu không, kiểm tra thuộc tính biến thể (case-insensitive) -->
                                                            <span v-else-if="extractAllAttributes(selectedVariant)[attr.toLowerCase()]" class="value">
                                                                {{ extractAllAttributes(selectedVariant)[attr.toLowerCase()] }}
                                                            </span>
                                                            <span v-else class="no-value">—</span>
                                                        </td>
                                                        <td class="product-col" v-for="p in compareProducts" :key="p.key_id">
                                                            <!-- Kiểm tra thông số kỹ thuật so sánh trước (case-insensitive) -->
                                                            <span v-if="p.thong_so_ky_thuat && p.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())" class="value">
                                                                {{ p.thong_so_ky_thuat.find(s => (s.ten_thuoctinh || '').toLowerCase() === attr.toLowerCase())?.giatri }}
                                                            </span>
                                                            <!-- Nếu không, kiểm tra thuộc tính biến thể (case-insensitive) -->
                                                            <span v-else-if="(p.attributes || {})[attr.toLowerCase()]" class="value">
                                                                {{ (p.attributes || {})[attr.toLowerCase()] }}
                                                            </span>
                                                            <span v-else class="no-value">—</span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- ĐÁNH GIÁ CẢI THIỆN -->
                <div class="reviews" id="reviews-section">
                    <div class="review-header">
                        <div class="review-stats">
                            <h2>💬 Đánh giá từ người dùng</h2>
                            
                            <!-- Rating Summary -->
                            <div v-if="reviews.length > 0" class="rating-summary">
                                <div class="rating-score">
                                    <div class="score-number">{{ averageRating }}</div>
                                    <div class="score-stars">
                                        <span v-for="i in 5" :key="i">{{ i <= Math.round(averageRating) ? '⭐' : '☆' }}</span>
                                    </div>
                                    <div class="score-count">{{ reviews.length }} đánh giá</div>
                                </div>
                                
                                <!-- Rating Distribution -->
                                <div class="rating-distribution">
                                    <div v-for="stars in [5,4,3,2,1]" :key="stars" class="rating-bar">
                                        <span class="rating-label">{{ stars }} ⭐</span>
                                        <div class="bar-container">
                                            <div class="bar-fill" :style="{ width: (ratingDistribution[stars] / reviews.length * 100) + '%' }"></div>
                                        </div>
                                        <span class="rating-percent">{{ Math.round(ratingDistribution[stars] / reviews.length * 100) }}%</span>
                                    </div>
                                </div>
                            </div>
                            <p v-else style="color: #64748b; margin: 0;">Sản phẩm này chưa có đánh giá. Hãy là người đầu tiên mua và đánh giá!</p>
                        </div>
                    </div>

                    <div class="review-list" v-if="reviews.length > 0">
                        <div class="review-card" v-for="review in reviews" :key="review.id_danhgia">
                            <div class="review-header-row">
                                <div class="user-info">
                                    <div class="user-avatar">
                                        {{ review.user?.name?.charAt(0) || 'U' }}
                                    </div>
                                    <div>
                                        <b class="user-name">{{ review.user?.name || 'Người dùng' }}</b>
                                        <p class="review-date">{{ formatDate(review.created_at) }}</p>
                                    </div>
                                </div>
                                <div class="rating-stars">
                                    <span v-for="s in 5" :key="s" class="star">
                                        {{ s <= review.danhgia ? '⭐' : '☆' }}
                                    </span>
                                </div>
                            </div>

                            <p class="review-comment">{{ review.binhluan || 'Hài lòng với sản phẩm.' }}</p>
                        </div>
                    </div>

                    <div v-else class="empty-reviews">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path
                                d="M12 20.25c4.556 0 8.25-3.694 8.25-8.25S16.556 3.75 12 3.75 3.75 7.444 3.75 12s3.694 8.25 8.25 8.25z" />
                            <path d="M12 8.25v7.5M15.75 12h-7.5" />
                        </svg>
                        <p>Chưa có bình luận nào cho sản phẩm này.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="related" v-if="!isLoading && filteredRelatedProducts.length > 0">
        <div class="related-header">
            <h2 v-if="selectedCategory">🔍 Sản phẩm {{ selectedCategory }}</h2>
            <h2 v-else>Sản phẩm tương tự</h2>
            <router-link to="/products">Xem tất cả →</router-link>
        </div>
        <div class="related-list">
            <div class="product-card" v-for="p in paginatedRelatedProducts" :key="p.key_id"
                @click="router.push(`/products/${p.id}?variant=${p.key_id}`)">
                <div class="img-box"><img :src="p.img" :alt="p.fullName" /></div>
                <h4>{{ p.fullName }}</h4>
                <p class="sub">{{ p.specText }}</p>
                <p class="price">{{ formatPrice(p.price) }}</p>
            </div>
        </div>

        <!-- PHÂN TRANG -->
        <div class="related-pagination" v-if="totalRelatedPages > 1">
            <button class="pag-btn" :disabled="currentRelatedPage === 1" @click="currentRelatedPage--">
                &laquo; Trước
            </button>
            <div class="pag-numbers">
                <button v-for="p in totalRelatedPages" :key="p" class="pag-num"
                    :class="{ active: currentRelatedPage === p }" @click="currentRelatedPage = p">
                    {{ p }}
                </button>
            </div>
            <button class="pag-btn" :disabled="currentRelatedPage === totalRelatedPages" @click="currentRelatedPage++">
                Sau &raquo;
            </button>
        </div>
    </div>

    <!-- SẢN PHẨM ĐÃ XEM GẦN ĐÂY -->
    <div class="related" v-if="!isLoading && recentlyViewedProducts.length > 0">
        <div class="related-header">
            <h2>Sản phẩm đã xem gần đây</h2>
        </div>
        <div class="related-list">
            <div class="product-card" v-for="p in paginatedRecentlyViewedProducts" :key="p.key_id"
                @click="router.push(`/products/${p.id}?variant=${p.key_id}`)">
                <div class="img-box"><img :src="p.img" :alt="p.fullName" /></div>
                <h4>{{ p.fullName }}</h4>
                <p class="sub">{{ p.specText }}</p>
                <p class="price">{{ formatPrice(p.price) }}</p>
            </div>
        </div>

        <!-- PHÂN TRANG -->
        <div class="related-pagination" v-if="totalRecentlyViewedPages > 1">
            <button class="pag-btn" :disabled="currentRecentlyViewedPage === 1" @click="currentRecentlyViewedPage--">
                &laquo; Trước
            </button>
            <div class="pag-numbers">
                <button v-for="p in totalRecentlyViewedPages" :key="p" class="pag-num"
                    :class="{ active: currentRecentlyViewedPage === p }" @click="currentRecentlyViewedPage = p">
                    {{ p }}
                </button>
            </div>
            <button class="pag-btn" :disabled="currentRecentlyViewedPage === totalRecentlyViewedPages" @click="currentRecentlyViewedPage++">
                Sau &raquo;
            </button>
        </div>
    </div>

</template>

<style scoped>



/* ===== TOAST THÔNG BÁO ===== */
.wishlist-btn {
    padding: 0 16px;
    border: 1px solid #ff4d4f;
    border-radius: 8px;
    cursor: pointer;
    background: white;
    color: #ff4d4f;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.2s;
}

.wishlist-btn:hover:not(:disabled) {
    background: #fff1f0;
    transform: scale(1.05);
}

.wishlist-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    color: white;
}

.toast.success {
    background: #16a34a;
}

.toast.error {
    background: #dc2626;
}

.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

/* ===== CHỌN SỐ LƯỢNG ===== */
.qty-wrap {
    margin-bottom: 20px;
}

.qty-control {
    display: inline-flex;
    align-items: center;
    gap: 0;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    overflow: hidden;
}

.qty-control button {
    width: 38px;
    height: 38px;
    border: none;
    background: var(--tn-surface-2);
    font-size: 18px;
    cursor: pointer;
    transition: background 0.2s;
}

.qty-control button:hover:not(:disabled) {
    background: #dbeafe;
    color: #2563eb;
}

.qty-control button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.qty-control span {
    min-width: 48px;
    text-align: center;
    font-weight: 600;
    font-size: 15px;
    border-left: 1px solid #cbd5e1;
    border-right: 1px solid #cbd5e1;
    padding: 0 8px;
    line-height: 38px;
}

/* ===== GIỮ NGUYÊN STYLE CŨ ===== */
.page {
    background: #f5f7fb;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 24px;
    font-family: sans-serif;
}

.detail {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 40px;
    align-items: start;
}

.main-img {
    background: #eef2ff;
    padding: 24px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 420px;
    overflow: hidden;
}

.main-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 6px;
}

.thumb-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 15px;
    width: 100%;
}

.thumbs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    flex: 1;
}

.thumbs img {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    cursor: pointer;
    opacity: 0.7;
    border-radius: 6px;
    border: 2px solid #f1f5f9;
    transition: 0.2s;
}

.thumbs img.active {
    border-color: #2563eb;
    opacity: 1;
    background: #fff;
}

.thumb-nav {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid var(--tn-border);
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    transition: 0.2s;
    flex-shrink: 0;
}

.thumb-nav:hover:not(:disabled) {
    background: var(--tn-bg);
    color: #2563eb;
    border-color: #2563eb;
}

.thumb-nav:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.tag {
    background: #e2e8f0;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
}

h1 {
    margin: 10px 0;
}

.price {
    font-size: 24px;
    color: #2563eb;
    font-weight: bold;
    margin-bottom: 20px;
}

.variant-stock {
    margin-top: -10px;
    margin-bottom: 18px;
    font-size: 14px;
}

.in-stock {
    color: #16a34a;
}

.out-stock {
    color: #dc2626;
    font-weight: bold;
}

.product-options {
    margin-bottom: 25px;
}

.option-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 14px;
}

.option-label {
    margin: 0 0 8px;
    font-size: 14px;
    color: #334155;
    font-weight: bold;
}

.variant-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.variant-item-btn {
    padding: 8px 16px;
    border: 1px solid #cbd5e1;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s ease;
    color: #334155;
}

.variant-item-btn:hover {
    border-color: #2563eb;
    color: #2563eb;
}

.variant-item-btn.active {
    border-color: #2563eb;
    background: #eff6ff;
    color: #2563eb;
    font-weight: bold;
    box-shadow: 0 0 0 1px #2563eb;
}

.color-list {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}

.color-btn {
    width: 38px;
    height: 38px;
    border-radius: 999px;
    border: 2px solid transparent;
    background: transparent;
    padding: 3px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.color-btn.active {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px #2563eb;
}

.color-dot {
    display: block;
    width: 100%;
    height: 100%;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
}

.actions {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
}

.add {
    flex: 1;
    background: #2563eb;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.2s;
    font-size: 15px;
    font-weight: 600;
}

.add:hover:not(.disabled-btn) {
    background: #1d4ed8;
}

.disabled-btn {
    opacity: 0.5;
    cursor: not-allowed !important;
}

.install {
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    cursor: pointer;
    background: white;
}

.info {
    display: flex;
    gap: 20px;
    font-size: 13px;
    color: #64748b;
}

.reviews {
    margin-top: 60px;
}

/* ===== THÔNG SỐ KỸ THUẬT ===== */
.specifications {
    margin-top: 60px;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.spec-header {
    margin-bottom: 24px;
    border-left: 4px solid #2563eb;
    padding-left: 16px;
}

.spec-header h2 {
    font-size: 20px;
    margin: 0;
    color: #1e293b;
}

.spec-table-wrap {
    overflow: hidden;
    border-radius: 12px;
    border: 1px solid var(--tn-border);
}

.spec-table {
    width: 100%;
    border-collapse: collapse;
}

.spec-table tr {
    transition: background 0.2s;
}

.spec-table tr:nth-child(even) {
    background: var(--tn-bg);
}

.spec-table tr:hover {
    background: var(--tn-surface-2);
}

.spec-table td {
    padding: 14px 20px;
    font-size: 14px;
    border-bottom: 1px solid #e2e8f0;
}

.spec-table tr:last-child td {
    border-bottom: none;
}

.spec-label {
    width: 30%;
    font-weight: 600;
    color: #64748b;
    background: var(--tn-surface-2);
}

.spec-value {
    color: #1e293b;
}

@media (max-width: 768px) {
    .spec-label {
        width: 40%;
    }
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    align-items: center;
}

.review-header button {
    background: #2563eb;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
}

.review-list {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.review-card {
    background: #dadbdc;
    padding: 15px;
    border-radius: 10px;
}

.stars {
    color: orange;
}

.related {
    max-width: 1200px;
    margin: 60px auto;
    padding: 0 24px;
}

.related-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.related-header h2 {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
}

.related-header a {
    color: #2563eb;
    font-weight: 600;
    text-decoration: none;
    font-size: 14px;
}

.related-list {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
}

.product-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 12px;
    transition: all 0.25s ease;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 36px rgba(0, 0, 0, 0.1);
    border-color: #2563eb;
}

.img-box {
    background: var(--tn-bg);
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 12px;
    overflow: hidden;
}

.img-box img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    border-radius: 8px;
    transition: transform 0.3s;
}

.product-card:hover .img-box img {
    transform: scale(1.05);
}

.product-card h4 {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    /* Standard fallback */
    line-clamp: 2;
    overflow: hidden;
    height: 36px;
    line-height: 1.4;
}

.sub {
    font-size: 11px;
    color: #64748b;
    margin-bottom: 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-card .price {
    font-size: 15px;
    font-weight: 800;
    color: #2563eb;
}

@media (max-width: 1024px) {

    .detail,
    .review-list {
        grid-template-columns: 1fr;
    }

    .related {
        padding: 0 16px;
    }

    .related-list {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .container {
        padding: 16px 12px;
    }
    .main-img {
        height: 320px;
        padding: 12px;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 12px 8px;
    }
    .main-img {
        height: 240px;
        padding: 8px;
    }
    .related-list {
        grid-template-columns: 1fr;
    }
}

/* REVIEW STYLES */
.stars-gold {
    color: #f59e0b;
    font-size: 16px;
    margin-right: 5px;
}

.rating-count {
    font-size: 14px;
    color: #64748b;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f1f5f9;
}

.review-header h2 {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

.review-header p {
    font-size: 14px;
    color: #64748b;
    margin-top: 5px;
}

.review-card {
    background: #fff;
    border: 1px solid var(--tn-border);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    transition: transform 0.2s;
}

.review-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.review-user-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.user-avatar-small {
    width: 36px;
    height: 36px;
    background: #2563eb;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
}

.user-name {
    font-size: 14px;
    color: #0f172a;
}

.review-date {
    font-size: 11px;
    color: #94a3b8;
}

.review-attr-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin: 8px 0;
}

.attr-tag {
    font-size: 12px;
    color: #475569;
    background: var(--tn-bg);
    padding: 4px 10px;
    border-radius: 4px;
    border: 1px solid var(--tn-border);
    font-weight: 500;
}

.review-comment {
    font-size: 14px;
    color: #334155;
    line-height: 1.6;
    margin-top: 5px;
}

.empty-reviews {
    text-align: center;
    padding: 60px 0;
    color: #94a3b8;
}

.empty-reviews svg {
    width: 48px;
    height: 48px;
    margin-bottom: 15px;
}

/* PHÂN TRANG RELATED */
.related-pagination {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
}

.pag-btn {
    padding: 8px 16px;
    border: 1px solid var(--tn-border);
    background: white;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}

.pag-btn:hover:not(:disabled) {
    border-color: #2563eb;
    color: #2563eb;
    background: #eff6ff;
}

.pag-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pag-numbers {
    display: flex;
    gap: 6px;
}

.pag-num {
    width: 36px;
    height: 36px;
    border: 1px solid var(--tn-border);
    background: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}

.pag-num:hover:not(.active) {
    border-color: #cbd5e1;
    color: #0f172a;
}

.pag-num.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

/* ===== COMPARISON STYLES ===== */
.comparison {
    margin-top: 40px;
    padding: 30px 0;
    border-top: 2px solid #f1f5f9;
}

.comparison h2 {
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 25px;
}

.comparison-table-wrapper {
    overflow-x: auto;
    border: 1px solid var(--tn-border);
    border-radius: 12px;
    background: var(--tn-bg);
    max-height: 600px;
    overflow-y: auto;
}

.comparison-table {
    width: 100%;
    min-width: 600px;
    border-collapse: collapse;
    background: white;
}

.comparison-table th,
.comparison-table td {
    padding: 16px 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.comparison-table th {
    background: var(--tn-surface-2);
    font-weight: 700;
    color: #0f172a;
    font-size: 13px;
    vertical-align: top;
}

.comparison-table tbody tr:hover {
    background: var(--tn-bg);
}

.attr-col {
    width: 180px;
    font-weight: 600;
    color: #475569;
    font-size: 13px;
    min-width: 180px;
}

.product-col {
    min-width: 150px;
}

.prod-header {
    display: flex;
    gap: 10px;
    align-items: center;
}

.prod-header img {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid var(--tn-border);
}

.prod-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.prod-name {
    font-size: 12px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.3;
}

.prod-price {
    font-size: 14px;
    font-weight: 700;
    color: #2563eb;
}

.comparison-table tbody td.product-col {
    font-size: 13px;
    color: #0f172a;
}

.value {
    display: inline-block;
    padding: 6px 10px;
    background: #eff6ff;
    border-radius: 6px;
    font-size: 12px;
    color: #0c4a6e;
    font-weight: 600;
}

.no-value {
    color: #cbd5e1;
    font-size: 13px;
}

@media (max-width: 1024px) {
    .comparison-table {
        font-size: 12px;
    }
    
    .attr-col {
        width: 140px;
        min-width: 140px;
    }
    
    .product-col {
        min-width: 130px;
    }
    
    .prod-header img {
        width: 50px;
        height: 50px;
    }
    
    .prod-name {
        font-size: 11px;
    }
}

/* ===== MODAL & COMPARE STYLES (scoped to this component) ===== */
.compare-btn {
    margin-left: 10px;
    background: transparent;
    border: 1px solid var(--tn-border);
    color: #0f172a;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
}
.compare-btn:hover { background: var(--tn-surface-2); }

.compare-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(2,6,23,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 24px;
}

.compare-modal {
    width: min(1200px, 100%);
    background: var(--tn-surface);
    border-radius: 12px;
    box-shadow: 0 20px 50px rgba(2,6,23,0.2);
    overflow: hidden;
}

.compare-modal-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:16px 20px;
    border-bottom:1px solid #f1f5f9;
}
.compare-modal-header h3{ margin:0; }
.compare-modal-header .close{ background:transparent;border:none;font-size:18px;cursor:pointer }

.compare-modal-body{
    display:flex;
    gap:20px;
}
.compare-left{ width:320px; max-height:520px; overflow:auto; padding:16px }
.compare-right{ flex:1; padding:16px; overflow:hidden; max-height:600px; display:flex; flex-direction:column }
.compare-list{ display:flex; flex-direction:column; gap:8px }
.compare-item{ display:flex; gap:10px; align-items:center; padding:8px; border-radius:8px; border:1px solid #f1f5f9 }
.compare-item img{ width:56px;height:56px;object-fit:cover;border-radius:6px }
.compare-item .meta{ font-size:13px }
.compare-item .name{ font-weight:700; color:#0f172a }
.compare-item .spec{ color:#64748b; font-size:12px }

.compare-actions{ padding-bottom:10px }
.compare-result { flex: 1; overflow: hidden; display: flex; flex-direction: column; }
.compare-result .comparison-table{ width:100%; border-collapse:collapse }

@media (max-width: 768px){ .compare-modal{ width:100%; height:100%; border-radius:0 } .compare-left{ display:none } }



@media (max-width: 768px) {
    .comparison {
        display: none;
    }
}

/* ===== BANNER CHI TIẾT SẢN PHẨM ===== */
.banner-section {
    position: relative;
    height: 320px;
    display: flex;
    align-items: center;
    color: white;
    margin-bottom: 40px;
    overflow: hidden;
    border-radius: 0 0 16px 16px;
}

.banner-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    filter: blur(8px) brightness(0.4);
}

.banner-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(2,6,23,0.7) 0%, rgba(37,99,235,0.3) 100%);
}

.banner-content {
    position: relative;
    z-index: 10;
    width: 100%;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    margin-bottom: 16px;
    opacity: 0.9;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
    transition: opacity 0.2s;
}

.breadcrumb a:hover {
    opacity: 0.7;
    text-decoration: underline;
}

.breadcrumb span {
    opacity: 0.6;
}

.banner-title {
    font-size: 36px;
    font-weight: 800;
    margin: 0 0 12px 0;
    line-height: 1.2;
}

.banner-meta {
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 14px;
    flex-wrap: wrap;
}

.brand {
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.rating {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rating .stars {
    font-size: 16px;
}

.rating .count {
    opacity: 0.8;
}

.price-badge {
    font-size: 24px;
    font-weight: 700;
    background: rgba(37,99,235,0.9);
    padding: 8px 16px;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .banner-section {
        height: 240px;
    }
    .banner-title {
        font-size: 24px;
    }
    .banner-meta {
        gap: 12px;
        font-size: 12px;
    }
}

/* ===== FILTER SECTION ===== */
.filter-section {
    background: linear-gradient(135deg, #f0f9ff 0%, #eff6ff 100%);
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 40px;
    border: 1px solid #bfdbfe;
}

.filter-header {
    margin-bottom: 20px;
}

.filter-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px 0;
}

.filter-header p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

.filter-categories {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
}

.filter-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 8px;
    padding: 16px 12px;
    background: white;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    color: #475569;
    font-size: 12px;
}

.filter-btn:hover {
    border-color: #2563eb;
    background: #eff6ff;
    color: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37,99,235,0.15);
}

.filter-btn.all-products {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: white;
    border-color: #2563eb;
}

.filter-btn.all-products:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    border-color: #1e40af;
}

.filter-icon {
    font-size: 24px;
}

@media (max-width: 768px) {
    .filter-section {
        padding: 20px;
    }
    .filter-categories {
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 10px;
    }
}

/* ===== IMAGE SECTION IMPROVEMENTS ===== */
.image-section {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.main-img-wrapper {
    position: relative;
}

.main-img {
    background: linear-gradient(135deg, #f0f9ff 0%, #eff6ff 100%);
    padding: 24px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 420px;
    overflow: hidden;
    border: 1px solid #bfdbfe;
    position: relative;
}

.main-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 6px;
}

.out-of-stock-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: #dc2626;
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 13px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.image-nav {
    position: absolute;
    inset: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    pointer-events: none;
}

.nav-arrow {
    pointer-events: auto;
    width: 44px;
    height: 44px;
    background: rgba(255,255,255,0.9);
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #0f172a;
    transition: all 0.2s;
    margin: 0 12px;
}

.nav-arrow:hover {
    background: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.nav-arrow svg {
    width: 20px;
    height: 20px;
}

/* ===== ENHANCED REVIEW SECTION ===== */
.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 20px;
}

.review-stats {
    flex: 1;
    min-width: 300px;
}

.review-header h2 {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 16px 0;
}

.rating-summary {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 32px;
    align-items: center;
}

.rating-score {
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, #f0f9ff 0%, #eff6ff 100%);
    border-radius: 12px;
    border: 1px solid #bfdbfe;
}

.score-number {
    font-size: 48px;
    font-weight: 800;
    color: #2563eb;
    line-height: 1;
    margin-bottom: 8px;
}

.score-stars {
    font-size: 20px;
    margin-bottom: 8px;
    letter-spacing: 4px;
}

.score-count {
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
}

.rating-distribution {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.rating-bar {
    display: grid;
    grid-template-columns: 60px 1fr 50px;
    align-items: center;
    gap: 12px;
}

.rating-label {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    text-align: left;
}

.bar-container {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.rating-percent {
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    text-align: right;
}

.review-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.review-card {
    background: white;
    border: 1px solid var(--tn-border);
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s;
}

.review-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    border-color: #cbd5e1;
}

.review-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 15px;
    flex-shrink: 0;
}

.user-name {
    font-size: 14px;
    color: #0f172a;
    font-weight: 700;
    display: block;
}

.review-date {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 3px;
}

.rating-stars {
    display: flex;
    gap: 4px;
}

.star {
    font-size: 16px;
    cursor: default;
}

.review-comment {
    font-size: 14px;
    color: #475569;
    line-height: 1.6;
    margin: 0;
}

@media (max-width: 768px) {
    .rating-summary {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .filter-categories {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    }
}

</style>
