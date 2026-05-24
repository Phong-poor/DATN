import { createApp } from 'vue'
import './assets/styles/swal-theme.css'
import App from './App.vue'
import router from './router/index.js'
import { initGoogleAnalytics } from './services/analytics'

initGoogleAnalytics()

createApp(App)
  .use(router)
  .mount('#app')
