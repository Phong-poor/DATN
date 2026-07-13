<?php

namespace App\Http\Controllers;

use App\Models\VongQuay;
use App\Models\LichSuQuay;
use App\Models\UserVoucher;
use App\Models\Promotion;
use App\Models\User;
use App\Models\XuHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class VongQuayController extends Controller
{
    /**
     * Synchronize the 'retry' slot: ensure exactly one exists and its rate is (100 - sum(others)).
     */
    private function syncRetrySlot()
    {
        $retrySlots = VongQuay::where('loai', 'retry')->orderBy('id', 'asc')->get();
        if ($retrySlots->isEmpty()) {
            $retrySlot = VongQuay::create([
                'ten' => 'Chúc bạn may mắn lần sau',
                'ti_le' => 100,
                'loai' => 'retry',
                'mau_sac' => '#000000',
                'mau_chu' => '#ffffff',
            ]);
        } else {
            $retrySlot = $retrySlots->first();
            if ($retrySlots->count() > 1) {
                VongQuay::where('loai', 'retry')->where('id', '!=', $retrySlot->id)->delete();
            }
        }

        $sumNonRetry = VongQuay::where('id', '!=', $retrySlot->id)
            ->where('loai', '!=', 'retry')
            ->sum('ti_le');

        $retrySlot->ti_le = max(0, 100 - $sumNonRetry);
        $retrySlot->save();

        return $retrySlot;
    }

    /**
     * Get list of wheel prizes for public/user view.
     */
    public function prizes()
    {
        $this->syncRetrySlot();
        $prizes = VongQuay::orderBy('id', 'asc')->get();
        return response()->json([
            'success' => true,
            'data' => $prizes,
        ]);
    }

    /**
     * Get user's own spin history.
     */
    public function lichSu(Request $request)
    {
        $user = $request->user();
        $history = LichSuQuay::where('id_khachhang', $user->id)
            ->where('loai_qua', '!=', 'claim') // Exclude daily claims from winning history
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Securely spin the wheel on the backend.
     */
    public function quay(Request $request)
    {
        $user = $request->user();

        // 1. Kiểm tra giới hạn 1 lần quay/ngày cho User (Admin không bị giới hạn)
        if ($user->vaitro !== 'admin') {
            $hasSpunToday = LichSuQuay::where('id_khachhang', $user->id)
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if ($hasSpunToday) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã quay vòng quay hôm nay rồi. Hãy quay lại vào ngày mai nhé!',
                ], 400);
            }
        }

        // Sync retry slot rate before running spin
        $this->syncRetrySlot();

        // 2. Fetch all prizes - require at least 2 slots
        $prizes = VongQuay::all();
        if ($prizes->count() < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Vòng quay cần có ít nhất 2 ô phần thưởng mới có thể quay. Vui lòng liên hệ Admin!',
            ], 400);
        }

        // 3. Backend weighted random selection
        $totalWeight = $prizes->sum('ti_le');
        if ($totalWeight <= 0) {
            $totalWeight = 100; // Fallback
        }

        $rand = mt_rand(0, $totalWeight * 100) / 100;
        $winningPrize = null;
        $sum = 0;

        foreach ($prizes as $prize) {
            $sum += (float) $prize->ti_le;
            if ($rand <= $sum) {
                $winningPrize = $prize;
                break;
            }
        }

        // Fallback in case of rounding gaps
        if (!$winningPrize) {
            $winningPrize = $prizes->first();
        }

        // Find the index of the winning prize in the ordered list
        $orderedPrizes = VongQuay::orderBy('id', 'asc')->get();
        $winningIndex = 0;
        foreach ($orderedPrizes as $index => $op) {
            if ($op->id === $winningPrize->id) {
                $winningIndex = $index;
                break;
            }
        }

        // 4. Process the reward inside a database transaction to prevent race conditions
        DB::beginTransaction();
        try {
            // Deduct ticket (Disabled for testing)
            $currentUser = User::findOrFail($user->id);
            // $currentUser->decrement('luot_quay');

            // Apply award based on prize type
            switch ($winningPrize->loai) {
                case 'ticket':
                    $ticketsToAdd = intval($winningPrize->giatri) ?: 1;
                    $currentUser->increment('luot_quay', $ticketsToAdd);
                    break;

                case 'coin':
                    $coinsToAdd = intval($winningPrize->giatri) ?: 0;
                    if ($coinsToAdd > 0) {
                        $currentUser->increment('xu', $coinsToAdd);

                        // Write to coins log table
                        DB::table('lich_su_xu')->insert([
                            'id_khachhang' => $user->id,
                            'so_xu' => $coinsToAdd,
                            'loai_giao_dich' => 'nhan_xu_vong_quay',
                            'mo_ta' => 'Nhận xu từ Vòng Quay May Mắn',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    break;

                case 'voucher':
                    if ($winningPrize->id_voucher) {
                        $voucher = Promotion::find($winningPrize->id_voucher);
                        if (!$voucher) {
                            $winningPrize->id_voucher = null;
                            $winningPrize->save();

                            return response()->json([
                                'success' => false,
                                'message' => 'Phần thưởng voucher này chưa được liên kết với mã voucher hợp lệ. Vui lòng liên hệ Admin cập nhật vòng quay.',
                            ], 422);
                        }

                        UserVoucher::create([
                            'id_user' => $user->id,
                            'id_voucher' => $voucher->id,
                            'trang_thai' => 0, // Unused
                            'ngay_nhan' => now(),
                        ]);
                    }
                    break;

                // 'gift' (Balo, Mouse) and 'retry' (Good luck next time) require no balance changes
            }

            // Save log to spin history
            LichSuQuay::create([
                'id_khachhang' => $user->id,
                'id_vongquay' => $winningPrize->id,
                'ten_qua' => $winningPrize->ten,
                'loai_qua' => $winningPrize->loai,
                'gia_tri_qua' => $winningPrize->giatri,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý kết quả: ' . $e->getMessage(),
            ], 500);
        }

        // Return updated balances
        $updatedUser = User::find($user->id);

        return response()->json([
            'success' => true,
            'winningIndex' => $winningIndex,
            'prize' => $winningPrize,
            'tickets' => $updatedUser->luot_quay,
            'xu' => $updatedUser->xu,
        ]);
    }

    /**
     * Claim daily tickets (limit once per calendar day).
     */
    public function nhanLuotHangNgay(Request $request)
    {
        $user = $request->user();

        // Check if user has claimed today
        $todayClaim = LichSuQuay::where('id_khachhang', $user->id)
            ->where('loai_qua', 'claim')
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($todayClaim) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã nhận lượt quay ngày hôm nay rồi. Hãy quay lại vào ngày mai!',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $currentUser = User::findOrFail($user->id);
            $currentUser->increment('luot_quay', 3);

            // Log this claim
            LichSuQuay::create([
                'id_khachhang' => $user->id,
                'id_vongquay' => null,
                'ten_qua' => 'Nhận lượt quay hàng ngày',
                'loai_qua' => 'claim',
                'gia_tri_qua' => '3',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhận lượt quay: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nhận 3 lượt quay miễn phí hàng ngày thành công!',
            'tickets' => $currentUser->luot_quay,
        ]);
    }

    // =========================================================================
    // ADMIN ENDPOINTS (Requires admin middleware)
    // =========================================================================

    /**
     * Get all wheel slots with voucher relation.
     */
    public function adminIndex()
    {
        $this->syncRetrySlot();
        $slots = VongQuay::with('voucher')->orderBy('id', 'asc')->get();
        // Also load all promotions/vouchers so the admin can link them
        $vouchers = Promotion::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'slots' => $slots,
            'vouchers' => $vouchers,
        ]);
    }

    /**
     * Create a new prize slot.
     */
    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'ten' => 'required|string|max:100',
            'ti_le' => 'required|numeric|min:0|max:100',
            'loai' => 'required|string|in:voucher,gift,coin,ticket,retry',
            'giatri' => 'nullable|max:255',
            'id_voucher' => 'nullable|exists:vouchers,id',
            'mau_sac' => 'nullable|string|max:20',
            'mau_chu' => 'nullable|string|max:20',
        ]);

        if ($validated['loai'] === 'retry') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ được phép có một ô "Chúc may mắn lần sau".',
            ], 422);
        }

        // Validate: sum of all non-retry slots + new ti_le cannot exceed 100%
        $currentNonRetryTotal = VongQuay::where('loai', '!=', 'retry')->sum('ti_le');
        if ($currentNonRetryTotal + $validated['ti_le'] > 100) {
            $remaining = 100 - $currentNonRetryTotal;
            return response()->json([
                'success' => false,
                'message' => "Tổng tỷ lệ của các ô khác là {$currentNonRetryTotal}%. Bạn chỉ có thể thêm tối đa {$remaining}% nữa.",
            ], 422);
        }

        $slot = VongQuay::create($validated);
        $this->syncRetrySlot();

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm phần thưởng vòng quay mới.',
            'slot' => $slot->load('voucher'),
        ]);
    }

    /**
     * Update an existing prize slot.
     */
    public function adminUpdate(Request $request, $id)
    {
        $slot = VongQuay::findOrFail($id);

        $validated = $request->validate([
            'ten' => 'required|string|max:100',
            'ti_le' => 'required|numeric|min:0|max:100',
            'loai' => 'required|string|in:voucher,gift,coin,ticket,retry',
            'giatri' => 'nullable|max:255',
            'id_voucher' => 'nullable|exists:vouchers,id',
            'mau_sac' => 'nullable|string|max:20',
            'mau_chu' => 'nullable|string|max:20',
        ]);

        if ($slot->loai === 'retry') {
            // Keep type retry and do not update rate from request
            $validated['loai'] = 'retry';
            $validated['ti_le'] = $slot->ti_le;
        } else {
            if ($validated['loai'] === 'retry') {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể đổi loại quà tặng thành "Chúc may mắn lần sau".',
                ], 422);
            }

            // Validate: total percentage cannot exceed 100% (exclude current slot and retry slot)
            $currentNonRetryTotal = VongQuay::where('id', '!=', $id)
                ->where('loai', '!=', 'retry')
                ->sum('ti_le');
            if ($currentNonRetryTotal + $validated['ti_le'] > 100) {
                $remaining = 100 - $currentNonRetryTotal;
                return response()->json([
                    'success' => false,
                    'message' => "Tổng tỷ lệ các ô khác là {$currentNonRetryTotal}%. Bạn chỉ có thể đặt tối đa {$remaining}% cho ô này.",
                ], 422);
            }
        }

        $slot->update($validated);
        $this->syncRetrySlot();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cấu hình phần thưởng thành công.',
            'slot' => $slot->load('voucher'),
        ]);
    }

    /**
     * Delete a prize slot.
     */
    public function adminDestroy($id)
    {
        $slot = VongQuay::findOrFail($id);
        if ($slot->loai === 'retry') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa phần quà "Chúc may mắn lần sau".',
            ], 400);
        }
        $slot->delete();
        $this->syncRetrySlot();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa phần thưởng vòng quay.',
        ]);
    }

    /**
     * Get complete spin logs for admin view.
     */
    public function adminHistory()
    {
        $history = LichSuQuay::with('user')
            ->where('loai_qua', '!=', 'claim')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
