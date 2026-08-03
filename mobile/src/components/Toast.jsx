import React, { useEffect, useRef } from 'react';
import { StyleSheet, Text, Animated, View, Dimensions, Platform, TouchableOpacity } from 'react-native';
import { Feather } from '@expo/vector-icons';
import useToastStore from '../store/useToastStore';
import { COLORS, RADIUS, SPACING } from '../utils/theme';

const { width } = Dimensions.get('window');
const TOAST_WIDTH = Platform.OS === 'web' ? Math.min(width * 0.9, 360) : width * 0.9;

export default function Toast() {
  const { visible, message, type, hide, actions } = useToastStore();
  const animatedValue = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (visible) {
      // Slide in and fade in
      Animated.parallel([
        Animated.timing(animatedValue, {
          toValue: 1,
          duration: 350,
          useNativeDriver: Platform.OS !== 'web', // Native driver is not fully supported on some web setups
        }),
      ]).start();
    } else {
      // Slide out and fade out
      Animated.parallel([
        Animated.timing(animatedValue, {
          toValue: 0,
          duration: 300,
          useNativeDriver: Platform.OS !== 'web',
        }),
      ]).start();
    }
  }, [visible, animatedValue]);

  if (!visible && animatedValue._value === 0) {
    return null;
  }

  // Interpolate slide from top (Y translation)
  const translateY = animatedValue.interpolate({
    inputRange: [0, 1],
    outputRange: [-100, 0],
  });

  // Interpolate opacity
  const opacity = animatedValue.interpolate({
    inputRange: [0, 1],
    outputRange: [0, 1],
  });

  // Configure icon and colors based on type
  const typeConfig = {
    success: {
      icon: 'check-circle',
      iconColor: '#10b981', // Emerald green
      borderColor: 'rgba(16, 185, 129, 0.2)',
    },
    error: {
      icon: 'x-circle',
      iconColor: '#f43f5e', // Rose red
      borderColor: 'rgba(244, 63, 94, 0.2)',
    },
    info: {
      icon: 'info',
      iconColor: '#3b82f6', // Bright blue
      borderColor: 'rgba(59, 130, 246, 0.2)',
    },
  }[type] || {
    icon: 'info',
    iconColor: '#94a3b8',
    borderColor: 'rgba(148, 163, 184, 0.2)',
  };

  return (
    <View style={styles.outerContainer} pointerEvents="box-none">
      <Animated.View
        style={[
          styles.container,
          {
            transform: [{ translateY }],
            opacity,
            borderColor: typeConfig.borderColor,
          },
        ]}
      >
        <View style={styles.mainContent}>
          <View style={styles.messageRow}>
            <Feather name={typeConfig.icon} size={20} color={typeConfig.iconColor} style={styles.icon} />
            <Text style={styles.message} numberOfLines={2}>
              {message}
            </Text>
          </View>
          {actions && actions.length > 0 && (
            <View style={styles.actionRow}>
              {actions.map((act, index) => (
                <TouchableOpacity
                  key={index}
                  style={styles.actionBtn}
                  onPress={() => {
                    hide();
                    if (typeof act.onPress === 'function') {
                      act.onPress();
                    }
                  }}
                >
                  <Text style={styles.actionBtnText}>{act.text}</Text>
                </TouchableOpacity>
              ))}
            </View>
          )}
        </View>
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  outerContainer: {
    position: 'absolute',
    top: Platform.OS === 'ios' ? 60 : 40,
    left: 0,
    right: 0,
    alignItems: 'center',
    zIndex: 99999,
  },
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    width: TOAST_WIDTH,
    backgroundColor: 'rgba(15, 23, 42, 0.95)', // Premium dark slate card
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderRadius: 14,
    borderWidth: 1,
    ...Platform.select({
      ios: {
        shadowColor: '#000',
        shadowOpacity: 0.15,
        shadowOffset: { width: 0, height: 4 },
        shadowRadius: 6,
      },
      android: {
        elevation: 6,
      },
      web: {
        boxShadow: '0 8px 16px rgba(0, 0, 0, 0.16)',
      },
    }),
  },
  mainContent: {
    flex: 1,
  },
  messageRow: {
    flexDirection: 'row',
    alignItems: 'center',
    width: '100%',
  },
  actionRow: {
    flexDirection: 'row',
    justifyContent: 'flex-end',
    marginTop: 8,
    gap: 12,
  },
  actionBtn: {
    paddingVertical: 4,
    paddingHorizontal: 10,
    borderRadius: 6,
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
  },
  actionBtnText: {
    color: '#a5b4fc', // indigo-300 readable on dark bg
    fontSize: 11,
    fontWeight: '700',
  },
  icon: {
    marginRight: 12,
  },
  message: {
    fontSize: 13,
    fontWeight: '600',
    color: '#f8fafc',
    flex: 1,
  },
});
