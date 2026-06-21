<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import { getUser, updateUser } from '@/services/auth'
import { storageUrl } from '@/services/urls'
import swal from '@/services/swal'

const loading = ref(false)
const saving = ref(false)
const uploadingAvatar = ref(false)
const editing = ref(false)
const avatarFileRef = ref(null)
const avatarPreview = ref('')
const localUser = ref(getUser() || {})

const form = ref({
  ten: '',
  email: '',
  sodienthoai: '',
  gioitinh: '',
  ngaysinh: '',
})

const avatarUrl = computed(() => {
  if (avatarPreview.value) return avatarPreview.value
  const avatar = localUser.value?.anhdaidien || localUser.value?.avatar
  if (!avatar) return ''
  return avatar.startsWith('http') ? avatar : storageUrl(avatar)
})

const avatarInitial = computed(() => (form.value.ten || 'A').trim().charAt(0).toUpperCase())
const memberSince = computed(() => {
  const dt = localUser.value?.created_at
  if (!dt) return '--'
  return `Thành viên từ ${new Date(dt).toLocaleDateString('vi-VN')}`
})

function displayGender(v) {
  return v || 'Chưa cập nhật'
}

function displayDate(v) {
  if (!v) return 'Chưa cập nhật'
  return new Date(v).toLocaleDateString('vi-VN')
}

async function fetchProfile() {
  loading.value = true
  try {
    const res = await api.get('/admin/account/profile')
    const u = res.data?.data || {}
    form.value = {
      ten: u.ten || '',
      email: u.email || '',
      sodienthoai: u.sodienthoai || '',
      gioitinh: u.gioitinh || '',
      ngaysinh: u.ngaysinh || '',
    }
    localUser.value = { ...localUser.value, ...u }
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  saving.value = true
  try {
    const res = await api.put('/admin/account/profile', form.value)
    const user = res.data?.data
    if (user) {
      localUser.value = { ...localUser.value, ...user }
      updateUser(localUser.value)
      window.dispatchEvent(new Event('user-updated'))
    }
    editing.value = false
    await swal.success('Thành công', 'Đã cập nhật hồ sơ quản trị.')
  } finally {
    saving.value = false
  }
}

function pickAvatar() {
  if (!editing.value) return
  avatarFileRef.value?.click()
}

async function onAvatarChange(e) {
  const file = e.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (ev) => {
    avatarPreview.value = ev.target?.result || ''
  }
  reader.readAsDataURL(file)

  const formData = new FormData()
  formData.append('avatar', file)

  uploadingAvatar.value = true
  try {
    const res = await api.post('/user/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const user = res.data?.user
    if (user) {
      localUser.value = { ...localUser.value, ...user }
      updateUser(localUser.value)
      window.dispatchEvent(new Event('user-updated'))
    }
    await swal.success('Thành công', 'Đã cập nhật ảnh đại diện.')
  } finally {
    uploadingAvatar.value = false
    e.target.value = ''
  }
}

onMounted(fetchProfile)
</script>

<template>
  <div class="profile-page">
    <div v-if="loading" class="state">Đang tải hồ sơ...</div>

    <div v-else class="layout">
      <aside class="left">
        <input ref="avatarFileRef" type="file" accept="image/*" class="hidden-file" @change="onAvatarChange" />
        <div class="avatar" :class="{ editable: editing }" @click="pickAvatar">
          <img v-if="avatarUrl" :src="avatarUrl" alt="Admin Avatar" />
          <span v-else>{{ avatarInitial }}</span>
          <div v-if="editing" class="avatar-overlay">{{ uploadingAvatar ? 'Đang tải...' : 'Đổi ảnh' }}</div>
        </div>
        <h3>{{ form.ten || 'Admin' }}</h3>
        <span class="role-badge">Quản trị viên</span>
        <p class="member-since">{{ memberSince }}</p>
      </aside>

      <section class="right">
        <div class="head">
          <div>
            <h2>Thông tin cá nhân</h2>
            <p>Quản lý thông tin hồ sơ của bạn</p>
          </div>
          <button v-if="!editing" class="edit-btn" @click="editing = true">Chỉnh sửa</button>
          <div v-else class="actions">
            <button class="ghost-btn" @click="editing = false">Hủy</button>
            <button class="save-btn" :disabled="saving" @click="saveProfile">{{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}</button>
          </div>
        </div>

        <div class="rows" v-if="!editing">
          <div class="row"><span>Họ và tên</span><b>{{ form.ten || 'Chưa cập nhật' }}</b></div>
          <div class="row"><span>Email</span><b>{{ form.email || 'Chưa cập nhật' }}</b></div>
          <div class="row"><span>Số điện thoại</span><b>{{ form.sodienthoai || 'Chưa cập nhật' }}</b></div>
          <div class="row"><span>Ngày sinh</span><b>{{ displayDate(form.ngaysinh) }}</b></div>
          <div class="row"><span>Giới tính</span><b>{{ displayGender(form.gioitinh) }}</b></div>
        </div>

        <div class="form" v-else>
          <label><span>Họ và tên</span><input v-model="form.ten" type="text" /></label>
          <label><span>Email</span><input v-model="form.email" type="email" /></label>
          <label><span>Số điện thoại</span><input v-model="form.sodienthoai" type="text" /></label>
          <label><span>Ngày sinh</span><input v-model="form.ngaysinh" type="date" /></label>
          <label><span>Giới tính</span>
            <select v-model="form.gioitinh">
              <option value="">Chưa chọn</option>
              <option>Nam</option>
              <option>Nữ</option>
              <option>Khác</option>
            </select>
          </label>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.profile-page{padding:20px 24px 30px}
.state{padding:16px;color:#64748b}
.layout{display:grid;grid-template-columns:230px 1fr;gap:12px}
.left,.right{background:#fff;border:1px solid #e2e8f0;border-radius:18px}
.left{padding:18px 14px;text-align:center}
.hidden-file{display:none}
.avatar{width:84px;height:84px;margin:0 auto 10px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;border:2px solid #e2e8f0}
.avatar.editable{cursor:pointer}
.avatar img{width:100%;height:100%;object-fit:cover}
.avatar span{font-size:26px;font-weight:800;color:#1d4ed8}
.avatar-overlay{position:absolute;inset:auto 0 0 0;background:rgba(15,23,42,.68);color:#fff;font-size:11px;padding:5px 0}
.left h3{margin:0 0 6px;font-size:20px;color:#0f172a}
.role-badge{display:inline-block;background:#dbeafe;color:#1d4ed8;padding:6px 12px;border-radius:999px;font-weight:700;font-size:12px}
.member-since{margin:10px 0 0;color:#64748b;font-size:13px}
.right{padding:16px 18px}
.head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:10px}
.head h2{margin:0 0 2px;font-size:21px;color:#0f172a}
.head p{margin:0;color:#64748b;font-size:13px}
.edit-btn,.save-btn,.ghost-btn{border-radius:12px;padding:9px 14px;font-weight:700;cursor:pointer}
.edit-btn{border:1px solid #cbd5e1;background:#f8fafc;color:#334155}
.save-btn{border:1px solid #2563eb;background:#2563eb;color:#fff}
.ghost-btn{border:1px solid #dbe2ea;background:#fff;color:#334155}
.actions{display:flex;gap:8px}
.rows{display:grid}
.row{display:grid;grid-template-columns:150px 1fr;align-items:center;padding:11px 0;border-bottom:1px solid #edf2f7}
.row:last-child{border-bottom:none}
.row span{color:#64748b;font-weight:600}
.row b{color:#0f172a;font-size:14px;font-weight:700;line-height:1.35}
.form{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form label{display:flex;flex-direction:column;gap:6px}
.form span{color:#334155;font-size:13px;font-weight:600}
input,select{border:1px solid #dbe2ea;border-radius:10px;padding:10px 12px;font-size:14px}
@media (max-width:980px){
  .layout{grid-template-columns:1fr}
  .row{grid-template-columns:1fr;gap:5px}
  .form{grid-template-columns:1fr}
}
</style>
