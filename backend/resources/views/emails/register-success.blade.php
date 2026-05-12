<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Đăng ký thành công</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial, sans-serif;">

    <table width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8;padding:30px 0;">
        <tr>
            <td align="center">

                <!-- BOX -->
                <table width="500" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">

                    <!-- HEADER -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:30px;text-align:center;color:white;">
                            <h1 style="margin:0;">NextGen Labtop</h1>
                            <p style="margin:5px 0 0;">Chinh phục tầm cao mới</p>
                        </td>
                    </tr>

                    <!-- CONTENT -->
                    <tr>
                        <td style="padding:30px;">
                            <h2 style="margin-top:0;color:#333;">
                                Xin chào {{ $user->name }} 👋
                            </h2>

                            <p style="color:#555;font-size:15px;">
                                Bạn đã đăng ký tài khoản thành công 🎉
                            </p>

                            <p style="color:#555;font-size:15px;">
                                Chào mừng bạn đến với hệ thống <strong>NextGen</strong>.
                            </p>

                            <!-- BUTTON -->
                            <div style="text-align:center;margin:30px 0;">
                                <a href="http://localhost:5173/login"
                                   style="background:linear-gradient(135deg,#4f46e5,#7c3aed);
                                          color:#fff;
                                          padding:12px 25px;
                                          border-radius:25px;
                                          text-decoration:none;
                                          font-weight:bold;
                                          display:inline-block;">
                                    Đăng nhập ngay
                                </a>
                            </div>

                            <p style="color:#999;font-size:13px;">
                                Nếu bạn không thực hiện hành động này, vui lòng bỏ qua email.
                            </p>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#f9fafb;padding:15px;text-align:center;color:#999;font-size:12px;">
                            © 2026 NextGen. All rights reserved.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>