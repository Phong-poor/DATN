<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { getToken } from '@/services/auth'

// Form State
const name = ref('')
const phone = ref('')
const email = ref('')
const subject = ref('Tư vấn mua hàng')
const message = ref('')
const error = ref('')
const success = ref(false)
const loading = ref(false)
const captchaVisible = ref(false)
const captchaVerified = ref(false)

const currentFormStep = ref(1)

const resetCaptcha = () => {
  captchaVisible.value = false
  captchaVerified.value = false
}

const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms))

const subjects = [
  'Tư vấn mua hàng',
  'Hỗ trợ kỹ thuật',
  'Bảo hành & sửa chữa',
  'Hợp tác kinh doanh',
  'Khác',
]

const step1Categories = [
  { label: 'Tư vấn mua hàng', icon: `<svg class="cyber-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="12" rx="2" ry="2"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="8" y1="22" x2="16" y2="22"/><line x1="2" y1="16" x2="22" y2="16"/></svg>`, desc: 'Chọn cấu hình Laptop, Workstation, AI PC phù hợp.' },
  { label: 'Hỗ trợ kỹ thuật', icon: `<svg class="cyber-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>`, desc: 'Xử lý lỗi phần cứng, cài đặt driver, phần mềm.' },
  { label: 'Bảo hành & sửa chữa', icon: `<svg class="cyber-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`, desc: 'Tra cứu chính sách bảo hành, sửa chữa dịch vụ.' },
  { label: 'Hợp tác kinh doanh', icon: `<svg class="cyber-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`, desc: 'Cung cấp giải pháp doanh nghiệp, đại lý bán lẻ.' },
  { label: 'Khác', icon: `<svg class="cyber-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0 -4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>`, desc: 'Các câu hỏi hoặc yêu cầu hỗ trợ đặc thù khác.' }
]

// Suggestion tags for Step 3 based on subject
const suggestionTags = computed(() => {
  if (subject.value === 'Tư vấn mua hàng') {
    return ['Tư vấn laptop chạy Blender/3D', 'Báo giá PC chạy LLM Local', 'Laptop gaming dưới 30 triệu', 'Tư vấn trả góp 0%']
  }
  if (subject.value === 'Hỗ trợ kỹ thuật') {
    return ['Cách cài lại Windows local', 'Máy không nhận GPU rời', 'Nhiệt độ CPU quá cao', 'Lỗi màn hình xanh BSOD']
  }
  if (subject.value === 'Bảo hành & sửa chữa') {
    return ['Tra cứu thời hạn bảo hành', 'Chi phí thay pin chính hãng', 'Quy trình đổi trả 1-1', 'Nâng cấp RAM tại showroom']
  }
  if (subject.value === 'Hợp tác kinh doanh') {
    return ['Báo giá số lượng lớn doanh nghiệp', 'Chính sách đại lý NextGen', 'Yêu cầu làm đối tác cung ứng']
  }
  return ['Yêu cầu hỗ trợ khẩn cấp', 'Góp ý chất lượng dịch vụ']
})

const applySuggestion = (tag) => {
  message.value = tag
  resetCaptcha()
}

// Validation before step change
const nextStep = () => {
  if (currentFormStep.value === 1) {
    currentFormStep.value = 2
  } else if (currentFormStep.value === 2) {
    if (!name.value || !phone.value || !email.value) {
      error.value = 'Vui lòng nhập đầy đủ thông tin liên hệ bắt buộc.'
      setTimeout(() => { error.value = '' }, 4000)
      return
    }
    // simple email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(email.value)) {
      error.value = 'Địa chỉ Email không hợp lệ.'
      setTimeout(() => { error.value = '' }, 4000)
      return
    }
    error.value = ''
    resetCaptcha()
    currentFormStep.value = 3
  }
}

const prevStep = () => {
  if (currentFormStep.value > 1) {
    resetCaptcha()
    currentFormStep.value--
  }
}

const selectCategory = (categoryLabel) => {
  subject.value = categoryLabel
  resetCaptcha()
  // Auto transition to step 2 after a brief delay
  setTimeout(() => {
    currentFormStep.value = 2
  }, 250)
}

onMounted(async () => {
  if (!getToken()) return

  try {
    const data = (await api.get('/user/profile')).data
    name.value = data.name
    email.value = data.email
    phone.value = data.phone
  } catch (err) {
    console.log('Chưa đăng nhập')
  }
})

async function submitForm() {
  if (loading.value) return
  if (!name.value || !phone.value || !email.value || !message.value) {
    error.value = 'Vui lòng nhập đầy đủ nội dung mô tả yêu cầu.'
    success.value = false
    return
  }
  error.value = ''
  success.value = false
  loading.value = true
  await wait(3000)
  loading.value = false
  captchaVisible.value = true
  captchaVerified.value = false
}

async function handleCaptchaChange() {
  if (!captchaVerified.value || loading.value) return
  await sendContactRequest()
}

async function sendContactRequest() {
  try {
    error.value = ''
    success.value = false
    loading.value = true
    const data = (await api.post('/lien-he', {
      name: name.value,
      email: email.value,
      phone: phone.value,
      message: `[${subject.value}] ${message.value}`,
    })).data
    if (data.status) {
      success.value = true
      name.value = ''
      phone.value = ''
      email.value = ''
      message.value = ''
      subject.value = 'Tư vấn mua hàng'
      currentFormStep.value = 1
      resetCaptcha()
      setTimeout(() => { success.value = false }, 6000)
    } else {
      error.value = data.message || 'Gửi yêu cầu thất bại, vui lòng thử lại'
    }
  } catch (err) {
    error.value = 'Lỗi kết nối máy chủ. Vui lòng thử lại sau.'
  } finally {
    loading.value = false
  }
}

// Showrooms State
const showrooms = ref([
  {
    id: 1,
    name: 'Trụ sở chính (Nhà Bè)',
    address: 'Số 18/7 Huỳnh Tấn Phát, Thị trấn Nhà Bè, Huyện Nhà Bè, TP. Hồ Chí Minh',
    query: 'Công Ty Cổ Phần Công Nghệ Đại Dương Huỳnh Tấn Phát',
    mapUrl: 'https://www.google.com/maps?q=Công+Ty+Cổ+Phần+Công+Nghệ+Đại+Dương+Huỳnh+Tấn+Phát',
    phone: '1900 8888 (Phím 1)',
    worktime: 'T2 – T6: 8:00 – 18:00 | T7: 8:00 – 12:00'
  },
  {
    id: 2,
    name: 'Chi nhánh Quận 1 (TP. HCM)',
    address: 'Số 135 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh',
    query: '135 Nguyễn Huệ, Bến Nghé, Quận 1, Thành phố Hồ Chí Minh',
    mapUrl: 'https://www.google.com/maps?q=135+Nguyễn+Huệ,+Bến+Nghé,+Quận+1,+TP+Hồ+Chí+Minh',
    phone: '1900 8888 (Phím 2)',
    worktime: 'T2 – T6: 8:00 – 20:00 | T7-CN: 8:00 – 18:00'
  },
  {
    id: 3,
    name: 'Chi nhánh Cầu Giấy (Hà Nội)',
    address: 'Số 26 Trần Thái Tông, Dịch Vọng Hậu, Cầu Giấy, Hà Nội',
    query: '26 Trần Thái Tông, Dịch Vọng Hậu, Cầu Giấy, Hà Nội',
    mapUrl: 'https://www.google.com/maps?q=26+Trần+Thái+Tông,+Dịch+Vọng+Hậu,+Cầu+Giấy,+Hà+Nội',
    phone: '1900 8888 (Phím 3)',
    worktime: 'T2 – T6: 8:00 – 19:00 | T7: 8:00 – 17:00'
  },
])

const selectedShowroom = ref(showrooms.value[0])

const infos = computed(() => [
  { icon: `<svg class="cyber-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>`, label: 'Địa chỉ', value: selectedShowroom.value.address, color: 'rgba(37, 99, 235, 0.15)' },
  { icon: `<svg class="cyber-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.08 6.08l.95-.95a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>`, label: 'Hotline', value: selectedShowroom.value.phone, bold: true, color: 'rgba(37, 99, 235, 0.15)' },
  { icon: `<svg class="cyber-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>`, label: 'Email', value: 'support@vinatech.vn', color: 'rgba(139, 92, 246, 0.15)' },
  { icon: `<svg class="cyber-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`, label: 'Giờ mở cửa', value: selectedShowroom.value.worktime, color: 'rgba(245, 158, 11, 0.15)' },
])

const bookShowroomVisit = (store) => {
  subject.value = 'Hợp tác kinh doanh'
  message.value = `Tôi muốn đăng ký lịch hẹn tư vấn và trải nghiệm phần cứng trực tiếp tại Showroom: ${store.name}.`
  resetCaptcha()
  currentFormStep.value = 3 // Jump straight to descriptions
  const element = document.getElementById('guidedContactForm')
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

// Experts Data
const experts = [
  {
    name: 'Hoàng Long',
    role: 'Gaming Specialist',
    experience: '5 năm kinh nghiệm',
    specialty: 'Cấu hình tối ưu chơi game AAA, eSports & Laptop Gaming cao cấp.',
    avatar: 'HL'
  },
  {
    name: 'Minh Thư',
    role: 'Workstation Consultant',
    experience: '6 năm kinh nghiệm',
    specialty: 'Hệ thống Render 3D, Blender, dựng phim & RTX Enterprise.',
    avatar: 'MT'
  },
  {
    name: 'Tuấn Kiệt',
    role: 'AI PC & Neural compute Expert',
    experience: '4 năm kinh nghiệm',
    specialty: 'Setup local LLM, Stable Diffusion, tăng tốc NPU Intel/AMD.',
    avatar: 'TK'
  },
  {
    name: 'Khánh An',
    role: 'Enterprise Solutions Director',
    experience: '8 năm kinh nghiệm',
    specialty: 'Máy chủ lưu trữ NAS, ảo hóa Server & mạng lưới doanh nghiệp.',
    avatar: 'KA'
  }
]

// FAQ Data
const faqs = ref([
  {
    q: 'Tôi có thể đến trực tiếp showroom để xem hàng không?',
    a: 'Hoàn toàn có thể! Hệ thống showroom VinaTech mở cửa phục vụ cả ngày. Bạn có thể đến bất kỳ chi nhánh nào gần nhất để trải nghiệm thực tế các dòng laptop cấu hình cao cùng sự hỗ trợ chuyên sâu của đội ngũ kỹ sư.',
    open: false,
    category: 'buying'
  },
  {
    q: 'VinaTech có hỗ trợ giao hàng toàn quốc không?',
    a: 'Có! Chúng tôi giao hàng toàn quốc 63 tỉnh thành. Các đơn hàng nội thành Hồ Chí Minh và Hà Nội được đóng gói niêm phong cực kỳ cẩn thận và bàn giao siêu tốc chỉ từ 2–4 giờ.',
    open: false,
    category: 'buying'
  },
  {
    q: 'Chính sách bảo hành của VinaTech như thế nào?',
    a: 'Tất cả sản phẩm tại VinaTech đều được bảo hành chính hãng từ 12–24 tháng. Chúng tôi còn cung cấp chính sách NextGen Care+ bảo hành thêm 12 tháng phần cứng cho các thiết bị mua mới.',
    open: false,
    category: 'warranty'
  },
  {
    q: 'Tôi muốn trả góp 0% lãi suất, cần điều kiện gì?',
    a: 'VinaTech hỗ trợ trả góp 0% qua thẻ tín dụng của hơn 25 ngân hàng liên kết toàn quốc. Bạn chỉ cần thẻ tín dụng chính chủ còn hạn mức thanh toán. Thủ tục duyệt trực tuyến cực nhanh chỉ trong 10 phút.',
    open: false,
    category: 'buying'
  },
  {
    q: 'Thời gian phản hồi sau khi gửi form liên hệ là bao lâu?',
    a: 'Đội ngũ chuyên viên NextGen cam kết phản hồi tất cả các yêu cầu tư vấn bằng văn bản hoặc liên hệ trực tiếp trong vòng tối đa 2 giờ làm việc kể từ lúc nhận được guided form.',
    open: false,
    category: 'technical'
  },
  {
    q: 'VinaTech có nhận đổi trả hàng không?',
    a: 'Đổi mới 1-1 miễn phí trong vòng 30 ngày nếu sản phẩm xuất hiện lỗi phần cứng từ nhà sản xuất. Hỗ trợ thu cũ đổi mới lên đời laptop cấu hình cao hơn cực ưu đãi cho khách hàng thân thiết.',
    open: false,
    category: 'warranty'
  },
])

const faqSearchQuery = ref('')
const selectedFaqCategory = ref('all')

const filteredFaqs = computed(() => {
  return faqs.value.filter(faq => {
    const matchesCategory = selectedFaqCategory.value === 'all' || faq.category === selectedFaqCategory.value
    const matchesSearch = faq.q.toLowerCase().includes(faqSearchQuery.value.toLowerCase()) || 
                          faq.a.toLowerCase().includes(faqSearchQuery.value.toLowerCase())
    return matchesCategory && matchesSearch
  })
})

const toggleFaq = (index) => {
  // Toggle the clicked FAQ, close others
  faqs.value.forEach((item, idx) => {
    if (idx === index) {
      item.open = !item.open
    } else {
      item.open = false
    }
  })
}
</script>

<template>
  <div class="contact-page">
    


    <!-- ===== CYBER GRADIENT HERO ===== -->
    <section class="support-hero">
      <div class="glow-sphere-1"></div>
      <div class="glow-sphere-2"></div>
      
      <div class="hero-container">
        <span class="support-badge">
          <svg class="cyber-svg-icon badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
          NEXTGEN GLOBAL SUPPORT
        </span>
        <h1>Trò chuyện cùng <br /><span class="highlight-text">Chuyên Gia Công Nghệ.</span></h1>
        <p class="hero-desc">Bàn giải pháp phần cứng tối tân, tối ưu hóa AI PC local, Workstation dựng phim chuyên nghiệp và giải pháp hệ thống doanh nghiệp.</p>
        
        <div class="hero-actions">
          <a href="#guidedContactForm" class="btn-glow-primary">Đặt Lịch Tư Vấn</a>
          <a href="tel:19008888" class="btn-glass">Hotline 1900 8888</a>
        </div>

        <!-- Live statistic counters -->
        <div class="stats-grid">
          <div class="stat-card">
            <span class="stat-number">5.000+</span>
            <span class="stat-label">Khách Hàng Đã Hỗ Trợ</span>
          </div>
          <div class="stat-border-line"></div>
          <div class="stat-card">
            <span class="stat-number">98%</span>
            <span class="stat-label">Tỷ Lệ Hài Lòng Tuyệt Đối</span>
          </div>
          <div class="stat-border-line"></div>
          <div class="stat-card">
            <span class="stat-number">2h</span>
            <span class="stat-label">Cam Kết Phản Hồi Tối Đa</span>
          </div>
          <div class="stat-border-line"></div>
          <div class="stat-card">
            <span class="stat-number">3</span>
            <span class="stat-label">Showroom Trải Nghiệm Lớn</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== CONVERSATIONAL SUPPORT FORM CENTER ===== -->
    <section class="form-section-container" id="guidedContactForm">
      <div class="form-wrapper-max">
        
        <div class="section-magazine-title">
          <span class="sub-label">GUIDED SUPPORT CENTER</span>
          <h2>Trung Tâm Khởi Tạo <span>Yêu Cầu Hỗ Trợ.</span></h2>
          <p>Điền thông tin hội thoại từng bước dưới đây, kỹ sư NextGen sẽ liên hệ giải đáp nhanh chóng.</p>
        </div>

        <div class="guided-grid-layout">
          
          <!-- LEFT SIDE: GUIDED SMART FORM -->
          <div class="guided-form-glass-card">
            <div class="step-progress-row">
              <div class="step-node" :class="{ active: currentFormStep >= 1, done: currentFormStep > 1 }">
                <span class="step-num">1</span>
                <span class="step-label">Nhu Cầu</span>
              </div>
              <div class="step-line" :class="{ fill: currentFormStep > 1 }"></div>
              <div class="step-node" :class="{ active: currentFormStep >= 2, done: currentFormStep > 2 }">
                <span class="step-num">2</span>
                <span class="step-label">Thông Tin</span>
              </div>
              <div class="step-line" :class="{ fill: currentFormStep > 2 }"></div>
              <div class="step-node" :class="{ active: currentFormStep >= 3, done: currentFormStep > 3 }">
                <span class="step-num">3</span>
                <span class="step-label">Chi Tiết</span>
              </div>
            </div>

            <!-- STEP 1: FOCUS / TOPIC CHOICE -->
            <div v-if="currentFormStep === 1" class="step-view-content fade-in">
              <h3 class="step-view-title">Bước 1: Nhu cầu của bạn là gì?</h3>
              <p class="step-view-subtitle">Hãy chọn một chuyên mục hỗ trợ chính để chúng tôi điều phối đúng chuyên gia.</p>
              
              <div class="guided-categories-grid">
                <div 
                  v-for="cat in step1Categories" 
                  :key="cat.label" 
                  class="guided-category-card"
                  :class="{ active: subject === cat.label }"
                  @click="selectCategory(cat.label)"
                >
                  <div class="cat-icon-box" v-html="cat.icon"></div>
                  <div class="cat-details">
                    <h4>{{ cat.label }}</h4>
                    <p>{{ cat.desc }}</p>
                  </div>
                  <div class="cat-check-dot"></div>
                </div>
              </div>

              <div class="step-actions-footer">
                <span></span>
                <button type="button" class="btn-step-next" @click="nextStep">
                  Bước Tiếp Theo ➜
                </button>
              </div>
            </div>

            <!-- STEP 2: CUSTOMER INFO -->
            <div v-if="currentFormStep === 2" class="step-view-content fade-in">
              <h3 class="step-view-title">Bước 2: Thông tin liên hệ của bạn</h3>
              <p class="step-view-subtitle">Nhập chính xác để chuyên gia NextGen có thể kết nối ngay lập tức.</p>
              
              <div class="guided-inputs-wrapper">
                <div class="floating-input-field">
                  <span class="field-icon-neon">
                    <svg class="cyber-svg-icon input-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  </span>
                  <div class="input-block">
                    <input 
                      id="step2-name"
                      v-model="name"
                      type="text" 
                      placeholder=" "
                      required
                    />
                    <label for="step2-name">Họ và tên của bạn *</label>
                  </div>
                </div>

                <div class="floating-input-field">
                  <span class="field-icon-neon">
                    <svg class="cyber-svg-icon input-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                  </span>
                  <div class="input-block">
                    <input 
                      id="step2-phone"
                      v-model="phone"
                      type="tel" 
                      placeholder=" "
                      required
                    />
                    <label for="step2-phone">Số điện thoại liên lạc *</label>
                  </div>
                </div>

                <div class="floating-input-field">
                  <span class="field-icon-neon">
                    <svg class="cyber-svg-icon input-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  </span>
                  <div class="input-block">
                    <input 
                      id="step2-email"
                      v-model="email"
                      type="email" 
                      placeholder=" "
                      required
                    />
                    <label for="step2-email">Địa chỉ Email xác thực *</label>
                  </div>
                </div>
              </div>

              <!-- Inline Step Alert -->
              <div v-if="error" class="step-validation-error">
                <svg class="cyber-svg-icon error-inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> {{ error }}
              </div>

              <div class="step-actions-footer">
                <button type="button" class="btn-step-prev" @click="prevStep">
                  ⬅ Quay Lại
                </button>
                <button type="button" class="btn-step-next" @click="nextStep">
                  Tiếp Theo ➜
                </button>
              </div>
            </div>

            <!-- STEP 3: MESSAGE & SUBMIT -->
            <div v-if="currentFormStep === 3" class="step-view-content fade-in">
              <h3 class="step-view-title">Bước 3: Mô tả chi tiết mong muốn</h3>
              <p class="step-view-subtitle">Chuyên mục đang chọn: <strong class="text-secondary-cyan">{{ subject }}</strong></p>
              
              <!-- Quick Suggestions tags -->
              <div class="suggestion-tags-row">
                <span class="suggest-label">
                  <svg class="cyber-svg-icon suggest-lbl-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6m-6 3h6m-7.5-6a6 6 0 1 1 9 0c0 2-1.5 3-2.5 4h-4c-1-1-2.5-2-2.5-4z"/></svg>
                  Gợi ý nhanh:
                </span>
                <button 
                  v-for="tag in suggestionTags" 
                  :key="tag"
                  type="button"
                  class="suggest-tag-btn"
                  @click="applySuggestion(tag)"
                >
                  {{ tag }}
                </button>
              </div>

              <div class="guided-textarea-field">
                <textarea 
                  v-model="message"
                  @input="resetCaptcha"
                  placeholder="Mô tả cấu hình bạn cần, lỗi thiết bị bạn gặp phải hoặc nhu cầu hợp tác cụ thể..."
                  required
                ></textarea>
              </div>

              <div v-if="captchaVisible" class="human-captcha-box" :class="{ verified: captchaVerified }">
                <label class="human-captcha-check">
                  <input v-model="captchaVerified" type="checkbox" @change="handleCaptchaChange" />
                  <span class="captcha-custom-check">
                    <svg v-if="captchaVerified" class="cyber-svg-icon captcha-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                  </span>
                  <span class="captcha-copy">
                    <strong>Xác minh bạn là con người</strong>
                    <small>Tick vào ô này rồi bấm gửi yêu cầu để tiếp tục.</small>
                  </span>
                </label>
                <div class="captcha-brand">
                  <svg class="captcha-cloud-icon" viewBox="0 0 64 44" aria-hidden="true">
                    <path fill="#f38020" d="M47.8 18.6C46 9.9 38.2 3.4 29 3.4c-8.1 0-15.1 5-17.9 12.1C4.9 16.4.2 21.6.2 28c0 7 5.7 12.6 12.7 12.6h34.3c6.6 0 12-5.4 12-12 0-5.5-3.7-10.2-8.8-11.6-.8-.2-1.7.7-2.6 1.6Z"/>
                    <path fill="#faae40" d="M31.2 40.6h20.2c6.8 0 12.3-5.5 12.3-12.3 0-5.9-4.2-10.8-9.7-12l-22.8 24.3Z" opacity=".9"/>
                  </svg>
                  <strong>NEXTGEN VERIFY</strong>
                  <span>Quyền riêng tư · Giúp đỡ</span>
                </div>
              </div>

              <!-- Error & Success states -->
              <div v-if="error" class="form-feedback-alert error">
                <span>
                  <svg class="cyber-svg-icon alert-err-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Lỗi:
                </span> {{ error }}
              </div>
              <div v-if="success" class="form-feedback-alert success">
                <span>
                  <svg class="cyber-svg-icon alert-ok-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Thành công:
                </span> Gửi tin nhắn thành công! Đội ngũ tư vấn sẽ gọi cho bạn trong tối đa 2 giờ.
              </div>

              <div v-if="!captchaVisible" class="step-actions-footer">
                <button type="button" class="btn-step-prev" @click="prevStep">
                  ⬅ Quay Lại
                </button>
                <button 
                  type="button" 
                  class="btn-step-submit" 
                  :class="{ loading: loading }"
                  :disabled="loading"
                  @click="submitForm"
                >
                  <span v-if="loading" class="spin-loader"></span>
                  <span v-else>Xác Nhận Gửi Yêu Cầu ➜</span>
                </button>
              </div>

              <p class="cyber-privacy-note">
                <svg class="cyber-svg-icon privacy-note-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Dữ liệu cá nhân được mã hóa và bảo vệ nghiêm ngặt theo tiêu chuẩn ISO 27001.
              </p>
            </div>

          </div>

          <!-- RIGHT SIDE: SHOWROOM PANEL -->
          <div class="showroom-guided-panel">
            <div class="showroom-brand-header">
              <span class="pill-badge">
                <svg class="cyber-svg-icon pill-badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                HỆ THỐNG TRẢI NGHIỆM
              </span>
              <h3>Bản Đồ Showroom NextGen</h3>
              <p>Chọn địa điểm showroom để cập nhật thông tin liên hệ và đặt lịch trực tiếp.</p>
            </div>

            <!-- Showroom Buttons Selector -->
            <div class="showroom-cyber-list">
              <div 
                v-for="store in showrooms" 
                :key="store.id" 
                class="showroom-cyber-btn"
                :class="{ active: selectedShowroom.id === store.id }"
                @click="selectedShowroom = store"
              >
                <span class="active-neon-dot"></span>
                <div class="store-btn-info">
                  <h5>{{ store.name }}</h5>
                  <span>{{ store.address.split(',')[0] }}</span>
                </div>
              </div>
            </div>

            <!-- Dynamic Showroom Info Card -->
            <div class="showroom-detail-glass-card">
              <div class="detail-row" v-for="info in infos" :key="info.label">
                <div class="detail-icon" :style="{ backgroundColor: info.color }" v-html="info.icon"></div>
                <div class="detail-text">
                  <span class="detail-label">{{ info.label }}</span>
                  <p class="detail-val" :class="{ bold: info.bold }">{{ info.value }}</p>
                </div>
              </div>
              
              <button 
                type="button" 
                class="btn-visit-schedule" 
                @click="bookShowroomVisit(selectedShowroom)"
              >
                <svg class="cyber-svg-icon btn-sched-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Đặt Lịch Hẹn Trải Nghiệm Showroom
              </button>
            </div>

            <!-- Secondary Embedded Maps Viewport -->
            <div class="showroom-embedded-map">
              <iframe 
                :src="'https://www.google.com/maps?q=' + encodeURIComponent(selectedShowroom.query) + '&output=embed'"
                loading="lazy"
                title="Bản đồ showroom"
              ></iframe>
              <div class="map-overlay-footer">
                <a :href="selectedShowroom.mapUrl" target="_blank" class="cyber-map-link">
                  🧭 Mở trong Google Maps chính thức ➜
                </a>
              </div>
            </div>

          </div>

        </div>

      </div>
    </section>

    <!-- ===== HUMANIZING SECTION: MEET THE EXPERTS ===== -->
    <section class="experts-section-wrapper">
      <div class="experts-inner-container">
        
        <div class="section-magazine-title text-center">
          <span class="sub-label">HUMANIZING SUPPORT</span>
          <h2>Đồng Hành Cùng <span>Các Chuyên Gia.</span></h2>
          <p>NextGen mang đến dịch vụ tư vấn trực tiếp bởi những kỹ sư, chuyên gia phần cứng hàng đầu.</p>
        </div>

        <div class="experts-grid">
          <div 
            v-for="expert in experts" 
            :key="expert.name" 
            class="expert-avatar-card"
          >
            <div class="expert-card-top">
              <div class="expert-avatar-circle">{{ expert.avatar }}</div>
              <div class="expert-identity">
                <h4>{{ expert.name }}</h4>
                <span class="expert-badge-role">{{ expert.role }}</span>
              </div>
            </div>
            <div class="expert-card-body">
              <div class="exp-badge">{{ expert.experience }}</div>
              <p class="exp-description">"{{ expert.specialty }}"</p>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ===== TECHNOLOGY PARTNERS TRUST BAR ===== -->
    <section class="tech-trust-banner">
      <div class="tech-trust-inner">
        <h4>Hệ Sinh Thái Đối Tác Phần Cứng Cao Cấp Của NextGen</h4>
        <div class="partners-marquee-container">
          <div class="partners-marquee-track">
            <span>Intel</span>
            <span class="ticker-dot">•</span>
            <span>AMD</span>
            <span class="ticker-dot">•</span>
            <span>NVIDIA</span>
            <span class="ticker-dot">•</span>
            <span>ASUS ROG</span>
            <span class="ticker-dot">•</span>
            <span>MSI</span>
            <span class="ticker-dot">•</span>
            <span>Dell Enterprise</span>
            <span class="ticker-dot">•</span>
            <span>Lenovo Pro</span>
            <span class="ticker-dot">•</span>
            <span>HP ZBook</span>
            
            <!-- Loop Duplicate -->
            <span class="ticker-dot">•</span>
            <span>Intel</span>
            <span class="ticker-dot">•</span>
            <span>AMD</span>
            <span class="ticker-dot">•</span>
            <span>NVIDIA</span>
            <span class="ticker-dot">•</span>
            <span>ASUS ROG</span>
            <span class="ticker-dot">•</span>
            <span>MSI</span>
            <span class="ticker-dot">•</span>
            <span>Dell Enterprise</span>
            <span class="ticker-dot">•</span>
            <span>Lenovo Pro</span>
            <span class="ticker-dot">•</span>
            <span>HP ZBook</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FAQ KNOWLEDGE CENTER ===== -->
    <section class="faq-knowledge-center">
      <div class="faq-inner-max">
        
        <div class="section-magazine-title text-center">
          <span class="sub-label">KNOWLEDGE BASE</span>
          <h2>Giải Đáp <span>Thắc Mắc.</span></h2>
          <p>Hệ thống tri thức được phân loại trực quan giúp bạn giải quyết nhanh các câu hỏi thường gặp.</p>
        </div>

        <!-- FAQ Categories and Search bar -->
        <div class="faq-controls-row">
          <div class="faq-category-tabs">
            <button 
              type="button" 
              class="faq-tab-btn" 
              :class="{ active: selectedFaqCategory === 'all' }"
              @click="selectedFaqCategory = 'all'"
            >
              Tất Cả
            </button>
            <button 
              type="button" 
              class="faq-tab-btn" 
              :class="{ active: selectedFaqCategory === 'buying' }"
              @click="selectedFaqCategory = 'buying'"
            >
              <svg class="cyber-svg-icon tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg> Mua Hàng & Trả Góp
            </button>
            <button 
              type="button" 
              class="faq-tab-btn" 
              :class="{ active: selectedFaqCategory === 'warranty' }"
              @click="selectedFaqCategory = 'warranty'"
            >
              <svg class="cyber-svg-icon tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Bảo Hành & Đổi Trả
            </button>
            <button 
              type="button" 
              class="faq-tab-btn" 
              :class="{ active: selectedFaqCategory === 'technical' }"
              @click="selectedFaqCategory = 'technical'"
            >
              <svg class="cyber-svg-icon tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg> Hỗ Trợ Kỹ Thuật
            </button>
          </div>

          <div class="faq-search-box">
            <span class="search-icon">
              <svg class="cyber-svg-icon faq-srch-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input 
              v-model="faqSearchQuery"
              type="text" 
              placeholder="Tìm câu hỏi của bạn..." 
              aria-label="Tìm kiếm câu hỏi"
            />
          </div>
        </div>

        <!-- FAQ Accordions List -->
        <div class="faq-accordions-grid">
          <div 
            v-for="(faq, idx) in filteredFaqs" 
            :key="idx" 
            class="faq-cyber-accordion"
            :class="{ open: faq.open }"
            @click="toggleFaq(idx)"
          >
            <div class="accordion-head">
              <span class="accordion-index">{{ String(idx + 1).padStart(2, '0') }}</span>
              <h4 class="accordion-qtext">{{ faq.q }}</h4>
              <div class="accordion-trigger-icon"></div>
            </div>
            
            <div class="accordion-body-wrapper" :class="{ expanded: faq.open }">
              <div class="accordion-body-inner">
                <p>{{ faq.a }}</p>
              </div>
            </div>
          </div>

          <div v-if="filteredFaqs.length === 0" class="faq-empty-state">
            <span class="icon">
              <svg class="cyber-svg-icon faq-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <h5>Không tìm thấy câu hỏi phù hợp</h5>
            <p>Vui lòng thử lại với từ khóa khác hoặc liên hệ trực tiếp với chuyên gia qua guided form ở trên.</p>
          </div>
        </div>

        <!-- Support CTA Footer -->
        <div class="support-footer-cta-card">
          <div class="glow-accent-overlay"></div>
          <div class="cta-card-content">
            <span class="cta-emoji-box">
              <svg class="cyber-svg-icon cta-bubble-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            <div class="cta-text-details">
              <h3>Bạn có yêu cầu đặc biệt khác?</h3>
              <p>Đội ngũ chuyên viên NextGen luôn túc trực hỗ trợ tư vấn cấu hình doanh nghiệp và các giải pháp hạ tầng máy chủ.</p>
            </div>
            <div class="cta-actions-group">
              <a href="tel:19008888" class="cta-phone-btn">
                <svg class="cyber-svg-icon cta-call-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.08 6.08l.95-.95a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Gọi 1900 8888
              </a>
              <a href="#guidedContactForm" class="cta-form-btn">Gửi Form Ngay</a>
            </div>
          </div>
        </div>

      </div>
    </section>

  </div>
</template>

<style scoped>

@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap');

/* ==================== GENERAL STYLE & TOKENS ==================== */
:deep(.cyber-svg-icon) {
  width: 1.1em;
  height: 1.1em;
  stroke: currentColor;
  stroke-width: 1.6px;
  fill: none;
  display: inline-block;
  vertical-align: middle;
  transition: transform 0.2s, stroke 0.2s;
}
.badge-icon {
  margin-right: 6px;
  width: 11px;
  height: 11px;
  margin-top: -2px;
}
.input-field-icon {
  color: var(--text-secondary);
  width: 15px;
  height: 15px;
  opacity: 0.8;
}
.error-inline-icon, .alert-err-icon {
  color: #dc2626;
  width: 14px;
  height: 14px;
  margin-right: 4px;
  margin-top: -2px;
}
.alert-ok-icon {
  color: #2563eb;
  width: 14px;
  height: 14px;
  margin-right: 4px;
  margin-top: -2px;
}
.privacy-note-icon {
  width: 11px;
  height: 11px;
  margin-right: 4px;
  margin-top: -2px;
}
.pill-badge-icon {
  width: 11px;
  height: 11px;
  margin-right: 4px;
  margin-top: -2px;
}
.btn-sched-icon {
  width: 13px;
  height: 13px;
  margin-right: 6px;
  margin-top: -2px;
}
.cta-call-icon {
  width: 13px;
  height: 13px;
  margin-right: 6px;
  margin-top: -2px;
}
:deep(.detail-icon .cyber-svg-icon) {
  width: 16px;
  height: 16px;
}
.tab-icon {
  width: 13px;
  height: 13px;
  margin-right: 6px;
  margin-top: -2px;
}
.faq-srch-icon, .faq-empty-icon {
  width: 14px;
  height: 14px;
}
.cta-bubble-icon {
  width: 18px;
  height: 18px;
  color: white;
}
.suggest-lbl-icon {
  width: 13px;
  height: 13px;
  margin-right: 4px;
  margin-top: -2px;
  color: var(--accent);
}
:deep(.cat-icon-box .cyber-svg-icon) {
  width: 18px;
  height: 18px;
  color: var(--text-secondary);
}
:deep(.guided-category-card.active .cat-icon-box .cyber-svg-icon) {
  color: white;
}

.contact-page {
  --primary: #2563EB;
  --primary-glow: rgba(37, 99, 235, 0.15);
  --secondary: #3B82F6;
  --secondary-glow: rgba(37, 99, 235, 0.15);
  --accent: #f59e0b;
  --dark-bg: #0F172A;
  --dark-surface: #111827;
  --light-bg: #0d1b2e;
  --light-surface: #111f35;
  --text-primary: #0F172A;
  --text-secondary: #475569;
  --border-color: #e6eef6;
  --card-glow: 0px 10px 35px rgba(15, 23, 42, 0.03);
  --font-heading: 'Outfit', 'Inter', sans-serif;
  --font-body: 'Inter', sans-serif;
  --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);

  background-color: var(--tn-bg);
  color: var(--text-primary);
  font-family: var(--font-body);
  overflow-x: hidden;
  position: relative;
}



/* ==================== CYBER GRADIENT HERO ==================== */
.support-hero {
  position: relative;
  background: 
    linear-gradient(135deg, rgba(15, 23, 42, 0.85) 0%, rgba(17, 24, 39, 0.8) 100%), 
    url('/elite_workspace.png') center/cover no-repeat;
  padding: 90px 24px 75px;
  text-align: center;
  color: white;
  overflow: hidden;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.glow-sphere-1 {
  position: absolute;
  top: -20%; left: -10%;
  width: 50vw; height: 50vw;
  background: radial-gradient(circle, rgba(37, 99, 235, 0.12) 0%, transparent 65%);
  pointer-events: none;
}
.glow-sphere-2 {
  position: absolute;
  bottom: -20%; right: -10%;
  width: 50vw; height: 50vw;
  background: radial-gradient(circle, rgba(37, 99, 235, 0.1) 0%, transparent 65%);
  pointer-events: none;
}

.hero-container {
  max-width: 860px;
  margin: 0 auto;
  position: relative;
  z-index: 2;
}
.support-badge {
  display: inline-block;
  font-family: var(--font-heading);
  font-size: 11px;
  font-weight: 800;
  color: var(--secondary);
  letter-spacing: 1.5px;
  background: rgba(37, 99, 235, 0.08);
  border: 1px solid rgba(37, 99, 235, 0.15);
  padding: 5px 14px;
  border-radius: 30px;
  margin-bottom: 20px;
}
.support-hero h1 {
  font-family: var(--font-heading);
  font-size: 44px;
  font-weight: 800;
  line-height: 1.25;
  letter-spacing: -1.5px;
  margin: 0 0 16px 0;
  color: #ffffff;
}
.highlight-text {
  color: var(--secondary);
}
.hero-desc {
  font-size: 15px;
  color: #94a3b8;
  line-height: 1.65;
  max-width: 680px;
  margin: 0 auto 36px;
}
.hero-actions {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-bottom: 56px;
}

/* Premium Buttons */
.btn-glow-primary {
  padding: 13px 26px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
  color: white;
  font-family: var(--font-heading);
  font-size: 13.5px;
  font-weight: 800;
  text-decoration: none;
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.35);
  transition: var(--transition);
}
.btn-glow-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(37, 99, 235, 0.5);
}
.btn-glass {
  padding: 13px 26px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
  font-family: var(--font-heading);
  font-size: 13.5px;
  font-weight: 700;
  text-decoration: none;
  backdrop-filter: blur(8px);
  transition: var(--transition);
}
.btn-glass:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

/* Stat Counter Area */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(7, auto);
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 20px;
  padding: 20px 36px;
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  box-shadow: 
    0 15px 35px -5px rgba(0, 0, 0, 0.4),
    inset 0 1px 1px rgba(255, 255, 255, 0.12);
}
.stat-card {
  text-align: center;
}
.stat-number {
  display: block;
  font-family: var(--font-heading);
  font-size: 26px;
  font-weight: 800;
  color: var(--secondary);
  line-height: 1.1;
  margin-bottom: 4px;
}
.stat-label {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.stat-border-line {
  width: 1px;
  height: 36px;
  background: rgba(255, 255, 255, 0.15);
}

/* ==================== GUIDED CONTACT FORM AREA ==================== */
.form-section-container {
  padding: 60px 24px;
}
.form-wrapper-max {
  max-width: 1200px;
  margin: 0 auto;
}

.section-magazine-title {
  margin-bottom: 36px;
}
.section-magazine-title.text-center {
  text-align: center;
}
.section-magazine-title .sub-label {
  font-family: var(--font-heading);
  font-size: 10px;
  font-weight: 800;
  color: var(--primary);
  letter-spacing: 1.5px;
  display: block;
  margin-bottom: 6px;
}
.section-magazine-title h2 {
  font-family: var(--font-heading);
  font-size: 32px;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -1px;
  margin: 0 0 10px 0;
}
.section-magazine-title h2 span {
  color: var(--primary);
}
.section-magazine-title p {
  font-size: 14px;
  color: var(--text-secondary);
  margin: 0;
}

.guided-grid-layout {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 36px;
  align-items: start;
}

/* LEFT: Guided Steps Box (light form variant) */
.guided-form-glass-card {
  background: var(--tn-surface);
  border-radius: 24px;
  border: 1px solid rgba(2,6,23,0.06);
  padding: 32px;
  box-shadow: 0 10px 30px rgba(2,6,23,0.06);
  min-height: 520px;
  display: flex;
  flex-direction: column;
}

/* Step Bar Node */
.step-progress-row {
  display: flex;
  align-items: center;
  margin-bottom: 36px;
  justify-content: space-between;
}
.step-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  position: relative;
  z-index: 2;
  width: 68px;
}
.step-num {
  width: 32px; height: 32px;
  border-radius: 50%;
  background: var(--tn-bg);
  border: 2px solid #e6eef6;
  color: var(--text-secondary);
  font-family: var(--font-heading);
  font-size: 13.5px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}
.step-label {
  font-size: 10px;
  font-weight: 700;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.step-line {
  flex-grow: 1;
  height: 2px;
  background: #cbd5e1;
  margin-top: -16px;
  z-index: 1;
  transition: var(--transition);
}

.step-node.active .step-num {
  border-color: var(--primary);
  background: var(--tn-bg);
  color: var(--primary);
  box-shadow: 0 0 10px var(--primary-glow);
}
.step-node.active .step-label {
  color: var(--primary);
  font-weight: 800;
}
.step-node.done .step-num {
  border-color: var(--primary);
  background: var(--primary);
  color: white;
}
.step-node.done .step-label {
  color: var(--text-primary);
}
.step-line.fill {
  background: var(--primary);
}

/* Step Content Styles */
.step-view-content {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}
.step-view-title {
  font-family: var(--font-heading);
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 4px 0;
}
.step-view-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
  margin: 0 0 24px 0;
}

.fade-in {
  animation: stepFadeIn 0.3s ease-out;
}
@keyframes stepFadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Step 1 Category list */
.guided-categories-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 24px;
}
.guided-category-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-radius: 14px;
  border: 1.5px solid #e6eef6;
  background: var(--tn-surface);
  cursor: pointer;
  transition: var(--transition);
  position: relative;
}
.cat-icon-box {
  width: 44px; height: 44px;
  border-radius: 10px;
  background: var(--tn-surface-2);
  border: 1px solid #e6eef6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
  transition: var(--transition);
}
.cat-details h4 {
  font-family: var(--font-heading);
  font-size: 13.5px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 2px 0;
}
.cat-details p {
  font-size: 11px;
  color: var(--text-secondary);
  line-height: 1.4;
  margin: 0;
}
.cat-check-dot {
  width: 16px; height: 16px;
  border-radius: 50%;
  border: 1.5px solid #e6eef6;
  margin-left: auto;
  background: var(--tn-surface-2);
  transition: var(--transition);
  flex-shrink: 0;
}

.guided-category-card:hover {
  border-color: var(--primary);
  background: var(--tn-bg);
  transform: translateX(3px);
}
.guided-category-card.active {
  border-color: var(--primary);
  background: rgba(37, 99, 235, 0.04);
  box-shadow: 0 6px 16px var(--primary-glow);
}
.guided-category-card.active .cat-icon-box {
  border-color: var(--primary);
  background: var(--primary);
  color: white;
}
.guided-category-card.active .cat-check-dot {
  border-color: var(--primary);
  background: var(--primary);
  box-shadow: inset 0 0 0 3px white;
}

/* Step 2 Inputs */
.guided-inputs-wrapper {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
}
.floating-input-field {
  display: flex;
  align-items: center;
  gap: 14px;
  border: 1.5px solid #e6eef6;
  border-radius: 14px;
  padding: 10px 16px;
  background: var(--tn-surface);
  transition: var(--transition);
}
.field-icon-neon {
  font-size: 18px;
  opacity: 0.7;
}
.input-block {
  display: flex;
  flex-direction: column;
  position: relative;
  flex-grow: 1;
}
.input-block input {
  border: none;
  background: transparent;
  outline: none;
  font-family: inherit;
  font-size: 13.5px;
  color: var(--text-primary);
  padding: 16px 0 4px 0;
  width: 100%;
}
.input-block label {
  position: absolute;
  left: 0; top: 12px;
  font-size: 13px;
  color: var(--text-secondary);
  pointer-events: none;
  transition: all 0.2s ease;
}

/* Floating behavior */
.input-block input:focus ~ label,
.input-block input:not(:placeholder-shown) ~ label {
  top: 0px;
  font-size: 10px;
  font-weight: 700;
  color: var(--text-secondary);
}

.floating-input-field:focus-within {
  border-color: #dbe7f3;
  background: var(--tn-surface);
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
}
.input-block input:-webkit-autofill,
.input-block input:-webkit-autofill:hover,
.input-block input:-webkit-autofill:focus,
.input-block input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
  -webkit-text-fill-color: var(--text-primary) !important;
  caret-color: var(--text-primary);
  transition: background-color 9999s ease-out 0s;
}

.step-validation-error {
  padding: 10px 14px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  font-size: 12.5px;
  font-weight: 600;
  border-radius: 10px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Step 3 suggestion tag and texts */
.suggestion-tags-row {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 14px;
  align-items: center;
}
.suggest-label {
  font-size: 11.5px;
  font-weight: 700;
  color: var(--text-secondary);
}
.suggest-tag-btn {
  padding: 5px 10px;
  border-radius: 6px;
  border: 1px solid #e6eef6;
  background: var(--tn-surface);
  cursor: pointer;
  font-family: inherit;
  font-size: 11px;
  font-weight: 600;
  color: var(--text-secondary);
  transition: var(--transition);
}
.suggest-tag-btn:hover {
  background: var(--secondary);
  border-color: var(--secondary);
  color: white;
}

.guided-textarea-field {
  border: 1.5px solid #e6eef6;
  border-radius: 14px;
  padding: 12px;
  background: var(--tn-surface);
  margin-bottom: 18px;
  transition: var(--transition);
}
.guided-textarea-field textarea {
  width: 100%;
  height: 140px;
  border: none;
  background: transparent;
  outline: none;
  font-family: inherit;
  font-size: 13.5px;
  color: var(--text-primary);
  line-height: 1.6;
  resize: none;
}
.guided-textarea-field:focus-within {
  border-color: var(--primary);
  background: var(--tn-surface);
  box-shadow: 0 4px 12px rgba(37,99,235,0.06);
}

.text-secondary-cyan {
  color: var(--secondary);
}

.human-captcha-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: min(100%, 340px);
  min-height: 72px;
  padding: 12px 14px;
  margin: -2px auto 18px;
  border: 1px solid #cfd8e3;
  border-radius: 4px;
  background: var(--tn-surface);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
  transition: var(--transition);
}
.human-captcha-box.verified {
  border-color: #8bb7f0;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}
.human-captcha-check {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  flex-grow: 1;
  min-width: 0;
}
.human-captcha-check input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}
.captcha-custom-check {
  width: 24px;
  height: 24px;
  border-radius: 3px;
  border: 2px solid #64748b;
  background: var(--tn-surface);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: var(--transition);
}
.human-captcha-check:hover .captcha-custom-check {
  border-color: #2563eb;
}
.human-captcha-box.verified .captcha-custom-check {
  border-color: #2563eb;
  background: #2563eb;
}
.captcha-check-icon {
  width: 15px;
  height: 15px;
}
.captcha-copy {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.captcha-copy strong {
  font-family: var(--font-heading);
  font-size: 13px;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.35;
}
.captcha-copy small {
  display: none;
}
.captcha-brand {
  width: 88px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  text-align: center;
  color: #111827;
}
.captcha-cloud-icon {
  width: 30px;
  height: 21px;
  margin-bottom: 2px;
}
.captcha-brand strong {
  font-family: Arial, sans-serif;
  font-size: 8.5px;
  font-weight: 800;
  letter-spacing: 1px;
  line-height: 1.15;
}
.captcha-brand span {
  max-width: 100%;
  margin-top: 2px;
  font-size: 8px;
  line-height: 1.2;
  color: #334155;
  text-decoration: underline;
}
/* Form Action Footer */
.step-actions-footer {
  margin-top: auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 20px;
  border-top: 1px solid #e6eef6;
}
.btn-step-next {
  padding: 11px 22px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
  color: white;
  font-family: var(--font-heading);
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  transition: var(--transition);
  box-shadow: 0 4px 12px var(--primary-glow);
}
.btn-step-next:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
}
.btn-step-prev {
  padding: 11px 18px;
  border-radius: 10px;
  border: 1.5px solid #e6eef6;
  background: var(--tn-bg);
  color: var(--text-secondary);
  font-family: var(--font-heading);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: var(--transition);
}
.btn-step-prev:hover {
  background: #eef2f6;
  color: var(--text-primary);
  border-color: #e6eef6;
}
.btn-step-submit {
  padding: 11px 24px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, var(--secondary) 0%, #1d4ed8 100%);
  color: white;
  font-family: var(--font-heading);
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  transition: var(--transition);
  box-shadow: 0 4px 12px var(--secondary-glow);
  min-width: 218px;
  min-height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.btn-step-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35);
}
.btn-step-submit:disabled {
  cursor: not-allowed;
}
.btn-step-submit.loading {
  width: 82px;
  min-width: 82px;
  padding: 0;
  border-radius: 10px;
  background: #5fc4d5;
  box-shadow: 0 8px 18px rgba(37, 99, 235, 0.2);
}

.spin-loader {
  display: inline-block;
  width: 18px; height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.45);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 0.75s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.form-feedback-alert {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 18px;
}
.form-feedback-alert.error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
}
.form-feedback-alert.success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #2563eb;
}

.cyber-privacy-note {
  font-size: 9.5px;
  color: #94a3b8;
  text-align: center;
  margin: 12px 0 0 0;
}

/* RIGHT: Showrooms guided panel */
.showroom-guided-panel {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.showroom-brand-header {
  margin-bottom: 4px;
}
.showroom-brand-header h3 {
  font-family: var(--font-heading);
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 4px 0;
}
.showroom-brand-header p {
  font-size: 13px;
  color: var(--text-secondary);
  margin: 0;
}
.pill-badge {
  display: inline-block;
  font-family: var(--font-heading);
  font-size: 9px;
  font-weight: 800;
  color: var(--primary);
  background: rgba(37, 99, 235, 0.06);
  padding: 3px 8px;
  border-radius: 4px;
  margin-bottom: 6px;
  letter-spacing: 0.5px;
}

.showroom-cyber-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.showroom-cyber-btn {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 18px;
  border-radius: 14px;
  border: 1.5px solid #e6eef6;
  background: var(--tn-surface);
  cursor: pointer;
  transition: var(--transition);
}
.active-neon-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #cbd5e1;
  transition: var(--transition);
}
.store-btn-info h5 {
  font-family: var(--font-heading);
  font-size: 13.5px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0;
}
.store-btn-info span {
  font-size: 10.5px;
  color: var(--text-secondary);
}

.showroom-cyber-btn:hover {
  background: var(--tn-bg);
  transform: translateX(3px);
  border-color: #cbd5e1;
}
.showroom-cyber-btn.active {
  border-color: var(--primary);
  background: rgba(37, 99, 235, 0.08);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
}
.showroom-cyber-btn.active .active-neon-dot {
  background: var(--primary);
  box-shadow: 0 0 8px var(--primary);
}

/* Dynamic details card */
.showroom-detail-glass-card {
  background: var(--tn-surface);
  border-radius: 18px;
  padding: 20px;
  border: 1px solid #e6eef6;
  display: flex;
  flex-direction: column;
  gap: 12px;
  box-shadow: 0 10px 30px rgba(2,6,23,0.04);
}
.detail-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.detail-icon {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}
.detail-text {
  display: flex;
  flex-direction: column;
}
.detail-label {
  font-size: 9.5px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}
.detail-val {
  font-size: 12.5px;
  color: var(--text-primary);
  margin: 0;
  line-height: 1.4;
}
.detail-val.bold {
  font-weight: 700;
}

.btn-visit-schedule {
  width: 100%;
  padding: 11px;
  border: 1.5px solid var(--primary);
  border-radius: 10px;
  background: transparent;
  color: var(--primary);
  font-family: var(--font-heading);
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  margin-top: 6px;
  transition: var(--transition);
}
.btn-visit-schedule:hover {
  background: var(--primary);
  color: white;
  box-shadow: 0 4px 12px var(--primary-glow);
}

/* Secondary Maps */
.showroom-embedded-map {
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid #cbd5e1;
  height: 200px;
  display: flex;
  flex-direction: column;
  position: relative;
}
.showroom-embedded-map iframe {
  width: 100%;
  flex-grow: 1;
  border: none;
}
.map-overlay-footer {
  background: var(--tn-bg);
  border-top: 1px solid #e6eef6;
  padding: 8px;
  text-align: center;
}
.cyber-map-link {
  font-family: var(--font-heading);
  font-size: 11px;
  font-weight: 800;
  color: var(--primary);
  text-decoration: none;
  transition: var(--transition);
}
.cyber-map-link:hover {
  color: #1d4ed8;
}

/* ==================== MEET OUR EXPERTS ==================== */
.experts-section-wrapper {
  background: linear-gradient(180deg, #F8FAFC 0%, #EEF2F6 100%);
  padding: 70px 24px;
  border-top: 1px solid #e2e8f0;
}
.experts-inner-container {
  max-width: 1200px;
  margin: 0 auto;
}

.experts-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-top: 36px;
}
.expert-avatar-card {
  background: var(--tn-surface);
  border-radius: 20px;
  border: 1px solid #e6eef6;
  padding: 24px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  transition: var(--transition);
  display: flex;
  flex-direction: column;
}
.expert-card-top {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 18px;
}
.expert-avatar-circle {
  width: 44px; height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  font-family: var(--font-heading);
  font-size: 14px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.expert-identity h4 {
  font-family: var(--font-heading);
  font-size: 14px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 2px 0;
}
.expert-badge-role {
  font-size: 10.5px;
  font-weight: 600;
  color: var(--text-secondary);
}
.expert-card-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex-grow: 1;
}
.exp-badge {
  align-self: flex-start;
  font-size: 10px;
  font-weight: 700;
  color: var(--primary);
  background: rgba(37, 99, 235, 0.05);
  padding: 3px 8px;
  border-radius: 4px;
}
.exp-description {
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.5;
  font-style: italic;
  margin: 0;
}

.expert-avatar-card:hover {
  transform: translateY(-4px);
  border-color: var(--primary);
  box-shadow: 0 16px 30px rgba(37, 99, 235, 0.06);
}

/* ==================== TECH TRUST BRAND BAR ==================== */
.tech-trust-banner {
  background: var(--tn-bg);
  border-top: 1px solid #e6eef6;
  border-bottom: 1px solid #e6eef6;
  padding: 32px 24px;
  text-align: center;
}
.tech-trust-inner {
  max-width: 1000px;
  margin: 0 auto;
}
.tech-trust-inner h4 {
  font-family: var(--font-heading);
  font-size: 11.5px;
  font-weight: 800;
  color: #94a3b8;
  letter-spacing: 1px;
  text-transform: uppercase;
  margin: 0 0 20px 0;
}
.partners-marquee-container {
  overflow: hidden;
  width: 100%;
  position: relative;
  display: flex;
}
.partners-marquee-track {
  display: flex;
  align-items: center;
  gap: 32px;
  white-space: nowrap;
  animation: techTickerScroll 25s linear infinite;
}
.partners-marquee-track span {
  font-family: var(--font-heading);
  font-size: 18px;
  font-weight: 800;
  color: #000000;
  cursor: default;
  transition: var(--transition);
}
.partners-marquee-track span:hover {
  color: var(--primary);
  transform: scale(1.05);
}
.partners-marquee-track .ticker-dot {
  color: #cbd5e1;
  font-size: 14px;
}
@keyframes techTickerScroll {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

/* ==================== FAQ KNOWLEDGE CENTER ==================== */
.faq-knowledge-center {
  padding: 70px 24px;
  background: var(--tn-surface);
}
.faq-inner-max {
  max-width: 900px;
  margin: 0 auto;
}

.faq-controls-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-top: 36px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.faq-category-tabs {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.faq-tab-btn {
  padding: 8px 14px;
  border-radius: 8px;
  border: 1px solid #e6eef6;
  background: var(--tn-bg);
  cursor: pointer;
  font-family: inherit;
  font-size: 12.5px;
  font-weight: 600;
  color: #475569;
  transition: var(--transition);
}
.faq-tab-btn:hover,
.faq-tab-btn.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
  box-shadow: 0 4px 10px var(--primary-glow);
}

.faq-search-box {
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1.5px solid #e6eef6;
  border-radius: 10px;
  padding: 8px 14px;
  background: var(--tn-surface);
  width: 280px;
  transition: var(--transition);
}
.search-icon {
  font-size: 14px;
  opacity: 0.6;
}
.faq-search-box input {
  border: none;
  background: transparent;
  outline: none;
  font-family: inherit;
  font-size: 13px;
  color: var(--text-primary);
  width: 100%;
}
.faq-search-box:focus-within {
  border-color: var(--primary);
  background: var(--tn-surface);
  box-shadow: 0 4px 12px rgba(37,99,235,0.06);
}

/* Accordions */
.faq-accordions-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 40px;
}
.faq-cyber-accordion {
  background: var(--tn-surface);
  border-radius: 16px;
  border: 1.5px solid #e6eef6;
  cursor: pointer;
  overflow: hidden;
  transition: var(--transition);
}
.accordion-head {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 24px;
  position: relative;
}
.accordion-index {
  font-family: var(--font-heading);
  font-size: 11px;
  font-weight: 800;
  color: #475569;
  background: var(--tn-bg);
  padding: 3px 6px;
  border-radius: 4px;
  transition: var(--transition);
}
.accordion-qtext {
  flex-grow: 1;
  font-family: var(--font-heading);
  font-size: 14px;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.4;
  margin: 0;
}
.accordion-trigger-icon {
  width: 24px; height: 24px;
  border-radius: 50%;
  background: var(--tn-bg);
  position: relative;
  transition: var(--transition);
  flex-shrink: 0;
}
.accordion-trigger-icon::before,
.accordion-trigger-icon::after {
  content: '';
  position: absolute;
  background: var(--text-secondary);
  transition: var(--transition);
}
/* horizontal line */
.accordion-trigger-icon::before {
  width: 10px; height: 2px;
  left: 7px; top: 11px;
}
/* vertical line */
.accordion-trigger-icon::after {
  width: 2px; height: 10px;
  left: 11px; top: 7px;
}

.faq-cyber-accordion:hover {
  border-color: var(--primary);
  box-shadow: 0 4px 16px rgba(37, 99, 235, 0.05);
}

/* Expanded state styles */
.faq-cyber-accordion.open {
  border-color: var(--primary);
  box-shadow: var(--card-glow);
}
.faq-cyber-accordion.open .accordion-index {
  background: rgba(37, 99, 235, 0.06);
  color: var(--primary);
}
.faq-cyber-accordion.open .accordion-trigger-icon {
  background: var(--primary);
  transform: rotate(135deg);
}
.faq-cyber-accordion.open .accordion-trigger-icon::before,
.faq-cyber-accordion.open .accordion-trigger-icon::after {
  background: white;
}

.accordion-body-wrapper {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.accordion-body-wrapper.expanded {
  max-height: 200px;
}
.accordion-body-inner {
  padding: 0 24px 20px 64px;
}
.accordion-body-inner p {
  font-size: 13.5px;
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0;
  padding-top: 12px;
  border-top: 1px solid #e6eef6;
}

.faq-empty-state {
  padding: 48px;
  text-align: center;
  border: 1.5px dashed #cbd5e1;
  border-radius: 16px;
  color: var(--text-secondary);
}
.faq-empty-state .icon {
  font-size: 32px;
  margin-bottom: 12px;
  display: block;
}
.faq-empty-state h5 {
  font-family: var(--font-heading);
  font-size: 14.5px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 6px 0;
}
.faq-empty-state p {
  font-size: 12.5px;
  margin: 0;
}

/* Bottom CTA Card */
.support-footer-cta-card {
  position: relative;
  background: #061A3A; /* Primary Navy */
  border-radius: 24px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 32px;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
  color: #ffffff;
}
.support-footer-cta-card .glow-accent-overlay {
  position: absolute;
  top: -50%; right: -20%;
  width: 80%; height: 180%;
  background: radial-gradient(circle, rgba(37, 99, 235, 0.25) 0%, transparent 60%);
  pointer-events: none;
}
.cta-card-content {
  display: flex;
  align-items: center;
  gap: 24px;
  position: relative;
  z-index: 2;
  flex-wrap: wrap;
}
.cta-emoji-box {
  width: 52px; height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}
.cta-text-details {
  flex-grow: 1;
  min-width: 250px;
}
.cta-text-details h3 {
  font-family: var(--font-heading);
  font-size: 20px;
  font-weight: 800;
  margin: 0 0 6px 0;
  color: #ffffff !important;
}
.cta-text-details p {
  font-size: 13.5px;
  color: #cbd5e1 !important;
  line-height: 1.5;
  margin: 0;
}
.cta-actions-group {
  display: flex;
  gap: 12px;
  flex-shrink: 0;
  flex-wrap: wrap;
}
.cta-phone-btn {
  padding: 12px 24px;
  border-radius: 10px;
  background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
  color: #ffffff !important;
  font-family: var(--font-heading);
  font-size: 13px;
  font-weight: 800;
  text-decoration: none;
  transition: var(--transition);
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
}
.cta-phone-btn:hover {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
}
.cta-form-btn {
  padding: 12px 24px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #ffffff !important;
  font-family: var(--font-heading);
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  transition: var(--transition);
}
.cta-form-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.25);
  transform: translateY(-2px);
}

/* ==================== RESPONSIVE RULES ==================== */
@media (max-width: 1100px) {
  .guided-grid-layout {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  .experts-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 900px) {
  .support-hero h1 {
    font-size: 36px;
  }
  .stats-grid {
    grid-template-columns: repeat(3, auto);
    justify-content: center;
    gap: 16px 28px;
  }
  .stat-border-line {
    display: none;
  }
  .faq-controls-row {
    flex-direction: column;
    align-items: stretch;
  }
  .faq-search-box {
    width: 100%;
  }
}

@media (max-width: 768px) {
  .experts-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .support-hero {
    padding: 60px 16px 50px;
  }
  .support-hero h1 {
    font-size: 28px;
  }
  .hero-desc {
    font-size: 13.5px;
    margin-bottom: 24px;
  }
  .hero-actions {
    flex-direction: column;
    width: 100%;
    gap: 10px;
  }
  .btn-glow-primary, .btn-glass {
    width: 100%;
    text-align: center;
  }
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    padding: 16px;
  }
  .stat-number {
    font-size: 20px;
  }
  .stat-label {
    font-size: 10px;
  }
  .form-section-container {
    padding: 32px 16px;
  }
  .guided-form-glass-card {
    padding: 20px;
  }
  .step-progress-row {
    margin-bottom: 24px;
  }
  .step-node {
    width: 48px;
  }
  .step-num {
    width: 28px; height: 28px;
    font-size: 12px;
  }
  .step-label {
    font-size: 8px;
  }
  .guided-category-card {
    padding: 12px;
  }
  .cat-icon-box {
    width: 36px; height: 36px;
    font-size: 16px;
  }
  .cat-details h4 {
    font-size: 12.5px;
  }
  .cat-details p {
    font-size: 10px;
  }
  .floating-input-field {
    padding: 6px 12px;
  }
  .input-block input {
    font-size: 12.5px;
  }
  .step-actions-footer {
    gap: 8px;
  }
  .human-captcha-box {
    width: min(100%, 320px);
    min-height: 70px;
    gap: 10px;
    padding: 11px 12px;
  }
  .captcha-copy strong {
    font-size: 12.5px;
  }
  .captcha-brand {
    width: 78px;
  }
  .captcha-brand strong {
    font-size: 8px;
  }
  .captcha-brand span {
    font-size: 8px;
  }
  .btn-step-next, .btn-step-prev, .btn-step-submit {
    flex-grow: 1;
    padding: 10px;
    font-size: 12px;
    text-align: center;
  }
  .tech-trust-banner {
    padding: 24px 16px;
  }
  .partners-marquee-track span {
    font-size: 15px;
  }
  .faq-knowledge-center {
    padding: 40px 16px;
  }
  .accordion-head {
    padding: 14px 16px;
    gap: 12px;
  }
  .accordion-qtext {
    font-size: 13px;
  }
  .accordion-body-inner {
    padding: 0 16px 16px 44px;
  }
  .accordion-body-inner p {
    font-size: 12.5px;
  }
  .support-footer-cta-card {
    padding: 20px;
  }
  .cta-phone-btn, .cta-form-btn {
    width: 100%;
    text-align: center;
  }
}
</style>
