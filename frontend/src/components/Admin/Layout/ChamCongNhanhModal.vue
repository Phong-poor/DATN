<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'
import { storageUrl } from '@/services/urls'
import swal from '@/services/swal'

const emit = defineEmits(['close', 'success'])
const videoRef = ref(null)
const stream = ref(null)
const loading = ref(true)
const processing = ref(false)
const message = ref('Đang khởi tạo camera...')
const state = ref('loading')
const result = ref(null)
const faceReady = ref(false)
const cooldownSeconds = ref(0)
let faceapi = null
let presenceTimer = null
let presenceRunning = false
let preferredFemaleVoice = null
let cooldownTimer = null

function startCooldown(seconds = 3) {
  window.clearInterval(cooldownTimer)
  cooldownSeconds.value = Math.max(1, Number(seconds) || 3)
  cooldownTimer = window.setInterval(() => {
    cooldownSeconds.value -= 1
    if (cooldownSeconds.value <= 0) {
      cooldownSeconds.value = 0
      window.clearInterval(cooldownTimer)
      cooldownTimer = null
    }
  }, 1000)
}

function apiErrorMessage(error) {
  if (!error?.response) {
    return error?.code === 'ECONNABORTED'
      ? 'Máy chủ phản hồi quá lâu. Vui lòng kiểm tra mạng rồi thử lại.'
      : 'Không kết nối được máy chủ. Vui lòng kiểm tra mạng hoặc backend.'
  }
  const data = error.response.data || {}
  const validationError = data.errors && Object.values(data.errors).flat()[0]
  return validationError || data.message || `Máy chủ trả về lỗi ${error.response.status}. Vui lòng thử lại.`
}

function avatarUrl(avatar, name) {
  if (!avatar) return `https://ui-avatars.com/api/?name=${encodeURIComponent(name || 'NV')}&background=2563eb&color=fff`
  return avatar.startsWith('http') ? avatar : storageUrl(avatar)
}

async function loadModels() {
  faceapi = await import('@vladmandic/face-api')
  if (faceapi.tf) {
    try {
      await faceapi.tf.setBackend('webgl')
      await faceapi.tf.ready()
    } catch (_) {}
  }
  await Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
    faceapi.nets.faceLandmark68TinyNet.loadFromUri('/models'),
    faceapi.nets.faceRecognitionNet.loadFromUri('/models')
  ])
}

async function startCamera() {
  await nextTick()
  stream.value = await navigator.mediaDevices.getUserMedia({
    video: { facingMode: 'user', width: { ideal: 480 }, height: { ideal: 360 } },
    audio: false
  })
  videoRef.value.srcObject = stream.value
  await videoRef.value.play()
}

function captureImage() {
  const video = videoRef.value
  const canvas = document.createElement('canvas')
  const sourceWidth = video.videoWidth || 640
  const sourceHeight = video.videoHeight || 480
  canvas.width = Math.min(480, sourceWidth)
  canvas.height = Math.round(canvas.width * sourceHeight / sourceWidth)
  const context = canvas.getContext('2d')
  context.translate(canvas.width, 0)
  context.scale(-1, 1)
  context.drawImage(video, 0, 0, canvas.width, canvas.height)
  return canvas.toDataURL('image/jpeg', 0.72)
}

function timeToMinutes(time) {
  const [hours = 0, minutes = 0] = String(time || '').split(':').map(Number)
  return hours * 60 + minutes
}

function selectPreferredFemaleVoice() {
  if (!('speechSynthesis' in window)) return null
  const voices = window.speechSynthesis.getVoices()
  const femaleKeywords = [
    'hoaimy', 'hoài my', 'female', 'woman', 'nữ', 'linh', 'mai',
    'zira', 'aria', 'jenny', 'sonia', 'hazel', 'susan', 'samantha',
    'victoria', 'karen', 'moira', 'tessa', 'google uk english female'
  ]
  const maleKeywords = ['namminh', 'nam minh', 'male', 'man']
  const vietnameseVoices = voices.filter(voice => voice.lang?.toLowerCase().startsWith('vi'))
  preferredFemaleVoice =
    vietnameseVoices.find(voice => /hoai\s?my/i.test(voice.name)) ||
    vietnameseVoices.find(voice =>
      femaleKeywords.some(keyword => voice.name.toLowerCase().includes(keyword)) &&
      !maleKeywords.some(keyword => voice.name.toLowerCase().includes(keyword))
    ) ||
    vietnameseVoices[0] ||
    null

  return preferredFemaleVoice
}

function speakVietnamese(text) {
  if (!text || !('speechSynthesis' in window)) return
  window.speechSynthesis.cancel()
  const utterance = new SpeechSynthesisUtterance(text)
  utterance.lang = 'vi-VN'
  utterance.rate = 0.94
  utterance.pitch = 1
  utterance.volume = 1

  const selectedVoice = preferredFemaleVoice || selectPreferredFemaleVoice()
  if (!selectedVoice) return
  utterance.voice = selectedVoice
  window.speechSynthesis.speak(utterance)
}

function buildAttendanceVoice(responseData) {
  const employeeName = responseData.employee?.name || 'nhân viên'
  const record = responseData.record || {}

  if (responseData.type === 'checkin') {
    const lateMinutes = Number(record.di_tre_phut || 0)
    if (lateMinutes > 0) {
      return `Xin chào ${employeeName}. Bạn đã đến muộn ${lateMinutes} phút. Check in đã được xác nhận thành công.`
    }
    return `Xin chào ${employeeName}. Check in đúng giờ đã được xác nhận thành công. Chúc bạn một ngày làm việc hiệu quả.`
  }

  const checkoutTime = record.gio_ra || ''
  if (timeToMinutes(checkoutTime) < 17 * 60 + 30) {
    return `Xin chào ${employeeName}. Bạn check out trước 17 giờ 30, ca làm việc chưa kết thúc. Tuy nhiên, giờ ra về đã được xác nhận thành công.`
  }
  return `Xin chào ${employeeName}. Check out tan ca đã được xác nhận thành công. Cảm ơn bạn đã hoàn thành ngày làm việc.`
}

function schedulePresenceCheck(delay = 650) {
  window.clearTimeout(presenceTimer)
  presenceTimer = window.setTimeout(checkFacePresence, delay)
}

async function checkFacePresence() {
  if (presenceRunning || processing.value || result.value || !faceapi || !videoRef.value || loading.value) {
    schedulePresenceCheck()
    return
  }

  presenceRunning = true
  try {
    const detections = await faceapi.detectAllFaces(
      videoRef.value,
      new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.32 })
    )

    faceReady.value = false
    if (detections.length === 0) {
      state.value = 'no-face'
      message.value = 'Bắt buộc phải có khuôn mặt — không phát hiện mặt nên chưa thể chấm công'
    } else if (detections.length > 1) {
      state.value = 'multiple'
      message.value = 'Chỉ được có một khuôn mặt trong khung hình'
    } else {
      const box = detections[0].box
      const width = videoRef.value.videoWidth || 480
      const height = videoRef.value.videoHeight || 360
      const centerX = (box.x + box.width / 2) / width
      const centerY = (box.y + box.height / 2) / height
      const largeEnough = box.width / width >= 0.18 && box.height / height >= 0.22
      const centered = centerX >= 0.22 && centerX <= 0.78 && centerY >= 0.18 && centerY <= 0.78

      if (!largeEnough) {
        state.value = 'warning'
        message.value = 'Đã thấy khuôn mặt nhưng đang quá xa — vui lòng tiến gần camera'
      } else if (!centered) {
        state.value = 'warning'
        message.value = 'Hãy đưa khuôn mặt vào chính giữa khung quét'
      } else {
        faceReady.value = true
        state.value = 'face-ready'
        message.value = 'Đã phát hiện khuôn mặt hợp lệ — có thể chấm công'
      }
    }
  } catch (error) {
    faceReady.value = false
    state.value = 'error'
    message.value = 'Không thể kiểm tra khuôn mặt — vui lòng bật lại camera'
  } finally {
    presenceRunning = false
    schedulePresenceCheck()
  }
}

async function quickCheck() {
  if (processing.value || cooldownSeconds.value > 0 || !faceapi) return
  if (!faceReady.value) {
    state.value = 'no-face'
    message.value = 'Bắt buộc phải phát hiện đúng một khuôn mặt hợp lệ trước khi chấm công'
    return
  }
  processing.value = true
  result.value = null
  state.value = 'scanning'
  message.value = 'Đang nhận diện nhân viên...'

  try {
    const detections = await faceapi
      .detectAllFaces(videoRef.value, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 }))
      .withFaceLandmarks(true)
      .withFaceDescriptors()

    if (detections.length !== 1) {
      faceReady.value = false
      state.value = 'error'
      message.value = detections.length > 1
        ? 'Chỉ một nhân viên được đứng trước camera'
        : 'Chưa phát hiện khuôn mặt — vui lòng nhìn thẳng vào camera'
      startCooldown(2)
      await swal.warning('Chưa thể chấm công', message.value)
      return
    }

    const response = await api.post('/admin/cham-cong/quick-check', {
      image: captureImage(),
      face_descriptor: Array.from(detections[0].descriptor)
    }, { skipGlobalLoader: true, bypassOffline: true })

    result.value = response.data
    state.value = 'success'
    message.value = response.data.type === 'checkin'
      ? `Check-in thành công lúc ${response.data.record.gio_vao.slice(0, 5)}`
      : `Check-out tan ca thành công lúc ${response.data.record.gio_ra.slice(0, 5)}`
    speakVietnamese(buildAttendanceVoice(response.data))
    emit('success', response.data)
    startCooldown(3)
    const actionLabel = response.data.type === 'checkin' ? 'Check-in' : 'Check-out'
    const attendanceTime = response.data.record.gio_vao || response.data.record.gio_ra
    await swal.success(
      `${actionLabel} thành công`,
      `${response.data.employee?.name || 'Nhân viên'} đã ${actionLabel.toLowerCase()} lúc ${String(attendanceTime || '').slice(0, 5)}.${response.data.warning ? ` ${response.data.warning}` : ''}`
    )
  } catch (error) {
    state.value = 'error'
    message.value = apiErrorMessage(error)
    startCooldown(error.response?.data?.retry_after || 3)
    await swal.error('Chấm công chưa thành công', message.value)
  } finally {
    processing.value = false
  }
}

function close() {
  if (processing.value) return
  if ('speechSynthesis' in window) window.speechSynthesis.cancel()
  emit('close')
}

function resetForNext() {
  if (processing.value || cooldownSeconds.value > 0) return
  result.value = null
  faceReady.value = false
  state.value = 'scanning'
  message.value = 'Đang tìm khuôn mặt nhân viên tiếp theo...'
  schedulePresenceCheck(100)
}

onMounted(async () => {
  if ('speechSynthesis' in window) {
    selectPreferredFemaleVoice()
    window.speechSynthesis.addEventListener('voiceschanged', selectPreferredFemaleVoice)
  }
  try {
    await Promise.all([loadModels(), startCamera()])
    state.value = 'scanning'
    message.value = 'Đang kiểm tra khuôn mặt...'
    schedulePresenceCheck(100)
  } catch (error) {
    state.value = 'error'
    message.value = 'Không thể mở camera hoặc tải bộ nhận diện'
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  window.clearTimeout(presenceTimer)
  window.clearInterval(cooldownTimer)
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel()
    window.speechSynthesis.removeEventListener('voiceschanged', selectPreferredFemaleVoice)
  }
  stream.value?.getTracks().forEach(track => track.stop())
})
</script>

<template>
  <Teleport to="body">
    <div class="quick-attendance-backdrop" @click.self="close">
      <section class="quick-attendance-modal" role="dialog" aria-modal="true" aria-label="Chấm công nhanh">
        <header>
          <div>
            <span>CHẤM CÔNG NHANH</span>
            <h3>Nhận diện nhân viên</h3>
            <p>Hệ thống tự xác định nhân viên và ghi nhận giờ vào hoặc giờ ra.</p>
          </div>
          <button type="button" class="modal-close" :disabled="processing" aria-label="Đóng cửa sổ chấm công" title="Đóng" @click="close">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" />
            </svg>
          </button>
        </header>

        <div class="quick-camera">
          <video ref="videoRef" autoplay muted playsinline></video>
          <div class="face-frame"><i></i><i></i><i></i><i></i></div>
          <div :class="['quick-status', state]">
            <span></span>{{ message }}
          </div>
          <div v-if="loading" class="quick-loading">Đang chuẩn bị nhận diện...</div>
        </div>

        <div v-if="result?.employee" class="recognized-employee">
          <img :src="avatarUrl(result.employee.avatar, result.employee.name)" alt="" />
          <div>
            <span>Đã nhận diện</span>
            <strong>{{ result.employee.name }}</strong>
            <small>{{ result.employee.role_name }} · {{ result.employee.email }}</small>
          </div>
          <b>{{ result.type === 'checkin' ? 'CHECK-IN' : 'TAN CA' }}</b>
        </div>

        <button
          type="button"
          class="quick-check-button"
          :disabled="loading || processing || cooldownSeconds > 0 || (!result && !faceReady)"
          @click="result ? resetForNext() : quickCheck()"
        >
          {{ processing ? 'Đang xác thực, vui lòng chờ...' : (cooldownSeconds > 0 ? `Có thể thao tác tiếp sau ${cooldownSeconds}s` : (result ? 'Chấm công nhân viên tiếp theo' : 'Chấm công ngay')) }}
        </button>
        <small class="privacy-note">Ảnh chỉ được lưu làm minh chứng cho lượt chấm công.</small>
      </section>
    </div>
  </Teleport>
</template>

<style scoped>
.quick-attendance-backdrop{position:fixed;inset:0;z-index:10000;display:grid;place-items:center;padding:20px;background:rgba(2,6,23,.72);backdrop-filter:blur(5px)}
.quick-attendance-modal{width:min(520px,100%);padding:18px;border:1px solid #dbeafe;border-radius:20px;background:#fff;box-shadow:0 30px 80px rgba(2,6,23,.35)}
header{display:flex;justify-content:space-between;gap:16px;margin-bottom:14px}header span{color:#2563eb;font-size:10px;font-weight:800;letter-spacing:.1em}h3{margin:3px 0;color:#0f172a;font-size:21px}header p{margin:0;color:#64748b;font-size:12px}.modal-close{display:grid;flex:0 0 auto;place-items:center;width:38px;height:38px;padding:0;border:1px solid #fecaca;border-radius:11px;background:#fff5f5;color:#ef4444;cursor:pointer;transition:background .18s ease,border-color .18s ease,color .18s ease,transform .18s ease,box-shadow .18s ease}.modal-close:disabled{opacity:.5;cursor:wait}.modal-close svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2.2;stroke-linecap:round}.modal-close:not(:disabled):hover{border-color:#dc2626;background:#dc2626;color:#fff;box-shadow:0 8px 20px rgba(220,38,38,.35);transform:translateY(-2px) scale(1.06)}.modal-close:focus-visible{outline:3px solid rgba(239,68,68,.28);outline-offset:2px}.modal-close:active{transform:translateY(0) scale(.96)}
.quick-camera{position:relative;overflow:hidden;width:100%;aspect-ratio:4/3;border-radius:14px;background:#020617}.quick-camera video{width:100%;height:100%;object-fit:cover;transform:scaleX(-1)}.face-frame{position:absolute;inset:22% 22% 20%}.face-frame i{position:absolute;width:28px;height:28px;border-color:#22d3ee;border-style:solid}.face-frame i:nth-child(1){left:0;top:0;border-width:4px 0 0 4px}.face-frame i:nth-child(2){right:0;top:0;border-width:4px 4px 0 0}.face-frame i:nth-child(3){left:0;bottom:0;border-width:0 0 4px 4px}.face-frame i:nth-child(4){right:0;bottom:0;border-width:0 4px 4px 0}
.quick-status{position:absolute;left:12px;right:12px;bottom:12px;padding:10px 12px;border-radius:10px;background:rgba(15,23,42,.9);color:#fff;text-align:center;font-size:12px;font-weight:700}.quick-status span{display:inline-block;width:7px;height:7px;margin-right:7px;border-radius:50%;background:#60a5fa}.quick-status.success,.quick-status.face-ready{background:rgba(5,150,105,.95)}.quick-status.success span,.quick-status.face-ready span{background:#bbf7d0}.quick-status.error,.quick-status.no-face,.quick-status.multiple{background:rgba(220,38,38,.95)}.quick-status.error span,.quick-status.no-face span,.quick-status.multiple span{background:#fecaca}.quick-status.warning{background:rgba(217,119,6,.95)}.quick-status.warning span{background:#fef3c7}.quick-loading{position:absolute;inset:0;display:grid;place-items:center;background:rgba(2,6,23,.72);color:#fff;font-weight:700}
.recognized-employee{display:grid;grid-template-columns:42px 1fr auto;align-items:center;gap:10px;margin-top:12px;padding:10px;border:1px solid #bbf7d0;border-radius:12px;background:#f0fdf4}.recognized-employee img{width:42px;height:42px;border-radius:50%;object-fit:cover}.recognized-employee div{display:flex;min-width:0;flex-direction:column}.recognized-employee span,.recognized-employee small{color:#64748b;font-size:10px}.recognized-employee strong{color:#0f172a;font-size:13px}.recognized-employee b{padding:5px 8px;border-radius:7px;background:#dcfce7;color:#15803d;font-size:10px}
.quick-check-button{width:100%;min-height:46px;margin-top:12px;border:0;border-radius:11px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 8px 20px rgba(37,99,235,.24)}.quick-check-button:disabled{opacity:.55;cursor:not-allowed}.privacy-note{display:block;margin-top:8px;color:#94a3b8;text-align:center;font-size:10px}

/* DARK MODE OVERRIDES FOR QUICK ATTENDANCE MODAL */
:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .quick-attendance-modal {
  background: #181d24 !important;
  border-color: #28303d !important;
  box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6) !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .quick-attendance-modal h3 {
  color: #f8fafc !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .quick-attendance-modal header p {
  color: #94a3b8 !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .quick-attendance-modal .modal-close {
  background: rgba(239, 68, 68, 0.12) !important;
  border-color: rgba(239, 68, 68, 0.25) !important;
  color: #f87171 !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .quick-attendance-modal .modal-close:hover {
  background: #dc2626 !important;
  color: #ffffff !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .quick-attendance-modal .recognized-employee {
  background: rgba(34, 197, 94, 0.08) !important;
  border-color: rgba(34, 197, 94, 0.25) !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .quick-attendance-modal .recognized-employee strong {
  color: #f8fafc !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .quick-attendance-modal .recognized-employee span,
:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .quick-attendance-modal .recognized-employee small {
  color: #94a3b8 !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .quick-attendance-modal .privacy-note {
  color: #64748b !important;
}
</style>
