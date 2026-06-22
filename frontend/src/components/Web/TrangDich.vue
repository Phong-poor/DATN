<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { getToken } from '@/services/auth'
import api from '../../services/api'
import swal from '@/services/swal'
import { productImageUrl } from '@/services/urls'

const router = useRouter()

// ===================== PRODUCT DATA & API LOADING =====================
const allProducts = ref([])
const loading = ref(true)

const normalizeProducts = (payload) => {
    if (Array.isArray(payload)) return payload
    if (Array.isArray(payload?.data)) return payload.data
    return []
}

const mapProducts = (rawProducts) => {
    const safeProducts = Array.isArray(rawProducts) ? rawProducts : []
    const productVariants = safeProducts.map(p => {
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
                img: productImageUrl(p, null, 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=500'),
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

            let generalSpecs = [];
            try {
                const tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || []);
                if (Array.isArray(tskt)) {
                    generalSpecs = tskt.map(item => item.giatri).filter(Boolean);
                }
            } catch (e) { }

            const fullName = [p.tenSP, ...generalSpecs].join(' ');
            
            const specs = [
                { label: 'RAM', value: ram },
                { label: 'CPU', value: cpu },
                { label: 'Card', value: gpu },
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
                img: productImageUrl(p, bt, 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=500'),
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

const mockProducts = [
    {
        id: 1,
        key_id: "mock-1",
        name: "VinaTech Beast Hunter X",
        fullName: "VinaTech Beast Hunter X Ultra Core i9 RTX 4080",
        category: "Laptop Gaming",
        id_danhmuc: "2",
        brandName: "ASUS",
        weight: "2.4",
        priceNum: 48990000,
        oldPriceNum: 55990000,
        specs: [{ label: 'RAM', value: '32GB' }, { label: 'CPU', value: 'i9 14900HX' }, { label: 'Card', value: 'RTX 4080' }],
        img: "https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500",
        badge: "HOT",
        badgeColor: "#dc2626"
    },
    {
        id: 2,
        key_id: "mock-2",
        name: "VinaTech ZenBook Space Pro",
        fullName: "VinaTech ZenBook Space Pro Core Ultra 7 OLED Touch",
        category: "Laptop Văn phòng",
        id_danhmuc: "3",
        brandName: "ASUS",
        weight: "1.2",
        priceNum: 32500000,
        oldPriceNum: 36900000,
        specs: [{ label: 'RAM', value: '16GB' }, { label: 'CPU', value: 'Ultra 7 155H' }, { label: 'Màu', value: 'Màu xám không gian' }],
        img: "https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=500",
        badge: "NEW",
        badgeColor: "#2563eb"
    },
    {
        id: 3,
        key_id: "mock-3",
        name: "VinaTech Scholar Essential Lite",
        fullName: "VinaTech Scholar Essential Lite Ryzen 5 IPS FHD",
        category: "Laptop Sinh viên",
        id_danhmuc: "7",
        brandName: "ACER",
        weight: "1.6",
        priceNum: 12990000,
        oldPriceNum: 15490000,
        specs: [{ label: 'RAM', value: '8GB' }, { label: 'CPU', value: 'Ryzen 5 7520U' }],
        img: "https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=500",
        badge: "SALE",
        badgeColor: "#10b981"
    }
]

onMounted(async () => {
    try {
        const spRes = await api.get('/sanpham')
        const items = mapProducts(normalizeProducts(spRes.data))
        if (items && items.length > 0) {
            allProducts.value = items
        } else {
            allProducts.value = mockProducts
        }
    } catch (error) {
        console.error('Lỗi khi tải dữ liệu thực tế, dùng dữ liệu giả lập chất lượng cao:', error)
        allProducts.value = mockProducts
    } finally {
        loading.value = false
    }

    startCountdown()

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed')
                observer.unobserve(entry.target)
            }
        })
    }, { threshold: 0.1 })

    document.querySelectorAll('.reveal-el').forEach(el => observer.observe(el))
})

// ===================== INTERACTIVE SPECS VISUALIZER =====================
const activeSpecTab = ref('cpu')
const specsData = {
    cpu: {
        title: 'CPU Intel Core Ultra 9 / Ryzen 9',
        sub: 'Bộ xử lý trung tâm thế hệ mới tích hợp NPU trí tuệ nhân tạo',
        desc: 'Sở hữu khả năng xử lý lên đến 24 nhân, 32 luồng cùng bộ tăng tốc AI tích hợp sẵn. Cân mọi tác vụ từ biên dịch mã nguồn phức tạp, xử lý dữ liệu lớn đến kết xuất video 4K chuyên nghiệp chỉ trong tích tắc.',
        specDetails: [
            { label: 'Tốc độ tối đa', val: '5.8 GHz Turbo' },
            { label: 'Số nhân/luồng', val: '24 Cores / 32 Threads' },
            { label: 'Bộ nhớ đệm L3', val: '36 MB Smart Cache' },
            { label: 'Bộ tăng tốc AI', val: 'Intel® AI Boost / AMD Ryzen™ AI' }
        ],
        glowColor: 'var(--accent-glow-blue)'
    },
    gpu: {
        title: 'NVIDIA GeForce RTX 4080 / 4090',
        sub: 'Đồ họa tối thượng với Ray Tracing thời gian thực & DLSS 3.5',
        desc: 'Trải nghiệm sức mạnh xử lý đồ họa đột phá với kiến trúc Ada Lovelace siêu hiệu quả. Trải nghiệm game AAA chân thực tối đa với ánh sáng phản chiếu Ray Tracing hoặc render đồ họa 3D kiến trúc siêu nhanh.',
        specDetails: [
            { label: 'VRAM', val: '12GB - 16GB GDDR6' },
            { label: 'TGP tối đa', val: '175W Dynamic Boost' },
            { label: 'Nhân Tensor', val: 'Thế hệ 4 (Tăng tốc AI)' },
            { label: 'Công nghệ hỗ trợ', val: 'DLSS 3.5 Frame Generation' }
        ],
        glowColor: 'var(--accent-glow-green)'
    },
    ram: {
        title: 'RAM 32GB / 64GB DDR5 Dual Channel',
        sub: 'Băng thông rộng đỉnh cao cho trải nghiệm đa tác vụ mượt mà',
        desc: 'Được trang bị RAM DDR5 tần số cực cao lên đến 5600MHz giúp mở hàng trăm tab Chrome, chạy đồng thời các ứng dụng giả lập nặng, máy ảo Docker hay IDE lập trình mà không gặp bất kỳ hiện tượng giật lag nào.',
        specDetails: [
            { label: 'Chuẩn RAM', val: 'LPDDR5X / DDR5' },
            { label: 'Tần số bus', val: '5600 MHz - 7467 MHz' },
            { label: 'Khả năng nâng cấp', val: 'Hỗ trợ lên đến 96GB' },
            { label: 'Tiết kiệm điện năng', val: 'Băng thông tăng 50%, giảm 10% điện áp' }
        ],
        glowColor: 'var(--accent-glow-purple)'
    },
    display: {
        title: 'Màn hình OLED 2.8K / 3.2K 240Hz',
        sub: 'Màu sắc điện ảnh hoàn mỹ với dải màu rộng đạt chuẩn chuyên nghiệp',
        desc: 'Màn hình OLED đỉnh cao với độ tương phản vô hạn 1.000.000:1, độ phủ màu hoàn hảo 100% DCI-P3 chuyên dùng làm phim. Tần số quét 240Hz siêu mượt giúp thao tác cuộn trang hay chơi game FPS không để lại vết mờ.',
        specDetails: [
            { label: 'Tỷ lệ tương phản', val: '1.000.000:1 True Black' },
            { label: 'Độ phủ màu', val: '100% DCI-P3 (Pantone Validated)' },
            { label: 'Tần số quét', val: '120Hz - 240Hz Pro' },
            { label: 'Độ sáng đỉnh', val: '600 nits HDR Peak' }
        ],
        glowColor: 'var(--accent-glow-pink)'
    }
}

const currentSpec = computed(() => specsData[activeSpecTab.value])

// ===================== INTERACTIVE AI NPU SIMULATOR =====================
const activeAiTask = ref('chat')
const aiSimulatorData = {
    chat: {
        title: 'Sinh phản hồi trợ lý AI (LLM Inference)',
        sub: 'Phân tích tài liệu PDF 500 trang & tóm tắt ý chính',
        npuTime: 0.35,
        cpuTime: 3.80,
        text: 'NPU chuyên dụng thực thi song song các phép nhân ma trận trọng số 4-bit (INT4), giảm tải hoàn toàn cho CPU và hoàn thành tác vụ chỉ trong nháy mắt.',
        speedup: '10.8x'
    },
    image: {
        title: 'Tách nền & Phóng đại hình ảnh (AI Upscaling)',
        sub: 'Tăng độ phân giải từ FHD lên 4K sắc nét bằng Super Resolution',
        npuTime: 0.72,
        cpuTime: 8.50,
        text: 'Các nhân Tensor Core và bộ tăng tốc đồ họa xử lý luồng điểm ảnh thời gian thực, loại bỏ răng cưa và nhiễu ảnh một cách thông minh mà không gây trễ hệ thống.',
        speedup: '11.8x'
    },
    coding: {
        title: 'Tự sinh mã nguồn & Kiểm thử bảo mật (AI Copilot)',
        sub: 'Đọc hiểu logic thuật toán & tự động phát hiện lỗi hổng bảo mật',
        npuTime: 0.48,
        cpuTime: 4.90,
        text: 'Chạy cục bộ (Local Model) mô hình ngôn ngữ lớn 7 tỷ tham số ngay trên máy, bảo mật tuyệt đối dữ liệu doanh nghiệp mà không cần gửi lên máy chủ đám mây.',
        speedup: '10.2x'
    },
    render: {
        title: 'Khử nhiễu kết xuất 3D (AI Denoiser)',
        sub: 'Kết xuất hình ảnh kiến trúc nội thất Ray Tracing thời gian thực',
        npuTime: 1.20,
        cpuTime: 15.60,
        text: 'Áp dụng thuật toán học sâu AI giúp bù đắp các tia sáng bị thiếu hụt khi render, đẩy nhanh tiến độ làm việc của các kiến trúc sư lên gấp nhiều lần.',
        speedup: '13.0x'
    }
}

const currentAiSim = computed(() => aiSimulatorData[activeAiTask.value])

// ===================== LAPTOP FINDER QUIZ =====================
const quizStep = ref(1) // 1: Nhu cầu, 2: Ngân sách, 3: Ưu tiên, 4: Kết quả
const answers = ref({
    demand: '',    // 'gaming', 'graphic', 'office', 'student'
    budget: 0,     // 1: <15tr, 2: 15-25tr, 3: >25tr
    priority: ''   // 'performance', 'battery', 'portable'
})

const selectDemand = (val) => {
    answers.value.demand = val
    quizStep.value = 2
}

const selectBudget = (val) => {
    answers.value.budget = val
    quizStep.value = 3
}

const selectPriority = (val) => {
    answers.value.priority = val
    quizStep.value = 4
}

const resetQuiz = () => {
    answers.value = { demand: '', budget: 0, priority: '' }
    quizStep.value = 1
}

const recommendedProducts = computed(() => {
    if (allProducts.value.length === 0) return []

    let temp = [...allProducts.value]
    if (answers.value.budget === 1) {
        temp = temp.filter(p => p.priceNum > 0 && p.priceNum < 16000000)
    } else if (answers.value.budget === 2) {
        temp = temp.filter(p => p.priceNum >= 16000000 && p.priceNum <= 28000000)
    } else if (answers.value.budget === 3) {
        temp = temp.filter(p => p.priceNum > 28000000)
    }

    if (answers.value.demand === 'gaming') {
        temp = temp.filter(p => {
            return p.category.toLowerCase().includes('gaming') || 
                   p.fullName.toLowerCase().includes('gaming') || 
                   p.fullName.toLowerCase().includes('rtx') || 
                   p.fullName.toLowerCase().includes('tuf') || 
                   p.fullName.toLowerCase().includes('rog');
        })
    } else if (answers.value.demand === 'office') {
        temp = temp.filter(p => {
            return p.category.toLowerCase().includes('văn phòng') || 
                   p.category.toLowerCase().includes('office') || 
                   p.fullName.toLowerCase().includes('macbook') || 
                   p.fullName.toLowerCase().includes('zenbook') || 
                   p.fullName.toLowerCase().includes('xps');
        })
    } else if (answers.value.demand === 'student') {
        temp = temp.filter(p => {
            return p.category.toLowerCase().includes('sinh viên') || 
                   p.category.toLowerCase().includes('student') || 
                   p.fullName.toLowerCase().includes('vivobook') || 
                   p.fullName.toLowerCase().includes('aspire') || 
                   p.fullName.toLowerCase().includes('ideapad');
        })
    }

    if (answers.value.priority === 'portable') {
        temp.sort((a, b) => {
            const wA = parseFloat(a.weight) || 2.0
            const wB = parseFloat(b.weight) || 2.0
            return wA - wB
        })
    } else if (answers.value.priority === 'performance') {
        temp.sort((a, b) => b.priceNum - a.priceNum)
    }

    if (temp.length === 0) {
        return allProducts.value.slice(0, 2)
    }

    return temp.slice(0, 2)
})

// ===================== FLASH DEAL COUNTDOWN =====================
const countdownTime = ref({ hours: '00', minutes: '00', seconds: '00' })
let timerInterval = null

const startCountdown = () => {
    const getTargetTime = () => {
        const target = new Date()
        target.setHours(23, 59, 59, 999)
        return target.getTime()
    }
    
    const target = getTargetTime()

    const updateTimer = () => {
        const now = new Date().getTime()
        const diff = target - now

        if (diff <= 0) {
            countdownTime.value = { hours: '00', minutes: '00', seconds: '00' }
            return
        }

        const h = Math.floor(diff / (1000 * 60 * 60))
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
        const s = Math.floor((diff % (1000 * 60)) / 1000)

        countdownTime.value = {
            hours: String(h).padStart(2, '0'),
            minutes: String(m).padStart(2, '0'),
            seconds: String(s).padStart(2, '0')
        }
    }

    updateTimer()
    timerInterval = setInterval(updateTimer, 1000)
}

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
})

// ===================== FAQ ACCORDION =====================
const activeFaqIdx = ref(null)
const faqs = [
    {
        q: 'Tôi có thể mua trả góp với lãi suất 0% như thế nào?',
        a: 'Bạn có thể trả góp 0% cực kỳ dễ dàng qua 2 hình thức: Thẻ tín dụng của hơn 25 ngân hàng liên kết (duyệt trực tuyến chỉ trong 2 phút, không giữ thẻ, không mất phí ẩn) hoặc thông qua hồ sơ tài chính trực tiếp (chỉ cần CCCD có chip) tại các showroom VinaTech với sự hỗ trợ nhiệt tình từ các đối tác tài chính lớn như HD Saison, MCredit, Home Credit.'
    },
    {
        q: 'Chính sách bảo hành tại VinaTech Elite có gì đặc biệt?',
        a: 'Tất cả các dòng laptop cao cấp đều được hưởng gói Bảo hành vàng 24 tháng chính hãng, bảo hành 1 đổi 1 trong vòng 30 ngày đầu tiên nếu có lỗi phần cứng từ nhà sản xuất. Đặc biệt, khách hàng mua dòng máy Elite sẽ nhận thẻ đặc quyền VVIP: Hỗ trợ giao nhận máy sửa chữa tận nhà miễn phí, ưu tiên mượn máy cấu hình tương đương sử dụng tạm thời và bảo dưỡng vệ sinh máy, tra keo tản nhiệt kim loại lỏng định kỳ miễn phí trọn đời.'
    },
    {
        q: 'Thời gian giao hàng toàn quốc mất bao lâu và đóng gói như thế nào?',
        a: 'Hệ thống hỗ trợ giao hàng siêu tốc trong vòng 2 giờ nội thành cho các khu vực trung tâm TP.HCM, Hà Nội, Đà Nẵng. Với các khu vực và tỉnh thành khác trên cả nước, sản phẩm sẽ được đóng gói chống sốc chuẩn quân đội gồm 3 lớp đệm khí nén cách nhiệt và thùng gỗ bảo vệ bên ngoài, chuyển phát nhanh tận nơi hoàn toàn miễn phí chỉ trong 1-3 ngày làm việc.'
    },
    {
        q: 'Làm thế nào để tôi nhận ưu đãi thu cũ đổi mới lên đời tiết kiệm?',
        a: 'Rất đơn giản, bạn chỉ cần mang chiếc máy tính cũ của bất kỳ thương hiệu nào đến showroom VinaTech gần nhất để kỹ thuật viên kiểm định phần cứng trong 10 phút. Chúng tôi cam kết định giá thu mua lại với mức giá cao nhất thị trường và tặng thêm voucher trợ giá đặc quyền lên tới 2,000,000đ khi bạn lên đời các dòng laptop Elite Premium mới.'
    }
]

const toggleFaq = (idx) => {
    activeFaqIdx.value = activeFaqIdx.value === idx ? null : idx
}

// ===================== WISH LIST & CART ACTION HELPERS =====================
const formatPrice = (p) => {
    if (!p || p === 0) return 'Liên hệ'
    return new Intl.NumberFormat('vi-VN').format(p) + 'đ'
}

const themVaoGioHang = async (product) => {
    const token = getToken()
    if (!token) {
        swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!')
        router.push('/dang-nhap')
        return
    }

    if (!product.key_id || String(product.key_id).startsWith('mock')) {
        swal.warning('Thông báo', 'Sản phẩm thử nghiệm này không thể thêm vào giỏ hàng thực tế!')
        return
    }

    try {
        await api.post('/gio-hang/them', {
            id_bienthe: Number(product.key_id),
            soluong: 1
        }, {
            headers: { Authorization: `Bearer ${token}` }
        })

        swal.success('Thành công', `Đã thêm ${product.fullName || product.name} vào giỏ hàng!`)
        window.dispatchEvent(new Event('cart-updated'))
    } catch (err) {
        swal.error('Lỗi', err.response?.data?.message || 'Có lỗi xảy ra!')
    }
}

// ===================== 1. DYNAMIC AUDIO EQUALIZER SIMULATOR =====================
const activeAudioMode = ref('cinema')
const audioModesData = {
    studio: {
        name: 'Studio Mixer Pro',
        sub: 'Âm thanh cân bằng chuẩn phòng thu (Flat Response)',
        desc: 'Chế độ tối ưu hóa cho các nhà sản xuất âm nhạc và kỹ sư âm thanh. Độ méo hài tổng THD cực thấp dưới 0.005%, dải âm phẳng tuyệt đối tái tạo nguyên bản giọng hát và nhạc cụ mà không bị méo tiếng.',
        eqSpeeds: [1.2, 0.8, 1.5, 0.9, 1.3, 0.7, 1.1, 1.4, 0.8, 1.2, 1.0, 1.6],
        eqHeights: ['60%', '40%', '75%', '50%', '65%', '35%', '55%', '70%', '40%', '60%', '50%', '80%']
    },
    cinema: {
        name: 'Cinematic Theatre',
        sub: 'Âm thanh vòm Dolby Atmos đa chiều 3D sống động',
        desc: 'Mở rộng không gian âm trường (Soundstage) cực đại. Thuật toán AI phân tích vị trí các tia âm thanh để phản xạ lên tường, kết hợp loa siêu trầm kép tạo nên âm trầm sâu thẳm, mạnh mẽ như trong rạp chiếu phim.',
        eqSpeeds: [0.6, 0.9, 0.5, 0.8, 0.7, 0.6, 0.9, 0.5, 0.8, 0.7, 0.6, 0.8],
        eqHeights: ['85%', '70%', '90%', '75%', '80%', '65%', '75%', '90%', '70%', '85%', '80%', '95%']
    },
    gaming: {
        name: 'Ultra Gaming FPS',
        sub: 'Tăng cường tần số cao, định vị tiếng chân & tiếng súng trễ 0ms',
        desc: 'Được tinh chỉnh đặc biệt cho thể loại game bắn súng FPS và sinh tồn. Khuếch đại tần số tiếng bước chân di chuyển của đối thủ ở dải âm cao, triệt tiêu tiếng vang thừa giúp định hướng 360 độ chính xác tuyệt đối.',
        eqSpeeds: [2.1, 1.8, 2.3, 1.9, 2.2, 1.7, 2.0, 2.4, 1.8, 2.1, 1.9, 2.5],
        eqHeights: ['40%', '30%', '55%', '45%', '50%', '30%', '40%', '60%', '35%', '45%', '40%', '65%']
    }
}
const currentAudioMode = computed(() => audioModesData[activeAudioMode.value])

// ===================== 2. INTERACTIVE AI PLAYGROUND TERMINAL =====================
const activeAiPrompt = ref('')
const aiTerminalOutput = ref('')
const isAiTerminalTyping = ref(false)
const aiNpuLoad = ref(3) // base NPU load
const aiProcessingLog = ref([])
let aiTypingInterval = null

const aiPromptsData = {
    summarize: {
        label: 'Tóm tắt báo cáo PDF',
        prompt: 'Phân tích tài liệu PDF 500 trang về chiến lược tài chính quý I của tập đoàn VinaTech và rút ra 3 luận điểm cốt lõi.',
        logs: [
            'Loading model: Llama-3-Elite-7B-Instruct-Q4_K_M.gguf...',
            'Allocating 6.8 GB NPU SRAM memory...',
            'Executing attention matrix multiplication on local NPU Core...',
            'Token processing speed: 78 tokens/sec | Hardware temp: 44°C'
        ],
        output: `🤖 TRỢ LÝ TRÍ TUỆ NHÂN TẠO VINATECH AI AGENT (RUNNING LOCAL):
==================================================================
Kính gửi người dùng, dựa trên tài liệu báo cáo tài chính dài 500 trang, tôi đã trích xuất 3 luận điểm quan trọng nhất với độ chính xác cao:

1. DOANH THU MẢNG AI LAPTOP TĂNG TRƯỞNG VƯỢT BẬC (+145%):
   - Sự ra mắt của dòng sản phẩm Elite Premium 2026 đóng góp 42% vào tổng doanh thu bán lẻ phần cứng.
   - Biên lợi nhuận gộp của dòng Elite đạt 38.5%, cao nhất trong lịch sử công ty nhờ tối ưu hóa chuỗi cung ứng CNC.

2. CƠ CẤU CHI PHÍ R&D ĐƯỢC TỐI ƯU HÓA NHỜ AI AUTOMATION:
   - Chi phí nghiên cứu phát triển giảm 22% nhờ áp dụng các mô hình học máy mô phỏng khí động học tản nhiệt buồng hơi Aero-Blade thay cho chế thử vật lý lặp đi lặp lại.

3. KẾ HOẠCH DỰ TRỮ DÒNG TIỀN CHO QUÝ II:
   - Trích lập 150 tỷ VNĐ để gom linh kiện chip nhớ RAM DDR5 trước làn sóng tăng giá toàn cầu, bảo vệ mức giá ổn định cho khách hàng.

*Báo cáo được phân tích cục bộ 100% trên máy của bạn, bảo mật hoàn toàn dữ liệu nội bộ.*`
    },
    refactor: {
        label: 'Tối ưu thuật toán JS',
        prompt: 'Tối ưu hóa mã nguồn thuật toán tìm kiếm từ gợi ý Auto-Complete sử dụng cấu trúc Trie để giảm thiểu độ phức tạp bộ nhớ.',
        logs: [
            'Parsing Abstract Syntax Tree (AST) of the input code...',
            'NPU Vector Engines running parallel graph search optimized for Javascript runtime...',
            'Analyzing space complexity bottleneck... Found memory leak in recursive DFS...',
            'Generating optimized class-based Trie structure in ES6...'
        ],
        output: `💻 VINATECH LOCAL AI COPILOT:
==================================================================
Chào bạn! Tôi đã phân tích cấu trúc mã nguồn của bạn. Thuật toán Trie cũ đang tiêu tốn nhiều bộ nhớ do giữ tham chiếu thừa. Dưới đây là mã ES6 đã tối ưu chạy bằng NPU hỗ trợ tăng tốc biên dịch:

class TrieNode {
  constructor() {
    this.children = {}; // Sử dụng map trống để tiết kiệm bộ nhớ thay vì khởi tạo Array(26)
    this.isEndOfWord = false;
  }
}

class OptimizedTrie {
  constructor() {
    this.root = new TrieNode();
  }

  // Chèn từ mới với độ phức tạp O(L), tiết kiệm 60% RAM so với bản cũ
  insert(word) {
    let node = this.root;
    for (let char of word) {
      if (!node.children[char]) {
        node.children[char] = new TrieNode();
      }
      node = node.children[char];
    }
    node.isEndOfWord = true;
  }
  
  // Tìm kiếm gợi ý tiền tố siêu tốc
  searchPrefix(prefix) {
    let node = this.root;
    for (let char of prefix) {
      if (!node.children[char]) return [];
      node = node.children[char];
    }
    const results = [];
    this._dfs(node, prefix, results);
    return results;
  }

  _dfs(node, currentWord, results) {
    if (results.length >= 5) return; // Giới hạn 5 kết quả tối đa để giải phóng Stack Memory
    if (node.isEndOfWord) results.push(currentWord);
    for (let char in node.children) {
      this._dfs(node.children[char], currentWord + char, results);
    }
  }
}

*Mã nguồn đã được tối ưu hóa độ phức tạp bộ nhớ từ O(26^L) xuống O(L) thực tế.*`
    },
    marketing: {
        label: 'Kế hoạch Marketing Elite',
        prompt: 'Lập kế hoạch ra mắt dòng máy VinaTech Elite Premium 2026 trên nền tảng mạng xã hội nhắm tới giới sáng tạo nội dung nghệ thuật.',
        logs: [
            'Fetching local creativity database and market sentiment indicators...',
            'Structuring campaign timeline with 3-phase strategic roadmap...',
            'Calculating budget allocation efficiency indexes...',
            'Compiling campaign copy and high-conversion visual design directions...'
        ],
        output: `📣 VINATECH CREATIVE MARKETER ASSISTANT:
==================================================================
Dưới đây là kế hoạch chiến dịch ra mắt "Predator AI Laptop: VinaTech Elite Premium" hướng tới các nghệ sĩ, thiết kế đồ họa (Creatives) cực kỳ chi tiết:

PHASE 1: TEASER - "SÁNG TẠO KHÔNG GIỚI HẠN" (Tuần 1 - Tuần 2)
  - Hoạt động: Đăng tải các video chuyển động 3D cận cảnh chi tiết phay nhôm CNC nguyên khối và hình ảnh màn hình OLED phản chiếu dải màu điện ảnh.
  - Thông điệp: "Đẳng cấp chế tác từ kim loại hàng không. Chuẩn màu đến từng pixel."
  - Target: Tò mò về chất lượng hoàn thiện của máy.

PHASE 2: LAUNCH - "ĐỘT PHÁ TỪ SỨC MẠNH TRÍ TUỆ NHÂN TẠO" (Tuần 3)
  - Hoạt động: Tổ chức livestream trực tiếp trải nghiệm AI NPU vẽ ảnh nghệ thuật, tách nền 4K và biên dịch mã nguồn nội bộ không tốn tài nguyên mạng.
  - Thông điệp: "Chạy mô hình AI cục bộ bảo mật 100%. Tốc độ gấp 13 lần."
  - Ưu đãi: Mở đặt cọc Flash Deal 10 suất nhận ngay combo nâng cấp trị giá 4.5M.

PHASE 3: ADVOCACY - "ĐỒNG HÀNH CÙNG KHÁCH HÀNG THỰC TẾ" (Tuần 4 trở đi)
  - Hoạt động: Hợp tác với các Creative Directors, Lập trình viên hàng đầu chia sẻ trải nghiệm sử dụng thực tế (đặc quyền VVIP, bảo dưỡng trọn đời).
  - Hashtags: #VinaTechElite #KynuyenAI #AIEraPremium

*Kế hoạch định hướng rõ ràng mục tiêu chuyển đổi đơn hàng và khẳng định đẳng cấp cao của thương hiệu VinaTech.*`
    }
}

const runAiPrompt = (key) => {
    if (isAiTerminalTyping.value) return
    activeAiPrompt.value = key
    aiTerminalOutput.value = ''
    aiProcessingLog.value = []
    isAiTerminalTyping.value = true
    aiNpuLoad.value = 12

    const data = aiPromptsData[key]
    
    // Simulate terminal logs printing first
    let logIdx = 0
    const logInterval = setInterval(() => {
        if (logIdx < data.logs.length) {
            aiProcessingLog.value.push(data.logs[logIdx])
            logIdx++
            aiNpuLoad.value = Math.floor(Math.random() * 20) + 40
        } else {
            clearInterval(logInterval)
            // Start typing output
            aiNpuLoad.value = 96 // full load
            let charIdx = 0
            const fullText = data.output
            aiTypingInterval = setInterval(() => {
                if (charIdx < fullText.length) {
                    aiTerminalOutput.value += fullText.substring(charIdx, charIdx + 8)
                    charIdx += 8
                    aiNpuLoad.value = Math.floor(Math.random() * 8) + 90
                } else {
                    clearInterval(aiTypingInterval)
                    isAiTerminalTyping.value = false
                    aiNpuLoad.value = 3 // Idle state
                }
            }, 15)
        }
    }, 400)
}

// ===================== 3. INTERACTIVE BATTERY & POWER CALCULATOR =====================
const batteryBrightness = ref(60) // slider 10 to 100
const batteryWorkload = ref('coding') // 'office', 'coding', 'gaming', 'rendering'

const workloadDetails = {
    office: {
        name: 'Văn phòng & Lướt Web',
        baseWatts: 6.5,
        cpuPercent: 12,
        gpuPercent: 5,
        displayWeight: 0.5,
        systemPercent: 10,
        tip: 'Khuyên dùng: Ở tác vụ này bạn có thể kích hoạt chế độ Silent Mode để máy chạy êm ái hoàn toàn không quay quạt.'
    },
    coding: {
        name: 'Lập trình & Biên dịch mã nguồn',
        baseWatts: 15.0,
        cpuPercent: 42,
        gpuPercent: 10,
        displayWeight: 0.7,
        systemPercent: 20,
        tip: 'Khuyên dùng: Tích hợp NPU xử lý Auto-Complete giúp giảm tải 35% điện năng tiêu thụ cho nhân CPU chính.'
    },
    gaming: {
        name: 'Chơi game nặng (Cyberpunk / AAA)',
        baseWatts: 68.0,
        cpuPercent: 65,
        gpuPercent: 95,
        displayWeight: 1.0,
        systemPercent: 30,
        tip: 'Khuyên dùng: Hãy kết nối bộ sạc 180W đi kèm để tận dụng tối đa dòng điện Max TGP 175W của card đồ họa.'
    },
    rendering: {
        name: 'Kết xuất đồ họa 3D Blender / Premiere 4K',
        baseWatts: 82.0,
        cpuPercent: 88,
        gpuPercent: 90,
        displayWeight: 0.9,
        systemPercent: 35,
        tip: 'Khuyên dùng: Bật tính năng tản nhiệt buồng hơi lỏng Aero-Blade để duy trì hiệu năng đỉnh ổn định lâu dài.'
    }
}

const batteryStats = computed(() => {
    const detail = workloadDetails[batteryWorkload.value]
    const displayDraw = 1.0 + (batteryBrightness.value / 100) * 8.5
    const cpuDraw = detail.baseWatts * 0.4
    const gpuDraw = detail.baseWatts * 0.45
    const systemDraw = 2.0 + (detail.systemPercent / 100) * 5.0
    const totalWatts = cpuDraw + gpuDraw + displayDraw + systemDraw
    const capacityWh = 99.6
    const totalHours = capacityWh / totalWatts
    const hours = Math.floor(totalHours)
    const minutes = Math.floor((totalHours - hours) * 60)
    
    const cpuPercent = Math.round((cpuDraw / totalWatts) * 100)
    const gpuPercent = Math.round((gpuDraw / totalWatts) * 100)
    const displayPercent = Math.round((displayDraw / totalWatts) * 100)
    const systemPercent = Math.round((systemDraw / totalWatts) * 100)
    
    return {
        hours,
        minutes,
        totalWatts: totalWatts.toFixed(1),
        cpuPercent,
        gpuPercent,
        displayPercent,
        systemPercent,
        tip: detail.tip
    }
})

// ===================== 4. INTERACTIVE TRADE-IN CALCULATOR =====================
const tradeInBrand = ref('apple')
const tradeInCondition = ref('good')
const tradeInLaptopName = ref('')
const tradeInBrandData = {
    apple: { baseVal: 15000000, name: 'Apple MacBook' },
    dell: { baseVal: 11000000, name: 'Dell XPS/Inspiron' },
    hp: { baseVal: 9500000, name: 'HP Spectre/Envy' },
    asus: { baseVal: 9000000, name: 'ASUS Zenbook/ROG' },
    other: { baseVal: 7000000, name: 'Các dòng máy khác' }
}
const tradeInConditionData = {
    new: { multiplier: 1.0, bonus: 3000000, desc: 'Máy đẹp như mới, không trầy xước, pin tốt (>85%)' },
    good: { multiplier: 0.8, bonus: 2500000, desc: 'Máy hoạt động tốt, trầy xước nhẹ, màn hình đẹp' },
    scratched: { multiplier: 0.5, bonus: 2000000, desc: 'Máy trầy xước nhiều, pin chai, vỏ hơi móp' },
    broken: { multiplier: 0.2, bonus: 1000000, desc: 'Máy lỗi nguồn/màn hình, hỏng phím, chỉ thu mua xác' }
}

const tradeInResult = computed(() => {
    const brand = tradeInBrandData[tradeInBrand.value]
    const cond = tradeInConditionData[tradeInCondition.value]
    const estimateVal = Math.round(brand.baseVal * cond.multiplier)
    const bonusVal = cond.bonus
    const totalDiscount = estimateVal + bonusVal
    
    // VinaTech Elite Premium Base Prices
    const targetPrice = 36900000 // base elite premium price
    const finalPayment = Math.max(targetPrice - totalDiscount, 0)
    
    return {
        brandName: brand.name,
        estimateVal,
        bonusVal,
        totalDiscount,
        targetPrice,
        finalPayment
    }
})

// Detailed specs matrix data
const specsMatrix = [
    { param: 'Trọng lượng', scholar: '1.6 kg', pro: '1.2 kg', beast: '2.4 kg' },
    { param: 'Vi xử lý (CPU)', scholar: 'Ryzen 5 7520U', pro: 'Intel Core Ultra 7 155H', beast: 'Intel Core i9 14900HX' },
    { param: 'Đồ họa (GPU)', scholar: 'AMD Radeon Graphics', pro: 'Intel Arc Graphics (AI-Boost)', beast: 'NVIDIA RTX 4090 (175W)' },
    { param: 'Màn hình hiển thị', scholar: '15.6" IPS FHD 60Hz', pro: '14" Lumina OLED 2.8K 120Hz', beast: '16" Lumina OLED 3.2K 240Hz' },
    { param: 'Bộ nhớ RAM', scholar: '8GB LPDDR5 5200MHz', pro: '32GB LPDDR5X 7467MHz', beast: '64GB DDR5 5600MHz (Dual)' },
    { param: 'Lưu trữ SSD', scholar: '512GB PCIe Gen 4', pro: '1TB PCIe Gen 4 Ultra', beast: '2TB PCIe Gen 5 Raid 0 (14k MB/s)' },
    { param: 'Thời lượng Pin', scholar: '6 - 8 giờ liên tục', pro: '14 - 18 giờ liên tục (99.6Wh)', beast: '5 - 7 giờ liên tục' },
    { param: 'Vật liệu khung vỏ', scholar: 'Hợp kim nhựa cao cấp', pro: 'Nhôm hàng không 6000 CNC', beast: 'Nhôm hàng không 6000 CNC' }
]
</script>

<template>
    <div class="landing-premium">
        <!-- BACKGROUND DECORATIONS -->
        <div class="glow-orb orb-1"></div>
        <div class="glow-orb orb-2"></div>
        <div class="glow-orb orb-3"></div>

        <!-- 1. IMMERSIVE HERO SECTION -->
        <section class="hero-sec">
            <div class="hud-lines"></div>
            <div class="hud-scanner"></div>

            <div class="container hero-grid">
                <div class="hero-text-block reveal-el">
                    <div class="elite-badge">
                        <span class="pulse-dot"></span>
                        VinaTech Elite Premium 2026 • AI Era
                    </div>
                    <h1>
                        Kỷ Nguyên Máy Tính AI<br />
                        <span class="glow-text-cyan">Hiệu Năng Tột Đỉnh</span> & 
                        <span class="glow-text-pink">Chế Tác Vượt Thời Gian</span>
                    </h1>
                    <p class="hero-desc">
                        Chào mừng bạn đến với chương tiếp theo của công nghệ máy tính xách tay. Được kiến tạo dựa trên triết lý thiết kế tối giản, vật liệu bền bỉ và sở hữu sức mạnh xử lý trí tuệ nhân tạo đột phá nhờ nhân NPU chuyên biệt. Đây không chỉ là một công cụ làm việc, mà còn là người bạn đồng hành định hình đẳng cấp của bạn.
                    </p>

                    <div class="hero-ctas">
                        <a href="#quiz-sec" class="btn-shimmer">
                            <span class="shimmer-effect"></span>
                            Trải nghiệm chọn máy thông minh
                        </a>
                        <router-link to="/san-pham" class="btn-glass-neon">
                            Khám phá bộ sưu tập cửa hàng →
                        </router-link>
                    </div>

                    <div class="hero-stats">
                        <div class="stat-box">
                            <h4>15K+</h4>
                            <span>Khách hàng tin tưởng sử dụng</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-box">
                            <h4>99.2%</h4>
                            <span>Đánh giá 5 sao từ chuyên gia</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-box">
                            <h4>24/7</h4>
                            <span>Hỗ trợ VIP chuyên biệt</span>
                        </div>
                    </div>
                </div>

                <div class="hero-visual-block reveal-el">
                    <div class="laptop-canvas">
                        <div class="ambient-glow-circle"></div>
                        <div class="laptop-mockup-wrapper">
                            <img src="/hero_3d_laptop.png" alt="VinaTech Elite Laptop" class="laptop-front-img" />
                            <div class="tech-glow-borders"></div>
                        </div>

                        <!-- Floating Micro UI Cards -->
                        <div class="float-card card-ai">
                            <div class="icon">✨</div>
                            <div>
                                <h5>AI NPU Core</h5>
                                <span>Bộ tăng tốc AI 45 TOPS</span>
                            </div>
                        </div>

                        <div class="float-card card-fps">
                            <div class="icon">🎮</div>
                            <div>
                                <h5>RTX 4090</h5>
                                <span>Max TGP 175W Monster</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW BRAND IDEA: THE PHILOSOPHY OF INDUSTRIAL DESIGN -->
        <section class="philosophy-sec reveal-el">
            <div class="container">
                <div class="philosophy-grid">
                    <div class="phil-image-box">
                        <img src="/elite_chassis_cnc.png" alt="CNC Precision Machining" class="phil-img" />
                        <div class="glow-corners-matrix"></div>
                    </div>
                    <div class="phil-text-box">
                        <span class="label-neon pink">TRIẾT LÝ CHẾ TÁC</span>
                        <h2>Chính Xác Đến Từng Micromet</h2>
                        <p class="phil-intro">
                            Mỗi khung sườn của laptop VinaTech Elite Premium đều trải qua quy trình phay CNC nguyên khối từ hợp kim nhôm hàng không vũ trụ cấp 6000. Điều này mang lại sự cân bằng hoàn mỹ giữa trọng lượng siêu nhẹ và độ cứng cáp chống va đập tuyệt đối.
                        </p>
                        <div class="phil-features">
                            <div class="phil-feat-item">
                                <div class="num-box">01</div>
                                <div>
                                    <h5>Xử lý bề mặt phun cát Anodize</h5>
                                    <p>Tạo nên lớp phủ mịn màng như lụa, chống bám dấu vân tay và chống trầy xước gấp 3 lần vật liệu nhôm thông thường.</p>
                                </div>
                            </div>
                            <div class="phil-feat-item">
                                <div class="num-box">02</div>
                                <div>
                                    <h5>Bản lề trợ lực Ergo-Lift</h5>
                                    <p>Nâng nhẹ thân máy lên 3 độ khi mở màn hình, cải thiện tư thế gõ phím của cổ tay và tối ưu hóa 25% lưu lượng gió tản nhiệt dưới gầm máy.</p>
                                </div>
                            </div>
                            <div class="phil-feat-item">
                                <div class="num-box">03</div>
                                <div>
                                    <h5>Kiểm thử độ bền chuẩn quân đội</h5>
                                    <p>Vượt qua 26 bài kiểm tra độ bền khắc nghiệt MIL-STD-810H bao gồm sốc nhiệt từ -20°C đến 60°C, rung lắc mạnh và rơi tự do từ độ cao 1.2 mét.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW LUXURY UNBOXING SECTION -->
        <section class="unboxing-sec reveal-el">
            <div class="container">
                <div class="unboxing-grid">
                    <div class="ub-text-box">
                        <span class="label-neon pink">PREMIUM UNBOXING EXPERIENCE</span>
                        <h2>Nghệ Thuật Đánh Thức Các Giác Quan</h2>
                        <p class="ub-intro">
                            Sự sang trọng thực sự bắt đầu ngay từ trước khi bạn bật nguồn chiếc máy tính. Tại VinaTech, chúng tôi tin rằng trải nghiệm mở hộp (unboxing) là một nghi thức thiêng liêng chào đón bạn đến với thế giới công nghệ đẳng cấp nhất. Mỗi chiếc laptop Elite Premium được đóng gói trong một tác phẩm nghệ thuật chế tác thủ công.
                        </p>
                        <div class="ub-story-content">
                            <p>
                                Vỏ hộp ngoài sử dụng chất liệu carton tái chế sinh học cao cấp, phủ lớp sơn mờ nhám vân da đen bóng tạo cảm giác cầm nắm chắc chắn và sang trọng. Nổi bật ở chính giữa là biểu tượng VinaTech Elite được dập chìm mạ vàng 24K óng ánh, phản chiếu ánh sáng dịu nhẹ dưới mọi góc nhìn.
                            </p>
                            <p>
                                Khi bạn từ từ nhấc nắp hộp, hệ thống bản lề thủy lực tích hợp trong bao bì sẽ nhẹ nhàng nâng chiếc máy lên góc 15 độ, như một lời chào mừng nồng nhiệt. Máy được đặt gọn gàng trên lớp đệm nhung tơ tằm mềm mại chống tĩnh điện tuyệt đối, giúp bảo vệ toàn vẹn bề mặt Anodize siêu mịn của thân máy.
                            </p>
                            <p>
                                Bên dưới khay máy, bạn sẽ tìm thấy chiếc **Thẻ Chứng Nhận Đặc Quyền VVIP** bằng hợp kim Titanium siêu nhẹ, được khắc laser tinh xảo mã số series độc bản đăng ký riêng cho bạn. Cùng với đó là củ sạc nhanh công nghệ GaN 180W nhỏ gọn hơn 40% so với thông thường và bao da sợi carbon khâu tay tỉ mỉ, hoàn thiện một hệ sinh thái mở hộp đẳng cấp doanh nhân.
                            </p>
                        </div>
                    </div>
                    <div class="ub-image-box">
                        <img src="/elite_shipping.png" alt="Premium Unboxing Box Experience" class="ub-img" />
                        <div class="ambient-glow-pink-left"></div>
                        <div class="ub-hud-tag">LIMITED SPECIAL EMBLEM v2.6</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. INTERACTIVE SPECS VISUALIZER -->
        <section class="visualizer-sec reveal-el">
            <div class="container">
                <div class="sec-header text-center">
                    <span class="label-neon">CÔNG NGHỆ DẪN ĐẦU</span>
                    <h2>Nội Thất Phần Cứng Tối Thượng</h2>
                    <p class="max-w-600">Bấm chọn từng linh kiện để khám phá công nghệ lõi mang đến hiệu suất tối tân cho dòng laptop VinaTech Elite.</p>
                </div>

                <div class="visualizer-grid">
                    <!-- Tabs cột bên trái -->
                    <div class="visualizer-tabs">
                        <button 
                            v-for="(spec, key) in specsData" 
                            :key="key"
                            class="tab-btn"
                            :class="{ active: activeSpecTab === key }"
                            @click="activeSpecTab = key"
                        >
                            <span class="tab-indicator"></span>
                            <div class="tab-info">
                                <span class="tab-title">{{ key.toUpperCase() }}</span>
                                <span class="tab-subtitle">{{ spec.title }}</span>
                            </div>
                        </button>
                    </div>

                    <!-- Khu vực hiển thị linh kiện tương tác bên phải -->
                    <div class="visualizer-display" :style="{ '--glow-color': currentSpec.glowColor }">
                        <div class="display-glow-effect"></div>
                        
                        <div class="display-content-card">
                            <span class="tech-badge">ELITE HARDWARE SPECIFICATION</span>
                            <h3>{{ currentSpec.title }}</h3>
                            <span class="spec-sub">{{ currentSpec.sub }}</span>
                            <p class="spec-desc">{{ currentSpec.desc }}</p>

                            <div class="spec-metric-grid">
                                <div class="metric-item" v-for="det in currentSpec.specDetails" :key="det.label">
                                    <span class="m-label">{{ det.label }}</span>
                                    <span class="m-val">{{ det.val }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW ADVANCED ENGINEERING SECTION: THERMALS -->
        <section class="thermals-sec reveal-el">
            <div class="container">
                <div class="thermals-grid">
                    <div class="thermals-text">
                        <span class="label-neon cyan">TẢN NHIỆT AERO-BLADE</span>
                        <h2>Lạnh Lẽo Ngay Cả Dưới Áp Lực Nặng Nhất</h2>
                        <p class="thermals-intro">
                            Một cấu hình mạnh mẽ chỉ có thể phát huy 100% công năng khi có hệ thống giải nhiệt đẳng cấp tương xứng. VinaTech Elite tích hợp công nghệ tản nhiệt buồng hơi kết hợp keo tản nhiệt kim loại lỏng (Liquid Metal) tiên tiến nhất thế giới.
                        </p>
                        
                        <div class="thermal-indicators">
                            <div class="thermal-box">
                                <h3>-15°C</h3>
                                <span>Giảm nhiệt độ CPU tối đa</span>
                            </div>
                            <div class="thermal-box">
                                <h3>+35%</h3>
                                <span>Tăng tốc lưu thông luồng khí</span>
                            </div>
                            <div class="thermal-box">
                                <h3>&lt; 38dB</h3>
                                <span>Vận hành êm ái cực độ</span>
                            </div>
                        </div>

                        <p class="thermals-detail">
                            Sử dụng quạt tản nhiệt kép Aero-Blade với 89 cánh quạt siêu mỏng chế tác từ polymer tinh thể lỏng cao cấp, tạo ra áp suất luồng gió mạnh hơn nhưng giảm thiểu tối đa tiếng ồn động cơ. Buồng hơi (Vapor Chamber) diện tích lớn phủ kín toàn bộ khu vực CPU, GPU và dàn VRM cấp nguồn, tản nhiệt đều toàn thân máy.
                        </p>
                    </div>
                    <div class="thermals-image">
                        <img src="/elite_laptop_parts.png" alt="Advanced Vapor Chamber Thermals" class="thermal-img" />
                        <div class="ambient-glow-red"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW CORE HARDWARE ENGINEERING SECTION -->
        <section class="motherboard-sec reveal-el">
            <div class="container">
                <div class="motherboard-grid">
                    <div class="mb-image-box">
                        <img src="/elite_motherboard.png" alt="VinaTech Elite Motherboard Internals" class="mb-img" />
                        <div class="ambient-glow-cyan-right"></div>
                        <div class="mb-hud-tag">AI CORE ARCHITECTURE v2.6</div>
                    </div>
                    <div class="mb-text-box">
                        <span class="label-neon cyan">INTERNAL HARDWARE ENGINEERING</span>
                        <h2>Bản Giao Hưởng Của Những Linh Kiện Tối Tân</h2>
                        <p class="mb-intro">
                            Ẩn sau vẻ ngoài thanh lịch cực kỳ mỏng nhẹ là một kiệt tác cơ khí điện tử đột phá. Đội ngũ kỹ sư VinaTech đã tái định nghĩa lại kiến trúc bo mạch chủ (Motherboard Layout) để tích hợp những công nghệ dẫn đầu thế giới vào một không gian siêu mỏng.
                        </p>
                        <div class="mb-technical-list">
                            <div class="tech-item">
                                <h5>🧠 Bộ Tăng Tốc Trí Tuệ Nhân Tạo AI NPU Core</h5>
                                <p>Nhân xử lý chuyên biệt tích hợp sâu trong vi xử lý thế hệ mới, thực thi 45 nghìn tỷ phép tính mỗi giây (45 TOPS) chuyên phục vụ cho các thuật toán học máy, tối ưu năng lượng và bảo mật tuyệt đối.</p>
                            </div>
                            <div class="tech-item">
                                <h5>⚡ RAM LPDDR5X Dual-Channel 7467 MT/s</h5>
                                <p>Được hàn trực tiếp lên bo mạch bằng hợp kim thiếc-bạc cao cấp chống oxy hóa, đảm bảo băng thông dữ liệu rộng nhất, độ trễ tiệm cận 0ms và tiết kiệm 20% điện năng so với RAM cắm khe truyền thống.</p>
                            </div>
                            <div class="tech-item">
                                <h5>🚀 SSD PCIe Gen 5 Raid 0 Siêu Tốc</h5>
                                <p>Sử dụng giao thức truyền tải dữ liệu thế hệ mới nhất với băng thông tăng gấp đôi, đạt tốc độ đọc ghi kỷ lục lên đến 14,000 MB/s, cho phép mở các dự án lập trình khổng lồ hay render video 8K chỉ trong tích tắc.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW ADVANCED DISPLAY & DOLBY SPATIAL AUDIO SECTION WITH EQUALIZER -->
        <section class="display-audio-sec reveal-el">
            <div class="container">
                <div class="display-audio-grid">
                    <div class="da-image-block">
                        <img src="/elite_display_panel.png" alt="VinaTech Lumina OLED Display Panel" class="da-panel-img" />
                        <div class="da-mesh-glow"></div>
                        <div class="da-hud-indicator">OLED MULTI-LAYER PANEL v2.6</div>
                    </div>
                    
                    <div class="da-text-block">
                        <span class="label-neon pink">MÀN HÌNH LUMINA OLED & SPATIAL AUDIO</span>
                        <h2>Cảm Nhận Từng Màu Sắc, Chìm Đắm Trong Từng Âm Tần</h2>
                        <p class="da-intro">
                            Trải nghiệm nghe nhìn vượt xa mọi chuẩn mực thông thường. Laptop VinaTech Elite Premium là sự kết hợp hoàn hảo giữa công nghệ hiển thị Lumina OLED tự phát sáng đỉnh cao và hệ thống âm thanh vòm Dolby Atmos đa chiều thế hệ mới.
                        </p>
                        
                        <div class="da-sub-features">
                            <div class="da-feat">
                                <h5>🌟 Tấm nền Lumina OLED 10-bit màu</h5>
                                <p>Độ bao phủ màu đạt chuẩn điện ảnh 100% DCI-P3, độ tương phản vô hạn 1.000.000:1 cho màu đen sâu thẳm tuyệt đối. Mỗi màn hình đều được hiệu chỉnh màu sắc chuyên nghiệp từ nhà máy đảm bảo sai lệch Delta E &lt; 1.</p>
                            </div>
                            <div class="da-feat">
                                <h5>🔊 Hệ loa vòm Dynamic Quad-Speakers</h5>
                                <p>Sở hữu tổ hợp 4 loa đỉnh cao gồm 2 loa trầm kép phản xạ âm trầm sâu lắng và 2 loa bổng tái tạo giọng hát trong trẻo. Bộ giải mã Spatial Audio mang lại không gian âm trường 3D sống động chân thực.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DYNAMIC SOUND MIXER INTERACTIVE WIDGET -->
                <div class="sound-mixer-widget">
                    <div class="widget-header">
                        <div class="widget-title">
                            <span class="widget-icon">🎙️</span>
                            <div>
                                <h4>Trình Giả Lập Không Gian Âm Thanh Dolby Atmos</h4>
                                <p>Bấm chọn các chế độ bên dưới để thử nghiệm cách thuật toán AI căn chỉnh tần số âm vòm thời gian thực.</p>
                            </div>
                        </div>
                        <div class="mixer-tabs">
                            <button 
                                v-for="(mode, key) in audioModesData" 
                                :key="key" 
                                class="mixer-tab-btn"
                                :class="{ active: activeAudioMode === key }"
                                @click="activeAudioMode = key"
                            >
                                {{ mode.name.split(' ')[0] }}
                            </button>
                        </div>
                    </div>

                    <div class="mixer-body">
                        <div class="mixer-info">
                            <span class="mixer-badge">DOLBY CORE v2.6 ACTIVE</span>
                            <h3>{{ currentAudioMode.name }}</h3>
                            <span class="mixer-sub">{{ currentAudioMode.sub }}</span>
                            <p class="mixer-desc">{{ currentAudioMode.desc }}</p>
                        </div>

                        <div class="equalizer-visualizer">
                            <div class="eq-container">
                                <div 
                                    v-for="(height, i) in currentAudioMode.eqHeights" 
                                    :key="i" 
                                    class="eq-bar"
                                    :style="{ 
                                        height: height, 
                                        animationDuration: currentAudioMode.eqSpeeds[i] + 's' 
                                    }"
                                ></div>
                            </div>
                            <span class="eq-legend">GIẢ LẬP ĐỒ THỊ TẦN SỐ ÂM THANH DỰA TRÊN THỜI GIAN THỰC</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW INTERACTIVE AI SIMULATOR DEMO -->
        <section class="ai-sim-sec reveal-el">
            <div class="container">
                <div class="sec-header text-center">
                    <span class="label-neon pink">AI ACCELERATION DEMO</span>
                    <h2>Trải Nghiệm Sự Đột Phá Từ Nhân AI NPU</h2>
                    <p class="max-w-600">Bấm chọn từng tác vụ xử lý bên dưới để mô phỏng thời gian hoàn thành tác vụ giữa laptop tích hợp AI NPU so với laptop truyền thống chạy hoàn toàn bằng CPU.</p>
                </div>

                <div class="ai-sim-box">
                    <div class="ai-tabs">
                        <button 
                            v-for="(sim, key) in aiSimulatorData" 
                            :key="key"
                            class="ai-tab-btn"
                            :class="{ active: activeAiTask === key }"
                            @click="activeAiTask = key"
                        >
                            {{ sim.title.split(' (')[0] }}
                        </button>
                    </div>

                    <div class="ai-display-grid">
                        <div class="ai-sim-info">
                            <span class="ai-badge">NPU PERFORMANCE BOOSTER</span>
                            <h3>{{ currentAiSim.title }}</h3>
                            <span class="ai-sub">{{ currentAiSim.sub }}</span>
                            <p class="ai-text">{{ currentAiSim.text }}</p>
                            <div class="speed-boost-callout">
                                <h4>Nhanh hơn gấp <span>{{ currentAiSim.speedup }}</span> lần</h4>
                                <p>Nhờ kiến trúc phần cứng tối ưu hóa riêng cho các mô hình học sâu AI.</p>
                            </div>
                        </div>

                        <div class="ai-sim-charts">
                            <!-- NPU Bar -->
                            <div class="chart-row">
                                <div class="chart-label">
                                    <span>VinaTech Elite NPU Core</span>
                                    <b>{{ currentAiSim.npuTime }}s</b>
                                </div>
                                <div class="bar-track npu-track">
                                    <div class="bar-fill npu-fill" :style="{ width: (currentAiSim.npuTime / 16 * 100) + '%' }"></div>
                                </div>
                            </div>

                            <!-- CPU Bar -->
                            <div class="chart-row">
                                <div class="chart-label">
                                    <span>Laptop truyền thống (Chỉ dùng CPU)</span>
                                    <b>{{ currentAiSim.cpuTime }}s</b>
                                </div>
                                <div class="bar-track cpu-track">
                                    <div class="bar-fill cpu-fill" :style="{ width: (currentAiSim.cpuTime / 16 * 100) + '%' }"></div>
                                </div>
                            </div>

                            <span class="charts-note">*Thời gian đo lường trên bộ công cụ kiểm thử AI chuẩn quốc tế.</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW LOCAL AI PLAYGROUND INTERACTIVE CONSOLE -->
        <section class="ai-playground-sec reveal-el">
            <div class="container">
                <div class="sec-header text-center">
                    <span class="label-neon cyan">LOCAL AI AGENT CONSOLE</span>
                    <h2>Tương Tác Trực Tiếp Với VinaTech AI Core</h2>
                    <p class="max-w-600">Trải nghiệm khả năng hoạt động offline 100% cực kỳ bảo mật và mạnh mẽ của trợ lý AI được tích hợp sẵn sâu trong nhân hệ điều hành VinaTech OS.</p>
                </div>

                <div class="ai-playground-grid">
                    <div class="ai-prompts-sidebar">
                        <h4>CHỌN CÂU LỆNH YÊU CẦU (PROMPTS)</h4>
                        <p class="sidebar-intro">Bấm vào bất kỳ câu lệnh mẫu nào dưới đây để xem mô hình Llama-3 7B xử lý trực tiếp trên phần cứng NPU của máy tính.</p>
                        
                        <div class="prompt-cards-list">
                            <button 
                                v-for="(p, key) in aiPromptsData" 
                                :key="key"
                                class="prompt-btn-card"
                                :class="{ active: activeAiPrompt === key, disabled: isAiTerminalTyping }"
                                @click="runAiPrompt(key)"
                                :disabled="isAiTerminalTyping"
                            >
                                <div class="p-card-head">
                                    <span class="p-icon" v-if="key === 'summarize'">📂</span>
                                    <span class="p-icon" v-else-if="key === 'refactor'">💻</span>
                                    <span class="p-icon" v-else>📣</span>
                                    <h5>{{ p.label }}</h5>
                                </div>
                                <p class="p-card-text">"{{ p.prompt }}"</p>
                            </button>
                        </div>
                    </div>

                    <!-- Futuristic terminal dashboard -->
                    <div class="ai-terminal-window">
                        <div class="terminal-header">
                            <div class="window-controls">
                                <span class="dot red"></span>
                                <span class="dot yellow"></span>
                                <span class="dot green"></span>
                            </div>
                            <span class="terminal-title">vinatech-ai-core --llama-3-local-engine.sh</span>
                            <span class="terminal-status" :class="{ typing: isAiTerminalTyping }">
                                {{ isAiTerminalTyping ? '● RUNNING' : '● IDLE' }}
                            </span>
                        </div>

                        <div class="terminal-dashboard">
                            <!-- Live Telemetry Stats -->
                            <div class="telemetry-bar">
                                <div class="t-stat">
                                    <span class="t-label">TEMP:</span>
                                    <span class="t-value" :class="{ high: isAiTerminalTyping }">
                                        {{ isAiTerminalTyping ? '44°C' : '39°C' }}
                                    </span>
                                </div>
                                <div class="t-stat">
                                    <span class="t-label">NPU LOAD:</span>
                                    <span class="t-value" :class="{ active: isAiTerminalTyping }">
                                        {{ aiNpuLoad }}%
                                    </span>
                                </div>
                                <div class="t-stat">
                                    <span class="t-label">POWER:</span>
                                    <span class="t-value">
                                        {{ isAiTerminalTyping ? '8.4W' : '0.2W' }}
                                    </span>
                                </div>
                            </div>

                            <div class="npu-progress-bar">
                                <div class="npu-progress-fill" :style="{ width: aiNpuLoad + '%' }"></div>
                            </div>
                        </div>

                        <div class="terminal-body" ref="terminalBody">
                            <!-- Base welcome message -->
                            <div class="terminal-line system">
                                <span class="t-prefix">root@vinatech-elite:~$</span> ./start-local-agent.sh
                            </div>
                            <div class="terminal-line output">
                                VinaTech AI Core Engine v2.6.4-build-2026. Loaded successfully.
                                Ready for offline AI-Inference tasks. NPU acceleration enabled.
                            </div>

                            <!-- Logs when processing -->
                            <div v-for="(log, i) in aiProcessingLog" :key="i" class="terminal-line log">
                                <span class="t-prefix">[LOG]</span> {{ log }}
                            </div>

                            <!-- Typed Output -->
                            <div v-if="aiTerminalOutput" class="terminal-output-text">
                                {{ aiTerminalOutput }}<span class="cursor-blink" v-if="isAiTerminalTyping">_</span>
                            </div>

                            <div v-if="!activeAiPrompt && !isAiTerminalTyping" class="terminal-line prompt-hint">
                                &gt;&gt; Vui lòng chọn một câu lệnh prompt bên trái để bắt đầu mô phỏng xử lý...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STORY INTEGRATION WITH SECOND GENERATED IMAGE -->
                <div class="ai-story-banner">
                    <div class="ai-story-grid">
                        <div class="ai-story-text">
                            <span class="label-neon pink">BẢO MẬT DỮ LIỆU ĐỘC QUYỀN</span>
                            <h3>Tại sao chạy AI Offline lại quan trọng?</h3>
                            <p>
                                Trong thế giới số hiện đại, dữ liệu cá nhân và bí mật thương mại là tài sản vô giá. Các dòng laptop VinaTech Elite Premium tích hợp nhân xử lý AI chuyên dụng Llama-3 chạy cục bộ giúp bạn:
                            </p>
                            <ul class="ai-story-list">
                                <li>🔒 <b>Bảo mật 100%:</b> Không có bất kỳ dữ liệu, mã nguồn hay văn bản nào của bạn bị gửi lên đám mây của bên thứ ba.</li>
                                <li>✈️ <b>Hoạt động mọi nơi:</b> Cho dù bạn đang ở trên máy bay, vùng sâu vùng xa không có internet hay mạng yếu, trợ lý AI vẫn phản hồi lập tức.</li>
                                <li>⚡ <b>Độ trễ bằng 0:</b> Nhận câu trả lời ngay sau 0.1 giây mà không phải xếp hàng chờ đợi server quá tải.</li>
                            </ul>
                        </div>
                        <div class="ai-story-visual">
                            <img src="/elite_ai_assistant.png" alt="Futuristic Holographic AI Assistant" class="ai-story-img" />
                            <div class="da-mesh-glow pink"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. INTERACTIVE LAPTOP FINDER QUIZ -->
        <section id="quiz-sec" class="quiz-sec reveal-el">
            <div class="container">
                <div class="quiz-container-box">
                    <div class="quiz-glow"></div>

                    <!-- Step 1: Nhu cầu -->
                    <div v-if="quizStep === 1" class="quiz-step">
                        <span class="step-num">BƯỚC 1/3</span>
                        <h3>Nhu cầu sử dụng chính của bạn là gì?</h3>
                        <p class="step-sub">Chúng tôi sẽ lọc những cấu hình tối ưu nhất cho hoạt động hàng ngày của bạn.</p>

                        <div class="quiz-options">
                            <button class="opt-card" @click="selectDemand('gaming')">
                                <span class="opt-emoji">🎮</span>
                                <h4>Chơi Game Khủng</h4>
                                <p>Cần cấu hình GPU RTX mạnh nhất, màn hình quét cao.</p>
                            </button>
                            <button class="opt-card" @click="selectDemand('graphic')">
                                <span class="opt-emoji">🎨</span>
                                <h4>Đồ Họa & Sáng Tạo</h4>
                                <p>Cần màn hình chuẩn màu OLED/IPS, CPU nhiều nhân.</p>
                            </button>
                            <button class="opt-card" @click="selectDemand('office')">
                                <span class="opt-emoji">💼</span>
                                <h4>Văn Phòng Cao Cấp</h4>
                                <p>Mỏng nhẹ thời thượng, pin lâu, bàn phím gõ êm.</p>
                            </button>
                            <button class="opt-card" @click="selectDemand('student')">
                                <span class="opt-emoji">🎓</span>
                                <h4>Học Tập & Làm Việc Nhẹ</h4>
                                <p>Cực kỳ bền bỉ, tiết kiệm điện năng, giá thành tối ưu.</p>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Ngân sách -->
                    <div v-if="quizStep === 2" class="quiz-step">
                        <span class="step-num">BƯỚC 2/3</span>
                        <h3>Hạn mức ngân sách ước tính là bao nhiêu?</h3>
                        <p class="step-sub">Giúp tối ưu hóa mức hiệu năng trên từng đồng tiền bạn đầu tư.</p>

                        <div class="quiz-options cols-3">
                            <button class="opt-card" @click="selectBudget(1)">
                                <span class="opt-emoji">💸</span>
                                <h4>Dưới 15 Triệu</h4>
                                <p>Phù hợp học sinh sinh viên, văn phòng cơ bản.</p>
                            </button>
                            <button class="opt-card" @click="selectBudget(2)">
                                <span class="opt-emoji">💰</span>
                                <h4>15 Triệu - 25 Triệu</h4>
                                <p>Phân khúc tầm trung xuất sắc, đáp ứng đa dạng nhu cầu.</p>
                            </button>
                            <button class="opt-card" @click="selectBudget(3)">
                                <span class="opt-emoji">💎</span>
                                <h4>Trên 25 Triệu</h4>
                                <p>Flagship đỉnh cao, phần cứng hoàn mỹ không giới hạn.</p>
                            </button>
                        </div>
                        <button class="btn-back" @click="quizStep = 1">← Trở lại câu trước</button>
                    </div>

                    <!-- Step 3: Tiêu chí ưu tiên -->
                    <div v-if="quizStep === 3" class="quiz-step">
                        <span class="step-num">BƯỚC 3/3</span>
                        <h3>Đâu là yếu tố bạn ưu tiên hàng đầu?</h3>
                        <p class="step-sub">Chúng tôi sẽ hiệu chỉnh thuật toán để chọn ra đặc tính phù hợp nhất.</p>

                        <div class="quiz-options cols-3">
                            <button class="opt-card" @click="selectPriority('performance')">
                                <span class="opt-emoji">⚡</span>
                                <h4>Hiệu Năng Tối Đa</h4>
                                <p>Ưu tiên CPU/GPU mạnh nhất để render, chơi game.</p>
                            </button>
                            <button class="opt-card" @click="selectPriority('battery')">
                                <span class="opt-emoji">🔋</span>
                                <h4>Pin Siêu Trâu</h4>
                                <p>Hoạt động thoải mái cả ngày dài không cần mang sạc.</p>
                            </button>
                            <button class="opt-card" @click="selectPriority('portable')">
                                <span class="opt-emoji">⚖️</span>
                                <h4>Siêu Mỏng Nhẹ</h4>
                                <p>Độ mỏng ấn tượng, siêu nhẹ để mang đi muôn nơi.</p>
                            </button>
                        </div>
                        <button class="btn-back" @click="quizStep = 2">← Trở lại câu trước</button>
                    </div>

                    <!-- Step 4: Kết quả gợi ý thực tế -->
                    <div v-if="quizStep === 4" class="quiz-step results-step">
                        <span class="step-success">✓ ĐÃ LỌC THÀNH CÔNG DỮ LIỆU</span>
                        <h3>Chúng Tôi Đã Tìm Thấy Gợi Ý Hoàn Hảo Cho Bạn!</h3>
                        <p class="step-sub">Dựa trên nhu cầu thực tế của bạn, đây là những dòng laptop có cấu hình phù hợp và tối ưu nhất tại cửa hàng:</p>

                        <div class="recommended-grid">
                            <div class="rec-card" v-for="p in recommendedProducts" :key="p.key_id">
                                <span class="rec-badge" v-if="p.badge">{{ p.badge }}</span>
                                <div class="rec-thumb">
                                    <img :src="p.img" :alt="p.fullName" />
                                </div>
                                <div class="rec-body">
                                    <h4>{{ p.fullName }}</h4>
                                    <span class="rec-cat">{{ p.category }} • {{ p.brandName }}</span>

                                    <div class="rec-specs">
                                        <span class="rec-spec-item" v-for="s in p.specs" :key="s.label">
                                            <b>{{ s.label }}:</b> {{ s.value }}
                                        </span>
                                    </div>

                                    <div class="rec-price-row">
                                        <div class="price-box">
                                            <span class="curr-price">{{ formatPrice(p.priceNum) }}</span>
                                            <span class="old-price" v-if="p.oldPriceNum > p.priceNum">{{ formatPrice(p.oldPriceNum) }}</span>
                                        </div>
                                    </div>

                                    <div class="rec-actions">
                                        <router-link :to="`/san-pham/${p.id}?variant=${p.key_id}`" class="btn-rec-detail">
                                            Xem chi tiết
                                        </router-link>
                                        <button class="btn-rec-cart" @click="themVaoGioHang(p)">
                                            Mua ngay
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="results-footer">
                            <button class="btn-restart" @click="resetQuiz">🔄 Thực hiện lại Quiz chọn máy</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. TIER COMPARISON MATRIX -->
        <section class="matrix-sec reveal-el">
            <div class="container">
                <div class="sec-header text-center">
                    <span class="label-neon pink">SO SÁNH PHÂN KHÚC</span>
                    <h2>Tìm Kiếm Định Hướng Theo Từng Phân Khúc</h2>
                    <p class="max-w-600">Được chế tạo tỉ mỉ để đáp ứng hoàn hảo cho từng đối tượng sử dụng cụ thể.</p>
                </div>

                <div class="matrix-grid">
                    <!-- Phân khúc Pro -->
                    <div class="matrix-card">
                        <div class="card-head">
                            <span class="tier-tag">💼 ELITE PRO</span>
                            <h3>Mỏng Nhẹ Văn Phòng</h3>
                            <p>Tối ưu tính cơ động, thanh lịch và thời lượng pin dồi dào.</p>
                        </div>
                        <div class="card-price">Từ 16,990,000đ</div>
                        <ul class="card-features">
                            <li><span class="bullet-check">✓</span> Trọng lượng siêu nhẹ &lt; 1.3kg</li>
                            <li><span class="bullet-check">✓</span> Thời lượng pin từ 10 - 15 giờ liên tục</li>
                            <li><span class="bullet-check">✓</span> Màn hình OLED chuẩn màu điện ảnh</li>
                            <li><span class="bullet-check">✓</span> Vận hành không tiếng ồn cực mát</li>
                        </ul>
                        <router-link to="/san-pham?cat=3" class="btn-matrix-go">Xem các mẫu Pro →</router-link>
                    </div>

                    <!-- Phân khúc Beast (Nổi bật nhất) -->
                    <div class="matrix-card primary-card">
                        <div class="glow-border-rainbow"></div>
                        <div class="card-head">
                            <span class="tier-tag hot">🔥 ELITE BEAST</span>
                            <h3>Gaming & Đồ Họa</h3>
                            <p>Hiệu năng tối thượng cho các nhà thiết kế và game thủ chuyên nghiệp.</p>
                        </div>
                        <div class="card-price">Từ 28,500,000đ</div>
                        <ul class="card-features">
                            <li><span class="bullet-check">✓</span> CPU Intel Core i9 / Ryzen 9 khủng</li>
                            <li><span class="bullet-check">✓</span> Đồ họa NVIDIA RTX 40-Series tối tân</li>
                            <li><span class="bullet-check">✓</span> Tản nhiệt buồng hơi lỏng cao cấp</li>
                            <li><span class="bullet-check">✓</span> Màn hình 240Hz siêu mượt FPS</li>
                        </ul>
                        <router-link to="/san-pham?cat=2" class="btn-matrix-go beast-btn">Chinh Phục Ngay →</router-link>
                    </div>

                    <!-- Phân khúc Scholar -->
                    <div class="matrix-card">
                        <div class="card-head">
                            <span class="tier-tag scholar">🎓 ELITE SCHOLAR</span>
                            <h3>Sinh Viên & Cơ Bản</h3>
                            <p>Độ bền chuẩn quân đội, đáp ứng mượt mà tác vụ học tập với chi phí tối ưu.</p>
                        </div>
                        <div class="card-price">Từ 10,490,000đ</div>
                        <ul class="card-features">
                            <li><span class="bullet-check">✓</span> Độ bền chuẩn quân đội MIL-STD</li>
                            <li><span class="bullet-check">✓</span> Cấu hình ổn định đa tác vụ nhẹ</li>
                            <li><span class="bullet-check">✓</span> Bàn phím gõ êm ái chống tràn nước</li>
                            <li><span class="bullet-check">✓</span> Giá thành cực kỳ tiếp cận và bền bỉ</li>
                        </ul>
                        <router-link to="/san-pham?cat=7" class="btn-matrix-go">Xem các mẫu Scholar →</router-link>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW INTERACTIVE TRADE-IN CALCULATOR & SPECS TABLE SECTION -->
        <section class="tradein-sec reveal-el">
            <div class="container">
                <div class="sec-header text-center">
                    <span class="label-neon cyan">TRADE-IN SOLUTIONS</span>
                    <h2>Thu Cũ Đổi Mới - Lên Đời Siêu Tiết Kiệm</h2>
                    <p class="max-w-600">
                        VinaTech cam kết đồng hành hỗ trợ bạn nâng cấp công nghệ dễ dàng nhất. Sử dụng ngay bộ công cụ tính toán dưới đây để xem giá trị chiếc laptop cũ của bạn có thể quy đổi và nhận voucher trợ giá khủng.
                    </p>
                </div>

                <div class="tradein-grid">
                    <!-- Trình tính toán Thu cũ đổi mới bên trái -->
                    <div class="tradein-calculator">
                        <h4>ƯỚC TÍNH GIÁ TRỊ LÊN ĐỜI LAPTOP</h4>
                        <p class="ti-desc">Vui lòng chọn hãng sản xuất và tình trạng thực tế của chiếc máy cũ của bạn để nhận báo giá dự kiến ngay lập tức.</p>

                        <div class="ti-form">
                            <!-- Chọn Hãng -->
                            <div class="ti-group">
                                <label class="ti-label">HÃNG LAPTOP CŨ ĐANG SỬ DỤNG</label>
                                <div class="ti-brand-selector">
                                    <button 
                                        v-for="(brand, key) in tradeInBrandData" 
                                        :key="key" 
                                        class="ti-brand-btn"
                                        :class="{ active: tradeInBrand === key }"
                                        @click="tradeInBrand = key"
                                    >
                                        {{ brand.name.split(' ')[0] }}
                                    </button>
                                </div>
                            </div>

                            <!-- Chọn Tình Trạng -->
                            <div class="ti-group">
                                <label class="ti-label">TÌNH TRẠNG VẬT LÝ CỦA MÁY CŨ</label>
                                <div class="ti-condition-list">
                                    <button 
                                        v-for="(cond, key) in tradeInConditionData" 
                                        :key="key"
                                        class="ti-cond-btn"
                                        :class="{ active: tradeInCondition === key }"
                                        @click="tradeInCondition = key"
                                    >
                                        <span class="cond-name" v-if="key === 'new'">🌟 Như mới</span>
                                        <span class="cond-name" v-else-if="key === 'good'">✓ Tốt</span>
                                        <span class="cond-name" v-else-if="key === 'scratched'">⚠ Trầy nhẹ</span>
                                        <span class="cond-name" v-else>💔 Hỏng nhẹ</span>
                                        <span class="cond-desc">{{ cond.desc }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="ti-result-panel">
                            <div class="ti-result-row">
                                <span>Giá trị máy cũ thu mua dự kiến:</span>
                                <b>{{ formatPrice(tradeInResult.estimateVal) }}</b>
                            </div>
                            <div class="ti-result-row">
                                <span>Voucher trợ giá VVIP lên đời:</span>
                                <b class="glow-txt-pink">+ {{ formatPrice(tradeInResult.bonusVal) }}</b>
                            </div>
                            <div class="ti-result-row total-discount">
                                <span>Tổng cộng ưu đãi trừ trực tiếp:</span>
                                <b class="glow-txt-cyan">- {{ formatPrice(tradeInResult.totalDiscount) }}</b>
                            </div>
                            <div class="ti-final-row">
                                <div class="price-meta">
                                    <span>Giá niêm yết Elite Premium:</span>
                                    <span class="price-old">{{ formatPrice(tradeInResult.targetPrice) }}</span>
                                </div>
                                <div class="final-payment-box">
                                    <span>SỐ TIỀN THỰC TẾ CẦN TRẢ:</span>
                                    <span class="payment-val">{{ formatPrice(tradeInResult.finalPayment) }}</span>
                                </div>
                            </div>
                            <button class="btn-claim-tradein" @click="swal.success('Đã đăng ký', 'Đăng ký Thu Cũ Đổi Mới thành công! Chuyên viên VinaTech sẽ liên hệ kiểm định máy cho bạn trong 10 phút.')">
                                Đăng Ký Lên Đời Elite Ngay →
                            </button>
                        </div>
                    </div>

                    <!-- Bảng so sánh thông số kỹ thuật bên phải -->
                    <div class="specs-comparison-box">
                        <h4>BẢNG THÔNG SỐ KỸ THUẬT SO SÁNH CHI TIẾT</h4>
                        <p class="ti-desc">Phân tích chi tiết và đối chiếu trực quan thông số phần cứng giữa các phân khúc sản phẩm VinaTech.</p>

                        <div class="table-responsive-wrapper">
                            <table class="specs-table">
                                <thead>
                                    <tr>
                                        <th>Thông số</th>
                                        <th class="scholar-header">Elite Scholar</th>
                                        <th class="pro-header active">Elite Pro</th>
                                        <th class="beast-header">Elite Beast</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in specsMatrix" :key="row.param">
                                        <td class="row-param">{{ row.param }}</td>
                                        <td class="row-scholar">{{ row.scholar }}</td>
                                        <td class="row-pro active">{{ row.pro }}</td>
                                        <td class="row-beast">{{ row.beast }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW INTERACTIVE BATTERY & ENERGY CALCULATOR SECTION -->
        <section class="battery-calc-sec reveal-el">
            <div class="container">
                <div class="battery-calc-grid">
                    <div class="bc-text-block">
                        <span class="label-neon cyan">TỰ CHỦ NĂNG LƯỢNG THÔNG MINH</span>
                        <h2>Năng Lượng Bền Bỉ, Kiểm Soát Tuyệt Đối</h2>
                        <p class="bc-intro">
                            Với dung lượng pin cực đại 99.6 Watt-giờ (giới hạn tối đa được phép mang lên máy bay theo quy định hàng không quốc tế), VinaTech Elite mang lại thời lượng sử dụng bền bỉ đáng kinh ngạc. Hãy kéo thanh trượt độ sáng màn hình và chọn tác vụ của bạn bên phải để kiểm tra thời lượng pin mô phỏng thực tế.
                        </p>

                        <div class="bc-calculations">
                            <div class="time-remaining-callout">
                                <span class="calc-label">THỜI GIAN SỬ DỤNG DỰ KIẾN</span>
                                <div class="time-display">
                                    <span class="hours-glow">{{ batteryStats.hours }}</span>h 
                                    <span class="hours-glow">{{ batteryStats.minutes }}</span>m
                                </div>
                                <p class="calc-sub">*Mô phỏng dựa trên pin dung lượng 99.6Wh mới 100%.</p>
                            </div>

                            <div class="bc-tip-box">
                                <span class="tip-icon">💡</span>
                                <p>{{ batteryStats.tip }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bc-control-block">
                        <h4>TRÌNH GIẢ LẬP TIÊU THỤ ĐIỆN NĂNG</h4>
                        
                        <div class="bc-control-group">
                            <div class="control-label-row">
                                <span>ĐỘ SÁNG MÀN HÌNH</span>
                                <b>{{ batteryBrightness }}%</b>
                            </div>
                            <div class="slider-wrapper">
                                <input 
                                    type="range" 
                                    min="10" 
                                    max="100" 
                                    v-model="batteryBrightness" 
                                    class="bc-slider"
                                />
                            </div>
                        </div>

                        <div class="bc-control-group">
                            <div class="control-label-row">
                                <span>TÁC VỤ HOẠT ĐỘNG KHUYÊN DÙNG</span>
                            </div>
                            <div class="workload-buttons">
                                <button 
                                    v-for="(detail, key) in workloadDetails" 
                                    :key="key"
                                    class="workload-btn"
                                    :class="{ active: batteryWorkload === key }"
                                    @click="batteryWorkload = key"
                                >
                                    {{ detail.name.split(' & ')[0].split(' (')[0] }}
                                </button>
                            </div>
                        </div>

                        <div class="energy-chart-card">
                            <h5>PHÂN BỔ TIÊU THỤ CÔNG SUẤT: <b>{{ batteryStats.totalWatts }}W</b></h5>
                            
                            <div class="energy-bar-list">
                                <!-- Display -->
                                <div class="energy-row">
                                    <div class="row-meta">
                                        <span>Màn hình Lumina OLED</span>
                                        <b>{{ batteryStats.displayPercent }}%</b>
                                    </div>
                                    <div class="eb-track">
                                        <div class="eb-fill display-fill" :style="{ width: batteryStats.displayPercent + '%' }"></div>
                                    </div>
                                </div>

                                <!-- CPU -->
                                <div class="energy-row">
                                    <div class="row-meta">
                                        <span>Vi xử lý (CPU/NPU)</span>
                                        <b>{{ batteryStats.cpuPercent }}%</b>
                                    </div>
                                    <div class="eb-track">
                                        <div class="eb-fill cpu-fill" :style="{ width: batteryStats.cpuPercent + '%' }"></div>
                                    </div>
                                </div>

                                <!-- GPU -->
                                <div class="energy-row">
                                    <div class="row-meta">
                                        <span>Đồ họa chuyên dụng (GPU)</span>
                                        <b>{{ batteryStats.gpuPercent }}%</b>
                                    </div>
                                    <div class="eb-track">
                                        <div class="eb-fill gpu-fill" :style="{ width: batteryStats.gpuPercent + '%' }"></div>
                                    </div>
                                </div>

                                <!-- Other -->
                                <div class="energy-row">
                                    <div class="row-meta">
                                        <span>Quạt tản nhiệt & Bo mạch</span>
                                        <b>{{ batteryStats.systemPercent }}%</b>
                                    </div>
                                    <div class="eb-track">
                                        <div class="eb-fill system-fill" :style="{ width: batteryStats.systemPercent + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW ADVANCED IDEA: ACCESSORIES ECOSYSTEM -->
        <section class="ecosystem-sec reveal-el">
            <div class="container">
                <div class="ecosystem-grid">
                    <div class="eco-visual">
                        <img src="/elite_accessories.png" alt="VinaTech Elite Ecosystem Accessories" class="eco-img" />
                        <div class="ambient-glow-blue-right"></div>
                    </div>
                    <div class="eco-text">
                        <span class="label-neon cyan">HỆ SINH THÁI ELITE</span>
                        <h2>Mở Rộng Không Gian Làm Việc Chuyên Nghiệp</h2>
                        <p class="eco-intro">
                            Nâng tầm hiệu suất công việc của bạn lên gấp nhiều lần nhờ hệ sinh thái phụ kiện kết nối đồng bộ được thiết kế riêng dành riêng cho dòng sản phẩm VinaTech Elite Premium.
                        </p>
                        
                        <div class="eco-items-grid">
                            <div class="eco-card-item">
                                <h5>🔌 VinaTech Docking Station 10-in-1</h5>
                                <p>Truy xuất đồng thời ra 3 màn hình 4K 60Hz, sạc nhanh Power Delivery 100W và truyền dữ liệu tốc độ cao 40Gbps qua cổng Thunderbolt 4.</p>
                            </div>
                            <div class="eco-card-item">
                                <h5>💼 Bao da Carbon sợi siêu bền</h5>
                                <p>Chế tác từ chất liệu sợi carbon cao cấp chống thấm nước tuyệt đối, lớp nhung chống sốc chuẩn quân đội bảo vệ laptop an toàn trong mọi chuyến đi.</p>
                            </div>
                            <div class="eco-card-item">
                                <h5>🖱️ Chuột Ergonomic Precision Wireless</h5>
                                <p>Thiết kế công thái học bảo vệ cổ tay, mắt đọc hồng ngoại siêu nhạy 20,000 DPI hoạt động mượt mà trên mọi bề mặt kính cường lực.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW LIFESTYLE & EXECUTIVE USE SECTION -->
        <section class="lifestyle-sec reveal-el">
            <div class="container">
                <div class="lifestyle-grid">
                    <div class="ls-text-box">
                        <span class="label-neon pink">THE ELITE EXECUTIVE LIFESTYLE</span>
                        <h2>Khi Đẳng Cấp Đồng Hành Cùng Phong Cách Sống</h2>
                        <p class="ls-intro">
                            VinaTech Elite Premium không chỉ đơn thuần là một thiết bị công nghệ hỗ trợ công việc, mà nó còn là biểu tượng khẳng định phong cách sống và tư duy thẩm mỹ đỉnh cao của bạn. Thiết bị được sinh ra để hòa quyện hoàn hảo vào những không gian làm việc hiện đại nhất toàn cầu.
                        </p>
                        <div class="ls-lifestyle-blocks">
                            <div class="ls-block">
                                <h5>🌅 Buổi Sáng Sáng Tạo & Tự Do Ngoại Tuyến</h5>
                                <p>Bắt đầu ngày mới tại ban công căn hộ Penthouse hướng ra bình minh thành phố hoặc trên những chuyến bay quốc tế sang trọng. Với thời lượng pin thực tế lên đến 18 tiếng, bạn tự tin xử lý mọi tài liệu, biên dịch mã nguồn phức tạp hoàn toàn ngoại tuyến mà không cần bận tâm tìm kiếm ổ cắm sạc điện thoại.</p>
                            </div>
                            <div class="ls-block">
                                <h5>💻 Buổi Chiều Kết Nối & Quản Trị Hệ Sinh Thái</h5>
                                <p>Trở về văn phòng làm việc tối giản, kết nối laptop qua một cổng duy nhất tới Docking Station Thunderbolt 4 để xuất hình ảnh ra 3 màn hình 4K độ phân giải siêu nét. Elite Premium hoạt động như một bộ não trung tâm mạnh mẽ điều phối toàn bộ luồng công việc khổng lồ một cách mượt mà và mát mẻ.</p>
                            </div>
                            <div class="ls-block">
                                <h5>🌃 Buổi Tối Sáng Tạo Nghệ Thuật & Thư Giãn Đỉnh Cao</h5>
                                <p>Khi hoàng hôn buông xuống thành phố, chiếc laptop lại trở thành một rạp chiếu phim thu nhỏ sống động. Màn hình Lumina OLED phản chiếu dải màu điện ảnh rực rỡ kết hợp hệ loa Dolby Atmos vòm đa chiều mang lại những phút giây đắm chìm trong âm nhạc nghệ thuật và những tựa game AAA đỉnh cao.</p>
                            </div>
                        </div>
                    </div>
                    <div class="ls-image-box">
                        <img src="/elite_workspace.png" alt="Sleek VinaTech Elite Laptop in Luxury Workspace" class="ls-img" />
                        <div class="ambient-glow-orange-right"></div>
                        <div class="ls-hud-tag">EXECUTIVE EDITION 2026</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. LIMITED FLASH DEAL & LIVE COUNTDOWN -->
        <section class="deal-sec reveal-el">
            <div class="container">
                <div class="deal-box">
                    <div class="deal-grid">
                        <div class="deal-content">
                            <div class="deal-badge-glowing">⚡ FLASH DEAL ĐỘC QUYỀN TRONG NGÀY</div>
                            <h2>Gói Combo Nâng Cấp Vàng Elite</h2>
                            <p class="deal-desc">Mua bất kỳ dòng máy tính thuộc phiên bản Elite ngày hôm nay, nhận ngay quà tặng cao cấp trị giá lên đến <b>4,500,000đ</b> gồm:</p>
                            
                            <ul class="deal-items">
                                <li>🎒 Balo chống nước cao cấp chuẩn Smart-Guard (Trị giá 1,200k)</li>
                                <li>🖱️ Chuột công thái học cao cấp Silent Click (Trị giá 800k)</li>
                                <li>🛡️ Gói bảo hành mở rộng toàn diện thêm 12 tháng (Trị giá 1,500k)</li>
                                <li>✨ Gói vệ sinh máy & cài đặt phần mềm bản quyền 1 năm (Trị giá 1,000k)</li>
                            </ul>

                            <div class="stock-progress-area">
                                <div class="stock-label">
                                    <span>Đã bán: <b>7 Gói</b></span>
                                    <span>Chỉ còn: <b>3 Gói ưu đãi cuối cùng!</b></span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-bar" style="width: 70%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="deal-timer-area text-center">
                            <h4 class="timer-title">ƯU ĐÃI SẼ KẾT THÚC SAU</h4>
                            
                            <div class="countdown-timer">
                                <div class="time-block">
                                    <span class="number">{{ countdownTime.hours }}</span>
                                    <span class="label">GIỜ</span>
                                </div>
                                <span class="colon">:</span>
                                <div class="time-block">
                                    <span class="number">{{ countdownTime.minutes }}</span>
                                    <span class="label">PHÚT</span>
                                </div>
                                <span class="colon">:</span>
                                <div class="time-block">
                                    <span class="number">{{ countdownTime.seconds }}</span>
                                    <span class="label">GIÂY</span>
                                </div>
                            </div>

                            <router-link to="/san-pham" class="btn-claim-deal">
                                Nhận Ưu Đãi Ngay →
                            </router-link>
                            <span class="deal-note">* Áp dụng cho 10 khách hàng đặt cọc sớm nhất trong ngày.</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEW BRAND IDEA: VVIP PRIVILEGE & VIP SERVICES -->
        <section class="vvip-sec reveal-el">
            <div class="container">
                <div class="sec-header text-center">
                    <span class="label-neon pink">VVIP PRIVILEGE PROGRAM</span>
                    <h2>Đặc Quyền VVIP Dành Cho Bạn</h2>
                    <p class="max-w-600">Sở hữu VinaTech Elite Premium là sở hữu sự chăm sóc VIP tận tâm từ đội ngũ chuyên gia công nghệ chuyên nghiệp nhất.</p>
                </div>

                <div class="vvip-grid">
                    <div class="vvip-card">
                        <div class="vvip-icon">🛡️</div>
                        <h4>Bảo hành vàng 1-Đổi-1 trong 30 ngày</h4>
                        <p>Bất kỳ lỗi phần cứng nào từ nhà sản xuất sẽ được đổi mới hoàn toàn một chiếc laptop niêm phong mới ngay lập tức mà không cần chờ đợi thẩm định sửa chữa lâu ngày.</p>
                    </div>
                    <div class="vvip-card">
                        <div class="vvip-icon">☎️</div>
                        <h4>Đường dây nóng hỗ trợ VVIP 24/7</h4>
                        <p>Một tổng đài điện thoại riêng biệt được kết nối trực tiếp đến các kỹ sư phần cứng cấp cao của VinaTech để xử lý mọi thắc mắc phần mềm hay cấu hình từ xa bất kể ngày đêm.</p>
                    </div>
                    <div class="vvip-card">
                        <div class="vvip-icon">⚙️</div>
                        <h4>Chăm sóc vệ sinh phần cứng trọn đời</h4>
                        <p>Hỗ trợ bảo dưỡng thổi bụi, làm sạch bàn phím màn hình và tra keo tản nhiệt chất lượng cao định kỳ miễn phí 2 lần mỗi năm tại bất kỳ chi nhánh nào của VinaTech trên toàn quốc.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. PREMIUM TESTIMONIALS -->
        <section class="testimonials-sec reveal-el">
            <div class="container">
                <div class="sec-header text-center">
                    <span class="label-neon cyan">PHẢN HỒI THỰC TẾ</span>
                    <h2>Khách Hàng Nói Gì Về VinaTech?</h2>
                    <p class="max-w-600">Chúng tôi tự hào đồng hành cùng hàng ngàn lập trình viên, designer và game thủ hàng đầu.</p>
                </div>

                <div class="testimonials-grid">
                    <div class="testi-card">
                        <div class="quote-icon">“</div>
                        <p class="testi-content">Trải nghiệm mua sắm ở VinaTech cực kỳ đẳng cấp. Các mẫu máy Elite được đóng gói kỹ lưỡng, kĩ thuật viên giao nhanh tận nhà và cài đặt toàn bộ công cụ lập trình chu đáo. Hiệu năng Core Ultra 9 thực sự quá khủng.</p>
                        <div class="user-info">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Trần Minh Quân" />
                            <div>
                                <h5>Trần Minh Quân</h5>
                                <span>Senior Software Engineer</span>
                            </div>
                        </div>
                        <div class="stars">⭐⭐⭐⭐⭐</div>
                    </div>

                    <div class="testi-card">
                        <div class="quote-icon">“</div>
                        <p class="testi-content">Màn hình OLED trên dòng máy Beast màu sắc siêu chuẩn xác, phục vụ hoàn hảo cho công việc chỉnh sửa ảnh chuyên nghiệp của mình. Thiết kế máy sắc sảo, tản nhiệt êm không hề bị hú to khi render đồ họa nặng.</p>
                        <div class="user-info">
                            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Nguyễn Phương Anh" />
                            <div>
                                <h5>Nguyễn Phương Anh</h5>
                                <span>Creative Director</span>
                            </div>
                        </div>
                        <div class="stars">⭐⭐⭐⭐⭐</div>
                    </div>

                    <div class="testi-card">
                        <div class="quote-icon">“</div>
                        <p class="testi-content">Chính sách trả góp 0% cực kỳ nhanh chóng. Mình được duyệt hồ sơ online chỉ trong 5 phút. Tư vấn viên rất chuyên nghiệp, giúp so sánh chi tiết giữa ASUS và Dell để mình chọn được chiếc máy ưng ý nhất.</p>
                        <div class="user-info">
                            <img src="https://randomuser.me/api/portraits/men/52.jpg" alt="Lê Hoàng Nam" />
                            <div>
                                <h5>Lê Hoàng Nam</h5>
                                <span>Marketing Specialist</span>
                            </div>
                        </div>
                        <div class="stars">⭐⭐⭐⭐⭐</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. FREQUENTLY ASKED QUESTIONS (FAQ) -->
        <section class="faq-sec reveal-el">
            <div class="container max-w-800">
                <div class="sec-header text-center">
                    <span class="label-neon pink">FAQ</span>
                    <h2>Giải Đáp Thắc Mắc</h2>
                    <p>Mọi thông tin bạn cần biết để yên tâm mua sắm tại VinaTech Elite.</p>
                </div>

                <div class="faq-accordion">
                    <div 
                        class="faq-row" 
                        v-for="(faq, idx) in faqs" 
                        :key="idx"
                        :class="{ open: activeFaqIdx === idx }"
                    >
                        <button class="faq-quest" @click="toggleFaq(idx)">
                            <span>{{ faq.q }}</span>
                            <span class="faq-icon"></span>
                        </button>
                        <div class="faq-answ" :style="{ maxHeight: activeFaqIdx === idx ? '260px' : '0px' }">
                            <div class="faq-answ-inner">
                                <p>{{ faq.a }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. HIGH-IMPACT CALL TO ACTION -->
        <section class="cta-sec reveal-el">
            <div class="container text-center">
                <div class="cta-content-box">
                    <h2>Sẵn Sàng Nâng Tầm Trải Nghiệm Công Nghệ?</h2>
                    <p class="max-w-600">Sở hữu những cấu hình đỉnh cao nhất năm 2026 với chính sách giá ưu đãi, hỗ trợ toàn diện và quà tặng độc quyền của chúng tôi.</p>
                    
                    <div class="cta-btns">
                        <router-link to="/san-pham" class="btn-cta-primary">Mua Laptop Elite Ngay</router-link>
                        <router-link to="/lien-he" class="btn-cta-secondary">Liên hệ nhận tư vấn</router-link>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>

@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap');

.landing-premium {
  --font-title: 'Outfit', 'Inter', sans-serif;
  --font-body: 'Inter', sans-serif;
  --bg-deep: #060913;
  --bg-card: rgba(15, 23, 42, 0.45);
  --bg-card-hover: rgba(20, 32, 58, 0.65);
  --border-glow: rgba(99, 102, 241, 0.15);
  --border-neon-cyan: rgba(34, 211, 238, 0.4);
  --border-neon-pink: rgba(244, 63, 94, 0.4);
  
  --neon-cyan: #22d3ee;
  --neon-pink: #f43f5e;
  --neon-purple: #a855f7;
  --neon-blue: #3b82f6;

  --accent-glow-blue: rgba(59, 130, 246, 0.35);
  --accent-glow-green: rgba(16, 185, 129, 0.35);
  --accent-glow-purple: rgba(168, 85, 247, 0.35);
  --accent-glow-pink: rgba(244, 63, 94, 0.35);

  background-color: var(--bg-deep);
  color: #e2e8f0;
  font-family: var(--font-body);
  overflow-x: hidden;
  position: relative;
  min-height: 100vh;
}

.container {
  width: min(1200px, 92%);
  margin: 0 auto;
}

.max-w-600 {
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.max-w-800 {
  max-width: 800px;
  margin-left: auto;
  margin-right: auto;
}

.text-center {
  text-align: center;
}

/* Background glowing orbs */
.glow-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  z-index: 0;
  pointer-events: none;
  opacity: 0.15;
}
.orb-1 {
  width: 500px;
  height: 500px;
  background: var(--neon-cyan);
  top: 5%;
  right: -10%;
}
.orb-2 {
  width: 450px;
  height: 450px;
  background: var(--neon-pink);
  top: 35%;
  left: -15%;
}
.orb-3 {
  width: 600px;
  height: 600px;
  background: var(--neon-purple);
  bottom: 10%;
  right: -10%;
}

/* General Headings */
h1, h2, h3, h4, h5 {
  font-family: var(--font-title);
  color: #ffffff;
  font-weight: 700;
}

.sec-header {
  margin-bottom: 60px;
}
.sec-header h2 {
  font-size: 38px;
  margin: 12px 0 16px;
  background: linear-gradient(135deg, #ffffff 30%, #a5f3fc 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.sec-header p {
  color: #94a3b8;
  font-size: 16px;
  line-height: 1.6;
}

/* Neon text effects */
.glow-text-cyan {
  background: linear-gradient(90deg, #22d3ee, #06b6d4);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 0 10px rgba(34, 211, 238, 0.3));
}
.glow-text-pink {
  background: linear-gradient(90deg, #f43f5e, #db2777);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 0 10px rgba(244, 63, 94, 0.3));
}

.label-neon {
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 2px;
  color: var(--neon-cyan);
  text-transform: uppercase;
  text-shadow: 0 0 8px rgba(34, 211, 238, 0.4);
}
.label-neon.pink {
  color: var(--neon-pink);
  text-shadow: 0 0 8px rgba(244, 63, 94, 0.4);
}
.label-neon.cyan {
  color: var(--neon-cyan);
  text-shadow: 0 0 8px rgba(34, 211, 238, 0.4);
}

/* SCROLL REVEAL ACTIVATION CLASS */
.reveal-el {
  opacity: 0;
  transform: translateY(40px);
  transition: all 0.9s cubic-bezier(0.4, 0, 0.2, 1);
}
.reveal-el.revealed {
  opacity: 1;
  transform: translateY(0);
}

/* =======================================================
   1. HERO SECTION
   ======================================================= */
.hero-sec {
  position: relative;
  padding: 120px 0 100px;
  min-height: 90vh;
  display: flex;
  align-items: center;
  z-index: 1;
}

.hud-lines {
  position: absolute;
  inset: 0;
  background-image: 
    radial-gradient(circle at 50% 50%, transparent 45%, rgba(6, 9, 19, 0.9) 80%),
    linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
  background-size: 100% 100%, 40px 40px, 40px 40px;
  opacity: 0.75;
  pointer-events: none;
  z-index: -1;
}

.elite-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(99, 102, 241, 0.1);
  border: 1px solid rgba(99, 102, 241, 0.3);
  padding: 8px 18px;
  border-radius: 30px;
  font-size: 13px;
  font-weight: 600;
  color: #c7d2fe;
  margin-bottom: 24px;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background: var(--neon-cyan);
  border-radius: 50%;
  animation: pulse-glow 1.5s infinite alternate;
}

@keyframes pulse-glow {
  0% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(34, 211, 238, 0.7); }
  100% { transform: scale(1.2); box-shadow: 0 0 12px 4px rgba(34, 211, 238, 0.1); }
}

.hero-grid {
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  gap: 40px;
  align-items: center;
}

.hero-text-block h1 {
  font-size: 50px;
  line-height: 1.15;
  margin-bottom: 20px;
  letter-spacing: -1px;
}

.hero-desc {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.65;
  margin-bottom: 35px;
}

.hero-ctas {
  display: flex;
  gap: 16px;
  margin-bottom: 50px;
  flex-wrap: wrap;
}

.btn-shimmer {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 16px 36px;
  background: linear-gradient(135deg, var(--neon-pink) 0%, var(--neon-purple) 100%);
  color: #ffffff;
  font-weight: 700;
  font-size: 15px;
  border-radius: 14px;
  border: none;
  cursor: pointer;
  box-shadow: 0 0 25px rgba(244, 63, 94, 0.35);
  transition: all 0.3s ease;
  overflow: hidden;
  text-decoration: none;
}
.btn-shimmer:hover {
  transform: translateY(-3px);
  box-shadow: 0 0 35px rgba(244, 63, 94, 0.55);
}

.shimmer-effect {
  position: absolute;
  top: 0;
  left: -100%;
  width: 50%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
  transform: skewX(-25deg);
  animation: shimmer-anim 3.5s infinite;
}

@keyframes shimmer-anim {
  0% { left: -100%; }
  30% { left: 150%; }
  100% { left: 150%; }
}

.btn-glass-neon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 16px 32px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #ffffff;
  font-weight: 600;
  font-size: 15px;
  border-radius: 14px;
  transition: all 0.3s ease;
  cursor: pointer;
  text-decoration: none;
}
.btn-glass-neon:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--neon-cyan);
  box-shadow: 0 0 20px rgba(34, 211, 238, 0.25);
  transform: translateY(-3px);
}

.hero-stats {
  display: flex;
  align-items: center;
  gap: 24px;
}
.stat-box h4 {
  font-size: 26px;
  margin: 0 0 4px;
  color: #ffffff;
}
.stat-box span {
  font-size: 12px;
  color: #64748b;
}
.stat-divider {
  width: 1px;
  height: 36px;
  background: rgba(255, 255, 255, 0.1);
}

.laptop-canvas {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.ambient-glow-circle {
  position: absolute;
  width: 320px;
  height: 320px;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, transparent 70%);
  z-index: 0;
  pointer-events: none;
  animation: float-slow 8s infinite alternate ease-in-out;
}

.laptop-mockup-wrapper {
  position: relative;
  z-index: 1;
  border-radius: 20px;
  padding: 10px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
  transition: transform 0.5s ease;
}

.laptop-front-img {
  max-width: 100%;
  height: auto;
  border-radius: 12px;
  display: block;
}

.tech-glow-borders {
  position: absolute;
  inset: -1px;
  border-radius: 20px;
  padding: 1px;
  background: linear-gradient(135deg, var(--neon-cyan), transparent, var(--neon-pink));
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  pointer-events: none;
  opacity: 0.6;
}

.float-card {
  position: absolute;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.3);
  z-index: 2;
  animation: float-slow 5s infinite alternate ease-in-out;
}
.card-ai { top: 15%; left: -5%; }
.card-fps { bottom: 12%; right: -5%; animation-delay: 2.5s; }

.float-card .icon {
  font-size: 20px;
  background: rgba(255, 255, 255, 0.05);
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.float-card h5 { font-size: 14px; margin: 0; color: #ffffff; }
.float-card span { font-size: 11px; color: #94a3b8; }

@keyframes float-slow {
  0% { transform: translateY(0px) rotate(0deg); }
  100% { transform: translateY(-12px) rotate(1deg); }
}

/* =======================================================
   NEW BRAND SECTION: PHILOSOPHY OF DESIGN
   ======================================================= */
.philosophy-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.philosophy-grid {
  display: grid;
  grid-template-columns: 0.95fr 1.05fr;
  gap: 60px;
  align-items: center;
}

.phil-image-box {
  position: relative;
  border-radius: 28px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

.phil-img {
  width: 100%;
  height: auto;
  border-radius: 20px;
  display: block;
}

.glow-corners-matrix {
  position: absolute;
  inset: 0;
  border: 1px solid rgba(244, 63, 94, 0.2);
  pointer-events: none;
  border-radius: 28px;
  z-index: 5;
}

.phil-text-box h2 {
  font-size: 38px;
  margin: 16px 0 20px;
  background: linear-gradient(135deg, #ffffff 40%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.phil-intro {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 30px;
}

.phil-features {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.phil-feat-item {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

.num-box {
  background: rgba(244, 63, 94, 0.1);
  border: 1px solid rgba(244, 63, 94, 0.25);
  width: 44px;
  height: 44px;
  border-radius: 12px;
  color: var(--neon-pink);
  font-family: var(--font-title);
  font-weight: 800;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.phil-feat-item h5 {
  font-size: 16px;
  margin: 0 0 6px;
  color: #ffffff;
}

.phil-feat-item p {
  font-size: 13.5px;
  color: #64748b;
  margin: 0;
  line-height: 1.6;
}

/* =======================================================
   2. SPECS VISUALIZER
   ======================================================= */
.visualizer-sec {
  padding: 100px 0;
  position: relative;
  background: rgba(5, 8, 16, 0.4);
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.visualizer-grid {
  display: grid;
  grid-template-columns: 1fr 1.6fr;
  gap: 40px;
  align-items: stretch;
}

.visualizer-tabs {
  display: flex;
  flex-direction: column;
  gap: 14px;
  justify-content: center;
}

.tab-btn {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  padding: 16px 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  text-align: left;
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}
.tab-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.1);
  transform: translateX(5px);
}

.tab-indicator {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: var(--neon-cyan);
  transform: scaleY(0);
  transition: transform 0.3s ease;
}

.tab-btn.active {
  background: rgba(99, 102, 241, 0.08);
  border-color: rgba(99, 102, 241, 0.25);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}
.tab-btn.active .tab-indicator { transform: scaleY(1); }
.tab-btn:nth-child(2).active .tab-indicator { background: var(--neon-pink); }
.tab-btn:nth-child(3).active .tab-indicator { background: var(--neon-purple); }
.tab-btn:nth-child(4).active .tab-indicator { background: var(--neon-blue); }

.tab-title {
  display: block;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1.5px;
  color: #64748b;
  margin-bottom: 4px;
}
.tab-btn.active .tab-title { color: #ffffff; }

.tab-subtitle {
  display: block;
  font-size: 15px;
  font-weight: 700;
  color: #94a3b8;
}
.tab-btn.active .tab-subtitle { color: #ffffff; }

.visualizer-display {
  position: relative;
  background: rgba(10, 18, 36, 0.7);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 28px;
  padding: 44px;
  overflow: hidden;
  display: flex;
  align-items: center;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
  transition: all 0.5s ease;
}

.display-glow-effect {
  position: absolute;
  width: 250px;
  height: 250px;
  right: -5%;
  top: -5%;
  background: radial-gradient(circle, var(--glow-color) 0%, transparent 70%);
  z-index: 0;
  pointer-events: none;
  filter: blur(40px);
  opacity: 0.5;
}

.display-content-card {
  position: relative;
  z-index: 1;
  width: 100%;
}

.tech-badge {
  font-size: 11px;
  font-weight: 700;
  color: var(--neon-cyan);
  border: 1px solid rgba(34, 211, 238, 0.2);
  padding: 4px 12px;
  border-radius: 20px;
  background: rgba(34, 211, 238, 0.05);
}

.display-content-card h3 {
  font-size: 30px;
  margin: 16px 0 6px;
  background: linear-gradient(90deg, #ffffff 40%, #c084fc 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.spec-sub {
  display: block;
  font-size: 15px;
  color: #a78bfa;
  font-weight: 500;
  margin-bottom: 20px;
}

.spec-desc {
  font-size: 15px;
  color: #94a3b8;
  line-height: 1.65;
  margin-bottom: 30px;
}

.spec-metric-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 24px;
}

.metric-item { display: flex; flex-direction: column; }
.m-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
.m-val { font-size: 16px; font-weight: 700; color: #ffffff; }

/* =======================================================
   NEW BRAND SECTION: ADVANCED THERMAL ENGINEERING
   ======================================================= */
.thermals-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.thermals-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 60px;
  align-items: center;
}

.thermals-text h2 {
  font-size: 38px;
  margin: 16px 0 20px;
  background: linear-gradient(135deg, #ffffff 40%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.thermals-intro {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 30px;
}

.thermal-indicators {
  display: flex;
  gap: 30px;
  margin-bottom: 35px;
}

.thermal-box {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  padding: 16px 24px;
  flex: 1;
  text-align: center;
}
.thermal-box h3 {
  font-size: 26px;
  margin: 0 0 4px;
  color: var(--neon-cyan);
}
.thermal-box span {
  font-size: 12px;
  color: #64748b;
}

.thermals-detail {
  font-size: 14.5px;
  color: #94a3b8;
  line-height: 1.65;
}

.thermals-image {
  position: relative;
  border-radius: 28px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
}

.thermal-img {
  width: 100%;
  height: auto;
  border-radius: 20px;
  display: block;
}

.ambient-glow-red {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 50%, rgba(239, 68, 68, 0.15) 0%, transparent 60%);
  pointer-events: none;
}

/* =======================================================
   NEW BRAND SECTION: INTERACTIVE AI NPU SIMULATOR
   ======================================================= */
.ai-sim-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.ai-sim-box {
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 28px;
  padding: 44px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
}

.ai-tabs {
  display: flex;
  gap: 12px;
  margin-bottom: 40px;
  flex-wrap: wrap;
  justify-content: center;
}

.ai-tab-btn {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  color: #94a3b8;
  padding: 12px 24px;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14.5px;
  transition: all 0.3s;
}
.ai-tab-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #ffffff;
}
.ai-tab-btn.active {
  background: linear-gradient(135deg, var(--neon-pink) 0%, var(--neon-purple) 100%);
  color: #ffffff;
  border-color: transparent;
  box-shadow: 0 4px 15px rgba(244, 63, 94, 0.3);
}

.ai-display-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 40px;
  align-items: center;
}

.ai-sim-info h3 {
  font-size: 26px;
  margin: 16px 0 6px;
  color: #ffffff;
}

.ai-badge {
  font-size: 11px;
  color: var(--neon-pink);
  border: 1px solid rgba(244, 63, 94, 0.2);
  padding: 4px 12px;
  border-radius: 20px;
  background: rgba(244, 63, 94, 0.05);
}

.ai-sub {
  display: block;
  font-size: 14px;
  color: #cbd5e1;
  font-weight: 500;
  margin-bottom: 16px;
}

.ai-text {
  font-size: 14.5px;
  color: #94a3b8;
  line-height: 1.65;
  margin-bottom: 24px;
}

.speed-boost-callout {
  background: rgba(168, 85, 247, 0.08);
  border: 1px solid rgba(168, 85, 247, 0.2);
  border-radius: 16px;
  padding: 20px;
}
.speed-boost-callout h4 {
  font-size: 18px;
  margin: 0 0 6px;
}
.speed-boost-callout h4 span {
  color: var(--neon-pink);
  font-size: 24px;
  font-weight: 800;
}
.speed-boost-callout p {
  font-size: 12.5px;
  color: #64748b;
  margin: 0;
}

.ai-sim-charts {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.chart-row {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.chart-label {
  display: flex;
  justify-content: space-between;
  font-size: 13.5px;
}

.bar-track {
  height: 12px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  border-radius: 10px;
  transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}
.npu-fill {
  background: linear-gradient(90deg, var(--neon-cyan), var(--neon-blue));
  box-shadow: 0 0 10px rgba(34, 211, 238, 0.4);
}
.cpu-fill {
  background: #475569;
}

.charts-note {
  font-size: 11px;
  color: #64748b;
  text-align: right;
  display: block;
}

/* =======================================================
   3. LAPTOP FINDER QUIZ
   ======================================================= */
.quiz-sec {
  padding: 100px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.quiz-container-box {
  position: relative;
  background: var(--bg-card);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 32px;
  padding: 60px 40px;
  overflow: hidden;
  box-shadow: 0 30px 70px rgba(0, 0, 0, 0.5);
}

.quiz-glow {
  position: absolute;
  width: 400px;
  height: 400px;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  background: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, transparent 60%);
  pointer-events: none;
  z-index: 0;
}

.quiz-step {
  position: relative;
  z-index: 1;
  text-align: center;
  max-width: 900px;
  margin: 0 auto;
}

.step-num {
  font-size: 12px;
  font-weight: 800;
  color: var(--neon-pink);
  letter-spacing: 2px;
}

.quiz-step h3 {
  font-size: 32px;
  margin: 10px 0 12px;
  background: linear-gradient(135deg, #ffffff 40%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.step-sub {
  color: #94a3b8;
  font-size: 16px;
  margin-bottom: 40px;
}

.quiz-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}
.quiz-options.cols-3 { grid-template-columns: repeat(3, 1fr); }

.opt-card {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  padding: 30px 20px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}
.opt-card:hover {
  background: var(--bg-card-hover);
  border-color: var(--neon-cyan);
  box-shadow: 0 10px 25px rgba(34, 211, 238, 0.15);
  transform: translateY(-6px);
}

.opt-emoji { font-size: 36px; margin-bottom: 16px; display: block; }
.opt-card h4 { font-size: 18px; margin: 0 0 10px; color: #ffffff; }
.opt-card p { font-size: 13px; color: #64748b; margin: 0; line-height: 1.5; }

.btn-back {
  background: transparent;
  border: none;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 30px;
  transition: color 0.3s;
}
.btn-back:hover { color: #ffffff; }

.step-success {
  font-size: 11px;
  font-weight: 800;
  color: #10b981;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.25);
  padding: 6px 18px;
  border-radius: 20px;
  letter-spacing: 1px;
  display: inline-block;
  margin-bottom: 12px;
}

.recommended-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  text-align: left;
  margin: 30px 0;
}

.rec-card {
  position: relative;
  background: rgba(10, 15, 30, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  overflow: hidden;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}
.rec-card:hover {
  border-color: var(--neon-pink);
  box-shadow: 0 12px 30px rgba(244, 63, 94, 0.15);
  transform: translateY(-4px);
}

.rec-badge {
  position: absolute;
  top: 15px;
  left: 15px;
  background: var(--neon-pink);
  color: white;
  font-size: 10px;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 6px;
  z-index: 10;
}

.rec-thumb {
  position: relative;
  height: 200px;
  overflow: hidden;
  background: #0d1222;
  display: flex;
  align-items: center;
  justify-content: center;
}
.rec-thumb img {
  max-width: 90%;
  max-height: 85%;
  object-fit: contain;
  transition: transform 0.5s ease;
}
.rec-card:hover .rec-thumb img { transform: scale(1.06); }

.rec-body {
  padding: 24px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.rec-body h4 {
  font-size: 18px;
  margin: 0 0 6px;
  line-height: 1.4;
  height: 50px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.rec-cat { font-size: 12px; color: #64748b; margin-bottom: 16px; display: block; }
.rec-specs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
.rec-spec-item {
  font-size: 12px;
  background: rgba(255, 255, 255, 0.03);
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: #94a3b8;
}

.rec-price-row { margin-top: auto; margin-bottom: 20px; }
.rec-price-row .curr-price { font-size: 20px; font-weight: 800; color: #ffffff; }
.rec-price-row .old-price { font-size: 14px; color: #64748b; text-decoration: line-through; margin-left: 10px; }

.rec-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.btn-rec-detail {
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.02);
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
  border-radius: 10px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s;
}
.btn-rec-detail:hover { background: rgba(255,255,255,0.06); border-color: #ffffff; }

.btn-rec-cart {
  padding: 12px;
  border: none;
  background: linear-gradient(135deg, var(--neon-pink) 0%, var(--neon-purple) 100%);
  color: white;
  font-size: 14px;
  font-weight: 700;
  border-radius: 10px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}
.btn-rec-cart:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(244, 63, 94, 0.3); }

.results-footer { margin-top: 20px; }
.btn-restart {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #94a3b8;
  font-size: 13px;
  font-weight: 600;
  padding: 10px 20px;
  border-radius: 30px;
  cursor: pointer;
  transition: all 0.3s;
}
.btn-restart:hover { background: rgba(255, 255, 255, 0.05); color: #ffffff; border-color: rgba(255, 255, 255, 0.3); }

/* =======================================================
   4. TIER COMPARISON MATRIX
   ======================================================= */
.matrix-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.matrix-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  align-items: stretch;
}

.matrix-card {
  background: var(--bg-card);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 28px;
  padding: 44px 32px;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.matrix-card:hover {
  transform: translateY(-8px);
  border-color: rgba(255, 255, 255, 0.1);
  box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}

.matrix-card.primary-card {
  background: rgba(20, 24, 48, 0.6);
  border-color: rgba(168, 85, 247, 0.3);
}
.matrix-card.primary-card:hover {
  box-shadow: 0 20px 50px rgba(168, 85, 247, 0.2);
}

.glow-border-rainbow {
  position: absolute;
  inset: -1px;
  border-radius: 28px;
  padding: 1.5px;
  background: linear-gradient(135deg, var(--neon-pink) 0%, var(--neon-purple) 50%, var(--neon-cyan) 100%);
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  pointer-events: none;
}

.tier-tag {
  font-size: 11px;
  font-weight: 800;
  color: var(--neon-cyan);
  letter-spacing: 1.5px;
  background: rgba(34, 211, 238, 0.1);
  padding: 4px 10px;
  border-radius: 6px;
  display: inline-block;
  margin-bottom: 16px;
}
.tier-tag.hot { color: var(--neon-pink); background: rgba(244, 63, 94, 0.1); }
.tier-tag.scholar { color: var(--neon-purple); background: rgba(168, 85, 247, 0.1); }

.matrix-card h3 { font-size: 24px; margin: 0 0 10px; }
.matrix-card p { font-size: 14px; color: #64748b; line-height: 1.5; margin: 0; height: 60px; }
.card-price { font-size: 26px; font-weight: 800; color: #ffffff; margin: 24px 0; }

.card-features {
  list-style: none;
  padding: 0;
  margin: 0 0 40px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.card-features li {
  font-size: 14px;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 10px;
}
.bullet-check { color: var(--neon-cyan); font-weight: 700; }
.primary-card .bullet-check { color: var(--neon-pink); }

.btn-matrix-go {
  margin-top: auto;
  display: block;
  text-align: center;
  padding: 14px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  color: #ffffff;
  font-weight: 600;
  font-size: 14px;
  text-decoration: none;
  transition: all 0.3s;
}
.btn-matrix-go:hover { background: rgba(255, 255, 255, 0.08); border-color: var(--neon-cyan); }

.btn-matrix-go.beast-btn {
  background: linear-gradient(135deg, var(--neon-pink) 0%, var(--neon-purple) 100%);
  border: none;
  box-shadow: 0 4px 15px rgba(244, 63, 94, 0.2);
}
.btn-matrix-go.beast-btn:hover {
  box-shadow: 0 6px 20px rgba(244, 63, 94, 0.35);
  transform: translateY(-2px);
}

/* =======================================================
   NEW BRAND SECTION: ADVANCED ACCESSORIES ECOSYSTEM
   ======================================================= */
.ecosystem-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.ecosystem-grid {
  display: grid;
  grid-template-columns: 0.9fr 1.1fr;
  gap: 60px;
  align-items: center;
}

.eco-visual {
  position: relative;
  border-radius: 28px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
}

.eco-img {
  width: 100%;
  height: auto;
  border-radius: 20px;
  display: block;
}

.ambient-glow-blue-right {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.15) 0%, transparent 60%);
  pointer-events: none;
}

.eco-text h2 {
  font-size: 38px;
  margin: 16px 0 20px;
  background: linear-gradient(135deg, #ffffff 40%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.eco-intro {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 30px;
}

.eco-items-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.eco-card-item {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  padding: 20px 24px;
}
.eco-card-item h5 {
  font-size: 16px;
  margin: 0 0 8px;
  color: var(--neon-cyan);
}
.eco-card-item p {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
  line-height: 1.6;
}

/* =======================================================
   5. LIMITED FLASH DEAL
   ======================================================= */
.deal-sec {
  padding: 100px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.deal-box {
  background: linear-gradient(135deg, rgba(20, 10, 48, 0.6) 0%, rgba(8, 12, 36, 0.8) 100%);
  border: 1px solid rgba(168, 85, 247, 0.2);
  border-radius: 32px;
  padding: 50px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
}
.deal-box::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle at 10% 20%, rgba(244, 63, 94, 0.05) 0%, transparent 40%);
  pointer-events: none;
}

.deal-badge-glowing {
  font-size: 11px;
  font-weight: 800;
  color: var(--neon-pink);
  letter-spacing: 2px;
  text-shadow: 0 0 10px rgba(244, 63, 94, 0.5);
  margin-bottom: 14px;
}

.deal-grid {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 50px;
  align-items: center;
}

.deal-content h2 { font-size: 34px; margin: 0 0 16px; }
.deal-desc { color: #94a3b8; font-size: 15px; margin-bottom: 24px; }

.deal-items {
  list-style: none;
  padding: 0;
  margin: 0 0 35px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.deal-items li { font-size: 14px; color: #e2e8f0; }

.stock-progress-area { max-width: 480px; }
.stock-label { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; }
.stock-label b { color: #ffffff; }

.progress-track {
  height: 8px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--neon-pink), var(--neon-purple));
  border-radius: 10px;
  position: relative;
  overflow: hidden;
}
.progress-bar::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: linear-gradient(
    -45deg, 
    rgba(255, 255, 255, 0.2) 25%, 
    transparent 25%, 
    transparent 50%, 
    rgba(255, 255, 255, 0.2) 50%, 
    rgba(255, 255, 255, 0.2) 75%, 
    transparent 75%, 
    transparent
  );
  background-size: 20px 20px;
  animation: stripe-move 2s linear infinite;
}

@keyframes stripe-move {
  0% { background-position: 0 0; }
  100% { background-position: 40px 0; }
}

.deal-timer-area {
  background: rgba(10, 10, 20, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  padding: 30px;
  position: relative;
}

.timer-title {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.5px;
  color: #64748b;
  margin: 0 0 20px;
}

.countdown-timer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 30px;
}

.time-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 14px;
  width: 70px;
  padding: 12px 0;
}
.time-block .number {
  font-size: 28px;
  font-weight: 800;
  color: #ffffff;
  font-family: var(--font-title);
}
.time-block .label {
  font-size: 9px;
  color: #64748b;
  margin-top: 4px;
  font-weight: 700;
}

.colon { font-size: 24px; font-weight: 700; color: #64748b; }

.btn-claim-deal {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 15px;
  background: linear-gradient(135deg, var(--neon-cyan) 0%, var(--neon-blue) 100%);
  color: white;
  font-weight: 700;
  font-size: 15px;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  text-decoration: none;
  box-shadow: 0 5px 20px rgba(34, 211, 238, 0.25);
  transition: all 0.3s;
  width: 100%;
}
.btn-claim-deal:hover {
  box-shadow: 0 8px 25px rgba(34, 211, 238, 0.45);
  transform: translateY(-2px);
}

.deal-note { font-size: 11px; color: #64748b; display: block; margin-top: 14px; }

/* =======================================================
   NEW BRAND SECTION: VVIP PRIVILEGES & VIP SERVICES
   ======================================================= */
.vvip-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.vvip-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
}

.vvip-card {
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 24px;
  padding: 40px 30px;
  text-align: center;
  transition: all 0.3s;
}
.vvip-card:hover {
  background: rgba(244, 63, 94, 0.02);
  border-color: rgba(244, 63, 94, 0.2);
  transform: translateY(-6px);
}

.vvip-icon {
  font-size: 40px;
  margin-bottom: 20px;
}

.vvip-card h4 {
  font-size: 18px;
  margin: 0 0 12px;
  color: #ffffff;
}

.vvip-card p {
  font-size: 13.5px;
  color: #94a3b8;
  line-height: 1.6;
  margin: 0;
}

/* =======================================================
   6. PREMIUM TESTIMONIALS
   ======================================================= */
.testimonials-sec {
  padding: 100px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.testi-card {
  background: var(--bg-card);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  padding: 35px;
  position: relative;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}
.testi-card:hover {
  border-color: var(--neon-cyan);
  background: var(--bg-card-hover);
  transform: translateY(-5px);
}

.quote-icon {
  font-size: 54px;
  font-family: Georgia, serif;
  color: rgba(34, 211, 238, 0.1);
  position: absolute;
  top: 10px;
  left: 24px;
  line-height: 1;
}

.testi-content {
  font-size: 14.5px;
  color: #94a3b8;
  line-height: 1.65;
  margin: 16px 0 24px;
  position: relative;
  z-index: 1;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: auto;
}
.user-info img {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid rgba(255, 255, 255, 0.1);
}
.user-info h5 { font-size: 15px; margin: 0 0 2px; }
.user-info span { font-size: 11px; color: #64748b; }
.stars { font-size: 12px; margin-top: 14px; letter-spacing: 2px; }

/* =======================================================
   7. FREQUENTLY ASKED QUESTIONS
   ======================================================= */
.faq-sec {
  padding: 100px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.faq-accordion {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-top: 40px;
}

.faq-row {
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 16px;
  overflow: hidden;
  transition: all 0.3s;
}
.faq-row:hover {
  border-color: rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
}
.faq-row.open {
  border-color: rgba(168, 85, 247, 0.25);
  background: rgba(168, 85, 247, 0.03);
}

.faq-quest {
  width: 100%;
  background: transparent;
  border: none;
  padding: 22px 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  text-align: left;
}
.faq-quest span { font-size: 16px; font-weight: 600; color: #ffffff; }

.faq-icon {
  width: 18px;
  height: 18px;
  position: relative;
  flex-shrink: 0;
}
.faq-icon::before,
.faq-icon::after {
  content: '';
  position: absolute;
  background: var(--tn-surface);
  transition: transform 0.3s ease;
}
.faq-icon::before { top: 8px; left: 0; right: 0; height: 2px; }
.faq-icon::after { top: 0; bottom: 0; left: 8px; width: 2px; }

.faq-row.open .faq-icon::after {
  transform: rotate(90deg);
  opacity: 0;
}

.faq-answ {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.faq-answ-inner {
  padding: 0 28px 24px;
}
.faq-answ-inner p {
  color: #94a3b8;
  font-size: 14px;
  line-height: 1.65;
  margin: 0;
}

/* =======================================================
   8. HIGH-IMPACT CALL TO ACTION (CTA)
   ======================================================= */
.cta-sec {
  padding: 100px 0 120px;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.cta-content-box {
  position: relative;
  background: linear-gradient(135deg, rgba(244, 63, 94, 0.08) 0%, rgba(99, 102, 241, 0.08) 100%);
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding: 60px 40px;
  border-radius: 32px;
  overflow: hidden;
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.3);
}

.cta-content-box h2 {
  font-size: 38px;
  margin: 0 0 16px;
  letter-spacing: -1px;
}

.cta-content-box p {
  color: #94a3b8;
  font-size: 16px;
  line-height: 1.65;
  margin-bottom: 35px;
}

.cta-btns {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-cta-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 16px 36px;
  background: linear-gradient(135deg, var(--neon-pink) 0%, var(--neon-purple) 100%);
  color: white;
  font-weight: 700;
  font-size: 15px;
  border-radius: 14px;
  border: none;
  cursor: pointer;
  text-decoration: none;
  box-shadow: 0 5px 20px rgba(244, 63, 94, 0.3);
  transition: all 0.3s;
}
.btn-cta-primary:hover {
  box-shadow: 0 8px 25px rgba(244, 63, 94, 0.5);
  transform: translateY(-3px);
}

.btn-cta-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 16px 36px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
  font-weight: 600;
  font-size: 15px;
  border-radius: 14px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s;
}
.btn-cta-secondary:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--neon-cyan);
  transform: translateY(-3px);
}

/* =======================================================
   NEW BRAND SECTIONS: LUMINA OLED & SPATIAL AUDIO
   ======================================================= */
.display-audio-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.display-audio-grid {
  display: grid;
  grid-template-columns: 0.95fr 1.05fr;
  gap: 60px;
  align-items: center;
  margin-bottom: 50px;
}

.da-image-block {
  position: relative;
  border-radius: 28px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

.da-panel-img {
  width: 100%;
  height: auto;
  border-radius: 20px;
  display: block;
}

.da-mesh-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 70% 30%, rgba(244, 63, 94, 0.15) 0%, transparent 60%);
  pointer-events: none;
}
.da-mesh-glow.pink {
  background: radial-gradient(circle at 30% 70%, rgba(168, 85, 247, 0.18) 0%, transparent 60%);
}

.da-hud-indicator {
  position: absolute;
  bottom: 20px;
  right: 20px;
  font-size: 10px;
  font-weight: 800;
  color: var(--neon-cyan);
  background: rgba(15, 23, 42, 0.7);
  padding: 4px 10px;
  border-radius: 6px;
  border: 1px solid rgba(34, 211, 238, 0.2);
  letter-spacing: 1px;
}

.da-text-block h2 {
  font-size: 38px;
  margin: 16px 0 20px;
  background: linear-gradient(135deg, #ffffff 40%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.da-intro {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 30px;
}

.da-sub-features {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.da-feat {
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 16px;
  padding: 20px;
}
.da-feat h5 {
  font-size: 16px;
  margin: 0 0 8px;
  color: var(--neon-pink);
}
.da-feat p {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
  line-height: 1.6;
}

/* Sound Mixer Interactive Widget */
.sound-mixer-widget {
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 28px;
  padding: 40px;
  box-shadow: 0 25px 55px rgba(0,0,0,0.4);
}

.sound-mixer-widget .widget-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding-bottom: 24px;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 20px;
}

.widget-title {
  display: flex;
  align-items: center;
  gap: 16px;
}
.widget-icon {
  font-size: 32px;
  background: rgba(255, 255, 255, 0.03);
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.widget-title h4 { font-size: 18px; margin: 0 0 4px; }
.widget-title p { font-size: 12.5px; color: #64748b; margin: 0; }

.mixer-tabs {
  display: flex;
  gap: 8px;
  background: rgba(255, 255, 255, 0.02);
  padding: 6px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.mixer-tab-btn {
  background: transparent;
  border: none;
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
}
.mixer-tab-btn.active {
  background: linear-gradient(135deg, var(--neon-pink) 0%, var(--neon-purple) 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(244, 63, 94, 0.25);
}

.mixer-body {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 40px;
  align-items: center;
}

.mixer-info {
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.03);
  border-radius: 20px;
  padding: 30px;
}
.mixer-badge {
  font-size: 10px;
  font-weight: 800;
  color: var(--neon-pink);
  border: 1px solid rgba(244, 63, 94, 0.3);
  background: rgba(244, 63, 94, 0.08);
  padding: 4px 10px;
  border-radius: 20px;
  letter-spacing: 1px;
}
.mixer-info h3 { font-size: 24px; margin: 16px 0 6px; }
.mixer-sub { font-size: 13.5px; color: #64748b; display: block; margin-bottom: 16px; }
.mixer-desc { font-size: 14px; color: #94a3b8; line-height: 1.65; margin: 0; }

.equalizer-visualizer {
  background: #090c15;
  border: 1px solid rgba(255, 255, 255, 0.03);
  border-radius: 24px;
  padding: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
  position: relative;
  overflow: hidden;
}
.equalizer-visualizer::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: linear-gradient(rgba(255, 255, 255, 0.01) 1px, transparent 1px);
  background-size: 100% 20px;
  pointer-events: none;
}

.eq-container {
  height: 140px;
  display: flex;
  align-items: flex-end;
  gap: 8px;
  width: 100%;
  justify-content: center;
}

.eq-bar {
  width: 12px;
  background: linear-gradient(0deg, var(--neon-pink), var(--neon-cyan));
  border-radius: 6px;
  box-shadow: 0 0 10px rgba(34, 211, 238, 0.3);
  animation: bounce-eq 1s infinite alternate ease-in-out;
  transform-origin: bottom;
  transition: height 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes bounce-eq {
  0% { transform: scaleY(0.1); }
  100% { transform: scaleY(1); }
}

.eq-legend {
  font-size: 10px;
  font-weight: 700;
  color: #475569;
  letter-spacing: 1px;
}

/* =======================================================
   NEW BRAND SECTIONS: LOCAL AI PLAYGROUND TERMINAL
   ======================================================= */
.ai-playground-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.ai-playground-grid {
  display: grid;
  grid-template-columns: 0.85fr 1.15fr;
  gap: 40px;
  margin-bottom: 60px;
}

.ai-prompts-sidebar {
  background: rgba(15, 23, 42, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 28px;
  padding: 30px;
}
.ai-prompts-sidebar h4 { font-size: 16px; margin: 0 0 8px; letter-spacing: 1px; color: #ffffff; }
.sidebar-intro { font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }

.prompt-cards-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.prompt-btn-card {
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 16px;
  padding: 18px;
  text-align: left;
  cursor: pointer;
  transition: all 0.3s;
  width: 100%;
}
.prompt-btn-card:hover:not(.disabled) {
  background: rgba(255, 255, 255, 0.03);
  border-color: var(--neon-cyan);
  transform: translateX(4px);
}
.prompt-btn-card.active {
  background: rgba(34, 211, 238, 0.04);
  border-color: var(--neon-cyan);
  box-shadow: 0 0 15px rgba(34, 211, 238, 0.1);
}
.prompt-btn-card.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.p-card-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
.p-icon { font-size: 18px; }
.p-card-head h5 { font-size: 14px; margin: 0; color: #ffffff; }
.prompt-btn-card.active h5 { color: var(--neon-cyan); }
.p-card-text { font-size: 12px; color: #64748b; margin: 0; line-height: 1.4; }

/* AI Terminal Simulator styling */
.ai-terminal-window {
  background: #05070f;
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 30px 60px rgba(0,0,0,0.6);
  display: flex;
  flex-direction: column;
  height: 480px;
}

.terminal-header {
  background: #0a0e1a;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  padding: 12px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.window-controls {
  display: flex;
  gap: 6px;
}
.window-controls .dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}
.dot.red { background: #ef4444; }
.dot.yellow { background: #f59e0b; }
.dot.green { background: #10b981; }

.terminal-title {
  font-size: 11px;
  font-family: monospace;
  color: #64748b;
}

.terminal-status {
  font-size: 10px;
  font-weight: 800;
  font-family: monospace;
  color: #64748b;
}
.terminal-status.typing {
  color: #10b981;
}

/* Terminal live telemetry dashboard */
.terminal-dashboard {
  background: #070a14;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
  padding: 14px 20px;
}

.telemetry-bar {
  display: flex;
  gap: 20px;
  margin-bottom: 10px;
}

.t-stat {
  font-family: monospace;
  font-size: 11px;
}
.t-label { color: #475569; margin-right: 6px; }
.t-value { color: #94a3b8; font-weight: bold; }
.t-value.high { color: #f59e0b; }
.t-value.active { color: var(--neon-cyan); }

.npu-progress-bar {
  height: 4px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 10px;
  overflow: hidden;
}
.npu-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--neon-cyan), var(--neon-pink));
  width: 3%;
  border-radius: 10px;
  transition: width 0.4s ease;
}

.terminal-body {
  padding: 20px;
  font-family: monospace;
  font-size: 12.5px;
  line-height: 1.6;
  overflow-y: auto;
  flex-grow: 1;
  color: #cbd5e1;
}

.terminal-line {
  margin-bottom: 8px;
  white-space: pre-wrap;
}
.terminal-line.system { color: #3b82f6; }
.terminal-line.output { color: #a855f7; }
.terminal-line.log { color: #f59e0b; }
.t-prefix { color: #64748b; margin-right: 8px; }

.terminal-output-text {
  color: #10b981;
  white-space: pre-wrap;
  background: rgba(16, 185, 129, 0.02);
  border-left: 2px solid #10b981;
  padding: 10px 14px;
  border-radius: 0 8px 8px 0;
  margin-top: 15px;
}

.cursor-blink {
  animation: blink-cursor 0.8s infinite;
  font-weight: bold;
}

@keyframes blink-cursor {
  0%, 100% { opacity: 0; }
  50% { opacity: 1; }
}

.terminal-line.prompt-hint {
  color: #475569;
  margin-top: 20px;
  font-style: italic;
}

/* AI marketing story banner styling */
.ai-story-banner {
  background: linear-gradient(135deg, rgba(168, 85, 247, 0.04) 0%, rgba(244, 63, 94, 0.04) 100%);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 32px;
  padding: 50px;
}

.ai-story-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 50px;
  align-items: center;
}

.ai-story-text h3 {
  font-size: 30px;
  margin: 16px 0;
  background: linear-gradient(135deg, #ffffff 40%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.ai-story-list {
  list-style: none;
  padding: 0;
  margin: 24px 0 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.ai-story-list li {
  font-size: 14px;
  color: #94a3b8;
  line-height: 1.6;
}
.ai-story-list li b { color: #ffffff; }

.ai-story-visual {
  position: relative;
  border-radius: 24px;
  padding: 6px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.05);
  box-shadow: 0 15px 35px rgba(0,0,0,0.4);
}
.ai-story-img {
  width: 100%;
  height: auto;
  border-radius: 18px;
  display: block;
}

/* =======================================================
   NEW BRAND SECTIONS: INTERACTIVE BATTERY & ENERGY CALCULATOR
   ======================================================= */
.battery-calc-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.battery-calc-grid {
  display: grid;
  grid-template-columns: 0.95fr 1.05fr;
  gap: 60px;
  align-items: center;
}

.bc-text-block h2 {
  font-size: 38px;
  margin: 16px 0 20px;
  background: linear-gradient(135deg, #ffffff 40%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.bc-intro {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 40px;
}

.bc-calculations {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.time-remaining-callout {
  background: rgba(34, 211, 238, 0.03);
  border: 1px solid rgba(34, 211, 238, 0.15);
  border-radius: 24px;
  padding: 30px;
  text-align: center;
}
.calc-label { font-size: 11px; font-weight: 800; color: var(--neon-cyan); letter-spacing: 2px; }
.time-display {
  font-size: 54px;
  font-weight: 800;
  color: #ffffff;
  margin: 10px 0 6px;
  font-family: var(--font-title);
}
.hours-glow {
  color: var(--neon-cyan);
  text-shadow: 0 0 15px rgba(34, 211, 238, 0.5);
}
.calc-sub { font-size: 11px; color: #475569; }

.bc-tip-box {
  display: flex;
  gap: 12px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 16px;
  padding: 18px 24px;
  align-items: center;
}
.tip-icon { font-size: 24px; }
.bc-tip-box p { font-size: 13px; color: #94a3b8; margin: 0; line-height: 1.6; }

.bc-control-block {
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 32px;
  padding: 40px;
  box-shadow: 0 25px 60px rgba(0,0,0,0.4);
}
.bc-control-block h4 { font-size: 16px; margin: 0 0 30px; letter-spacing: 1px; color: #ffffff; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 14px; }

.bc-control-group {
  margin-bottom: 30px;
}

.control-label-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 12px;
  letter-spacing: 0.5px;
}
.control-label-row b { color: var(--neon-cyan); font-size: 14px; }

/* Custom premium slider */
.slider-wrapper {
  padding: 4px 0;
}
.bc-slider {
  -webkit-appearance: none;
  width: 100%;
  height: 6px;
  border-radius: 3px;
  background: rgba(255, 255, 255, 0.05);
  outline: none;
}
.bc-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--neon-cyan);
  box-shadow: 0 0 10px rgba(34, 211, 238, 0.6);
  cursor: pointer;
  transition: transform 0.2s;
}
.bc-slider::-webkit-slider-thumb:hover {
  transform: scale(1.2);
}

.workload-buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.workload-btn {
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 12px;
  padding: 14px;
  color: #94a3b8;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  text-align: left;
}
.workload-btn:hover {
  background: rgba(255, 255, 255, 0.03);
  color: #ffffff;
}
.workload-btn.active {
  background: rgba(34, 211, 238, 0.05);
  border-color: var(--neon-cyan);
  color: var(--neon-cyan);
  box-shadow: 0 0 12px rgba(34, 211, 238, 0.1);
}

.energy-chart-card {
  background: #090c15;
  border: 1px solid rgba(255, 255, 255, 0.03);
  border-radius: 20px;
  padding: 24px;
  margin-top: 30px;
}
.energy-chart-card h5 { font-size: 13px; margin: 0 0 16px; color: #64748b; letter-spacing: 0.5px; }
.energy-chart-card h5 b { color: #ffffff; }

.energy-bar-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.energy-row {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.row-meta {
  display: flex;
  justify-content: space-between;
  font-size: 11.5px;
  color: #94a3b8;
}
.row-meta b { color: #ffffff; }

.eb-track {
  height: 6px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 10px;
  overflow: hidden;
}
.eb-fill {
  height: 100%;
  border-radius: 10px;
  transition: width 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.display-fill { background: linear-gradient(90deg, var(--neon-pink), var(--neon-purple)); }
.cpu-fill { background: linear-gradient(90deg, var(--neon-cyan), var(--neon-blue)); }
.gpu-fill { background: linear-gradient(90deg, #10b981, #059669); }
.system-fill { background: #475569; }

/* =======================================================
   NEW SECTIONS: PREMIUM UNBOXING (.unboxing-sec)
   ======================================================= */
.unboxing-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.unboxing-grid {
  display: grid;
  grid-template-columns: 1.05fr 0.95fr;
  gap: 60px;
  align-items: center;
}

.ub-text-box h2 {
  font-size: 38px;
  margin: 16px 0 20px;
  background: linear-gradient(135deg, #ffffff 40%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.ub-intro {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 24px;
}

.ub-story-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.ub-story-content p {
  font-size: 14.5px;
  color: #94a3b8;
  line-height: 1.65;
  margin: 0;
}

.ub-story-content p strong {
  color: #ffffff;
}

.ub-image-box {
  position: relative;
  border-radius: 28px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
}

.ub-img {
  width: 100%;
  height: auto;
  border-radius: 20px;
  display: block;
  transition: transform 0.5s ease;
}

.ub-image-box:hover .ub-img {
  transform: scale(1.02);
}

.ambient-glow-pink-left {
  position: absolute;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(244, 63, 94, 0.15) 0%, transparent 70%);
  z-index: 0;
  pointer-events: none;
  left: -20%;
  top: 10%;
  filter: blur(40px);
}

.ub-hud-tag {
  position: absolute;
  bottom: 24px;
  right: 24px;
  font-family: monospace;
  font-size: 10px;
  color: var(--neon-pink);
  border: 1px solid rgba(244, 63, 94, 0.3);
  background: rgba(15, 23, 42, 0.85);
  padding: 6px 14px;
  border-radius: 8px;
  letter-spacing: 1px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

/* =======================================================
   NEW SECTIONS: CORE HARDWARE ENGINEERING (.motherboard-sec)
   ======================================================= */
.motherboard-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.motherboard-grid {
  display: grid;
  grid-template-columns: 0.95fr 1.05fr;
  gap: 60px;
  align-items: center;
}

.mb-image-box {
  position: relative;
  border-radius: 28px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
}

.mb-img {
  width: 100%;
  height: auto;
  border-radius: 20px;
  display: block;
  transition: transform 0.5s ease;
}

.mb-image-box:hover .mb-img {
  transform: scale(1.02);
}

.ambient-glow-cyan-right {
  position: absolute;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(34, 211, 238, 0.15) 0%, transparent 70%);
  z-index: 0;
  pointer-events: none;
  right: -20%;
  bottom: 10%;
  filter: blur(40px);
}

.mb-hud-tag {
  position: absolute;
  bottom: 24px;
  left: 24px;
  font-family: monospace;
  font-size: 10px;
  color: var(--neon-cyan);
  border: 1px solid rgba(34, 211, 238, 0.3);
  background: rgba(15, 23, 42, 0.85);
  padding: 6px 14px;
  border-radius: 8px;
  letter-spacing: 1px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.mb-text-box h2 {
  font-size: 38px;
  margin: 16px 0 20px;
  background: linear-gradient(135deg, #ffffff 40%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.mb-intro {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 30px;
}

.mb-technical-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.tech-item h5 {
  font-size: 15px;
  margin: 0 0 6px;
  color: #ffffff;
  font-weight: 700;
}

.tech-item p {
  font-size: 13.5px;
  color: #64748b;
  margin: 0;
  line-height: 1.6;
}

/* =======================================================
   NEW SECTIONS: DISPLAY & DOLBY SPATIAL AUDIO (.display-audio-sec)
   ======================================================= */
.display-audio-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.display-audio-grid {
  display: grid;
  grid-template-columns: 0.95fr 1.05fr;
  gap: 60px;
  align-items: center;
  margin-bottom: 60px;
}

.da-image-block {
  position: relative;
  border-radius: 28px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
}

.da-panel-img {
  width: 100%;
  height: auto;
  border-radius: 20px;
  display: block;
}

.da-mesh-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 50%, rgba(168, 85, 247, 0.15) 0%, transparent 60%);
  pointer-events: none;
}
.da-mesh-glow.pink {
  background: radial-gradient(circle at 50% 50%, rgba(244, 63, 94, 0.15) 0%, transparent 60%);
}

.da-hud-indicator {
  position: absolute;
  bottom: 24px;
  left: 24px;
  font-family: monospace;
  font-size: 10px;
  color: var(--neon-pink);
  border: 1px solid rgba(244, 63, 94, 0.3);
  background: rgba(15, 23, 42, 0.85);
  padding: 6px 14px;
  border-radius: 8px;
  letter-spacing: 1px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.da-text-block h2 {
  font-size: 38px;
  margin: 16px 0 20px;
  background: linear-gradient(135deg, #ffffff 40%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.da-intro {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 30px;
}

.da-sub-features {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.da-feat h5 {
  font-size: 15px;
  margin: 0 0 6px;
  color: #ffffff;
}

.da-feat p {
  font-size: 13.5px;
  color: #64748b;
  margin: 0;
  line-height: 1.6;
}

.sound-mixer-widget {
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 28px;
  padding: 40px;
  box-shadow: 0 25px 55px rgba(0,0,0,0.4);
}

/* =======================================================
   NEW SECTIONS: TRADE-IN & COMPARISON TABLE (.tradein-sec)
   ======================================================= */
.tradein-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.tradein-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  align-items: stretch;
}

.tradein-calculator, .specs-comparison-box {
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 28px;
  padding: 40px;
  box-shadow: 0 25px 55px rgba(0,0,0,0.4);
}

.tradein-calculator h4, .specs-comparison-box h4 {
  font-size: 16px;
  margin: 0 0 8px;
  letter-spacing: 1px;
  color: #ffffff;
}

.ti-desc {
  font-size: 13px;
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 24px;
}

.ti-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-bottom: 30px;
}

.ti-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.ti-label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  letter-spacing: 1px;
}

.ti-brand-selector {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.ti-brand-btn {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  padding: 10px 16px;
  color: #94a3b8;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.ti-brand-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #ffffff;
}

.ti-brand-btn.active {
  background: rgba(34, 211, 238, 0.05);
  border-color: var(--neon-cyan);
  color: var(--neon-cyan);
  box-shadow: 0 0 12px rgba(34, 211, 238, 0.2);
}

.ti-condition-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.ti-cond-btn {
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 12px;
  padding: 12px 16px;
  cursor: pointer;
  transition: all 0.3s;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 4px;
  width: 100%;
}

.ti-cond-btn:hover {
  background: rgba(255, 255, 255, 0.03);
  border-color: rgba(255, 255, 255, 0.1);
}

.ti-cond-btn.active {
  background: rgba(244, 63, 94, 0.04);
  border-color: var(--neon-pink);
}

.cond-name {
  font-size: 13px;
  font-weight: 700;
  color: #ffffff;
}
.ti-cond-btn.active .cond-name {
  color: var(--neon-pink);
}

.cond-desc {
  font-size: 11px;
  color: #64748b;
}

.ti-result-panel {
  background: #070a14;
  border: 1px solid rgba(255, 255, 255, 0.03);
  border-radius: 20px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.ti-result-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  color: #94a3b8;
}

.ti-result-row b {
  color: #ffffff;
  font-size: 15px;
}

.glow-txt-pink {
  color: var(--neon-pink) !important;
  text-shadow: 0 0 10px rgba(244, 63, 94, 0.3);
}

.glow-txt-cyan {
  color: var(--neon-cyan) !important;
  text-shadow: 0 0 10px rgba(34, 211, 238, 0.3);
}

.total-discount {
  border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
  padding-bottom: 14px;
}

.ti-final-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin: 6px 0;
  flex-wrap: wrap;
  gap: 12px;
}

.price-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.price-meta span {
  font-size: 11px;
  color: #64748b;
}

.price-old {
  font-size: 14px;
  text-decoration: line-through;
  color: #475569;
}

.final-payment-box {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.final-payment-box span {
  font-size: 11px;
  color: var(--neon-cyan);
  font-weight: 700;
  letter-spacing: 1px;
}

.payment-val {
  font-size: 24px;
  font-weight: 800;
  color: #ffffff;
  text-shadow: 0 0 15px rgba(34, 211, 238, 0.3);
}

.btn-claim-tradein {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, var(--neon-cyan) 0%, var(--neon-blue) 100%);
  color: #ffffff;
  font-weight: 700;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(34, 211, 238, 0.25);
  transition: all 0.3s;
  text-align: center;
}

.btn-claim-tradein:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(34, 211, 238, 0.4);
}

.table-responsive-wrapper {
  overflow-x: auto;
  margin-top: 10px;
}

.specs-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.specs-table th {
  padding: 14px;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.specs-table td {
  padding: 14px;
  font-size: 13px;
  color: #cbd5e1;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.scholar-header { color: #f59e0b; }
.pro-header { color: var(--neon-cyan); }
.beast-header { color: var(--neon-pink); }

.pro-header.active, .row-pro.active {
  background: rgba(34, 211, 238, 0.03);
  border-left: 1px solid rgba(34, 211, 238, 0.1);
  border-right: 1px solid rgba(34, 211, 238, 0.1);
}

.specs-table tr:hover td {
  background: rgba(255, 255, 255, 0.01);
}

.row-param {
  font-weight: 700;
  color: #ffffff;
}

/* =======================================================
   NEW SECTIONS: ECOSYSTEM SYSTEM (.ecosystem-sec)
   ======================================================= */
.ecosystem-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.ecosystem-grid {
  display: grid;
  grid-template-columns: 0.95fr 1.05fr;
  gap: 60px;
  align-items: center;
}

.eco-visual {
  position: relative;
  border-radius: 28px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
}

.eco-img {
  width: 100%;
  height: auto;
  border-radius: 20px;
  display: block;
}

.ambient-glow-blue-right {
  position: absolute;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
  z-index: 0;
  pointer-events: none;
  right: -20%;
  top: 10%;
  filter: blur(40px);
}

.eco-text h2 {
  font-size: 38px;
  margin: 16px 0 20px;
  background: linear-gradient(135deg, #ffffff 40%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.eco-intro {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 30px;
}

.eco-items-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.eco-card-item h5 {
  font-size: 15px;
  margin: 0 0 6px;
  color: #ffffff;
}

.eco-card-item p {
  font-size: 13.5px;
  color: #64748b;
  margin: 0;
  line-height: 1.6;
}

/* =======================================================
   NEW SECTIONS: LIFESTYLE & EXECUTIVE USE (.lifestyle-sec)
   ======================================================= */
.lifestyle-sec {
  padding: 100px 0;
  position: relative;
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

.lifestyle-grid {
  display: grid;
  grid-template-columns: 1.05fr 0.95fr;
  gap: 60px;
  align-items: center;
}

.ls-text-box h2 {
  font-size: 38px;
  margin: 16px 0 20px;
  background: linear-gradient(135deg, #ffffff 40%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.ls-intro {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 30px;
}

.ls-lifestyle-blocks {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.ls-block h5 {
  font-size: 15px;
  margin: 0 0 6px;
  color: #ffffff;
  font-weight: 700;
}

.ls-block p {
  font-size: 13.5px;
  color: #64748b;
  margin: 0;
  line-height: 1.65;
}

.ls-image-box {
  position: relative;
  border-radius: 28px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
}

.ls-img {
  width: 100%;
  height: auto;
  border-radius: 20px;
  display: block;
}

.ambient-glow-orange-right {
  position: absolute;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(249, 115, 22, 0.15) 0%, transparent 70%);
  z-index: 0;
  pointer-events: none;
  right: -20%;
  bottom: 10%;
  filter: blur(40px);
}

.ls-hud-tag {
  position: absolute;
  bottom: 24px;
  right: 24px;
  font-family: monospace;
  font-size: 10px;
  color: var(--neon-pink);
  border: 1px solid rgba(244, 63, 94, 0.3);
  background: rgba(15, 23, 42, 0.85);
  padding: 6px 14px;
  border-radius: 8px;
  letter-spacing: 1px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

/* =======================================================
   RESPONSIVE DESIGN SYSTEM
   ======================================================= */
@media (max-width: 1024px) {
  .hero-text-block h1 { font-size: 44px; }
  .philosophy-grid { grid-template-columns: 1fr; gap: 40px; }
  .thermals-grid { grid-template-columns: 1fr; gap: 40px; }
  .unboxing-grid { grid-template-columns: 1fr; gap: 40px; }
  .motherboard-grid { grid-template-columns: 1fr; gap: 40px; }
  .display-audio-grid { grid-template-columns: 1fr; gap: 40px; }
  .tradein-grid { grid-template-columns: 1fr; gap: 40px; }
  .ecosystem-grid { grid-template-columns: 1fr; gap: 40px; }
  .lifestyle-grid { grid-template-columns: 1fr; gap: 40px; }
  .sound-mixer-widget .mixer-body { grid-template-columns: 1fr; gap: 30px; }
  .ai-display-grid { grid-template-columns: 1fr; gap: 45px; }
  
  .visualizer-grid { grid-template-columns: 1fr; }
  .visualizer-tabs { flex-direction: row; flex-wrap: wrap; justify-content: space-between; }
  .tab-btn { flex: 1 1 45%; }
  
  .matrix-grid { grid-template-columns: 1fr; gap: 30px; }
  .deal-grid { grid-template-columns: 1fr; gap: 40px; }
  .testimonials-grid { grid-template-columns: 1fr; gap: 30px; }
  .vvip-grid { grid-template-columns: 1fr; gap: 20px; }
}

@media (max-width: 768px) {
  .hero-grid { grid-template-columns: 1fr; gap: 50px; text-align: center; }
  .hero-ctas { justify-content: center; }
  .hero-stats { justify-content: center; }
  .quiz-options.cols-3 { grid-template-columns: 1fr; }
  .recommended-grid { grid-template-columns: 1fr; }
  .sec-header h2 { font-size: 30px; }
  .visualizer-display { padding: 28px; }
  .spec-metric-grid { grid-template-columns: 1fr; }
  .deal-box { padding: 30px; }
  .countdown-timer { gap: 6px; }
  .time-block { width: 60px; }
  
  .unboxing-sec { padding: 60px 0; }
  .motherboard-sec { padding: 60px 0; }
  .display-audio-sec { padding: 60px 0; }
  .tradein-sec { padding: 60px 0; }
  .ecosystem-sec { padding: 60px 0; }
  .lifestyle-sec { padding: 60px 0; }
  .tradein-calculator, .specs-comparison-box { padding: 24px; }
  .sound-mixer-widget { padding: 24px; }
  .mixer-info { padding: 20px; }
  .equalizer-visualizer { padding: 20px; }
  .specs-table th, .specs-table td { padding: 8px; font-size: 11px; }
}

@media (max-width: 480px) {
  .hero-text-block h1 { font-size: 34px; }
  .hero-sec { padding-top: 80px; }
  .tab-btn { flex: 1 1 100%; }
}
</style>
