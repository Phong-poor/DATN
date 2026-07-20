import React, { useState, useRef, useEffect } from 'react';
import {
  StyleSheet,
  Text,
  View,
  ScrollView,
  TextInput,
  TouchableOpacity,
  KeyboardAvoidingView,
  Platform,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { COLORS, RADIUS, SPACING, TYPOGRAPHY } from '../utils/theme';
import api from '../services/api';
import logger from '../utils/logger';

export default function ChatbotScreen({ navigation }) {
  const [messages, setMessages] = useState([
    {
      id: 'welcome',
      text: 'Xin chào! Em là Trợ lý AI Nexzen. Em có thể giúp gì cho anh/chị hôm nay ạ? Anh/chị có thể hỏi em về: \n\n• Khuyến mãi và Voucher 🎁\n• Địa chỉ showroom & Hotline 📍\n• Chính sách bảo hành & Đổi trả 🛡️\n• Giao hàng hỏa tốc 🚀',
      isBot: true,
      time: new Date(),
    },
  ]);
  const [inputText, setInputText] = useState('');
  const [loading, setLoading] = useState(false);
  const scrollViewRef = useRef(null);

  const handleSend = async () => {
    if (!inputText.trim()) return;

    const userMsgText = inputText.trim();
    setInputText('');

    // Add user message to state
    const userMsg = {
      id: `user-${Date.now()}`,
      text: userMsgText,
      isBot: false,
      time: new Date(),
    };
    setMessages((prev) => [...prev, userMsg]);
    setLoading(true);

    try {
      const res = await api.post('/chat', { message: userMsgText });
      const replyText = res.data?.reply || 'Dạ, hiện tại em chưa hiểu câu hỏi của anh/chị. Anh/chị có thể chat trực tiếp với Admin hoặc gọi Hotline 1900 8080 để được hỗ trợ chuyên sâu ạ!';
      
      const botMsg = {
        id: `bot-${Date.now()}`,
        text: replyText,
        isBot: true,
        time: new Date(),
      };
      setMessages((prev) => [...prev, botMsg]);
    } catch (err) {
      logger.log('Chatbot error:', err);
      const errorMsg = {
        id: `error-${Date.now()}`,
        text: 'Kết nối máy chủ chatbot gặp gián đoạn. Anh/chị vui lòng thử lại sau giây lát nha!',
        isBot: true,
        time: new Date(),
      };
      setMessages((prev) => [...prev, errorMsg]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // Auto scroll to bottom when new messages arrive
    setTimeout(() => {
      scrollViewRef.current?.scrollToEnd({ animated: true });
    }, 100);
  }, [messages]);

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      {/* Top Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.backIcon}>❮</Text>
          <Text style={styles.backText}>Quay lại</Text>
        </TouchableOpacity>
        <View style={styles.headerTitleWrap}>
          <Text style={styles.headerTitle}>Trợ lý AI Nexzen</Text>
          <View style={styles.onlineBadge}>
            <View style={styles.onlineDot} />
            <Text style={styles.onlineText}>Online</Text>
          </View>
        </View>
      </View>

      {/* Chat Area */}
      <ScrollView
        ref={scrollViewRef}
        style={styles.chatArea}
        contentContainerStyle={styles.chatContent}
        keyboardShouldPersistTaps="handled"
      >
        {messages.map((msg) => (
          <View
            key={msg.id}
            style={[
              styles.messageRow,
              msg.isBot ? styles.botRow : styles.userRow,
            ]}
          >
            <View
              style={[
                styles.bubble,
                msg.isBot ? styles.botBubble : styles.userBubble,
              ]}
            >
              <Text
                style={[
                  styles.messageText,
                  msg.isBot ? styles.botText : styles.userText,
                ]}
              >
                {msg.text}
              </Text>
            </View>
          </View>
        ))}

        {loading && (
          <View style={[styles.messageRow, styles.botRow]}>
            <View style={[styles.bubble, styles.botBubble, styles.typingBubble]}>
              <ActivityIndicator size="small" color="#cbd5e1" />
            </View>
          </View>
        )}
      </ScrollView>

      {/* Input Area */}
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={Platform.OS === 'ios' ? 90 : 0}
      >
        <View style={styles.inputContainer}>
          <TextInput
            style={styles.input}
            value={inputText}
            onChangeText={setInputText}
            placeholder="Hỏi trợ lý AI về sản phẩm, chính sách..."
            placeholderTextColor={COLORS.textMuted}
            onSubmitEditing={handleSend}
            returnKeyType="send"
          />
          <TouchableOpacity
            style={[styles.sendBtn, !inputText.trim() && styles.disabledSendBtn]}
            onPress={handleSend}
            disabled={!inputText.trim()}
          >
            <Text style={styles.sendBtnText}>Gửi</Text>
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: SPACING.lg,
    paddingVertical: SPACING.md,
    borderBottomWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
  },
  backBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: SPACING.md,
  },
  backIcon: {
    fontSize: 14,
    color: COLORS.primary,
    marginRight: 4,
    fontWeight: '700',
  },
  backText: {
    fontSize: 14,
    color: COLORS.primary,
    fontWeight: '600',
  },
  headerTitleWrap: {
    flex: 1,
  },
  headerTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.textPrimary,
  },
  onlineBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 2,
  },
  onlineDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: COLORS.success,
    marginRight: 4,
  },
  onlineText: {
    fontSize: 10,
    color: COLORS.textTertiary,
    fontWeight: '500',
  },
  chatArea: {
    flex: 1,
  },
  chatContent: {
    padding: SPACING.lg,
    paddingBottom: SPACING.xxl,
  },
  messageRow: {
    flexDirection: 'row',
    marginBottom: SPACING.md,
    width: '100%',
  },
  botRow: {
    justifyContent: 'flex-start',
  },
  userRow: {
    justifyContent: 'flex-end',
  },
  bubble: {
    maxWidth: '80%',
    borderRadius: RADIUS.lg,
    paddingHorizontal: SPACING.md,
    paddingVertical: SPACING.sm,
  },
  botBubble: {
    backgroundColor: COLORS.surface,
    borderTopLeftRadius: 2,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  userBubble: {
    backgroundColor: COLORS.primary,
    borderTopRightRadius: 2,
  },
  typingBubble: {
    paddingVertical: SPACING.xs,
    paddingHorizontal: SPACING.sm,
    justifyContent: 'center',
    alignItems: 'center',
  },
  messageText: {
    fontSize: 14,
    lineHeight: 20,
  },
  botText: {
    color: COLORS.textSecondary,
  },
  userText: {
    color: COLORS.white,
    fontWeight: '500',
  },
  inputContainer: {
    flexDirection: 'row',
    padding: SPACING.md,
    backgroundColor: COLORS.surface,
    borderTopWidth: 1,
    borderColor: COLORS.border,
    alignItems: 'center',
  },
  input: {
    flex: 1,
    height: 40,
    backgroundColor: COLORS.background,
    borderRadius: RADIUS.md,
    paddingHorizontal: SPACING.md,
    color: COLORS.textPrimary,
    fontSize: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginRight: SPACING.md,
  },
  sendBtn: {
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
    paddingHorizontal: SPACING.lg,
    paddingVertical: 10,
    justifyContent: 'center',
    alignItems: 'center',
  },
  disabledSendBtn: {
    backgroundColor: COLORS.border,
    opacity: 0.6,
  },
  sendBtnText: {
    color: COLORS.white,
    fontSize: 14,
    fontWeight: '700',
  },
});
