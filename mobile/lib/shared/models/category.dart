import '../../core/utils/parsers.dart';

class Category {
  const Category({
    required this.id,
    required this.name,
    required this.status,
    this.parentId,
  });

  factory Category.fromJson(Map<String, dynamic> json) => Category(
    id: toInt(json['id_danhmuc']),
    name: toText(json['ten_danhmuc']),
    status: toText(json['trangthai']),
    parentId: json['id_danhmuc_cha'] == null
        ? null
        : toInt(json['id_danhmuc_cha']),
  );

  final int id;
  final String name;
  final String status;
  final int? parentId;
}
