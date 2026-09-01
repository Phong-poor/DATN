<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher mới từ NextGen</title>
</head>
<body style="margin:0;padding:0;background:#07101f;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellspacing="0" cellpadding="0" style="background:#07101f;padding:40px 20px;">
    <tr>
        <td align="center">
            <table width="560" cellpadding="0" cellspacing="0" style="background:#0d1930;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.5);border:1px solid rgba(37,99,235,0.2);">

                <!-- HEADER -->
                <tr>
                    <td style="background:linear-gradient(135deg,#1e3a8a,#2563eb,#3b82f6);padding:40px 36px;text-align:center;">
                        <div style="font-size:48px;margin-bottom:12px;">🎁</div>
                        <h1 style="margin:0 0 8px;font-size:26px;font-weight:800;color:#ffffff;">Voucher mới dành cho bạn!</h1>
                        <p style="margin:0;font-size:14px;color:rgba(255,255,255,0.8);">NextGen gửi tặng ưu đãi đặc biệt</p>
                    </td>
                </tr>

                <!-- VOUCHER CARD -->
                <tr>
                    <td style="padding:36px 36px 0;">
                        <div style="background:linear-gradient(135deg,rgba(37,99,235,0.08),rgba(29,78,216,0.06));border:2px dashed rgba(59,130,246,0.4);border-radius:16px;padding:28px;text-align:center;margin-bottom:28px;">
                            <p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:2px;color:#60a5fa;text-transform:uppercase;">Mã giảm giá</p>
                            <div style="background:#0a1628;border:1px solid rgba(59,130,246,0.35);border-radius:12px;padding:14px 24px;display:inline-block;margin-bottom:16px;">
                                <span style="font-size:28px;font-weight:900;color:#93c5fd;letter-spacing:4px;font-family:monospace;">{{ $voucher->code }}</span>
                            </div>
                            <p style="margin:0 0 8px;font-size:16px;font-weight:700;color:#f0f6ff;">
                                @if($voucher->loai === 'percent')
                                    Giảm <span style="color:#60a5fa;">{{ number_format($voucher->giatri) }}%</span>
                                @elseif($voucher->loai === 'fixed')
                                    Giảm <span style="color:#60a5fa;">{{ number_format($voucher->giatri, 0, ',', '.') }}đ</span>
                                @else
                                    Ưu đãi đặc biệt
                                @endif
                            </p>
                            @if($voucher->ten)
                            <p style="margin:0;font-size:13px;color:#94a3b8;">{{ $voucher->ten }}</p>
                            @endif
                        </div>
                    </td>
                </tr>

                <!-- CONTENT -->
                <tr>
                    <td style="padding:0 36px 28px;">
                        @if($voucher->mota)
                        <p style="margin:0 0 20px;font-size:15px;color:#94a3b8;line-height:1.75;">{{ $voucher->mota }}</p>
                        @endif

                        <!-- Details -->
                        <table width="100%" cellspacing="0" cellpadding="0" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:12px;margin-bottom:28px;">
                            @if($voucher->dieu_kien)
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid rgba(255,255,255,0.04);font-size:13px;color:#64748b;">Đơn tối thiểu</td>
                                <td style="padding:12px 16px;border-bottom:1px solid rgba(255,255,255,0.04);font-size:13px;font-weight:700;color:#e2e8f0;text-align:right;">{{ number_format($voucher->dieu_kien, 0, ',', '.') }}đ</td>
                            </tr>
                            @endif
                            @if($voucher->ngaybatdau)
                            <tr>
                                <td style="padding:12px 16px;border-bottom:1px solid rgba(255,255,255,0.04);font-size:13px;color:#64748b;">Hiệu lực từ</td>
                                <td style="padding:12px 16px;border-bottom:1px solid rgba(255,255,255,0.04);font-size:13px;font-weight:700;color:#e2e8f0;text-align:right;">{{ \Carbon\Carbon::parse($voucher->ngaybatdau)->format('d/m/Y') }}</td>
                            </tr>
                            @endif
                            @if($voucher->ngayketthuc)
                            <tr>
                                <td style="padding:12px 16px;font-size:13px;color:#64748b;">Hết hạn</td>
                                <td style="padding:12px 16px;font-size:13px;font-weight:700;color:#60a5fa;text-align:right;">{{ \Carbon\Carbon::parse($voucher->ngayketthuc)->format('d/m/Y') }}</td>
                            </tr>
                            @endif
                        </table>

                        <!-- CTA Button -->
                        <div style="text-align:center;">
                            <a href="http://localhost:5173/gio-hang"
                               style="display:inline-block;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:12px;font-weight:700;font-size:15px;box-shadow:0 4px 20px rgba(37,99,235,0.45);letter-spacing:0.2px;">
                                🛒 Dùng voucher ngay →
                            </a>
                        </div>
                    </td>
                </tr>

                <!-- DIVIDER -->
                <tr>
                    <td style="padding:0 36px;">
                        <div style="height:1px;background:rgba(255,255,255,0.05);"></div>
                    </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                    <td style="padding:24px 36px;text-align:center;">
                        <p style="margin:0 0 8px;font-size:13px;color:#475569;">
                            Bạn nhận email này vì đã đăng ký bản tin tại <strong style="color:#60a5fa;">NextGen</strong>
                        </p>
                        <p style="margin:0 0 12px;font-size:12px;color:#334155;">
                            <a href="http://localhost:5173" style="color:#60a5fa;text-decoration:none;">Về trang chủ</a>
                            &nbsp;·&nbsp;
                            <a href="http://localhost:5173/unsubscribe?email={{ urlencode($subscriberEmail) }}" style="color:#475569;text-decoration:none;">Hủy đăng ký</a>
                        </p>
                        <p style="margin:0;font-size:11px;color:#1e293b;">© {{ date('Y') }} NextGen Technology.</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
