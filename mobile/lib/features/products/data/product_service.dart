import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/product.dart';

class ProductService {
  ProductService(this._apiClient);

  final ApiClient _apiClient;
  List<Product>? _cache;

  Future<List<Product>> getProducts({bool refresh = false}) async {
    if (!refresh && _cache != null) return _cache!;
    final response = await _apiClient.get(ApiEndpoints.products);
    return cacheProducts(toMapList(response).map(Product.fromJson).toList());
  }

  List<Product> cacheProducts(List<Product> products) {
    _cache = List.unmodifiable(products);
    return _cache!;
  }

  Future<List<Product>> getFeatured({int limit = 8}) async {
    final products = await getProducts();
    return products.take(limit).toList();
  }

  Future<List<Product>> search(String query) async {
    if (query.trim().isEmpty) return getProducts();
    final response = await _apiClient.get(
      ApiEndpoints.productSearch,
      query: {'q': query.trim()},
    );
    return toMapList(response).map(Product.fromJson).toList();
  }

  Future<List<Product>> getByCategory(int categoryId) async {
    final products = await getProducts();
    return products
        .where((product) => product.categoryId == categoryId)
        .toList();
  }

  Future<Product> getProduct(int id) async {
    final response = toMap(
      await _apiClient.get(ApiEndpoints.productDetail(id)),
    );
    return Product.fromJson(response);
  }
}
