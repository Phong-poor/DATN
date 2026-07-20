import React from 'react';
import { StyleSheet, View, ActivityIndicator } from 'react-native';
import { COLORS } from '../utils/theme';

export default function LoadingIndicator({
  size = 'large',
  color = COLORS.primary,
  style
}) {
  return (
    <View style={[styles.container, style]}>
      <ActivityIndicator size={size} color={color} animating={true} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: 20,
  },
});

