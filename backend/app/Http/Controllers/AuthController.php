<?php

namespace App\Http\Controllers;

use App\Mail\RegisterSuccessMail;
use App\Models\AffiliateProfile;
use App\Models\AffiliateReferral;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Xử lý đăng ký, đăng nhập, đăng xuất và xác thực tài khoản mạng xã hội.
 */
class AuthController extends Controller
{
    private function issueSingleSessionToken(User $user, string $tokenName = 'session_token'): string
    {
        $user->tokens()->delete();

        return $user->createToken($tokenName)->plainTextToken;
    }

    private function frontendUrl(string $path, array $query = []): string
    {
        $url = rtrim(config('app.frontend_url'), '/').$path;

        if (! empty($query)) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }

    public function register(Request $request)
    {
        if (! $request->filled('ten') && $request->filled('name')) {
            $request->merge([
                'ten' => $request->input('name'),
            ]);
        }

        if (! $request->filled('sodienthoai') && $request->filled('phone')) {
            $request->merge([
                'sodienthoai' => $request->input('phone'),
            ]);
        }

        if (! $request->filled('matkhau') && $request->filled('password')) {
            $request->merge([
                'matkhau' => $request->input('password'),
                'matkhau_confirmation' => $request->input('password_confirmation'),
            ]);
        }

        if ($request->has('sodienthoai')) {
            $request->merge([
                'sodienthoai' => preg_replace('/[\s\.-]+/', '', (string) $request->input('sodienthoai')),
            ]);
        }

        $validated = $request->validate([
            'ten' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:khachhang,email'],
            'sodienthoai' => ['required', 'regex:/^0[0-9]{9}$/'],
            'matkhau' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
            'referral_code' => ['nullable', 'string', 'max:20'],
        ], [
            'ten.required' => 'Vui lòng nhập họ và tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'sodienthoai.required' => 'Vui lòng nhập số điện thoại.',
            'sodienthoai.regex' => 'Số điện thoại phải có 10 chữ số và bắt đầu bằng số 0.',
            'matkhau.required' => 'Vui lòng nhập mật khẩu.',
            'matkhau.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'matkhau.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'referral_code.max' => 'Mã giới thiệu không được vượt quá 20 ký tự.',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'ten' => $validated['ten'],
                'email' => $validated['email'],
                'sodienthoai' => $validated['sodienthoai'] ?? null,
                'matkhau' => $validated['matkhau'],
            ]);

            if (! empty($validated['referral_code'])) {
                $refCode = strtoupper($validated['referral_code']);
                $profile = AffiliateProfile::where('ma_affiliate', $refCode)
                    ->where('trangthai', 'active')
                    ->first();

                if (! $profile) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'Mã giới thiệu không hợp lệ hoặc đã ngừng hoạt động.',
                    ], 422);
                }

                if ($profile && (int) $profile->id_khachhang !== (int) $user->id) {
                    AffiliateReferral::firstOrCreate(
                        ['id_khachhang_duoc_gioithieu' => $user->id],
                        [
                            'id_affiliate_khachhang' => $profile->id_khachhang,
                            'ma_ref' => $refCode,
                            'da_dang_ky_luc' => now(),
                        ]
                    );
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Đăng ký thất bại. Vui lòng thử lại.',
                'error' => $e->getMessage(),
            ], 500);
        }

        Mail::to($user->email)->queue(new RegisterSuccessMail($user));

        return response()->json([
            'message' => 'Đăng ký thành công! Email xác nhận đã được gửi.',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        if ($request->has('email')) {
            $request->merge(['email' => trim($request->input('email'))]);
        }

        if (! $request->filled('matkhau') && $request->filled('password')) {
            $request->merge(['matkhau' => $request->input('password')]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'matkhau' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'matkhau.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['matkhau'], $user->matkhau)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không đúng.',
            ], 401);
        }

        if ($user->trangthai === 'locked') {
            return response()->json([
                'message' => 'Tài khoản của bạn đã bị khóa.',
                'code' => 'ACCOUNT_LOCKED',
            ], 423);
        }

        $tokenName = ! empty($validated['remember']) ? 'remember_token' : 'session_token';
        $token = $this->issueSingleSessionToken($user, $tokenName);

        return response()->json([
            'message' => 'Đăng nhập thành công.',
            'token' => $token,
            'remember' => (bool) ($validated['remember'] ?? false),
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Đăng xuất thành công.',
        ]);
    }

    public function redirectGoogle(Request $request)
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            $html = "
            <!DOCTYPE html>
            <html lang='vi'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Chưa Cấu Hình Google Login</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                    .card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); max-width: 500px; padding: 40px; text-align: center; border: 1px solid #e2e8f0; margin: 20px; }
                    h2 { color: #dc2626; margin-top: 0; font-size: 24px; font-weight: 700; }
                    p { color: #475569; font-size: 15px; line-height: 1.6; margin: 16px 0 24px; }
                    code { background: #fee2e2; color: #b91c1c; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-size: 14px; font-weight: bold; }
                    .btn { display: inline-block; background: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; transition: background 0.2s; }
                    .btn:hover { background: #1d4ed8; }
                </style>
            </head>
            <body>
                <div class='card'>
                    <h2>⚠️ Chưa Cấu Hình Đăng Nhập Google</h2>
                    <p>Chức năng đăng nhập Google chưa được kích hoạt ở backend. Vui lòng mở file <code>.env</code> trong thư mục <code>backend</code> và thêm các cấu hình sau:</p>
                    <div style='text-align: left; background: #f1f5f9; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 13px; color: #334155; margin-bottom: 24px;'>
                        GOOGLE_CLIENT_ID=your_client_id<br>
                        GOOGLE_CLIENT_SECRET=your_client_secret<br>
                        GOOGLE_REDIRECT_URI=".e((string) config('services.google.redirect'))."
                    </div>
                    <a href='".rtrim(config('app.frontend_url'), '/')."/login' class='btn'>Quay lại trang Đăng nhập</a>
                </div>
            </body>
            </html>
            ";

            return response($html, 400)->header('Content-Type', 'text/html; charset=utf-8');
        }

        $driver = Socialite::driver('google')->stateless();
        if ($request->boolean('mobile')) {
            $mobileRedirect = (string) $request->query('mobile_redirect', 'nexzen://auth');
            if (! str_starts_with($mobileRedirect, 'nexzen://') && ! str_starts_with($mobileRedirect, 'exp://')) {
                $mobileRedirect = 'nexzen://auth';
            }
            $encodedRedirect = rtrim(strtr(base64_encode($mobileRedirect), '+/', '-_'), '=');
            $driver->with(['state' => 'mobile|'.$encodedRedirect.'|'.strtoupper((string) $request->query('ref', ''))]);
        } elseif ($request->has('ref')) {
            $driver->with(['state' => $request->query('ref')]);
        }

        return $driver->redirect();
    }

    public function handleGoogle(Request $request)
    {
        $oauthState = (string) $request->query('state', '');
        $isMobile = str_starts_with($oauthState, 'mobile|');
        $mobileState = $isMobile ? explode('|', $oauthState, 3) : [];
        $encodedRedirect = $mobileState[1] ?? '';
        $mobileRedirect = $encodedRedirect
            ? base64_decode(strtr($encodedRedirect, '-_', '+/').str_repeat('=', (4 - strlen($encodedRedirect) % 4) % 4))
            : 'nexzen://auth';
        if (! is_string($mobileRedirect) || (! str_starts_with($mobileRedirect, 'nexzen://') && ! str_starts_with($mobileRedirect, 'exp://'))) {
            $mobileRedirect = 'nexzen://auth';
        }
        try {
            $driver = Socialite::driver('google')->stateless();
            if (app()->environment('local')) {
                $driver->setHttpClient(new Client(['verify' => false]));
            }
            $googleUser = $driver->user();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth callback failed', ['message' => $e->getMessage()]);

            return $isMobile ? redirect($mobileRedirect.'?error=google_callback_failed') : redirect($this->frontendUrl('/login', ['social_error' => 'google_callback_failed']));
        }

        $googleId = $googleUser->getId();
        $googleEmail = $googleUser->getEmail();

        $user = User::where('id_google', $googleId)->first();
        if (! $user && $googleEmail) {
            $user = User::where('email', $googleEmail)->first();
        }

        DB::beginTransaction();
        try {
            if (! $user) {
                $user = User::create([
                    'ten' => $googleUser->getName(),
                    'email' => $googleEmail,
                    'matkhau' => Str::random(16),
                    'id_google' => $googleId,
                    'vaitro' => 'user',
                ]);

                // Record referral code if present in the state parameter
                $refCode = $isMobile ? ($mobileState[2] ?? '') : $oauthState;
                if (! empty($refCode)) {
                    $refCode = strtoupper($refCode);
                    $profile = AffiliateProfile::where('ma_affiliate', $refCode)
                        ->where('trangthai', 'active')
                        ->first();

                    if ($profile && (int) $profile->id_khachhang !== (int) $user->id) {
                        AffiliateReferral::firstOrCreate(
                            ['id_khachhang_duoc_gioithieu' => $user->id],
                            [
                                'id_affiliate_khachhang' => $profile->id_khachhang,
                                'ma_ref' => $refCode,
                                'da_dang_ky_luc' => now(),
                            ]
                        );
                    }
                }
            } else {
                if (! $user->id_google) {
                    $user->id_google = $googleId;
                    $user->save();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return $isMobile ? redirect($mobileRedirect.'?error=google_create_failed') : redirect($this->frontendUrl('/login', ['social_error' => 'google_create_failed']));
        }

        if (! $user) {
            return $isMobile ? redirect($mobileRedirect.'?error=google_user_not_found') : redirect($this->frontendUrl('/login', ['social_error' => 'google_user_not_found']));
        }

        $token = $this->issueSingleSessionToken($user, 'auth_token');
        if ($isMobile) {
            return redirect($mobileRedirect.'?'.http_build_query(['token' => $token, 'provider' => 'google']));
        }

        return redirect($this->frontendUrl('/login-success', [
            'token' => $token,
            'provider' => 'google',
        ]));
    }
}
