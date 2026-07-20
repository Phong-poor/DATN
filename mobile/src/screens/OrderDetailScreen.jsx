import React from 'react';
import { StyleSheet, Text, View, ScrollView, TouchableOpacity, Image } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import { Feather } from '@expo/vector-icons';
import { getImageUrl } from '../services/api';
import { SHIPPING_FEE } from '../constants/pricing';

export default function OrderDetailScreen({ route, navigation }) {
  const { order } = route.params || {};

  const formatPrice = (value) => {
    return parseFloat(value || 0).toLocaleString('vi-VN') + 'đ';
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getStatusLabel = (status) => {
    switch (status) {
      case 'pending':
      case 'processing':
        return { label: 'Đang chuẩn bị hàng', color: '#f59e0b', icon: 'clock' };
      case 'shipping':
        return { label: 'Đang giao hàng', color: '#3b82f6', icon: 'truck' };
      case 'done':
      case 'completed':
        return { label: 'Đã hoàn thành', color: '#10b981', icon: 'check-circle' };
      case 'cancelled':
        return { label: 'Đã hủy', color: '#ef4444', icon: 'x-circle' };
      default:
        return { label: status, color: '#94a3b8', icon: 'info' };
    }
  };

  // Determine which index of the timeline stepper is active
  const getTimelineIndex = (status) => {
    switch (status) {
      case 'pending':
      case 'processing':
        return 1; // Step 2 active
      case 'shipping':
        return 2; // Step 3 active
      case 'done':
      case 'completed':
        return 3; // Step 4 active
      case 'cancelled':
        return -1; // Special canceled layout
      default:
        return 0;
    }
  };

  const activeStep = getTimelineIndex(order?.trangthai);
  const statusDetails = getStatusLabel(order?.trangthai);

  // Normal delivery timeline nodes
  const timelineSteps = [
    { title: 'Đặt đơn thành công', desc: 'Đơn hàng của bạn đã được khởi tạo trên hệ thống.', stepIndex: 0 },
    { title: 'Đang chuẩn bị hàng', desc: 'Người bán đang đóng gói sản phẩm và chuẩn bị gửi đi.', stepIndex: 1 },
    { title: 'Đang giao hàng', desc: 'Đơn hàng đã giao cho đơn vị vận chuyển và đang trên đường giao tới bạn.', stepIndex: 2 },
    { title: 'Giao hàng thành công', desc: 'Đơn hàng đã được giao nhận thành công và hoàn tất.', stepIndex: 3 },
  ];

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* Header */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>❮</Text>
          <Text style={styles.backText}>Quay lại</Text>
        </TouchableOpacity>
        <Text style={styles.topTitle}>Chi tiết đơn hàng</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* Status Header */}
        <View style={styles.statusHeaderCard}>
          <Feather name={statusDetails.icon} size={28} color={statusDetails.color} />
          <View style={styles.statusHeaderInfo}>
            <Text style={styles.statusHeaderLabel}>Trạng thái đơn hàng</Text>
            <Text style={[styles.statusHeaderValue, { color: statusDetails.color }]}>
              {statusDetails.label}
            </Text>
          </View>
        </View>

        {/* Shipping Timeline Tracker */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Hành trình đơn hàng</Text>

          {order?.trangthai === 'cancelled' ? (
            <View style={styles.cancelledTimeline}>
              <View style={styles.cancelledIconCircle}>
                <Feather name="x" size={24} color={COLORS.white} />
              </View>
              <View style={styles.cancelledTextContainer}>
                <Text style={styles.cancelledTitle}>Đơn hàng đã bị hủy</Text>
                <Text style={styles.cancelledDesc}>
                  Đơn hàng đã được hủy bỏ vào lúc {formatDate(order.updated_at || order.created_at)}. Số tiền thanh toán (nếu có) sẽ được hoàn lại theo chính sách.
                </Text>
              </View>
            </View>
          ) : (
            <View style={styles.timelineContainer}>
              {timelineSteps.map((step, idx) => {
                const isCompleted = idx < activeStep;
                const isActive = idx === activeStep;
                const isFuture = idx > activeStep;

                let nodeColor = '#cbd5e1'; // gray
                if (isActive || isCompleted) {
                  nodeColor = '#10b981'; // emerald green
                }

                return (
                  <View key={idx} style={styles.timelineRow}>
                    {/* Left Timeline Line & Bullet */}
                    <View style={styles.timelineLeftColumn}>
                      <View style={[styles.timelineLine, idx === 0 && { top: '50%' }, idx === timelineSteps.length - 1 && { bottom: '50%' }, { backgroundColor: isCompleted ? '#10b981' : '#cbd5e1' }]} />
                      {isActive ? (
                        <View style={styles.truckCircle}>
                          <Feather name="truck" size={12} color={COLORS.white} />
                        </View>
                      ) : (
                        <View style={[styles.bulletDot, { backgroundColor: nodeColor }]} />
                      )}
                    </View>

                    {/* Right Timeline Details */}
                    <View style={styles.timelineTextContainer}>
                      <Text style={[styles.timelineStepTitle, isActive && styles.activeStepText, isFuture && styles.futureStepText]}>
                        {step.title}
                      </Text>
                      <Text style={[styles.timelineStepDesc, isFuture && styles.futureStepText]}>
                        {step.desc}
                      </Text>
                      {isActive && (
                        <Text style={styles.timelineStepDate}>{formatDate(order?.updated_at || order?.created_at)}</Text>
                      )}
                    </View>
                  </View>
                );
              })}
            </View>
          )}
        </View>

        {/* Shipping Address */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Địa chỉ nhận hàng</Text>
          <View style={styles.addressContainer}>
            <Feather name="map-pin" size={16} color={COLORS.primary} style={styles.addressIcon} />
            <View style={styles.addressInfo}>
              <Text style={styles.recipientName}>{order?.tennguoinhan || 'Người nhận'}</Text>
              <Text style={styles.recipientPhone}>{order?.sodienthoai || 'Số điện thoại'}</Text>
              <Text style={styles.recipientAddress}>{order?.diachigiaohang || 'Địa chỉ giao hàng'}</Text>
            </View>
          </View>
        </View>

        {/* Product Items List */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Sản phẩm đã đặt</Text>
          {order?.chi_tiets?.map((detail, idx) => {
            const product = detail.bien_the?.san_pham || {};
            const imageUrl = getImageUrl(product.hinhanh);

            return (
              <View key={idx} style={styles.productRow}>
                <View style={styles.imgContainer}>
                  {imageUrl ? (
                    <Image source={{ uri: imageUrl }} style={styles.productImg} resizeMode="contain" />
                  ) : (
                    <Text style={styles.imgText}>💻</Text>
                  )}
                </View>
                <View style={styles.productDetails}>
                  <Text style={styles.productName} numberOfLines={1}>{product.tenSP || 'Sản phẩm'}</Text>
                  <Text style={styles.variantName}>
                    Cấu hình: {detail.bien_the?.ten_bienthe || 'Mặc định'}
                  </Text>
                  <View style={styles.priceQtyRow}>
                    <Text style={styles.productPrice}>{formatPrice(detail.gia)}</Text>
                    <Text style={styles.productQty}>x{detail.soluong}</Text>
                  </View>
                  {(order?.trangthai === 'done' || order?.trangthai === 'completed') && (
                    <TouchableOpacity
                      style={{
                        alignSelf: 'flex-start',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        paddingVertical: 4,
                        paddingHorizontal: 12,
                        borderRadius: 6,
                        marginTop: 6,
                      }}
                      onPress={() => navigation.navigate('ProductDetail', {
                        productId: product.id_sanpham,
                        triggerReview: {
                          id_dathang: order.id_dathang || order.id_donhang,
                          id_bienthe: detail.id_bienthe,
                          ten_bienthe: detail.bien_the?.ten_bienthe || 'Mặc định'
                        }
                      })}
                    >
                      <Text style={{ color: COLORS.primary, fontSize: 11, fontWeight: '700' }}>Viết đánh giá</Text>
                    </TouchableOpacity>
                  )}
                </View>
              </View>
            );
          })}
        </View>

        {/* Payment and Billing Details */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Thông tin thanh toán</Text>

          <View style={styles.billingRow}>
            <Text style={styles.billingLabel}>Mã hóa đơn</Text>
            <Text style={styles.billingValue}>#{order?.madonhang || order?.id_dathang}</Text>
          </View>

          <View style={styles.billingRow}>
            <Text style={styles.billingLabel}>Thời gian đặt</Text>
            <Text style={styles.billingValue}>{formatDate(order?.created_at)}</Text>
          </View>

          <View style={styles.billingRow}>
            <Text style={styles.billingLabel}>Phương thức thanh toán</Text>
            <Text style={styles.billingValue}>
              {order?.PTTT === 'VNPay' ? 'Online qua cổng VNPay' : 'Thanh toán tiền mặt (COD)'}
            </Text>
          </View>

          <View style={styles.divider} />

          <View style={styles.billingRow}>
            <Text style={styles.billingLabel}>Tiền hàng</Text>
            <Text style={styles.billingValue}>
              {formatPrice(order?.chi_tiets?.reduce((sum, det) => sum + det.gia * det.soluong, 0) || 0)}
            </Text>
          </View>

          <View style={styles.billingRow}>
            <Text style={styles.billingLabel}>Phí vận chuyển</Text>
            <Text style={styles.billingValue}>{formatPrice(SHIPPING_FEE)}</Text>
          </View>

          {order?.giamgia > 0 && (
            <View style={styles.billingRow}>
              <Text style={styles.billingLabel}>Voucher giảm giá</Text>
              <Text style={[styles.billingValue, { color: '#10b981' }]}>
                -{formatPrice(order.giamgia)}
              </Text>
            </View>
          )}

          <View style={styles.divider} />

          <View style={styles.totalRow}>
            <Text style={styles.totalLabel}>Tổng thanh toán</Text>
            <Text style={styles.totalValue}>{formatPrice(order?.tongtien)}</Text>
          </View>
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
  },
  statusHeaderCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: SPACING.lg,
  },
  statusHeaderInfo: {
    marginLeft: SPACING.md,
  },
  statusHeaderLabel: {
    fontSize: 11,
    color: COLORS.textTertiary,
    fontWeight: '600',
    marginBottom: 2,
  },
  statusHeaderValue: {
    fontSize: 16,
    fontWeight: '700',
  },
  sectionCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.lg,
    marginBottom: SPACING.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.lg,
    borderLeftWidth: 3,
    borderLeftColor: COLORS.primary,
    paddingLeft: SPACING.sm,
  },
  // Timeline styles
  timelineContainer: {
    paddingLeft: SPACING.xs,
  },
  timelineRow: {
    flexDirection: 'row',
    minHeight: 70,
  },
  timelineLeftColumn: {
    alignItems: 'center',
    width: 24,
    marginRight: SPACING.md,
  },
  timelineLine: {
    position: 'absolute',
    top: 0,
    bottom: 0,
    width: 2,
  },
  bulletDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    marginTop: 6,
    zIndex: 2,
  },
  truckCircle: {
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: '#10b981',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 2,
    shadowColor: '#10b981',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 3,
    elevation: 4,
  },
  timelineTextContainer: {
    flex: 1,
    paddingBottom: SPACING.lg,
  },
  timelineStepTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#10b981', // green for completed
    marginBottom: 4,
  },
  activeStepText: {
    color: '#10b981',
    fontSize: 13,
    fontWeight: '800',
  },
  futureStepText: {
    color: COLORS.textTertiary,
    fontWeight: '500',
  },
  timelineStepDesc: {
    fontSize: 12,
    color: COLORS.textSecondary,
    lineHeight: 18,
    marginBottom: 4,
  },
  timelineStepDate: {
    fontSize: 10,
    color: COLORS.textTertiary,
    fontWeight: '500',
  },
  // Cancelled timeline styles
  cancelledTimeline: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: SPACING.sm,
  },
  cancelledIconCircle: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#ef4444',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: SPACING.md,
  },
  cancelledTextContainer: {
    flex: 1,
  },
  cancelledTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: '#ef4444',
    marginBottom: 4,
  },
  cancelledDesc: {
    fontSize: 12,
    color: COLORS.textSecondary,
    lineHeight: 18,
  },
  // Address styles
  addressContainer: {
    flexDirection: 'row',
  },
  addressIcon: {
    marginTop: 3,
    marginRight: SPACING.md,
  },
  addressInfo: {
    flex: 1,
  },
  recipientName: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  recipientPhone: {
    fontSize: 13,
    color: COLORS.textSecondary,
    marginBottom: 6,
  },
  recipientAddress: {
    fontSize: 13,
    color: COLORS.textTertiary,
    lineHeight: 18,
  },
  // Product list styles
  productRow: {
    flexDirection: 'row',
    marginBottom: SPACING.lg,
  },
  imgContainer: {
    width: 60,
    height: 60,
    backgroundColor: COLORS.background,
    borderRadius: RADIUS.md,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  productImg: {
    width: 50,
    height: 50,
  },
  imgText: {
    fontSize: 20,
  },
  productDetails: {
    flex: 1,
    marginLeft: SPACING.md,
    justifyContent: 'center',
  },
  productName: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  variantName: {
    fontSize: 11,
    color: COLORS.textTertiary,
    marginBottom: 4,
  },
  priceQtyRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  productPrice: {
    fontSize: 12,
    color: COLORS.warning,
    fontWeight: '700',
  },
  productQty: {
    fontSize: 12,
    color: COLORS.textSecondary,
    fontWeight: '500',
  },
  // Billing styles
  billingRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: SPACING.md,
  },
  billingLabel: {
    fontSize: 13,
    color: COLORS.textSecondary,
  },
  billingValue: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.textPrimary,
  },
  divider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginVertical: SPACING.md,
  },
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  totalLabel: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  totalValue: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.warning,
  },
});
