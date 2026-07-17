<?php
$path = __DIR__ . DIRECTORY_SEPARATOR . 'generated_testcases.csv';
$text = file_get_contents($path);
$map = [
    'Account created successfully' => 'Tài khoản được tạo thành công',
    'Email already used error' => 'Email đã được sử dụng',
    'Người dùng đã đăng nhập with orders' => 'Người dùng đã đăng nhập và có đơn hàng',
    'Loại search keyword; 2. Submit; 3. Verify results' => '1. Nhập từ khóa tìm kiếm; 2. Gửi; 3. Kiểm tra kết quả',
    'search keyword' => 'từ khóa tìm kiếm',
    'Submit' => 'Gửi',
    'Verify results' => 'Kiểm tra kết quả',
    'Valid article link' => 'Liên kết bài viết hợp lệ',
    'Account created successfully' => 'Tài khoản được tạo thành công',
    'Email already used error' => 'Email đã được sử dụng',
    'Password reset email sent' => 'Gửi email đặt lại mật khẩu',
    'Redirect to login prompt' => 'Chuyển hướng đến yêu cầu đăng nhập',
    'Login required message' => 'Hiển thị yêu cầu đăng nhập',
    'Product detail displays' => 'Trang chi tiết sản phẩm hiển thị',
    'Item added to cart' => 'Mặt hàng được thêm vào giỏ hàng',
    'Search results page opens' => 'Trang kết quả tìm kiếm mở',
    'Valid product URL' => 'URL sản phẩm hợp lệ',
    'Keyword \'laptop\'' => "Từ khóa 'laptop'",
    'User logged in with orders' => 'Người dùng đã đăng nhập và có đơn hàng',
    'User logged in and on product page' => 'Người dùng đã đăng nhập và trên trang sản phẩm',
];
$text = str_replace(array_keys($map), array_values($map), $text);
file_put_contents($path, $text);
