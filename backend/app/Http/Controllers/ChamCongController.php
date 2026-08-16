<?php

namespace App\Http\Controllers;

use App\Models\CauHinhCaLam;
use App\Models\ChamCong;
use App\Models\LichLamNhanVien;
use App\Models\DonXinNghi;
use App\Models\User;
use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

/**
 * Xử lý đăng ký khuôn mặt, check-in/check-out và quản lý lịch công nhân viên.
 */
class ChamCongController extends Controller
{
    private const FACE_MATCH_THRESHOLD = 0.48;
    private const FACE_DUPLICATE_THRESHOLD = 0.42;
    private const FACE_AMBIGUITY_MARGIN = 0.06;

    /**
     * Lấy trạng thái chấm công hôm nay của nhân viên hiện tại
     */
    public function getStatus(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $this->markPreviousMissedCheckouts($user->id);

        $chamCong = ChamCong::where('id_nhanvien', $user->id)
            ->where('ngay_cham_cong', $today)
            ->first();

        $scheduleError = $this->attendanceScheduleError($user, Carbon::today());

        return response()->json([
            'success' => true,
            'face_registered' => (bool) $user->face_registered,
            'checked_in' => $chamCong ? ($chamCong->gio_vao !== null) : false,
            'checked_out' => $chamCong ? ($chamCong->gio_ra !== null) : false,
            'today_record' => $chamCong,
            'unresolved_attendance' => ChamCong::where('id_nhanvien', $user->id)
                ->where('trang_thai', 'missing_checkout')
                ->latest('ngay_cham_cong')
                ->first(),
            'attendance_allowed' => $scheduleError === null,
            'attendance_block_reason' => $scheduleError,
            'work_assignment' => LichLamNhanVien::where('id_nhanvien', $user->id)->first(),
            'employee' => [
                'id' => $user->id,
                'name' => $user->ten,
                'email' => $user->email,
                'role' => $user->vaitro,
                'role_name' => $user->ten_vaitro_hienthi,
                'avatar' => $user->anhdaidien,
            ],
            'work_schedule' => CauHinhCaLam::current()->toScheduleArray(),
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

        $descriptor = array_map('floatval', $request->input('face_descriptor'));
        if ($duplicate = $this->findDuplicateFaceOwner($descriptor, $user->id)) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_ALREADY_REGISTERED',
                'message' => 'Khuôn mặt này đã được đăng ký cho một nhân viên khác. Mỗi nhân viên chỉ được sử dụng khuôn mặt của chính mình.',
            ], 422);
        }

        $user->face_descriptor = json_encode($descriptor);
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
        $user = $request->user();
        $rateLimitKey = 'attendance-action:'.($user?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $retryAfter = max(1, RateLimiter::availableIn($rateLimitKey));

            return response()->json([
                'success' => false,
                'code' => 'ATTENDANCE_TOO_FAST',
                'message' => "Yêu cầu trước đang được xử lý. Vui lòng chờ {$retryAfter} giây rồi thử lại.",
                'retry_after' => $retryAfter,
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 4);

        return $this->performCheckInCheckOut($request);
    }

    private function performCheckInCheckOut(Request $request)
    {
        $request->validate([
            'image' => ['required', 'string', 'max:2500000', 'regex:/^data:image\/(jpeg|jpg|png);base64,/i'],
            'face_descriptor' => 'required|array|size:128',
            'face_descriptor.*' => 'numeric',
        ], [
            'image.required' => 'Không nhận được ảnh chấm công từ camera.',
            'image.max' => 'Ảnh chấm công quá lớn. Vui lòng thử lại.',
            'image.regex' => 'Ảnh chấm công không đúng định dạng JPG hoặc PNG.',
            'face_descriptor.required' => 'Không nhận được dữ liệu khuôn mặt. Vui lòng nhìn thẳng vào camera.',
            'face_descriptor.array' => 'Dữ liệu khuôn mặt không hợp lệ.',
            'face_descriptor.size' => 'Dữ liệu khuôn mặt chưa đầy đủ. Vui lòng quét lại.',
            'face_descriptor.*.numeric' => 'Dữ liệu nhận diện khuôn mặt bị lỗi. Vui lòng quét lại.',
        ]);

        $user = $request->user();
        $incomingDescriptor = array_map('floatval', $request->input('face_descriptor'));

        if ($user->vaitro === 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản khách hàng không có quyền chấm công nhân viên.',
            ], 403);
        }

        if (! $user->face_registered || ! $user->face_descriptor) {
            if ($this->matchesAnotherRegisteredEmployee($incomingDescriptor, $user->id)) {
                return response()->json([
                    'success' => false,
                    'code' => 'FACE_IDENTITY_MISMATCH',
                    'message' => 'Khuôn mặt không đúng với tài khoản đang chấm công.',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'code' => 'FACE_NOT_REGISTERED',
                'message' => 'Nhân viên chưa đăng ký khuôn mặt. Vui lòng đăng ký trước khi chấm công.',
            ], 422);
        }

        $storedDescriptor = json_decode($user->face_descriptor, true);
        if (! is_array($storedDescriptor) || count($storedDescriptor) !== 128) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu khuôn mặt đã đăng ký không hợp lệ. Vui lòng đăng ký lại.',
            ], 422);
        }

        $faceDistance = $this->calculateEuclideanDistance(
            array_map('floatval', $storedDescriptor),
            $incomingDescriptor
        );

        if ($faceDistance > self::FACE_MATCH_THRESHOLD) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_IDENTITY_MISMATCH',
                'message' => 'Khuôn mặt không đúng với tài khoản đang chấm công.',
                'match_score' => round($faceDistance, 4),
            ], 422);
        }

        // Ngoài việc khớp hồ sơ hiện tại, khuôn mặt phải gần hồ sơ của chính tài khoản
        // hơn mọi nhân viên khác. Điều này chặn việc dùng mặt đồng nghiệp để check-out.
        $identityConflict = $this->detectFaceIdentityConflict(
            $incomingDescriptor,
            $user->id,
            $faceDistance
        );
        if ($identityConflict) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_IDENTITY_MISMATCH',
                'message' => 'Khuôn mặt không đúng với tài khoản đang chấm công.',
            ], 422);
        }

        // Chỉ cập nhật dữ liệu ca cũ sau khi đã xác minh đúng danh tính.
        $missedCheckoutCount = $this->markPreviousMissedCheckouts($user->id);

        // Chỉ lưu ảnh sau khi đã xác thực đúng khuôn mặt nhân viên.
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        $currentTime = $now->toTimeString();

        $chamCong = ChamCong::where('id_nhanvien', $user->id)
            ->where('ngay_cham_cong', $today)
            ->first();

        if (! $chamCong && ($message = $this->attendanceScheduleError($user, $now))) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        // Chỉ kiểm tra khung giờ khi check-in. Một ca đã bắt đầu luôn được check-out,
        // kể cả nhân viên về muộn hơn giờ kết thúc ca.
        if (! $chamCong && ($message = $this->checkInTimeError($user, $now))) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $type = $chamCong ? 'checkout' : 'checkin';
        $imagePath = $this->saveBase64Image($request->image, $user->id, $today, $type);

        if (! $imagePath) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lưu trữ ảnh chụp chấm công!',
            ], 500);
        }

        // 4. Ghi nhận dữ liệu Check-in hoặc Check-out
        if (! $chamCong) {
            // == CHECK-IN ==
            $gioVaoPhut = $now->hour * 60 + $now->minute;
            $diTrePhut = 0;

            $schedule = $this->scheduleMinutes($user);
            if ($schedule['shift'] === 'morning' || ($schedule['shift'] === 'full_day' && $gioVaoPhut <= $schedule['morning_end'])) {
                // Vào ca sáng
                $diTrePhut = max(0, $gioVaoPhut - $schedule['morning_start']);
            } else {
                // Vào ca chiều
                $diTrePhut = max(0, $gioVaoPhut - $schedule['afternoon_start']);
            }

            $chamCong = new ChamCong([
                'id_nhanvien' => $user->id,
                'ngay_cham_cong' => $today,
                'gio_vao' => $currentTime,
                'anh_vao' => $imagePath,
                'di_tre_phut' => $diTrePhut,
                'tong_gio' => 0.00,
                'tong_cong' => 0.00,
                'trang_thai' => 'working',
            ]);
            $chamCong->save();

            return response()->json([
                'success' => true,
                'type' => 'checkin',
                'message' => 'Check-in thành công!',
                'record' => $chamCong,
                'warning' => $missedCheckoutCount > 0
                    ? 'Bạn có ca làm trước đó quên chấm ra. Ca mới vẫn được ghi nhận; vui lòng gửi quản trị viên bổ sung giờ ra.'
                    : null,
            ]);
        } else {
            // == CHECK-OUT ==
            if ($chamCong->gio_ra !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hôm nay bạn đã thực hiện check-out rồi. Mỗi ngày chỉ được phép check-out 1 lần!',
                ], 400);
            }

            $totals = $this->calculateAttendanceTotals($user, $chamCong->gio_vao, $currentTime);
            $tongGio = $totals['tong_gio'];
            $tongCong = $totals['tong_cong'];

            // Cập nhật bản ghi
            $chamCong->gio_ra = $currentTime;
            $chamCong->anh_ra = $imagePath;
            $chamCong->tong_gio = $tongGio;
            $chamCong->tong_cong = $tongCong;
            $chamCong->trang_thai = 'completed';
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
        $secondBestDistance = PHP_FLOAT_MAX;

        $employees = Admin::where('face_registered', true)
            ->whereNotNull('face_descriptor')
            ->where('trangthai', '!=', 'locked')
            ->get();

        foreach ($employees as $employee) {
            $stored = json_decode($employee->face_descriptor, true);
            if (! is_array($stored) || count($stored) !== 128) {
                continue;
            }

            $distance = $this->calculateEuclideanDistance(array_map('floatval', $stored), $incoming);
            if ($distance < $bestDistance) {
                $secondBestDistance = $bestDistance;
                $bestDistance = $distance;
                $bestEmployee = $employee;
            } elseif ($distance < $secondBestDistance) {
                $secondBestDistance = $distance;
            }
        }

        if (! $bestEmployee || $bestDistance > self::FACE_MATCH_THRESHOLD) {
            return response()->json([
                'success' => false,
                'message' => 'Không nhận diện được nhân viên. Vui lòng đăng ký hoặc cập nhật khuôn mặt trước.',
            ], 422);
        }

        if ($secondBestDistance <= self::FACE_MATCH_THRESHOLD
            && ($secondBestDistance - $bestDistance) < self::FACE_AMBIGUITY_MARGIN) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_MATCH_AMBIGUOUS',
                'message' => 'Khuôn mặt đang khớp với nhiều hồ sơ nhân viên. Quản trị viên cần đăng ký lại các khuôn mặt bị trùng trước khi chấm công.',
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
        $this->markPreviousMissedCheckouts($user->id);
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
        $leaderboard = Admin::query()
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
                $item->total_cong = (float) ($item->total_cong ?? 0.0);
                $item->total_gio = (float) ($item->total_gio ?? 0.0);

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
        $this->markPreviousMissedCheckouts();
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
        $isSuperAdmin = $currentUser && strtolower((string) $currentUser->vaitro) === 'admin';

        $query = ChamCong::with('user:id,ten,email,anhdaidien,vaitro');

        if ($date) {
            $query->where('ngay_cham_cong', $date);
        } elseif ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$year, $monthNumber] = array_map('intval', explode('-', $month));
            $query->whereYear('ngay_cham_cong', $year)
                ->whereMonth('ngay_cham_cong', $monthNumber);
        }

        if ($employeeId && ! $isSuperAdmin && (int) $employeeId !== (int) $currentUser->id) {
            return response()->json([
                'message' => 'Bạn chỉ được xem chi tiết lương của chính mình.',
            ], 403);
        }

        if (! $isAdmin) {
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
        $salaryRows = (clone $query);
        if (! $employeeId) {
            $salaryRows->where('id_nhanvien', $currentUser->id);
        }
        $salaryRows = $salaryRows->get();
        $baseSalaryPerDay = 350000;
        $penaltyPerTenMinutes = 10000;

        $payrollSummary = $salaryRows->reduce(function ($summary, ChamCong $record) use ($baseSalaryPerDay, $penaltyPerTenMinutes) {
            $worked = ! empty($record->gio_vao);
            $workUnits = max(0, min(1, (float) $record->tong_cong));
            $lateMinutes = max(0, (int) $record->di_tre_phut);
            $penaltyBlocks = $lateMinutes > 0 ? (int) ceil($lateMinutes / 10) : 0;
            $grossSalary = (int) round($baseSalaryPerDay * $workUnits);
            $penalty = min($grossSalary, $penaltyBlocks * $penaltyPerTenMinutes);

            $summary['work_days'] += $workUnits;
            $summary['on_time_days'] += $workUnits > 0 && $lateMinutes === 0 ? 1 : 0;
            $summary['late_days'] += $workUnits > 0 && $lateMinutes > 0 ? 1 : 0;
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

        $records->setCollection($records->getCollection()->map(function (ChamCong $record) use ($baseSalaryPerDay, $penaltyPerTenMinutes, $currentUser, $isSuperAdmin) {
            if (! $isSuperAdmin && (int) $record->id_nhanvien !== (int) $currentUser->id) {
                return $record;
            }

            $worked = ! empty($record->gio_vao);
            $workUnits = max(0, min(1, (float) $record->tong_cong));
            $lateMinutes = max(0, (int) $record->di_tre_phut);
            $penaltyBlocks = $lateMinutes > 0 ? (int) ceil($lateMinutes / 10) : 0;
            $grossSalary = (int) round($baseSalaryPerDay * $workUnits);
            $penalty = min($grossSalary, $penaltyBlocks * $penaltyPerTenMinutes);

            $record->setAttribute('luong_ngay', $grossSalary);
            $record->setAttribute('tien_phat', $penalty);
            $record->setAttribute('luong_thuc_nhan', max(0, $grossSalary - $penalty));
            $record->setAttribute(
                'ghi_chu_luong',
                $record->trang_thai === 'missing_checkout'
                    ? 'Quên chấm ra: tạm thời chưa tính công và lương cho đến khi quản trị viên xác minh.'
                    : (! $worked
                    ? 'Chưa có lượt check-in nên chưa tính lương ngày.'
                    : ($lateMinutes > 0
                        ? "Đi làm muộn {$lateMinutes} phút = {$penaltyBlocks} mốc 10 phút × "
                            .number_format($penaltyPerTenMinutes, 0, ',', '.').'đ, tổng khấu trừ '
                            .number_format($penalty, 0, ',', '.').'đ.'
                        : "Đã ghi nhận {$workUnits} công theo thời gian làm việc thực tế."))
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

        $employees = Admin::with('lichLamNhanVien')
            ->with(['chamCongs' => function ($query) {
                $query->latest('ngay_cham_cong')->latest('created_at')->limit(1);
            }])
            ->orderBy('ten')
            ->get()
            ->map(function (Admin $employee) {
                $latest = $employee->chamCongs->first();

                return [
                    'id' => $employee->id,
                    'ten' => $employee->ten,
                    'email' => $employee->email,
                    'sodienthoai' => $employee->sodienthoai,
                    'so_cccd' => $employee->so_cccd,
                    'ngaysinh' => $employee->ngaysinh,
                    'gioitinh' => $employee->gioitinh,
                    'ngay_cap_cccd' => $employee->ngay_cap_cccd,
                    'noi_cap_cccd' => $employee->noi_cap_cccd,
                    'co_anh_cccd_mat_truoc' => (bool) $employee->anh_cccd_mat_truoc,
                    'co_anh_cccd_mat_sau' => (bool) $employee->anh_cccd_mat_sau,
                    'anhdaidien' => $employee->anhdaidien,
                    'ma_vaitro' => $employee->vaitro,
                    'ten_vaitro' => $employee->ten_vaitro_hienthi,
                    'trangthai' => $employee->trangthai,
                    'face_registered' => (bool) $employee->face_registered,
                    'work_assignment' => $employee->lichLamNhanVien,
                    'schedule_registered' => (bool) $employee->lichLamNhanVien,
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

        $employee = Admin::findOrFail($id);
        if ($employee->trangthai === 'locked') {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản nhân viên đang bị khóa, không thể đăng ký khuôn mặt.',
            ], 422);
        }
        $descriptor = array_map('floatval', $validated['face_descriptor']);
        if ($duplicate = $this->findDuplicateFaceOwner($descriptor, $employee->id)) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_ALREADY_REGISTERED',
                'message' => "Khuôn mặt này đã thuộc hồ sơ của {$duplicate->ten}. Không thể gán cùng một khuôn mặt cho nhiều nhân viên.",
            ], 422);
        }

        $employee->face_descriptor = json_encode($descriptor);
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
        $employee = Admin::findOrFail($id);
        $employee->face_descriptor = null;
        $employee->face_registered = false;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa dữ liệu khuôn mặt của {$employee->ten}.",
        ]);
    }

    public function adminGetCaLam()
    {
        return response()->json([
            'success' => true,
            'data' => CauHinhCaLam::current()->toScheduleArray(),
        ]);
    }

    public function adminUpdateCaLam(Request $request)
    {
        $validated = $request->validate([
            'morning_start' => ['required', 'date_format:H:i'],
            'morning_end' => ['required', 'date_format:H:i'],
            'afternoon_start' => ['required', 'date_format:H:i'],
            'afternoon_end' => ['required', 'date_format:H:i'],
        ]);

        $minutes = array_map(fn ($time) => $this->timeToMinutes($time), $validated);
        if (! ($minutes['morning_start'] < $minutes['morning_end']
            && $minutes['morning_end'] < $minutes['afternoon_start']
            && $minutes['afternoon_start'] < $minutes['afternoon_end'])) {
            return response()->json([
                'success' => false,
                'message' => 'Thời gian ca làm phải theo đúng thứ tự: bắt đầu sáng, kết thúc sáng, bắt đầu chiều, kết thúc chiều.',
            ], 422);
        }

        $setting = CauHinhCaLam::current();
        $setting->update([
            'ca_sang_bat_dau' => $validated['morning_start'],
            'ca_sang_ket_thuc' => $validated['morning_end'],
            'ca_chieu_bat_dau' => $validated['afternoon_start'],
            'ca_chieu_ket_thuc' => $validated['afternoon_end'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ca làm thành công.',
            'data' => $setting->fresh()->toScheduleArray(),
        ]);
    }

    public function adminGetLichLam(Request $request, $id)
    {
        Admin::findOrFail($id);
        $schedule = LichLamNhanVien::where('id_nhanvien', $id)->first();

        return response()->json(['success' => true, 'data' => $schedule]);
    }

    public function adminUpdateLichLam(Request $request, $id)
    {
        Admin::findOrFail($id);
        $validated = $request->validate([
            'loai_ca' => ['required', 'in:full_day,morning,afternoon'],
            'ngay_bat_dau' => ['required', 'date'],
            'ngay_ket_thuc' => ['nullable', 'date', 'after_or_equal:ngay_bat_dau'],
            'thu_lam_viec' => ['required', 'array', 'min:1'],
            'thu_lam_viec.*' => ['integer', 'between:1,7', 'distinct'],
        ], [
            'loai_ca.required' => 'Vui lòng chọn ca làm việc.',
            'loai_ca.in' => 'Ca làm việc không hợp lệ.',
            'ngay_bat_dau.required' => 'Vui lòng chọn ngày bắt đầu làm việc.',
            'ngay_bat_dau.date' => 'Ngày bắt đầu không hợp lệ.',
            'ngay_ket_thuc.date' => 'Ngày kết thúc không hợp lệ.',
            'ngay_ket_thuc.after_or_equal' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
            'thu_lam_viec.required' => 'Vui lòng chọn ít nhất một ngày làm việc.',
            'thu_lam_viec.min' => 'Vui lòng chọn ít nhất một ngày làm việc.',
            'thu_lam_viec.*.between' => 'Ngày làm việc đã chọn không hợp lệ.',
        ]);

        $schedule = LichLamNhanVien::updateOrCreate(
            ['id_nhanvien' => $id],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật lịch làm việc cho nhân viên.',
            'data' => $schedule->fresh(),
        ]);
    }

    public function adminBoSungGioRa(Request $request, $id)
    {
        $validated = $request->validate([
            'gio_ra' => ['required', 'date_format:H:i'],
            'ly_do' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'gio_ra.required' => 'Vui lòng nhập giờ ra cần bổ sung.',
            'gio_ra.date_format' => 'Giờ ra phải đúng định dạng giờ và phút.',
            'ly_do.required' => 'Bắt buộc nhập lý do điều chỉnh.',
            'ly_do.min' => 'Lý do điều chỉnh cần có ít nhất 10 ký tự.',
        ]);

        $record = ChamCong::with('user')->findOrFail($id);
        if (! $record->gio_vao || $record->gio_ra) {
            return response()->json(['success' => false, 'message' => 'Bản ghi này không còn thiếu giờ ra.'], 422);
        }
        if (Carbon::parse($record->ngay_cham_cong)->isToday()) {
            return response()->json(['success' => false, 'message' => 'Ca hôm nay chưa kết thúc. Chỉ được bổ sung giờ ra cho ngày trước đó.'], 422);
        }

        $gioRa = $validated['gio_ra'].':00';
        if ($this->timeToMinutes($gioRa) <= $this->timeToMinutes($record->gio_vao)) {
            return response()->json(['success' => false, 'message' => 'Giờ ra phải sau giờ vào của ca làm việc.'], 422);
        }

        $totals = $this->calculateAttendanceTotals($record->user, $record->gio_vao, $gioRa);
        $record->update([
            'gio_ra' => $gioRa,
            'tong_gio' => $totals['tong_gio'],
            'tong_cong' => $totals['tong_cong'],
            'trang_thai' => 'manually_completed',
            'ly_do_dieu_chinh' => $validated['ly_do'],
            'dieu_chinh_boi' => $request->user()->id,
            'dieu_chinh_luc' => now(),
            'ghi_chu' => trim(($record->ghi_chu ? $record->ghi_chu."\n" : '').'Giờ ra được quản trị viên bổ sung.'),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Đã bổ sung giờ ra cho {$record->user->ten} và lưu nhật ký điều chỉnh.",
            'data' => $record->fresh(['user', 'nguoiDieuChinh']),
        ]);
    }

    private function markPreviousMissedCheckouts(?int $employeeId = null): int
    {
        return ChamCong::query()
            ->when($employeeId, fn ($query) => $query->where('id_nhanvien', $employeeId))
            ->where('ngay_cham_cong', '<', Carbon::today()->toDateString())
            ->whereNotNull('gio_vao')
            ->whereNull('gio_ra')
            ->where(function ($query) {
                $query->whereNull('trang_thai')->orWhere('trang_thai', 'working');
            })
            ->update([
                'trang_thai' => 'missing_checkout',
                'tong_gio' => 0,
                'tong_cong' => 0,
                'ghi_chu' => 'Quên chấm ra; chờ quản trị viên xác minh và bổ sung.',
                'updated_at' => now(),
            ]);
    }

    private function calculateAttendanceTotals(User $user, string $gioVao, string $gioRa): array
    {
        $gioVaoPhut = $this->timeToMinutes($gioVao);
        $gioRaPhut = $this->timeToMinutes($gioRa);
        $schedule = $this->scheduleMinutes($user);

        $phutSang = $schedule['shift'] === 'afternoon' ? 0 : max(
            0,
            min($schedule['morning_end'], $gioRaPhut) - max($schedule['morning_start'], $gioVaoPhut)
        );
        $phutChieu = $schedule['shift'] === 'morning' ? 0 : max(
            0,
            min($schedule['afternoon_end'], $gioRaPhut) - max($schedule['afternoon_start'], $gioVaoPhut)
        );

        // Mỗi buổi chỉ được tính 0.5 công khi làm thực tế tối thiểu 3 giờ.
        $tongCong = ($phutSang >= 180 ? 0.5 : 0) + ($phutChieu >= 180 ? 0.5 : 0);

        return [
            'tong_gio' => round(($phutSang + $phutChieu) / 60, 2),
            'tong_cong' => $tongCong,
        ];
    }

    private function attendanceScheduleError(User $user, Carbon $date): ?string
    {
        $approvedLeave = DonXinNghi::where('id_nhanvien', $user->id)
            ->where('trang_thai', 'approved')
            ->where('thoi_luong', 'full_day')
            ->whereNotIn('loai_nghi', ['late', 'early_leave', 'remote'])
            ->whereDate('tu_ngay', '<=', $date->toDateString())
            ->whereDate('den_ngay', '>=', $date->toDateString())
            ->first();
        if ($approvedLeave) {
            return 'Hôm nay bạn có đơn nghỉ đã được duyệt. Không cần thực hiện chấm công.';
        }

        $assignment = LichLamNhanVien::where('id_nhanvien', $user->id)->first();
        if (! $assignment) {
            return 'Bạn chưa được đăng ký lịch và ca làm việc. Vui lòng liên hệ quản trị viên.';
        }

        if ($date->lt($assignment->ngay_bat_dau)) {
            return 'Chưa đến ngày bắt đầu làm việc của bạn.';
        }
        if ($assignment->ngay_ket_thuc && $date->gt($assignment->ngay_ket_thuc)) {
            return 'Lịch làm việc của bạn đã kết thúc.';
        }
        if (! in_array($date->dayOfWeekIso, $assignment->thu_lam_viec ?: [], true)) {
            return 'Hôm nay không nằm trong lịch làm việc đã đăng ký.';
        }

        return null;
    }

    private function checkInTimeError(User $user, Carbon $now): ?string
    {
        $schedule = $this->scheduleMinutes($user);
        [$start, $end] = match ($schedule['shift']) {
            'morning' => [$schedule['morning_start'], $schedule['morning_end']],
            'afternoon' => [$schedule['afternoon_start'], $schedule['afternoon_end']],
            default => [$schedule['morning_start'], $schedule['afternoon_end']],
        };
        $current = $now->hour * 60 + $now->minute;
        $earliest = max(0, $start - 60);

        if ($current < $earliest) {
            return 'Chưa đến giờ check-in. Bạn chỉ có thể check-in sớm tối đa 60 phút trước khi ca bắt đầu.';
        }
        if ($current > $end) {
            return 'Ca làm việc hôm nay đã kết thúc, không thể check-in.';
        }

        return null;
    }

    private function scheduleMinutes(?User $user = null): array
    {
        $schedule = CauHinhCaLam::current()->toScheduleArray();
        $shift = $user
            ? (LichLamNhanVien::where('id_nhanvien', $user->id)->value('loai_ca') ?: 'full_day')
            : 'full_day';

        return [
            'shift' => $shift,
            'morning_start' => $this->timeToMinutes($schedule['morning_start']),
            'morning_end' => $this->timeToMinutes($schedule['morning_end']),
            'afternoon_start' => $this->timeToMinutes($schedule['afternoon_start']),
            'afternoon_end' => $this->timeToMinutes($schedule['afternoon_end']),
        ];
    }

    private function timeToMinutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
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

    private function findDuplicateFaceOwner(array $descriptor, int $exceptUserId): ?User
    {
        foreach ($this->registeredFaceOwners($exceptUserId) as $employee) {
            $stored = json_decode($employee->face_descriptor, true);
            if (! is_array($stored) || count($stored) !== 128) continue;

            if ($this->calculateEuclideanDistance(array_map('floatval', $stored), $descriptor) <= self::FACE_DUPLICATE_THRESHOLD) {
                return $employee;
            }
        }

        return null;
    }

    private function detectFaceIdentityConflict(array $descriptor, int $currentUserId, float $currentDistance): bool
    {
        foreach ($this->registeredFaceOwners($currentUserId) as $employee) {
            $stored = json_decode($employee->face_descriptor, true);
            if (! is_array($stored) || count($stored) !== 128) continue;

            $otherDistance = $this->calculateEuclideanDistance(array_map('floatval', $stored), $descriptor);
            if ($otherDistance <= self::FACE_MATCH_THRESHOLD
                && $otherDistance + self::FACE_AMBIGUITY_MARGIN < $currentDistance) {
                return true;
            }
        }

        return false;
    }

    private function matchesAnotherRegisteredEmployee(array $descriptor, int $currentUserId): bool
    {
        foreach ($this->registeredFaceOwners($currentUserId) as $employee) {
            $stored = json_decode($employee->face_descriptor, true);
            if (! is_array($stored) || count($stored) !== 128) continue;

            if ($this->calculateEuclideanDistance(array_map('floatval', $stored), $descriptor) <= self::FACE_MATCH_THRESHOLD) {
                return true;
            }
        }

        return false;
    }

    private function registeredFaceOwners(int $exceptUserId)
    {
        return Admin::query()
            ->where('id', '!=', $exceptUserId)
            ->where('face_registered', true)
            ->whereNotNull('face_descriptor')
            ->where('trangthai', '!=', 'locked')
            ->get(['id', 'ten', 'face_descriptor']);
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
