<template>
  <div class="admin-chat-container">
    <!-- Chat Window -->
    <transition name="slide-fade">
      <div v-show="isOpen" class="chatbot-window">
        <!-- Header -->
        <div class="chat-header">
          <div class="header-info">
            <div class="avatar-wrap">
              <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix&backgroundColor=b6e3f4"
                alt="Admin" class="avatar" />
              <span class="status-dot"></span>
            </div>
            <div class="title-wrap">
              <h4 class="title">Hỗ trợ trực tuyến</h4>
              <p class="subtitle">Admin đang sẵn sàng hỗ trợ bạn</p>
            </div>
            
            <!-- Back to AI Button -->
            <button class="mode-toggle-btn" @click="switchToAI" title="Quay lại Chatbot AI">
              Tư vấn AI
            </button>

            <!-- Close Button -->
            <button class="close-btn-header" @click="isOpen = false">✕</button>
          </div>
        </div>

        <!-- Body -->
        <div class="chat-body" ref="chatBody">
          <ChatMessageRow
            v-for="(msg, index) in messages"
            :key="msg.id || msg._clientKey || `tmp-${index}`"
            :msg="msg"
            :auth-user-id="authUserId"
            api-prefix="chat"
            :side-class="isOwnMessage(msg) ? 'message-right' : 'message-left'"
            :bubble-class="isOwnMessage(msg) ? 'user' : 'admin'"
            @open-image="openImage"
            @updated="(m) => patchMessage(messages, m)"
            @deleted="(id) => removeMessageById(messages, id)"
          >
            <template v-if="!isOwnMessage(msg)" #avatar>
              <div class="bot-avatar-small">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix&backgroundColor=b6e3f4" alt="Admin" />
              </div>
            </template>
            <template #body="{ msg: rowMsg }">
              <ChatMessageBody :msg="rowMsg" :is-own="isOwnMessage(rowMsg)" @open-image="openImage">
                <span v-html="formatMessage(rowMsg.message || '')"></span>
                <template v-if="rowMsg.message" #caption>
                  <span v-html="formatMessage(rowMsg.message)"></span>
                </template>
              </ChatMessageBody>
            </template>
          </ChatMessageRow>

        </div>

        <!-- Footer -->
        <div class="chat-footer">
          <ChatComposer
            v-model="newMessage"
            variant="user-widget"
            :disabled="isLoading"
            placeholder="Nhập nội dung cần hỗ trợ..."
            @send="onComposerSend"
          />
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted, computed } from 'vue';
import api from '@/services/api';
import echo from '@/services/echo';
import { getUser } from '@/services/auth';
import ChatMessageBody from '@/components/Chat/ChatMessageBody.vue';
import ChatMessageRow from '@/components/Chat/ChatMessageRow.vue';
import ChatComposer from '@/components/Chat/ChatComposer.vue';
import {
  bindChatChannel,
  patchMessage,
  removeMessageById,
  startChatTitleNotice,
  stopChatTitleNotice,
  submitChatComposer,
} from '@/utils/chatMessage';

const isOpen = ref(false);
const isLoading = ref(false);
const newMessage = ref('');
const chatBody = ref(null);
const messages = ref([]);
const conversationId = ref(null);
const CHAT_SEND_ENDPOINT = '/chat/send';

const currentUser = ref(getUser());
const authUserId = computed(() => currentUser.value?.id);
const isOwnMessage = (msg) => Number(msg?.sender_id) === Number(authUserId.value);

const refreshCurrentUser = () => {
  currentUser.value = getUser();
  if (currentUser.value && !conversationId.value) {
    loadMessages();
  }
};

const loadMessages = async () => {
  try {
    const res = await api.get('/chat/me');
    conversationId.value = res.data.id;
    messages.value = res.data.messages || [];
    scrollToBottom();
    
    // Subscribe to real-time updates
    subscribeToChannel();
  } catch (error) {
    console.error('Lỗi load tin nhắn:', error);
  }
};

const subscribeToChannel = () => {
  bindChatChannel(
    echo,
    `chat.${conversationId.value}`,
    messages,
    authUserId.value,
    () => {
      scrollToBottom();
      if (!isOpen.value || document.hidden) startChatTitleNotice();
    }
  );
};

const switchToAI = () => {
  isOpen.value = false;
  window.dispatchEvent(new CustomEvent('open-chatbot'));
};

const formatMessage = (text) => {
  if (!text) return '';
  let formatted = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  formatted = formatted.replace(/\n/g, '<br/>');
  return formatted;
};

const scrollToBottom = async () => {
  await nextTick();
  if (chatBody.value) {
    chatBody.value.scrollTop = chatBody.value.scrollHeight;
  }
};

const openImage = (url) => {
  window.open(url, '_blank');
};

const onComposerSend = async ({ text, items }) => {
  if (isLoading.value) return;

  newMessage.value = '';
  stopChatTitleNotice();
  isLoading.value = true;

  try {
    await submitChatComposer({
      endpoint: CHAT_SEND_ENDPOINT,
      conversationId: conversationId.value,
      text,
      items,
      messagesRef: messages,
      authUserId: authUserId.value,
    });
    scrollToBottom();
  } catch (error) {
    console.error('Lỗi gửi tin nhắn:', error);
  } finally {
    isLoading.value = false;
  }
};

const handleOpenAdminChat = () => {
  isOpen.value = true;
  stopChatTitleNotice();
  window.dispatchEvent(new CustomEvent('admin-chat-state', { detail: { open: true } }));
  if (messages.value.length === 0) {
    loadMessages();
  } else {
    scrollToBottom();
  }
};

const handleToggleAdminChat = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) stopChatTitleNotice();
  window.dispatchEvent(new CustomEvent('admin-chat-state', { detail: { open: isOpen.value } }));
  if (isOpen.value) {
    if (messages.value.length === 0) loadMessages();
    else scrollToBottom();
  }
};

const handleOpenChatEvent = () => {
  isOpen.value = false;
  window.dispatchEvent(new CustomEvent('admin-chat-state', { detail: { open: false } }));
};

const handleVisibilityChange = () => {
  if (!document.hidden && isOpen.value) {
    stopChatTitleNotice();
  }
};

onMounted(() => {
  window.addEventListener('open-admin-chat', handleOpenAdminChat);
  window.addEventListener('toggle-admin-chat', handleToggleAdminChat);
  window.addEventListener('open-chatbot', handleOpenChatEvent);
  window.addEventListener('user-updated', refreshCurrentUser);
  document.addEventListener('visibilitychange', handleVisibilityChange);
  
  if (currentUser.value) loadMessages();
});

onUnmounted(() => {
  window.removeEventListener('open-admin-chat', handleOpenAdminChat);
  window.removeEventListener('toggle-admin-chat', handleToggleAdminChat);
  window.removeEventListener('open-chatbot', handleOpenChatEvent);
  window.removeEventListener('user-updated', refreshCurrentUser);
  document.removeEventListener('visibilitychange', handleVisibilityChange);
  stopChatTitleNotice();
  
  if (conversationId.value && echo) {
    echo.leaveChannel(`chat.${conversationId.value}`);
  }
});
</script>

<style scoped>
.admin-chat-container {
  position: fixed;
  bottom: 0px; 
  right: 30px;
  z-index: 10000;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.chatbot-window {
  position: absolute;
  bottom: 110px;
  right: 0;
  width: 350px;
  height: 500px;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(26, 39, 68, 0.18);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(220, 38, 38, 0.08);
  transform-origin: bottom right;
}

.slide-fade-enter-active {
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.slide-fade-leave-active {
  transition: all 0.2s ease-in;
}
.slide-fade-enter-from, .slide-fade-leave-to {
  transform: scale(0.8) translateY(20px);
  opacity: 0;
}

.chat-header {
  background: linear-gradient(135deg, #1a2744 0%, #1e3a8a 60%, #2563eb 100%);
  padding: 15px 20px;
  color: white;
  position: relative;
}

.header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar-wrap {
  position: relative;
  width: 45px;
  height: 45px;
  background: white;
  border-radius: 50%;
  padding: 2px;
}

.avatar {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.status-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 12px;
  height: 12px;
  background-color: #4ade80;
  border: 2px solid white;
  border-radius: 50%;
}

.title-wrap .title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
}

.title-wrap .subtitle {
  margin: 2px 0 0;
  font-size: 11px;
  opacity: 0.85;
}

.mode-toggle-btn {
  margin-left: auto;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 20px;
  color: white;
  padding: 4px 10px;
  font-size: 10px;
  font-weight: 600;
  cursor: pointer;
}

.close-btn-header {
  background: transparent;
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
  padding: 5px;
  margin-left: 5px;
  opacity: 0.7;
}

.chat-body {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.bot-avatar-small {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: white;
  flex-shrink: 0;
  overflow: hidden;
}
.bot-avatar-small img { width: 100%; height: 100%; }

.chat-footer {
  padding: 12px;
  background: white;
  border-top: 1px solid #f1f5f9;
}

.chat-footer :deep(.chat-composer) {
  width: 100%;
}

.chat-body :deep(.msg-bubble) {
  padding: 8px 12px;
  border-radius: 18px;
  font-size: 13px;
  line-height: 1.5;
}

.chat-body :deep(.msg-bubble.user) {
  background: #2563eb;
  color: white;
  border-bottom-right-radius: 4px;
}

.chat-body :deep(.msg-bubble.admin) {
  background: white;
  color: #1e293b;
  border-bottom-left-radius: 4px;
  border: 1px solid #e2e8f0;
}

</style>
