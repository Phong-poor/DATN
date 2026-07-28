import api from './api';

const ADDRESS_API_BASE_URL = 'https://provinces.open-api.vn/api/v2';

let provincesCache = null;
const wardsCache = new Map();

const normalizeApiList = (data, keys = []) => {
  if (Array.isArray(data)) return data;
  for (const key of keys) {
    if (Array.isArray(data?.[key])) return data[key];
  }
  if (Array.isArray(data?.data)) return data.data;
  if (Array.isArray(data?.results)) return data.results;
  return [];
};

const fetchJson = async (url) => {
  const response = await fetch(url, {
    headers: { Accept: 'application/json' },
  });
  if (!response.ok) {
    throw new Error(`Address API returned HTTP ${response.status}`);
  }
  return response.json();
};

export const fetchProvinces = async () => {
  if (provincesCache) return provincesCache;
  const data = await fetchJson(`${ADDRESS_API_BASE_URL}/p/`);
  provincesCache = normalizeApiList(data, ['provinces']);
  return provincesCache;
};

export const fetchWardsByProvince = async (provinceCode) => {
  if (!provinceCode) return [];
  const cacheKey = String(provinceCode);
  if (wardsCache.has(cacheKey)) return wardsCache.get(cacheKey);

  const data = await fetchJson(`${ADDRESS_API_BASE_URL}/p/${provinceCode}?depth=2`);
  const directWards = normalizeApiList(data, ['wards']);
  const districts = normalizeApiList(data, ['districts']);
  const wards = directWards.length
    ? directWards
    : districts.flatMap((district) => normalizeApiList(district, ['wards']).map((ward) => ({
      ...ward,
      districtName: district.name,
    })));

  wardsCache.set(cacheKey, wards);
  return wards;
};

export const searchAddressSuggestions = async ({ detail, ward, province, signal }) => {
  const query = [detail, ward, province, 'Việt Nam'].filter(Boolean).join(', ');
  if (query.trim().length < 3) return [];

  const response = await api.get('/address/suggestions', {
    params: { q: query, ward: ward || '', province: province || '' },
    signal,
  });
  return normalizeApiList(response.data);
};

export const geocodeAddress = async ({ detail, ward, province }) => {
  const query = [detail, ward, province, 'Việt Nam'].filter(Boolean).join(', ');
  if (query.trim().length < 3) return null;

  const response = await api.get('/address/geocode', { params: { q: query } });
  const lat = Number(response.data?.lat);
  const lng = Number(response.data?.lng);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
  return { latitude: lat, longitude: lng };
};

export const reverseGeocodeLocation = async (latitude, longitude) => {
  const response = await api.get('/address/reverse', { params: { lat: latitude, lng: longitude } });
  return response.data || null;
};

export const findAddressCodeByName = (items, name) => {
  const normalize = (value = '') => value
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/^(tinh|thanh pho|tp|quan|huyen|thi xa|phuong|xa|thi tran)\s+/i, '')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase();

  const target = normalize(name);
  if (!target) return '';
  return items.find((item) => {
    const candidate = normalize(item?.name);
    return candidate === target || candidate.includes(target) || target.includes(candidate);
  })?.code || '';
};
