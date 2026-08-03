<?php

namespace App\Http\Controllers;

use App\Models\BirthdayCouponLog;
use App\Models\Promotion;
use App\Models\User;
use App\Services\BirthdayCouponService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BirthdayCodeController extends Controller
{
    /**
     * GET /api/admin/birthday-codes
     * Lấy danh sách khách hàng sinh nhật kèm trạng thái log gửi mã
     */
    public function index(Request $request, BirthdayCouponService $service)
    {
        $dateStr = $request->input('date', Carbon::today()->toDateString());
        $targetDate = Carbon::parse($dateStr);
        $keyword = $request->input('keyword', '');
        $status = $request->input('status', 'Tất cả');

        $settings = $service->getSettings();
        $activePromotion = null;
        if ($settings->id_voucher) {
            $activePromotion = Promotion::find($settings->id_voucher);
        }
        $defaultCode = $activePromotion ? $activePromotion->code : ($settings->mavoucher ?? 'HAPPYBDAY100');

        // 1. Query users having birthday on this day and month
        $usersQuery = User::whereMonth('ngaysinh', $targetDate->month)
            ->whereDay('ngaysinh', $targetDate->day)
            ->where('vaitro', 'user')
            ->where('trangthai', '!=', 'locked')
            ->whereNotNull('email');

        if (! empty($keyword)) {
            $usersQuery->where(function ($q) use ($keyword) {
                $q->where('ten', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $users = $usersQuery->get();

        // 2. Fetch logs for this date/month in the current year
        $year = Carbon::now()->year;
        $logs = BirthdayCouponLog::whereYear('created_at', $year)
            ->whereMonth('ngaysinh', $targetDate->month)
            ->whereDay('ngaysinh', $targetDate->day)
            ->get()
            ->keyBy('id_khachhang');

        // 3. Map users to logs and format
        $data = $users->map(function ($user) use ($logs, $settings, $defaultCode) {
            $log = $logs->get($user->id);
            $userStatus = 'Chưa gửi';
            $sentTime = '—';
            $errorLog = '';

            if ($log) {
                if ($log->trangthai === 'sent') {
                    $userStatus = 'Đã gửi';
                } elseif ($log->trangthai === 'failed') {
                    $userStatus = 'Gửi lỗi';
                }
                $sentTime = $log->guiluc ? $log->guiluc->format('d/m/Y H:i') : '—';
                $errorLog = $log->thongbaoloi ?? '';
            }

            return [
                'id' => $user->id,
                'name' => $user->ten,
                'email' => $user->email,
                'dob' => $user->ngaysinh ? Carbon::parse($user->ngaysinh)->format('d/m/Y') : '—',
                'mavoucher' => $log->mavoucher ?? $defaultCode,
                'code' => $log->mavoucher ?? $defaultCode,
                'id_voucher' => $log->id_voucher ?? ($settings->id_voucher ?? null),
                'promotion_id' => $log->id_voucher ?? ($settings->id_voucher ?? null),
                'trangthai' => $userStatus,
                'status' => $userStatus,
                'guiluc' => $sentTime,
                'thongbaoloi' => $errorLog,
            ];
        });

        // 4. Filter by status on mapped collection
        if ($status !== 'Tất cả') {
            $data = $data->filter(function ($item) use ($status) {
                return $item['trangthai'] === $status;
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'stats' => [
                'total' => $data->count(),
                'sent' => $data->where('trangthai', 'Đã gửi')->count(),
                'unsent' => $data->where('trangthai', 'Chưa gửi')->count(),
                'failed' => $data->where('trangthai', 'Gửi lỗi')->count(),
            ],
        ]);
    }

    /**
     * POST /api/admin/birthday-codes/scan
     * Quét và chuẩn bị
     */
    public function scan(Request $request, BirthdayCouponService $service)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $dateStr = $request->input('date');
        $users = $service->scanBirthdayUsers($dateStr);
        $count = $users->count();

        return response()->json([
            'success' => true,
            'message' => 'Quét dữ liệu thành công!',
            'count' => $count,
        ]);
    }

    /**
     * POST /api/admin/birthday-codes/send
     * Gửi cho 1 khách hàng
     */
    public function send(Request $request, BirthdayCouponService $service)
    {
        $request->validate([
            'user_id' => 'required|exists:khachhang,id',
            'promotion_id' => 'nullable|integer|exists:vouchers,id',
            'voucher_code' => 'nullable|string',
        ]);

        $userId = $request->input('user_id');
        $user = User::findOrFail($userId);

        $voucherId = $request->input('promotion_id');
        if ($voucherId) {
            $promotion = Promotion::where('id', $voucherId)->where('danhmuc', 'birthday')->first();
        } else {
            $promotion = $service->getBirthdayPromotion();
        }

        if (! $promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn mã khuyến mãi sinh nhật trước khi gửi.',
            ], 422);
        }

        $res = $service->sendBirthdayCouponToUser($user, $promotion);

        if ($res['status'] === 'skipped') {
            return response()->json([
                'success' => false,
                'message' => 'Khách hàng '.$user->ten.' đã nhận mã giảm giá sinh nhật trong năm nay!',
            ], 422);
        }

        if ($res['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Gửi email sinh nhật cho khách hàng '.$user->ten.' thành công!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Lỗi khi gửi email: '.($res['error'] ?? 'Unknown error'),
        ], 500);
    }

    /**
     * POST /api/admin/birthday-codes/send-bulk
     * Gửi hàng loạt
     */
    public function sendBulk(Request $request, BirthdayCouponService $service)
    {
        $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:khachhang,id',
            'user_promotions' => 'nullable|array',
            'user_promotions.*.user_id' => 'required|exists:khachhang,id',
            'user_promotions.*.promotion_id' => 'required|exists:vouchers,id',
        ]);

        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        if ($request->has('user_promotions')) {
            foreach ($request->input('user_promotions') as $item) {
                $user = User::find($item['user_id']);
                $promotion = Promotion::where('id', $item['promotion_id'])->where('danhmuc', 'birthday')->first();
                if ($user && $promotion) {
                    $res = $service->sendBirthdayCouponToUser($user, $promotion);
                    $results[$res['status']]++;
                } else {
                    $results['failed']++;
                }
            }
        } else {
            $promotion = $service->getBirthdayPromotion();
            if (! $promotion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn mã khuyến mãi sinh nhật trước khi gửi.',
                ], 422);
            }
            $userIds = $request->input('user_ids', []);
            $results = $service->sendBulkBirthdayCoupons($userIds, $promotion->id);
        }

        return response()->json([
            'success' => true,
            'message' => "Hoàn thành gửi mã sinh nhật! Đã gửi: {$results['sent']}, lỗi: {$results['failed']}, bỏ qua (đã nhận trước đó): {$results['skipped']}",
            'data' => $results,
        ]);
    }

    /**
     * POST /api/admin/birthday-codes/resend
     * Gửi lại mã (bỏ qua giới hạn 1 lần/năm)
     */
    public function resend(Request $request, BirthdayCouponService $service)
    {
        $request->validate([
            'user_id' => 'required|exists:khachhang,id',
            'promotion_id' => 'nullable|integer|exists:vouchers,id',
        ]);

        $userId = $request->input('user_id');
        $user = User::findOrFail($userId);

        $voucherId = $request->input('promotion_id');
        if ($voucherId) {
            $promotion = Promotion::where('id', $voucherId)->where('danhmuc', 'birthday')->first();
        } else {
            $promotion = $service->getBirthdayPromotion();
        }

        if (! $promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn mã khuyến mãi sinh nhật trước khi gửi.',
            ], 422);
        }

        $res = $service->sendBirthdayCouponToUser($user, $promotion, true); // force = true

        if ($res['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Gửi lại email mã sinh nhật cho khách hàng '.$user->ten.' thành công!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gửi lại email thất bại: '.($res['error'] ?? 'Unknown error'),
        ], 500);
    }

    /**
     * POST /api/admin/birthday-codes/run-auto-now
     * Kích hoạt tự động quét và gửi ngay lập tức (Chạy thử ngay)
     */
    public function runAutoNow(Request $request, BirthdayCouponService $service)
    {
        $request->validate([
            'date' => ['required', 'date'],
            'promotion_id' => ['required', 'integer', 'exists:vouchers,id'],
        ]);
        $date = $request->input('date', Carbon::today()->toDateString());
        $force = $request->boolean('force', true);

        $result = $service->runAutomaticBirthdayCoupons($date, $force, $request->integer('promotion_id'));

        if (isset($result['success']) && $result['success'] === false) {
            return response()->json([
                'success' => false,
                'message' => $result['reason'] ?? 'Có lỗi xảy ra',
            ], 422);
        }

        if (isset($result['users_found']) && $result['users_found'] === 0) {
            return response()->json([
                'success' => true,
                'message' => 'Không có khách hàng sinh nhật trong ngày được chọn.',
                'data' => [
                    'total_birthdays' => 0,
                    'sent' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Birthday auto scan completed',
            'data' => [
                'total_birthdays' => $result['users_found'] ?? 0,
                'sent' => $result['sent'] ?? 0,
                'failed' => $result['failed'] ?? 0,
                'skipped' => $result['skipped'] ?? 0,
            ],
        ]);
    }

    /**
     * GET /api/admin/birthday-codes/logs
     * Lịch sử gửi mã
     */
    public function logs(Request $request)
    {
        $keyword = $request->input('keyword', '');
        $status = $request->input('status', 'Tất cả');

        $query = BirthdayCouponLog::orderBy('id', 'desc');

        if (! empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('email', 'like', "%{$keyword}%")
                    ->orWhere('mavoucher', 'like', "%{$keyword}%")
                    ->orWhereHas('user', function ($uq) use ($keyword) {
                        $uq->where('ten', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($status !== 'Tất cả') {
            $statusVal = $status === 'Gửi thành công' || $status === 'Đã gửi' ? 'sent' : 'failed';
            $query->where('trangthai', $statusVal);
        }

        $logs = $query->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'name' => $log->user ? $log->user->ten : 'N/A',
                'email' => $log->email,
                'mavoucher' => $log->mavoucher,
                'code' => $log->mavoucher,
                'ngaysinh' => $log->ngaysinh ? Carbon::parse($log->ngaysinh)->format('d/m/Y') : '—',
                'guiluc' => $log->guiluc ? $log->guiluc->format('d/m/Y H:i') : '—',
                'trangthai' => $log->trangthai === 'sent' ? 'Đã gửi' : 'Gửi lỗi',
                'status' => $log->trangthai === 'sent' ? 'Đã gửi' : 'Gửi lỗi',
                'thongbaoloi' => $log->thongbaoloi ?? '',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * GET /api/admin/birthday-codes/settings
     * Lấy cấu hình tự động
     */
    public function getSettingsApi(BirthdayCouponService $service)
    {
        $settings = $service->getSettings();
        // Load active birthday promotions
        $promotions = Promotion::where('danhmuc', 'birthday')
            ->whereIn('trangthai', ['running', 'open'])
            ->where(function ($q) {
                $q->whereNull('ngaybatdau')->orWhere('ngaybatdau', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('ngayketthuc')
                    ->orWhere('ngayketthuc', '>=', now()->toDateString());
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => (bool) $settings->kichhoat,
                'run_time' => substr((string) $settings->giochay, 0, 5),
                'valid_days' => (int) ($settings->thoi_han_ngay ?: 30),
                'promotion_id' => $settings->id_voucher,
                'promotion_code' => $settings->mavoucher,
                'email_template_id' => $settings->id_mau_email,
                'send_once_per_year' => (bool) $settings->gui_mot_lan_moi_nam,
                'retry_if_failed' => (bool) $settings->thu_lai_khi_that_bai,
                'notify_admin' => (bool) $settings->thongbao_admin,
            ],
            'promotions' => $promotions,
        ]);
    }

    /**
     * POST /api/admin/birthday-codes/settings
     * Lưu cấu hình tự động
     */
    public function saveSettingsApi(Request $request, BirthdayCouponService $service)
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'run_time' => 'required|date_format:H:i',
            'valid_days' => 'required|integer|min:1|max:365',
            'promotion_id' => 'nullable|integer|exists:vouchers,id',
            'email_template_id' => 'nullable|string|max:100',
            'send_once_per_year' => 'required|boolean',
            'retry_if_failed' => 'required|boolean',
            'notify_admin' => 'required|boolean',
        ]);

        $promoCode = null;
        if ($request->promotion_id) {
            $promo = Promotion::where('id', $request->promotion_id)->where('danhmuc', 'birthday')->first();
            if ($promo) {
                $promoCode = $promo->code;
            }
        }
        if ($request->boolean('enabled') && ! $promoCode) {
            return response()->json(['success' => false, 'message' => 'Mã được chọn không phải mã khuyến mãi sinh nhật hợp lệ.'], 422);
        }

        $settings = $service->getSettings();
        $settings->update([
            'kichhoat' => $request->boolean('enabled'),
            'giochay' => $request->run_time,
            'thoi_han_ngay' => $request->integer('valid_days'),
            'id_voucher' => $request->promotion_id,
            'mavoucher' => $promoCode, // auto sync
            'id_mau_email' => $request->email_template_id,
            'gui_mot_lan_moi_nam' => $request->boolean('send_once_per_year'),
            'thu_lai_khi_that_bai' => $request->boolean('retry_if_failed'),
            'thongbao_admin' => $request->boolean('notify_admin'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lưu cấu hình tự động gửi mã sinh nhật thành công!',
            'data' => $settings,
        ]);
    }
}
