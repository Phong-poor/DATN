<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// Initialize as true so that on first hard reload (F5) the user sees the loader instantly
const isVisible = ref(true)
let loadingCount = 1
let showTimeout = null
let hideTimeout = null
let maxTimeout = null

const clearTimers = () => {
  clearTimeout(showTimeout)
  clearTimeout(hideTimeout)
  clearTimeout(maxTimeout)
}

const resetLoading = () => {
  loadingCount = 0
  clearTimers()
  isVisible.value = false
}

const startLoading = () => {
  loadingCount++
  if (loadingCount !== 1) return

  clearTimers()
  showTimeout = setTimeout(() => {
    isVisible.value = true
  }, 50) // Snappy entry on navigation

  // Limit loading to 8s max to avoid stuck overlays
  maxTimeout = setTimeout(resetLoading, 8000)
}

const stopLoading = () => {
  loadingCount = Math.max(0, loadingCount - 1)
  if (loadingCount > 0) return

  clearTimeout(showTimeout)
  hideTimeout = setTimeout(() => {
    isVisible.value = false
    clearTimers()
  }, 250) // Quick, seamless exit
}

onMounted(() => {
  window.addEventListener('global-loader-show', startLoading)
  window.addEventListener('global-loader-hide', stopLoading)

  // Auto-hide the initial load after 200ms since pages now render from cache instantly
  setTimeout(() => {
    stopLoading()
  }, 200)
})

onUnmounted(() => {
  window.removeEventListener('global-loader-show', startLoading)
  window.removeEventListener('global-loader-hide', stopLoading)
  resetLoading()
})
</script>

<template>
  <Transition name="loader-fade">
    <div v-if="isVisible" class="global-loader-overlay" aria-label="Đang tải dữ liệu">
      <div class="loader-content">
        <!-- Sleek Minimalist Spinner -->
        <div class="spinner-wrapper">
          <div class="minimal-spinner"></div>
          <div class="logo-center">
            <span class="logo-text">NextGen</span>
          </div>
        </div>

        <!-- Clean, Sophisticated Text -->
        <div class="loading-text">
          <span>Đang tải trang</span>
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
  background: rgba(15, 17, 23, 0.65);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
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
  gap: 16px;
}

/* Minimalist Spinner Container */
.spinner-wrapper {
  position: relative;
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Clean circular line spinner */
.minimal-spinner {
  width: 68px;
  height: 68px;
  border: 2px solid rgba(255, 255, 255, 0.08);
  border-top: 2px solid #2563eb;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}

.logo-center {
  position: absolute;
}

.logo-text {
  font-family: 'Outfit', 'Inter', sans-serif;
  font-weight: 700;
  font-size: 12.5px;
  letter-spacing: 0.5px;
  color: #ffffff;
  opacity: 0.95;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* loading-text style: luxury aesthetic */
.loading-text {
  display: flex;
  align-items: center;
  gap: 4px;
  font-family: 'Inter', sans-serif;
  font-size: 10.5px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.55);
  letter-spacing: 1.5px;
  text-transform: uppercase;
}

/* Dot animation: soft premium fade-in-out dots */
.dots-loading {
  display: inline-flex;
  align-items: center;
  gap: 2px;
}

.dots-loading .dot {
  width: 3px;
  height: 3px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.55);
  animation: dot-blink 1.4s infinite both;
}

.dots-loading .dot:nth-child(2) {
  animation-delay: 0.2s;
}

.dots-loading .dot:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes dot-blink {
  0%, 80%, 100% {
    opacity: 0.25;
    transform: scale(0.85);
  }
  40% {
    opacity: 1;
    transform: scale(1.05);
  }
}

/* Smooth Transitions */
.loader-fade-enter-active,
.loader-fade-leave-active {
  transition: opacity 0.25s ease;
}

.loader-fade-enter-from,
.loader-fade-leave-to {
  opacity: 0;
}
</style>
