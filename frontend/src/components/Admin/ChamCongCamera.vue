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
const isEnrollmentMode = computed(() => Boolean(enrollmentTarget.value))
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
  trangthai: 'active'
})
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
    if (await ensurePresenceModel()) startFaceMonitoring()
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
  return fields.every(field => !employeeErrors.value[field])
}

function applyServerValidationErrors(error, target = employeeErrors) {
  const errors = error.response?.data?.errors || {}
  Object.entries(errors).forEach(([field, messages]) => {
    target.value[field] = Array.isArray(messages) ? messages[0] : String(messages)
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

    const payload = {
      ...employeeForm.value,
      trangthai: 'active',
      matkhau_confirmation: employeeForm.value.matkhau
    }
    const response = await api.post('/admin/users', payload)
    const created = response.data.user
    createdEmployeeId = created.id
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
    employeeForm.value = { ten: '', email: '', sodienthoai: '', vaitro: roles.value[0]?.ma_vaitro || '', matkhau: '', trangthai: 'active' }
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
  if (isEnrollmentMode.value && !hasEmployeeDraft.value) {
    await registerCurrentFace()
    await fetchEmployees()
    return
  }
  await createEmployeeAndEnroll()
}

function openEditEmployee(employee) {
  editingEmployee.value = employee
  employeeForm.value = {
    ten: employee.ten || '',
    email: employee.email || '',
    sodienthoai: employee.sodienthoai || '',
    vaitro: employee.ma_vaitro || employee.vaitro || '',
    trangthai: employee.trangthai || 'active',
    matkhau: ''
  }
  employeeErrors.value = {}
  employeeTouched.value = {}
  employeeFormError.value = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function saveEmployee() {
  if (!editingEmployee.value || !validateEmployeeForm()) return
  savingEmployee.value = true
  try {
    const payload = { ...employeeForm.value }
    if (!payload.matkhau) delete payload.matkhau
    await api.put(`/admin/users/${editingEmployee.value.id}`, payload)
    await fetchEmployees()
    if (Number(enrollmentTarget.value?.id) === Number(editingEmployee.value.id)) {
      const updated = employees.value.find(item => Number(item.id) === Number(editingEmployee.value.id))
      if (updated) enrollmentTarget.value = { ...updated, name: updated.ten, avatar: updated.anhdaidien, role_name: updated.ten_vaitro }
    }
    editingEmployee.value = null
    employeeForm.value = { ten: '', email: '', sodienthoai: '', vaitro: roles.value[0]?.ma_vaitro || '', matkhau: '', trangthai: 'active' }
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
  employeeForm.value = { ten: '', email: '', sodienthoai: '', vaitro: roles.value[0]?.ma_vaitro || '', matkhau: '', trangthai: 'active' }
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
    const detections = await faceapi.detectAllFaces(
      videoRef.value,
      new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.35 })
    )
    faceDetected.value = detections.length === 1
    if (detections.length === 1) {
      scanState.value = 'detected'
      detectionMessage.value = 'Đã phát hiện khuôn mặt — sẵn sàng đăng ký'
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

    scanState.value = 'detected'
    detectionMessage.value = 'Đã phát hiện 1 khuôn mặt hợp lệ'
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
      await api.post(`/admin/cham-cong/nhan-vien/${enrollmentTarget.value.id}/dang-ky-khuon-mat`, {
        face_descriptor: descriptor
      })
      enrollmentTarget.value.face_registered = true
      await fetchEmployees()
      swal.success('Đăng ký thành công', `Khuôn mặt đã được liên kết với hồ sơ ${enrollmentTarget.value.name}.`)
    } else {
      await api.post('/cham-cong/register-face', { face_descriptor: descriptor })
      await fetchStatus()
      swal.success('Đăng ký thành công', 'Khuôn mặt đã được liên kết với hồ sơ nhân viên của bạn.')
    }
  } catch (error) {
    swal.error('Đăng ký thất bại', error.response?.data?.message || 'Không thể đăng ký khuôn mặt.')
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
    })

    if (res.data.success) {
      const typeText = res.data.type === 'checkin' ? 'Check-in' : 'Check-out'
      await swal.success('Chấm công thành công', `${typeText} lúc ${formatTime(res.data.record.gio_vao || res.data.record.gio_ra)} thành công!`)
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
              invalid: ['no-face', 'multiple', 'error'].includes(scanState),
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

        <form id="employee-unified-form" class="employee-setup-form" @submit.prevent="handleUnifiedEmployeeSubmit">
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

          <p v-if="employeeFormError" class="setup-error">{{ employeeFormError }}</p>

        </form>

        <div class="setup-links">
          <button type="button" @click="router.push({ name: 'admin-roles' })">Quản lý vai trò & quyền</button>
          <button type="button" @click="router.push('/admin/quan-ly-nguoi-dung')">Danh sách người dùng</button>
        </div>
      </aside>

      <div class="unified-submit-area">
        <div>
          <strong>{{ editingEmployee ? 'Cập nhật thông tin nhân viên' : (isEnrollmentMode && !hasEmployeeDraft ? 'Cập nhật sinh trắc học nhân viên' : 'Hoàn tất thiết lập nhân viên') }}</strong>
          <span>
            {{ editingEmployee
              ? `Các thay đổi của ${editingEmployee.ten} sẽ được lưu trực tiếp vào hồ sơ.`
              : isEnrollmentMode && !hasEmployeeDraft
              ? `Khuôn mặt sẽ được cập nhật cho ${displayEmployee.name}.`
              : 'Hệ thống sẽ kiểm tra dữ liệu, tạo hồ sơ, gán vai trò và lưu khuôn mặt trong một lần.' }}
          </span>
        </div>
        <button v-if="editingEmployee" type="button" class="cancel-edit-button" @click="cancelEditEmployee">Hủy sửa</button>
        <button
          type="submit"
          form="employee-unified-form"
          class="create-employee-button"
          :disabled="creatingEmployee || savingEmployee || isRegisteringFace || (!editingEmployee && !isCameraActive)"
        >
          {{ savingEmployee
            ? 'Đang lưu thay đổi...'
            : creatingEmployee || isRegisteringFace
            ? 'Đang xác thực và lưu dữ liệu...'
            : editingEmployee
              ? 'Lưu thay đổi'
              : (isEnrollmentMode && !hasEmployeeDraft ? 'Cập nhật khuôn mặt' : 'Tạo nhân viên & đăng ký khuôn mặt') }}
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
        <label class="employee-search">
          <span aria-hidden="true">⌕</span>
          <input v-model="employeeSearch" placeholder="Tìm theo tên, email, số điện thoại..." />
        </label>
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
  grid-template-columns: 46px minmax(0, 1fr) auto;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border: 1px solid #bfdbfe;
  border-radius: 14px;
  background: linear-gradient(135deg, #eff6ff, #f8fafc);
}

.enrollment-icon {
  width: 46px;
  height: 46px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #2563eb;
  color: #fff;
  font-size: 28px;
  font-weight: 800;
}

.enrollment-banner span { color: #2563eb; font-size: 11px; font-weight: 800; letter-spacing: .08em; }
.enrollment-banner strong { display: block; color: #0f172a; font-size: 18px; margin-top: 2px; }
.enrollment-banner p { margin: 2px 0 0; color: #64748b; font-size: 12px; }
.enrollment-banner button {
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  background: #fff;
  color: #1d4ed8;
  padding: 10px 14px;
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
  grid-template-columns: minmax(0, 1.04fr) minmax(400px, .96fr);
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
  padding: 0 16px 0 0;
  border: 0;
  border-right: 1px solid #e2e8f0;
  border-radius: 0;
  box-shadow: none;
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
.employee-setup-form { display: grid; gap: 8px; margin-top: 11px; }
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
  margin: 0;
  padding: 8px 10px;
  border-radius: 8px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 10.5px;
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
  padding: 11px 0 0;
  border-top: 1px solid #e2e8f0;
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
  display: flex;
  align-items: center;
  gap: 7px;
  width: min(340px, 100%);
  height: 38px;
  padding: 0 11px;
  border: 1px solid #cbd5e1;
  border-radius: 9px;
  color: #64748b;
}
.employee-search input { width: 100%; border: 0; outline: 0; background: transparent; color: #0f172a; font-size: 11px; }
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
    padding: 0 0 16px;
    border-right: 0;
    border-bottom: 1px solid #e2e8f0;
  }
  .employee-setup-card { padding: 16px 0 0; }
  .unified-submit-area { align-items: stretch; flex-direction: column; }
  .unified-submit-area .create-employee-button { width: 100%; min-width: 0; }
  .cancel-edit-button { width: 100%; }
}

@media (max-width: 620px) {
  .today-schedule { grid-template-columns: 1fr; }
  .enrollment-banner { grid-template-columns: 40px 1fr; }
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
