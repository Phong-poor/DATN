<?php

namespace App\Http\Controllers;

use App\Models\DatHang;
use App\Models\DatHangChiTiet;
use App\Models\GioHang;
use App\Models\BienThe;
use App\Models\DiaChi;
use App\Models\Promotion;
use App\Models\UserVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Mail\OrderSuccessMail;
use App\Events\OrderStatusUpdated;
use App\Events\OrderPlaced;


class DatHangController extends Controller
{
    public function cancelOrder(Request $request, $id)
    {
        $userId = Auth::id();
        $order = DatHang::with('chi_tiets.bienThe')
            ->where('id_dathang', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        if (!in_array($order->trangthai, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng ở trạng thái này.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $order->update([
                'trangthai' => 'cancelled',
                'lydo' => $request->lydo ?? 'Người dùng hủy đơn'
            ]);

            foreach ($order->chi_tiets as $chiTiet) {
                if ($chiTiet->bienThe) {
                    $chiTiet->bienThe->increment('soluong', $chiTiet->soluong);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hủy đơn hàng thành công!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reorder(Request $request, $id)
    {
        $userId = Auth::id();
        $order = DatHang::with('chi_tiets.bienThe')
            ->where('id_dathang', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $order->update(['lydo' => null]);
            $addedItemsCount = 0;
            $skippedItems = [];

            foreach ($order->chi_tiets as $chiTiet) {
                $bienThe = $chiTiet->bienThe;

                if (!$bienThe || $bienThe->soluong <= 0) {
                    $skippedItems[] = $bienThe ? $bienThe->ten_bienthe : 'Sản phẩm không còn tồn tại';
                    continue;
                }

                $cartItem = GioHang::where('user_id', $userId)
                    ->where('id_bienthe', $chiTiet->id_bienthe)
                    ->first();

                if ($cartItem) {
                    $cartItem->increment('soluong', $chiTiet->soluong);
                } else {
                    GioHang::create([
                        'user_id'    => $userId,
                        'id_bienthe' => $chiTiet->id_bienthe,
                        'soluong'    => $chiTiet->soluong,
                    ]);
                }
                $addedItemsCount++;
            }

            DB::commit();

            if ($addedItemsCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rất tiếc! Tất cả sản phẩm trong đơn hàng này đều đã hết hàng.',
                    'skipped' => $skippedItems
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã thêm $addedItemsCount sản phẩm vào giỏ hàng.",
                'skipped' => $skippedItems
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'id_diachi' => 'nullable|integer',
            'diachi' => 'required_without:id_diachi|string',
            'PTTT'   => 'required|string',
        ]);

        $userId = Auth::id();
        $diaChiGiaoHang = $request->diachi;

        if ($request->filled('id_diachi')) {
            $diaChi = DiaChi::where('id_user', $userId)
                ->where('id_diachi', $request->id_diachi)
                ->first();

            if (! $diaChi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn địa chỉ'
                ], 422);
            }

            $diaChiGiaoHang = $diaChi->dia_chi_day_du;
        }

        $gioHangItems = GioHang::with(['bienThe', 'combo'])->where('user_id', $userId)->get();

        if ($gioHangItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Giỏ hàng của bạn đang trống.'
            ], 400);
        }

        // Nhóm các items theo combo_group_id để tính giá phân bổ cho combo
        $groupedCombos = $gioHangItems->filter(fn($item) => $item->id_combo && $item->combo_group_id)
            ->groupBy('combo_group_id');

        $allocatedPrices = [];

        // Lấy danh sách ID biến thể thực tế trong giỏ hàng để kiểm tra điều kiện ưu đãi
        $cartVariantIds = $gioHangItems->pluck('id_bienthe')->toArray();
        $freeComboOffers = DB::table('bienthe_combo_offers')
            ->whereIn('id_bienthe', $cartVariantIds)
            ->where('trangthai', 1)
            ->get()
            ->filter(function ($offer) {
                return \App\Http\Controllers\ComboController::isOfferValid($offer);
            })
            ->keyBy('id_combo');

        foreach ($groupedCombos as $groupId => $comboItems) {
            if ($comboItems->isEmpty()) continue;
            
            $first = $comboItems->first();
            $combo = $first->combo;
            if (!$combo) continue;

            // Xác định giá bán của combo (miễn phí nếu có biến thể kích hoạt ưu đãi, hoặc lấy giá gốc combo)
            $totalComboPrice = (float) $combo->giakhuyenmai;
            if (isset($freeComboOffers[$combo->id_combo])) {
                $offer = $freeComboOffers[$combo->id_combo];
                if ($offer->loai_uudai === 'free') {
                    $totalComboPrice = 0.00;
                } else {
                    $totalComboPrice = (float) ($offer->giakhuyenmai_override ?? $combo->giakhuyenmai);
                }
            }
            
            // Tính tổng giá gốc của các biến thể được chọn trong combo
            $sumOriginalPrice = 0;
            foreach ($comboItems as $item) {
                $sumOriginalPrice += $item->bienThe ? (float)$item->bienThe->gia : 0;
            }

            if ($sumOriginalPrice <= 0) continue;

            // Phân bổ tỷ lệ giá
            $tempSum = 0;
            $itemsCount = $comboItems->count();
            
            foreach ($comboItems as $index => $item) {
                if (!$item->bienThe) continue;
                
                $originalPrice = (float)$item->bienThe->gia;
                
                if ($index === $itemsCount - 1) {
                    $allocatedPrice = $totalComboPrice - $tempSum;
                } else {
                    $allocatedPrice = $totalComboPrice > 0 
                        ? round($originalPrice * ($totalComboPrice / $sumOriginalPrice))
                        : 0.00;
                    $tempSum += $allocatedPrice;
                }
                
                $allocatedPrices[$item->id_giohang] = $allocatedPrice;
            }
        }

        // Tính tổng tiền gốc (đã bao gồm giảm giá combo!)
        $tongTienGoc = 0;
        foreach ($gioHangItems as $item) {
            $unitPrice = isset($allocatedPrices[$item->id_giohang])
                ? $allocatedPrices[$item->id_giohang]
                : ($item->bienThe?->gia ?? 0);
            
            $tongTienGoc += $item->soluong * $unitPrice;
        }

        $shippingFee = 30000;

        // ── Xử lý mã giảm giá ──────────────────────────
        $giamGia = 0;
        $giamGiaShip = 0;
        $promoId = null;

        if ($request->filled('promo_code')) {
            $promo = Promotion::where('code', strtoupper($request->promo_code))
                ->whereIn('status', ['running', 'open'])
                ->first();

            if ($promo) {
                if ($promo->category === 'freeship') {
                    return response()->json(['success' => false, 'message' => 'Mã này là mã freeship, không áp dụng cho đơn hàng.'], 400);
                }
                if ($promo->end_date && now()->gt($promo->end_date)) {
                    return response()->json(['success' => false, 'message' => 'Mã giảm giá đã hết hạn.'], 400);
                }

                // Block if subtotal < conditions
                if ($promo->dieu_kien && $promo->dieu_kien > 0) {
                    if ($tongTienGoc < $promo->dieu_kien) {
                        return response()->json([
                            'success' => false, 
                            'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($promo->dieu_kien, 0, ',', '.') . 'đ để sử dụng mã này.'
                        ], 400);
                    }
                }

                if ($promo->type === 'percent') {
                    $giamGia = round($tongTienGoc * $promo->value / 100);
                } elseif ($promo->type === 'fixed') {
                    $giamGia = min($promo->value, $tongTienGoc);
                } elseif ($promo->type === 'maxprice') {
                    $giamGia = min($promo->value, $tongTienGoc);
                }

                $promoId = $promo->id;
            }
        }

        if ($request->filled('freeship_code')) {
            $fpromo = Promotion::where('code', strtoupper($request->freeship_code))
                ->whereIn('status', ['running', 'open'])
                ->first();

            if ($fpromo) {
                if ($fpromo->end_date && now()->gt($fpromo->end_date)) {
                    return response()->json(['success' => false, 'message' => 'Mã freeship đã hết hạn.'], 400);
                }

                if ($fpromo->dieu_kien && $fpromo->dieu_kien > 0) {
                    if ($tongTienGoc < $fpromo->dieu_kien) {
                        return response()->json([
                            'success' => false, 
                            'message' => 'Đơn hàng chưa đạt tối thiểu ' . number_format($fpromo->dieu_kien, 0, ',', '.') . 'đ để dùng mã miễn phí vận chuyển.'
                        ], 400);
                    }
                }

                if ($fpromo->category === 'freeship') {
                    $giamGiaShip = $shippingFee;
                } else {
                    return response()->json(['success' => false, 'message' => 'Mã này không phải mã freeship.'], 400);
                }
            }
        }

        $tongTienSauGiam = max(0, $tongTienGoc - $giamGia) + max(0, $shippingFee - $giamGiaShip);

        $paymentProvider = $this->resolvePaymentProvider($request->PTTT);
        $isMomoPayment = $paymentProvider === 'momo';
        $hasPaymentTracking = Schema::hasColumn('dathang', 'payment_provider');
        $freeshipPromotionId = isset($fpromo) && $fpromo ? $fpromo->id : null;

        if ($isMomoPayment) {
            if (!env('MOMO_PARTNER_CODE') || !env('MOMO_ACCESS_KEY') || !env('MOMO_SECRET_KEY')) {
                return response()->json([
                    'success' => false,
                    'message' => 'MoMo sandbox chưa được cấu hình partnerCode/accessKey/secretKey trong file .env.'
                ], 422);
            }

            if ($tongTienSauGiam < 1000 || $tongTienSauGiam > 50000000) {
                return response()->json([
                    'success' => false,
                    'message' => 'MoMo chỉ hỗ trợ thanh toán từ 1.000đ đến 50.000.000đ.'
                ], 422);
            }

        }

        $donHang = null;

        try {
            DB::beginTransaction();

            $orderData = [
                'user_id'     => $userId,
                'tongtien'    => $tongTienSauGiam,
                'trangthai'   => 'pending',
                'diachi'      => $diaChiGiaoHang,
                'PTTT'        => $request->PTTT,
                'giam_gia'    => $giamGia + $giamGiaShip,       // lưu số tiền đã giảm
                'promotion_id' => $promoId,      // lưu id promotion đã dùng
            ];

            if ($hasPaymentTracking) {
                $orderData['payment_provider'] = $paymentProvider;
                $orderData['payment_status'] = in_array($paymentProvider, ['momo', 'vnpay'], true) ? 'pending' : 'unpaid';
                $orderData['payment_payload'] = [
                    'checkout' => [
                        'promo_id' => $promoId,
                        'freeship_promotion_id' => $freeshipPromotionId,
                        'promo_code' => $request->promo_code,
                        'freeship_code' => $request->freeship_code,
                    ],
                ];
            }

            $donHang = DatHang::create($orderData);

            foreach ($gioHangItems as $item) {
                $unitPrice = isset($allocatedPrices[$item->id_giohang])
                    ? $allocatedPrices[$item->id_giohang]
                    : ($item->bienThe?->gia ?? 0);

                DatHangChiTiet::create([
                    'id_dathang' => $donHang->id_dathang,
                    'id_bienthe' => $item->id_bienthe,
                    'soluong'    => $item->soluong,
                    'gia'        => $unitPrice,
                    'id_combo'   => $item->id_combo,
                    'combo_group_id' => $item->combo_group_id,
                ]);
            }

            if (!$isMomoPayment) {
                GioHang::where('user_id', $userId)->delete();

                // Cập nhật trạng thái voucher trong hồ sơ user thành "Đã sử dụng"
                if ($promoId) {
                    UserVoucher::where('id_user', $userId)
                        ->where('id_promotion', $promoId)
                        ->update(['trang_thai' => 1]);
                }

                // Nếu có dùng thêm mã freeship
                if ($freeshipPromotionId) {
                    UserVoucher::where('id_user', $userId)
                        ->where('id_promotion', $freeshipPromotionId)
                        ->update(['trang_thai' => 1]);
                }
            }

            // Increment the usage of applied combo offers
            $appliedOfferIds = [];
            foreach ($groupedCombos as $groupId => $comboItems) {
                $first = $comboItems->first();
                $combo = $first->combo;
                if ($combo && isset($freeComboOffers[$combo->id_combo])) {
                    $offer = $freeComboOffers[$combo->id_combo];
                    $appliedOfferIds[] = $offer->id;
                }
            }

            if (!empty($appliedOfferIds)) {
                DB::table('bienthe_combo_offers')
                    ->whereIn('id', $appliedOfferIds)
                    ->increment('da_su_dung');
            }

            DB::commit();

            // Invalidate dashboard cache
            Cache::forget('dashboard_data_all');
            Cache::forget('dashboard_data_week');
            Cache::forget('dashboard_data_month');
            Cache::forget('dashboard_data_year');

            // MoMo chỉ thông báo đơn mới cho admin sau khi thanh toán thành công.
            if (!$isMomoPayment) {
                broadcast(new OrderPlaced($donHang));
            }

            $payUrl = null;
            if ($paymentProvider === 'vnpay') {
                $vnpay = new VnpayController();
                $payUrl = $vnpay->createPaymentUrl($donHang);
            }
            if ($isMomoPayment) {
                $momo = new MomoController();
                $momoPayment = $momo->createPayment($donHang, $this->resolveMomoRequestType($paymentProvider));
                $payUrl = $momoPayment['payUrl'];
                $donHang = $donHang->fresh();
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Đặt hàng thành công!',
                'order'     => $donHang,
                'payUrl'    => $payUrl,
                'giam_gia'  => $giamGia,
            ]);

        } catch (\Exception $e) {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($isMomoPayment && $donHang) {
                DatHangChiTiet::where('id_dathang', $donHang->id_dathang)->delete();
                $donHang->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendSuccessEmail($id)
    {
        try {
            $order = DatHang::with(['chi_tiets.bienThe.sanPham', 'user'])->findOrFail($id);

            if ($order->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            Mail::to($order->user->email)->send(new \App\Mail\OrderSuccessMail($order, $order->user));

            return response()->json(['success' => true, 'message' => 'Email sent']);
        } catch (\Exception $e) {
            Log::error("Lỗi gửi mail thủ công: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function orders()
    {
        $userId = Auth::id();
        $orders = DatHang::with(['chi_tiets.bienThe.sanPham'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $orders->each(function($order) use ($userId) {
            $order->chi_tiets->each(function($chiTiet) use ($order, $userId) {
                $chiTiet->is_reviewed = \App\Models\DanhGia::where('id_dathang', $order->id_dathang)
                    ->where('id_bienthe', $chiTiet->id_bienthe)
                    ->where('user_id', $userId)
                    ->exists();
            });
        });

        return response()->json([
            'success' => true,
            'orders'  => $orders
        ]);
    }

    public function refund(Request $request, $id)
    {
        $userId = Auth::id();
        $order = DatHang::where('id_dathang', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($order->trangthai !== 'done') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể yêu cầu hoàn trả khi đơn hàng đã hoàn thành.'
            ], 400);
        }

        $request->validate([
            'lydo' => 'required|string',
            'proof' => 'required|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi,wmv|max:20480',
        ]);

        try {
            DB::beginTransaction();

            $proofPath = null;
            if ($request->hasFile('proof')) {
                $file = $request->file('proof');
                $filename = time() . '_' . $file->getClientOriginalName();
                $proofPath = $file->storeAs('refund_proofs', $filename, 'public');
            }

            $order->update([
                'trangthai' => 'refund_pending',
                'lydo' => $request->lydo,
                'refund_proof' => $proofPath
            ]);

            DB::commit();

            // Broadcast the status update
            event(new OrderStatusUpdated($order));

            return response()->json([
                'success' => true,
                'message' => 'Gửi yêu cầu hoàn trả thành công!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== ADMIN METHODS =====

    public function allOrders()
    {
        $orders = DatHang::with(['user', 'chi_tiets.bienThe.sanPham'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders'  => $orders
        ]);
    }

    public function updateStatus(Request $request, $id) 
    {
        $request->validate([
            'trangthai' => 'required|string|in:pending,confirmed,shipping,done,cancelled,refund_pending,refund_pickup,refund_delivering,refund_received,refunded,refund_rejected'
        ]);

        $order = DatHang::with('chi_tiets.bienThe')->findOrFail($id);
        $oldStatus = $order->trangthai;
        $newStatus = $request->trangthai;

        if ($oldStatus === $newStatus) {
            return response()->json([
                'success' => true,
                'message' => 'Trạng thái không đổi.',
                'order'   => $order
            ]);
        }

        try {
            DB::beginTransaction();

            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                foreach ($order->chi_tiets as $chiTiet) {
                    if ($chiTiet->bienThe) {
                        $chiTiet->bienThe->increment('soluong', $chiTiet->soluong);
                    } 
                }
            }

            if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                foreach ($order->chi_tiets as $chiTiet) {
                    if ($chiTiet->bienThe) {
                        if ($chiTiet->bienThe->soluong < $chiTiet->soluong) {
                            throw new \Exception("Sản phẩm {$chiTiet->bienThe->ten_bienthe} không đủ hàng để khôi phục đơn hàng.");
                        }
                        $chiTiet->bienThe->decrement('soluong', $chiTiet->soluong);
                    }
                }
            }

            $updateData = ['trangthai' => $newStatus];
            if ($newStatus === 'cancelled' && $request->has('lydo')) {
                $updateData['lydo'] = $request->lydo;
            }
            $order->update($updateData);

            DB::commit();

            // Broadcast the status update
            event(new OrderStatusUpdated($order));


            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công!',
                'order'   => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    private function resolvePaymentProvider(?string $method): ?string
    {
        $method = trim((string) $method);

        return match ($method) {
            'MoMo', 'MOMO', 'momo' => 'momo',
            'Ví điện tử', 'VNPAY', 'VNPay', 'vnpay' => 'vnpay',
            'COD' => 'cod',
            'Chuyển khoản' => 'bank',
            default => null,
        };
    }

    private function resolveMomoRequestType(?string $provider): string
    {
        return env('MOMO_REQUEST_TYPE', 'payWithMethod') ?: 'payWithMethod';
    }
}
