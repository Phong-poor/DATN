<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { prefetchProductsPage, getPrefetchedProductsData } from '@/services/productsPrefetch'
import { storageUrl } from '@/services/urls'
import { getToken } from '@/services/auth'

const router = useRouter()
const route = useRoute()

// ===================== DỮ LIỆU Äá»˜NG & ÄỒNG Bá»˜ BACKEND =====================
const products = ref([])
const categories = ref([])
const brands = ref([])
const isLoading = ref(true)

// Dữ liệu dự phòng mẫu cao cấp (High-fidelity Fallback Data)
const fallbackProducts = [
  {
    id_sanpham: 101,
    tenSP: 'Laptop ASUS ROG Zephyrus G14 GA402RJ',
    brand: 'ASUS',
    category: 'Laptop Gaming',
    gia: 39990000,
    oldPrice: 45990000,
    specs: ['Ryzen 9', 'RTX 4060', '16GB RAM', '1TB SSD'],
    image: 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=600&q=80',
    rating: 4.8,
    reviews: 98,
    promo: 'Tặng kèm Balo ROG + Chuột Gaming',
    inStock: true
  },
  {
    id_sanpham: 102,
    tenSP: 'MacBook Pro 14 inch M3 2023',
    brand: 'Apple',
    category: 'MacBook',
    gia: 52990000,
    oldPrice: 59990000,
    specs: ['Apple M3', '16GB RAM', '1TB SSD', 'Liquid Retina'],
    image: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=600&q=80',
    rating: 4.9,
    reviews: 124,
    promo: 'Ưu đãi trả góp 0% qua Home Credit',
    inStock: true
  },
  {
    id_sanpham: 103,
    tenSP: 'Laptop Dell Alienware x16 R2 Flagship',
    brand: 'Dell',
    category: 'Laptop Gaming',
    gia: 102900000,
    oldPrice: 115000000,
    specs: ['Core i9', 'RTX 4090', '32GB RAM', '240Hz OLED'],
    image: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=600&q=80',
    rating: 5.0,
    reviews: 57,
    promo: 'Tặng Chuột Alienware AW610M + Balo',
    inStock: true
  },
  {
    id_sanpham: 104,
    tenSP: 'MacBook Air 15 inch M2 2022',
    brand: 'Apple',
    category: 'MacBook',
    gia: 35990000,
    oldPrice: 39990000,
    specs: ['Apple M2', '16GB RAM', '512GB SSD', 'Fanless Design'],
    image: 'https://images.unsplash.com/photo-1527430253228-e93688616381?auto=format&fit=crop&w=600&q=80',
    rating: 4.7,
    reviews: 64,
    promo: 'Tặng cáp chuyển đổi Multi-port USB-C',
    inStock: true
  },
  {
    id_sanpham: 105,
    tenSP: 'Laptop Lenovo Legion Slim 7 16IRH8',
    brand: 'Lenovo',
    category: 'Laptop Gaming',
    gia: 57990000,
    oldPrice: 63990000,
    specs: ['Core i7', 'RTX 4070', '16GB RAM', '1TB SSD'],
    image: 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?auto=format&fit=crop&w=600&q=80',
    rating: 4.8,
    reviews: 79,
    promo: 'Tặng kèm tai nghe Legion H200',
    inStock: true
  },
  {
    id_sanpham: 106,
    tenSP: 'Laptop MSI Stealth 16 Studio A13VG',
    brand: 'MSI',
    category: 'Laptop Gaming',
    gia: 72990000,
    oldPrice: 79990000,
    specs: ['Core i9', 'RTX 4080', '32GB RAM', '1TB SSD'],
    image: 'https://images.unsplash.com/photo-1555617117-08c39bb051aa?auto=format&fit=crop&w=600&q=80',
    rating: 4.9,
    reviews: 42,
    promo: 'Tặng chuột MSI Dual Mode + Balo Stealth',
    inStock: true
  }
]

// ===================== KHỞI TẠO Bá»˜ LỌC =====================
const activeCategory = ref('Tất cả')
const selectedBrands = ref([])
const minPrice = ref(0)
const maxPrice = ref(150000000)
const selectedRams = ref([])
const selectedSsds = ref([])
const selectedCpus = ref([])
const selectedGpus = ref([])
const selectedScreens = ref([])
const selectedHzs = ref([])
const activeSort = ref('best_sellers')
const searchQuery = ref('')
const PRODUCTS_PER_PAGE = 16
const currentPage = ref(1)

const categoryAliasMap = {
  gaming: 'Laptop Gaming',
  'laptop gaming': 'Laptop Gaming',
  macbook: 'MacBook',
  workstation: 'Workstation',
  'laptop văn phòng': 'Laptop Văn phòng',
  'laptop van phong': 'Laptop Văn phòng',
  office: 'Laptop Văn phòng',
  student: 'Laptop Học sinh',
  'laptop học sinh': 'Laptop Học sinh',
  'tat ca': 'Tất cả',
  'tất cả': 'Tất cả',
}

const normalizeCategoryQuery = (category) => {
  if (!category) return 'Tất cả'
  const value = String(category).trim()
  return categoryAliasMap[value.toLowerCase()] || value
}

const applyRouteFilters = (query = route.query) => {
  activeCategory.value = normalizeCategoryQuery(query.category)
  searchQuery.value = query.q ? String(query.q) : ''
}

const toggleBrand = (brand) => {
  const idx = selectedBrands.value.indexOf(brand)
  if (idx > -1) {
    selectedBrands.value.splice(idx, 1)
  } else {
    selectedBrands.value.push(brand)
  }
}

const toggleRam = (ram) => {
  const idx = selectedRams.value.indexOf(ram)
  if (idx > -1) {
    selectedRams.value.splice(idx, 1)
  } else {
    selectedRams.value.push(ram)
  }
}

const toggleSsd = (ssd) => {
  const idx = selectedSsds.value.indexOf(ssd)
  if (idx > -1) {
    selectedSsds.value.splice(idx, 1)
  } else {
    selectedSsds.value.push(ssd)
  }
}

const toggleCpu = (cpu) => {
  const idx = selectedCpus.value.indexOf(cpu)
  idx > -1 ? selectedCpus.value.splice(idx, 1) : selectedCpus.value.push(cpu)
}
const toggleGpu = (gpu) => {
  const idx = selectedGpus.value.indexOf(gpu)
  idx > -1 ? selectedGpus.value.splice(idx, 1) : selectedGpus.value.push(gpu)
}
const toggleScreen = (screen) => {
  const idx = selectedScreens.value.indexOf(screen)
  idx > -1 ? selectedScreens.value.splice(idx, 1) : selectedScreens.value.push(screen)
}
const toggleHz = (hz) => {
  const idx = selectedHzs.value.indexOf(hz)
  idx > -1 ? selectedHzs.value.splice(idx, 1) : selectedHzs.value.push(hz)
}

const clearAllFilters = () => {
  selectedBrands.value = []
  minPrice.value = 0
  maxPrice.value = 150000000
  selectedRams.value = []
  selectedSsds.value = []
  selectedCpus.value = []
  selectedGpus.value = []
  selectedScreens.value = []
  selectedHzs.value = []
  searchQuery.value = ''
  activeCategory.value = 'Tất cả'
  router.replace({ path: '/products' })
}

watch(minPrice, (newMin) => {
  if (newMin > maxPrice.value - 5000000) {
    minPrice.value = maxPrice.value - 5000000
  }
})
watch(maxPrice, (newMax) => {
  if (newMax < minPrice.value + 5000000) {
    maxPrice.value = minPrice.value + 5000000
  }
})

const filterOptions = {
  categories: ['Tất cả', 'Laptop Gaming', 'Workstation', 'MacBook', 'Laptop Văn phòng', 'Laptop Học sinh'],
  brands: ['Apple', 'ASUS', 'Lenovo', 'Dell', 'MSI', 'Acer', 'HP', 'Gigabyte'],
  rams: ['8GB', '16GB', '32GB', '64GB'],
  ssds: ['256GB', '512GB', '1TB', '2TB'],
  cpus: ['Core i9', 'Core i7', 'Core i5', 'Ryzen 9', 'Ryzen 7', 'Apple M3', 'Apple M2'],
  gpus: ['RTX 4090', 'RTX 4080', 'RTX 4070', 'RTX 4060', 'RTX 4050'],
  screens: ['13.3 inch', '14 inch', '15.6 inch', '16 inch', '17.3 inch'],
  hzs: ['60Hz', '120Hz', '144Hz', '165Hz', '240Hz']
}

// ===================== DỮ LIỆU Äá»˜NG CỦA BẢN MẪU GIAO DIỆN =====================
// Dải thương hiệu logo hiển thị
const brandLogos = [
  { name: 'ASUS', logo: '/ASUS_Logo.svg.png' },
  { name: 'HP', logo: '/HP_logo_2012.svg.png' },
  { name: 'Lenovo', logo: '/Lenovo_logo_2015.svg.png' },
  { name: 'Dell', logo: '/Dell_Logo.svg.png' },
  { name: 'Apple', logo: '/Apple_logo_black.svg.png' },
  { name: 'Acer', logo: '/Acer_2011.svg.png' }
]

const heroBannerImages = [
  '/Gemini_Generated_Image_j1cibhj1cibhj1ci.png'
]

const premiumBadges = ['ĐẮT NHẤT', 'FLAGSHIP', 'ELITE', 'PRO MAX', 'ULTRA']
const premiumTags = ['TOP DB', 'CẤU HÌNH THẬT', 'MUA NGAY', 'HIGH-END', 'PREMIUM']
const premiumPriceThreshold = 60000000

const sortedPremiumSource = computed(() => {
  const source = products.value.length ? products.value : fallbackProducts
  return source
    .filter(product => Number(product.gia) > 0)
    .slice()
    .sort((a, b) => Number(b.gia) - Number(a.gia))
})

const premiumProductsOverThreshold = computed(() => (
  sortedPremiumSource.value.filter(product => Number(product.gia) >= premiumPriceThreshold)
))

const isUsingPremiumFallback = computed(() => (
  products.value.length > 0 &&
  premiumProductsOverThreshold.value.length === 0 &&
  sortedPremiumSource.value.length > 0
))

const flashSaleCarousel = computed(() => {
  const source = premiumProductsOverThreshold.value.length
    ? premiumProductsOverThreshold.value
    : sortedPremiumSource.value

  return source
    .slice(0, 10)
    .map((product, index) => ({
      id_sanpham: product.id_sanpham,
      id_bienthe: product.id_bienthe,
      name: product.tenSP,
      price: Number(product.gia),
      badge: premiumBadges[index] || 'PREMIUM',
      tag: premiumTags[index] || product.brand,
      highlight: product.promo || `Biến thể cao cấp nhất hiện có trong database của ${product.brand}.`,
      specs: product.specs?.length ? product.specs.slice(0, 4) : ['Cấu hình cao cấp'],
      image: product.image,
      inStock: product.inStock,
      variantName: product.variantName,
      brand: product.brand,
      category: product.category
    }))
})

const currentFlashSaleIndex = ref(0)
const flashSaleVisibleCount = 5
const flashSaleViewportRef = ref(null)
const flashSaleViewportWidth = ref(0)
const flashSaleMaxIndex = computed(() => Math.max(0, flashSaleCarousel.value.length - flashSaleVisibleCount))
const flashSaleGap = 12
const flashSaleStep = computed(() => {
  if (!flashSaleViewportWidth.value) return 0
  return ((flashSaleViewportWidth.value - flashSaleGap * (flashSaleVisibleCount - 1)) / flashSaleVisibleCount) + flashSaleGap
})
const flashSaleTrackStyle = computed(() => ({
  '--flash-card-width': flashSaleViewportWidth.value
    ? `${(flashSaleViewportWidth.value - flashSaleGap * (flashSaleVisibleCount - 1)) / flashSaleVisibleCount}px`
    : 'calc((100% - 48px) / 5)',
  '--flash-x': `${-(currentFlashSaleIndex.value * flashSaleStep.value) + flashDragOffset.value}px`
}))

const updateFlashSaleViewport = () => {
  flashSaleViewportWidth.value = flashSaleViewportRef.value?.clientWidth || 0
}

const nextFlashSale = () => {
  currentFlashSaleIndex.value =
    currentFlashSaleIndex.value >= flashSaleMaxIndex.value ? 0 : currentFlashSaleIndex.value + 1
}

const prevFlashSale = () => {
  currentFlashSaleIndex.value =
    currentFlashSaleIndex.value <= 0 ? flashSaleMaxIndex.value : currentFlashSaleIndex.value - 1
}

const isFlashDragging = ref(false)
const flashDragStartX = ref(0)
const flashDragOffset = ref(0)
let flashWheelLocked = false

const startFlashDrag = (event) => {
  if (event.target?.closest?.('button, a, input, select, textarea, .flash-actions')) return
  isFlashDragging.value = true
  flashDragStartX.value = event.clientX
  flashDragOffset.value = 0
  event.currentTarget?.setPointerCapture?.(event.pointerId)
}

const moveFlashDrag = (event) => {
  if (!isFlashDragging.value) return
  flashDragOffset.value = event.clientX - flashDragStartX.value
  if (Math.abs(flashDragOffset.value) > 8) event.preventDefault()
}

const endFlashDrag = () => {
  if (!isFlashDragging.value) return
  const threshold = 58
  if (flashDragOffset.value <= -threshold) nextFlashSale()
  if (flashDragOffset.value >= threshold) prevFlashSale()
  isFlashDragging.value = false
  flashDragOffset.value = 0
}

const handleFlashWheel = (event) => {
  if (flashWheelLocked) return
  const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY
  if (Math.abs(delta) < 24) return
  delta > 0 ? nextFlashSale() : prevFlashSale()
  flashWheelLocked = true
  window.setTimeout(() => { flashWheelLocked = false }, 520)
}

// ===================== GÓC NHÌN CHI TIẾT (INTERACTIVE SECTION) =====================
const interactiveAngles = [
  { label: 'Góc nghiêng 45 độ', img: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1000&q=80' },
  { label: 'Bàn phím & Touchpad', img: 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=1000&q=80' },
  { label: 'Mặt sau máy', img: 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?auto=format&fit=crop&w=1000&q=80' },
  { label: 'Khung máy góc rộng', img: 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?auto=format&fit=crop&w=1000&q=80' }
]
const activeAngleIndex = ref(0)

const nextAngle = () => {
  activeAngleIndex.value = (activeAngleIndex.value + 1) % interactiveAngles.length
}
const prevAngle = () => {
  activeAngleIndex.value = (activeAngleIndex.value - 1 + interactiveAngles.length) % interactiveAngles.length
}

// Services pills row
const servicesList = [
  { title: 'Giao hàng hỏa tốc 2H', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>`, desc: 'Nhận hàng siêu tốc trong 2 giờ nội thành' },
  { title: 'Trả góp 0% linh hoạt', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>`, desc: 'Thủ tục đơn giản qua thẻ hoặc hồ sơ' },
  { title: 'Bảo hành 24/7 toàn quốc', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`, desc: 'Hỗ trợ kỹ thuật chuyên nghiệp trọn đời' },
  { title: 'Đổi trả trong 30 ngày', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>`, desc: 'Yên tâm mua sắm, đổi trả miễn phí dễ dàng' }
]

// Showroom highlights
const showroomHighlights = [
  { text: 'Trải nghiệm trực quan', desc: 'Đầy đủ các dòng máy cao cấp sẵn sàng trải nghiệm thực tế.', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" /></svg>` },
  { text: 'Hệ thống demo đỉnh cao', desc: 'Setup ánh sáng neon Cyberpunk sống động, chuyên nghiệp.', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A5 5 0 0 0 8 8c0 1 .3 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>` },
  { text: 'Kỹ thuật viên tư vấn 1-1', desc: 'Đội ngũ kỹ sư hỗ trợ chuyên nghiệp giải đáp mọi thắc mắc.', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 11v1"/><path d="M19 16v1"/><path d="m20.5 12.5-.866-.5"/><path d="M17.5 14.5l-.866-.5"/><path d="m20.5 15.5-.866.5"/><path d="m17.5 13.5-.866.5"/></svg>` }
]

// ===================== DỮ LIỆU ÄỒNG Bá»˜ VÃ€ XỬ LÝ LỌC =====================
const loadData = async () => {
  isLoading.value = true
  try {
    // Prefetch dữ liệu API
    const cache = await prefetchProductsPage()
    if (cache && cache.productsRaw && cache.productsRaw.length > 0) {
      // Map dữ liệu từ backend sang cấu trúc giao diện đẹp
      products.value = cache.productsRaw.map(p => {
        let generalSpecs = []
        try {
          const tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || [])
          if (Array.isArray(tskt)) {
            generalSpecs = tskt.map(item => item.giatri).filter(Boolean)
          }
        } catch (e) {
          console.warn('Lỗi parse thông số kỹ thuật', e)
        }

        const variants = Array.isArray(p.bien_thes) ? p.bien_thes : []
        const premiumVariant = variants.length
          ? variants.slice().sort((a, b) => Number(b.gia || 0) - Number(a.gia || 0))[0]
          : null

        // Lấy giá và cấu hình từ biến thể đắt nhất để section flagship phản ánh dữ liệu thật trong database.
        const giaSP = Number(premiumVariant?.gia || p.gia || 25000000)
        const imagePath = premiumVariant?.hinhanh || p.hinhanh

        // Extract RAM & SSD từ biến thể hoặc thông số kỹ thuật
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
          category: p.danh_muc?.ten_danhmuc || p.danhmuc?.tenDM || p.category || 'Laptop',
          gia: giaSP,
          oldPrice: Math.floor(giaSP * 1.15),
          specs: variantSpecs.length > 0 ? variantSpecs.slice(0, 4) : (generalSpecs.length > 0 ? generalSpecs.slice(0, 4) : [ram, ssd, 'IPS FHD']),
          image: imagePath ? (imagePath.startsWith('http') ? imagePath : storageUrl(imagePath)) : 'https://via.placeholder.com/600',
          rating: 4.8,
          reviews: Math.floor(Math.random() * 80) + 15,
          promo: p.mota_ngan || 'Tặng kèm Balo cao cấp + Chuột Wireless',
          inStock: p.trangthai === 'hoat_dong' || Number(premiumVariant?.soluong || 0) > 0,
          ram,
          ssd
        }
      })
    } else {
      products.value = [...fallbackProducts]
    }
  } catch (err) {
    console.error('Lỗi khi tải sản phẩm từ backend:', err)
    products.value = [...fallbackProducts]
  } finally {
    isLoading.value = false
  }
}

// Xử lý bộ lọc sản phẩm động (Filtered Catalog)
const filteredProducts = computed(() => {
  let list = products.value.filter(product => {
    // 1. Lọc theo danh mục nút tròn (activeCategory)
    const matchCategory = activeCategory.value === 'Tất cả' || 
                          product.category.toLowerCase().includes(activeCategory.value.toLowerCase()) ||
                          (activeCategory.value === 'MacBook' && product.brand.toLowerCase() === 'apple')

    // 2. Lọc theo thương hiệu (selectedBrands)
    const matchBrand = selectedBrands.value.length === 0 || 
                       selectedBrands.value.some(b => product.brand.toLowerCase() === b.toLowerCase())

    // 3. Lọc theo khoảng giá (minPrice - maxPrice slider)
    const matchPrice = product.gia >= minPrice.value && product.gia <= maxPrice.value

    // 4. Lọc theo RAM (selectedRams)
    const matchRam = selectedRams.value.length === 0 || selectedRams.value.some(ram => {
      return (product.ram && product.ram.toLowerCase().includes(ram.toLowerCase())) ||
             product.specs.some(spec => spec.toLowerCase().includes(ram.toLowerCase()))
    })

    // 5. Lọc theo SSD (selectedSsds)
    const matchSsd = selectedSsds.value.length === 0 || selectedSsds.value.some(ssd => {
      return (product.ssd && product.ssd.toLowerCase().includes(ssd.toLowerCase())) ||
             product.specs.some(spec => spec.toLowerCase().includes(ssd.toLowerCase()))
    })

    // 6. Lọc theo CPU
    const matchCpu = selectedCpus.value.length === 0 || selectedCpus.value.some(cpu => {
      return product.specs.some(spec => spec.toLowerCase().includes(cpu.toLowerCase()))
    })

    // 7. Lọc theo GPU
    const matchGpu = selectedGpus.value.length === 0 || selectedGpus.value.some(gpu => {
      return product.specs.some(spec => spec.toLowerCase().includes(gpu.toLowerCase()))
    })

    // 8. Lọc theo Màn hình
    const matchScreen = selectedScreens.value.length === 0 || selectedScreens.value.some(scr => {
      return product.specs.some(spec => spec.toLowerCase().includes(scr.toLowerCase()))
    })

    // 9. Lọc theo Hz
    const matchHz = selectedHzs.value.length === 0 || selectedHzs.value.some(hz => {
      return product.specs.some(spec => spec.toLowerCase().includes(hz.toLowerCase()))
    })

    // 11. Lọc theo tìm kiếm (searchQuery)
    const matchSearch = !searchQuery.value || 
                        product.tenSP.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                        product.brand.toLowerCase().includes(searchQuery.value.toLowerCase())

    return matchCategory && matchBrand && matchPrice && matchRam && matchSsd && matchCpu && matchGpu && matchScreen && matchHz && matchSearch
  })

  // SẮP XẾP (SORTING)
  if (activeSort.value === 'price_asc') {
    list.sort((a, b) => a.gia - b.gia)
  } else if (activeSort.value === 'price_desc') {
    list.sort((a, b) => b.gia - a.gia)
  } else if (activeSort.value === 'best_sellers') {
    list.sort((a, b) => b.reviews - a.reviews)
  } else if (activeSort.value === 'newest') {
    list.sort((a, b) => b.id_sanpham - a.id_sanpham)
  } else if (activeSort.value === 'promotions') {
    list.sort((a, b) => (b.oldPrice - b.gia) - (a.oldPrice - a.gia))
  }

  return list
})

const totalCatalogPages = computed(() => Math.max(1, Math.ceil(filteredProducts.value.length / PRODUCTS_PER_PAGE)))

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * PRODUCTS_PER_PAGE
  return filteredProducts.value.slice(start, start + PRODUCTS_PER_PAGE)
})

const visibleCatalogPages = computed(() => {
  const total = totalCatalogPages.value
  const current = currentPage.value
  if (total <= 5) return Array.from({ length: total }, (_, index) => index + 1)

  const pages = [1]
  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)

  if (start > 2) pages.push('...')
  for (let page = start; page <= end; page++) pages.push(page)
  if (end < total - 1) pages.push('...')
  pages.push(total)

  return pages
})

const goToCatalogPage = (page) => {
  if (page === '...' || page < 1 || page > totalCatalogPages.value || page === currentPage.value) return
  currentPage.value = page
  document.getElementById('catalog-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

watch(filteredProducts, () => {
  currentPage.value = 1
})
const formatPrice = (value) => new Intl.NumberFormat('vi-VN').format(value) + '₫'

const selectQuickBrand = (brandName) => {
  const index = selectedBrands.value.indexOf(brandName)
  if (index === -1) {
    selectedBrands.value.push(brandName)
  } else {
    selectedBrands.value.splice(index, 1)
  }
}

const isQuickBrandActive = (brandName) => {
  return selectedBrands.value.includes(brandName)
}

const selectCategory = (category) => {
  activeCategory.value = category
  const nextQuery = { ...route.query }
  if (category === 'Tất cả') {
    delete nextQuery.category
  } else {
    nextQuery.category = category
  }
  router.replace({ path: '/products', query: nextQuery })
}

const goToSection = async (sectionId) => {
  await router.push({ path: '/products', query: route.query, hash: `#${sectionId}` })
  window.setTimeout(() => {
    document.getElementById(sectionId)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 180)
}

const goToPremiumProduct = (product) => {
  if (product?.id_sanpham) {
    router.push({
      path: `/products/${product.id_sanpham}`,
      query: product.id_bienthe ? { variant: product.id_bienthe } : {}
    })
    return
  }
  router.push({ path: '/products', query: { q: product?.name || '' } })
}

// Chuyển sang chi tiết sản phẩm
const viewDetail = (id) => {
  router.push(`/products/${id}`)
}

const getSwal = async () => {
  const module = await import('@/services/swal')
  return module.default
}

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

const buyPremiumProduct = (product) => {
  addToCart(product, {
    redirectTo: (cartItem, variantId) => ({
      path: '/checkout',
      query: {
        buy_now: '1',
        variant: variantId,
        cart_item: cartItem?.id_giohang
      }
    }),
    buyNow: true,
    silent: true
  })
}

const toggleWishlist = async (product) => {
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

    swal.toast('Đã thêm vào danh sách yêu thích!', 'success')
    window.dispatchEvent(new Event('wishlist-updated'))
  } catch (err) {
    console.error('Lỗi yêu thích:', err)
    swal.info('Thông báo', err.response?.data?.message || 'Đã xảy ra sự cố.')
  }
}


onMounted(() => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
  applyRouteFilters()
  loadData()
  requestAnimationFrame(updateFlashSaleViewport)
  window.addEventListener('resize', updateFlashSaleViewport)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateFlashSaleViewport)
})

watch(() => route.query, (newQuery) => {
  applyRouteFilters(newQuery)
})
</script>

<template>
  <div class="premium-page-shell">
    
    <!-- ===================== HERO VIEWPORT ===================== -->
    <section
      class="hero-banner"
      :style="{
        '--hero-bg-1': `url('${heroBannerImages[0]}')`,
        '--hero-bg-2': `url('${heroBannerImages[0]}')`
      }"
    >
      <div class="hero-copy">
        <span class="eyebrow-badge">
          <svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12" style="display: inline-block; vertical-align: middle; margin-right: 4px; color: #f59e0b;">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
          </svg>
          Predator Flagship
        </span>
        <h1>Hiệu năng tối ưu cho game AAA & đồ họa 3D</h1>
        <p>Khám phá bộ sưu tập cấu hình vượt giới hạn hiệu năng, tối ưu hóa hệ thống tản nhiệt thế hệ mới cho trải nghiệm gaming hoàn mỹ.</p>
        <div class="hero-actions">
          <button class="btn-glow" @click="goToSection('catalog-section')">Chọn theo nhu cầu</button>
          <button class="btn-outline" @click="goToSection('showroom-section')">Tham quan showroom</button>
        </div>
        
        <div class="hero-specs-grid">
          <div class="spec-stat-card">
            <span class="spec-stat-title">RTX 40 Series</span>
            <span class="spec-stat-desc">Đồ họa Ray-Tracing siêu thực</span>
          </div>
          <div class="spec-stat-card">
            <span class="spec-stat-title">M3 / Core i9</span>
            <span class="spec-stat-desc">Băng thông xử lý đa nhiệm vượt trội</span>
          </div>
          <div class="spec-stat-card">
            <span class="spec-stat-title">Tản nhiệt Premium</span>
            <span class="spec-stat-desc">Luồng khí thông minh, êm ái</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================== BRAND LOGOS ROW ===================== -->
    <section class="brand-logos-row-wrap">
      <div class="brand-logos-marquee-viewport">
        <div class="brand-logos-track">
          <template v-for="copy in 4" :key="'brand-loop-' + copy">
            <div 
              v-for="brand in brandLogos" 
              :key="copy + '-' + brand.name" 
              class="brand-logo-item" 
              :class="{ active: isQuickBrandActive(brand.name) }"
              @click="selectQuickBrand(brand.name)"
              :title="brand.name"
            >
              <img :src="brand.logo" :alt="brand.name" class="brand-logo-img" loading="lazy" />
            </div>
          </template>
        </div>
      </div>
    </section>

    <!-- ===================== PREMIUM FLAGSHIP MACHINES ===================== -->
    <section class="section-layout flash-sale-section">
      <div class="flash-sale-header-row">
        <div class="flash-sale-title-block">
          <span class="flash-fire-icon">◆</span>
          <h2>MÁY FLAGSHIP ĐẮT TIỀN NHẤT</h2>
          <p>Những cấu hình xịn nhất, mạnh nhất và cao cấp nhất dành cho gaming, sáng tạo nội dung và workstation.</p>
          <p v-if="isUsingPremiumFallback" class="premium-fallback-note">
            Chưa có sản phẩm trên {{ formatPrice(premiumPriceThreshold) }}, đang hiển thị top sản phẩm đắt nhất trong database.
          </p>
        </div>

        <div class="premium-rank-badge">
          <span>Top cấu hình</span>
          <strong>Ultra Premium</strong>
        </div>
      </div>

      <!-- Premium Flagship Slider Wrapper -->
      <div v-if="flashSaleCarousel.length > 0" class="flash-sale-carousel-wrapper">
        <button class="carousel-arrow prev" @click="prevFlashSale" :disabled="flashSaleMaxIndex === 0">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        <div
          class="flash-sale-viewport"
          ref="flashSaleViewportRef"
          @pointerdown="startFlashDrag"
          @pointermove="moveFlashDrag"
          @pointerup="endFlashDrag"
          @pointercancel="endFlashDrag"
          @pointerleave="endFlashDrag"
          @wheel.prevent="handleFlashWheel"
        >
          <div
            class="flash-sale-grid-slider"
            :class="{ dragging: isFlashDragging }"
            :style="flashSaleTrackStyle"
          >
          <div 
            class="flash-sale-card" 
            v-for="product in flashSaleCarousel" 
            :key="product.id_bienthe || product.id_sanpham"
            @click="goToPremiumProduct(product)"
          >
            <!-- Premium Badge -->
            <div class="flash-card-badge-row">
              <span class="flash-sale-badge">{{ product.badge }}</span>
              <span class="flash-sale-tag">{{ product.tag }}</span>
            </div>

            <div class="flash-card-image-box">
              <img :src="product.image" :alt="product.name" />
            </div>

            <div class="flash-card-content">
              <h3 class="flash-product-title">{{ product.name }}</h3>
              
              <!-- Specs pills -->
              <div class="flash-specs-list">
                <span class="flash-spec-pill" v-for="spec in product.specs" :key="spec">{{ spec }}</span>
              </div>

              <!-- Price row -->
              <div class="flash-price-row">
                <span class="price-discounted">{{ formatPrice(product.price) }}</span>
                <span class="price-original">{{ product.variantName || 'Biến thể cao cấp nhất' }}</span>
              </div>

              <!-- Premium Highlight -->
              <div class="flash-progress-wrapper">
                <div class="flash-progress-bar" style="width: 100%"></div>
                <span class="flash-progress-text">{{ product.highlight }}</span>
              </div>

              <div class="flash-actions" @pointerdown.stop>
                <button type="button" class="flash-buy-btn" @click.stop="goToPremiumProduct(product)">Xem cấu hình</button>
                <button
                  type="button"
                  class="flash-buy-btn buy-now"
                  :disabled="!product.inStock"
                  @click.stop="buyPremiumProduct(product)"
                >
                  {{ product.inStock ? 'Mua ngay' : 'Hết hàng' }}
                </button>
              </div>
            </div>
          </div>
          </div>
        </div>

        <button class="carousel-arrow next" @click="nextFlashSale" :disabled="flashSaleMaxIndex === 0">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>

      <div v-else class="premium-empty-state">
        <span>Chưa có sản phẩm trên {{ formatPrice(premiumPriceThreshold) }}</span>
        <p>Section này sẽ tự hiển thị ngay khi database có biến thể sản phẩm đạt mức giá này.</p>
      </div>
    </section>

    <!-- ===================== KHÁM PHÁ CHI TIẾT SẢN PHẨM ===================== -->
    <section class="interactive-details-section">
      <div class="glass-container-details">
        
        <!-- Left Side: Interactive image slider -->
        <div class="interactive-slider-box">
          <div class="slider-header-badge">
            <span class="badge-accent">TRỰC QUAN SHOT</span>
            <h3>Khám phá Thiết kế & Góc Nhìn Chi Tiết</h3>
          </div>
          
          <div class="interactive-image-viewer">
            <button class="viewer-arrow prev" @click="prevAngle">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            
            <img :src="interactiveAngles[activeAngleIndex].img" :alt="interactiveAngles[activeAngleIndex].label" class="angle-display-image" />
            
            <button class="viewer-arrow next" @click="nextAngle">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
          
          <!-- Angle Selectors Buttons -->
          <div class="angle-selectors-row">
            <button 
              v-for="(angle, idx) in interactiveAngles" 
              :key="angle.label" 
              class="angle-selector-btn"
              :class="{ active: activeAngleIndex === idx }"
              @click="activeAngleIndex = idx"
            >
              {{ angle.label }}
            </button>
            
            <button class="btn-3d-visual">
              <span>View 3D 360°</span>
            </button>
          </div>
        </div>

        <!-- Right Side: Spec advantages checklist -->
        <div class="interactive-copy-box">
          <span class="section-subtitle-cyan">Độc quyền tại Predator</span>
          <h2>Góc nhìn thực tế từ tương lai</h2>
          <p>Mỗi chi tiết thiết kế trên các dòng laptop flagship đều được hoàn thiện từ nhôm hàng không CNC cứng cáp, các khe gió và tản nhiệt tối ưu cho luồng khí tối đa.</p>
          
          <div class="advantages-checklist-group">
            <div class="checklist-item">
              <span class="check-icon">✓</span>
              <div>
                <strong>Độ phân giải siêu thực, tần số quét 240Hz</strong>
                <p>Khung hình mượt mà không độ trễ, tối ưu hóa màu sắc điện ảnh DCI-P3.</p>
              </div>
            </div>
            <div class="checklist-item">
              <span class="check-icon">✓</span>
              <div>
                <strong>Chất liệu cao cấp sợi Carbon & Hợp kim Magie</strong>
                <p>Nhẹ hơn 30% so với nhôm thông thường nhưng siêu bền bỉ.</p>
              </div>
            </div>
            <div class="checklist-item">
              <span class="check-icon">✓</span>
              <div>
                <strong>Bảo hành chính hãng 2 năm, hỗ trợ 24/7</strong>
                <p>An tâm tối đa với hệ thống hỗ trợ sự cố chuyên nghiệp từ xa.</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ===================== SERVICE PILLS ROW ===================== -->
    <section class="services-pills-wrap">
      <div class="services-pills-grid">
        <div class="service-pill-card" v-for="service in servicesList" :key="service.title">
          <div class="service-pill-icon-box" v-html="service.icon"></div>
          <div class="service-pill-text">
            <h4>{{ service.title }}</h4>
            <p>{{ service.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================== MAIN PREMIUM CATALOG DIRECTORY ===================== -->
    <section id="catalog-section" class="section-layout catalog-section-wrapper">
      
      <!-- Catalog Header Row -->
      <div class="catalog-header-row">
        <div>
          <span class="catalog-label-glow">Premium Catalog</span>
          <h2>Danh sách Laptop Premium</h2>
          <p>Khám phá hệ thống máy tính cao cấp tích hợp cấu hình đỉnh cao nhất</p>
        </div>
        
        <!-- Search bar inside catalog -->
        <div class="catalog-search-bar">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input 
            type="text" 
            placeholder="Tìm kiếm model, CPU, RTX..." 
            v-model="searchQuery"
          />
        </div>
      </div>

      <!-- Quick Category Pill Row -->
      <div class="catalog-pills-row">
        <button 
          v-for="cat in filterOptions.categories" 
          :key="cat" 
          class="catalog-pill-btn"
          :class="{ active: activeCategory === cat }"
          @click="selectCategory(cat)"
        >
          {{ cat }}
        </button>
      </div>

      <!-- Catalog Main Layout -->
      <div class="catalog-layout">
        
        <!-- Left Sidebar Filter -->
        <!-- Left Sidebar Filter -->
        <aside class="catalog-filter-sidebar">
          <div class="filter-sidebar-card">
            <div class="filter-sidebar-header">
              <div class="header-title-wrap">
                <span class="filter-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16" style="display: inline-block; vertical-align: middle;">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                  </svg>
                </span>
                <h3>Bộ lọc</h3>
              </div>
              <button 
                v-if="selectedBrands.length > 0 || minPrice > 0 || maxPrice < 150000000 || selectedRams.length > 0 || selectedSsds.length > 0 || searchQuery !== ''" 
                class="clear-all-link"
                @click="clearAllFilters"
              >
                Xóa lọc
              </button>
            </div>

            <!-- Brand Filter -->
            <div class="filter-option-group">
              <h4>Thương hiệu</h4>
              <div class="filter-pill-grid brand-pill-grid">
                <button 
                  v-for="brand in filterOptions.brands" 
                  :key="brand" 
                  class="filter-pill-btn"
                  :class="{ active: selectedBrands.includes(brand) }"
                  @click="toggleBrand(brand)"
                >
                  {{ brand }}
                </button>
              </div>
            </div>

            <!-- Price Filter Slider -->
            <div class="filter-option-group">
              <h4>Khoảng giá</h4>
              <div class="price-slider-wrapper">
                <div class="price-slider-display">
                  <span>{{ formatPrice(minPrice) }}</span>
                  <span>-</span>
                  <span>{{ formatPrice(maxPrice) }}</span>
                </div>
                <div class="price-slider-container">
                  <div class="price-slider-track" :style="{
                    left: `${(minPrice / 150000000) * 100}%`,
                    right: `${100 - (maxPrice / 150000000) * 100}%`
                  }"></div>
                  <div class="price-slider-inputs">
                    <input 
                      type="range" 
                      min="0" 
                      max="150000000" 
                      step="1000000" 
                      v-model.number="minPrice" 
                      aria-label="Giá tối thiểu"
                    />
                    <input 
                      type="range" 
                      min="0" 
                      max="150000000" 
                      step="1000000" 
                      v-model.number="maxPrice" 
                      aria-label="Giá tối đa"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- RAM Filter -->
            <div class="filter-option-group">
              <h4>Bộ nhớ RAM</h4>
              <div class="filter-pill-grid">
                <button 
                  v-for="ram in filterOptions.rams" 
                  :key="ram" 
                  class="filter-pill-btn"
                  :class="{ active: selectedRams.includes(ram) }"
                  @click="toggleRam(ram)"
                >
                  {{ ram }}
                </button>
              </div>
            </div>

            <!-- SSD Filter -->
            <div class="filter-option-group">
              <h4>Ổ cứng SSD</h4>
              <div class="filter-pill-grid">
                <button 
                  v-for="ssd in filterOptions.ssds" 
                  :key="ssd" 
                  class="filter-pill-btn"
                  :class="{ active: selectedSsds.includes(ssd) }"
                  @click="toggleSsd(ssd)"
                >
                  {{ ssd }}
                </button>
              </div>
            </div>

            <!-- CPU Filter -->
            <div class="filter-option-group">
              <h4>Vi xử lý CPU</h4>
              <div class="filter-pill-grid">
                <button 
                  v-for="cpu in filterOptions.cpus" 
                  :key="cpu" 
                  class="filter-pill-btn"
                  :class="{ active: selectedCpus.includes(cpu) }"
                  @click="toggleCpu(cpu)"
                >
                  {{ cpu }}
                </button>
              </div>
            </div>

            <!-- GPU Filter -->
            <div class="filter-option-group gpu-filter-group">
              <h4>Card đồ họa GPU</h4>
              <div class="filter-pill-grid">
                <button 
                  v-for="gpu in filterOptions.gpus" 
                  :key="gpu" 
                  class="filter-pill-btn"
                  :class="{ active: selectedGpus.includes(gpu) }"
                  @click="toggleGpu(gpu)"
                >
                  {{ gpu }}
                </button>
              </div>
            </div>

            <!-- Screen Size Filter -->
            <div class="filter-option-group">
              <h4>Kích thước màn hình</h4>
              <div class="filter-pill-grid">
                <button 
                  v-for="scr in filterOptions.screens" 
                  :key="scr" 
                  class="filter-pill-btn"
                  :class="{ active: selectedScreens.includes(scr) }"
                  @click="toggleScreen(scr)"
                >
                  {{ scr }}
                </button>
              </div>
            </div>

            <!-- Refresh Rate Filter -->
            <div class="filter-option-group">
              <h4>Tần số quét màn hình</h4>
              <div class="filter-pill-grid">
                <button 
                  v-for="hz in filterOptions.hzs" 
                  :key="hz" 
                  class="filter-pill-btn"
                  :class="{ active: selectedHzs.includes(hz) }"
                  @click="toggleHz(hz)"
                >
                  {{ hz }}
                </button>
              </div>
            </div>

          </div>
        </aside>

        <!-- Right Premium Product Grid -->
        <div class="catalog-grid-area">
          <!-- Sorting and Count Bar -->
          <div class="grid-sort-header-row">
            <span class="results-count">Đang hiển thị <b>{{ paginatedProducts.length }}</b>/<b>{{ filteredProducts.length }}</b> sản phẩm</span>
            <div class="sort-dropdown-wrap">
              <label for="catalog-sort-select">Sắp xếp theo:</label>
              <select id="catalog-sort-select" v-model="activeSort" class="premium-sort-select">
                <option value="best_sellers">Bán chạy nhất</option>
                <option value="price_asc">Giá từ thấp đến cao</option>
                <option value="price_desc">Giá từ cao đến thấp</option>
                <option value="newest">Mới nhất</option>
                <option value="promotions">Khuyến mãi nhiều nhất</option>
              </select>
            </div>
          </div>
          <div v-if="isLoading" class="catalog-loading-box">
            <div class="spinner"></div>
            <p>Đang tải danh sách sản phẩm...</p>
          </div>

          <div v-else-if="filteredProducts.length === 0" class="catalog-empty-box">
            <span class="empty-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48" style="color: #94a3b8; display: inline-block; margin-bottom: 12px;">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
              </svg>
            </span>
            <p>Không tìm thấy dòng máy tính phù hợp với bộ lọc đã chọn.</p>
            <button class="btn-glow btn-sm" @click="clearAllFilters">Xóa bộ lọc</button>
          </div>

          <div v-else class="catalog-product-grid">
            <article class="premium-product-card" v-for="prod in paginatedProducts" :key="prod.id_sanpham" @click="viewDetail(prod.id_sanpham)">
              
              <!-- Card Top Badge - BEST SELLER -->
              <div class="card-badge-overlay" v-if="prod.gia % 3 === 0 || prod.gia > 60000000">
                <span class="badge-best-seller">BEST SELLER</span>
              </div>

              <!-- Product Image Container (Perfect White Square Box) -->
              <div class="card-media-box">
                <img :src="prod.image" :alt="prod.tenSP" />
                
                <button class="hover-heart-btn" @click.stop="toggleWishlist(prod)" title="Thêm vào yêu thích">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                  </svg>
                </button>

                <!-- Hover Overlay actions -->
                <div class="card-hover-overlay">
                  <button class="hover-quick-view-btn" @click.stop="viewDetail(prod.id_sanpham)">Xem nhanh -></button>
                </div>
              </div>

              <!-- Card Body Content -->
              <div class="card-body-content">
                <h3 class="product-card-title">{{ prod.tenSP }}</h3>

                <!-- Rating and Reviews Count -->
                <div class="product-rating-row">
                  <span class="stars">★ {{ prod.rating || '4.8' }}</span>
                  <span class="reviews-count">({{ prod.reviews || '12' }} đánh giá)</span>
                </div>

                <!-- Specs Short Badges -->
                <div class="product-specs-summary" v-if="prod.specs && prod.specs.length > 0">
                  <span v-for="spec in prod.specs.slice(0, 4)" :key="spec" class="spec-tag">{{ spec }}</span>
                </div>
                
                <!-- Price Area with Discount Badge -->
                <div class="card-price-row">
                  <span class="footer-price-curr">{{ formatPrice(prod.gia) }}</span>
                  <span class="badge-discount">-{{ Math.round((prod.oldPrice - prod.gia) / prod.oldPrice * 100) }}%</span>
                </div>

                <!-- Crossed out original price -->
                <span class="footer-price-old">{{ formatPrice(prod.oldPrice) }}</span>

                <!-- Installment monthly fee (Dynamically calculated based on price / 12) -->
                <span class="installment-text">• Góp {{ formatPrice(Math.round(prod.gia / 12)) }}/t</span>

                <!-- Badge row 1: Chính Hãng -->
                <div class="card-badge-row-1">
                  <span class="badge-chinh-hang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="check-icon-svg">
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Chính Hãng
                  </span>
                </div>

                <!-- Badge row 2: Freeship & Warranty -->
                <div class="card-badge-row-2">
                  <span class="badge-ship-warranty">
                    <span class="badge-icon">
                      <svg viewBox="0 0 24 24" fill="currentColor" width="10" height="10" style="display: inline-block; vertical-align: middle;">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                      </svg>
                    </span>
                    Freeship 2H
                  </span>
                  <span class="badge-ship-warranty">
                    <span class="badge-icon">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="10" height="10" style="display: inline-block; vertical-align: middle;">
                        <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                      </svg>
                    </span>
                    BH 24T
                  </span>
                </div>
              </div>

              <!-- Floating Cart circular button in bottom right corner on hover -->
              <button class="card-hover-cart-btn" @click.stop="addToCart(prod)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
              </button>

            </article>
          </div>


          <!-- Catalog Pagination -->
          <div class="catalog-pagination-row" v-if="filteredProducts.length > PRODUCTS_PER_PAGE">
            <button class="pagination-arrow prev" :disabled="currentPage === 1" @click="goToCatalogPage(currentPage - 1)">&lt;</button>
            <button
              v-for="(page, index) in visibleCatalogPages"
              :key="`catalog-page-${page}-${index}`"
              class="pagination-number"
              :class="{ active: currentPage === page, dots: page === '...' }"
              :disabled="page === '...'"
              @click="goToCatalogPage(page)"
            >
              {{ page }}
            </button>
            <button class="pagination-arrow next" :disabled="currentPage === totalCatalogPages" @click="goToCatalogPage(currentPage + 1)">&gt;</button>
          </div>
        </div>

      </div>
    </section>

    <!-- ===================== SHOWROOM IMMERSIVE BANNER ===================== -->
    <section id="showroom-section" class="showroom-immersive-banner">
      <div class="showroom-immersive-overlay"></div>
      
      <div class="showroom-immersive-container">
        <!-- Left: Copy content -->
        <div class="showroom-immersive-copy">
          <span class="showroom-tag-neon">PREDATOR SHOWROOM</span>
          <h2>Trải nghiệm trực tiếp tại Showroom Predator</h2>
          <p>Đến ngay cửa hàng không gian tương lai của Predator để tận tay trải nghiệm các dòng máy tính gaming khủng nhất và nhận tư vấn cấu hình tối ưu từ chuyên gia của chúng tôi.</p>
          
          <div class="showroom-highlights-checklist">
            <div 
              class="showroom-highlight-card" 
              v-for="hl in showroomHighlights" 
              :key="hl.text"
            >
              <span class="highlight-icon" v-html="hl.icon"></span>
              <div>
                <strong>{{ hl.text }}</strong>
                <p>{{ hl.desc }}</p>
              </div>
            </div>
          </div>
          
          <button class="btn-glow" @click="router.push('/contact')">Đăng ký trải nghiệm ngay</button>
        </div>
        
        <!-- Right: Neon Image -->
        <div class="showroom-immersive-visual">
          <img src="/Gemini_Generated_Image_v5vppjv5vppjv5vp (2).png" alt="Predator laptop showroom" />
        </div>
      </div>
    </section>

    <!-- ===================== PREMIUM SERVICES GRID SYSTEM ===================== -->
    <section class="section-layout services-grid-system">
      <div class="services-grid-header">
        <span class="services-system-label">DỊCH VỤ XỨNG TẦM</span>
        <h2>Dịch vụ xứng tầm một hệ thống cao cấp</h2>
      </div>

      <div class="services-composite-grid">
        <!-- Left Card (Large Full-Height) -->
        <div class="composite-card large-box-left">
          <div class="composite-card-info">
            <span class="box-badge-blue">HOT LINE</span>
            <h3>Giao hàng hỏa tốc 2H</h3>
            <p>Hệ thống vận hành thần tốc, trao tay hộp sản phẩm nguyên seal bọc túi chống sốc chuyên dụng đến tận cửa phòng khách của bạn trong vòng 2 giờ tại nội thành.</p>
          </div>
          <div class="composite-card-media">
            <img src="https://images.unsplash.com/photo-1549465220-1a8b9238cd48?auto=format&fit=crop&w=800&q=80" alt="Premium Luxury Cardboard box opening" />
          </div>
        </div>

        <!-- Right Stack (3 Cards) -->
        <div class="composite-right-stack">
          <div class="composite-row-top">
            <!-- Top Left card -->
            <div class="composite-card card-half-top">
              <div class="composite-card-info">
                <h3>Trả góp 0% linh hoạt</h3>
                <p>Hỗ trợ đa dạng thẻ tín dụng, xét duyệt từ xa nhanh gọn qua ứng dụng điện thoại.</p>
              </div>
              <div class="composite-card-media-half">
                <img src="https://images.unsplash.com/photo-1589758438368-0ad531db3366?auto=format&fit=crop&w=600&q=80" alt="Sleek Credit cards mockup" />
              </div>
            </div>

            <!-- Top Right card -->
            <div class="composite-card card-half-top">
              <div class="composite-card-info">
                <h3>Bảo hành 24/7 toàn quốc</h3>
                <p>Yên tâm tuyệt đối với bảo hiểm phần cứng chuyên sâu 24 tháng chính hãng.</p>
              </div>
              <div class="composite-card-media-half">
                <img src="https://images.unsplash.com/photo-1510519138101-570d1dca3d66?auto=format&fit=crop&w=600&q=80" alt="Modern support shield render" />
              </div>
            </div>
          </div>

          <!-- Bottom card (Wide full-width of right column) -->
          <div class="composite-card card-full-bottom">
            <div class="composite-card-info">
              <h3>Hỗ trợ kỹ thuật 24/7</h3>
              <p>Dịch vụ tư vấn phần cứng và khắc phục sự cố phần mềm thông qua TeamViewer/UltraViewer bất kể ngày đêm bởi đội ngũ kỹ thuật viên cao cấp giàu kinh nghiệm của Predator.</p>
            </div>
            <div class="composite-card-media-wide">
              <img src="https://images.unsplash.com/photo-1504639725590-34d0984388bd?auto=format&fit=crop&w=1000&q=80" alt="Creative cozy PC desk workstation setup" />
            </div>
          </div>
        </div>
      </div>
    </section>

  </div>
</template>

<style scoped>
/* ==================== SEARCH / SORTING HEADER & RATING / SPECS STYLING ==================== */
.grid-sort-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  background: #ffffff;
  padding: 14px 20px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.results-count {
  font-size: 13.5px;
  color: #64748b;
}

.results-count b {
  color: #0f172a;
}

.sort-dropdown-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sort-dropdown-wrap label {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.premium-sort-select {
  padding: 8px 36px 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  border: 1px solid #cbd5e1;
  background-color: #ffffff;
  color: #334155;
  outline: none;
  cursor: pointer;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.product-rating-row {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 4px 0;
  font-size: 11.5px;
}

.product-rating-row .stars {
  color: #fbbf24;
  font-weight: 700;
}

.product-rating-row .reviews-count {
  color: #94a3b8;
}

.product-specs-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin: 6px 0;
}

.product-specs-summary .spec-tag {
  background: #f1f5f9;
  color: #475569;
  font-size: 10.5px;
  font-weight: 600;
  padding: 2px 7px;
  border-radius: 5px;
  border: 1px solid #e2e8f0;
}

/* ============================================================
   TECHNOVA PREDATOR PREMIUM CLEAN DESIGN SYSTEM
   Professional, Simple, High-End Dark-Slate Style (No colorful cheap gradients)
   ============================================================ */

.premium-page-shell {
  background: #f8fafc;
  color: #0f172a;
  font-family: 'Be Vietnam Pro', 'Inter', sans-serif;
  overflow-x: clip; /* clip does not prevent sticky position context */
  padding-bottom: 80px;
}

/* Base Typography & Layout */
h1, h2, h3, h4 {
  font-weight: 700;
  letter-spacing: -0.015em;
  color: #0f172a;
}

p {
  color: #94a3b8;
  line-height: 1.65;
  font-size: 14px;
}

.section-layout {
  padding: 60px 6vw 30px;
  max-width: 1440px;
  margin: 0 auto;
}

/* Solid Professional Buttons (No Flashy Gradients) */
.btn-glow {
  background: #2563eb; /* Clean solid royal tech blue */
  color: #ffffff;
  padding: 12px 26px;
  font-size: 13.5px;
  font-weight: 700;
  border-radius: 8px; /* High-end sharp corner radius */
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
  transition: all 0.2s ease-in-out;
}

.btn-glow:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}

.btn-glow.btn-sm {
  padding: 8px 16px;
  font-size: 12px;
  border-radius: 6px;
}

.btn-outline {
  background: rgba(255, 255, 255, 0.03);
  color: #f1f5f9;
  padding: 12px 26px;
  font-size: 13.5px;
  font-weight: 700;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-outline:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.25);
  transform: translateY(-1px);
}

/* ============================================================
   1. HERO SECTION
   ============================================================ */
.hero-banner {
  display: grid;
  grid-template-columns: minmax(320px, 0.52fr) 1fr;
  gap: 32px;
  align-items: center;
  min-height: 28vh;
  padding: 30px max(6vw, calc((100vw - 1280px) / 2 + 32px)) 26px;
  width: 100vw;
  max-width: none;
  margin-left: 50%;
  transform: translateX(-50%);
  position: relative;
  overflow: hidden;
  isolation: isolate;
  border-radius: 0;
}

.hero-banner::before,
.hero-banner::after {
  content: '';
  position: absolute;
  inset: 0;
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center right;
  z-index: -3;
}

.hero-banner::before {
  background-image: var(--hero-bg-1);
  background-position: center right;
}

.hero-banner::after {
  z-index: -2;
  background:
    linear-gradient(90deg, rgba(7, 12, 22, 0.72) 0%, rgba(7, 12, 22, 0.42) 34%, rgba(7, 12, 22, 0.08) 72%, rgba(7, 12, 22, 0.02) 100%),
    linear-gradient(0deg, rgba(7, 12, 22, 0.04), rgba(7, 12, 22, 0.04));
}

.hero-banner > * {
  position: relative;
  z-index: 1;
}

.hero-banner .hero-copy::before {
  content: '';
  position: absolute;
  inset: -20px -28px;
  background: linear-gradient(90deg, rgba(8, 13, 24, 0.54), rgba(8, 13, 24, 0.26) 70%, transparent);
  border-radius: 24px;
  z-index: -1;
  pointer-events: none;
}

.hero-banner::selection {
  background: rgba(96, 165, 250, 0.28);
  color: #ffffff;
}

.hero-banner::before,
.hero-banner::after {
  filter: saturate(1.08) contrast(1.02) brightness(1.08);
}

.hero-copy {
  z-index: 2;
  position: relative;
  grid-column: 1;
}

.eyebrow-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 6px;
  background: rgba(37, 99, 235, 0.1);
  border: 1px solid rgba(37, 99, 235, 0.2);
  color: #60a5fa;
  font-weight: 700;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 12px;
}

.hero-banner h1 {
  font-size: clamp(1.65rem, 2.15vw, 2.35rem);
  line-height: 1.1;
  margin-bottom: 10px;
  color: #ffffff;
}

.hero-banner p {
  font-size: 12.5px;
  line-height: 1.5;
  margin-bottom: 16px;
  max-width: 500px;
  color: #94a3b8;
}

.hero-actions {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.hero-actions .btn-glow,
.hero-actions .btn-outline {
  padding: 9px 18px;
  font-size: 12px;
  border-radius: 7px;
}

.hero-specs-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.spec-stat-card {
  background: #0f172a; /* Clean solid slate dark box */
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  padding: 10px;
  transition: all 0.2s ease;
}

.spec-stat-card:hover {
  border-color: rgba(37, 99, 235, 0.25);
  background: #141e33;
}

.spec-stat-title {
  display: block;
  font-size: 11.5px;
  font-weight: 700;
  color: #3b82f6;
  margin-bottom: 3px;
}

.spec-stat-desc {
  font-size: 10px;
  color: #64748b;
  line-height: 1.35;
}

@keyframes heroBgOne {
  0%, 44% { opacity: 1; transform: scale(1.03); }
  55%, 94% { opacity: 0; transform: scale(1.07); }
  100% { opacity: 1; transform: scale(1.03); }
}

@keyframes heroBgTwo {
  0%, 44% { opacity: 0; transform: scale(1.07); }
  55%, 94% { opacity: 1; transform: scale(1.03); }
  100% { opacity: 0; transform: scale(1.07); }
}

/* ============================================================
   2. BRAND LOGOS ROW
   ============================================================ */
.brand-logos-row-wrap {
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.98) 0%, rgba(10, 17, 31, 1) 100%);
  border-top: 1px solid rgba(148, 163, 184, 0.12);
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
  padding: 18px clamp(48px, 7vw, 132px);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
}

.brand-logos-marquee-viewport {
  width: 100%;
  overflow: hidden;
  display: flex;
  align-items: center;
  padding: 6px 0;
  mask-image: linear-gradient(to right, transparent, white 4%, white 96%, transparent);
  -webkit-mask-image: linear-gradient(to right, transparent, white 4%, white 96%, transparent);
}

.brand-logos-track {
  --brand-card-width: 172px;
  --brand-gap: 20px;
  --brand-count: 6;
  display: flex;
  gap: var(--brand-gap);
  width: max-content;
  animation: marquee 30s linear infinite;
  will-change: transform;
}

.brand-logo-item {
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--brand-card-width); /* Consistent card width */
  height: 72px; /* Consistent card height */
  padding: 14px 24px;
  border-radius: 18px; /* Exact border-radius requested */
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.22);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  flex-shrink: 0;
  position: relative;
  overflow: hidden; /* Exact overflow requested */
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
}

.brand-logo-item::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.55), transparent 62%);
  opacity: 0.45;
  transition: opacity 0.3s ease;
}

.brand-logo-item:hover {
  transform: translateY(-4px) scale(1.05); /* Smooth hover scale */
  border-color: rgba(37, 99, 235, 0.42);
  box-shadow: 0 16px 34px rgba(37, 99, 235, 0.22); /* Hover glow effect */
}

.brand-logo-item:hover::after {
  opacity: 0.7;
}

.brand-logo-item.active {
  border-color: #2563eb;
  box-shadow: 0 16px 34px rgba(37, 99, 235, 0.22);
  transform: translateY(-4px) scale(1.05);
}

.brand-logo-img {
  width: 100%;
  height: 100%;
  max-width: 118px;
  max-height: 44px;
  object-fit: contain; /* Exact object-fit requested */
  filter: none;
  transition: all 0.3s ease;
  opacity: 1;
  position: relative;
  z-index: 2;
}

.brand-logo-item:hover .brand-logo-img,
.brand-logo-item.active .brand-logo-img {
  opacity: 1;
  transform: scale(1.05);
}

@keyframes marquee {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(calc(-1 * var(--brand-count) * (var(--brand-card-width) + var(--brand-gap))));
  }
}

/* ============================================================
   3. PREMIUM FLAGSHIP MACHINES
   ============================================================ */
.flash-sale-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 20px;
}

.flash-sale-title-block h2 {
  font-size: 28px;
  color: #0f172a;
  margin: 6px 0;
}

.premium-fallback-note {
  margin-top: 8px;
  color: #b45309 !important;
  font-size: 12px !important;
  font-weight: 700;
}

.flash-fire-icon {
  font-size: 22px;
  margin-right: 6px;
  color: #f59e0b;
}

.premium-rank-badge {
  display: flex;
  flex-direction: column;
  gap: 2px;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  border: 1px solid rgba(245, 158, 11, 0.35);
  color: #f8fafc;
  padding: 10px 16px;
  border-radius: 10px;
  box-shadow: 0 14px 30px rgba(15, 23, 42, 0.14);
}

.premium-rank-badge span {
  color: #fbbf24;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.8px;
  text-transform: uppercase;
}

.premium-rank-badge strong {
  font-size: 15px;
  font-weight: 800;
}

.countdown-clock-container {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(239, 68, 68, 0.04);
  border: 1px solid rgba(239, 68, 68, 0.15);
  padding: 8px 14px;
  border-radius: 8px;
}

.countdown-label {
  font-size: 13px;
  font-weight: 700;
  color: #fca5a5;
  margin-right: 4px;
}

.countdown-digit-block {
  background: #ef4444;
  color: #ffffff;
  font-size: 16px;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 4px;
  min-width: 32px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
}

.countdown-colon {
  font-size: 16px;
  font-weight: 700;
  color: #ef4444;
}

/* Premium Flagship Carousel Grid */
.flash-sale-carousel-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  --arrow-bg: rgba(15, 23, 42, 0.9);
  --flash-gap: 12px;
}

.flash-sale-viewport {
  width: 100%;
  overflow: hidden;
  cursor: grab;
  user-select: none;
  touch-action: pan-y;
}

.flash-sale-grid-slider {
  display: flex;
  gap: var(--flash-gap);
  width: max-content;
  position: relative;
  transform: translate3d(var(--flash-x), 0, 0);
  transition: transform 0.62s cubic-bezier(0.22, 1, 0.36, 1);
  will-change: transform;
}

.flash-sale-viewport:active,
.flash-sale-grid-slider.dragging {
  cursor: grabbing;
}

.flash-sale-grid-slider.dragging {
  transition: transform 0.08s linear;
}

.carousel-arrow {
  background: var(--arrow-bg);
  color: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.18);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  flex-shrink: 0;
  transition: background 0.25s ease, border-color 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease, opacity 0.25s ease;
  box-shadow: 0 10px 24px rgba(2, 6, 23, 0.24);
}

.carousel-arrow svg {
  width: 16px;
  height: 16px;
  transition: transform 0.25s ease;
}

.carousel-arrow:hover:not(:disabled) {
  background: #2563eb;
  border-color: rgba(96, 165, 250, 0.85);
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 14px 30px rgba(37, 99, 235, 0.28);
}

.carousel-arrow.prev:hover:not(:disabled) svg {
  transform: translateX(-2px);
}

.carousel-arrow.next:hover:not(:disabled) svg {
  transform: translateX(2px);
}

.carousel-arrow:disabled {
  opacity: 0.28;
  cursor: not-allowed;
  box-shadow: none;
}

.premium-empty-state {
  border: 1px dashed #cbd5e1;
  background: #ffffff;
  border-radius: 12px;
  padding: 28px 24px;
  text-align: center;
  color: #64748b;
}

.premium-empty-state span {
  display: block;
  color: #0f172a;
  font-size: 16px;
  font-weight: 800;
  margin-bottom: 8px;
}

.premium-empty-state p {
  margin: 0;
  font-size: 13px;
}

/* Clean premium white cards */
.flash-sale-card {
  flex: 0 0 var(--flash-card-width, calc((100% - 48px) / 5));
  max-width: var(--flash-card-width, calc((100% - 48px) / 5));
  background: #ffffff; /* Clean solid white background */
  color: #0f172a;
  border-radius: 12px;
  padding: 12px;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
  display: flex;
  flex-direction: column;
  position: relative;
  border: 1px solid #e2e8f0;
  transition: transform 0.34s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.28s ease, border-color 0.28s ease;
  will-change: transform, opacity;
  user-select: none;
}

.flash-sale-grid-slider:not(.dragging) .flash-sale-card:hover {
  transform: translateY(-4px);
  border-color: rgba(245, 158, 11, 0.42);
  box-shadow: 0 18px 36px rgba(2, 6, 23, 0.24);
}

.flash-sale-card img,
.flash-sale-card button {
  user-select: none;
}

.flash-sale-card img {
  pointer-events: none;
}

.flash-card-badge-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 7px;
}

.flash-sale-badge {
  background: #0f172a;
  color: #ffffff;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 9.5px;
  font-weight: 700;
}

.flash-sale-tag {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: #ffffff;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 9.5px;
  font-weight: 700;
}

.flash-card-image-box {
  width: 100%;
  height: 108px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 10px;
}

.flash-card-image-box img {
  max-width: 84%;
  max-height: 84%;
  object-fit: contain;
}

.flash-card-content {
  display: flex;
  flex: 1;
  flex-direction: column;
}

.flash-product-title {
  font-size: 11.5px;
  font-weight: 700;
  line-height: 1.4;
  color: #0f172a;
  margin-bottom: 7px;
  height: 32px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.flash-specs-list {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}

.flash-spec-pill {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 2px 5px;
  border-radius: 4px;
  font-size: 8.5px;
  font-weight: 600;
}

.flash-price-row {
  display: flex;
  align-items: baseline;
  gap: 5px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.price-discounted {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.price-original {
  font-size: 9.5px;
  color: #b45309;
  text-decoration: none;
  font-weight: 700;
  line-height: 1.2;
  max-width: 100%;
}

/* Compact Progress Bar */
.flash-progress-wrapper {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  min-height: 34px;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  padding: 5px 7px;
  margin-bottom: 8px;
}

.flash-progress-bar {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  background: linear-gradient(90deg, rgba(245, 158, 11, 0.18), rgba(37, 99, 235, 0.08));
  border-radius: 6px;
}

.flash-progress-text {
  font-size: 8.5px;
  font-weight: 700;
  color: #334155;
  z-index: 1;
  position: relative;
  line-height: 1.25;
}

.flash-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
  margin-top: auto;
}

.flash-buy-btn {
  background: #0f172a;
  color: #ffffff;
  font-weight: 700;
  font-size: 10.5px;
  border: none;
  border-radius: 6px;
  padding: 8px 7px;
  cursor: pointer;
  transition: background 0.2s ease;
  min-width: 0;
  white-space: nowrap;
}

.flash-buy-btn:hover {
  background: #1d4ed8;
}

.flash-buy-btn.buy-now {
  background: #2563eb;
}

.flash-buy-btn.buy-now:hover {
  background: #1d4ed8;
}

.flash-buy-btn:disabled {
  background: #94a3b8;
  cursor: not-allowed;
}

/* ============================================================
   4. INTERACTIVE ANGLE DETAIL CORNER
   ============================================================ */
.interactive-details-section {
  width: 100vw;
  position: relative;
  left: 50%;
  right: 50%;
  margin-left: -50vw;
  margin-right: -50vw;
  padding: 50px 0;
  background: linear-gradient(135deg, #020817 0%, #0f172a 40%, #0a1628 70%, #060d1a 100%);
  overflow: hidden;
}

.glass-container-details {
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(24px) saturate(160%);
  -webkit-backdrop-filter: blur(24px) saturate(160%);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow:
    0 0 0 1px rgba(99, 102, 241, 0.08) inset,
    0 1px 0 rgba(255, 255, 255, 0.08) inset;
  padding: 50px max(24px, 8vw);
  max-width: 1440px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 48px;
  align-items: center;
  position: relative;
  overflow: hidden;
}

.glass-container-details::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    135deg,
    rgba(99, 102, 241, 0.06) 0%,
    transparent 50%,
    rgba(6, 182, 212, 0.04) 100%
  );
  border-radius: inherit;
  pointer-events: none;
}

.interactive-slider-box {
  display: flex;
  flex-direction: column;
}

.slider-header-badge {
  margin-bottom: 20px;
}

.badge-accent {
  background: rgba(37, 99, 235, 0.18);
  color: #60a5fa;
  font-size: 10.5px;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 4px 12px;
  border-radius: 6px;
  border: 1px solid rgba(96, 165, 250, 0.35);
  display: inline-block;
  margin-bottom: 10px;
}

.slider-header-badge h3 {
  font-size: 22px;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.3;
  text-shadow: 0 2px 10px rgba(0,0,0,0.5);
}

.interactive-image-viewer {
  background: #0b121f;
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 16px;
  height: 320px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  margin-bottom: 20px;
}

.viewer-arrow {
  position: absolute;
  background: rgba(255, 255, 255, 0.03);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.08);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  z-index: 2;
}

.viewer-arrow:hover {
  background: #2563eb;
  border-color: #2563eb;
}

.viewer-arrow.prev { left: 12px; }
.viewer-arrow.next { right: 12px; }

.angle-display-image {
  max-width: 85%;
  max-height: 85%;
  object-fit: contain;
}

.angle-selectors-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: space-between;
  align-items: center;
}

.angle-selector-btn {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
  padding: 8px 14px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.angle-selector-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.15);
}

.angle-selector-btn.active {
  background: rgba(37, 99, 235, 0.1);
  border-color: #2563eb;
  color: #3b82f6;
}

.btn-3d-visual {
  background: #10b981;
  color: #ffffff;
  border: none;
  padding: 8px 14px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s ease;
}

.btn-3d-visual:hover {
  background: #059669;
}

/* Copy box features checklist */
.section-subtitle-cyan {
  color: #38bdf8;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.interactive-copy-box h2 {
  font-size: 28px;
  margin: 10px 0 16px;
  line-height: 1.25;
  color: #ffffff;
  text-shadow: 0 2px 12px rgba(0,0,0,0.4);
}

.interactive-copy-box > p {
  color: #cbd5e1;
  font-size: 14px;
  line-height: 1.7;
  margin-bottom: 8px;
}

.advantages-checklist-group {
  display: grid;
  gap: 16px;
  margin-top: 24px;
}

.checklist-item {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}

.check-icon {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  flex-shrink: 0;
  font-size: 12px;
}

.checklist-item strong {
  display: block;
  font-size: 14.5px;
  color: #f1f5f9;
  margin-bottom: 4px;
}

.checklist-item p {
  font-size: 13px;
  margin: 0;
  color: #94a3b8;
  line-height: 1.6;
}

/* ============================================================
   5. SERVICE PILLS ROW
   ============================================================ */
.services-pills-wrap {
  padding: 30px 6vw;
  max-width: 1440px;
  margin: 0 auto;
}

.services-pills-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.service-pill-card {
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 12px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
  transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.service-pill-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: #2563eb;
  border-radius: 999px;
  transform: scaleX(0);
  transform-origin: left center;
  transition: transform 1.15s cubic-bezier(0.22, 1, 0.36, 1);
}

.service-pill-card:hover {
  border-color: rgba(37, 99, 235, 0.34);
  transform: translateY(-3px);
  box-shadow: 0 18px 34px rgba(37, 99, 235, 0.14);
}

.service-pill-card:hover::before {
  transform: scaleX(1);
}

.service-pill-icon-box {
  background: rgba(37, 99, 235, 0.1);
  border: 1px solid rgba(37, 99, 235, 0.2);
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.service-pill-text h4 {
  font-size: 13.5px;
  margin-bottom: 2px;
  color: #0f172a;
}

.service-pill-text p {
  font-size: 10.5px;
  margin: 0;
  color: #334155;
  line-height: 1.45;
}

/* ============================================================
   6. PREMIUM PRODUCT DIRECTORY (CATALOG)
   ============================================================ */
.catalog-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.catalog-label-glow {
  color: #3b82f6;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.05em;
  display: block;
}

.catalog-header-row h2 {
  font-size: 28px;
  margin: 6px 0;
}

.catalog-search-bar {
  display: flex;
  align-items: center;
  background: #ffffff;
  border: 1px solid #dbe3ef;
  border-radius: 8px;
  padding: 8px 14px;
  width: 280px;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.catalog-search-bar:focus-within {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.catalog-search-bar .search-icon {
  width: 16px;
  height: 16px;
  color: #64748b;
  flex-shrink: 0;
  transition: color 0.2s ease;
}

.catalog-search-bar:focus-within .search-icon {
  color: #3b82f6;
}

.catalog-search-bar input {
  background: transparent;
  border: none;
  outline: none;
  color: #0f172a;
  font-size: 13px;
  margin-left: 8px;
  width: 100%;
}

.catalog-search-bar input::placeholder {
  color: #64748b;
}

.catalog-pills-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 30px;
}

.catalog-pill-btn {
  background: rgba(226, 232, 240, 0.82);
  border: 1px solid rgba(148, 163, 184, 0.28);
  color: #111827;
  padding: 8px 20px;
  border-radius: 6px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.catalog-pill-btn:hover {
  background: #ffffff;
  border-color: rgba(37, 99, 235, 0.38);
  color: #0f172a;
}

.catalog-pill-btn.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
}

/* Catalog Main Structure - Standard 4 Columns layout */
.catalog-layout {
  display: grid;
  grid-template-columns: 238px minmax(0, 1fr);
  gap: 22px;
  align-items: start;
}

.catalog-filter-sidebar {
  position: sticky;
  top: 112px;
  z-index: 10;
  align-self: start;
}

.filter-sidebar-card {
  background: rgba(226, 232, 240, 0.94);
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-radius: 14px;
  padding: 14px;
  max-height: calc(100vh - 132px);
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-width: thin;
  scrollbar-color: rgba(37, 99, 235, 0.45) transparent;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07);
  backdrop-filter: blur(14px);
}

.filter-sidebar-card::-webkit-scrollbar {
  width: 5px;
}

.filter-sidebar-card::-webkit-scrollbar-thumb {
  background: rgba(37, 99, 235, 0.38);
  border-radius: 999px;
}

.filter-sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.24);
  padding-bottom: 10px;
}

.header-title-wrap {
  display: flex;
  align-items: center;
  gap: 7px;
}

.filter-icon {
  font-size: 15px;
  line-height: 1;
}

.filter-sidebar-header h3 {
  font-size: 14px;
  margin: 0;
  color: #0f172a;
}

.clear-all-link {
  background: none;
  border: none;
  color: #ef4444;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  padding: 2px 5px;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.clear-all-link:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #f87171;
}

.filter-option-group {
  margin-bottom: 13px;
}

.filter-option-group h4 {
  font-size: 10.5px;
  color: #111827;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 8px;
  font-weight: 700;
}

/* Compact Checkable Badges Grid */
.filter-pill-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 5px;
}

.brand-pill-grid {
  grid-template-columns: repeat(4, 1fr);
}

.brand-pill-grid .filter-pill-btn {
  font-size: 9.5px;
  padding-inline: 2px;
}

/* For RAM and SSD, we can have 4 columns since the texts are shorter */
.filter-option-group:nth-of-type(4) .filter-pill-grid,
.filter-option-group:nth-of-type(5) .filter-pill-grid {
  grid-template-columns: repeat(4, 1fr);
}

.gpu-filter-group .filter-pill-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.gpu-filter-group .filter-pill-btn {
  font-size: 10px;
  padding-inline: 4px;
  overflow: visible;
  text-overflow: clip;
}

.filter-pill-btn {
  background: rgba(255, 255, 255, 0.72);
  border: 1px solid rgba(148, 163, 184, 0.28);
  color: #111827;
  min-height: 30px;
  padding: 5px 3px;
  border-radius: 6px;
  font-size: 10.5px;
  font-weight: 600;
  cursor: pointer;
  text-align: center;
  transition: all 0.15s ease;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.filter-pill-btn:hover {
  background: #ffffff;
  border-color: rgba(37, 99, 235, 0.38);
  color: #0f172a;
}

.filter-pill-btn.active {
  background: rgba(37, 99, 235, 0.1);
  border-color: #2563eb;
  color: #0f172a;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
}

/* Premium Range Slider styling */
.price-slider-wrapper {
  display: flex;
  flex-direction: column;
}

.price-slider-display {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 10px;
  background: rgba(255, 255, 255, 0.8);
  padding: 6px 8px;
  border-radius: 6px;
  border: 1px dashed rgba(37, 99, 235, 0.15);
}

.price-slider-container {
  position: relative;
  height: 5px;
  background: rgba(148, 163, 184, 0.28);
  border-radius: 3px;
  margin: 8px 0 16px;
}

.price-slider-track {
  position: absolute;
  height: 100%;
  background: #2563eb;
  border-radius: 3px;
}

.price-slider-inputs {
  position: relative;
  height: 0;
}

.price-slider-inputs input[type="range"] {
  position: absolute;
  top: -5px;
  width: 100%;
  height: 5px;
  background: none;
  pointer-events: none;
  -webkit-appearance: none;
  margin: 0;
}

.price-slider-inputs input[type="range"]::-webkit-slider-thumb {
  height: 14px;
  width: 14px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #2563eb;
  cursor: pointer;
  pointer-events: auto;
  -webkit-appearance: none;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
  transition: transform 0.1s ease;
}

.price-slider-inputs input[type="range"]::-webkit-slider-thumb:hover {
  transform: scale(1.2);
}

.price-slider-inputs input[type="range"]::-moz-range-thumb {
  height: 14px;
  width: 14px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #2563eb;
  cursor: pointer;
  pointer-events: auto;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
  transition: transform 0.1s ease;
}

.price-slider-inputs input[type="range"]::-moz-range-thumb:hover {
  transform: scale(1.2);
}

/* Grid Area - Compact 4 Columns */
.catalog-grid-area {
  display: flex;
  flex-direction: column;
}

.catalog-loading-box,
.catalog-empty-box {
  background: rgba(226, 232, 240, 0.94);
  border: 1px dashed rgba(148, 163, 184, 0.32);
  color: #0f172a;
  border-radius: 16px;
  padding: 60px 30px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 2px solid rgba(37, 99, 235, 0.1);
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 16px;
}

.empty-icon {
  font-size: 32px;
  margin-bottom: 16px;
}

.catalog-product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

/* Premium Dark Card Product matching the user's screenshot exactly */
.premium-product-card {
  background: rgba(226, 232, 240, 0.94);
  border: 1px solid rgba(148, 163, 184, 0.26);
  border-radius: 12px;
  overflow: visible;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
  padding: 10px;
}

.premium-product-card:hover {
  background: rgba(241, 245, 249, 0.98);
  border-color: rgba(37, 99, 235, 0.32);
  transform: translateY(-3px);
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.14);
}

.card-badge-overlay {
  position: absolute;
  top: 16px;
  left: 16px;
  z-index: 3;
  display: flex;
  gap: 6px;
}

.badge-best-seller {
  background: #f59e0b; /* Solid refined gold (no shiny glow) */
  color: #0f172a;
  font-size: 10px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

/* Perfect White Square Container for image */
.card-media-box {
  width: 100%;
  aspect-ratio: 1 / 0.78;
  background: #ffffff; /* Solid White square */
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.card-media-box img {
  max-width: 86%;
  max-height: 82%;
  object-fit: contain;
}

/* Hover overlay on image container */
.card-hover-overlay {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.24);
  display: block;
  opacity: 0;
  transition: opacity 0.25s ease;
  z-index: 2;
  border-radius: 10px;
}

.card-media-box:hover .card-hover-overlay {
  opacity: 1;
}

.hover-heart-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  opacity: 0;
  transform: translateY(-4px);
  z-index: 4;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
}

.card-media-box:hover .hover-heart-btn {
  opacity: 1;
  transform: translateY(0);
}

.hover-heart-btn svg {
  width: 16px;
  height: 16px;
}

.hover-heart-btn:hover {
  background: #ef4444;
  border-color: #ef4444;
  transform: translateY(0) scale(1.06);
}

.hover-quick-view-btn {
  position: absolute;
  left: 16px;
  bottom: 16px;
  background: transparent;
  color: #ffffff;
  font-weight: 750;
  font-size: 13px;
  border: none;
  border-radius: 0;
  padding: 0;
  cursor: pointer;
  transition: all 0.2s ease;
  text-shadow: 0 2px 8px rgba(15, 23, 42, 0.35);
}

.hover-quick-view-btn:hover {
  background: transparent;
  color: #2563eb;
  text-decoration: underline;
  text-underline-offset: 4px;
}

.card-body-content {
  padding: 9px 2px 2px;
  display: flex;
  flex-direction: column;
  flex: 1;
  position: relative;
}

.product-card-title {
  font-size: 13px;
  font-weight: 700;
  line-height: 1.35;
  color: #0f172a;
  margin-bottom: 6px;
  height: 36px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Price row with discount badge */
.card-price-row {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.footer-price-curr {
  font-size: 15.5px;
  font-weight: 700;
  color: #0f172a;
}

.badge-discount {
  background: rgba(239, 68, 68, 0.05);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #b91c1c;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 5px;
  border-radius: 3px;
}

.footer-price-old {
  font-size: 11.5px;
  color: #475569;
  text-decoration: line-through;
  font-weight: 500;
  margin-top: 2px;
  display: block;
}

.installment-text {
  font-size: 11.5px;
  color: #334155;
  margin-top: 3px;
  font-weight: 500;
  display: block;
}

/* Checkmark badge Chính Hãng */
.card-badge-row-1 {
  margin-top: 6px;
}

.badge-chinh-hang {
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

.check-icon-svg {
  width: 10px;
  height: 10px;
  stroke: #60a5fa;
}

/* Row 2 badges (Freeship & BH) */
.card-badge-row-2 {
  display: flex;
  gap: 4px;
  margin-top: 5px;
  flex-wrap: wrap;
}

.badge-ship-warranty {
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

.badge-icon {
  font-size: 11px;
}

/* Floating Cart button in bottom right corner on hover */
.card-hover-cart-btn {
  position: absolute;
  right: 8px;
  bottom: 8px;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #2563eb;
  color: #ffffff;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);
  opacity: 0;
  transform: scale(0.8);
  transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  cursor: pointer;
  z-index: 5;
}

.card-hover-cart-btn svg {
  width: 15px;
  height: 15px;
}

.premium-product-card:hover .card-hover-cart-btn {
  opacity: 1;
  transform: scale(1);
}

.card-hover-cart-btn:hover {
  background: #1d4ed8;
  transform: scale(1.06);
}

/* Pagination */
.catalog-pagination-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 48px;
}

.pagination-arrow,
.pagination-number {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.06);
  color: #cbd5e1;
  width: 36px;
  height: 36px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination-arrow:hover:not(:disabled),
.pagination-number:hover {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
}

.pagination-number.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
}

.pagination-arrow:disabled {
  opacity: 0.25;
  cursor: not-allowed;
}

/* ============================================================
   7. SHOWROOM IMMERSIVE SECTION
   ============================================================ */
.showroom-immersive-banner {
  margin: 50px auto 30px;
  max-width: 1300px;
  border-radius: 24px;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.05);
  position: relative;
  overflow: hidden;
  padding: 48px;
}

.showroom-immersive-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  align-items: center;
  position: relative;
  z-index: 2;
}

.showroom-tag-neon {
  color: #3b82f6;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.05em;
  display: block;
}

.showroom-immersive-copy h2 {
  font-size: 28px;
  margin: 10px 0 16px;
  color: #ffffff;
}

.showroom-highlights-checklist {
  display: grid;
  gap: 16px;
  margin-bottom: 28px;
}

.showroom-highlight-card {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}

.highlight-icon {
  background: rgba(37, 99, 235, 0.1);
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 11px;
}

.showroom-highlight-card strong {
  display: block;
  font-size: 14px;
  margin-bottom: 2px;
}

.showroom-highlight-card p {
  font-size: 12.5px;
  margin: 0;
}

.showroom-immersive-visual {
  width: 100%;
  height: 320px;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.showroom-immersive-visual img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ============================================================
   8. COMPOSITE SERVICES GRID SYSTEM (DỊCH VỤ XỨNG TẦM)
   ============================================================ */
.services-grid-header {
  text-align: center;
  margin-bottom: 32px;
}

.services-system-label {
  color: #3b82f6;
  font-weight: 700;
  font-size: 12px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  display: block;
  margin-bottom: 8px;
}

.services-grid-header h2 {
  font-size: 28px;
  margin: 0;
}

.services-composite-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 24px;
  max-width: 1300px;
  margin: 0 auto;
  align-items: stretch;
}

.composite-card {
  background: #111a2d;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 18px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 14px 34px rgba(2, 6, 23, 0.16);
}

.composite-card:hover {
  background: #15213a;
  border-color: rgba(96, 165, 250, 0.24);
  transform: translateY(-2px);
  box-shadow: 0 18px 42px rgba(2, 6, 23, 0.24);
}

.large-box-left {
  height: 100%;
  min-height: 520px;
}

.composite-card-info {
  padding: 28px;
  min-height: 150px;
}

.box-badge-blue {
  background: rgba(37, 99, 235, 0.1);
  border: 1px solid rgba(37, 99, 235, 0.2);
  color: #60a5fa;
  font-size: 10px;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 4px;
  display: inline-block;
  margin-bottom: 12px;
}

.composite-card-info h3 {
  font-size: 20px;
  line-height: 1.25;
  margin: 0 0 10px;
  color: #ffffff;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.28);
}

.composite-card-info p {
  font-size: 13px;
  line-height: 1.65;
  margin: 0;
  color: #d8e2f0;
}

.composite-card-media {
  flex: 1;
  width: 100%;
  min-height: 300px;
  overflow: hidden;
}

.composite-card-media img,
.composite-card-media-half img,
.composite-card-media-wide img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.35s ease, filter 0.35s ease;
}

.composite-card:hover img {
  transform: scale(1.035);
  filter: saturate(1.05) contrast(1.02);
}

/* Right stacked composite */
.composite-right-stack {
  display: grid;
  grid-template-rows: 1fr 1fr;
  gap: 24px;
  min-height: 520px;
}

.composite-row-top {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  min-height: 248px;
}

.card-half-top {
  height: 100%;
}

.card-half-top .composite-card-info {
  padding: 24px;
  min-height: 116px;
}

.card-half-top .composite-card-info h3 {
  font-size: 16px;
  line-height: 1.35;
  margin-bottom: 8px;
}

.card-half-top .composite-card-info p {
  font-size: 12px;
  line-height: 1.55;
}

.composite-card-media-half {
  flex: 1;
  min-height: 132px;
  overflow: hidden;
}

/* Bottom wide box */
.card-full-bottom {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  align-items: center;
  height: 100%;
  min-height: 248px;
}

.card-full-bottom .composite-card-info {
  padding: 28px;
  min-height: 0;
}

.composite-card-media-wide {
  width: 100%;
  height: 100%;
  min-height: 248px;
  overflow: hidden;
}

/* ============================================================
   9. RESPONSIVE MEDIA QUERIES
   ============================================================ */
@media (max-width: 1200px) {
  .hero-banner {
    grid-template-columns: 1fr;
    min-height: auto;
    padding: 36px 24px 34px;
  }
  
  .hero-visual {
    height: 300px;
  }

  .glass-container-details {
    grid-template-columns: 1fr;
    gap: 30px;
  }

  .catalog-product-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
  }

  .services-composite-grid {
    grid-template-columns: 1fr;
  }
  
  .large-box-left {
    height: 420px;
  }
}

@media (max-width: 992px) {
  .brand-logos-container {
    justify-content: center;
  }

  .services-pills-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .catalog-layout {
    grid-template-columns: 1fr;
  }

  .catalog-filter-sidebar {
    position: static;
  }

  .catalog-product-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
  }

  .showroom-immersive-container {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .hero-banner {
    padding: 28px 18px 28px;
    gap: 24px;
    width: 100%;
    margin-left: 0;
    transform: none;
  }

  .hero-banner h1 {
    font-size: 2rem;
  }

  .hero-specs-grid {
    grid-template-columns: 1fr;
  }

  .hero-visual {
    height: 240px;
  }

  .flash-sale-header-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .angle-selectors-row {
    justify-content: center;
    gap: 8px;
  }

  .btn-3d-visual {
    width: 100%;
    margin-top: 10px;
  }

  .catalog-product-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }

  .composite-row-top {
    grid-template-columns: 1fr;
  }
  
  .card-half-top {
    height: auto;
  }

  .card-full-bottom {
    grid-template-columns: 1fr;
    height: auto;
  }

  .composite-card-media-wide {
    height: 160px;
  }
}

@media (max-width: 480px) {
  .services-pills-grid {
    grid-template-columns: 1fr;
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.98); }
  to { opacity: 1; transform: scale(1); }
}
</style>
