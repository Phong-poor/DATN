import React from 'react';
import { StyleSheet, Text, View, Modal, TouchableOpacity, Animated, Platform } from 'react-native';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';

export default function CustomAlert({
  visible,
  title,
  message,
  onCancel,
  onConfirm,
  confirmText = 'Xác nhận',
  cancelText = 'Hủy',
  type = 'info', // 'info', 'success', 'warning', 'error'
}) {
  const getIcon = () => {
    switch (type) {
      case 'success': return '✅';
      case 'warning': return '⚠️';
      case 'error': return '❌';
      default: return 'ℹ️';
    }
  };

  const getHeaderColor = () => {
    switch (type) {
      case 'success': return '#10b981'; // Green
      case 'warning': return '#f59e0b'; // Amber
      case 'error': return '#ef4444'; // Red
      default: return '#6366f1'; // Indigo
    }
  };

  return (
    <Modal
      transparent
      visible={visible}
      animationType="fade"
      onRequestClose={onCancel}
    >
      <View style={styles.backdrop}>
        <View style={styles.alertCard}>
          {/* Header Line color based on type */}
          <View style={[styles.headerBar, { backgroundColor: getHeaderColor() }]} />

          <View style={styles.contentContainer}>
            <Text style={styles.icon}>{getIcon()}</Text>
            
            {title ? <Text style={styles.title}>{title}</Text> : null}
            {message ? <Text style={styles.message}>{message}</Text> : null}
          </View>

          {/* Action buttons */}
          <View style={styles.buttonRow}>
            {onCancel ? (
              <TouchableOpacity style={styles.cancelBtn} onPress={onCancel}>
                <Text style={styles.cancelText}>{cancelText}</Text>
              </TouchableOpacity>
            ) : null}

            <TouchableOpacity 
              style={[styles.confirmBtn, { backgroundColor: getHeaderColor() }]} 
              onPress={onConfirm}
            >
              <Text style={styles.confirmText}>{confirmText}</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.75)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: SPACING.xxl,
  },
  alertCard: {
    width: '100%',
    maxWidth: 340,
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
    elevation: 10,
    ...Platform.select({
      ios: {
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 8 },
        shadowOpacity: 0.25,
        shadowRadius: 16,
      },
      web: {
        boxShadow: '0px 8px 16px rgba(0, 0, 0, 0.25)',
      },
    }),
  },
  headerBar: {
    height: 4,
    width: '100%',
  },
  contentContainer: {
    padding: SPACING.xxl,
    alignItems: 'center',
  },
  icon: {
    fontSize: 32,
    marginBottom: 14,
  },
  title: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.textPrimary,
    textAlign: 'center',
    marginBottom: SPACING.sm,
  },
  message: {
    fontSize: 13,
    color: COLORS.textSecondary,
    textAlign: 'center',
    lineHeight: 18,
  },
  buttonRow: {
    flexDirection: 'row',
    borderTopWidth: 1,
    borderColor: COLORS.border,
    padding: SPACING.md,
    justifyContent: 'flex-end',
    backgroundColor: COLORS.background,
  },
  cancelBtn: {
    paddingVertical: SPACING.sm,
    paddingHorizontal: SPACING.lg,
    marginRight: SPACING.sm,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    justifyContent: 'center',
    alignItems: 'center',
  },
  cancelText: {
    fontSize: 13,
    color: COLORS.textSecondary,
    fontWeight: '600',
  },
  confirmBtn: {
    paddingVertical: SPACING.sm,
    paddingHorizontal: SPACING.lg,
    borderRadius: RADIUS.md,
    justifyContent: 'center',
    alignItems: 'center',
  },
  confirmText: {
    fontSize: 13,
    color: COLORS.white,
    fontWeight: '700',
  },
});
