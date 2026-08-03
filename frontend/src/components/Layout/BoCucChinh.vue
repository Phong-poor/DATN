<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

import Header from './Header.vue'
import Footer from './Footer.vue'


const route = useRoute()

const showChatbot = computed(() => {
  const path = route.path || '/'
  return path === '/'
    || path === '/laptop'
    || path === '/phu-kien'
    || path === '/khuyen-mai'
    || path === '/tin-tuc'
    || path === '/news'
    || path === '/lien-he'
    || path === '/contact'
    || path.startsWith('/san-pham/')
    || path.startsWith('/products/')
    || path.startsWith('/tin-tuc/')
    || path.startsWith('/news/')
})

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
    <main class="web-intro-main" :class="{ 'product-detail-gap': route.name === 'product-detail' }">
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
  padding-top: 108px;
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

.web-intro-main.product-detail-gap {
  padding-top: 40px;
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
    padding-top: 60px;
  }

  .web-intro-main.product-detail-gap {
    padding-top: 24px;
  }
}

.scroll-top-btn {
  position: fixed;
  right: var(--floating-widget-right, 24px);
  bottom: 30px;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: #1f2f55;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  color: white;
  border: 0;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.12) inset;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9998;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), bottom 0.3s ease;
}

.scroll-top-btn.has-chatbot {
  right: calc(var(--floating-widget-right, 24px) + 6px);
  bottom: 102px;
}

.scroll-top-btn:hover {
  transform: translateY(-4px) scale(1.06);
  background: #263b6a;
  box-shadow: 0 16px 32px rgba(15, 23, 42, 0.26), 0 0 0 1px rgba(255, 255, 255, 0.18) inset;
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

@media (max-width: 600px) {
  .scroll-top-btn {
    width: 44px;
    height: 44px;
  }

  .scroll-top-btn.has-chatbot {
    right: calc(var(--floating-widget-right-mobile, 18px) + 7px);
    bottom: 96px;
  }
}
</style>
