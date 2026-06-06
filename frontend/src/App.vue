<template>
  <router-view v-slot="{ Component }">
    <component :is="Component" />
  </router-view>

  <SupportWidget v-if="showChatbot && widgetsReady" />
  <ChatbotWidget v-if="showChatbot && widgetsReady" />
  <AdminChatWidget v-if="showChatbot && adminChatReady" />
  <GlobalLoader />
  <ZaloWidget v-if="showChatbot && widgetsReady" />
  <FloatingContactMenu v-if="showChatbot && widgetsReady" />
</template>

<script setup>
import { computed, defineAsyncComponent, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import GlobalLoader from '@/components/Layout/GlobalLoader.vue'
const SupportWidget = defineAsyncComponent(() => import('@/components/Web/SupportWidget.vue'))
const ChatbotWidget = defineAsyncComponent(() => import('@/components/Web/ChatbotWidget.vue'))
const AdminChatWidget = defineAsyncComponent(() => import('@/components/Web/AdminChatWidget.vue'))
const ZaloWidget = defineAsyncComponent(() => import('@/components/Web/ZaloWidget.vue'))
const FloatingContactMenu = defineAsyncComponent(() => import('@/components/Web/FloatingContactMenu.vue'))
const route = useRoute()
const widgetsReady = ref(false)
const adminChatReady = ref(false)
let pageShowHandler = null
let openAdminChatHandler = null
let toggleAdminChatHandler = null

const showChatbot = computed(() => {
  const hiddenRouteNames = ['login', 'register', 'forgot-password', 'otp-verify', 'reset-password', 'login-success']
  if (route.name && hiddenRouteNames.includes(route.name)) return false
  if (route.path && route.path.startsWith('/admin')) return false
  return true
})

onMounted(() => {
  if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual'
  }
  window.scrollTo(0, 0)
  pageShowHandler = () => {
    window.scrollTo(0, 0)
    requestAnimationFrame(() => window.scrollTo(0, 0))
  }
  window.addEventListener('pageshow', pageShowHandler)

  const bootWidgets = () => { widgetsReady.value = true }
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(bootWidgets, { timeout: 1800 })
  } else {
    setTimeout(bootWidgets, 1000)
  }

  const ensureAdminChat = (eventName) => {
    if (adminChatReady.value) return
    adminChatReady.value = true
    nextTick(() => {
      window.dispatchEvent(new CustomEvent(eventName))
    })
  }
  openAdminChatHandler = () => ensureAdminChat('open-admin-chat')
  toggleAdminChatHandler = () => ensureAdminChat('toggle-admin-chat')
  window.addEventListener('open-admin-chat', openAdminChatHandler)
  window.addEventListener('toggle-admin-chat', toggleAdminChatHandler)
})

onUnmounted(() => {
  if (pageShowHandler) {
    window.removeEventListener('pageshow', pageShowHandler)
  }
  if (openAdminChatHandler) {
    window.removeEventListener('open-admin-chat', openAdminChatHandler)
  }
  if (toggleAdminChatHandler) {
    window.removeEventListener('toggle-admin-chat', toggleAdminChatHandler)
  }
})
</script>

<style>
body {
  margin: 0;
  font-family: var(--font-sans);
}

.route-shell {
  min-height: 100vh;
}
</style>
