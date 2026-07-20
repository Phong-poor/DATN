import React from 'react';
import { StyleSheet, Text, View, TouchableOpacity } from 'react-native';
import { COLORS, TYPOGRAPHY, SPACING } from '../utils/theme';

export default function RatingStars({
  rating = 0,
  maxRating = 5,
  showCount,
  count,
  allowRating = false,
  onRate,
  size = 20,
  style
}) {
  const stars = Array.from({ length: maxRating }, (_, i) => i + 1);

  const handleStarPress = (star) => {
    if (allowRating && onRate) {
      onRate(star);
    }
  };

  return (
    <View style={[styles.container, style]}>
      {stars.map((star) => (
        <TouchableOpacity
          key={star}
          onPress={() => handleStarPress(star)}
          disabled={!allowRating}
          activeOpacity={0.7}
        >
          <Text style={[
            styles.star,
            { fontSize: size },
            star <= Math.round(rating) ? styles.starFilled : styles.starEmpty
          ]}>
            {star <= Math.round(rating) ? '★' : '☆'}
          </Text>
        </TouchableOpacity>
      ))}
      {showCount && count !== undefined && (
        <Text style={styles.countText}>({count})</Text>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  star: {
    marginRight: 2,
  },
  starFilled: {
    color: COLORS.warning,
  },
  starEmpty: {
    color: COLORS.border,
  },
  countText: {
    marginLeft: SPACING.sm,
    color: COLORS.textTertiary,
    fontSize: 12,
    fontWeight: '500',
  },
});

