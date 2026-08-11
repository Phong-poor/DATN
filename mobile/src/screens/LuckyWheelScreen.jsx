import React, { useCallback, useEffect, useState, useRef } from 'react';
import { StyleSheet, Text, View, TouchableOpacity, ActivityIndicator, Animated, Easing, ScrollView, Platform, Modal, Vibration } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons, Feather } from '@expo/vector-icons';
import { COLORS, RADIUS, SPACING, TYPOGRAPHY } from '../utils/theme';
import api from '../services/api';
import useAuthStore from '../store/useAuthStore';
import { showAlert } from '../utils/alert';

const normalizeTicketCount = (value) => Math.max(0, Math.min(1, Number(value) || 0));

export default function LuckyWheelScreen({ navigation }) {
  const token = useAuthStore((state) => state.token);
  const checkSession = useAuthStore((state) => state.checkSession);

  const [prizes, setPrizes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [spinning, setSpinning] = useState(false);
  const [history, setHistory] = useState([]);
  const [hasSpunToday, setHasSpunToday] = useState(false);
  const [winningPrize, setWinningPrize] = useState(null);
  const [showResult, setShowResult] = useState(false);
  const [showHistory, setShowHistory] = useState(false);

  // Shopee Check-in and Balances States
  const [checkinStatus, setCheckinStatus] = useState(null);
  const [userTickets, setUserTickets] = useState(0);
  const [userCoins, setUserCoins] = useState(0);

  const spinValue = useRef(new Animated.Value(0)).current;
  const currentRotation = useRef(0);
  const entranceValue = useRef(new Animated.Value(0)).current;
  const pulseValue = useRef(new Animated.Value(0)).current;
  const ambientValue = useRef(new Animated.Value(0)).current;
  const pointerValue = useRef(new Animated.Value(0)).current;
  const resultScale = useRef(new Animated.Value(0.72)).current;
  const confettiValue = useRef(new Animated.Value(0)).current;
  const pointerLoopRef = useRef(null);

  useEffect(() => {
    entranceValue.setValue(0);
    Animated.timing(entranceValue, {
      toValue: 1,
      duration: 650,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: Platform.OS !== 'web',
    }).start();

    const pulseLoop = Animated.loop(
      Animated.sequence([
        Animated.timing(pulseValue, { toValue: 1, duration: 900, easing: Easing.inOut(Easing.quad), useNativeDriver: Platform.OS !== 'web' }),
        Animated.timing(pulseValue, { toValue: 0, duration: 900, easing: Easing.inOut(Easing.quad), useNativeDriver: Platform.OS !== 'web' }),
      ])
    );
    const ambientLoop = Animated.loop(
      Animated.timing(ambientValue, {
        toValue: 1,
        duration: 7000,
        easing: Easing.linear,
        useNativeDriver: Platform.OS !== 'web',
      })
    );

    pulseLoop.start();
    ambientLoop.start();

    return () => {
      pulseLoop.stop();
      ambientLoop.stop();
      pointerLoopRef.current?.stop();
    };
  }, [ambientValue, entranceValue, pulseValue]);

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

  // Refresh balances whenever this screen becomes active so persisted user data cannot go stale.
  useFocusEffect(useCallback(() => {
    if (!token) return undefined;

    let isActive = true;

    const loadData = async () => {
      try {
        setLoading(true);
        // Load latest user details (tickets/coins)
        const [prizeRes, historyRes, sessionUser, checkinRes] = await Promise.all([
          api.get('/vong-quay/prizes').catch(() => null),
          api.get('/vong-quay/lich-su').catch(() => ({ data: { data: [] } })),
          checkSession(),
          api.get('/diem-danh/status').catch(() => null),
        ]);

        if (!isActive) return;

        if (prizeRes?.data?.success) {
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

        if (sessionUser) {
          setUserTickets(normalizeTicketCount(sessionUser.luot_quay));
          setUserCoins(Number(sessionUser.xu ?? 0));
        }

        if (checkinRes?.data?.success) {
          setCheckinStatus(checkinRes.data);
        }
      } catch (err) {
        console.log(err);
      } finally {
        if (isActive) {
          setLoading(false);
        }
      }
    };

    loadData();

    return () => {
      isActive = false;
    };
  }, [checkSession, token]));

  const handleClaimDailyFreeSpin = async () => {
    try {
      const res = await api.post('/vong-quay/nhan-luot');
      showAlert('Thành công', res.data?.message || 'Đã nhận lượt quay miễn phí hôm nay!');
      if (res.data?.tickets !== undefined) {
        setUserTickets(normalizeTicketCount(res.data.tickets));
      }
      await checkSession().catch(() => {});
    } catch (err) {
      if (err.response?.data?.tickets !== undefined) {
        setUserTickets(normalizeTicketCount(err.response.data.tickets));
      }
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

  const revealPrize = (prize) => {
    setWinningPrize(prize);
    setShowResult(true);
    resultScale.setValue(0.72);
    confettiValue.setValue(0);
    Vibration.vibrate([0, 70, 50, 110]);

    Animated.parallel([
      Animated.spring(resultScale, {
        toValue: 1,
        friction: 6,
        tension: 70,
        useNativeDriver: Platform.OS !== 'web',
      }),
      Animated.timing(confettiValue, {
        toValue: 1,
        duration: 1300,
        easing: Easing.out(Easing.cubic),
        useNativeDriver: Platform.OS !== 'web',
      }),
    ]).start();
  };

  const handleSpin = async () => {
    if (spinning || prizes.length === 0 || userTickets <= 0) return;

    setSpinning(true);
    pointerValue.setValue(0);
    pointerLoopRef.current = Animated.loop(
      Animated.sequence([
        Animated.timing(pointerValue, { toValue: 1, duration: 105, useNativeDriver: Platform.OS !== 'web' }),
        Animated.timing(pointerValue, { toValue: 0, duration: 105, useNativeDriver: Platform.OS !== 'web' }),
      ]),
      { iterations: 24 }
    );
    pointerLoopRef.current.start();

    try {
      const res = await api.post('/vong-quay/quay');
      if (res.data?.success) {
        const winningPrize = res.data.prize;
        const prizeIndex = prizes.findIndex(p => p.id === winningPrize.id);

        if (prizeIndex === -1) {
          pointerLoopRef.current?.stop();
          pointerValue.setValue(0);
          revealPrize(winningPrize);
          setSpinning(false);
          if (res.data?.tickets !== undefined) {
            setUserTickets(normalizeTicketCount(res.data.tickets));
          }
          await checkSession().catch(() => {});
          return;
        }

        const segmentAngle = 360 / prizes.length;
        const normalizedCurrent = ((currentRotation.current % 360) + 360) % 360;
        const targetAngle = (360 - (prizeIndex * segmentAngle)) % 360;
        const landingDelta = (targetAngle - normalizedCurrent + 360) % 360;
        const totalSpinDegrees = currentRotation.current + (360 * 7) + landingDelta;
        
        Animated.timing(spinValue, {
          toValue: totalSpinDegrees,
          duration: 5200,
          easing: Easing.bezier(0.12, 0.72, 0.12, 1),
          useNativeDriver: Platform.OS !== 'web',
        }).start(async ({ finished }) => {
          if (!finished) return;
          pointerLoopRef.current?.stop();
          pointerValue.setValue(0);
          currentRotation.current = totalSpinDegrees;
          setSpinning(false);
          setHasSpunToday(true);
          revealPrize(winningPrize);
          
          if (res.data?.tickets !== undefined) {
            setUserTickets(normalizeTicketCount(res.data.tickets));
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
      } else {
        pointerLoopRef.current?.stop();
        pointerValue.setValue(0);
        setSpinning(false);
        showAlert('Thông báo', res.data?.message || 'Không thể thực hiện lượt quay lúc này.');
      }
    } catch (err) {
      pointerLoopRef.current?.stop();
      pointerValue.setValue(0);
      if (err.response?.data?.tickets !== undefined) {
        setUserTickets(normalizeTicketCount(err.response.data.tickets));
      }
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
          <TouchableOpacity style={styles.authPromptBtn} onPress={() => navigation.navigate('Main', { screen: 'Tài khoản' })}>
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
  const wheelEntranceStyle = {
    opacity: entranceValue,
    transform: [
      { translateY: entranceValue.interpolate({ inputRange: [0, 1], outputRange: [24, 0] }) },
      { scale: entranceValue.interpolate({ inputRange: [0, 1], outputRange: [0.94, 1] }) },
    ],
  };
  const pulseScale = pulseValue.interpolate({ inputRange: [0, 1], outputRange: [1, 1.09] });
  const pulseOpacity = pulseValue.interpolate({ inputRange: [0, 1], outputRange: [0.42, 0.08] });
  const ambientRotation = ambientValue.interpolate({ inputRange: [0, 1], outputRange: ['0deg', '360deg'] });
  const pointerTranslateY = pointerValue.interpolate({ inputRange: [0, 1], outputRange: [0, 7] });

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <View style={styles.gameHeader}>
        <TouchableOpacity accessibilityRole="button" style={styles.headerIconBtn} onPress={() => navigation.goBack()}>
          <Ionicons name="close" size={22} color="#f8fafc" />
        </TouchableOpacity>
        <View style={styles.headerTitleWrap}>
          <Text style={styles.headerEyebrow}>NEXTGEN REWARDS</Text>
          <Text style={styles.gameHeaderTitle}>Vòng quay may mắn</Text>
        </View>
        <TouchableOpacity accessibilityRole="button" style={styles.headerIconBtn} onPress={() => setShowHistory(true)}>
          <Ionicons name="time-outline" size={21} color="#f8fafc" />
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerLoader}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Đang tải vòng quay...</Text>
        </View>
      ) : (
        <View style={styles.gameScreen}>
          <View style={styles.compactStats}>
            <View style={styles.compactStatItem}>
              <View style={[styles.statIcon, styles.ticketStatIcon]}>
                <Ionicons name="ticket" size={16} color="#fbbf24" />
              </View>
              <View>
                <Text style={styles.compactStatLabel}>Lượt quay</Text>
                <Text style={styles.compactStatValue}>{userTickets}</Text>
              </View>
            </View>
            <View style={styles.compactStatDivider} />
            <View style={styles.compactStatItem}>
              <View style={[styles.statIcon, styles.coinStatIcon]}>
                <Ionicons name="logo-bitcoin" size={17} color="#fde68a" />
              </View>
              <View>
                <Text style={styles.compactStatLabel}>Xu hiện có</Text>
                <Text style={styles.compactStatValue}>{userCoins.toLocaleString('vi-VN')}</Text>
              </View>
            </View>
            <View style={styles.compactStatDivider} />
            <View style={styles.compactStatItem}>
              <View style={[styles.statIcon, styles.streakStatIcon]}>
                <Ionicons name="flame" size={17} color="#fb7185" />
              </View>
              <View>
                <Text style={styles.compactStatLabel}>Chuỗi ngày</Text>
                <Text style={styles.compactStatValue}>{checkinStatus?.current_streak || 0}</Text>
              </View>
            </View>
          </View>

          <Animated.View style={[styles.wheelWindow, wheelEntranceStyle]}>
            <View style={styles.windowTopLine}>
              <View>
                <Text style={styles.wheelEyebrow}>GIẢI THƯỞNG HÔM NAY</Text>
                <Text style={styles.wheelHeading}>Chạm để thử vận may</Text>
              </View>
              <View style={[styles.liveDot, spinning && styles.liveDotActive]} />
            </View>

            <View style={styles.wheelStageCompact}>
              <Animated.View style={[styles.ambientOrbit, { transform: [{ rotate: ambientRotation }] }]}>
                <View style={[styles.ambientSpark, styles.ambientSparkOne]} />
                <View style={[styles.ambientSpark, styles.ambientSparkTwo]} />
                <View style={[styles.ambientSpark, styles.ambientSparkThree]} />
              </Animated.View>

              <Animated.View style={[styles.pointerContainer, { transform: [{ translateY: pointerTranslateY }] }]}>
                <View style={styles.pointerCap}>
                  <Ionicons name="diamond" size={17} color="#fff7d6" />
                </View>
                <Ionicons name="triangle" size={38} color="#fbbf24" style={styles.pointerIcon} />
              </Animated.View>

              <View style={styles.wheelFrame}>
                {Array.from({ length: 16 }).map((_, index) => {
                  const angle = (index / 16) * Math.PI * 2;
                  return (
                    <View
                      key={`bulb-${index}`}
                      style={[
                        styles.wheelBulb,
                        {
                          left: 140 + Math.cos(angle) * 133,
                          top: 140 + Math.sin(angle) * 133,
                          opacity: index % 2 === 0 ? 1 : 0.55,
                        },
                      ]}
                    />
                  );
                })}

                <Animated.View style={[styles.wheelContainer, { transform: [{ rotate: spinRotation }] }]}>
                  {prizes.map((prize, idx) => {
                    const angle = 360 / prizes.length;
                    const rotation = idx * angle;
                    const radius = 124;
                    const segmentRad = Math.PI / prizes.length;
                    const borderWidth = radius * Math.tan(segmentRad);

                    return (
                      <View
                        key={prize.id || `prize-${idx}`}
                        style={[styles.wheelSegmentContainer, { transform: [{ rotate: `${rotation}deg` }] }]}
                      >
                        <View
                          style={[
                            styles.wheelSegmentTriangle,
                            {
                              borderTopColor: prize.mau_sac || (idx % 2 === 0 ? '#7c3aed' : '#ec4899'),
                              borderLeftWidth: borderWidth,
                              borderRightWidth: borderWidth,
                              borderTopWidth: radius,
                            },
                          ]}
                        />
                        <View style={styles.prizeLabelWrap}>
                          <Ionicons name={idx % 2 === 0 ? 'gift' : 'sparkles'} size={14} color={prize.mau_chu || '#fff'} />
                          <Text numberOfLines={2} style={[styles.wheelPrizeText, { color: prize.mau_chu || '#fff' }]}>
                            {prize.ten}
                          </Text>
                        </View>
                      </View>
                    );
                  })}
                  <View style={styles.wheelInnerRing} />
                </Animated.View>

                <Animated.View style={[styles.spinPulse, { opacity: pulseOpacity, transform: [{ scale: pulseScale }] }]} />
                <Animated.View style={[styles.spinButtonShell, { transform: [{ scale: spinning ? 0.94 : pulseScale }] }]}>
                  <TouchableOpacity
                    style={[styles.spinBtn, (spinning || userTickets <= 0) && styles.spinBtnDisabled]}
                    onPress={handleSpin}
                    disabled={spinning || userTickets <= 0 || prizes.length === 0}
                    activeOpacity={0.86}
                  >
                    <Ionicons name={spinning ? 'sync' : 'sparkles'} size={18} color="#fff" />
                    <Text style={styles.spinBtnText}>{spinning ? 'ĐANG QUAY' : 'QUAY'}</Text>
                  </TouchableOpacity>
                </Animated.View>
              </View>
            </View>

            <Text style={styles.spinHint}>
              {spinning
                ? 'Vòng quay đang tìm phần thưởng của bạn...'
                : userTickets > 0
                  ? 'Chạm QUAY để sử dụng 1 lượt'
                  : 'Bạn đã hết lượt quay — nhận lượt miễn phí ở phía trên'}
            </Text>
          </Animated.View>

          <View style={styles.actionDock}>
            <TouchableOpacity
              style={[styles.dockAction, checkinStatus?.checked_today && styles.dockActionDone]}
              onPress={handleCheckIn}
              disabled={checkinStatus?.checked_today}
              activeOpacity={0.85}
            >
              <View style={styles.dockActionIcon}>
                <Ionicons name={checkinStatus?.checked_today ? 'checkmark' : 'calendar-outline'} size={20} color="#fff" />
              </View>
              <View style={styles.dockActionCopy}>
                <Text style={styles.dockActionTitle}>{checkinStatus?.checked_today ? 'Đã điểm danh' : 'Điểm danh hôm nay'}</Text>
                <Text style={styles.dockActionSub}>{checkinStatus?.checked_today ? 'Hẹn bạn vào ngày mai' : 'Nhận Xu miễn phí'}</Text>
              </View>
            </TouchableOpacity>

            <TouchableOpacity style={[styles.dockAction, styles.dockActionGift]} onPress={handleClaimDailyFreeSpin} activeOpacity={0.85}>
              <View style={[styles.dockActionIcon, styles.dockGiftIcon]}>
                <Ionicons name="gift-outline" size={20} color="#2e1065" />
              </View>
              <View style={styles.dockActionCopy}>
                <Text style={styles.dockActionTitle}>Nhận lượt miễn phí</Text>
                <Text style={styles.dockActionSub}>Mỗi ngày một lượt</Text>
              </View>
            </TouchableOpacity>
          </View>
        </View>
      )}

      <Modal visible={showHistory} transparent animationType="slide" onRequestClose={() => setShowHistory(false)}>
        <View style={styles.sheetOverlay}>
          <TouchableOpacity style={styles.sheetDismissArea} activeOpacity={1} onPress={() => setShowHistory(false)} />
          <View style={styles.historySheet}>
            <View style={styles.sheetHandle} />
            <View style={styles.sheetHeader}>
              <View>
                <Text style={styles.sheetEyebrow}>TÀI KHOẢN CỦA BẠN</Text>
                <Text style={styles.sheetTitle}>Lịch sử trúng thưởng</Text>
              </View>
              <TouchableOpacity style={styles.sheetCloseBtn} onPress={() => setShowHistory(false)}>
                <Ionicons name="close" size={20} color={COLORS.textPrimary} />
              </TouchableOpacity>
            </View>
            <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.historyList}>
              {history.length === 0 ? (
                <View style={styles.emptyHistory}>
                  <Ionicons name="gift-outline" size={32} color={COLORS.textTertiary} />
                  <Text style={styles.noHistoryText}>Bạn chưa có phần thưởng nào.</Text>
                </View>
              ) : history.map((h, index) => (
                <View key={h.id || index} style={styles.historyRow}>
                  <View style={styles.historyAwardIcon}>
                    <Feather name="award" size={17} color="#f59e0b" />
                  </View>
                  <View style={styles.historyCopy}>
                    <Text style={styles.historyItemName}>{h.ten_qua || h.ten}</Text>
                    <Text style={styles.historyDate}>{new Date(h.created_at).toLocaleDateString('vi-VN')}</Text>
                  </View>
                </View>
              ))}
            </ScrollView>
          </View>
        </View>
      </Modal>

      <Modal
        visible={showResult}
        transparent
        animationType="fade"
        statusBarTranslucent
        onRequestClose={() => setShowResult(false)}
      >
        <View style={styles.resultOverlay}>
          <View pointerEvents="none" style={styles.confettiLayer}>
            {Array.from({ length: 18 }).map((_, index) => {
              const spread = ((index % 9) - 4) * 35;
              const fall = 185 + (index % 4) * 28;
              const colors = ['#fbbf24', '#ec4899', '#8b5cf6', '#22d3ee', '#34d399'];
              return (
                <Animated.View
                  key={`confetti-${index}`}
                  style={[
                    styles.confettiPiece,
                    {
                      backgroundColor: colors[index % colors.length],
                      opacity: confettiValue.interpolate({
                        inputRange: [0, 0.12, 0.78, 1],
                        outputRange: [0, 1, 1, 0],
                      }),
                      transform: [
                        {
                          translateX: confettiValue.interpolate({
                            inputRange: [0, 1],
                            outputRange: [0, spread],
                          }),
                        },
                        {
                          translateY: confettiValue.interpolate({
                            inputRange: [0, 1],
                            outputRange: [0, fall],
                          }),
                        },
                        {
                          rotate: confettiValue.interpolate({
                            inputRange: [0, 1],
                            outputRange: ['0deg', `${180 + index * 55}deg`],
                          }),
                        },
                      ],
                    },
                  ]}
                />
              );
            })}
          </View>

          <Animated.View style={[styles.resultCard, { transform: [{ scale: resultScale }] }]}>
            <View style={styles.resultGlow} />
            <View style={styles.resultIcon}>
              <Ionicons name="trophy" size={34} color="#fff7d6" />
            </View>
            <Text style={styles.resultEyebrow}>CHÚC MỪNG</Text>
            <Text style={styles.resultTitle}>Bạn đã trúng</Text>
            <View style={styles.resultPrizeBox}>
              <Ionicons name="gift" size={21} color="#fbbf24" />
              <Text style={styles.resultPrize}>{winningPrize?.ten || 'Phần thưởng may mắn'}</Text>
            </View>
            <Text style={styles.resultDescription}>
              Phần thưởng đã được ghi nhận vào tài khoản của bạn.
            </Text>
            <TouchableOpacity
              accessibilityRole="button"
              style={styles.resultButton}
              onPress={() => setShowResult(false)}
              activeOpacity={0.86}
            >
              <Text style={styles.resultButtonText}>Tuyệt vời</Text>
              <Ionicons name="arrow-forward" size={17} color="#1b103d" />
            </TouchableOpacity>
          </Animated.View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#090b18',
  },
  gameHeader: {
    height: 62,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 14,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255, 255, 255, 0.07)',
    backgroundColor: '#0d1020',
  },
  headerIconBtn: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.07)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
  },
  headerTitleWrap: {
    flex: 1,
    alignItems: 'center',
  },
  headerEyebrow: {
    color: '#a78bfa',
    fontSize: 8,
    fontWeight: '900',
    letterSpacing: 1.5,
    marginBottom: 2,
  },
  gameHeaderTitle: {
    color: '#f8fafc',
    fontSize: 16,
    fontWeight: '800',
  },
  gameScreen: {
    flex: 1,
    paddingHorizontal: 12,
    paddingTop: 10,
    paddingBottom: 10,
    gap: 10,
  },
  compactStats: {
    minHeight: 60,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#12162a',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    paddingHorizontal: 10,
    paddingVertical: 9,
  },
  compactStatItem: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
  },
  compactStatDivider: {
    width: 1,
    height: 28,
    marginHorizontal: 7,
    backgroundColor: 'rgba(255, 255, 255, 0.09)',
  },
  statIcon: {
    width: 30,
    height: 30,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  ticketStatIcon: { backgroundColor: 'rgba(251, 191, 36, 0.13)' },
  coinStatIcon: { backgroundColor: 'rgba(245, 158, 11, 0.13)' },
  streakStatIcon: { backgroundColor: 'rgba(244, 63, 94, 0.13)' },
  compactStatLabel: {
    color: '#7f849d',
    fontSize: 8.5,
    fontWeight: '700',
    marginBottom: 2,
  },
  compactStatValue: {
    color: '#f8fafc',
    fontSize: 14,
    fontWeight: '900',
  },
  wheelWindow: {
    flex: 1,
    minHeight: 365,
    alignItems: 'center',
    backgroundColor: '#11142b',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: 'rgba(139, 92, 246, 0.28)',
    paddingHorizontal: 12,
    paddingTop: 14,
    paddingBottom: 10,
    overflow: 'hidden',
  },
  windowTopLine: {
    width: '100%',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 4,
  },
  liveDot: {
    width: 9,
    height: 9,
    borderRadius: 5,
    backgroundColor: '#334155',
  },
  liveDotActive: {
    backgroundColor: '#34d399',
    shadowColor: '#34d399',
    shadowOpacity: 1,
    shadowRadius: 7,
    elevation: 5,
  },
  wheelStageCompact: {
    flex: 1,
    minHeight: 300,
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },
  actionDock: {
    minHeight: 76,
    flexDirection: 'row',
    gap: 9,
  },
  dockAction: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#7c3aed',
    borderRadius: 18,
    paddingHorizontal: 11,
    paddingVertical: 11,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.12)',
  },
  dockActionDone: {
    backgroundColor: '#253047',
  },
  dockActionGift: {
    backgroundColor: '#d6b55b',
  },
  dockActionIcon: {
    width: 34,
    height: 34,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 8,
    backgroundColor: 'rgba(255, 255, 255, 0.16)',
  },
  dockGiftIcon: {
    backgroundColor: 'rgba(255, 255, 255, 0.3)',
  },
  dockActionCopy: { flex: 1 },
  dockActionTitle: {
    color: '#fff',
    fontSize: 11,
    lineHeight: 14,
    fontWeight: '900',
  },
  dockActionSub: {
    color: 'rgba(255, 255, 255, 0.68)',
    fontSize: 8.5,
    marginTop: 3,
  },
  sheetOverlay: {
    flex: 1,
    justifyContent: 'flex-end',
    backgroundColor: 'rgba(2, 4, 12, 0.62)',
  },
  sheetDismissArea: { flex: 1 },
  historySheet: {
    maxHeight: '68%',
    minHeight: 340,
    backgroundColor: COLORS.surface,
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingHorizontal: 18,
    paddingBottom: 24,
  },
  sheetHandle: {
    width: 42,
    height: 4,
    borderRadius: 2,
    alignSelf: 'center',
    backgroundColor: COLORS.border,
    marginTop: 10,
    marginBottom: 14,
  },
  sheetHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  sheetEyebrow: {
    color: COLORS.primary,
    fontSize: 9,
    fontWeight: '900',
    letterSpacing: 1.2,
    marginBottom: 3,
  },
  sheetTitle: {
    color: COLORS.textPrimary,
    fontSize: 20,
    fontWeight: '900',
  },
  sheetCloseBtn: {
    width: 38,
    height: 38,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.background,
  },
  historyList: { paddingBottom: 20 },
  emptyHistory: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 54,
  },
  historyAwardIcon: {
    width: 38,
    height: 38,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 11,
    backgroundColor: 'rgba(245, 158, 11, 0.1)',
  },
  historyCopy: {
    flex: 1,
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
    paddingBottom: 110,
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
  wheelExperience: {
    backgroundColor: '#101226',
    borderRadius: 26,
    borderWidth: 1,
    borderColor: 'rgba(167, 139, 250, 0.3)',
    paddingHorizontal: 16,
    paddingTop: 18,
    paddingBottom: 16,
    marginBottom: SPACING.lg,
    overflow: 'hidden',
    ...Platform.select({
      ios: {
        shadowColor: '#7c3aed',
        shadowOffset: { width: 0, height: 12 },
        shadowOpacity: 0.2,
        shadowRadius: 20,
      },
      android: { elevation: 7 },
    }),
  },
  wheelHeadingRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    zIndex: 2,
  },
  wheelEyebrow: {
    color: '#fbbf24',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 1.4,
    marginBottom: 4,
  },
  wheelHeading: {
    color: '#f8fafc',
    fontSize: 17,
    fontWeight: '800',
  },
  ticketPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    backgroundColor: 'rgba(251, 191, 36, 0.12)',
    borderWidth: 1,
    borderColor: 'rgba(251, 191, 36, 0.38)',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  ticketPillText: {
    color: '#fde68a',
    fontSize: 12,
    fontWeight: '800',
  },
  wheelStage: {
    height: 352,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 2,
  },
  ambientOrbit: {
    position: 'absolute',
    width: 306,
    height: 306,
    borderRadius: 153,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: 'rgba(167, 139, 250, 0.28)',
  },
  ambientSpark: {
    position: 'absolute',
    width: 7,
    height: 7,
    borderRadius: 4,
    backgroundColor: '#fbbf24',
    shadowColor: '#fbbf24',
    shadowOpacity: 0.9,
    shadowRadius: 7,
    elevation: 5,
  },
  ambientSparkOne: { top: 24, left: 60 },
  ambientSparkTwo: { right: 2, top: 158, backgroundColor: '#22d3ee' },
  ambientSparkThree: { bottom: 28, left: 52, backgroundColor: '#ec4899' },
  wheelFrame: {
    width: 292,
    height: 292,
    borderRadius: 146,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#21174b',
    borderWidth: 6,
    borderColor: '#8b5cf6',
    shadowColor: '#8b5cf6',
    shadowOpacity: 0.55,
    shadowRadius: 18,
    elevation: 12,
  },
  pointerContainer: {
    position: 'absolute',
    top: 2,
    zIndex: 30,
    alignItems: 'center',
  },
  pointerCap: {
    width: 38,
    height: 29,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#6d28d9',
    borderWidth: 2,
    borderColor: '#fde68a',
    zIndex: 2,
    elevation: 8,
  },
  pointerIcon: {
    transform: [{ rotate: '180deg' }],
    marginTop: -8,
    zIndex: 1,
  },
  wheelBulb: {
    position: 'absolute',
    width: 8,
    height: 8,
    marginLeft: -4,
    marginTop: -4,
    borderRadius: 4,
    backgroundColor: '#fff4b8',
    shadowColor: '#fbbf24',
    shadowOpacity: 1,
    shadowRadius: 5,
    elevation: 4,
  },
  wheelContainer: {
    width: 264,
    height: 264,
    borderRadius: 132,
    backgroundColor: '#312e81',
    borderWidth: 4,
    borderColor: '#f4d06f',
    justifyContent: 'center',
    alignItems: 'center',
    overflow: 'hidden',
    position: 'absolute',
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
    textAlign: 'center',
    fontSize: 8.5,
    lineHeight: 11,
    fontWeight: '800',
    marginTop: 3,
  },
  prizeLabelWrap: {
    position: 'absolute',
    top: 13,
    width: 82,
    alignItems: 'center',
  },
  wheelInnerRing: {
    position: 'absolute',
    top: 90,
    left: 90,
    width: 84,
    height: 84,
    borderRadius: 42,
    backgroundColor: '#24164f',
    borderWidth: 3,
    borderColor: '#fde68a',
  },
  spinPulse: {
    position: 'absolute',
    width: 92,
    height: 92,
    borderRadius: 46,
    backgroundColor: '#fbbf24',
  },
  spinButtonShell: {
    width: 72,
    height: 72,
    zIndex: 20,
  },
  spinBtn: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: '#e11d48',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 3,
    borderColor: '#fff2b2',
    elevation: 10,
  },
  spinBtnDisabled: {
    backgroundColor: '#94a3b8',
  },
  spinBtnText: {
    color: '#fff',
    fontWeight: '900',
    fontSize: 10,
    marginTop: 1,
    letterSpacing: 0.5,
  },
  spinHint: {
    color: '#c4b5fd',
    fontSize: 11.5,
    lineHeight: 17,
    textAlign: 'center',
    paddingHorizontal: 18,
    marginTop: -2,
  },
  resultOverlay: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    backgroundColor: 'rgba(4, 4, 16, 0.82)',
  },
  confettiLayer: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center',
  },
  confettiPiece: {
    position: 'absolute',
    top: '25%',
    width: 9,
    height: 18,
    borderRadius: 3,
  },
  resultCard: {
    width: '100%',
    maxWidth: 370,
    alignItems: 'center',
    overflow: 'hidden',
    backgroundColor: '#17142f',
    borderRadius: 28,
    borderWidth: 1,
    borderColor: 'rgba(251, 191, 36, 0.55)',
    paddingHorizontal: 22,
    paddingTop: 30,
    paddingBottom: 22,
    shadowColor: '#8b5cf6',
    shadowOpacity: 0.48,
    shadowRadius: 26,
    elevation: 18,
  },
  resultGlow: {
    position: 'absolute',
    top: -100,
    width: 240,
    height: 190,
    borderRadius: 120,
    backgroundColor: 'rgba(124, 58, 237, 0.34)',
  },
  resultIcon: {
    width: 68,
    height: 68,
    borderRadius: 34,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#7c3aed',
    borderWidth: 2,
    borderColor: '#fde68a',
    marginBottom: 16,
    elevation: 9,
  },
  resultEyebrow: {
    color: '#fbbf24',
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 2.2,
    marginBottom: 7,
  },
  resultTitle: {
    color: '#fff',
    fontSize: 25,
    fontWeight: '900',
    marginBottom: 17,
  },
  resultPrizeBox: {
    width: '100%',
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 9,
    backgroundColor: 'rgba(251, 191, 36, 0.1)',
    borderWidth: 1,
    borderColor: 'rgba(251, 191, 36, 0.28)',
    borderRadius: 16,
    paddingVertical: 15,
    paddingHorizontal: 14,
  },
  resultPrize: {
    flexShrink: 1,
    color: '#fde68a',
    fontSize: 17,
    fontWeight: '900',
    textAlign: 'center',
  },
  resultDescription: {
    color: '#b8b5cf',
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
    marginTop: 14,
    marginBottom: 20,
  },
  resultButton: {
    width: '100%',
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 8,
    backgroundColor: '#fbbf24',
    borderRadius: 15,
    paddingVertical: 14,
  },
  resultButtonText: {
    color: '#1b103d',
    fontSize: 14,
    fontWeight: '900',
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
    width: '100%',
    flexDirection: 'row',
    paddingVertical: 12,
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
    marginTop: 4,
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
