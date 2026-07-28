import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import MapView, { Marker } from 'react-native-maps';
import { COLORS, RADIUS, SPACING } from '../utils/theme';

export default function AddressMap({ latitude, longitude, onCoordinateChange }) {
  const coordinate = {
    latitude: Number(latitude),
    longitude: Number(longitude),
  };

  return (
    <View style={styles.wrap}>
      <MapView
        key={`${coordinate.latitude}-${coordinate.longitude}`}
        style={styles.map}
        initialRegion={{
          ...coordinate,
          latitudeDelta: 0.006,
          longitudeDelta: 0.006,
        }}
      >
        <Marker
          draggable
          coordinate={coordinate}
          onDragEnd={(event) => onCoordinateChange?.(event.nativeEvent.coordinate)}
        />
      </MapView>
      <Text style={styles.hint}>Giữ và kéo ghim để chỉnh chính xác điểm giao hàng</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    marginTop: SPACING.md,
    borderRadius: RADIUS.lg,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  map: { width: '100%', height: 230 },
  hint: {
    color: COLORS.textTertiary,
    fontSize: 11,
    textAlign: 'center',
    padding: SPACING.sm,
    backgroundColor: COLORS.surface,
  },
});
