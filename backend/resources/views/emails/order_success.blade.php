<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng</title>
</head>
<body style="margin:0;padding:0;background:#f2f5f8;font-family:Arial,Helvetica,sans-serif;color:#202938;">
    @php
        $customerName = $user->ten ?? $user->name ?? 'Quý khách';
        $orderTotal = (float) ($order->tongtien ?? 0);
        $depositAmount = (int) ceil($orderTotal * 0.5);
        $remainingAmount = max(0, (int) $orderTotal - $depositAmount);
        $paymentMethod = ($order->PTTT ?? '') === 'COD'
            ? 'Thanh toán khi nhận hàng (COD)'
            : ($order->PTTT ?? 'Chuyển khoản');
        $orderCode = 'NG-' . str_pad((string) ($order->id_dathang ?? 0), 6, '0', STR_PAD_LEFT);
    @endphp

    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        NextGen đã tiếp nhận đơn hàng #{{ $order->id_dathang }}. Vui lòng đặt cọc 50%, phần còn lại thanh toán khi nhận hàng.
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f2f5f8;padding:28px 12px;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:640px;max-width:100%;background:#ffffff;border-collapse:separate;border-spacing:0;overflow:hidden;border-radius:18px;box-shadow:0 18px 50px rgba(15,23,42,0.12);">
                    <tr>
                        <td style="background:#08b957;padding:0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td style="padding:28px 34px 22px;color:#ffffff;">
                                        <div style="font-size:13px;letter-spacing:2px;text-transform:uppercase;font-weight:700;opacity:.92;">NextGen E-Receipt</div>
                                        <h1 style="margin:16px 0 6px;font-size:28px;line-height:1.2;font-weight:800;">Đơn hàng đã được tiếp nhận</h1>
                                        <p style="margin:0;font-size:14px;line-height:1.6;opacity:.94;">Cảm ơn {{ $customerName }} đã mua sắm tại NextGen.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="height:16px;background:linear-gradient(150deg,#ffffff 0,#ffffff 18%,#08b957 18%,#08b957 24%,#ffffff 24%,#ffffff 34%,#08b957 34%);opacity:.9;"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 34px 8px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td width="52%" valign="top" style="padding-bottom:18px;">
                                        <div style="font-size:13px;color:#64748b;font-weight:700;text-transform:uppercase;">Tổng giá trị đơn hàng</div>
                                        <div style="margin-top:6px;font-size:34px;line-height:1;font-weight:900;color:#08a848;">{{ number_format($orderTotal, 0, ',', '.') }}đ</div>
                                    </td>
                                    <td width="48%" valign="top" align="right" style="padding-bottom:18px;">
                                        <div style="font-size:13px;color:#64748b;font-weight:700;text-transform:uppercase;">Ngày | Giờ</div>
                                        <div style="margin-top:8px;font-size:18px;font-weight:800;color:#111827;">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                                        <div style="margin-top:6px;font-size:13px;color:#64748b;">Mã đơn: <strong style="color:#111827;">{{ $orderCode }}</strong></div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 34px 22px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;">
                                <tr>
                                    <td width="48%" valign="top" style="padding:24px 24px 18px;">
                                        <div style="font-size:15px;color:#08a848;font-weight:900;letter-spacing:.5px;text-transform:uppercase;">Chi tiết đơn hàng</div>
                                        <div style="height:14px;"></div>
                                        <div style="font-size:13px;color:#9aa4b2;">Người nhận</div>
                                        <div style="font-size:15px;font-weight:800;color:#111827;margin:3px 0 12px;">{{ $customerName }}</div>

                                        <div style="font-size:13px;color:#9aa4b2;">Mã đơn hàng</div>
                                        <div style="font-size:15px;font-weight:800;color:#111827;margin:3px 0 12px;">#{{ $order->id_dathang }}</div>

                                        <div style="font-size:13px;color:#9aa4b2;">Phương thức</div>
                                        <div style="font-size:15px;font-weight:800;color:#111827;margin:3px 0 12px;">{{ $paymentMethod }}</div>

                                        <div style="font-size:13px;color:#9aa4b2;">Giao đến</div>
                                        <div style="font-size:15px;font-weight:800;color:#111827;line-height:1.45;margin-top:3px;">{{ $order->diachi }}</div>
                                    </td>
                                    <td width="52%" valign="top" style="padding:24px 24px 18px;background:#ffffff;border-left:1px solid #e2e8f0;">
                                        <div style="font-size:15px;color:#08a848;font-weight:900;letter-spacing:.5px;text-transform:uppercase;">Hóa đơn của bạn</div>
                                        <div style="margin-top:14px;border:1px solid #d7dde5;background:#ffffff;padding:16px 16px 12px;">
                                            <div style="font-size:13px;color:#64748b;">Bạn thanh toán bằng:</div>
                                            <div style="font-size:17px;font-weight:900;color:#111827;margin-top:4px;">{{ $paymentMethod }}</div>

                                            <div style="border-top:1px dashed #b9c3d0;margin:16px 0 12px;"></div>

                                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="font-size:13px;color:#111827;">
                                                <tr>
                                                    <td style="padding:0 0 9px;color:#64748b;">Chi tiết</td>
                                                    <td align="center" style="padding:0 0 9px;color:#64748b;">SL</td>
                                                    <td align="right" style="padding:0 0 9px;color:#64748b;">Số tiền</td>
                                                </tr>
                                                @foreach($order->chiTiets as $ct)
                                                    <tr>
                                                        <td valign="top" style="padding:8px 0;border-top:1px dashed #d7dde5;font-weight:800;line-height:1.35;">
                                                            {{ $ct->bienThe->sanPham->tenSP ?? 'Sản phẩm' }}
                                                            @if(!empty($ct->bienThe->ten_bienthe))
                                                                <div style="font-size:12px;color:#64748b;font-weight:400;">{{ $ct->bienThe->ten_bienthe }}</div>
                                                            @endif
                                                        </td>
                                                        <td align="center" valign="top" style="padding:8px 8px;border-top:1px dashed #d7dde5;">{{ $ct->soluong }}</td>
                                                        <td align="right" valign="top" style="padding:8px 0;border-top:1px dashed #d7dde5;font-weight:800;">{{ number_format((float) $ct->gia * (int) $ct->soluong, 0, ',', '.') }}đ</td>
                                                    </tr>
                                                @endforeach
                                            </table>

                                            <div style="border-top:1px dashed #b9c3d0;margin:12px 0;"></div>

                                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="font-size:14px;color:#111827;">
                                                <tr>
                                                    <td style="padding:5px 0;">Tổng giá trị đơn hàng</td>
                                                    <td align="right" style="padding:5px 0;font-weight:800;">{{ number_format($orderTotal, 0, ',', '.') }}đ</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:5px 0;">Phí vận chuyển</td>
                                                    <td align="right" style="padding:5px 0;font-weight:800;color:#08a848;">Miễn phí</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:8px 0;color:#ef4444;font-weight:800;">Đặt cọc cần chuyển trước (50%)</td>
                                                    <td align="right" style="padding:8px 0;color:#ef4444;font-weight:900;">{{ number_format($depositAmount, 0, ',', '.') }}đ</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:8px 0;color:#08a848;font-weight:900;">Còn lại khi giao tận nhà</td>
                                                    <td align="right" style="padding:8px 0;color:#08a848;font-size:18px;font-weight:900;">{{ number_format($remainingAmount, 0, ',', '.') }}đ</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 34px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#ecfdf3;border:1px solid #b7ebc9;border-radius:14px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <div style="font-size:16px;font-weight:900;color:#067a38;">Lưu ý thanh toán rõ ràng</div>
                                        <p style="margin:8px 0 0;font-size:14px;line-height:1.7;color:#1f5135;">
                                            Đơn hàng cần đặt cọc trước <strong>{{ number_format($depositAmount, 0, ',', '.') }}đ</strong> để xác nhận uy tín.
                                            Khi nhân viên giao hàng đến, khách chỉ thanh toán nốt <strong>{{ number_format($remainingAmount, 0, ',', '.') }}đ</strong>,
                                            không thanh toán lại toàn bộ giá trị đơn hàng.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 34px 30px;">
                            <div style="font-size:18px;font-weight:900;color:#111827;margin-bottom:12px;">Chi tiết sản phẩm</div>
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;font-size:14px;">
                                <thead>
                                    <tr>
                                        <th align="left" style="background:#eef2f7;color:#111827;padding:13px 12px;border-bottom:1px solid #dbe3ee;">Sản phẩm</th>
                                        <th align="center" style="background:#eef2f7;color:#111827;padding:13px 12px;border-bottom:1px solid #dbe3ee;">SL</th>
                                        <th align="right" style="background:#eef2f7;color:#111827;padding:13px 12px;border-bottom:1px solid #dbe3ee;">Giá</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->chiTiets as $ct)
                                        <tr>
                                            <td style="padding:14px 12px;border-bottom:1px solid #eef2f7;font-weight:800;color:#1f2937;">
                                                {{ $ct->bienThe->sanPham->tenSP ?? 'Sản phẩm' }}
                                                @if(!empty($ct->bienThe->ten_bienthe))
                                                    <div style="margin-top:3px;font-size:12px;color:#64748b;font-weight:400;">{{ $ct->bienThe->ten_bienthe }}</div>
                                                @endif
                                            </td>
                                            <td align="center" style="padding:14px 12px;border-bottom:1px solid #eef2f7;">{{ $ct->soluong }}</td>
                                            <td align="right" style="padding:14px 12px;border-bottom:1px solid #eef2f7;font-weight:800;">{{ number_format((float) $ct->gia * (int) $ct->soluong, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:18px;font-size:15px;">
                                <tr>
                                    <td align="right" style="padding:6px 12px;color:#64748b;">Tổng giá trị đơn hàng:</td>
                                    <td align="right" width="180" style="padding:6px 12px;font-weight:900;color:#111827;">{{ number_format($orderTotal, 0, ',', '.') }}đ</td>
                                </tr>
                                <tr>
                                    <td align="right" style="padding:6px 12px;color:#ef4444;">Đặt cọc trước 50%:</td>
                                    <td align="right" style="padding:6px 12px;font-weight:900;color:#ef4444;">{{ number_format($depositAmount, 0, ',', '.') }}đ</td>
                                </tr>
                                <tr>
                                    <td align="right" style="padding:10px 12px;font-size:18px;font-weight:900;color:#08a848;">Thanh toán còn lại khi nhận hàng:</td>
                                    <td align="right" style="padding:10px 12px;font-size:22px;font-weight:900;color:#08a848;">{{ number_format($remainingAmount, 0, ',', '.') }}đ</td>
                                </tr>
                            </table>

                            <div style="text-align:center;margin-top:26px;">
                                <a href="http://localhost:5173/profile" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:900;padding:14px 34px;border-radius:10px;">Theo dõi đơn hàng</a>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8fafc;padding:22px 34px;text-align:center;border-top:1px solid #e2e8f0;">
                            <div style="font-size:13px;line-height:1.6;color:#64748b;">
                                Nếu cần hỗ trợ, vui lòng liên hệ hotline <strong style="color:#111827;">1800 9999</strong> hoặc phản hồi email này.
                            </div>
                            <div style="margin-top:10px;font-size:12px;color:#94a3b8;">© 2026 NextGen Laptop. Premium Tech Store.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
