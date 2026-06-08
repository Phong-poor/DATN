<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Header from '../Layout/Header.vue'
import Footer from '../Layout/Footer.vue'
import api from '../../services/api'
import { getToken } from '@/services/auth'
import swal from '@/services/swal'
import { productImageUrl } from '@/services/urls'
import { getPrefetchedProductsData, prefetchProductsPage } from '@/services/productsPrefetch'

const thongBao = ref('')
const router = useRouter()
const route = useRoute()

// ===================== STATE =====================
const products = ref([])
const categories = ref([])
const brands = ref([])
const isLoading = ref(true)

// Filters State
const selectedCategories = ref([])
const selectedBrands = ref([])
const selectedRAMs = ref([])
const selectedCPUs = ref([])
const selectedGPUs = ref([])
const selectedKichThuoc = ref([])
const selectedDoPhanGiai = ref([])
const selectedTamNen = ref([])
const selectedPin = ref([])
const selectedSac = ref([])

const attrOptions = ref({
    ram: [], cpu: [], gpu: [], kichthuoc: [],
    dophan: [], tamnen: [], pin: [], sac: []
})
onMounted(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
})
// Collapse
const collapsed = ref({
    danhmuc: false,
    thuonghieu: false,
    ram: true,
    cpu: true,
    gpu: true,
    kichthuoc: true,
    dophan: true,
    tamnen: true,
    pin: true,
    sac: true,
    gia: true
})

const selectedPriceRange = ref('')
const selectedSort = ref('')
const showFilterModal = ref(false)

const applyPrefetchedData = (warm) => {
    if (!warm) return false
    products.value = mapProducts(warm.productsRaw || [])
    categories.value = warm.categories || []
    brands.value = warm.brands || []
    attrOptions.value = warm.attrOptions || attrOptions.value
    applyFilters()
    return true
}

// ===================== MAP PRODUCTS =====================
const interleaveProductVariants = (items) => {
    const groups = new Map()

    items.forEach((item) => {
        const groupKey = item.id
        if (!groups.has(groupKey)) groups.set(groupKey, [])
        groups.get(groupKey).push(item)
    })

    const productGroups = [...groups.values()]
    const result = []
    let hasMore = true
    let variantIndex = 0

    while (hasMore) {
        hasMore = false
        productGroups.forEach((group) => {
            if (group.length > variantIndex) {
                result.push(group[variantIndex])
                hasMore = true
            }
        })
        variantIndex++
    }

    return result
}

const mapProducts = (rawProducts) => {
    const productGroups = rawProducts.map(p => {
        if (!p.bien_thes || p.bien_thes.length === 0) {
            return [{
                id: p.id_sanpham,
                key_id: String(p.id_sanpham),
                name: p.tenSP,
                fullName: p.tenSP,
                id_danhmuc: String(p.id_danhmuc || ''),
                id_thuonghieu: String(p.id_thuonghieu || ''),
                brandName: p.thuong_hieu?.ten_thuonghieu || '',
                weight: p.khoiluong,
                priceNum: 0,
                oldPriceNum: 0,
                specs: [],
                all_variants: [],
                img: productImageUrl(p, null, ''),
                badge: p.trangthai === 'Hot' ? 'HOT' : (p.trangthai === 'Mới' ? 'NEW' : ''),
                badgeColor: p.trangthai === 'Hot' ? '#dc2626' : '#2563eb'
            }]
        }

        const all_vars_info = p.bien_thes.map(bt => {
            let r = '', c = '', g = '', k = '', d = '', t = '', pi = '', s = '', m = '';
            let tt = [];
            try { tt = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || []); } catch (e) { }
            if (Array.isArray(tt)) {
                tt.forEach(a => {
                    const ten = (a.ten_thuoctinh || '').toLowerCase();
                    if (ten === 'ram') r = a.giatri;
                    else if (ten === 'cpu') c = a.giatri;
                    else if (ten === 'gpu') g = a.giatri;
                    else if (ten === 'màu sắc' || ten === 'màu') m = a.giatri;
                });
            }
            return {
                id_bienthe: bt.id_bienthe,
                shortName: [r, c, g, m].filter(Boolean).join(' - ') || 'Mặc định'
            }
        });

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

            // Title = Tên SP + Thông số kỹ thuật chung
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
                id_danhmuc: String(p.id_danhmuc || ''),
                id_thuonghieu: String(p.id_thuonghieu || ''),
                brandName: p.thuong_hieu?.ten_thuonghieu || '',
                weight: p.khoiluong,
                priceNum: bt.gia || 0,
                oldPriceNum: bt.gia_khuyen_mai || 0,
                ram, cpu, gpu, kichthuoc, dophan, tamnen, pin, sac,
                specs: specs,
                all_variants: all_vars_info,
                img: productImageUrl(p, bt, ''),
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
        for (let i = 0; i < productGroups.length; i++) {
            if (productGroups[i].length > variantIndex) {
                flatList.push(productGroups[i][variantIndex]);
                hasMore = true;
            }
        }
        variantIndex++;
    }

    return interleaveProductVariants(flatList);
}

// ===================== FETCH PRODUCTS =====================
const fetchProducts = async () => {
    try {
        const q = route.query.q
        const url = q
            ? `/sanpham/search?q=${encodeURIComponent(q)}`
            : '/sanpham'

        const res = await api.get(url, { skipGlobalLoader: true })
        const raw = Array.isArray(res.data) ? res.data : (res.data.data || [])
        products.value = mapProducts(raw)

    } catch (error) {
        console.error(error)
    }
}

// ===================== LOAD FILTER =====================
const loadFilterData = async () => {
    try {
        const initRes = await api.get('/sanpham/init', { skipGlobalLoader: true })
        const data = initRes.data

        categories.value = data.categories || []
        brands.value = data.brands || []
        attrOptions.value = data.attributes || attrOptions.value

    } catch (error) {
        console.error(error)
    }
}

// ===================== FILTER + PAGINATION =====================
const filteredProducts = ref([])
const currentPage = ref(1)
const itemsPerPage = 15

const displayedProducts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    return filteredProducts.value.slice(start, start + itemsPerPage)
})

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredProducts.value.length / itemsPerPage))
)

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const delta = 1;
    const left = current - delta;
    const right = current + delta + 1;
    let range = [];
    let rangeWithDots = [];
    let l;

    for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= left && i < right)) {
            range.push(i);
        }
    }

    for (let i of range) {
        if (l) {
            if (i - l !== 1) {
                rangeWithDots.push('...');
            }
        }
        rangeWithDots.push(i);
        l = i;
    }

    return rangeWithDots;
})

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const applyFilters = () => {
    // Lọc hoàn toàn phía Vue, không gọi API lại
    let result = [...products.value]

    if (selectedCategories.value.length) {
        result = result.filter(p => selectedCategories.value.includes(String(p.id_danhmuc)))
    }
    if (selectedBrands.value.length) {
        result = result.filter(p => selectedBrands.value.includes(String(p.id_thuonghieu)))
    }
    if (selectedRAMs.value.length) {
        result = result.filter(p => selectedRAMs.value.includes(p.ram))
    }
    if (selectedCPUs.value.length) {
        result = result.filter(p => selectedCPUs.value.includes(p.cpu))
    }
    if (selectedGPUs.value.length) {
        result = result.filter(p => selectedGPUs.value.includes(p.gpu))
    }
    if (selectedKichThuoc.value.length) {
        result = result.filter(p => selectedKichThuoc.value.includes(p.kichthuoc))
    }
    if (selectedDoPhanGiai.value.length) {
        result = result.filter(p => selectedDoPhanGiai.value.includes(p.dophan))
    }
    if (selectedTamNen.value.length) {
        result = result.filter(p => selectedTamNen.value.includes(p.tamnen))
    }
    if (selectedPin.value.length) {
        result = result.filter(p => selectedPin.value.includes(p.pin))
    }
    if (selectedSac.value.length) {
        result = result.filter(p => selectedSac.value.includes(p.sac))
    }
    if (selectedPriceRange.value === 'under20') {
        result = result.filter(p => p.priceNum < 20000000)
    } else if (selectedPriceRange.value === '20to50') {
        result = result.filter(p => p.priceNum >= 20000000 && p.priceNum <= 50000000)
    } else if (selectedPriceRange.value === 'above50') {
        result = result.filter(p => p.priceNum > 50000000)
    }
    if (!selectedSort.value) {
        result = interleaveProductVariants(result)
    } else if (selectedSort.value === 'newest') {
        result.sort((a, b) => b.id - a.id)
    } else if (selectedSort.value === 'price_asc') {
        result.sort((a, b) => a.priceNum - b.priceNum)
    } else if (selectedSort.value === 'price_desc') {
        result.sort((a, b) => b.priceNum - a.priceNum)
    } else if (selectedSort.value === 'name_asc') {
        result.sort((a, b) => a.fullName.localeCompare(b.fullName))
    } else if (selectedSort.value === 'name_desc') {
        result.sort((a, b) => b.fullName.localeCompare(a.fullName))
    }

    filteredProducts.value = result
    currentPage.value = 1
}

// ===================== WATCH =====================
watch(() => route.query.q, async () => {
    isLoading.value = true
    await fetchProducts()
    applyFilters()
    isLoading.value = false
})

watch([
    selectedCategories,
    selectedBrands,
    selectedRAMs,
    selectedCPUs,
    selectedGPUs,
    selectedKichThuoc,
    selectedDoPhanGiai,
    selectedTamNen,
    selectedPin,
    selectedSac,
    selectedPriceRange,
    selectedSort
], applyFilters)

const themVaoYeuThich = async (product) => {
    const token = getToken()
    if (!token) {
        swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập!')
        router.push('/login')
        return
    }

    try {
        await api.post('/yeu-thich/them', {
            id_bienthe: product.key_id,
            soluong: 1
        }, {
            headers: { Authorization: `Bearer ${token}` }
        })

        swal.success('Thành công', `Đã thêm ${product.name} vào yêu thích`)
        window.dispatchEvent(new Event('wishlist-updated'))

    } catch (err) {
        console.error('Lỗi thêm yêu thích:', err)
        swal.error('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra, không thể thêm vào yêu thích!')
    }
}

const loadCache = () => {
    try {
        const cached = localStorage.getItem('predator_products_cache')
        if (cached) {
            const parsed = JSON.parse(cached)
            if (parsed.productsRaw) products.value = mapProducts(parsed.productsRaw)
            if (parsed.categories) categories.value = parsed.categories
            if (parsed.brands) brands.value = parsed.brands
            if (parsed.attrOptions) attrOptions.value = parsed.attrOptions
            applyFilters()
            return true
        }
    } catch (e) {
        console.error('Lỗi load cache sản phẩm:', e)
    }
    return false
}

const saveCache = (productsRaw, categoriesData, brandsData, attrOptionsData) => {
    try {
        localStorage.setItem('predator_products_cache', JSON.stringify({
            productsRaw,
            categories: categoriesData,
            brands: brandsData,
            attrOptions: attrOptionsData
        }))
    } catch (e) {
        console.error('Lỗi save cache sản phẩm:', e)
    }
}

// ===================== INIT =====================
onMounted(async () => {
    // 1. Tải cache ngay lập tức để hiển thị tức thì
    const hasCache = loadCache()
    if (hasCache) {
        isLoading.value = false
    } else {
        isLoading.value = true
    }

    // Xử lý query params từ trang khác (vd: Home) gửi qua
    if (route.query.cat) {
        selectedCategories.value = [String(route.query.cat)]
    }
    if (route.query.brand) {
        selectedBrands.value = [String(route.query.brand)]
    }

    // 2. Chạy ngầm tải dữ liệu mới từ API máy chủ
    try {
        const q = route.query.q
        const url = q
            ? `/sanpham/search?q=${encodeURIComponent(q)}`
            : '/sanpham'

        const [spRes, catRes, brandRes, attrRes] = await Promise.all([
            api.get(url, { skipGlobalLoader: true }),
            api.get('/danhmuc', { skipGlobalLoader: true }),
            api.get('/thuonghieu', { skipGlobalLoader: true }),
            api.get('/sanpham/attribute-options', { skipGlobalLoader: true })
        ])

        const rawProducts = Array.isArray(spRes.data) ? spRes.data : (spRes.data.data || [])
        const categoriesData = catRes.data?.data || catRes.data || []
        const brandsData = brandRes.data?.data || brandRes.data || []
        const attrOptionsData = attrRes.data || attrOptions.value

        products.value = mapProducts(rawProducts)
        categories.value = categoriesData
        brands.value = brandsData
        attrOptions.value = attrOptionsData

        applyFilters()
        isLoading.value = false

        // Lưu cache mới nhất (chỉ lưu nếu không tìm kiếm để tránh đè cache chung bằng kết quả tìm kiếm hẹp)
        if (!q) {
            saveCache(rawProducts, categoriesData, brandsData, attrOptionsData)
        }
    } catch (error) {
        console.error('Lỗi khi tải dữ liệu sản phẩm:', error)
        isLoading.value = false
    }
})

// ===================== HELPERS =====================
const formatPrice = (p) => new Intl.NumberFormat('vi-VN').format(p) + 'đ'

const toggleList = (listType, item) => {
    const val = String(item)
    let target = []
    if (listType === 'cat') target = selectedCategories
    else if (listType === 'brand') target = selectedBrands
    else if (listType === 'ram') target = selectedRAMs
    else if (listType === 'cpu') target = selectedCPUs
    else if (listType === 'gpu') target = selectedGPUs
    else if (listType === 'kichthuoc') target = selectedKichThuoc
    else if (listType === 'dophan') target = selectedDoPhanGiai
    else if (listType === 'tamnen') target = selectedTamNen
    else if (listType === 'pin') target = selectedPin
    else if (listType === 'sac') target = selectedSac

    const idx = target.value.indexOf(val)
    if (idx === -1) target.value.push(val)
    else target.value.splice(idx, 1)
}
// ===================== CART (GIỎ HÀNG) =====================
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

        swal.success('Thành công', `Đã thêm ${product.name} vào giỏ hàng!`)
        window.dispatchEvent(new Event('cart-updated'))

    } catch (err) {
        console.error('Lỗi thêm giỏ hàng:', err)
        swal.error('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra, không thể thêm vào giỏ hàng!')
    }
}
const clearAll = () => {
    selectedCategories.value = []
    selectedBrands.value = []
    selectedRAMs.value = []
    selectedCPUs.value = []
    selectedGPUs.value = []
    selectedKichThuoc.value = []
    selectedDoPhanGiai.value = []
    selectedTamNen.value = []
    selectedPin.value = []
    selectedSac.value = []
    selectedPriceRange.value = ''
    applyFilters()
}
</script>

<template>
    <div class="page">
        <div class="container">

            <!-- FILTER MODAL OVERLAY -->
            <transition name="modal-fade">
                <div v-if="showFilterModal" class="filter-modal-overlay" @click.self="showFilterModal = false">
                    <div class="filter-modal">
                        <div class="modal-header">
                            <h3>Tất cả bộ lọc</h3>
                            <button class="close-modal" @click="showFilterModal = false">✕ Đóng</button>
                        </div>
                        <div class="modal-body">
                            <!-- DANH MỤC -->
                            <div class="modal-section" v-if="categories.length > 0">
                                <p class="modal-section-title">Danh mục</p>
                                <div class="modal-chips">
                                    <span v-for="c in categories" :key="c.id_danhmuc" class="modal-chip"
                                        :class="{ active: selectedCategories.includes(String(c.id_danhmuc)) }"
                                        @click="toggleList('cat', c.id_danhmuc)">
                                        {{ c.ten_danhmuc }}
                                    </span>
                                </div>
                            </div>
                            <!-- THƯƠNG HIỆU -->
                            <div class="modal-section" v-if="brands.length > 0">
                                <p class="modal-section-title">Hãng</p>
                                <div class="modal-chips">
                                    <span v-for="b in brands" :key="b.id_thuonghieu" class="modal-chip"
                                        :class="{ active: selectedBrands.includes(String(b.id_thuonghieu)) }"
                                        @click="toggleList('brand', b.id_thuonghieu)">
                                        {{ b.ten_thuonghieu === 'Levono' ? 'Lenovo' : b.ten_thuonghieu }}
                                    </span>
                                </div>
                            </div>
                            <!-- MỨC GIÁ -->
                            <div class="modal-section">
                                <p class="modal-section-title">Giá</p>
                                <div class="modal-chips">
                                    <span class="modal-chip" :class="{ active: selectedPriceRange === '' }" @click="selectedPriceRange = ''">Tất cả</span>
                                    <span class="modal-chip" :class="{ active: selectedPriceRange === 'under20' }" @click="selectedPriceRange = 'under20'">Dưới 20 triệu</span>
                                    <span class="modal-chip" :class="{ active: selectedPriceRange === '20to50' }" @click="selectedPriceRange = '20to50'">20 - 50 triệu</span>
                                    <span class="modal-chip" :class="{ active: selectedPriceRange === 'above50' }" @click="selectedPriceRange = 'above50'">Trên 50 triệu</span>
                                </div>
                            </div>
                            <!-- RAM -->
                            <div class="modal-section" v-if="attrOptions.ram.length > 0">
                                <p class="modal-section-title">RAM</p>
                                <div class="modal-chips">
                                    <span v-for="r in attrOptions.ram" :key="r" class="modal-chip"
                                        :class="{ active: selectedRAMs.includes(r) }" @click="toggleList('ram', r)">{{ r }}</span>
                                </div>
                            </div>
                            <!-- CPU -->
                            <div class="modal-section" v-if="attrOptions.cpu.length > 0">
                                <p class="modal-section-title">CPU</p>
                                <div class="modal-chips">
                                    <span v-for="r in attrOptions.cpu" :key="r" class="modal-chip"
                                        :class="{ active: selectedCPUs.includes(r) }" @click="toggleList('cpu', r)">{{ r }}</span>
                                </div>
                            </div>
                            <!-- GPU -->
                            <div class="modal-section" v-if="attrOptions.gpu.length > 0">
                                <p class="modal-section-title">GPU</p>
                                <div class="modal-chips">
                                    <span v-for="r in attrOptions.gpu" :key="r" class="modal-chip"
                                        :class="{ active: selectedGPUs.includes(r) }" @click="toggleList('gpu', r)">{{ r }}</span>
                                </div>
                            </div>
                            <!-- Kích thước -->
                            <div class="modal-section" v-if="attrOptions.kichthuoc.length > 0">
                                <p class="modal-section-title">Kích thước màn hình</p>
                                <div class="modal-chips">
                                    <span v-for="r in attrOptions.kichthuoc" :key="r" class="modal-chip"
                                        :class="{ active: selectedKichThuoc.includes(r) }" @click="toggleList('kichthuoc', r)">{{ r }}</span>
                                </div>
                            </div>
                            <!-- Tấm nền -->
                            <div class="modal-section" v-if="attrOptions.tamnen.length > 0">
                                <p class="modal-section-title">Tấm nền</p>
                                <div class="modal-chips">
                                    <span v-for="r in attrOptions.tamnen" :key="r" class="modal-chip"
                                        :class="{ active: selectedTamNen.includes(r) }" @click="toggleList('tamnen', r)">{{ r }}</span>
                                </div>
                            </div>
                            <!-- Độ phân giải -->
                            <div class="modal-section" v-if="attrOptions.dophan.length > 0">
                                <p class="modal-section-title">Độ phân giải</p>
                                <div class="modal-chips">
                                    <span v-for="r in attrOptions.dophan" :key="r" class="modal-chip"
                                        :class="{ active: selectedDoPhanGiai.includes(r) }" @click="toggleList('dophan', r)">{{ r }}</span>
                                </div>
                            </div>
                            <!-- Pin -->
                            <div class="modal-section" v-if="attrOptions.pin.length > 0">
                                <p class="modal-section-title">Pin</p>
                                <div class="modal-chips">
                                    <span v-for="r in attrOptions.pin" :key="r" class="modal-chip"
                                        :class="{ active: selectedPin.includes(r) }" @click="toggleList('pin', r)">{{ r }}</span>
                                </div>
                            </div>
                            <!-- Sạc -->
                            <div class="modal-section" v-if="attrOptions.sac.length > 0">
                                <p class="modal-section-title">Sạc</p>
                                <div class="modal-chips">
                                    <span v-for="r in attrOptions.sac" :key="r" class="modal-chip"
                                        :class="{ active: selectedSac.includes(r) }" @click="toggleList('sac', r)">{{ r }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="modal-btn-clear" @click="clearAll">Xóa bộ lọc</button>
                            <button class="modal-btn-apply" @click="() => { applyFilters(); showFilterModal = false; }">Xác nhận</button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- CONTENT -->
            <main class="content">

                <!-- TOP BAR -->
                <div class="top-bar">
                    <div>
                        <h1>Danh sách Laptop</h1>
                        <p v-if="!isLoading">Tìm thấy <b>{{ filteredProducts.length }}</b> sản phẩm phù hợp</p>
                        <p v-else>Đang tìm kiếm sản phẩm...</p>
                    </div>
                </div>

                <!-- FILTER + SORT BAR -->
                <div class="filter-sort-bar">
                    <!-- Nút Lọc -->
                    <button class="btn-open-filter" @click="showFilterModal = true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        Lọc
                        <span v-if="selectedCategories.length + selectedBrands.length + selectedRAMs.length + selectedCPUs.length + selectedGPUs.length + selectedKichThuoc.length + selectedDoPhanGiai.length + selectedTamNen.length + selectedPin.length + selectedSac.length + (selectedPriceRange ? 1 : 0) > 0" class="filter-count">
                            {{ selectedCategories.length + selectedBrands.length + selectedRAMs.length + selectedCPUs.length + selectedGPUs.length + selectedKichThuoc.length + selectedDoPhanGiai.length + selectedTamNen.length + selectedPin.length + selectedSac.length + (selectedPriceRange ? 1 : 0) }}
                        </span>
                    </button>

                    <button class="btn-tat-ca" @click="clearAll">Tất cả</button>

                    <!-- Sắp xếp -->
                    <div class="sort-wrap" style="margin-left: auto;">
                        <select v-model="selectedSort" class="sort-select">
                            <option value="newest">Mới nhất</option>
                            <option value="price_asc">Giá tăng dần</option>
                            <option value="price_desc">Giá giảm dần</option>
                            <option value="name_asc">Tên A-Z</option>
                            <option value="name_desc">Tên Z-A</option>
                        </select>
                        <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                <!-- LOADING STATE -->
                <div v-if="isLoading" class="loading-grid">
                    <div class="skeleton-card" v-for="i in 6" :key="i"></div>
                </div>

                <!-- Không có kết quả -->
                <div v-if="products.length === 0 && route.query.q" class="empty-search">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                    <p>Không tìm thấy sản phẩm nào cho <strong>"{{ route.query.q }}"</strong></p>
                    <button @click="router.push('/products')" class="clear-search-btn">Xem tất cả sản phẩm</button>
                </div>

                <!-- GRID -->
                <div class="grid" v-else-if="displayedProducts.length > 0">
                    <div class="card" v-for="p in displayedProducts" :key="p.key_id">

                        <span v-if="p.badge" class="badge" :style="{ background: p.badgeColor }">{{ p.badge }}</span>

                        <div class="img-box" @click="router.push(`/products/${p.id}?variant=${p.key_id}`)">
                            <img :src="p.img" :alt="p.name" />
                        </div>

                        <div class="card-body">
                            <h3 @click="router.push(`/products/${p.id}?variant=${p.key_id}`)">{{ p.fullName }}</h3>
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
                                <span v-if="p.oldPriceNum > p.priceNum" class="old-price">{{ formatPrice(p.oldPriceNum)
                                }}</span>
                            </div>

                            <div class="card-actions">
                                <router-link :to="`/products/${p.id}?variant=${p.key_id}`" class="btn-detail">
                                    Chi tiết
                                </router-link>

                                <button class="btn-cart" title="Yêu thích" @click="themVaoYeuThich(p)"
                                    style="background: white; border: 1px solid #ff4d4f; color: #ff4d4f;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                        stroke-linecap="round">
                                        <path
                                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                                        </path>
                                    </svg>
                                </button>

                                <button class="btn-cart" title="Thêm vào giỏ hàng" @click.stop="themVaoGioHang(p)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="9" cy="21" r="1"></circle>
                                        <circle cx="20" cy="21" r="1"></circle>
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- EMPTY STATE -->
                <div v-else class="empty-state">
                    <h3>Không tìm thấy sản phẩm nào</h3>
                    <button @click="clearAll">Xóa bộ lọc</button>
                </div>

                <!-- PAGINATION -->
                <div class="pagination" v-if="totalPages > 1">
                    <button class="pg-btn" :disabled="currentPage === 1" @click="changePage(currentPage - 1)">
                        &lsaquo;
                    </button>
                    <button v-for="(page, index) in visiblePages" :key="index" class="pg-btn"
                        :class="{ active: currentPage === page, dots: page === '...' }" :disabled="page === '...'"
                        @click="page !== '...' && changePage(page)">
                        {{ page }}
                    </button>
                    <button class="pg-btn" :disabled="currentPage === totalPages" @click="changePage(currentPage + 1)">
                        &rsaquo;
                    </button>
                </div>
                <div class="pagination-info" v-if="filteredProducts.length > 0">
                    <strong>{{ filteredProducts.length }}</strong> biến thể — trang <strong>{{ currentPage }}/{{
                        totalPages }}</strong>
                </div>

            </main>
        </div>
    </div>
</template>

<style scoped>

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

* {
    box-sizing: border-box;
}

.page {
    font-family: 'Inter', sans-serif;
    background: var(--tn-bg);
    padding: 32px 0 60px;
    min-height: 100vh;
}

.container {
    width: min(1200px, 95%);
    margin: auto;
}

.layout {
    display: block;
    gap: 0;
    align-items: flex-start;
}

/* ===== FILTER MODAL ===== */
.filter-modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 2000;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 48px 20px;
    overflow-y: auto;
}
.filter-modal {
    background: white;
    border-radius: 14px;
    width: 100%;
    max-width: 860px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    display: flex;
    flex-direction: column;
    max-height: 85vh;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}
.modal-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
}
.close-modal {
    background: white;
    border: 1px solid var(--tn-border);
    padding: 6px 14px;
    border-radius: 8px;
    color: #64748b;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}
.close-modal:hover { background: var(--tn-surface-2); }
.modal-body {
    padding: 20px 24px;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.modal-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin: 0 0 10px;
}
.modal-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.modal-chip {
    padding: 7px 14px;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    background: white;
    transition: all 0.2s;
}
.modal-chip:hover {
    border-color: #2563eb;
    color: #2563eb;
}
.modal-chip.active {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    border-color: transparent;
}
.modal-footer {
    display: flex;
    justify-content: center;
    gap: 16px;
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    background: var(--tn-bg);
    border-radius: 0 0 14px 14px;
    flex-shrink: 0;
}
.modal-btn-clear {
    background: white;
    border: 1.5px solid #e2e8f0;
    padding: 10px 28px;
    border-radius: 8px;
    color: #ef4444;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}
.modal-btn-clear:hover { background: #fef2f2; border-color: #ef4444; }
.modal-btn-apply {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    border: none;
    padding: 10px 32px;
    border-radius: 8px;
    color: white;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: opacity 0.2s;
}
.modal-btn-apply:hover { opacity: 0.9; }

/* Modal transition */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

/* ===== FILTER + SORT BAR ===== */
.filter-sort-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.btn-open-filter {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: white;
    border: 1.5px solid #2563eb;
    border-radius: 8px;
    color: #2563eb;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    position: relative;
    transition: all 0.2s;
    flex-shrink: 0;
}
.btn-open-filter svg { width: 15px; height: 15px; stroke: #2563eb; }
.btn-open-filter:hover { background: #eff6ff; }
.filter-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    background: #2563eb;
    color: white;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 700;
    margin-left: 2px;
}
.quick-brands {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    flex: 1;
    min-width: 0;
}
.quick-brands::-webkit-scrollbar { height: 0; }
.brand-pill {
    padding: 8px 14px;
    background: var(--tn-surface-2);
    border-radius: 8px;
    border: 1.5px solid transparent;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}
.brand-pill:hover { background: #e2e8f0; }
.brand-pill.active {
    background: white;
    border-color: #2563eb;
    color: #2563eb;
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

.sidebar-header svg {
    width: 16px;
    height: 16px;
    color: #2563eb;
}

.sidebar-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.divider {
    height: 1px;
    background: var(--tn-surface-2);
    margin: 16px 0;
}

.group-label {
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    margin: 0;
}

/* Dropdown header */
.filter-group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    padding: 6px 0;
    user-select: none;
    margin-bottom: 4px;
    border-radius: 6px;
    transition: background 0.15s;
}

.filter-group-header:hover {
    background: var(--tn-bg);
}

.arrow-icon {
    width: 16px;
    height: 16px;
    color: #94a3b8;
    transition: transform 0.25s ease;
    flex-shrink: 0;
}

.arrow-icon.rotated {
    transform: rotate(-90deg);
}

/* Collapse animation */
.collapse-body {
    overflow: hidden;
    max-height: 400px;
    transition: max-height 0.3s ease, opacity 0.25s ease;
    opacity: 1;
    margin-bottom: 6px;
}

.collapse-body.closed {
    max-height: 0;
    opacity: 0;
}

.check-label {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 13px;
    color: #334155;
    margin-bottom: 9px;
    cursor: pointer;
    user-select: none;
}

.check-label input {
    display: none;
}

.checkmark {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    flex-shrink: 0;
    border: 1.5px solid #cbd5e1;
    background: white;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.check-label input:checked~.checkmark {
    background: #2563eb;
    border-color: #2563eb;
}

.check-label input:checked~.checkmark::after {
    content: '';
    width: 8px;
    height: 5px;
    border-left: 2px solid white;
    border-bottom: 2px solid white;
    transform: rotate(-45deg) translate(1px, -1px);
    display: block;
}

.brands {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.brand-tag {
    padding: 5px 12px;
    border-radius: 20px;
    border: 1px solid var(--tn-border);
    font-size: 12px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
}

.brand-tag:hover {
    border-color: #2563eb;
    color: #2563eb;
    background: #f0f6ff;
}

.brand-tag.active {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    border-color: transparent;
}

.attr-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
}

.attr-tag {
    padding: 5px 14px;
    border-radius: 20px;
    border: 1px solid var(--tn-border);
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    background: white;
    transition: all 0.2s;
}

.attr-tag:hover {
    border-color: #2563eb;
    color: #2563eb;
    background: #f0f6ff;
}

.attr-tag.active {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    border-color: transparent;
}


.apply-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 100%;
    padding: 10px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 15px;
    transition: all 0.2s;
}

.apply-btn svg {
    width: 14px;
    height: 14px;
}

.apply-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.reset-link {
    display: block;
    width: 100%;
    background: none;
    border: none;
    color: #64748b;
    font-size: 12px;
    margin-top: 12px;
    cursor: pointer;
    text-decoration: underline;
}

/* ===== CONTENT ===== */
.content {
    flex: 1;
    min-width: 0;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 20px;
}

.top-bar h1 {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 4px;
}

.top-bar p {
    font-size: 13px;
    color: #94a3b8;
    margin: 0;
}

.top-bar b {
    color: #2563eb;
}

.sort-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border-radius: 10px;
    border: 1px solid var(--tn-border);
    background: white;
    cursor: pointer;
}

.sort-wrap select {
    border: none;
    outline: none;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    cursor: pointer;
    appearance: none;
    min-width: 130px;
    font-family: 'Inter', sans-serif;
}

.chevron {
    width: 12px;
    height: 12px;
    color: #94a3b8;
}

/* GRID */
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.card {
    background: white;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    overflow: hidden;
    position: relative;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
}

.badge {
    position: absolute;
    top: 12px;
    left: 12px;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 8px;
    z-index: 1;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
}

.img-box {
    background: var(--tn-bg);
    padding: 14px;
    cursor: pointer;
    overflow: hidden;
    position: relative;
    aspect-ratio: 16 / 9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.img-box img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 10px;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.card:hover .img-box img {
    transform: scale(1.06);
}

.card-body {
    padding: 16px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.card-body h3 {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px;
    cursor: pointer;
    line-height: 1.45;
    min-height: 58px;
    transition: color 0.2s ease;
}

.card-body h3:hover {
    color: #2563eb;
}

.brand-txt {
    font-size: 11px;
    color: #94a3b8;
    margin: 0 0 10px;
    height: 16px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    font-weight: 500;
}

.price-row {
    margin-top: auto; /* Pin to bottom */
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 12px;
}

.price {
    font-size: 16px;
    font-weight: 800;
    color: #2563eb;
    letter-spacing: -0.3px;
}

.old-price {
    font-size: 12px;
    color: #cbd5e1;
    text-decoration: line-through;
    font-weight: 500;
}

.card-actions {
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
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-detail:hover {
    background: #2563eb;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.btn-cart {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
}

.btn-cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-cart svg {
    width: 14px;
    height: 14px;
}

/* EMPTY SEARCH */
.empty-search {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 80px 0;
    color: #94a3b8;
}

.empty-search svg {
    width: 64px;
    height: 64px;
    stroke: #cbd5e1;
    stroke-width: 1.5;
}

.empty-search p {
    font-size: 16px;
    color: #64748b;
}

.empty-search strong {
    color: #0f172a;
}

.clear-search-btn {
    padding: 10px 24px;
    border-radius: 10px;
    background: #2563eb;
    color: #fff;
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.clear-search-btn:hover {
    background: #1d4ed8;
}

/* PAGINATION */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
    margin-top: 28px;
}

/* SKELETON & LOADING */
.loading-small {
    font-size: 12px;
    color: #94a3b8;
    padding: 5px 0;
}

.loading-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    width: 100%;
}

.skeleton-card {
    height: 320px;
    background: linear-gradient(90deg, #f1f5f9 25%, #f8fafc 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 16px;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}

/* RADIO CUSTOM */
.checkmark.radio {
    border-radius: 50%;
}

.check-label input:checked~.checkmark.radio::after {
    content: '';
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    border: none;
    transform: none;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 20px;
    border: 1px dashed #e2e8f0;
    grid-column: 1 / -1;
}

.empty-state h3 {
    font-size: 18px;
    color: #0f172a;
    margin: 0 0 8px;
}

.empty-state button {
    padding: 10px 20px;
    border-radius: 10px;
    border: 1px solid var(--tn-border);
    background: var(--tn-bg);
    cursor: pointer;
}

/* PAGINATION */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 28px;
}

.pg-btn {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    border: 1px solid var(--tn-border);
    background: white;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    cursor: pointer;
    transition: all 0.2s;
}

.pg-btn:not(.dots):hover:not(:disabled) {
    border-color: #2563eb;
    color: #2563eb;
}

.pg-btn.active {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    border-color: transparent;
}

.pg-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pg-btn.dots {
    border: none;
    background: transparent;
    opacity: 1;
    cursor: default;
    padding: 0 4px;
    width: auto;
    font-size: 15px;
    color: #94a3b8;
}

.pagination-info {
    text-align: right;
    font-size: 13px;
    color: #64748b;
    margin-top: 14px;
}

.pagination-info strong {
    color: #0f172a;
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .layout {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        position: static;
    }

    .grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .top-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}

@media (max-width: 480px) {
    .grid {
        grid-template-columns: 1fr;
    }
}

.card-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-top: 12px;
}

.btn-cart {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid var(--tn-border);
    background: #fff;
    color: #334155;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-cart:hover {
    background: #5b5ef4;
    border-color: #5b5ef4;
    color: #fff;
}

.btn-cart svg {
    width: 18px;
    height: 18px;
}

.specs-box {
    background: var(--tn-bg);
    border: 1px solid var(--tn-border);
    border-radius: 8px;
    padding: 10px;
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.spec-item {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    line-height: 1.4;
}

.spec-label {
    color: #64748b;
    font-weight: 500;
}

.spec-value {
    color: #0f172a;
    font-weight: 600;
    text-align: right;
}


.variant-chip {
    padding: 4px 8px;
    font-size: 11px;
    color: #475569;
    background: #fff;
    border-radius: 6px;
    border: 1px solid var(--tn-border);
    text-decoration: none;
    transition: all 0.2s;
    font-weight: 500;
}

.variant-chip:hover {
    border-color: #5b5ef4;
    color: #5b5ef4;
}

.variant-chip.active {
    background: #ebf5ff;
    color: #2563eb;
    border-color: #2563eb;
}

.card h3 {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
    cursor: pointer;
    line-height: 1.5;
}

/* ===== FILTER SORT BAR ===== */
.filter-sort-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.btn-open-filter {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: white;
    border: 1.5px solid #2563eb;
    border-radius: 8px;
    color: #2563eb;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    position: relative;
    transition: all 0.2s;
    flex-shrink: 0;
    font-family: 'Inter', sans-serif;
}

.btn-open-filter svg {
    width: 15px;
    height: 15px;
}

.btn-open-filter:hover {
    background: #eff6ff;
}

.filter-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    background: #2563eb;
    color: white;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 700;
    margin-left: 2px;
}

.btn-tat-ca {
    padding: 8px 16px;
    background: var(--tn-surface-2);
    border: 1.5px solid transparent;
    border-radius: 8px;
    color: #475569;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}

.btn-tat-ca:hover {
    background: #e2e8f0;
}

.sort-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    border: 1px solid var(--tn-border);
    background: white;
    cursor: pointer;
}

.sort-select {
    border: none;
    outline: none;
    background: transparent;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    cursor: pointer;
    appearance: none;
    min-width: 130px;
    font-family: 'Inter', sans-serif;
}

.chevron {
    width: 12px;
    height: 12px;
    color: #94a3b8;
    flex-shrink: 0;
}
</style>
