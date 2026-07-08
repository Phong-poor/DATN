<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'

const tickets = ref(0)
let ticketCheckInterval = null

const updateTickets = () => {
  tickets.value = parseInt(localStorage.getItem('vongquay_tickets') || '0')
}

const fetchUserProfile = async () => {
  try {
    const res = await api.get('/user/profile')
    if (res.data) {
      tickets.value = parseInt(res.data.luot_quay ?? 0)
      localStorage.setItem('vongquay_tickets', tickets.value.toString())
    }
  } catch (e) {
    // Ignore error if not logged in
  }
}

onMounted(() => {
  updateTickets()
  fetchUserProfile()
  // Poll localStorage every second to ensure the badge updates if the user claims or spins
  ticketCheckInterval = setInterval(updateTickets, 1000)
})

onUnmounted(() => {
  if (ticketCheckInterval) clearInterval(ticketCheckInterval)
})

const openLuckyWheel = () => {
  window.dispatchEvent(new Event('open-lucky-wheel'))
}
</script>

<template>
  <div @click="openLuckyWheel" class="floating-wheel-widget" title="Vòng quay may mắn">
    <div class="wheel-icon-box">
      <img src="/images.jpg" alt="Vòng quay may mắn" class="wheel-img" />
    </div>
    
  </div>
</template>

<style scoped>
.floating-wheel-widget {
  position: fixed;
  left: 30px;
  bottom: 30px;
  width: 62px;
  height: 62px;
  border-radius: 50%;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  box-shadow: 
    0 8px 24px rgba(239, 68, 68, 0.35), 
    0 0 15px rgba(234, 179, 8, 0.25);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  text-decoration: none;
  padding: 0;
}

.wheel-icon-box {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
}

.wheel-img {
  width: 62px;
  height: 62px;
  border-radius: 50%;
  object-fit: cover;
  display: block;
}

.wheel-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #ffffff;
  color: #ef4444;
  font-family: 'Outfit', 'Inter', sans-serif;
  font-size: 11px;
  font-weight: 800;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px solid #ef4444;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  animation: pulse 2s infinite;
}

/* Tooltip on Hover */
.wheel-tooltip {
  position: absolute;
  left: 70px;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 8px 14px;
  border-radius: 10px;
  color: #ffffff;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transform: translateX(-15px) scale(0.9);
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
  z-index: 9998;
}

.wheel-tooltip::before {
  content: '';
  position: absolute;
  left: -6px;
  top: 50%;
  transform: translateY(-50%) rotate(45deg);
  width: 10px;
  height: 10px;
  background: #0f172a;
  border-left: 1px solid rgba(255, 255, 255, 0.1);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tooltip-title {
  font-family: 'Outfit', sans-serif;
  font-size: 13px;
  font-weight: 700;
  color: #facc15;
}

.tooltip-subtitle {
  font-size: 10px;
  color: #94a3b8;
  margin-top: 1px;
}

/* Hover States */
.floating-wheel-widget:hover {
  transform: scale(1.1) translateY(-2px);
  box-shadow: 
    0 12px 30px rgba(239, 68, 68, 0.6), 
    0 0 20px rgba(234, 179, 8, 0.45);
  border-color: #facc15;
}

.floating-wheel-widget:hover .svg-wheel {
  animation-duration: 2.5s; /* Spins faster on hover */
}

.floating-wheel-widget:hover .wheel-tooltip {
  opacity: 1;
  transform: translateX(0) scale(1);
}

.floating-wheel-widget:active {
  transform: scale(0.95);
}

/* Keyframes */
@keyframes spin-slow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.15); }
  100% { transform: scale(1); }
}

@keyframes float {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-5px); }
  100% { transform: translateY(0px); }
}

/* Hide on mobile devices to prevent layout clutter */
@media (max-width: 600px) {
  .floating-wheel-widget {
    left: 20px;
    bottom: 20px;
    width: 48px;
    height: 48px;
  }
  .wheel-tooltip {
    display: none; /* Hide tooltip on small screens */
  }
}
</style>
