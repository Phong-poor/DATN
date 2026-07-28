import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS, RADIUS, SPACING } from '../utils/theme';

export default function AddressMap({ latitude, longitude }) {
  return (
    <View style={styles.wrap}>
      <Ionicons name="location-outline" size={30} color={COLORS.primary} />
      <Text style={styles.title}>Đã xác định vị trí giao hàng</Text>
      <Text style={styles.coordinates}>
        {Number(latitude).toFixed(6)}, {Number(longitude).toFixed(6)}
      </Text>
      <Text style={styles.hint}>Kéo ghim bản đồ hiện hỗ trợ trên ứng dụng Android và iOS.</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    minHeight: 180,
    marginTop: SPACING.md,
    padding: SPACING.lg,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: { marginTop: SPACING.sm, color: COLORS.textPrimary, fontWeight: '700' },
  coordinates: { marginTop: SPACING.xs, color: COLORS.primary, fontSize: 12 },
  hint: {
    marginTop: SPACING.md,
    color: COLORS.textTertiary,
    fontSize: 11,
    textAlign: 'center',
  },
});
