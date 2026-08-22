<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateReferral;
use App\Models\AffiliateWithdrawRequest;
use App\Services\AffiliateBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AffiliateController extends Controller
{
    public function __construct(private readonly AffiliateBalanceService $balanceService) {}

    public function me(Request $request)
    {
        $user = $request->user();
        $profile = AffiliateProfile::with('user')->where('id_khachhang', $user->id)->first();
        if (!$profile) {
            return response()->json(['active' => false, 'profile' => null, 'stats' => $this->emptyStats(), 'rules' => ['minimum_withdrawal' => (float) config('affiliate.minimum_withdrawal', 100000)]]);
        }

        return response()->json([
            'active' => $profile->trangthai === 'active',
            'profile' => $profile,
            'ref_link' => rtrim(config('app.frontend_url'), '/').'/register?ref='.$profile->ma_affiliate,
            'rules' => ['minimum_withdrawal' => (float) config('affiliate.minimum_withdrawal', 100000)],
            'stats' => [
                'total_referrals' => AffiliateReferral::where('id_affiliate_khachhang', $user->id)->count(),
                ...$this->balanceService->summary((int) $user->id),
            ],
        ]);
    }

    public function activate(Request $request)
    {
        $user = $request->user();
        $profile = AffiliateProfile::firstOrNew(['id_khachhang' => $user->id]);
        if ($profile->exists) {
            if (in_array($profile->trangthai, ['active', 'pending'], true)) {
                return response()->json([
                    'message' => $profile->trangthai === 'active' ? 'Tài khoản affiliate đang hoạt động.' : 'Yêu cầu affiliate đang chờ quản trị viên duyệt.',
                    'profile' => $profile,
                ]);
            }
            throw ValidationException::withMessages(['status' => 'Tài khoản affiliate đang bị khóa hoặc đã bị từ chối. Vui lòng liên hệ quản trị viên.']);
        }

        $profile->ma_affiliate = $this->generateAffiliateCode();
        $profile->ty_le_hoa_hong = config('affiliate.default_commission_rate', 5);
        $profile->trangthai = 'pending';
        $profile->save();

        return response()->json(['message' => 'Đã gửi yêu cầu đăng ký affiliate. Vui lòng chờ quản trị viên duyệt.', 'profile' => $profile], 201);
    }

    public function referrals(Request $request)
    {
        return response()->json(AffiliateReferral::with('referredUser')->where('id_affiliate_khachhang', $request->user()->id)->latest()->get());
    }

    public function commissions(Request $request)
    {
        return response()->json(AffiliateCommission::with(['referredUser', 'order'])->where('id_affiliate_khachhang', $request->user()->id)->latest()->get());
    }

    public function withdraws(Request $request)
    {
        return response()->json(AffiliateWithdrawRequest::where('id_affiliate_khachhang', $request->user()->id)->latest()->get());
    }

    public function requestWithdraw(Request $request)
    {
        $minimum = (float) config('affiliate.minimum_withdrawal', 100000);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:'.$minimum],
            'bank_name' => ['required', 'string', 'max:120'],
            'bank_account_name' => ['required', 'string', 'max:120'],
            'bank_account_number' => ['required', 'string', 'max:50', 'regex:/^[0-9]{6,30}$/'],
        ], [
            'amount.min' => 'Số tiền rút tối thiểu là '.number_format($minimum, 0, ',', '.').'đ.',
            'bank_account_number.regex' => 'Số tài khoản chỉ được gồm 6 đến 30 chữ số.',
        ]);

        $user = $request->user();
        $row = DB::transaction(function () use ($user, $validated) {
            $profile = AffiliateProfile::where('id_khachhang', $user->id)->lockForUpdate()->first();
            if (!$profile || $profile->trangthai !== 'active') {
                throw ValidationException::withMessages(['affiliate' => 'Tài khoản affiliate phải được duyệt và đang hoạt động để rút tiền.']);
            }

            $available = $this->balanceService->summary((int) $user->id)['available_balance'];
            if ((float) $validated['amount'] > $available) {
                throw ValidationException::withMessages(['amount' => 'Số dư khả dụng không đủ để rút.']);
            }

            return AffiliateWithdrawRequest::create([
                'id_affiliate_khachhang' => $user->id,
                'ma_yeu_cau' => 'AFF'.now()->format('ymd').strtoupper(Str::random(8)),
                'so_tien' => $validated['amount'],
                'ten_ngan_hang' => trim($validated['bank_name']),
                'ten_chu_tai_khoan' => mb_strtoupper(trim($validated['bank_account_name'])),
                'so_tai_khoan' => trim($validated['bank_account_number']),
                'trangthai' => 'pending',
            ]);
        });

        return response()->json(['message' => 'Đã gửi yêu cầu rút tiền và tạm giữ số dư tương ứng.', 'withdraw' => $row], 201);
    }

    private function emptyStats(): array
    {
        return ['total_referrals' => 0, 'pending_commission' => 0, 'approved_commission' => 0, 'paid_commission' => 0, 'reserved_withdrawal' => 0, 'available_balance' => 0];
    }

    private function generateAffiliateCode(): string
    {
        do { $code = strtoupper(Str::random(8)); }
        while (AffiliateProfile::where('ma_affiliate', $code)->exists());
        return $code;
    }
}
