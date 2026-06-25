import { getUser } from '@/services/auth';

export default function adminGuard(to, from, next) {
  const user = getUser();

  if (!user) {
    return next("/login");
  }

  if (user.vaitro === "user") {
    return next("/");
  }

  const role = String(user.vaitro || '').toLowerCase();
  if (role !== 'admin') {
    const rolePermissions = {
      inventory: ['/admin', '/admin/products', '/admin/categories', '/admin/brands', '/admin/variants', '/admin/profile', '/admin/settings'],
      order_manager: ['/admin', '/admin/orders', '/admin/profile', '/admin/settings'],
      marketing: ['/admin', '/admin/promotions', '/admin/birthday-codes', '/admin/combos', '/admin/flash-sale', '/admin/profile', '/admin/settings'],
      affiliate_manager: ['/admin', '/admin/affiliates', '/admin/profile', '/admin/settings'],
      editor: ['/admin', '/admin/news', '/admin/reviews', '/admin/banners', '/admin/profile', '/admin/settings'],
      support: ['/admin', '/admin/contacts', '/admin/profile', '/admin/settings'],
      accountant: ['/admin', '/admin/orders', '/admin/profile', '/admin/settings'],
    };

    const allowedPaths = rolePermissions[role] || [];
    const isAllowed = allowedPaths.some(path => {
      if (path === '/admin') {
        return to.path === '/admin';
      }
      return to.path === path || to.path.startsWith(path + '/');
    });

    if (!isAllowed) {
      return next(false);
    }
  }

  return next();
}