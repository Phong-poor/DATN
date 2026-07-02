<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../../services/api'
import swal from '@/services/swal'
import { normalizeImageUrl } from '@/services/urls'
import { getToken, getUser } from '@/services/auth'


// ===================== STATE =====================
const cart = ref([])
const isLoading = ref(false)
const coupon = ref('')
const discount = ref(0)
const isAdminUser = computed(() => getUser()?.role === 'admin')

// ===================== SELECTION =====================
const selectedIds = ref(new Set())

const allItemIds = computed(() => {
    return cart.value.filter(i => !i.id_combo).map(i => i.id_giohang)
})

const isAllSelected = computed(() => {
    if (allItemIds.value.length === 0) return false
    return allItemIds.value.every(id => selectedIds.value.has(id))
})

const selectedCount = computed(() => selectedIds.value.size)

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = new Set()
    } else {
        selectedIds.value = new Set(allItemIds.value)
    }
}

const toggleItem = (id) => {
    const s = new Set(selectedIds.value)
    if (s.has(id)) s.delete(id)
    else s.add(id)
    selectedIds.value = s
}
const appliedPromo = ref(null)

const freeshipCoupon = ref('')
const freeshipDiscount = ref(0)
const appliedFreeshipPromo = ref(null)

// L?y di?u ki?n t?i thi?u t? promotion freeship được ch?n (d?ng computed)
const freeshipMinOrder = computed(() => {
    // N?u dang p m freeship th l?y dieu_kien c?a m d
    if (appliedFreeshipPromo.value && appliedFreeshipPromo.value.dieu_kien > 0) {
        return appliedFreeshipPromo.value.dieu_kien
    }
    // N?u dang ch?n m t? select (chua apply) th l?y dieu_kien c?a m d
    if (freeshipCoupon.value) {
        const p = freeshipPromosList.value.find(p => p.code === freeshipCoupon.value)
        if (p && p.dieu_kien > 0) return p.dieu_kien
    }
    // N?u chua ch?n m no, l?y dieu_kien nh? nh?t trong danh sch freeship
    const withCondition = freeshipPromosList.value.filter(p => p.dieu_kien > 0)
    if (withCondition.length > 0) {
        return Math.min(...withCondition.map(p => p.dieu_kien))
    }
    return 0 // Khng c di?u ki?n = mi?n ph ship t?t c? don
})

// L?y di?u ki?n ring cho t?ng m freeship (dng khi ch?n m c? th?)
const getFreeshipMinOrder = (promo) => {
    if (!promo) return 0
    return promo.dieu_kien > 0 ? promo.dieu_kien : 0
}

const shippingFee = computed(() => cart.value.length > 0 ? 30000 : 0)

const thongBao = ref({ show: false, type: '', message: '' })

const hienThiThongBao = (type, message) => {
    thongBao.value = { show: true, type, message }
    setTimeout(() => { thongBao.value.show = false }, 3000)
}

// ===================== GI? HNG =====================
const fetchGioHang = async () => {
    try {
        if (cart.value.length === 0) isLoading.value = true
        const res = await api.get('/gio-hang')
        cart.value = res.data.gio_hang
        localStorage.setItem('nextgen_cart_cache', JSON.stringify(cart.value))
    } catch (err) {
        console.error('Lỗi tải giỏ hàng:', err)
    } finally {
        isLoading.value = false
    }
}

const subtotal = computed(() => cart.value.reduce((sum, item) => sum + item.thanh_tien, 0))
const total = computed(() => Math.max(0, subtotal.value - discount.value) + Math.max(0, shippingFee.value - freeshipDiscount.value))

const groupedCart = computed(() => {
    const list = []
    const comboGroups = {}

    cart.value.forEach(item => {
        if (item.id_combo && item.id_nhom_combo) {
            if (!comboGroups[item.id_nhom_combo]) {
                comboGroups[item.id_nhom_combo] = {
                    isCombo: true,
                    id_nhom_combo: item.id_nhom_combo,
                    id_combo: item.id_combo,
                    ten_combo: item.ten_combo,
                    hinhanh_combo: item.hinhanh_combo,
                    gia_combo: item.gia_combo,
                    soluong: item.soluong,
                    ton_kho: item.ton_kho,
                    items: []
                }
                list.push(comboGroups[item.id_nhom_combo])
            }
            comboGroups[item.id_nhom_combo].items.push(item)
            if (item.ton_kho < comboGroups[item.id_nhom_combo].ton_kho) {
                comboGroups[item.id_nhom_combo].ton_kho = item.ton_kho
            }
        } else {
            list.push({
                isCombo: false,
                ...item
            })
        }
    })

    return list
})

const capNhatSoLuongCombo = async (group, delta) => {
    const soLuongMoi = group.soluong + delta
    if (soLuongMoi < 1) return
    if (soLuongMoi > group.ton_kho) {
        hienThiThongBao('error', `Kho chỉ còn ${group.ton_kho} combo.`)
        return
    }

    group.soluong = soLuongMoi
    
    // C?p nh?t s? lu?ng c?a t?ng mn trong cache gi? hng c?c b?
    cart.value.forEach(item => {
        if (item.id_nhom_combo === group.id_nhom_combo) {
            item.soluong = soLuongMoi
            item.thanh_tien = item.gia * soLuongMoi
        }
    })

    if (appliedPromo.value) tinhDiscount(appliedPromo.value)
    if (appliedFreeshipPromo.value) tinhFreeshipDiscount(appliedFreeshipPromo.value)

    try {
        await api.put(`/gio-hang/cap-nhat-combo/${group.id_nhom_combo}`, { soluong: soLuongMoi })
    } catch (err) {
        hienThiThongBao('error', err.response?.data?.message || 'L?i c?p nh?t s? lu?ng combo!')
        fetchGioHang()
    }
}

const deleteCombo = async (group) => {
    const isConfirmed = await swal.confirm('Xóa Combo', 'Bạn có chắc chắn muốn xóa combo này khỏi giỏ hàng?')
    if (!isConfirmed) return

    cart.value = cart.value.filter(item => item.id_nhom_combo !== group.id_nhom_combo)

    if (appliedPromo.value) tinhDiscount(appliedPromo.value)
    if (appliedFreeshipPromo.value) tinhFreeshipDiscount(appliedFreeshipPromo.value)

    try {
        await api.delete(`/gio-hang/xoa-combo/${group.id_nhom_combo}`)
        hienThiThongBao('success', 'Đã xóa combo khỏi giỏ hàng.')
        window.dispatchEvent(new Event('cart-updated'))
    } catch (err) {
        hienThiThongBao('error', 'Lỗi khi xóa combo!')
        fetchGioHang()
    }
}

const capNhatSoLuong = async (item, delta) => {
    const soLuongMoi = item.soluong + delta
    if (soLuongMoi < 1) return
    if (soLuongMoi > item.ton_kho) {
        hienThiThongBao('error', `Kho chỉ còn ${item.ton_kho} sản phẩm.`)
        return
    }

    // C?p nh?t state g?c trong cart.value d? kch ho?t tnh ton l?i subtotal/total
    const originalItem = cart.value.find(c => c.id_giohang === item.id_giohang)
    if (originalItem) {
        originalItem.soluong = soLuongMoi
        originalItem.thanh_tien = originalItem.gia * soLuongMoi
    }
    
    // C?p nh?t b?n sao (item) d? fallback
    item.soluong = soLuongMoi
    item.thanh_tien = item.gia * soLuongMoi

    // Tnh l?i discount n?u d p m
    if (appliedPromo.value) tinhDiscount(appliedPromo.value)
    if (appliedFreeshipPromo.value) tinhFreeshipDiscount(appliedFreeshipPromo.value)

    try {
        await api.put(`/gio-hang/cap-nhat/${item.id_giohang}`, { soluong: soLuongMoi })
    } catch (err) {
        hienThiThongBao('error', err.response?.data?.message || 'L?i c?p nh?t s? lu?ng!')
        fetchGioHang()
    }
}

const xoaSanPham = async (idGioHang) => {
    const index = cart.value.findIndex(item => item.id_giohang === idGioHang)
    if (index === -1) return
    const item = cart.value[index]
    cart.value.splice(index, 1)
    selectedIds.value.delete(idGioHang)

    // Tính lại discount sau khi xóa
    if (appliedPromo.value) tinhDiscount(appliedPromo.value)
    if (appliedFreeshipPromo.value) tinhFreeshipDiscount(appliedFreeshipPromo.value)

    try {
        await api.delete(`/gio-hang/xoa/${item.id_giohang}`)
        hienThiThongBao('success', 'Đã xóa sản phẩm khỏi giỏ hàng.')
        window.dispatchEvent(new Event('cart-updated'))
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
        selectedIds.value = new Set()
        discount.value = 0
        appliedPromo.value = null
        coupon.value = ''
        freeshipCoupon.value = ''
        freeshipDiscount.value = 0
        appliedFreeshipPromo.value = null
        hienThiThongBao('success', 'Đã xóa toàn bộ giỏ hàng.')
        window.dispatchEvent(new Event('cart-updated'))
    } catch (err) {
        hienThiThongBao('error', 'Lỗi xóa giỏ hàng!')
    }
}

const xoaDaChon = async () => {
    if (selectedIds.value.size === 0) return
    const isConfirmed = await swal.confirm('Xóa sản phẩm đã chọn', `Bạn có chắc chắn muốn xóa ${selectedIds.value.size} sản phẩm đã chọn?`)
    if (!isConfirmed) return

    const ids = [...selectedIds.value]
    for (const id of ids) {
        await xoaSanPham(id)
    }
    selectedIds.value = new Set()
}

// ===================== M GI?M GI =====================
const allPromos = ref([])

const fetchPromotions = async () => {
    try {
        const token = getToken()
        if (token) {
            // Lấy danh sách voucher người dùng đang sở hữu
            const res = await api.get('/user/vouchers')
            // res.data.vouchers chứa danh sách { id, promotion: { ... } }
            if (res.data && res.data.vouchers) {
                allPromos.value = res.data.vouchers.map(v => v.promotion).filter(Boolean)
            }
        } else {
            // Nếu là guest thì lấy danh sách public
            const res = await api.get('/promotions')
            allPromos.value = res.data
        }
    } catch (err) {
        console.error('Lỗi tải khuyến mãi:', err)
    }
}

// Tnh s? ti?n gi?m d?a vo promo object
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
    const minOrder = getFreeshipMinOrder(promo)
    // Ki?m tra l?i di?u ki?n khi tnh l?i (v d? sau khi xa s?n ph?m)
    if (minOrder > 0 && subtotal.value < minOrder) {
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

const discountPromosList = computed(() => validPromosList.value.filter(p => {
    if (p.category !== 'product') return false
    if (p.dieu_kien > 0) {
        const dk = Number(p.dieu_kien)
        if (p.loai_dieu_kien === '>=' && subtotal.value < dk) return false
        if (p.loai_dieu_kien === '>' && subtotal.value <= dk) return false
        if (p.loai_dieu_kien === '=' && subtotal.value !== dk) return false
    }
    return true
}))

const freeshipPromosList = computed(() => validPromosList.value.filter(p => {
    if (p.category !== 'freeship') return false
    const minOrder = p.dieu_kien > 0 ? p.dieu_kien : 0
    if (minOrder > 0 && subtotal.value < minOrder) return false
    return true
}))

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
    const promo = freeshipPromosList.value.find(p => p.code === freeshipCoupon.value)
    if (!promo) return
    const minOrder = getFreeshipMinOrder(promo)
    // Ki?m tra di?u ki?n don hng t?i thi?u c?a m ny
    if (minOrder > 0 && subtotal.value < minOrder) {
        freeshipCoupon.value = ''
        hienThiThongBao('error', `Cần mua tối thiểu ${formatPrice(minOrder)} để dùng mã miễn phí vận chuyển này!`)
        return
    }
    appliedFreeshipPromo.value = promo
    tinhFreeshipDiscount(promo)
    hienThiThongBao('success', `Đã chọn mã freeship ${promo.code}`)
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
                   hienThiThongBao('success', `Tự động áp dụng mã ${bestP.code} tốt nhất!`)
               }
           }
        } else if (coupon.value === '') {
            appliedPromo.value = bestP
            tinhDiscount(bestP)
            coupon.value = bestP.code
        }
    }

    // 2. FREESHIP DISCOUNT (p d?ng d?a trn dieu_kien c?a t?ng m)
    let bestF = null
    let maxDF = 0
    freeshipPromosList.value.forEach(p => {
        const minOrder = getFreeshipMinOrder(p)
        if (minOrder > 0 && sub < minOrder) return // Chua d? di?u ki?n
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
               hienThiThongBao('success', `Tự động chọn mã freeship tốt nhất!`)
           }
        } else if (freeshipCoupon.value === '') {
            appliedFreeshipPromo.value = bestF
            tinhFreeshipDiscount(bestF)
            freeshipCoupon.value = bestF.code
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

const formatPrice = (price) => new Intl.NumberFormat('vi-VN').format(price) + 'd'

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
    } catch (e) { console.error('L?i parse thong_so_ky_thuat:', e) }
    
    return specs.length > 0 ? `${name} ${specs.join(' ')}` : name
}

onMounted(() => { 
    window.scrollTo(0, 0)
    try {
        const cached = localStorage.getItem('nextgen_cart_cache')
        if (cached) {
            cart.value = JSON.parse(cached)
        }
    } catch (e) {}
    
    fetchGioHang()
    fetchPromotions()
})
</script>

<template>
  <div class="cart-root">
    <!-- ===== TOAST NOTIFICATION ===== -->
    <transition name="toast-slide">
      <div v-if="thongBao.show" :class="['premium-toast', thongBao.type]">
        <span class="toast-icon">{{ thongBao.type === 'success' ? '✓' : '⚠' }}</span>
        {{ thongBao.message }}
      </div>
    </transition>

    <div class="cart-page">
    <div class="cart-wrap">

      <!-- ===== LEFT: CART ITEMS ===== -->
      <div class="cart-left">

        <!-- PAGE HEADER -->
        <div class="cart-page-header">
          <div class="header-top-row">
            <div class="header-title-area">
              <div class="header-eyebrow">🎯 NextGen Laptop Store</div>
              <h1 class="header-title">Giỏ hàng của bạn
                <span class="item-count-badge">{{ cart.length }} sản phẩm</span>
              </h1>
              <p class="header-sub">Kiểm tra sản phẩm trước khi thanh toán</p>
            </div>
            <div class="header-actions">
              <router-link to="/san-pham" class="btn-continue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Tiếp tục mua sắm
              </router-link>
              <button v-if="cart.length > 0" class="btn-clear-all" @click="xoaTatCa">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                Xóa tất cả
              </button>
            </div>
          </div>

          <!-- SELECTION TOOLBAR -->
          <div class="selection-bar" v-if="cart.length > 0">
            <label class="select-all-wrap">
              <input
                type="checkbox"
                class="item-checkbox"
                :checked="isAllSelected"
                @change="toggleSelectAll"
              />
              <span>Chọn tất cả ({{ allItemIds.length }})</span>
            </label>
            <transition name="fade">
              <div class="selection-actions" v-if="selectedCount > 0">
                <span class="selected-badge">{{ selectedCount }} đã chọn</span>
                <button class="btn-delete-selected" @click="xoaDaChon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                  Xóa đã chọn
                </button>
              </div>
            </transition>
          </div>
        </div>

        <!-- LOADING SKELETONS -->
        <div v-if="isLoading" class="skeleton-list">
          <div v-for="n in 3" :key="n" class="skeleton-item">
            <div class="sk-img"></div>
            <div class="sk-body">
              <div class="sk-line long"></div>
              <div class="sk-line med"></div>
              <div class="sk-chips">
                <div class="sk-chip"></div><div class="sk-chip"></div><div class="sk-chip"></div>
              </div>
              <div class="sk-line short"></div>
            </div>
            <div class="sk-price">
              <div class="sk-line med"></div>
              <div class="sk-line short"></div>
            </div>
          </div>
        </div>

        <!-- EMPTY STATE -->
        <div v-else-if="cart.length === 0" class="empty-cart">
          <div class="empty-icon-wrap">
            <div class="empty-icon-ring">
              <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 8h4l4 28h28l4-20H16" stroke="url(#bagGrad)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="24" cy="44" r="3" fill="url(#bagGrad)"/>
                <circle cx="40" cy="44" r="3" fill="url(#bagGrad)"/>
                <defs>
                  <linearGradient id="bagGrad" x1="8" y1="8" x2="48" y2="48" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#818cf8"/><stop offset="1" stop-color="#6366f1"/>
                  </linearGradient>
                </defs>
              </svg>
            </div>
          </div>
          <h2 class="empty-title">Giỏ hàng đang trống</h2>
          <p class="empty-sub">Khám phá những mẫu laptop mới nhất tại NextGen và thêm sản phẩm bạn yêu thích vào giỏ hàng.</p>
          <router-link to="/san-pham" class="empty-cta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            Tiếp tục mua sắm
          </router-link>
        </div>

        <!-- CART ITEMS LIST -->
        <transition-group v-else name="item-anim" tag="div" class="items-list">
          <div v-for="(entry, index) in groupedCart" :key="entry.isCombo ? entry.id_nhom_combo : entry.id_giohang">

            <!-- Standalone item -->
            <div
              class="cart-item-card"
              :class="{ 'is-selected': selectedIds.has(entry.id_giohang) }"
              v-if="!entry.isCombo"
            >
              <!-- CHECKBOX -->
              <label class="card-checkbox-wrap" @click.stop>
                <input
                  type="checkbox"
                  class="item-checkbox"
                  :checked="selectedIds.has(entry.id_giohang)"
                  @change="toggleItem(entry.id_giohang)"
                />
              </label>

              <!-- IMAGE BOX -->
              <div class="item-image-box">
                <div class="stock-dot" :class="entry.ton_kho > 0 ? 'in' : 'out'">
                  {{ entry.ton_kho > 0 ? 'Còn hàng' : 'Hết hàng' }}
                </div>
                <img
                  :src="normalizeImageUrl(entry.hinh_anh, 'https://via.placeholder.com/90')"
                  :alt="entry.ten_san_pham"
                  class="item-img"
                  @error="e => e.target.src = 'https://via.placeholder.com/90'"
                />
              </div>

              <!-- ITEM INFO -->
              <div class="item-info">
                <div class="item-brand">{{ entry.ten_thuonghieu || 'NextGen' }}</div>
                <h3 class="item-name">{{ getFullProductName(entry) }}</h3>

                <!-- ATTRIBUTE CHIPS -->
                <div class="attr-chips" v-if="entry.thuoc_tinh && entry.thuoc_tinh.length">
                  <span v-for="attr in entry.thuoc_tinh" :key="attr.ten_thuoctinh" class="attr-chip">
                    <span class="chip-key">{{ attr.ten_thuoctinh }}</span>
                    <span class="chip-val">{{ attr.giatri }}</span>
                  </span>
                </div>

                <!-- QUANTITY SELECTOR -->
                <div class="qty-selector">
                  <span class="qty-label">Số lượng</span>
                  <div class="qty-controls">
                    <button class="qty-btn" @click="capNhatSoLuong(entry, -1)" :disabled="entry.soluong <= 1">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <span class="qty-num">{{ entry.soluong }}</span>
                    <button class="qty-btn" @click="capNhatSoLuong(entry, +1)" :disabled="entry.soluong >= entry.ton_kho">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- PRICE + REMOVE -->
              <div class="item-right">
                <div class="item-price-wrap">
                  <div class="item-total-price">{{ formatPrice(entry.thanh_tien) }}</div>
                  <div class="item-unit-price">{{ formatPrice(entry.gia) }} / sp</div>
                </div>
                <button class="btn-remove" @click="xoaSanPham(entry.id_giohang)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                  Xóa
                </button>
              </div>

            </div>

            <!-- Combo grouped items -->
            <div class="combo-item-group" :class="{ 'combo-gift-group': entry.gia_combo === 0 }" v-else>
              <!-- Banner qu t?ng VIP (ch? hi?n khi l uu di mi?n ph) -->
              <div v-if="entry.gia_combo === 0" class="gift-offer-banner">
                <span class="gift-offer-icon">🎁</span>
                <div class="gift-offer-text">
                  <strong>Quà Tặng Đặc Quyền VIP</strong>
                  <span>Miễn phí hoàn toàn - Kèm theo đơn hàng của bạn</span>
                </div>
                <span class="gift-free-badge">0d</span>
              </div>

              <div class="combo-group-header">
                <div class="title-box">
                  <span class="badge-tag" :class="{ 'badge-tag-gift': entry.gia_combo === 0 }">
                    {{ entry.gia_combo === 0 ? 'Quà tặng VIP' : 'Combo' }}
                  </span>
                  <h3>{{ entry.ten_combo }}</h3>
                </div>
                <button class="combo-remove-btn" @click="deleteCombo(entry)">×</button>
              </div>
              <div class="combo-child-list">
                <div class="child-item" v-for="child in entry.items" :key="child.id_giohang">
                  <img :src="normalizeImageUrl(child.hinh_anh, 'https://via.placeholder.com/90')" />
                  <div class="child-info">
                    <h4>{{ getFullProductName(child) }}</h4>
                    <p>{{ child.ten_bienthe }}</p>
                    <div class="attr-tags" v-if="child.thuoc_tinh && child.thuoc_tinh.length">
                      <span v-for="attr in child.thuoc_tinh" :key="attr.ten_thuoctinh" class="attr-tag">
                        {{ attr.ten_thuoctinh }}: {{ attr.giatri }}
                      </span>
                    </div>
                  </div>
                  <div class="child-price">
                    <span class="original-price">{{ formatPrice(child.gia_goc) }}</span>
                    <span v-if="entry.gia_combo === 0" class="allocated-price free-price-text">Miễn phí</span>
                    <span v-else class="allocated-price">{{ formatPrice(child.gia) }}</span>
                  </div>
                </div>
              </div>
              <div class="combo-group-footer">
                <div class="qty-section">
                  <span>Số lượng:</span>
                  <div class="qty">
                    <button @click="capNhatSoLuongCombo(entry, -1)" :disabled="entry.soluong <= 1">-</button>
                    <span>{{ entry.soluong }}</span>
                    <button @click="capNhatSoLuongCombo(entry, +1)" :disabled="entry.soluong >= entry.ton_kho">+</button>
                  </div>
                </div>
                <div class="total-section">
                  <span class="lbl">Trọn bộ:</span>
                  <span v-if="entry.gia_combo === 0" class="price-val free-combo-price">MIỄN PHÍ</span>
                  <span v-else class="price-val">{{ formatPrice(entry.gia_combo * entry.soluong) }}</span>
                </div>
              </div>
            </div>

          </div>
        </transition-group>

      </div>

      <!-- ===== RIGHT: ORDER SUMMARY ===== -->
      <div class="cart-right" v-if="!isLoading">
        <div class="summary-card">
          <div class="summary-header">
            <h2 class="summary-title">Tóm tắt đơn hàng</h2>
            <span class="summary-count">{{ cart.length }} sản phẩm</span>
          </div>

          <!-- PRICE ROWS -->
          <div class="summary-rows">
            <div class="summary-row">
              <span>Tạm tính</span>
              <span class="sum-val">{{ formatPrice(subtotal) }}</span>
            </div>
            <div class="summary-row" v-if="discount > 0">
              <span>Giảm giá <span class="promo-badge">{{ appliedPromo?.code }}</span></span>
              <span class="sum-val discount">-{{ formatPrice(discount) }}</span>
            </div>
            <div class="summary-row">
              <span>Phí vận chuyển</span>
              <span class="sum-val">{{ formatPrice(shippingFee) }}</span>
            </div>
            <div class="summary-row" v-if="freeshipDiscount > 0">
              <span class="freeship-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Freeship áp dụng
              </span>
              <span class="sum-val discount">-{{ formatPrice(freeshipDiscount) }}</span>
            </div>
          </div>

          <!-- FREESHIP PROGRESS -->
          <div class="freeship-progress-box" v-if="freeshipPromosList.length > 0">
            <div class="freeship-header-row">
              <span class="freeship-icon" :class="{ met: subtotal >= freeshipMinOrder }">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
              </span>
              <span class="freeship-text" v-if="subtotal >= freeshipMinOrder">
                Đủ điều kiện miễn phí vận chuyển!
              </span>
              <span class="freeship-text pending" v-else>
                Mua thêm <strong>{{ formatPrice(freeshipMinOrder - subtotal) }}</strong> để freeship
              </span>
            </div>
            <div class="freeship-bar-bg">
              <div class="freeship-bar-fill" :style="{ width: Math.min(100, (subtotal / freeshipMinOrder) * 100) + '%' }"></div>
            </div>
            <p class="freeship-note" v-if="subtotal >= freeshipMinOrder">
              Đơn hàng của bạn được miễn phí vận chuyển nội thành
            </p>
          </div>

          <!-- COUPON SELECTORS -->
          <div class="coupon-section">
            <div class="coupon-label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
              Mã giảm giá
            </div>
            <select v-model="coupon" @change="apDungMaTuSelect" class="coupon-select">
              <option value="">Không dùng mã</option>
              <option v-for="p in discountPromosList" :key="p.code" :value="p.code">
                {{ p.name }} - {{ p.type === 'percent' ? `Giảm ${p.value}%` : `Giảm ${formatPrice(p.value)}` }}
              </option>
            </select>
          </div>

          <div class="coupon-section" v-if="freeshipPromosList.length > 0">
            <div class="coupon-label green">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
              Mã miễn phí vận chuyển
            </div>
            <select
              v-model="freeshipCoupon"
              @change="apDungFreeshipTuSelect"
              class="coupon-select green"
              :disabled="subtotal < freeshipMinOrder"
            >
              <option value="">Không dùng mã freeship</option>
              <option v-for="p in freeshipPromosList" :key="p.code" :value="p.code">
                {{ p.name }} - Giảm 100% phí ship
              </option>
            </select>
          </div>

          <!-- TOTAL -->
          <div class="summary-total-row">
            <span class="total-label">Tổng cộng</span>
            <div class="total-amount">{{ formatPrice(total) }}</div>
          </div>

          <!-- CHECKOUT BUTTON -->
          <div v-if="isAdminUser" class="admin-shopping-lock">
            Tài khoản quản trị viên chỉ dùng để quản lý hệ thống, không được mua sắm hàng hóa.
          </div>

          <router-link
            v-else
            :to="{ path: '/thanh-toan', query: { 
              promo_code: appliedPromo ? appliedPromo.code : '', 
              discount: discount,
              freeship_code: appliedFreeshipPromo ? appliedFreeshipPromo.code : '',
              freeship_discount: freeshipDiscount
            }}"
            class="checkout-btn"
            :class="{ 'checkout-disabled': cart.length === 0 }"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Thanh toán ngay
            <svg class="arrow-right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
          </router-link>

          <!-- PAYMENT ICONS -->
          <div class="payment-methods">
            <span class="pay-method visa">VISA</span>
            <span class="pay-method mc">MC</span>
            <span class="pay-method momo">MoMo</span>
            <span class="pay-method vnpay">VNPay</span>
            <span class="pay-method cod">COD</span>
          </div>

          <!-- TRUST BADGES -->
          <div class="trust-grid">
            <div class="trust-badge">
              <div class="trust-icon shield">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              </div>
              <span>Bảo mật SSL</span>
            </div>
            <div class="trust-badge">
              <div class="trust-icon check">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              </div>
              <span>Bảo hành chính hãng</span>
            </div>
            <div class="trust-badge">
              <div class="trust-icon refresh">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>
              </div>
              <span>Đổi trả 7 ngày</span>
            </div>
            <div class="trust-badge">
              <div class="trust-icon credit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
              </div>
              <span>Hỗ trợ trả góp</span>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>
</template>

<style scoped>

@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ==================== CSS VARIABLES ==================== */
.cart-page {
  --primary: #6366f1;
  --primary-dark: #4f46e5;
  --primary-light: #eef2ff;
  --primary-glow: rgba(99, 102, 241, 0.2);
  --success: #22c55e;
  --danger: #ef4444;
  --text-1: #0f172a;
  --text-2: #475569;
  --text-3: #94a3b8;
  --border: #e2e8f0;
  --card: #ffffff;
  --font-display: 'Outfit', sans-serif;
  --font-body: 'Inter', 'Outfit', sans-serif;
  --radius: 20px;
  --tr: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);

  font-family: var(--font-body);
  background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 50%, #eef2ff 100%);
  min-height: 100vh;
  padding: 36px 20px 80px;
  color: var(--text-1);
}

.cart-wrap {
  max-width: 1280px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 28px;
  align-items: start;
}

/* ==================== TOAST ==================== */
.premium-toast {
  position: fixed;
  top: 22px;
  right: 22px;
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 22px;
  border-radius: 14px;
  font-family: var(--font-body);
  font-size: 14px;
  font-weight: 600;
  color: white;
  box-shadow: 0 12px 32px rgba(0,0,0,0.15);
  backdrop-filter: blur(10px);
}
.premium-toast.success { background: linear-gradient(135deg, #16a34a, #15803d); }
.premium-toast.error   { background: linear-gradient(135deg, #dc2626, #b91c1c); }
.toast-icon {
  width: 22px; height: 22px; border-radius: 50%;
  background: rgba(255,255,255,0.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; flex-shrink: 0;
}
.toast-slide-enter-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-slide-leave-active { transition: all 0.25s ease; }
.toast-slide-enter-from  { opacity: 0; transform: translateX(60px) scale(0.9); }
.toast-slide-leave-to    { opacity: 0; transform: translateX(60px); }

/* ==================== LEFT COLUMN ==================== */
.cart-left { display: flex; flex-direction: column; gap: 20px; }

/* PAGE HEADER */
.cart-page-header {
  background: var(--card);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  padding: 24px 28px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}
.header-top-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 16px;
}
.header-eyebrow {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--primary);
  margin-bottom: 6px;
}
.header-title {
  font-family: var(--font-display);
  font-size: 30px;
  font-weight: 800;
  letter-spacing: -1px;
  color: var(--text-1);
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.item-count-badge {
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 700;
  background: var(--primary-light);
  color: var(--primary);
  padding: 4px 12px;
  border-radius: 20px;
  letter-spacing: 0;
}
.header-sub {
  font-size: 13px;
  color: var(--text-3);
  margin-top: 4px;
  font-weight: 400;
}
.header-actions { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }

.btn-continue {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 16px;
  border-radius: 12px;
  border: 1.5px solid var(--border);
  background: var(--card);
  color: var(--text-2);
  font-family: var(--font-display);
  font-size: 12.5px;
  font-weight: 600;
  text-decoration: none;
  transition: var(--tr);
}
.btn-continue svg { width: 14px; height: 14px; }
.btn-continue:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }

.btn-clear-all {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 16px;
  border-radius: 12px;
  border: none;
  background: #fef2f2;
  color: var(--danger);
  font-family: var(--font-display);
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--tr);
}
.btn-clear-all svg { width: 13px; height: 13px; }
.btn-clear-all:hover { background: #fee2e2; }

/* SELECTION BAR */
.selection-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid var(--border);
  flex-wrap: wrap;
}
.select-all-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-2);
  user-select: none;
}
.select-all-wrap:hover { color: var(--primary); }
.selection-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-left: auto;
}
.selected-badge {
  font-size: 11.5px;
  font-weight: 700;
  background: var(--primary-light);
  color: var(--primary);
  padding: 3px 10px;
  border-radius: 20px;
}
.btn-delete-selected {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 13px;
  border-radius: 10px;
  border: none;
  background: #fef2f2;
  color: var(--danger);
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: var(--tr);
}
.btn-delete-selected svg { width: 12px; height: 12px; }
.btn-delete-selected:hover { background: #fee2e2; }

/* Fade transition */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s, transform 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateX(10px); }

/* Custom checkbox */
.item-checkbox {
  width: 17px;
  height: 17px;
  accent-color: var(--primary);
  cursor: pointer;
  flex-shrink: 0;
}

/* SKELETON */
.skeleton-list { display: flex; flex-direction: column; gap: 14px; }
.skeleton-item {
  display: flex;
  gap: 18px;
  background: var(--card);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  padding: 20px;
  align-items: flex-start;
}
.sk-img { width: 90px; height: 90px; border-radius: 12px; background: linear-gradient(90deg, #f1f5f9 25%, #e8edf5 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; flex-shrink: 0; }
.sk-body { flex: 1; display: flex; flex-direction: column; gap: 10px; }
.sk-chips { display: flex; gap: 6px; }
.sk-chip { height: 22px; width: 80px; border-radius: 8px; background: linear-gradient(90deg, #f1f5f9 25%, #e8edf5 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.sk-price { display: flex; flex-direction: column; gap: 8px; width: 100px; align-items: flex-end; }
.sk-line { height: 12px; border-radius: 6px; background: linear-gradient(90deg, #f1f5f9 25%, #e8edf5 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.sk-line.long { width: 100%; } .sk-line.med { width: 65%; } .sk-line.short { width: 40%; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* EMPTY STATE */
.empty-cart {
  background: var(--card);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  padding: 80px 40px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}
.empty-icon-wrap { margin-bottom: 24px; }
.empty-icon-ring {
  width: 110px; height: 110px; border-radius: 50%;
  background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto; padding: 26px;
}
.empty-icon-ring svg { width: 100%; height: 100%; }
.empty-title { font-family: var(--font-display); font-size: 24px; font-weight: 800; color: var(--text-1); margin-bottom: 10px; letter-spacing: -0.5px; }
.empty-sub { font-size: 14px; color: var(--text-3); line-height: 1.65; max-width: 380px; margin: 0 auto 28px; }
.empty-cta {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 12px 28px; border-radius: 14px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: white; font-family: var(--font-display); font-size: 14px; font-weight: 700;
  text-decoration: none; box-shadow: 0 6px 18px var(--primary-glow); transition: var(--tr);
}
.empty-cta svg { width: 16px; height: 16px; }
.empty-cta:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(99, 102, 241, 0.35); }

/* ITEMS LIST */
.items-list { display: flex; flex-direction: column; gap: 12px; }

/* CART ITEM CARD */
.cart-item-card {
  background: var(--card);
  border-radius: 14px;
  border: 1.5px solid var(--border);
  padding: 12px 14px;
  display: flex;
  gap: 12px;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.04);
  transition: var(--tr);
  position: relative;
}
.cart-item-card.is-selected {
  border-color: var(--primary);
  background: linear-gradient(135deg, #f5f3ff 0%, #eef2ff 100%);
  box-shadow: 0 4px 16px rgba(99,102,241,0.12);
}
.cart-item-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.1);
  border-color: #c7d2fe;
}
.cart-item-card.is-selected:hover { border-color: var(--primary-dark); }

/* Checkbox inside card */
.card-checkbox-wrap {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  cursor: pointer;
}

/* IMAGE BOX */
.item-image-box {
  width: 90px;
  height: 90px;
  flex-shrink: 0;
  background: linear-gradient(145deg, #f8fafc 0%, #eef2ff 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  position: relative;
  overflow: hidden;
}
.item-img {
  max-width: 100%;
  max-height: 72px;
  object-fit: contain;
  transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
  filter: drop-shadow(0 4px 8px rgba(0,0,0,0.08));
}
.cart-item-card:hover .item-img { transform: scale(1.06); }

.stock-dot {
  position: absolute;
  top: 6px;
  left: 6px;
  font-size: 9px;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 20px;
  color: white;
  z-index: 2;
}
.stock-dot.in  { background: var(--success); }
.stock-dot.out { background: var(--danger); }

/* ITEM INFO */
.item-info { flex: 1; min-width: 0; }

.item-brand {
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 1.4px;
  text-transform: uppercase;
  color: var(--primary);
  margin-bottom: 4px;
}
.item-name {
  font-family: var(--font-display);
  font-size: 13px;
  font-weight: 700;
  color: var(--text-1);
  line-height: 1.4;
  margin-bottom: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* ATTRIBUTE CHIPS */
.attr-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 8px; }
.attr-chip {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  background: var(--tn-surface-2);
  border: 1px solid var(--tn-border);
  border-radius: 6px;
  padding: 2px 7px;
  font-size: 9.5px;
}
.chip-key { color: #64748b; font-weight: 500; }
.chip-val { color: #1e293b; font-weight: 700; }

/* QUANTITY SELECTOR */
.qty-selector { display: flex; align-items: center; gap: 8px; }
.qty-label { font-size: 11px; font-weight: 600; color: var(--text-2); }
.qty-controls {
  display: flex;
  align-items: center;
  gap: 0;
  border: 1.5px solid #c7d2fe;
  border-radius: 10px;
  overflow: hidden;
  background: var(--tn-bg);
}
.qty-btn {
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6366f1;
  transition: var(--tr);
}
.qty-btn svg { width: 12px; height: 12px; }
.qty-btn:hover:not(:disabled) { background: #eef2ff; color: var(--primary-dark); }
.qty-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.qty-num {
  min-width: 30px;
  text-align: center;
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
  border-left: 1px solid #c7d2fe;
  border-right: 1px solid #c7d2fe;
  padding: 0 4px;
  line-height: 28px;
}


.item-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  flex-shrink: 0;
  min-width: 120px;
}
.item-price-wrap { text-align: right; }
.item-total-price {
  font-family: var(--font-display);
  font-size: 18px;
  font-weight: 800;
  color: var(--primary);
  letter-spacing: -0.3px;
}
.item-unit-price { font-size: 11px; color: var(--text-3); margin-top: 2px; font-weight: 500; }

.btn-remove {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 12px;
  border-radius: 10px;
  border: 1.5px solid var(--border);
  background: transparent;
  color: var(--text-3);
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--tr);
}
.btn-remove svg { width: 12px; height: 12px; }
.btn-remove:hover { border-color: var(--danger); background: #fef2f2; color: var(--danger); }

/* ITEM TRANSITION */
.item-anim-enter-active { transition: all 0.4s cubic-bezier(0.4,0,0.2,1); }
.item-anim-leave-active { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
.item-anim-enter-from  { opacity: 0; transform: translateY(16px) scale(0.97); }
.item-anim-leave-to    { opacity: 0; transform: translateX(30px) scale(0.95); }

/* ==================== RIGHT: SUMMARY ==================== */
.cart-right { position: sticky; top: 20px; }

.summary-card {
  background: var(--card);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  padding: 24px;
  box-shadow: 0 8px 28px rgba(99, 102, 241, 0.06);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.summary-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--border);
}
.summary-title {
  font-family: var(--font-display);
  font-size: 17px;
  font-weight: 800;
  color: var(--text-1);
  letter-spacing: -0.3px;
}
.summary-count {
  font-size: 11px;
  font-weight: 700;
  color: var(--primary);
  background: var(--primary-light);
  padding: 3px 10px;
  border-radius: 20px;
}

/* PRICE ROWS */
.summary-rows { display: flex; flex-direction: column; gap: 10px; }
.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  color: var(--text-2);
  font-weight: 500;
}
.sum-val { font-weight: 700; color: var(--text-1); }
.sum-val.discount { color: var(--success); }
.promo-badge {
  background: #dcfce7;
  color: #16a34a;
  font-size: 9px;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 6px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  margin-left: 4px;
}
.freeship-label {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #16a34a;
  font-weight: 600;
}
.freeship-label svg { width: 12px; height: 12px; }

/* FREESHIP PROGRESS */
.freeship-progress-box {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border: 1px solid #bbf7d0;
  border-radius: 14px;
  padding: 14px;
}
.freeship-header-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.freeship-icon {
  width: 28px; height: 28px; border-radius: 8px;
  background: #d1fae5; color: #15803d;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.freeship-icon.met { background: #15803d; color: white; }
.freeship-icon svg { width: 14px; height: 14px; }
.freeship-text { font-size: 12px; font-weight: 600; color: #166534; }
.freeship-text.pending { color: #92400e; }
.freeship-text strong { color: #b45309; }
.freeship-bar-bg {
  height: 6px; background: #bbf7d0; border-radius: 10px; overflow: hidden;
}
.freeship-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #22c55e, #16a34a);
  border-radius: 10px;
  transition: width 0.5s ease;
}
.freeship-note { font-size: 10.5px; color: #166534; margin-top: 7px; font-weight: 500; }

/* COUPON */
.coupon-section { display: flex; flex-direction: column; gap: 6px; }
.coupon-label {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11.5px;
  font-weight: 700;
  color: var(--text-2);
  letter-spacing: 0.3px;
}
.coupon-label svg { width: 12px; height: 12px; }
.coupon-label.green { color: #16a34a; }

.coupon-select {
  width: 100%;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1.5px solid var(--border);
  font-family: var(--font-body);
  font-size: 12.5px;
  font-weight: 500;
  color: var(--text-1);
  background: #fff;
  cursor: pointer;
  outline: none;
  transition: var(--tr);
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 16px;
  padding-right: 32px;
}
.coupon-select:focus { border-color: var(--primary); background-color: #fafafa; box-shadow: 0 0 0 3px var(--primary-glow); }
.coupon-select.green { border-color: #86efac; background-color: #f0fdf4; color: #166534; }
.coupon-select.green:disabled { opacity: 0.5; cursor: not-allowed; }

/* TOTAL */
.summary-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
  border-top: 2px solid #e0e7ff;
  border-bottom: 1px solid var(--border);
}
.total-label { font-family: var(--font-display); font-size: 14px; font-weight: 700; color: var(--text-1); }
.total-amount {
  font-family: var(--font-display);
  font-size: 24px;
  font-weight: 800;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.5px;
}

/* CHECKOUT BUTTON */
.checkout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 15px;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: white;
  font-family: var(--font-display);
  font-size: 15px;
  font-weight: 800;
  text-decoration: none;
  box-shadow: 0 6px 20px var(--primary-glow);
  transition: var(--tr);
  position: relative;
  letter-spacing: 0.2px;
}
.checkout-btn svg { width: 18px; height: 18px; }
.arrow-right { margin-left: auto; }
.checkout-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4); }
.checkout-disabled { pointer-events: none; opacity: 0.45; }

.admin-shopping-lock {
  border: 1px solid #fecaca;
  border-radius: 14px;
  padding: 14px 16px;
  background: #fef2f2;
  color: #b91c1c;
  font-weight: 800;
  line-height: 1.5;
  text-align: center;
}

/* PAYMENT METHODS */
.payment-methods {
  display: flex;
  gap: 6px;
  justify-content: center;
  flex-wrap: wrap;
}
.pay-method {
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.5px;
  border: 1.5px solid var(--border);
  color: var(--text-2);
  background: #fff;
}
.pay-method.visa   { border-color: #1a1f71; color: #1a1f71; }
.pay-method.mc     { border-color: #eb001b; color: #eb001b; }
.pay-method.momo   { border-color: #a50064; color: #a50064; }
.pay-method.vnpay  { border-color: #0066b3; color: #0066b3; }
.pay-method.cod    { border-color: var(--success); color: #16a34a; }

/* TRUST BADGES */
.trust-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.trust-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  color: var(--text-2);
}
.trust-icon {
  width: 28px; height: 28px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.trust-icon svg { width: 13px; height: 13px; }
.trust-icon.shield { background: #ede9fe; color: #7c3aed; }
.trust-icon.check  { background: #dcfce7; color: #16a34a; }
.trust-icon.refresh{ background: #fef3c7; color: #d97706; }
.trust-icon.credit { background: #dbeafe; color: #2563eb; }

/* ==================== RESPONSIVE ==================== */
@media (max-width: 1100px) {
  .cart-wrap { grid-template-columns: 1fr 360px; }
}
@media (max-width: 900px) {
  .cart-wrap { grid-template-columns: 1fr; }
  .cart-right { position: static; }
}
@media (max-width: 600px) {
  .cart-page { padding: 20px 14px 60px; }
  .cart-page-header { padding: 18px; }
  .header-title { font-size: 22px; }
  .header-top-row { flex-direction: column; gap: 12px; }
  .header-actions { width: 100%; }
  .btn-continue, .btn-clear-all { flex: 1; justify-content: center; }
  .cart-item-card { flex-direction: column; gap: 14px; }
  .item-image-box { width: 100%; height: 160px; }
  .item-right { flex-direction: row; width: 100%; justify-content: space-between; align-items: center; }
}

/* --- GROUPED COMBO ITEMS --- */
.combo-item-group {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.95));
    border: 2px solid rgba(59, 130, 246, 0.4);
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.04);
    position: relative;
    transition: all 0.3s ease;
}

.combo-item-group:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(59, 130, 246, 0.08);
    border-color: rgba(59, 130, 246, 0.7);
}

/* Combo qu� t?ng VIP (gia_combo = 0) */
.combo-gift-group {
    border-color: rgba(22, 163, 74, 0.5) !important;
    background: linear-gradient(135deg, rgba(240, 253, 244, 0.95), rgba(255, 255, 255, 0.98)) !important;
    box-shadow: 0 10px 25px rgba(22, 163, 74, 0.06) !important;
}

.combo-gift-group:hover {
    border-color: rgba(22, 163, 74, 0.8) !important;
    box-shadow: 0 15px 30px rgba(22, 163, 74, 0.12) !important;
}

/* Banner qu� t?ng VIP */
.gift-offer-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    border: 1px solid #86efac;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 14px;
}

.gift-offer-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.gift-offer-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.gift-offer-text strong {
    font-size: 13px;
    font-weight: 800;
    color: #166534;
}

.gift-offer-text span {
    font-size: 11px;
    color: #15803d;
}

.gift-free-badge {
    background: #16a34a;
    color: white;
    font-size: 13px;
    font-weight: 900;
    padding: 4px 12px;
    border-radius: 20px;
    flex-shrink: 0;
    letter-spacing: 0.5px;
}

/* Badge xanh l� cho combo qu� t?ng */
.badge-tag-gift {
    background: linear-gradient(135deg, #16a34a, #15803d) !important;
}

/* Gi� mi?n ph� */
.free-price-text {
    color: #16a34a !important;
    font-weight: 900 !important;
    font-size: 12px !important;
}

.free-combo-price {
    font-size: 18px;
    font-weight: 900;
    color: #16a34a !important;
    letter-spacing: 0.5px;
}

.combo-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px dashed rgba(59, 130, 246, 0.3);
    padding-bottom: 12px;
    margin-bottom: 14px;
}

.combo-group-header .title-box {
    display: flex;
    align-items: center;
    gap: 10px;
}

.combo-group-header h3 {
    font-size: 14px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.badge-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    white-space: nowrap;
    flex-shrink: 0;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: white;
    font-size: 10px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.combo-remove-btn {
    border: none;
    background: #fee2e2;
    color: #dc2626;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
}

.combo-remove-btn:hover {
    background: #fca5a5;
}

.combo-child-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}

.child-item {
    display: flex;
    align-items: center;
    gap: 14px;
    background: white;
    border-radius: 12px;
    padding: 10px 14px;
    border: 1px solid #edf2f7;
}

.child-item img {
    width: 64px;
    height: 52px;
    object-fit: cover;
    border-radius: 8px;
}

.child-info {
    flex: 1;
    min-width: 0;
    text-align: left;
}

.child-info h4 {
    font-size: 13px;
    font-weight: 700;
    color: #334155;
    margin: 0 0 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.child-info p {
    font-size: 11px;
    color: #64748b;
    margin: 0;
}

.child-price {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.child-price .original-price {
    font-size: 11px;
    color: #94a3b8;
    text-decoration: line-through;
}

.child-price .allocated-price {
    font-size: 13px;
    font-weight: 800;
    color: #1e293b;
}

.combo-group-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid rgba(59, 130, 246, 0.1);
    padding-top: 14px;
}

.combo-group-footer .qty-section {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12.5px;
    font-weight: 700;
    color: #475569;
}

.combo-group-footer .total-section {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.combo-group-footer .total-section .lbl {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
}

.combo-group-footer .total-section .price-val {
    font-size: 18px;
    font-weight: 800;
    color: #2563eb;
}
</style>
