import { getUser } from '@/services/auth';

const pathPermissionMap = {
  '/admin/quan-ly-san-pham': 'san_pham_xem',
  '/admin/products': 'san_pham_xem',
  
  '/admin/quan-ly-danh-muc': 'danh_muc_xem',
  '/admin/categories': 'danh_muc_xem',
  
  '/admin/quan-ly-thuong-hieu': 'thuong_hieu_xem',
  '/admin/brands': 'thuong_hieu_xem',
  
  '/admin/bien-the': 'bien_the_xem',
  '/admin/variants': 'bien_the_xem',
  
  '/admin/quan-ly-don-hang': 'don_hang_xem',
  '/admin/orders': 'don_hang_xem',
  
  '/admin/hoa-don': 'hoa_don_xem',
  '/admin/billing': 'hoa_don_xem',
  
  '/admin/quan-ly-khuyen-mai': 'marketing_quan_ly',
  '/admin/promotions': 'marketing_quan_ly',
  '/admin/gui-ma-sinh-nhat': 'marketing_quan_ly',
  '/admin/birthdays': 'marketing_quan_ly',
  '/admin/birthday-codes': 'marketing_quan_ly',
  '/admin/combos': 'marketing_quan_ly',
  '/admin/flash-sales': 'marketing_quan_ly',
  '/admin/flash-sale': 'marketing_quan_ly',
  
  '/admin/quan-ly-tiep-thi': 'affiliate_quan_ly',
  '/admin/affiliates': 'affiliate_quan_ly',
  
  '/admin/quan-ly-tin-tuc': 'tin_tuc_quan_ly',
  '/admin/news': 'tin_tuc_quan_ly',
  
  '/admin/reviews': 'binh_luan_quan_ly',
  
  '/admin/quan-ly-banner': 'banner_quan_ly',
  '/admin/banners': 'banner_quan_ly',
  
  '/admin/quan-ly-lien-he': 'lien_he_quan_ly',
  '/admin/contacts': 'lien_he_quan_ly',
  
  '/admin/quan-ly-nguoi-dung': 'tai_khoan_quan_ly',
  '/admin/users': 'tai_khoan_quan_ly',
  
  '/admin/quan-ly-vai-tro': 'vai_tro_quan_ly',
  '/admin/roles': 'vai_tro_quan_ly',
  '/admin/vaitro': 'vai_tro_quan_ly',
  
  '/admin/nhat-ky-hoat-dong': 'nhat_ky_quan_ly',
  '/admin/activity-log': 'nhat_ky_quan_ly',
}

const basicPaths = [
  '/admin',
  '/admin/bang-dieu-khien',
  '/admin/ho-so-quan-tri',
  '/admin/profile',
  '/admin/cai-dat-he-thong',
  '/admin/settings'
]

export default function adminGuard(to, from, next) {
  const user = getUser();

  if (!user) {
    return next("/dang-nhap");
  }

  const role = String(user.vaitro || user.role || '').toLowerCase();

  if (role === "user") {
    return next("/");
  }

  if (!role) {
    return next("/");
  }

  // Tất cả vai trò nhân viên (khác user) có toàn quyền vào khu vực quản trị.
  return next();
}
