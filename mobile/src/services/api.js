import axios from 'axios';
import { Platform } from 'react-native';
import useAuthStore from '../store/useAuthStore';

// Determine the base API and Media URL depending on the running platform
const getBaseUrl = () => {
  if (Platform.OS === 'web') {
    return 'http://127.0.0.1:8000/api';
  }
  if (Platform.OS === 'android') {
    // 10.0.2.2 is the IP of the host machine in Android Emulator
    return 'http://10.0.2.2:8000/api';
  }
  return 'http://127.0.0.1:8000/api';
};

const getMediaUrl = () => {
  if (Platform.OS === 'web') {
    return 'http://127.0.0.1:8000';
  }
  if (Platform.OS === 'android') {
    return 'http://10.0.2.2:8000';
  }
  return 'http://127.0.0.1:8000';
};

export const API_BASE_URL = getBaseUrl();
export const MEDIA_BASE_URL = getMediaUrl();

const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000, // 30 seconds - allows for cold start of local dev server
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});

// Request Interceptor: Automatically attach the Bearer token if it exists in auth store
api.interceptors.request.use(
  (config) => {
    const token = useAuthStore.getState().token;
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response Interceptor: Automatically log out if 401 Unauthorized occurs (token expired/invalid)
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;
    
    if (error.response && error.response.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      
      // Clear token and user from auth store (log out)
      useAuthStore.getState().logoutLocal();
    }
    return Promise.reject(error);
  }
);

// Helper function to resolve media URL
export const getImageUrl = (path) => {
  if (!path) return null;
  if (path.startsWith('http://') || path.startsWith('https://')) {
    if (Platform.OS === 'android') {
      return path.replace('localhost', '10.0.2.2').replace('127.0.0.1', '10.0.2.2');
    }
    return path;
  }
  
  const cleanPath = path.replace(/^\/+/, '');
  if (cleanPath.startsWith('storage/')) {
    return `${MEDIA_BASE_URL}/${cleanPath}`;
  }
  return `${MEDIA_BASE_URL}/storage/${cleanPath}`;
};

export default api;
