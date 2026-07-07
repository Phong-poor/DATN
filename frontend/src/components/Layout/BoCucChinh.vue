<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

import Header from './Header.vue'
import Breadcrumbs from './DuongDanDieuHuong.vue'
import Footer from './Footer.vue'


const route = useRoute()

const showChatbot = computed(() =>
  ['/', '/san-pham', '/tin-tuc', '/lien-he', '/phong-thi-nghiem-tuong-tac'].includes(route.path)
)

const showScrollTop = ref(false)
let scrollTicking = false

const handleScroll = () => {
  if (scrollTicking) return

  scrollTicking = true
  window.requestAnimationFrame(() => {
    showScrollTop.value = window.scrollY > 300
    scrollTicking = false
  })
}

const scrollToTop = () => {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

onMounted(() => {
  sessionStorage.removeItem('web_intro_animation')

  window.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <div class="web-layout">
    <div class="web-intro-header">
      <Header />
    </div>
    <Breadcrumbs v-if="route.path !== '/'" />
    <main class="web-intro-main">
      <router-view v-slot="{ Component }">
        <transition name="page-fade">
          <component :is="Component" :key="route.fullPath" />
        </transition>
      </router-view>
    </main>
    <div class="web-intro-footer">
      <Footer />
    </div>
  </div>

  <!-- Nút cuộn lên đầu trang (Back to Top) -->
  <transition name="fade-scale">
    <button 
      v-show="showScrollTop" 
      @click="scrollToTop" 
      class="scroll-top-btn" 
      :class="{ 'has-chatbot': showChatbot }"
      aria-label="Cuộn lên đầu trang"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="m18 15-6-6-6 6"/>
      </svg>
    </button>
  </transition>
</template>

<style scoped>
.web-layout {
  min-height: 100vh;
  background: #f8fafc;
  padding-top: 116px;
  overflow-x: clip;
}

.web-intro-header {
  position: relative;
  z-index: 1000;
  min-height: 0;
}

.web-intro-main {
  position: relative;
  min-height: calc(100vh - 116px);
  isolation: isolate;
}

.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 0.08s ease;
}

.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
}

@media (max-width: 600px) {
  .web-layout {
    padding-top: 64px;
  }
}

.scroll-top-btn {
  position: fixed;
  right: 37.5px; /* (30px chatbot padding + (60px chatbot width - 45px button width) / 2) to center align perfectly */
  bottom: 30px;
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  color: white;
  border: 1px solid rgba(37, 99, 235, 0.35);
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.2), 0 0 12px rgba(37, 99, 235, 0.1);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9998;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), bottom 0.3s ease;
}

.scroll-top-btn.has-chatbot {
  bottom: 96px; /* Positioned perfectly above the 56px chatbot bubble with clean spacing */
}

.scroll-top-btn:hover {
  transform: translateY(-4px) scale(1.06);
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  box-shadow: 0 12px 28px rgba(37, 99, 235, 0.4), 0 0 18px rgba(37, 99, 235, 0.25);
  border-color: rgba(255, 255, 255, 0.45);
}

.scroll-top-btn:active {
  transform: translateY(-2px) scale(0.96);
}

/* Transition Animations */
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1), transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.6) translateY(10px);
}
</style>
