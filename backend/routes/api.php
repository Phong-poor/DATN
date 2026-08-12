<?php

use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminAffiliateController;
use App\Http\Controllers\AdminTwoFactorController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\AffiliateVideoController;
use App\Http\Controllers\Api\AffiliateWalletController;
use App\Http\Controllers\Api\AffiliateWithdrawalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BienTheController;
use App\Http\Controllers\BienTheHinhAnhController;
use App\Http\Controllers\BirthdayCodeController;
use App\Http\Controllers\ChamCongController;
use App\Http\Controllers\DonXinNghiController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\ComboController;
use App\Http\Controllers\DanhGiaController;
use App\Http\Controllers\DanhMucChaController;
use App\Http\Controllers\DanhMucController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatHangController;
use App\Http\Controllers\DiaChiController;
use App\Http\Controllers\DiemDanhController;
use App\Http\Controllers\FlashSaleController;
use App\Http\Controllers\FlashSaleWebController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GeocodeController;
use App\Http\Controllers\GioHangController;
use App\Http\Controllers\LienHeController;
use App\Http\Controllers\MomoController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\SanPhamController;
use App\Http\Controllers\SanPhamDaXemController;
use App\Http\Controllers\SepayController;
use App\Http\Controllers\ThuocTinhController;
use App\Http\Controllers\ThuongHieuController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VaiTroController;
use App\Http\Controllers\VnpayController;
use App\Http\Controllers\VongQuayController;
use App\Http\Controllers\XuController;
use App\Http\Controllers\YeuThichController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Geocode routes moved inside auth:sanctum
Route::get('/auth/google', [AuthController::class, 'redirectGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogle']);

Route::get('/refund-file', [DatHangController::class, 'getRefundFile']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/two-factor/challenge', [AuthController::class, 'verifyTwoFactorChallenge'])->middleware('throttle:5,1');
Route::post('/register', [AuthController::class, 'register']);

// ================= QUÊN MẬT KHẨU =================
Route::get('/vnpay/return', [VnpayController::class, 'vnpayReturn']);
Route::get('/vnpay/ipn', [VnpayController::class, 'handleIPN']);
Route::get('/momo/return', [MomoController::class, 'momoReturn']);
Route::post('/momo/ipn', [MomoController::class, 'momoIpn']);
Route::post('/sepay/webhook', [SepayController::class, 'webhook']);

Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
Route::get('/forgot-password/captcha', [ForgotPasswordController::class, 'captcha']);
Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
Route::post('/forgot-password/reset-password', [ForgotPasswordController::class, 'resetPassword']);

// Mobile-specific forgot password (no captcha required)
Route::post('/mobile/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtpMobile']);
Route::post('/mobile/forgot-password/reset-password', [ForgotPasswordController::class, 'resetPasswordMobile']);

// ================= LIÊN HỆ (KHÁCH) =================
Route::post('/lien-he', [LienHeController::class, 'store']);
Route::post('/consultation-requests', [LienHeController::class, 'storeConsultation']);
Route::post('/showroom-appointments', [LienHeController::class, 'storeAppointment']);

// ================= KHUYẾN MÃI (PUBLIC) =================
Route::get('/promotions', [PromotionController::class, 'index']);
Route::post('/apply-promo', [PromotionController::class, 'applyPromo']);
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{id}', [NewsController::class, 'show']);
Route::get('/news-tags', [NewsController::class, 'tags']);
Route::get('/news-feed.xml', [NewsController::class, 'feed']);
Route::get('/news-sitemap.xml', [NewsController::class, 'sitemap']);
Route::post('/news/{id}/track', [NewsController::class, 'track'])->whereNumber('id');
Route::post('/news-subscribe', [NewsController::class, 'subscribe']);
Route::get('/banners', [BannerController::class, 'index']);
Route::get('/flash-sale/current', [FlashSaleWebController::class, 'getCurrentSession']);
Route::get('/vong-quay/prizes', [VongQuayController::class, 'prizes']);

// Ảnh chat phục vụ qua API, không phụ thuộc symlink storage/public.
Route::get('/chat/attachments/{filename}', [ChatController::class, 'serveAttachment'])
    ->where('filename', '[A-Za-z0-9._-]+');

// ================= USER LOGIN =================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/session', function (Request $request) {
        $user = $request->user();

        if ($user && $user->trangthai === 'locked') {
            return response()->json([
                'message' => 'Tài khoản của bạn đã bị khóa.',
                'code' => 'ACCOUNT_LOCKED',
            ], 423);
        }

        return response()->json([
            'authenticated' => true,
            'user' => $user ? array_merge($user->toArray(), [
                'is_google_account' => ! empty($user->id_google),
            ]) : null,
        ]);
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware(['throttle:60,1'])->group(function () {
        Route::get('/address/suggestions', [GeocodeController::class, 'suggestions']);
        Route::get('/address/geocode', [GeocodeController::class, 'geocode']);
        Route::get('/address/reverse', [GeocodeController::class, 'reverse']);
    });

    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::post('/user/heartbeat', function (Request $request) {
        $request->user()->forceFill([
            'hoat_dong_cuoi_luc' => now(),
        ])->saveQuietly();

        return response()->json([
            'success' => true,
            'online_window_seconds' => 300,
        ]);
    });
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);
    Route::get('/user/change-password/captcha', [UserController::class, 'passwordCaptcha']);
    Route::put('/user/change-password', [UserController::class, 'changePasswordDirect']);
    Route::post('/user/change-password/request-otp', [UserController::class, 'requestPasswordOTP']);
    Route::post('/user/change-password/verify-current', [UserController::class, 'verifyCurrentPassword']);
    Route::post('/user/change-password/check-otp', [UserController::class, 'checkOTP']);
    Route::post('/user/change-password/verify-otp', [UserController::class, 'changePasswordWithOTP']);
    Route::get('/user/dia-chi', [DiaChiController::class, 'index']);
    Route::post('/user/dia-chi', [DiaChiController::class, 'store']);
    Route::put('/user/dia-chi/{id}', [DiaChiController::class, 'update']);
    Route::delete('/user/dia-chi/{id}', [DiaChiController::class, 'destroy']);
    Route::patch('/user/dia-chi/{id}/mac-dinh', [DiaChiController::class, 'setDefault']);

    // ===== GIỎ HÀNG =====
    Route::get('/gio-hang', [GioHangController::class, 'index']);
    Route::post('/gio-hang/them', [GioHangController::class, 'them']);
    Route::post('/gio-hang/them-combo', [GioHangController::class, 'themCombo']);
    Route::put('/gio-hang/cap-nhat/{id}', [GioHangController::class, 'capNhat']);
    Route::put('/gio-hang/cap-nhat-combo/{group_id}', [GioHangController::class, 'capNhatCombo']);
    Route::delete('/gio-hang/xoa-tat', [GioHangController::class, 'xoaTat']);
    Route::delete('/gio-hang/xoa/{id}', [GioHangController::class, 'xoa']);
    Route::delete('/gio-hang/xoa-combo/{group_id}', [GioHangController::class, 'xoaCombo']);
    Route::get('/gio-hang/dem', [GioHangController::class, 'demSoLuong']);

    // ===== ĐẶT HÀNG =====
    Route::post('/checkout', [DatHangController::class, 'checkout'])->middleware('checkout.throttle');
    Route::post('/orders/send-email/{id}', [DatHangController::class, 'sendSuccessEmail']);
    Route::post('/orders/{id}/payment-notice', [DatHangController::class, 'notifyManualPayment']);
    Route::get('/orders', [DatHangController::class, 'orders']);
    Route::get('/orders/{id}', [DatHangController::class, 'show']);
    Route::post('/chat/refund-assist', [ChatbotController::class, 'refundAssist']);
    Route::get('/orders/{id}/momo-status', [MomoController::class, 'momoQuery']);
    Route::get('/orders/{id}/sepay-status', [SepayController::class, 'status']);
    Route::post('/orders/{id}/cancel', [DatHangController::class, 'cancelOrder']);
    Route::post('/orders/{id}/reorder', [DatHangController::class, 'reorder']);
    Route::post('/orders/{id}/refund', [DatHangController::class, 'refund']);
    Route::post('/orders/{id}/refund-proof', [DatHangController::class, 'uploadRefundProof']);
    Route::post('/donhang/{id}/refund-proof', [DatHangController::class, 'uploadRefundProof']);
    Route::get('/refund-file', [DatHangController::class, 'getRefundFile']);

    // ===== YÊU THÍCH =====
    Route::get('/yeu-thich', [YeuThichController::class, 'index']);
    Route::post('/yeu-thich/them', [YeuThichController::class, 'them']);
    Route::put('/yeu-thich/cap-nhat/{id}', [YeuThichController::class, 'capNhat']);
    Route::delete('/yeu-thich/xoa/{id}', [YeuThichController::class, 'xoa']);

    // ===== ĐÁNH GIÁ =====
    Route::post('/danh-gia', [DanhGiaController::class, 'store']);

    // ===== VOUCHER CỦA TÔI =====
    Route::get('/user/vouchers', [PromotionController::class, 'myVouchers']);
    Route::get('/user/vouchers/available', [PromotionController::class, 'availableGifts']);
    Route::post('/user/vouchers/claim', [PromotionController::class, 'claimVoucher']);

    // ===== SẢN PHẨM ĐÃ XEM =====
    Route::post('/sanpham-daxem/{id}', [SanPhamDaXemController::class, 'logView']);
    Route::get('/sanpham-daxem', [SanPhamDaXemController::class, 'index']);

    // ===== HỆ THỐNG XU =====
    Route::get('/xu/balance', [XuController::class, 'getBalance']);
    Route::get('/xu/history', [XuController::class, 'getHistory']);
    Route::get('/xu/settings', [XuController::class, 'getPublicSettings']);

    // ===== ĐIỂM DANH HÀNG NGÀY =====
    Route::get('/diem-danh/status', [DiemDanhController::class, 'getStatus']);
    Route::post('/diem-danh', [DiemDanhController::class, 'checkIn']);

    // ===== CHẤM CÔNG NHÂN VIÊN =====
    Route::get('/cham-cong/status', [ChamCongController::class, 'getStatus']);
    Route::post('/cham-cong/register-face', [ChamCongController::class, 'dangKyKhuonMat']);
    Route::post('/cham-cong/delete-face', [ChamCongController::class, 'xoaKhuonMat']);
    Route::post('/cham-cong/check', [ChamCongController::class, 'checkInCheckOut']);
    Route::get('/cham-cong/my-history', [ChamCongController::class, 'getLichSuCaNhan']);
    Route::get('/cham-cong/leaderboard', [ChamCongController::class, 'getLeaderboard']);
    Route::get('/cham-cong/don-xin-nghi', [DonXinNghiController::class, 'index']);
    Route::post('/cham-cong/don-xin-nghi', [DonXinNghiController::class, 'store']);
    Route::post('/cham-cong/don-xin-nghi/{donXinNghi}/bo-sung', [DonXinNghiController::class, 'resubmit']);
    Route::patch('/cham-cong/don-xin-nghi/{donXinNghi}/huy', [DonXinNghiController::class, 'cancel']);

    // ===== AFFILIATE =====
    Route::get('/affiliate/me', [AffiliateController::class, 'me']);
    Route::post('/affiliate/activate', [AffiliateController::class, 'activate']);
    Route::get('/affiliate/referrals', [AffiliateController::class, 'referrals']);
    Route::get('/affiliate/commissions', [AffiliateController::class, 'commissions']);
    Route::get('/affiliate/withdraws', [AffiliateController::class, 'withdraws']);
    Route::post('/affiliate/withdraws', [AffiliateController::class, 'requestWithdraw']);
    Route::get('/affiliate/wallet', [AffiliateWalletController::class, 'show']);
    Route::get('/affiliate/withdrawals', [AffiliateWithdrawalController::class, 'index']);
    Route::post('/affiliate/withdrawals', [AffiliateWithdrawalController::class, 'store']);
    Route::get('/affiliate/videos', [AffiliateVideoController::class, 'myVideos']);
    Route::post('/affiliate/videos', [AffiliateVideoController::class, 'store']);
    Route::post('/affiliate/videos/{id}', [AffiliateVideoController::class, 'update']);
    Route::put('/affiliate/videos/{id}', [AffiliateVideoController::class, 'update']);
    Route::delete('/affiliate/videos/{id}', [AffiliateVideoController::class, 'destroy']);

    // ===== CHAT (USER) =====
    Route::get('/chat/me', [ChatController::class, 'getUserConversation']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::put('/chat/messages/{id}', [ChatController::class, 'updateMessage']);
    Route::delete('/chat/messages/{id}', [ChatController::class, 'destroyMessage']);

    // ===== VÒNG QUAY MAY MẮN =====
    Route::get('/vong-quay/lich-su', [VongQuayController::class, 'lichSu']);
    Route::post('/vong-quay/quay', [VongQuayController::class, 'quay']);
    Route::post('/vong-quay/nhan-luot', [VongQuayController::class, 'nhanLuotHangNgay']);
});

Route::get('/danhmuc', [DanhMucController::class, 'index']);
Route::get('/affiliate-videos/public', [AffiliateVideoController::class, 'publicIndex']);
Route::post('/affiliate-videos/{id}/track', [AffiliateVideoController::class, 'track']);
Route::get('/danhmuc-cha', [DanhMucChaController::class, 'index']);
Route::get('/danhmuc-cha/{id}', [DanhMucChaController::class, 'show']);
Route::get('/danhmuc/parents', [DanhMucController::class, 'getParentCategories']);
Route::get('/danhmuc/{id_danhmuc}/children', [DanhMucController::class, 'getChildrenCategories']);
Route::get('/danhmuc/{id_danhmuc}/inherited-attributes', [DanhMucController::class, 'getCategoryWithInheritedAttributes']);
Route::get('/danhmuc/{id_danhmuc}', [DanhMucController::class, 'show']);

// ================= THƯƠNG HIỆU =================
Route::get('/thuonghieu', [ThuongHieuController::class, 'index']);
Route::get('/thuonghieu/by-category/{categoryId}', [ThuongHieuController::class, 'getByCategory']);
Route::get('/thuonghieu/{id_thuonghieu}', [ThuongHieuController::class, 'show']);

// Route::post('/register', [UserController::class, 'store']);

// ================= THUỘC TÍNH =================
Route::get('/nhomthuoctinh', [ThuocTinhController::class, 'getNhom']);

Route::get('/thuoctinh', [ThuocTinhController::class, 'getThuocTinh']);

Route::get('/giatrithuoctinh/{id}', [ThuocTinhController::class, 'getGiaTri']);

Route::get('/thuoctinh-all', [ThuocTinhController::class, 'getAll']);

// ================= COLOR =================
Route::get('/colors', [ColorController::class, 'index']);
Route::get('/colors/{id}', [ColorController::class, 'show']);

// ================= SẢN PHẨM =================
Route::get('/mobile/home', [SanPhamController::class, 'mobileHome']);
Route::get('/sanpham/init', [SanPhamController::class, 'init']);
Route::get('/sanpham/search', [SanPhamController::class, 'search']);
Route::get('/sanpham/attribute-options', [SanPhamController::class, 'attributeOptions']);
Route::get('/sanpham', [SanPhamController::class, 'index']);
Route::get('/sanpham/{id}', [SanPhamController::class, 'show']);
Route::get('/sanpham/{id}/reviews', [DanhGiaController::class, 'index']);

// ================= COMBOS =================
Route::get('/combos', [ComboController::class, 'index']);
Route::get('/combos/{id}', [ComboController::class, 'show']);

// ================= BIẾN THỂ =================
Route::get('/bienthe', [BienTheController::class, 'index']);
Route::get('/bienthe/sanpham/{id_sanpham}', [BienTheController::class, 'getBySanPham']);
Route::get('/bienthe/{id}', [BienTheController::class, 'show']);

// ================= HÌNH ẢNH =================
Route::get('/bienthe-hinhanh', [BienTheHinhAnhController::class, 'index']);
Route::get('/bienthe-hinhanh/sanpham/{id_sanpham}', [BienTheHinhAnhController::class, 'getBySanPham']);
Route::get('/bienthe-hinhanh/{id}', [BienTheHinhAnhController::class, 'show']);

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

        // ===== ADMIN ROLES =====
        Route::apiResource('vaitro', VaiTroController::class);
        Route::post('/danhmuc-cha', [DanhMucChaController::class, 'store']);
        Route::put('/danhmuc-cha/{id}', [DanhMucChaController::class, 'update']);
        Route::delete('/danhmuc-cha/{id}', [DanhMucChaController::class, 'destroy']);
        Route::post('/danhmuc', [DanhMucController::class, 'store']);
        Route::put('/danhmuc/{id_danhmuc}', [DanhMucController::class, 'update']);
        Route::delete('/danhmuc/{id_danhmuc}', [DanhMucController::class, 'destroy']);
        Route::post('/thuonghieu', [ThuongHieuController::class, 'store']);
        Route::post('/thuonghieu/{id_thuonghieu}', [ThuongHieuController::class, 'update']);
        Route::delete('/thuonghieu/{id_thuonghieu}', [ThuongHieuController::class, 'destroy']);
        Route::post('/nhomthuoctinh', [ThuocTinhController::class, 'addNhom']);
        Route::put('/nhomthuoctinh/{id}', [ThuocTinhController::class, 'updateNhom']);
        Route::delete('/nhomthuoctinh/{id}', [ThuocTinhController::class, 'deleteNhom']);
        Route::post('/thuoctinh', [ThuocTinhController::class, 'addThuocTinh']);
        Route::put('/thuoctinh/{id}', [ThuocTinhController::class, 'updateThuocTinh']);
        Route::delete('/thuoctinh/{id}', [ThuocTinhController::class, 'deleteThuocTinh']);
        Route::post('/giatrithuoctinh', [ThuocTinhController::class, 'addGiaTri']);
        Route::put('/giatrithuoctinh/{id}', [ThuocTinhController::class, 'updateGiaTri']);
        Route::delete('/giatrithuoctinh/{id}', [ThuocTinhController::class, 'deleteGiaTri']);
        Route::post('/colors', [ColorController::class, 'store']);
        Route::put('/colors/{id}', [ColorController::class, 'update']);
        Route::delete('/colors/{id}', [ColorController::class, 'destroy']);
        Route::post('/sanpham', [SanPhamController::class, 'store']);
        Route::put('/sanpham/{id}', [SanPhamController::class, 'update']);
        Route::delete('/sanpham/{id}', [SanPhamController::class, 'destroy']);
        Route::post('/bienthe', [BienTheController::class, 'store']);
        Route::put('/bienthe/{id}', [BienTheController::class, 'update']);
        Route::delete('/bienthe/{id}', [BienTheController::class, 'destroy']);
        Route::post('/bienthe-hinhanh', [BienTheHinhAnhController::class, 'store']);
        Route::put('/bienthe-hinhanh/{id}', [BienTheHinhAnhController::class, 'update']);
        Route::delete('/bienthe-hinhanh/{id}', [BienTheHinhAnhController::class, 'destroy']);
        Route::get('/sanpham/export-inventory', [SanPhamController::class, 'exportInventory']);
        Route::post('/sanpham/import-stock', [SanPhamController::class, 'importStock']);
        // ===== ADMIN ORDERS =====
        Route::get('/orders', [DatHangController::class, 'allOrders']);
        Route::post('/orders/shipment/sync-demo', [DatHangController::class, 'syncDemoShipments']);
        Route::post('/orders/{id}/shipment', [DatHangController::class, 'createDemoShipment']);
        Route::post('/orders/{id}/shipment/advance', [DatHangController::class, 'advanceDemoShipment']);
        Route::post('/orders/{id}/shipment/fail', [DatHangController::class, 'markDemoShipmentFailed']);
        Route::post('/orders/{id}/shipment/retry', [DatHangController::class, 'retryDemoShipment']);
        Route::put('/orders/{id}/status', [DatHangController::class, 'updateStatus']);
        Route::put('/orders/{id}/payment-status', [DatHangController::class, 'updatePaymentStatus']);
        Route::post('/orders/{id}/refund-proof', [DatHangController::class, 'uploadRefundProof']);
        Route::delete('/orders/{id}', [DatHangController::class, 'destroyAdmin']);

        // ===== LIÊN HỆ ADMIN =====
        Route::get('/lien-he', [LienHeController::class, 'index']);
        Route::post('/lien-he/reply/{id}', [LienHeController::class, 'reply']);
        Route::delete('/contacts/{id}', [LienHeController::class, 'destroy']);

        Route::post('/apply-promo', [PromotionController::class, 'applyPromo']);
        Route::get('/promotions', [PromotionController::class, 'index']);
        Route::post('/promotions', [PromotionController::class, 'store']);
        Route::put('/promotions/{id}', [PromotionController::class, 'update']);
        Route::patch('/promotions/{id}/auto-send', [PromotionController::class, 'updateAutoSend']);
        Route::delete('/promotions/{id}', [PromotionController::class, 'destroy']);

        // ===== ADMIN BIRTHDAY CODES =====
        Route::get('/birthday-codes', [BirthdayCodeController::class, 'index']);
        Route::post('/birthday-codes/scan', [BirthdayCodeController::class, 'scan']);
        Route::post('/birthday-codes/send', [BirthdayCodeController::class, 'send']);
        Route::post('/birthday-codes/send-bulk', [BirthdayCodeController::class, 'sendBulk']);
        Route::post('/birthday-codes/resend', [BirthdayCodeController::class, 'resend']);
        Route::post('/birthday-codes/run-auto-now', [BirthdayCodeController::class, 'runAutoNow']);
        Route::get('/birthday-codes/logs', [BirthdayCodeController::class, 'logs']);
        Route::get('/birthday-codes/settings', [BirthdayCodeController::class, 'getSettingsApi']);
        Route::post('/birthday-codes/settings', [BirthdayCodeController::class, 'saveSettingsApi']);
        Route::get('/banners', [BannerController::class, 'adminIndex']);
        Route::post('/banners', [BannerController::class, 'store']);
        Route::post('/banners/{id}', [BannerController::class, 'update']);
        Route::delete('/banners/{id}', [BannerController::class, 'destroy']);

        // ===== ADMIN COMBOS =====
        Route::get('/combos', [ComboController::class, 'adminIndex']);
        Route::post('/combos', [ComboController::class, 'store']);
        Route::put('/combos/{id}', [ComboController::class, 'update']);
        Route::delete('/combos/{id}', [ComboController::class, 'destroy']);

        // ===== ADMIN COMBO OFFERS =====
        Route::get('/combo-offers', [ComboController::class, 'adminOffersIndex']);
        Route::post('/combo-offers', [ComboController::class, 'storeOffer']);
        Route::put('/combo-offers/{id}', [ComboController::class, 'updateOffer']);
        Route::delete('/combo-offers/{id}', [ComboController::class, 'deleteOffer']);
        Route::get('/news', [NewsController::class, 'index']);
        Route::get('/news-stats', [NewsController::class, 'stats']);
        Route::post('/news/upload-image', [NewsController::class, 'uploadContentImage']);
        Route::post('/news', [NewsController::class, 'store']);
        Route::put('/news/{id}', [NewsController::class, 'update']);
        Route::patch('/news/{id}/autosave', [NewsController::class, 'autosave']);
        Route::get('/news/{id}/preview', [NewsController::class, 'preview']);
        Route::get('/news/{id}/revisions', [NewsController::class, 'revisions']);
        Route::post('/news/{id}/revisions/{revisionId}/restore', [NewsController::class, 'restoreRevision']);
        Route::delete('/news/{id}', [NewsController::class, 'destroy']);

        // ===== ADMIN REVIEWS =====
        Route::get('/reviews/ai-status', [DanhGiaController::class, 'getAiStatus']);
        Route::post('/reviews/ai-status', [DanhGiaController::class, 'toggleAiStatus']);
        Route::get('/reviews', [DanhGiaController::class, 'adminIndex']);
        Route::post('/reviews/auto-moderate', [DanhGiaController::class, 'autoModeratePending']);
        Route::put('/reviews/bulk-status', [DanhGiaController::class, 'bulkUpdateStatus']);
        Route::put('/reviews/{id}/status', [DanhGiaController::class, 'updateStatus']);
        Route::delete('/reviews/{id}', [DanhGiaController::class, 'destroy']);

        // ===== AFFILIATE ADMIN =====
        Route::get('/affiliates', [AdminAffiliateController::class, 'index']);
        Route::put('/affiliate-profiles/{id}', [AdminAffiliateController::class, 'updateProfile']);
        Route::put('/affiliate-commissions/{id}/status', [AdminAffiliateController::class, 'updateCommissionStatus']);
        Route::put('/affiliate-withdraws/{id}/status', [AdminAffiliateController::class, 'updateWithdrawStatus']);
        Route::get('/affiliate-videos', [AffiliateVideoController::class, 'adminIndex']);
        Route::put('/affiliate-videos/{id}/status', [AffiliateVideoController::class, 'updateStatus']);

        // ===== ADMIN ACCOUNT =====
        Route::get('/account/profile', [AdminAccountController::class, 'profile']);
        Route::put('/account/profile', [AdminAccountController::class, 'updateProfile']);
        Route::get('/account/activity-log', [AdminAccountController::class, 'activityLog']);
        Route::get('/account/active-admins', [AdminAccountController::class, 'activeAdmins']);
        Route::get('/account/system-activity-logs', [AdminAccountController::class, 'systemActivityLogs']);
        Route::get('/account/billing', [AdminAccountController::class, 'billing']);
        Route::get('/account/settings', [AdminAccountController::class, 'settings']);
        Route::put('/account/settings', [AdminAccountController::class, 'updateSettings']);
        Route::get('/account/two-factor', [AdminTwoFactorController::class, 'status']);
        Route::post('/account/two-factor/enable', [AdminTwoFactorController::class, 'enable'])->middleware('throttle:5,1');
        Route::post('/account/two-factor/confirm', [AdminTwoFactorController::class, 'confirm'])->middleware('throttle:5,1');
        Route::post('/account/two-factor/recovery-codes', [AdminTwoFactorController::class, 'recoveryCodes'])->middleware('throttle:3,1');
        Route::delete('/account/two-factor', [AdminTwoFactorController::class, 'disable'])->middleware('throttle:5,1');

        // ===== ADMIN CHAT =====
        Route::get('/chat/conversations', [ChatController::class, 'getConversations']);
        Route::get('/chat/conversations/{id}/messages', [ChatController::class, 'getMessages']);
        Route::delete('/chat/conversations', [ChatController::class, 'destroyConversations']);
        Route::post('/chat/send', [ChatController::class, 'sendMessage']);
        Route::put('/chat/messages/{id}', [ChatController::class, 'updateMessage']);
        Route::delete('/chat/messages/{id}', [ChatController::class, 'destroyMessage']);

        // ===== ADMIN FLASH SALES =====
        Route::get('/flash-sales', [FlashSaleController::class, 'index']);
        Route::post('/flash-sales', [FlashSaleController::class, 'store']);
        Route::get('/flash-sales/{id}', [FlashSaleController::class, 'show']);
        Route::put('/flash-sales/{id}', [FlashSaleController::class, 'update']);
        Route::delete('/flash-sales/{id}', [FlashSaleController::class, 'destroy']);
        Route::post('/flash-sales/{id}/products', [FlashSaleController::class, 'saveProducts']);
        Route::delete('/flash-sales/{id}/products/{productId}', [FlashSaleController::class, 'removeProduct']);

        // ===== ADMIN COIN SETTINGS =====
        Route::get('/xu/settings', [XuController::class, 'getAdminSettings']);
        Route::put('/xu/settings', [XuController::class, 'updateAdminSettings']);

        // ===== ADMIN DAILY CHECK-IN =====
        Route::get('/diem-danh', [DiemDanhController::class, 'adminIndex']);
        Route::get('/diem-danh/cauhinh', [DiemDanhController::class, 'adminGetSettings']);
        Route::put('/diem-danh/cauhinh', [DiemDanhController::class, 'adminUpdateSettings']);

        // ===== ADMIN TIME ATTENDANCE =====
        Route::get('/quan-ly-cham-cong', [ChamCongController::class, 'adminGetLichSu']);
        Route::get('/cham-cong/ca-lam', [ChamCongController::class, 'adminGetCaLam']);
        Route::put('/cham-cong/ca-lam', [ChamCongController::class, 'adminUpdateCaLam']);
        Route::get('/cham-cong/nhan-vien/{id}/lich-lam', [ChamCongController::class, 'adminGetLichLam']);
        Route::put('/cham-cong/nhan-vien/{id}/lich-lam', [ChamCongController::class, 'adminUpdateLichLam']);
        Route::get('/cham-cong/nhan-vien', [ChamCongController::class, 'adminGetNhanVien']);
        Route::post('/cham-cong/nhan-vien/{id}/dang-ky-khuon-mat', [ChamCongController::class, 'adminDangKyKhuonMat']);
        Route::delete('/cham-cong/nhan-vien/{id}/khuon-mat', [ChamCongController::class, 'adminXoaKhuonMat']);
        Route::post('/cham-cong/quick-check', [ChamCongController::class, 'adminQuickCheck']);
        Route::put('/cham-cong/ban-ghi/{id}/bo-sung-gio-ra', [ChamCongController::class, 'adminBoSungGioRa']);
        Route::get('/cham-cong/don-xin-nghi', [DonXinNghiController::class, 'adminIndex']);
        Route::patch('/cham-cong/don-xin-nghi/{donXinNghi}/xu-ly', [DonXinNghiController::class, 'review']);

        // ===== ADMIN LUCKY WHEEL =====
        Route::get('/vong-quay', [VongQuayController::class, 'adminIndex']);
        Route::post('/vong-quay', [VongQuayController::class, 'adminStore']);
        Route::put('/vong-quay/{id}', [VongQuayController::class, 'adminUpdate']);
        Route::delete('/vong-quay/{id}', [VongQuayController::class, 'adminDestroy']);
        Route::get('/vong-quay/lich-su', [VongQuayController::class, 'adminHistory']);

    });
