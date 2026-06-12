const addressApiBaseUrl = 'https://provinces.open-api.vn/api/v2';

let provincesCache = null;
const wardsCache = new Map();

/**
 * Chuẩn hóa danh sách dữ liệu từ API
 */
const normalizeApiList = (data, keys = []) => {
    if (Array.isArray(data)) return data;

    for (const key of keys) {
        if (Array.isArray(data?.[key])) return data[key];
    }

    if (Array.isArray(data?.data)) return data.data;
    if (Array.isArray(data?.results)) return data.results;
    return [];
};

/**
 * Tải danh sách Tỉnh/Thành phố (Có cache JS thuần)
 */
export const fetchProvinces = async () => {
    if (provincesCache) {
        return provincesCache;
    }

    try {
        const res = await fetch(`${addressApiBaseUrl}/p/`);
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const data = await res.json();
        provincesCache = normalizeApiList(data, ['provinces']);
        return provincesCache;
    } catch (error) {
        console.error('Lỗi tải tỉnh/thành từ service:', error);
        throw error;
    }
};

/**
 * Tải danh sách Phường/Xã dẹt phẳng của một Tỉnh/Thành (Có cache JS thuần theo provinceCode)
 */
export const fetchWardsByProvince = async (provinceCode) => {
    if (!provinceCode) {
        return [];
    }

    const cacheKey = String(provinceCode);
    if (wardsCache.has(cacheKey)) {
        return wardsCache.get(cacheKey);
    }

    try {
        const res = await fetch(`${addressApiBaseUrl}/p/${provinceCode}?depth=2`);
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const data = await res.json();
        const districts = normalizeApiList(data, ['districts']);
        const directWards = normalizeApiList(data, ['wards']);
        
        const wardsList = directWards.length
            ? directWards
            : districts.flatMap((district) => 
                normalizeApiList(district, ['wards']).map((ward) => ({
                    ...ward,
                    districtName: district.name,
                }))
            );

        wardsCache.set(cacheKey, wardsList);
        return wardsList;
    } catch (error) {
        console.error(`Lỗi tải phường/xã của tỉnh ${provinceCode} từ service:`, error);
        throw error;
    }
};
