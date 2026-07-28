import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';
import axios from 'axios';
import { Platform } from 'react-native';
import logger from '../utils/logger';
import { API_BASE_URL } from '../config/network';

const useAuthStore = create(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      loading: false,
      error: null,

      login: async (email, password) => {
        set({ loading: true, error: null });
        try {
          const response = await axios.post(`${API_BASE_URL}/login`, {
            email,
            password,
          }, {
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            }
          });

          const { token, user } = response.data;
          set({ token, user, loading: false });
          return { success: true };
        } catch (error) {
          let message = 'Đăng nhập thất bại. Vui lòng kiểm tra lại.';
          if (error.response && error.response.data && error.response.data.message) {
            message = error.response.data.message;
          }
          set({ error: message, loading: false });
          return { success: false, error: message };
        }
      },

      register: async (ten, email, sodienthoai, password, password_confirmation) => {
        set({ loading: true, error: null });
        try {
          const response = await axios.post(`${API_BASE_URL}/register`, {
            ten,
            email,
            sodienthoai,
            password,
            password_confirmation,
          }, {
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            }
          });

          set({ loading: false });
          return { success: true, message: response.data.message || 'Đăng ký thành công!' };
        } catch (error) {
          let message = 'Đăng ký thất bại. Vui lòng thử lại.';
          if (error.response && error.response.data && error.response.data.message) {
            message = error.response.data.message;
          }
          set({ error: message, loading: false });
          return { success: false, error: message };
        }
      },

      logout: async () => {
        const token = get().token;
        set({ loading: true });
        
        // Try calling the logout API, ignore errors to ensure we log out locally anyway
        if (token) {
          try {
            await axios.post(`${API_BASE_URL}/logout`, {}, {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
              }
            });
          } catch (e) {
            logger.log('API logout failed, logging out locally', e);
          }
        }

        get().logoutLocal();
      },

      logoutLocal: () => {
        set({ user: null, token: null, loading: false, error: null });
      },

      checkSession: async () => {
        const token = get().token;
        if (!token) return null;

        try {
          const response = await axios.get(`${API_BASE_URL}/auth/session`, {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/json',
            }
          });
          if (response.data && response.data.user) {
            const sessionUser = response.data.user;
            set({ user: sessionUser });
            return sessionUser;
          }
        } catch (error) {
          if (error.response && (error.response.status === 401 || error.response.status === 423)) {
            // Token expired or account locked
            get().logoutLocal();
          }
        }

        return null;
      },

      clearError: () => set({ error: null }),

      completeSocialLogin: async (token) => {
        if (!token) return { success: false };
        set({ token, loading: true, error: null });
        try {
          const response = await axios.get(`${API_BASE_URL}/auth/session`, {
            headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
          });
          set({ user: response.data?.user || null, loading: false });
          return { success: !!response.data?.user };
        } catch (error) {
          set({ token: null, user: null, loading: false, error: 'Không thể hoàn tất đăng nhập Google.' });
          return { success: false };
        }
      },

      uploadAvatar: async (uri) => {
        const token = get().token;
        if (!token) return { success: false, error: 'Chưa đăng nhập' };
        
        set({ loading: true, error: null });
        try {
          const formData = new FormData();
          
          if (Platform.OS === 'web') {
            if (uri instanceof Blob) {
              formData.append('avatar', uri, 'avatar.jpg');
            } else {
              const response = await fetch(uri);
              const blob = await response.blob();
              formData.append('avatar', blob, 'avatar.jpg');
            }
          } else {
            const filename = uri.split('/').pop();
            const match = /\.(\w+)$/.exec(filename || '');
            const type = match ? `image/${match[1]}` : `image`;
            formData.append('avatar', {
              uri,
              name: filename,
              type,
            });
          }

          const response = await axios.post(`${API_BASE_URL}/user/avatar`, formData, {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'multipart/form-data',
              'Accept': 'application/json',
            }
          });

          const updatedUser = response.data.user;
          set({ user: updatedUser, loading: false });
          return { success: true, user: updatedUser };
        } catch (error) {
          let message = 'Tải ảnh đại diện thất bại.';
          if (error.response && error.response.data && error.response.data.message) {
            message = error.response.data.message;
          }
          set({ error: message, loading: false });
          return { success: false, error: message };
        }
      },
    }),
    {
      name: 'auth-storage',
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (state) => ({ user: state.user, token: state.token }), // only save user and token
    }
  )
);

export default useAuthStore;
