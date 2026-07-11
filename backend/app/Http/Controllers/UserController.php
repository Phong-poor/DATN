<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;


class UserController extends Controller
{
    /**
     * POST /api/user/avatar
     * Upload and update user avatar
     */
    public function uploadAvatar(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');

            // 1. Tạo tên file duy nhất
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();

            // 2. Định nghĩa thư mục lưu trữ: public/uploads/avatar
            $path = 'uploads/avatar';

            // 3. Xóa avatar cũ nếu có (và không phải mặc định)
            if ($user->anhdaidien && Storage::disk('public')->exists($user->anhdaidien)) {
                Storage::disk('public')->delete($user->anhdaidien);
            }

            // 4. Lưu file vào disk 'public'
            $filePath = $file->storeAs($path, $filename, 'public');

            // 5. Cập nhật DB
            $user->anhdaidien = $filePath;
            $user->save();

            return response()->json([
                'message' => 'Cập nhật ảnh đại diện thành công',
                'avatar_url' => asset('storage/' . $filePath),
                'user' => $user
            ]);
        }

        return response()->json(['message' => 'Không tìm thấy file'], 400);
    }

    public function index()
    {
        $users = User::select('id', 'ten', 'email', 'sodienthoai', 'vaitro', 'trangthai', 'created_at', 'anhdaidien')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::select('id', 'ten', 'email', 'sodienthoai', 'vaitro', 'trangthai', 'created_at', 'anhdaidien')
            ->findOrFail($id);

        return response()->json($user);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten' => 'required|string|max:255',
            'email' => 'required|email|unique:khachhang,email',
            'matkhau' => 'required|string|min:8|confirmed',
            'sodienthoai' => 'nullable|string|max:20',
            'vaitro' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value !== 'user' && !\App\Models\VaiTro::where('ma_vaitro', $value)->exists()) {
                        $fail('Vai trò không hợp lệ.');
                    }
                }
            ],
            'trangthai' => 'nullable|in:active,locked',
        ]);

        $user = User::create([
            'ten' => $validated['ten'],
            'email' => $validated['email'],
            'matkhau' => $validated['matkhau'],
            'sodienthoai' => $validated['sodienthoai'] ?? null,
            'vaitro' => $validated['vaitro'] ?? 'user',
            'trangthai' => $validated['trangthai'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Tạo người dùng thành công',
            'user' => $user->only(['id', 'ten', 'email', 'sodienthoai', 'vaitro', 'trangthai', 'created_at', 'anhdaidien']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $oldRole = $user->vaitro;
        $oldStatus = $user->trangthai;

        $validated = $request->validate([
            'ten' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('khachhang', 'email')->ignore($id)],
            'sodienthoai' => 'nullable|string|max:20',
            'vaitro' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value !== 'user' && !\App\Models\VaiTro::where('ma_vaitro', $value)->exists()) {
                        $fail('Vai trò không hợp lệ.');
                    }
                }
            ],
            'trangthai' => 'nullable|in:active,locked',
            'matkhau' => 'nullable|string|min:8',
        ]);

        if (isset($validated['ten']))
            $user->ten = $validated['ten'];
        if (isset($validated['email']))
            $user->email = $validated['email'];
        if (isset($validated['sodienthoai']))
            $user->sodienthoai = $validated['sodienthoai'];
        if (isset($validated['vaitro'])) {
            // Không được đổi vai trò của NextGen và phongtqpk
            if (in_array($user->email, ['nextgenshop@gmail.com', 'phongtqpk04300@gmail.com']) && $validated['vaitro'] !== 'admin') {
                return response()->json([
                    'message' => 'Không thể thay đổi vai trò của tài khoản Giám đốc sáng lập'
                ], 422);
            }
            // Nhân viên không được đổi sang khách hàng
            if ($user->vaitro !== 'user' && $validated['vaitro'] === 'user') {
                return response()->json([
                    'message' => 'Nhân viên không thể chuyển đổi thành khách hàng'
                ], 422);
            }
            // Khách hàng không được thay đổi vai trò
            if ($user->vaitro === 'user' && $validated['vaitro'] !== 'user') {
                return response()->json([
                    'message' => 'Không thể thay đổi vai trò của tài khoản Khách hàng'
                ], 422);
            }
            $user->vaitro = $validated['vaitro'];
        }
        if (isset($validated['trangthai']))
            $user->trangthai = $validated['trangthai'];
        if (!empty($validated['matkhau'])) {
            $user->matkhau = $validated['matkhau'];
        }

        $user->save();

        if ($oldRole !== $user->vaitro || $oldStatus !== $user->trangthai) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Cập nhật thành công',
            'user' => $user->only(['id', 'ten', 'email', 'sodienthoai', 'vaitro', 'trangthai', 'created_at', 'anhdaidien']),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->merge([
            'ten' => $request->input('ten', $request->input('name')),
            'sodienthoai' => $request->input('sodienthoai', $request->input('phone')),
            'ngaysinh' => $request->input('ngaysinh', $request->input('date_of_birth')),
            'gioitinh' => $request->input('gioitinh', $request->input('gender')),
        ]);

        $validated = $request->validate([
            'ten' => 'required|string|max:255',
            'email' => 'required|email|unique:khachhang,email,' . $user->id,
            'sodienthoai' => 'nullable|string|max:20',
            'ngaysinh' => 'nullable|date',
            'gioitinh' => 'nullable|in:male,female,Nam,Nữ,Nu',
        ]);

        $date = (!empty($validated['ngaysinh']))
            ? Carbon::parse($validated['ngaysinh'])->format('Y-m-d')
            : null;

        $genderMap = [
            'male' => 'Nam',
            'female' => 'Nữ',
        ];

        $user->ten = $validated['ten'];
        $user->email = $validated['email'];
        $user->sodienthoai = $validated['sodienthoai'] ?? null;
        $user->ngaysinh = $date;
        $user->gioitinh = isset($validated['gioitinh']) ? $genderMap[$validated['gioitinh']] : null;

        $user->save();
        $user->refresh();

        return response()->json([
            'message' => 'Cập nhật thành công',
            'user' => $user
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    public function passwordCaptcha(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $left = random_int(2, 9);
        $right = random_int(1, 9);
        $operator = random_int(0, 1) === 1 ? '+' : '-';
        $answer = $operator === '+' ? $left + $right : $left - $right;

        if ($answer < 0) {
            [$left, $right] = [$right, $left];
            $answer = $left - $right;
        }

        Cache::put("password_captcha:{$user->id}", $answer, now()->addMinutes(10));

        return response()->json([
            'question' => "{$left} {$operator} {$right} = ?",
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Xóa người dùng thành công']);
    }

    /**
     * POST /api/user/change-password/request-otp
     * Request OTP to change password
     */
    public function requestPasswordOTP(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
        ]);

        if (!Hash::check($request->current_password, $user->matkhau)) {
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 422);
        }

        $otp = rand(100000, 999999);
        $user->otp_khoiphuc = $otp;
        $user->otp_khoiphuc_hethan_luc = Carbon::now()->addMinutes(10);
        $user->save();

        try {
            Mail::to($user->email)->send(new \App\Mail\SendResetOtpMail($otp));

            return response()->json([
                'message' => 'Mã OTP đã được gửi đến email của bạn',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gửi mail thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/user/change-password/verify-otp
     * Verify OTP and change password
     */
    public function changePasswordWithOTP(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'otp' => 'required',
            'new_password' => 'required|min:8',
        ]);

        if ((int)$user->otp_khoiphuc !== (int)$request->otp || Carbon::now()->gt($user->otp_khoiphuc_hethan_luc)) {
            return response()->json(['message' => 'Mã OTP không đúng hoặc đã hết hạn'], 422);
        }

        $user->matkhau = Hash::make($request->new_password);
        $user->otp_khoiphuc = null;
        $user->otp_khoiphuc_hethan_luc = null;
        $user->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công. Hệ thống sẽ đăng xuất sau vài giây.',
        ]);
    }

    /**
     * PUT /api/user/change-password
     * Change password directly from user settings profile
     */
    public function changePasswordDirect(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            \Log::warning("Direct password change failed: User is unauthenticated");
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        \Log::info("Direct password change requested for User ID: {$user->id}, Email: {$user->email}");

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
            'captcha_answer' => 'required|integer',
        ], [
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp'
        ]);

        $captchaAnswer = Cache::pull("password_captcha:{$user->id}");
        if ($captchaAnswer === null || (int) $request->captcha_answer !== (int) $captchaAnswer) {
            return response()->json([
                'message' => 'Captcha không đúng hoặc đã hết hạn',
                'errors' => [
                    'captcha_answer' => ['Captcha không đúng hoặc đã hết hạn'],
                ],
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->matkhau)) {
            \Log::warning("Direct password change failed for User ID: {$user->id}: Current password check failed.");
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 422);
        }

        $user->matkhau = Hash::make($request->new_password);
        $user->save();

        \Log::info("Direct password change succeeded for User ID: {$user->id}");

        return response()->json([
            'message' => 'Đổi mật khẩu thành công!'
        ]);
    }
}
