<template>
  <div class="chat-composer" :class="variant" ref="composerRef">
    <div v-if="pending.length > 0" class="previews-row">
      <div v-for="(item, idx) in pending" :key="idx" class="preview-item">
        <img v-if="item.isImage" :src="item.preview" alt="Preview" class="image-preview" />
        <div v-else class="file-preview">
          <span>📄</span>
          <span class="file-preview-name">{{ item.name }}</span>
        </div>
        <button type="button" class="remove-image-btn" @click="removePending(idx)">✕</button>
      </div>
    </div>

    <div class="composer-main">
      <button type="button" class="composer-icon-btn" aria-label="Đính kèm file" @click="docInput?.click()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
        </svg>
      </button>
      <button type="button" class="composer-icon-btn" aria-label="Chọn ảnh" @click="imgInput?.click()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
          <circle cx="12" cy="13" r="4"/>
        </svg>
      </button>

      <input ref="imgInput" type="file" accept="image/*" multiple class="hidden-input" @change="onImagesPicked" />
      <input
        ref="docInput"
        type="file"
        multiple
        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,application/*"
        class="hidden-input"
        @change="onDocsPicked"
      />

      <div class="composer-input-wrap">
        <input
          type="text"
          :placeholder="placeholder"
          :disabled="disabled"
          :value="modelValue"
          @input="$emit('update:modelValue', $event.target.value)"
          @keyup.enter="submit"
        />
        
        <!-- Emoji Picker Container -->
        <div class="emoji-picker-container">
          <button type="button" class="emoji-trigger-btn" aria-label="Chọn biểu cảm" @click.stop="toggleEmojiPicker">
            😀
          </button>
          <div v-if="showEmojiPicker" class="emoji-picker-popover" @mousedown.stop>
            <div class="emoji-list">
              <span v-for="emoji in emojis" :key="emoji" class="emoji-item" @click="addEmoji(emoji)">
                {{ emoji }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Send button when there is text or attachment -->
      <button
        v-if="modelValue.trim() || pending.length > 0"
        type="button"
        class="composer-send-btn"
        aria-label="Gửi tin nhắn"
        :disabled="disabled"
        @click="submit"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m22 2-7 20-4-9-9-4Z"/>
          <path d="M22 2 11 13"/>
        </svg>
      </button>

      <!-- Like button (Messenger-style) when input is empty -->
      <button
        v-else
        type="button"
        class="composer-like-btn"
        aria-label="Gửi Like"
        :disabled="disabled"
        @click="sendLike"
      >
        👍
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { readFileAsDataURL, isImageFile } from '@/utils/chatMessage'

const props = defineProps({
  modelValue: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  placeholder: { type: String, default: 'Aa' },
  variant: { type: String, default: 'messenger' },
})

const emit = defineEmits(['update:modelValue', 'send'])

const imgInput = ref(null)
const docInput = ref(null)
const pending = ref([])
const composerRef = ref(null)
const showEmojiPicker = ref(false)

const emojis = [
  '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇',
  '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚',
  '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩',
  '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣',
  '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬',
  '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗',
  '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯',
  '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐',
  '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈',
  '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾',
  '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿',
  '😾', '👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤏', '✌️', '🤞',
  '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍',
  '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝',
  '🙏', '✍️', '💅', '🤳', '💪', '🦾', '🦿', '🦵', '🦶', '👂',
  '🦻', '👃', '🧠', '🦷', '🦴', '👀', '👁️', '👅', '👄', '💋',
  '🩸', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎',
  '💔', '❤️‍🔥', '❤️‍🩹', '❣️', '💕', '💞', '💓', '💗', '💖', '💘'
]

const toggleEmojiPicker = () => {
  showEmojiPicker.value = !showEmojiPicker.value
}

const addEmoji = (emoji) => {
  const text = props.modelValue + emoji
  emit('update:modelValue', text)
  showEmojiPicker.value = false
}

const sendLike = () => {
  if (props.disabled) return
  emit('send', {
    text: '👍',
    items: [],
  })
}

const handleClickOutside = (e) => {
  if (composerRef.value && !composerRef.value.contains(e.target)) {
    showEmojiPicker.value = false
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside)
})

const addFiles = async (files, imagesOnly = false) => {
  for (const file of files) {
    if (imagesOnly && !isImageFile(file)) continue
    const maxMb = 10
    if (file.size > maxMb * 1024 * 1024) continue
    const dataUrl = await readFileAsDataURL(file)
    pending.value.push({
      name: file.name,
      preview: dataUrl,
      dataUrl,
      isImage: isImageFile(file),
    })
  }
}

const onImagesPicked = async (e) => {
  await addFiles(Array.from(e.target.files || []), true)
  e.target.value = ''
}

const onDocsPicked = async (e) => {
  await addFiles(Array.from(e.target.files || []), false)
  e.target.value = ''
}

const removePending = (idx) => {
  pending.value.splice(idx, 1)
}

const submit = () => {
  if (props.disabled) return
  if (!props.modelValue.trim() && pending.value.length === 0) return
  emit('send', {
    text: props.modelValue.trim(),
    items: pending.value.map((p) => ({
      dataUrl: p.dataUrl,
      name: p.name,
      isImage: p.isImage,
    })),
  })
  emit('update:modelValue', '')
  pending.value = []
}
</script>

<style scoped>
.chat-composer {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.composer-main {
  display: flex;
  align-items: center;
  gap: 6px;
  width: 100%;
  min-width: 0;
}

.hidden-input {
  display: none;
}

.composer-icon-btn {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: #0084ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 0;
}

.composer-icon-btn:hover {
  background: #e7f3ff;
}

.composer-icon-btn svg {
  width: 20px;
  height: 20px;
}

.composer-input-wrap {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  background: transparent !important;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 6px 12px;
  gap: 8px;
}

.composer-input-wrap input {
  flex: 1;
  min-width: 0;
  border: none !important;
  border-style: none !important;
  box-shadow: none !important;
  outline: none !important;
  background: transparent !important;
  border-radius: 0 !important;
  font-size: 14px;
  color: inherit;
  font-weight: 600;
  padding: 4px 0 !important;
}

.composer-input-wrap input::placeholder {
  color: #64748b;
  opacity: 1;
  font-weight: 600;
}

.emoji-picker-container {
  position: relative;
  display: flex;
  align-items: center;
}

.emoji-trigger-btn {
  background: transparent;
  border: none;
  font-size: 19px;
  cursor: pointer;
  padding: 2px;
  opacity: 0.7;
  transition: opacity 0.2s, transform 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  user-select: none;
}

.emoji-trigger-btn:hover {
  opacity: 1;
  transform: scale(1.15);
}

.emoji-picker-popover {
  position: absolute;
  bottom: 38px;
  right: -6px;
  width: 240px;
  height: 180px;
  background: #ffffff;
  border: 1px solid #e4e6eb;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.16);
  z-index: 10000;
  padding: 8px;
  overflow-y: auto;
}

.emoji-picker-popover::-webkit-scrollbar {
  width: 4px;
}

.emoji-picker-popover::-webkit-scrollbar-track {
  background: transparent;
}

.emoji-picker-popover::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.emoji-list {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 4px;
}

.emoji-item {
  font-size: 19px;
  cursor: pointer;
  user-select: none;
  transition: transform 0.1s;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
}

.emoji-item:hover {
  transform: scale(1.22);
  background: #f1f5f9;
}

.composer-send-btn {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 50%;
  background: #0084ff;
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  transition: background-color 0.2s, transform 0.1s;
}

.composer-send-btn:hover {
  transform: scale(1.05);
}

.composer-send-btn svg {
  width: 18px;
  height: 18px;
}

.composer-send-btn:disabled {
  background: #e4e6eb;
  color: #bcc0c4;
  cursor: not-allowed;
}

.composer-like-btn {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  font-size: 22px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  transition: transform 0.2s;
  user-select: none;
}

.composer-like-btn:hover:not(:disabled) {
  transform: scale(1.2);
}

.composer-like-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.previews-row {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  width: 100%;
}

.preview-item {
  position: relative;
  width: 64px;
  height: 64px;
  flex-shrink: 0;
}

.image-preview {
  width: 100%;
  height: 100%;
  border-radius: 10px;
  object-fit: cover;
  border: 1px solid #e2e8f0;
}

.file-preview {
  width: 100%;
  height: 100%;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4px;
  font-size: 18px;
}

.file-preview-name {
  font-size: 8px;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 100%;
  margin-top: 2px;
}

.remove-image-btn {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.6);
  color: white;
  border: none;
  font-size: 10px;
  cursor: pointer;
}

/* User widget theme */
.chat-composer.user-widget .composer-icon-btn {
  color: #2563eb;
}

.chat-composer.user-widget .composer-icon-btn:hover {
  background: #e0e7ff;
}

.chat-composer.user-widget .composer-send-btn {
  background: #2563eb;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .composer-input-wrap {
  background: transparent !important;
  border: 1px solid #475569 !important;
  border-radius: 20px !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .composer-input-wrap input {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  outline: none !important;
  color: #f8fafc !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .composer-input-wrap input::placeholder {
  color: #64748b !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .emoji-picker-popover {
  background: #181d24 !important;
  border-color: #28303d !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5) !important;
}

:is(html[data-admin-theme='dark'],
  html[data-theme='dark'],
  .admin-layout.theme-dark,
  .admin-layout.dark,
  .admin-layout.is-dark,
  body.theme-dark,
  body.dark,
  .dark) .emoji-item:hover {
  background: #222a36 !important;
}
</style>
