import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/wishlist_item.dart';
import '../../../shared/widgets/network_image_box.dart';
import '../../../shared/widgets/state_content.dart';
import '../../cart/presentation/cart_screen.dart';
import '../../products/presentation/product_detail_screen.dart';

class WishlistScreen extends StatefulWidget {
  const WishlistScreen({super.key});

  @override
  State<WishlistScreen> createState() => _WishlistScreenState();
}

class _WishlistScreenState extends State<WishlistScreen> {
  bool _loading = true;
  String? _error;
  List<WishlistItem> _items = const [];

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_loading && _items.isEmpty && _error == null) _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      _items = await AppScope.of(context).wishlistService.getItems();
    } catch (exception) {
      _error = '$exception';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _remove(WishlistItem item) async {
    try {
      await AppScope.of(context).wishlistService.remove(item.id);
      if (mounted) {
        setState(() => _items = _items.where((e) => e.id != item.id).toList());
      }
    } catch (exception) {
      _show(exception);
    }
  }

  Future<void> _moveToCart(WishlistItem item) async {
    final deps = AppScope.of(context);
    try {
      await deps.cartService.add(
        variantId: item.variantId,
        quantity: item.quantity,
      );
      await deps.wishlistService.remove(item.id);
      if (!mounted) return;
      setState(() => _items = _items.where((e) => e.id != item.id).toList());
      await Navigator.of(
        context,
      ).push(MaterialPageRoute<void>(builder: (_) => const CartScreen()));
    } catch (exception) {
      _show(exception);
    }
  }

  void _show(Object message) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text('$message')));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sản phẩm yêu thích')),
      body: StateContent(
        loading: _loading,
        error: _error,
        onRetry: _load,
        empty: !_loading && _items.isEmpty,
        emptyMessage: 'Bạn chưa lưu sản phẩm nào',
        child: RefreshIndicator(
          onRefresh: _load,
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(12, 8, 12, 24),
            itemCount: _items.length,
            separatorBuilder: (_, _) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final item = _items[index];
              return Card(
                child: InkWell(
                  onTap: item.productId == 0
                      ? null
                      : () => Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) =>
                                ProductDetailScreen(productId: item.productId),
                          ),
                        ),
                  child: Padding(
                    padding: const EdgeInsets.all(10),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(6),
                          child: NetworkImageBox(
                            url: item.imageUrl,
                            width: 92,
                            height: 92,
                            fit: BoxFit.contain,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                item.name,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              if (item.variantName.isNotEmpty) ...[
                                const SizedBox(height: 4),
                                Text(
                                  item.variantName,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: AppColors.muted,
                                    fontSize: 11,
                                  ),
                                ),
                              ],
                              const SizedBox(height: 7),
                              Text(
                                formatMoney(item.price),
                                style: const TextStyle(
                                  color: AppColors.danger,
                                  fontSize: 14,
                                  fontWeight: FontWeight.w900,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Expanded(
                                    child: FilledButton.icon(
                                      onPressed: item.stock > 0
                                          ? () => _moveToCart(item)
                                          : null,
                                      icon: const Icon(
                                        Icons.shopping_cart_outlined,
                                        size: 17,
                                      ),
                                      label: const Text('Thêm vào giỏ'),
                                    ),
                                  ),
                                  IconButton(
                                    tooltip: 'Xóa khỏi yêu thích',
                                    onPressed: () => _remove(item),
                                    icon: const Icon(
                                      Icons.delete_outline,
                                      color: AppColors.danger,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
