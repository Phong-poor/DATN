const authMessageMap = new Map([
  ['Email hoac mat khau khong dung', 'Email hoặc mật khẩu không đúng.'],
  ['Dang nhap thanh cong', 'Đăng nhập thành công.'],
  ['Dang ky thanh cong! Da gui email.', 'Đăng ký thành công! Email xác nhận đã được gửi.'],
  ['Dang ky that bai', 'Đăng ký thất bại. Vui lòng thử lại.'],
  ['Dang xuat thanh cong', 'Đăng xuất thành công.'],
  ['Ma gioi thieu khong hop le hoac da ngung hoat dong.', 'Mã giới thiệu không hợp lệ hoặc đã ngừng hoạt động.'],
  ['Server không trả token', 'Máy chủ không trả về token đăng nhập.'],
  ['Sai tài khoản hoặc mật khẩu', 'Email hoặc mật khẩu không đúng.'],
  ['Nhập email và password.', 'Vui lòng nhập email và mật khẩu.'],
  ['Lỗi đổi mật khẩu', 'Không thể đổi mật khẩu. Vui lòng thử lại.'],
  ['Có lỗi xảy ra!', 'Có lỗi xảy ra. Vui lòng thử lại.'],
  ['The email has already been taken.', 'Email này đã được sử dụng.'],
  ['The email field is required.', 'Vui lòng nhập email.'],
  ['The email field must be a valid email address.', 'Email không đúng định dạng.'],
  ['The password field is required.', 'Vui lòng nhập mật khẩu.'],
  ['The password field must be at least 6 characters.', 'Mật khẩu phải có ít nhất 6 ký tự.'],
  ['The password field confirmation does not match.', 'Xác nhận mật khẩu không khớp.'],
  ['The name field is required.', 'Vui lòng nhập họ và tên.'],
])

export function formatAuthMessage(message, fallback = 'Có lỗi xảy ra. Vui lòng thử lại.') {
  if (!message) return fallback

  const text = String(message).trim()
  return authMessageMap.get(text) || text
}
