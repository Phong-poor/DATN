import React, { useState, useEffect, useCallback } from 'react';
import { StyleSheet, Text, View, ScrollView, TextInput, TouchableOpacity, ActivityIndicator, FlatList, Clipboard, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons, Feather } from '@expo/vector-icons';
import { COLORS, RADIUS, SPACING, TYPOGRAPHY } from '../utils/theme';
import api from '../services/api';
import useAuthStore from '../store/useAuthStore';
import { showAlert } from '../utils/alert';

export default function AffiliateScreen({ navigation }) {
  const token = useAuthStore((state) => state.token);

  const [loading, setLoading] = useState(true);
  const [active, setActive] = useState(false);
  const [profile, setProfile] = useState(null);
  const [stats, setStats] = useState(null);
  const [refLink, setRefLink] = useState('');
  
  // Withdraw and Lists
  const [withdrawAmount, setWithdrawAmount] = useState('');
  const [withdrawLoading, setWithdrawLoading] = useState(false);
  const [tab, setTab] = useState('referrals'); // 'referrals' | 'withdraws'
  const [referrals, setReferrals] = useState([]);
  const [withdraws, setWithdraws] = useState([]);
  const [submitting, setSubmitting] = useState(false);

  const loadData = useCallback(async () => {
    try {
      setLoading(true);
      const res = await api.get('/affiliate/me');
      setActive(res.data.active);
      setProfile(res.data.profile);
      setStats(res.data.stats);
      setRefLink(res.data.ref_link || '');

      if (res.data.active) {
        const [refRes, withdrawRes] = await Promise.all([
          api.get('/affiliate/referrals').catch(() => ({ data: [] })),
          api.get('/affiliate/withdraws').catch(() => ({ data: [] })),
        ]);
        setReferrals(refRes.data.data || refRes.data || []);
        setWithdraws(withdrawRes.data.data || withdrawRes.data || []);
      }
    } catch (err) {
      console.log(err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (token) {
      loadData();
    }
  }, [token, loadData]);

  const handleActivate = async () => {
    setSubmitting(true);
    try {
      const res = await api.post('/affiliate/activate');
      showAlert('Thành công', 'Đăng ký chương trình VinaTech Partner thành công!');
      loadData();
    } catch (err) {
      const msg = err.response?.data?.message || 'Không thể đăng ký lúc này.';
      showAlert('Lỗi', msg);
    } finally {
      setSubmitting(false);
    }
  };

  const handleCopyLink = () => {
    Clipboard.setString(refLink);
    showAlert('Thành công', 'Đã sao chép link tiếp thị vào bộ nhớ tạm!');
  };

  const handleWithdrawRequest = async () => {
    const amountStr = withdrawAmount.trim();
    if (!amountStr || isNaN(amountStr) || parseFloat(amountStr) <= 0) {
      showAlert('Lỗi', 'Vui lòng nhập số tiền rút hợp lệ.');
      return;
    }
    const amount = parseFloat(amountStr);
    if (amount > (stats?.available_balance || 0)) {
      showAlert('Lỗi', 'Số dư hoa hồng khả dụng không đủ.');
      return;
    }

    setWithdrawLoading(true);
    try {
      await api.post('/affiliate/withdraws', { so_tien: amount });
      showAlert('Thành công', 'Gửi yêu cầu rút tiền thành công! Vui lòng chờ Admin xét duyệt.');
      setWithdrawAmount('');
      loadData();
    } catch (err) {
      const msg = err.response?.data?.message || 'Không thể gửi yêu cầu rút tiền.';
      showAlert('Lỗi', msg);
    } finally {
      setWithdrawLoading(false);
    }
  };

  const formatPrice = (value) => {
    return parseFloat(value || 0).toLocaleString('vi-VN') + '₫';
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
  };

  if (!token) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <View style={styles.topBar}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.backIcon}>❮</Text>
            <Text style={styles.backText}>Quay lại</Text>
          </TouchableOpacity>
          <Text style={styles.topTitle}>Đối tác VinaTech</Text>
        </View>
        <View style={styles.authPromptContainer}>
          <Text style={styles.authPromptIcon}>🤝</Text>
          <Text style={styles.authPromptTitle}>Yêu cầu đăng nhập</Text>
          <Text style={styles.authPromptDesc}>Vui lòng đăng nhập tài khoản của bạn để tham gia chương trình Tiếp thị liên kết Affiliate Partner.</Text>
          <TouchableOpacity style={styles.authPromptBtn} onPress={() => navigation.navigate('Tài khoản')}>
            <Text style={styles.authPromptBtnText}>Đăng nhập ngay</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* Header */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>❮</Text>
          <Text style={styles.backText}>Quay lại</Text>
        </TouchableOpacity>
        <Text style={styles.topTitle}>Đối tác VinaTech</Text>
      </View>

      {loading ? (
        <View style={styles.centerLoader}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Đang tải trung tâm tiếp thị...</Text>
        </View>
      ) : !active ? (
        /* Not Activated Layout */
        <ScrollView contentContainerStyle={styles.scrollContent}>
          <View style={styles.landingCard}>
            <Text style={styles.landingEmoji}>🤝</Text>
            <Text style={styles.landingTitle}>VinaTech Partner Program</Text>
            <Text style={styles.landingDesc}>
              Tham gia mạng lưới đối tác tiếp thị liên kết của VinaTech để bắt đầu kiếm tiền ngay hôm nay:
            </Text>
            <View style={styles.benefitList}>
              <View style={styles.benefitRow}>
                <Ionicons name="checkmark-circle" size={18} color="#10b981" />
                <Text style={styles.benefitText}>Nhận hoa hồng lên tới 5% giá trị đơn hàng</Text>
              </View>
              <View style={styles.benefitRow}>
                <Ionicons name="checkmark-circle" size={18} color="#10b981" />
                <Text style={styles.benefitText}>Thống kê lượt click, đăng ký và hoa hồng theo thời gian thực</Text>
              </View>
              <View style={styles.benefitRow}>
                <Ionicons name="checkmark-circle" size={18} color="#10b981" />
                <Text style={styles.benefitText}>Rút tiền dễ dàng, minh bạch, xét duyệt nhanh chóng</Text>
              </View>
            </View>

            <TouchableOpacity
              style={[styles.activateBtn, submitting && { opacity: 0.7 }]}
              onPress={handleActivate}
              disabled={submitting}
            >
              {submitting ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.activateBtnText}>Kích hoạt tài khoản đối tác</Text>
              )}
            </TouchableOpacity>
          </View>
        </ScrollView>
      ) : (
        /* Active Dashboard Layout */
        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          {/* Stats Summary Card */}
          <View style={styles.statsCard}>
            <Text style={styles.cardHeaderTitle}>Tổng quan thu nhập</Text>
            <View style={styles.statsGrid}>
              <View style={styles.statsItem}>
                <Text style={styles.statsLabel}>Số dư khả dụng</Text>
                <Text style={[styles.statsValue, { color: COLORS.success || '#10b981' }]}>
                  {formatPrice(stats?.available_balance)}
                </Text>
              </View>
              <View style={styles.statsItem}>
                <Text style={styles.statsLabel}>Tổng lượt giới thiệu</Text>
                <Text style={styles.statsValue}>{stats?.total_referrals || 0}</Text>
              </View>
            </View>
            <View style={styles.statsGrid}>
              <View style={styles.statsItem}>
                <Text style={styles.statsLabel}>Đang chờ duyệt</Text>
                <Text style={[styles.statsValue, { color: '#f59e0b', fontSize: 16 }]}>
                  {formatPrice(stats?.pending_commission)}
                </Text>
              </View>
              <View style={styles.statsItem}>
                <Text style={styles.statsLabel}>Đã thanh toán</Text>
                <Text style={[styles.statsValue, { color: COLORS.primary, fontSize: 16 }]}>
                  {formatPrice(stats?.paid_commission)}
                </Text>
              </View>
            </View>
          </View>

          {/* Referral link generator */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Link tiếp thị liên kết của bạn</Text>
            <Text style={styles.sectionDesc}>Chia sẻ đường dẫn này, khi có khách hàng nhấp và đăng ký mua hàng, bạn sẽ nhận được phần trăm hoa hồng của đơn đó!</Text>
            
            <View style={styles.linkContainer}>
              <Text style={styles.linkText} numberOfLines={1}>{refLink}</Text>
              <TouchableOpacity style={styles.copyBtn} onPress={handleCopyLink}>
                <Ionicons name="copy-outline" size={18} color="#fff" />
              </TouchableOpacity>
            </View>
          </View>

          {/* Withdraw Request Form */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Yêu cầu rút tiền hoa hồng</Text>
            <Text style={styles.sectionDesc}>Số tiền khả dụng hiện tại có thể rút: {formatPrice(stats?.available_balance)}</Text>
            <View style={styles.withdrawRow}>
              <TextInput
                style={styles.withdrawInput}
                value={withdrawAmount}
                onChangeText={setWithdrawAmount}
                placeholder="Nhập số tiền cần rút..."
                placeholderTextColor="#64748b"
                keyboardType="numeric"
              />
              <TouchableOpacity
                style={[styles.withdrawBtn, withdrawLoading && { opacity: 0.7 }]}
                onPress={handleWithdrawRequest}
                disabled={withdrawLoading}
              >
                {withdrawLoading ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <Text style={styles.withdrawBtnText}>Rút tiền</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>

          {/* Tab lists */}
          <View style={styles.sectionCard}>
            <View style={styles.tabsRow}>
              <TouchableOpacity
                style={[styles.tabBtn, tab === 'referrals' && styles.tabBtnActive]}
                onPress={() => setTab('referrals')}
              >
                <Text style={[styles.tabBtnText, tab === 'referrals' && styles.tabBtnTextActive]}>Khách hàng giới thiệu</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.tabBtn, tab === 'withdraws' && styles.tabBtnActive]}
                onPress={() => setTab('withdraws')}
              >
                <Text style={[styles.tabBtnText, tab === 'withdraws' && styles.tabBtnTextActive]}>Lịch sử rút tiền</Text>
              </TouchableOpacity>
            </View>

            {tab === 'referrals' ? (
              referrals.length === 0 ? (
                <Text style={styles.noDataText}>Chưa có ai đăng ký qua mã giới thiệu của bạn.</Text>
              ) : (
                referrals.map((r, index) => (
                  <View key={r.id || index} style={styles.listRow}>
                    <View>
                      <Text style={styles.itemName}>{r.referred_user?.ten || 'Đối tác giới thiệu'}</Text>
                      <Text style={styles.itemSub}>{r.referred_user?.email || ''}</Text>
                    </View>
                    <Text style={styles.itemDate}>{formatDate(r.created_at)}</Text>
                  </View>
                ))
              )
            ) : (
              withdraws.length === 0 ? (
                <Text style={styles.noDataText}>Bạn chưa gửi yêu cầu rút tiền nào.</Text>
              ) : (
                withdraws.map((w, index) => (
                  <View key={w.id || index} style={styles.listRow}>
                    <View>
                      <Text style={styles.itemName}>Rút {formatPrice(w.so_tien)}</Text>
                      <Text style={[styles.itemStatusText, { color: w.trangthai === 'approved' || w.trangthai === 'paid' ? '#10b981' : w.trangthai === 'pending' ? '#f59e0b' : '#ef4444' }]}>
                        {w.trangthai === 'pending' ? 'Chờ duyệt' : w.trangthai === 'paid' ? 'Đã chi trả' : 'Từ chối'}
                      </Text>
                    </View>
                    <Text style={styles.itemDate}>{formatDate(w.created_at)}</Text>
                  </View>
                ))
              )
            )}
          </View>
        </ScrollView>
      )}
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
    backgroundColor: COLORS.background,
  },
  backBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: SPACING.lg,
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
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  centerLoader: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: SPACING.md,
    color: COLORS.textSecondary,
    fontSize: 14,
  },
  scrollContent: {
    padding: SPACING.lg,
  },
  authPromptContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: SPACING.xl,
    backgroundColor: COLORS.background,
  },
  authPromptIcon: {
    fontSize: 48,
    marginBottom: SPACING.md,
  },
  authPromptTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.sm,
  },
  authPromptDesc: {
    fontSize: 14,
    color: COLORS.textSecondary,
    textAlign: 'center',
    marginBottom: SPACING.xl,
    lineHeight: 20,
  },
  authPromptBtn: {
    backgroundColor: COLORS.primary,
    paddingVertical: 12,
    paddingHorizontal: 24,
    borderRadius: RADIUS.md,
  },
  authPromptBtnText: {
    color: COLORS.white,
    fontWeight: '700',
    fontSize: 14,
  },
  landingCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.xl,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.border,
    marginTop: SPACING.lg,
  },
  landingEmoji: {
    fontSize: 64,
    marginBottom: SPACING.md,
  },
  landingTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: COLORS.textPrimary,
    marginBottom: SPACING.md,
  },
  landingDesc: {
    fontSize: 13,
    color: COLORS.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
    marginBottom: SPACING.xl,
  },
  benefitList: {
    width: '100%',
    marginBottom: SPACING.xl,
    gap: SPACING.md,
  },
  benefitRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: SPACING.md,
  },
  benefitText: {
    fontSize: 13,
    color: COLORS.textPrimary,
    fontWeight: '600',
    flex: 1,
  },
  activateBtn: {
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
    paddingVertical: 14,
    paddingHorizontal: 28,
    width: '100%',
    alignItems: 'center',
  },
  activateBtnText: {
    color: COLORS.white,
    fontSize: 14,
    fontWeight: '700',
  },
  statsCard: {
    backgroundColor: '#312e81', // Premium dark indigo card
    borderRadius: RADIUS.lg,
    padding: SPACING.lg,
    marginBottom: SPACING.lg,
  },
  cardHeaderTitle: {
    color: '#c7d2fe',
    fontWeight: '700',
    fontSize: 11,
    textTransform: 'uppercase',
    marginBottom: SPACING.md,
  },
  statsGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: SPACING.md,
  },
  statsItem: {
    flex: 1,
  },
  statsLabel: {
    fontSize: 11,
    color: '#a5b4fc',
    marginBottom: 4,
  },
  statsValue: {
    fontSize: 18,
    fontWeight: '800',
    color: COLORS.white,
  },
  sectionCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.lg,
    marginBottom: SPACING.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.sm,
    borderLeftWidth: 3,
    borderLeftColor: COLORS.primary,
    paddingLeft: SPACING.sm,
  },
  sectionDesc: {
    fontSize: 12,
    color: COLORS.textSecondary,
    lineHeight: 18,
    marginBottom: SPACING.md,
  },
  linkContainer: {
    flexDirection: 'row',
    backgroundColor: COLORS.background,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    alignItems: 'center',
    overflow: 'hidden',
  },
  linkText: {
    flex: 1,
    color: COLORS.textSecondary,
    fontSize: 12,
    paddingHorizontal: SPACING.md,
  },
  copyBtn: {
    backgroundColor: COLORS.primary,
    paddingVertical: 12,
    paddingHorizontal: 16,
  },
  withdrawRow: {
    flexDirection: 'row',
    gap: SPACING.md,
  },
  withdrawInput: {
    flex: 1,
    backgroundColor: COLORS.background,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: SPACING.md,
    color: COLORS.textPrimary,
    fontSize: 13,
  },
  withdrawBtn: {
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
    paddingHorizontal: 16,
    justifyContent: 'center',
  },
  withdrawBtnText: {
    color: COLORS.white,
    fontWeight: '700',
    fontSize: 13,
  },
  tabsRow: {
    flexDirection: 'row',
    borderBottomWidth: 1,
    borderColor: COLORS.border,
    marginBottom: SPACING.md,
  },
  tabBtn: {
    flex: 1,
    paddingVertical: SPACING.md,
    alignItems: 'center',
  },
  tabBtnActive: {
    borderBottomWidth: 2,
    borderBottomColor: COLORS.primary,
  },
  tabBtnText: {
    fontSize: 12,
    color: COLORS.textTertiary,
    fontWeight: '600',
  },
  tabBtnTextActive: {
    color: COLORS.primary,
    fontWeight: '700',
  },
  noDataText: {
    fontSize: 12,
    color: COLORS.textTertiary,
    textAlign: 'center',
    marginVertical: SPACING.lg,
  },
  listRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: SPACING.md,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
  },
  itemName: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  itemSub: {
    fontSize: 11,
    color: COLORS.textTertiary,
  },
  itemDate: {
    fontSize: 11,
    color: COLORS.textTertiary,
  },
  itemStatusText: {
    fontSize: 11,
    fontWeight: '700',
    marginTop: 2,
  },
});
