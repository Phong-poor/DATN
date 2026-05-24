<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DatHangController;
use App\Http\Controllers\VnpayController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DanhMucController;
use App\Http\Controllers\ThuongHieuController;
use App\Http\Controllers\ThuocTinhController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\SanPhamController;
use App\Http\Controllers\BienTheController;
use App\Http\Controllers\BienTheHinhAnhController;
use App\Http\Controllers\GioHangController;
use App\Http\Controllers\YeuThichController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LienHeController;
use App\Http\Controllers\DanhGiaController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\DiaChiController;

Route::get('/auth/facebook', [AuthController::class, 'redirectFacebook']);
Route::get('/auth/facebook/callback', [AuthController::class, 'handleFacebook']);

Route::get('/auth/google', [AuthController::class, 'redirectGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogle']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ================= QUÊN MẬT KHẨU =================
Route::get('/vnpay/return', [VnpayController::class, 'vnpayReturn']);
Route::get('/vnpay/ipn', [VnpayController::class, 'handleIPN']);

Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
Route::post('/forgot-password/reset-password', [ForgotPasswordController::class, 'resetPassword']);

// ================= LIÊN HỆ (KHÁCH) =================
Route::get('/contacts', [LienHeController::class, 'index']);
Route::post('/lien-he', [LienHeController::class, 'store']);
Route::post('/contacts/{id}/reply', [LienHeController::class, 'reply']);

// ================= KHUYẾN MÃI (PUBLIC) =================
Route::get('/promotions', [PromotionController::class, 'index']);
Route::post('/apply-promo', [PromotionController::class, 'applyPromo']);

// ================= USER LOGIN =================
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);
    Route::post('/user/change-password/request-otp', [UserController::class, 'requestPasswordOTP']);
    Route::post('/user/change-password/verify-otp', [UserController::class, 'changePasswordWithOTP']);
    Route::get('/user/dia-chi', [DiaChiController::class, 'index']);
    Route::post('/user/dia-chi', [DiaChiController::class, 'store']);
    Route::put('/user/dia-chi/{id}', [DiaChiController::class, 'update']);
    Route::delete('/user/dia-chi/{id}', [DiaChiController::class, 'destroy']);
    Route::patch('/user/dia-chi/{id}/mac-dinh', [DiaChiController::class, 'setDefault']);

    // ===== GIỎ HÀNG =====
    Route::get('/gio-hang', [GioHangController::class, 'index']);
    Route::post('/gio-hang/them', [GioHangController::class, 'them']);
    Route::put('/gio-hang/cap-nhat/{id}', [GioHangController::class, 'capNhat']);
    Route::delete('/gio-hang/xoa-tat', [GioHangController::class, 'xoaTat']);
    Route::delete('/gio-hang/xoa/{id}', [GioHangController::class, 'xoa']);
    Route::get('/gio-hang/dem', [GioHangController::class, 'demSoLuong']);

    // ===== ĐẶT HÀNG =====
    Route::post('/checkout', [DatHangController::class, 'checkout']);
    Route::post('/orders/send-email/{id}', [DatHangController::class, 'sendSuccessEmail']);
    Route::get('/orders', [DatHangController::class, 'orders']);
    Route::post('/orders/{id}/cancel', [DatHangController::class, 'cancelOrder']);
    Route::post('/orders/{id}/reorder', [DatHangController::class, 'reorder']);

    // ===== YÊU THÍCH =====
    Route::get('/yeu-thich', [YeuThichController::class, 'index']);
    Route::post('/yeu-thich/them', [YeuThichController::class, 'them']);
    Route::put('/yeu-thich/cap-nhat/{id}', [YeuThichController::class, 'capNhat']);
    Route::delete('/yeu-thich/xoa/{id}', [YeuThichController::class, 'xoa']);

    // ===== ĐÁNH GIÁ =====
    Route::post('/danh-gia', [App\Http\Controllers\DanhGiaController::class, 'store']);

    // ===== VOUCHER CỦA TÔI =====
    Route::get('/user/vouchers', [PromotionController::class, 'myVouchers']);
    Route::get('/user/vouchers/available', [PromotionController::class, 'availableGifts']);
    Route::post('/user/vouchers/claim', [PromotionController::class, 'claimVoucher']);
});

// Route::get('/auth/google', [AuthController::class, 'redirectGoogle']);
// Route::get('/auth/google/callback', [AuthController::class, 'handleGoogle']);


Route::get('/danhmuc', [DanhMucController::class, 'index']);
Route::post('/danhmuc', [DanhMucController::class, 'store']);
Route::get('/danhmuc/{id_danhmuc}', [DanhMucController::class, 'show']);
Route::put('/danhmuc/{id_danhmuc}', [DanhMucController::class, 'update']);
Route::delete('/danhmuc/{id_danhmuc}', [DanhMucController::class, 'destroy']);

// ================= THƯƠNG HIỆU =================
Route::get('/thuonghieu', [ThuongHieuController::class, 'index']);
Route::post('/thuonghieu', [ThuongHieuController::class, 'store']);
Route::get('/thuonghieu/{id_thuonghieu}', [ThuongHieuController::class, 'show']);
Route::put('/thuonghieu/{id_thuonghieu}', [ThuongHieuController::class, 'update']);
Route::delete('/thuonghieu/{id_thuonghieu}', [ThuongHieuController::class, 'destroy']);


// Route::post('/register', [UserController::class, 'store']);
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

// ================= THUỘC TÍNH =================
Route::get('/nhomthuoctinh', [ThuocTinhController::class, 'getNhom']);
Route::post('/nhomthuoctinh', [ThuocTinhController::class, 'addNhom']);
Route::put('/nhomthuoctinh/{id}', [ThuocTinhController::class, 'updateNhom']);
Route::delete('/nhomthuoctinh/{id}', [ThuocTinhController::class, 'deleteNhom']);

Route::get('/thuoctinh', [ThuocTinhController::class, 'getThuocTinh']);
Route::post('/thuoctinh', [ThuocTinhController::class, 'addThuocTinh']);
Route::put('/thuoctinh/{id}', [ThuocTinhController::class, 'updateThuocTinh']);
Route::delete('/thuoctinh/{id}', [ThuocTinhController::class, 'deleteThuocTinh']);

Route::get('/giatrithuoctinh/{id}', [ThuocTinhController::class, 'getGiaTri']);
Route::post('/giatrithuoctinh', [ThuocTinhController::class, 'addGiaTri']);
Route::put('/giatrithuoctinh/{id}', [ThuocTinhController::class, 'updateGiaTri']);
Route::delete('/giatrithuoctinh/{id}', [ThuocTinhController::class, 'deleteGiaTri']);

Route::get('/thuoctinh-all', [ThuocTinhController::class, 'getAll']);

// ================= COLOR =================
Route::get('/colors', [ColorController::class, 'index']);
Route::post('/colors', [ColorController::class, 'store']);
Route::get('/colors/{id}', [ColorController::class, 'show']);
Route::put('/colors/{id}', [ColorController::class, 'update']);
Route::delete('/colors/{id}', [ColorController::class, 'destroy']);

// ================= SẢN PHẨM =================
Route::get('/sanpham/search', [SanPhamController::class, 'search']);
Route::get('/sanpham/attribute-options', [SanPhamController::class, 'attributeOptions']);
Route::get('/sanpham', [SanPhamController::class, 'index']);
Route::post('/sanpham', [SanPhamController::class, 'store']);
Route::get('/sanpham/{id}', [SanPhamController::class, 'show']);
Route::put('/sanpham/{id}', [SanPhamController::class, 'update']);
Route::delete('/sanpham/{id}', [SanPhamController::class, 'destroy']);

// ================= BIẾN THỂ =================
Route::get('/bienthe', [BienTheController::class, 'index']);
Route::get('/bienthe/sanpham/{id_sanpham}', [BienTheController::class, 'getBySanPham']);
Route::get('/bienthe/{id}', [BienTheController::class, 'show']);
Route::post('/bienthe', [BienTheController::class, 'store']);
Route::put('/bienthe/{id}', [BienTheController::class, 'update']);
Route::delete('/bienthe/{id}', [BienTheController::class, 'destroy']);

// ================= HÌNH ẢNH =================
Route::get('/bienthe-hinhanh', [BienTheHinhAnhController::class, 'index']);
Route::get('/bienthe-hinhanh/sanpham/{id_sanpham}', [BienTheHinhAnhController::class, 'getBySanPham']);
Route::get('/bienthe-hinhanh/{id}', [BienTheHinhAnhController::class, 'show']);
Route::post('/bienthe-hinhanh', [BienTheHinhAnhController::class, 'store']);
Route::put('/bienthe-hinhanh/{id}', [BienTheHinhAnhController::class, 'update']);
Route::delete('/bienthe-hinhanh/{id}', [BienTheHinhAnhController::class, 'destroy']);

// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register', [AuthController::class, 'register']);

// ================= TEST =================
Route::get('/test', function () {
    return 'OK API';
});

use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {
    Mail::raw('Test gửi mail thành công', function ($msg) {
        $msg->to('machquanlac5@gmail.com')
            ->subject('Test Mail Laravel');
    });

    return 'OK';
});
// ================= CHATBOT =================
Route::post('/chat', [ChatbotController::class, 'chat']);

// ================= ADMIN =================
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {
        // ===== DASHBOARD =====
        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::get('/sanpham/export-inventory', [SanPhamController::class, 'exportInventory']);
        Route::post('/sanpham/import-stock', [SanPhamController::class, 'importStock']);
        // ===== ADMIN ORDERS =====
        Route::get('/orders', [DatHangController::class, 'allOrders']);
        Route::put('/orders/{id}/status', [DatHangController::class, 'updateStatus']);

        // ===== LIÊN HỆ ADMIN =====
        Route::get('/lien-he', [LienHeController::class, 'index']);
        Route::post('/lien-he/reply/{id}', [LienHeController::class, 'reply']);
        Route::delete('/contacts/{id}', [LienHeController::class, 'destroy']);
        Route::get('/reviews', [App\Http\Controllers\DanhGiaController::class, 'adminIndex']);

        Route::post('/apply-promo', [PromotionController::class, 'applyPromo']);
        Route::apiResource('promotions', PromotionController::class);
        Route::get('/promotions', [PromotionController::class, 'index']);
        Route::post('/promotions', [PromotionController::class, 'store']);
        Route::put('/promotions/{id}', [PromotionController::class, 'update']);
        Route::delete('/promotions/{id}', [PromotionController::class, 'destroy']);
        Route::put('/reviews/{id}/status', [App\Http\Controllers\DanhGiaController::class, 'updateStatus']);
        Route::delete('/reviews/{id}', [App\Http\Controllers\DanhGiaController::class, 'destroy']);
        Route::get('/sanpham/{id}/reviews', [App\Http\Controllers\DanhGiaController::class, 'index']);



        // ===== ADMIN REVIEWS =====
        Route::get('/reviews', [DanhGiaController::class, 'adminIndex']);
        Route::put('/reviews/{id}/status', [DanhGiaController::class, 'updateStatus']);
        Route::delete('/reviews/{id}', [DanhGiaController::class, 'destroy']);

});
