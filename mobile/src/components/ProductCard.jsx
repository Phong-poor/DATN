import React, { useMemo, useCallback } from 'react';
import { StyleSheet, Text, View, TouchableOpacity, Alert, Platform } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { getImageUrl } from '../services/api';
import useCartStore from '../store/useCartStore';
import useWishlistStore from '../store/useWishlistStore';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import OptimizedImage from './OptimizedImage';
import { showAlert } from '../utils/alert';

// Format price helper - extracted to module level
const formatPrice = (value) => {
  if (!value) return 'Liên hệ';
  return parseFloat(value).toLocaleString('vi-VN') + 'đ';
};

function ProductCard({ product, onPress, style }) {
  const navigation = useNavigation();
  const addToCart = useCartStore((state) => state.addToCart);

  const addToWishlist = useWishlistStore((state) => state.addToWishlist);
  const removeFromWishlist = useWishlistStore((state) => state.removeFromWishlist);
  const isFav = useWishlistStore((state) => state.isInWishlist(product.id_sanpham));

  // Memoize computed values
  const { defaultVariant, price, specText, imageUrl } = useMemo(() => {
    const variant = product.bien_thes && product.bien_thes.length > 0 ? product.bien_thes[0] : null;
    return {
      defaultVariant: variant,
      price: variant ? variant.gia : 0,
      specText: variant ? variant.ten_bienthe : '',
      imageUrl: getImageUrl(product.hinhanh)
    };
  }, [product.bien_thes, product.hinhanh]);

  const formattedPrice = useMemo(() => formatPrice(price), [price]);

  const handleAddToCart = useCallback(() => {
    addToCart(product, 1, defaultVariant);
    showAlert(
      'Thành công',
      `Đã thêm "${product.tenSP}" vào giỏ hàng!`,
      [
        {
          text: 'Xem chi tiết',
          onPress: () => navigation.navigate('ProductDetail', { productId: product.id_sanpham })
        },
        {
          text: 'Xem giỏ hàng',
          onPress: () => navigation.navigate('Giỏ hàng')
        }
      ]
    );
  }, [product, defaultVariant, addToCart, navigation]);

  const handleToggleWishlist = useCallback(() => {
    if (isFav) {
      removeFromWishlist(product.id_sanpham);
      showAlert('Thông báo', `Đã xoá "${product.tenSP}" khỏi danh sách yêu thích!`);
    } else {
      addToWishlist(product);
      showAlert('Thành công', `Đã thêm "${product.tenSP}" vào danh sách yêu thích!`);
    }
  }, [isFav, product, addToWishlist, removeFromWishlist]);

  const handlePress = useCallback(() => {
    if (onPress) {
      onPress();
    } else {
      navigation.navigate('ProductDetail', { productId: product.id_sanpham });
    }
  }, [onPress, navigation, product.id_sanpham]);

  return (
    <TouchableOpacity style={[styles.card, style]} onPress={handlePress} activeOpacity={0.8}>
      <View style={styles.imageContainer}>
        {imageUrl ? (
          <OptimizedImage source={{ uri: imageUrl }} style={styles.image} contentFit="contain" />
        ) : (
          <Text style={styles.fallbackIcon}>💻</Text>
        )}
        <TouchableOpacity style={styles.favBtn} onPress={handleToggleWishlist} activeOpacity={0.7}>
          <Text style={[styles.favIcon, isFav && styles.favIconActive]}>{isFav ? '❤️' : '🤍'}</Text>
        </TouchableOpacity>
      </View>
      
      <View style={styles.info}>
        <Text style={styles.brand}>{product.thuong_hieu?.ten_thuonghieu || 'Chính hãng'}</Text>
        <Text style={styles.name} numberOfLines={2}>{product.tenSP}</Text>
        
        {specText ? (
          <View style={styles.specBadge}>
            <Text style={styles.specText} numberOfLines={1} ellipsizeMode="tail">{specText}</Text>
          </View>
        ) : null}

        <View style={styles.footer}>
          <Text style={styles.price} numberOfLines={1} adjustsFontSizeToFit>{formattedPrice}</Text>
          <TouchableOpacity style={styles.addBtn} onPress={handleAddToCart}>
            <Text style={styles.addBtnText}>🛒 +</Text>
          </TouchableOpacity>
        </View>
      </View>
    </TouchableOpacity>
  );
}

// Memoize component with custom comparison
export default React.memo(ProductCard, (prevProps, nextProps) => {
  // Only re-render if product id or style changed
  return (
    prevProps.product.id_sanpham === nextProps.product.id_sanpham &&
    prevProps.style === nextProps.style &&
    prevProps.onPress === nextProps.onPress
  );
});

const styles = StyleSheet.create({
  card: {
    width: '48%',
    maxWidth: 220,
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: SPACING.lg,
    overflow: 'hidden',
    justifyContent: 'space-between',
    elevation: 3,
    ...Platform.select({
      ios: {
        shadowColor: '#000',
        shadowOpacity: 0.1,
        shadowOffset: { width: 0, height: 2 },
        shadowRadius: 4,
      },
      web: {
        boxShadow: '0px 2px 4px rgba(0, 0, 0, 0.1)',
      },
    }),
  },
  imageContainer: {
    height: 120,
    backgroundColor: COLORS.background,
    justifyContent: 'center',
    alignItems: 'center',
    overflow: 'hidden',
    padding: SPACING.sm,
    position: 'relative',
  },
  favBtn: {
    position: 'absolute',
    top: 6,
    right: 6,
    backgroundColor: 'rgba(30, 41, 59, 0.7)',
    borderRadius: 14,
    width: 28,
    height: 28,
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 10,
  },
  favIcon: {
    fontSize: 14,
    color: '#94a3b8',
  },
  favIconActive: {
    color: '#ef4444',
  },
  image: {
    width: '100%',
    height: '100%',
  },
  fallbackIcon: {
    fontSize: 44,
  },
  info: {
    padding: SPACING.md,
    flex: 1,
    justifyContent: 'space-between',
  },
  brand: {
    ...TYPOGRAPHY.labelSmall,
    color: COLORS.primary,
    fontWeight: '700',
    textTransform: 'uppercase',
    marginBottom: SPACING.xs,
  },
  name: {
    ...TYPOGRAPHY.titleSmall,
    color: COLORS.textPrimary,
    marginBottom: SPACING.sm,
    height: 38,
    lineHeight: 18,
  },
  specBadge: {
    alignSelf: 'flex-start',
    backgroundColor: COLORS.border,
    borderRadius: RADIUS.sm,
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.sm,
    marginBottom: SPACING.md,
  },
  specText: {
    color: COLORS.textSecondary,
    ...TYPOGRAPHY.labelSmall,
    fontWeight: '600',
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: SPACING.xs,
  },
  price: {
    ...TYPOGRAPHY.titleMedium,
    color: COLORS.warning,
    flex: 1,
  },
  addBtn: {
    backgroundColor: COLORS.primary,
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.md,
    borderRadius: RADIUS.md,
  },
  addBtnText: {
    color: COLORS.white,
    ...TYPOGRAPHY.labelSmall,
    fontWeight: '700',
  },
});
