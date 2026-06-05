import '../../core/config/api_config.dart';
import '../../core/utils/parsers.dart';

class WishlistItem {
  const WishlistItem({
    required this.id,
    required this.variantId,
    required this.productId,
    required this.quantity,
    required this.name,
    required this.variantName,
    required this.imageUrl,
    required this.price,
    required this.stock,
  });

  factory WishlistItem.fromJson(Map<String, dynamic> json) {
    final variant = toMap(json['bienthe']);
    final product = toMap(variant['sanpham'] ?? variant['san_pham']);
    return WishlistItem(
      id: toInt(json['id']),
      variantId: toInt(json['id_bienthe'] ?? variant['id_bienthe']),
      productId: toInt(product['id_sanpham']),
      quantity: toInt(json['soluong']),
      name: toText(product['tenSP']),
      variantName: toText(variant['ten_bienthe']),
      imageUrl: ApiConfig.assetUrl(
        toText(variant['hinhanh'] ?? product['hinhanh']),
      ),
      price: toDouble(variant['gia']),
      stock: toInt(variant['soluong']),
    );
  }

  final int id;
  final int variantId;
  final int productId;
  final int quantity;
  final String name;
  final String variantName;
  final String imageUrl;
  final double price;
  final int stock;
}
