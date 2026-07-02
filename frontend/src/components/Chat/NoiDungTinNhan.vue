<template>
  <template v-if="hasChatAttachment(msg)">
    <div v-if="isChatImageAttachment(msg)" class="chat-image-wrap">
      <img
        :src="chatAttachmentUrl(msg)"
        class="chat-image"
        alt="Hình ảnh"
        @click="$emit('open-image', chatAttachmentUrl(msg))"
      />
    </div>
    <a
      v-else
      :href="chatAttachmentUrl(msg)"
      class="chat-file-link"
      target="_blank"
      rel="noopener noreferrer"
      @click.stop
    >
      <span class="chat-file-icon">📎</span>
      <span class="chat-file-name">{{ msg.attachment_name || 'Tệp đính kèm' }}</span>
    </a>
    <div v-if="msg.message" class="image-caption" :class="{ 'is-own': isOwn }">
      <slot name="caption">{{ msg.message }}</slot>
    </div>
  </template>
  <template v-else>
    <slot>{{ msg.message }}</slot>
  </template>
</template>

<script setup>
import { chatAttachmentUrl, hasChatAttachment, isChatImageAttachment } from '@/utils/chatMessage'

defineProps({
  msg: { type: Object, required: true },
  isOwn: { type: Boolean, default: false },
})

defineEmits(['open-image'])
</script>

<style scoped>
.chat-image-wrap {
  margin: -8px -12px 0;
  line-height: 0;
  overflow: hidden;
}

.chat-image {
  max-width: 260px;
  width: 100%;
  height: auto;
  cursor: pointer;
  display: block;
  transition: opacity .15s;
}

.chat-image:hover {
  opacity: 0.85;
}

.chat-file-link {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  background: rgba(0, 0, 0, 0.06);
  border-radius: 10px;
  color: inherit;
  text-decoration: none;
  max-width: 220px;
}

.chat-file-icon {
  font-size: 18px;
  flex-shrink: 0;
}

.chat-file-name {
  font-size: 13px;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.image-caption {
  padding-top: 8px;
  word-wrap: break-word;
  overflow-wrap: break-word;
  text-align: left;
}

.image-caption.is-own {
  text-align: right;
}
</style>
