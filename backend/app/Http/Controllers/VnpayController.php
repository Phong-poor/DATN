<?php

namespace App\Http\Controllers;

use App\Events\NewOrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Models\DatHang;
use App\Models\GioHang;
use App\Models\XuHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tạo giao dịch VNPAY, xác minh IPN/return và cập nhật trạng thái đơn hàng.
 */
class VnpayController extends Controller
{
    public function createPaymentUrl($order)
    {
        $vnp_Url = env('VNPAY_URL');
        $vnp_Returnurl = env('VNPAY_RETURN_URL');

        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');

        $vnp_TxnRef = (string) $order->id_dathang.'_'.time();
        $vnp_OrderInfo = 'Thanh toan don hang '.$order->id_dathang;
        $vnp_OrderType = 'other';
        $vnp_Amount = (int) ($order->tongtien * 100);
        $vnp_Locale = 'vn';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $vnp_TmnCode,
            'vnp_Amount' => $vnp_Amount,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => date('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $vnp_IpAddr,
            'vnp_Locale' => $vnp_Locale,
            'vnp_OrderInfo' => $vnp_OrderInfo,
            'vnp_OrderType' => $vnp_OrderType,
            'vnp_ReturnUrl' => $vnp_Returnurl,
            'vnp_TxnRef' => $vnp_TxnRef,
        ];

        ksort($inputData);
        $query = '';
        $i = 0;
        $hashdata = '';
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashdata .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key).'='.urlencode($value).'&';
        }

        $vnp_Url = $vnp_Url.'?'.$query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash='.$vnpSecureHash;
        }

        return $vnp_Url;
    }

    public function handleIPN(Request $request)
    {
        $inputData = $request->all();
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHashType']);
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);

        $i = 0;
        $hashData = '';
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData.'&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashData = $hashData.urlencode($key).'='.urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash == $vnp_SecureHash) {
            $parts = explode('_', $inputData['vnp_TxnRef']);
            $orderId = $parts[0];
            $order = DatHang::find($orderId);

            if ($order) {
                if ($inputData['vnp_ResponseCode'] == '00') {
                    $order->update([
                        'trangthai' => 'confirmed',
                        'trang_thai_thanh_toan' => 'paid',
                        'nha_cung_cap_thanh_toan' => 'vnpay',
                        'thanh_toan_luc' => now(),
                    ]);

                    try {
                        app(\App\Services\DemoShipmentService::class)->syncDueShipments();
                    } catch (\Throwable $t) {}

                    // Broadcast to Admin
                    event(new OrderStatusUpdated($order));
                    event(new NewOrderPlaced($order));

                    return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
                }

                if ($order->trangthai == 'pending') {
                    $this->restoreCartAndCancelOrder($order);
                }

                return response()->json(['RspCode' => '00', 'Message' => 'Payment Failed']);
            }

            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
    }

    public function vnpayReturn(Request $request)
    {
        $inputData = $request->all();
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHashType']);
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);

        $i = 0;
        $hashData = '';
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData.'&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashData = $hashData.urlencode($key).'='.urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        $parts = explode('_', $request->vnp_TxnRef);
        $orderId = $parts[0];
        $frontendUrl = config('app.frontend_url');

        if ($secureHash == $vnp_SecureHash) {
            if ($request->vnp_ResponseCode == '00') {
                $order = DatHang::find($orderId);
                if ($order && $order->trang_thai_thanh_toan !== 'paid') {
                    $order->update([
                        'trangthai' => 'confirmed',
                        'trang_thai_thanh_toan' => 'paid',
                        'nha_cung_cap_thanh_toan' => 'vnpay',
                        'thanh_toan_luc' => now(),
                    ]);

                    try {
                        app(\App\Services\DemoShipmentService::class)->syncDueShipments();
                    } catch (\Throwable $t) {}

                    event(new OrderStatusUpdated($order));
                    event(new NewOrderPlaced($order));
                }

                return redirect($frontendUrl.'/thank-you?status=success&order_id='.$orderId);
            } else {
                $order = DatHang::find($orderId);
                if ($order && $order->trangthai == 'pending') {
                    $this->restoreCartAndCancelOrder($order);
                }

                return redirect($frontendUrl.'/payment-failed');
            }
        }

        return redirect($frontendUrl.'/payment-failed');
    }

    private function restoreCartAndCancelOrder($order)
    {
        DB::transaction(function () use ($order) {
            $orderWithDetails = DatHang::with('chi_tiets')->find($order->id_dathang);
            if ($orderWithDetails) {
                // Restore cart items
                foreach ($orderWithDetails->chi_tiets as $detail) {
                    $cartItem = GioHang::where('id_khachhang', $orderWithDetails->id_khachhang)
                        ->where('id_bienthe', $detail->id_bienthe)
                        ->first();
                    if ($cartItem) {
                        $cartItem->increment('soluong', $detail->soluong);
                    } else {
                        GioHang::create([
                            'id_khachhang' => $orderWithDetails->id_khachhang,
                            'id_bienthe' => $detail->id_bienthe,
                            'soluong' => $detail->soluong,
                        ]);
                    }
                }

                // Cancel the order properly to restore stock and coins
                $orderWithDetails->update([
                    'trangthai' => 'cancelled',
                    'lydo' => 'Thanh toán VNPAY thất bại hoặc bị hủy bởi người dùng',
                    'trang_thai_thanh_toan' => 'failed',
                ]);

                // Restore stock
                foreach ($orderWithDetails->chi_tiets as $chiTiet) {
                    if ($chiTiet->bienThe) {
                        $chiTiet->bienThe->increment('soluong', $chiTiet->soluong);
                    }
                }

                // Restore coins
                if ($orderWithDetails->xu_dung > 0) {
                    $user = $orderWithDetails->user;
                    if ($user) {
                        $user->increment('xu', $orderWithDetails->xu_dung);
                        XuHistory::create([
                            'id_khachhang' => $orderWithDetails->id_khachhang,
                            'so_xu' => $orderWithDetails->xu_dung,
                            'loai_giao_dich' => 'hoan_tra',
                            'id_dathang' => $orderWithDetails->id_dathang,
                            'mo_ta' => 'Hoàn xu do hủy thanh toán VNPAY đơn hàng #'.$orderWithDetails->id_dathang,
                        ]);
                    }
                }
            }
        });
    }
}
