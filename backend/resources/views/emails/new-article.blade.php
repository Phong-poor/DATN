<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài viết mới từ NextGen</title>
</head>
<body style="margin:0;padding:0;background:#07101f;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellspacing="0" cellpadding="0" style="background:#07101f;padding:40px 20px;">
    <tr>
        <td align="center">
            <table width="560" cellpadding="0" cellspacing="0" style="background:#0d1930;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.5);border:1px solid rgba(37,99,235,0.2);">

                <!-- HEADER -->
                <tr>
                    <td style="background:linear-gradient(135deg,#0f172a,#1e3a5f);padding:36px;text-align:center;position:relative;">
                        <p style="margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:2px;color:#60a5fa;text-transform:uppercase;">NextGen · Bản Tin Công Nghệ</p>
                        <h1 style="margin:0;font-size:11px;font-weight:600;color:rgba(255,255,255,0.4);letter-spacing:1px;">📰 BÀI VIẾT MỚI VỪA ĐƯỢC ĐĂNG</h1>
                    </td>
                </tr>

                @if($article->hinhanh)
                <!-- ARTICLE IMAGE -->
                <tr>
                    <td style="padding:0;">
                        <img src="{{ $article->hinhanh }}" alt="{{ $article->tieude }}"
                             style="width:100%;height:220px;object-fit:cover;display:block;" />
                    </td>
                </tr>
                @endif

                <!-- CONTENT -->
                <tr>
                    <td style="padding:36px;">
                        <!-- Category badge -->
                        <div style="margin-bottom:16px;">
                            <span style="display:inline-block;background:rgba(37,99,235,0.15);border:1px solid rgba(37,99,235,0.3);color:#60a5fa;padding:4px 12px;border-radius:6px;font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">
                                {{ $article->danhmuc ?? 'Tin tức' }}
                            </span>
                        </div>

                        <h2 style="margin:0 0 16px;font-size:22px;font-weight:800;color:#f0f6ff;line-height:1.4;">
                            {{ $article->tieude }}
                        </h2>

                        @if($article->tomtat)
                        <p style="margin:0 0 24px;font-size:15px;color:#94a3b8;line-height:1.75;">
                            {{ Str::limit($article->tomtat, 200) }}
                        </p>
                        @endif

                        <!-- Meta info -->
                        <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
                            <tr>
                                <td style="font-size:12px;color:#475569;">
                                    @if($article->tacgia)
                                    ✍️ <strong style="color:#64748b;">{{ $article->tacgia }}</strong>
                                    @endif
                                    &nbsp;·&nbsp;
                                    📅 <strong style="color:#64748b;">{{ optional($article->created_at)->format('d/m/Y') }}</strong>
                                </td>
                            </tr>
                        </table>

                        <!-- CTA Button -->
                        <div style="text-align:center;margin-bottom:8px;">
                            <a href="http://localhost:5173/tin-tuc/{{ $article->slug ?? $article->id }}"
                               style="display:inline-block;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:12px;font-weight:700;font-size:15px;box-shadow:0 4px 20px rgba(37,99,235,0.4);letter-spacing:0.2px;">
                                📖 Đọc bài viết →
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
                            Bạn nhận được email này vì đã đăng ký bản tin tại <strong style="color:#60a5fa;">NextGen</strong>
                        </p>
                        <p style="margin:0 0 12px;font-size:12px;color:#334155;">
                            <a href="http://localhost:5173/tin-tuc" style="color:#60a5fa;text-decoration:none;">Xem tất cả bài viết</a>
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
