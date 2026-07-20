import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, ScrollView, TextInput, TouchableOpacity, ActivityIndicator, KeyboardAvoidingView, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons, Feather } from '@expo/vector-icons';
import { COLORS, RADIUS, SPACING, TYPOGRAPHY } from '../utils/theme';
import api from '../services/api';
import useAuthStore from '../store/useAuthStore';
import { showAlert } from '../utils/alert';

export default function ContactScreen({ navigation }) {
  const user = useAuthStore((state) => state.user);
  
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [category, setCategory] = useState('Tư vấn');
  const [content, setContent] = useState('');
  const [submitting, setSubmitting] = useState(false);

  // Pre-fill user data if logged in
  useEffect(() => {
    if (user) {
      setName(user.name || user.ten || '');
      setEmail(user.email || '');
      setPhone(user.phone || user.sodienthoai || '');
    }
  }, [user]);

  const handleSubmit = async () => {
    if (!name.trim()) {
      showAlert('Lỗi', 'Vui lòng nhập họ và tên.');
      return;
    }
    if (!email.trim() || !email.includes('@')) {
      showAlert('Lỗi', 'Vui lòng nhập email hợp lệ.');
      return;
    }
    if (!content.trim()) {
      showAlert('Lỗi', 'Vui lòng nhập nội dung liên hệ.');
      return;
    }

    setSubmitting(true);
    try {
      await api.post('/lien-he', {
        hoten: name.trim(),
        email: email.trim(),
        sodienthoai: phone.trim() || null,
        noidung: content.trim(),
        danhmuc: category,
      });

      showAlert('Thành công', 'Đã gửi thông tin liên hệ thành công! Chúng tôi sẽ phản hồi sớm nhất có thể.');
      setContent('');
    } catch (err) {
      const msg = err.response?.data?.message || 'Không thể gửi liên hệ lúc này. Vui lòng thử lại sau.';
      showAlert('Lỗi', msg);
    } finally {
      setSubmitting(false);
    }
  };

  const categories = ['Tư vấn', 'Hỗ trợ kỹ thuật', 'Bảo hành', 'Góp ý'];

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* Header */}
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>❮</Text>
          <Text style={styles.backText}>Quay lại</Text>
        </TouchableOpacity>
        <Text style={styles.topTitle}>Liên hệ VinaTech</Text>
      </View>

      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          {/* Showroom Cards */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Thông tin liên hệ</Text>
            <View style={styles.infoRow}>
              <Feather name="map-pin" size={18} color={COLORS.primary} style={styles.infoIcon} />
              <View style={styles.infoTextContainer}>
                <Text style={styles.infoLabel}>Trụ sở chính</Text>
                <Text style={styles.infoValue}>120 Trần Hưng Đạo, Quận 1, TP. Hồ Chí Minh</Text>
              </View>
            </View>

            <View style={styles.infoRow}>
              <Feather name="phone" size={18} color={COLORS.primary} style={styles.infoIcon} />
              <View style={styles.infoTextContainer}>
                <Text style={styles.infoLabel}>Hotline hỗ trợ</Text>
                <Text style={styles.infoValue}>1900 6789 (8:00 - 21:00 hàng ngày)</Text>
              </View>
            </View>

            <View style={styles.infoRow}>
              <Feather name="mail" size={18} color={COLORS.primary} style={styles.infoIcon} />
              <View style={styles.infoTextContainer}>
                <Text style={styles.infoLabel}>Email chăm sóc khách hàng</Text>
                <Text style={styles.infoValue}>support@vinatech.com.vn</Text>
              </View>
            </View>
          </View>

          {/* Contact form */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Gửi lời nhắn cho chúng tôi</Text>
            
            <View style={styles.inputContainer}>
              <Text style={styles.label}>Họ và tên *</Text>
              <TextInput
                style={styles.input}
                value={name}
                onChangeText={setName}
                placeholder="Nguyễn Văn A"
                placeholderTextColor="#64748b"
              />
            </View>

            <View style={styles.inputContainer}>
              <Text style={styles.label}>Địa chỉ Email *</Text>
              <TextInput
                style={styles.input}
                value={email}
                onChangeText={setEmail}
                placeholder="email@domain.com"
                placeholderTextColor="#64748b"
                keyboardType="email-address"
                autoCapitalize="none"
              />
            </View>

            <View style={styles.inputContainer}>
              <Text style={styles.label}>Số điện thoại</Text>
              <TextInput
                style={styles.input}
                value={phone}
                onChangeText={setPhone}
                placeholder="09XXXXXXXX"
                placeholderTextColor="#64748b"
                keyboardType="phone-pad"
                maxLength={10}
              />
            </View>

            <View style={styles.inputContainer}>
              <Text style={styles.label}>Chủ đề liên hệ</Text>
              <View style={styles.categoryRow}>
                {categories.map((cat) => (
                  <TouchableOpacity
                    key={cat}
                    style={[styles.categoryBtn, category === cat && styles.categoryBtnActive]}
                    onPress={() => setCategory(cat)}
                  >
                    <Text style={[styles.categoryBtnText, category === cat && styles.categoryBtnTextActive]}>
                      {cat}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>

            <View style={styles.inputContainer}>
              <Text style={styles.label}>Nội dung tin nhắn *</Text>
              <TextInput
                style={[styles.input, styles.textArea]}
                value={content}
                onChangeText={setContent}
                placeholder="Nhập nội dung bạn muốn gửi tới VinaTech..."
                placeholderTextColor="#64748b"
                multiline
                numberOfLines={5}
              />
            </View>

            <TouchableOpacity
              style={[styles.submitBtn, submitting && { opacity: 0.7 }]}
              onPress={handleSubmit}
              disabled={submitting}
            >
              {submitting ? (
                <ActivityIndicator color="#ffffff" />
              ) : (
                <Text style={styles.submitBtnText}>Gửi thông tin liên hệ</Text>
              )}
            </TouchableOpacity>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: SPACING.lg,
    paddingVertical: SPACING.md,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.background,
  },
  backBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: SPACING.lg,
  },
  backIcon: {
    fontSize: 14,
    color: COLORS.primary,
    marginRight: SPACING.xs,
    fontWeight: '700',
  },
  backText: {
    fontSize: 14,
    color: COLORS.primary,
    fontWeight: '600',
  },
  topTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  scrollContent: {
    padding: SPACING.lg,
  },
  sectionCard: {
    backgroundColor: COLORS.surface,
    borderRadius: RADIUS.lg,
    padding: SPACING.lg,
    marginBottom: SPACING.lg,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.textPrimary,
    marginBottom: SPACING.lg,
    borderLeftWidth: 3,
    borderLeftColor: COLORS.primary,
    paddingLeft: SPACING.sm,
  },
  infoRow: {
    flexDirection: 'row',
    marginBottom: SPACING.md,
    alignItems: 'center',
  },
  infoIcon: {
    marginRight: SPACING.md,
  },
  infoTextContainer: {
    flex: 1,
  },
  infoLabel: {
    fontSize: 11,
    color: COLORS.textTertiary,
    fontWeight: '600',
    marginBottom: 2,
  },
  infoValue: {
    fontSize: 13,
    color: COLORS.textPrimary,
    fontWeight: '600',
  },
  inputContainer: {
    marginBottom: SPACING.md,
  },
  label: {
    fontSize: 12,
    fontWeight: '600',
    color: COLORS.textSecondary,
    marginBottom: SPACING.xs,
  },
  input: {
    backgroundColor: COLORS.background,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.md,
    paddingHorizontal: SPACING.lg,
    color: COLORS.textPrimary,
    fontSize: 14,
  },
  textArea: {
    minHeight: 100,
    textAlignVertical: 'top',
  },
  categoryRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: SPACING.sm,
    marginTop: SPACING.xs,
  },
  categoryBtn: {
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.background,
  },
  categoryBtnActive: {
    borderColor: COLORS.primary,
    backgroundColor: 'rgba(99, 102, 241, 0.08)',
  },
  categoryBtnText: {
    fontSize: 12,
    color: COLORS.textSecondary,
    fontWeight: '600',
  },
  categoryBtnTextActive: {
    color: COLORS.primary,
  },
  submitBtn: {
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
    paddingVertical: SPACING.md,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: SPACING.md,
  },
  submitBtnText: {
    color: COLORS.white,
    fontSize: 14,
    fontWeight: '700',
  },
});
