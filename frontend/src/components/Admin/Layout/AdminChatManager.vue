<template>
  <div class="admin-chat-manager" ref="managerRef">
    <!-- Trigger Button -->
    <div class="topbar-popover">
      <button class="topbar-icon-button chat-trigger" 
        :class="{ active: isListOpen }"
        type="button" 
        aria-label="Tin nhắn" 
        @click="toggleList">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
        </svg>
        <span class="icon-badge pulse" v-if="unreadCount > 0">{{ unreadCount }}</span>
      </button>

      <!-- Main Dropdown (User List) -->
      <transition name="slide-up">
        <div v-if="isListOpen" class="chat-dropdown" @mousedown.stop>
          <div class="chat-dropdown-header">
            <h3>Đoạn chat</h3>
            <div class="conversation-menu-wrap">
              <button
                type="button"
                class="conversation-menu-btn"
                aria-label="Tùy chọn đoạn chat"
                @click.stop="showConversationMenu = !showConversationMenu"
              >
                <span></span>
                <span></span>
                <span></span>
              </button>
              <div v-if="showConversationMenu" class="conversation-menu" @click.stop>
                <button type="button" @click="enterDeleteMode">Xóa</button>
              </div>
            </div>
          </div>

          <div v-if="deleteMode" class="delete-toolbar">
            <span>{{ selectedConversationIds.length }} đã chọn</span>
            <div class="delete-toolbar-actions">
              <button type="button" class="toolbar-btn" @click="cancelDeleteMode">Hủy</button>
              <button
                type="button"
                class="toolbar-btn danger"
                :disabled="selectedConversationIds.length === 0 || deletingConversations"
                @click="deleteSelectedConversations"
              >
                {{ deletingConversations ? 'Đang xóa...' : 'Xóa' }}
              </button>
            </div>
          </div>
          
          <div class="chat-search">
            <div class="search-input-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
              <input type="text" placeholder="Tìm kiếm trên Messenger" v-model="searchQuery" />
            </div>
          </div>

          <div class="user-list">
            <div 
              v-for="conv in filteredConversations" 
              :key="conv.id" 
              class="user-item"
              :class="{ active: activeConversations.some(c => c.id === conv.id), selecting: deleteMode }"
              @click="deleteMode ? toggleConversationSelection(conv.id) : selectConversation(conv)"
            >
              <label v-if="deleteMode" class="conversation-check" @click.stop>
                <input
                  type="checkbox"
                  :checked="selectedConversationIds.includes(conv.id)"
                  @change="toggleConversationSelection(conv.id)"
                />
                <span></span>
              </label>
              <div class="user-avatar-wrap">
                <img :src="getAvatar(conv.user)" :alt="conv.user.name" class="user-avatar" />
                <span v-if="conv.user.online" class="online-status"></span>
              </div>
              <div class="user-preview">
                <div class="user-header-info">
                  <span class="user-name">{{ conv.user.name || conv.user.email }}</span>
                  <span class="message-time">{{ formatTime(conv.updated_at) }}</span>
                </div>
                <p class="last-message" :class="{ unread: conv.unread_count > 0 }">
                  {{ conv.last_message || 'Bắt đầu cuộc trò chuyện' }}
                </p>
              </div>
              <div v-if="conv.unread_count > 0" class="unread-dot"></div>
            </div>

            <div v-if="conversations.length === 0" class="empty-list">
              Chưa có cuộc trò chuyện nào.
            </div>
          </div>

        </div>
      </transition>
    </div>

    <!-- Floating Conversation Views -->
    <div class="active-conversations-container">
      <transition-group name="slide-in-right">
        <div 
          v-for="(conv, index) in activeConversations" 
          :key="conv.id" 
          class="conversation-window-wrapper"
          :style="{ right: `${80 + index * 340}px` }"
          @mousedown.stop
        >
          <div class="conversation-window">
            <div class="conv-header">
              <div class="conv-header-left">
                <div class="conv-avatar-wrap">
                  <img :src="getAvatar(conv.user)" alt="Avatar" />
                  <span v-if="conv.user.online" class="online-status"></span>
                </div>
                <div class="conv-user-info">
                  <span class="conv-name text-truncate">{{ conv.user.name || conv.user.email }}</span>
                  <span class="conv-status">{{ conv.user.online ? 'Đang hoạt động' : 'Ngoại tuyến' }}</span>
                </div>
              </div>
              <div class="conv-header-actions">
                <button type="button" class="action-btn" aria-label="Tìm kiếm tin nhắn" title="Tìm kiếm tin nhắn" @click="showMessageSearchMap[conv.id] = !showMessageSearchMap[conv.id]" style="margin-right: 8px;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; display: block;">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                  </svg>
                </button>
                <button type="button" class="action-btn" aria-label="Đóng" @click="closeConversation(conv)">✕</button>
              </div>
            </div>

            <!-- Message Search Bar -->
            <transition name="slide-down">
              <div v-if="showMessageSearchMap[conv.id]" class="message-search-bar">
                <input
                  type="text"
                  v-model="searchMessageQueryMap[conv.id]"
                  placeholder="Tìm kiếm nội dung tin nhắn..."
                  @input="onSearchQueryInput(conv.id)"
                  @keyup.enter="nextMatch(conv.id)"
                />
                <div v-if="(searchMessageQueryMap[conv.id] || '').trim()" class="search-navigation">
                  <span class="search-count">{{ getMatchesCount(conv.id) > 0 ? (currentMatchIndexMap[conv.id] !== undefined ? currentMatchIndexMap[conv.id] : -1) + 1 : 0 }}/{{ getMatchesCount(conv.id) }}</span>
                  <button type="button" class="nav-btn" @click="prevMatch(conv.id)" title="Kết quả trước">▲</button>
                  <button type="button" class="nav-btn" @click="nextMatch(conv.id)" title="Kết quả tiếp">▼</button>
                </div>
                <button type="button" class="search-close-btn" @click="closeMessageSearch(conv.id)">✕</button>
              </div>
            </transition>

            <div class="conv-body" :id="`conv-body-${conv.id}`">
              <ChatMessageRow
                v-for="(msg, idx) in messagesMap[conv.id] || []"
                :key="msg.id || msg._clientKey || `tmp-${idx}`"
                :msg="msg"
                :auth-user-id="authUserId"
                api-prefix="admin/chat"
                :side-class="isOwnMessage(msg) ? 'msg-right' : 'msg-left'"
                :bubble-class="isOwnMessage(msg) ? 'admin' : 'user'"
                @open-image="openImage"
                @updated="(m) => patchMessage(messagesMap[conv.id] || [], m)"
                @deleted="(id) => removeMessageById(messagesMap[conv.id] || [], id)"
              >
                <template #body="{ msg: rowMsg }">
                  <ChatMessageBody :msg="rowMsg" :is-own="isOwnMessage(rowMsg)" @open-image="openImage">
                    <span v-html="formatMessage(rowMsg.message || '', searchMessageQueryMap[conv.id])"></span>
                    <template v-if="rowMsg.message" #caption>
                      <span v-html="formatMessage(rowMsg.message, searchMessageQueryMap[conv.id])"></span>
                    </template>
                  </ChatMessageBody>
                </template>
                <template v-if="!isOwnMessage(msg)" #avatar>
                  <div class="msg-avatar-small">
                    <img :src="getAvatar(conv.user)" alt="User" />
                  </div>
                </template>
              </ChatMessageRow>
            </div>

            <div class="conv-footer">
              <ChatComposer
                v-model="newMessagesMap[conv.id]"
                placeholder="Aa"
                @send="(data) => onComposerSend(conv, data)"
              />
            </div>
          </div>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import api from '@/services/api';
import echo from '@/services/echo';
import { getUser } from '@/services/auth';
import swal from '@/services/swal';
import { normalizeImageUrl } from '@/services/urls';

import {
  bindChatChannel,
  conversationPreviewFromMessage,
  patchMessage,
  removeMessageById,
  startChatTitleNotice,
  stopChatTitleNotice,
  submitChatComposer,
} from '@/utils/chatMessage';
import ChatMessageRow from '@/components/Chat/ChatMessageRow.vue';
import ChatMessageBody from '@/components/Chat/ChatMessageBody.vue';
import ChatComposer from '@/components/Chat/ChatComposer.vue';

const isListOpen = ref(false);
const searchQuery = ref('');
const activeConversations = ref([]); // Array of active conversations, max 3
const conversations = ref([]);
const messagesMap = ref({}); // conv.id -> Messages array
const newMessagesMap = ref({}); // conv.id -> composing message text
const showMessageSearchMap = ref({}); // conv.id -> boolean search bar visibility
const searchMessageQueryMap = ref({}); // conv.id -> search string
const currentMatchIndexMap = ref({}); // conv.id -> search match index

const managerRef = ref(null);
const showConversationMenu = ref(false);
const deleteMode = ref(false);
const selectedConversationIds = ref([]);
const deletingConversations = ref(false);
const CHAT_SEND_ENDPOINT = '/admin/chat/send';

const user = getUser();
const authUserId = computed(() => user?.id);

const getSearchMatches = (convId) => {
  const query = (searchMessageQueryMap.value[convId] || '').trim().toLowerCase();
  if (!query) return [];
  const messages = messagesMap.value[convId] || [];
  return messages
    .map((msg, index) => ({ msg, index }))
    .filter(item => item.msg.message && item.msg.message.toLowerCase().includes(query));
};

const getMatchesCount = (convId) => {
  return getSearchMatches(convId).length;
};

const scrollToMatch = (convId) => {
  const matchIndex = currentMatchIndexMap.value[convId];
  const matches = getSearchMatches(convId);
  if (matchIndex === -1 || matches.length === 0) return;
  const match = matches[matchIndex];
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

const onSearchQueryInput = (convId) => {
  const query = searchMessageQueryMap.value[convId] || '';
  if (query.trim()) {
    const matches = getSearchMatches(convId);
    currentMatchIndexMap.value[convId] = matches.length - 1; // Default to last match
    scrollToMatch(convId);
  } else {
    currentMatchIndexMap.value[convId] = -1;
  }
};

const nextMatch = (convId) => {
  const count = getMatchesCount(convId);
  if (count === 0) return;
  const current = currentMatchIndexMap.value[convId] !== undefined ? currentMatchIndexMap.value[convId] : -1;
  currentMatchIndexMap.value[convId] = (current + 1) % count;
  scrollToMatch(convId);
};

const prevMatch = (convId) => {
  const count = getMatchesCount(convId);
  if (count === 0) return;
  const current = currentMatchIndexMap.value[convId] !== undefined ? currentMatchIndexMap.value[convId] : -1;
  currentMatchIndexMap.value[convId] = (current - 1 + count) % count;
  scrollToMatch(convId);
};

const closeMessageSearch = (convId) => {
  showMessageSearchMap.value[convId] = false;
  searchMessageQueryMap.value[convId] = '';
  currentMatchIndexMap.value[convId] = -1;
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

const isOwnMessage = (msg) => Number(msg?.sender_id) === Number(authUserId.value);

const unreadCount = computed(() => {
  return conversations.value.filter(c => c.unread_count > 0).length;
});

const filteredConversations = computed(() => {
  if (!searchQuery.value) return conversations.value;
  return conversations.value.filter(c => 
    (c.user.name || c.user.email).toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const loadConversations = async () => {
  try {
    const res = await api.get('/admin/chat/conversations');
    conversations.value = res.data;
  } catch (error) {
    console.error('Lỗi load danh sách chat:', error);
  }
};

const selectConversation = async (conv) => {
  if (deleteMode.value) return;
  
  if (!activeConversations.value.find(c => c.id === conv.id)) {
    if (activeConversations.value.length >= 3) {
      activeConversations.value.shift();
    }
    activeConversations.value.push(conv);
    
    try {
      const res = await api.get(`/admin/chat/conversations/${conv.id}/messages`);
      messagesMap.value[conv.id] = res.data;
      scrollToBottom(conv.id);
      subscribeToConversation(conv.id);
    } catch (error) {
      console.error('Lỗi load tin nhắn:', error);
    }
  }
  
  isListOpen.value = false;
  stopChatTitleNotice();
  conv.unread_count = 0;
};

const enterDeleteMode = () => {
  showConversationMenu.value = false;
  deleteMode.value = true;
  selectedConversationIds.value = [];
};

const cancelDeleteMode = () => {
  deleteMode.value = false;
  selectedConversationIds.value = [];
};

const toggleConversationSelection = (id) => {
  const idx = selectedConversationIds.value.indexOf(id);
  if (idx === -1) {
    selectedConversationIds.value.push(id);
  } else {
    selectedConversationIds.value.splice(idx, 1);
  }
};

const deleteSelectedConversations = async () => {
  if (selectedConversationIds.value.length === 0 || deletingConversations.value) return;
  const confirmed = await swal.confirm('Xác nhận xóa', `Xóa ${selectedConversationIds.value.length} đoạn chat đã chọn?`);
  if (!confirmed) return;

  deletingConversations.value = true;
  try {
    const ids = [...selectedConversationIds.value];
    await api.delete('/admin/chat/conversations', { data: { ids } });
    conversations.value = conversations.value.filter((conv) => !ids.includes(conv.id));
    
    ids.forEach(id => {
      const idx = activeConversations.value.findIndex(c => c.id === id);
      if (idx !== -1) {
        closeConversation(activeConversations.value[idx]);
      }
    });

    cancelDeleteMode();
  } catch (error) {
    console.error('Lỗi xóa đoạn chat:', error);
    swal.error('Không thể xóa', error?.response?.data?.message || 'Không xóa được đoạn chat. Thử lại sau.');
  } finally {
    deletingConversations.value = false;
  }
};

const closeConversation = (conv) => {
  if (echo) {
    echo.leaveChannel(`chat.${conv.id}`);
  }
  const idx = activeConversations.value.findIndex(c => c.id === conv.id);
  if (idx !== -1) {
    activeConversations.value.splice(idx, 1);
  }
  delete messagesMap.value[conv.id];
  delete newMessagesMap.value[conv.id];
  delete showMessageSearchMap.value[conv.id];
  delete searchMessageQueryMap.value[conv.id];
  delete currentMatchIndexMap.value[conv.id];
};

const bumpConversation = (msg) => {
  const idx = conversations.value.findIndex((c) => c.id === msg.conversation_id);
  if (idx === -1) {
    loadConversations();
    startChatTitleNotice();
    return;
  }
  conversations.value[idx].last_message = conversationPreviewFromMessage(msg);
  conversations.value[idx].updated_at = msg.created_at;
  
  if (Number(msg.sender_id) === Number(conversations.value[idx].user.id)) {
    conversations.value[idx].user.online = true;
    conversations.value[idx].user.last_active_at = msg.created_at;
  }

  const conv = conversations.value.splice(idx, 1)[0];
  conversations.value.unshift(conv);

  if (activeConversations.value.find(c => c.id === msg.conversation_id)) {
    const active = activeConversations.value.find(c => c.id === msg.conversation_id);
    if (Number(msg.sender_id) === Number(active.user.id)) {
      active.user.online = true;
      active.user.last_active_at = msg.created_at;
    }
  }

  if (!activeConversations.value.find(c => c.id === msg.conversation_id)) {
    conv.unread_count = (conv.unread_count || 0) + 1;
    startChatTitleNotice();
  }
};

const subscribeToGlobal = () => {
  if (!echo) return;

  echo.private('admin.chat').listen('.message.sent', (e) => {
    bumpConversation(e.message);
  });
};

const subscribeToConversation = (id) => {
  const messagesRef = computed({
    get: () => messagesMap.value[id] || [],
    set: (val) => { messagesMap.value[id] = val; }
  });

  bindChatChannel(echo, `chat.${id}`, messagesRef, authUserId.value, (msg) => {
    scrollToBottom(id);
    const active = activeConversations.value.find(c => c.id === id);
    if (active && Number(msg.sender_id) === Number(active.user.id)) {
      active.user.online = true;
      active.user.last_active_at = msg.created_at;
    }
    const idx = conversations.value.findIndex((c) => c.id === id);
    if (idx !== -1) {
      conversations.value[idx].user.online = true;
      conversations.value[idx].user.last_active_at = msg.created_at;
    }
  });
};

const openImage = (url) => {
  window.open(url, '_blank');
};

const onComposerSend = async (conv, { text, items }) => {
  newMessagesMap.value[conv.id] = '';
  stopChatTitleNotice();

  const messagesRef = computed({
    get: () => messagesMap.value[conv.id] || [],
    set: (val) => { messagesMap.value[conv.id] = val; }
  });

  try {
    const lastMsgText = await submitChatComposer({
      endpoint: CHAT_SEND_ENDPOINT,
      conversationId: conv.id,
      text,
      items,
      messagesRef: messagesRef,
      authUserId: authUserId.value,
    });

    if (lastMsgText) {
      conv.last_message = lastMsgText;
      conv.updated_at = new Date().toISOString();
      const idx = conversations.value.findIndex(c => c.id === conv.id);
      if (idx !== -1) {
        const [item] = conversations.value.splice(idx, 1);
        conversations.value.unshift(item);
      }
    }
    scrollToBottom(conv.id);
  } catch (error) {
    console.error('Lỗi gửi tin nhắn admin:', error);
  }
};

const toggleList = () => {
  isListOpen.value = !isListOpen.value;
  showConversationMenu.value = false;
  if (isListOpen.value) {
    loadConversations();
  }
};

const getAvatar = (user) => {
  if (user && user.avatar) {
    if (user.avatar.startsWith('http')) return user.avatar;
    return normalizeImageUrl(user.avatar);
  }
  const name = user ? (user.name || user.email || 'User') : 'User';
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=3b82f6&color=fff&bold=true`;
};

const formatTime = (time) => {
  if (!time) return '';
  const date = new Date(time);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const scrollToBottom = async (convId) => {
  await nextTick();
  const el = document.getElementById(`conv-body-${convId}`);
  if (el) {
    el.scrollTop = el.scrollHeight;
  }
};

const handleClickOutside = (e) => {
  if (managerRef.value && !managerRef.value.contains(e.target)) {
    isListOpen.value = false;
    showConversationMenu.value = false;
  }
};

const handleVisibilityChange = () => {
  if (!document.hidden && activeConversations.value.length > 0) {
    stopChatTitleNotice();
  }
};

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside);
  document.addEventListener('visibilitychange', handleVisibilityChange);
  loadConversations();
  subscribeToGlobal();
});

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside);
  document.removeEventListener('visibilitychange', handleVisibilityChange);
  stopChatTitleNotice();
  if (echo) {
    echo.leaveChannel('admin.chat');
    activeConversations.value.forEach(c => echo.leaveChannel(`chat.${c.id}`));
  }
});
</script>

<style scoped>
.admin-chat-manager {
  position: relative;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.topbar-icon-button {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 1px solid rgba(15,23,42,.12);
  background: #fff;
  color: #1f2937;
  display: grid;
  place-items: center;
  cursor: pointer;
  position: relative;
  transition: all 0.2s;
}

.topbar-icon-button:hover, .topbar-icon-button.active {
  background: #eef2ff;
  color: #2563eb;
  border-color: #2563eb;
}

.topbar-icon-button svg {
  width: 20px;
  height: 20px;
}

.icon-badge {
  position: absolute;
  top: 5px;
  right: 5px;
  min-width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #ef4444;
  color: white;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #fff;
}

.pulse {
  animation: pulse-red 2s infinite;
}

@keyframes pulse-red {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.chat-dropdown {
  position: absolute;
  top: calc(100% + 15px);
  right: -80px;
  width: 360px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12), 0 8px 10px rgba(0, 0, 0, 0.08);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  max-height: 550px;
}

.chat-dropdown-header {
  padding: 16px 16px 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chat-dropdown-header h3 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #050505;
}

.conversation-menu-wrap {
  position: relative;
}

.conversation-menu-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: #65676b;
  display: grid;
  place-content: center;
  gap: 2px;
  cursor: pointer;
}

.conversation-menu-btn:hover {
  background: #f0f2f5;
  color: #050505;
}

.conversation-menu-btn span {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: currentColor;
  display: block;
}

.conversation-menu {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  min-width: 110px;
  background: #fff;
  border: 1px solid #e4e6eb;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.16);
  overflow: hidden;
  z-index: 20;
}

.conversation-menu button {
  width: 100%;
  border: none;
  background: #fff;
  color: #e41e3f;
  padding: 10px 14px;
  font-size: 13px;
  font-weight: 600;
  text-align: left;
  cursor: pointer;
}

.conversation-menu button:hover {
  background: #fff1f2;
}

.delete-toolbar {
  margin: 0 16px 12px;
  padding: 10px 12px;
  border-radius: 8px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  color: #334155;
  font-size: 13px;
  font-weight: 600;
}

.delete-toolbar-actions {
  display: flex;
  gap: 6px;
}

.toolbar-btn {
  border: none;
  border-radius: 6px;
  background: #e4e6eb;
  color: #050505;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.toolbar-btn.danger {
  background: #e41e3f;
  color: #fff;
}

.toolbar-btn:disabled {
  opacity: .55;
  cursor: not-allowed;
}

.chat-search {
  padding: 0 16px 12px;
}

.search-input-wrap {
  background: #f0f2f5;
  border-radius: 20px;
  padding: 8px 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.search-input-wrap svg {
  width: 16px;
  height: 16px;
  color: #65676b;
}

.search-input-wrap input {
  border: none;
  background: transparent;
  outline: none;
  flex: 1;
  font-size: 14px;
}

.user-list {
  flex: 1;
  overflow-y: auto;
  padding: 0 8px;
}

.empty-list {
  padding: 20px;
  text-align: center;
  color: #65676b;
  font-style: italic;
}

.user-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
  position: relative;
}

.user-item:hover, .user-item.active {
  background: #f2f2f2;
}

.user-item.selecting {
  cursor: default;
}

.conversation-check {
  width: 24px;
  height: 24px;
  flex: 0 0 24px;
  display: grid;
  place-items: center;
  cursor: pointer;
}

.conversation-check input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.conversation-check span {
  width: 18px;
  height: 18px;
  border-radius: 5px;
  border: 2px solid #cbd5e1;
  background: #fff;
  display: block;
  position: relative;
}

.conversation-check input:checked + span {
  background: #2563eb;
  border-color: #2563eb;
}

.conversation-check input:checked + span::after {
  content: '';
  position: absolute;
  left: 4px;
  top: 1px;
  width: 5px;
  height: 9px;
  border: solid #fff;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.user-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}

.user-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  object-fit: cover;
}

.online-status {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 14px;
  height: 14px;
  background: #31a24c;
  border-radius: 50%;
  border: 2px solid #fff;
}

.user-preview {
  flex: 1;
  min-width: 0;
}

.user-header-info {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.user-name {
  font-size: 15px;
  font-weight: 500;
  color: #050505;
}

.message-time {
  font-size: 12px;
  color: #65676b;
}

.last-message {
  margin: 0;
  font-size: 13px;
  color: #65676b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.last-message.unread {
  font-weight: 600;
  color: #050505;
}

.unread-dot {
  width: 12px;
  height: 12px;
  background: #0084ff;
  border-radius: 50%;
  margin-left: 8px;
}

.active-conversations-container {
  position: fixed;
  bottom: 0;
  right: 0;
  left: 0;
  height: 0;
  pointer-events: none;
  z-index: 2000;
  display: flex;
  flex-direction: row-reverse;
}

.conversation-window-wrapper {
  position: fixed;
  bottom: 0;
  width: 330px;
  height: 480px;
  pointer-events: auto;
  transition: right 0.3s ease;
  z-index: 2000;
}

.conversation-window {
  width: 100%;
  height: 100%;
  background: #fff;
  border-radius: 8px 8px 0 0;
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12), 0 8px 10px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
}

.conv-header {
  padding: 8px 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(0,0,0,0.1);
}

.conv-header-left {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 0;
}

.back-btn {
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: #0084ff;
  display: flex;
}

.back-btn svg { width: 20px; height: 20px; }

.conv-avatar-wrap img {
  width: 32px;
  height: 32px;
  border-radius: 50%;
}

.conv-user-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.conv-name {
  font-size: 14px;
  font-weight: 600;
}

.conv-status {
  font-size: 11px;
  color: #65676b;
}

.conv-header-actions {
  display: flex;
  gap: 4px;
}

.action-btn {
  background: none;
  border: none;
  padding: 6px;
  border-radius: 50%;
  cursor: pointer;
  color: #0084ff;
  display: flex;
}

.conv-body {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: #fff;
}

.conv-body :deep(.chat-message-row) {
  max-width: 85%;
}

.msg-avatar-small img {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  margin-top: 4px;
}

.conv-body :deep(.msg-bubble) {
  padding: 8px 12px;
  border-radius: 18px;
  font-size: 14px;
  line-height: 1.4;
}

.conv-body :deep(.msg-bubble.user) {
  background: #f0f2f5;
  color: #050505;
}

.conv-body :deep(.msg-bubble.admin) {
  background: #0084ff;
  color: #fff;
}

.conv-footer {
  padding: 8px 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  border-top: 1px solid #ebedef;
  flex-shrink: 0;
}

.slide-up-enter-active, .slide-up-leave-active { transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1); }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(10px); opacity: 0; }

.slide-in-right-enter-active, .slide-in-right-leave-active { transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); }
.slide-in-right-enter-from, .slide-in-right-leave-to { transform: translateX(20px); opacity: 0; }

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Message Search Bar styles */
.message-search-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: #f8fafc;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  z-index: 10;
  flex-shrink: 0;
}

.message-search-bar input {
  flex: 1;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 6px 10px;
  font-size: 13px;
  outline: none;
  background: #fff;
}

.message-search-bar input:focus {
  border-color: #0084ff;
}

.search-navigation {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.search-count {
  font-size: 12px;
  color: #64748b;
  margin-right: 4px;
  font-weight: 600;
}

.nav-btn {
  border: 1px solid #cbd5e1;
  background: #fff;
  border-radius: 4px;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 10px;
}

.nav-btn:hover {
  background: #f1f5f9;
}

.search-close-btn {
  border: none;
  background: transparent;
  font-size: 16px;
  cursor: pointer;
  color: #64748b;
  padding: 4px;
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
