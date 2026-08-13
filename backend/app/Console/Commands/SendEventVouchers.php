<?php

namespace App\Console\Commands;

use App\Mail\EventVoucherMail;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserVoucher;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEventVouchers extends Command
{
    protected $signature = 'events:send-vouchers
        {code? : Mã sự kiện chữ in hoa, ví dụ QUOCKHANH; mặc định tìm sự kiện hôm nay}
        {--email=* : Chỉ gửi đến các địa chỉ email chỉ định}
        {--force : Gửi lại dù khách đã nhận sự kiện này trong năm nay}
        {--dry-run : Chỉ kiểm tra, không cấp voucher và không gửi email}';

    protected $description = 'Cấp voucher sự kiện vào tài khoản và gửi thông báo qua Gmail cho khách hàng';

    public function handle(): int
    {
        $code = strtoupper(trim((string) $this->argument('code')));
        $query = Promotion::query()
            ->where('danhmuc', 'event')
            ->where('tu_dong_gui', true)
            ->whereIn('trangthai', ['running', 'open']);
        $promotion = $code !== '' ? $query->where('code', $code)->first() : $query->where('ngay_su_kien', now()->format('d-m'))->first();

        if (! $promotion) {
            if ($code === '') {
                $this->info('Hôm nay không có sự kiện tự động cần gửi.');
                return self::SUCCESS;
            }
            $this->error("Không tìm thấy chiến dịch đang hoạt động có mã {$code}.");
            return self::FAILURE;
        }

        $emails = collect($this->option('email'))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        $query = User::query()
            ->whereNotNull('email')
            ->where(function (Builder $builder) {
                $builder->whereNull('trangthai')->orWhere('trangthai', '!=', 'locked');
            });

        if ($emails->isNotEmpty()) {
            $query->whereIn('email', $emails);
        } else {
            $query->where(function (Builder $builder) {
                $builder->whereNull('vaitro')->orWhere('vaitro', 'user');
            });
        }

        $recipientCount = (clone $query)->count();
        if ($this->option('dry-run')) {
            $this->info("Sự kiện: {$promotion->ten} ({$promotion->code})");
            $this->info("Số người sẽ nhận: {$recipientCount}");
            return self::SUCCESS;
        }

        if ($recipientCount === 0) {
            $this->warn('Không tìm thấy người nhận phù hợp.');
            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;
        [$eventDay, $eventMonth] = array_map('intval', explode('-', $promotion->ngay_su_kien));
        $expiresAt = Carbon::create(now()->year, $eventMonth, $eventDay)->endOfDay();
        if ($expiresAt->isPast()) {
            $expiresAt->addYear();
        }

        $query->orderBy('id')->chunkById(100, function ($users) use (
            $promotion,
            $expiresAt,
            &$sent,
            &$skipped,
            &$failed,
        ) {
            foreach ($users as $user) {
                $existing = UserVoucher::query()
                    ->where('id_user', $user->id)
                    ->where('id_voucher', $promotion->id)
                    ->first();

                $receivedThisYear = $existing?->ngay_nhan?->isSameYear(now()) ?? false;
                if ($receivedThisYear && ! $this->option('force')) {
                    $skipped++;
                    continue;
                }

                try {
                    DB::transaction(function () use ($existing, $user, $promotion, $expiresAt) {
                        $userVoucher = $existing ?: new UserVoucher([
                            'id_user' => $user->id,
                            'id_voucher' => $promotion->id,
                        ]);
                        $userVoucher->fill([
                            'trang_thai' => 0,
                            'ngay_nhan' => now(),
                            'het_han_luc' => $expiresAt,
                            'da_su_dung_luc' => null,
                        ])->save();

                        Mail::to($user->email)->send(new EventVoucherMail($user, $promotion, $expiresAt));
                    });
                    $sent++;
                } catch (Throwable $exception) {
                    report($exception);
                    $failed++;
                    $this->warn("Gửi thất bại đến {$user->email}: {$exception->getMessage()}");
                }
            }
        });

        $this->info("Hoàn tất {$promotion->ten}: gửi {$sent}, bỏ qua {$skipped}, lỗi {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
