import React, { useEffect } from 'react';
import { StyleSheet, Text, View, FlatList, TouchableOpacity, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import { useNavigation } from '@react-navigation/native';
import useWishlistStore from '../store/useWishlistStore';
import useCartStore from '../store/useCartStore';
import { getImageUrl } from '../services/api';
import OptimizedImage from '../components/OptimizedImage';
import { showAlert } from '../utils/alert';

export default function WishlistScreen() {
  const navigation = useNavigation();
  const items = useWishlistStore((state) => state.items);
  const fetchWishlist = useWishlistStore((state) => state.fetchWishlist);
  const removeFromWishlist = useWishlistStore((state) => state.removeFromWishlist);
  const addToCart = useCartStore((state) => state.addToCart);

  // Sync wishlist on mount
  useEffect(() => {
    fetchWishlist();
  }, [fetchWishlist]);

  const formatPrice = (value) => {
    if (!value) return 'Liên hệ';
    return parseFloat(value).toLocaleString('vi-VN') + 'đ';
  };

  const handleAddToCart = (product) => {
    const variant = product.bien_thes && product.bien_thes.length > 0 ? product.bien_thes[0] : null;
    addToCart(product, 1, variant);
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
  };

  const renderItem = ({ item }) => {
    const imageUrl = getImageUrl(item.hinhanh);
    const variant = item.bien_thes && item.bien_thes.length > 0 ? item.bien_thes[0] : null;
    const price = variant ? variant.gia : 0;

    return (
      <TouchableOpacity 
        style={styles.card}
        onPress={() => navigation.navigate('ProductDetail', { productId: item.id_sanpham })}
        activeOpacity={0.8}
      >
        <View style={styles.imgContainer}>
          {imageUrl ? (
            <OptimizedImage source={{ uri: imageUrl }} style={styles.img} contentFit="contain" />
          ) : (
            <Text style={styles.imgText}>💻</Text>
          )}
        </View>
        
        <View style={styles.details}>
          <Text style={styles.name} numberOfLines={2}>{item.tenSP}</Text>
          <Text style={styles.price}>{formatPrice(price)}</Text>
          
          <TouchableOpacity 
            style={styles.addCartBtn} 
            onPress={() => handleAddToCart(item)}
          >
            <Text style={styles.addCartText}>🛒 Thêm vào giỏ</Text>
          </TouchableOpacity>
        </View>

        <TouchableOpacity 
          onPress={() => removeFromWishlist(item.id_sanpham)} 
          style={styles.removeBtn}
        >
          <Text style={styles.removeBtnText}>🗑️</Text>
        </TouchableOpacity>
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backBtnText}>❮</Text>
        </TouchableOpacity>
        <Text style={styles.title}>Sản phẩm yêu thích</Text>
      </View>

      {items.length > 0 ? (
        <FlatList
          data={items}
          keyExtractor={(item) => item.id_sanpham.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContainer}
        />
      ) : (
        <View style={styles.emptyContainer}>
          <Text style={styles.emptyIcon}>❤️</Text>
          <Text style={styles.emptyText}>Danh sách yêu thích trống</Text>
          <TouchableOpacity 
            style={styles.exploreBtn} 
            onPress={() => navigation.navigate('Main', { screen: 'Trang chủ' })}
          >
            <Text style={styles.exploreText}>Khám phá ngay</Text>
          </TouchableOpacity>
        </View>
      )}
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
    backgroundColor: COLORS.surface,
    flexDirection: 'row',
    alignItems: 'center',
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
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  listContainer: {
    padding: SPACING.lg,
  },
  card: {
    flexDirection: 'row',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.md,
    marginBottom: SPACING.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    position: 'relative',
  },
  imgContainer: {
    width: 80,
    height: 80,
    backgroundColor: COLORS.background,
    borderRadius: RADIUS.md,
    justifyContent: 'center',
    alignItems: 'center',
    overflow: 'hidden',
    padding: SPACING.xs,
  },
  img: {
    width: '100%',
    height: '100%',
  },
  imgText: {
    fontSize: 24,
  },
  details: {
    flex: 1,
    marginLeft: SPACING.md,
    marginRight: 32, // space for remove button
    justifyContent: 'space-between',
  },
  name: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.textPrimary,
    lineHeight: 20,
  },
  price: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.warning,
    marginVertical: SPACING.xs,
  },
  addCartBtn: {
    backgroundColor: COLORS.primary,
    paddingVertical: 6,
    paddingHorizontal: SPACING.md,
    borderRadius: RADIUS.md,
    alignSelf: 'flex-start',
  },
  addCartText: {
    color: COLORS.white,
    fontSize: 12,
    fontWeight: '600',
  },
  removeBtn: {
    position: 'absolute',
    top: SPACING.md,
    right: SPACING.md,
    padding: SPACING.xs,
  },
  removeBtnText: {
    fontSize: 16,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: SPACING.xl,
  },
  emptyIcon: {
    fontSize: 64,
    marginBottom: SPACING.lg,
    color: COLORS.border,
  },
  emptyText: {
    fontSize: 15,
    color: COLORS.textTertiary,
    marginBottom: SPACING.xl,
  },
  exploreBtn: {
    backgroundColor: COLORS.primary,
    paddingVertical: SPACING.md,
    paddingHorizontal: SPACING.xl,
    borderRadius: RADIUS.lg,
  },
  exploreText: {
    color: COLORS.white,
    fontWeight: '700',
    fontSize: 14,
  },
});
