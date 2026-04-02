<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DatHangController;

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

Route::get('/auth/facebook', [AuthController::class, 'redirectFacebook']);
Route::get('/auth/facebook/callback', [AuthController::class, 'handleFacebook']);

Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
Route::post('/forgot-password/reset-password', [ForgotPasswordController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);

    // ===== GIỎ HÀNG =====
    Route::get('/gio-hang',                     [GioHangController::class, 'index']);
    Route::post('/gio-hang/them',               [GioHangController::class, 'them']);
    Route::put('/gio-hang/cap-nhat/{id}',       [GioHangController::class, 'capNhat']);
    Route::delete('/gio-hang/xoa-tat',          [GioHangController::class, 'xoaTat']);
    Route::delete('/gio-hang/xoa/{id}',         [GioHangController::class, 'xoa']);
    Route::get('/gio-hang/dem',                 [GioHangController::class, 'demSoLuong']);

    // ===== ĐẶT HÀNG =====
    Route::post('/checkout',                    [DatHangController::class, 'checkout']);
    Route::get('/orders',                       [DatHangController::class, 'orders']);
    // ===== YÊU THÍCH =====
    Route::get('/yeu-thich', [YeuThichController::class, 'index']);
    Route::post('/yeu-thich/them', [YeuThichController::class, 'them']);
    Route::put('/yeu-thich/cap-nhat/{id}', [YeuThichController::class, 'capNhat']);
    Route::delete('/yeu-thich/xoa/{id}', [YeuThichController::class, 'xoa']);
});

Route::get('/auth/google', [AuthController::class, 'redirectGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogle']);




Route::get('/danhmuc',             [DanhMucController::class, 'index']);
Route::post('/danhmuc',         [DanhMucController::class, 'store']);
Route::get('/danhmuc/{id_danhmuc}',        [DanhMucController::class, 'show']);
Route::put('/danhmuc/{id_danhmuc}',        [DanhMucController::class, 'update']);
Route::delete('/danhmuc/{id_danhmuc}',     [DanhMucController::class, 'destroy']);

Route::get('/thuonghieu',             [ThuongHieuController::class, 'index']);
Route::post('/thuonghieu',         [ThuongHieuController::class, 'store']);
Route::get('/thuonghieu/{id_thuonghieu}',        [ThuongHieuController::class, 'show']);
Route::put('/thuonghieu/{id_thuonghieu}',        [ThuongHieuController::class, 'update']);
Route::delete('/thuonghieu/{id_thuonghieu}',     [ThuongHieuController::class, 'destroy']);


Route::post('/register',         [UserController::class, 'store']);
Route::get('/users',             [UserController::class, 'index']);
Route::get('/users/{id}',        [UserController::class, 'show']);
Route::put('/users/{id}',        [UserController::class, 'update']);
Route::delete('/users/{id}',     [UserController::class, 'destroy']);


Route::get('/nhomthuoctinh',                [ThuocTinhController::class, 'getNhom']);
Route::post('/nhomthuoctinh',               [ThuocTinhController::class, 'addNhom']);
Route::put('/nhomthuoctinh/{id}',           [ThuocTinhController::class, 'updateNhom']);
Route::delete('/nhomthuoctinh/{id}',        [ThuocTinhController::class, 'deleteNhom']);


Route::get('/thuoctinh',                    [ThuocTinhController::class, 'getThuocTinh']);
Route::post('/thuoctinh',                   [ThuocTinhController::class, 'addThuocTinh']);
Route::put('/thuoctinh/{id}',               [ThuocTinhController::class, 'updateThuocTinh']);
Route::delete('/thuoctinh/{id}',            [ThuocTinhController::class, 'deleteThuocTinh']);


Route::get('/giatrithuoctinh/{id}',         [ThuocTinhController::class, 'getGiaTri']);
Route::post('/giatrithuoctinh',             [ThuocTinhController::class, 'addGiaTri']);
Route::put('/giatrithuoctinh/{id}',               [ThuocTinhController::class, 'updateGiaTri']);
Route::delete('/giatrithuoctinh/{id}',      [ThuocTinhController::class, 'deleteGiaTri']);


Route::get('/thuoctinh-all',                [ThuocTinhController::class, 'getAll']);


Route::get('/colors',             [ColorController::class, 'index']);
Route::post('/colors',            [ColorController::class, 'store']);
Route::get('/colors/{id}',        [ColorController::class, 'show']);
Route::put('/colors/{id}',        [ColorController::class, 'update']);
Route::delete('/colors/{id}',     [ColorController::class, 'destroy']);


Route::get('/sanpham',             [SanPhamController::class, 'index']);
Route::post('/sanpham',            [SanPhamController::class, 'store']);
Route::get('/sanpham/{id}',        [SanPhamController::class, 'show']);
Route::put('/sanpham/{id}',        [SanPhamController::class, 'update']);
Route::delete('/sanpham/{id}',     [SanPhamController::class, 'destroy']);


Route::get('/bienthe',                      [BienTheController::class, 'index']);
Route::get('/bienthe/sanpham/{id_sanpham}', [BienTheController::class, 'getBySanPham']);
Route::get('/bienthe/{id}',                 [BienTheController::class, 'show']);
Route::post('/bienthe',                     [BienTheController::class, 'store']);
Route::put('/bienthe/{id}',                 [BienTheController::class, 'update']);
Route::delete('/bienthe/{id}',              [BienTheController::class, 'destroy']);


Route::get('/bienthe-hinhanh',                         [BienTheHinhAnhController::class, 'index']);
Route::get('/bienthe-hinhanh/sanpham/{id_sanpham}', [BienTheHinhAnhController::class, 'getBySanPham']);
Route::get('/bienthe-hinhanh/{id}',                   [BienTheHinhAnhController::class, 'show']);
Route::post('/bienthe-hinhanh',                       [BienTheHinhAnhController::class, 'store']);
Route::put('/bienthe-hinhanh/{id}',                   [BienTheHinhAnhController::class, 'update']);
Route::delete('/bienthe-hinhanh/{id}',                [BienTheHinhAnhController::class, 'destroy']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/test', function () {
    return 'OK API';
});

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // ===== ADMIN ORDERS =====
        Route::get('/orders', [DatHangController::class, 'allOrders']);
        Route::put('/orders/{id}/status', [DatHangController::class, 'updateStatus']);
    });
