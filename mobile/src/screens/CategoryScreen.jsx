import React, { useEffect, useState, useRef } from 'react';
import { StyleSheet, Text, View, FlatList, TextInput, TouchableOpacity, ActivityIndicator, RefreshControl, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import api from '../services/api';
import ProductCard from '../components/ProductCard';
import { CatalogGridSkeleton } from '../components/SkeletonLoader';
import logger from '../utils/logger';

export default function CategoryScreen({ navigation }) {
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [brands, setBrands] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  
  // Search and Filter States
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [selectedBrand, setSelectedBrand] = useState(null);
  const [selectedPriceRange, setSelectedPriceRange] = useState(null); // 'low', 'mid', 'high', null

  // Debounce timer ref
  const searchTimeoutRef = useRef(null);

  const fetchCatalogData = async (query = '') => {
    try {
      setLoading(true);
      const params = {};
      if (query.trim()) {
        params.q = query.trim();
      }
      
      const response = await api.get('/sanpham/init', { params });
      
      setProducts(response.data.products || []);
      setCategories(response.data.categories || []);
      setBrands(response.data.brands || []);
    } catch (err) {
      logger.log('Error fetching catalog data:', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchCatalogData();
  }, []);

  const onRefresh = () => {
    setRefreshing(true);
    setSearchQuery('');
    setSelectedCategory(null);
    setSelectedBrand(null);
    setSelectedPriceRange(null);
    fetchCatalogData();
  };

  const handleSearch = (text) => {
    setSearchQuery(text);
    
    // Clear existing timeout
    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
    }
    
    // Set new timeout for debounced search (500ms delay)
    searchTimeoutRef.current = setTimeout(() => {
      fetchCatalogData(text);
    }, 500);
  };

  // Cleanup timeout on unmount
  useEffect(() => {
    return () => {
      if (searchTimeoutRef.current) {
        clearTimeout(searchTimeoutRef.current);
      }
    };
  }, []);

  // Client-side filtering logic to combine Category, Brand, and Price
  const getFilteredProducts = () => {
    return products.filter(prod => {
      // Category filter
      if (selectedCategory && prod.id_danhmuc !== selectedCategory) {
        return false;
      }

      // Brand filter
      if (selectedBrand && prod.id_thuonghieu !== selectedBrand) {
        return false;
      }

      // Price filter (based on first variant price)
      const variant = prod.bien_thes && prod.bien_thes.length > 0 ? prod.bien_thes[0] : null;
      const price = variant ? parseFloat(variant.gia) : 0;

      if (selectedPriceRange === 'low') {
        return price < 15000000; // < 15M
      } else if (selectedPriceRange === 'mid') {
        return price >= 15000000 && price <= 25000000; // 15M - 25M
      } else if (selectedPriceRange === 'high') {
        return price > 25000000; // > 25M
      }

      return true;
    });
  };

  const filteredProducts = getFilteredProducts();

  return (
    <SafeAreaView style={styles.container}>
      {/* Search Bar */}
      <View style={styles.searchHeader}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backBtnText}>❮</Text>
        </TouchableOpacity>
        <View style={styles.searchBox}>
          <Text style={styles.searchIcon}>🔍</Text>
          <TextInput
            style={styles.searchInput}
            value={searchQuery}
            onChangeText={handleSearch}
            placeholder="Tìm kiếm máy tính, linh kiện..."
            placeholderTextColor="#64748b"
            autoCapitalize="none"
          />
          {searchQuery ? (
            <TouchableOpacity onPress={() => handleSearch('')} style={styles.clearBtn}>
              <Text style={styles.clearText}>✕</Text>
            </TouchableOpacity>
          ) : null}
        </View>
      </View>

      {/* Filter Section */}
      <View style={styles.filterWrapper}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.filterScroll}>
          {/* Category Filter */}
          <View style={styles.filterGroup}>
            <TouchableOpacity
              style={[styles.filterChip, !selectedCategory && styles.activeChip]}
              onPress={() => setSelectedCategory(null)}
            >
              <Text style={[styles.chipText, !selectedCategory && styles.activeChipText]}>Tất cả danh mục</Text>
            </TouchableOpacity>
            {categories.map(cat => (
              <TouchableOpacity
                key={cat.id_danhmuc}
                style={[styles.filterChip, selectedCategory === cat.id_danhmuc && styles.activeChip]}
                onPress={() => setSelectedCategory(cat.id_danhmuc)}
              >
                <Text style={[styles.chipText, selectedCategory === cat.id_danhmuc && styles.activeChipText]}>
                  {cat.ten_danhmuc}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </ScrollView>

        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.filterScroll}>
          {/* Brand Filter */}
          <View style={styles.filterGroup}>
            <TouchableOpacity
              style={[styles.filterChip, !selectedBrand && styles.activeChip]}
              onPress={() => setSelectedBrand(null)}
            >
              <Text style={[styles.chipText, !selectedBrand && styles.activeChipText]}>Tất cả hãng</Text>
            </TouchableOpacity>
            {brands.map(brand => (
              <TouchableOpacity
                key={brand.id_thuonghieu}
                style={[styles.filterChip, selectedBrand === brand.id_thuonghieu && styles.activeChip]}
                onPress={() => setSelectedBrand(brand.id_thuonghieu)}
              >
                <Text style={[styles.chipText, selectedBrand === brand.id_thuonghieu && styles.activeChipText]}>
                  {brand.ten_thuonghieu}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </ScrollView>

        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.filterScroll}>
          {/* Price Range Filter */}
          <View style={styles.filterGroup}>
            <TouchableOpacity
              style={[styles.filterChip, !selectedPriceRange && styles.activeChip]}
              onPress={() => setSelectedPriceRange(null)}
            >
              <Text style={[styles.chipText, !selectedPriceRange && styles.activeChipText]}>Tất cả giá</Text>
            </TouchableOpacity>
            
            <TouchableOpacity
              style={[styles.filterChip, selectedPriceRange === 'low' && styles.activeChip]}
              onPress={() => setSelectedPriceRange('low')}
            >
              <Text style={[styles.chipText, selectedPriceRange === 'low' && styles.activeChipText]}>{"< 15 triệu"}</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.filterChip, selectedPriceRange === 'mid' && styles.activeChip]}
              onPress={() => setSelectedPriceRange('mid')}
            >
              <Text style={[styles.chipText, selectedPriceRange === 'mid' && styles.activeChipText]}>15 - 25 triệu</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.filterChip, selectedPriceRange === 'high' && styles.activeChip]}
              onPress={() => setSelectedPriceRange('high')}
            >
              <Text style={[styles.chipText, selectedPriceRange === 'high' && styles.activeChipText]}>{"> 25 triệu"}</Text>
            </TouchableOpacity>
          </View>
        </ScrollView>
      </View>

      {/* Product List Grid */}
      {loading ? (
        <CatalogGridSkeleton />
      ) : (
        <FlatList
          data={filteredProducts}
          keyExtractor={(item) => item.id_sanpham.toString()}
          numColumns={2}
          columnWrapperStyle={styles.columnWrapper}
          contentContainerStyle={styles.listContainer}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#6366f1" />
          }
          renderItem={({ item }) => <ProductCard product={item} />}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Text style={styles.emptyIcon}>📦</Text>
              <Text style={styles.emptyText}>Không tìm thấy sản phẩm phù hợp</Text>
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
  searchHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    paddingHorizontal: SPACING.lg,
    paddingVertical: SPACING.md,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
  },
  backBtn: {
    marginRight: SPACING.md,
    padding: SPACING.xs,
  },
  backBtnText: {
    fontSize: 18,
    color: COLORS.textPrimary,
  },
  searchBox: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    paddingHorizontal: SPACING.md,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  searchIcon: {
    fontSize: 16,
    marginRight: SPACING.sm,
  },
  searchInput: {
    flex: 1,
    height: 44,
    color: COLORS.textPrimary,
    fontSize: 14,
  },
  clearBtn: {
    padding: SPACING.xs,
  },
  clearText: {
    color: COLORS.textTertiary,
    fontSize: 14,
  },
  filterWrapper: {
    paddingVertical: SPACING.sm,
    backgroundColor: COLORS.background,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
  },
  filterScroll: {
    paddingHorizontal: SPACING.lg,
    marginBottom: SPACING.xs,
  },
  filterGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingBottom: SPACING.xs,
  },
  filterChip: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.md,
    marginRight: SPACING.sm,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  activeChip: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  chipText: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '600',
  },
  activeChipText: {
    color: COLORS.white,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: COLORS.textSecondary,
    marginTop: SPACING.md,
  },
  listContainer: {
    padding: SPACING.md,
    paddingBottom: SPACING.xxxl,
  },
  columnWrapper: {
    justifyContent: 'space-between',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingTop: 100,
  },
  emptyIcon: {
    fontSize: 48,
    marginBottom: SPACING.lg,
  },
  emptyText: {
    color: COLORS.textTertiary,
    fontSize: 14,
  },
});
