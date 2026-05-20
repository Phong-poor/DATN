<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'
import { getToken } from '@/services/auth'



const route = useRoute()
const isLoading = ref(true)
const router = useRouter()
// ===================== STATE GIỎ HÀNG =====================
const soLuongMua = ref(1)
const dangThem = ref(false)
const thongBao = ref({ show: false, type: '', message: '' })

const hienThiThongBao = (type, message) => {
    thongBao.value = { show: true, type, message }
    setTimeout(() => { thongBao.value.show = false }, 3000)
}

// ===================== STATE SẢN PHẨM =====================
const product = ref({
    tenSP: 'Đang tải...',
    gia: 0,
    SKU: '',
    hinhanh: '',
    hinhAnhs: [],
    bienThes: [],
    thong_so_ky_thuat: []
})

const selectedImage = ref('https://via.placeholder.com/600')
const selectedVariant = ref(null)
const selectedOptions = ref({})

// ===================== REVIEWS STATE =====================
const reviews = ref([])
const fetchReviews = async () => {
    try {
        const productId = route.params.id || 1
        const res = await api.get(`/sanpham/${productId}/reviews`)
        reviews.value = res.data.reviews || []
    } catch (error) {
        console.error('Lỗi khi tải đánh giá:', error)
    }
}

const averageRating = computed(() => {
    if (reviews.value.length === 0) return 0
    const sum = reviews.value.reduce((acc, r) => acc + r.danhgia, 0)
    return (sum / reviews.value.length).toFixed(1)
})

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}


// ===================== HELPERS BIẾN THỂ =====================
const getVariantAttributes = (variant) =>
    variant?.thuoc_tinh || variant?.attributes ||
    (variant?.thuoc_tinh_json
        ? (typeof variant.thuoc_tinh_json === 'string'
            ? JSON.parse(variant.thuoc_tinh_json)
            : variant.thuoc_tinh_json)
        : [])

const variantGroups = computed(() => {
    const variants = product.value.bienThes || []
    const map = new Map()

    variants.forEach(variant => {
        getVariantAttributes(variant).forEach(attr => {
            if (!map.has(attr.ten_thuoctinh)) map.set(attr.ten_thuoctinh, [])
            const exists = map.get(attr.ten_thuoctinh).some(v => v.giatri === attr.giatri)
            if (!exists) map.get(attr.ten_thuoctinh).push({ giatri: attr.giatri, ma_mau: attr.ma_mau || null })
        })
    })

    return Array.from(map.entries())
        .map(([name, values]) => ({ name, values }))
        .sort((a, b) => (a.name === 'Màu sắc' ? -1 : b.name === 'Màu sắc' ? 1 : 0))
})

const findMatchingVariant = () => {
    const variants = product.value.bienThes || []
    return variants.find(variant =>
        Object.entries(selectedOptions.value).every(([attrName, attrValue]) => {
            const found = getVariantAttributes(variant).find(i => i.ten_thuoctinh === attrName)
            return found && found.giatri === attrValue
        })
    ) || null
}


const handleSelectOption = (groupName, value) => {
    selectedOptions.value = { ...selectedOptions.value, [groupName]: value }
    const matched = findMatchingVariant()
    if (matched) selectedVariant.value = matched
}

// Reset số lượng khi đổi biến thể
const handleSelectOptionWithReset = (groupName, value) => {
    handleSelectOption(groupName, value)
    soLuongMua.value = 1
}

// ===================== SỐ LƯỢNG MUA =====================
const giamSoLuong = () => { if (soLuongMua.value > 1) soLuongMua.value-- }
const tangSoLuong = () => {
    const maxTonKho = selectedVariant.value?.soluong ?? 999
    if (soLuongMua.value < maxTonKho) soLuongMua.value++
}

// THÊM GIỎ HÀNG
const themVaoGioHang = async () => {

    // ✅ CHECK ĐĂNG NHẬP TRƯỚC
    const token = getToken()
    if (!token) {
        hienThiThongBao('error', 'Vui lòng đăng nhập trước!')
        setTimeout(() => {
            router.push({
                path: '/login',
                query: { redirect: route.fullPath }
            })
        }, 1000)
        return
    }

    // ======================
    if (!selectedVariant.value) {
        hienThiThongBao('error', 'Vui lòng chọn biến thể sản phẩm!')
        return
    }

    if (selectedVariant.value.soluong === 0) {
        hienThiThongBao('error', 'Sản phẩm này đã hết hàng!')
        return
    }

    dangThem.value = true

    try {
        await api.post('/gio-hang/them', {
            id_bienthe: selectedVariant.value.id_bienthe,
            soluong: soLuongMua.value,
        })

        hienThiThongBao('success', '✅ Đã thêm vào giỏ hàng!')

        // 🔥 cập nhật badge header
        window.dispatchEvent(new Event('cart-updated'))

    } catch (err) {
        const msg = err.response?.data?.message || 'Có lỗi xảy ra, vui lòng thử lại!'
        hienThiThongBao('error', msg)
    } finally {
        dangThem.value = false
    }
}
// ===================== FORMAT =====================
const formatPrice = (price) => {
    if (!price) return '0đ'
    return new Intl.NumberFormat('vi-VN').format(price) + 'đ'
}

const getImageUrl = (path) => {
    if (!path) return 'https://via.placeholder.com/600'
    if (path.startsWith('http') || path.startsWith('data:image')) return path
    return `http://127.0.0.1:8000/storage/${path}`
}

const allImages = computed(() => {
    if (!product.value) return []
    let images = []
    if (product.value.hinhanh) images.push(getImageUrl(product.value.hinhanh))
    const listHinhAnh = product.value.hinh_anhs || product.value.hinhAnhs || []
    listHinhAnh.forEach(img => images.push(getImageUrl(img.duongdan)))
    return images.length > 0 ? images : ['https://via.placeholder.com/600']
})

// ===================== THUMB SLIDER =====================
const thumbIndex = ref(0)
const thumbLimit = 4
const visibleThumbs = computed(() => {
    return allImages.value.slice(thumbIndex.value, thumbIndex.value + thumbLimit)
})
const nextThumbs = () => {
    if (thumbIndex.value + thumbLimit < allImages.value.length) {
        thumbIndex.value++
    }
}
const prevThumbs = () => {
    if (thumbIndex.value > 0) {
        thumbIndex.value--
    }
}

// ===================== FETCH SẢN PHẨM =====================
const fetchProductDetail = async () => {
    try {
        isLoading.value = true
        const productId = route.params.id || 1
        const response = await api.get(`/sanpham/${productId}`)
        const data = response.data
        const variants = data.bien_thes || data.bienThes || []
        product.value = { ...data, bienThes: variants }

        if (allImages.value.length > 0) selectedImage.value = allImages.value[0]

        if (variants.length > 0) {
            const variantId = route.query.variant
            let targetVariant = variants[0]

            if (variantId) {
                const found = variants.find(v => String(v.id_bienthe) === String(variantId))
                if (found) targetVariant = found
            }

            selectedVariant.value = targetVariant
            const options = {}
            getVariantAttributes(targetVariant).forEach(attr => {
                options[attr.ten_thuoctinh] = attr.giatri
            })
            selectedOptions.value = options

            // Cập nhật ảnh đại diện nếu biến thể có ảnh (optional - nếu có field hinhanh trong biến thể)
            if (targetVariant.hinhanh) {
                selectedImage.value = getImageUrl(targetVariant.hinhanh)
            }
        }

        // Tải sản phẩm tương tự
        if (data.id_danhmuc) {
            fetchRelatedProducts(data.id_danhmuc, data.id_sanpham)
        }

    } catch (error) {
        console.error('Lỗi khi tải chi tiết sản phẩm:', error)
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchProductDetail()
    fetchReviews()
})

const relatedProducts = ref([])

const currentRelatedPage = ref(1)
const relatedItemsPerPage = 5
const paginatedRelatedProducts = computed(() => {
    const start = (currentRelatedPage.value - 1) * relatedItemsPerPage
    return relatedProducts.value.slice(start, start + relatedItemsPerPage)
})

const totalRelatedPages = computed(() => Math.ceil(relatedProducts.value.length / relatedItemsPerPage))


const fetchRelatedProducts = async (id_danhmuc, currentProductId) => {
    try {
        const res = await api.get('/sanpham')
        const allProducts = res.data || []

        // Lọc theo danh mục và loại bỏ sản phẩm hiện tại
        const filtered = allProducts.filter(p =>
            p.id_danhmuc === id_danhmuc &&
            p.id_sanpham !== currentProductId
        )

        const variants = []
        filtered.forEach(p => {
            // Parse thông số chung để ghép tên
            let generalSpecs = []
            try {
                const tskt = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || []);
                if (Array.isArray(tskt)) {
                    generalSpecs = tskt.map(item => item.giatri).filter(Boolean);
                }
            } catch (e) { }
            const fullNameBase = [p.tenSP, ...generalSpecs].join(' ');

            if (p.bien_thes && p.bien_thes.length > 0) {
                p.bien_thes.forEach(bt => {
                    let ram = '', cpu = '', mausac = '';
                    let thuoc_tinh = [];
                    try { thuoc_tinh = typeof bt.thuoc_tinh_json === 'string' ? JSON.parse(bt.thuoc_tinh_json || '[]') : (bt.thuoc_tinh_json || []); } catch (e) { }

                    if (Array.isArray(thuoc_tinh)) {
                        thuoc_tinh.forEach(attr => {
                            const ten = (attr.ten_thuoctinh || '').toLowerCase();
                            if (ten === 'ram') ram = attr.giatri;
                            else if (ten === 'cpu') cpu = attr.giatri;
                            else if (ten === 'màu sắc' || ten === 'màu') mausac = attr.giatri;
                        });
                    }

                    const specText = [ram, cpu, mausac].filter(Boolean).join(' · ');

                    variants.push({
                        id: p.id_sanpham,
                        key_id: bt.id_bienthe,
                        fullName: fullNameBase,
                        specText: specText,
                        price: bt.gia,
                        img: bt.hinhanh ? getImageUrl(bt.hinhanh) : getImageUrl(p.hinhanh)
                    })
                })
            }
        })


        relatedProducts.value = variants.slice(0, 10)

        relatedProducts.value = variants
        currentRelatedPage.value = 1

    } catch (error) {
        console.error('Lỗi tải sản phẩm tương tự:', error)
    }
}
const dangThemYeuThich = ref(false)

const themVaoYeuThich = async () => {
    // 1. Check đăng nhập
    const token = getToken()
    if (!token) {
        hienThiThongBao('error', 'Vui lòng đăng nhập trước!')
        setTimeout(() => {
            router.push({ path: '/login', query: { redirect: route.fullPath } })
        }, 1000)
        return
    }

    // 2. Check xem khách đã chọn biến thể (RAM, SSD, Màu...) chưa
    if (!selectedVariant.value) {
        hienThiThongBao('error', 'Vui lòng chọn biến thể sản phẩm trước khi yêu thích!')
        return
    }

    dangThemYeuThich.value = true
    try {
        // 3. Gọi API thêm vào Database
        await api.post('/yeu-thich/them', {
            id_bienthe: selectedVariant.value.id_bienthe,
            soluong: soLuongMua.value, // Thích bao nhiêu cái thì truyền bấy nhiêu
        })

        // 4. Báo thành công và update Header

        hienThiThongBao('success', '❤️ Đã lưu vào danh sách yêu thích!')

        hienThiThongBao('success', '❤️ Đã lưu vào danh sách yêu thích từ trang chi tiết!')

        window.dispatchEvent(new Event('wishlist-updated'))

    } catch (err) {
        hienThiThongBao('error', err.response?.data?.message || 'Có lỗi xảy ra!')
    } finally {
        dangThemYeuThich.value = false
    }
}
</script>

<template>


    <transition name="slide-down">
        <div v-if="thongBao.show" :class="['toast', thongBao.type]">
            {{ thongBao.message }}
        </div>
    </transition>

    <div class="page">
        <div class="container">

            <div v-if="isLoading" style="text-align: center; padding: 50px;">
                Đang tải dữ liệu sản phẩm...
            </div>

            <div v-else>
                <div class="breadcrumb">
                    Trang chủ / Laptop / {{ product.tenSP }}
                </div>

                <div class="detail">

                    <!-- ẢNH -->
                    <div>
                        <div class="main-img">
                            <img :src="selectedImage" />
                        </div>
                        <div class="thumb-wrapper">
                            <button class="thumb-nav p-left" @click="prevThumbs" :disabled="thumbIndex === 0">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"
                                    height="16">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </button>
                            <div class="thumbs">
                                <img v-for="(img, i) in visibleThumbs" :key="i" :src="img" @click="selectedImage = img"
                                    :class="{ active: selectedImage === img }" />
                            </div>
                            <button class="thumb-nav p-right" @click="nextThumbs"
                                :disabled="thumbIndex + thumbLimit >= allImages.length">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"
                                    height="16">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- THÔNG TIN -->
                    <div>
                        <span class="tag" v-if="product.thuong_hieu">{{ product.thuong_hieu.ten_thuonghieu }}</span>
                        <span class="tag" v-else>MỚI NHẤT 2026</span>

                        <h1>{{ product.tenSP }}</h1>

                        <div class="rating">
                            <span class="stars-gold">
                                <template v-for="i in 5">
                                    <span v-if="i <= Math.round(averageRating)">★</span>
                                    <span v-else style="color: #e2e8f0;">★</span>
                                </template>
                            </span>
                            <span class="rating-count">
                                ({{ reviews.length > 0 ? averageRating + '/5' : 'Chưa có đánh giá' }})
                                <span v-if="reviews.length > 0"> • {{ reviews.length }} đánh giá</span>
                            </span>
                        </div>

                        <div class="price">
                            {{ selectedVariant ? formatPrice(selectedVariant.gia) : formatPrice(product.gia) }}
                        </div>


                        <div class="variant-stock" v-if="selectedVariant">
                            <span v-if="selectedVariant.soluong > 0" class="in-stock">
                                ✅ Còn hàng: {{ selectedVariant.soluong }} sản phẩm
                            </span>
                            <span v-else class="out-stock">❌ Hết hàng</span>
                        </div>

                        <div class="product-options" v-if="product.bienThes && product.bienThes.length > 0">
                            <div class="option-group" v-for="group in variantGroups" :key="group.name">
                                <p class="option-label">{{ group.name }}:</p>

                                <div v-if="group.name === 'Màu sắc'" class="color-list">
                                    <button v-for="item in group.values" :key="item.giatri" class="color-btn"
                                        :class="{ active: selectedOptions[group.name] === item.giatri }"
                                        @click="handleSelectOptionWithReset(group.name, item.giatri)"
                                        :title="item.giatri">
                                        <span class="color-dot"
                                            :style="{ backgroundColor: item.ma_mau || '#ccc' }"></span>
                                    </button>
                                </div>

                                <div v-else class="variant-grid">
                                    <button v-for="item in group.values" :key="item.giatri" class="variant-item-btn"
                                        :class="{ active: selectedOptions[group.name] === item.giatri }"
                                        @click="handleSelectOptionWithReset(group.name, item.giatri)">
                                        {{ item.giatri }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="product-options" v-else>
                            <p style="color:#666; font-size:14px;">Sản phẩm đang cập nhật tùy chọn.</p>
                        </div>


                        <div class="qty-wrap" v-if="selectedVariant && selectedVariant.soluong > 0">
                            <p class="option-label">Số lượng:</p>
                            <div class="qty-control">
                                <button @click="giamSoLuong" :disabled="soLuongMua <= 1">−</button>
                                <span>{{ soLuongMua }}</span>
                                <button @click="tangSoLuong"
                                    :disabled="soLuongMua >= selectedVariant.soluong">+</button>
                            </div>
                        </div>


                        <div class="actions">
                            <button class="add"
                                :disabled="!selectedVariant || selectedVariant.soluong === 0 || dangThem"
                                :class="{ 'disabled-btn': !selectedVariant || selectedVariant.soluong === 0 }"
                                @click="themVaoGioHang">
                                <span v-if="dangThem"> Đang thêm...</span>
                                <span v-else> Thêm vào giỏ hàng</span>
                            </button>
                            <button class="install">Trả góp 0%</button>

                            <button class="wishlist-btn" :disabled="dangThemYeuThich" @click="themVaoYeuThich"
                                title="Thêm vào yêu thích">
                                <span v-if="dangThemYeuThich">⏳</span>
                                <span v-else>❤️</span>
                            </button>
                        </div>

                        <div class="info">
                            <span>Giao nhanh 2h</span>
                            <span>Bảo hành 24 tháng</span>
                        </div>
                    </div>
                </div>

                <!-- THÔNG SỐ KỸ THUẬT -->
                <div class="specifications" v-if="product.thong_so_ky_thuat && product.thong_so_ky_thuat.length > 0">
                    <div class="spec-header">
                        <h2>Thông số kỹ thuật</h2>
                    </div>
                    <div class="spec-table-wrap">
                        <table class="spec-table">
                            <tbody>
                                <tr v-for="(spec, idx) in product.thong_so_ky_thuat" :key="idx">
                                    <td class="spec-label">{{ spec.ten_thuoctinh }}</td>
                                    <td class="spec-value">{{ spec.giatri }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ĐÁNH GIÁ -->
                <div class="reviews" id="reviews-section">
                    <div class="review-header">
                        <div>
                            <h2>Đánh giá từ người dùng ({{ reviews.length }})</h2>
                            <p v-if="reviews.length > 0">Điểm trung bình: <b>{{ averageRating }} / 5</b></p>
                            <p v-else>Sản phẩm này chưa có đánh giá. Hãy là người đầu tiên mua và đánh giá!</p>
                        </div>
                    </div>

                    <div class="review-list" v-if="reviews.length > 0">
                        <div class="review-card" v-for="review in reviews" :key="review.id_danhgia">
                            <div class="review-user-row">
                                <div class="user-avatar-small">
                                    {{ review.user?.name?.charAt(0) || 'U' }}
                                </div>
                                <div>
                                    <b class="user-name">{{ review.user?.name || 'Người dùng' }}</b>
                                    <p class="review-date">{{ formatDate(review.created_at) }}</p>
                                </div>
                            </div>

                            <div class="stars-gold">
                                <span v-for="s in 5" :key="s">
                                    {{ s <= review.danhgia ? '★' : '☆' }} </span>
                            </div>

                            <p class="review-comment">{{ review.binhluan || 'Hài lòng với sản phẩm.' }}</p>
                        </div>
                    </div>

                    <div v-else class="empty-reviews">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path
                                d="M12 20.25c4.556 0 8.25-3.694 8.25-8.25S16.556 3.75 12 3.75 3.75 7.444 3.75 12s3.694 8.25 8.25 8.25z" />
                            <path d="M12 8.25v7.5M15.75 12h-7.5" />
                        </svg>
                        <p>Chưa có bình luận nào cho sản phẩm này.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="related" v-if="relatedProducts.length > 0">
        <div class="related-header">
            <h2>Sản phẩm tương tự</h2>
            <router-link to="/products">Xem tất cả →</router-link>
        </div>
        <div class="related-list">

            

            <div class="product-card" v-for="p in paginatedRelatedProducts" :key="p.key_id"

                @click="router.push(`/products/${p.id}?variant=${p.key_id}`).then(() => window.location.reload())">
                <div class="img-box"><img :src="p.img" :alt="p.fullName" /></div>
                <h4>{{ p.fullName }}</h4>
                <p class="sub">{{ p.specText }}</p>
                <p class="price">{{ formatPrice(p.price) }}</p>
            </div>
        </div>


        <!-- PHÂN TRANG -->
        <div class="related-pagination" v-if="totalRelatedPages > 1">
            <button class="pag-btn" :disabled="currentRelatedPage === 1" @click="currentRelatedPage--">
                &laquo; Trước
            </button>
            <div class="pag-numbers">
                <button v-for="p in totalRelatedPages" :key="p" class="pag-num"
                    :class="{ active: currentRelatedPage === p }" @click="currentRelatedPage = p">
                    {{ p }}
                </button>
            </div>
            <button class="pag-btn" :disabled="currentRelatedPage === totalRelatedPages" @click="currentRelatedPage++">
                Sau &raquo;
            </button>
        </div>
>>>>>>> 5ee47b520ccf04fb393c034b6cd81450f2067428
    </div>

</template>

<style scoped>
/* ===== TOAST THÔNG BÁO ===== */
.wishlist-btn {
    padding: 0 16px;
    border: 1px solid #ff4d4f;
    border-radius: 8px;
    cursor: pointer;
    background: white;
    color: #ff4d4f;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.2s;
}

.wishlist-btn:hover:not(:disabled) {
    background: #fff1f0;
    transform: scale(1.05);
}

.wishlist-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

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

.slide-down-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

/* ===== CHỌN SỐ LƯỢNG ===== */
.qty-wrap {
    margin-bottom: 20px;
}

.qty-control {
    display: inline-flex;
    align-items: center;
    gap: 0;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    overflow: hidden;
}

.qty-control button {
    width: 38px;
    height: 38px;
    border: none;
    background: #f1f5f9;
    font-size: 18px;
    cursor: pointer;
    transition: background 0.2s;
}

.qty-control button:hover:not(:disabled) {
    background: #dbeafe;
    color: #2563eb;
}

.qty-control button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.qty-control span {
    min-width: 48px;
    text-align: center;
    font-weight: 600;
    font-size: 15px;
    border-left: 1px solid #cbd5e1;
    border-right: 1px solid #cbd5e1;
    padding: 0 8px;
    line-height: 38px;
}

/* ===== GIỮ NGUYÊN STYLE CŨ ===== */
.page {
    background: #f5f7fb;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 24px;
    font-family: sans-serif;
}

.detail {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 40px;
    align-items: start;
}

.main-img {
    background: #eef2ff;
    padding: 24px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 420px;
    overflow: hidden;
}

.main-img img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 6px;
}

.thumb-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 15px;
    width: 100%;
}

.thumbs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    flex: 1;
}

.thumbs img {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    cursor: pointer;
    opacity: 0.7;
    border-radius: 6px;
    border: 2px solid #f1f5f9;
    transition: 0.2s;
}

.thumbs img.active {
    border-color: #2563eb;
    opacity: 1;
    background: #fff;
}

.thumb-nav {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid #e2e8f0;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    transition: 0.2s;
    flex-shrink: 0;
}

.thumb-nav:hover:not(:disabled) {
    background: #f8fafc;
    color: #2563eb;
    border-color: #2563eb;
}

.thumb-nav:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.tag {
    background: #e2e8f0;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
}

h1 {
    margin: 10px 0;
}

.price {
    font-size: 24px;
    color: #2563eb;
    font-weight: bold;
    margin-bottom: 20px;
}

.variant-stock {
    margin-top: -10px;
    margin-bottom: 18px;
    font-size: 14px;
}

.in-stock {
    color: #16a34a;
}

.out-stock {
    color: #dc2626;
    font-weight: bold;
}

.product-options {
    margin-bottom: 25px;
}

.option-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 14px;
}

.option-label {
    margin: 0 0 8px;
    font-size: 14px;
    color: #334155;
    font-weight: bold;
}

.variant-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.variant-item-btn {
    padding: 8px 16px;
    border: 1px solid #cbd5e1;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s ease;
    color: #334155;
}

.variant-item-btn:hover {
    border-color: #2563eb;
    color: #2563eb;
}

.variant-item-btn.active {
    border-color: #2563eb;
    background: #eff6ff;
    color: #2563eb;
    font-weight: bold;
    box-shadow: 0 0 0 1px #2563eb;
}

.color-list {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}

.color-btn {
    width: 38px;
    height: 38px;
    border-radius: 999px;
    border: 2px solid transparent;
    background: transparent;
    padding: 3px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.color-btn.active {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px #2563eb;
}

.color-dot {
    display: block;
    width: 100%;
    height: 100%;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
}

.actions {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
}

.add {
    flex: 1;
    background: #2563eb;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.2s;
    font-size: 15px;
    font-weight: 600;
}

.add:hover:not(.disabled-btn) {
    background: #1d4ed8;
}

.disabled-btn {
    opacity: 0.5;
    cursor: not-allowed !important;
}

.install {
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    cursor: pointer;
    background: white;
}

.info {
    display: flex;
    gap: 20px;
    font-size: 13px;
    color: #64748b;
}

.reviews {
    margin-top: 60px;
}

/* ===== THÔNG SỐ KỸ THUẬT ===== */
.specifications {
    margin-top: 60px;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.spec-header {
    margin-bottom: 24px;
    border-left: 4px solid #2563eb;
    padding-left: 16px;
}

.spec-header h2 {
    font-size: 20px;
    margin: 0;
    color: #1e293b;
}

.spec-table-wrap {
    overflow: hidden;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.spec-table {
    width: 100%;
    border-collapse: collapse;
}

.spec-table tr {
    transition: background 0.2s;
}

.spec-table tr:nth-child(even) {
    background: #f8fafc;
}

.spec-table tr:hover {
    background: #f1f5f9;
}

.spec-table td {
    padding: 14px 20px;
    font-size: 14px;
    border-bottom: 1px solid #e2e8f0;
}

.spec-table tr:last-child td {
    border-bottom: none;
}

.spec-label {
    width: 30%;
    font-weight: 600;
    color: #64748b;
    background: #f1f5f9;
}

.spec-value {
    color: #1e293b;
}

@media (max-width: 768px) {
    .spec-label {
        width: 40%;
    }
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    align-items: center;
}

.review-header button {
    background: #2563eb;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
}

.review-list {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.review-card {
    background: #dadbdc;
    padding: 15px;
    border-radius: 10px;
}

.stars {
    color: orange;
}

.related {
    padding: 0 150px;
    margin: 60px 0;
}

.related-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.related-header h2 {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
}

.related-header a {
    color: #2563eb;
    font-weight: 600;
    text-decoration: none;
    font-size: 14px;
}

.related-list {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
}

.product-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 12px;
    transition: all 0.25s ease;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 36px rgba(0, 0, 0, 0.1);
    border-color: #2563eb;
}

.img-box {
    background: #f8fafc;
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 12px;
    overflow: hidden;
}

.img-box img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    border-radius: 8px;
    transition: transform 0.3s;
}

.product-card:hover .img-box img {
    transform: scale(1.05);
}

.product-card h4 {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 36px;
    line-height: 1.4;
}

.sub {
    font-size: 11px;
    color: #64748b;
    margin-bottom: 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-card .price {
    font-size: 15px;
    font-weight: 800;
    color: #2563eb;
}

@media (max-width: 768px) {

    .detail,
    .review-list {
        grid-template-columns: 1fr;
    }

    .related {
        padding: 0 16px;
    }

    .related-list {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* REVIEW STYLES */
.stars-gold {
    color: #f59e0b;
    font-size: 16px;
    margin-right: 5px;
}

.rating-count {
    font-size: 14px;
    color: #64748b;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f1f5f9;
}

.review-header h2 {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

.review-header p {
    font-size: 14px;
    color: #64748b;
    margin-top: 5px;
}

.review-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    transition: transform 0.2s;
}

.review-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.review-user-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.user-avatar-small {
    width: 36px;
    height: 36px;
    background: #2563eb;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
}

.user-name {
    font-size: 14px;
    color: #0f172a;
}

.review-date {
    font-size: 11px;
    color: #94a3b8;
}

.review-attr-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin: 8px 0;
}

.attr-tag {
    font-size: 12px;
    color: #475569;
    background: #f8fafc;
    padding: 4px 10px;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
    font-weight: 500;
}

.review-comment {
    font-size: 14px;
    color: #334155;
    line-height: 1.6;
    margin-top: 5px;
}

.empty-reviews {
    text-align: center;
    padding: 60px 0;
    color: #94a3b8;
}

.empty-reviews svg {
    width: 48px;
    height: 48px;
    margin-bottom: 15px;
}


/* PHÂN TRANG RELATED */
.related-pagination {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
}

.pag-btn {
    padding: 8px 16px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}

.pag-btn:hover:not(:disabled) {
    border-color: #2563eb;
    color: #2563eb;
    background: #eff6ff;
}

.pag-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pag-numbers {
    display: flex;
    gap: 6px;
}

.pag-num {
    width: 36px;
    height: 36px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
}

.pag-num:hover:not(.active) {
    border-color: #cbd5e1;
    color: #0f172a;
}

.pag-num.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

</style>