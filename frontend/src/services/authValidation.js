export const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
export const vietnamPhonePattern = /^0[0-9]{9}$/

export function normalizeEmail(value) {
  return String(value || '').trim().toLowerCase()
}

export function normalizePhone(value) {
  return String(value || '').replace(/[\s.-]/g, '')
}

export function validateEmail(value) {
  const email = normalizeEmail(value)
  if (!email) return 'Vui lòng nhập email.'
  if (!emailPattern.test(email)) return 'Email không đúng định dạng.'
  return ''
}

export function validatePhone(value) {
  const phone = normalizePhone(value)
  if (!phone) return 'Vui lòng nhập số điện thoại.'
  if (!vietnamPhonePattern.test(phone)) {
    return 'Số điện thoại phải có 10 chữ số và bắt đầu bằng số 0.'
  }
  return ''
}

export function getPasswordChecks(value) {
  const password = String(value || '')
  return {
    length: password.length >= 8,
    upper: /[A-Z]/.test(password),
    lower: /[a-z]/.test(password),
    number: /[0-9]/.test(password),
    special: /[^A-Za-z0-9]/.test(password),
  }
}

export function getPasswordScore(value) {
  return Object.values(getPasswordChecks(value)).filter(Boolean).length
}

export function getPasswordStrength(value) {
  const score = getPasswordScore(value)
  if (!value) return { score, label: '', color: '#cbd5e1', width: '0%' }

  if (score <= 2) return { score, label: 'Mật khẩu yếu', color: '#ef4444', width: '40%' }
  if (score === 3) return { score, label: 'Mật khẩu trung bình', color: '#f59e0b', width: '60%' }
  if (score === 4) return { score, label: 'Mật khẩu mạnh', color: '#2563eb', width: '80%' }
  return { score, label: 'Mật khẩu rất mạnh', color: '#16a34a', width: '100%' }
}

export function getPasswordRequirements(value) {
  const checks = getPasswordChecks(value)
  return [
    { key: 'length', label: 'Ít nhất 8 ký tự', ok: checks.length },
    { key: 'upper', label: 'Có chữ hoa', ok: checks.upper },
    { key: 'lower', label: 'Có chữ thường', ok: checks.lower },
    { key: 'number', label: 'Có số', ok: checks.number },
    { key: 'special', label: 'Có ký tự đặc biệt', ok: checks.special },
  ]
}

export function validateStrongPassword(value) {
  if (!value) return 'Vui lòng nhập mật khẩu.'
  if (getPasswordScore(value) < 4) {
    return 'Mật khẩu cần ít nhất 8 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt.'
  }
  return ''
}

export function validatePasswordConfirmation(password, confirmation) {
  if (!confirmation) return 'Vui lòng xác nhận mật khẩu.'
  if (password !== confirmation) return 'Mật khẩu xác nhận không khớp.'
  return ''
}
