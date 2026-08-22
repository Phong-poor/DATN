<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        if ($user->trangthai === 'locked') {
            return response()->json([
                'message' => 'Tài khoản của bạn đã bị khóa.',
                'code' => 'ACCOUNT_LOCKED',
            ], 423);
        }

        // Khách hàng không có quyền vào admin
        if ($user->vaitro === 'user') {
            return response()->json([
                'message' => 'Bạn không có quyền vào trang admin'
            ], 403);
        }

        // Chỉ quản trị viên tối cao mới có toàn quyền. Các vai trò nhân viên
        // phải tiếp tục đi qua bảng ánh xạ quyền ở bên dưới.
        if ($user->vaitro === 'admin') {
            return $next($request);
        }

        $path = $request->getPathInfo();
        $method = $request->getMethod();
        $userPerms = $user->cac_quyen ?: [];

        // Các route cơ bản ai cũng được vào (Dashboard, Xem/Sửa Profile cá nhân, Nhật ký thông báo cá nhân)
        $basicPaths = [
            '/api/admin/dashboard',
            '/api/admin/account/profile',
            '/api/admin/account/active-admins',
            '/api/admin/account/activity-log'
        ];
        
        foreach ($basicPaths as $bp) {
            if ($path === $bp || str_starts_with($path, $bp . '/')) {
                return $next($request);
            }
        }

        $requiredPermission = null;

        // 1. NHẬP/XUẤT KHO & SẢN PHẨM
        if ($path === '/api/admin/sanpham/export-inventory') {
            $requiredPermission = 'san_pham_xem';
        } elseif ($path === '/api/admin/sanpham/import-stock') {
            $requiredPermission = 'nhap_xuat_kho';
        } elseif ($path === '/api/admin/sanpham' || str_starts_with($path, '/api/admin/sanpham/')) {
            $requiredPermission = ($method === 'GET') ? 'san_pham_xem' : 'san_pham_sua';
        }
        
        // 2. DANH MỤC
        elseif ($path === '/api/admin/danhmuc' || str_starts_with($path, '/api/admin/danhmuc/') ||
                  $path === '/api/admin/danhmuc-cha' || str_starts_with($path, '/api/admin/danhmuc-cha/')) {
            $requiredPermission = ($method === 'GET') ? 'san_pham_xem' : 'danh_muc_sua';
        }
        
        // 3. THƯƠNG HIỆU
        elseif ($path === '/api/admin/thuonghieu' || str_starts_with($path, '/api/admin/thuonghieu/')) {
            $requiredPermission = ($method === 'GET') ? 'san_pham_xem' : 'thuong_hieu_sua';
        }
        
        // 4. BIẾN THỂ & THUỘC TÍNH (colors, bienthe, thuoctinh, giatrithuoctinh, nhomthuoctinh)
        elseif ($path === '/api/admin/bienthe' || str_starts_with($path, '/api/admin/bienthe/') ||
                  $path === '/api/admin/bienthe-hinhanh' || str_starts_with($path, '/api/admin/bienthe-hinhanh/') ||
                  $path === '/api/admin/colors' || str_starts_with($path, '/api/admin/colors/') ||
                  $path === '/api/admin/thuoctinh' || str_starts_with($path, '/api/admin/thuoctinh/') ||
                  $path === '/api/admin/giatrithuoctinh' || str_starts_with($path, '/api/admin/giatrithuoctinh/') ||
                  $path === '/api/admin/nhomthuoctinh' || str_starts_with($path, '/api/admin/nhomthuoctinh/')) {
            $requiredPermission = ($method === 'GET') ? 'san_pham_xem' : 'bien_the_sua';
        }
        
        // 5. ĐƠN HÀNG & DUYỆT ĐƠN
        elseif ($path === '/api/admin/orders' || str_starts_with($path, '/api/admin/orders/')) {
            $requiredPermission = ($method === 'GET') ? 'don_hang_xem' : 'don_hang_sua';
        }
        
        // 6. THỐNG KÊ DOANH THU & BILLING
        elseif ($path === '/api/admin/account/billing' || str_starts_with($path, '/api/admin/account/billing/')) {
            $requiredPermission = 'hoa_don_xem';
        }
        
        // 7. MARKETING, COUPON, COMBO, FLASH SALE
        elseif ($path === '/api/admin/promotions' || str_starts_with($path, '/api/admin/promotions/') ||
                  $path === '/api/admin/apply-promo' ||
                  $path === '/api/admin/combos' || str_starts_with($path, '/api/admin/combos/') ||
                  $path === '/api/admin/combo-offers' || str_starts_with($path, '/api/admin/combo-offers/') ||
                  $path === '/api/admin/flash-sales' || str_starts_with($path, '/api/admin/flash-sales/') ||
                  $path === '/api/admin/birthday-codes' || str_starts_with($path, '/api/admin/birthday-codes/')) {
            $requiredPermission = 'marketing_quan_ly';
        }
        
        // 8. TẤT CẢ AFFILIATE
        elseif ($path === '/api/admin/affiliates' || str_starts_with($path, '/api/admin/affiliates/') ||
                  $path === '/api/admin/affiliate-profiles' || str_starts_with($path, '/api/admin/affiliate-profiles/') ||
                  $path === '/api/admin/affiliate-commissions' || str_starts_with($path, '/api/admin/affiliate-commissions/') ||
                  $path === '/api/admin/affiliate-withdraws' || str_starts_with($path, '/api/admin/affiliate-withdraws/')) {
            $requiredPermission = 'affiliate_quan_ly';
        }
        
        // 9. TIN TỨC & BÀI VIẾT
        elseif ($path === '/api/admin/news' || str_starts_with($path, '/api/admin/news/') ||
                  $path === '/api/admin/news-stats' ||
                  $path === '/api/admin/news/upload-image') {
            $requiredPermission = 'tin_tuc_quan_ly';
        }
        
        // 10. BÌNH LUẬN & ĐÁNH GIÁ (reviews)
        elseif ($path === '/api/admin/reviews' || str_starts_with($path, '/api/admin/reviews/')) {
            $requiredPermission = 'binh_luan_quan_ly';
        }
        
        // 11. BANNERS
        elseif ($path === '/api/admin/banners' || str_starts_with($path, '/api/admin/banners/')) {
            $requiredPermission = 'banner_quan_ly';
        }
        
        // 12. TƯ VẤN & LIÊN HỆ
        elseif ($path === '/api/admin/lien-he' || str_starts_with($path, '/api/admin/lien-he/') ||
                  $path === '/api/admin/contacts' || str_starts_with($path, '/api/admin/contacts/')) {
            $requiredPermission = 'lien_he_quan_ly';
        }
        elseif ($path === '/api/admin/chat' || str_starts_with($path, '/api/admin/chat/') ||
                  $path === '/api/admin/conversations' || str_starts_with($path, '/api/admin/conversations/')) {
            $requiredPermission = 'chat_quan_ly';
        }
        
        // 13. QUẢN LÝ TÀI KHOẢN (users)
        elseif ($path === '/api/admin/users' || str_starts_with($path, '/api/admin/users/')) {
            $requiredPermission = 'tai_khoan_quan_ly';
        }
        
        // 14. QUẢN LÝ VAI TRÒ (vaitro)
        elseif ($path === '/api/admin/vaitro' || str_starts_with($path, '/api/admin/vaitro/')) {
            $requiredPermission = 'vai_tro_quan_ly';
        }
        
        // 15. NHẬT KÝ HOẠT ĐỘNG TOÀN HỆ THỐNG
        elseif ($path === '/api/admin/account/system-activity-logs') {
            $requiredPermission = 'nhat_ky_quan_ly';
        }

        // 16. CẤU HÌNH HỆ THỐNG XU
        elseif ($path === '/api/admin/xu/settings' || str_starts_with($path, '/api/admin/xu/settings/')) {
            $requiredPermission = 'xu_quan_ly';
        }

        // 17. QUẢN LÝ VÒNG QUAY MAY MẮN
        elseif ($path === '/api/admin/vong-quay' || str_starts_with($path, '/api/admin/vong-quay/')) {
            $requiredPermission = 'vong_quay_quan_ly';
        }

        // 18. QUẢN LÝ ĐIỂM DANH HÀNG NGÀY
        elseif ($path === '/api/admin/diem-danh' || str_starts_with($path, '/api/admin/diem-danh/')) {
            $requiredPermission = 'diem_danh_quan_ly';
        }

        // Nếu xác định được quyền yêu cầu nhưng user không có quyền đó -> chặn
        if ($requiredPermission && !in_array($requiredPermission, $userPerms)) {
            return response()->json([
                'message' => 'Tài khoản của bạn không có quyền thực hiện hành động này!'
            ], 403);
        }

        // Fail closed: một API admin mới chưa được ánh xạ quyền không được tự
        // động mở cho mọi nhân viên.
        if (!$requiredPermission) {
            return response()->json([
                'message' => 'Tài khoản của bạn không có quyền truy cập chức năng này!'
            ], 403);
        }

        return $next($request);
    }
}
