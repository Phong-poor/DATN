import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/wishlist_item.dart';

class WishlistService {
  const WishlistService(this._apiClient);

  final ApiClient _apiClient;

  Future<List<WishlistItem>> getItems() async {
    final response = toMap(
      await _apiClient.get(ApiEndpoints.wishlist, authenticated: true),
    );
    return toMapList(response['data']).map(WishlistItem.fromJson).toList();
  }

  Future<String> add({required int variantId, int quantity = 1}) async {
    final response = toMap(
      await _apiClient.post(
        ApiEndpoints.wishlistAdd,
        authenticated: true,
        body: {'id_bienthe': variantId, 'soluong': quantity},
      ),
    );
    return toText(response['message']);
  }

  Future<void> updateQuantity(int id, int quantity) async {
    await _apiClient.put(
      ApiEndpoints.wishlistUpdate(id),
      authenticated: true,
      body: {'soluong': quantity},
    );
  }

  Future<void> remove(int id) async {
    await _apiClient.delete(
      ApiEndpoints.wishlistRemove(id),
      authenticated: true,
    );
  }
}
