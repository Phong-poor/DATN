<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import Header from '../Layout/Header.vue'
import Footer from '../Layout/Footer.vue'
import api from '../../services/api'

const router = useRouter()

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

// Trạng thái đóng/mở từng section
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
const selectedSort = ref('newest')

// ===================== FETCH DATA =====================
const mapProducts = (rawProducts) => {
    const productVariants = rawProducts.map(p => {
        if (!p.bien_thes || p.bien_thes.length === 0) {
            return [{
                id: p.id_sanpham,
                key_id: String(p.id_sanpham),
                name: p.tenSP,
                id_danhmuc: String(p.id_danhmuc || p.danh_muc?.id_danhmuc || ''),
                id_thuonghieu: String(p.id_thuonghieu || p.thuong_hieu?.id_thuonghieu || ''),
                brandName: p.thuong_hieu?.ten_thuonghieu === 'Levono' ? 'Lenovo' : (p.thuong_hieu?.ten_thuonghieu || ''),
                weight: p.khoiluong,
                priceNum: 0,
                oldPriceNum: 0,
                img: p.hinhanh ? 'http://127.0.0.1:8000/storage/' + p.hinhanh : 'https://via.placeholder.com/300',
                badge: p.trangthai === 'Hot' ? 'HOT' : (p.trangthai === 'Mới' ? 'NEW' : ''),
                badgeColor: p.trangthai === 'Hot' ? '#dc2626' : '#2563eb'
            }];
        }

        return p.bien_thes.map(bt => {
            let ram = '', cpu = '', gpu = '', kichthuoc = '', dophan = '', tamnen = '', pin = '', sac = '';
            
            let thuoc_tinh = [];
            try {
                thuoc_tinh = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || []);
            } catch (e) { }

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
                id_danhmuc: String(p.id_danhmuc || p.danh_muc?.id_danhmuc || ''),
                id_thuonghieu: String(p.id_thuonghieu || p.thuong_hieu?.id_thuonghieu || ''),
                brandName: p.thuong_hieu?.ten_thuonghieu === 'Levono' ? 'Lenovo' : (p.thuong_hieu?.ten_thuonghieu || ''),
                weight: p.khoiluong,
                priceNum: bt.gia || 0,
                oldPriceNum: bt.gia_khuyen_mai || 0,
                img: p.hinhanh ? 'http://127.0.0.1:8000/storage/' + p.hinhanh : 'https://via.placeholder.com/300',
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

onMounted(async () => {
    try {
        isLoading.value = true
        const [catRes, brandRes, attrRes] = await Promise.all([
            api.get('/danhmuc'),
            api.get('/thuonghieu'),
            api.get('/sanpham/attribute-options')
        ])
        
        categories.value = catRes.data?.data || catRes.data || []
        brands.value = brandRes.data?.data || brandRes.data || []
        attrOptions.value = attrRes.data || {
            ram: [], cpu: [], gpu: [], kichthuoc: [], 
            dophan: [], tamnen: [], pin: [], sac: []
        }
        
        // Initial apply
        applyFilters()
    } catch (error) {
        console.error('Lỗi tải dữ liệu:', error)
    } finally {
        isLoading.value = false
    }
})

// ===================== FILTER LOGIC =====================
const filteredProducts = ref([])
const currentPage = ref(1)
const itemsPerPage = 15

const displayedProducts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    const end = start + itemsPerPage
    return filteredProducts.value.slice(start, end)
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredProducts.value.length / itemsPerPage)))

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const MathMax = Math.max;
    const MathMin = Math.min;
    
    let startPage = MathMax(1, current - 2);
    let endPage = MathMin(total, startPage + 4);
    
    if (endPage - startPage < 4) {
        startPage = MathMax(1, endPage - 4);
    }
    
    const pages = [];
    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }
    return pages;
})

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const applyFilters = async () => {
    // Filter by category on backend
    const params = {}
    if (selectedRAMs.value.length > 0) params.ram = selectedRAMs.value.join(',')
    if (selectedCPUs.value.length > 0) params.cpu = selectedCPUs.value.join(',')
    if (selectedGPUs.value.length > 0) params.gpu = selectedGPUs.value.join(',')
    if (selectedKichThuoc.value.length > 0) params.kichthuoc = selectedKichThuoc.value.join(',')
    if (selectedDoPhanGiai.value.length > 0) params.dophan = selectedDoPhanGiai.value.join(',')
    if (selectedTamNen.value.length > 0) params.tamnen = selectedTamNen.value.join(',')
    if (selectedPin.value.length > 0) params.pin = selectedPin.value.join(',')
    if (selectedSac.value.length > 0) params.sac = selectedSac.value.join(',')

    const res = await api.get('/sanpham', { params })
    const rawProducts = Array.isArray(res.data) ? res.data : (res.data.data || [])
    products.value = mapProducts(rawProducts)

    let result = [...products.value]

    // Filter by Category
    if (selectedCategories.value.length > 0) {
        result = result.filter(p => selectedCategories.value.includes(String(p.id_danhmuc)))
    }

    // Filter by Brand
    if (selectedBrands.value.length > 0) {
        result = result.filter(p => selectedBrands.value.includes(String(p.id_thuonghieu)))
    }

    // Filter by Price
    if (selectedPriceRange.value) {
        const pRange = String(selectedPriceRange.value)
        if (pRange === 'under20') {
            result = result.filter(p => p.priceNum < 20000000)
        } else if (pRange === '20to50') {
            result = result.filter(p => p.priceNum >= 20000000 && p.priceNum <= 50000000)
        } else if (pRange === 'above50') {
            result = result.filter(p => p.priceNum > 50000000)
        }
    }

    // Sorting
    const sValue = String(selectedSort.value)
    if (sValue === 'price_asc') {
        result.sort((a, b) => {
            if (a.priceNum !== b.priceNum) return a.priceNum - b.priceNum;
            return b.id - a.id;
        })
    } else if (sValue === 'price_desc') {
        result.sort((a, b) => {
            if (a.priceNum !== b.priceNum) return b.priceNum - a.priceNum;
            return b.id - a.id;
        })
    } else if (sValue === 'newest') {
        // Mặc định mapProducts đã trộn xen kẽ (interleave) dựa theo sản phẩm mới nhất
        // Nên giữ nguyên thứ tự ban đầu để đảm bảo trộn đều
    }

    filteredProducts.value = result
    currentPage.value = 1
}
watch(selectedSort, () => applyFilters())

// Format helpers
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
    <Header />
    <div class="page">
        <div class="container layout">

            <!-- SIDEBAR -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="4" y1="6" x2="20" y2="6" />
                        <line x1="8" y1="12" x2="16" y2="12" />
                        <line x1="11" y1="18" x2="13" y2="18" />
                    </svg>
                    <h3>Bộ lọc sản phẩm</h3>
                </div>

                <!-- DANH MỤC -->
                <div class="filter-group">
                    <div class="filter-group-header" @click="collapsed.danhmuc = !collapsed.danhmuc">
                        <p class="group-label">Danh mục</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.danhmuc }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.danhmuc }">
                        <div v-if="categories.length === 0" class="loading-small">Đang tải...</div>
                        <label class="check-label" v-for="c in categories" :key="c.id_danhmuc">
                            <input type="checkbox" :checked="selectedCategories.includes(String(c.id_danhmuc))" 
                                   @change="toggleList('cat', c.id_danhmuc)" />
                            <span class="checkmark"></span>
                            {{ c.ten_danhmuc }}
                        </label>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- THƯƠNG HIỆU -->
                <div class="filter-group">
                    <div class="filter-group-header" @click="collapsed.thuonghieu = !collapsed.thuonghieu">
                        <p class="group-label">Thương hiệu</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.thuonghieu }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.thuonghieu }">
                        <div v-if="brands.length === 0" class="loading-small">Đang tải...</div>
                        <div class="brands">
                            <span v-for="b in brands" :key="b.id_thuonghieu" class="brand-tag"
                                :class="{ active: selectedBrands.includes(String(b.id_thuonghieu)) }"
                                @click="toggleList('brand', b.id_thuonghieu)">
                                {{ b.ten_thuonghieu === 'Levono' ? 'Lenovo' : b.ten_thuonghieu }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- RAM -->
                <div class="filter-group" v-if="attrOptions.ram.length > 0">
                    <div class="filter-group-header" @click="collapsed.ram = !collapsed.ram">
                        <p class="group-label">RAM</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.ram }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.ram }">
                        <div class="attr-tags">
                            <span v-for="r in attrOptions.ram" :key="r" class="attr-tag"
                                :class="{ active: selectedRAMs.includes(r) }"
                                @click="toggleList('ram', r)">{{ r }}</span>
                        </div>
                    </div>
                </div>
                <div class="divider"></div>

                <!-- CPU -->
                <div class="filter-group" v-if="attrOptions.cpu.length > 0">
                    <div class="filter-group-header" @click="collapsed.cpu = !collapsed.cpu">
                        <p class="group-label">CPU</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.cpu }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.cpu }">
                        <div class="attr-tags">
                            <span v-for="r in attrOptions.cpu" :key="r" class="attr-tag"
                                :class="{ active: selectedCPUs.includes(r) }"
                                @click="toggleList('cpu', r)">{{ r }}</span>
                        </div>
                    </div>
                </div>
                <div class="divider" v-if="attrOptions.cpu.length > 0"></div>

                <!-- GPU -->
                <div class="filter-group" v-if="attrOptions.gpu.length > 0">
                    <div class="filter-group-header" @click="collapsed.gpu = !collapsed.gpu">
                        <p class="group-label">GPU</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.gpu }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.gpu }">
                        <div class="attr-tags">
                            <span v-for="r in attrOptions.gpu" :key="r" class="attr-tag"
                                :class="{ active: selectedGPUs.includes(r) }"
                                @click="toggleList('gpu', r)">{{ r }}</span>
                        </div>
                    </div>
                </div>
                <div class="divider" v-if="attrOptions.gpu.length > 0"></div>

                <!-- Kích thước -->
                <div class="filter-group" v-if="attrOptions.kichthuoc.length > 0">
                    <div class="filter-group-header" @click="collapsed.kichthuoc = !collapsed.kichthuoc">
                        <p class="group-label">Kích thước</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.kichthuoc }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.kichthuoc }">
                        <div class="attr-tags">
                            <span v-for="r in attrOptions.kichthuoc" :key="r" class="attr-tag"
                                :class="{ active: selectedKichThuoc.includes(r) }"
                                @click="toggleList('kichthuoc', r)">{{ r }}</span>
                        </div>
                    </div>
                </div>
                <div class="divider" v-if="attrOptions.kichthuoc.length > 0"></div>

                <!-- Độ phân giải -->
                <div class="filter-group" v-if="attrOptions.dophan.length > 0">
                    <div class="filter-group-header" @click="collapsed.dophan = !collapsed.dophan">
                        <p class="group-label">Độ phân giải</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.dophan }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.dophan }">
                        <div class="attr-tags">
                            <span v-for="r in attrOptions.dophan" :key="r" class="attr-tag"
                                :class="{ active: selectedDoPhanGiai.includes(r) }"
                                @click="toggleList('dophan', r)">{{ r }}</span>
                        </div>
                    </div>
                </div>
                <div class="divider" v-if="attrOptions.dophan.length > 0"></div>

                <!-- Tấm nền -->
                <div class="filter-group" v-if="attrOptions.tamnen.length > 0">
                    <div class="filter-group-header" @click="collapsed.tamnen = !collapsed.tamnen">
                        <p class="group-label">Tấm nền</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.tamnen }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.tamnen }">
                        <div class="attr-tags">
                            <span v-for="r in attrOptions.tamnen" :key="r" class="attr-tag"
                                :class="{ active: selectedTamNen.includes(r) }"
                                @click="toggleList('tamnen', r)">{{ r }}</span>
                        </div>
                    </div>
                </div>
                <div class="divider" v-if="attrOptions.tamnen.length > 0"></div>

                <!-- Pin -->
                <div class="filter-group" v-if="attrOptions.pin.length > 0">
                    <div class="filter-group-header" @click="collapsed.pin = !collapsed.pin">
                        <p class="group-label">PIN</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.pin }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.pin }">
                        <div class="attr-tags">
                            <span v-for="r in attrOptions.pin" :key="r" class="attr-tag"
                                :class="{ active: selectedPin.includes(r) }"
                                @click="toggleList('pin', r)">{{ r }}</span>
                        </div>
                    </div>
                </div>
                <div class="divider" v-if="attrOptions.pin.length > 0"></div>

                <!-- Sạc -->
                <div class="filter-group" v-if="attrOptions.sac.length > 0">
                    <div class="filter-group-header" @click="collapsed.sac = !collapsed.sac">
                        <p class="group-label">SẠC</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.sac }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.sac }">
                        <div class="attr-tags">
                            <span v-for="r in attrOptions.sac" :key="r" class="attr-tag"
                                :class="{ active: selectedSac.includes(r) }"
                                @click="toggleList('sac', r)">{{ r }}</span>
                        </div>
                    </div>
                </div>
                <div class="divider" v-if="attrOptions.sac.length > 0"></div>

                <!-- Lọc Theo Giá -->
                <div class="filter-group">
                    <div class="filter-group-header" @click="collapsed.gia = !collapsed.gia">
                        <p class="group-label">Mức giá</p>
                        <svg class="arrow-icon" :class="{ rotated: collapsed.gia }" viewBox="0 0 20 20" fill="none">
                            <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="collapse-body" :class="{ closed: collapsed.gia }">
                        <label class="check-label">
                            <input type="radio" name="price" value="" v-model="selectedPriceRange" />
                            <span class="checkmark radio"></span> Tất cả
                        </label>
                        <label class="check-label">
                            <input type="radio" name="price" value="under20" v-model="selectedPriceRange" />
                            <span class="checkmark radio"></span> Dưới 20.000.000đ
                        </label>
                        <label class="check-label">
                            <input type="radio" name="price" value="20to50" v-model="selectedPriceRange" />
                            <span class="checkmark radio"></span> Từ 20 - 50.000.000đ
                        </label>
                        <label class="check-label">
                            <input type="radio" name="price" value="above50" v-model="selectedPriceRange" />
                            <span class="checkmark radio"></span> Trên 50.000.000đ
                        </label>
                    </div>
                </div>

                <div class="divider"></div>

                <button class="apply-btn" @click="applyFilters">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    Áp dụng bộ lọc
                </button>

                <button class="reset-link" @click="clearAll">
                    Xóa tất cả bộ lọc
                </button>
            </aside>

            <!-- CONTENT -->
            <main class="content">

                <div class="top-bar">
                    <div>
                        <h1>Danh sách Laptop</h1>
                        <p v-if="!isLoading">Tìm thấy <b>{{ filteredProducts.length }}</b> sản phẩm phù hợp</p>
                        <p v-else>Đang tìm kiếm sản phẩm...</p>
                    </div>
                </div>

                <!-- LOADING STATE -->
                <div v-if="isLoading" class="loading-grid">
                    <div class="skeleton-card" v-for="i in 6" :key="i"></div>
                </div>

                <!-- GRID -->
                <div class="grid" v-else-if="displayedProducts.length > 0">
                    <div class="card" v-for="p in displayedProducts" :key="p.key_id">

                        <span v-if="p.badge" class="badge" :style="{ background: p.badgeColor }">{{ p.badge }}</span>

                        <div class="img-box" @click="router.push(`/products/${p.id}`)">
                            <img :src="p.img" :alt="p.name" />
                        </div>

                        <div class="card-body">
                            <h3 @click="router.push(`/products/${p.id}`)">{{ p.name }}</h3>
                            <p class="brand-txt">{{ p.brandName }} {{ p.weight ? '· ' + p.weight + 'kg' : '' }}</p>

                            <div class="price-row">
                                <span class="price" v-if="p.priceNum > 0">{{ formatPrice(p.priceNum) }}</span>
                                <span class="price" v-else>Liên hệ</span>
                                <span v-if="p.oldPriceNum > p.priceNum" class="old-price">{{ formatPrice(p.oldPriceNum) }}</span>
                            </div>

                            <div class="card-actions">
                                <router-link :to="`/products/${p.id}`" class="btn-detail">
                                    Chi tiết
                                </router-link>

                                <button class="btn-cart" title="Thêm vào giỏ hàng">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                        stroke-linecap="round">
                                        <circle cx="9" cy="21" r="1" />
                                        <circle cx="20" cy="21" r="1" />
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
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
                        &laquo;
                    </button>
                    <button 
                        v-for="page in visiblePages" 
                        :key="page"
                        class="pg-btn" 
                        :class="{ active: currentPage === page }"
                        @click="changePage(page)">
                        {{ page }}
                    </button>
                    <button class="pg-btn" :disabled="currentPage === totalPages" @click="changePage(currentPage + 1)">
                        &raquo;
                    </button>
                </div>

            </main>
        </div>
    </div>
    <Footer />
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

* { box-sizing: border-box; }

.page {
    font-family: 'Inter', sans-serif;
    background: #f8fafc;
    padding: 32px 0 60px;
    min-height: 100vh;
}

.container { width: min(1200px, 95%); margin: auto; }
.layout { display: flex; gap: 24px; align-items: flex-start; }

/* ===== SIDEBAR ===== */
.sidebar {
    width: 248px;
    flex-shrink: 0;
    background: white;
    border-radius: 18px;
    border: 1px solid #f1f5f9;
    padding: 22px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    position: sticky;
    top: 20px;
}

.sidebar-header { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
.sidebar-header svg { width: 16px; height: 16px; color: #2563eb; }
.sidebar-header h3 { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }

.divider { height: 1px; background: #f1f5f9; margin: 16px 0; }

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
.filter-group-header:hover { background: #f8fafc; }

.arrow-icon {
    width: 16px;
    height: 16px;
    color: #94a3b8;
    transition: transform 0.25s ease;
    flex-shrink: 0;
}
.arrow-icon.rotated { transform: rotate(-90deg); }

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

.check-label input { display: none; }

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

.check-label input:checked~.checkmark { background: #2563eb; border-color: #2563eb; }
.check-label input:checked~.checkmark::after {
    content: '';
    width: 8px;
    height: 5px;
    border-left: 2px solid white;
    border-bottom: 2px solid white;
    transform: rotate(-45deg) translate(1px, -1px);
    display: block;
}

.brands { display: flex; flex-wrap: wrap; gap: 6px; }

.brand-tag {
    padding: 5px 12px;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    font-size: 12px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
}

.brand-tag:hover { border-color: #2563eb; color: #2563eb; background: #f0f6ff; }
.brand-tag.active { background: linear-gradient(135deg, #2563eb, #4f46e5); color: white; border-color: transparent; }

.attr-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
.attr-tag {
    padding: 5px 14px; border-radius: 20px; border: 1px solid #e2e8f0;
    font-size: 12px; font-weight: 600; color: #475569; cursor: pointer;
    background: white; transition: all 0.2s;
}
.attr-tag:hover { border-color: #2563eb; color: #2563eb; background: #f0f6ff; }
.attr-tag.active { background: linear-gradient(135deg, #2563eb, #4f46e5); color: white; border-color: transparent; }


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

.apply-btn:hover { opacity: 0.9; transform: translateY(-1px); }

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
.content { flex: 1; min-width: 0; }

.top-bar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
.top-bar h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 4px; }
.top-bar p { font-size: 13px; color: #94a3b8; margin: 0; }
.top-bar b { color: #2563eb; }

.sort-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
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

.chevron { width: 12px; height: 12px; color: #94a3b8; }

/* GRID */
.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }

.card {
    background: white;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    overflow: hidden;
    position: relative;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}

.card:hover { transform: translateY(-5px); box-shadow: 0 14px 36px rgba(0, 0, 0, 0.1); }

.badge {
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

.img-box { background: #f8fafc; padding: 14px; cursor: pointer; }
.img-box img { width: 100%; height: 148px; object-fit: cover; border-radius: 10px; }

.card-body { padding: 13px 15px 15px; }
.card-body h3 { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 4px; cursor: pointer; }
.brand-txt { font-size: 11px; color: #94a3b8; margin: 0 0 10px; }

.price-row { display: flex; align-items: baseline; gap: 8px; margin-bottom: 12px; }
.price { font-size: 15px; font-weight: 800; color: #2563eb; }
.old-price { font-size: 11px; color: #cbd5e1; text-decoration: line-through; }

.card-actions { display: flex; gap: 8px; }

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
}

.btn-detail:hover { background: #2563eb; color: white; }

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
}

/* SKELETON & LOADING */
.loading-small { font-size: 12px; color: #94a3b8; padding: 5px 0; }
.loading-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; width: 100%; }
.skeleton-card { height: 320px; background: linear-gradient(90deg, #f1f5f9 25%, #f8fafc 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 16px; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* RADIO CUSTOM */
.checkmark.radio { border-radius: 50%; }
.check-label input:checked ~ .checkmark.radio::after { content: ''; width: 8px; height: 8px; background: white; border-radius: 50%; border: none; transform: none; }

/* EMPTY STATE */
.empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 20px; border: 1px dashed #e2e8f0; grid-column: 1 / -1; }
.empty-state h3 { font-size: 18px; color: #0f172a; margin: 0 0 8px; }
.empty-state button { padding: 10px 20px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; cursor: pointer; }

/* PAGINATION */
.pagination { display: flex; justify-content: center; align-items: center; gap: 5px; margin-top: 28px; }
.pg-btn { width: 36px; height: 36px; border-radius: 9px; border: 1px solid #e2e8f0; background: white; font-size: 13px; font-weight: 500; color: #334155; cursor: pointer; }
.pg-btn.active { background: linear-gradient(135deg, #2563eb, #4f46e5); color: white; border-color: transparent; }
.pg-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* RESPONSIVE */
@media (max-width: 900px) { .grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) {
    .layout { flex-direction: column; }
    .sidebar { width: 100%; position: static; }
    .grid { grid-template-columns: repeat(2, 1fr); }
    .top-bar { flex-direction: column; align-items: flex-start; gap: 12px; }
}
</style>