import React, { useEffect, useRef } from 'react';
import { StyleSheet, View, Animated, Dimensions, Platform } from 'react-native';
import { COLORS, RADIUS, SPACING } from '../utils/theme';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

// Reusable Pulsing Skeleton Base component
export function SkeletonItem({ width, height, borderRadius = 8, style }) {
  const pulseAnim = useRef(new Animated.Value(0.3)).current;

  useEffect(() => {
    const pulse = Animated.loop(
      Animated.sequence([
        Animated.timing(pulseAnim, {
          toValue: 0.8,
          duration: 800,
          useNativeDriver: Platform.OS !== 'web',
        }),
        Animated.timing(pulseAnim, {
          toValue: 0.3,
          duration: 800,
          useNativeDriver: Platform.OS !== 'web',
        }),
      ])
    );
    pulse.start();
    return () => pulse.stop();
  }, [pulseAnim]);

  return (
    <Animated.View
      style={[
        {
          width,
          height,
          borderRadius,
          backgroundColor: COLORS.border,
          opacity: pulseAnim,
        },
        style,
      ]}
    />
  );
}

// Skeleton for the Home Screen
export function HomeSkeleton() {
  return (
    <View style={styles.container}>
      {/* Header Skeleton */}
      <View style={styles.headerRow}>
        <View>
          <SkeletonItem width={120} height={12} borderRadius={4} style={{ marginBottom: 8 }} />
          <SkeletonItem width={160} height={20} borderRadius={6} />
        </View>
        <SkeletonItem width={44} height={44} borderRadius={22} />
      </View>

      {/* Banner Carousel Skeleton */}
      <SkeletonItem width="100%" height={180} borderRadius={16} style={{ marginBottom: 28 }} />

      {/* Categories Horizontal Scroll Skeleton */}
      <SkeletonItem width={140} height={16} borderRadius={4} style={{ marginBottom: 14 }} />
      <View style={styles.horizontalRow}>
        <SkeletonItem width={100} height={36} borderRadius={18} style={{ marginRight: 10 }} />
        <SkeletonItem width={120} height={36} borderRadius={18} style={{ marginRight: 10 }} />
        <SkeletonItem width={90} height={36} borderRadius={18} style={{ marginRight: 10 }} />
        <SkeletonItem width={110} height={36} borderRadius={18} style={{ marginRight: 10 }} />
      </View>

      {/* Newest Laptops Section Skeleton */}
      <View style={[styles.headerRow, { marginTop: 24 }]}>
        <SkeletonItem width={150} height={16} borderRadius={4} />
        <SkeletonItem width={60} height={12} borderRadius={4} />
      </View>
      
      <View style={styles.horizontalRow}>
        <CardSkeleton />
        <CardSkeleton />
      </View>
    </View>
  );
}

// Skeleton for a Single Product Card
export function CardSkeleton() {
  return (
    <View style={styles.card}>
      <SkeletonItem width="100%" height={120} borderRadius={0} style={{ marginBottom: 10 }} />
      <View style={{ padding: 12 }}>
        <SkeletonItem width={50} height={10} borderRadius={2} style={{ marginBottom: 6 }} />
        <SkeletonItem width="90%" height={14} borderRadius={4} style={{ marginBottom: 6 }} />
        <SkeletonItem width="70%" height={14} borderRadius={4} style={{ marginBottom: 12 }} />
        
        <View style={styles.cardFooter}>
          <SkeletonItem width={80} height={16} borderRadius={4} />
          <SkeletonItem width={32} height={24} borderRadius={6} />
        </View>
      </View>
    </View>
  );
}

// Skeleton for Category Screen / Catalog list
export function CatalogSkeleton() {
  return (
    <View style={styles.container}>
      {/* Search box skeleton */}
      <SkeletonItem width="100%" height={44} borderRadius={12} style={{ marginBottom: 16 }} />
      
      {/* Filter rows skeletons */}
      <View style={[styles.horizontalRow, { marginBottom: 8 }]}>
        <SkeletonItem width={90} height={28} borderRadius={6} style={{ marginRight: 8 }} />
        <SkeletonItem width={110} height={28} borderRadius={6} style={{ marginRight: 8 }} />
        <SkeletonItem width={80} height={28} borderRadius={6} style={{ marginRight: 8 }} />
      </View>

      <View style={[styles.horizontalRow, { marginBottom: 16 }]}>
        <SkeletonItem width={70} height={28} borderRadius={6} style={{ marginRight: 8 }} />
        <SkeletonItem width={90} height={28} borderRadius={6} style={{ marginRight: 8 }} />
        <SkeletonItem width={100} height={28} borderRadius={6} style={{ marginRight: 8 }} />
      </View>

      {/* Grid skeleton */}
      <View style={styles.grid}>
        <CardSkeleton />
        <CardSkeleton />
        <CardSkeleton />
        <CardSkeleton />
      </View>
    </View>
  );
}

// Grid-only skeleton for catalog lists
export function CatalogGridSkeleton() {
  return (
    <View style={{ flex: 1, padding: 14 }}>
      <View style={styles.grid}>
        <CardSkeleton />
        <CardSkeleton />
        <CardSkeleton />
        <CardSkeleton />
      </View>
    </View>
  );
}

// Skeleton for Product Detail Screen
export function DetailSkeleton() {
  return (
    <View style={styles.container}>
      {/* Top back bar skeleton */}
      <View style={[styles.headerRow, { marginBottom: 14, paddingHorizontal: 0 }]}>
        <SkeletonItem width={90} height={16} borderRadius={4} />
        <SkeletonItem width={150} height={16} borderRadius={4} />
      </View>

      {/* Big Image Slider placeholder */}
      <SkeletonItem width="100%" height={260} borderRadius={0} style={{ marginBottom: 16 }} />

      {/* Basic Info placeholders */}
      <View style={styles.detailInfoBox}>
        <SkeletonItem width={80} height={10} borderRadius={2} style={{ marginBottom: 8 }} />
        <SkeletonItem width="90%" height={22} borderRadius={6} style={{ marginBottom: 8 }} />
        <SkeletonItem width="50%" height={22} borderRadius={6} style={{ marginBottom: 16 }} />
        
        <View style={styles.headerRow}>
          <SkeletonItem width={140} height={24} borderRadius={4} />
          <SkeletonItem width={100} height={24} borderRadius={6} />
        </View>
      </View>

      {/* Variant Selector placeholder */}
      <View style={styles.detailInfoBox}>
        <SkeletonItem width={120} height={14} borderRadius={4} style={{ marginBottom: 12 }} />
        <View style={styles.horizontalRow}>
          <SkeletonItem width={70} height={32} borderRadius={6} style={{ marginRight: 10 }} />
          <SkeletonItem width={70} height={32} borderRadius={6} style={{ marginRight: 10 }} />
          <SkeletonItem width={70} height={32} borderRadius={6} style={{ marginRight: 10 }} />
        </View>
      </View>
    </View>
  );
}

// Skeleton for Order History list
export function OrderHistorySkeleton() {
  return (
    <View style={styles.container}>
      {/* Top back bar skeleton */}
      <View style={[styles.headerRow, { marginBottom: 14, paddingHorizontal: 0 }]}>
        <SkeletonItem width={90} height={16} borderRadius={4} />
        <SkeletonItem width={150} height={16} borderRadius={4} />
      </View>

      {/* Order Item Card 1 */}
      <View style={styles.detailInfoBox}>
        <View style={[styles.headerRow, { marginBottom: 12 }]}>
          <View>
            <SkeletonItem width={100} height={14} borderRadius={4} style={{ marginBottom: 6 }} />
            <SkeletonItem width={120} height={10} borderRadius={2} />
          </View>
          <SkeletonItem width={80} height={20} borderRadius={6} />
        </View>
        <View style={styles.divider} />
        <View style={styles.horizontalRow}>
          <SkeletonItem width={50} height={50} borderRadius={8} style={{ marginRight: 12 }} />
          <View style={{ flex: 1, justifyContent: 'center' }}>
            <SkeletonItem width="80%" height={12} borderRadius={3} style={{ marginBottom: 6 }} />
            <SkeletonItem width="40%" height={10} borderRadius={2} />
          </View>
        </View>
      </View>

      {/* Order Item Card 2 */}
      <View style={styles.detailInfoBox}>
        <View style={[styles.headerRow, { marginBottom: 12 }]}>
          <View>
            <SkeletonItem width={100} height={14} borderRadius={4} style={{ marginBottom: 6 }} />
            <SkeletonItem width={120} height={10} borderRadius={2} />
          </View>
          <SkeletonItem width={80} height={20} borderRadius={6} />
        </View>
        <View style={styles.divider} />
        <View style={styles.horizontalRow}>
          <SkeletonItem width={50} height={50} borderRadius={8} style={{ marginRight: 12 }} />
          <View style={{ flex: 1, justifyContent: 'center' }}>
            <SkeletonItem width="80%" height={12} borderRadius={3} style={{ marginBottom: 6 }} />
            <SkeletonItem width="40%" height={10} borderRadius={2} />
          </View>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
    padding: SPACING.lg,
  },
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: SPACING.lg,
  },
  horizontalRow: {
    flexDirection: 'row',
    marginBottom: SPACING.lg,
  },
  card: {
    width: (SCREEN_WIDTH - 54) / 2,
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginRight: SPACING.md,
    marginBottom: SPACING.lg,
    overflow: 'hidden',
  },
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  detailInfoBox: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.lg,
    marginBottom: SPACING.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  divider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginVertical: SPACING.md,
  },
});
