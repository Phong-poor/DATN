<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chúc mừng sinh nhật</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: #0a0f1e;
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .wrapper {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }

        .card {
            background: linear-gradient(145deg, #0d1630 0%, #080f22 50%, #0d1630 100%);
            border: 1px solid #2563eb33;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        /* Top decorative bar */
        .card::before {
            content: '';
            display: block;
            height: 3px;
            background: linear-gradient(90deg, transparent, #2563eb, #60a5fa, #2563eb, transparent);
        }

        /* Subtle corner ornaments */
        .ornament {
            position: absolute;
            width: 60px;
            height: 60px;
            opacity: 0.4;
        }
        .ornament-tl { top: 12px; left: 12px; border-top: 1px solid #2563eb; border-left: 1px solid #2563eb; }
        .ornament-tr { top: 12px; right: 12px; border-top: 1px solid #2563eb; border-right: 1px solid #2563eb; }
        .ornament-bl { bottom: 12px; left: 12px; border-bottom: 1px solid #2563eb; border-left: 1px solid #2563eb; }
        .ornament-br { bottom: 12px; right: 12px; border-bottom: 1px solid #2563eb; border-right: 1px solid #2563eb; }

        /* Header */
        .header {
            padding: 60px 60px 40px;
            text-align: center;
            position: relative;
        }

        .brand {
            font-family: 'DM Sans', sans-serif;
            font-weight: 500;
            font-size: 11px;
            letter-spacing: 0.4em;
            color: #60a5fa;
        }

        .candle-row {
            font-size: 28px;
            letter-spacing: 8px;
            margin-bottom: 28px;
            opacity: 0.9;
        }

        .greeting-sub {
            font-family: 'DM Sans', sans-serif;
            font-weight: 300;
            font-size: 11px;
            letter-spacing: 0.35em;
            color: #7aa3d4;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 38px;
            color: #e0eeff;
            line-height: 1.15;
            letter-spacing: 0.01em;
        }

        h1 .name {
            color: #60a5fa;
            font-style: italic;
            display: block;
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0 60px;
            margin-bottom: 40px;
        }
        .divider-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, #c9a84c44);
        }
        .divider-line.right {
            background: linear-gradient(90deg, #c9a84c44, transparent);
        }
        .divider-diamond {
            width: 6px;
            height: 6px;
            background: #c9a84c;
            transform: rotate(45deg);
            opacity: 0.7;
        }

        /* Body */
        .body {
            padding: 0 60px 40px;
            text-align: center;
        }

        .message {
            font-size: 15px;
            line-height: 1.85;
            color: #b8a88a;
            font-weight: 300;
            margin-bottom: 44px;
        }

        /* Coupon block */
        .coupon-label {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.4em;
            color: #8a7a5a;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .coupon-box {
            background: linear-gradient(135deg, #1e1608, #2a1e08, #1e1608);
            border: 1px solid #c9a84c55;
            border-radius: 2px;
            padding: 24px 40px;
            margin-bottom: 44px;
            position: relative;
            display: inline-block;
            width: 100%;
        }

        /* Dashed sides */
        .coupon-box::before,
        .coupon-box::after {
            content: '';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 60%;
            background: repeating-linear-gradient(
                to bottom,
                #c9a84c55 0px, #c9a84c55 4px,
                transparent 4px, transparent 8px
            );
        }
        .coupon-box::before { left: 0; }
        .coupon-box::after { right: 0; }

        .coupon-code {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            color: #f5d78e;
            letter-spacing: 0.15em;
        }

        /* Closing */
        .closing {
            font-size: 14px;
            line-height: 1.9;
            color: #8a7a5a;
            font-weight: 300;
        }

        .closing strong {
            color: #c9a84c;
            font-weight: 500;
        }

        /* Footer */
        .footer {
            padding: 28px 60px 48px;
            text-align: center;
            border-top: 1px solid #c9a84c1a;
        }

        .footer-text {
            font-size: 10px;
            letter-spacing: 0.3em;
            color: #4a4030;
            text-transform: uppercase;
        }

        /* Bottom bar */
        .card::after {
            content: '';
            display: block;
            height: 3px;
            background: linear-gradient(90deg, transparent, #c9a84c, #f5d78e, #c9a84c, transparent);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">

            <div class="ornament ornament-tl"></div>
            <div class="ornament ornament-tr"></div>
            <div class="ornament ornament-bl"></div>
            <div class="ornament ornament-br"></div>

            <div class="header">
                <div class="brand">NextGen &nbsp;·&nbsp; Dành riêng cho bạn</div>
                <div class="candle-row">🕯️ 🎂 🕯️</div>
                <div class="greeting-sub">Chúc mừng sinh nhật</div>
                <h1>
                    <span class="name">{{ $customerName }}</span>
                </h1>
            </div>

            <div class="divider">
                <div class="divider-line"></div>
                <div class="divider-diamond"></div>
                <div class="divider-line right"></div>
            </div>

            <div class="body">
                <p class="message">
                    Nhân dịp sinh nhật của bạn, NextGen xin gửi tặng<br>
                    một món quà nhỏ thể hiện sự trân trọng của chúng tôi.<br>
                    Mong rằng ngày hôm nay thật đặc biệt và tràn đầy niềm vui.
                </p>

                <p class="coupon-label">Mã ưu đãi của bạn</p>
                <div class="coupon-box">
                    <div class="coupon-code">{{ $couponCode }}</div>
                </div>

                <p class="closing">
                    Chúc bạn một ngày sinh nhật thật vui vẻ và hạnh phúc.<br><br>
                    Trân trọng,<br>
                    <strong>Đội ngũ NextGen</strong>
                </p>
            </div>

            <div class="footer">
                <p class="footer-text">© {{ date('Y') }} NextGen &nbsp;·&nbsp; Đây là email tự động, vui lòng không trả lời</p>
            </div>

        </div>
    </div>
</body>
</html>