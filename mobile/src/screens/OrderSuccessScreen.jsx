import React, { useEffect, useRef } from 'react';
import { StyleSheet, Text, View, TouchableOpacity, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import { Feather } from '@expo/vector-icons';
import api from '../services/api';

export default function OrderSuccessScreen({ route, navigation }) {
  const { order } = route.params || {};
  const paymentConfirmed = route.params?.paymentConfirmed;
  const emailRequested = useRef(false);

  useEffect(() => {
    const orderId = order?.id_dathang || order?.id_donhang || order?.id;
    const method = String(order?.PTTT || '').toLowerCase();
    const paid = paymentConfirmed || order?.trang_thai_thanh_toan === 'paid' || method === 'cod';
    if (!orderId || !paid || emailRequested.current) return;
    emailRequested.current = true;
    api.post(`/orders/send-email/${orderId}`).catch(() => {});
  }, [order, paymentConfirmed]);

  const formatPrice = (value) => {
    return parseFloat(value || 0).toLocaleString('vi-VN') + 'đ';
  };

  const handleGoHome = () => {
    navigation.navigate('Main', { screen: 'Trang chủ' });
  };

  const handleGoOrderDetail = () => {
    if (order) {
      navigation.navigate('OrderDetail', { order: order });
    } else {
      navigation.navigate('OrderHistory');
    }
  };

  const getPaymentLabel = () => {
    if (order?.PTTT === 'VNPay') return 'Thanh toán online (VNPay)';
    if (order?.PTTT === 'MoMo' || order?.PTTT === 'momo') return 'Thanh toán online (MoMo)';
    if (order?.PTTT === 'SePay') return 'Chuyển khoản tự động (SePay)';
    return 'Thanh toán khi nhận hàng (COD)';
  };

  return (
    <SafeAreaView style={styles.container} edges={['top', 'bottom']}>
      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* Success Icon Badge */}
        <View style={styles.successBadgeContainer}>
          <View style={styles.iconCircle}>
            <Feather name="check" size={54} color={COLORS.white} />
          </View>
          <Text style={styles.successTitle}>Đặt hàng thành công!</Text>
          <Text style={styles.successSubtitle}>Cảm ơn bạn đã tin tưởng và chọn mua sắm tại cửa hàng của chúng tôi.</Text>
        </View>

        {/* Order Details Card */}
        {order && (
          <View style={styles.detailsCard}>
            <Text style={styles.detailsTitle}>Thông tin đơn hàng</Text>
            
            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Mã đơn hàng</Text>
              <Text style={styles.detailValue}>#{order.madonhang || order.id_donhang}</Text>
            </View>

            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Phương thức thanh toán</Text>
              <Text style={styles.detailValue}>
                {getPaymentLabel()}
              </Text>
            </View>

            <View style={styles.detailRow}>
              <Text style={styles.detailLabel}>Tổng thanh toán</Text>
              <Text style={styles.totalPrice}>{formatPrice(order.tongtien)}</Text>
            </View>

            <View style={styles.divider} />

            <Text style={styles.shippingTitle}>Địa chỉ giao hàng</Text>
            <Text style={styles.shippingName}>{order.tennguoinhan || 'Người nhận'}</Text>
            <Text style={styles.shippingPhone}>{order.sodienthoai || 'Số điện thoại'}</Text>
            <Text style={styles.shippingAddress}>{order.diachigiaohang || 'Địa chỉ nhận hàng'}</Text>
          </View>
        )}

        {/* Action Buttons */}
        <View style={styles.buttonContainer}>
          <TouchableOpacity style={styles.primaryBtn} onPress={handleGoOrderDetail}>
            <Text style={styles.primaryBtnText}>Xem chi tiết đơn hàng</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.secondaryBtn} onPress={handleGoHome}>
            <Text style={styles.secondaryBtnText}>Tiếp tục mua sắm</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scrollContent: {
    padding: SPACING.xl,
    alignItems: 'center',
    paddingBottom: 40,
    width: '100%',
  },
  successBadgeContainer: {
    alignItems: 'center',
    marginVertical: SPACING.xxl,
  },
  iconCircle: {
    width: 96,
    height: 96,
    borderRadius: 48,
    backgroundColor: '#10b981', // Shopee / standard success green
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#10b981',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.3,
    shadowRadius: 10,
    elevation: 8,
    marginBottom: SPACING.xl,
  },
  successTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: SPACING.md,
    textAlign: 'center',
  },
  successSubtitle: {
    fontSize: 14,
    color: COLORS.textTertiary,
    textAlign: 'center',
    lineHeight: 20,
    paddingHorizontal: SPACING.xl,
  },
  detailsCard: {
    width: '100%',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.xl,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: SPACING.xxl,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
  },
  detailsTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.lg,
  },
  detailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: SPACING.md,
  },
  detailLabel: {
    fontSize: 13,
    color: COLORS.textSecondary,
  },
  detailValue: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.textPrimary,
  },
  totalPrice: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.warning,
  },
  divider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginVertical: SPACING.lg,
  },
  shippingTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.sm,
  },
  shippingName: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.textPrimary,
    marginBottom: 4,
  },
  shippingPhone: {
    fontSize: 13,
    color: COLORS.textSecondary,
    marginBottom: 8,
  },
  shippingAddress: {
    fontSize: 13,
    color: COLORS.textTertiary,
    lineHeight: 18,
  },
  buttonContainer: {
    width: '100%',
    gap: SPACING.md,
  },
  primaryBtn: {
    backgroundColor: COLORS.primary,
    paddingVertical: 14,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    justifyContent: 'center',
    width: '100%',
  },
  primaryBtnText: {
    color: COLORS.white,
    fontWeight: '700',
    fontSize: 15,
  },
  secondaryBtn: {
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: 14,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    justifyContent: 'center',
    width: '100%',
  },
  secondaryBtnText: {
    color: COLORS.textSecondary,
    fontWeight: '600',
    fontSize: 15,
  },
});
