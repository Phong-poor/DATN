import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../errors/api_error.dart';
import '../storage/auth_storage.dart';

typedef UnauthorizedCallback = Future<void> Function();

class ApiClient {
  ApiClient(
    this._authStorage, {
    http.Client? httpClient,
    UnauthorizedCallback? onUnauthorized,
  }) : _httpClient = httpClient ?? http.Client() {
    _onUnauthorized = onUnauthorized;
  }

  final AuthStorage _authStorage;
  final http.Client _httpClient;
  UnauthorizedCallback? _onUnauthorized;

  set onUnauthorized(UnauthorizedCallback? callback) =>
      _onUnauthorized = callback;

  Future<dynamic> get(
    String path, {
    Map<String, dynamic>? query,
    bool authenticated = false,
  }) => _request('GET', path, query: query, authenticated: authenticated);

  Future<dynamic> post(
    String path, {
    Map<String, dynamic>? body,
    bool authenticated = false,
  }) => _request('POST', path, body: body, authenticated: authenticated);

  Future<dynamic> put(
    String path, {
    Map<String, dynamic>? body,
    bool authenticated = false,
  }) => _request('PUT', path, body: body, authenticated: authenticated);

  Future<dynamic> patch(
    String path, {
    Map<String, dynamic>? body,
    bool authenticated = false,
  }) => _request('PATCH', path, body: body, authenticated: authenticated);

  Future<dynamic> delete(String path, {bool authenticated = false}) =>
      _request('DELETE', path, authenticated: authenticated);

  Future<dynamic> _request(
    String method,
    String path, {
    Map<String, dynamic>? query,
    Map<String, dynamic>? body,
    required bool authenticated,
  }) async {
    final token = authenticated ? await _authStorage.getToken() : null;
    final headers = <String, String>{
      ...ApiConfig.defaultHeaders,
      if (token?.isNotEmpty == true) 'Authorization': 'Bearer $token',
    };
    final encodedBody = body == null ? null : jsonEncode(body);

    try {
      late http.Response response;
      final uri = ApiConfig.endpoint(path, query: query);
      switch (method) {
        case 'POST':
          response = await _httpClient
              .post(uri, headers: headers, body: encodedBody)
              .timeout(ApiConfig.timeout);
          break;
        case 'PUT':
          response = await _httpClient
              .put(uri, headers: headers, body: encodedBody)
              .timeout(ApiConfig.timeout);
          break;
        case 'PATCH':
          response = await _httpClient
              .patch(uri, headers: headers, body: encodedBody)
              .timeout(ApiConfig.timeout);
          break;
        case 'DELETE':
          response = await _httpClient
              .delete(uri, headers: headers)
              .timeout(ApiConfig.timeout);
          break;
        default:
          response = await _httpClient
              .get(uri, headers: headers)
              .timeout(ApiConfig.timeout);
      }

      final data = _decode(response.body);
      if (response.statusCode >= 200 && response.statusCode < 300) return data;

      final error = _toApiError(response.statusCode, data);
      if (authenticated && response.statusCode == 401) {
        await _authStorage.clearAuthData();
        await _onUnauthorized?.call();
      }
      throw error;
    } on ApiError {
      rethrow;
    } on TimeoutException {
      throw const ApiError(
        message: 'Máy chủ phản hồi quá lâu. Vui lòng thử lại.',
        type: ApiErrorType.timeout,
      );
    } on SocketException {
      throw const ApiError(
        message: 'Không thể kết nối tới máy chủ Laravel.',
        type: ApiErrorType.network,
      );
    } on http.ClientException {
      throw const ApiError(
        message: 'Kết nối mạng bị lỗi.',
        type: ApiErrorType.network,
      );
    } catch (_) {
      throw const ApiError(
        message: 'Đã xảy ra lỗi không xác định.',
        type: ApiErrorType.unknown,
      );
    }
  }

  dynamic _decode(String body) {
    if (body.trim().isEmpty) return null;
    try {
      return jsonDecode(body);
    } catch (_) {
      return body;
    }
  }

  ApiError _toApiError(int statusCode, dynamic data) {
    final map = data is Map ? Map<String, dynamic>.from(data) : null;
    final fieldErrors = <String, List<String>>{};
    final rawErrors = map?['errors'];
    if (rawErrors is Map) {
      for (final entry in rawErrors.entries) {
        final value = entry.value;
        fieldErrors['${entry.key}'] = value is List
            ? value.map((item) => '$item').toList()
            : ['$value'];
      }
    }

    var message = '${map?['message'] ?? map?['error'] ?? 'Yêu cầu thất bại.'}';
    if (fieldErrors.isNotEmpty) message = fieldErrors.values.first.first;

    final type = switch (statusCode) {
      401 => ApiErrorType.unauthorized,
      403 => ApiErrorType.forbidden,
      404 => ApiErrorType.notFound,
      422 => ApiErrorType.validation,
      >= 500 => ApiErrorType.server,
      _ => ApiErrorType.unknown,
    };

    return ApiError(
      message: message,
      type: type,
      statusCode: statusCode,
      fieldErrors: fieldErrors,
    );
  }
}
