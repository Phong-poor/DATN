import 'dart:convert';

import '../../core/config/api_config.dart';
import '../../core/utils/parsers.dart';
import 'category.dart';

class ProductImage {
  const ProductImage({required this.path, required this.url, this.order = 0});

  factory ProductImage.fromJson(Map<String, dynamic> json) {
    final path = toText(json['duongdan']);
    return ProductImage(
      path: path,
      url: ApiConfig.assetUrl(path),
      order: toInt(json['thutu']),
    );
  }

  final String path;
  final String url;
  final int order;
}

class ProductVariant {
  const ProductVariant({
    required this.id,
    required this.name,
    required this.price,
    required this.stock,
    required this.attributes,
  });

  factory ProductVariant.fromJson(Map<String, dynamic> json) => ProductVariant(
    id: toInt(json['id_bienthe']),
    name: toText(json['ten_bienthe']),
    price: toDouble(json['gia']),
    stock: toInt(json['soluong']),
    attributes: _parseAttributes(json['thuoc_tinh'] ?? json['thuoc_tinh_json']),
  );

  final int id;
  final String name;
  final double price;
  final int stock;
  final List<Map<String, dynamic>> attributes;

  static List<Map<String, dynamic>> _parseAttributes(dynamic value) {
    if (value is List) return toMapList(value);
    if (value is String && value.isNotEmpty) {
      try {
        return toMapList(jsonDecode(value));
      } catch (_) {
        return const [];
      }
    }
    return const [];
  }
}

class Product {
  const Product({
    required this.id,
    required this.name,
    required this.sku,
    required this.imageUrl,
    required this.status,
    required this.categoryId,
    required this.category,
    required this.brandName,
    required this.variants,
    required this.images,
    required this.specifications,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    final categoryMap = toMap(json['danh_muc']);
    final brandMap = toMap(json['thuong_hieu']);
    return Product(
      id: toInt(json['id_sanpham']),
      name: toText(json['tenSP']),
      sku: toText(json['SKU']),
      imageUrl: ApiConfig.assetUrl(toText(json['hinhanh'])),
      status: toText(json['trangthai']),
      categoryId: toInt(json['id_danhmuc']),
      category: categoryMap.isEmpty ? null : Category.fromJson(categoryMap),
      brandName: toText(brandMap['ten_thuonghieu']),
      variants: toMapList(
        json['bien_thes'],
      ).map(ProductVariant.fromJson).toList(),
      images: toMapList(json['hinh_anhs']).map(ProductImage.fromJson).toList(),
      specifications: toMapList(json['thong_so_ky_thuat']),
    );
  }

  final int id;
  final String name;
  final String sku;
  final String imageUrl;
  final String status;
  final int categoryId;
  final Category? category;
  final String brandName;
  final List<ProductVariant> variants;
  final List<ProductImage> images;
  final List<Map<String, dynamic>> specifications;

  ProductVariant? get firstAvailableVariant {
    for (final variant in variants) {
      if (variant.stock > 0) return variant;
    }
    return variants.isEmpty ? null : variants.first;
  }

  double get displayPrice => firstAvailableVariant?.price ?? 0;
}
