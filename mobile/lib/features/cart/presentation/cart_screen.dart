import 'package:flutter/material.dart';

import '../../../app/app_dependencies.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/cart.dart';
import '../../../shared/widgets/commerce_widgets.dart';
import '../../../shared/widgets/network_image_box.dart';
import '../../../shared/widgets/state_content.dart';
import 'checkout_screen.dart';

class CartScreen extends StatefulWidget {
  const CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  bool _loading = true;
  String? _error;
  Cart? _cart;
  final Set<int> _busyItems = {};

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_loading && _cart == null && _error == null) _load();
  }

  Future<void> _load({bool showLoader = true}) async {
    if (showLoader) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      _cart = await AppScope.of(context).cartService.getCart();
    } catch (exception) {
      _error = '$exception';
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _update(CartItem item, int quantity) async {
    setState(() => _busyItems.add(item.id));
    try {
      await AppScope.of(
        context,
      ).cartService.update(itemId: item.id, quantity: quantity);
      await _load(showLoader: false);
    } catch (exception) {
      _showError(exception);
    } finally {
      if (mounted) setState(() => _busyItems.remove(item.id));
    }
  }

  Future<void> _remove(CartItem item) async {
    setState(() => _busyItems.add(item.id));
    try {
      await AppScope.of(context).cartService.remove(item.id);
      await _load(showLoader: false);
    } catch (exception) {
      _showError(exception);
    } finally {
      if (mounted) setState(() => _busyItems.remove(item.id));
    }
  }

  Future<void> _clear() async {
    try {
      await AppScope.of(context).cartService.clear();
      await _load(showLoader: false);
    } catch (exception) {
      _showError(exception);
    }
  }

  void _showError(Object exception) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text('$exception')));
  }

  @override
  Widget build(BuildContext context) {
    final cart = _cart;
    return Scaffold(
      appBar: AppBar(
        title: Text('Giỏ hàng${cart == null ? '' : ' (${cart.itemCount})'}'),
        actions: [
          IconButton(
            tooltip: 'Xóa tất cả',
            onPressed: cart?.items.isNotEmpty == true ? _clear : null,
            icon: const Icon(Icons.delete_sweep_outlined),
          ),
        ],
      ),
      body: StateContent(
        loading: _loading,
        error: _error,
        onRetry: _load,
        empty: !_loading && (cart == null || cart.items.isEmpty),
        emptyMessage: 'Giỏ hàng đang trống\nHãy chọn một sản phẩm bạn thích.',
        child: cart == null
            ? const SizedBox.shrink()
            : RefreshIndicator(
                onRefresh: _load,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(12, 10, 12, 24),
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEFF6FF),
                        border: Border.all(color: const Color(0xFFBFDBFE)),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Row(
                        children: [
                          Icon(
                            Icons.local_shipping_outlined,
                            color: AppColors.primary,
                          ),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Miễn phí giao hàng cho đơn từ 300.000 VND',
                              style: TextStyle(
                                color: AppColors.primaryDark,
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 10),
                    for (final item in cart.items) ...[
                      _CartItemCard(
                        item: item,
                        busy: _busyItems.contains(item.id),
                        onDecrease: item.quantity > 1
                            ? () => _update(item, item.quantity - 1)
                            : null,
                        onIncrease: item.quantity < item.stock
                            ? () => _update(item, item.quantity + 1)
                            : null,
                        onRemove: () => _remove(item),
                      ),
                      const SizedBox(height: 10),
                    ],
                  ],
                ),
              ),
      ),
      bottomNavigationBar: cart == null || cart.items.isEmpty
          ? null
          : SafeArea(
              top: false,
              child: Container(
                padding: const EdgeInsets.fromLTRB(14, 10, 14, 10),
                decoration: const BoxDecoration(
                  color: AppColors.surface,
                  border: Border(top: BorderSide(color: AppColors.border)),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Text(
                            'Tổng thanh toán',
                            style: TextStyle(
                              color: AppColors.muted,
                              fontSize: 11,
                            ),
                          ),
                          Text(
                            formatMoney(cart.total),
                            style: const TextStyle(
                              color: AppColors.danger,
                              fontSize: 18,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ],
                      ),
                    ),
                    FilledButton(
                      onPressed: () async {
                        await Navigator.of(context).push(
                          MaterialPageRoute<void>(
                            builder: (_) => const CheckoutScreen(),
                          ),
                        );
                        await _load(showLoader: false);
                      },
                      child: const Text('Mua hàng'),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}

class _CartItemCard extends StatelessWidget {
  const _CartItemCard({
    required this.item,
    required this.busy,
    required this.onDecrease,
    required this.onIncrease,
    required this.onRemove,
  });

  final CartItem item;
  final bool busy;
  final VoidCallback? onDecrease;
  final VoidCallback? onIncrease;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(10),
        child: Column(
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(6),
                  child: NetworkImageBox(
                    url: item.imageUrl,
                    width: 90,
                    height: 90,
                    fit: BoxFit.contain,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.productName,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: Theme.of(context).textTheme.titleSmall,
                      ),
                      const SizedBox(height: 5),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 6,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          item.variantName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: AppColors.muted,
                            fontSize: 10,
                          ),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        formatMoney(item.unitPrice),
                        style: const TextStyle(
                          color: AppColors.danger,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  tooltip: 'Xóa',
                  onPressed: busy ? null : onRemove,
                  icon: const Icon(Icons.delete_outline, size: 20),
                ),
              ],
            ),
            const Divider(),
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Tạm tính: ${formatMoney(item.totalPrice)}',
                    style: const TextStyle(
                      color: AppColors.muted,
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                if (busy)
                  const SizedBox.square(
                    dimension: 24,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                else
                  QuantityStepper(
                    quantity: item.quantity,
                    compact: true,
                    onDecrease: onDecrease,
                    onIncrease: onIncrease,
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
