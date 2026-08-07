import React, { useEffect, useState, useRef, useMemo, useCallback } from 'react';
import { StyleSheet, Text, View, ScrollView, TouchableOpacity, RefreshControl, Dimensions, FlatList, Image, Linking, Animated, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Image as ExpoImage } from 'expo-image';
import { Feather } from '@expo/vector-icons';
import OptimizedImage from '../components/OptimizedImage';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import api, { getImageUrl } from '../services/api';
import ProductCard from '../components/ProductCard';
import { HomeSkeleton } from '../components/SkeletonLoader';
import { fetchWithCache, clearCache } from '../utils/apiCache';
import { laptopSegments, setups, testimonials, fallbackBlogs, brandTabs, getCategoryDetails } from '../constants/homeData';
import logoImage from '../../assets/nextgen_logo_header.png';
import supportAvatar from '../../assets/support_avatar.png';
import logger from '../utils/logger';
import useCartStore from '../store/useCartStore';
import useRecentlyViewedStore from '../store/useRecentlyViewedStore';
import useAuthStore from '../store/useAuthStore';
const statsData = [
  { value: '15K+', label: 'Khách Hàng Hài Lòng', icon: '👥' },
  { value: '500+', label: 'Sản Phẩm Cao Cấp', icon: '💻' },
  { value: '24/7', label: 'Hỗ Trợ Chuyên Sâu', icon: '📞' },
  { value: '99%', label: 'Đánh Giá Tích Cực', icon: '⭐' }
];

const { width: SCREEN_WIDTH } = Dimensions.get('window');

export default function HomeScreen({ navigation }) {
  const cartItems = useCartStore((state) => state.items);
  const cartCount = useMemo(() => cartItems.reduce((sum, item) => sum + item.quantity, 0), [cartItems]);
  const recentlyViewed = useRecentlyViewedStore((state) => state.items);
  const token = useAuthStore((state) => state.token);

  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [banners, setBanners] = useState([]);
  const [blogs, setBlogs] = useState([]);
  const [affiliateVideos, setAffiliateVideos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [activeBannerIndex, setActiveBannerIndex] = useState(0);
  
  // Responsive banner width state
  const [containerWidth, setContainerWidth] = useState(SCREEN_WIDTH - 40);
  const [isChatMenuOpen, setIsChatMenuOpen] = useState(false);
  const [isMenuRendered, setIsMenuRendered] = useState(false);
  const menuAnim = useRef(new Animated.Value(0)).current;

  const [visibleSections, setVisibleSections] = useState({
    bestSellers: false,
    bentoGrid: false,
    news: false,
    testimonials: false,
  });

  // Progressive timer to load sections gradually if user stays on top
  useEffect(() => {
    const t1 = setTimeout(() => setVisibleSections(p => ({ ...p, bestSellers: true })), 250);
    const t2 = setTimeout(() => setVisibleSections(p => ({ ...p, bentoGrid: true })), 500);
    const t3 = setTimeout(() => setVisibleSections(p => ({ ...p, news: true })), 750);
    const t4 = setTimeout(() => setVisibleSections(p => ({ ...p, testimonials: true })), 1000);

    return () => {
      clearTimeout(t1);
      clearTimeout(t2);
      clearTimeout(t3);
      clearTimeout(t4);
    };
  }, []);

  const handleScroll = (event) => {
    const scrollY = event.nativeEvent.contentOffset.y;
    
    // Quick load trigger when user scrolls down
    setVisibleSections(prev => {
      let updated = { ...prev };
      let changed = false;

      if (!prev.bestSellers && scrollY > 80) {
        updated.bestSellers = true;
        changed = true;
      }
      if (!prev.bentoGrid && scrollY > 300) {
        updated.bentoGrid = true;
        changed = true;
      }
      if (!prev.news && scrollY > 650) {
        updated.news = true;
        changed = true;
      }
      if (!prev.testimonials && scrollY > 900) {
        updated.testimonials = true;
        changed = true;
      }

      return changed ? updated : prev;
    });
  };

  const toggleChatMenu = () => {
    if (isChatMenuOpen) {
      Animated.timing(menuAnim, {
        toValue: 0,
        duration: 200,
        useNativeDriver: true,
      }).start(() => {
        setIsChatMenuOpen(false);
        setIsMenuRendered(false);
      });
    } else {
      setIsChatMenuOpen(true);
      setIsMenuRendered(true);
      Animated.spring(menuAnim, {
        toValue: 1,
        tension: 55,
        friction: 8,
        useNativeDriver: true,
      }).start();
    }
  };

  // Tab state for Best Sellers filtering
  const [activeBrandTab, setActiveBrandTab] = useState('all');

  const bannerScrollRef = useRef(null);

  const fetchHomeData = async () => {
    try {
      setError(null);
      
      // Use cached API calls with 5-minute cache duration
      const [bannersRes, homeRes] = await Promise.all([
        fetchWithCache('/banners', () => api.get('/banners')),
        fetchWithCache('/mobile/home', () => api.get('/mobile/home'))
      ]);

      const bannersList = bannersRes.data || bannersRes || [];
      const productsList = homeRes.data?.products || homeRes.products || [];
      const categoriesList = homeRes.data?.categories || homeRes.categories || [];

      setBanners(bannersList);
      setProducts(productsList);
      setCategories(categoriesList);
      
      // Disable loader instantly for main content visibility
      setLoading(false);
      setRefreshing(false);

      // Prefetch images for instant loading
      try {
        const prefetchUrls = [];
        bannersList.forEach(b => {
          const url = getImageUrl(b.hinhanh);
          if (url) prefetchUrls.push(url);
        });
        productsList.slice(0, 6).forEach(p => {
          const url = getImageUrl(p.hinhanh);
          if (url) prefetchUrls.push(url);
        });

        if (prefetchUrls.length > 0) {
          ExpoImage.prefetch(prefetchUrls);
          console.log(`[Image Prefetch] Prefetched ${prefetchUrls.length} images for banners and top products.`);
        }
      } catch (prefetchErr) {
        console.log('Image prefetch failed:', prefetchErr);
      }

      // Resilient news fetching with caching
      try {
        const newsRes = await fetchWithCache('/news', () => api.get('/news'));
        const newsData = newsRes.data?.data || newsRes.data || [];
        setBlogs((newsData || []).slice(0, 3).map(post => ({
          id: post.id || Math.random(),
          category: post.category || post.danhmuc || 'Tin tức',
          title: post.title || post.tieude || 'Bài viết công nghệ',
          date: post.publishedAt || post.dang_luc || post.created_at 
            ? new Date(post.publishedAt || post.dang_luc || post.created_at).toLocaleDateString('vi-VN') 
            : 'Mới cập nhật',
          image: post.image || (post.hinhanh ? getImageUrl(post.hinhanh) : null) || post.thumbnail || 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=300&q=80'
        })));
      } catch (newsErr) {
        console.log('News API failed:', newsErr);
        // Use imported fallback blogs
        setBlogs(fallbackBlogs);
      }
      try {
        const videoRes = await api.get('/affiliate-videos/public', { params: { limit: 6 } });
        setAffiliateVideos(Array.isArray(videoRes.data) ? videoRes.data : []);
      } catch (_) {
        setAffiliateVideos([]);
      }
    } catch (err) {
      console.log('Error fetching home data:', err);
      setError('Không thể tải dữ liệu. Vui lòng kiểm tra lại kết nối.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchHomeData();
  }, []);

  useEffect(() => {
    if (!token) return;
    api.get('/sanpham-daxem')
      .then((response) => {
        if (Array.isArray(response.data)) {
          useRecentlyViewedStore.getState().replaceProducts(response.data);
        }
      })
      .catch((error) => logger.log('Failed to load recently viewed products:', error));
  }, [token]);

  // Optimized banner auto-scroll (only when user isn't interacting)
  useEffect(() => {
    if (banners.length <= 1) return;
    
    let autoScrollInterval = null;
    let restartTimeout = null;
    
    const startAutoScroll = () => {
      if (autoScrollInterval) return;
      autoScrollInterval = setInterval(() => {
        const nextIndex = (activeBannerIndex + 1) % banners.length;
        setActiveBannerIndex(nextIndex);
        bannerScrollRef.current?.scrollTo({
          x: nextIndex * containerWidth,
          animated: true
        });
      }, 5000);
    };

    const stopAutoScroll = () => {
      if (autoScrollInterval) {
        clearInterval(autoScrollInterval);
        autoScrollInterval = null;
      }
    };

    // Start auto-scroll
    startAutoScroll();

    // Stop on user interaction
    const handleMomentumScrollEnd = () => {
      stopAutoScroll();
      // Restart after 3 seconds of inactivity
      restartTimeout = setTimeout(() => {
        const scrollOffset = bannerScrollRef.current?.scrollOffset?.x || 0;
        const currentSlideIndex = Math.round(scrollOffset / (SCREEN_WIDTH - 40));
        if (currentSlideIndex === activeBannerIndex) {
          startAutoScroll();
        }
      }, 3000);
    };

    return () => {
      stopAutoScroll();
      if (restartTimeout) {
        clearTimeout(restartTimeout);
      }
    };
  }, [activeBannerIndex, banners, containerWidth]);

  // Memoize filtered products to avoid recalculating on every render
  const filteredProducts = useMemo(() => {
    if (activeBrandTab === 'all') return products;
    return products.filter((p) => {
      const name = (p.tenSP || '').toLowerCase();
      const brand = p.thuong_hieu?.ten_thuonghieu ? p.thuong_hieu.ten_thuonghieu.toLowerCase() : '';
      
      if (activeBrandTab === 'macbook') {
        return name.includes('macbook') || brand.includes('apple') || brand.includes('mac');
      }
      if (activeBrandTab === 'thinkpad') {
        return name.includes('thinkpad') || name.includes('loq') || brand.includes('lenovo');
      }
      if (activeBrandTab === 'asus') {
        return brand.includes('asus') || name.includes('tuf') || name.includes('zenbook') || name.includes('vivobook');
      }
      if (activeBrandTab === 'hp') {
        return brand.includes('hp') || name.includes('pavilion');
      }
      return true;
    });
  }, [products, activeBrandTab]);

  // Memoize onRefresh callback
  const onRefresh = useCallback(() => {
    setRefreshing(true);
    fetchHomeData();
  }, []);

  // Memoize banner scroll handler
  const handleBannerScroll = useCallback((event) => {
    const slideSize = event.nativeEvent.layoutMeasurement.width;
    const index = event.nativeEvent.contentOffset.x / slideSize;
    const roundIndex = Math.round(index);
    if (roundIndex !== activeBannerIndex) {
      setActiveBannerIndex(roundIndex);
    }
  }, [activeBannerIndex]);

  if (loading) {
    return <HomeSkeleton />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView
        contentContainerStyle={styles.scrollContainer}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#6366f1" />
        }
        scrollEventThrottle={16}
        onScroll={handleScroll}
      >
        {/* Header Bar */}
        <View style={styles.header}>
          {/* Left: Logo (Old position) */}
          <OptimizedImage 
            source={logoImage} 
            style={styles.headerLogo} 
            contentFit="contain"
            contentPosition="left"
          />

          {/* Right Actions: Cart and notifications */}
          <View style={styles.headerActions}>
            <TouchableOpacity 
              style={styles.headerBtn} 
              onPress={() => navigation.navigate('Giỏ hàng')}
              accessibilityRole="button"
              accessibilityLabel="Mở giỏ hàng"
            >
              <Feather name="shopping-cart" size={24} color={COLORS.textPrimary} />
              {cartCount > 0 && (
                <View style={styles.badgeContainer}>
                  <Text style={styles.badgeText}>{cartCount}</Text>
                </View>
              )}
            </TouchableOpacity>

            <TouchableOpacity 
              style={[styles.headerBtn, { marginLeft: SPACING.md }]} 
              onPress={() => navigation.navigate('Notifications')}
              accessibilityRole="button"
              accessibilityLabel="Mở thông báo"
            >
              <Feather name="bell" size={24} color={COLORS.textPrimary} />
            </TouchableOpacity>
          </View>
        </View>

        {error && (
          <View style={styles.errorBanner}>
            <Text style={styles.errorText}>{error}</Text>
            <TouchableOpacity style={styles.retryBtn} onPress={fetchHomeData}>
              <Text style={styles.retryBtnText}>Thử lại</Text>
            </TouchableOpacity>
          </View>
        )}

        {/* Carousel Banners */}
        {banners.length > 0 && (
          <View 
            style={styles.bannerWrapper}
            onLayout={(e) => {
              const { width } = e.nativeEvent.layout;
              if (width > 0) {
                setContainerWidth(width);
              }
            }}
          >
            <ScrollView
              ref={bannerScrollRef}
              horizontal
              pagingEnabled
              snapToInterval={containerWidth}
              decelerationRate="fast"
              showsHorizontalScrollIndicator={false}
              onMomentumScrollEnd={handleBannerScroll}
              style={styles.bannerCarousel}
            >
              {banners.map((banner) => (
                <View key={banner.id} style={[styles.bannerSlide, { width: containerWidth }]}>
                  <OptimizedImage
                    source={{ uri: getImageUrl(banner.hinhanh) }}
                    style={styles.bannerImage}
                    lazyLoad={false}
                    priority="high"
                  />
                  <View style={styles.bannerOverlay}>
                    <Text style={styles.bannerBadge}>{banner.chudenho || 'HOT PROMOTION'}</Text>
                    <Text style={styles.bannerTitle} numberOfLines={1}>{banner.tieude}</Text>
                    <Text style={styles.bannerSub} numberOfLines={2}>{banner.phude}</Text>
                    <TouchableOpacity 
                      style={styles.bannerBtn}
                      onPress={() => {
                        if (banner.id_sanpham) {
                          navigation.navigate('ProductDetail', { productId: banner.id_sanpham });
                        } else {
                          navigation.navigate('Danh mục');
                        }
                      }}
                    >
                      <Text style={styles.bannerBtnText}>{banner.nhanchinh || 'Xem ngay'}</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              ))}
            </ScrollView>
            
            {/* Pagination Indicators */}
            <View style={styles.paginationDots}>
              {banners.map((_, index) => (
                <View
                  key={index}
                  style={[
                    styles.dot,
                    activeBannerIndex === index ? styles.activeDot : null
                  ]}
                />
              ))}
            </View>
          </View>
        )}

        {/* Trust Stats Bar */}
        <View style={styles.statsContainer}>
          <View style={styles.trustRow}>
            {statsData.map((stat, idx) => (
              <View key={idx} style={styles.trustCard}>
                <Text style={styles.statIcon}>{stat.icon}</Text>
                <Text style={styles.statNumber}>{stat.value}</Text>
                <Text style={styles.statLabel}>{stat.label}</Text>
              </View>
            ))}
          </View>
        </View>

        {/* Specialized Laptop Segments */}
        <View style={styles.sectionContainer}>
          <Text style={styles.sectionTitle}>Phân Khúc Laptop Chuyên Biệt</Text>
          <Text style={styles.sectionSubtitle}>Tuyển chọn máy tính tối ưu hóa cấu hình cho từng nhu cầu</Text>
          
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.segmentsScroll}>
            {laptopSegments.map((segment) => (
              <TouchableOpacity
                key={segment.id}
                style={styles.segmentCard}
                onPress={() => navigation.navigate('Danh mục')}
              >
                <OptimizedImage source={{ uri: segment.image }} style={styles.segmentImg} />
                <View style={styles.segmentOverlay}>
                  <Text style={styles.segmentTitle}>{segment.title}</Text>
                  <Text style={styles.segmentDesc}>{segment.desc}</Text>
                </View>
              </TouchableOpacity>
            ))}
          </ScrollView>
        </View>

        {/* Brand tabs: Sản phẩm bán chạy nhất */}
        {visibleSections.bestSellers ? (
          <View style={styles.sectionContainer}>
            <View style={styles.sectionHeader}>
              <View>
                <Text style={styles.sectionTitle}>Sản Phẩm Bán Chạy Nhất</Text>
                <Text style={styles.sectionSubtitle}>Các cấu hình laptop bền bỉ được đánh giá cao nhất</Text>
              </View>
              <TouchableOpacity onPress={() => navigation.navigate('Danh mục')}>
                <Text style={styles.seeAllText}>Xem tất cả ❯</Text>
              </TouchableOpacity>
            </View>

            {/* Styled Filter Brand Tabs */}
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.brandTabsContainer}>
              {brandTabs.map(tab => (
                <TouchableOpacity
                  key={tab.id}
                  style={[styles.brandTab, activeBrandTab === tab.id && styles.activeBrandTab]}
                  onPress={() => setActiveBrandTab(tab.id)}
                >
                  <Text style={[styles.brandTabText, activeBrandTab === tab.id && styles.activeBrandTabText]}>
                    {tab.label}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>

            {/* List of best seller products */}
            {filteredProducts.length > 0 ? (
              <FlatList
                data={filteredProducts}
                horizontal
                showsHorizontalScrollIndicator={false}
                keyExtractor={(item) => item.id_sanpham.toString()}
                renderItem={({ item }) => (
                  <View style={styles.horizontalCardWrapper}>
                    <ProductCard product={item} style={{ width: '100%', marginBottom: 0 }} />
                  </View>
                )}
                initialNumToRender={4}
                maxToRenderPerBatch={6}
                windowSize={5}
                removeClippedSubviews={true}
              />
            ) : (
              <View style={styles.noProductsBox}>
                <Text style={styles.noProductsText}>Không có sản phẩm thuộc hãng này</Text>
              </View>
            )}
          </View>
        ) : (
          <View style={styles.lazyPlaceholder}>
            <ActivityIndicator size="small" color="#6366f1" />
          </View>
        )}

        {/* Section: Premium Ecosystem Bento Grid */}
        {visibleSections.bentoGrid ? (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Kiến Tạo Góc Setup Trong Mơ</Text>
            <Text style={styles.sectionSubtitle}>Kiến tạo góc làm việc & chiến game đỉnh cao cùng Nexzen</Text>

            <View style={styles.bentoGrid}>
              {/* XL Block */}
              <TouchableOpacity 
                style={styles.bentoBlockXl} 
                activeOpacity={0.9}
                onPress={() => navigation.navigate('Danh mục')}
              >
                <Image 
                  source={{ uri: 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?w=600' }} 
                  style={styles.bentoBg} 
                />
                <View style={styles.bentoOverlay} />
                <View style={styles.bentoContent}>
                  <Text style={styles.bentoTag}>TRUNG TÂM SETUP</Text>
                  <Text style={styles.bentoTitle}>Không Gian Tối Giản</Text>
                  <Text style={styles.bentoDesc}>Đồng bộ cổng kết nối và tối ưu không gian đa nhiệm đỉnh cao.</Text>
                  <Text style={styles.bentoCta}>Khám phá ngay ➔</Text>
                </View>
              </TouchableOpacity>

              {/* Two Columns Grid */}
              <View style={styles.bentoRow}>
                {/* Block 1 */}
                <TouchableOpacity 
                  style={styles.bentoBlockMd} 
                  activeOpacity={0.9}
                  onPress={() => navigation.navigate('Danh mục')}
                >
                  <Image 
                    source={{ uri: 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=400' }} 
                    style={styles.bentoBg} 
                  />
                  <View style={styles.bentoOverlay} />
                  <View style={styles.bentoContent}>
                    <Text style={styles.bentoTag}>ÂM THANH</Text>
                    <Text style={styles.bentoTitle}>Tai Nghe Studio</Text>
                    <Text style={styles.bentoCta}>Xem mẫu ➔</Text>
                  </View>
                </TouchableOpacity>

                {/* Block 2 */}
                <TouchableOpacity 
                  style={styles.bentoBlockMd} 
                  activeOpacity={0.9}
                  onPress={() => navigation.navigate('Danh mục')}
                >
                  <Image 
                    source={{ uri: 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=400' }} 
                    style={styles.bentoBg} 
                  />
                  <View style={styles.bentoOverlay} />
                  <View style={styles.bentoContent}>
                    <Text style={styles.bentoTag}>BÀN PHÍM CƠ</Text>
                    <Text style={styles.bentoTitle}>Bàn Phím Custom</Text>
                    <Text style={styles.bentoCta}>Sở hữu ngay ➔</Text>
                  </View>
                </TouchableOpacity>
              </View>

              {/* Wide Block */}
              <TouchableOpacity 
                style={styles.bentoBlockWide} 
                activeOpacity={0.9}
                onPress={() => navigation.navigate('Danh mục')}
              >
                <Image 
                  source={{ uri: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600' }} 
                  style={styles.bentoBg} 
                />
                <View style={styles.bentoOverlay} />
                <View style={styles.bentoContent}>
                  <Text style={styles.bentoTag}>MÀN HÌNH</Text>
                  <Text style={styles.bentoTitle}>Màn Hình Đỉnh OLED</Text>
                  <Text style={styles.bentoDesc}>Công nghệ hình ảnh tối tân, màu sắc chân thực sắc nét.</Text>
                  <Text style={styles.bentoCta}>Xem chi tiết ➔</Text>
                </View>
              </TouchableOpacity>
            </View>
          </View>
        ) : (
          <View style={styles.lazyPlaceholder}>
            <ActivityIndicator size="small" color="#6366f1" />
          </View>
        )}

        {/* Section: Brand Values (Giá trị thương hiệu) */}
        <View style={styles.sectionContainer}>
          <Text style={styles.sectionTitle}>Giá Trị Xứng Tầm Thương Hiệu</Text>
          <View style={styles.valuesGrid}>
            <View style={styles.valueCard}>
              <Text style={styles.valueIcon}>🚀</Text>
              <Text style={styles.valueTitle}>Giao hỏa tốc 2h</Text>
              <Text style={styles.valueDesc}>Nội thành hỏa tốc nhận hàng nhanh chóng.</Text>
            </View>
            <View style={styles.valueCard}>
              <Text style={styles.valueIcon}>🛡️</Text>
              <Text style={styles.valueTitle}>Bảo hành 24 tháng</Text>
              <Text style={styles.valueDesc}>Cam kết 1 đổi 1 trong vòng 30 ngày đầu tiên.</Text>
            </View>
            <View style={styles.valueCard}>
              <Text style={styles.valueIcon}>🔄</Text>
              <Text style={styles.valueTitle}>Thu cũ đổi mới</Text>
              <Text style={styles.valueDesc}>Hỗ trợ thu cũ trợ giá nâng cấp máy lên tới 2tr.</Text>
            </View>
            <View style={styles.valueCard}>
              <Text style={styles.valueIcon}>💳</Text>
              <Text style={styles.valueTitle}>Trả góp 0%</Text>
              <Text style={styles.valueDesc}>Thủ tục nhanh gọn duyệt hồ sơ trong 5 phút.</Text>
            </View>
          </View>
        </View>

        {affiliateVideos.length > 0 && (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Video từ cộng đồng đối tác</Text>
            <Text style={styles.sectionSubtitle}>Trải nghiệm sản phẩm do đối tác VinaTech chia sẻ</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false}>
              {affiliateVideos.map((video) => (
                <TouchableOpacity
                  key={video.id}
                  style={styles.affiliateVideoCard}
                  onPress={() => {
                    api.post(`/affiliate-videos/${video.id}/track`, { type: 'click' }).catch(() => {});
                    const target = video.video_url || getImageUrl(video.video_path);
                    if (target) Linking.openURL(target);
                  }}
                >
                  <Feather name="play-circle" size={34} color="#fff" />
                  <Text style={styles.affiliateVideoTitle} numberOfLines={2}>{video.tieu_de}</Text>
                  <Text style={styles.affiliateVideoAuthor}>{video.affiliate_user?.ten || 'Đối tác VinaTech'}</Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        )}

        {/* Section: Tech Insights Magazine (Technology News) */}
        {visibleSections.news ? (
          <View style={styles.sectionContainer}>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: SPACING.md }}>
              <View style={{ flex: 1, marginRight: SPACING.sm }}>
                <Text style={styles.sectionTitle}>Tech Insights Magazine</Text>
                <Text style={styles.sectionSubtitle}>Cập nhật xu hướng và mẹo công nghệ máy tính mới nhất</Text>
              </View>
              <TouchableOpacity onPress={() => navigation.navigate('NewsList')} style={{ paddingVertical: 6, paddingHorizontal: 12, borderRadius: RADIUS.md, backgroundColor: 'rgba(99, 102, 241, 0.08)' }}>
                <Text style={{ color: COLORS.primary, fontSize: 12, fontWeight: '700' }}>Xem tất cả</Text>
              </TouchableOpacity>
            </View>

            <View style={styles.blogsList}>
              {blogs.map((blog, index) => (
                <TouchableOpacity 
                  key={blog.id || `blog-${index}`} 
                  style={styles.blogRow}
                  onPress={() => navigation.navigate('NewsDetail', { newsId: blog.id })}
                  activeOpacity={0.7}
                >
                  <OptimizedImage source={{ uri: blog.image }} style={styles.blogImg} />
                  <View style={styles.blogDetails}>
                    <Text style={styles.blogCategory}>{blog.category}</Text>
                    <Text style={styles.blogTitle} numberOfLines={2}>{blog.title}</Text>
                    <Text style={styles.blogDate}>{blog.date}</Text>
                  </View>
                </TouchableOpacity>
              ))}
            </View>
          </View>
        ) : (
          <View style={styles.lazyPlaceholder}>
            <ActivityIndicator size="small" color="#6366f1" />
          </View>
        )}

        {recentlyViewed && recentlyViewed.length > 0 && (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Sản phẩm đã xem gần đây</Text>
            <Text style={styles.sectionSubtitle}>Các sản phẩm công nghệ bạn vừa quan tâm tìm hiểu</Text>
            <FlatList
              data={recentlyViewed}
              keyExtractor={(item) => (item.id_sanpham || item.id).toString()}
              horizontal
              showsHorizontalScrollIndicator={false}
              renderItem={({ item }) => (
                <View style={styles.horizontalCardWrapper}>
                  <ProductCard 
                    product={item} 
                    onPress={() => navigation.navigate('ProductDetail', { productId: item.id_sanpham })} 
                    style={{ width: '100%', marginBottom: 0 }}
                  />
                </View>
              )}
              contentContainerStyle={{ paddingVertical: 10 }}
            />
          </View>
        )}

        {/* Section: Testimonials (Đồng hành cùng công việc) */}
        {visibleSections.testimonials ? (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Đồng Hành Cùng Mọi Luồng Công Việc</Text>
            <Text style={styles.sectionSubtitle}>Khách hàng chia sẻ trải nghiệm thực tế cùng Nexzen</Text>

            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.testimonialsScroll}>
              {testimonials.map((test) => (
                <View key={test.id} style={styles.testimonialCard}>
                  <Text style={styles.testStars}>{test.rating}</Text>
                  <Text style={styles.testComment}>"{test.comment}"</Text>
                  <View style={styles.testUserRow}>
                    <View style={styles.testAvatar}>
                      <Text style={styles.testAvatarText}>{test.name.substring(4, 5) || 'K'}</Text>
                    </View>
                    <View>
                      <Text style={styles.testUserName}>{test.name}</Text>
                      <Text style={styles.testUserRole}>{test.role}</Text>
                    </View>
                  </View>
                </View>
              ))}
            </ScrollView>
          </View>
        ) : (
          <View style={styles.lazyPlaceholder}>
            <ActivityIndicator size="small" color="#6366f1" />
          </View>
        )}

        {/* Compact Footer Section */}
        <View style={styles.footerContainer}>
          <Text style={styles.footerCopyright}>
            © 2026 Nexzen. All Rights Reserved.
          </Text>
        </View>
      </ScrollView>
      {/* Floating Speed-Dial Contact Menu */}
      <View style={styles.floatingMenuContainer}>
        {/* Expanded Options with Slide & Fade Animation */}
        {isMenuRendered && (
          <Animated.View style={[
            styles.menuOptions,
            {
              opacity: menuAnim,
              transform: [
                {
                  translateY: menuAnim.interpolate({
                    inputRange: [0, 1],
                    outputRange: [40, 0]
                  })
                },
                {
                  scale: menuAnim.interpolate({
                    inputRange: [0, 1],
                    outputRange: [0.3, 1]
                  })
                }
              ]
            }
          ]}>
            {/* Option 1: AI Chatbot */}
            <TouchableOpacity 
              style={[styles.menuOptionBtn, styles.aiBtn]}
              activeOpacity={0.85}
              onPress={() => {
                toggleChatMenu();
                navigation.navigate('Chatbot');
              }}
            >
              <Text style={styles.optionIcon}>💬</Text>
            </TouchableOpacity>

            {/* Option 2: Zalo Chat */}
            <TouchableOpacity 
              style={[styles.menuOptionBtn, styles.zaloBtn]}
              activeOpacity={0.85}
              onPress={() => {
                toggleChatMenu();
                Linking.openURL('https://zalo.me/0397972161').catch(err => 
                  logger.log('Error opening Zalo link:', err)
                );
              }}
            >
              <Text style={styles.zaloText}>Zalo</Text>
            </TouchableOpacity>
          </Animated.View>
        )}

        {/* Master Button */}
        <TouchableOpacity 
          style={[styles.chatBubble, isChatMenuOpen && styles.chatBubbleOpen]}
          activeOpacity={0.85}
          onPress={toggleChatMenu}
        >
          {isChatMenuOpen ? (
            <Text style={styles.closeIcon}>✕</Text>
          ) : (
            <>
              <Image 
                source={supportAvatar} 
                style={styles.chatAvatar} 
              />
              <View style={styles.chatOnlineDot} />
            </>
          )}
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  floatingMenuContainer: {
    position: 'absolute',
    right: 20,
    bottom: 20,
    alignItems: 'center',
    zIndex: 999,
  },
  menuOptions: {
    alignItems: 'center',
    marginBottom: 10,
  },
  menuOptionBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 10,
    shadowColor: '#000',
    shadowOpacity: 0.25,
    shadowOffset: { width: 0, height: 3 },
    shadowRadius: 4,
    elevation: 6,
  },
  aiBtn: {
    backgroundColor: '#2563eb', // Royal blue
  },
  zaloBtn: {
    backgroundColor: '#0068ff', // Zalo blue
  },
  optionIcon: {
    fontSize: 18,
    color: COLORS.white,
  },
  zaloText: {
    color: COLORS.white,
    fontSize: 11,
    fontWeight: '800',
  },
  chatBubble: {
    width: 58,
    height: 58,
    borderRadius: 29,
    backgroundColor: '#2563eb', // Master blue from website
    padding: 3,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#2563eb',
    shadowOpacity: 0.4,
    shadowOffset: { width: 0, height: 6 },
    shadowRadius: 8,
    elevation: 8,
  },
  chatBubbleOpen: {
    backgroundColor: '#64748b', // Slate gray when open
    shadowColor: '#64748b',
  },
  closeIcon: {
    fontSize: 20,
    color: COLORS.white,
    fontWeight: '700',
  },
  chatAvatar: {
    width: 52,
    height: 52,
    borderRadius: 26,
  },
  chatOnlineDot: {
    position: 'absolute',
    top: 2,
    right: 2,
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: COLORS.success,
    borderWidth: 2,
    borderColor: COLORS.surface,
  },
  headerLogo: {
    width: 165,
    height: 44,
    backgroundColor: 'transparent',
  },
  scrollContainer: {
    padding: SPACING.lg,
    paddingBottom: SPACING.xxxl,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: SPACING.lg,
  },
  headerActions: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  headerBtn: {
    justifyContent: 'center',
    alignItems: 'center',
    position: 'relative',
    padding: SPACING.xs,
  },
  badgeContainer: {
    position: 'absolute',
    top: -4,
    right: -6,
    backgroundColor: COLORS.primary,
    borderRadius: 9,
    width: 18,
    height: 18,
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 10,
  },
  badgeText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: 'bold',
  },
  errorBanner: {
    backgroundColor: COLORS.error,
    borderRadius: RADIUS.lg,
    padding: SPACING.lg,
    marginBottom: SPACING.lg,
    alignItems: 'center',
  },
  errorText: {
    color: '#fca5a5',
    textAlign: 'center',
    marginBottom: SPACING.sm,
  },
  retryBtn: {
    backgroundColor: COLORS.error,
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.lg,
    borderRadius: SPACING.xs,
  },
  retryBtnText: {
    color: '#ffffff',
    fontWeight: '600',
  },
  bannerWrapper: {
    height: 180,
    width: '100%',
    borderRadius: RADIUS.xl,
    overflow: 'hidden',
    marginBottom: SPACING.xxl,
    position: 'relative',
  },
  bannerCarousel: {
    flex: 1,
  },
  bannerSlide: {
    width: SCREEN_WIDTH - 40,
    height: 180,
    position: 'relative',
  },
  bannerImage: {
    width: '100%',
    height: '100%',
  },
  bannerOverlay: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    top: 0,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    padding: 20,
    justifyContent: 'center',
  },
  bannerBadge: {
    color: COLORS.primary,
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 1.5,
    marginBottom: SPACING.xs,
  },
  bannerTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: '#ffffff',
    marginBottom: 4,
  },
  bannerSub: {
    fontSize: 12,
    color: COLORS.textSecondary,
    marginBottom: SPACING.lg,
    lineHeight: 16,
  },
  bannerBtn: {
    alignSelf: 'flex-start',
    backgroundColor: COLORS.primary,
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.lg,
    borderRadius: RADIUS.md,
  },
  bannerBtnText: {
    color: '#ffffff',
    fontWeight: '700',
    fontSize: 11,
  },
  paginationDots: {
    position: 'absolute',
    bottom: 12,
    left: 0,
    right: 0,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  dot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: 'rgba(255, 255, 255, 0.4)',
    marginHorizontal: 4,
  },
  activeDot: {
    width: 14,
    backgroundColor: COLORS.white,
  },
  statsContainer: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.xl,
    paddingVertical: SPACING.md,
    paddingHorizontal: SPACING.sm,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: SPACING.xxl,
  },
  lazyPlaceholder: {
    height: 120,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginVertical: SPACING.md,
  },
  trustRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    width: '100%',
  },
  trustCard: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statIcon: {
    fontSize: 18,
    marginBottom: 2,
    textAlign: 'center',
  },
  statNumber: {
    fontSize: 14,
    fontWeight: '800',
    color: COLORS.primary,
    textAlign: 'center',
  },
  statLabel: {
    fontSize: 9,
    color: COLORS.textSecondary,
    textAlign: 'center',
    marginTop: 2,
  },
  sectionContainer: {
    marginBottom: SPACING.xxl,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: SPACING.md,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: SPACING.xs,
  },
  sectionSubtitle: {
    fontSize: 11,
    color: COLORS.textTertiary,
    marginBottom: SPACING.md,
    lineHeight: 14,
  },
  seeAllText: {
    fontSize: 12,
    color: COLORS.primary,
    fontWeight: '600',
  },
  segmentsScroll: {
    flexDirection: 'row',
  },
  segmentCard: {
    width: 160,
    height: 110,
    borderRadius: RADIUS.lg,
    overflow: 'hidden',
    marginRight: SPACING.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    position: 'relative',
  },
  segmentImg: {
    width: '100%',
    height: '100%',
  },
  segmentOverlay: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    top: 0,
    backgroundColor: 'rgba(15, 23, 42, 0.55)',
    padding: SPACING.md,
    justifyContent: 'flex-end',
  },
  segmentTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: COLORS.white,
    marginBottom: SPACING.xs,
  },
  segmentDesc: {
    fontSize: 9,
    color: COLORS.textTertiary,
  },
  brandTabsContainer: {
    flexDirection: 'row',
    marginBottom: SPACING.lg,
  },
  brandTab: {
    backgroundColor: COLORS.surface,
    borderRadius: 18,
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.lg,
    marginRight: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  activeBrandTab: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  brandTabText: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '600',
  },
  activeBrandTabText: {
    color: COLORS.white,
  },
  horizontalCardWrapper: {
    width: SCREEN_WIDTH * 0.44,
    maxWidth: 220,
    marginRight: SPACING.md,
  },
  noProductsBox: {
    height: 100,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  noProductsText: {
    color: COLORS.textTertiary,
    fontSize: 12,
  },
  bentoGrid: {
    marginTop: SPACING.md,
  },
  bentoBlockXl: {
    height: 160,
    borderRadius: RADIUS.lg,
    overflow: 'hidden',
    position: 'relative',
    marginBottom: SPACING.md,
  },
  bentoBlockWide: {
    height: 130,
    borderRadius: RADIUS.lg,
    overflow: 'hidden',
    position: 'relative',
    marginTop: SPACING.md,
  },
  bentoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  bentoBlockMd: {
    width: '48%',
    height: 120,
    borderRadius: RADIUS.lg,
    overflow: 'hidden',
    position: 'relative',
  },
  bentoBg: {
    width: '100%',
    height: '100%',
    position: 'absolute',
  },
  bentoOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
  },
  bentoContent: {
    flex: 1,
    padding: SPACING.md,
    justifyContent: 'flex-end',
  },
  bentoTag: {
    fontSize: 9,
    fontWeight: '800',
    color: COLORS.primaryLight,
    letterSpacing: 0.5,
    marginBottom: 2,
  },
  bentoTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  bentoDesc: {
    fontSize: 10,
    color: COLORS.textSecondary,
    lineHeight: 14,
    marginBottom: 4,
  },
  bentoCta: {
    fontSize: 10,
    fontWeight: '600',
    color: COLORS.primary,
  },
  valuesGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  valueCard: {
    width: '48%',
    backgroundColor: '#1e293b',
    borderRadius: 12,
    padding: 14,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#334155',
  },
  valueIcon: {
    fontSize: 20,
    marginBottom: 8,
  },
  valueTitle: {
    fontSize: 12,
    fontWeight: '700',
    color: '#f8fafc',
    marginBottom: 4,
  },
  valueDesc: {
    fontSize: 10,
    color: '#94a3b8',
    lineHeight: 13,
  },
  blogsList: {
    backgroundColor: '#1e293b',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: '#334155',
  },
  blogRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  blogImg: {
    width: 70,
    height: 56,
    borderRadius: 6,
    marginRight: 14,
  },
  blogDetails: {
    flex: 1,
  },
  blogCategory: {
    fontSize: 9,
    fontWeight: '800',
    color: '#6366f1',
    textTransform: 'uppercase',
    marginBottom: 2,
  },
  blogTitle: {
    fontSize: 12,
    fontWeight: '700',
    color: '#f8fafc',
    marginBottom: 4,
  },
  blogDate: {
    fontSize: 9,
    color: '#64748b',
  },
  testimonialsScroll: {
    flexDirection: 'row',
  },
  affiliateVideoCard: {
    width: 210,
    minHeight: 135,
    marginRight: SPACING.md,
    padding: SPACING.lg,
    borderRadius: RADIUS.lg,
    backgroundColor: '#312e81',
    justifyContent: 'flex-end',
  },
  affiliateVideoTitle: { color: '#fff', fontWeight: '800', fontSize: 14, marginTop: SPACING.md },
  affiliateVideoAuthor: { color: '#c7d2fe', fontSize: 11, marginTop: 5 },
  testimonialCard: {
    width: 260,
    backgroundColor: '#1e293b',
    borderRadius: 12,
    padding: 16,
    marginRight: 12,
    borderWidth: 1,
    borderColor: '#334155',
  },
  testStars: {
    fontSize: 12,
    marginBottom: 8,
  },
  testComment: {
    fontSize: 12,
    color: '#cbd5e1',
    fontStyle: 'italic',
    lineHeight: 16,
    marginBottom: 14,
  },
  testUserRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  testAvatar: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#312e81',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 10,
  },
  testAvatarText: {
    color: '#a5b4fc',
    fontSize: 12,
    fontWeight: '700',
  },
  testUserName: {
    fontSize: 12,
    fontWeight: '700',
    color: '#f8fafc',
  },
  testUserRole: {
    fontSize: 10,
    color: '#94a3b8',
  },
  footerContainer: {
    marginTop: 40,
    marginBottom: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  footerLogo: {
    fontSize: 16,
    fontWeight: '900',
    color: '#6366f1',
    letterSpacing: 1.5,
    marginBottom: 6,
  },
  footerSubtitle: {
    fontSize: 11,
    color: '#94a3b8',
    textAlign: 'center',
    marginBottom: 14,
  },
  footerContact: {
    fontSize: 11,
    color: '#cbd5e1',
    marginBottom: 6,
  },
  footerPayments: {
    flexDirection: 'row',
    marginTop: 14,
    marginBottom: 16,
  },
  paymentBadge: {
    backgroundColor: '#0f172a',
    color: '#94a3b8',
    fontSize: 10,
    fontWeight: '700',
    paddingVertical: 4,
    paddingHorizontal: 10,
    borderRadius: 6,
    marginHorizontal: 4,
    borderWidth: 1,
    borderColor: '#334155',
  },
  footerDivider: {
    height: 1,
    backgroundColor: '#334155',
    width: '100%',
    marginVertical: 14,
  },
  footerCopyright: {
    fontSize: 10,
    color: '#64748b',
    textAlign: 'center',
  },
});
