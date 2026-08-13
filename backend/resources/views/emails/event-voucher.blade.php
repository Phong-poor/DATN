<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher sự kiện</title>
</head>
<body style="margin:0;background:{{ $theme['background'] }};font-family:Arial,sans-serif;color:#e5efff;padding:32px 16px">
    <div style="max-width:600px;margin:auto;background:{{ $theme['card'] }};border:1px solid {{ $theme['primary'] }};border-radius:16px;overflow:hidden">
        <div style="height:5px;background:linear-gradient(90deg,{{ $theme['primary'] }},{{ $theme['accent'] }},{{ $theme['primary'] }})"></div>
        <div style="padding:42px 40px;text-align:center">
            <div style="font-size:46px;margin-bottom:14px">{{ $theme['icon'] }}</div>
            <p style="margin:0 0 12px;color:{{ $theme['accent'] }};font-size:12px;letter-spacing:3px">NEXTGEN GROUP</p>
            <h1 style="margin:0 0 8px;font-size:30px;color:#fff">{{ $theme['headline'] }}</h1>
            <p style="margin:0 0 20px;color:{{ $theme['accent'] }};font-weight:bold">{{ $promotion->ten }}</p>
            <p style="color:#b8c7df;line-height:1.7">
                Xin chào <strong style="color:#fff">{{ $customer->ten ?: $customer->name }}</strong>,<br>
                {{ $theme['message'] }}<br>
                Voucher đã được lưu vào tài khoản của bạn.
            </p>

            <div style="margin:30px 0;padding:24px;border:1px dashed {{ $theme['accent'] }};border-radius:12px;background:{{ $theme['background'] }}">
                <p style="margin:0 0 10px;color:{{ $theme['accent'] }};font-size:12px;text-transform:uppercase;letter-spacing:2px">Mã voucher của bạn</p>
                <strong style="font-size:32px;letter-spacing:5px;color:{{ $theme['accent'] }}">{{ $promotion->code }}</strong>
            </div>

            <p style="color:#b8c7df;line-height:1.7">
                @if($promotion->loai === 'percent')
                    Ưu đãi giảm {{ rtrim(rtrim(number_format((float) $promotion->giatri, 2, '.', ''), '0'), '.') }}%.
                @else
                    Ưu đãi giảm {{ number_format((float) $promotion->giatri, 0, ',', '.') }}đ.
                @endif
                @if($expiresAt)
                    Voucher có hiệu lực đến {{ $expiresAt->format('H:i d/m/Y') }}.
                @endif
            </p>
        </div>
        <div style="padding:20px;text-align:center;background:{{ $theme['background'] }};color:#94a3b8;font-size:12px">
            © {{ date('Y') }} NextGen Group · Đây là email tự động, vui lòng không trả lời.
        </div>
    </div>
</body>
</html>
