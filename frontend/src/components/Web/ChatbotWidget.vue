<template>
  <div class="chatbot-container">
    <!-- Bubble Button -->
    <button class="chatbot-bubble glow-effect" @click="toggleChat"
      :class="{ 'pulse-animation': !isOpen && messages.length === 1 }">
      <i v-if="!isOpen" class="chat-icon">💬</i>
      <i v-else class="close-icon">❌</i>
    </button>

    <!-- Chat Window -->
    <transition name="slide-fade">
      <div v-show="isOpen" class="chatbot-window">
        <!-- Header -->
        <div class="chat-header">
          <div class="header-info">
            <div class="avatar-wrap">
              <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Mia&backgroundColor=ffdfbf"
                alt="Nhân viên tư vấn" class="avatar" />
              <span class="status-dot"></span>
            </div>
            <div class="title-wrap">
              <h4 class="title">NetGen Laptop</h4>
              <p class="subtitle">Chuyên gia tư vấn Laptop</p>
            </div>
          </div>
        </div>

        <!-- Body -->
        <div class="chat-body" ref="chatBody">
          <div v-for="(msg, index) in messages" :key="index" class="message-wrapper"
            :class="msg.role === 'user' ? 'message-right' : 'message-left'">
            <div v-if="msg.role === 'bot'" class="bot-avatar-small">
              <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Mia&backgroundColor=ffdfbf" alt="Bot" />
            </div>

            <div class="message-bubble" :class="msg.role">
              <span v-html="formatMessage(msg.content)"></span>

              <!-- Danh sách sản phẩm nếu có -->
              <div v-if="msg.products && msg.products.length" class="chatbot-products">
                <div v-for="prod in msg.products" :key="prod.id_sanpham" class="bot-product-card"
                  @click="goToProduct(prod.id_sanpham)">
                  <img :src="getProductImage(prod)" :alt="prod.tenSP" class="bot-product-img" />
                  <div class="bot-product-info">
                    <div class="bot-product-name">{{ prod.tenSP }}</div>
                    <div class="bot-product-price">{{ getPrice(prod) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="isLoading" class="message-wrapper message-left">
            <div class="bot-avatar-small">
              <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Mia&backgroundColor=ffdfbf" alt="Bot" />
            </div>
            <div class="message-bubble bot typing-indicator">
              <span></span><span></span><span></span>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="chat-footer">
          <form @submit.prevent="sendMessage" class="input-form">
            <input type="text" v-model="newMessage" placeholder="Hỏi bé ngay (ví dụ: tư vấn máy sinh viên)..."
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
import { ref, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const isOpen = ref(false);
const isLoading = ref(false);
const newMessage = ref('');
const chatBody = ref(null);
const router = useRouter();

const goToProduct = (id) => {
  if (!id) return;
  router.push(`/products/${id}`);
};

const formatPrice = (price) => {
  if (!price) return '0 ₫';
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
};

const getPrice = (prod) => {
  const bt = prod.bien_thes || prod.bienThes;
  if (bt && bt.length > 0) return formatPrice(bt[0].gia);
  return 'Liên hệ';
};

const getProductImage = (prod) => {
  if (!prod.hinhanh) return 'https://via.placeholder.com/150';
  if (prod.hinhanh.startsWith('http')) return prod.hinhanh;
  return `http://localhost:8000/storage/${prod.hinhanh}`;
};

const messages = ref([
  {
    role: 'bot',
    content: "Dạ em chào khách yêu ạ! Khách yêu đang tìm kiếm chiếc laptop chân ái nào vậy ạ? Anh/Chị cứ nói nhu cầu (văn phòng, sinh viên hay gaming) kèm tầm giá để em tư vấn nha!"
  }
]);

const toggleChat = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    scrollToBottom();
  }
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
    const response = await axios.post('http://localhost:8000/api/chat', {
      message: userText
    }, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      }
    });

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
    console.log('Status:', error?.response?.status);
    console.log('Data:', error?.response?.data);

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
</script>

<style scoped>
.chatbot-container {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 9999;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* ===== BUBBLE BUTTON ===== */
.chatbot-bubble {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1a2744, #2563eb);
  color: white;
  border: none;
  box-shadow: 0 4px 15px rgba(37, 99, 235, 0.45);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.chatbot-bubble:hover {
  transform: scale(1.1);
}

.glow-effect {
  position: relative;
}

.pulse-animation::before {
  content: '';
  position: absolute;
  top: -5px;
  left: -5px;
  right: -5px;
  bottom: -5px;
  border-radius: 50%;
  background: rgba(37, 99, 235, 0.35);
  z-index: -1;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(0.95);
    opacity: 0.8;
  }

  100% {
    transform: scale(1.4);
    opacity: 0;
  }
}

/* ===== CHAT WINDOW ===== */
.chatbot-window {
  position: absolute;
  bottom: 80px;
  right: 0;
  width: 350px;
  height: 500px;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(26, 39, 68, 0.18);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(37, 99, 235, 0.08);
  transform-origin: bottom right;
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
  background: white;
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
  background: white;
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
  background: white;
  color: #1e293b;
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
  background: #f8fafc;
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
  background: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
}

.bot-product-img {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 8px;
  background: white;
  border: 1px solid #e2e8f0;
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
  color: #1e293b;
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
  background: white;
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
  background: white;
  border-color: #2563eb;
  box-shadow: 0 2px 12px rgba(37, 99, 235, 0.15);
}

.input-form input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 14px;
  outline: none;
  color: #1e293b;
}

.input-form input::placeholder {
  color: #94a3b8;
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