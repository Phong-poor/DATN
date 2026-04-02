<?php

namespace App\Http\Controllers;

use App\Mail\SendResetOtpMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email không tồn tại'],
            ]);
        }

        $otp = rand(100000, 999999);

        $user->reset_otp = $otp;
        $user->reset_otp_expires_at = Carbon::now()->addMinutes(5);
        $user->save();

        try {
            Mail::to($user->email)->send(new SendResetOtpMail($otp));

            return response()->json([
                'message' => 'OTP đã được gửi về email',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gửi mail thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required'],
        ]);

        $user = User::where('email', $request->email)
            ->where('reset_otp', $request->otp)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'otp' => ['OTP không đúng'],
            ]);
        }

        if (!$user->reset_otp_expires_at || now()->gt($user->reset_otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['OTP đã hết hạn'],
            ]);
        }

        return response()->json([
            'message' => 'OTP hợp lệ',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required'],
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        $user = User::where('email', $request->email)
            ->where('reset_otp', $request->otp)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'otp' => ['OTP không đúng'],
            ]);
        }

        if (!$user->reset_otp_expires_at || now()->gt($user->reset_otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['OTP đã hết hạn'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->reset_otp = null;
        $user->reset_otp_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công',
        ]);
    }
}