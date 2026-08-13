# Design System Usage Guide

## Overview
This design system provides consistent styling across the entire mobile app.

## Design Tokens

### Colors
```javascript
import { COLORS } from '../utils/theme';
backgroundColor: COLORS.primary
```

### Typography  
```javascript
import { TYPOGRAPHY } from '../utils/theme';
fontSize: TYPOGRAPHY.bodyMedium.fontSize
```

### Spacing
```javascript
import { SPACING } from '../utils/theme';
padding: SPACING.lg  // 16px
```

### Border Radius
```javascript
import { RADIUS } from '../utils/theme';
borderRadius: RADIUS.lg  // 12px
```

### Shadows
```javascript
import { SHADOWS } from '../utils/theme';
...SHADOWS.md
```

## Migration Example

### Before (Hardcoded)
```javascript
backgroundColor: '#1e293b'
fontSize: 14
```

### After (Design System)
```javascript
backgroundColor: COLORS.surface
...TYPOGRAPHY.bodyMedium
```

## Best Practices
1. Always use design tokens instead of hardcoded values
2. Use SPACING for margins/paddings
3. Use TYPOGRAPHY for all text styles
4. Use COLORS for all colors
