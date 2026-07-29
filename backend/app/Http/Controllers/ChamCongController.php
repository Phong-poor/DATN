<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ChamCong;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Xử lý đăng ký khuôn mặt, check-in/check-out và quản lý lịch công nhân viên.
 */
class ChamCongController extends Controller
{
    /**
     * Lấy trạng thái chấm công hôm nay của nhân viên hiện tại
     */
    public function getStatus(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        
        $chamCong = ChamCong::where('id_nhanvien', $user->id)
            ->where('ngay_cham_cong', $today)
            ->first();

        return response()->json([
            'success' => true,
            'face_registered' => (bool)$user->face_registered,
            'checked_in' => $chamCong ? ($chamCong->gio_vao !== null) : false,
            'checked_out' => $chamCong ? ($chamCong->gio_ra !== null) : false,
            'today_record' => $chamCong,
            'employee' => [
                'id' => $user->id,
                'name' => $user->ten,
                'email' => $user->email,
                'role' => $user->vaitro,
                'role_name' => $user->ten_vaitro_hienthi,
                'avatar' => $user->anhdaidien,
            ],
            'work_schedule' => [
                'morning' => '08:00 - 12:00',
                'break' => '12:00 - 13:30',
                'afternoon' => '13:30 - 17:30',
            ],
        ]);
    }

    /**
     * Đăng ký khuôn mặt mới cho nhân viên
     */
    public function dangKyKhuonMat(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'numeric',
        ]);

        $user = $request->user();
        if ($user->vaitro === 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ tài khoản nhân viên mới được đăng ký khuôn mặt chấm công.',
            ], 403);
        }
        $user->face_descriptor = json_encode($request->face_descriptor);
        $user->face_registered = true;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký nhận diện khuôn mặt thành công!',
        ]);
    }

    /**
     * Xóa khuôn mặt của nhân viên
     */
    public function xoaKhuonMat(Request $request)
    {
        $user = $request->user();
        $user->face_descriptor = null;
        $user->face_registered = false;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa dữ liệu nhận diện khuôn mặt thành công!',
        ]);
    }

    /**
     * Chấm công Check-in / Check-out bằng Camera
     */
    public function checkInCheckOut(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'numeric',
        ]);

        $user = $request->user();

        if ($user->vaitro === 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản khách hàng không có quyền chấm công nhân viên.',
            ], 403);
        }

        if (!$user->face_registered || !$user->face_descriptor) {
            return response()->json([
                'success' => false,
                'message' => 'Nhân viên chưa đăng ký khuôn mặt. Vui lòng đăng ký trước khi chấm công.',
            ], 422);
        }

        $storedDescriptor = json_decode($user->face_descriptor, true);
        if (!is_array($storedDescriptor) || count($storedDescriptor) !== 128) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu khuôn mặt đã đăng ký không hợp lệ. Vui lòng đăng ký lại.',
            ], 422);
        }

        $faceDistance = $this->calculateEuclideanDistance(
            array_map('floatval', $storedDescriptor),
            array_map('floatval', $request->input('face_descriptor'))
        );

        // Ngưỡng phổ biến của descriptor 128 chiều: nhỏ hơn hoặc bằng 0.55 là cùng một người.
        if ($faceDistance > 0.55) {
            return response()->json([
                'success' => false,
                'message' => 'Khuôn mặt không khớp với nhân viên đang đăng nhập.',
                'match_score' => round($faceDistance, 4),
            ], 422);
        }

        // Chỉ lưu ảnh sau khi đã xác thực đúng khuôn mặt nhân viên.
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        $currentTime = $now->toTimeString();
        
        $chamCong = ChamCong::where('id_nhanvien', $user->id)
            ->where('ngay_cham_cong', $today)
            ->first();

        $type = $chamCong ? 'checkout' : 'checkin';
        $imagePath = $this->saveBase64Image($request->image, $user->id, $today, $type);

        if (!$imagePath) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lưu trữ ảnh chụp chấm công!',
            ], 500);
        }

        // 4. Ghi nhận dữ liệu Check-in hoặc Check-out
        if (!$chamCong) {
            // == CHECK-IN ==
            $gioVaoPhut = $now->hour * 60 + $now->minute;
            $diTrePhut = 0;

            // Ca sáng mốc là 08:00 (480 phút)
            // Ca chiều mốc là 13:30 (810 phút)
            if ($gioVaoPhut <= 12 * 60) {
                // Vào ca sáng
                $diTrePhut = max(0, $gioVaoPhut - 8 * 60);
            } else {
                // Vào ca chiều
                $diTrePhut = max(0, $gioVaoPhut - 13.5 * 60);
            }

            $chamCong = new ChamCong([
                'id_nhanvien' => $user->id,
                'ngay_cham_cong' => $today,
                'gio_vao' => $currentTime,
                'anh_vao' => $imagePath,
                'di_tre_phut' => $diTrePhut,
                'tong_gio' => 0.00,
                'tong_cong' => 0.00,
            ]);
            $chamCong->save();

            return response()->json([
                'success' => true,
                'type' => 'checkin',
                'message' => 'Check-in thành công!',
                'record' => $chamCong,
            ]);
        } else {
            // == CHECK-OUT ==
            if ($chamCong->gio_ra !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hôm nay bạn đã thực hiện check-out rồi. Mỗi ngày chỉ được phép check-out 1 lần!',
                ], 400);
            }

            $gioVaoString = $chamCong->gio_vao;
            $gioVaoCarbon = Carbon::createFromFormat('H:i:s', $gioVaoString);
            
            $gioVaoPhut = $gioVaoCarbon->hour * 60 + $gioVaoCarbon->minute;
            $gioRaPhut = $now->hour * 60 + $now->minute;

            // 1. Tính tổng giờ thực tế làm việc (đã trừ 1.5 tiếng nghỉ trưa 12:00 - 13:30)
            $phutSang = 0;
            $raCaSang = 12 * 60; // 12h00
            $vaoCaSang = 8 * 60;  // 08h00

            if ($gioVaoPhut < $raCaSang) {
                $diemVaoSang = max($vaoCaSang, $gioVaoPhut);
                $diemRaSang = min($raCaSang, $gioRaPhut);
                $phutSang = max(0, $diemRaSang - $diemVaoSang);
            }

            $phutChieu = 0;
            $vaoCaChieu = 13.5 * 60; // 13h30
            $raCaChieu = 17.5 * 60;  // 17h30

            if ($gioRaPhut > $vaoCaChieu) {
                $diemVaoChieu = max($vaoCaChieu, $gioVaoPhut);
                $diemRaChieu = min($raCaChieu, $gioRaPhut);
                $phutChieu = max(0, $diemRaChieu - $diemVaoChieu);
            }

            $tongPhut = $phutSang + $phutChieu;
            $tongGio = round($tongPhut / 60, 2);

            // 2. Tính tổng công (mỗi ca có đi làm/có mặt là được 0.5 công, bất kể về sớm)
            $congSang = ($gioVaoPhut < 12 * 60) ? 0.5 : 0.0;
            $congChieu = ($gioRaPhut >= 13.5 * 60) ? 0.5 : 0.0;
            $tongCong = $congSang + $congChieu;

            // Cập nhật bản ghi
            $chamCong->gio_ra = $currentTime;
            $chamCong->anh_ra = $imagePath;
            $chamCong->tong_gio = $tongGio;
            $chamCong->tong_cong = $tongCong;
            $chamCong->save();

            return response()->json([
                'success' => true,
                'type' => 'checkout',
                'message' => 'Check-out tan ca thành công!',
                'record' => $chamCong,
            ]);
        }
    }

    /**
     * Chấm công nhanh tại máy quản trị: nhận diện khuôn mặt và tự tìm đúng nhân viên.
     */
    public function adminQuickCheck(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'numeric',
        ]);

        $incoming = array_map('floatval', $request->input('face_descriptor'));
        $bestEmployee = null;
        $bestDistance = PHP_FLOAT_MAX;

        $employees = User::where('vaitro', '!=', 'user')
            ->where('face_registered', true)
            ->whereNotNull('face_descriptor')
            ->where('trangthai', '!=', 'locked')
            ->get();

        foreach ($employees as $employee) {
            $stored = json_decode($employee->face_descriptor, true);
            if (!is_array($stored) || count($stored) !== 128) {
                continue;
            }

            $distance = $this->calculateEuclideanDistance(array_map('floatval', $stored), $incoming);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestEmployee = $employee;
            }
        }

        if (!$bestEmployee || $bestDistance > 0.55) {
            return response()->json([
                'success' => false,
                'message' => 'Không nhận diện được nhân viên. Vui lòng đăng ký hoặc cập nhật khuôn mặt trước.',
            ], 422);
        }

        // Tái sử dụng toàn bộ quy tắc check-in/check-out hiện có với đúng nhân viên vừa nhận diện.
        $request->setUserResolver(fn () => $bestEmployee);
        $response = $this->checkInCheckOut($request);
        $payload = $response->getData(true);
        if (($payload['success'] ?? false) === true) {
            $payload['employee'] = [
                'id' => $bestEmployee->id,
                'name' => $bestEmployee->ten,
                'email' => $bestEmployee->email,
                'role_name' => $bestEmployee->ten_vaitro_hienthi,
                'avatar' => $bestEmployee->anhdaidien,
            ];
            $payload['match_score'] = round($bestDistance, 4);
            $response->setData($payload);
        }

        return $response;
    }

    /**
     * Lấy lịch sử chấm công cá nhân của tháng hiện tại
     */
    public function getLichSuCaNhan(Request $request)
    {
        $user = $request->user();
        $thang = $request->query('thang', Carbon::now()->month);
        $nam = $request->query('nam', Carbon::now()->year);

        $records = ChamCong::where('id_nhanvien', $user->id)
            ->whereYear('ngay_cham_cong', $nam)
            ->whereMonth('ngay_cham_cong', $thang)
            ->orderBy('ngay_cham_cong', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

    /**
     * Lấy bảng xếp hạng chấm công tháng hiện tại
     */
    public function getLeaderboard(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $thang = $now->month;
        $nam = $now->year;

        // Truy vấn danh sách tất cả nhân viên và tính tổng công/giờ trong tháng
        $leaderboard = User::where('vaitro', '!=', 'user')
            ->select('id', 'ten', 'anhdaidien', 'vaitro')
            ->withSum(['chamCongs as total_cong' => function ($query) use ($thang, $nam) {
                $query->whereYear('ngay_cham_cong', $nam)
                      ->whereMonth('ngay_cham_cong', $thang);
            }], 'tong_cong')
            ->withSum(['chamCongs as total_gio' => function ($query) use ($thang, $nam) {
                $query->whereYear('ngay_cham_cong', $nam)
                      ->whereMonth('ngay_cham_cong', $thang);
            }], 'tong_gio')
            ->get()
            ->map(function ($item) {
                // Carbon withSum có thể trả về null nếu không có bản ghi nào
                $item->total_cong = (float)($item->total_cong ?? 0.0);
                $item->total_gio = (float)($item->total_gio ?? 0.0);
                return $item;
            })
            // Sắp xếp theo tổng công giảm dần, tổng giờ giảm dần, sau đó theo tên
            ->sort(function ($a, $b) {
                if ($a->total_cong !== $b->total_cong) {
                    return $b->total_cong <=> $a->total_cong;
                }
                if ($a->total_gio !== $b->total_gio) {
                    return $b->total_gio <=> $a->total_gio;
                }
                return strcmp($a->ten, $b->ten);
            })
            ->values();

        // Tìm xếp hạng của user hiện tại
        $myRank = -1;
        foreach ($leaderboard as $index => $item) {
            if ($item->id === $user->id) {
                $myRank = $index + 1; // 1-indexed
                break;
            }
        }

        return response()->json([
            'success' => true,
            'my_rank' => $myRank,
            'total_users' => $leaderboard->count(),
            // Trả về TOP 10 nhân viên
            'leaderboard' => $leaderboard->take(10),
        ]);
    }

    /**
     * Admin: Lấy toàn bộ lịch sử chấm công của nhân viên
     */
    public function adminGetLichSu(Request $request)
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $date = $request->query('date');
        $month = $request->query('month');
        $employeeId = $request->query('employee_id');
        $search = $request->query('search');
        $currentUser = $request->user();
        $isAdmin = $currentUser && $currentUser->vaitro !== 'user';

        $query = ChamCong::with('user:id,ten,email,anhdaidien,vaitro');

        if ($date) {
            $query->where('ngay_cham_cong', $date);
        } elseif ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$year, $monthNumber] = array_map('intval', explode('-', $month));
            $query->whereYear('ngay_cham_cong', $year)
                ->whereMonth('ngay_cham_cong', $monthNumber);
        }

        if ($employeeId && (int) $employeeId !== (int) $currentUser->id) {
            return response()->json([
                'message' => 'Bạn chỉ được xem chi tiết lương của chính mình.',
            ], 403);
        }

        if (!$isAdmin) {
            // Bảo mật bắt buộc ở server: nhân viên chỉ được xem dữ liệu của chính mình,
            // kể cả khi cố truyền employee_id của người khác trên URL.
            $query->where('id_nhanvien', $currentUser->id);
        } elseif ($employeeId) {
            $query->where('id_nhanvien', $employeeId);
        }

        if ($isAdmin && $search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('ten', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $attendanceRows = (clone $query)->get();
        $salaryRows = (clone $query)
            ->where('id_nhanvien', $currentUser->id)
            ->get();
        $baseSalaryPerDay = 350000;
        $penaltyPerTenMinutes = 10000;

        $payrollSummary = $salaryRows->reduce(function ($summary, ChamCong $record) use ($baseSalaryPerDay, $penaltyPerTenMinutes) {
            $worked = !empty($record->gio_vao);
            $lateMinutes = max(0, (int) $record->di_tre_phut);
            $penaltyBlocks = $lateMinutes > 0 ? (int) ceil($lateMinutes / 10) : 0;
            $grossSalary = $worked ? $baseSalaryPerDay : 0;
            $penalty = min($grossSalary, $penaltyBlocks * $penaltyPerTenMinutes);

            $summary['work_days'] += $worked ? 1 : 0;
            $summary['on_time_days'] += $worked && $lateMinutes === 0 ? 1 : 0;
            $summary['late_days'] += $worked && $lateMinutes > 0 ? 1 : 0;
            $summary['gross_salary'] += $grossSalary;
            $summary['total_penalty'] += $penalty;
            $summary['net_salary'] += max(0, $grossSalary - $penalty);
            return $summary;
        }, [
            'work_days' => 0,
            'on_time_days' => 0,
            'late_days' => 0,
            'gross_salary' => 0,
            'total_penalty' => 0,
            'net_salary' => 0,
            'base_salary_per_day' => $baseSalaryPerDay,
            'penalty_per_ten_minutes' => $penaltyPerTenMinutes,
        ]);
        $attendanceSummary = [
            'present' => $attendanceRows->whereNotNull('gio_vao')->pluck('id_nhanvien')->unique()->count(),
            'late' => $attendanceRows->where('di_tre_phut', '>', 0)->count(),
            'total_work_units' => round((float) $attendanceRows->sum('tong_cong'), 2),
            'total_hours' => round((float) $attendanceRows->sum('tong_gio'), 2),
        ];

        $records = $query->orderBy('ngay_cham_cong', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate((int) $request->query('per_page', 15));

        $records->setCollection($records->getCollection()->map(function (ChamCong $record) use ($baseSalaryPerDay, $penaltyPerTenMinutes, $currentUser) {
            if ((int) $record->id_nhanvien !== (int) $currentUser->id) {
                return $record;
            }

            $worked = !empty($record->gio_vao);
            $lateMinutes = max(0, (int) $record->di_tre_phut);
            $penaltyBlocks = $lateMinutes > 0 ? (int) ceil($lateMinutes / 10) : 0;
            $grossSalary = $worked ? $baseSalaryPerDay : 0;
            $penalty = min($grossSalary, $penaltyBlocks * $penaltyPerTenMinutes);

            $record->setAttribute('luong_ngay', $grossSalary);
            $record->setAttribute('tien_phat', $penalty);
            $record->setAttribute('luong_thuc_nhan', max(0, $grossSalary - $penalty));
            $record->setAttribute(
                'ghi_chu_luong',
                !$worked
                    ? 'Chưa có lượt check-in nên chưa tính lương ngày.'
                    : ($lateMinutes > 0
                        ? "Đi làm muộn {$lateMinutes} phút = {$penaltyBlocks} mốc 10 phút × "
                            . number_format($penaltyPerTenMinutes, 0, ',', '.') . 'đ, tổng khấu trừ '
                            . number_format($penalty, 0, ',', '.') . 'đ.'
                        : 'Đi làm đúng giờ, hưởng đủ lương ngày.')
            );
            return $record;
        }));

        return response()->json([
            'success' => true,
            'data' => $records,
            'payroll_summary' => $payrollSummary,
            'attendance_summary' => $attendanceSummary,
        ]);
    }

    /**
     * Danh bạ hồ sơ chấm công, đồng bộ trực tiếp với Vai trò & quyền.
     */
    public function adminGetNhanVien(Request $request)
    {
        abort_unless($request->user()?->vaitro !== 'user', 403, 'Chỉ nhân viên quản trị được xem danh sách nhân viên.');

        $employees = User::where('vaitro', '!=', 'user')
            ->with(['chamCongs' => function ($query) {
                $query->latest('ngay_cham_cong')->latest('created_at')->limit(1);
            }])
            ->orderBy('ten')
            ->get()
            ->map(function (User $employee) {
                $latest = $employee->chamCongs->first();

                return [
                    'id' => $employee->id,
                    'ten' => $employee->ten,
                    'email' => $employee->email,
                    'sodienthoai' => $employee->sodienthoai,
                    'anhdaidien' => $employee->anhdaidien,
                    'ma_vaitro' => $employee->vaitro,
                    'ten_vaitro' => $employee->ten_vaitro_hienthi,
                    'trangthai' => $employee->trangthai,
                    'face_registered' => (bool) $employee->face_registered,
                    'latest_attendance' => $latest ? [
                        'date' => $latest->ngay_cham_cong,
                        'check_in' => $latest->gio_vao,
                        'check_out' => $latest->gio_ra,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $employees,
            'summary' => [
                'total' => $employees->count(),
                'registered' => $employees->where('face_registered', true)->count(),
                'not_registered' => $employees->where('face_registered', false)->count(),
                'locked' => $employees->where('trangthai', 'locked')->count(),
            ],
        ]);
    }

    public function adminDangKyKhuonMat(Request $request, $id)
    {
        $validated = $request->validate([
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'numeric',
        ]);

        $employee = User::where('vaitro', '!=', 'user')->findOrFail($id);
        if ($employee->trangthai === 'locked') {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản nhân viên đang bị khóa, không thể đăng ký khuôn mặt.',
            ], 422);
        }
        $employee->face_descriptor = json_encode(array_map('floatval', $validated['face_descriptor']));
        $employee->face_registered = true;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => "Đã đăng ký khuôn mặt cho {$employee->ten}.",
            'employee' => [
                'id' => $employee->id,
                'ten' => $employee->ten,
                'ten_vaitro' => $employee->ten_vaitro_hienthi,
                'face_registered' => true,
            ],
        ]);
    }

    public function adminXoaKhuonMat(Request $request, $id)
    {
        $employee = User::where('vaitro', '!=', 'user')->findOrFail($id);
        $employee->face_descriptor = null;
        $employee->face_registered = false;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa dữ liệu khuôn mặt của {$employee->ten}.",
        ]);
    }

    /**
     * Tính khoảng cách Euclidean giữa 2 vector descriptor
     */
    private function calculateEuclideanDistance($desc1, $desc2)
    {
        if (count($desc1) !== count($desc2)) {
            return 1.0; // Khoảng cách tối đa nếu khác chiều
        }

        $sum = 0;
        $count = count($desc1);
        for ($i = 0; $i < $count; $i++) {
            $diff = $desc1[$i] - $desc2[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /**
     * Lưu ảnh Base64 chấm công thành tệp tin trên disk
     */
    private function saveBase64Image($base64String, $userId, $date, $type)
    {
        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $typeMatch)) {
                $imageType = strtolower($typeMatch[1]);
                $base64String = substr($base64String, strpos($base64String, ',') + 1);
            } else {
                $imageType = 'jpg';
            }

            $imageData = base64_decode($base64String);
            if ($imageData === false) {
                return null;
            }

            $filename = "chamcong_{$userId}_{$date}_{$type}.{$imageType}";
            $relativePath = "chamcong/{$filename}";

            Storage::disk('public')->put($relativePath, $imageData);

            return "chamcong/{$filename}";
        } catch (\Exception $e) {
            return null;
        }
    }
}
