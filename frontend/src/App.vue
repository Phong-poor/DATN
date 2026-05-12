<template>
  <router-view />
  <ChatbotWidget v-if="showChatbot" />
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import ChatbotWidget from '@/components/Web/ChatbotWidget.vue';

const route = useRoute();

const showChatbot = computed(() => {
  const hiddenRouteNames = ['login', 'register', 'forgot-password', 'otp-verify', 'reset-password', 'login-success'];
  
  if (route.name && hiddenRouteNames.includes(route.name)) {
    return false;
  }
  
  if (route.path && route.path.startsWith('/admin')) {
    return false;
  }
  
  return true;
});
</script>

<style>
body {
  margin: 0;
  font-family: Arial, Helvetica, sans-serif;
}
</style>