import '../../core/utils/parsers.dart';

class Pagination<T> {
  const Pagination({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory Pagination.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) fromJson,
  ) => Pagination(
    items: toMapList(json['data']).map(fromJson).toList(),
    currentPage: toInt(json['current_page']),
    lastPage: toInt(json['last_page']),
    total: toInt(json['total']),
  );

  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;
}
