import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/order.dart';

class OrderService {
  const OrderService(this._apiClient);

  final ApiClient _apiClient;

  Future<List<Order>> getOrders() async {
    final response = toMap(
      await _apiClient.get(ApiEndpoints.orders, authenticated: true),
    );
    return toMapList(response['orders']).map(Order.fromJson).toList();
  }

  Future<Order?> getOrder(int id) async {
    // Backend has no GET /orders/{id}; use the existing order list endpoint.
    final orders = await getOrders();
    for (final order in orders) {
      if (order.id == id) return order;
    }
    return null;
  }

  Future<CheckoutResult> checkout({
    int? addressId,
    String? address,
    required String paymentMethod,
    required String name,
    required String phone,
    String? promoCode,
    String? freeShipCode,
  }) async {
    final response = toMap(
      await _apiClient.post(
        ApiEndpoints.checkout,
        authenticated: true,
        body: {
          'id_diachi': ?addressId,
          if (addressId == null) 'diachi': address?.trim() ?? '',
          'PTTT': paymentMethod,
          'name': name.trim(),
          'phone': phone.trim(),
          if (promoCode?.trim().isNotEmpty == true)
            'promo_code': promoCode!.trim(),
          if (freeShipCode?.trim().isNotEmpty == true)
            'freeship_code': freeShipCode!.trim(),
        },
      ),
    );
    return CheckoutResult.fromJson(response);
  }

  Future<String> cancel(int id, {String? reason}) async {
    final response = toMap(
      await _apiClient.post(
        ApiEndpoints.orderCancel(id),
        authenticated: true,
        body: {if (reason?.trim().isNotEmpty == true) 'lydo': reason!.trim()},
      ),
    );
    return toText(response['message']);
  }

  Future<String> reorder(int id) async {
    final response = toMap(
      await _apiClient.post(ApiEndpoints.orderReorder(id), authenticated: true),
    );
    return toText(response['message']);
  }
}
