import React, { useCallback, useEffect, useRef, useState } from 'react';
import { StyleSheet, Text, View, FlatList, TouchableOpacity, Image, ActivityIndicator, Alert, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import api, { getImageUrl } from '../services/api';
import { OrderHistorySkeleton } from '../components/SkeletonLoader';
import { SHIPPING_FEE } from '../constants/pricing';
import logger from '../utils/logger';
import useCartStore from '../store/useCartStore';

let activeOrdersRequest = null;

const requestOrders = () => {
  if (!activeOrdersRequest) {
    activeOrdersRequest = api.get('/orders').finally(() => {
      activeOrdersRequest = null;
    });
  }

  return activeOrdersRequest;
};

export default function OrderHistoryScreen({ navigation }) {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [reorderingId, setReorderingId] = useState(null);
  const [loadError, setLoadError] = useState('');
  const mountedRef = useRef(true);

  const fetchOrders = useCallback(async () => {
    setLoadError('');
    try {
      const response = await requestOrders();
      if (!response.data?.success) {
        throw new Error(response.data?.message || 'Invalid orders response');
      }

      if (mountedRef.current) {
        setOrders(response.data.orders || []);
      }
    } catch (error) {
      logger.log('Error fetching orders:', error);
      if (mountedRef.current) {
        setLoadError('Không thể tải lịch sử đơn hàng. Vui lòng thử lại.');
      }
    } finally {
      if (mountedRef.current) {
        setLoading(false);
        setRefreshing(false);
      }
    }
  }, []);

  useEffect(() => {
    mountedRef.current = true;
    fetchOrders();

    return () => {
      mountedRef.current = false;
    };
  }, [fetchOrders]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchOrders();
  };

  const handleCancelOrder = (orderId) => {
    Alert.alert(
      'Hủy đơn hàng',
      'Bạn có chắc chắn muốn hủy đơn hàng này không?',
      [
        { text: 'Bỏ qua', style: 'cancel' },
        {
          text: 'Đồng ý hủy',
          style: 'destructive',
          onPress: async () => {
            try {
              setLoading(true);
              const response = await api.post(`/orders/${orderId}/cancel`);
              if (response.data?.success || response.status === 200) {
                Alert.alert('Thành công', 'Đơn hàng của bạn đã được hủy thành công.');
                fetchOrders();
              }
            } catch (error) {
              logger.log('Cancel order failed:', error);
              const msg = error.response?.data?.message || 'Không thể hủy đơn hàng này.';
              Alert.alert('Lỗi', msg);
              setLoading(false);
            }
          }
        }
      ]
    );
  };

  const handleReorder = async (orderId) => {
    if (reorderingId) return;
    setReorderingId(orderId);
    try {
      const response = await api.post(`/orders/${orderId}/reorder`);
      if (response.data?.success) {
        const cartResponse = await api.get('/gio-hang');
        useCartStore.getState().replaceWithServerCart(cartResponse.data?.gio_hang || []);
        const skipped = response.data?.skipped || [];
        Alert.alert(
          'Đã thêm vào giỏ hàng',
          skipped.length > 0
            ? `${response.data.message || 'Đã thêm các sản phẩm còn hàng.'}\nCó ${skipped.length} sản phẩm không thể thêm.`
            : (response.data.message || 'Các sản phẩm đã được thêm vào giỏ hàng.'),
          [
            { text: 'Ở lại', style: 'cancel' },
            { text: 'Xem giỏ hàng', onPress: () => navigation.navigate('Giỏ hàng') },
          ]
        );
      }
    } catch (error) {
      logger.log('Reorder failed:', error);
      Alert.alert('Không thể mua lại', error.response?.data?.message || 'Vui lòng thử lại sau.');
    } finally {
      setReorderingId(null);
    }
  };

  const formatPrice = (value) => {
    return parseFloat(value).toLocaleString('vi-VN') + 'đ';
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

  const getStatusDetails = (status) => {
    switch (status) {
      case 'pending':
      case 'processing':
        return { label: 'Chờ xử lý', color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.15)' };
      case 'shipping':
        return { label: 'Đang giao hàng', color: '#3b82f6', bg: 'rgba(59, 130, 246, 0.15)' };
      case 'done':
      case 'completed':
        return { label: 'Đã hoàn thành', color: '#10b981', bg: 'rgba(16, 185, 129, 0.15)' };
      case 'cancelled':
        return { label: 'Đã hủy', color: '#ef4444', bg: 'rgba(239, 68, 68, 0.15)' };
      default:
        return { label: status, color: '#94a3b8', bg: 'rgba(148, 163, 184, 0.15)' };
    }
  };

  const renderOrderItem = ({ item }) => {
    const status = getStatusDetails(item.trangthai);
    const subtotal = item.chi_tiets?.reduce((sum, det) => sum + det.gia * det.soluong, 0) || 0;
    const total = subtotal + SHIPPING_FEE; // Total includes shipping

    return (
      <TouchableOpacity 
        style={styles.orderCard} 
        activeOpacity={0.9} 
        onPress={() => navigation.navigate('OrderDetail', { order: item })}
      >
        {/* Card Header */}
        <View style={styles.orderHeader}>
          <View>
            <Text style={styles.orderId}>Đơn hàng #{item.id_dathang}</Text>
            <Text style={styles.orderDate}>{formatDate(item.created_at)}</Text>
          </View>
          <View style={[styles.statusBadge, { backgroundColor: status.bg }]}>
            <Text style={[styles.statusText, { color: status.color }]}>{status.label}</Text>
          </View>
        </View>

        {/* Product Items List */}
        <View style={styles.productsList}>
          {item.chi_tiets?.map((detail, idx) => {
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
                    <Text style={styles.productQty}>SL: {detail.soluong}</Text>
                  </View>
                </View>
              </View>
            );
          })}
        </View>

        <View style={styles.divider} />

        {/* Card Footer */}
        <View style={styles.orderFooter}>
          <View style={styles.totalWrapper}>
            <Text style={styles.totalLabel}>Tổng tiền (gồm ship):</Text>
            <Text style={styles.totalValue}>{formatPrice(total)}</Text>
          </View>
          
          {item.trangthai === 'pending' && (
            <TouchableOpacity
              style={styles.cancelBtn}
              onPress={() => handleCancelOrder(item.id_dathang)}
            >
              <Text style={styles.cancelBtnText}>Hủy đơn</Text>
            </TouchableOpacity>
          )}
          {['done', 'completed', 'cancelled'].includes(item.trangthai) && (
            <TouchableOpacity
              style={styles.reorderBtn}
              onPress={() => handleReorder(item.id_dathang)}
              disabled={reorderingId === item.id_dathang}
            >
              {reorderingId === item.id_dathang ? (
                <ActivityIndicator size="small" color={COLORS.white} />
              ) : (
                <Text style={styles.reorderBtnText}>Mua lại</Text>
              )}
            </TouchableOpacity>
          )}
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* Header back bar */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>❮</Text>
          <Text style={styles.backText}>Quay lại</Text>
        </TouchableOpacity>
        <Text style={styles.topTitle}>Lịch sử đơn hàng</Text>
      </View>

      {loading && !refreshing ? (
        <OrderHistorySkeleton />
      ) : (
        <FlatList
          data={orders}
          keyExtractor={(item) => item.id_dathang.toString()}
          renderItem={renderOrderItem}
          contentContainerStyle={styles.listContainer}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#6366f1" />
          }
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Text style={styles.emptyIcon}>{loadError ? '!' : '📦'}</Text>
              <Text style={styles.emptyText}>{loadError || 'Bạn chưa đặt đơn hàng nào.'}</Text>
              {loadError ? (
                <TouchableOpacity
                  style={styles.retryBtn}
                  onPress={() => {
                    setLoading(true);
                    fetchOrders();
                  }}
                >
                  <Text style={styles.retryBtnText}>Thử lại</Text>
                </TouchableOpacity>
              ) : null}
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: COLORS.textSecondary,
    marginTop: SPACING.md,
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
  listContainer: {
    padding: SPACING.lg,
    paddingBottom: SPACING.xxxl,
  },
  orderCard: {
    backgroundColor: '#1e293b',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#334155',
    padding: 16,
    marginBottom: 16,
    elevation: 3,
  },
  orderHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 14,
  },
  orderId: {
    color: '#f8fafc',
    fontSize: 15,
    fontWeight: '700',
  },
  orderDate: {
    color: '#94a3b8',
    fontSize: 12,
    marginTop: 2,
  },
  statusBadge: {
    paddingVertical: 4,
    paddingHorizontal: 10,
    borderRadius: 6,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  productsList: {
    marginBottom: 6,
  },
  productRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  imgContainer: {
    width: 50,
    height: 50,
    borderRadius: 8,
    backgroundColor: '#0f172a',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
    overflow: 'hidden',
  },
  productImg: {
    width: '100%',
    height: '100%',
  },
  imgText: {
    fontSize: 24,
  },
  productDetails: {
    flex: 1,
  },
  productName: {
    color: '#f8fafc',
    fontSize: 13,
    fontWeight: '600',
  },
  variantName: {
    color: '#94a3b8',
    fontSize: 11,
    marginTop: 2,
  },
  priceQtyRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 4,
  },
  productPrice: {
    color: '#f59e0b',
    fontSize: 12,
    fontWeight: '600',
  },
  productQty: {
    color: '#cbd5e1',
    fontSize: 12,
  },
  divider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginVertical: SPACING.md,
  },
  orderFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  totalWrapper: {
    flex: 1,
  },
  totalLabel: {
    color: COLORS.textTertiary,
    fontSize: 11,
  },
  totalValue: {
    color: COLORS.warning,
    fontSize: 16,
    fontWeight: '800',
    marginTop: SPACING.xs,
  },
  cancelBtn: {
    backgroundColor: COLORS.error,
    borderWidth: 1,
    borderColor: COLORS.error,
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.md,
  },
  cancelBtnText: {
    color: '#fca5a5',
    fontSize: 12,
    fontWeight: '600',
  },
  reorderBtn: {
    minWidth: 88,
    minHeight: 36,
    paddingHorizontal: SPACING.md,
    borderRadius: RADIUS.md,
    backgroundColor: COLORS.primary,
    justifyContent: 'center',
    alignItems: 'center',
  },
  reorderBtnText: {
    color: COLORS.white,
    fontSize: 12,
    fontWeight: '700',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingTop: 120,
  },
  emptyIcon: {
    fontSize: 48,
    color: COLORS.border,
    marginBottom: SPACING.lg,
  },
  emptyText: {
    color: COLORS.textTertiary,
    fontSize: 14,
    textAlign: 'center',
  },
  retryBtn: {
    marginTop: SPACING.lg,
    paddingVertical: SPACING.sm,
    paddingHorizontal: SPACING.xl,
    borderRadius: RADIUS.md,
    backgroundColor: COLORS.primary,
  },
  retryBtnText: {
    color: COLORS.white,
    fontSize: 14,
    fontWeight: '700',
  },
});
