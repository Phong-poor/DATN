import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/storage/auth_storage.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/user.dart';

class AuthService {
  const AuthService(this._apiClient, this._authStorage);

  final ApiClient _apiClient;
  final AuthStorage _authStorage;

  Future<User> login({
    required String email,
    required String password,
    bool remember = true,
  }) async {
    final response = toMap(
      await _apiClient.post(
        ApiEndpoints.login,
        body: {
          'email': email.trim(),
          'password': password,
          'remember': remember,
        },
      ),
    );
    final token = toText(response['token']);
    final userMap = toMap(response['user']);
    await _authStorage.saveToken(token);
    await _authStorage.saveUser(userMap);
    return User.fromJson(userMap);
  }

  Future<User> register({
    required String name,
    required String email,
    required String phone,
    required String password,
  }) async {
    final response = toMap(
      await _apiClient.post(
        ApiEndpoints.register,
        body: {
          'name': name.trim(),
          'email': email.trim(),
          'phone': phone.trim(),
          'password': password,
          'password_confirmation': password,
        },
      ),
    );
    return User.fromJson(toMap(response['user']));
  }

  Future<User> profile() async {
    final response = toMap(
      await _apiClient.get(ApiEndpoints.profile, authenticated: true),
    );
    await _authStorage.saveUser(response);
    return User.fromJson(response);
  }

  Future<void> logout() async {
    try {
      await _apiClient.post(ApiEndpoints.logout, authenticated: true);
    } finally {
      await _authStorage.clearAuthData();
    }
  }
}
