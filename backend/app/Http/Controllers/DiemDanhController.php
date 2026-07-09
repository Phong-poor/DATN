<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiemDanh;
use App\Models\XuHistory;
use App\Models\CauHinhDiemDanh;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DiemDanhController extends Controller
{
    /**
     * Lấy trạng thái điểm danh hiện tại của User (Thứ 2 đến Chủ nhật của tuần hiện tại)
     */
    public function getStatus(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        
        // Lấy cấu hình điểm danh từ database
        $cauhinhs = CauHinhDiemDanh::orderBy('thu_tu', 'asc')->get();
        
        // Lấy ngày đầu tuần (Thứ Hai) và cuối tuần (Chủ Nhật)
        $startOfWeek = Carbon::today()->startOfWeek(); // Mặc định bắt đầu từ Thứ Hai
        $endOfWeek = Carbon::today()->endOfWeek();
        
        // Lấy lịch sử điểm danh của user trong tuần hiện tại
        $thisWeekLogs = DiemDanh::where('id_khachhang', $user->id)
            ->whereBetween('ngay_diem_danh', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get()
            ->keyBy('ngay_diem_danh');

        $todayIso = Carbon::today()->dayOfWeekIso; // 1: Thứ Hai, ..., 7: Chủ Nhật
        $checkedToday = false;

        $daysProgress = [];
        foreach ($cauhinhs as $ch) {
            $dateStr = $startOfWeek->copy()->addDays($ch->thu_tu - 1)->toDateString();
            $hasChecked = isset($thisWeekLogs[$dateStr]);
            
            if ($dateStr === $today && $hasChecked) {
                $checkedToday = true;
            }
            
            $status = 'locked';
            if ($hasChecked) {
                $status = 'checked';
            } elseif ($dateStr === $today) {
                $status = 'current';
            } else {
                $status = 'locked';
            }
            
            $daysProgress[] = [
                'day' => $ch->thu_tu,
                'label' => $ch->ten_ngay,
                'xu' => $ch->so_xu_thuong,
                'status' => $status,
                'date' => $dateStr
            ];
        }

        // Tính chuỗi ngày liên tục (streak) tính từ hôm nay/hôm qua ngược về trước
        $currentStreak = 0;
        $streakDate = $checkedToday ? Carbon::today() : Carbon::yesterday();
        while (true) {
            $hasLog = DiemDanh::where('id_khachhang', $user->id)
                ->where('ngay_diem_danh', $streakDate->toDateString())
                ->exists();
            if ($hasLog) {
                $currentStreak++;
                $streakDate->subDay();
            } else {
                break;
            }
        }

        return response()->json([
            'success' => true,
            'checked_today' => $checkedToday,
            'current_streak' => $currentStreak,
            'next_day' => $todayIso,
            'days_progress' => $daysProgress
        ]);
    }

    /**
     * Thực hiện điểm danh nhận Xu theo ngày trong tuần
     */
    public function checkIn(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $dayOfWeekIso = Carbon::today()->dayOfWeekIso;

        return DB::transaction(function () use ($user, $today, $dayOfWeekIso) {
            // 1. Kiểm tra xem hôm nay đã điểm danh chưa
            $checkedToday = DiemDanh::where('id_khachhang', $user->id)
                ->where('ngay_diem_danh', $today)
                ->exists();

            if ($checkedToday) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã điểm danh hôm nay rồi!'
                ], 400);
            }

            // 2. Lấy cấu hình xu thưởng của ngày hôm nay
            $cauhinh = CauHinhDiemDanh::where('thu_tu', $dayOfWeekIso)->first();
            $xuReward = $cauhinh ? $cauhinh->so_xu_thuong : 5;

            // 3. Tính streak mới
            $currentStreak = 0;
            $streakDate = Carbon::yesterday();
            while (true) {
                $hasLog = DiemDanh::where('id_khachhang', $user->id)
                    ->where('ngay_diem_danh', $streakDate->toDateString())
                    ->exists();
                if ($hasLog) {
                    $currentStreak++;
                    $streakDate->subDay();
                } else {
                    break;
                }
            }
            $newStreak = $currentStreak + 1;

            // 4. Lưu bản ghi Điểm Danh
            DiemDanh::create([
                'id_khachhang' => $user->id,
                'ngay_diem_danh' => $today,
                'streak' => $newStreak,
                'so_xu_nhan' => $xuReward
            ]);

            // 5. Cộng xu cho User
            $user->xu = ($user->xu ?? 0) + $xuReward;
            $user->save();

            // 6. Ghi nhận lịch sử giao dịch Xu
            XuHistory::create([
                'id_khachhang' => $user->id,
                'so_xu' => $xuReward,
                'loai_giao_dich' => 'diem_danh',
                'mo_ta' => "Điểm danh " . ($cauhinh ? $cauhinh->ten_ngay : "hôm nay") . " nhận xu"
            ]);

            return response()->json([
                'success' => true,
                'message' => "Điểm danh thành công! Bạn nhận được $xuReward Xu.",
                'xu_reward' => $xuReward,
                'new_streak' => $newStreak,
                'total_xu' => $user->xu
            ]);
        });
    }

    /**
     * Admin: Lấy danh sách lịch sử điểm danh và thống kê
     */
    public function adminIndex(Request $request)
    {
        $query = DiemDanh::with('user:id,ten,email,anhdaidien');

        // Lọc theo tìm kiếm từ khóa
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('ten', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        // Lọc theo ngày điểm danh
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('ngay_diem_danh', $request->date);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15);

        // Tính toán thống kê hôm nay
        $today = Carbon::today()->toDateString();
        $totalCheckinsToday = DiemDanh::where('ngay_diem_danh', $today)->count();
        $totalXuToday = DiemDanh::where('ngay_diem_danh', $today)->sum('so_xu_nhan');
        $maxStreak = DiemDanh::max('streak') ?? 0;

        return response()->json([
            'success' => true,
            'data' => $logs,
            'stats' => [
                'total_checkins_today' => $totalCheckinsToday,
                'total_xu_today' => (int) $totalXuToday,
                'max_streak' => (int) $maxStreak
            ]
        ]);
    }

    /**
     * Admin: Lấy danh sách cấu hình điểm danh 7 ngày (Thứ 2 - Chủ nhật)
     */
    public function adminGetSettings()
    {
        $settings = CauHinhDiemDanh::orderBy('thu_tu', 'asc')->get();
        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }

    /**
     * Admin: Cập nhật cấu hình xu thưởng 7 ngày
     */
    public function adminUpdateSettings(Request $request)
    {
        $request->validate([
            'settings' => 'required|array|size:7',
            'settings.*.thu_tu' => 'required|integer|min:1|max:7',
            'settings.*.so_xu_thuong' => 'required|integer|min:1',
        ], [
            'settings.*.so_xu_thuong.min' => 'Số xu thưởng phải lớn hơn 0.',
            'settings.*.so_xu_thuong.required' => 'Vui lòng nhập số xu thưởng.',
            'settings.*.so_xu_thuong.integer' => 'Số xu thưởng phải là số nguyên.',
        ]);

        return DB::transaction(function () use ($request) {
            foreach ($request->settings as $item) {
                CauHinhDiemDanh::where('thu_tu', $item['thu_tu'])
                    ->update(['so_xu_thuong' => $item['so_xu_thuong']]);
            }

            $settings = CauHinhDiemDanh::orderBy('thu_tu', 'asc')->get();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật cấu hình điểm danh thành công!',
                'settings' => $settings
            ]);
        });
    }
}
