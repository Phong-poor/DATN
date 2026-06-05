import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/cart.dart';

class CartService {
  const CartService(this._apiClient);

  final ApiClient _apiClient;

  Future<Cart> getCart() async {
    final response = toMap(
      await _apiClient.get(ApiEndpoints.cart, authenticated: true),
    );
    return Cart.fromJson(response);
  }

  Future<String> add({required int variantId, int quantity = 1}) async {
    final response = toMap(
      await _apiClient.post(
        ApiEndpoints.cartAdd,
        authenticated: true,
        body: {'id_bienthe': variantId, 'soluong': quantity},
      ),
    );
    return toText(response['message']);
  }

  Future<String> update({required int itemId, required int quantity}) async {
    final response = toMap(
      await _apiClient.put(
        ApiEndpoints.cartUpdate(itemId),
        authenticated: true,
        body: {'soluong': quantity},
      ),
    );
    return toText(response['message']);
  }

  Future<String> remove(int itemId) async {
    final response = toMap(
      await _apiClient.delete(
        ApiEndpoints.cartRemove(itemId),
        authenticated: true,
      ),
    );
    return toText(response['message']);
  }

  Future<String> clear() async {
    final response = toMap(
      await _apiClient.delete(ApiEndpoints.cartClear, authenticated: true),
    );
    return toText(response['message']);
  }
}
