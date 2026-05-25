<template>
  <router-view v-slot="{ Component }">
    <component :is="Component" />
  </router-view>

  <SupportWidget v-if="showChatbot && widgetsReady" />
  <ChatbotWidget v-if="showChatbot && widgetsReady" />
  <GlobalLoader />
  <ZaloWidget v-if="showChatbot" />
</template>

<script setup>
import { computed, defineAsyncComponent, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import GlobalLoader from '@/components/Layout/GlobalLoader.vue'
import ChatbotWidget from '@/components/Web/ChatbotWidget.vue'
import ZaloWidget from '@/components/Web/ZaloWidget.vue'

const SupportWidget = defineAsyncComponent(() => import('@/components/Web/SupportWidget.vue'))
const route = useRoute()
const widgetsReady = ref(false)

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

  const bootWidgets = () => { widgetsReady.value = true }
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(bootWidgets, { timeout: 1200 })
  } else {
    setTimeout(bootWidgets, 600)
  }
})
</script>

<style>
body {
  margin: 0;
  font-family: Arial, Helvetica, sans-serif;
}

.route-shell {
  min-height: 100vh;
}
</style>
