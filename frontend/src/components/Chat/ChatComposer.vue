<template>
  <div class="chat-composer" :class="variant">
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
      </div>

      <button
        type="button"
        class="composer-send-btn"
        aria-label="Gửi tin nhắn"
        :disabled="disabled || (!modelValue.trim() && pending.length === 0)"
        @click="submit"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m22 2-7 20-4-9-9-4Z"/>
          <path d="M22 2 11 13"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
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
  background: #f0f2f5;
  border-radius: 20px;
  padding: 6px 10px 6px 14px;
}

.composer-input-wrap input {
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent;
  outline: none;
  font-size: 14px;
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
</style>
