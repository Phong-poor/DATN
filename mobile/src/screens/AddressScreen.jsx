import React, { useEffect, useState, useCallback } from 'react';
import {
  StyleSheet, Text, View, FlatList, TextInput, TouchableOpacity,
  ActivityIndicator, Alert, Modal, ScrollView, KeyboardAvoidingView, Platform,
  RefreshControl,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS, RADIUS, SPACING, TYPOGRAPHY } from '../utils/theme';
import api from '../services/api';
import useAuthStore from '../store/useAuthStore';

const LOAI_DIA_CHI = [
  { value: 'home', label: '🏠 Nhà riêng', icon: 'home-outline' },
  { value: 'company', label: '🏢 Công ty', icon: 'business-outline' },
];

const TINH_THANH = [
  'Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ',
  'Bình Dương', 'Đồng Nai', 'An Giang', 'Bà Rịa - Vũng Tàu',
  'Bắc Giang', 'Bắc Kạn', 'Bắc Ninh', 'Bến Tre', 'Bình Định', 'Bình Phước',
  'Bình Thuận', 'Cà Mau', 'Cao Bằng', 'Đắk Lắk', 'Đắk Nông', 'Điện Biên',
  'Đồng Tháp', 'Gia Lai', 'Hà Giang', 'Hà Nam', 'Hà Tĩnh', 'Hải Dương',
  'Hậu Giang', 'Hòa Bình', 'Hưng Yên', 'Khánh Hòa', 'Kiên Giang', 'Kon Tum',
  'Lai Châu', 'Lạng Sơn', 'Lào Cai', 'Lâm Đồng', 'Long An', 'Nam Định',
  'Nghệ An', 'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên', 'Quảng Bình',
  'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị', 'Sóc Trăng', 'Sơn La',
  'Tây Ninh', 'Thái Bình', 'Thái Nguyên', 'Thanh Hóa', 'Thừa Thiên Huế',
  'Tiền Giang', 'Trà Vinh', 'Tuyên Quang', 'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái',
];

const emptyForm = {
  tinh_thanhpho: '',
  phuong_xa: '',
  diachi_cuthe: '',
  loai_diachi: 'home',
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
            {[address.diachi_cuthe, address.phuong_xa, address.tinh_thanhpho]
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
  const [showTinhList, setShowTinhList] = useState(false);
  const [tinhFilter, setTinhFilter] = useState('');

  useEffect(() => {
    if (address) {
      setForm({
        tinh_thanhpho: address.tinh_thanhpho || '',
        phuong_xa: address.phuong_xa || '',
        diachi_cuthe: address.diachi_cuthe || '',
        loai_diachi: address.loai_diachi || 'home',
      });
    } else {
      setForm(emptyForm);
    }
    setShowTinhList(false);
    setTinhFilter('');
  }, [address, visible]);

  const setField = (key, val) => setForm(f => ({ ...f, [key]: val }));

  const handleSave = async () => {
    if (!form.tinh_thanhpho.trim()) { Alert.alert('Lỗi', 'Vui lòng chọn tỉnh/thành phố.'); return; }
    if (!form.phuong_xa.trim()) { Alert.alert('Lỗi', 'Vui lòng nhập phường/xã.'); return; }
    if (!form.diachi_cuthe.trim() || form.diachi_cuthe.trim().length < 5) {
      Alert.alert('Lỗi', 'Địa chỉ cụ thể phải từ 5 ký tự trở lên.');
      return;
    }

    setSaving(true);
    try {
      if (address?.id_diachi) {
        await api.put(`/user/dia-chi/${address.id_diachi}`, form);
        Alert.alert('Thành công', 'Đã cập nhật địa chỉ!');
      } else {
        await api.post('/user/dia-chi', form);
        Alert.alert('Thành công', 'Đã thêm địa chỉ mới!');
      }
      onSaved();
      onClose();
    } catch (err) {
      const msg = err.response?.data?.message || 'Không thể lưu địa chỉ.';
      Alert.alert('Lỗi', msg);
    } finally {
      setSaving(false);
    }
  };

  const filteredTinh = TINH_THANH.filter(t => t.toLowerCase().includes(tinhFilter.toLowerCase()));

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
                onPress={() => setShowTinhList(true)}
              >
                <Text style={form.tinh_thanhpho ? styles.selectFieldValue : styles.selectFieldPlaceholder}>
                  {form.tinh_thanhpho || 'Chọn tỉnh/thành phố'}
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
                    {filteredTinh.map(t => (
                      <TouchableOpacity
                        key={t}
                        style={[styles.dropdownItem, form.tinh_thanhpho === t && styles.dropdownItemActive]}
                        onPress={() => { setField('tinh_thanhpho', t); setShowTinhList(false); setTinhFilter(''); }}
                      >
                        <Text style={[styles.dropdownItemText, form.tinh_thanhpho === t && { color: COLORS.primary, fontWeight: '700' }]}>{t}</Text>
                        {form.tinh_thanhpho === t && <Ionicons name="checkmark" size={16} color={COLORS.primary} />}
                      </TouchableOpacity>
                    ))}
                  </ScrollView>
                </View>
              )}


              {/* Phường/xã */}
              <Text style={styles.fieldLabel}>Phường/Xã <Text style={styles.required}>*</Text></Text>
              <TextInput
                style={styles.fieldInput}
                value={form.phuong_xa}
                onChangeText={v => setField('phuong_xa', v)}
                placeholder="VD: Phường Bến Nghé..."
                placeholderTextColor={COLORS.textTertiary}
              />

              {/* Địa chỉ cụ thể */}
              <Text style={styles.fieldLabel}>Địa chỉ cụ thể <Text style={styles.required}>*</Text></Text>
              <TextInput
                style={[styles.fieldInput, { height: 80, textAlignVertical: 'top', paddingTop: SPACING.md }]}
                value={form.diachi_cuthe}
                onChangeText={v => setField('diachi_cuthe', v)}
                placeholder="Số nhà, tên đường, tòa nhà..."
                placeholderTextColor={COLORS.textTertiary}
                multiline
              />

              <TouchableOpacity
                style={[styles.saveBtn, saving && { opacity: 0.6 }]}
                onPress={handleSave}
                disabled={saving}
              >
                {saving
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
