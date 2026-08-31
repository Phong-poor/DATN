<template>
  <div
    :id="msg.id ? `msg-row-${msg.id}` : (msg._clientKey ? `msg-row-${msg._clientKey}` : null)"
    class="chat-message-row"
    :class="[sideClass, { 'is-own': isOwn }]"
  >
    <slot name="avatar" />

    <div class="bubble-col">
      <div class="bubble-content-wrap">
        <div
          class="msg-bubble"
          :class="bubbleClass"
        >
          <div v-if="isEditing" class="edit-panel" @click.stop>
            <textarea
              ref="editInput"
              v-model="editText"
              rows="2"
              class="edit-textarea"
              @keydown.enter.exact.prevent="saveEdit"
              @keydown.esc="cancelEdit"
            />
            <div class="edit-actions">
              <button type="button" class="edit-btn save" @click="saveEdit">Lưu</button>
              <button type="button" class="edit-btn cancel" @click="cancelEdit">Hủy</button>
            </div>
          </div>
          <template v-else>
            <slot name="body" :msg="msg">
              <ChatMessageBody :msg="msg" :is-own="isOwn" @open-image="$emit('open-image', $event)" />
            </slot>
          </template>
        </div>

        <!-- Time sent and Read status -->
        <div class="msg-meta-info" :class="{ 'is-own': isOwn }">
          <span v-if="formattedTime" class="msg-time">{{ formattedTime }}</span>
          <span v-if="isOwn" class="msg-status" :class="{ 'is-seen': isSeen }">
            <span v-if="formattedTime" class="msg-dot">•</span>
            <svg v-if="isSeen" class="status-icon seen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M18 6L7 17l-5-5"/><path d="M22 6l-7 7"/>
            </svg>
            <svg v-else class="status-icon sent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M20 6L9 17l-5-5"/>
            </svg>
            <span>{{ isSeen ? 'Đã xem' : 'Đã gửi' }}</span>
          </span>
        </div>
      </div>

      <div v-if="isOwn && messageId && !isEditing" class="msg-menu-container">
        <button
          type="button"
          class="msg-menu-trigger"
          :aria-expanded="menuOpen"
          aria-label="Tùy chọn tin nhắn"
          title="Tùy chọn tin nhắn"
          @click.stop="toggleMenu"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>

        <div v-if="menuOpen" class="msg-action-menu" @click.stop>
          <button v-if="msg.noidung" type="button" @click="startEdit">Sửa</button>
          <button type="button" class="danger" @click="confirmDelete">Xóa</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import ChatMessageBody from './NoiDungTinNhan.vue'

const props = defineProps({
  msg: { type: Object, required: true },
  authUserId: { type: [Number, String], required: true },
  apiPrefix: { type: String, default: 'chat' },
  sideClass: { type: String, default: '' },
  bubbleClass: { type: String, default: '' },
})

const emit = defineEmits(['open-image', 'updated', 'deleted'])

const menuOpen = ref(false)
const isEditing = ref(false)
const editText = ref('')
const editInput = ref(null)
const saving = ref(false)

const senderId = computed(() => props.msg.id_nguoigui ?? props.msg.sender_id)
const isOwn = computed(() => Number(senderId.value) === Number(props.authUserId))
const messageId = computed(() => props.msg.id)

const formattedTime = computed(() => {
  const raw = props.msg.created_at || props.msg.timestamp || props.msg.created_time || props.msg.tin_nhan_cuoi_luc
  if (!raw) return ''
  try {
    const d = new Date(raw)
    if (isNaN(d.getTime())) return ''
    const hours = String(d.getHours()).padStart(2, '0')
    const minutes = String(d.getMinutes()).padStart(2, '0')
    return `${hours}:${minutes}`
  } catch (e) {
    return ''
  }
})

const isSeen = computed(() => {
  const v = props.msg.daxem ?? props.msg.da_xem ?? props.msg.is_read
  return v === true || v === 1 || v === '1' || v === 'true'
})

const toggleMenu = () => {
  if (!isOwn.value || !messageId.value || isEditing.value) return
  menuOpen.value = !menuOpen.value
}

const startEdit = () => {
  menuOpen.value = false
  editText.value = props.msg.noidung || ''
  isEditing.value = true
  nextTick(() => editInput.value?.focus())
}

const cancelEdit = () => {
  isEditing.value = false
  editText.value = ''
}

const saveEdit = async () => {
  const text = editText.value.trim()
  if (!text || saving.value) return

  saving.value = true
  try {
    const res = await api.put(`/${props.apiPrefix}/messages/${messageId.value}`, {
      noidung: text,
    })
    emit('updated', res.data.message)
    isEditing.value = false
  } catch (e) {
    console.error('Lỗi sửa tin nhắn:', e)
    alert('Không sửa được tin nhắn. Thử lại sau.')
  } finally {
    saving.value = false
  }
}

const confirmDelete = async () => {
  menuOpen.value = false
  if (!confirm('Xóa tin nhắn này?')) return

  try {
    await api.delete(`/${props.apiPrefix}/messages/${messageId.value}`)
    emit('deleted', messageId.value)
  } catch (e) {
    console.error('Lỗi xóa tin nhắn:', e)
    alert('Không xóa được tin nhắn. Thử lại sau.')
  }
}

const closeMenu = () => {
  menuOpen.value = false
}

onMounted(() => {
  document.addEventListener('click', closeMenu)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenu)
})

</script>

<style scoped>
.chat-message-row {
  display: flex;
  gap: 8px;
  max-width: 85%;
}

.chat-message-row.msg-right,
.chat-message-row.message-right {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.chat-message-row.msg-left,
.chat-message-row.message-left {
  align-self: flex-start;
}

.bubble-col {
  position: relative;
  min-width: 0;
  display: flex;
  align-items: flex-start;
  gap: 6px;
}

.chat-message-row.is-own .bubble-col {
  display: flex;
  flex-direction: row-reverse;
  align-items: flex-start;
  gap: 6px;
}

.bubble-content-wrap {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.chat-message-row.is-own .bubble-content-wrap {
  align-items: flex-end;
}

.chat-message-row:not(.is-own) .bubble-content-wrap {
  align-items: flex-start;
}

.msg-bubble {
  padding: 8px 12px;
  border-radius: 18px;
  font-size: 14px;
  line-height: 1.4;
  overflow: hidden;
  word-wrap: break-word;
  word-break: break-word;
}

.chat-message-row.is-own .msg-bubble {
  background: #2563eb;
  color: #ffffff;
  border-bottom-right-radius: 4px;
}

.chat-message-row:not(.is-own) .msg-bubble {
  background: #ffffff;
  color: #0f172a;
  border-bottom-left-radius: 4px;
  border: 1px solid #e2e8f0;
}

.msg-menu-container {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 5;
}

.msg-menu-trigger {
  width: 26px;
  height: 26px;
  flex: 0 0 26px;
  border: none;
  border-radius: 50%;
  background: #fff;
  color: #475569;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.14);
  display: grid;
  place-content: center;
  gap: 2px;
  cursor: pointer;
  opacity: .9;
  transition: opacity .15s ease, background .15s ease, color .15s ease;
  z-index: 3;
}

.bubble-col:hover .msg-menu-trigger,
.msg-menu-trigger:focus,
.msg-menu-trigger[aria-expanded="true"] {
  opacity: 1;
}

.msg-menu-trigger:hover,
.msg-menu-trigger[aria-expanded="true"] {
  background: #f1f5f9;
  color: #0f172a;
}

.msg-menu-trigger span {
  width: 3px;
  height: 3px;
  border-radius: 50%;
  background: currentColor;
  display: block;
}

.msg-action-menu {
  position: absolute;
  top: 50%;
  right: calc(100% + 8px);
  transform: translateY(-50%);
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  border: 1px solid #e4e6eb;
  overflow: hidden;
  z-index: 10;
  min-width: 100px;
}

.msg-action-menu button {
  display: block;
  width: 100%;
  border: none;
  background: #fff;
  padding: 10px 14px;
  text-align: left;
  font-size: 13px;
  cursor: pointer;
}

.msg-action-menu button:hover {
  background: #f0f2f5;
}

.msg-action-menu button.danger {
  color: #e41e3f;
}

.edit-panel {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 180px;
}

.edit-textarea {
  width: 100%;
  border: 1px solid rgba(0, 0, 0, 0.15);
  border-radius: 8px;
  padding: 8px;
  font-size: 13px;
  font-family: inherit;
  resize: vertical;
  outline: none;
  background: #fff;
  color: #050505;
}

.edit-actions {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
}

.edit-btn {
  border: none;
  border-radius: 6px;
  padding: 5px 12px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.edit-btn.save {
  background: #0084ff;
  color: #fff;
}

.edit-btn.cancel {
  background: #e4e6eb;
  color: #050505;
}

.msg-meta-info {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 3px;
  padding: 0 4px;
  font-size: 11px;
  color: #94a3b8;
  user-select: none;
}

.msg-meta-info.is-own {
  justify-content: flex-end;
}

.msg-time {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 500;
}

.msg-status {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  color: #94a3b8;
  font-weight: 500;
}

.msg-status.is-seen {
  color: #2563eb;
  font-weight: 600;
}

.status-icon {
  width: 12px;
  height: 12px;
  flex: 0 0 12px;
  stroke-width: 2.5;
}

.msg-dot {
  font-size: 9px;
  color: #cbd5e1;
}
</style>
