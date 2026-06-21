<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { saveAuth } from '@/services/auth'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms))

const safeRedirectPath = (path) => {
  if (!path || typeof path !== 'string') return ''
  if (!path.startsWith('/') || path.startsWith('/login') || path.startsWith('/login-success')) return ''
  return path
}

const redirectAfterSocialLogin = async (user, token) => {
  if (user?.vaitro === 'admin') {
    sessionStorage.setItem('skip_next_route_loader', '1')
    sessionStorage.setItem('admin_intro_animation', '1')
    await wait(260)
    await router.replace('/admin')
    return
  }

  const redirectPath = safeRedirectPath(sessionStorage.getItem('redirect_after_auth'))
  sessionStorage.removeItem('redirect_after_auth')

  if (redirectPath) {
    sessionStorage.setItem('web_intro_animation', '1')
    await wait(220)
    await router.replace(redirectPath)
    return
  }

  const pendingItemStr = localStorage.getItem('pendingCartItem')
  if (pendingItemStr) {
    try {
      const pendingItem = JSON.parse(pendingItemStr)
      await api.post('/gio-hang/them', pendingItem, {
        headers: { Authorization: `Bearer ${token}` }
      })
      localStorage.removeItem('pendingCartItem')
      window.dispatchEvent(new Event('cart-updated'))
      sessionStorage.setItem('web_intro_animation', '1')
      await wait(220)
      await router.replace('/cart')
      return
    } catch (err) {
      console.error('Lỗi thêm pending item:', err)
    }
  }

  sessionStorage.setItem('web_intro_animation', '1')
  await wait(220)
  await router.replace('/')
}

onMounted(() => {
  const token = route.query.token

  if (token) {
    fetchUser(token)
  } else {
    router.push('/login')
  }
})

const fetchUser = async (token) => {
  try {
    const res = await api.get('/user/profile', {
      headers: {
        Authorization: `Bearer ${token}`
      }
    })

    const user = res.data

    saveAuth(token, user)

    await redirectAfterSocialLogin(user, token)
  } catch (e) {
    console.error('Lỗi lấy profile sau đăng nhập mạng xã hội:', e)
    router.push('/login')
  }
}
</script>

<template>
  <div class="login-success-container">
    <div class="glass-loader-card">
      <div class="spinner-outer">
        <div class="spinner-inner"></div>
        <div class="spinner-glow"></div>
      </div>
      <h3 class="loading-title">Đang xác thực tài khoản</h3>
      <p class="loading-subtitle">Hệ thống đang kết nối an toàn và đồng bộ hóa tài khoản của bạn...</p>
    </div>
  </div>
</template>

<style scoped>

.login-success-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent 40%),
              radial-gradient(circle at bottom left, rgba(139, 92, 246, 0.08), transparent 40%),
              #f8fafc;
  font-family: 'Outfit', 'Inter', sans-serif;
  padding: 20px;
}

.glass-loader-card {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.05);
  border-radius: 24px;
  padding: 45px 30px;
  text-align: center;
  max-width: 440px;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: cardFadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.spinner-outer {
  position: relative;
  width: 64px;
  height: 64px;
  margin-bottom: 24px;
}

.spinner-inner {
  box-sizing: border-box;
  width: 100%;
  height: 100%;
  border: 3.5px solid rgba(59, 130, 246, 0.1);
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.spinner-glow {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  box-shadow: 0 0 15px rgba(59, 130, 246, 0.45);
  animation: glowPulse 2s ease-in-out infinite;
  pointer-events: none;
}

.loading-title {
  color: #0f172a;
  font-size: 20px;
  font-weight: 700;
  margin: 0 0 10px 0;
  letter-spacing: -0.02em;
}

.loading-subtitle {
  color: #64748b;
  font-size: 14px;
  line-height: 1.6;
  font-weight: 450;
  margin: 0;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes glowPulse {
  0%, 100% { opacity: 0.3; transform: scale(1); }
  50% { opacity: 0.75; transform: scale(1.06); }
}

@keyframes cardFadeIn {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
