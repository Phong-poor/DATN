import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, ScrollView, TextInput, TouchableOpacity, ActivityIndicator, KeyboardAvoidingView, Platform, Linking, Modal } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import api from '../services/api';
import useAuthStore from '../store/useAuthStore';
import useCartStore from '../store/useCartStore';
import CustomAlert from '../components/CustomAlert';
import { SHIPPING_FEE } from '../constants/pricing';
import logger from '../utils/logger';

export default function CheckoutScreen({ navigation }) {
  const user = useAuthStore((state) => state.user);
  const items = useCartStore((state) => state.items);
  const clearCart = useCartStore((state) => state.clearCart);

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('COD'); // 'COD' or 'VNPay'
  const [loading, setLoading] = useState(false);

  // Address book states
  const [addresses, setAddresses] = useState([]);
  const [addressModalVisible, setAddressModalVisible] = useState(false);

  // Fetch address book on mount
  useEffect(() => {
    const fetchAddresses = async () => {
      const token = useAuthStore.getState().token;
      if (!token) return;
      try {
        const response = await api.get('/user/dia-chi');
        const data = response.data?.data || response.data || [];
        if (Array.isArray(data)) {
          setAddresses(data);
          const defaultAddr = data.find(addr => addr.mac_dinh) || data[0];
          if (defaultAddr) {
            const fullAddrStr = [defaultAddr.diachi_cuthe, defaultAddr.phuong_xa, defaultAddr.quan_huyen, defaultAddr.tinh_thanhpho].filter(Boolean).join(', ');
            setAddress(fullAddrStr);
          }
        }
      } catch (err) {
        logger.log('Failed to fetch addresses:', err);
      }
    };
    fetchAddresses();
  }, []);

  // Voucher / Promo Code states
  const [promoCode, setPromoCode] = useState('');
  const [promoDiscount, setPromoDiscount] = useState(0);
  const [promoApplied, setPromoApplied] = useState(false);
  const [promoMessage, setPromoMessage] = useState('');
  const [promoError, setPromoError] = useState(null);
  const [userVouchers, setUserVouchers] = useState([]);

  const [alertConfig, setAlertConfig] = useState({
    visible: false,
    title: '',
    message: '',
    type: 'info',
    onConfirm: () => {},
  });

  // Fetch user vouchers on mount
  useEffect(() => {
    const fetchUserVouchers = async () => {
      const token = useAuthStore.getState().token;
      if (!token) return;
      try {
        const response = await api.get('/user/vouchers');
        if (response.data && response.data.success) {
          setUserVouchers(response.data.vouchers || []);
        }
      } catch (err) {
        logger.log('Failed to fetch user vouchers:', err);
      }
    };
    fetchUserVouchers();
  }, []);

  const showAlert = (title, message, type = 'info', onConfirm = null) => {
    setAlertConfig({
      visible: true,
      title,
      message,
      type,
      onConfirm: () => {
        setAlertConfig(prev => ({ ...prev, visible: false }));
        if (onConfirm) onConfirm();
      }
    });
  };

  // Pre-fill user data
  useEffect(() => {
    if (user) {
      setName(user.name || user.ten || '');
      setPhone(user.phone || user.sodienthoai || '');
      if (user.dia_chis && user.dia_chis.length > 0) {
        const defaultAddr = user.dia_chis.find(addr => addr.is_default) || user.dia_chis[0];
        setAddress(defaultAddr.dia_chi_day_du || defaultAddr.diachi || '');
      }
    }
  }, [user]);

  const calculateSubtotal = () => {
    return items.reduce((sum, item) => sum + item.price * item.quantity, 0);
  };

  const calculateTotal = () => {
    return Math.max(0, calculateSubtotal() + SHIPPING_FEE - promoDiscount);
  };

  const formatPrice = (value) => {
    return parseFloat(value).toLocaleString('vi-VN') + 'đ';
  };

  const handleApplyPromoCode = async (code) => {
    if (!code.trim()) return;
    setLoading(true);
    setPromoError(null);
    setPromoMessage('');
    try {
      const response = await api.post('/apply-promo', {
        code: code.trim().toUpperCase(),
        subtotal: calculateSubtotal(),
      });
      if (response.data.success) {
        setPromoDiscount(response.data.discount);
        setPromoApplied(true);
        setPromoMessage(response.data.message);
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Áp dụng mã giảm giá thất bại.';
      setPromoMessage(msg);
      setPromoError(true);
      setPromoApplied(false);
      setPromoDiscount(0);
    } finally {
      setLoading(false);
    }
  };

  const handleApplyPromo = () => {
    handleApplyPromoCode(promoCode);
  };

  const handleRemovePromo = () => {
    setPromoCode('');
    setPromoDiscount(0);
    setPromoApplied(false);
    setPromoMessage('');
    setPromoError(null);
  };

  const handlePlaceOrder = async () => {
    if (!name.trim()) {
      showAlert('Thiếu thông tin', 'Vui lòng nhập họ tên người nhận.', 'warning');
      return;
    }
    
    const phoneRegex = /^0[0-9]{9}$/;
    if (!phoneRegex.test(phone.trim())) {
      showAlert('Thiếu thông tin', 'Số điện thoại nhận hàng phải gồm 10 chữ số và bắt đầu bằng số 0.', 'warning');
      return;
    }

    if (!address.trim()) {
      showAlert('Thiếu thông tin', 'Vui lòng nhập địa chỉ nhận hàng.', 'warning');
      return;
    }

    setLoading(true);

    try {
      // 1. Clear backend cart first to prevent mixing old cart items
      await api.delete('/gio-hang/xoa-tat');

      // 2. Sync all local cart items to backend cart
      for (const item of items) {
        await api.post('/gio-hang/them', {
          id_bienthe: item.variantId,
          soluong: item.quantity,
        });
      }

      // 3. Call the checkout/create order endpoint
      const response = await api.post('/checkout', {
        id_diachi: null,
        diachi: address.trim(),
        name: name.trim(),
        phone: phone.trim(),
        PTTT: paymentMethod, // 'COD' or 'VNPay'
        promo_code: promoApplied ? promoCode.trim().toUpperCase() : null,
      });

      if (response.data.success) {
        // Clear local cart
        clearCart();
        
        const payUrl = response.data.payUrl;
        
        if (paymentMethod === 'VNPay' && payUrl) {
          try {
            Linking.openURL(payUrl);
          } catch (linkErr) {
            logger.log('Failed to open payment URL:', linkErr);
          }
          navigation.replace('OrderSuccess', { order: response.data.order });
        } else {
          navigation.replace('OrderSuccess', { order: response.data.order });
        }
      }
    } catch (error) {
      logger.log('Checkout failed:', error);
      const msg = error.response?.data?.message || 'Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại.';
      showAlert('Lỗi đặt hàng', msg, 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={styles.container}
    >
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        {/* Header Back Bar */}
        <View style={styles.topBar}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.backIcon}>❮</Text>
            <Text style={styles.backText}>Quay lại</Text>
          </TouchableOpacity>
          <Text style={styles.topTitle}>Thanh toán</Text>
        </View>

        <ScrollView contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
          {/* Recipient Information Form */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Thông tin nhận hàng</Text>

            <View style={styles.inputContainer}>
              <Text style={styles.label}>Họ và tên người nhận</Text>
              <TextInput
                style={styles.input}
                value={name}
                onChangeText={setName}
                placeholder="Nguyễn Văn A"
                placeholderTextColor="#64748b"
              />
            </View>

            <View style={styles.inputContainer}>
              <Text style={styles.label}>Số điện thoại liên hệ</Text>
              <TextInput
                style={styles.input}
                value={phone}
                onChangeText={setPhone}
                placeholder="09XXXXXXXX"
                placeholderTextColor="#64748b"
                keyboardType="phone-pad"
                maxLength={10}
              />
            </View>

            <View style={styles.inputContainer}>
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 }}>
                <Text style={styles.label}>Địa chỉ giao hàng chi tiết</Text>
                {addresses.length > 0 && (
                  <TouchableOpacity onPress={() => setAddressModalVisible(true)}>
                    <Text style={{ color: COLORS.primary, fontSize: 12, fontWeight: '700' }}>Chọn từ sổ địa chỉ</Text>
                  </TouchableOpacity>
                )}
              </View>
              <TextInput
                style={[styles.input, styles.textArea]}
                value={address}
                onChangeText={setAddress}
                placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố..."
                placeholderTextColor="#64748b"
                multiline
                numberOfLines={3}
              />
            </View>
          </View>

          {/* Payment Method Selector */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Phương thức thanh toán</Text>
            
            <TouchableOpacity
              style={[
                styles.paymentOption,
                paymentMethod === 'COD' && styles.activePaymentOption
              ]}
              onPress={() => setPaymentMethod('COD')}
            >
              <Text style={styles.paymentIcon}>💵</Text>
              <View style={styles.paymentTextWrapper}>
                <Text style={styles.paymentName}>COD</Text>
                <Text style={styles.paymentDesc}>Thanh toán bằng tiền mặt khi nhận hàng</Text>
              </View>
              <View style={[styles.radio, paymentMethod === 'COD' && styles.activeRadio]} />
            </TouchableOpacity>

            <TouchableOpacity
              style={[
                styles.paymentOption,
                paymentMethod === 'VNPay' && styles.activePaymentOption
              ]}
              onPress={() => setPaymentMethod('VNPay')}
            >
              <Text style={styles.paymentIcon}>💳</Text>
              <View style={styles.paymentTextWrapper}>
                <Text style={styles.paymentName}>Chuyển khoản</Text>
                <Text style={styles.paymentDesc}>Thanh toán online qua cổng VNPay / ATM</Text>
              </View>
              <View style={[styles.radio, paymentMethod === 'VNPay' && styles.activeRadio]} />
            </TouchableOpacity>
          </View>

          {/* Voucher Section */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Mã giảm giá</Text>
            
            <View style={styles.promoInputRow}>
              <TextInput
                style={[styles.input, styles.promoInput, promoApplied && styles.disabledInput]}
                value={promoCode}
                onChangeText={(text) => {
                  setPromoCode(text);
                  setPromoError(null);
                  setPromoMessage('');
                }}
                placeholder="NHẬP MÃ GIẢM GIÁ"
                placeholderTextColor="#64748b"
                autoCapitalize="characters"
                editable={!promoApplied}
              />
              <TouchableOpacity 
                style={[styles.promoBtn, promoApplied ? styles.promoCancelBtn : styles.promoApplyBtn]} 
                onPress={promoApplied ? handleRemovePromo : handleApplyPromo}
                disabled={!promoCode.trim()}
              >
                <Text style={styles.promoBtnText}>{promoApplied ? 'Hủy' : 'Áp dụng'}</Text>
              </TouchableOpacity>
            </View>

            {promoMessage ? (
              <Text style={[styles.promoMessage, promoApplied ? styles.promoSuccessText : styles.promoErrorText]}>
                {promoMessage}
              </Text>
            ) : null}

            {/* List of user vouchers */}
            {userVouchers.length > 0 && !promoApplied && (
              <View style={styles.vouchersWrapper}>
                <Text style={styles.vouchersLabel}>Voucher của bạn:</Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.vouchersScroll}>
                  {userVouchers.map((item) => {
                    const promo = item.promotion;
                    if (!promo || item.trang_thai !== 0) return null;
                    return (
                      <TouchableOpacity 
                        key={item.id} 
                        style={styles.voucherBadge}
                        onPress={() => {
                          setPromoCode(promo.code);
                          handleApplyPromoCode(promo.code);
                        }}
                      >
                        <Text style={styles.voucherBadgeText}>{promo.code}</Text>
                        <Text style={styles.voucherBadgeDesc}>
                          {promo.loai === 'percent' ? `-${promo.giatri}%` : `-${formatPrice(promo.giatri)}`}
                        </Text>
                      </TouchableOpacity>
                    );
                  })}
                </ScrollView>
              </View>
            )}
          </View>

          {/* Order Summary list */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Tóm tắt đơn hàng</Text>
            {items.map((item) => (
              <View key={item.id} style={styles.itemRow}>
                <View style={styles.itemLeft}>
                  <Text style={styles.itemName} numberOfLines={1}>{item.name}</Text>
                  <Text style={styles.itemVariant}>{item.variantName || 'Cấu hình tiêu chuẩn'}</Text>
                </View>
                <Text style={styles.itemQtyPrice}>
                  {item.quantity} x {formatPrice(item.price)}
                </Text>
              </View>
            ))}

            <View style={styles.divider} />

            <View style={styles.priceRow}>
              <Text style={styles.priceLabel}>Tạm tính</Text>
              <Text style={styles.priceValue}>{formatPrice(calculateSubtotal())}</Text>
            </View>

            <View style={styles.priceRow}>
              <Text style={styles.priceLabel}>Phí vận chuyển</Text>
              <Text style={styles.priceValue}>{formatPrice(SHIPPING_FEE)}</Text>
            </View>

            {promoApplied && (
              <View style={styles.priceRow}>
                <Text style={styles.priceLabel}>Giảm giá</Text>
                <Text style={[styles.priceValue, { color: COLORS.success || '#22c55e' }]}>
                  -{formatPrice(promoDiscount)}
                </Text>
              </View>
            )}

            <View style={styles.divider} />

            <View style={styles.priceRow}>
              <Text style={styles.totalLabel}>Tổng cộng</Text>
              <Text style={styles.totalValue}>{formatPrice(calculateTotal())}</Text>
            </View>
          </View>
        </ScrollView>

        {/* Sticky Place Order Button */}
        <View style={styles.stickyBottomBar}>
          <View style={styles.bottomPriceContainer}>
            <Text style={styles.bottomPriceLabel}>Tổng thanh toán</Text>
            <Text style={styles.bottomPrice}>{formatPrice(calculateTotal())}</Text>
          </View>
          
          <TouchableOpacity
            style={[styles.placeOrderBtn, loading && styles.disabledBtn]}
            onPress={handlePlaceOrder}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="#ffffff" />
            ) : (
              <Text style={styles.placeOrderBtnText}>Đặt hàng</Text>
            )}
          </TouchableOpacity>
        </View>
      </SafeAreaView>

      <CustomAlert
        visible={alertConfig.visible}
        title={alertConfig.title}
        message={alertConfig.message}
        type={alertConfig.type}
        onConfirm={alertConfig.onConfirm}
        confirmText="Đồng ý"
        onCancel={null}
      />

      {/* Address Selector Modal */}
      <Modal
        visible={addressModalVisible}
        animationType="slide"
        transparent
        onRequestClose={() => setAddressModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Chọn địa chỉ nhận hàng</Text>
              <TouchableOpacity onPress={() => setAddressModalVisible(false)} style={styles.closeBtn}>
                <Ionicons name="close" size={22} color={COLORS.textPrimary} />
              </TouchableOpacity>
            </View>

            <ScrollView showsVerticalScrollIndicator={false}>
              {addresses.map((addr) => {
                const fullAddrStr = [addr.diachi_cuthe, addr.phuong_xa, addr.quan_huyen, addr.tinh_thanhpho]
                  .filter(Boolean)
                  .join(', ');

                return (
                  <TouchableOpacity
                    key={addr.id_diachi}
                    style={{
                      padding: 16,
                      borderRadius: 12,
                      borderWidth: 1,
                      borderColor: address === fullAddrStr ? COLORS.primary : COLORS.border,
                      backgroundColor: address === fullAddrStr ? 'rgba(99, 102, 241, 0.05)' : COLORS.surface,
                      marginBottom: 12,
                    }}
                    onPress={() => {
                      setAddress(fullAddrStr);
                      setAddressModalVisible(false);
                    }}
                  >
                    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 4 }}>
                      <Text style={{ fontWeight: '700', fontSize: 13, color: COLORS.textPrimary }}>
                        {addr.loai_diachi === 'company' ? '🏢 Công ty' : '🏠 Nhà riêng'}
                      </Text>
                      {addr.mac_dinh && (
                        <View style={{ paddingVertical: 2, paddingHorizontal: 6, borderRadius: 4, backgroundColor: 'rgba(16, 185, 129, 0.1)' }}>
                          <Text style={{ fontSize: 10, color: '#10b981', fontWeight: '700' }}>Mặc định</Text>
                        </View>
                      )}
                    </View>
                    <Text style={{ fontSize: 13, color: COLORS.textSecondary, lineHeight: 18 }}>{fullAddrStr}</Text>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>
          </View>
        </View>
      </Modal>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  safeArea: {
    flex: 1,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: SPACING.lg,
    paddingVertical: SPACING.md,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.background,
  },
  backBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: SPACING.lg,
  },
  backIcon: {
    fontSize: 14,
    color: COLORS.primary,
    marginRight: SPACING.xs,
    fontWeight: '700',
  },
  backText: {
    fontSize: 14,
    color: COLORS.primary,
    fontWeight: '600',
  },
  topTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  scrollContent: {
    padding: SPACING.lg,
    paddingBottom: 120, // Guarantee enough spacing for absolute bottom bar
  },
  sectionCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.lg,
    marginBottom: SPACING.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    elevation: 2,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.lg,
    borderLeftWidth: 3,
    borderLeftColor: COLORS.primary,
    paddingLeft: SPACING.sm,
  },
  inputContainer: {
    marginBottom: SPACING.md,
  },
  label: {
    fontSize: 12,
    fontWeight: '600',
    color: COLORS.textSecondary,
    marginBottom: SPACING.xs,
  },
  input: {
    backgroundColor: COLORS.background,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: SPACING.md,
    paddingHorizontal: SPACING.lg,
    color: COLORS.textPrimary,
    fontSize: 15,
  },
  disabledInput: {
    backgroundColor: COLORS.border,
    color: COLORS.textTertiary,
  },
  promoInputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: SPACING.sm,
  },
  promoInput: {
    flex: 1,
    marginRight: SPACING.sm,
    textTransform: 'uppercase',
  },
  promoBtn: {
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.md,
    paddingHorizontal: SPACING.lg,
    justifyContent: 'center',
    alignItems: 'center',
    minWidth: 80,
  },
  promoApplyBtn: {
    backgroundColor: COLORS.primary,
  },
  promoCancelBtn: {
    backgroundColor: COLORS.error,
  },
  promoBtnText: {
    color: COLORS.white,
    fontWeight: '700',
    fontSize: 13,
  },
  promoMessage: {
    fontSize: 13,
    marginBottom: SPACING.sm,
    textAlign: 'left',
    fontWeight: '500',
  },
  promoSuccessText: {
    color: COLORS.success || '#22c55e',
  },
  promoErrorText: {
    color: COLORS.error || '#ef4444',
  },
  vouchersWrapper: {
    marginTop: SPACING.md,
  },
  vouchersLabel: {
    fontSize: 12,
    color: COLORS.textSecondary,
    fontWeight: '600',
    marginBottom: SPACING.sm,
  },
  vouchersScroll: {
    flexDirection: 'row',
  },
  voucherBadge: {
    backgroundColor: '#1e1b4b',
    borderWidth: 1,
    borderColor: '#312e81',
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.sm,
    paddingHorizontal: SPACING.md,
    marginRight: SPACING.sm,
    alignItems: 'center',
  },
  voucherBadgeText: {
    color: '#a5b4fc',
    fontWeight: '700',
    fontSize: 12,
  },
  voucherBadgeDesc: {
    color: '#818cf8',
    fontSize: 10,
    marginTop: 2,
  },
  textArea: {
    height: 80,
    textAlignVertical: 'top',
  },
  paymentOption: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.background,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    padding: SPACING.md,
    marginBottom: SPACING.md,
  },
  activePaymentOption: {
    borderColor: COLORS.primary,
    backgroundColor: 'rgba(99, 102, 241, 0.05)',
  },
  paymentIcon: {
    fontSize: 22,
    marginRight: SPACING.md,
  },
  paymentTextWrapper: {
    flex: 1,
  },
  paymentName: {
    color: COLORS.textPrimary,
    fontSize: 14,
    fontWeight: '700',
    marginBottom: SPACING.xs,
  },
  paymentDesc: {
    color: COLORS.textTertiary,
    fontSize: 11,
  },
  radio: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: COLORS.border,
    justifyContent: 'center',
    alignItems: 'center',
  },
  activeRadio: {
    borderColor: COLORS.primary,
    backgroundColor: COLORS.primary,
  },
  itemRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: SPACING.md,
  },
  itemLeft: {
    flex: 1,
    marginRight: SPACING.sm,
  },
  itemName: {
    color: COLORS.textPrimary,
    fontSize: 13,
    fontWeight: '600',
  },
  itemVariant: {
    color: COLORS.textTertiary,
    fontSize: 11,
    marginTop: SPACING.xs,
  },
  itemQtyPrice: {
    color: COLORS.textSecondary,
    fontSize: 13,
    fontWeight: '500',
  },
  divider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginVertical: SPACING.md,
  },
  priceRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: SPACING.sm,
  },
  priceLabel: {
    color: COLORS.textTertiary,
    fontSize: 13,
  },
  priceValue: {
    color: COLORS.textPrimary,
    fontSize: 13,
    fontWeight: '500',
  },
  totalLabel: {
    color: COLORS.textPrimary,
    fontSize: 15,
    fontWeight: '700',
  },
  totalValue: {
    color: COLORS.warning,
    fontSize: 16,
    fontWeight: '800',
  },
  stickyBottomBar: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: 80,
    backgroundColor: COLORS.surface,
    borderTopWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: SPACING.lg,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    shadowColor: '#000',
    ...Platform.select({
      ios: {
        shadowOpacity: 0.15,
        shadowOffset: { width: 0, height: -3 },
        shadowRadius: 6,
      },
      web: {
        boxShadow: '0px -3px 6px rgba(0, 0, 0, 0.15)',
      },
    }),
    elevation: 10,
  },
  bottomPriceContainer: {
    justifyContent: 'center',
  },
  bottomPriceLabel: {
    fontSize: 11,
    color: COLORS.textTertiary,
    fontWeight: '600',
    marginBottom: SPACING.xs,
  },
  bottomPrice: {
    fontSize: 18,
    fontWeight: '800',
    color: COLORS.warning,
  },
  placeOrderBtn: {
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.md,
    paddingHorizontal: SPACING.lg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  disabledBtn: {
    backgroundColor: COLORS.border,
    opacity: 0.6,
  },
  placeOrderBtnText: {
    color: COLORS.white,
    fontSize: 15,
    fontWeight: '700',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalSheet: {
    backgroundColor: COLORS.background,
    borderTopLeftRadius: RADIUS.lg,
    borderTopRightRadius: RADIUS.lg,
    padding: SPACING.lg,
    maxHeight: '75%',
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: SPACING.lg,
  },
  modalTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  closeBtn: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: COLORS.surface,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
});
