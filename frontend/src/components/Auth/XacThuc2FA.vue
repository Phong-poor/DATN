<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { saveAuth } from '@/services/auth'

const route = useRoute()
const router = useRouter()
const code = ref('')
const loading = ref(false)
const error = ref('')
const challenge = ref('')
const isRecovery = ref(false)
const canSubmit = computed(() => isRecovery.value ? code.value.trim().length >= 8 : /^\d{6}$/.test(code.value))

onMounted(() => {
  challenge.value = String(route.query.challenge || sessionStorage.getItem('admin_2fa_challenge') || '')
  if (!challenge.value) router.replace('/dang-nhap')
})

const submit = async () => {
  if (!canSubmit.value || loading.value) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.post('/auth/two-factor/challenge', {
      challenge_token: challenge.value,
      code: code.value.trim(),
    }, { immediateLoader: true })
    saveAuth(data.token, data.user, Boolean(data.remember))
    sessionStorage.removeItem('admin_2fa_challenge')
    window.location.href = '/admin'
  } catch (err) {
    error.value = err.response?.data?.message || 'Không thể xác thực. Vui lòng thử lại.'
    if ([419, 429].includes(err.response?.status)) sessionStorage.removeItem('admin_2fa_challenge')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="two-factor-page">
    <section class="two-factor-card">
      <div class="shield">✓</div>
      <span class="eyebrow">Bảo mật Admin</span>
      <h1>Xác thực hai lớp</h1>
      <p>{{ isRecovery ? 'Nhập một mã khôi phục chưa sử dụng.' : 'Mở ứng dụng Authenticator và nhập mã 6 số đang hiển thị.' }}</p>

      <form @submit.prevent="submit">
        <input
          v-model="code"
          :inputmode="isRecovery ? 'text' : 'numeric'"
          autocomplete="one-time-code"
          :maxlength="isRecovery ? 64 : 6"
          :placeholder="isRecovery ? 'Mã khôi phục' : '000000'"
          autofocus
          @input="!isRecovery && (code = code.replace(/\D/g, '').slice(0, 6))"
        />
        <div v-if="error" class="error">{{ error }}</div>
        <button type="submit" :disabled="!canSubmit || loading">{{ loading ? 'Đang xác thực...' : 'Xác thực và đăng nhập' }}</button>
      </form>

      <button class="mode-btn" type="button" @click="isRecovery = !isRecovery; code = ''; error = ''">
        {{ isRecovery ? 'Dùng mã từ Authenticator' : 'Dùng mã khôi phục' }}
      </button>
      <a href="/dang-nhap" @click="sessionStorage.removeItem('admin_2fa_challenge')">Quay lại đăng nhập</a>
    </section>
  </main>
</template>

<style scoped>
.two-factor-page{min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at top,#1d4ed8 0,#0f172a 42%,#020617 100%);font-family:Inter,system-ui,sans-serif}.two-factor-card{width:min(420px,100%);padding:32px;border:1px solid rgba(148,163,184,.25);border-radius:22px;background:rgba(255,255,255,.98);box-shadow:0 28px 70px rgba(2,6,23,.45);text-align:center}.shield{display:grid;place-items:center;width:52px;height:52px;margin:0 auto 14px;border-radius:16px;background:#dbeafe;color:#2563eb;font-size:25px;font-weight:900}.eyebrow{color:#2563eb;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}h1{margin:7px 0 8px;color:#0f172a;font-size:25px}p{margin:0 auto 22px;color:#64748b;font-size:13px;line-height:1.55}form{display:grid;gap:12px}input{width:100%;height:54px;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:12px;background:#f8fafc;color:#0f172a;font-size:24px;font-weight:800;letter-spacing:.22em;text-align:center;outline:none}input:focus{border-color:#3b82f6;box-shadow:0 0 0 4px rgba(59,130,246,.14)}form button{height:44px;border:0;border-radius:11px;background:#2563eb;color:#fff;font-size:13px;font-weight:800;cursor:pointer}form button:disabled{opacity:.55;cursor:not-allowed}.error{padding:9px 11px;border-radius:9px;background:#fef2f2;color:#dc2626;font-size:12px}.mode-btn{margin:16px 0 10px;border:0;background:transparent;color:#2563eb;font-size:12px;font-weight:700;cursor:pointer}.two-factor-card>a{display:block;color:#64748b;font-size:11px;text-decoration:none}
</style>
