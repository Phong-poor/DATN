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
              :class="msg.role === 'user' ? 'message-right' : 'message-left'"
              :data-message-role="msg.role"
              :data-message-index="index">
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
              <div class="message-bubble bot typing-indicator" aria-live="polite">
                <span class="typing-text">Mia đang trả lời</span>
                <span class="typing-dots" aria-hidden="true"><i></i><i></i><i></i></span>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="chat-footer">
            <div class="quick-suggestions" aria-label="Gợi ý câu hỏi nhanh">
              <button
                v-for="suggestion in quickSuggestions"
                :key="suggestion"
                type="button"
                class="quick-suggestion-chip"
                :disabled="isLoading"
                @click="sendQuickSuggestion(suggestion)"
              >
                {{ suggestion }}
              </button>
            </div>

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
          <div class="chat-header checkout-chat-header">
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
            <div class="shipping-designed-form">
              <div class="checkout-steps">
                <div class="step done">
                  <span>✓</span>
                  <small>CHAT</small>
                </div>
                <div class="step-line active"></div>
                <div class="step active">
                  <span>▣</span>
                  <small>SHIPPING</small>
                </div>
                <div class="step-line"></div>
                <div class="step">
                  <span>◎</span>
                  <small>CONFIRM</small>
                </div>
              </div>

              <div class="checkout-scroll">
                <div class="checkout-helper">
                  <span>☻</span>
                  <p>Great! Let's get your shipping details to finish up the order.</p>
                </div>

                <div class="shipping-card" v-if="checkoutProducts.length">
                  <div class="shipping-products">
                    <button
                      v-for="prod in checkoutProducts"
                      :key="prod.id_bienthe"
                      type="button"
                      class="shipping-product"
                      :class="{ active: selectedProduct?.id_bienthe === prod.id_bienthe }"
                      @click="selectCheckoutProduct(prod)"
                    >
                      <img :src="getProductImage(prod)" alt="product" />
                      <span>
                        <small>SELECTED VARIANT</small>
                        <strong>{{ prod.ten_bienthe || getDisplayName(prod) }}</strong>
                      </span>
                      <b>{{ formatPrice(prod.gia) }}</b>
                      <em>{{ selectedProduct?.id_bienthe === prod.id_bienthe ? 'Đang chọn' : 'Chọn' }}</em>
                    </button>
                  </div>
                </div>

                <form @submit.prevent="goToOrderConfirm" class="shipping-form">
                  <div class="field" v-if="userAddresses.length > 0">
                    <label>Địa chỉ đã lưu</label>
                    <select v-model="selectedAddressId" @change="onAddressChange" class="saved-address-select">
                      <option v-for="addr in userAddresses" :key="addr.id_diachi" :value="addr.id_diachi">
                        {{ addr.ten_nguoinhan }} - {{ addr.sdt_nguoinhan }} ({{ addr.dia_chi_day_du }})
                      </option>
                      <option :value="null">-- Nhập địa chỉ mới --</option>
                    </select>
                  </div>

                  <div class="field">
                    <label>Họ và tên người nhận</label>
                    <input v-model="checkoutForm.name" placeholder="Lê Ngọc Tài" required />
                  </div>
                  <div class="field">
                    <label>Số điện thoại</label>
                    <input v-model="checkoutForm.phone" placeholder="09xx xxx xxx" type="tel" maxlength="10" required />
                  </div>
                  <div class="field">
                    <label>Email nhận hóa đơn</label>
                    <input v-model="checkoutForm.email" placeholder="Email@example.com" type="email" required />
                  </div>
                  <div class="field">
                    <label>Địa chỉ nhận hàng</label>
                    <textarea v-model="checkoutForm.address" placeholder="Số nhà, tên đường..." required :readonly="!!selectedAddressId"></textarea>
                  </div>
                  <div class="field">
                    <label>Phương thức thanh toán</label>
                    <select v-model="checkoutForm.paymentMethod">
                      <option value="cod">COD (Thanh toán khi nhận hàng)</option>
                      <option value="bank">Chuyển khoản (Quét mã VietQR)</option>
                      <option value="vnpay">Cổng VNPay (Thanh toán online)</option>
                      <option value="momo">Ví MoMo (Thanh toán online)</option>
                    </select>
                  </div>

                  <button type="submit" class="shipping-submit">Tiếp tục đặt hàng <span>→</span></button>
                  <p class="shipping-secure">Thanh toán bảo mật & mã hóa SSL 256-bit</p>
                </form>
              </div>
            </div>

            <div class="deposit-policy">
              <div class="deposit-row">
                <span>Tổng giá trị</span>
                <strong>{{ formatPrice(selectedProductPrice) }}</strong>
              </div>
              <div class="deposit-row highlight">
                <span>Đặt cọc trước 50%</span>
                <strong>{{ formatPrice(depositAmount) }}</strong>
              </div>
              <div class="deposit-row">
                <span>Thanh toán khi nhận hàng</span>
                <strong>{{ formatPrice(remainingAmount) }}</strong>
              </div>
              <label class="deposit-confirm">
                <input type="checkbox" v-model="depositConfirmed" required />
                <span>Tôi đồng ý chuyển trước 50% giá trị đơn hàng, 50% còn lại thanh toán khi nhận hàng.</span>
              </label>
              <button type="button" class="deposit-continue-btn" @click="goToOrderConfirm">Tiếp tục xác nhận</button>
            </div>

            <div class="checkout-product-picker" v-if="checkoutProducts.length">
              <div class="picker-label">Chọn sản phẩm</div>
              <div class="checkout-product-list">
                <button
                  v-for="prod in checkoutProducts"
                  :key="prod.id_bienthe"
                  type="button"
                  class="checkout-product-mini"
                  :class="{ active: selectedProduct?.id_bienthe === prod.id_bienthe }"
                  @click="selectCheckoutProduct(prod)"
                >
                  <img :src="getProductImage(prod)" class="mini-img" />
                  <div class="mini-info">
                    <div class="mini-name">{{ prod.ten_bienthe || getDisplayName(prod) }}</div>
                    <div class="mini-price">{{ formatPrice(prod.gia) }}</div>
                  </div>
                </button>
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

            <div class="confirm-deposit-plan">
              <div>
                <span>Cần chuyển cọc 50%</span>
                <strong>{{ formatPrice(depositAmount) }}</strong>
              </div>
              <div>
                <span>Còn lại khi giao hàng</span>
                <strong>{{ formatPrice(remainingAmount) }}</strong>
              </div>
              <p>Đơn hàng chatbot yêu cầu đặt cọc 50% để tránh bom hàng. Hóa đơn xác nhận sẽ được gửi về email {{ checkoutForm.email }}.</p>
            </div>

            <label class="confirm-info-check">
              <input type="checkbox" v-model="confirmInfoChecked" />
              <span>Tôi xác nhận thông tin nhận hàng, email và số tiền đặt cọc ở trên là chính xác.</span>
            </label>

            <div class="confirm-actions">
              <button type="button" class="cancel-btn" @click="chatbotView = 'checkout'">Quay lại</button>
              <button type="button" class="confirm-btn" @click="submitDirectOrder" :disabled="isLoading || !confirmInfoChecked">
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
                <b>{{ formatPrice(createdOrder.tongtien || createdOrder.tong_tien || selectedProduct.gia) }}</b>
              </div>

              <div class="bill-deposit-plan">
                <div>
                  <span>Cần chuyển cọc 50%</span>
                  <strong>{{ formatPrice(depositAmount) }}</strong>
                </div>
                <div>
                  <span>Còn lại khi giao hàng</span>
                  <strong>{{ formatPrice(remainingAmount) }}</strong>
                </div>
              </div>
            </div>

            <!-- MoMo personal QR block -->
            <div v-if="createdOrder && (createdOrder.PTTT === 'Chuyển khoản' || createdOrder.PTTT === 'bank')" class="vietqr-payment-box" :class="{ expired: paymentQrExpired }">
              <div class="vietqr-heading">QUÉT MÃ MOMO ĐỂ THANH TOÁN</div>
              <div class="payment-expire-timer" :class="{ expired: paymentQrExpired }">
                {{ paymentQrExpired ? 'Mã QR đã hết hạn sau 15 phút' : `Mã QR còn hiệu lực ${paymentQrRemainingText}` }}
              </div>
              <div class="vietqr-image-wrapper">
                <img
                  v-if="!vietQrImageFailed"
                  :src="momoQrImageUrl"
                  class="vietqr-qrcode-img momo-personal-qr"
                  alt="MoMo QR Lê Ngọc Tài"
                  @error="useNextMomoQrImage"
                />
                <div v-else class="vietqr-fallback">
                  <strong>Chưa có ảnh QR MoMo</strong>
                  <span>Đặt ảnh QR vào frontend/public/payment với tên momo-ngoc-tai.jpg hoặc qr-momo.jpg.</span>
                </div>
              </div>
              <div class="vietqr-bank-details">
                <div class="vietqr-row">
                  <span class="vqr-label">Ví nhận:</span>
                  <span class="vqr-val">{{ momoAccountName }}</span>
                </div>
                <div class="vietqr-row">
                  <span class="vqr-label">STK MoMo:</span>
                  <span class="vqr-val font-mono">{{ momoMaskedAccount }}</span>
                </div>
                <div class="vietqr-row clickable-copy" @click="copyPaymentText(String(paymentQrAmount), 'Đã sao chép số tiền!')" title="Click để sao chép">
                  <span class="vqr-label">Số tiền:</span>
                  <span class="vqr-val font-mono">{{ formatPrice(paymentQrAmount) }} <i class="copy-icon">📋</i></span>
                </div>
                <div class="vietqr-row clickable-copy" @click="copyPaymentText(paymentQrMemo, 'Đã sao chép nội dung chuyển khoản!')" title="Click để sao chép">
                  <span class="vqr-label">Nội dung CK:</span>
                  <span class="vqr-val font-mono highlight-memo">{{ paymentQrMemo }} <i class="copy-icon">📋</i></span>
                </div>
              </div>
              <button
                type="button"
                class="payment-confirm-btn"
                :disabled="paymentQrExpired || paymentNoticeLoading || paymentNoticeSent"
                @click="confirmManualPayment"
              >
                {{ paymentNoticeSent ? 'Đã gửi thông báo thanh toán' : (paymentNoticeLoading ? 'Đang gửi thông báo...' : 'Tôi đã chuyển khoản') }}
              </button>
              <div class="vietqr-hint">{{ paymentQrExpired ? 'Vui lòng tạo lại đơn hoặc liên hệ nhân viên để lấy mã mới.' : 'Sau khi chuyển khoản xong, bấm nút bên trên để gửi Gmail thông báo cho cửa hàng kiểm tra giao dịch.' }}</div>
            </div>

            <div class="bill-actions">
              <a v-if="payUrl" :href="payUrl" target="_blank" class="pay-now-btn">Thanh toán trực tuyến ngay</a>
              <button type="button" class="done-btn" @click="resetChatbotView">Quay lại trò chuyện</button>
            </div>
            
            <p class="bill-note">Sau khi chuyển khoản, bấm “Tôi đã chuyển khoản” để cửa hàng nhận Gmail thông báo và kiểm tra giao dịch.</p>
          </div>
        </template>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
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
  paymentMethod: 'bank'
});

const userAddresses = ref([]);
const selectedAddressId = ref(null);
const depositConfirmed = ref(false);
const confirmInfoChecked = ref(false);
const paymentQrDurationMs = 15 * 60 * 1000;
const paymentQrExpiresAt = ref(null);
const paymentQrRemainingMs = ref(paymentQrDurationMs);
const vietQrImageFailed = ref(false);
const momoQrImageIndex = ref(0);
const paymentNoticeLoading = ref(false);
const paymentNoticeSent = ref(false);
let paymentQrTimer = null;

const momoQrImageCandidates = [
  '/payment/momo-ngoc-tai.jpg',
  '/payment/momo-ngoc-tai.png',
  '/payment/momo-ngoc-tai.jpeg',
  '/payment/qr-momo.jpg',
  '/payment/qr-momo.png',
  '/payment/qr-momo.jpeg',
  '/payment/momo-qr.jpg',
  '/payment/momo-qr.png',
  '/payment/momo-qr.jpeg',
];
const momoQrImageUrl = computed(() => momoQrImageCandidates[momoQrImageIndex.value]);
const momoAccountName = 'LÊ NGỌC TÀI';
const momoMaskedAccount = '*******383';

const checkoutProducts = computed(() => {
  const productMap = new Map();

  messages.value.forEach((msg) => {
    (msg.products || []).forEach((prod) => {
      if (prod?.id_bienthe) {
        productMap.set(prod.id_bienthe, prod);
      }
    });
  });

  if (selectedProduct.value?.id_bienthe) {
    productMap.set(selectedProduct.value.id_bienthe, selectedProduct.value);
  }

  return Array.from(productMap.values());
});

const selectCheckoutProduct = (product) => {
  selectedProduct.value = product;
};

const selectedProductPrice = computed(() => Number(selectedProduct.value?.gia || 0));
const depositAmount = computed(() => Math.ceil(selectedProductPrice.value * 0.5));
const remainingAmount = computed(() => Math.max(0, selectedProductPrice.value - depositAmount.value));
const paymentQrAmount = computed(() => {
  const orderTotal = Number(createdOrder.value?.tongtien || createdOrder.value?.tong_tien || selectedProductPrice.value || 0);
  return Math.max(1000, depositAmount.value || Math.ceil(orderTotal * 0.5));
});
const paymentQrMemo = computed(() => `VINATECH ${createdOrder.value?.ma_dathang || createdOrder.value?.id_dathang || 'ORDER'}`);
const paymentQrExpired = computed(() => paymentQrRemainingMs.value <= 0);
const paymentQrRemainingText = computed(() => {
  const totalSeconds = Math.max(0, Math.ceil(paymentQrRemainingMs.value / 1000));
  const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
  const seconds = String(totalSeconds % 60).padStart(2, '0');
  return `${minutes}:${seconds}`;
});

const stopPaymentQrTimer = () => {
  if (paymentQrTimer) {
    clearInterval(paymentQrTimer);
    paymentQrTimer = null;
  }
};

const updatePaymentQrRemaining = () => {
  if (!paymentQrExpiresAt.value) {
    paymentQrRemainingMs.value = paymentQrDurationMs;
    return;
  }

  paymentQrRemainingMs.value = Math.max(0, paymentQrExpiresAt.value - Date.now());
  if (paymentQrRemainingMs.value <= 0) {
    stopPaymentQrTimer();
  }
};

const startPaymentQrTimer = () => {
  stopPaymentQrTimer();
  vietQrImageFailed.value = false;
  momoQrImageIndex.value = 0;
  paymentNoticeLoading.value = false;
  paymentNoticeSent.value = false;
  paymentQrExpiresAt.value = Date.now() + paymentQrDurationMs;
  updatePaymentQrRemaining();
  paymentQrTimer = setInterval(updatePaymentQrRemaining, 1000);
};

const goToOrderConfirm = () => {
  if (!depositConfirmed.value) {
    swal.warning('Xác nhận đặt cọc', 'Vui lòng đồng ý chuyển trước 50% giá trị đơn hàng để tiếp tục.');
    return;
  }

  checkoutForm.value.paymentMethod = 'bank';
  confirmInfoChecked.value = false;
  chatbotView.value = 'confirm';
};

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
  stopPaymentQrTimer();
  chatbotView.value = 'chat';
  selectedProduct.value = null;
  createdOrder.value = null;
  payUrl.value = '';
  depositConfirmed.value = false;
  confirmInfoChecked.value = false;
  paymentQrExpiresAt.value = null;
  paymentQrRemainingMs.value = paymentQrDurationMs;
  vietQrImageFailed.value = false;
  momoQrImageIndex.value = 0;
  paymentNoticeLoading.value = false;
  paymentNoticeSent.value = false;
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
  checkoutForm.value.paymentMethod = 'bank';
  depositConfirmed.value = false;
  confirmInfoChecked.value = false;
  stopPaymentQrTimer();
  paymentQrExpiresAt.value = null;
  paymentQrRemainingMs.value = paymentQrDurationMs;
  
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

  if (!confirmInfoChecked.value) {
    swal.warning('Xác nhận thông tin', 'Vui lòng xác nhận thông tin nhận hàng và khoản đặt cọc trước khi tạo đơn.');
    return;
  }

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
    checkoutForm.value.paymentMethod = 'bank';
    const response = await api.post('/checkout', {
        id_diachi: selectedAddressId.value || undefined,
        diachi: checkoutForm.value.address,
        name: checkoutForm.value.name,
        phone: phoneStr,
        email: checkoutForm.value.email,
        PTTT: checkoutForm.value.paymentMethod === 'vnpay' ? 'VNPAY' : (checkoutForm.value.paymentMethod === 'momo' ? 'MOMO' : (checkoutForm.value.paymentMethod === 'bank' ? 'Chuyển khoản' : 'COD')),
        selected_variants: [selectedProduct.value.id_bienthe],
        chatbot_order: true,
        deposit_percent: 50,
        deposit_amount: depositAmount.value,
        remaining_amount: remainingAmount.value,
        note: `Chatbot deposit 50%: ${depositAmount.value}. Remaining on delivery: ${remainingAmount.value}.`
    });

    if (response.data.success) {
        createdOrder.value = response.data.order;
        payUrl.value = response.data.payUrl || '';
        chatbotView.value = 'bill';
        startPaymentQrTimer();
        
        window.dispatchEvent(new Event('cart-updated'));
        
        // Kích hoạt gửi email hóa đơn
        if (false && createdOrder.value?.id_dathang) {
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

const copyText = (text, successMsg) => {
  navigator.clipboard.writeText(text).then(() => {
    swal.toast(successMsg || 'Đã sao chép vào bộ nhớ tạm!');
  }).catch(err => {
    console.error('Lỗi sao chép:', err);
  });
};

const copyPaymentText = (text, successMsg) => {
  if (paymentQrExpired.value) {
    swal.warning('Mã QR đã hết hạn', 'Mã thanh toán chỉ có hiệu lực trong 15 phút. Vui lòng tạo lại đơn hoặc liên hệ nhân viên để lấy mã mới.');
    return;
  }

  copyText(text, successMsg);
};

const useNextMomoQrImage = () => {
  if (momoQrImageIndex.value < momoQrImageCandidates.length - 1) {
    momoQrImageIndex.value += 1;
    return;
  }

  vietQrImageFailed.value = true;
};

const confirmManualPayment = async () => {
  if (!createdOrder.value?.id_dathang || paymentNoticeLoading.value || paymentNoticeSent.value) return;

  if (paymentQrExpired.value) {
    swal.warning('Mã QR đã hết hạn', 'Vui lòng tạo lại đơn hoặc liên hệ nhân viên để lấy mã mới.');
    return;
  }

  paymentNoticeLoading.value = true;

  try {
    const res = await api.post(`/orders/${createdOrder.value.id_dathang}/payment-notice`, {
      amount: paymentQrAmount.value,
      memo: paymentQrMemo.value,
      method: 'momo_personal_qr',
    });

    paymentNoticeSent.value = true;
    if (res.data?.order) {
      createdOrder.value = { ...createdOrder.value, ...res.data.order };
    }
    swal.success('Đã gửi thông báo', res.data?.message || 'Cửa hàng đã nhận thông báo chuyển khoản và sẽ kiểm tra giao dịch.');
  } catch (error) {
    console.error('Lỗi gửi thông báo thanh toán:', error);
    swal.error('Lỗi thông báo', error.response?.data?.message || 'Không gửi được thông báo thanh toán. Vui lòng thử lại.');
  } finally {
    paymentNoticeLoading.value = false;
  }
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
  
  if (!image) return 'https://placehold.co/150';
  if (image.startsWith('http')) return image;
  return storageUrl(image);
};

const messages = ref([
  {
    role: 'bot',
    content: "Xin chào anh/chị! Em là Mia, chuyên viên hỗ trợ của VinaTech. Rất vui được đồng hành cùng anh/chị. Anh/chị đang cần tìm kiếm dòng máy nào (văn phòng, đồ họa hay gaming) trong tầm giá bao nhiêu ạ? Em sẽ tư vấn chi tiết cho mình nhé!"
  }
]);

const quickSuggestions = [
  'Laptop giá rẻ',
  'Laptop gaming 25 triệu',
  'MacBook văn phòng',
  'Máy cho sinh viên',
  'Laptop đồ họa',
  'Có khuyến mãi không?',
];

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

const scrollToLatestConversationStart = async () => {
  await nextTick();
  if (!chatBody.value) return;

  const userMessages = chatBody.value.querySelectorAll('[data-message-role="user"]');
  const botMessages = chatBody.value.querySelectorAll('[data-message-role="bot"]');
  const latestExchangeStart = userMessages[userMessages.length - 1] || botMessages[botMessages.length - 1];
  if (!latestExchangeStart) return;

  chatBody.value.scrollTo({
    top: Math.max(latestExchangeStart.offsetTop - 18, 0),
    behavior: 'smooth',
  });
};

const sendMessage = async () => {
  if (!newMessage.value.trim() || isLoading.value) return;

  const userText = newMessage.value.trim();
  messages.value.push({ role: 'user', content: userText });
  newMessage.value = '';
  isLoading.value = true;
  await scrollToBottom();

  let botMessage = null;

  try {
    const response = await api.post('/chat', { message: userText });

    if (response.data.reply) {
      botMessage = {
        role: 'bot',
        content: response.data.reply,
        products: response.data.products || []
      };
    } else {
      botMessage = {
        role: 'bot',
        content: 'Bot chưa có phản hồi hợp lệ từ server.'
      };
    }
  } catch (error) {
    console.error('Chat error full:', error);

    if (error?.response?.data?.reply) {
      botMessage = {
        role: 'bot',
        content: error.response.data.reply
      };
    } else if (error?.response?.data?.message) {
      botMessage = {
        role: 'bot',
        content: 'Lỗi backend: ' + error.response.data.message
      };
    } else {
      botMessage = {
        role: 'bot',
        content: 'Không gọi được API chat. Kiểm tra Laravel route /api/chat và controller.'
      };
    }
  } finally {
    isLoading.value = false;
    if (botMessage) {
      messages.value.push(botMessage);
      await scrollToLatestConversationStart();
    }
  }
};

const sendQuickSuggestion = (suggestion) => {
  if (isLoading.value) return;

  newMessage.value = suggestion;
  sendMessage();
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
      stopPaymentQrTimer();
      paymentQrExpiresAt.value = null;
      paymentQrRemainingMs.value = paymentQrDurationMs;
      
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
  stopPaymentQrTimer();
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
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 18px 48px rgba(15, 23, 42, 0.18);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.28);
  transform-origin: bottom right;
}

.chat-close-btn {
  margin-left: auto;
  background: transparent;
  border: none;
  color: #475569;
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
  background: rgba(255, 255, 255, 0.16);
  color: #ffffff;
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
  background: #0d1b2e;
  padding: 15px 20px;
  color: #ffffff;
  position: relative;
  border-bottom: 1px solid rgba(96, 165, 250, 0.18);
}

.mode-toggle-btn {
  margin-left: auto;
  background: #2563eb;
  border: 1px solid #2563eb;
  border-radius: 20px;
  color: white;
  padding: 5px 12px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.mode-toggle-btn:hover {
  background: #1d4ed8;
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
  background: #ffffff;
  border-radius: 50%;
  padding: 2px;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.22), 0 8px 18px rgba(15, 23, 42, 0.12);
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
  font-weight: 800;
  color: #ffffff;
}

.title-wrap .subtitle {
  margin: 2px 0 0;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.82);
  opacity: 1;
  font-weight: 600;
}

/* ===== BODY ===== */
.chat-body {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  background: #f8fafc;
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
  background: #ffffff;
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
  font-size: 14.5px;
  line-height: 1.55;
  font-weight: 600;
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
  background: #ffffff;
  color: #1e293b;
  border-bottom-left-radius: 4px;
  border: 1px solid rgba(148, 163, 184, 0.26);
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
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
  border: 1px solid rgba(148, 163, 184, 0.24);
  border-radius: 12px;
  padding: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
}

.bot-product-card:hover {
  transform: translateY(-2px);
  border-color: #2563eb;
  background: #eff6ff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
}

.bot-product-img {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 8px;
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.24);
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
  color: #0f172a;
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
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
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
  box-shadow: 0 0 8px rgba(37, 99, 235, 0.4);
}

.bot-product-buy-btn:active {
  transform: scale(0.95);
}

/* ===== TYPING INDICATOR ===== */
.typing-indicator {
  display: inline-flex;
  gap: 8px;
  padding: 12px 16px;
  align-items: center;
  color: #475569;
  font-size: 13px;
  font-weight: 700;
}

.typing-text {
  white-space: nowrap;
}

.typing-dots {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.typing-dots i {
  width: 6px;
  height: 6px;
  background: #2563eb;
  border-radius: 50%;
  animation: typing 1.4s infinite ease-in-out;
  opacity: 0.5;
}

.typing-dots i:nth-child(1) {
  animation-delay: 0s;
}

.typing-dots i:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-dots i:nth-child(3) {
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
  background: #ffffff;
  border-top: 1px solid rgba(148, 163, 184, 0.22);
}

.quick-suggestions {
  display: flex;
  gap: 8px;
  margin-bottom: 10px;
  padding-bottom: 2px;
  overflow-x: auto;
  scrollbar-width: none;
}

.quick-suggestions::-webkit-scrollbar {
  display: none;
}

.quick-suggestion-chip {
  flex: 0 0 auto;
  min-height: 32px;
  padding: 7px 12px;
  border: 1px solid #dbeafe;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12.5px;
  font-weight: 800;
  line-height: 1;
  cursor: pointer;
  transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
  white-space: nowrap;
}

.quick-suggestion-chip:hover:not(:disabled) {
  transform: translateY(-1px);
  border-color: #2563eb;
  background: #2563eb;
  color: #ffffff;
}

.quick-suggestion-chip:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.input-form {
  display: flex;
  gap: 10px;
  background: #f8fafc;
  padding: 5px 5px 5px 15px;
  border-radius: 30px;
  border: 1px solid rgba(148, 163, 184, 0.28);
  transition: all 0.3s;
}

.input-form:focus-within {
  background: #ffffff;
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
  font-weight: 600;
}

.input-form input::placeholder {
  color: #64748b !important;
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

.checkout-product-picker {
  background: rgba(13, 27, 46, 0.55);
  border: 1px solid rgba(37, 99, 235, 0.12);
  border-radius: 10px;
  padding: 7px;
}

.picker-label {
  color: #94a3b8;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.35px;
  text-transform: capitalize;
  margin: 0 0 5px 2px;
}

.checkout-product-list {
  max-height: 92px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 5px;
  padding-right: 3px;
}

.checkout-product-list::-webkit-scrollbar {
  width: 4px;
}

.checkout-product-list::-webkit-scrollbar-thumb {
  background: rgba(96, 165, 250, 0.6);
  border-radius: 999px;
}

.checkout-product-mini {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #0d1b2e;
  border: 1px solid rgba(37, 99, 235, 0.12);
  border-radius: 12px;
  padding: 8px 12px;
  margin-bottom: 0;
  text-align: left;
  cursor: pointer;
  width: 100%;
}

.checkout-product-mini.active {
  border-color: rgba(56, 189, 248, 0.8);
  background: #0f2744;
  box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.16);
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
  text-transform: capitalize;
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
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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
  text-transform: capitalize;
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
  color: #3b82f6;
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
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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
  background: rgba(37, 99, 235, 0.15);
  color: #3b82f6;
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
  text-transform: capitalize;
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
  color: #3b82f6;
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
  background: rgba(37, 99, 235, 0.1);
  color: #4ade80;
}

.payment-tip-badge.bank {
  background: rgba(59, 130, 246, 0.1);
  color: #60a5fa;
}

/* ===== VIETQR STYLING ===== */
.vietqr-payment-box {
  background: #0d1b2e;
  border: 1px solid rgba(37, 99, 235, 0.2);
  border-radius: 12px;
  width: 100%;
  padding: 14px;
  text-align: center;
  box-sizing: border-box;
  margin-top: 14px;
}

.vietqr-payment-box.expired {
  border-color: rgba(239, 68, 68, 0.35);
}

.vietqr-heading {
  font-size: 11px;
  font-weight: 800;
  color: #4ade80;
  margin-bottom: 7px;
  letter-spacing: 0.5px;
}

.payment-expire-timer {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 24px;
  padding: 4px 10px;
  margin: -2px 0 10px;
  border-radius: 999px;
  background: rgba(250, 204, 21, 0.12);
  color: #facc15;
  font-size: 11px;
  font-weight: 800;
}

.payment-expire-timer.expired {
  background: rgba(239, 68, 68, 0.14);
  color: #f87171;
}

.vietqr-image-wrapper {
  background: #ffffff;
  padding: 10px;
  border-radius: 12px;
  display: inline-block;
  margin-bottom: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.vietqr-payment-box.expired .vietqr-image-wrapper {
  opacity: 0.42;
  filter: grayscale(1);
}

.vietqr-qrcode-img {
  width: 172px;
  height: 172px;
  display: block;
}

.momo-personal-qr {
  width: min(210px, 100%);
  height: auto;
  max-height: 280px;
  object-fit: contain;
  border-radius: 10px;
}

.vietqr-fallback {
  width: 172px;
  height: 172px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #0f172a;
  text-align: center;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  background: #f8fafc;
  padding: 12px;
  box-sizing: border-box;
}

.vietqr-fallback strong {
  font-size: 13px;
  color: #dc2626;
}

.vietqr-fallback span {
  font-size: 11px;
  line-height: 1.35;
  color: #475569;
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
  gap: 10px;
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
  text-align: right;
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

.payment-confirm-btn {
  width: 100%;
  min-height: 38px;
  margin-top: 4px;
  border: 0;
  border-radius: 10px;
  background: linear-gradient(135deg, #ec4899, #d946ef);
  color: #ffffff;
  font-size: 12.5px;
  font-weight: 900;
  cursor: pointer;
  box-shadow: 0 10px 22px rgba(217, 70, 239, 0.22);
  transition: transform 0.18s ease, opacity 0.18s ease;
}

.payment-confirm-btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.payment-confirm-btn:disabled {
  cursor: not-allowed;
  opacity: 0.58;
  box-shadow: none;
}

/* Compact checkout form inside chatbot */
.chatbot-window.checkout-window {
  width: 320px;
  height: 430px;
  border-radius: 18px;
}

.checkout-chat-header {
  padding: 8px 14px;
}

.checkout-chat-header .header-info {
  gap: 8px;
}

.checkout-chat-header .title-wrap .title {
  font-size: 14px;
  line-height: 1.2;
}

.checkout-chat-header .title-wrap .subtitle {
  font-size: 10.5px;
  margin-top: 1px;
}

.checkout-chat-header .chat-back-navigation-btn,
.checkout-chat-header .chat-close-btn {
  width: 28px;
  height: 28px;
  padding: 0;
  font-size: 17px;
}

.form-view {
  padding: 10px 12px !important;
  gap: 7px;
  overflow-y: auto;
}

.checkout-product-picker {
  padding: 6px;
}

.checkout-product-list {
  max-height: 78px;
}

.checkout-product-mini {
  gap: 8px;
  padding: 6px 9px;
  margin-bottom: 0;
  border-radius: 9px;
}

.checkout-product-mini .mini-img {
  width: 32px;
  height: 32px;
}

.checkout-product-mini .mini-name {
  font-size: 10.8px;
  line-height: 1.25;
}

.checkout-product-mini .mini-price {
  font-size: 11.5px;
  margin-top: 1px;
}

.chatbot-checkout-form {
  gap: 5px;
}

.chatbot-checkout-form .input-group {
  gap: 2px;
}

.chatbot-checkout-form .input-group label {
  font-size: 9px;
  letter-spacing: 0.25px;
}

.chatbot-checkout-form input,
.chatbot-checkout-form textarea,
.chatbot-checkout-form select {
  min-height: 30px !important;
  padding: 5px 9px !important;
  font-size: 11.5px !important;
  margin-top: 1px !important;
  border-radius: 7px !important;
}

.chatbot-checkout-form textarea {
  height: 38px !important;
}

.payment-tip-badge {
  font-size: 9px;
  margin-top: 3px;
  padding: 3px 6px;
  line-height: 1.25;
}

.submit-btn {
  padding: 7px 9px;
  margin-top: 4px;
  font-size: 11.5px;
  border-radius: 12px;
}

/* Shipping form redesign */
.chatbot-window.checkout-window {
  width: 285px;
  height: 462px;
  background: #111827;
  border-color: rgba(80, 99, 133, 0.8);
  border-radius: 8px;
}

.checkout-chat-header {
  background: #202a3f;
  border-bottom: 1px solid rgba(91, 111, 145, 0.35);
  padding: 8px 12px;
}

.checkout-chat-header .title-wrap {
  margin-left: 2px !important;
}

.checkout-chat-header .title-wrap .title {
  color: #ffffff;
  font-size: 12px;
  font-weight: 800;
}

.checkout-chat-header .title-wrap .subtitle {
  color: #7ee787;
  font-size: 8px;
  font-weight: 700;
}

.checkout-chat-header .chat-back-navigation-btn,
.checkout-chat-header .chat-close-btn {
  color: #cbd5e1;
}

.form-view {
  padding: 0 !important;
  gap: 0;
  background: #111827 !important;
  overflow: hidden;
}

.form-view > .checkout-product-picker,
.form-view > .chatbot-checkout-form {
  display: none !important;
}

.shipping-designed-form {
  min-height: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #111827;
  color: #dbeafe;
}

.checkout-steps {
  display: grid;
  grid-template-columns: 44px 1fr 64px 1fr 52px;
  align-items: center;
  padding: 9px 13px 8px;
  background: #111827;
  border-bottom: 1px solid rgba(91, 111, 145, 0.22);
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  color: #59677e;
  font-size: 8px;
  font-weight: 800;
}

.step span {
  width: 18px;
  height: 18px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: rgba(148, 163, 184, 0.12);
  color: #526075;
  font-size: 10px;
}

.step small {
  font-size: 7px;
  letter-spacing: 0.45px;
}

.step.done span,
.step.active span {
  background: #12c781;
  color: #06251b;
}

.step.active small {
  color: #2ff3b2;
}

.step-line {
  height: 1px;
  background: #334155;
}

.step-line.active {
  background: #12c781;
}

.checkout-scroll {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 13px 14px 12px;
}

.checkout-scroll::-webkit-scrollbar,
.shipping-products::-webkit-scrollbar {
  width: 4px;
}

.checkout-scroll::-webkit-scrollbar-thumb,
.shipping-products::-webkit-scrollbar-thumb {
  background: rgba(45, 243, 178, 0.5);
  border-radius: 999px;
}

.checkout-helper {
  display: grid;
  grid-template-columns: 18px 1fr;
  gap: 8px;
  align-items: start;
  margin-bottom: 11px;
}

.checkout-helper span {
  width: 18px;
  height: 18px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: rgba(18, 199, 129, 0.13);
  color: #2ff3b2;
  font-size: 9px;
}

.checkout-helper p {
  margin: 0;
  background: #202a3f;
  border-radius: 6px;
  padding: 8px 9px;
  color: #dbeafe;
  font-size: 9px;
  line-height: 1.35;
  font-style: italic;
}

.shipping-card {
  margin-bottom: 10px;
}

.shipping-products {
  max-height: 90px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding-right: 2px;
}

.shipping-product {
  width: 100%;
  display: grid;
  grid-template-columns: 38px minmax(0, 1fr) auto;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background: #202a3f;
  border: 1px solid rgba(96, 116, 148, 0.65);
  border-radius: 6px;
  color: #ffffff;
  cursor: pointer;
  text-align: left;
}

.shipping-product.active {
  border-color: #2ff3b2;
  box-shadow: 0 0 0 1px rgba(47, 243, 178, 0.18);
}

.shipping-product img {
  width: 32px;
  height: 32px;
  border-radius: 5px;
  object-fit: cover;
  background: #111827;
}

.shipping-product span {
  min-width: 0;
}

.shipping-product small {
  display: block;
  color: #7ee787;
  font-size: 7px;
  font-weight: 800;
  letter-spacing: 0.45px;
}

.shipping-product strong {
  display: block;
  color: #ffffff;
  font-size: 9px;
  line-height: 1.25;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.shipping-product b {
  color: #2ff3b2;
  font-size: 8px;
  white-space: nowrap;
}

.shipping-form {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.shipping-form .field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.shipping-form label {
  color: #7ee787;
  font-size: 7px;
  font-weight: 900;
  letter-spacing: 0.45px;
  text-transform: capitalize;
}

.shipping-form input,
.shipping-form textarea,
.shipping-form select {
  width: 100%;
  min-height: 31px;
  padding: 8px 9px;
  border: 1px solid #334155;
  border-radius: 4px;
  background: #172033;
  color: #ffffff;
  font-size: 9px;
  font-weight: 700;
  outline: none;
  box-sizing: border-box;
}

.shipping-form textarea {
  height: 45px;
  resize: none;
}

.shipping-form input::placeholder,
.shipping-form textarea::placeholder {
  color: #6b7890;
}

.shipping-form input:focus,
.shipping-form textarea:focus,
.shipping-form select:focus {
  border-color: #2ff3b2;
  box-shadow: 0 0 0 2px rgba(47, 243, 178, 0.12);
}

.shipping-submit {
  min-height: 35px;
  border: 0;
  border-radius: 6px;
  background: #16c784;
  color: #102018;
  font-size: 10px;
  font-weight: 900;
  text-transform: capitalize;
  cursor: pointer;
}

.shipping-submit span {
  margin-left: 5px;
}

.shipping-secure {
  margin: 1px 0 0;
  color: #738096;
  font-size: 7px;
  text-align: center;
}

.chatbot-window .form-view {
  flex: 1;
  min-height: 0;
}

/* Checkout form should feel like the product cards inside the chatbot */
.chatbot-window .checkout-chat-header {
  background: #0d1b2e;
  border-bottom: 1px solid rgba(96, 165, 250, 0.18);
  padding: 15px 20px;
}

.chatbot-window .checkout-chat-header .title-wrap .title {
  color: #ffffff;
  font-size: 16px;
}

.chatbot-window .checkout-chat-header .title-wrap .subtitle {
  color: #7ee787;
  font-size: 12px;
}

.chatbot-window .checkout-chat-header .chat-back-navigation-btn,
.chatbot-window .checkout-chat-header .chat-close-btn {
  color: #cbd5e1;
  background: rgba(255, 255, 255, 0.1);
}

.chatbot-window .form-view {
  background: #f8fafc !important;
  overflow-y: auto;
}

.chatbot-window .shipping-designed-form {
  background: #f8fafc;
  color: #0f172a;
  height: auto;
  flex: 0 0 auto;
}

.chatbot-window .checkout-steps {
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
}

.chatbot-window .step {
  color: #94a3b8;
}

.chatbot-window .step span {
  background: #e2e8f0;
  color: #64748b;
}

.chatbot-window .step.done span,
.chatbot-window .step.active span {
  background: #2563eb;
  color: #ffffff;
}

.chatbot-window .step.active small {
  color: #2563eb;
}

.chatbot-window .step-line {
  background: #cbd5e1;
}

.chatbot-window .step-line.active {
  background: #2563eb;
}

.chatbot-window .checkout-scroll {
  padding: 10px 20px 16px;
}

.chatbot-window .checkout-helper {
  display: none;
}

.chatbot-window .shipping-products {
  max-height: 170px;
  gap: 8px;
}

.chatbot-window .shipping-products::-webkit-scrollbar-thumb,
.chatbot-window .checkout-scroll::-webkit-scrollbar-thumb {
  background: #bfdbfe;
}

.chatbot-window .shipping-product {
  grid-template-columns: 48px minmax(0, 1fr) 58px;
  gap: 8px;
  padding: 8px;
  background: #ffffff;
  border: 1px solid #dbe4ef;
  border-radius: 12px;
  color: #0f172a;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
}

.chatbot-window .shipping-product.active {
  border-color: #2563eb;
  box-shadow: 0 10px 24px rgba(22, 163, 74, 0.14);
}

.chatbot-window .shipping-product img {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}

.chatbot-window .shipping-product small {
  display: none;
}

.chatbot-window .shipping-product strong {
  color: #0f172a;
  font-size: 12.5px;
  line-height: 1.28;
  white-space: normal;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.chatbot-window .shipping-product b {
  color: #ef4444;
  font-size: 13px;
  grid-column: 2 / 3;
  white-space: nowrap;
}

.chatbot-window .shipping-product em {
  grid-column: 3;
  grid-row: 1 / span 2;
  justify-self: end;
  align-self: center;
  min-width: 48px;
  padding: 6px 8px;
  border-radius: 8px;
  background: #2563eb;
  color: #ffffff;
  font-style: normal;
  font-size: 11.5px;
  font-weight: 800;
  text-align: center;
  line-height: 1.15;
}

.chatbot-window .shipping-product.active em {
  background: #1d4ed8;
}

.chatbot-window .shipping-form {
  margin-top: 12px;
  background: #ffffff;
  border: 1px solid #dbe4ef;
  border-radius: 16px;
  padding: 14px;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
}

.chatbot-window .shipping-form label {
  color: #475569;
  font-size: 11px;
}

.chatbot-window .shipping-form input,
.chatbot-window .shipping-form textarea,
.chatbot-window .shipping-form select {
  min-height: 38px;
  background: #ffffff;
  color: #0f172a;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 13px;
}

.chatbot-window .shipping-form textarea {
  height: 58px;
}

.chatbot-window .shipping-form .field:last-of-type {
  display: none;
}

.chatbot-window .shipping-submit {
  background: #2563eb;
  color: #ffffff;
  min-height: 42px;
  border-radius: 10px;
  font-size: 14px;
  display: none;
}

.chatbot-window .shipping-secure {
  color: #94a3b8;
  font-size: 10px;
}

.deposit-policy,
.confirm-deposit-plan,
.bill-deposit-plan {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #bbf7d0;
  border-radius: 12px;
  background: #f0fdf4;
  padding: 10px 12px;
  color: #0f172a;
}

.deposit-policy {
  margin: 10px 20px 0;
  width: calc(100% - 40px);
}

.deposit-row,
.confirm-deposit-plan > div,
.bill-deposit-plan > div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  font-size: 11.5px;
  font-weight: 700;
  margin: 4px 0;
}

.deposit-row strong,
.confirm-deposit-plan strong,
.bill-deposit-plan strong {
  color: #1d4ed8;
  white-space: nowrap;
}

.deposit-row.highlight strong,
.confirm-deposit-plan > div:first-child strong,
.bill-deposit-plan > div:first-child strong {
  color: #ef4444;
}

.deposit-confirm,
.confirm-info-check {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 9px;
  color: #334155;
  font-size: 11px;
  line-height: 1.35;
  font-weight: 700;
  text-align: left;
}

.deposit-confirm input,
.confirm-info-check input {
  width: 16px;
  height: 16px;
  flex: 0 0 16px;
  margin-top: 1px;
  accent-color: #2563eb;
}

.deposit-continue-btn {
  width: 100%;
  min-height: 38px;
  margin-top: 10px;
  border: 0;
  border-radius: 10px;
  background: #2563eb;
  color: #ffffff;
  font-size: 13px;
  font-weight: 900;
  cursor: pointer;
}

.confirm-deposit-plan {
  margin-bottom: 10px;
  background: rgba(22, 163, 74, 0.1);
  border-color: rgba(37, 99, 235, 0.35);
  color: #e2e8f0;
}

.confirm-deposit-plan p {
  margin: 8px 0 0;
  color: #bbf7d0 !important;
  font-size: 11px;
  line-height: 1.35;
}

.confirm-info-check {
  margin: 0 0 12px;
  padding: 10px 12px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.06);
  color: #e2e8f0;
}

.bill-deposit-plan {
  margin-top: 10px;
  background: rgba(37, 99, 235, 0.08);
  border-color: rgba(37, 99, 235, 0.28);
  color: #e2e8f0;
}

/* Light confirmation step */
.chatbot-window .confirm-view {
  background: #f8fafc !important;
  color: #0f172a !important;
  padding: 16px 18px !important;
}

.chatbot-window .confirm-box {
  background: #ffffff;
  border: 1px solid #dbe4ef;
  border-radius: 14px;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
}

.chatbot-window .confirm-box .box-title {
  color: #0284c7;
}

.chatbot-window .confirm-box p {
  color: #334155 !important;
}

.chatbot-window .confirm-product-img {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}

.chatbot-window .confirm-product-info .prod-name {
  color: #0f172a;
}

.chatbot-window .confirm-product-info .prod-price {
  color: #ef4444;
}

.chatbot-window .confirm-total {
  background: #eef6ff;
  border: 1px solid #dbeafe;
  border-radius: 12px;
}

.chatbot-window .confirm-total span {
  color: #475569;
}

.chatbot-window .confirm-total b {
  color: #0f172a;
}

.chatbot-window .confirm-deposit-plan {
  background: #f0fdf4;
  border-color: #bbf7d0;
  color: #0f172a;
}

.chatbot-window .confirm-deposit-plan p {
  color: #166534 !important;
}

.chatbot-window .confirm-info-check {
  background: #ffffff;
  border: 1px solid #dbe4ef;
  color: #334155;
}

.chatbot-window .confirm-actions .cancel-btn {
  background: #e2e8f0;
  color: #0f172a !important;
}

.chatbot-window .confirm-actions .confirm-btn {
  background: #2563eb;
  color: #ffffff !important;
}

/* Light invoice card inside chatbot */
.chatbot-window .bill-view {
  background: #f8fafc !important;
  color: #0f172a !important;
}

.chatbot-window .bill-title {
  color: #0f172a;
}

.chatbot-window .bill-details {
  background: #ffffff;
  border: 1px solid #dbe4ef;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
}

.chatbot-window .bill-details p {
  color: #334155 !important;
}

.chatbot-window .bill-details p strong {
  color: #0f172a;
}

.chatbot-window .order-code-text {
  background: #e0f2fe;
  color: #0369a1;
}

.chatbot-window .badge-status-waiting {
  background: #fef3c7;
  color: #b45309;
}

.chatbot-window .bill-divider {
  border-top-color: #e2e8f0;
}

.chatbot-window .bill-product-img {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}

.chatbot-window .bill-product-info .prod-name {
  color: #0f172a;
}

.chatbot-window .bill-product-info .prod-price {
  color: #ef4444;
}

.chatbot-window .bill-total {
  color: #0f172a;
}

.chatbot-window .bill-total b {
  color: #0f172a;
}

.chatbot-window .bill-deposit-plan {
  background: #f0fdf4;
  border-color: #bbf7d0;
  color: #0f172a;
}
</style>
