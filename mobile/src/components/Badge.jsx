import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';

export default function Badge({
  value,
  variant = 'primary',
  size = 'md',
  showZero = false,
  style
}) {
  if (!showZero && (!value || value <= 0)) return null;

  const badgeStyles = {
    primary: { backgroundColor: COLORS.primary, textColor: COLORS.white },
    secondary: { backgroundColor: COLORS.surface, textColor: COLORS.textPrimary },
    danger: { backgroundColor: COLORS.error, textColor: COLORS.white },
    warning: { backgroundColor: COLORS.warning, textColor: COLORS.textPrimary },
    success: { backgroundColor: COLORS.success, textColor: COLORS.white },
  };

  const sizeStyles = {
    sm: { paddingVertical: 2, paddingHorizontal: 6, fontSize: 10 },
    md: { paddingVertical: 4, paddingHorizontal: 8, fontSize: 12 },
    lg: { paddingVertical: 6, paddingHorizontal: 10, fontSize: 14 },
  };

  const badgeStyle = badgeStyles[variant] || badgeStyles.primary;
  const sizeStyle = sizeStyles[size] || sizeStyles.md;

  return (
    <View style={[styles.container, { backgroundColor: badgeStyle.backgroundColor, borderRadius: RADIUS.full, ...sizeStyle }, style]}>
      <Text style={[styles.text, { color: badgeStyle.textColor }]}>
        {value > 99 ? '99+' : value}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  text: {
    fontWeight: '700',
  },
});

