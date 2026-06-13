<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const isVisible = ref(false)
let loadingCount = 0
let showTimeout = null
let hideTimeout = null
let maxTimeout = null
let visibleAt = 0
let minVisibleMs = 180

const clearTimers = () => {
  clearTimeout(showTimeout)
  clearTimeout(hideTimeout)
  clearTimeout(maxTimeout)
}

const resetLoading = () => {
  loadingCount = 0
  clearTimers()
  isVisible.value = false
  visibleAt = 0
  minVisibleMs = 180
}

const revealLoader = () => {
  visibleAt = Date.now()
  isVisible.value = true
}

const startLoading = (event) => {
  const options = event?.detail || {}
  const showImmediately = Boolean(options.immediate)
  minVisibleMs = Number.isFinite(options.minDuration) ? options.minDuration : minVisibleMs
  loadingCount++
  if (loadingCount !== 1) return

  clearTimers()
  if (showImmediately) {
    revealLoader()
  } else {
    showTimeout = setTimeout(revealLoader, 180)
  }

  // Limit loading to 8s max to avoid stuck overlays
  maxTimeout = setTimeout(resetLoading, 8000)
}

const stopLoading = () => {
  loadingCount = Math.max(0, loadingCount - 1)
  if (loadingCount > 0) return

  clearTimeout(showTimeout)
  const elapsed = visibleAt ? Date.now() - visibleAt : minVisibleMs
  const wait = Math.max(40, minVisibleMs - elapsed)
  hideTimeout = setTimeout(() => {
    isVisible.value = false
    clearTimers()
  }, wait)
}

const forceHideLoading = () => {
  resetLoading()
}

onMounted(() => {
  window.addEventListener('global-loader-show', startLoading)
  window.addEventListener('global-loader-hide', stopLoading)
  window.addEventListener('global-loader-force-hide', forceHideLoading)
})

onUnmounted(() => {
  window.removeEventListener('global-loader-show', startLoading)
  window.removeEventListener('global-loader-hide', stopLoading)
  window.removeEventListener('global-loader-force-hide', forceHideLoading)
  resetLoading()
})
</script>

<template>
  <Transition name="loader-fade">
    <div v-if="isVisible" class="global-loader-overlay" aria-label="Đang tải dữ liệu">
      <div class="loader-content">
        <!-- Sleek Premium Spinner -->
        <div class="spinner-wrapper">
          <!-- Ambient Glow Background -->
          <div class="spinner-glow"></div>
          
          <!-- Outer Pulsing Ring -->
          <div class="ring-outer"></div>
          
          <!-- Middle Spinning Ring (Cyan-Purple Gradient) -->
          <div class="ring-middle"></div>
          
          <!-- Inner Reverse Spinning Ring (Blue) -->
          <div class="ring-inner"></div>
          
          <div class="logo-center">
            <span class="logo-text">Predator</span>
          </div>
        </div>

        <!-- Clean, Sophisticated Text -->
        <div class="loading-text">
          <span class="loading-label">Đang tải trang</span>
          <span class="dots-loading">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
          </span>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.global-loader-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100vw;
  height: 100vh;
  background: radial-gradient(circle at center, rgba(15, 23, 42, 0.75) 0%, rgba(8, 10, 15, 0.96) 100%);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2147483647; /* Keep on top of all headers and modals */
  pointer-events: all; /* Prevent background actions while loading */
}

.loader-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 24px;
}

/* Minimalist Spinner Container */
.spinner-wrapper {
  position: relative;
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Background Soft Ambient Glow */
.spinner-glow {
  position: absolute;
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.22) 0%, rgba(168, 85, 247, 0.08) 50%, transparent 70%);
  filter: blur(12px);
  animation: glow-pulse 3s ease-in-out infinite alternate;
  z-index: 1;
}

/* Outer Pulsing Ring */
.ring-outer {
  position: absolute;
  width: 106px;
  height: 106px;
  border: 1px solid rgba(168, 85, 247, 0.15);
  border-radius: 50%;
  animation: pulse-ring 2.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
  z-index: 1;
}

/* Middle Spinning Ring (Blue-Purple Gradient) */
.ring-middle {
  position: absolute;
  width: 86px;
  height: 86px;
  border: 2px solid transparent;
  border-top: 2px solid #3b82f6;
  border-right: 2px solid #a855f7;
  border-radius: 50%;
  animation: spin-clockwise 1.4s cubic-bezier(0.4, 0.1, 0.2, 0.9) infinite;
  z-index: 2;
}

/* Inner Reverse Spinning Ring (Cyan-Blue) */
.ring-inner {
  position: absolute;
  width: 70px;
  height: 70px;
  border: 2px solid transparent;
  border-bottom: 2px solid #06b6d4;
  border-left: 2px solid #3b82f6;
  border-radius: 50%;
  animation: spin-counter 0.9s cubic-bezier(0.4, 0.1, 0.2, 0.9) infinite;
  z-index: 2;
}

.logo-center {
  position: absolute;
  z-index: 3;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.logo-text {
  font-family: 'Outfit', 'Inter', sans-serif;
  font-weight: 800;
  font-size: 13.5px;
  letter-spacing: 0.8px;
  background: linear-gradient(135deg, #ffffff 30%, #93c5fd 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: logo-breath 2s ease-in-out infinite alternate;
}

@keyframes spin-clockwise {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes spin-counter {
  0% { transform: rotate(360deg); }
  100% { transform: rotate(0deg); }
}

@keyframes glow-pulse {
  0% { transform: scale(0.9); opacity: 0.5; }
  100% { transform: scale(1.15); opacity: 0.95; }
}

@keyframes pulse-ring {
  0% { transform: scale(0.85); opacity: 0.1; }
  50% { opacity: 0.6; }
  100% { transform: scale(1.15); opacity: 0; }
}

@keyframes logo-breath {
  0% { 
    transform: scale(0.97); 
    opacity: 0.85; 
    filter: drop-shadow(0 1px 3px rgba(59, 130, 246, 0.2)); 
  }
  100% { 
    transform: scale(1.03); 
    opacity: 1; 
    filter: drop-shadow(0 4px 12px rgba(59, 130, 246, 0.5)); 
  }
}

/* loading-text style: luxury aesthetic */
.loading-text {
  display: flex;
  align-items: center;
  gap: 6px;
  font-family: 'Outfit', 'Inter', sans-serif;
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 2px;
  text-transform: uppercase;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
}

.loading-label {
  background: linear-gradient(to right, #cbd5e1, #94a3b8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Dot animation: soft premium fade-in-out dots */
.dots-loading {
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

.dots-loading .dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background-color: #3b82f6;
  box-shadow: 0 0 6px rgba(59, 130, 246, 0.8);
  animation: dot-blink 1.4s infinite both;
}

.dots-loading .dot:nth-child(2) {
  background-color: #6366f1;
  box-shadow: 0 0 6px rgba(99, 102, 241, 0.8);
  animation-delay: 0.2s;
}

.dots-loading .dot:nth-child(3) {
  background-color: #a855f7;
  box-shadow: 0 0 6px rgba(168, 85, 247, 0.8);
  animation-delay: 0.4s;
}

@keyframes dot-blink {
  0%, 80%, 100% {
    opacity: 0.3;
    transform: scale(0.8);
  }
  40% {
    opacity: 1;
    transform: scale(1.2);
  }
}

/* Smooth Transitions */
.loader-fade-enter-active,
.loader-fade-leave-active {
  transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.loader-fade-enter-from,
.loader-fade-leave-to {
  opacity: 0;
}
</style>
