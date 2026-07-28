import React, { useEffect, useState, useRef, useCallback } from 'react';
import { StyleSheet, Text, View, ScrollView, TouchableOpacity, Image, ActivityIndicator, useWindowDimensions, Alert, Platform, TextInput, Modal, KeyboardAvoidingView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import api, { getImageUrl } from '../services/api';
import useCartStore from '../store/useCartStore';
import useWishlistStore from '../store/useWishlistStore';
import useAuthStore from '../store/useAuthStore';
import useRecentlyViewedStore from '../store/useRecentlyViewedStore';
import { DetailSkeleton } from '../components/SkeletonLoader';
import OptimizedImage from '../components/OptimizedImage';
import ProductCard from '../components/ProductCard';
import logger from '../utils/logger';
import { FontAwesome } from '@expo/vector-icons';
import { showAlert } from '../utils/alert';

const getColorHex = (val) => {
  if (val.hex) return val.hex;
  const name = String(val.giatri || '').trim().toLowerCase();
  const colorMap = {
    'trắng': '#ffffff',
    'đen': '#1e293b', // slate-800 is better than pure black for dark theme visibility
    'black': '#1e293b',
    'white': '#ffffff',
    'xanh': '#3b82f6',
    'xanh dương': '#3b82f6',
    'xanh lá': '#10b981',
    'đỏ': '#ef4444',
    'vàng': '#f59e0b',
    'hồng': '#ec4899',
    'tím': '#8b5cf6',
    'cam': '#f97316',
    'xám': '#6b7280',
    'gray': '#6b7280',
    'nâu': '#78350f',
    'bạc': '#cbd5e1',
    'silver': '#cbd5e1',
    'gold': '#fbbf24',
    'vàng hồng': '#fda4af',
  };
  return colorMap[name] || null;
};

const parseArrayLike = (value) => {
  if (Array.isArray(value)) return value;
  if (!value) return [];
  if (typeof value === 'string') {
    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }
  return [];
};

const specLabelForValue = (value = '') => {
  const text = String(value).toLowerCase();
  if (text.includes('rtx') || text.includes('gtx') || text.includes('gpu')) return 'GPU';
  if (text.includes('core') || text.includes('ryzen') || text.includes('intel') || text.includes('amd')) return 'CPU';
  if (text.includes('ram') || text.includes('gb ram')) return 'RAM';
  if (text.includes('ssd') || text.includes('tb') || text.includes('storage')) return 'SSD';
  if (text.includes('hz')) return 'Tần số quét';
  if (text.includes('oled') || text.includes('ips') || text.includes('inch')) return 'Màn hình';
  return 'Thông số';
};

const specsArrayToTechnicalSpecs = (specs = []) => {
  if (!Array.isArray(specs)) return [];
  return specs
    .filter(Boolean)
    .map((value, index) => ({
      id_thuoctinh: `card-spec-${index}`,
      ten_thuoctinh: specLabelForValue(value),
      giatri: String(value),
    }));
};

const normalizeTechnicalSpecs = (data = {}, variants = []) => {
  const directSpecs = parseArrayLike(data.thong_so_ky_thuat)
    .map((spec, index) => {
      if (typeof spec === 'string') {
        return {
          id_thuoctinh: `text-spec-${index}`,
          ten_thuoctinh: specLabelForValue(spec),
          giatri: spec,
        };
      }

      return {
        id_thuoctinh: spec?.id_thuoctinh || `spec-${index}`,
        ten_thuoctinh: spec?.ten_thuoctinh || spec?.label || spec?.name || specLabelForValue(spec?.giatri || spec?.value || ''),
        giatri: spec?.giatri || spec?.value || spec?.text || '',
      };
    })
    .filter((spec) => spec.ten_thuoctinh && spec.giatri);

  if (directSpecs.length > 0) return directSpecs;

  const cardSpecs = parseArrayLike(data.specs);
  if (cardSpecs.length > 0) return specsArrayToTechnicalSpecs(cardSpecs);

  const variantAttrs = [];
  const firstVariantWithAttrs = variants.find((variant) => {
    let attr = variant?.thuoc_tinh || variant?.attributes;
    if (!attr && variant?.thuoc_tinh_json) {
      try {
        attr = typeof variant.thuoc_tinh_json === 'string'
          ? JSON.parse(variant.thuoc_tinh_json)
          : variant.thuoc_tinh_json;
      } catch (e) {
        attr = [];
      }
    }
    return Array.isArray(attr) && attr.length > 0;
  });

  if (firstVariantWithAttrs) {
    let attrs = firstVariantWithAttrs.thuoc_tinh || firstVariantWithAttrs.attributes;
    if (!attrs && firstVariantWithAttrs.thuoc_tinh_json) {
      try {
        attrs = typeof firstVariantWithAttrs.thuoc_tinh_json === 'string'
          ? JSON.parse(firstVariantWithAttrs.thuoc_tinh_json)
          : firstVariantWithAttrs.thuoc_tinh_json;
      } catch (e) {}
    }
    if (Array.isArray(attrs)) {
      attrs.forEach((attr, index) => {
        variantAttrs.push({
          id_thuoctinh: attr.id_thuoctinh || `variant-spec-${index}`,
          ten_thuoctinh: attr.ten_thuoctinh || specLabelForValue(attr.giatri),
          giatri: attr.giatri,
        });
      });
    }
  }

  return variantAttrs;
};

const enrichProductForDetail = (data = {}) => {
  const existingVariants = data.bien_thes || data.bienThes || [];
  const technicalSpecs = normalizeTechnicalSpecs(data, existingVariants);
  const hasRealSingleVariant = Number.isInteger(Number(data.id_bienthe)) && Number(data.id_bienthe) > 0;
  const fallbackVariant = hasRealSingleVariant ? {
    id_bienthe: data.id_bienthe,
    ten_bienthe: data.variantName || data.tenSP || 'Cấu hình tiêu chuẩn',
    gia: data.gia || 0,
    soluong: data.inStock === false ? 0 : 1,
    hinhanh: data.hinhanh || data.image || '',
    isSingleVariantFallback: true,
    thuoc_tinh: technicalSpecs.map((spec) => ({
      id_thuoctinh: spec.id_thuoctinh,
      ten_thuoctinh: spec.ten_thuoctinh,
      giatri: spec.giatri,
    })),
  } : null;
  const variants = existingVariants.length > 0 ? existingVariants : (fallbackVariant ? [fallbackVariant] : []);
  const firstVariantImage = variants.find((variant) => variant?.hinhanh)?.hinhanh;

  return {
    ...data,
    hinhanh: data.hinhanh || data.image || firstVariantImage || '',
    hinh_anhs: data.hinh_anhs || data.hinhAnhs || [],
    hinhAnhs: data.hinhAnhs || data.hinh_anhs || [],
    thong_so_ky_thuat: technicalSpecs,
    bien_thes: variants,
    bienThes: variants,
  };
};

export default function ProductDetailScreen({ route, navigation }) {
  const { productId } = route.params;
  const addToCart = useCartStore((state) => state.addToCart);
  
  const addToWishlist = useWishlistStore((state) => state.addToWishlist);
  const removeFromWishlist = useWishlistStore((state) => state.removeFromWishlist);
  const isFav = useWishlistStore((state) => state.isInWishlist(productId));
  const { token } = useAuthStore();

  const { width: windowWidth } = useWindowDimensions();

  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  
  // Selection States
  const [selectedVariant, setSelectedVariant] = useState(null);
  const [selectedOptions, setSelectedOptions] = useState({});
  const [activeImage, setActiveImage] = useState(null);
  const [activeImageIndex, setActiveImageIndex] = useState(0);

  const imageScrollRef = useRef(null);

  // Reviews and related products states
  const [reviews, setReviews] = useState([]);
  const [relatedProducts, setRelatedProducts] = useState([]);
  const [combos, setCombos] = useState([]);

  // Review form state
  const [reviewModalVisible, setReviewModalVisible] = useState(false);
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewComment, setReviewComment] = useState('');
  const [submittingReview, setSubmittingReview] = useState(false);
  const [doneOrders, setDoneOrders] = useState([]);
  const [showAllReviews, setShowAllReviews] = useState(false);

  // Helper: extract attributes from a variant
  const getVariantAttributes = (variant) => {
    let attr = variant?.thuoc_tinh || variant?.attributes;
    if (!attr && variant?.thuoc_tinh_json) {
      try {
        attr = typeof variant.thuoc_tinh_json === 'string'
          ? JSON.parse(variant.thuoc_tinh_json)
          : variant.thuoc_tinh_json;
      } catch (e) {
        attr = [];
      }
    }
    return Array.isArray(attr) ? attr : [];
  };

  const fetchProductDetail = useCallback(async () => {
    try {
      setLoading(true);
      const res = await api.get(`/sanpham/${productId}`);
      const data = enrichProductForDetail(res.data);

      // Normalize product details similar to web frontend
      const variants = data.bien_thes || [];
      
      // Find default available variant or first variant
      const defaultVariant = variants.find(v => parseFloat(v.soluong || 0) > 0) || variants[0] || null;

      setProduct(data);
      setSelectedVariant(defaultVariant);
      setActiveImage(data.hinhanh);
      
      // Save to recently viewed products
      useRecentlyViewedStore.getState().addProduct(data);
      if (useAuthStore.getState().token) {
        api.post(`/sanpham-daxem/${productId}`).catch((error) => {
          logger.log('Failed to sync recently viewed product:', error);
        });
      }

      // Initialize selected options
      if (defaultVariant) {
        const initialOptions = {};
        getVariantAttributes(defaultVariant).forEach(attr => {
          initialOptions[attr.ten_thuoctinh] = attr.giatri;
        });
        setSelectedOptions(initialOptions);
        if (defaultVariant.hinhanh) {
          setActiveImage(defaultVariant.hinhanh);
        }
      }

      // Render main product details instantly
      setLoading(false);

      // Auto-open review modal if triggered from orders
      if (route.params?.triggerReview) {
        setTimeout(() => {
          handleOpenReview();
        }, 600);
      }

      // Fetch reviews, related products, and combos in parallel
      try {
        const [reviewsRes, relatedRes, combosRes] = await Promise.all([
          api.get(`/sanpham/${productId}/reviews`),
          api.get(`/sanpham?id_danhmuc=${data.id_danhmuc}`),
          api.get('/combos').catch(() => ({ data: [] }))
        ]);

        if (reviewsRes.data && reviewsRes.data.success) {
          setReviews(reviewsRes.data.reviews || []);
        }

        if (relatedRes.data) {
          const items = relatedRes.data.data || relatedRes.data || [];
          const filtered = items.filter(p => p.id_sanpham !== productId);
          setRelatedProducts(filtered.slice(0, 6));
        }

        // Filter active combos containing this product
        const allCombos = combosRes.data?.data || combosRes.data || [];
        if (Array.isArray(allCombos)) {
          const matched = allCombos.filter(c => c.products?.some(p => p.id_sanpham === productId));
          setCombos(matched);
        }
      } catch (err) {
        logger.log('Error fetching secondary product details:', err);
      }
    } catch (err) {
      logger.log('Error fetching product details:', err);
      Alert.alert('Lỗi', 'Không thể tải chi tiết sản phẩm. Vui lòng thử lại sau.');
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  }, [productId, navigation]);

  useEffect(() => {
    fetchProductDetail();
  }, [fetchProductDetail]);

  const handleSelectOption = (groupName, value) => {
    const updatedOptions = { ...selectedOptions, [groupName]: value };
    setSelectedOptions(updatedOptions);

    // Find variant matching the new combination
    const variants = product.bien_thes || [];
    const matched = variants.find(variant =>
      Object.entries(updatedOptions).every(([attrName, attrValue]) => {
        const found = getVariantAttributes(variant).find(i => i.ten_thuoctinh === attrName);
        return found && found.giatri === attrValue;
      })
    );

    if (matched) {
      setSelectedVariant(matched);
      if (matched.hinhanh) {
        setActiveImage(matched.hinhanh);
      }
    } else {
      setSelectedVariant(null); // No configuration available for this combo
    }
  };

  const handleAddToCart = async () => {
    if (!selectedVariant) {
      showAlert('Thông báo', 'Cấu hình này hiện không khả dụng. Vui lòng chọn cấu hình khác!');
      return;
    }

    if (parseInt(selectedVariant.soluong || 0) === 0) {
      showAlert('Thông báo', 'Sản phẩm cấu hình này đã hết hàng!');
      return;
    }

    try {
      if (token) {
        await useCartStore.getState().addProductToServer(selectedVariant.id_bienthe, 1);
      } else {
        addToCart(product, 1, selectedVariant);
      }
    } catch (error) {
      showAlert('Lỗi', error.response?.data?.message || 'Không thể thêm sản phẩm vào giỏ hàng.');
      return;
    }
    showAlert(
      'Thành công',
      `Đã thêm "${product.tenSP}" cấu hình "${selectedVariant.ten_bienthe}" vào giỏ hàng!`,
      [
        {
          text: 'Xem giỏ hàng',
          onPress: () => navigation.navigate('Giỏ hàng')
        }
      ]
    );
  };

  const handleBuyNow = async () => {
    if (!selectedVariant || Number(selectedVariant.soluong || 0) <= 0) return;
    if (!token) {
      showAlert('Cần đăng nhập', 'Vui lòng đăng nhập để sử dụng chức năng mua ngay và thanh toán.');
      return;
    }
    try {
      await useCartStore.getState().addProductToServer(selectedVariant.id_bienthe, 1);
      navigation.navigate('Checkout', { buyNowVariantId: selectedVariant.id_bienthe });
    } catch (error) {
      showAlert('Lỗi', error.response?.data?.message || 'Không thể tạo đơn mua ngay.');
    }
  };

  const handleAddComboToCart = async (combo) => {
    if (!combo || !combo.products) return;
    const selectedVariants = combo.products
      .map(p => p.bien_thes?.find(v => Number(v.soluong || 0) > 0) || p.bien_thes?.[0])
      .filter(Boolean);
    if (selectedVariants.length !== combo.products.length) {
      showAlert('Không thể thêm combo', 'Một sản phẩm trong combo chưa có biến thể khả dụng.');
      return;
    }
    try {
      if (token) {
        await useCartStore.getState().addComboToServer(
          combo.id_combo,
          selectedVariants.map(variant => variant.id_bienthe),
          1
        );
      } else {
        combo.products.forEach((p, index) => addToCart(p, 1, selectedVariants[index]));
      }
    } catch (error) {
      showAlert('Lỗi', error.response?.data?.message || 'Không thể thêm combo vào giỏ hàng.');
      return;
    }
    showAlert(
      'Thành công',
      `Đã thêm toàn bộ sản phẩm trong combo "${combo.ten_combo}" vào giỏ hàng!`,
      [
        {
          text: 'Xem giỏ hàng',
          onPress: () => navigation.navigate('Giỏ hàng')
        }
      ]
    );
  };

  const handleImageScroll = (event) => {
    const slideSize = event.nativeEvent.layoutMeasurement.width;
    const index = event.nativeEvent.contentOffset.x / slideSize;
    setActiveImageIndex(Math.round(index));
  };

  const handleToggleWishlist = useCallback(() => {
    if (!product) return;
    if (isFav) {
      removeFromWishlist(productId);
      showAlert('Thông báo', `Đã xoá "${product.tenSP}" khỏi danh sách yêu thích!`);
    } else {
      addToWishlist(product);
      showAlert('Thành công', `Đã thêm "${product.tenSP}" vào danh sách yêu thích!`);
    }
  }, [isFav, product, productId, addToWishlist, removeFromWishlist]);

  const fetchDoneOrders = useCallback(async () => {
    if (!token) return;
    try {
      const res = await api.get('/orders', { params: { trangthai: 'done' } });
      const orders = res.data?.orders || res.data?.data || res.data || [];
      // Filter orders that contain this product
      const relevant = Array.isArray(orders) ? orders.filter(order => {
        const items = order.chi_tiet_dat_hang || order.items || [];
        return items.some(item => {
          const bienthe = item.bienthe || item.bien_the || {};
          return bienthe.id_sanpham === productId || item.id_sanpham === productId;
        });
      }) : [];
      setDoneOrders(relevant);
    } catch {
      setDoneOrders([]);
    }
  }, [token, productId]);

  useEffect(() => {
    fetchDoneOrders();
  }, [fetchDoneOrders]);

  const handleOpenReview = () => {
    if (!token) {
      Alert.alert('Cần đăng nhập', 'Vui lòng đăng nhập để gửi đánh giá.');
      return;
    }
    if (!route.params?.triggerReview && doneOrders.length === 0) {
      Alert.alert('Thông báo', 'Bạn cần mua và nhận sản phẩm này trước khi đánh giá.');
      return;
    }
    setReviewRating(5);
    setReviewComment('');
    setReviewModalVisible(true);
  };

  const handleSubmitReview = async () => {
    let id_dathang = null;
    let id_bienthe = null;

    if (route.params?.triggerReview) {
      id_dathang = route.params.triggerReview.id_dathang;
      id_bienthe = route.params.triggerReview.id_bienthe;
    } else {
      if (doneOrders.length === 0 || !selectedVariant) return;
      const order = doneOrders[0];
      const items = order.chi_tiet_dat_hang || order.items || [];
      const matchedItem = items.find(item => {
        const bienthe = item.bienthe || item.bien_the || {};
        return bienthe.id_sanpham === productId;
      });
      id_dathang = order.id_dathang || order.id;
      id_bienthe = matchedItem?.bienthe?.id_bienthe || matchedItem?.id_bienthe || selectedVariant?.id_bienthe;
    }

    if (!id_dathang || !id_bienthe) {
      Alert.alert('Lỗi', 'Không tìm thấy thông tin đơn hàng hoặc biến thể để đánh giá.');
      return;
    }

    setSubmittingReview(true);
    try {
      await api.post('/danh-gia', {
        id_dathang,
        id_bienthe,
        danhgia: reviewRating,
        binhluan: reviewComment.trim() || null,
      });
      showAlert('Thành công', 'Đánh giá của bạn đã được gửi thành công!');
      setReviewModalVisible(false);
      // Refresh reviews
      try {
        const res = await api.get(`/sanpham/${productId}/reviews`);
        if (res.data?.success) setReviews(res.data.reviews || []);
      } catch {}
    } catch (err) {
      const msg = err.response?.data?.message || 'Không thể gửi đánh giá. Bạn có thể đã đánh giá rồi.';
      showAlert('Lỗi', msg);
    } finally {
      setSubmittingReview(false);
    }
  };

  if (loading || !product) {
    return <DetailSkeleton />;
  }

  // Generate variant groups (e.g. RAM: [8GB, 16GB], Color: [Vàng, Nâu])
  const variants = product.bien_thes || [];
  const optionGroupsMap = new Map();
  variants.forEach(variant => {
    getVariantAttributes(variant).forEach(attr => {
      if (!optionGroupsMap.has(attr.ten_thuoctinh)) {
        optionGroupsMap.set(attr.ten_thuoctinh, []);
      }
      const list = optionGroupsMap.get(attr.ten_thuoctinh);
      const exists = list.some(v => v.giatri === attr.giatri);
      if (!exists) {
        list.push({
          giatri: attr.giatri,
          hex: attr.ma_mau || attr.hex || null
        });
      }
    });
  });
  const variantGroups = Array.from(optionGroupsMap.entries()).map(([name, values]) => ({ name, values }));

  // Collect all images for slider
  const sliderImages = [];
  if (activeImage) sliderImages.push(activeImage);
  if (product.hinh_anhs && product.hinh_anhs.length > 0) {
    product.hinh_anhs.forEach(img => {
      const path = img.duongdan;
      if (path && path !== activeImage && !path.includes('tgdd.vn') && !path.includes('susercontent.com')) {
        sliderImages.push(path);
      }
    });
  }

  // Normalize Specs table
  const specs = [];
  if (selectedVariant) {
    getVariantAttributes(selectedVariant).forEach(attr => {
      specs.push({ label: attr.ten_thuoctinh, value: attr.giatri });
    });
  }
  if (product?.khoiluong) {
    specs.push({ label: 'Khối lượng', value: `${product.khoiluong} kg` });
  }
  if (product?.SKU) {
    specs.push({ label: 'Mã máy (SKU)', value: product.SKU });
  }

  // Extract description text from thong_so_ky_thuat
  const getDescription = () => {
    if (!product) return '';
    if (product.thong_so_ky_thuat && Array.isArray(product.thong_so_ky_thuat)) {
      const descItem = product.thong_so_ky_thuat.find(spec => {
        const label = (spec.ten_thuoctinh || '').toLowerCase();
        return label.includes('mô tả') || label.includes('description') || label.includes('mota');
      });
      if (descItem && descItem.giatri) {
        return descItem.giatri;
      }
    }
    if (typeof product.thong_so_ky_thuat === 'string' && product.thong_so_ky_thuat.length > 50) {
      return product.thong_so_ky_thuat;
    }
    return `${product.tenSP} là sản phẩm chính hãng của thương hiệu ${product.thuong_hieu?.ten_thuonghieu || 'Nexzen'}. Thiết bị sở hữu thiết kế tinh tế, độ hoàn thiện cao cùng hiệu năng mạnh mẽ vượt trội, đáp ứng hoàn hảo mọi nhu cầu của bạn.`;
  };

  const price = selectedVariant ? selectedVariant.gia : 0;
  const stock = selectedVariant ? selectedVariant.soluong : 0;

  const formatPrice = (value) => {
    if (!value) return 'Liên hệ';
    return parseFloat(value).toLocaleString('vi-VN') + 'đ';
  };

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* Top Header Back Bar */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>❮</Text>
          <Text style={styles.backText}>Quay lại</Text>
        </TouchableOpacity>
        <Text style={styles.topTitle} numberOfLines={1}>{product?.tenSP || 'Chi tiết sản phẩm'}</Text>
        {product && (
          <TouchableOpacity style={styles.favHeaderBtn} onPress={handleToggleWishlist} activeOpacity={0.7}>
            <Text style={styles.favHeaderIcon}>{isFav ? '❤️' : '🤍'}</Text>
          </TouchableOpacity>
        )}
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Image Slider */}
        <View style={styles.sliderWrapper}>
          <ScrollView
            ref={imageScrollRef}
            horizontal
            pagingEnabled
            showsHorizontalScrollIndicator={false}
            onMomentumScrollEnd={handleImageScroll}
            style={styles.imageSlider}
          >
            {sliderImages.map((img, idx) => (
              <View key={idx} style={[styles.slide, { width: windowWidth }]}>
                <OptimizedImage
                  source={{ uri: getImageUrl(img) }}
                  style={styles.productImage}
                  contentFit="contain"
                />
              </View>
            ))}
          </ScrollView>

          {/* Dots Indicator */}
          {sliderImages.length > 1 && (
            <View style={styles.dotsContainer}>
              {sliderImages.map((_, idx) => (
                <View
                  key={idx}
                  style={[
                    styles.dot,
                    activeImageIndex === idx ? styles.activeDot : null
                  ]}
                />
              ))}
            </View>
          )}
        </View>

        {/* Basic Info */}
        <View style={styles.infoSection}>
          <Text style={styles.brandName}>{product.thuong_hieu?.ten_thuonghieu || 'CHÍNH HÃNG'}</Text>
          <Text style={styles.productName}>{product.tenSP}</Text>
          
          <View style={styles.priceStockRow}>
            {selectedVariant ? (
              <Text style={styles.productPrice}>{formatPrice(price)}</Text>
            ) : (
              <Text style={styles.unavailablePrice}>Cấu hình không khả dụng</Text>
            )}
            
            <View style={styles.stockBadge}>
              <Text style={styles.stockText}>
                {selectedVariant ? `Kho: ${stock} máy` : 'Liên hệ'}
              </Text>
            </View>
          </View>
        </View>

        {/* Multi-Variant Selector */}
        {variantGroups.length > 0 && (
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Chọn cấu hình sản phẩm</Text>
            {variantGroups.map((group) => (
              <View key={group.name} style={styles.variantGroup}>
                <Text style={styles.variantGroupName}>{group.name}</Text>
                <View style={styles.optionsRow}>
                  {group.values.map((val) => {
                    const isSelected = selectedOptions[group.name] === val.giatri;
                    
                    const colorHex = getColorHex(val);
                    const isColorGroup = String(group.name).toLowerCase().includes('màu') || 
                                         String(group.name).toLowerCase().includes('color');

                    // Render color box if color option
                    if (isColorGroup && colorHex) {
                      return (
                        <TouchableOpacity
                          key={val.giatri}
                          style={[
                            styles.colorOption,
                            isSelected && styles.activeColorOption,
                          ]}
                          onPress={() => handleSelectOption(group.name, val.giatri)}
                        >
                          <View style={[styles.colorDot, { backgroundColor: colorHex }]} />
                          <Text style={[styles.colorText, isSelected && styles.activeColorText]}>
                            {val.giatri}
                          </Text>
                        </TouchableOpacity>
                      );
                    }

                    return (
                      <TouchableOpacity
                        key={val.giatri}
                        style={[
                          styles.optionBtn,
                          isSelected && styles.activeOptionBtn,
                        ]}
                        onPress={() => handleSelectOption(group.name, val.giatri)}
                      >
                        <Text style={[styles.optionText, isSelected && styles.activeOptionText]}>
                          {val.giatri}
                        </Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </View>
            ))}
          </View>
        )}

        {/* Combos list */}
        {combos && combos.length > 0 && (
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Ưu đãi mua theo Combo tiết kiệm</Text>
            {combos.map((c) => (
              <View key={c.id_combo} style={{ borderBottomWidth: 1, borderColor: COLORS.border, paddingBottom: 12, marginBottom: 12 }}>
                <Text style={{ fontWeight: '700', fontSize: 14, color: COLORS.textPrimary, marginBottom: 4 }}>
                  {c.ten_combo}
                </Text>
                <Text style={{ fontSize: 12, color: COLORS.textSecondary, marginBottom: 8 }}>
                  {c.mota}
                </Text>

                {/* List of products in combo */}
                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 10 }}>
                  {c.products?.map((p) => (
                    <View key={p.id_sanpham} style={{ width: 100, marginRight: 10, alignItems: 'center' }}>
                      <OptimizedImage source={{ uri: getImageUrl(p.hinhanh) }} style={{ width: 60, height: 60, borderRadius: RADIUS.md }} contentFit="contain" />
                      <Text numberOfLines={1} style={{ fontSize: 10, color: COLORS.textPrimary, marginTop: 4, textAlign: 'center' }}>
                        {p.tenSP}
                      </Text>
                    </View>
                  ))}
                </ScrollView>

                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                  <View>
                    <Text style={{ fontSize: 11, color: COLORS.textTertiary }}>Giá trọn bộ Combo:</Text>
                    <Text style={{ fontSize: 15, fontWeight: '800', color: COLORS.warning }}>
                      {parseFloat(c.giakhuyenmai || 0).toLocaleString('vi-VN')}₫
                    </Text>
                  </View>
                  <TouchableOpacity
                    style={{ backgroundColor: COLORS.primary, paddingVertical: 6, paddingHorizontal: 12, borderRadius: RADIUS.md }}
                    onPress={() => handleAddComboToCart(c)}
                  >
                    <Text style={{ color: '#fff', fontSize: 12, fontWeight: '700' }}>Mua cả bộ</Text>
                  </TouchableOpacity>
                </View>
              </View>
            ))}
          </View>
        )}

        {/* Technical Specification Table */}
        {specs.length > 0 && (
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Thông số kỹ thuật chi tiết</Text>
            <View style={styles.specTable}>
              {specs.map((item, idx) => (
                <View key={idx} style={[styles.specRow, idx % 2 === 0 ? styles.evenSpecRow : null]}>
                  <Text style={styles.specLabel}>{item.label}</Text>
                  <Text style={styles.specValue}>{item.value}</Text>
                </View>
              ))}
            </View>
          </View>
        )}

        {/* Description Section */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Mô tả sản phẩm</Text>
          <Text style={styles.descriptionText}>{getDescription()}</Text>
        </View>

        {/* Reviews Section */}
        <View style={styles.sectionCard}>
          {/* Rating summary */}
          {reviews.length > 0 && (() => {
            const avg = reviews.reduce((sum, r) => sum + (r.danhgia || 0), 0) / reviews.length;
            const counts = [5,4,3,2,1].map(star => ({
              star,
              count: reviews.filter(r => r.danhgia === star).length,
            }));
            return (
              <View style={styles.ratingSummary}>
                <View style={styles.ratingBigScore}>
                  <Text style={styles.ratingAvgNum}>{avg.toFixed(1)}</Text>
                  <View style={styles.ratingStarsRow}>
                    {[1,2,3,4,5].map(i => (
                      <FontAwesome key={i} name={i <= Math.round(avg) ? 'star' : 'star-o'} size={14} color="#f59e0b" />
                    ))}
                  </View>
                  <Text style={styles.ratingTotal}>{reviews.length} đánh giá</Text>
                </View>
                <View style={styles.ratingBars}>
                  {counts.map(({ star, count }) => (
                    <View key={star} style={styles.ratingBarRow}>
                      <Text style={styles.ratingBarLabel}>{star}★</Text>
                      <View style={styles.ratingBarBg}>
                        <View style={[styles.ratingBarFill, { width: `${reviews.length ? (count / reviews.length) * 100 : 0}%` }]} />
                      </View>
                      <Text style={styles.ratingBarCount}>{count}</Text>
                    </View>
                  ))}
                </View>
              </View>
            );
          })()}

          <View style={styles.reviewsHeaderRow}>
            <Text style={styles.sectionTitle}>Đánh giá ({reviews.length})</Text>
            <TouchableOpacity style={styles.writeReviewBtn} onPress={handleOpenReview}>
              <FontAwesome name="pencil" size={13} color={COLORS.primary} />
              <Text style={styles.writeReviewText}>Viết đánh giá</Text>
            </TouchableOpacity>
          </View>

          {reviews.length === 0 ? (
            <View style={styles.noReviewWrap}>
              <Text style={{ fontSize: 32 }}>⭐</Text>
              <Text style={styles.emptyText}>Chưa có đánh giá nào. Hãy là người đầu tiên!</Text>
            </View>
          ) : (
            <View style={styles.reviewsList}>
              {(showAllReviews ? reviews : reviews.slice(0, 3)).map((rev) => (
                <View key={rev.id_danhgia || rev.id} style={styles.reviewItem}>
                  <View style={styles.reviewHeader}>
                    <View style={styles.reviewAvatarRow}>
                      <View style={styles.reviewAvatar}>
                        <Text style={styles.reviewAvatarText}>
                          {(rev.user?.ten || rev.user?.name || 'K').charAt(0).toUpperCase()}
                        </Text>
                      </View>
                      <View>
                        <Text style={styles.reviewUser}>{rev.user?.ten || rev.user?.name || 'Khách hàng'}</Text>
                        <Text style={styles.reviewDate}>
                          {rev.created_at ? new Date(rev.created_at).toLocaleDateString('vi-VN') : ''}
                        </Text>
                      </View>
                    </View>
                    <View style={styles.starsRow}>
                      {Array.from({ length: 5 }).map((_, starIdx) => (
                        <FontAwesome
                          key={starIdx}
                          name={starIdx < rev.danhgia ? 'star' : 'star-o'}
                          size={13}
                          color="#f59e0b"
                        />
                      ))}
                    </View>
                  </View>
                  {rev.bienThe?.ten_bienthe && (
                    <View style={styles.reviewVariantBadge}>
                      <Text style={styles.reviewVariant}>Phân loại: {rev.bienThe.ten_bienthe}</Text>
                    </View>
                  )}
                  {rev.binhluan ? (
                    <Text style={styles.reviewComment}>{rev.binhluan}</Text>
                  ) : null}
                </View>
              ))}
              {reviews.length > 3 && (
                <TouchableOpacity
                  style={styles.showMoreBtn}
                  onPress={() => setShowAllReviews(v => !v)}
                >
                  <Text style={styles.showMoreText}>
                    {showAllReviews ? 'Thu gọn ▲' : `Xem tất cả ${reviews.length} đánh giá ▼`}
                  </Text>
                </TouchableOpacity>
              )}
            </View>
          )}
        </View>

        {/* Review Modal */}
        <Modal visible={reviewModalVisible} animationType="slide" transparent onRequestClose={() => setReviewModalVisible(false)}>
          <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
            <View style={styles.reviewModalOverlay}>
              <View style={styles.reviewModalSheet}>
                <Text style={styles.reviewModalTitle}>Đánh giá sản phẩm</Text>
                <Text style={styles.reviewModalSub}>{product?.tenSP}</Text>

                {/* Star picker */}
                <Text style={styles.reviewLabel}>Chất lượng sản phẩm</Text>
                <View style={styles.starPicker}>
                  {[1,2,3,4,5].map(star => (
                    <TouchableOpacity key={star} onPress={() => setReviewRating(star)}>
                      <FontAwesome
                        name={star <= reviewRating ? 'star' : 'star-o'}
                        size={36}
                        color="#f59e0b"
                        style={{ marginHorizontal: 6 }}
                      />
                    </TouchableOpacity>
                  ))}
                </View>
                <Text style={styles.ratingLabel}>
                  {reviewRating === 5 ? 'Tuyệt vời' : reviewRating === 4 ? 'Tốt' : reviewRating === 3 ? 'Bình thường' : reviewRating === 2 ? 'Tệ' : 'Rất tệ'}
                </Text>

                {/* Comment */}
                <Text style={styles.reviewLabel}>Nhận xét (tùy chọn)</Text>
                <TextInput
                  style={styles.reviewTextInput}
                  value={reviewComment}
                  onChangeText={setReviewComment}
                  placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm..."
                  placeholderTextColor={COLORS.textTertiary}
                  multiline
                  numberOfLines={4}
                  textAlignVertical="top"
                />

                <View style={styles.reviewModalActions}>
                  <TouchableOpacity
                    style={styles.reviewCancelBtn}
                    onPress={() => setReviewModalVisible(false)}
                  >
                    <Text style={styles.reviewCancelText}>Hủy</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={[styles.reviewSubmitBtn, submittingReview && { opacity: 0.6 }]}
                    onPress={handleSubmitReview}
                    disabled={submittingReview}
                  >
                    {submittingReview
                      ? <ActivityIndicator color="#fff" size="small" />
                      : <Text style={styles.reviewSubmitText}>Gửi đánh giá</Text>
                    }
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          </KeyboardAvoidingView>
        </Modal>


        {/* Related Products */}
        {relatedProducts.length > 0 && (
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Sản phẩm liên quan</Text>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.relatedContainer}
            >
              {relatedProducts.map((p) => (
                <ProductCard 
                  key={p.id_sanpham} 
                  product={p} 
                  style={styles.relatedCard}
                />
              ))}
            </ScrollView>
          </View>
        )}
      </ScrollView>

      {/* Sticky Bottom Bar */}
      <View style={styles.stickyBottomBar}>
        <View style={styles.bottomPriceContainer}>
          <Text style={styles.bottomPriceLabel}>Tạm tính</Text>
          <Text style={styles.bottomPrice}>
            {selectedVariant ? formatPrice(price) : 'Liên hệ'}
          </Text>
        </View>
        
        <View style={styles.purchaseActions}>
        <TouchableOpacity
          style={[styles.buyNowBtn, (!selectedVariant || parseInt(stock || 0) === 0) && styles.disabledBtn]}
          onPress={handleBuyNow}
          disabled={!selectedVariant || parseInt(stock || 0) === 0}
        >
          <Text style={styles.buyNowBtnText}>Mua ngay</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[
            styles.addToCartBtn,
            (!selectedVariant || parseInt(stock || 0) === 0) && styles.disabledBtn
          ]}
          onPress={handleAddToCart}
          disabled={!selectedVariant || parseInt(stock || 0) === 0}
        >
          <Text style={styles.addToCartBtnText}>
            {selectedVariant
              ? (parseInt(stock || 0) > 0 ? 'Thêm vào giỏ hàng' : 'Hết hàng')
              : 'Liên hệ'}
          </Text>
        </TouchableOpacity>
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  center: {
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
    marginRight: SPACING.md,
  },
  favHeaderBtn: {
    paddingHorizontal: SPACING.xs,
    justifyContent: 'center',
    alignItems: 'center',
  },
  favHeaderIcon: {
    fontSize: 20,
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
    flex: 1,
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  scrollContent: {
    paddingBottom: SPACING.xxxl, // Space for sticky bottom bar
  },
  sliderWrapper: {
    height: 260,
    backgroundColor: COLORS.surface,
    position: 'relative',
    width: '100%',
  },
  imageSlider: {
    flex: 1,
    width: '100%',
  },
  slide: {
    height: 260,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  productImage: {
    width: '100%',
    height: '100%',
  },
  dotsContainer: {
    position: 'absolute',
    bottom: 12,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  dot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: 'rgba(255, 255, 255, 0.4)',
    marginHorizontal: 3,
  },
  activeDot: {
    width: 12,
    backgroundColor: COLORS.primary,
  },
  infoSection: {
    padding: SPACING.lg,
    backgroundColor: COLORS.surface,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
    marginBottom: SPACING.lg,
  },
  brandName: {
    fontSize: 11,
    color: COLORS.primary,
    fontWeight: '800',
    letterSpacing: 1.5,
    textTransform: 'uppercase',
    marginBottom: SPACING.xs,
  },
  productName: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: SPACING.md,
    lineHeight: 28,
  },
  priceStockRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: SPACING.xs,
  },
  productPrice: {
    fontSize: 22,
    fontWeight: '800',
    color: COLORS.warning,
  },
  unavailablePrice: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.error,
  },
  stockBadge: {
    backgroundColor: '#1e1b4b',
    borderWidth: 1,
    borderColor: '#312e81',
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.sm,
    borderRadius: SPACING.xs,
  },
  stockText: {
    color: '#a5b4fc',
    fontSize: 12,
    fontWeight: '600',
  },
  sectionCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.lg,
    marginHorizontal: SPACING.lg,
    marginBottom: SPACING.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.md,
    borderLeftWidth: 3,
    borderLeftColor: COLORS.primary,
    paddingLeft: SPACING.sm,
  },
  variantGroup: {
    marginBottom: SPACING.md,
  },
  variantGroupName: {
    color: COLORS.textTertiary,
    fontSize: 12,
    fontWeight: '600',
    marginBottom: SPACING.sm,
  },
  optionsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  optionBtn: {
    backgroundColor: COLORS.background,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: SPACING.sm,
    paddingHorizontal: SPACING.md,
    marginRight: SPACING.sm,
    marginBottom: SPACING.sm,
  },
  activeOptionBtn: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  optionText: {
    color: COLORS.textSecondary,
    fontSize: 13,
    fontWeight: '600',
  },
  activeOptionText: {
    color: COLORS.white,
  },
  colorOption: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.background,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.md,
    marginRight: SPACING.sm,
    marginBottom: SPACING.sm,
  },
  activeColorOption: {
    borderColor: COLORS.primary,
    backgroundColor: 'rgba(99, 102, 241, 0.1)',
  },
  colorDot: {
    width: 14,
    height: 14,
    borderRadius: 7,
    marginRight: SPACING.sm,
    borderWidth: 1,
    borderColor: COLORS.white,
  },
  colorText: {
    color: COLORS.textSecondary,
    fontSize: 13,
    fontWeight: '600',
  },
  activeColorText: {
    color: '#a5b4fc',
  },
  specTable: {
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    overflow: 'hidden',
  },
  specRow: {
    flexDirection: 'row',
    paddingVertical: SPACING.md,
    paddingHorizontal: SPACING.lg,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
  },
  evenSpecRow: {
    backgroundColor: COLORS.background,
  },
  specLabel: {
    width: '40%',
    color: COLORS.textTertiary,
    fontSize: 13,
    fontWeight: '600',
  },
  specValue: {
    width: '60%',
    color: COLORS.textPrimary,
    fontSize: 13,
    fontWeight: '500',
  },
  descriptionText: {
    color: COLORS.textSecondary,
    fontSize: 14,
    lineHeight: 22,
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
    ...Platform.select({
      ios: {
        shadowColor: '#000',
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
  addToCartBtn: {
    flex: 1,
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.md,
    paddingHorizontal: SPACING.lg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  purchaseActions: {
    flexDirection: 'row',
    gap: SPACING.sm,
    flex: 1,
    marginLeft: SPACING.md,
  },
  buyNowBtn: {
    flex: 1,
    borderWidth: 1,
    borderColor: COLORS.primary,
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buyNowBtnText: {
    color: COLORS.primary,
    fontSize: 14,
    fontWeight: '700',
  },
  disabledBtn: {
    backgroundColor: COLORS.border,
    opacity: 0.6,
  },
  addToCartBtnText: {
    color: COLORS.white,
    fontSize: 14,
    fontWeight: '700',
  },
  emptyText: {
    fontSize: 13,
    color: COLORS.textTertiary,
    fontStyle: 'italic',
    marginTop: SPACING.xs,
  },
  reviewsList: {
    marginTop: SPACING.xs,
  },
  reviewItem: {
    borderBottomWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: SPACING.md,
  },
  reviewHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: SPACING.xs,
  },
  reviewUser: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  reviewDate: {
    fontSize: 11,
    color: COLORS.textTertiary,
  },
  reviewRating: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: SPACING.xs,
  },
  starsRow: {
    flexDirection: 'row',
  },
  starIconActive: {
    color: COLORS.warning,
    fontSize: 12,
    marginRight: 2,
  },
  starIconInactive: {
    color: COLORS.border,
    fontSize: 12,
    marginRight: 2,
  },
  reviewVariant: {
    fontSize: 11,
    color: COLORS.primary,
    fontWeight: '600',
  },
  reviewVariantBadge: {
    backgroundColor: 'rgba(99,102,241,0.1)', paddingHorizontal: SPACING.sm,
    paddingVertical: 2, borderRadius: RADIUS.sm, alignSelf: 'flex-start', marginBottom: SPACING.xs,
  },
  reviewComment: {
    fontSize: 13,
    color: COLORS.textSecondary,
    lineHeight: 18,
    marginTop: SPACING.xs,
  },
  relatedContainer: {
    paddingVertical: SPACING.xs,
  },
  relatedCard: {
    width: 150,
    marginRight: SPACING.md,
    marginBottom: 0,
  },

  // Enhanced reviews
  ratingSummary: {
    flexDirection: 'row', alignItems: 'center', gap: SPACING.lg,
    marginBottom: SPACING.lg, paddingBottom: SPACING.lg, borderBottomWidth: 1, borderColor: COLORS.border,
  },
  ratingBigScore: { alignItems: 'center', minWidth: 70 },
  ratingAvgNum: { fontSize: 40, fontWeight: '800', color: COLORS.warning },
  ratingStarsRow: { flexDirection: 'row', gap: 2, marginVertical: 4 },
  ratingTotal: { fontSize: 11, color: COLORS.textTertiary },
  ratingBars: { flex: 1, gap: 4 },
  ratingBarRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.sm },
  ratingBarLabel: { fontSize: 11, color: COLORS.textTertiary, width: 22 },
  ratingBarBg: { flex: 1, height: 6, backgroundColor: COLORS.border, borderRadius: 3, overflow: 'hidden' },
  ratingBarFill: { height: '100%', backgroundColor: '#f59e0b', borderRadius: 3 },
  ratingBarCount: { fontSize: 11, color: COLORS.textTertiary, width: 18, textAlign: 'right' },

  reviewsHeaderRow: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: SPACING.md,
  },
  writeReviewBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    backgroundColor: 'rgba(99,102,241,0.1)', paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm,
    borderRadius: RADIUS.md,
  },
  writeReviewText: { fontSize: 12, color: COLORS.primary, fontWeight: '600' },

  noReviewWrap: { alignItems: 'center', paddingVertical: SPACING.xl, gap: SPACING.sm },

  reviewAvatarRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.sm, flex: 1 },
  reviewAvatar: {
    width: 34, height: 34, borderRadius: 17, backgroundColor: COLORS.primary,
    alignItems: 'center', justifyContent: 'center',
  },
  reviewAvatarText: { color: '#fff', fontWeight: '700', fontSize: 14 },

  showMoreBtn: { alignItems: 'center', paddingVertical: SPACING.md },
  showMoreText: { color: COLORS.primary, fontWeight: '600', fontSize: 13 },

  // Review modal
  reviewModalOverlay: { flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.65)' },
  reviewModalSheet: {
    backgroundColor: COLORS.surface, borderTopLeftRadius: RADIUS.xl, borderTopRightRadius: RADIUS.xl,
    padding: SPACING.xl, paddingBottom: 32,
  },
  reviewModalTitle: { fontSize: 18, fontWeight: '800', color: COLORS.textPrimary, marginBottom: 4 },
  reviewModalSub: { fontSize: 13, color: COLORS.textTertiary, marginBottom: SPACING.lg },
  reviewLabel: { fontSize: 13, fontWeight: '600', color: COLORS.textSecondary, marginBottom: SPACING.sm, marginTop: SPACING.md },
  starPicker: { flexDirection: 'row', justifyContent: 'center', marginBottom: SPACING.sm },
  ratingLabel: { textAlign: 'center', fontSize: 13, color: COLORS.warning, fontWeight: '700', marginBottom: SPACING.md },
  reviewTextInput: {
    backgroundColor: COLORS.background, borderRadius: RADIUS.md, borderWidth: 1,
    borderColor: COLORS.border, padding: SPACING.md, color: COLORS.textPrimary,
    fontSize: 14, height: 100, textAlignVertical: 'top',
  },
  reviewModalActions: {
    flexDirection: 'row', gap: SPACING.md, marginTop: SPACING.xl,
  },
  reviewCancelBtn: {
    flex: 1, paddingVertical: SPACING.md, borderRadius: RADIUS.md,
    backgroundColor: COLORS.background, alignItems: 'center',
    borderWidth: 1, borderColor: COLORS.border,
  },
  reviewCancelText: { color: COLORS.textSecondary, fontWeight: '600' },
  reviewSubmitBtn: {
    flex: 2, paddingVertical: SPACING.md, borderRadius: RADIUS.md,
    backgroundColor: COLORS.primary, alignItems: 'center',
  },
  reviewSubmitText: { color: '#fff', fontWeight: '700', fontSize: 15 },
});
