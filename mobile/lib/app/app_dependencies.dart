import 'package:flutter/widgets.dart';

import '../core/network/api_client.dart';
import '../core/storage/auth_storage.dart';
import '../features/auth/data/auth_service.dart';
import '../features/auth/presentation/auth_controller.dart';
import '../features/cart/data/cart_service.dart';
import '../features/categories/data/category_service.dart';
import '../features/explore/data/content_service.dart';
import '../features/home/data/home_service.dart';
import '../features/orders/data/order_service.dart';
import '../features/products/data/product_service.dart';
import '../features/profile/data/profile_service.dart';
import '../features/wishlist/data/wishlist_service.dart';

class AppDependencies {
  AppDependencies._({
    required this.authStorage,
    required this.apiClient,
    required this.authService,
    required this.authController,
    required this.categoryService,
    required this.contentService,
    required this.homeService,
    required this.productService,
    required this.cartService,
    required this.orderService,
    required this.profileService,
    required this.wishlistService,
  });

  factory AppDependencies.create({AuthStorage? authStorage}) {
    final storage = authStorage ?? SecureAuthStorage();
    final client = ApiClient(storage);
    final authService = AuthService(client, storage);
    final authController = AuthController(authService, storage);
    final categoryService = CategoryService(client);
    final productService = ProductService(client);
    client.onUnauthorized = authController.handleUnauthorized;

    return AppDependencies._(
      authStorage: storage,
      apiClient: client,
      authService: authService,
      authController: authController,
      categoryService: categoryService,
      contentService: ContentService(client),
      homeService: HomeService(client, categoryService),
      productService: productService,
      cartService: CartService(client),
      orderService: OrderService(client),
      profileService: ProfileService(client),
      wishlistService: WishlistService(client),
    );
  }

  final AuthStorage authStorage;
  final ApiClient apiClient;
  final AuthService authService;
  final AuthController authController;
  final CategoryService categoryService;
  final ContentService contentService;
  final HomeService homeService;
  final ProductService productService;
  final CartService cartService;
  final OrderService orderService;
  final ProfileService profileService;
  final WishlistService wishlistService;
}

class AppScope extends InheritedWidget {
  const AppScope({required this.dependencies, required super.child, super.key});

  final AppDependencies dependencies;

  static AppDependencies of(BuildContext context) {
    final scope = context.dependOnInheritedWidgetOfExactType<AppScope>();
    assert(scope != null, 'AppScope is missing');
    return scope!.dependencies;
  }

  @override
  bool updateShouldNotify(AppScope oldWidget) =>
      dependencies != oldWidget.dependencies;
}
