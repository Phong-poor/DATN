<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import swal from '@/services/swal'
import { productImageUrl } from '@/services/urls'
import { prefetchProductsPage } from '@/services/productsPrefetch'
import {
  Zap,
  Monitor,
  Award,
  Cpu,
  ArrowRight,
  ShieldCheck,
  Layers,
  TrendingUp,
  Briefcase,
  UserCheck,
  FileText,
  ShoppingBag,
  Heart,
  Star,
  Settings,
  Video,
  Brain,
  Mail,
  Check
} from 'lucide-vue-next'

const router = useRouter()

// ===================== STATE MANAGEMENT =====================
const isLoading = ref(true)
const products = ref([])
const activeBenchmarkTab = ref('render')
const consultantName = ref('')
const consultantPhone = ref('')
const consultantEmail = ref('')
const consultantField = ref('architecture')

// Count-up stats state
const displayedRenderSpeed = ref(0)
const displayedIsvPercent = ref(0)

const statsData = computed(() => [
  { value: `${displayedRenderSpeed.value}%`, label: 'Render nhanh hơn', sub: 'So với thế hệ tiền nhiệm', icon: Zap, color: '#00e5ff' },
  { value: '4K UHD+', label: 'Hỗ trợ màn hình', sub: 'Độ rộng màu 100% DCI-P3', icon: Monitor, color: '#3b82f6' },
  { value: `${displayedIsvPercent.value}%`, label: 'Chứng nhận ISV', sub: 'Tương thích 100% phần mềm', icon: Award, color: '#10b981' },
  { value: 'Xeon / Ultra', label: 'Vi xử lý tối tân', sub: 'Intel Xeon & Core Ultra vPro', icon: Cpu, color: '#8b5cf6' }
])

// ===================== MOCK DATA =====================
const brandLogos = [
  { name: 'Dell Precision', logoText: 'DELL Precision', desc: 'Dòng máy trạm huyền thoại bền bỉ' },
  { name: 'HP ZBook', logoText: 'HP ZBook', desc: 'Thiết kế di động cao cấp' },
  { name: 'Lenovo ThinkPad P', logoText: 'ThinkPad P', desc: 'Bàn phím đỉnh cao, hiệu năng bền bỉ' },
  { name: 'ASUS ProArt', logoText: 'ASUS ProArt', desc: 'Dành riêng cho nhà sáng tạo nội dung' },
  { name: 'MSI Creator', logoText: 'MSI Creator', desc: 'Sự pha trộn hoàn hảo giữa game & đồ họa' },
  { name: 'Acer ConceptD', logoText: 'Acer ConceptD', desc: 'Màn hình Pantone chuẩn màu tuyệt đối' }
]

const categoriesData = [
  { id: 'arch', title: 'Kiến Trúc & Quy Hoạch', desc: 'Đồ họa vector, dựng hình CAD 3D và render thời gian thực không độ trễ.', img: 'https://images.unsplash.com/photo-1503387762-592ded58c45a?w=600&q=80', query: 'CAD' },
  { id: 'film', title: 'Sản Xuất Phim & VFX', desc: 'Xử lý video RAW 8K, dựng phim đa luồng và tạo hiệu ứng kỹ xảo điện ảnh phức tạp.', img: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=600&q=80', query: 'dựng phim' },
  { id: 'design', title: 'Thiết Kế Đồ Họa & 3D', desc: 'Chỉnh sửa ảnh siêu phân giải, vẽ kỹ thuật số và thiết kế bao bì quảng cáo chuyên sâu.', img: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&q=80', query: 'đồ họa' },
  { id: 'ai', title: 'AI & Data Science', desc: 'Huấn luyện mô hình Deep Learning, xử lý tập dữ liệu lớn và chạy thuật toán tối ưu NPU.', img: 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=600&q=80', query: 'AI' }
]

const segOffers = [
  {
    title: 'Kiến Trúc & Kỹ Thuật',
    icon: Settings,
    desc: 'Thiết kế tối ưu cho các phần mềm CAD và dựng mô hình kỹ thuật số chuyên sâu.',
    softwares: ['AutoCAD', 'Sketchup', 'Revit', 'Lumion', 'V-Ray'],
    hardwareSpec: 'Khuyên dùng GPU NVIDIA RTX vPro & CPU Intel Core i7/i9 HX.'
  },
  {
    title: 'Thiết Kế & Sản Xuất Nội Dung',
    icon: Video,
    desc: 'Được thiết kế để tối đa hóa tốc độ biên tập video và độ chuẩn xác của màu sắc hiển thị.',
    softwares: ['Photoshop', 'Illustrator', 'Premiere Pro', 'After Effects', 'DaVinci'],
    hardwareSpec: 'Khuyên dùng Màn hình OLED chuẩn màu Pantone & RAM tối thiểu 32GB.'
  },
  {
    title: 'AI & Deep Learning Engineer',
    icon: Brain,
    desc: 'Đáp ứng các thuật toán xử lý dữ liệu nặng và huấn luyện mô hình học máy cục bộ.',
    softwares: ['Python', 'TensorFlow', 'PyTorch', 'Jupyter', 'Docker'],
    hardwareSpec: 'Khuyên dùng GPU tối thiểu 16GB VRAM & RAM hệ thống 64GB trở lên.'
  }
]

const benchmarkData = {
  render: {
    title: '3D Rendering Speed (V-Ray / Blender Cycles)',
    unit: 'Điểm số hiệu năng (Càng cao càng tốt)',
    items: [
      { name: 'NVIDIA RTX 5000 Ada (Workstation Flagship)', score: 100, color: '#00f2fe' },
      { name: 'NVIDIA RTX 4000 Ada (Workstation Mid-range)', score: 78, color: '#3b82f6' },
      { name: 'NVIDIA RTX 4070 (Gaming Standard Laptop)', score: 55, color: '#64748b' },
      { name: 'Intel Iris Xe Graphics (Office Standard Laptop)', score: 12, color: '#475569' }
    ]
  },
  video: {
    title: '8K Video Editing & Grading (DaVinci Resolve)',
    unit: 'FPS trung bình (Càng cao càng tốt)',
    items: [
      { name: 'NVIDIA RTX 5000 Ada (Workstation Flagship)', score: 92, color: '#00f2fe' },
      { name: 'NVIDIA RTX 4000 Ada (Workstation Mid-range)', score: 72, color: '#3b82f6' },
      { name: 'NVIDIA RTX 4070 (Gaming Standard Laptop)', score: 60, color: '#64748b' },
      { name: 'Intel Iris Xe Graphics (Office Standard Laptop)', score: 8, color: '#475569' }
    ]
  },
  modeling: {
    title: '3D CAD Modeling Viewport FPS (SolidWorks / Catia)',
    unit: 'FPS khung nhìn thực tế (Càng cao càng tốt)',
    items: [
      { name: 'NVIDIA RTX 5000 Ada (Workstation Flagship)', score: 100, color: '#00f2fe' },
      { name: 'NVIDIA RTX 4000 Ada (Workstation Mid-range)', score: 85, color: '#3b82f6' },
      { name: 'NVIDIA RTX 4070 (Gaming Standard Laptop)', score: 40, color: '#64748b' },
      { name: 'Intel Iris Xe Graphics (Office Standard Laptop)', score: 15, color: '#475569' }
    ]
  },
  ai: {
    title: 'AI Model Training (PyTorch Epoch Duration)',
    unit: 'Tốc độ hoàn thành mẫu / giây (Càng cao càng tốt)',
    items: [
      { name: 'NVIDIA RTX 5000 Ada (Workstation Flagship)', score: 98, color: '#00f2fe' },
      { name: 'NVIDIA RTX 4000 Ada (Workstation Mid-range)', score: 76, color: '#3b82f6' },
      { name: 'NVIDIA RTX 4070 (Gaming Standard Laptop)', score: 48, color: '#64748b' },
      { name: 'Intel Iris Xe Graphics (Office Standard Laptop)', score: 5, color: '#475569' }
    ]
  }
}

const caseStudies = [
  { id: 1, title: 'Dự án Căn Hộ Vinhomes Golden River', category: 'Thiết Kế Kiến Trúc & Quy Hoạch', desc: 'Dựng hình Revit và render Lumion 3D real-time toàn khu đô thị với Dell Precision 7680.', img: 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=600&q=80' },
  { id: 2, title: 'Phim Kỹ Xảo "Huyền Thoại Trỗi Dậy"', category: 'VFX & Hậu Kỳ Điện Ảnh', desc: 'Xử lý biên tập video RAW 8K đa camera và dựng hình nhân vật 3D trên ASUS ProArt Studiobook 16.', img: 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=600&q=80' },
  { id: 3, title: 'Hệ Thống Phân Tích Hành Vi Bán Lẻ', category: 'AI & Data Science', desc: 'Huấn luyện mô hình thị giác máy tính nhận diện khách hàng thực tế sử dụng card trạm NVIDIA RTX Ada.', img: 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=600&q=80' }
]

const articles = [
  { id: 10, title: 'Đánh giá Dell Precision 7680: Máy trạm di động mạnh nhất thế giới 2026', desc: 'Sức mạnh vượt trội từ CPU Intel Core i9 HX và card đồ họa chuyên nghiệp NVIDIA RTX 5000 Ada.', author: 'Hoàng Minh', date: '01/06/2026', img: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=400' },
  { id: 11, title: 'Chứng nhận ISV là gì? Tại sao người dùng thiết kế CAD bắt buộc phải dùng Workstation?', desc: 'Chi tiết lợi ích chứng nhận tối ưu hóa từ Adobe, Autodesk và Dassault Systèmes.', author: 'Khánh An', date: '28/05/2026', img: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400' },
  { id: 12, title: 'So sánh GPU NVIDIA GeForce RTX và NVIDIA RTX A-Series (Ada Generation)', desc: 'Sự khác biệt về VRAM ECC, độ tin cậy và khả năng tăng tốc Render chuyên sâu.', author: 'Lâm Bách', date: '24/05/2026', img: 'https://images.unsplash.com/photo-1591488320449-011701bb6704?w=400' }
]

// ===================== DỮ LIỆU ĐỘNG & ĐỒNG BỘ BACKEND =====================
const loadPageData = async () => {
  isLoading.value = true
  try {
    const cache = await prefetchProductsPage()
    if (cache && cache.productsRaw && cache.productsRaw.length > 0) {
      // Lọc các sản phẩm thuộc danh mục Workstation hoặc thương hiệu máy trạm nổi tiếng
      const wsProducts = cache.productsRaw.filter(p => {
        const cat = (p.danh_muc?.ten_danhmuc || p.danhmuc?.tenDM || p.category || '').toLowerCase()
        const name = (p.tenSP || '').toLowerCase()
        return cat.includes('workstation') || cat.includes('máy trạm') || name.includes('precision') || name.includes('zbook') || name.includes('thinkpad p') || name.includes('proart') || name.includes('creator') || name.includes('conceptd')
      })

      if (wsProducts.length > 0) {
        products.value = wsProducts.map(p => {
          let generalSpecs = []
          try {
            const tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || [])
            if (Array.isArray(tskt)) {
              generalSpecs = tskt.map(item => item.giatri).filter(Boolean)
            }
          } catch (e) {}

          const giaSP = p.bien_thes && p.bien_thes.length > 0 ? p.bien_thes[0].gia : (p.gia || 65000000)
          const firstVariant = p.bien_thes && p.bien_thes.length > 0 ? p.bien_thes[0] : null

          let ram = '32GB'
          let ssd = '1TB'
          let cpu = 'Intel Core i9'
          let gpu = 'NVIDIA RTX Ada'

          if (p.bien_thes && p.bien_thes.length > 0) {
            try {
              const bt = p.bien_thes[0]
              const tt = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || [])
              if (Array.isArray(tt)) {
                tt.forEach(attr => {
                  const attrName = (attr.ten_thuoctinh || '').toLowerCase()
                  if (attrName.includes('ram')) ram = attr.giatri
                  if (attrName.includes('ssd') || attrName.includes('ổ cứng')) ssd = attr.giatri
                  if (attrName.includes('cpu')) cpu = attr.giatri
                  if (attrName.includes('gpu') || attrName.includes('card')) gpu = attr.giatri
                })
              }
            } catch (e) {}
          }

          return {
            id: p.id_sanpham,
            tenSP: p.tenSP,
            brand: p.thuong_hieu?.ten_thuonghieu || p.thuonghieu?.tenTH || p.brand || 'Dell',
            category: 'Workstation',
            gia: giaSP,
            oldPrice: Math.floor(giaSP * 1.1),
            specs: generalSpecs.length > 0 ? generalSpecs.slice(0, 4) : [cpu, gpu, ram, ssd],
            image: productImageUrl(p, firstVariant, 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=500'),
            rating: 4.9,
            reviews: Math.floor(Math.random() * 30) + 12,
            promo: p.mota_ngan || 'Hỗ trợ trả góp 0% lãi suất + Tặng balo trạm cao cấp',
            inStock: p.trangthai === 'hoat_dong' || p.soluong > 0
          }
        })
      } else {
        products.value = generateFallbackWorkstations()
      }
    } else {
      products.value = generateFallbackWorkstations()
    }
  } catch (err) {
    console.error('Lỗi khi tải danh sách sản phẩm Workstation:', err)
    products.value = generateFallbackWorkstations()
  } finally {
    isLoading.value = false
    nextTick(() => {
      initScrollReveal()
      initStatsObserver()
    })
  }
}

function generateFallbackWorkstations() {
  return [
    { id: 201, tenSP: 'Laptop Dell Precision 7680 Flagship Workstation', brand: 'Dell', category: 'Workstation', gia: 115000000, oldPrice: 125000000, specs: ['Intel Core i9-13950HX', 'NVIDIA RTX 5000 Ada 16GB', '64GB RAM DDR5', '2TB SSD Gen 4'], image: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500', rating: 5.0, reviews: 24, promo: 'Tặng kèm màn hình Dell UltraSharp 24 inch', inStock: true },
    { id: 202, tenSP: 'ASUS ProArt Studiobook 16 OLED Creators', brand: 'ASUS', category: 'Workstation', gia: 68990000, oldPrice: 75900000, specs: ['Intel Core Ultra 9 185H', 'NVIDIA RTX 4070 8GB', '32GB RAM', '1TB SSD', '16" 3.2K OLED 120Hz'], image: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=500', rating: 4.9, reviews: 18, promo: 'Tặng kèm bảng vẽ Wacom Intuos', inStock: true },
    { id: 203, tenSP: 'Laptop Lenovo ThinkPad P16 Gen 2 Core i9', brand: 'Lenovo', category: 'Workstation', gia: 85990000, oldPrice: 93900000, specs: ['Intel Core i9-13980HX', 'NVIDIA RTX 3500 Ada 12GB', '32GB RAM DDR5', '1TB SSD'], image: 'https://images.unsplash.com/photo-1585776245991-cf89dd7fc73a?w=500', rating: 4.9, reviews: 15, promo: 'Trả góp 0% lãi suất trong 12 tháng', inStock: true },
    { id: 204, tenSP: 'HP ZBook Fury 16 G10 Premium Mobile Workstation', brand: 'HP', category: 'Workstation', gia: 52990000, oldPrice: 58900000, specs: ['Intel Core i7-13850HX', 'NVIDIA RTX A2000 8GB', '32GB RAM', '1TB SSD'], image: 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=500', rating: 4.8, reviews: 14, promo: 'Tặng balo trạm HP Business + Chuột ko dây', inStock: true },
    { id: 205, tenSP: 'Laptop MSI Creator 16 AI Studio OLED', brand: 'MSI', category: 'Workstation', gia: 74990000, oldPrice: 81900000, specs: ['Core Ultra 9', 'RTX 4080 12GB', '32GB RAM', '2TB SSD', '16" MiniLED 4K'], image: 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500', rating: 4.8, reviews: 12, promo: 'Tặng chuột MSI Creator Mouse', inStock: true },
    { id: 206, tenSP: 'Acer ConceptD 5 Pro Special Edition', brand: 'Acer', category: 'Workstation', gia: 58990000, oldPrice: 64900000, specs: ['Intel Core i7', 'NVIDIA RTX A3000 12GB', '32GB RAM', '1TB SSD', 'Pantone Certified'], image: 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?w=500', rating: 4.7, reviews: 10, promo: 'Tặng gói phần mềm bản quyền Adobe 1 năm', inStock: true },
    { id: 207, tenSP: 'Laptop Dell Precision 5480 Ultra-Mobile Workstation', brand: 'Dell', category: 'Workstation', gia: 42500000, oldPrice: 47900000, specs: ['Intel Core i7-13800H', 'NVIDIA RTX A1000 6GB', '16GB RAM', '512GB SSD', '14" FHD+'], image: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500', rating: 4.8, reviews: 20, promo: 'Tặng túi chống sốc cao cấp', inStock: true },
    { id: 208, tenSP: 'HP ZBook Studio G10 Thin & Light Creators', brand: 'HP', category: 'Workstation', gia: 62990000, oldPrice: 68900000, specs: ['Intel Core i9-13900H', 'RTX 4070 8GB', '32GB RAM', '1TB SSD', '16" 120Hz Screen'], image: 'https://images.unsplash.com/photo-1585776245991-cf89dd7fc73a?w=500', rating: 4.9, reviews: 11, promo: 'Tặng cổng chuyển đổi USB-C Hub 6-in-1', inStock: true }
  ]
}

// ===================== ACTIONS & BEHAVIORS =====================
const handleAddToCart = async (product) => {
  const token = localStorage.getItem('token')
  if (!token) {
    swal.confirm('Yêu cầu đăng nhập', 'Vui lòng đăng nhập trước khi thêm sản phẩm vào giỏ hàng!', 'Đăng nhập')
      .then((isConfirmed) => {
        if (isConfirmed) {
          router.push({ path: '/login', query: { redirect: '/workstation' } })
        }
      })
    return
  }

  try {
    let variantId = product.id
    const res = await api.get(`/sanpham/${product.id}`, { skipGlobalLoader: true })
    if (res.data) {
      const variants = res.data.bien_thes || res.data.bienThes || []
      if (variants.length > 0) {
        variantId = variants[0].id_bienthe
      }
    }

    await api.post('/gio-hang/them', {
      id_bienthe: variantId,
      soluong: 1
    })

    swal.toast(`Đã thêm ${product.tenSP} vào giỏ hàng!`, 'success')
    window.dispatchEvent(new Event('cart-updated'))
  } catch (err) {
    console.error('Lỗi thêm giỏ hàng:', err)
    swal.error('Thất bại', err.response?.data?.message || 'Có lỗi xảy ra, vui lòng thử lại sau.')
  }
}

const handleToggleWishlist = async (product) => {
  const token = localStorage.getItem('token')
  if (!token) {
    swal.confirm('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để lưu sản phẩm vào danh sách yêu thích!', 'Đăng nhập')
      .then((isConfirmed) => {
        if (isConfirmed) {
          router.push('/login')
        }
      })
    return
  }

  try {
    let variantId = product.id
    const res = await api.get(`/sanpham/${product.id}`, { skipGlobalLoader: true })
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

    swal.toast(`Đã thêm ${product.tenSP} vào danh sách yêu thích! ❤️`, 'success')
    window.dispatchEvent(new Event('wishlist-updated'))
  } catch (err) {
    console.error('Lỗi thêm danh sách yêu thích:', err)
    swal.info('Thông báo', err.response?.data?.message || 'Sản phẩm đã có sẵn trong danh sách yêu thích.')
  }
}

const handleConsultantSubmit = () => {
  if (!consultantName.value.trim() || !consultantPhone.value.trim()) {
    swal.toast('Vui lòng nhập họ tên và số điện thoại của bạn!', 'error')
    return
  }
  swal.success(
    'Đã gửi yêu cầu thành công! 📞',
    'Các chuyên gia Predator Group sẽ liên hệ lại với bạn trong vòng 15 phút để tư vấn cấu hình tối ưu nhất.'
  )
  consultantName.value = ''
  consultantPhone.value = ''
  consultantEmail.value = ''
}

const handleGetAdviceNow = () => {
  swal.custom({
    title: 'Nhận Tư Vấn Cấu Hình Workstation',
    html: `
      <div style="text-align: left; font-size: 13.5px; color: #64748b; margin-top: 10px;">
        <label style="display: block; margin-bottom: 6px; font-weight: 700; color: #0f172a;">Họ và tên *</label>
        <input id="swal-name" class="swal2-input" style="width: 100%; margin: 0 0 16px; font-size: 13.5px;" placeholder="Nhập họ tên của bạn">
        
        <label style="display: block; margin-bottom: 6px; font-weight: 700; color: #0f172a;">Số điện thoại *</label>
        <input id="swal-phone" class="swal2-input" style="width: 100%; margin: 0 0 16px; font-size: 13.5px;" placeholder="Nhập số điện thoại di động">
        
        <label style="display: block; margin-bottom: 6px; font-weight: 700; color: #0f172a;">Lĩnh vực công việc</label>
        <select id="swal-field" class="swal2-input" style="width: 100%; margin: 0; font-size: 13.5px; height: 42px;">
          <option value="arch">Kiến Trúc & Quy Hoạch</option>
          <option value="vfx">Dựng Phim & VFX</option>
          <option value="graphic">Thiết Kế Đồ Họa 3D</option>
          <option value="ai">AI / Data Science</option>
        </select>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Gửi yêu cầu',
    cancelButtonText: 'Hủy bỏ',
    preConfirm: () => {
      const name = document.getElementById('swal-name').value
      const phone = document.getElementById('swal-phone').value
      const field = document.getElementById('swal-field').value
      if (!name.trim() || !phone.trim()) {
        swal.showValidationMessage('Vui lòng điền đầy đủ họ tên và số điện thoại!')
        return false
      }
      return { name, phone, field }
    }
  }).then((result) => {
    if (result.isConfirmed) {
      swal.success(
        'Đăng ký thành công! 🎉',
        `Predator Group đã tiếp nhận yêu cầu tư vấn cho lĩnh vực công việc của bạn. Chuyên viên sẽ gọi điện cho bạn sớm nhất.`
      )
    }
  })
}

const scrollToSection = (id) => {
  const el = document.getElementById(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price).replace(/\s/g, '')
}

const viewProductDetails = (id) => {
  router.push(`/products/${id}`)
}

// ===================== SCROLL REVEAL & STATS ANIMATIONS =====================
const initScrollReveal = () => {
  const reveals = document.querySelectorAll('.scroll-reveal:not(.active)')
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

const animateStats = () => {
  const duration = 2000
  const fps = 60
  const steps = Math.round(duration / (1000 / fps))
  
  let rStep = 0
  const rTimer = setInterval(() => {
    rStep++
    const progress = rStep === steps ? 1 : 1 - Math.pow(2, -10 * rStep / steps)
    displayedRenderSpeed.value = Math.round(50 * progress)
    if (rStep >= steps) {
      displayedRenderSpeed.value = 50
      clearInterval(rTimer)
    }
  }, 1000 / fps)

  let iStep = 0
  const iTimer = setInterval(() => {
    iStep++
    const progress = iStep === steps ? 1 : 1 - Math.pow(2, -10 * iStep / steps)
    displayedIsvPercent.value = Math.round(100 * progress)
    if (iStep >= steps) {
      displayedIsvPercent.value = 100
      clearInterval(iTimer)
    }
  }, 1000 / fps)
}

const initStatsObserver = () => {
  const section = document.querySelector('.stats-bar-section')
  if (!section) return
  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting) {
        animateStats()
        observer.disconnect()
      }
    },
    { threshold: 0.3 }
  )
  observer.observe(section)
}

onMounted(() => {
  window.scrollTo({ top: 0 })
  loadPageData()
})
</script>

<template>
  <div class="workstation-shell">
    
    <!-- 1. HERO BANNER SECTION (Dark Premium Enterprise Style) -->
    <section class="hero-viewport">
      <div class="cyber-grid-overlay"></div>
      <div class="ambient-glow glow-navy"></div>
      <div class="ambient-glow glow-cyan"></div>
      
      <div class="hero-container">
        <div class="hero-text-block">
          <div class="hero-eyebrow-badge">
            <span class="badge-dot"></span>
            ENTERPRISE CERTIFIED
          </div>
          <h1 class="hero-title">
            WORKSTATION
            <span class="gradient-text">CHUYÊN NGHIỆP</span>
          </h1>
          <p class="hero-description">
            Sức mạnh xử lý tối thượng chuyên sâu dành riêng cho thiết kế kỹ thuật CAD, dựng mô hình 3D, làm kỹ xảo hậu kỳ VFX, trí tuệ nhân tạo AI và khoa học dữ liệu. Tối ưu hóa 100% cùng chứng chỉ tương thích phần mềm ISV.
          </p>
          <div class="hero-action-buttons">
            <button @click="scrollToSection('ws-catalog-section')" class="btn btn-primary-glow">
              <ShoppingBag class="btn-icon" />
              Xem sản phẩm
            </button>
            <button @click="scrollToSection('ws-consultant-section')" class="btn btn-glassmorphism">
              <UserCheck class="btn-icon" />
              Tư vấn cấu hình
            </button>
          </div>
        </div>

        <div class="hero-graphic-block">
          <div class="laptop-showcase">
            <img src="https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&q=80" alt="Workstation 3D Modeling" class="showcase-img" />
            <div class="overlay-isv border-glow">
              <ShieldCheck class="isv-icon text-cyan" />
              <span>ISV Certified System</span>
            </div>
            <div class="overlay-softwares border-glow">
              <span>AutoCAD · Revit · Adobe CC</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 2. THỐNG KÊ NĂNG LỰC SECTION (Home Style) -->
    <section class="stats-bar-section">
      <div class="grid-container">
        <div class="stats-grid scroll-reveal reveal-stagger">
          <div v-for="(stat, i) in statsData" :key="i" class="stat-card">
            <div class="stat-icon-wrapper" :style="{ background: stat.color + '0e', color: stat.color }">
              <component :is="stat.icon" class="stat-icon" />
            </div>
            <div class="stat-content">
              <h3 class="stat-value">{{ stat.value }}</h3>
              <p class="stat-label">{{ stat.label }}</p>
              <span class="stat-sub">{{ stat.sub }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 3. DANH MỤC WORKSTATION SECTION (4 Large Glassmorphism Cards) -->
    <section class="section category-section">
      <div class="grid-container">
        <div class="section-header scroll-reveal reveal-fade-up">
          <span class="section-badge-label">GIẢI PHÁP CHUYÊN BIỆT</span>
          <h2>Phân Loại Theo Ngành Nghề Chuyên Sâu</h2>
          <p>Mỗi cỗ máy trạm được cấu hình để đáp ứng tối đa hiệu năng đặc thù cho từng nhóm công việc.</p>
        </div>

        <div class="category-cards-grid scroll-reveal reveal-stagger">
          <div
            v-for="cat in categoriesData"
            :key="cat.id"
            @click="router.push({ path: '/products', query: { category: 'Workstation', q: cat.query } })"
            class="category-premium-card"
          >
            <div class="card-bg-image" :style="{ backgroundImage: `url(${cat.img})` }"></div>
            <div class="card-gradient-shield"></div>
            <div class="category-card-content">
              <h3>{{ cat.title }}</h3>
              <p>{{ cat.desc }}</p>
              <span class="interactive-anchor">
                Khám phá giải pháp
                <ArrowRight class="anchor-icon" />
              </span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 4. THƯƠNG HIỆU NỔI BẬT SECTION (Logo Loop Carousel) -->
    <section class="brand-carousel-section">
      <div class="grid-container">
        <div class="carousel-headline scroll-reveal">
          <span>PARTNERS & PLATFORMS</span>
          <h3>Thương Hiệu Máy Trạm Đồng Hành Hàng Đầu</h3>
        </div>
        <div class="brand-carousel-track-wrapper scroll-reveal">
          <div class="brand-carousel-track">
            <!-- First loop group -->
            <div v-for="(b, idx) in brandLogos" :key="idx" class="brand-logo-card">
              <span class="logo-text">{{ b.logoText }}</span>
              <span class="logo-desc">{{ b.desc }}</span>
            </div>
            <!-- Second loop group to support infinite animation -->
            <div v-for="(b, idx) in brandLogos" :key="'loop-' + idx" class="brand-logo-card">
              <span class="logo-text">{{ b.logoText }}</span>
              <span class="logo-desc">{{ b.desc }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 5. SẢN PHẨM NỔI BẬT SECTION (Grid 4 Columns) -->
    <section id="ws-catalog-section" class="section products-grid-section">
      <div class="grid-container">
        <div class="section-header center scroll-reveal reveal-fade-up">
          <span class="section-badge-label">HỆ THỐNG MÁY TRẠM</span>
          <h2>Dòng Máy Trạm Workstation Nổi Bật</h2>
          <p>Tuyển chọn các cấu hình máy trạm di động mạnh mẽ nhất thế giới có sẵn tại Predator Group.</p>
        </div>

        <!-- Loading skeleton -->
        <div v-if="isLoading" class="catalog-product-grid scroll-reveal reveal-stagger">
          <div v-for="i in 4" :key="i" class="skeleton-card">
            <div class="skeleton-media"></div>
            <div class="skeleton-text line-1"></div>
            <div class="skeleton-text line-2"></div>
            <div class="skeleton-text line-3"></div>
          </div>
        </div>

        <!-- Catalog Product Grid -->
        <div v-else class="catalog-product-grid scroll-reveal reveal-stagger">
          <article
            v-for="p in products"
            :key="p.id"
            @click="viewProductDetails(p.id)"
            class="premium-product-card ws-card"
          >
            <!-- Badge ISV -->
            <div class="card-badge-overlay">
              <span class="badge-best-seller isv-badge">
                <ShieldCheck class="badge-isv-icon" />
                ISV Certified
              </span>
            </div>

            <!-- Product Image Box -->
            <div class="card-media-box">
              <img :src="p.image" :alt="p.tenSP" />
              
              <button @click.stop="handleToggleWishlist(p)" class="hover-heart-btn" title="Thêm vào yêu thích">
                <Heart class="heart-icon-svg" />
              </button>

              <div class="card-hover-overlay">
                <button @click.stop="viewProductDetails(p.id)" class="hover-quick-view-btn">Cấu hình chi tiết -></button>
              </div>
            </div>

            <!-- Card Body Content -->
            <div class="card-body-content">
              <span class="ws-brand-tag">{{ p.brand }} Workstation</span>
              <h3 class="product-card-title">{{ p.tenSP }}</h3>
              
              <!-- Specs details list -->
              <div class="ws-specs-list">
                <div v-for="(spec, sIdx) in p.specs" :key="sIdx" class="ws-spec-item">
                  <span class="spec-bullet"></span>
                  <span class="spec-val">{{ spec }}</span>
                </div>
              </div>

              <!-- Price row with discount badge -->
              <div class="card-price-row">
                <span class="footer-price-curr">{{ formatPrice(p.gia) }}</span>
                <span class="badge-discount">-10%</span>
              </div>

              <!-- Crossed out price -->
              <span class="footer-price-old">{{ formatPrice(p.oldPrice) }}</span>
              <span class="installment-text">• Hỗ trợ trả góp 0% lãi suất</span>
            </div>

            <!-- Circular Hover Add to Cart button -->
            <button @click.stop="handleAddToCart(p)" class="card-hover-cart-btn" aria-label="Thêm vào giỏ hàng">
              <ShoppingBag />
            </button>
          </article>
        </div>
      </div>
    </section>

    <!-- 6. WORKSTATION CHO TỪNG NHU CẦU SECTION (3 Large Bento Cards) -->
    <section class="section segment-section">
      <div class="grid-container">
        <div class="section-header center scroll-reveal reveal-fade-up">
          <span class="section-badge-label">CẤU HÌNH TỐI ƯU</span>
          <h2>Workstation Cho Từng Nhu Cầu Chuyên Biệt</h2>
          <p>Chúng tôi tinh chỉnh phần cứng nhằm đáp ứng chính xác yêu cầu tài nguyên từ các phần mềm chuyên ngành.</p>
        </div>

        <div class="segment-grid scroll-reveal reveal-stagger">
          <div v-for="(seg, idx) in segOffers" :key="idx" class="segment-card border-glow">
            <div class="segment-header">
              <div class="seg-icon-box">
                <component :is="seg.icon" class="seg-icon" />
              </div>
              <h3>{{ seg.title }}</h3>
            </div>
            <p class="seg-desc">{{ seg.desc }}</p>
            
            <div class="seg-software-box">
              <div class="sw-title">Phần mềm tương thích tối ưu:</div>
              <div class="sw-list">
                <span v-for="sw in seg.softwares" :key="sw" class="sw-tag">
                  <Check class="sw-check-icon" />
                  {{ sw }}
                </span>
              </div>
            </div>

            <div class="seg-footer">
              <span class="spec-label">Thông số kỹ thuật đề xuất:</span>
              <p class="spec-detail">{{ seg.hardwareSpec }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 7. BENCHMARK SECTION (Performance Dashboard style) -->
    <section class="section benchmark-section">
      <div class="grid-container">
        <div class="section-header center scroll-reveal reveal-fade-up">
          <span class="section-badge-label">SO SÁNH HIỆU NĂNG</span>
          <h2>Đo Lường Hiệu Năng Thực Tế</h2>
          <p>Biểu đồ so sánh năng lượng xử lý đo được trên các ứng dụng kỹ thuật và đồ họa chuyên sâu.</p>
        </div>

        <div class="benchmark-dashboard border-glow scroll-reveal">
          <div class="dashboard-sidebar">
            <button
              @click="activeBenchmarkTab = 'render'"
              class="db-tab-btn"
              :class="{ active: activeBenchmarkTab === 'render' }"
            >
              <Zap class="db-icon" />
              3D Rendering
            </button>
            <button
              @click="activeBenchmarkTab = 'video'"
              class="db-tab-btn"
              :class="{ active: activeBenchmarkTab === 'video' }"
            >
              <Video class="db-icon" />
              Video Editing 8K
            </button>
            <button
              @click="activeBenchmarkTab = 'modeling'"
              class="db-tab-btn"
              :class="{ active: activeBenchmarkTab === 'modeling' }"
            >
              <Settings class="db-icon" />
              CAD & 3D Modeling
            </button>
            <button
              @click="activeBenchmarkTab = 'ai'"
              class="db-tab-btn"
              :class="{ active: activeBenchmarkTab === 'ai' }"
            >
              <Brain class="db-icon" />
              AI Deep Learning
            </button>
          </div>

          <div class="dashboard-content">
            <h4>{{ benchmarkData[activeBenchmarkTab].title }}</h4>
            <span class="db-unit">{{ benchmarkData[activeBenchmarkTab].unit }}</span>

            <div class="db-charts-list">
              <div v-for="(item, idx) in benchmarkData[activeBenchmarkTab].items" :key="idx" class="chart-row">
                <div class="chart-info">
                  <span class="chart-item-name">{{ item.name }}</span>
                  <span class="chart-item-score">{{ item.score }}%</span>
                </div>
                <div class="chart-bar-track">
                  <div
                    class="chart-bar-fill"
                    :style="{ width: item.score + '%', background: item.color }"
                  ></div>
                </div>
              </div>
            </div>

            <div class="dashboard-footer-info">
              <ShieldCheck class="db-shield-icon" />
              <span>Kiểm thử được thực hiện trong phòng thí nghiệm Predator Lab dưới sự giám sát chặt chẽ của các kỹ sư hệ thống.</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 8. CASE STUDY SECTION (Real projects in large cards) -->
    <section class="section case-study-section">
      <div class="grid-container">
        <div class="section-header scroll-reveal reveal-fade-up">
          <span class="section-badge-label">DỰ ÁN THỰC TẾ</span>
          <h2>Case Study: Ứng Dụng Trong Thực Tế</h2>
          <p>Xem cách các chuyên gia hàng đầu ứng dụng máy trạm Predator Workstation để hiện thực hóa dự án triệu đô.</p>
        </div>

        <div class="case-study-grid scroll-reveal reveal-stagger">
          <article v-for="cs in caseStudies" :key="cs.id" class="case-card">
            <div class="case-img-box">
              <img :src="cs.img" :alt="cs.title" />
              <span class="case-tag">{{ cs.category }}</span>
            </div>
            <div class="case-info">
              <h3>{{ cs.title }}</h3>
              <p>{{ cs.desc }}</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- 9. TƯ VẤN CHUYÊN GIA SECTION (CTA Form layout) -->
    <section id="ws-consultant-section" class="section consultant-cta-section">
      <div class="grid-container">
        <div class="consultant-box border-glow scroll-reveal">
          <div class="consultant-layout">
            <div class="consultant-headline">
              <span class="subtitle-cyan">PREDATOR EXPERT SUPPORT</span>
              <h2>Không biết chọn cấu hình máy trạm nào?</h2>
              <p>Hệ thống phần cứng máy trạm rất phức tạp. Hãy cung cấp thông tin nhu cầu, chuyên viên hệ thống của Predator Group sẽ gọi điện phân tích và báo giá cấu hình tối ưu nhất dành cho bạn.</p>
              
              <div class="expert-profile">
                <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?w=150" alt="Tech Expert" class="expert-avatar" />
                <div>
                  <strong>Bùi Quang Huy</strong>
                  <span>Trưởng bộ phận Giải pháp doanh nghiệp</span>
                </div>
              </div>
            </div>

            <div class="consultant-form-wrapper">
              <form @submit.prevent="handleConsultantSubmit" class="consultant-form">
                <div class="form-group">
                  <label>Họ và tên *</label>
                  <input type="text" v-model="consultantName" placeholder="Nhập họ tên của bạn" required />
                </div>
                <div class="form-group">
                  <label>Số điện thoại di động *</label>
                  <input type="tel" v-model="consultantPhone" placeholder="Nhập số điện thoại liên hệ" required />
                </div>
                <div class="form-group">
                  <label>Địa chỉ Email (Không bắt buộc)</label>
                  <input type="email" v-model="consultantEmail" placeholder="Nhập địa chỉ email của bạn" />
                </div>
                <div class="form-group">
                  <label>Lĩnh vực chuyên môn</label>
                  <select v-model="consultantField">
                    <option value="architecture">Kiến Trúc & Quy Hoạch</option>
                    <option value="editing">Biên Tập Phim & VFX</option>
                    <option value="graphic">Thiết Kế Đồ Họa 3D</option>
                    <option value="ai">AI / Machine Learning</option>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary-glow w-100 justify-content-center mt-3">
                  Nhận tư vấn cấu hình miễn phí
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 10. TIN TỨC WORKSTATION (Magazine Style) -->
    <section class="section magazine-news-section">
      <div class="grid-container">
        <div class="section-header center scroll-reveal reveal-fade-up">
          <span class="section-badge-label">KNOWLEDGE BASE</span>
          <h2>Workstation Insights Magazine</h2>
          <p>Tin tức công nghệ phần cứng máy trạm, hướng dẫn tối ưu hệ điều hành và đánh giá so sánh GPU chuyên dụng.</p>
        </div>

        <div class="magazine-grid scroll-reveal reveal-stagger">
          <article v-for="art in articles" :key="art.id" @click="router.push('/news')" class="magazine-card">
            <div class="mag-img-box">
              <img :src="art.img" :alt="art.title" />
            </div>
            <div class="mag-body">
              <div class="mag-meta">
                <span>Bởi {{ art.author }}</span>
                <span>•</span>
                <span>{{ art.date }}</span>
              </div>
              <h3>{{ art.title }}</h3>
              <p>{{ art.desc }}</p>
              <span class="mag-link">Đọc bài viết ➔</span>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- 11. FOOTER CTA SECTION (Business Enterprise setup banner) -->
    <section v-if="false" class="ending-cta-section">
      <div class="cta-banner-image-tint"></div>
      <div class="cta-banner-container scroll-reveal">
        <span class="cta-eyebrow">PREDATOR ENTERPRISE SOLUTIONS</span>
        <h2>WORKSTATION</h2>
        <p>Kiến tạo sức mạnh xử lý không giới hạn, đảm bảo hoạt động liên tục 24/7 với chế độ bảo hành chuyên biệt ProSupport.</p>
        <button @click="scrollToSection('ws-catalog-section')" class="btn btn-primary-glow">
          Khám phá ngay
        </button>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* ============================================================
   COLOR SYSTEM & STYLING TOKENS
   ============================================================ */
.workstation-shell {
  --primary-navy: #030e22;
  --secondary-navy: #0a1931;
  --accent-cyan: #00f2fe;
  --accent-blue: #3b82f6;
  --text-white: #ffffff;
  --text-muted: #94a3b8;
  --border-glass: rgba(255, 255, 255, 0.06);
  --bg-dark: #020712;
  --bg-light: #f8fafc;
  
  background-color: var(--bg-dark);
  color: var(--text-white);
  font-family: 'Be Vietnam Pro', sans-serif;
  overflow-x: hidden;
}

.grid-container {
  width: min(1280px, calc(100% - 32px));
  margin: 0 auto;
}

.section {
  padding: 80px 0;
}

.section-header {
  margin-bottom: 48px;
}
.section-header.center {
  text-align: center;
}
.section-badge-label {
  display: inline-block;
  background: rgba(0, 242, 254, 0.08);
  border: 1px solid rgba(0, 242, 254, 0.25);
  color: var(--accent-cyan);
  font-size: 11px;
  font-weight: 800;
  padding: 4px 12px;
  border-radius: 99px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 12px;
}
.section-header h2 {
  font-size: clamp(28px, 3.5vw, 36px);
  font-weight: 800;
  color: #ffffff;
  margin: 6px 0 12px;
  letter-spacing: -0.02em;
}
.section-header p {
  color: var(--text-muted);
  font-size: 15px;
  max-width: 620px;
  margin: 0;
}
.section-header.center p {
  margin: 0 auto;
}

/* ============================================================
   1. HERO SECTION (ASUS ProArt / Dell Precision Dark Premium Style)
   ============================================================ */
.hero-viewport {
  background-color: #020712;
  background-image:
    linear-gradient(90deg, rgba(2, 7, 18, 0.78) 0%, rgba(2, 7, 18, 0.52) 43%, rgba(2, 7, 18, 0.16) 100%),
    linear-gradient(180deg, rgba(2, 7, 18, 0.02) 0%, rgba(2, 7, 18, 0.46) 100%),
    url('/Gemini_Generated_Image_dp15ytdp15ytdp15.png');
  background-size: cover;
  background-position: center 46%;
  position: relative;
  min-height: clamp(430px, 50vh, 560px);
  display: flex;
  align-items: center;
  padding: 48px 0;
  overflow: hidden;
}
.cyber-grid-overlay {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(rgba(255,255,255,0.003) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,0.003) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none;
  opacity: 0.18;
}
.ambient-glow {
  position: absolute;
  width: 520px;
  height: 520px;
  border-radius: 50%;
  filter: blur(120px);
  pointer-events: none;
  opacity: 0.06;
}
.glow-navy {
  top: -10%;
  left: 10%;
  background: radial-gradient(circle, var(--accent-blue) 0%, transparent 70%);
}
.glow-cyan {
  bottom: -20%;
  right: 15%;
  background: radial-gradient(circle, var(--accent-cyan) 0%, transparent 70%);
}
.hero-container {
  width: min(1280px, calc(100% - 32px));
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  gap: 40px;
  align-items: center;
  position: relative;
  z-index: 2;
}
.hero-text-block {
  max-width: 620px;
}
.hero-eyebrow-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(15, 23, 42, 0.32);
  border: 1px solid rgba(226, 232, 240, 0.34);
  color: #dbe4ef;
  font-size: 11px;
  font-weight: 800;
  padding: 5px 13px;
  border-radius: 99px;
  letter-spacing: 0.1em;
  margin-bottom: 14px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
}
.badge-dot {
  width: 8px;
  height: 8px;
  background: #dbe4ef;
  border-radius: 50%;
  box-shadow: 0 0 10px rgba(226, 232, 240, 0.85);
  animation: pulse-glow 2.5s infinite;
}
@keyframes pulse-glow {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.3; transform: scale(0.85); }
}
.hero-title {
  font-size: clamp(34px, 4vw, 52px);
  font-weight: 850;
  line-height: 1.1;
  letter-spacing: 0;
  margin-bottom: 16px;
  text-transform: uppercase;
  color: #eef2f7;
  text-shadow: 0 12px 36px rgba(0, 0, 0, 0.55);
}
.gradient-text {
  display: block;
  background: linear-gradient(115deg, #ffffff 0%, #b8c2cf 36%, #f8fafc 50%, #8d99a8 72%, #eef2f7 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  color: #dbe4ef;
  text-shadow: none;
}
.hero-description {
  color: #c2ccd8;
  font-size: 14.5px;
  line-height: 1.65;
  margin-bottom: 24px;
  max-width: 600px;
}
.hero-action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.hero-graphic-block {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}
.laptop-showcase {
  position: relative;
  width: 100%;
  max-width: 430px;
  border-radius: 16px;
  border: 1px solid rgba(226, 232, 240, 0.22);
  box-shadow: 0 18px 42px rgba(0,0,0,0.34);
  background: rgba(2, 7, 18, 0.18);
  padding: 12px;
}
.showcase-img {
  width: 100%;
  height: 220px;
  border-radius: 10px;
  object-fit: cover;
  display: block;
}
.overlay-isv {
  position: absolute;
  top: -16px;
  right: -10px;
  background: rgba(15, 23, 42, 0.78);
  border-radius: 10px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 12px;
  font-weight: 700;
  color: #e5e7eb;
}
.isv-icon {
  width: 18px;
  height: 18px;
}
.overlay-softwares {
  position: absolute;
  bottom: -16px;
  left: -10px;
  background: rgba(15, 23, 42, 0.78);
  border-radius: 10px;
  padding: 9px 14px;
  font-size: 12px;
  font-weight: 600;
  color: #dbe4ef;
}

/* ============================================================
   2. STATS BAR SECTION
   ============================================================ */
.stats-bar-section {
  background-color: var(--secondary-navy);
  border-top: 1px solid var(--border-glass);
  border-bottom: 1px solid var(--border-glass);
  padding: 28px 0;
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
}
.stat-icon-wrapper {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-icon {
  width: 24px;
  height: 24px;
}
.stat-value {
  font-size: 22px;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 2px;
}
.stat-label {
  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  margin: 0;
}
.stat-sub {
  color: var(--text-muted);
  font-size: 11px;
  display: block;
}

/* ============================================================
   3. CATEGORY SECTION (Phân khúc ngành nghề)
   ============================================================ */
.category-section {
  background-color: var(--bg-dark);
}
.category-cards-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}
@media (max-width: 1024px) {
  .category-cards-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
  .category-cards-grid { grid-template-columns: 1fr; }
}

.category-premium-card {
  position: relative;
  height: 280px;
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid var(--border-glass);
  cursor: pointer;
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.category-premium-card:hover {
  transform: translateY(-6px);
}
.card-bg-image {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  transition: transform 0.4s ease;
}
.category-premium-card:hover .card-bg-image {
  transform: scale(1.06);
}
.card-gradient-shield {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(3, 14, 34, 0.95) 15%, rgba(3, 14, 34, 0.4) 60%, transparent 100%);
  z-index: 1;
}
.category-card-content {
  position: absolute;
  left: 20px;
  bottom: 20px;
  right: 20px;
  z-index: 2;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.category-card-content h3 {
  font-size: 17px;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
}
.category-card-content p {
  font-size: 12px;
  color: #94a3b8;
  margin: 0;
  line-height: 1.5;
  height: 36px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.interactive-anchor {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 700;
  color: var(--accent-cyan);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 4px;
}
.anchor-icon {
  width: 14px;
  height: 14px;
  transition: transform 0.2s ease;
}
.category-premium-card:hover .anchor-icon {
  transform: translateX(4px);
}

/* ============================================================
   4. BRAND CAROUSEL SECTION (Infinite logo track)
   ============================================================ */
.brand-carousel-section {
  padding: 40px 0 60px;
  background: #030a18;
  border-top: 1px solid var(--border-glass);
  border-bottom: 1px solid var(--border-glass);
}
.carousel-headline {
  text-align: center;
  margin-bottom: 32px;
}
.carousel-headline span {
  font-size: 10px;
  font-weight: 800;
  color: var(--accent-cyan);
  letter-spacing: 0.1em;
}
.carousel-headline h3 {
  font-size: 18px;
  font-weight: 800;
  color: #ffffff;
  margin: 4px 0 0;
}
.brand-carousel-track-wrapper {
  overflow: hidden;
  position: relative;
  width: 100%;
}
.brand-carousel-track-wrapper::before,
.brand-carousel-track-wrapper::after {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  width: 120px;
  z-index: 2;
  pointer-events: none;
}
.brand-carousel-track-wrapper::before {
  left: 0;
  background: linear-gradient(90deg, #030a18 0%, transparent 100%);
}
.brand-carousel-track-wrapper::after {
  right: 0;
  background: linear-gradient(-90deg, #030a18 0%, transparent 100%);
}

.brand-carousel-track {
  display: flex;
  gap: 20px;
  width: max-content;
  animation: logo-scroll 32s linear infinite;
}
@keyframes logo-scroll {
  0% { transform: translateX(0); }
  100% { transform: translateX(calc(-50% - 10px)); }
}

.brand-logo-card {
  width: 220px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-glass);
  border-radius: 12px;
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}
.brand-logo-card .logo-text {
  font-size: 15px;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.01em;
  margin-bottom: 4px;
}
.brand-logo-card .logo-desc {
  font-size: 10.5px;
  color: var(--text-muted);
}

/* ============================================================
   5. SẢN PHẨM NỔI BẬT SECTION (Enterprise Workstation Grid)
   ============================================================ */
.products-grid-section {
  background-color: var(--bg-light);
  border-bottom: 1px solid #e2e8f0;
}
.products-grid-section h2 {
  color: #0f172a;
}
.products-grid-section p {
  color: #64748b;
}

.catalog-product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}
@media (max-width: 1024px) {
  .catalog-product-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
  .catalog-product-grid { grid-template-columns: 1fr; }
}

/* Scoped Workstation Card Layout */
.premium-product-card.ws-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  overflow: visible;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
  padding: 14px;
}
.premium-product-card.ws-card:hover {
  background: #ffffff;
  border-color: rgba(59, 130, 246, 0.32);
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
}
.ws-card .card-media-box {
  background: #f8fafc;
}
.ws-card .product-card-title {
  color: #0f172a;
}
.ws-card .footer-price-curr {
  color: #0f172a;
  font-weight: 800;
}
.ws-card .footer-price-old {
  color: #64748b;
}
.ws-card .installment-text {
  color: #475569;
}

.isv-badge {
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.3);
  color: #10b981;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.badge-isv-icon {
  width: 12px;
  height: 12px;
}

.ws-brand-tag {
  font-size: 11px;
  font-weight: 700;
  color: var(--accent-blue);
  text-transform: uppercase;
  margin-top: 4px;
}
.ws-specs-list {
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin: 12px 0;
  background: #f8fafc;
  padding: 10px;
  border-radius: 8px;
}
.ws-spec-item {
  display: flex;
  align-items: center;
  gap: 6px;
}
.spec-bullet {
  width: 4px;
  height: 4px;
  background: #cbd5e1;
  border-radius: 50%;
}
.spec-val {
  font-size: 11.5px;
  color: #475569;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ============================================================
   6. WORKSTATION CHO TỪNG NHU CẦU (Bento Segment Grid)
   ============================================================ */
.segment-section {
  background-color: #030a18;
  border-top: 1px solid var(--border-glass);
  border-bottom: 1px solid var(--border-glass);
}
.segment-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
}
@media (max-width: 992px) {
  .segment-grid { grid-template-columns: 1fr; }
}

.segment-card {
  background: rgba(13, 27, 50, 0.3);
  border-radius: 20px;
  padding: 32px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.segment-header {
  display: flex;
  align-items: center;
  gap: 16px;
}
.seg-icon-box {
  width: 46px;
  height: 46px;
  background: rgba(0, 242, 254, 0.08);
  border: 1px solid rgba(0, 242, 254, 0.2);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent-cyan);
}
.seg-icon {
  width: 22px;
  height: 22px;
}
.segment-header h3 {
  font-size: 19px;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
}
.seg-desc {
  font-size: 13.5px;
  color: var(--text-muted);
  line-height: 1.6;
  margin: 0;
}
.seg-software-box {
  background: rgba(0,0,0,0.15);
  border-radius: 12px;
  padding: 16px;
}
.sw-title {
  font-size: 12px;
  font-weight: 700;
  color: var(--text-white);
  margin-bottom: 10px;
}
.sw-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.sw-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(255,255,255,0.03);
  border: 1px solid var(--border-glass);
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11.5px;
  color: var(--text-muted);
}
.sw-check-icon {
  width: 12px;
  height: 12px;
  color: var(--accent-cyan);
}
.seg-footer {
  margin-top: auto;
  border-top: 1px solid var(--border-glass);
  padding-top: 16px;
}
.spec-label {
  font-size: 11.5px;
  font-weight: 700;
  color: var(--accent-cyan);
  display: block;
  margin-bottom: 4px;
}
.spec-detail {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0;
}

/* ============================================================
   7. BENCHMARK SECTION (Dashboard performance comparison)
   ============================================================ */
.benchmark-section {
  background-color: var(--bg-dark);
}
.benchmark-dashboard {
  background: rgba(13, 27, 50, 0.25);
  border-radius: 20px;
  display: grid;
  grid-template-columns: 280px 1fr;
  overflow: hidden;
  backdrop-filter: blur(12px);
}
@media (max-width: 768px) {
  .benchmark-dashboard { grid-template-columns: 1fr; }
}

.dashboard-sidebar {
  background: rgba(6, 15, 30, 0.8);
  border-right: 1px solid var(--border-glass);
  padding: 24px 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.db-tab-btn {
  width: 100%;
  background: transparent;
  border: 1px solid transparent;
  color: var(--text-muted);
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 13.5px;
  font-weight: 700;
  text-align: left;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.db-tab-btn:hover {
  background: rgba(255,255,255,0.02);
  color: #ffffff;
}
.db-tab-btn.active {
  background: rgba(0, 242, 254, 0.08);
  border-color: rgba(0, 242, 254, 0.25);
  color: var(--accent-cyan);
}
.db-icon {
  width: 16px;
  height: 16px;
}

.dashboard-content {
  padding: 40px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.dashboard-content h4 {
  font-size: clamp(18px, 2.5vw, 22px);
  font-weight: 800;
  color: #ffffff;
  margin: 0;
}
.db-unit {
  font-size: 12px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.db-charts-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin: 16px 0;
}
.chart-row {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.chart-info {
  display: flex;
  justify-content: space-between;
  font-size: 12.5px;
  font-weight: 600;
}
.chart-item-name {
  color: #ffffff;
}
.chart-item-score {
  color: var(--accent-cyan);
  font-family: monospace;
  font-weight: 700;
}
.chart-bar-track {
  width: 100%;
  height: 8px;
  background: rgba(255,255,255,0.04);
  border-radius: 99px;
  overflow: hidden;
}
.chart-bar-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}
.dashboard-footer-info {
  display: flex;
  align-items: center;
  gap: 8px;
  border-top: 1px solid var(--border-glass);
  padding-top: 16px;
  font-size: 11.5px;
  color: var(--text-muted);
}
.db-shield-icon {
  width: 14px;
  height: 14px;
  color: var(--accent-cyan);
  flex-shrink: 0;
}

/* ============================================================
   8. CASE STUDY SECTION
   ============================================================ */
.case-study-section {
  background-color: #030a18;
  border-top: 1px solid var(--border-glass);
  border-bottom: 1px solid var(--border-glass);
}
.case-study-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
}
@media (max-width: 992px) {
  .case-study-grid { grid-template-columns: 1fr; }
}

.case-card {
  background: rgba(13, 27, 50, 0.25);
  border: 1px solid var(--border-glass);
  border-radius: 16px;
  overflow: hidden;
}
.case-img-box {
  width: 100%;
  height: 200px;
  position: relative;
  overflow: hidden;
}
.case-img-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}
.case-card:hover .case-img-box img {
  transform: scale(1.04);
}
.case-tag {
  position: absolute;
  top: 12px;
  left: 12px;
  background: var(--accent-blue);
  color: #ffffff;
  font-size: 10.5px;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 4px;
  text-transform: uppercase;
}
.case-info {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.case-info h3 {
  font-size: 16px;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  line-height: 1.4;
}
.case-info p {
  font-size: 12.5px;
  color: var(--text-muted);
  margin: 0;
  line-height: 1.65;
}

/* ============================================================
   9. TƯ VẤN CHUYÊN GIA SECTION (CTA Form Layout)
   ============================================================ */
.consultant-cta-section {
  background-color: var(--bg-dark);
}
.consultant-box {
  background: #061124;
  background-image: linear-gradient(135deg, #061124 0%, #030712 100%);
  border-radius: 24px;
  padding: 48px;
  position: relative;
  overflow: hidden;
}
@media (max-width: 600px) {
  .consultant-box { padding: 24px; }
}

.consultant-layout {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 40px;
  align-items: center;
  position: relative;
  z-index: 2;
}
@media (max-width: 992px) {
  .consultant-layout { grid-template-columns: 1fr; gap: 32px; }
}

.consultant-headline {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.subtitle-cyan {
  font-size: 11px;
  font-weight: 800;
  color: var(--accent-cyan);
  letter-spacing: 0.1em;
  text-transform: uppercase;
}
.consultant-headline h2 {
  font-size: clamp(24px, 3.5vw, 32px);
  font-weight: 850;
  color: #ffffff;
  margin: 0;
  line-height: 1.25;
}
.consultant-headline p {
  color: var(--text-muted);
  font-size: 13.5px;
  line-height: 1.6;
  margin: 0;
}

.expert-profile {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 12px;
  background: rgba(255,255,255,0.02);
  border: 1px solid var(--border-glass);
  padding: 14px;
  border-radius: 12px;
  align-self: flex-start;
}
.expert-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}
.expert-profile d {
  display: flex;
  flex-direction: column;
}
.expert-profile strong {
  display: block;
  font-size: 13.5px;
  color: #ffffff;
}
.expert-profile span {
  font-size: 11px;
  color: var(--text-muted);
}

.consultant-form-wrapper {
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid var(--border-glass);
  border-radius: 16px;
  padding: 24px;
  backdrop-filter: blur(10px);
}
.consultant-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-group label {
  font-size: 12px;
  font-weight: 700;
  color: #ffffff;
}
.form-group input,
.form-group select {
  background: rgba(255,255,255,0.03);
  border: 1px solid var(--border-glass);
  border-radius: 8px;
  padding: 10px 14px;
  color: #ffffff;
  font-size: 13px;
  outline: none;
  transition: border-color 0.2s ease;
}
.form-group input:focus,
.form-group select:focus {
  border-color: var(--accent-cyan);
}
.form-group input::placeholder {
  color: #475569;
}
.form-group select option {
  background: #061124;
  color: #ffffff;
}

/* ============================================================
   10. TIN TỨC WORKSTATION (Insights Magazine)
   ============================================================ */
.magazine-news-section {
  background-color: var(--bg-light);
}
.magazine-news-section h2 {
  color: #0f172a;
}
.magazine-news-section p {
  color: #64748b;
}

.magazine-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
}
@media (max-width: 992px) {
  .magazine-grid { grid-template-columns: 1fr; }
}

.magazine-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(0,0,0,0.01);
  transition: all 0.25s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
}
.magazine-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 32px rgba(15,23,42,0.05);
  border-color: rgba(59, 130, 246, 0.2);
}
.mag-img-box {
  width: 100%;
  height: 200px;
  overflow: hidden;
}
.mag-img-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}
.magazine-card:hover .mag-img-box img {
  transform: scale(1.04);
}
.mag-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex: 1;
}
.mag-meta {
  display: flex;
  gap: 8px;
  font-size: 11px;
  color: #64748b;
  font-weight: 600;
}
.magazine-card h3 {
  font-size: 15px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.mag-body p {
  font-size: 12.5px;
  color: #64748b;
  margin: 0;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.mag-link {
  font-size: 11.5px;
  font-weight: 700;
  color: var(--accent-blue);
  margin-top: auto;
  padding-top: 10px;
}

/* ============================================================
   11. FOOTER CTA SECTION (Enterprise solutions banner)
   ============================================================ */
.ending-cta-section {
  background-image: url('https://images.unsplash.com/photo-1503387762-592ded58c45a?w=1200');
  background-size: cover;
  background-position: center;
  position: relative;
  padding: 120px 0;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}
.cta-banner-image-tint {
  position: absolute;
  inset: 0;
  background: rgba(3, 14, 34, 0.9);
  z-index: 1;
}
.cta-banner-container {
  position: relative;
  z-index: 2;
  max-width: 660px;
  padding: 0 16px;
}
.cta-eyebrow {
  font-size: 11px;
  font-weight: 800;
  color: var(--accent-cyan);
  letter-spacing: 0.1em;
  text-transform: uppercase;
}
.cta-banner-container h2 {
  font-size: clamp(28px, 4vw, 40px);
  font-weight: 850;
  color: #ffffff;
  margin: 12px 0 16px;
  letter-spacing: -0.02em;
  text-transform: uppercase;
}
.cta-banner-container p {
  color: var(--text-muted);
  font-size: 15px;
  line-height: 1.65;
  margin-bottom: 32px;
}

/* ============================================================
   SHARED UTILITIES & ANIMATIONS
   ============================================================ */
.border-glow {
  border: 1px solid var(--border-glass);
}
.border-glow:hover {
  border-color: rgba(0, 242, 254, 0.25);
  box-shadow: 0 0 20px rgba(0, 242, 254, 0.12);
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 750;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  border: none;
}
.btn-sm {
  padding: 8px 18px;
  font-size: 12.5px;
}
.btn-icon {
  width: 16px;
  height: 16px;
}
.w-100 {
  width: 100%;
}
.justify-content-center {
  justify-content: center;
}
.mt-3 {
  margin-top: 12px;
}

.btn-primary-glow {
  background: var(--accent-blue);
  color: #ffffff;
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.35);
}
.btn-primary-glow:hover {
  background: #2563eb;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
}
.btn-primary-glow:active {
  transform: translateY(0);
}

.btn-glassmorphism {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #ffffff;
}
.btn-glassmorphism:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.18);
  transform: translateY(-2px);
}

/* Skeletons */
.skeleton-card {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.04);
  border-radius: 16px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  min-height: 340px;
}
.skeleton-media {
  background: rgba(255,255,255,0.03);
  border-radius: 10px;
  aspect-ratio: 1/1;
  width: 100%;
}
.skeleton-text {
  height: 14px;
  background: rgba(255,255,255,0.03);
  border-radius: 4px;
}
.skeleton-text.line-1 { width: 40%; }
.skeleton-text.line-2 { width: 90%; }
.skeleton-text.line-3 { width: 60%; }

.skeleton-media,
.skeleton-text {
  animation: skeleton-pulse 1.5s infinite ease-in-out;
}
@keyframes skeleton-pulse {
  0%, 100% { opacity: 0.4; }
  50% { opacity: 0.8; }
}

/* Scroll reveal */
.scroll-reveal {
  opacity: 0;
  transform: scale(0.98) translateY(20px);
  transition: opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1), transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.scroll-reveal.active {
  opacity: 1;
  transform: translateY(0) scale(1);
}
.reveal-fade-up {
  transform: translateY(30px);
}
.reveal-fade-up.active {
  transform: translateY(0);
}
.reveal-stagger > * {
  opacity: 0;
  transform: translateY(20px);
  transition: opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1), transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.scroll-reveal.reveal-stagger.active > * {
  opacity: 1;
  transform: translateY(0);
}
.scroll-reveal.reveal-stagger.active > *:nth-child(1) { transition-delay: 0.08s; }
.scroll-reveal.reveal-stagger.active > *:nth-child(2) { transition-delay: 0.16s; }
.scroll-reveal.reveal-stagger.active > *:nth-child(3) { transition-delay: 0.24s; }
.scroll-reveal.reveal-stagger.active > *:nth-child(4) { transition-delay: 0.32s; }
.scroll-reveal.reveal-stagger.active > *:nth-child(5) { transition-delay: 0.40s; }
.scroll-reveal.reveal-stagger.active > *:nth-child(6) { transition-delay: 0.48s; }

/* Product card hover adjustments */
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
.card-media-box {
  width: 100%;
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}
.card-media-box img {
  max-width: 85%;
  max-height: 85%;
  object-fit: contain;
  transition: transform 0.3s ease;
}
.card-media-box:hover .card-hover-overlay {
  opacity: 1;
}
.hover-heart-btn {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 46px;
  height: 46px;
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
  width: 18px;
  height: 18px;
}
.hover-heart-btn:hover {
  background: #ef4444;
  border-color: #ef4444;
  transform: translateY(0) scale(1.06);
}
.hover-quick-view-btn {
  position: absolute;
  left: 20px;
  bottom: 20px;
  background: transparent;
  color: #ffffff;
  font-weight: 750;
  font-size: 14px;
  border: none;
  padding: 0;
  cursor: pointer;
  text-shadow: 0 2px 8px rgba(15, 23, 42, 0.35);
  transition: all 0.2s ease;
}
.hover-quick-view-btn:hover {
  color: var(--accent-blue);
  text-decoration: underline;
}
.card-hover-cart-btn {
  position: absolute;
  right: 10px;
  bottom: 10px;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--accent-blue);
  color: #ffffff;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(59, 130, 246, 0.35);
  opacity: 0;
  transform: scale(0.8);
  transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  cursor: pointer;
  z-index: 5;
}
.card-hover-cart-btn svg {
  width: 16px;
  height: 16px;
}
.premium-product-card:hover .card-hover-cart-btn {
  opacity: 1;
  transform: scale(1);
}
.card-hover-cart-btn:hover {
  background: #2563eb;
  transform: scale(1.06);
}

@media (max-width: 992px) {
  .hero-viewport {
    min-height: 50vh;
    padding: 42px 0;
    background-position: center;
  }
  .hero-container {
    grid-template-columns: 1fr;
    text-align: center;
    gap: 24px;
  }
  .hero-text-block {
    margin: 0 auto;
  }
  .hero-action-buttons {
    justify-content: center;
  }
  .hero-graphic-block {
    display: none;
  }
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (max-width: 600px) {
  .hero-viewport {
    min-height: 52vh;
    padding: 36px 0;
  }
  .hero-title {
    font-size: clamp(30px, 11vw, 42px);
  }
  .hero-description {
    font-size: 13.5px;
    margin-bottom: 20px;
  }
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
</style>
