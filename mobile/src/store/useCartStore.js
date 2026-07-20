import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';

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
