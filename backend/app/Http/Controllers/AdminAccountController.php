<?php

namespace App\Http\Controllers;

use App\Models\DatHang;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class AdminAccountController extends Controller
{
    private string $settingsPath = 'admin/settings.json';

    private function settingsDisk()
    {
        return Storage::disk('local');
    }

    private function defaultSettings(): array
    {
        return [
            'general' => [
                'brand_name' => 'Predator',
                'slogan' => 'Giải pháp công nghệ toàn diện',
                'support_email' => 'support@predator.vn',
                'support_phone' => '1800 9999',
                'business_address' => 'TP. Hồ Chí Minh',
                'working_hours' => '08:00 - 21:00',
            ],
            'appearance' => [
                'primary_color' => '#2563eb',
                'accent_color' => '#7c3aed',
                'theme_mode' => 'system',
                'font_family' => 'Inter',
                'border_radius' => 12,
                'card_shadow' => 'medium',
                'density' => 'comfortable',
                'content_width' => 'fluid',
                'sidebar_style' => 'solid',
                'animation_level' => 'normal',
            ],
            'notifications' => [
                'security_alerts' => true,
                'order_updates' => true,
                'team_activity' => true,
                'product_updates' => false,
                'marketing_promotions' => false,
                'daily_report' => false,
            ],
            'security' => [
                'force_logout_on_password_change' => true,
                'require_2fa_for_admin' => false,
                'session_timeout_minutes' => 120,
                'login_alert_email' => true,
            ],
        ];
    }

    private function mergeSettings(array $data): array
    {
        return array_replace_recursive($this->defaultSettings(), $data);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:Nam,Nữ,Khác',
            'date_of_birth' => 'nullable|date',
        ]);

        $user->fill($validated);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ thành công',
            'data' => $user,
        ]);
    }

    public function activityLog()
    {
        $orders = DatHang::with('user:id,name,email')
            ->latest('updated_at')
            ->limit(30)
            ->get()
            ->map(function ($order) {
                return [
                    'type' => 'order',
                    'title' => 'Cập nhật đơn hàng #' . $order->id_dathang,
                    'description' => 'Trạng thái: ' . $order->trangthai . ' | Tổng tiền: ' . number_format((float) $order->tongtien, 0, ',', '.') . 'đ',
                    'actor' => $order->user?->name ?? 'Hệ thống',
                    'at' => optional($order->updated_at)->toISOString(),
                ];
            });

        $users = User::latest('created_at')
            ->limit(20)
            ->get(['id', 'name', 'email', 'role', 'created_at'])
            ->map(function ($user) {
                return [
                    'type' => 'user',
                    'title' => 'Tài khoản mới: ' . $user->name,
                    'description' => 'Email: ' . $user->email . ' | Quyền: ' . $user->role,
                    'actor' => 'Hệ thống',
                    'at' => optional($user->created_at)->toISOString(),
                ];
            });

        $logs = $orders
            ->concat($users)
            ->sortByDesc('at')
            ->take(40)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function billing(Request $request)
    {
        $months = (int) $request->query('months', 6);
        $months = max(3, min(12, $months));

        $from = Carbon::now()->startOfMonth()->subMonths($months - 1);

        $monthly = DatHang::selectRaw("
                DATE_FORMAT(created_at, '%Y-%m') as ym,
                SUM(CASE WHEN trangthai = 'done' THEN tongtien ELSE 0 END) as revenue,
                SUM(CASE WHEN trangthai = 'done' THEN giam_gia ELSE 0 END) as discount,
                COUNT(*) as orders
            ")
            ->where('created_at', '>=', $from)
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $series = collect();
        for ($i = 0; $i < $months; $i++) {
            $m = $from->copy()->addMonths($i);
            $ym = $m->format('Y-m');
            $row = $monthly->get($ym);
            $series->push([
                'label' => $m->format('m/Y'),
                'revenue' => (float) ($row->revenue ?? 0),
                'discount' => (float) ($row->discount ?? 0),
                'orders' => (int) ($row->orders ?? 0),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'months' => $months,
                'totals' => [
                    'revenue' => (float) $series->sum('revenue'),
                    'discount' => (float) $series->sum('discount'),
                    'orders' => (int) $series->sum('orders'),
                ],
                'series' => $series,
            ],
        ]);
    }

    public function settings()
    {
        $disk = $this->settingsDisk();
        if (!$disk->exists($this->settingsPath)) {
            $legacyDisk = Storage::disk('public');
            $legacyData = $legacyDisk->exists($this->settingsPath)
                ? (json_decode($legacyDisk->get($this->settingsPath), true) ?: [])
                : [];

            $disk->put($this->settingsPath, json_encode($this->mergeSettings($legacyData), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $raw = $disk->get($this->settingsPath);
        $data = $this->mergeSettings(json_decode($raw, true) ?: []);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'general.brand_name' => 'required|string|max:255',
            'general.slogan' => 'nullable|string|max:255',
            'general.support_email' => 'required|email',
            'general.support_phone' => 'nullable|string|max:20',
            'general.business_address' => 'nullable|string|max:255',
            'general.working_hours' => 'nullable|string|max:80',

            'appearance.primary_color' => 'required|string|max:20',
            'appearance.accent_color' => 'required|string|max:20',
            'appearance.theme_mode' => 'required|in:light,dark,system',
            'appearance.font_family' => 'required|string|max:60',
            'appearance.border_radius' => 'required|integer|min:6|max:24',
            'appearance.card_shadow' => 'required|in:none,soft,medium,strong',
            'appearance.density' => 'required|in:compact,comfortable,spacious',
            'appearance.content_width' => 'required|in:boxed,fluid',
            'appearance.sidebar_style' => 'required|in:solid,glass,gradient',
            'appearance.animation_level' => 'required|in:off,normal,rich',

            'notifications.security_alerts' => 'required|boolean',
            'notifications.order_updates' => 'required|boolean',
            'notifications.team_activity' => 'required|boolean',
            'notifications.product_updates' => 'required|boolean',
            'notifications.marketing_promotions' => 'required|boolean',
            'notifications.daily_report' => 'required|boolean',

            'security.force_logout_on_password_change' => 'required|boolean',
            'security.require_2fa_for_admin' => 'required|boolean',
            'security.session_timeout_minutes' => 'required|integer|min:15|max:1440',
            'security.login_alert_email' => 'required|boolean',
        ]);

        $validated = $this->mergeSettings($validated);
        $this->settingsDisk()->put($this->settingsPath, json_encode($validated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json([
            'success' => true,
            'message' => 'Lưu cấu hình thành công',
            'data' => $validated,
        ]);
    }

    // API: Xem các Admin đang hoạt động
    public function activeAdmins()
    {
        $admins = User::where('role', 'admin')
            ->orderBy('last_active_at', 'desc')
            ->get()
            ->map(function ($admin) {
                // Xác định trạng thái online dựa trên thời gian hoạt động cuối (5 phút)
                $isOnline = false;
                if ($admin->last_active_at) {
                    $isOnline = \Illuminate\Support\Carbon::parse($admin->last_active_at)->diffInMinutes(now()) < 5;
                }
                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'avatar' => $admin->avatar,
                    'last_active_at' => $admin->last_active_at ? \Illuminate\Support\Carbon::parse($admin->last_active_at)->toIsoString() : null,
                    'is_online' => $isOnline,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $admins
        ]);
    }

    // API: Lấy nhật ký hệ thống nâng cao (Audit Logs) có phân trang và tìm kiếm
    public function systemActivityLogs(Request $request)
    {
        $query = \App\Models\AdminActivityLog::with('user:id,name,email,avatar')
            ->latest();

        // Lọc theo từ khóa (tên admin, mô tả hành động, địa chỉ IP)
        if ($request->filled('keyword')) {
            $k = $request->keyword;
            $query->where(function($q) use ($k) {
                $q->where('description', 'like', "%{$k}%")
                  ->orWhere('action', 'like', "%{$k}%")
                  ->orWhere('model_name', 'like', "%{$k}%")
                  ->orWhere('ip_address', 'like', "%{$k}%")
                  ->orWhereHas('user', function($sub) use ($k) {
                      $sub->where('name', 'like', "%{$k}%")
                          ->orWhere('email', 'like', "%{$k}%");
                  });
            });
        }

        // Lọc theo thao tác (Thêm mới, Cập nhật, Xóa)
        if ($request->filled('action_filter')) {
            $query->where('action', $request->action_filter);
        }

        // Lọc theo phân hệ (Sản phẩm, Đơn hàng...)
        if ($request->filled('model_filter')) {
            $query->where('model_name', $request->model_filter);
        }

        $logs = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}
