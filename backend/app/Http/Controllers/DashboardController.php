<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use Illuminate\Http\Request;

use App\Models\DatHang;
use App\Models\User;
use App\Models\Admin;
use App\Models\BienThe;
use App\Models\DatHangChiTiet;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tổng hợp số liệu doanh thu, đơn hàng, sản phẩm và người dùng cho dashboard.
 */
class DashboardController extends Controller
{
    public function dailyRevenue(Request $request)
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = isset($validated['month'])
            ? \Carbon\Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : now()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $rows = DatHang::query()
            ->where('trangthai', 'done')
            ->whereBetween('updated_at', [$month, $end])
            ->selectRaw('DATE(updated_at) as revenue_date, COUNT(*) as orders, SUM(tongtien) as revenue')
            ->groupByRaw('DATE(updated_at)')
            ->get()
            ->keyBy('revenue_date');

        $days = collect(range(1, $month->daysInMonth))->map(function ($day) use ($month, $rows) {
            $date = $month->copy()->day($day)->toDateString();
            $row = $rows->get($date);
            $revenue = (float) ($row->revenue ?? 0);
            return [
                'date' => $date,
                'day' => $day,
                'orders' => (int) ($row->orders ?? 0),
                'revenue' => $revenue,
                'revenue_formatted' => number_format($revenue, 0, ',', '.') . 'đ',
            ];
        });

        return response()->json(['data' => [
            'month' => $month->format('Y-m'),
            'label' => 'Tháng '.$month->format('m/Y'),
            'days' => $days,
            'total_orders' => (int) $days->sum('orders'),
            'total_revenue' => (float) $days->sum('revenue'),
            'total_revenue_formatted' => number_format((float) $days->sum('revenue'), 0, ',', '.') . 'đ',
        ]]);
    }

    public function index(Request $request)
    {
        try {
            $period = $request->query('period', 'all');

            $data = Cache::remember("dashboard_data_v9_{$period}", 120, function () use ($period) {
                // ================= TIME =================
                $now = now();
                $dateFrom = match ($period) {
                'month' => $now->copy()->startOfMonth(),
                'year'  => $now->copy()->startOfYear(),
                'all'   => $now->copy()->subYears(50),
                default => $now->copy()->startOfWeek(),
            };

            [$trendCurrentFrom, $trendPreviousFrom, $trendPreviousTo] = match ($period) {
                'month' => [
                    $now->copy()->startOfMonth(),
                    $now->copy()->subMonth()->startOfMonth(),
                    $now->copy()->subMonth()->endOfMonth(),
                ],
                'year' => [
                    $now->copy()->startOfYear(),
                    $now->copy()->subYear()->startOfYear(),
                    $now->copy()->subYear()->endOfYear(),
                ],
                'all' => [
                    $now->copy()->subDays(30),
                    $now->copy()->subDays(60),
                    $now->copy()->subDays(30),
                ],
                default => [
                    $now->copy()->startOfWeek(),
                    $now->copy()->subWeek()->startOfWeek(),
                    $now->copy()->subWeek()->endOfWeek(),
                ],
            };

            $calcTrend = function ($current, $previous) {
                $current = (float) $current;
                $previous = (float) $previous;
                if ($previous <= 0) {
                    return $current > 0 ? 100 : 0;
                }
                return round((($current - $previous) / $previous) * 100, 1);
            };

            // ================= DOANH THU =================
            $tongDoanhThuRaw = DatHang::where('trangthai', 'done')
                ->whereBetween('created_at', [$dateFrom, $now])
                ->sum('tongtien');
            $tongDoanhThu = number_format($tongDoanhThuRaw, 0, ',', '.') . 'đ';

            // Doanh thu phát sinh trong ngày: tính theo thời điểm đơn được hoàn tất/cập nhật.
            $doanhThuHomNayRaw = DatHang::where('trangthai', 'done')
                ->whereDate('updated_at', $now->toDateString())
                ->sum('tongtien');
            $doanhThuHomNay = number_format($doanhThuHomNayRaw, 0, ',', '.') . 'đ';

            $donHoanThanh = DatHang::where('trangthai', 'done')
                ->whereBetween('created_at', [$dateFrom, $now])
                ->count();
            $giaTriDonTrungBinhRaw = $donHoanThanh > 0 ? $tongDoanhThuRaw / $donHoanThanh : 0;
            $giaTriDonTrungBinh = number_format($giaTriDonTrungBinhRaw, 0, ',', '.') . 'đ';

            // ================= KHÁCH =================
            $tongKhachHang = User::where('vaitro', 'user')->count();

            // ================= BIẾN THỂ =================
            $tongBienThe = BienThe::count();

            // ================= TRẠNG THÁI =================
            $trangThaiRaw = DatHang::selectRaw('trangthai, COUNT(*) as total')
                ->whereBetween('created_at', [$dateFrom, $now])
                ->groupBy('trangthai')
                ->pluck('total', 'trangthai');

            $tongDonHang = $trangThaiRaw->sum();

            $statusLabels = [
                'pending'   => 'Chờ xác nhận',
                'confirmed' => 'Đã xác nhận',
                'shipping'  => 'Đang giao',
                'done'      => 'Hoàn thành',
                'cancelled' => 'Hủy đơn',
                'refund_pending' => 'Yêu cầu hoàn trả',
                'refund_pickup' => 'Chờ lấy hàng hoàn',
                'refund_delivering' => 'Đang giao hoàn',
                'refund_received' => 'Đã nhận hoàn',
                'refunded'  => 'Đã hoàn tiền',
            ];

            $trangThai = collect($statusLabels)->map(function ($label, $status) use ($trangThaiRaw, $tongDonHang) {
                $count = $trangThaiRaw[$status] ?? 0;

                return [
                    'status' => $status,
                    'label'  => $label,
                    'count'  => $count,
                    'pct'    => $tongDonHang > 0 ? round(($count / $tongDonHang) * 100) : 0,
                ];
            })->values();

            // ================= BIỂU ĐỒ THEO ĐÚNG KỲ SO SÁNH =================
            // "Tất cả" dùng 30 ngày gần nhất giống phần phân tích; năm nhóm theo tháng.
            $chartByMonth = $period === 'year';
            $chartGroupSql = $chartByMonth
                ? "DATE_FORMAT(created_at, '%Y-%m')"
                : 'DATE(created_at)';

            $chartPeriods = collect(CarbonPeriod::create(
                $trendCurrentFrom->copy()->startOfDay(),
                $chartByMonth ? '1 month' : '1 day',
                $now->copy()->endOfDay()
            ))->map(fn ($date) => $chartByMonth ? $date->format('Y-m') : $date->format('Y-m-d'));

            $bieuDoRaw = DatHang::selectRaw("
                    {$chartGroupSql} as label,
                    SUM(CASE WHEN trangthai = 'done' THEN tongtien ELSE 0 END) as total,
                    COUNT(*) as orders
                ")
                ->whereBetween('created_at', [$trendCurrentFrom, $now])
                ->groupBy('label')
                ->orderBy('label')
                ->get()
                ->keyBy('label');

            $bieuDo = $chartPeriods->map(function ($label) use ($bieuDoRaw) {
                $row = $bieuDoRaw->get($label);
                return [
                    'label' => $label,
                    'total' => (float) ($row->total ?? 0),
                    'orders' => (int) ($row->orders ?? 0),
                ];
            })->values();

            $bieuDoKhachHangRaw = User::where('vaitro', 'user')
                ->selectRaw("{$chartGroupSql} as label, COUNT(*) as total")
                ->whereBetween('created_at', [$trendCurrentFrom, $now])
                ->groupBy('label')
                ->orderBy('label')
                ->get()
                ->keyBy('label');

            $bieuDoKhachHang = $chartPeriods->map(function ($label) use ($bieuDoKhachHangRaw) {
                return [
                    'label' => $label,
                    'total' => (int) ($bieuDoKhachHangRaw->get($label)->total ?? 0),
                ];
            })->values();

            $bieuDoSanPhamRaw = DatHangChiTiet::selectRaw("
                    {$chartGroupSql} as label,
                    SUM(soluong) as total
                ")
                ->whereHas('datHang', function ($q) use ($trendCurrentFrom, $now) {
                    $q->where('trangthai', 'done')
                      ->whereBetween('created_at', [$trendCurrentFrom, $now]);
                })
                ->whereBetween('created_at', [$trendCurrentFrom, $now])
                ->groupBy('label')
                ->orderBy('label')
                ->get()
                ->keyBy('label');

            $bieuDoSanPham = $chartPeriods->map(function ($label) use ($bieuDoSanPhamRaw) {
                return [
                    'label' => $label,
                    'total' => (int) ($bieuDoSanPhamRaw->get($label)->total ?? 0),
                ];
            })->values();

            // ================= ĐƠN HÀNG =================
            $donHangQuery = DatHang::with('user');
            
            if ($period !== 'all') {
                $donHangQuery->whereBetween('created_at', [$dateFrom, now()]);
            }
            
            $donHang = $donHangQuery->latest()
                ->limit(5)
                ->get()
                ->map(function ($o) {
                    $statusLabels = [
                        'pending'   => 'Chờ xác nhận',
                        'confirmed' => 'Đã xác nhận',
                        'shipping'  => 'Đang giao',
                        'done'      => 'Hoàn thành',
                        'cancelled' => 'Hủy đơn',
                        'refund_pending' => 'Yêu cầu hoàn trả',
                        'refund_pickup' => 'Chờ lấy hàng hoàn',
                        'refund_delivering' => 'Đang giao hoàn',
                        'refund_received' => 'Đã nhận hoàn',
                        'refunded'  => 'Đã hoàn tiền',
                    ];
                    return [
                        'id'       => '#DH-' . str_pad($o->id_dathang, 4, '0', STR_PAD_LEFT),
                        'khach'    => $o->user->ten ?? 'N/A',
                        'tong'     => number_format($o->tongtien, 0, ',', '.') . 'đ',
                        'status'   => $o->trangthai,
                        'trangthai'=> $statusLabels[$o->trangthai] ?? $o->trangthai,
                    ];
                });

            // ================= SẢN PHẨM BÁN CHẠY =================
            $sanPham = DatHangChiTiet::with(['bienThe.sanPham'])
                ->selectRaw('id_bienthe, SUM(soluong) as tong_ban')
                ->whereHas('datHang', function ($q) use ($dateFrom) {
                    $q->where('trangthai', 'done')
                      ->whereBetween('created_at', [$dateFrom, now()]);
                })
                ->groupBy('id_bienthe')
                ->orderByDesc('tong_ban')
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    $sp = $item->bienThe?->sanPham;
                    $tenSPSanPham = $sp?->tenSP ?? 'Sản phẩm';
                    $tenBienThe = $item->bienThe?->ten_bienthe;
                    $tenHienThi = $tenBienThe ? $tenSPSanPham . ' - ' . $tenBienThe : $tenSPSanPham;

                    $imgUrl = $sp?->hinhanh;
                    if ($imgUrl && !str_starts_with($imgUrl, 'http')) {
                        $imgUrl = url('storage/' . ltrim($imgUrl, '/'));
                    }

                    return [
                        'id'       => $item->id_bienthe,
                        'ten'      => $tenHienThi,
                        'img'      => $imgUrl,
                        'gia'      => $item->bienThe?->gia
                                        ? number_format($item->bienThe->gia, 0, ',', '.') . 'đ'
                                        : '—',
                        'tong_ban' => number_format($item->tong_ban, 0, ',', '.') . ' đơn vị',
                    ];
                });

            // ================= CẢNH BÁO TỒN KHO =================
            $tonKhoCanhBao = BienThe::with('sanPham')
                ->where('soluong', '<=', 5)
                ->orderBy('soluong')
                ->limit(5)
                ->get()
                ->map(function ($variant) {
                    return [
                        'id' => $variant->id_bienthe,
                        'id_bienthe' => $variant->id_bienthe,
                        'id_sanpham' => $variant->id_sanpham,
                        'ten' => trim(($variant->sanPham?->tenSP ?? 'Sản phẩm') . ' - ' . ($variant->ten_bienthe ?? 'Mặc định'), ' -'),
                        'soluong' => (int) $variant->soluong,
                        'gia' => number_format((float) $variant->gia, 0, ',', '.') . 'đ',
                    ];
                });

            // ================= ĐƠN CẦN THEO DÕI =================
            $donCanXuLy = DatHang::with('user')
                ->whereIn('trangthai', ['pending', 'confirmed', 'shipping', 'refund_pending'])
                ->where('created_at', '<=', now()->subHours(12))
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($order) use ($statusLabels) {
                    return [
                        'id' => '#DH-' . str_pad($order->id_dathang, 4, '0', STR_PAD_LEFT),
                        'khach' => $order->user->ten ?? 'N/A',
                        'tong' => number_format((float) $order->tongtien, 0, ',', '.') . 'đ',
                        'status' => $order->trangthai,
                        'trangthai' => $statusLabels[$order->trangthai] ?? $order->trangthai,
                        'tuoi_don' => $order->created_at?->diffForHumans() ?? '',
                    ];
                });

            // ================= PHƯƠNG THỨC THANH TOÁN =================
            $paymentLabels = [
                'cod' => 'COD',
                'cash' => 'COD',
                'vnpay' => 'VNPay',
                'momo' => 'Momo',
                'bank' => 'Chuyển khoản',
                'online' => 'Thanh toán online',
            ];
            $thanhToan = DatHang::query()
                ->select(['nha_cung_cap_thanh_toan', 'kieu_thanh_toan', 'PTTT'])
                ->whereBetween('created_at', [$dateFrom, now()])
                ->get()
                ->groupBy(function ($order) {
                    return strtolower(
                        trim($order->nha_cung_cap_thanh_toan ?: $order->kieu_thanh_toan ?: $order->PTTT ?: 'cod')
                    );
                })
                ->map(function ($item) use ($paymentLabels) {
                    $method = (string) $item->first()?->nha_cung_cap_thanh_toan
                        ?: (string) $item->first()?->kieu_thanh_toan
                        ?: (string) $item->first()?->PTTT
                        ?: 'cod';
                    $method = strtolower(trim($method));
                    return [
                        'label' => $paymentLabels[$method] ?? strtoupper($method),
                        'total' => $item->count(),
                    ];
                })
                ->sortByDesc('total')
                ->values();

            // ================= DANH MỤC BÁN CHẠY =================
            $danhMucBanChay = DatHangChiTiet::with('bienThe.sanPham.danhMuc')
                ->selectRaw('id_bienthe, SUM(soluong) as tong_ban, SUM(soluong * gia) as doanh_thu')
                ->whereHas('datHang', function ($q) use ($dateFrom) {
                    $q->where('trangthai', 'done')
                      ->whereBetween('created_at', [$dateFrom, now()]);
                })
                ->groupBy('id_bienthe')
                ->get()
                ->groupBy(fn ($item) => $item->bienThe?->sanPham?->danhMuc?->ten_danhmuc ?? 'Chưa phân loại')
                ->map(function ($items, $name) {
                    return [
                        'label' => $name,
                        'total' => (int) $items->sum('tong_ban'),
                        'revenue' => number_format((float) $items->sum('doanh_thu'), 0, ',', '.') . 'đ',
                    ];
                })
                ->sortByDesc('total')
                ->take(5)
                ->values();

            // ================= PHÂN TÍCH TĂNG TRƯỞNG =================
            $doanhThuKyNay = DatHang::where('trangthai', 'done')
                ->whereBetween('created_at', [$trendCurrentFrom, $now])
                ->sum('tongtien');
            $doanhThuKyTruoc = DatHang::where('trangthai', 'done')
                ->whereBetween('created_at', [$trendPreviousFrom, $trendPreviousTo])
                ->sum('tongtien');

            $donKyNay = DatHang::whereBetween('created_at', [$trendCurrentFrom, $now])->count();
            $donKyTruoc = DatHang::whereBetween('created_at', [$trendPreviousFrom, $trendPreviousTo])->count();

            $khachKyNay = User::where('vaitro', 'user')
                ->whereBetween('created_at', [$trendCurrentFrom, $now])
                ->count();
            $khachKyTruoc = User::where('vaitro', 'user')
                ->whereBetween('created_at', [$trendPreviousFrom, $trendPreviousTo])
                ->count();

            $sanPhamKyNay = DatHangChiTiet::whereHas('datHang', function ($q) use ($trendCurrentFrom, $now) {
                    $q->where('trangthai', 'done')
                      ->whereBetween('created_at', [$trendCurrentFrom, $now]);
                })
                ->sum('soluong');
            $sanPhamKyTruoc = DatHangChiTiet::whereHas('datHang', function ($q) use ($trendPreviousFrom, $trendPreviousTo) {
                    $q->where('trangthai', 'done')
                      ->whereBetween('created_at', [$trendPreviousFrom, $trendPreviousTo]);
                })
                ->sum('soluong');

            $phanTich = [
                'gia_tri_don_trung_binh' => $giaTriDonTrungBinh,
                'don_hoan_thanh' => $donHoanThanh,
                'tong_don' => $tongDonHang,
                'ti_le_hoan_thanh' => $tongDonHang > 0 ? round(($donHoanThanh / $tongDonHang) * 100, 1) : 0,
                'doanh_thu' => [
                    'current' => number_format((float) $doanhThuKyNay, 0, ',', '.') . 'đ',
                    'previous' => number_format((float) $doanhThuKyTruoc, 0, ',', '.') . 'đ',
                    'trend' => $calcTrend($doanhThuKyNay, $doanhThuKyTruoc),
                ],
                'don_hang' => [
                    'current' => $donKyNay,
                    'previous' => $donKyTruoc,
                    'trend' => $calcTrend($donKyNay, $donKyTruoc),
                ],
                'khach_hang' => [
                    'current' => $khachKyNay,
                    'previous' => $khachKyTruoc,
                    'trend' => $calcTrend($khachKyNay, $khachKyTruoc),
                ],
                'san_pham' => [
                    'current' => (int) $sanPhamKyNay,
                    'previous' => (int) $sanPhamKyTruoc,
                    'trend' => $calcTrend($sanPhamKyNay, $sanPhamKyTruoc),
                ],
            ];

            // ================= TRẠNG THÁI NHÂN SỰ =================
            $onlineSince = $now->copy()->subMinutes(5);
            $idleSince = $now->copy()->subMinutes(15);
            $staffRows = Admin::orderByDesc('hoat_dong_cuoi_luc')
                ->limit(8)
                ->get()
                ->map(function ($staff) use ($onlineSince, $idleSince) {
                    $lastActiveAt = $staff->hoat_dong_cuoi_luc;
                    $isOnline = $lastActiveAt && $lastActiveAt->greaterThanOrEqualTo($onlineSince);
                    $isIdle = !$isOnline && $lastActiveAt && $lastActiveAt->greaterThanOrEqualTo($idleSince);

                    $avatar = $staff->anhdaidien;
                    if ($avatar && !str_starts_with($avatar, 'http')) {
                        $avatar = url('storage/' . ltrim($avatar, '/'));
                    }

                    return [
                        'id' => $staff->id,
                        'ten' => $staff->ten ?? $staff->email,
                        'email' => $staff->email,
                        'vaitro' => $staff->ten_vaitro_hienthi,
                        'avatar' => $avatar,
                        'last_active_at' => $lastActiveAt?->toIso8601String(),
                        'last_active_text' => $lastActiveAt?->diffForHumans() ?? 'Chưa ghi nhận',
                        'status' => $isOnline ? 'online' : ($isIdle ? 'idle' : 'offline'),
                        'status_label' => $isOnline ? 'Đang online' : ($isIdle ? 'Vắng mặt' : 'Offline'),
                    ];
                });

            $nhanSuHoatDong = [
                'online' => $staffRows->where('status', 'online')->count(),
                'idle' => $staffRows->where('status', 'idle')->count(),
                'offline' => $staffRows->where('status', 'offline')->count(),
                'total' => $staffRows->count(),
                'items' => $staffRows->values(),
            ];

            $khachHangHoatDong = [
                'online' => User::where('vaitro', 'user')
                    ->where('hoat_dong_cuoi_luc', '>=', $onlineSince)
                    ->count(),
                'recent' => User::where('vaitro', 'user')
                    ->where('hoat_dong_cuoi_luc', '>=', $idleSince)
                    ->count(),
                'visited_today' => User::where('vaitro', 'user')
                    ->whereDate('hoat_dong_cuoi_luc', $now->toDateString())
                    ->count(),
            ];

            // ================= CHÂN DUNG ĐỘ TUỔI KHÁCH MUA =================
            // Chỉ tính đơn hoàn tất; một khách có nhiều đơn vẫn được phản ánh đúng theo sức mua.
            $ageRanges = [
                ['key' => 'under_18', 'label' => 'Dưới 18', 'min' => 0, 'max' => 17],
                ['key' => '18_24', 'label' => '18–24', 'min' => 18, 'max' => 24],
                ['key' => '25_34', 'label' => '25–34', 'min' => 25, 'max' => 34],
                ['key' => '35_44', 'label' => '35–44', 'min' => 35, 'max' => 44],
                ['key' => '45_plus', 'label' => 'Từ 45', 'min' => 45, 'max' => 130],
            ];

            $agePurchaseRows = DatHang::query()
                ->join('khachhang', 'khachhang.id', '=', 'dathang.id_khachhang')
                ->where('dathang.trangthai', 'done')
                ->whereBetween('dathang.created_at', [$dateFrom, $now])
                ->whereNotNull('khachhang.ngaysinh')
                ->selectRaw('dathang.id_khachhang, khachhang.ngaysinh, COUNT(*) as orders, SUM(dathang.tongtien) as revenue')
                ->groupBy('dathang.id_khachhang', 'khachhang.ngaysinh')
                ->get();

            $ageGroups = collect($ageRanges)->map(function ($range) use ($agePurchaseRows, $now) {
                $customers = $agePurchaseRows->filter(function ($row) use ($range, $now) {
                    try {
                        $age = \Carbon\Carbon::parse($row->ngaysinh)->age;
                        return $age >= $range['min'] && $age <= $range['max'] && $age <= $now->age + 130;
                    } catch (\Throwable) {
                        return false;
                    }
                });

                return [
                    'key' => $range['key'],
                    'label' => $range['label'],
                    'customers' => $customers->count(),
                    'orders' => (int) $customers->sum('orders'),
                    'revenue' => (float) $customers->sum('revenue'),
                ];
            });

            $ageTotalOrders = (int) $ageGroups->sum('orders');
            $ageGroups = $ageGroups->map(function ($group) use ($ageTotalOrders) {
                $group['pct'] = $ageTotalOrders > 0 ? round(($group['orders'] / $ageTotalOrders) * 100, 1) : 0;
                $group['revenue_formatted'] = number_format($group['revenue'], 0, ',', '.') . 'đ';
                return $group;
            })->values();
            $ageTotalRevenue = (float) $ageGroups->sum('revenue');
            $ageGroups = $ageGroups->map(function ($group) use ($ageTotalRevenue) {
                $group['revenue_pct'] = $ageTotalRevenue > 0 ? round(($group['revenue'] / $ageTotalRevenue) * 100, 1) : 0;
                return $group;
            });
            $topAgeGroup = $ageGroups->sortByDesc('orders')->first();

            $phanTichDoTuoi = [
                'groups' => $ageGroups,
                'top_group' => ($topAgeGroup && $topAgeGroup['orders'] > 0) ? $topAgeGroup : null,
                'known_customers' => $agePurchaseRows->count(),
                'total_orders' => $ageTotalOrders,
                'total_revenue' => $ageTotalRevenue,
            ];

            // ================= TÀI KHOẢN CÓ NGUY CƠ BOM HÀNG =================
            // Chỉ coi là "bom" khi đã có bằng chứng ở khâu giao nhận, không đồng nhất với hủy sớm.
            $riskOrders = DatHang::with('user')->get();

            $bomHangRows = $riskOrders->groupBy(function ($order) {
                $phone = preg_replace('/\D+/', '', (string) $order->user?->sodienthoai);
                return $phone !== '' ? 'phone:'.$phone : 'account:'.$order->id_khachhang;
            })->map(function ($orders) {
                $customer = $orders->first()?->user;
                if (! $customer || $customer->vaitro !== 'user') return null;

                $confirmedBombs = 0;
                $contactFailures = 0;
                $customerCancellations = 0;
                $deliveryOrders = 0;
                $evidence = [];

                foreach ($orders as $order) {
                    $paymentData = $order->du_lieu_thanh_toan ?? [];
                    $shipment = $paymentData['shipping_demo'] ?? [];
                    $returnReason = mb_strtolower((string) ($shipment['return_reason'] ?? ''), 'UTF-8');
                    $failureReason = mb_strtolower((string) ($shipment['last_failure_reason'] ?? $shipment['failure_reason'] ?? ''), 'UTF-8');
                    $attempts = (int) ($shipment['delivery_attempts'] ?? 0);
                    $hasShipment = ! empty($shipment['tracking_code']);
                    if ($hasShipment) $deliveryOrders++;

                    $refused = str_contains($returnReason, 'từ chối') || str_contains($failureReason, 'từ chối');
                    $failedThreeTimes = $attempts >= 3 && ! empty($shipment['returned_at']);
                    if ($refused || $failedThreeTimes) {
                        $confirmedBombs++;
                        $evidence[] = [
                            'order_id' => (int) $order->id_dathang,
                            'reason' => $refused ? 'Khách từ chối nhận hàng' : 'Không giao được sau 3 lần',
                        ];
                    } elseif ($attempts > 0) {
                        $contactFailures += $attempts;
                    }

                    $cancelSource = $paymentData['cancellation']['source'] ?? null;
                    $reason = mb_strtolower((string) $order->lydo, 'UTF-8');
                    $looksLikeSystemFailure = str_contains($reason, 'thanh toán') || str_contains($reason, 'hết hạn');
                    if ($order->trangthai === 'cancelled' && ! $looksLikeSystemFailure && $cancelSource !== 'admin') {
                        $customerCancellations++;
                    }
                }

                if ($confirmedBombs === 0 && $contactFailures < 2) return null;

                $bombRate = $deliveryOrders > 0 ? round(($confirmedBombs / $deliveryOrders) * 100, 1) : 0;
                $score = min(100, ($confirmedBombs * 45) + (min($contactFailures, 4) * 10) + (min($customerCancellations, 3) * 3));
                $risk = $confirmedBombs >= 2 || $score >= 70 ? 'high' : ($score >= 40 ? 'medium' : 'low');
                $policyLocked = $confirmedBombs >= 3 && $bombRate >= 50;

                return [
                    'id' => (int) $customer->id,
                    'name' => $customer->ten ?: 'Khách hàng #' . $customer->id,
                    'email' => $customer->email,
                    'phone' => $customer->sodienthoai,
                    'account_status' => $customer->trangthai,
                    'policy_locked' => $policyLocked || $customer->trangthai === 'locked',
                    'total_orders' => $orders->count(),
                    'delivery_orders' => $deliveryOrders,
                    'confirmed_bombs' => $confirmedBombs,
                    'contact_failures' => $contactFailures,
                    'customer_cancellations' => $customerCancellations,
                    'bomb_rate' => $bombRate,
                    'risk_score' => $score,
                    'risk' => $risk,
                    'evidence' => $evidence,
                ];
            })->filter()->sortByDesc('risk_score')->values()->take(6);

            $phanTichBomHang = [
                'items' => $bomHangRows,
                'flagged_accounts' => $bomHangRows->count(),
                'confirmed_bombs' => (int) $bomHangRows->sum('confirmed_bombs'),
                'definition' => 'Đối chiếu theo tài khoản và số điện thoại. Tự khóa khi có từ 3 vụ bom xác nhận và tỷ lệ bom từ 50%.',
            ];

                return [
                    'period'              => $period,
                    'doanh_thu'           => $tongDoanhThu,
                    'doanh_thu_hom_nay'   => $doanhThuHomNay,
                    'khach_hang'          => $tongKhachHang,
                    'bien_the'            => $tongBienThe,
                    'trang_thai'          => $trangThai,
                    'bieu_do'             => $bieuDo,
                    'bieu_do_khach_hang'  => $bieuDoKhachHang,
                    'bieu_do_san_pham'    => $bieuDoSanPham,
                    'don_hang'            => $donHang,
                    'san_pham'            => $sanPham,
                    'ton_kho_canh_bao'    => $tonKhoCanhBao,
                    'don_can_xu_ly'        => $donCanXuLy,
                    'thanh_toan'           => $thanhToan,
                    'danh_muc_ban_chay'    => $danhMucBanChay,
                    'phan_tich'             => $phanTich,
                    'nhan_su_hoat_dong'     => $nhanSuHoatDong,
                    'khach_hang_hoat_dong'  => $khachHangHoatDong,
                    'phan_tich_do_tuoi'     => $phanTichDoTuoi,
                    'phan_tich_bom_hang'    => $phanTichBomHang,
                ];
            });

            // ================= RESPONSE =================
            return response()->json([
                'thongbao' => 'thành công',
                'message'  => 'Lấy dữ liệu dashboard thành công',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'thongbao' => 'thất bại',
                'message'  => $e->getMessage()
            ], 500);
        }
    }
}
