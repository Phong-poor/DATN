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
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // 4. Lưu file vào disk 'public'
            $filePath = $file->storeAs($path, $filename, 'public');

            // 5. Cập nhật DB
            $user->avatar = $filePath;
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
        $users = User::select('id', 'name', 'email', 'phone', 'role', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::select('id', 'name', 'email', 'phone', 'role', 'status', 'created_at')
            ->findOrFail($id);

        return response()->json($user);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'role' => 'nullable|in:admin,user',
            'status' => 'nullable|in:active,locked',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'] ?? 'user',
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Tạo người dùng thành công',
            'user' => $user->only(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'phone' => 'nullable|string|max:20',
            'role' => 'nullable|in:admin,user',
            'status' => 'nullable|in:active,locked',
            'password' => 'nullable|string|min:8',
        ]);

        if (isset($validated['name']))
            $user->name = $validated['name'];
        if (isset($validated['email']))
            $user->email = $validated['email'];
        if (isset($validated['phone']))
            $user->phone = $validated['phone'];
        if (isset($validated['role']))
            $user->role = $validated['role'];
        if (isset($validated['status']))
            $user->status = $validated['status'];
        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return response()->json([
            'message' => 'Cập nhật thành công',
            'user' => $user->only(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at']),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
        ]);

        $date = (!empty($validated['date_of_birth']))
            ? Carbon::parse($validated['date_of_birth'])->format('Y-m-d')
            : null;

        $genderMap = [
            'male' => 'Nam',
            'female' => 'Nữ',
        ];

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->date_of_birth = $date;
        $user->gender = isset($validated['gender']) ? $genderMap[$validated['gender']] : null;

        $user->save();

        return response()->json([
            'message' => 'Cập nhật thành công',
            'user' => $user
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
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
            'new_password' => 'required|min:8|confirmed',
        ], [
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp'
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 422);
        }

        $otp = rand(100000, 999999);
        $user->reset_otp = $otp;
        $user->reset_otp_expires_at = Carbon::now()->addMinutes(10);
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

        if ((int)$user->reset_otp !== (int)$request->otp || Carbon::now()->gt($user->reset_otp_expires_at)) {
            return response()->json(['message' => 'Mã OTP không đúng hoặc đã hết hạn'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->reset_otp = null;
        $user->reset_otp_expires_at = null;
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
        ], [
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp'
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            \Log::warning("Direct password change failed for User ID: {$user->id}: Current password check failed.");
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        \Log::info("Direct password change succeeded for User ID: {$user->id}");

        return response()->json([
            'message' => 'Đổi mật khẩu thành công!'
        ]);
    }
}
