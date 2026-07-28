import React, { useEffect, useState, useCallback } from 'react';
import {
  StyleSheet, Text, View, FlatList, TextInput, TouchableOpacity,
  ActivityIndicator, Alert, Modal, ScrollView, KeyboardAvoidingView, Platform,
  RefreshControl,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import MapView, { Marker } from 'react-native-maps';
import * as Location from 'expo-location';
import { COLORS, RADIUS, SPACING, TYPOGRAPHY } from '../utils/theme';
import api from '../services/api';
import useAuthStore from '../store/useAuthStore';
import {
  fetchProvinces,
  fetchWardsByProvince,
  findAddressCodeByName,
  geocodeAddress,
  reverseGeocodeLocation,
  searchAddressSuggestions,
} from '../services/addressService';
import logger from '../utils/logger';

const LOAI_DIA_CHI = [
  { value: 'home', label: '🏠 Nhà riêng', icon: 'home-outline' },
  { value: 'company', label: '🏢 Công ty', icon: 'business-outline' },
];

const emptyForm = {
  tinh_thanhpho: '',
  phuong_xa: '',
  diachi_cuthe: '',
  latitude: null,
  longitude: null,
  loai_diachi: 'home',
  mac_dinh: false,
};

function AddressCard({ address, onEdit, onDelete, onSetDefault }) {
  return (
    <View style={[styles.card, address.mac_dinh && styles.cardDefault]}>
      {address.mac_dinh && (
        <View style={styles.defaultBanner}>
          <Ionicons name="checkmark-circle" size={13} color={COLORS.primary} />
          <Text style={styles.defaultBannerText}>Địa chỉ mặc định</Text>
        </View>
      )}
      <View style={styles.cardHeader}>
        <View style={styles.typeIconWrap}>
          <Ionicons
            name={address.loai_diachi === 'company' ? 'business-outline' : 'home-outline'}
            size={20} color={COLORS.primary}
          />
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.cardType}>
            {address.loai_diachi === 'company' ? 'Công ty' : 'Nhà riêng'}
          </Text>
          <Text style={styles.cardAddr} numberOfLines={2}>
            {address.dia_chi_day_du || [address.diachi_cuthe, address.phuong_xa, address.tinh_thanhpho]
              .filter(Boolean).join(', ')}
          </Text>
        </View>
      </View>

      <View style={styles.cardActions}>
        {!address.mac_dinh && (
          <TouchableOpacity style={styles.actionBtn} onPress={() => onSetDefault(address)}>
            <Ionicons name="pin-outline" size={15} color={COLORS.primary} />
            <Text style={[styles.actionBtnText, { color: COLORS.primary }]}>Đặt mặc định</Text>
          </TouchableOpacity>
        )}
        <TouchableOpacity style={styles.actionBtn} onPress={() => onEdit(address)}>
          <Ionicons name="create-outline" size={15} color={COLORS.textSecondary} />
          <Text style={styles.actionBtnText}>Chỉnh sửa</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.actionBtn} onPress={() => onDelete(address)}>
          <Ionicons name="trash-outline" size={15} color={COLORS.error} />
          <Text style={[styles.actionBtnText, { color: COLORS.error }]}>Xóa</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

function AddressFormModal({ visible, address, onClose, onSaved }) {
  const [form, setForm] = useState(emptyForm);
  const [saving, setSaving] = useState(false);
  const [provinces, setProvinces] = useState([]);
  const [wards, setWards] = useState([]);
  const [selectedProvinceCode, setSelectedProvinceCode] = useState('');
  const [selectedWardCode, setSelectedWardCode] = useState('');
  const [loadingProvinces, setLoadingProvinces] = useState(false);
  const [loadingWards, setLoadingWards] = useState(false);
  const [showTinhList, setShowTinhList] = useState(false);
  const [showWardList, setShowWardList] = useState(false);
  const [tinhFilter, setTinhFilter] = useState('');
  const [wardFilter, setWardFilter] = useState('');
  const [suggestions, setSuggestions] = useState([]);
  const [searchingAddress, setSearchingAddress] = useState(false);
  const [geocoding, setGeocoding] = useState(false);
  const [locating, setLocating] = useState(false);

  useEffect(() => {
    if (address) {
      setForm({
        tinh_thanhpho: address.tinh_thanhpho || '',
        phuong_xa: address.phuong_xa || '',
        diachi_cuthe: address.diachi_cuthe || '',
        latitude: address.latitude ?? null,
        longitude: address.longitude ?? null,
        loai_diachi: address.loai_diachi || 'home',
        mac_dinh: Boolean(address.mac_dinh),
      });
    } else {
      setForm({ ...emptyForm });
    }
    setShowTinhList(false);
    setShowWardList(false);
    setTinhFilter('');
    setWardFilter('');
    setSuggestions([]);
  }, [address, visible]);

  useEffect(() => {
    if (!visible) return;
    let active = true;

    const loadInitialAddressData = async () => {
      setLoadingProvinces(true);
      try {
        const provinceList = await fetchProvinces();
        if (!active) return;
        setProvinces(provinceList);

        const provinceCode = findAddressCodeByName(provinceList, address?.tinh_thanhpho);
        setSelectedProvinceCode(String(provinceCode || ''));
        if (provinceCode) {
          setLoadingWards(true);
          const wardList = await fetchWardsByProvince(provinceCode);
          if (!active) return;
          setWards(wardList);
          setSelectedWardCode(String(findAddressCodeByName(wardList, address?.phuong_xa) || ''));
        } else {
          setWards([]);
          setSelectedWardCode('');
        }
      } catch (error) {
        logger.log('Failed to load province/ward data:', error);
        Alert.alert('Lỗi địa chỉ', 'Không thể tải danh sách tỉnh/phường. Vui lòng kiểm tra kết nối mạng.');
      } finally {
        if (active) {
          setLoadingProvinces(false);
          setLoadingWards(false);
        }
      }
    };

    loadInitialAddressData();
    return () => { active = false; };
  }, [address, visible]);

  useEffect(() => {
    if (!visible || form.diachi_cuthe.trim().length < 3 || !form.tinh_thanhpho) {
      setSuggestions([]);
      return undefined;
    }

    const controller = new AbortController();
    const timeout = setTimeout(async () => {
      setSearchingAddress(true);
      try {
        const results = await searchAddressSuggestions({
          detail: form.diachi_cuthe.trim(),
          ward: form.phuong_xa,
          province: form.tinh_thanhpho,
          signal: controller.signal,
        });
        setSuggestions(results.filter((item) => item?.lat && item?.lng).slice(0, 5));
      } catch (error) {
        if (error.code !== 'ERR_CANCELED') logger.log('Address suggestion failed:', error);
        setSuggestions([]);
      } finally {
        setSearchingAddress(false);
      }
    }, 700);

    return () => {
      clearTimeout(timeout);
      controller.abort();
    };
  }, [form.diachi_cuthe, form.phuong_xa, form.tinh_thanhpho, visible]);

  const setField = (key, val) => setForm(f => ({ ...f, [key]: val }));

  const handleProvinceSelect = async (province) => {
    setSelectedProvinceCode(String(province.code));
    setSelectedWardCode('');
    setForm((current) => ({
      ...current,
      tinh_thanhpho: province.name,
      phuong_xa: '',
      latitude: null,
      longitude: null,
    }));
    setShowTinhList(false);
    setShowWardList(false);
    setTinhFilter('');
    setLoadingWards(true);
    try {
      setWards(await fetchWardsByProvince(province.code));
    } catch (error) {
      logger.log('Failed to load wards:', error);
      setWards([]);
      Alert.alert('Lỗi địa chỉ', 'Không thể tải danh sách phường/xã.');
    } finally {
      setLoadingWards(false);
    }
  };

  const handleWardSelect = (ward) => {
    setSelectedWardCode(String(ward.code));
    setForm((current) => ({
      ...current,
      phuong_xa: ward.name,
      latitude: null,
      longitude: null,
    }));
    setShowWardList(false);
    setWardFilter('');
  };

  const applySuggestion = (item) => {
    setForm((current) => ({
      ...current,
      diachi_cuthe: item.title || item.display_name || current.diachi_cuthe,
      latitude: Number(item.lat),
      longitude: Number(item.lng),
    }));
    setSuggestions([]);
  };

  const applyCoordinates = async (latitude, longitude) => {
    setForm((current) => ({ ...current, latitude, longitude }));
    setGeocoding(true);
    try {
      const result = await reverseGeocodeLocation(latitude, longitude);
      if (!result) return;
      setForm((current) => ({
        ...current,
        latitude,
        longitude,
        tinh_thanhpho: result.province || current.tinh_thanhpho,
        phuong_xa: result.ward || current.phuong_xa,
        diachi_cuthe: result.display_name || result.address || current.diachi_cuthe,
      }));
      if (result.province) {
        const provinceList = provinces.length ? provinces : await fetchProvinces();
        const provinceCode = findAddressCodeByName(provinceList, result.province);
        if (provinceCode) {
          setSelectedProvinceCode(String(provinceCode));
          const wardList = await fetchWardsByProvince(provinceCode);
          setWards(wardList);
          setSelectedWardCode(String(findAddressCodeByName(wardList, result.ward) || ''));
        }
      }
    } catch (error) {
      logger.log('Reverse geocode failed:', error);
    } finally {
      setGeocoding(false);
    }
  };

  const useCurrentLocation = async () => {
    setLocating(true);
    try {
      const permission = await Location.requestForegroundPermissionsAsync();
      if (permission.status !== 'granted') {
        Alert.alert('Cần quyền vị trí', 'Vui lòng cho phép ứng dụng truy cập vị trí để chọn địa chỉ hiện tại.');
        return;
      }
      const position = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
      await applyCoordinates(position.coords.latitude, position.coords.longitude);
    } catch (error) {
      Alert.alert('Lỗi vị trí', 'Không thể lấy vị trí hiện tại. Vui lòng bật GPS và thử lại.');
    } finally {
      setLocating(false);
    }
  };

  const ensureCoordinates = async () => {
    if (Number.isFinite(Number(form.latitude)) && Number.isFinite(Number(form.longitude))) {
      return { latitude: Number(form.latitude), longitude: Number(form.longitude) };
    }
    setGeocoding(true);
    try {
      return await geocodeAddress({
        detail: form.diachi_cuthe.trim(),
        ward: form.phuong_xa,
        province: form.tinh_thanhpho,
      });
    } catch (error) {
      logger.log('Address geocode failed:', error);
      return null;
    } finally {
      setGeocoding(false);
    }
  };

  const handleSave = async () => {
    if (!form.tinh_thanhpho.trim()) { Alert.alert('Lỗi', 'Vui lòng chọn tỉnh/thành phố.'); return; }
    if (!form.phuong_xa.trim()) { Alert.alert('Lỗi', 'Vui lòng nhập phường/xã.'); return; }
    if (!form.diachi_cuthe.trim() || form.diachi_cuthe.trim().length < 5) {
      Alert.alert('Lỗi', 'Địa chỉ cụ thể phải từ 5 ký tự trở lên.');
      return;
    }

    setSaving(true);
    try {
      const coordinates = await ensureCoordinates();
      const payload = {
        tinh_thanhpho: form.tinh_thanhpho.trim(),
        phuong_xa: form.phuong_xa.trim(),
        diachi_cuthe: form.diachi_cuthe.trim(),
        latitude: coordinates?.latitude ?? null,
        longitude: coordinates?.longitude ?? null,
        loai_diachi: form.loai_diachi,
        mac_dinh: Boolean(form.mac_dinh),
      };

      if (address?.id_diachi) {
        await api.put(`/user/dia-chi/${address.id_diachi}`, payload);
        Alert.alert('Thành công', 'Đã cập nhật địa chỉ!');
      } else {
        await api.post('/user/dia-chi', payload);
        Alert.alert('Thành công', 'Đã thêm địa chỉ mới!');
      }
      onSaved();
      onClose();
    } catch (err) {
      const validationMessage = Object.values(err.response?.data?.errors || {}).flat()[0];
      const msg = validationMessage || err.response?.data?.message || 'Không thể lưu địa chỉ.';
      Alert.alert('Lỗi', msg);
    } finally {
      setSaving(false);
    }
  };

  const filteredTinh = provinces.filter((item) => item.name?.toLowerCase().includes(tinhFilter.toLowerCase()));
  const filteredWards = wards.filter((item) => item.name?.toLowerCase().includes(wardFilter.toLowerCase()));

  return (
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            {/* Modal header */}
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>{address ? 'Sửa địa chỉ' : 'Thêm địa chỉ mới'}</Text>
              <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
                <Ionicons name="close" size={22} color={COLORS.textPrimary} />
              </TouchableOpacity>
            </View>

            <ScrollView showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
              {/* Loại địa chỉ */}
              <Text style={styles.fieldLabel}>Loại địa chỉ</Text>
              <View style={styles.typeRow}>
                {LOAI_DIA_CHI.map(lt => (
                  <TouchableOpacity
                    key={lt.value}
                    style={[styles.typeOption, form.loai_diachi === lt.value && styles.typeOptionActive]}
                    onPress={() => setField('loai_diachi', lt.value)}
                  >
                    <Ionicons name={lt.icon} size={18} color={form.loai_diachi === lt.value ? COLORS.primary : COLORS.textSecondary} />
                    <Text style={[styles.typeOptionText, form.loai_diachi === lt.value && { color: COLORS.primary, fontWeight: '700' }]}>
                      {lt.label}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>

              {/* Tỉnh/thành */}
              <Text style={styles.fieldLabel}>Tỉnh/Thành phố <Text style={styles.required}>*</Text></Text>
              <TouchableOpacity
                style={styles.selectField}
                onPress={() => setShowTinhList((current) => !current)}
                disabled={loadingProvinces}
              >
                <Text style={form.tinh_thanhpho ? styles.selectFieldValue : styles.selectFieldPlaceholder}>
                  {loadingProvinces ? 'Đang tải tỉnh/thành...' : (form.tinh_thanhpho || 'Chọn tỉnh/thành phố')}
                </Text>
                <Ionicons name="chevron-down" size={16} color={COLORS.textTertiary} />
              </TouchableOpacity>

              {/* Tinh list dropdown */}
              {showTinhList && (
                <View style={styles.dropdown}>
                  <TextInput
                    style={styles.dropdownSearch}
                    value={tinhFilter}
                    onChangeText={setTinhFilter}
                    placeholder="Tìm tỉnh/thành..."
                    placeholderTextColor={COLORS.textTertiary}
                    autoFocus
                  />
                  <ScrollView style={{ maxHeight: 200 }} keyboardShouldPersistTaps="handled">
                    {filteredTinh.map(item => (
                      <TouchableOpacity
                        key={String(item.code)}
                        style={[styles.dropdownItem, String(selectedProvinceCode) === String(item.code) && styles.dropdownItemActive]}
                        onPress={() => handleProvinceSelect(item)}
                      >
                        <Text style={[styles.dropdownItemText, String(selectedProvinceCode) === String(item.code) && { color: COLORS.primary, fontWeight: '700' }]}>{item.name}</Text>
                        {String(selectedProvinceCode) === String(item.code) && <Ionicons name="checkmark" size={16} color={COLORS.primary} />}
                      </TouchableOpacity>
                    ))}
                  </ScrollView>
                </View>
              )}


              {/* Phường/xã lấy từ API theo tỉnh */}
              <Text style={styles.fieldLabel}>Phường/Xã <Text style={styles.required}>*</Text></Text>
              <TouchableOpacity
                style={styles.selectField}
                onPress={() => setShowWardList((current) => !current)}
                disabled={!selectedProvinceCode || loadingWards}
              >
                <Text style={form.phuong_xa ? styles.selectFieldValue : styles.selectFieldPlaceholder}>
                  {loadingWards ? 'Đang tải phường/xã...' : (form.phuong_xa || 'Chọn phường/xã')}
                </Text>
                <Ionicons name="chevron-down" size={16} color={COLORS.textTertiary} />
              </TouchableOpacity>

              {showWardList && (
                <View style={styles.dropdown}>
                  <TextInput
                    style={styles.dropdownSearch}
                    value={wardFilter}
                    onChangeText={setWardFilter}
                    placeholder="Tìm phường/xã..."
                    placeholderTextColor={COLORS.textTertiary}
                    autoFocus
                  />
                  <ScrollView style={{ maxHeight: 200 }} keyboardShouldPersistTaps="handled">
                    {filteredWards.map(item => (
                      <TouchableOpacity
                        key={String(item.code)}
                        style={[styles.dropdownItem, String(selectedWardCode) === String(item.code) && styles.dropdownItemActive]}
                        onPress={() => handleWardSelect(item)}
                      >
                        <View style={{ flex: 1 }}>
                          <Text style={[styles.dropdownItemText, String(selectedWardCode) === String(item.code) && { color: COLORS.primary, fontWeight: '700' }]}>{item.name}</Text>
                          {!!item.districtName && <Text style={styles.dropdownItemMeta}>{item.districtName}</Text>}
                        </View>
                        {String(selectedWardCode) === String(item.code) && <Ionicons name="checkmark" size={16} color={COLORS.primary} />}
                      </TouchableOpacity>
                    ))}
                  </ScrollView>
                </View>
              )}

              {/* Địa chỉ cụ thể */}
              <Text style={styles.fieldLabel}>Địa chỉ cụ thể <Text style={styles.required}>*</Text></Text>
              <TextInput
                style={[styles.fieldInput, { height: 80, textAlignVertical: 'top', paddingTop: SPACING.md }]}
                value={form.diachi_cuthe}
                onChangeText={v => setForm(current => ({ ...current, diachi_cuthe: v, latitude: null, longitude: null }))}
                placeholder="Số nhà, tên đường, tòa nhà..."
                placeholderTextColor={COLORS.textTertiary}
                multiline
              />

              {searchingAddress && (
                <View style={styles.searchingRow}>
                  <ActivityIndicator size="small" color={COLORS.primary} />
                  <Text style={styles.searchingText}>Đang tìm địa chỉ phù hợp...</Text>
                </View>
              )}

              {suggestions.length > 0 && (
                <View style={styles.suggestionList}>
                  {suggestions.map((item, index) => (
                    <TouchableOpacity key={`${item.lat}-${item.lng}-${index}`} style={styles.suggestionItem} onPress={() => applySuggestion(item)}>
                      <Ionicons name="location-outline" size={17} color={COLORS.primaryLight} />
                      <View style={{ flex: 1 }}>
                        <Text style={styles.suggestionTitle}>{item.title || item.display_name}</Text>
                        {!!item.subtitle && <Text style={styles.suggestionSubtitle}>{item.subtitle}</Text>}
                      </View>
                    </TouchableOpacity>
                  ))}
                </View>
              )}

              <TouchableOpacity style={styles.locationButton} onPress={useCurrentLocation} disabled={locating}>
                {locating ? <ActivityIndicator size="small" color={COLORS.primary} /> : <Ionicons name="locate-outline" size={18} color={COLORS.primary} />}
                <Text style={styles.locationButtonText}>Dùng vị trí hiện tại</Text>
              </TouchableOpacity>

              {Number.isFinite(Number(form.latitude)) && Number.isFinite(Number(form.longitude)) && (
                <View style={styles.mapWrap}>
                  <MapView
                    key={`${form.latitude}-${form.longitude}`}
                    style={styles.map}
                    initialRegion={{ latitude: Number(form.latitude), longitude: Number(form.longitude), latitudeDelta: 0.006, longitudeDelta: 0.006 }}
                  >
                    <Marker
                      draggable
                      coordinate={{ latitude: Number(form.latitude), longitude: Number(form.longitude) }}
                      onDragEnd={(event) => applyCoordinates(event.nativeEvent.coordinate.latitude, event.nativeEvent.coordinate.longitude)}
                    />
                  </MapView>
                  <Text style={styles.mapHint}>Giữ và kéo ghim để chỉnh chính xác điểm giao hàng</Text>
                </View>
              )}

              {(form.latitude !== null && form.longitude !== null) && (
                <View style={styles.coordinateBadge}>
                  <Ionicons name="navigate-circle-outline" size={16} color={COLORS.success} />
                  <Text style={styles.coordinateText}>Đã xác định tọa độ giao hàng</Text>
                </View>
              )}

              <TouchableOpacity
                style={styles.defaultToggleRow}
                onPress={() => setField('mac_dinh', !form.mac_dinh)}
              >
                <Ionicons
                  name={form.mac_dinh ? 'checkbox' : 'square-outline'}
                  size={22}
                  color={form.mac_dinh ? COLORS.primary : COLORS.textTertiary}
                />
                <Text style={styles.defaultToggleText}>Đặt làm địa chỉ mặc định</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.saveBtn, saving && { opacity: 0.6 }]}
                onPress={handleSave}
                disabled={saving || geocoding}
              >
                {saving || geocoding
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={styles.saveBtnText}>{address ? 'Cập nhật' : 'Thêm địa chỉ'}</Text>
                }
              </TouchableOpacity>
            </ScrollView>
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}

export default function AddressScreen() {
  const navigation = useNavigation();
  const token = useAuthStore((state) => state.token);
  const [addresses, setAddresses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [editingAddress, setEditingAddress] = useState(null);

  const fetchAddresses = useCallback(async () => {
    if (!token) {
      setAddresses([]);
      return;
    }

    try {
      const res = await api.get('/user/dia-chi');
      const data = res.data?.data || res.data || [];
      setAddresses(Array.isArray(data) ? data : []);
    } catch {
      setAddresses([]);
    }
  }, [token]);

  useEffect(() => {
    if (!token) {
      setLoading(false);
      return;
    }

    setLoading(true);
    fetchAddresses().finally(() => setLoading(false));
  }, [fetchAddresses, token]);

  if (!token) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <View style={styles.topBar}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.backIcon}>❮</Text>
            <Text style={styles.backText}>Quay lại</Text>
          </TouchableOpacity>
          <Text style={styles.topTitle}>Sổ địa chỉ</Text>
        </View>
        <View style={styles.authPromptContainer}>
          <Text style={styles.authPromptIcon}>📍</Text>
          <Text style={styles.authPromptTitle}>Yêu cầu đăng nhập</Text>
          <Text style={styles.authPromptDesc}>Vui lòng đăng nhập tài khoản của bạn để xem và quản lý sổ địa chỉ giao hàng.</Text>
          <TouchableOpacity style={styles.authPromptBtn} onPress={() => navigation.navigate('Main', { screen: 'Tài khoản' })}>
            <Text style={styles.authPromptBtnText}>Đăng nhập ngay</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const handleRefresh = async () => {
    setRefreshing(true);
    await fetchAddresses();
    setRefreshing(false);
  };

  const handleEdit = (addr) => {
    setEditingAddress(addr);
    setModalVisible(true);
  };

  const handleAdd = () => {
    setEditingAddress(null);
    setModalVisible(true);
  };

  const handleDelete = (addr) => {
    Alert.alert(
      'Xóa địa chỉ',
      'Bạn có chắc muốn xóa địa chỉ này không?',
      [
        { text: 'Hủy', style: 'cancel' },
        {
          text: 'Xóa', style: 'destructive',
          onPress: async () => {
            try {
              await api.delete(`/user/dia-chi/${addr.id_diachi}`);
              await fetchAddresses();
            } catch (err) {
              Alert.alert('Lỗi', err.response?.data?.message || 'Không thể xóa địa chỉ.');
            }
          },
        },
      ]
    );
  };

  const handleSetDefault = async (addr) => {
    try {
      await api.patch(`/user/dia-chi/${addr.id_diachi}/mac-dinh`);
      await fetchAddresses();
    } catch (err) {
      Alert.alert('Lỗi', err.response?.data?.message || 'Không thể đặt mặc định.');
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={22} color={COLORS.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Sổ địa chỉ</Text>
        <TouchableOpacity style={styles.addBtn} onPress={handleAdd}>
          <Ionicons name="add" size={22} color={COLORS.primary} />
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerLoader}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Đang tải địa chỉ...</Text>
        </View>
      ) : (
        <FlatList
          data={addresses}
          keyExtractor={item => String(item.id_diachi)}
          renderItem={({ item }) => (
            <AddressCard
              address={item}
              onEdit={handleEdit}
              onDelete={handleDelete}
              onSetDefault={handleSetDefault}
            />
          )}
          contentContainerStyle={styles.listContent}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor={COLORS.primary} />}
          ListEmptyComponent={() => (
            <View style={styles.emptyContainer}>
              <Text style={{ fontSize: 56 }}>📍</Text>
              <Text style={styles.emptyTitle}>Chưa có địa chỉ nào</Text>
              <Text style={styles.emptySubtitle}>Thêm địa chỉ để thanh toán nhanh hơn</Text>
              <TouchableOpacity style={styles.addEmptyBtn} onPress={handleAdd}>
                <Ionicons name="add-circle-outline" size={18} color="#fff" />
                <Text style={styles.addEmptyBtnText}>Thêm địa chỉ mới</Text>
              </TouchableOpacity>
            </View>
          )}
          ListFooterComponent={() => (
            addresses.length > 0 ? (
              <TouchableOpacity style={styles.addMoreBtn} onPress={handleAdd}>
                <Ionicons name="add-circle-outline" size={18} color={COLORS.primary} />
                <Text style={styles.addMoreBtnText}>Thêm địa chỉ mới</Text>
              </TouchableOpacity>
            ) : null
          )}
        />
      )}

      <AddressFormModal
        visible={modalVisible}
        address={editingAddress}
        onClose={() => setModalVisible(false)}
        onSaved={fetchAddresses}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.background },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SPACING.lg, paddingVertical: SPACING.md,
    borderBottomWidth: 1, borderColor: COLORS.border,
  },
  backBtn: {
    width: 36, height: 36, borderRadius: 18, backgroundColor: COLORS.surface,
    alignItems: 'center', justifyContent: 'center',
  },
  addBtn: {
    width: 36, height: 36, borderRadius: 18, backgroundColor: 'rgba(99,102,241,0.15)',
    alignItems: 'center', justifyContent: 'center',
  },
  headerTitle: { ...TYPOGRAPHY.headlineSmall, color: COLORS.textPrimary },

  listContent: { padding: SPACING.lg },

  card: {
    backgroundColor: COLORS.surface, borderRadius: RADIUS.lg, padding: SPACING.lg,
    marginBottom: SPACING.md, borderWidth: 1, borderColor: COLORS.border,
  },
  cardDefault: { borderColor: COLORS.primary, borderWidth: 1.5 },
  defaultBanner: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    backgroundColor: 'rgba(99,102,241,0.1)', paddingHorizontal: SPACING.sm,
    paddingVertical: 4, borderRadius: RADIUS.sm, alignSelf: 'flex-start', marginBottom: SPACING.sm,
  },
  defaultBannerText: { fontSize: 11, fontWeight: '700', color: COLORS.primary },
  cardHeader: { flexDirection: 'row', alignItems: 'flex-start', gap: SPACING.md },
  typeIconWrap: {
    width: 40, height: 40, borderRadius: 20, backgroundColor: 'rgba(99,102,241,0.12)',
    alignItems: 'center', justifyContent: 'center',
  },
  cardType: { fontSize: 13, fontWeight: '700', color: COLORS.textPrimary, marginBottom: 4 },
  cardAddr: { fontSize: 13, color: COLORS.textSecondary, lineHeight: 18 },
  cardActions: {
    flexDirection: 'row', gap: SPACING.lg, marginTop: SPACING.md,
    paddingTop: SPACING.md, borderTopWidth: 1, borderColor: COLORS.border, flexWrap: 'wrap',
  },
  actionBtn: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  actionBtnText: { fontSize: 12, color: COLORS.textSecondary, fontWeight: '500' },

  centerLoader: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: SPACING.md },
  loadingText: { color: COLORS.textTertiary, fontSize: 14 },

  emptyContainer: { paddingTop: 80, alignItems: 'center', gap: SPACING.md, paddingHorizontal: SPACING.xl },
  emptyTitle: { fontSize: 16, fontWeight: '700', color: COLORS.textSecondary },
  emptySubtitle: { fontSize: 13, color: COLORS.textTertiary, textAlign: 'center' },
  addEmptyBtn: {
    flexDirection: 'row', alignItems: 'center', gap: SPACING.sm,
    backgroundColor: COLORS.primary, paddingHorizontal: SPACING.xl, paddingVertical: SPACING.md,
    borderRadius: RADIUS.lg, marginTop: SPACING.sm,
  },
  addEmptyBtnText: { color: '#fff', fontWeight: '700', fontSize: 14 },
  addMoreBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: SPACING.sm,
    paddingVertical: SPACING.lg, marginHorizontal: SPACING.lg,
    borderWidth: 1.5, borderColor: COLORS.primary, borderRadius: RADIUS.lg, borderStyle: 'dashed',
  },
  addMoreBtnText: { color: COLORS.primary, fontWeight: '700', fontSize: 14 },

  // Modal
  modalOverlay: { flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.6)' },
  modalSheet: {
    backgroundColor: COLORS.surface, borderTopLeftRadius: RADIUS.xl, borderTopRightRadius: RADIUS.xl,
    padding: SPACING.xl, maxHeight: '90%',
  },
  modalHeader: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    marginBottom: SPACING.xl,
  },
  modalTitle: { ...TYPOGRAPHY.headlineSmall, color: COLORS.textPrimary },
  closeBtn: {
    width: 32, height: 32, borderRadius: 16, backgroundColor: COLORS.background,
    alignItems: 'center', justifyContent: 'center',
  },

  fieldLabel: { fontSize: 13, fontWeight: '600', color: COLORS.textSecondary, marginBottom: SPACING.sm, marginTop: SPACING.md },
  required: { color: COLORS.error },
  fieldInput: {
    backgroundColor: COLORS.background, borderRadius: RADIUS.md, borderWidth: 1,
    borderColor: COLORS.border, paddingVertical: SPACING.md, paddingHorizontal: SPACING.lg,
    color: COLORS.textPrimary, fontSize: 14,
  },
  selectField: {
    backgroundColor: COLORS.background, borderRadius: RADIUS.md, borderWidth: 1, borderColor: COLORS.border,
    paddingVertical: SPACING.md, paddingHorizontal: SPACING.lg,
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
  },
  selectFieldValue: { color: COLORS.textPrimary, fontSize: 14 },
  selectFieldPlaceholder: { color: COLORS.textTertiary, fontSize: 14 },
  dropdown: {
    backgroundColor: COLORS.background, borderWidth: 1, borderColor: COLORS.border,
    borderRadius: RADIUS.md, marginTop: SPACING.sm,
  },
  dropdownSearch: {
    paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm,
    borderBottomWidth: 1, borderColor: COLORS.border, color: COLORS.textPrimary, fontSize: 13,
  },
  dropdownItem: {
    paddingHorizontal: SPACING.lg, paddingVertical: SPACING.md,
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    borderBottomWidth: 1, borderColor: COLORS.border,
  },
  dropdownItemActive: { backgroundColor: 'rgba(99,102,241,0.08)' },
  dropdownItemText: { fontSize: 14, color: COLORS.textSecondary },
  dropdownItemMeta: { fontSize: 10, color: COLORS.textTertiary, marginTop: 2 },
  searchingRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.sm, marginTop: SPACING.sm },
  searchingText: { color: COLORS.textTertiary, fontSize: 11 },
  suggestionList: {
    marginTop: SPACING.sm,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    backgroundColor: COLORS.background,
    overflow: 'hidden',
  },
  suggestionItem: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: SPACING.sm,
    padding: SPACING.md,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  suggestionTitle: { color: COLORS.textPrimary, fontSize: 12, fontWeight: '600' },
  suggestionSubtitle: { color: COLORS.textTertiary, fontSize: 10, lineHeight: 15, marginTop: 2 },
  coordinateBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: SPACING.xs,
    paddingHorizontal: SPACING.sm,
    paddingVertical: 5,
    borderRadius: RADIUS.full,
    marginTop: SPACING.sm,
    backgroundColor: 'rgba(16,185,129,0.12)',
  },
  locationButton: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8,
    borderWidth: 1, borderColor: COLORS.primary, borderRadius: RADIUS.md,
    padding: SPACING.md, marginTop: SPACING.md,
  },
  locationButtonText: { color: COLORS.primary, fontWeight: '700' },
  mapWrap: { marginTop: SPACING.md, borderRadius: RADIUS.lg, overflow: 'hidden', borderWidth: 1, borderColor: COLORS.border },
  map: { width: '100%', height: 230 },
  mapHint: { color: COLORS.textTertiary, fontSize: 11, textAlign: 'center', padding: SPACING.sm, backgroundColor: COLORS.surface },
  coordinateText: { color: COLORS.success, fontSize: 10, fontWeight: '700' },
  defaultToggleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: SPACING.sm,
    marginTop: SPACING.lg,
    paddingVertical: SPACING.sm,
  },
  defaultToggleText: { color: COLORS.textSecondary, fontSize: 13, fontWeight: '600' },

  typeRow: { flexDirection: 'row', gap: SPACING.md, marginBottom: SPACING.sm },
  typeOption: {
    flex: 1, flexDirection: 'row', alignItems: 'center', gap: SPACING.sm,
    padding: SPACING.md, borderRadius: RADIUS.md, borderWidth: 1.5, borderColor: COLORS.border,
    backgroundColor: COLORS.background,
  },
  typeOptionActive: { borderColor: COLORS.primary, backgroundColor: 'rgba(99,102,241,0.08)' },
  typeOptionText: { fontSize: 13, color: COLORS.textSecondary, fontWeight: '500' },

  saveBtn: {
    backgroundColor: COLORS.primary, borderRadius: RADIUS.lg, paddingVertical: SPACING.md + 2,
    alignItems: 'center', marginTop: SPACING.xl, marginBottom: SPACING.xl,
  },
  saveBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
  
  authPromptContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: SPACING.xl,
    backgroundColor: COLORS.background,
  },
  authPromptIcon: {
    fontSize: 48,
    marginBottom: SPACING.md,
  },
  authPromptTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.sm,
  },
  authPromptDesc: {
    fontSize: 14,
    color: COLORS.textSecondary,
    textAlign: 'center',
    marginBottom: SPACING.xl,
    lineHeight: 20,
  },
  authPromptBtn: {
    backgroundColor: COLORS.primary,
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: RADIUS.md,
  },
  authPromptBtnText: {
    color: COLORS.white,
    fontWeight: '700',
    fontSize: 14,
  },
});
