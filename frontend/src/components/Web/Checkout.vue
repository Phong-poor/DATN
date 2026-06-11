<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../../services/api'
import { getUser, updateUser, getToken } from '@/services/auth'
import { geocodeArea, geocodeWithFallback } from '@/services/geocode'
import swal from '@/services/swal'
import AddressMapPicker from './AddressMapPicker.vue'
import { normalizeImageUrl } from '@/services/urls'

const isUserLoggedIn = computed(() => Boolean(getToken()))



const router = useRouter()
const route = useRoute()

const promoCode = ref(route.query.promo_code || '')
const discount = ref(Number(route.query.discount) || 0)
const freeshipCode = ref(route.query.freeship_code || '')
const freeshipDiscount = ref(Number(route.query.freeship_discount) || 0)
const shippingFee = ref(30000)
const buyNowVariantId = computed(() => route.query.buy_now === '1' ? String(route.query.variant || '') : '')
const buyNowCartItemId = computed(() => route.query.buy_now === '1' ? String(route.query.cart_item || '') : '')

const isLoading = ref(true)
const isSubmitting = ref(false)
const addresses = ref([])
const selectedAddressId = ref(null)
const loadingAddresses = ref(false)
const showAddressModal = ref(false)
const showAddAddressModal = ref(false)
const showMapPicker = ref(false)
const editingAddressId = ref(null)
const savingAddress = ref(false)
const loadingProvinces = ref(false)
const loadingWards = ref(false)
const locatingSelectedArea = ref(false)
const selectedProvinceCode = ref('')
const selectedWardCode = ref('')
const provinces = ref([])
const wards = ref([])
const mapInitialPosition = ref(null)
const lastGeocodedString = ref('')

const defaultAddressForm = () => ({
    tinh_thanhpho: '',
    quan_huyen: '',
    phuong_xa: '',
    diachi_cuthe: '',
    full_address: '',
    latitude: null,
    longitude: null,
    loai_diachi: 'home',
    mac_dinh: false
})

const addressForm = ref(defaultAddressForm())
const addressApiBaseUrl = 'https://provinces.open-api.vn/api/v2'

const form = ref({
  name: '', // Sẽ tự fill nếu user đã log
  phone: '',
  email: '',
  address: ''
})

const payment = ref('cod')
const paymentMethodMap = {
    cod: 'COD',
    vnpay: 'VNPay',
    momo: 'MoMo'
}
const cart = ref([])

const isKnownAddressPart = (value) => value && value !== 'Không xác định'

const formatAddress = (addr) => {
    return [
        addr.diachi_cuthe,
        addr.phuong_xa,
        addr.quan_huyen,
        addr.tinh_thanhpho,
    ].filter(isKnownAddressPart).join(', ')
}

const selectedAddress = computed(() =>
    addresses.value.find(addr => addr.id_diachi === selectedAddressId.value)
)

const chooseAddress = (addr) => {
    selectedAddressId.value = addr.id_diachi
    form.value.address = formatAddress(addr)
    showAddressModal.value = false
}

const normalizeApiList = (data, keys = []) => {
    if (Array.isArray(data)) return data

    for (const key of keys) {
        if (Array.isArray(data?.[key])) return data[key]
    }

    if (Array.isArray(data?.data)) return data.data
    if (Array.isArray(data?.results)) return data.results
    return []
}

const fetchProvinces = async () => {
    if (provinces.value.length) return

    loadingProvinces.value = true
    try {
        const res = await fetch(`${addressApiBaseUrl}/p/`)
        const data = await res.json()
        provinces.value = normalizeApiList(data, ['provinces'])
    } catch (error) {
        console.error('Lỗi tải tỉnh/thành:', error)
        swal.error('Lỗi địa chỉ', 'Không thể tải danh sách tỉnh/thành.')
    } finally {
        loadingProvinces.value = false
    }
}

const fetchWardsByProvince = async (provinceCode) => {
    if (!provinceCode) {
        wards.value = []
        return
    }

    loadingWards.value = true
    try {
        const res = await fetch(`${addressApiBaseUrl}/p/${provinceCode}?depth=2`)
        const data = await res.json()
        const districts = normalizeApiList(data, ['districts'])
        const directWards = normalizeApiList(data, ['wards'])
        wards.value = directWards.length
            ? directWards
            : districts.flatMap((district) => normalizeApiList(district, ['wards']).map((ward) => ({
                ...ward,
                districtName: district.name,
            })))
    } catch (error) {
        console.error('Lỗi tải phường/xã:', error)
        swal.error('Lỗi địa chỉ', 'Không thể tải danh sách phường/xã.')
    } finally {
        loadingWards.value = false
    }
}

const handleProvinceChange = async () => {
    const province = provinces.value.find(item => String(item.code) === String(selectedProvinceCode.value))
    addressForm.value.tinh_thanhpho = province?.name || ''
    addressForm.value.quan_huyen = ''
    addressForm.value.phuong_xa = ''
    addressForm.value.full_address = addressForm.value.tinh_thanhpho
    selectedWardCode.value = ''
    mapInitialPosition.value = null
    await fetchWardsByProvince(selectedProvinceCode.value)
    await prepareMapInitialPosition()
}

const handleWardChange = async () => {
    const ward = wards.value.find(item => String(item.code) === String(selectedWardCode.value))
    addressForm.value.phuong_xa = ward?.name || ''
    addressForm.value.quan_huyen = ward?.districtName || ''
    addressForm.value.full_address = [addressForm.value.tinh_thanhpho, addressForm.value.phuong_xa].filter(Boolean).join(', ')
    mapInitialPosition.value = null
    await prepareMapInitialPosition()
}

const normalizeAddressName = (name = '') => name
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/^(tinh|thanh pho|tp|quan|huyen|thi xa|phuong|xa|thi tran)\s+/i, '')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase()

const findAddressCodeByName = (items, name) => {
    const normalizedName = normalizeAddressName(name)
    if (!normalizedName) return ''

    return items.find((item) => {
        const itemName = normalizeAddressName(item.name)
        return itemName === normalizedName
            || itemName.includes(normalizedName)
            || normalizedName.includes(itemName)
    })?.code || ''
}

const findProvinceCodeByName = (name) => findAddressCodeByName(provinces.value, name)
const findWardCodeByName = (name) => findAddressCodeByName(wards.value, name)

const geocodeSelectedArea = async () => {
    locatingSelectedArea.value = true
    try {
        const res = await geocodeWithFallback('', addressForm.value.phuong_xa, addressForm.value.quan_huyen, addressForm.value.tinh_thanhpho)
        if (res && res.lat && res.lng) {
            return { 
                lat: Number(res.lat), 
                lng: Number(res.lng),
                geojson: res.geojson,
                boundingbox: res.boundingbox
            }
        }
        return null
    } catch (error) {
        console.error('Lỗi tìm vị trí khu vực:', error)
        return null
    } finally {
        locatingSelectedArea.value = false
    }
}

const buildCurrentGeocodeString = () => {
    return [
        addressForm.value.diachi_cuthe, 
        addressForm.value.phuong_xa, 
        addressForm.value.quan_huyen, 
        addressForm.value.tinh_thanhpho
    ].filter(Boolean).join(', ').toLowerCase().replace(/\s+/g, ' ').trim()
}

const handleDetailBlur = async () => {
    if (!addressForm.value.diachi_cuthe || addressForm.value.diachi_cuthe.trim().length < 3) return
    if (locatingSelectedArea.value) return // Tránh double request
    
    const currentFullStr = buildCurrentGeocodeString()

    if (currentFullStr === lastGeocodedString.value) return // Không gọi API nếu không đổi
    
    locatingSelectedArea.value = true
    try {
        const fallbackRes = await geocodeWithFallback(
            addressForm.value.diachi_cuthe, 
            addressForm.value.phuong_xa, 
            addressForm.value.quan_huyen, 
            addressForm.value.tinh_thanhpho
        )
        if (fallbackRes && Number.isFinite(Number(fallbackRes.lat)) && Number.isFinite(Number(fallbackRes.lng))) {
            addressForm.value.latitude = Number(fallbackRes.lat)
            addressForm.value.longitude = Number(fallbackRes.lng)
            mapInitialPosition.value = { lat: addressForm.value.latitude, lng: addressForm.value.longitude }
            lastGeocodedString.value = currentFullStr
        }
    } catch (error) {
        console.error('Lỗi tìm vị trí chi tiết:', error)
    } finally {
        locatingSelectedArea.value = false
    }
}

const prepareMapInitialPosition = async () => {
    mapInitialPosition.value = await geocodeSelectedArea()
}

const openMapPicker = async () => {
    if (!addressForm.value.tinh_thanhpho) {
        swal.error('Thiếu địa chỉ', 'Vui lòng chọn tỉnh/thành phố trước khi ghim vị trí.')
        return
    }

    mapInitialPosition.value = await geocodeSelectedArea()
    showMapPicker.value = true
}

const applyMapAddress = (address) => {
    addressForm.value.tinh_thanhpho = address.province || addressForm.value.tinh_thanhpho
    addressForm.value.quan_huyen = address.district || addressForm.value.quan_huyen || ''
    addressForm.value.phuong_xa = address.ward || addressForm.value.phuong_xa || address.fullAddress || ''
    addressForm.value.full_address = address.fullAddress || [addressForm.value.tinh_thanhpho, addressForm.value.phuong_xa].filter(Boolean).join(', ')
    addressForm.value.latitude = address.latitude ?? addressForm.value.latitude
    addressForm.value.longitude = address.longitude ?? addressForm.value.longitude
    mapInitialPosition.value = address.latitude && address.longitude
        ? { lat: Number(address.latitude), lng: Number(address.longitude) }
        : mapInitialPosition.value

    if (!addressForm.value.diachi_cuthe && address.detail) {
        addressForm.value.diachi_cuthe = address.detail
    }
}

const openAddressModal = () => {
    showAddressModal.value = true
}

const openAddAddressModal = async () => {
    editingAddressId.value = null
    addressForm.value = {
        ...defaultAddressForm()
    }
    selectedProvinceCode.value = ''
    selectedWardCode.value = ''
    wards.value = []
    mapInitialPosition.value = null
    showAddressModal.value = false
    showAddAddressModal.value = true
    await fetchProvinces()
}

const openEditAddressModal = async (addr) => {
    editingAddressId.value = addr.id_diachi
    addressForm.value = {
        tinh_thanhpho: addr.tinh_thanhpho || '',
        quan_huyen: addr.quan_huyen || '',
        phuong_xa: addr.phuong_xa || '',
        diachi_cuthe: addr.diachi_cuthe || '',
        full_address: [addr.tinh_thanhpho, addr.phuong_xa].filter(Boolean).join(', '),
        latitude: addr.latitude ?? null,
        longitude: addr.longitude ?? null,
        loai_diachi: addr.loai_diachi || 'home',
        mac_dinh: Boolean(addr.mac_dinh),
    }
    mapInitialPosition.value = addr.latitude && addr.longitude
        ? { lat: Number(addr.latitude), lng: Number(addr.longitude) }
        : null
    showAddressModal.value = false
    showAddAddressModal.value = true
    await fetchProvinces()
    selectedProvinceCode.value = findProvinceCodeByName(addressForm.value.tinh_thanhpho)
    selectedWardCode.value = ''

    if (selectedProvinceCode.value) {
        await fetchWardsByProvince(selectedProvinceCode.value)
        selectedWardCode.value = findWardCodeByName(addressForm.value.phuong_xa)
    }

    if (!mapInitialPosition.value) {
        await prepareMapInitialPosition()
    }
}

const saveNewAddress = async () => {
    savingAddress.value = true
    try {
        const payload = {
            tinh_thanhpho: addressForm.value.tinh_thanhpho || addressForm.value.full_address || 'Không xác định',
            quan_huyen: addressForm.value.quan_huyen || 'Không xác định',
            phuong_xa: addressForm.value.phuong_xa || addressForm.value.full_address || 'Không xác định',
            diachi_cuthe: addressForm.value.diachi_cuthe,
            latitude: addressForm.value.latitude,
            longitude: addressForm.value.longitude,
            loai_diachi: addressForm.value.loai_diachi,
            mac_dinh: addressForm.value.mac_dinh,
        }
        const isEditing = Boolean(editingAddressId.value)
        const res = isEditing
            ? await api.put(`/user/dia-chi/${editingAddressId.value}`, payload)
            : await api.post('/user/dia-chi', payload)
        await fetchAddresses(res.data.data?.id_diachi)
        showAddAddressModal.value = false
        editingAddressId.value = null
        swal.success('Thành công', isEditing ? 'Đã cập nhật địa chỉ.' : 'Đã thêm địa chỉ mới.')
    } catch (error) {
        const msg = error.response?.data?.message
            || Object.values(error.response?.data?.errors || {})?.[0]?.[0]
            || 'Không thể thêm địa chỉ mới.'
        swal.error('Lỗi địa chỉ', msg)
    } finally {
        savingAddress.value = false
    }
}

const fetchAddresses = async (preferredAddressId = null) => {
    loadingAddresses.value = true
    try {
        const res = await api.get('/user/dia-chi')
        addresses.value = res.data.data || []
        const defaultAddress = addresses.value.find(addr => addr.id_diachi === preferredAddressId)
            || addresses.value.find(addr => addr.id_diachi === selectedAddressId.value)
            || addresses.value.find(addr => addr.mac_dinh)
            || addresses.value[0]
        if (defaultAddress) {
            chooseAddress(defaultAddress)
        }
    } catch (error) {
        console.error('Lỗi tải địa chỉ:', error)
    } finally {
        loadingAddresses.value = false
    }
}

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

const fetchCart = async () => {
    try {
        isLoading.value = true
        const response = await api.get('/gio-hang')
        if (response.data.success) {
            let items = response.data.gio_hang.map(item => ({
                id_giohang: item.id_giohang,
                id_bienthe: item.id_bienthe,
                name: getFullProductName(item),
                desc: item.ten_bienthe,
                price: item.gia,
                qty: item.soluong,
                img: normalizeImageUrl(item.hinh_anh, 'https://via.placeholder.com/200'),
                id_combo: item.id_combo,
                combo_group_id: item.combo_group_id,
                ten_combo: item.ten_combo,
                hinhanh_combo: normalizeImageUrl(item.hinhanh_combo, ''),
                gia_combo: item.gia_combo,
                gia_goc: item.gia_goc
            }))

            if (buyNowCartItemId.value) {
                items = items.filter(item => String(item.id_giohang) === buyNowCartItemId.value)
            } else if (buyNowVariantId.value) {
                items = items.filter(item => String(item.id_bienthe) === buyNowVariantId.value)
            }

            cart.value = items
        }
    } catch (error) {
        console.error('Lỗi khi tải giỏ hàng:', error)
    } finally {
        isLoading.value = false
    }
}

const fillUserForm = (user = {}) => {
    form.value.name = user.name || user.ten || form.value.name || ''
    form.value.email = user.email || form.value.email || ''
    form.value.phone = user.phone || user.sdt || user.so_dien_thoai || form.value.phone || ''
}

const fetchUserProfile = async () => {
    const cachedUser = getUser()
    if (cachedUser) {
        fillUserForm(cachedUser)
    }

    try {
        const response = await api.get('/user/profile')
        const profile = response.data?.user || response.data?.data || response.data
        if (profile) {
            fillUserForm(profile)
            updateUser({ ...(cachedUser || {}), ...profile })
        }
    } catch (error) {
        console.error('Lỗi tải thông tin người dùng:', error)
    }
}

const groupedCart = computed(() => {
    const list = []
    const comboGroups = {}

    cart.value.forEach(item => {
        if (item.id_combo && item.combo_group_id) {
            if (!comboGroups[item.combo_group_id]) {
                comboGroups[item.combo_group_id] = {
                    isCombo: true,
                    combo_group_id: item.combo_group_id,
                    id_combo: item.id_combo,
                    ten_combo: item.ten_combo,
                    hinhanh_combo: normalizeImageUrl(item.hinhanh_combo, ''),
                    gia_combo: item.gia_combo,
                    qty: item.qty,
                    items: []
                }
                list.push(comboGroups[item.combo_group_id])
            }
            comboGroups[item.combo_group_id].items.push(item)
        } else {
            list.push({
                isCombo: false,
                ...item
            })
        }
    })

    return list
})

onMounted(() => {
    fetchCart()
    fetchAddresses()
    fetchUserProfile()
})

const subtotal = computed(() =>
  cart.value.reduce((sum, i) => sum + i.price * i.qty, 0)
)

const total = computed(() => {
    const afterDiscount = Math.max(0, subtotal.value - discount.value)
    const afterShipping = Math.max(0, shippingFee.value - freeshipDiscount.value)
    return afterDiscount + afterShipping
})

const format = (n) => n.toLocaleString('vi-VN') + 'đ'

const normalizePhone = () => {
    form.value.phone = String(form.value.phone || '').replace(/\D/g, '').slice(0, 10)
}

const confirmOrder = async () => {
    normalizePhone()

    if (!/^0\d{9}$/.test(form.value.phone)) {
        swal.warning('Thiếu thông tin', 'Vui lòng nhập số điện thoại 10 số và bắt đầu bằng số 0.')
        return
    }

    if (!selectedAddressId.value && !form.value.address) {
        swal.warning('Thiếu thông tin', 'Vui lòng chọn địa chỉ')
        return
    }

    if (!form.value.address) {
        swal.warning('Thiếu thông tin', 'Vui lòng nhập địa chỉ nhận hàng!')
        return
    }

    try {
        isSubmitting.value = true
        const response = await api.post('/checkout', {
            id_diachi: selectedAddressId.value,
            diachi: form.value.address,
            name: form.value.name,
            phone: form.value.phone,
            PTTT: paymentMethodMap[payment.value] || 'COD',
            promo_code: promoCode.value,
            freeship_code: freeshipCode.value,
            selected_cart_items: buyNowCartItemId.value ? [buyNowCartItemId.value] : undefined,
            selected_variants: !buyNowCartItemId.value && buyNowVariantId.value ? [buyNowVariantId.value] : undefined
        })

        if (response.data.success) {
            const grantedVouchers = response.data.granted_vouchers || [];
            if (grantedVouchers.length > 0) {
                const voucherNames = grantedVouchers.map(v => v.name).join(', ');
                await swal.success(
                    'Chúc mừng!',
                    `Bạn đã nhận được voucher: ${voucherNames} khi mua hàng thành công!`
                );
            }

            if (response.data.payUrl) {
                window.location.href = response.data.payUrl;
            } else {
                router.push({ 
                    name: 'thank-you', 
                    query: { 
                        status: 'success', 
                        order_id: response.data.order.id_dathang 
                    } 
                })
            }
        }
    } catch (error) {
        const msg = error.response?.data?.message || 'Có lỗi xảy ra khi đặt hàng.'
        swal.error('Lỗi đặt hàng', msg)
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>


  <div class="checkout-page">
    <div class="container">

      <!-- LEFT -->
      <div class="left">
        <h1>Thanh toán</h1>
        <p class="subtitle">
          Hoàn tất đơn đặt hàng của bạn với sự chính xác tuyệt đối.
        </p>

        <!-- INFO -->
        <div class="box">
          <div class="box-title">
            <span class="step">1</span>
            Thông tin người nhận
          </div>

          <div class="form-grid">
            <input v-model="form.name" placeholder="Họ và tên" class="checkout-input" />
            <input
              v-model="form.phone"
              placeholder="Số điện thoại"
              type="tel"
              inputmode="numeric"
              autocomplete="tel"
              maxlength="10"
              class="checkout-input"
              @input="normalizePhone"
            />
          </div>

          <input class="checkout-input" v-model="form.email" placeholder="Email" />

          <div class="address-list" v-if="isUserLoggedIn || loadingAddresses">
            <div class="address-header">
              <p class="address-title">Địa chỉ giao hàng</p>
              <div class="address-actions">
                <button type="button" class="address-link" @click="addresses.length ? openAddressModal() : openAddAddressModal()">
                  {{ addresses.length ? 'Thay đổi' : 'Thêm địa chỉ mới' }}
                </button>
              </div>
            </div>
            <p v-if="loadingAddresses" class="address-loading">Đang tải địa chỉ...</p>
          </div>

          <textarea class="checkout-textarea" v-model="form.address" placeholder="Địa chỉ nhận hàng"></textarea>
        </div>

        <!-- PAYMENT -->
        <div class="box">
          <div class="box-title">
            <span class="step">2</span>
            Phương thức thanh toán
          </div>

          <div class="pay-list">

            <label class="pay-item" :class="{ active: payment === 'cod' }">
              <input type="radio" value="cod" v-model="payment" />
              <div class="radio"></div>
              <div class="pay-logo">
                <img src="/payment/cod.png" alt="COD" />
              </div>
              <div class="pay-text">
                <b>COD (Thanh toán khi nhận hàng)</b>
                <p>Thanh toán tiền mặt khi nhận hàng</p>
              </div>
            </label>

            <label class="pay-item" :class="{ active: payment === 'vnpay' }">
              <input type="radio" value="vnpay" v-model="payment" />
              <div class="radio"></div>
              <div class="pay-logo">
                <img src="/payment/vnpay.png" alt="VNPay" />
              </div>
              <div class="pay-text">
                <b>Ví điện tử VNPay</b>
                <p>Thanh toán nhanh qua cổng VNPay sandbox</p>
              </div>
            </label>

            <label class="pay-item" :class="{ active: payment === 'momo' }">
              <input type="radio" value="momo" v-model="payment" />
              <div class="radio"></div>
              <div class="pay-logo">
                <img src="/payment/momo.png" alt="MoMo" />
              </div>
              <div class="pay-text">
                <b>MoMo Sandbox</b>
                <p>Chuyển sang MoMo để chọn QR, ATM/Napas hoặc Visa/Mastercard/JCB</p>
              </div>
            </label>

          </div>
        </div>
      </div>

      <!-- RIGHT -->
      <div class="right">
        <div class="summary">
          <h3>Tóm tắt đơn hàng</h3>

          <div v-for="(entry, index) in groupedCart" :key="entry.isCombo ? entry.combo_group_id : index">
            <!-- Standalone Item -->
            <div class="item" v-if="!entry.isCombo">
              <img :src="entry.img" />
              <div style="flex: 1; min-width: 0; text-align: left;">
                <p style="margin: 0; font-size: 13.5px; font-weight: 600; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ entry.name }}</p>
                <span style="font-size: 11px; color: #64748b; display: block; margin-top: 2px;">{{ entry.desc }}</span>
                <span class="qty-badge" style="display: inline-block; font-size: 11px; color: #2563eb; background: #eff6ff; padding: 2px 6px; border-radius: 4px; margin-top: 4px; font-weight: 700;">x{{ entry.qty }}</span>
              </div>
              <div style="text-align: right; min-width: 75px;">
                <b style="font-size: 14px; color: #1e293b;">{{ format(entry.price * entry.qty) }}</b>
                <span style="display: block; font-size: 10px; color: #94a3b8; margin-top: 2px;" v-if="entry.qty > 1">{{ format(entry.price) }}/sp</span>
              </div>
            </div>

            <!-- Grouped Combo Item -->
            <div class="checkout-combo-group" v-else>
              <div class="checkout-combo-header">
                <span class="checkout-badge-tag">🎁 Combo</span>
                <h4>{{ entry.ten_combo }}</h4>
                <span class="checkout-combo-qty">x{{ entry.qty }}</span>
              </div>
              <div class="checkout-combo-child-list">
                <div class="checkout-child-item" v-for="child in entry.items" :key="child.id_giohang">
                  <img :src="child.img" />
                  <div class="checkout-child-info">
                    <p>{{ child.name }}</p>
                    <span>{{ child.desc }}</span>
                  </div>
                  <div class="checkout-child-price">
                    <span class="allocated-price">{{ format(child.price) }}</span>
                  </div>
                </div>
              </div>
              <div class="checkout-combo-footer">
                <span>Tổng combo:</span>
                <b>{{ format(entry.gia_combo * entry.qty) }}</b>
              </div>
            </div>
          </div>

          <div class="line"></div>

          <div class="row">
            <span>Tạm tính</span>
            <b>{{ format(subtotal) }}</b>
          </div>

          <div class="row" v-if="discount > 0">
            <span>Giảm giá (Mã {{ promoCode }})</span>
            <b style="color:#dc2626">-{{ format(discount) }}</b>
          </div>

          <div class="row">
            <span>Phí vận chuyển</span>
            <b>{{ format(shippingFee) }}</b>
          </div>

          <div class="row" v-if="freeshipDiscount > 0">
            <span>Freeship (Mã {{ freeshipCode }})</span>
            <b style="color:#16a34a">-{{ format(freeshipDiscount) }}</b>
          </div>
          <div class="total">
            <span>TỔNG CỘNG: </span>
            <b>{{ format(total) }}</b>
          </div>

          <button class="btn" @click="confirmOrder" :disabled="isSubmitting || cart.length === 0">
            <span v-if="isSubmitting">⏳ Đang xử lý...</span>
            <span v-else>Xác nhận đặt hàng</span>
          </button>

          <p class="secure">🔒 Giao dịch được bảo mật 256-bit</p>
        </div>

        <div class="coupon" v-if="promoCode || freeshipCode">
          <span>🏷️ Ưu đãi đã áp dụng</span>
          <p v-if="promoCode" style="margin:5px 0">Giảm giá đơn hàng: <b>{{ promoCode }}</b></p>
          <p v-if="freeshipCode" style="margin:5px 0">Miễn phí vận chuyển: <b>{{ freeshipCode }}</b></p>
        </div>
      </div>

    </div>
  </div>

  <div v-if="showAddressModal" class="overlay" @click.self="showAddressModal = false" style="z-index: 9015;">
    <div class="modal address-modal">
      <div class="modal-head">
        <h2 class="modal-title">Chọn địa chỉ giao hàng</h2>
        <button type="button" class="close-btn" @click="showAddressModal = false">
          <svg viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="modal-body address-choices">
        <label
          v-for="addr in addresses"
          :key="addr.id_diachi"
          class="address-card"
          :class="{ active: selectedAddressId === addr.id_diachi }"
        >
          <input type="radio" :value="addr.id_diachi" v-model="selectedAddressId" @change="chooseAddress(addr)" />
          <div>
            <b style="line-height: 1.4; display: block; margin-bottom: 4px;">{{ formatAddress(addr) }}</b>
            <span>{{ addr.loai_diachi === 'company' ? 'Công ty' : 'Nhà riêng' }}</span>
            <span v-if="addr.mac_dinh" class="default-tag">Mặc định</span>
          </div>
          <button type="button" class="address-update-btn" @click.stop.prevent="openEditAddressModal(addr)">Cập nhật</button>
        </label>
      </div>
      <div class="modal-footer" style="display: flex; justify-content: flex-end; align-items: center; gap: 12px; padding: 0 28px 28px; border-top: none;">
        <button type="button" class="btn-cancel" @click="showAddressModal = false">Hủy</button>
        <button type="button" class="btn-save" @click="openAddAddressModal">Thêm địa chỉ mới</button>
      </div>
    </div>
  </div>

  <div v-if="showAddAddressModal" class="overlay" @click.self="showAddAddressModal = false" style="z-index: 9015;">
    <div class="modal address-modal">
      <div class="modal-head">
        <h2 class="modal-title">{{ editingAddressId ? 'Cập nhật địa chỉ' : 'Thêm địa chỉ mới' }}</h2>
        <button type="button" class="close-btn" @click="showAddAddressModal = false">
          <svg viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="modal-body">
        <form class="address-modal-form" @submit.prevent="saveNewAddress">
          <div class="form-group form-full">
            <div class="region-picker-row">
              <div class="region-picker-field">
                <label>Tỉnh/Thành phố</label>
                <select v-model="selectedProvinceCode" :disabled="loadingProvinces" required @change="handleProvinceChange">
                  <option value="" disabled>{{ loadingProvinces ? 'Đang tải tỉnh/thành...' : 'Chọn tỉnh/thành phố' }}</option>
                  <option v-for="province in provinces" :key="province.code" :value="province.code">{{ province.name }}</option>
                </select>
              </div>
              <div class="region-picker-field">
                <label>Phường/Xã</label>
                <select v-model="selectedWardCode" :disabled="!selectedProvinceCode || loadingWards" required @change="handleWardChange">
                  <option value="" disabled>{{ loadingWards ? 'Đang tải phường/xã...' : 'Chọn phường/xã' }}</option>
                  <option v-for="ward in wards" :key="ward.code" :value="ward.code">{{ ward.name }}</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group form-full">
            <label>Địa chỉ chi tiết</label>
            <input v-model="addressForm.diachi_cuthe" @blur="handleDetailBlur" placeholder="Số nhà, tên đường..." required />
          </div>
          <div class="form-group form-full">
            <label>Vị trí giao hàng</label>
            <div class="inline-map-field">
              <AddressMapPicker inline :initial-position="mapInitialPosition" @selected="applyMapAddress" @open="openMapPicker" />
              <small v-if="locatingSelectedArea">Đang tìm vị trí khu vực...</small>
              <small v-else-if="addressForm.full_address">{{ addressForm.full_address }}</small>
            </div>
          </div>
          <div class="form-group">
            <label>Loại địa chỉ</label>
            <select v-model="addressForm.loai_diachi" required>
              <option value="home">Nhà riêng</option>
              <option value="company">Công ty</option>
            </select>
          </div>
          <div class="form-group form-full">
            <label class="checkbox-label">
              <input type="checkbox" v-model="addressForm.mac_dinh" />
              <span>Đặt làm địa chỉ mặc định</span>
            </label>
          </div>
          <div class="form-actions form-full address-modal-actions">
            <button type="button" class="btn-cancel" @click="showAddAddressModal = false">Hủy</button>
            <button type="submit" class="btn-save" :disabled="savingAddress">
              <svg v-if="savingAddress" class="spin" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
              {{ savingAddress ? 'Đang lưu...' : 'Lưu địa chỉ' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <AddressMapPicker v-model="showMapPicker" :initial-position="mapInitialPosition" @selected="applyMapAddress" />


</template>

<style scoped>

.checkout-page {
  background: #f5f7fb;
  min-height: 100vh;
}

/* CONTAINER */
.container {
  max-width: 1040px;
  margin: auto;
  padding: 28px 20px;
  display: flex;
  gap: 24px;
  align-items: start;
}

.left {
  flex: 0 1 680px;
  min-width: 0;
}

.right {
  width: 340px;
  flex-shrink: 0;
  box-sizing: border-box;
}

/* TEXT */
.subtitle {
  color: #64748b;
  margin-bottom: 20px;
}

/* BOX */
.box {
  background: #e5e7eb;
  padding: 18px;
  border-radius: 12px;
  margin-bottom: 16px;
  border: 1px solid #cbd5e1;
}

.box-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #111827;
}

.step {
  width: 24px;
  height: 24px;
  background: #2563eb;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* FORM */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 8px;
}

/* INPUT */
input:not([type="radio"]):not([type="checkbox"]) {
  width: 100%;
  height: 44px;
  padding: 0 14px;
  border: none;
  background: #111f35;
  border-radius: 10px;
  font-size: 14px;
  box-sizing: border-box;
  margin-bottom: 12px;
}

select {
  width: 100%;
  height: 44px;
  padding: 0 14px;
  border: none;
  background: #111f35;
  border-radius: 10px;
  font-size: 14px;
  box-sizing: border-box;
}

/* TEXTAREA TÁCH RIÊNG */
textarea {
  width: 100%;
  height: 120px;
  padding: 12px 14px;
  border: none;
  background: #111f35;
  border-radius: 10px;
  font-size: 14px;
  box-sizing: border-box;

  margin-top: 10px;
  resize: none;
}

/* CUSTOM CHECKOUT INPUTS */
.checkout-input {
  width: 100% !important;
  height: 42px !important;
  padding: 0 14px !important;
  border: 1px solid #d1d5db !important;
  background: var(--tn-bg) !important;
  border-radius: 10px !important;
  font-size: 13px !important;
  color: #1e293b !important;
  box-sizing: border-box !important;
  margin-bottom: 10px !important;
  outline: none !important;
  transition: all 0.2s ease !important;
}

.checkout-input:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16) !important;
  background: var(--tn-surface) !important;
}

.checkout-textarea {
  width: 100% !important;
  height: 84px !important;
  padding: 12px 14px !important;
  border: 1px solid #d1d5db !important;
  background: var(--tn-bg) !important;
  border-radius: 10px !important;
  font-size: 13px !important;
  color: #1e293b !important;
  box-sizing: border-box !important;
  margin-top: 8px !important;
  resize: none !important;
  outline: none !important;
  transition: all 0.2s ease !important;
}

.checkout-textarea:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16) !important;
  background: var(--tn-surface) !important;
}

.address-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.address-title {
  margin: 0;
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
}

.address-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.address-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.address-link {
  border: none;
  background: transparent;
  color: #2563eb;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}

.address-card {
  display: grid;
  grid-template-columns: 20px 1fr auto;
  gap: 10px;
  padding: 16px;
  border: 1.5px solid rgba(255, 255, 255, 0.06);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.02);
  cursor: pointer;
  transition: all 0.25s ease;
}

.address-card:hover {
  border-color: rgba(56, 189, 248, 0.25);
  background: rgba(255, 255, 255, 0.04);
}

.address-card.active {
  border-color: rgba(56, 189, 248, 0.4);
  background: rgba(56, 189, 248, 0.04);
}

.selected-address {
  grid-template-columns: 1fr;
  cursor: default;
}

.address-update-btn {
  border: none;
  background: transparent;
  color: #38bdf8;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  white-space: nowrap;
  font-size: 13px;
}

.address-choices {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.address-card input {
  width: 16px;
  height: 16px;
  margin: 2px 0 0;
  accent-color: #38bdf8;
}

.address-card b {
  display: block;
  font-size: 14px;
  color: #ffffff;
  font-weight: 700;
}

.address-card span {
  display: inline-block;
  margin-right: 6px;
  font-size: 11px;
  color: #22d3ee;
  background: rgba(6, 182, 212, 0.12);
  border: 1px solid rgba(6, 182, 212, 0.25);
  padding: 3px 8px;
  border-radius: 99px;
  font-weight: 700;
}

.address-card .default-tag {
  color: #22d3ee;
  background: rgba(6, 182, 212, 0.12);
  border: 1px solid rgba(6, 182, 212, 0.25);
}

/* MODAL OVERLAY */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(5, 11, 21, 0.75);
  z-index: 9015;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.modal {
  background: #0f1c30;
  border: 1px solid rgba(56, 189, 248, 0.15);
  box-shadow: 0 24px 50px rgba(0, 0, 0, 0.4);
  border-radius: 24px;
  width: 100%;
  max-width: 520px;
  max-height: 88vh;
  overflow-y: auto;
}

.modal-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 24px 28px 0;
}

.modal-title {
  font-size: 18px;
  font-weight: 800;
  color: #ffffff !important;
  margin: 0 0 4px;
  letter-spacing: -0.2px;
}

.close-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.04);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

.close-btn svg {
  width: 14px;
  height: 14px;
  stroke: currentColor;
  stroke-width: 2.5;
}

.modal-body {
  padding: 20px 28px 28px;
}

.address-modal {
  max-width: 720px;
}

.address-modal-form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.address-modal-form .form-group {
  margin: 0;
}

.address-modal-form input,
.address-modal-form select {
  width: 100%;
  box-sizing: border-box;
}

.address-modal-actions {
  justify-content: flex-end;
  margin-top: 12px;
}

/* FORMS */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 12.5px;
  font-weight: 600;
  color: #94a3b8 !important;
  letter-spacing: 0.2px;
}

.form-group input,
.form-group select {
  padding: 10px 14px;
  border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
  border-radius: 11px;
  font-size: 13.5px;
  color: #ffffff !important;
  outline: none;
  transition: all 0.2s ease;
  background: rgba(13, 27, 46, 0.5) !important;
}

.form-group input:disabled,
.form-group select:disabled {
  opacity: 0.5;
  color: #64748b !important;
  cursor: not-allowed;
}

.form-group select option {
  background-color: #0f1c2e;
  color: #e2e8f0;
}

.form-group select option:disabled {
  color: #64748b;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #38bdf8;
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
  background: rgba(13, 27, 46, 0.8) !important;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  color: #cbd5e1;
  font-weight: 600;
  user-select: none;
}

.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #38bdf8;
  cursor: pointer;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.btn-cancel {
  padding: 9px 19px;
  border-radius: 11px;
  background: rgba(255, 255, 255, 0.03) !important;
  border: 1px solid rgba(255, 255, 255, 0.07) !important;
  color: #94a3b8 !important;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-cancel:hover {
  background: rgba(255, 255, 255, 0.08) !important;
  color: #ffffff !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
}

.btn-save {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 9px 21px;
  border-radius: 11px;
  background: linear-gradient(135deg, #0284c7 0%, #0891b2 100%) !important;
  border: none !important;
  color: #ffffff !important;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(6, 182, 212, 0.2) !important;
}

.btn-save:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 15px rgba(6, 182, 212, 0.3) !important;
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.spin {
  width: 16px;
  height: 16px;
  stroke: #ffffff;
  stroke-width: 2.5;
  fill: none;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.form-full {
  grid-column: 1 / -1;
}

.region-picker-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.region-picker-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.inline-map-field small {
  display: block;
  margin-top: 6px;
  color: #64748b;
  font-size: 12px;
}

/* PAYMENT */
.pay-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.pay-item {
  display: grid;
  grid-template-columns: 24px 40px 1fr;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 10px;
  background: var(--tn-bg);
  border: 1px solid #d1d5db;
  cursor: pointer;
}

.pay-item.active {
  background: #dbeafe;
  border: 1px solid #3b82f6;
}

.pay-item input {
  display: none;
}

.radio {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid #94a3b8;
  position: relative;
}

.pay-item.active .radio {
  border-color: #2563eb;
}

.pay-item.active .radio::after {
  content: '';
  width: 10px;
  height: 10px;
  background: #2563eb;
  border-radius: 50%;
  position: absolute;
  top: 3px;
  left: 3px;
}

.pay-text p {
  font-size: 11.5px;
  color: #64748b;
  line-height: 1.4;
  margin: 2px 0 0;
}

.pay-text {
  min-width: 0;
}

.pay-text b {
  line-height: 1.35;
  color: #1e293b;
  font-size: 13.5px;
}

.pay-logo {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: transparent;
}

.pay-logo img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
}

/* RIGHT */
.summary {
  background: #eef2ff;
  padding: 18px;
  border-radius: 12px;
}

.item {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 10px;
}

.item img {
  width: 50px;
  height: 50px;
  object-fit: cover;
}

.line {
  height: 1px;
  background: #cbd5e1;
  margin: 10px 0;
}

.row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.total {
  margin-top: 10px;
  font-size: 18px;
  font-weight: bold;
}

.btn {
  margin-top: 14px;
  width: 100%;
  padding: 11px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 8px;
}

.secure {
  text-align: center;
  font-size: 12px;
  color: #64748b;
  margin-top: 10px;
}

/* COUPON */
.coupon {
  margin-top: 15px;
  background: #e0e7ff;
  padding: 15px;
  border-radius: 10px;
}

/* ─── GROUPED COMBO ITEMS FOR CHECKOUT SUMMARY ─── */
.checkout-combo-group {
  background: white;
  border: 1.5px solid rgba(59, 130, 246, 0.35);
  border-radius: 12px;
  padding: 12px;
  margin-bottom: 12px;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.03);
}

.checkout-combo-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px dashed rgba(59, 130, 246, 0.2);
  padding-bottom: 8px;
  margin-bottom: 8px;
  gap: 8px;
}

.checkout-combo-header h4 {
  font-size: 13px;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
  flex: 1;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.checkout-badge-tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  white-space: nowrap;
  flex-shrink: 0;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white;
  font-size: 9px;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 10px;
  text-transform: uppercase;
}

.checkout-combo-qty {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.checkout-combo-child-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 8px;
}

.checkout-child-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 8px;
  border-radius: 8px;
  border: 1px solid #f1f5f9;
  background: var(--tn-bg);
}

.checkout-child-item img {
  width: 40px;
  height: 32px;
  object-fit: cover;
  border-radius: 4px;
}

.checkout-child-info {
  flex: 1;
  min-width: 0;
  text-align: left;
}

.checkout-child-info p {
  font-size: 11.5px;
  font-weight: 700;
  color: #334155;
  margin: 0 0 1px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.checkout-child-info span {
  font-size: 10px;
  color: #64748b;
  display: block;
}

.checkout-child-price {
  font-size: 11.5px;
  font-weight: 700;
  color: #475569;
}

.checkout-combo-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px dashed rgba(59, 130, 246, 0.15);
  padding-top: 8px;
  font-size: 12px;
}

.checkout-combo-footer span {
  color: #64748b;
  font-weight: 500;
}

.checkout-combo-footer b {
  font-size: 14px;
  font-weight: 800;
  color: #2563eb;
}

/* RESPONSIVE */
@media (max-width: 992px) {
  .container {
    flex-direction: column;
    gap: 20px;
    padding: 24px 16px;
  }
  .right {
    width: 100%;
  }
}

@media (max-width: 576px) {
  .container {
    padding: 16px 10px;
    gap: 16px;
  }
  .box {
    padding: 16px;
  }
  .form-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .pay-item {
    grid-template-columns: 24px 38px 1fr;
    gap: 10px;
    padding: 14px;
  }
  .pay-logo {
    width: 38px;
    height: 38px;
    font-size: 11px;
  }
}
</style>
