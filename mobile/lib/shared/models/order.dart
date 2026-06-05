import '../../core/config/api_config.dart';
import '../../core/utils/parsers.dart';

class CheckoutResult {
  const CheckoutResult({required this.order, required this.paymentUrl});

  factory CheckoutResult.fromJson(Map<String, dynamic> json) => CheckoutResult(
    order: Order.fromJson(toMap(json['order'])),
    paymentUrl: toText(json['payUrl']),
  );

  final Order order;
  final String paymentUrl;
}

class OrderItem {
  const OrderItem({
    required this.variantId,
    required this.productName,
    required this.variantName,
    required this.imageUrl,
    required this.quantity,
    required this.price,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    final variant = toMap(json['bien_the'] ?? json['bienThe']);
    final product = toMap(variant['san_pham'] ?? variant['sanPham']);
    return OrderItem(
      variantId: toInt(json['id_bienthe']),
      productName: toText(product['tenSP']),
      variantName: toText(variant['ten_bienthe']),
      imageUrl: ApiConfig.assetUrl(toText(product['hinhanh'])),
      quantity: toInt(json['soluong']),
      price: toDouble(json['gia']),
    );
  }

  final int variantId;
  final String productName;
  final String variantName;
  final String imageUrl;
  final int quantity;
  final double price;
}

class Order {
  const Order({
    required this.id,
    required this.total,
    required this.status,
    required this.address,
    required this.paymentMethod,
    required this.createdAt,
    required this.items,
  });

  factory Order.fromJson(Map<String, dynamic> json) => Order(
    id: toInt(json['id_dathang']),
    total: toDouble(json['tongtien']),
    status: toText(json['trangthai']),
    address: toText(json['diachi']),
    paymentMethod: toText(json['PTTT']),
    createdAt: toText(json['created_at']),
    items: toMapList(json['chi_tiets']).map(OrderItem.fromJson).toList(),
  );

  final int id;
  final double total;
  final String status;
  final String address;
  final String paymentMethod;
  final String createdAt;
  final List<OrderItem> items;
}
