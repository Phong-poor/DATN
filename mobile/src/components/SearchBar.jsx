import React from 'react';
import { StyleSheet, Text, View, TextInput, TouchableOpacity } from 'react-native';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';

export default function SearchBar({
  placeholder = 'Tìm kiếm...',
  value,
  onChange,
  style,
  ...props
}) {
  return (
    <View style={[styles.container, style]}>
      <Text style={styles.searchIcon}>🔍</Text>
      <TextInput
        style={styles.input}
        placeholder={placeholder}
        placeholderTextColor={COLORS.textTertiary}
        value={value}
        onChangeText={onChange}
        {...props}
      />
      {value ? (
        <TouchableOpacity onPress={() => onChange('')} style={styles.clearBtn}>
          <Text style={styles.clearText}>✕</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    paddingHorizontal: SPACING.md,
    paddingVertical: SPACING.sm,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  searchIcon: {
    fontSize: 16,
    marginRight: SPACING.sm,
  },
  input: {
    flex: 1,
    height: 40,
    color: COLORS.textPrimary,
    fontSize: 14,
  },
  clearBtn: {
    padding: SPACING.xs,
  },
  clearText: {
    color: COLORS.textTertiary,
    fontSize: 16,
  },
});

