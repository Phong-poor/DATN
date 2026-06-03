<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Mã OTP</title>
</head>
<body style="margin:0; padding:0; background:#f1f5f9; font-family:Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
        <tr>
            <td align="center">

                <table width="420" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; padding:30px; box-shadow:0 10px 30px rgba(0,0,0,0.08);">

                    <!-- LOGO / TITLE -->
                    <tr>
                        <td align="center" style="padding-bottom:20px;">
                            <h2 style="margin:0; color:#2563eb;">Predator</h2>
                        </td>
                    </tr>

                    <!-- ICON -->
                    <tr>
                        <td align="center" style="padding-bottom:20px;">
                            <div style="width:60px; height:60px; background:#e0ecff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                                🔐
                            </div>
                        </td>
                    </tr>

                    <!-- TITLE -->
                    <tr>
                        <td align="center" style="padding-bottom:10px;">
                            <h3 style="margin:0; color:#1e293b;">Xác thực OTP</h3>
                        </td>
                    </tr>

                    <!-- DESC -->
                    <tr>
                        <td align="center" style="padding-bottom:25px;">
                            <p style="margin:0; color:#64748b; font-size:14px;">
                                Sử dụng mã OTP dưới đây để đặt lại mật khẩu của bạn
                            </p>
                        </td>
                    </tr>

                    <!-- OTP BOX -->
                    <tr>
                        <td align="center" style="padding-bottom:25px;">
                            <div style="
                                display:inline-block;
                                padding:14px 28px;
                                font-size:26px;
                                font-weight:bold;
                                letter-spacing:6px;
                                color:#2563eb;
                                background:#f1f5f9;
                                border-radius:10px;
                            ">
                                {{ $otp }}
                            </div>
                        </td>
                    </tr>

                    <!-- NOTE -->
                    <tr>
                        <td align="center" style="padding-bottom:20px;">
                            <p style="margin:0; font-size:13px; color:#ef4444;">
                                Mã OTP sẽ hết hạn sau 5 phút
                            </p>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td align="center" style="padding-top:20px;">
                            <p style="margin:0; font-size:11px; color:#94a3b8;">
                                © 2026 VinaTech Premium. Bảo mật tuyệt đối.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>