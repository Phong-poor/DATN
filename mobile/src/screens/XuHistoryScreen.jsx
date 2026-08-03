import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api from '../services/api';
import { COLORS, RADIUS, SPACING } from '../utils/theme';

export default function XuHistoryScreen({ navigation }) {
  const [balance, setBalance] = useState(0);
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    const [balanceRes, historyRes] = await Promise.all([api.get('/xu/balance'), api.get('/xu/history')]);
    setBalance(Number(balanceRes.data?.xu || 0));
    setItems(historyRes.data?.data?.data || []);
  }, []);

  useEffect(() => { load().finally(() => setLoading(false)); }, [load]);

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><Ionicons name="arrow-back" size={24} color={COLORS.textPrimary} /></TouchableOpacity>
        <Text style={styles.title}>Ví xu của tôi</Text><View style={{ width: 24 }} />
      </View>
      <View style={styles.balanceCard}>
        <Ionicons name="sparkles" size={28} color="#fbbf24" />
        <Text style={styles.balanceLabel}>Số dư hiện tại</Text>
        <Text style={styles.balance}>{balance.toLocaleString('vi-VN')} xu</Text>
      </View>
      {loading ? <ActivityIndicator style={{ marginTop: 40 }} color={COLORS.primary} /> : (
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id_lichsu)}
          contentContainerStyle={styles.list}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load().finally(() => setRefreshing(false)); }} />}
          ListEmptyComponent={<Text style={styles.empty}>Chưa có giao dịch xu.</Text>}
          renderItem={({ item }) => {
            const positive = Number(item.so_xu) >= 0;
            return (
              <View style={styles.item}>
                <View style={[styles.icon, { backgroundColor: positive ? '#dcfce7' : '#fee2e2' }]}><Ionicons name={positive ? 'add' : 'remove'} size={20} color={positive ? '#16a34a' : '#dc2626'} /></View>
                <View style={{ flex: 1 }}><Text style={styles.description}>{item.mo_ta || item.loai_giao_dich}</Text><Text style={styles.date}>{new Date(item.created_at).toLocaleString('vi-VN')}</Text></View>
                <Text style={[styles.amount, { color: positive ? '#16a34a' : '#dc2626' }]}>{positive ? '+' : ''}{Number(item.so_xu).toLocaleString('vi-VN')}</Text>
              </View>
            );
          }}
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.background },
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: SPACING.lg, borderBottomWidth: 1, borderColor: COLORS.border },
  title: { color: COLORS.textPrimary, fontSize: 18, fontWeight: '800' },
  balanceCard: { margin: SPACING.lg, padding: SPACING.xl, backgroundColor: '#312e81', borderRadius: RADIUS.lg, alignItems: 'center' },
  balanceLabel: { color: '#c7d2fe', marginTop: 8 },
  balance: { color: '#fff', fontSize: 28, fontWeight: '900', marginTop: 4 },
  list: { padding: SPACING.lg },
  item: { flexDirection: 'row', alignItems: 'center', gap: SPACING.md, backgroundColor: COLORS.surface, borderRadius: RADIUS.md, padding: SPACING.md, marginBottom: SPACING.sm },
  icon: { width: 36, height: 36, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  description: { color: COLORS.textPrimary, fontWeight: '600' },
  date: { color: COLORS.textTertiary, fontSize: 11, marginTop: 4 },
  amount: { fontWeight: '800' },
  empty: { textAlign: 'center', color: COLORS.textTertiary, marginTop: 50 },
});
