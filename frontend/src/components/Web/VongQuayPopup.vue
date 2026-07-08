<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'

const emit = defineEmits(['close'])

// State variables loaded from DB/API
const prizes = ref([])
const tickets = ref(0)
const history = ref([])
const isSpinning = ref(false)
const currentAngle = ref(0)

// Canvas properties
const wheelCanvas = ref(null)
let ctx = null
let animationFrameId = null


// Web Audio API Context for synthesized tick sounds
let audioCtx = null

// Fetch data from backend
const fetchPrizes = async () => {
  try {
    const res = await api.get('/vong-quay/prizes')
    if (res.data?.success) {
      prizes.value = res.data.data
      drawWheel()
    }
  } catch (e) {
    console.error('Không thể tải danh sách phần thưởng:', e)
  }
}

const fetchHistory = async () => {
  try {
    const res = await api.get('/vong-quay/lich-su')
    if (res.data?.success) {
      history.value = res.data.data.map(h => ({
        id: h.id_lichsu,
        prizeName: h.ten_qua,
        type: h.loai_qua,
        value: h.gia_tri_qua,
        time: new Date(h.created_at).toLocaleString('vi-VN')
      }))
    }
  } catch (e) {
    console.error('Không thể tải lịch sử quay:', e)
  }
}

const fetchUserProfile = async () => {
  try {
    const res = await api.get('/user/profile')
    if (res.data) {
      tickets.value = parseInt(res.data.luot_quay ?? 0)
      localStorage.setItem('vongquay_tickets', tickets.value)
    }
  } catch (e) {
    console.error('Không thể tải thông tin lượt quay:', e)
  }
}


// Draw the Lucky Wheel on Canvas
const drawWheel = () => {
  if (!wheelCanvas.value || prizes.value.length === 0) return
  const canvas = wheelCanvas.value
  ctx = canvas.getContext('2d')
  
  const dpr = window.devicePixelRatio || 1
  const width = canvas.clientWidth
  const height = canvas.clientHeight
  
  if (canvas.width !== width * dpr || canvas.height !== height * dpr) {
    canvas.width = width * dpr
    canvas.height = height * dpr
  }
  
  ctx.save()
  ctx.scale(dpr, dpr)
  ctx.clearRect(0, 0, width, height)
  
  const cx = width / 2
  const cy = height / 2
  const radius = Math.min(width, height) / 2 - 20 // dynamic padding
  const numSectors = prizes.value.length
  const sectorAngle = (2 * Math.PI) / numSectors
  
  // 1. Draw neon outer shadow glow
  ctx.shadowColor = 'rgba(239, 68, 68, 0.4)'
  ctx.shadowBlur = 12
  
  // 2. Draw Outer Rim / Wheel Border
  ctx.beginPath()
  ctx.arc(cx, cy, radius + 8, 0, 2 * Math.PI)
  ctx.fillStyle = '#0f172a'
  ctx.fill()
  
  ctx.shadowBlur = 0
  
  // Outer metal ring
  ctx.beginPath()
  ctx.arc(cx, cy, radius + 8, 0, 2 * Math.PI)
  ctx.strokeStyle = '#334155'
  ctx.lineWidth = 3
  ctx.stroke()
  
  // 3. Draw Sectors
  for (let i = 0; i < numSectors; i++) {
    const angleStart = currentAngle.value + i * sectorAngle
    const angleEnd = angleStart + sectorAngle
    const prize = prizes.value[i]
    
    ctx.beginPath()
    ctx.moveTo(cx, cy)
    ctx.arc(cx, cy, radius, angleStart, angleEnd)
    ctx.closePath()
    
    ctx.fillStyle = prize.mau_sac || '#475569'
    ctx.fill()
    
    // Draw separator lines
    ctx.beginPath()
    ctx.moveTo(cx, cy)
    ctx.lineTo(cx + Math.cos(angleStart) * radius, cy + Math.sin(angleStart) * radius)
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)'
    ctx.lineWidth = 2
    ctx.stroke()
    
    // 4. Draw Prize text & icons
    ctx.save()
    ctx.translate(cx, cy)
    ctx.rotate(angleStart + sectorAngle / 2)
    ctx.textAlign = 'right'
    ctx.textBaseline = 'middle'
    ctx.fillStyle = prize.mau_chu || '#ffffff'
    ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif'
    
    let text = prize.ten
    if (prize.loai === 'voucher') text = '🎟️ ' + text
    else if (prize.loai === 'gift') text = '🎁 ' + text
    else if (prize.loai === 'coin') text = '🪙 ' + text
    else if (prize.loai === 'ticket') text = '⚡ ' + text
    else text = '☘️ ' + text
    
    ctx.fillText(text, radius - 18, 0)
    ctx.restore()
  }
  

  // 6. Draw Center Core Base
  ctx.beginPath()
  ctx.arc(cx, cy, 26, 0, 2 * Math.PI)
  ctx.fillStyle = '#1e293b'
  ctx.shadowColor = 'rgba(0, 0, 0, 0.4)'
  ctx.shadowBlur = 5
  ctx.fill()
  ctx.shadowBlur = 0
  
  ctx.beginPath()
  ctx.arc(cx, cy, 26, 0, 2 * Math.PI)
  ctx.strokeStyle = '#eab308'
  ctx.lineWidth = 2
  ctx.stroke()
  
  ctx.restore()
}

// Handle Win Reward Output
const handleWinOutput = (prize) => {
  if (prize.loai === 'ticket') {
    swal.success('Trúng Thêm Lượt!', `Chúc mừng bạn đã trúng thêm ${prize.giatri} lượt quay miễn phí!`)
  } else if (prize.loai === 'retry') {
    swal.info('May Mắn Lần Sau', 'Chúc bạn may mắn hơn ở các lượt quay tiếp theo!')
  } else {
    swal.success('Chúc Mừng Bạn!', `Bạn đã trúng thưởng: ${prize.ten}. Phần quà đã được thêm vào tài khoản của bạn.`)
  }
}

// Start Backend-verified Spin Logic
const startSpin = async () => {
  if (isSpinning.value) return

  // Validate: need at least 2 prize slots
  if (prizes.value.length < 2) {
    swal.error('Vòng quay chưa sẵn sàng', 'Vòng quay cần có ít nhất 2 ô phần thưởng mới có thể quay. Vui lòng liên hệ Admin!')
    return
  }

  // if (tickets.value <= 0) {
  //   swal.warning('Hết lượt quay', 'Hãy nhận thêm lượt quay miễn phí để tiếp tục chơi nhé!')
  //   return
  // }
  
  try {
    const res = await api.post('/vong-quay/quay')
    if (!res.data?.success) {
      swal.error('Lỗi', res.data?.message || 'Không thể thực hiện quay.')
      return
    }

    const targetIndex = res.data.winningIndex
    const updatedTickets = res.data.tickets
    
    // Update local tickets count immediately
    tickets.value = updatedTickets
    localStorage.setItem('vongquay_tickets', updatedTickets.toString())
    
    isSpinning.value = true
    const numSectors = prizes.value.length
    const sectorAngle = (2 * Math.PI) / numSectors
    
    const minSpins = 6
    const currentAngleStart = currentAngle.value
    
    const baseAngle = 1.5 * Math.PI - (targetIndex + 0.5) * sectorAngle
    let diffAngle = baseAngle - (currentAngleStart % (2 * Math.PI))
    if (diffAngle < 0) {
      diffAngle += 2 * Math.PI
    }
    
    const targetRot = currentAngleStart + minSpins * 2 * Math.PI + diffAngle
    const duration = 5000
    const startTime = performance.now()
    
    let lastSector = Math.floor((((1.5 * Math.PI - currentAngleStart) % (2 * Math.PI)) + 2 * Math.PI) % (2 * Math.PI) / sectorAngle)
    
    const animate = (now) => {
      const elapsed = now - startTime
      
      if (elapsed >= duration) {
        currentAngle.value = targetRot
        isSpinning.value = false
        drawWheel()
        
        // Show result popup
        handleWinOutput(res.data.prize)
        // Refresh local history list from database
        fetchHistory()
        return
      }
      
      const t = elapsed / duration
      const easeT = 1 - Math.pow(1 - t, 3.5)
      currentAngle.value = currentAngleStart + (targetRot - currentAngleStart) * easeT
      
      const curAng = currentAngle.value
      const currentSector = Math.floor((((1.5 * Math.PI - curAng) % (2 * Math.PI)) + 2 * Math.PI) % (2 * Math.PI) / sectorAngle)
      
      if (currentSector !== lastSector) {
        lastSector = currentSector
      }
      
      drawWheel()
      animationFrameId = requestAnimationFrame(animate)
    }
    
    animationFrameId = requestAnimationFrame(animate)
    
  } catch (e) {
    swal.error('Lỗi', e?.response?.data?.message || 'Có lỗi xảy ra khi thực hiện quay.')
  }
}

// Claim daily ticket logic
const claimDailyTicket = async () => {
  try {
    const res = await api.post('/vong-quay/nhan-luot')
    if (res.data?.success) {
      tickets.value = res.data.tickets
      localStorage.setItem('vongquay_tickets', res.data.tickets.toString())
      swal.success('Thành Công!', res.data.message)
    }
  } catch (e) {
    swal.error('Không thể nhận lượt', e?.response?.data?.message || 'Đã xảy ra lỗi khi nhận lượt.')
  }
}

// Lock background scroll when popup is open
const lockScroll = () => {
  document.body.style.overflow = 'hidden'
}

const unlockScroll = () => {
  document.body.style.overflow = ''
}

// Init elements and canvas watchers
onMounted(async () => {
  lockScroll()
  
  // Parallel fetch wheel setup, history, and tickets
  await fetchPrizes()
  await fetchHistory()
  await fetchUserProfile()
  

  window.addEventListener('resize', drawWheel)
})

onUnmounted(() => {
  unlockScroll()
  if (animationFrameId) cancelAnimationFrame(animationFrameId)
  window.removeEventListener('resize', drawWheel)
})
</script>

<template>
  <div class="wheel-modal-overlay" @click.self="emit('close')">
    <div class="wheel-modal-container glass-modal-card">
      
      <!-- Close Button -->
      <button class="modal-close-btn" @click="emit('close')" aria-label="Đóng popup">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
      
      <!-- HEADER -->
      <div class="modal-header">
        <div class="banner-badge">LUCKY WHEEL</div>
        <h2 class="modal-title">VÒNG QUAY MAY MẮN NEXTGEN</h2>
        <p class="modal-subtitle">Xoay vòng quay nhận coupon và những phần quà Gaming hấp dẫn!</p>
      </div>
      
      <!-- GRID GAME CONTENT -->
      <div class="modal-grid">
        
        <!-- LEFT: THE CANVAS WHEEL -->
        <div class="wheel-section">
          <div class="wheel-wrapper">
            <!-- Pin Arrow -->
            <div class="wheel-pointer">
              <svg viewBox="0 0 24 24" class="pointer-svg">
                <path d="M12 21L3 6h18L12 21z" fill="#facc15" stroke="#1e293b" stroke-width="2" />
                <path d="M12 18L5 7h14l-7 11z" fill="#f97316" />
              </svg>
            </div>
            
            <!-- Canvas container -->
            <div class="canvas-container">
              <canvas ref="wheelCanvas" class="wheel-canvas"></canvas>
              
              <!-- Absolute Center Trigger Button -->
              <button 
                class="spin-trigger-btn" 
                :disabled="isSpinning" 
                @click="startSpin"
                :class="{ 'spinning': isSpinning }"
              >
                <span class="trigger-label">QUAY</span>
              </button>
            </div>
          </div>
          
          <div class="tickets-hud">
            <span class="ticket-icon">⚡</span>
            <span>Mỗi ngày <strong>1</strong> lượt quay miễn phí</span>
          </div>
        </div>
        
        <!-- RIGHT: SIDEBAR CONTROLS -->
        <div class="sidebar-section">
          

          

          <!-- SPIN HISTORY -->
          <div class="section-card history-container">
            <div class="history-hdr">
              <h3 class="sect-title">📜 LỊCH SỬ TRÚNG THƯỞNG</h3>
            </div>
            
            <div class="history-scroll" v-if="history.length > 0">
              <div v-for="h in history" :key="h.id" class="history-row">
                <div class="hist-meta">
                  <span class="prize-name" :class="h.type">{{ h.prizeName }}</span>
                  <span class="time">{{ h.time.split(' ')[1] || h.time }}</span>
                </div>
                <div class="hist-code" v-if="h.type === 'voucher' || h.type === 'gift'">
                  {{ h.value }}
                </div>
              </div>
            </div>
            
            <div class="history-empty" v-else>
              <p>Chưa có quà. Hãy xoay ngay!</p>
            </div>
          </div>
          
        </div>
      </div>
      
    </div>
  </div>
</template>

<style scoped>
.wheel-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(2, 6, 23, 0.75);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 10005;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.wheel-modal-container {
  position: relative;
  width: min(850px, 95vw);
  max-height: 90vh;
  overflow-y: auto;
  border-radius: 24px;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 
    0 25px 50px -12px rgba(0, 0, 0, 0.5),
    0 0 30px rgba(239, 68, 68, 0.15);
  padding: 30px;
  color: #f1f5f9;
}

/* Custom Scrollbar for Modal Container */
.wheel-modal-container::-webkit-scrollbar {
  width: 5px;
}
.wheel-modal-container::-webkit-scrollbar-track {
  background: transparent;
}
.wheel-modal-container::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.08);
  border-radius: 4px;
}

/* Close Button */
.modal-close-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  z-index: 10;
}

.modal-close-btn:hover {
  background: #ef4444;
  color: #ffffff;
  border-color: #ef4444;
  transform: rotate(90deg);
}

.modal-close-btn svg {
  width: 16px;
  height: 16px;
}

/* HEADER styling */
.modal-header {
  text-align: center;
  margin-bottom: 24px;
  padding-right: 15px;
  padding-left: 15px;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

.banner-badge {
  display: inline-block;
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
  padding: 4px 10px;
  border-radius: 100px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  margin-bottom: 8px;
}

.modal-title {
  font-family: 'Outfit', sans-serif;
  font-size: clamp(1.2rem, 3.5vw, 1.8rem);
  font-weight: 800;
  margin: 0 0 6px 0;
  overflow-wrap: break-word;
  word-break: break-word;
  width: 100%;
  color: #ffffff;
}

.modal-subtitle {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}

/* GRID SETUP */
.modal-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 30px;
}

@media (max-width: 768px) {
  .modal-grid {
    grid-template-columns: 1fr;
    gap: 24px;
  }
}

/* LEFT COLUMN - WHEEL */
.wheel-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 18px;
  padding: 24px 12px;
}

.wheel-wrapper {
  position: relative;
  width: min(310px, 80vw);
  height: min(310px, 80vw);
  margin-bottom: 20px;
}

.wheel-pointer {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  width: 26px;
  height: 35px;
  z-index: 10;
  filter: drop-shadow(0 3px 4px rgba(0, 0, 0, 0.3));
}

.canvas-container {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 50%;
}

.wheel-canvas {
  width: 100%;
  height: 100%;
  display: block;
}

/* Center button overlay */
.spin-trigger-btn {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #facc15;
  border: 3.5px solid #1e293b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 
    0 0 0 3px rgba(234, 179, 8, 0.2),
    0 6px 12px rgba(0, 0, 0, 0.4),
    inset 0 1.5px 3px rgba(255, 255, 255, 0.5);
  transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.2);
  z-index: 12;
}

.spin-trigger-btn:hover:not(:disabled) {
  transform: translate(-50%, -50%) scale(1.08);
  box-shadow: 
    0 0 15px rgba(234, 179, 8, 0.5),
    0 8px 16px rgba(0, 0, 0, 0.4);
}

.spin-trigger-btn:active:not(:disabled) {
  transform: translate(-50%, -50%) scale(0.95);
}

.spin-trigger-btn:disabled {
  background: #475569;
  border-color: #334155;
  cursor: not-allowed;
  box-shadow: none;
}

.trigger-label {
  font-family: 'Outfit', sans-serif;
  font-size: 12px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: 0.3px;
}

.spin-trigger-btn:disabled .trigger-label {
  color: #94a3b8;
}

/* Tickets HUD */
.tickets-hud {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 100px;
  padding: 8px 18px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #cbd5e1;
}

.ticket-icon {
  font-size: 16px;
}

.tickets-hud strong {
  color: #ef4444;
  font-size: 15px;
  margin: 0 2px;
}

/* RIGHT COLUMN - SIDEBAR */
.sidebar-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.section-card {
  background: rgba(15, 23, 42, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  padding: 16px;
}

.sect-title {
  font-family: 'Outfit', sans-serif;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.5px;
  color: #e2e8f0;
  margin: 0 0 10px 0;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: #ef4444;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
}

.btn-primary:hover {
  filter: brightness(1.1);
  transform: translateY(-1px);
}

.btn-outline {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #94a3b8;
}

.btn-outline:hover {
  background: rgba(255, 255, 255, 0.04);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.2);
}

/* PRIZES TABLE */
.prizes-table {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

@media (max-width: 480px) {
  .prizes-table {
    grid-template-columns: 1fr;
  }
}

.prize-row-item {
  display: flex;
  align-items: center;
  background: rgba(30, 41, 59, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.03);
  border-radius: 8px;
  padding: 8px 10px;
  font-size: 12px;
}

.prize-row-item .dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  margin-right: 8px;
  flex-shrink: 0;
}

.prize-row-item .label {
  color: #cbd5e1;
  margin-right: auto;
  font-weight: 500;
}

.prize-row-item .rate {
  color: #64748b;
  font-size: 10px;
}

/* HISTORY */
.history-container {
  display: flex;
  flex-direction: column;
  max-height: 200px;
}

.history-hdr {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-clear {
  background: transparent;
  border: none;
  color: #64748b;
  font-size: 11px;
  cursor: pointer;
  padding: 0;
}

.btn-clear:hover {
  color: #ef4444;
}

.history-scroll {
  overflow-y: auto;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-right: 3px;
}

.history-scroll::-webkit-scrollbar {
  width: 3px;
}
.history-scroll::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.06);
  border-radius: 3px;
}

.history-row {
  background: rgba(30, 41, 59, 0.4);
  border-left: 2px solid #ef4444;
  padding: 8px;
  border-radius: 0 6px 6px 0;
  font-size: 12px;
}

.hist-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.prize-name {
  font-weight: 600;
}

.prize-name.voucher { color: #facc15; }
.prize-name.gift { color: #f97316; }
.prize-name.coin { color: #06b6d4; }
.prize-name.ticket { color: #10b981; }
.prize-name.retry { color: #94a3b8; }

.hist-meta .time {
  font-size: 10px;
  color: #64748b;
}

.hist-code {
  font-family: monospace;
  font-size: 11px;
  color: #94a3b8;
  background: rgba(15, 23, 42, 0.3);
  padding: 2px 6px;
  border-radius: 3px;
  margin-top: 3px;
  border: 1px dashed rgba(255, 255, 255, 0.04);
}

.history-empty {
  text-align: center;
  padding: 16px 0;
  color: #475569;
  font-size: 12px;
}
</style>
