import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from '../services/api';
import useAuthStore from './useAuthStore';

const useCartStore = create(
  persist(
    (set, get) => ({
      items: [],

      addToCart: (product, quantity = 1, variant = null) => {
        const items = get().items;
        const productId = product.id_sanpham;
        const variantId = variant ? variant.id_bienthe : null;
        
        // Generate a unique identifier for items in the cart
        const cartItemId = variantId ? `${productId}_${variantId}` : `${productId}`;
        
        const existingItemIndex = items.findIndex(item => item.id === cartItemId);
        
        // Max stock from variant or product
        const maxStock = variant ? parseInt(variant.soluong || 0) : parseInt(product.so_luong || product.soluong || 999);

        let newItems;
        if (existingItemIndex > -1) {
          // If item already exists, update quantity (cap at maxStock)
          newItems = items.map((item, idx) => {
            if (idx === existingItemIndex) {
              const newQty = Math.min(item.quantity + quantity, item.maxStock || maxStock || 999);
              return { ...item, quantity: newQty };
            }
            return item;
          });
        } else {
          // Add new item
          const price = variant ? parseFloat(variant.gia) : (product.gia ? parseFloat(product.gia) : 0);
          newItems = [
            ...items,
            {
              id: cartItemId,
              productId,
              variantId,
              name: product.tenSP,
              variantName: variant ? variant.ten_bienthe : null,
              price: price,
              quantity: Math.min(quantity, maxStock),
              image: product.hinhanh,
              maxStock: maxStock,
            }
          ];
        }

        set({ items: newItems });
      },

      removeFromCart: (id) => {
        const items = get().items;
        const newItems = items.filter(item => item.id !== id);
        set({ items: newItems });
      },

      updateQuantity: (id, quantity) => {
        const items = get().items;
        // Do not allow quantity <= 0 (use explicit remove instead)
        if (quantity <= 0) {
          return;
        }

        const newItems = items.map(item => {
          if (item.id === id) {
            const cappedQty = Math.min(Math.max(1, quantity), item.maxStock || 999);
            return { ...item, quantity: cappedQty };
          }
          return item;
        });
        set({ items: newItems });
      },

      updateMaxStock: (id, maxStock) => {
        const items = get().items;
        const newItems = items.map(item => {
          if (item.id === id) {
            const cappedQty = Math.min(item.quantity, maxStock);
            return { ...item, maxStock, quantity: cappedQty };
          }
          return item;
        });
        set({ items: newItems });
      },

      replaceWithServerCart: (serverItems = []) => {
        const mappedItems = serverItems.map((item) => ({
          id: `server_${item.id_giohang}`,
          serverId: item.id_giohang,
          comboId: item.id_combo || null,
          comboGroupId: item.id_nhom_combo || null,
          comboName: item.ten_combo || null,
          productId: item.id_sanpham || null,
          variantId: item.id_bienthe,
          name: item.ten_san_pham || item.ten_combo || 'Sản phẩm',
          variantName: item.ten_bienthe || null,
          price: parseFloat(item.gia ?? item.gia_combo ?? 0),
          quantity: parseInt(item.soluong || 1, 10),
          image: item.hinh_anh || item.hinhanh_combo || null,
          maxStock: parseInt(item.ton_kho || 0, 10) + parseInt(item.soluong || 1, 10),
        }));
        set({ items: mappedItems });
      },

      fetchServerCart: async () => {
        if (!useAuthStore.getState().token) return get().items;
        const response = await api.get('/gio-hang');
        get().replaceWithServerCart(response.data?.gio_hang || []);
        return get().items;
      },

      syncLocalCartToServer: async () => {
        if (!useAuthStore.getState().token) return;
        const localItems = get().items.filter((item) => !item.serverId && item.variantId);
        for (const item of localItems) {
          await api.post('/gio-hang/them', {
            id_bienthe: item.variantId,
            soluong: item.quantity,
          });
        }
        await get().fetchServerCart();
      },

      addProductToServer: async (variantId, quantity = 1) => {
        await api.post('/gio-hang/them', { id_bienthe: variantId, soluong: quantity });
        await get().fetchServerCart();
      },

      addComboToServer: async (comboId, selectedVariants, quantity = 1) => {
        await api.post('/gio-hang/them-combo', {
          id_combo: comboId,
          selected_variants: selectedVariants,
          soluong: quantity,
        });
        await get().fetchServerCart();
      },

      updateServerQuantity: async (item, quantity) => {
        if (item.comboGroupId) {
          await api.put(`/gio-hang/cap-nhat-combo/${item.comboGroupId}`, { soluong: quantity });
        } else {
          await api.put(`/gio-hang/cap-nhat/${item.serverId}`, { soluong: quantity });
        }
        await get().fetchServerCart();
      },

      removeServerItem: async (item) => {
        if (item.comboGroupId) {
          await api.delete(`/gio-hang/xoa-combo/${item.comboGroupId}`);
        } else {
          await api.delete(`/gio-hang/xoa/${item.serverId}`);
        }
        await get().fetchServerCart();
      },

      clearCart: () => {
        set({ items: [] });
      },
    }),
    {
      name: 'cart-storage',
      storage: createJSONStorage(() => AsyncStorage),
    }
  )
);

export default useCartStore;
