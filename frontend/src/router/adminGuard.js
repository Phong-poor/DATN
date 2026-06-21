import { getUser } from '@/services/auth';

export default function adminGuard(to, from, next) {
  const user = getUser();

  if (!user) {
    return next("/login");
  }

  if (user.vaitro !== "admin") {
    return next("/");
  }

  return next();
}