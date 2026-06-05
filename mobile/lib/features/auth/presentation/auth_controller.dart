import 'package:flutter/foundation.dart';

import '../../../core/errors/api_error.dart';
import '../../../core/storage/auth_storage.dart';
import '../../../shared/models/user.dart';
import '../data/auth_service.dart';

enum AuthStatus { checking, authenticated, unauthenticated }

class AuthController extends ChangeNotifier {
  AuthController(this._authService, this._authStorage);

  final AuthService _authService;
  final AuthStorage _authStorage;

  AuthStatus status = AuthStatus.checking;
  User? user;
  String? error;
  bool busy = false;

  Future<void> checkAuth() async {
    status = AuthStatus.checking;
    error = null;
    notifyListeners();

    final token = await _authStorage.getToken();
    if (token?.isNotEmpty != true) {
      status = AuthStatus.unauthenticated;
      notifyListeners();
      return;
    }

    try {
      final cachedUser = await _authStorage.getUser();
      user = cachedUser == null ? null : User.fromJson(cachedUser);
    } catch (_) {
      user = null;
    }
    status = AuthStatus.authenticated;
    notifyListeners();
  }

  Future<bool> login({
    required String email,
    required String password,
    bool remember = true,
  }) async {
    busy = true;
    error = null;
    notifyListeners();
    try {
      user = await _authService.login(
        email: email,
        password: password,
        remember: remember,
      );
      status = AuthStatus.authenticated;
      return true;
    } catch (exception) {
      error = _message(exception);
      return false;
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  Future<bool> register({
    required String name,
    required String email,
    required String phone,
    required String password,
  }) async {
    busy = true;
    error = null;
    notifyListeners();
    try {
      await _authService.register(
        name: name,
        email: email,
        phone: phone,
        password: password,
      );
      user = await _authService.login(email: email, password: password);
      status = AuthStatus.authenticated;
      return true;
    } catch (exception) {
      error = _message(exception);
      return false;
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  Future<void> logout() async {
    busy = true;
    notifyListeners();
    try {
      await _authService.logout();
    } finally {
      user = null;
      status = AuthStatus.unauthenticated;
      busy = false;
      notifyListeners();
    }
  }

  Future<void> handleUnauthorized() async {
    await _authStorage.clearAuthData();
    user = null;
    status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  void updateUser(User value) {
    user = value;
    notifyListeners();
  }

  void clearError() {
    if (error == null) return;
    error = null;
    notifyListeners();
  }

  String _message(Object exception) {
    final message = exception is ApiError
        ? exception.message
        : exception.toString();
    return switch (message) {
      'The password field format is invalid.' =>
        'Mật khẩu cần có chữ hoa, chữ thường, số và ký tự đặc biệt.',
      'The phone field format is invalid.' =>
        'Số điện thoại phải có 10 chữ số và bắt đầu bằng số 0.',
      _ => message,
    };
  }
}
