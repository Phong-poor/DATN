import { create } from 'zustand';

/**
 * Global Toast Notification Store
 */
const useToastStore = create((set, get) => {
  let timeoutId = null;

  return {
    visible: false,
    message: '',
    type: 'success', // 'success' | 'error' | 'info'
    actions: null, // array of { text, onPress }

    show: (message, type = 'success', duration = 4000, actions = null) => {
      // Clear any existing auto-hide timeout
      if (timeoutId) {
        clearTimeout(timeoutId);
      }

      set({ visible: true, message, type, actions });

      // Set new auto-hide timeout
      timeoutId = setTimeout(() => {
        set({ visible: false, actions: null });
        timeoutId = null;
      }, duration);
    },

    hide: () => {
      if (timeoutId) {
        clearTimeout(timeoutId);
        timeoutId = null;
      }
      set({ visible: false, actions: null });
    },
  };
});

export default useToastStore;
