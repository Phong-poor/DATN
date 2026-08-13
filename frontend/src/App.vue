<template>
  <router-view v-slot="{ Component }">
    <component :is="Component" />
  </router-view>

  <ChatbotWidget v-if="showChatbot && widgetsReady" />
  <AdminChatWidget v-if="showChatbot && adminChatReady" />
  <GlobalLoader />
  <ZaloWidget v-if="showChatbot && widgetsReady" />
  <FloatingContactMenu v-if="showChatbot && widgetsReady" />
  <VongQuayNoi v-if="showLuckyWheel && widgetsReady" />
  <VongQuayPopup v-if="showWheelPopup" @close="showWheelPopup = false" />
  <OfflineSyncManager v-if="showOfflineManager" />
</template>

<script setup>
import { computed, defineAsyncComponent, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import GlobalLoader from '@/components/Layout/TrinhTaiTrang.vue'
import OfflineSyncManager from '@/components/Layout/OfflineSyncManager.vue'
import { initGlobalDraftManager } from '@/services/offlineSync'
const ChatbotWidget = defineAsyncComponent(() => import('@/components/Web/KhungChatbot.vue'))
const AdminChatWidget = defineAsyncComponent(() => import('@/components/Web/KhungChatAdmin.vue'))
const ZaloWidget = defineAsyncComponent(() => import('@/components/Web/KhungZalo.vue'))
const FloatingContactMenu = defineAsyncComponent(() => import('@/components/Web/TrinhMenuLienHeNoi.vue'))
const VongQuayNoi = defineAsyncComponent(() => import('@/components/Web/VongQuayNoi.vue'))
const VongQuayPopup = defineAsyncComponent(() => import('@/components/Web/VongQuayPopup.vue'))
const route = useRoute()
const router = useRouter()
const widgetsReady = ref(false)
const adminChatReady = ref(false)
const showWheelPopup = ref(false)
let pageShowHandler = null
let openAdminChatHandler = null
let toggleAdminChatHandler = null
let openLuckyWheelHandler = null

const showChatbot = computed(() => {
  const hiddenRouteNames = ['login', 'register', 'forgot-password', 'otp-verify', 'two-factor-challenge', 'reset-password', 'login-success']
  if (route.name && hiddenRouteNames.includes(route.name)) return false
  if (route.path && route.path.startsWith('/admin')) return false
  return true
})

const showOfflineManager = computed(() => {
  return route.path && route.path.startsWith('/admin')
})

const showLuckyWheel = computed(() => {
  const allowedRoutes = ['home', 'laptop', 'phu-kien', 'product-detail']
  return route.name && allowedRoutes.includes(route.name)
})

onMounted(() => {
  initGlobalDraftManager(router)
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
    window.__pendingAdminChatEvent = eventName
    adminChatReady.value = true
  }
  openAdminChatHandler = () => ensureAdminChat('open-admin-chat')
  toggleAdminChatHandler = () => ensureAdminChat('toggle-admin-chat')
  window.addEventListener('open-admin-chat', openAdminChatHandler)
  window.addEventListener('toggle-admin-chat', toggleAdminChatHandler)

  openLuckyWheelHandler = () => {
    showWheelPopup.value = true
  }
  window.addEventListener('open-lucky-wheel', openLuckyWheelHandler)
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
  if (openLuckyWheelHandler) {
    window.removeEventListener('open-lucky-wheel', openLuckyWheelHandler)
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
