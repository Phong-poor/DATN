import React, { useEffect, useState, useCallback, useRef } from 'react';
import {
  StyleSheet, Text, View, FlatList, TextInput, TouchableOpacity,
  ActivityIndicator, Image, RefreshControl, Animated,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS, RADIUS, SPACING, TYPOGRAPHY } from '../utils/theme';
import api, { getImageUrl } from '../services/api';

const CATEGORIES = ['Tất cả', 'Laptop', 'Gaming', 'Công nghệ', 'Tin tức', 'Khuyến mãi'];

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
};

function NewsCard({ item, onPress }) {
  const scaleAnim = useRef(new Animated.Value(1)).current;

  const onPressIn = () => Animated.spring(scaleAnim, { toValue: 0.97, useNativeDriver: true }).start();
  const onPressOut = () => Animated.spring(scaleAnim, { toValue: 1, useNativeDriver: true }).start();

  const imageUrl = item.hinhanh
    ? (item.hinhanh.startsWith('http') ? item.hinhanh : getImageUrl(item.hinhanh))
    : null;

  return (
    <Animated.View style={{ transform: [{ scale: scaleAnim }] }}>
      <TouchableOpacity
        style={styles.card}
        onPress={onPress}
        onPressIn={onPressIn}
        onPressOut={onPressOut}
        activeOpacity={1}
      >
        {imageUrl ? (
          <Image source={{ uri: imageUrl }} style={styles.cardImage} resizeMode="cover" />
        ) : (
          <View style={[styles.cardImage, styles.cardImagePlaceholder]}>
            <Ionicons name="newspaper-outline" size={36} color={COLORS.textTertiary} />
          </View>
        )}
        <View style={styles.cardContent}>
          <View style={styles.cardMeta}>
            <View style={styles.categoryBadge}>
              <Text style={styles.categoryBadgeText}>{item.danhmuc || 'Tin tức'}</Text>
            </View>
            <Text style={styles.cardDate}>{formatDate(item.dang_luc || item.created_at)}</Text>
          </View>
          <Text style={styles.cardTitle} numberOfLines={2}>{item.tieude}</Text>
          {item.tomtat ? (
            <Text style={styles.cardSummary} numberOfLines={2}>{item.tomtat}</Text>
          ) : null}
          <View style={styles.cardFooter}>
            {item.tacgia ? (
              <View style={styles.authorRow}>
                <Ionicons name="person-circle-outline" size={14} color={COLORS.textTertiary} />
                <Text style={styles.authorText}>{item.tacgia}</Text>
              </View>
            ) : null}
            {item.luotxem != null ? (
              <View style={styles.viewRow}>
                <Ionicons name="eye-outline" size={13} color={COLORS.textTertiary} />
                <Text style={styles.viewText}>{item.luotxem}</Text>
              </View>
            ) : null}
          </View>
        </View>
      </TouchableOpacity>
    </Animated.View>
  );
}

export default function NewsListScreen() {
  const navigation = useNavigation();
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [loadError, setLoadError] = useState('');
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [activeCategory, setActiveCategory] = useState('Tất cả');
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [subscriberEmail, setSubscriberEmail] = useState('');
  const [subscribeMessage, setSubscribeMessage] = useState('');
  const searchTimer = useRef(null);

  const fetchNews = useCallback(async (pageNum = 1, reset = false) => {
    try {
      if (pageNum === 1) setLoadError('');
      const params = { per_page: 10, scope: 'public', page: pageNum };
      if (activeCategory !== 'Tất cả') params.danhmuc = activeCategory;
      if (search.trim()) params.q = search.trim();

      const res = await api.get('/news', { params });
      const data = res.data?.data || res.data || [];
      const lp = res.data?.last_page || 1;
      setLastPage(lp);
      setPage(pageNum);

      if (reset || pageNum === 1) {
        setArticles(Array.isArray(data) ? data : []);
      } else {
        setArticles(prev => [...prev, ...(Array.isArray(data) ? data : [])]);
      }
    } catch (err) {
      if (pageNum === 1) {
        setLoadError(err.response?.data?.message || 'Không thể tải bài viết. Vui lòng kiểm tra kết nối và thử lại.');
        if (reset) setArticles([]);
      }
    }
  }, [activeCategory, search]);

  const retryLoad = () => {
    setLoading(true);
    fetchNews(1, true).finally(() => setLoading(false));
  };

  useEffect(() => {
    setLoading(true);
    fetchNews(1, true).finally(() => setLoading(false));
  }, [fetchNews]);

  const handleRefresh = async () => {
    setRefreshing(true);
    await fetchNews(1, true);
    setRefreshing(false);
  };

  const handleLoadMore = async () => {
    if (loadingMore || page >= lastPage) return;
    setLoadingMore(true);
    await fetchNews(page + 1, false);
    setLoadingMore(false);
  };

  const handleSearchInput = (text) => {
    setSearchInput(text);
    clearTimeout(searchTimer.current);
    searchTimer.current = setTimeout(() => setSearch(text), 500);
  };

  const handleCategoryPress = (cat) => {
    setActiveCategory(cat);
  };

  const handleSubscribe = async () => {
    if (!/^\S+@\S+\.\S+$/.test(subscriberEmail.trim())) {
      setSubscribeMessage('Vui lòng nhập email hợp lệ.');
      return;
    }
    try {
      const response = await api.post('/news-subscribe', { email: subscriberEmail.trim() });
      setSubscribeMessage(response.data?.message || 'Đăng ký nhận tin thành công.');
      setSubscriberEmail('');
    } catch (err) {
      setSubscribeMessage(err.response?.data?.message || 'Không thể đăng ký nhận tin.');
    }
  };

  const renderFooter = () => {
    if (!loadingMore) return null;
    return (
      <View style={styles.footerLoader}>
        <ActivityIndicator color={COLORS.primary} />
      </View>
    );
  };

  const renderEmpty = () => {
    if (loading) return null;
    return (
      <View style={styles.emptyContainer}>
        <Ionicons name="newspaper-outline" size={64} color={COLORS.textTertiary} />
        <Text style={styles.emptyTitle}>Không có bài viết</Text>
        <Text style={styles.emptySubtitle}>Thử tìm kiếm với từ khóa khác</Text>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={22} color={COLORS.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Tin tức & Blog</Text>
        <View style={{ width: 36 }} />
      </View>

      {/* Search */}
      <View style={styles.searchContainer}>
        <Ionicons name="search-outline" size={18} color={COLORS.textTertiary} style={styles.searchIcon} />
        <TextInput
          style={styles.searchInput}
          value={searchInput}
          onChangeText={handleSearchInput}
          placeholder="Tìm kiếm bài viết..."
          placeholderTextColor={COLORS.textTertiary}
          returnKeyType="search"
        />
        {searchInput.length > 0 && (
          <TouchableOpacity onPress={() => { setSearchInput(''); setSearch(''); }}>
            <Ionicons name="close-circle" size={18} color={COLORS.textTertiary} />
          </TouchableOpacity>
        )}
      </View>

      {/* Category filter */}
      <FlatList
        data={CATEGORIES}
        horizontal
        style={styles.categoryScroller}
        showsHorizontalScrollIndicator={false}
        keyExtractor={(item) => item}
        contentContainerStyle={styles.categoryList}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={[styles.categoryBtn, activeCategory === item && styles.activeCategoryBtn]}
            onPress={() => handleCategoryPress(item)}
          >
            <Text style={[styles.categoryBtnText, activeCategory === item && styles.activeCategoryBtnText]}>
              {item}
            </Text>
          </TouchableOpacity>
        )}
      />

      {/* News list */}
      {loading ? (
        <View style={styles.centerLoader}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Đang tải tin tức...</Text>
        </View>
      ) : loadError && articles.length === 0 ? (
        <View style={styles.centerLoader}>
          <Ionicons name="cloud-offline-outline" size={48} color={COLORS.textTertiary} />
          <Text style={styles.errorText}>{loadError}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={retryLoad}>
            <Text style={styles.retryButtonText}>Thử lại</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          data={articles}
          keyExtractor={(item, idx) => String(item.id || idx)}
          renderItem={({ item }) => (
            <NewsCard
              item={item}
              onPress={() => navigation.navigate('NewsDetail', { newsId: item.id })}
            />
          )}
          contentContainerStyle={styles.listContent}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor={COLORS.primary} />}
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.3}
          ListFooterComponent={renderFooter}
          ListEmptyComponent={renderEmpty}
          ListHeaderComponent={(
            <View style={styles.subscribeCard}>
              <Text style={styles.subscribeTitle}>Nhận tin công nghệ mới</Text>
              <View style={styles.subscribeRow}>
                <TextInput
                  style={styles.subscribeInput}
                  value={subscriberEmail}
                  onChangeText={setSubscriberEmail}
                  placeholder="Email của bạn"
                  placeholderTextColor={COLORS.textTertiary}
                  keyboardType="email-address"
                  autoCapitalize="none"
                />
                <TouchableOpacity style={styles.subscribeButton} onPress={handleSubscribe}>
                  <Text style={styles.subscribeButtonText}>Đăng ký</Text>
                </TouchableOpacity>
              </View>
              {subscribeMessage ? <Text style={styles.subscribeMessage}>{subscribeMessage}</Text> : null}
            </View>
          )}
        />
      )}
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
  headerTitle: { ...TYPOGRAPHY.headlineSmall, color: COLORS.textPrimary },

  searchContainer: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg, marginHorizontal: SPACING.lg, marginTop: SPACING.md,
    paddingHorizontal: SPACING.md, borderWidth: 1, borderColor: COLORS.border,
  },
  searchIcon: { marginRight: SPACING.sm },
  searchInput: { flex: 1, paddingVertical: SPACING.md, color: COLORS.textPrimary, fontSize: 14 },

  categoryScroller: { flexGrow: 0, flexShrink: 0, height: 64 },
  categoryList: { paddingHorizontal: SPACING.lg, paddingVertical: SPACING.md, gap: SPACING.sm, alignItems: 'center' },
  categoryBtn: {
    paddingHorizontal: SPACING.lg, paddingVertical: SPACING.sm,
    borderRadius: RADIUS.full, backgroundColor: COLORS.surface,
    borderWidth: 1, borderColor: COLORS.border,
    alignSelf: 'center',
  },
  activeCategoryBtn: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  categoryBtnText: { fontSize: 13, fontWeight: '500', color: COLORS.textSecondary },
  activeCategoryBtnText: { color: '#fff', fontWeight: '600' },

  listContent: { paddingHorizontal: SPACING.lg, paddingBottom: SPACING.xxxl },
  subscribeCard: { backgroundColor: COLORS.surface, borderWidth: 1, borderColor: COLORS.border, borderRadius: RADIUS.lg, padding: SPACING.lg, marginBottom: SPACING.lg },
  subscribeTitle: { color: COLORS.textPrimary, fontWeight: '800', fontSize: 15, marginBottom: SPACING.md },
  subscribeRow: { flexDirection: 'row', gap: SPACING.sm },
  subscribeInput: { flex: 1, borderWidth: 1, borderColor: COLORS.border, borderRadius: RADIUS.md, color: COLORS.textPrimary, paddingHorizontal: SPACING.md },
  subscribeButton: { backgroundColor: COLORS.primary, borderRadius: RADIUS.md, justifyContent: 'center', paddingHorizontal: SPACING.md },
  subscribeButtonText: { color: '#fff', fontWeight: '700' },
  subscribeMessage: { color: COLORS.textSecondary, fontSize: 12, marginTop: SPACING.sm },

  card: {
    backgroundColor: COLORS.surface, borderRadius: RADIUS.lg,
    marginBottom: SPACING.lg, overflow: 'hidden',
    borderWidth: 1, borderColor: COLORS.border,
  },
  cardImage: { width: '100%', height: 180 },
  cardImagePlaceholder: {
    backgroundColor: COLORS.surfaceLight, alignItems: 'center', justifyContent: 'center',
  },
  cardContent: { padding: SPACING.lg },
  cardMeta: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: SPACING.sm },
  categoryBadge: {
    backgroundColor: 'rgba(99,102,241,0.15)', paddingHorizontal: SPACING.sm, paddingVertical: 3,
    borderRadius: RADIUS.sm,
  },
  categoryBadgeText: { fontSize: 11, color: COLORS.primary, fontWeight: '600' },
  cardDate: { fontSize: 11, color: COLORS.textTertiary },
  cardTitle: { ...TYPOGRAPHY.titleLarge, color: COLORS.textPrimary, marginBottom: SPACING.sm, lineHeight: 22 },
  cardSummary: { fontSize: 13, color: COLORS.textSecondary, lineHeight: 20, marginBottom: SPACING.sm },
  cardFooter: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: SPACING.xs },
  authorRow: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  authorText: { fontSize: 12, color: COLORS.textTertiary },
  viewRow: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  viewText: { fontSize: 12, color: COLORS.textTertiary },

  centerLoader: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: SPACING.md },
  loadingText: { color: COLORS.textTertiary, fontSize: 14 },
  errorText: { color: COLORS.textSecondary, fontSize: 14, lineHeight: 20, textAlign: 'center', paddingHorizontal: SPACING.xl },
  retryButton: { backgroundColor: COLORS.primary, borderRadius: RADIUS.md, paddingHorizontal: SPACING.xl, paddingVertical: SPACING.sm },
  retryButtonText: { color: '#fff', fontWeight: '700' },
  footerLoader: { paddingVertical: SPACING.xl, alignItems: 'center' },

  emptyContainer: { paddingTop: 80, alignItems: 'center', gap: SPACING.md },
  emptyTitle: { ...TYPOGRAPHY.titleLarge, color: COLORS.textSecondary },
  emptySubtitle: { fontSize: 13, color: COLORS.textTertiary },
});
