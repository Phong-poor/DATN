import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, TextInput, TouchableOpacity, ScrollView, KeyboardAvoidingView, Platform, ActivityIndicator, Alert, Image } from 'react-native';
import { FontAwesome } from '@expo/vector-icons';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import useAuthStore from '../store/useAuthStore';
import logoImage from '../../assets/nextgen_logo_header.png';
import { showAlert } from '../utils/alert';

export default function RegisterScreen({ onBackToLogin }) {
  const register = useAuthStore((state) => state.register);
  const login = useAuthStore((state) => state.login);
  const loading = useAuthStore((state) => state.loading);
  const error = useAuthStore((state) => state.error);
  const clearError = useAuthStore((state) => state.clearError);

  const [ten, setTen] = useState('');
  const [email, setEmail] = useState('');
  const [sodienthoai, setSodienthoai] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [clientError, setClientError] = useState(null);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  useEffect(() => {
    clearError();
    setClientError(null);
  }, []);

  const validateForm = () => {
    setClientError(null);
    clearError();

    if (!ten.trim()) {
      setClientError('Vui lòng nhập họ và tên.');
      return false;
    }
    
    // Email regex validate
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.trim())) {
      setClientError('Email không đúng định dạng.');
      return false;
    }

    // Phone validation: must be 10 digits and start with 0
    const phoneRegex = /^0[0-9]{9}$/;
    if (!phoneRegex.test(sodienthoai.trim())) {
      setClientError('Số điện thoại phải có 10 chữ số và bắt đầu bằng số 0.');
      return false;
    }

    // Password validation: min 8 chars, uppercase, lowercase, number, special char (matches backend)
    if (password.length < 8) {
      setClientError('Mật khẩu phải có ít nhất 8 ký tự.');
      return false;
    }
    if (!/[A-Z]/.test(password)) {
      setClientError('Mật khẩu phải có ít nhất 1 chữ cái viết hoa.');
      return false;
    }
    if (!/[a-z]/.test(password)) {
      setClientError('Mật khẩu phải có ít nhất 1 chữ cái viết thường.');
      return false;
    }
    if (!/[0-9]/.test(password)) {
      setClientError('Mật khẩu phải có ít nhất 1 chữ số.');
      return false;
    }
    if (!/[^A-Za-z0-9]/.test(password)) {
      setClientError('Mật khẩu phải có ít nhất 1 ký tự đặc biệt (vd: @#$%!&*).');
      return false;
    }

    if (password !== passwordConfirmation) {
      setClientError('Xác nhận mật khẩu không khớp.');
      return false;
    }

    return true;
  };

  const handleRegister = async () => {
    if (!validateForm()) return;

    const result = await register(
      ten.trim(),
      email.trim(),
      sodienthoai.trim(),
      password,
      passwordConfirmation
    );

    if (result.success) {
      // Auto login after successful registration
      const loginResult = await login(email.trim(), password);
      if (loginResult.success) {
        showAlert(
          'Đăng ký thành công',
          'Tài khoản của bạn đã được khởi tạo và đăng nhập thành công!'
        );
      } else {
        // Fallback to manual login if auto-login fails
        showAlert(
          'Đăng ký thành công',
          'Đăng ký thành công! Vui lòng đăng nhập thủ công.',
          [{ text: 'Đăng nhập ngay', onPress: onBackToLogin }]
        );
      }
    }
  };

  const currentError = clientError || error;

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={styles.container}
    >
      <ScrollView contentContainerStyle={styles.scrollContainer} keyboardShouldPersistTaps="handled">
        <View style={styles.header}>
          <Image 
            source={logoImage} 
            style={styles.loginLogo} 
            resizeMode="contain"
          />
          <Text style={styles.logoSub}>Tạo Tài Khoản Mới</Text>
        </View>

        <View style={styles.formCard}>
          <Text style={styles.formTitle}>Đăng ký thành viên</Text>

          {currentError && (
            <View style={styles.errorBox}>
              <Text style={styles.errorBoxText}>{currentError}</Text>
            </View>
          )}

          {/* Full Name */}
          <View style={styles.inputContainer}>
            <Text style={styles.label}>Họ và tên</Text>
            <TextInput
              style={styles.input}
              value={ten}
              onChangeText={(text) => {
                setTen(text);
                setClientError(null);
                clearError();
              }}
              placeholder="Nguyễn Văn A"
              placeholderTextColor="#64748b"
              textContentType="name"
            />
          </View>

          {/* Email */}
          <View style={styles.inputContainer}>
            <Text style={styles.label}>Email</Text>
            <TextInput
              style={styles.input}
              value={email}
              onChangeText={(text) => {
                setEmail(text);
                setClientError(null);
                clearError();
              }}
              placeholder="example@email.com"
              placeholderTextColor="#64748b"
              keyboardType="email-address"
              autoCapitalize="none"
              textContentType="emailAddress"
            />
          </View>

          {/* Phone */}
          <View style={styles.inputContainer}>
            <Text style={styles.label}>Số điện thoại</Text>
            <TextInput
              style={styles.input}
              value={sodienthoai}
              onChangeText={(text) => {
                setSodienthoai(text);
                setClientError(null);
                clearError();
              }}
              placeholder="09XXXXXXXX"
              placeholderTextColor="#64748b"
              keyboardType="phone-pad"
              maxLength={10}
              textContentType="telephoneNumber"
            />
          </View>

          {/* Password */}
          <View style={styles.inputContainer}>
            <Text style={styles.label}>Mật khẩu</Text>
            <View style={styles.passwordWrapper}>
              <TextInput
                style={styles.inputPasswordField}
                value={password}
                onChangeText={(text) => {
                  setPassword(text);
                  setClientError(null);
                  clearError();
                }}
                placeholder="Tối thiểu 8 ký tự, chữ hoa, số, ký tự đặc biệt"
                placeholderTextColor="#64748b"
                secureTextEntry={!showPassword}
                autoCapitalize="none"
                textContentType="newPassword"
              />
              <TouchableOpacity style={styles.eyeBtn} onPress={() => setShowPassword(v => !v)}>
                <FontAwesome name={showPassword ? 'eye' : 'eye-slash'} size={18} color="#64748b" />
              </TouchableOpacity>
            </View>
            <Text style={styles.passwordHint}>Gồm chữ HOA, thường, số và ký tự đặc biệt (@#$%!&*...)</Text>
          </View>

          {/* Confirm Password */}
          <View style={styles.inputContainer}>
            <Text style={styles.label}>Xác nhận mật khẩu</Text>
            <View style={styles.passwordWrapper}>
              <TextInput
                style={styles.inputPasswordField}
                value={passwordConfirmation}
                onChangeText={(text) => {
                  setPasswordConfirmation(text);
                  setClientError(null);
                  clearError();
                }}
                placeholder="Nhập lại mật khẩu"
                placeholderTextColor="#64748b"
                secureTextEntry={!showConfirmPassword}
                autoCapitalize="none"
                textContentType="password"
              />
              <TouchableOpacity style={styles.eyeBtn} onPress={() => setShowConfirmPassword(v => !v)}>
                <FontAwesome name={showConfirmPassword ? 'eye' : 'eye-slash'} size={18} color="#64748b" />
              </TouchableOpacity>
            </View>
          </View>

          <TouchableOpacity
            style={[styles.registerBtn, loading && styles.disabledBtn]}
            onPress={handleRegister}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="#ffffff" />
            ) : (
              <Text style={styles.registerBtnText}>Đăng ký</Text>
            )}
          </TouchableOpacity>

          <TouchableOpacity style={styles.backToLoginBtn} onPress={onBackToLogin}>
            <Text style={styles.backToLoginText}>Đã có tài khoản? Đăng nhập ngay</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scrollContainer: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: SPACING.xxl,
    paddingTop: SPACING.xxxl,
    paddingBottom: SPACING.xxxl,
  },
  header: {
    alignItems: 'center',
    marginBottom: SPACING.xxl,
  },
  loginLogo: {
    width: 180,
    height: 48,
    marginBottom: SPACING.xs,
  },
  logoSub: {
    fontSize: 14,
    color: COLORS.textTertiary,
    marginTop: SPACING.xs,
  },
  formCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.xxl,
    borderWidth: 1,
    borderColor: COLORS.border,
    elevation: 4,
    ...Platform.select({
      ios: {
        shadowColor: '#000',
        shadowOpacity: 0.2,
        shadowOffset: { width: 0, height: 4 },
        shadowRadius: 12,
      },
      web: {
        boxShadow: '0px 4px 12px rgba(0, 0, 0, 0.2)',
      },
    }),
  },
  formTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.lg,
    textAlign: 'center',
  },
  errorBox: {
    backgroundColor: COLORS.error,
    padding: SPACING.md,
    borderRadius: RADIUS.md,
    marginBottom: SPACING.lg,
    borderWidth: 1,
    borderColor: COLORS.error,
  },
  errorBoxText: {
    color: '#fca5a5',
    fontSize: 13,
    textAlign: 'center',
  },
  inputContainer: {
    marginBottom: SPACING.lg,
  },
  label: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.textSecondary,
    marginBottom: SPACING.sm,
  },
  input: {
    backgroundColor: COLORS.background,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: SPACING.md,
    paddingHorizontal: SPACING.lg,
    color: COLORS.textPrimary,
    fontSize: 15,
  },
  passwordWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.background,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  inputPasswordField: {
    flex: 1,
    paddingVertical: SPACING.md,
    paddingHorizontal: SPACING.lg,
    color: COLORS.textPrimary,
    fontSize: 15,
  },
  eyeBtn: {
    paddingHorizontal: SPACING.md,
    paddingVertical: SPACING.md,
  },
  passwordHint: {
    fontSize: 11,
    color: COLORS.textTertiary,
    marginTop: 4,
  },
  registerBtn: {
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.md,
    alignItems: 'center',
    marginTop: SPACING.sm,
  },
  disabledBtn: {
    opacity: 0.6,
  },
  registerBtnText: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: '600',
  },
  backToLoginBtn: {
    marginTop: SPACING.lg,
    alignItems: 'center',
  },
  backToLoginText: {
    color: '#a5b4fc',
    fontSize: 13,
    fontWeight: '600',
  },
});
