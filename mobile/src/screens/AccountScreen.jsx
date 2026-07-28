import React, { useEffect, useState, useCallback, useRef, useMemo } from 'react';
import { StyleSheet, Text, View, ScrollView, TextInput, TouchableOpacity, ActivityIndicator, KeyboardAvoidingView, Platform, Alert, Image } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, RADIUS, TYPOGRAPHY, SPACING } from '../utils/theme';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import * as ImagePicker from 'expo-image-picker';
import useAuthStore from '../store/useAuthStore';
import RegisterScreen from './RegisterScreen';
import api, { getImageUrl } from '../services/api';
import logoImage from '../../assets/nextgen_logo_header.png';
import { Feather, FontAwesome } from '@expo/vector-icons';
import OptimizedImage from '../components/OptimizedImage';

export default function AccountScreen() {
  const navigation = useNavigation();
  const user = useAuthStore((state) => state.user);
  const token = useAuthStore((state) => state.token);
  const loading = useAuthStore((state) => state.loading);
  const error = useAuthStore((state) => state.error);
  const login = useAuthStore((state) => state.login);
  const logout = useAuthStore((state) => state.logout);
  const checkSession = useAuthStore((state) => state.checkSession);
  const clearError = useAuthStore((state) => state.clearError);
  const uploadAvatar = useAuthStore((state) => state.uploadAvatar);

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showLoginPassword, setShowLoginPassword] = useState(false);
  const [clientError, setClientError] = useState(null);
  const [isRegistering, setIsRegistering] = useState(false);
  const [isForgotPassword, setIsForgotPassword] = useState(false);

  // Sub-sections
  const [activeSection, setActiveSection] = useState(null); // null, 'profile', 'change_password'

  // Profile Form States
  const [profileName, setProfileName] = useState('');
  const [profilePhone, setProfilePhone] = useState('');
  const [profileEmail, setProfileEmail] = useState('');
  const [profileGender, setProfileGender] = useState('male'); // 'male' or 'female'
  const [profileBirth, setProfileBirth] = useState('');
  const [profileLoading, setProfileLoading] = useState(false);

  // Password Form States
  const [otp, setOtp] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [otpSent, setOtpSent] = useState(false);
  const [passwordLoading, setPasswordLoading] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  // Forgot password states
  const [forgotEmail, setForgotEmail] = useState('');
  const [forgotOtp, setForgotOtp] = useState('');
  const [forgotNewPassword, setForgotNewPassword] = useState('');
  const [forgotConfirmPassword, setForgotConfirmPassword] = useState('');
  const [forgotStep, setForgotStep] = useState(1);
  const [forgotLoading, setForgotLoading] = useState(false);
  const [showForgotPassword, setShowForgotPassword] = useState(false);
  const [showForgotConfirm, setShowForgotConfirm] = useState(false);

  useFocusEffect(
    useCallback(() => {
      if (token) {
        checkSession();
      }
    }, [checkSession, token])
  );

  // Clear auth store errors when transitioning screens/views
  useEffect(() => {
    clearError();
    setClientError(null);
  }, [isRegistering, isForgotPassword, forgotStep, activeSection]);

  // Pre-fill fields when user data is available
  useEffect(() => {
    if (user) {
      setProfileName(user.name || user.ten || '');
      setProfilePhone(user.sodienthoai || '');
      setProfileEmail(user.email || '');
      
      const genderMap = {
        'Nam': 'male',
        'Nữ': 'female',
      };
      setProfileGender(genderMap[user.gioitinh] || 'male');
      setProfileBirth(user.ngaysinh || '');
    }
  }, [user]);

  const handlePickAvatar = async () => {
    try {
      const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('Quyền truy cập', 'Ứng dụng cần quyền truy cập thư viện ảnh để thay đổi ảnh đại diện.');
        return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions?.Images || 'images',
        allowsEditing: true,
        aspect: [1, 1],
        quality: 0.8,
      });

      if (!result.canceled && result.assets && result.assets.length > 0) {
        const selectedUri = result.assets[0].uri;
        
        let fileToUpload = selectedUri;
        if (Platform.OS === 'web') {
          const response = await fetch(selectedUri);
          fileToUpload = await response.blob();
        }
        
        const uploadResult = await uploadAvatar(fileToUpload);
        if (uploadResult.success) {
          Alert.alert('Thành công', 'Cập nhật ảnh đại diện thành công!');
        } else {
          Alert.alert('Thất bại', uploadResult.error || 'Không thể cập nhật ảnh đại diện.');
        }
      }
    } catch (err) {
      Alert.alert('Lỗi', 'Có lỗi xảy ra khi chọn ảnh.');
    }
  };

  const validateLoginForm = () => {
    setClientError(null);
    clearError();

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.trim())) {
      setClientError('Email không đúng định dạng.');
      return false;
    }

    if (!password.trim()) {
      setClientError('Vui lòng nhập mật khẩu.');
      return false;
    }

    if (password.length < 6) {
      setClientError('Mật khẩu phải có ít nhất 6 ký tự.');
      return false;
    }

    return true;
  };

  const handleLogin = async () => {
    if (!validateLoginForm()) return;
    await login(email.trim(), password);
  };

  const handleLogout = () => {
    logout();
  };

  const handleForgotRequestOTP = async () => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(forgotEmail.trim())) {
      Alert.alert('Lỗi', 'Email không đúng định dạng.');
      return;
    }
    setForgotLoading(true);
    try {
      await api.post('/mobile/forgot-password/send-otp', { email: forgotEmail.trim() });
      setForgotStep(2);
      Alert.alert('Thành công', 'Mã OTP đã được gửi tới email của bạn (nếu email tồn tại).');
    } catch (err) {
      const msg = err.response?.data?.message || 'Gửi OTP thất bại. Kiểm tra lại email.';
      Alert.alert('Lỗi', msg);
    } finally {
      setForgotLoading(false);
    }
  };

  const handleForgotVerifyOTP = async () => {
    if (!forgotOtp.trim()) {
      Alert.alert('Lỗi', 'Vui lòng nhập mã OTP.');
      return;
    }
    if (forgotNewPassword.length < 8) {
      Alert.alert('Lỗi', 'Mật khẩu mới phải từ 8 ký tự trở lên.');
      return;
    }
    if (!/[A-Z]/.test(forgotNewPassword)) {
      Alert.alert('Lỗi', 'Mật khẩu phải có ít nhất 1 chữ hoa.');
      return;
    }
    if (!/[a-z]/.test(forgotNewPassword)) {
      Alert.alert('Lỗi', 'Mật khẩu phải có ít nhất 1 chữ thường.');
      return;
    }
    if (!/[0-9]/.test(forgotNewPassword)) {
      Alert.alert('Lỗi', 'Mật khẩu phải có ít nhất 1 chữ số.');
      return;
    }
    if (!/[^A-Za-z0-9]/.test(forgotNewPassword)) {
      Alert.alert('Lỗi', 'Mật khẩu phải có ít nhất 1 ký tự đặc biệt.');
      return;
    }
    if (forgotNewPassword !== forgotConfirmPassword) {
      Alert.alert('Lỗi', 'Xác nhận mật khẩu không khớp.');
      return;
    }
    setForgotLoading(true);
    try {
      await api.post('/mobile/forgot-password/reset-password', {
        email: forgotEmail.trim(),
        otp: forgotOtp.trim(),
        new_password: forgotNewPassword,
        new_password_confirmation: forgotConfirmPassword,
      });
      Alert.alert('Đổi mật khẩu thành công', 'Vui lòng đăng nhập lại.', [{ text: 'OK', onPress: () => {
        setIsForgotPassword(false);
        setForgotStep(1);
        setForgotEmail('');
        setForgotOtp('');
        setForgotNewPassword('');
        setForgotConfirmPassword('');
      }}]);
    } catch (err) {
      const msg = err.response?.data?.message || 'Mã OTP không đúng hoặc đã hết hạn.';
      Alert.alert('Lỗi', msg);
    } finally {
      setForgotLoading(false);
    }
  };

  const handleSaveProfile = async () => {
    if (!profileName.trim()) {
      Alert.alert('Lỗi', 'Họ tên không được để trống.');
      return;
    }
    if (profilePhone.trim()) {
      const phoneRegex = /^0[0-9]{9}$/;
      if (!phoneRegex.test(profilePhone.trim())) {
        Alert.alert('Lỗi', 'Số điện thoại phải có 10 chữ số và bắt đầu bằng số 0.');
        return;
      }
    }
    if (profileBirth.trim()) {
      const dateRegex = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/;
      if (!dateRegex.test(profileBirth.trim())) {
        Alert.alert('Lỗi', 'Ngày sinh phải đúng định dạng YYYY-MM-DD (ví dụ: 2000-01-15).');
        return;
      }
      const birthDate = new Date(profileBirth.trim());
      const today = new Date();
      if (birthDate >= today) {
        Alert.alert('Lỗi', 'Ngày sinh phải nhỏ hơn ngày hiện tại.');
        return;
      }
      const age = today.getFullYear() - birthDate.getFullYear();
      if (age > 120) {
        Alert.alert('Lỗi', 'Ngày sinh không hợp lệ.');
        return;
      }
    }
    setProfileLoading(true);
    try {
      const response = await api.put('/user/profile', {
        ten: profileName.trim(),
        email: profileEmail.trim(),
        sodienthoai: profilePhone.trim(),
        ngaysinh: profileBirth.trim() || null,
        gioitinh: profileGender, // 'male' or 'female'
      });
      if (response.data) {
        Alert.alert('Thành công', 'Cập nhật thông tin cá nhân thành công!');
        await checkSession();
        setActiveSection(null);
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Có lỗi xảy ra khi lưu thông tin.';
      Alert.alert('Lỗi', msg);
    } finally {
      setProfileLoading(false);
    }
  };

  const handleRequestOTP = async () => {
    setPasswordLoading(true);
    try {
      const response = await api.post('/user/change-password/request-otp', {
        email: user.email,
      });
      Alert.alert('Thành công', response.data.message || 'Mã OTP đã được gửi về email của bạn!');
      setOtpSent(true);
    } catch (err) {
      const msg = err.response?.data?.message || 'Gửi OTP thất bại. Vui lòng kiểm tra lại.';
      Alert.alert('Lỗi', msg);
    } finally {
      setPasswordLoading(false);
    }
  };

  const handleVerifyOTP = async () => {
    if (!otp.trim()) {
      Alert.alert('Lỗi', 'Vui lòng nhập mã OTP.');
      return;
    }
    if (newPassword.length < 8) {
      Alert.alert('Lỗi', 'Mật khẩu mới phải từ 8 ký tự trở lên.');
      return;
    }
    if (!/[A-Z]/.test(newPassword)) {
      Alert.alert('Lỗi', 'Mật khẩu phải có ít nhất 1 chữ hoa.');
      return;
    }
    if (!/[a-z]/.test(newPassword)) {
      Alert.alert('Lỗi', 'Mật khẩu phải có ít nhất 1 chữ thường.');
      return;
    }
    if (!/[0-9]/.test(newPassword)) {
      Alert.alert('Lỗi', 'Mật khẩu phải có ít nhất 1 chữ số.');
      return;
    }
    if (!/[^A-Za-z0-9]/.test(newPassword)) {
      Alert.alert('Lỗi', 'Mật khẩu phải có ít nhất 1 ký tự đặc biệt (@#$%!&*).');
      return;
    }
    if (newPassword !== confirmPassword) {
      Alert.alert('Lỗi', 'Xác nhận mật khẩu mới không khớp.');
      return;
    }
    setPasswordLoading(true);
    try {
      const response = await api.post('/user/change-password/verify-otp', {
        otp: otp.trim(),
        new_password: newPassword,
      });
      Alert.alert('Thành công', response.data.message || 'Đổi mật khẩu thành công!');
      setOtp('');
      setNewPassword('');
      setConfirmPassword('');
      setOtpSent(false);
      setActiveSection(null);
    } catch (err) {
      const msg = err.response?.data?.message || 'Mã OTP không đúng hoặc đã hết hạn.';
      Alert.alert('Lỗi', msg);
    } finally {
      setPasswordLoading(false);
    }
  };

  const menuItems = [
    { title: 'Thông tin cá nhân', icon: '👤' },
    { title: 'Danh sách yêu thích', icon: '❤️' },
    { title: 'Lịch sử đơn hàng', icon: '📦' },
    { title: 'Sổ địa chỉ', icon: '📍' },
    { title: 'Khuyến mãi & Voucher', icon: '🎁' },
    { title: 'Tiếp thị liên kết (Affiliate)', icon: '🤝' },
    { title: 'Tin tức & Blog', icon: '📰' },
    { title: 'Chat với nhân viên', iconName: 'message-circle' },
    { title: 'Liên hệ & Hỗ trợ', icon: '📞' },
    { title: 'Cài đặt tài khoản', icon: '⚙️' },
  ];

  // Render Register Screen if toggle is on
  if (isRegistering) {
    return (
      <RegisterScreen 
        onBackToLogin={() => {
          setIsRegistering(false);
          clearError();
          setClientError(null);
        }} 
      />
    );
  }

  // Render Forgot Password screen
  if (isForgotPassword) {
    return (
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={styles.container}>
        <SafeAreaView style={styles.safeArea}>
          <ScrollView contentContainerStyle={styles.loginScroll} keyboardShouldPersistTaps="handled">
            <View style={styles.loginHeader}>
              <Image source={logoImage} style={styles.loginLogo} resizeMode="contain" />
              <Text style={styles.logoSub}>Quên Mật Khẩu</Text>
            </View>

            <View style={styles.formCard}>
              <Text style={styles.formTitle}>
                {forgotStep === 1 ? 'Xác nhận Email' : 'Nhập mã OTP'}
              </Text>

              {forgotStep === 1 ? (
                <View>
                  <Text style={styles.infoText}>
                    Nhập email đăng ký của bạn. Hệ thống sẽ gửi mã OTP để đặt lại mật khẩu.
                  </Text>
                  <View style={styles.inputContainer}>
                    <Text style={styles.label}>Email đăng ký</Text>
                    <TextInput
                      style={styles.input}
                      value={forgotEmail}
                      onChangeText={setForgotEmail}
                      placeholder="example@email.com"
                      placeholderTextColor="#64748b"
                      keyboardType="email-address"
                      autoCapitalize="none"
                    />
                  </View>
                  <TouchableOpacity
                    style={[styles.loginBtn, forgotLoading && styles.disabledBtn]}
                    onPress={handleForgotRequestOTP}
                    disabled={forgotLoading}
                  >
                    {forgotLoading ? <ActivityIndicator color="#fff" /> : <Text style={styles.loginBtnText}>Gửi mã OTP</Text>}
                  </TouchableOpacity>
                </View>
              ) : (
                <View>
                  <Text style={styles.infoText}>
                    Mã OTP đã được gửi đến{' '}
                    <Text style={{ fontWeight: '700', color: COLORS.primary }}>{forgotEmail}</Text>
                  </Text>
                  <View style={styles.inputContainer}>
                    <Text style={styles.label}>Mã OTP</Text>
                    <TextInput
                      style={styles.input}
                      value={forgotOtp}
                      onChangeText={setForgotOtp}
                      placeholder="6 chữ số"
                      placeholderTextColor="#64748b"
                      keyboardType="number-pad"
                      maxLength={6}
                    />
                  </View>
                  <View style={styles.inputContainer}>
                    <Text style={styles.label}>Mật khẩu mới</Text>
                    <View style={styles.passwordWrapper}>
                      <TextInput
                        style={styles.inputPasswordField}
                        value={forgotNewPassword}
                        onChangeText={setForgotNewPassword}
                        placeholder="Tối thiểu 8 ký tự, chữ hoa, số, đặc biệt"
                        placeholderTextColor="#64748b"
                        secureTextEntry={!showForgotPassword}
                        autoCapitalize="none"
                      />
                      <TouchableOpacity style={styles.eyeBtn} onPress={() => setShowForgotPassword(v => !v)}>
                        <FontAwesome name={showForgotPassword ? 'eye' : 'eye-slash'} size={18} color="#64748b" />
                      </TouchableOpacity>
                    </View>
                    <Text style={styles.passwordHint}>Gồm chữ HOA, thường, số và ký tự đặc biệt</Text>
                  </View>
                  <View style={styles.inputContainer}>
                    <Text style={styles.label}>Xác nhận mật khẩu mới</Text>
                    <View style={styles.passwordWrapper}>
                      <TextInput
                        style={styles.inputPasswordField}
                        value={forgotConfirmPassword}
                        onChangeText={setForgotConfirmPassword}
                        placeholder="Nhập lại mật khẩu mới"
                        placeholderTextColor="#64748b"
                        secureTextEntry={!showForgotConfirm}
                        autoCapitalize="none"
                      />
                      <TouchableOpacity style={styles.eyeBtn} onPress={() => setShowForgotConfirm(v => !v)}>
                        <FontAwesome name={showForgotConfirm ? 'eye' : 'eye-slash'} size={18} color="#64748b" />
                      </TouchableOpacity>
                    </View>
                  </View>
                  <TouchableOpacity
                    style={[styles.loginBtn, forgotLoading && styles.disabledBtn]}
                    onPress={handleForgotVerifyOTP}
                    disabled={forgotLoading}
                  >
                    {forgotLoading ? <ActivityIndicator color="#fff" /> : <Text style={styles.loginBtnText}>Đổi mật khẩu</Text>}
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.registerToggleBtn} onPress={() => setForgotStep(1)}>
                    <Text style={styles.registerToggleText}>Gửi lại OTP</Text>
                  </TouchableOpacity>
                </View>
              )}

              <TouchableOpacity
                style={[styles.registerToggleBtn, { marginTop: 16 }]}
                onPress={() => { setIsForgotPassword(false); setForgotStep(1); }}
              >
                <Text style={styles.registerToggleText}>← Quay lại đăng nhập</Text>
              </TouchableOpacity>
            </View>
          </ScrollView>
        </SafeAreaView>
      </KeyboardAvoidingView>
    );
  }

  // Render Login Form if user is not authenticated
  if (!token || !user) {
    const currentError = clientError || error;

    return (
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.container}
      >
        <SafeAreaView style={styles.safeArea}>
          <ScrollView contentContainerStyle={styles.loginScroll} keyboardShouldPersistTaps="handled">
            <View style={styles.loginHeader}>
              <TouchableOpacity style={styles.loginBackBtn} onPress={() => navigation.goBack()}>
                <Text style={styles.loginBackBtnText}>❮ Quay lại trang chủ</Text>
              </TouchableOpacity>
              <Image 
                source={logoImage} 
                style={styles.loginLogo} 
                resizeMode="contain"
              />
              <Text style={styles.logoSub}>Hệ Thống Đăng Nhập Cửa Hàng</Text>
            </View>

            <View style={styles.formCard}>
              <Text style={styles.formTitle}>Đăng nhập tài khoản</Text>

              {currentError && (
                <View style={styles.errorBox}>
                  <Text style={styles.errorBoxText}>{currentError}</Text>
                </View>
              )}

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
                    placeholder="********"
                    placeholderTextColor="#64748b"
                    secureTextEntry={!showLoginPassword}
                    autoCapitalize="none"
                    textContentType="password"
                  />
                  <TouchableOpacity style={styles.eyeBtn} onPress={() => setShowLoginPassword(v => !v)}>
                    <FontAwesome name={showLoginPassword ? 'eye' : 'eye-slash'} size={18} color="#64748b" />
                  </TouchableOpacity>
                </View>
              </View>

              <TouchableOpacity 
                style={[styles.loginBtn, loading && styles.disabledBtn]} 
                onPress={handleLogin}
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator color="#ffffff" />
                ) : (
                  <Text style={styles.loginBtnText}>Đăng nhập</Text>
                )}
              </TouchableOpacity>

              <TouchableOpacity style={styles.registerToggleBtn} onPress={() => setIsRegistering(true)}>
                <Text style={styles.registerToggleText}>Chưa có tài khoản? Đăng ký ngay</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.registerToggleBtn, { marginTop: 4 }]}
                onPress={() => { setIsForgotPassword(true); setForgotEmail(email.trim()); }}
              >
                <Text style={[styles.registerToggleText, { color: '#fbbf24' }]}>🔑 Quên mật khẩu?</Text>
              </TouchableOpacity>

              <View style={styles.dividerContainer}>
                <View style={styles.dividerLine} />
                <Text style={styles.dividerText}>Hoặc đăng nhập bằng</Text>
                <View style={styles.dividerLine} />
              </View>

              <TouchableOpacity 
                style={styles.googleLoginBtn} 
                activeOpacity={0.85}
                onPress={() => {
                  Alert.alert('Đăng nhập Google', 'Tính năng đăng nhập bằng Google đang được liên kết với API.');
                }}
              >
                <FontAwesome name="google" size={20} color="#EA4335" />
                <Text style={styles.googleLoginText}>Đăng nhập với Google</Text>
              </TouchableOpacity>
            </View>
          </ScrollView>
        </SafeAreaView>
      </KeyboardAvoidingView>
    );
  }

  // Render Account Info Screen if authenticated
  return (
    <SafeAreaView style={styles.container}>
      <ScrollView keyboardShouldPersistTaps="handled">
        {activeSection === 'profile' ? (
          /* Profile Edit Section */
          <View style={styles.formContainer}>
            <View style={styles.sectionHeader}>
              <TouchableOpacity style={styles.backLink} onPress={() => setActiveSection(null)}>
                <Text style={styles.backLinkText}>❮ Quay lại</Text>
              </TouchableOpacity>
              <Text style={styles.sectionTitle}>Thông tin cá nhân</Text>
            </View>

            <View style={styles.formCard}>
              {/* Profile Photo Edit */}
              <View style={styles.avatarEditContainer}>
                <TouchableOpacity onPress={handlePickAvatar} style={styles.largeAvatarContainer}>
                  {user.anhdaidien ? (
                    <OptimizedImage 
                      source={{ uri: getImageUrl(user.anhdaidien) }} 
                      style={styles.largeAvatarImage} 
                    />
                  ) : (
                    <View style={styles.largeAvatarFallback}>
                      <Text style={styles.largeAvatarText}>
                        {user.name ? user.name.substring(0, 2).toUpperCase() : 'ML'}
                      </Text>
                    </View>
                  )}
                  <View style={styles.largeCameraOverlay}>
                    <FontAwesome name="camera" size={14} color="#ffffff" />
                  </View>
                </TouchableOpacity>
                <Text style={styles.avatarHint}>Chạm để thay đổi ảnh đại diện</Text>
              </View>

              <View style={styles.inputContainer}>
                <Text style={styles.label}>Họ và tên</Text>
                <TextInput
                  style={styles.input}
                  value={profileName}
                  onChangeText={setProfileName}
                  placeholder="Nguyễn Văn A"
                  placeholderTextColor="#64748b"
                />
              </View>

              <View style={styles.inputContainer}>
                <Text style={styles.label}>Email</Text>
                <TextInput
                  style={[styles.input, styles.disabledInput]}
                  value={profileEmail}
                  editable={false}
                  placeholder="example@email.com"
                  placeholderTextColor="#64748b"
                />
              </View>

              <View style={styles.inputContainer}>
                <Text style={styles.label}>Số điện thoại</Text>
                <TextInput
                  style={styles.input}
                  value={profilePhone}
                  onChangeText={setProfilePhone}
                  placeholder="09XXXXXXXX"
                  placeholderTextColor="#64748b"
                  keyboardType="phone-pad"
                />
              </View>

              <View style={styles.inputContainer}>
                <Text style={styles.label}>Giới tính</Text>
                <View style={styles.genderRow}>
                  <TouchableOpacity
                    style={[styles.genderBtn, profileGender === 'male' && styles.activeGenderBtn]}
                    onPress={() => setProfileGender('male')}
                  >
                    <Text style={[styles.genderText, profileGender === 'male' && styles.activeGenderText]}>Nam</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={[styles.genderBtn, profileGender === 'female' && styles.activeGenderBtn]}
                    onPress={() => setProfileGender('female')}
                  >
                    <Text style={[styles.genderText, profileGender === 'female' && styles.activeGenderText]}>Nữ</Text>
                  </TouchableOpacity>
                </View>
              </View>

              <View style={styles.inputContainer}>
                <Text style={styles.label}>Ngày sinh (YYYY-MM-DD)</Text>
                <TextInput
                  style={styles.input}
                  value={profileBirth}
                  onChangeText={setProfileBirth}
                  placeholder="2000-01-01"
                  placeholderTextColor="#64748b"
                />
              </View>

              <TouchableOpacity 
                style={[styles.loginBtn, profileLoading && styles.disabledBtn]} 
                onPress={handleSaveProfile}
                disabled={profileLoading}
              >
                {profileLoading ? (
                  <ActivityIndicator color="#ffffff" />
                ) : (
                  <Text style={styles.loginBtnText}>Lưu thông tin</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        ) : activeSection === 'change_password' ? (
          /* Change Password Section */
          <View style={styles.formContainer}>
            <View style={styles.sectionHeader}>
              <TouchableOpacity style={styles.backLink} onPress={() => {
                setActiveSection(null);
                setOtpSent(false);
              }}>
                <Text style={styles.backLinkText}>❮ Quay lại</Text>
              </TouchableOpacity>
              <Text style={styles.sectionTitle}>Đổi mật khẩu</Text>
            </View>

            <View style={styles.formCard}>
              {!otpSent ? (
                /* Step 1: Send OTP */
                <View>
                  <Text style={styles.infoText}>
                    Để bảo mật, hệ thống sẽ gửi một mã OTP gồm 6 chữ số đến địa chỉ email đăng ký của bạn:
                    {"\n"}{"\n"}
                    <Text style={{fontWeight: '700', color: COLORS.primary}}>{user?.email}</Text>
                  </Text>
                  
                  <TouchableOpacity 
                    style={[styles.loginBtn, passwordLoading && styles.disabledBtn]} 
                    onPress={handleRequestOTP}
                    disabled={passwordLoading}
                  >
                    {passwordLoading ? (
                      <ActivityIndicator color="#ffffff" />
                    ) : (
                      <Text style={styles.loginBtnText}>Gửi mã OTP</Text>
                    )}
                  </TouchableOpacity>
                </View>
              ) : (
                /* Step 2: Input OTP and New Password */
                <View>
                  <View style={styles.inputContainer}>
                    <Text style={styles.label}>Nhập mã OTP</Text>
                    <TextInput
                      style={styles.input}
                      value={otp}
                      onChangeText={setOtp}
                      placeholder="XXXXXX"
                      placeholderTextColor="#64748b"
                      keyboardType="number-pad"
                      maxLength={6}
                    />
                  </View>

                  <View style={styles.inputContainer}>
                    <Text style={styles.label}>Mật khẩu mới</Text>
                    <View style={styles.passwordWrapper}>
                      <TextInput
                        style={styles.inputPasswordField}
                        value={newPassword}
                        onChangeText={setNewPassword}
                        placeholder="Tối thiểu 8 ký tự, chữ hoa, số, đặc biệt"
                        placeholderTextColor="#64748b"
                        secureTextEntry={!showNewPassword}
                        autoCapitalize="none"
                      />
                      <TouchableOpacity style={styles.eyeBtn} onPress={() => setShowNewPassword(v => !v)}>
                        <FontAwesome name={showNewPassword ? 'eye' : 'eye-slash'} size={18} color="#64748b" />
                      </TouchableOpacity>
                    </View>
                    <Text style={styles.passwordHint}>Gồm chữ HOA, thường, số và ký tự đặc biệt (@#$%!&*...)</Text>
                  </View>

                  <View style={styles.inputContainer}>
                    <Text style={styles.label}>Xác nhận mật khẩu mới</Text>
                    <View style={styles.passwordWrapper}>
                      <TextInput
                        style={styles.inputPasswordField}
                        value={confirmPassword}
                        onChangeText={setConfirmPassword}
                        placeholder="Nhập lại mật khẩu mới"
                        placeholderTextColor="#64748b"
                        secureTextEntry={!showConfirmPassword}
                        autoCapitalize="none"
                      />
                      <TouchableOpacity style={styles.eyeBtn} onPress={() => setShowConfirmPassword(v => !v)}>
                        <FontAwesome name={showConfirmPassword ? 'eye' : 'eye-slash'} size={18} color="#64748b" />
                      </TouchableOpacity>
                    </View>
                  </View>

                  <TouchableOpacity 
                    style={[styles.loginBtn, passwordLoading && styles.disabledBtn]} 
                    onPress={handleVerifyOTP}
                    disabled={passwordLoading}
                  >
                    {passwordLoading ? (
                      <ActivityIndicator color="#ffffff" />
                    ) : (
                      <Text style={styles.loginBtnText}>Đổi mật khẩu</Text>
                    )}
                  </TouchableOpacity>
                  
                  <TouchableOpacity 
                    style={styles.registerToggleBtn} 
                    onPress={() => setOtpSent(false)}
                  >
                    <Text style={styles.registerToggleText}>Gửi lại mã OTP khác</Text>
                  </TouchableOpacity>
                </View>
              )}
            </View>
          </View>
        ) : (
          /* Default Account Menu View */
          <View>
            {/* Account Header with Back Button */}
            <View style={styles.accountHeader}>
              <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
                <Text style={styles.backBtnText}>❮</Text>
              </TouchableOpacity>
              <Text style={styles.accountTitle}>Tài khoản của tôi</Text>
            </View>

            {/* Profile Card */}
            <View style={styles.profileCard}>
              <TouchableOpacity onPress={handlePickAvatar} style={styles.avatarContainer}>
                {user.anhdaidien ? (
                  <OptimizedImage 
                    source={{ uri: getImageUrl(user.anhdaidien) }} 
                    style={styles.avatarImage} 
                  />
                ) : (
                  <View style={styles.avatar}>
                    <Text style={styles.avatarText}>
                      {user.name ? user.name.substring(0, 2).toUpperCase() : 'ML'}
                    </Text>
                  </View>
                )}
                <View style={styles.cameraOverlay}>
                  <FontAwesome name="camera" size={10} color="#ffffff" />
                </View>
              </TouchableOpacity>
              <View style={styles.userInfo}>
                <Text style={styles.userName}>{user.name || 'Người dùng'}</Text>
                <Text style={styles.userEmail}>{user.email || 'No email'}</Text>
                <View style={styles.roleBadge}>
                  <Text style={styles.roleText}>{user.role || 'Thành viên'}</Text>
                </View>
              </View>
            </View>

            {/* Menu list */}
            <View style={styles.menuContainer}>
              {menuItems.map((item, idx) => {
                const handlePress = () => {
                  if (item.title === 'Lịch sử đơn hàng') {
                    navigation.navigate('OrderHistory');
                  } else if (item.title === 'Danh sách yêu thích') {
                    navigation.navigate('Yêu thích');
                  } else if (item.title === 'Thông tin cá nhân') {
                    setActiveSection('profile');
                  } else if (item.title === 'Cài đặt tài khoản') {
                    setActiveSection('change_password');
                  } else if (item.title === 'Sổ địa chỉ') {
                    navigation.navigate('Address');
                  } else if (item.title === 'Khuyến mãi & Voucher') {
                    navigation.navigate('Promotion');
                  } else if (item.title === 'Tiếp thị liên kết (Affiliate)') {
                    navigation.navigate('Affiliate');
                  } else if (item.title === 'Tin tức & Blog') {
                    navigation.navigate('NewsList');
                  } else if (item.title === 'Chat với nhân viên') {
                    navigation.navigate('SupportChat');
                  } else if (item.title === 'Liên hệ & Hỗ trợ') {
                    navigation.navigate('Contact');
                  } else {
                    Alert.alert('Thông báo', `Chức năng "${item.title}" đang được hoàn thiện!`);
                  }
                };
                return (
                  <TouchableOpacity key={idx} style={styles.menuItem} onPress={handlePress}>
                    <View style={styles.menuLeft}>
                      {item.iconName ? (
                        <Feather name={item.iconName} size={22} color={COLORS.primaryLight} style={styles.menuIcon} />
                      ) : (
                        <Text style={styles.menuIcon}>{item.icon}</Text>
                      )}
                      <Text style={styles.menuTitle}>{item.title}</Text>
                    </View>
                    <Text style={styles.menuArrow}>❯</Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            {/* Logout Button */}
            <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
              <Text style={styles.logoutBtnText}>Đăng xuất</Text>
            </TouchableOpacity>

            {/* App Version */}
            <Text style={styles.versionText}>Phiên bản 1.0.0 (Expo + Zustand)</Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  safeArea: {
    flex: 1,
  },
  loginScroll: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: SPACING.xxl,
  },
  loginHeader: {
    alignItems: 'center',
    marginBottom: SPACING.xxxl,
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
  infoText: {
    fontSize: 14,
    color: COLORS.textSecondary,
    lineHeight: 22,
    marginBottom: SPACING.lg,
  },
  disabledInput: {
    backgroundColor: COLORS.border,
    color: COLORS.textTertiary,
  },
  loginBtn: {
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.md,
    alignItems: 'center',
    marginTop: SPACING.sm,
  },
  disabledBtn: {
    opacity: 0.6,
  },
  loginBtnText: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: '600',
  },
  registerToggleBtn: {
    marginTop: SPACING.lg,
    alignItems: 'center',
  },
  registerToggleText: {
    color: '#a5b4fc',
    fontSize: 13,
    fontWeight: '600',
  },
  dividerContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: SPACING.xl,
  },
  dividerLine: {
    flex: 1,
    height: 1,
    backgroundColor: COLORS.border,
  },
  dividerText: {
    marginHorizontal: SPACING.md,
    color: COLORS.textTertiary,
    fontSize: 12,
  },
  googleLoginBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.white,
    paddingVertical: 12,
    borderRadius: RADIUS.md,
    shadowColor: '#000',
    shadowOpacity: 0.1,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 4,
    elevation: 2,
    marginTop: SPACING.md,
  },
  googleLoginText: {
    color: '#1e293b',
    fontSize: 14,
    fontWeight: '700',
    marginLeft: SPACING.sm,
  },
  profileCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: SPACING.xxl,
    backgroundColor: COLORS.surface,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
  },
  avatar: {
    width: 68,
    height: 68,
    borderRadius: 34,
    backgroundColor: COLORS.primary,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: SPACING.sm,
  },
  avatarText: {
    color: COLORS.white,
    fontSize: 22,
    fontWeight: '800',
  },
  userInfo: {
    flex: 1,
  },
  userName: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.xs,
  },
  userEmail: {
    fontSize: 14,
    color: COLORS.textTertiary,
    marginBottom: SPACING.xs,
  },
  roleBadge: {
    alignSelf: 'flex-start',
    backgroundColor: '#312e81',
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.sm,
    borderRadius: RADIUS.sm,
  },
  roleText: {
    color: '#a5b4fc',
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  menuContainer: {
    marginTop: SPACING.lg,
    backgroundColor: COLORS.surface,
    borderTopWidth: 1,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: SPACING.lg,
    paddingHorizontal: SPACING.lg,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
  },
  menuLeft: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  menuIcon: {
    fontSize: 18,
    marginRight: SPACING.md,
  },
  menuTitle: {
    fontSize: 15,
    color: COLORS.textSecondary,
    fontWeight: '500',
  },
  menuArrow: {
    fontSize: 12,
    color: COLORS.textTertiary,
  },
  logoutBtn: {
    marginHorizontal: SPACING.lg,
    marginTop: SPACING.xxxl,
    backgroundColor: COLORS.error,
    borderRadius: RADIUS.lg,
    paddingVertical: SPACING.md,
    alignItems: 'center',
  },
  logoutBtnText: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: '600',
  },
  versionText: {
    textAlign: 'center',
    color: COLORS.textTertiary,
    fontSize: 11,
    marginVertical: SPACING.xxl,
  },
  formContainer: {
    padding: SPACING.lg,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: SPACING.xl,
    marginTop: SPACING.md,
  },
  backLink: {
    marginRight: SPACING.lg,
  },
  backLinkText: {
    color: COLORS.primary,
    fontWeight: '700',
    fontSize: 14,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  genderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  genderBtn: {
    flex: 1,
    backgroundColor: COLORS.background,
    borderColor: COLORS.border,
    borderWidth: 1,
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.md,
    alignItems: 'center',
    marginHorizontal: SPACING.xs,
  },
  activeGenderBtn: {
    borderColor: COLORS.primary,
    backgroundColor: 'rgba(99, 102, 241, 0.1)',
  },
  genderText: {
    color: COLORS.textSecondary,
    fontWeight: '600',
  },
  activeGenderText: {
    color: COLORS.primary,
  },
  accountHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: SPACING.lg,
    paddingVertical: SPACING.md,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
  },
  accountTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  backBtn: {
    marginRight: SPACING.md,
    padding: SPACING.xs,
  },
  backBtnText: {
    fontSize: 18,
    color: COLORS.textPrimary,
  },
  loginBackBtn: {
    alignSelf: 'flex-start',
    marginBottom: SPACING.md,
    paddingVertical: SPACING.xs,
  },
  loginBackBtnText: {
    color: COLORS.primary,
    fontWeight: '600',
    fontSize: 14,
  },
  avatarContainer: {
    position: 'relative',
    marginRight: SPACING.md,
  },
  avatarImage: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: 'rgba(99, 102, 241, 0.1)',
  },
  cameraOverlay: {
    position: 'absolute',
    bottom: -2,
    right: -2,
    backgroundColor: COLORS.primary,
    width: 20,
    height: 20,
    borderRadius: 10,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1.5,
    borderColor: '#ffffff',
  },
  avatarEditContainer: {
    alignItems: 'center',
    marginBottom: SPACING.lg,
    paddingBottom: SPACING.md,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
  },
  largeAvatarContainer: {
    position: 'relative',
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(99, 102, 241, 0.1)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: SPACING.sm,
  },
  largeAvatarImage: {
    width: 100,
    height: 100,
    borderRadius: 50,
  },
  largeAvatarFallback: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(99, 102, 241, 0.1)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  largeAvatarText: {
    fontSize: 32,
    fontWeight: '700',
    color: COLORS.primary,
  },
  largeCameraOverlay: {
    position: 'absolute',
    bottom: 2,
    right: 2,
    backgroundColor: COLORS.primary,
    width: 28,
    height: 28,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#ffffff',
  },
  avatarHint: {
    fontSize: 12,
    color: COLORS.textTertiary,
  },
});
