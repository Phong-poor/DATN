<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import Header from '../Layout/Header.vue'
import Footer from '../Layout/Footer.vue'

// ===================== STATE =====================
const cart = ref([])
const isLoading = ref(true)
const coupon = ref('')
const discount = ref(0)

const thongBao = ref({ show: false, type: '', message: '' })

const hienThiThongBao = (type, message) => {
    thongBao.value = { show: true, type, message }
    setTimeout(() => { thongBao.value.show = false }, 3000)
}

//giỏ hàng 
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
const total = computed(() => subtotal.value - discount.value)

const capNhatSoLuong = async (item, delta) => {
    const soLuongMoi = item.soluong + delta
    if (soLuongMoi < 1) return
    if (soLuongMoi > item.ton_kho) {
        hienThiThongBao('error', `Kho chỉ còn ${item.ton_kho} sản phẩm.`)
        return
    }

    // Optimistic update
    item.soluong = soLuongMoi
    item.thanh_tien = item.gia * soLuongMoi

    try {
        await api.put(`/gio-hang/cap-nhat/${item.id_giohang}`, { soluong: soLuongMoi })
    } catch (err) {
        hienThiThongBao('error', err.response?.data?.message || 'Lỗi cập nhật số lượng!')
        fetchGioHang() // rollback
    }
}

// XÓA MỘT SẢN PHẨM 
const xoaSanPham = async (index) => {
    const item = cart.value[index]
    cart.value.splice(index, 1) // Optimistic

    try {
        await api.delete(`/gio-hang/xoa/${item.id_giohang}`)
        hienThiThongBao('success', 'Đã xóa sản phẩm khỏi giỏ hàng.')
    } catch (err) {
        hienThiThongBao('error', 'Lỗi xóa sản phẩm!')
        fetchGioHang() // rollback
    }
}

//  XÓA TOÀN BỘ 
const xoaTatCa = async () => {
    if (!confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) return
    try {
        await api.delete('/gio-hang/xoa-tat')
        cart.value = []
        discount.value = 0
        hienThiThongBao('success', 'Đã xóa toàn bộ giỏ hàng.')
    } catch (err) {
        hienThiThongBao('error', 'Lỗi xóa giỏ hàng!')
    }
}

// MÃ GIẢM GIÁ (demo) 
const apDungMa = () => {
    if (coupon.value.toUpperCase() === 'SALE10') {
        discount.value = Math.round(subtotal.value * 0.1)
        hienThiThongBao('success', ' Áp dụng mã SALE10 – giảm 10%!')
    } else if (coupon.value.toUpperCase() === 'GIAMGIA') {
        discount.value = 3000000
        hienThiThongBao('success', ' Áp dụng mã thành công – giảm 3.000.000đ!')
    } else {
        hienThiThongBao('error', 'Mã giảm giá không hợp lệ.')
    }
}

const formatPrice = (price) => new Intl.NumberFormat('vi-VN').format(price) + 'đ'

onMounted(() => { fetchGioHang() })


</script>

<template>
    <Header />

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
                            <h3>{{ item.ten_san_pham }}</h3>
                            <p>{{ item.ten_bienthe }}</p>

                         
                            <div class="attr-tags" v-if="item.thuoc_tinh && item.thuoc_tinh.length">
                                <span v-for="attr in item.thuoc_tinh" :key="attr.ten_thuoctinh" class="attr-tag">
                                    {{ attr.ten_thuoctinh }}: {{ attr.giatri }}
                                </span>
                            </div>

                            <div class="qty">
                                <button @click="capNhatSoLuong(item, -1)" :disabled="item.soluong <= 1">−</button>
                                <span>{{ item.soluong }}</span>
                                <button @click="capNhatSoLuong(item, +1)"
                                    :disabled="item.soluong >= item.ton_kho">+</button>
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
                    <b class="free">Miễn phí</b>
                </div>

                <div class="coupon">
                    <input v-model="coupon" placeholder="Nhập mã giảm giá..." @keyup.enter="apDungMa" />
                    <button @click="apDungMa">Áp dụng</button>
                </div>

                <div class="total">
                    <span>Tổng cộng</span>
                    <h2>{{ formatPrice(total) }}</h2>
                </div>

                <router-link to="/checkout" class="checkout" :class="{ 'checkout-disabled': cart.length === 0 }">
                    <span>Thanh toán ngay</span>
                    <span class="icon">→</span>
                </router-link>

            </div>

        </div>

        <!-- ===== GỢI Ý ===== -->
        <div class="suggest container">
            <h3>Gợi ý cho bạn</h3>
            <div class="grid">
                <div class="card" v-for="(s, i) in suggest" :key="i">
                    <img :src="s.img" />
                    <p>{{ s.name }}</p>
                    <span>{{ s.price }}</span>
                </div>
            </div>
        </div>
    </div>

    <Footer />
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');


.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    color: white;
}

.toast.success {
    background: #16a34a;
}

.toast.error {
    background: #dc2626;
}

.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

/* ===== PAGE ===== */
.page {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    padding: 40px 0;
}

.container {
    width: min(1100px, 95%);
    margin: auto;
}

.cart {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

/* ===== LEFT ===== */
.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.cart-header h1 span {
    font-size: 16px;
    color: #64748b;
    font-weight: 400;
}

.btn-clear {
    background: #fee2e2;
    color: #dc2626;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
}

.btn-clear:hover {
    background: #fca5a5;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
    font-size: 15px;
}

.btn-shop {
    display: inline-block;
    margin-top: 14px;
    background: #2563eb;
    color: white;
    padding: 10px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
}

.item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border-radius: 18px;
    margin-bottom: 14px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
    transition: 0.3s;
}

.item:hover {
    transform: translateY(-5px);
}

.item img {
    width: 90px;
    height: 75px;
    object-fit: cover;
    border-radius: 12px;
}

.info {
    flex: 1;
}

.info h3 {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 4px;
}

.info p {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 4px;
}

.attr-tags {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}

.attr-tag {
    background: #e0e7ff;
    color: #4338ca;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 6px;
    font-weight: 500;
}

.qty {
    display: flex;
    gap: 0;
    align-items: center;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    width: fit-content;
    margin-top: 6px;
}

.qty button {
    width: 30px;
    height: 30px;
    border: none;
    background: #f1f5f9;
    font-size: 16px;
    cursor: pointer;
    transition: 0.2s;
}

.qty button:hover:not(:disabled) {
    background: #dbeafe;
    color: #2563eb;
}

.qty button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.qty span {
    min-width: 36px;
    text-align: center;
    font-weight: 600;
    font-size: 14px;
    border-left: 1px solid #e2e8f0;
    border-right: 1px solid #e2e8f0;
}

.price-col {
    text-align: right;
}

.price {
    font-weight: 700;
    color: #2563eb;
    font-size: 15px;
}

.unit-price {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 2px;
}

.remove {
    border: none;
    background: #fee2e2;
    color: #dc2626;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
    transition: 0.2s;
    flex-shrink: 0;
}

.remove:hover {
    background: #fca5a5;
}

/* ===== RIGHT ===== */
.right {
    padding: 24px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
    height: fit-content;
    position: sticky;
    top: 20px;
}

.right h3 {
    margin-bottom: 16px;
    font-size: 17px;
}

.row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
}

.discount {
    color: #dc2626;
}

.free {
    color: #16a34a;
}

.coupon {
    display: flex;
    margin: 16px 0;
}

.coupon input {
    flex: 1;
    padding: 10px 12px;
    border-radius: 10px 0 0 10px;
    border: 1px solid #ddd;
    font-size: 13px;
    outline: none;
}

.coupon button {
    padding: 10px 14px;
    border: none;
    background: #6366f1;
    color: white;
    border-radius: 0 10px 10px 0;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
}

.total {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 2px solid #e0e7ff;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total span {
    font-size: 15px;
    font-weight: 600;
}

.total h2 {
    color: #2563eb;
    font-size: 22px;
}

.checkout {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 16px;
    padding: 14px;
    border-radius: 12px;
    background: linear-gradient(90deg, #2563eb, #4f46e5);
    color: white;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    position: relative;
    transition: 0.2s;
}

.checkout .icon {
    position: absolute;
    right: 20px;
}

.checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
}

.checkout-disabled {
    pointer-events: none;
    opacity: 0.5;
}

/* ===== GỢI Ý ===== */
.suggest {
    margin-top: 50px;
}

.suggest h3 {
    margin-bottom: 16px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.card {
    background: rgba(255, 255, 255, 0.7);
    padding: 16px;
    border-radius: 16px;
    text-align: center;
}

.card img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 10px;
}

.card p {
    margin: 8px 0 4px;
    font-size: 14px;
}

.card span {
    color: #2563eb;
    font-weight: bold;
}

/* ===== ANIMATION ===== */
.fade-enter-active,
.fade-leave-active {
    transition: all 0.4s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

@media (max-width: 768px) {
    .cart {
        grid-template-columns: 1fr;
    }

    .right {
        position: static;
    }

    .grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>