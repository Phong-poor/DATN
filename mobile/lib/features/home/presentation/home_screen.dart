import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/models/category.dart';
import '../../../shared/models/product.dart';
import '../../../shared/widgets/commerce_widgets.dart';
import '../../../shared/widgets/state_content.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../auth/presentation/login_screen.dart';
import '../../cart/presentation/cart_screen.dart';
import '../../explore/presentation/chat_assistant_screen.dart';
import '../../explore/presentation/contact_screen.dart';
import '../../explore/presentation/news_screen.dart';
import '../../explore/presentation/promotions_screen.dart';
import '../../products/presentation/product_detail_screen.dart';
import '../../products/presentation/product_list_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  bool _loading = true;
  String? _error;
  List<Category> _categories = const [];
  List<Product> _products = const [];

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_loading && _categories.isEmpty && _error == null) _load();
  }

  Future<void> _load({bool refresh = false}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final home = await AppScope.of(
        context,
      ).homeService.getHome(refresh: refresh);
      _categories = home.categories;
      _products = home.products.take(8).toList();
    } catch (exception) {
      _error = '$exception';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _openProducts([Category? category]) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => ProductListScreen(category: category),
      ),
    );
  }

  Future<void> _openCart() async {
    final auth = AppScope.of(context).authController;
    if (auth.status != AuthStatus.authenticated) {
      await Navigator.of(
        context,
      ).push(MaterialPageRoute<void>(builder: (_) => const LoginScreen()));
      if (!mounted || auth.status != AuthStatus.authenticated) return;
    }
    if (!mounted) return;
    await Navigator.of(
      context,
    ).push(MaterialPageRoute<void>(builder: (_) => const CartScreen()));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          if (_error != null && _products.isEmpty)
            StateContent(
              loading: false,
              error: _error,
              onRetry: _load,
              child: const SizedBox.shrink(),
            )
          else if (!_loading && _products.isEmpty)
            StateContent(
              loading: false,
              empty: true,
              onRetry: _load,
              child: const SizedBox.shrink(),
            )
          else
            RefreshIndicator(
              onRefresh: () => _load(refresh: true),
              child: CustomScrollView(
                slivers: [
                  SliverToBoxAdapter(child: _HomeHeader(onCartTap: _openCart)),
                  const SliverToBoxAdapter(child: _AnnouncementStrip()),
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(14, 18, 14, 10),
                      child: SectionHeader(
                        title: 'Danh mục nổi bật',
                        actionLabel: 'Xem tất cả',
                        onAction: _openProducts,
                      ),
                    ),
                  ),
                  SliverToBoxAdapter(
                    child: SizedBox(
                      height: 86,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 14),
                        scrollDirection: Axis.horizontal,
                        itemCount: _categories.length,
                        separatorBuilder: (_, _) => const SizedBox(width: 8),
                        itemBuilder: (context, index) {
                          final category = _categories[index];
                          return CategoryIconTile(
                            category: category,
                            onTap: () => _openProducts(category),
                          );
                        },
                      ),
                    ),
                  ),
                  const SliverToBoxAdapter(
                    child: Padding(
                      padding: EdgeInsets.fromLTRB(14, 18, 14, 0),
                      child: _QuickUtilities(),
                    ),
                  ),
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(14, 18, 14, 0),
                      child: _HeroBanner(onTap: _openProducts),
                    ),
                  ),
                  const SliverToBoxAdapter(
                    child: Padding(
                      padding: EdgeInsets.fromLTRB(14, 12, 14, 4),
                      child: _ServiceStrip(),
                    ),
                  ),
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(14, 20, 14, 10),
                      child: SectionHeader(
                        title: 'Gợi ý hôm nay',
                        subtitle: 'Laptop chính hãng dành cho bạn',
                        actionLabel: 'Xem thêm',
                        onAction: _openProducts,
                      ),
                    ),
                  ),
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(14, 0, 14, 24),
                    sliver: SliverGrid(
                      delegate: SliverChildBuilderDelegate((context, index) {
                        final product = _products[index];
                        return ProductGridCard(
                          product: product,
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute<void>(
                              builder: (_) =>
                                  ProductDetailScreen(productId: product.id),
                            ),
                          ),
                        );
                      }, childCount: _products.length),
                      gridDelegate:
                          const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            mainAxisSpacing: 10,
                            crossAxisSpacing: 10,
                            mainAxisExtent: 274,
                          ),
                    ),
                  ),
                ],
              ),
            ),
          if (_loading)
            const Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: LinearProgressIndicator(minHeight: 2),
            ),
        ],
      ),
    );
  }
}

class _QuickUtilities extends StatelessWidget {
  const _QuickUtilities();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          _QuickUtility(
            icon: Icons.local_offer_outlined,
            label: 'Khuyến mãi',
            color: AppColors.danger,
            onTap: () => _open(context, const PromotionsScreen()),
          ),
          _QuickUtility(
            icon: Icons.newspaper_outlined,
            label: 'Tin công nghệ',
            color: AppColors.primary,
            onTap: () => _open(context, const NewsScreen()),
          ),
          _QuickUtility(
            icon: Icons.auto_awesome_outlined,
            label: 'Trợ lý AI',
            color: AppColors.warning,
            onTap: () => _open(context, const ChatAssistantScreen()),
          ),
          _QuickUtility(
            icon: Icons.support_agent_outlined,
            label: 'Liên hệ',
            color: AppColors.success,
            onTap: () => _open(context, const ContactScreen()),
          ),
        ],
      ),
    );
  }

  static void _open(BuildContext context, Widget screen) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => screen));
  }
}

class _QuickUtility extends StatelessWidget {
  const _QuickUtility({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 2, vertical: 4),
          child: Column(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, color: color, size: 22),
              ),
              const SizedBox(height: 7),
              Text(
                label,
                maxLines: 2,
                textAlign: TextAlign.center,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 10,
                  height: 1.15,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _HomeHeader extends StatelessWidget {
  const _HomeHeader({required this.onCartTap});

  final VoidCallback onCartTap;

  @override
  Widget build(BuildContext context) {
    return ColoredBox(
      color: AppColors.surface,
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(14, 8, 8, 12),
          child: Column(
            children: [
              Row(
                children: [
                  const Expanded(child: BrandMark(compact: true)),
                  IconButton(
                    tooltip: 'Thông báo',
                    onPressed: () => ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Chưa có thông báo mới')),
                    ),
                    icon: const Icon(Icons.notifications_none_outlined),
                  ),
                  IconButton(
                    tooltip: 'Giỏ hàng',
                    onPressed: onCartTap,
                    icon: const Icon(Icons.shopping_cart_outlined),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              CommerceSearchField(
                readOnly: true,
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => const ProductListScreen(autofocus: true),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AnnouncementStrip extends StatelessWidget {
  const _AnnouncementStrip();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.primary,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      child: const Row(
        children: [
          Icon(Icons.bolt, color: Colors.white, size: 16),
          SizedBox(width: 6),
          Expanded(
            child: Text(
              'Freeship đơn từ 300K • Trả góp 0% • Bảo hành chính hãng',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: Colors.white,
                fontSize: 11,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _HeroBanner extends StatelessWidget {
  const _HeroBanner({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 218,
      child: Material(
        color: AppColors.navy,
        borderRadius: BorderRadius.circular(8),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onTap,
          child: Stack(
            children: [
              Positioned(
                right: -34,
                bottom: -5,
                width: 235,
                height: 175,
                child: Image.asset(
                  'assets/images/hero_laptop.png',
                  cacheWidth: 720,
                  fit: BoxFit.contain,
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(18),
                child: SizedBox(
                  width: 175,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const StatusPill(
                        label: 'PREMIUM LAPTOP 2026',
                        color: AppColors.primary,
                      ),
                      const Spacer(),
                      const Text(
                        'Sức mạnh hội tụ',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 22,
                          height: 1.05,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 5),
                      const Text(
                        'Laptop cao cấp cho gaming, sáng tạo và công việc.',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Color(0xFFCBD5E1),
                          fontSize: 11,
                          height: 1.25,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 7,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: const Text(
                          'Khám phá ngay',
                          style: TextStyle(
                            color: AppColors.navy,
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ServiceStrip extends StatelessWidget {
  const _ServiceStrip();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(8),
      ),
      child: const Row(
        children: [
          _ServiceItem(
            Icons.local_shipping_outlined,
            'Giao nhanh',
            AppColors.primary,
          ),
          _ServiceItem(
            Icons.verified_user_outlined,
            'Chính hãng',
            AppColors.success,
          ),
          _ServiceItem(
            Icons.credit_card_outlined,
            'Trả góp 0%',
            AppColors.warning,
          ),
        ],
      ),
    );
  }
}

class _ServiceItem extends StatelessWidget {
  const _ServiceItem(this.icon, this.label, this.color);

  final IconData icon;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 4),
          Text(
            label,
            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}
