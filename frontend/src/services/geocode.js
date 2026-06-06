import api from '@/services/api';

/**
 * Làm sạch chuỗi query
 */
const cleanQuery = (q) => {
  if (!q) return '';
  return q.replace(/,\s*,/g, ',').replace(/^[,\s]+|[,\s]+$/g, '').replace(/,\s*Việt Nam$/i, ', Việt Nam');
};

/**
 * Xóa phần số nhà khỏi chuỗi địa chỉ chi tiết
 */
const removeHouseNumber = (detail) => {
  if (!detail) return '';
  return detail.replace(/^Số\s*\d+[a-zA-Z-]*\s*|^\d+[a-zA-Z-]*\s*/i, '').trim();
};

/**
 * Lấy mảng gợi ý danh sách địa chỉ (Autocomplete) thông qua Backend API
 */
export const searchSuggestions = async (query, signal = null, extraParams = {}) => {
  const qStr = cleanQuery(query);
  if (!qStr || qStr.length < 3) return [];
  
  try {
    const config = { params: { q: qStr, ...extraParams } };
    if (signal) {
      config.signal = signal;
    }
    const response = await api.get('/address/suggestions', config);
    if (response && response.data) {
      return response.data;
    }
  } catch (error) {
    if (error.name === 'CanceledError' || (error.code === 'ERR_CANCELED')) {
      return [];
    }
  }
  return [];
};

/**
 * Geocode chung (trả về tọa độ từ chuỗi địa chỉ)
 */
export const fetchGeocode = async (query, signal = null) => {
  const qStr = cleanQuery(query);
  if (!qStr || qStr.length < 3) return null;

  try {
    const config = { params: { q: qStr } };
    if (signal) {
      config.signal = signal;
    }
    const response = await api.get('/address/geocode', config);
    if (response && response.data) {
      return response.data;
    }
  } catch (error) {
    if (error.name === 'CanceledError' || (error.code === 'ERR_CANCELED')) return null;
  }
  return null;
}

/**
 * Geocode khu vực (Tỉnh/Phường)
 */
export const geocodeArea = async (ward, district, province, signal = null) => {
  const query = [ward, district, province, 'Việt Nam'].filter(Boolean).join(', ');
  const result = await fetchGeocode(query, signal);
  if (result) {
    return { lat: result.lat, lng: result.lng, ...result };
  }
  return null;
};

/**
 * Geocode với Fallback logic. 
 * Chỉ gửi 1 request đầy đủ duy nhất lên Backend, Backend tự xử lý fallback.
 */
export const geocodeWithFallback = async (detail, ward, district, province) => {
  const fullDetail = [detail, ward, district, province].filter(Boolean).join(', ');
  if (!fullDetail) {
    return { lat: null, lng: null, accuracy: 'failed', provider: null };
  }

  const qStr = cleanQuery(`${fullDetail}, Việt Nam`);
  if (!qStr) {
    return { lat: null, lng: null, accuracy: 'failed', provider: null };
  }

  try {
    const result = await fetchGeocode(qStr);
    if (result && Number.isFinite(Number(result.lat)) && Number.isFinite(Number(result.lng))) {
      const accuracy = result.accuracy || result.accuracy_level || 'unknown';
      return { 
        lat: Number(result.lat), 
        lng: Number(result.lng), 
        accuracy, 
        provider: result.provider || result.source || null,
        message: result.message || undefined,
        geojson: result.geojson,
        boundingbox: result.boundingbox,
        raw: result.raw
      };
    }
  } catch (error) {
    console.error('Lỗi gọi API geocode:', error);
  }

  return { lat: null, lng: null, accuracy: 'failed', provider: null };
};

/**
 * Reverse Geocode (Tọa độ -> Địa chỉ)
 */
export const reverseGeocodeLocation = async (lat, lng, signal = null) => {
  try {
    const config = { params: { lat, lng } };
    if (signal) {
      config.signal = signal;
    }
    const response = await api.get('/address/reverse', config);
    if (response && response.data) {
      return response.data;
    }
  } catch (error) {
    if (error.name === 'CanceledError' || (error.code === 'ERR_CANCELED')) return null;
  }
  return null;
};
