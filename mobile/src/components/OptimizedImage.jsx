import React, { useState, useMemo, useCallback } from 'react';
import { View, StyleSheet } from 'react-native';
import { Image } from 'expo-image';
import logger from '../utils/logger';

/**
 * OptimizedImage Component
 * Uses expo-image for maximum performance:
 * - Built-in native caching
 * - Instant rendering (no transitions or loading indicators)
 * - Better memory management
 * - Error fallback
 */
export default function OptimizedImage({
  source,
  style,
  placeholder = null, // Disable blurhash to load the real image instantly
  transition = 0,     // No transition animation (instant pop-in)
  lazyLoad = false,   // Eager loading by default
  contentFit = 'cover',
  contentPosition = 'center',
  onError,
  onLoad,
  ...props
}) {
  // Track current image source (for fallback swap on error)
  const [currentSource, setCurrentSource] = useState(null);

  // Handle image source - support strings, numbers (require), and uri objects
  const imageSource = useMemo(() => {
    if (!source) return null;
    
    if (typeof source === 'string') {
      return source;
    }
    
    if (typeof source === 'number') {
      return source;
    }
    
    if (typeof source === 'object') {
      return source.uri ? source.uri : source;
    }
    
    return null;
  }, [source]);

  // Sync currentSource when prop source changes
  React.useEffect(() => {
    setCurrentSource(imageSource);
  }, [imageSource]);

  const handleError = useCallback((error) => {
    logger.log('[OptimizedImage Error] Failed to load:', currentSource);
    
    // Curated high-quality tech placeholder images from Unsplash
    const fallbacks = [
      'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&q=80', // Laptop mockup
      'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&q=80', // Tech setup
      'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&q=80', // Keyboard/Monitor
      'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=800&q=80', // Workspace
      'https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=800&q=80', // Futuristic tech
      'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=800&q=80', // Laptop
    ];

    if (currentSource && typeof currentSource === 'string' && !fallbacks.includes(currentSource)) {
      // Calculate a simple hash code from the current source string
      let hash = 0;
      for (let i = 0; i < currentSource.length; i++) {
        hash = currentSource.charCodeAt(i) + ((hash << 5) - hash);
      }
      const index = Math.abs(hash) % fallbacks.length;
      setCurrentSource(fallbacks[index]);
    }
    
    onError?.(error);
  }, [currentSource, onError]);

  // Check if image is valid
  const isValidImage = currentSource !== null && currentSource !== undefined;

  if (!isValidImage) {
    return null;
  }

  return (
    <View style={[styles.container, style]}>
      <Image
        source={currentSource}
        style={StyleSheet.flatten(style)}
        placeholder={placeholder}
        transition={transition}
        lazy={lazyLoad}
        onError={handleError}
        onLoad={onLoad}
        contentFit={contentFit}
        contentPosition={contentPosition}
        {...props}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: 'transparent', // Transparent background to avoid grey flash
  },
});
