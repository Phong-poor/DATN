const authMessageMap = new Map([
  ['Email hoac mat khau khong dung', 'Email hoặc mật khẩu không đúng.'],
  ['Email hoặc mật khẩu không đúng.', 'Email hoặc mật khẩu không đúng.'],
  ['Sai tài khoản hoặc mật khẩu', 'Email hoặc mật khẩu không đúng.'],
  ['Nhap email va password.', 'Vui lòng nhập email và mật khẩu.'],
  ['Nhập email và password.', 'Vui lòng nhập email và mật khẩu.'],

  ['Dang nhap thanh cong', 'Đăng nhập thành công.'],
  ['Đăng nhập thành công.', 'Đăng nhập thành công.'],
  ['Dang xuat thanh cong', 'Đăng xuất thành công.'],
  ['Đăng xuất thành công.', 'Đăng xuất thành công.'],

  ['Dang ky thanh cong! Da gui email.', 'Đăng ký thành công! Email xác nhận đã được gửi.'],
  ['Đăng ký thành công! Email xác nhận đã được gửi.', 'Đăng ký thành công! Email xác nhận đã được gửi.'],
  ['Dang ky that bai', 'Đăng ký thất bại. Vui lòng thử lại.'],
  ['Đăng ký thất bại. Vui lòng thử lại.', 'Đăng ký thất bại. Vui lòng thử lại.'],
  ['Ma gioi thieu khong hop le hoac da ngung hoat dong.', 'Mã giới thiệu không hợp lệ hoặc đã ngừng hoạt động.'],
  ['Mã giới thiệu không hợp lệ hoặc đã ngừng hoạt động.', 'Mã giới thiệu không hợp lệ hoặc đã ngừng hoạt động.'],

  ['Server không trả token', 'Máy chủ không trả về token đăng nhập.'],
  ['Có lỗi xảy ra!', 'Có lỗi xảy ra. Vui lòng thử lại.'],
  ['Lỗi đổi mật khẩu', 'Không thể đổi mật khẩu. Vui lòng thử lại.'],

  ['The email has already been taken.', 'Email này đã được sử dụng.'],
  ['The email field is required.', 'Vui lòng nhập email.'],
  ['The email field must be a valid email address.', 'Email không đúng định dạng.'],
  ['The name field is required.', 'Vui lòng nhập họ và tên.'],
  ['The phone field is required.', 'Vui lòng nhập số điện thoại.'],
  ['The phone field format is invalid.', 'Số điện thoại phải có 10 chữ số và bắt đầu bằng số 0.'],

  ['The password field is required.', 'Vui lòng nhập mật khẩu.'],
  ['The password field must be at least 8 characters.', 'Mật khẩu phải có ít nhất 8 ký tự.'],
  ['The password field must be at least 6 characters.', 'Mật khẩu phải có ít nhất 8 ký tự.'],
  ['Mật khẩu phải có ít nhất 6 ký tự.', 'Mật khẩu phải có ít nhất 8 ký tự.'],
  ['The password field confirmation does not match.', 'Xác nhận mật khẩu không khớp.'],
  ['The password field format is invalid.', 'Mật khẩu cần có chữ hoa, chữ thường, số và ký tự đặc biệt.'],
  ['Mật khẩu cần có chữ hoa, chữ thường, số và ký tự đặc biệt.', 'Mật khẩu cần có chữ hoa, chữ thường, số và ký tự đặc biệt.'],

  ['Mã OTP đã được gửi về email.', 'Mã OTP đã được gửi về email.'],
  ['Mã OTP hợp lệ.', 'Mã OTP hợp lệ.'],
  ['Mã OTP không đúng.', 'Mã OTP không đúng.'],
  ['Mã OTP đã hết hạn.', 'Mã OTP đã hết hạn.'],
  ['Đổi mật khẩu thành công.', 'Đổi mật khẩu thành công.'],

  ['Vui lòng xác minh bạn là con người.', 'Vui lòng xác minh bạn là con người.'],
  ['Captcha không đúng hoặc đã hết hạn.', 'Captcha không đúng hoặc đã hết hạn.'],
])

export function formatAuthMessage(message, fallback = 'Có lỗi xảy ra. Vui lòng thử lại.') {
  if (!message) return fallback

  const text = String(message).trim()
  return authMessageMap.get(text) || text
}
