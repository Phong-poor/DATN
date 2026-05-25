<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../../services/api'
import { getUser } from '@/services/auth'
import swal from '@/services/swal'
import AddressMapPicker from './AddressMapPicker.vue'



const router = useRouter()
const route = useRoute()

const promoCode = ref(route.query.promo_code || '')
const discount = ref(Number(route.query.discount) || 0)
const freeshipCode = ref(route.query.freeship_code || '')
const freeshipDiscount = ref(Number(route.query.freeship_discount) || 0)
const shippingFee = ref(30000)

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
    const parts = [addressForm.value.phuong_xa, addressForm.value.quan_huyen, addressForm.value.tinh_thanhpho]
        .filter((item) => item && item !== 'Không xác định')
    const query = [...parts, 'Việt Nam'].join(', ')
    if (!query.trim()) return null

    locatingSelectedArea.value = true
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=vn&q=${encodeURIComponent(query)}`)
        const data = await res.json()
        let location = data?.[0]
        if (!location && addressForm.value.tinh_thanhpho) {
            const provinceRes = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=vn&q=${encodeURIComponent(`${addressForm.value.tinh_thanhpho}, Việt Nam`)}`)
            const provinceData = await provinceRes.json()
            location = provinceData?.[0]
        }
        if (!location) return null

        return { lat: Number(location.lat), lng: Number(location.lon) }
    } catch (error) {
        console.error('Lỗi tìm vị trí khu vực:', error)
        return null
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
            cart.value = response.data.gio_hang.map(item => ({
                id_giohang: item.id_giohang,
                name: getFullProductName(item),
                desc: item.ten_bienthe,
                price: item.gia,
                qty: item.soluong,
                img: item.hinh_anh || 'https://via.placeholder.com/200'
            }))
        }
    } catch (error) {
        console.error('Lỗi khi tải giỏ hàng:', error)
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchCart()
    fetchAddresses()

    // Tự động điền thông tin người dùng nếu đã đăng nhập
    const user = getUser()
    if (user) {
        form.value.name = user.name || user.ten || ''
        form.value.email = user.email || ''
        form.value.phone = user.phone || user.sdt || ''
    }
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

const confirmOrder = async () => {
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
            PTTT: payment.value === 'cod' ? 'COD' : (payment.value === 'bank' ? 'Chuyển khoản' : 'Ví điện tử'),
            promo_code: promoCode.value,
            freeship_code: freeshipCode.value
        })

        if (response.data.success) {
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
            <input v-model="form.name" placeholder="Họ và tên" readonly class="readonly-input" />
            <input v-model="form.phone" placeholder="Số điện thoại" readonly class="readonly-input" />
          </div>

          <input v-model="form.email" placeholder="Email" readonly class="readonly-input" />

          <div class="address-list" v-if="addresses.length || loadingAddresses">
            <div class="address-header">
              <p class="address-title">Địa chỉ giao hàng</p>
              <div class="address-actions">
                <button type="button" class="address-link" @click="addresses.length ? openAddressModal() : openAddAddressModal()">
                  {{ addresses.length ? 'Thay đổi' : 'Thêm địa chỉ mới' }}
                </button>
              </div>
            </div>
            <p v-if="loadingAddresses" class="address-loading">Đang tải địa chỉ...</p>
            <div v-else-if="selectedAddress" class="address-card active selected-address">
              <div>
                <b style="line-height: 1.4; display: block; margin-bottom: 4px;">{{ formatAddress(selectedAddress) }}</b>
                <span style="color: #64748b; font-size: 13px;">{{ selectedAddress.loai_diachi === 'company' ? 'Công ty' : 'Nhà riêng' }}</span>
                <span v-if="selectedAddress.mac_dinh" class="default-tag">Mặc định</span>
              </div>
            </div>
          </div>

          <textarea v-model="form.address" placeholder="Địa chỉ nhận hàng"></textarea>
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
              <div class="pay-text">
                <b>COD (Thanh toán khi nhận hàng)</b>
                <p>Thanh toán tiền mặt khi nhận hàng</p>
              </div>
            </label>

            <label class="pay-item" :class="{ active: payment === 'momo' }">
              <input type="radio" value="momo" v-model="payment" />
              <div class="radio"></div>
              <div class="pay-text">
                <b>Ví điện tử VNPay</b>
                <p>Thanh toán nhanh qua ví điện tử</p>
              </div>
            </label>

          </div>
        </div>
      </div>

      <!-- RIGHT -->
      <div class="right">
        <div class="summary">
          <h3>Tóm tắt đơn hàng</h3>

          <div class="item" v-for="(i, index) in cart" :key="index">
            <img :src="i.img" />
            <div>
              <p>{{ i.name }}</p>
              <span>{{ i.desc }}</span>
            </div>
            <b>{{ format(i.price) }}</b>
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

  <div v-if="showAddressModal" class="modal-backdrop" @click.self="showAddressModal = false">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Chọn địa chỉ giao hàng</h3>
        <button type="button" class="modal-close" @click="showAddressModal = false">×</button>
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
            <span style="color: #64748b; font-size: 13px;">{{ addr.loai_diachi === 'company' ? 'Công ty' : 'Nhà riêng' }}</span>
            <span v-if="addr.mac_dinh" class="default-tag">Mặc định</span>
          </div>
          <button type="button" class="address-update-btn" @click.stop.prevent="openEditAddressModal(addr)">Cập nhật</button>
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-secondary" @click="openAddAddressModal">Thêm địa chỉ mới</button>
      </div>
    </div>
  </div>

  <div v-if="showAddAddressModal" class="modal-backdrop" @click.self="showAddAddressModal = false">
    <div class="modal-box">
      <div class="modal-header">
        <h3>{{ editingAddressId ? 'Cập nhật địa chỉ' : 'Thêm địa chỉ mới' }}</h3>
        <button type="button" class="modal-close" @click="showAddAddressModal = false">×</button>
      </div>
      <form class="modal-body add-address-form" @submit.prevent="saveNewAddress">
        <div class="checkout-form-group checkout-form-full">
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
        <div class="checkout-form-group checkout-form-full">
          <label>Địa chỉ chi tiết</label>
          <input v-model="addressForm.diachi_cuthe" placeholder="Số nhà, tên đường..." required />
        </div>
        <div class="inline-map-field">
          <AddressMapPicker inline :initial-position="mapInitialPosition" @selected="applyMapAddress" @open="openMapPicker" />
          <small v-if="locatingSelectedArea">Đang tìm vị trí khu vực...</small>
          <small v-else-if="addressForm.full_address">{{ addressForm.full_address }}</small>
        </div>
        <div class="checkout-form-group">
          <label>Loại địa chỉ</label>
          <select v-model="addressForm.loai_diachi" required>
            <option value="home">Nhà riêng</option>
            <option value="company">Công ty</option>
          </select>
        </div>
        <label class="modal-checkbox">
          <input type="checkbox" v-model="addressForm.mac_dinh" />
          <span>Đặt làm địa chỉ mặc định</span>
        </label>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="showAddAddressModal = false">Hủy</button>
          <button type="submit" class="btn-primary" :disabled="savingAddress">{{ savingAddress ? 'Đang lưu...' : 'Hoàn thành' }}</button>
        </div>
      </form>
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
  max-width: 1200px;
  margin: auto;
  padding: 40px 24px;
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 30px;
}

/* TEXT */
.subtitle {
  color: #64748b;
  margin-bottom: 20px;
}

/* BOX */
.box {
  background: white;
  padding: 24px;
  border-radius: 14px;
  margin-bottom: 20px;
}

.box-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  margin-bottom: 16px;
}

.step {
  width: 28px;
  height: 28px;
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
  gap: 12px;
  margin-bottom: 12px;
}

/* INPUT */
input {
  width: 100%;
  height: 44px;
  padding: 0 14px;
  border: none;
  background: #f1f5f9;
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
  background: #f1f5f9;
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
  background: #f1f5f9;
  border-radius: 10px;
  font-size: 14px;
  box-sizing: border-box;

  margin-top: 10px;
  resize: none;
}

textarea {
  height: 120px;
  padding-top: 12px;
  resize: none;
}

.address-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin: 12px 0;
}

.address-title {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
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

.address-loading {
  margin: 0;
  color: #64748b;
  font-size: 13px;
}

.address-card {
  display: grid;
  grid-template-columns: 20px 1fr auto;
  gap: 10px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
  cursor: pointer;
}

.address-card.active {
  border-color: #2563eb;
  background: #eff6ff;
}

.selected-address {
  grid-template-columns: 1fr;
  cursor: default;
}

.address-update-btn {
  border: none;
  background: transparent;
  color: #2563eb;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  white-space: nowrap;
}

.address-choices {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.address-card input {
  width: 16px;
  height: 16px;
  margin: 2px 0 0;
}

.address-card b {
  display: block;
  font-size: 13px;
  color: #0f172a;
}

.address-card p {
  margin: 4px 0;
  font-size: 13px;
  color: #475569;
  line-height: 1.4;
}

.address-card span {
  display: inline-block;
  margin-right: 6px;
  font-size: 11px;
  color: #2563eb;
  background: #dbeafe;
  padding: 3px 8px;
  border-radius: 999px;
}

.address-card .default-tag {
  color: #15803d;
  background: #dcfce7;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.45);
}

.modal-box {
  width: min(620px, 100%);
  max-height: 90vh;
  overflow: auto;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.25);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: #f1f5f9;
  color: #334155;
  font-size: 22px;
  cursor: pointer;
}

.modal-body {
  padding: 20px;
}

.add-address-form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.add-address-form input,
.add-address-form select {
  margin-bottom: 0;
}

.checkout-form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.checkout-form-group label,
.region-picker-field label {
  color: #374151;
  font-size: 13px;
  font-weight: 600;
}

.checkout-form-full {
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

.modal-checkbox,
.modal-footer,
.map-placeholder,
.inline-map-field {
  grid-column: 1 / -1;
}

.inline-map-field small {
  display: block;
  margin-top: 6px;
  color: #64748b;
  font-size: 12px;
}

.map-placeholder {
  min-height: 88px;
  width: 100%;
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
  background: linear-gradient(135deg,#f8fafc 25%,#f1f5f9 25%,#f1f5f9 50%,#f8fafc 50%,#f8fafc 75%,#f1f5f9 75%);
  background-size: 32px 32px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  font-weight: 700;
}

.map-placeholder small {
  max-width: 90%;
  color: #94a3b8;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.map-placeholder:disabled {
  opacity: .65;
  cursor: not-allowed;
}

.modal-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #334155;
}

.modal-checkbox input {
  width: 16px;
  height: 16px;
  margin: 0;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 0 20px 20px;
}

.add-address-form .modal-footer {
  padding: 8px 0 0;
}

.btn-secondary,
.btn-primary {
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary {
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #334155;
}

.btn-primary {
  border: 1px solid #2563eb;
  background: #2563eb;
  color: #fff;
}

/* PAYMENT */
.pay-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.pay-item {
  display: grid;
  grid-template-columns: 28px 1fr;
  align-items: center;
  gap: 14px;
  padding: 16px;
  border-radius: 12px;
  background: #f8fafc;
  cursor: pointer;
}

.pay-item.active {
  background: #e0ecff;
  border: 1px solid #2563eb;
}

.pay-item input {
  display: none;
}

.radio {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid #94a3b8;
  position: relative;
}

.pay-item.active .radio {
  border-color: #2563eb;
}

.pay-item.active .radio::after {
  content: '';
  width: 12px;
  height: 12px;
  background: #2563eb;
  border-radius: 50%;
  position: absolute;
  top: 3px;
  left: 3px;
}

.pay-text p {
  font-size: 12px;
  color: #64748b;
}

/* RIGHT */
.summary {
  background: #eef2ff;
  padding: 20px;
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
  margin-top: 15px;
  width: 100%;
  padding: 12px;
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

/* RESPONSIVE */
@media (max-width: 768px) {
  .container {
    grid-template-columns: 1fr;
    padding: 24px 16px;
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
}
</style>
