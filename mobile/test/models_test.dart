import 'package:datn_mobile/shared/models/cart.dart';
import 'package:datn_mobile/shared/models/order.dart';
import 'package:datn_mobile/shared/models/product.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('parses Laravel product response', () {
    final product = Product.fromJson({
      'id_sanpham': 12,
      'tenSP': 'Laptop Test',
      'SKU': 'SKU-12',
      'id_danhmuc': 3,
      'bien_thes': [
        {
          'id_bienthe': 5,
          'ten_bienthe': '16GB',
          'gia': '25000000',
          'soluong': 2,
        },
      ],
    });

    expect(product.id, 12);
    expect(product.displayPrice, 25000000);
    expect(product.firstAvailableVariant?.id, 5);
  });

  test('parses Laravel cart response', () {
    final cart = Cart.fromJson({
      'gio_hang': [
        {
          'id_giohang': 1,
          'id_bienthe': 5,
          'ten_san_pham': 'Laptop Test',
          'soluong': 2,
          'gia': 100,
          'thanh_tien': 200,
        },
      ],
      'tong_tien': 200,
      'so_luong_san_pham': 1,
    });

    expect(cart.items.single.quantity, 2);
    expect(cart.total, 200);
  });

  test('parses checkout order and payment URL', () {
    final result = CheckoutResult.fromJson({
      'order': {
        'id_dathang': 9,
        'tongtien': 120000,
        'trangthai': 'pending',
        'PTTT': 'VNPay',
      },
      'payUrl': 'https://payment.example.test/order/9',
    });

    expect(result.order.id, 9);
    expect(result.paymentUrl, contains('/order/9'));
  });
}
