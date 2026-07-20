import React, { useEffect, useState, useCallback, useRef } from 'react';
import {
  StyleSheet, Text, View, FlatList, TouchableOpacity, ActivityIndicator,
  RefreshControl, Animated, Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { Ionicons, FontAwesome } from '@expo/vector-icons';
import { COLORS, RADIUS, SPACING, TYPOGRAPHY } from '../utils/theme';
import api from '../services/api';
import useAuthStore from '../store/useAuthStore';
import { showAlert } from '../utils/alert';

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
};

const formatDiscount = (promotion) => {
  if (!promotion) return '';
  if (promotion.loai === 'percent') return `Giảm ${promotion.giatri}%`;
  if (promotion.loai === 'fixed') return `Giảm ${Number(promotion.giatri).toLocaleString('vi-VN')}₫`;
  if (promotion.loai === 'maxprice') return `Giá tối đa ${Number(promotion.giatri).toLocaleString('vi-VN')}₫`;
  return `Giảm giá`;
};

const getConditionText = (promo) => {
  if (!promo.dieu_kien || promo.dieu_kien <= 0) return 'Không giới hạn đơn tối thiểu';
  return `Đơn tối thiểu ${Number(promo.dieu_kien).toLocaleString('vi-VN')}₫`;
};

const getCategoryColor = (cat) => {
  switch (cat) {
    case 'birthday': return '#f59e0b';
    case 'freeship': return '#10b981';
    default: return '#6366f1';
  }
};

const getCategoryLabel = (cat) => {
  switch (cat) {
    case 'birthday': return '🎂 Sinh nhật';
    case 'freeship': return '🚚 Freeship';
    default: return '🎁 Sản phẩm';
  }
};

function VoucherCard({ promo, isMine, onClaim, claiming }) {
  const scaleAnim = useRef(new Animated.Value(1)).current;
  const color = getCategoryColor(promo.danhmuc);

  const expiringSoon = promo.ngayketthuc
    ? (new Date(promo.ngayketthuc) - new Date()) / (1000 * 60 * 60 * 24) <= 3
    : false;

  return (
    <Animated.View style={[styles.voucherCard, { transform: [{ scale: scaleAnim }] }]}>
      {/* Left accent */}
      <View style={[styles.voucherAccent, { backgroundColor: color }]}>
        <Ionicons name="pricetag" size={22} color="#fff" />
      </View>

      {/* Dashes separator */}
      <View style={styles.dashedSeparator} />

      {/* Content */}
      <View style={styles.voucherContent}>
        <View style={styles.voucherTopRow}>
          <View style={[styles.catBadge, { backgroundColor: color + '22', borderColor: color + '44' }]}>
            <Text style={[styles.catBadgeText, { color }]}>{getCategoryLabel(promo.danhmuc)}</Text>
          </View>
          {expiringSoon && (
            <View style={styles.expireBadge}>
              <Text style={styles.expireBadgeText}>Sắp hết hạn!</Text>
            </View>
          )}
        </View>

        <Text style={styles.voucherName} numberOfLines={1}>{promo.ten}</Text>
        <Text style={styles.voucherDiscount}>{formatDiscount(promo)}</Text>
        <Text style={styles.voucherCondition}>{getConditionText(promo)}</Text>

        <View style={styles.voucherBottom}>
          <View>
            <View style={styles.codeBox}>
              <Text style={styles.codeText}>{promo.code}</Text>
              <Ionicons name="copy-outline" size={13} color={color} style={{ marginLeft: 4 }} />
            </View>
            {promo.ngayketthuc && (
              <Text style={styles.expireDate}>HSD: {formatDate(promo.ngayketthuc)}</Text>
            )}
          </View>

          {!isMine && (
            <TouchableOpacity
              style={[styles.claimBtn, { backgroundColor: color }, claiming && styles.claimBtnDisabled]}
              onPress={onClaim}
              disabled={claiming}
            >
              {claiming
                ? <ActivityIndicator size="small" color="#fff" />
                : <Text style={styles.claimBtnText}>Lấy mã</Text>
              }
            </TouchableOpacity>
          )}
          {isMine && (
            <View style={[styles.claimedBadge, { borderColor: color }]}>
              <Ionicons name="checkmark-circle" size={14} color={color} />
              <Text style={[styles.claimedText, { color }]}>Đã có</Text>
            </View>
          )}
        </View>
      </View>
    </Animated.View>
  );
}

function StarRating({ value }) {
  return (
    <View style={{ flexDirection: 'row', gap: 2 }}>
      {[1, 2, 3, 4, 5].map(i => (
        <FontAwesome key={i} name={i <= value ? 'star' : 'star-o'} size={13} color="#f59e0b" />
      ))}
    </View>
  );
}

export default function PromotionScreen() {
  const navigation = useNavigation();
  const { token, user } = useAuthStore();

  const [tab, setTab] = useState('public'); // 'public' | 'mine'
  const [publicPromos, setPublicPromos] = useState([]);
  const [myVouchers, setMyVouchers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [claiming, setClaiming] = useState({});

  const fetchData = useCallback(async () => {
    try {
      const [pubRes] = await Promise.all([
        api.get('/promotions'),
      ]);
      setPublicPromos(Array.isArray(pubRes.data) ? pubRes.data : []);

      if (token) {
        try {
          const myRes = await api.get('/user/vouchers');
          setMyVouchers(Array.isArray(myRes.data) ? myRes.data : []);
        } catch {
          setMyVouchers([]);
        }
      }
    } catch {
      //
    }
  }, [token]);

  useEffect(() => {
    setLoading(true);
    fetchData().finally(() => setLoading(false));
  }, [fetchData]);

  const handleRefresh = async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  };

  const handleClaim = async (promo) => {
    if (!token) {
      showAlert('Cần đăng nhập', 'Vui lòng đăng nhập để lấy mã giảm giá.');
      return;
    }
    setClaiming(prev => ({ ...prev, [promo.id]: true }));
    try {
      await api.post('/user/vouchers/claim', { id_voucher: promo.id });
      showAlert('Thành công', `Đã lưu voucher "${promo.code}" vào tài khoản!`);
      await fetchData();
    } catch (err) {
      const msg = err.response?.data?.message || 'Không thể lấy mã này.';
      showAlert('Lỗi', msg);
    } finally {
      setClaiming(prev => ({ ...prev, [promo.id]: false }));
    }
  };

  const myVoucherIds = new Set(myVouchers.map(v => v.id_voucher || v.id));

  const displayList = tab === 'public' ? publicPromos : myVouchers.map(v => v.voucher || v);

  const renderEmpty = () => (
    <View style={styles.emptyContainer}>
      <Text style={{ fontSize: 48 }}>🎁</Text>
      <Text style={styles.emptyTitle}>
        {tab === 'public' ? 'Chưa có khuyến mãi nào' : 'Bạn chưa có voucher nào'}
      </Text>
      {tab === 'mine' && !token ? (
        <View style={{ alignItems: 'center', marginTop: 10 }}>
          <Text style={[styles.emptySubtitle, { textAlign: 'center', paddingHorizontal: 20 }]}>Đăng nhập để xem và quản lý voucher của bạn</Text>
          <TouchableOpacity 
            style={{ marginTop: 15, paddingVertical: 10, paddingHorizontal: 20, backgroundColor: COLORS.primary, borderRadius: RADIUS.md }}
            onPress={() => navigation.navigate('Tài khoản')}
          >
            <Text style={{ color: '#fff', fontWeight: '700' }}>Đăng nhập ngay</Text>
          </TouchableOpacity>
        </View>
      ) : null}
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={22} color={COLORS.textPrimary} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Khuyến mãi</Text>
        <View style={{ width: 36 }} />
      </View>

      {/* Tabs */}
      <View style={styles.tabsRow}>
        <TouchableOpacity
          style={[styles.tabBtn, tab === 'public' && styles.tabBtnActive]}
          onPress={() => setTab('public')}
        >
          <Ionicons name="pricetags-outline" size={16} color={tab === 'public' ? COLORS.primary : COLORS.textTertiary} />
          <Text style={[styles.tabBtnText, tab === 'public' && styles.tabBtnTextActive]}>Tất cả ưu đãi</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tabBtn, tab === 'mine' && styles.tabBtnActive]}
          onPress={() => setTab('mine')}
        >
          <Ionicons name="wallet-outline" size={16} color={tab === 'mine' ? COLORS.primary : COLORS.textTertiary} />
          <Text style={[styles.tabBtnText, tab === 'mine' && styles.tabBtnTextActive]}>Voucher của tôi</Text>
          {myVouchers.length > 0 && (
            <View style={styles.badge}><Text style={styles.badgeText}>{myVouchers.length}</Text></View>
          )}
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerLoader}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Đang tải...</Text>
        </View>
      ) : (
        <FlatList
          data={displayList}
          keyExtractor={(item, idx) => String(item.id || item.id_voucher || idx)}
          renderItem={({ item }) => (
            <VoucherCard
              promo={item}
              isMine={myVoucherIds.has(item.id)}
              claiming={!!claiming[item.id]}
              onClaim={() => handleClaim(item)}
            />
          )}
          contentContainerStyle={styles.listContent}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor={COLORS.primary} />}
          ListEmptyComponent={renderEmpty}
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

  tabsRow: {
    flexDirection: 'row', borderBottomWidth: 1, borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
  },
  tabBtn: {
    flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    paddingVertical: SPACING.md, gap: SPACING.sm, borderBottomWidth: 2, borderColor: 'transparent',
  },
  tabBtnActive: { borderColor: COLORS.primary },
  tabBtnText: { fontSize: 13, fontWeight: '500', color: COLORS.textTertiary },
  tabBtnTextActive: { color: COLORS.primary, fontWeight: '700' },
  badge: {
    backgroundColor: COLORS.primary, borderRadius: 10, paddingHorizontal: 6, paddingVertical: 1, minWidth: 18, alignItems: 'center',
  },
  badgeText: { color: '#fff', fontSize: 10, fontWeight: '700' },

  listContent: { padding: SPACING.lg, gap: SPACING.md },

  voucherCard: {
    backgroundColor: COLORS.surface, borderRadius: RADIUS.lg, flexDirection: 'row',
    borderWidth: 1, borderColor: COLORS.border, overflow: 'hidden', marginBottom: SPACING.md,
  },
  voucherAccent: {
    width: 52, alignItems: 'center', justifyContent: 'center', padding: SPACING.sm,
  },
  dashedSeparator: {
    width: 1, backgroundColor: COLORS.border, marginVertical: SPACING.sm,
    borderStyle: 'dashed',
  },
  voucherContent: { flex: 1, padding: SPACING.md, gap: SPACING.xs },
  voucherTopRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.sm },
  catBadge: {
    paddingHorizontal: SPACING.sm, paddingVertical: 2, borderRadius: RADIUS.sm, borderWidth: 1,
  },
  catBadgeText: { fontSize: 11, fontWeight: '600' },
  expireBadge: {
    backgroundColor: 'rgba(239,68,68,0.15)', paddingHorizontal: SPACING.sm, paddingVertical: 2,
    borderRadius: RADIUS.sm,
  },
  expireBadgeText: { fontSize: 10, color: COLORS.error, fontWeight: '600' },
  voucherName: { fontSize: 14, fontWeight: '700', color: COLORS.textPrimary },
  voucherDiscount: { fontSize: 16, fontWeight: '800', color: COLORS.warning },
  voucherCondition: { fontSize: 11, color: COLORS.textTertiary },
  voucherBottom: { flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between', marginTop: SPACING.xs },
  codeBox: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: 'rgba(99,102,241,0.1)', paddingHorizontal: SPACING.sm, paddingVertical: 3,
    borderRadius: RADIUS.sm, borderWidth: 1, borderColor: 'rgba(99,102,241,0.3)', borderStyle: 'dashed',
  },
  codeText: { fontSize: 13, fontWeight: '700', color: COLORS.primary, letterSpacing: 1 },
  expireDate: { fontSize: 10, color: COLORS.textTertiary, marginTop: 3 },
  claimBtn: {
    paddingHorizontal: SPACING.lg, paddingVertical: SPACING.sm, borderRadius: RADIUS.md,
    minWidth: 72, alignItems: 'center',
  },
  claimBtnDisabled: { opacity: 0.6 },
  claimBtnText: { color: '#fff', fontSize: 13, fontWeight: '700' },
  claimedBadge: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    paddingHorizontal: SPACING.sm, paddingVertical: 4, borderRadius: RADIUS.sm, borderWidth: 1,
  },
  claimedText: { fontSize: 12, fontWeight: '600' },

  centerLoader: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: SPACING.md },
  loadingText: { color: COLORS.textTertiary, fontSize: 14 },

  emptyContainer: { paddingTop: 80, alignItems: 'center', gap: SPACING.md },
  emptyTitle: { fontSize: 16, fontWeight: '600', color: COLORS.textSecondary },
  emptySubtitle: { fontSize: 13, color: COLORS.textTertiary },
});
