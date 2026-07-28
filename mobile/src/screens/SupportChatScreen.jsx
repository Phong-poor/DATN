import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Feather } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import api from '../services/api';
import useAuthStore from '../store/useAuthStore';
import { COLORS, RADIUS, SPACING } from '../utils/theme';
import logger from '../utils/logger';

export default function SupportChatScreen({ navigation }) {
  const user = useAuthStore((state) => state.user);
  const token = useAuthStore((state) => state.token);
  const [conversationId, setConversationId] = useState(null);
  const [messages, setMessages] = useState([]);
  const [text, setText] = useState('');
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState('');
  const listRef = useRef(null);

  const currentUserId = user?.id ?? user?.id_nguoidung ?? user?.id_user;

  const loadConversation = useCallback(async (silent = false) => {
    if (!token) {
      setLoading(false);
      return;
    }

    if (!silent) setLoading(true);
    try {
      const response = await api.get('/chat/me');
      setConversationId(response.data?.id ?? null);
      setMessages(Array.isArray(response.data?.messages) ? response.data.messages : []);
      setError('');
    } catch (err) {
      logger.log('Failed to load support conversation:', err);
      if (!silent) setError(err.response?.data?.message || 'Không thể tải cuộc trò chuyện.');
    } finally {
      if (!silent) setLoading(false);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      loadConversation();
      const interval = setInterval(() => loadConversation(true), 5000);
      return () => clearInterval(interval);
    }, [loadConversation])
  );

  useEffect(() => {
    if (messages.length > 0) {
      requestAnimationFrame(() => listRef.current?.scrollToEnd({ animated: true }));
    }
  }, [messages.length]);

  const sendMessage = async () => {
    const content = text.trim();
    if (!content || sending) return;

    setSending(true);
    setText('');
    setError('');
    try {
      const response = await api.post('/chat/send', {
        noidung: content,
        id_cuoc_tro_chuyen: conversationId,
      });
      const sent = response.data?.message;
      if (sent && !Array.isArray(sent)) {
        setMessages((current) => [...current.filter((item) => item.id !== sent.id), sent]);
      } else {
        await loadConversation(true);
      }
    } catch (err) {
      logger.log('Failed to send support message:', err);
      setText(content);
      setError(err.response?.data?.message || 'Gửi tin nhắn thất bại. Vui lòng thử lại.');
    } finally {
      setSending(false);
    }
  };

  const renderMessage = ({ item }) => {
    const isMine = String(item.id_nguoigui) === String(currentUserId);
    const senderName = item.sender?.ten || item.sender?.name || (isMine ? 'Bạn' : 'Nhân viên hỗ trợ');
    const sentAt = item.created_at
      ? new Date(item.created_at).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
      : '';

    return (
      <View style={[styles.messageRow, isMine ? styles.mineRow : styles.supportRow]}>
        <View style={[styles.bubble, isMine ? styles.mineBubble : styles.supportBubble]}>
          {!isMine && <Text style={styles.sender}>{senderName}</Text>}
          <Text style={styles.messageText}>{item.noidung}</Text>
          <Text style={[styles.time, isMine && styles.mineTime]}>{sentAt}</Text>
        </View>
      </View>
    );
  };

  if (!token) {
    return (
      <SafeAreaView style={styles.container} edges={['top']}>
        <Header navigation={navigation} />
        <View style={styles.centerState}>
          <Feather name="message-circle" size={52} color={COLORS.textTertiary} />
          <Text style={styles.stateTitle}>Bạn cần đăng nhập</Text>
          <Text style={styles.stateText}>Đăng nhập để trò chuyện trực tiếp với nhân viên hỗ trợ.</Text>
          <TouchableOpacity
            style={styles.loginButton}
            onPress={() => navigation.navigate('Main', { screen: 'Tài khoản' })}
          >
            <Text style={styles.loginButtonText}>Đến trang đăng nhập</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <Header navigation={navigation} />
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        keyboardVerticalOffset={Platform.OS === 'ios' ? 8 : 0}
      >
        {loading ? (
          <View style={styles.centerState}>
            <ActivityIndicator color={COLORS.primary} />
            <Text style={styles.stateText}>Đang tải cuộc trò chuyện...</Text>
          </View>
        ) : (
          <FlatList
            ref={listRef}
            data={messages}
            keyExtractor={(item, index) => String(item.id ?? `message-${index}`)}
            renderItem={renderMessage}
            contentContainerStyle={styles.messageList}
            onContentSizeChange={() => listRef.current?.scrollToEnd({ animated: false })}
            ListEmptyComponent={
              <View style={styles.emptyState}>
                <Feather name="headphones" size={44} color={COLORS.primaryLight} />
                <Text style={styles.stateTitle}>Hỗ trợ trực tuyến</Text>
                <Text style={styles.stateText}>Hãy gửi câu hỏi, nhân viên sẽ phản hồi ngay tại đây.</Text>
              </View>
            }
          />
        )}

        {!!error && <Text style={styles.errorText}>{error}</Text>}

        <View style={styles.composer}>
          <TextInput
            style={styles.input}
            value={text}
            onChangeText={setText}
            placeholder="Nhập tin nhắn..."
            placeholderTextColor={COLORS.textMuted}
            multiline
            maxLength={5000}
          />
          <TouchableOpacity
            style={[styles.sendButton, (!text.trim() || sending) && styles.disabledButton]}
            onPress={sendMessage}
            disabled={!text.trim() || sending}
            accessibilityLabel="Gửi tin nhắn"
          >
            {sending
              ? <ActivityIndicator size="small" color={COLORS.white} />
              : <Feather name="send" size={20} color={COLORS.white} />}
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

function Header({ navigation }) {
  return (
    <View style={styles.header}>
      <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()} accessibilityLabel="Quay lại">
        <Feather name="chevron-left" size={24} color={COLORS.primaryLight} />
      </TouchableOpacity>
      <View style={styles.headerCopy}>
        <Text style={styles.headerTitle}>Chat với nhân viên</Text>
        <Text style={styles.headerSubtitle}>Hỗ trợ khách hàng</Text>
      </View>
      <View style={styles.onlineDot} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.background },
  flex: { flex: 1 },
  header: {
    minHeight: 62,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: SPACING.md,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
    backgroundColor: COLORS.surface,
  },
  backButton: { padding: SPACING.sm },
  headerCopy: { flex: 1, marginLeft: SPACING.xs },
  headerTitle: { color: COLORS.textPrimary, fontSize: 16, fontWeight: '700' },
  headerSubtitle: { color: COLORS.textTertiary, fontSize: 11, marginTop: 2 },
  onlineDot: { width: 9, height: 9, borderRadius: 5, backgroundColor: COLORS.success, marginRight: SPACING.md },
  centerState: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: SPACING.xxl, gap: SPACING.md },
  emptyState: { flex: 1, minHeight: 360, justifyContent: 'center', alignItems: 'center', padding: SPACING.xxl, gap: SPACING.md },
  stateTitle: { color: COLORS.textPrimary, fontSize: 18, fontWeight: '700', textAlign: 'center' },
  stateText: { color: COLORS.textTertiary, fontSize: 13, lineHeight: 20, textAlign: 'center' },
  loginButton: { backgroundColor: COLORS.primary, borderRadius: RADIUS.md, paddingHorizontal: SPACING.xl, paddingVertical: SPACING.md },
  loginButtonText: { color: COLORS.white, fontWeight: '700' },
  messageList: { flexGrow: 1, padding: SPACING.lg, paddingBottom: SPACING.xxl },
  messageRow: { flexDirection: 'row', marginBottom: SPACING.md },
  mineRow: { justifyContent: 'flex-end' },
  supportRow: { justifyContent: 'flex-start' },
  bubble: { maxWidth: '82%', borderRadius: RADIUS.lg, paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm },
  mineBubble: { backgroundColor: COLORS.primary, borderBottomRightRadius: 3 },
  supportBubble: { backgroundColor: COLORS.surfaceLight, borderBottomLeftRadius: 3 },
  sender: { color: COLORS.primaryLight, fontSize: 11, fontWeight: '700', marginBottom: 4 },
  messageText: { color: COLORS.textPrimary, fontSize: 14, lineHeight: 20 },
  time: { color: COLORS.textTertiary, fontSize: 9, marginTop: 4, alignSelf: 'flex-end' },
  mineTime: { color: 'rgba(255,255,255,0.72)' },
  errorText: { color: COLORS.error, fontSize: 12, textAlign: 'center', paddingHorizontal: SPACING.lg, paddingBottom: SPACING.xs },
  composer: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: SPACING.sm,
    padding: SPACING.md,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
    backgroundColor: COLORS.surface,
  },
  input: {
    flex: 1,
    minHeight: 44,
    maxHeight: 110,
    color: COLORS.textPrimary,
    backgroundColor: COLORS.background,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.full,
    paddingHorizontal: SPACING.lg,
    paddingTop: Platform.OS === 'ios' ? 12 : 9,
    paddingBottom: Platform.OS === 'ios' ? 12 : 9,
  },
  sendButton: { width: 44, height: 44, borderRadius: 22, backgroundColor: COLORS.primary, justifyContent: 'center', alignItems: 'center' },
  disabledButton: { opacity: 0.45 },
});
