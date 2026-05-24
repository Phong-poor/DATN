<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  initialPosition: { type: Object, default: null },
  inline: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'selected', 'open'])

const mapEl = ref(null)
const loadingMap = ref(false)
const geocoding = ref(false)
const errorMessage = ref('')
const selectedLatLng = ref(null)

let L = null
let map = null
let marker = null

const defaultCenter = [16.047079, 108.20623]
const defaultZoom = 6

const close = () => {
  if (props.inline) return
  emit('update:modelValue', false)
}

const setMarker = (latlng, zoom = 16) => {
  selectedLatLng.value = { lat: latlng.lat, lng: latlng.lng }

  if (!marker) {
    marker = L.marker(latlng, { draggable: true }).addTo(map)
    marker.on('dragend', () => {
      selectedLatLng.value = marker.getLatLng()
    })
  } else {
    marker.setLatLng(latlng)
  }

  map.setView(latlng, zoom)
}

const setupDefaultIcon = () => {
  delete L.Icon.Default.prototype._getIconUrl
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
  })
}

const initMap = async () => {
  if (map) {
    await nextTick()
    setTimeout(() => map.invalidateSize(), 80)
    if (props.initialPosition?.lat && props.initialPosition?.lng) {
      setMarker(props.initialPosition)
    }
    return
  }

  loadingMap.value = true
  errorMessage.value = ''

  try {
    L = await import('leaflet')
    setupDefaultIcon()
    await nextTick()

    map = L.map(mapEl.value, {
      zoomControl: true,
      attributionControl: true,
    }).setView(defaultCenter, defaultZoom)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap',
    }).addTo(map)

    map.on('click', (event) => {
      setMarker(event.latlng)
    })

    setTimeout(() => map.invalidateSize(), 120)

    if (props.initialPosition?.lat && props.initialPosition?.lng) {
      setMarker(props.initialPosition)
    }
  } catch (error) {
    console.error('Lỗi tải bản đồ:', error)
    errorMessage.value = 'Không thể tải bản đồ. Vui lòng thử lại.'
  } finally {
    loadingMap.value = false
  }
}

const parseAddress = (data) => {
  const address = data.address || {}
  const province = address.province || address.state || address.city || address.municipality || address.region || ''
  const district = address.county || address.city_district || address.district || address.borough || address.town || ''
  const ward = address.ward || address.quarter || address.neighbourhood || address.suburb || address.village || address.hamlet || ''
  const detail = [address.house_number, address.road || address.pedestrian || address.residential].filter(Boolean).join(' ')

  return {
    province,
    district,
    ward,
    detail,
    fullAddress: data.display_name || [detail, ward, district, province].filter(Boolean).join(', '),
    latitude: selectedLatLng.value.lat,
    longitude: selectedLatLng.value.lng,
  }
}

const confirmLocation = async () => {
  if (!selectedLatLng.value) {
    errorMessage.value = 'Vui lòng click bản đồ hoặc lấy vị trí hiện tại để chọn điểm giao hàng.'
    return
  }

  geocoding.value = true
  errorMessage.value = ''

  try {
    const { lat, lng } = selectedLatLng.value
    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=vi`)

    if (!response.ok) {
      throw new Error('Không thể lấy địa chỉ từ vị trí đã chọn.')
    }

    const data = await response.json()

    if (!data?.display_name && !data?.address) {
      throw new Error('Không tìm thấy địa chỉ từ vị trí đã chọn.')
    }

    emit('selected', parseAddress(data))
    if (!props.inline) close()
  } catch (error) {
    console.error('Lỗi xác nhận vị trí:', error)
    errorMessage.value = error.message || 'Không thể lấy địa chỉ từ vị trí đã chọn.'
  } finally {
    geocoding.value = false
  }
}

watch(() => props.modelValue, (visible) => {
  if (visible) initMap()
})

watch(() => props.initialPosition, (position) => {
  if (position?.lat && position?.lng && map) {
    setMarker(position)
  }
})

watch(() => props.inline, (inline) => {
  if (inline) initMap()
})

onMounted(() => {
  if (props.inline) initMap()
})

onBeforeUnmount(() => {
  if (map) {
    map.remove()
    map = null
    marker = null
  }
})
</script>

<template>
  <div v-if="inline" class="map-picker-inline">
    <div class="map-picker-body inline-body">
      <div v-if="loadingMap" class="map-loading">Đang tải bản đồ...</div>
      <div ref="mapEl" class="map-picker-canvas inline-canvas"></div>
      <button type="button" class="map-view-button" @click="emit('open')">Xem bản đồ</button>
      <p v-if="errorMessage" class="map-error">{{ errorMessage }}</p>
    </div>
    <div class="map-picker-footer inline-footer">
      <button type="button" class="map-primary" :disabled="geocoding || loadingMap" @click="confirmLocation">
        {{ geocoding ? 'Đang xác nhận...' : 'Xác nhận vị trí' }}
      </button>
    </div>
  </div>
  <div v-else-if="modelValue" class="map-picker-backdrop" @click.self="close">
    <div class="map-picker-modal">
      <div class="map-picker-header">
        <div>
          <h3>Chọn vị trí giao hàng</h3>
          <p>Click bản đồ hoặc kéo ghim để chọn đúng vị trí</p>
        </div>
        <button type="button" class="map-picker-close" @click="close">×</button>
      </div>

      <div class="map-picker-body">
        <div v-if="loadingMap" class="map-loading">Đang tải bản đồ...</div>
        <div ref="mapEl" class="map-picker-canvas"></div>
        <div class="map-helper">
          <span>Vui lòng ghim địa chỉ chính xác để giao hàng thuận tiện hơn.</span>
        </div>
        <p v-if="errorMessage" class="map-error">{{ errorMessage }}</p>
      </div>

      <div class="map-picker-footer">
        <div class="map-footer-actions">
          <button type="button" class="map-secondary" @click="close">Trở lại</button>
          <button type="button" class="map-primary" :disabled="geocoding || loadingMap" @click="confirmLocation">
            {{ geocoding ? 'Đang xác nhận...' : 'Xác nhận vị trí' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.map-picker-backdrop { position:fixed; inset:0; z-index:9030; background:rgba(15,23,42,.55); display:flex; align-items:center; justify-content:center; padding:18px; }
.map-picker-inline { width:100%; border:1px solid #e5e7eb; border-radius:4px; overflow:hidden; background:#fff; }
.map-picker-modal { width:min(760px,100%); background:#fff; border-radius:18px; overflow:hidden; box-shadow:0 24px 60px rgba(15,23,42,.28); }
.map-picker-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:20px 22px 12px; }
.map-picker-header h3 { margin:0; font-size:20px; color:#0f172a; }
.map-picker-header p { margin:5px 0 0; font-size:13px; color:#64748b; }
.map-picker-close { width:32px; height:32px; border:none; border-radius:50%; background:#f1f5f9; color:#64748b; font-size:24px; line-height:1; cursor:pointer; }
.map-picker-body { position:relative; padding:0 22px 14px; }
.map-picker-canvas { width:100%; height:360px; border-radius:14px; overflow:hidden; background:#e2e8f0; z-index:1; }
.inline-body { padding:0; }
.inline-canvas { height:170px; border-radius:0; }
.map-view-overlay { position:absolute; inset:0 22px 14px; z-index:2; display:flex; align-items:center; justify-content:center; background:rgba(15,23,42,.42); color:#fff; font-weight:700; opacity:0; pointer-events:none; transition:opacity .18s ease; border-radius:14px; }
.inline-body .map-view-overlay { inset:0; border-radius:0; }
.map-picker-body:hover .map-view-overlay { opacity:1; }
.map-loading { position:absolute; inset:0 22px 14px; z-index:3; display:flex; align-items:center; justify-content:center; border-radius:14px; background:rgba(248,250,252,.86); color:#2563eb; font-weight:700; }
.map-helper { margin-top:12px; padding:12px 14px; border-radius:12px; background:#fff7ed; color:#9a3412; font-size:13px; }
.map-error { margin:10px 0 0; color:#dc2626; font-size:13px; }
.map-picker-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:0 22px 22px; }
.inline-footer { padding:8px; justify-content:flex-end; background:#f8fafc; }
.map-footer-actions { display:flex; gap:10px; }
.map-primary,
.map-secondary { height:42px; border-radius:10px; padding:0 18px; font-weight:700; cursor:pointer; }
.map-primary { border:none; background:#ee4d2d; color:#fff; }
.map-secondary { border:1px solid #e2e8f0; background:#fff; color:#475569; }
.inline-footer .map-primary,
.inline-footer .map-secondary { height:32px; border-radius:4px; padding:0 12px; font-size:12px; }
.map-primary:disabled,
.map-secondary:disabled { opacity:.65; cursor:not-allowed; }
@media (max-width:640px) {
  .map-picker-canvas { height:300px; }
  .map-picker-footer { flex-direction:column; align-items:stretch; }
  .map-footer-actions { width:100%; }
  .map-footer-actions button,
  .map-picker-footer > button { flex:1; }
}
</style>
