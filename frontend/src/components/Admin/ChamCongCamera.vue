<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'
import { getUser } from '@/services/auth'

let faceapi = null

// === STATES ===
const videoRef = ref(null)
const canvasRef = ref(null)
const stream = ref(null)

const isModelsLoading = ref(false)
const isCameraActive = ref(false)
const isProcessing = ref(false)
const faceDetected = ref(false)
const detectionMessage = ref('Đang khởi tạo nhận diện khuôn mặt...')
const scanState = ref('idle')
const modelsReady = ref(false)
const isRegisteringFace = ref(false)
let isDetectingFace = false
let faceScanTimer = null

const userStatus = ref({
  face_registered: false,
  checked_in: false,
  checked_out: false,
  today_record: null,
  employee: null
})

const myHistory = ref([])
const myRank = ref(-1)
const totalUsersCount = ref(0)
const leaderboardList = ref([])
const currentUser = ref(getUser() || {})
const route = useRoute()
const router = useRouter()
const enrollmentTarget = ref(null)
const requestedEnrollmentId = computed(() => Number(route.query.enroll) || null)
const isEnrollmentMode = computed(() => Boolean(requestedEnrollmentId.value))
const displayEmployee = computed(() => enrollmentTarget.value || userStatus.value.employee || {
  name: currentUser.value?.ten || currentUser.value?.name,
  email: currentUser.value?.email,
  role_name: currentUser.value?.ten_vaitro || currentUser.value?.vaitro,
  avatar: currentUser.value?.anhdaidien
})
const displayFaceRegistered = computed(() =>
  isEnrollmentMode.value
    ? Boolean(enrollmentTarget.value?.face_registered)
    : Boolean(userStatus.value.face_registered)
)
const verificationOnly = true
const roles = ref([])
const creatingEmployee = ref(false)
const employeeFormError = ref('')
const employeeForm = ref({
  ten: '',
  email: '',
  sodienthoai: '',
  vaitro: '',
  matkhau: '',
  trangthai: 'active',
  so_cccd: '',
  ngaysinh: '',
  gioitinh: '',
  ngay_cap_cccd: '',
  noi_cap_cccd: ''
})
const identityFiles = ref({ anh_cccd_mat_truoc: null, anh_cccd_mat_sau: null })
const identityPreviews = ref({ anh_cccd_mat_truoc: '', anh_cccd_mat_sau: '' })
const identityError = ref('')
const identityVerified = ref(false)
const identityVerifying = ref(false)
const identityProgress = ref(0)
const identityVerificationMessage = ref('')
const identitySideStatus = ref({
  anh_cccd_mat_truoc: { state: 'idle', message: '' },
  anh_cccd_mat_sau: { state: 'idle', message: '' }
})
const identityFileHashes = ref({ anh_cccd_mat_truoc: '', anh_cccd_mat_sau: '' })

function emptyEmployeeForm() {
  return { ten: '', email: '', sodienthoai: '', vaitro: roles.value[0]?.ma_vaitro || '', matkhau: '', trangthai: 'active', so_cccd: '', ngaysinh: '', gioitinh: '', ngay_cap_cccd: '', noi_cap_cccd: '' }
}

function resetIdentityFiles() {
  Object.values(identityPreviews.value).forEach(url => url && URL.revokeObjectURL(url))
  identityFiles.value = { anh_cccd_mat_truoc: null, anh_cccd_mat_sau: null }
  identityPreviews.value = { anh_cccd_mat_truoc: '', anh_cccd_mat_sau: '' }
  identityError.value = ''
  identityVerified.value = false
  identityVerificationMessage.value = ''
  identitySideStatus.value = {
    anh_cccd_mat_truoc: { state: 'idle', message: '' },
    anh_cccd_mat_sau: { state: 'idle', message: '' }
  }
  identityFileHashes.value = { anh_cccd_mat_truoc: '', anh_cccd_mat_sau: '' }
}

async function fileHash(file) {
  const digest = await crypto.subtle.digest('SHA-256', await file.arrayBuffer())
  return Array.from(new Uint8Array(digest)).map(value => value.toString(16).padStart(2, '0')).join('')
}

async function inspectIdentitySide(field, file) {
  const expectedSide = field === 'anh_cccd_mat_truoc' ? 'front' : 'back'
  identitySideStatus.value[field] = { state: 'checking', message: 'Đang kiểm tra đúng mặt CCCD...' }
  let worker
  try {
    const hash = await fileHash(file)
    const otherField = field === 'anh_cccd_mat_truoc' ? 'anh_cccd_mat_sau' : 'anh_cccd_mat_truoc'
    identityFileHashes.value[field] = hash
    if (identityFileHashes.value[otherField] && identityFileHashes.value[otherField] === hash) {
      throw new Error('Hai ảnh đang giống nhau. Vui lòng chọn đúng hai mặt khác nhau của CCCD.')
    }

    const { createWorker } = await import('tesseract.js')
    worker = await createWorker('vie+eng', 1, { langPath: `${import.meta.env.BASE_URL}tessdata` })
    const result = await worker.recognize(file)
    const text = normalizeOcrText(result.data.text)
    const frontScore = scoreIdentitySide(text, 'front')
    const backScore = scoreIdentitySide(text, 'back')
    const isFront = frontScore >= 2 && frontScore > backScore
    const isBack = backScore >= 1 && backScore >= frontScore

    if (expectedSide === 'front' && !isFront) {
      throw new Error(isBack ? 'Đây là mặt sau CCCD. Vui lòng chuyển sang ô Mặt sau.' : 'Không nhận diện được mặt trước CCCD. Hãy chọn ảnh rõ hơn.')
    }
    if (expectedSide === 'back' && !isBack) {
      throw new Error(isFront ? 'Đây là mặt trước CCCD. Vui lòng chuyển sang ô Mặt trước.' : 'Không nhận diện được mặt sau CCCD. Hãy chọn ảnh rõ mã MRZ.')
    }
    identitySideStatus.value[field] = { state: 'valid', message: expectedSide === 'front' ? 'Đúng mặt trước CCCD' : 'Đúng mặt sau CCCD' }
  } catch (error) {
    identitySideStatus.value[field] = { state: 'invalid', message: error.message }
    identityError.value = error.message
  } finally {
    if (worker) await worker.terminate()
  }
}

async function selectIdentityImage(field, event) {
  const file = event.target.files?.[0]
  if (!file) return
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
    identityError.value = 'Ảnh CCCD phải là JPG, PNG hoặc WEBP và không vượt quá 5 MB.'
    event.target.value = ''
    return
  }
  if (identityPreviews.value[field]) URL.revokeObjectURL(identityPreviews.value[field])
  identityFiles.value[field] = file
  identityPreviews.value[field] = URL.createObjectURL(file)
  identityError.value = ''
  identityVerified.value = false
  identityVerificationMessage.value = 'Ảnh đã thay đổi, vui lòng xác thực lại CCCD.'
  await inspectIdentitySide(field, file)
}

function normalizeOcrText(value = '') {
  return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().replace(/[^A-Z0-9<\/\-:.\n ]/g, ' ')
}

function imageSize(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file)
    const img = new Image()
    img.onload = () => { URL.revokeObjectURL(url); resolve({ width: img.naturalWidth, height: img.naturalHeight }) }
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Không thể đọc kích thước ảnh.')) }
    img.src = url
  })
}

function scoreIdentitySide(text, side) {
  const frontSignals = [/CAN CUOC/, /CITIZEN IDENTITY/, /SO.*NO/, /HO VA TEN/, /FULL NAME/, /DATE OF BIRTH/, /QUOC TICH/, /NATIONALITY/]
  const backSignals = [/IDVNM/, /DAC DIEM/, /IDENTIFICATION/, /NGAY CAP/, /DATE OF ISSUE/, /FINGER/, /MRZ/, /CUC TRUONG/]
  const signals = side === 'front' ? frontSignals : backSignals
  return signals.reduce((total, pattern) => total + (pattern.test(text) ? 1 : 0), 0)
}

function parseDate(value) {
  const match = String(value || '').match(/(\d{2})[\/\-.](\d{2})[\/\-.](\d{4})/)
  return match ? `${match[3]}-${match[2]}-${match[1]}` : ''
}

function extractIdentityFields(frontRaw, backRaw) {
  const front = normalizeOcrText(frontRaw)
  const back = normalizeOcrText(backRaw)
  const frontLines = front.split('\n').map(line => line.trim().replace(/\s{2,}/g, ' ')).filter(Boolean)
  const rawFrontLines = String(frontRaw || '').split('\n').map(line => line.trim().replace(/\s{2,}/g, ' ')).filter(Boolean)
  const allDatesFront = [...front.matchAll(/\b\d{2}[\/\-.]\d{2}[\/\-.]\d{4}\b/g)].map(match => match[0])
  const allDatesBack = [...back.matchAll(/\b\d{2}[\/\-.]\d{2}[\/\-.]\d{4}\b/g)].map(match => match[0])
  const id = front.match(/\b\d{12}\b/)?.[0] || back.match(/IDVNM[^\d]*(\d{12})/)?.[1] || ''
  const nameLineIndex = frontLines.findIndex(line => /FULL NAME|HO VA TEN/.test(line))
  let name = ''
  if (nameLineIndex >= 0) {
    const rawLabelLine = rawFrontLines.find(line => /FULL NAME|HO VA TEN/.test(normalizeOcrText(line))) || ''
    name = rawLabelLine.includes(':') ? rawLabelLine.split(':').pop().trim() : ''
    if (!name || /FULL\s*NAME|HỌ\s*VÀ\s*TÊN|HO\s*VA\s*TEN/iu.test(name)) {
      const rawIndex = rawFrontLines.indexOf(rawLabelLine)
      name = rawFrontLines[rawIndex + 1] || ''
    }
  }
  name = name
    .replace(/(?:DATE OF BIRTH|NGÀY SINH|SEX|GIỚI TÍNH).*$/iu, '')
    .replace(/[^\p{L} ]/gu, ' ')
    .replace(/\s{2,}/g, ' ')
    .trim()
  if (name.length < 4 || /^(FULL NAME|HO VA TEN|DATE OF BIRTH|CITIZEN IDENTITY)$/.test(name)) name = ''
  const gender = /(?:SEX|GIOI TINH)[\s\/:.-]*(FEMALE|MALE|NAM|NU)/.exec(front)?.[1] || ''
  return {
    so_cccd: id,
    ten: name ? name.toLocaleLowerCase('vi').replace(/(^|\s)\p{L}/gu, letter => letter.toLocaleUpperCase('vi')) : '',
    ngaysinh: parseDate(allDatesFront[0]),
    gioitinh: /FEMALE|\bNU\b/.test(gender) ? 'Nữ' : (/MALE|\bNAM\b/.test(gender) ? 'Nam' : ''),
    ngay_cap_cccd: parseDate(allDatesBack[0]),
    noi_cap_cccd: 'Cục Cảnh sát quản lý hành chính về trật tự xã hội'
  }
}

async function verifyIdentityImages() {
  const frontFile = identityFiles.value.anh_cccd_mat_truoc
  const backFile = identityFiles.value.anh_cccd_mat_sau
  identityError.value = ''
  identityVerificationMessage.value = ''
  if (!frontFile || !backFile) {
    identityError.value = 'Vui lòng tải đủ mặt trước và mặt sau trước khi xác thực.'
    return
  }

  identityVerifying.value = true
  identityProgress.value = 0
  let worker
  try {
    const [frontSize, backSize] = await Promise.all([imageSize(frontFile), imageSize(backFile)])
    if (frontSize.width <= frontSize.height || backSize.width <= backSize.height) {
      throw new Error('CCCD phải được chụp nằm ngang, thấy trọn bốn góc và không bị xoay.')
    }
    const { createWorker } = await import('tesseract.js')
    worker = await createWorker('vie+eng', 1, {
      langPath: `${import.meta.env.BASE_URL}tessdata`,
      logger: message => {
        if (message.status === 'recognizing text') identityProgress.value = Math.round(message.progress * 50)
      }
    })
    const frontResult = await worker.recognize(frontFile)
    identityProgress.value = 50
    const backResult = await worker.recognize(backFile)
    identityProgress.value = 100
    const frontText = normalizeOcrText(frontResult.data.text)
    const backText = normalizeOcrText(backResult.data.text)
    const frontScore = scoreIdentitySide(frontText, 'front')
    const backScore = scoreIdentitySide(backText, 'back')
    const reversedFrontScore = scoreIdentitySide(frontText, 'back')
    const reversedBackScore = scoreIdentitySide(backText, 'front')

    if (reversedFrontScore > frontScore && reversedBackScore > backScore) throw new Error('Bạn đang tải ngược hai mặt CCCD. Hãy đổi ảnh mặt trước và mặt sau.')
    if (frontScore < 2) throw new Error('Ảnh mặt trước không hợp lệ hoặc chữ quá mờ. Hãy chụp rõ số CCCD và thông tin cá nhân.')
    if (backScore < 1) throw new Error('Ảnh mặt sau không hợp lệ hoặc chữ quá mờ. Hãy chụp rõ mã MRZ và ngày cấp.')

    const extracted = extractIdentityFields(frontResult.data.text, backResult.data.text)
    if (!/^\d{12}$/.test(extracted.so_cccd)) throw new Error('Không đọc được đủ 12 số CCCD. Hãy chụp gần hơn, tránh lóa sáng.')
    Object.entries(extracted).forEach(([key, value]) => { if (value) employeeForm.value[key] = value })
    identityVerified.value = true
    identityVerificationMessage.value = 'Đã xác thực đúng mặt trước, mặt sau và tự động điền thông tin. Vui lòng kiểm tra lại trước khi lưu.'
  } catch (error) {
    identityVerified.value = false
    identityError.value = error.message || 'Không thể đọc CCCD. Vui lòng thử lại với ảnh rõ hơn.'
  } finally {
    if (worker) await worker.terminate()
    identityVerifying.value = false
  }
}

function buildEmployeePayload(includePasswordConfirmation = false) {
  const data = new FormData()
  Object.entries(employeeForm.value).forEach(([key, value]) => {
    if (key === 'matkhau' && !value) return
    data.append(key, value ?? '')
  })
  if (includePasswordConfirmation) data.append('matkhau_confirmation', employeeForm.value.matkhau)
  Object.entries(identityFiles.value).forEach(([key, file]) => file && data.append(key, file))
  return data
}
const defaultWorkAssignment = () => ({
  loai_ca: 'full_day',
  ngay_bat_dau: new Date().toISOString().slice(0, 10),
  ngay_ket_thuc: '',
  thu_lam_viec: [1, 2, 3, 4, 5, 6]
})
const workAssignment = ref(defaultWorkAssignment())
const workAssignmentErrors = ref({})
const workAssignmentTouched = ref({})
const weekdays = [
  { value: 1, label: 'Thứ 2' }, { value: 2, label: 'Thứ 3' },
  { value: 3, label: 'Thứ 4' }, { value: 4, label: 'Thứ 5' },
  { value: 5, label: 'Thứ 6' }, { value: 6, label: 'Thứ 7' },
  { value: 7, label: 'Chủ nhật' }
]
const employeeErrors = ref({})
const employeeTouched = ref({})
const employees = ref([])
const employeeSearch = ref('')
const employeesLoading = ref(false)
const editingEmployee = ref(null)
const savingEmployee = ref(false)
const filteredEmployees = computed(() => {
  const keyword = employeeSearch.value.trim().toLocaleLowerCase('vi')
  if (!keyword) return employees.value
  return employees.value.filter(employee =>
    [employee.ten, employee.email, employee.sodienthoai, employee.ten_vaitro]
      .some(value => String(value || '').toLocaleLowerCase('vi').includes(keyword))
  )
})
const hasEmployeeDraft = computed(() =>
  ['ten', 'email', 'sodienthoai', 'matkhau'].some(field => String(employeeForm.value[field] || '').trim())
)
const setupSteps = computed(() => [
  {
    number: 1,
    label: 'Tài khoản & vai trò',
    done: Boolean(employeeForm.value.ten && employeeForm.value.email && employeeForm.value.vaitro && (editingEmployee.value || employeeForm.value.matkhau))
  },
  { number: 2, label: 'Xác thực CCCD', done: identityVerified.value },
  { number: 3, label: 'Lịch & khuôn mặt', done: Boolean(workAssignment.value.ngay_bat_dau && workAssignment.value.thu_lam_viec.length && faceDetected.value) }
])

const videoDevices = ref([])
const selectedDeviceId = ref('')
const nextAttendanceAction = computed(() => {
  if (userStatus.value.checked_out) return 'Đã hoàn thành chấm công hôm nay'
  if (userStatus.value.checked_in) return 'Bước tiếp theo: Check-out khi kết thúc ca'
  return 'Bước tiếp theo: Check-in để bắt đầu ca'
})

async function getCameraDevices() {
  try {
    const devices = await navigator.mediaDevices.enumerateDevices()
    const filtered = devices.filter(device => device.kind === 'videoinput')
    videoDevices.value = filtered
    
    if (filtered.length > 0) {
      const exists = filtered.some(d => d.deviceId === selectedDeviceId.value)
      if (!selectedDeviceId.value || !exists) {
        // Ưu tiên camera thật
        const defaultDevice = filtered.find(d => d.label && !d.label.toLowerCase().includes('virtual')) || filtered[0]
        selectedDeviceId.value = defaultDevice.deviceId
      }
    }
  } catch (err) {
    console.error('Lỗi lấy danh sách camera:', err)
  }
}

async function onCameraChange(event) {
  const newId = event.target.value
  selectedDeviceId.value = newId
  if (isCameraActive.value) {
    await startCamera()
  }
}

// === HELPER FUNCTIONS ===
function getAvatarUrl(avatar, name) {
  if (!avatar) return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'Staff')}&background=0D8ABC&color=fff`
  return avatar.startsWith('http') ? avatar : storageUrl(avatar)
}

function formatTime(timeStr) {
  if (!timeStr) return '--:--'
  // Cắt bớt phần giây :ss nếu có
  const parts = timeStr.split(':')
  if (parts.length >= 2) return `${parts[0]}:${parts[1]}`
  return timeStr
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

// === API CALLS ===
async function fetchStatus() {
  try {
    const res = await api.get('/cham-cong/status', { skipGlobalLoader: true })
    if (res.data.success) {
      userStatus.value = {
        face_registered: res.data.face_registered,
        checked_in: res.data.checked_in,
        checked_out: res.data.checked_out,
        today_record: res.data.today_record,
        employee: res.data.employee || null
      }
    }
  } catch (error) {
    console.error('Lỗi tải trạng thái chấm công:', error)
  }
}

async function fetchMyHistory() {
  try {
    const res = await api.get('/cham-cong/my-history', { skipGlobalLoader: true })
    if (res.data.success) {
      myHistory.value = res.data.data || []
    }
  } catch (error) {
    console.error('Lỗi tải lịch sử chấm công cá nhân:', error)
  }
}

async function fetchLeaderboard() {
  try {
    const res = await api.get('/cham-cong/leaderboard', { skipGlobalLoader: true })
    if (res.data.success) {
      myRank.value = res.data.my_rank
      totalUsersCount.value = res.data.total_users
      leaderboardList.value = res.data.leaderboard || []
    }
  } catch (error) {
    console.error('Lỗi tải bảng xếp hạng:', error)
  }
}

// === CAMERA LOGIC ===
async function initFaceApi() {
  scanState.value = 'idle'
  detectionMessage.value = 'Đang chuẩn bị kiểm tra khuôn mặt...'
  await startCamera()

  window.setTimeout(async () => {
    if (await ensureFaceModels()) startFaceMonitoring()
  }, 500)
}

async function fetchEnrollmentTarget() {
  const employeeId = Number(route.query.enroll)
  if (!employeeId) return

  try {
    const res = await api.get('/admin/cham-cong/nhan-vien', { skipGlobalLoader: true })
    const target = (res.data.data || []).find(employee => Number(employee.id) === employeeId)
    if (!target) {
      await swal.warning('Không tìm thấy nhân viên', 'Hồ sơ không tồn tại hoặc không thuộc nhóm nhân viên.')
      router.replace({ name: 'admin-quanly-chamcong' })
      return
    }
    enrollmentTarget.value = {
      ...target,
      name: target.ten,
      avatar: target.anhdaidien,
      role_name: target.ten_vaitro
    }
    resetIdentityFiles()
    employeeForm.value = {
      ten: target.ten || '',
      email: target.email || '',
      sodienthoai: target.sodienthoai || '',
      vaitro: target.ma_vaitro || target.vaitro || '',
      matkhau: '',
      trangthai: target.trangthai || 'active',
      so_cccd: target.so_cccd || '',
      ngaysinh: target.ngaysinh ? String(target.ngaysinh).slice(0, 10) : '',
      gioitinh: target.gioitinh || '',
      ngay_cap_cccd: target.ngay_cap_cccd ? String(target.ngay_cap_cccd).slice(0, 10) : '',
      noi_cap_cccd: target.noi_cap_cccd || ''
    }
    identityVerified.value = Boolean(target.co_anh_cccd_mat_truoc && target.co_anh_cccd_mat_sau)
    identityVerificationMessage.value = identityVerified.value
      ? 'Hồ sơ CCCD đã được lưu đầy đủ cho nhân viên này.'
      : 'Nhân viên chưa có đủ hồ sơ CCCD. Hãy chọn Sửa hồ sơ để bổ sung.'

    // Hồ sơ có sẵn phải tải cả lịch đã gán; trước đây màn hình chỉ hiện lịch mặc định
    // nên quản trị viên tưởng đã lưu, trong khi backend chưa có bản ghi lịch làm việc.
    const scheduleResponse = await api.get(
      `/admin/cham-cong/nhan-vien/${employeeId}/lich-lam`,
      { skipGlobalLoader: true, cache: false }
    )
    const assignment = scheduleResponse.data?.data
    workAssignment.value = assignment ? {
      loai_ca: assignment.loai_ca || 'full_day',
      ngay_bat_dau: String(assignment.ngay_bat_dau || '').slice(0, 10),
      ngay_ket_thuc: assignment.ngay_ket_thuc ? String(assignment.ngay_ket_thuc).slice(0, 10) : '',
      thu_lam_viec: (assignment.thu_lam_viec || []).map(Number)
    } : defaultWorkAssignment()
  } catch (error) {
    swal.error('Không tải được hồ sơ', error.response?.data?.message || 'Vui lòng thử lại.')
  }
}

async function fetchRoles() {
  try {
    const response = await api.get('/admin/vaitro', { skipGlobalLoader: true })
    roles.value = (response.data.data || []).filter(role => role.ma_vaitro !== 'user')
    if (!employeeForm.value.vaitro && roles.value.length) {
      employeeForm.value.vaitro = roles.value[0].ma_vaitro
    }
  } catch (error) {
    employeeFormError.value = 'Không thể tải danh sách vai trò.'
  }
}

const employeeValidators = {
  ten(value) {
    const name = String(value || '').trim()
    if (!name) return 'Vui lòng nhập họ và tên nhân viên.'
    if (name.length < 2 || name.length > 100) return 'Họ tên phải có từ 2 đến 100 ký tự.'
    if (!/^[\p{L}\s.'-]+$/u.test(name)) return 'Họ tên chỉ được chứa chữ cái và khoảng trắng.'
    return ''
  },
  email(value) {
    const email = String(value || '').trim()
    if (!email) return 'Vui lòng nhập email đăng nhập.'
    if (email.length > 150) return 'Email không được vượt quá 150 ký tự.'
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(email)) return 'Email chưa đúng định dạng, ví dụ: nhanvien@nextgen.vn.'
    return ''
  },
  sodienthoai(value) {
    const phone = String(value || '').replace(/[\s.-]/g, '')
    if (!phone) return ''
    if (!/^(?:\+84|0)(?:3|5|7|8|9)\d{8}$/.test(phone)) return 'Số điện thoại Việt Nam chưa hợp lệ.'
    return ''
  },
  vaitro(value) {
    return value ? '' : 'Vui lòng chọn vai trò làm việc.'
  },
  matkhau(value) {
    const password = String(value || '')
    if (!password) return 'Vui lòng nhập mật khẩu ban đầu.'
    if (password.length < 8) return 'Mật khẩu phải có ít nhất 8 ký tự.'
    if (!/[A-Za-zÀ-ỹ]/.test(password) || !/\d/.test(password)) return 'Mật khẩu cần có ít nhất 1 chữ và 1 số.'
    return ''
  }
}

function validateEmployeeField(field) {
  employeeTouched.value[field] = true
  if (field === 'matkhau' && editingEmployee.value && !employeeForm.value.matkhau) {
    employeeErrors.value.matkhau = ''
    return true
  }
  employeeErrors.value[field] = employeeValidators[field]?.(employeeForm.value[field]) || ''
  return !employeeErrors.value[field]
}

function validateEmployeeForm() {
  const fields = editingEmployee.value
    ? ['ten', 'email', 'sodienthoai', 'vaitro']
    : ['ten', 'email', 'sodienthoai', 'vaitro', 'matkhau']
  fields.forEach(validateEmployeeField)
  const number = String(employeeForm.value.so_cccd || '').trim()
  identityError.value = !/^\d{12}$/.test(number)
    ? 'Số CCCD phải gồm đúng 12 chữ số.'
    : (!editingEmployee.value && (!identityFiles.value.anh_cccd_mat_truoc || !identityFiles.value.anh_cccd_mat_sau))
      ? 'Vui lòng tải đủ ảnh mặt trước và mặt sau CCCD.'
      : (!editingEmployee.value && !identityVerified.value)
        ? 'Vui lòng bấm “Xác thực & đọc CCCD” trước khi tạo nhân viên.'
        : ''
  const scheduleValid = validateWorkAssignment()
  return scheduleValid && !identityError.value && fields.every(field => !employeeErrors.value[field])
}

function validateWorkAssignmentField(field) {
  workAssignmentTouched.value[field] = true
  let message = ''
  if (field === 'ngay_bat_dau' && !workAssignment.value.ngay_bat_dau) {
    message = 'Vui lòng chọn ngày bắt đầu làm việc.'
  }
  if (field === 'ngay_ket_thuc' && workAssignment.value.ngay_ket_thuc) {
    if (!workAssignment.value.ngay_bat_dau) message = 'Vui lòng chọn ngày bắt đầu trước.'
    else if (workAssignment.value.ngay_ket_thuc < workAssignment.value.ngay_bat_dau) {
      message = 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.'
    }
  }
  if (field === 'thu_lam_viec' && !workAssignment.value.thu_lam_viec.length) {
    message = 'Vui lòng chọn ít nhất một ngày làm việc.'
  }
  workAssignmentErrors.value[field] = message
  return !message
}

function validateWorkAssignment() {
  return ['ngay_bat_dau', 'ngay_ket_thuc', 'thu_lam_viec']
    .map(validateWorkAssignmentField)
    .every(Boolean)
}

function onStartDateChanged() {
  validateWorkAssignmentField('ngay_bat_dau')
  if (workAssignment.value.ngay_ket_thuc || workAssignmentTouched.value.ngay_ket_thuc) {
    validateWorkAssignmentField('ngay_ket_thuc')
  }
}

function applyServerValidationErrors(error, target = employeeErrors) {
  const errors = error.response?.data?.errors || {}
  Object.entries(errors).forEach(([field, messages]) => {
    const message = Array.isArray(messages) ? messages[0] : String(messages)
    const scheduleField = field.split('.')[0]
    if (['loai_ca', 'ngay_bat_dau', 'ngay_ket_thuc', 'thu_lam_viec'].includes(scheduleField)) {
      workAssignmentErrors.value[scheduleField] = message
      workAssignmentTouched.value[scheduleField] = true
    } else {
      target.value[field] = message
    }
  })
}

async function fetchEmployees() {
  employeesLoading.value = true
  try {
    const response = await api.get('/admin/cham-cong/nhan-vien', { skipGlobalLoader: true })
    employees.value = response.data.data || []
  } catch (error) {
    console.error('Không thể tải danh sách nhân viên:', error)
  } finally {
    employeesLoading.value = false
  }
}

async function createEmployeeAndEnroll() {
  employeeFormError.value = ''
  if (!validateEmployeeForm()) return

  creatingEmployee.value = true
  let createdEmployeeId = null
  try {
    await pauseFaceMonitoring()
    if (!await ensureFaceModels()) throw new Error('Không thể tải bộ nhận diện khuôn mặt.')
    const descriptor = await detectFaceDescriptor()
    if (!descriptor) {
      employeeFormError.value = detectionMessage.value
      return
    }

    const payload = buildEmployeePayload(true)
    const response = await api.post('/admin/users', payload)
    const created = response.data.user
    createdEmployeeId = created.id
    await api.put(`/admin/cham-cong/nhan-vien/${created.id}/lich-lam`, {
      ...workAssignment.value,
      ngay_ket_thuc: workAssignment.value.ngay_ket_thuc || null
    })
    await api.post(`/admin/cham-cong/nhan-vien/${created.id}/dang-ky-khuon-mat`, {
      face_descriptor: descriptor
    })
    const role = roles.value.find(item => item.ma_vaitro === created.vaitro)
    enrollmentTarget.value = {
      ...created,
      name: created.ten,
      avatar: created.anhdaidien,
      role_name: role?.ten_vaitro || created.vaitro,
      ten_vaitro: role?.ten_vaitro || created.vaitro,
      face_registered: true
    }
    await router.replace({ name: 'admin-chamcong-camera', query: { enroll: created.id } })
    employeeForm.value = emptyEmployeeForm()
    resetIdentityFiles()
    workAssignment.value = defaultWorkAssignment()
    workAssignmentErrors.value = {}
    workAssignmentTouched.value = {}
    employeeErrors.value = {}
    employeeTouched.value = {}
    await fetchEmployees()
    swal.success('Hoàn tất thiết lập nhân viên', `Đã tạo hồ sơ, gán vai trò và đăng ký khuôn mặt cho ${created.ten}.`)
  } catch (error) {
    if (createdEmployeeId) {
      try {
        await api.delete(`/admin/users/${createdEmployeeId}`)
      } catch (rollbackError) {
        console.error('Không thể hoàn tác hồ sơ tạo chưa hoàn chỉnh:', rollbackError)
      }
    }
    applyServerValidationErrors(error)
    employeeFormError.value = error.response?.data?.message || error.message || 'Không thể hoàn tất thiết lập nhân viên.'
  } finally {
    creatingEmployee.value = false
    if (faceapi?.nets?.tinyFaceDetector?.isLoaded) startFaceMonitoring()
  }
}

async function handleUnifiedEmployeeSubmit() {
  if (editingEmployee.value) {
    await saveEmployee()
    return
  }
  if (isEnrollmentMode.value) {
    if (!enrollmentTarget.value) {
      await swal.error('Chưa tải được nhân viên', 'Không thể đăng ký khuôn mặt khi hồ sơ nhân viên chưa tải thành công. Vui lòng tải lại trang.')
      return
    }
    await registerCurrentFace()
    await fetchEmployees()
    return
  }
  await createEmployeeAndEnroll()
}

async function openEditEmployee(employee) {
  editingEmployee.value = employee
  employeeForm.value = {
    ten: employee.ten || '',
    email: employee.email || '',
    sodienthoai: employee.sodienthoai || '',
    vaitro: employee.ma_vaitro || employee.vaitro || '',
    trangthai: employee.trangthai || 'active',
    matkhau: ''
    , so_cccd: employee.so_cccd || ''
    , ngaysinh: employee.ngaysinh ? String(employee.ngaysinh).slice(0, 10) : ''
    , gioitinh: employee.gioitinh || ''
    , ngay_cap_cccd: employee.ngay_cap_cccd ? String(employee.ngay_cap_cccd).slice(0, 10) : ''
    , noi_cap_cccd: employee.noi_cap_cccd || ''
  }
  resetIdentityFiles()
  identityVerified.value = Boolean(employee.co_anh_cccd_mat_truoc && employee.co_anh_cccd_mat_sau)
  employeeErrors.value = {}
  employeeTouched.value = {}
  employeeFormError.value = ''
  workAssignmentErrors.value = {}
  workAssignmentTouched.value = {}
  try {
    const response = await api.get(`/admin/cham-cong/nhan-vien/${employee.id}/lich-lam`, { skipGlobalLoader: true })
    const assignment = response.data?.data
    workAssignment.value = assignment ? {
      loai_ca: assignment.loai_ca,
      ngay_bat_dau: String(assignment.ngay_bat_dau).slice(0, 10),
      ngay_ket_thuc: assignment.ngay_ket_thuc ? String(assignment.ngay_ket_thuc).slice(0, 10) : '',
      thu_lam_viec: assignment.thu_lam_viec || []
    } : defaultWorkAssignment()
  } catch (_) {
    workAssignment.value = defaultWorkAssignment()
    workAssignmentErrors.value = {}
    workAssignmentTouched.value = {}
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function saveEmployee() {
  if (!editingEmployee.value || !validateEmployeeForm()) return
  savingEmployee.value = true
  try {
    const payload = buildEmployeePayload(false)
    payload.append('_method', 'PUT')
    await api.post(`/admin/users/${editingEmployee.value.id}`, payload)
    await api.put(`/admin/cham-cong/nhan-vien/${editingEmployee.value.id}/lich-lam`, {
      ...workAssignment.value,
      ngay_ket_thuc: workAssignment.value.ngay_ket_thuc || null
    })
    await fetchEmployees()
    if (Number(enrollmentTarget.value?.id) === Number(editingEmployee.value.id)) {
      const updated = employees.value.find(item => Number(item.id) === Number(editingEmployee.value.id))
      if (updated) enrollmentTarget.value = { ...updated, name: updated.ten, avatar: updated.anhdaidien, role_name: updated.ten_vaitro }
    }
    editingEmployee.value = null
    employeeForm.value = emptyEmployeeForm()
    resetIdentityFiles()
    workAssignment.value = defaultWorkAssignment()
    employeeErrors.value = {}
    employeeTouched.value = {}
    swal.success('Đã cập nhật', 'Thông tin nhân viên đã được lưu.')
  } catch (error) {
    applyServerValidationErrors(error)
    employeeFormError.value = error.response?.data?.message || 'Vui lòng kiểm tra lại thông tin.'
  } finally {
    savingEmployee.value = false
  }
}

function cancelEditEmployee() {
  editingEmployee.value = null
  employeeForm.value = emptyEmployeeForm()
  resetIdentityFiles()
  workAssignment.value = defaultWorkAssignment()
  workAssignmentErrors.value = {}
  workAssignmentTouched.value = {}
  employeeErrors.value = {}
  employeeTouched.value = {}
  employeeFormError.value = ''
}

async function selectEmployeeForFace(employee) {
  enrollmentTarget.value = { ...employee, name: employee.ten, avatar: employee.anhdaidien, role_name: employee.ten_vaitro }
  await router.replace({ name: 'admin-chamcong-camera', query: { enroll: employee.id } })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function removeEmployeeFace(employee) {
  const confirmed = await swal.confirm(
    'Xóa dữ liệu khuôn mặt?',
    `Nhân viên ${employee.ten} sẽ phải đăng ký lại trước khi chấm công.`
  )
  if (!confirmed) return
  try {
    await api.delete(`/admin/cham-cong/nhan-vien/${employee.id}/khuon-mat`)
    await fetchEmployees()
    if (Number(enrollmentTarget.value?.id) === Number(employee.id)) {
      enrollmentTarget.value.face_registered = false
    }
    swal.success('Đã xóa khuôn mặt', 'Dữ liệu sinh trắc học đã được gỡ khỏi hồ sơ.')
  } catch (error) {
    swal.error('Không thể xóa khuôn mặt', error.response?.data?.message || 'Vui lòng thử lại.')
  }
}

async function deleteEmployee(employee) {
  if (Number(employee.id) === Number(currentUser.value?.id)) {
    swal.warning('Không thể xóa', 'Bạn không thể xóa chính tài khoản đang đăng nhập.')
    return
  }
  const confirmed = await swal.confirm(
    'Xóa nhân viên?',
    `Hồ sơ ${employee.ten} và dữ liệu liên quan sẽ bị xóa. Thao tác này không thể hoàn tác.`
  )
  if (!confirmed) return
  try {
    await api.delete(`/admin/users/${employee.id}`)
    await fetchEmployees()
    if (Number(enrollmentTarget.value?.id) === Number(employee.id)) {
      enrollmentTarget.value = null
      await router.replace({ name: 'admin-chamcong-camera' })
    }
    swal.success('Đã xóa nhân viên', 'Hồ sơ đã được xóa khỏi hệ thống.')
  } catch (error) {
    swal.error('Không thể xóa nhân viên', error.response?.data?.message || 'Hồ sơ đang có dữ liệu liên quan.')
  }
}

async function ensureFaceRuntime() {
  if (faceapi) return true
  isModelsLoading.value = true
  try {
    const faceApiModule = await import('@vladmandic/face-api')
    faceapi = faceApiModule

    // Ưu tiên GPU để tránh khóa luồng giao diện khi suy luận khuôn mặt.
    if (faceapi.tf) {
      try {
        await faceapi.tf.setBackend('webgl')
        await faceapi.tf.ready()
      } catch (backendError) {
        console.warn('WebGL không khả dụng, dùng bộ xử lý mặc định:', backendError)
      }
    }
    return true
  } catch (error) {
    console.error('Không thể khởi tạo nhận diện:', error)
    scanState.value = 'error'
    detectionMessage.value = 'Không thể khởi tạo nhận diện khuôn mặt'
    return false
  } finally {
    isModelsLoading.value = false
  }
}

async function ensurePresenceModel() {
  if (!await ensureFaceRuntime()) return false
  if (faceapi.nets.tinyFaceDetector.isLoaded) return true

  try {
    detectionMessage.value = 'Đang tải bộ phát hiện khuôn mặt...'
    await faceapi.nets.tinyFaceDetector.loadFromUri('/models')
    return true
  } catch (error) {
    scanState.value = 'error'
    detectionMessage.value = 'Không thể tải bộ phát hiện khuôn mặt'
    return false
  }
}

async function ensureFaceModels() {
  if (modelsReady.value && faceapi) return true

  isModelsLoading.value = true
  detectionMessage.value = 'Đang tải bộ xác thực nhân viên...'
  try {
    if (!await ensurePresenceModel()) return false

    const modelTasks = []
    if (!faceapi.nets.faceLandmark68TinyNet.isLoaded) {
      modelTasks.push(faceapi.nets.faceLandmark68TinyNet.loadFromUri('/models'))
    }
    if (!faceapi.nets.faceRecognitionNet.isLoaded) {
      modelTasks.push(faceapi.nets.faceRecognitionNet.loadFromUri('/models'))
    }
    const modelLoading = Promise.all(modelTasks)
    const modelTimeout = new Promise((_, reject) => {
      window.setTimeout(() => reject(new Error('MODEL_LOAD_TIMEOUT')), 20000)
    })
    await Promise.race([modelLoading, modelTimeout])

    modelsReady.value = true
    detectionMessage.value = 'Bộ nhận diện đã sẵn sàng'
    return true
  } catch (error) {
    console.error('Không thể tải mô hình nhận diện:', error)
    detectionMessage.value = 'Không thể tải mô hình nhận diện khuôn mặt'
    swal.error('Lỗi nhận diện', 'Không thể tải mô hình nhận diện khuôn mặt. Vui lòng tải lại trang.')
    return false
  } finally {
    isModelsLoading.value = false
  }
}

async function detectFacePresence() {
  if (!faceapi?.nets?.tinyFaceDetector?.isLoaded || !videoRef.value || videoRef.value.readyState < 2 || isDetectingFace || document.hidden) return

  isDetectingFace = true
  scanState.value = 'scanning'
  detectionMessage.value = 'Đang kiểm tra khuôn mặt...'
  try {
    const detectionTask = faceapi.detectAllFaces(
      videoRef.value,
      new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.35 })
    )
    const detections = modelsReady.value
      ? await detectionTask.withFaceLandmarks(true)
      : await detectionTask
    faceDetected.value = detections.length === 1
    if (detections.length === 1) {
      if (modelsReady.value) {
        const visibility = checkFaceVisibility(detections[0])
        faceDetected.value = visibility.valid
        scanState.value = visibility.valid ? 'detected' : 'occluded'
        detectionMessage.value = visibility.message
      } else {
        scanState.value = 'scanning'
        detectionMessage.value = 'Đã thấy khuôn mặt — đang kiểm tra che phủ và độ rõ'
      }
    } else if (detections.length > 1) {
      scanState.value = 'multiple'
      detectionMessage.value = 'Có nhiều khuôn mặt — chỉ để một người trước camera'
    } else {
      scanState.value = 'no-face'
      detectionMessage.value = 'Không phát hiện khuôn mặt — chưa thể đăng ký'
    }
  } finally {
    isDetectingFace = false
  }
}

function startFaceMonitoring() {
  if (faceScanTimer) window.clearInterval(faceScanTimer)
  detectFacePresence()
  faceScanTimer = window.setInterval(detectFacePresence, 3000)
}

async function pauseFaceMonitoring() {
  if (faceScanTimer) {
    window.clearInterval(faceScanTimer)
    faceScanTimer = null
  }
  for (let index = 0; index < 20 && isDetectingFace; index++) {
    await new Promise(resolve => window.setTimeout(resolve, 50))
  }
}

function checkFaceVisibility(result) {
  const box = result?.detection?.box
  const landmarks = result?.landmarks
  const video = videoRef.value
  if (!box || !landmarks || !video?.videoWidth || !video?.videoHeight) {
    return { valid: false, message: 'Không đọc được đầy đủ mắt, mũi và miệng — hãy để lộ rõ toàn bộ khuôn mặt' }
  }
  if (Number(result.detection.score || 0) < 0.55) {
    return { valid: false, message: 'Khuôn mặt chưa đủ rõ — hãy tháo khẩu trang, bỏ vật che mặt và nhìn thẳng camera' }
  }

  const faceRatio = box.width / video.videoWidth
  if (faceRatio < 0.2) return { valid: false, message: 'Khuôn mặt đang quá xa — hãy tiến gần camera hơn' }
  if (faceRatio > 0.78) return { valid: false, message: 'Khuôn mặt đang quá gần — hãy lùi lại một chút' }

  const leftEye = landmarks.getLeftEye()
  const rightEye = landmarks.getRightEye()
  const nose = landmarks.getNose()
  const mouth = landmarks.getMouth()
  if (leftEye.length < 2 || rightEye.length < 2 || nose.length < 4 || mouth.length < 4) {
    return { valid: false, message: 'Mắt, mũi hoặc miệng đang bị che — vui lòng để lộ rõ khuôn mặt' }
  }

  const averagePoint = points => ({
    x: points.reduce((sum, point) => sum + point.x, 0) / points.length,
    y: points.reduce((sum, point) => sum + point.y, 0) / points.length
  })
  const leftEyeCenter = averagePoint(leftEye)
  const rightEyeCenter = averagePoint(rightEye)
  const noseCenter = averagePoint(nose)
  const mouthCenter = averagePoint(mouth)
  const eyeMidX = (leftEyeCenter.x + rightEyeCenter.x) / 2
  const eyeDistance = Math.abs(rightEyeCenter.x - leftEyeCenter.x)
  if (!eyeDistance || Math.abs(noseCenter.x - eyeMidX) / eyeDistance > 0.32) {
    return { valid: false, message: 'Hãy nhìn thẳng vào camera, không quay nghiêng khuôn mặt' }
  }
  if (mouthCenter.y <= noseCenter.y || mouthCenter.y - noseCenter.y < box.height * 0.08) {
    return { valid: false, message: 'Vùng mũi hoặc miệng đang bị che — vui lòng tháo khẩu trang' }
  }

  const canvas = document.createElement('canvas')
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  const context = canvas.getContext('2d', { willReadFrequently: true })
  context.drawImage(video, 0, 0, canvas.width, canvas.height)
  const sampleX = Math.max(0, Math.round(box.x + box.width * 0.23))
  const sampleY = Math.max(0, Math.round(noseCenter.y - box.height * 0.02))
  const sampleWidth = Math.min(canvas.width - sampleX, Math.max(1, Math.round(box.width * 0.54)))
  const sampleHeight = Math.min(canvas.height - sampleY, Math.max(1, Math.round(box.height * 0.34)))
  const pixels = context.getImageData(sampleX, sampleY, sampleWidth, sampleHeight).data
  let skinPixels = 0
  let visiblePixels = 0
  let brightnessTotal = 0
  for (let index = 0; index < pixels.length; index += 16) {
    const red = pixels[index]
    const green = pixels[index + 1]
    const blue = pixels[index + 2]
    const max = Math.max(red, green, blue)
    const min = Math.min(red, green, blue)
    const cb = 128 - 0.169 * red - 0.331 * green + 0.5 * blue
    const cr = 128 + 0.5 * red - 0.419 * green - 0.081 * blue
    brightnessTotal += 0.299 * red + 0.587 * green + 0.114 * blue
    visiblePixels++
    if (red > 35 && max - min > 10 && cb >= 70 && cb <= 140 && cr >= 115 && cr <= 185) skinPixels++
  }
  const brightness = visiblePixels ? brightnessTotal / visiblePixels : 0
  const skinRatio = visiblePixels ? skinPixels / visiblePixels : 0
  if (brightness < 38) return { valid: false, message: 'Khuôn mặt đang quá tối — hãy tăng ánh sáng phía trước' }
  if (skinRatio < 0.12) {
    return { valid: false, message: 'Phát hiện vùng mũi/miệng bị che. Hãy tháo khẩu trang và mọi vật che mặt' }
  }
  return { valid: true, message: 'Khuôn mặt rõ, không bị che và đủ điều kiện xác thực' }
}

async function detectFaceDescriptor() {
  if (
    !modelsReady.value ||
    !faceapi ||
    !videoRef.value ||
    videoRef.value.readyState < 2 ||
    isDetectingFace ||
    document.hidden
  ) return null

  isDetectingFace = true
  scanState.value = 'scanning'
  faceDetected.value = false
  detectionMessage.value = 'Đang kiểm tra khuôn mặt trong camera...'
  try {
    let detections = await faceapi
      .detectAllFaces(videoRef.value, new faceapi.TinyFaceDetectorOptions({
        inputSize: 160,
        scoreThreshold: 0.42
      }))
      .withFaceLandmarks(true)
      .withFaceDescriptors()

    // Camera tối hoặc hơi mờ: tự quét lại nhạy hơn, chỉ chạy khi lượt nhẹ không thấy mặt.
    if (detections.length === 0) {
      scanState.value = 'scanning'
      detectionMessage.value = 'Đang quét lại khuôn mặt ở chế độ nhạy...'
      detections = await faceapi
        .detectAllFaces(videoRef.value, new faceapi.TinyFaceDetectorOptions({
          inputSize: 224,
          scoreThreshold: 0.28
        }))
        .withFaceLandmarks(true)
        .withFaceDescriptors()
    }

    faceDetected.value = detections.length === 1

    if (detections.length === 0) {
      scanState.value = 'no-face'
      detectionMessage.value = 'Chưa phát hiện khuôn mặt — hãy nhìn thẳng vào camera'
      return null
    }
    if (detections.length > 1) {
      scanState.value = 'multiple'
      detectionMessage.value = 'Có nhiều khuôn mặt — chỉ một nhân viên đứng trước camera'
      return null
    }

    const visibility = checkFaceVisibility(detections[0])
    if (!visibility.valid) {
      scanState.value = 'occluded'
      faceDetected.value = false
      detectionMessage.value = visibility.message
      return null
    }

    scanState.value = 'detected'
    detectionMessage.value = visibility.message
    return Array.from(detections[0].descriptor)
  } catch (error) {
    scanState.value = 'error'
    faceDetected.value = false
    detectionMessage.value = 'Không thể kiểm tra khuôn mặt — vui lòng thử lại'
    throw error
  } finally {
    isDetectingFace = false
  }
}

async function registerCurrentFace() {
  if (isEnrollmentMode.value && !validateWorkAssignment()) {
    await swal.warning('Chưa thể hoàn tất', 'Vui lòng kiểm tra ca làm, ngày bắt đầu và các ngày làm việc trong tuần.')
    return
  }
  isRegisteringFace.value = true
  try {
    await pauseFaceMonitoring()
    if (!await ensureFaceModels()) return
    const descriptor = await detectFaceDescriptor()
    if (!descriptor) {
      swal.warning('Chưa thể đăng ký', detectionMessage.value)
      return
    }
    if (isEnrollmentMode.value) {
      await api.put(`/admin/cham-cong/nhan-vien/${enrollmentTarget.value.id}/lich-lam`, {
        ...workAssignment.value,
        thu_lam_viec: workAssignment.value.thu_lam_viec.map(Number),
        ngay_ket_thuc: workAssignment.value.ngay_ket_thuc || null
      }, { bypassOffline: true })
      await api.post(`/admin/cham-cong/nhan-vien/${enrollmentTarget.value.id}/dang-ky-khuon-mat`, {
        face_descriptor: descriptor
      }, { bypassOffline: true })
      enrollmentTarget.value.face_registered = true
      await fetchEmployees()
      await swal.success(
        'Hoàn tất thiết lập chấm công',
        `Đã lưu lịch làm việc và khuôn mặt cho ${enrollmentTarget.value.name}. Nhân viên có thể chấm công theo lịch vừa gán.`
      )
    } else {
      await api.post('/cham-cong/register-face', { face_descriptor: descriptor })
      await fetchStatus()
      swal.success('Đăng ký thành công', 'Khuôn mặt đã được liên kết với hồ sơ nhân viên của bạn.')
    }
  } catch (error) {
    applyServerValidationErrors(error)
    const validationMessage = error.response?.data?.errors
      ? Object.values(error.response.data.errors).flat()[0]
      : null
    await swal.error('Thiết lập chưa thành công', validationMessage || error.response?.data?.message || 'Không thể lưu lịch làm việc hoặc khuôn mặt.')
  } finally {
    isRegisteringFace.value = false
    if (faceapi?.nets?.tinyFaceDetector?.isLoaded) startFaceMonitoring()
  }
}

async function startCamera() {
  await nextTick()
  
  if (stream.value) {
    stream.value.getTracks().forEach(track => track.stop())
  }
  
  try {
    const constraints = {
      video: selectedDeviceId.value 
        ? { 
            deviceId: { ideal: selectedDeviceId.value },
            width: { ideal: 480 },
            height: { ideal: 360 }
          }
        : { 
            facingMode: 'user',
            width: { ideal: 480 },
            height: { ideal: 360 }
          }
    }
    
    stream.value = await navigator.mediaDevices.getUserMedia(constraints)
    
    if (videoRef.value) {
      videoRef.value.muted = true
      videoRef.value.srcObject = stream.value
      try {
        await videoRef.value.play()
        console.log('Camera stream started successfully!')
      } catch (playErr) {
        console.warn('Play video failed:', playErr)
      }
      isCameraActive.value = true
      await getCameraDevices()
    }
  } catch (error) {
    console.error('Lỗi mở camera:', error)
    swal.error('Lỗi Camera', 'Không thể truy cập camera. Vui lòng cấp quyền truy cập camera cho trình duyệt!')
  }
}

function stopCamera() {
  if (stream.value) {
    stream.value.getTracks().forEach(track => track.stop())
    stream.value = null
  }
  isCameraActive.value = false
}

// Chụp ảnh webcam dạng base64
function captureWebcamImage() {
  if (!videoRef.value) return null
  const canvas = document.createElement('canvas')
  // Ảnh minh chứng 480px đủ rõ và nhẹ hơn nhiều so với khung camera gốc.
  const sourceWidth = videoRef.value.videoWidth || 640
  const sourceHeight = videoRef.value.videoHeight || 480
  canvas.width = Math.min(480, sourceWidth)
  canvas.height = Math.round(canvas.width * (sourceHeight / sourceWidth))
  const ctx = canvas.getContext('2d')
  
  // Lật gương ảnh chụp cho giống ảnh preview trên màn hình
  ctx.translate(canvas.width, 0)
  ctx.scale(-1, 1)
  
  ctx.drawImage(videoRef.value, 0, 0, canvas.width, canvas.height)
  return canvas.toDataURL('image/jpeg', 0.72)
}

// Thực hiện chấm công (Check-in / Check-out)
async function handleCheckInOut() {
  if (isProcessing.value) return
  isProcessing.value = true
  try {
    await pauseFaceMonitoring()
    if (!await ensureFaceModels()) return
    const faceDescriptor = await detectFaceDescriptor()
    if (!faceDescriptor) {
      swal.warning('Không thể chấm công', detectionMessage.value)
      return
    }

    if (!userStatus.value.face_registered) {
      swal.warning('Chưa đăng ký khuôn mặt', 'Vui lòng đăng ký khuôn mặt trước khi chấm công.')
      return
    }

    const base64Image = captureWebcamImage()

    if (!base64Image) {
      swal.error('Lỗi chụp ảnh', 'Có lỗi xảy ra khi chụp ảnh chấm công. Vui lòng thử lại!')
      isProcessing.value = false
      return
    }

    const res = await api.post('/cham-cong/check', {
      image: base64Image,
      face_descriptor: faceDescriptor
    }, { bypassOffline: true })

    if (res.data.success) {
      const typeText = res.data.type === 'checkin' ? 'Check-in' : 'Check-out'
      await swal.success(
        'Chấm công thành công',
        `${typeText} lúc ${formatTime(res.data.record.gio_vao || res.data.record.gio_ra)} thành công!${res.data.warning ? ` ${res.data.warning}` : ''}`
      )
      await fetchStatus()
      await fetchMyHistory()
      await fetchLeaderboard()
    }
  } catch (error) {
    console.error('Lỗi chấm công:', error)
    swal.error('Chấm công thất bại', error.response?.data?.message || 'Có lỗi xảy ra khi chấm công. Vui lòng thử lại!')
  } finally {
    isProcessing.value = false
    if (faceapi?.nets?.tinyFaceDetector?.isLoaded) startFaceMonitoring()
  }
}

// === LIFECYCLE ===
onMounted(async () => {
  // Các tác vụ độc lập chạy song song để không giữ màn hình loading.
  await Promise.allSettled([
    fetchEnrollmentTarget(),
    fetchRoles(),
    fetchEmployees(),
    fetchStatus(),
    fetchMyHistory(),
    fetchLeaderboard(),
    getCameraDevices(),
    initFaceApi()
  ])
})

onUnmounted(() => {
  if (faceScanTimer) window.clearInterval(faceScanTimer)
  stopCamera()
})
</script>

<template>
  <div class="attendance-page">
    <section v-if="isEnrollmentMode" class="enrollment-banner">
      <div class="enrollment-icon">◎</div>
      <div>
        <span>CHẾ ĐỘ QUẢN TRỊ HỒ SƠ KHUÔN MẶT</span>
        <strong>Đăng ký cho {{ displayEmployee.name }}</strong>
        <p>{{ displayEmployee.role_name || 'Nhân viên' }} · {{ displayEmployee.email }}</p>
      </div>
      <button type="button" @click="router.push({ name: 'admin-quanly-chamcong' })">Quay lại quản lý</button>
    </section>

    <section v-if="!isEnrollmentMode" class="verification-intro">
      <div>
        <span>QUẢN TRỊ SINH TRẮC HỌC</span>
        <strong>Đăng ký và cập nhật khuôn mặt nhân viên</strong>
        <p>Chọn đúng hồ sơ nhân viên trước khi ghi nhận khuôn mặt. Dữ liệu này sẽ được dùng tại popup chấm công nhanh.</p>
      </div>
      <button type="button" @click="router.push({ name: 'admin-quanly-chamcong' })">Chọn hồ sơ nhân viên</button>
    </section>

    <div class="dashboard-grid verification-grid">
      
      <!-- CỘT BÊN TRÁI: CAMERA VÀ THÔNG SỐ CHẤM CÔNG -->
      <div class="chamcong-card camera-card">
        <div class="card-header">
          <h3 class="card-title">Xác thực khuôn mặt nhân viên</h3>
          <span class="badge badge-success">Đăng ký sinh trắc học</span>
        </div>

        <!-- Khung Camera Glassmorphic -->
        <div class="camera-wrapper">
          <div v-if="isModelsLoading" class="camera-loader">
            <div class="spinner"></div>
            <p>Đang tải mô hình học máy...</p>
          </div>
          
          <div class="video-container">
            <video ref="videoRef" autoplay muted playsinline class="webcam-video"></video>
            <div class="face-detection-status" :class="{
              valid: scanState === 'detected',
              invalid: ['no-face', 'multiple', 'occluded', 'error'].includes(scanState),
              scanning: scanState === 'scanning'
            }">
              <span class="detection-dot"></span>
              {{ detectionMessage }}
            </div>
            
            <!-- Khung quét định vị khuôn mặt công nghệ (CSS tĩnh, siêu nhẹ, không sập GPU) -->
            <div v-if="isCameraActive" class="tech-corners-overlay">
              <span class="corner top-left"></span>
              <span class="corner top-right"></span>
              <span class="corner bottom-left"></span>
              <span class="corner bottom-right"></span>
              <div v-if="!isProcessing" class="scan-bar"></div>
            </div>
            
            <div v-if="!isCameraActive" class="camera-error">
              <div class="camera-error-icon-wrapper">
                <svg class="camera-error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                  <circle cx="12" cy="13" r="4"/>
                </svg>
              </div>
              <p class="camera-error-text">Camera đang tắt hoặc chưa được cấp quyền</p>
              <button @click="startCamera" class="btn-restart-camera" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                  <circle cx="12" cy="13" r="4"/>
                </svg>
                Bật lại Camera
              </button>
            </div>
            
            <div v-if="isProcessing" class="processing-overlay">
              <div class="spinner-pulse"></div>
              <p>Đang xử lý ảnh...</p>
            </div>
          </div>
        </div>

        <!-- Khung hành động -->
        <div class="camera-actions">
          <div class="identified-employee">
            <img :src="getAvatarUrl(displayEmployee.avatar, displayEmployee.name)" alt="Nhân viên" />
            <div>
              <span>{{ isEnrollmentMode ? 'Hồ sơ nhân viên đang được đăng ký' : 'Hồ sơ nhân viên cần xác thực' }}</span>
              <strong>{{ displayEmployee.name }}</strong>
              <small>{{ displayEmployee.email }} · {{ displayEmployee.role_name || displayEmployee.role || 'Nhân viên' }}</small>
            </div>
            <span class="registration-state" :class="{ registered: displayFaceRegistered }">
              {{ displayFaceRegistered ? 'Đã đăng ký khuôn mặt' : 'Chưa đăng ký khuôn mặt' }}
            </span>
          </div>

          <!-- Dropdown chọn Camera -->
          <div v-if="videoDevices.length > 1" class="camera-select-container">
            <label for="camera-select" class="camera-select-label">Chọn Camera:</label>
            <select id="camera-select" :value="selectedDeviceId" @change="onCameraChange" class="form-select">
              <option v-for="device in videoDevices" :key="device.deviceId" :value="device.deviceId">
                {{ device.label || `Camera ${videoDevices.indexOf(device) + 1}` }}
              </option>
            </select>
          </div>

          <div v-if="!verificationOnly" class="action-check-box">
            <div class="check-actions-grid">
              <button 
                @click="handleCheckInOut" 
                class="btn btn-lg btn-block"
                :class="userStatus.checked_out ? 'btn-success' : (userStatus.checked_in ? 'btn-warning' : 'btn-primary')"
                :disabled="isProcessing || !isCameraActive || !userStatus.face_registered || userStatus.checked_out"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                {{
                  isProcessing
                    ? 'Đang xác thực khuôn mặt...'
                    : userStatus.checked_out
                      ? 'Đã hoàn thành chấm công hôm nay'
                      : userStatus.checked_in
                        ? 'Chấm công ra'
                        : 'Chấm công vào'
                }}
              </button>
              <small v-if="userStatus.checked_out" class="attendance-done-note">
                Giờ vào {{ formatTime(userStatus.today_record?.gio_vao) }} ·
                Giờ ra {{ formatTime(userStatus.today_record?.gio_ra) }} ·
                Tổng {{ userStatus.today_record?.tong_gio || '0.00' }} giờ
              </small>
            </div>
          </div>
        </div>

        <!-- Khung Thông số lớn Premium -->
        <div v-if="!verificationOnly" class="chamcong-stats-grid">
          <div class="chamcong-stat-item">
            <span class="chamcong-stat-label">Giờ Vào</span>
            <span class="chamcong-stat-value text-blue">{{ formatTime(userStatus.today_record?.gio_vao) }}</span>
          </div>
          <div class="chamcong-stat-item">
            <span class="chamcong-stat-label">Giờ Ra</span>
            <span class="chamcong-stat-value text-purple">{{ formatTime(userStatus.today_record?.gio_ra) }}</span>
          </div>
          <div class="chamcong-stat-item" :class="{ 'alert-late': userStatus.today_record?.di_tre_phut > 0 }">
            <span class="chamcong-stat-label">Đi Trễ</span>
            <span class="chamcong-stat-value">{{ userStatus.today_record?.di_tre_phut || 0 }}p</span>
          </div>
          <div class="chamcong-stat-item">
            <span class="chamcong-stat-label">Tổng Giờ</span>
            <span class="chamcong-stat-value">{{ userStatus.today_record?.tong_gio || '0.00' }}h</span>
          </div>
          <div class="chamcong-stat-item text-center">
            <span class="chamcong-stat-label">Tổng Công</span>
            <span class="chamcong-stat-value highlight-gold">+{{ userStatus.today_record?.tong_cong || '0.00' }}</span>
          </div>
        </div>
      </div>

      <aside class="employee-setup-card">
        <div class="setup-heading">
          <span>THIẾT LẬP NHÂN VIÊN</span>
          <h3>{{ editingEmployee ? `Cập nhật ${editingEmployee.ten}` : 'Tạo hồ sơ và gán vai trò' }}</h3>
          <p>{{ editingEmployee
            ? 'Toàn bộ thông tin nhân viên đang được hiển thị tại đây. Chỉnh sửa và lưu trực tiếp, không cần mở popup.'
            : 'Sau khi tạo, hồ sơ sẽ được chuyển ngay sang bước đăng ký khuôn mặt bên trái.' }}</p>
        </div>

        <div class="setup-stepper" aria-label="Tiến trình thiết lập nhân viên">
          <div v-for="step in setupSteps" :key="step.number" class="setup-step" :class="{ done: step.done }">
            <span>{{ step.done ? '✓' : step.number }}</span>
            <small>{{ step.label }}</small>
          </div>
        </div>

        <div v-if="isEnrollmentMode && !editingEmployee" class="existing-profile-notice">
          <div>
            <strong>Đang đăng ký khuôn mặt cho: {{ enrollmentTarget.ten }}</strong>
            <small>Thông tin bên dưới được lấy từ hồ sơ thật. Bạn có thể cập nhật lịch làm việc và khuôn mặt.</small>
          </div>
          <button type="button" @click="openEditEmployee(enrollmentTarget)">Sửa hồ sơ</button>
        </div>

        <form id="employee-unified-form" class="employee-setup-form" @submit.prevent="handleUnifiedEmployeeSubmit">
          <section class="profile-card" :class="{ 'section-readonly': isEnrollmentMode && !editingEmployee }" :inert="isEnrollmentMode && !editingEmployee">
            <div class="form-section-heading">
              <span>01</span>
              <div><strong>Tài khoản và quyền làm việc</strong><small>Thông tin dùng để đăng nhập và phân quyền nhân viên.</small></div>
            </div>
            <div class="profile-fields-grid">
          <label :class="{ invalid: employeeTouched.ten && employeeErrors.ten }">
            <span>Họ và tên nhân viên *</span>
            <input v-model.trim="employeeForm.ten" placeholder="Ví dụ: Nguyễn Văn An" autocomplete="off"
              @input="employeeTouched.ten && validateEmployeeField('ten')" @blur="validateEmployeeField('ten')" />
            <small v-if="employeeTouched.ten && employeeErrors.ten">{{ employeeErrors.ten }}</small>
          </label>
          <label :class="{ invalid: employeeTouched.email && employeeErrors.email }">
            <span>Email đăng nhập *</span>
            <input v-model.trim="employeeForm.email" type="email" placeholder="nhanvien@nextgen.vn" autocomplete="off"
              @input="employeeTouched.email && validateEmployeeField('email')" @blur="validateEmployeeField('email')" />
            <small v-if="employeeTouched.email && employeeErrors.email">{{ employeeErrors.email }}</small>
          </label>
          <label :class="{ invalid: employeeTouched.sodienthoai && employeeErrors.sodienthoai }">
            <span>Số điện thoại</span>
            <input v-model.trim="employeeForm.sodienthoai" inputmode="tel" placeholder="09xx xxx xxx" autocomplete="off"
              @input="employeeTouched.sodienthoai && validateEmployeeField('sodienthoai')" @blur="validateEmployeeField('sodienthoai')" />
            <small v-if="employeeTouched.sodienthoai && employeeErrors.sodienthoai">{{ employeeErrors.sodienthoai }}</small>
          </label>
          <label :class="{ invalid: employeeTouched.vaitro && employeeErrors.vaitro }">
            <span>Vai trò làm việc *</span>
            <select v-model="employeeForm.vaitro" @change="validateEmployeeField('vaitro')" @blur="validateEmployeeField('vaitro')">
              <option v-for="role in roles" :key="role.id_vaitro" :value="role.ma_vaitro">
                {{ role.ten_vaitro }}
              </option>
            </select>
            <small v-if="employeeTouched.vaitro && employeeErrors.vaitro">{{ employeeErrors.vaitro }}</small>
          </label>
          <label :class="{ invalid: employeeTouched.matkhau && employeeErrors.matkhau }">
            <span>{{ editingEmployee ? 'Mật khẩu mới (không bắt buộc)' : 'Mật khẩu ban đầu *' }}</span>
            <input v-model="employeeForm.matkhau" type="password"
              :placeholder="editingEmployee ? 'Để trống nếu không đổi mật khẩu' : 'Ít nhất 8 ký tự, gồm chữ và số'" autocomplete="new-password"
              @input="employeeTouched.matkhau && validateEmployeeField('matkhau')" @blur="validateEmployeeField('matkhau')" />
            <small v-if="employeeTouched.matkhau && employeeErrors.matkhau">{{ employeeErrors.matkhau }}</small>
          </label>
          <label v-if="editingEmployee">
            <span>Trạng thái</span>
            <select v-model="employeeForm.trangthai">
              <option value="active">Đang làm việc</option>
              <option value="locked">Khóa tài khoản</option>
            </select>
          </label>
            </div>
          </section>

          <section class="identity-card" :class="{ 'section-readonly': isEnrollmentMode && !editingEmployee }" :inert="isEnrollmentMode && !editingEmployee">
            <div class="identity-heading">
              <div>
                <div class="form-section-heading compact">
                  <span>02</span>
                  <div><strong>Thông tin căn cước công dân</strong><small>Dùng để xác minh hồ sơ nhân sự; ảnh được lưu trong vùng riêng tư của hệ thống.</small></div>
                </div>
              </div>
              <span class="identity-status">{{ editingEmployee?.co_anh_cccd_mat_truoc && editingEmployee?.co_anh_cccd_mat_sau ? 'Đã có hồ sơ' : 'Cần bổ sung' }}</span>
            </div>

            <div class="identity-info-grid">
              <label>
                <span>Số CCCD *</span>
                <input v-model.trim="employeeForm.so_cccd" inputmode="numeric" maxlength="12" placeholder="Nhập 12 chữ số" />
              </label>
              <label>
                <span>Ngày sinh</span>
                <input v-model="employeeForm.ngaysinh" type="date" />
              </label>
              <label>
                <span>Giới tính</span>
                <select v-model="employeeForm.gioitinh">
                  <option value="">Chọn giới tính</option>
                  <option value="Nam">Nam</option>
                  <option value="Nữ">Nữ</option>
                  <option value="Khác">Khác</option>
                </select>
              </label>
              <label>
                <span>Ngày cấp</span>
                <input v-model="employeeForm.ngay_cap_cccd" type="date" />
              </label>
              <label class="identity-place-field">
                <span>Nơi cấp</span>
                <input v-model.trim="employeeForm.noi_cap_cccd" placeholder="Ví dụ: Cục Cảnh sát QLHC về TTXH" />
              </label>
            </div>

            <div class="identity-upload-grid">
              <label v-for="side in [{ key: 'anh_cccd_mat_truoc', label: 'Mặt trước CCCD' }, { key: 'anh_cccd_mat_sau', label: 'Mặt sau CCCD' }]" :key="side.key" class="identity-upload" :class="identitySideStatus[side.key].state">
                <input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" @change="selectIdentityImage(side.key, $event)" />
                <img v-if="identityPreviews[side.key]" :src="identityPreviews[side.key]" :alt="side.label" />
                <span v-else class="identity-upload-placeholder">
                  <b>＋ {{ side.label }}</b>
                  <small>{{ editingEmployee?.[side.key === 'anh_cccd_mat_truoc' ? 'co_anh_cccd_mat_truoc' : 'co_anh_cccd_mat_sau'] ? 'Đã lưu · bấm để thay ảnh' : 'Chụp hoặc chọn ảnh' }}</small>
                </span>
                <span v-if="identitySideStatus[side.key].state !== 'idle'" class="identity-side-result">
                  {{ identitySideStatus[side.key].state === 'checking' ? '◌' : (identitySideStatus[side.key].state === 'valid' ? '✓' : '!') }}
                  {{ identitySideStatus[side.key].message }}
                </span>
              </label>
            </div>
            <div class="identity-verify-row">
              <div class="identity-verification-state" :class="{ verified: identityVerified, failed: identityError }">
                <strong>{{ identityVerified ? 'CCCD đã được xác thực' : 'Chưa xác thực nội dung CCCD' }}</strong>
                <small v-if="identityVerificationMessage">{{ identityVerificationMessage }}</small>
                <small v-else>Hệ thống sẽ kiểm tra đúng hai mặt và tự điền thông tin vào biểu mẫu.</small>
              </div>
              <button type="button" class="identity-verify-button" :disabled="identityVerifying || !identityFiles.anh_cccd_mat_truoc || !identityFiles.anh_cccd_mat_sau || identitySideStatus.anh_cccd_mat_truoc.state !== 'valid' || identitySideStatus.anh_cccd_mat_sau.state !== 'valid'" @click="verifyIdentityImages">
                {{ identityVerifying ? `Đang đọc ảnh ${identityProgress}%` : (identityVerified ? 'Xác thực lại' : 'Xác thực & đọc CCCD') }}
              </button>
            </div>
            <p v-if="identityError" class="identity-error">{{ identityError }}</p>
          </section>

          <div class="work-assignment-block">
            <div class="assignment-heading">
              <div class="form-section-heading compact">
                <span>03</span>
                <div><strong>Lịch làm việc và chấm công</strong><small>Áp dụng cho việc chấm công và tính công của nhân viên.</small></div>
              </div>
            </div>
            <label>
              <span>Ca làm việc *</span>
              <select v-model="workAssignment.loai_ca" required>
                <option value="full_day">Cả ngày (ca sáng + ca chiều)</option>
                <option value="morning">Ca sáng</option>
                <option value="afternoon">Ca chiều</option>
              </select>
            </label>
            <div class="assignment-date-grid">
              <label class="assignment-date-field" :class="{ invalid: workAssignmentTouched.ngay_bat_dau && workAssignmentErrors.ngay_bat_dau }">
                <span>Ngày bắt đầu *</span>
                <input v-model="workAssignment.ngay_bat_dau" type="date"
                  @change="onStartDateChanged" @blur="validateWorkAssignmentField('ngay_bat_dau')" />
                <small v-if="workAssignmentTouched.ngay_bat_dau && workAssignmentErrors.ngay_bat_dau" class="assignment-field-feedback">
                  {{ workAssignmentErrors.ngay_bat_dau }}
                </small>
                <small v-else class="assignment-field-feedback feedback-placeholder" aria-hidden="true">&nbsp;</small>
              </label>
              <label class="assignment-date-field" :class="{ invalid: workAssignmentTouched.ngay_ket_thuc && workAssignmentErrors.ngay_ket_thuc }">
                <span>Ngày kết thúc</span>
                <input v-model="workAssignment.ngay_ket_thuc" type="date" :min="workAssignment.ngay_bat_dau"
                  @change="validateWorkAssignmentField('ngay_ket_thuc')" @blur="validateWorkAssignmentField('ngay_ket_thuc')" />
                <small v-if="workAssignmentTouched.ngay_ket_thuc && workAssignmentErrors.ngay_ket_thuc" class="assignment-field-feedback">
                  {{ workAssignmentErrors.ngay_ket_thuc }}
                </small>
                <small v-else class="optional-note assignment-field-feedback">Để trống nếu chưa xác định</small>
              </label>
            </div>
            <div class="weekday-field">
              <span>Ngày làm trong tuần *</span>
              <div class="weekday-options">
                <label v-for="day in weekdays" :key="day.value" :class="{ selected: workAssignment.thu_lam_viec.includes(day.value) }">
                  <input v-model="workAssignment.thu_lam_viec" type="checkbox" :value="day.value"
                    @change="validateWorkAssignmentField('thu_lam_viec')" />
                  {{ day.label }}
                </label>
              </div>
              <small v-if="workAssignmentTouched.thu_lam_viec && workAssignmentErrors.thu_lam_viec" class="assignment-error">
                {{ workAssignmentErrors.thu_lam_viec }}
              </small>
            </div>
          </div>

          <p v-if="employeeFormError" class="setup-error">{{ employeeFormError }}</p>

        </form>

        <div class="setup-links">
          <button type="button" @click="router.push({ name: 'admin-roles' })">Quản lý vai trò & quyền</button>
          <button type="button" @click="router.push('/admin/quan-ly-nguoi-dung')">Danh sách người dùng</button>
        </div>
      </aside>

      <div class="unified-submit-area">
        <div>
          <strong>{{ editingEmployee ? 'Cập nhật thông tin nhân viên' : (isEnrollmentMode ? 'Cập nhật sinh trắc học nhân viên' : 'Hoàn tất thiết lập nhân viên') }}</strong>
          <span>
            {{ editingEmployee
              ? `Các thay đổi của ${editingEmployee.ten} sẽ được lưu trực tiếp vào hồ sơ.`
              : isEnrollmentMode
              ? `Lịch làm việc và khuôn mặt sẽ được cập nhật cho ${displayEmployee.name}.`
              : 'Hệ thống sẽ kiểm tra dữ liệu, tạo hồ sơ, gán vai trò và lưu khuôn mặt trong một lần.' }}
          </span>
        </div>
        <button v-if="editingEmployee" type="button" class="cancel-edit-button" @click="cancelEditEmployee">Hủy sửa</button>
        <button
          type="submit"
          form="employee-unified-form"
          class="create-employee-button"
          :disabled="creatingEmployee || savingEmployee || isRegisteringFace || (isEnrollmentMode && !enrollmentTarget) || (!editingEmployee && !isCameraActive)"
        >
          {{ savingEmployee
            ? 'Đang lưu thay đổi...'
            : creatingEmployee || isRegisteringFace
            ? 'Đang xác thực và lưu dữ liệu...'
            : editingEmployee
              ? 'Lưu thay đổi'
              : (isEnrollmentMode ? 'Lưu lịch & cập nhật khuôn mặt' : 'Tạo nhân viên & đăng ký khuôn mặt') }}
        </button>
      </div>

      <!-- CỘT BÊN PHẢI: XẾP HẠNG VÀ LỊCH LÀM VIỆC -->
      <div v-if="!verificationOnly" class="attendance-side-column">
        <div class="chamcong-card leaderboard-card">
          <div class="card-header">
            <div>
              <h3 class="card-title">Xếp hạng Chấm công</h3>
              <p class="card-subtitle">Thống kê tích lũy công trong tháng hiện tại</p>
            </div>
            <div v-if="myRank > 0" class="my-rank-badge">
              <span class="rank-title">Hạng của bạn</span>
              <span class="rank-number">{{ myRank }}/{{ totalUsersCount }}</span>
            </div>
          </div>

          <div class="leaderboard-body">
            <div v-if="leaderboardList.length === 0" class="empty-state">
              Chưa có dữ liệu xếp hạng trong tháng này.
            </div>
            <div v-else class="leaderboard-list">
              <div
                v-for="(item, index) in leaderboardList"
                :key="item.id"
                class="leaderboard-item"
                :class="{ 'is-me': item.id === currentUser?.id || myRank === index + 1 }"
              >
                <div class="item-rank">
                  <span v-if="index === 0" class="medal medal-gold">🥇</span>
                  <span v-else-if="index === 1" class="medal medal-silver">🥈</span>
                  <span v-else-if="index === 2" class="medal medal-bronze">🥉</span>
                  <span v-else class="rank-text">{{ index + 1 }}</span>
                </div>

                <img :src="getAvatarUrl(item.anhdaidien, item.ten)" class="item-avatar" alt="Avatar" />
                <div class="item-info">
                  <span class="item-name">{{ item.ten }}</span>
                  <span class="item-role">{{ item.vaitro === 'admin' ? 'Quản trị' : 'Nhân viên' }}</span>
                </div>

                <div class="item-stats text-right">
                  <span class="item-cong">+{{ item.total_cong }} công</span>
                  <span class="item-hours">{{ item.total_gio }} giờ làm</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <section class="attendance-today">
          <div class="today-schedule">
            <div><span>Ca sáng</span><strong>08:00 – 12:00</strong></div>
            <div><span>Nghỉ trưa</span><strong>12:00 – 13:30</strong></div>
            <div><span>Ca chiều</span><strong>13:30 – 17:30</strong></div>
          </div>
          <div class="next-action" :class="{ completed: userStatus.checked_out }">
            <span>Trạng thái hôm nay</span>
            <strong>{{ nextAttendanceAction }}</strong>
            <p>Camera sẽ chụp ảnh làm minh chứng cho mỗi lượt.</p>
          </div>
        </section>
      </div>
    </div>

    <section class="employee-directory">
      <div class="directory-heading">
        <div>
          <span>QUẢN LÝ NHÂN SỰ</span>
          <h3>Danh sách nhân viên</h3>
          <p>Sửa hồ sơ, đổi vai trò hoặc quản lý dữ liệu khuôn mặt tại một nơi.</p>
        </div>
        <div class="employee-search" role="search">
          <svg class="employee-search-icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="6.5" />
            <path d="m16 16 4 4" />
          </svg>
          <input
            v-model="employeeSearch"
            type="search"
            aria-label="Tìm kiếm nhân viên"
            placeholder="Tìm theo tên, email, số điện thoại..."
          />
          <button
            v-if="employeeSearch"
            type="button"
            class="employee-search-clear"
            aria-label="Xóa nội dung tìm kiếm"
            @click="employeeSearch = ''"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="m7 7 10 10M17 7 7 17" />
            </svg>
          </button>
        </div>
      </div>

      <div v-if="employeesLoading" class="directory-empty">Đang tải danh sách nhân viên...</div>
      <div v-else-if="filteredEmployees.length === 0" class="directory-empty">Không tìm thấy nhân viên phù hợp.</div>
      <div v-else class="employee-table-wrap">
        <table class="employee-table">
          <thead>
            <tr>
              <th>Nhân viên</th>
              <th>Liên hệ</th>
              <th>Vai trò</th>
              <th>Khuôn mặt</th>
              <th>Lịch làm</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="employee in filteredEmployees" :key="employee.id">
              <td>
                <div class="employee-identity">
                  <img :src="getAvatarUrl(employee.anhdaidien, employee.ten)" alt="" />
                  <div><strong>{{ employee.ten }}</strong><small>Mã NV: #{{ employee.id }}</small></div>
                </div>
              </td>
              <td><strong>{{ employee.email }}</strong><small>{{ employee.sodienthoai || 'Chưa cập nhật SĐT' }}</small></td>
              <td><span class="role-pill">{{ employee.ten_vaitro || employee.vaitro }}</span></td>
              <td>
                <span class="face-pill" :class="{ registered: employee.face_registered }">
                  {{ employee.face_registered ? 'Đã đăng ký' : 'Chưa đăng ký' }}
                </span>
              </td>
              <td>
                <span class="face-pill" :class="{ registered: employee.schedule_registered }">
                  {{ employee.schedule_registered ? 'Đã gán lịch' : 'Chưa gán lịch' }}
                </span>
              </td>
              <td><span class="status-pill" :class="{ locked: employee.trangthai !== 'active' }">{{ employee.trangthai === 'active' ? 'Đang làm việc' : 'Đã khóa' }}</span></td>
              <td>
                <div class="employee-actions">
                  <button type="button" class="action-face" @click="selectEmployeeForFace(employee)">
                    {{ employee.face_registered ? 'Cập nhật mặt' : 'Đăng ký mặt' }}
                  </button>
                  <button type="button" class="action-edit" @click="openEditEmployee(employee)">Sửa</button>
                  <button v-if="employee.face_registered" type="button" class="action-neutral" @click="removeEmployeeFace(employee)">Gỡ mặt</button>
                  <button type="button" class="action-delete" :disabled="Number(employee.id) === Number(currentUser?.id)" @click="deleteEmployee(employee)">Xóa</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- KHU VỰC BÊN DƯỚI: LỊCH SỬ CHẤM CÔNG CÁ NHÂN -->
    <div v-if="!verificationOnly" class="chamcong-card history-card">
      <div class="card-header">
        <h3 class="card-title">Lịch sử chấm công của bạn</h3>
        <p class="card-subtitle">Chi tiết lịch sử các ngày chấm công trong tháng</p>
      </div>

      <div class="history-body">
        <div v-if="myHistory.length === 0" class="empty-state">
          Bạn chưa thực hiện lượt chấm công nào trong tháng này.
        </div>
        <div v-else class="table-container">
          <table class="history-table">
            <thead>
              <tr>
                <th>Ngày</th>
                <th>Giờ Vào</th>
                <th>Ảnh Check-in</th>
                <th>Giờ Ra</th>
                <th>Ảnh Check-out</th>
                <th>Đi Trễ</th>
                <th>Tổng Giờ</th>
                <th>Công Nhận</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in myHistory" :key="log.id">
                <td class="font-bold">{{ formatDate(log.ngay_cham_cong) }}</td>
                <td class="text-blue">{{ formatTime(log.gio_vao) }}</td>
                <td>
                  <img v-if="log.anh_vao" :src="storageUrl(log.anh_vao)" class="history-thumb" alt="Checkin" @click="swal.image(storageUrl(log.anh_vao), 'Ảnh Check-in')" />
                  <span v-else class="text-gray">Chưa chụp</span>
                </td>
                <td class="text-purple">{{ formatTime(log.gio_ra) }}</td>
                <td>
                  <img v-if="log.anh_ra" :src="storageUrl(log.anh_ra)" class="history-thumb" alt="Checkout" @click="swal.image(storageUrl(log.anh_ra), 'Ảnh Check-out')" />
                  <span v-else class="text-gray">Chưa chụp</span>
                </td>
                <td>
                  <span v-if="log.di_tre_phut > 0" class="badge-danger">Trễ {{ log.di_tre_phut }} phút</span>
                  <span v-else class="badge-success-outline">Đúng giờ</span>
                </td>
                <td>{{ log.tong_gio }}h</td>
                <td>
                  <span class="badge-gold">+{{ log.tong_cong }} công</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.attendance-page {
  padding: 0;
  background: #f8fafc;
  min-height: 100%;
  font-family: Inter, sans-serif;
  color: #1e293b;
  display: flex;
  flex-direction: column;
  gap: 11px;
  max-width: 1240px;
  margin: 0 auto;
  width: 100%;
}

.attendance-today,
.dashboard-grid,
.history-card,
.enrollment-banner {
  max-width: 1240px;
  margin-left: auto;
  margin-right: auto;
}

.enrollment-banner {
  display: grid;
  grid-template-columns: 58px minmax(0, 1fr) auto;
  align-items: center;
  gap: 20px;
  width: 100%;
  min-height: 104px;
  padding: 20px 24px;
  box-sizing: border-box;
  border: 1px solid #bfdbfe;
  border-radius: 16px;
  background: linear-gradient(135deg, #eff6ff, #f8fafc);
  box-shadow: 0 10px 30px rgba(37, 99, 235, .08);
}

.enrollment-icon {
  width: 58px;
  height: 58px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #2563eb;
  color: #fff;
  font-size: 28px;
  font-weight: 800;
}

.enrollment-banner span { color: #2563eb; font-size: 12px; font-weight: 800; letter-spacing: .08em; }
.enrollment-banner strong { display: block; color: #0f172a; font-size: 22px; margin-top: 4px; }
.enrollment-banner p { margin: 4px 0 0; color: #64748b; font-size: 13px; }
.enrollment-banner button {
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  background: #fff;
  color: #1d4ed8;
  min-height: 48px;
  padding: 11px 20px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.verification-intro {
  width: 100%;
  max-width: 1180px;
  margin: 0 auto;
  padding: 10px 14px;
  border: 1px solid #bfdbfe;
  border-radius: 14px;
  background: linear-gradient(135deg, #eff6ff, #f8fafc);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}
.verification-intro span { color: #2563eb; font-size: 10px; font-weight: 800; letter-spacing: .08em; }
.verification-intro strong { display: block; margin-top: 2px; color: #0f172a; font-size: 15px; }
.verification-intro p { margin: 3px 0 0; color: #64748b; font-size: 11px; }
.verification-intro button {
  flex: 0 0 auto;
  padding: 9px 12px;
  border: 0;
  border-radius: 9px;
  background: #2563eb;
  color: #fff;
  font-size: 11px;
  font-weight: 750;
  cursor: pointer;
}

.attendance-page:has(.enrollment-banner) .dashboard-grid:not(.verification-grid) {
  grid-template-columns: minmax(0, 760px);
  justify-content: center;
}

.attendance-today {
  display: grid;
  grid-template-columns: 1fr minmax(280px, 0.9fr);
  gap: 10px;
  padding: 10px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
}

.today-clock,
.next-action {
  padding: 12px 14px;
  border-radius: 11px;
  background: #eff6ff;
  border: 1px solid #dbeafe;
  display: flex;
  flex-direction: column;
}

.today-clock span,
.next-action span,
.today-schedule span { color: #64748b; font-size: 11px; font-weight: 600; }
.today-clock strong { color: #1d4ed8; font-size: 24px; line-height: 1.2; margin-top: 3px; }
.today-clock p,
.next-action p { color: #64748b; font-size: 11.5px; margin: 3px 0 0; }

.today-schedule {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.today-schedule > div {
  padding: 9px 11px;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4px;
}

.today-schedule strong { color: #0f172a; font-size: 13px; white-space: nowrap; }
.next-action { justify-content: center; background: #f8fafc; border-color: #e2e8f0; }
.next-action strong { color: #2563eb; font-size: 13px; margin-top: 4px; }
.next-action.completed { background: #ecfdf5; border-color: #bbf7d0; }
.next-action.completed strong { color: #15803d; }

.dashboard-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.12fr) minmax(360px, 0.88fr);
  align-items: start;
  gap: 14px;
  width: 100%;
}

.dashboard-grid.verification-grid {
  grid-template-columns: minmax(360px, .78fr) minmax(560px, 1.22fr);
  justify-content: center;
  max-width: 1180px;
  gap: 0;
  padding: 14px;
  border: 1px solid #dbe3ef;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 10px 30px rgba(15, 23, 42, .07);
}
.verification-grid > .chamcong-card {
  align-self: start;
  position: sticky;
  top: 82px;
  padding: 0 16px 0 0;
  border: 0;
  border-right: 1px solid #e2e8f0;
  border-radius: 0;
  box-shadow: none;
}

.verification-grid > .camera-card .camera-wrapper {
  margin-top: 14px;
}

.verification-grid > .camera-card .camera-actions {
  margin-bottom: 0;
}

.employee-setup-card {
  align-self: start;
  padding: 0 0 0 16px;
  border: 0;
  border-radius: 0;
  background: #fff;
  box-shadow: none;
}
.setup-heading { padding-bottom: 13px; border-bottom: 1px solid #e2e8f0; }
.setup-heading > span { color: #2563eb; font-size: 10px; font-weight: 800; letter-spacing: .09em; }
.setup-heading h3 { margin: 4px 0 3px; color: #0f172a; font-size: 17px; }
.setup-heading p { margin: 0; color: #64748b; font-size: 11px; line-height: 1.5; }
.setup-stepper { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; margin-top: 11px; padding: 6px; border-radius: 12px; background: #f1f5f9; }
.setup-step { display: flex; align-items: center; min-width: 0; gap: 6px; padding: 7px 8px; border-radius: 9px; color: #64748b; }
.setup-step > span { flex: 0 0 auto; width: 21px; height: 21px; display: grid; place-items: center; border-radius: 50%; background: #e2e8f0; color: #475569; font-size: 9px; font-weight: 850; }
.setup-step small { overflow: hidden; color: inherit; font-size: 9px; font-weight: 750; white-space: nowrap; text-overflow: ellipsis; }
.setup-step.done { background: #ecfdf5; color: #047857; }
.setup-step.done > span { background: #10b981; color: #fff; }
.existing-profile-notice { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 10px; padding: 10px 12px; border: 1px solid #bbf7d0; border-radius: 11px; background: #f0fdf4; }
.existing-profile-notice strong, .existing-profile-notice small { display: block; }
.existing-profile-notice strong { color: #047857; font-size: 10.5px; }
.existing-profile-notice small { margin-top: 2px; color: #64748b; font-size: 9.5px; line-height: 1.35; }
.existing-profile-notice button { flex: 0 0 auto; min-height: 31px; padding: 6px 10px; border: 1px solid #86efac; border-radius: 8px; background: #fff; color: #047857; font-size: 9.5px; font-weight: 800; cursor: pointer; }
.section-readonly { position: relative; background: #f8fafc; }
.profile-card.section-readonly::after { content: 'Dữ liệu từ hồ sơ nhân viên'; position: absolute; right: 12px; top: 12px; padding: 4px 7px; border-radius: 999px; background: #e2e8f0; color: #475569; font-size: 8px; font-weight: 800; }
.section-readonly input, .section-readonly select, .section-readonly .identity-upload { background-color: #f8fafc; color: #334155; cursor: default; }
.employee-setup-form {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  align-items: start;
  gap: 9px 10px;
  margin-top: 11px;
}
.profile-card { grid-column: 1 / -1; display: grid; gap: 12px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; }
.profile-fields-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: start; gap: 9px 10px; }
.form-section-heading { display: flex; align-items: flex-start; gap: 9px; }
.form-section-heading > span { flex: 0 0 auto; width: 27px; height: 27px; display: grid; place-items: center; border-radius: 8px; background: #dbeafe; color: #1d4ed8; font-size: 9px; font-weight: 900; }
.form-section-heading strong, .form-section-heading small { display: block; }
.form-section-heading strong { color: #0f172a; font-size: 12px; }
.form-section-heading small { margin-top: 2px; color: #64748b; font-size: 9.5px; line-height: 1.35; }
.form-section-heading.compact > span { width: 25px; height: 25px; }
.employee-setup-form label { display: grid; gap: 5px; }
.employee-setup-form label > span { color: #475569; font-size: 10.5px; font-weight: 700; }
.employee-setup-form input,
.employee-setup-form select {
  width: 100%;
  height: 37px;
  padding: 0 11px;
  border: 1px solid #cbd5e1;
  border-radius: 9px;
  outline: 0;
  background: #fff;
  color: #0f172a;
  font-size: 12px;
  transition: border-color .18s, box-shadow .18s;
}
.employee-setup-form input:focus,
.employee-setup-form select:focus {
  border-color: #60a5fa;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
}
.employee-setup-form label.invalid input,
.employee-setup-form label.invalid select,
.edit-form-grid label.invalid input,
.edit-form-grid label.invalid select {
  border-color: #ef4444;
  background: #fffafa;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, .08);
}
.employee-setup-form label small,
.edit-form-grid label small {
  color: #dc2626;
  font-size: 9.5px;
  font-weight: 650;
  line-height: 1.3;
}
.setup-error {
  grid-column: 1 / -1;
  margin: 0;
  padding: 8px 10px;
  border-radius: 8px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 10.5px;
}
.identity-card {
  grid-column: 1 / -1;
  display: grid;
  gap: 12px;
  padding: 14px;
  border: 1px solid #c7d9f7;
  border-radius: 14px;
  background: #f8fbff;
}
.identity-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.identity-heading strong, .identity-heading small { display: block; }
.identity-heading strong { color: #1e3a8a; font-size: 13px; }
.identity-heading small { margin-top: 3px; color: #64748b; font-size: 10px; line-height: 1.4; }
.identity-status { flex: 0 0 auto; padding: 5px 8px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 9px; font-weight: 800; }
.identity-info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px 10px; }
.identity-place-field { grid-column: 1 / -1; }
.identity-upload-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.identity-upload { position: relative; min-height: 104px; overflow: hidden; border: 1px dashed #93b4e8; border-radius: 11px; background: #fff; cursor: pointer; }
.identity-upload > input { position: absolute; inset: 0; z-index: 2; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
.identity-upload > img { width: 100%; height: 120px; object-fit: cover; }
.identity-upload.checking { border-color: #f59e0b; }
.identity-upload.valid { border-style: solid; border-color: #10b981; box-shadow: 0 0 0 2px rgba(16, 185, 129, .1); }
.identity-upload.invalid { border-style: solid; border-color: #ef4444; box-shadow: 0 0 0 2px rgba(239, 68, 68, .1); }
.identity-side-result { position: absolute; z-index: 3; left: 7px; right: 7px; bottom: 7px; display: flex; align-items: center; gap: 5px; padding: 6px 8px; border-radius: 7px; background: rgba(15, 23, 42, .88); color: #fff !important; font-size: 8.5px !important; font-weight: 750 !important; line-height: 1.25; backdrop-filter: blur(6px); }
.identity-upload.valid .identity-side-result { background: rgba(4, 120, 87, .92); }
.identity-upload.invalid .identity-side-result { background: rgba(185, 28, 28, .94); }
.identity-upload-placeholder { min-height: 104px; display: grid; place-content: center; gap: 5px; padding: 12px; text-align: center; color: #2563eb !important; }
.identity-upload-placeholder b { font-size: 11px; }
.identity-upload-placeholder small { color: #64748b !important; font-size: 9.5px; }
.identity-error { margin: 0; color: #dc2626; font-size: 10px; font-weight: 650; }
.identity-verify-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 2px; }
.identity-verification-state { min-width: 0; }
.identity-verification-state strong, .identity-verification-state small { display: block; }
.identity-verification-state strong { color: #92400e; font-size: 10.5px; }
.identity-verification-state small { margin-top: 2px; color: #64748b; font-size: 9.5px; line-height: 1.35; }
.identity-verification-state.verified strong { color: #047857; }
.identity-verification-state.failed strong { color: #b91c1c; }
.identity-verify-button { flex: 0 0 auto; min-width: 166px; min-height: 38px; padding: 8px 13px; border: 0; border-radius: 9px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; font-size: 10.5px; font-weight: 800; cursor: pointer; box-shadow: 0 5px 13px rgba(37, 99, 235, .18); }
.identity-verify-button:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; }
@media (max-width: 640px) {
  .identity-info-grid, .identity-upload-grid { grid-template-columns: 1fr; }
  .profile-fields-grid { grid-template-columns: 1fr; }
  .identity-place-field { grid-column: auto; }
  .identity-verify-row { align-items: stretch; flex-direction: column; }
  .identity-verify-button { width: 100%; }
  .setup-stepper { grid-template-columns: 1fr; }
  .existing-profile-notice { align-items: stretch; flex-direction: column; }
  .existing-profile-notice button { width: 100%; }
}
.create-employee-button {
  min-height: 42px;
  border: 0;
  border-radius: 9px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
  font-size: 11.5px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 7px 16px rgba(37, 99, 235, .2);
}
.create-employee-button:disabled { opacity: .55; cursor: not-allowed; }
.unified-submit-area {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-top: 12px;
  position: sticky;
  bottom: 0;
  z-index: 12;
  padding: 11px 12px;
  border: 1px solid #dbe3ef;
  border-radius: 12px;
  background: rgba(255, 255, 255, .96);
  box-shadow: 0 -8px 24px rgba(15, 23, 42, .08);
  backdrop-filter: blur(12px);
}
.unified-submit-area > div { min-width: 0; }
.unified-submit-area strong,
.unified-submit-area span { display: block; }
.unified-submit-area strong { color: #0f172a; font-size: 12px; }
.unified-submit-area span { margin-top: 3px; color: #64748b; font-size: 10px; line-height: 1.4; }
.unified-submit-area .create-employee-button {
  flex: 0 0 auto;
  min-width: 280px;
  padding: 0 18px;
}
.cancel-edit-button {
  flex: 0 0 auto;
  min-height: 42px;
  padding: 0 16px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #fff;
  color: #475569;
  font-size: 11px;
  font-weight: 750;
  cursor: pointer;
}
.cancel-edit-button:hover { border-color: #94a3b8; background: #f8fafc; }
.setup-links {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 7px;
  margin-top: 10px;
}
.setup-links button {
  min-height: 34px;
  padding: 5px 8px;
  border: 1px solid #dbe3ef;
  border-radius: 8px;
  background: #f8fafc;
  color: #475569;
  font-size: 9.5px;
  font-weight: 700;
  cursor: pointer;
}

.employee-directory {
  width: 100%;
  max-width: 1180px;
  margin: 0 auto;
  overflow: hidden;
  border: 1px solid #dbe3ef;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
}

/* Tận dụng toàn bộ chiều ngang khi thanh quản trị được thu gọn. */
:global(.admin-layout.sidebar-collapsed) .attendance-page,
:global(.admin-layout.sidebar-collapsed) .attendance-today,
:global(.admin-layout.sidebar-collapsed) .dashboard-grid,
:global(.admin-layout.sidebar-collapsed) .dashboard-grid.verification-grid,
:global(.admin-layout.sidebar-collapsed) .history-card,
:global(.admin-layout.sidebar-collapsed) .enrollment-banner,
:global(.admin-layout.sidebar-collapsed) .verification-intro,
:global(.admin-layout.sidebar-collapsed) .employee-directory {
  max-width: none;
  width: 100%;
}

.directory-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 18px;
  border-bottom: 1px solid #e2e8f0;
}
.directory-heading > div > span { color: #2563eb; font-size: 9.5px; font-weight: 850; letter-spacing: .1em; }
.directory-heading h3 { margin: 3px 0 2px; color: #0f172a; font-size: 17px; }
.directory-heading p { margin: 0; color: #64748b; font-size: 10.5px; }
.employee-search {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  width: min(390px, 100%);
  height: 44px;
  padding: 0 13px;
  border: 1px solid #d7e0ec;
  border-radius: 13px;
  background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
  color: #64748b;
  box-shadow: 0 2px 8px rgba(15, 23, 42, .05), inset 0 1px 0 rgba(255, 255, 255, .9);
  transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.employee-search:hover {
  border-color: #b7c6dc;
  box-shadow: 0 4px 12px rgba(15, 23, 42, .07);
}
.employee-search:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, .12), 0 5px 16px rgba(37, 99, 235, .09);
  transform: translateY(-1px);
}
.employee-search-icon {
  flex: 0 0 auto;
  width: 18px;
  height: 18px;
  color: #64748b;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.9;
  stroke-linecap: round;
}
.employee-search:focus-within .employee-search-icon { color: #2563eb; }
.employee-search input {
  width: 100%;
  min-width: 0;
  height: 100%;
  padding: 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: #0f172a;
  font-size: 12.5px;
  font-weight: 500;
}
.employee-search input::placeholder { color: #94a3b8; font-weight: 400; }
.employee-search input::-webkit-search-cancel-button { display: none; }
.employee-search-clear {
  flex: 0 0 auto;
  width: 26px;
  height: 26px;
  display: grid;
  place-items: center;
  padding: 0;
  border: 0;
  border-radius: 8px;
  background: #eef2f7;
  color: #64748b;
  cursor: pointer;
  transition: background .18s ease, color .18s ease;
}
.employee-search-clear:hover { background: #dbeafe; color: #1d4ed8; }
.employee-search-clear svg {
  width: 14px;
  height: 14px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
}
.directory-empty { padding: 34px 16px; text-align: center; color: #64748b; font-size: 12px; }
.employee-table-wrap { width: 100%; overflow-x: auto; }
.employee-table { width: 100%; border-collapse: collapse; min-width: 940px; }
.employee-table th {
  padding: 10px 12px;
  background: #f8fafc;
  color: #475569;
  font-size: 9.5px;
  font-weight: 800;
  text-align: left;
  white-space: nowrap;
}
.employee-table td { padding: 11px 12px; border-top: 1px solid #eef2f7; color: #334155; font-size: 10.5px; vertical-align: middle; }
.employee-table td > strong,
.employee-table td > small { display: block; }
.employee-table td > small { margin-top: 3px; color: #94a3b8; }
.employee-identity { display: flex; align-items: center; gap: 9px; min-width: 170px; }
.employee-identity img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; background: #eff6ff; }
.employee-identity strong,
.employee-identity small { display: block; }
.employee-identity strong { color: #0f172a; font-size: 11px; }
.employee-identity small { margin-top: 2px; color: #94a3b8; font-size: 9px; }
.role-pill,
.face-pill,
.status-pill {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 4px 8px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
  font-size: 9px;
  font-weight: 750;
  white-space: nowrap;
}
.face-pill { background: #fff7ed; color: #c2410c; }
.face-pill.registered { background: #dcfce7; color: #15803d; }
.status-pill { background: #dcfce7; color: #15803d; }
.status-pill.locked { background: #fee2e2; color: #b91c1c; }
.employee-actions { display: flex; align-items: center; gap: 5px; white-space: nowrap; }
.employee-actions button {
  min-height: 28px;
  padding: 5px 8px;
  border: 1px solid transparent;
  border-radius: 7px;
  font-size: 8.5px;
  font-weight: 750;
  cursor: pointer;
}
.employee-actions button:disabled { opacity: .4; cursor: not-allowed; }
.action-face { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe !important; }
.action-edit { background: #f1f5f9; color: #334155; border-color: #dbe3ef !important; }
.action-neutral { background: #fff7ed; color: #c2410c; border-color: #fed7aa !important; }
.action-delete { background: #fef2f2; color: #dc2626; border-color: #fecaca !important; }

.attendance-side-column {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.attendance-side-column .attendance-today {
  width: 100%;
  max-width: none;
  margin: 0;
  grid-template-columns: 1fr;
}

@media (max-width: 1024px) {
  .attendance-today { grid-template-columns: 1fr; }
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
  .dashboard-grid.verification-grid { grid-template-columns: 1fr; }
  .verification-grid > .chamcong-card {
    position: static;
    padding: 0 0 16px;
    border-right: 0;
    border-bottom: 1px solid #e2e8f0;
  }
  .verification-grid > .camera-card .camera-wrapper { margin-top: 0; }
  .verification-grid > .camera-card .camera-actions { margin-bottom: 8px; }
  .employee-setup-card { padding: 16px 0 0; }
  .unified-submit-area { align-items: stretch; flex-direction: column; }
  .unified-submit-area .create-employee-button { width: 100%; min-width: 0; }
  .cancel-edit-button { width: 100%; }
}

@media (max-width: 620px) {
  .employee-setup-form { grid-template-columns: 1fr; }
  .employee-setup-form > * { grid-column: 1; }
  .today-schedule { grid-template-columns: 1fr; }
  .enrollment-banner { grid-template-columns: 44px 1fr; gap: 12px; min-height: 0; padding: 16px; }
  .enrollment-icon { width: 44px; height: 44px; font-size: 24px; }
  .enrollment-banner strong { font-size: 18px; }
  .enrollment-banner button { grid-column: 1 / -1; }
  .directory-heading { align-items: stretch; flex-direction: column; }
  .employee-search { width: 100%; }
}

/* === CARD GLASSMORPHIC === */
.chamcong-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.025);
  overflow: hidden;
  padding: 14px;
}

.camera-card {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.camera-card > .card-header {
  width: 100%;
}

.camera-card > .chamcong-stats-grid {
  width: 100%;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 8px;
}

.card-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: 0.5px;
}

.card-subtitle {
  font-size: 11.5px;
  color: #94a3b8;
  margin: 2px 0 0 0;
}

/* === CARD CAMERA === */
.camera-wrapper {
  width: 100%;
  background: #000000;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  position: relative;
  aspect-ratio: 4 / 3;
  max-width: 390px;
  margin: 0 auto 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.video-container {
  width: 100%;
  height: 100%;
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
}

.face-detection-status {
  position: absolute;
  left: 12px;
  right: 12px;
  bottom: 12px;
  z-index: 4;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 8px 10px;
  border-radius: 9px;
  color: #fff;
  background: rgba(15, 23, 42, 0.86);
  backdrop-filter: blur(8px);
  font-size: 11.5px;
  font-weight: 700;
}

.face-detection-status.valid { background: rgba(5, 150, 105, 0.9); }
.face-detection-status.invalid { background: rgba(185, 28, 28, 0.86); }
.face-detection-status.scanning { background: rgba(37, 99, 235, 0.9); }
.detection-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

.face-scan-notice {
  max-width: 420px;
  margin: 0 auto 12px;
  padding: 11px 13px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  border: 1px solid #cbd5e1;
  border-radius: 11px;
  background: #f8fafc;
}

.notice-symbol {
  width: 24px;
  height: 24px;
  flex: 0 0 24px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #e2e8f0;
  color: #475569;
  font-weight: 800;
}

.face-scan-notice strong { display: block; color: #0f172a; font-size: 12.5px; }
.face-scan-notice p { margin: 2px 0 0; color: #64748b; font-size: 11px; line-height: 1.45; }
.face-scan-notice.state-detected { background: #ecfdf5; border-color: #86efac; }
.face-scan-notice.state-detected .notice-symbol { background: #16a34a; color: #fff; }
.face-scan-notice.state-scanning { background: #eff6ff; border-color: #93c5fd; }
.face-scan-notice.state-scanning .notice-symbol { background: #2563eb; color: #fff; }
.face-scan-notice.state-no-face,
.face-scan-notice.state-multiple,
.face-scan-notice.state-error { background: #fef2f2; border-color: #fca5a5; }
.face-scan-notice.state-no-face .notice-symbol,
.face-scan-notice.state-multiple .notice-symbol,
.face-scan-notice.state-error .notice-symbol { background: #dc2626; color: #fff; }

.webcam-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transform: scaleX(-1);
  background: #000000;
}

.identified-employee {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  margin-bottom: 7px;
  border: 1px solid #dbeafe;
  border-radius: 11px;
  background: #f8fbff;
}

.identified-employee img {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #bfdbfe;
}

.identified-employee > div { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.identified-employee > div span { color: #64748b; font-size: 10.5px; }
.identified-employee > div strong { color: #0f172a; font-size: 13.5px; }
.identified-employee > div small { color: #64748b; font-size: 10.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.registration-state {
  padding: 5px 8px;
  border-radius: 999px;
  background: #fee2e2;
  color: #b91c1c;
  font-size: 10px;
  font-weight: 700;
  white-space: nowrap;
}
.registration-state.registered { background: #dcfce7; color: #15803d; }

.btn-register-face {
  width: 100%;
  min-height: 42px;
  margin-bottom: 10px;
  border: 0;
  border-radius: 10px;
  background: #7c3aed;
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}
.btn-register-face:disabled { opacity: 0.45; cursor: not-allowed; }

.tech-corners-overlay {
  position: absolute;
  top: 15%;
  left: 15%;
  width: 70%;
  height: 70%;
  pointer-events: none;
  z-index: 2;
  box-sizing: border-box;
}

.tech-corners-overlay .corner {
  position: absolute;
  width: 20px;
  height: 20px;
  border: 4px solid #00f2fe;
}

.tech-corners-overlay .top-left {
  top: 0;
  left: 0;
  border-right: none;
  border-bottom: none;
}

.tech-corners-overlay .top-right {
  top: 0;
  right: 0;
  border-left: none;
  border-bottom: none;
}

.tech-corners-overlay .bottom-left {
  bottom: 0;
  left: 0;
  border-right: none;
  border-top: none;
}

.tech-corners-overlay .bottom-right {
  bottom: 0;
  right: 0;
  border-left: none;
  border-top: none;
}

/* Quét neon chạy lên xuống tĩnh */
.scan-bar {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: linear-gradient(to right, transparent, #2563eb, #00f2fe, #2563eb, transparent);
  box-shadow: 0 0 8px #00f2fe;
  animation: scan 3s linear infinite;
  pointer-events: none;
}

@keyframes scan {
  0% { top: 0%; }
  50% { top: 100%; }
  100% { top: 0%; }
}

.camera-loader, .camera-error, .processing-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(15, 23, 42, 0.92);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  z-index: 10;
  color: #94a3b8;
  padding: 20px;
}

.camera-error-icon-wrapper {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
  margin-bottom: 2px;
}

.camera-error-icon {
  width: 38px;
  height: 38px;
  color: #cbd5e1;
}

.camera-error-text {
  font-size: 13.5px;
  color: #94a3b8;
  margin: 0;
  font-weight: 500;
  text-align: center;
}

.btn-restart-camera {
  background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
  color: #ffffff !important;
  padding: 10px 20px !important;
  font-size: 14px !important;
  font-weight: 600 !important;
  border-radius: 10px !important;
  border: none !important;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35) !important;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.btn-restart-camera:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5) !important;
  background: linear-gradient(135deg, #1d4ed8, #1e40af) !important;
}

.btn-restart-camera svg {
  width: 17px;
  height: 17px;
}

.processing-overlay {
  background: rgba(2, 6, 23, 0.6);
  color: #38bdf8;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-left-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.spinner-pulse {
  width: 50px;
  height: 50px;
  background-color: rgba(56, 189, 248, 0.2);
  border: 2px solid #38bdf8;
  border-radius: 50%;
  animation: pulse 1.2s ease-in-out infinite;
}

@keyframes spin {
  100% { transform: rotate(360deg); }
}

@keyframes pulse {
  0% { transform: scale(0.8); opacity: 0.5; }
  50% { transform: scale(1.2); opacity: 1; }
  100% { transform: scale(0.8); opacity: 0.5; }
}

/* === CAMERA ACTIONS === */
.camera-actions {
  width: 100%;
  max-width: 390px;
  margin: 0 auto 8px;
  text-align: center;
}

.action-register-guide {
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.2);
  padding: 16px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.action-register-guide p {
  font-size: 13.5px;
  color: #a7f3d0;
  margin: 0;
}

.action-check-box {
  margin-top: 12px;
}

.check-actions-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: center;
}

.btn-delete-face {
  background: transparent;
  border: none;
  color: #ef4444;
  font-size: 12px;
  cursor: pointer;
  text-decoration: underline;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.btn-delete-face:hover {
  opacity: 1;
}

.attendance-complete-msg {
  display: grid;
  grid-template-columns: 42px minmax(0, 1fr);
  align-items: start;
  gap: 12px;
  text-align: left;
  background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
  border: 1px solid #a7f3d0;
  padding: 14px;
  border-radius: 12px;
}

.check-actions-grid .btn {
  width: 100%;
  min-height: 48px;
}

.attendance-done-note {
  color: #64748b;
  font-size: 11px;
  line-height: 1.5;
}

.attendance-complete-msg h4 {
  margin: 0;
  color: #065f46;
  font-size: 14px;
}

.attendance-complete-msg p {
  margin: 2px 0 0;
  font-size: 11px;
  color: #64748b;
}

.success-icon {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #10b981;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: bold;
}

.complete-content { min-width: 0; }
.complete-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.complete-badge {
  flex: 0 0 auto;
  padding: 4px 8px;
  border-radius: 999px;
  background: #d1fae5;
  color: #047857;
  font-size: 10px;
  font-weight: 800;
}

.complete-metrics {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;
  margin-top: 11px;
}

.complete-metrics > div {
  padding: 8px;
  border-radius: 8px;
  background: rgba(255, 255, 255, .78);
  border: 1px solid rgba(167, 243, 208, .8);
}

.complete-metrics span {
  display: block;
  color: #64748b;
  font-size: 9.5px;
  font-weight: 600;
}

.complete-metrics strong {
  display: block;
  margin-top: 2px;
  color: #0f172a;
  font-size: 13px;
}

@media (max-width: 520px) {
  .attendance-complete-msg { grid-template-columns: 1fr; }
  .complete-metrics { grid-template-columns: 1fr; }
  .complete-badge { display: none; }
}

/* Button UI */
.btn {
  border: none;
  border-radius: 10px;
  padding: 12px 20px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #fff;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
}

.btn-warning {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3);
}

.btn-warning:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
}

.btn-success {
  background: linear-gradient(135deg, #10b981, #059669);
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
}

.btn-success:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-lg {
  padding: 10px 20px;
  font-size: 14px;
}

.btn-block {
  width: 100%;
}

.btn svg {
  width: 18px;
  height: 18px;
}

/* === STATS PANEL GRID === */
.chamcong-stats-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
  border-top: 1px solid #e2e8f0;
  padding-top: 12px;
}

@media (max-width: 640px) {
  .chamcong-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.chamcong-stat-item {
  background: #f8fafc !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 10px !important;
  padding: 8px 4px !important;
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  gap: 4px !important;
  box-shadow: 0 1px 2px rgba(0,0,0,0.02) !important;
}

.chamcong-stat-label {
  font-size: 11px !important;
  color: #64748b !important;
  font-weight: 600 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
}

.chamcong-stat-value {
  font-size: 14px !important;
  font-weight: 700 !important;
  color: #1e293b !important;
}

.text-blue { color: #2563eb !important; }
.text-purple { color: #7c3aed !important; }

.alert-late {
  background: #fef2f2 !important;
  border-color: #fca5a5 !important;
}

.alert-late .chamcong-stat-value {
  color: #dc2626 !important;
}

.highlight-gold {
  color: #d97706 !important;
}

/* === LEADERBOARD === */
.leaderboard-card {
  display: flex;
  flex-direction: column;
  height: auto;
  align-self: start;
}

.my-rank-badge {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border: 1px solid #fcd34d;
  padding: 6px 12px;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.rank-title {
  font-size: 9px;
  color: #b45309;
  text-transform: uppercase;
  font-weight: bold;
}

.rank-number {
  font-size: 16px;
  font-weight: 800;
  color: #78350f;
}

.leaderboard-body {
  flex: 0 1 auto;
  max-height: 390px;
  overflow-y: auto;
  padding-right: 4px;
}

.leaderboard-body::-webkit-scrollbar {
  width: 6px;
}

.leaderboard-body::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 3px;
}

.leaderboard-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.leaderboard-item {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  gap: 10px;
  transition: all 0.2s;
}

.leaderboard-item:hover {
  background: #f8fafc;
}

.leaderboard-item.is-me {
  background: #eff6ff;
  border-color: #bfdbfe;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.05);
}

.item-rank {
  width: 28px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.medal {
  font-size: 20px;
}

.rank-text {
  font-size: 14px;
  font-weight: 700;
  color: #64748b;
}

.item-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 1.5px solid rgba(255, 255, 255, 0.1);
}

.item-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.item-name {
  font-weight: 600;
  font-size: 14px;
  color: #1e293b;
}

.work-assignment-block {
  grid-column: 1 / -1;
  display: grid;
  gap: 13px;
  padding: 16px;
  border: 1px solid #bfdbfe;
  border-radius: 14px;
  background: #f8fbff;
}
.assignment-heading strong,
.assignment-heading small { display: block; }
.assignment-heading strong { color: #1d4ed8; font-size: 13px; }
.assignment-heading small { margin-top: 3px; color: #64748b; font-size: 10px; }
.assignment-date-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  align-items: start;
  gap: 12px;
}
.assignment-date-field {
  grid-template-rows: 16px 37px 15px;
  align-content: start;
}
.assignment-date-field > span,
.assignment-date-field > input { align-self: stretch; }
.assignment-field-feedback {
  display: block;
  min-height: 15px;
  margin: 0;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.feedback-placeholder { visibility: hidden; }
.weekday-field { display: grid; gap: 7px; }
.weekday-field > span { color: #334155; font-size: 11px; font-weight: 700; }
.weekday-options { display: flex; flex-wrap: wrap; gap: 6px; }
.weekday-options label {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 9px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  font-size: 10px;
  cursor: pointer;
}
.weekday-options label.selected { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; font-weight: 750; }
.weekday-options input { width: 13px; height: 13px; margin: 0; }
.assignment-error { color: #dc2626 !important; }
.employee-setup-form label small.optional-note {
  color: #64748b;
  font-weight: 500;
}
@media (max-width: 640px) { .assignment-date-grid { grid-template-columns: 1fr; } }

.item-role {
  font-size: 11.5px;
  color: #64748b;
}

.item-stats {
  display: flex;
  flex-direction: column;
}

.item-cong {
  font-weight: 700;
  font-size: 13.5px;
  color: #d97706;
}

.item-hours {
  font-size: 11px;
  color: #64748b;
}

/* === HISTORY CARD === */
.history-card {
  width: 100%;
}

.table-container {
  overflow-x: auto;
}

.history-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.history-table th {
  padding: 14px 16px;
  border-bottom: 1px solid #e2e8f0;
  color: #475569;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
}

.history-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13.5px;
  vertical-align: middle;
  color: #1e293b;
}

.history-table tr:hover td {
  background: #f8fafc;
}

.history-thumb {
  width: 48px;
  height: 36px;
  border-radius: 6px;
  object-fit: cover;
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: transform 0.2s;
}

.history-thumb:hover {
  transform: scale(1.1);
}

/* Badge styles */
.badge {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 600;
}

.badge-success { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.badge-warning { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
.badge-gold { background: rgba(251, 191, 36, 0.12); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.2); }

.badge-danger {
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}

.badge-success-outline {
  border: 1px solid rgba(16, 185, 129, 0.3);
  color: #10b981;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #64748b;
  font-size: 14px;
}

.font-bold { font-weight: 700; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.text-gray { color: #64748b; }

/* Camera Select Dropdown */
.camera-select-container {
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  font-size: 13.5px;
  background: #f8fafc;
  padding: 10px 14px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.camera-select-label {
  font-weight: 600;
  color: #475569;
}

.form-select {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  color: #1e293b;
  padding: 6px 12px;
  outline: none;
  cursor: pointer;
  max-width: 250px;
  font-family: inherit;
}

.form-select option {
  background: #ffffff;
  color: #1e293b;
}
</style>
