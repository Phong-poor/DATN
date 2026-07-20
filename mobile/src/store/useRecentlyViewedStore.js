import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';

const useRecentlyViewedStore = create(
  persist(
    (set, get) => ({
      items: [], // array of products

      addProduct: (product) => {
        if (!product) return;
        const currentItems = get().items;
        // Remove existing occurrence to prevent duplicates
        const filtered = currentItems.filter(item => item.id_sanpham !== product.id_sanpham);
        // Prepend product and limit to max 10 items
        const updated = [product, ...filtered].slice(0, 10);
        set({ items: updated });
      },

      clearRecentlyViewed: () => {
        set({ items: [] });
      }
    }),
    {
      name: 'recently-viewed-storage',
      storage: createJSONStorage(() => AsyncStorage),
    }
  )
);

export default useRecentlyViewedStore;
