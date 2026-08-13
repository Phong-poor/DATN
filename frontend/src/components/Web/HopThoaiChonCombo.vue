<script setup>
import { ref, computed, watch } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'

// --- Props & Emits ---
const props = defineProps({
  combo: {
    type: Object,
    required: true
  },
  show: {
    type: Boolean,
    default: false
  },
  triggerVariant: {
    type: Object,
    default: null
    // Biến thể laptop kích hoạt ưu đãi (có id_bienthe, ten_bienthe, gia)
    // Nếu có, sẽ tự động thêm laptop vào giỏ hàng kèm combo
  }
})

const emit = defineEmits(['close', 'success'])

// --- State ---
const selectedVariants = ref({})
const soluong = ref(1)
const isAdding = ref(false)

// Initialize selected variants with first variant of each product
watch(() => props.combo, (newCombo) => {
  if (newCombo && newCombo.products) {
    const initial = {}
    newCombo.products.forEach(p => {
      if (p.bien_thes && p.bien_thes.length > 0) {
        // Tự động chọn biến thể đầu tiên
        initial[p.id_sanpham] = p.bien_thes[0].id_bienthe
      }
    })
    selectedVariants.value = initial
  }
}, { immediate: true })

// --- Computed ---
const selectedVariantsList = computed(() => {
  const list = []
  if (!props.combo || !props.combo.products) return list

  props.combo.products.forEach(p => {
    const selectedId = selectedVariants.value[p.id_sanpham]
    const foundVariant = p.bien_thes?.find(v => v.id_bienthe === selectedId)
    if (foundVariant) {
      list.push(foundVariant)
    }
  })
  return list
})

const totalOriginalPrice = computed(() => {
  return selectedVariantsList.value.reduce((sum, v) => sum + Number(v.gia || 0), 0)
})

const totalOriginalPriceAllQty = computed(() => totalOriginalPrice.value * soluong.value)
const totalComboPriceAllQty = computed(() => Number(props.combo.giakhuyenmai) * soluong.value)

const savingAmount = computed(() => {
  const savings = totalOriginalPrice.value - Number(props.combo.giakhuyenmai)
  return savings > 0 ? savings : 0
})

const savingAmountAllQty = computed(() => savingAmount.value * soluong.value)

const isSelectionComplete = computed(() => {
  if (!props.combo || !props.combo.products) return false
  return props.combo.products.every(p => selectedVariants.value[p.id_sanpham] !== undefined)
})

// --- Actions ---
const selectVariant = (productId, variantId) => {
  selectedVariants.value[productId] = variantId
}

const getVariantObject = (productId, variantId) => {
  const p = props.combo.products.find(prod => prod.id_sanpham === productId)
  return p?.bien_thes?.find(v => v.id_bienthe === variantId) || null
}

const handleAddToCart = async () => {
  if (!isSelectionComplete.value) {
    swal.warning('Chưa cấu hình xong', 'Vui lòng chọn biến thể cho tất cả sản phẩm trong combo!')
    return
  }

  isAdding.value = true
  try {
    const comboPayload = {
      id_combo: props.combo.id_combo,
      soluong: soluong.value,
      selected_variants: Object.values(selectedVariants.value)
    }

    if (props.triggerVariant) {
      // Gửi song song: thêm laptop + thêm combo cùng lúc (Promise.all)
      const laptopRequest = api.post('/gio-hang/them', {
        id_bienthe: props.triggerVariant.id_bienthe,
        soluong: soluong.value,
      }).catch(err => {
        // Laptop đã có trong giỏ hoặc lỗi khác → bỏ qua, vẫn tiếp tục thêm combo
        console.warn('Lưu ý khi thêm laptop:', err?.response?.data?.message)
        return null
      })

      const comboRequest = api.post('/gio-hang/them-combo', comboPayload)

      const [, comboRes] = await Promise.all([laptopRequest, comboRequest])

      if (comboRes?.data?.success) {
        swal.success('Đã thêm vào giỏ', 'Đã thêm Laptop + Quà Tặng VIP vào giỏ hàng!')
        emit('success')
        emit('close')
        window.dispatchEvent(new Event('cart-updated'))
      }
    } else {
      // Combo bán lẻ thông thường (không có triggerVariant)
      const res = await api.post('/gio-hang/them-combo', comboPayload)
      if (res.data && res.data.success) {
        swal.success('Đã thêm vào giỏ', 'Combo ưu đãi đã được thêm thành công vào giỏ hàng của bạn!')
        emit('success')
        emit('close')
        window.dispatchEvent(new Event('cart-updated'))
      }
    }
  } catch (error) {
    console.error(error)
    const msg = error.response?.data?.message || 'Không thể thêm combo này vào giỏ hàng.'
    swal.error('Lỗi', msg)
  } finally {
    isAdding.value = false
  }
}
</script>

<template>
  <transition name="modal-fade">
    <div v-if="show" class="combo-modal-overlay" @click.self="emit('close')">
      <div class="combo-modal">
        <button class="close-btn" @click="emit('close')">×</button>
        
        <!-- Header -->
        <div class="modal-hdr">
          <span class="badge-promo">{{ (triggerVariant || Number(combo.giakhuyenmai || 0) === 0) ? '🎁 QUÀ TẶNG MIỄN PHÍ' : '🔥 COMBO ƯU ĐÃI' }}</span>
          <h2>{{ (triggerVariant || Number(combo.giakhuyenmai || 0) === 0) ? 'Chọn Phụ Kiện Quà Tặng VIP' : 'Cấu hình Combo Phụ Kiện' }}</h2>
          <p class="combo-title">{{ combo.ten_combo }}</p>
        </div>

        <!-- Banner thông tin laptop kích hoạt (chỉ hiện với ưu đãi biến thể) -->
        <div v-if="triggerVariant" class="trigger-laptop-banner">
          <div class="trigger-laptop-icon">💻</div>
          <div class="trigger-laptop-info">
            <span class="trigger-label">Đặc quyền kèm với:</span>
            <strong>{{ triggerVariant.ten_bienthe }}</strong>
          </div>
          <div class="trigger-price-free">
            <span class="trigger-combo-free">×{{ soluong }}</span>
          </div>
        </div>

        <div class="modal-body-content">
          <!-- Products configurator -->
          <div class="products-configs">
            <div v-for="(p, index) in combo.products" :key="p.id_sanpham" class="product-config-card">
              <!-- Connector Plus Icon -->
              <div v-if="index > 0" class="plus-connector">+</div>

              <div class="p-card-body">
                <div class="p-header">
                  <span class="p-number">Sản phẩm #{{ index + 1 }}</span>
                  <h3>{{ p.tenSP }}</h3>
                </div>

                <!-- Variant Selectors -->
                <div class="variants-section">
                  <p class="select-label">Chọn phiên bản/màu sắc:</p>
                  <div class="variant-grid">
                    <button 
                      v-for="v in p.bien_thes" 
                      :key="v.id_bienthe"
                      class="v-select-btn"
                      :class="{ active: selectedVariants[p.id_sanpham] === v.id_bienthe }"
                      @click="selectVariant(p.id_sanpham, v.id_bienthe)"
                    >
                      <div class="checked-dot"></div>
                      <div class="v-details">
                        <span class="name">{{ v.ten_bienthe }}</span>
                        <span class="price">{{ Number(v.gia).toLocaleString('vi-VN') }}đ</span>
                      </div>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Checkout Box / Summary -->
          <div class="combo-summary-box">
            <h3>Tổng quan Combo</h3>
            
            <div class="summary-details">
              <div class="sum-row">
                <span>Tổng giá mua lẻ:</span>
                <span class="strike">{{ totalOriginalPriceAllQty.toLocaleString('vi-VN') }}đ</span>
              </div>
              <div class="sum-row highlight">
                <span>Giá Combo ưu đãi:</span>
                <span class="combo-price" v-if="Number(combo.giakhuyenmai || 0) > 0">{{ totalComboPriceAllQty.toLocaleString('vi-VN') }}đ</span>
                <span class="combo-price free-text" v-else style="color: #10b981; font-weight: 800;">MIỄN PHÍ (0đ)</span>
              </div>
              <div class="sum-row saving">
                <span>Tiết kiệm được:</span>
                <span class="save-val">-{{ savingAmountAllQty.toLocaleString('vi-VN') }}đ ({{ Math.round((savingAmount / totalOriginalPrice) * 100) }}%)</span>
              </div>
            </div>

            <!-- Quantity Selector -->
            <div class="qty-selector-section">
              <span>Số lượng bộ combo:</span>
              <div class="qty-ctrl">
                <button :disabled="soluong <= 1" @click="soluong--">-</button>
                <input type="number" v-model.number="soluong" min="1" readonly />
                <button @click="soluong++">+</button>
              </div>
            </div>

            <button 
              :class="['add-to-cart-btn', (triggerVariant || Number(combo.giakhuyenmai || 0) === 0) ? 'btn-gift-mode' : '']"
              :disabled="isAdding || !isSelectionComplete"
              @click="handleAddToCart"
            >
              <template v-if="isAdding">
                <span class="btn-spinner"></span>
                Đang xử lý...
              </template>
              <template v-else-if="triggerVariant || Number(combo.giakhuyenmai || 0) === 0">
                <span style="font-size:18px">🎁</span>
                Nhận Quà VIP Ngay
              </template>
              <template v-else>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                  <circle cx="9" cy="21" r="1" />
                  <circle cx="20" cy="21" r="1" />
                  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                </svg>
                Thêm Combo Vào Giỏ Hàng
              </template>
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>

.combo-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  z-index: 999999 !important;
  padding: 100px 20px 20px 20px;
  overflow-y: auto;
}

.combo-modal {
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
  border-radius: 24px;
  width: 100%;
  max-width: 960px;
  max-height: calc(100vh - 130px);
  margin: 0 auto;
  overflow-y: auto;
  position: relative;
  padding: 32px;
  animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes modalScale {
  from { transform: scale(0.92); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.close-btn {
  position: absolute;
  top: 24px;
  right: 24px;
  background: var(--tn-surface-2);
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  font-size: 20px;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #cbd5e1;
  color: #0f172a;
}

.modal-hdr {
  margin-bottom: 28px;
}

.badge-promo {
  background: linear-gradient(135deg, #ff4e50, #f9d423);
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.5px;
}

.modal-hdr h2 {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  margin: 12px 0 4px;
}

.combo-title {
  font-size: 16px;
  color: #475569;
  font-weight: 600;
}

.modal-body-content {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 32px;
}

@media (max-width: 860px) {
  .modal-body-content {
    grid-template-columns: 1fr;
  }
}

.products-configs {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.product-config-card {
  position: relative;
  background: white;
  border: 1px solid var(--tn-border);
  border-radius: 18px;
  padding: 20px;
}

.plus-connector {
  position: absolute;
  top: -24px;
  left: 50%;
  transform: translateX(-50%);
  background: #3b82f6;
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 800;
  box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
  z-index: 10;
}

.p-card-body .p-number {
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.5px;
}

.p-card-body h3 {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
  margin: 4px 0 16px;
}

.select-label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 8px;
}

.variant-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 10px;
}

.v-select-btn {
  background: white;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
  transition: all 0.2s ease;
}

.v-select-btn:hover {
  border-color: #cbd5e1;
  background: var(--tn-bg);
}

.v-select-btn.active {
  border-color: #3b82f6;
  background: #eff6ff;
}

.checked-dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  background: white;
  transition: all 0.2s;
  flex-shrink: 0;
}

.v-select-btn.active .checked-dot {
  border-color: #3b82f6;
  background: #3b82f6;
  box-shadow: inset 0 0 0 2.5px white;
}

.v-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.v-details .name {
  font-size: 12.5px;
  font-weight: 700;
  color: #1e293b;
}

.v-details .price {
  font-size: 11px;
  color: #64748b;
}

/* Summary Box */
.combo-summary-box {
  background: var(--tn-bg);
  border-radius: 20px;
  border: 1px solid #edf2f7;
  padding: 24px;
  height: fit-content;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.combo-summary-box h3 {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  border-bottom: 1.5px dashed #e2e8f0;
  padding-bottom: 12px;
  margin: 0;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.sum-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #475569;
}

.sum-row.highlight {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.sum-row.highlight .combo-price {
  font-size: 18px;
  font-weight: 800;
  color: #2563eb;
}

.sum-row.saving {
  color: #166534;
  font-weight: 700;
  background: #f0fdf4;
  padding: 6px 10px;
  border-radius: 8px;
}

.qty-selector-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
  color: #475569;
  font-weight: 600;
}

.qty-ctrl {
  display: flex;
  align-items: center;
  border: 1px solid #cbd5e1;
  background: white;
  border-radius: 8px;
  overflow: hidden;
}

.qty-ctrl button {
  background: none;
  border: none;
  width: 32px;
  height: 32px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 700;
  color: #475569;
  transition: background 0.2s;
}

.qty-ctrl button:hover {
  background: var(--tn-surface-2);
}

.qty-ctrl button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.qty-ctrl input {
  width: 40px;
  height: 32px;
  text-align: center;
  border: none;
  border-left: 1px solid #cbd5e1;
  border-right: 1px solid #cbd5e1;
  font-size: 14px;
  font-weight: 800;
  color: #0f172a !important;
  -webkit-text-fill-color: #0f172a !important;
  opacity: 1 !important;
  background: #ffffff !important;
  outline: none;
}

.add-to-cart-btn {
  width: 100%;
  background: linear-gradient(135deg, #2563eb, #2563eb);
  color: white;
  border: none;
  padding: 15px 20px;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  white-space: nowrap;
  letter-spacing: 0.2px;
}

.add-to-cart-btn:hover {
  opacity: 0.95;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35);
}

.add-to-cart-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* Nút ưu đãi quà tặng VIP */
.btn-gift-mode {
  background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
  box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35) !important;
  font-size: 16px !important;
}

.btn-gift-mode:hover {
  box-shadow: 0 8px 22px rgba(22, 163, 74, 0.45) !important;
}

/* Spinner khi đang xử lý */
.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2.5px solid rgba(255, 255, 255, 0.4);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.25s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}

/* ── Banner Laptop kích hoạt ưu đãi ── */
.trigger-laptop-banner {
  display: flex;
  align-items: center;
  gap: 14px;
  background: linear-gradient(135deg, #dcfce7, #f0fdf4);
  border: 1.5px solid #86efac;
  border-radius: 14px;
  padding: 14px 18px;
  margin-bottom: 20px;
}

.trigger-laptop-icon {
  font-size: 28px;
  flex-shrink: 0;
}

.trigger-laptop-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.trigger-label {
  font-size: 10px;
  font-weight: 700;
  color: #1d4ed8;
  text-transform: capitalize;
  letter-spacing: 0.5px;
}

.trigger-laptop-info strong {
  font-size: 13px;
  font-weight: 800;
  color: #166534;
  line-height: 1.4;
}

.trigger-price-free {
  flex-shrink: 0;
}

.trigger-combo-free {
  font-size: 18px;
  font-weight: 900;
  color: #2563eb;
}
</style>
