import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/category.dart';
import '../../../shared/models/product.dart';
import '../../categories/data/category_service.dart';

class HomeData {
  const HomeData({required this.categories, required this.products});

  final List<Category> categories;
  final List<Product> products;
}

class HomeService {
  HomeService(this._apiClient, this._categoryService);

  final ApiClient _apiClient;
  final CategoryService _categoryService;
  HomeData? _cache;
  Future<HomeData>? _pending;

  Future<HomeData> getHome({bool refresh = false}) {
    if (!refresh && _cache != null) return Future.value(_cache!);
    if (!refresh && _pending != null) return _pending!;

    final request = _load();
    _pending = request;
    return request.whenComplete(() => _pending = null);
  }

  Future<HomeData> _load() async {
    final response = toMap(await _apiClient.get(ApiEndpoints.mobileHome));
    final categories = _categoryService.cacheCategories(
      toMapList(response['categories']).map(Category.fromJson).toList(),
    );
    final products = toMapList(
      response['products'],
    ).map(Product.fromJson).toList();
    return _cache = HomeData(categories: categories, products: products);
  }
}
