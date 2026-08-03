import { Alert, Platform } from 'react-native';
import useToastStore from '../store/useToastStore';

/**
 * Cross-platform alert/toast interceptor
 * Automatically displays success and information notifications as non-blocking floating toasts.
 * Falls back to native dialogs for errors or user actions.
 */
export const showAlert = (title, message, buttons = []) => {
  const lowercaseTitle = String(title || '').toLowerCase();
  const isSuccess = lowercaseTitle.includes('thành công');
  const isInfo = lowercaseTitle.includes('thông báo');
  const isError = lowercaseTitle.includes('lỗi') || lowercaseTitle.includes('thất bại');

  // Intercept status messages to show a premium toast instead of blocking native dialogs
  if (isSuccess || isInfo || isError) {
    const type = isSuccess ? 'success' : (isError ? 'error' : 'info');
    useToastStore.getState().show(message, type, 5000, buttons);
    return;
  }

  // Fallback for interactive alerts or web platforms
  if (Platform.OS === 'web') {
    const formattedMessage = title ? `${title}\n\n${message}` : message;
    
    // For critical errors or user decisions, use standard browser confirm/alert
    if (buttons && buttons.length > 1) {
      const confirmed = confirm(formattedMessage);
      const okButton = buttons.find(b => b.text && b.text.toLowerCase() !== 'hủy' && b.text.toLowerCase() !== 'cancel');
      const cancelButton = buttons.find(b => b.text && (b.text.toLowerCase() === 'hủy' || b.text.toLowerCase() === 'cancel'));
      
      if (confirmed && okButton && typeof okButton.onPress === 'function') {
        okButton.onPress();
      } else if (!confirmed && cancelButton && typeof cancelButton.onPress === 'function') {
        cancelButton.onPress();
      }
    } else {
      alert(formattedMessage);
      if (buttons && buttons.length > 0 && typeof buttons[0].onPress === 'function') {
        buttons[0].onPress();
      }
    }
  } else {
    Alert.alert(title, message, buttons);
  }
};
