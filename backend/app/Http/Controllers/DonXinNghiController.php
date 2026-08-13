<?php

namespace App\Http\Controllers;

use App\Models\ChamCong;
use App\Models\DonXinNghi;
use App\Models\LichLamNhanVien;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class DonXinNghiController extends Controller
{
    private const TYPES = ['annual', 'sick', 'personal', 'unpaid', 'maternity', 'bereavement', 'late', 'early_leave', 'remote', 'other'];
    private const DURATIONS = ['full_day', 'morning', 'afternoon', 'hours'];

    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => DonXinNghi::with('nguoiXuLy:id,ten')
                ->where('id_nhanvien', $request->user()->id)
                ->latest()
                ->paginate(min((int) $request->query('per_page', 10), 50)),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->vaitro === 'user') {
            return response()->json(['success' => false, 'message' => 'Chỉ tài khoản nhân viên mới được gửi đơn nghỉ.'], 403);
        }

        $key = 'leave-request:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return response()->json(['success' => false, 'message' => 'Đơn trước đang được xử lý. Vui lòng chờ vài giây.'], 429);
        }
        RateLimiter::hit($key, 5);

        $validated = $request->validate([
            'loai_nghi' => ['required', Rule::in(self::TYPES)],
            'thoi_luong' => ['required', Rule::in(self::DURATIONS)],
            'tu_ngay' => ['required', 'date', 'after_or_equal:today'],
            'den_ngay' => ['required', 'date', 'after_or_equal:tu_ngay'],
            'ly_do' => ['required', 'string', 'min:10', 'max:1000'],
            'nguoi_ban_giao' => ['nullable', 'string', 'max:150'],
            'ghi_chu_ban_giao' => ['nullable', 'string', 'max:1000'],
            'minh_chung' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'tu_ngay.after_or_equal' => 'Không thể gửi đơn nghỉ lùi ngày.',
            'ly_do.min' => 'Lý do nghỉ cần có ít nhất 10 ký tự.',
            'minh_chung.max' => 'Tệp minh chứng không được vượt quá 5MB.',
            'minh_chung.file' => 'Minh chứng phải là một tệp ảnh hoặc PDF hợp lệ.',
            'minh_chung.mimes' => 'Minh chứng chỉ chấp nhận định dạng JPG, JPEG, PNG hoặc PDF.',
        ]);

        $from = Carbon::parse($validated['tu_ngay']);
        $to = Carbon::parse($validated['den_ngay']);
        if ($from->diffInDays($to) > 60) {
            return response()->json(['success' => false, 'message' => 'Một đơn nghỉ không được dài quá 60 ngày.'], 422);
        }
        if ($validated['thoi_luong'] !== 'full_day' && ! $from->isSameDay($to)) {
            return response()->json(['success' => false, 'message' => 'Nghỉ theo buổi hoặc theo giờ chỉ áp dụng trong một ngày.'], 422);
        }

        $overlap = DonXinNghi::where('id_nhanvien', $user->id)
            ->whereIn('trang_thai', ['pending', 'approved', 'needs_info'])
            ->whereDate('tu_ngay', '<=', $to)
            ->whereDate('den_ngay', '>=', $from)
            ->exists();
        if ($overlap) {
            return response()->json(['success' => false, 'message' => 'Bạn đã có đơn nghỉ trùng thời gian đang xử lý hoặc đã duyệt.'], 422);
        }

        $validated['id_nhanvien'] = $user->id;
        $validated['trang_thai'] = 'pending';
        if ($request->hasFile('minh_chung')) {
            $validated['minh_chung'] = $request->file('minh_chung')->store('leave-evidence', 'public');
        }

        $leave = DonXinNghi::create($validated);

        return response()->json(['success' => true, 'message' => 'Đã gửi đơn nghỉ và chuyển quản lý phê duyệt.', 'data' => $leave], 201);
    }

    public function cancel(Request $request, DonXinNghi $donXinNghi)
    {
        abort_unless((int) $donXinNghi->id_nhanvien === (int) $request->user()->id, 403);
        if (! in_array($donXinNghi->trang_thai, ['pending', 'needs_info'], true)) {
            return response()->json(['success' => false, 'message' => 'Chỉ có thể hủy đơn đang chờ xử lý.'], 422);
        }

        $donXinNghi->update(['trang_thai' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Đã hủy đơn nghỉ.']);
    }

    public function resubmit(Request $request, DonXinNghi $donXinNghi)
    {
        abort_unless((int) $donXinNghi->id_nhanvien === (int) $request->user()->id, 403);
        if ($donXinNghi->trang_thai !== 'needs_info') {
            return response()->json(['success' => false, 'message' => 'Đơn này hiện không yêu cầu bổ sung thông tin.'], 409);
        }

        $validated = $request->validate([
            'loai_nghi' => ['required', Rule::in(self::TYPES)],
            'thoi_luong' => ['required', Rule::in(self::DURATIONS)],
            'tu_ngay' => ['required', 'date', 'after_or_equal:today'],
            'den_ngay' => ['required', 'date', 'after_or_equal:tu_ngay'],
            'ly_do' => ['required', 'string', 'min:10', 'max:1000'],
            'nguoi_ban_giao' => ['nullable', 'string', 'max:150'],
            'ghi_chu_ban_giao' => ['nullable', 'string', 'max:1000'],
            'minh_chung' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'tu_ngay.after_or_equal' => 'Không thể gửi lại đơn nghỉ lùi ngày.',
            'ly_do.min' => 'Lý do nghỉ cần có ít nhất 10 ký tự.',
            'minh_chung.file' => 'Minh chứng phải là một tệp ảnh hoặc PDF hợp lệ.',
            'minh_chung.mimes' => 'Minh chứng chỉ chấp nhận định dạng JPG, JPEG, PNG hoặc PDF.',
            'minh_chung.max' => 'Tệp minh chứng không được vượt quá 5MB.',
        ]);

        $from = Carbon::parse($validated['tu_ngay']);
        $to = Carbon::parse($validated['den_ngay']);
        if ($from->diffInDays($to) > 60) {
            return response()->json(['success' => false, 'message' => 'Một đơn nghỉ không được dài quá 60 ngày.'], 422);
        }
        if ($validated['thoi_luong'] !== 'full_day' && ! $from->isSameDay($to)) {
            return response()->json(['success' => false, 'message' => 'Nghỉ theo buổi hoặc theo giờ chỉ áp dụng trong một ngày.'], 422);
        }

        if ($request->hasFile('minh_chung')) {
            $validated['minh_chung'] = $request->file('minh_chung')->store('leave-evidence', 'public');
        } else {
            unset($validated['minh_chung']);
        }

        $donXinNghi->update($validated + [
            'trang_thai' => 'pending',
            'xu_ly_boi' => null,
            'xu_ly_luc' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi thông tin bổ sung. Đơn đã được chuyển lại cho quản trị viên phê duyệt.',
            'data' => $donXinNghi->fresh(),
        ]);
    }

    public function adminIndex(Request $request)
    {
        $this->ensureSuperAdmin($request);
        $query = DonXinNghi::with(['nhanVien:id,ten,email,anhdaidien,vaitro', 'nguoiXuLy:id,ten']);
        if ($request->query('status') === 'actionable') {
            $query->where('trang_thai', 'pending');
        } elseif ($request->query('status') === 'history') {
            $query->whereIn('trang_thai', ['approved', 'rejected', 'cancelled']);
        } elseif ($request->filled('status')) {
            $query->where('trang_thai', $request->query('status'));
        }
        if ($request->filled('employee_id')) $query->where('id_nhanvien', $request->query('employee_id'));

        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        return response()->json(['success' => true, 'data' => $query->latest()->paginate($perPage)]);
    }

    public function review(Request $request, DonXinNghi $donXinNghi)
    {
        $this->ensureSuperAdmin($request);
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'needs_info'])],
            'feedback' => ['nullable', 'string', 'max:1000', Rule::requiredIf($request->input('action') !== 'approve')],
        ]);
        if ((int) $donXinNghi->id_nhanvien === (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Bạn không được tự duyệt đơn nghỉ của chính mình.'], 422);
        }
        if ($donXinNghi->trang_thai !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Đơn này đã được xử lý trước đó.'], 409);
        }
        if ($validated['action'] === 'approve' && ChamCong::where('id_nhanvien', $donXinNghi->id_nhanvien)
            ->whereBetween('ngay_cham_cong', [$donXinNghi->tu_ngay, $donXinNghi->den_ngay])
            ->whereNotNull('gio_vao')->exists()) {
            return response()->json(['success' => false, 'message' => 'Khoảng nghỉ đã có dữ liệu chấm công thực tế. Vui lòng kiểm tra trước khi duyệt.'], 422);
        }

        $status = ['approve' => 'approved', 'reject' => 'rejected', 'needs_info' => 'needs_info'][$validated['action']];
        DB::transaction(function () use ($donXinNghi, $request, $validated, $status) {
            $donXinNghi->update([
                'trang_thai' => $status,
                'phan_hoi_quan_ly' => $validated['feedback'] ?? null,
                'xu_ly_boi' => $request->user()->id,
                'xu_ly_luc' => now(),
            ]);
            if ($status === 'approved') $this->syncApprovedLeaveToAttendance($donXinNghi);
        });

        $message = $status === 'approved'
            ? 'Đã duyệt và đồng bộ đơn nghỉ vào bảng công.'
            : 'Đã cập nhật trạng thái đơn nghỉ.';

        return response()->json(['success' => true, 'message' => $message, 'data' => $donXinNghi->fresh(['nhanVien', 'nguoiXuLy'])]);
    }

    private function syncApprovedLeaveToAttendance(DonXinNghi $leave): void
    {
        // Nghỉ một phần ngày, đi trễ, về sớm và làm từ xa vẫn phải chấm công thực tế.
        if ($leave->thoi_luong !== 'full_day' || in_array($leave->loai_nghi, ['late', 'early_leave', 'remote'], true)) return;

        $assignment = LichLamNhanVien::where('id_nhanvien', $leave->id_nhanvien)->first();
        foreach (CarbonPeriod::create($leave->tu_ngay, $leave->den_ngay) as $date) {
            if ($assignment && ! in_array($date->dayOfWeekIso, $assignment->thu_lam_viec ?: [], true)) continue;
            $workUnit = in_array($leave->loai_nghi, ['annual', 'sick', 'maternity', 'bereavement'], true) ? 1 : 0;
            ChamCong::updateOrCreate(
                ['id_nhanvien' => $leave->id_nhanvien, 'ngay_cham_cong' => $date->toDateString()],
                ['tong_gio' => 0, 'tong_cong' => $workUnit, 'trang_thai' => 'leave_approved', 'ghi_chu' => 'Nghỉ đã duyệt: '.$leave->loai_nghi.' (đơn #'.$leave->id.')']
            );
        }
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless(strtolower((string) $request->user()?->vaitro) === 'admin', 403, 'Chỉ quản trị viên cao nhất được quản lý và phê duyệt đơn nghỉ.');
    }
}
