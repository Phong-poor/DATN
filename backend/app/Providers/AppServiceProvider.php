<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerAuditLogListeners();
    }

    /**
     * Register Eloquent event listeners to automatically collect admin audit logs
     */
    protected function registerAuditLogListeners(): void
    {
        $modelsToAudit = [
            \App\Models\Sanpham::class => 'Sản phẩm',
            \App\Models\DanhMuc::class => 'Danh mục',
            \App\Models\ThuongHieu::class => 'Thương hiệu',
            \App\Models\Banner::class => 'Banner',
            \App\Models\Promotion::class => 'Khuyến mãi',
            \App\Models\DatHang::class => 'Đơn hàng',
            \App\Models\User::class => 'Thành viên',
        ];

        foreach ($modelsToAudit as $modelClass => $friendlyName) {
            // THÊM MỚI
            $modelClass::created(function ($model) use ($friendlyName) {
                $this->logActivity('created', $friendlyName, $model);
            });

            // CẬP NHẬT
            $modelClass::updated(function ($model) use ($friendlyName) {
                $this->logActivity('updated', $friendlyName, $model);
            });

            // XÓA
            $modelClass::deleted(function ($model) use ($friendlyName) {
                $this->logActivity('deleted', $friendlyName, $model);
            });
        }
    }

    /**
     * Formulate and save the audit log record
     */
    protected function logActivity(string $action, string $friendlyName, $model): void
    {
        $user = auth()->user();
        // Chỉ lưu log nếu thao tác được thực hiện bởi Admin đang đăng nhập
        if (!$user || $user->vaitro === 'user') {
            return;
        }

        // Tìm kiếm trường định danh thân thiện (name, title, id...)
        $itemName = $model->name ?? $model->ten ?? $model->ten_sanpham ?? $model->title ?? $model->id_dathang ?? $model->id;
        $targetId = $model->getKey();

        $actionText = 'thao tác';
        $description = '';

        if ($action === 'created') {
            $actionText = 'Thêm mới';
            $description = "Đã thêm mới {$friendlyName} [{$itemName}] (ID: {$targetId})";
        } elseif ($action === 'deleted') {
            $actionText = 'Xóa';
            $description = "Đã xóa {$friendlyName} [{$itemName}] (ID: {$targetId})";
        } elseif ($action === 'updated') {
            $actionText = 'Cập nhật';
            
            // Phân tích những trường dữ liệu nào bị thay đổi
            $changes = [];
            $dirty = $model->getDirty();
            foreach ($dirty as $key => $newValue) {
                // Tránh ghi log mật khẩu vì lý do bảo mật
                if (in_array($key, ['password', 'remember_token', 'updated_at'])) {
                    continue;
                }
                $oldValue = $model->getOriginal($key);
                
                $oldStr = is_array($oldValue) || is_object($oldValue) ? json_encode($oldValue) : (string)$oldValue;
                $newStr = is_array($newValue) || is_object($newValue) ? json_encode($newValue) : (string)$newValue;
                
                if (strlen($oldStr) > 40) $oldStr = substr($oldStr, 0, 37) . '...';
                if (strlen($newStr) > 40) $newStr = substr($newStr, 0, 37) . '...';
                
                $changes[] = "[{$key}]: '{$oldStr}' ➔ '{$newStr}'";
            }

            if (!empty($changes)) {
                $description = "Đã cập nhật {$friendlyName} [{$itemName}] (ID: {$targetId}). Thay đổi: " . implode(', ', $changes);
            } else {
                $description = "Đã cập nhật {$friendlyName} [{$itemName}] (ID: {$targetId}) nhưng không có thay đổi giá trị thuộc tính";
            }
        }

        \App\Models\AdminActivityLog::create([
            'id_khachhang' => $user->id,
            'hanhdong' => $actionText,
            'tenmodel' => $friendlyName,
            'id_doituong' => $targetId,
            'mota' => $description,
            'diachi_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
