import React from 'react';
import { TouchableOpacity, Text, ActivityIndicator, StyleSheet } from 'react-native';
import { COLORS, TYPOGRAPHY, BUTTON_VARIANTS, RADIUS, SPACING } from '../utils/theme';

export default function Button({ 
  variant = 'primary', 
  children, 
  disabled = false, 
  loading = false, 
  style, 
  onPress 
}) {
  const buttonStyle = [
    styles.base,
    BUTTON_VARIANTS[variant],
    disabled && styles.disabled,
    style
  ];
  
  return (
    <TouchableOpacity 
      style={buttonStyle} 
      onPress={onPress} 
      disabled={disabled || loading}
      activeOpacity={0.7}
    >
      {loading ? (
        <ActivityIndicator 
          color={BUTTON_VARIANTS[variant].textColor} 
          size="small" 
        />
      ) : (
        <Text style={styles.text}>{children}</Text>
      )}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  base: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: SPACING.sm,
    paddingHorizontal: SPACING.lg,
    borderRadius: RADIUS.md,
  },
  disabled: {
    opacity: 0.5,
  },
  text: {
    ...TYPOGRAPHY.labelLarge,
    fontWeight: '600',
  },
});
