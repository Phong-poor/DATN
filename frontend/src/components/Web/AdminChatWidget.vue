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

            <!-- Message Search Button -->
            <button class="mode-toggle-btn search-trigger" @click="showMessageSearch = !showMessageSearch" title="Tìm kiếm tin nhắn" style="padding: 4px; display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; margin-left: 8px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; display: block;">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
              </svg>
            </button>

            <!-- Close Button -->
            <button class="close-btn-header" @click="isOpen = false">✕</button>
          </div>
        </div>

        <!-- Message Search Bar -->
        <transition name="slide-down">
          <div v-if="showMessageSearch" class="message-search-bar">
            <input
              type="text"
              v-model="searchMessageQuery"
              placeholder="Tìm tin nhắn..."
              ref="searchMsgInput"
              @keyup.enter="nextMatch"
            />
            <div v-if="searchMessageQuery.trim()" class="search-navigation">
              <span class="search-count">{{ matchesCount > 0 ? currentMatchIndex + 1 : 0 }}/{{ matchesCount }}</span>
              <button type="button" class="nav-btn" @click="prevMatch" title="Trước">▲</button>
              <button type="button" class="nav-btn" @click="nextMatch" title="Sau">▼</button>
            </div>
            <button type="button" class="search-close-btn" @click="closeMessageSearch">✕</button>
          </div>
        </transition>

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
                <span v-html="formatMessage(rowMsg.noidung || '', searchMessageQuery)"></span>
                <template v-if="rowMsg.noidung" #caption>
                  <span v-html="formatMessage(rowMsg.noidung, searchMessageQuery)"></span>
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
import { ref, nextTick, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import echo from '@/services/echo';
import { getUser, getToken } from '@/services/auth';
import swal from '@/services/swal';
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
const router = useRouter();

const currentUser = ref(getUser());
const authUserId = computed(() => currentUser.value?.id);
const isOwnMessage = (msg) => Number(msg?.id_nguoigui) === Number(authUserId.value);

// Message search logic
const showMessageSearch = ref(false);
const searchMessageQuery = ref('');
const currentMatchIndex = ref(-1);
const searchMsgInput = ref(null);

const searchMatches = computed(() => {
  if (!searchMessageQuery.value.trim()) return [];
  const query = searchMessageQuery.value.trim().toLowerCase();
  return messages.value
    .map((msg, index) => ({ msg, index }))
    .filter(item => item.msg.noidung && item.msg.noidung.toLowerCase().includes(query));
});

const matchesCount = computed(() => searchMatches.value.length);

watch(searchMessageQuery, (newVal) => {
  if (newVal.trim()) {
    currentMatchIndex.value = searchMatches.value.length - 1; // Default to last match
    scrollToMatch();
  } else {
    currentMatchIndex.value = -1;
  }
});

const scrollToMatch = () => {
  if (currentMatchIndex.value === -1 || searchMatches.value.length === 0) return;
  const match = searchMatches.value[currentMatchIndex.value];
  if (!match) return;

  nextTick(() => {
    const el = document.getElementById(`msg-row-${match.msg.id || match.msg._clientKey}`);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      el.classList.add('pulse-match');
      setTimeout(() => {
        el.classList.remove('pulse-match');
      }, 1500);
    }
  });
};

const nextMatch = () => {
  if (matchesCount.value === 0) return;
  currentMatchIndex.value = (currentMatchIndex.value + 1) % matchesCount.value;
  scrollToMatch();
};

const prevMatch = () => {
  if (matchesCount.value === 0) return;
  currentMatchIndex.value = (currentMatchIndex.value - 1 + matchesCount.value) % matchesCount.value;
  scrollToMatch();
};

const closeMessageSearch = () => {
  showMessageSearch.value = false;
  searchMessageQuery.value = '';
  currentMatchIndex.value = -1;
};

const ensureAuthenticated = () => {
  currentUser.value = getUser();
  if (getToken() && currentUser.value) return true;
  swal.info('Cần đăng nhập', 'Bạn vui lòng đăng nhập để nhắn tin trực tiếp với admin.');
  router.push('/login');
  return false;
};

const refreshCurrentUser = () => {
  currentUser.value = getUser();
  if (!currentUser.value || !getToken()) {
    if (conversationId.value && echo) {
      echo.leaveChannel(`chat.${conversationId.value}`);
    }
    conversationId.value = null;
    messages.value = [];
  } else if (!conversationId.value) {
    loadMessages();
  }
};

const loadMessages = async () => {
  const token = getToken();
  if (!token || !currentUser.value) {
    return;
  }
  try {
    const res = await api.get('/chat/me');
    if (res && res.data) {
      conversationId.value = res.data.id;
      messages.value = res.data.messages || [];
      scrollToBottom();
      
      // Subscribe to real-time updates
      subscribeToChannel();
    }
  } catch (error) {
    console.error('Lỗi load tin nhắn:', error);
    messages.value = [];
  }
};

const subscribeToChannel = () => {
  if (!conversationId.value || !echo) return;
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

const formatMessage = (text, query = '') => {
  if (!text) return '';
  let escaped = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
    
  let formatted = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
  
  if (query && query.trim()) {
    const escapedQuery = query.trim().replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    const regex = new RegExp(`(${escapedQuery})`, 'gi');
    formatted = formatted.replace(regex, '<mark class="search-highlight">$1</mark>');
  }
  
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
  if (!getToken() || !currentUser.value) return;

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
  if (!ensureAuthenticated()) return;
  isOpen.value = true;
  stopChatTitleNotice();
  window.dispatchEvent(new CustomEvent('admin-chat-state', { detail: { open: true } }));
  if (getToken() && currentUser.value) {
    if (messages.value.length === 0) {
      loadMessages();
    } else {
      scrollToBottom();
    }
  }
};

const handleToggleAdminChat = () => {
  if (!isOpen.value && !ensureAuthenticated()) return;
  isOpen.value = !isOpen.value;
  if (isOpen.value) stopChatTitleNotice();
  window.dispatchEvent(new CustomEvent('admin-chat-state', { detail: { open: isOpen.value } }));
  if (isOpen.value && getToken() && currentUser.value) {
    if (messages.value.length === 0) loadMessages();
    else scrollToBottom();
  }
};

const handleOpenChatEvent = () => {
  isOpen.value = false;
  window.dispatchEvent(new CustomEvent('admin-chat-state', { detail: { open: false } }));
  closeMessageSearch();
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
  
  if (getToken() && currentUser.value) loadMessages();
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
  background: var(--tn-surface);
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
  background: var(--tn-bg);
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
  border: 1px solid var(--tn-border);
}

/* Message Search Bar styles */
.message-search-bar {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #f8fafc;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  z-index: 10;
  flex-shrink: 0;
}

.message-search-bar input {
  flex: 1;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 12px;
  outline: none;
  background: #fff;
  color: #334155;
}

.message-search-bar input:focus {
  border-color: #2563eb;
}

.search-navigation {
  display: flex;
  align-items: center;
  gap: 3px;
  flex-shrink: 0;
}

.search-count {
  font-size: 11px;
  color: #64748b;
  margin-right: 2px;
  font-weight: 600;
}

.nav-btn {
  border: 1px solid #cbd5e1;
  background: #fff;
  border-radius: 4px;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 8px;
  color: #334155;
}

.nav-btn:hover {
  background: #f1f5f9;
}

.search-close-btn {
  border: none;
  background: transparent;
  font-size: 14px;
  cursor: pointer;
  color: #64748b;
  padding: 2px;
}

.search-close-btn:hover {
  color: #0f172a;
}

.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.25s ease;
}
.slide-down-enter-from, .slide-down-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}

/* Search Highlight styles */
:deep(.search-highlight) {
  background-color: #fef08a !important;
  color: #0f172a !important;
  padding: 1px 2px;
  border-radius: 3px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

:deep(.pulse-match) {
  animation: pulse-match-anim 1.5s ease-in-out;
}

@keyframes pulse-match-anim {
  0% { background-color: transparent; }
  25% { background-color: rgba(254, 240, 138, 0.4); }
  50% { background-color: transparent; }
  75% { background-color: rgba(254, 240, 138, 0.4); }
  100% { background-color: transparent; }
}
</style>
