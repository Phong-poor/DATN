<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use Illuminate\Http\Request;

use App\Models\DatHang;
use App\Models\User;
use App\Models\BienThe;
use App\Models\DatHangChiTiet;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $period = $request->query('period', 'all');

            $data = Cache::remember("dashboard_data_v3_{$period}", 120, function () use ($period) {
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

            // ================= BIỂU ĐỒ DOANH THU =================
            $bieuDo = DatHang::selectRaw("
                    DATE(created_at) as label,
                    SUM(tongtien) as total
                ")
                ->where('trangthai', 'done')
                ->whereBetween('created_at', [$dateFrom, $now])
                ->groupBy('label')
                ->orderBy('label')
                ->get();

            // ================= BIỂU ĐỒ KHÁCH HÀNG =================
            $bieuDoKhachHang = User::where('vaitro', 'user')
                ->selectRaw("DATE(created_at) as label, COUNT(*) as total")
                ->whereBetween('created_at', [$dateFrom, $now])
                ->groupBy('label')
                ->orderBy('label')
                ->get();

            // ================= BIỂU ĐỒ SẢN PHẨM =================
            $bieuDoSanPham = DatHangChiTiet::selectRaw("
                    DATE(created_at) as label,
                    SUM(soluong) as total
                ")
                ->whereHas('datHang', function ($q) use ($dateFrom) {
                    $q->where('trangthai', 'done')
                      ->whereBetween('created_at', [$dateFrom, now()]);
                })
                ->groupBy('label')
                ->orderBy('label')
                ->get();

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
            ];

            // ================= TRẠNG THÁI NHÂN SỰ =================
            $onlineSince = $now->copy()->subMinutes(5);
            $idleSince = $now->copy()->subMinutes(15);
            $staffRows = User::where('vaitro', '!=', 'user')
                ->orderByDesc('hoat_dong_cuoi_luc')
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

                return [
                    'period'              => $period,
                    'doanh_thu'           => $tongDoanhThu,
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
