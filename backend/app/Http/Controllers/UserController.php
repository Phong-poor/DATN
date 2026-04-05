<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

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
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone'    => 'nullable|string|max:20',
            'role'     => 'nullable|in:admin,support,user',
            'status'   => 'nullable|in:active,locked',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone'    => $validated['phone'] ?? null,
            'role'     => $validated['role'] ?? 'user',
            'status'   => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'Tạo người dùng thành công',
            'user'    => $user->only(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'phone'    => 'nullable|string|max:20',
            'role'     => 'nullable|in:admin,support,user',
            'status'   => 'nullable|in:active,locked',
            'password' => 'nullable|string|min:8',
        ]);

        if (isset($validated['name']))   $user->name   = $validated['name'];
        if (isset($validated['email']))  $user->email  = $validated['email'];
        if (isset($validated['phone']))  $user->phone  = $validated['phone'];
        if (isset($validated['role']))   $user->role   = $validated['role'];
        if (isset($validated['status'])) $user->status = $validated['status'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'Cập nhật thành công',
            'user'    => $user->only(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at']),
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
}