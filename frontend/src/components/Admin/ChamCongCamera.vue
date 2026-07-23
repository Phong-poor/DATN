<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'
import { getUser } from '@/services/auth'

// === STATES ===
const videoRef = ref(null)
const canvasRef = ref(null)
const stream = ref(null)

const isModelsLoading = ref(false)
const isCameraActive = ref(false)
const isProcessing = ref(false)
const faceDetected = ref(true)

const userStatus = ref({
  face_registered: false,
  checked_in: false,
  checked_out: false,
  today_record: null
})

const myHistory = ref([])
const myRank = ref(-1)
const totalUsersCount = ref(0)
const leaderboardList = ref([])
const currentUser = ref(getUser() || {})

const videoDevices = ref([])
const selectedDeviceId = ref('')

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
    const res = await api.get('/cham-cong/status')
    if (res.data.success) {
      userStatus.value = {
        face_registered: res.data.face_registered,
        checked_in: res.data.checked_in,
        checked_out: res.data.checked_out,
        today_record: res.data.today_record
      }
    }
  } catch (error) {
    console.error('Lỗi tải trạng thái chấm công:', error)
  }
}

async function fetchMyHistory() {
  try {
    const res = await api.get('/cham-cong/my-history')
    if (res.data.success) {
      myHistory.value = res.data.data || []
    }
  } catch (error) {
    console.error('Lỗi tải lịch sử chấm công cá nhân:', error)
  }
}

async function fetchLeaderboard() {
  try {
    const res = await api.get('/cham-cong/leaderboard')
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
  isModelsLoading.value = false
  await nextTick()
  await startCamera()
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
            width: { ideal: 640 },
            height: { ideal: 480 }
          }
        : { 
            facingMode: 'user',
            width: { ideal: 640 },
            height: { ideal: 480 }
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
  canvas.width = videoRef.value.videoWidth || 640
  canvas.height = videoRef.value.videoHeight || 480
  const ctx = canvas.getContext('2d')
  
  // Lật gương ảnh chụp cho giống ảnh preview trên màn hình
  ctx.translate(canvas.width, 0)
  ctx.scale(-1, 1)
  
  ctx.drawImage(videoRef.value, 0, 0, canvas.width, canvas.height)
  return canvas.toDataURL('image/jpeg', 0.85)
}

// Thực hiện chấm công (Check-in / Check-out)
async function handleCheckInOut() {
  isProcessing.value = true
  try {
    const base64Image = captureWebcamImage()

    if (!base64Image) {
      swal.error('Lỗi chụp ảnh', 'Có lỗi xảy ra khi chụp ảnh chấm công. Vui lòng thử lại!')
      isProcessing.value = false
      return
    }

    const res = await api.post('/cham-cong/check', {
      image: base64Image
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
  }
}

// === LIFECYCLE ===
onMounted(async () => {
  await fetchStatus()
  await fetchMyHistory()
  await fetchLeaderboard()
  await getCameraDevices()
  await initFaceApi()
})

onUnmounted(() => {
  stopCamera()
})
</script>

<template>
  <div class="attendance-page">
    <div class="dashboard-grid">
      
      <!-- CỘT BÊN TRÁI: CAMERA VÀ THÔNG SỐ CHẤM CÔNG -->
      <div class="chamcong-card camera-card">
        <div class="card-header">
          <h3 class="card-title">Chấm công bằng Camera</h3>
          <span class="badge badge-success">Chụp ảnh minh chứng</span>
        </div>

        <!-- Khung Camera Glassmorphic -->
        <div class="camera-wrapper">
          <div v-if="isModelsLoading" class="camera-loader">
            <div class="spinner"></div>
            <p>Đang tải mô hình học máy...</p>
          </div>
          
          <div class="video-container">
            <video ref="videoRef" autoplay muted playsinline class="webcam-video"></video>
            
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
          <!-- Dropdown chọn Camera -->
          <div v-if="videoDevices.length > 1" class="camera-select-container">
            <label for="camera-select" class="camera-select-label">Chọn Camera:</label>
            <select id="camera-select" :value="selectedDeviceId" @change="onCameraChange" class="form-select">
              <option v-for="device in videoDevices" :key="device.deviceId" :value="device.deviceId">
                {{ device.label || `Camera ${videoDevices.indexOf(device) + 1}` }}
              </option>
            </select>
          </div>

          <div class="action-check-box">
            <!-- Nếu đã check-in và check-out -->
            <div v-if="userStatus.checked_in && userStatus.checked_out" class="attendance-complete-msg">
              <div class="success-icon">✓</div>
              <div>
                <h4>Đã hoàn thành chấm công!</h4>
                <p>Bạn đã thực hiện Check-in và Check-out đầy đủ trong ngày hôm nay.</p>
              </div>
            </div>
            
            <!-- Nếu chưa checkin hoặc đã checkin nhưng chưa checkout -->
            <div v-else class="check-actions-grid">
              <button 
                @click="handleCheckInOut" 
                class="btn btn-lg btn-block"
                :class="userStatus.checked_in ? 'btn-warning' : 'btn-primary'"
                :disabled="isProcessing || !isCameraActive"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                Chụp ảnh {{ userStatus.checked_in ? 'Check-out' : 'Check-in' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Khung Thông số lớn Premium -->
        <div class="chamcong-stats-grid">
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

      <!-- CỘT BÊN PHẢI: BẢNG XẾP HẠNG CHẤM CÔNG -->
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
              <!-- Huy chương / Thứ hạng -->
              <div class="item-rank">
                <span v-if="index === 0" class="medal medal-gold">🥇</span>
                <span v-else-if="index === 1" class="medal medal-silver">🥈</span>
                <span v-else-if="index === 2" class="medal medal-bronze">🥉</span>
                <span v-else class="rank-text">{{ index + 1 }}</span>
              </div>

              <!-- Avatar & Tên -->
              <img :src="getAvatarUrl(item.anhdaidien, item.ten)" class="item-avatar" alt="Avatar" />
              <div class="item-info">
                <span class="item-name">{{ item.ten }}</span>
                <span class="item-role">{{ item.vaitro === 'admin' ? 'Quản trị' : 'Nhân viên' }}</span>
              </div>

              <!-- Chỉ số công -->
              <div class="item-stats text-right">
                <span class="item-cong">+{{ item.total_cong }} công</span>
                <span class="item-hours">{{ item.total_gio }} giờ làm</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- KHU VỰC BÊN DƯỚI: LỊCH SỬ CHẤM CÔNG CÁ NHÂN -->
    <div class="chamcong-card history-card">
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
  padding: 16px;
  background: #f8fafc;
  min-height: 100%;
  font-family: Inter, sans-serif;
  color: #1e293b;
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-width: 1320px;
  margin: 0 auto;
  width: 100%;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 16px;
}

@media (max-width: 1024px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

/* === CARD GLASSMORPHIC === */
.chamcong-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.025);
  overflow: hidden;
  padding: 16px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
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
  max-width: 420px;
  margin: 0 auto 12px auto;
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

.webcam-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transform: scaleX(-1);
  background: #000000;
}

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
  max-width: 420px;
  margin: 0 auto 14px auto;
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
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 16px;
  border-radius: 12px;
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
  display: flex;
  align-items: center;
  gap: 16px;
  text-align: left;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.3);
  padding: 12px 18px;
  border-radius: 10px;
}

.attendance-complete-msg h4 {
  margin: 0;
  color: #10b981;
}

.attendance-complete-msg p {
  margin: 4px 0 0 0;
  font-size: 13px;
  color: #94a3b8;
}

.success-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #10b981;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: bold;
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
  height: 100%;
  max-height: 560px;
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
  flex: 1;
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
