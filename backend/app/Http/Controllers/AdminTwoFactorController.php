<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

class AdminTwoFactorController extends Controller
{
    public function status(Request $request)
    {
        $user = $request->user();
        $pending = ! empty($user->two_factor_secret) && empty($user->two_factor_confirmed_at);

        $status = [
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'pending' => $pending,
            'recovery_codes_count' => $user->hasEnabledTwoFactorAuthentication() ? count($user->recoveryCodes()) : 0,
            'password_required' => empty($user->id_google),
        ];

        if ($pending) {
            $status['qr_svg'] = $user->twoFactorQrCodeSvg();
            $status['manual_key'] = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        }

        return response()->json($status);
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable)
    {
        $user = $request->user();
        $this->ensureAdmin($user);
        $this->confirmPasswordWhenRequired($request, $user);

        $enable($user, true);
        $user->refresh();

        return response()->json([
            'message' => 'Hãy quét mã QR và nhập mã 6 số để hoàn tất bật 2FA.',
            'qr_svg' => $user->twoFactorQrCodeSvg(),
            'manual_key' => Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
        ]);
    }

    public function confirm(Request $request, Google2FA $google2fa)
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        $this->ensureAdmin($user);

        if (empty($user->two_factor_secret)) {
            throw ValidationException::withMessages(['code' => ['Mã xác thực không đúng hoặc đã hết hạn.']]);
        }

        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        if (! $google2fa->verifyKey($secret, $validated['code'], 2)) {
            throw ValidationException::withMessages(['code' => ['Mã xác thực không đúng hoặc đã hết hạn. Hãy chờ mã mới rồi thử lại.']]);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        TwoFactorAuthenticationConfirmed::dispatch($user);

        $user->refresh();

        return response()->json([
            'message' => 'Đã bật xác thực hai lớp cho tài khoản Admin.',
            'recovery_codes' => $user->recoveryCodes(),
        ]);
    }

    public function recoveryCodes(Request $request, GenerateNewRecoveryCodes $generate)
    {
        $validated = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();
        $this->ensureAdmin($user);
        $this->verifyCodeOrRecovery($user, $validated['code']);

        $generate($user);
        $user->refresh();

        return response()->json([
            'message' => 'Đã tạo bộ mã khôi phục mới. Bộ mã cũ không còn hiệu lực.',
            'recovery_codes' => $user->recoveryCodes(),
        ]);
    }

    public function showRecoveryCodes(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();
        $this->ensureAdmin($user);
        $this->verifyCodeOrRecovery($user, $validated['code']);

        return response()->json([
            'recovery_codes' => $user->fresh()->recoveryCodes(),
        ]);
    }

    public function disable(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();
        $this->ensureAdmin($user);
        $this->verifyCodeOrRecovery($user, $validated['code']);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => 'Đã tắt xác thực hai lớp.']);
    }

    public function cancelPending(Request $request)
    {
        $user = $request->user();
        $this->ensureAdmin($user);

        if ($user->hasEnabledTwoFactorAuthentication()) {
            throw ValidationException::withMessages([
                'two_factor' => ['2FA đã được bật. Hãy dùng chức năng Tắt 2FA.'],
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => 'Đã hủy thiết lập 2FA đang chờ xác nhận.']);
    }

    private function ensureAdmin($user): void
    {
        abort_if(! $user || $user->vaitro === 'user', 403, 'Chỉ tài khoản quản trị mới được cấu hình 2FA.');
    }

    private function confirmPasswordWhenRequired(Request $request, $user): void
    {
        if (! empty($user->id_google)) {
            return;
        }

        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check((string) $request->input('password'), $user->matkhau)) {
            throw ValidationException::withMessages(['password' => ['Mật khẩu hiện tại không đúng.']]);
        }
    }

    private function verifyCodeOrRecovery($user, string $code): void
    {
        if (! $user->hasEnabledTwoFactorAuthentication()) {
            throw ValidationException::withMessages(['code' => ['2FA chưa được bật.']]);
        }

        $provider = app(TwoFactorAuthenticationProvider::class);
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        if ($provider->verify($secret, preg_replace('/\s+/', '', $code))) {
            return;
        }

        $recoveryCode = collect($user->recoveryCodes())->first(fn ($item) => hash_equals((string) $item, trim($code)));
        if ($recoveryCode) {
            $user->replaceRecoveryCode($recoveryCode);

            return;
        }

        throw ValidationException::withMessages(['code' => ['Mã xác thực hoặc mã khôi phục không đúng.']]);
    }
}
