<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { saveAuth } from '@/services/auth'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

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

    router.push('/')
  } catch (e) {
    console.error('Lỗi lấy profile sau login Google:', e)
    router.push('/login')
  }
}
</script>