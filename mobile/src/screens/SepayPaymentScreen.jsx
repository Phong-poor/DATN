import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Image, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api from '../services/api';
import { COLORS, RADIUS, SPACING } from '../utils/theme';

export default function SepayPaymentScreen({ route, navigation }) {
  const { payment, order } = route.params || {};
  const orderId = payment?.order_id || order?.id_dathang || order?.id;
  const [error, setError] = useState('');

  useEffect(() => {
    if (!orderId) return undefined;
    let active = true;
    const checkStatus = async () => {
      try {
        const response = await api.get(`/orders/${orderId}/sepay-status`);
        if (active && response.data?.payment_status === 'paid') {
          navigation.replace('OrderSuccess', { order: { ...order, id_dathang: orderId, trang_thai_thanh_toan: 'paid' }, paymentConfirmed: true });
        }
      } catch (err) {
        if (active) setError(err.response?.data?.message || 'Không kiểm tra được trạng thái thanh toán.');
      }
    };
    checkStatus();
    const timer = setInterval(checkStatus, 3000);
    return () => {
      active = false;
      clearInterval(timer);
    };
  }, [navigation, order, orderId]);

  const formatMoney = (value) => `${Number(value || 0).toLocaleString('vi-VN')}đ`;

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.header}>
          <View style={styles.brand}><Text style={styles.brandText}>SePay</Text></View>
          <View style={{ flex: 1 }}>
            <Text style={styles.title}>Quét mã để thanh toán</Text>
            <Text style={styles.subtitle}>Hệ thống tự xác nhận sau khi ngân hàng ghi nhận tiền.</Text>
          </View>
        </View>

        {payment?.qr_url ? <Image source={{ uri: payment.qr_url }} style={styles.qr} resizeMode="contain" /> : null}

        <View style={styles.details}>
          <Row label="Số tiền" value={formatMoney(payment?.amount)} accent />
          <Row label="Ngân hàng" value={payment?.bank} />
          <Row label="Số tài khoản" value={payment?.account_number} selectable />
          {payment?.account_name ? <Row label="Chủ tài khoản" value={payment.account_name} /> : null}
          <Row label="Nội dung chuyển khoản" value={payment?.payment_code} accent selectable />
        </View>

        <View style={styles.warning}>
          <Ionicons name="warning-outline" size={20} color="#9a3412" />
          <Text style={styles.warningText}>Giữ nguyên số tiền và nội dung chuyển khoản để đơn được xác nhận tự động.</Text>
        </View>
        <View style={styles.waiting}>
          <ActivityIndicator color="#2563eb" />
          <Text style={styles.waitingText}>Đang chờ thanh toán đơn #{orderId}</Text>
        </View>
        {error ? <Text style={styles.error}>{error}</Text> : null}
        <TouchableOpacity style={styles.orderButton} onPress={() => navigation.navigate('OrderHistory')}>
          <Text style={styles.orderButtonText}>Xem đơn hàng</Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

function Row({ label, value, accent, selectable }) {
  return (
    <View style={styles.row}>
      <Text style={styles.label}>{label}</Text>
      <Text selectable={selectable} style={[styles.value, accent && styles.accent]}>{value || '—'}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: COLORS.background },
  content: { padding: SPACING.lg, paddingBottom: 40 },
  header: { flexDirection: 'row', alignItems: 'center', gap: SPACING.md, marginBottom: SPACING.xl },
  brand: { backgroundColor: '#16a34a', padding: 12, borderRadius: RADIUS.md },
  brandText: { color: '#fff', fontWeight: '900', fontSize: 18 },
  title: { color: COLORS.textPrimary, fontSize: 21, fontWeight: '800' },
  subtitle: { color: COLORS.textTertiary, fontSize: 12, marginTop: 4 },
  qr: { width: '100%', aspectRatio: 1, backgroundColor: '#fff', borderRadius: RADIUS.lg },
  details: { backgroundColor: COLORS.surface, borderRadius: RADIUS.lg, padding: SPACING.lg, marginTop: SPACING.lg },
  row: { paddingVertical: SPACING.md, borderBottomWidth: 1, borderBottomColor: COLORS.border },
  label: { color: COLORS.textTertiary, fontSize: 12, marginBottom: 4 },
  value: { color: COLORS.textPrimary, fontSize: 16, fontWeight: '700' },
  accent: { color: '#dc2626', fontSize: 20 },
  warning: { flexDirection: 'row', gap: 8, backgroundColor: '#fff7ed', borderRadius: RADIUS.md, padding: SPACING.md, marginTop: SPACING.lg },
  warningText: { color: '#9a3412', flex: 1, lineHeight: 19 },
  waiting: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 10, backgroundColor: '#eff6ff', padding: SPACING.md, borderRadius: RADIUS.md, marginTop: SPACING.lg },
  waitingText: { color: '#1d4ed8', fontWeight: '700' },
  error: { color: COLORS.error, textAlign: 'center', marginTop: SPACING.md },
  orderButton: { alignItems: 'center', borderWidth: 1, borderColor: COLORS.border, borderRadius: RADIUS.md, padding: SPACING.md, marginTop: SPACING.lg },
  orderButtonText: { color: COLORS.textPrimary, fontWeight: '700' },
});
