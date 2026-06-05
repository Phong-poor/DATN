import '../../core/config/api_config.dart';
import '../../core/utils/parsers.dart';

class NewsArticle {
  const NewsArticle({
    required this.id,
    required this.title,
    required this.category,
    required this.author,
    required this.imageUrl,
    required this.excerpt,
    required this.content,
    required this.publishedAt,
    required this.views,
  });

  factory NewsArticle.fromJson(Map<String, dynamic> json) => NewsArticle(
    id: toInt(json['id']),
    title: toText(json['title']),
    category: toText(json['category']),
    author: toText(json['author']),
    imageUrl: ApiConfig.assetUrl(toText(json['image'])),
    excerpt: toText(json['excerpt']),
    content: toText(json['content']),
    publishedAt: toText(json['published_at']),
    views: toInt(json['views']),
  );

  final int id;
  final String title;
  final String category;
  final String author;
  final String imageUrl;
  final String excerpt;
  final String content;
  final String publishedAt;
  final int views;

  String get readableContent => content
      .replaceAll(RegExp(r'!\[[^\]]*\]\([^)]+\)'), '')
      .replaceAllMapped(RegExp(r'\[([^\]]+)\]\([^)]+\)'), (match) => match[1]!)
      .replaceAll(RegExp(r'^#{1,6}\s*', multiLine: true), '')
      .replaceAll('**', '')
      .replaceAll(RegExp(r'\n{3,}'), '\n\n')
      .trim();
}

class Promotion {
  const Promotion({
    required this.id,
    required this.name,
    required this.category,
    required this.code,
    required this.type,
    required this.value,
    required this.description,
    required this.startDate,
    required this.endDate,
    required this.status,
    required this.condition,
  });

  factory Promotion.fromJson(Map<String, dynamic> json) => Promotion(
    id: toInt(json['id']),
    name: toText(json['name']),
    category: toText(json['category']),
    code: toText(json['code']),
    type: toText(json['type']),
    value: toDouble(json['value']),
    description: toText(json['mota']),
    startDate: toText(json['start_date']),
    endDate: toText(json['end_date']),
    status: toText(json['status']),
    condition: toDouble(json['dieu_kien']),
  );

  final int id;
  final String name;
  final String category;
  final String code;
  final String type;
  final double value;
  final String description;
  final String startDate;
  final String endDate;
  final String status;
  final double condition;

  String get offerLabel => switch ((category, type)) {
    ('freeship', _) => 'FREESHIP',
    (_, 'percent') => 'GIẢM ${value.round()}%',
    _ => 'GIẢM ${formatMoney(value)}',
  };
}

class ChatProduct {
  const ChatProduct({
    required this.productId,
    required this.variantId,
    required this.name,
    required this.price,
    required this.imageUrl,
  });

  factory ChatProduct.fromJson(Map<String, dynamic> json) {
    final product = toMap(json['san_pham'] ?? json['sanPham']);
    return ChatProduct(
      productId: toInt(json['id_sanpham'] ?? product['id_sanpham']),
      variantId: toInt(json['id_bienthe']),
      name: [
        toText(product['tenSP']),
        toText(json['ten_bienthe']),
      ].where((value) => value.isNotEmpty).join(' - '),
      price: toDouble(json['gia']),
      imageUrl: ApiConfig.assetUrl(
        toText(product['hinhanh'] ?? json['hinhanh']),
      ),
    );
  }

  final int productId;
  final int variantId;
  final String name;
  final double price;
  final String imageUrl;
}

class ChatReply {
  const ChatReply({required this.message, required this.products});

  factory ChatReply.fromJson(Map<String, dynamic> json) => ChatReply(
    message: toText(json['reply']),
    products: toMapList(json['products']).map(ChatProduct.fromJson).toList(),
  );

  final String message;
  final List<ChatProduct> products;
}
