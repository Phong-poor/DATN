<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class AdminTwoFactorController extends Controller
{
    public function status(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'pending' => ! empty($user->two_factor_secret) && empty($user->two_factor_confirmed_at),
            'recovery_codes_count' => $user->hasEnabledTwoFactorAuthentication() ? count($user->recoveryCodes()) : 0,
            'password_required' => empty($user->id_google),
        ]);
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

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm)
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        $this->ensureAdmin($user);

        try {
            $confirm($user, $validated['code']);
        } catch (ValidationException) {
            throw ValidationException::withMessages(['code' => ['Mã xác thực không đúng hoặc đã hết hạn.']]);
        }

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
