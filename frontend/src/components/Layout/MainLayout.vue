<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import Header from './Header.vue'
import Footer from './Footer.vue'
import ChatbotWidget from '../Web/ChatbotWidget.vue'


const route = useRoute()

const showChatbot = computed(() =>
  ['/', '/products', '/news', '/contact'].includes(route.path)
)
</script>

<template>
  <Header />
  <router-view />
  <Footer />

  <!-- sửa component -->
  <ChatbotWidget v-if="showChatbot" />
</template>