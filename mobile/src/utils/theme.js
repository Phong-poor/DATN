import { Platform } from 'react-native';

// ============================================
// DESIGN SYSTEM - TYPOGRAPHY & THEME
// ============================================
// Tất cả màn hình PHẢI dùng các style này thay vì tự định nghĩa fontSize
// ============================================

export const COLORS = {
  // Primary Colors
  primary: '#6366f1',      // Indigo-500
  primaryDark: '#4f46e5',  // Indigo-600
  primaryLight: '#818cf8', // Indigo-400
  primaryMuted: '#e0e7ff', // Indigo-100
  
  // Surface Colors
  background: '#0f172a',   // Slate-900
  surface: '#1e293b',      // Slate-800
  surfaceLight: '#334155', // Slate-700
  
  // Text Colors
  textPrimary: '#f8fafc',  // Slate-50
  textSecondary: '#cbd5e1', // Slate-300
  textTertiary: '#94a3b8', // Slate-400
  textMuted: '#64748b',    // Slate-500
  
  // Semantic Colors
  success: '#10b981',      // Green-500
  error: '#ef4444',        // Red-500
  warning: '#f59e0b',      // Amber-500
  info: '#3b82f6',         // Blue-500
  
  // Border & Divider
  border: '#334155',       // Slate-700
  divider: '#475569',      // Slate-600
  
  // Others
  white: '#ffffff',
  black: '#000000',
};

// ============================================
// TYPOGRAPHY SCALE - Theo chuẩn Material Design
// ============================================
export const TYPOGRAPHY = {
  // Display (Tiêu đề cực lớn - Hero sections)
  displayLarge: {
    fontSize: 32,
    fontWeight: '800',
    lineHeight: 40,
    letterSpacing: -0.5,
  },
  displayMedium: {
    fontSize: 28,
    fontWeight: '700',
    lineHeight: 36,
    letterSpacing: -0.25,
  },
  
  // Headline (Tiêu đề chính)
  headlineLarge: {
    fontSize: 24,
    fontWeight: '700',
    lineHeight: 32,
  },
  headlineMedium: {
    fontSize: 20,
    fontWeight: '600',
    lineHeight: 28,
  },
  headlineSmall: {
    fontSize: 18,
    fontWeight: '600',
    lineHeight: 24,
  },
  
  // Title (Tiêu đề phụ)
  titleLarge: {
    fontSize: 16,
    fontWeight: '600',
    lineHeight: 24,
  },
  titleMedium: {
    fontSize: 14,
    fontWeight: '600',
    lineHeight: 20,
  },
  titleSmall: {
    fontSize: 12,
    fontWeight: '600',
    lineHeight: 16,
  },
  
  // Body (Nội dung chính)
  bodyLarge: {
    fontSize: 16,
    fontWeight: '400',
    lineHeight: 24,
  },
  bodyMedium: {
    fontSize: 14,
    fontWeight: '400',
    lineHeight: 20,
  },
  bodySmall: {
    fontSize: 12,
    fontWeight: '400',
    lineHeight: 16,
  },
  
  // Label (Nhãn, button text)
  labelLarge: {
    fontSize: 14,
    fontWeight: '500',
    lineHeight: 20,
  },
  labelMedium: {
    fontSize: 12,
    fontWeight: '500',
    lineHeight: 16,
  },
  labelSmall: {
    fontSize: 10,
    fontWeight: '500',
    lineHeight: 14,
  },
  
  // Caption (Chú thích nhỏ)
  caption: {
    fontSize: 10,
    fontWeight: '400',
    lineHeight: 14,
  },
};

// ============================================
// SPACING SCALE (8pt grid system)
// ============================================
export const SPACING = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  xxl: 24,
  xxxl: 32,
};

// ============================================
// BORDER RADIUS
// ============================================

export const RADIUS = {
  sm: 4,
  md: 8,
  lg: 12,
  xl: 16,
  full: 9999,
};

// ============================================
// SHADOWS (cho Android & iOS & Web)
// ============================================
export const SHADOWS = {
  sm: Platform.select({
    ios: {
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: 0.05,
      shadowRadius: 2,
    },
    android: {
      elevation: 1,
    },
    web: {
      boxShadow: '0px 1px 2px rgba(0, 0, 0, 0.05)',
    },
    default: {
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: 0.05,
      shadowRadius: 2,
      elevation: 1,
    }
  }),
  md: Platform.select({
    ios: {
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.1,
      shadowRadius: 4,
    },
    android: {
      elevation: 3,
    },
    web: {
      boxShadow: '0px 2px 4px rgba(0, 0, 0, 0.1)',
    },
    default: {
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.1,
      shadowRadius: 4,
      elevation: 3,
    }
  }),
  lg: Platform.select({
    ios: {
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 4 },
      shadowOpacity: 0.15,
      shadowRadius: 8,
    },
    android: {
      elevation: 5,
    },
    web: {
      boxShadow: '0px 4px 8px rgba(0, 0, 0, 0.15)',
    },
    default: {
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 4 },
      shadowOpacity: 0.15,
      shadowRadius: 8,
      elevation: 5,
    }
  }),
};

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Tạo text style kết hợp typography + color
 * @param {string} variant - Tên variant từ TYPOGRAPHY
 * @param {string} color - Tên color từ COLORS
 * @returns {object} Style object
 */
export const createTextStyle = (variant, color = 'textPrimary') => ({
  ...TYPOGRAPHY[variant],
  color: COLORS[color],
});

/**
 * Format price theo VND
 */
export const formatPrice = (price) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
  }).format(price);
};

// ============================================
// BUTTON VARIANTS
// ============================================
export const BUTTON_VARIANTS = {
  primary: {
    backgroundColor: COLORS.primary,
    textColor: COLORS.white,
    borderColor: 'transparent',
  },
  secondary: {
    backgroundColor: COLORS.surface,
    textColor: COLORS.textPrimary,
    borderColor: COLORS.border,
  },
  outline: {
    backgroundColor: 'transparent',
    textColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  ghost: {
    backgroundColor: 'transparent',
    textColor: COLORS.textPrimary,
    borderColor: 'transparent',
  },
  danger: {
    backgroundColor: COLORS.error,
    textColor: COLORS.white,
    borderColor: 'transparent',
  },
};

// ============================================
// INPUT STYLES
// ============================================
export const INPUT_STYLES = {
  default: {
    backgroundColor: COLORS.surface,
    textColor: COLORS.textPrimary,
    borderColor: COLORS.border,
    placeholderColor: COLORS.textTertiary,
    focusBorderColor: COLORS.primary,
  },
  error: {
    backgroundColor: COLORS.surface,
    textColor: COLORS.textPrimary,
    borderColor: COLORS.error,
    placeholderColor: COLORS.textTertiary,
    focusBorderColor: COLORS.error,
  },
};

// ============================================
// CARD STYLES
// ============================================
export const CARD_STYLES = {
  default: {
    backgroundColor: COLORS.surface,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    shadow: SHADOWS.sm,
  },
  elevated: {
    backgroundColor: COLORS.surface,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    shadow: SHADOWS.md,
  },
  filled: {
    backgroundColor: COLORS.primaryMuted,
    borderColor: 'transparent',
    borderRadius: RADIUS.md,
    shadow: null,
  },
};

// ============================================
// NAVIGATION STYLES - Bottom Tab Bar
// ============================================
export const NAVIGATION_STYLES = {
  tabBar: {
    backgroundColor: COLORS.surface,
    borderTopColor: COLORS.border,
    borderTopWidth: 1,
    height: 60,
    paddingBottom: SPACING.lg,
    paddingTop: SPACING.sm,
  },
  tabIcon: {
    inactiveColor: COLORS.textTertiary,
    activeColor: COLORS.primary,
  },
  tabLabel: {
    fontSize: TYPOGRAPHY.labelSmall.fontSize,
    fontWeight: TYPOGRAPHY.labelSmall.fontWeight,
  },
};

// ============================================
// EXPORT DEFAULT
// ============================================
export default {
  COLORS,
  TYPOGRAPHY,
  SPACING,
  RADIUS,
  SHADOWS,
  BUTTON_VARIANTS,
  INPUT_STYLES,
  CARD_STYLES,
  NAVIGATION_STYLES,
  createTextStyle,
  formatPrice,
};
