<template>
  <div
    class="chat-message-row"
    :class="[sideClass, { 'is-own': isOwn }]"
  >
    <slot name="avatar" />

    <div class="bubble-col">
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
          <button v-if="msg.message" type="button" @click="startEdit">Sửa</button>
          <button type="button" class="danger" @click="confirmDelete">Xóa</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import ChatMessageBody from './ChatMessageBody.vue'

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

const isOwn = computed(() => Number(props.msg.sender_id) === Number(props.authUserId))
const messageId = computed(() => props.msg.id)

const toggleMenu = () => {
  if (!isOwn.value || !messageId.value || isEditing.value) return
  menuOpen.value = !menuOpen.value
}

const startEdit = () => {
  menuOpen.value = false
  editText.value = props.msg.message || ''
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
      message: text,
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
}

.chat-message-row.is-own .bubble-col {
  display: flex;
  flex-direction: row-reverse;
  align-items: center;
  gap: 6px;
}

.msg-bubble {
  padding: 8px 12px;
  border-radius: 18px;
  font-size: 14px;
  line-height: 1.4;
  overflow: hidden;
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
</style>
