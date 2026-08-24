<?php

namespace App\Http\Controllers;

use App\Models\DanhMuc;
use App\Models\News;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FooterController extends Controller
{
    public function index()
    {
        $data = Cache::remember('public.footer', now()->addMinutes(2), function () {
            $defaults = [
                'brand_name' => 'NextGen',
                'slogan' => 'Giải pháp công nghệ toàn diện cho học tập, làm việc và giải trí.',
                'support_email' => 'support@nextgen.vn',
                'support_phone' => '1800 9999',
                'business_address' => 'TP. Hồ Chí Minh',
                'working_hours' => '08:00 - 21:00',
            ];

            $stored = [];
            if (Storage::disk('local')->exists('admin/settings.json')) {
                $decoded = json_decode(Storage::disk('local')->get('admin/settings.json'), true);
                $stored = is_array($decoded['general'] ?? null) ? $decoded['general'] : [];
            }

            $general = array_merge($defaults, array_intersect_key($stored, $defaults));

            return [
                'store' => $general,
                'online_users' => User::query()
                    ->where('hoat_dong_cuoi_luc', '>=', now()->subMinutes(5))
                    ->count(),
                'categories' => DanhMuc::query()
                    ->select(['id_danhmuc', 'ten_danhmuc'])
                    ->where(function ($query) {
                        $query->whereNull('trangthai')
                            ->orWhereIn('trangthai', [1, '1', 'active', 'hoat_dong']);
                    })
                    ->orderBy('id_danhmuc')
                    ->limit(5)
                    ->get(),
                'news' => News::query()
                    ->select(['id', 'slug', 'tieude'])
                    ->where('trangthai', 'published')
                    ->where(fn ($query) => $query->whereNull('dang_luc')->orWhere('dang_luc', '<=', now()))
                    ->latest('id')
                    ->limit(5)
                    ->get(),
                'server_time' => now()->toISOString(),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }
}
