import axios from 'axios';
import useAuthStore from '../store/useAuthStore';
import { API_BASE_URL, MEDIA_BASE_URL } from '../config/network';

export { API_BASE_URL, MEDIA_BASE_URL };

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
    const storagePath = path.match(/\/storage\/.*$/i);
    if (storagePath) {
      return `${MEDIA_BASE_URL}${storagePath[0]}`;
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
