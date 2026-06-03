<?php

namespace App\Http\Controllers;

use App\Mail\SendResetOtpMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function captcha()
    {
        $token = Str::random(40);
        Cache::put("forgot_password_captcha:{$token}", true, now()->addMinutes(5));

        return response()->json([
            'token' => $token,
            'type' => 'checkbox',
            'label' => 'Xác minh bạn là con người',
            'expires_in' => 300,
        ]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'captcha_token' => ['required', 'string'],
            'captcha_verified' => ['accepted'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'captcha_token.required' => 'Vui lòng tải mã xác minh.',
            'captcha_verified.accepted' => 'Vui lòng xác minh bạn là con người.',
        ]);

        $isCaptchaValid = Cache::pull("forgot_password_captcha:{$request->captcha_token}");

        if (!$isCaptchaValid) {
            throw ValidationException::withMessages([
                'captcha_verified' => ['Captcha không đúng hoặc đã hết hạn.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email không tồn tại.'],
            ]);
        }

        $otp = random_int(100000, 999999);

        $user->reset_otp = $otp;
        $user->reset_otp_expires_at = Carbon::now()->addMinutes(5);
        $user->save();

        try {
            Mail::to($user->email)->send(new SendResetOtpMail($otp));

            return response()->json([
                'message' => 'Mã OTP đã được gửi về email.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gửi email thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'otp.required' => 'Vui lòng nhập mã OTP.',
        ]);

        $user = User::where('email', $request->email)
            ->where('reset_otp', $request->otp)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'otp' => ['Mã OTP không đúng.'],
            ]);
        }

        if (!$user->reset_otp_expires_at || now()->gt($user->reset_otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['Mã OTP đã hết hạn.'],
            ]);
        }

        return response()->json([
            'message' => 'Mã OTP hợp lệ.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required'],
            'password' => [
                'required',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'otp.required' => 'Vui lòng nhập mã OTP.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.regex' => 'Mật khẩu cần có chữ hoa, chữ thường, số và ký tự đặc biệt.',
        ]);

        $user = User::where('email', $request->email)
            ->where('reset_otp', $request->otp)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'otp' => ['Mã OTP không đúng.'],
            ]);
        }

        if (!$user->reset_otp_expires_at || now()->gt($user->reset_otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['Mã OTP đã hết hạn.'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->reset_otp = null;
        $user->reset_otp_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }
}
