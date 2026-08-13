import React, { useState } from 'react';
import { View, Text, TextInput, StyleSheet } from 'react-native';
import { COLORS, TYPOGRAPHY, INPUT_STYLES, RADIUS, SPACING } from '../utils/theme';

export default function Input({ 
  label, 
  error, 
  placeholder, 
  value, 
  onChangeText, 
  secureTextEntry = false,
  style,
  ...props
}) {
  const [isFocused, setIsFocused] = useState(false);
  
  const inputStyle = [
    styles.base,
    error ? INPUT_STYLES.error : (isFocused ? styles.focus : INPUT_STYLES.default),
    style
  ];
  
  return (
    <View style={styles.container}>
      {label && <Text style={styles.label}>{label}</Text>}
      <TextInput
        style={inputStyle}
        placeholder={placeholder}
        placeholderTextColor={COLORS.textTertiary}
        value={value}
        onChangeText={onChangeText}
        secureTextEntry={secureTextEntry}
        onFocus={() => setIsFocused(true)}
        onBlur={() => setIsFocused(false)}
        {...props}
      />
      {error && <Text style={styles.errorText}>{error}</Text>}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginBottom: SPACING.lg,
  },
  label: {
    ...TYPOGRAPHY.labelMedium,
    color: COLORS.textSecondary,
    marginBottom: SPACING.xs,
    fontWeight: '500',
  },
  base: {
    paddingVertical: SPACING.sm,
    paddingHorizontal: SPACING.md,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    fontSize: TYPOGRAPHY.bodyMedium.fontSize,
    color: COLORS.textPrimary,
  },
  focus: {
    borderColor: COLORS.primary,
    borderWidth: 2,
  },
  errorText: {
    ...TYPOGRAPHY.labelSmall,
    color: COLORS.error,
    marginTop: SPACING.xs,
  },
});
