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

const handleScroll = () => {
  showScrollTop.value = window.scrollY > 300
}

const scrollToTop = () => {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <Header />
  <Breadcrumbs v-if="route.path !== '/'" />
  <router-view />
  <Footer />

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
.scroll-top-btn {
  position: fixed;
  right: 37.5px; /* (30px chatbot padding + (60px chatbot width - 45px button width) / 2) to center align perfectly */
  bottom: 30px;
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(26, 39, 68, 0.95) 0%, rgba(37, 99, 235, 0.95) 100%);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9998;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1), bottom 0.3s ease;
}

.scroll-top-btn.has-chatbot {
  bottom: 105px; /* Positioned perfectly above the 60px chatbot bubble (30px + 60px + 15px spacing) */
}

.scroll-top-btn:hover {
  transform: translateY(-3px);
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.95) 0%, rgba(59, 130, 246, 0.95) 100%);
  box-shadow: 0 8px 25px rgba(37, 99, 235, 0.5), 0 0 15px rgba(37, 99, 235, 0.3);
  border-color: rgba(255, 255, 255, 0.4);
}

.scroll-top-btn:active {
  transform: translateY(-1px) scale(0.95);
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
