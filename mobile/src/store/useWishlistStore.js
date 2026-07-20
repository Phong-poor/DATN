import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from '../services/api';
import useAuthStore from './useAuthStore';
import { logger } from '../utils/logger';

const useWishlistStore = create(
  persist(
    (set, get) => ({
      items: [], // Array of products

      fetchWishlist: async () => {
        const token = useAuthStore.getState().token;
        if (!token) return;
        try {
          const res = await api.get('/yeu-thich');
          if (res.data && res.data.data) {
            // Normalize backend structure to product structure
            const normalized = res.data.data.map(item => {
              const bienthe = item.bienthe;
              const sanpham = bienthe?.sanpham;
              return {
                id_yeuthich: item.id, // Keep the relation ID for deletion
                id_sanpham: sanpham?.id_sanpham || item.id_sanpham,
                tenSP: sanpham?.tenSP || 'Sản phẩm',
                hinhanh: sanpham?.hinhanh,
                bien_thes: bienthe ? [bienthe] : [],
                thuong_hieu: sanpham?.thuong_hieu,
              };
            });
            set({ items: normalized });
          }
        } catch (err) {
          logger.log('Failed to fetch wishlist:', err);
        }
      },

      addToWishlist: async (product) => {
        const items = get().items;
        const exists = items.some(item => item.id_sanpham === product.id_sanpham);
        if (exists) return;

        // Add to local state first for instant UI response
        const newItem = {
          ...product,
          id_yeuthich: null, // Temporary
        };
        set({ items: [...items, newItem] });

        // Sync with backend if logged in
        const token = useAuthStore.getState().token;
        if (token) {
          try {
            const variantId = product.bien_thes && product.bien_thes.length > 0
              ? product.bien_thes[0].id_bienthe
              : null;
              
            if (!variantId) {
              logger.log('No variant ID found for wishlist item');
              return;
            }

            const res = await api.post('/yeu-thich/them', {
              id_bienthe: variantId,
              soluong: 1,
            });
            
            if (res.data.status === 'success') {
              // Re-fetch to get correct backend relation IDs (id_yeuthich)
              get().fetchWishlist();
            }
          } catch (err) {
            logger.log('Failed to add to wishlist backend:', err);
          }
        }
      },

      removeFromWishlist: async (productId) => {
        const items = get().items;
        const itemToRemove = items.find(item => item.id_sanpham === productId);
        if (!itemToRemove) return;

        set({ items: items.filter(item => item.id_sanpham !== productId) });

        // Sync with backend if logged in and we have the relation ID
        const token = useAuthStore.getState().token;
        const relationId = itemToRemove.id_yeuthich;
        
        if (token) {
          try {
            let deleteId = relationId;
            
            // If we don't have the relation ID yet, we need to fetch the wishlist first
            if (!deleteId) {
              const res = await api.get('/yeu-thich');
              const found = res.data?.data?.find(item => item.bienthe?.id_sanpham === productId);
              if (found) {
                deleteId = found.id;
              }
            }

            if (deleteId) {
              await api.delete(`/yeu-thich/xoa/${deleteId}`);
            }
          } catch (err) {
            logger.log('Failed to remove from wishlist backend:', err);
          }
        }
      },

      isInWishlist: (productId) => {
        return get().items.some(item => item.id_sanpham === productId);
      },
    }),
    {
      name: 'wishlist-storage',
      storage: createJSONStorage(() => AsyncStorage),
    }
  )
);

export default useWishlistStore;
