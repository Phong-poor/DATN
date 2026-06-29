<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

import Header from './Header.vue'
import Breadcrumbs from './Breadcrumbs.vue'
import Footer from './Footer.vue'


const route = useRoute()

const showChatbot = computed(() =>
  ['/', '/products', '/news', '/contact', '/interactive-labs'].includes(route.path)
)

const showScrollTop = ref(false)
const webIntroActive = ref(false)
let scrollTicking = false
let webIntroTimer = null

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
  if (sessionStorage.getItem('web_intro_animation') === '1') {
    sessionStorage.removeItem('web_intro_animation')
    webIntroActive.value = true
    webIntroTimer = window.setTimeout(() => {
      webIntroActive.value = false
      webIntroTimer = null
    }, 1350)
  }

  window.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
  if (webIntroTimer) {
    clearTimeout(webIntroTimer)
    webIntroTimer = null
  }

  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <div class="web-layout" :class="{ 'intro-active': webIntroActive }">
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
  padding-top: 102px;
}

.web-layout.intro-active {
  animation: webIntroBase 1.1s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.web-layout.intro-active .web-intro-header {
  animation: webIntroHeader 0.82s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.web-layout.intro-active .web-intro-main {
  animation: webIntroMain 1s cubic-bezier(0.16, 1, 0.3, 1) 0.16s both;
}

.web-layout.intro-active .web-intro-footer {
  animation: webIntroFooter 0.75s cubic-bezier(0.16, 1, 0.3, 1) 0.34s both;
}

@keyframes webIntroBase {
  0% { background: #eef4ff; }
  100% { background: #f8fafc; }
}

@keyframes webIntroHeader {
  0% {
    opacity: 0;
    transform: translate3d(0, -24px, 0);
  }
  100% {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

@keyframes webIntroMain {
  0% {
    opacity: 0;
    transform: translate3d(0, 26px, 0) scale(0.992);
  }
  100% {
    opacity: 1;
    transform: translate3d(0, 0, 0) scale(1);
  }
}

@keyframes webIntroFooter {
  0% {
    opacity: 0;
    transform: translate3d(0, 18px, 0);
  }
  100% {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .web-layout.intro-active,
  .web-layout.intro-active .web-intro-header,
  .web-layout.intro-active .web-intro-main,
  .web-layout.intro-active .web-intro-footer {
    animation-duration: 0.01ms !important;
  }
}

@media (max-width: 600px) {
  .web-layout {
    padding-top: 60px;
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
  background: linear-gradient(135deg, #2563eb 0%, #6366f1 100%);
  box-shadow: 0 12px 28px rgba(37, 99, 235, 0.4), 0 0 18px rgba(99, 102, 241, 0.25);
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
