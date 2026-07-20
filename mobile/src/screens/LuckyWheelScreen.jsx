import React, { useState, useEffect, useRef } from 'react';
import { StyleSheet, Text, View, TouchableOpacity, ActivityIndicator, Animated, Easing, ScrollView, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons, Feather } from '@expo/vector-icons';
import { COLORS, RADIUS, SPACING, TYPOGRAPHY } from '../utils/theme';
import api from '../services/api';
import useAuthStore from '../store/useAuthStore';
import { showAlert } from '../utils/alert';

export default function LuckyWheelScreen({ navigation }) {
  const token = useAuthStore((state) => state.token);
  const user = useAuthStore((state) => state.user);
  const checkSession = useAuthStore((state) => state.checkSession);

  const [prizes, setPrizes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [spinning, setSpinning] = useState(false);
  const [history, setHistory] = useState([]);
  const [hasSpunToday, setHasSpunToday] = useState(false);

  // Shopee Check-in and Balances States
  const [checkinStatus, setCheckinStatus] = useState(null);
  const [userTickets, setUserTickets] = useState(0);
  const [userCoins, setUserCoins] = useState(0);

  const spinValue = useRef(new Animated.Value(0)).current;
  const currentRotation = useRef(0);

  const fetchCheckinStatus = async () => {
    try {
      const res = await api.get('/diem-danh/status');
      if (res.data?.success) {
        setCheckinStatus(res.data);
      }
    } catch (err) {
      console.log('Failed to fetch check-in status:', err);
    }
  };

  // Fetch prizes list and spin history on mount
  useEffect(() => {
    if (!token) return;
    const loadData = async () => {
      try {
        setLoading(true);
        // Load latest user details (tickets/coins)
        const [prizeRes, historyRes, sessionRes] = await Promise.all([
          api.get('/vong-quay/prizes'),
          api.get('/vong-quay/lich-su').catch(() => ({ data: { data: [] } })),
          api.get('/auth/session').catch(() => null),
        ]);

        if (prizeRes.data?.success) {
          setPrizes(prizeRes.data.data || []);
        }

        if (historyRes.data?.success) {
          const list = historyRes.data.data || [];
          setHistory(list);
          
          // Check if user has spun today
          const todayStr = new Date().toDateString();
          const spun = list.some(h => new Date(h.created_at).toDateString() === todayStr);
          setHasSpunToday(spun);
        }

        if (sessionRes && sessionRes.data?.user) {
          setUserTickets(sessionRes.data.user.luot_quay || 0);
          setUserCoins(sessionRes.data.user.xu || 0);
        }

        await fetchCheckinStatus();
      } catch (err) {
        console.log(err);
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, [token]);

  const handleClaimDailyFreeSpin = async () => {
    try {
      const res = await api.post('/vong-quay/nhan-luot');
      showAlert('Thành công', res.data?.message || 'Đã nhận lượt quay miễn phí hôm nay!');
      if (res.data?.tickets !== undefined) {
        setUserTickets(res.data.tickets);
      }
      await checkSession().catch(() => {});
    } catch (err) {
      const msg = err.response?.data?.message || 'Bạn đã nhận lượt quay hôm nay rồi.';
      showAlert('Thông báo', msg);
    }
  };

  const handleCheckIn = async () => {
    try {
      const res = await api.post('/diem-danh');
      if (res.data?.success) {
        showAlert('Thành công', res.data.message);
        setUserCoins(res.data.total_xu);
        await fetchCheckinStatus();
        await checkSession().catch(() => {});
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Không thể điểm danh lúc này.';
      showAlert('Thông báo', msg);
    }
  };

  const handleSpin = async () => {
    if (spinning) return;

    setSpinning(true);
    try {
      const res = await api.post('/vong-quay/quay');
      if (res.data?.success) {
        const winningPrize = res.data.prize;
        const prizeIndex = prizes.findIndex(p => p.id === winningPrize.id);

        if (prizeIndex === -1) {
          showAlert('Thành công', `Chúc mừng! Bạn đã trúng giải: ${winningPrize.ten}`);
          setSpinning(false);
          if (res.data?.tickets !== undefined) {
            setUserTickets(res.data.tickets);
          }
          await checkSession().catch(() => {});
          return;
        }

        // Calculate rotation animation details
        const segmentAngle = 360 / prizes.length;
        const prizeAngle = prizeIndex * segmentAngle;
        
        // Target angle lands the pointer at the top (subtracting the angle from 360)
        const targetAngle = 360 - prizeAngle;
        
        // Spin at least 5 complete rounds (1800 degrees) plus target angle offset
        const totalSpinDegrees = currentRotation.current + 1800 + targetAngle;
        
        Animated.timing(spinValue, {
          toValue: totalSpinDegrees,
          duration: 4000,
          easing: Easing.out(Easing.quad),
          useNativeDriver: Platform.OS !== 'web',
        }).start(async () => {
          // Animation finished
          currentRotation.current = totalSpinDegrees % 360;
          setSpinning(false);
          setHasSpunToday(true);
          showAlert('Chúc mừng!', `Bạn đã trúng thưởng: ${winningPrize.ten}`);
          
          if (res.data?.tickets !== undefined) {
            setUserTickets(res.data.tickets);
          }
          if (res.data?.xu !== undefined) {
            setUserCoins(res.data.xu);
          }
          
          // Refresh user session/tickets/coins
          await checkSession().catch(() => {});

          // Refresh history
          api.get('/vong-quay/lich-su').then(hRes => {
            if (hRes.data?.success) setHistory(hRes.data.data || []);
          }).catch(() => {});
        });
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Không thể thực hiện lượt quay lúc này.';
      showAlert('Lỗi', msg);
      setSpinning(false);
    }
  };

  if (!token) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <View style={styles.topBar}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.backIcon}>❮</Text>
            <Text style={styles.backText}>Quay lại</Text>
          </TouchableOpacity>
          <Text style={styles.topTitle}>Vòng quay may mắn</Text>
        </View>
        <View style={styles.authPromptContainer}>
          <Text style={styles.authPromptIcon}>🎡</Text>
          <Text style={styles.authPromptTitle}>Yêu cầu đăng nhập</Text>
          <Text style={styles.authPromptDesc}>Vui lòng đăng nhập tài khoản của bạn để tham gia vòng quay may mắn trúng thưởng.</Text>
          <TouchableOpacity style={styles.authPromptBtn} onPress={() => navigation.navigate('Tài khoản')}>
            <Text style={styles.authPromptBtnText}>Đăng nhập ngay</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const spinRotation = spinValue.interpolate({
    inputRange: [0, 36000],
    outputRange: ['0deg', '36000deg'],
  });

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* Header */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>❮</Text>
          <Text style={styles.backText}>Quay lại</Text>
        </TouchableOpacity>
        <Text style={styles.topTitle}>Vòng quay may mắn</Text>
      </View>

      {loading ? (
        <View style={styles.centerLoader}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Đang tải vòng quay...</Text>
        </View>
      ) : (
        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          {/* Shopee-style Daily Check-in Card */}
          <View style={styles.checkinCard}>
            <View style={styles.checkinHeader}>
              <View>
                <Text style={styles.checkinTitle}>Điểm danh nhận Xu hàng ngày</Text>
                <Text style={styles.checkinSub}>Chuỗi liên tục: <Text style={{ color: '#f59e0b', fontWeight: 'bold' }}>{checkinStatus?.current_streak || 0} ngày</Text></Text>
              </View>
              <TouchableOpacity
                style={[
                  styles.checkinActionBtn,
                  checkinStatus?.checked_today && styles.checkinActionBtnDisabled
                ]}
                onPress={handleCheckIn}
                disabled={checkinStatus?.checked_today}
              >
                <Text style={styles.checkinActionText}>
                  {checkinStatus?.checked_today ? 'Đã điểm danh' : 'Điểm danh'}
                </Text>
              </TouchableOpacity>
            </View>

            <View style={styles.checkinGrid}>
              {(checkinStatus?.days_progress || []).map((day) => {
                const isChecked = day.status === 'checked';
                const isCurrent = day.status === 'current';
                
                // Map Day numbers to labels: 1 -> Th2, 2 -> Th3, ..., 6 -> Th7, 7 -> CN
                const getDayLabel = (d) => {
                  if (d === 7) return 'CN';
                  return 'T' + (d + 1);
                };

                return (
                  <View key={day.day} style={[styles.dayItem, isChecked && styles.dayItemChecked, isCurrent && styles.dayItemCurrent]}>
                    <Text style={styles.dayLabel}>{getDayLabel(day.day)}</Text>
                    <View style={styles.dayBadge}>
                      {isChecked ? (
                        <Ionicons name="checkmark-circle" size={14} color="#10b981" />
                      ) : (
                        <Text style={[styles.dayXuText, isCurrent && styles.dayXuTextCurrent]}>+{day.xu}</Text>
                      )}
                    </View>
                  </View>
                );
              })}
            </View>
          </View>

          {/* Daily Spin Ticket Claim Card */}
          <TouchableOpacity style={styles.claimDailyCard} onPress={handleClaimDailyFreeSpin}>
            <Ionicons name="gift-outline" size={20} color="#fff" />
            <Text style={styles.claimDailyText}>Điểm danh nhận thêm lượt quay hàng ngày!</Text>
            <Ionicons name="chevron-forward" size={16} color="#fff" />
          </TouchableOpacity>

          {/* User Balances Info */}
          <View style={styles.balancesCard}>
            <View style={styles.balanceItem}>
              <Text style={styles.balanceLabel}>🎟️ Lượt quay còn lại</Text>
              <Text style={styles.balanceValue}>{userTickets}</Text>
            </View>
            <View style={styles.balanceDivider} />
            <View style={styles.balanceItem}>
              <Text style={styles.balanceLabel}>🪙 Xu hiện có</Text>
              <Text style={styles.balanceValue}>{userCoins.toLocaleString('vi-VN')}</Text>
            </View>
          </View>

          {/* Lucky Wheel Container */}
          <View style={styles.wheelSection}>
            {/* Top Pointer Indicator */}
            <View style={styles.pointerContainer}>
              <Ionicons name="triangle" size={32} color="#f43f5e" style={styles.pointerIcon} />
            </View>

            {/* Neon Rotating Wheel */}
            <Animated.View style={[styles.wheelContainer, { transform: [{ rotate: spinRotation }] }]}>
              {prizes.map((prize, idx) => {
                const angle = 360 / prizes.length;
                const rotation = idx * angle;

                // Inner radius is 280 / 2 - 8 (border) = 132
                const radius = 132;
                const segmentRad = Math.PI / prizes.length;
                const borderWidth = radius * Math.tan(segmentRad);

                return (
                  <View
                    key={prize.id || `prize-${idx}`}
                    style={[
                      styles.wheelSegmentContainer,
                      {
                        transform: [{ rotate: `${rotation}deg` }],
                      },
                    ]}
                  >
                    <View
                      style={[
                        styles.wheelSegmentTriangle,
                        {
                          borderTopColor: prize.mau_sac || '#6366f1',
                          borderLeftWidth: borderWidth,
                          borderRightWidth: borderWidth,
                          borderTopWidth: radius,
                        },
                      ]}
                    />
                    <Text style={[styles.wheelPrizeText, { color: prize.mau_chu || '#fff' }]}>
                      {prize.ten}
                    </Text>
                  </View>
                );
              })}
            </Animated.View>

            {/* Center spin trigger button */}
            <TouchableOpacity
              style={[styles.spinBtn, spinning && styles.spinBtnDisabled]}
              onPress={handleSpin}
              disabled={spinning}
            >
              <Text style={styles.spinBtnText}>{spinning ? 'SPIN' : 'QUAY'}</Text>
            </TouchableOpacity>
          </View>

          {/* Spin History */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Lịch sử trúng thưởng</Text>
            {history.length === 0 ? (
              <Text style={styles.noHistoryText}>Bạn chưa tham gia quay thưởng lần nào.</Text>
            ) : (
              history.map((h, index) => (
                <View key={h.id || index} style={styles.historyRow}>
                  <View style={styles.historyLeft}>
                    <Feather name="award" size={16} color={COLORS.primary} />
                    <Text style={styles.historyItemName}>{h.ten_qua || h.ten}</Text>
                  </View>
                  <Text style={styles.historyDate}>
                    {new Date(h.created_at).toLocaleDateString('vi-VN')}
                  </Text>
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
  checkinCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.lg,
    marginBottom: SPACING.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  checkinHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: SPACING.lg,
  },
  checkinTitle: {
    fontSize: 14,
    fontWeight: '750',
    color: COLORS.textPrimary,
    marginBottom: 2,
  },
  checkinSub: {
    fontSize: 11,
    color: COLORS.textSecondary,
  },
  checkinActionBtn: {
    backgroundColor: '#fbbf24',
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: RADIUS.md,
  },
  checkinActionBtnDisabled: {
    backgroundColor: COLORS.border,
  },
  checkinActionText: {
    color: '#0f172a',
    fontSize: 12,
    fontWeight: '700',
  },
  checkinGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 4,
  },
  dayItem: {
    flex: 1,
    backgroundColor: COLORS.background,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingVertical: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dayItemChecked: {
    backgroundColor: 'rgba(16, 185, 129, 0.05)',
    borderColor: '#10b981',
  },
  dayItemCurrent: {
    backgroundColor: 'rgba(251, 191, 36, 0.1)',
    borderColor: '#fbbf24',
  },
  dayLabel: {
    fontSize: 10,
    fontWeight: '600',
    color: COLORS.textSecondary,
    marginBottom: 6,
  },
  dayBadge: {
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: 'rgba(30, 41, 59, 0.4)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  dayXuText: {
    fontSize: 9,
    color: COLORS.textTertiary,
    fontWeight: '700',
  },
  dayXuTextCurrent: {
    color: '#fbbf24',
  },
  claimDailyCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
    padding: SPACING.md,
    marginBottom: SPACING.lg,
  },
  claimDailyText: {
    flex: 1,
    color: COLORS.white,
    fontWeight: '700',
    fontSize: 13,
    marginLeft: SPACING.md,
  },
  wheelSection: {
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: SPACING.xl,
    position: 'relative',
    height: 320,
  },
  pointerContainer: {
    position: 'absolute',
    top: -12,
    zIndex: 10,
    alignItems: 'center',
  },
  pointerIcon: {
    transform: [{ rotate: '180deg' }],
  },
  wheelContainer: {
    width: 280,
    height: 280,
    borderRadius: 140,
    backgroundColor: COLORS.surface,
    borderWidth: 8,
    borderColor: '#4338ca', // Indigo neon outer border
    justifyContent: 'center',
    alignItems: 'center',
    overflow: 'hidden',
    position: 'relative',
    ...Platform.select({
      ios: {
        shadowColor: '#6366f1',
        shadowOffset: { width: 0, height: 0 },
        shadowOpacity: 0.5,
        shadowRadius: 15,
      },
      android: {
        elevation: 8,
      },
    }),
  },
  balancesCard: {
    flexDirection: 'row',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.md,
    padding: SPACING.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: SPACING.lg,
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  balanceItem: {
    flex: 1,
    alignItems: 'center',
  },
  balanceLabel: {
    fontSize: 12,
    color: COLORS.textSecondary,
    marginBottom: 4,
    fontWeight: '600',
  },
  balanceValue: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.textPrimary,
  },
  balanceDivider: {
    width: 1,
    height: 30,
    backgroundColor: COLORS.border,
  },
  wheelSegmentContainer: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    justifyContent: 'flex-start',
    alignItems: 'center',
  },
  wheelSegmentTriangle: {
    width: 0,
    height: 0,
    borderLeftColor: 'transparent',
    borderRightColor: 'transparent',
    borderStyle: 'solid',
  },
  wheelPrizeText: {
    position: 'absolute',
    top: 15,
    width: 75,
    textAlign: 'center',
    fontSize: 9,
    fontWeight: '850',
  },
  spinBtn: {
    position: 'absolute',
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: '#f43f5e',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 3,
    borderColor: '#fff',
    zIndex: 15,
    elevation: 10,
  },
  spinBtnDisabled: {
    backgroundColor: '#94a3b8',
  },
  spinBtnText: {
    color: '#fff',
    fontWeight: '900',
    fontSize: 12,
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
    marginBottom: SPACING.lg,
    borderLeftWidth: 3,
    borderLeftColor: COLORS.primary,
    paddingLeft: SPACING.sm,
  },
  noHistoryText: {
    fontSize: 13,
    color: COLORS.textTertiary,
    textAlign: 'center',
    marginVertical: SPACING.md,
  },
  historyRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: SPACING.md,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
  },
  historyLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: SPACING.md,
  },
  historyItemName: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.textPrimary,
  },
  historyDate: {
    fontSize: 11,
    color: COLORS.textTertiary,
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
});
