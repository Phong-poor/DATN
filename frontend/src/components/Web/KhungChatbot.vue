<template>
  <div class="chatbot-container" ref="chatWidgetRef">
    <!-- Bubble Button hidden, handled by FloatingContactMenu -->
    <div v-if="false" class="chatbot-bubble glow-effect" role="button" tabindex="0" @click="toggleChat"
      @keydown.enter.prevent="toggleChat" @keydown.space.prevent="toggleChat"
      :class="{ 'pulse-animation': !isOpen && !isAdminOpen && messages.length === 1 }">
      <i v-if="!isOpen && !isAdminOpen" class="chat-icon">💬</i>
      <i v-else class="close-icon">❌</i>

    </div>

    <transition name="slide-fade">
      <div v-show="isOpen" class="chatbot-window">
        <!-- 1. VIEW: CHAT -->
        <template v-if="chatbotView === 'chat'">
          <!-- Header -->
          <div class="chat-header">
            <div class="header-info">
              <div class="avatar-wrap">
                <img src="/support_avatar.png" alt="Nhân viên tư vấn" class="avatar" />
                <span class="status-dot"></span>
              </div>
              <div class="title-wrap">
                <h4 class="title">Tư vấn trực tuyến</h4>
                <p class="subtitle">Mia - Chuyên viên hỗ trợ VinaTech</p>
              </div>
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
                      <div class="bot-product-meta-row">
                        <div class="bot-product-price">{{ formatPrice(prod.gia) }}</div>
                        <button class="bot-product-buy-btn" @click.stop="chotDon(prod)" type="button">
                          Chốt đơn
                        </button>
                      </div>
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
              
              <!-- Emoji Picker -->
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

              <button type="submit" :disabled="!newMessage.trim() || isLoading" class="send-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;">
                  <path d="m22 2-7 20-4-9-9-4Z"/>
                  <path d="M22 2 11 13"/>
                </svg>
              </button>
            </form>
          </div>
        </template>

        <!-- 2. VIEW: CHECKOUT -->
        <template v-else-if="chatbotView === 'checkout'">
          <!-- Header -->
          <div class="chat-header">
            <div class="header-info">
              <button class="chat-back-navigation-btn" @click="chatbotView = 'chat'" title="Quay lại" type="button">
                ←
              </button>
              <div class="title-wrap" style="margin-left: 8px;">
                <h4 class="title">Chốt đơn nhanh</h4>
                <p class="subtitle">Nhập thông tin giao hàng</p>
              </div>
              <button class="chat-close-btn" type="button" @click="isOpen = false" aria-label="Đóng chat">✕</button>
            </div>
          </div>

          <!-- Body Form -->
          <div class="chat-body form-view">
            <div class="checkout-product-mini" v-if="selectedProduct">
              <img :src="getProductImage(selectedProduct)" class="mini-img" />
              <div class="mini-info">
                <div class="mini-name">{{ selectedProduct.ten_bienthe || getDisplayName(selectedProduct) }}</div>
                <div class="mini-price">{{ formatPrice(selectedProduct.gia) }}</div>
              </div>
            </div>

            <form @submit.prevent="chatbotView = 'confirm'" class="chatbot-checkout-form">
              <!-- Chọn địa chỉ đã lưu -->
              <div class="input-group" v-if="userAddresses.length > 0">
                <label>Địa chỉ đã lưu</label>
                <select v-model="selectedAddressId" @change="onAddressChange" class="saved-address-select">
                  <option v-for="addr in userAddresses" :key="addr.id_diachi" :value="addr.id_diachi">
                    {{ addr.ten_nguoinhan }} - {{ addr.sdt_nguoinhan }} ({{ addr.dia_chi_day_du }})
                  </option>
                  <option :value="null">-- Nhập địa chỉ mới --</option>
                </select>
              </div>

              <div class="input-group">
                <label>Họ và tên người nhận</label>
                <input v-model="checkoutForm.name" placeholder="Nhập họ và tên" required />
              </div>
              <div class="input-group">
                <label>Số điện thoại</label>
                <input v-model="checkoutForm.phone" placeholder="Nhập số điện thoại" type="tel" maxlength="10" required />
              </div>
              <div class="input-group">
                <label>Email nhận hóa đơn</label>
                <input v-model="checkoutForm.email" placeholder="Nhập email nhận hóa đơn" type="email" required />
              </div>
              <div class="input-group">
                <label>Địa chỉ nhận hàng</label>
                <textarea v-model="checkoutForm.address" placeholder="Số nhà, tên đường, phường, quận..." required :readonly="!!selectedAddressId"></textarea>
              </div>
              <div class="input-group">
                <label>Phương thức thanh toán</label>
                <select v-model="checkoutForm.paymentMethod">
                  <option value="cod">COD (Thanh toán khi nhận hàng)</option>
                  <option value="bank">Chuyển khoản (Quét mã VietQR)</option>
                  <option value="vnpay">Cổng VNPay (Thanh toán online)</option>
                  <option value="momo">Ví MoMo (Thanh toán online)</option>
                </select>
                <!-- Smart payment tips -->
                <span class="payment-tip-badge" :class="checkoutForm.paymentMethod">
                  {{ checkoutForm.paymentMethod === 'cod' ? '📦 Giao hàng tận nhà, thanh toán tiền mặt khi nhận hàng.' 
                     : (checkoutForm.paymentMethod === 'bank' ? '🏦 Nhận mã QR thanh toán nhanh qua ứng dụng Ngân hàng.' 
                     : '⚡ Khuyên dùng: Thanh toán trực tuyến an toàn, nhận hàng nhanh hơn!') }}
                </span>
              </div>
              <button type="submit" class="submit-btn green-glow">Tiếp tục đặt hàng</button>
            </form>
          </div>
        </template>

        <!-- 3. VIEW: CONFIRM -->
        <template v-else-if="chatbotView === 'confirm'">
          <!-- Header -->
          <div class="chat-header">
            <div class="header-info">
              <button class="chat-back-navigation-btn" @click="chatbotView = 'checkout'" title="Quay lại" type="button">
                ←
              </button>
              <div class="title-wrap" style="margin-left: 8px;">
                <h4 class="title">Xác nhận đơn hàng</h4>
                <p class="subtitle">Kiểm tra thông tin trước khi đặt</p>
              </div>
              <button class="chat-close-btn" type="button" @click="isOpen = false" aria-label="Đóng chat">✕</button>
            </div>
          </div>

          <!-- Body Confirm -->
          <div class="chat-body confirm-view">
            <div class="confirm-box">
              <h5 class="box-title">Sản phẩm đã chọn</h5>
              <div class="confirm-product-layout" v-if="selectedProduct">
                <img :src="getProductImage(selectedProduct)" class="confirm-product-img" />
                <div class="confirm-product-info">
                  <span class="prod-name">{{ selectedProduct.ten_bienthe || getDisplayName(selectedProduct) }}</span>
                  <b class="prod-price">{{ formatPrice(selectedProduct.gia) }}</b>
                </div>
              </div>
            </div>

            <div class="confirm-box shadow-box">
              <h5 class="box-title">Thông tin giao hàng</h5>
              <p><strong>Người nhận:</strong> {{ checkoutForm.name }}</p>
              <p><strong>Số điện thoại:</strong> {{ checkoutForm.phone }}</p>
              <p><strong>Email:</strong> {{ checkoutForm.email }}</p>
              <p><strong>Địa chỉ:</strong> {{ checkoutForm.address }}</p>
              <p><strong>Thanh toán:</strong> {{ checkoutForm.paymentMethod === 'cod' ? 'Thanh toán khi nhận hàng (COD)' : (checkoutForm.paymentMethod === 'bank' ? 'Chuyển khoản qua VietQR' : (checkoutForm.paymentMethod === 'vnpay' ? 'Thanh toán trực tuyến VNPay' : 'Thanh toán trực tuyến MoMo')) }}</p>
            </div>

            <div class="confirm-total">
              <span>Tổng thanh toán:</span>
              <b v-if="selectedProduct">{{ formatPrice(selectedProduct.gia) }}</b>
            </div>

            <div class="confirm-actions">
              <button type="button" class="cancel-btn" @click="chatbotView = 'checkout'">Quay lại</button>
              <button type="button" class="confirm-btn" @click="submitDirectOrder" :disabled="isLoading">
                {{ isLoading ? 'Đang xử lý...' : 'Xác nhận mua ngay' }}
              </button>
            </div>
          </div>
        </template>

        <!-- 4. VIEW: BILL/BUILD RECEIPT -->
        <template v-else-if="chatbotView === 'bill'">
          <!-- Header -->
          <div class="chat-header">
            <div class="header-info">
              <div class="title-wrap">
                <h4 class="title">Hóa đơn đơn hàng</h4>
                <p class="subtitle" style="color: #4ade80;">Đặt hàng thành công! 🎉</p>
              </div>
              <button class="chat-close-btn" type="button" @click="resetChatbotView" aria-label="Đóng chat">✕</button>
            </div>
          </div>

          <!-- Body Invoice -->
          <div class="chat-body bill-view">
            <div class="bill-success-icon">✔️</div>
            <h4 class="bill-title">Hóa đơn điện tử</h4>
            
            <div class="bill-details" v-if="createdOrder">
              <p><strong>Mã đơn hàng:</strong> <code class="order-code-text">{{ createdOrder.ma_dathang || createdOrder.id_dathang }}</code></p>
              <p><strong>Trạng thái:</strong> <span class="badge-status-waiting">{{ createdOrder.PTTT !== 'COD' && !payUrl ? 'Đang chờ xử lý' : (createdOrder.PTTT !== 'COD' ? 'Chờ thanh toán' : 'Chờ giao hàng') }}</span></p>
              <p><strong>Khách hàng:</strong> {{ createdOrder.ten_nguoinhan || checkoutForm.name }}</p>
              <p><strong>Số điện thoại:</strong> {{ createdOrder.sdt_nguoinhan || checkoutForm.phone }}</p>
              <p><strong>Email:</strong> {{ checkoutForm.email }}</p>
              <p class="address-paragraph"><strong>Giao tới:</strong> {{ createdOrder.diachi_giaohang || checkoutForm.address }}</p>
              
              <div class="bill-divider"></div>
              
              <div class="bill-product-layout" v-if="selectedProduct">
                <img :src="getProductImage(selectedProduct)" class="bill-product-img" />
                <div class="bill-product-info">
                  <span class="prod-name">{{ selectedProduct.ten_bienthe || getDisplayName(selectedProduct) }} (x1)</span>
                  <b class="prod-price">{{ formatPrice(selectedProduct.gia) }}</b>
                </div>
              </div>
              
              <div class="bill-divider"></div>
              
              <div class="bill-total">
                <span>Tổng cộng:</span>
                <b>{{ formatPrice(createdOrder.tong_tien || selectedProduct.gia) }}</b>
              </div>
            </div>

            <!-- VietQR block -->
            <div v-if="createdOrder && (createdOrder.PTTT === 'Chuyển khoản' || createdOrder.PTTT === 'bank')" class="vietqr-payment-box">
              <div class="vietqr-heading">QUÉT MÃ ĐỂ THANH TOÁN CHUYỂN KHOẢN</div>
              <div class="vietqr-image-wrapper">
                <img :src="getVietQrUrl(createdOrder)" class="vietqr-qrcode-img" alt="VietQR VinaTech" />
              </div>
              <div class="vietqr-bank-details">
                <div class="vietqr-row">
                  <span class="vqr-label">Ngân hàng:</span>
                  <span class="vqr-val">MB Bank (Quân Đội)</span>
                </div>
                <div class="vietqr-row clickable-copy" @click="copyText('0900123456789', 'Đã sao chép số tài khoản MB Bank!')" title="Click để sao chép">
                  <span class="vqr-label">Số tài khoản:</span>
                  <span class="vqr-val font-mono">0900123456789 <i class="copy-icon">📋</i></span>
                </div>
                <div class="vietqr-row">
                  <span class="vqr-label">Chủ tài khoản:</span>
                  <span class="vqr-val">CONG TY VINATECH</span>
                </div>
                <div class="vietqr-row clickable-copy" @click="copyText('VINATECH ' + (createdOrder.ma_dathang || createdOrder.id_dathang), 'Đã sao chép nội dung chuyển khoản!')" title="Click để sao chép">
                  <span class="vqr-label">Nội dung CK:</span>
                  <span class="vqr-val font-mono highlight-memo">VINATECH {{ createdOrder.ma_dathang || createdOrder.id_dathang }} <i class="copy-icon">📋</i></span>
                </div>
              </div>
              <div class="vietqr-hint">💡 Nhấp vào Số tài khoản hoặc Nội dung CK để sao chép nhanh!</div>
            </div>

            <div class="bill-actions">
              <a v-if="payUrl" :href="payUrl" target="_blank" class="pay-now-btn">Thanh toán trực tuyến ngay</a>
              <button type="button" class="done-btn" @click="resetChatbotView">Quay lại trò chuyện</button>
            </div>
            
            <p class="bill-note">Hóa đơn chi tiết đã được gửi tới email của bạn. Vui lòng kiểm tra hộp thư.</p>
          </div>
        </template>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { productImageUrl, storageUrl } from '@/services/urls';
import { getToken, getUser } from '@/services/auth';
import { stopChatTitleNotice } from '@/utils/chatMessage';
import swal from '@/services/swal';

const chatWidgetRef = ref(null);
const showEmojiPicker = ref(false);

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
];

const toggleEmojiPicker = () => {
  showEmojiPicker.value = !showEmojiPicker.value;
};

const addEmoji = (emoji) => {
  newMessage.value += emoji;
  showEmojiPicker.value = false;
};

const handleClickOutside = (e) => {
  if (chatWidgetRef.value && !chatWidgetRef.value.contains(e.target)) {
    showEmojiPicker.value = false;
  }
};

const isOpen = ref(false);
const isLoading = ref(false);
const currentChatMode = ref('ai'); // 'ai' or 'admin'
const isAdminOpen = ref(false);
const newMessage = ref('');
const chatBody = ref(null);
const router = useRouter();

const chatbotView = ref('chat');
const selectedProduct = ref(null);
const createdOrder = ref(null);
const payUrl = ref('');
const checkoutForm = ref({
  name: '',
  phone: '',
  email: '',
  address: '',
  paymentMethod: 'cod'
});

const userAddresses = ref([]);
const selectedAddressId = ref(null);

const loadUserAddresses = async () => {
  try {
    const res = await api.get('/user/dia-chi');
    userAddresses.value = res.data.data || [];
    
    // Auto select default or first address
    const defaultAddress = userAddresses.value.find(addr => addr.mac_dinh) || userAddresses.value[0];
    if (defaultAddress) {
      selectedAddressId.value = defaultAddress.id_diachi;
      checkoutForm.value.address = defaultAddress.dia_chi_day_du;
      checkoutForm.value.name = defaultAddress.ten_nguoinhan || checkoutForm.value.name;
      checkoutForm.value.phone = defaultAddress.sdt_nguoinhan || checkoutForm.value.phone;
    } else {
      selectedAddressId.value = null;
    }
  } catch (error) {
    console.error('Lỗi tải danh sách địa chỉ trong chatbot:', error);
  }
};

const onAddressChange = () => {
  if (selectedAddressId.value) {
    const addr = userAddresses.value.find(a => a.id_diachi === selectedAddressId.value);
    if (addr) {
      checkoutForm.value.address = addr.dia_chi_day_du;
      checkoutForm.value.name = addr.ten_nguoinhan || checkoutForm.value.name;
      checkoutForm.value.phone = addr.sdt_nguoinhan || checkoutForm.value.phone;
    }
  } else {
    checkoutForm.value.address = '';
  }
};

const resetChatbotView = () => {
  chatbotView.value = 'chat';
  selectedProduct.value = null;
  createdOrder.value = null;
  payUrl.value = '';
};

const goToProduct = (bt) => {
  if (!bt) return;
  const spId = bt.id_sanpham || (bt.san_pham?.id_sanpham) || (bt.sanPham?.id_sanpham);
  if (!spId) return;

  router.push(`/san-pham/${spId}?variant=${bt.id_bienthe}`);
};

const chotDon = async (bt) => {
  if (!bt) return;

  const token = getToken();
  if (!token) {
    swal.info('Yêu cầu đăng nhập', 'Vui lòng đăng nhập để tiến hành thanh toán mua hàng!');
    localStorage.setItem('pendingChatbotItem', JSON.stringify(bt));
    router.push({ path: '/dang-nhap', query: { redirect: router.currentRoute.value.fullPath } });
    return;
  }

  selectedProduct.value = bt;
  
  // Pre-fill user data
  const user = getUser();
  if (user) {
    checkoutForm.value.name = user.name || user.ten || '';
    checkoutForm.value.email = user.email || '';
    checkoutForm.value.phone = user.phone || user.sdt || '';
  }
  
  loadUserAddresses();
  chatbotView.value = 'checkout';
};

const submitDirectOrder = async () => {
  if (!selectedProduct.value) return;

  // Validate phone number format (0 followed by 9 digits)
  const phoneStr = String(checkoutForm.value.phone || '').replace(/\D/g, '');
  if (!/^0\d{9}$/.test(phoneStr)) {
    swal.warning('Thiếu thông tin', 'Vui lòng nhập số điện thoại 10 số và bắt đầu bằng số 0.');
    return;
  }

  isLoading.value = true;
  let addedCartItem = null;

  try {
    // 1. Thêm sản phẩm tạm thời vào giỏ để đáp ứng điều kiện kiểm tra của Backend Controller
    const addRes = await api.post('/gio-hang/them', {
      id_bienthe: selectedProduct.value.id_bienthe,
      soluong: 1
    });
    if (addRes.data && addRes.data.item) {
      addedCartItem = addRes.data.item;
    }
  } catch (addErr) {
    console.error('Lỗi chuẩn bị giỏ hàng tạm thời:', addErr);
    swal.error('Lỗi đặt hàng', addErr.response?.data?.message || 'Không thể chuẩn bị giỏ hàng tạm thời.');
    isLoading.value = false;
    return;
  }

  try {
    // 2. Tiến hành gọi API thanh toán/đặt hàng
    const response = await api.post('/checkout', {
        id_diachi: selectedAddressId.value || undefined,
        diachi: checkoutForm.value.address,
        name: checkoutForm.value.name,
        phone: phoneStr,
        PTTT: checkoutForm.value.paymentMethod === 'vnpay' ? 'VNPAY' : (checkoutForm.value.paymentMethod === 'momo' ? 'MOMO' : (checkoutForm.value.paymentMethod === 'bank' ? 'Chuyển khoản' : 'COD')),
        selected_variants: [selectedProduct.value.id_bienthe]
    });

    if (response.data.success) {
        createdOrder.value = response.data.order;
        payUrl.value = response.data.payUrl || '';
        chatbotView.value = 'bill';
        
        window.dispatchEvent(new Event('cart-updated'));
        
        // Kích hoạt gửi email hóa đơn
        if (createdOrder.value?.id_dathang) {
          api.post(`/orders/send-email/${createdOrder.value.id_dathang}`).catch(e => {
             console.error('Lỗi gửi email bill từ chatbot:', e);
          });
        }
    }
  } catch (error) {
    console.error('Lỗi đặt hàng trực tiếp:', error);
    
    // 3. Nếu xảy ra lỗi khi thanh toán, dọn dẹp (xóa) sản phẩm tạm đã thêm để trả lại tồn kho và giữ giỏ hàng sạch
    if (addedCartItem && addedCartItem.id_giohang) {
      try {
        await api.delete(`/gio-hang/xoa/${addedCartItem.id_giohang}`);
      } catch (cleanupErr) {
        console.error('Lỗi dọn dẹp sản phẩm giỏ hàng tạm:', cleanupErr);
      }
    }
    
    swal.error('Lỗi đặt hàng', error.response?.data?.message || 'Có lỗi xảy ra khi xử lý đơn hàng.');
  } finally {
    isLoading.value = false;
  }
};

const getVietQrUrl = (order) => {
  if (!order) return '';
  const bankId = 'MB'; // MB Bank
  const accountNo = '0900123456789';
  const template = 'print'; // print or compact
  const amount = order.tong_tien || selectedProduct.value?.gia || 0;
  const memo = `VINATECH ${order.ma_dathang || order.id_dathang}`;
  const accountName = 'CONG TY VINATECH';
  
  return `https://img.vietqr.io/image/${bankId}-${accountNo}-${template}.png?amount=${amount}&addInfo=${encodeURIComponent(memo)}&accountName=${encodeURIComponent(accountName)}`;
};

const copyText = (text, successMsg) => {
  navigator.clipboard.writeText(text).then(() => {
    swal.toast(successMsg || 'Đã sao chép vào bộ nhớ tạm!');
  }).catch(err => {
    console.error('Lỗi sao chép:', err);
  });
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
    router.push('/dang-nhap')
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
  document.addEventListener('mousedown', handleClickOutside);

  // Khôi phục phiên chốt đơn của chatbot sau khi đăng nhập thành công
  const pending = localStorage.getItem('pendingChatbotItem');
  if (pending && getToken()) {
    try {
      const bt = JSON.parse(pending);
      localStorage.removeItem('pendingChatbotItem');
      selectedProduct.value = bt;
      
      const user = getUser();
      if (user) {
        checkoutForm.value.name = user.name || user.ten || '';
        checkoutForm.value.email = user.email || '';
        checkoutForm.value.phone = user.phone || user.sdt || '';
      }
      
      loadUserAddresses();
      isOpen.value = true;
      chatbotView.value = 'checkout';
      scrollToBottom();
    } catch (e) {
      console.error('Lỗi khôi phục chatbot item:', e);
    }
  }
});

onUnmounted(() => {
  window.removeEventListener('open-chatbot', handleOpenChatEvent);
  window.removeEventListener('open-admin-chat', handleOpenAdminChatEvent);
  window.removeEventListener('admin-chat-state', handleAdminStateEvent);
  document.removeEventListener('mousedown', handleClickOutside);
  stopChatTitleNotice();
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

.bot-product-meta-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 6px;
  gap: 8px;
}

.bot-product-buy-btn {
  background: linear-gradient(135deg, #22c55e, #15803d);
  color: white !important;
  border: none;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.bot-product-buy-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 0 8px rgba(34, 197, 94, 0.4);
}

.bot-product-buy-btn:active {
  transform: scale(0.95);
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
  color: #1e293b !important;
}

.input-form:focus-within input {
  color: #ffffff !important;
}

.input-form input::placeholder {
  color: #64748b !important;
}

.input-form:focus-within input::placeholder {
  color: #94a3b8 !important;
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

/* ===== EMOJI PICKER ===== */
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
  padding: 4px;
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
  bottom: 42px;
  right: -6px;
  width: 240px;
  height: 180px;
  background: #111f35;
  border: 1px solid rgba(37, 99, 235, 0.2);
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
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
  background: #2563eb;
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
  background: rgba(37, 99, 235, 0.15);
}

/* ===== DIRECT CHECKOUT WIDGET STYLES ===== */
.chat-back-navigation-btn {
  background: transparent;
  border: none;
  color: white;
  font-size: 20px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 8px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-back-navigation-btn:hover {
  background: rgba(255, 255, 255, 0.15);
}

.form-view, .confirm-view, .bill-view {
  background: #111f35 !important;
  color: #e2e8f0;
}

.checkout-product-mini {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #0d1b2e;
  border: 1px solid rgba(37, 99, 235, 0.12);
  border-radius: 12px;
  padding: 8px 12px;
  margin-bottom: 12px;
  text-align: left;
}

.checkout-product-mini .mini-img {
  width: 44px;
  height: 44px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid rgba(255,255,255,0.06);
}

.checkout-product-mini .mini-info {
  display: flex;
  flex-direction: column;
}

.checkout-product-mini .mini-name {
  font-size: 12.5px;
  font-weight: 600;
  color: #f8fafc;
  line-height: 1.3;
}

.checkout-product-mini .mini-price {
  font-size: 12.5px;
  font-weight: 700;
  color: #ef4444;
  margin-top: 2px;
}

.chatbot-checkout-form {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.chatbot-checkout-form .input-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  text-align: left;
}

.chatbot-checkout-form .input-group label {
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.chatbot-checkout-form input,
.chatbot-checkout-form textarea,
.chatbot-checkout-form select {
  background: #0d1b2e !important;
  color: #ffffff !important;
  border: 1px solid rgba(37, 99, 235, 0.2) !important;
  border-radius: 8px !important;
  padding: 8px 12px !important;
  width: 100% !important;
  font-size: 13px !important;
  margin-top: 4px !important;
  margin-bottom: 0 !important;
  outline: none !important;
  box-sizing: border-box !important;
}

.chatbot-checkout-form select {
  appearance: select !important;
}

.chatbot-checkout-form input:focus,
.chatbot-checkout-form textarea:focus,
.chatbot-checkout-form select:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2) !important;
}

.chatbot-checkout-form textarea {
  height: 60px !important;
  resize: none !important;
}

.submit-btn {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white !important;
  border: none;
  border-radius: 20px;
  padding: 10px;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  margin-top: 10px;
  transition: all 0.2s;
}

.submit-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

/* CONFIRM VIEW */
.confirm-box {
  background: #0d1b2e;
  border: 1px solid rgba(37, 99, 235, 0.1);
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 12px;
  text-align: left;
}

.confirm-box .box-title {
  margin: 0 0 8px;
  font-size: 12px;
  font-weight: 800;
  color: #38bdf8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.confirm-box p {
  margin: 4px 0;
  font-size: 12.5px;
  color: #cbd5e1 !important;
  line-height: 1.4;
}

.confirm-product {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.confirm-product .prod-name {
  font-size: 12.5px;
  font-weight: 600;
  color: #fff;
}

.confirm-product .prod-price {
  font-size: 13px;
  color: #ef4444;
}

.confirm-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 14px;
  background: rgba(37, 99, 235, 0.1);
  border-radius: 8px;
  margin-bottom: 16px;
}

.confirm-total span {
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
}

.confirm-total b {
  font-size: 16px;
  color: #22c55e;
  font-weight: 800;
}

.confirm-actions {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 10px;
}

.confirm-actions .cancel-btn {
  background: #334155;
  color: white !important;
  border: none;
  border-radius: 20px;
  padding: 10px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.confirm-actions .confirm-btn {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white !important;
  border: none;
  border-radius: 20px;
  padding: 10px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.confirm-actions .confirm-btn:hover {
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.confirm-actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* BILL / INVOICE VIEW */
.bill-view {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px !important;
  overflow-y: auto;
}

.bill-success-icon {
  width: 48px;
  height: 48px;
  background: rgba(34, 197, 94, 0.15);
  color: #22c55e;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 22px;
  margin-bottom: 8px;
}

.bill-title {
  font-size: 16px;
  font-weight: 800;
  color: #fff;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.bill-details {
  background: #0d1b2e;
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 12px;
  width: 100%;
  padding: 12px 14px;
  text-align: left;
  box-sizing: border-box;
}

.bill-details p {
  margin: 4px 0;
  font-size: 12px;
  color: #cbd5e1 !important;
  line-height: 1.4;
}

.order-code-text {
  background: rgba(37, 99, 235, 0.15);
  color: #38bdf8;
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
}

.badge-status-waiting {
  background: rgba(234, 179, 8, 0.15);
  color: #facc15;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
}

.bill-divider {
  border-top: 1px dashed rgba(255, 255, 255, 0.1);
  margin: 10px 0;
}

.bill-item {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #fff;
}

.bill-total {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  font-weight: 700;
}

.bill-total b {
  color: #22c55e;
}

.bill-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  margin-top: 16px;
}

.pay-now-btn {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white !important;
  border: none;
  border-radius: 20px;
  padding: 10px;
  font-size: 13px;
  font-weight: 700;
  text-align: center;
  text-decoration: none;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.pay-now-btn:hover {
  transform: translateY(-1px);
}

.done-btn {
  background: #334155;
  color: white !important;
  border: none;
  border-radius: 20px;
  padding: 10px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.bill-note {
  font-size: 10.5px;
  color: #64748b !important;
  margin-top: 10px;
  text-align: center;
}

.address-paragraph {
  word-break: break-all;
}

.confirm-product-layout,
.bill-product-layout {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.confirm-product-img,
.bill-product-img {
  width: 44px;
  height: 44px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: #111f35;
}

.confirm-product-info,
.bill-product-info {
  display: flex;
  flex-direction: column;
  text-align: left;
  flex: 1;
}

.confirm-product-info .prod-name,
.bill-product-info .prod-name {
  font-size: 12.5px;
  font-weight: 600;
  color: #ffffff;
  line-height: 1.3;
}

.confirm-product-info .prod-price,
.bill-product-info .prod-price {
  font-size: 12.5px;
  color: #ef4444;
  margin-top: 2px;
}

.saved-address-select {
  appearance: select !important;
  background: #0d1b2e !important;
  color: #ffffff !important;
  border: 1px solid rgba(37, 99, 235, 0.2) !important;
  border-radius: 8px !important;
  padding: 8px 12px !important;
  width: 100% !important;
  font-size: 13px !important;
  outline: none !important;
}

.payment-tip-badge {
  display: block;
  font-size: 11px;
  margin-top: 6px;
  line-height: 1.4;
  padding: 4px 8px;
  border-radius: 6px;
  text-align: left;
}

.payment-tip-badge.cod {
  background: rgba(148, 163, 184, 0.1);
  color: #94a3b8;
}

.payment-tip-badge.vnpay,
.payment-tip-badge.momo {
  background: rgba(34, 197, 94, 0.1);
  color: #4ade80;
}

.payment-tip-badge.bank {
  background: rgba(59, 130, 246, 0.1);
  color: #60a5fa;
}

/* ===== VIETQR STYLING ===== */
.vietqr-payment-box {
  background: #0d1b2e;
  border: 1px solid rgba(34, 197, 94, 0.2);
  border-radius: 12px;
  width: 100%;
  padding: 14px;
  text-align: center;
  box-sizing: border-box;
  margin-top: 14px;
}

.vietqr-heading {
  font-size: 11px;
  font-weight: 800;
  color: #4ade80;
  margin-bottom: 10px;
  letter-spacing: 0.5px;
}

.vietqr-image-wrapper {
  background: #ffffff;
  padding: 8px;
  border-radius: 8px;
  display: inline-block;
  margin-bottom: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.vietqr-qrcode-img {
  width: 150px;
  height: 150px;
  display: block;
}

.vietqr-bank-details {
  text-align: left;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 8px;
  padding: 8px 10px;
  margin-bottom: 8px;
}

.vietqr-row {
  display: flex;
  justify-content: space-between;
  font-size: 11.5px;
  margin: 4px 0;
  color: #cbd5e1;
}

.vietqr-row .vqr-label {
  color: #94a3b8;
}

.vietqr-row .vqr-val {
  color: #ffffff;
  font-weight: 600;
}

.clickable-copy {
  cursor: pointer;
  transition: all 0.2s;
  border-radius: 4px;
  padding: 2px 4px;
  margin: 2px -4px;
}

.clickable-copy:hover {
  background: rgba(37, 99, 235, 0.15);
}

.clickable-copy:hover .vqr-val {
  color: #38bdf8;
}

.copy-icon {
  font-style: normal;
  font-size: 10px;
  margin-left: 4px;
}

.highlight-memo {
  color: #facc15 !important;
}

.vietqr-hint {
  font-size: 10px;
  color: #94a3b8;
  margin-top: 6px;
  font-style: italic;
}
</style>
