<template>
  <div class="support-widget">
    <transition name="fade-scale">
      <div v-if="supportOpen" class="support-card">
        <div class="support-header">
          <div class="support-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <div class="support-info">
            <p class="support-title">Nhân viên tư vấn khách hàng</p>
            <p class="support-subtitle">Hỗ trợ nhanh qua WhatsApp</p>
          </div>
        </div>
        <div class="support-items">
          <button type="button" class="support-item" @click="openWhatsApp">
            <span>Chat WhatsApp</span>
            <i style="color: #10b981; display: inline-flex; align-items: center;">
              <svg viewBox="0 0 24 24" fill="currentColor" width="10" height="10">
                <circle cx="12" cy="12" r="8"/>
              </svg>
            </i>
          </button>
          <button type="button" class="support-item" @click="contactPhone">
            <span>Gọi điện</span>
            <i style="display: inline-flex; align-items: center; color: #94a3b8;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
            </i>
          </button>
          <button type="button" class="support-item" @click="openChatbot">
            <span>Trò chuyện trực tuyến</span>
            <i style="display: inline-flex; align-items: center; color: #94a3b8;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
              </svg>
            </i>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const supportOpen = ref(false);
const whatsappNumber = '84901234567';
const whatsappMessage = encodeURIComponent('Xin chào, tôi muốn được hỗ trợ tư vấn laptop.');
const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${whatsappMessage}`;

const toggleSupport = () => {
  supportOpen.value = !supportOpen.value;
  // announce to other components (e.g., chat bubble) that support panel opened/closed
  window.dispatchEvent(new CustomEvent('support-opened', { detail: { open: supportOpen.value } }));
};

const openWhatsApp = () => {
  window.open(whatsappUrl, '_blank');
  window.dispatchEvent(new CustomEvent('support-opened', { detail: { open: false } }));
};

const contactPhone = () => {
  window.location.href = 'tel:1900123456';
  window.dispatchEvent(new CustomEvent('support-opened', { detail: { open: false } }));
};

const openChatbot = () => {
  // dispatch a global event so the ChatbotWidget can open itself
  window.dispatchEvent(new CustomEvent('open-chatbot'));
  supportOpen.value = false;
  window.dispatchEvent(new CustomEvent('support-opened', { detail: { open: false } }));
};

// Listen for external toggle requests (from the chat bubble)
const handleToggleSupportEvent = (e) => {
  if (e?.detail && typeof e.detail.open === 'boolean') {
    supportOpen.value = e.detail.open;
  } else {
    supportOpen.value = !supportOpen.value;
  }
};

onMounted(() => {
  window.addEventListener('toggle-support', handleToggleSupportEvent);
});

onUnmounted(() => {
  window.removeEventListener('toggle-support', handleToggleSupportEvent);
});
</script>

<style scoped>

.support-widget {
  position: fixed;
  right: 30px;
  bottom: 110px;
  z-index: 9999;
  width: 220px;
  max-width: calc(100vw - 40px);
}

.support-toggle-btn {
  width: 100%;
  border: none;
  border-radius: 24px;
  padding: 14px 18px;
  background: linear-gradient(135deg, #2563eb, #1e3a8a);
  color: white;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 14px 30px rgba(37, 99, 235, 0.24);
}

.support-arrow {
  transition: transform 0.2s ease;
}

.support-arrow.open {
  transform: rotate(180deg);
}

.support-card {
  background: linear-gradient(180deg, #1e3a8a 0%, #2563eb 100%);
  border-radius: 24px;
  color: white;
  padding: 18px;
  box-shadow: 0 14px 30px rgba(37, 99, 235, 0.2);
  overflow: hidden;
}

.support-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 12px;
}

.support-icon {
  width: 46px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.18);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
}

.support-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.support-title {
  margin: 0;
.support-title {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
}
.support-subtitle {
  margin: 0;
  font-size: 11px;
  opacity: 0.88;
}
  font-weight: 700;
}

.support-subtitle {
  margin: 0;
  font-size: 12px;
  opacity: 0.88;
}

.support-details {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.support-agent-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 13px;
}

.agent-label {
  opacity: 0.82;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.support-whatsapp-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.24);
  background: rgba(16, 185, 129, 0.16);
  color: white;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.support-whatsapp-btn:hover {
  transform: translateY(-1px);
  background: rgba(16, 185, 129, 0.25);
}

.support-items {
  display: grid;
  gap: 10px;
  margin-top: 8px;
}

.support-item {
  width: 100%;
  padding: 10px 12px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.08);
  color: white;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.support-item:hover {
  background: rgba(255, 255, 255, 0.12);
}

/* animation */
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: all 0.2s ease;
}

.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
