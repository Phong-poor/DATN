import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, View, ScrollView, TouchableOpacity, Image, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import api, { getImageUrl } from '../services/api';

const stripHtml = (html) => {
  if (!html) return '';
  return html
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/p>/gi, '\n\n')
    .replace(/<\/div>/gi, '\n')
    .replace(/<[^>]+>/g, '')
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .trim();
};

export default function NewsDetailScreen({ route, navigation }) {
  const { newsId } = route.params;
  const [news, setNews] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchNewsDetail = async () => {
      try {
        const res = await api.get(`/news/${newsId}`);
        setNews(res.data);
      } catch (err) {
        console.log('Failed to fetch news detail:', err);
      } finally {
        setLoading(false);
      }
    };
    fetchNewsDetail();
  }, [newsId]);

  const formatDate = (dateStr) => {
    if (!dateStr) return 'Mới cập nhật';
    return new Date(dateStr).toLocaleDateString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Đang tải bài viết...</Text>
      </SafeAreaView>
    );
  }

  if (!news) {
    return (
      <SafeAreaView style={styles.loadingContainer}>
        <Text style={styles.errorText}>Không tìm thấy bài viết hoặc bài viết đã bị gỡ.</Text>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backBtnText}>Quay lại</Text>
        </TouchableOpacity>
      </SafeAreaView>
    );
  }

  const imageUrl = getImageUrl(news.hinhanh);
  const cleanContent = stripHtml(news.noidung);
  const cleanSummary = stripHtml(news.tomtat);

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* Header Back Bar */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>❮</Text>
          <Text style={styles.backText}>Tin tức</Text>
        </TouchableOpacity>
        <Text style={styles.topTitle} numberOfLines={1}>{news.tieude}</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Main Title */}
        <Text style={styles.title}>{news.tieude}</Text>

        {/* Metadata */}
        <View style={styles.metaRow}>
          <View style={styles.badge}>
            <Text style={styles.badgeText}>{news.danhmuc || 'Công nghệ'}</Text>
          </View>
          <Text style={styles.metaText}>📅 {formatDate(news.dang_luc || news.created_at)}</Text>
          <Text style={styles.metaText}>👁️ {news.luotxem || 0} lượt xem</Text>
        </View>

        {/* Author */}
        {news.tacgia ? (
          <Text style={styles.authorText}>Tác giả: <Text style={{fontWeight: '700'}}>{news.tacgia}</Text></Text>
        ) : null}

        {/* Big Banner Image */}
        {imageUrl ? (
          <Image source={{ uri: imageUrl }} style={styles.image} resizeMode="cover" />
        ) : null}

        {/* Summary Block */}
        {cleanSummary ? (
          <View style={styles.summaryContainer}>
            <Text style={styles.summaryText}>{cleanSummary}</Text>
          </View>
        ) : null}

        {/* Content Body */}
        <Text style={styles.content}>{cleanContent}</Text>
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
    backgroundColor: COLORS.surface,
  },
  backBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: SPACING.md,
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
    padding: SPACING.lg,
    paddingBottom: SPACING.xxxl,
  },
  title: {
    fontSize: 22,
    fontWeight: '800',
    color: COLORS.textPrimary,
    lineHeight: 30,
    marginBottom: SPACING.md,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: SPACING.sm,
  },
  badge: {
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.sm,
    paddingVertical: 3,
    paddingHorizontal: SPACING.sm,
    marginRight: SPACING.md,
  },
  badgeText: {
    color: COLORS.white,
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  metaText: {
    color: COLORS.textTertiary,
    fontSize: 12,
    marginRight: SPACING.md,
  },
  authorText: {
    color: COLORS.textSecondary,
    fontSize: 13,
    marginBottom: SPACING.lg,
  },
  image: {
    width: '100%',
    height: 200,
    borderRadius: RADIUS.lg,
    marginBottom: SPACING.xl,
    backgroundColor: COLORS.surface,
  },
  summaryContainer: {
    backgroundColor: COLORS.surface,
    borderLeftWidth: 4,
    borderLeftColor: COLORS.primary,
    padding: SPACING.md,
    borderRadius: RADIUS.md,
    marginBottom: SPACING.xl,
  },
  summaryText: {
    color: COLORS.textSecondary,
    fontSize: 14,
    fontStyle: 'italic',
    lineHeight: 22,
  },
  content: {
    color: COLORS.textPrimary,
    fontSize: 15,
    lineHeight: 25,
  },
  loadingContainer: {
    flex: 1,
    backgroundColor: COLORS.background,
    justifyContent: 'center',
    alignItems: 'center',
    padding: SPACING.xl,
  },
  loadingText: {
    color: COLORS.textSecondary,
    marginTop: SPACING.md,
    fontSize: 14,
  },
  errorText: {
    color: COLORS.error,
    fontSize: 15,
    textAlign: 'center',
    marginBottom: SPACING.xl,
  },
  backBtnText: {
    color: COLORS.white,
    backgroundColor: COLORS.primary,
    paddingVertical: SPACING.sm,
    paddingHorizontal: SPACING.xl,
    borderRadius: RADIUS.md,
    fontWeight: '600',
  },
});
