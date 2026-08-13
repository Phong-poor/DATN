import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { COLORS, TYPOGRAPHY, CARD_STYLES, RADIUS, SHADOWS, SPACING } from '../utils/theme';

export default function Card({ 
  variant = 'default', 
  children, 
  style, 
  onPress 
}) {
  const cardStyle = [
    styles.base,
    CARD_STYLES[variant],
    style
  ];
  
  if (onPress) {
    return (
      <TouchableOpacity 
        style={cardStyle} 
        onPress={onPress}
        activeOpacity={0.8}
      >
        {children}
      </TouchableOpacity>
    );
  }
  
  return (
    <View style={cardStyle}>
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  base: {
    borderRadius: RADIUS.lg,
    overflow: 'hidden',
  },
});
