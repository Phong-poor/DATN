<?php

namespace App\Http\Controllers;

use App\Mail\RegisterSuccessMail;
use App\Models\AffiliateProfile;
use App\Models\AffiliateReferral;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Socialite\Facades\Socialite;

/**
 * Xử lý đăng ký, đăng nhập, đăng xuất và xác thực tài khoản mạng xã hội.
 */
class AuthController extends Controller
{
    private const TWO_FACTOR_CHALLENGE_TTL = 300;

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

    private function requiresTwoFactor(User $user): bool
    {
        return $user->vaitro !== 'user' && $user->hasEnabledTwoFactorAuthentication();
    }

    private function createTwoFactorChallenge(User $user, bool $remember = false): string
    {
        $plainToken = Str::random(64);
        Cache::put('admin-2fa-challenge:'.hash('sha256', $plainToken), [
            'user_id' => $user->id,
            'remember' => $remember,
            'attempts' => 0,
        ], now()->addSeconds(self::TWO_FACTOR_CHALLENGE_TTL));

        return $plainToken;
    }

    public function verifyTwoFactorChallenge(Request $request)
    {
        $validated = $request->validate([
            'challenge_token' => ['required', 'string', 'size:64'],
            'code' => ['required', 'string', 'max:64'],
        ]);

        $cacheKey = 'admin-2fa-challenge:'.hash('sha256', $validated['challenge_token']);
        $challenge = Cache::get($cacheKey);
        if (! is_array($challenge)) {
            return response()->json(['message' => 'Phiên xác thực đã hết hạn. Vui lòng đăng nhập lại.'], 419);
        }

        $attempts = (int) ($challenge['attempts'] ?? 0) + 1;
        if ($attempts > 5) {
            Cache::forget($cacheKey);

            return response()->json(['message' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng đăng nhập lại.'], 429);
        }
        $challenge['attempts'] = $attempts;
        Cache::put($cacheKey, $challenge, now()->addSeconds(self::TWO_FACTOR_CHALLENGE_TTL));

        $user = User::find($challenge['user_id'] ?? null);
        if (! $user || $user->trangthai === 'locked' || ! $this->requiresTwoFactor($user)) {
            Cache::forget($cacheKey);

            return response()->json(['message' => 'Yêu cầu xác thực không còn hợp lệ.'], 422);
        }

        $code = trim($validated['code']);
        $provider = app(TwoFactorAuthenticationProvider::class);
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $valid = $provider->verify($secret, preg_replace('/\s+/', '', $code));

        if (! $valid) {
            $recovery = collect($user->recoveryCodes())->first(fn ($item) => hash_equals((string) $item, $code));
            if ($recovery) {
                $user->replaceRecoveryCode($recovery);
                $valid = true;
            }
        }

        if (! $valid) {
            return response()->json(['message' => 'Mã xác thực không đúng.'], 422);
        }

        Cache::forget($cacheKey);
        $remember = (bool) ($challenge['remember'] ?? false);
        $token = $this->issueSingleSessionToken($user, $remember ? 'remember_token' : 'session_token');

        return response()->json([
            'message' => 'Xác thực hai lớp thành công.',
            'token' => $token,
            'remember' => $remember,
            'user' => $user,
        ]);
    }

    /**
     * Resolve one canonical Google callback for both OAuth steps.
     * On production, never leak a localhost callback copied from a local .env.
     */
    private function googleRedirectUri(Request $request): string
    {
        $configured = trim((string) config('services.google.redirect'));
        $isLocalCallback = preg_match('#^https?://(localhost|127\.0\.0\.1)(?::\d+)?/#i', $configured) === 1;
        $isPlaceholder = str_contains($configured, 'tenmien.com');

        if ($configured !== '' && ! $isPlaceholder && (! app()->environment('production') || ! $isLocalCallback)) {
            return $configured;
        }

        $scheme = strtolower((string) $request->header('X-Forwarded-Proto')) === 'https'
            ? 'https'
            : $request->getScheme();
        $host = $request->getHttpHost();

        return $scheme.'://'.$host.'/api/auth/google/callback';
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

        if ($this->requiresTwoFactor($user)) {
            return response()->json([
                'message' => 'Vui lòng nhập mã xác thực hai lớp.',
                'two_factor_required' => true,
                'challenge_token' => $this->createTwoFactorChallenge($user, (bool) ($validated['remember'] ?? false)),
                'expires_in' => self::TWO_FACTOR_CHALLENGE_TTL,
            ]);
        }

        $tokenName = 'remember_token';
        $token = $this->issueSingleSessionToken($user, $tokenName);

        return response()->json([
            'message' => 'Đăng nhập thành công.',
            'token' => $token,
            'remember' => true,
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

        $redirectUri = $this->googleRedirectUri($request);
        $driver = Socialite::driver('google')->stateless()->redirectUrl($redirectUri);
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
            $redirectUri = $this->googleRedirectUri($request);
            $driver = Socialite::driver('google')->stateless()->redirectUrl($redirectUri);
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

        if ($this->requiresTwoFactor($user)) {
            $challenge = $this->createTwoFactorChallenge($user);
            if ($isMobile) {
                return redirect($mobileRedirect.'?'.http_build_query(['two_factor_required' => 1, 'challenge' => $challenge]));
            }

            return redirect($this->frontendUrl('/xac-thuc-2fa', ['challenge' => $challenge]));
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
