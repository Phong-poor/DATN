import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/category.dart';

class CategoryService {
  CategoryService(this._apiClient);

  final ApiClient _apiClient;
  List<Category>? _cache;

  Future<List<Category>> getCategories({bool refresh = false}) async {
    if (!refresh && _cache != null) return _cache!;
    final response = toMap(await _apiClient.get(ApiEndpoints.categories));
    return cacheCategories(
      toMapList(response['data']).map(Category.fromJson).toList(),
    );
  }

  List<Category> cacheCategories(List<Category> categories) {
    _cache = List.unmodifiable(categories);
    return _cache!;
  }

  Future<Category> getCategory(int id) async {
    final response = toMap(
      await _apiClient.get(ApiEndpoints.categoryDetail(id)),
    );
    return Category.fromJson(toMap(response['data']));
  }
}
