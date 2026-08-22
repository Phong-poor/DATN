<?php

namespace App\Http\Controllers;

use App\Models\AffiliateCommission;
use App\Models\AffiliateProfile;
use App\Models\AffiliateReferral;
use App\Models\AffiliateWithdrawRequest;
use App\Services\AffiliateBalanceService;
use App\Services\AffiliatePayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminAffiliateController extends Controller
{
    public function __construct(
        private readonly AffiliateBalanceService $balanceService,
        private readonly AffiliatePayoutService $payoutService,
    ) {}

    public function index()
    {
        $profiles = AffiliateProfile::with('user')->orderByDesc('id')->get();
        $referralCounts = AffiliateReferral::selectRaw('id_affiliate_khachhang, COUNT(*) as total')
            ->groupBy('id_affiliate_khachhang')->pluck('total', 'id_affiliate_khachhang');

        $profiles->each(function ($profile) use ($referralCounts) {
            $summary = $this->balanceService->summary((int) $profile->id_khachhang);
            $profile->referrals_count = (int) ($referralCounts[$profile->id_khachhang] ?? 0);
            $profile->pending_withdraw_amount = $summary['reserved_withdrawal'];
            $profile->available_balance = $summary['available_balance'];
        });

        return response()->json([
            'profiles' => $profiles,
            'commissions' => AffiliateCommission::with(['affiliateUser', 'referredUser', 'order'])->orderByDesc('id')->get(),
            'withdraw_requests' => AffiliateWithdrawRequest::with('affiliateUser')->orderByDesc('id')->get(),
            'rules' => [
                'minimum_withdrawal' => (float) config('affiliate.minimum_withdrawal', 100000),
                'maximum_commission_rate' => (float) config('affiliate.max_commission_rate', 30),
            ],
        ]);
    }

    public function updateProfile(Request $request, $id)
    {
        $maxRate = (float) config('affiliate.max_commission_rate', 30);
        $validated = $request->validate([
            'status' => 'sometimes|required|in:pending,active,suspended,rejected',
            'commission_rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:'.$maxRate],
        ]);

        $profile = AffiliateProfile::findOrFail($id);
        if (array_key_exists('status', $validated)) $profile->status = $validated['status'];
        if (array_key_exists('commission_rate', $validated)) $profile->commission_rate = $validated['commission_rate'];
        $profile->save();

        return response()->json(['message' => 'Cập nhật publisher thành công.', 'profile' => $profile->load('user')]);
    }

    public function updateCommissionStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,cancelled',
            'note' => 'nullable|string|max:500',
        ]);

        $commission = DB::transaction(function () use ($id, $validated) {
            $row = AffiliateCommission::with('order')->lockForUpdate()->findOrFail($id);
            $allowed = ['pending' => ['approved', 'cancelled'], 'approved' => ['cancelled']];
            if (!in_array($validated['status'], $allowed[$row->trangthai] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Không thể chuyển hoa hồng từ trạng thái hiện tại sang trạng thái đã chọn.']);
            }

            if ($validated['status'] === 'approved') {
                $order = $row->order;
                $completed = $order && in_array($order->trangthai, ['done', 'completed'], true);
                if (!$completed || $order->trang_thai_thanh_toan !== 'paid') {
                    throw ValidationException::withMessages(['status' => 'Chỉ duyệt hoa hồng khi đơn đã hoàn tất và đã thanh toán.']);
                }
                $row->approved_at = now();
            } else {
                $otherEarned = (float) AffiliateCommission::where('id_affiliate_khachhang', $row->id_affiliate_khachhang)
                    ->where('id', '!=', $row->id)->whereIn('trangthai', ['approved', 'paid'])->sum('so_tien');
                $committed = (float) AffiliateWithdrawRequest::where('id_affiliate_khachhang', $row->id_affiliate_khachhang)
                    ->whereIn('trangthai', ['pending', 'approved', 'processing', 'paid'])->sum('so_tien');
                if ($otherEarned < $committed) {
                    throw ValidationException::withMessages(['status' => 'Không thể hủy vì khoản hoa hồng này đang bảo đảm cho yêu cầu rút tiền.']);
                }
                $row->approved_at = null;
                $row->paid_at = null;
            }

            $row->status = $validated['status'];
            $row->note = $validated['note'] ?? $row->ghichu;
            $row->save();
            $this->balanceService->refreshProfileTotals((int) $row->id_affiliate_khachhang);
            return $row;
        });

        return response()->json(['message' => 'Cập nhật trạng thái hoa hồng thành công.', 'commission' => $commission]);
    }

    public function updateWithdrawStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,paid',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validated['status'] === 'paid') {
            $row = DB::transaction(function () use ($id) {
                $withdraw = AffiliateWithdrawRequest::lockForUpdate()->findOrFail($id);
                if ($withdraw->trangthai !== 'approved') {
                    throw ValidationException::withMessages(['status' => 'Chỉ có thể gửi lệnh chi tiền sau khi yêu cầu đã được duyệt.']);
                }
                $withdraw->status = 'processing';
                $withdraw->bat_dau_xu_ly_luc = now();
                $withdraw->save();
                return $withdraw;
            });

            try {
                $payout = $this->payoutService->send($row);
            } catch (\Throwable $exception) {
                DB::transaction(function () use ($id, $exception) {
                    $withdraw = AffiliateWithdrawRequest::lockForUpdate()->findOrFail($id);
                    if ($withdraw->trangthai === 'processing') {
                        $withdraw->status = 'approved';
                        $withdraw->note = trim(($withdraw->ghichu ? $withdraw->ghichu."\n" : '').'Gửi lệnh chi trả thất bại: '.$exception->getMessage());
                        $withdraw->save();
                    }
                });
                throw ValidationException::withMessages(['status' => 'Không gửi được lệnh chi trả: '.$exception->getMessage()]);
            }

            $row = DB::transaction(function () use ($id, $payout) {
                $withdraw = AffiliateWithdrawRequest::lockForUpdate()->findOrFail($id);
                $withdraw->status = $payout['final_status'];
                $withdraw->nha_cung_cap = $payout['provider_code'];
                $withdraw->ma_giao_dich = $payout['transaction_id'] ?? null;
                $withdraw->du_lieu_chi_tra = $payout;
                $withdraw->note = trim(($withdraw->ghichu ? $withdraw->ghichu."\n" : '').($payout['message'] ?? 'Đã gửi lệnh chi trả.'));
                if ($payout['final_status'] === 'paid') $withdraw->paid_at = now();
                $withdraw->save();
                $this->balanceService->refreshProfileTotals((int) $withdraw->id_affiliate_khachhang);
                return $withdraw;
            });

            return response()->json([
                'message' => $row->trangthai === 'paid' ? 'Chi trả thành công và đã ghi nhận mã giao dịch.' : 'Đã gửi lệnh chi trả, đang chờ nhà cung cấp xác nhận.',
                'withdraw_request' => $row,
            ]);
        }

        $row = DB::transaction(function () use ($id, $validated) {
            $withdraw = AffiliateWithdrawRequest::lockForUpdate()->findOrFail($id);
            $allowed = ['pending' => ['approved', 'rejected'], 'approved' => ['rejected']];
            if (!in_array($validated['status'], $allowed[$withdraw->trangthai] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Yêu cầu rút tiền phải đi đúng thứ tự: chờ duyệt → đã duyệt → đã chi trả.']);
            }

            $withdraw->status = $validated['status'];
            $withdraw->note = $validated['note'] ?? $withdraw->ghichu;
            if ($validated['status'] === 'approved') $withdraw->approved_at = now();
            if ($validated['status'] === 'rejected') {
                $withdraw->approved_at = null;
                $withdraw->paid_at = null;
            }
            $withdraw->save();
            $this->balanceService->refreshProfileTotals((int) $withdraw->id_affiliate_khachhang);
            return $withdraw;
        });

        return response()->json(['message' => 'Cập nhật trạng thái rút tiền thành công.', 'withdraw_request' => $row]);
    }
}
