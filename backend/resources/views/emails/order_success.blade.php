<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Xác nhận đơn hàng</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial, sans-serif;">

    <table width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8;padding:30px 0;">
        <tr>
            <td align="center">

                <!-- BOX -->
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">

                    <!-- HEADER -->
                    <tr>
                        <td style="background:blue;padding:30px;text-align:center;color:white;">
                            <h1 style="margin:0;">NextGen Shop</h1>
                            <p style="margin:5px 0 0;">Xác nhận đặt hàng thành công</p>
                        </td>
                    </tr>

                    <!-- CONTENT -->
                    <tr>
                        <td style="padding:30px;">
                            <h2 style="margin-top:0;color:#333;">
                                Xin chào {{ $user->name }} 👋
                            </h2>

                            <p style="color:#555;font-size:15px;">
                                Cảm ơn bạn đã tin tưởng mua sắm tại <strong>NextGen Laptop</strong>. Đơn hàng của bạn đã được tiếp nhận và đang được xử lý.
                            </p>

                            <div style="margin:25px 0; padding:20px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">
                                <h3 style="margin-top:0; color:#0f2b5b; font-size:16px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
                                    Thông tin đơn hàng #{{ $order->id_dathang }}
                                </h3>
                                <table width="100%" style="font-size:14px; color:#475569; line-height:2;">
                                    <tr>
                                        <td><strong>Ngày đặt:</strong></td>
                                        <td align="right">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Trạng thái:</strong></td>
                                        <td align="right" style="color:#2563eb; font-weight:bold;">Chờ xác nhận</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phương thức:</strong></td>
                                        <td align="right">{{ $order->PTTT === 'COD' ? 'Thanh toán khi nhận hàng (COD)' : $order->PTTT }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Địa chỉ:</strong></td>
                                        <td align="right">{{ $order->diachi }}</td>
                                    </tr>
                                </table>
                            </div>

                            <h3 style="color:#333; font-size:16px;">Chi tiết sản phẩm</h3>
                            <table width="100%" cellspacing="0" cellpadding="10" style="border-collapse:collapse; font-size:14px;">
                                <thead style="background:#f1f5f9;">
                                    <tr>
                                        <th align="left" style="border-bottom:2px solid #e2e8f0;">Sản phẩm</th>
                                        <th align="center" style="border-bottom:2px solid #e2e8f0;">SL</th>
                                        <th align="right" style="border-bottom:2px solid #e2e8f0;">Giá</th>
                                        <th><img src="" alt=""></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->chiTiets as $ct)
                                    <tr>
                                        <td style="border-bottom:1px solid #f1f5f9; padding:12px 0;">
                                            <div style="font-weight:bold; color:#1e293b;">{{ $ct->bienThe->sanPham->tenSP ?? 'Sản phẩm' }}</div>
                                            <div style="font-size:12px; color:#64748b;">{{ $ct->bienThe->ten_bienthe }}</div>
                                        </td>
                                        <td align="center" style="border-bottom:1px solid #f1f5f9;">{{ $ct->soluong }}</td>
                                        <td align="right" style="border-bottom:1px solid #f1f5f9;">{{ number_format($ct->gia, 0, ',', '.') }}đ</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" align="right" style="padding-top:20px; font-weight:bold; color:#333;">Tạm tính:</td>
                                        <td align="right" style="padding-top:20px; font-weight:bold; color:#333;">{{ number_format($order->tongtien, 0, ',', '.') }}đ</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" align="right" style="color:#64748b; font-size:12px;">Phí vận chuyển:</td>
                                        <td align="right" style="color:#64748b; font-size:12px;">Miễn phí</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" align="right" style="padding-top:10px; font-size:18px; font-weight:800; color:#1e6be6;">TỔNG CỘNG:</td>
                                        <td align="right" style="padding-top:10px; font-size:18px; font-weight:800; color:#1e6be6;">{{ number_format($order->tongtien, 0, ',', '.') }}đ</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div style="text-align:center;margin:40px 0 20px;">
                                <a href="http://localhost:5173/profile"
                                   style="background:#1e6be6;
                                          color:#fff;
                                          padding:12px 30px;
                                          border-radius:8px;
                                          text-decoration:none;
                                          font-weight:bold;
                                          display:inline-block;
                                          box-shadow:0 4px 12px rgba(30,107,230,0.2);">
                                    Theo dõi đơn hàng
                                </a>
                            </div>

                            <p style="color:#94a3b8;font-size:13px;text-align:center;margin-top:30px;">
                                Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ hotline 1800 9999 hoặc trả lời thư này.
                            </p>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#f8fafc;padding:20px;text-align:center;color:#94a3b8;font-size:12px;border-top:1px solid #e2e8f0;">
                            © 2026 NextGen Laptop. All rights reserved.<br>
                            Địa chỉ: 123 Đường Công Nghệ, TP. Hồ Chí Minh.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
