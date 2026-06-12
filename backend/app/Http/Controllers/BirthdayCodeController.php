<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Promotion;
use App\Models\BirthdayCouponLog;
use App\Models\BirthdayCouponSetting;
use App\Services\BirthdayCouponService;
use Carbon\Carbon;

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
        if ($settings->promotion_id) {
            $activePromotion = Promotion::find($settings->promotion_id);
        }
        $defaultCode = $activePromotion ? $activePromotion->code : ($settings->promotion_code ?? 'HAPPYBDAY100');

        // 1. Query users having birthday on this day and month
        $usersQuery = User::whereMonth('date_of_birth', $targetDate->month)
            ->whereDay('date_of_birth', $targetDate->day);

        if (!empty($keyword)) {
            $usersQuery->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $users = $usersQuery->get();

        // 2. Fetch logs for this date/month in the current year
        $year = Carbon::now()->year;
        $logs = BirthdayCouponLog::whereYear('created_at', $year)
            ->whereMonth('birthday_date', $targetDate->month)
            ->whereDay('birthday_date', $targetDate->day)
            ->get()
            ->keyBy('user_id');

        // 3. Map users to logs and format
        $data = $users->map(function ($user) use ($logs, $settings, $defaultCode) {
            $log = $logs->get($user->id);
            $userStatus = 'Chưa gửi';
            $sentTime = '—';
            $errorLog = '';

            if ($log) {
                if ($log->status === 'sent') {
                    $userStatus = 'Đã gửi';
                } elseif ($log->status === 'failed') {
                    $userStatus = 'Gửi lỗi';
                }
                $sentTime = $log->sent_at ? $log->sent_at->format('d/m/Y H:i') : '—';
                $errorLog = $log->error_message ?? '';
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'dob' => $user->date_of_birth ? Carbon::parse($user->date_of_birth)->format('d/m/Y') : '—',
                'code' => $log->voucher_code ?? $defaultCode,
                'promotion_id' => $log->promotion_id ?? ($settings->promotion_id ?? null),
                'status' => $userStatus,
                'sentTime' => $sentTime,
                'errorLog' => $errorLog,
            ];
        });

        // 4. Filter by status on mapped collection
        if ($status !== 'Tất cả') {
            $data = $data->filter(function ($item) use ($status) {
                return $item['status'] === $status;
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'stats' => [
                'total' => $data->count(),
                'sent' => $data->where('status', 'Đã gửi')->count(),
                'unsent' => $data->where('status', 'Chưa gửi')->count(),
                'failed' => $data->where('status', 'Gửi lỗi')->count(),
            ]
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
            'user_id' => 'required|exists:users,id',
            'promotion_id' => 'nullable|integer|exists:promotions,id',
            'voucher_code' => 'nullable|string',
        ]);

        $userId = $request->input('user_id');
        $user = User::findOrFail($userId);

        $promotionId = $request->input('promotion_id');
        if ($promotionId) {
            $promotion = Promotion::find($promotionId);
        } else {
            $promotion = $service->getBirthdayPromotion();
        }

        if (!$promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn mã khuyến mãi sinh nhật trước khi gửi.',
            ], 422);
        }

        $res = $service->sendBirthdayCouponToUser($user, $promotion);

        if ($res['status'] === 'skipped') {
            return response()->json([
                'success' => false,
                'message' => 'Khách hàng ' . $user->name . ' đã nhận mã giảm giá sinh nhật trong năm nay!',
            ], 422);
        }

        if ($res['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Gửi email sinh nhật cho khách hàng ' . $user->name . ' thành công!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Lỗi khi gửi email: ' . ($res['error'] ?? 'Unknown error'),
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
            'user_ids.*' => 'exists:users,id',
            'user_promotions' => 'nullable|array',
            'user_promotions.*.user_id' => 'required|exists:users,id',
            'user_promotions.*.promotion_id' => 'required|exists:promotions,id',
        ]);

        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        if ($request->has('user_promotions')) {
            foreach ($request->input('user_promotions') as $item) {
                $user = User::find($item['user_id']);
                $promotion = Promotion::find($item['promotion_id']);
                if ($user && $promotion) {
                    $res = $service->sendBirthdayCouponToUser($user, $promotion);
                    $results[$res['status']]++;
                } else {
                    $results['failed']++;
                }
            }
        } else {
            $promotion = $service->getBirthdayPromotion();
            if (!$promotion) {
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
            'data' => $results
        ]);
    }

    /**
     * POST /api/admin/birthday-codes/resend
     * Gửi lại mã (bỏ qua giới hạn 1 lần/năm)
     */
    public function resend(Request $request, BirthdayCouponService $service)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'promotion_id' => 'nullable|integer|exists:promotions,id',
        ]);

        $userId = $request->input('user_id');
        $user = User::findOrFail($userId);
        
        $promotionId = $request->input('promotion_id');
        if ($promotionId) {
            $promotion = Promotion::find($promotionId);
        } else {
            $promotion = $service->getBirthdayPromotion();
        }

        if (!$promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn mã khuyến mãi sinh nhật trước khi gửi.',
            ], 422);
        }

        $res = $service->sendBirthdayCouponToUser($user, $promotion, true); // force = true

        if ($res['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Gửi lại email mã sinh nhật cho khách hàng ' . $user->name . ' thành công!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gửi lại email thất bại: ' . ($res['error'] ?? 'Unknown error'),
        ], 500);
    }

    /**
     * POST /api/admin/birthday-codes/run-auto-now
     * Kích hoạt tự động quét và gửi ngay lập tức (Chạy thử ngay)
     */
    public function runAutoNow(Request $request, BirthdayCouponService $service)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $force = $request->boolean('force', true);

        $result = $service->runAutomaticBirthdayCoupons($date, $force);
        
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
                ]
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
            ]
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

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('email', 'like', "%{$keyword}%")
                  ->orWhere('voucher_code', 'like', "%{$keyword}%")
                  ->orWhereHas('user', function ($uq) use ($keyword) {
                      $uq->where('name', 'like', "%{$keyword}%");
                  });
            });
        }

        if ($status !== 'Tất cả') {
            $statusVal = $status === 'Gửi thành công' || $status === 'Đã gửi' ? 'sent' : 'failed';
            $query->where('status', $statusVal);
        }

        $logs = $query->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'name' => $log->user ? $log->user->name : 'N/A',
                'email' => $log->email,
                'code' => $log->voucher_code,
                'dob' => $log->birthday_date ? Carbon::parse($log->birthday_date)->format('d/m/Y') : '—',
                'sentTime' => $log->sent_at ? $log->sent_at->format('d/m/Y H:i') : '—',
                'status' => $log->status === 'sent' ? 'Đã gửi' : 'Gửi lỗi',
                'errorLog' => $log->error_message ?? '',
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
        $promotions = Promotion::where('category', 'birthday')
            ->whereIn('status', ['running', 'open'])
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $settings,
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
            'run_time' => 'required|string',
            'promotion_id' => 'nullable|integer|exists:promotions,id',
            'email_template_id' => 'nullable|string',
            'send_once_per_year' => 'required|boolean',
            'retry_if_failed' => 'required|boolean',
            'notify_admin' => 'required|boolean',
        ]);

        $promoCode = null;
        if ($request->promotion_id) {
            $promo = Promotion::find($request->promotion_id);
            if ($promo) {
                $promoCode = $promo->code;
            }
        }

        $settings = $service->getSettings();
        $settings->update([
            'enabled' => $request->enabled,
            'run_time' => $request->run_time,
            'promotion_id' => $request->promotion_id,
            'promotion_code' => $promoCode, // auto sync
            'email_template_id' => $request->email_template_id,
            'send_once_per_year' => $request->send_once_per_year,
            'retry_if_failed' => $request->retry_if_failed,
            'notify_admin' => $request->notify_admin,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lưu cấu hình tự động gửi mã sinh nhật thành công!',
            'data' => $settings,
        ]);
    }
}
