<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chào mừng bạn đến với NextGen Newsletter</title>
</head>
<body style="margin:0;padding:0;background:#07101f;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellspacing="0" cellpadding="0" style="background:#07101f;padding:40px 20px;">
    <tr>
        <td align="center">
            <table width="560" cellpadding="0" cellspacing="0" style="background:#0d1930;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.5);border:1px solid rgba(37,99,235,0.2);">

                <!-- HEADER -->
                <tr>
                    <td style="background:linear-gradient(135deg,#1e40af,#2563eb,#1d4ed8);padding:40px 36px;text-align:center;">
                        <div style="display:inline-block;background:rgba(255,255,255,0.1);border-radius:50%;padding:16px;margin-bottom:16px;">
                            <span style="font-size:36px;">📧</span>
                        </div>
                        <h1 style="margin:0 0 8px;font-size:28px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">NextGen Newsletter</h1>
                        <p style="margin:0;font-size:14px;color:rgba(255,255,255,0.75);">Xu hướng công nghệ · Voucher độc quyền · Tin tức mới nhất</p>
                    </td>
                </tr>

                <!-- CONTENT -->
                <tr>
                    <td style="padding:40px 36px;">
                        <h2 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#f0f6ff;">🎉 Chào mừng bạn!</h2>
                        <p style="margin:0 0 20px;font-size:15px;color:#94a3b8;line-height:1.7;">
                            Bạn đã đăng ký nhận bản tin từ <strong style="color:#60a5fa;">NextGen</strong> bằng địa chỉ:
                        </p>
                        <div style="background:rgba(37,99,235,0.08);border:1px solid rgba(37,99,235,0.25);border-radius:12px;padding:14px 20px;margin-bottom:28px;text-align:center;">
                            <span style="font-size:16px;font-weight:700;color:#60a5fa;">{{ $email }}</span>
                        </div>

                        <p style="margin:0 0 20px;font-size:15px;color:#94a3b8;line-height:1.7;">
                            Từ bây giờ, bạn sẽ nhận được:
                        </p>

                        <!-- Feature list -->
                        <table width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="padding:10px 0;vertical-align:top;width:36px;">
                                    <span style="font-size:20px;">🔥</span>
                                </td>
                                <td style="padding:10px 0 10px 8px;font-size:14px;color:#cbd5e1;line-height:1.6;">
                                    <strong style="color:#f0f6ff;">Tin tức công nghệ mới nhất</strong> – Cập nhật ngay khi có bài viết mới từ NextGen.
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;vertical-align:top;width:36px;">
                                    <span style="font-size:20px;">🎁</span>
                                </td>
                                <td style="padding:10px 0 10px 8px;font-size:14px;color:#cbd5e1;line-height:1.6;">
                                    <strong style="color:#f0f6ff;">Voucher giảm giá độc quyền</strong> – Nhận ngay mã khuyến mãi khi admin tạo voucher mới.
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;vertical-align:top;width:36px;">
                                    <span style="font-size:20px;">⚡</span>
                                </td>
                                <td style="padding:10px 0 10px 8px;font-size:14px;color:#cbd5e1;line-height:1.6;">
                                    <strong style="color:#f0f6ff;">Ưu đãi VIP sớm nhất</strong> – Là người đầu tiên biết về Flash Sale và sự kiện đặc biệt.
                                </td>
                            </tr>
                        </table>

                        <!-- CTA Button -->
                        <div style="text-align:center;margin:32px 0 24px;">
                            <a href="http://localhost:5173" style="display:inline-block;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:12px;font-weight:700;font-size:15px;letter-spacing:0.3px;box-shadow:0 4px 20px rgba(37,99,235,0.4);">
                                🛍️ Khám phá NextGen ngay
                            </a>
                        </div>
                    </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                    <td style="background:rgba(255,255,255,0.02);border-top:1px solid rgba(255,255,255,0.05);padding:24px 36px;text-align:center;">
                        <p style="margin:0 0 8px;font-size:12px;color:#475569;">
                            Bạn nhận được email này vì đã đăng ký tại <strong style="color:#60a5fa;">nextgen.vn</strong>
                        </p>
                        <p style="margin:0;font-size:11px;color:#334155;">
                            Muốn hủy đăng ký?
                            <a href="http://localhost:5173/unsubscribe?email={{ urlencode($email) }}" style="color:#60a5fa;">Nhấn vào đây</a>
                        </p>
                        <p style="margin:12px 0 0;font-size:11px;color:#1e293b;">© {{ date('Y') }} NextGen Technology. Mọi quyền được bảo lưu.</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
