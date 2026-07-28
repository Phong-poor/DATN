<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'
import { getUser } from '@/services/auth'

const router = useRouter()
const currentUser = ref(getUser() || {})
const isAdmin = computed(() => currentUser.value?.vaitro === 'admin')
// === STATES ===
const logs = ref([])
const employees = ref([])
const employeeSummary = ref({ total: 0, registered: 0, not_registered: 0, locked: 0 })
const employeeSearch = ref('')
const loading = ref(false)
const searchQuery = ref('')
const filterDate = ref('')
const filterMonth = ref(new Date().toISOString().slice(0, 7))
const selectedEmployeeId = ref('')
const viewMode = ref('month')
const payrollSummary = ref({
  work_days: 0,
  on_time_days: 0,
  late_days: 0,
  gross_salary: 0,
  total_penalty: 0,
  net_salary: 0,
  base_salary_per_day: 350000,
  penalty_per_ten_minutes: 10000
})
const calendarEmployee = ref(null)
const calendarMonth = ref(new Date().toISOString().slice(0, 7))
const calendarLogs = ref([])
const calendarSummary = ref({})
const calendarLoading = ref(false)
const selectedCalendarLog = ref(null)
const calendarTitle = computed(() => {
  const [year, month] = calendarMonth.value.split('-').map(Number)
  return `Tháng ${month}/${year}`
})
const calendarCells = computed(() => {
  const [year, month] = calendarMonth.value.split('-').map(Number)
  const firstDay = new Date(year, month - 1, 1)
  const start = new Date(year, month - 1, 1 - firstDay.getDay())
  const logMap = new Map(calendarLogs.value.map(log => [log.ngay_cham_cong, log]))
  return Array.from({ length: 42 }, (_, index) => {
    const date = new Date(start)
    date.setDate(start.getDate() + index)
    const key = [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, '0'),
      String(date.getDate()).padStart(2, '0')
    ].join('-')
    return {
      key,
      day: date.getDate(),
      currentMonth: date.getMonth() === month - 1,
      isToday: key === new Date().toISOString().slice(0, 10),
      log: logMap.get(key) || null
    }
  })
})

const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)
const perPage = ref(15)

// Thống kê nhanh hôm nay
const stats = ref({
  total_staff: 0,
  present: 0,
  late: 0,
  absent: 0
})

// === FUNCTIONS ===
function getAvatarUrl(avatar, name) {
  if (!avatar) return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'Staff')}&background=0D8ABC&color=fff`
  return avatar.startsWith('http') ? avatar : storageUrl(avatar)
}

function formatTime(timeStr) {
  if (!timeStr) return '--:--'
  const parts = timeStr.split(':')
  if (parts.length >= 2) return `${parts[0]}:${parts[1]}`
  return timeStr
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatMoney(value) {
  return `${Number(value || 0).toLocaleString('vi-VN')}đ`
}

function formatWorkDate(dateStr) {
  if (!dateStr) return { weekday: '', date: '' }
  const d = new Date(`${dateStr}T00:00:00`)
  return {
    weekday: d.toLocaleDateString('vi-VN', { weekday: 'long' }),
    date: d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }
}

function attendanceStatus(log) {
  if (!log.gio_vao) return { text: 'Chưa check-in', className: 'status-missing' }
  if (!log.gio_ra) return { text: 'Đang làm việc', className: 'status-working' }
  if (Number(log.di_tre_phut) > 0) return { text: 'Hoàn tất · đi trễ', className: 'status-late' }
  return { text: 'Đã hoàn tất', className: 'status-complete' }
}

const workSchedule = {
  morning: '08:00 – 12:00',
  break: '12:00 – 13:30',
  afternoon: '13:30 – 17:30'
}

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    let url = `/admin/quan-ly-cham-cong?page=${page}`
    if (searchQuery.value) {
      url += `&search=${encodeURIComponent(searchQuery.value)}`
    }
    if (filterDate.value) {
      url += `&date=${filterDate.value}`
    }
    if (viewMode.value === 'month' && filterMonth.value) {
      url += `&month=${filterMonth.value}`
    }
    if (selectedEmployeeId.value) {
      url += `&employee_id=${selectedEmployeeId.value}`
    }

    const res = await api.get(url)
    if (res.data.success) {
      logs.value = res.data.data.data || []
      currentPage.value = res.data.data.current_page || 1
      lastPage.value = res.data.data.last_page || 1
      totalItems.value = res.data.data.total || 0
      perPage.value = res.data.data.per_page || 15
      payrollSummary.value = res.data.payroll_summary || payrollSummary.value
      const apiStats = res.data.attendance_summary || {}
      stats.value = {
        total_staff: apiStats.present || 0,
        present: apiStats.present || 0,
        late: apiStats.late || 0,
        absent: 0,
        total_work_units: apiStats.total_work_units || 0,
        total_hours: apiStats.total_hours || 0
      }
    }
  } catch (error) {
    console.error('Không thể tải lịch sử chấm công:', error)
    swal.error('Lỗi', 'Không thể tải danh sách lịch sử chấm công.')
  } finally {
    loading.value = false
  }
}

async function fetchEmployees() {
  try {
    const res = await api.get('/admin/cham-cong/nhan-vien', { skipGlobalLoader: true })
    if (res.data?.success) {
      employees.value = res.data.data || []
      employeeSummary.value = res.data.summary || employeeSummary.value
    }
  } catch (error) {
    console.error('Không thể tải hồ sơ nhân viên chấm công:', error)
  }
}

function filteredEmployees() {
  const keyword = employeeSearch.value.trim().toLowerCase()
  if (!keyword) return employees.value
  return employees.value.filter(employee =>
    [employee.ten, employee.email, employee.ten_vaitro, employee.ma_vaitro]
      .some(value => String(value || '').toLowerCase().includes(keyword))
  )
}

function openFaceEnrollment(employee) {
  router.push({ name: 'admin-chamcong-camera', query: { enroll: employee.id } })
}

async function openEmployeeCalendar(employee) {
  calendarEmployee.value = employee
  calendarMonth.value = new Date().toISOString().slice(0, 7)
  selectedCalendarLog.value = null
  await fetchEmployeeCalendar()
}

async function fetchEmployeeCalendar() {
  if (!calendarEmployee.value) return
  calendarLoading.value = true
  try {
    const response = await api.get('/admin/quan-ly-cham-cong', {
      params: {
        employee_id: calendarEmployee.value.id,
        month: calendarMonth.value,
        per_page: 62
      },
      skipGlobalLoader: true
    })
    calendarLogs.value = response.data.data?.data || []
    calendarSummary.value = response.data.payroll_summary || {}
  } catch (error) {
    swal.error('Không tải được lịch', error.response?.data?.message || 'Vui lòng thử lại.')
  } finally {
    calendarLoading.value = false
  }
}

async function changeCalendarMonth(offset) {
  const [year, month] = calendarMonth.value.split('-').map(Number)
  const next = new Date(year, month - 1 + offset, 1)
  calendarMonth.value = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`
  selectedCalendarLog.value = null
  await fetchEmployeeCalendar()
}

function closeEmployeeCalendar() {
  calendarEmployee.value = null
  calendarLogs.value = []
  selectedCalendarLog.value = null
}

async function removeEmployeeFace(employee) {
  const confirmed = await swal.confirm(
    'Xóa khuôn mặt đã đăng ký?',
    `Nhân viên ${employee.ten} sẽ phải đăng ký lại trước khi chấm công.`
  )
  if (!confirmed) return

  try {
    await api.delete(`/admin/cham-cong/nhan-vien/${employee.id}/khuon-mat`)
    await fetchEmployees()
    swal.success('Đã xóa', `Đã xóa dữ liệu khuôn mặt của ${employee.ten}.`)
  } catch (error) {
    swal.error('Không thể xóa', error.response?.data?.message || 'Vui lòng thử lại.')
  }
}

function viewImage(url, title) {
  swal.image(storageUrl(url), title)
}

watch([searchQuery, filterDate, filterMonth, selectedEmployeeId, viewMode], () => {
  fetchLogs(1)
})

function setViewMode(mode) {
  viewMode.value = mode
  if (mode === 'all') {
    filterDate.value = ''
  } else if (mode === 'day' && !filterDate.value) {
    filterDate.value = new Date().toISOString().slice(0, 10)
  } else if (mode === 'month') {
    filterDate.value = ''
  }
}

onMounted(() => {
  fetchLogs(1)
  if (isAdmin.value) fetchEmployees()
})
</script>

<template>
  <div class="attendance-admin-page">
    <div class="page-header">
      <div>
        <h2 class="page-title">Quản lý Chấm công Nhân viên</h2>
        <p class="page-subtitle">Xem toàn bộ lịch sử chấm công, đi trễ, số giờ làm và công của nhân viên.</p>
      </div>
      
      <!-- Cấu hình các bộ lọc -->
      <div v-if="isAdmin" class="filter-bar">
        <div class="search-box">
          <input type="text" placeholder="Tìm kiếm nhân viên..." v-model="searchQuery" />
        </div>
      </div>
    </div>

    <div class="schedule-guide">
      <div class="schedule-guide-title">
        <span class="guide-icon">i</span>
        <div>
          <strong>Quy định thời gian làm việc</strong>
          <p>Mỗi ngày chấm một lần vào và một lần ra. Hệ thống tự tính đi trễ, giờ làm thực tế và tổng công.</p>
        </div>
      </div>
      <div class="schedule-slots">
        <div><span>Ca sáng</span><strong>{{ workSchedule.morning }}</strong></div>
        <div><span>Nghỉ trưa</span><strong>{{ workSchedule.break }}</strong></div>
        <div><span>Ca chiều</span><strong>{{ workSchedule.afternoon }}</strong></div>
      </div>
    </div>

    <div class="stats-grid">
      <div class="chamcong-stat-card">
        <span class="stat-lbl">Tổng nhân sự có mặt</span>
        <span class="stat-val text-blue">{{ stats.present }} người</span>
      </div>
      <div class="chamcong-stat-card">
        <span class="stat-lbl">Tổng số lượt đi muộn</span>
        <span class="stat-val text-red">{{ stats.late }} lượt</span>
      </div>
      <div class="chamcong-stat-card">
        <span class="stat-lbl">Tổng số công tích lũy</span>
        <span class="stat-val text-gold">+{{ Number(stats.total_work_units || 0).toFixed(1) }}</span>
      </div>
      <div class="chamcong-stat-card">
        <span class="stat-lbl">Tổng giờ làm việc thực tế</span>
        <span class="stat-val text-purple">{{ Number(stats.total_hours || 0).toFixed(1) }}h</span>
      </div>
    </div>

    <section v-if="isAdmin" class="employee-registry">
      <div class="registry-header">
        <div>
          <span class="section-eyebrow">HỒ SƠ CHẤM CÔNG</span>
          <h3>Nhân viên và khuôn mặt đã đăng ký</h3>
          <p>Tên và chức vụ được đồng bộ trực tiếp từ trang Vai trò & quyền.</p>
        </div>
        <div class="registry-summary">
          <span><strong>{{ employeeSummary.total }}</strong> nhân viên</span>
          <span class="summary-ready"><strong>{{ employeeSummary.registered }}</strong> đã đăng ký</span>
          <span class="summary-pending"><strong>{{ employeeSummary.not_registered }}</strong> chưa đăng ký</span>
        </div>
      </div>

      <div class="registry-tools">
        <div class="employee-search">
          <span>⌕</span>
          <input v-model="employeeSearch" placeholder="Tìm tên, email hoặc chức vụ nhân viên..." />
        </div>
        <button type="button" class="roles-link" @click="router.push({ name: 'admin-roles' })">
          Quản lý vai trò & quyền
        </button>
      </div>

      <div class="employee-grid">
        <article v-for="employee in filteredEmployees()" :key="employee.id" class="employee-card" role="button" tabindex="0"
          @click="openEmployeeCalendar(employee)" @keydown.enter="openEmployeeCalendar(employee)">
          <img :src="getAvatarUrl(employee.anhdaidien, employee.ten)" :alt="employee.ten" />
          <div class="employee-main">
            <div class="employee-name-line">
              <strong>{{ employee.ten }}</strong>
              <span class="account-state" :class="{ locked: employee.trangthai === 'locked' }">
                {{ employee.trangthai === 'locked' ? 'Đã khóa' : 'Đang hoạt động' }}
              </span>
            </div>
            <span class="employee-role">{{ employee.ten_vaitro }}</span>
            <small>{{ employee.email }}</small>
            <div class="last-attendance">
              <span>Lần gần nhất</span>
              <strong v-if="employee.latest_attendance">
                {{ formatDate(employee.latest_attendance.date) }} ·
                {{ formatTime(employee.latest_attendance.check_in) }} → {{ formatTime(employee.latest_attendance.check_out) }}
              </strong>
              <strong v-else>Chưa có dữ liệu chấm công</strong>
            </div>
          </div>
          <div class="face-profile">
            <span class="face-state" :class="{ registered: employee.face_registered }">
              {{ employee.face_registered ? '✓ Đã có khuôn mặt' : '! Chưa có khuôn mặt' }}
            </span>
            <button
              type="button"
              class="enroll-btn"
              :disabled="employee.trangthai === 'locked'"
              @click.stop="openFaceEnrollment(employee)"
            >
              {{ employee.trangthai === 'locked' ? 'Tài khoản đã khóa' : (employee.face_registered ? 'Cập nhật khuôn mặt' : 'Đăng ký khuôn mặt') }}
            </button>
            <button v-if="employee.face_registered" type="button" class="remove-face-btn" @click.stop="removeEmployeeFace(employee)">
              Xóa dữ liệu mặt
            </button>
          </div>
        </article>
        <div v-if="filteredEmployees().length === 0" class="registry-empty">
          Không tìm thấy nhân viên phù hợp.
        </div>
      </div>
    </section>

    <section v-if="isAdmin" class="payroll-panel">
      <div class="payroll-heading">
        <div>
          <span class="section-eyebrow">LỊCH LÀM VIỆC & TÍNH LƯƠNG</span>
          <h3>Bảng công nhân viên</h3>
          <p>Lương chuẩn {{ formatMoney(payrollSummary.base_salary_per_day) }}/ngày. Cứ mỗi 10 phút đi muộn trừ {{ formatMoney(payrollSummary.penalty_per_ten_minutes) }}.</p>
        </div>
        <div class="view-switcher">
          <button type="button" :class="{ active: viewMode === 'day' }" @click="setViewMode('day')">Theo ngày</button>
          <button type="button" :class="{ active: viewMode === 'month' }" @click="setViewMode('month')">Theo tháng</button>
          <button type="button" :class="{ active: viewMode === 'all' }" @click="setViewMode('all')">Tất cả</button>
        </div>
      </div>

      <div class="payroll-filters">
        <label>
          <span>Nhân viên</span>
          <select v-model="selectedEmployeeId">
            <option value="">Tất cả nhân viên</option>
            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
              {{ employee.ten }} — {{ employee.ten_vaitro }}
            </option>
          </select>
        </label>
        <label v-if="viewMode === 'day'">
          <span>Ngày làm việc</span>
          <input v-model="filterDate" type="date" />
        </label>
        <label v-if="viewMode === 'month'">
          <span>Tháng làm việc</span>
          <input v-model="filterMonth" type="month" />
        </label>
      </div>

      <div class="payroll-summary-grid">
        <article><span>Ngày đi làm</span><strong>{{ payrollSummary.work_days }}</strong><small>{{ payrollSummary.on_time_days }} ngày đúng giờ</small></article>
        <article class="summary-late"><span>Ngày đi muộn</span><strong>{{ payrollSummary.late_days }}</strong><small>Có ghi chú theo từng ngày</small></article>
        <article><span>Lương trước phạt</span><strong>{{ formatMoney(payrollSummary.gross_salary) }}</strong><small>{{ formatMoney(payrollSummary.base_salary_per_day) }}/ngày</small></article>
        <article class="summary-penalty"><span>Tổng tiền phạt</span><strong>-{{ formatMoney(payrollSummary.total_penalty) }}</strong><small>Mỗi 10 phút trừ {{ formatMoney(payrollSummary.penalty_per_ten_minutes) }}</small></article>
        <article class="summary-net"><span>Thực nhận dự kiến</span><strong>{{ formatMoney(payrollSummary.net_salary) }}</strong><small>Sau khi trừ đi muộn</small></article>
      </div>
    </section>

    <!-- Bảng Dữ liệu chính -->
    <div class="chamcong-table-card">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải danh sách chấm công...</p>
      </div>

      <div v-else-if="logs.length === 0" class="empty-state">
        Không tìm thấy bản ghi chấm công nào khớp với bộ lọc.
      </div>

      <div v-else class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Nhân viên</th>
              <th>Ngày làm</th>
              <th>Lịch làm chuẩn</th>
              <th>Thời gian thực tế</th>
              <th>Trạng thái</th>
              <th>Đi trễ</th>
              <th>Giờ làm / Công</th>
              <th>Ghi chú khấu trừ</th>
              <th>Minh chứng</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td>
                <div class="user-info">
                  <img :src="getAvatarUrl(log.user?.anhdaidien, log.user?.ten)" class="user-avatar" />
                  <div>
                    <p class="user-name">{{ log.user?.ten || 'Không xác định' }}</p>
                    <p class="user-email">{{ log.user?.email || '' }}</p>
                  </div>
                </div>
              </td>
              <td>
                <div class="work-date">
                  <strong>{{ formatWorkDate(log.ngay_cham_cong).date }}</strong>
                  <span>{{ formatWorkDate(log.ngay_cham_cong).weekday }}</span>
                </div>
              </td>
              <td>
                <div class="schedule-cell">
                  <span>Sáng {{ workSchedule.morning }}</span>
                  <span>Chiều {{ workSchedule.afternoon }}</span>
                </div>
              </td>
              <td>
                <div class="actual-time">
                  <span><i class="dot dot-in"></i> Vào <strong>{{ formatTime(log.gio_vao) }}</strong></span>
                  <span><i class="dot dot-out"></i> Ra <strong>{{ formatTime(log.gio_ra) }}</strong></span>
                </div>
              </td>
              <td>
                <span class="attendance-status" :class="attendanceStatus(log).className">
                  {{ attendanceStatus(log).text }}
                </span>
              </td>
              <td>
                <span v-if="log.di_tre_phut > 0" class="badge-danger">Trễ {{ log.di_tre_phut }} phút</span>
                <span v-else class="badge-success">Đúng giờ</span>
              </td>
              <td>
                <div class="work-total">
                  <strong>{{ log.tong_gio || '0.00' }} giờ</strong>
                  <span>+{{ log.tong_cong || '0.00' }} công</span>
                </div>
              </td>
              <td>
                <div class="payroll-note" :class="{ late: log.tien_phat > 0 }">
                  <strong>{{ log.tien_phat > 0 ? `Trừ ${formatMoney(log.tien_phat)}` : 'Không khấu trừ' }}</strong>
                  <span>{{ log.ghi_chu_luong }}</span>
                </div>
              </td>
              <td>
                <div class="evidence-pair">
                  <button v-if="log.anh_vao" type="button" @click="viewImage(log.anh_vao, `Ảnh check-in - ${log.user?.ten}`)">
                    <img :src="storageUrl(log.anh_vao)" class="history-thumb" alt="Check-in" />
                    <span>Vào</span>
                  </button>
                  <button v-if="log.anh_ra" type="button" @click="viewImage(log.anh_ra, `Ảnh check-out - ${log.user?.ten}`)">
                    <img :src="storageUrl(log.anh_ra)" class="history-thumb" alt="Check-out" />
                    <span>Ra</span>
                  </button>
                  <span v-if="!log.anh_vao && !log.anh_ra" class="text-gray">Không có ảnh</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-footer" v-if="lastPage > 1">
        <p class="pagination-info">Hiển thị trang {{ currentPage }} / {{ lastPage }} (Tổng số {{ totalItems }} bản ghi)</p>
        <div class="pagination">
          <button class="p-arrow" :disabled="currentPage === 1" @click="fetchLogs(currentPage - 1)">‹ Trước</button>
          <div class="p-nums">
            <button v-for="p in lastPage" :key="p" class="p-num" :class="{ active: currentPage === p }" @click="fetchLogs(p)">{{ p }}</button>
          </div>
          <button class="p-arrow" :disabled="currentPage === lastPage" @click="fetchLogs(currentPage + 1)">Sau ›</button>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="calendarEmployee" class="calendar-modal-backdrop" @click.self="closeEmployeeCalendar">
        <section class="employee-calendar-modal" role="dialog" aria-modal="true">
          <header class="calendar-modal-header">
            <div class="calendar-employee">
              <img :src="getAvatarUrl(calendarEmployee.anhdaidien, calendarEmployee.ten)" alt="" />
              <div>
                <span>LỊCH CÔNG NHÂN VIÊN</span>
                <h3>{{ calendarEmployee.ten }}</h3>
                <p>{{ calendarEmployee.ten_vaitro }} · {{ calendarEmployee.email }}</p>
              </div>
            </div>
            <button type="button" class="calendar-close" aria-label="Đóng lịch công" title="Đóng" @click="closeEmployeeCalendar">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" />
              </svg>
            </button>
          </header>

          <div class="calendar-toolbar">
            <button type="button" @click="changeCalendarMonth(-1)">‹</button>
            <strong>{{ calendarTitle }}</strong>
            <button type="button" @click="changeCalendarMonth(1)">›</button>
          </div>

          <div class="calendar-payroll-summary">
            <div><span>Ngày công</span><strong>{{ calendarSummary.work_days || 0 }}</strong></div>
            <div><span>Lương trước phạt</span><strong>{{ formatMoney(calendarSummary.gross_salary) }}</strong></div>
            <div class="deduction"><span>Đi muộn / khấu trừ</span><strong>{{ calendarSummary.late_days || 0 }} ngày · -{{ formatMoney(calendarSummary.total_penalty) }}</strong></div>
            <div class="net"><span>Thực nhận tháng</span><strong>{{ formatMoney(calendarSummary.net_salary) }}</strong></div>
          </div>

          <div v-if="calendarLoading" class="calendar-loading">Đang tải lịch công...</div>
          <div v-else class="employee-calendar">
            <div v-for="weekday in ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']" :key="weekday" class="calendar-weekday">{{ weekday }}</div>
            <button
              v-for="cell in calendarCells"
              :key="cell.key"
              type="button"
              class="calendar-day"
              :class="{ muted: !cell.currentMonth, today: cell.isToday, worked: cell.log, late: Number(cell.log?.di_tre_phut) > 0 }"
              :disabled="!cell.currentMonth"
              @click="selectedCalendarLog = cell.log"
            >
              <span class="day-number">{{ cell.day }}</span>
              <template v-if="cell.log">
                <small>{{ formatTime(cell.log.gio_vao) }} → {{ formatTime(cell.log.gio_ra) }}</small>
                <strong>{{ formatMoney(cell.log.luong_thuc_nhan) }}</strong>
                <em v-if="cell.log.tien_phat > 0">Trừ {{ formatMoney(cell.log.tien_phat) }}</em>
                <em v-else class="on-time">Đúng giờ · đủ công</em>
              </template>
              <small v-else-if="cell.currentMonth" class="no-record">Không có công</small>
            </button>
          </div>

          <div v-if="selectedCalendarLog" class="calendar-day-detail">
            <div>
              <span>CHI TIẾT {{ formatDate(selectedCalendarLog.ngay_cham_cong) }}</span>
              <strong>{{ selectedCalendarLog.ghi_chu_luong }}</strong>
            </div>
            <div><span>Giờ vào</span><strong>{{ formatTime(selectedCalendarLog.gio_vao) }}</strong></div>
            <div><span>Giờ ra</span><strong>{{ formatTime(selectedCalendarLog.gio_ra) }}</strong></div>
            <div><span>Lương ngày</span><strong>{{ formatMoney(selectedCalendarLog.luong_ngay) }}</strong></div>
            <div class="detail-penalty"><span>Khấu trừ</span><strong>-{{ formatMoney(selectedCalendarLog.tien_phat) }}</strong></div>
            <div class="detail-net"><span>Thực nhận</span><strong>{{ formatMoney(selectedCalendarLog.luong_thuc_nhan) }}</strong></div>
          </div>
          <p v-else class="calendar-hint">Bấm vào ngày có chấm công để xem đầy đủ giờ làm và lý do khấu trừ.</p>
        </section>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.attendance-admin-page {
  padding: 24px;
  background: #f8fafc;
  min-height: 100%;
  color: #1e293b;
  font-family: Inter, sans-serif;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.page-title {
  font-size: 24px;
  font-weight: 800;
  margin: 0;
  color: #0f172a;
}

.page-subtitle {
  font-size: 13.5px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.filter-bar {
  display: flex;
  gap: 12px;
}

.search-box input, .date-box input {
  padding: 10px 16px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  color: #0f172a;
  font-size: 13.5px;
  outline: none;
  transition: all 0.2s;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

.search-box input:focus, .date-box input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.employee-registry {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 18px;
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.05);
}
.registry-header,
.registry-tools {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}
.section-eyebrow { color: #2563eb; font-size: 10px; font-weight: 800; letter-spacing: .12em; }
.registry-header h3 { margin: 3px 0; color: #0f172a; font-size: 18px; }
.registry-header p { margin: 0; color: #64748b; font-size: 12px; }
.registry-summary { display: flex; gap: 8px; flex-wrap: wrap; }
.registry-summary span {
  padding: 7px 10px;
  border-radius: 9px;
  background: #f1f5f9;
  color: #475569;
  font-size: 11.5px;
}
.registry-summary .summary-ready { background: #dcfce7; color: #15803d; }
.registry-summary .summary-pending { background: #fef3c7; color: #b45309; }
.registry-tools { margin-top: 14px; }
.employee-search {
  flex: 1;
  height: 40px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  color: #64748b;
}
.employee-search input { width: 100%; border: 0; outline: 0; background: transparent; font-size: 12.5px; }
.roles-link,
.enroll-btn {
  border: 0;
  border-radius: 9px;
  padding: 9px 12px;
  background: #2563eb;
  color: #fff;
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
}
.roles-link { height: 40px; background: #0f172a; }
.enroll-btn:disabled { background: #cbd5e1; color: #64748b; cursor: not-allowed; }
.employee-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-top: 14px;
  max-height: 410px;
  overflow-y: auto;
  padding-right: 4px;
}
.employee-card {
  display: grid;
  grid-template-columns: 46px minmax(0, 1fr) auto;
  align-items: center;
  gap: 11px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fbfdff;
  cursor: pointer;
  transition: border-color .18s, box-shadow .18s, transform .18s;
}
.employee-card:hover,
.employee-card:focus-visible {
  border-color: #93c5fd;
  outline: none;
  box-shadow: 0 7px 18px rgba(37, 99, 235, .1);
  transform: translateY(-1px);
}
.employee-card > img { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; border: 2px solid #dbeafe; }
.employee-main { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.employee-name-line { display: flex; align-items: center; gap: 7px; }
.employee-name-line strong { color: #0f172a; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.account-state { padding: 2px 6px; border-radius: 999px; color: #15803d; background: #dcfce7; font-size: 9px; white-space: nowrap; }
.account-state.locked { color: #b91c1c; background: #fee2e2; }
.employee-role { color: #2563eb; font-size: 11.5px; font-weight: 700; }
.employee-main small { color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.last-attendance { margin-top: 5px; display: flex; flex-direction: column; gap: 1px; }
.last-attendance span { color: #94a3b8; font-size: 9.5px; text-transform: uppercase; }
.last-attendance strong { color: #475569; font-size: 10.5px; }
.face-profile { display: flex; flex-direction: column; align-items: stretch; gap: 5px; min-width: 132px; }
.face-state { padding: 5px 7px; border-radius: 7px; text-align: center; background: #fef3c7; color: #b45309; font-size: 10px; font-weight: 700; }
.face-state.registered { background: #dcfce7; color: #15803d; }
.remove-face-btn { border: 0; background: transparent; color: #dc2626; font-size: 9.5px; cursor: pointer; }
.registry-empty { grid-column: 1 / -1; padding: 30px; text-align: center; color: #64748b; }
@media (max-width: 1050px) {
  .employee-grid { grid-template-columns: 1fr; }
}
@media (max-width: 700px) {
  .registry-header, .registry-tools { align-items: stretch; flex-direction: column; }
  .employee-card { grid-template-columns: 42px minmax(0, 1fr); }
  .face-profile { grid-column: 1 / -1; }
}

.payroll-panel {
  padding: 18px;
  border: 1px solid #dbe3ef;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 4px 16px rgba(15, 23, 42, .05);
}
.payroll-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
}
.payroll-heading h3 { margin: 3px 0; color: #0f172a; font-size: 19px; }
.payroll-heading p { margin: 0; color: #64748b; font-size: 11.5px; }
.view-switcher {
  display: inline-flex;
  padding: 4px;
  border-radius: 10px;
  background: #f1f5f9;
}
.view-switcher button {
  min-height: 34px;
  padding: 6px 12px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: #64748b;
  font-size: 11px;
  font-weight: 750;
  cursor: pointer;
}
.view-switcher button.active { background: #2563eb; color: #fff; box-shadow: 0 4px 10px rgba(37, 99, 235, .2); }
.payroll-filters {
  display: flex;
  align-items: end;
  gap: 10px;
  margin-top: 15px;
  padding-top: 14px;
  border-top: 1px solid #e2e8f0;
}
.payroll-filters label { display: grid; gap: 5px; min-width: 230px; }
.payroll-filters label span { color: #475569; font-size: 10px; font-weight: 750; }
.payroll-filters select,
.payroll-filters input {
  height: 39px;
  padding: 0 11px;
  border: 1px solid #cbd5e1;
  border-radius: 9px;
  outline: none;
  background: #fff;
  color: #0f172a;
  font-size: 11.5px;
}
.payroll-summary-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 9px;
  margin-top: 14px;
}
.payroll-summary-grid article {
  min-width: 0;
  padding: 12px;
  border: 1px solid #dbeafe;
  border-radius: 11px;
  background: #f8fbff;
}
.payroll-summary-grid span,
.payroll-summary-grid strong,
.payroll-summary-grid small { display: block; }
.payroll-summary-grid span { color: #64748b; font-size: 9.5px; font-weight: 700; text-transform: uppercase; }
.payroll-summary-grid strong { margin-top: 6px; color: #1d4ed8; font-size: 17px; }
.payroll-summary-grid small { margin-top: 3px; color: #64748b; font-size: 9.5px; line-height: 1.35; }
.payroll-summary-grid .summary-late,
.payroll-summary-grid .summary-penalty { border-color: #fed7aa; background: #fff7ed; }
.payroll-summary-grid .summary-late strong,
.payroll-summary-grid .summary-penalty strong { color: #dc2626; }
.payroll-summary-grid .summary-net { border-color: #bbf7d0; background: #f0fdf4; }
.payroll-summary-grid .summary-net strong { color: #15803d; }

.schedule-guide {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 16px 18px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 14px;
}

.schedule-guide-title {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.schedule-guide-title strong { color: #0f172a; font-size: 14px; }
.schedule-guide-title p { margin: 3px 0 0; color: #64748b; font-size: 12.5px; }
.guide-icon {
  display: grid;
  place-items: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  flex: 0 0 24px;
  background: #2563eb;
  color: #fff;
  font-weight: 800;
}

.schedule-slots {
  display: flex;
  align-items: stretch;
  gap: 8px;
  flex-shrink: 0;
}

.schedule-slots > div {
  min-width: 126px;
  padding: 8px 12px;
  background: #fff;
  border: 1px solid #dbeafe;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.schedule-slots span { font-size: 11px; color: #64748b; }
.schedule-slots strong { font-size: 12.5px; color: #1e3a8a; }

/* === STATS GRID === */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.chamcong-stat-card {
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.stat-lbl {
  font-size: 12px;
  color: #64748b !important;
  font-weight: 600 !important;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-val {
  font-size: 22px;
  font-weight: 800 !important;
}

.text-blue { color: #2563eb !important; }
.text-red { color: #dc2626 !important; }
.text-gold { color: #d97706 !important; }
.text-purple { color: #7c3aed !important; }

/* === TABLE CARD === */
.chamcong-table-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.025);
  overflow: hidden;
  padding: 16px;
}

.table-container {
  overflow-x: auto;
  padding-bottom: 8px;
  scrollbar-color: #94a3b8 #e2e8f0;
  scrollbar-width: auto;
}
.table-container::-webkit-scrollbar { height: 12px; }
.table-container::-webkit-scrollbar-track { border-radius: 999px; background: #e2e8f0; }
.table-container::-webkit-scrollbar-thumb { border: 2px solid #e2e8f0; border-radius: 999px; background: #94a3b8; }
.table-container::-webkit-scrollbar-thumb:hover { background: #64748b; }

.table-container::after {
  content: 'Kéo ngang để xem đầy đủ bảng chấm công và tiền lương →';
  position: sticky;
  left: 0;
  display: block;
  width: max-content;
  margin-top: 7px;
  color: #64748b;
  font-size: 10px;
  font-weight: 650;
}

.admin-table {
  width: 100%;
  min-width: 1600px;
  border-collapse: collapse;
  text-align: left;
  table-layout: auto;
}

.admin-table th {
  padding: 14px 16px;
  border-bottom: 1px solid #e2e8f0;
  color: #475569;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  background: #f8fafc;
}

.admin-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13.5px;
  vertical-align: middle;
  color: #1e293b;
}
.admin-table th:nth-child(1), .admin-table td:nth-child(1) { min-width: 240px; }
.admin-table th:nth-child(2), .admin-table td:nth-child(2) { min-width: 135px; }
.admin-table th:nth-child(3), .admin-table td:nth-child(3) { min-width: 175px; }
.admin-table th:nth-child(4), .admin-table td:nth-child(4) { min-width: 145px; }
.admin-table th:nth-child(5), .admin-table td:nth-child(5) { min-width: 145px; }
.admin-table th:nth-child(6), .admin-table td:nth-child(6) { min-width: 135px; text-align: center; }
.admin-table th:nth-child(7), .admin-table td:nth-child(7) { min-width: 135px; }
.admin-table th:nth-child(8), .admin-table td:nth-child(8) { min-width: 320px; }
.admin-table th:nth-child(9), .admin-table td:nth-child(9) { min-width: 125px; }

.admin-table tr:hover td {
  background: #f8fafc;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 1.5px solid #e2e8f0;
}

.user-name {
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.user-email {
  font-size: 11.5px;
  color: #64748b;
  margin: 2px 0 0 0;
}

.history-thumb {
  width: 48px;
  height: 36px;
  border-radius: 6px;
  object-fit: cover;
  cursor: pointer;
  border: 1px solid #cbd5e1;
  transition: transform 0.2s;
}

.history-thumb:hover {
  transform: scale(1.1);
}

.work-date,
.schedule-cell,
.actual-time,
.work-total {
  display: flex;
  flex-direction: column;
  gap: 4px;
  white-space: nowrap;
}

.work-date strong,
.actual-time strong,
.work-total strong { color: #0f172a; font-size: 13px; }
.work-date span,
.schedule-cell span { color: #64748b; font-size: 11.5px; }
.actual-time span { display: flex; align-items: center; gap: 5px; color: #64748b; font-size: 12px; }
.work-total span { color: #d97706; font-size: 12px; font-weight: 700; }
.payroll-note { display: flex; min-width: 130px; flex-direction: column; gap: 3px; }
.payroll-note {
  min-width: 275px;
  padding: 8px 9px;
  border-left: 3px solid #86efac;
  border-radius: 7px;
  background: #f0fdf4;
}
.payroll-note strong { color: #15803d; font-size: 10.5px; }
.payroll-note span { color: #64748b; font-size: 10px; line-height: 1.45; white-space: normal; }
.payroll-note.late { border-left-color: #f87171; background: #fef2f2; }
.payroll-note.late strong { color: #dc2626; }

.dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.dot-in { background: #2563eb; }
.dot-out { background: #7c3aed; }

.attendance-status {
  display: inline-flex;
  align-items: center;
  padding: 5px 8px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 700;
  white-space: nowrap;
}
.status-complete { background: #dcfce7; color: #15803d; }
.status-working { background: #dbeafe; color: #1d4ed8; }
.status-late { background: #fef3c7; color: #b45309; }
.status-missing { background: #fee2e2; color: #b91c1c; }

.evidence-pair { display: flex; align-items: center; gap: 7px; }
.evidence-pair button {
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  color: #64748b;
  font-size: 10px;
}

@media (max-width: 1050px) {
  .payroll-summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .schedule-guide { align-items: stretch; flex-direction: column; }
  .schedule-slots { flex-wrap: wrap; }
}

@media (max-width: 700px) {
  .payroll-heading,
  .payroll-filters { align-items: stretch; flex-direction: column; }
  .view-switcher { display: grid; grid-template-columns: repeat(3, 1fr); }
  .payroll-filters label { min-width: 0; }
  .payroll-summary-grid { grid-template-columns: 1fr 1fr; }
  .payroll-summary-grid .summary-net { grid-column: 1 / -1; }
}

/* Badges */
.badge-danger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fca5a5;
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  white-space: nowrap;
}

.badge-success {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
  white-space: nowrap;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}

.badge-gold {
  background: #fffbeb;
  color: #d97706;
  border: 1px solid #fde68a;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #64748b;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-left-color: #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px auto;
}

@keyframes spin {
  100% { transform: rotate(360deg); }
}

/* Pagination */
.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 4px 4px 4px;
  border-top: 1px solid #e2e8f0;
  margin-top: 12px;
}

.calendar-modal-backdrop {
  position: fixed;
  z-index: 11000;
  inset: 0;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgba(15, 23, 42, .68);
  backdrop-filter: blur(5px);
}
.employee-calendar-modal {
  width: min(1240px, calc(100vw - 36px));
  max-height: calc(100vh - 36px);
  overflow-y: auto;
  padding: 20px;
  border-radius: 18px;
  background: #f8fafc;
  box-shadow: 0 30px 90px rgba(2, 6, 23, .35);
}
.calendar-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}
.calendar-employee { display: flex; align-items: center; gap: 12px; }
.calendar-employee img { width: 48px; height: 48px; border: 2px solid #bfdbfe; border-radius: 50%; object-fit: cover; }
.calendar-employee span { color: #2563eb; font-size: 9.5px; font-weight: 850; letter-spacing: .1em; }
.calendar-employee h3 { margin: 2px 0; color: #0f172a; font-size: 20px; }
.calendar-employee p { margin: 0; color: #64748b; font-size: 11px; }
.calendar-close { display: grid; flex: 0 0 auto; place-items: center; width: 38px; height: 38px; padding: 0; border: 1px solid #dbe3ef; border-radius: 11px; background: #fff; color: #475569; cursor: pointer; transition: background .18s ease, border-color .18s ease, color .18s ease, transform .18s ease; }
.calendar-close svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; }
.calendar-close:hover { border-color: #fecaca; background: #fef2f2; color: #dc2626; transform: translateY(-1px); }
.calendar-close:focus-visible { outline: 3px solid rgba(37, 99, 235, .2); outline-offset: 2px; }
.calendar-close:active { transform: translateY(0); }
.calendar-toolbar { display: flex; align-items: center; justify-content: center; gap: 14px; margin-top: 14px; }
.calendar-toolbar strong { min-width: 130px; color: #0f172a; font-size: 16px; text-align: center; }
.calendar-toolbar button { width: 34px; height: 34px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #2563eb; font-size: 23px; cursor: pointer; }
.calendar-payroll-summary {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 9px;
  margin-top: 14px;
}
.calendar-payroll-summary div { padding: 10px 12px; border: 1px solid #dbeafe; border-radius: 10px; background: #fff; }
.calendar-payroll-summary span,
.calendar-payroll-summary strong { display: block; }
.calendar-payroll-summary span { color: #64748b; font-size: 9.5px; font-weight: 700; text-transform: uppercase; }
.calendar-payroll-summary strong { margin-top: 4px; color: #1d4ed8; font-size: 14px; }
.calendar-payroll-summary .deduction { border-color: #fecaca; background: #fff7f7; }
.calendar-payroll-summary .deduction strong { color: #dc2626; }
.calendar-payroll-summary .net { border-color: #bbf7d0; background: #f0fdf4; }
.calendar-payroll-summary .net strong { color: #15803d; }
.calendar-loading { display: grid; min-height: 430px; place-items: center; color: #64748b; font-size: 13px; }
.employee-calendar {
  display: grid;
  grid-template-columns: repeat(7, minmax(120px, 1fr));
  gap: 7px;
  margin-top: 14px;
  overflow-x: auto;
}
.calendar-weekday { padding: 7px; color: #475569; font-size: 10px; font-weight: 800; text-align: center; }
.calendar-day {
  position: relative;
  display: flex;
  min-height: 112px;
  padding: 9px;
  border: 1px solid #dbe3ef;
  border-radius: 10px;
  flex-direction: column;
  align-items: flex-start;
  gap: 5px;
  background: #fff;
  color: #334155;
  text-align: left;
  cursor: pointer;
}
.calendar-day:hover:not(:disabled) { border-color: #60a5fa; box-shadow: 0 4px 12px rgba(37, 99, 235, .1); }
.calendar-day.muted { opacity: .35; background: #f1f5f9; }
.calendar-day.today { box-shadow: inset 0 0 0 2px #60a5fa; }
.calendar-day.worked { border-color: #bbf7d0; background: #f7fff9; }
.calendar-day.late { border-color: #fecaca; background: #fff8f8; }
.day-number { display: grid; width: 24px; height: 24px; place-items: center; border-radius: 7px; color: #0f172a; font-size: 11px; font-weight: 800; background: #f1f5f9; }
.calendar-day.today .day-number { background: #2563eb; color: #fff; }
.calendar-day small { color: #64748b; font-size: 9.5px; }
.calendar-day > strong { margin-top: auto; color: #15803d; font-size: 12px; }
.calendar-day em { color: #dc2626; font-size: 9.5px; font-style: normal; font-weight: 700; }
.calendar-day em.on-time { color: #15803d; }
.calendar-day .no-record { margin-top: auto; color: #cbd5e1; }
.calendar-day-detail {
  display: grid;
  grid-template-columns: minmax(260px, 2fr) repeat(5, minmax(100px, 1fr));
  gap: 8px;
  margin-top: 12px;
  padding: 12px;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  background: #fff;
}
.calendar-day-detail > div { padding: 7px 9px; border-right: 1px solid #e2e8f0; }
.calendar-day-detail > div:last-child { border-right: 0; }
.calendar-day-detail span,
.calendar-day-detail strong { display: block; }
.calendar-day-detail span { color: #64748b; font-size: 8.5px; font-weight: 750; text-transform: uppercase; }
.calendar-day-detail strong { margin-top: 4px; color: #0f172a; font-size: 10.5px; line-height: 1.4; }
.calendar-day-detail .detail-penalty strong { color: #dc2626; }
.calendar-day-detail .detail-net strong { color: #15803d; }
.calendar-hint { margin: 10px 0 0; color: #64748b; font-size: 10px; text-align: center; }

@media (max-width: 800px) {
  .employee-calendar-modal { padding: 14px; }
  .calendar-payroll-summary { grid-template-columns: 1fr 1fr; }
  .employee-calendar { grid-template-columns: repeat(7, minmax(105px, 1fr)); }
  .calendar-day-detail { grid-template-columns: 1fr 1fr; }
  .calendar-day-detail > div { border-right: 0; border-bottom: 1px solid #e2e8f0; }
}

.pagination-info {
  font-size: 12.5px;
  color: #64748b;
  margin: 0;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 6px;
}

.p-arrow {
  padding: 6px 12px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12.5px;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.2s;
}

.p-arrow:hover:not(:disabled) {
  background: #f1f5f9;
}

.p-arrow:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.p-nums {
  display: flex;
  gap: 4px;
}

.p-num {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 12.5px;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.2s;
}

.p-num:hover {
  background: #f1f5f9;
}

.p-num.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
}

.font-bold { font-weight: 700; }
.text-gray { color: #64748b; }
</style>
