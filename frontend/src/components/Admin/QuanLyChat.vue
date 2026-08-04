<template>
  <div class="admin-chat-page-container">
    <div class="chat-layout-card">
      <!-- Left Sidebar: Conversations list -->
      <aside class="chat-sidebar-pane">
        <div class="pane-header">
          <div class="header-top">
            <h2>Hộp thư hỗ trợ</h2>
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
                <button type="button" @click="enterDeleteMode">Xóa cuộc trò chuyện</button>
              </div>
            </div>
          </div>

          <div v-if="deleteMode" class="delete-toolbar">
            <span>Đã chọn {{ selectedConversationIds.length }}</span>
            <div class="delete-toolbar-actions">
              <button type="button" class="toolbar-btn cancel" @click="cancelDeleteMode">Hủy</button>
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

          <div class="search-input-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" placeholder="Tìm kiếm hội thoại..." v-model="searchQuery" />
          </div>
        </div>

        <div class="conversation-list-scroll">
          <div v-if="loadingList" class="loading-state">
            <span class="spinner"></span>
            Đang tải danh sách...
          </div>
          <template v-else>
            <div 
              v-for="conv in filteredConversations" 
              :key="conv.id" 
              class="conv-item"
              :class="{ 
                active: selectedConversation?.id === conv.id,
                selecting: deleteMode 
              }"
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
              <div class="avatar-wrapper">
                <img :src="getAvatar(conv.user)" :alt="conv.user.name" class="user-avatar" />
                <span v-if="conv.user.online" class="online-dot"></span>
              </div>
              <div class="conv-details">
                <div class="conv-name-row">
                  <span class="user-name text-truncate">{{ conv.user.name || conv.user.email }}</span>
                  <span class="time-badge">{{ formatTime(conv.updated_at) }}</span>
                </div>
                <p class="last-msg-text text-truncate" :class="{ unread: conv.unread_count > 0 }">
                  {{ conv.last_message || 'Bắt đầu cuộc trò chuyện' }}
                </p>
              </div>
              <span v-if="conv.unread_count > 0" class="unread-badge"></span>
            </div>

            <div v-if="conversations.length === 0" class="empty-list-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
              </svg>
              <p>Chưa có hội thoại nào</p>
            </div>
          </template>
        </div>
      </aside>

      <!-- Right Main Panel: Active message feed -->
      <section class="chat-main-pane">
        <template v-if="selectedConversation">
          <!-- Chat Box Header -->
          <header class="chat-pane-header">
            <div class="active-user-info">
              <div class="avatar-wrapper">
                <img :src="getAvatar(selectedConversation.user)" alt="User avatar" />
                <span v-if="selectedConversation.user.online" class="online-dot"></span>
              </div>
              <div class="text-info">
                <h3>{{ selectedConversation.user.name || selectedConversation.user.email }}</h3>
                <p class="status-desc">{{ selectedConversation.user.online ? 'Đang hoạt động' : 'Ngoại tuyến' }}</p>
              </div>
            </div>

            <div class="header-actions">
              <button 
                type="button" 
                class="header-btn" 
                :class="{ active: showMessageSearch }"
                title="Tìm kiếm tin nhắn"
                @click="showMessageSearch = !showMessageSearch"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
              </button>
            </div>
          </header>

          <!-- Inside-chat search bar -->
          <transition name="slide-down">
            <div v-if="showMessageSearch" class="chat-search-bar">
              <div class="search-input-inner">
                <input
                  type="text"
                  v-model="searchMessageQuery"
                  placeholder="Tìm nội dung tin nhắn..."
                  @input="onSearchQueryInput"
                  @keyup.enter="nextMatch"
                />
                <div v-if="searchMessageQuery.trim()" class="search-nav">
                  <span class="search-count">{{ matchesCount > 0 ? currentMatchIndex + 1 : 0 }}/{{ matchesCount }}</span>
                  <button type="button" class="nav-arrow" @click="prevMatch" title="Kết quả trước">▲</button>
                  <button type="button" class="nav-arrow" @click="nextMatch" title="Kết quả sau">▼</button>
                </div>
                <button type="button" class="close-search-btn" @click="closeMessageSearch">✕</button>
              </div>
            </div>
          </transition>

          <!-- Chat body with message list -->
          <div class="chat-messages-body" ref="messagesBodyRef">
            <div v-if="loadingMessages" class="loading-state-messages">
              <span class="spinner"></span>
              Đang tải tin nhắn...
            </div>
            <template v-else>
              <ChatMessageRow
                v-for="(msg, idx) in activeMessages"
                :key="msg.id || msg._clientKey || `page-tmp-${idx}`"
                :msg="msg"
                :auth-user-id="authUserId"
                api-prefix="admin/chat"
                :side-class="isOwnMessage(msg) ? 'msg-right' : 'msg-left'"
                :bubble-class="isOwnMessage(msg) ? 'admin' : 'user'"
                @open-image="openImage"
                @updated="handleMessageUpdated"
                @deleted="handleMessageDeleted"
              >
                <template #body="{ msg: rowMsg }">
                  <ChatMessageBody :msg="rowMsg" :is-own="isOwnMessage(rowMsg)" @open-image="openImage">
                    <span v-html="formatMessageContent(rowMsg.noidung || '', searchMessageQuery)"></span>
                    <template v-if="rowMsg.noidung" #caption>
                      <span v-html="formatMessageContent(rowMsg.noidung, searchMessageQuery)"></span>
                    </template>
                  </ChatMessageBody>
                </template>
                <template v-slot:avatar v-if="!isOwnMessage(msg)">
                  <div class="msg-avatar-small">
                    <img :src="getAvatar(selectedConversation.user)" alt="User" />
                  </div>
                </template>
              </ChatMessageRow>
            </template>
          </div>

          <!-- Chat composer -->
          <footer class="chat-composer-footer">
            <ChatComposer
              v-model="newComposedMessage"
              placeholder="Nhập tin nhắn..."
              @send="onComposerSend"
              :disabled="sending"
            />
          </footer>
        </template>

        <!-- Empty state when no conversation is selected -->
        <div v-else class="empty-chat-pane">
          <div class="empty-pane-content">
            <div class="empty-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                <path d="M21 15a3 3 0 0 1-3 3H8l-5 3V6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3z" />
                <path d="M8 9h8M8 13h5" />
              </svg>
            </div>
            <h2>Hộp thư VinaTech</h2>
            <p>Chọn một cuộc hội thoại từ danh sách bên trái để bắt đầu tư vấn cho khách hàng.</p>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
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
  submitChatComposer,
} from '@/utils/chatMessage';
import ChatMessageRow from '@/components/Chat/DongTinNhan.vue';
import ChatMessageBody from '@/components/Chat/NoiDungTinNhan.vue';
import ChatComposer from '@/components/Chat/KhungSoanThaoChat.vue';

const loadingList = ref(false);
const loadingMessages = ref(false);
const sending = ref(false);

const searchQuery = ref('');
const conversations = ref([]);
const selectedConversation = ref(null);
const activeMessages = ref([]);
const newComposedMessage = ref('');

// Delete Mode logic
const showConversationMenu = ref(false);
const deleteMode = ref(false);
const selectedConversationIds = ref([]);
const deletingConversations = ref(false);

// Search inside chat logic
const showMessageSearch = ref(false);
const searchMessageQuery = ref('');
const currentMatchIndex = ref(-1);

const messagesBodyRef = ref(null);

const user = getUser();
const authUserId = computed(() => user?.id);
const isOwnMessage = (msg) => Number(msg?.id_nguoigui ?? msg?.sender_id) === Number(authUserId.value);

const filteredConversations = computed(() => {
  if (!searchQuery.value) return conversations.value;
  return conversations.value.filter(c =>
    (c.user.name || c.user.email).toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

// Matches logic inside active chat
const searchMatches = computed(() => {
  if (!searchMessageQuery.value.trim()) return [];
  const query = searchMessageQuery.value.trim().toLowerCase();
  return activeMessages.value
    .map((msg, index) => ({ msg, index }))
    .filter(item => item.msg.noidung && item.msg.noidung.toLowerCase().includes(query));
});
const matchesCount = computed(() => searchMatches.value.length);

watch(searchMessageQuery, () => {
  if (searchMessageQuery.value.trim()) {
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

const loadConversations = async (silent = false) => {
  if (!silent) loadingList.value = true;
  try {
    const res = await api.get('/admin/chat/conversations');
    conversations.value = res.data;
  } catch (error) {
    console.error('Lỗi load danh sách chat:', error);
  } finally {
    if (!silent) loadingList.value = false;
  }
};

const selectConversation = async (conv) => {
  if (deleteMode.value) return;
  if (selectedConversation.value?.id === conv.id) return;

  // Leave old Echo channel
  if (selectedConversation.value && echo) {
    echo.leaveChannel(`chat.${selectedConversation.value.id}`);
  }

  selectedConversation.value = conv;
  activeMessages.value = [];
  closeMessageSearch();

  loadingMessages.value = true;
  try {
    const res = await api.get(`/admin/chat/conversations/${conv.id}/messages`);
    activeMessages.value = res.data;
    scrollToBottom();
    subscribeToConversation(conv.id);
  } catch (error) {
    console.error('Lỗi load tin nhắn:', error);
  } finally {
    loadingMessages.value = false;
  }

  // Clear unread badge locally
  conv.unread_count = 0;
};

const subscribeToConversation = (id) => {
  if (!echo) return;
  
  const messagesRef = computed({
    get: () => activeMessages.value,
    set: (val) => { activeMessages.value = val; }
  });

  bindChatChannel(echo, `chat.${id}`, messagesRef, authUserId.value, (msg) => {
    scrollToBottom();
    // Update online status
    if (selectedConversation.value && Number(msg.id_nguoigui) === Number(selectedConversation.value.user.id)) {
      selectedConversation.value.user.online = true;
    }
  });
};

const onComposerSend = async ({ text, items }) => {
  if (!selectedConversation.value || sending.value) return;
  newComposedMessage.value = '';
  sending.value = true;

  const messagesRef = computed({
    get: () => activeMessages.value,
    set: (val) => { activeMessages.value = val; }
  });

  try {
    const lastMsgText = await submitChatComposer({
      endpoint: '/admin/chat/send',
      conversationId: selectedConversation.value.id,
      text,
      items,
      messagesRef: messagesRef,
      authUserId: authUserId.value,
    });

    if (lastMsgText) {
      selectedConversation.value.last_message = lastMsgText;
      selectedConversation.value.updated_at = new Date().toISOString();
      
      // Bump active conversation to top of list
      const idx = conversations.value.findIndex(c => c.id === selectedConversation.value.id);
      if (idx !== -1) {
        const [item] = conversations.value.splice(idx, 1);
        conversations.value.unshift(item);
      }
    }
    scrollToBottom();
  } catch (error) {
    console.error('Lỗi gửi tin nhắn admin page:', error);
  } finally {
    sending.value = false;
  }
};

const handleMessageUpdated = (msg) => {
  patchMessage(activeMessages.value, msg);
};

const handleMessageDeleted = (id) => {
  removeMessageById(activeMessages.value, id);
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
  const confirmed = await swal.confirm('Xác nhận xóa', `Bạn có chắc muốn xóa ${selectedConversationIds.value.length} đoạn chat đã chọn?`);
  if (!confirmed) return;

  deletingConversations.value = true;
  try {
    const ids = [...selectedConversationIds.value];
    await api.delete('/admin/chat/conversations', { data: { ids } });
    conversations.value = conversations.value.filter((conv) => !ids.includes(conv.id));
    
    // If deleted the active conversation, close it
    if (selectedConversation.value && ids.includes(selectedConversation.value.id)) {
      if (echo) {
        echo.leaveChannel(`chat.${selectedConversation.value.id}`);
      }
      selectedConversation.value = null;
      activeMessages.value = [];
    }

    cancelDeleteMode();
    swal.success('Đã xóa', 'Xóa các cuộc trò chuyện thành công.');
  } catch (error) {
    console.error('Lỗi xóa đoạn chat:', error);
    swal.error('Không thể xóa', error?.response?.data?.message || 'Không xóa được đoạn chat.');
  } finally {
    deletingConversations.value = false;
  }
};

const subscribeToGlobal = () => {
  if (!echo) return;

  echo.private('admin.chat').listen('.message.sent', (e) => {
    const msg = e.message;
    const convId = msg.id_cuoc_tro_chuyen || msg.conversation_id;
    const idx = conversations.value.findIndex(c => Number(c.id) === Number(convId));

    if (idx === -1) {
      loadConversations(true);
      return;
    }

    conversations.value[idx].last_message = conversationPreviewFromMessage(msg);
    conversations.value[idx].updated_at = msg.created_at;

    if (Number(msg.id_nguoigui) === Number(conversations.value[idx].user.id)) {
      conversations.value[idx].user.online = true;
    }

    const conv = conversations.value.splice(idx, 1)[0];
    conversations.value.unshift(conv);

    if (selectedConversation.value && Number(selectedConversation.value.id) === Number(convId)) {
      if (Number(msg.id_nguoigui) === Number(selectedConversation.value.user.id)) {
        selectedConversation.value.user.online = true;
      }
    } else {
      conv.unread_count = (conv.unread_count || 0) + 1;
    }
  });
};

const getAvatar = (user) => {
  if (user && user.avatar) {
    if (user.avatar.startsWith('http')) return user.avatar;
    return normalizeImageUrl(user.avatar);
  }
  const name = user ? (user.name || user.email || 'User') : 'User';
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=2563eb&color=fff&bold=true`;
};

const formatTime = (time) => {
  if (!time) return '';
  const date = new Date(time);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatMessageContent = (text, query = '') => {
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

const openImage = (url) => {
  window.open(url, '_blank');
};

const scrollToBottom = async () => {
  await nextTick();
  if (messagesBodyRef.value) {
    messagesBodyRef.value.scrollTop = messagesBodyRef.value.scrollHeight;
  }
};

const handleClickOutside = () => {
  showConversationMenu.value = false;
};

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside);
  loadConversations();
  subscribeToGlobal();
});

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside);
  if (echo) {
    echo.leaveChannel('admin.chat');
    if (selectedConversation.value) {
      echo.leaveChannel(`chat.${selectedConversation.value.id}`);
    }
  }
});
</script>

<style scoped>
.admin-chat-page-container {
  padding: 16px 24px;
  height: calc(100vh - 90px);
}

.chat-layout-card {
  display: flex;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
  height: 100%;
  overflow: hidden;
}

/* Sidebar List */
.chat-sidebar-pane {
  width: 320px;
  border-right: 1px solid #edf2f7;
  display: flex;
  flex-direction: column;
  background: #fdfdfd;
}

.pane-header {
  padding: 16px;
  border-bottom: 1px solid #edf2f7;
}

.header-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.header-top h2 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.conversation-menu-wrap {
  position: relative;
}

.conversation-menu-btn {
  display: flex;
  flex-direction: column;
  gap: 3px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  transition: background 0.2s;
}

.conversation-menu-btn:hover {
  background: #f1f5f9;
}

.conversation-menu-btn span {
  width: 4px;
  height: 4px;
  background: #64748b;
  border-radius: 50%;
}

.conversation-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: #ffffff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border: 1px solid #edf2f7;
  border-radius: 8px;
  padding: 4px 0;
  min-width: 140px;
  z-index: 10;
}

.conversation-menu button {
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  padding: 8px 12px;
  font-size: 13px;
  color: #ef4444;
  cursor: pointer;
}

.conversation-menu button:hover {
  background: #fef2f2;
}

.delete-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fef2f2;
  border: 1px solid #fee2e2;
  border-radius: 8px;
  padding: 8px 12px;
  margin-bottom: 12px;
}

.delete-toolbar span {
  font-size: 13px;
  color: #ef4444;
  font-weight: 600;
}

.delete-toolbar-actions {
  display: flex;
  gap: 8px;
}

.toolbar-btn {
  border: none;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
}

.toolbar-btn.cancel {
  background: #e2e8f0;
  color: #475569;
}

.toolbar-btn.danger {
  background: #ef4444;
  color: #ffffff;
}

.toolbar-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.search-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input-wrap svg {
  position: absolute;
  left: 12px;
  width: 16px;
  height: 16px;
  color: #94a3b8;
  pointer-events: none;
}

.search-input-wrap input {
  width: 100%;
  padding: 8px 12px 8px 36px;
  border: 1px solid #cbd5e1;
  border-radius: 20px;
  font-size: 13px;
  outline: none;
  background: #f8fafc;
  transition: all 0.2s;
}

.search-input-wrap input:focus {
  border-color: #3b82f6;
  background: #ffffff;
}

.conversation-list-scroll {
  flex: 1;
  overflow-y: auto;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 30px;
  color: #64748b;
  font-size: 13px;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.conv-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f8fafc;
  transition: background 0.2s;
  position: relative;
}

.conv-item:hover {
  background: #f1f5f9;
}

.conv-item.active {
  background: #eff6ff;
  border-left: 3px solid #3b82f6;
}

.conversation-check {
  display: flex;
  align-items: center;
  margin-right: 4px;
}

.avatar-wrapper {
  position: relative;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  flex-shrink: 0;
  background: #e2e8f0;
}

.avatar-wrapper img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.online-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 12px;
  height: 12px;
  background: #10b981;
  border: 2px solid #ffffff;
  border-radius: 50%;
}

.conv-details {
  flex: 1;
  min-width: 0;
}

.conv-name-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.conv-name-row .user-name {
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
}

.conv-name-row .time-badge {
  font-size: 11px;
  color: #94a3b8;
}

.last-msg-text {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}

.last-msg-text.unread {
  color: #0f172a;
  font-weight: 700;
}

.unread-badge {
  width: 8px;
  height: 8px;
  background: #3b82f6;
  border-radius: 50%;
  flex-shrink: 0;
}

.empty-list-state {
  text-align: center;
  padding: 40px 20px;
  color: #94a3b8;
}

.empty-list-state svg {
  width: 32px;
  height: 32px;
  margin-bottom: 8px;
}

.empty-list-state p {
  font-size: 13px;
  margin: 0;
}

/* Right Chat Feed */
.chat-main-pane {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #fafafb;
  height: 100%;
  position: relative;
}

.chat-pane-header {
  height: 64px;
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #edf2f7;
  background: #ffffff;
  flex-shrink: 0;
}

.active-user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.active-user-info h3 {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.status-desc {
  font-size: 11px;
  color: #64748b;
  margin: 2px 0 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.header-btn {
  background: none;
  border: none;
  padding: 8px;
  border-radius: 50%;
  cursor: pointer;
  color: #64748b;
  transition: all 0.2s;
}

.header-btn:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.header-btn.active {
  background: #eff6ff;
  color: #2563eb;
}

.header-btn svg {
  width: 18px;
  height: 18px;
  display: block;
}

/* Chat inside-search bar */
.chat-search-bar {
  background: #ffffff;
  padding: 10px 20px;
  border-bottom: 1px solid #edf2f7;
  flex-shrink: 0;
}

.search-input-inner {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 4px 12px;
}

.search-input-inner input {
  flex: 1;
  border: none;
  background: none;
  outline: none;
  font-size: 13px;
  padding: 4px 0;
}

.search-nav {
  display: flex;
  align-items: center;
  gap: 4px;
}

.search-count {
  font-size: 11px;
  color: #64748b;
  margin-right: 6px;
}

.nav-arrow {
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px 6px;
  font-size: 10px;
  color: #475569;
  border-radius: 4px;
}

.nav-arrow:hover {
  background: #e2e8f0;
}

.close-search-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 13px;
  color: #64748b;
}

/* Message Scroll Pane */
.chat-messages-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.loading-state-messages {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  flex: 1;
  color: #64748b;
  font-size: 13px;
}

.msg-avatar-small {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  overflow: hidden;
  margin-right: 8px;
  flex-shrink: 0;
}

.msg-avatar-small img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Chat Footer Composer */
.chat-composer-footer {
  padding: 16px 20px;
  background: #ffffff;
  border-top: 1px solid #edf2f7;
  flex-shrink: 0;
}

/* Empty pane design */
.empty-chat-pane {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
}

.empty-pane-content {
  text-align: center;
  max-width: 380px;
  padding: 20px;
}

.empty-icon-wrap {
  width: 72px;
  height: 72px;
  background: #eff6ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3b82f6;
  margin: 0 auto 16px;
}

.empty-icon-wrap svg {
  width: 36px;
  height: 36px;
}

.empty-pane-content h2 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 8px 0;
}

.empty-pane-content p {
  font-size: 13px;
  color: #64748b;
  line-height: 1.5;
  margin: 0;
}

/* Pulse / Highlight matches */
.pulse-match {
  animation: match-glow 1.5s ease-out;
}

@keyframes match-glow {
  0% { background-color: rgba(254, 240, 138, 0.7); }
  100% { background-color: transparent; }
}

.search-highlight {
  background-color: #fef08a;
  color: #000000;
}

/* Truncate utilities */
.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
