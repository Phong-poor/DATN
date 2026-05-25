<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../../services/api'
import swal from '@/services/swal'


// ===================== STATE =====================
const cart = ref([])
const isLoading = ref(false)
const coupon = ref('')
const discount = ref(0)
const appliedPromo = ref(null)

const freeshipCoupon = ref('')
const freeshipDiscount = ref(0)
const appliedFreeshipPromo = ref(null)

const MIN_ORDER_FOR_FREESHIP = 30000000 // Đơn hàng tối thiểu để dùng freeship (30 triệu)

const shippingFee = ref(30000)

const thongBao = ref({ show: false, type: '', message: '' })

const hienThiThongBao = (type, message) => {
    thongBao.value = { show: true, type, message }
    setTimeout(() => { thongBao.value.show = false }, 3000)
}

// ===================== GIỎ HÀNG =====================
const fetchGioHang = async () => {
    try {
        isLoading.value = true
        const res = await api.get('/gio-hang')
        cart.value = res.data.gio_hang
    } catch (err) {
        console.error('Lỗi tải giỏ hàng:', err)
    } finally {
        isLoading.value = false
    }
}

const subtotal = computed(() => cart.value.reduce((sum, item) => sum + item.thanh_tien, 0))
const total = computed(() => Math.max(0, subtotal.value - discount.value) + Math.max(0, shippingFee.value - freeshipDiscount.value))

const capNhatSoLuong = async (item, delta) => {
    const soLuongMoi = item.soluong + delta
    if (soLuongMoi < 1) return
    if (soLuongMoi > item.ton_kho) {
        hienThiThongBao('error', `Kho chỉ còn ${item.ton_kho} sản phẩm.`)
        return
    }

    item.soluong = soLuongMoi
    item.thanh_tien = item.gia * soLuongMoi

    // Tính lại discount nếu đã áp mã
    if (appliedPromo.value) tinhDiscount(appliedPromo.value)
    if (appliedFreeshipPromo.value) tinhFreeshipDiscount(appliedFreeshipPromo.value)

    try {
        await api.put(`/gio-hang/cap-nhat/${item.id_giohang}`, { soluong: soLuongMoi })
    } catch (err) {
        hienThiThongBao('error', err.response?.data?.message || 'Lỗi cập nhật số lượng!')
        fetchGioHang()
    }
}

const xoaSanPham = async (index) => {
    const item = cart.value[index]
    cart.value.splice(index, 1)

    // Tính lại discount sau khi xóa
    if (appliedPromo.value) tinhDiscount(appliedPromo.value)
    if (appliedFreeshipPromo.value) tinhFreeshipDiscount(appliedFreeshipPromo.value)

    try {
        await api.delete(`/gio-hang/xoa/${item.id_giohang}`)
        hienThiThongBao('success', 'Đã xóa sản phẩm khỏi giỏ hàng.')
    } catch (err) {
        hienThiThongBao('error', 'Lỗi xóa sản phẩm!')
        fetchGioHang()
    }
}

const xoaTatCa = async () => {
    const isConfirmed = await swal.confirm('Xóa giỏ hàng', 'Bạn có chắc chắn muốn xóa toàn bộ sản phẩm khỏi giỏ hàng?')
  if (!isConfirmed) return
    try {
        await api.delete('/gio-hang/xoa-tat')
        cart.value = []
        discount.value = 0
        appliedPromo.value = null
        coupon.value = ''
        freeshipCoupon.value = ''
        freeshipDiscount.value = 0
        appliedFreeshipPromo.value = null
        hienThiThongBao('success', 'Đã xóa toàn bộ giỏ hàng.')
    } catch (err) {
        hienThiThongBao('error', 'Lỗi xóa giỏ hàng!')
    }
}

// ===================== MÃ GIẢM GIÁ =====================
const allPromos = ref([])

const fetchPromotions = async () => {
    try {
        const res = await api.get('/promotions')
        allPromos.value = res.data
    } catch (err) {
        console.error('Lỗi tải khuyến mãi:', err)
    }
}

// Tính số tiền giảm dựa vào promo object
const tinhDiscount = (promo) => {
    if (!promo) { discount.value = 0; return }
    const sub = subtotal.value
    if (promo.type === 'percent') {
        discount.value = Math.round(sub * promo.value / 100)
    } else if (promo.type === 'fixed') {
        discount.value = Math.min(promo.value, sub)
    } else {
        discount.value = 0
    }
}

const tinhFreeshipDiscount = (promo) => {
    if (!promo) { freeshipDiscount.value = 0; return }
    // Kiểm tra lại điều kiện khi tính lại (ví dụ sau khi xóa sản phẩm)
    if (subtotal.value < MIN_ORDER_FOR_FREESHIP) {
        freeshipDiscount.value = 0
        appliedFreeshipPromo.value = null
        freeshipCoupon.value = ''
        return
    }
    freeshipDiscount.value = shippingFee.value
}

const huyMa = () => {
    coupon.value = ''
    discount.value = 0
    appliedPromo.value = null
    hienThiThongBao('success', 'Đã hủy mã giảm giá.')
}

const validPromosList = computed(() => {
    const now = new Date()
    return allPromos.value.filter(p => {
        if (p.status !== 'running' && p.status !== 'open') return false
        if (p.end_date && new Date(p.end_date) < now) return false
        return true
    })
})

const discountPromosList = computed(() => validPromosList.value.filter(p => p.category === 'product'))
const freeshipPromosList = computed(() => validPromosList.value.filter(p => p.category === 'freeship'))

const apDungMaTuSelect = () => {
    if (!coupon.value) {
        huyMa()
        return
    }
    const promo = discountPromosList.value.find(p => p.code === coupon.value)
    if (promo) {
        appliedPromo.value = promo
        tinhDiscount(promo)
        hienThiThongBao('success', `Đã chọn mã ${promo.code}`)
    }
}

const apDungFreeshipTuSelect = () => {
    if (!freeshipCoupon.value) {
        freeshipDiscount.value = 0
        appliedFreeshipPromo.value = null
        hienThiThongBao('success', 'Đã hủy mã freeship.')
        return
    }
    // Kiểm tra điều kiện đơn hàng tối thiểu
    if (subtotal.value < MIN_ORDER_FOR_FREESHIP) {
        freeshipCoupon.value = ''
        hienThiThongBao('error', `Cần mua tối thiểu ${formatPrice(MIN_ORDER_FOR_FREESHIP)} để dùng mã miễn phí vận chuyển!`)
        return
    }
    const promo = freeshipPromosList.value.find(p => p.code === freeshipCoupon.value)
    if (promo) {
        appliedFreeshipPromo.value = promo
        tinhFreeshipDiscount(promo)
        hienThiThongBao('success', `Đã chọn mã freeship ${promo.code}`)
    }
}

const autoApplyPromo = () => {
    const sub = subtotal.value
    if (sub === 0) return

    // 1. PRODUCT DISCOUNT
    let bestP = null
    let maxDP = 0

    discountPromosList.value.forEach(p => {
        let d = 0
        if (p.type === 'percent') {
            d = Math.round(sub * p.value / 100)
        } else if (p.type === 'fixed') {
            d = Math.min(p.value, sub)
        }
        if (d > maxDP) {
            maxDP = d
            bestP = p
        }
    })

    if (bestP) {
        let currentDP = 0
        if (appliedPromo.value) {
           if (appliedPromo.value.code !== bestP.code) {
               if (appliedPromo.value.type === 'percent') currentDP = Math.round(sub * appliedPromo.value.value / 100)
               else if (appliedPromo.value.type === 'fixed') currentDP = Math.min(appliedPromo.value.value, sub)
               if (maxDP > currentDP) {
                   appliedPromo.value = bestP
                   tinhDiscount(bestP)
                   coupon.value = bestP.code
                   hienThiThongBao('success', ` Tự động áp dụng mã ${bestP.code} tốt nhất!`)
               }
           }
        } else if (coupon.value === '') {
            appliedPromo.value = bestP
            tinhDiscount(bestP)
            coupon.value = bestP.code
        }
    }

    // 2. FREESHIP DISCOUNT (chỉ áp dụng nếu đơn hàng >= MIN_ORDER_FOR_FREESHIP)
    if (sub >= MIN_ORDER_FOR_FREESHIP) {
        let bestF = null
        let maxDF = 0
        freeshipPromosList.value.forEach(p => {
            let d = shippingFee.value
            if (d > maxDF) {
                maxDF = d
                bestF = p
            }
        })

        if (bestF) {
            let currentDF = appliedFreeshipPromo.value ? shippingFee.value : 0
            if (appliedFreeshipPromo.value) {
               if (appliedFreeshipPromo.value.code !== bestF.code && maxDF > currentDF) {
                   appliedFreeshipPromo.value = bestF
                   tinhFreeshipDiscount(bestF)
                   freeshipCoupon.value = bestF.code
                   hienThiThongBao('success', ` Tự động chọn mã freeship tốt nhất!`)
               }
            } else if (freeshipCoupon.value === '') {
                appliedFreeshipPromo.value = bestF
                tinhFreeshipDiscount(bestF)
                freeshipCoupon.value = bestF.code
            }
        }
    }

    if (appliedPromo.value) tinhDiscount(appliedPromo.value)
    if (appliedFreeshipPromo.value) tinhFreeshipDiscount(appliedFreeshipPromo.value)
}

watch([subtotal, allPromos], () => {
    if (subtotal.value > 0 && allPromos.value.length > 0) {
        autoApplyPromo()
    }
})

const formatPrice = (price) => new Intl.NumberFormat('vi-VN').format(price) + 'đ'

const getFullProductName = (item) => {
    let name = item.ten_san_pham || ''
    let specs = []
    try {
        const tskt = typeof item.thong_so_ky_thuat === 'string' 
            ? JSON.parse(item.thong_so_ky_thuat || '[]') 
            : (item.thong_so_ky_thuat || [])
        if (Array.isArray(tskt)) {
            specs = tskt.map(s => s.giatri).filter(Boolean)
        }
    } catch (e) { console.error('Lỗi parse thong_so_ky_thuat:', e) }
    
    return specs.length > 0 ? `${name} ${specs.join(' ')}` : name
}

onMounted(() => { 
    fetchGioHang()
    fetchPromotions()
})
</script>

<template>

    <!-- TOAST -->
    <transition name="slide-down">
        <div v-if="thongBao.show" :class="['toast', thongBao.type]">
            {{ thongBao.message }}
        </div>
    </transition>

    <div class="page">
        <div class="container cart">

            <!-- ===== LEFT: DANH SÁCH GIỎ HÀNG ===== -->
            <div class="left">
                <div class="cart-header">
                    <div>
                        <h1>Giỏ hàng <span>({{ cart.length }})</span></h1>
                        <p class="sub">Sản phẩm bạn đã chọn</p>
                    </div>
                    <button v-if="cart.length > 0" class="btn-clear" @click="xoaTatCa">
                        🗑️ Xóa tất cả
                    </button>
                </div>

                <!-- LOADING -->
                <div v-if="isLoading" class="empty-state">
                    <p>⏳ Đang tải giỏ hàng...</p>
                </div>

                <!-- RỖ -->
                <div v-else-if="cart.length === 0" class="empty-state">
                    <p>🛒 Giỏ hàng của bạn đang trống.</p>
                    <router-link to="/" class="btn-shop">Mua sắm ngay</router-link>
                </div>

                <!-- DANH SÁCH -->
                <transition-group v-else name="fade" tag="div">
                    <div class="item" v-for="(item, i) in cart" :key="item.id_giohang">
                        <img :src="item.hinh_anh || 'https://via.placeholder.com/90'" />
                        <div class="info">
                            <h3>{{ getFullProductName(item) }}</h3>
                            <p>{{ item.ten_bienthe }}</p>
                            <div class="attr-tags" v-if="item.thuoc_tinh && item.thuoc_tinh.length">
                                <span v-for="attr in item.thuoc_tinh" :key="attr.ten_thuoctinh" class="attr-tag">
                                    {{ attr.ten_thuoctinh }}: {{ attr.giatri }}
                                </span>
                            </div>
                            <div class="qty">
                                <button @click="capNhatSoLuong(item, -1)" :disabled="item.soluong <= 1">−</button>
                                <span>{{ item.soluong }}</span>
                                <button @click="capNhatSoLuong(item, +1)" :disabled="item.soluong >= item.ton_kho">+</button>
                            </div>
                        </div>
                        <div class="price-col">
                            <div class="price">{{ formatPrice(item.thanh_tien) }}</div>
                            <div class="unit-price">{{ formatPrice(item.gia) }} / sp</div>
                        </div>
                        <button class="remove" @click="xoaSanPham(i)" title="Xóa sản phẩm">×</button>
                    </div>
                </transition-group>
            </div>

            <!-- ===== RIGHT: TỔNG THANH TOÁN ===== -->
            <div class="right" v-if="!isLoading">
                <h3>Tổng thanh toán</h3>

                <div class="row">
                    <span>Tạm tính ({{ cart.length }} sản phẩm)</span>
                    <b>{{ formatPrice(subtotal) }}</b>
                </div>

                <div class="row">
                    <span>Giảm giá</span>
                    <b class="discount">{{ discount > 0 ? '-' + formatPrice(discount) : '0đ' }}</b>
                </div>

                <div class="row">
                    <span>Vận chuyển</span>
                    <b>{{ formatPrice(shippingFee) }}</b>
                </div>
                <div class="row" v-if="freeshipDiscount > 0">
                    <span style="color:#16a34a">Freeship áp dụng</span>
                    <b class="discount">-{{ formatPrice(freeshipDiscount) }}</b>
                </div>

                <!-- CHỌN MÃ FREESHIP -->
                <div class="promo-select-box" style="margin-top:0">
                    <div v-if="freeshipPromosList.length > 0" class="freeship-condition-hint" :class="{ 'condition-met': subtotal >= MIN_ORDER_FOR_FREESHIP }">
                        <span v-if="subtotal < MIN_ORDER_FOR_FREESHIP">
                            🚚 Mua thêm <b>{{ formatPrice(MIN_ORDER_FOR_FREESHIP - subtotal) }}</b> để dùng mã miễn phí ship
                        </span>
                        <span v-else>
                            ✅ Đủ điều kiện dùng mã miễn phí vận chuyển!
                        </span>
                    </div>
                    <select
                        v-model="freeshipCoupon"
                        @change="apDungFreeshipTuSelect"
                        class="promo-select freeship-select"
                        :disabled="subtotal < MIN_ORDER_FOR_FREESHIP"
                    >
                        <option value="">không dùng mã vận chuyển</option>
                        <option v-for="p in freeshipPromosList" :key="p.code" :value="p.code">
                            {{ p.name }} - Giảm 100%
                        </option>
                    </select>
                </div>

                <!-- CHỌN MÃ GIẢM GIÁ TỪ SELECT -->
                <div class="promo-select-box">
                    <p class="select-label">Mã giảm giá đơn hàng:</p>
                    <select v-model="coupon" @change="apDungMaTuSelect" class="promo-select">
                        <option value="">tôi ko sử dụng mã </option>
                        <option v-for="p in discountPromosList" :key="p.code" :value="p.code">
                            {{ p.name }} - {{ p.type === 'percent' ? `Giảm ${p.value}%` : `Giảm ${formatPrice(p.value)}` }}
                        </option>
                    </select>
                </div>

                <div class="total">
                    <span>Tổng cộng</span>
                    <h2>{{ formatPrice(total) }}</h2>
                </div>

                <router-link
                    :to="{ path: '/checkout', query: { 
                        promo_code: appliedPromo ? appliedPromo.code : '', 
                        discount: discount,
                        freeship_code: appliedFreeshipPromo ? appliedFreeshipPromo.code : '',
                        freeship_discount: freeshipDiscount
                    }}"
                    class="checkout"
                    :class="{ 'checkout-disabled': cart.length === 0 }"
                >
                    <span>Thanh toán ngay</span>
                    <span class="icon">→</span>
                </router-link>

            </div>
        </div>
    </div>

</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');

.toast {
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    padding: 14px 20px; border-radius: 12px; font-size: 14px;
    font-weight: 600; box-shadow: 0 8px 30px rgba(0,0,0,0.12); color: white;
}
.toast.success { background: #16a34a; }
.toast.error { background: #dc2626; }
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-20px); }

.page { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, #f8fafc, #eef2ff); padding: 40px 0; }
.container { width: min(1100px, 95%); margin: auto; }
.cart { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }

.cart-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.cart-header h1 span { font-size: 16px; color: #64748b; font-weight: 400; }
.btn-clear { background: #fee2e2; color: #dc2626; border: none; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: 0.2s; }
.btn-clear:hover { background: #fca5a5; }
.empty-state { text-align: center; padding: 60px 20px; color: #64748b; font-size: 15px; }
.btn-shop { display: inline-block; margin-top: 14px; background: #2563eb; color: white; padding: 10px 24px; border-radius: 10px; text-decoration: none; font-weight: 600; }

.item { display: flex; align-items: center; gap: 16px; padding: 16px; border-radius: 18px; margin-bottom: 14px; background: rgba(255,255,255,0.7); backdrop-filter: blur(12px); box-shadow: 0 20px 40px rgba(0,0,0,0.05); transition: 0.3s; }
.item:hover { transform: translateY(-5px); }
.item img { width: 90px; height: 75px; object-fit: cover; border-radius: 12px; }
.info { flex: 1; }
.info h3 { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
.info p { font-size: 12px; color: #64748b; margin-bottom: 4px; }
.attr-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px; }
.attr-tag { background: #e0e7ff; color: #4338ca; font-size: 11px; padding: 2px 8px; border-radius: 6px; font-weight: 500; }

.qty { display: flex; gap: 0; align-items: center; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; width: fit-content; margin-top: 6px; }
.qty button { width: 30px; height: 30px; border: none; background: #f1f5f9; font-size: 16px; cursor: pointer; transition: 0.2s; }
.qty button:hover:not(:disabled) { background: #dbeafe; color: #2563eb; }
.qty button:disabled { opacity: 0.4; cursor: not-allowed; }
.qty span { min-width: 36px; text-align: center; font-weight: 600; font-size: 14px; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; }

.price-col { text-align: right; }
.price { font-weight: 700; color: #2563eb; font-size: 15px; }
.unit-price { font-size: 11px; color: #94a3b8; margin-top: 2px; }
.remove { border: none; background: #fee2e2; color: #dc2626; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 18px; line-height: 1; transition: 0.2s; flex-shrink: 0; }
.remove:hover { background: #fca5a5; }

/* RIGHT */
.right { padding: 24px; border-radius: 20px; background: rgba(255,255,255,0.7); backdrop-filter: blur(12px); box-shadow: 0 20px 40px rgba(0,0,0,0.05); height: fit-content; position: sticky; top: 20px; }
.right h3 { margin-bottom: 16px; font-size: 17px; }
.row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
.discount { color: #dc2626; }
.free { color: #16a34a; }

/* SELECT MÃ GIẢM GIÁ */
.promo-select-box { margin: 16px 0; }
.select-label { font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
.promo-select {
    width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #ddd;
    font-size: 13px; outline: none; background: #f8fafc; cursor: pointer;
    font-family: inherit; color: #1e293b; font-weight: 500;
    transition: 0.2s;
}
.promo-select.freeship-select { border-color: #86efac; background: #f0fdf4; color: #166534; }
.promo-select.freeship-select:disabled { opacity: 0.5; cursor: not-allowed; background: #f1f5f9; border-color: #cbd5e1; color: #94a3b8; }
.promo-select:focus { border-color: #2563eb; background: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

/* FREESHIP CONDITION HINT */
.freeship-condition-hint {
    font-size: 12px; padding: 7px 10px; border-radius: 8px; margin-bottom: 6px;
    background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa;
    transition: all 0.3s ease;
}
.freeship-condition-hint.condition-met {
    background: #f0fdf4; color: #166534; border-color: #86efac;
}

/* SPINNER */
.spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.total { margin-top: 14px; padding-top: 14px; border-top: 2px solid #e0e7ff; display: flex; justify-content: space-between; align-items: center; }
.total span { font-size: 15px; font-weight: 600; }
.total h2 { color: #2563eb; font-size: 22px; }

.checkout { display: flex; justify-content: center; align-items: center; margin-top: 16px; padding: 14px; border-radius: 12px; background: linear-gradient(90deg, #2563eb, #4f46e5); color: white; font-weight: 600; font-size: 15px; text-decoration: none; position: relative; transition: 0.2s; }
.checkout .icon { position: absolute; right: 20px; }
.checkout:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(79,70,229,0.3); }
.checkout-disabled { pointer-events: none; opacity: 0.5; }

.suggest { margin-top: 50px; }
.suggest h3 { margin-bottom: 16px; }
.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.card { background: rgba(255,255,255,0.7); padding: 16px; border-radius: 16px; text-align: center; }
.card img { width: 100%; height: 120px; object-fit: cover; border-radius: 10px; }
.card p { margin: 8px 0 4px; font-size: 14px; }
.card span { color: #2563eb; font-weight: bold; }

.fade-enter-active, .fade-leave-active { transition: all 0.4s ease; }
.fade-enter-from { opacity: 0; transform: translateY(20px); }
.fade-leave-to { opacity: 0; transform: translateX(20px); }

@media (max-width: 768px) {
    .cart { grid-template-columns: 1fr; }
    .right { position: static; }
    .grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 576px) {
    .item {
        display: grid;
        grid-template-columns: 80px 1fr;
        gap: 12px;
        position: relative;
        padding: 12px;
    }
    .item img {
        grid-column: 1;
        grid-row: 1 / span 2;
        width: 80px;
        height: 80px;
        object-fit: cover;
    }
    .info {
        grid-column: 2;
        grid-row: 1;
        padding-right: 24px;
    }
    .price-col {
        grid-column: 2;
        grid-row: 2;
        text-align: left;
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-top: 4px;
    }
    .unit-price {
        margin-top: 0;
    }
    .remove {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 28px;
        height: 28px;
    }
}
</style>