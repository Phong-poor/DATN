import React from 'react';
import { StyleSheet, Text, View, TouchableOpacity } from 'react-native';
import { COLORS, TYPOGRAPHY, SPACING } from '../utils/theme';

export default function EmptyState({
  variant = 'default',
  icon = '📦',
  title = 'Không có dữ liệu',
  description,
  actionLabel,
  onAction,
  style
}) {
  const variantStyles = {
    default: { icon: '📦', color: COLORS.textTertiary },
    error: { icon: '❌', color: COLORS.error },
    warning: { icon: '⚠️', color: COLORS.warning },
    success: { icon: '✅', color: COLORS.success },
  };

  const currentStyle = variantStyles[variant] || variantStyles.default;

  return (
    <View style={[styles.container, style]}>
      <Text style={[styles.icon, { color: currentStyle.color, fontSize: 64 }]}>{icon}</Text>
      <Text style={[styles.title, { color: COLORS.textPrimary }]}>{title}</Text>
      {description ? (
        <Text style={[styles.description, { color: COLORS.textTertiary }]}>
          {description}
        </Text>
      ) : null}
      {actionLabel && onAction ? (
        <TouchableOpacity
          style={[styles.actionBtn, { backgroundColor: COLORS.primary }]}
          onPress={onAction}
        >
          <Text style={[styles.actionText, { color: COLORS.white }]}>{actionLabel}</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: SPACING.xxl,
  },
  icon: {
    marginBottom: SPACING.lg,
  },
  title: {
    fontSize: 18,
    fontWeight: '700',
    marginBottom: SPACING.sm,
    textAlign: 'center',
  },
  description: {
    fontSize: 14,
    textAlign: 'center',
    marginBottom: SPACING.lg,
    lineHeight: 20,
  },
  actionBtn: {
    paddingVertical: SPACING.sm,
    paddingHorizontal: SPACING.lg,
    borderRadius: RADIUS.md,
  },
  actionText: {
    fontSize: 14,
    fontWeight: '600',
  },
});

