import 'package:datn_mobile/core/errors/api_error.dart';
import 'package:datn_mobile/core/network/api_client.dart';
import 'package:datn_mobile/core/storage/auth_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  test('adds Sanctum bearer token to authenticated requests', () async {
    final storage = MemoryAuthStorage();
    await storage.saveToken('test-token');
    final client = ApiClient(
      storage,
      httpClient: MockClient((request) async {
        expect(request.headers['authorization'], 'Bearer test-token');
        expect(request.url.path, endsWith('/api/user/profile'));
        return http.Response('{"id":1}', 200);
      }),
    );

    final response = await client.get('user/profile', authenticated: true);

    expect(response['id'], 1);
  });

  test('clears auth data and normalizes 401 responses', () async {
    final storage = MemoryAuthStorage();
    await storage.saveToken('expired-token');
    var unauthorizedCalled = false;
    final client = ApiClient(
      storage,
      httpClient: MockClient(
        (_) async => http.Response('{"message":"Unauthenticated"}', 401),
      ),
      onUnauthorized: () async => unauthorizedCalled = true,
    );

    await expectLater(
      client.get('user/profile', authenticated: true),
      throwsA(
        isA<ApiError>().having(
          (error) => error.type,
          'type',
          ApiErrorType.unauthorized,
        ),
      ),
    );
    expect(await storage.hasToken(), isFalse);
    expect(unauthorizedCalled, isTrue);
  });
}
