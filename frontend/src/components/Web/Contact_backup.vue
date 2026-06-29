<script setup>
import { ref, computed } from 'vue'
import { onMounted } from 'vue'
import api from '@/services/api'
import { getToken } from '@/services/auth'

const name = ref('')
const phone = ref('')
const email = ref('')
const subject = ref('Tư vấn mua hàng')
const message = ref('')
const error = ref('')
const success = ref(false)
const loading = ref(false)

const subjects = [
  'Tư vấn mua hàng',
  'Hỗ trợ kỹ thuật',
  'Bảo hành & sửa chữa',
  'Hợp tác kinh doanh',
  'Khác',
]

onMounted(async () => {
  if (!getToken()) return

  try {
    const data = (await api.get('/user/profile')).data
    name.value = data.ten
    email.value = data.email
    phone.value = data.sodienthoai
  } catch (err) {
    console.log('Chưa đăng nhập')
  }
})

async function submitForm() {
  if (loading.value) return
  if (!name.value || !phone.value || !email.value || !message.value) {
    error.value = 'Vui lòng nhập đầy đủ thông tin bắt buộc'
    success.value = false
    return
  }
  try {
    error.value = ''
    success.value = false
    loading.value = true
    const data = (await api.post('/lien-he', {
      hoten: name.value,
      email: email.value,
      sodienthoai: phone.value,
      noidung: `[${subject.value}] ${message.value}`,
      danhmuc: subject.value,
    })).data
    if (data.status) {
      success.value = true
      name.value = ''
      phone.value = ''
      email.value = ''
      message.value = ''
      subject.value = 'Tư vấn mua hàng'
      setTimeout(() => { success.value = false }, 5000)
    } else {
      error.value = data.message || 'Gửi thất bại, vui lòng thử lại'
    }
  } catch (err) {
    error.value = 'Lỗi kết nối server. Vui lòng thử lại sau.'
  } finally {
    loading.value = false
  }
}

const showrooms = ref([
  {
    id: 1,
    name: 'Trụ sở chính (Nhà Bè)',
    address: 'Số 18/7 Huỳnh Tấn Phát, Thị trấn Nhà Bè, Huyện Nhà Bè, TP. Hồ Chí Minh',
    query: 'Công Ty Cổ Phần Công Nghệ Đại Dương Huỳnh Tấn Phát',
    mapUrl: 'https://www.google.com/maps?q=Công+Ty+Cổ+Phần+Công+Nghệ+Đại+Dương+Huỳnh+Tấn+Phát',
    phone: '1900 8888 (Phím 1)',
  },
  {
    id: 2,
    name: 'Chi nhánh Quận 1 (TP. HCM)',
    address: 'Số 135 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh',
    query: '135 Nguyễn Huệ, Bến Nghé, Quận 1, Thành phố Hồ Chí Minh',
    mapUrl: 'https://www.google.com/maps?q=135+Nguyễn+Huệ,+Bến+Nghé,+Quận+1,+TP+Hồ+Chí+Minh',
    phone: '1900 8888 (Phím 2)',
  },
  {
    id: 3,
    name: 'Chi nhánh Cầu Giấy (Hà Nội)',
    address: 'Số 26 Trần Thái Tông, Dịch Vọng Hậu, Cầu Giấy, Hà Nội',
    query: '26 Trần Thái Tông, Dịch Vọng Hậu, Cầu Giấy, Hà Nội',
    mapUrl: 'https://www.google.com/maps?q=26+Trần+Thái+Tông,+Dịch+Vọng+Hậu,+Cầu+Giấy,+Hà+Nội',
    phone: '1900 8888 (Phím 3)',
  },
])

const selectedShowroom = ref(showrooms.value[0])

const infos = computed(() => [
  { icon: '📍', label: 'Địa chỉ', value: selectedShowroom.value.address, color: '#dbeafe' },
  { icon: '📞', label: 'Hotline', value: selectedShowroom.value.phone, bold: true, color: '#dcfce7' },
  { icon: '✉️', label: 'Email', value: 'support@vinatech.vn', color: '#ede9fe' },
  { icon: '🕐', label: 'Giờ làm việc', value: 'T2 – T6: 8:00 – 18:00 | T7: 8:00 – 12:00', color: '#fef9c3' },
])

// FAQ data
const faqs = ref([
  {
    q: 'Tôi có thể đến trực tiếp showroom để xem hàng không?',
    a: 'Hoàn toàn có thể! Hệ thống showroom VinaTech mở cửa từ T2–T6: 8:00–18:00, T7: 8:00–12:00. Bạn có thể đến bất kỳ chi nhánh nào gần nhất để trải nghiệm sản phẩm trực tiếp cùng đội ngũ tư vấn chuyên nghiệp.',
    open: false,
  },
  {
    q: 'VinaTech có hỗ trợ giao hàng toàn quốc không?',
    a: 'Có! Chúng tôi giao hàng toàn quốc 63 tỉnh thành. Đơn hàng nội thành TP.HCM và Hà Nội được giao trong 2–4 giờ. Các tỉnh thành khác từ 1–3 ngày làm việc. Hàng được đóng gói cẩn thận, kiểm tra kỹ trước khi xuất kho.',
    open: false,
  },
  {
    q: 'Chính sách bảo hành của VinaTech như thế nào?',
    a: 'Tất cả sản phẩm tại VinaTech đều được bảo hành chính hãng từ 12–24 tháng tùy sản phẩm. Ngoài ra, VinaTech còn hỗ trợ bảo hành mở rộng thêm 12 tháng với chi phí ưu đãi. Sự cố phần cứng được xử lý miễn phí trong thời gian bảo hành.',
    open: false,
  },
  {
    q: 'Tôi muốn trả góp 0% lãi suất, cần điều kiện gì?',
    a: 'VinaTech hỗ trợ trả góp 0% qua thẻ tín dụng các ngân hàng: Techcombank, VPBank, BIDV, MB Bank, Sacombank... Bạn chỉ cần có CCCD/CMND và thẻ tín dụng còn hiệu lực. Phân kỳ linh hoạt từ 3–24 tháng, duyệt nhanh trong 15 phút tại showroom.',
    open: false,
  },
  {
    q: 'Thời gian phản hồi sau khi gửi form liên hệ là bao lâu?',
    a: 'Đội ngũ VinaTech cam kết phản hồi trong vòng 24 giờ làm việc. Với các yêu cầu khẩn cấp, bạn có thể gọi trực tiếp hotline 1900 8888 để được hỗ trợ ngay lập tức từ 8:00–18:00 các ngày trong tuần.',
    open: false,
  },
  {
    q: 'VinaTech có nhận đổi trả hàng không?',
    a: '1 đổi 1 trong 30 ngày nếu sản phẩm có lỗi phần cứng do nhà sản xuất. Hoàn tiền 100% trong 7 ngày nếu không hài lòng với điều kiện sản phẩm còn nguyên vẹn, đầy đủ phụ kiện. Liên hệ hotline để được hướng dẫn quy trình đổi trả nhanh nhất.',
    open: false,
  },
])

const toggleFaq = (index) => {
  faqs.value[index].open = !faqs.value[index].open
}
</script>

<template>
  <div class="page">
    <!-- Rest of layout simplified for backup purposes -->
    <p>Contact page backup</p>
  </div>
</template>
