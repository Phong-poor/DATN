import { getUser } from '@/services/auth';

export default function adminGuard(to, from, next) {
  const user = getUser();

  if (!user) {
    return next("/login");
  }

  if (user.role !== "admin") {
    return next("/");
  }

  return next();
}