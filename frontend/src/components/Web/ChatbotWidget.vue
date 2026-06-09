<template>
  <div class="chatbot-container">
    <!-- Bubble Button hidden, handled by FloatingContactMenu -->
    <div v-if="false" class="chatbot-bubble glow-effect" role="button" tabindex="0" @click="toggleChat"
      @keydown.enter.prevent="toggleChat" @keydown.space.prevent="toggleChat"
      :class="{ 'pulse-animation': !isOpen && !isAdminOpen && messages.length === 1 }">
      <i v-if="!isOpen && !isAdminOpen" class="chat-icon">💬</i>
      <i v-else class="close-icon">❌</i>

    </div>

    <!-- Chat Window -->
    <transition name="slide-fade">
      <div v-show="isOpen" class="chatbot-window">
        <!-- Header -->
        <div class="chat-header">
          <div class="header-info">
            <div class="avatar-wrap">
              <img src="/support_avatar.png"
                alt="Nhân viên tư vấn" class="avatar" />
              <span class="status-dot"></span>
            </div>
            <div class="title-wrap">
              <h4 class="title">Tư vấn trực tuyến</h4>
              <p class="subtitle">Mia - Chuyên viên hỗ trợ VinaTech</p>
            </div>
            <!-- Button to open Admin Chat -->
            <button class="mode-toggle-btn" @click="switchToAdmin" title="Nhắn cho Admin">
              Nhắn Admin
            </button>
            <button class="chat-close-btn" type="button" @click="isOpen = false" aria-label="Đóng chat">✕</button>
          </div>
        </div>

        <!-- Body -->
        <div class="chat-body" ref="chatBody">
          <div v-for="(msg, index) in messages" :key="index" class="message-wrapper"
            :class="msg.role === 'user' ? 'message-right' : 'message-left'">
            <div v-if="msg.role === 'bot'" class="bot-avatar-small">
              <img src="/support_avatar.png" alt="Bot" />
            </div>

            <div class="message-bubble" :class="msg.role">
              <span v-html="formatMessage(msg.content)"></span>

              <!-- Danh sách sản phẩm nếu có -->
              <div v-if="msg.products && msg.products.length" class="chatbot-products">
                <div v-for="prod in msg.products" :key="prod.id_bienthe" class="bot-product-card"
                  @click="goToProduct(prod)">
                  <img :src="getProductImage(prod)" :alt="getDisplayName(prod)" class="bot-product-img" />
                  <div class="bot-product-info">
                    <div class="bot-product-name">{{ getDisplayName(prod) }}</div>
                    <div class="bot-product-price">{{ formatPrice(prod.gia) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="isLoading" class="message-wrapper message-left">
            <div class="bot-avatar-small">
              <img src="/support_avatar.png" alt="Bot" />
            </div>
            <div class="message-bubble bot typing-indicator">
              <span></span><span></span><span></span>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="chat-footer">
          <form @submit.prevent="sendMessage" class="input-form">
            <input type="text" v-model="newMessage" placeholder="Trò chuyện với Mia (ví dụ: tư vấn laptop văn phòng)..."
              :disabled="isLoading" autocomplete="off" />
            <button type="submit" :disabled="!newMessage.trim() || isLoading" class="send-btn">
              ➤
            </button>
          </form>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { productImageUrl, storageUrl } from '@/services/urls';
import { getToken } from '@/services/auth';
import swal from '@/services/swal';

const isOpen = ref(false);
const isLoading = ref(false);
const currentChatMode = ref('ai'); // 'ai' or 'admin'
const isAdminOpen = ref(false);
const newMessage = ref('');
const chatBody = ref(null);
const router = useRouter();

const goToProduct = (bt) => {
  if (!bt) return;
  const spId = bt.id_sanpham || (bt.san_pham?.id_sanpham) || (bt.sanPham?.id_sanpham);
  if (!spId) return;

  router.push(`/products/${spId}?variant=${bt.id_bienthe}`);
};

const getDisplayName = (bt) => {
  if (!bt) return '';
  const sp = bt.san_pham || bt.sanPham;
  const name = sp ? sp.tenSP : 'Sản phẩm';
  
  // Lấy vài thông số mẫu từ sản phẩm (nếu có)
  let specPart = '';
  if (sp && sp.thong_so_ky_thuat) {
    const specs = sp.thong_so_ky_thuat;
    const cpu = specs.CPU || specs.cpu;
    const ram = specs.RAM || specs.ram;
    if (cpu || ram) {
      specPart = `(${cpu || ''}${cpu && ram ? ' / ' : ''}${ram || ''})`;
    }
  }

  return `${name} ${specPart} - ${bt.ten_bienthe}`;
};

const formatPrice = (price) => {
  if (!price) return '0 ₫';
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
};

const getProductImage = (bt) => {
  const sp = bt.san_pham || bt.sanPham;
  const image = sp ? sp.hinhanh : bt.hinhanh; // Ưu tiên hình ảnh sản phẩm chính
  
  if (!image) return 'https://via.placeholder.com/150';
  if (image.startsWith('http')) return image;
  return storageUrl(image);
};

const messages = ref([
  {
    role: 'bot',
    content: "Xin chào anh/chị! Em là Mia, chuyên viên hỗ trợ của VinaTech. Rất vui được đồng hành cùng anh/chị. Anh/chị đang cần tìm kiếm dòng máy nào (văn phòng, đồ họa hay gaming) trong tầm giá bao nhiêu ạ? Em sẽ tư vấn chi tiết cho mình nhé!"
  }
]);

const toggleChat = () => {
  if (currentChatMode.value === 'admin') {
    window.dispatchEvent(new CustomEvent('toggle-admin-chat'));
    return;
  }
  
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    scrollToBottom();
  }
};

const switchToAdmin = () => {
  if (!getToken()) {
    swal.info('Cần đăng nhập', 'Bạn vui lòng đăng nhập để nhắn tin trực tiếp với admin.')
    router.push('/login')
    return
  }
  isOpen.value = false; // Close AI Chat
  currentChatMode.value = 'admin';
  window.dispatchEvent(new CustomEvent('open-admin-chat')); // Trigger Admin Chat
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

const sendMessage = async () => {
  if (!newMessage.value.trim() || isLoading.value) return;

  const userText = newMessage.value.trim();
  messages.value.push({ role: 'user', content: userText });
  newMessage.value = '';
  isLoading.value = true;
  await scrollToBottom();

  try {
    const response = await api.post('/chat', { message: userText });

    if (response.data.reply) {
      messages.value.push({
        role: 'bot',
        content: response.data.reply,
        products: response.data.products || []
      });
    } else {
      messages.value.push({
        role: 'bot',
        content: 'Bot chưa có phản hồi hợp lệ từ server.'
      });
    }
  } catch (error) {
    console.error('Chat error full:', error);

    if (error?.response?.data?.reply) {
      messages.value.push({
        role: 'bot',
        content: error.response.data.reply
      });
    } else if (error?.response?.data?.message) {
      messages.value.push({
        role: 'bot',
        content: 'Lỗi backend: ' + error.response.data.message
      });
    } else {
      messages.value.push({
        role: 'bot',
        content: 'Không gọi được API chat. Kiểm tra Laravel route /api/chat và controller.'
      });
    }
  } finally {
    isLoading.value = false;
    await scrollToBottom();
  }
};

// Listen for global interaction events
const handleOpenChatEvent = () => {
  currentChatMode.value = 'ai';
  isOpen.value = true;
  scrollToBottom();
};

const handleOpenAdminChatEvent = () => {
  currentChatMode.value = 'admin';
  isOpen.value = false; // Ensure AI is closed
};

const handleAdminStateEvent = (e) => {
  isAdminOpen.value = !!(e && e.detail && e.detail.open);
  if (isAdminOpen.value) {
    currentChatMode.value = 'admin';
  }
};

onMounted(() => {
  window.addEventListener('open-chatbot', handleOpenChatEvent);
  window.addEventListener('open-admin-chat', handleOpenAdminChatEvent);
  window.addEventListener('admin-chat-state', handleAdminStateEvent);
});

onUnmounted(() => {
  window.removeEventListener('open-chatbot', handleOpenChatEvent);
  window.removeEventListener('open-admin-chat', handleOpenAdminChatEvent);
  window.removeEventListener('admin-chat-state', handleAdminStateEvent);
});
</script>

<style scoped>
.chatbot-container {
  position: fixed;
  bottom: 28px;
  right: 24px;
  z-index: 9999;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* ===== BUBBLE BUTTON ===== */
.chatbot-bubble {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1a2744, #2563eb);
  color: white;
  border: none;
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.28);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 21px;
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.chatbot-bubble:hover {
  transform: translateY(-2px);
}

.glow-effect {
  position: relative;
}

.pulse-animation::before {
  content: '';
  position: absolute;
  top: -3px;
  left: -3px;
  right: -3px;
  bottom: -3px;
  border-radius: 50%;
  background: rgba(37, 99, 235, 0.18);
  z-index: -1;
  animation: pulse 2.8s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(0.95);
    opacity: 0.55;
  }

  100% {
    transform: scale(1.32);
    opacity: 0;
  }
}

/* ===== CHAT WINDOW ===== */
.chatbot-window {
  position: absolute;
  bottom: 86px;
  right: 0;
  width: 350px;
  height: 500px;
  background: #111f35;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(26, 39, 68, 0.18);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(37, 99, 235, 0.08);
  transform-origin: bottom right;
}

.chat-close-btn {
  margin-left: auto;
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.8);
  font-size: 18px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 8px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-close-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  color: white;
}

@media (max-width: 640px) {
  .chatbot-container {
    right: 18px;
    bottom: 20px;
  }

  .chatbot-bubble {
    width: 48px;
    height: 48px;
    font-size: 20px;
  }
}

.slide-fade-enter-active {
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.slide-fade-leave-active {
  transition: all 0.2s ease-in;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: scale(0.8) translateY(20px);
  opacity: 0;
}

/* ===== HEADER ===== */
.chat-header {
  background: linear-gradient(135deg, #1a2744 0%, #1e3a8a 60%, #2563eb 100%);
  padding: 15px 20px;
  color: white;
  position: relative;
}

.mode-toggle-btn {
  margin-left: auto;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 20px;
  color: white;
  padding: 5px 12px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.mode-toggle-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.header-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.avatar-wrap {
  position: relative;
  width: 45px;
  height: 45px;
  background: #111f35;
  border-radius: 50%;
  padding: 2px;
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
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
  font-size: 16px;
  font-weight: 700;
}

.title-wrap .subtitle {
  margin: 2px 0 0;
  font-size: 12px;
  opacity: 0.85;
}

/* ===== BODY ===== */
.chat-body {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  background: #f0f4ff;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.chat-body::-webkit-scrollbar {
  width: 5px;
}

.chat-body::-webkit-scrollbar-track {
  background: transparent;
}

.chat-body::-webkit-scrollbar-thumb {
  background: #bfcfef;
  border-radius: 5px;
}

.message-wrapper {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  max-width: 90%;
}

.message-right {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.message-left {
  align-self: flex-start;
}

.bot-avatar-small {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: #111f35;
  flex-shrink: 0;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.1);
}

.bot-avatar-small img {
  width: 100%;
  height: 100%;
}

.message-bubble {
  padding: 12px 16px;
  border-radius: 18px;
  font-size: 14px;
  line-height: 1.5;
  word-wrap: break-word;
}

/* Tin nhắn người dùng — xanh blue chủ đạo */
.message-bubble.user {
  background: linear-gradient(135deg, #1e3a8a, #2563eb);
  color: white;
  border-bottom-right-radius: 4px;
  box-shadow: 0 2px 12px rgba(37, 99, 235, 0.25);
}

/* Tin nhắn bot — trắng với viền xanh nhẹ */
.message-bubble.bot {
  background: #111f35;
  color: #e2e8f0;
  border-bottom-left-radius: 4px;
  border: 1px solid rgba(37, 99, 235, 0.1);
  box-shadow: 0 2px 10px rgba(37, 99, 235, 0.06);
}

/* ===== BOT PRODUCT CARDS ===== */
.chatbot-products {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 12px;
}

.bot-product-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #0d1b2e;
  border: 1px solid rgba(37, 99, 235, 0.15);
  border-radius: 12px;
  padding: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
}

.bot-product-card:hover {
  transform: translateY(-2px);
  border-color: #2563eb;
  background: #111f35;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
}

.bot-product-img {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 8px;
  background: #111f35;
  border: 1px solid rgba(255,255,255,0.07);
}

.bot-product-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.bot-product-name {
  font-size: 13px;
  font-weight: 600;
  color: #e2e8f0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.3;
  margin-bottom: 4px;
}

.bot-product-price {
  font-size: 13px;
  font-weight: 700;
  color: #ef4444;
}

/* ===== TYPING INDICATOR ===== */
.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 15px 18px;
  align-items: center;
}

.typing-indicator span {
  width: 6px;
  height: 6px;
  background: #2563eb;
  border-radius: 50%;
  animation: typing 1.4s infinite ease-in-out;
  opacity: 0.5;
}

.typing-indicator span:nth-child(1) {
  animation-delay: 0s;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {

  0%,
  100% {
    transform: translateY(0);
    opacity: 0.5;
  }

  50% {
    transform: translateY(-4px);
    opacity: 1;
  }
}

/* ===== FOOTER ===== */
.chat-footer {
  padding: 15px;
  background: #111f35;
  border-top: 1px solid rgba(37, 99, 235, 0.08);
}

.input-form {
  display: flex;
  gap: 10px;
  background: #f0f4ff;
  padding: 5px 5px 5px 15px;
  border-radius: 30px;
  border: 1px solid transparent;
  transition: all 0.3s;
}

.input-form:focus-within {
  background: #111f35;
  border-color: #2563eb;
  box-shadow: 0 2px 12px rgba(37, 99, 235, 0.15);
}

.input-form input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 14px;
  outline: none;
  color: #e2e8f0;
}

.input-form input::placeholder {
  color: #475569;
}

/* Nút gửi — xanh blue */
.send-btn {
  background: linear-gradient(135deg, #1e3a8a, #2563eb);
  color: white;
  border: none;
  width: 35px;
  height: 35px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s, opacity 0.2s;
}

.send-btn:hover:not(:disabled) {
  transform: scale(1.08);
  opacity: 0.9;
}

.send-btn:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}
</style>
