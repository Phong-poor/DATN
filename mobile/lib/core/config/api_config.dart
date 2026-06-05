import 'package:flutter/foundation.dart';

class ApiConfig {
  const ApiConfig._();

  static const _definedServerUrl = String.fromEnvironment('API_BASE_URL');
  static const _definedAssetServerUrl = String.fromEnvironment(
    'ASSET_SERVER_URL',
  );
  static const apiPrefix = String.fromEnvironment(
    'API_PREFIX',
    defaultValue: '/api',
  );
  static const timeout = Duration(seconds: 20);

  static String get serverUrl {
    if (_definedServerUrl.trim().isNotEmpty) {
      return _trimSlash(_definedServerUrl.trim());
    }

    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8000';
    }

    return 'http://127.0.0.1:8000';
  }

  static String get baseUrl => '$serverUrl$apiPrefix';
  static String get assetServerUrl {
    if (_definedAssetServerUrl.trim().isNotEmpty) {
      return _trimSlash(_definedAssetServerUrl.trim());
    }

    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8001';
    }

    return 'http://127.0.0.1:8001';
  }

  static String get storageUrl => '$assetServerUrl/storage';

  static Map<String, String> get defaultHeaders => const {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };

  static Uri endpoint(String path, {Map<String, dynamic>? query}) {
    final cleanPath = path.replaceFirst(RegExp(r'^/+'), '');
    final uri = Uri.parse('$baseUrl/$cleanPath');
    if (query == null || query.isEmpty) return uri;

    return uri.replace(
      queryParameters: query.map(
        (key, value) => MapEntry(key, value == null ? '' : '$value'),
      ),
    );
  }

  static String assetUrl(String? path) {
    final value = path?.trim() ?? '';
    if (value.isEmpty) return '';

    final parsed = Uri.tryParse(value);
    if (parsed != null && parsed.hasScheme) {
      if (parsed.host == '127.0.0.1' || parsed.host == 'localhost') {
        final localServer = Uri.parse(assetServerUrl);
        return parsed
            .replace(host: localServer.host, port: localServer.port)
            .toString();
      }
      return value;
    }

    return '$storageUrl/${value.replaceFirst(RegExp(r'^/+'), '')}';
  }

  static String _trimSlash(String value) =>
      value.replaceFirst(RegExp(r'/+$'), '');
}
