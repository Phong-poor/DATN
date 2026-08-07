import React, { useCallback, useState, useEffect } from 'react';
import { ActivityIndicator, StyleSheet, Text, View, FlatList, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import useCartStore from '../store/useCartStore';
import useAuthStore from '../store/useAuthStore';
import api, { getImageUrl } from '../services/api';
import OptimizedImage from '../components/OptimizedImage';
import CustomAlert from '../components/CustomAlert';
import { showAlert } from '../utils/alert';
import { Feather } from '@expo/vector-icons';

export default function CartScreen() {
  const navigation = useNavigation();
  const token = useAuthStore((state) => state.token);

  const [alertVisible, setAlertVisible] = useState(false);
  const [syncing, setSyncing] = useState(false);
  const [updatingId, setUpdatingId] = useState(null);

  const items = useCartStore((state) => state.items);
  const updateQuantity = useCartStore((state) => state.updateQuantity);
  const removeFromCart = useCartStore((state) => state.removeFromCart);

  useFocusEffect(
    useCallback(() => {
      if (!token) return undefined;
      let active = true;
      setSyncing(true);
      useCartStore.getState().syncLocalCartToServer()
        .catch((err) => console.log('[Cart Server Sync Error]', err))
        .finally(() => active && setSyncing(false));
      return () => { active = false; };
    }, [token])
  );

  // Sync cart item stocks from the database API upon opening the cart
  useEffect(() => {
    const syncStock = async () => {
      try {
        for (const item of items) {
          if (item.productId) {
            const res = await api.get(`/sanpham/${item.productId}`);
            const product = res.data;
            if (product) {
              let latestStock = 999;
              if (item.variantId && product.bien_thes) {
                const variant = product.bien_thes.find(v => v.id_bienthe === item.variantId);
                if (variant) {
                  latestStock = parseInt(variant.soluong || 0);
                }
              } else {
                latestStock = parseInt(product.so_luong || product.soluong || 999);
              }
              useCartStore.getState().updateMaxStock(item.id, latestStock);
            }
          }
        }
      } catch (err) {
        console.log('[Cart Sync Stock Error]', err);
      }
    };

    if (items.length > 0) {
      syncStock();
    }
  }, []);

  const formatPrice = (value) => {
    return parseFloat(value).toLocaleString('vi-VN') + 'đ';
  };

  const totalAmount = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

  const handleCheckout = () => {
    if (!token) {
      setAlertVisible(true);
      return;
    }

    navigation.navigate('Checkout');
  };

  const handleDelete = (item) => {
    showAlert(
      'Xác nhận xóa',
      `Bạn có chắc chắn muốn xóa sản phẩm "${item.name}" khỏi giỏ hàng không?`,
      [
        { text: 'Hủy', style: 'cancel' },
        {
          text: 'Xóa',
          style: 'destructive',
          onPress: async () => {
            if (!token || !item.serverId) {
              removeFromCart(item.id);
              return;
            }
            setUpdatingId(item.comboGroupId || item.id);
            try {
              await useCartStore.getState().removeServerItem(item);
            } catch (err) {
              showAlert('Lỗi', err.response?.data?.message || 'Không thể xóa sản phẩm khỏi giỏ hàng.');
            } finally {
              setUpdatingId(null);
            }
          },
        }
      ]
    );
  };

  const handleQuantityChange = async (item, quantity) => {
    if (quantity <= 0) return;
    if (!token || !item.serverId) {
      updateQuantity(item.id, quantity);
      return;
    }
    setUpdatingId(item.comboGroupId || item.id);
    try {
      await useCartStore.getState().updateServerQuantity(item, quantity);
    } catch (err) {
      showAlert('Lỗi', err.response?.data?.message || 'Không thể cập nhật số lượng.');
    } finally {
      setUpdatingId(null);
    }
  };

  const renderItem = ({ item }) => {
    const imageUrl = getImageUrl(item.image);

    return (
      <View style={styles.card}>
        <View style={styles.imgContainer}>
          {imageUrl ? (
            <OptimizedImage source={{ uri: imageUrl }} style={styles.img} contentFit="contain" />
          ) : (
            <Text style={styles.imgText}>💻</Text>
          )}
        </View>
        <View style={styles.details}>
          <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
          {item.variantName && (
            <Text style={styles.variantName}>{item.variantName}</Text>
          )}
          {!!item.comboId && <Text style={styles.comboName}>Combo: {item.comboName || 'Ưu đãi theo bộ'}</Text>}
          <Text style={styles.price}>{formatPrice(item.price)}</Text>
          <View style={styles.quantityRow}>
            <TouchableOpacity 
              onPress={() => handleQuantityChange(item, item.quantity - 1)}
              style={[styles.qtyBtn, item.quantity <= 1 && styles.qtyBtnDisabled]}
              disabled={item.quantity <= 1}
            >
              <Feather 
                name="minus" 
                size={14} 
                color={item.quantity <= 1 ? COLORS.textSecondary : COLORS.textPrimary} 
              />
            </TouchableOpacity>
            <Text style={styles.qtyText}>{item.quantity}</Text>
            <TouchableOpacity 
              onPress={() => handleQuantityChange(item, item.quantity + 1)}
              style={[styles.qtyBtn, item.quantity >= (item.maxStock || 999) && styles.qtyBtnDisabled]}
              disabled={item.quantity >= (item.maxStock || 999)}
            >
              <Feather 
                name="plus" 
                size={14} 
                color={item.quantity >= (item.maxStock || 999) ? COLORS.textSecondary : COLORS.textPrimary} 
              />
            </TouchableOpacity>
          </View>
        </View>
        <TouchableOpacity onPress={() => handleDelete(item)} style={styles.removeBtn}>
          {updatingId === (item.comboGroupId || item.id)
            ? <ActivityIndicator size="small" color={COLORS.error} />
            : <Feather name="trash-2" size={18} color={COLORS.error} />}
        </TouchableOpacity>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backBtnText}>❮</Text>
        </TouchableOpacity>
        <Text style={styles.title}>Giỏ hàng của bạn</Text>
      </View>

      {items.length > 0 ? (
        <>
          <FlatList
            data={items}
            keyExtractor={(item) => item.id}
            renderItem={renderItem}
            contentContainerStyle={styles.listContainer}
          />
          <View style={styles.footer}>
            <View style={styles.totalRow}>
              <Text style={styles.totalLabel}>Tổng cộng:</Text>
              <Text style={styles.totalValue}>{formatPrice(totalAmount)}</Text>
            </View>
            <TouchableOpacity style={styles.checkoutBtn} onPress={handleCheckout}>
              <Text style={styles.checkoutText}>Thanh toán ngay</Text>
            </TouchableOpacity>
          </View>
        </>
      ) : (
        <View style={styles.emptyContainer}>
          <Feather name="shopping-cart" size={58} color={COLORS.textSecondary} />
          <Text style={styles.emptyText}>Giỏ hàng của bạn đang trống</Text>
        </View>
      )}

      {syncing && (
        <View style={styles.syncBadge}>
          <ActivityIndicator size="small" color={COLORS.white} />
          <Text style={styles.syncBadgeText}>Đang đồng bộ giỏ hàng...</Text>
        </View>
      )}

      <CustomAlert
        visible={alertVisible}
        type="warning"
        title="Yêu cầu đăng nhập"
        message="Vui lòng đăng nhập tài khoản trước khi tiến hành thanh toán và đặt hàng."
        confirmText="Đăng nhập"
        cancelText="Để sau"
        onCancel={() => setAlertVisible(false)}
        onConfirm={() => {
          setAlertVisible(false);
          navigation.navigate('Main', { screen: 'Tài khoản' });
        }}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  header: {
    paddingHorizontal: SPACING.lg,
    paddingVertical: SPACING.md,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
  },
  backBtn: {
    marginRight: SPACING.md,
    padding: SPACING.xs,
  },
  backBtnText: {
    fontSize: 18,
    color: COLORS.textPrimary,
  },
  title: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  listContainer: {
    padding: SPACING.lg,
  },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.md,
    marginBottom: SPACING.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  imgContainer: {
    width: 80,
    height: 80,
    borderRadius: RADIUS.md,
    backgroundColor: COLORS.background,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: SPACING.md,
    overflow: 'hidden',
    padding: 6,
    flexShrink: 0,
  },
  img: {
    width: '100%',
    height: '100%',
    borderRadius: RADIUS.md,
  },
  imgText: {
    fontSize: 32,
  },
  details: {
    flex: 1,
  },
  name: {
    fontSize: 15,
    fontWeight: '600',
    color: COLORS.textPrimary,
    marginBottom: SPACING.xs,
  },
  variantName: {
    fontSize: 11,
    color: COLORS.primary,
    fontWeight: '600',
    marginBottom: SPACING.xs,
  },
  comboName: {
    fontSize: 10,
    color: COLORS.success,
    fontWeight: '700',
    marginBottom: SPACING.xs,
  },
  price: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.warning,
    marginBottom: SPACING.sm,
  },
  quantityRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  qtyBtn: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: COLORS.border,
    justifyContent: 'center',
    alignItems: 'center',
  },
  qtyBtnDisabled: {
    opacity: 0.3,
  },
  qtyBtnText: {
    color: COLORS.textPrimary,
    fontSize: 16,
    fontWeight: '600',
  },
  qtyBtnTextDisabled: {
    color: COLORS.textSecondary,
  },
  qtyText: {
    color: COLORS.textPrimary,
    paddingHorizontal: 12,
    fontSize: 14,
    fontWeight: '600',
  },
  removeBtn: {
    padding: SPACING.xs,
    justifyContent: 'center',
    alignItems: 'center',
  },
  removeBtnText: {
    fontSize: 18,
  },
  footer: {
    backgroundColor: COLORS.surface,
    borderTopWidth: 1,
    borderColor: COLORS.border,
    padding: SPACING.lg,
    borderTopLeftRadius: RADIUS.xl,
    borderTopRightRadius: RADIUS.xl,
  },
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: SPACING.lg,
  },
  totalLabel: {
    fontSize: 16,
    color: COLORS.textTertiary,
  },
  totalValue: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.warning,
  },
  checkoutBtn: {
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.lg,
    paddingVertical: SPACING.lg,
    alignItems: 'center',
  },
  checkoutText: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: '600',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: SPACING.lg,
  },
  emptyIcon: {
    fontSize: 64,
    marginBottom: SPACING.lg,
    color: COLORS.textTertiary,
  },
  emptyText: {
    color: COLORS.textTertiary,
    fontSize: 16,
  },
  syncBadge: {
    position: 'absolute',
    top: 72,
    alignSelf: 'center',
    flexDirection: 'row',
    alignItems: 'center',
    gap: SPACING.sm,
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.full,
    paddingHorizontal: SPACING.md,
    paddingVertical: SPACING.sm,
  },
  syncBadgeText: { color: COLORS.white, fontSize: 11, fontWeight: '700' },
});
