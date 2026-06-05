import '../../core/config/api_config.dart';
import '../../core/utils/parsers.dart';

class CartItem {
  const CartItem({
    required this.id,
    required this.variantId,
    required this.productName,
    required this.variantName,
    required this.imageUrl,
    required this.quantity,
    required this.stock,
    required this.unitPrice,
    required this.totalPrice,
  });

  factory CartItem.fromJson(Map<String, dynamic> json) => CartItem(
    id: toInt(json['id_giohang']),
    variantId: toInt(json['id_bienthe']),
    productName: toText(json['ten_san_pham']),
    variantName: toText(json['ten_bienthe']),
    imageUrl: ApiConfig.assetUrl(toText(json['hinh_anh'])),
    quantity: toInt(json['soluong']),
    stock: toInt(json['ton_kho']),
    unitPrice: toDouble(json['gia']),
    totalPrice: toDouble(json['thanh_tien']),
  );

  final int id;
  final int variantId;
  final String productName;
  final String variantName;
  final String imageUrl;
  final int quantity;
  final int stock;
  final double unitPrice;
  final double totalPrice;
}

class Cart {
  const Cart({
    required this.items,
    required this.total,
    required this.itemCount,
  });

  factory Cart.fromJson(Map<String, dynamic> json) => Cart(
    items: toMapList(json['gio_hang']).map(CartItem.fromJson).toList(),
    total: toDouble(json['tong_tien']),
    itemCount: toInt(json['so_luong_san_pham']),
  );

  final List<CartItem> items;
  final double total;
  final int itemCount;
}
