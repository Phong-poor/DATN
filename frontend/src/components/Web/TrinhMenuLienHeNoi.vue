<template>
  <div class="floating-menu-container" :class="{ 'menu-open': isOpen }">
    <!-- Menu Options -->
    <transition name="speed-dial">
      <div v-if="isOpen" class="menu-options">
        
        <!-- Option 1: Online Consultation -->
        <button 
          type="button" 
          class="menu-option-btn ai-btn" 
          @click="triggerAction('ai')"
          aria-label="Tư vấn trực tuyến"
        >
          <span class="menu-tooltip">Tư vấn trực tuyến</span>
          <span class="option-icon">💬</span>
        </button>

        <!-- Option 2: Zalo Chat -->
        <button 
          type="button" 
          class="menu-option-btn zalo-btn" 
          @click="triggerAction('zalo')"
          aria-label="Chat Zalo"
        >
          <span class="menu-tooltip">Chat Zalo</span>
          <span class="option-icon">Zalo</span>
        </button>

      </div>
    </transition>

    <!-- Master Button -->
    <button 
      type="button" 
      class="master-menu-btn" 
      @click="toggleMenu" 
      :aria-label="isOpen ? 'Đóng menu' : 'Mở menu liên hệ'"
    >
      <!-- Online Status Badge -->
      <span v-if="!isOpen" class="online-status-badge"></span>

      <div class="icon-wrap" :class="{ 'rotate-icon': isOpen }">
        <div v-if="!isOpen" class="avatar-container">
          <img src="/support_avatar.png" alt="Tư vấn viên" class="assistant-avatar" />
        </div>
        <!-- Close icon when open -->
        <span v-else class="close-icon">✕</span>
      </div>
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const isOpen = ref(false)

const toggleMenu = () => {
  isOpen.value = !isOpen.value
}

const triggerAction = (type) => {
  if (type === 'ai') {
    window.dispatchEvent(new CustomEvent('open-chatbot'))
  } else if (type === 'zalo') {
    window.dispatchEvent(new CustomEvent('open-zalo'))
  }
  isOpen.value = false
}
</script>

<style scoped>

/* ========== FLOATING MENU CONTAINER ========== */
.floating-menu-container {
  position: fixed;
  right: 24px;
  bottom: 28px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}

/* ========== MASTER BUTTON ========== */
.master-menu-btn {
  width: 58px;
  height: 58px;
  border-radius: 50%;
  border: none;
  background: #2563eb;
  padding: 3px; /* Creates a gorgeous glowing border effect around the avatar! */
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 
    0 10px 25px rgba(37, 99, 235, 0.35), 
    0 0 20px rgba(217, 70, 239, 0.2);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
  overflow: visible;
  outline: none;
}

.master-menu-btn:hover {
  transform: translateY(-3px) scale(1.04);
  box-shadow: 
    0 14px 30px rgba(37, 99, 235, 0.45), 
    0 0 25px rgba(217, 70, 239, 0.35);
}

.master-menu-btn:active {
  transform: translateY(-1px) scale(0.96);
}

/* Avatar layout */
.avatar-container {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  overflow: hidden;
  background: #111f35;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.assistant-avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.online-status-badge {
  position: absolute;
  top: 1px;
  right: 1px;
  width: 14px;
  height: 14px;
  background-color: #4ade80;
  border: 2px solid white;
  border-radius: 50%;
  z-index: 10;
  box-shadow: 0 0 10px rgba(96, 165, 250, 0.6);
  animation: status-pulse 2s infinite;
}

@keyframes status-pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(96, 165, 250, 0.7);
  }
  70% {
    box-shadow: 0 0 0 6px rgba(96, 165, 250, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(96, 165, 250, 0);
  }
}

/* Double aura glowing ring effect */
.master-menu-btn::before,
.master-menu-btn::after {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  z-index: -1;
  pointer-events: none;
  opacity: 0;
}

.master-menu-btn::before {
  background: rgba(37, 99, 235, 0.3);
  animation: pulse-aura-1 3s infinite ease-out;
}

.master-menu-btn::after {
  background: rgba(37, 99, 235, 0.15);
  animation: pulse-aura-2 3s infinite ease-out 1.5s;
}

@keyframes pulse-aura-1 {
  0% {
    transform: scale(0.95);
    opacity: 0.8;
  }
  100% {
    transform: scale(1.4);
    opacity: 0;
  }
}

@keyframes pulse-aura-2 {
  0% {
    transform: scale(0.95);
    opacity: 0.8;
  }
  100% {
    transform: scale(1.4);
    opacity: 0;
  }
}

.icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  transition: transform 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.rotate-icon {
  transform: rotate(180deg);
}

.close-icon {
  font-size: 20px;
  font-weight: 700;
  line-height: 1;
  color: white;
}

/* ========== OPTIONS LIST ========== */
.menu-options {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  margin-bottom: 2px;
}

/* ========== OPTION BUTTONS ========== */
.menu-option-btn {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
}

.menu-option-btn:hover {
  transform: scale(1.12) translateY(-2px);
  border-color: rgba(255, 255, 255, 0.5);
}

.menu-option-btn:active {
  transform: scale(0.95);
}

/* ========== TOOLTIPS (Premium Glassmorphism) ========== */
.menu-tooltip {
  position: absolute;
  right: 60px;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #f8fafc;
  font-size: 12px;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 10px;
  white-space: nowrap;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.3);
  opacity: 0;
  transform: translateX(12px) scale(0.9);
  pointer-events: none;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  font-family: inherit;
}

.menu-option-btn:hover .menu-tooltip {
  opacity: 1;
  transform: translateX(0) scale(1);
}

/* Option Button Specifics */
.ai-btn {
  background: #2563eb;
  box-shadow: 0 6px 18px rgba(37, 99, 235, 0.3);
}

.ai-btn:hover {
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.45);
}

.zalo-btn {
  background: #0068ff;
  box-shadow: 0 6px 18px rgba(0, 104, 255, 0.3);
}

.zalo-btn:hover {
  box-shadow: 0 8px 24px rgba(0, 132, 255, 0.45);
}

.zalo-btn .option-icon {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: -0.2px;
}

.option-icon {
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ========== SPEED DIAL TRANSITIONS ========== */
.speed-dial-enter-active {
  transition: all 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.speed-dial-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 1, 1);
}

.speed-dial-enter-from,
.speed-dial-leave-to {
  opacity: 0;
  transform: translateY(24px) scale(0.8);
}

/* ========== RESPONSIVE ========== */
@media (max-width: 640px) {
  .floating-menu-container {
    right: 18px;
    bottom: 20px;
    gap: 10px;
  }
  .master-menu-btn {
    width: 48px;
    height: 48px;
  }
  .menu-option-btn {
    width: 40px;
    height: 40px;
  }
  .menu-tooltip {
    right: 52px;
    padding: 5px 10px;
    font-size: 11px;
  }
}
</style>
