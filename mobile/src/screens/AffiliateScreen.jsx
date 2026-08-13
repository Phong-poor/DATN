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
  const [withdrawBankName, setWithdrawBankName] = useState('');
  const [withdrawAccountName, setWithdrawAccountName] = useState('');
  const [withdrawAccountNumber, setWithdrawAccountNumber] = useState('');
  const [withdrawLoading, setWithdrawLoading] = useState(false);
  const [tab, setTab] = useState('referrals'); // 'referrals' | 'withdraws'
  const [referrals, setReferrals] = useState([]);
  const [withdraws, setWithdraws] = useState([]);
  const [commissions, setCommissions] = useState([]);
  const [wallet, setWallet] = useState(null);
  const [videos, setVideos] = useState([]);
  const [videoTitle, setVideoTitle] = useState('');
  const [videoUrl, setVideoUrl] = useState('');
  const [videoDescription, setVideoDescription] = useState('');
  const [videoLoading, setVideoLoading] = useState(false);
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
        const [refRes, withdrawRes, commissionRes, walletRes, videoRes] = await Promise.all([
          api.get('/affiliate/referrals').catch(() => ({ data: [] })),
          api.get('/affiliate/withdraws').catch(() => ({ data: [] })),
          api.get('/affiliate/commissions').catch(() => ({ data: [] })),
          api.get('/affiliate/wallet').catch(() => ({ data: null })),
          api.get('/affiliate/videos').catch(() => ({ data: [] })),
        ]);
        setReferrals(refRes.data.data || refRes.data || []);
        setWithdraws(withdrawRes.data.data || withdrawRes.data || []);
        setCommissions(commissionRes.data.data || commissionRes.data || []);
        setWallet(walletRes.data?.data || walletRes.data || null);
        setVideos(videoRes.data?.data || videoRes.data || []);
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

  const submitVideo = async () => {
    if (videoTitle.trim().length < 5 || !/^https?:\/\//.test(videoUrl.trim())) {
      showAlert('Thiếu thông tin', 'Tiêu đề cần ít nhất 5 ký tự và link video phải bắt đầu bằng http/https.');
      return;
    }
    setVideoLoading(true);
    try {
      await api.post('/affiliate/videos', { title: videoTitle.trim(), description: videoDescription.trim() || null, video_url: videoUrl.trim() });
      setVideoTitle(''); setVideoUrl(''); setVideoDescription('');
      showAlert('Thành công', 'Video đã được gửi và đang chờ duyệt.');
      await loadData();
    } catch (err) {
      showAlert('Lỗi', err.response?.data?.message || 'Không thể gửi video.');
    } finally { setVideoLoading(false); }
  };

  const deleteVideo = async (id) => {
    try {
      await api.delete(`/affiliate/videos/${id}`);
      setVideos((current) => current.filter((video) => video.id !== id));
    } catch (err) { showAlert('Lỗi', err.response?.data?.message || 'Không thể xóa video.'); }
  };

  const handleCopyLink = () => {
    Clipboard.setString(refLink);
    showAlert('Thành công', 'Đã sao chép link tiếp thị vào bộ nhớ tạm!');
  };

  const handleWithdrawRequest = async () => {
    const amountStr = withdrawAmount.trim();
    if (!amountStr || isNaN(amountStr) || parseFloat(amountStr) < 10000) {
      showAlert('Lỗi', 'Số tiền rút tối thiểu là 10.000₫.');
      return;
    }
    const amount = parseFloat(amountStr);
    if (amount > (stats?.available_balance || 0)) {
      showAlert('Lỗi', 'Số dư hoa hồng khả dụng không đủ.');
      return;
    }
    if (!withdrawBankName.trim() || !withdrawAccountName.trim() || !withdrawAccountNumber.trim()) {
      showAlert('Lỗi', 'Vui lòng nhập đầy đủ ngân hàng, tên chủ tài khoản và số tài khoản.');
      return;
    }

    setWithdrawLoading(true);
    try {
      await api.post('/affiliate/withdraws', {
        amount,
        bank_name: withdrawBankName.trim(),
        bank_account_name: withdrawAccountName.trim().toUpperCase(),
        bank_account_number: withdrawAccountNumber.trim(),
      });
      showAlert('Thành công', 'Gửi yêu cầu rút tiền thành công! Vui lòng chờ Admin xét duyệt.');
      setWithdrawAmount('');
      setWithdrawBankName('');
      setWithdrawAccountName('');
      setWithdrawAccountNumber('');
      await loadData();
    } catch (err) {
      const validationMessage = Object.values(err.response?.data?.errors || {}).flat()[0];
      const msg = validationMessage || err.response?.data?.message || 'Không thể gửi yêu cầu rút tiền.';
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
          <TouchableOpacity style={styles.authPromptBtn} onPress={() => navigation.navigate('Main', { screen: 'Tài khoản' })}>
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
            {wallet ? <Text style={styles.walletLine}>Ví mới: {formatPrice(wallet.balance)} · Chờ đối soát: {formatPrice(wallet.pending_balance)} · Đã rút: {formatPrice(wallet.total_withdrawn)}</Text> : null}
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

          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Đăng video affiliate</Text>
            <TextInput style={styles.withdrawInput} value={videoTitle} onChangeText={setVideoTitle} placeholder="Tiêu đề video" placeholderTextColor="#64748b" />
            <TextInput style={styles.withdrawInput} value={videoUrl} onChangeText={setVideoUrl} placeholder="https://youtube.com/..." placeholderTextColor="#64748b" autoCapitalize="none" />
            <TextInput style={[styles.withdrawInput, { minHeight: 70 }]} value={videoDescription} onChangeText={setVideoDescription} placeholder="Mô tả video" placeholderTextColor="#64748b" multiline />
            <TouchableOpacity style={[styles.withdrawBtn, videoLoading && { opacity: 0.7 }]} onPress={submitVideo} disabled={videoLoading}>
              {videoLoading ? <ActivityIndicator color="#fff" /> : <Text style={styles.withdrawBtnText}>Gửi video duyệt</Text>}
            </TouchableOpacity>
          </View>

          {/* Withdraw Request Form */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Yêu cầu rút tiền hoa hồng</Text>
            <Text style={styles.sectionDesc}>Số tiền khả dụng hiện tại có thể rút: {formatPrice(stats?.available_balance)}</Text>
            <View style={styles.withdrawForm}>
              <Text style={styles.withdrawLabel}>Số tiền muốn rút</Text>
              <TextInput
                style={styles.withdrawInput}
                value={withdrawAmount}
                onChangeText={setWithdrawAmount}
                placeholder="Tối thiểu 10.000₫"
                placeholderTextColor="#64748b"
                keyboardType="numeric"
              />

              <Text style={styles.withdrawLabel}>Ngân hàng</Text>
              <TextInput
                style={styles.withdrawInput}
                value={withdrawBankName}
                onChangeText={setWithdrawBankName}
                placeholder="Ví dụ: Vietcombank, Techcombank..."
                placeholderTextColor="#64748b"
                maxLength={120}
              />

              <Text style={styles.withdrawLabel}>Tên chủ tài khoản</Text>
              <TextInput
                style={styles.withdrawInput}
                value={withdrawAccountName}
                onChangeText={setWithdrawAccountName}
                placeholder="NGUYEN VAN A"
                placeholderTextColor="#64748b"
                autoCapitalize="characters"
                maxLength={120}
              />

              <Text style={styles.withdrawLabel}>Số tài khoản</Text>
              <TextInput
                style={styles.withdrawInput}
                value={withdrawAccountNumber}
                onChangeText={setWithdrawAccountNumber}
                placeholder="Nhập chính xác số tài khoản"
                placeholderTextColor="#64748b"
                keyboardType="number-pad"
                maxLength={50}
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
              <TouchableOpacity style={[styles.tabBtn, tab === 'commissions' && styles.tabBtnActive]} onPress={() => setTab('commissions')}>
                <Text style={[styles.tabBtnText, tab === 'commissions' && styles.tabBtnTextActive]}>Hoa hồng</Text>
              </TouchableOpacity>
              <TouchableOpacity style={[styles.tabBtn, tab === 'videos' && styles.tabBtnActive]} onPress={() => setTab('videos')}>
                <Text style={[styles.tabBtnText, tab === 'videos' && styles.tabBtnTextActive]}>Video</Text>
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
            ) : tab === 'withdraws' ? (
              withdraws.length === 0 ? (
                <Text style={styles.noDataText}>Bạn chưa gửi yêu cầu rút tiền nào.</Text>
              ) : (
                withdraws.map((w, index) => (
                  <View key={w.id || index} style={styles.listRow}>
                    <View>
                      <Text style={styles.itemName}>Rút {formatPrice(w.so_tien)}</Text>
                      <Text style={[styles.itemStatusText, { color: w.trangthai === 'approved' || w.trangthai === 'paid' ? '#10b981' : w.trangthai === 'pending' ? '#f59e0b' : '#ef4444' }]}>
                        {w.trangthai === 'pending'
                          ? 'Chờ duyệt'
                          : w.trangthai === 'approved'
                            ? 'Đã duyệt'
                            : w.trangthai === 'paid'
                              ? 'Đã chi trả'
                              : 'Từ chối'}
                      </Text>
                    </View>
                    <Text style={styles.itemDate}>{formatDate(w.created_at)}</Text>
                  </View>
                ))
              )
            ) : tab === 'commissions' ? (
              commissions.length === 0 ? <Text style={styles.noDataText}>Chưa có giao dịch hoa hồng.</Text> : commissions.map((commission, index) => (
                <View key={commission.id || index} style={styles.listRow}>
                  <View><Text style={styles.itemName}>{formatPrice(commission.so_tien)}</Text><Text style={styles.itemSub}>Đơn #{commission.id_dathang || commission.order?.id_dathang || '—'} · {commission.trangthai}</Text></View>
                  <Text style={styles.itemDate}>{formatDate(commission.created_at)}</Text>
                </View>
              ))
            ) : (
              videos.length === 0 ? <Text style={styles.noDataText}>Bạn chưa đăng video affiliate.</Text> : videos.map((video, index) => (
                <View key={video.id || index} style={styles.listRow}>
                  <View style={{ flex: 1 }}><Text style={styles.itemName}>{video.tieu_de}</Text><Text style={styles.itemSub}>{video.trangthai}</Text></View>
                  <TouchableOpacity onPress={() => deleteVideo(video.id)}><Ionicons name="trash-outline" size={20} color={COLORS.error} /></TouchableOpacity>
                </View>
              ))
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
  walletLine: { color: '#c7d2fe', fontSize: 11, lineHeight: 18, borderTopWidth: 1, borderTopColor: '#ffffff22', paddingTop: SPACING.sm },
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
  withdrawForm: {
    gap: SPACING.sm,
  },
  withdrawLabel: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '600',
    marginTop: SPACING.xs,
  },
  withdrawInput: {
    backgroundColor: COLORS.background,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: SPACING.md,
    paddingVertical: SPACING.md,
    color: COLORS.textPrimary,
    fontSize: 13,
  },
  withdrawBtn: {
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
    paddingHorizontal: 16,
    paddingVertical: SPACING.md,
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: SPACING.sm,
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
