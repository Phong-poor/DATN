<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use Illuminate\Http\Request;

use App\Models\DatHang;
use App\Models\User;
use App\Models\Bienthe;
use App\Models\DatHangChiTiet;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $period = $request->query('period', 'all');

            $data = Cache::remember("dashboard_data_{$period}", 120, function () use ($period) {
                // ================= TIME =================
                $dateFrom = match ($period) {
                'month' => now()->startOfMonth(),
                'year'  => now()->startOfYear(),
                'all'   => now()->subYears(50),
                default => now()->startOfWeek(),
            };

            // ================= DOANH THU =================
            $tongDoanhThuRaw = DatHang::where('trangthai', 'done')
                ->whereBetween('created_at', [$dateFrom, now()])
                ->sum('tongtien');
            $tongDoanhThu = number_format($tongDoanhThuRaw, 0, ',', '.') . 'đ';

            // ================= KHÁCH =================
            $tongKhachHang = User::where('vaitro', 'user')->count();

            // ================= BIẾN THỂ =================
            $tongBienThe = Bienthe::count();

            // ================= TRẠNG THÁI =================
            $trangThaiRaw = DatHang::selectRaw('trangthai, COUNT(*) as total')
                ->whereBetween('created_at', [$dateFrom, now()])
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
                ->whereBetween('created_at', [$dateFrom, now()])
                ->groupBy('label')
                ->orderBy('label')
                ->get();

            // ================= BIỂU ĐỒ KHÁCH HÀNG =================
            $bieuDoKhachHang = User::where('vaitro', 'user')
                ->selectRaw("DATE(created_at) as label, COUNT(*) as total")
                ->whereBetween('created_at', [$dateFrom, now()])
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